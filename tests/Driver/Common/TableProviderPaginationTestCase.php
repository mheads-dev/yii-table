<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Driver\Common;

use HttpSoft\Message\ServerRequest;
use Mheads\Yii\Table\Column\Column;
use Mheads\Yii\Table\Http\Request\TableRequestApplier;
use Mheads\Yii\Table\Provider\TableProvider;
use Mheads\Yii\Table\Serialization\TableArraySerializer;
use Mheads\Yii\Table\Sort\SortDefinition;
use Mheads\Yii\Table\Tests\Stubs\Db\DbQueryDataReader;
use Mheads\Yii\Table\Tests\TestCase;
use Yiisoft\Data\Paginator\InvalidPageException;
use Yiisoft\Data\Paginator\KeysetPaginator;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PaginatorInterface;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Db\Query\Query;

abstract class TableProviderPaginationTestCase extends TestCase
{
	/**
	 * Проверяет контракт keyset-pagination в serialized payload.
	 */
	public function testKeysetPaginationContractPayload(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query, sort: Sort::only(['id'])->withOrderString('id'));
		$paginator = (new KeysetPaginator($reader))->withPageSize(3);

		$table = new TableProvider('products', $paginator);
		$table->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id'], isId: true, sort: new SortDefinition(['id' => SORT_ASC], ['id' => SORT_DESC])));
		$table->addColumn(new Column('name', 'Name', static fn(array $row): string => (string)$row['name']));

		$payload = (new TableArraySerializer())->serialize($table);

		self::assertSame('prev-page', $payload['config']['prevPageParam']);
		self::assertSame('keyset', $payload['pagination']['type']);
		self::assertSame(3, $payload['pagination']['perPage']);
		self::assertSame(3, $payload['pagination']['currentPageSize']);
		self::assertArrayHasKey('nextPageToken', $payload['pagination']);
		self::assertArrayHasKey('prevPageToken', $payload['pagination']);
		self::assertArrayHasKey('isOnFirstPage', $payload['pagination']);
	}

	public function testAutoPaginationEnabledWrapsReader(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);

		$table = new TableProvider('products', $reader);
		$table
			->setPageSize(2)
			->setPage(2)
			->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id']));

		$preparedReader = $table->dataReader();

		self::assertInstanceOf(PaginatorInterface::class, $preparedReader);
		self::assertInstanceOf(OffsetPaginator::class, $preparedReader);
		self::assertSame(2, $preparedReader->getPageSize());
		self::assertSame(2, $preparedReader->getCurrentPage());
		self::assertCount(2, $table->rows());
	}

	public function testAutoPaginationDisabledKeepsReaderUnwrapped(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);

		$table = new TableProvider('products', $reader);
		$table
			->setAutoPagination(false)
			->setPageSize(2)
			->setPage(2)
			->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id']));

		$preparedReader = $table->dataReader();

		self::assertNotInstanceOf(PaginatorInterface::class, $preparedReader);
		self::assertCount(7, $table->rows());
	}

	public function testKeysetPaginationWithLogicalSortKeyUsesRealFieldInFilter(): void
	{
		$query = (new Query(self::db()))
			->from(['p' => 'product'])
			->leftJoin(['cp' => 'composite_product'], 'cp.id = p.id');

		$reader = DbQueryDataReader::create(
			$query,
			sort: Sort::any()->withOrder(['p.id' => 'desc']),
		);
		$paginator = (new KeysetPaginator($reader))->withPageSize(2);

		$table = new TableProvider('products', $paginator);
		$table
			->addColumn(new Column(
				'id',
				'ID',
				static fn(array $row): int => (int)$row['id'],
				sort: SortDefinition::byField('p.id'),
			))
			->addColumn(new Column(
				'name',
				'Name',
				static fn(array $row): string => (string)$row['name'],
				sort: SortDefinition::byField('p.name'),
			))
			->setSort(Sort::any()->withOrderString('name'))
			->setPage('Monitor');

		$rows = $table->rows();
		self::assertNotEmpty($rows);
	}

	public function testPageSizeConstraintTrueIgnoresUserValue(): void
	{
		$table = $this->createOffsetTableWithDefaultPageSize(5);
		$table->setPageSizeConstraint(true)->setPageSize('2');

		$this->assertEffectivePageSize($table, 5);
	}

	public function testPageSizeConstraintFalseAllowsAnyPositiveValue(): void
	{
		$table = $this->createOffsetTableWithDefaultPageSize(5);
		$table->setPageSizeConstraint(false)->setPageSize('7');

		$this->assertEffectivePageSize($table, 7);
	}

	public function testPageSizeConstraintIntCapsDefaultAndRejectsTooLargeRequestedValue(): void
	{
		$table = $this->createOffsetTableWithDefaultPageSize(10);
		$table->setPageSizeConstraint(4);
		$this->assertEffectivePageSize($table, 4);

		$table->setPageSize(6);
		$this->assertEffectivePageSize($table, 4);

		$table->setPageSize(3);
		$this->assertEffectivePageSize($table, 3);
	}

	public function testPageSizeConstraintWhitelistUsesFirstAsDefaultAndValidatesRequestedValue(): void
	{
		$table = $this->createOffsetTableWithDefaultPageSize(10);
		$table->setPageSizeConstraint([2, 5, 20]);
		$this->assertEffectivePageSize($table, 2);

		$table->setPageSize('5');
		$this->assertEffectivePageSize($table, 5);

		$table->setPageSize(9);
		$this->assertEffectivePageSize($table, 2);
	}

	public function testInvalidUserPageSizeFallsBackToDefaultWithConstraintApplied(): void
	{
		$table = $this->createOffsetTableWithDefaultPageSize(8);
		$table->setPageSizeConstraint(6);

		$table->setPageSize(0);
		$this->assertEffectivePageSize($table, 6);

		$table->setPageSize(-1);
		$this->assertEffectivePageSize($table, 6);

		$table->setPageSize('abc');
		$this->assertEffectivePageSize($table, 6);
	}

	public function testRequestApplierPassesRawPerPageToTableProvider(): void
	{
		$table = $this->createOffsetTableWithDefaultPageSize(5);
		$table->setPageSizeConstraint(false);

		$request = (new ServerRequest())->withQueryParams([
			'per-page' => 'abc',
		]);

		(new TableRequestApplier())->apply($table, $request);
		$this->assertEffectivePageSize($table, 5);
	}

	public function testRawStringPageSizeIsNormalizedViaIntCastLikeBaseListView(): void
	{
		$table = $this->createOffsetTableWithDefaultPageSize(5);
		$table->setPageSizeConstraint(false)->setPageSize('12foo');

		$this->assertEffectivePageSize($table, 12);
	}

	public function testRequestApplierDoesNotResetDeclaredPageSizeWhenPerPageIsMissing(): void
	{
		$table = $this->createOffsetTableWithDefaultPageSize(5);
		$table->setPageSizeConstraint(false)->setPageSize(30);

		$request = (new ServerRequest())->withQueryParams([
			'page' => 1,
		]);

		(new TableRequestApplier())->apply($table, $request);
		$this->assertEffectivePageSize($table, 30);
	}

	public function testIgnoreMissingPageFallsBackToFirstPage(): void
	{
		$table = $this->createOffsetTableWithDefaultPageSize(2);
		$table->setPage(999);

		$rows = $table->rows();
		self::assertCount(2, $rows);
		self::assertSame(1, $rows[0]['id']);
		self::assertSame(2, $rows[1]['id']);
	}

	public function testDisableIgnoreMissingPageThrowsInvalidPageException(): void
	{
		$table = $this->createOffsetTableWithDefaultPageSize(2);
		$table->setIgnoreMissingPage(false)->setPage(999);

		$this->expectException(InvalidPageException::class);
		$table->rows();
	}

	private function createOffsetTableWithDefaultPageSize(int $pageSize): TableProvider
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);
		$paginator = (new OffsetPaginator($reader))->withPageSize($pageSize);

		return (new TableProvider('products', $paginator))
			->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id']));
	}

	private function assertEffectivePageSize(TableProvider $table, int $expectedPageSize): void
	{
		$reader = $table->dataReader();
		self::assertInstanceOf(PaginatorInterface::class, $reader);
		self::assertSame($expectedPageSize, $reader->getPageSize());
	}
}
