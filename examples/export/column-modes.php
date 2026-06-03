<?php

declare(strict_types=1);

namespace App\Api\Product;

use Mheads\Yii\Table\Export\Column\ExportColumn;
use Mheads\Yii\Table\Export\Column\ExportColumnMode;
use Mheads\Yii\Table\Export\TableBoundExportGeneratorFactory;
use Mheads\Yii\Table\Export\TableBoundExportOptions;

final class ExportColumnModesExample
{
    public function __construct(
        private readonly ProductListTableFactory $tableFactory,
        private readonly TableBoundExportGeneratorFactory $exportFactory = new TableBoundExportGeneratorFactory(),
    ) {}

    public function configure(): void
    {
        // `ProductListTableFactory`/`Product` are placeholders from app domain.
        $table = $this->tableFactory->create();

        $customColumns = [
            new ExportColumn(
                key: 'pictureUrl',
                title: 'Picture URL',
                reader: static fn(Product $row): ?string => $row->getPicture()?->getUrl(),
            ),
        ];

        // Select one mode for current generator.
        // Switch to TABLE_ONLY / CUSTOM_ONLY / MERGE as needed.
        $mode = ExportColumnMode::MERGE;

        $table->addExportGenerator($this->exportFactory->csv(
            $table,
            new TableBoundExportOptions(
                columnsMode: $mode,
                customColumns: $customColumns,
                fileName: 'products-export',
            ),
        ));
    }
}
