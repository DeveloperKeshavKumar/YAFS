<?php

namespace YAFS\Router;

use YAFS\Http\Request;

/**
 * The Router manages a collection of routes and finds matches for requests.
 * 
 * This is the core of the routing system. It holds all defined routes and
 * provides methods to search through them to find which one should handle
 * an incoming request.
 * 
 * The Router follows a "first match wins" strategy, which means routes are
 * tested in the order they were defined, and the first one that matches is
 * used. This has important implications for route ordering that developers
 * need to understand.
 */
class Router
{
  /**
   * All registered routes, grouped by HTTP method for efficiency.
   * 
   * Structure: ['GET' => [Route, Route, ...], 'POST' => [...], ...]
   * 
   * We group by method so when searching for a match, we only need to
   * check routes that use the correct HTTP method, not all routes.
   */
  private array $routes = [];

  /**
   * Global middleware that runs for all routes.
   */
  private array $globalMiddleware = [];

  /**
   * Register a new route.
   *
   * @param string $method HTTP method
   * @param string $pattern URL pattern
   * @param callable $handler Handler function
   * @return Route The created route (allows chaining middleware)
   */
  public function addRoute(string $method, string $pattern, callable $handler): Route
  {
    $method = strtoupper($method);

    // Create the route
    $route = new Route($method, $pattern, $handler);

    // Add it to our collection, grouped by method
    if (!isset($this->routes[$method])) {
      $this->routes[$method] = [];
    }

    $this->routes[$method][] = $route;

    // Return the route so caller can add middleware if needed
    return $route;
  }

  /**
   * Find a route that matches the given request.
   *
   * This is where the actual routing happens. We search through routes
   * for the request's HTTP method and return the first one that matches.
   *
   * @param Request $request The incoming request
   * @return Route|null The matching route, or null if none found
   */
  public function match(Request $request): ?Route
  {
    $method = $request->getMethod();
    $path = $request->getPath();

    // If we have no routes for this HTTP method, no match possible
    if (!isset($this->routes[$method])) {
      return null;
    }

    // Search through routes for this method in order
    foreach ($this->routes[$method] as $route) {
      if ($route->matches($method, $path)) {
        // Found a match! Extract parameters and store in request
        $params = $route->extractParameters($path);
        $request->setParams($params);

        return $route;
      }
    }

    // No route matched
    return null;
  }

  /**
   * Add global middleware that runs for all routes.
   *
   * @param callable $middleware Middleware function
   */
  public function use(callable $middleware): void
  {
    $this->globalMiddleware[] = $middleware;
  }

  /**
   * Get all global middleware.
   */
  public function getGlobalMiddleware(): array
  {
    return $this->globalMiddleware;
  }

  /**
   * Convenience method for GET routes.
   */
  public function get(string $pattern, callable $handler): Route
  {
    return $this->addRoute('GET', $pattern, $handler);
  }

  /**
   * Convenience method for POST routes.
   */
  public function post(string $pattern, callable $handler): Route
  {
    return $this->addRoute('POST', $pattern, $handler);
  }

  /**
   * Convenience method for PUT routes.
   */
  public function put(string $pattern, callable $handler): Route
  {
    return $this->addRoute('PUT', $pattern, $handler);
  }

  /**
   * Convenience method for DELETE routes.
   */
  public function delete(string $pattern, callable $handler): Route
  {
    return $this->addRoute('DELETE', $pattern, $handler);
  }

  /**
   * Convenience method for PATCH routes.
   */
  public function patch(string $pattern, callable $handler): Route
  {
    return $this->addRoute('PATCH', $pattern, $handler);
  }

  /**
   * Get all registered routes for a specific method.
   * Useful for debugging or generating documentation.
   */
  public function getRoutes(?string $method = null): array
  {
    if ($method !== null) {
      $method = strtoupper($method);
      return $this->routes[$method] ?? [];
    }

    return $this->routes;
  }
}
