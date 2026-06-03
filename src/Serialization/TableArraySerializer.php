<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Serialization;

use LogicException;
use Mheads\Yii\Table\Column\ColumnInterface;
use Mheads\Yii\Table\Filter\FilterInterface;
use Mheads\Yii\Table\Filter\FilterPayloadProviderInterface;
use Mheads\Yii\Table\Provider\TableProviderInterface;
use Mheads\Yii\Table\Sort\SortOption;
use Override;
use Yiisoft\Data\Paginator\InvalidPageException;
use Yiisoft\Data\Paginator\KeysetPaginator;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PaginatorInterface;

/**
 * @psalm-import-type TablePayload from TablePayloadTypes
 * @psalm-import-type OffsetPagination from TablePayloadTypes
 * @psalm-import-type KeysetPagination from TablePayloadTypes
 * @psalm-import-type GenericPagination from TablePayloadTypes
 * @psalm-import-type ColumnSortPayload from TablePayloadTypes
 */
final class TableArraySerializer implements TableSerializerInterface
{
	/**
	 * @return TablePayload
	 * @throws InvalidPageException
	 */
	#[Override]
	public function serialize(TableProviderInterface $table): array
	{
		$reader = $table->dataReader();
		$paginator = $reader instanceof PaginatorInterface ? $reader : null;
		$effectiveOrder = $table->effectiveSortOrder();

		return [
			'config' => [
				'tableId'            => $table->id(),
				'filterParam'        => $table->filterParam(),
				'sortParam'          => $this->hasSorting($table) ? $table->sortParam() : null,
				'pageParam'          => $paginator !== null ? $table->pageParam() : null,
				'prevPageParam'      => $paginator instanceof KeysetPaginator ? $table->prevPageParam() : null,
				'pageSizeParam'      => $paginator !== null ? $table->pageSizeParam() : null,
				'pageSizeConstraint' => $paginator !== null ? $table->pageSizeConstraint() : null,
				'columnIdKey'        => $this->findIdColumnKey($table),
				'exportParam'        => $table->exportGenerators() !== [] ? $table->exportParam() : null,
				'exportCodes'        => $this->serializeExportCodes($table),
			],
			'pagination' => $this->serializePagination($paginator),
			'columns'    => array_values(array_map(
				fn($column) => [
					'key'             => $column->key(),
					'title'           => $column->title(),
					'sort'            => $this->serializeColumnSort($column, $effectiveOrder),
					'isHidden'        => $column->isHidden(),
					'filterKey'       => $column->filter()?->key(),
					'extraFilterKeys' => array_values(array_map(static fn($filter) => $filter->key(), $column->extraFilters())),
				],
				$table->columns(),
			)),
			'filters' => array_values(array_map(
				fn(FilterInterface $filter): array => $this->serializeFilter($filter, $table),
				$table->filters(),
			)),
			'sorts' => array_values(array_map(
				static fn(SortOption $option): array => [
					'title'      => $option->title(),
					'value'      => $option->value(),
					'isDefault'  => $option->isDefault(),
					'isDisabled' => $option->isDisabled(),
					'isSelected' => array_key_exists($option->value(), $effectiveOrder),
				],
				$table->sortOptions(),
			)),
			'rows' => $table->rows(),
		];
	}

	private function serializeFilter(FilterInterface $filter, TableProviderInterface $table): array
	{
		if (!$filter instanceof FilterPayloadProviderInterface)
		{
			throw new LogicException(sprintf(
				'Filter "%s" must implement %s to be serialized by %s.',
				$filter->key(),
				FilterPayloadProviderInterface::class,
				self::class,
			));
		}

		return $filter->toArray($table->filterInput());
	}

	/**
	 * @return GenericPagination|KeysetPagination|OffsetPagination|null
	 */
	private function serializePagination(?PaginatorInterface $paginator): ?array
	{
		if ($paginator === null)
		{
			return null;
		}

		if ($paginator instanceof OffsetPaginator)
		{
			return [
				'type'          => 'offset',
				'currentPage'   => $paginator->getCurrentPage(),
				'perPage'       => $paginator->getPageSize(),
				'pageCount'     => $paginator->getTotalPages(),
				'totalCount'    => $paginator->getTotalItems(),
				'nextPageToken' => $paginator->getNextToken()?->value,
				'prevPageToken' => $paginator->getPreviousToken()?->value,
			];
		}

		if ($paginator instanceof KeysetPaginator)
		{
			return [
				'type'            => 'keyset',
				'perPage'         => $paginator->getPageSize(),
				'currentPageSize' => $paginator->getCurrentPageSize(),
				'nextPageToken'   => $paginator->getNextToken()?->value,
				'prevPageToken'   => $paginator->getPreviousToken()?->value,
				'isOnFirstPage'   => $paginator->isOnFirstPage(),
				'isOnLastPage'    => $paginator->isOnLastPage(),
			];
		}

		return [
			'type'            => 'generic',
			'perPage'         => $paginator->getPageSize(),
			'currentPageSize' => $paginator->getCurrentPageSize(),
			'nextPageToken'   => $paginator->getNextToken()?->value,
			'prevPageToken'   => $paginator->getPreviousToken()?->value,
		];
	}

	private function findIdColumnKey(TableProviderInterface $table): ?string
	{
		foreach ($table->columns() as $column)
		{
			if ($column->isId())
			{
				return $column->key();
			}
		}

		return null;
	}

	/**
	 * @return array<int, string>|null
	 */
	private function serializeExportCodes(TableProviderInterface $table): ?array
	{
		if ($table->exportGenerators() === [])
		{
			return null;
		}

		$codes = [];
		foreach ($table->exportGenerators() as $generator)
		{
			$codes[] = $generator->code();
		}

		return $codes;
	}

	private function hasSorting(TableProviderInterface $table): bool
	{
		if ($table->sortOptions() !== [])
		{
			return true;
		}

		foreach ($table->columns() as $column)
		{
			if ($column->sort() !== null)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @param array<string, 'asc'|'desc'> $effectiveOrder
	 * @return ColumnSortPayload|null
	 */
	private function serializeColumnSort(ColumnInterface $column, array $effectiveOrder): ?array
	{
		$definition = $column->sort();
		if ($definition === null)
		{
			return null;
		}

		$direction = $effectiveOrder[$column->key()] ?? null;

		return [
			'isSorted'        => $direction !== null,
			'isDefault'       => $definition->defaultDirection() !== null,
			'sortedDirection' => $direction === null ? null : ($direction === 'desc' ? 'descend' : 'ascend'),
			'values'          => [
				'ascend'  => $column->key(),
				'descend' => '-' . $column->key(),
			],
		];
	}
}
