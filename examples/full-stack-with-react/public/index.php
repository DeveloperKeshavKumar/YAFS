<?php
/**
 * YAFS Application Entry Point
 */

require_once __DIR__ . '/../autoload.php';

use YAFS\Application;
use YAFS\View\View;
use YAFS\View\AssetManager;

// Set view paths
View::setViewsPath(__DIR__ . '/../views');
AssetManager::setMode('production');

// Create application instance
$app = new Application();

// Load routes
if (file_exists(__DIR__ . '/../routes/web.php')) {
    require_once __DIR__ . '/../routes/web.php';
}

if (file_exists(__DIR__ . '/../routes/api.php')) {
    require_once __DIR__ . '/../routes/api.php';
}

// Run the application
$app->run();
