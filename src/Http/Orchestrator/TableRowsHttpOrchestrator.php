<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Http\Orchestrator;

use Mheads\Yii\Table\Http\Request\TableRequestApplierInterface;
use Mheads\Yii\Table\Http\Response\Payload\TablePayloadResponderInterface;
use Mheads\Yii\Table\Provider\TableConfiguratorInterface;
use Mheads\Yii\Table\Provider\TableProviderInterface;
use Mheads\Yii\Table\Serialization\TableRowsSerializerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Data\Paginator\InvalidPageException;

final class TableRowsHttpOrchestrator
{
	public function __construct(
		private readonly TableRequestApplierInterface $tableRequestApplier,
		private readonly TableRowsSerializerInterface $serializer,
		private readonly TablePayloadResponderInterface $payloadResponder,
	) {}

	/**
	 * @throws InvalidPageException
	 */
	public function respond(
		TableProviderInterface&TableConfiguratorInterface $table,
		ServerRequestInterface $request,
	): ResponseInterface {
		$this->tableRequestApplier->apply($table, $request);

		return $this->payloadResponder->respond($this->serializer->serializeRows($table));
	}
}
