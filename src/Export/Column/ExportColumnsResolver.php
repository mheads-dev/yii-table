<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Export\Column;

use Mheads\Yii\Table\Column\ColumnInterface;

final class ExportColumnsResolver
{
	/**
	 * @param array<int, ColumnInterface> $tableColumns
	 * @param array<int, ExportColumn> $customColumns
	 * @return array<int, ExportColumn>
	 */
	public function resolve(
		array $tableColumns,
		array $customColumns = [],
		ExportColumnMode $mode = ExportColumnMode::TABLE_ONLY,
	): array {
		return match ($mode)
		{
			ExportColumnMode::TABLE_ONLY  => $this->resolveTableOnly($tableColumns),
			ExportColumnMode::CUSTOM_ONLY => array_values($customColumns),
			ExportColumnMode::MERGE       => $this->resolveMerge($tableColumns, $customColumns),
		};
	}

	/**
	 * @param array<int, ColumnInterface> $tableColumns
	 * @return array<int, ExportColumn>
	 */
	private function resolveTableOnly(array $tableColumns): array
	{
		$result = [];

		foreach ($tableColumns as $column)
		{
			if ($this->isHidden($column))
			{
				continue;
			}

			$result[] = ExportColumn::fromColumn($column);
		}

		return $result;
	}

	/**
	 * @param array<int, ColumnInterface> $tableColumns
	 * @param array<int, ExportColumn> $customColumns
	 * @return array<int, ExportColumn>
	 */
	private function resolveMerge(array $tableColumns, array $customColumns): array
	{
		$result = [];

		foreach ($this->resolveTableOnly($tableColumns) as $column)
		{
			$result[$column->key()] = $column;
		}

		foreach ($customColumns as $column)
		{
			$result[$column->key()] = $column;
		}

		return array_values($result);
	}

	private function isHidden(ColumnInterface $column): bool
	{
		return $column->isHidden();
	}
}
