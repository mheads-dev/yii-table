<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Serialization;

use Mheads\Yii\Table\Provider\TableProviderInterface;
use Yiisoft\Data\Paginator\InvalidPageException;

interface TableRowsSerializerInterface
{
	/**
	 * @throws InvalidPageException
	 */
	public function serializeRows(TableProviderInterface $table): array;
}
