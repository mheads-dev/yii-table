# Changelog

All notable changes to `mheads/yii-table` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.1] - 2026-06-19

### Documentation

- Document `mheads/yii-table-export-queue` as the async export queue package.

## [1.0.0] - 2026-06-17

### Added

- Table provider contracts for columns, filters, sorting, pagination, serialization, HTTP payloads, and exports.
- Filter support for search, composite search, select, checkbox, number, and date use cases.
- Sort DSL and sortable column configuration.
- HTTP request applier, table orchestrator, and payload responders.
- CSV and XLSX export pipeline with configurable columns, custom writers, batch reading, timeout checks, cancellation checks, and row read observers.
- I18n contracts, Yii translator adapter, and English/Russian message resources.
- Unit and MySQL-backed test suites for provider behavior, serialization, filters, sorting, pagination, export rows reading, and HTTP payload snapshots.
- Usage examples and English documentation guide.

### Changed

- Include bundled translation resources in Composer distribution archives.
- Split filter payload contracts before stable release.
- Split table payload serializers before stable release.
- Split HTTP table orchestration responsibilities before stable release.

## [1.0.0-beta2] - 2026-06-17

### Changed

- Relaxed table payload extension contracts:
  - `TableSerializerInterface::serialize()` no longer requires the default `TablePayload` Psalm shape.
  - `TablePayloadResponderInterface::respond()` no longer documents payloads as `array<string, mixed>`.
- Split filter data contracts from filter payload serialization:
  - `FilterInterface` no longer requires `toArray()`.
  - Added `FilterPayloadProviderInterface` for filters that can be serialized into table payloads.
  - Added `AbstractPayloadFilter` for the default filter payload shape.
- Built-in filters now extend `AbstractPayloadFilter`.
- `FilterPayloadProviderInterface::toArray()` now accepts optional `FilterInput`.
- `TableArraySerializer` now serializes filters through `FilterPayloadProviderInterface`.
- Added `TableConfigSerializerInterface` and `TableRowsSerializerInterface` for applications that split table config and row payloads.
- `TableArraySerializer` now exposes `serializeConfig()` and `serializeRows()` in addition to the full `serialize()` payload.
- Added split HTTP orchestrators for config, rows, and export workflows.
- Extracted export response handling into `TableExportHttpResponder`.

### Removed

- Removed the shared `FilterPayload` Psalm type. Use `DefaultFilterPayload` for the default payload shape provided by `AbstractPayloadFilter`.

### Documentation

- Updated custom payload and custom filter documentation for the new extension model.
- Added split config, rows, and export orchestration guidance.

[Unreleased]: https://github.com/mheads-dev/yii-table/compare/1.0.1...HEAD
[1.0.1]: https://github.com/mheads-dev/yii-table/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/mheads-dev/yii-table/releases/tag/1.0.0
[1.0.0-beta2]: https://github.com/mheads-dev/yii-table/releases/tag/1.0.0-beta2
