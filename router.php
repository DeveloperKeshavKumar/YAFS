<?php

// Load application
require_once __DIR__ . '/autoload.php';

/**
 * YAFS Development Router
 * Proxies Vite requests and handles PHP routing
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$vitePort = $_ENV['VITE_PORT'] ?? 5173;

// Proxy Vite dev server requests
if (shouldProxyToVite($uri)) {
  proxyToVite($uri, $vitePort);
  exit;
}

// Serve static files from public/
$publicPath = __DIR__ . '/public' . $uri;
if ($uri !== '/' && file_exists($publicPath) && !is_dir($publicPath)) {
  return false; // Let PHP's built-in server handle it
}

use YAFS\Application;
use YAFS\View\View;
use YAFS\View\AssetManager;

AssetManager::setManifestPath(__DIR__ . '/public/assets/build/.vite/manifest.json');
AssetManager::setViteDevServer("http://localhost:$vitePort");

// Set views path
View::setViewsPath(__DIR__ . '/views');

// Create application
$app = new Application();

if (isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] === 'false') {
  $app->setDebug(false);
}

// Load routes
if (file_exists(__DIR__ . '/routes/web.php')) {
  require_once __DIR__ . '/routes/web.php';
}

if (file_exists(__DIR__ . '/routes/api.php')) {
  require_once __DIR__ . '/routes/api.php';
}

if (!file_exists(__DIR__ . '/routes/web.php') && !file_exists(__DIR__ . '/routes/api.php')) {
  $app->get('/', function ($req, $res) {
    return $res->view('react', [
      'title' => 'YAFS',
      'props' => [
        'title' => 'Welcome to YAFS!',
        'message' => 'React + PHP on single port!'
      ]
    ]);
  });
}

// Run application
$app->run();

/**
 * Check if request should be proxied to Vite
 */
function shouldProxyToVite(string $uri): bool
{
  // Vite special paths
  if (preg_match('#^/@(vite|react-refresh|fs|id)/#', $uri)) {
    return true;
  }

  // Frontend source files
  if (preg_match('#^/src/.+\.(jsx?|tsx?|css|vue|svelte)$#', $uri)) {
    return true;
  }

  // Node modules (for Vite processing)
  if (preg_match('#^/node_modules/#', $uri)) {
    return true;
  }

  // HMR WebSocket and ping
  if (strpos($uri, '/__vite_ping') !== false) {
    return true;
  }

  // Vite client
  if (strpos($uri, '@vite/client') !== false) {
    return true;
  }

  return false;
}

/**
 * Proxy request to Vite dev server
 */
function proxyToVite(string $uri, int $vitePort): void
{
  $viteUrl = "http://localhost:$vitePort" . $_SERVER['REQUEST_URI'];

  // Initialize cURL
  $ch = curl_init($viteUrl);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HEADER, true);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 30);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

  // Forward headers from client (except host)
  $headers = [];
  if (function_exists('getallheaders')) {
    foreach (getallheaders() as $name => $value) {
      if (strtolower($name) !== 'host') {
        $headers[] = "$name: $value";
      }
    }
  }
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

  // Execute request
  $response = curl_exec($ch);

  // Check for errors
  if (curl_errno($ch)) {
    http_response_code(502);
    header('Content-Type: text/plain');
    echo "Vite proxy error: " . curl_error($ch);
    curl_close($ch);
    return;
  }

  $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

  curl_close($ch);

  // Extract headers and body
  $responseHeaders = substr($response, 0, $headerSize);
  $body = substr($response, $headerSize);

  // Set status code
  http_response_code($httpCode);

  // Forward response headers (skip certain headers)
  $skipHeaders = ['transfer-encoding', 'connection', 'keep-alive'];
  foreach (explode("\r\n", $responseHeaders) as $header) {
    $header = trim($header);
    if (empty($header) || strpos($header, 'HTTP/') === 0) {
      continue;
    }

    // Check if header should be skipped
    $headerLower = strtolower($header);
    $skip = false;
    foreach ($skipHeaders as $skipHeader) {
      if (strpos($headerLower, $skipHeader) === 0) {
        $skip = true;
        break;
      }
    }

    if (!$skip) {
      header($header);
    }
  }

  // Output body
  echo $body;
}
