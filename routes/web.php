<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::post('/user/login', '\App\Http\Controllers\UserController@login');

Route::get('/', function () {
    if(isset(auth()->user()->id)){
        return redirect("/dashboard");
    }else{
        return view('login');
    }
});
Route::get('/register', function () {
    if(isset(auth()->user()->id)){
        return redirect("/dashboard");
    }else{
        return view('login');
    }
});

Route::get('/show2fa', '\App\Http\Controllers\Get2faController@index');
Route::get('/get2fa', '\App\Http\Controllers\Get2faController@get2fa');
Route::post('/check2fa', '\App\Http\Controllers\Get2faController@check');

Route::get('/admin', function () {
    return view('login');
})->name('admin');

Route::get('/home', function() {
    return redirect("/dashboard");
});

Route::get('/welcome', function () {
    return view('welcome');
});

Route::get('/comprovantePix/{id}', '\App\Http\Controllers\ComprovantePixController@index');

Route::get('qr/{id}/{order_id}/{dimension}', '\App\Http\Controllers\MethodsViewsController@qrPix');
Route::get('invfastpayments/{authorization}/{order_id}', '\App\Http\Controllers\MethodsViewsController@invoiceGlobal');

Route::middleware(['auth:sanctum', 'verified'])->group(function () {

    Route::get('/dashboard', '\App\Http\Controllers\DashboardController@index')->name('dashboard');
    Route::post('/dashboard', '\App\Http\Controllers\DashboardController@search')->name('dashboard');
    Route::post('/dashboard/getDash', '\App\Http\Controllers\DashboardController@getDash')->name('dashboard');

    Route::get('/transactions', '\App\Http\Controllers\TransactionsController@index')->name('transactions');
    Route::post('/transactions/search', '\App\Http\Controllers\TransactionsController@search')->name('transactions');
    Route::post('/transactions/searchfind', '\App\Http\Controllers\TransactionsController@searchfind')->name('transactions');

    Route::get('/merchants', '\App\Http\Controllers\MerchantsController@index')->name('merchants')->middleware('adminaccess');
    Route::post('/merchants/search', '\App\Http\Controllers\MerchantsController@search')->name('merchants')->middleware('adminaccess');
    Route::get('/merchants/create', '\App\Http\Controllers\MerchantsController@create')->name('merchants')->middleware('adminaccess');
    Route::post('/merchants/store', '\App\Http\Controllers\MerchantsController@store')->name('merchants')->middleware('adminaccess');
    Route::put('/merchants/{client}/update', '\App\Http\Controllers\MerchantsController@update')->name('merchants')->middleware('adminaccess');
    Route::get('/merchants/{client}', '\App\Http\Controllers\MerchantsController@show')->name('merchants')->middleware('adminaccess');
    Route::get('/merchants/{client}/edit', '\App\Http\Controllers\MerchantsController@edit')->name('merchants')->middleware('adminaccess');

    Route::get('/merchants/update_api_keys/{id}', '\App\Http\Controllers\MerchantsController@update_api_keys')->name('merchants'); //url callback withdraw
    Route::post('/merchants/update_webhook', '\App\Http\Controllers\MerchantsController@update_webhook')->name('merchants'); //url callback withdraw
    Route::get('/download-postman-collection', '\App\Http\Controllers\MerchantsController@postmanCollection');

    Route::post('merchants/charge', '\App\Http\Controllers\MerchantsController@charge')->name('merchants');
    Route::post('merchants/sendPix', '\App\Http\Controllers\MerchantsController@sendPix')->name('merchants');

    Route::get('/myinfo', '\App\Http\Controllers\MerchantsController@info')->name('merchants');

    Route::get('/clientapi', '\App\Http\Controllers\MerchantsController@api')->name('clientapi'); // Merchant API

    Route::get('/banks', '\App\Http\Controllers\BanksController@index')->name('banks')->middleware('adminaccess');
    Route::get('/banks/create', '\App\Http\Controllers\BanksController@create')->name('banks')->middleware('adminaccess');
    Route::post('/banks/store', '\App\Http\Controllers\BanksController@store')->name('banks')->middleware('adminaccess');
    Route::post('/banks/updateClients', '\App\Http\Controllers\BanksController@updateClients')->name('banks')->middleware('adminaccess');
    Route::post('/banks/updateClientsData', '\App\Http\Controllers\BanksController@updateClientsData')->name('banks')->middleware('adminaccess');
    Route::post('/banks/getBank', '\App\Http\Controllers\BanksController@getBank')->name('banks')->middleware('adminaccess');
    Route::get('/banks/exportPayin/{id}', '\App\Http\Controllers\BanksController@exportPayin')->name('banks')->middleware('adminaccess');
    Route::get('/banks/{bank}/freeze', '\App\Http\Controllers\BanksController@freeze')->name('banks')->middleware('adminaccess');

    Route::get('/banks/getBalanceCelcoin', '\App\Http\Controllers\BanksController@getBalanceCelcoin');

    Route::get('/logs', '\App\Http\Controllers\LogsController@index')->name('logs')->middleware('adminaccess');
    Route::post('/logs/search', '\App\Http\Controllers\LogsController@search')->name('logs')->middleware('adminaccess');

    Route::get('/user/{user}/freeze', '\App\Http\Controllers\UserController@freeze');
    Route::resource('/users', '\App\Http\Controllers\UserController');

    Route::get('/user/2fa', '\App\Http\Controllers\UserController@user2fa');
    Route::post('/user/save2fa', '\App\Http\Controllers\UserController@save2fa');

    Route::post('/resendCallback', '\App\Http\Controllers\InternalController@resendCallback');

    Route::middleware(['check_access'])->group(function () {
        Route::get('/solicitation-withdrawal/', '\App\Http\Controllers\WithdrawManualController@index')->name("withdrawal.index");
        Route::get('/solicitation-withdrawal/{id}/getWithdraw', '\App\Http\Controllers\WithdrawManualController@getWithdraw')->name("withdrawal.index");
        Route::post('/solicitation-withdrawal/search', '\App\Http\Controllers\WithdrawManualController@search')->name("withdrawal.index");
        Route::post('/approveWithdrawBatch', '\App\Http\Controllers\WithdrawManualController@approveWithdrawBatch')->name("withdrawal.index");
    });

    Route::post('/withdrawal/newWithdraw', '\App\Http\Controllers\WithdrawManualController@newWithdraw')->name('withdrawal');
    Route::post('/withdrawal/search', '\App\Http\Controllers\WithdrawalController@search')->name('withdrawal');
    Route::post('/withdrawal/approve', '\App\Http\Controllers\WithdrawalController@approve')->name('withdrawal');
    Route::post('/withdrawal/updateComission', '\App\Http\Controllers\WithdrawalController@updateComission')->name('withdrawal');
    Route::post('/withdrawal/reportError', '\App\Http\Controllers\WithdrawalController@reportError')->name('withdrawal');
    Route::post('/withdrawal/delete', '\App\Http\Controllers\WithdrawalController@delete')->name('withdrawal');
    Route::post('/withdrawal/split', '\App\Http\Controllers\WithdrawalController@split')->name('withdrawal');
    Route::post('/withdrawal/pdf', '\App\Http\Controllers\WithdrawalController@pdf')->name('withdrawal');
    Route::post('/withdrawal/approvePixBatch', '\App\Http\Controllers\WithdrawalController@approvePixBatch')->name('withdrawal');
    Route::post('/withdrawal/sendPixBatch', '\App\Http\Controllers\WithdrawalController@sendPixBatch')->name('withdrawal');

    Route::post('/withdrawal/createWithdrawCelcoin', '\App\Http\Controllers\WithdrawController@createWithdrawCelcoin')->name('withdrawal');

    Route::post('/approveDeposit/search', '\App\Http\Controllers\ApproveDepositController@search');
    Route::post('/approveDeposit/aproove', '\App\Http\Controllers\ApproveDepositController@aproove');
    Route::post('/approveDeposit/cancel', '\App\Http\Controllers\ApproveDepositController@reportError');
    Route::post('/approveDeposit/user', '\App\Http\Controllers\ApproveDepositController@getUser');

    // Resources routes
    Route::resource('/withdrawal', '\App\Http\Controllers\WithdrawalController');
    Route::resource('/approveDeposit', '\App\Http\Controllers\ApproveDepositController')->middleware('adminaccess');



});
