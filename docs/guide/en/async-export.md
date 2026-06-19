# Async export

Use async export when the dataset is large or request timeout is possible.

## Typical flow

1. Request `/products?export=csv` starts sync export.
2. Timeout occurs (`ExportTimeoutException`).
3. Action catches timeout and enqueues a background job with snapshot (`filter`, `sort`).
4. Worker loads the job, recreates the table, and reapplies the snapshot.
5. Worker runs export with `ExportRuntimeOptions`:
   - `disableTimeout=true`
   - cancellation checker
   - rows observer for progress/heartbeat
6. API provides a cancellation endpoint for running jobs.

Queue orchestration is application-level integration. Use
[`mheads/yii-table-export-queue`](https://github.com/mheads-dev/yii-table-export-queue)
when you need ready-made queue-based export orchestration for `mheads/yii-table`.

Export ignores request pagination (`page`, `prev-page`, `per-page`) and runs on the full filtered and sorted dataset.

## Examples

- [examples/export/async-export-action.php](../../../examples/export/async-export-action.php)
- [examples/export/async-export-job-service.php](../../../examples/export/async-export-job-service.php)
- [examples/export/runtime/db-export-cancellation-checker.php](../../../examples/export/runtime/db-export-cancellation-checker.php)
- [examples/export/runtime/db-export-rows-observer.php](../../../examples/export/runtime/db-export-rows-observer.php)

## Queue package

- [`mheads/yii-table-export-queue`](https://github.com/mheads-dev/yii-table-export-queue)
