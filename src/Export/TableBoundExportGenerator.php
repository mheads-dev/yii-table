<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Export;

use Mheads\Yii\Table\Export\Column\ExportColumnsResolver;
use Mheads\Yii\Table\Export\RowsReader\PaginatorAllItemsDataReader;
use Mheads\Yii\Table\Export\RowsReader\RowsReaderInterface;
use Mheads\Yii\Table\Export\RowsReader\TableExportRowsReader;
use Mheads\Yii\Table\Export\Writer\WriterInterface;
use Mheads\Yii\Table\Provider\TableProviderInterface;
use Override;
use Yiisoft\Data\Paginator\PaginatorInterface;
use Yiisoft\Data\Reader\ReadableDataInterface;

final class TableBoundExportGenerator implements ExportGeneratorInterface
{
	public function __construct(
		private readonly TableProviderInterface $table,
		private readonly WriterInterface $writer,
		private readonly TableBoundExportOptions $options = new TableBoundExportOptions(),
	) {
		$this->columnsResolver = new ExportColumnsResolver();
	}

	private readonly ExportColumnsResolver $columnsResolver;

	#[Override]
	public function code(): string
	{
		return $this->writer->code();
	}

	#[Override]
	public function writer(): WriterInterface
	{
		return $this->writer;
	}

	#[Override]
	public function rowsReader(): RowsReaderInterface
	{
		$columns = $this->columnsResolver->resolve(
			array_values($this->table->columns()),
			mode: $this->options->columnsMode,
			customColumns: $this->options->customColumns,
		);

		$reader = $this->table->dataReader(allowAutoWrap: false);
		if ($reader instanceof PaginatorInterface)
		{
			$reader = new PaginatorAllItemsDataReader($reader);
		}
		/** @var ReadableDataInterface<array-key, array|object> $reader */

		return new TableExportRowsReader(
			$reader,
			$columns,
			$this->options->batchStrategy,
		);
	}

	#[Override]
	public function timeoutSeconds(): ?int
	{
		return $this->options->timeoutSeconds;
	}

	#[Override]
	public function fileName(): ?string
	{
		return $this->options->fileName;
	}
}
