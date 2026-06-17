<?php


use App\Http\Controllers\Api\MobileController;
use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::post('auth/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('auth/verify-otp', [AuthController::class, 'verifyOtp']);


    Route::middleware(['auth:sanctum', 'active', 'force.logout'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::patch('me', [AuthController::class, 'updateProfile']);


        Route::get('devices', [MobileController::class, 'devices']);
        Route::get('devices/{device}', [MobileController::class, 'device']);
        Route::post('devices/{device}/heartbeat', [MobileController::class, 'heartbeat'])->middleware('throttle:60,1');
        Route::post('devices/{device}/location', [MobileController::class, 'location'])->middleware('throttle:120,1');
        Route::get('tracking/live/{userId}', [MobileController::class, 'live']);
        Route::get('tracking/history/{userId}/{date}', [MobileController::class, 'history']);

        Route::get('groups', [MobileController::class, 'groups']);
        Route::post('groups', [MobileController::class, 'createGroup']);
        Route::patch('groups/{group}', [MobileController::class, 'updateGroup']);
        Route::delete('groups/{group}', [MobileController::class, 'deleteGroup']);
        Route::post('pairing/redeem', [MobileController::class, 'redeemPairing']);
        Route::post('groups/{group}/pairing/refresh', [MobileController::class, 'refreshPairing']);
        Route::patch('groups/{group}/members/{userId}', [MobileController::class, 'setMemberRole']);
        Route::delete('groups/{group}/members/{userId}', [MobileController::class, 'removeMember']);

        Route::get('plans', [MobileController::class, 'plans']);
        Route::get('subscriptions', [MobileController::class, 'subscriptions']);
        Route::post('plans/{plan}/purchase', [MobileController::class, 'purchase']);
        Route::post('payments/{payment}/verify', [MobileController::class, 'verifyPayment']);
        Route::post('subscriptions/{subscription}/cancel', [MobileController::class, 'cancelSubscription']);

        Route::get('notifications', [MobileController::class, 'notifications']);
        Route::post('notifications/{id}/read', [MobileController::class, 'readNotification']);
        Route::post('push-tokens', [MobileController::class, 'pushToken']);
        Route::get('tickets', [MobileController::class, 'tickets']);
        Route::post('tickets', [MobileController::class, 'createTicket']);
        Route::post('tickets/{ticket}/replies', [MobileController::class, 'replyTicket']);
        Route::post('sos', [MobileController::class, 'triggerSos'])->middleware('throttle:10,1');
        Route::get('emergency-contacts', [MobileController::class, 'contacts']);
        Route::post('emergency-contacts', [MobileController::class, 'addContact']);
        Route::delete('emergency-contacts/{contact}', [MobileController::class, 'deleteContact']);
    });
});
