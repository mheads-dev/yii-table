<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Provider;

use Mheads\Yii\Table\Filter\FilterInterface;

interface TableFilterConfiguratorInterface
{
	/**
	 * Добавляет фильтр таблицы, не привязанный к колонке.
	 */
	public function addFilter(FilterInterface $filter): self;
}
