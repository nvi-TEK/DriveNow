<?php


/*
|--------------------------------------------------------------------------
| Admin Auth Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'AdminController@dashboard')->name('index');
Route::get('/dashboard', 'AdminController@dashboard')->name('dashboard');
Route::get('/heatmap', 'AdminController@heatmap')->name('heatmap');

Route::resource('user', 	'Resource\UserResource');
Route::resource('fleet', 'Resource\FleetResource');
Route::resource('provider', 'Resource\ProviderResource');
Route::resource('document', 'Resource\DocumentResource');
Route::resource('service', 	'Resource\ServiceResource');
Route::resource('promocode','Resource\PromocodeResource');

Route::group(['as' => 'provider.'], function () {
    Route::get('review/provider', 'AdminController@provider_review')->name('review');
    Route::get('provider/{id}/approve', 'Resource\ProviderResource@approve')->name('approve');
    Route::get('provider/{id}/disapprove', 'Resource\ProviderResource@disapprove')->name('disapprove');
    Route::get('provider/{id}/request', 'Resource\ProviderResource@request')->name('request');
    Route::resource('provider/{provider}/document', 'Resource\ProviderDocumentResource');
    Route::get('provider/{id}/statement', 'Resource\ProviderResource@statement')->name('statement');

});

Route::get('provider/delete/allocation/', 'AdminController@destory_allocation')->name('destory.allocation');
Route::get('review/user', 			'AdminController@user_review')->name('user.review');
Route::get('user/{id}/request', 	'Resource\UserResource@request')->name('user.request');
Route::get('map/user', 				'AdminController@user_map')->name('user.map');
Route::get('map/provider', 			'AdminController@provider_map')->name('provider.map');
Route::get('setting', 				'AdminController@setting')->name('setting');
Route::post('setting/store', 		'AdminController@setting_store')->name('setting.store');
Route::get('profile', 				'AdminController@profile')->name('profile');
Route::post('profile/update', 		'AdminController@profile_update')->name('profile.update');
Route::get('password', 				'AdminController@password')->name('password');
Route::post('password/update', 		'AdminController@password_update')->name('password.update');
Route::get('payment', 				'AdminController@payment')->name('payment');
Route::get('payment/setting', 		'AdminController@payment_setting')->name('payment.setting');
Route::get('help', 					'AdminController@help')->name('help');
Route::get('/privacy', 				'AdminController@privacy')->name('privacy');
Route::post('/pages', 				'AdminController@pages')->name('pages.update');
Route::get('request', 				'AdminController@request_history')->name('request.history');
Route::get('scheduled/request', 	'AdminController@scheduled_request')->name('scheduled.request');
Route::get('request/{id}/details', 	'AdminController@request_details')->name('request.details');
Route::get('destory/{id}/service', 	'AdminController@destory_provider_service')->name('destory.service');

// statements

Route::get('/statement', 'AdminController@statement')->name('ride.statement');
Route::get('/statement/provider', 'AdminController@statement_provider')->name('ride.statement.provider');
Route::get('/statement/today', 'AdminController@statement_today')->name('ride.statement.today');
Route::get('/statement/monthly', 'AdminController@statement_monthly')->name('ride.statement.monthly');
Route::get('/statement/yearly', 'AdminController@statement_yearly')->name('ride.statement.yearly');


