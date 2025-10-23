<?php

namespace YAFS\Testing;

/**
 * Assertion functions for testing.
 * 
 * These are the building blocks of tests. Each assertion checks that
 * something is true, and throws an exception if it's not. This is how
 * tests communicate success or failure to the test runner.
 */
class Assert
{
  /**
   * Assert that a value is true.
   */
  public static function assertTrue($value, string $message = ''): void
  {
    if ($value !== true) {
      $actual = var_export($value, true);
      throw new \Exception(
        $message ?: "Expected true, got {$actual}"
      );
    }
  }

  /**
   * Assert that a value is false.
   */
  public static function assertFalse($value, string $message = ''): void
  {
    if ($value !== false) {
      $actual = var_export($value, true);
      throw new \Exception(
        $message ?: "Expected false, got {$actual}"
      );
    }
  }

  /**
   * Assert that two values are equal.
   * 
   * This uses loose comparison (==) by default. For strict comparison,
   * use assertSame().
   */
  public static function assertEquals($expected, $actual, string $message = ''): void
  {
    if ($expected != $actual) {
      $expectedStr = var_export($expected, true);
      $actualStr = var_export($actual, true);
      throw new \Exception(
        $message ?: "Expected {$expectedStr}, got {$actualStr}"
      );
    }
  }

  /**
   * Assert that two values are identical (strict comparison).
   * 
   * This uses === which checks both value and type.
   */
  public static function assertSame($expected, $actual, string $message = ''): void
  {
    if ($expected !== $actual) {
      $expectedStr = var_export($expected, true);
      $actualStr = var_export($actual, true);
      throw new \Exception(
        $message ?: "Expected {$expectedStr} (same), got {$actualStr}"
      );
    }
  }

  /**
   * Assert that a value is null.
   */
  public static function assertNull($value, string $message = ''): void
  {
    if ($value !== null) {
      $actual = var_export($value, true);
      throw new \Exception(
        $message ?: "Expected null, got {$actual}"
      );
    }
  }

  /**
   * Assert that a value is not null.
   */
  public static function assertNotNull($value, string $message = ''): void
  {
    if ($value === null) {
      throw new \Exception(
        $message ?: "Expected non-null value, got null"
      );
    }
  }

  /**
   * Assert that an array contains a specific key.
   */
  public static function assertArrayHasKey($key, array $array, string $message = ''): void
  {
    if (!array_key_exists($key, $array)) {
      throw new \Exception(
        $message ?: "Array does not contain key '{$key}'"
      );
    }
  }

  /**
   * Assert that a string contains a substring.
   */
  public static function assertStringContains(string $needle, string $haystack, string $message = ''): void
  {
    if (strpos($haystack, $needle) === false) {
      throw new \Exception(
        $message ?: "String '{$haystack}' does not contain '{$needle}'"
      );
    }
  }

  /**
   * Assert that a value is an instance of a specific class.
   */
  public static function assertInstanceOf(string $expected, $actual, string $message = ''): void
  {
    if (!($actual instanceof $expected)) {
      $actualType = is_object($actual) ? get_class($actual) : gettype($actual);
      throw new \Exception(
        $message ?: "Expected instance of {$expected}, got {$actualType}"
      );
    }
  }

  /**
   * Assert that a callable throws an exception.
   * 
   * This is useful for testing error handling. You want to verify that
   * invalid input causes appropriate exceptions.
   */
  public static function assertThrows(string $expectedException, callable $callable, string $message = ''): void
  {
    try {
      $callable();
      throw new \Exception(
        $message ?: "Expected {$expectedException} to be thrown, but nothing was thrown"
      );
    } catch (\Exception $e) {
      if (!($e instanceof $expectedException)) {
        throw new \Exception(
          $message ?: "Expected {$expectedException}, got " . get_class($e)
        );
      }
    }
  }
}
