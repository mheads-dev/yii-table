<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Serialization;

use Mheads\Yii\Table\Provider\TableProviderInterface;

interface TableConfigSerializerInterface
{
	public function serializeConfig(TableProviderInterface $table): array;
}
