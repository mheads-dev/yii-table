<?php

declare(strict_types=1);

namespace App\Export\Writer;

use Mheads\Yii\Table\Export\RowsReader\RowsReaderInterface;
use Mheads\Yii\Table\Export\Writer\WriterInterface;
use RuntimeException;

use function fclose;
use function fopen;
use function fwrite;
use function is_resource;
use function is_string;
use function json_encode;

final class JsonLinesWriter implements WriterInterface
{
    public function code(): string
    {
        return 'jsonl';
    }

    public function mimeType(): ?string
    {
        return 'application/x-ndjson';
    }

    public function extension(): string
    {
        return 'jsonl';
    }

    /**
     * @param resource|string $target
     */
    public function write(RowsReaderInterface $rowsReader, mixed $target): void
    {
        [$stream, $mustClose] = $this->openStream($target);

        try {
            foreach ($rowsReader->read() as $row) {
                $line = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
                if (fwrite($stream, $line) === false) {
                    throw new RuntimeException('Unable to write JSONL row.');
                }
            }
        } finally {
            if ($mustClose) {
                fclose($stream);
            }
        }
    }

    /**
     * @return array{0: resource, 1: bool}
     */
    private function openStream(mixed $target): array
    {
        if (is_resource($target)) {
            return [$target, false];
        }

        if (!is_string($target) || $target === '') {
            throw new RuntimeException('Target must be a writable resource or non-empty path string.');
        }

        $stream = fopen($target, 'wb');
        if ($stream === false) {
            throw new RuntimeException('Unable to open target for JSONL export.');
        }

        return [$stream, true];
    }
}

/**
 * Usage with table:
 *
 * $table->addExportGenerator(
 *     (new TableBoundExportGeneratorFactory())->create(
 *         table: $table,
 *         writer: new JsonLinesWriter(),
 *         options: new TableBoundExportOptions(fileName: 'products'),
 *     ),
 * );
 *
 * Request: /products?export=jsonl
 */
