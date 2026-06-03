<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Export\Runtime;

interface RowsReadObserverInterface
{
	public function onStart(): void;

	public function onRowsRead(int $rowsDelta, int $rowsTotal): void;

	public function onFinish(int $rowsTotal): void;
}
