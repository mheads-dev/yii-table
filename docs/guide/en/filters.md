# Filters

Filters are attached to columns and convert request values into `yiisoft/data` filter objects.

Default filter query parameter: `filter`.

Examples are based on:

- [examples/usage/product-list-table-factory.php](../../../examples/usage/product-list-table-factory.php)
- [examples/usage/custom-filter-has-file-checkbox-filter.php](../../../examples/usage/custom-filter-has-file-checkbox-filter.php)

## SearchFilter

Single value:

```text
/products?filter[name]=Wireless%20Headphones
```

Multiple values (`isMultiple=true`):

```text
/products?filter[name][0]=Wireless&filter[name][1]=Headphones
```

## CompositeSearchFilter

One query value applied to multiple configured fields:

```text
/products?filter[q]=sony
```

## SelectFilter

Single option:

```text
/products?filter[categoryName]=Audio
```

Multiple options (`isMultiple=true`):

```text
/products?filter[categoryName][0]=Audio&filter[categoryName][1]=Gaming
```

## CheckboxFilter

Usually array values:

```text
/products?filter[pictureUrl][0]=with&filter[pictureUrl][1]=without
```

Single value is also supported:

```text
/products?filter[pictureUrl]=with
```

## NumberFilter

Short form (`exactly`):

```text
/products?filter[id]=100
```

Structured forms:

```text
/products?filter[id][select]=exactly&filter[id][number]=100
/products?filter[id][select]=more_than&filter[id][from]=100
/products?filter[id][select]=less_than&filter[id][to]=500
/products?filter[id][select]=range&filter[id][from]=100&filter[id][to]=500
```

## DateFilter

Short form (`date`):

```text
/products?filter[dateUpdate]=2026-05-13
```

Structured forms:

```text
/products?filter[dateUpdate][select]=date&filter[dateUpdate][date]=2026-05-13
/products?filter[dateUpdate][select]=range_date&filter[dateUpdate][from]=2026-05-01&filter[dateUpdate][to]=2026-05-31
```

If custom presets are registered (`today`, `current_week`, ...), use:

```text
/products?filter[dateUpdate][select]=today
```

## Invalid input

Invalid non-empty `NumberFilter` and `DateFilter` payloads become an empty-result condition (`None`).

Built-in filter payloads expose normalized request values under `filters[*].values`.

## Custom filters

Choose one base:

- Implement `Mheads\Yii\Table\Filter\FilterInterface` directly when no shared base class is needed.
- Extend `Mheads\Yii\Table\Filter\AbstractFilter` when the filter is used only for data filtering, or when a custom table serializer handles filter payloads.
- Extend `Mheads\Yii\Table\Filter\AbstractPayloadFilter` when using `TableArraySerializer` and the default filter payload keys are acceptable.
- Extend `AbstractFilter` and implement `Mheads\Yii\Table\Filter\FilterPayloadProviderInterface` when using `TableArraySerializer` with a custom filter payload shape.

Then:

1. Implement `type(): string`.
2. Implement `buildDataFilter(FilterInput $input): ?\Yiisoft\Data\Reader\FilterInterface`.
3. Implement or override `toArray(?FilterInput $input = null)` only for filters that implement `FilterPayloadProviderInterface`. The returned array shape is application-defined.
4. Attach the filter to a column in the table factory.

Example custom checkbox filter (`with` / `without` for nullable file field):

- [examples/usage/custom-filter-has-file-checkbox-filter.php](../../../examples/usage/custom-filter-has-file-checkbox-filter.php)

Example integration into table factory:

- [examples/usage/product-list-table-factory.php](../../../examples/usage/product-list-table-factory.php)
