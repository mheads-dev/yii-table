<?php

declare(strict_types=1);

namespace App\Api\Product;

use Mheads\Yii\Table\Http\Orchestrator\TableHttpOrchestrator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ListAction
{
    public function __construct(
        private TableHttpOrchestrator $tableHttp,
        private ProductListTableFactory $tableFactory,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $table = $this->tableFactory->create();

        return $this->tableHttp->respond($table, $request);
    }
}
