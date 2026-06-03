<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Filter;

use Closure;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use Mheads\Yii\Table\I18n\LocalizableInterface;
use Mheads\Yii\Table\I18n\LocalizableTrait;
use Mheads\Yii\Table\I18n\TableMessage;
use Override;
use Yiisoft\Data\Reader\Filter\AndX;
use Yiisoft\Data\Reader\Filter\Equals;
use Yiisoft\Data\Reader\Filter\GreaterThanOrEqual;
use Yiisoft\Data\Reader\Filter\LessThanOrEqual;
use Yiisoft\Data\Reader\Filter\None;
use Yiisoft\Data\Reader\Filter\OrX;
use Yiisoft\Data\Reader\FilterInterface as DataFilterInterface;

use function array_filter;
use function array_values;
use function count;
use function in_array;
use function is_array;
use function is_scalar;
use function preg_match;
use function strlen;

/** @psalm-type DateItem = array<string, mixed> */
final class DateFilter extends AbstractPayloadFilter implements LocalizableInterface
{
	use LocalizableTrait;

	public const string TYPE_DATE = 'date';
	public const string TYPE_DATE_TIME = 'date_time';
	public const string TYPE_UNIX = 'unix';

	public const string SELECT_DATE = 'date';
	public const string SELECT_RANGE_DATE = 'range_date';
	public const string SELECT_TODAY = 'today';
	public const string SELECT_YESTERDAY = 'yesterday';
	public const string SELECT_TOMORROW = 'tomorrow';
	public const string SELECT_CURRENT_WEEK = 'current_week';
	public const string SELECT_PREVIOUS_WEEK = 'previous_week';
	public const string SELECT_NEXT_WEEK = 'next_week';

	private readonly Closure $nowProvider;
	/** @var array<string, DateFilterPreset> */
	private array $customPresets = [];

	public function __construct(
		string $key,
		string $title,
		private readonly string $field,
		private readonly string $valueType = self::TYPE_DATE,
		private readonly bool $isMultiple = false,
		?callable $nowProvider = null,
		?string $caption = null,
	) {
		parent::__construct($key, $title, $caption);
		if (!in_array($this->valueType, [self::TYPE_DATE, self::TYPE_DATE_TIME, self::TYPE_UNIX], true))
		{
			throw new InvalidArgumentException('Invalid valueType for DateFilter.');
		}
		$this->nowProvider = $nowProvider !== null
			? $nowProvider(...)
			: static fn(): DateTimeImmutable => new DateTimeImmutable();
	}

	#[Override]
	public function type(): string
	{
		return 'date';
	}

	#[Override]
	public function buildDataFilter(FilterInput $input): ?DataFilterInterface
	{
		/** @var list<array<string, mixed>>|null $values */
		$values = $this->getValues($input);
		if ($values === null)
		{
			return null;
		}

		$filters = [];
		foreach ($values as $value)
		{
			$filter = $this->buildOneFilter($value);
			if ($filter !== null)
			{
				$filters[] = $filter;
			}
		}

		if ($filters === [])
		{
			return new None();
		}

		if (count($filters) === 1)
		{
			return $filters[0];
		}

		return new OrX(...$filters);
	}

	#[Override]
	public function toArray(?FilterInput $input = null): array
	{
		$result = parent::toArray($input);
		$result['isMultiple'] = $this->isMultiple;
		$result['select'] = $this->getSelect();
		return $result;
	}

	#[Override]
	protected function getValues(FilterInput $input): ?array
	{
		$values = $this->normalizeValues($input);
		return $values === [] ? null : $values;
	}

	/**
	 * @return list<array{select: string, title: string}>
	 */
	private function getSelect(): array
	{
		$selects = [
			[
				'select' => self::SELECT_DATE,
				'title'  => $this->translateOrSelf(TableMessage::DATE_EXACT, 'Exact date'),
			],
			[
				'select' => self::SELECT_RANGE_DATE,
				'title'  => $this->translateOrSelf(TableMessage::DATE_RANGE, 'Date range'),
			],
		];

		foreach ($this->customPresets as $preset)
		{
			$selects[] = [
				'select' => $preset->select,
				'title'  => $this->translateOrSelf($preset->messageId, $preset->title),
			];
		}

		return $selects;
	}

	private function normalizeValues(FilterInput $input): array
	{
		$raw = $input->value($this->key());

		if ($this->isFilledScalar($raw))
		{
			$values = [['select' => self::SELECT_DATE, 'date' => (string)$raw]];
		}
		elseif (is_array($raw) && (isset($raw['select']) || isset($raw['date']) || isset($raw['from']) || isset($raw['to'])))
		{
			$values = [$raw];
		}
		elseif (is_array($raw))
		{
			$values = $raw;
		}
		else
		{
			return [];
		}

		$normalized = [];
		foreach ($values as $item)
		{
			if ($this->isFilledScalar($item))
			{
				$normalized[] = ['select' => self::SELECT_DATE, 'date' => (string)$item];
				continue;
			}

			if (!is_array($item))
			{
				continue;
			}

			$select = $item['select'] ?? null;
			if (!is_string($select) && isset($item['date']))
			{
				$select = self::SELECT_DATE;
			}
			if (!is_string($select) && (isset($item['from']) || isset($item['to'])))
			{
				$select = self::SELECT_RANGE_DATE;
			}

			if (is_string($select) && isset($this->customPresets[$select]))
			{
				$presetValue = $this->customPresets[$select]->normalize($item);
				if (is_array($presetValue) && isset($presetValue['select']) && is_string($presetValue['select']))
				{
					$normalized[] = $presetValue;
				}
				continue;
			}

			if ($select === self::SELECT_DATE && $this->isValidDateValue($item['date'] ?? null))
			{
				$normalized[] = ['select' => self::SELECT_DATE, 'date' => (string)$item['date']];
				continue;
			}

			if ($select === self::SELECT_RANGE_DATE)
			{
				$from = $this->isValidDateValue($item['from'] ?? null) ? (string)$item['from'] : null;
				$to = $this->isValidDateValue($item['to'] ?? null) ? (string)$item['to'] : null;
				if ($from !== null || $to !== null)
				{
					$value = ['select' => self::SELECT_RANGE_DATE];
					if ($from !== null)
					{
						$value['from'] = $from;
					}
					if ($to !== null)
					{
						$value['to'] = $to;
					}
					$normalized[] = $value;
				}
			}
		}

		$normalized = array_values(array_filter($normalized));
		if ($normalized === [])
		{
			return [];
		}

		if (!$this->isMultiple)
		{
			return [$normalized[0]];
		}

		return $normalized;
	}

	private function buildOneFilter(array $value): ?DataFilterInterface
	{
		$select = $value['select'] ?? null;
		if (!is_string($select))
		{
			return null;
		}

		if (isset($this->customPresets[$select]))
		{
			/** @var DateTimeImmutable $now */
			$now = ($this->nowProvider)();
			[$fromDate, $toDate] = $this->customPresets[$select]->resolveRange($value, $now);
			$from = $fromDate !== null ? $this->toStorageValue($fromDate, false) : null;
			$to = $toDate !== null ? $this->toStorageValue($toDate, true) : null;
			return $this->buildRangeFilter($from, $to);
		}

		switch ($select)
		{
			case self::SELECT_DATE:
				if (!isset($value['date']) || !is_string($value['date']))
				{
					return null;
				}

				$date = $this->parseDate($value['date']);
				if ($date === null)
				{
					return null;
				}

				$from = $this->toStorageValue($date, false);
				$to = $this->toStorageValue($date, true);

				if ($from === $to)
				{
					return new Equals($this->field, $from);
				}

				return new AndX(
					new GreaterThanOrEqual($this->field, $from),
					new LessThanOrEqual($this->field, $to),
				);

			case self::SELECT_RANGE_DATE:
				$fromDate = isset($value['from']) && is_string($value['from']) ? $this->parseDate($value['from']) : null;
				$toDate = isset($value['to']) && is_string($value['to']) ? $this->parseDate($value['to']) : null;
				$from = $fromDate !== null ? $this->toStorageValue($fromDate, false) : null;
				$to = $toDate !== null ? $this->toStorageValue($toDate, true) : null;
				return $this->buildRangeFilter($from, $to);

			default:
				return null;
		}
	}

	private function buildRangeFilter(string|int|null $from, string|int|null $to): ?DataFilterInterface
	{
		if ($from !== null && $to !== null && $from === $to)
		{
			return new Equals($this->field, $from);
		}

		$parts = [];
		if ($from !== null)
		{
			$parts[] = new GreaterThanOrEqual($this->field, $from);
		}
		if ($to !== null)
		{
			$parts[] = new LessThanOrEqual($this->field, $to);
		}

		if ($parts === [])
		{
			return null;
		}
		if (count($parts) === 1)
		{
			return $parts[0];
		}

		return new AndX(...$parts);
	}

	private function isFilledScalar(mixed $value): bool
	{
		return is_scalar($value) && strlen((string)$value) > 0;
	}

	private function isValidDateValue(mixed $value): bool
	{
		return is_scalar($value) && preg_match('~^\d{2}\.\d{2}\.\d{4}$~', (string)$value) === 1;
	}

	private function parseDate(string $value): ?DateTimeImmutable
	{
		$date = DateTimeImmutable::createFromFormat('d.m.Y', $value);
		return $date !== false ? $date : null;
	}

	private function toStorageValue(DateTimeImmutable $date, bool $endOfDay): string|int
	{
		switch ($this->valueType)
		{
			case self::TYPE_DATE:
				return $date->format('Y-m-d');

			case self::TYPE_DATE_TIME:
				$normalized = $endOfDay ? $date->setTime(23, 59, 59) : $date->setTime(0, 0);
				return $normalized->format('Y-m-d H:i:s');

			case self::TYPE_UNIX:
				$normalized = $endOfDay ? $date->setTime(23, 59, 59) : $date->setTime(0, 0);
				return $normalized->getTimestamp();
		}

		throw new InvalidArgumentException('Invalid valueType for DateFilter.');
	}

	public static function presetToday(?string $title = null): DateFilterPreset
	{
		return new DateFilterPreset(
			select: self::SELECT_TODAY,
			messageId: TableMessage::DATE_TODAY,
			title: $title ?? 'Today',
			rangeResolver: static fn(array $_, DateTimeImmutable $now): array => [$now, $now],
		);
	}

	public static function presetYesterday(?string $title = null): DateFilterPreset
	{
		return new DateFilterPreset(
			select: self::SELECT_YESTERDAY,
			messageId: TableMessage::DATE_YESTERDAY,
			title: $title ?? 'Yesterday',
			rangeResolver: static fn(array $_, DateTimeImmutable $now): array => [$now->modify('-1 day'), $now->modify('-1 day')],
		);
	}

	public static function presetTomorrow(?string $title = null): DateFilterPreset
	{
		return new DateFilterPreset(
			select: self::SELECT_TOMORROW,
			messageId: TableMessage::DATE_TOMORROW,
			title: $title ?? 'Tomorrow',
			rangeResolver: static fn(array $_, DateTimeImmutable $now): array => [$now->modify('+1 day'), $now->modify('+1 day')],
		);
	}

	public static function presetCurrentWeek(?string $title = null): DateFilterPreset
	{
		return new DateFilterPreset(
			select: self::SELECT_CURRENT_WEEK,
			messageId: TableMessage::DATE_CURRENT_WEEK,
			title: $title ?? 'Current week',
			rangeResolver: static function (array $_, DateTimeImmutable $now): array {
				$from = $now->modify('monday this week');
				return [$from, $from->modify('+6 days')];
			},
		);
	}

	public static function presetPreviousWeek(?string $title = null): DateFilterPreset
	{
		return new DateFilterPreset(
			select: self::SELECT_PREVIOUS_WEEK,
			messageId: TableMessage::DATE_PREVIOUS_WEEK,
			title: $title ?? 'Previous week',
			rangeResolver: static function (array $_, DateTimeImmutable $now): array {
				$from = $now->modify('monday this week')->modify('-7 days');
				return [$from, $from->modify('+6 days')];
			},
		);
	}

	public static function presetNextWeek(?string $title = null): DateFilterPreset
	{
		return new DateFilterPreset(
			select: self::SELECT_NEXT_WEEK,
			messageId: TableMessage::DATE_NEXT_WEEK,
			title: $title ?? 'Next week',
			rangeResolver: static function (array $_, DateTimeImmutable $now): array {
				$from = $now->modify('monday this week')->modify('+7 days');
				return [$from, $from->modify('+6 days')];
			},
		);
	}

	public function addPreset(DateFilterPreset $preset): self
	{
		if ($this->isBuiltinSelect($preset->select) || isset($this->customPresets[$preset->select]))
		{
			throw new DomainException(sprintf('Select type "%s" already exists.', $preset->select));
		}

		$this->customPresets[$preset->select] = $preset;
		return $this;
	}

	/**
	 * @return list<DateFilterPreset>
	 */
	public function presets(): array
	{
		return array_values($this->customPresets);
	}

	private function isBuiltinSelect(string $select): bool
	{
		return in_array(
			$select,
			[
				self::SELECT_DATE,
				self::SELECT_RANGE_DATE,
			],
			true,
		);
	}
}
