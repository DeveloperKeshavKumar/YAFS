<?php

require_once __DIR__ . '/../autoload.php';

use YAFS\Testing\TestRunner;
use YAFS\Testing\Assert;
use YAFS\Database\Connection;
use YAFS\Database\ConnectionManager;
use YAFS\Database\DB;

$runner = new TestRunner();

// =========================
// CONNECTION TESTS
// =========================

$runner->test('Connection: Create PDO connection', function() {
    $config = [
        'host' => 'localhost',
        'database' => 'testdb',
        'username' => 'root',
        'password' => ''
    ];
    
    try {
        $conn = new Connection($config);
        Assert::assertInstanceOf(Connection::class, $conn);
        
        // Test that we can get PDO instance
        $pdo = $conn->getPdo();
        Assert::assertInstanceOf(\PDO::class, $pdo);
    } catch (\Exception $e) {
        if (strpos($e->getMessage(), 'Connection failed') !== false) {
            echo "  (Skipped - MySQL not available)\n";
            return;
        }
        throw $e;
    }
});

$runner->test('Connection: Unsupported driver throws exception', function() {
    $exceptionThrown = false;
    
    try {
        $conn = new Connection([
            'driver' => 'unsupported_driver_xyz',
            'host' => 'localhost',
            'database' => 'testdb',
            'username' => 'root',
            'password' => ''
        ]);
        // Force connection
        $conn->getPdo();
    } catch (\Exception $e) {
        $exceptionThrown = true;
        Assert::assertStringContains('Unsupported driver', $e->getMessage());
    }
    
    Assert::assertTrue($exceptionThrown, 'Expected exception for unsupported driver');
});

// =========================
// CONNECTION MANAGER TESTS
// =========================

$runner->test('ConnectionManager: Add and retrieve connection config', function() {
    ConnectionManager::addConnection([
        'host' => 'localhost',
        'database' => 'testdb',
        'username' => 'root',
        'password' => ''
    ], 'test_conn');
    
    $names = ConnectionManager::getConnectionNames();
    Assert::assertTrue(in_array('test_conn', $names));
});

$runner->test('ConnectionManager: Set default connection', function() {
    ConnectionManager::addConnection([
        'host' => 'localhost',
        'database' => 'testdb',
        'username' => 'root',
        'password' => ''
    ], 'main');
    
    ConnectionManager::setDefaultConnection('main');
    Assert::assertEquals('main', ConnectionManager::getDefaultConnection());
});

$runner->test('ConnectionManager: Get connection instance', function() {
    ConnectionManager::addConnection([
        'host' => 'localhost',
        'database' => 'testdb',
        'username' => 'root',
        'password' => ''
    ], 'test_instance');
    
    try {
        $conn = ConnectionManager::connection('test_instance');
        Assert::assertInstanceOf(Connection::class, $conn);
    } catch (\Exception $e) {
        if (strpos($e->getMessage(), 'Connection failed') !== false) {
            echo "  (Skipped - MySQL not available)\n";
            return;
        }
        throw $e;
    }
});

$runner->test('ConnectionManager: Reuse existing connection', function() {
    ConnectionManager::addConnection([
        'host' => 'localhost',
        'database' => 'testdb',
        'username' => 'root',
        'password' => ''
    ], 'reuse_test');
    
    try {
        $conn1 = ConnectionManager::connection('reuse_test');
        $conn2 = ConnectionManager::connection('reuse_test');
        
        // Should be the same instance
        Assert::assertSame($conn1, $conn2);
    } catch (\Exception $e) {
        if (strpos($e->getMessage(), 'Connection failed') !== false) {
            echo "  (Skipped - MySQL not available)\n";
            return;
        }
        throw $e;
    }
});

$runner->test('ConnectionManager: Check connection exists', function() {
    ConnectionManager::addConnection([
        'host' => 'localhost',
        'database' => 'testdb',
        'username' => 'root',
        'password' => ''
    ], 'exists_test');
    
    try {
        ConnectionManager::connection('exists_test'); // Initialize connection
        Assert::assertTrue(ConnectionManager::hasConnection('exists_test'));
        Assert::assertFalse(ConnectionManager::hasConnection('nonexistent'));
    } catch (\Exception $e) {
        if (strpos($e->getMessage(), 'Connection failed') !== false) {
            echo "  (Skipped - MySQL not available)\n";
            return;
        }
        throw $e;
    }
});

$runner->test('ConnectionManager: Disconnect specific connection', function() {
    ConnectionManager::addConnection([
        'host' => 'localhost',
        'database' => 'testdb',
        'username' => 'root',
        'password' => ''
    ], 'disconnect_test');
    
    try {
        ConnectionManager::connection('disconnect_test');
        Assert::assertTrue(ConnectionManager::hasConnection('disconnect_test'));
        
        ConnectionManager::disconnect('disconnect_test');
        Assert::assertFalse(ConnectionManager::hasConnection('disconnect_test'));
    } catch (\Exception $e) {
        if (strpos($e->getMessage(), 'Connection failed') !== false) {
            echo "  (Skipped - MySQL not available)\n";
            return;
        }
        throw $e;
    }
});

// =========================
// DB FACADE TESTS
// =========================

$runner->test('DB: Add connection via facade', function() {
    DB::addConnection([
        'host' => 'localhost',
        'database' => 'testdb',
        'username' => 'root',
        'password' => ''
    ], 'facade_test');
    
    $names = ConnectionManager::getConnectionNames();
    Assert::assertTrue(in_array('facade_test', $names));
});

$runner->test('DB: Get query builder via facade', function() {
    DB::addConnection([
        'host' => 'localhost',
        'database' => 'testdb',
        'username' => 'root',
        'password' => ''
    ], 'builder_test');
    
    try {
        $builder = DB::table('users', 'builder_test');
        Assert::assertInstanceOf(\YAFS\Database\QueryBuilder::class, $builder);
    } catch (\Exception $e) {
        if (strpos($e->getMessage(), 'Connection failed') !== false) {
            echo "  (Skipped - MySQL not available)\n";
            return;
        }
        throw $e;
    }
});

$runner->test('DB: Get connection instance via facade', function() {
    DB::addConnection([
        'host' => 'localhost',
        'database' => 'testdb',
        'username' => 'root',
        'password' => ''
    ], 'conn_test');
    
    try {
        $conn = DB::connection('conn_test');
        Assert::assertInstanceOf(Connection::class, $conn);
    } catch (\Exception $e) {
        if (strpos($e->getMessage(), 'Connection failed') !== false) {
            echo "  (Skipped - MySQL not available)\n";
            return;
        }
        throw $e;
    }
});

$runner->test('DB: Disconnect via facade', function() {
    DB::addConnection([
        'host' => 'localhost',
        'database' => 'testdb',
        'username' => 'root',
        'password' => ''
    ], 'disconnect_facade_test');
    
    try {
        DB::connection('disconnect_facade_test');
        Assert::assertTrue(ConnectionManager::hasConnection('disconnect_facade_test'));
        
        DB::disconnect('disconnect_facade_test');
        Assert::assertFalse(ConnectionManager::hasConnection('disconnect_facade_test'));
    } catch (\Exception $e) {
        if (strpos($e->getMessage(), 'Connection failed') !== false) {
            echo "  (Skipped - MySQL not available)\n";
            return;
        }
        throw $e;
    }
});

$runner->test('DB: Throw exception for undefined connection', function() {
    $exceptionThrown = false;
    
    try {
        ConnectionManager::connection('undefined_connection_xyz');
    } catch (\Exception $e) {
        $exceptionThrown = true;
        Assert::assertStringContains('not found', $e->getMessage());
    }
    
    Assert::assertTrue($exceptionThrown, 'Expected exception for undefined connection');
});

$runner->test('DB: Multiple named connections', function() {
    DB::addConnection([
        'host' => 'localhost',
        'database' => 'db1',
        'username' => 'root',
        'password' => ''
    ], 'conn1');
    
    DB::addConnection([
        'host' => 'localhost',
        'database' => 'db2',
        'username' => 'root',
        'password' => ''
    ], 'conn2');
    
    $names = ConnectionManager::getConnectionNames();
    Assert::assertTrue(in_array('conn1', $names));
    Assert::assertTrue(in_array('conn2', $names));
});

$runner->run();