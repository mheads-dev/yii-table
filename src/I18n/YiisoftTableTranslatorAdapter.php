<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\I18n;

use Override;
use Yiisoft\Translator\TranslatorInterface;

final readonly class YiisoftTableTranslatorAdapter implements TableTranslatorInterface
{
	public function __construct(
		private TranslatorInterface $translator,
		private string              $category = TableTranslatorInterface::CATEGORY,
	) {}

	/**
	 * @param array<string, mixed> $parameters
	 */
	#[Override]
	public function translate(
		string $id,
		array  $parameters = [],
	): string {
		return $this->translator->translate(
			$id,
			$parameters,
			$this->category,
		);
	}
}

