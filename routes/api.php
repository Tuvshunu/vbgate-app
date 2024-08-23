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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('sms/send', 'App\Http\Controllers\SmsGateController@checkUserAuhth');
Route::get('sms/token', 'App\Http\Controllers\SmsGateController@apiTokenGenerate');


Route::post('account/name/get/local', 'App\Http\Controllers\AccountNameController@getAccountLocal');
Route::post('cust/address/get', 'App\Http\Controllers\AccountNameController@getCustAddress');
Route::post('email/send', 'App\Http\Controllers\SmsGateController@checkUserEmailAuhth');


Route::get('statement/get/account', 'App\Http\Controllers\AccountNameController@insertStatementLegal');

Route::post('get/ticket/deal', 'App\Http\Controllers\AccountNameController@getTicketDeal');

Route::post('get/card/order/info', 'App\Http\Controllers\AccountNameController@getCardOrderInfo');

Route::post('get/card/list', 'App\Http\Controllers\AccountNameController@getCardList');

Route::post('get/pfm/debit/stat', 'App\Http\Controllers\AccountNameController@getCardDebitStat');

Route::post('get/pfm/debit/stat/detail', 'App\Http\Controllers\AccountNameController@getCardDebitStatDetail');

Route::post('get/sms/list', 'App\Http\Controllers\SmsGateController@smsList');

Route::post('get/sms/detail', 'App\Http\Controllers\SmsGateController@smsDetail');

Route::post('create/sms', 'App\Http\Controllers\SmsGateController@createSms');

Route::post('update/sms', 'App\Http\Controllers\SmsGateController@updateSms');

Route::post('update/status/sms', 'App\Http\Controllers\SmsGateController@updateStatusSms');

Route::post('account/td/balance/loan', 'App\Http\Controllers\AccountNameController@getLnAvailableBal');

Route::post('account/td/princbal/calc', 'App\Http\Controllers\AccountNameController@getIntPayCalc');

//Route::get('test/email', 'App\Http\Controllers\SmsGateController@TestEmailGate');

///Route::get('sms/send', 'App\Http\Controllers\SmsGateController@SmsGate');




Route::post('get/penalty/list', 'App\Http\Controllers\SmartcarController@getPenaltyList');
Route::post('set/penalty/update', 'App\Http\Controllers\SmartcarController@setUpdatePenalty');


Route::post('gitpull', ['uses' => 'App\Http\Controllers\ApiController@gitpull']);
Route::get('gitpull', ['uses' => 'App\Http\Controllers\ApiController@gitpull']);
Route::post('iban/convert', 'App\Http\Controllers\ApiController@getIbanConvert');

//Route::get('test/email', 'App\Http\Controllers\SmsGateController@TestEmailGate');

///Route::get('sms/send', 'App\Http\Controllers\SmsGateController@SmsGate');

Route::post('card/checkEligibility', 'App\Http\Controllers\CardTokenController@checkCardEligibility');
Route::post('card/approveProvisioning', 'App\Http\Controllers\CardTokenController@approveProvisioning');
Route::post('card/deliverActivationCode', 'App\Http\Controllers\CardTokenController@deliverActivationCode');
Route::post('card/verifyActivationCode', 'App\Http\Controllers\CardTokenController@verifyActivationCode');
Route::post('card/metadata/notification', 'App\Http\Controllers\CardTokenController@cardMetadataNotificationService');

