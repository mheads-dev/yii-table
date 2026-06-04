<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Http\Orchestrator;

use Mheads\Yii\Table\Http\Request\TableRequestApplierInterface;
use Mheads\Yii\Table\Http\Response\Payload\TablePayloadResponderInterface;
use Mheads\Yii\Table\Provider\TableConfiguratorInterface;
use Mheads\Yii\Table\Provider\TableProviderInterface;
use Mheads\Yii\Table\Serialization\TableConfigSerializerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class TableConfigHttpOrchestrator
{
	public function __construct(
		private readonly TableRequestApplierInterface $tableRequestApplier,
		private readonly TableConfigSerializerInterface $serializer,
		private readonly TablePayloadResponderInterface $payloadResponder,
	) {}

	public function respond(
		TableProviderInterface&TableConfiguratorInterface $table,
		?ServerRequestInterface $request = null,
	): ResponseInterface {
		if ($request !== null)
		{
			$this->tableRequestApplier->apply($table, $request);
		}

		return $this->payloadResponder->respond($this->serializer->serializeConfig($table));
	}
}
