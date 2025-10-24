# YAFS View Layer Documentation

The YAFS View Layer provides a simple yet powerful system for rendering PHP templates and integrating with modern frontend frameworks like React. It supports both traditional server-side rendering and modern single-page applications with seamless PHP-to-frontend data passing.

## Table of Contents

- [Quick Start](#quick-start)
- [PHP Templates](#php-templates)
- [React Integration](#react-integration)
- [Asset Management](#asset-management)
- [Data Passing](#data-passing)
- [Production Deployment](#production-deployment)
- [Best Practices](#best-practices)

## Quick Start

### Basic Template Rendering

```php
<?php

use YAFS\Application;

$app = new Application();

// Render a view
$app->get('/', function($req, $res) {
    return $res->view('welcome', [
        'title' => 'Welcome to YAFS',
        'user' => ['name' => 'John']
    ]);
});

$app->run();
```

### React Application

```php
<?php

use YAFS\Application;

$app = new Application();

// Render React app with props
$app->get('/', function($req, $res) {
    return $res->view('react', [
        'title' => 'My App',
        'props' => [
            'user' => ['name' => 'John'],
            'apiUrl' => '/api'
        ]
    ]);
});

$app->run();
```

## PHP Templates

### Creating Templates

Templates are stored in the `views/` directory:

```php
// views/welcome.php
<?php
$title = $title ?? 'My App';
$message = $message ?? 'Welcome!';

$content = <<<HTML
<div style="max-width: 800px; margin: 0 auto; padding: 2rem;">
    <h1>{$title}</h1>
    <p>{$message}</p>
</div>
HTML;

require __DIR__ . '/layouts/app.php';
```

### Layout System

Create reusable layouts:

```php
// views/layouts/app.php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'YAFS App') ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?= $content ?? '' ?>
    <script src="/assets/js/app.js"></script>
</body>
</html>
```

### Passing Data to Views

```php
// Simple data passing
$app->get('/profile', function($req, $res) {
    return $res->view('profile', [
        'user' => [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'avatar' => '/images/avatar.jpg'
        ]
    ]);
});

// Using database
$app->get('/posts', function($req, $res) {
    $posts = DB::table('posts')
        ->where('status', 'published')
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
    
    return $res->view('posts', [
        'title' => 'Latest Posts',
        'posts' => $posts
    ]);
});
```

### Escaping Output (XSS Prevention)

Always escape user-generated content:

```php
// views/post.php
<h1><?= htmlspecialchars($post['title']) ?></h1>
<div class="author">
    By <?= htmlspecialchars($post['author']) ?>
</div>
<div class="content">
    <?= nl2br(htmlspecialchars($post['content'])) ?>
</div>

// For trusted HTML (admin content)
<div class="content">
    <?= $trustedHtml ?>
</div>
```

### Shared Data

Share data across all views:

```php
<?php

use YAFS\View\View;

// Share global data
View::share('app_name', 'My Application');
View::share('version', '1.0.0');
View::share('current_user', $_SESSION['user'] ?? null);

// Now available in all views
// views/header.php
<header>
    <h1><?= $app_name ?></h1>
    <?php if ($current_user): ?>
        <p>Welcome, <?= htmlspecialchars($current_user['name']) ?></p>
    <?php endif; ?>
</header>
```

## React Integration

### Setup React Project

YAFS CLI automatically sets up React with Vite:

```bash
# Create new project with React
php yafs init react

# Or add React to existing project
php yafs add react
```

### React View Template

Create a React view template:

```php
// views/react.php
<?php use YAFS\View\AssetManager; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'YAFS React App') ?></title>
    <?= AssetManager::react('src/main.jsx') ?>
</head>
<body>
    <div id="root"></div>
    <?php if (isset($props)): ?>
        <script>
            window.__YAFS_PROPS__ = <?= json_encode($props, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
        </script>
    <?php endif; ?>
</body>
</html>
```

### Passing Props to React

Pass data from PHP to React:

```php
// routes/web.php
$app->get('/', function($req, $res) {
    // Fetch data from database
    $user = DB::table('users')
        ->where('id', $_SESSION['user_id'])
        ->first();
    
    return $res->view('react', [
        'title' => 'Dashboard',
        'props' => [
            'user' => $user,
            'apiUrl' => '/api',
            'config' => [
                'appName' => 'My App',
                'environment' => $_ENV['APP_ENV'] ?? 'production'
            ]
        ]
    ]);
});
```

### Receiving Props in React

Access PHP data in your React components:

```jsx
// frontend/src/App.jsx
import { useEffect, useState } from 'react'

function App({ user, apiUrl, config }) {
  const [data, setData] = useState(null)
  
  useEffect(() => {
    // Use props from PHP
    fetch(`${apiUrl}/dashboard`)
      .then(res => res.json())
      .then(setData)
  }, [apiUrl])
  
  return (
    <div>
      <h1>Welcome, {user.name}!</h1>
      <p>App: {config.appName}</p>
      {data && <pre>{JSON.stringify(data, null, 2)}</pre>}
    </div>
  )
}

export default App
```

```jsx
// frontend/src/main.jsx
import React from 'react'
import ReactDOM from 'react-dom/client'
import App from './App'
import './index.css'

// Get props from PHP
const props = window.__YAFS_PROPS__ || {}

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <App {...props} />
  </React.StrictMode>
)
```

### API Routes with React

Combine React frontend with PHP API:

```php
// routes/web.php

// React app route
$app->get('/', function($req, $res) {
    return $res->view('react', [
        'title' => 'My App',
        'props' => ['apiUrl' => '/api']
    ]);
});

// API routes
$app->group(['prefix' => '/api'], function($app) {
    
    $app->get('/users', function($req, $res) {
        $users = DB::table('users')
            ->select('id', 'name', 'email')
            ->get();
        return $res->json($users);
    });
    
    $app->get('/posts', function($req, $res) {
        $posts = DB::table('posts')
            ->where('status', 'published')
            ->orderBy('created_at', 'desc')
            ->get();
        return $res->json($posts);
    });
    
    $app->post('/posts', function($req, $res) {
        $data = $req->json();
        $id = DB::table('posts')->insertGetId($data);
        return $res->status(201)->json(['id' => $id]);
    });
});
```

## Asset Management

### Development Mode

In development, Vite serves assets with Hot Module Replacement (HMR):

```php
<?php

use YAFS\View\AssetManager;

// Set development mode (automatic in dev server)
AssetManager::setMode('dev');
AssetManager::setViteDevServer('http://localhost:5173');

// Include React entry point
// Automatically loads from Vite dev server
<?= AssetManager::react('src/main.jsx') ?>
```

### Production Mode

In production, assets are built and served from manifest:

```bash
# Build assets for production
cd frontend
npm run build
```

```php
<?php

use YAFS\View\AssetManager;

// Set production mode
AssetManager::setMode('production');

// Loads built assets with content hashing
<?= AssetManager::react('src/main.jsx') ?>

// Output:
// <script type="module" src="/assets/build/assets/main-abc123.js"></script>
// <link rel="stylesheet" href="/assets/build/assets/main-def456.css">
```

### Static Assets

Include regular CSS/JS files:

```php
// views/layouts/app.php
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/components.css">
</head>
<body>
    <?= $content ?>
    <script src="/assets/js/app.js"></script>
    <script src="/assets/js/utilities.js"></script>
</body>
</html>
```

### Asset Helpers

```php
// Helper function for versioned assets
function asset(string $path): string {
    $version = $_ENV['ASSET_VERSION'] ?? '1.0';
    return "{$path}?v={$version}";
}

// Usage in views
<link rel="stylesheet" href="<?= asset('/assets/css/style.css') ?>">
<img src="<?= asset('/images/logo.png') ?>" alt="Logo">
```

## Data Passing

### View Data

Pass data to PHP templates:

```php
$app->get('/dashboard', function($req, $res) {
    return $res->view('dashboard', [
        'pageTitle' => 'Dashboard',
        'stats' => [
            'users' => 1250,
            'posts' => 890,
            'comments' => 4320
        ],
        'recentActivity' => DB::table('activity')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
    ]);
});
```

### React Props

Pass data to React components:

```php
$app->get('/app', function($req, $res) {
    return $res->view('react', [
        'title' => 'Application',
        'props' => [
            // User data
            'user' => [
                'id' => 123,
                'name' => 'John Doe',
                'role' => 'admin'
            ],
            
            // Configuration
            'config' => [
                'apiUrl' => '/api',
                'wsUrl' => 'ws://localhost:6001',
                'features' => ['comments', 'likes', 'shares']
            ],
            
            // Initial data
            'initialData' => [
                'posts' => DB::table('posts')->limit(10)->get(),
                'notifications' => DB::table('notifications')
                    ->where('user_id', 123)
                    ->where('read', false)
                    ->get()
            ]
        ]
    ]);
});
```

### Complex Data Types

Handle different data types:

```php
$app->get('/complex', function($req, $res) {
    return $res->view('react', [
        'props' => [
            // Arrays
            'items' => [1, 2, 3, 4, 5],
            
            // Nested objects
            'user' => [
                'profile' => [
                    'name' => 'John',
                    'settings' => [
                        'theme' => 'dark',
                        'notifications' => true
                    ]
                ]
            ],
            
            // Dates (convert to ISO string)
            'createdAt' => date('c'),
            
            // Booleans
            'isAuthenticated' => true,
            
            // Null values
            'optionalField' => null,
            
            // Numbers
            'count' => 42,
            'price' => 19.99
        ]
    ]);
});
```

## Production Deployment

### Building Assets

Build optimized assets for production:

```bash
# Navigate to frontend directory
cd frontend

# Install dependencies (if needed)
npm install

# Build for production
npm run build

# Output will be in public/assets/build/
```

### Environment Configuration

```php
// public/index.php
<?php

require_once __DIR__ . '/../autoload.php';

use YAFS\Application;
use YAFS\View\View;
use YAFS\View\AssetManager;

// Set production mode
AssetManager::setMode('production');

// Set views path
View::setViewsPath(__DIR__ . '/../views');

$app = new Application();
$app->setDebug(false); // Disable debug mode

// Load routes
require_once __DIR__ . '/../routes/web.php';

$app->run();
```

### Vite Configuration

Ensure Vite is configured correctly:

```js
// frontend/vite.config.js
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  base: process.env.YAFS_ENV === 'production' ? '/assets/build/' : '/',
  server: {
    port: 5173,
    host: true,
    cors: true,
    origin: 'http://localhost:5173',
  },
  build: {
    outDir: '../public/assets/build',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: './index.html',
    },
  },
})
```

### Server Configuration

#### Apache (.htaccess)

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # Serve existing files directly
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    
    # Route everything else to index.php
    RewriteRule ^ index.php [L]
</IfModule>

# Cache static assets
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/html/public;
    
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

## Best Practices

### 1. Always Escape User Input

```php
// ✓ Good - escaped output
<h1><?= htmlspecialchars($userInput) ?></h1>
<p><?= nl2br(htmlspecialchars($userContent)) ?></p>

// ✗ Bad - unescaped (XSS vulnerability)
<h1><?= $userInput ?></h1>
```

### 2. Use Layouts for Consistency

```php
// ✓ Good - reusable layout
// views/page.php
<?php
$content = '<div>Page content</div>';
require __DIR__ . '/layouts/app.php';

// ✗ Bad - duplicating HTML structure
// views/page.php
<!DOCTYPE html>
<html>
<head>...</head>
<body>...</body>
</html>
```

### 3. Separate Concerns

```php
// ✓ Good - logic in route, presentation in view
$app->get('/users', function($req, $res) {
    $users = UserService::getActive();
    return $res->view('users', ['users' => $users]);
});

// ✗ Bad - mixing database queries in views
// views/users.php
<?php
$users = DB::table('users')->get(); // Don't do this
```

### 4. Validate Props in React

```jsx
// ✓ Good - validate props from PHP
function App({ user, config }) {
  // Validate data exists
  if (!user || !config) {
    return <div>Loading...</div>
  }
  
  return <div>Welcome, {user.name}!</div>
}

// ✗ Bad - assuming props exist
function App({ user, config }) {
  return <div>Welcome, {user.name}!</div> // Error if user is undefined
}
```

### 5. Use Environment-Based Configuration

```php
// ✓ Good - environment-based mode
$mode = $_ENV['APP_ENV'] === 'production' ? 'production' : 'dev';
AssetManager::setMode($mode);

// ✗ Bad - hardcoded mode
AssetManager::setMode('production'); // Breaks in development
```

### 6. Keep Views Simple

```php
// ✓ Good - simple view logic
<?php foreach ($posts as $post): ?>
    <article>
        <h2><?= htmlspecialchars($post['title']) ?></h2>
        <p><?= htmlspecialchars($post['excerpt']) ?></p>
    </article>
<?php endforeach; ?>

// ✗ Bad - complex logic in views
<?php
// Don't do heavy processing in views
$posts = array_filter($posts, function($post) {
    return $post['status'] === 'published' 
        && strtotime($post['published_at']) < time();
});
usort($posts, function($a, $b) {
    return strcmp($b['created_at'], $a['created_at']);
});
?>
```

### 7. Optimize Asset Loading

```php
// ✓ Good - load only what's needed
<?php if ($page === 'dashboard'): ?>
    <script src="/assets/js/charts.js"></script>
<?php endif; ?>

// ✗ Bad - loading everything everywhere
<script src="/assets/js/all-libraries.js"></script> <!-- 5MB -->
```

### 8. Use Proper HTTP Status Codes

```php
// ✓ Good - appropriate status codes
$app->get('/posts/:id', function($req, $res) {
    $post = DB::table('posts')->where('id', $req->param('id'))->first();
    
    if (!$post) {
        return $res->status(404)->view('errors/404');
    }
    
    return $res->view('post', ['post' => $post]);
});

// ✗ Bad - always 200 OK
return $res->view('errors/404'); // Still returns 200
```

---

For practical examples, see [VIEW_EXAMPLES.md](VIEW_EXAMPLES.md).