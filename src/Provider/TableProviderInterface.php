<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Provider;

use Mheads\Yii\Table\Column\ColumnInterface;
use Mheads\Yii\Table\Export\ExportGeneratorInterface;
use Mheads\Yii\Table\Filter\FilterInput;
use Mheads\Yii\Table\Filter\FilterInterface;
use Mheads\Yii\Table\Sort\SortOption;
use Yiisoft\Data\Paginator\InvalidPageException;
use Yiisoft\Data\Reader\ReadableDataInterface;
use Yiisoft\Data\Reader\Sort;

interface TableProviderInterface
{
	/**
	 * Возвращает уникальный идентификатор таблицы.
	 */
	public function id(): string;

	/**
	 * Возвращает источник данных таблицы.
	 */
	public function reader(): ReadableDataInterface;

	/**
	 * Возвращает описание колонок таблицы.
	 *
	 * @return ColumnInterface[]
	 */
	public function columns(): array;

	/**
	 * Возвращает доступные экспорты таблицы.
	 *
	 * @return ExportGeneratorInterface[]
	 */
	public function exportGenerators(): array;

	/**
	 * Возвращает имя request-параметра для выбора экспорта.
	 */
	public function exportParam(): string;

	/**
	 * Возвращает имя request-параметра фильтров.
	 */
	public function filterParam(): string;

	/**
	 * Возвращает имя request-параметра сортировки.
	 */
	public function sortParam(): string;

	/**
	 * Возвращает имя request-параметра номера страницы.
	 */
	public function pageParam(): string;

	/**
	 * Возвращает имя request-параметра размера страницы.
	 */
	public function pageSizeParam(): string;

	/**
	 * Возвращает ограничение размера страницы:
	 * - true: пользовательский per-page запрещён;
	 * - false: без ограничений;
	 * - int: максимум;
	 * - int[]: whitelist значений.
	 *
	 * @return array<int, int>|bool|int
	 */
	public function pageSizeConstraint(): bool|int|array;

	/**
	 * Возвращает имя request-параметра для направления keyset-пагинации (previous page).
	 */
	public function prevPageParam(): string;

	/**
	 * Возвращает нормализованный фильтровый input.
	 */
	public function filterInput(): FilterInput;

	/**
	 * Возвращает сортировку, полученную из запроса.
	 */
	public function sort(): ?Sort;

	/**
	 * Возвращает селект-опции сортировки, не привязанные к колонкам.
	 *
	 * @return SortOption[]
	 */
	public function sortOptions(): array;

	/**
	 * Возвращает итоговый order в формате yiisoft/data:
	 * ключ сортировки => направление (`asc`/`desc`).
	 *
	 * @return array<string, 'asc'|'desc'>
	 */
	public function effectiveSortOrder(): array;

	/**
	 * Возвращает все фильтры таблицы (включая extra filters).
	 *
	 * @return FilterInterface[]
	 */
	public function filters(): array;

	/**
	 * Возвращает строки текущей страницы таблицы после применения фильтрации и сортировки.
	 *
	 * @return array<int, array<string, mixed>>
	 * @throws InvalidPageException
	 */
	public function rows(): array;

	/**
	 * Возвращает подготовленный reader c применёнными фильтрами/сортировкой.
	 *
	 * Если `$allowAutoWrap=false`, провайдер не будет автоматически оборачивать
	 * совместимый reader в paginator.
	 *
	 * @throws InvalidPageException
	 */
	public function dataReader(bool $allowAutoWrap = true): ReadableDataInterface;
}
