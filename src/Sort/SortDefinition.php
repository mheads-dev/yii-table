<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Sort;

final class SortDefinition
{
	public static function byField(string $field, ?int $defaultDirection = null): self
	{
		return new self(
			[$field => SORT_ASC],
			[$field => SORT_DESC],
			$defaultDirection,
		);
	}

	/**
	 * @param array<string, int> $order
	 */
	public static function fixedOrder(array $order, ?int $defaultDirection = null): self
	{
		return new self($order, $order, $defaultDirection);
	}

	/**
	 * @param array<string, int> $asc
	 * @param array<string, int> $desc
	 */
	public function __construct(
		private readonly array $asc,
		private readonly array $desc,
		private readonly ?int $defaultDirection = null,
	) {}

	/**
	 * @return array<string, int>
	 */
	public function asc(): array
	{
		return $this->asc;
	}

	/**
	 * @return array<string, int>
	 */
	public function desc(): array
	{
		return $this->desc;
	}

	public function defaultDirection(): ?int
	{
		return $this->defaultDirection;
	}
}
