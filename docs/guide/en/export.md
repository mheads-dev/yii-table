# Sync export

Table supports export via the `export` query parameter:

```text
/products?export=csv
/products?export=xlsx
```

## Flow

1. The request applier applies table filter and sort parameters.
2. Request pagination (`page`, `prev-page`, `per-page`) is ignored for export.
3. If `export` is present, `TableHttpResponder` calls `TableExportService`.
4. Export streams file response with `Content-Disposition: attachment`.

## Column modes

- `TABLE_ONLY`: export only columns declared in table.
- `CUSTOM_ONLY`: export only `ExportColumn[]` from export config.
- `MERGE`: table columns plus custom export columns.

## Examples

- [examples/export/column-modes.php](../../../examples/export/column-modes.php)
- [examples/export/custom-only-flat-export.php](../../../examples/export/custom-only-flat-export.php)

## Extension points

- custom writer: [examples/export/custom-writer-jsonl.php](../../../examples/export/custom-writer-jsonl.php)
- custom batch strategy: [examples/export/custom-batch-strategy.php](../../../examples/export/custom-batch-strategy.php)

## Error contract

- [HTTP error contract](error-contract.md)
