<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Export;

use BadMethodCallException;
use Mheads\Yii\Table\Column\Column;
use Mheads\Yii\Table\Export\BatchStrategy\OffsetLimitBatchReadStrategy;
use Mheads\Yii\Table\Export\Column\ExportColumn;
use Mheads\Yii\Table\Export\Column\ExportColumnMode;
use Mheads\Yii\Table\Export\RowsReader\PaginatorAllItemsDataReader;
use Mheads\Yii\Table\Export\RowsReader\TableExportRowsReader;
use Mheads\Yii\Table\Export\TableBoundExportGenerator;
use Mheads\Yii\Table\Export\TableBoundExportGeneratorFactory;
use Mheads\Yii\Table\Export\TableBoundExportOptions;
use Mheads\Yii\Table\Export\Writer\CsvWriter;
use Mheads\Yii\Table\Export\Writer\WriterInterface;
use Mheads\Yii\Table\Export\Writer\XlsxWriter;
use Mheads\Yii\Table\Filter\FilterInput;
use Mheads\Yii\Table\Provider\TableProviderInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Reader\Iterable\IterableDataReader;
use Yiisoft\Data\Reader\ReadableDataInterface;
use Yiisoft\Data\Reader\Sort;

/**
 * @internal
 */
final class TableBoundExportGeneratorTest extends TestCase
{
	public function testBuildsRowsReaderFromTableWithMergedColumnsAndPaginatorSource(): void
	{
		// End-to-end generator contract: merge columns, ignore hidden, read all paginator items.
		$baseReader = new IterableDataReader([
			['id' => 1, 'name' => 'Phone', 'sku' => 'P-1'],
			['id' => 2, 'name' => 'Tablet', 'sku' => 'T-2'],
		]);
		$paginator = (new OffsetPaginator($baseReader))->withPageSize(1);

		$provider = new TableBoundExportProviderStub(
			columns: [
				new Column('id', 'ID', static fn(array $row): int => (int)$row['id']),
				new Column('name', 'Name', static fn(array $row): string => (string)$row['name']),
				new Column('hidden', 'Hidden', static fn(array $row): string => 'x', isHidden: true),
			],
			dataReader: $paginator,
		);

		$generator = new TableBoundExportGenerator(
			table: $provider,
			writer: new TableBoundExportWriterStub('csv'),
			options: new TableBoundExportOptions(
				columnsMode: ExportColumnMode::MERGE,
				customColumns: [
					new ExportColumn('name', 'Name upper', static fn(array $row): string => strtoupper((string)$row['name'])),
					new ExportColumn('sku', 'SKU', static fn(array $row): string => (string)$row['sku']),
				],
				timeoutSeconds: 77,
				fileName: 'products-export',
				batchStrategy: new OffsetLimitBatchReadStrategy(batchSize: 2),
			),
		);

		$rowsReader = $generator->rowsReader();

		self::assertInstanceOf(TableExportRowsReader::class, $rowsReader);
		self::assertSame([false], $provider->dataReaderAllowAutoWrapCalls);
		self::assertSame('csv', $generator->code());
		self::assertSame(77, $generator->timeoutSeconds());
		self::assertSame('products-export', $generator->fileName());

		self::assertSame(
			['id', 'name', 'sku'],
			array_map(static fn(ExportColumn $column): string => $column->key(), $rowsReader->definitionColumns()),
		);
		self::assertSame(
			[
				['id' => 1, 'name' => 'PHONE', 'sku' => 'P-1'],
				['id' => 2, 'name' => 'TABLET', 'sku' => 'T-2'],
			],
			iterator_to_array($rowsReader->read(), false),
		);
	}

	public function testWrapsPaginatorIntoAllItemsReader(): void
	{
		// Export must ignore paginator page window, so paginator source is wrapped.
		$provider = new TableBoundExportProviderStub(
			columns: [
				new Column('id', 'ID', static fn(array $row): int => (int)$row['id']),
			],
			dataReader: (new OffsetPaginator(new IterableDataReader([['id' => 1], ['id' => 2]])))->withPageSize(1),
		);
		$generator = new TableBoundExportGenerator($provider, new TableBoundExportWriterStub('csv'));
		$rowsReader = $generator->rowsReader();

		self::assertInstanceOf(TableExportRowsReader::class, $rowsReader);
		// Verify internal reader type to ensure all-items wrapper is actually applied.
		$reflection = new ReflectionClass($rowsReader);
		$property = $reflection->getProperty('reader');
		/**  */
		$innerReader = $property->getValue($rowsReader);
		self::assertInstanceOf(PaginatorAllItemsDataReader::class, $innerReader);
	}

	public function testFactoryCreatesGeneratorsForCsvAndXlsx(): void
	{
		// Factory must preserve explicit writer instance and runtime metadata.
		$provider = new TableBoundExportProviderStub(
			columns: [new Column('id', 'ID', static fn(array $row): int => (int)$row['id'])],
			dataReader: new IterableDataReader([['id' => 1]]),
		);
		$factory = new TableBoundExportGeneratorFactory();

		$csvWriter = new CsvWriter();
		$csv = $factory->csv(
			table: $provider,
			options: new TableBoundExportOptions(
				columnsMode: ExportColumnMode::TABLE_ONLY,
				timeoutSeconds: 11,
				fileName: 'csv-name',
				batchStrategy: new OffsetLimitBatchReadStrategy(batchSize: 10),
			),
			writer: $csvWriter,
		);
		$xlsxWriter = new XlsxWriter(sheetName: 'Export Sheet');
		$xlsx = $factory->xlsx(
			table: $provider,
			options: new TableBoundExportOptions(
				columnsMode: ExportColumnMode::TABLE_ONLY,
				timeoutSeconds: 22,
				fileName: 'xlsx-name',
				batchStrategy: new OffsetLimitBatchReadStrategy(batchSize: 5),
			),
			sheetName: 'ignored when writer passed',
			writer: $xlsxWriter,
		);

		self::assertSame($csvWriter, $csv->writer());
		self::assertSame('csv', $csv->code());
		self::assertSame(11, $csv->timeoutSeconds());
		self::assertSame('csv-name', $csv->fileName());

		self::assertSame($xlsxWriter, $xlsx->writer());
		self::assertSame('xlsx', $xlsx->code());
		self::assertSame(22, $xlsx->timeoutSeconds());
		self::assertSame('xlsx-name', $xlsx->fileName());
	}
}

/**
 * @internal
 */
final class TableBoundExportProviderStub implements TableProviderInterface
{
	/** @var array<int, bool> */
	public array $dataReaderAllowAutoWrapCalls = [];

	/**
	 * @param array<int, Column> $columns
	 */
	public function __construct(
		private readonly array $columns,
		private readonly ReadableDataInterface $dataReader,
	) {}

	public function id(): string
	{
		return 'table';
	}

	public function reader(): ReadableDataInterface
	{
		return $this->dataReader;
	}

	public function columns(): array
	{
		return $this->columns;
	}

	public function exportGenerators(): array
	{
		return [];
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
		throw new BadMethodCallException();
	}

	public function dataReader(bool $allowAutoWrap = true): ReadableDataInterface
	{
		$this->dataReaderAllowAutoWrapCalls[] = $allowAutoWrap;
		return $this->dataReader;
	}
}

/**
 * @internal
 */
final class TableBoundExportWriterStub implements WriterInterface
{
	public function __construct(private readonly string $code) {}

	public function code(): string
	{
		return $this->code;
	}

	public function mimeType(): ?string
	{
		return null;
	}

	public function extension(): string
	{
		return 'txt';
	}

	public function write(\Mheads\Yii\Table\Export\RowsReader\RowsReaderInterface $rowsReader, mixed $target): void {}
}
