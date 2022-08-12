<?php

use Illuminate\Support\Facades\Route;
use janboddez\Webmention\Http\Controllers\WebmentionController;

Route::middleware('api')
    ->post('/webmention', [WebmentionController::class, 'handle']);
