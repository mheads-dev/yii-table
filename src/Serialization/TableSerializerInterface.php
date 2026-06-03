<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Serialization;

use Mheads\Yii\Table\Provider\TableProviderInterface;
use Yiisoft\Data\Paginator\InvalidPageException;

/**
 * @psalm-import-type TablePayload from TablePayloadTypes
 */
interface TableSerializerInterface
{
	/**
	 * Сериализует таблицу в стабильный API payload:
	 * `{config, pagination, columns, filters, sorts, rows}`.
	 *
	 * @return TablePayload
	 * @throws InvalidPageException
	 */
	public function serialize(TableProviderInterface $table): array;
}
