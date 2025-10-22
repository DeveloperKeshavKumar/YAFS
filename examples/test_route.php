<?php

require_once __DIR__ . '/../src/Router/Route.php';

use YAFS\Router\Route;

// Create a route that matches user profile pages
$route = new Route('GET', '/users/:id', function () {
  return "User profile handler";
});

// Test if it matches various paths
$tests = [
  ['GET', '/users/123', true],      // Should match
  ['GET', '/users/abc', true],      // Should match
  ['POST', '/users/123', false],    // Wrong method
  ['GET', '/users', false],         // Missing parameter
  ['GET', '/users/123/extra', false], // Extra segment
];

echo "Testing route pattern: /users/:id\n\n";

foreach ($tests as [$method, $path, $expected]) {
  $matches = $route->matches($method, $path);
  $result = $matches === $expected ? '✓' : '✗';
  echo "$result $method $path => " . ($matches ? 'match' : 'no match') . "\n";

  if ($matches) {
    $params = $route->extractParameters($path);
    echo "  Parameters: " . json_encode($params) . "\n";
  }
}
