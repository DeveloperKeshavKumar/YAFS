<?php

require_once __DIR__ . '/../src/Http/Request.php';
require_once __DIR__ . '/../src/Http/Response.php';
require_once __DIR__ . '/../src/Router/Route.php';
require_once __DIR__ . '/../src/Router/Router.php';
require_once __DIR__ . '/../src/Application.php';

use YAFS\Application;
use YAFS\Http\Request;

$app = new Application();

// Logging middleware that runs for all routes
$app->use(function($req, $res, $next) {
    echo "[LOG] {$req->getMethod()} {$req->getPath()}\n";
    return $next();
});

// Basic routes
$app->get('/', function($req, $res) {
    return $res->text('Welcome to YAFS!');
});

$app->get('/about', function($req, $res) {
    return $res->html('<h1>About YAFS</h1><p>A simple, straightforward PHP framework</p>');
});

// API v1 with route groups
$app->group(['prefix' => '/api/v1'], function($app) {
    
    // Tenant middleware for multi-tenancy demo
    $tenantMiddleware = function($req, $res, $next) {
        // In real app, would extract from subdomain
        $req->tenant = 'demo-tenant';
        return $next();
    };
    
    $app->group(['middleware' => [$tenantMiddleware]], function($app) {
        
        $app->get('/users', function($req, $res) {
            return $res->json([
                'tenant' => $req->tenant,
                'users' => [
                    ['id' => 1, 'name' => 'Alice'],
                    ['id' => 2, 'name' => 'Bob']
                ]
            ]);
        });
        
        $app->get('/users/:id', function($req, $res) {
            return $res->json([
                'tenant' => $req->tenant,
                'user' => [
                    'id' => $req->param('id'),
                    'name' => 'User ' . $req->param('id')
                ]
            ]);
        });
        
        $app->post('/users', function($req, $res) {
            return $res->status(201)->json([
                'tenant' => $req->tenant,
                'message' => 'User created',
                'data' => $req->json()
            ]);
        });
    });
});

// API v2 with different response format
$app->group(['prefix' => '/api/v2'], function($app) {
    
    $app->get('/users', function($req, $res) {
        return $res->json([
            'meta' => ['version' => 'v2', 'count' => 2],
            'data' => [
                ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com'],
                ['id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com']
            ]
        ]);
    });
});

// Product catalog with nested groups
$app->group(['prefix' => '/products'], function($app) {
    
    $app->get('/:category', function($req, $res) {
        $category = $req->param('category');
        $minPrice = $req->query('min', 0);
        $maxPrice = $req->query('max', 999999);
        
        return $res->json([
            'category' => $category,
            'filters' => [
                'minPrice' => $minPrice,
                'maxPrice' => $maxPrice
            ],
            'products' => []
        ]);
    });
    
    $app->get('/:category/:id', function($req, $res) {
        return $res->json([
            'category' => $req->param('category'),
            'productId' => $req->param('id'),
            'product' => [
                'name' => 'Sample Product',
                'price' => 999
            ]
        ]);
    });
});

// Run tests
echo "=== YAFS Router Demo ===\n\n";

$tests = [
    ['GET', '/'],
    ['GET', '/about'],
    ['GET', '/api/v1/users'],
    ['GET', '/api/v1/users/123'],
    ['GET', '/api/v2/users'],
    ['GET', '/products/electronics?min=500&max=2000'],
    ['GET', '/products/electronics/laptop-42'],
    ['GET', '/nonexistent'],
];

foreach ($tests as [$method, $path]) {
    echo "\n--- Request: $method $path ---\n";
    $request = new Request($method, $path, $_GET ?? []);
    $result = $app->handle($request);
    
    if (is_array($result)) {
        echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo $result . "\n";
    }
}