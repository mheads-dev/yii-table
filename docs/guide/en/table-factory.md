# Table factory

A table factory is the recommended place to keep table schema and behavior together.

It usually configures:

- table ID;
- data reader;
- page size and page size constraints;
- columns;
- filters attached to columns;
- sort definitions and sort options;
- export generators.

## QueryDataReader example

Use this for DB-backed tables:

- [examples/usage/product-list-table-factory.php](../../../examples/usage/product-list-table-factory.php)

## IterableDataReader example

Use this for array-like sources, tests, prototypes, or in-memory lists:

- [examples/usage/array-product-list-table-factory.php](../../../examples/usage/array-product-list-table-factory.php)

## I18n-aware factory

Pass a `TableTranslatorInterface` into `TableProvider` when filters should expose translated select labels:

- [examples/usage/product-list-table-factory-with-i18n.php](../../../examples/usage/product-list-table-factory-with-i18n.php)

## Related topics

- [Data sources](data-sources.md)
- [Filters](filters.md)
- [Sorting](sorting.md)
- [Export](export.md)
