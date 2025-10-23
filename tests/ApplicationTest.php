<?php

require_once __DIR__ . '/../src/Http/Request.php';
require_once __DIR__ . '/../src/Http/Response.php';
require_once __DIR__ . '/../src/Router/Route.php';
require_once __DIR__ . '/../src/Router/Router.php';
require_once __DIR__ . '/../src/Application.php';
require_once __DIR__ . '/../src/Testing/TestRunner.php';
require_once __DIR__ . '/../src/Testing/Assertions.php';

use YAFS\Application;
use YAFS\Http\Request;
use YAFS\Http\Response;
use YAFS\Testing\TestRunner;
use YAFS\Testing\Assert;

$runner = new TestRunner();

// Test basic application request handling
$runner->test('Application handles simple GET request', function () {
  $app = new Application();

  $app->get('/hello', function ($req, $res) {
    return $res->text('Hello, World!');
  });

  $request = new Request('GET', '/hello');
  $result = $app->handle($request);

  Assert::assertEquals('Hello, World!', $result);
});

// Test application returns 404 for unmatched routes
$runner->test('Application returns 404 for unmatched routes', function () {
  $app = new Application();

  $app->get('/exists', function ($req, $res) {
    return 'content';
  });

  $request = new Request('GET', '/does-not-exist');
  $result = $app->handle($request);

  Assert::assertStringContains('404', $result);
  Assert::assertStringContains('/does-not-exist', $result);
});

// Test that request parameters are accessible in handlers
$runner->test('Application makes route parameters accessible to handlers', function () {
  $app = new Application();

  $app->get('/users/:id', function ($req, $res) {
    $id = $req->param('id');
    return "User ID: {$id}";
  });

  $request = new Request('GET', '/users/42');
  $result = $app->handle($request);

  Assert::assertEquals('User ID: 42', $result);
});

// Test that query parameters work independently of routing
$runner->test('Application handles query parameters separately from routing', function () {
  $app = new Application();

  $app->get('/search', function ($req, $res) {
    $query = $req->query('q', 'default');
    $page = $req->query('page', 1);
    return "Search: {$query}, Page: {$page}";
  });

  $request = new Request('GET', '/search?q=test&page=2', ['q' => 'test', 'page' => '2']);
  $result = $app->handle($request);

  Assert::assertEquals('Search: test, Page: 2', $result);
});

// Test response type handling - JSON
$runner->test('Application handles JSON responses', function () {
  $app = new Application();

  $app->get('/api/data', function ($req, $res) {
    return $res->json(['name' => 'John', 'age' => 30]);
  });

  $request = new Request('GET', '/api/data');
  $result = $app->handle($request);

  Assert::assertTrue(is_array($result));
  Assert::assertArrayHasKey('name', $result);
  Assert::assertEquals('John', $result['name']);
});

// Test all HTTP methods
$runner->test('Application supports all HTTP methods', function () {
  $app = new Application();

  $app->get('/resource', function ($req, $res) {
    return 'GET';
  });

  $app->post('/resource', function ($req, $res) {
    return 'POST';
  });

  $app->put('/resource', function ($req, $res) {
    return 'PUT';
  });

  $app->patch('/resource', function ($req, $res) {
    return 'PATCH';
  });

  $app->delete('/resource', function ($req, $res) {
    return 'DELETE';
  });

  Assert::assertEquals('GET', $app->handle(new Request('GET', '/resource')));
  Assert::assertEquals('POST', $app->handle(new Request('POST', '/resource')));
  Assert::assertEquals('PUT', $app->handle(new Request('PUT', '/resource')));
  Assert::assertEquals('PATCH', $app->handle(new Request('PATCH', '/resource')));
  Assert::assertEquals('DELETE', $app->handle(new Request('DELETE', '/resource')));
});

// Test route groups with prefix
$runner->test('Application route groups apply prefix correctly', function () {
  $app = new Application();

  $app->group(['prefix' => '/api/v1'], function ($app) {
    $app->get('/users', function ($req, $res) {
      return 'v1 users';
    });

    $app->get('/posts', function ($req, $res) {
      return 'v1 posts';
    });
  });

  $usersRequest = new Request('GET', '/api/v1/users');
  $postsRequest = new Request('GET', '/api/v1/posts');

  Assert::assertEquals('v1 users', $app->handle($usersRequest));
  Assert::assertEquals('v1 posts', $app->handle($postsRequest));
});

// Test nested route groups
$runner->test('Application handles nested route groups', function () {
  $app = new Application();

  $app->group(['prefix' => '/api'], function ($app) {
    $app->group(['prefix' => '/v1'], function ($app) {
      $app->get('/users', function ($req, $res) {
        return 'api/v1/users';
      });
    });
  });

  $request = new Request('GET', '/api/v1/users');
  $result = $app->handle($request);

  Assert::assertEquals('api/v1/users', $result);
});

// Test global middleware execution
$runner->test('Application executes global middleware', function () {
  $app = new Application();

  $middlewareExecuted = false;

  $app->use(function ($req, $res, $next) use (&$middlewareExecuted) {
    $middlewareExecuted = true;
    return $next();
  });

  $app->get('/test', function ($req, $res) {
    return 'handler';
  });

  $request = new Request('GET', '/test');
  $app->handle($request);

  Assert::assertTrue($middlewareExecuted, 'Global middleware should have executed');
});

// Test middleware can modify request
$runner->test('Middleware can attach data to request', function () {
  $app = new Application();

  $app->use(function ($req, $res, $next) {
    $req->customData = 'middleware was here';
    return $next();
  });

  $app->get('/test', function ($req, $res) {
    return $req->customData ?? 'not found';
  });

  $request = new Request('GET', '/test');
  $result = $app->handle($request);

  Assert::assertEquals('middleware was here', $result);
});

// Test middleware chain execution order
$runner->test('Middleware executes in correct order', function () {
  $app = new Application();

  $order = [];

  $app->use(function ($req, $res, $next) use (&$order) {
    $order[] = 'global-1';
    return $next();
  });

  $app->use(function ($req, $res, $next) use (&$order) {
    $order[] = 'global-2';
    return $next();
  });

  $app->get('/test', function ($req, $res) use (&$order) {
    $order[] = 'handler';
    return 'done';
  });

  $request = new Request('GET', '/test');
  $app->handle($request);

  Assert::assertEquals(['global-1', 'global-2', 'handler'], $order);
});

// Test route-specific middleware
$runner->test('Application applies route-specific middleware', function () {
  $app = new Application();

  $routeMiddlewareExecuted = false;

  $app->get('/protected', function ($req, $res) {
    return 'protected content';
  })->middleware(function ($req, $res, $next) use (&$routeMiddlewareExecuted) {
    $routeMiddlewareExecuted = true;
    return $next();
  });

  $request = new Request('GET', '/protected');
  $app->handle($request);

  Assert::assertTrue($routeMiddlewareExecuted);
});

// Test group middleware
$runner->test('Application applies group middleware to all routes in group', function () {
  $app = new Application();

  $groupMiddlewareCount = 0;

  $groupMiddleware = function ($req, $res, $next) use (&$groupMiddlewareCount) {
    $groupMiddlewareCount++;
    return $next();
  };

  $app->group(['middleware' => [$groupMiddleware]], function ($app) {
    $app->get('/route1', function ($req, $res) {
      return 'route1';
    });

    $app->get('/route2', function ($req, $res) {
      return 'route2';
    });
  });

  $app->handle(new Request('GET', '/route1'));
  $app->handle(new Request('GET', '/route2'));

  Assert::assertEquals(2, $groupMiddlewareCount, 'Group middleware should execute for both routes');
});

// Test middleware can short-circuit request
$runner->test('Middleware can stop request without calling handler', function () {
  $app = new Application();

  $handlerCalled = false;

  $app->use(function ($req, $res, $next) {
    // Don't call next(), just return early
    return 'blocked by middleware';
  });

  $app->get('/test', function ($req, $res) use (&$handlerCalled) {
    $handlerCalled = true;
    return 'handler';
  });

  $request = new Request('GET', '/test');
  $result = $app->handle($request);

  Assert::assertFalse($handlerCalled, 'Handler should not have been called');
  Assert::assertEquals('blocked by middleware', $result);
});

// Test route groups with both prefix and middleware
$runner->test('Route groups can combine prefix and middleware', function () {
  $app = new Application();

  $authenticated = false;

  $authMiddleware = function ($req, $res, $next) use (&$authenticated) {
    $authenticated = true;
    $req->user = 'John';
    return $next();
  };

  $app->group([
    'prefix' => '/api',
    'middleware' => [$authMiddleware]
  ], function ($app) {
    $app->get('/profile', function ($req, $res) {
      return "Profile for {$req->user}";
    });
  });

  $request = new Request('GET', '/api/profile');
  $result = $app->handle($request);

  Assert::assertTrue($authenticated);
  Assert::assertEquals('Profile for John', $result);
});

// Test complex real-world scenario: API versioning with multi-tenancy
$runner->test('Application handles complex multi-tenant API versioning scenario', function () {
  $app = new Application();

  // Tenant middleware
  $tenantMiddleware = function ($req, $res, $next) {
    $req->tenant = 'acme-corp';
    return $next();
  };

  // API v1
  $app->group([
    'prefix' => '/api/v1',
    'middleware' => [$tenantMiddleware]
  ], function ($app) {
    $app->get('/users/:id', function ($req, $res) {
      return $res->json([
        'version' => 'v1',
        'tenant' => $req->tenant,
        'userId' => $req->param('id')
      ]);
    });
  });

  // API v2
  $app->group([
    'prefix' => '/api/v2',
    'middleware' => [$tenantMiddleware]
  ], function ($app) {
    $app->get('/users/:id', function ($req, $res) {
      return $res->json([
        'version' => 'v2',
        'tenant' => $req->tenant,
        'userId' => $req->param('id'),
        'enhanced' => true
      ]);
    });
  });

  $v1Request = new Request('GET', '/api/v1/users/123');
  $v1Result = $app->handle($v1Request);

  Assert::assertEquals('v1', $v1Result['version']);
  Assert::assertEquals('acme-corp', $v1Result['tenant']);
  Assert::assertEquals('123', $v1Result['userId']);

  $v2Request = new Request('GET', '/api/v2/users/456');
  $v2Result = $app->handle($v2Request);

  Assert::assertEquals('v2', $v2Result['version']);
  Assert::assertTrue($v2Result['enhanced']);
});

$runner->run();
