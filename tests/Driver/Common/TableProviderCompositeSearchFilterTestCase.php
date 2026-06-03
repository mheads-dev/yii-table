<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Driver\Common;

use Mheads\Yii\Table\Column\Column;
use Mheads\Yii\Table\Filter\CompositeSearchFilter;
use Mheads\Yii\Table\Filter\FilterInput;
use Mheads\Yii\Table\Filter\SearchFilter;
use Mheads\Yii\Table\Provider\TableProvider;
use Mheads\Yii\Table\Serialization\TableArraySerializer;
use Mheads\Yii\Table\Sort\SortDefinition;
use Mheads\Yii\Table\Tests\Stubs\Db\DbQueryDataReader;
use Mheads\Yii\Table\Tests\TestCase;
use Yiisoft\Data\Reader\Sort;

abstract class TableProviderCompositeSearchFilterTestCase extends TestCase
{
	/**
	 * Проверяет OR-режим по умолчанию:
	 * один query находит записи, где совпадение есть в любой из колонок.
	 */
	public function testCompositeSearchFilterUsesOrByDefault(): void
	{
		$query = self::db()->createQuery()->from('composite_product');
		$reader = DbQueryDataReader::create($query);

		$filter = (new CompositeSearchFilter('query', 'Query'))
			->addField('name', SearchFilter::SEARCH_MODE_LIKE)
			->addField('category', SearchFilter::SEARCH_MODE_LIKE);

		$table = new TableProvider('composite-products', $reader);
		$table->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id'], isId: true, sort: new SortDefinition(['id' => SORT_ASC], ['id' => SORT_DESC])));
		$table->addColumn(new Column('name', 'Name', static fn(array $row): string => (string)$row['name'], filter: $filter));
		$table->addColumn(new Column('category', 'Category', static fn(array $row): ?string => $row['category'] !== null ? (string)$row['category'] : null));
		$table->setFilterInput(new FilterInput(['query' => 'solo']));
		$table->setSort(Sort::any()->withOrderString('id'));

		$payload = (new TableArraySerializer())->serialize($table);

		self::assertSame('search', $payload['filters'][0]['type']);
		self::assertFalse($payload['filters'][0]['isMultiple']);
		self::assertSame(['solo'], $payload['filters'][0]['values']);
		self::assertSame([1, 2], array_column($payload['rows'], 'id'));
	}

	/**
	 * Проверяет AND-режим:
	 * запись возвращается только когда query совпадает во всех подключенных колонках.
	 */
	public function testCompositeSearchFilterCanUseAnd(): void
	{
		$query = self::db()->createQuery()->from('composite_product');
		$reader = DbQueryDataReader::create($query);

		$filter = (new CompositeSearchFilter('query', 'Query', combineWithOr: false))
			->addField('name', SearchFilter::SEARCH_MODE_LIKE)
			->addField('category', SearchFilter::SEARCH_MODE_LIKE);

		$table = new TableProvider('composite-products', $reader);
		$table->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id'], isId: true, sort: new SortDefinition(['id' => SORT_ASC], ['id' => SORT_DESC])));
		$table->addColumn(new Column('name', 'Name', static fn(array $row): string => (string)$row['name'], filter: $filter));
		$table->addColumn(new Column('category', 'Category', static fn(array $row): ?string => $row['category'] !== null ? (string)$row['category'] : null));
		$table->setFilterInput(new FilterInput(['query' => 'both']));
		$table->setSort(Sort::any()->withOrderString('id'));

		$payload = (new TableArraySerializer())->serialize($table);

		self::assertSame([3], array_column($payload['rows'], 'id'));
	}

	/**
	 * Проверяет режим equal без совпадений:
	 * частичное вхождение не должно матчиться.
	 */
	public function testCompositeSearchFilterSupportsEqualMode(): void
	{
		$query = self::db()->createQuery()->from('composite_product');
		$reader = DbQueryDataReader::create($query);

		$filter = (new CompositeSearchFilter('query', 'Query'))
			->addField('name', SearchFilter::SEARCH_MODE_EQUAL)
			->addField('category', SearchFilter::SEARCH_MODE_EQUAL);

		$table = new TableProvider('composite-products', $reader);
		$table->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id'], isId: true, sort: new SortDefinition(['id' => SORT_ASC], ['id' => SORT_DESC])));
		$table->addColumn(new Column('name', 'Name', static fn(array $row): string => (string)$row['name'], filter: $filter));
		$table->addColumn(new Column('category', 'Category', static fn(array $row): ?string => $row['category'] !== null ? (string)$row['category'] : null));
		$table->setFilterInput(new FilterInput(['query' => 'solo']));
		$table->setSort(Sort::any()->withOrderString('id'));

		$payload = (new TableArraySerializer())->serialize($table);

		self::assertSame([], array_column($payload['rows'], 'id'));
	}

	/**
	 * Проверяет режим equal с точным совпадением.
	 */
	public function testCompositeSearchFilterSupportsEqualModeWithMatch(): void
	{
		$query = self::db()->createQuery()->from('composite_product');
		$reader = DbQueryDataReader::create($query);

		$filter = (new CompositeSearchFilter('query', 'Query'))
			->addField('name', SearchFilter::SEARCH_MODE_EQUAL)
			->addField('category', SearchFilter::SEARCH_MODE_EQUAL);

		$table = new TableProvider('composite-products', $reader);
		$table->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id'], isId: true, sort: new SortDefinition(['id' => SORT_ASC], ['id' => SORT_DESC])));
		$table->addColumn(new Column('name', 'Name', static fn(array $row): string => (string)$row['name'], filter: $filter));
		$table->addColumn(new Column('category', 'Category', static fn(array $row): ?string => $row['category'] !== null ? (string)$row['category'] : null));
		$table->setFilterInput(new FilterInput(['query' => 'both-value']));
		$table->setSort(Sort::any()->withOrderString('id'));

		$payload = (new TableArraySerializer())->serialize($table);

		self::assertSame([3], array_column($payload['rows'], 'id'));
	}
}
