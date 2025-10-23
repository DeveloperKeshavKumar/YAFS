<?php

namespace YAFS\Exceptions;

/**
 * Thrown when a route is defined with an invalid pattern or configuration.
 * 
 * This is a programmer error that should be caught during development,
 * not in production. When this is thrown, it means the developer needs
 * to fix how they're defining their routes.
 */
class InvalidRouteException extends RouterException
{
  /**
   * Create exception for invalid route pattern.
   */
  public static function invalidPattern(string $pattern, string $reason): self
  {
    return new self(
      "Invalid route pattern '{$pattern}': {$reason}"
    );
  }

  /**
   * Create exception for invalid HTTP method.
   */
  public static function invalidMethod(string $method): self
  {
    $validMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'];
    $validList = implode(', ', $validMethods);

    return new self(
      "Invalid HTTP method '{$method}'. Valid methods are: {$validList}"
    );
  }

  /**
   * Create exception for invalid handler.
   */
  public static function invalidHandler(): self
  {
    return new self(
      "Route handler must be a callable (function, closure, or array)"
    );
  }
}
