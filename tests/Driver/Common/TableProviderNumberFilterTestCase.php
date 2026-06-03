<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Driver\Common;

use Mheads\Yii\Table\Column\Column;
use Mheads\Yii\Table\Filter\FilterInput;
use Mheads\Yii\Table\Filter\NumberFilter;
use Mheads\Yii\Table\I18n\TableTranslatorInterface;
use Mheads\Yii\Table\Provider\TableProvider;
use Mheads\Yii\Table\Serialization\TableArraySerializer;
use Mheads\Yii\Table\Sort\SortDefinition;
use Mheads\Yii\Table\Tests\Stubs\Db\DbQueryDataReader;
use Mheads\Yii\Table\Tests\TestCase;
use Yiisoft\Data\Reader\Sort;

abstract class TableProviderNumberFilterTestCase extends TestCase
{
	/**
	 * Проверяет NumberFilter в scalar-входе:
	 * преобразование к exactly и фильтрацию по точному числовому совпадению.
	 */
	public function testNumberFilterSupportsScalarExactlyInput(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);

		$table = new TableProvider('products', $reader);
		$table->addColumn(
			new Column(
				'id',
				'ID',
				static fn(array $row): int => (int)$row['id'],
				isId: true,
				sort: new SortDefinition(['id' => SORT_ASC], ['id' => SORT_DESC]),
				filter: new NumberFilter(
					key: 'id',
					title: 'Id',
					field: 'id',
				),
			),
		);
		$table->addColumn(
			new Column(
				'name',
				'Name',
				static fn(array $row): string => (string)$row['name'],
			),
		);
		$table->setFilterInput(new FilterInput(['id' => '6']));
		$table->setSort(Sort::any()->withOrderString('id'));

		$payload = (new TableArraySerializer())->serialize($table);

		self::assertSame(
			[
				[
					'select' => NumberFilter::SELECT_EXACTLY,
					'number' => '6',
				],
			],
			$payload['filters'][0]['values'],
		);
		self::assertSame('number', $payload['filters'][0]['type']);
		self::assertFalse($payload['filters'][0]['isMultiple']);
		self::assertSame([6], array_column($payload['rows'], 'id'));
	}

	/**
	 * Проверяет NumberFilter в multiple-режиме:
	 * OR между условиями и диапазонную фильтрацию по id.
	 */
	public function testNumberFilterSupportsMultipleRangeRules(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);

		$table = new TableProvider('products', $reader);
		$table->addColumn(
			new Column(
				'id',
				'ID',
				static fn(array $row): int => (int)$row['id'],
				isId: true,
				sort: new SortDefinition(['id' => SORT_ASC], ['id' => SORT_DESC]),
				filter: new NumberFilter(
					key: 'id',
					title: 'Id',
					field: 'id',
					isMultiple: true,
				),
			),
		);
		$table->addColumn(
			new Column(
				'name',
				'Name',
				static fn(array $row): string => (string)$row['name'],
			),
		);
		$table->setFilterInput(
			new FilterInput(
				[
					'id' => [
						['select' => NumberFilter::SELECT_RANGE, 'from' => '2', 'to' => '3'],
						['select' => NumberFilter::SELECT_MORE_THAN, 'from' => '6'],
					],
				],
			),
		);
		$table->setSort(Sort::any()->withOrderString('id'));

		$payload = (new TableArraySerializer())->serialize($table);

		self::assertTrue($payload['filters'][0]['isMultiple']);
		self::assertSame(
			[
				['select' => NumberFilter::SELECT_RANGE, 'from' => '2', 'to' => '3'],
				['select' => NumberFilter::SELECT_MORE_THAN, 'from' => '6'],
			],
			$payload['filters'][0]['values'],
		);
		self::assertSame([2, 3, 7], array_column($payload['rows'], 'id'));
	}

	/**
	 * Проверяет legacy-поведение NumberFilter:
	 * нечисловые значения в values остаются, но в DB-условие не попадают,
	 * и итогом становится пустая выборка (аналог SQL 1=0).
	 */
	public function testNumberFilterReturnsNoRowsWhenValuesAreNonNumeric(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);

		$table = new TableProvider('products', $reader);
		$table->addColumn(
			new Column(
				'id',
				'ID',
				static fn(array $row): int => (int)$row['id'],
				isId: true,
				sort: new SortDefinition(['id' => SORT_ASC], ['id' => SORT_DESC]),
				filter: new NumberFilter(
					key: 'id',
					title: 'Id',
					field: 'id',
				),
			),
		);
		$table->addColumn(
			new Column(
				'name',
				'Name',
				static fn(array $row): string => (string)$row['name'],
			),
		);
		$table->setFilterInput(
			new FilterInput(
				[
					'id' => [
						'select' => NumberFilter::SELECT_EXACTLY,
						'number' => 'abc',
					],
				],
			),
		);

		$payload = (new TableArraySerializer())->serialize($table);

		self::assertSame(
			[
				[
					'select' => NumberFilter::SELECT_EXACTLY,
					'number' => 'abc',
				],
			],
			$payload['filters'][0]['values'],
		);
		self::assertSame([], $payload['rows']);
	}

	public function testNumberFilterSelectLabelsAreLocalizedViaProviderTranslator(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);
		$table = new TableProvider(
			id: 'products',
			reader: $reader,
			translator: new TableProviderNumberFilterTranslatorStub(),
		);

		$table->addColumn(
			new Column(
				'id',
				'ID',
				static fn(array $row): int => (int)$row['id'],
				filter: new NumberFilter(
					key: 'id',
					title: 'Id',
					field: 'id',
				),
			),
		);

		$payload = (new TableArraySerializer())->serialize($table);
		$select = $payload['filters'][0]['select'];

		self::assertSame('T:number_filter.exactly', $select[0]['title']);
		self::assertSame('T:number_filter.more_than', $select[1]['title']);
		self::assertSame('T:number_filter.less_than', $select[2]['title']);
		self::assertSame('T:number_filter.range', $select[3]['title']);
	}

	public function testNumberFilterSelectLabelsFallbackWhenTranslatorReturnsMessageId(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);
		$table = new TableProvider(
			id: 'products',
			reader: $reader,
			translator: new TableProviderNumberFilterPassThroughTranslatorStub(),
		);

		$table->addColumn(
			new Column(
				'id',
				'ID',
				static fn(array $row): int => (int)$row['id'],
				filter: new NumberFilter(
					key: 'id',
					title: 'Id',
					field: 'id',
				),
			),
		);

		$payload = (new TableArraySerializer())->serialize($table);
		$select = $payload['filters'][0]['select'];

		self::assertSame('Exactly', $select[0]['title']);
		self::assertSame('More than', $select[1]['title']);
		self::assertSame('Less than', $select[2]['title']);
		self::assertSame('Range', $select[3]['title']);
	}

}

final class TableProviderNumberFilterTranslatorStub implements TableTranslatorInterface
{
	public function translate(string $message, array $parameters = [], ?string $category = null): string
	{
		return 'T:' . $message;
	}
}

final class TableProviderNumberFilterPassThroughTranslatorStub implements TableTranslatorInterface
{
	public function translate(string $message, array $parameters = [], ?string $category = null): string
	{
		return $message;
	}
}
