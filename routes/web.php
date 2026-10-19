<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json([
    'name' => 'Postcodes REST API',
    'api' => '/api/cities',
    'documentation' => '/apidoc/index.html',
]));
