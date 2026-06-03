<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Export\Column;

enum ExportColumnMode: string
{
	case TABLE_ONLY = 'table_only';
	case CUSTOM_ONLY = 'custom_only';
	case MERGE = 'merge';
}
