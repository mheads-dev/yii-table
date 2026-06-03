<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Driver\Mysql;

use Mheads\Yii\Table\Tests\Driver\Common\TableProviderContractsTestCase;
use Mheads\Yii\Table\Tests\Support\MysqlHelper;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * @internal
 */
#[AllowMockObjectsWithoutExpectations]
final class TableProviderContractsTest extends TableProviderContractsTestCase
{
	protected static function createConnection(): ConnectionInterface
	{
		return (new MysqlHelper())->createConnection();
	}
}

