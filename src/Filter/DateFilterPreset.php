<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Filter;

use Closure;
use DateTimeImmutable;

/**
 * Declarative preset for {@see DateFilter}.
 *
	 * Preset defines:
	 * - stable select key exposed to client (`select`);
	 * - stable translation message ID (`messageId`) for filter schema title;
	 * - human-readable fallback title (`title`) for environments without translator;
	 * - optional normalization of raw input value (`valueNormalizer`);
	 * - optional resolver that converts preset input into date range (`rangeResolver`).
	 */
final readonly class DateFilterPreset
{
	/**
	 * @param (callable(array): ?array)|null $valueNormalizer
	 * @param (callable(array, DateTimeImmutable): array{0: ?DateTimeImmutable, 1: ?DateTimeImmutable})|null $rangeResolver
	 */
	public function __construct(
		public string $select,
		public string $messageId,
		public string $title,
		?callable     $valueNormalizer = null,
		?callable     $rangeResolver = null,
	) {
		$this->valueNormalizer = $valueNormalizer !== null ? $valueNormalizer(...) : null;
		$this->rangeResolver = $rangeResolver !== null ? $rangeResolver(...) : null;
	}

	/**
	 * @var (Closure(array): ?array)|null
	 */
	private ?Closure $valueNormalizer;

	/**
	 * @var (Closure(array, DateTimeImmutable): array{0: ?DateTimeImmutable, 1: ?DateTimeImmutable})|null
	 */
	private ?Closure $rangeResolver;

	/**
	 * @return array<array-key, mixed>|null
	 */
	public function normalize(array $value): ?array
	{
		if ($this->valueNormalizer === null)
		{
			return ['select' => $this->select];
		}

		$result = ($this->valueNormalizer)($value);
		return is_array($result) ? $result : null;
	}

	/**
	 * @return array{0: ?DateTimeImmutable, 1: ?DateTimeImmutable}
	 */
	public function resolveRange(array $value, DateTimeImmutable $now): array
	{
		if ($this->rangeResolver === null)
		{
			return [null, null];
		}

		return ($this->rangeResolver)($value, $now);
	}
}
