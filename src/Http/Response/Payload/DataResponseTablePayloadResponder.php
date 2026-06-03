<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Http\Response\Payload;

use InvalidArgumentException;
use Override;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

use function get_debug_type;
use function is_callable;
use function method_exists;
use function sprintf;

/**
 * Adapter for app-level response formatters/content negotiation.
 *
 * Intended for integration with yiisoft/data-response factories, e.g.
 * \Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface.
 */
final class DataResponseTablePayloadResponder implements TablePayloadResponderInterface
{
	/**
	 * @param object $dataResponseFactory Factory with callable method (by default "createResponse")
	 */
	public function __construct(
		private readonly object $dataResponseFactory,
		private readonly string $method = 'createResponse',
	) {
		if ($this->method === '' || !method_exists($this->dataResponseFactory, $this->method))
		{
			throw new InvalidArgumentException(sprintf(
				'Method "%s" is not available on %s.',
				$this->method,
				$this->dataResponseFactory::class,
			));
		}
	}

	#[Override]
	public function respond(array $payload, int $statusCode = 200): ResponseInterface
	{
		$callable = [$this->dataResponseFactory, $this->method];
		if (!is_callable($callable))
		{
			throw new RuntimeException(sprintf(
				'Method "%s" on %s is not callable.',
				$this->method,
				$this->dataResponseFactory::class,
			));
		}

		$response = $this->dataResponseFactory->{$this->method}($payload, $statusCode);
		if (!$response instanceof ResponseInterface)
		{
			throw new RuntimeException(sprintf(
				'Method "%s" on %s must return %s, got %s.',
				$this->method,
				$this->dataResponseFactory::class,
				ResponseInterface::class,
				get_debug_type($response),
			));
		}

		return $response;
	}
}
