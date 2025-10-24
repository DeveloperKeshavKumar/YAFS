<?php
/**
 * Web Routes
 */

use YAFS\View\View;

View::share('app_name', 'YAFS');

$app->get('/', function($req, $res) {
    return $res->view('welcome', ['title' => 'Welcome to YAFS']);
});

$app->get('/about', function($req, $res) {
    return $res->view('welcome', [
        'title' => 'About YAFS',
        'message' => 'A lightweight PHP framework'
    ]);
});

$app->group(['prefix' => '/api'], function($app) {
    $app->get('/hello', function($req, $res) {
        return $res->json(['message' => 'Hello from YAFS!']);
    });
});
