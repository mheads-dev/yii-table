<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Sort;

final class SortDsl
{
	public static function column(string $field, ?int $defaultDirection = null): SortDefinition
	{
		return SortDefinition::byField($field, $defaultDirection);
	}

	public static function option(
		string         $value,
		string         $title,
		SortDefinition $definition,
		bool           $isDefault = false,
		bool           $isDisabled = false,
	): SortOption {
		return new SortOption($value, $title, $definition, $isDefault, $isDisabled);
	}

	public static function optionAsc(
		string $value,
		string $title,
		string $field,
		bool   $isDefault = false,
		bool   $isDisabled = false,
	): SortOption {
		return self::option($value, $title, SortDefinition::fixedOrder([$field => SORT_ASC]), $isDefault, $isDisabled);
	}

	public static function optionDesc(
		string $value,
		string $title,
		string $field,
		bool   $isDefault = false,
		bool   $isDisabled = false,
	): SortOption {
		return self::option($value, $title, SortDefinition::fixedOrder([$field => SORT_DESC]), $isDefault, $isDisabled);
	}

	/**
	 * @param array<string, int> $order
	 */
	public static function optionFixed(
		string $value,
		string $title,
		array  $order,
		bool   $isDefault = false,
		bool   $isDisabled = false,
	): SortOption {
		return self::option($value, $title, SortDefinition::fixedOrder($order), $isDefault, $isDisabled);
	}
}
