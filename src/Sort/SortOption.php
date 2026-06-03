<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Sort;

final class SortOption
{
	public function __construct(
		private readonly string         $value,
		private readonly string         $title,
		private readonly SortDefinition $definition,
		private readonly bool           $isDefault = false,
		private readonly bool           $isDisabled = false,
	) {}

	public function value(): string
	{
		return $this->value;
	}

	public function title(): string
	{
		return $this->title;
	}

	public function definition(): SortDefinition
	{
		return $this->definition;
	}

	public function isDefault(): bool
	{
		return $this->isDefault;
	}

	public function isDisabled(): bool
	{
		return $this->isDisabled;
	}
}
