<?php

namespace YAFS\View;

/**
 * Simple PHP template engine.
 * 
 * Renders PHP templates with data binding. Templates are plain PHP files
 * that have access to passed variables.
 * 
 * Example:
 *   View::render('home', ['title' => 'Welcome', 'user' => $user]);
 */
class View
{
  /**
   * Base directory for view files.
   */
  protected static string $viewsPath = __DIR__ . '/../../views';

  /**
   * Shared data available to all views.
   */
  protected static array $shared = [];

  /**
   * Set the base views directory.
   * 
   * @param string $path Absolute path to views directory
   */
  public static function setViewsPath(string $path): void
  {
    self::$viewsPath = rtrim($path, '/');
  }

  /**
   * Get the current views path.
   * 
   * @return string
   */
  public static function getViewsPath(): string
  {
    return self::$viewsPath;
  }

  /**
   * Share data with all views.
   * 
   * Useful for making data available globally (e.g., current user, site settings).
   * 
   * @param string|array $key Key or array of key-value pairs
   * @param mixed $value Value (if $key is string)
   */
  public static function share($key, $value = null): void
  {
    if (is_array($key)) {
      self::$shared = array_merge(self::$shared, $key);
    } else {
      self::$shared[$key] = $value;
    }
  }

  /**
   * Get shared data.
   * 
   * @return array
   */
  public static function getShared(): array
  {
    return self::$shared;
  }

  /**
   * Render a view template.
   * 
   * @param string $view View name (e.g., 'home' or 'users/profile')
   * @param array $data Data to pass to view
   * @return string Rendered HTML
   * @throws \RuntimeException If view file not found
   */
  public static function render(string $view, array $data = []): string
  {
    $viewPath = self::findView($view);

    if (!file_exists($viewPath)) {
      throw new \RuntimeException("View not found: {$view} (searched: {$viewPath})");
    }

    // Merge view data with shared data (view data takes precedence)
    $data = array_merge(self::$shared, $data);

    // Render the view
    return self::renderFile($viewPath, $data);
  }

  /**
   * Check if a view exists.
   * 
   * @param string $view View name
   * @return bool
   */
  public static function exists(string $view): bool
  {
    $viewPath = self::findView($view);
    return file_exists($viewPath);
  }

  /**
   * Find the full path to a view file.
   * 
   * Supports dot notation: 'users.profile' becomes 'users/profile.php'
   * Also supports slash notation: 'users/profile'
   * 
   * @param string $view View name
   * @return string Full path to view file
   */
  protected static function findView(string $view): string
  {
    // Convert dot notation to directory separator
    $view = str_replace('.', '/', $view);

    // Remove .php extension if provided
    $view = preg_replace('/\.php$/', '', $view);

    return self::$viewsPath . '/' . $view . '.php';
  }

  /**
   * Render a file with given data.
   * 
   * Uses output buffering to capture the rendered content.
   * Variables are extracted into the local scope for use in templates.
   * 
   * @param string $__path Path to template file
   * @param array $__data Data to extract
   * @return string Rendered content
   */
  protected static function renderFile(string $__path, array $__data): string
  {
    // Extract data into local scope
    // Using $__data and $__path to avoid conflicts with extracted variables
    extract($__data, EXTR_SKIP);

    // Start output buffering
    ob_start();

    try {
      // Include the view file
      // Variables from $__data are now available as local variables
      include $__path;

      // Get the buffered content
      return ob_get_clean();
    } catch (\Throwable $e) {
      // Clean buffer on error
      ob_end_clean();
      throw $e;
    }
  }

  /**
   * Create a new View instance for method chaining.
   * 
   * This allows for more object-oriented usage if preferred:
   *   $view = View::make('home')->with('title', 'Welcome')->render();
   * 
   * @param string $view View name
   * @param array $data Initial data
   * @return ViewInstance
   */
  public static function make(string $view, array $data = []): ViewInstance
  {
    return new ViewInstance($view, $data);
  }
}

/**
 * View instance for method chaining.
 * 
 * Allows fluent interface for building views:
 *   View::make('home')
 *       ->with('title', 'Welcome')
 *       ->with('user', $user)
 *       ->render();
 */
class ViewInstance
{
  /**
   * View name.
   */
  protected string $view;

  /**
   * View data.
   */
  protected array $data;

  /**
   * Create view instance.
   * 
   * @param string $view View name
   * @param array $data Initial data
   */
  public function __construct(string $view, array $data = [])
  {
    $this->view = $view;
    $this->data = $data;
  }

  /**
   * Add data to the view.
   * 
   * @param string|array $key Key or array of data
   * @param mixed $value Value (if $key is string)
   * @return self
   */
  public function with($key, $value = null): self
  {
    if (is_array($key)) {
      $this->data = array_merge($this->data, $key);
    } else {
      $this->data[$key] = $value;
    }

    return $this;
  }

  /**
   * Render the view.
   * 
   * @return string Rendered HTML
   */
  public function render(): string
  {
    return View::render($this->view, $this->data);
  }

  /**
   * Convert to string (renders the view).
   * 
   * @return string
   */
  public function __toString(): string
  {
    try {
      return $this->render();
    } catch (\Throwable $e) {
      // Can't throw exceptions from __toString
      return "Error rendering view: {$e->getMessage()}";
    }
  }
}
