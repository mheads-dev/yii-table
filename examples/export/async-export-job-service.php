<?php

declare(strict_types=1);

namespace App\Export;

use App\Api\Product\ProductListTableFactory;
use App\Export\Runtime\DbExportCancellationChecker;
use App\Export\Runtime\DbExportRowsObserver;
use Mheads\Yii\Table\Export\Runtime\ExportRuntimeOptions;
use Mheads\Yii\Table\Export\TableExportService;
use Mheads\Yii\Table\Provider\TableConfiguratorInterface;
use Mheads\Yii\Table\Provider\TableProviderInterface;

/**
 * Minimal example of background export processing.
 *
 * Queue storage, locking and retries are app responsibility.
 */
final readonly class ExportJobService
{
    public function __construct(
        // Reuse your table factory from examples/usage.
        private ProductListTableFactory $tableFactory,
        private TableExportService $exportService = new TableExportService(),
    ) {}

    public function runOne(string $exportCode, string $targetPath): void
    {
        $table = $this->tableFactory->create();

        // Apply snapshot (filter/sort/pageSize) captured at enqueue time.
        $this->applySnapshot($table);

        $this->exportService->run(
            provider: $table,
            code: $exportCode,
            target: $targetPath,
            runtimeOptions: new ExportRuntimeOptions(
                disableTimeout: true,
                cancellationChecker: new DbExportCancellationChecker(/* db */, /* jobId */),
                rowsReadObserver: new DbExportRowsObserver(/* db */, /* jobId */),
                cancellationCheckEachRows: 500,
                observerNotifyEachRows: 500,
            ),
        );
    }

    private function applySnapshot(TableProviderInterface&TableConfiguratorInterface $table): void
    {
        // Example:
        // $table->setFilterInput(new FilterInput($snapshot['filter'] ?? []));
        // $table->setSort(Sort::any()->withOrderString((string)($snapshot['sort'] ?? '')));
        // $table->setPageSize((int)$snapshot['pageSize']);
    }
}
