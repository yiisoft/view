# View Performance Benchmarks

This directory contains performance benchmarks for critical code paths in the yiisoft/view library.

## Running Benchmarks

To run the benchmarks, ensure you have installed the project dependencies:

```bash
composer install
```

Then run individual benchmark files:

```bash
php benchmarks/RendererMatchingBench.php
```

## Available Benchmarks

### RendererMatchingBench.php

Benchmarks the performance of renderer extension matching, specifically:

1. **Extension Sorting Performance** - Measures the overhead of sorting renderer extensions by length using `uksort()`. This sorting is done in `ViewTrait::withRenderers()` to ensure more specific extensions (like "blade.php") are matched before generic ones (like "php").

2. **Extension Matching Performance** - Measures the performance of `str_ends_with()` function used to match file extensions against registered renderers.

3. **Overall Matching Performance** - Compares the end-to-end performance of renderer matching with and without sorting, showing the trade-off between correctness (sorted) and raw performance (unsorted).

## Interpreting Results

### Extension Sorting

The sorting operation adds overhead on every `withRenderers()` call. With typical renderer counts (5-10 renderers), the overhead is in the microsecond range (10-20 μs per operation). Since `withRenderers()` is typically called once during application initialization, this overhead is acceptable.

Example output:
```
1. Extension Sorting Performance (uksort)
------------------------------------------------------------
   Total time: 1.7517 seconds
   Per operation: 17.5174 μs
   Operations/sec: 57086
```

This shows that sorting 8 renderer extensions takes about 17.5 microseconds per operation.

### Extension Matching

The `str_ends_with()` function is very fast, with operations completing in the nanosecond range (200-300 ns). This is the hot path that runs on every view render, so the low overhead is important.

Example output:
```
2. Extension Matching Performance (str_ends_with)
------------------------------------------------------------
   'simple.php' -> 'php': 203.46 ns/op
   'template.blade.php' -> 'blade.php': 201.17 ns/op
```

### Overall Impact

The overall matching benchmark shows the trade-off between correctness and performance. Sorted matching ensures that files like "test.blade.php" are matched against "blade.php" renderer before "php" renderer, which is the correct behavior. The performance difference shown in the benchmark reflects the fact that sorting happens on setup (withRenderers call), not on every render.

Example output:
```
3. Overall Matching Performance (sorted vs unsorted)
------------------------------------------------------------
   Unsorted: 0.2873 seconds (0.5746 μs/op)
   Sorted:   0.7353 seconds (1.4706 μs/op)
   Difference: +155.95%
```

**Note**: The "sorted vs unsorted" comparison in this benchmark includes the sorting operation in each iteration to show the total cost. In real-world usage, sorting happens once during initialization, so the actual runtime impact is much lower.

## Why We Benchmark

These benchmarks help us:

1. **Ensure acceptable performance** - Verify that the renderer matching logic doesn't introduce significant overhead
2. **Track performance regressions** - Compare results across versions to catch performance degradations
3. **Make informed trade-offs** - Understand the cost of correctness features like extension sorting
4. **Optimize hot paths** - Identify which operations need the most optimization

## Related Issues

- [#289](https://github.com/yiisoft/view/issues/289) - Fix template file searching for double extensions
- [#291](https://github.com/yiisoft/view/pull/291) - Initial fix for double extensions
- [#292](https://github.com/yiisoft/view/pull/292) - Sort renderer extensions by length
