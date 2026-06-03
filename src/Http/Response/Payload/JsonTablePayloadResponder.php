<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Http\Response\Payload;

use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;

use function json_encode;

final class JsonTablePayloadResponder implements TablePayloadResponderInterface
{
	public function __construct(
		private readonly ResponseFactoryInterface $responseFactory,
		private readonly StreamFactoryInterface $streamFactory,
	) {}

	#[Override]
	public function respond(array $payload, int $statusCode = 200): ResponseInterface
	{
		$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if ($json === false)
		{
			throw new RuntimeException('Unable to encode JSON response.');
		}

		return $this
			->responseFactory
			->createResponse($statusCode)
			->withHeader('Content-Type', 'application/json')
			->withBody($this->streamFactory->createStream($json));
	}
}
