<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Filter;

use Override;
use Yiisoft\Data\Reader\Filter\AndX;
use Yiisoft\Data\Reader\Filter\Equals;
use Yiisoft\Data\Reader\Filter\Like;
use Yiisoft\Data\Reader\Filter\OrX;
use Yiisoft\Data\Reader\FilterInterface as DataFilterInterface;

use function array_filter;
use function array_map;
use function array_values;
use function count;
use function is_array;
use function is_scalar;
use function strlen;

final class CompositeSearchFilter extends AbstractFilter
{
	/** @var list<array{field: string, searchMode: string}> */
	private array $rules = [];

	public function __construct(
		string $key,
		string $title,
		private readonly bool $combineWithOr = true,
		?string $caption = null,
	) {
		parent::__construct($key, $title, $caption);
	}

	public function addField(string $field, string $searchMode = SearchFilter::SEARCH_MODE_LIKE): self
	{
		$this->rules[] = [
			'field'      => $field,
			'searchMode' => $searchMode,
		];

		return $this;
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
		if ($values === null || $this->rules === [])
		{
			return null;
		}

		$value = $values[0];
		$filters = [];
		foreach ($this->rules as $rule)
		{
			$filters[] = $rule['searchMode'] === SearchFilter::SEARCH_MODE_EQUAL
				? new Equals($rule['field'], $value)
				: new Like($rule['field'], $value);
		}

		if (count($filters) === 1)
		{
			return $filters[0];
		}

		return $this->combineWithOr ? new OrX(...$filters) : new AndX(...$filters);
	}

	#[Override]
	public function toArray(FilterInput $input): array
	{
		$result = parent::toArray($input);
		$result['isMultiple'] = false;
		return $result;
	}

	#[Override]
	protected function getValues(FilterInput $input): mixed
	{
		$value = $input->value($this->key());
		if ($value === null)
		{
			return null;
		}

		$values = is_array($value) ? $value : [$value];
		$values = array_filter(
			$values,
			static fn(mixed $item): bool => is_scalar($item) && strlen((string)$item) > 0,
		);
		$values = array_values(array_map(static fn(mixed $item): string => (string)$item, $values));

		if ($values === [])
		{
			return null;
		}

		return [$values[0]];
	}
}
