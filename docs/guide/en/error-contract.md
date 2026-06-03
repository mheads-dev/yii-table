# HTTP error contract

Default non-export responses come from the configured payload responder.

Export errors are mapped by `TableHttpResponder` to payload:

```json
{"errorCode":"...","errorMessage":"..."}
```

## Export errors

| Case | `errorCode` | HTTP status |
| --- | --- | --- |
| Unknown export format | `unsupported_format` | `400` |
| Export timeout | `timeout` | `422` |
| Other export failure | `export_failed` | `422` |

## Notes

- Per-call override is possible via `onExportException` callback in orchestrator/responder.
- `ExportCanceledException` is mapped as `export_failed` by default unless you intercept it with `onExportException`.
- Missing pages fall back to the first available page by default. If you call `setIgnoreMissingPage(false)`, invalid page numbers or keyset tokens may throw `InvalidPageException`; map it in application-level middleware/error handler.
