<?php

namespace YAFS\Exceptions;

/**
 * Base exception for database-related errors.
 */
class DatabaseException extends \Exception
{
  /**
   * Create exception for missing configuration.
   */
  public static function missingConfig(string $key): self
  {
    return new self(
      "Missing required database configuration: '{$key}'"
    );
  }

  /**
   * Create exception for connection failure.
   */
  public static function connectionFailed(string $reason): self
  {
    return new self(
      "Database connection failed: {$reason}"
    );
  }

  /**
   * Create exception for query failure.
   */
  public static function queryFailed(string $sql, string $reason): self
  {
    return new self(
      "Query failed: {$reason}\nSQL: {$sql}"
    );
  }

  /**
   * Create exception for invalid table name.
   */
  public static function invalidTable(string $table): self
  {
    return new self(
      "Invalid table name: '{$table}'. Table names must contain only alphanumeric characters and underscores."
    );
  }

  /**
   * Create exception for invalid column name.
   */
  public static function invalidColumn(string $column): self
  {
    return new self(
      "Invalid column name: '{$column}'. Column names must contain only alphanumeric characters and underscores."
    );
  }
}
