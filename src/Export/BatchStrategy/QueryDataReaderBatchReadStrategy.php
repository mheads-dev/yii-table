<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Export\BatchStrategy;

use InvalidArgumentException;
use Override;
use Yiisoft\Data\Reader\ReadableDataInterface;

use function get_debug_type;
use function interface_exists;
use function is_a;
use function method_exists;

/**
 * Optional adapter for yiisoft/data-db query readers.
 *
 * Works with readers compatible with
 * \Yiisoft\Data\Db\QueryDataReaderInterface.
 */
final class QueryDataReaderBatchReadStrategy implements BatchStrategyInterface
{
	private const TARGET_INTERFACE = 'Yiisoft\Data\Db\QueryDataReaderInterface';

	/**
	 * @param positive-int $batchSize
	 */
	public function __construct(
		private readonly int $batchSize,
	) {}

	#[Override]
	public function canRead(ReadableDataInterface $reader): bool
	{
		/** @psalm-suppress ArgumentTypeCoercion */
		return interface_exists(self::TARGET_INTERFACE) && is_a($reader, self::TARGET_INTERFACE);
	}

	#[Override]
	public function readBatched(ReadableDataInterface $reader): iterable
	{
		if (!$this->canRead($reader))
		{
			throw new InvalidArgumentException(
				'Reader is not compatible with QueryDataReaderInterface, got: ' . get_debug_type($reader) . '.',
			);
		}

		if (!method_exists($reader, 'withBatchSize'))
		{
			throw new InvalidArgumentException(
				'Reader does not support method withBatchSize(), got: ' . get_debug_type($reader) . '.',
			);
		}

		/** @psalm-suppress MixedMethodCall */
		$batchReader = $reader->withBatchSize($this->batchSize);
		/** @psalm-suppress MixedMethodCall */
		/** @var iterable<array-key, array|object> $rows */
		$rows = $batchReader->read();
		yield from $rows;
	}
}
