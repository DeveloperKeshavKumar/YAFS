<?php

namespace YAFS;

use YAFS\Router\Router;
use YAFS\Router\Route;
use YAFS\Http\Request;
use YAFS\Http\Response;

/**
 * The Application class is the main entry point for YAFS.
 * 
 * This is what developers interact with. It provides a clean,
 * Express.js-inspired API for defining routes and middleware,
 * while internally managing the Router and handling the request
 * lifecycle.
 * 
 * The Application coordinates between receiving requests,
 * routing them, running middleware, executing handlers, and
 * sending responses.
 */
class Application
{
  private Router $router;
  private array $routeGroups = [];

  public function __construct()
  {
    $this->router = new Router();
  }

  /**
   * Define a GET route.
   * 
   * This is the most common type of route, used for retrieving data.
   * 
   * @param string $pattern URL pattern
   * @param callable $handler Handler function
   * @return Route The created route (for chaining middleware)
   */
  public function get(string $pattern, callable $handler): Route
  {
    $pattern = $this->applyGroupPrefix($pattern);
    $route = $this->router->get($pattern, $handler);
    $this->applyGroupMiddleware($route);
    return $route;
  }

  /**
   * Define a POST route.
   * 
   * Used for creating new resources.
   */
  public function post(string $pattern, callable $handler): Route
  {
    $pattern = $this->applyGroupPrefix($pattern);
    $route = $this->router->post($pattern, $handler);
    $this->applyGroupMiddleware($route);
    return $route;
  }

  /**
   * Define a PUT route.
   * 
   * Used for updating existing resources (full replacement).
   */
  public function put(string $pattern, callable $handler): Route
  {
    $pattern = $this->applyGroupPrefix($pattern);
    $route = $this->router->put($pattern, $handler);
    $this->applyGroupMiddleware($route);
    return $route;
  }

  /**
   * Define a PATCH route.
   * 
   * Used for partially updating existing resources.
   */
  public function patch(string $pattern, callable $handler): Route
  {
    $pattern = $this->applyGroupPrefix($pattern);
    $route = $this->router->patch($pattern, $handler);
    $this->applyGroupMiddleware($route);
    return $route;
  }

  /**
   * Define a DELETE route.
   * 
   * Used for removing resources.
   */
  public function delete(string $pattern, callable $handler): Route
  {
    $pattern = $this->applyGroupPrefix($pattern);
    $route = $this->router->delete($pattern, $handler);
    $this->applyGroupMiddleware($route);
    return $route;
  }

  /**
   * Add global middleware that runs for all routes.
   * 
   * @param callable $middleware Middleware function
   */
  public function use(callable $middleware): void
  {
    $this->router->use($middleware);
  }

  /**
   * Create a route group with a common prefix.
   * 
   * This is incredibly useful for API versioning and organizing routes.
   * 
   * Example:
   * $app->group(['prefix' => '/api/v1'], function($app) {
   *     $app->get('/users', ...); // Becomes /api/v1/users
   *     $app->get('/posts', ...); // Becomes /api/v1/posts
   * });
   * 
   * @param array $attributes Group attributes (prefix, middleware, etc.)
   * @param callable $callback Function that defines routes in this group
   */
  public function group(array $attributes, callable $callback): void
  {
    // Save the current group state
    $previousGroup = end($this->routeGroups) ?: [];

    // Merge with new attributes, allowing nesting
    $newGroup = [
      'prefix' => ($previousGroup['prefix'] ?? '') . ($attributes['prefix'] ?? ''),
      'middleware' => array_merge(
        $previousGroup['middleware'] ?? [],
        $attributes['middleware'] ?? []
      ),
    ];

    // Push this group onto the stack
    $this->routeGroups[] = $newGroup;

    // Execute the callback, which will define routes in this group
    $callback($this);

    // Pop the group off the stack when done
    array_pop($this->routeGroups);
  }

  /**
   * Apply the current group prefix to a pattern.
   * 
   * This is called internally when defining routes to automatically
   * prepend group prefixes.
   */
  private function applyGroupPrefix(string $pattern): string
  {
    if (empty($this->routeGroups)) {
      return $pattern;
    }

    $currentGroup = end($this->routeGroups);
    $prefix = $currentGroup['prefix'] ?? '';

    if (empty($prefix)) {
      return $pattern;
    }

    $prefix = rtrim($prefix, '/');
    $pattern = '/' . ltrim($pattern, '/');

    return $prefix . $pattern;
  }

  // New helper method to apply group middleware to a route
  private function applyGroupMiddleware(Route $route): void
  {
    if (empty($this->routeGroups)) {
      return;
    }

    $currentGroup = end($this->routeGroups);
    $middleware = $currentGroup['middleware'] ?? [];

    foreach ($middleware as $mw) {
      $route->middleware($mw);
    }
  }

  /**
   * Handle an incoming request.
   * 
   * This is the core request lifecycle:
   * 1. Find a matching route
   * 2. Run global middleware
   * 3. Run route-specific middleware
   * 4. Execute the handler
   * 5. Return the response
   * 
   * @param Request $request The incoming request
   * @return mixed The response from the handler
   */
  public function handle(Request $request)
  {
    // Try to find a matching route
    $route = $this->router->match($request);

    // If no route matched, return 404
    if ($route === null) {
      http_response_code(404);
      return $this->handleNotFound($request);
    }

    // Build the middleware stack
    $middleware = array_merge(
      $this->router->getGlobalMiddleware(),
      $route->getMiddleware()
    );

    // Execute middleware chain and handler
    return $this->executeMiddlewareStack($middleware, $route, $request);
  }

  /**
   * Execute the middleware stack and final handler.
   * 
   * Middleware forms a chain where each middleware can:
   * - Do something before the handler
   * - Call next() to continue the chain
   * - Do something after the handler
   * - Or stop the chain early by not calling next()
   * 
   * This is a common pattern in web frameworks that provides
   * great flexibility for cross-cutting concerns like logging,
   * authentication, etc.
   */
  private function executeMiddlewareStack(array $middleware, Route $route, Request $request)
  {
    // Create a response object that handlers can use
    $response = new Response();

    // Build the middleware chain from the end backwards
    $handler = $route->getHandler();

    // Wrap the handler in middleware layers
    $next = function () use ($handler, $request, $response) {
      return $handler($request, $response);
    };

    // Wrap each middleware around the next
    foreach (array_reverse($middleware) as $mw) {
      $currentNext = $next;
      $next = function () use ($mw, $request, $response, $currentNext) {
        return $mw($request, $response, $currentNext);
      };
    }

    // Execute the complete chain
    return $next();
  }

  /**
   * Handle a 404 Not Found response.
   * 
   * Override this method to customize 404 handling.
   */
  protected function handleNotFound(Request $request)
  {
    return "404 Not Found: {$request->getPath()}";
  }

  /**
   * Run the application by handling the current HTTP request.
   * 
   * This is typically called once at the end of your index.php:
   * $app->run();
   */
  public function run(): void
  {
    // Create request from PHP globals
    $request = Request::fromGlobals();

    // Handle the request
    $result = $this->handle($request);

    // Send the response
    if (is_string($result)) {
      echo $result;
    } elseif (is_array($result) || is_object($result)) {
      header('Content-Type: application/json');
      echo json_encode($result);
    }
  }
}
