<?php

declare(strict_types=1);

use Mheads\Yii\Table\Column\Column;
use Mheads\Yii\Table\Export\BatchStrategy\OffsetLimitBatchReadStrategy;
use Mheads\Yii\Table\Export\TableBoundExportGeneratorFactory;
use Mheads\Yii\Table\Export\TableBoundExportOptions;
use Mheads\Yii\Table\Export\TableExportService;
use Mheads\Yii\Table\Provider\TableProvider;
use Yiisoft\Data\Reader\DataReaderInterface;
use Yiisoft\Data\Reader\Filter\All;
use Yiisoft\Data\Reader\FilterInterface;
use Yiisoft\Data\Reader\Sort;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

/**
 * @implements DataReaderInterface<int, array{id:int,name:string,updatedAt:string}>
 */
final class SyntheticDataReader implements DataReaderInterface
{
	private int $offset = 0;
	private ?int $limit = null;
	private ?Sort $sort = null;
	private FilterInterface $filter;

	public function __construct(
		private readonly int $totalRows,
	) {
		$this->filter = new All();
	}

	public function read(): iterable
	{
		$end = $this->limit === null
			? $this->totalRows
			: min($this->totalRows, $this->offset + $this->limit);

		for ($id = $this->offset + 1; $id <= $end; $id++)
		{
			yield [
				'id'        => $id,
				'name'      => 'Product #' . $id,
				'updatedAt' => '2026-05-13T00:00:00+00:00',
			];
		}
	}

	public function readOne(): array|object|null
	{
		$start = $this->offset + 1;
		if ($start > $this->totalRows)
		{
			return null;
		}

		return [
			'id'        => $start,
			'name'      => 'Product #' . $start,
			'updatedAt' => '2026-05-13T00:00:00+00:00',
		];
	}

	public function withOffset(int $offset): static
	{
		$new = clone $this;
		$new->offset = max(0, $offset);
		return $new;
	}

	public function getOffset(): int
	{
		return $this->offset;
	}

	public function withLimit(?int $limit): static
	{
		$new = clone $this;
		$new->limit = $limit;
		return $new;
	}

	public function getLimit(): ?int
	{
		return $this->limit;
	}

	public function count(): int
	{
		return $this->totalRows;
	}

	public function withSort(?Sort $sort): static
	{
		$new = clone $this;
		$new->sort = $sort;
		return $new;
	}

	public function getSort(): ?Sort
	{
		return $this->sort;
	}

	public function withFilter(FilterInterface $filter): static
	{
		$new = clone $this;
		$new->filter = $filter;
		return $new;
	}

	public function getFilter(): FilterInterface
	{
		return $this->filter;
	}

	public function getIterator(): Traversable
	{
		yield from $this->read();
	}
}

/**
 * @return array{rows:int,batch:int,formats:list<string>}
 */
function parseArgs(): array
{
	$options = getopt('', ['rows::', 'batch::', 'formats::']);

	$rows = isset($options['rows']) ? max(1, (int)$options['rows']) : 100000;
	$batch = isset($options['batch']) ? max(1, (int)$options['batch']) : 2000;
	$formatsRaw = isset($options['formats']) ? (string)$options['formats'] : 'csv,xlsx';

	$parts = array_map('trim', explode(',', $formatsRaw));
	$formats = array_values(array_filter($parts, static fn(string $v): bool => $v !== ''));

	return ['rows' => $rows, 'batch' => $batch, 'formats' => $formats];
}

/**
 * @param list<string> $formats
 */
function createTable(int $rows, int $batch, array $formats): TableProvider
{
	$table = new TableProvider(
		id: 'perf-products',
		reader: new SyntheticDataReader($rows),
	);

	$table->addColumn(new Column(
		key: 'id',
		title: 'ID',
		reader: static fn(array $row): int => (int)$row['id'],
		isId: true,
	));
	$table->addColumn(new Column(
		key: 'name',
		title: 'Name',
		reader: static fn(array $row): string => (string)$row['name'],
	));
	$table->addColumn(new Column(
		key: 'updatedAt',
		title: 'Updated At',
		reader: static fn(array $row): string => (string)$row['updatedAt'],
	));

	$factory = new TableBoundExportGeneratorFactory();
	$options = new TableBoundExportOptions(
		fileName: 'perf-products',
		batchStrategy: new OffsetLimitBatchReadStrategy($batch),
	);

	if (in_array('csv', $formats, true))
	{
		$table->addExportGenerator($factory->csv($table, $options));
	}

	if (in_array('xlsx', $formats, true) && class_exists('Vtiful\\Kernel\\Excel'))
	{
		$table->addExportGenerator($factory->xlsx($table, $options, 'Products'));
	}

	return $table;
}

/**
 * @return array<string,mixed>
 */
function runOne(TableExportService $service, TableProvider $table, string $format): array
{
	$target = tempnam(sys_get_temp_dir(), 'table-perf-');
	if ($target === false)
	{
		throw new RuntimeException('Unable to create temporary target file.');
	}

	if (function_exists('memory_reset_peak_usage'))
	{
		memory_reset_peak_usage();
	}

	$startedAt = microtime(true);
	$service->run($table, $format, $target);
	$duration = microtime(true) - $startedAt;
	$peakBytes = memory_get_peak_usage(true);
	$sizeBytes = filesize($target) ?: 0;

	@unlink($target);

	return [
		'format'          => $format,
		'durationSeconds' => round($duration, 4),
		'peakMemoryMiB'   => round($peakBytes / 1024 / 1024, 2),
		'outputSizeMiB'   => round($sizeBytes / 1024 / 1024, 2),
	];
}

$cfg = parseArgs();
$table = createTable($cfg['rows'], $cfg['batch'], $cfg['formats']);
$service = new TableExportService();

$available = array_map(
	static fn($g): string => $g->code(),
	$table->exportGenerators(),
);

$results = [];
foreach ($available as $format)
{
	$results[] = runOne($service, $table, $format);
}

echo json_encode([
	'meta' => [
		'rows'             => $cfg['rows'],
		'batchSize'        => $cfg['batch'],
		'formatsRequested' => $cfg['formats'],
		'formatsExecuted'  => $available,
	],
	'results' => $results,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
