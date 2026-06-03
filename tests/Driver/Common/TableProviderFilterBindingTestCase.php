<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Driver\Common;

use Mheads\Yii\Table\Column\Column;
use Mheads\Yii\Table\Filter\CheckboxFilter;
use Mheads\Yii\Table\Filter\SearchFilter;
use Mheads\Yii\Table\Provider\TableProvider;
use Mheads\Yii\Table\Serialization\TableArraySerializer;
use Mheads\Yii\Table\Tests\Stubs\Db\DbQueryDataReader;
use Mheads\Yii\Table\Tests\TestCase;

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
}
