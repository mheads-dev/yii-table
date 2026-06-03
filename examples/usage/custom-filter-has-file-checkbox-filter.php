<?php

declare(strict_types=1);

namespace App\Api\Product\Filter;

use Mheads\Yii\Table\Filter\AbstractFilter;
use Mheads\Yii\Table\Filter\FilterInput;
use Override;
use Yiisoft\Data\Reader\Filter\EqualsNull;
use Yiisoft\Data\Reader\Filter\Not;
use Yiisoft\Data\Reader\FilterInterface as DataFilterInterface;

use function array_filter;
use function array_values;
use function in_array;
use function is_array;
use function is_scalar;
use function strlen;

/**
 * Custom checkbox filter:
 * - with    -> field IS NOT NULL
 * - without -> field IS NULL
 */
final class HasFileCheckboxFilter extends AbstractFilter
{
    public const string VALUE_WITH_FILE = 'with';
    public const string VALUE_WITHOUT_FILE = 'without';

    public function __construct(
        string $key,
        string $title,
        private readonly string $field,
        ?string $caption = null,
    ) {
        parent::__construct($key, $title, $caption);
    }

    #[Override]
    public function type(): string
    {
        return 'checkbox';
    }

    #[Override]
    public function buildDataFilter(FilterInput $input): ?DataFilterInterface
    {
        /** @var list<string> $values */
        $values = $this->normalizeValues($input);
        if ($values === []) {
            return null;
        }

        $hasWith = in_array(self::VALUE_WITH_FILE, $values, true);
        $hasWithout = in_array(self::VALUE_WITHOUT_FILE, $values, true);

        if ($hasWith && $hasWithout) {
            return null;
        }

        if ($hasWith) {
            return new Not(new EqualsNull($this->field));
        }

        return new EqualsNull($this->field);
    }

    #[Override]
    public function toArray(FilterInput $input): array
    {
        $result = parent::toArray($input);
        $result['options'] = [
            ['label' => 'Has file', 'value' => self::VALUE_WITH_FILE],
            ['label' => 'No file', 'value' => self::VALUE_WITHOUT_FILE],
        ];

        return $result;
    }

    /**
     * @return list<string>
     */
    private function normalizeValues(FilterInput $input): array
    {
        $raw = $input->value($this->key());
        if ($raw === null) {
            return [];
        }

        $values = is_array($raw) ? $raw : [$raw];
        $values = array_filter(
            $values,
            static fn(mixed $item): bool => is_scalar($item) && strlen((string) $item) > 0,
        );
        $values = array_values(array_map(static fn(mixed $item): string => (string) $item, $values));

        return array_values(array_filter(
            $values,
            static fn(string $value): bool => in_array($value, [self::VALUE_WITH_FILE, self::VALUE_WITHOUT_FILE], true),
        ));
    }
}
