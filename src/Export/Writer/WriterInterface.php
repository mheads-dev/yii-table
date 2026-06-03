<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Export\Writer;

use Mheads\Yii\Table\Export\RowsReader\RowsReaderInterface;

interface WriterInterface
{
	public function code(): string;

	public function mimeType(): ?string;

	public function extension(): string;

	/**
	 * @param resource|string $target
	 */
	public function write(RowsReaderInterface $rowsReader, mixed $target): void;
}
