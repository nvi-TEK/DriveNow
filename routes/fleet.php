<?php

/*
|--------------------------------------------------------------------------
| Fleet Routes
|--------------------------------------------------------------------------
*/

Route::get('/', 'FleetController@dashboard')->name('index');
Route::get('/dashboard', 'FleetController@dashboard')->name('dashboard');

Route::resource('provider', 'Resource\ProviderFleetResource');

Route::group(['as' => 'provider.'], function () {
    Route::get('review/provider', 'FleetController@provider_review')->name('review');
    Route::get('provider/{id}/approve', 'Resource\ProviderFleetResource@approve')->name('approve');
    Route::get('provider/{id}/disapprove', 'Resource\ProviderFleetResource@disapprove')->name('disapprove');
    Route::get('provider/{id}/request', 'Resource\ProviderFleetResource@request')->name('request');
    Route::resource('provider/{provider}/document', 'Resource\ProviderFleetDocumentResource');
    Route::get('provider/{id}/statement', 'Resource\ProviderFleetResource@statement')->name('statement');
    Route::get('provider/{provider}/service/{document}', 'Resource\ProviderFleetDocumentResource@service_destroy')->name('document.service.destroy');
});

Route::get('/notifymissingdocuments/{id}', 'Resource\ProviderFleetDocumentResource@notifymissingdocuments')->name('notifymissingdocuments');
Route::get('/document/notify/{id}', 'Resource\DocumentResource@notify');
Route::get('/document/notify/{provider}/{id}', 'Resource\ProviderFleetDocumentResource@notify');

Route::get('user/{id}/request', 'Resource\FleetUserResource@request')->name('user.request');

Route::get('map', 'FleetController@map_index')->name('map.index');
Route::get('map/ajax', 'FleetController@map_ajax')->name('map.ajax');

Route::get('profile', 'FleetController@profile')->name('profile');
Route::post('profile', 'FleetController@profile_update')->name('profile.update');

Route::get('password', 'FleetController@password')->name('password');
Route::post('password', 'FleetController@password_update')->name('password.update');

Route::resource('service', 'Resource\FleetServiceResource');
Route::resource('user', 'Resource\FleetUserResource');


Route::post('document/upload', 'FleetController@updateDocument')->name('provider.uploadDocument');


// Static Pages - Post updates to pages.update when adding new static pages.

Route::get('requests', 'Resource\TripResource@Fleetindex')->name('requests.index');
Route::delete('requests/{id}', 'Resource\TripResource@Fleetdestroy')->name('requests.destroy');
Route::get('requests/{id}', 'Resource\TripResource@Fleetshow')->name('requests.show');
Route::get('scheduled', 'Resource\TripResource@Fleetscheduled')->name('requests.scheduled');

Route::post('makeonline', 'FleetController@makeonline')->name('makeonline');
Route::get('createrequest', 'FleetController@createrequest')->name('requests.createrequest');
Route::post('estimate_fare', 'FleetController@estimate_fare')->name('getEstimation');
Route::post('createrequest', 'FleetController@postrequest');
Route::post('request_update', 'Resource\TripResource@updateFleetRequest');
Route::post('/getuserlocation', 'FleetController@getuserlocation');
Route::get('request/reject/{id}', 'Resource\TripResource@FleetRequestReject');

Route::get('payment', 'FleetController@payment')->name('payment');

//Statements
Route::get('/statement', 'FleetController@statement')->name('ride.statement');
Route::get('/statement/provider/{fleet}', 'FleetController@statement_provider')->name('ride.statement.provider');
Route::get('/statement/fleet', 'FleetController@statement_fleet')->name('ride.statement.fleet');
Route::get('/statement/service', 'FleetController@statement_service')->name('ride.statement.service');
Route::get('/statement/{id}/{fleet}', 'Resource\ServiceResource@fleet_statement')->name('service.statement');
Route::get('/statement/today', 'FleetController@statement_today')->name('ride.statement.today');
Route::get('/statement/monthly', 'FleetController@statement_monthly')->name('ride.statement.monthly');
Route::get('/statement/yearly', 'FleetController@statement_yearly')->name('ride.statement.yearly');

Route::get('/driverPayout', 'FleetController@showPayout')->name('payout');
Route::post('/driverPayout', 'FleetController@driverPayout');

Route::post('/getBank', 'Resource\ProviderFleetResource@getBanks'); 

Route::get('promocode', 'Resource\PromocodeResource@Fleetindex')->name('promocode.index');
Route::get('promocode/create', 'Resource\PromocodeResource@Fleetcreate')->name('promocode.create');
Route::delete('promocode/{id}', 'Resource\PromocodeResource@destroy')->name('promocode.destroy');
Route::get('promocode/{id}', 'Resource\PromocodeResource@Fleetedit')->name('promocode.edit');
Route::post('promocode/update', 'Resource\PromocodeResource@Fleetupdate')->name('promocode.update');
Route::post('promocode/store', 'Resource\PromocodeResource@Fleetstore')->name('promocode.store');


Route::get('/getRequests', 'FleetController@getRequests');
Route::post('/UpdateNotification', 'FleetController@UpdateNotification');
Route::get('/getNotifications', 'FleetController@getNotifications');


Route::get('/provider/bank/{id}', 'Resource\ProviderFleetResource@bank')->name('provider.bank');
Route::get('/provider/bank/{id}/approve', 'Resource\ProviderResource@bank_approve')->name('provider.bank.approve');
Route::get('/provider/bank/{id}/disapprove', 'Resource\ProviderResource@bank_disapprove')->name('provider.bank.disapprove');
Route::get('/provider/license/{id}', 'Resource\ProviderFleetResource@license')->name('provider.license');
Route::get('/provider/license/{id}/approve', 'Resource\ProviderResource@license_approve')->name('provider.license.approve');
Route::get('/provider/license/{id}/disapprove', 'Resource\ProviderResource@license_disapprove')->name('provider.license.disapprove');
Route::get('/provider/vehicle/{id}', 'Resource\ProviderFleetResource@vehicle')->name('provider.vehicle');
Route::get('/provider/vehicle/{id}/approve', 'Resource\ProviderResource@vehicle_approve')->name('provider.vehicle.approve');
Route::get('/provider/vehicle/{id}/disapprove', 'Resource\ProviderResource@vehicle_disapprove')->name('provider.vehicle.disapprove');

//Driver Filters
Route::get('drivers/offline', 'Resource\ProviderFleetResource@offline_drivers')->name('driver.offline');
Route::get('drivers/online', 'Resource\ProviderFleetResource@online_drivers')->name('driver.online');
Route::get('drivers/approved', 'Resource\ProviderFleetResource@approved_drivers')->name('driver.approved');
Route::get('drivers/not_approved', 'Resource\ProviderFleetResource@not_approved_drivers')->name('driver.not_approved');
Route::get('drivers/makeOnline', 'Resource\ProviderFleetResource@makeOnline_drivers')->name('driver.makeOnline');
Route::get('drivers/makeOffline', 'Resource\ProviderFleetResource@makeOffline_drivers')->name('driver.makeOffline');
Route::get('service/assign/drivers/{id}', 'Resource\ServiceResource@assign_service_drivers')->name('service.assign.drivers');
Route::get('/driveruploadeddocument/', 'Resource\ProviderFleetResource@recent_uploaded')->name('provider.document.uploaded');

Route::post('/send_individual_push', 'AdminController@send_individual_push')->name('individual_push.send');

Route::get('/cancelled_requests', 'Resource\TripResource@fleet_cancelled_requests')->name('requests.cancelled');

Route::get('/failed_requests', 'Resource\TripResource@fleet_failed_requests')->name('requests.failed');

Route::get('/failed_request_details/{id}', 'Resource\TripResource@fleet_failed_request_details')->name('requests.failed.show');

Route::get('request/assign/driver/{id}/{request_id}', 'Resource\TripResource@assign_driver')->name('request.assign.driver');

Route::get('payment_status/{id}', 'Resource\TripResource@payment_status');

