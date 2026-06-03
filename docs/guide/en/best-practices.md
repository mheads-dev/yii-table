# Best practices

## Keep table declaration centralized

Use a table factory as the single place for columns, filters, sort options, page size, and export formats.

## Keep request keys stable

Frontend grids depend on request values such as `filter[name]`, `sort=name_desc`, `page`, and `per-page`. Treat these keys as API contract.

## Prefer explicit page size constraints

Use a whitelist for public endpoints:

```php
$table->setPageSizeConstraint([10, 20, 50]);
```

## Choose pagination intentionally

Use offset pagination for small and medium datasets where total count is useful.

Use keyset pagination for large datasets where cursor navigation is more stable and efficient.

## Keep export columns flat

Frontend row values may contain nested arrays for display. Export values should usually be scalar strings/numbers/dates.

Use `ExportColumn` with `MERGE` or `CUSTOM_ONLY` when display columns are not suitable for files.

## Handle long exports outside request time

Set a sync export timeout and enqueue a background job on `ExportTimeoutException`.

- [Async export](async-export.md)

## Validate custom filters with invalid input

Invalid non-empty `NumberFilter` and `DateFilter` payloads intentionally become an empty-result condition. Keep this behavior visible in API tests.
