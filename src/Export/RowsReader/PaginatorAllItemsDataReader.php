<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Export\RowsReader;

use Override;
use Yiisoft\Data\Paginator\PaginatorInterface;
use Yiisoft\Data\Reader\ReadableDataInterface;

/**
 * @internal
 * @template TValue as array|object
 * @implements ReadableDataInterface<int, TValue>
 */
final class PaginatorAllItemsDataReader implements ReadableDataInterface
{
	/**
	 * @param PaginatorInterface<array-key, TValue> $paginator
	 */
	public function __construct(
		private readonly PaginatorInterface $paginator,
	) {}

	#[Override]
	public function read(): iterable
	{
		$current = $this->paginator->withToken(null);

		do
		{
			foreach ($current->read() as $item)
			{
				yield $item;
			}

			$current = $current->nextPage();
		}
		while ($current !== null);
	}

	#[Override]
	public function readOne(): array|object|null
	{
		foreach ($this->read() as $item)
		{
			return $item;
		}

		return null;
	}
}
