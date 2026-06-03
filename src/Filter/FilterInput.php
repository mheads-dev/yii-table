<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Filter;

final class FilterInput
{
	/**
	 * @param array<string, mixed> $values
	 */
	public function __construct(
		private readonly array $values = [],
	) {}

	/**
	 * @return array<string, mixed>
	 */
	public function values(): array
	{
		return $this->values;
	}

	public function value(string $key): mixed
	{
		return $this->values[$key] ?? null;
	}
}
