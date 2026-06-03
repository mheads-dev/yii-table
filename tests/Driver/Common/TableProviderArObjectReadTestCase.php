<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Driver\Common;

use Mheads\Yii\Table\Column\Column;
use Mheads\Yii\Table\Provider\TableProvider;
use Mheads\Yii\Table\Sort\SortDefinition;
use Mheads\Yii\Table\Tests\Stubs\ActiveRecord\Product;
use Mheads\Yii\Table\Tests\Stubs\Db\DbQueryDataReader;
use Mheads\Yii\Table\Tests\TestCase;
use Yiisoft\Data\Reader\Sort;

abstract class TableProviderArObjectReadTestCase extends TestCase
{
	/**
	 * Проверяет чтение значений колонок из AR-объекта (без asArray()).
	 */
	public function testReadsColumnValuesFromActiveRecordObject(): void
	{
		$query = Product::query();
		$reader = DbQueryDataReader::create($query);

		$table = new TableProvider('products-ar', $reader);
		$table->addColumn(new Column('id', 'ID', static fn(object $row): int => (int)$row->id, isId: true, sort: SortDefinition::byField('id')));
		$table->addColumn(new Column('name', 'Name', static fn(object $row): string => (string)$row->name));
		$table->setSort(Sort::any()->withOrderString('id'));

		self::assertSame([1, 2, 3, 4], array_column($table->rows(), 'id'));
		self::assertSame('Phone X', $table->rows()[0]['name']);
	}
}
