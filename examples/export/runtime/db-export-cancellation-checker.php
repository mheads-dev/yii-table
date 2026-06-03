<?php

declare(strict_types=1);

namespace App\Export\Runtime;

use Mheads\Yii\Table\Export\Exception\ExportCanceledException;
use Mheads\Yii\Table\Export\Runtime\ExportCancellationCheckerInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * Cooperative cancel checker for background export.
 *
 * Reads job state from app queue storage and throws when cancel requested.
 */
final readonly class DbExportCancellationChecker implements ExportCancellationCheckerInterface
{
    public function __construct(
        private ConnectionInterface $db,
        private string $jobId,
    ) {}

    public function throwIfCanceled(): void
    {
        $row = $this->db->createQuery()
            ->from('export_job')
            ->select(['cancel_requested_at'])
            ->where(['id' => $this->jobId])
            ->one();

        if (is_array($row) && $row['cancel_requested_at'] !== null) {
            throw new ExportCanceledException('Export canceled by user.');
        }
    }
}
