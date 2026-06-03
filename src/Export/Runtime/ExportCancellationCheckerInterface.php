<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Export\Runtime;

interface ExportCancellationCheckerInterface
{
	public function throwIfCanceled(): void;
}
