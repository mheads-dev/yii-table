<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\I18n;

interface LocalizableInterface
{
	public function setTranslator(TableTranslatorInterface $translator): void;
}
