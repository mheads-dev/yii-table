<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Driver\Common;

use Mheads\Yii\Table\Column\Column;
use Mheads\Yii\Table\Filter\FilterInput;
use Mheads\Yii\Table\Filter\SearchFilter;
use Mheads\Yii\Table\Provider\TableProvider;
use Mheads\Yii\Table\Serialization\TableArraySerializer;
use Mheads\Yii\Table\Sort\SortDefinition;
use Mheads\Yii\Table\Tests\Stubs\ActiveRecord\Product;
use Mheads\Yii\Table\Tests\Stubs\Db\DbQueryDataReader;
use Mheads\Yii\Table\Tests\TestCase;
use Yiisoft\Data\Reader\Sort;

abstract class TableProviderArSerializationTestCase extends TestCase
{
	/**
	 * Проверяет полный AR-flow без asArray:
	 * object-read колонок + filter + sort + serialize.
	 */
	public function testSerializesTableFromActiveRecordObjects(): void
	{
		$query = Product::query();
		$reader = DbQueryDataReader::create($query);

		$table = new TableProvider('products-ar', $reader);
		$table->addColumn(
			new Column(
				'id',
				'ID',
				static fn(object $row): int => (int)$row->id,
				isId: true,
				sort: SortDefinition::byField('id'),
			),
		);
		$table->addColumn(
			new Column(
				'name',
				'Name',
				static fn(object $row): string => (string)$row->name,
				filter: new SearchFilter(
					key: 'name',
					title: 'Name',
					field: 'name',
					searchMode: SearchFilter::SEARCH_MODE_EQUAL,
				),
			),
		);
		$table->addColumn(new Column('categoryId', 'CategoryId', static fn(object $row): int => (int)$row->category_id));
		$table->setFilterInput(new FilterInput(['name' => 'Mouse X']));
		$table->setSort(Sort::any()->withOrderString('-id'));
		$table->setPageSize(5);

		$payload = (new TableArraySerializer())->serialize($table);

		self::assertSame(
			[
				'tableId'            => 'products-ar',
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
		self::assertSame([['id' => 4, 'name' => 'Mouse X', 'categoryId' => 2]], $payload['rows']);
		self::assertSame(
			[
				[
					'key'        => 'name',
					'title'      => 'Name',
					'caption'    => null,
					'type'       => 'search',
					'values'     => ['Mouse X'],
					'columnKey'  => 'name',
					'isMultiple' => false,
					'searchMode' => 'equal',
				],
			],
			$payload['filters'],
		);
	}
}
