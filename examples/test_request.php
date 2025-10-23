<?php

require_once __DIR__ . '/../src/Http/Request.php';

use YAFS\Http\Request;

echo "Testing Request path extraction:\n\n";

// Test various URIs to see how path extraction works
$testCases = [
  '/search/phones',
  '/search/phones?color=black',
  '/search/phones?color=black&price=500',
  '/users/123',
  '/users/123?includeDeleted=true',
  '/',
  '/?debug=true',
];

foreach ($testCases as $uri) {
  $request = new Request('GET', $uri, ['test' => 'value']);
  echo "URI: $uri\n";
  echo "Path extracted: " . $request->getPath() . "\n";
  echo "---\n";
}

echo "\nTesting parameter separation:\n\n";

// Create a request simulating /users/123?includeDeleted=true
$request = new Request('GET', '/users/123', ['includeDeleted' => 'true']);

// Simulate what the router would do after matching
$request->setParams(['id' => '123']);

echo "Full URI would be: /users/123?includeDeleted=true\n";
echo "Path for routing: " . $request->getPath() . "\n";
echo "Route param 'id': " . $request->param('id') . "\n";
echo "Query param 'includeDeleted': " . $request->query('includeDeleted') . "\n";
