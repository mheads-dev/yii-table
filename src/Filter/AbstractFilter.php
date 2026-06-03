<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Filter;

use Override;

abstract class AbstractFilter implements FilterInterface
{
	private ?string $columnKey = null;

	public function __construct(
		private readonly string $key,
		private readonly string $title,
		private readonly ?string $caption = null,
	) {}

	#[Override]
	public function key(): string
	{
		return $this->key;
	}

	public function title(): string
	{
		return $this->title;
	}

	public function caption(): ?string
	{
		return $this->caption;
	}

	#[Override]
	public function setColumnKey(?string $columnKey): void
	{
		$this->columnKey = $columnKey;
	}

	#[Override]
	public function getColumnKey(): ?string
	{
		return $this->columnKey;
	}

	protected function getValues(FilterInput $input): mixed
	{
		return $input->value($this->key());
	}
}
