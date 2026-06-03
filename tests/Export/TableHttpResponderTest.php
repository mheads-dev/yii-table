<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Export;

use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Message\StreamFactory;
use Mheads\Yii\Table\Export\Exception\ExportTimeoutException;
use Mheads\Yii\Table\Export\ExportGeneratorInterface;
use Mheads\Yii\Table\Export\RowsReader\RowsReaderInterface;
use Mheads\Yii\Table\Export\Writer\WriterInterface;
use Mheads\Yii\Table\Http\Response\Payload\TablePayloadResponderInterface;
use Mheads\Yii\Table\Http\Response\TableHttpResponder;
use Mheads\Yii\Table\Provider\TableProviderInterface;
use Mheads\Yii\Table\Serialization\TableSerializerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * @internal
 */
final class TableHttpResponderTest extends TestCase
{
	public function testDelegatesPayloadResponseToCustomResponder(): void
	{
		$table = self::createStub(TableProviderInterface::class);
		$table->method('exportParam')->willReturn('export');

		$serializer = self::createStub(TableSerializerInterface::class);
		$serializer->method('serialize')->willReturn(['rows' => [['id' => 1]]]);

		$customResponder = new TableHttpResponderCustomPayloadResponder();
		$responder = new TableHttpResponder(
			new ResponseFactory(),
			new StreamFactory(),
			$serializer,
			payloadResponder: $customResponder,
		);

		$response = $responder->respond($table, (new ServerRequest())->withQueryParams([]));

		self::assertSame(['rows' => [['id' => 1]]], $customResponder->lastPayload);
		self::assertSame(299, $response->getStatusCode());
		self::assertSame('application/xml', $response->getHeaderLine('Content-Type'));
		self::assertSame('<ok/>', (string)$response->getBody());
	}

	public function testUsesJsonPayloadResponseByDefault(): void
	{
		$table = self::createStub(TableProviderInterface::class);
		$table->method('exportParam')->willReturn('export');

		$serializer = self::createStub(TableSerializerInterface::class);
		$serializer->method('serialize')->willReturn(['ok' => true]);

		$responder = new TableHttpResponder(new ResponseFactory(), new StreamFactory(), $serializer);
		$response = $responder->respond($table, (new ServerRequest())->withQueryParams([]));

		self::assertSame(200, $response->getStatusCode());
		self::assertStringStartsWith('application/json', $response->getHeaderLine('Content-Type'));
		self::assertSame('{"ok":true}', (string)$response->getBody());
	}

	public function testReturnsTimeoutPayloadWhenExportTimesOut(): void
	{
		$table = self::createStub(TableProviderInterface::class);
		$table->method('exportParam')->willReturn('export');
		$table->method('exportGenerators')->willReturn([new TableHttpResponderExportGeneratorStub()]);

		$responder = new TableHttpResponder(new ResponseFactory(), new StreamFactory());
		$response = $responder->respond($table, (new ServerRequest())->withQueryParams(['export' => 'csv']));

		self::assertSame(422, $response->getStatusCode());
		self::assertSame('{"errorCode":"timeout","errorMessage":"Export timeout exceeded."}', (string)$response->getBody());
	}

	public function testDelegatesExportExceptionToCallback(): void
	{
		$table = self::createStub(TableProviderInterface::class);
		$table->method('exportParam')->willReturn('export');
		$table->method('exportGenerators')->willReturn([new TableHttpResponderExportGeneratorStub()]);

		$capturedExportCode = null;
		$capturedException = null;
		$responder = new TableHttpResponder(
			new ResponseFactory(),
			new StreamFactory(),
		);
		$response = $responder->respond(
			$table,
			(new ServerRequest())->withQueryParams(['export' => 'csv']),
			static function (Throwable $exception, TableProviderInterface $_table, ServerRequestInterface $_request, string $exportCode) use (&$capturedExportCode, &$capturedException): ResponseInterface {
				$capturedExportCode = $exportCode;
				$capturedException = $exception;
				return (new ResponseFactory())->createResponse(202);
			},
		);

		self::assertSame('csv', $capturedExportCode);
		self::assertInstanceOf(ExportTimeoutException::class, $capturedException);
		self::assertSame(202, $response->getStatusCode());
	}
}

/**
 * @internal
 */
final class TableHttpResponderCustomPayloadResponder implements TablePayloadResponderInterface
{
	/** @var array<string, mixed>|null */
	public ?array $lastPayload = null;

	public function respond(array $payload, int $statusCode = 200): ResponseInterface
	{
		$this->lastPayload = $payload;

		return (new ResponseFactory())
			->createResponse(299)
			->withHeader('Content-Type', 'application/xml')
			->withBody((new StreamFactory())->createStream('<ok/>'));
	}
}

/**
 * @internal
 */
final class TableHttpResponderExportGeneratorStub implements ExportGeneratorInterface
{
	public function code(): string
	{
		return 'csv';
	}

	public function writer(): WriterInterface
	{
		return new class implements WriterInterface {
			public function code(): string
			{
				return 'csv';
			}

			public function mimeType(): ?string
			{
				return 'text/csv';
			}

			public function extension(): string
			{
				return 'csv';
			}

			public function write(
				RowsReaderInterface $rowsReader,
				mixed $target,
			): void {
				throw new ExportTimeoutException('Export timeout exceeded.');
			}
		};
	}

	public function rowsReader(): RowsReaderInterface
	{
		return new class implements RowsReaderInterface {
			public function definitionColumns(): array
			{
				return [];
			}

			public function read(): iterable
			{
				return [];
			}
		};
	}

	public function timeoutSeconds(): ?int
	{
		return 1;
	}

	public function fileName(): ?string
	{
		return null;
	}
}
