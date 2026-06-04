<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Http\Response;

use Mheads\Yii\Table\Export\Exception\ExportTimeoutException;
use Mheads\Yii\Table\Export\Exception\UnsupportedExportFormatException;
use Mheads\Yii\Table\Export\ExportGeneratorInterface;
use Mheads\Yii\Table\Export\TableExportService;
use Mheads\Yii\Table\Http\Response\Payload\TablePayloadResponderInterface;
use Mheads\Yii\Table\Provider\TableProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;
use Throwable;

use function fopen;
use function is_resource;
use function rewind;
use function sprintf;

final class TableExportHttpResponder
{
	public function __construct(
		private readonly ResponseFactoryInterface $responseFactory,
		private readonly StreamFactoryInterface $streamFactory,
		private readonly TableExportService $exportService,
		private readonly TablePayloadResponderInterface $payloadResponder,
	) {}

	/**
	 * @param callable(Throwable, TableProviderInterface, ServerRequestInterface, string): ?ResponseInterface|null $onExportException
	 */
	public function respondExport(
		TableProviderInterface $table,
		ServerRequestInterface $request,
		string $exportCode,
		?callable $onExportException = null,
	): ResponseInterface {
		$target = fopen('php://temp', 'w+b');
		if (!is_resource($target))
		{
			throw new RuntimeException('Unable to open temporary export stream.');
		}

		try
		{
			$generator = $this->exportService->run($table, $exportCode, $target);
		}
		catch (Throwable $exception)
		{
			if ($onExportException !== null)
			{
				$intercepted = $onExportException($exception, $table, $request, $exportCode);
				if ($intercepted !== null)
				{
					return $intercepted;
				}
			}

			[$payload, $statusCode] = $this->defaultErrorPayload($exception);
			return $this->payloadResponder->respond($payload, $statusCode);
		}

		rewind($target);
		return $this->streamResponse($generator, $target);
	}

	/**
	 * @return array{array<string, string|null>, int}
	 */
	private function defaultErrorPayload(Throwable $exception): array
	{
		if ($exception instanceof UnsupportedExportFormatException)
		{
			return [['errorCode' => 'unsupported_format', 'errorMessage' => $exception->getMessage()], 400];
		}

		if ($exception instanceof ExportTimeoutException)
		{
			return [['errorCode' => 'timeout', 'errorMessage' => $exception->getMessage()], 422];
		}

		return [['errorCode' => 'export_failed', 'errorMessage' => $exception->getMessage()], 422];
	}

	/**
	 * @param resource $target
	 */
	private function streamResponse(ExportGeneratorInterface $generator, mixed $target): ResponseInterface
	{
		$stream = $this->streamFactory->createStreamFromResource($target);

		$fileName = $generator->fileName() ?? 'export';
		$fullName = $fileName . '.' . $generator->writer()->extension();
		$contentType = $generator->writer()->mimeType() ?? 'application/octet-stream';

		return $this
			->responseFactory
			->createResponse(200)
			->withHeader('Content-Type', $contentType)
			->withHeader('Content-Disposition', sprintf('attachment; filename="%s"', $fullName))
			->withBody($stream);
	}
}
