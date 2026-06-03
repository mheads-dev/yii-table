<?php

declare(strict_types=1);

namespace App\Export\BatchStrategy;

use Mheads\Yii\Table\Export\BatchStrategy\BatchStrategyInterface;
use Yiisoft\Data\Reader\DataReaderInterface;
use Yiisoft\Data\Reader\ReadableDataInterface;

/**
 * Example custom batching strategy on top of generic DataReaderInterface.
 */
final readonly class CustomOffsetBatchStrategy implements BatchStrategyInterface
{
    /**
     * @param positive-int $batchSize
     */
    public function __construct(
        private int $batchSize = 1000,
    ) {}

    public function canRead(ReadableDataInterface $reader): bool
    {
        return $reader instanceof DataReaderInterface;
    }

    public function readBatched(ReadableDataInterface $reader): iterable
    {
        if (!$reader instanceof DataReaderInterface) {
            yield from $reader->read();
            return;
        }

        $offset = 0;
        while (true) {
            $chunkReader = $reader
                ->withOffset($offset)
                ->withLimit($this->batchSize);

            $chunkCount = 0;
            foreach ($chunkReader->read() as $entity) {
                yield $entity;
                $chunkCount++;
            }

            if ($chunkCount === 0 || $chunkCount < $this->batchSize) {
                return;
            }

            $offset += $chunkCount;
        }
    }
}

/**
 * Usage in export options:
 *
 * $table->addExportGenerator(
 *     (new TableBoundExportGeneratorFactory())->csv(
 *         table: $table,
 *         options: new TableBoundExportOptions(
 *             batchStrategy: new CustomOffsetBatchStrategy(2000),
 *         ),
 *     ),
 * );
 */
