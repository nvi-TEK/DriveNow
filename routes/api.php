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

Route::post('/signup' , 'UserApiController@signup');
Route::post('/login' , 'UserApiController@login');
Route::post('/logout' , 'UserApiController@logout');
Route::post('/social_login' , 'Auth\SocialLoginController@social_login');
Route::post('/forgot/password',     'UserApiController@forgot_password');
Route::post('/reset/password',      'UserApiController@reset_password');
Route::post('/auth/facebook', 		'Auth\SocialLoginController@facebookViaAPI');
Route::post('/auth/google', 		'Auth\SocialLoginController@googleViaAPI');
Route::post('/sendOTP', 'UserApiController@sendOTP');
Route::get('trotrotracker', 'UserApiController@trotrotracker');
Route::post('/sendMessage', 'UserApiController@sendMessage');
Route::post('/create_invoice_wallet', 'PaymentController@create_invoice_wallet');

Route::post('/drivenow_token', 'ProviderAuth\TokenController@drivenow_token');

Route::group(['middleware' => ['auth:api']], function () {

	// user profile

	Route::post('/change/password' , 	'UserApiController@change_password');
	Route::post('/update/location' , 	'UserApiController@update_location');
	Route::get('/fleets' , 			'UserApiController@fleets');
	Route::get('/details' , 			'UserApiController@details');
	Route::get('/provider' , 			'UserApiController@provider');
	Route::post('/update/profile' , 	'UserApiController@update_profile');
	Route::get('/referrals' , 			'UserApiController@referals');

	Route::post('/otp_activation' , 'UserApiController@otp_activation');

	Route::post('/saveToken' , 'UserApiController@saveToken');

	Route::get('/notifications' , 	'UserApiController@notifications');

	Route::get('/delete_notification' ,   'UserApiController@delete_notification');

	Route::post('/uploadImage' , 'UserApiController@upload_image');

	Route::get('/activeRequest' , 	'UserApiController@active_request');



	// services

	Route::get('/services' , 'UserApiController@services');

	Route::post('/service_estimate' , 'UserApiController@service_estimate');

	Route::post('/service_estimate_coda' , 'UserApiController@service_estimate_coda');

	Route::get('/fleetservice' , 'UserApiController@serviceTypes');

	Route::get('/chat' , 'UserApiController@chat_histroy');

	Route::post('/changepayment' , 'UserApiController@changepayment');

	// Manage Locations

    Route::get('/locations', 'UserApiController@locations');

    Route::post('/addLocation', 'UserApiController@add_location');

    Route::post('/editLocation', 'UserApiController@edit_location');

    Route::post('/defaultLocation', 'UserApiController@default_location');

    Route::post('/deleteLocation', 'UserApiController@delete_location');


	// estimated

	Route::post('/estimated/fare' , 'UserApiController@estimated_fare');
	Route::post('/estimated/fare_new' , 'UserApiController@estimated_fare_new');

	// provider

	Route::post('/rate/provider' , 'UserApiController@rate_provider');

	Route::get('/wallet_balance' , 'UserApiController@wallet_balance');
    Route::post('/add_money' , 'UserApiController@add_money');

    Route::get('/wallet_balance_sp' , 'UserApiController@wallet_balance_sp');
    Route::post('/add_money_sp' , 'UserApiController@add_money_sp');

	// request

	Route::post('/send/request' , 	'UserApiController@send_request');
	Route::post('/send/request/delivery' , 	'UserApiController@send_request_delivery');
	Route::post('/cancel/request' , 'UserApiController@cancel_request');
	Route::get('/request/check' , 	'UserApiController@request_status_check');

	//Change Destination

	Route::post('/change_destination', 'UserApiController@change_destination');

	Route::post('/change_destination_request', 'UserApiController@change_destination_request');

	Route::post('/change_destination_estimation', 'UserApiController@change_destination_estimation');

	// payment

	Route::post('/payment' , 	'PaymentController@payment');
	Route::post('/add/money' , 	'PaymentController@add_money');

	Route::post('/confirm_transaction', 'PaymentController@confirm_transaction');

	// estimated

	Route::get('/estimated/fare' , 'UserApiController@estimated_fare');

	// promocode

	Route::get('/promocodes' , 		'UserApiController@promocodes');
	Route::post('/promocode/add' , 	'UserApiController@add_promocode');

	// card payment

    Route::resource('card', 'Resource\CardResource');

    Route::get('/show/providers' , 'UserApiController@show_providers');
    
    Route::get('upcoming/trips' , 'UserApiController@upcoming_trips');
    Route::get('upcoming/trip/details' , 'UserApiController@upcoming_trip_details');

	Route::get('/help' , 'UserApiController@help_details');

	Route::post('/emergency_contact/add', 'UserApiController@add_emergency_contacts');

	Route::post('/emergency_contact/update', 'UserApiController@update_emergency_contacts');

	Route::get('/emergency_contacts' , 		'UserApiController@emergency_contacts');

	Route::post('/emergency_contacts/delete' , 		'UserApiController@delete_emergency_contacts');

	Route::post('/sendSOSAlert', 'UserApiController@sendSOSAlert');

	Route::get('driver_details', 'UserApiController@driver_details');

	Route::post('/short_my_url', 'UserApiController@short_my_url');

	Route::get('convery_url', 'UserApiController@return_url');

	Route::get('delete_account', 'UserApiController@delete_account');

});
