<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Http\Response;

use Mheads\Yii\Table\Provider\TableProviderInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Yiisoft\Data\Paginator\InvalidPageException;

interface TableHttpResponderInterface
{
	/**
	 * @param callable(Throwable, TableProviderInterface, ServerRequestInterface, string): ?ResponseInterface|null $onExportException
	 * @throws InvalidPageException
	 */
	public function respond(
		TableProviderInterface $table,
		ServerRequestInterface $request,
		?callable $onExportException = null,
	): ResponseInterface;
}
