<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Provider;

use Mheads\Yii\Table\Column\ColumnInterface;
use Mheads\Yii\Table\Export\ExportGeneratorInterface;
use Mheads\Yii\Table\Filter\FilterInput;
use Mheads\Yii\Table\Filter\FilterInterface;
use Mheads\Yii\Table\I18n\LocalizableInterface;
use Mheads\Yii\Table\I18n\TableTranslatorInterface;
use Mheads\Yii\Table\Sort\SortDefinition;
use Mheads\Yii\Table\Sort\SortOption;
use Override;
use Yiisoft\Data\Paginator\InvalidPageException;
use Yiisoft\Data\Paginator\KeysetFilterContext;
use Yiisoft\Data\Paginator\KeysetPaginator;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Paginator\PaginatorInterface;
use Yiisoft\Data\Reader\CountableDataInterface;
use Yiisoft\Data\Reader\Filter\All;
use Yiisoft\Data\Reader\Filter\AndX;
use Yiisoft\Data\Reader\Filter\Compare;
use Yiisoft\Data\Reader\FilterableDataInterface;
use Yiisoft\Data\Reader\LimitableDataInterface;
use Yiisoft\Data\Reader\OffsetableDataInterface;
use Yiisoft\Data\Reader\ReadableDataInterface;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Reader\SortableDataInterface;

use function array_values;
use function in_array;
use function is_int;
use function is_string;

final class TableProvider implements TableProviderInterface, TableConfiguratorInterface
{
	/** @var ColumnInterface[] */
	private array $columns = [];

	/** @var ExportGeneratorInterface[] */
	private array $exportGenerators = [];

	/** @var FilterInterface[] */
	private array $filters = [];

	/** @var array<string, SortDefinition> */
	private array $sortDefinitions = [];

	/** @var SortOption[] */
	private array $sortOptions = [];

	private string $exportParam = 'export';
	private string $filterParam = 'filter';
	private string $sortParam = 'sort';
	private string $pageParam = 'page';
	private string $pageSizeParam = 'per-page';
	private string $prevPageParam = 'prev-page';
	private string|int|null $page = null;
	private string|int|null $previousPage = null;
	private mixed $pageSize = null;
	/** @var array<int, int>|bool|int */
	private bool|int|array $pageSizeConstraint = false;
	private bool $autoPagination = true;
	private bool $ignoreMissingPage = true;
	private ?TableTranslatorInterface $translator;

	public function __construct(
		private readonly string                $id,
		private readonly ReadableDataInterface $reader,
		private ?FilterInput                   $filterInput = null,
		private ?Sort                          $sort = null,
		?TableTranslatorInterface              $translator = null,
	) {
		$this->filterInput ??= new FilterInput();
		$this->translator = $translator;
	}

	#[Override]
	public function id(): string
	{
		return $this->id;
	}

	#[Override]
	public function reader(): ReadableDataInterface
	{
		return $this->reader;
	}

	#[Override]
	public function setIgnoreMissingPage(bool $ignore): self
	{
		$this->ignoreMissingPage = $ignore;
		return $this;
	}

	#[Override]
	public function addColumn(ColumnInterface $column): self
	{
		$this->columns[] = $column;

		$columnFilter = $column->filter();
		if ($columnFilter !== null)
		{
			$this->injectTranslatorIntoFilter($columnFilter);
			$columnFilter->setColumnKey($column->key());
			$this->filters[$columnFilter->key()] = $columnFilter;
		}

		foreach ($column->extraFilters() as $filter)
		{
			$this->injectTranslatorIntoFilter($filter);
			$filter->setColumnKey($column->key());
			$this->filters[$filter->key()] = $filter;
		}

		$sort = $column->sort();
		if ($sort !== null)
		{
			$this->addSort($column->key(), $sort);
		}

		return $this;
	}

	#[Override]
	public function addSort(string $key, SortDefinition $definition): self
	{
		$this->sortDefinitions[$key] = $definition;
		return $this;
	}

	#[Override]
	public function addSortOption(SortOption $option): self
	{
		$this->sortOptions[] = $option;

		if ($option->isDisabled())
		{
			return $this;
		}

		$definition = $option->definition();
		$defaultDirection = $option->isDefault() ? SORT_ASC : null;
		$this->addSort(
			$option->value(),
			new SortDefinition($definition->asc(), $definition->desc(), $defaultDirection),
		);

		return $this;
	}

	#[Override]
	public function addExportGenerator(ExportGeneratorInterface $generator): self
	{
		$this->exportGenerators[] = $generator;
		return $this;
	}

	#[Override]
	public function setExportParam(string $param): self
	{
		$this->exportParam = $param;
		return $this;
	}

	#[Override]
	public function setFilterParam(string $param): self
	{
		$this->filterParam = $param;
		return $this;
	}

	#[Override]
	public function setSortParam(string $param): self
	{
		$this->sortParam = $param;
		return $this;
	}

	#[Override]
	public function setPageParam(string $param): self
	{
		$this->pageParam = $param;
		return $this;
	}

	#[Override]
	public function setPageSizeParam(string $param): self
	{
		$this->pageSizeParam = $param;
		return $this;
	}

	#[Override]
	public function setPrevPageParam(string $param): self
	{
		$this->prevPageParam = $param;
		return $this;
	}

	#[Override]
	public function setFilterInput(FilterInput $input): self
	{
		$this->filterInput = $input;
		return $this;
	}

	#[Override]
	public function setSort(?Sort $sort): self
	{
		$this->sort = $sort;
		return $this;
	}

	#[Override]
	public function setPage(string|int|null $page): self
	{
		$this->page = $this->normalizePageTokenValue($page);
		return $this;
	}

	#[Override]
	public function setPreviousPage(string|int|null $page): self
	{
		$this->previousPage = $this->normalizePageTokenValue($page);
		return $this;
	}

	#[Override]
	public function setPageSize(mixed $pageSize): self
	{
		$this->pageSize = $pageSize;
		return $this;
	}

	#[Override]
	public function setPageSizeConstraint(bool|int|array $constraint): self
	{
		$this->pageSizeConstraint = $this->normalizePageSizeConstraint($constraint);
		return $this;
	}

	#[Override]
	public function setAutoPagination(bool $enabled): self
	{
		$this->autoPagination = $enabled;
		return $this;
	}

	#[Override]
	public function columns(): array
	{
		return $this->columns;
	}

	#[Override]
	public function exportGenerators(): array
	{
		return $this->exportGenerators;
	}

	#[Override]
	public function exportParam(): string
	{
		return $this->exportParam;
	}

	#[Override]
	public function filterParam(): string
	{
		return $this->filterParam;
	}

	#[Override]
	public function sortParam(): string
	{
		return $this->sortParam;
	}

	#[Override]
	public function pageParam(): string
	{
		return $this->pageParam;
	}

	#[Override]
	public function pageSizeParam(): string
	{
		return $this->pageSizeParam;
	}

	#[Override]
	public function pageSizeConstraint(): bool|int|array
	{
		return $this->pageSizeConstraint;
	}

	#[Override]
	public function prevPageParam(): string
	{
		return $this->prevPageParam;
	}

	#[Override]
	public function filterInput(): FilterInput
	{
		return $this->filterInput ?? new FilterInput();
	}

	#[Override]
	public function sort(): ?Sort
	{
		return $this->sort;
	}

	#[Override]
	public function sortOptions(): array
	{
		return $this->sortOptions;
	}

	#[Override]
	public function effectiveSortOrder(): array
	{
		[$config, $defaultOrder] = $this->buildSortConfigAndDefaultOrder();

		if ($config === [])
		{
			return [];
		}

		$requestedOrder = $this->sort?->getOrder() ?? [];
		$knownOrder = [];
		foreach ($requestedOrder as $field => $direction)
		{
			if (isset($config[$field]))
			{
				$knownOrder[$field] = $direction;
			}
		}

		return $knownOrder === [] ? $defaultOrder : $knownOrder;
	}

	#[Override]
	public function filters(): array
	{
		return array_values($this->filters);
	}

	/**
	 * @throws InvalidPageException
	 */
	#[Override]
	public function rows(): array
	{
		$filter = $this->buildFilter();
		$sort = $this->resolveSort();
		/** @var array<int, array<string, mixed>> $rows */
		$rows = $this->withMissingPageHandling(
			fn(bool $applyPageTokens): array => $this->readRows(
				$this->prepareReader($filter, $sort, true, $applyPageTokens),
			),
		);
		return $rows;
	}

	/**
	 * @throws InvalidPageException
	 */
	#[Override]
	public function dataReader(bool $allowAutoWrap = true): ReadableDataInterface
	{
		$filter = $this->buildFilter();
		$sort = $this->resolveSort();
		return $this->withMissingPageHandling(
			fn(bool $applyPageTokens): ReadableDataInterface => $this->prepareReader(
				$filter,
				$sort,
				$allowAutoWrap,
				$applyPageTokens,
			),
		);
	}

	/**
	 * @throws InvalidPageException
	 */
	private function readRows(ReadableDataInterface $reader): array
	{
		$rows = [];
		foreach ($reader->read() as $entity)
		{
			$row = [];
			foreach ($this->columns as $column)
			{
				$row[$column->key()] = $column->read($entity);
			}
			$rows[] = $row;
		}

		return $rows;
	}

	/**
	 * @template T
	 * @param callable(bool):T $callback
	 * @return T
	 * @throws InvalidPageException
	 */
	private function withMissingPageHandling(callable $callback): mixed
	{
		try
		{
			return $callback(true);
		}
		catch (InvalidPageException $exception)
		{
			if ($this->ignoreMissingPage)
			{
				try
				{
					return $callback(false);
				}
				catch (InvalidPageException $exception)
				{
				}
			}

			throw $exception;
		}
	}

	private function buildFilter(): \Yiisoft\Data\Reader\FilterInterface
	{
		$filters = [];

		foreach ($this->filters as $filter)
		{
			$dataFilter = $filter->buildDataFilter($this->filterInput());
			if ($dataFilter !== null)
			{
				$filters[] = $dataFilter;
			}
		}

		if ($filters === [])
		{
			return new All();
		}

		if (count($filters) === 1)
		{
			return $filters[0];
		}

		return new AndX(...$filters);
	}

	private function resolveSort(): ?Sort
	{
		[$config, ] = $this->buildSortConfigAndDefaultOrder();

		if ($config === [])
		{
			return null;
		}

		$sort = Sort::only($config)->withoutDefaultSorting();
		return $sort->withOrder($this->effectiveSortOrder());
	}

	/**
	 * @return array{0: array<string, array{asc: array<string, int>, desc: array<string, int>}>, 1: array<string, 'asc'|'desc'>}
	 */
	private function buildSortConfigAndDefaultOrder(): array
	{
		$config = [];
		$defaultOrder = [];
		foreach ($this->sortDefinitions as $sortKey => $definition)
		{
			$config[$sortKey] = [
				'asc'  => $definition->asc(),
				'desc' => $definition->desc(),
			];

			if ($definition->defaultDirection() !== null)
			{
				$defaultOrder[$sortKey] = $definition->defaultDirection() === SORT_DESC ? 'desc' : 'asc';
			}
		}

		return [$config, $defaultOrder];
	}

	private function prepareReader(
		\Yiisoft\Data\Reader\FilterInterface $filter,
		?Sort $sort,
		bool $allowAutoWrap = true,
		bool $applyPageTokens = true,
	): ReadableDataInterface {
		$reader = $this->prepareFilterAndSortReader($this->reader, $filter, $sort);
		$reader = $this->autoWrapWithPaginator($reader, $allowAutoWrap);

		if ($reader instanceof PaginatorInterface)
		{
			$paginator = $reader;
			$effectivePageSize = $this->normalizePageSizeValue($paginator->getPageSize());
			if ($effectivePageSize !== $paginator->getPageSize())
			{
				/** @var positive-int $effectivePageSize */
				$paginator = $paginator->withPageSize($effectivePageSize);
			}

			if ($paginator->isPaginationRequired())
			{
				if ($applyPageTokens)
				{
					$page = $this->page;
					$previousPage = $this->previousPage;
					if ($page !== null)
					{
						$paginator = $paginator->withToken(PageToken::next((string)$page));
					}
					elseif ($previousPage !== null)
					{
						$paginator = $paginator->withToken(PageToken::previous((string)$previousPage));
					}
				}
			}

			return $paginator;
		}

		return $reader;
	}

	private function autoWrapWithPaginator(ReadableDataInterface $reader, bool $allowAutoWrap): ReadableDataInterface
	{
		if ($reader instanceof PaginatorInterface)
		{
			return $reader;
		}

		if (!$this->autoPagination || !$allowAutoWrap)
		{
			return $reader;
		}

		if (
			$reader instanceof OffsetableDataInterface
			&& $reader instanceof CountableDataInterface
			&& $reader instanceof LimitableDataInterface
		) {
			return new OffsetPaginator($reader);
		}

		if (
			$reader instanceof FilterableDataInterface
			&& $reader instanceof SortableDataInterface
			&& $reader instanceof LimitableDataInterface
			&& $reader->getSort() !== null
		) {
			return new KeysetPaginator($reader);
		}

		return $reader;
	}

	private function prepareFilterAndSortReader(
		ReadableDataInterface $reader,
		\Yiisoft\Data\Reader\FilterInterface $filter,
		?Sort $sort,
	): ReadableDataInterface {
		if ($reader instanceof PaginatorInterface)
		{
			if ($reader->isFilterable())
			{
				$reader = $reader->withFilter($filter);
			}

			if ($sort !== null && $reader->isSortable())
			{
				$reader = $reader->withSort($sort);
				if ($reader instanceof KeysetPaginator)
				{
					$reader = $this->applyKeysetFilterFieldMapping($reader, $sort);
				}
			}

			return $reader;
		}

		if ($reader instanceof FilterableDataInterface)
		{
			$reader = $reader->withFilter($filter);
		}

		if ($sort !== null && $reader instanceof SortableDataInterface)
		{
			$reader = $reader->withSort($sort);
		}

		return $reader;
	}

	private function applyKeysetFilterFieldMapping(KeysetPaginator $paginator, Sort $sort): KeysetPaginator
	{
		$criteria = $sort->getCriteria();
		if ($criteria === [])
		{
			return $paginator;
		}

		$actualField = key($criteria);

		return $paginator->withFilterCallback(
			static function (Compare $filter, KeysetFilterContext $context) use ($actualField) {
				if ($context->field === $actualField)
				{
					return $filter;
				}

				$filterClass = $filter::class;
				return new $filterClass($actualField, $filter->value);
			},
		);
	}

	private function normalizePageTokenValue(string|int|null $page): string|int|null
	{
		if (is_string($page) && $page === '')
		{
			return null;
		}

		return $page;
	}

	private function injectTranslatorIntoFilter(FilterInterface $filter): void
	{
		if (!$filter instanceof LocalizableInterface || $this->translator === null)
		{
			return;
		}

		$filter->setTranslator($this->translator);
	}

	/**
	 * @psalm-return array<int, int>|bool|int
	 */
	private function normalizePageSizeConstraint(bool|int|array $constraint): bool|int|array
	{
		if ($constraint === true || $constraint === false)
		{
			return $constraint;
		}

		if (is_int($constraint))
		{
			return $constraint > 0 ? $constraint : false;
		}

		/** @var array<int, int> $result */
		$result = [];
		foreach ($constraint as $value)
		{
			if (is_int($value) && $value > 0)
			{
				$result[$value] = $value;
			}
		}

		return array_values($result);
	}

	private function normalizePageSizeValue(int $defaultPageSize): int
	{
		$normalizedDefault = $this->normalizeDefaultPageSizeByConstraint($defaultPageSize);
		$rawPageSize = $this->normalizePositiveInt($this->pageSize);
		if ($rawPageSize === null || $this->pageSizeConstraint === true)
		{
			return $normalizedDefault;
		}

		return $this->normalizePageSizeByConstraint($rawPageSize, $normalizedDefault, false);
	}

	private function normalizeDefaultPageSizeByConstraint(int $defaultPageSize): int
	{
		return $this->normalizePageSizeByConstraint($defaultPageSize, $defaultPageSize, true);
	}

	private function normalizePageSizeByConstraint(int $pageSize, int $fallback, bool $capByMax): int
	{
		$constraint = $this->pageSizeConstraint;
		if ($constraint === true || $constraint === false)
		{
			return $pageSize;
		}

		if (is_int($constraint))
		{
			if ($pageSize <= $constraint)
			{
				return $pageSize;
			}

			return $capByMax ? $constraint : $fallback;
		}

		if ($constraint === [])
		{
			return $pageSize;
		}

		if (in_array($pageSize, $constraint, true))
		{
			return $pageSize;
		}

		return $capByMax ? $constraint[0] : $fallback;
	}

	/**
	 * @return positive-int|null
	 */
	private function normalizePositiveInt(mixed $value): ?int
	{
		if (!is_int($value) && !is_string($value))
		{
			return null;
		}

		$intValue = (int)$value;
		return $intValue < 1 ? null : $intValue;
	}

}
