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
