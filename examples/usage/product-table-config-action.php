<?php

declare(strict_types=1);

namespace App\Api\Product;

use Mheads\Yii\Table\Http\Orchestrator\TableConfigHttpOrchestrator;
use Psr\Http\Message\ResponseInterface;

final readonly class ProductTableConfigAction
{
    public function __construct(
        private TableConfigHttpOrchestrator $tableHttp,
        private ProductListTableFactory $tableFactory,
    ) {}

    public function __invoke(): ResponseInterface
    {
        return $this->tableHttp->respond(
            $this->tableFactory->create(),
        );
    }
}
