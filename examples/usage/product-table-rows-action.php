<?php

declare(strict_types=1);

namespace App\Api\Product;

use Mheads\Yii\Table\Http\Orchestrator\TableRowsHttpOrchestrator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ProductTableRowsAction
{
    public function __construct(
        private TableRowsHttpOrchestrator $tableHttp,
        private ProductListTableFactory $tableFactory,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return $this->tableHttp->respond(
            $this->tableFactory->create(),
            $request,
        );
    }
}
