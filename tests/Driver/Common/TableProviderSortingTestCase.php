<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Driver\Common;

use Mheads\Yii\Table\Column\Column;
use Mheads\Yii\Table\Provider\TableProvider;
use Mheads\Yii\Table\Sort\SortDefinition;
use Mheads\Yii\Table\Sort\SortDsl;
use Mheads\Yii\Table\Sort\SortOption;
use Mheads\Yii\Table\Tests\Stubs\Db\DbQueryDataReader;
use Mheads\Yii\Table\Tests\TestCase;
use Yiisoft\Data\Reader\Sort;

abstract class TableProviderSortingTestCase extends TestCase
{
	/**
	 * Проверяет сортировку:
	 * default, явные asc/desc по id и name, fallback к default при неизвестном поле.
	 */
	public function testSupportsAscDescAndDefaultColumnSorting(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);

		$table = new TableProvider('products', $reader);
		$table->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id'], isId: true, sort: SortDefinition::byField('id', SORT_DESC)));
		$table->addColumn(new Column('name', 'Name', static fn(array $row): string => (string)$row['name'], sort: SortDefinition::byField('name')));
		$table->addColumn(new Column('category', 'Category', static fn(array $row): ?string => $row['category'] !== null ? (string)$row['category'] : null));

		self::assertSame([7, 6, 5, 4, 3, 2, 1], array_column($table->rows(), 'id'));

		$table->setSort(Sort::any()->withOrderString('id'));
		self::assertSame([1, 2, 3, 4, 5, 6, 7], array_column($table->rows(), 'id'));

		$table->setSort(Sort::any()->withOrderString('-id'));
		self::assertSame([7, 6, 5, 4, 3, 2, 1], array_column($table->rows(), 'id'));

		$table->setSort(Sort::any()->withOrderString('name'));
		self::assertSame(
			['Headphones', 'Keyboard', 'Laptop', 'Monitor', 'Mouse', 'Phone', 'Tablet'],
			array_column($table->rows(), 'name'),
		);

		$table->setSort(Sort::any()->withOrderString('-name'));
		self::assertSame(
			['Tablet', 'Phone', 'Mouse', 'Monitor', 'Laptop', 'Keyboard', 'Headphones'],
			array_column($table->rows(), 'name'),
		);

		$table->setSort(Sort::any()->withOrderString('category'));
		self::assertSame([7, 6, 5, 4, 3, 2, 1], array_column($table->rows(), 'id'));
	}

	/**
	 * Проверяет table-level сортировку, не привязанную к Column::sort().
	 */
	public function testSupportsDetachedTableLevelSorting(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);

		$table = new TableProvider('products', $reader);
		$table->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id'], isId: true, sort: SortDefinition::byField('id', SORT_DESC)));
		$table->addColumn(new Column('name', 'Name', static fn(array $row): string => (string)$row['name']));
		$table->addColumn(new Column('category', 'Category', static fn(array $row): ?string => $row['category'] !== null ? (string)$row['category'] : null));
		$table->addSort('by_category', new SortDefinition(['category' => SORT_ASC, 'id' => SORT_ASC], ['category' => SORT_DESC, 'id' => SORT_DESC]));

		$table->setSort(Sort::any()->withOrderString('by_category'));
		self::assertSame(
			['accessory', 'accessory', 'accessory', 'computer', 'computer', 'mobile', 'mobile'],
			array_column($table->rows(), 'category'),
		);

		$table->setSort(Sort::any()->withOrderString('-by_category'));
		self::assertSame(
			['mobile', 'mobile', 'computer', 'computer', 'accessory', 'accessory', 'accessory'],
			array_column($table->rows(), 'category'),
		);
	}

	/**
	 * Проверяет отдельные селект-опции сортировки с фиксированным направлением.
	 */
	public function testSupportsDetachedSingleDirectionSortOptions(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);

		$table = new TableProvider('products', $reader);
		$table->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id']));
		$table->addColumn(new Column('name', 'Name', static fn(array $row): string => (string)$row['name']));
		$table->addSortOption(new SortOption('name_asc', 'By name (A-Z)', SortDefinition::fixedOrder(['name' => SORT_ASC]), isDefault: true));
		$table->addSortOption(new SortOption('name_desc', 'By name (Z-A)', SortDefinition::fixedOrder(['name' => SORT_DESC])));

		self::assertSame(
			['Headphones', 'Keyboard', 'Laptop', 'Monitor', 'Mouse', 'Phone', 'Tablet'],
			array_column($table->rows(), 'name'),
		);

		$table->setSort(Sort::any()->withOrderString('name_desc'));
		self::assertSame(
			['Tablet', 'Phone', 'Mouse', 'Monitor', 'Laptop', 'Keyboard', 'Headphones'],
			array_column($table->rows(), 'name'),
		);
	}

	/**
	 * Проверяет fallback на default sort-option:
	 * 1) когда сортировка не передана,
	 * 2) когда передано неизвестное значение сортировки.
	 */
	public function testFallsBackToDefaultSortOptionWhenMissingOrUnknown(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);

		$table = new TableProvider('products', $reader);
		$table->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id']));
		$table->addColumn(new Column('name', 'Name', static fn(array $row): string => (string)$row['name']));
		$table->addSortOption(new SortOption('name_asc', 'By name (A-Z)', SortDefinition::fixedOrder(['name' => SORT_ASC]), isDefault: true));
		$table->addSortOption(new SortOption('name_desc', 'By name (Z-A)', SortDefinition::fixedOrder(['name' => SORT_DESC])));

		self::assertSame(
			['Headphones', 'Keyboard', 'Laptop', 'Monitor', 'Mouse', 'Phone', 'Tablet'],
			array_column($table->rows(), 'name'),
		);

		$table->setSort(Sort::any()->withOrderString('unknown_sort'));
		self::assertSame(
			['Headphones', 'Keyboard', 'Laptop', 'Monitor', 'Mouse', 'Phone', 'Tablet'],
			array_column($table->rows(), 'name'),
		);
	}

	/**
	 * Проверяет fallback на default sort-option с обратной сортировкой.
	 */
	public function testFallsBackToDescendingDefaultSortOptionWhenMissingOrUnknown(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);

		$table = new TableProvider('products', $reader);
		$table->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id']));
		$table->addColumn(new Column('name', 'Name', static fn(array $row): string => (string)$row['name']));
		$table->addSortOption(SortDsl::optionAsc('name_asc', 'By name (A-Z)', 'name'));
		$table->addSortOption(SortDsl::optionDesc('name_desc', 'By name (Z-A)', 'name', isDefault: true));
		$table->addSortOption(SortDsl::optionAsc('name_disabled', 'By name (disabled)', 'name', isDisabled: true));

		self::assertSame(
			['Tablet', 'Phone', 'Mouse', 'Monitor', 'Laptop', 'Keyboard', 'Headphones'],
			array_column($table->rows(), 'name'),
		);

		$table->setSort(Sort::any()->withOrderString('unknown_sort'));
		self::assertSame(
			['Tablet', 'Phone', 'Mouse', 'Monitor', 'Laptop', 'Keyboard', 'Headphones'],
			array_column($table->rows(), 'name'),
		);

		$table->setSort(Sort::any()->withOrderString('name_disabled'));
		self::assertSame(
			['Tablet', 'Phone', 'Mouse', 'Monitor', 'Laptop', 'Keyboard', 'Headphones'],
			array_column($table->rows(), 'name'),
		);
	}
}
