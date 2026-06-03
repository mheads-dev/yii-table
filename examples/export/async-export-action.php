<?php

declare(strict_types=1);

namespace App\Api\Product;

use App\Export\ExportJobService;
use Mheads\Yii\Table\Export\Exception\ExportTimeoutException;
use Mheads\Yii\Table\Http\Orchestrator\TableHttpOrchestrator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;

final readonly class ListWithExportAction
{
    public function __construct(
        private TableHttpOrchestrator $tableHttp,
        private ProductListTableFactory $tableFactory,
        private ExportJobService $exportJobs,
        private DataResponseFactoryInterface $responseFactory,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $table = $this->tableFactory->create();

        return $this->tableHttp->respond(
            $table,
            $request,
            function (Throwable $e, mixed $table, ServerRequestInterface $request, string $exportCode): ?ResponseInterface {
                if (!$e instanceof ExportTimeoutException) {
                    return null;
                }

                $exportId = $this->exportJobs->enqueueFromTimeout(
                    tableKey: $table->id(),
                    exportCode: $exportCode,
                    queryParams: $request->getQueryParams(),
                );

                return $this->responseFactory
                    ->createResponse([
                        'status' => 'queued',
                        'exportId' => $exportId,
                    ])
                    ->withStatus(202);
            },
        );
    }
}
