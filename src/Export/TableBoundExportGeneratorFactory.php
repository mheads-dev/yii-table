<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Export;

use Mheads\Yii\Table\Export\Writer\CsvWriter;
use Mheads\Yii\Table\Export\Writer\WriterInterface;
use Mheads\Yii\Table\Export\Writer\XlsxWriter;
use Mheads\Yii\Table\Provider\TableProviderInterface;

final class TableBoundExportGeneratorFactory
{
	public function csv(
		TableProviderInterface $table,
		?TableBoundExportOptions $options = null,
		?CsvWriter $writer = null,
	): TableBoundExportGenerator {
		return $this->create(
			table: $table,
			writer: $writer ?? new CsvWriter(),
			options: $options,
		);
	}

	public function xlsx(
		TableProviderInterface $table,
		?TableBoundExportOptions $options = null,
		string $sheetName = 'Sheet',
		?XlsxWriter $writer = null,
	): TableBoundExportGenerator {
		return $this->create(
			table: $table,
			writer: $writer ?? new XlsxWriter(sheetName: $sheetName),
			options: $options,
		);
	}

	public function create(
		TableProviderInterface $table,
		WriterInterface $writer,
		?TableBoundExportOptions $options = null,
	): TableBoundExportGenerator {
		return new TableBoundExportGenerator(
			table: $table,
			writer: $writer,
			options: $options ?? new TableBoundExportOptions(),
		);
	}
}
