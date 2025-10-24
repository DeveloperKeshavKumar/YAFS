<?php

namespace YAFS\Http;

/**
 * Represents an HTTP request.
 * 
 * This class encapsulates everything about an incoming request:
 * the HTTP method, the path, query parameters, headers, and body.
 * 
 * Importantly, it separates the path from the query string, which
 * is crucial for routing to work correctly.
 * 
 * @author YAFS Framework
 * @package YAFS\Http
 */
class Request
{
  /**
   * The HTTP method (GET, POST, PUT, DELETE, etc.).
   */
  private string $method;

  /**
   * The request path without query string.
   */
  private string $path;

  /**
   * Query string parameters from the URL.
   */
  private array $queryParams;

  /**
   * Route parameters extracted by the router.
   * For example, for route "/users/:id" matching "/users/123",
   * this would be ['id' => '123']
   */
  private array $params = [];

  /**
   * HTTP headers from the request.
   */
  private array $headers;

  /**
   * The raw request body.
   */
  private mixed $body;

  /**
   * Create a new request instance.
   *
   * @param string $method HTTP method (GET, POST, etc.)
   * @param string $uri Full request URI (may include query string)
   * @param array $queryParams Query string parameters
   * @param array $headers HTTP headers
   * @param mixed $body Request body (for POST, PUT, etc.)
   */
  public function __construct(
    string $method,
    string $uri,
    array $queryParams = [],
    array $headers = [],
    mixed $body = null
  ) {
    $this->method = strtoupper($method);
    $this->headers = $headers;
    $this->body = $body;
    $this->queryParams = $queryParams;

    // This is the critical part: separate path from query string
    // If URI is "/search/term?color=black", we want path to be just "/search/term"
    $this->path = $this->extractPath($uri);
  }

  /**
   * Extract the path portion from a URI.
   * 
   * Given a URI like "/search/term?color=black&price=500"
   * this returns just "/search/term"
   * 
   * This is why routing works correctly regardless of query parameters.
   * 
   * @param string $uri The full URI including potential query string
   * @return string The path portion without query string
   */
  private function extractPath(string $uri): string
  {
    // Find the position of the question mark
    $questionMarkPos = strpos($uri, '?');

    // If there's no question mark, the entire URI is the path
    if ($questionMarkPos === false) {
      return $uri;
    }

    // Otherwise, take everything before the question mark
    return substr($uri, 0, $questionMarkPos);
  }

  /**
   * Create a Request from PHP's global variables.
   * 
   * This is how we'll typically create requests in the real application.
   * It reads from $_SERVER, $_GET, and php://input to construct the request object.
   * 
   * @return self A new Request instance built from PHP globals
   */
  public static function fromGlobals(): self
  {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';

    // PHP automatically parses query string into $_GET
    $queryParams = $_GET;

    // Collect headers from $_SERVER
    // In PHP, headers are prefixed with HTTP_ in $_SERVER
    $headers = [];
    foreach ($_SERVER as $key => $value) {
      if (strpos($key, 'HTTP_') === 0) {
        $headerName = str_replace('HTTP_', '', $key);
        $headerName = str_replace('_', '-', $headerName);
        $headers[strtolower($headerName)] = $value;
      }
    }

    // For POST/PUT/PATCH requests, get the body
    $body = null;
    if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
      $body = file_get_contents('php://input');
    }

    return new self($method, $uri, $queryParams, $headers, $body);
  }

  /**
   * Get the HTTP method.
   * 
   * @return string The HTTP method in uppercase (GET, POST, PUT, DELETE, etc.)
   */
  public function getMethod(): string
  {
    return $this->method;
  }

  /**
   * Get the path (without query string).
   * 
   * This is what the router uses for pattern matching.
   * For example, for URL "/users/123?active=true", this returns "/users/123"
   * 
   * @return string The request path
   */
  public function getPath(): string
  {
    return $this->path;
  }

  /**
   * Get a query parameter by name.
   * 
   * Query parameters are from the URL after the question mark.
   * For URL "/search?q=phones&color=black":
   * - $request->query('q') returns "phones"
   * - $request->query('color') returns "black"
   * - $request->query('missing', 'default') returns "default"
   * 
   * @param string $key The query parameter name
   * @param mixed $default Default value if parameter doesn't exist
   * @return mixed The parameter value or default
   */
  public function query(string $key, mixed $default = null): mixed
  {
    return $this->queryParams[$key] ?? $default;
  }

  /**
   * Get all query parameters.
   * 
   * Returns the entire query string as an associative array.
   * For URL "/search?q=phones&color=black", returns:
   * ['q' => 'phones', 'color' => 'black']
   * 
   * @return array All query parameters
   */
  public function allQuery(): array
  {
    return $this->queryParams;
  }

  /**
   * Set route parameters extracted by the router.
   * 
   * When a route pattern like "/users/:id" matches "/users/123",
   * the router calls this method to store ['id' => '123'].
   * 
   * This should only be called by the routing system, not in application code.
   * 
   * @param array $params Associative array of parameter names to values
   * @return void
   */
  public function setParams(array $params): void
  {
    $this->params = $params;
  }

  /**
   * Get route parameters.
   * 
   * This method is flexible and can be used in multiple ways:
   * 
   * 1. Get all parameters:
   *    $params = $request->params()
   *    Returns: ['id' => '123', 'slug' => 'hello-world']
   * 
   * 2. Get specific parameter:
   *    $id = $request->params('id')
   *    Returns: '123'
   * 
   * 3. Get parameter with default:
   *    $page = $request->params('page', 1)
   *    Returns: '1' if 'page' doesn't exist
   * 
   * Route parameters come from the URL pattern, not the query string.
   * For route "/users/:id" matching "/users/123", this gives you the '123'.
   * 
   * @param string|null $key Parameter name, or null to get all parameters
   * @param mixed $default Default value if parameter doesn't exist
   * @return mixed The parameter value, all parameters, or default
   */
  public function params(?string $key = null, mixed $default = null): mixed
  {
    // If no key provided, return all params
    if ($key === null) {
      return $this->params;
    }

    // Otherwise return specific param
    return $this->params[$key] ?? $default;
  }

  /**
   * Get a route parameter by name (singular alias).
   * 
   * This is an alternative to params() that some developers prefer.
   * For pattern "/users/:id" matching "/users/123":
   * $request->param('id') returns "123"
   * 
   * @param string $key Parameter name
   * @param mixed $default Default value if parameter doesn't exist
   * @return mixed The parameter value or default
   */
  public function param(string $key, mixed $default = null): mixed
  {
    return $this->params[$key] ?? $default;
  }

  /**
   * Get all route parameters.
   * 
   * Returns all parameters extracted from the route pattern.
   * This is equivalent to calling params() with no arguments.
   * 
   * @return array All route parameters as associative array
   */
  public function allParams(): array
  {
    return $this->params;
  }

  /**
   * Get a header by name.
   * 
   * Header names are case-insensitive.
   * Examples:
   * - $request->header('Content-Type')
   * - $request->header('User-Agent')
   * - $request->header('X-Custom-Header', 'default')
   * 
   * @param string $name Header name (case-insensitive)
   * @param mixed $default Default value if header doesn't exist
   * @return mixed The header value or default
   */
  public function header(string $name, mixed $default = null): mixed
  {
    $name = strtolower($name);
    return $this->headers[$name] ?? $default;
  }

  /**
   * Get the raw request body.
   * 
   * Returns the body exactly as received, typically a string.
   * For POST/PUT/PATCH requests, this is the raw data from php://input.
   * 
   * @return mixed The raw request body
   */
  public function getBody(): mixed
  {
    return $this->body;
  }

  /**
   * Get the body parsed as JSON.
   * 
   * If the body is a JSON string, this parses it and returns an array.
   * Returns null if the body is not valid JSON.
   * 
   * Example:
   * For body: {"name": "John", "age": 30}
   * Returns: ['name' => 'John', 'age' => 30]
   * 
   * @return mixed The parsed JSON as array, or null if not valid JSON
   */
  public function json(): mixed
  {
    if (is_string($this->body)) {
      return json_decode($this->body, true);
    }
    return null;
  }

  /**
   * Get all POST data as an array.
   * 
   * This returns the entire $_POST superglobal as an array.
   * Useful for form submissions.
   * 
   * Example:
   * $data = $request->body()
   * Returns: ['title' => 'My Todo', 'description' => '...', 'priority' => 'high']
   * 
   * @return array All POST data
   */
  public function body(): array
  {
    return $_POST;
  }

  /**
   * Get a specific POST field value.
   * 
   * Retrieves a single value from the POST data.
   * 
   * Examples:
   * - $request->post('title')
   * - $request->post('email', 'guest@example.com')
   * 
   * @param string $key Field name
   * @param mixed $default Default value if field doesn't exist
   * @return mixed The field value or default
   */
  public function post(string $key, $default = null)
  {
    return $_POST[$key] ?? $default;
  }

  /**
   * Get a value from POST or GET (input from either source).
   * 
   * Searches POST first, then GET. Useful when you don't care
   * about the source of the data.
   * 
   * Examples:
   * - $request->input('search')  // Works for both POST and GET
   * - $request->input('page', 1)
   * 
   * @param string $key Field/parameter name
   * @param mixed $default Default value if not found in either POST or GET
   * @return mixed The input value or default
   */
  public function input(string $key, $default = null)
  {
    return $_POST[$key] ?? $_GET[$key] ?? $default;
  }

  /**
   * Check if the request is an AJAX request.
   * 
   * Detects if the request was made via XMLHttpRequest (AJAX).
   * Most JavaScript frameworks (jQuery, Axios, etc.) set this header.
   * 
   * @return bool True if AJAX request, false otherwise
   */
  public function isAjax(): bool
  {
    return $this->header('x-requested-with') === 'XMLHttpRequest';
  }

  /**
   * Check if the request expects a JSON response.
   * 
   * Looks at the Accept header to determine if the client
   * wants JSON back. Useful for API endpoints.
   * 
   * @return bool True if JSON response expected, false otherwise
   */
  public function expectsJson(): bool
  {
    return strpos($this->header('accept', ''), 'application/json') !== false;
  }

  /**
   * Get the client's IP address.
   * 
   * Returns the IP address of the client making the request.
   * Note: This can be spoofed if behind a proxy without proper configuration.
   * 
   * @return string|null The client IP address or null if not available
   */
  public function ip(): ?string
  {
    return $_SERVER['REMOTE_ADDR'] ?? null;
  }

  /**
   * Get the User-Agent string.
   * 
   * Returns the browser/client User-Agent header.
   * Example: "Mozilla/5.0 (Windows NT 10.0; Win64; x64)..."
   * 
   * @return string|null The User-Agent string or null if not set
   */
  public function userAgent(): ?string
  {
    return $this->header('user-agent');
  }
}
