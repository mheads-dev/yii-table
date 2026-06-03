<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Export;

use Mheads\Yii\Table\Export\Column\ExportColumn;
use Mheads\Yii\Table\Export\Exception\ExportCanceledException;
use Mheads\Yii\Table\Export\Exception\ExportTimeoutException;
use Mheads\Yii\Table\Export\RowsReader\CancellationAwareRowsReader;
use Mheads\Yii\Table\Export\RowsReader\ObservableRowsReader;
use Mheads\Yii\Table\Export\RowsReader\RowsReaderInterface;
use Mheads\Yii\Table\Export\RowsReader\TimeoutAwareRowsReader;
use Mheads\Yii\Table\Export\Runtime\ExportCancellationCheckerInterface;
use Mheads\Yii\Table\Export\Runtime\RowsReadObserverInterface;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class RowsReaderRuntimeDecoratorsTest extends TestCase
{
	public function testTimeoutAwareRowsReaderThrowsWhenTimedOutBeforeRead(): void
	{
		// Timeout=0 must fail immediately before first row is yielded.
		$rowsReader = new TimeoutAwareRowsReader(
			new RuntimeRowsReaderStub([['id' => 1]]),
			timeoutSeconds: 0,
		);

		$this->expectException(ExportTimeoutException::class);
		iterator_to_array($rowsReader->read(), false);
	}

	public function testTimeoutAwareRowsReaderPassesDefinitionColumnsAndRows(): void
	{
		// Decorator must not alter schema or payload when timeout not reached.
		$inner = new RuntimeRowsReaderStub([['id' => 1], ['id' => 2]]);
		$rowsReader = new TimeoutAwareRowsReader($inner, timeoutSeconds: 10);

		self::assertSame(
			array_map(static fn(ExportColumn $column): string => $column->key(), $inner->definitionColumns()),
			array_map(static fn(ExportColumn $column): string => $column->key(), $rowsReader->definitionColumns()),
		);
		self::assertSame([['id' => 1], ['id' => 2]], iterator_to_array($rowsReader->read(), false));
	}

	public function testCancellationAwareRowsReaderChecksPeriodicallyAndAtEnd(): void
	{
		// Contract: check at start, every N rows, and once after stream end.
		$checker = new RuntimeCancellationCheckerStub();
		$rowsReader = new CancellationAwareRowsReader(
			new RuntimeRowsReaderStub([['id' => 1], ['id' => 2], ['id' => 3]]),
			$checker,
			checkEachRows: 2,
		);

		self::assertSame([['id' => 1], ['id' => 2], ['id' => 3]], iterator_to_array($rowsReader->read(), false));
		self::assertSame(3, $checker->calls);
	}

	public function testCancellationAwareRowsReaderBubblesCancelException(): void
	{
		// Decorator must not swallow cancellation exception.
		$rowsReader = new CancellationAwareRowsReader(
			new RuntimeRowsReaderStub([['id' => 1]]),
			new RuntimeCancellationCheckerStub(throwOnCall: 1),
		);

		$this->expectException(ExportCanceledException::class);
		iterator_to_array($rowsReader->read(), false);
	}

	public function testObservableRowsReaderNotifiesObserverByChunks(): void
	{
		// Observer should receive start, chunk deltas, then finish(total).
		$observer = new RuntimeRowsObserverStub();
		$rowsReader = new ObservableRowsReader(
			new RuntimeRowsReaderStub([['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4], ['id' => 5]]),
			$observer,
			notifyEachRows: 2,
		);

		self::assertSame([['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4], ['id' => 5]], iterator_to_array($rowsReader->read(), false));
		self::assertSame(1, $observer->startCalls);
		self::assertSame([[2, 2], [2, 4], [1, 5]], $observer->chunks);
		self::assertSame([5], $observer->finishRowsTotals);
	}
}

/**
 * @internal
 */
final class RuntimeRowsReaderStub implements RowsReaderInterface
{
	/**
	 * @param array<int, array<string, mixed>> $rows
	 */
	public function __construct(private readonly array $rows) {}

	public function definitionColumns(): array
	{
		return [
			new ExportColumn('id', 'ID', static fn(array $row): int => (int)$row['id']),
		];
	}

	public function read(): iterable
	{
		yield from $this->rows;
	}
}

/**
 * @internal
 */
final class RuntimeCancellationCheckerStub implements ExportCancellationCheckerInterface
{
	public int $calls = 0;

	public function __construct(private readonly ?int $throwOnCall = null) {}

	public function throwIfCanceled(): void
	{
		$this->calls++;

		if ($this->throwOnCall !== null && $this->calls >= $this->throwOnCall)
		{
			throw new ExportCanceledException('Export canceled.');
		}
	}
}

/**
 * @internal
 */
final class RuntimeRowsObserverStub implements RowsReadObserverInterface
{
	public int $startCalls = 0;
	/** @var array<int, array{int, int}> */
	public array $chunks = [];
	/** @var array<int, int> */
	public array $finishRowsTotals = [];

	public function onStart(): void
	{
		$this->startCalls++;
	}

	public function onRowsRead(int $rowsDelta, int $rowsTotal): void
	{
		$this->chunks[] = [$rowsDelta, $rowsTotal];
	}

	public function onFinish(int $rowsTotal): void
	{
		$this->finishRowsTotals[] = $rowsTotal;
	}
}
