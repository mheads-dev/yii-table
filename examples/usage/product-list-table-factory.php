<?php

declare(strict_types=1);

namespace App\Api\Product;

use App\Api\Product\Filter\HasFileCheckboxFilter;
use Mheads\Yii\Table\Column\Column;
use Mheads\Yii\Table\Export\BatchStrategy\QueryDataReaderBatchReadStrategy;
use Mheads\Yii\Table\Export\Column\ExportColumn;
use Mheads\Yii\Table\Export\Column\ExportColumnMode;
use Mheads\Yii\Table\Export\TableBoundExportGeneratorFactory;
use Mheads\Yii\Table\Export\TableBoundExportOptions;
use Mheads\Yii\Table\Filter\DateFilter;
use Mheads\Yii\Table\Filter\NumberFilter;
use Mheads\Yii\Table\Filter\SearchFilter;
use Mheads\Yii\Table\Filter\SelectFilter;
use Mheads\Yii\Table\Provider\TableProvider;
use Mheads\Yii\Table\Sort\SortDefinition;
use Mheads\Yii\Table\Sort\SortOption;
use Yiisoft\Data\Db\QueryDataReader;

final class ProductListTableFactory
{
    public function __construct(
        private readonly TableBoundExportGeneratorFactory $exportFactory = new TableBoundExportGeneratorFactory(),
    ) {}

    public function create(): TableProvider
    {
        // `Product` is placeholder app entity. Replace query and relations with your own model.
        $query = Product::query()
            ->joinWith('category')
            ->joinWith('picture');

        $table = (new TableProvider(
            id: 'products',
            reader: new QueryDataReader($query),
        ))
            ->setPageSize(10)
            ->setPageSizeConstraint([10, 20, 50]);

        $table->addSortOption(new SortOption('name_desc', 'Sorting by Z-A', SortDefinition::fixedOrder(['product.name' => SORT_DESC], SORT_DESC)));
        $table->addSortOption(new SortOption('name_asc', 'Sorting by A-Z', SortDefinition::fixedOrder(['product.name' => SORT_ASC], SORT_ASC)));

        $table->addColumn(new Column(
            key: 'id',
            title: 'ID',
            reader: static fn(Product $row): int => (int) $row->getId(),
            isId: true,
            sort: SortDefinition::byField('product.id', SORT_DESC),
            filter: new NumberFilter('id', 'ID', 'product.id'),
        ));

        $table->addColumn(new Column(
            key: 'dateUpdate',
            title: 'Updated At',
            reader: static fn(Product $row): ?string => $row->getUpdatedAt()?->format(DATE_ATOM),
            sort: SortDefinition::byField('product.updated_at'),
            filter: new DateFilter(
                key: 'dateUpdate',
                title: 'Updated At',
                field: 'product.updated_at',
                valueType: DateFilter::TYPE_DATE_TIME,
            ),
        ));

        $table->addColumn(new Column(
            key: 'name',
            title: 'Name',
            reader: static fn(Product $row): string => $row->getName(),
            sort: SortDefinition::byField('product.name'),
            filter: new SearchFilter('name', 'Name', 'product.name'),
        ));

        $table->addColumn(new Column(
            key: 'categoryName',
            title: 'Category',
            reader: static fn(Product $row): ?string => $row->getCategory()?->getName(),
            sort: SortDefinition::byField('category.name'),
            filter: new SelectFilter(
                key: 'categoryName',
                title: 'Category',
                field: 'category.name',
                options: fn(): array => [
                    ['label' => 'Audio', 'value' => 'Audio'],
                    ['label' => 'Gaming', 'value' => 'Gaming'],
                ],
            ),
        ));

        $table->addColumn(new Column(
            key: 'pictureUrl',
            title: 'Picture',
            reader: static fn(Product $row): ?array => ($picture = $row->getPicture()) ? [
                'id' => $picture->getId(),
                'url' => $picture->getUrl(),
            ] : null,
            filter: new HasFileCheckboxFilter(
                key: 'pictureUrl',
                title: 'Picture',
                field: 'product.picture_id',
            ),
        ));

        $exportOptions = new TableBoundExportOptions(
            columnsMode: ExportColumnMode::MERGE,
            customColumns: [
                new ExportColumn(
                    key: 'pictureUrl',
                    title: 'Picture URL',
                    reader: static fn(Product $row): ?string => $row->getPicture()?->getUrl(),
                ),
            ],
            timeoutSeconds: 5,
            fileName: 'products',
            batchStrategy: new QueryDataReaderBatchReadStrategy(1000),
        );

        $table->addExportGenerator($this->exportFactory->csv($table, $exportOptions));
        $table->addExportGenerator($this->exportFactory->xlsx($table, $exportOptions, 'Products'));

        return $table;
    }
}
