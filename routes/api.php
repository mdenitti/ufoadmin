<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AlienController;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
| complete prefex of api encapsulated
*/


// Default included api test routes - notice the sanctum middleware...

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


// API routes for testing

Route::get('/test', function () {
    return response()->json([
        'message' => 'Hello World!',
    ], 200);
});

// not secure... not adding any new routes... but it is a test route
// In production it is best to remove this route and to incorporate the routine
// into the Voyager admin panel

Route::get('/token', function (Request $request) {
    $user = User::find(1);
    $token = $user->createToken('token-name')->plainTextToken;
    return response()->json([
        'token' => $token,
    ], 200);
});

Route::middleware('auth:sanctum')->group(function () {
  
   // API routes for the aliens...
   Route::get('/aliens', [AlienController::class, 'index']);
   Route::get('/alien/{id}', [AlienController::class, 'show']);
   Route::post('/aliens', [AlienController::class, 'store']);
   Route::put('/alien/{id}', [AlienController::class, 'update']);
   Route::delete('/alien/{id}', [AlienController::class, 'destroy']);

});