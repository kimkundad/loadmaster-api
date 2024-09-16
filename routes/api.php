<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController; // Import the AuthController

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// user
Route::post('/login',  [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify', [AuthController::class, 'verify']);

Route::get('/logout', [AuthController::class, 'logout'])->middleware("jwtAuth");
Route::post('/refresh', [AuthController::class, 'refresh'])->middleware("jwtAuth");
Route::get('/user-profile', [AuthController::class, 'getUser'])->middleware("jwtAuth");
Route::get('/user-order', [AuthController::class, 'getUserOrder'])->middleware("jwtAuth");
Route::get('/user-branch', [AuthController::class, 'getUserBranch'])->middleware("jwtAuth");
Route::get('/user-branch-{id}', [AuthController::class, 'getUserBranchID'])->middleware("jwtAuth");
Route::post('/refresh-token', [AuthController::class, 'refresh']);

