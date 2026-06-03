# Data sources

`TableProvider` accepts any source that implements `Yiisoft\Data\Reader\ReadableDataInterface`.

## IterableDataReader

Use `IterableDataReader` for arrays and simple iterable sources.

Examples:

- [examples/usage/array-product-list-table-factory.php](../../../examples/usage/array-product-list-table-factory.php)
- [examples/usage/array-list-action.php](../../../examples/usage/array-list-action.php)

## QueryDataReader

Use `QueryDataReader` for DB-backed tables.

Examples:

- [examples/usage/product-list-table-factory.php](../../../examples/usage/product-list-table-factory.php)
- [examples/usage/product-list-table-factory-with-i18n.php](../../../examples/usage/product-list-table-factory-with-i18n.php)

## Export note

Export runs on the filtered and sorted dataset. Request pagination (`page`, `prev-page`, `per-page`) is ignored for export.

- [Export](export.md)
