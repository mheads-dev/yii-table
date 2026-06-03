<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Filter;

use Override;
use Yiisoft\Data\Reader\Filter\Equals;
use Yiisoft\Data\Reader\Filter\Like;
use Yiisoft\Data\Reader\Filter\OrX;
use Yiisoft\Data\Reader\FilterInterface as DataFilterInterface;

use function array_filter;
use function array_map;
use function array_values;
use function is_array;
use function is_scalar;
use function strlen;

final class SearchFilter extends AbstractFilter
{
	public const SEARCH_MODE_EQUAL = 'equal';
	public const SEARCH_MODE_LIKE = 'like';

	public function __construct(
		string $key,
		string $title,
		private readonly string $field,
		private readonly bool $isMultiple = false,
		private readonly string $searchMode = self::SEARCH_MODE_LIKE,
		?string $caption = null,
	) {
		parent::__construct($key, $title, $caption);
	}

	#[Override]
	public function type(): string
	{
		return 'search';
	}

	#[Override]
	public function buildDataFilter(FilterInput $input): ?DataFilterInterface
	{
		/** @var list<string>|null $values */
		$values = $this->getValues($input);
		if ($values === null)
		{
			return null;
		}

		$filters = array_map(
			fn(string $value): DataFilterInterface => $this->searchMode === self::SEARCH_MODE_EQUAL
				? new Equals($this->field, $value)
				: new Like($this->field, $value),
			$values,
		);

		if (count($filters) === 1)
		{
			return $filters[0];
		}

		return new OrX(...$filters);
	}

	#[Override]
	public function toArray(FilterInput $input): array
	{
		$result = parent::toArray($input);
		$result['isMultiple'] = $this->isMultiple;
		$result['searchMode'] = $this->searchMode;
		return $result;
	}

	#[Override]
	protected function getValues(FilterInput $input): mixed
	{
		$values = $this->normalizeValues($input);
		return $values === [] ? null : $values;
	}

	/**
	 * @return list<string>
	 */
	private function normalizeValues(FilterInput $input): array
	{
		$value = $input->value($this->key());
		if ($value === null)
		{
			return [];
		}

		$values = is_array($value) ? $value : [$value];
		$values = array_filter(
			$values,
			static fn(mixed $item): bool => is_scalar($item) && strlen((string)$item) > 0,
		);
		$values = array_values(array_map(static fn(mixed $item): string => (string)$item, $values));

		if ($values === [])
		{
			return [];
		}

		if (!$this->isMultiple)
		{
			return [$values[0]];
		}

		return $values;
	}
}
