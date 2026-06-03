# Performance smoke checks

Use the export pipeline smoke test to measure library-level export overhead on a controlled synthetic reader.

It is not a DB benchmark and does not measure performance of application-specific `ReadableDataInterface` implementations.

## Run

```bash
make perf-smoke
```

Custom parameters:

```bash
make perf-smoke ROWS=200000 BATCH=5000 FORMATS=csv,xlsx
```

The output includes:

- `rows`
- `batchSize`
- `formatsExecuted`
- per-format `durationSeconds`
- per-format `peakMemoryMiB`
- per-format `outputSizeMiB`

Use these numbers as baseline and compare across releases to detect regressions.

## Baseline (2026-05-13)

Command:

```bash
make perf-smoke ROWS=100000 BATCH=2000 FORMATS=csv,xlsx
```

Results:

- `csv`: `durationSeconds=0.1496`, `peakMemoryMiB=4.00`, `outputSizeMiB=4.65`
- `xlsx`: `durationSeconds=0.2952`, `peakMemoryMiB=4.00`, `outputSizeMiB=1.62`
