<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Serialization;

/**
 * @psalm-type OffsetPagination=array{
 *     type: 'offset',
 *     currentPage: int,
 *     perPage: int,
 *     totalCount: int,
 *     pageCount: int,
 *     nextPageToken: string|null,
 *     prevPageToken: string|null
 * }
 * @psalm-type KeysetPagination=array{
 *     type: 'keyset',
 *     perPage: int,
 *     currentPageSize: int,
 *     nextPageToken: string|null,
 *     prevPageToken: string|null,
 *     isOnFirstPage: bool,
 *     isOnLastPage: bool
 * }
 * @psalm-type GenericPagination=array{
 *     type: 'generic',
 *     perPage: int,
 *     currentPageSize: int,
 *     nextPageToken: string|null,
 *     prevPageToken: string|null
 * }
 * @psalm-type ColumnSortPayload=array{
 *     isSorted: bool,
 *     isDefault: bool,
 *     sortedDirection: 'ascend'|'descend'|null,
 *     values: array{ascend: string, descend: string}
 * }
 * @psalm-type ColumnPayload=array{
 *     key: string,
 *     title: string,
 *     sort: ColumnSortPayload|null,
 *     isHidden: bool,
 *     filterKey: string|null,
 *     extraFilterKeys: array<int, string>
 * }
 * @psalm-type SortOptionPayload=array{
 *     title: string,
 *     value: string,
 *     isDefault: bool,
 *     isDisabled: bool,
 *     isSelected: bool
 * }
 * @psalm-type DefaultFilterPayload=array{
 *     key: string,
 *     title: string,
 *     caption: string|null,
 *     type: string,
 *     values: mixed,
 *     columnKey: string|null,
 *     ...
 * }
 * @psalm-type TablePayload=array{
 *     config: array{
 *         tableId: string,
 *         filterParam: string,
 *         sortParam: string|null,
 *         pageParam: string|null,
 *         pageSizeParam: string|null,
 *         pageSizeConstraint: bool|int|array<int, int>|null,
 *         prevPageParam: string|null,
 *         columnIdKey: string|null,
 *         exportParam: string|null,
 *         exportCodes: array<int, string>|null
 *     },
 *     pagination: OffsetPagination|KeysetPagination|GenericPagination|null,
 *     columns: array<int, ColumnPayload>,
 *     filters: array<int, array<array-key, mixed>>,
 *     sorts: array<int, SortOptionPayload>,
 *     rows: array<int, array<string, mixed>>
 * }
 */
final class TablePayloadTypes
{
	private function __construct() {}
}
