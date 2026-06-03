<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Driver\Common;

use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Message\StreamFactory;
use Mheads\Yii\Table\Column\Column;
use Mheads\Yii\Table\Filter\SearchFilter;
use Mheads\Yii\Table\Http\Orchestrator\TableHttpOrchestrator;
use Mheads\Yii\Table\Http\Request\TableRequestApplier;
use Mheads\Yii\Table\Http\Response\TableHttpResponder;
use Mheads\Yii\Table\Provider\TableProvider;
use Mheads\Yii\Table\Sort\SortDefinition;
use Mheads\Yii\Table\Tests\Stubs\ActiveRecord\Product;
use Mheads\Yii\Table\Tests\Stubs\Db\DbQueryDataReader;
use Mheads\Yii\Table\Tests\TestCase;
use Yiisoft\Data\Paginator\OffsetPaginator;

use function dirname;
use function file_get_contents;
use function json_decode;

abstract class TableHttpOrchestratorPayloadGoldenFileTestCase extends TestCase
{
	public function testMatchesGoldenPayloadForArRelationHttpFlow(): void
	{
		$query = Product::query()->joinWith('category');
		$reader = DbQueryDataReader::create($query);
		$paginator = (new OffsetPaginator($reader))->withPageSize(10);

		$table = new TableProvider('ar-products-http', $paginator);
		$table->addColumn(new Column(
			'id',
			'ID',
			static fn(object $row): int => (int)$row->id,
			isId: true,
		));
		$table->addColumn(new Column(
			'name',
			'Name',
			static fn(object $row): string => (string)$row->name,
		));
		$table->addColumn(new Column(
			'categoryTitle',
			'Category',
			static fn(object $row): string => (string)$row->relation('category')->get('title'),
			filter: new SearchFilter(
				key: 'categoryTitle',
				title: 'Category',
				field: 'ar_category.title',
				searchMode: SearchFilter::SEARCH_MODE_EQUAL,
			),
		));
		$table->addSort(
			'category_sort',
			SortDefinition::fixedOrder(['ar_category.title' => SORT_ASC, 'ar_product.id' => SORT_ASC]),
		);

		$orchestrator = new TableHttpOrchestrator(
			new TableRequestApplier(),
			new TableHttpResponder(new ResponseFactory(), new StreamFactory()),
		);
		$response = $orchestrator->respond(
			$table,
			(new ServerRequest())->withQueryParams([
				'filter'   => ['categoryTitle' => 'mobile-cat'],
				'sort'     => 'category_sort',
				'page'     => 1,
				'per-page' => 2,
			]),
		);

		$actual = json_decode((string)$response->getBody(), true);
		self::assertIsArray($actual);
		self::assertSame($this->expectedPayload(), $actual);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function expectedPayload(): array
	{
		$path = dirname(__DIR__, 2) . '/data/golden/http-orchestrator-ar-relation-payload.json';
		$json = file_get_contents($path);
		self::assertNotFalse($json);

		$decoded = json_decode($json, true);
		self::assertIsArray($decoded);
		return $decoded;
	}
}
