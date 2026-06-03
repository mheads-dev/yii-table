<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Export\BatchStrategy;

use Override;
use Yiisoft\Data\Reader\DataReaderInterface;
use Yiisoft\Data\Reader\ReadableDataInterface;

/**
 * Generic offset/limit batching strategy.
 *
 * Works with DataReaderInterface implementations.
 */
final class OffsetLimitBatchReadStrategy implements BatchStrategyInterface
{
	/**
	 * @param positive-int $batchSize
	 */
	public function __construct(
		private readonly int $batchSize,
	) {}

	#[Override]
	public function canRead(ReadableDataInterface $reader): bool
	{
		return $reader instanceof DataReaderInterface;
	}

	#[Override]
	public function readBatched(ReadableDataInterface $reader): iterable
	{
		if (!$reader instanceof DataReaderInterface)
		{
			yield from $reader->read();
			return;
		}

		$sourceLimit = $reader->getLimit();
		$offset = 0;

		while (true)
		{
			$limit = $this->batchSize;
			if ($sourceLimit !== null)
			{
				$remaining = $sourceLimit - $offset;
				if ($remaining <= 0)
				{
					return;
				}

				$limit = min($this->batchSize, $remaining);
			}

			$chunkReader = $reader
				->withOffset($offset)
				->withLimit($limit);

			$chunkCount = 0;
			foreach ($chunkReader->read() as $entity)
			{
				yield $entity;
				$chunkCount++;
			}

			if ($chunkCount === 0)
			{
				return;
			}

			$offset += $chunkCount;

			if ($chunkCount < $this->batchSize)
			{
				return;
			}
		}
	}
}
