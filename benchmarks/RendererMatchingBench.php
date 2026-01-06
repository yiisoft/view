<?php

declare(strict_types=1);

/**
 * Benchmark for renderer extension matching performance.
 *
 * This benchmark tests the performance of:
 * 1. Sorting renderer extensions by length (uksort operation)
 * 2. Matching file extensions using str_ends_with()
 * 3. Impact of sorting on overall matching performance
 *
 * Run with: php benchmarks/RendererMatchingBench.php
 */

namespace Yiisoft\View\Benchmarks;

require_once __DIR__ . '/../vendor/autoload.php';

use Yiisoft\View\PhpTemplateRenderer;
use Yiisoft\View\TemplateRendererInterface;

/**
 * Simple benchmark runner for renderer matching operations.
 */
class RendererMatchingBench
{
    private const ITERATIONS = 100000;
    
    /**
     * Run all benchmarks and display results.
     */
    public function run(): void
    {
        echo "Renderer Matching Performance Benchmark\n";
        echo str_repeat('=', 60) . "\n\n";
        
        $this->benchmarkExtensionSorting();
        echo "\n";
        
        $this->benchmarkExtensionMatching();
        echo "\n";
        
        $this->benchmarkOverallImpact();
        echo "\n";
    }
    
    /**
     * Benchmark the uksort operation for sorting extensions by length.
     */
    private function benchmarkExtensionSorting(): void
    {
        echo "1. Extension Sorting Performance (uksort)\n";
        echo str_repeat('-', 60) . "\n";
        
        $renderers = $this->createRendererArray();
        
        // Benchmark sorting
        $start = hrtime(true);
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $copy = $renderers;
            uksort($copy, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));
        }
        $end = hrtime(true);
        
        $duration = ($end - $start) / 1e9; // Convert to seconds
        $perOperation = ($duration / self::ITERATIONS) * 1e6; // Convert to microseconds
        
        echo sprintf("   Total time: %.4f seconds\n", $duration);
        echo sprintf("   Per operation: %.4f μs\n", $perOperation);
        echo sprintf("   Operations/sec: %.0f\n", self::ITERATIONS / $duration);
    }
    
    /**
     * Benchmark str_ends_with() performance for extension matching.
     */
    private function benchmarkExtensionMatching(): void
    {
        echo "2. Extension Matching Performance (str_ends_with)\n";
        echo str_repeat('-', 60) . "\n";
        
        $testCases = [
            'simple.php' => 'php',
            'template.blade.php' => 'blade.php',
            'view.twig' => 'twig',
            'component.blade.php' => 'blade.php',
        ];
        
        foreach ($testCases as $filename => $expectedExt) {
            $start = hrtime(true);
            for ($i = 0; $i < self::ITERATIONS; $i++) {
                str_ends_with($filename, '.' . $expectedExt);
            }
            $end = hrtime(true);
            
            $duration = ($end - $start) / 1e9;
            $perOperation = ($duration / self::ITERATIONS) * 1e9; // Convert to nanoseconds
            
            echo sprintf("   '%s' -> '%s': %.2f ns/op\n", $filename, $expectedExt, $perOperation);
        }
    }
    
    /**
     * Benchmark the overall impact of sorting on renderer matching.
     */
    private function benchmarkOverallImpact(): void
    {
        echo "3. Overall Matching Performance (sorted vs unsorted)\n";
        echo str_repeat('-', 60) . "\n";
        
        $renderers = $this->createRendererArray();
        $sortedRenderers = $renderers;
        uksort($sortedRenderers, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));
        
        $testFiles = [
            'simple.php',
            'template.blade.php',
            'view.twig',
            'component.blade.php',
            'page.php',
        ];
        
        // Benchmark unsorted matching
        $start = hrtime(true);
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            foreach ($testFiles as $filename) {
                $this->findRenderer($filename, $renderers);
            }
        }
        $end = hrtime(true);
        $unsortedDuration = ($end - $start) / 1e9;
        
        // Benchmark sorted matching
        $start = hrtime(true);
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            foreach ($testFiles as $filename) {
                $this->findRenderer($filename, $sortedRenderers);
            }
        }
        $end = hrtime(true);
        $sortedDuration = ($end - $start) / 1e9;
        
        echo sprintf("   Unsorted: %.4f seconds (%.4f μs/op)\n", 
            $unsortedDuration, 
            ($unsortedDuration / (self::ITERATIONS * count($testFiles))) * 1e6
        );
        echo sprintf("   Sorted:   %.4f seconds (%.4f μs/op)\n", 
            $sortedDuration,
            ($sortedDuration / (self::ITERATIONS * count($testFiles))) * 1e6
        );
        
        $diff = (($sortedDuration - $unsortedDuration) / $unsortedDuration) * 100;
        echo sprintf("   Difference: %+.2f%%\n", $diff);
        
        if ($diff < 0) {
            echo sprintf("   Sorted matching is %.2f%% faster\n", abs($diff));
        } else {
            echo sprintf("   Sorted matching is %.2f%% slower\n", $diff);
        }
    }
    
    /**
     * Simulate the renderer matching logic from ViewTrait.
     */
    private function findRenderer(string $filename, array $renderers): ?TemplateRendererInterface
    {
        foreach ($renderers as $extension => $candidateRenderer) {
            if ($extension === '') {
                continue;
            }
            
            if (!str_ends_with($filename, '.' . $extension)) {
                continue;
            }
            
            return $candidateRenderer;
        }
        
        return null;
    }
    
    /**
     * Create a sample renderer array for testing.
     */
    private function createRendererArray(): array
    {
        $phpRenderer = new PhpTemplateRenderer();
        
        return [
            'php' => $phpRenderer,
            'blade.php' => $phpRenderer,
            'twig' => $phpRenderer,
            'html.php' => $phpRenderer,
            'phtml' => $phpRenderer,
            'tpl.php' => $phpRenderer,
            'view.php' => $phpRenderer,
            'inc.php' => $phpRenderer,
        ];
    }
}

// Run the benchmark
$bench = new RendererMatchingBench();
$bench->run();
