<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Http\Response\Payload;

use Psr\Http\Message\ResponseInterface;

interface TablePayloadResponderInterface
{
	public function respond(array $payload, int $statusCode = 200): ResponseInterface;
}
