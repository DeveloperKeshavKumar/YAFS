<?php

require_once __DIR__ . '/../src/Exceptions/RouterException.php';
require_once __DIR__ . '/../src/Exceptions/InvalidRouteException.php';
require_once __DIR__ . '/../src/Exceptions/RouteNotFoundException.php';
require_once __DIR__ . '/../src/Exceptions/MiddlewareException.php';
require_once __DIR__ . '/../src/Http/Request.php';
require_once __DIR__ . '/../src/Http/Response.php';
require_once __DIR__ . '/../src/Router/Route.php';
require_once __DIR__ . '/../src/Router/Router.php';
require_once __DIR__ . '/../src/Application.php';
require_once __DIR__ . '/../src/Testing/TestRunner.php';
require_once __DIR__ . '/../src/Testing/Assertions.php';

use YAFS\Router\Route;
use YAFS\Application;
use YAFS\Http\Request;
use YAFS\Exceptions\InvalidRouteException;
use YAFS\Exceptions\MiddlewareException;
use YAFS\Testing\TestRunner;
use YAFS\Testing\Assert;

$runner = new TestRunner();

// Test invalid HTTP method
$runner->test('Route throws exception for invalid HTTP method', function () {
  $exceptionThrown = false;

  try {
    new Route('INVALID', '/test', fn() => 'handler');
  } catch (InvalidRouteException $e) {
    $exceptionThrown = true;
    Assert::assertStringContains('INVALID', $e->getMessage());
    Assert::assertStringContains('Valid methods', $e->getMessage());
  }

  Assert::assertTrue($exceptionThrown, 'Should throw InvalidRouteException for invalid method');
});

// Test pattern must start with slash
$runner->test('Route throws exception for pattern not starting with slash', function () {
  $exceptionThrown = false;

  try {
    new Route('GET', 'users', fn() => 'handler');
  } catch (InvalidRouteException $e) {
    $exceptionThrown = true;
    Assert::assertStringContains("must start with '/'", $e->getMessage());
  }

  Assert::assertTrue($exceptionThrown);
});

// Test invalid parameter names
$runner->test('Route throws exception for invalid parameter names', function () {
  $exceptionThrown = false;

  try {
    new Route('GET', '/users/:123', fn() => 'handler');
  } catch (InvalidRouteException $e) {
    $exceptionThrown = true;
    Assert::assertStringContains('must start with a letter', $e->getMessage());
  }

  Assert::assertTrue($exceptionThrown);
});

// Test consecutive slashes
$runner->test('Route throws exception for consecutive slashes', function () {
  $exceptionThrown = false;

  try {
    new Route('GET', '/users//posts', fn() => 'handler');
  } catch (InvalidRouteException $e) {
    $exceptionThrown = true;
    Assert::assertStringContains('consecutive slashes', $e->getMessage());
  }

  Assert::assertTrue($exceptionThrown);
});

// Test whitespace in pattern
$runner->test('Route throws exception for whitespace in pattern', function () {
  $exceptionThrown = false;

  try {
    new Route('GET', '/users /posts', fn() => 'handler');
  } catch (InvalidRouteException $e) {
    $exceptionThrown = true;
    Assert::assertStringContains('cannot contain whitespace', $e->getMessage());
  }

  Assert::assertTrue($exceptionThrown);
});

// Test duplicate parameter names
$runner->test('Route throws exception for duplicate parameter names', function () {
  $exceptionThrown = false;

  try {
    new Route('GET', '/users/:id/posts/:id', fn() => 'handler');
  } catch (InvalidRouteException $e) {
    $exceptionThrown = true;
    Assert::assertStringContains('Duplicate parameter', $e->getMessage());
  }

  Assert::assertTrue($exceptionThrown);
});

// Test non-callable handler
$runner->test('Route throws exception for non-callable handler', function () {
  $exceptionThrown = false;

  try {
    new Route('GET', '/test', 'not-a-function');
  } catch (InvalidRouteException $e) {
    $exceptionThrown = true;
    Assert::assertStringContains('must be a callable', $e->getMessage());
  }

  Assert::assertTrue($exceptionThrown);
});

// Test non-callable middleware
$runner->test('Route throws exception for non-callable middleware', function () {
  $route = new Route('GET', '/test', fn() => 'handler');

  $exceptionThrown = false;

  try {
    $route->middleware('not-callable');
  } catch (MiddlewareException $e) {
    $exceptionThrown = true;
    Assert::assertStringContains('must be callable', $e->getMessage());
  }

  Assert::assertTrue($exceptionThrown);
});

// Test Application handles 404 gracefully
$runner->test('Application handles 404 with proper HTTP status', function () {
  $app = new Application();
  $app->setDebug(false);

  $app->get('/exists', fn($req, $res) => 'content');

  $request = new Request('GET', '/does-not-exist');

  // Capture the response
  ob_start();
  $result = $app->handle($request);
  ob_end_clean();

  Assert::assertStringContains('404', $result);
});

// Test Application handles exceptions in handlers
$runner->test('Application catches exceptions thrown in handlers', function () {
  $app = new Application();
  $app->setDebug(false);

  $app->get('/error', function ($req, $res) {
    throw new \Exception('Handler error');
  });

  $request = new Request('GET', '/error');

  ob_start();
  $result = $app->handle($request);
  ob_end_clean();

  Assert::assertStringContains('500', $result);
});

// Test debug mode shows detailed errors
$runner->test('Application shows detailed errors in debug mode', function () {
  $app = new Application();
  $app->setDebug(true);

  $app->get('/error', function ($req, $res) {
    throw new \Exception('Detailed error message');
  });

  $request = new Request('GET', '/error');

  ob_start();
  $result = $app->handle($request);
  ob_end_clean();

  Assert::assertStringContains('Detailed error message', $result);
  Assert::assertStringContains('Debug Mode', $result);
});

// Test custom error handler
$runner->test('Application uses custom error handler when set', function () {
  $app = new Application();

  $customHandlerCalled = false;

  $app->setErrorHandler(function ($exception) use (&$customHandlerCalled) {
    $customHandlerCalled = true;
    return 'Custom error response';
  });

  $app->get('/error', function ($req, $res) {
    throw new \Exception('Test error');
  });

  $request = new Request('GET', '/error');
  $result = $app->handle($request);

  Assert::assertTrue($customHandlerCalled);
  Assert::assertEquals('Custom error response', $result);
});

$runner->run();
