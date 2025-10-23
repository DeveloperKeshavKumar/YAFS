# YAFS Query Builder Documentation

The YAFS Query Builder provides a fluent, security-first interface for building SQL queries. All queries use prepared statements to prevent SQL injection.

## Table of Contents

- [Quick Start](#quick-start)
- [Configuration](#configuration)
- [SELECT Queries](#select-queries)
- [WHERE Clauses](#where-clauses)
- [Joins](#joins)
- [Ordering & Limiting](#ordering--limiting)
- [Aggregates](#aggregates)
- [INSERT Operations](#insert-operations)
- [UPDATE Operations](#update-operations)
- [DELETE Operations](#delete-operations)
- [Transactions](#transactions)
- [Raw Queries](#raw-queries)
- [Best Practices](#best-practices)

## Quick Start

```php
<?php

use YAFS\Database\DB;

// Configure database
DB::addConnection([
    'host' => 'localhost',
    'database' => 'myapp',
    'username' => 'root',
    'password' => 'secret'
]);

// Simple query
$users = DB::table('users')
    ->where('status', 'active')
    ->get();

// Single record
$user = DB::table('users')
    ->where('id', 1)
    ->first();

// Insert
DB::table('users')->insert([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Update
DB::table('users')
    ->where('id', 1)
    ->update(['status' => 'inactive']);

// Delete
DB::table('users')
    ->where('status', 'banned')
    ->delete();
```

## Configuration

### Single Connection

```php
DB::addConnection([
    'host' => 'localhost',
    'database' => 'myapp',
    'username' => 'root',
    'password' => 'secret',
    'charset' => 'utf8mb4',  // Optional, default: utf8mb4
    'port' => 3306           // Optional, default: 3306
]);
```

### Multiple Connections

```php
// Primary database
DB::addConnection([
    'host' => 'localhost',
    'database' => 'main_db',
    'username' => 'root',
    'password' => 'secret'
], 'primary');

// Analytics database
DB::addConnection([
    'host' => 'analytics.example.com',
    'database' => 'analytics',
    'username' => 'analyst',
    'password' => 'secret'
], 'analytics');

// Set default connection
ConnectionManager::setDefaultConnection('primary');

// Use specific connection
$stats = DB::table('events', 'analytics')->count();
```

## SELECT Queries

### Basic Selection

```php
// Select all columns
$users = DB::table('users')->get();

// Select specific columns
$users = DB::table('users')
    ->select('id', 'name', 'email')
    ->get();

// Select with array
$users = DB::table('users')
    ->select(['id', 'name', 'email'])
    ->get();
```

### Getting Single Records

```php
// First record
$user = DB::table('users')
    ->where('email', 'john@example.com')
    ->first();

// Returns null if not found
if ($user === null) {
    // Handle not found
}
```

### Getting Single Values

```php
// Get single column value
$name = DB::table('users')
    ->where('id', 1)
    ->value('name');

// Returns null if not found
```

### Checking Existence

```php
// Check if records exist
$exists = DB::table('users')
    ->where('email', 'john@example.com')
    ->exists();

if ($exists) {
    // User exists
}
```

## WHERE Clauses

### Basic WHERE

```php
// Equality
$users = DB::table('users')
    ->where('status', 'active')
    ->get();

// With operator
$users = DB::table('users')
    ->where('age', '>', 18)
    ->get();

// Supported operators: =, >, <, >=, <=, !=, <>, LIKE
$users = DB::table('users')
    ->where('name', 'LIKE', 'John%')
    ->get();
```

### Multiple WHERE (AND)

```php
// All conditions must be true (AND)
$users = DB::table('users')
    ->where('status', 'active')
    ->where('age', '>', 18)
    ->where('country', 'US')
    ->get();

// Generated SQL:
// SELECT * FROM `users` 
// WHERE `status` = ? AND `age` > ? AND `country` = ?
```

### OR WHERE

```php
// At least one condition must be true
$users = DB::table('users')
    ->where('status', 'active')
    ->orWhere('status', 'pending')
    ->get();

// Generated SQL:
// SELECT * FROM `users` 
// WHERE `status` = ? OR `status` = ?
```

### WHERE IN

```php
// Match any value in array
$users = DB::table('users')
    ->whereIn('id', [1, 2, 3, 4, 5])
    ->get();

// Generated SQL:
// SELECT * FROM `users` WHERE `id` IN (?, ?, ?, ?, ?)
```

### WHERE NULL

```php
// Check for NULL values
$users = DB::table('users')
    ->whereNull('deleted_at')
    ->get();

// Check for NOT NULL
$users = DB::table('users')
    ->whereNotNull('email_verified_at')
    ->get();
```

### Complex WHERE Combinations

```php
// Mixing AND and OR
$users = DB::table('users')
    ->where('country', 'US')
    ->where('age', '>', 18)
    ->orWhere('status', 'premium')
    ->get();

// Generated SQL:
// SELECT * FROM `users` 
// WHERE `country` = ? AND `age` > ? OR `status` = ?
```

## Joins

### Inner Join

```php
// Basic join
$posts = DB::table('posts')
    ->join('users', 'posts.user_id', '=', 'users.id')
    ->select('posts.*', 'users.name as author')
    ->get();

// Generated SQL:
// SELECT posts.*, users.name as author 
// FROM `posts` 
// INNER JOIN `users` ON `posts`.`user_id` = `users`.`id`
```

### Left Join

```php
// Include posts even without users
$posts = DB::table('posts')
    ->leftJoin('users', 'posts.user_id', '=', 'users.id')
    ->select('posts.*', 'users.name as author')
    ->get();
```

### Multiple Joins

```php
// Join multiple tables
$posts = DB::table('posts')
    ->join('users', 'posts.user_id', '=', 'users.id')
    ->leftJoin('categories', 'posts.category_id', '=', 'categories.id')
    ->select('posts.*', 'users.name as author', 'categories.name as category')
    ->get();
```

## Ordering & Limiting

### ORDER BY

```php
// Single column
$users = DB::table('users')
    ->orderBy('created_at', 'desc')
    ->get();

// Multiple columns
$users = DB::table('users')
    ->orderBy('status', 'asc')
    ->orderBy('created_at', 'desc')
    ->get();

// Default direction is ASC
$users = DB::table('users')
    ->orderBy('name')
    ->get();
```

### LIMIT

```php
// Limit results
$users = DB::table('users')
    ->limit(10)
    ->get();
```

### OFFSET (Pagination)

```php
// Skip first 20, get next 10
$users = DB::table('users')
    ->limit(10)
    ->offset(20)
    ->get();

// Page-based pagination
$page = 3;
$perPage = 10;
$users = DB::table('users')
    ->limit($perPage)
    ->offset(($page - 1) * $perPage)
    ->get();
```

## Aggregates

### COUNT

```php
// Count all records
$count = DB::table('users')->count();

// Count with conditions
$activeCount = DB::table('users')
    ->where('status', 'active')
    ->count();

// Count specific column
$verified = DB::table('users')
    ->whereNotNull('email_verified_at')
    ->count('id');
```

### MAX, MIN, SUM, AVG

```php
// Maximum value
$maxAge = DB::table('users')->max('age');

// Minimum value
$minAge = DB::table('users')->min('age');

// Sum
$totalSales = DB::table('orders')->sum('amount');

// Average
$avgRating = DB::table('products')->avg('rating');
```

### GROUP BY and HAVING

```php
// Group results
$stats = DB::table('orders')
    ->select('user_id', 'COUNT(*) as order_count', 'SUM(amount) as total_spent')
    ->groupBy('user_id')
    ->get();

// With HAVING clause
$highSpenders = DB::table('orders')
    ->select('user_id', 'SUM(amount) as total_spent')
    ->groupBy('user_id')
    ->having('total_spent', '>', 1000)
    ->get();
```

## INSERT Operations

### Single Insert

```php
// Insert single record
DB::table('users')->insert([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => password_hash('secret', PASSWORD_DEFAULT),
    'status' => 'active'
]);

// Returns: true on success
```

### Insert and Get ID

```php
// Insert and get auto-increment ID
$userId = DB::table('users')->insertGetId([
    'name' => 'Jane Doe',
    'email' => 'jane@example.com'
]);

echo "Created user with ID: {$userId}";
```

### Bulk Insert

```php
// Insert multiple records at once
DB::table('users')->insert([
    ['name' => 'User 1', 'email' => 'user1@example.com'],
    ['name' => 'User 2', 'email' => 'user2@example.com'],
    ['name' => 'User 3', 'email' => 'user3@example.com']
]);

// More efficient than multiple single inserts
```

## UPDATE Operations

### Basic Update

```php
// Update records
$affected = DB::table('users')
    ->where('id', 1)
    ->update([
        'name' => 'Updated Name',
        'status' => 'inactive'
    ]);

echo "Updated {$affected} rows";
```

### Update with Multiple Conditions

```php
// Update multiple matching records
$affected = DB::table('users')
    ->where('status', 'pending')
    ->where('created_at', '<', '2024-01-01')
    ->update(['status' => 'expired']);
```

### Increment/Decrement

```php
// Increment a value
DB::table('posts')
    ->where('id', 1)
    ->increment('view_count');

// Increment by specific amount
DB::table('posts')
    ->where('id', 1)
    ->increment('view_count', 5);

// Decrement
DB::table('products')
    ->where('id', 10)
    ->decrement('stock');

// Decrement by specific amount
DB::table('products')
    ->where('id', 10)
    ->decrement('stock', 3);
```

## DELETE Operations

### Basic Delete

```php
// Delete records (requires WHERE clause)
$deleted = DB::table('users')
    ->where('status', 'banned')
    ->delete();

echo "Deleted {$deleted} users";
```

### Safety: DELETE Requires WHERE

```php
// This will throw RuntimeException
try {
    DB::table('users')->delete();
} catch (\RuntimeException $e) {
    // Error: Cannot delete without WHERE clause
}

// Use truncate() to delete all records
DB::table('logs')->truncate();
```

### Truncate Table

```php
// Delete all records and reset auto-increment
DB::table('logs')->truncate();

// WARNING: This cannot be undone and resets ID counter
```

## Transactions

### Basic Transaction

```php
try {
    DB::beginTransaction();
    
    // Multiple operations
    $userId = DB::table('users')->insertGetId([
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ]);
    
    DB::table('profiles')->insert([
        'user_id' => $userId,
        'bio' => 'Hello world'
    ]);
    
    DB::table('stats')
        ->where('id', 1)
        ->increment('user_count');
    
    DB::commit();
    
} catch (\Exception $e) {
    DB::rollBack();
    throw $e;
}
```

### Transaction Best Practices

```php
// Always wrap in try-catch
try {
    DB::beginTransaction();
    
    // Critical operations
    $order = createOrder($data);
    processPayment($order);
    sendConfirmation($order);
    
    DB::commit();
    
} catch (\Exception $e) {
    DB::rollBack();
    
    // Log error
    error_log("Transaction failed: " . $e->getMessage());
    
    // Handle failure
    notifyAdmin($e);
    
    throw $e;
}
```

## Raw Queries

### Raw SELECT

```php
// Simple raw query
$users = DB::select('SELECT * FROM users WHERE status = ?', ['active']);

// With multiple parameters
$posts = DB::select(
    'SELECT * FROM posts WHERE user_id = ? AND created_at > ?',
    [42, '2024-01-01']
);
```

### Raw SELECT (Single Result)

```php
$user = DB::selectOne(
    'SELECT * FROM users WHERE id = ?',
    [1]
);
```

### Raw INSERT/UPDATE/DELETE

```php
// Returns number of affected rows
$affected = DB::statement(
    'UPDATE users SET status = ? WHERE created_at < ?',
    ['expired', '2023-01-01']
);

// Insert
$affected = DB::statement(
    'INSERT INTO logs (message, level) VALUES (?, ?)',
    ['Error occurred', 'error']
);
```

### Get Last Insert ID

```php
DB::statement('INSERT INTO users (name) VALUES (?)', ['John']);
$id = DB::lastInsertId();
```

## Best Practices

### 1. Always Use WHERE with DELETE/UPDATE

```php
// ✓ Good - specific condition
DB::table('users')
    ->where('id', 1)
    ->delete();

// ✗ Bad - will throw exception
DB::table('users')->delete();

// ✓ Good - if you really want to delete all
DB::table('users')->truncate();
```

### 2. Use Transactions for Related Operations

```php
// ✓ Good - atomic operations
try {
    DB::beginTransaction();
    $orderId = DB::table('orders')->insertGetId($orderData);
    DB::table('order_items')->insert($itemsData);
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
}

// ✗ Bad - non-atomic
$orderId = DB::table('orders')->insertGetId($orderData);
DB::table('order_items')->insert($itemsData); // If this fails, order is orphaned
```

### 3. Select Only Needed Columns

```php
// ✓ Good - specific columns
$users = DB::table('users')
    ->select('id', 'name', 'email')
    ->get();

// ✗ Bad - unnecessary data transfer
$users = DB::table('users')->get(); // SELECT *
```

### 4. Use Prepared Statements (Automatic)

```php
// ✓ Good - query builder handles this automatically
$user = DB::table('users')
    ->where('email', $email)
    ->first();

// ✗ Bad - never concatenate user input
// $users = DB::select("SELECT * FROM users WHERE email = '{$email}'");
```

### 5. Check for NULL Results

```php
// ✓ Good - handle missing records
$user = DB::table('users')->where('id', $id)->first();

if ($user === null) {
    throw new NotFoundException("User not found");
}

// ✗ Bad - assuming record exists
$name = $user['name']; // Fatal error if $user is null
```

### 6. Use exists() for Existence Checks

```php
// ✓ Good - efficient existence check
if (DB::table('users')->where('email', $email)->exists()) {
    // Email taken
}

// ✗ Bad - fetches unnecessary data
if (count(DB::table('users')->where('email', $email)->get()) > 0) {
    // Email taken
}
```

### 7. Limit Results When Appropriate

```php
// ✓ Good - prevent memory issues
$recentPosts = DB::table('posts')
    ->orderBy('created_at', 'desc')
    ->limit(100)
    ->get();

// ✗ Bad - could load millions of records
$allPosts = DB::table('posts')->get();
```

### 8. Use Indexes for Performance

```php
// When querying frequently, ensure columns are indexed
// In your migrations:
// CREATE INDEX idx_users_email ON users(email);
// CREATE INDEX idx_posts_user_id ON posts(user_id);

// Then queries will be fast:
$user = DB::table('users')->where('email', $email)->first();
$posts = DB::table('posts')->where('user_id', $userId)->get();
```

## Debugging

### View Generated SQL

```php
$builder = DB::table('users')
    ->where('status', 'active')
    ->orderBy('created_at', 'desc');

// Get SQL
echo $builder->toSql();
// Output: SELECT * FROM `users` WHERE `status` = ? ORDER BY `created_at` DESC

// Get bindings
print_r($builder->getBindings());
// Output: ['active']
```

### Common Errors

```php
// RuntimeException: Cannot delete without WHERE clause
// Solution: Add where() or use truncate()

// DatabaseException: Connection failed
// Solution: Check credentials and MySQL is running

// DatabaseException: Table doesn't exist
// Solution: Create table or check table name spelling
```

---

For examples, see [DATABASE_EXAMPLES.md](DATABASE_EXAMPLES.md).