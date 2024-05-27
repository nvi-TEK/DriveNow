<?php

/*
|--------------------------------------------------------------------------
| Provider Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 		'ProviderController@index')->name('index');
Route::get('/agreement/{id}', 		'ProviderController@agreement')->name('agreement');
Route::get('/trips', 	'ProviderResources\TripController@history')->name('trips');

Route::get('/incoming', 			'ProviderController@incoming')->name('incoming');
Route::post('/request/{id}', 		'ProviderController@accept')->name('accept');
Route::patch('/request/{id}', 		'ProviderController@update')->name('update');
Route::post('/request/{id}/rate', 	'ProviderController@rating')->name('rating');
Route::delete('/request/{id}', 		'ProviderController@reject')->name('reject');

Route::get('/earnings', 'ProviderController@earnings')->name('earnings');
Route::get('/agree/{id}', 'ProviderController@agree')->name('agree');

Route::resource('documents', 'ProviderResources\DocumentController');

Route::get('/profile', 	'ProviderResources\ProfileController@show')->name('profile.index');
Route::post('/profile', 'ProviderResources\ProfileController@store')->name('profile.update');

Route::get('/location', 	'ProviderController@location_edit')->name('location.index');
Route::post('/location', 	'ProviderController@location_update')->name('location.update');

Route::post('/profile/available', 	'ProviderController@available')->name('available');
Route::get('/profile/password', 'ProviderController@change_password')->name('change.password');
Route::post('/change/password', 'ProviderController@update_password')->name('password.update');

Route::get('/upcoming', 'ProviderController@upcoming_trips')->name('upcoming');
Route::post('/cancel', 'ProviderController@cancel')->name('cancel');

Route::get('/drivenow', 'ProviderController@drivenow')->name('drivenow');

Route::get('/drivenow/status', 'Resource\ProviderResource@drivenow_status')->name('drivenow.status');

Route::get('/drivenow/payment', 'ProviderController@drivenow_payment')->name('drivenow.payment');
Route::get('/drivenow/payment/missed/{id}', 'ProviderController@drivenow_missed_payment')->name('drivenow.pay_missed');
Route::post('/drivenow/paynow', 'ProviderController@drivenow_paynow')->name('drivenow.paynow');

Route::get('/drivenow_day_off/{id}', 'ProviderController@drivenow_driver_off')->name('drivenow.day_off');

Route::get('/drivenow_day_on/{id}', 'ProviderController@drivenow_driver_on')->name('drivenow.day_on');


//PayStack Callback
Route::post('/pay', 'ProviderController@redirectToGateway')->name('paystack.pay');

Route::get('/paystack/callback', 'PaymentController@handleGatewayCallback')->name('drivenow.paystack.payment');

Route::get('/drive-own', 'ProviderController@drivenow')->name('drivenow');





