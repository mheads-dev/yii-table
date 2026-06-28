<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Driver\Common;

use Mheads\Yii\Table\Column\Column;
use Mheads\Yii\Table\Filter\FilterInput;
use Mheads\Yii\Table\Filter\SearchFilter;
use Mheads\Yii\Table\Provider\TableProvider;
use Mheads\Yii\Table\Serialization\TableArraySerializer;
use Mheads\Yii\Table\Sort\SortDefinition;
use Mheads\Yii\Table\Sort\SortOption;
use Mheads\Yii\Table\Tests\Stubs\Db\DbQueryDataReader;
use Mheads\Yii\Table\Tests\TestCase;
use Traversable;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Reader\DataReaderInterface;
use Yiisoft\Data\Reader\FilterInterface;
use Yiisoft\Data\Reader\Iterable\IterableDataReader;
use Yiisoft\Data\Reader\Sort;

abstract class TableProviderSerializationTestCase extends TestCase
{
	/**
	 * Проверяет полный payload сериализации таблицы:
	 * config, pagination, columns, filters и rows при offset-pagination.
	 */
	public function testSerializesExpectedTableSchemaWithOffsetPagination(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);
		$paginator = (new OffsetPaginator($reader))
			->withPageSize(3)
			->withCurrentPage(2);

		$table = new TableProvider('products', $paginator);
		$table->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id'], isId: true, sort: SortDefinition::byField('id')));
		$table->addColumn(
			new Column(
				'name',
				'Name',
				static fn(array $row): string => (string)$row['name'],
				sort: SortDefinition::byField('name'),
				filter: new SearchFilter(key: 'name', title: 'Name', field: 'name', searchMode: SearchFilter::SEARCH_MODE_EQUAL),
			),
		);
		$table->addColumn(new Column('category', 'Category', static fn(array $row): ?string => $row['category'] !== null ? (string)$row['category'] : null));
		$table->setSort(Sort::any()->withOrderString('id'));

		$payload = (new TableArraySerializer())->serialize($table);

		self::assertSame(
			[
				'tableId'            => 'products',
				'filterParam'        => 'filter',
				'sortParam'          => 'sort',
				'pageParam'          => 'page',
				'prevPageParam'      => null,
				'pageSizeParam'      => 'per-page',
				'pageSizeConstraint' => false,
				'columnIdKey'        => 'id',
				'exportParam'        => null,
				'exportCodes'        => null,
			],
			$payload['config'],
		);
		self::assertSame(
			[
				'type'          => 'offset',
				'currentPage'   => 2,
				'perPage'       => 3,
				'pageCount'     => 3,
				'totalCount'    => 7,
				'nextPageToken' => '3',
				'prevPageToken' => '1',
			],
			$payload['pagination'],
		);
		self::assertSame(
			[
				[
					'key'             => 'id',
					'title'           => 'ID',
					'sort'            => ['isSorted' => true, 'isDefault' => false, 'sortedDirection' => 'ascend', 'values' => ['ascend' => 'id', 'descend' => '-id']],
					'isHidden'        => false,
					'filterKey'       => null,
					'extraFilterKeys' => [],
				],
				[
					'key'             => 'name',
					'title'           => 'Name',
					'sort'            => ['isSorted' => false, 'isDefault' => false, 'sortedDirection' => null, 'values' => ['ascend' => 'name', 'descend' => '-name']],
					'isHidden'        => false,
					'filterKey'       => 'name',
					'extraFilterKeys' => [],
				],
				[
					'key'             => 'category',
					'title'           => 'Category',
					'sort'            => null,
					'isHidden'        => false,
					'filterKey'       => null,
					'extraFilterKeys' => [],
				],
			],
			$payload['columns'],
		);
		self::assertSame(
			[
				[
					'key'        => 'name',
					'title'      => 'Name',
					'caption'    => null,
					'type'       => 'search',
					'values'     => null,
					'columnKey'  => 'name',
					'isMultiple' => false,
					'searchMode' => 'equal',
				],
			],
			$payload['filters'],
		);
		self::assertSame([], $payload['sorts']);
		self::assertSame(
			[
				['id' => 4, 'name' => 'Monitor', 'category' => 'computer'],
				['id' => 5, 'name' => 'Keyboard', 'category' => 'accessory'],
				['id' => 6, 'name' => 'Mouse', 'category' => 'accessory'],
			],
			$payload['rows'],
		);
	}

	public function testSerializesConfigAndRowsSeparately(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);
		$paginator = (new OffsetPaginator($reader))
			->withPageSize(3)
			->withCurrentPage(1);

		$table = new TableProvider('products', $paginator);
		$table->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id'], isId: true, sort: SortDefinition::byField('id')));
		$table->addColumn(
			new Column(
				'name',
				'Name',
				static fn(array $row): string => (string)$row['name'],
				filter: new SearchFilter(key: 'name', title: 'Name', field: 'name', searchMode: SearchFilter::SEARCH_MODE_EQUAL),
			),
		);
		$table->setSort(Sort::any()->withOrderString('id'));
		$table->setFilterInput(new FilterInput(['name' => 'Mouse']));

		$serializer = new TableArraySerializer();
		$configPayload = $serializer->serializeConfig($table);
		$rowsPayload = $serializer->serializeRows($table);
		$payload = $serializer->serialize($table);

		self::assertSame(['config', 'columns', 'filters', 'sorts'], array_keys($configPayload));
		self::assertSame(['pagination', 'rows'], array_keys($rowsPayload));
		self::assertSame(
			[
				'config'     => $configPayload['config'],
				'pagination' => $rowsPayload['pagination'],
				'columns'    => $configPayload['columns'],
				'filters'    => $configPayload['filters'],
				'sorts'      => $configPayload['sorts'],
				'rows'       => $rowsPayload['rows'],
			],
			$payload,
		);
		self::assertSame(['Mouse'], $configPayload['filters'][0]['values']);
		self::assertSame([['id' => 6, 'name' => 'Mouse']], $rowsPayload['rows']);
	}

	public function testSerializeConfigDoesNotCountOffsetPaginationReader(): void
	{
		$countLog = new CountingDataReaderLog();
		$reader = new CountingDataReader(
			[
				['id' => 1],
				['id' => 2],
				['id' => 3],
				['id' => 4],
			],
			$countLog,
		);
		$table = new TableProvider('products', $reader);
		$table->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id']));

		$configPayload = (new TableArraySerializer())->serializeConfig($table);

		self::assertSame(0, $countLog->countCalls);
		self::assertSame('page', $configPayload['config']['pageParam']);
		self::assertSame('per-page', $configPayload['config']['pageSizeParam']);
	}

	public function testSerializeRowsReusesPreparedOffsetPaginationReader(): void
	{
		$countLog = new CountingDataReaderLog();
		$reader = new CountingDataReader(
			[
				['id' => 1],
				['id' => 2],
				['id' => 3],
				['id' => 4],
			],
			$countLog,
		);
		$table = new TableProvider('products', $reader);
		$table->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id']));
		$table->setPageSize(2);
		$table->setPage(1);

		$payload = (new TableArraySerializer())->serializeRows($table);

		self::assertSame(5, $countLog->countCalls);
		self::assertSame(4, $payload['pagination']['totalCount']);
		self::assertSame([['id' => 1], ['id' => 2]], $payload['rows']);
	}

	public function testPreparedReaderCacheResetsAfterPageChange(): void
	{
		$countLog = new CountingDataReaderLog();
		$reader = new CountingDataReader(
			[
				['id' => 1],
				['id' => 2],
				['id' => 3],
				['id' => 4],
			],
			$countLog,
		);
		$table = new TableProvider('products', $reader);
		$table->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id']));
		$table->setPageSize(2);
		$table->setPage(1);

		$table->dataReader();
		$table->dataReader();
		self::assertSame(1, $countLog->countCalls);

		$table->setPage(2);
		$table->dataReader();

		self::assertSame(2, $countLog->countCalls);
	}

	public function testDataReaderWithoutAutoWrapDoesNotUsePreparedReaderCache(): void
	{
		$countLog = new CountingDataReaderLog();
		$reader = new CountingDataReader(
			[
				['id' => 1],
				['id' => 2],
				['id' => 3],
				['id' => 4],
			],
			$countLog,
		);
		$table = new TableProvider('products', $reader);
		$table->setPage(1);

		$preparedReader = $table->dataReader();
		$rawReader = $table->dataReader(false);

		self::assertInstanceOf(OffsetPaginator::class, $preparedReader);
		self::assertInstanceOf(CountingDataReader::class, $rawReader);
		self::assertSame(1, $countLog->countCalls);
	}

	/**
	 * Проверяет применение SearchFilter к данным и totalCount,
	 * а также нормализацию values в блоке filters.
	 */
	public function testAppliesFilterInputToRowsAndTotalCount(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);
		$paginator = (new OffsetPaginator($reader))
			->withPageSize(3)
			->withCurrentPage(1);

		$table = new TableProvider('products', $paginator);
		$table->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id'], isId: true, sort: SortDefinition::byField('id')));
		$table->addColumn(
			new Column(
				'name',
				'Name',
				static fn(array $row): string => (string)$row['name'],
				filter: new SearchFilter(key: 'name', title: 'Name', field: 'name', searchMode: SearchFilter::SEARCH_MODE_EQUAL),
			),
		);
		$table->addColumn(new Column('category', 'Category', static fn(array $row): ?string => $row['category'] !== null ? (string)$row['category'] : null));
		$table->setFilterInput(new FilterInput(['name' => 'Mouse']));

		$payload = (new TableArraySerializer())->serialize($table);

		self::assertSame(
			[
				'type'          => 'offset',
				'currentPage'   => 1,
				'perPage'       => 3,
				'pageCount'     => 1,
				'totalCount'    => 1,
				'nextPageToken' => null,
				'prevPageToken' => null,
			],
			$payload['pagination'],
		);
		self::assertSame(
			[
				[
					'key'        => 'name',
					'title'      => 'Name',
					'caption'    => null,
					'type'       => 'search',
					'values'     => ['Mouse'],
					'columnKey'  => 'name',
					'isMultiple' => false,
					'searchMode' => 'equal',
				],
			],
			$payload['filters'],
		);
		self::assertSame([], $payload['sorts']);
		self::assertSame([['id' => 6, 'name' => 'Mouse', 'category' => 'accessory']], $payload['rows']);
	}

	/**
	 * Проверяет сериализацию селект-сортировок вне колонок и отметку выбранного значения.
	 */
	public function testSerializesDetachedSortOptions(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);

		$table = new TableProvider('products', $reader);
		$table->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id'], isId: true));
		$table->addColumn(new Column('name', 'Name', static fn(array $row): string => (string)$row['name']));
		$table->addColumn(new Column('category', 'Category', static fn(array $row): ?string => $row['category'] !== null ? (string)$row['category'] : null));
		$table->addSortOption(new SortOption('name_asc', 'By name (A-Z)', SortDefinition::fixedOrder(['name' => SORT_ASC]), isDefault: true));
		$table->addSortOption(new SortOption('name_desc', 'By name (Z-A)', SortDefinition::fixedOrder(['name' => SORT_DESC])));
		$table->addSortOption(new SortOption(
			'name_disabled',
			'By name (disabled)',
			SortDefinition::fixedOrder(['name' => SORT_ASC]),
			isDisabled: true,
		));
		$table->setSort(Sort::any()->withOrderString('name_desc'));

		$payload = (new TableArraySerializer())->serialize($table);

		self::assertSame(
			[
				['title' => 'By name (A-Z)', 'value' => 'name_asc', 'isDefault' => true, 'isDisabled' => false, 'isSelected' => false],
				['title' => 'By name (Z-A)', 'value' => 'name_desc', 'isDefault' => false, 'isDisabled' => false, 'isSelected' => true],
				['title' => 'By name (disabled)', 'value' => 'name_disabled', 'isDefault' => false, 'isDisabled' => true, 'isSelected' => false],
			],
			$payload['sorts'],
		);
	}
}

/**
 * @internal
 */
final class CountingDataReaderLog
{
	public int $countCalls = 0;
}

/**
 * @internal
 *
 * @implements DataReaderInterface<int, array<string, mixed>>
 */
final class CountingDataReader implements DataReaderInterface
{
	/** @var DataReaderInterface<int, array<string, mixed>> */
	private DataReaderInterface $reader;

	/**
	 * @param DataReaderInterface<int, array<string, mixed>>|iterable<int, array<string, mixed>> $data
	 */
	public function __construct(iterable $data, private readonly CountingDataReaderLog $log)
	{
		$this->reader = $data instanceof DataReaderInterface ? $data : new IterableDataReader($data);
	}

	public function withFilter(FilterInterface $filter): static
	{
		return new self($this->reader->withFilter($filter), $this->log);
	}

	public function getFilter(): FilterInterface
	{
		return $this->reader->getFilter();
	}

	public function withLimit(?int $limit): static
	{
		return new self($this->reader->withLimit($limit), $this->log);
	}

	public function getLimit(): ?int
	{
		return $this->reader->getLimit();
	}

	public function withOffset(int $offset): static
	{
		return new self($this->reader->withOffset($offset), $this->log);
	}

	public function getOffset(): int
	{
		return $this->reader->getOffset();
	}

	public function withSort(?Sort $sort): static
	{
		return new self($this->reader->withSort($sort), $this->log);
	}

	public function getSort(): ?Sort
	{
		return $this->reader->getSort();
	}

	public function count(): int
	{
		$this->log->countCalls++;
		return $this->reader->count();
	}

	public function read(): iterable
	{
		return $this->reader->read();
	}

	public function readOne(): array|object|null
	{
		return $this->reader->readOne();
	}

	public function getIterator(): Traversable
	{
		yield from $this->read();
	}
}
