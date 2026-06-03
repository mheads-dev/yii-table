<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\I18n;

trait LocalizableTrait
{
	private ?TableTranslatorInterface $translator = null;

	public function setTranslator(TableTranslatorInterface $translator): void
	{
		$this->translator = $translator;
	}

	protected function translateOrSelf(string $message, ?string $fallback = null): string
	{
		if ($this->translator === null)
		{
			return $fallback ?? $message;
		}

		$translated = $this->translator->translate($message);
		if ($fallback !== null && $translated === $message)
		{
			return $fallback;
		}

		return $translated;
	}
}
