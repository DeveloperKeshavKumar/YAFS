<?php

namespace YAFS\Exceptions;

/**
 * Thrown when middleware is misconfigured or fails during execution.
 */
class MiddlewareException extends RouterException
{
  /**
   * Create exception for invalid middleware.
   */
  public static function notCallable($middleware): self
  {
    $type = is_object($middleware) ? get_class($middleware) : gettype($middleware);

    return new self(
      "Middleware must be callable, {$type} given"
    );
  }

  /**
   * Create exception for middleware that doesn't call next().
   */
  public static function didNotCallNext(string $middlewareName): self
  {
    return new self(
      "Middleware '{$middlewareName}' did not call next() and did not return a response"
    );
  }
}
