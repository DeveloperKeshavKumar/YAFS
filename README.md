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

YAFS is being built component-by-component with a focus on quality over features:

- ✅ **Router**: Express-style routing with middleware support
- ⏳ **Query Builder**: Secure MySQL query builder with prepared statements  
- ⏳ **React Integration**: Seamless PHP-to-React prop passing with Vite
- ⏳ **Documentation & Demo**: Live examples and deployment guide

## Quick Example
```php
// Define routes with Express.js-style syntax
$app = new YAFS\Application();

$app->get('/users/:id', function($req, $res) {
    $user = DB::table('users')
        ->where('id', $req->params['id'])
        ->first();
    
    return $res->json($user);
});

$app->listen(3000);
```

## Philosophy

YAFS follows these principles:

1. **Security first**: Prepared statements, XSS prevention, and secure defaults
2. **Developer experience**: Clean APIs that are intuitive and predictable
3. **No magic**: Explicit is better than implicit. You should understand what your code does
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

**v0.1 (Current Focus)**
- Core routing system with middleware
- MySQL query builder with security focus
- React integration via Vite
- Basic documentation and examples

**v0.2 (Future)**
- Authentication helpers
- File upload handling
- Enhanced error pages
- Performance optimizations

**v1.0 (Goal)**
- Battle-tested in production apps
- Comprehensive documentation
- Deployment tooling
- Community plugin ecosystem

## Why I'm Building This

I'm a recent graduate actively looking for opportunitites. After getting a ton of rejections, I decided to build something that demonstrates my understanding of web application architecture, security, and modern development practices.

YAFS is both a learning project and a practical tool. I'm building it to show my thought process, technical decisions, and ability to ship working software.

If this project helps even one other developer build their application more easily, it's a success.

## Contributing

YAFS is in early development. I'm focusing on getting the core components right before accepting contributions, but feedback and suggestions are always welcome!

## License

MIT License - Use it however you want

---

**Status**: Pre-alpha, under active development  
**Author**: [Keshav Kumar](https://github.com/DeveloperKeshavKumar)  
**Contact**: [LinkedIn](https://linkedin.com/in/keshav-1-kumar)
