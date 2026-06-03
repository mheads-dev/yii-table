<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Export\Writer;

use Mheads\Yii\Table\Export\Column\ExportColumn;
use Mheads\Yii\Table\Export\RowsReader\RowsReaderInterface;
use Override;
use RuntimeException;

use function class_exists;
use function fclose;
use function feof;
use function fopen;
use function fread;
use function fwrite;
use function is_resource;
use function sprintf;
use function str_starts_with;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * @psalm-suppress MixedMethodCall
 */
final class XlsxWriter implements WriterInterface
{
	public function __construct(
		private readonly string $sheetName = 'Sheet',
		private readonly int $maxRowsCountOnSheet = 1000000,
	) {
		if ($this->maxRowsCountOnSheet < 2)
		{
			throw new RuntimeException('XLSX maxRowsCountOnSheet must be greater than 1.');
		}
	}

	#[Override]
	public function code(): string
	{
		return 'xlsx';
	}

	#[Override]
	public function mimeType(): ?string
	{
		return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
	}

	#[Override]
	public function extension(): string
	{
		return 'xlsx';
	}

	#[Override]
	public function write(RowsReaderInterface $rowsReader, mixed $target): void
	{
		$requiresCopyToTarget = $this->requiresCopyToTarget($target);
		$fileName = $this->resolveTargetFileName($target, $requiresCopyToTarget);

		$excel = $this->createExcel();
		$excel->constMemory($fileName, $this->sheetName);

		$boldFormat = $this->createBoldFormat($excel);
		$columns = $rowsReader->definitionColumns();
		$headerLabels = $this->resolveHeaderLabels($columns);
		if ($headerLabels !== [])
		{
			$this->initHeader($excel, $boldFormat, $headerLabels);
		}

		$sheetIndex = 1;
		$rowOnSheet = $headerLabels === [] ? 0 : 1;

		foreach ($rowsReader->read() as $row)
		{
			if ($headerLabels === [])
			{
				$headerLabels = array_keys($row);
				$this->initHeader($excel, $boldFormat, $headerLabels);
				$rowOnSheet = 1;
			}

			$rowOnSheet++;
			$excel->data([$row]);

			if ($rowOnSheet >= $this->maxRowsCountOnSheet)
			{
				$rowOnSheet = 1;
				$sheetIndex++;
				$excel->addSheet($this->sheetName . ' ' . $sheetIndex);
				if ($headerLabels !== [])
				{
					$this->initHeader($excel, $boldFormat, $headerLabels);
				}
			}

		}

		$resultFile = $excel->output();
		if (!is_string($resultFile) || $resultFile === '')
		{
			throw new RuntimeException('Vtiful returned invalid output file path.');
		}

		if ($requiresCopyToTarget)
		{
			$this->copyToTarget($resultFile, $target);
			@unlink($resultFile);
		}
	}

	/**
	 */
	private function resolveTargetFileName(mixed $target, bool $requiresCopyToTarget): string
	{
		if ($requiresCopyToTarget)
		{
			$temp = tempnam(sys_get_temp_dir(), 'table_provider_xlsx_');
			if ($temp === false)
			{
				throw new RuntimeException('Unable to create temporary XLSX file.');
			}
			return $temp;
		}

		if (!is_string($target) || $target === '')
		{
			throw new RuntimeException('XLSX target must be non-empty path, php:// stream, or writable resource.');
		}
		return $target;
	}

	private function requiresCopyToTarget(mixed $target): bool
	{
		if (is_resource($target))
		{
			return true;
		}

		return is_string($target) && str_starts_with($target, 'php://');
	}

	/**
	 */
	private function createExcel(): object
	{
		$excelClass = 'Vtiful\\Kernel\\Excel';
		if (!class_exists($excelClass))
		{
			throw new RuntimeException('ext-xlswriter is not installed: class Vtiful\\Kernel\\Excel not found.');
		}

		/** @var object $excel */
		$excel = new $excelClass(['path' => '']);
		return $excel;
	}

	/**
	 */
	private function createBoldFormat(object $excel): mixed
	{
		return $this->createFormatResource($excel, true);
	}

	/**
	 * @param array<int, ExportColumn> $columns
	 * @return array<int, string|null>
	 */
	private function resolveHeaderLabels(array $columns): array
	{
		if ($columns === [])
		{
			return [];
		}

		$headers = [];
		foreach ($columns as $column)
		{
			$headers[] = $column->title() !== '' ? $column->title() : null;
		}

		return $headers;
	}

	/**
	 * @param array<int, string|null> $labels
	 */
	private function initHeader(object $excel, mixed $boldFormat, array $labels): void
	{
		$excel->header($labels);
		$excel->freezePanes(1, 0)->setRow('1', 15, $boldFormat);
	}

	/**
	 */
	private function createFormatResource(object $excel, bool $bold): mixed
	{
		$formatClass = 'Vtiful\\Kernel\\Format';
		if (!class_exists($formatClass))
		{
			throw new RuntimeException('ext-xlswriter is not installed: class Vtiful\\Kernel\\Format not found.');
		}

		$format = new $formatClass($excel->getHandle());
		if ($bold)
		{
			return $format->bold()->toResource();
		}
		return $format->wrap()->toResource();
	}

	private function copyToTarget(string $fromFile, mixed $target): void
	{
		$fr = fopen($fromFile, 'rb');
		if (!is_resource($fr))
		{
			throw new RuntimeException(sprintf('Unable to open temporary XLSX result file "%s".', $fromFile));
		}

		$mustCloseTarget = false;
		if (is_resource($target))
		{
			$fw = $target;
		}
		elseif (is_string($target) && $target !== '')
		{
			$fw = fopen($target, 'wb');
			$mustCloseTarget = true;
		}
		else
		{
			fclose($fr);
			throw new RuntimeException('XLSX target must be writable resource, non-empty path, or php:// stream.');
		}

		if (!is_resource($fw))
		{
			fclose($fr);
			throw new RuntimeException(sprintf('Unable to open output target "%s".', (string)$target));
		}

		try
		{
			while (!feof($fr))
			{
				$chunk = fread($fr, 8192);
				if ($chunk === false)
				{
					throw new RuntimeException('Unable to read temporary XLSX result file.');
				}

				if ($chunk === '')
				{
					continue;
				}

				if (fwrite($fw, $chunk) === false)
				{
					throw new RuntimeException('Unable to write XLSX output stream.');
				}
			}
		}
		finally
		{
			fclose($fr);
			if ($mustCloseTarget)
			{
				fclose($fw);
			}
		}
	}
}
