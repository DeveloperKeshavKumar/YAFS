# YAFS Router Examples

Real-world examples demonstrating common use cases.

## Basic Blog Application
```php
<?php

use YAFS\Application;

$app = new Application();

// Homepage
$app->get('/', function($req, $res) {
    return $res->html('
        <h1>My Blog</h1>
        <a href="/posts">View Posts</a>
    ');
});

// List posts
$app->get('/posts', function($req, $res) {
    $page = $req->query('page', 1);
    $posts = PostModel::paginate($page, 10);
    
    return $res->json([
        'posts' => $posts,
        'page' => $page
    ]);
});

// Single post
$app->get('/posts/:slug', function($req, $res) {
    $slug = $req->param('slug');
    $post = PostModel::findBySlug($slug);
    
    if (!$post) {
        return $res->status(404)->json(['error' => 'Post not found']);
    }
    
    return $res->json($post);
});

// Create post (requires auth)
$app->post('/posts', function($req, $res) {
    $data = $req->json();
    $post = PostModel::create($data);
    
    return $res->status(201)->json($post);
})->middleware($authMiddleware);

$app->run();
```

## RESTful API with Authentication
```php
<?php

use YAFS\Application;

$app = new Application();
$app->setDebug(false); // Production mode

// Authentication middleware
$authMiddleware = function($req, $res, $next) {
    $token = $req->header('authorization');
    
    if (!$token || !$user = Auth::validateToken($token)) {
        return $res->status(401)->json(['error' => 'Unauthorized']);
    }
    
    $req->user = $user;
    return $next();
};

// Public routes
$app->post('/auth/login', function($req, $res) {
    $credentials = $req->json();
    $token = Auth::login($credentials['email'], $credentials['password']);
    
    if (!$token) {
        return $res->status(401)->json(['error' => 'Invalid credentials']);
    }
    
    return $res->json(['token' => $token]);
});

// Protected API routes
$app->group([
    'prefix' => '/api',
    'middleware' => [$authMiddleware]
], function($app) {
    
    // User profile
    $app->get('/me', function($req, $res) {
        return $res->json($req->user);
    });
    
    // User's posts
    $app->get('/me/posts', function($req, $res) {
        $posts = PostModel::byUser($req->user->id);
        return $res->json($posts);
    });
    
    // Create post
    $app->post('/posts', function($req, $res) {
        $data = $req->json();
        $data['user_id'] = $req->user->id;
        
        $post = PostModel::create($data);
        return $res->status(201)->json($post);
    });
});

$app->run();
```

## API Versioning
```php
<?php

use YAFS\Application;

$app = new Application();

// API v1
$app->group(['prefix' => '/api/v1'], function($app) {
    
    $app->get('/users', function($req, $res) {
        return $res->json([
            'version' => 'v1',
            'users' => User::all()
        ]);
    });
    
    $app->get('/users/:id', function($req, $res) {
        $user = User::find($req->param('id'));
        return $res->json([
            'version' => 'v1',
            'user' => $user
        ]);
    });
});

// API v2 - Enhanced response format
$app->group(['prefix' => '/api/v2'], function($app) {
    
    $app->get('/users', function($req, $res) {
        return $res->json([
            'meta' => [
                'version' => 'v2',
                'count' => User::count()
            ],
            'data' => User::all()
        ]);
    });
    
    $app->get('/users/:id', function($req, $res) {
        $user = User::find($req->param('id'));
        return $res->json([
            'meta' => ['version' => 'v2'],
            'data' => $user,
            'included' => [
                'posts' => $user->posts(),
                'comments' => $user->comments()
            ]
        ]);
    });
});

$app->run();
```

## Multi-Tenant SaaS Application
```php
<?php

use YAFS\Application;

$app = new Application();

// Tenant resolution middleware
$tenantMiddleware = function($req, $res, $next) {
    $host = $req->header('host');
    $subdomain = explode('.', $host)[0];
    
    $tenant = Tenant::findBySubdomain($subdomain);
    
    if (!$tenant) {
        return $res->status(404)->json(['error' => 'Tenant not found']);
    }
    
    // Set tenant context for database queries
    DB::setTenant($tenant->id);
    $req->tenant = $tenant;
    
    return $next();
};

// All routes are tenant-aware
$app->group(['middleware' => [$tenantMiddleware]], function($app) {
    
    $app->get('/dashboard', function($req, $res) {
        return $res->json([
            'tenant' => $req->tenant->name,
            'stats' => Dashboard::stats()
        ]);
    });
    
    $app->get('/users', function($req, $res) {
        // Automatically scoped to current tenant
        return $res->json(User::all());
    });
    
    $app->post('/users', function($req, $res) {
        $data = $req->json();
        $data['tenant_id'] = $req->tenant->id;
        
        $user = User::create($data);
        return $res->status(201)->json($user);
    });
});

$app->run();
```

## Admin Panel with Role-Based Access
```php
<?php

use YAFS\Application;

$app = new Application();

// Middleware factory for role checking
function requireRole($role) {
    return function($req, $res, $next) use ($role) {
        if (!isset($req->user) || $req->user->role !== $role) {
            return $res->status(403)->json(['error' => 'Forbidden']);
        }
        return $next();
    };
}

// Admin routes
$app->group([
    'prefix' => '/admin',
    'middleware' => [$authMiddleware, requireRole('admin')]
], function($app) {
    
    $app->get('/dashboard', function($req, $res) {
        return $res->json(['admin' => 'dashboard']);
    });
    
    $app->get('/users', function($req, $res) {
        return $res->json(User::all());
    });
    
    $app->delete('/users/:id', function($req, $res) {
        User::delete($req->param('id'));
        return $res->status(204)->json([]);
    });
});

// Moderator routes
$app->group([
    'prefix' => '/mod',
    'middleware' => [$authMiddleware, requireRole('moderator')]
], function($app) {
    
    $app->get('/reports', function($req, $res) {
        return $res->json(Report::pending());
    });
    
    $app->post('/reports/:id/resolve', function($req, $res) {
        Report::resolve($req->param('id'));
        return $res->json(['resolved' => true]);
    });
});

$app->run();
```

## E-commerce Product Catalog
```php
<?php

use YAFS\Application;

$app = new Application();

// Products
$app->get('/products', function($req, $res) {
    $category = $req->query('category');
    $minPrice = $req->query('min_price', 0);
    $maxPrice = $req->query('max_price', PHP_INT_MAX);
    
    $products = Product::query()
        ->when($category, fn($q) => $q->where('category', $category))
        ->whereBetween('price', [$minPrice, $maxPrice])
        ->get();
    
    return $res->json($products);
});

// Product details
$app->get('/products/:slug', function($req, $res) {
    $product = Product::findBySlug($req->param('slug'));
    
    if (!$product) {
        return $res->status(404)->json(['error' => 'Product not found']);
    }
    
    return $res->json([
        'product' => $product,
        'related' => $product->related(),
        'reviews' => $product->reviews()
    ]);
});

// Shopping cart
$app->group(['prefix' => '/cart'], function($app) use ($authMiddleware) {
    
    $app->get('/', function($req, $res) use ($authMiddleware) {
        $sessionId = session_id();
        $cart = Cart::forSession($sessionId);
        return $res->json($cart);
    });
    
    $app->post('/items', function($req, $res) {
        $data = $req->json();
        $sessionId = session_id();
        
        Cart::addItem($sessionId, $data['product_id'], $data['quantity']);
        return $res->status(201)->json(['added' => true]);
    });
    
    $app->delete('/items/:productId', function($req, $res) {
        $sessionId = session_id();
        Cart::removeItem($sessionId, $req->param('productId'));
        return $res->status(204)->json([]);
    });
});

$app->run();
```

## Webhook Handler
```php
<?php

use YAFS\Application;

$app = new Application();

// Webhook signature validation middleware
$validateWebhook = function($req, $res, $next) {
    $signature = $req->header('x-webhook-signature');
    $payload = $req->getBody();
    
    $expectedSignature = hash_hmac('sha256', $payload, $_ENV['WEBHOOK_SECRET']);
    
    if (!hash_equals($expectedSignature, $signature)) {
        return $res->status(401)->json(['error' => 'Invalid signature']);
    }
    
    return $next();
};

// Webhook endpoints
$app->group([
    'prefix' => '/webhooks',
    'middleware' => [$validateWebhook]
], function($app) {
    
    $app->post('/stripe', function($req, $res) {
        $event = $req->json();
        
        switch ($event['type']) {
            case 'payment_intent.succeeded':
                Payments::handleSuccess($event['data']);
                break;
            case 'payment_intent.failed':
                Payments::handleFailure($event['data']);
                break;
        }
        
        return $res->json(['received' => true]);
    });
    
    $app->post('/github', function($req, $res) {
        $event = $req->header('x-github-event');
        $payload = $req->json();
        
        if ($event === 'push') {
            Deployments::trigger($payload);
        }
        
        return $res->json(['received' => true]);
    });
});

$app->run();
```

---

These examples demonstrate common patterns you'll encounter in real applications. Adapt them to your specific needs!