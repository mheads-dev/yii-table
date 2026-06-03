<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Stubs\Db;

use Yiisoft\Data\Db\QueryDataReader;
use Yiisoft\Data\Db\QueryDataReaderInterface;
use Yiisoft\Data\Reader\Filter\All;
use Yiisoft\Data\Reader\FilterInterface;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Db\Query\QueryInterface;

final class DbQueryDataReader
{
	/**
	 * Creates a DB-backed data reader from a Yii DB query.
	 * @param non-negative-int|null $limit
	 */
	public static function create(
		QueryInterface $query,
		?Sort $sort = null,
		int $offset = 0,
		?int $limit = null,
		?string $countParam = null,
		?FilterInterface $filter = null,
		?FilterInterface $having = null,
		?int $batchSize = null,
	): QueryDataReaderInterface {
		return new QueryDataReader(
			query: $query,
			sort: $sort,
			offset: $offset,
			limit: $limit,
			countParam: $countParam,
			filter: $filter ?? new All(),
			having: $having ?? new All(),
			batchSize: $batchSize,
		);
	}
}

