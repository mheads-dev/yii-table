<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Http\Orchestrator;

use Mheads\Yii\Table\Http\Request\TableRequestApplierInterface;
use Mheads\Yii\Table\Http\Response\TableHttpResponderInterface;
use Mheads\Yii\Table\Provider\TableConfiguratorInterface;
use Mheads\Yii\Table\Provider\TableProviderInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Yiisoft\Data\Paginator\InvalidPageException;

final class TableHttpOrchestrator
{
	public function __construct(
		private readonly TableRequestApplierInterface $tableRequestApplier,
		private readonly TableHttpResponderInterface $tableHttpResponder,
	) {}

	/**
	 * @param callable(Throwable, TableProviderInterface, ServerRequestInterface, string): ?ResponseInterface|null $onExportException
	 * @throws InvalidPageException
	 */
	public function respond(
		TableProviderInterface&TableConfiguratorInterface $table,
		ServerRequestInterface $request,
		?callable $onExportException = null,
	): ResponseInterface {
		$this->tableRequestApplier->apply($table, $request);

		return $this->tableHttpResponder->respond($table, $request, $onExportException);
	}
}
