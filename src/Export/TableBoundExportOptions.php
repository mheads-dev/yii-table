<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Export;

use Mheads\Yii\Table\Export\BatchStrategy\BatchStrategyInterface;
use Mheads\Yii\Table\Export\Column\ExportColumn;
use Mheads\Yii\Table\Export\Column\ExportColumnMode;

final class TableBoundExportOptions
{
	/**
	 * @param array<int, ExportColumn> $customColumns
	 */
	public function __construct(
		public readonly ExportColumnMode $columnsMode = ExportColumnMode::TABLE_ONLY,
		public readonly array $customColumns = [],
		public readonly ?int $timeoutSeconds = null,
		public readonly ?string $fileName = null,
		public readonly ?BatchStrategyInterface $batchStrategy = null,
	) {}
}

