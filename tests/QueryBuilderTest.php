<?php

require_once __DIR__ . '/../autoload.php';

use YAFS\Testing\TestRunner;
use YAFS\Testing\Assert;
use YAFS\Database\QueryBuilder;
use YAFS\Database\Connection;

$runner = new TestRunner();

// Mock connection for testing SQL generation
class MockConnection extends Connection
{
	public function __construct() {}
	public function select(string $sql, array $bindings = []): array
	{
		return [];
	}
	public function selectOne(string $sql, array $bindings = []): ?array
	{
		return null;
	}
	public function affectingStatement(string $sql, array $bindings = []): int
	{
		return 0;
	}
}

$runner->test('SELECT basic columns', function () {
	$conn = new MockConnection();
	$builder = new QueryBuilder($conn, 'users');
	$builder->select('id', 'name', 'email');

	Assert::assertStringContains('SELECT id, name, email', $builder->toSql());
});

$runner->test('SELECT with array', function () {
	$conn = new MockConnection();
	$builder = new QueryBuilder($conn, 'users');
	$builder->select('id', 'name');

	Assert::assertStringContains('SELECT id, name', $builder->toSql());
});

$runner->test('WHERE with equality', function () {
	$conn = new MockConnection();
	$builder = new QueryBuilder($conn, 'users');
	$builder->where('status', 'active');

	Assert::assertStringContains('WHERE `status` = ?', $builder->toSql());
	Assert::assertEquals(['active'], $builder->getBindings());
});

$runner->test('WHERE with operator', function () {
	$conn = new MockConnection();
	$builder = new QueryBuilder($conn, 'users');
	$builder->where('age', '>', 18);

	Assert::assertStringContains('WHERE `age` > ?', $builder->toSql());
	Assert::assertEquals([18], $builder->getBindings());
});

$runner->test('Multiple WHERE clauses', function () {
	$conn = new MockConnection();
	$builder = new QueryBuilder($conn, 'users');
	$builder->where('status', 'active')->where('age', '>', 18);

	$sql = $builder->toSql();
	Assert::assertStringContains('`status` = ?', $sql);
	Assert::assertStringContains('`age` > ?', $sql);
	Assert::assertEquals(['active', 18], $builder->getBindings());
});

$runner->test('OR WHERE clause', function () {
	$conn = new MockConnection();
	$builder = new QueryBuilder($conn, 'users');
	$builder->where('status', 'active')->orWhere('status', 'pending');

	Assert::assertStringContains('OR `status` = ?', $builder->toSql());
});

$runner->test('WHERE IN clause', function () {
	$conn = new MockConnection();
	$builder = new QueryBuilder($conn, 'users');
	$builder->whereIn('id', [1, 2, 3]);

	Assert::assertStringContains('`id` IN (?, ?, ?)', $builder->toSql());
	Assert::assertEquals([1, 2, 3], $builder->getBindings());
});

$runner->test('WHERE NULL clause', function () {
	$conn = new MockConnection();
	$builder = new QueryBuilder($conn, 'users');
	$builder->whereNull('deleted_at');

	Assert::assertStringContains('`deleted_at` IS NULL', $builder->toSql());
});

$runner->test('WHERE NOT NULL clause', function () {
	$conn = new MockConnection();
	$builder = new QueryBuilder($conn, 'users');
	$builder->whereNotNull('email_verified_at');

	Assert::assertStringContains('`email_verified_at` IS NOT NULL', $builder->toSql());
});

$runner->test('ORDER BY clause', function () {
	$conn = new MockConnection();
	$builder = new QueryBuilder($conn, 'users');
	$builder->orderBy('created_at', 'desc');

	Assert::assertStringContains('ORDER BY `created_at` DESC', $builder->toSql());
});

$runner->test('Multiple ORDER BY', function () {
	$conn = new MockConnection();
	$builder = new QueryBuilder($conn, 'users');
	$builder->orderBy('status')->orderBy('created_at', 'desc');

	$sql = $builder->toSql();
	Assert::assertStringContains('ORDER BY `status` ASC, `created_at` DESC', $sql);
});

$runner->test('LIMIT clause', function () {
	$conn = new MockConnection();
	$builder = new QueryBuilder($conn, 'users');
	$builder->limit(10);

	Assert::assertStringContains('LIMIT 10', $builder->toSql());
});

$runner->test('OFFSET clause', function () {
	$conn = new MockConnection();
	$builder = new QueryBuilder($conn, 'users');
	$builder->limit(10)->offset(20);

	$sql = $builder->toSql();
	Assert::assertStringContains('LIMIT 10', $sql);
	Assert::assertStringContains('OFFSET 20', $sql);
});

$runner->test('INNER JOIN', function () {
	$conn = new MockConnection();
	$builder = new QueryBuilder($conn, 'users');
	$builder->join('posts', 'users.id', '=', 'posts.user_id');

	Assert::assertStringContains('INNER JOIN `posts` ON `users`.`id` = `posts`.`user_id`', $builder->toSql());
});

$runner->test('LEFT JOIN', function () {
	$conn = new MockConnection();
	$builder = new QueryBuilder($conn, 'users');
	$builder->leftJoin('posts', 'users.id', '=', 'posts.user_id');

	Assert::assertStringContains('LEFT JOIN `posts` ON `users`.`id` = `posts`.`user_id`', $builder->toSql());
});

$runner->test('GROUP BY clause', function () {
	$conn = new MockConnection();
	$builder = new QueryBuilder($conn, 'orders');
	$builder->select('user_id', 'COUNT(*) as total')->groupBy('user_id');

	Assert::assertStringContains('GROUP BY `user_id`', $builder->toSql());
});

$runner->test('HAVING clause', function () {
	$conn = new MockConnection();
	$builder = new QueryBuilder($conn, 'orders');
	$builder->select('user_id', 'COUNT(*) as total')
		->groupBy('user_id')
		->having('total', '>', 5);

	$sql = $builder->toSql();
	Assert::assertStringContains('GROUP BY `user_id`', $sql);
	Assert::assertStringContains('HAVING `total` > ?', $sql);
});

$runner->test('Complex query chain', function () {
	$conn = new MockConnection();
	$builder = new QueryBuilder($conn, 'users');
	$builder->select('id', 'name')
		->where('status', 'active')
		->where('age', '>', 18)
		->orderBy('created_at', 'desc')
		->limit(10)
		->offset(5);

	$sql = $builder->toSql();
	Assert::assertStringContains('SELECT id, name', $sql);
	Assert::assertStringContains('WHERE `status` = ?', $sql);
	Assert::assertStringContains('`age` > ?', $sql);
	Assert::assertStringContains('ORDER BY `created_at` DESC', $sql);
	Assert::assertStringContains('LIMIT 10', $sql);
	Assert::assertStringContains('OFFSET 5', $sql);
});

$runner->run();
