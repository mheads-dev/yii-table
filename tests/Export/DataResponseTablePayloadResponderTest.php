<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Export;

use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\StreamFactory;
use Mheads\Yii\Table\Http\Response\Payload\DataResponseTablePayloadResponder;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @internal
 */
final class DataResponseTablePayloadResponderTest extends TestCase
{
	public function testDelegatesResponseCreationToFactory(): void
	{
		$responder = new DataResponseTablePayloadResponder(new DataResponseFactoryStub());
		$response = $responder->respond(['ok' => true], 207);

		self::assertSame(207, $response->getStatusCode());
		self::assertSame('application/xml', $response->getHeaderLine('Content-Type'));
		self::assertSame('<ok/>', (string)$response->getBody());
	}

	public function testThrowsWhenFactoryReturnsUnexpectedType(): void
	{
		$responder = new DataResponseTablePayloadResponder(new DataResponseFactoryInvalidStub());

		$this->expectException(RuntimeException::class);
		$responder->respond(['ok' => true]);
	}
}

/**
 * @internal
 */
final class DataResponseFactoryStub
{
	public function createResponse(array $payload, int $statusCode): \Psr\Http\Message\ResponseInterface
	{
		return (new ResponseFactory())
			->createResponse($statusCode)
			->withHeader('Content-Type', 'application/xml')
			->withBody((new StreamFactory())->createStream('<ok/>'));
	}
}

/**
 * @internal
 */
final class DataResponseFactoryInvalidStub
{
	public function createResponse(array $payload, int $statusCode): array
	{
		return $payload;
	}
}
