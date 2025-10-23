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
- ⏳ **Query Builder**: MySQL query builder with prepared statements  
- ⏳ **React Integration**: PHP-to-React prop passing with Vite
- ⏳ **Live Demo**: Coming soon

## Quick Example

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
- Complete documentation

**Phase 2 - Database (Next)**
- MySQL query builder
- Prepared statements
- Connection pooling

**Phase 3 - React Integration**
- Vite setup
- PHP-to-React props
- Development workflow

**v1.0 Goal**
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

**Status**: Pre-alpha, v0.1.0 (Router complete)  
**Author**: [Keshav Kumar](https://github.com/DeveloperKeshavKumar)  
**Contact**: [LinkedIn](https://linkedin.com/in/keshav-1-kumar)