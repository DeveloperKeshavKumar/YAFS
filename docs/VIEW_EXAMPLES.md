# YAFS View Layer Examples

Real-world examples demonstrating view rendering and React integration patterns.

## Blog Application

### PHP Templates

```php
<?php
// routes/web.php

use YAFS\Application;
use YAFS\View\View;
use YAFS\Database\DB;

$app = new Application();

// Share global data
View::share('site_name', 'My Blog');
View::share('current_year', date('Y'));

// Homepage
$app->get('/', function($req, $res) {
    $posts = DB::table('posts')
        ->join('users', 'posts.user_id', '=', 'users.id')
        ->select('posts.*', 'users.name as author')
        ->where('posts.status', 'published')
        ->orderBy('posts.published_at', 'desc')
        ->limit(10)
        ->get();
    
    return $res->view('blog/home', [
        'title' => 'Latest Posts',
        'posts' => $posts
    ]);
});

// Single post
$app->get('/posts/:slug', function($req, $res) {
    $post = DB::table('posts')
        ->join('users', 'posts.user_id', '=', 'users.id')
        ->select('posts.*', 'users.name as author', 'users.avatar as author_avatar')
        ->where('posts.slug', $req->param('slug'))
        ->where('posts.status', 'published')
        ->first();
    
    if (!$post) {
        return $res->status(404)->view('errors/404');
    }
    
    // Increment view count
    DB::table('posts')
        ->where('id', $post['id'])
        ->increment('view_count');
    
    // Get comments
    $comments = DB::table('comments')
        ->join('users', 'comments.user_id', '=', 'users.id')
        ->select('comments.*', 'users.name as author', 'users.avatar')
        ->where('comments.post_id', $post['id'])
        ->where('comments.approved', 1)
        ->orderBy('comments.created_at', 'asc')
        ->get();
    
    return $res->view('blog/post', [
        'title' => $post['title'],
        'post' => $post,
        'comments' => $comments
    ]);
});

$app->run();
```

```php
// views/blog/home.php
<?php
$content = <<<HTML
<div class="container">
    <h1>Latest Posts</h1>
    
    <div class="posts-grid">
HTML;

foreach ($posts as $post):
    $excerpt = htmlspecialchars($post['excerpt'] ?? substr($post['content'], 0, 200));
    $title = htmlspecialchars($post['title']);
    $author = htmlspecialchars($post['author']);
    $date = date('F j, Y', strtotime($post['published_at']));
    
    $content .= <<<HTML
        <article class="post-card">
            <h2><a href="/posts/{$post['slug']}">{$title}</a></h2>
            <div class="meta">
                <span>By {$author}</span>
                <span>{$date}</span>
                <span>{$post['view_count']} views</span>
            </div>
            <p>{$excerpt}...</p>
            <a href="/posts/{$post['slug']}" class="read-more">Read more →</a>
        </article>
HTML;
endforeach;

$content .= <<<HTML
    </div>
</div>
HTML;

require __DIR__ . '/../layouts/blog.php';
```

```php
// views/blog/post.php
<?php
$title = htmlspecialchars($post['title']);
$author = htmlspecialchars($post['author']);
$date = date('F j, Y', strtotime($post['published_at']));
$content_html = nl2br(htmlspecialchars($post['content']));

$content = <<<HTML
<article class="post-single">
    <header>
        <h1>{$title}</h1>
        <div class="meta">
            <img src="{$post['author_avatar']}" alt="{$author}" class="avatar">
            <div>
                <span class="author">By {$author}</span>
                <span class="date">{$date}</span>
            </div>
        </div>
    </header>
    
    <div class="post-content">
        {$content_html}
    </div>
    
    <div class="comments-section">
        <h2>Comments ({$post['comment_count']})</h2>
HTML;

foreach ($comments as $comment):
    $comment_author = htmlspecialchars($comment['author']);
    $comment_text = nl2br(htmlspecialchars($comment['content']));
    $comment_date = date('M j, Y', strtotime($comment['created_at']));
    
    $content .= <<<HTML
        <div class="comment">
            <img src="{$comment['avatar']}" alt="{$comment_author}" class="avatar">
            <div class="comment-body">
                <div class="comment-header">
                    <strong>{$comment_author}</strong>
                    <span class="date">{$comment_date}</span>
                </div>
                <p>{$comment_text}</p>
            </div>
        </div>
HTML;
endforeach;

$content .= <<<HTML
    </div>
</article>
HTML;

require __DIR__ . '/../layouts/blog.php';
```

## React Dashboard Application

### Full-Stack Setup

```php
<?php
// routes/web.php

use YAFS\Application;
use YAFS\Database\DB;

$app = new Application();

// React app route
$app->get('/', function($req, $res) {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login');
        exit;
    }
    
    // Get user data
    $user = DB::table('users')
        ->where('id', $_SESSION['user_id'])
        ->first();
    
    // Get initial dashboard data
    $stats = [
        'totalPosts' => DB::table('posts')->where('user_id', $user['id'])->count(),
        'totalComments' => DB::table('comments')->where('user_id', $user['id'])->count(),
        'totalViews' => DB::table('posts')->where('user_id', $user['id'])->sum('view_count')
    ];
    
    $recentPosts = DB::table('posts')
        ->where('user_id', $user['id'])
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    
    return $res->view('react', [
        'title' => 'Dashboard - ' . $user['name'],
        'props' => [
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'avatar' => $user['avatar'],
                'role' => $user['role']
            ],
            'stats' => $stats,
            'recentPosts' => $recentPosts,
            'config' => [
                'apiUrl' => '/api',
                'uploadUrl' => '/api/upload',
                'maxFileSize' => 5 * 1024 * 1024 // 5MB
            ]
        ]
    ]);
});

// API routes
$app->group(['prefix' => '/api'], function($app) {
    
    // Get all posts
    $app->get('/posts', function($req, $res) {
        $posts = DB::table('posts')
            ->where('user_id', $_SESSION['user_id'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return $res->json($posts);
    });
    
    // Create post
    $app->post('/posts', function($req, $res) {
        $data = $req->json();
        
        $id = DB::table('posts')->insertGetId([
            'user_id' => $_SESSION['user_id'],
            'title' => $data['title'],
            'content' => $data['content'],
            'slug' => slugify($data['title']),
            'status' => 'draft',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return $res->status(201)->json(['id' => $id]);
    });
    
    // Update post
    $app->put('/posts/:id', function($req, $res) {
        $id = $req->param('id');
        $data = $req->json();
        
        DB::table('posts')
            ->where('id', $id)
            ->where('user_id', $_SESSION['user_id'])
            ->update([
                'title' => $data['title'],
                'content' => $data['content'],
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        
        return $res->json(['updated' => true]);
    });
    
    // Delete post
    $app->delete('/posts/:id', function($req, $res) {
        DB::table('posts')
            ->where('id', $req->param('id'))
            ->where('user_id', $_SESSION['user_id'])
            ->delete();
        
        return $res->status(204)->json([]);
    });
});

function slugify($text) {
    return strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $text));
}

$app->run();
```

### React Components

```jsx
// frontend/src/App.jsx
import { useState, useEffect } from 'react'
import Dashboard from './components/Dashboard'
import PostList from './components/PostList'
import PostEditor from './components/PostEditor'
import './App.css'

function App({ user, stats, recentPosts, config }) {
  const [currentView, setCurrentView] = useState('dashboard')
  const [posts, setPosts] = useState(recentPosts || [])
  const [editingPost, setEditingPost] = useState(null)
  
  useEffect(() => {
    // Fetch all posts
    fetch(`${config.apiUrl}/posts`)
      .then(res => res.json())
      .then(setPosts)
      .catch(err => console.error('Error fetching posts:', err))
  }, [config.apiUrl])
  
  const handleCreatePost = async (postData) => {
    const response = await fetch(`${config.apiUrl}/posts`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(postData)
    })
    
    if (response.ok) {
      const { id } = await response.json()
      setPosts([{ ...postData, id }, ...posts])
      setCurrentView('posts')
    }
  }
  
  const handleUpdatePost = async (id, postData) => {
    const response = await fetch(`${config.apiUrl}/posts/${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(postData)
    })
    
    if (response.ok) {
      setPosts(posts.map(p => p.id === id ? { ...p, ...postData } : p))
      setEditingPost(null)
      setCurrentView('posts')
    }
  }
  
  const handleDeletePost = async (id) => {
    if (!confirm('Delete this post?')) return
    
    const response = await fetch(`${config.apiUrl}/posts/${id}`, {
      method: 'DELETE'
    })
    
    if (response.ok) {
      setPosts(posts.filter(p => p.id !== id))
    }
  }
  
  return (
    <div className="app">
      <header className="app-header">
        <h1>My Blog Dashboard</h1>
        <div className="user-info">
          <img src={user.avatar} alt={user.name} className="avatar" />
          <span>{user.name}</span>
        </div>
      </header>
      
      <nav className="app-nav">
        <button 
          className={currentView === 'dashboard' ? 'active' : ''}
          onClick={() => setCurrentView('dashboard')}
        >
          Dashboard
        </button>
        <button 
          className={currentView === 'posts' ? 'active' : ''}
          onClick={() => setCurrentView('posts')}
        >
          Posts
        </button>
        <button 
          className={currentView === 'create' ? 'active' : ''}
          onClick={() => { setCurrentView('create'); setEditingPost(null); }}
        >
          Create Post
        </button>
      </nav>
      
      <main className="app-main">
        {currentView === 'dashboard' && (
          <Dashboard user={user} stats={stats} recentPosts={recentPosts} />
        )}
        
        {currentView === 'posts' && (
          <PostList 
            posts={posts}
            onEdit={(post) => { setEditingPost(post); setCurrentView('edit'); }}
            onDelete={handleDeletePost}
          />
        )}
        
        {(currentView === 'create' || currentView === 'edit') && (
          <PostEditor 
            post={editingPost}
            onSave={editingPost ? handleUpdatePost : handleCreatePost}
            onCancel={() => setCurrentView('posts')}
          />
        )}
      </main>
    </div>
  )
}

export default App
```

```jsx
// frontend/src/components/Dashboard.jsx
function Dashboard({ user, stats, recentPosts }) {
  return (
    <div className="dashboard">
      <h2>Welcome back, {user.name}!</h2>
      
      <div className="stats-grid">
        <div className="stat-card">
          <h3>{stats.totalPosts}</h3>
          <p>Total Posts</p>
        </div>
        <div className="stat-card">
          <h3>{stats.totalComments}</h3>
          <p>Comments</p>
        </div>
        <div className="stat-card">
          <h3>{stats.totalViews}</h3>
          <p>Total Views</p>
        </div>
      </div>
      
      <div className="recent-posts">
        <h3>Recent Posts</h3>
        <ul>
          {recentPosts.map(post => (
            <li key={post.id}>
              <a href={`/posts/${post.slug}`}>{post.title}</a>
              <span className="views">{post.view_count} views</span>
            </li>
          ))}
        </ul>
      </div>
    </div>
  )
}

export default Dashboard
```

```jsx
// frontend/src/components/PostList.jsx
function PostList({ posts, onEdit, onDelete }) {
  return (
    <div className="post-list">
      <h2>All Posts</h2>
      
      <table>
        <thead>
          <tr>
            <th>Title</th>
            <th>Status</th>
            <th>Views</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {posts.map(post => (
            <tr key={post.id}>
              <td>{post.title}</td>
              <td>
                <span className={`status ${post.status}`}>
                  {post.status}
                </span>
              </td>
              <td>{post.view_count}</td>
              <td>{new Date(post.created_at).toLocaleDateString()}</td>
              <td>
                <button onClick={() => onEdit(post)}>Edit</button>
                <button onClick={() => onDelete(post.id)}>Delete</button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}

export default PostList
```

```jsx
// frontend/src/components/PostEditor.jsx
import { useState } from 'react'

function PostEditor({ post, onSave, onCancel }) {
  const [title, setTitle] = useState(post?.title || '')
  const [content, setContent] = useState(post?.content || '')
  
  const handleSubmit = (e) => {
    e.preventDefault()
    onSave(post?.id, { title, content })
  }
  
  return (
    <div className="post-editor">
      <h2>{post ? 'Edit Post' : 'Create New Post'}</h2>
      
      <form onSubmit={handleSubmit}>
        <div className="form-group">
          <label>Title</label>
          <input 
            type="text"
            value={title}
            onChange={(e) => setTitle(e.target.value)}
            required
          />
        </div>
        
        <div className="form-group">
          <label>Content</label>
          <textarea 
            value={content}
            onChange={(e) => setContent(e.target.value)}
            rows="15"
            required
          />
        </div>
        
        <div className="form-actions">
          <button type="submit" className="btn-primary">
            {post ? 'Update' : 'Create'} Post
          </button>
          <button type="button" onClick={onCancel} className="btn-secondary">
            Cancel
          </button>
        </div>
      </form>
    </div>
  )
}

export default PostEditor
```

## E-commerce Product Catalog

### React Product Browser

```php
<?php
// routes/web.php

$app->get('/shop', function($req, $res) {
    $categories = DB::table('categories')
        ->orderBy('name')
        ->get();
    
    $featuredProducts = DB::table('products')
        ->where('featured', 1)
        ->where('stock', '>', 0)
        ->limit(8)
        ->get();
    
    return $res->view('react', [
        'title' => 'Shop',
        'props' => [
            'categories' => $categories,
            'featuredProducts' => $featuredProducts,
            'config' => [
                'apiUrl' => '/api',
                'currency' => 'USD',
                'cartId' => $_SESSION['cart_id'] ?? null
            ]
        ]
    ]);
});

$app->group(['prefix' => '/api'], function($app) {
    
    $app->get('/products', function($req, $res) {
        $category = $req->query('category');
        $search = $req->query('search');
        
        $query = DB::table('products')
            ->where('stock', '>', 0);
        
        if ($category) {
            $query->where('category_id', $category);
        }
        
        if ($search) {
            $query->where('name', 'LIKE', "%{$search}%");
        }
        
        $products = $query->orderBy('name')->get();
        
        return $res->json($products);
    });
    
    $app->post('/cart/add', function($req, $res) {
        $data = $req->json();
        
        // Add to cart logic
        $cartId = $_SESSION['cart_id'] ?? createCart();
        
        DB::table('cart_items')->insert([
            'cart_id' => $cartId,
            'product_id' => $data['productId'],
            'quantity' => $data['quantity']
        ]);
        
        return $res->json(['success' => true]);
    });
});
```

```jsx
// frontend/src/Shop.jsx
import { useState, useEffect } from 'react'
import ProductCard from './components/ProductCard'

function Shop({ categories, featuredProducts, config }) {
  const [products, setProducts] = useState(featuredProducts)
  const [selectedCategory, setSelectedCategory] = useState(null)
  const [searchTerm, setSearchTerm] = useState('')
  const [loading, setLoading] = useState(false)
  
  const fetchProducts = async (category = null, search = '') => {
    setLoading(true)
    
    const params = new URLSearchParams()
    if (category) params.set('category', category)
    if (search) params.set('search', search)
    
    const response = await fetch(`${config.apiUrl}/products?${params}`)
    const data = await response.json()
    
    setProducts(data)
    setLoading(false)
  }
  
  const handleSearch = (e) => {
    e.preventDefault()
    fetchProducts(selectedCategory, searchTerm)
  }
  
  const handleAddToCart = async (productId, quantity = 1) => {
    await fetch(`${config.apiUrl}/cart/add`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ productId, quantity })
    })
    
    alert('Added to cart!')
  }
  
  return (
    <div className="shop">
      <aside className="sidebar">
        <h3>Categories</h3>
        <ul>
          <li>
            <button onClick={() => { setSelectedCategory(null); fetchProducts(); }}>
              All Products
            </button>
          </li>
          {categories.map(cat => (
            <li key={cat.id}>
              <button onClick={() => { setSelectedCategory(cat.id); fetchProducts(cat.id); }}>
                {cat.name}
              </button>
            </li>
          ))}
        </ul>
      </aside>
      
      <main>
        <div className="search-bar">
          <form onSubmit={handleSearch}>
            <input 
              type="search"
              placeholder="Search products..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />
            <button type="submit">Search</button>
          </form>
        </div>
        
        {loading ? (
          <div className="loading">Loading...</div>
        ) : (
          <div className="products-grid">
            {products.map(product => (
              <ProductCard 
                key={product.id}
                product={product}
                currency={config.currency}
                onAddToCart={() => handleAddToCart(product.id)}
              />
            ))}
          </div>
        )}
      </main>
    </div>
  )
}

export default Shop
```

## Multi-Page Application with Shared Layout

```php
<?php
// routes/web.php

use YAFS\View\View;

// Share common data
View::share('app_name', 'My Application');
View::share('nav_items', [
    ['url' => '/', 'label' => 'Home'],
    ['url' => '/about', 'label' => 'About'],
    ['url' => '/services', 'label' => 'Services'],
    ['url' => '/contact', 'label' => 'Contact']
]);

$app->get('/', function($req, $res) {
    return $res->view('pages/home', [
        'title' => 'Home',
        'hero_title' => 'Welcome to Our Site',
        'hero_text' => 'We build amazing things'
    ]);
});

$app->get('/about', function($req, $res) {
    $team = DB::table('team_members')
        ->orderBy('position')
        ->get();
    
    return $res->view('pages/about', [
        'title' => 'About Us',
        'team' => $team
    ]);
});

$app->get('/services', function($req, $res) {
    $services = DB::table('services')
        ->where('active', 1)
        ->get();
    
    return $res->view('pages/services', [
        'title' => 'Our Services',
        'services' => $services
    ]);
});
```

```php
// views/layouts/app.php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'My App') ?> - <?= $app_name ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1><?= htmlspecialchars($app_name) ?></h1>
            <nav>
                <?php foreach ($nav_items as $item): ?>
                    <a href="<?= htmlspecialchars($item['url']) ?>">
                        <?= htmlspecialchars($item['label']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </header>
    
    <main>
        <?= $content ?? '' ?>
    </main>
    
    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($app_name) ?>. All rights reserved.</p>
        </div>
    </footer>
    
    <script src="/assets/js/app.js"></script>
</body>
</html>
```

---

These examples demonstrate practical patterns for building real applications with YAFS. Mix and match based on your needs!

Note: These examples are for reference only and may differ slightly from the latest version. A stable release with working examples will be available soon. Meanwhile, check out our ready-to-use [boilerplate templates](https://github.com/DeveloperKeshavKumar/YAFS/tree/main/examples) for API-only, Templates, and Full-Stack React+PHP setups.