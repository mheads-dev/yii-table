# mheads/yii-table

Universal table provider for Yii3 / yiisoft applications: columns, filters, sorting, pagination, export.

`yii-table` gives you a backend contract for data grids. You describe table schema once, then reuse it for HTTP payloads, filtering, sorting, pagination, and export.

## What You Get

- predictable API payload: `config`, `pagination`, `columns`, `filters`, `sorts`, `rows`;
- one place to configure columns, filters, sort options, pagination, and export;
- request parameters applied consistently: `filter`, `sort`, `page`, `prev-page`, `per-page`, `export`;
- CSV/XLSX export and custom formats via `WriterInterface`;
- support for `Yiisoft\Data\Reader\ReadableDataInterface` sources from `yiisoft/data`.

Typical request:

```text
/products?filter[categoryName]=Audio&per-page=2&page=1
```

Full example payload:

- [examples/payload_example.json](examples/payload_example.json)

## Requirements

- PHP 8.3 - 8.5.
- `yiisoft/data` 2.x.
- `psr/http-message` 2.x.

Optional integrations:

- `yiisoft/data-db` for `QueryDataReader`;
- `yiisoft/data-response` for application-level response formatting;
- `yiisoft/translator` for i18n adapters;
- `ext-xlswriter` for XLSX export.

## Installation

Install the package with Composer:

```bash
composer require mheads/yii-table
```

## Quick Start

### 1. Configure DI

Start with minimal HTTP wiring:

- [examples/di/common.php](examples/di/common.php)
- [Configuration guide](docs/guide/en/configuration.md)

### 2. Create a table factory

Declare columns, filters, sort options, page size, and export formats:

- [examples/usage/product-list-table-factory.php](examples/usage/product-list-table-factory.php)
- [Table factory guide](docs/guide/en/table-factory.md)

### 3. Add an HTTP action

Use `TableHttpOrchestrator` to apply request parameters and produce a JSON payload or an export response:

- [examples/usage/query-list-action.php](examples/usage/query-list-action.php)
- [HTTP usage guide](docs/guide/en/http-usage.md)

### 4. Call the endpoint

```text
/products?sort=-name&per-page=10&page=1&filter[name]=headphones
```

### 5. Try export

```text
/products?export=csv
/products?export=xlsx
```

## Documentation

- [Guide](docs/guide/en/README.md)
- [Examples](examples/README.md)

Common entry points:

- [Filters](docs/guide/en/filters.md)
- [Sorting](docs/guide/en/sorting.md)
- [Pagination](docs/guide/en/pagination.md)
- [Keyset pagination](docs/guide/en/keyset-pagination.md)
- [Sync export](docs/guide/en/export.md)
- [Async export](docs/guide/en/async-export.md)
- [I18n](docs/guide/en/i18n.md)
- [HTTP error contract](docs/guide/en/error-contract.md)
- [Advanced documentation map](docs/guide/en/advanced-map.md)

## Examples

The examples are intentionally compact and focused on contract demonstration. They are not intended to be executed directly from `vendor/.../examples` without adaptation.

- [examples/README.md](examples/README.md)

## License

This package is released under the terms of the BSD-3-Clause License.
See [LICENSE.md](LICENSE.md) for details.
