<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Driver\Common;

use Mheads\Yii\Table\Column\Column;
use Mheads\Yii\Table\Filter\FilterInput;
use Mheads\Yii\Table\Filter\SearchFilter;
use Mheads\Yii\Table\Provider\TableConfiguratorInterface;
use Mheads\Yii\Table\Provider\TableProvider;
use Mheads\Yii\Table\Provider\TableProviderInterface;
use Mheads\Yii\Table\Serialization\TableArraySerializer;
use Mheads\Yii\Table\Sort\SortDefinition;
use Mheads\Yii\Table\Tests\Stubs\Db\DbQueryDataReader;
use Mheads\Yii\Table\Tests\TestCase;
use Yiisoft\Data\Reader\Sort;

abstract class TableProviderContractsTestCase extends TestCase
{
	/**
	 * Проверяет разделение контрактов:
	 * конфигурация через TableConfiguratorInterface,
	 * чтение/сериализация через TableProviderInterface.
	 */
	public function testSeparatesConfiguratorAndRuntimeContracts(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);

		$table = new TableProvider('products', $reader);
		$this->configureTable($table);

		$runtime = $this->toRuntime($table);
		$payload = (new TableArraySerializer())->serialize($runtime);

		self::assertSame(
			[
				'tableId'            => 'products',
				'filterParam'        => 'filter',
				'sortParam'          => 'sort',
				'pageParam'          => 'page',
				'prevPageParam'      => null,
				'pageSizeParam'      => 'per-page',
				'pageSizeConstraint' => false,
				'columnIdKey'        => 'id',
				'exportParam'        => null,
				'exportCodes'        => null,
			],
			$payload['config'],
		);
		self::assertSame([['id' => 6, 'name' => 'Mouse']], $payload['rows']);
	}

	private function configureTable(TableConfiguratorInterface $table): void
	{
		$table->addColumn(
			new Column(
				'id',
				'ID',
				static fn(array $row): int => (int)$row['id'],
				isId: true,
				sort: SortDefinition::byField('id'),
			),
		);
		$table->addColumn(
			new Column(
				'name',
				'Name',
				static fn(array $row): string => (string)$row['name'],
				filter: new SearchFilter(
					key: 'name',
					title: 'Name',
					field: 'name',
					searchMode: SearchFilter::SEARCH_MODE_EQUAL,
				),
			),
		);
		$table->setFilterInput(new FilterInput(['name' => 'Mouse']));
		$table->setSort(Sort::any()->withOrderString('-id'));
		$table->setPageSize(5);
	}

	private function toRuntime(TableProviderInterface $table): TableProviderInterface
	{
		return $table;
	}
}
