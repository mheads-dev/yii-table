<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Filter;

use InvalidArgumentException;
use Override;
use Yiisoft\Data\Reader\Filter\In;
use Yiisoft\Data\Reader\FilterInterface as DataFilterInterface;

use function array_filter;
use function array_values;
use function call_user_func;
use function is_array;
use function is_callable;
use function is_scalar;
use function strlen;

/**
 * @psalm-type CheckboxOption = array{label: string, value: string}
 * @psalm-type CheckboxOptions = array<int, CheckboxOption>
 * @psalm-type CheckboxValues = array<int, string>
 */
final class CheckboxFilter extends AbstractPayloadFilter
{
	/**
	 * @var callable(): CheckboxOptions|CheckboxOptions
	 */
	private mixed $options;

	/**
	 * @param callable(): CheckboxOptions|CheckboxOptions $options
	 */
	public function __construct(
		string $key,
		string $title,
		private readonly string $field,
		array|callable $options,
		?string $caption = null,
	) {
		parent::__construct($key, $title, $caption);

		if (!is_array($options) && !is_callable($options))
		{
			throw new InvalidArgumentException('Options must be array or callable.');
		}

		$this->options = $options;
	}

	#[Override]
	public function type(): string
	{
		return 'checkbox';
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

		return new In($this->field, $values);
	}

	#[Override]
	public function toArray(?FilterInput $input = null): array
	{
		$result = parent::toArray($input);
		$result['options'] = $this->serializeOptions();
		return $result;
	}

	#[Override]
	protected function getValues(FilterInput $input): mixed
	{
		$values = $this->normalizeValues($input);
		return $values === [] ? null : $values;
	}

	/**
	 * @return CheckboxValues
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

		return array_values(
			array_map(static fn(mixed $item): string => (string)$item, $values),
		);
	}

	/**
	 * @return CheckboxOptions
	 */
	private function serializeOptions(): array
	{
		/** @var CheckboxOptions $options */
		$options = is_callable($this->options) ? call_user_func($this->options) : $this->options;
		return $options;
	}
}
