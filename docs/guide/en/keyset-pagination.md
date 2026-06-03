# Keyset pagination

Keyset pagination uses page tokens instead of offset page numbers.

Request parameters:

- `page` for forward token;
- `prev-page` for backward token.

`prev-page` has priority over `page` in the request applier.

## Requirements

1. Pass `Yiisoft\Data\Paginator\KeysetPaginator` into `TableProvider`.
2. Configure deterministic sorting because keyset tokens depend on sort order.

## Minimal setup

Assuming `QueryDataReader`, `Sort`, `KeysetPaginator`, and `TableProvider` are imported:

```php
$reader = new QueryDataReader(
    query: $query,
    sort: Sort::only(['id'])->withOrderString('id'),
);
$paginator = (new KeysetPaginator($reader))->withPageSize(10);
$table = new TableProvider('products', $paginator);
```

## Request example

```text
/products?prev-page=eyJpZCI6MTIzfQ&per-page=10&sort=-name
```

## Payload fragment

```json
{
  "config": {
    "pageParam": "page",
    "prevPageParam": "prev-page",
    "pageSizeParam": "per-page"
  },
  "pagination": {
    "type": "keyset",
    "perPage": 10,
    "currentPageSize": 10,
    "nextPageToken": "eyJpZCI6MTAwfQ",
    "prevPageToken": "eyJpZCI6MTIwfQ",
    "isOnFirstPage": false,
    "isOnLastPage": false
  }
}
```
