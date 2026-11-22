<?php

use Illuminate\Support\Facades\Route;

it('keeps state-changing web routes behind Laravel CSRF middleware', function () {
    $route = Route::getRoutes()->getByName('admin.books.import.store');

    expect($route->gatherMiddleware())->toContain('web');
});
