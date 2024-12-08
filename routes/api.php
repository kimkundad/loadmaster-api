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
Route::post('/sendOtp', [AuthController::class, 'sendOtp']);
Route::post('/sms', [AuthController::class, 'sms']);
Route::post('/reverify', [AuthController::class, 'reverify']);
Route::post('/reserPass', [AuthController::class, 'reserPass']);
Route::get('/getSetting', [AuthController::class, 'getSetting']);
Route::get('/getNews', [AuthController::class, 'getNews']);
Route::get('/getHoliday', [AuthController::class, 'getHoliday']);
Route::get('/getNewsById/{id}', [AuthController::class, 'getNewsById']);
Route::post('/saveToken', [AuthController::class, 'saveToken']);


Route::get('/logout', [AuthController::class, 'logout'])->middleware("jwtAuth");
Route::post('/refresh', [AuthController::class, 'refresh'])->middleware("jwtAuth");
Route::get('/user-profile', [AuthController::class, 'getUser'])->middleware("jwtAuth");
Route::get('/user-order', [AuthController::class, 'getUserOrder'])->middleware("jwtAuth");
Route::get('/user-order-cus', [AuthController::class, 'getUserOrderCus'])->middleware("jwtAuth");
Route::get('/user-order-success', [AuthController::class, 'getUserOrderSuccess'])->middleware("jwtAuth");
Route::get('/user-pay-history', [AuthController::class, 'getPayhistory'])->middleware("jwtAuth");
Route::get('/user-pay-historyById/{id}', [AuthController::class, 'getPayhistoryById'])->middleware("jwtAuth");

Route::get('/user-branch', [AuthController::class, 'getUserBranch'])->middleware("jwtAuth");
Route::get('/user-branch-{id}', [AuthController::class, 'getUserBranchID'])->middleware("jwtAuth");
Route::post('/refresh-token', [AuthController::class, 'refresh']);
Route::post('/user-branch-create', [AuthController::class, 'userBranchCreate'])->middleware("jwtAuth");
Route::get('/getOrderByID/{id}', [AuthController::class, 'getOrderByID'])->middleware("jwtAuth");
Route::post('/checkQrcode', [AuthController::class, 'checkQrcode'])->middleware("jwtAuth");
Route::post('/createOrdere', [AuthController::class, 'createOrdere'])->middleware("jwtAuth");
Route::post('/getProvince', [AuthController::class, 'getProvince']);
Route::post('/myLocation', [AuthController::class, 'myLocation'])->middleware("jwtAuth");
Route::post('/generate-pdf', [AuthController::class, 'generatePDF'])->middleware("jwtAuth");
Route::post('/generatePDFtoMail', [AuthController::class, 'generatePDFtoMail'])->middleware("jwtAuth");

Route::post('/notiStatus', [AuthController::class, 'notiStatus'])->middleware("jwtAuth");
Route::post('/cancelInvoice', [AuthController::class, 'cancelInvoice'])->middleware("jwtAuth");
Route::post('/updateReceipt', [AuthController::class, 'updateReceipt'])->middleware("jwtAuth");
Route::post('/postPayment', [AuthController::class, 'postPayment'])->middleware("jwtAuth");
Route::post('/PostRatting', [AuthController::class, 'PostRatting'])->middleware("jwtAuth");

Route::get('/getNotiNew', [AuthController::class, 'getNotiNew'])->middleware("jwtAuth");


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
Route::get('/getOrderByIDDri/{id}', [AuthController::class, 'getOrderByIDDri'])->middleware("jwtAuth");
Route::post('/postNotiDri', [AuthController::class, 'postNotiDri'])->middleware("jwtAuth");
Route::post('/postDoc', [AuthController::class, 'postDoc'])->middleware("jwtAuth");
Route::get('/getImgDoc/{id}', [AuthController::class, 'getImgDoc'])->middleware("jwtAuth");
Route::get('/getDoc', [AuthController::class, 'getDoc'])->middleware("jwtAuth");
Route::post('/UpAvatar', [AuthController::class, 'UpAvatar'])->middleware("jwtAuth");

Route::get('/createRooms', [AuthController::class, 'createRoom'])->middleware("jwtAuth");
Route::get('/chat-history', [AuthController::class, 'fetchChatHistory'])->middleware("jwtAuth");
Route::post('/storeMessage', [AuthController::class, 'storeMessage']);
