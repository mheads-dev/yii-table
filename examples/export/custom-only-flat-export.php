<?php

declare(strict_types=1);

namespace App\Api\Product;

use Mheads\Yii\Table\Export\Column\ExportColumn;
use Mheads\Yii\Table\Export\Column\ExportColumnMode;
use Mheads\Yii\Table\Export\TableBoundExportGeneratorFactory;
use Mheads\Yii\Table\Export\TableBoundExportOptions;

/**
 * CUSTOM_ONLY example:
 * export only custom flat columns, ignore visible table columns.
 */
final class CustomOnlyFlatExportExample
{
    public function __construct(
        private readonly ProductListTableFactory $tableFactory,
        private readonly TableBoundExportGeneratorFactory $exportFactory = new TableBoundExportGeneratorFactory(),
    ) {}

    public function configure(): void
    {
        // `ProductListTableFactory`/`Product` are placeholders from app domain.
        $table = $this->tableFactory->create();

        $table->addExportGenerator(
            $this->exportFactory->csv(
                table: $table,
                options: new TableBoundExportOptions(
                    columnsMode: ExportColumnMode::CUSTOM_ONLY,
                    customColumns: [
                        new ExportColumn(
                            key: 'productId',
                            title: 'Product ID',
                            reader: static fn(Product $row): int => (int) $row->getId(),
                        ),
                        new ExportColumn(
                            key: 'productName',
                            title: 'Product Name',
                            reader: static fn(Product $row): string => $row->getName(),
                        ),
                        new ExportColumn(
                            key: 'category',
                            title: 'Category',
                            reader: static fn(Product $row): string => (string) $row->getCategory()?->getName(),
                        ),
                        new ExportColumn(
                            key: 'pictureUrl',
                            title: 'Picture URL',
                            reader: static fn(Product $row): ?string => $row->getPicture()?->getUrl(),
                        ),
                        new ExportColumn(
                            key: 'updatedAt',
                            title: 'Updated At (ISO8601)',
                            reader: static fn(Product $row): ?string => $row->getUpdatedAt()?->format(DATE_ATOM),
                        ),
                    ],
                    fileName: 'products-flat',
                ),
            ),
        );
    }
}
