<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Driver\Mysql;

use Mheads\Yii\Table\Tests\Driver\Common\TableProviderCheckboxFilterTestCase;
use Mheads\Yii\Table\Tests\Support\MysqlHelper;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * @internal
 */
#[AllowMockObjectsWithoutExpectations]
final class TableProviderCheckboxFilterTest extends TableProviderCheckboxFilterTestCase
{
	protected static function createConnection(): ConnectionInterface
	{
		return (new MysqlHelper())->createConnection();
	}
}
