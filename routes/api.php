<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [App\Http\Controllers\Api\AuthController::class, 'register']);
Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);

Route::middleware('auth:api')->group(function(){
    Route::post('/pay', [App\Http\Controllers\Api\PaymentController::class, 'getPaymentAuthorizationCode'])->name('pay');
    Route::get('/payment/callback', [App\Http\Controllers\Api\PaymentController::class, 'handleGatewayCallback']);
    Route::get('/transactions/list', [App\Http\Controllers\Api\PaymentController::class, 'listTransactions']);
    Route::get('/transactions/search/{search_param}', [App\Http\Controllers\Api\PaymentController::class, 'searchTransactions']);
});