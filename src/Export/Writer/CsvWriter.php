<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Export\Writer;

use Mheads\Yii\Table\Export\RowsReader\RowsReaderInterface;
use Override;
use RuntimeException;

use function fclose;
use function fopen;
use function fputcsv;
use function fwrite;
use function is_resource;
use function sprintf;

final class CsvWriter implements WriterInterface
{
	public function __construct(
		private readonly string $delimiter = ',',
		private readonly string $enclosure = '"',
		private readonly string $escape = '\\',
		private readonly string $endOfLine = "\n",
		private readonly bool $withBom = true,
	) {}

	#[Override]
	public function code(): string
	{
		return 'csv';
	}

	#[Override]
	public function mimeType(): ?string
	{
		return 'text/csv';
	}

	#[Override]
	public function extension(): string
	{
		return 'csv';
	}

	#[Override]
	public function write(RowsReaderInterface $rowsReader, mixed $target): void
	{
		[$stream, $mustClose] = $this->openStream($target);

		try
		{
			if ($this->withBom)
			{
				$this->writeBom($stream);
			}

			$headerLabels = $this->resolveHeader($rowsReader);
			if ($headerLabels !== [])
			{
				$this->writeCsvRow($stream, $headerLabels);
			}

			foreach ($rowsReader->read() as $row)
			{
				if ($headerLabels === [])
				{
					$headerLabels = array_keys($row);
					$this->writeCsvRow($stream, $headerLabels);
				}

				$this->writeCsvRow($stream, $row);
			}
		}
		finally
		{
			if ($mustClose)
			{
				fclose($stream);
			}
		}
	}

	/**
	 * @return array<int, string>
	 */
	private function resolveHeader(RowsReaderInterface $rowsReader): array
	{
		$header = [];
		foreach ($rowsReader->definitionColumns() as $column)
		{
			$header[] = $column->title();
		}
		return $header;
	}

	/**
	 * @param resource $stream
	 * @param array<array-key, mixed> $row
	 */
	private function writeCsvRow(mixed $stream, array $row): void
	{
		/** @psalm-suppress MixedArgumentTypeCoercion */
		if (
			fputcsv(
				$stream,
				$row,
				$this->delimiter,
				$this->enclosure,
				$this->escape,
				$this->endOfLine,
			) === false
		) {
			throw new RuntimeException('Unable to write CSV row.');
		}
	}

	/**
	 * @param resource $stream
	 */
	private function writeBom(mixed $stream): void
	{
		$bom = chr(0xEF) . chr(0xBB) . chr(0xBF);
		if (fwrite($stream, $bom) === false)
		{
			throw new RuntimeException('Unable to write CSV BOM.');
		}
	}

	/**
	 * @return array{0: resource, 1: bool}
	 */
	private function openStream(mixed $target): array
	{
		if (is_resource($target))
		{
			return [$target, false];
		}

		if (!is_string($target) || $target === '')
		{
			throw new RuntimeException('CSV target must be a writable resource or non-empty path string.');
		}

		$stream = fopen($target, 'wb');
		if ($stream === false)
		{
			throw new RuntimeException(sprintf('Unable to open target "%s" for CSV export.', $target));
		}

		return [$stream, true];
	}
}
