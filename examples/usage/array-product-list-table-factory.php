<?php

declare(strict_types=1);

namespace App\Api\Product;

use Mheads\Yii\Table\Column\Column;
use Mheads\Yii\Table\Export\TableBoundExportGeneratorFactory;
use Mheads\Yii\Table\Export\TableBoundExportOptions;
use Mheads\Yii\Table\Provider\TableProvider;
use Mheads\Yii\Table\Sort\SortDefinition;
use Yiisoft\Data\Reader\Iterable\IterableDataReader;

final class ArrayProductListTableFactory
{
    public function __construct(
        private readonly TableBoundExportGeneratorFactory $exportFactory = new TableBoundExportGeneratorFactory(),
    ) {}

    public function create(): TableProvider
    {
        $table = (new TableProvider(
            id: 'products-array',
            reader: new IterableDataReader($this->buildRows()),
        ))->setPageSize(20);

        $table->addColumn(new Column(
            key: 'id',
            title: 'ID',
            reader: static fn(array $row): int => (int) $row['id'],
            isId: true,
            sort: SortDefinition::byField('id', SORT_DESC),
        ));
        $table->addColumn(new Column(
            key: 'name',
            title: 'Name',
            reader: static fn(array $row): string => (string) $row['name'],
            sort: SortDefinition::byField('name'),
        ));
        $table->addColumn(new Column(
            key: 'categoryName',
            title: 'Category',
            reader: static fn(array $row): string => (string) $row['categoryName'],
            sort: SortDefinition::byField('categoryName'),
        ));
        $table->addColumn(new Column(
            key: 'updatedAt',
            title: 'Updated At',
            reader: static fn(array $row): string => (string) $row['updatedAt'],
            sort: SortDefinition::byField('updatedAt'),
        ));

        $exportOptions = new TableBoundExportOptions(fileName: 'products-array');
        $table->addExportGenerator($this->exportFactory->csv($table, $exportOptions));
        $table->addExportGenerator($this->exportFactory->xlsx($table, $exportOptions, 'Products'));

        return $table;
    }

    /**
     * @return list<array{id:int,name:string,categoryName:string,updatedAt:string}>
     */
    private function buildRows(): array
    {
        return [
            ['id' => 1, 'name' => 'Wireless Headphones', 'categoryName' => 'Audio', 'updatedAt' => '2026-05-13T00:00:00+00:00'],
            ['id' => 2, 'name' => 'Gaming Mouse', 'categoryName' => 'Gaming', 'updatedAt' => '2026-05-12T00:00:00+00:00'],
        ];
    }
}
