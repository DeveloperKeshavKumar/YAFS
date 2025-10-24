# YAFS - Yet Another Full-Stack Framework

**PHP Backend + React Frontend in a Single, Straightforward Project**

YAFS is a lightweight full-stack web framework that combines the reliability and affordability of PHP hosting with modern React frontend development. Built with Express.js-inspired patterns, YAFS provides a clean, intuitive API for developers who want to build modern web applications without the complexity of enterprise frameworks.

## Why YAFS?

- **Honest naming**: We're not claiming to revolutionize web development. We're building something practical that works.
- **Express.js patterns in PHP**: Familiar, intuitive routing and middleware system
- **Zero external dependencies**: Self-contained core means no dependency hell or breaking changes
- **Cost-effective hosting**: Deploy on affordable PHP hosting (₹200-500/month) instead of expensive Node.js infrastructure
- **Modern developer experience**: React, Vite, and clean API design

## Current Status

🚧 **In Active Development** 

- ✅ **Router (v0.1.0)**: Production-ready with 90%+ test coverage - [Docs](docs/ROUTER.md)
- ✅ **Query Builder (v0.2.0)**: MySQL query builder with prepared statements - [Docs](docs/DATABASE.md)
- ✅ **React Integration (v0.3.0)**: Single-port dev server with Vite HMR - [Docs](docs/View.md)
- ⏳ **Live Demo**: Coming soon

## Quick Start

```bash
# Create new project
php yafs init react

# Start development
cd project-name
php server.php

# Visit http://localhost:8000
```

## Quick Example

### Routing
```php
<?php

use YAFS\Application;

$app = new Application();

// Simple route
$app->get('/users/:id', function($req, $res) {
    return $res->json(['user_id' => $req->param('id')]);
});

// With middleware
$app->post('/posts', [$authMiddleware], function($req, $res) {
    return $res->status(201)->json(['created' => true]);
});

// Route groups
$app->group(['prefix' => '/api'], function($app) {
    $app->get('/status', function($req, $res) {
        return $res->json(['status' => 'online']);
    });
});

$app->run();
```

### Database
```php
<?php

use YAFS\Database\DB;

// Configure database
DB::addConnection([
    'host' => 'localhost',
    'database' => 'myapp',
    'username' => 'root',
    'password' => 'secret'
]);

// Fluent query builder
$users = DB::table('users')
    ->where('status', 'active')
    ->where('age', '>', 18)
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

// Insert with auto-increment ID
$userId = DB::table('users')->insertGetId([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Transactions
try {
    DB::beginTransaction();
    
    $orderId = DB::table('orders')->insertGetId($orderData);
    DB::table('order_items')->insert($itemsData);
    
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    throw $e;
}
```

### React Integration
```php
// routes/web.php
$app->get('/', function($req, $res) {
    return $res->view('react', [
        'title' => 'My App',
        'props' => [
            'user' => ['name' => 'John'],
            'apiUrl' => '/api'
        ]
    ]);
});

// API routes work alongside React
$app->get('/api/hello', function($req, $res) {
    return $res->json(['message' => 'Hello from PHP!']);
});
```

```jsx
// frontend/src/App.jsx
import { useEffect } from 'react'

function App({ user, apiUrl }) {
  useEffect(() => {
    fetch(`${apiUrl}/hello`)
      .then(res => res.json())
      .then(data => console.log(data))
  }, [])

  return <h1>Hello, {user.name}!</h1>
}
```

## Philosophy

1. **Security first**: Prepared statements, XSS prevention, and secure defaults
2. **Developer experience**: Clean APIs that are intuitive and predictable
3. **No magic**: Explicit is better than implicit
4. **Production-ready basics**: Nail the fundamentals instead of adding every feature

## What YAFS Is NOT

- Not a Laravel replacement for complex enterprise applications
- Not attempting server-side rendering (initially)
- Not trying to support every database (MySQL only for now)
- Not solving problems you probably don't have

## Technical Requirements

- PHP 8.2+
- MySQL 8.0+
- Node.js 18+ (for React/Vite tooling)

## Roadmap

**Phase 1 - Router ✅ (v0.1.0 - Complete)**
- Express-style routing with all HTTP methods
- Route parameters and groups
- Middleware support
- 90%+ test coverage

**Phase 2 - Database ✅ (v0.2.0 - Complete)**
- MySQL query builder with fluent interface
- Prepared statements (SQL injection impossible)
- Connection pooling and multiple databases
- Transaction support

**Phase 3 - React Integration ✅ (v0.3.0 - Complete)**
- Single-port development (PHP + Vite on :8000)
- Automatic Vite orchestration
- Hot Module Replacement (HMR)
- PHP-to-React prop passing
- Production-ready builds

**v1.0 Goal (Next)**
- Battle-tested in production
- Authentication helpers
- File uploads
- Deployment guide

## Why I'm Building This

I'm a recent graduate actively looking for opportunities. After facing rejections, I decided to build something that demonstrates my understanding of web application architecture, security, and modern development practices.

YAFS is both a learning project and a practical tool. I'm building it to show my thought process, technical decisions, and ability to ship working software.

If this project helps even one other developer, it's a success.

## Contributing

YAFS is in early development. I'm focusing on getting the core components right before accepting contributions, but feedback and suggestions are always welcome!

## License

MIT License - Use it however you want

---

**Status**: Pre-alpha, v0.3.0 (View Layer [React+Vite] complete)  
**Author**: [Keshav Kumar](https://github.com/DeveloperKeshavKumar)  
**Contact**: [LinkedIn](https://linkedin.com/in/keshav-1-kumar)