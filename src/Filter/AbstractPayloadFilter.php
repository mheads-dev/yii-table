<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Filter;

use Override;

/**
 * @psalm-import-type DefaultFilterPayload from \Mheads\Yii\Table\Serialization\TablePayloadTypes
 */
abstract class AbstractPayloadFilter extends AbstractFilter implements FilterPayloadProviderInterface
{
	#[Override]
	/**
	 * @return DefaultFilterPayload
	 */
	public function toArray(?FilterInput $input = null): array
	{
		return [
			'key'       => $this->key(),
			'title'     => $this->title(),
			'caption'   => $this->caption(),
			'type'      => $this->type(),
			'values'    => $input === null ? null : $this->getValues($input),
			'columnKey' => $this->getColumnKey(),
		];
	}
}
