# Examples

## Example Map

| Scenario | File |
| --- | --- |
| Minimal DI wiring for HTTP flow | [di/common.php](di/common.php) |
| QueryDataReader table factory | [usage/product-list-table-factory.php](usage/product-list-table-factory.php) |
| QueryDataReader table factory with i18n injection | [usage/product-list-table-factory-with-i18n.php](usage/product-list-table-factory-with-i18n.php) |
| IterableDataReader/array table factory | [usage/array-product-list-table-factory.php](usage/array-product-list-table-factory.php) |
| HTTP action via orchestrator | [usage/query-list-action.php](usage/query-list-action.php) |
| HTTP action via request applier + responder directly | [usage/array-list-action.php](usage/array-list-action.php) |
| Custom filter (`with`/`without` nullable file field) | [usage/custom-filter-has-file-checkbox-filter.php](usage/custom-filter-has-file-checkbox-filter.php) |
| Export columns mode (`TABLE_ONLY`/`CUSTOM_ONLY`/`MERGE`) | [export/column-modes.php](export/column-modes.php) |
| Export with only custom flat columns | [export/custom-only-flat-export.php](export/custom-only-flat-export.php) |
| Custom writer (JSONL) | [export/custom-writer-jsonl.php](export/custom-writer-jsonl.php) |
| Custom export batch strategy | [export/custom-batch-strategy.php](export/custom-batch-strategy.php) |
| Sync HTTP timeout -> async queue action pattern | [export/async-export-action.php](export/async-export-action.php) |
| Async queue job service example | [export/async-export-job-service.php](export/async-export-job-service.php) |
| Runtime cancellation checker sample | [export/runtime/db-export-cancellation-checker.php](export/runtime/db-export-cancellation-checker.php) |
| Runtime rows observer sample | [export/runtime/db-export-rows-observer.php](export/runtime/db-export-rows-observer.php) |

## Preparation

1. Add package + optional integrations from root [README.md](../README.md).
2. Wire DI from [di/common.php](di/common.php) into your app container.
3. For `QueryDataReader` examples, use your own DB connection and table schema.
4. For export/XLSX examples, install `ext-xlswriter` if you use XLSX writer.

## How To Use In Project

1. Start with one scenario from the map above, usually `usage/product-list-table-factory.php` plus `usage/query-list-action.php`.
2. Copy code into your app module and replace placeholders with your entities/queries.
3. Keep request contract from the guide:
   - [Filters](../docs/guide/en/filters.md)
   - [Sorting](../docs/guide/en/sorting.md)
   - [Pagination](../docs/guide/en/pagination.md)
   - [Keyset pagination](../docs/guide/en/keyset-pagination.md)
4. Add export flow from `examples/export/*` only after base payload is stable in your endpoint.

## Notes

- Examples are intentionally compact and focused on contract demonstration.
- They are not intended to be executed directly from `vendor/.../examples` without adaptation.
