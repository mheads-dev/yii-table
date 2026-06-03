<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Driver\Common;

use Mheads\Yii\Table\Column\Column;
use Mheads\Yii\Table\Filter\FilterInput;
use Mheads\Yii\Table\Filter\SearchFilter;
use Mheads\Yii\Table\Provider\TableProvider;
use Mheads\Yii\Table\Sort\SortDefinition;
use Mheads\Yii\Table\Tests\Stubs\ActiveRecord\Product;
use Mheads\Yii\Table\Tests\Stubs\Db\DbQueryDataReader;
use Mheads\Yii\Table\Tests\TestCase;
use Yiisoft\Data\Reader\Sort;

abstract class TableProviderArRelationTestCase extends TestCase
{
	/**
	 * Проверяет AR relation через joinWith:
	 * фильтр и сорт по колонке связанной таблицы.
	 */
	public function testSupportsFilterAndSortByRelatedTableColumn(): void
	{
		$query = Product::query()->joinWith('category');
		$reader = DbQueryDataReader::create($query);

		$table = new TableProvider('ar-products', $reader);
		$table->addColumn(new Column('id', 'ID', static fn(object $row): int => (int)$row->id, isId: true));
		$table->addColumn(new Column('name', 'Name', static fn(object $row): string => (string)$row->name));
		$table->addColumn(
			new Column(
				'categoryTitle',
				'Category',
				static fn(object $row): string => (string)$row->relation('category')->get('title'),
				filter: new SearchFilter(
					key: 'categoryTitle',
					title: 'Category',
					field: 'ar_category.title',
					searchMode: SearchFilter::SEARCH_MODE_EQUAL,
				),
			),
		);
		$table->addSort(
			'category_sort',
			SortDefinition::fixedOrder(['ar_category.title' => SORT_ASC, 'ar_product.id' => SORT_ASC]),
		);
		$table->setFilterInput(new FilterInput(['categoryTitle' => 'mobile-cat']));
		$table->setSort(Sort::any()->withOrderString('category_sort'));

		self::assertSame([1, 2], array_column($table->rows(), 'id'));

		$table->setFilterInput(new FilterInput());
		self::assertSame([3, 4, 1, 2], array_column($table->rows(), 'id'));
	}
}
