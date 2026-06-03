<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Filter;

use Yiisoft\Data\Reader\FilterInterface as DataFilterInterface;

interface FilterInterface
{
	public function key(): string;

	public function type(): string;

	public function setColumnKey(?string $columnKey): void;

	public function getColumnKey(): ?string;

	public function buildDataFilter(FilterInput $input): ?DataFilterInterface;
}
