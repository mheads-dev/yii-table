<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Http\Request;

use Mheads\Yii\Table\Provider\TableConfiguratorInterface;
use Mheads\Yii\Table\Provider\TableProviderInterface;
use Psr\Http\Message\ServerRequestInterface;

interface TableRequestApplierInterface
{
	public function apply(
		TableProviderInterface&TableConfiguratorInterface $table,
		ServerRequestInterface $request,
	): void;
}
