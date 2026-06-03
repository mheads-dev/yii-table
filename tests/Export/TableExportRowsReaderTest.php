<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Export;

use Mheads\Yii\Table\Export\BatchStrategy\OffsetLimitBatchReadStrategy;
use Mheads\Yii\Table\Export\Column\ExportColumn;
use Mheads\Yii\Table\Export\RowsReader\TableExportRowsReader;
use PHPUnit\Framework\TestCase;
use Traversable;
use Yiisoft\Data\Reader\DataReaderInterface;
use Yiisoft\Data\Reader\FilterInterface;
use Yiisoft\Data\Reader\Iterable\IterableDataReader;
use Yiisoft\Data\Reader\Sort;

/**
 * @internal
 */
final class TableExportRowsReaderTest extends TestCase
{
	public function testIgnoresReaderOffsetAndRespectsReaderLimitInBatches(): void
	{
		$baseReader = (new IterableDataReader(
			data: [
				['id' => 1, 'name' => 'A'],
				['id' => 2, 'name' => 'B'],
				['id' => 3, 'name' => 'C'],
				['id' => 4, 'name' => 'D'],
				['id' => 5, 'name' => 'E'],
			],
		))
			->withOffset(1)
			->withLimit(3);

		$reader = new SpyDataReaderForExportTest(
			$baseReader,
			new SpyDataReaderCallLog(),
		);

		$rowsReader = new TableExportRowsReader(
			$reader,
			[
				new ExportColumn('id', 'ID', static fn(array $row): int => $row['id']),
				new ExportColumn('name', 'Name', static fn(array $row): string => $row['name']),
			],
			batchStrategy: new OffsetLimitBatchReadStrategy(batchSize: 2),
		);

		$rows = iterator_to_array($rowsReader->read(), false);

		self::assertSame(
			[
				['id' => 1, 'name' => 'A'],
				['id' => 2, 'name' => 'B'],
				['id' => 3, 'name' => 'C'],
			],
			$rows,
		);
		self::assertSame([0, 0, 2], $reader->log->offsetCalls);
		self::assertSame([2, 1], $reader->log->limitCalls);
	}
}

/**
 * @internal
 *
 * @implements DataReaderInterface<int, array{id:int,name:string}>
 */
final class SpyDataReaderForExportTest implements DataReaderInterface
{
	/**
	 * @param DataReaderInterface<int, array{id:int,name:string}> $inner
	 */
	public function __construct(
		private DataReaderInterface $inner,
		public SpyDataReaderCallLog $log,
	) {}

	public function read(): iterable
	{
		return $this->inner->read();
	}

	public function readOne(): array|object|null
	{
		return $this->inner->readOne();
	}

	public function withOffset(int $offset): static
	{
		$new = clone $this;
		$new->log->offsetCalls[] = $offset;
		$new->inner = $this->inner->withOffset($offset);
		return $new;
	}

	public function getOffset(): int
	{
		return $this->inner->getOffset();
	}

	public function withLimit(?int $limit): static
	{
		$new = clone $this;
		$new->log->limitCalls[] = $limit;
		$new->inner = $this->inner->withLimit($limit);
		return $new;
	}

	public function getLimit(): ?int
	{
		return $this->inner->getLimit();
	}

	public function count(): int
	{
		return $this->inner->count();
	}

	public function withSort(?Sort $sort): static
	{
		$new = clone $this;
		$new->inner = $this->inner->withSort($sort);
		return $new;
	}

	public function getSort(): ?Sort
	{
		return $this->inner->getSort();
	}

	public function withFilter(FilterInterface $filter): static
	{
		$new = clone $this;
		$new->inner = $this->inner->withFilter($filter);
		return $new;
	}

	public function getFilter(): FilterInterface
	{
		return $this->inner->getFilter();
	}

	public function getIterator(): Traversable
	{
		yield from $this->read();
	}
}

/**
 * @internal
 */
final class SpyDataReaderCallLog
{
	/** @var array<int, int> */
	public array $offsetCalls = [];

	/** @var array<int, int|null> */
	public array $limitCalls = [];
}
