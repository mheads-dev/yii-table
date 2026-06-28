# Pagination

Default pagination query parameters:

- `page`
- `per-page`

## Offset pagination

Request example:

```text
/products?per-page=10&page=2
```

Offset pagination response includes:

```json
{
  "pagination": {
    "type": "offset",
    "currentPage": 2,
    "perPage": 10,
    "pageCount": 39,
    "totalCount": 386,
    "nextPageToken": "3",
    "prevPageToken": "1"
  }
}
```

When the source reader has a pre-applied limit, pass the limited reader to `TableProvider`
and let the provider wrap it into `OffsetPaginator` via default auto pagination:

```php
$reader = (new QueryDataReader(query: $query))
    ->withLimit(100);

$table = (new TableProvider('products', $reader))
    ->setPageSize(10);
```

Do not wrap a limited reader into `OffsetPaginator` before passing it to `TableProvider`.
`OffsetPaginator` does not allow changing sort on a reader that already has a limit,
so table sorting will not be applied.

## Page size constraints

Use `setPageSizeConstraint()` to restrict accepted page sizes.

```php
$table
    ->setPageSize(10)
    ->setPageSizeConstraint([10, 20, 50]);
```

## Missing page fallback

By default, `TableProvider` ignores missing pages and falls back to the first available page.
This applies to invalid offset pages and invalid keyset page tokens.

```php
$table->setIgnoreMissingPage(true); // default
```

Disable the fallback when the application should handle invalid pages explicitly:

```php
$table->setIgnoreMissingPage(false);
```

With fallback disabled, `rows()`, `dataReader()`, and HTTP response serialization may throw
`Yiisoft\Data\Paginator\InvalidPageException`. Handle it in application middleware or an error handler.

## Disable auto pagination

Use this when the endpoint should return a full list without paginator metadata or tokens.

```php
$table = (new TableProvider(
    id: 'products',
    reader: $reader,
))
    ->setAutoPagination(false);
```

Result:

- rows are read directly from source reader;
- `pagination` in payload is `null`;
- `config.pageParam`, `config.pageSizeParam`, and `config.pageSizeConstraint` are `null`.

## Keyset pagination

Use keyset pagination for large datasets when stable cursor-based navigation is preferred:

- [Keyset pagination](keyset-pagination.md)
