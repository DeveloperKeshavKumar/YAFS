<?php
/**
 * Web Routes
 */

// React app route will be added automatically

// API routes
$app->group(['prefix' => '/api'], function($app) {
    $app->get('/hello', function($req, $res) {
        return $res->json(['message' => 'Hello from YAFS API!']);
    });
    
    $app->get('/items', function($req, $res) {
        return $res->json([
            'items' => [
                ['id' => 1, 'name' => 'Item 1'],
                ['id' => 2, 'name' => 'Item 2'],
                ['id' => 3, 'name' => 'Item 3']
            ]
        ]);
    });
});

// React App Route
$app->get('/', function($req, $res) {
    return $res->view('react', [
        'title' => 'React App',
        'props' => [
            'title' => 'YAFS React App',
            'message' => 'Full-stack PHP + React!'
        ]
    ]);
});

