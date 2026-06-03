<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Column;

use Closure;
use Mheads\Yii\Table\Filter\FilterInterface;
use Mheads\Yii\Table\Sort\SortDefinition;
use Override;

final class Column implements ColumnInterface
{
	private Closure $reader;

	/**
	 * @param callable(mixed):mixed $reader
	 * @param FilterInterface[] $extraFilters
	 */
	public function __construct(
		private readonly string $key,
		private readonly string $title,
		callable $reader,
		private readonly bool $isId = false,
		private readonly bool $isHidden = false,
		private readonly ?SortDefinition $sort = null,
		private readonly ?FilterInterface $filter = null,
		private readonly array $extraFilters = [],
	) {
		$this->reader = $reader(...);
	}

	#[Override]
	public function key(): string
	{
		return $this->key;
	}

	#[Override]
	public function title(): string
	{
		return $this->title;
	}

	#[Override]
	public function read(mixed $entity): mixed
	{
		return ($this->reader)($entity);
	}

	#[Override]
	public function isId(): bool
	{
		return $this->isId;
	}

	#[Override]
	public function isHidden(): bool
	{
		return $this->isHidden;
	}

	#[Override]
	public function sort(): ?SortDefinition
	{
		return $this->sort;
	}

	#[Override]
	public function filter(): ?FilterInterface
	{
		return $this->filter;
	}

	#[Override]
	public function extraFilters(): array
	{
		return $this->extraFilters;
	}
}
