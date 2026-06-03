<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Export\RowsReader;

use Mheads\Yii\Table\Export\Runtime\ExportCancellationCheckerInterface;
use Override;

final class CancellationAwareRowsReader implements RowsReaderInterface
{
	public function __construct(
		private readonly RowsReaderInterface $innerReader,
		private readonly ExportCancellationCheckerInterface $cancellationChecker,
		private readonly int $checkEachRows = 10000,
	) {}

	#[Override]
	public function definitionColumns(): array
	{
		return $this->innerReader->definitionColumns();
	}

	#[Override]
	public function read(): iterable
	{
		$readCount = 0;
		foreach ($this->innerReader->read() as $row)
		{
			if ($readCount === 0 || $readCount >= $this->checkEachRows)
			{
				$readCount = 0;
				$this->cancellationChecker->throwIfCanceled();
			}

			$readCount++;
			yield $row;
		}

		$this->cancellationChecker->throwIfCanceled();
	}
}
