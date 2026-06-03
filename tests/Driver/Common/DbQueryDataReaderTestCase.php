<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Driver\Common;

use Mheads\Yii\Table\Tests\Stubs\Db\DbQueryDataReader;
use Mheads\Yii\Table\Tests\TestCase;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Reader\Filter\Equals;
use Yiisoft\Data\Reader\Sort;

abstract class DbQueryDataReaderTestCase extends TestCase
{
	/**
	 * Проверяет, что DbQueryDataReader применяет одновременно filter и sort.
	 */
	public function testCanFilterAndSortRows(): void
	{
		$query = self::db()->createQuery()->from('product');
		$sort = Sort::only(['id'])->withOrderString('-id');

		$reader = DbQueryDataReader::create(
			query: $query,
			sort: $sort,
			filter: new Equals('name', 'Tablet'),
		);

		$rows = iterator_to_array($reader->read(), false);

		self::assertCount(1, $rows);
		self::assertSame('2', (string)$rows[0]['id']);
		self::assertSame('Tablet', $rows[0]['name']);
	}

	/**
	 * Проверяет offset-pagination поверх DbQueryDataReader и расчет общего числа страниц.
	 */
	public function testCanPaginateRowsWithOffsetPaginator(): void
	{
		$query = self::db()->createQuery()->from('product');
		$sort = Sort::only(['id'])->withOrderString('id');
		$reader = DbQueryDataReader::create(query: $query, sort: $sort);

		$paginator = (new OffsetPaginator($reader))
			->withPageSize(1)
			->withCurrentPage(2);

		$rows = iterator_to_array($paginator->read(), false);

		self::assertCount(1, $rows);
		self::assertSame('2', (string)$rows[0]['id']);
		self::assertSame('Tablet', $rows[0]['name']);
		self::assertSame(7, $paginator->getTotalPages());
	}
}
