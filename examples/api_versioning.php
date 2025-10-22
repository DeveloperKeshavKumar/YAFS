<?php

require_once __DIR__ . '/../src/Router/Route.php';
require_once __DIR__ . '/../src/Router/Router.php';
require_once __DIR__ . '/../src/Http/Request.php';
require_once __DIR__ . '/../src/Http/Response.php';
require_once __DIR__ . '/../src/Application.php';

use YAFS\Application;

$app = new Application();

// API v1 - Static routes for stability
$app->group(['prefix' => '/api/v1'], function($app) {
    
    $app->get('/users', function($req, $res) {
        return $res->json([
            'version' => 'v1',
            'users' => [
                ['id' => 1, 'name' => 'Alice'],
                ['id' => 2, 'name' => 'Bob']
            ]
        ]);
    });
    
    $app->get('/users/:id', function($req, $res) {
        return $res->json([
            'version' => 'v1',
            'user' => [
                'id' => $req->param('id'),
                'name' => 'User ' . $req->param('id')
            ]
        ]);
    });
    
    $app->post('/users', function($req, $res) {
        return $res->status(201)->json([
            'version' => 'v1',
            'message' => 'User created'
        ]);
    });
});

// API v2 - Updated response format
$app->group(['prefix' => '/api/v2'], function($app) {
    
    $app->get('/users', function($req, $res) {
        return $res->json([
            'meta' => ['version' => 'v2'],
            'data' => [
                ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com'],
                ['id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com']
            ]
        ]);
    });
    
    $app->get('/users/:id', function($req, $res) {
        return $res->json([
            'meta' => ['version' => 'v2'],
            'data' => [
                'id' => $req->param('id'),
                'name' => 'User ' . $req->param('id'),
                'email' => 'user' . $req->param('id') . '@example.com'
            ]
        ]);
    });
});

// Nested groups - for organizing by resource type
$app->group(['prefix' => '/api/v1'], function($app) {
    
    $app->group(['prefix' => '/products'], function($app) {
        
        $app->get('/:category', function($req, $res) {
            return $res->json([
                'category' => $req->param('category'),
                'products' => []
            ]);
        });
        
        $app->get('/:category/:id', function($req, $res) {
            return $res->json([
                'category' => $req->param('category'),
                'productId' => $req->param('id'),
                'product' => []
            ]);
        });
    });
});

// Test the versioned API
echo "Testing API Versioning with Route Groups:\n\n";

$tests = [
    ['GET', '/api/v1/users'],
    ['GET', '/api/v1/users/123'],
    ['POST', '/api/v1/users'],
    ['GET', '/api/v2/users'],
    ['GET', '/api/v2/users/456'],
    ['GET', '/api/v1/products/electronics'],
    ['GET', '/api/v1/products/electronics/laptop-123'],
];

foreach ($tests as [$method, $path]) {
    $request = new \YAFS\Http\Request($method, $path);
    $result = $app->handle($request);
    
    echo "$method $path\n";
    echo "Response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
}