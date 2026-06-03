<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Driver\Common;

use DateTimeImmutable;
use Mheads\Yii\Table\Column\Column;
use Mheads\Yii\Table\Filter\DateFilter;
use Mheads\Yii\Table\Filter\DateFilterPreset;
use Mheads\Yii\Table\Filter\FilterInput;
use Mheads\Yii\Table\I18n\TableTranslatorInterface;
use Mheads\Yii\Table\Provider\TableProvider;
use Mheads\Yii\Table\Serialization\TableArraySerializer;
use Mheads\Yii\Table\Sort\SortDefinition;
use Mheads\Yii\Table\Tests\Stubs\Db\DbQueryDataReader;
use Mheads\Yii\Table\Tests\TestCase;
use Yiisoft\Data\Reader\Sort;

abstract class TableProviderDateFilterTestCase extends TestCase
{
	/**
	 * Проверяет DateFilter для точной даты (dd.mm.YYYY -> Y-m-d) и сериализацию values.
	 */
	public function testDateFilterSupportsExactDate(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);

		$table = new TableProvider('products', $reader);
		$table->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id'], isId: true, sort: new SortDefinition(['id' => SORT_ASC], ['id' => SORT_DESC])));
		$table->addColumn(
			new Column(
				'createdAt',
				'Created At',
				static fn(array $row): string => (string)$row['created_at'],
				filter: new DateFilter(
					key: 'createdAt',
					title: 'Created At',
					field: 'created_at',
				),
			),
		);
		$table->setFilterInput(new FilterInput(['createdAt' => '02.01.2026']));
		$table->setSort(Sort::any()->withOrderString('id'));

		$payload = (new TableArraySerializer())->serialize($table);

		self::assertSame(
			[['select' => DateFilter::SELECT_DATE, 'date' => '02.01.2026']],
			$payload['filters'][0]['values'],
		);
		self::assertSame([2], array_column($payload['rows'], 'id'));
	}

	/**
	 * Проверяет DateFilter диапазоном и множественными правилами (OR), включая поведение None для невалидных значений.
	 */
	public function testDateFilterSupportsRangeAndInvalidInput(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);

		$table = new TableProvider('products', $reader);
		$table->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id'], isId: true, sort: new SortDefinition(['id' => SORT_ASC], ['id' => SORT_DESC])));
		$table->addColumn(
			new Column(
				'createdAt',
				'Created At',
				static fn(array $row): string => (string)$row['created_at'],
				filter: new DateFilter(
					key: 'createdAt',
					title: 'Created At',
					field: 'created_at',
					isMultiple: true,
				),
			),
		);
		$table->setFilterInput(
			new FilterInput(
				[
					'createdAt' => [
						['from' => '03.01.2026', 'to' => '04.01.2026'],
						['select' => DateFilter::SELECT_DATE, 'date' => 'bad-date'],
					],
				],
			),
		);
		$table->setSort(Sort::any()->withOrderString('id'));

		$payload = (new TableArraySerializer())->serialize($table);

		self::assertSame(
			[
				['select' => DateFilter::SELECT_RANGE_DATE, 'from' => '03.01.2026', 'to' => '04.01.2026'],
			],
			$payload['filters'][0]['values'],
		);
		self::assertSame([3, 4], array_column($payload['rows'], 'id'));
	}

	/**
	 * Проверяет поведение DateFilter: если input полностью невалиден, фильтр не применяется.
	 */
	public function testDateFilterSkipsInvalidValues(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);

		$table = new TableProvider('products', $reader);
		$table->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id'], isId: true, sort: new SortDefinition(['id' => SORT_ASC], ['id' => SORT_DESC])));
		$table->addColumn(
			new Column(
				'createdAt',
				'Created At',
				static fn(array $row): string => (string)$row['created_at'],
				filter: new DateFilter(
					key: 'createdAt',
					title: 'Created At',
					field: 'created_at',
				),
			),
		);
		$table->setFilterInput(
			new FilterInput(
				[
					'createdAt' => [
						'select' => DateFilter::SELECT_DATE,
						'date'   => '2026-01-05',
					],
				],
			),
		);

		$payload = (new TableArraySerializer())->serialize($table);

		self::assertNull($payload['filters'][0]['values']);
		self::assertSame([1, 2, 3, 4, 5, 6, 7], array_column($payload['rows'], 'id'));
	}

	/**
	 * Проверяет DateFilter с valueType=date_time: exact date преобразуется в диапазон суток.
	 */
	public function testDateFilterSupportsDateTimeStorageType(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);

		$table = new TableProvider('products', $reader);
		$table->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id'], isId: true, sort: new SortDefinition(['id' => SORT_ASC], ['id' => SORT_DESC])));
		$table->addColumn(
			new Column(
				'createdAtDt',
				'Created At Dt',
				static fn(array $row): string => (string)$row['created_at_dt'],
				filter: new DateFilter(
					key: 'createdAtDt',
					title: 'Created At Dt',
					field: 'created_at_dt',
					valueType: DateFilter::TYPE_DATE_TIME,
				),
			),
		);
		$table->setFilterInput(new FilterInput(['createdAtDt' => '02.01.2026']));

		$payload = (new TableArraySerializer())->serialize($table);

		self::assertSame([2], array_column($payload['rows'], 'id'));
	}

	/**
	 * Проверяет DateFilter с valueType=unix: range_date преобразуется в unix-границы суток.
	 */
	public function testDateFilterSupportsUnixStorageType(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);

		$table = new TableProvider('products', $reader);
		$table->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id'], isId: true, sort: new SortDefinition(['id' => SORT_ASC], ['id' => SORT_DESC])));
		$table->addColumn(
			new Column(
				'createdAtTs',
				'Created At Ts',
				static fn(array $row): int => (int)$row['created_at_ts'],
				filter: new DateFilter(
					key: 'createdAtTs',
					title: 'Created At Ts',
					field: 'created_at_ts',
					valueType: DateFilter::TYPE_UNIX,
				),
			),
		);
		$table->setFilterInput(new FilterInput(['createdAtTs' => ['from' => '03.01.2026', 'to' => '04.01.2026']]));

		$payload = (new TableArraySerializer())->serialize($table);

		self::assertSame([3, 4], array_column($payload['rows'], 'id'));
	}

	/**
	 * Проверяет пресет today с фиксированным now.
	 */
	public function testDateFilterSupportsTodayPreset(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);
		$dateFilter = new DateFilter(
			key: 'createdAt',
			title: 'Created At',
			field: 'created_at',
			nowProvider: static fn(): DateTimeImmutable => new DateTimeImmutable('2026-01-03 12:00:00'),
		);
		$dateFilter->addPreset(DateFilter::presetToday());

		$table = new TableProvider('products', $reader);
		$table->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id'], isId: true, sort: new SortDefinition(['id' => SORT_ASC], ['id' => SORT_DESC])));
		$table->addColumn(
			new Column(
				'createdAt',
				'Created At',
				static fn(array $row): string => (string)$row['created_at'],
				filter: $dateFilter,
			),
		);
		$table->setFilterInput(new FilterInput(['createdAt' => ['select' => DateFilter::SELECT_TODAY]]));

		$payload = (new TableArraySerializer())->serialize($table);

		$selectMap = [];
		foreach ($payload['filters'][0]['select'] as $item)
		{
			$selectMap[$item['select']] = $item['title'];
		}

		self::assertSame('Today', $selectMap[DateFilter::SELECT_TODAY] ?? null);
		self::assertSame([3], array_column($payload['rows'], 'id'));
	}

	/**
	 * Проверяет пресет current_week с фиксированным now.
	 */
	public function testDateFilterSupportsCurrentWeekPreset(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);
		$dateFilter = new DateFilter(
			key: 'createdAt',
			title: 'Created At',
			field: 'created_at',
			nowProvider: static fn(): DateTimeImmutable => new DateTimeImmutable('2026-01-08 12:00:00'),
		);
		$dateFilter->addPreset(DateFilter::presetCurrentWeek());

		$table = new TableProvider('products', $reader);
		$table->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id'], isId: true, sort: new SortDefinition(['id' => SORT_ASC], ['id' => SORT_DESC])));
		$table->addColumn(
			new Column(
				'createdAt',
				'Created At',
				static fn(array $row): string => (string)$row['created_at'],
				filter: $dateFilter,
			),
		);
		$table->setFilterInput(new FilterInput(['createdAt' => ['select' => DateFilter::SELECT_CURRENT_WEEK]]));

		$payload = (new TableArraySerializer())->serialize($table);

		self::assertSame([5, 6, 7], array_column($payload['rows'], 'id'));
	}

	/**
	 * Проверяет пользовательский пресет: добавление в select и применение диапазона.
	 */
	public function testDateFilterSupportsCustomPreset(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);

		$dateFilter = new DateFilter(
			key: 'createdAt',
			title: 'Created At',
			field: 'created_at',
		);
		$dateFilter->addPreset(
			new DateFilterPreset(
				select: 'first_quarter',
				messageId: 'date_filter.first_quarter',
				title: 'First quarter',
				valueNormalizer: static fn(array $value): ?array => ($value['select'] ?? null) === 'first_quarter'
					? ['select' => 'first_quarter']
					: null,
				rangeResolver: static fn(array $_, DateTimeImmutable $now): array => [
					$now->setDate((int)$now->format('Y'), 1, 1),
					$now->setDate((int)$now->format('Y'), 3, 31),
				],
			),
		);

		$table = new TableProvider('products', $reader);
		$table->addColumn(new Column('id', 'ID', static fn(array $row): int => (int)$row['id'], isId: true, sort: new SortDefinition(['id' => SORT_ASC], ['id' => SORT_DESC])));
		$table->addColumn(
			new Column(
				'createdAt',
				'Created At',
				static fn(array $row): string => (string)$row['created_at'],
				filter: $dateFilter,
			),
		);
		$table->setFilterInput(new FilterInput(['createdAt' => ['select' => 'first_quarter']]));

		$payload = (new TableArraySerializer())->serialize($table);

		$selectMap = [];
		foreach ($payload['filters'][0]['select'] as $item)
		{
			$selectMap[$item['select']] = $item['title'];
		}

		self::assertSame('First quarter', $selectMap['first_quarter'] ?? null);
		self::assertSame([1, 2, 3, 4, 5, 6, 7], array_column($payload['rows'], 'id'));
	}

	public function testDateFilterSelectLabelsAreLocalizedViaProviderTranslator(): void
	{
		$query = self::db()->createQuery()->from('product');
		$reader = DbQueryDataReader::create($query);
		$dateFilter = new DateFilter(
			key: 'createdAt',
			title: 'Created At',
			field: 'created_at',
		);
		$dateFilter->addPreset(DateFilter::presetToday());
		$dateFilter->addPreset(DateFilter::presetYesterday());
		$dateFilter->addPreset(DateFilter::presetTomorrow());
		$dateFilter->addPreset(DateFilter::presetCurrentWeek());
		$dateFilter->addPreset(DateFilter::presetPreviousWeek());
		$dateFilter->addPreset(DateFilter::presetNextWeek());

		$table = new TableProvider(
			id: 'products',
			reader: $reader,
			translator: new TableProviderDateFilterTranslatorStub(),
		);
		$table->addColumn(
			new Column(
				'createdAt',
				'Created At',
				static fn(array $row): string => (string)$row['created_at'],
				filter: $dateFilter,
			),
		);

		$payload = (new TableArraySerializer())->serialize($table);
		$selectMap = [];
		foreach ($payload['filters'][0]['select'] as $item)
		{
			$selectMap[$item['select']] = $item['title'];
		}

		self::assertSame('T:date_filter.exact', $selectMap[DateFilter::SELECT_DATE] ?? null);
		self::assertSame('T:date_filter.range', $selectMap[DateFilter::SELECT_RANGE_DATE] ?? null);
		self::assertSame('T:date_filter.today', $selectMap[DateFilter::SELECT_TODAY] ?? null);
		self::assertSame('T:date_filter.yesterday', $selectMap[DateFilter::SELECT_YESTERDAY] ?? null);
		self::assertSame('T:date_filter.tomorrow', $selectMap[DateFilter::SELECT_TOMORROW] ?? null);
		self::assertSame('T:date_filter.current_week', $selectMap[DateFilter::SELECT_CURRENT_WEEK] ?? null);
		self::assertSame('T:date_filter.previous_week', $selectMap[DateFilter::SELECT_PREVIOUS_WEEK] ?? null);
		self::assertSame('T:date_filter.next_week', $selectMap[DateFilter::SELECT_NEXT_WEEK] ?? null);
	}
}

final class TableProviderDateFilterTranslatorStub implements TableTranslatorInterface
{
	public function translate(string $id, array $parameters = []): string
	{
		return 'T:' . $id;
	}
}
