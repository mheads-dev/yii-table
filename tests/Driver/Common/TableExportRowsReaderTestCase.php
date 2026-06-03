<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Driver\Common;

use Mheads\Yii\Table\Column\Column;
use Mheads\Yii\Table\Export\BatchStrategy\OffsetLimitBatchReadStrategy;
use Mheads\Yii\Table\Export\BatchStrategy\QueryDataReaderBatchReadStrategy;
use Mheads\Yii\Table\Export\Column\ExportColumnMode;
use Mheads\Yii\Table\Export\Column\ExportColumnsResolver;
use Mheads\Yii\Table\Export\RowsReader\PaginatorAllItemsDataReader;
use Mheads\Yii\Table\Export\RowsReader\TableExportRowsReader;
use Mheads\Yii\Table\Filter\FilterInput;
use Mheads\Yii\Table\Filter\SearchFilter;
use Mheads\Yii\Table\Provider\TableProvider;
use Mheads\Yii\Table\Sort\SortDefinition;
use Mheads\Yii\Table\Tests\Stubs\Db\DbQueryDataReader;
use Mheads\Yii\Table\Tests\TestCase;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Reader\Sort;

abstract class TableExportRowsReaderTestCase extends TestCase
{
	/**
	 * Проверяет базовый export-контракт:
	 * фильтр/сорт применяются, пагинация таблицы игнорируется.
	 */
	public function testExportRowsReaderAppliesFilterAndSortAndIgnoresPagination(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);

		$paginator = (new OffsetPaginator($reader))->withPageSize(1);

		$table = new TableProvider('products', $paginator);
		$table
			->addColumn(
				new Column(
					'id',
					'ID',
					static fn(array $row): int => (int)$row['id'],
					sort: SortDefinition::byField('id'),
				),
			)
			->addColumn(
				new Column(
					'name',
					'Name',
					static fn(array $row): string => (string)$row['name'],
					sort: SortDefinition::byField('name'),
				),
			)
			->addColumn(
				new Column(
					'category',
					'Category',
					static fn(array $row): string => (string)$row['category'],
					isHidden: true,
					filter: new SearchFilter(
						key: 'category',
						title: 'Category',
						field: 'category',
						searchMode: SearchFilter::SEARCH_MODE_EQUAL,
					),
				),
			);

		$table->setFilterInput(new FilterInput(['category' => 'accessory']));
		$table->setSort(Sort::any()->withOrderString('name'));

		$columns = (new ExportColumnsResolver())->resolve(
			$table->columns(),
			mode: ExportColumnMode::TABLE_ONLY,
		);

		$rowsReader = new TableExportRowsReader(
			new PaginatorAllItemsDataReader($table->dataReader()),
			$columns,
			batchStrategy: new OffsetLimitBatchReadStrategy(batchSize: 2),
		);

		self::assertCount(1, $table->rows());

		$rows = iterator_to_array($rowsReader->read(), false);

		self::assertSame(
			[
				['id' => 7, 'name' => 'Headphones'],
				['id' => 5, 'name' => 'Keyboard'],
				['id' => 6, 'name' => 'Mouse'],
			],
			$rows,
		);
	}

	/**
	 * Проверяет интеграцию query batching стратегии с DB reader.
	 */
	public function testExportRowsReaderWithQueryBatchStrategyReadsRows(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query, sort: Sort::only(['id'])->withOrderString('id'));

		$columns = [
			new Column(
				'id',
				'ID',
				static fn(array $row): int => (int)$row['id'],
			),
			new Column(
				'name',
				'Name',
				static fn(array $row): string => (string)$row['name'],
			),
		];

		$exportColumns = (new ExportColumnsResolver())->resolve(
			$columns,
			mode: ExportColumnMode::TABLE_ONLY,
		);

		$rowsReader = new TableExportRowsReader(
			$reader,
			$exportColumns,
			batchStrategy: new QueryDataReaderBatchReadStrategy(batchSize: 2),
		);

		$rows = iterator_to_array($rowsReader->read(), false);

		self::assertCount(7, $rows);
		self::assertSame(
			[
				['id' => 1, 'name' => 'Phone'],
				['id' => 2, 'name' => 'Tablet'],
				['id' => 3, 'name' => 'Laptop'],
				['id' => 4, 'name' => 'Monitor'],
				['id' => 5, 'name' => 'Keyboard'],
				['id' => 6, 'name' => 'Mouse'],
				['id' => 7, 'name' => 'Headphones'],
			],
			$rows,
		);
	}
}
