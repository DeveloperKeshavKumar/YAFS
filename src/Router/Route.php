<?php

namespace YAFS\Router;

use YAFS\Exceptions\InvalidRouteException;
use YAFS\Exceptions\MiddlewareException;

/**
 * Represents a single route in the application.
 * 
 * A route knows its pattern, HTTP method, handler, and any middleware.
 * It can convert its pattern into a regex for matching URLs and extract
 * parameters from matched URLs.
 */
class Route
{
  private string $pattern;
  private string $method;
  private $handler;
  private array $middleware = [];
  private ?string $compiledPattern = null;
  private array $parameterNames = [];
  private const VALID_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'];

  /**
   * Create a new route.
   *
   * @param string $method HTTP method (GET, POST, PUT, DELETE, etc.)
   * @param string $pattern URL pattern like "/users/:id/posts/:postId"
   * @param callable $handler Function to execute when route matches
   */
  public function __construct(string $method, string $pattern, mixed $handler)
  {
    // Validate HTTP method first
    $method = strtoupper($method);
    if (!in_array($method, self::VALID_METHODS)) {
      throw InvalidRouteException::invalidMethod($method);
    }

    // Validate handler is callable BEFORE assigning
    if (!is_callable($handler)) {
      throw InvalidRouteException::invalidHandler();
    }

    // Validate pattern format
    $this->validatePattern($pattern);

    $this->method = $method;
    $this->pattern = $pattern;
    $this->handler = $handler;

    // Immediately compile the pattern so we're ready to match
    $this->compile();
  }

  /**
   * Validate route pattern for common mistakes.
   */
  private function validatePattern(string $pattern): void
  {
    // Pattern must start with /
    if (!str_starts_with($pattern, '/')) {
      throw InvalidRouteException::invalidPattern(
        $pattern,
        "Pattern must start with '/'"
      );
    }

    // Check for invalid characters in parameter names
    if (preg_match('/:([^a-zA-Z_])/', $pattern, $matches)) {
      throw InvalidRouteException::invalidPattern(
        $pattern,
        "Parameter names must start with a letter or underscore"
      );
    }

    // Check for consecutive slashes (except at start)
    if (preg_match('#//#', $pattern)) {
      throw InvalidRouteException::invalidPattern(
        $pattern,
        "Pattern contains consecutive slashes"
      );
    }

    // Check for spaces in pattern
    if (preg_match('/\s/', $pattern)) {
      throw InvalidRouteException::invalidPattern(
        $pattern,
        "Pattern cannot contain whitespace"
      );
    }
  }

  /**
   * Convert the URL pattern into a regular expression.
   * 
   * This is the heart of how routing works. We take a friendly pattern
   * like "/users/:id" and convert it to a regex like "#^/users/([^/]+)$#"
   * 
   * We also remember the parameter names (:id becomes "id") so we can
   * later extract their values from matched URLs.
   */
  private function compile(): void
  {
    // Start with the original pattern
    $pattern = $this->pattern;

    // Find all parameter placeholders like :id, :name, etc.
    // We use a regex to find them: a colon followed by word characters
    preg_match_all('/:([a-zA-Z_][a-zA-Z0-9_]*)/', $pattern, $matches);

    // Store the parameter names for later
    $this->parameterNames = $matches[1];

    // Check for duplicate parameter names
    $uniqueParams = array_unique($this->parameterNames);
    if (count($uniqueParams) !== count($this->parameterNames)) {
      $duplicates = array_diff_assoc($this->parameterNames, $uniqueParams);
      throw InvalidRouteException::invalidPattern(
        $this->pattern,
        "Duplicate parameter name: " . implode(', ', $duplicates)
      );
    }

    // Replace each :param with a regex capture group
    // [^/]+ means "one or more characters that aren't slashes"
    // This ensures :id only captures the ID part, not the rest of the URL
    $pattern = preg_replace('/:([a-zA-Z_][a-zA-Z0-9_]*)/', '([^/]+)', $pattern);

    // Escape forward slashes so they're treated literally
    $pattern = str_replace('/', '\/', $pattern);

    // Wrap in regex delimiters and add start/end anchors
    // ^ means "start of string", $ means "end of string"
    // This ensures exact matches only
    $this->compiledPattern = '#^' . $pattern . '$#';
  }

  /**
   * Check if this route matches the given method and path.
   *
   * @param string $method HTTP method to check
   * @param string $path URL path to check
   * @return bool True if this route matches
   */
  public function matches(string $method, string $path): bool
  {
    // First check if HTTP method matches
    if ($this->method !== strtoupper($method)) {
      return false;
    }

    // Then check if the path matches our compiled regex pattern
    return preg_match($this->compiledPattern, $path) === 1;
  }

  /**
   * Extract parameter values from a matched path.
   *
   * For example, if our pattern is "/users/:id/posts/:postId"
   * and the path is "/users/123/posts/456", this returns:
   * ['id' => '123', 'postId' => '456']
   *
   * @param string $path The URL path that matched
   * @return array Associative array of parameter names to values
   */
  public function extractParameters(string $path): array
  {
    // Match the path against our regex pattern
    preg_match($this->compiledPattern, $path, $matches);

    // First element is the full match, rest are capture groups
    array_shift($matches);

    if (empty($this->parameterNames)) {
      return [];
    }

    // Combine parameter names with their captured values
    // array_combine pairs up the two arrays into key => value pairs
    return array_combine($this->parameterNames, $matches);
  }

  /**
   * Add middleware to this route.
   *
   * Middleware runs before the handler, useful for auth checks, logging, etc.
   *
   * @param callable $middleware Middleware function
   * @return self For method chaining
   */
  public function middleware(mixed $middleware): self
  {
    // Validate middleware is callable
    if (!is_callable($middleware)) {
      throw MiddlewareException::notCallable($middleware);
    }

    $this->middleware[] = $middleware;
    return $this; // Return $this to allow chaining: ->middleware(...)->middleware(...)
  }

  /**
   * Get the handler function for this route.
   */
  public function getHandler(): callable
  {
    return $this->handler;
  }

  /**
   * Get all middleware for this route.
   */
  public function getMiddleware(): array
  {
    return $this->middleware;
  }

  /**
   * Get the HTTP method for this route.
   */
  public function getMethod(): string
  {
    return $this->method;
  }

  /**
   * Get the original pattern for this route.
   */
  public function getPattern(): string
  {
    return $this->pattern;
  }
}
