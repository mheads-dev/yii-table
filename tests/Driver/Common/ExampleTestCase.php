<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Driver\Common;

use Mheads\Yii\Table\Tests\TestCase;

abstract class ExampleTestCase extends TestCase
{
	/**
	 * Базовая проверка тестовой БД: запись с id=1 доступна и содержит ожидаемое имя.
	 */
	public function testExample(): void
	{
		$row = self::db()->createQuery()
			->from('product')
			->where(['id' => 1])
			->one();

		self::assertNotNull($row);
		self::assertEquals(1, $row['id']);
		self::assertEquals('Phone', $row['name']);
	}
}
