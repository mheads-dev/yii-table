# Changelog

## 1.0.0-beta2

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
