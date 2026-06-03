<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Export\BatchStrategy;

use Yiisoft\Data\Reader\ReadableDataInterface;

interface BatchStrategyInterface
{
	public function canRead(ReadableDataInterface $reader): bool;

	/**
	 * Reads raw entities in batches.
	 * Mapping to export row shape is handled by TableExportRowsReader.
	 *
	 * @return iterable<array-key, array|object>
	 */
	public function readBatched(ReadableDataInterface $reader): iterable;
}
