<?php

namespace YAFS\Database;

use YAFS\Exceptions\DatabaseException;

/**
 * Fluent query builder for constructing SQL queries safely.
 * 
 * This class provides an intuitive, chainable interface for building queries
 * while ensuring all user input is properly parameterized. It's impossible
 * to create SQL injection vulnerabilities when using this builder correctly.
 * 
 * Example usage:
 * 
 * // SELECT queries
 * $users = DB::table('users')
 *     ->select('id', 'name', 'email')
 *     ->where('active', 1)
 *     ->where('role', '!=', 'guest')
 *     ->orderBy('name')
 *     ->limit(10)
 *     ->get();
 * 
 * // INSERT
 * $id = DB::table('users')->insert([
 *     'name' => 'John Doe',
 *     'email' => 'john@example.com'
 * ]);
 * 
 * // UPDATE
 * DB::table('users')
 *     ->where('id', 5)
 *     ->update(['name' => 'Jane Doe']);
 * 
 * // DELETE
 * DB::table('users')->where('id', 5)->delete();
 * 
 * @author YAFS Framework
 * @package YAFS\Database
 */
class QueryBuilder
{
  /**
   * The database connection instance.
   */
  protected Connection $connection;

  /**
   * The table name for the query.
   */
  protected string $table;

  /**
   * The columns to select.
   */
  protected array $columns = ['*'];

  /**
   * The WHERE clauses for the query.
   */
  protected array $wheres = [];

  /**
   * The bindings for the query.
   */
  protected array $bindings = [];

  /**
   * The ORDER BY clauses.
   */
  protected array $orders = [];

  /**
   * The LIMIT value.
   */
  protected ?int $limit = null;

  /**
   * The OFFSET value.
   */
  protected ?int $offset = null;

  /**
   * The JOIN clauses.
   */
  protected array $joins = [];

  /**
   * The GROUP BY columns.
   */
  protected array $groups = [];

  /**
   * The HAVING clauses.
   */
  protected array $havings = [];

  /**
   * Whether to use DISTINCT.
   */
  protected bool $distinct = false;

  /**
   * Valid comparison operators.
   */
  protected const OPERATORS = [
    '=',
    '!=',
    '<>',
    '<',
    '>',
    '<=',
    '>=',
    'LIKE',
    'NOT LIKE',
    'IN',
    'NOT IN',
    'IS NULL',
    'IS NOT NULL',
    'BETWEEN',
    'NOT BETWEEN'
  ];

  /**
   * Create a new query builder instance.
   */
  public function __construct(Connection $connection, string $table)
  {
    $this->connection = $connection;
    $this->table = $table;
  }

  /**
   * Set the columns to select.
   * 
   * @param string ...$columns Column names to select
   * @return self
   */
  public function select(string ...$columns): self
  {
    $this->columns = $columns;
    return $this;
  }

  /**
   * Add DISTINCT to the query.
   * 
   * @return self
   */
  public function distinct(): self
  {
    $this->distinct = true;
    return $this;
  }

  /**
   * Add a basic WHERE clause.
   * 
   * Usage:
   * - where('name', 'John')              // name = 'John'
   * - where('age', '>', 18)              // age > 18
   * - where('status', 'IN', [1, 2, 3])   // status IN (1, 2, 3)
   * 
   * @param string $column Column name
   * @param mixed $operatorOrValue Operator or value if operator is '='
   * @param mixed $value Value to compare (optional)
   * @return self
   */
  public function where(string $column, $operatorOrValue, $value = null): self
  {
    // If only 2 arguments, assume '=' operator
    if ($value === null) {
      $value = $operatorOrValue;
      $operator = '=';
    } else {
      $operator = strtoupper($operatorOrValue);
    }

    // Validate operator
    if (!in_array($operator, self::OPERATORS, true)) {
      throw new DatabaseException("Invalid operator: {$operator}");
    }

    $this->wheres[] = [
      'type' => 'basic',
      'column' => $column,
      'operator' => $operator,
      'value' => $value,
      'boolean' => 'AND'
    ];

    return $this;
  }

  /**
   * Add an OR WHERE clause.
   * 
   * @param string $column Column name
   * @param mixed $operatorOrValue Operator or value
   * @param mixed $value Value (optional)
   * @return self
   */
  public function orWhere(string $column, $operatorOrValue, $value = null): self
  {
    if ($value === null) {
      $value = $operatorOrValue;
      $operator = '=';
    } else {
      $operator = strtoupper($operatorOrValue);
    }

    if (!in_array($operator, self::OPERATORS, true)) {
      throw new DatabaseException("Invalid operator: {$operator}");
    }

    $this->wheres[] = [
      'type' => 'basic',
      'column' => $column,
      'operator' => $operator,
      'value' => $value,
      'boolean' => 'OR'
    ];

    return $this;
  }

  /**
   * Add a WHERE IN clause.
   * 
   * @param string $column Column name
   * @param array $values Array of values
   * @return self
   */
  public function whereIn(string $column, array $values): self
  {
    $this->wheres[] = [
      'type' => 'in',
      'column' => $column,
      'values' => $values,
      'boolean' => 'AND',
      'not' => false
    ];

    return $this;
  }

  /**
   * Add a WHERE NOT IN clause.
   * 
   * @param string $column Column name
   * @param array $values Array of values
   * @return self
   */
  public function whereNotIn(string $column, array $values): self
  {
    $this->wheres[] = [
      'type' => 'in',
      'column' => $column,
      'values' => $values,
      'boolean' => 'AND',
      'not' => true
    ];

    return $this;
  }

  /**
   * Add a WHERE NULL clause.
   * 
   * @param string $column Column name
   * @return self
   */
  public function whereNull(string $column): self
  {
    $this->wheres[] = [
      'type' => 'null',
      'column' => $column,
      'boolean' => 'AND',
      'not' => false
    ];

    return $this;
  }

  /**
   * Add a WHERE NOT NULL clause.
   * 
   * @param string $column Column name
   * @return self
   */
  public function whereNotNull(string $column): self
  {
    $this->wheres[] = [
      'type' => 'null',
      'column' => $column,
      'boolean' => 'AND',
      'not' => true
    ];

    return $this;
  }

  /**
   * Add a WHERE BETWEEN clause.
   * 
   * @param string $column Column name
   * @param mixed $min Minimum value
   * @param mixed $max Maximum value
   * @return self
   */
  public function whereBetween(string $column, $min, $max): self
  {
    $this->wheres[] = [
      'type' => 'between',
      'column' => $column,
      'min' => $min,
      'max' => $max,
      'boolean' => 'AND',
      'not' => false
    ];

    return $this;
  }

  /**
   * Add a WHERE NOT BETWEEN clause.
   * 
   * @param string $column Column name
   * @param mixed $min Minimum value
   * @param mixed $max Maximum value
   * @return self
   */
  public function whereNotBetween(string $column, $min, $max): self
  {
    $this->wheres[] = [
      'type' => 'between',
      'column' => $column,
      'min' => $min,
      'max' => $max,
      'boolean' => 'AND',
      'not' => true
    ];

    return $this;
  }

  /**
   * Add a raw WHERE clause (use with caution).
   * 
   * WARNING: This method allows raw SQL and should only be used
   * when absolutely necessary. Ensure you sanitize inputs!
   * 
   * @param string $sql Raw SQL condition
   * @param array $bindings Values to bind
   * @return self
   */
  public function whereRaw(string $sql, array $bindings = []): self
  {
    $this->wheres[] = [
      'type' => 'raw',
      'sql' => $sql,
      'bindings' => $bindings,
      'boolean' => 'AND'
    ];

    return $this;
  }

  /**
   * Add an INNER JOIN clause.
   * 
   * @param string $table Table to join
   * @param string $first First column
   * @param string $operator Comparison operator
   * @param string $second Second column
   * @return self
   */
  public function join(string $table, string $first, string $operator, string $second): self
  {
    $this->joins[] = [
      'type' => 'INNER',
      'table' => $table,
      'first' => $first,
      'operator' => $operator,
      'second' => $second
    ];

    return $this;
  }

  /**
   * Add a LEFT JOIN clause.
   * 
   * @param string $table Table to join
   * @param string $first First column
   * @param string $operator Comparison operator
   * @param string $second Second column
   * @return self
   */
  public function leftJoin(string $table, string $first, string $operator, string $second): self
  {
    $this->joins[] = [
      'type' => 'LEFT',
      'table' => $table,
      'first' => $first,
      'operator' => $operator,
      'second' => $second
    ];

    return $this;
  }

  /**
   * Add a RIGHT JOIN clause.
   * 
   * @param string $table Table to join
   * @param string $first First column
   * @param string $operator Comparison operator
   * @param string $second Second column
   * @return self
   */
  public function rightJoin(string $table, string $first, string $operator, string $second): self
  {
    $this->joins[] = [
      'type' => 'RIGHT',
      'table' => $table,
      'first' => $first,
      'operator' => $operator,
      'second' => $second
    ];

    return $this;
  }

  /**
   * Add an ORDER BY clause.
   * 
   * @param string $column Column name
   * @param string $direction 'ASC' or 'DESC'
   * @return self
   */
  public function orderBy(string $column, string $direction = 'ASC'): self
  {
    $direction = strtoupper($direction);

    if (!in_array($direction, ['ASC', 'DESC'], true)) {
      throw new DatabaseException("Invalid order direction: {$direction}");
    }

    $this->orders[] = [
      'column' => $column,
      'direction' => $direction
    ];

    return $this;
  }

  /**
   * Add a GROUP BY clause.
   * 
   * @param string ...$columns Column names
   * @return self
   */
  public function groupBy(string ...$columns): self
  {
    $this->groups = array_merge($this->groups, $columns);
    return $this;
  }

  /**
   * Add a HAVING clause.
   * 
   * @param string $column Column name
   * @param mixed $operatorOrValue Operator or value
   * @param mixed $value Value (optional)
   * @return self
   */
  public function having(string $column, $operatorOrValue, $value = null): self
  {
    if ($value === null) {
      $value = $operatorOrValue;
      $operator = '=';
    } else {
      $operator = strtoupper($operatorOrValue);
    }

    $this->havings[] = [
      'column' => $column,
      'operator' => $operator,
      'value' => $value
    ];

    return $this;
  }

  /**
   * Set the LIMIT value.
   * 
   * @param int $limit Number of rows to limit
   * @return self
   */
  public function limit(int $limit): self
  {
    if ($limit < 0) {
      throw new DatabaseException("Limit must be a positive integer");
    }

    $this->limit = $limit;
    return $this;
  }

  /**
   * Set the OFFSET value.
   * 
   * @param int $offset Number of rows to skip
   * @return self
   */
  public function offset(int $offset): self
  {
    if ($offset < 0) {
      throw new DatabaseException("Offset must be a positive integer");
    }

    $this->offset = $offset;
    return $this;
  }

  /**
   * Alias for limit() for pagination.
   * 
   * @param int $count Number of items per page
   * @return self
   */
  public function take(int $count): self
  {
    return $this->limit($count);
  }

  /**
   * Alias for offset() for pagination.
   * 
   * @param int $count Number of items to skip
   * @return self
   */
  public function skip(int $count): self
  {
    return $this->offset($count);
  }

  /**
   * Set up pagination.
   * 
   * @param int $page Page number (1-based)
   * @param int $perPage Items per page
   * @return self
   */
  public function paginate(int $page, int $perPage = 15): self
  {
    if ($page < 1) {
      throw new DatabaseException("Page must be 1 or greater");
    }

    $this->limit = $perPage;
    $this->offset = ($page - 1) * $perPage;

    return $this;
  }

  /**
   * Execute the query and return all results.
   * 
   * @return array Array of result rows
   */
  public function get(): array
  {
    $sql = $this->toSql();
    return $this->connection->select($sql, $this->bindings);
  }

  /**
   * Execute the query and return the first result.
   * 
   * @return array|null First row or null if no results
   */
  public function first(): ?array
  {
    $this->limit(1);
    $results = $this->get();
    return $results[0] ?? null;
  }

  /**
   * Find a row by its primary key.
   * 
   * @param mixed $id Primary key value
   * @param string $column Primary key column name
   * @return array|null
   */
  public function find($id, string $column = 'id'): ?array
  {
    return $this->where($column, $id)->first();
  }

  /**
   * Get a single column's value from the first result.
   * 
   * @param string $column Column name
   * @return mixed Column value or null
   */
  public function value(string $column)
  {
    $result = $this->first();
    return $result[$column] ?? null;
  }

  /**
   * Get an array of values from a single column.
   * 
   * @param string $column Column name
   * @return array Array of column values
   */
  public function pluck(string $column): array
  {
    $results = $this->get();
    return array_column($results, $column);
  }

  /**
   * Check if any rows exist for the query.
   * 
   * @return bool
   */
  public function exists(): bool
  {
    $sql = $this->toSql();
    $result = $this->connection->selectOne($sql, $this->bindings);
    return $result !== null;
  }

  /**
   * Get the count of rows.
   * 
   * @param string $column Column to count (default: *)
   * @return int
   */
  public function count(string $column = '*'): int
  {
    $original = $this->columns;
    $this->columns = ["COUNT({$column}) as aggregate"];

    $result = $this->first();
    $this->columns = $original;

    return (int) ($result['aggregate'] ?? 0);
  }

  /**
   * Get the maximum value of a column.
   * 
   * @param string $column Column name
   * @return mixed
   */
  public function max(string $column)
  {
    return $this->aggregate('MAX', $column);
  }

  /**
   * Get the minimum value of a column.
   * 
   * @param string $column Column name
   * @return mixed
   */
  public function min(string $column)
  {
    return $this->aggregate('MIN', $column);
  }

  /**
   * Get the average value of a column.
   * 
   * @param string $column Column name
   * @return mixed
   */
  public function avg(string $column)
  {
    return $this->aggregate('AVG', $column);
  }

  /**
   * Get the sum of a column.
   * 
   * @param string $column Column name
   * @return mixed
   */
  public function sum(string $column)
  {
    return $this->aggregate('SUM', $column);
  }

  /**
   * Execute an aggregate function.
   * 
   * @param string $function Aggregate function name
   * @param string $column Column name
   * @return mixed
   */
  protected function aggregate(string $function, string $column)
  {
    $original = $this->columns;
    $this->columns = ["{$function}({$column}) as aggregate"];

    $result = $this->first();
    $this->columns = $original;

    return $result['aggregate'] ?? null;
  }

  /**
   * Insert a new record.
   * 
   * @param array $data Associative array of column => value
   * @return string Last insert ID
   */
  public function insert(array $data): string
  {
    if (empty($data)) {
      throw new DatabaseException("Cannot insert empty data");
    }

    $columns = array_keys($data);
    $placeholders = array_fill(0, count($columns), '?');

    $sql = sprintf(
      "INSERT INTO %s (%s) VALUES (%s)",
      $this->quoteIdentifier($this->table),
      implode(', ', array_map([$this, 'quoteIdentifier'], $columns)),
      implode(', ', $placeholders)
    );

    $this->connection->affectingStatement($sql, array_values($data));
    return $this->connection->lastInsertId();
  }

  /**
   * Insert multiple records.
   * 
   * @param array $rows Array of associative arrays
   * @return int Number of rows inserted
   */
  public function insertMany(array $rows): int
  {
    if (empty($rows)) {
      throw new DatabaseException("Cannot insert empty data");
    }

    $columns = array_keys($rows[0]);
    $placeholderSet = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
    $allPlaceholders = implode(', ', array_fill(0, count($rows), $placeholderSet));

    $sql = sprintf(
      "INSERT INTO %s (%s) VALUES %s",
      $this->quoteIdentifier($this->table),
      implode(', ', array_map([$this, 'quoteIdentifier'], $columns)),
      $allPlaceholders
    );

    $bindings = [];
    foreach ($rows as $row) {
      $bindings = array_merge($bindings, array_values($row));
    }

    return $this->connection->affectingStatement($sql, $bindings);
  }

  /**
   * Update records.
   * 
   * @param array $data Associative array of column => value
   * @return int Number of affected rows
   */
  public function update(array $data): int
  {
    if (empty($data)) {
      throw new DatabaseException("Cannot update with empty data");
    }

    $sets = [];
    $bindings = [];

    foreach ($data as $column => $value) {
      $sets[] = $this->quoteIdentifier($column) . ' = ?';
      $bindings[] = $value;
    }

    $sql = sprintf(
      "UPDATE %s SET %s%s",
      $this->quoteIdentifier($this->table),
      implode(', ', $sets),
      $this->compileWheres($bindings)
    );

    return $this->connection->affectingStatement($sql, $bindings);
  }

  /**
   * Increment a column's value.
   * 
   * @param string $column Column name
   * @param int $amount Amount to increment
   * @return int Number of affected rows
   */
  public function increment(string $column, int $amount = 1): int
  {
    $quotedColumn = $this->quoteIdentifier($column);
    $bindings = [];

    $sql = sprintf(
      "UPDATE %s SET %s = %s + ?%s",
      $this->quoteIdentifier($this->table),
      $quotedColumn,
      $quotedColumn,
      $this->compileWheres($bindings)
    );

    array_unshift($bindings, $amount);
    return $this->connection->affectingStatement($sql, $bindings);
  }

  /**
   * Decrement a column's value.
   * 
   * @param string $column Column name
   * @param int $amount Amount to decrement
   * @return int Number of affected rows
   */
  public function decrement(string $column, int $amount = 1): int
  {
    $quotedColumn = $this->quoteIdentifier($column);
    $bindings = [];

    $sql = sprintf(
      "UPDATE %s SET %s = %s - ?%s",
      $this->quoteIdentifier($this->table),
      $quotedColumn,
      $quotedColumn,
      $this->compileWheres($bindings)
    );

    array_unshift($bindings, $amount);
    return $this->connection->affectingStatement($sql, $bindings);
  }

  /**
   * Delete records.
   * 
   * @return int Number of affected rows
   */
  public function delete(): int
  {
    $bindings = [];

    $sql = sprintf(
      "DELETE FROM %s%s",
      $this->quoteIdentifier($this->table),
      $this->compileWheres($bindings)
    );

    return $this->connection->affectingStatement($sql, $bindings);
  }

  /**
   * Truncate the table.
   * 
   * WARNING: This will delete ALL rows and reset auto-increment.
   * 
   * @return void
   */
  public function truncate(): void
  {
    $sql = sprintf("TRUNCATE TABLE %s", $this->quoteIdentifier($this->table));
    $this->connection->unprepared($sql);
  }

  /**
   * Compile the query to SQL.
   * 
   * @return string The SQL query
   */
  public function toSql(): string
  {
    $this->bindings = [];

    $sql = 'SELECT ';

    if ($this->distinct) {
      $sql .= 'DISTINCT ';
    }

    $sql .= implode(', ', $this->columns);
    $sql .= ' FROM ' . $this->quoteIdentifier($this->table);
    $sql .= $this->compileJoins();
    $sql .= $this->compileWheres($this->bindings);
    $sql .= $this->compileGroups();
    $sql .= $this->compileHavings($this->bindings);
    $sql .= $this->compileOrders();
    $sql .= $this->compileLimit();
    $sql .= $this->compileOffset();

    return $sql;
  }

  /**
   * Compile JOIN clauses.
   * 
   * @return string
   */
  protected function compileJoins(): string
  {
    if (empty($this->joins)) {
      return '';
    }

    $sql = '';
    foreach ($this->joins as $join) {
      $sql .= sprintf(
        " %s JOIN %s ON %s %s %s",
        $join['type'],
        $this->quoteIdentifier($join['table']),
        $this->quoteIdentifier($join['first']),
        $join['operator'],
        $this->quoteIdentifier($join['second'])
      );
    }

    return $sql;
  }

  /**
   * Compile WHERE clauses.
   * 
   * @param array $bindings Reference to bindings array
   * @return string
   */
  protected function compileWheres(array &$bindings): string
  {
    if (empty($this->wheres)) {
      return '';
    }

    $sql = ' WHERE ';
    $parts = [];

    foreach ($this->wheres as $i => $where) {
      $boolean = $i === 0 ? '' : " {$where['boolean']} ";

      switch ($where['type']) {
        case 'basic':
          $parts[] = $boolean . $this->compileBasicWhere($where, $bindings);
          break;

        case 'in':
          $parts[] = $boolean . $this->compileInWhere($where, $bindings);
          break;

        case 'null':
          $parts[] = $boolean . $this->compileNullWhere($where);
          break;

        case 'between':
          $parts[] = $boolean . $this->compileBetweenWhere($where, $bindings);
          break;

        case 'raw':
          $parts[] = $boolean . $where['sql'];
          $bindings = array_merge($bindings, $where['bindings']);
          break;
      }
    }

    return $sql . implode('', $parts);
  }

  /**
   * Compile a basic WHERE clause.
   * 
   * @param array $where Where clause data
   * @param array $bindings Reference to bindings array
   * @return string
   */
  protected function compileBasicWhere(array $where, array &$bindings): string
  {
    $column = $this->quoteIdentifier($where['column']);

    if (in_array($where['operator'], ['IN', 'NOT IN'])) {
      return $this->compileInWhere([
        'column' => $where['column'],
        'values' => (array) $where['value'],
        'not' => $where['operator'] === 'NOT IN'
      ], $bindings);
    }

    $bindings[] = $where['value'];
    return "{$column} {$where['operator']} ?";
  }

  /**
   * Compile a WHERE IN clause.
   * 
   * @param array $where Where clause data
   * @param array $bindings Reference to bindings array
   * @return string
   */
  protected function compileInWhere(array $where, array &$bindings): string
  {
    $column = $this->quoteIdentifier($where['column']);
    $not = $where['not'] ? 'NOT ' : '';

    if (empty($where['values'])) {
      return $where['not'] ? '1 = 1' : '1 = 0';
    }

    $placeholders = implode(', ', array_fill(0, count($where['values']), '?'));
    $bindings = array_merge($bindings, $where['values']);

    return "{$column} {$not}IN ({$placeholders})";
  }

  /**
   * Compile a WHERE NULL clause.
   * 
   * @param array $where Where clause data
   * @return string
   */
  protected function compileNullWhere(array $where): string
  {
    $column = $this->quoteIdentifier($where['column']);
    $not = $where['not'] ? 'NOT ' : '';

    return "{$column} IS {$not}NULL";
  }

  /**
   * Compile a WHERE BETWEEN clause.
   * 
   * @param array $where Where clause data
   * @param array $bindings Reference to bindings array
   * @return string
   */
  protected function compileBetweenWhere(array $where, array &$bindings): string
  {
    $column = $this->quoteIdentifier($where['column']);
    $not = $where['not'] ? 'NOT ' : '';

    $bindings[] = $where['min'];
    $bindings[] = $where['max'];

    return "{$column} {$not}BETWEEN ? AND ?";
  }

  /**
   * Compile GROUP BY clauses.
   * 
   * @return string
   */
  protected function compileGroups(): string
  {
    if (empty($this->groups)) {
      return '';
    }

    $columns = array_map([$this, 'quoteIdentifier'], $this->groups);
    return ' GROUP BY ' . implode(', ', $columns);
  }

  /**
   * Compile HAVING clauses.
   * 
   * @param array $bindings Reference to bindings array
   * @return string
   */
  protected function compileHavings(array &$bindings): string
  {
    if (empty($this->havings)) {
      return '';
    }

    $sql = ' HAVING ';
    $parts = [];

    foreach ($this->havings as $having) {
      $column = $this->quoteIdentifier($having['column']);
      $parts[] = "{$column} {$having['operator']} ?";
      $bindings[] = $having['value'];
    }

    return $sql . implode(' AND ', $parts);
  }

  /**
   * Compile ORDER BY clauses.
   * 
   * @return string
   */
  protected function compileOrders(): string
  {
    if (empty($this->orders)) {
      return '';
    }

    $parts = [];
    foreach ($this->orders as $order) {
      $column = $this->quoteIdentifier($order['column']);
      $parts[] = "{$column} {$order['direction']}";
    }

    return ' ORDER BY ' . implode(', ', $parts);
  }

  /**
   * Compile LIMIT clause.
   * 
   * @return string
   */
  protected function compileLimit(): string
  {
    return $this->limit !== null ? " LIMIT {$this->limit}" : '';
  }

  /**
   * Compile OFFSET clause.
   * 
   * @return string
   */
  protected function compileOffset(): string
  {
    return $this->offset !== null ? " OFFSET {$this->offset}" : '';
  }

  /**
   * Quote an identifier (table or column name).
   * 
   * @param string $identifier Identifier to quote
   * @return string Quoted identifier
   */
  protected function quoteIdentifier(string $identifier): string
  {
    // Handle aliases (e.g., "table.column" or "column AS alias")
    if (strpos($identifier, '.') !== false) {
      $parts = explode('.', $identifier);
      return implode('.', array_map([$this, 'quoteSingle'], $parts));
    }

    if (stripos($identifier, ' AS ') !== false) {
      $parts = preg_split('/\s+AS\s+/i', $identifier);
      return $this->quoteSingle($parts[0]) . ' AS ' . $this->quoteSingle($parts[1]);
    }

    return $this->quoteSingle($identifier);
  }

  /**
   * Quote a single identifier part.
   * 
   * @param string $identifier Identifier to quote
   * @return string Quoted identifier
   */
  protected function quoteSingle(string $identifier): string
  {
    $identifier = trim($identifier);

    // Don't quote *, functions, or already quoted identifiers
    if (
      $identifier === '*' ||
      strpos($identifier, '(') !== false ||
      strpos($identifier, '`') !== false
    ) {
      return $identifier;
    }

    return '`' . str_replace('`', '``', $identifier) . '`';
  }

  /**
   * Get the current bindings.
   * 
   * @return array
   */
  public function getBindings(): array
  {
    return $this->bindings;
  }

  /**
   * Debug: dump the SQL and bindings.
   * 
   * @return void
   */
  public function dd(): void
  {
    $sql = $this->toSql();
    echo "SQL: {$sql}\n";
    echo "Bindings: " . json_encode($this->bindings, JSON_PRETTY_PRINT) . "\n";
    die();
  }

  /**
   * Clone the query builder.
   * 
   * @return self
   */
  public function clone(): self
  {
    return clone $this;
  }
}
