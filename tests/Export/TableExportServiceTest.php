<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Export;

use BadMethodCallException;
use Mheads\Yii\Table\Export\Column\ExportColumn;
use Mheads\Yii\Table\Export\Exception\ExportCanceledException;
use Mheads\Yii\Table\Export\Exception\ExportTimeoutException;
use Mheads\Yii\Table\Export\Exception\UnsupportedExportFormatException;
use Mheads\Yii\Table\Export\ExportGeneratorInterface;
use Mheads\Yii\Table\Export\RowsReader\RowsReaderInterface;
use Mheads\Yii\Table\Export\Runtime\ExportCancellationCheckerInterface;
use Mheads\Yii\Table\Export\Runtime\ExportRuntimeOptions;
use Mheads\Yii\Table\Export\Runtime\RowsReadObserverInterface;
use Mheads\Yii\Table\Export\TableExportService;
use Mheads\Yii\Table\Export\Writer\WriterInterface;
use Mheads\Yii\Table\Filter\FilterInput;
use Mheads\Yii\Table\Provider\TableProviderInterface;
use PHPUnit\Framework\TestCase;
use Yiisoft\Data\Reader\ReadableDataInterface;
use Yiisoft\Data\Reader\Sort;

/**
 * @internal
 */
final class TableExportServiceTest extends TestCase
{
	public function testRunsGeneratorByCode(): void
	{
		$csv = new TableExportServiceGeneratorStub('csv');
		$xlsx = new TableExportServiceGeneratorStub('xlsx');
		$provider = new TableExportServiceProviderStub([$csv, $xlsx]);

		$generator = (new TableExportService())->run($provider, 'xlsx', 'php://temp');

		self::assertSame($xlsx, $generator);
		self::assertSame(0, $csv->writer->writeCalls);
		self::assertSame(1, $xlsx->writer->writeCalls);
	}

	public function testThrowsUnsupportedFormatForUnknownCode(): void
	{
		$provider = new TableExportServiceProviderStub([new TableExportServiceGeneratorStub('csv')]);

		$this->expectException(UnsupportedExportFormatException::class);
		(new TableExportService())->run($provider, 'xml', 'php://temp');
	}

	public function testBubblesTimeoutExceptionFromWriter(): void
	{
		$provider = new TableExportServiceProviderStub([new TableExportServiceGeneratorStub('csv', throwsTimeout: true)]);

		$this->expectException(ExportTimeoutException::class);
		(new TableExportService())->run($provider, 'csv', 'php://temp');
	}

	public function testThrowsCanceledExceptionWhenCancellationRequested(): void
	{
		$provider = new TableExportServiceProviderStub([new TableExportServiceGeneratorStub('csv')]);
		$service = new TableExportService();
		$options = new ExportRuntimeOptions(cancellationChecker: new TableExportServiceCancellationCheckerStub());

		$this->expectException(ExportCanceledException::class);
		$service->run($provider, 'csv', 'php://temp', $options);
	}

	public function testNotifiesObserverDuringExport(): void
	{
		$provider = new TableExportServiceProviderStub([new TableExportServiceGeneratorStub('csv')]);
		$observer = new TableExportServiceRowsObserverStub();
		$options = new ExportRuntimeOptions(
			rowsReadObserver: $observer,
			observerNotifyEachRows: 2,
		);

		(new TableExportService())->run($provider, 'csv', 'php://temp', $options);

		self::assertSame(1, $observer->startCalls);
		self::assertSame(1, $observer->finishCalls);
		self::assertSame([[2, 2], [1, 3]], $observer->chunks);
	}

	public function testThrowsTimeoutImmediatelyWhenTimeoutOverrideIsZero(): void
	{
		$provider = new TableExportServiceProviderStub([new TableExportServiceGeneratorStub('csv')]);
		$options = new ExportRuntimeOptions(timeoutSecondsOverride: 0);

		$this->expectException(ExportTimeoutException::class);
		(new TableExportService())->run($provider, 'csv', 'php://temp', $options);
	}
}

final class TableExportServiceProviderStub implements TableProviderInterface
{
	/**
	 * @param ExportGeneratorInterface[] $generators
	 */
	public function __construct(private readonly array $generators) {}

	public function id(): string
	{
		return 'table';
	}

	public function reader(): ReadableDataInterface
	{
		throw new BadMethodCallException();
	}

	public function columns(): array
	{
		return [];
	}

	public function exportGenerators(): array
	{
		return $this->generators;
	}

	public function exportParam(): string
	{
		return 'export';
	}

	public function filterParam(): string
	{
		return 'filter';
	}

	public function sortParam(): string
	{
		return 'sort';
	}

	public function pageParam(): string
	{
		return 'page';
	}

	public function pageSizeParam(): string
	{
		return 'per-page';
	}

	public function pageSizeConstraint(): bool|int|array
	{
		return false;
	}

	public function prevPageParam(): string
	{
		return 'prev-page';
	}

	public function filterInput(): FilterInput
	{
		return new FilterInput();
	}

	public function sort(): ?Sort
	{
		return null;
	}

	public function sortOptions(): array
	{
		return [];
	}

	public function effectiveSortOrder(): array
	{
		return [];
	}

	public function filters(): array
	{
		return [];
	}

	public function rows(): array
	{
		return [];
	}

	public function dataReader(bool $allowAutoWrap = true): ReadableDataInterface
	{
		throw new BadMethodCallException();
	}
}

final class TableExportServiceGeneratorStub implements ExportGeneratorInterface
{
	public readonly TableExportServiceWriterStub $writer;

	public function __construct(private readonly string $code, bool $throwsTimeout = false)
	{
		$this->writer = new TableExportServiceWriterStub($throwsTimeout);
	}

	public function code(): string
	{
		return $this->code;
	}

	public function writer(): WriterInterface
	{
		return $this->writer;
	}

	public function rowsReader(): RowsReaderInterface
	{
		return new TableExportServiceRowsReaderStub();
	}

	public function timeoutSeconds(): ?int
	{
		return null;
	}

	public function fileName(): ?string
	{
		return null;
	}
}

final class TableExportServiceRowsReaderStub implements RowsReaderInterface
{
	public function definitionColumns(): array
	{
		return [
			new ExportColumn('id', 'ID', static fn(mixed $entity): mixed => $entity),
		];
	}

	public function read(): iterable
	{
		yield ['id' => 1];
		yield ['id' => 2];
		yield ['id' => 3];
	}
}

final class TableExportServiceWriterStub implements WriterInterface
{
	public int $writeCalls = 0;

	public function __construct(
		private readonly bool $throwsTimeout = false,
	) {}

	public function code(): string
	{
		return 'stub';
	}

	public function mimeType(): ?string
	{
		return null;
	}

	public function extension(): string
	{
		return 'txt';
	}

	public function write(
		RowsReaderInterface $rowsReader,
		mixed $target,
	): void {
		$this->writeCalls++;
		if ($this->throwsTimeout)
		{
			throw new ExportTimeoutException('Export timeout exceeded.');
		}
		foreach ($rowsReader->read() as $row)
		{
			/** @psalm-suppress UnusedVariable */
			$_ = $row;
		}
	}
}

final class TableExportServiceCancellationCheckerStub implements ExportCancellationCheckerInterface
{
	public function throwIfCanceled(): void
	{
		throw new ExportCanceledException('Export canceled.');
	}
}

final class TableExportServiceRowsObserverStub implements RowsReadObserverInterface
{
	public int $startCalls = 0;
	public int $finishCalls = 0;
	/** @var array<int, array{int, int}> */
	public array $chunks = [];

	public function onStart(): void
	{
		$this->startCalls++;
	}

	public function onRowsRead(int $rowsDelta, int $rowsTotal): void
	{
		$this->chunks[] = [$rowsDelta, $rowsTotal];
	}

	public function onFinish(int $rowsTotal): void
	{
		$this->finishCalls++;
	}
}
