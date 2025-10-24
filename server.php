<?php
/**
 * YAFS Development Server
 * 
 * Smart detection:
 * - Has frontend/ → Starts Vite + PHP
 * - No frontend/ → PHP only (templates mode)
 * 
 * Single command: php server.php
 */

// Configuration
define('SERVER_PORT', 8000);
define('VITE_PORT', 5173);
define('FRONTEND_DIR', __DIR__ . '/frontend');

// Colors for terminal output
class Console {
    public static function info($msg) { echo "\033[36m[INFO]\033[0m $msg\n"; }
    public static function success($msg) { echo "\033[32m[✓]\033[0m $msg\n"; }
    public static function error($msg) { echo "\033[31m[✗]\033[0m $msg\n"; }
    public static function warn($msg) { echo "\033[33m[!]\033[0m $msg\n"; }
    public static function header($msg) { echo "\n\033[1m$msg\033[0m\n"; }
}

// Detect mode
$hasReact = file_exists(FRONTEND_DIR . '/package.json');
$mode = $hasReact ? 'full-stack' : 'templates';

// Banner
Console::header("╔══════════════════════════════════════╗");
Console::header("║   YAFS Development Server v0.4.0     ║");
Console::header("╚══════════════════════════════════════╝");

if ($mode === 'templates') {
    Console::info("Mode: PHP Templates Only");
    Console::info("No frontend/ detected - running PHP-only mode");
    Console::success("Perfect for server-side rendering!");
    
} else {
    Console::info("Mode: Full-Stack (PHP + React)");
    Console::info("Frontend detected - starting with Vite...");
}

// Start Vite only if React mode
$viteProcess = null;
if ($mode === 'full-stack') {
    // Check if npm is installed
    exec('npm --version 2>&1', $output, $return);
    if ($return !== 0) {
        Console::error("npm not found. Install Node.js first.");
        Console::info("Download from: https://nodejs.org/");
        Console::warn("Or run in templates-only mode (delete frontend/ folder)");
        exit(1);
    }
    
    // Check if node_modules exists
    if (!file_exists(FRONTEND_DIR . '/node_modules')) {
        Console::warn("Dependencies not installed. Running npm install...");
        Console::info("This may take a few minutes...");
        chdir(FRONTEND_DIR);
        passthru('npm install');
        chdir(__DIR__);
        Console::success("Dependencies installed!");
    }
    
    // Start Vite in background
    Console::info("Starting Vite dev server...");
    $viteProcess = startVite();
    
    // Wait for Vite to start
    Console::info("Waiting for Vite to start...");
    $maxAttempts = 30;
    $attempt = 0;
    while ($attempt < $maxAttempts) {
        if (@fsockopen('localhost', VITE_PORT, $errno, $errstr, 0.1)) {
            Console::success("Vite running on http://localhost:" . VITE_PORT);
            break;
        }
        usleep(200000); // 200ms
        $attempt++;
    }
    
    if ($attempt === $maxAttempts) {
        Console::error("Vite failed to start");
        Console::info("Try running manually: cd frontend && npm run dev");
        if ($viteProcess) proc_terminate($viteProcess);
        exit(1);
    }
}

// Register shutdown function to kill Vite
register_shutdown_function(function() use ($viteProcess) {
    if ($viteProcess && is_resource($viteProcess)) {
        Console::warn("\nShutting down Vite...");
        proc_terminate($viteProcess);
        proc_close($viteProcess);
    }
});

// Handle Ctrl+C gracefully
if (function_exists('pcntl_signal')) {
    pcntl_signal(SIGINT, function() use ($viteProcess) {
        Console::warn("\n\nShutting down...");
        if ($viteProcess && is_resource($viteProcess)) {
            proc_terminate($viteProcess);
            proc_close($viteProcess);
        }
        exit(0);
    });
}

// Display info
Console::header("\n╔══════════════════════════════════════╗");
Console::success("  Server: http://localhost:" . SERVER_PORT);
if ($mode === 'full-stack') {
    Console::success("  Vite:  (proxied)");
    Console::success("  HMR:    ✓ Enabled");
} else {
    Console::success("  Mode:   Templates Only");
    Console::info("Tip:  Run 'php yafs add react' \n\t\t to add React");
}
Console::header("╚══════════════════════════════════════╝\n");
Console::info("Press Ctrl+C to stop\n");

// Set environment variables for router
$_ENV['YAFS_MODE'] = $mode;
$_ENV['VITE_PORT'] = VITE_PORT;

// Start PHP built-in server
$router = __DIR__ . '/router.php';
if (!file_exists($router)) {
    Console::error("router.php not found!");
    exit(1);
}

$command = sprintf(
    'php -S localhost:%d -t %s %s 2>&1',
    SERVER_PORT,
    escapeshellarg(__DIR__ . '/public'),
    escapeshellarg($router)
);

passthru($command);

/**
 * Start Vite dev server in background
 */
function startVite() {
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ];
    
    if (PHP_OS_FAMILY === 'Windows') {
        $command = 'cd /d ' . escapeshellarg(FRONTEND_DIR) . ' && npm run dev';
        $process = proc_open($command, $descriptors, $pipes, FRONTEND_DIR);
    } else {
        $command = 'cd ' . escapeshellarg(FRONTEND_DIR) . ' && npm run dev > /dev/null 2>&1 &';
        $process = proc_open($command, $descriptors, $pipes, FRONTEND_DIR);
    }
    
    if (!is_resource($process)) {
        Console::error("Failed to start Vite process");
        exit(1);
    }
    
    return $process;
}