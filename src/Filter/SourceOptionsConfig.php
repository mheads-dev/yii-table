<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Filter;

use Closure;

use function call_user_func;

final class SourceOptionsConfig
{
	/**
	 * @var (Closure(list<string>): ?array<int, array{label: string, value: string}>)|null
	 */
	private readonly ?Closure $selectedOptionsGetter;

	/**
	 * @param (callable(list<string>): ?array<int, array{label: string, value: string}>)|null $selectedOptionsGetter
	 */
	public function __construct(
		private readonly string $url,
		private readonly string $termParam,
		?callable $selectedOptionsGetter = null,
	) {
		$this->selectedOptionsGetter = $selectedOptionsGetter !== null ? Closure::fromCallable($selectedOptionsGetter) : null;
	}

	public function getUrl(): string
	{
		return $this->url;
	}

	public function getTermParam(): string
	{
		return $this->termParam;
	}

	/**
	 * @param list<string> $values
	 *
	 * @return array<int, array{label: string, value: string}>|null
	 */
	public function getSelectedOptions(array $values): ?array
	{
		if ($this->selectedOptionsGetter !== null)
		{
			/** @var array<int, array{label: string, value: string}>|null $result */
			$result = call_user_func($this->selectedOptionsGetter, $values);
			return $result;
		}

		return null;
	}

	/**
	 * @return array{url: string, termParam: string}
	 */
	public function toArray(): array
	{
		return [
			'url'       => $this->url,
			'termParam' => $this->termParam,
		];
	}
}
