<?php

namespace YAFS\Testing;

/**
 * A minimal testing framework for YAFS.
 * 
 * This provides everything needed to write and run tests without external
 * dependencies. It's inspired by PHPUnit but simplified to focus on clarity
 * and ease of use.
 * 
 * The philosophy here is that testing should be straightforward. You define
 * test methods, make assertions about expected behavior, and the framework
 * handles running tests and reporting results.
 */
class TestRunner
{
  private array $tests = [];
  private array $results = [];
  private int $passCount = 0;
  private int $failCount = 0;

  /**
   * Register a test function.
   * 
   * Tests are just callables that make assertions. If a test throws an
   * exception, it's considered failed. If it completes without exceptions,
   * it's considered passed.
   */
  public function test(string $name, callable $testFunction): void
  {
    $this->tests[$name] = $testFunction;
  }

  /**
   * Run all registered tests and collect results.
   */
  public function run(): void
  {
    echo "\n=== Running Tests ===\n\n";

    foreach ($this->tests as $name => $testFunction) {
      try {
        // Run the test function
        $testFunction();

        // If we got here without exception, test passed
        $this->recordPass($name);
      } catch (\Exception $e) {
        // Test failed with an exception
        $this->recordFail($name, $e);
      }
    }

    $this->printSummary();
  }

  /**
   * Record a passing test.
   */
  private function recordPass(string $name): void
  {
    $this->passCount++;
    $this->results[$name] = ['status' => 'pass'];
    echo "✓ {$name}\n";
  }

  /**
   * Record a failing test.
   */
  private function recordFail(string $name, \Exception $e): void
  {
    $this->failCount++;
    $this->results[$name] = [
      'status' => 'fail',
      'message' => $e->getMessage(),
      'file' => $e->getFile(),
      'line' => $e->getLine()
    ];
    echo "✗ {$name}\n";
    echo "  Error: {$e->getMessage()}\n";
    echo "  at {$e->getFile()}:{$e->getLine()}\n\n";
  }

  /**
   * Print a summary of test results.
   */
  private function printSummary(): void
  {
    $total = $this->passCount + $this->failCount;

    echo "\n=== Test Summary ===\n";
    echo "Total: {$total} tests\n";
    echo "Passed: {$this->passCount}\n";
    echo "Failed: {$this->failCount}\n";

    if ($this->failCount === 0) {
      echo "\n✓ All tests passed!\n";
    } else {
      echo "\n✗ Some tests failed.\n";
    }
  }

  /**
   * Get the test results for programmatic access.
   */
  public function getResults(): array
  {
    return [
      'total' => $this->passCount + $this->failCount,
      'passed' => $this->passCount,
      'failed' => $this->failCount,
      'results' => $this->results
    ];
  }
}
