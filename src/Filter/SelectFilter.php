<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Filter;

use InvalidArgumentException;
use Override;
use Yiisoft\Data\Reader\Filter\Equals;
use Yiisoft\Data\Reader\Filter\In;
use Yiisoft\Data\Reader\Filter\Like;
use Yiisoft\Data\Reader\Filter\OrX;
use Yiisoft\Data\Reader\FilterInterface as DataFilterInterface;

use function array_filter;
use function array_map;
use function array_values;
use function call_user_func;
use function count;
use function is_array;
use function is_callable;
use function is_scalar;
use function strlen;

/**
 * @psalm-type SelectOption = array{label: string, value: string}
 * @psalm-type SelectOptions = array<int, SelectOption>
 */
final class SelectFilter extends AbstractPayloadFilter
{
	public const SEARCH_MODE_EQUAL = 'equal';
	public const SEARCH_MODE_LIKE = 'like';

	/**
	 * @var callable(): SelectOptions|SelectOptions|SourceOptionsConfig
	 */
	private mixed $options;

	/**
	 * @param callable(): SelectOptions|SelectOptions|SourceOptionsConfig $options
	 */
	public function __construct(
		string $key,
		string $title,
		private readonly string $field,
		array|callable|SourceOptionsConfig $options,
		private readonly bool $isMultiple = false,
		private readonly string $searchMode = self::SEARCH_MODE_EQUAL,
		?string $caption = null,
	) {
		parent::__construct($key, $title, $caption);

		if (!is_array($options) && !is_callable($options) && !$options instanceof SourceOptionsConfig)
		{
			throw new InvalidArgumentException('Options must be array, callable or SourceOptionsConfig.');
		}

		$this->options = $options;
	}

	#[Override]
	public function type(): string
	{
		return 'select';
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

		if ($this->searchMode === self::SEARCH_MODE_LIKE)
		{
			$filters = array_map(
				fn(string $value): DataFilterInterface => new Like($this->field, $value),
				$values,
			);

			if (count($filters) === 1)
			{
				return $filters[0];
			}

			return new OrX(...$filters);
		}

		if (count($values) === 1)
		{
			return new Equals($this->field, $values[0]);
		}

		return new In($this->field, $values);
	}

	#[Override]
	public function toArray(?FilterInput $input = null): array
	{
		$result = parent::toArray($input);
		$result['isMultiple'] = $this->isMultiple;
		$result['searchMode'] = $this->searchMode;
		$result['options'] = $this->serializeOptions();

		if ($result['values'] !== null && $this->options instanceof SourceOptionsConfig && is_array($result['values']))
		{
			/** @var list<string> $values */
			$values = $result['values'];
			$result['selectedOptions'] = $this->options->getSelectedOptions($values);
		}

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
		$raw = $input->value($this->key());
		if ($raw === null)
		{
			return [];
		}

		$values = is_array($raw) ? $raw : [$raw];
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

	/**
	 * @return array{url: string, termParam: string}|SelectOptions
	 */
	private function serializeOptions(): array
	{
		if ($this->options instanceof SourceOptionsConfig)
		{
			return $this->options->toArray();
		}

		$options = is_callable($this->options) ? call_user_func($this->options) : $this->options;
		/** @var SelectOptions $options */
		return $options;
	}
}
