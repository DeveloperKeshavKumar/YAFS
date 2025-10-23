# YAFS Query Builder Examples

Real-world examples demonstrating database operations with the Query Builder.

## User Management System

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

class UserService
{
    /**
     * Get all active users
     */
    public static function getActiveUsers(): array
    {
        return DB::table('users')
            ->where('status', 'active')
            ->whereNotNull('email_verified_at')
            ->orderBy('created_at', 'desc')
            ->get();
    }
    
    /**
     * Find user by email
     */
    public static function findByEmail(string $email): ?array
    {
        return DB::table('users')
            ->where('email', $email)
            ->first();
    }
    
    /**
     * Create new user
     */
    public static function create(array $data): string
    {
        return DB::table('users')->insertGetId([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Update user profile
     */
    public static function updateProfile(int $userId, array $data): int
    {
        return DB::table('users')
            ->where('id', $userId)
            ->update([
                'name' => $data['name'],
                'bio' => $data['bio'],
                'updated_at' => date('Y-m-d H:i:s')
            ]);
    }
    
    /**
     * Activate user account
     */
    public static function activate(int $userId): void
    {
        DB::table('users')
            ->where('id', $userId)
            ->update([
                'status' => 'active',
                'email_verified_at' => date('Y-m-d H:i:s')
            ]);
    }
    
    /**
     * Soft delete user
     */
    public static function softDelete(int $userId): int
    {
        return DB::table('users')
            ->where('id', $userId)
            ->update(['deleted_at' => date('Y-m-d H:i:s')]);
    }
    
    /**
     * Permanently delete user
     */
    public static function permanentDelete(int $userId): int
    {
        return DB::table('users')
            ->where('id', $userId)
            ->delete();
    }
    
    /**
     * Get user statistics
     */
    public static function getStats(): array
    {
        return [
            'total' => DB::table('users')->count(),
            'active' => DB::table('users')->where('status', 'active')->count(),
            'pending' => DB::table('users')->where('status', 'pending')->count(),
            'newest' => DB::table('users')->max('created_at')
        ];
    }
    
    /**
     * Search users
     */
    public static function search(string $query): array
    {
        return DB::table('users')
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->limit(20)
            ->get();
    }
}
```

## Blog System with Posts and Comments

```php
<?php

use YAFS\Database\DB;

class BlogService
{
    /**
     * Get published posts with author info
     */
    public static function getPublishedPosts(int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        
        return DB::table('posts')
            ->join('users', 'posts.user_id', '=', 'users.id')
            ->select('posts.*', 'users.name as author_name', 'users.email as author_email')
            ->where('posts.status', 'published')
            ->whereNotNull('posts.published_at')
            ->orderBy('posts.published_at', 'desc')
            ->limit($perPage)
            ->offset($offset)
            ->get();
    }
    
    /**
     * Get single post with comments count
     */
    public static function getPost(string $slug): ?array
    {
        $post = DB::table('posts')
            ->join('users', 'posts.user_id', '=', 'users.id')
            ->select('posts.*', 'users.name as author_name')
            ->where('posts.slug', $slug)
            ->first();
        
        if ($post) {
            // Get comment count
            $post['comment_count'] = DB::table('comments')
                ->where('post_id', $post['id'])
                ->where('approved', 1)
                ->count();
            
            // Increment view count
            DB::table('posts')
                ->where('id', $post['id'])
                ->increment('view_count');
        }
        
        return $post;
    }
    
    /**
     * Create new post
     */
    public static function createPost(int $userId, array $data): string
    {
        return DB::table('posts')->insertGetId([
            'user_id' => $userId,
            'title' => $data['title'],
            'slug' => self::generateSlug($data['title']),
            'content' => $data['content'],
            'excerpt' => $data['excerpt'] ?? null,
            'status' => 'draft',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Publish post
     */
    public static function publishPost(int $postId): void
    {
        DB::table('posts')
            ->where('id', $postId)
            ->update([
                'status' => 'published',
                'published_at' => date('Y-m-d H:i:s')
            ]);
    }
    
    /**
     * Get comments for post
     */
    public static function getComments(int $postId): array
    {
        return DB::table('comments')
            ->join('users', 'comments.user_id', '=', 'users.id')
            ->select('comments.*', 'users.name as author_name')
            ->where('comments.post_id', $postId)
            ->where('comments.approved', 1)
            ->orderBy('comments.created_at', 'asc')
            ->get();
    }
    
    /**
     * Add comment
     */
    public static function addComment(int $postId, int $userId, string $content): string
    {
        return DB::table('comments')->insertGetId([
            'post_id' => $postId,
            'user_id' => $userId,
            'content' => $content,
            'approved' => 0, // Requires approval
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Get posts by tag
     */
    public static function getPostsByTag(string $tag): array
    {
        return DB::table('posts')
            ->join('post_tags', 'posts.id', '=', 'post_tags.post_id')
            ->join('tags', 'post_tags.tag_id', '=', 'tags.id')
            ->select('posts.*')
            ->where('tags.slug', $tag)
            ->where('posts.status', 'published')
            ->orderBy('posts.published_at', 'desc')
            ->get();
    }
    
    /**
     * Get popular posts
     */
    public static function getPopularPosts(int $limit = 5): array
    {
        return DB::table('posts')
            ->select('id', 'title', 'slug', 'view_count')
            ->where('status', 'published')
            ->orderBy('view_count', 'desc')
            ->limit($limit)
            ->get();
    }
    
    private static function generateSlug(string $title): string
    {
        return strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $title));
    }
}
```

## E-commerce Order System

```php
<?php

use YAFS\Database\DB;

class OrderService
{
    /**
     * Create order with items (transaction)
     */
    public static function createOrder(int $userId, array $items, array $shippingInfo): string
    {
        try {
            DB::beginTransaction();
            
            // Calculate total
            $total = array_reduce($items, function($sum, $item) {
                return $sum + ($item['price'] * $item['quantity']);
            }, 0);
            
            // Create order
            $orderId = DB::table('orders')->insertGetId([
                'user_id' => $userId,
                'status' => 'pending',
                'total' => $total,
                'shipping_address' => $shippingInfo['address'],
                'shipping_city' => $shippingInfo['city'],
                'shipping_zip' => $shippingInfo['zip'],
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Add order items
            $orderItems = array_map(function($item) use ($orderId) {
                return [
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price']
                ];
            }, $items);
            
            DB::table('order_items')->insert($orderItems);
            
            // Update product stock
            foreach ($items as $item) {
                DB::table('products')
                    ->where('id', $item['product_id'])
                    ->decrement('stock', $item['quantity']);
            }
            
            // Clear cart
            DB::table('cart_items')
                ->where('user_id', $userId)
                ->delete();
            
            DB::commit();
            
            return $orderId;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Get user orders
     */
    public static function getUserOrders(int $userId): array
    {
        return DB::table('orders')
            ->select('id', 'status', 'total', 'created_at')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
    
    /**
     * Get order details with items
     */
    public static function getOrderDetails(int $orderId): ?array
    {
        $order = DB::table('orders')
            ->where('id', $orderId)
            ->first();
        
        if ($order) {
            $order['items'] = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->select(
                    'order_items.*',
                    'products.name',
                    'products.image'
                )
                ->where('order_items.order_id', $orderId)
                ->get();
        }
        
        return $order;
    }
    
    /**
     * Update order status
     */
    public static function updateStatus(int $orderId, string $status): void
    {
        $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid order status");
        }
        
        DB::table('orders')
            ->where('id', $orderId)
            ->update([
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
    }
    
    /**
     * Get orders by status
     */
    public static function getOrdersByStatus(string $status): array
    {
        return DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->select('orders.*', 'users.name as customer_name', 'users.email as customer_email')
            ->where('orders.status', $status)
            ->orderBy('orders.created_at', 'desc')
            ->get();
    }
    
    /**
     * Get sales statistics
     */
    public static function getSalesStats(string $startDate, string $endDate): array
    {
        return [
            'total_orders' => DB::table('orders')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            
            'total_revenue' => DB::table('orders')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereIn('status', ['delivered', 'shipped'])
                ->sum('total'),
            
            'avg_order_value' => DB::table('orders')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->avg('total'),
            
            'pending_orders' => DB::table('orders')
                ->where('status', 'pending')
                ->count()
        ];
    }
    
    /**
     * Get best selling products
     */
    public static function getBestSellers(int $limit = 10): array
    {
        return DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select(
                'products.id',
                'products.name',
                'SUM(order_items.quantity) as total_sold',
                'SUM(order_items.quantity * order_items.price) as total_revenue'
            )
            ->groupBy('products.id')
            ->orderBy('total_sold', 'desc')
            ->limit($limit)
            ->get();
    }
}
```

## Analytics and Reporting

```php
<?php

use YAFS\Database\DB;

class AnalyticsService
{
    /**
     * Get daily active users
     */
    public static function getDailyActiveUsers(string $date): int
    {
        return DB::table('user_activity')
            ->where('activity_date', $date)
            ->count('DISTINCT user_id');
    }
    
    /**
     * Get user engagement metrics
     */
    public static function getUserEngagement(string $startDate, string $endDate): array
    {
        return DB::table('users')
            ->leftJoin('posts', 'users.id', '=', 'posts.user_id')
            ->leftJoin('comments', 'users.id', '=', 'comments.user_id')
            ->select(
                'users.id',
                'users.name',
                'COUNT(DISTINCT posts.id) as post_count',
                'COUNT(DISTINCT comments.id) as comment_count'
            )
            ->whereBetween('users.created_at', [$startDate, $endDate])
            ->groupBy('users.id')
            ->having('post_count', '>', 0)
            ->orderBy('post_count', 'desc')
            ->get();
    }
    
    /**
     * Get revenue by month
     */
    public static function getMonthlyRevenue(int $year): array
    {
        $results = DB::select("
            SELECT 
                MONTH(created_at) as month,
                SUM(total) as revenue,
                COUNT(*) as order_count
            FROM orders
            WHERE YEAR(created_at) = ?
              AND status IN ('delivered', 'shipped')
            GROUP BY MONTH(created_at)
            ORDER BY month
        ", [$year]);
        
        return $results;
    }
    
    /**
     * Get top customers
     */
    public static function getTopCustomers(int $limit = 10): array
    {
        return DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                'COUNT(orders.id) as order_count',
                'SUM(orders.total) as total_spent'
            )
            ->where('orders.status', '!=', 'cancelled')
            ->groupBy('users.id')
            ->orderBy('total_spent', 'desc')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Get conversion funnel
     */
    public static function getConversionFunnel(): array
    {
        return [
            'visitors' => DB::table('page_views')
                ->count('DISTINCT session_id'),
            
            'signups' => DB::table('users')
                ->whereNotNull('created_at')
                ->count(),
            
            'first_purchase' => DB::table('orders')
                ->join('users', 'orders.user_id', '=', 'users.id')
                ->select('orders.user_id')
                ->groupBy('orders.user_id')
                ->having('COUNT(*)', '=', 1)
                ->count(),
            
            'repeat_customers' => DB::table('orders')
                ->select('user_id')
                ->groupBy('user_id')
                ->having('COUNT(*)', '>', 1)
                ->count()
        ];
    }
}
```

## Multi-Tenant Application

```php
<?php

use YAFS\Database\DB;

class TenantService
{
    private static ?int $currentTenantId = null;
    
    /**
     * Set current tenant context
     */
    public static function setTenant(int $tenantId): void
    {
        self::$currentTenantId = $tenantId;
    }
    
    /**
     * Get current tenant ID
     */
    public static function getCurrentTenantId(): ?int
    {
        return self::$currentTenantId;
    }
    
    /**
     * Get tenant-scoped query builder
     */
    private static function tenantQuery(string $table): \YAFS\Database\QueryBuilder
    {
        if (self::$currentTenantId === null) {
            throw new \RuntimeException("No tenant context set");
        }
        
        return DB::table($table)
            ->where('tenant_id', self::$currentTenantId);
    }
    
    /**
     * Get tenant users
     */
    public static function getUsers(): array
    {
        return self::tenantQuery('users')
            ->select('id', 'name', 'email', 'role')
            ->orderBy('name')
            ->get();
    }
    
    /**
     * Create user in tenant
     */
    public static function createUser(array $data): string
    {
        $data['tenant_id'] = self::$currentTenantId;
        return DB::table('users')->insertGetId($data);
    }
    
    /**
     * Get tenant statistics
     */
    public static function getStats(): array
    {
        return [
            'users' => self::tenantQuery('users')->count(),
            'projects' => self::tenantQuery('projects')->count(),
            'tasks' => self::tenantQuery('tasks')->count(),
            'storage_used' => self::tenantQuery('files')->sum('size')
        ];
    }
    
    /**
     * Switch database connection for tenant
     */
    public static function switchTenantDatabase(int $tenantId): void
    {
        $tenant = DB::table('tenants')
            ->where('id', $tenantId)
            ->first();
        
        if (!$tenant) {
            throw new \RuntimeException("Tenant not found");
        }
        
        // Add tenant-specific connection
        DB::addConnection([
            'host' => $tenant['db_host'],
            'database' => $tenant['db_name'],
            'username' => $tenant['db_user'],
            'password' => $tenant['db_password']
        ], "tenant_{$tenantId}");
        
        self::$currentTenantId = $tenantId;
    }
}
```

## Batch Operations

```php
<?php

use YAFS\Database\DB;

class BatchService
{
    /**
     * Bulk update user statuses
     */
    public static function bulkUpdateStatus(array $userIds, string $status): int
    {
        return DB::table('users')
            ->whereIn('id', $userIds)
            ->update(['status' => $status]);
    }
    
    /**
     * Bulk insert with chunking for large datasets
     */
    public static function bulkInsertChunked(string $table, array $records, int $chunkSize = 1000): void
    {
        $chunks = array_chunk($records, $chunkSize);
        
        foreach ($chunks as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }
    
    /**
     * Delete old records
     */
    public static function deleteOldRecords(string $table, string $dateColumn, int $daysOld): int
    {
        $cutoffDate = date('Y-m-d', strtotime("-{$daysOld} days"));
        
        return DB::table($table)
            ->where($dateColumn, '<', $cutoffDate)
            ->delete();
    }
    
    /**
     * Archive old data
     */
    public static function archiveOldData(int $daysOld): void
    {
        try {
            DB::beginTransaction();
            
            $cutoffDate = date('Y-m-d', strtotime("-{$daysOld} days"));
            
            // Get records to archive
            $records = DB::table('orders')
                ->where('created_at', '<', $cutoffDate)
                ->where('status', 'delivered')
                ->get();
            
            if (!empty($records)) {
                // Insert into archive
                DB::table('orders_archive')->insert($records);
                
                // Delete from main table
                $orderIds = array_column($records, 'id');
                DB::table('orders')
                    ->whereIn('id', $orderIds)
                    ->delete();
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
```

---

These examples demonstrate common patterns for building robust database-driven applications with YAFS.