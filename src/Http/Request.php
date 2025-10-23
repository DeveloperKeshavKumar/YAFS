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
 */
class Request
{
  private string $method;
  private string $path;
  private array $queryParams;
  private array $params = [];
  private array $headers;
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

    // For POST requests, get the body
    $body = null;
    if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
      $body = file_get_contents('php://input');
    }

    return new self($method, $uri, $queryParams, $headers, $body);
  }

  /**
   * Get the HTTP method.
   */
  public function getMethod(): string
  {
    return $this->method;
  }

  /**
   * Get the path (without query string).
   * 
   * This is what the router uses for pattern matching.
   */
  public function getPath(): string
  {
    return $this->path;
  }

  /**
   * Get a query parameter by name.
   * 
   * For URL "/search?q=phones&color=black"
   * $request->query('q') returns "phones"
   * $request->query('missing', 'default') returns "default"
   */
  public function query(string $key, mixed $default = null): mixed
  {
    return $this->queryParams[$key] ?? $default;
  }

  /**
   * Get all query parameters.
   */
  public function allQuery(): array
  {
    return $this->queryParams;
  }

  /**
   * Set route parameters extracted by the router.
   * 
   * When a route pattern like "/users/:id" matches "/users/123"
   * the router calls this to store ['id' => '123']
   */
  public function setParams(array $params): void
  {
    $this->params = $params;
  }

  /**
   * Get a route parameter by name.
   * 
   * For pattern "/users/:id" matching "/users/123"
   * $request->param('id') returns "123"
   */
  public function param(string $key, mixed $default = null): mixed
  {
    return $this->params[$key] ?? $default;
  }

  /**
   * Get all route parameters.
   */
  public function allParams(): array
  {
    return $this->params;
  }

  /**
   * Get a header by name.
   */
  public function header(string $name, mixed $default = null): mixed
  {
    $name = strtolower($name);
    return $this->headers[$name] ?? $default;
  }

  /**
   * Get the request body.
   */
  public function getBody(): mixed
  {
    return $this->body;
  }

  /**
   * Get the body as JSON.
   */
  public function json(): mixed
  {
    if (is_string($this->body)) {
      return json_decode($this->body, true);
    }
    return null;
  }
}
