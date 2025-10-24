<?php

namespace YAFS\Database;

use PDO;
use PDOException;
use PDOStatement;
use YAFS\Exceptions\DatabaseException;

/**
 * Database connection wrapper.
 * 
 * This class wraps PDO and provides a cleaner, more secure interface.
 * All queries are automatically prepared to prevent SQL injection.
 * 
 * @author YAFS Framework
 * @package YAFS\Database
 */
class Connection
{
  /**
   * The PDO connection instance.
   */
  protected ?PDO $pdo = null;

  /**
   * Connection configuration.
   */
  protected array $config;

  /**
   * Whether we're currently in a transaction.
   */
  protected bool $inTransaction = false;

  /**
   * Create a new connection instance.
   * 
   * @param array $config Database configuration
   */
  public function __construct(array $config)
  {
    $this->config = array_merge([
      'driver' => 'mysql',
      'host' => 'localhost',
      'port' => 3306,
      'database' => 'testdb',
      'username' => 'root',
      'password' => '',
      'charset' => 'utf8mb4',
      'collation' => 'utf8mb4_unicode_ci',
      'options' => []
    ], $config);
  }

  /**
   * Get the PDO connection, creating it if necessary.
   * 
   * @return PDO
   * @throws DatabaseException
   */
  public function getPdo(): PDO
  {
    if ($this->pdo === null) {
      $this->connect();
    }

    return $this->pdo;
  }

  /**
   * Establish the database connection.
   * 
   * @return void
   * @throws DatabaseException
   */
  protected function connect(): void
  {
    try {
      $dsn = $this->buildDsn();

      // Default PDO options for security
      $options = array_merge([
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false, // Use real prepared statements
        PDO::ATTR_STRINGIFY_FETCHES => false,
      ], $this->config['options']);

      $this->pdo = new PDO(
        $dsn,
        $this->config['username'],
        $this->config['password'],
        $options
      );

      // Set charset
      if ($this->config['driver'] === 'mysql') {
        $this->pdo->exec("SET NAMES '{$this->config['charset']}' COLLATE '{$this->config['collation']}'");
      }
    } catch (PDOException $e) {
      throw new DatabaseException(
        "Connection failed: " . $e->getMessage(),
        (int) $e->getCode(),
        $e
      );
    }
  }

  /**
   * Build the DSN string from config.
   * 
   * @return string
   */
  protected function buildDsn(): string
  {
    $driver = $this->config['driver'];
    $host = $this->config['host'];
    $port = $this->config['port'];
    $database = $this->config['database'];

    if ($driver === 'mysql') {
      return "mysql:host={$host};port={$port};dbname={$database}";
    }

    throw new DatabaseException("Unsupported driver: {$driver}");
  }

  /**
   * Execute a SELECT query and return all results.
   * 
   * @param string $sql SQL query
   * @param array $bindings Query parameters
   * @return array
   * @throws DatabaseException
   */
  public function select(string $sql, array $bindings = []): array
  {
    $statement = $this->execute($sql, $bindings);
    return $statement->fetchAll();
  }

  /**
   * Execute a SELECT query and return the first result.
   * 
   * @param string $sql SQL query
   * @param array $bindings Query parameters
   * @return array|null
   * @throws DatabaseException
   */
  public function selectOne(string $sql, array $bindings = []): ?array
  {
    $statement = $this->execute($sql, $bindings);
    $result = $statement->fetch();
    return $result ?: null;
  }

  /**
   * Execute an INSERT, UPDATE, or DELETE query.
   * 
   * @param string $sql SQL query
   * @param array $bindings Query parameters
   * @return int Number of affected rows
   * @throws DatabaseException
   */
  public function affectingStatement(string $sql, array $bindings = []): int
  {
    $statement = $this->execute($sql, $bindings);
    return $statement->rowCount();
  }

  /**
   * Execute an unprepared statement (use with caution).
   * 
   * WARNING: This method does NOT use prepared statements.
   * Only use for DDL queries like CREATE, DROP, TRUNCATE, etc.
   * Never use with user input!
   * 
   * @param string $sql SQL query
   * @return bool
   * @throws DatabaseException
   */
  public function unprepared(string $sql): bool
  {
    try {
      return $this->getPdo()->exec($sql) !== false;
    } catch (PDOException $e) {
      throw new DatabaseException(
        "Query failed: " . $e->getMessage(),
        (int) $e->getCode(),
        $e
      );
    }
  }

  /**
   * Execute a prepared statement.
   * 
   * @param string $sql SQL query
   * @param array $bindings Query parameters
   * @return PDOStatement
   * @throws DatabaseException
   */
  protected function execute(string $sql, array $bindings = []): PDOStatement
  {
    try {
      $statement = $this->getPdo()->prepare($sql);

      // Bind parameters with proper types
      foreach ($bindings as $key => $value) {
        $type = $this->getBindingType($value);
        $statement->bindValue(
          is_int($key) ? $key + 1 : $key,
          $value,
          $type
        );
      }

      $statement->execute();

      return $statement;
    } catch (PDOException $e) {
      throw new DatabaseException(
        "Query failed: " . $e->getMessage() . "\nSQL: {$sql}",
        (int) $e->getCode(),
        $e
      );
    }
  }

  /**
   * Determine the PDO binding type for a value.
   * 
   * @param mixed $value
   * @return int PDO::PARAM_* constant
   */
  protected function getBindingType($value): int
  {
    if (is_int($value)) {
      return PDO::PARAM_INT;
    }

    if (is_bool($value)) {
      return PDO::PARAM_BOOL;
    }

    if ($value === null) {
      return PDO::PARAM_NULL;
    }

    return PDO::PARAM_STR;
  }

  /**
   * Get a query builder for a table.
   * 
   * @param string $table Table name
   * @return QueryBuilder
   */
  public function table(string $table): QueryBuilder
  {
    return new QueryBuilder($this, $table);
  }

  /**
   * Begin a transaction.
   * 
   * @return bool
   * @throws DatabaseException
   */
  public function beginTransaction(): bool
  {
    if ($this->inTransaction) {
      throw new DatabaseException("Already in a transaction");
    }

    try {
      $result = $this->getPdo()->beginTransaction();
      $this->inTransaction = true;
      return $result;
    } catch (PDOException $e) {
      throw new DatabaseException(
        "Failed to begin transaction: " . $e->getMessage(),
        (int) $e->getCode(),
        $e
      );
    }
  }

  /**
   * Commit the transaction.
   * 
   * @return bool
   * @throws DatabaseException
   */
  public function commit(): bool
  {
    if (!$this->inTransaction) {
      throw new DatabaseException("No active transaction");
    }

    try {
      $result = $this->getPdo()->commit();
      $this->inTransaction = false;
      return $result;
    } catch (PDOException $e) {
      throw new DatabaseException(
        "Failed to commit transaction: " . $e->getMessage(),
        (int) $e->getCode(),
        $e
      );
    }
  }

  /**
   * Roll back the transaction.
   * 
   * @return bool
   * @throws DatabaseException
   */
  public function rollBack(): bool
  {
    if (!$this->inTransaction) {
      throw new DatabaseException("No active transaction");
    }

    try {
      $result = $this->getPdo()->rollBack();
      $this->inTransaction = false;
      return $result;
    } catch (PDOException $e) {
      throw new DatabaseException(
        "Failed to rollback transaction: " . $e->getMessage(),
        (int) $e->getCode(),
        $e
      );
    }
  }

  /**
   * Check if currently in a transaction.
   * 
   * @return bool
   */
  public function inTransaction(): bool
  {
    return $this->inTransaction;
  }

  /**
   * Get the ID of the last inserted row.
   * 
   * @return string
   */
  public function lastInsertId(): string
  {
    return $this->getPdo()->lastInsertId();
  }

  /**
   * Disconnect from the database.
   * 
   * @return void
   */
  public function disconnect(): void
  {
    $this->pdo = null;
    $this->inTransaction = false;
  }
}
