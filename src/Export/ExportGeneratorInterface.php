<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Export;

use Mheads\Yii\Table\Export\RowsReader\RowsReaderInterface;
use Mheads\Yii\Table\Export\Writer\WriterInterface;

interface ExportGeneratorInterface
{
	public function code(): string;

	public function writer(): WriterInterface;

	public function rowsReader(): RowsReaderInterface;

	public function timeoutSeconds(): ?int;

	public function fileName(): ?string;
}
