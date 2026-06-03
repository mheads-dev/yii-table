<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Serialization;

use Mheads\Yii\Table\Provider\TableProviderInterface;
use Yiisoft\Data\Paginator\InvalidPageException;

interface TableSerializerInterface
{
	/**
	 * Сериализует таблицу в payload для HTTP responder-а.
	 *
	 * @throws InvalidPageException
	 */
	public function serialize(TableProviderInterface $table): array;
}
