<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Filter;

use Override;

/**
 * @psalm-import-type FilterPayload from \Mheads\Yii\Table\Serialization\TablePayloadTypes
 */
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
	/**
	 * @return FilterPayload
	 */
	public function toArray(FilterInput $input): array
	{
		return [
			'key'       => $this->key(),
			'title'     => $this->title(),
			'caption'   => $this->caption(),
			'type'      => $this->type(),
			'values'    => $this->getValues($input),
			'columnKey' => $this->getColumnKey(),
		];
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
