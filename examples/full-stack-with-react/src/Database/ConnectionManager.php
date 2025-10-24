<?php

namespace YAFS\Database;

use YAFS\Exceptions\DatabaseException;

/**
 * Manages database connections.
 * 
 * This class handles:
 * - Storing database configuration
 * - Creating connections on demand
 * - Reusing connections (simple connection pooling)
 * - Managing multiple named connections if needed
 * 
 * The ConnectionManager ensures that we don't create multiple
 * connections unnecessarily, which is important for performance.
 */
class ConnectionManager
{
  /**
   * Stored configurations for named connections.
   */
  private static array $configs = [];

  /**
   * Active connections, keyed by name.
   */
  private static array $connections = [];

  /**
   * Name of the default connection.
   */
  private static string $defaultConnection = 'default';

  /**
   * Add a database configuration.
   *
   * @param array $config Database configuration
   * @param string $name Connection name
   */
  public static function addConnection(array $config, string $name = 'default'): void
  {
    self::$configs[$name] = $config;

    // If this is the first connection added, make it the default
    if (count(self::$configs) === 1) {
      self::$defaultConnection = $name;
    }
  }

  /**
   * Get a connection by name.
   * 
   * If the connection doesn't exist yet, it will be created.
   * If it already exists, the existing connection is returned.
   * This is basic connection pooling.
   *
   * @param string|null $name Connection name, or null for default
   * @return Connection
   * @throws DatabaseException If configuration doesn't exist
   */
  public static function connection(?string $name = null): Connection
  {
    $name = $name ?? self::$defaultConnection;

    // Return existing connection if available
    if (isset(self::$connections[$name])) {
      return self::$connections[$name];
    }

    // Check if configuration exists
    if (!isset(self::$configs[$name])) {
      throw new DatabaseException(
        "Database configuration '{$name}' not found. " .
          "Use ConnectionManager::addConnection() to configure it."
      );
    }

    // Create and store new connection
    $connection = new Connection(self::$configs[$name]);
    self::$connections[$name] = $connection;

    return $connection;
  }

  /**
   * Set the default connection name.
   */
  public static function setDefaultConnection(string $name): void
  {
    self::$defaultConnection = $name;
  }

  /**
   * Get the default connection name.
   */
  public static function getDefaultConnection(): string
  {
    return self::$defaultConnection;
  }

  /**
   * Disconnect a specific connection.
   */
  public static function disconnect(string $name): void
  {
    if (isset(self::$connections[$name])) {
      self::$connections[$name]->disconnect();
      unset(self::$connections[$name]);
    }
  }

  /**
   * Disconnect all connections.
   */
  public static function disconnectAll(): void
  {
    foreach (self::$connections as $name => $connection) {
      $connection->disconnect();
    }
    self::$connections = [];
  }

  /**
   * Check if a connection exists and is active.
   */
  public static function hasConnection(string $name): bool
  {
    return isset(self::$connections[$name]);
  }

  /**
   * Get all configured connection names.
   */
  public static function getConnectionNames(): array
  {
    return array_keys(self::$configs);
  }
}
