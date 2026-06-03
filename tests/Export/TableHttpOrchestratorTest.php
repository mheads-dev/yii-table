<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Export;

use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Message\StreamFactory;
use Mheads\Yii\Table\Filter\FilterInput;
use Mheads\Yii\Table\Http\Orchestrator\TableHttpOrchestrator;
use Mheads\Yii\Table\Http\Request\TableRequestApplier;
use Mheads\Yii\Table\Http\Response\Payload\TablePayloadResponderInterface;
use Mheads\Yii\Table\Http\Response\TableHttpResponder;
use Mheads\Yii\Table\Provider\TableConfiguratorInterface;
use Mheads\Yii\Table\Provider\TableProviderInterface;
use Mheads\Yii\Table\Serialization\TableSerializerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Data\Reader\Sort;

/**
 * @internal
 */
final class TableHttpOrchestratorTest extends TestCase
{
	public function testAppliesRequestAndDelegatesResponse(): void
	{
		$table = $this->createMockForIntersectionOfInterfaces([
			TableProviderInterface::class,
			TableConfiguratorInterface::class,
		]);
		$table->method('filterParam')->willReturn('filter');
		$table->method('sortParam')->willReturn('sort');
		$table->method('pageParam')->willReturn('page');
		$table->method('prevPageParam')->willReturn('prev-page');
		$table->method('pageSizeParam')->willReturn('per-page');
		$table->method('exportParam')->willReturn('export');
		$table->method('setFilterInput')->willReturnSelf();
		$table->method('setSort')->willReturnSelf();
		$table->method('setPage')->willReturnSelf();
		$table->method('setPreviousPage')->willReturnSelf();
		$table->method('setPageSize')->willReturnSelf();

		$table->expects(self::once())->method('setFilterInput')->with(self::isInstanceOf(FilterInput::class));
		$table->expects(self::once())->method('setSort')->with(self::isInstanceOf(Sort::class));
		$table->expects(self::once())->method('setPage')->with(2);
		$table->expects(self::once())->method('setPreviousPage')->with(null);
		$table->expects(self::once())->method('setPageSize')->with(25);

		$serializer = $this->createMock(TableSerializerInterface::class);
		$serializer->method('serialize')->with($table)->willReturn(['rows' => [['id' => 1]]]);
		$payloadResponder = new TableHttpOrchestratorPayloadResponderStub();

		$responder = new TableHttpResponder(
			new ResponseFactory(),
			new StreamFactory(),
			$serializer,
			payloadResponder: $payloadResponder,
		);
		$orchestrator = new TableHttpOrchestrator(new TableRequestApplier(), $responder);
		$response = $orchestrator->respond($table, (new ServerRequest())->withQueryParams([
			'sort'     => '-id',
			'page'     => 2,
			'per-page' => 25,
		]));

		self::assertSame(206, $response->getStatusCode());
		self::assertSame(['rows' => [['id' => 1]]], $payloadResponder->payload);
	}
}

/**
 * @internal
 */
final class TableHttpOrchestratorPayloadResponderStub implements TablePayloadResponderInterface
{
	/** @var array<string, mixed>|null */
	public ?array $payload = null;

	public function respond(array $payload, int $statusCode = 200): ResponseInterface
	{
		$this->payload = $payload;
		return (new ResponseFactory())->createResponse(206);
	}
}
