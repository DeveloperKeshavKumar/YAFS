<?php

require_once __DIR__ . '/../autoload.php';

use YAFS\Testing\TestRunner;
use YAFS\Testing\Assert;
use YAFS\View\View;
use YAFS\View\AssetManager;

$runner = new TestRunner();

// Setup: Create temporary test views
$testViewsPath = __DIR__ . '/../views_test';
mkdir($testViewsPath, 0777, true);
mkdir($testViewsPath . '/components', 0777, true);
View::setViewsPath($testViewsPath);

// Create test view file
file_put_contents($testViewsPath . '/test.php', '<h1><?= $title ?></h1>');
file_put_contents($testViewsPath . '/user.php', '<p>Hello, <?= $name ?>!</p>');
file_put_contents($testViewsPath . '/components/button.php', '<button><?= $label ?></button>');

// =========================
// VIEW TESTS
// =========================

$runner->test('View: Render simple template', function () use ($testViewsPath) {
  $html = View::render('test', ['title' => 'Welcome']);

  Assert::assertStringContains('<h1>Welcome</h1>', $html);
});

$runner->test('View: Pass data to template', function () {
  $html = View::render('user', ['name' => 'John']);

  Assert::assertStringContains('Hello, John!', $html);
});

$runner->test('View: Render with dot notation', function () use ($testViewsPath) {
  $html = View::render('components.button', ['label' => 'Click Me']);

  Assert::assertStringContains('<button>Click Me</button>', $html);
});

$runner->test('View: Check if view exists', function () {
  Assert::assertTrue(View::exists('test'));
  Assert::assertFalse(View::exists('nonexistent'));
});

$runner->test('View: Throw exception for missing view', function () {
  $exceptionThrown = false;

  try {
    View::render('nonexistent', []);
  } catch (\RuntimeException $e) {
    $exceptionThrown = true;
    Assert::assertStringContains('View not found', $e->getMessage());
  }

  Assert::assertTrue($exceptionThrown, 'Expected RuntimeException for missing view');
});

$runner->test('View: Share data globally', function () {
  View::share('app_name', 'YAFS');
  View::share('version', 'v0.3.0');

  $shared = View::getShared();
  Assert::assertEquals('YAFS', $shared['app_name']);
  Assert::assertEquals('v0.3.0', $shared['version']);
});

$runner->test('View: Shared data available in templates', function () use ($testViewsPath) {
  View::share('site_title', 'My Site');

  file_put_contents($testViewsPath . '/shared_test.php', '<title><?= $site_title ?></title>');

  $html = View::render('shared_test', []);
  Assert::assertStringContains('<title>My Site</title>', $html);
});

$runner->test('View: View data overrides shared data', function () use ($testViewsPath) {
  View::share('message', 'Shared Message');

  file_put_contents($testViewsPath . '/override_test.php', '<?= $message ?>');

  $html = View::render('override_test', ['message' => 'View Message']);
  Assert::assertEquals('View Message', $html);
});

$runner->test('View: Fluent interface with make()', function () {
  $view = View::make('user', ['name' => 'Jane']);
  $html = $view->render();

  Assert::assertStringContains('Hello, Jane!', $html);
});

$runner->test('View: Fluent interface with with()', function () {
  $html = View::make('user')
    ->with('name', 'Bob')
    ->render();

  Assert::assertStringContains('Hello, Bob!', $html);
});

$runner->test('View: Fluent interface with multiple with()', function () use ($testViewsPath) {
  file_put_contents($testViewsPath . '/multi.php', '<?= $first ?> <?= $last ?>');

  $html = View::make('multi')
    ->with('first', 'John')
    ->with('last', 'Doe')
    ->render();

  Assert::assertStringContains('John Doe', $html);
});

$runner->test('View: Fluent interface with array', function () use ($testViewsPath) {
  file_put_contents($testViewsPath . '/array.php', '<?= $a ?> <?= $b ?>');

  $html = View::make('array')
    ->with(['a' => '1', 'b' => '2'])
    ->render();

  Assert::assertStringContains('1 2', $html);
});

$runner->test('View: Set custom views path', function () use ($testViewsPath) {
  $customPath = __DIR__ . '/../custom_views';
  View::setViewsPath($customPath);

  Assert::assertEquals($customPath, View::getViewsPath());

  View::setViewsPath($testViewsPath);
});

// =========================
// ASSET MANAGER TESTS
// =========================

$runner->test('AssetManager: Set mode', function () {
  AssetManager::setMode('dev');
  Assert::assertEquals('dev', AssetManager::getMode());

  AssetManager::setMode('prod');
  Assert::assertEquals('prod', AssetManager::getMode());
});

$runner->test('AssetManager: Dev mode JS', function () {
  AssetManager::setMode('dev');
  $tag = AssetManager::js('src/main.jsx');

  Assert::assertStringContains('http://localhost:5173/src/main.jsx', $tag);
  Assert::assertStringContains('<script type="module"', $tag);
});

$runner->test('AssetManager: Vite client script', function () {
  AssetManager::setMode('dev');
  $tag = AssetManager::viteClient();

  Assert::assertStringContains('@vite/client', $tag);
  Assert::assertStringContains('http://localhost:5173', $tag);
});

$runner->test('AssetManager: No Vite client in prod', function () {
  AssetManager::setMode('prod');
  $tag = AssetManager::viteClient();

  Assert::assertEquals('', $tag);
});

$runner->test('AssetManager: Prod mode JS', function () {
  AssetManager::setMode('prod');
  $tag = AssetManager::js('app.js');

  Assert::assertStringContains('<script type="module"', $tag);
  Assert::assertStringContains('/assets/build/app.js', $tag);
});

$runner->test('AssetManager: Prod mode CSS', function () {
  AssetManager::setMode('prod');
  $tag = AssetManager::css('app.css');

  Assert::assertStringContains('<link rel="stylesheet"', $tag);
  Assert::assertStringContains('/assets/build/app.css', $tag);
});

$runner->test('AssetManager: Set Vite dev server URL', function () {
  AssetManager::setMode('dev');
  AssetManager::setViteDevServer('http://localhost:3000');

  $tag = AssetManager::js('main.js');
  Assert::assertStringContains('http://localhost:3000/main.js', $tag);

  // Reset
  AssetManager::setViteDevServer('http://localhost:5173');
});

$runner->test('AssetManager: Set public path', function () {
  AssetManager::setMode('prod');
  AssetManager::setPublicPath('/static');

  $tag = AssetManager::js('app.js');
  Assert::assertStringContains('/static/build/app.js', $tag);

  // Reset
  AssetManager::setPublicPath('/assets');
});

// Cleanup
$runner->run();

// Remove test views
function deleteDirectory($dir)
{
  if (!file_exists($dir)) return;

  $files = array_diff(scandir($dir), ['.', '..']);
  foreach ($files as $file) {
    $path = $dir . '/' . $file;
    is_dir($path) ? deleteDirectory($path) : unlink($path);
  }
  rmdir($dir);
}

deleteDirectory($testViewsPath);
