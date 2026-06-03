<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Export;

use Mheads\Yii\Table\Export\Column\ExportColumn;
use Mheads\Yii\Table\Export\RowsReader\RowsReaderInterface;
use Mheads\Yii\Table\Export\Writer\XlsxWriter;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function class_exists;
use function fclose;
use function file_get_contents;
use function filesize;
use function fopen;
use function rewind;
use function stream_get_contents;
use function substr;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * @internal
 */
final class XlsxWriterTest extends TestCase
{
	public function testWritesXlsxFile(): void
	{
		if (!class_exists('Vtiful\\Kernel\\Excel'))
		{
			self::markTestSkipped('ext-xlswriter is not installed.');
		}

		$file = tempnam(sys_get_temp_dir(), 'mheads-yii-xlsx-');
		if ($file === false)
		{
			throw new RuntimeException('Unable to create temp file.');
		}
		$xlsxFile = $file . '.xlsx';
		@unlink($xlsxFile);

		try
		{
			$writer = new XlsxWriter();
			$writer->write(
				new XlsxRowsReaderStub(
					[
						new ExportColumn('name', 'Name', static fn(mixed $entity): mixed => $entity),
						new ExportColumn('id', 'ID', static fn(mixed $entity): mixed => $entity),
						new ExportColumn('category', 'Category', static fn(mixed $entity): mixed => $entity),
					],
					[
						['id' => 1, 'name' => 'Phone', 'category' => 'mobile'],
						['id' => 2, 'name' => 'Tablet', 'category' => 'mobile'],
						['id' => 3, 'name' => 'Laptop', 'category' => 'computer'],
						['id' => 4, 'name' => 'Monitor', 'category' => 'computer'],
						['id' => 5, 'name' => 'Keyboard', 'category' => 'accessory'],
						['id' => 6, 'name' => 'Mouse', 'category' => 'accessory'],
						['id' => 7, 'name' => 'Headphones', 'category' => 'accessory'],
						['id' => 8, 'name' => 'USB-C Cable', 'category' => 'accessory'],
						['id' => 9, 'name' => 'Dock Station', 'category' => 'accessory'],
						['id' => 10, 'name' => 'Webcam 4K', 'category' => 'accessory'],
					],
				),
				$xlsxFile,
			);

			self::assertGreaterThan(0, filesize($xlsxFile));
			$content = file_get_contents($xlsxFile);
			self::assertNotFalse($content);
			self::assertSame('PK', substr($content, 0, 2));
		}
		finally
		{
			@unlink($file);
			@unlink($xlsxFile);
		}
	}

	public function testWritesXlsxToResourceTarget(): void
	{
		if (!class_exists('Vtiful\\Kernel\\Excel'))
		{
			self::markTestSkipped('ext-xlswriter is not installed.');
		}

		$stream = fopen('php://temp', 'w+b');
		if ($stream === false)
		{
			throw new RuntimeException('Unable to open temp stream.');
		}

		try
		{
			$writer = new XlsxWriter();
			$writer->write(
				new XlsxRowsReaderStub(
					[
						new ExportColumn('id', 'ID', static fn(mixed $entity): mixed => $entity),
					],
					[
						['id' => 1],
					],
				),
				$stream,
			);

			rewind($stream);
			$content = stream_get_contents($stream);
			self::assertNotFalse($content);
			self::assertSame('PK', substr($content, 0, 2));
		}
		finally
		{
			fclose($stream);
		}
	}
}

/**
 * @internal
 */
final class XlsxRowsReaderStub implements RowsReaderInterface
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
