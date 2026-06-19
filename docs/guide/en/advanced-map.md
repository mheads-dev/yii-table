# Advanced documentation map

## Custom filters

- [Custom filter example](../../../examples/usage/custom-filter-has-file-checkbox-filter.php)
- [Filters guide](filters.md)
- `Mheads\Yii\Table\Filter\FilterPayloadProviderInterface`

## Custom export formats

- [Custom writer example](../../../examples/export/custom-writer-jsonl.php)

## Export columns and batching

- [Column modes](../../../examples/export/column-modes.php)
- [CUSTOM_ONLY flat export](../../../examples/export/custom-only-flat-export.php)
- [Custom batch strategy](../../../examples/export/custom-batch-strategy.php)

## Async export tooling

- [Async export guide](async-export.md)
- [mheads/yii-table-export-queue](https://github.com/mheads-dev/yii-table-export-queue)
- [Async action](../../../examples/export/async-export-action.php)
- [Async job service](../../../examples/export/async-export-job-service.php)
- [Cancellation checker](../../../examples/export/runtime/db-export-cancellation-checker.php)
- [Rows observer](../../../examples/export/runtime/db-export-rows-observer.php)

## Payload customization

- [Custom payload contract](custom-payload.md)
- `Mheads\Yii\Table\Serialization\TableConfigSerializerInterface`
- `Mheads\Yii\Table\Serialization\TableRowsSerializerInterface`
- `Mheads\Yii\Table\Serialization\TableSerializerInterface`
- `Mheads\Yii\Table\Http\Response\Payload\TablePayloadResponderInterface`

## HTTP orchestration

- [HTTP usage](http-usage.md)
- `Mheads\Yii\Table\Http\Orchestrator\TableConfigHttpOrchestrator`
- `Mheads\Yii\Table\Http\Orchestrator\TableRowsHttpOrchestrator`
- `Mheads\Yii\Table\Http\Orchestrator\TableExportHttpOrchestrator`
- `Mheads\Yii\Table\Http\Orchestrator\TableHttpOrchestrator`

## Existing examples map

- [examples/README.md](../../../examples/README.md)
