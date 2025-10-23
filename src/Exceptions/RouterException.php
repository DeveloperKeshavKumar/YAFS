<?php

namespace YAFS\Exceptions;

/**
 * Base exception for all routing-related errors.
 * 
 * By creating custom exception types, we make it easier for developers
 * to catch and handle specific error conditions. It also makes error
 * messages more informative because we can include context-specific
 * information about what went wrong.
 */
class RouterException extends \Exception
{
  /**
   * Create a router exception with context.
   */
  public function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
  {
    parent::__construct($message, $code, $previous);
  }
}
