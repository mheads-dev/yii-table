<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Driver\Common;

use Mheads\Yii\Table\Column\Column;
use Mheads\Yii\Table\Filter\CheckboxFilter;
use Mheads\Yii\Table\Filter\FilterInput;
use Mheads\Yii\Table\Provider\TableProvider;
use Mheads\Yii\Table\Serialization\TableArraySerializer;
use Mheads\Yii\Table\Sort\SortDefinition;
use Mheads\Yii\Table\Tests\Stubs\Db\DbQueryDataReader;
use Mheads\Yii\Table\Tests\TestCase;
use Yiisoft\Data\Reader\Sort;

abstract class TableProviderCheckboxFilterTestCase extends TestCase
{
	/**
	 * Проверяет, что CheckboxFilter строит IN-условие и корректно сериализует options/values.
	 */
	public function testCheckboxFilterBuildsInConditionAndSerializesOptions(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);

		$table = new TableProvider('products', $reader);
		$table->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id'], isId: true));
		$table->addColumn(
			new Column(
				'category',
				'Category',
				static fn(array $row): ?string => $row['category'] !== null ? (string)$row['category'] : null,
				filter: new CheckboxFilter(
					key: 'category',
					title: 'Category',
					field: 'category',
					options: [
						['label' => 'Accessory', 'value' => 'accessory'],
						['label' => 'Computer', 'value' => 'computer'],
						['label' => 'Mobile', 'value' => 'mobile'],
					],
				),
			),
		);
		$table->setFilterInput(new FilterInput(['category' => ['accessory', 'computer']]));
		$table->setSort(Sort::any()->withOrderString('id'));

		$payload = (new TableArraySerializer())->serialize($table);

		self::assertSame([3, 4, 5, 6, 7], array_column($payload['rows'], 'id'));
		self::assertSame(
			[
				[
					'key'       => 'category',
					'title'     => 'Category',
					'caption'   => null,
					'type'      => 'checkbox',
					'values'    => ['accessory', 'computer'],
					'columnKey' => 'category',
					'options'   => [
						['label' => 'Accessory', 'value' => 'accessory'],
						['label' => 'Computer', 'value' => 'computer'],
						['label' => 'Mobile', 'value' => 'mobile'],
					],
				],
			],
			$payload['filters'],
		);
	}

	/**
	 * Проверяет, что без входного значения у CheckboxFilter в payload возвращается values = null.
	 */
	public function testCheckboxFilterHasNullValuesWhenInputNotProvided(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);

		$table = new TableProvider('products', $reader);
		$table->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id'], isId: true, sort: new SortDefinition(['id' => SORT_ASC], ['id' => SORT_DESC])));
		$table->addColumn(
			new Column(
				'category',
				'Category',
				static fn(array $row): ?string => $row['category'] !== null ? (string)$row['category'] : null,
				filter: new CheckboxFilter(
					key: 'category',
					title: 'Category',
					field: 'category',
					options: [
						['label' => 'Accessory', 'value' => 'accessory'],
						['label' => 'Computer', 'value' => 'computer'],
						['label' => 'Mobile', 'value' => 'mobile'],
					],
				),
			),
		);
		$table->setSort(Sort::any()->withOrderString('id'));

		$payload = (new TableArraySerializer())->serialize($table);

		self::assertNull($payload['filters'][0]['values']);
		self::assertSame([1, 2, 3, 4, 5, 6, 7], array_column($payload['rows'], 'id'));
	}

	/**
	 * Проверяет нормализацию строкового входа CheckboxFilter к массиву из одного элемента.
	 */
	public function testCheckboxFilterAcceptsStringInputValue(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);

		$table = new TableProvider('products', $reader);
		$table->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id'], isId: true, sort: new SortDefinition(['id' => SORT_ASC], ['id' => SORT_DESC])));
		$table->addColumn(
			new Column(
				'category',
				'Category',
				static fn(array $row): ?string => $row['category'] !== null ? (string)$row['category'] : null,
				filter: new CheckboxFilter(
					key: 'category',
					title: 'Category',
					field: 'category',
					options: [
						['label' => 'Accessory', 'value' => 'accessory'],
						['label' => 'Computer', 'value' => 'computer'],
						['label' => 'Mobile', 'value' => 'mobile'],
					],
				),
			),
		);
		$table->setFilterInput(new FilterInput(['category' => 'accessory']));
		$table->setSort(Sort::any()->withOrderString('id'));

		$payload = (new TableArraySerializer())->serialize($table);

		self::assertSame(['accessory'], $payload['filters'][0]['values']);
		self::assertSame([5, 6, 7], array_column($payload['rows'], 'id'));
	}
}
