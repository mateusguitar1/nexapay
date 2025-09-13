<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthClientDepositGetAPI;

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

/**
 * Authentication User
 */

Route::post('/authenticator','App\Http\Controllers\AuthenticationAPI@login');

 /**
  * End Authentication
  */

  Route::post('/sendpayment', 'App\Http\Controllers\REST\InternalControllerAPI@sendPayment');

/**
 * REST APIS
 */

 Route::middleware('auth:sanctum')->group(function (){

    //Logout
    Route::post('/logout', 'App\Http\Controllers\AuthenticationAPI@logout');

    //Dashboard
    Route::get('/getDashboard', 'App\Http\Controllers\REST\DashboardControllerAPI@index');
    Route::post('/getDashboard', 'App\Http\Controllers\REST\DashboardControllerAPI@search');

    //Transactions
    Route::get('/getTransactions', 'App\Http\Controllers\REST\TransactionsControllerAPI@index');
    Route::post('/getTransactions', 'App\Http\Controllers\REST\TransactionsControllerAPI@search');

    //Banks
    Route::get('/getBanks', 'App\Http\Controllers\REST\BanksControllerAPI@index');
    Route::get('/bank', 'App\Http\Controllers\REST\BanksControllerAPI@getBank');
    Route::post('/bank', 'App\Http\Controllers\REST\BanksControllerAPI@store');
    Route::put('/bank/{bank}', 'App\Http\Controllers\REST\BanksControllerAPI@update');
    Route::put('/bankMerchants', 'App\Http\Controllers\REST\BanksControllerAPI@updateClients');

    //Merchants
    Route::get('/merchants', 'App\Http\Controllers\REST\MerchantsControllerAPI@index');
    Route::post('/merchants', 'App\Http\Controllers\REST\MerchantsControllerAPI@search');
    Route::post('/merchant', 'App\Http\Controllers\REST\MerchantsControllerAPI@store');
    Route::put('/merchant/{client}', 'App\Http\Controllers\REST\MerchantsControllerAPI@update');
    Route::get('/merchant/{client}', 'App\Http\Controllers\REST\MerchantsControllerAPI@get');
    Route::put('/updateToken/{client}', 'App\Http\Controllers\REST\MerchantsControllerAPI@update_api_keys');
    Route::put('/updateCallback/{client}', 'App\Http\Controllers\REST\MerchantsControllerAPI@update_webhook');

    Route::get('/withdrawrest', 'App\Http\Controllers\REST\WithdrawControllerAPI@index');
    Route::post('/withdrawrest', 'App\Http\Controllers\REST\WithdrawControllerAPI@search');
    Route::post('/createwithdrawrest', 'App\Http\Controllers\REST\WithdrawControllerAPI@create');
    Route::delete('/deletewithdrawrest',  'App\Http\Controllers\REST\WithdrawControllerAPI@delete');

    Route::get('/user', 'App\Http\Controllers\REST\UserControllerAPI@user');
    Route::get('/getUser', 'App\Http\Controllers\REST\UserControllerAPI@getUser');
    Route::get('/users', 'App\Http\Controllers\REST\UserControllerAPI@index');
    Route::post('/users', 'App\Http\Controllers\REST\UserControllerAPI@store');
    Route::put('/users', 'App\Http\Controllers\REST\UserControllerAPI@update');
    Route::delete('/users', 'App\Http\Controllers\REST\UserControllerAPI@destroy');

    Route::get('/logs', 'App\Http\Controllers\REST\LogsControllerAPI@index');
    Route::post('/logs', 'App\Http\Controllers\REST\LogsControllerAPI@search');

    Route::post('/sendcallback', 'App\Http\Controllers\REST\InternalControllerAPI@sendCallback');

});

/**
 * End REST APIS
 */

/**
 * DEPOSIT TRANSACTIONS V3
 */

Route::post('/deposit', [
    'uses' => '\App\Http\Controllers\DepositController@create',
    'middleware' => ['authclientdepositpostapiv3','checkdatadepositapiv3']
]);

Route::get('/deposit', [
    'uses' => '\App\Http\Controllers\DepositController@get',
    'middleware' => 'authclientdepositgetapi'
]);

Route::delete('/deposit', [
    'uses' => '\App\Http\Controllers\DepositController@delete',
    'middleware' => ['authclientdepositpostapiv3']
]);

Route::post('/withdraw', [
    'uses' => '\App\Http\Controllers\WithdrawController@create',
    'middleware' => ['authclientwithdrawpostapiv3','checkdatawithdrawapiv3']
]);

Route::get('/withdraw', [
    'uses' => '\App\Http\Controllers\WithdrawController@get',
    'middleware' => ['authclientwithdrawpostapiv3']
]);

Route::post('/sendCallbackMerchant', [
    'uses' => '\App\Http\Controllers\SendCallbackController@index',
]);

/**
 * END DEPOSIT TRANSACTION V3
 */

 /**
 * Callback
 */
Route::post('/approvecallback', [
    'uses' => '\App\Http\Controllers\ApproveCallbackController@sendCallback'
]);

/**
 * WEBHOOKS
 */


Route::post('/boletos', [
    'uses' => '\App\Http\Controllers\WebhookController@webhookBS2'
]);

Route::post('/pixbs2', [
    'uses' => '\App\Http\Controllers\WebhookController@pixBS2'
]);

Route::post('/extrato', [
    'uses' => '\App\Http\Controllers\WebhookController@webhookBS2Extrato'
]);

Route::post('/hubapihook', [
    'uses' => '\App\Http\Controllers\WebhookController@hubapihook'
]);

Route::post('/volutihook', [
    'uses' => '\App\Http\Controllers\WebhookController@volutiWebhook'
]);

Route::post('/volutihookcc', [
    'uses' => '\App\Http\Controllers\WebhookController@volutiWebhookcc'
]);

Route::post('/volutihookdeposit', [
    'uses' => '\App\Http\Controllers\WebhookController@volutiNewWebhookDeposit'
]);

Route::post('/volutihookwithdraw', [
    'uses' => '\App\Http\Controllers\WebhookController@volutiNewWebhookWithdraw'
]);

Route::post('/luxtakhook', [
    'uses' => '\App\Http\Controllers\WebhookController@luxtakWebhook'
]);

Route::post('/luxtakwithdrawhook', [
    'uses' => '\App\Http\Controllers\WebhookController@luxtakWebhookWithdraw'
]);

Route::post('/openpixwebhook', [
    'uses' => '\App\Http\Controllers\WebhookController@openPixWebhook'
]);

Route::post('/asaaswebhook', [
    'uses' => '\App\Http\Controllers\WebhookController@asaasWebhook'
]);

Route::post('/celcoinwebhook', [
    'uses' => '\App\Http\Controllers\WebhookController@celcoinWebhook'
]);

Route::post('/shipaywebhook', [
    'uses' => '\App\Http\Controllers\WebhookController@shipaywebhook'
]);

Route::post('/accountpf', [
    'uses' => '\App\Http\Controllers\AccountPFController@store',
    'middleware' => ['authclientdepositpostapiv3']
]);

Route::post('/createpix', [
    'uses' => '\App\Http\Controllers\AccountPFController@createPixKey',
    'middleware' => ['authclientdepositpostapiv3']
]);

Route::put('/accountpf', [
    'uses' => '\App\Http\Controllers\AccountPFController@update',
    'middleware' => ['authclientdepositpostapiv3']
]);

Route::post('/accountpj', [
    'uses' => '\App\Http\Controllers\AccountPJController@store',
    'middleware' => ['authclientdepositpostapiv3']
]);

Route::post('/kyc-pj', [
    'uses' => '\App\Http\Controllers\AccountPJController@sendCelcoinKYC',
    'middleware' => ['authclientdepositpostapiv3']
]);

Route::post('/getstatusaccount', [
    'uses' => '\App\Http\Controllers\AccountPJController@getStatusAccount',
    'middleware' => ['authclientdepositpostapiv3']
]);

Route::put('/accountpj', [
    'uses' => '\App\Http\Controllers\AccountPJController@update',
    'middleware' => ['authclientdepositpostapiv3']
]);

Route::post('/transferpix', [
    'uses' => '\App\Http\Controllers\InternalController@transferpix',
    'middleware' => ['authclientdepositpostapiv3']
]);

Route::get('/checkpix', [
    'uses' => '\App\Http\Controllers\InternalController@checkpix',
    'middleware' => ['authclientdepositpostapiv3']
]);

Route::post('/createwebhook', [
    'uses' => '\App\Http\Controllers\InternalController@createwebhook',
    'middleware' => ['authclientdepositpostapiv3']
]);

Route::post('/deactivateaccount', [
    'uses' => '\App\Http\Controllers\InternalController@deactivateaccount',
    'middleware' => ['authclientdepositpostapiv3']
]);

Route::post('/deleteaccount', [
    'uses' => '\App\Http\Controllers\InternalController@deleteaccount',
    'middleware' => ['authclientdepositpostapiv3']
]);

Route::post('/refundpixcelcoin', [
    'uses' => '\App\Http\Controllers\InternalController@refundpixcelcoin',
    'middleware' => ['authclientdepositpostapiv3']
]);

Route::post('/listaccounts', [
    'uses' => '\App\Http\Controllers\AccountPJController@listAccounts',
    'middleware' => ['authclientdepositpostapiv3']
]);

Route::post('/sendaccountcelcoin', [
    'uses' => '\App\Http\Controllers\AccountPJController@sendDocumentsCelcoin',
    'middleware' => ['authclientdepositpostapiv3']
]);

// Route::post('/internaltransfervoluti', [
//     'uses' => '\App\Http\Controllers\InternalController@internalTransferVoluti',
//     'middleware' => ['authclientdepositpostapiv3']
// ]);


