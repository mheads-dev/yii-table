<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Export;

use Mheads\Yii\Table\Export\Exception\UnsupportedExportFormatException;
use Mheads\Yii\Table\Export\RowsReader\CancellationAwareRowsReader;
use Mheads\Yii\Table\Export\RowsReader\ObservableRowsReader;
use Mheads\Yii\Table\Export\RowsReader\TimeoutAwareRowsReader;
use Mheads\Yii\Table\Export\Runtime\ExportRuntimeOptions;
use Mheads\Yii\Table\Provider\TableProviderInterface;

final class TableExportService
{
	/**
	 * @param resource|string $target
	 */
	public function run(
		TableProviderInterface $provider,
		string $code,
		mixed $target,
		?ExportRuntimeOptions $runtimeOptions = null,
	): ExportGeneratorInterface {
		$generator = $this->findGenerator($provider, $code);
		if ($generator === null)
		{
			throw new UnsupportedExportFormatException('Unknown export format.');
		}

		$rowsReader = $generator->rowsReader();
		$timeoutSeconds = $runtimeOptions?->disableTimeout === true
			? null
			: ($runtimeOptions?->timeoutSecondsOverride ?? $generator->timeoutSeconds());
		if ($timeoutSeconds !== null)
		{
			$rowsReader = new TimeoutAwareRowsReader(
				$rowsReader,
				$timeoutSeconds,
				$runtimeOptions?->timeoutCheckEachRows ?? 10000,
			);
		}

		$cancellationChecker = $runtimeOptions?->cancellationChecker;
		if ($cancellationChecker !== null)
		{
			$rowsReader = new CancellationAwareRowsReader(
				$rowsReader,
				$cancellationChecker,
				$runtimeOptions?->cancellationCheckEachRows ?? 10000,
			);
		}
		$observer = $runtimeOptions?->rowsReadObserver;
		if ($observer !== null)
		{
			$rowsReader = new ObservableRowsReader(
				$rowsReader,
				$observer,
				$runtimeOptions?->observerNotifyEachRows ?? 1000,
			);
		}

		$generator->writer()->write(
			$rowsReader,
			$target,
		);

		return $generator;
	}

	private function findGenerator(TableProviderInterface $provider, string $code): ?ExportGeneratorInterface
	{
		foreach ($provider->exportGenerators() as $generator)
		{
			if ($generator->code() === $code)
			{
				return $generator;
			}
		}

		return null;
	}
}
