# Quick start

This flow creates a backend contract for a product grid: table config, columns, filters, sort options, pagination, rows, and optional export.

## 1. Configure DI

Start with minimal HTTP wiring:

- [examples/di/common.php](../../../examples/di/common.php)
- [Configuration](configuration.md)

## 2. Create a table factory

Use a factory to declare columns, filters, sort options, page size, and export formats in one place:

- [examples/usage/product-list-table-factory.php](../../../examples/usage/product-list-table-factory.php)
- [Table factory](table-factory.md)

## 3. Add an HTTP action

Use `TableHttpOrchestrator` to apply request parameters and produce a response or export file:

- [examples/usage/query-list-action.php](../../../examples/usage/query-list-action.php)
- [HTTP usage](http-usage.md)

## 4. Call the endpoint

```text
/products?sort=-name&per-page=10&page=1&filter[name]=headphones
```

## 5. Try export

```text
/products?export=csv
/products?export=xlsx
```

## Response contract

The payload contains:

- `config`
- `pagination`
- `columns`
- `filters`
- `sorts`
- `rows`

Full example payload:

- [examples/payload_example.json](../../../examples/payload_example.json)
