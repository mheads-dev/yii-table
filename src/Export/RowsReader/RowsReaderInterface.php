<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Export\RowsReader;

use Mheads\Yii\Table\Export\Column\ExportColumn;

interface RowsReaderInterface
{
	/**
	 * @return array<int, ExportColumn>
	 */
	public function definitionColumns(): array;

	/**
	 * @return iterable<array<string, mixed>>
	 */
	public function read(): iterable;
}
