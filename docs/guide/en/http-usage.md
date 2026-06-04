# HTTP usage

HTTP integration has two steps:

1. Apply query parameters to the table.
2. Render JSON payload or stream export response.

## Orchestrator flow

Use `TableHttpOrchestrator` for typical actions:

- [examples/usage/query-list-action.php](../../../examples/usage/query-list-action.php)

The orchestrator uses:

- `TableRequestApplierInterface`
- `TableHttpResponderInterface`

## Split config, rows, and export

Use split orchestrators when the frontend loads table metadata separately from rows.
This usually creates three HTTP workflows:

- `TableConfigHttpOrchestrator` for `config`, `columns`, `filters`, and `sorts`.
- `TableRowsHttpOrchestrator` for `pagination` and `rows`.
- `TableExportHttpOrchestrator` for export streams.

Config actions can omit the request for a clean config payload, or pass it when request parameters should affect filter payloads. Rows and export actions apply request parameters before producing the response.

- [Config action](../../../examples/usage/product-table-config-action.php)
- [Rows action](../../../examples/usage/product-table-rows-action.php)
- [Export action](../../../examples/usage/product-table-export-action.php)

Use `TableHttpOrchestrator` when one endpoint should return the full table payload and handle export. Use split orchestrators when config, rows, and export have separate routes.

## Direct flow

Use `TableRequestApplier` and `TableHttpResponder` directly when you need custom orchestration:

- [examples/usage/array-list-action.php](../../../examples/usage/array-list-action.php)

## Default query parameters

- `filter`
- `sort`
- `page`
- `prev-page`
- `per-page`
- `export`

## Related topics

- [Filters](filters.md)
- [Sorting](sorting.md)
- [Pagination](pagination.md)
- [Export](export.md)
- [HTTP error contract](error-contract.md)
