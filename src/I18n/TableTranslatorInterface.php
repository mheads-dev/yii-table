<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\I18n;

interface TableTranslatorInterface
{
	public const string CATEGORY = 'mheads-yii-table';

	/**
	 * @param array<string, mixed> $parameters
	 */
	public function translate(
		string $id,
		array  $parameters = [],
	): string;
}

