<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Support;

use Yiisoft\Db\Driver\Pdo\PdoConnectionInterface;

use function dirname;
use function explode;
use function file_get_contents;
use function str_replace;
use function trim;

final class DbHelper
{
	/**
	 * Loads the fixture into the database.
	 */
	public static function loadFixture(PdoConnectionInterface $db): void
	{
		$driverName = $db->getDriverName();

		$fixture = match ($driverName)
		{
			'mysql'  => dirname(__DIR__) . '/data/mysql.sql',
			'pgsql'  => dirname(__DIR__) . '/data/pgsql.sql',
			'sqlsrv' => dirname(__DIR__) . '/data/mssql.sql',
			'sqlite' => dirname(__DIR__) . '/data/sqlite.sql',
			'oci'    => dirname(__DIR__) . '/data/oci.sql',
		};

		if ($db->isActive())
		{
			$db->close();
		}

		$db->open();
		$content = file_get_contents($fixture);
		if($content === false)
		{
			return;
		}

		if($driverName === 'oci')
		{
			[$drops, $creates] = explode('/* STATEMENTS */', $content, 2);
			[$statements, $triggers] = explode('/* TRIGGERS */', $creates, 2);
			$lines = [
				...explode('--', $drops),
				...explode(';', $statements),
				...explode('/', $triggers),
			];
		}
		else
		{
			$lines = explode(';', $content);
		}

		foreach ($lines as $line)
		{
			if (trim($line) !== '')
			{
				$db->getPDO()->exec($line);
			}
		}
	}

	/**
	 * Adjust dbms specific escaping.
	 *
	 * @param string $sql string SQL statement to adjust.
	 * @param string $driverName string DBMS name.
	 */
	public static function replaceQuotes(string $sql, string $driverName): string
	{
		return match ($driverName)
		{
			'mysql'  => str_replace(['[[', ']]'], '`', $sql),
			'pgsql'  => str_replace(['[[', ']]'], '"', $sql),
			'sqlsrv' => str_replace(['[[', ']]'], ['[', ']'], $sql),
			'sqlite' => str_replace(['[[', ']]'], '"', $sql),
			'oci'    => str_replace(['[[', ']]'], '"', $sql),
			default  => $sql,
		};
	}
}
