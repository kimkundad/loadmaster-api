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
Route::post('/sms', [AuthController::class, 'sms']);
Route::post('/reverify', [AuthController::class, 'reverify']);
Route::post('/reserPass', [AuthController::class, 'reserPass']);


Route::get('/logout', [AuthController::class, 'logout'])->middleware("jwtAuth");
Route::post('/refresh', [AuthController::class, 'refresh'])->middleware("jwtAuth");
Route::get('/user-profile', [AuthController::class, 'getUser'])->middleware("jwtAuth");
Route::get('/user-order', [AuthController::class, 'getUserOrder'])->middleware("jwtAuth");
Route::get('/user-branch', [AuthController::class, 'getUserBranch'])->middleware("jwtAuth");
Route::get('/user-branch-{id}', [AuthController::class, 'getUserBranchID'])->middleware("jwtAuth");
Route::post('/refresh-token', [AuthController::class, 'refresh']);
Route::post('/user-branch-create', [AuthController::class, 'userBranchCreate'])->middleware("jwtAuth");
Route::get('/getOrderByID/{id}', [AuthController::class, 'getOrderByID'])->middleware("jwtAuth");
Route::post('/checkQrcode', [AuthController::class, 'checkQrcode'])->middleware("jwtAuth");
Route::post('/createOrdere', [AuthController::class, 'createOrdere'])->middleware("jwtAuth");
Route::post('/getProvince', [AuthController::class, 'getProvince'])->middleware("jwtAuth");
Route::post('/myLocation', [AuthController::class, 'myLocation'])->middleware("jwtAuth");


//driver
Route::get('/getOrderDri', [AuthController::class, 'getOrderDri'])->middleware("jwtAuth");
Route::post('/postImgStep1', [AuthController::class, 'postImgStep1'])->middleware("jwtAuth");
Route::get('/getImgStep1/{id}', [AuthController::class, 'getImgStep1'])->middleware("jwtAuth");
Route::get('/getImgStep2/{id}', [AuthController::class, 'getImgStep2'])->middleware("jwtAuth");
Route::get('/getImgStep3/{id}', [AuthController::class, 'getImgStep3'])->middleware("jwtAuth");
Route::post('/postStatusDri', [AuthController::class, 'postStatusDri'])->middleware("jwtAuth");
Route::get('/getHistory', [AuthController::class, 'getHistory'])->middleware("jwtAuth");
Route::post('/searchOrder', [AuthController::class, 'searchOrder'])->middleware("jwtAuth");
Route::post('/updateProfile', [AuthController::class, 'updateProfile'])->middleware("jwtAuth");
Route::post('/postCancelDanger', [AuthController::class, 'postCancelDanger'])->middleware("jwtAuth");
