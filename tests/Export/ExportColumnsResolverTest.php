<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Export;

use Mheads\Yii\Table\Column\ColumnInterface;
use Mheads\Yii\Table\Export\Column\ExportColumn;
use Mheads\Yii\Table\Export\Column\ExportColumnMode;
use Mheads\Yii\Table\Export\Column\ExportColumnsResolver;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ExportColumnsResolverTest extends TestCase
{
	public function testResolvesTableOnlyColumns(): void
	{
		$resolver = new ExportColumnsResolver();

		$result = $resolver->resolve(
			[
				$this->createColumn('id', 'ID'),
				$this->createColumn('name', 'Name'),
			],
			[],
			ExportColumnMode::TABLE_ONLY,
		);

		self::assertSame(['id', 'name'], array_map(static fn(ExportColumn $column): string => $column->key(), $result));
	}

	public function testResolvesCustomOnlyColumns(): void
	{
		$resolver = new ExportColumnsResolver();
		$custom = [
			new ExportColumn('sku', 'SKU', static fn(mixed $row): mixed => $row),
		];

		$result = $resolver->resolve([], $custom, ExportColumnMode::CUSTOM_ONLY);

		self::assertSame(['sku'], array_map(static fn(ExportColumn $column): string => $column->key(), $result));
	}

	public function testResolvesMergedColumnsAndOverridesByName(): void
	{
		$resolver = new ExportColumnsResolver();

		$result = $resolver->resolve(
			[
				$this->createColumn('id', 'ID'),
				$this->createColumn('name', 'Name'),
			],
			[
				new ExportColumn('name', 'Product Name', static fn(mixed $row): mixed => $row),
				new ExportColumn('price', 'Price', static fn(mixed $row): mixed => $row),
			],
			ExportColumnMode::MERGE,
		);

		self::assertSame(['id', 'name', 'price'], array_map(static fn(ExportColumn $column): string => $column->key(), $result));
		self::assertSame('Product Name', $result[1]->title());
	}

	public function testCreatesExportColumnFromTableColumn(): void
	{
		$column = $this->createColumn('name', 'Name');
		$exportColumn = ExportColumn::fromColumn($column);

		self::assertSame('name', $exportColumn->key());
		self::assertSame('Name', $exportColumn->title());
	}

	private function createColumn(string $name, string $label): ColumnInterface
	{
		$column = self::createStub(ColumnInterface::class);
		$column->method('key')->willReturn($name);
		$column->method('title')->willReturn($label);
		$column->method('read')->willReturnCallback(static fn(mixed $row): mixed => $row);

		return $column;
	}
}
