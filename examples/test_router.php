<?php

require_once __DIR__ . '/../src/Router/Route.php';
require_once __DIR__ . '/../src/Router/Router.php';
require_once __DIR__ . '/../src/Http/Request.php';

use YAFS\Router\Router;
use YAFS\Http\Request;

$router = new Router();

// Define some routes - notice the ordering matters!
$router->get('/users/new', function ($req) {
  return "Show new user form";
});

$router->get('/users/:id', function ($req) {
  $id = $req->param('id');
  return "Show user $id";
});

$router->get('/users/:id/posts/:postId', function ($req) {
  $userId = $req->param('id');
  $postId = $req->param('postId');
  return "Show post $postId for user $userId";
});

$router->post('/users', function ($req) {
  return "Create new user";
});

$router->get('/products/:category/:subcategory', function ($req) {
  $category = $req->param('category');
  $subcategory = $req->param('subcategory');
  return "Show $subcategory in $category";
});

// Test cases demonstrating various routing scenarios
$testCases = [
  ['GET', '/users/new', 'Show new user form'],
  ['GET', '/users/123', 'Show user 123'],
  ['GET', '/users/123/posts/456', 'Show post 456 for user 123'],
  ['POST', '/users', 'Create new user'],
  ['GET', '/products/electronics/laptops', 'Show laptops in electronics'],
  ['GET', '/nonexistent', null], // Should not match
  ['POST', '/users/123', null], // Wrong method
];

echo "Testing Router:\n\n";

foreach ($testCases as [$method, $path, $expected]) {
  $request = new Request($method, $path);
  $route = $router->match($request);

  if ($route === null) {
    $result = null;
  } else {
    $handler = $route->getHandler();
    $result = $handler($request);
  }

  $status = ($result === $expected) ? '✓' : '✗';
  echo "$status $method $path\n";

  if ($result !== null) {
    echo "  Result: $result\n";
    $params = $request->allParams();
    if (!empty($params)) {
      echo "  Params: " . json_encode($params) . "\n";
    }
  } else {
    echo "  Result: No match\n";
  }

  echo "\n";
}

// Demonstrate the importance of route ordering
echo "\nDemonstrating route order importance:\n\n";

$router2 = new Router();

// If we define the general route first...
$router2->get('/users/:id', function ($req) {
  return "Show user " . $req->param('id');
});

$router2->get('/users/new', function ($req) {
  return "Show new user form";
});

$request = new Request('GET', '/users/new');
$route = $router2->match($request);
$handler = $route->getHandler();
$result = $handler($request);

echo "With /users/:id defined first:\n";
echo "GET /users/new -> $result\n";
echo "The 'new' was captured as an ID!\n";
