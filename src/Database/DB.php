<?php

namespace YAFS\Database;

/**
 * Database facade providing static access to database operations.
 * 
 * This is the primary interface developers interact with. It provides
 * a clean, static API that hides the complexity of connection management.
 * 
 * Example usage:
 * 
 * // Configure database
 * DB::addConnection([
 *     'host' => 'localhost',
 *     'database' => 'myapp',
 *     'username' => 'root',
 *     'password' => 'secret'
 * ]);
 * 
 * // Use query builder
 * $users = DB::table('users')->where('active', 1)->get();
 * 
 * // Raw queries
 * $results = DB::select('SELECT * FROM users WHERE id = ?', [1]);
 */
class DB
{
  /**
   * Configure a database connection.
   */
  public static function addConnection(array $config, string $name = 'default'): void
  {
    ConnectionManager::addConnection($config, $name);
  }

  /**
   * Get a query builder for a table.
   */
  public static function table(string $table, ?string $connection = null): QueryBuilder
  {
    return ConnectionManager::connection($connection)->table($table);
  }

  /**
   * Execute a SELECT query and return all results.
   */
  public static function select(string $sql, array $bindings = [], ?string $connection = null): array
  {
    return ConnectionManager::connection($connection)->select($sql, $bindings);
  }

  /**
   * Execute a SELECT query and return first result.
   */
  public static function selectOne(string $sql, array $bindings = [], ?string $connection = null): ?array
  {
    return ConnectionManager::connection($connection)->selectOne($sql, $bindings);
  }

  /**
   * Execute an INSERT, UPDATE, or DELETE query.
   */
  public static function statement(string $sql, array $bindings = [], ?string $connection = null): int
  {
    return ConnectionManager::connection($connection)->affectingStatement($sql, $bindings);
  }

  /**
   * Get the ID of the last inserted row.
   */
  public static function lastInsertId(?string $connection = null): string
  {
    return ConnectionManager::connection($connection)->lastInsertId();
  }

  /**
   * Start a transaction.
   */
  public static function beginTransaction(?string $connection = null): bool
  {
    return ConnectionManager::connection($connection)->beginTransaction();
  }

  /**
   * Commit a transaction.
   */
  public static function commit(?string $connection = null): bool
  {
    return ConnectionManager::connection($connection)->commit();
  }

  /**
   * Roll back a transaction.
   */
  public static function rollBack(?string $connection = null): bool
  {
    return ConnectionManager::connection($connection)->rollBack();
  }

  /**
   * Get the underlying connection instance.
   */
  public static function connection(?string $name = null): Connection
  {
    return ConnectionManager::connection($name);
  }

  /**
   * Disconnect from database.
   */
  public static function disconnect(?string $name = null): void
  {
    if ($name === null) {
      ConnectionManager::disconnectAll();
    } else {
      ConnectionManager::disconnect($name);
    }
  }
}
