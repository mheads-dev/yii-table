<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Export\RowsReader;

use Mheads\Yii\Table\Export\BatchStrategy\BatchStrategyInterface;
use Mheads\Yii\Table\Export\Column\ExportColumn;
use Override;
use Yiisoft\Data\Reader\DataReaderInterface;
use Yiisoft\Data\Reader\ReadableDataInterface;

final class TableExportRowsReader implements RowsReaderInterface
{
	/**
	 * @param array<int, ExportColumn> $columns
	 */
	public function __construct(
		private readonly ReadableDataInterface $reader,
		private readonly array $columns,
		private readonly ?BatchStrategyInterface $batchStrategy = null,
	) {}

	#[Override]
	public function read(): iterable
	{
		$reader = $this->reader;
		if ($reader instanceof DataReaderInterface)
		{
			$reader = $reader->withOffset(0);
		}

		if ($this->batchStrategy !== null && $this->batchStrategy->canRead($reader))
		{
			yield from $this->mapRows($this->batchStrategy->readBatched($reader));
			return;
		}

		yield from $this->mapRows($reader->read());
	}

	#[Override]
	public function definitionColumns(): array
	{
		return $this->columns;
	}

	/**
	 * @param iterable<array-key, array|object> $rows
	 * @return iterable<array<string, mixed>>
	 */
	private function mapRows(iterable $rows): iterable
	{
		foreach ($rows as $entity)
		{
			$row = [];
			foreach ($this->columns as $column)
			{
				$row[$column->key()] = $column->read($entity);
			}

			yield $row;
		}
	}

}
