<?php

declare(strict_types=1);

namespace App\Api\Product;

use Mheads\Yii\Table\Http\Orchestrator\TableExportHttpOrchestrator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ProductTableExportAction
{
    public function __construct(
        private TableExportHttpOrchestrator $tableHttp,
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
