<?php

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

// Authentication
Route::post('/register' ,       'ProviderAuth\TokenController@register');
Route::post('/oauth/token' ,    'ProviderAuth\TokenController@authenticate');
Route::post('/api_key' ,    'ProviderAuth\TokenController@drivenow_token');

Route::post('/social_login' , 'ProviderAuth\TokenController@social_login');
Route::get('/cities' , 'ProviderAuth\TokenController@cities');
Route::get('/region' , 'ProviderAuth\TokenController@region');
Route::post('/forgot/password',     'ProviderAuth\TokenController@forgot_password');
Route::post('/reset/password',      'ProviderAuth\TokenController@reset_password');
Route::post('/sendOTP', 'ProviderApiController@sendOTP');

Route::post('/auth/facebook', 'ProviderAuth\TokenController@facebookViaAPI');
Route::post('/auth/google', 'ProviderAuth\TokenController@googleViaAPI');

Route::post('/create_invoice_wallet', 'PaymentController@create_invoice_wallet');


Route::group(['middleware' => ['provider.api']], function () {

    Route::group(['prefix' => 'profile'], function () {

        Route::get ('/' ,           'ProviderResources\ProfileController@index');
        Route::post('/' ,           'ProviderResources\ProfileController@update');
        Route::get('/documents' ,   'ProviderResources\ProfileController@documents');
        Route::post('/upload_document' , 'ProviderResources\ProfileController@upload_document');
        Route::post('/password' ,   'ProviderResources\ProfileController@password');
        Route::post('/location' ,   'ProviderResources\ProfileController@location');
        Route::get('/acc_details' ,   'ProviderResources\ProfileController@acc_details');
        Route::post('/upload_details' ,   'ProviderResources\ProfileController@upload_details');
        Route::post('/available' ,  'ProviderResources\ProfileController@available');
        Route::get('/driver_cards' ,   'ProviderResources\ProfileController@driver_cards');
        Route::post('/connectivity' , 'ProviderResources\ProfileController@connectivity');
        Route::get ('/referrals' ,           'ProviderResources\ProfileController@referals');

    });
    Route::get('/notifications' ,   'ProviderApiController@notifications');

    Route::get('/delete_notification' ,   'ProviderApiController@delete_notification');

    Route::get('/chat' , 'ProviderApiController@chat_history');

    Route::get('/fleetservice' , 'ProviderApiController@FleetServices');

    Route::get('/fleets' , 'ProviderApiController@fleets');

    Route::get('/target' , 'ProviderApiController@target');

    Route::post('/otp_activation', 'ProviderApiController@otp_activation');

    Route::get('/earnings' , 'ProviderApiController@earnings');

    Route::get('/wallet_balance' , 'ProviderApiController@wallet_balance');

    Route::post('/add_money' , 'ProviderApiController@add_money');

    Route::post('/withdraw' , 'ProviderApiController@withdraw');

    Route::get('/wallet_balance_sp' , 'ProviderApiController@wallet_balance_sp');

    Route::post('/add_money_sp' , 'ProviderApiController@add_money_sp');

    Route::post('/withdraw_sp' , 'ProviderApiController@withdraw_sp');

    Route::post('/update_eta' , 'ProviderApiController@update_eta');

    Route::get('/earning_metrics' , 'ProviderApiController@earning_metrics');
    
    Route::get ('/services' ,    'ProviderApiController@services');

    Route::get ('/services_coda' ,    'ProviderApiController@services_coda');

    Route::get ('/user' ,    'ProviderApiController@user');

    Route::get ('/sendRideCode/{id}' ,    'ProviderResources\TripController@RideCode');

    Route::post ('/update/service' ,    'ProviderApiController@update_services');

    Route::post('/logout' , 'ProviderAuth\TokenController@logout');

    Route::resource('trip', 'ProviderResources\TripController');

    Route::post('cancel', 'ProviderResources\TripController@cancel');

    Route::get('summary', 'ProviderResources\TripController@summary');
    
    Route::get('help', 'ProviderResources\TripController@help_details');

    Route::post('/change_destination', 'ProviderApiController@change_destination');

    Route::post('/change_destination_request', 'ProviderApiController@change_destination_request');

    Route::post('/change_destination_estimation', 'ProviderApiController@change_destination_estimation');

    Route::post('/set_destination', 'ProviderApiController@set_destination');

    Route::post('/set_destination_activation', 'ProviderApiController@set_destination_activation');

    Route::post('/add_location', 'ProviderApiController@add_location');
    Route::get('/get_locations', 'ProviderApiController@get_locations');

    Route::post('/promocode/add' ,  'ProviderApiController@redeem_referral');

    Route::group(['prefix' => 'trip'], function () {

        Route::post('{id}',             'ProviderResources\TripController@accept');
        Route::post('{id}/rate',        'ProviderResources\TripController@rate');
        Route::post('{id}/message' ,    'ProviderResources\TripController@message');
        Route::post('{id}/invoice_preview',        'ProviderResources\TripController@invoice_preview');
        Route::post('{id}/reject' ,    'ProviderResources\TripController@reject');

    });

    Route::group(['prefix' => 'requests'], function () {

        Route::get('/upcoming' , 'ProviderApiController@upcoming_request');
        Route::get('/history',          'ProviderResources\TripController@history');
        Route::get('/history/details',  'ProviderResources\TripController@history_details');
        Route::get('/upcoming/details', 'ProviderResources\TripController@upcoming_details');

    });

    Route::post('/emergency_contact/add', 'ProviderApiController@add_emergency_contacts');

    Route::post('/emergency_contact/update', 'ProviderApiController@update_emergency_contacts');

    Route::get('/emergency_contacts' ,      'ProviderApiController@emergency_contacts');

    Route::post('/emergency_contacts/delete' ,      'ProviderApiController@delete_emergency_contacts');

    Route::post('/sendSOSAlert', 'ProviderApiController@sendSOSAlert');

    Route::post('/add_car', 'ProviderApiController@add_car');

    Route::get('/list_cars', 'ProviderApiController@list_cars');

    Route::get('/car_info', 'ProviderApiController@car_info');

    Route::get('/delete_car', 'ProviderApiController@delete_car');

    Route::post('/update_car', 'ProviderApiController@update_car');

    Route::post('/add_account', 'ProviderApiController@add_account');

    Route::get('/list_accounts', 'ProviderApiController@list_accounts');

    Route::get('/account_info', 'ProviderApiController@account_info');

    Route::get('/delete_account', 'ProviderApiController@delete_account');

    Route::post('/update_account', 'ProviderApiController@update_account');

    Route::post('/changepayment' , 'ProviderApiController@changepayment');

    //Untapped APIs

    Route::get('/vehicles' , 'ProviderApiController@vehicle_list');

    Route::get('/vehicle/{id}' , 'ProviderApiController@vehicle_profile');

    Route::get('/transactions' , 'ProviderApiController@transactions');

    Route::get('/drivers' , 'ProviderApiController@driver_list');

    //DriveNow Profile APIs

    Route::get('/drivenow' , 'ProviderApiController@drivenow')->name('drivenow');

    Route::post('/drivenow/confirm_transaction', 'ProviderApiController@confirm_drivenow_transaction');

    Route::get('drivenow/agreement', 'ProviderApiController@agreement');

    Route::get('profile/delete', 'ProviderApiController@delete_profile');


});

