<?php

namespace YAFS;

use YAFS\Router\Router;
use YAFS\Router\Route;
use YAFS\Http\Request;
use YAFS\Http\Response;
use YAFS\Exceptions\RouteNotFoundException;
use YAFS\Exceptions\RouterException;

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
  private bool $debug = true; // Set to false in production
  private $errorHandler = null;

  public function __construct()
  {
    $this->router = new Router();
  }

  /**
   * Set debug mode.
   * In debug mode, detailed error information is shown.
   * In production, errors are logged but generic messages shown to users.
   */
  public function setDebug(bool $debug): void
  {
    $this->debug = $debug;
  }

  /**
   * Set a custom error handler.
   * This function will be called when an exception occurs during request handling.
   */
  public function setErrorHandler(callable $handler): void
  {
    $this->errorHandler = $handler;
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
    try {
      // Try to find a matching route
      $route = $this->router->match($request);

      // If no route matched, return 404
      if ($route === null) {
        throw new RouteNotFoundException($request->getMethod(), $request->getPath());
      }

      // Build the middleware stack
      $middleware = array_merge(
        $this->router->getGlobalMiddleware(),
        $route->getMiddleware()
      );

      // Execute middleware chain and handler
      return $this->executeMiddlewareStack($middleware, $route, $request);
    } catch (RouteNotFoundException $e) {
      return $this->handleNotFound($request, $e);
    } catch (RouterException $e) {
      return $this->handleRouterException($e);
    } catch (\Throwable $e) {
      return $this->handleException($e);
    }
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
  protected function handleNotFound(Request $request, RouteNotFoundException $e)
  {
    http_response_code(404);

    if ($this->debug) {
      return $this->renderDebugError(404, 'Route Not Found', [
        'message' => $e->getMessage(),
        'method' => $e->getMethod(),
        'path' => $e->getPath()
      ]);
    }

    return "404 Not Found";
  }

  /**
   * Handle router-specific exceptions.
   */
  protected function handleRouterException(RouterException $e)
  {
    http_response_code(500);

    if ($this->debug) {
      return $this->renderDebugError(500, 'Router Error', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
      ]);
    }

    // Log the error in production
    error_log("Router Exception: " . $e->getMessage());

    return "500 Internal Server Error";
  }

  /**
   * Handle general exceptions during request processing.
   */
  protected function handleException(\Throwable $e)
  {
    http_response_code(500);

    // If custom error handler is set, use it
    if ($this->errorHandler !== null) {
      try {
        return call_user_func($this->errorHandler, $e);
      } catch (\Throwable $handlerException) {
        // If error handler itself fails, fall back to default handling
        error_log("Error handler failed: " . $handlerException->getMessage());
      }
    }

    if ($this->debug) {
      return $this->renderDebugError(500, 'Application Error', [
        'type' => get_class($e),
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
      ]);
    }

    // Log the error in production
    error_log("Application Exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());

    return "500 Internal Server Error";
  }

  /**
   * Render a debug-friendly error page.
   */
  private function renderDebugError(int $code, string $title, array $details): string
  {
    $detailsHtml = '';
    foreach ($details as $key => $value) {
      $key = htmlspecialchars($key);
      $value = htmlspecialchars($value);
      $detailsHtml .= "<p><strong>{$key}:</strong><br><pre>{$value}</pre></p>";
    }

    return <<<HTML
              <!DOCTYPE html>
              <html>
              <head>
                  <title>{$code} - {$title}</title>
                  <style>
                      body { font-family: sans-serif; margin: 40px; background: #f5f5f5; }
                      .error-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                      h1 { color: #d32f2f; margin-top: 0; }
                      pre { background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto; }
                      .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
                  </style>
              </head>
              <body>
                  <div class="error-container">
                      <h1>{$code} - {$title}</h1>
                      {$detailsHtml}
                      <div class="warning">
                          <strong>Debug Mode Active:</strong> This detailed error information is only shown because debug mode is enabled. 
                          Set debug to false in production environments.
                      </div>
                  </div>
              </body>
              </html>
      HTML;
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
