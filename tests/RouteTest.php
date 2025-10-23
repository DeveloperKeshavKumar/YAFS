<?php

require_once __DIR__ . '/../src/Router/Route.php';
require_once __DIR__ . '/../src/Testing/TestRunner.php';
require_once __DIR__ . '/../src/Testing/Assertions.php';

use YAFS\Router\Route;
use YAFS\Testing\TestRunner;
use YAFS\Testing\Assert;

$runner = new TestRunner();

// Test basic route matching
$runner->test('Route matches correct HTTP method', function () {
  $route = new Route('GET', '/users', fn() => 'handler');

  Assert::assertTrue($route->matches('GET', '/users'));
  Assert::assertFalse($route->matches('POST', '/users'));
  Assert::assertFalse($route->matches('PUT', '/users'));
});

// Test case-insensitive method matching
$runner->test('Route matching is case-insensitive for methods', function () {
  $route = new Route('get', '/users', fn() => 'handler');

  Assert::assertTrue($route->matches('GET', '/users'));
  Assert::assertTrue($route->matches('get', '/users'));
  Assert::assertTrue($route->matches('Get', '/users'));
});

// Test simple parameter extraction
$runner->test('Route extracts single parameter', function () {
  $route = new Route('GET', '/users/:id', fn() => 'handler');

  Assert::assertTrue($route->matches('GET', '/users/123'));

  $params = $route->extractParameters('/users/123');
  Assert::assertArrayHasKey('id', $params);
  Assert::assertEquals('123', $params['id']);
});

// Test multiple parameters
$runner->test('Route extracts multiple parameters', function () {
  $route = new Route('GET', '/users/:userId/posts/:postId', fn() => 'handler');

  Assert::assertTrue($route->matches('GET', '/users/42/posts/99'));

  $params = $route->extractParameters('/users/42/posts/99');
  Assert::assertArrayHasKey('userId', $params);
  Assert::assertArrayHasKey('postId', $params);
  Assert::assertEquals('42', $params['userId']);
  Assert::assertEquals('99', $params['postId']);
});

// Test that parameters don't match across slashes
$runner->test('Route parameters respect slash boundaries', function () {
  $route = new Route('GET', '/users/:id', fn() => 'handler');

  // Should match single segment
  Assert::assertTrue($route->matches('GET', '/users/123'));

  // Should not match multiple segments
  Assert::assertFalse($route->matches('GET', '/users/123/extra'));
});

// Test static routes (no parameters)
$runner->test('Static route matches exactly', function () {
  $route = new Route('GET', '/about', fn() => 'handler');

  Assert::assertTrue($route->matches('GET', '/about'));
  Assert::assertFalse($route->matches('GET', '/about/extra'));
  Assert::assertFalse($route->matches('GET', '/abou'));
});

// Test mixed static and dynamic segments
$runner->test('Route with mixed static and dynamic segments', function () {
  $route = new Route('GET', '/api/v1/users/:id', fn() => 'handler');

  Assert::assertTrue($route->matches('GET', '/api/v1/users/789'));
  Assert::assertFalse($route->matches('GET', '/api/v2/users/789'));
  Assert::assertFalse($route->matches('GET', '/api/v1/posts/789'));
});

// Test parameter names with underscores
$runner->test('Route handles parameter names with underscores', function () {
  $route = new Route('GET', '/users/:user_id/posts/:post_id', fn() => 'handler');

  $params = $route->extractParameters('/users/123/posts/456');
  Assert::assertArrayHasKey('user_id', $params);
  Assert::assertArrayHasKey('post_id', $params);
});

// Test middleware attachment
$runner->test('Route stores middleware', function () {
  $route = new Route('GET', '/users', fn() => 'handler');

  $middleware1 = fn() => 'mw1';
  $middleware2 = fn() => 'mw2';

  $route->middleware($middleware1);
  $route->middleware($middleware2);

  $middleware = $route->getMiddleware();
  Assert::assertEquals(2, count($middleware));
});

// Test middleware chaining
$runner->test('Middleware methods support chaining', function () {
  $route = new Route('GET', '/users', fn() => 'handler');

  $mw1 = fn() => 'mw1';
  $mw2 = fn() => 'mw2';

  // Should be able to chain
  $result = $route->middleware($mw1)->middleware($mw2);

  Assert::assertInstanceOf(Route::class, $result);
  Assert::assertEquals(2, count($route->getMiddleware()));
});

// Test root path
$runner->test('Route matches root path', function () {
  $route = new Route('GET', '/', fn() => 'handler');

  Assert::assertTrue($route->matches('GET', '/'));
  Assert::assertFalse($route->matches('GET', '/anything'));
});

// Test trailing slash sensitivity
$runner->test('Route distinguishes trailing slashes', function () {
  $route = new Route('GET', '/users', fn() => 'handler');

  Assert::assertTrue($route->matches('GET', '/users'));
  Assert::assertFalse($route->matches('GET', '/users/'));
});

$runner->run();
