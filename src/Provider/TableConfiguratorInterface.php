<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Provider;

use Mheads\Yii\Table\Column\ColumnInterface;
use Mheads\Yii\Table\Export\ExportGeneratorInterface;
use Mheads\Yii\Table\Filter\FilterInput;
use Mheads\Yii\Table\Sort\SortDefinition;
use Mheads\Yii\Table\Sort\SortOption;
use Yiisoft\Data\Reader\Sort;

interface TableConfiguratorInterface
{
	/**
	 * Добавляет колонку таблицы.
	 */
	public function addColumn(ColumnInterface $column): self;

	/**
	 * Добавляет описание сортировки по ключу.
	 */
	public function addSort(string $key, SortDefinition $definition): self;

	/**
	 * Добавляет отдельную (непривязанную к колонке) опцию сортировки.
	 */
	public function addSortOption(SortOption $option): self;

	/**
	 * Добавляет доступный генератор экспорта.
	 */
	public function addExportGenerator(ExportGeneratorInterface $generator): self;

	/**
	 * Устанавливает имя request-параметра экспорта.
	 */
	public function setExportParam(string $param): self;

	/**
	 * Устанавливает имя request-параметра фильтров.
	 */
	public function setFilterParam(string $param): self;

	/**
	 * Устанавливает имя request-параметра сортировки.
	 */
	public function setSortParam(string $param): self;

	/**
	 * Устанавливает имя request-параметра страницы.
	 */
	public function setPageParam(string $param): self;

	/**
	 * Устанавливает имя request-параметра размера страницы.
	 */
	public function setPageSizeParam(string $param): self;

	/**
	 * Устанавливает имя request-параметра предыдущей страницы (keyset).
	 */
	public function setPrevPageParam(string $param): self;

	/**
	 * Устанавливает нормализованный input фильтров.
	 */
	public function setFilterInput(FilterInput $input): self;

	/**
	 * Устанавливает сортировку из запроса/контекста.
	 */
	public function setSort(?Sort $sort): self;

	/**
	 * Устанавливает токен/номер следующей страницы.
	 */
	public function setPage(string|int|null $page): self;

	/**
	 * Устанавливает токен/номер предыдущей страницы.
	 */
	public function setPreviousPage(string|int|null $page): self;

	/**
	 * Включает/выключает fallback на первую страницу при неверном page/prev-page.
	 */
	public function setIgnoreMissingPage(bool $ignore): self;

	/**
	 * Устанавливает raw-значение размера страницы из внешнего источника.
	 */
	public function setPageSize(mixed $pageSize): self;

	/**
	 * Устанавливает ограничение для размера страницы:
	 * `true` — только default, `false` — без ограничений, `int` — максимум, `int[]` — whitelist.
	 */
	public function setPageSizeConstraint(bool|int|array $constraint): self;

	/**
	 * Включает/выключает автоматическое оборачивание reader в paginator (т.е. постраничную навигацию)
	 */
	public function setAutoPagination(bool $enabled): self;
}
