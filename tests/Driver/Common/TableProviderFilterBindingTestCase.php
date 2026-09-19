<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Driver\Common;

use Mheads\Yii\Table\Column\Column;
use Mheads\Yii\Table\Filter\CheckboxFilter;
use Mheads\Yii\Table\Filter\FilterInput;
use Mheads\Yii\Table\Filter\NumberFilter;
use Mheads\Yii\Table\Filter\SearchFilter;
use Mheads\Yii\Table\I18n\TableTranslatorInterface;
use Mheads\Yii\Table\Provider\TableProvider;
use Mheads\Yii\Table\Serialization\TableArraySerializer;
use Mheads\Yii\Table\Sort\SortDefinition;
use Mheads\Yii\Table\Tests\Stubs\Db\DbQueryDataReader;
use Mheads\Yii\Table\Tests\TestCase;
use Yiisoft\Data\Reader\Sort;

abstract class TableProviderFilterBindingTestCase extends TestCase
{
	/**
	 * Проверяет привязку extraFilters к колонке и сериализацию extraFilterKeys/columnKey.
	 */
	public function testExtraFiltersLinkedToColumnAndSerializedInColumnKeys(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);

		$table = new TableProvider('products', $reader);
		$table->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id'], isId: true));
		$table->addColumn(
			new Column(
				'name',
				'Name',
				static fn(array $row): string => (string)$row['name'],
				filter: new SearchFilter(key: 'name', title: 'Name', field: 'name', searchMode: SearchFilter::SEARCH_MODE_EQUAL),
				extraFilters: [
					new CheckboxFilter(
						key: 'category',
						title: 'Category',
						field: 'category',
						options: [
							['label' => 'Accessory', 'value' => 'accessory'],
							['label' => 'Computer', 'value' => 'computer'],
							['label' => 'Mobile', 'value' => 'mobile'],
						],
					),
				],
			),
		);

		$payload = (new TableArraySerializer())->serialize($table);

		self::assertSame(
			[
				['key' => 'id', 'title' => 'ID', 'sort' => null, 'isHidden' => false, 'filterKey' => null, 'extraFilterKeys' => []],
				['key' => 'name', 'title' => 'Name', 'sort' => null, 'isHidden' => false, 'filterKey' => 'name', 'extraFilterKeys' => ['category']],
			],
			$payload['columns'],
		);
		self::assertSame('name', $payload['filters'][0]['columnKey']);
		self::assertSame('name', $payload['filters'][1]['columnKey']);
	}

	public function testTableLevelFilterSerializedAndAppliesToRows(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);

		$table = new TableProvider('products', $reader);
		$table->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id'], isId: true));
		$table->addColumn(new Column('name', 'Name', static fn(array $row): string => (string)$row['name']));
		$table->addFilter(new SearchFilter(
			key: 'search',
			title: 'Search',
			field: 'name',
			searchMode: SearchFilter::SEARCH_MODE_EQUAL,
		));
		$table->setFilterInput(new FilterInput(['search' => 'Mouse']));

		$payload = (new TableArraySerializer())->serialize($table);

		self::assertSame(
			[
				['key' => 'id', 'title' => 'ID', 'sort' => null, 'isHidden' => false, 'filterKey' => null, 'extraFilterKeys' => []],
				['key' => 'name', 'title' => 'Name', 'sort' => null, 'isHidden' => false, 'filterKey' => null, 'extraFilterKeys' => []],
			],
			$payload['columns'],
		);
		self::assertSame('search', $payload['filters'][0]['key']);
		self::assertNull($payload['filters'][0]['columnKey']);
		self::assertSame([['id' => 6, 'name' => 'Mouse']], $payload['rows']);
	}

	public function testTableLevelFilterReceivesTranslator(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);

		$table = new TableProvider(
			'products',
			$reader,
			translator: new TableLevelFilterTranslatorStub(),
		);
		$table->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id'], isId: true));
		$table->addFilter(new NumberFilter(key: 'id', title: 'ID', field: 'id'));

		$payload = (new TableArraySerializer())->serialize($table);

		self::assertSame('T:number_filter.exactly', $payload['filters'][0]['select'][0]['title']);
		self::assertNull($payload['filters'][0]['columnKey']);
	}

	public function testTableLevelFilterOverwritesFilterWithSameKey(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);

		$table = new TableProvider('products', $reader);
		$table->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id'], isId: true, sort: SortDefinition::byField('id')));
		$table->addColumn(new Column('name', 'Name', static fn(array $row): string => (string)$row['name']));
		$table->addFilter(new SearchFilter(key: 'search', title: 'Search', field: 'name'));
		$table->addFilter(new SearchFilter(key: 'search', title: 'Search', field: 'category'));
		$table->setFilterInput(new FilterInput(['search' => 'accessory']));
		$table->setSort(Sort::any()->withOrderString('id'));

		$payload = (new TableArraySerializer())->serialize($table);

		self::assertCount(1, $payload['filters']);
		self::assertSame([5, 6, 7], array_column($payload['rows'], 'id'));
	}
}

final class TableLevelFilterTranslatorStub implements TableTranslatorInterface
{
	public function translate(string $id, array $parameters = []): string
	{
		return 'T:' . $id;
	}
}
