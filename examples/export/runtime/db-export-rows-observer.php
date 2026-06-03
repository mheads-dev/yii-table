<?php

declare(strict_types=1);

namespace App\Export\Runtime;

use Mheads\Yii\Table\Export\Runtime\RowsReadObserverInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

use function date;

/**
 * Progress/heartbeat observer for background export.
 */
final readonly class DbExportRowsObserver implements RowsReadObserverInterface
{
    public function __construct(
        private ConnectionInterface $db,
        private string $jobId,
    ) {}

    public function onStart(): void
    {
        $this->touch(0);
    }

    public function onRowsRead(int $rowsDelta, int $rowsTotal): void
    {
        $this->touch($rowsTotal);
    }

    public function onFinish(int $rowsTotal): void
    {
        $this->touch($rowsTotal);
    }

    private function touch(int $processedRows): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->createCommand()->update(
            'export_job',
            ['heartbeat_at' => $now, 'processed_rows' => $processedRows, 'updated_at' => $now],
            ['id' => $this->jobId],
        )->execute();
    }
}
