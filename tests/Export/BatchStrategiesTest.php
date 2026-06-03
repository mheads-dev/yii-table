<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Export;

use InvalidArgumentException;
use Mheads\Yii\Table\Export\BatchStrategy\BatchStrategyInterface;
use Mheads\Yii\Table\Export\BatchStrategy\OffsetLimitBatchReadStrategy;
use Mheads\Yii\Table\Export\BatchStrategy\QueryDataReaderBatchReadStrategy;
use Mheads\Yii\Table\Export\Column\ExportColumn;
use Mheads\Yii\Table\Export\RowsReader\TableExportRowsReader;
use PHPUnit\Framework\TestCase;
use Traversable;
use Yiisoft\Data\Db\QueryDataReaderInterface;
use Yiisoft\Data\Reader\DataReaderInterface;
use Yiisoft\Data\Reader\FilterInterface;
use Yiisoft\Data\Reader\Iterable\IterableDataReader;
use Yiisoft\Data\Reader\ReadableDataInterface;
use Yiisoft\Data\Reader\Sort;

/**
 * @internal
 */
final class BatchStrategiesTest extends TestCase
{
	/**
	 * Проверяет, что offset/limit стратегия применима только к DataReaderInterface.
	 */
	public function testOffsetLimitStrategyCanReadOnlyDataReaderInterface(): void
	{
		$strategy = new OffsetLimitBatchReadStrategy(batchSize: 2);

		self::assertTrue($strategy->canRead(new BatchStrategySpyDataReader(
			new IterableDataReader([['id' => 1]]),
			new BatchStrategySpyDataReaderLog(),
		)));
		self::assertFalse($strategy->canRead(new BatchStrategyOnlyReadableReader([['id' => 1]])));
	}

	/**
	 * Проверяет, что стратегия читает батчами от offset=0 и уважает source limit.
	 */
	public function testOffsetLimitStrategyReadsInChunksAndRespectsSourceLimit(): void
	{
		$baseReader = (new IterableDataReader([
			['id' => 1],
			['id' => 2],
			['id' => 3],
			['id' => 4],
			['id' => 5],
		]))
			->withOffset(1)
			->withLimit(3);
		$spyReader = new BatchStrategySpyDataReader($baseReader, new BatchStrategySpyDataReaderLog());

		$strategy = new OffsetLimitBatchReadStrategy(batchSize: 2);
		$rows = iterator_to_array($strategy->readBatched($spyReader), false);

		self::assertSame([['id' => 1], ['id' => 2], ['id' => 3]], $rows);
		self::assertSame([0, 2], $spyReader->log->offsetCalls);
		self::assertSame([2, 1], $spyReader->log->limitCalls);
	}

	/**
	 * Проверяет fallback на обычное чтение для ReadableDataInterface без DataReader API.
	 */
	public function testOffsetLimitStrategyFallsBackToPlainReadForNonDataReader(): void
	{
		$reader = new BatchStrategyOnlyReadableReader([['id' => 1], ['id' => 2]]);
		$strategy = new OffsetLimitBatchReadStrategy(batchSize: 2);

		self::assertSame([['id' => 1], ['id' => 2]], iterator_to_array($strategy->readBatched($reader), false));
	}

	/**
	 * Проверяет ошибку при попытке batched-read несовместимого reader.
	 */
	public function testQueryStrategyThrowsForIncompatibleReader(): void
	{
		$strategy = new QueryDataReaderBatchReadStrategy(batchSize: 2);

		$this->expectException(InvalidArgumentException::class);
		iterator_to_array($strategy->readBatched(new BatchStrategyOnlyReadableReader([['id' => 1]])), false);
	}

	/**
	 * Проверяет happy-path query стратегии: withBatchSize() + чтение строк.
	 */
	public function testQueryStrategyUsesWithBatchSizeAndReadsRows(): void
	{
		$strategy = new QueryDataReaderBatchReadStrategy(batchSize: 3);

		$batched = self::createMock(QueryDataReaderInterface::class);
		$batched
			->expects(self::once())
			->method('read')
			->willReturn([['id' => 1], ['id' => 2]]);

		$reader = self::createMock(QueryDataReaderInterface::class);
		$reader
			->expects(self::once())
			->method('withBatchSize')
			->with(3)
			->willReturn($batched);

		self::assertTrue($strategy->canRead($reader));
		self::assertSame([['id' => 1], ['id' => 2]], iterator_to_array($strategy->readBatched($reader), false));
	}

	/**
	 * Проверяет, что TableExportRowsReader делегирует чтение стратегии при canRead=true.
	 */
	public function testTableExportRowsReaderUsesStrategyWhenSupported(): void
	{
		$strategy = new BatchStrategySpyStrategy(canRead: true, rows: [['id' => 11], ['id' => 12]]);
		$rowsReader = new TableExportRowsReader(
			new BatchStrategyOnlyReadableReader([['id' => 1], ['id' => 2]]),
			[
				new ExportColumn('id', 'ID', static fn(array $row): int => (int)$row['id']),
			],
			batchStrategy: $strategy,
		);

		self::assertSame([['id' => 11], ['id' => 12]], iterator_to_array($rowsReader->read(), false));
		self::assertSame(1, $strategy->canReadCalls);
		self::assertSame(1, $strategy->readBatchedCalls);
	}

	/**
	 * Проверяет fallback на обычный reader->read() при canRead=false.
	 */
	public function testTableExportRowsReaderFallsBackWithoutStrategySupport(): void
	{
		$strategy = new BatchStrategySpyStrategy(canRead: false, rows: [['id' => 11]]);
		$rowsReader = new TableExportRowsReader(
			new BatchStrategyOnlyReadableReader([['id' => 1], ['id' => 2]]),
			[
				new ExportColumn('id', 'ID', static fn(array $row): int => (int)$row['id']),
			],
			batchStrategy: $strategy,
		);

		self::assertSame([['id' => 1], ['id' => 2]], iterator_to_array($rowsReader->read(), false));
		self::assertSame(1, $strategy->canReadCalls);
		self::assertSame(0, $strategy->readBatchedCalls);
	}
}

/**
 * @internal
 */
final class BatchStrategyOnlyReadableReader implements ReadableDataInterface
{
	/**
	 * @param array<int, array<string, mixed>> $rows
	 */
	public function __construct(private readonly array $rows) {}

	public function read(): iterable
	{
		yield from $this->rows;
	}

	public function readOne(): array|object|null
	{
		return $this->rows[0] ?? null;
	}
}

/**
 * @internal
 *
 * @implements DataReaderInterface<int, array{id:int}>
 */
final class BatchStrategySpyDataReader implements DataReaderInterface
{
	/**
	 * @param DataReaderInterface<int, array{id:int}> $inner
	 */
	public function __construct(
		private DataReaderInterface $inner,
		public BatchStrategySpyDataReaderLog $log,
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
final class BatchStrategySpyDataReaderLog
{
	/** @var array<int, int> */
	public array $offsetCalls = [];

	/** @var array<int, int|null> */
	public array $limitCalls = [];
}

/**
 * @internal
 */
final class BatchStrategySpyStrategy implements BatchStrategyInterface
{
	public int $canReadCalls = 0;
	public int $readBatchedCalls = 0;

	/**
	 * @param array<int, array<string, mixed>> $rows
	 */
	public function __construct(
		private readonly bool $canRead,
		private readonly array $rows,
	) {}

	public function canRead(ReadableDataInterface $reader): bool
	{
		$this->canReadCalls++;
		return $this->canRead;
	}

	/**
	 * @return iterable<array-key, array|object>
	 */
	public function readBatched(ReadableDataInterface $reader): iterable
	{
		$this->readBatchedCalls++;
		yield from $this->rows;
	}
}
