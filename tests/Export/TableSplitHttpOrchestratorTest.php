<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Export;

use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Message\StreamFactory;
use Mheads\Yii\Table\Export\ExportGeneratorInterface;
use Mheads\Yii\Table\Export\RowsReader\RowsReaderInterface;
use Mheads\Yii\Table\Export\TableExportService;
use Mheads\Yii\Table\Export\Writer\WriterInterface;
use Mheads\Yii\Table\Http\Orchestrator\TableConfigHttpOrchestrator;
use Mheads\Yii\Table\Http\Orchestrator\TableExportHttpOrchestrator;
use Mheads\Yii\Table\Http\Orchestrator\TableRowsHttpOrchestrator;
use Mheads\Yii\Table\Http\Request\TableRequestApplierInterface;
use Mheads\Yii\Table\Http\Response\Payload\TablePayloadResponderInterface;
use Mheads\Yii\Table\Http\Response\TableExportHttpResponder;
use Mheads\Yii\Table\Provider\TableConfiguratorInterface;
use Mheads\Yii\Table\Provider\TableProviderInterface;
use Mheads\Yii\Table\Serialization\TableArraySerializer;
use Mheads\Yii\Table\Serialization\TableRowsSerializerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Data\Reader\Iterable\IterableDataReader;

/**
 * @internal
 */
final class TableSplitHttpOrchestratorTest extends TestCase
{
	public function testConfigOrchestratorReturnsConfigPayload(): void
	{
		$table = $this->createSerializableEmptyTable();
		$payloadResponder = new TableSplitPayloadResponderSpy();
		$orchestrator = new TableConfigHttpOrchestrator(
			new TableSplitRequestApplierSpy(),
			new TableArraySerializer(),
			$payloadResponder,
		);

		$response = $orchestrator->respond($table);

		self::assertSame(209, $response->getStatusCode());
		self::assertSame(['config', 'columns', 'filters', 'sorts'], array_keys($payloadResponder->payload ?? []));
	}

	public function testConfigOrchestratorDoesNotApplyMissingRequest(): void
	{
		$table = $this->createSerializableEmptyTable();
		$applier = new TableSplitRequestApplierSpy();
		$orchestrator = new TableConfigHttpOrchestrator(
			$applier,
			new TableArraySerializer(),
			new TableSplitPayloadResponderSpy(),
		);

		$orchestrator->respond($table);

		self::assertSame(0, $applier->calls);
	}

	public function testConfigOrchestratorAppliesProvidedRequest(): void
	{
		$table = $this->createMockForIntersectionOfInterfaces([
			TableProviderInterface::class,
			TableConfiguratorInterface::class,
		]);
		$table->expects(self::never())->method('rows');
		$applier = new TableSplitRequestApplierSpy();
		$orchestrator = new TableConfigHttpOrchestrator(
			$applier,
			new TableSplitConfigSerializerStub(),
			new TableSplitPayloadResponderSpy(),
		);

		$orchestrator->respond($table, new ServerRequest());

		self::assertSame(1, $applier->calls);
	}

	public function testRowsOrchestratorAppliesRequest(): void
	{
		$table = $this->createMockForIntersectionOfInterfaces([
			TableProviderInterface::class,
			TableConfiguratorInterface::class,
		]);
		$table->expects(self::never())->method('rows');
		$applier = new TableSplitRequestApplierSpy();
		$orchestrator = new TableRowsHttpOrchestrator(
			$applier,
			new TableSplitRowsSerializerStub(),
			new TableSplitPayloadResponderSpy(),
		);

		$orchestrator->respond($table, new ServerRequest());

		self::assertSame(1, $applier->calls);
	}

	public function testRowsOrchestratorReturnsRowsPayload(): void
	{
		$table = $this->createMockForIntersectionOfInterfaces([
			TableProviderInterface::class,
			TableConfiguratorInterface::class,
		]);
		$table->expects(self::never())->method('rows');
		$payloadResponder = new TableSplitPayloadResponderSpy();
		$serializer = new TableSplitRowsSerializerStub(['pagination' => null, 'rows' => [['id' => 1]]]);

		$orchestrator = new TableRowsHttpOrchestrator(
			new TableSplitRequestApplierSpy(),
			$serializer,
			$payloadResponder,
		);
		$response = $orchestrator->respond($table, new ServerRequest());

		self::assertSame(209, $response->getStatusCode());
		self::assertSame(['pagination' => null, 'rows' => [['id' => 1]]], $payloadResponder->payload);
	}

	public function testExportOrchestratorRequiresExportCode(): void
	{
		$table = $this->createMockForIntersectionOfInterfaces([
			TableProviderInterface::class,
			TableConfiguratorInterface::class,
		]);
		$table->expects(self::once())->method('exportParam')->willReturn('export');
		$payloadResponder = new TableSplitPayloadResponderSpy();
		$applier = new TableSplitRequestApplierSpy();

		$orchestrator = new TableExportHttpOrchestrator(
			$applier,
			$this->createExportResponder($payloadResponder),
			$payloadResponder,
		);
		$response = $orchestrator->respond($table, new ServerRequest());

		self::assertSame(400, $payloadResponder->statusCode);
		self::assertSame(209, $response->getStatusCode());
		self::assertSame(
			['errorCode' => 'missing_export', 'errorMessage' => 'Export code is required.'],
			$payloadResponder->payload,
		);
	}

	public function testExportOrchestratorAppliesRequest(): void
	{
		$table = $this->createMockForIntersectionOfInterfaces([
			TableProviderInterface::class,
			TableConfiguratorInterface::class,
		]);
		$table->expects(self::once())->method('exportParam')->willReturn('export');
		$table->expects(self::once())->method('exportGenerators')->willReturn([new TableSplitExportGeneratorStub()]);
		$payloadResponder = new TableSplitPayloadResponderSpy();
		$applier = new TableSplitRequestApplierSpy();
		$orchestrator = new TableExportHttpOrchestrator(
			$applier,
			$this->createExportResponder($payloadResponder),
			$payloadResponder,
		);

		$response = $orchestrator->respond(
			$table,
			(new ServerRequest())->withQueryParams(['export' => 'csv']),
		);

		self::assertSame(1, $applier->calls);
		self::assertSame(200, $response->getStatusCode());
	}

	public function testExportOrchestratorReturnsStreamResponse(): void
	{
		$table = $this->createMockForIntersectionOfInterfaces([
			TableProviderInterface::class,
			TableConfiguratorInterface::class,
		]);
		$table->expects(self::once())->method('exportParam')->willReturn('export');
		$table->expects(self::once())->method('exportGenerators')->willReturn([new TableSplitExportGeneratorStub()]);
		$payloadResponder = new TableSplitPayloadResponderSpy();
		$orchestrator = new TableExportHttpOrchestrator(
			new TableSplitRequestApplierSpy(),
			$this->createExportResponder($payloadResponder),
			$payloadResponder,
		);

		$response = $orchestrator->respond(
			$table,
			(new ServerRequest())->withQueryParams(['export' => 'csv']),
		);

		self::assertSame(200, $response->getStatusCode());
		self::assertSame('text/csv', $response->getHeaderLine('Content-Type'));
		self::assertSame('attachment; filename="products.csv"', $response->getHeaderLine('Content-Disposition'));
		self::assertSame("id\n1\n", (string)$response->getBody());
		self::assertNull($payloadResponder->payload);
	}

	/**
	 */
	private function createSerializableEmptyTable(): TableProviderInterface&TableConfiguratorInterface
	{
		$table = $this->createMockForIntersectionOfInterfaces([
			TableProviderInterface::class,
			TableConfiguratorInterface::class,
		]);
		$table->method('id')->willReturn('products');
		$table->method('dataReader')->willReturn(new IterableDataReader([]));
		$table->method('filterParam')->willReturn('filter');
		$table->method('sortParam')->willReturn('sort');
		$table->method('pageParam')->willReturn('page');
		$table->method('prevPageParam')->willReturn('prev-page');
		$table->method('pageSizeParam')->willReturn('per-page');
		$table->method('pageSizeConstraint')->willReturn(false);
		$table->method('columns')->willReturn([]);
		$table->method('filters')->willReturn([]);
		$table->method('sortOptions')->willReturn([]);
		$table->method('effectiveSortOrder')->willReturn([]);
		$table->method('exportGenerators')->willReturn([]);
		$table->method('exportParam')->willReturn('export');
		$table->expects(self::never())->method('rows');

		return $table;
	}

	private function createExportResponder(TablePayloadResponderInterface $payloadResponder): TableExportHttpResponder
	{
		return new TableExportHttpResponder(
			new ResponseFactory(),
			new StreamFactory(),
			new TableExportService(),
			$payloadResponder,
		);
	}
}

/**
 * @internal
 */
final class TableSplitPayloadResponderSpy implements TablePayloadResponderInterface
{
	/** @var array<array-key, mixed>|null */
	public ?array $payload = null;
	public int $statusCode = 200;

	public function respond(array $payload, int $statusCode = 200): ResponseInterface
	{
		$this->payload = $payload;
		$this->statusCode = $statusCode;

		return (new ResponseFactory())->createResponse(209);
	}
}

/**
 * @internal
 */
final class TableSplitRequestApplierSpy implements TableRequestApplierInterface
{
	public int $calls = 0;

	public function apply(
		TableProviderInterface&TableConfiguratorInterface $table,
		ServerRequestInterface $request,
	): void {
		$this->calls++;
	}
}

/**
 * @internal
 */
final class TableSplitConfigSerializerStub implements \Mheads\Yii\Table\Serialization\TableConfigSerializerInterface
{
	public function serializeConfig(TableProviderInterface $table): array
	{
		return ['config' => ['ok' => true]];
	}
}

/**
 * @internal
 */
final class TableSplitRowsSerializerStub implements TableRowsSerializerInterface
{
	/**
	 * @param array<array-key, mixed> $payload
	 */
	public function __construct(private readonly array $payload = ['pagination' => null, 'rows' => []]) {}

	public function serializeRows(TableProviderInterface $table): array
	{
		return $this->payload;
	}
}

/**
 * @internal
 */
final class TableSplitExportGeneratorStub implements ExportGeneratorInterface
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

			public function write(RowsReaderInterface $rowsReader, mixed $target): void
			{
				fwrite($target, "id\n1\n");
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
		return null;
	}

	public function fileName(): ?string
	{
		return 'products';
	}
}
