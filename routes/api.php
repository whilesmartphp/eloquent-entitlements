<?php

use Illuminate\Support\Facades\Route;
use Whilesmart\Entitlements\Http\Controllers\EntitlementController;

Route::get('entitlements', [EntitlementController::class, 'show']);
Route::post('entitlements/redeem-coupon', [EntitlementController::class, 'redeem']);
