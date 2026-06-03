<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Export\RowsReader;

use Mheads\Yii\Table\Export\Runtime\RowsReadObserverInterface;
use Override;

final class ObservableRowsReader implements RowsReaderInterface
{
	public function __construct(
		private readonly RowsReaderInterface $innerReader,
		private readonly RowsReadObserverInterface $observer,
		private readonly int $notifyEachRows = 1000,
	) {}

	#[Override]
	public function definitionColumns(): array
	{
		return $this->innerReader->definitionColumns();
	}

	#[Override]
	public function read(): iterable
	{
		$this->observer->onStart();

		$total = 0;
		$delta = 0;
		foreach ($this->innerReader->read() as $row)
		{
			$total++;
			$delta++;
			yield $row;

			if ($delta >= $this->notifyEachRows)
			{
				$this->observer->onRowsRead($delta, $total);
				$delta = 0;
			}
		}

		if ($delta > 0)
		{
			$this->observer->onRowsRead($delta, $total);
		}
		$this->observer->onFinish($total);
	}
}
