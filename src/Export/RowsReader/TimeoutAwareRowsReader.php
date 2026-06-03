<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Export\RowsReader;

use Mheads\Yii\Table\Export\Exception\ExportTimeoutException;
use Override;

use function microtime;

final class TimeoutAwareRowsReader implements RowsReaderInterface
{
	private readonly float $startedAt;

	public function __construct(
		private readonly RowsReaderInterface $innerReader,
		private readonly int $timeoutSeconds,
		private readonly int $checkEachRows = 10000,
	) {
		$this->startedAt = microtime(true);
	}

	#[Override]
	public function definitionColumns(): array
	{
		return $this->innerReader->definitionColumns();
	}

	#[Override]
	public function read(): iterable
	{
		$this->throwIfTimedOut();

		$readCount = 0;
		foreach ($this->innerReader->read() as $row)
		{
			if ($readCount === 0 || $readCount >= $this->checkEachRows)
			{
				$readCount = 0;
				$this->throwIfTimedOut();
			}

			$readCount++;
			yield $row;
		}

		$this->throwIfTimedOut();
	}

	private function throwIfTimedOut(): void
	{
		if ((microtime(true) - $this->startedAt) >= $this->timeoutSeconds)
		{
			throw new ExportTimeoutException('Export timeout exceeded.');
		}
	}
}
