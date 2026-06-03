<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Http\Request;

use Mheads\Yii\Table\Filter\FilterInput;
use Mheads\Yii\Table\Provider\TableConfiguratorInterface;
use Mheads\Yii\Table\Provider\TableProviderInterface;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Data\Reader\Sort;

use function array_key_exists;
use function is_array;
use function is_int;
use function is_string;

final class TableRequestApplier implements TableRequestApplierInterface
{
	/**
	 */
	#[Override]
	public function apply(
		TableProviderInterface&TableConfiguratorInterface $table,
		ServerRequestInterface $request,
	): void {
		$query = $request->getQueryParams();

		$filterValue = $query[$table->filterParam()] ?? null;
		$table->setFilterInput(new FilterInput($this->normalizeFilterValues($filterValue)));

		$sortValue = $query[$table->sortParam()] ?? null;
		if (is_string($sortValue) && $sortValue !== '')
		{
			$table->setSort(Sort::any()->withOrderString($sortValue));
		}
		else
		{
			$table->setSort(null);
		}

		if (array_key_exists($table->pageSizeParam(), $query))
		{
			$table->setPageSize($query[$table->pageSizeParam()]);
		}

		$pageValue = $query[$table->pageParam()] ?? null;
		$prevPageValue = $query[$table->prevPageParam()] ?? null;
		$prevPage = $this->normalizePage($prevPageValue);
		$page = $this->normalizePage($pageValue);

		if ($prevPage !== null)
		{
			$table->setPreviousPage($prevPage);
			$table->setPage(null);
			return;
		}

		$table->setPreviousPage(null);
		$table->setPage($page);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function normalizeFilterValues(mixed $value): array
	{
		if (!is_array($value))
		{
			return [];
		}

		$result = [];
		foreach ($value as $key => $item)
		{
			if (is_string($key))
			{
				$result[$key] = $item;
			}
		}

		return $result;
	}

	private function normalizePage(mixed $value): string|int|null
	{
		if (is_int($value))
		{
			return $value;
		}

		if (!is_string($value) || $value === '')
		{
			return null;
		}

		return $value;
	}
}
