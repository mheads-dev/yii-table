<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Filter;

interface FilterPayloadProviderInterface
{
	public function toArray(?FilterInput $input = null): array;
}
