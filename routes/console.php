<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('postcodes:info', function (): void {
    $this->info('POSTCODES REST API – php artisan route:list --path=api');
})->purpose('API információ megjelenítése');
