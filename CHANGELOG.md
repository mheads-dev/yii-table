# Changelog

## 1.0.0-beta2

### Changed

- Relaxed table payload extension contracts:
  - `TableSerializerInterface::serialize()` no longer requires the default `TablePayload` Psalm shape.
  - `TablePayloadResponderInterface::respond()` no longer requires `array<string, mixed>`.
- Split filter data contracts from filter payload serialization:
  - `FilterInterface` no longer requires `toArray()`.
  - Added `FilterPayloadProviderInterface` for filters that can be serialized into table payloads.
  - Added `AbstractPayloadFilter` for the default filter payload shape.
- Built-in filters now extend `AbstractPayloadFilter`.
- `FilterPayloadProviderInterface::toArray()` now accepts optional `FilterInput`.
- `TableArraySerializer` now serializes filters through `FilterPayloadProviderInterface`.

### Removed

- Removed the shared `FilterPayload` Psalm type. Use `DefaultFilterPayload` for the default payload shape provided by `AbstractPayloadFilter`.

### Documentation

- Updated custom payload and custom filter documentation for the new extension model.
