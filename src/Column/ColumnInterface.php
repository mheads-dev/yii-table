<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Column;

use Mheads\Yii\Table\Filter\FilterInterface;
use Mheads\Yii\Table\Sort\SortDefinition;

interface ColumnInterface
{
	public function key(): string;

	public function title(): string;

	public function read(mixed $entity): mixed;

	public function isId(): bool;

	public function isHidden(): bool;

	public function sort(): ?SortDefinition;

	public function filter(): ?FilterInterface;

	/**
	 * @return FilterInterface[]
	 */
	public function extraFilters(): array;
}
