<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Http\Response;

use Mheads\Yii\Table\Export\TableExportService;
use Mheads\Yii\Table\Http\Response\Payload\JsonTablePayloadResponder;
use Mheads\Yii\Table\Http\Response\Payload\TablePayloadResponderInterface;
use Mheads\Yii\Table\Provider\TableProviderInterface;
use Mheads\Yii\Table\Serialization\TableArraySerializer;
use Mheads\Yii\Table\Serialization\TableSerializerInterface;
use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Throwable;
use Yiisoft\Data\Paginator\InvalidPageException;

use function is_string;

final class TableHttpResponder implements TableHttpResponderInterface
{
	private readonly TablePayloadResponderInterface $payloadResponder;
	private readonly TableExportHttpResponder $exportResponder;

	public function __construct(
		ResponseFactoryInterface $responseFactory,
		StreamFactoryInterface $streamFactory,
		private readonly TableSerializerInterface $serializer = new TableArraySerializer(),
		TableExportService $exportService = new TableExportService(),
		?TablePayloadResponderInterface $payloadResponder = null,
	) {
		$this->payloadResponder = $payloadResponder ?? new JsonTablePayloadResponder(
			$responseFactory,
			$streamFactory,
		);
		$this->exportResponder = new TableExportHttpResponder(
			$responseFactory,
			$streamFactory,
			$exportService,
			$this->payloadResponder,
		);
	}

	#[Override]
	/**
	 * @param callable(Throwable, TableProviderInterface, ServerRequestInterface, string): ?ResponseInterface|null $onExportException
	 * @throws InvalidPageException
	 */
	public function respond(
		TableProviderInterface $table,
		ServerRequestInterface $request,
		?callable $onExportException = null,
	): ResponseInterface {
		$exportCode = $this->resolveExportCode($request, $table->exportParam());
		if ($exportCode === null)
		{
			return $this->payloadResponder->respond($this->serializer->serialize($table));
		}

		return $this->exportResponder->respondExport($table, $request, $exportCode, $onExportException);
	}

	private function resolveExportCode(ServerRequestInterface $request, string $exportParam): ?string
	{
		$queryParams = $request->getQueryParams();
		$value = $queryParams[$exportParam] ?? null;
		if (!is_string($value) || $value === '')
		{
			return null;
		}

		return $value;
	}
}
