<?php

declare(strict_types=1);

namespace App\Api\Product;

use Mheads\Yii\Table\Http\Request\TableRequestApplier;
use Mheads\Yii\Table\Http\Response\TableHttpResponder;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

final readonly class ArrayListAction
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
        private ArrayProductListTableFactory $tableFactory,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $table = $this->tableFactory->create();

        (new TableRequestApplier())->apply($table, $request);

        return (new TableHttpResponder(
            responseFactory: $this->responseFactory,
            streamFactory: $this->streamFactory,
        ))->respond($table, $request);
    }
}
