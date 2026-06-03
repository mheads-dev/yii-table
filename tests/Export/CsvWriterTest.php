<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Export;

use Mheads\Yii\Table\Export\Column\ExportColumn;
use Mheads\Yii\Table\Export\RowsReader\RowsReaderInterface;
use Mheads\Yii\Table\Export\Writer\CsvWriter;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function fclose;
use function file_get_contents;
use function fopen;
use function rewind;
use function stream_get_contents;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * @internal
 */
final class CsvWriterTest extends TestCase
{
	private const UTF8_BOM = "\xEF\xBB\xBF";

	public function testWritesHeaderAndRowsToResource(): void
	{
		$writer = new CsvWriter();
		$stream = fopen('php://temp', 'w+b');
		if ($stream === false)
		{
			throw new RuntimeException('Unable to open temp stream.');
		}

		try
		{
			$writer->write(
				new ArrayRowsReader(
					[
						['id' => 1, 'name' => 'Phone'],
						['id' => 2, 'name' => 'Tablet'],
					],
				),
				$stream,
			);

			rewind($stream);
			$content = stream_get_contents($stream);
		}
		finally
		{
			fclose($stream);
		}

		self::assertSame(self::UTF8_BOM . "id,name\n1,Phone\n2,Tablet\n", $content);
	}

	public function testWritesToFilePath(): void
	{
		$writer = new CsvWriter();
		$file = tempnam(sys_get_temp_dir(), 'mheads-yii-table-csv-');
		if ($file === false)
		{
			throw new RuntimeException('Unable to create temp file.');
		}

		try
		{
			$writer->write(
				new ArrayRowsReader(
					[
						['id' => 1, 'name' => 'Phone'],
					],
				),
				$file,
			);

			$content = file_get_contents($file);
		}
		finally
		{
			unlink($file);
		}

		self::assertSame(self::UTF8_BOM . "id,name\n1,Phone\n", $content);
	}

	public function testEscapesSpecialCharacters(): void
	{
		$writer = new CsvWriter();
		$stream = fopen('php://temp', 'w+b');
		if ($stream === false)
		{
			throw new RuntimeException('Unable to open temp stream.');
		}

		try
		{
			$writer->write(
				new ArrayRowsReader(
					[
						['name' => 'Phone, "Pro"'],
					],
				),
				$stream,
			);

			rewind($stream);
			$content = stream_get_contents($stream);
		}
		finally
		{
			fclose($stream);
		}

		self::assertSame(self::UTF8_BOM . "name\n\"Phone, \"\"Pro\"\"\"\n", $content);
	}

	public function testUsesExportColumnTitlesForHeaderWhenAvailable(): void
	{
		$writer = new CsvWriter();
		$stream = fopen('php://temp', 'w+b');
		if ($stream === false)
		{
			throw new RuntimeException('Unable to open temp stream.');
		}

		try
		{
			$writer->write(
				new ExportColumnsAwareArrayRowsReader(
					[
						new ExportColumn('id', 'ID колонка', static fn(mixed $row): mixed => $row),
						new ExportColumn('name', 'Название', static fn(mixed $row): mixed => $row),
					],
					[
						['id' => 1, 'name' => 'Phone'],
					],
				),
				$stream,
			);

			rewind($stream);
			$content = stream_get_contents($stream);
		}
		finally
		{
			fclose($stream);
		}

		self::assertSame(self::UTF8_BOM . "\"ID колонка\",Название\n1,Phone\n", $content);
	}
}

/**
 * @internal
 */
final class ArrayRowsReader implements RowsReaderInterface
{
	/**
	 * @param array<int, array<string, mixed>> $rows
	 */
	public function __construct(private readonly array $rows) {}

	public function definitionColumns(): array
	{
		return [];
	}

	public function read(): iterable
	{
		yield from $this->rows;
	}
}

/**
 * @internal
 */
final class ExportColumnsAwareArrayRowsReader implements RowsReaderInterface
{
	/**
	 * @param array<int, ExportColumn> $columns
	 * @param array<int, array<string, mixed>> $rows
	 */
	public function __construct(
		private readonly array $columns,
		private readonly array $rows,
	) {}

	public function definitionColumns(): array
	{
		return $this->columns;
	}

	public function read(): iterable
	{
		yield from $this->rows;
	}
}
