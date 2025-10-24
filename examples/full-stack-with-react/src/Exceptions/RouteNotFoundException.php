<?php

namespace YAFS\Exceptions;

/**
 * Thrown when no route matches the incoming request.
 * 
 * This represents a 404 situation. The exception carries information
 * about what was requested so it can be logged or displayed.
 */
class RouteNotFoundException extends RouterException
{
  private string $method;
  private string $path;

  public function __construct(string $method, string $path)
  {
    $this->method = $method;
    $this->path = $path;

    parent::__construct(
      "No route found for {$method} {$path}"
    );
  }

  public function getMethod(): string
  {
    return $this->method;
  }

  public function getPath(): string
  {
    return $this->path;
  }
}
