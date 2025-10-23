<?php

require_once __DIR__ . '/../src/Router/Route.php';
require_once __DIR__ . '/../src/Router/Router.php';
require_once __DIR__ . '/../src/Http/Request.php';
require_once __DIR__ . '/../src/Testing/TestRunner.php';
require_once __DIR__ . '/../src/Testing/Assertions.php';

use YAFS\Router\Router;
use YAFS\Http\Request;
use YAFS\Testing\TestRunner;
use YAFS\Testing\Assert;

$runner = new TestRunner();

// Test basic route registration and matching
$runner->test('Router matches simple GET route', function () {
  $router = new Router();

  $handlerCalled = false;
  $router->get('/users', function () use (&$handlerCalled) {
    $handlerCalled = true;
    return 'users list';
  });

  $request = new Request('GET', '/users');
  $route = $router->match($request);

  Assert::assertNotNull($route, 'Route should match');

  // Verify we can call the handler
  $handler = $route->getHandler();
  $result = $handler();
  Assert::assertTrue($handlerCalled, 'Handler should have been called');
  Assert::assertEquals('users list', $result);
});

// Test that router returns null for non-matching routes
$runner->test('Router returns null when no route matches', function () {
  $router = new Router();
  $router->get('/users', fn() => 'handler');

  $request = new Request('GET', '/posts');
  $route = $router->match($request);

  Assert::assertNull($route, 'Should return null when no route matches');
});

// Test HTTP method separation
$runner->test('Router separates routes by HTTP method', function () {
  $router = new Router();

  $router->get('/users', fn() => 'GET handler');
  $router->post('/users', fn() => 'POST handler');

  // GET request should match GET route
  $getRequest = new Request('GET', '/users');
  $getRoute = $router->match($getRequest);
  Assert::assertNotNull($getRoute);
  Assert::assertEquals('GET', $getRoute->getMethod());

  // POST request should match POST route
  $postRequest = new Request('POST', '/users');
  $postRoute = $router->match($postRequest);
  Assert::assertNotNull($postRoute);
  Assert::assertEquals('POST', $postRoute->getMethod());
});

// Test that router extracts parameters into request
$runner->test('Router extracts parameters into request object', function () {
  $router = new Router();
  $router->get('/users/:id', fn() => 'handler');

  $request = new Request('GET', '/users/123');
  $route = $router->match($request);

  Assert::assertNotNull($route);

  // After matching, request should have parameters
  $params = $request->allParams();
  Assert::assertArrayHasKey('id', $params);
  Assert::assertEquals('123', $params['id']);
});

// Test multiple parameters extraction
$runner->test('Router extracts multiple parameters correctly', function () {
  $router = new Router();
  $router->get('/users/:userId/posts/:postId', fn() => 'handler');

  $request = new Request('GET', '/users/42/posts/99');
  $route = $router->match($request);

  Assert::assertNotNull($route);

  $params = $request->allParams();
  Assert::assertEquals('42', $params['userId']);
  Assert::assertEquals('99', $params['postId']);
});

// Critical test: First match wins strategy
$runner->test('Router uses first-match-wins strategy', function () {
  $router = new Router();

  // Register general route first
  $router->get('/users/:id', fn() => 'dynamic handler');

  // Register specific route second
  $router->get('/users/new', fn() => 'new handler');

  // Request for /users/new should match the first route (dynamic)
  // because it was registered first and matches the pattern
  $request = new Request('GET', '/users/new');
  $route = $router->match($request);

  Assert::assertNotNull($route);

  // The matched route should be the dynamic one
  Assert::assertEquals('/users/:id', $route->getPattern());

  // The parameter should have captured "new" as the id
  $params = $request->allParams();
  Assert::assertEquals('new', $params['id']);
});

// Test the importance of route ordering
$runner->test('Route order matters for specificity', function () {
  $router = new Router();

  // This time register specific route first
  $router->get('/users/new', fn() => 'new handler');

  // Then register general route
  $router->get('/users/:id', fn() => 'dynamic handler');

  // Request for /users/new should match the specific route
  $newRequest = new Request('GET', '/users/new');
  $newRoute = $router->match($newRequest);
  Assert::assertEquals('/users/new', $newRoute->getPattern());

  // Request for /users/123 should match the dynamic route
  $dynamicRequest = new Request('GET', '/users/123');
  $dynamicRoute = $router->match($dynamicRequest);
  Assert::assertEquals('/users/:id', $dynamicRoute->getPattern());
  Assert::assertEquals('123', $dynamicRequest->param('id'));
});

// Test all HTTP method convenience methods
$runner->test('Router provides convenience methods for all HTTP verbs', function () {
  $router = new Router();

  $getRoute = $router->get('/resource', fn() => 'get');
  $postRoute = $router->post('/resource', fn() => 'post');
  $putRoute = $router->put('/resource', fn() => 'put');
  $patchRoute = $router->patch('/resource', fn() => 'patch');
  $deleteRoute = $router->delete('/resource', fn() => 'delete');

  Assert::assertEquals('GET', $getRoute->getMethod());
  Assert::assertEquals('POST', $postRoute->getMethod());
  Assert::assertEquals('PUT', $putRoute->getMethod());
  Assert::assertEquals('PATCH', $patchRoute->getMethod());
  Assert::assertEquals('DELETE', $deleteRoute->getMethod());
});

// Test global middleware registration
$runner->test('Router stores global middleware', function () {
  $router = new Router();

  $middleware1 = fn() => 'mw1';
  $middleware2 = fn() => 'mw2';

  $router->use($middleware1);
  $router->use($middleware2);

  $globalMiddleware = $router->getGlobalMiddleware();
  Assert::assertEquals(2, count($globalMiddleware));
});

// Test that query strings don't interfere with routing
$runner->test('Router ignores query strings in path matching', function () {
  $router = new Router();
  $router->get('/search', fn() => 'search handler');

  // Request with query string should still match
  $request = new Request('GET', '/search?q=test&page=1');
  $route = $router->match($request);

  Assert::assertNotNull($route, 'Route should match despite query string');
  Assert::assertEquals('/search', $route->getPattern());
});

// Test complex nested routes
$runner->test('Router handles deeply nested routes', function () {
  $router = new Router();
  $router->get(
    '/api/v1/users/:userId/posts/:postId/comments/:commentId',
    fn() => 'comment handler'
  );

  $request = new Request('GET', '/api/v1/users/10/posts/20/comments/30');
  $route = $router->match($request);

  Assert::assertNotNull($route);

  $params = $request->allParams();
  Assert::assertEquals('10', $params['userId']);
  Assert::assertEquals('20', $params['postId']);
  Assert::assertEquals('30', $params['commentId']);
});

// Test that routes with different methods on same path don't interfere
$runner->test('Same path with different methods handled independently', function () {
  $router = new Router();

  $router->get('/resource', fn() => 'GET handler');
  $router->post('/resource', fn() => 'POST handler');
  $router->delete('/resource', fn() => 'DELETE handler');

  $getRequest = new Request('GET', '/resource');
  $postRequest = new Request('POST', '/resource');
  $deleteRequest = new Request('DELETE', '/resource');

  Assert::assertNotNull($router->match($getRequest));
  Assert::assertNotNull($router->match($postRequest));
  Assert::assertNotNull($router->match($deleteRequest));

  // PUT should not match because we didn't define it
  $putRequest = new Request('PUT', '/resource');
  Assert::assertNull($router->match($putRequest));
});

// Test route with only static segments
$runner->test('Router handles fully static routes', function () {
  $router = new Router();
  $router->get('/about/team/leadership', fn() => 'leadership');

  $request = new Request('GET', '/about/team/leadership');
  $route = $router->match($request);

  Assert::assertNotNull($route);
  Assert::assertEquals(0, count($request->allParams()), 'Should have no parameters');
});

// Test getRoutes method for introspection
$runner->test('Router allows introspection of registered routes', function () {
  $router = new Router();

  $router->get('/users', fn() => 'h1');
  $router->get('/posts', fn() => 'h2');
  $router->post('/users', fn() => 'h3');

  $getRoutes = $router->getRoutes('GET');
  Assert::assertEquals(2, count($getRoutes), 'Should have 2 GET routes');

  $postRoutes = $router->getRoutes('POST');
  Assert::assertEquals(1, count($postRoutes), 'Should have 1 POST route');

  $allRoutes = $router->getRoutes();
  Assert::assertArrayHasKey('GET', $allRoutes);
  Assert::assertArrayHasKey('POST', $allRoutes);
});

// Test root path routing
$runner->test('Router handles root path correctly', function () {
  $router = new Router();
  $router->get('/', fn() => 'home');

  $request = new Request('GET', '/');
  $route = $router->match($request);

  Assert::assertNotNull($route);
  Assert::assertEquals('/', $route->getPattern());
});

// Test that similar patterns don't cross-match
$runner->test('Router distinguishes between similar patterns', function () {
  $router = new Router();

  $router->get('/users', fn() => 'all users');
  $router->get('/user/:id', fn() => 'single user');

  $usersRequest = new Request('GET', '/users');
  $usersRoute = $router->match($usersRequest);
  Assert::assertEquals('/users', $usersRoute->getPattern());

  $userRequest = new Request('GET', '/user/123');
  $userRoute = $router->match($userRequest);
  Assert::assertEquals('/user/:id', $userRoute->getPattern());
});

// Test that trailing slashes are significant
$runner->test('Router treats trailing slashes as distinct', function () {
  $router = new Router();
  $router->get('/users', fn() => 'without slash');

  $withoutSlash = new Request('GET', '/users');
  $withSlash = new Request('GET', '/users/');

  Assert::assertNotNull($router->match($withoutSlash));
  Assert::assertNull($router->match($withSlash), 'Trailing slash should not match');
});

$runner->run();
