<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ChannelNotifications API Routes
|--------------------------------------------------------------------------
|
| API routes for the ChannelNotifications module.
|
*/

Route::prefix('api/channel-notifications')
    ->name('channel_notifications.api.')
    ->middleware('api')
    ->group(function (): void {
        // Define your API routes here
    });
