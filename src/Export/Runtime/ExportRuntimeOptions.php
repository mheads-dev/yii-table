<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Export\Runtime;

/**
 * Runtime-настройки для одного запуска экспорта.
 *
 * Позволяют очереди/фоновому воркеру переопределять timeout и настраивать
 * проверки отмены/прогресса без изменения декларативных настроек таблицы.
 */
final readonly class ExportRuntimeOptions
{
	public function __construct(
		/** Полностью отключить timeout-проверки для этого запуска. */
		public bool $disableTimeout = false,
		/**
		 * Переопределение timeout в секундах.
		 * Используется, если `disableTimeout = false`.
		 * Если `null`, берётся timeout из ExportGeneratorInterface::timeoutSeconds().
		 */
		public ?int $timeoutSecondsOverride = null,
		/** Кооперативная проверка отмены. */
		public ?ExportCancellationCheckerInterface $cancellationChecker = null,
		/** Как часто вызывать checker отмены при чтении строк. */
		public int $cancellationCheckEachRows = 10000,
		/** Как часто проверять timeout при чтении строк. */
		public int $timeoutCheckEachRows = 10000,
		/** Опциональный observer для heartbeat/progress уведомлений. */
		public ?RowsReadObserverInterface $rowsReadObserver = null,
		/** Как часто отправлять observer-уведомления onRowsRead(). */
		public int $observerNotifyEachRows = 1000,
	) {}
}
