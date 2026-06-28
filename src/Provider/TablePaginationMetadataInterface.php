<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Provider;

interface TablePaginationMetadataInterface
{
	public const string PAGINATION_OFFSET = 'offset';
	public const string PAGINATION_KEYSET = 'keyset';
	public const string PAGINATION_GENERIC = 'generic';

	/**
	 * Возвращает тип пагинации без подготовки reader и без runtime-запросов.
	 *
	 * @return self::PAGINATION_GENERIC|self::PAGINATION_KEYSET|self::PAGINATION_OFFSET|null
	 */
	public function paginationType(): ?string;
}
