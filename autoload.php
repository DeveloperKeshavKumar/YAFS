<?php

/**
 * YAFS Custom Autoloader
 */

spl_autoload_register(function ($class) {
  // Project namespace prefix
  $prefix = 'YAFS\\';

  // Base directory for the namespace prefix
  $base_dir = __DIR__ . '/src/';

  // Does the class use the namespace prefix?
  $len = strlen($prefix);
  if (strncmp($prefix, $class, $len) !== 0) {
    // No, move to the next registered autoloader
    return;
  }

  // Get the relative class name
  $relative_class = substr($class, $len);

  // Replace namespace separators with directory separators
  // and append .php
  $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

  // If the file exists, require it
  if (file_exists($file)) {
    require $file;
  }
});

// Load .env file
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
  $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

  foreach ($lines as $line) {
    $line = trim($line);

    // Skip comments and empty lines
    if (empty($line) || $line[0] === '#') {
      continue;
    }

    // Skip if no = sign
    if (strpos($line, '=') === false) {
      continue;
    }

    // Parse key=value
    [$name, $value] = explode('=', $line, 2);
    $name = trim($name);
    $value = trim($value, " \t\n\r\0\x0B\"'");

    // Set environment variables
    putenv("$name=$value");
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
  }
}
