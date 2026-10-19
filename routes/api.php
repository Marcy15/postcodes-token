<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\CountyController;
use Illuminate\Support\Facades\Route;

Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
// A tananyagban használt /api/login cím is működik.
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');

// Nyilvános olvasás és keresés.
Route::get('cities', [CityController::class, 'index']);
Route::get('cities/postcode/{zip_code}', [CityController::class, 'byPostcode'])->whereNumber('zip_code');
Route::get('cities/{city}', [CityController::class, 'show'])->whereNumber('city');
Route::get('counties', [CountyController::class, 'index']);
Route::get('counties/{county}/cities', [CountyController::class, 'cities'])->whereNumber('county');
Route::get('counties/{county}', [CountyController::class, 'show'])->whereNumber('county');

// Minden adatváltoztatás tokenhez kötött; nincs nyilvános regisztráció.
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::post('cities', [CityController::class, 'store']);
    Route::match(['put', 'patch'], 'cities/{city}', [CityController::class, 'update'])->whereNumber('city');
    Route::delete('cities/{city}', [CityController::class, 'destroy'])->whereNumber('city');

    Route::post('counties', [CountyController::class, 'store']);
    Route::match(['put', 'patch'], 'counties/{county}', [CountyController::class, 'update'])->whereNumber('county');
    Route::delete('counties/{county}', [CountyController::class, 'destroy'])->whereNumber('county');
});
