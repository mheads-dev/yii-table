<?php

declare(strict_types=1);

namespace App\Api\Product;

use Mheads\Yii\Table\Column\Column;
use Mheads\Yii\Table\Export\TableBoundExportGeneratorFactory;
use Mheads\Yii\Table\Export\TableBoundExportOptions;
use Mheads\Yii\Table\Filter\DateFilter;
use Mheads\Yii\Table\Filter\SearchFilter;
use Mheads\Yii\Table\I18n\TableTranslatorInterface;
use Mheads\Yii\Table\Provider\TableProvider;
use Mheads\Yii\Table\Sort\SortDefinition;
use Yiisoft\Data\Db\QueryDataReader;

/**
 * Minimal i18n-focused example:
 * translator injected into TableProvider.
 */
final class ProductListTableFactoryWithI18n
{
    public function __construct(
        private readonly TableTranslatorInterface $translator,
        private readonly TableBoundExportGeneratorFactory $exportFactory = new TableBoundExportGeneratorFactory(),
    ) {}

    public function create(): TableProvider
    {
        // `Product` is placeholder app entity. Replace query and relations with your own model.
        $query = Product::query();

        $table = (new TableProvider(
            id: 'products',
            reader: new QueryDataReader($query),
            translator: $this->translator,
        ))
            ->setPageSize(10)
            ->setPageSizeConstraint([10, 20, 50]);

        $table->addColumn(new Column(
            key: 'id',
            title: 'ID',
            reader: static fn(Product $row): int => (int) $row->getId(),
            isId: true,
            sort: SortDefinition::byField('product.id', SORT_DESC),
        ));

        $table->addColumn(new Column(
            key: 'name',
            title: 'Name',
            reader: static fn(Product $row): string => $row->getName(),
            sort: SortDefinition::byField('product.name'),
            filter: new SearchFilter('name', 'Name', 'product.name'),
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

        $exportOptions = new TableBoundExportOptions(
            fileName: 'products',
        );

        $table->addExportGenerator($this->exportFactory->csv($table, $exportOptions));

        return $table;
    }
}
