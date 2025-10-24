<?php
/**
 * API Routes
 */

// Health check
$app->get('/api/health', function($req, $res) {
    return $res->json(['status' => 'ok', 'timestamp' => time()]);
});

// Hello API
$app->get('/api/hello', function($req, $res) {
    return $res->json(['message' => 'Hello from YAFS API!']);
});

// Example CRUD routes
$app->group(['prefix' => '/api'], function($app) {
    $app->get('/items', function($req, $res) {
        return $res->json(['items' => []]);
    });
    
    $app->get('/items/:id', function($req, $res) {
        $id = $req->param('id');
        return $res->json(['id' => $id, 'name' => 'Item ' . $id]);
    });
    
    $app->post('/items', function($req, $res) {
        $data = $req->json();
        return $res->status(201)->json(['id' => rand(1, 1000), 'data' => $data]);
    });
});
