<?php

namespace YAFS\View;

/**
 * Asset manager for loading CSS and JavaScript files with Vite integration.
 * 
 * This class seamlessly handles both development and production environments:
 * 
 * **Development Mode:**
 * - Loads assets from Vite dev server (localhost:5173)
 * - Enables Hot Module Replacement (HMR)
 * - Injects React Fast Refresh for instant component updates
 * - No build step required
 * 
 * **Production Mode:**
 * - Loads pre-built, optimized assets from manifest.json
 * - Uses content-hashed filenames for cache busting
 * - Supports code splitting and lazy loading
 * - Minimal file sizes with tree shaking
 * 
 * **Basic Usage:**
 * ```
 * // In your view:
 * <?php use YAFS\View\AssetManager; ?>
 * 
 * <!-- Load React with automatic dev/prod detection -->
 * <?= AssetManager::react('src/main.jsx') ?>
 * 
 * <!-- Or load individual assets -->
 * <?= AssetManager::viteClient() ?>
 * <?= AssetManager::reactRefresh() ?>
 * <?= AssetManager::js('src/main.jsx') ?>
 * <?= AssetManager::entryCss('src/main.jsx') ?>
 * ```
 * 
 * **Configuration:**
 * ```
 * // Explicitly set mode
 * AssetManager::setMode('dev'); // or 'prod'
 * 
 * // Custom Vite dev server URL
 * AssetManager::setViteUrl('http://localhost:3000');
 * 
 * // Custom manifest path
 * AssetManager::setManifestPath('/path/to/manifest.json');
 * ```
 * 
 * @author YAFS Framework
 * @package YAFS\View
 * @version 1.0.0
 */
class AssetManager
{
	/**
	 * Environment mode: 'dev' for development, 'prod' for production.
	 */
	protected static ?string $mode = null;

	/**
	 * Vite development server URL.
	 */
	protected static string $viteDevServerUrl = 'http://localhost:5173';

	/**
	 * Public assets directory (relative to web root).
	 */
	protected static string $publicPath = '/assets';

	/**
	 * Build output directory (relative to public path).
	 */
	protected static string $buildPath = '/build';

	/**
	 * Cached manifest data from manifest.json.
	 */
	protected static ?array $manifest = null;

	/**
	 * Custom manifest file path.
	 */
	protected static ?string $manifestPath = null;

	/**
	 * Set the environment mode explicitly.
	 * 
	 * @param string $mode Either 'dev' or 'prod'
	 * @return void
	 */
	public static function setMode(string $mode): void
	{
		self::$mode = $mode;
	}

	/**
	 * Get the current environment mode.
	 * 
	 * @return string Either 'dev' or 'prod'
	 */
	public static function getMode(): string
	{
		if (self::$mode !== null) {
			return self::$mode;
		}

		// Check environment variable FIRST
		$appEnv = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? null);

		if ($appEnv === 'production' || $appEnv === 'prod') {
			return 'prod';
		}

		// Check if Vite dev server is running
		if (self::isViteServerRunning()) {
			return 'dev';
		}

		// Default to production
		return 'prod';
	}

	/**
	 * Set the Vite development server URL.
	 * 
	 * @param string $url Full server URL (e.g., 'http://localhost:5173')
	 * @return void
	 */
	public static function setViteUrl(string $url): void
	{
		self::$viteDevServerUrl = rtrim($url, '/');
	}

	/**
	 * Alias for setViteUrl() for backward compatibility.
	 * 
	 * @param string $url Full server URL
	 * @return void
	 */
	public static function setViteDevServer(string $url): void
	{
		self::setViteUrl($url);
	}

	/**
	 * Set custom manifest file path.
	 * 
	 * @param string $path Absolute path to manifest.json
	 * @return void
	 */
	public static function setManifestPath(string $path): void
	{
		self::$manifestPath = $path;
	}

	/**
	 * Set the public assets directory path.
	 * 
	 * @param string $path Path relative to web root (e.g., '/assets' or '/dist')
	 * @return void
	 */
	public static function setPublicPath(string $path): void
	{
		self::$publicPath = '/' . trim($path, '/');
	}

	/**
	 * Set the build output directory path.
	 * 
	 * @param string $path Path relative to public path (e.g., '/build' or '/dist')
	 * @return void
	 */
	public static function setBuildPath(string $path): void
	{
		self::$buildPath = '/' . trim($path, '/');
	}

	/**
	 * Load a JavaScript file.
	 * 
	 * @param string $file File path (e.g., 'src/main.jsx')
	 * @return string HTML script tag
	 */
	public static function js(string $file): string
	{
		if (self::getMode() === 'dev') {
			return self::viteJs($file);
		}

		return self::productionJs($file);
	}

	/**
	 * Load a CSS file.
	 * 
	 * @param string $file File path (e.g., 'app.css')
	 * @return string HTML link tag or empty string
	 */
	public static function css(string $file): string
	{
		if (self::getMode() === 'dev') {
			return self::viteCss($file);
		}

		return self::productionCss($file);
	}

	/**
	 * Get Vite HMR client script.
	 * 
	 * @return string HTML script tag for Vite client
	 */
	public static function viteClient(): string
	{
		if (self::getMode() !== 'dev') {
			return '';
		}

		return sprintf(
			'<script type="module" src="%s/@vite/client"></script>',
			self::$viteDevServerUrl
		);
	}

	/**
	 * Get React Fast Refresh preamble script.
	 * 
	 * @return string HTML script tag with React Refresh runtime
	 */
	public static function reactRefresh(): string
	{
		if (self::getMode() !== 'dev') {
			return '';
		}

		return sprintf(
			'<script type="module">
                import RefreshRuntime from "%s/@react-refresh"
                RefreshRuntime.injectIntoGlobalHook(window)
                window.$RefreshReg$ = () => {}
                window.$RefreshSig$ = () => (type) => type
                window.__vite_plugin_react_preamble_installed__ = true
            </script>',
			self::$viteDevServerUrl
		);
	}

	/**
	 * Generate React asset tags (auto-detects dev/prod).
	 * 
	 * This is the main method to use in views for React apps.
	 * It automatically handles all the complexity of dev vs prod modes.
	 * 
	 * @param string $entry Entry point file (e.g., 'src/main.jsx')
	 * @return string Complete HTML tags for React
	 */
	public static function react(string $entry = 'src/main.jsx'): string
	{
		if (self::getMode() === 'dev') {
			$viteUrl = self::$viteDevServerUrl;

			return <<<HTML
        <script type="module">
            import RefreshRuntime from "{$viteUrl}/@react-refresh"
            RefreshRuntime.injectIntoGlobalHook(window)
            window.\$RefreshReg$ = () => {}
            window.\$RefreshSig$ = () => (type) => type
            window.__vite_plugin_react_preamble_installed__ = true
        </script>
        <script type="module" src="{$viteUrl}/@vite/client"></script>
        <script type="module" src="{$viteUrl}/{$entry}"></script>
        HTML;
		}

		// Production mode - use manifest
		return self::reactProd($entry);
	}

	/**
	 * Generate production React asset tags.
	 * 
	 * Internal method used by react() in production mode.
	 * 
	 * @param string $entry Entry point file
	 * @return string HTML tags for production assets
	 */
	protected static function reactProd(string $entry): string
	{
		$manifest = self::getManifest();

		if (!$manifest) {
			return '<!-- No manifest found -->';
		}

		// Try to find entry in manifest
		$entryData = null;

		// Try exact match
		if (isset($manifest[$entry])) {
			$entryData = $manifest[$entry];
		}
		// Try without src/
		elseif (isset($manifest[ltrim($entry, 'src/')])) {
			$entryData = $manifest[ltrim($entry, 'src/')];
		}
		// Default to index.html
		elseif (isset($manifest['index.html'])) {
			$entryData = $manifest['index.html'];
		}
		// Use first entry
		else {
			$entryData = reset($manifest);
		}

		if (!$entryData) {
			return '<!-- No entry data found -->';
		}

		$html = '';

		// Add CSS
		if (isset($entryData['css']) && is_array($entryData['css'])) {
			foreach ($entryData['css'] as $css) {
				$html .= sprintf(
					'<link rel="stylesheet" href="%s">',
					self::$publicPath . self::$buildPath . '/' . $css
				) . "\n";
			}
		}

		// Add JS
		if (isset($entryData['file'])) {
			$html .= sprintf(
				'<script type="module" src="%s"></script>',
				self::$publicPath . self::$buildPath . '/' . $entryData['file']
			);
		}

		return $html;
	}

	/**
	 * Load JavaScript from Vite dev server.
	 * 
	 * @param string $file File path
	 * @return string HTML script tag
	 */
	protected static function viteJs(string $file): string
	{
		$url = self::$viteDevServerUrl . '/' . ltrim($file, '/');
		return sprintf('<script type="module" src="%s"></script>', $url);
	}

	/**
	 * Handle CSS in Vite dev mode.
	 * 
	 * @param string $file File path
	 * @return string Empty string (CSS handled by Vite)
	 */
	protected static function viteCss(string $file): string
	{
		return '';
	}

	/**
	 * Load JavaScript from production build.
	 * 
	 * @param string $file Source file path
	 * @return string HTML script tag
	 */
	protected static function productionJs(string $file): string
	{
		$assetPath = self::getAssetPath($file);

		if (!$assetPath) {
			$assetPath = self::$publicPath . self::$buildPath . '/' . $file;
		}

		return sprintf('<script type="module" src="%s"></script>', $assetPath);
	}

	/**
	 * Load CSS from production build.
	 * 
	 * @param string $file Source file path
	 * @return string HTML link tag
	 */
	protected static function productionCss(string $file): string
	{
		$assetPath = self::getAssetPath($file);

		if (!$assetPath) {
			$assetPath = self::$publicPath . self::$buildPath . '/' . $file;
		}

		return sprintf('<link rel="stylesheet" href="%s">', $assetPath);
	}

	/**
	 * Get production asset path from Vite manifest.
	 * 
	 * @param string $file Source file name
	 * @return string|null Full asset path or null
	 */
	protected static function getAssetPath(string $file): ?string
	{
		$manifest = self::getManifest();

		if (!$manifest) {
			return null;
		}

		$file = ltrim($file, '/');

		if (!isset($manifest[$file])) {
			return null;
		}

		$entry = $manifest[$file];
		$outputFile = $entry['file'] ?? null;

		if (!$outputFile) {
			return null;
		}

		return self::$publicPath . self::$buildPath . '/' . $outputFile;
	}

	/**
	 * Load and cache the Vite manifest file.
	 * 
	 * @return array|null Manifest data or null
	 */
	protected static function getManifest(): ?array
	{
		if (self::$manifest !== null) {
			return self::$manifest;
		}

		if (self::$manifestPath && file_exists(self::$manifestPath)) {
			$manifestPath = self::$manifestPath;
		} else {
			// FIXED: Account for router running from project root
			$manifestPath = __DIR__ . '/../../public' . self::$publicPath . self::$buildPath . '/.vite/manifest.json';
		}

		// DEBUG
		error_log("Looking for manifest at: " . $manifestPath);
		error_log("File exists: " . (file_exists($manifestPath) ? 'YES' : 'NO'));

		if (!file_exists($manifestPath)) {
			return null;
		}

		$content = file_get_contents($manifestPath);
		self::$manifest = json_decode($content, true);

		return self::$manifest;
	}

	/**
	 * Check if Vite development server is running.
	 * 
	 * @return bool True if server is accessible
	 */
	public static function isViteServerRunning(): bool
	{
		$url = self::$viteDevServerUrl;
		$parts = parse_url($url);
		$host = $parts['host'] ?? 'localhost';
		$port = $parts['port'] ?? 5173;

		$connection = @fsockopen($host, $port, $errno, $errstr, 0.1);

		if ($connection) {
			fclose($connection);
			return true;
		}

		return false;
	}

	/**
	 * Get all CSS files for an entry point.
	 * 
	 * @param string $entry Entry point file
	 * @return array Array of CSS file paths
	 */
	public static function getEntryCss(string $entry): array
	{
		if (self::getMode() === 'dev') {
			return [];
		}

		$manifest = self::getManifest();
		if (!$manifest) {
			return [];
		}

		$entry = ltrim($entry, '/');

		if (!isset($manifest[$entry])) {
			return [];
		}

		$entryData = $manifest[$entry];
		$cssFiles = $entryData['css'] ?? [];

		$paths = [];
		foreach ($cssFiles as $cssFile) {
			$paths[] = self::$publicPath . self::$buildPath . '/' . $cssFile;
		}

		return $paths;
	}

	/**
	 * Render all CSS link tags for an entry point.
	 * 
	 * @param string $entry Entry point file
	 * @return string HTML link tags or empty string
	 */
	public static function entryCss(string $entry): string
	{
		$cssFiles = self::getEntryCss($entry);

		$tags = [];
		foreach ($cssFiles as $path) {
			$tags[] = sprintf('<link rel="stylesheet" href="%s">', $path);
		}

		return implode("\n", $tags);
	}
}
