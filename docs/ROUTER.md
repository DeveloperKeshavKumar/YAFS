# YAFS Router Documentation

The YAFS router provides Express.js-style routing for PHP applications with support for route parameters, middleware, and route groups.

## Table of Contents

- [Quick Start](#quick-start)
- [Defining Routes](#defining-routes)
- [Route Parameters](#route-parameters)
- [Route Groups](#route-groups)
- [Middleware](#middleware)
- [Error Handling](#error-handling)
- [Best Practices](#best-practices)

## Quick Start
```php
<?php

require_once 'vendor/autoload.php'; // Or your autoloader

use YAFS\Application;

$app = new Application();

// Define a simple route
$app->get('/', function($req, $res) {
    return $res->text('Hello, YAFS!');
});

// Define a route with parameters
$app->get('/users/:id', function($req, $res) {
    $id = $req->param('id');
    return $res->json(['user_id' => $id]);
});

// Run the application
$app->run();
```

## Defining Routes

YAFS supports all standard HTTP methods:
```php
// GET - Retrieve data
$app->get('/users', function($req, $res) {
    return $res->json(['users' => []]);
});

// POST - Create new resource
$app->post('/users', function($req, $res) {
    $data = $req->json();
    // Create user...
    return $res->status(201)->json(['created' => true]);
});

// PUT - Update entire resource
$app->put('/users/:id', function($req, $res) {
    $id = $req->param('id');
    // Update user...
    return $res->json(['updated' => true]);
});

// PATCH - Partial update
$app->patch('/users/:id', function($req, $res) {
    $id = $req->param('id');
    // Partially update user...
    return $res->json(['updated' => true]);
});

// DELETE - Remove resource
$app->delete('/users/:id', function($req, $res) {
    $id = $req->param('id');
    // Delete user...
    return $res->status(204)->json([]);
});
```

## Route Parameters

### Basic Parameters

Route parameters are defined with a colon prefix:
```php
$app->get('/users/:id', function($req, $res) {
    $id = $req->param('id');
    return "User ID: {$id}";
});

// Matches: /users/123, /users/abc, /users/999
// Does not match: /users, /users/123/posts
```

### Multiple Parameters

Routes can have multiple parameters:
```php
$app->get('/users/:userId/posts/:postId', function($req, $res) {
    $userId = $req->param('userId');
    $postId = $req->param('postId');
    
    return $res->json([
        'user' => $userId,
        'post' => $postId
    ]);
});

// Matches: /users/42/posts/99
```

### Parameter Names

Parameter names must:
- Start with a letter or underscore
- Contain only letters, numbers, and underscores
- Be unique within the route pattern
```php
// Valid
$app->get('/users/:id', ...);
$app->get('/users/:user_id', ...);
$app->get('/users/:userId', ...);

// Invalid - will throw InvalidRouteException
$app->get('/users/:123', ...);        // Starts with number
$app->get('/users/:id/posts/:id', ...); // Duplicate parameter name
```

### Query Parameters

Query parameters are separate from route parameters:
```php
$app->get('/search', function($req, $res) {
    $query = $req->query('q', '');      // Get query param with default
    $page = $req->query('page', 1);
    
    return $res->json([
        'query' => $query,
        'page' => $page
    ]);
});

// URL: /search?q=php&page=2
// Route matches: /search
// Query params: ['q' => 'php', 'page' => '2']
```

## Route Groups

Route groups allow you to organize routes with common prefixes and middleware.

### Basic Groups
```php
$app->group(['prefix' => '/api'], function($app) {
    
    $app->get('/users', function($req, $res) {
        // Accessible at: /api/users
        return $res->json(['users' => []]);
    });
    
    $app->get('/posts', function($req, $res) {
        // Accessible at: /api/posts
        return $res->json(['posts' => []]);
    });
});
```

### Nested Groups

Groups can be nested for deeper organization:
```php
$app->group(['prefix' => '/api'], function($app) {
    
    $app->group(['prefix' => '/v1'], function($app) {
        
        $app->get('/users', function($req, $res) {
            // Accessible at: /api/v1/users
            return $res->json(['version' => 'v1']);
        });
    });
    
    $app->group(['prefix' => '/v2'], function($app) {
        
        $app->get('/users', function($req, $res) {
            // Accessible at: /api/v2/users
            return $res->json(['version' => 'v2']);
        });
    });
});
```

### Groups with Middleware

Apply middleware to all routes in a group:
```php
// Authentication middleware
$authMiddleware = function($req, $res, $next) {
    // Check authentication...
    if (!isset($_SESSION['user'])) {
        return $res->status(401)->json(['error' => 'Unauthorized']);
    }
    return $next();
};

$app->group([
    'prefix' => '/admin',
    'middleware' => [$authMiddleware]
], function($app) {
    
    // All these routes require authentication
    $app->get('/dashboard', function($req, $res) {
        return $res->json(['dashboard' => 'data']);
    });
    
    $app->get('/users', function($req, $res) {
        return $res->json(['users' => []]);
    });
});
```

## Middleware

Middleware functions run before (and optionally after) route handlers.

### Global Middleware

Global middleware runs for every request:
```php
// Logging middleware
$app->use(function($req, $res, $next) {
    error_log("Request: {$req->getMethod()} {$req->getPath()}");
    
    $response = $next(); // Call next middleware/handler
    
    error_log("Response sent");
    return $response;
});

// CORS middleware
$app->use(function($req, $res, $next) {
    $res->header('Access-Control-Allow-Origin', '*');
    return $next();
});
```

### Route-Specific Middleware

Attach middleware to specific routes:
```php
$authMiddleware = function($req, $res, $next) {
    if (!isset($_SESSION['user'])) {
        return $res->status(401)->json(['error' => 'Unauthorized']);
    }
    return $next();
};

$app->get('/profile', function($req, $res) {
    return $res->json(['profile' => 'data']);
})->middleware($authMiddleware);
```

### Middleware Chaining

Multiple middleware can be chained:
```php
$app->get('/admin/users', function($req, $res) {
    return $res->json(['users' => []]);
})
->middleware($authMiddleware)
->middleware($adminMiddleware)
->middleware($loggingMiddleware);
```

### Middleware Execution Order

Middleware executes in this order:
1. Global middleware (in order registered)
2. Group middleware (outer to inner for nested groups)
3. Route-specific middleware (in order attached)
4. Route handler

### Short-Circuiting

Middleware can stop the chain by not calling `next()`:
```php
$app->use(function($req, $res, $next) {
    // Rate limiting check
    if ($this->isRateLimited($req)) {
        return $res->status(429)->json(['error' => 'Too many requests']);
        // Not calling next() stops the chain
    }
    
    return $next(); // Continue to next middleware/handler
});
```

## Error Handling

### Debug Mode

Enable detailed error pages during development:
```php
$app = new Application();
$app->setDebug(true); // Default: true

// In production:
$app->setDebug(false);
```

### Custom Error Handler

Define custom error handling logic:
```php
$app->setErrorHandler(function($exception) {
    // Log to external service
    logToSentry($exception);
    
    // Return custom error response
    return [
        'error' => true,
        'message' => 'An error occurred',
        'code' => $exception->getCode()
    ];
});
```

### Handling Specific Exceptions
```php
try {
    $app->run();
} catch (YAFS\Exceptions\RouteNotFoundException $e) {
    // Handle 404
    echo "Page not found: " . $e->getPath();
} catch (YAFS\Exceptions\InvalidRouteException $e) {
    // Handle route definition errors
    echo "Invalid route: " . $e->getMessage();
}
```

## Best Practices

### 1. Order Routes from Specific to General
```php
// ✓ Good - specific route first
$app->get('/users/new', ...);
$app->get('/users/:id', ...);

// ✗ Bad - general route first will match everything
$app->get('/users/:id', ...);  // This matches "new" as an ID
$app->get('/users/new', ...);  // This never matches
```

### 2. Use Route Groups for Organization
```php
// ✓ Good - organized by version
$app->group(['prefix' => '/api/v1'], function($app) {
    // v1 routes...
});

$app->group(['prefix' => '/api/v2'], function($app) {
    // v2 routes...
});
```

### 3. Keep Handlers Focused
```php
// ✓ Good - handler focuses on business logic
$app->get('/users/:id', function($req, $res) {
    $id = $req->param('id');
    $user = UserService::find($id);
    return $res->json($user);
});

// ✗ Bad - mixing concerns
$app->get('/users/:id', function($req, $res) {
    // Don't put authentication, logging, etc. here
    // Use middleware instead
});
```

### 4. Use Middleware for Cross-Cutting Concerns
```php
// ✓ Good - authentication in middleware
$authMiddleware = function($req, $res, $next) {
    // Auth logic once
    return $next();
};

$app->group(['middleware' => [$authMiddleware]], function($app) {
    // All protected routes
});
```

### 5. Validate Route Patterns Early
```php
// Routes are validated when defined, not at runtime
// This fails immediately during application setup:
try {
    $app->get('invalid', ...); // Missing leading slash
} catch (InvalidRouteException $e) {
    // Handle or let it fail - better to know during development
}
```

### 6. Use Meaningful Parameter Names
```php
// ✓ Good - clear parameter names
$app->get('/users/:userId/posts/:postId', ...);

// ✗ Bad - ambiguous names
$app->get('/users/:id/posts/:id2', ...);
```

### 7. Handle Errors Gracefully
```php
$app->setDebug($_ENV['APP_ENV'] !== 'production');

$app->setErrorHandler(function($e) {
    // Log all errors
    error_log($e->getMessage());
    
    // Return appropriate response
    return ['error' => 'Something went wrong'];
});
```

## Common Patterns

### API Versioning
```php
$app->group(['prefix' => '/api/v1'], function($app) {
    $app->get('/users', ...);
});

$app->group(['prefix' => '/api/v2'], function($app) {
    $app->get('/users', ...); // Different implementation
});
```

### Multi-Tenancy
```php
$tenantMiddleware = function($req, $res, $next) {
    $host = $req->header('host');
    $subdomain = explode('.', $host)[0];
    $req->tenant = Tenant::findBySubdomain($subdomain);
    return $next();
};

$app->group(['middleware' => [$tenantMiddleware]], function($app) {
    // All routes have tenant context
});
```

### RESTful Resource Routes
```php
// GET /api/users - List all
$app->get('/api/users', ...);

// POST /api/users - Create new
$app->post('/api/users', ...);

// GET /api/users/:id - Get one
$app->get('/api/users/:id', ...);

// PUT /api/users/:id - Update entire
$app->put('/api/users/:id', ...);

// PATCH /api/users/:id - Partial update
$app->patch('/api/users/:id', ...);

// DELETE /api/users/:id - Remove
$app->delete('/api/users/:id', ...);
```

---

For more examples, see [ROUTER_EXAMPLES.md](ROUTER_EXAMPLES.md).