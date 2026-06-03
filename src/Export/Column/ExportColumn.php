<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Export\Column;

use Closure;
use Mheads\Yii\Table\Column\ColumnInterface;

final class ExportColumn
{
	/**
	 * @param callable(mixed): mixed $reader
	 * @param array<string, mixed> $meta
	 */
	public function __construct(
		private readonly string $key,
		private readonly string $title,
		callable $reader,
		private readonly array $meta = [],
	) {
		$this->reader = $reader(...);
	}

	/** @var Closure(mixed): mixed */
	private readonly Closure $reader;

	public static function fromColumn(ColumnInterface $column): self
	{
		return new self(
			$column->key(),
			$column->title(),
			static fn(mixed $entity): mixed => $column->read($entity),
		);
	}

	public function key(): string
	{
		return $this->key;
	}

	public function title(): string
	{
		return $this->title;
	}

	public function read(mixed $entity): mixed
	{
		return ($this->reader)($entity);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function meta(): array
	{
		return $this->meta;
	}
}
