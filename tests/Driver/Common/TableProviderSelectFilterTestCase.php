<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Driver\Common;

use Mheads\Yii\Table\Column\Column;
use Mheads\Yii\Table\Filter\FilterInput;
use Mheads\Yii\Table\Filter\SelectFilter;
use Mheads\Yii\Table\Filter\SourceOptionsConfig;
use Mheads\Yii\Table\Provider\TableProvider;
use Mheads\Yii\Table\Serialization\TableArraySerializer;
use Mheads\Yii\Table\Sort\SortDefinition;
use Mheads\Yii\Table\Tests\Stubs\Db\DbQueryDataReader;
use Mheads\Yii\Table\Tests\TestCase;
use Yiisoft\Data\Reader\Sort;

abstract class TableProviderSelectFilterTestCase extends TestCase
{
	/**
	 * Проверяет SelectFilter в режиме equal:
	 * нормализацию строкового значения в массив и применение фильтра к rows.
	 */
	public function testSelectFilterEqualModeAcceptsStringAndFiltersRows(): void
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
				filter: new SelectFilter(
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
		self::assertSame('select', $payload['filters'][0]['type']);
		self::assertFalse($payload['filters'][0]['isMultiple']);
		self::assertSame('equal', $payload['filters'][0]['searchMode']);
		self::assertSame([5, 6, 7], array_column($payload['rows'], 'id'));
	}

	/**
	 * Проверяет SelectFilter в режиме like + multiple:
	 * объединение значений через OR и сериализацию массива values.
	 */
	public function testSelectFilterLikeModeWithMultipleValues(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);

		$table = new TableProvider('products', $reader);
		$table->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id'], isId: true, sort: new SortDefinition(['id' => SORT_ASC], ['id' => SORT_DESC])));
		$table->addColumn(
			new Column(
				'name',
				'Name',
				static fn(array $row): string => (string)$row['name'],
				filter: new SelectFilter(
					key: 'name',
					title: 'Name',
					field: 'name',
					options: [
						['label' => 'Phone', 'value' => 'Phone'],
						['label' => 'Tab', 'value' => 'Tab'],
					],
					isMultiple: true,
					searchMode: SelectFilter::SEARCH_MODE_LIKE,
				),
			),
		);
		$table->setFilterInput(new FilterInput(['name' => ['Phone', 'Tab']]));
		$table->setSort(Sort::any()->withOrderString('id'));

		$payload = (new TableArraySerializer())->serialize($table);

		self::assertSame(['Phone', 'Tab'], $payload['filters'][0]['values']);
		self::assertTrue($payload['filters'][0]['isMultiple']);
		self::assertSame('like', $payload['filters'][0]['searchMode']);
		self::assertSame([1, 2, 7], array_column($payload['rows'], 'id'));
	}

	/**
	 * Проверяет поддержку SourceOptionsConfig в SelectFilter:
	 * объектный options в payload и selectedOptions для выбранных values.
	 */
	public function testSelectFilterSupportsSourceOptionsConfig(): void
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
				filter: new SelectFilter(
					key: 'category',
					title: 'Category',
					field: 'category',
					options: new SourceOptionsConfig(
						url: '/api/categories/options',
						termParam: 'q',
						selectedOptionsGetter: static fn(array $values): array => array_map(
							static fn(string $value): array => ['label' => ucfirst($value), 'value' => $value],
							$values,
						),
					),
				),
			),
		);
		$table->setFilterInput(new FilterInput(['category' => 'accessory']));
		$table->setSort(Sort::any()->withOrderString('id'));

		$payload = (new TableArraySerializer())->serialize($table);

		self::assertSame(
			['url' => '/api/categories/options', 'termParam' => 'q'],
			$payload['filters'][0]['options'],
		);
		self::assertSame(
			[['label' => 'Accessory', 'value' => 'accessory']],
			$payload['filters'][0]['selectedOptions'],
		);
	}
}
