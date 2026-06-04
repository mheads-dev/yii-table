<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Http\Orchestrator;

use Mheads\Yii\Table\Http\Request\TableRequestApplierInterface;
use Mheads\Yii\Table\Http\Response\Payload\TablePayloadResponderInterface;
use Mheads\Yii\Table\Http\Response\TableExportHttpResponder;
use Mheads\Yii\Table\Provider\TableConfiguratorInterface;
use Mheads\Yii\Table\Provider\TableProviderInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

use function is_string;

final class TableExportHttpOrchestrator
{
	public function __construct(
		private readonly TableRequestApplierInterface $tableRequestApplier,
		private readonly TableExportHttpResponder $exportResponder,
		private readonly TablePayloadResponderInterface $payloadResponder,
	) {}

	/**
	 * @param callable(Throwable, TableProviderInterface, ServerRequestInterface, string): ?ResponseInterface|null $onExportException
	 */
	public function respond(
		TableProviderInterface&TableConfiguratorInterface $table,
		ServerRequestInterface $request,
		?callable $onExportException = null,
	): ResponseInterface {
		$this->tableRequestApplier->apply($table, $request);

		$exportCode = $this->resolveExportCode($request, $table->exportParam());
		if ($exportCode === null)
		{
			return $this->payloadResponder->respond(
				['errorCode' => 'missing_export', 'errorMessage' => 'Export code is required.'],
				400,
			);
		}

		return $this->exportResponder->respondExport($table, $request, $exportCode, $onExportException);
	}

	private function resolveExportCode(ServerRequestInterface $request, string $exportParam): ?string
	{
		$queryParams = $request->getQueryParams();
		$value = $queryParams[$exportParam] ?? null;
		if (!is_string($value) || $value === '')
		{
			return null;
		}

		return $value;
	}
}
