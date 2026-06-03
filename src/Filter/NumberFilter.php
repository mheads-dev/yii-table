<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Filter;

use Mheads\Yii\Table\I18n\LocalizableInterface;
use Mheads\Yii\Table\I18n\LocalizableTrait;
use Mheads\Yii\Table\I18n\TableMessage;
use Override;
use Yiisoft\Data\Reader\Filter\AndX;
use Yiisoft\Data\Reader\Filter\Equals;
use Yiisoft\Data\Reader\Filter\GreaterThan;
use Yiisoft\Data\Reader\Filter\GreaterThanOrEqual;
use Yiisoft\Data\Reader\Filter\LessThan;
use Yiisoft\Data\Reader\Filter\LessThanOrEqual;
use Yiisoft\Data\Reader\Filter\None;
use Yiisoft\Data\Reader\Filter\OrX;
use Yiisoft\Data\Reader\FilterInterface as DataFilterInterface;

use function array_filter;
use function array_values;
use function count;
use function in_array;
use function is_array;
use function is_numeric;
use function is_scalar;
use function strlen;

/**
 * @psalm-type NumberItem = array{
 *     select: self::SELECT_EXACTLY|self::SELECT_MORE_THAN|self::SELECT_LESS_THAN|self::SELECT_RANGE,
 *     number?: string|int|float,
 *     from?: string|int|float,
 *     to?: string|int|float
 * }
 */
final class NumberFilter extends AbstractPayloadFilter implements LocalizableInterface
{
	use LocalizableTrait;

	public const string SELECT_EXACTLY = 'exactly';
	public const string SELECT_MORE_THAN = 'more_than';
	public const string SELECT_LESS_THAN = 'less_than';
	public const string SELECT_RANGE = 'range';

	public function __construct(
		string $key,
		string $title,
		private readonly string $field,
		private readonly bool $isMultiple = false,
		?string $caption = null,
	) {
		parent::__construct($key, $title, $caption);
	}

	#[Override]
	public function type(): string
	{
		return 'number';
	}

	#[Override]
	public function buildDataFilter(FilterInput $input): ?DataFilterInterface
	{
		/** @var list<NumberItem>|null $values */
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
		$result['select'] = $this->getSelect();
		$result['isMultiple'] = $this->isMultiple;
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
		return [
			[
				'select' => self::SELECT_EXACTLY,
				'title'  => $this->translateOrSelf(TableMessage::NUMBER_EXACTLY, 'Exactly'),
			],
			[
				'select' => self::SELECT_MORE_THAN,
				'title'  => $this->translateOrSelf(TableMessage::NUMBER_MORE_THAN, 'More than'),
			],
			[
				'select' => self::SELECT_LESS_THAN,
				'title'  => $this->translateOrSelf(TableMessage::NUMBER_LESS_THAN, 'Less than'),
			],
			[
				'select' => self::SELECT_RANGE,
				'title'  => $this->translateOrSelf(TableMessage::NUMBER_RANGE, 'Range'),
			],
		];
	}

	/**
	 * @return list<NumberItem>
	 */
	private function normalizeValues(FilterInput $input): array
	{
		$raw = $input->value($this->key());

		if ($this->isFilledScalar($raw))
		{
			$values = [['select' => self::SELECT_EXACTLY, 'number' => (string)$raw]];
		}
		elseif (is_array($raw) && isset($raw['select']))
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

		$selects = [self::SELECT_EXACTLY, self::SELECT_MORE_THAN, self::SELECT_LESS_THAN, self::SELECT_RANGE];
		$normalized = [];

		foreach ($values as $item)
		{
			if ($this->isFilledScalar($item))
			{
				$normalized[] = ['select' => self::SELECT_EXACTLY, 'number' => (string)$item];
				continue;
			}

			if (!is_array($item) || !isset($item['select']) || !in_array($item['select'], $selects, true))
			{
				continue;
			}

			$select = $item['select'];
			if ($select === self::SELECT_EXACTLY && $this->isFilledScalar($item['number'] ?? null))
			{
				$normalized[] = ['select' => $select, 'number' => (string)$item['number']];
				continue;
			}

			if ($select === self::SELECT_MORE_THAN && $this->isFilledScalar($item['from'] ?? null))
			{
				$normalized[] = ['select' => $select, 'from' => (string)$item['from']];
				continue;
			}

			if ($select === self::SELECT_LESS_THAN && $this->isFilledScalar($item['to'] ?? null))
			{
				$normalized[] = ['select' => $select, 'to' => (string)$item['to']];
				continue;
			}

			if ($select === self::SELECT_RANGE)
			{
				$from = $this->isFilledScalar($item['from'] ?? null) ? (string)$item['from'] : null;
				$to = $this->isFilledScalar($item['to'] ?? null) ? (string)$item['to'] : null;
				if ($from !== null || $to !== null)
				{
					$range = ['select' => $select];
					if ($from !== null)
					{
						$range['from'] = $from;
					}
					if ($to !== null)
					{
						$range['to'] = $to;
					}
					$normalized[] = $range;
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

	/**
	 * @param NumberItem $value
	 */
	private function buildOneFilter(array $value): ?DataFilterInterface
	{
		$select = $value['select'] ?? null;
		if (!is_string($select))
		{
			return null;
		}

		if ($select === self::SELECT_EXACTLY && isset($value['number']) && is_numeric($value['number']))
		{
			return new Equals($this->field, $value['number']);
		}

		if ($select === self::SELECT_MORE_THAN && isset($value['from']) && is_numeric($value['from']))
		{
			return new GreaterThan($this->field, $value['from']);
		}

		if ($select === self::SELECT_LESS_THAN && isset($value['to']) && is_numeric($value['to']))
		{
			return new LessThan($this->field, $value['to']);
		}

		if ($select !== self::SELECT_RANGE)
		{
			return null;
		}

		$from = $value['from'] ?? null;
		$to = $value['to'] ?? null;
		$from = is_numeric($from) ? $from : null;
		$to = is_numeric($to) ? $to : null;

		if ($from !== null && $to !== null && (string)$from === (string)$to)
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
}
