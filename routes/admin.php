<?php

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::get('/template', function(){
    return view('admin.layout.newbase');
});
Route::get('/', 'AdminController@dashboard')->name('index');
Route::get('/dashboard', 'AdminController@dashboard')->name('dashboard');
Route::get('/heatmap', 'AdminController@heatmap')->name('heatmap');
Route::post('/getProvider', 'AdminController@getProvider');
Route::post('/getDriver', 'AdminController@getDriver');

Route::get('/push_drivers_online', 'AdminController@push_drivers_online')->name('push_drivers_online');

Route::get('/refresh_location', 'AdminController@refresh_location')->name('driver.refresh_location');

Route::get('/search', 'AdminController@search')->name('search');

Route::resource('user', 'Resource\UserResource');
Route::resource('fleet', 'Resource\FleetResource');
Route::post('/update-auto-payout', 'Resource\FleetResource@autopayout');
Route::post('/update-driver-payout', 'Resource\ProviderResource@autopayout');
Route::resource('provider', 'Resource\ProviderResource');
Route::get('/notifymissingdocuments/{id}', 'Resource\ProviderDocumentResource@notifymissingdocuments')->name('notifymissingdocuments');
Route::resource('document', 'Resource\DocumentResource');
Route::get('/document/notify/{id}', 'Resource\DocumentResource@notify');
Route::get('/document/notify/{provider}/{id}', 'Resource\ProviderDocumentResource@notify');
Route::resource('service', 'Resource\ServiceResource');
Route::resource('promocode', 'Resource\PromocodeResource');
Route::resource('moderator', 'Resource\ModeratorResource');
Route::resource('marketer', 'Resource\MarketerResource');

Route::get('/driverdocument/{id}', 'Resource\ProviderResource@uploaddocument');
Route::post('/driverdocument/{id}/{document_id}', 'Resource\ProviderResource@updatedocument');

Route::group(['as' => 'marketer.'], function () {
	Route::get('marketer/{id}/drivers', 'Resource\MarketerResource@drivers')->name('drivers');
});
Route::group(['as' => 'provider.'], function () {
    Route::get('review/provider', 'AdminController@provider_review')->name('review');
    Route::get('provider/{id}/approve', 'Resource\ProviderResource@approve')->name('approve');
    Route::get('provider/{id}/disapprove', 'Resource\ProviderResource@disapprove')->name('disapprove');
    Route::get('provider/{id}/request', 'Resource\ProviderResource@request')->name('request');
    Route::get('provider/{id}/statement', 'Resource\ProviderResource@statement')->name('statement');
    Route::resource('provider/{provider}/document', 'Resource\ProviderDocumentResource');
    Route::delete('provider/{provider}/service/{document}', 'Resource\ProviderDocumentResource@service_destroy')->name('document.service');
});

Route::get('dd', 'Resource\FleetResource@fleet_driver');

Route::post('estimate_fare', 'AdminController@estimate_fare')->name('getEstimation');

Route::post('document/upload', 'AdminController@updateDocument')->name('provider.uploadDocument');

Route::get('fleet/assign/service/{fleet}', 'Resource\AdminFleetServiceResource@index')->name('fleet.assign.service');
Route::post('fleet/assign/service/{fleet}', 'Resource\AdminFleetServiceResource@store')->name('fleet.assign.service.store');
Route::get('fleet/approve/service/{id}', 'Resource\AdminFleetServiceResource@approve')->name('fleet.approve.service');
Route::get('fleet/decline/service/{id}', 'Resource\AdminFleetServiceResource@decline')->name('fleet.decline.service');
Route::get('fleet/service/edit_pricing/{id}/{service}', 'Resource\AdminFleetServiceResource@EditPricing')->name('fleet.service.edit');
Route::post('fleet/service/update_pricing/{id}', 'Resource\AdminFleetServiceResource@UpdatePricing')->name('fleet.service.updatePricing');

Route::get('review/user', 'AdminController@user_review')->name('user.review');
Route::get('user/{id}/request', 'Resource\UserResource@request')->name('user.request');

Route::get('map', 'AdminController@map_index')->name('map.index');
Route::get('map/ajax', 'AdminController@map_ajax')->name('map.ajax');
Route::get('driver/map/{id}', 'AdminController@driver_map')->name('map.driver');

//User Map
Route::get('map/user', 'AdminController@user_map_index')->name('map.user');
Route::get('map/ajax/user', 'AdminController@user_map_ajax')->name('map.ajax.user');

Route::get('map/offline', 'AdminController@offline_map_index')->name('map.offline');
Route::get('map/ajax/offline', 'AdminController@offline_map_ajax')->name('map.ajax.offline');

Route::get('map/unactivated', 'AdminController@unactivated_map_index')->name('map.unactivated');
Route::get('map/ajax/unactivated', 'AdminController@unactivated_map_ajax')->name('map.ajax.unactivated');

Route::get('settings', 'AdminController@settings')->name('settings');
Route::post('settings/store', 'AdminController@settings_store')->name('settings.store');
Route::get('settings/payment', 'AdminController@settings_payment')->name('settings.payment');
Route::post('settings/payment', 'AdminController@settings_payment_store')->name('settings.payment.store');

Route::get('settings/ride', 'AdminController@settings_ride')->name('settings.ride');
Route::post('settings/ride', 'AdminController@settings_ride_store')->name('settings.ride.store');

Route::get('settings/referral', 'AdminController@settings_referral')->name('settings.referral');
Route::post('settings/referral', 'AdminController@settings_referral_store')->name('settings.referral.store');

Route::get('profile', 'AdminController@profile')->name('profile');
Route::post('profile', 'AdminController@profile_update')->name('profile.update');

Route::get('password', 'AdminController@password')->name('password');
Route::post('password', 'AdminController@password_update')->name('password.update');

Route::get('payment', 'AdminController@payment')->name('payment');

Route::get('/download/{id}/{doc}', 'AdminController@getDownload');

Route::get('destroy/{id}/service', 	'AdminController@destroy_provider_service')->name('destroy.service');

// statements

Route::get('/statement', 'AdminController@statement')->name('ride.statement');
Route::get('/statement/provider/{fleet}', 'AdminController@statement_provider')->name('ride.statement.provider');
Route::get('/statement/fleet', 'AdminController@statement_fleet')->name('ride.statement.fleet');
Route::get('/statement/service', 'AdminController@statement_service')->name('ride.statement.service');
Route::get('/statement/{id}', 'Resource\ServiceResource@statement')->name('service.statement');
Route::get('/statements/{type}', 'AdminController@statement');

Route::get('/ownerPayouts', 'AdminController@ownerPayouts')->name('ownerPayouts');
Route::post('/ownerPayouts/{type}', 'AdminController@PayOwnerPayouts');
Route::get('/driverPayouts', 'AdminController@driverPayouts')->name('driverPayouts');
// Static Pages - Post updates to pages.update when adding new static pages.

Route::get('/help', 'AdminController@help')->name('help');
Route::get('/privacy', 'AdminController@privacy')->name('privacy');
Route::post('/pages', 'AdminController@pages')->name('pages.update');

Route::resource('requests', 'Resource\TripResource');
Route::get('/delivery', 'Resource\TripResource@delivery')->name('requests.delivery');
Route::get('/getRequests', 'Resource\TripResource@getRequests');
Route::post('/UpdateNotification', 'Resource\TripResource@UpdateNotification');
Route::get('/getNotifications', 'Resource\TripResource@getNotifications');
Route::post('request_update', 'Resource\TripResource@updateRequest');
Route::get('scheduled', 'Resource\TripResource@scheduled')->name('requests.scheduled');

Route::get('push', 'AdminController@push_index')->name('push.index');
Route::post('push', 'AdminController@push_store')->name('push.store');

Route::post('/getBank', 'Resource\ProviderResource@getBanks'); 

Route::post('/driver_comment/post', 'AdminController@post_driver_comment')->name('provider.comment.post');

Route::post('/driver_comment/edit', 'AdminController@edit_driver_comment')->name('provider.comment.edit');

Route::post('/driver_comment/delete', 'AdminController@delete_driver_comment')->name('provider.comment.delete');


Route::post('/user_comment/post', 'AdminController@post_user_comment')->name('user.comment.post');

Route::post('/user_comment/edit', 'AdminController@edit_user_comment')->name('user.comment.edit');

Route::post('/user_comment/delete', 'AdminController@delete_user_comment')->name('user.comment.delete');


Route::get('/driveruploadeddocument/', 'Resource\ProviderResource@recent_uploaded')->name('provider.document.uploaded');

Route::get('/driver/start_trial', 'Resource\ProviderResource@approveTrialPeriod')->name('provider.document.start_trial');

//Bank Details
Route::get('/provider/bank/list/{id}', 'Resource\ProviderResource@bank_list')->name('provider.bank.list');
Route::get('/provider/bank/{id}', 'Resource\ProviderResource@bank')->name('provider.bank');
Route::get('/provider/bank/add/{id}', 'Resource\ProviderResource@add_bank')->name('provider.bank.add');
Route::post('/provider/bank/add', 'Resource\ProviderResource@store_bank')->name('provider.bank.store');
Route::post('/provider/bank/update', 'Resource\ProviderResource@update_bank')->name('provider.bank.update');
Route::get('/provider/bank/{id}/approve', 'Resource\ProviderResource@bank_approve')->name('provider.bank.approve');
Route::get('/provider/bank/{id}/disapprove', 'Resource\ProviderResource@bank_disapprove')->name('provider.bank.disapprove');

Route::get('/provider/license/{id}', 'Resource\ProviderResource@license')->name('provider.license');
Route::get('/provider/license/{id}/approve', 'Resource\ProviderResource@license_approve')->name('provider.license.approve');
Route::get('/provider/license/{id}/disapprove', 'Resource\ProviderResource@license_disapprove')->name('provider.license.disapprove');

//Driver Cars Details
Route::get('/provider/vehicle/list/{id}', 'Resource\ProviderResource@vehicle_list')->name('provider.vehicle.list');
Route::get('/provider/vehicle/{id}', 'Resource\ProviderResource@vehicle')->name('provider.vehicle');
Route::get('/provider/vehicle/add/{id}', 'Resource\ProviderResource@add_car')->name('provider.vehicle.add');
Route::post('/provider/vehicle/add', 'Resource\ProviderResource@store_car')->name('provider.vehicle.store');
Route::post('/provider/vehicle/update', 'Resource\ProviderResource@update_car')->name('provider.vehicle.update');
Route::get('/provider/vehicle/{id}/approve', 'Resource\ProviderResource@vehicle_approve')->name('provider.vehicle.approve');
Route::get('/provider/vehicle/{id}/disapprove', 'Resource\ProviderResource@vehicle_disapprove')->name('provider.vehicle.disapprove');

//Driver Filters
Route::get('drivers/offline', 'Resource\ProviderResource@offline_drivers')->name('driver.offline');
Route::get('drivers/online', 'Resource\ProviderResource@online_drivers')->name('driver.online');
Route::get('drivers/approved', 'Resource\ProviderResource@approved_drivers')->name('driver.approved');
Route::get('drivers/not_approved', 'Resource\ProviderResource@not_approved_drivers')->name('driver.not_approved');
Route::get('drivers/makeOnline', 'Resource\ProviderResource@makeOnline_drivers')->name('driver.makeOnline');
Route::get('drivers/makeOffline', 'Resource\ProviderResource@makeOffline_drivers')->name('driver.makeOffline');
Route::get('service/assign/drivers/{id}', 'Resource\ServiceResource@assign_service_drivers')->name('service.assign.drivers');


Route::post('drivers/search', 'Resource\ProviderResource@search_drivers')->name('driver.search');

Route::get('/cancelled_requests', 'Resource\TripResource@cancelled_requests')->name('requests.cancelled');

Route::get('/failed_requests', 'Resource\TripResource@failed_requests')->name('requests.failed');

Route::get('/failed_request_details/{id}', 'Resource\TripResource@failed_request_details')->name('requests.failed.show');

Route::get('/custom_push', 'AdminController@custom_push')->name('custom_push.index');

Route::post('/send_individual_push', 'AdminController@send_individual_push')->name('individual_push.send');

Route::post('/send_custom_push', 'AdminController@send_custom_push')->name('custom_push.send');

Route::get('/ambassadors', 'Resource\ProviderResource@ambassadors')->name('ambassadors.index');

Route::get('/due_ambassadors', 'Resource\ProviderResource@due_ambassadors')->name('ambassadors.due');

Route::get('/paid_ambassadors', 'Resource\ProviderResource@paid_ambassadors')->name('ambassadors.paid');

Route::get('/ambassadors/{id}/add', 'Resource\ProviderResource@add_ambassador')->name('ambassadors.add');

Route::get('/ambassadors/{id}/remove', 'Resource\ProviderResource@remove_ambassador')->name('ambassadors.remove');

Route::get('/ambassadors/pay', 'Resource\ProviderResource@pay_ambassadors')->name('ambassadors.pay');

Route::get('/ambassador_credit/{id}', 'Resource\ProviderResource@credit_ambassadors')->name('ambassadors.credit');

Route::get('/ambassador_credit_all', 'Resource\ProviderResource@credit_all_ambassadors')->name('ambassadors.overallcredit');

//DriveNow Drivers

Route::get('drivenow_deposits', 'Resource\ProviderResource@deposits')->name('drivenow.deposits');

Route::post('drivenow/add_deposit', 'Resource\ProviderResource@add_deposit')->name('drivenow.deposit.add');

Route::post('drivenow/refund_deposit', 'Resource\ProviderResource@refund_deposit')->name('drivenow.deposit.refund');

Route::get('driver_deposit/transactions/{id}', 'Resource\ProviderResource@deposit_transactions');

Route::get('/drivenow_drivers', 'Resource\ProviderResource@official_drivers')->name('official_drivers.index');

Route::get('/drivenow_drivers/completion', 'Resource\ProviderResource@completion_od')->name('official_drivers.completion');

Route::get('/drivenow_drivers/referrer', 'Resource\ProviderResource@referrals_od')->name('official_drivers.referrals');

Route::post('/drivenow_drivers/add', 'Resource\ProviderResource@add_official_driver')->name('official_drivers.add');

Route::get('/drivenow_drivers/{id}/remove', 'Resource\ProviderResource@remove_official_driver')->name('official_drivers.remove');

Route::get('official_drivers/{id}/d20_agreement', 'Resource\ProviderResource@drivenow_agreement')->name('official_drivers.drivenow_agreement');

Route::post('/drivenow_drivers/update_drivenow', 'Resource\ProviderResource@drivenow_update')->name('official_drivers.update');


Route::get('/upload_driver_profile_s3', 'AdminController@upload_driver_profile_s3')->name('upload.driver_profile');

Route::get('/upload_driver_docs_s3', 'AdminController@upload_driver_docs_s3')->name('upload.driver_doc');

Route::get('/upload_driver_cars_s3', 'AdminController@upload_driver_cars_s3')->name('upload.driver_cars');

Route::get('/upload_user_profile_s3', 'AdminController@upload_user_profile_s3')->name('upload.user_profile');

Route::get('/request_payment', 'AdminController@request_payment')->name('request.payment');


// Promotional Drivers

Route::get('/promo_drivers', 'Resource\ProviderResource@promo_drivers')->name('promo_drivers.index');

Route::get('/due_promo_drivers', 'Resource\ProviderResource@due_promo_drivers')->name('promo_drivers.due');

Route::get('/paid_promo_drivers', 'Resource\ProviderResource@paid_promo_drivers')->name('promo_drivers.paid');

Route::get('/promo_drivers/{id}/add', 'Resource\ProviderResource@add_promo_driver')->name('promo_drivers.add');

Route::get('/promo_drivers/{id}/remove', 'Resource\ProviderResource@remove_promo_driver')->name('promo_drivers.remove');

Route::get('/promo_drivers/pay', 'Resource\ProviderResource@pay_promo_drivers')->name('promo_drivers.pay');

Route::get('/promo_driver_credit/{id}', 'Resource\ProviderResource@credit_promo_drivers')->name('promo_drivers.credit');

Route::get('/promo_driver_credit_all', 'Resource\ProviderResource@credit_all_promo_drivers')->name('promo_drivers.overallcredit');

Route::get('/driver_manager', 'AdminController@manager')->name('manager.driver');

Route::get('/driver_manager/add', 'AdminController@create_driver_manager')->name('manager.driver.create');

Route::post('driver_manager/store', 'AdminController@store_driver_manager')->name('manager.driver.store');

Route::get('/driver_manager/edit/{id}', 'AdminController@edit_driver_manager')->name('manager.driver.edit');

Route::post('driver_manager/update', 'AdminController@update_driver_manager')->name('manager.driver.update');

Route::get('/driver_manager/delete/{id}', 'AdminController@delete_driver_manager')->name('manager.driver.delete');

Route::get('driver_manager/assign/service/{driver_manager}', 'Resource\AdminFleetServiceResource@index')->name('manager.assign.service');

Route::post('driver_manager/assign/service/{driver_manager}', 'Resource\AdminFleetServiceResource@store')->name('manager.assign.service.store');

Route::get('request/assign/driver/{id}/{request_id}', 'Resource\TripResource@assign_driver')->name('request.assign.driver');

Route::get('/driver_status/{id}/{status}', 'Resource\ProviderResource@status_update');

Route::get('/pay_reward/{id}', 'AdminController@PayReward')->name('request.pay_reward');

Route::post('payment_update', 'Resource\TripResource@payment_update');

Route::get('payment_status/{id}', 'Resource\TripResource@payment_status');

Route::post('/withdraw_sp' , 'Resource\ProviderResource@withdraw_sp')->name('driver.withdraw');

Route::get('/wallet_balance', 'AdminController@wallet_balances')->name('wallet_balance');

Route::post('/driver/credit' , 'Resource\ProviderResource@credit')->name('driver.credit');

Route::post('/driver/debit' , 'Resource\ProviderResource@debit')->name('driver.debit');

Route::get('/request/timeslot', 'Resource\TripResource@time_slot')->name('requests.timeslot');

Route::get('/request/list', 'Resource\TripResource@list_analytics')->name('requests.list');

Route::get('request_map', 'AdminController@map_index')->name('map.request');

Route::get('map/ajax', 'AdminController@map_ajax')->name('map.ajax');

Route::get('driver/map/{id}', 'AdminController@driver_map')->name('map.driver');

Route::get('driver/agreement/{id}', 'AdminController@agreement')->name('driver.agreement');

Route::get('/mmn', 'AdminController@mmn')->name('mmn.index');

Route::get('/mmn_cashback', 'AdminController@mmn_cashback')->name('mmn.cashback');

Route::get('/drivenow_driver/engine/{status}/{id}', 'Resource\ProviderResource@engine_control')->name('driver.engine_control');

Route::get('/drivenow_driver/engine_restore/{id}', 'Resource\ProviderResource@engine_restore')->name('driver.engine_control.restore');

Route::get('/drivenow_driver/engine_off/{id}', 'Resource\ProviderResource@turn_off_engine')->name('driver.engine_control.off');

//Bulk Block vehicle Engine of driver with due and device offline

Route::get('/drivenow_driver/bulk_engine_off', 'Resource\ProviderResource@block_offline_device')->name('drivenow.engine_control.bulk');

Route::get('/drivenow_transactions', 'Resource\ProviderResource@drivenow_transactions')->name('official_drivers.transactions');

Route::get('/drivenow_driver_transaction/{id}', 'Resource\ProviderResource@drivenow_driver_transactions')->name('drivenow_driver.transactions');


Route::get('/drivenow/approve/{id}', 'Resource\ProviderResource@drivenow_approve')->name('drivenow.approve');

Route::get('/drivenow_due', 'Resource\ProviderResource@drivenow_due')->name('official_drivers.drivenow_due');

Route::get('/drivenow_due/approve/{id}', 'Resource\ProviderResource@drivenow_due_approve')->name('drivenow.due.approve');

Route::get('/drivenow_due/make_paid/{id}', 'Resource\ProviderResource@drivenow_make_paid')->name('drivenow.payback');

Route::get('/drivenow_due_payment', 'Resource\ProviderResource@drivenow_due_payment')->name('drivenow.due_payment');

Route::post('/drivenow_due_payment', 'Resource\ProviderResource@drivenow_make_payment')->name('drivenow.make_payment');

Route::post('/drivenow_due/due_break', 'Resource\ProviderResource@drivenow_due_break')->name('drivenow.drivenow_break');

Route::post('/drivenow_due/drivenow_extra', 'Resource\ProviderResource@drivenow_extra')->name('drivenow.drivenow_extra');

Route::get('/drivenow_due/extra_due', 'Resource\ProviderResource@extra_due')->name('drivenow.extra_due');

Route::get('/drivenow_due/delete_extra_due/{id}', 'Resource\ProviderResource@extra_deactivate')->name('drivenow.extra.deactivate');

Route::get('/drivenow_tracker', 'Resource\ProviderResource@drivenow_tracker')->name('drivenow.tracker');

Route::get('/drivenow_driver/engine_control', 'Resource\ProviderResource@alldriver_engine_control')->name('official_driver.engine_control_off');

Route::get('/drivenow_due_break', 'Resource\ProviderResource@drivenow_break')->name('drivenow.drivenow_due_break');
Route::get('/drivenow/engine_status/{id}', 'Resource\ProviderResource@drivenow_engine_status')->name('drivenow.engine_status');


Route::get('/drivenow/{id}', 'Resource\ProviderResource@drivenow')->name('drivenow.profile');

Route::get('/drivenow/status/{id}', 'Resource\ProviderResource@drivenow_status')->name('drivenow.profile.status');

Route::get('/drivenow/payment/{id}', 'Resource\ProviderResource@drivenow_payment')->name('drivenow.profile.payment');
Route::post('/drivenow/paynow', 'Resource\ProviderResource@drivenow_paynow')->name('drivenow.profile.paynow');

Route::get('/drivenow_day_off/{id}', 'Resource\ProviderResource@drivenow_driver_off')->name('drivenow.day_off');

Route::get('/drivenow_day_on/{id}', 'Resource\ProviderResource@drivenow_driver_on')->name('drivenow.day_on');

//Expense Manager
Route::get('/expenses', 'AdminController@expenses')->name('expenses.index');
Route::get('/expenses/approve/{id}', 'AdminController@approve_expense')->name('expenses.approve');
Route::get('/expenses/decline/{id}', 'AdminController@decline_expense')->name('expenses.decline');
Route::get('/expenses/pay/{id}', 'AdminController@pay_expense')->name('expenses.pay');
Route::get('/expenses/create', 'AdminController@create_expense')->name('expenses.create');
Route::get('/expenses/edit/{id}', 'AdminController@edit_expense')->name('expenses.edit');
Route::post('/expense/add', 'AdminController@add_expenses')->name('expenses.add');
Route::post('/expense/update', 'AdminController@update_expenses')->name('expenses.update');

Route::get('/expenses/categories', 'AdminController@expense_categories')->name('expenses.category.index');
Route::get('/expenses/categories/edit/{id}', 'AdminController@edit_expense_categories')->name('expenses.category.edit');
Route::get('/expenses/categories/delete/{id}', 'AdminController@delete_exp_categories')->name('expenses.category.delete');
Route::post('/expense/categories/add', 'AdminController@add_expense_category')->name('expenses.category.add');
Route::post('/expense/categories/update', 'AdminController@update_expense_category')->name('expenses.category.update');


Route::get('/drivenow_terminated', 'Resource\ProviderResource@drivenow_terminated')->name('drivenow.terminated');

Route::get('/drivenow/due_engine/{status}/{id}', 'Resource\ProviderResource@due_engine_control')->name('drivenow.engine_control');

Route::post('/drivenow_drivers/remove', 'Resource\ProviderResource@remove_drivenow_driver')->name('drivenow.remove');


Route::get('/vehicles', 'Resource\VehicleResource@vehicles')->name('drivenow.vehicles.index');
Route::get('/vehicles/add', 'Resource\VehicleResource@add_vehicle')->name('drivenow.vehicles.add');
Route::post('/vehicles/store', 'Resource\VehicleResource@store_vehicle')->name('drivenow.vehicles.store');
Route::post('/vehicles/update', 'Resource\VehicleResource@update_vehicle')->name('drivenow.vehicles.update');
Route::get('/vehicle/{id}', 'Resource\VehicleResource@view_vehicle')->name('drivenow.vehicles.view');
Route::get('/vehicles/delete/{id}', 'Resource\VehicleResource@destroy_vehicle')->name('drivenow.vehicles.destroy');

Route::get('/suppliers', 'Resource\VehicleResource@suppliers')->name('drivenow.suppliers.index');
Route::get('/suppliers/add', 'Resource\VehicleResource@add_supplier')->name('drivenow.supplier.create');
Route::post('/suppliers/store', 'Resource\VehicleResource@store_supplier')->name('drivenow.suppliers.add');
Route::post('/suppliers/update', 'Resource\VehicleResource@update_supplier')->name('drivenow.suppliers.update');
Route::get('/supplier/{id}', 'Resource\VehicleResource@view_supplier')->name('drivenow.suppliers.view');
Route::get('/supplier/delete/{id}', 'Resource\VehicleResource@destroy')->name('drivenow.supplier.destroy');
Route::get('/suppliers/payments', 'Resource\VehicleResource@supplier_payments')->name('drivenow.suppliers.payments');


//Vehicle Payments
Route::get('/vehicles/payments', 'Resource\VehicleResource@vehicle_payments')->name('drivenow.vehicles_pay.index');
Route::get('/vehicle/pay/approve/{id}', 'Resource\VehicleResource@vehicle_pay_approve')->name('drivenow.vehicles_pay.approve');
Route::get('/vehicle/pay/reverse/{id}', 'Resource\VehicleResource@vehicle_pay_reverse')->name('drivenow.vehicles_pay.reverse');
Route::get('/vehicle/pay_all/{id}', 'Resource\VehicleResource@vehicle_pay_all')->name('drivenow.vehicles_pay.all');

Route::get('/supplier/getfleet/{id}', 'Resource\VehicleResource@getFleets')->name('drivenow.supplier.fleets');

Route::get('/suppliers/fleet', 'Resource\VehicleResource@supplier_fleet')->name('drivenow.suppliers.fleet.index');
Route::get('/suppliers/fleet/add', 'Resource\VehicleResource@add_supplier_fleet')->name('drivenow.suppliers.fleet.create');
Route::post('/suppliers/fleet/store', 'Resource\VehicleResource@store_supplier_fleet')->name('drivenow.suppliers.fleet.add');
Route::post('/suppliers/fleet/update', 'Resource\VehicleResource@update_supplier_fleet')->name('drivenow.suppliers.fleet.update');
Route::get('/supplier/fleet/{id}', 'Resource\VehicleResource@view_supplier_fleet')->name('drivenow.suppliers.fleet.view');
Route::get('/supplier/fleet/delete/{id}', 'Resource\VehicleResource@destroy_fleet')->name('drivenow.suppliers.fleet.destroy');

Route::get('/vehicles/repair', 'Resource\VehicleResource@repair_history')->name('vehicles.repair');
Route::post('/vehicles/repair/add', 'Resource\VehicleResource@add_repair')->name('vehicles.repair.add');
Route::get('/repair/approve/{id}', 'Resource\VehicleResource@approve_repair')->name('repairs.approve');
Route::get('/repair/decline/{id}', 'Resource\VehicleResource@decline_repair')->name('repairs.decline');

Route::post('/vehicles/repair/expense', 'Resource\VehicleResource@add_expenses')->name('vehicle.add_expense');

Route::get('/vehicles/tracker', 'Resource\VehicleResource@vehicle_tracker')->name('vehicles.tracker');

Route::post('/paystack/pay', 'Resource\ProviderResource@redirectToGateway')->name('drivenow.paystack.pay');

// Route::get('/paystack/callback', 'Resource\ProviderResource@handleGatewayCallback')->name('drivenow.paystack.payment');

//Payment Reminder

Route::get('/drivenow_reminder', 'Resource\ProviderResource@drivenow_payment_reminder')->name('drivenow.reminder');

Route::post('/vehicle_assign', 'Resource\ProviderResource@vehicle_assign')->name('drivenow.vehicle.assign');

Route::get('/drivenow_contract', 'AdminController@drivenow_contracts')->name('drivenow.contract.index');

Route::get('/drivenow_contract/create', 'AdminController@drivenow_contract_create')->name('drivenow.contract.create');

Route::get('/drivenow_contract/default/{id}', 'AdminController@drivenow_contract_default')->name('drivenow.contract.default');

Route::get('/drivenow_contract/approve/{id}', 'AdminController@drivenow_contract_approve')->name('drivenow.contract.approve');

Route::get('/drivenow_contract/disable/{id}', 'AdminController@drivenow_contract_disable')->name('drivenow.contract.disable');

Route::post('/drivenow_contract/store', 'AdminController@drivenow_contract_add')->name('drivenow.contract.store');

Route::post('/drivenow_contract/update', 'AdminController@drivenow_contract_update')->name('drivenow.contract.update');

//Change Driver contract

Route::post('/drivenow_contract/change', 'Resource\ProviderResource@drivenow_contract_change')->name('drivenow.contract.change');


//Blocked History

Route::get('/drivenow_blocked', 'Resource\ProviderResource@drivenow_blocked_history')->name('drivenow.blocked');

//Add to Driver to Drivenow Daily Payments

Route::get('/drivenow_due_daily_payment', 'Resource\ProviderResource@drivenow_daily')->name('drivenow.drivenow_daily');

Route::post('drivenow_daily', 'Resource\ProviderResource@add_to_drivenow_daily_due')->name('drivenow.daily.add');

Route::get('drivenow_daily/{id}', 'Resource\ProviderResource@add_to_drivenow_daily')->name('drivenow.add_daily');

Route::get('/drivenow_daily_reminder', 'Resource\ProviderResource@drivenow_daily_payment_reminder')->name('drivenow.daily_reminder');

//Bulk Block vehicle Engine of driver with daily due and device offline

Route::get('/drivenow_driver/daily_bulk_engine_off', 'Resource\ProviderResource@block_daily_due_drivers')->name('drivenow.engine_control_daily.bulk');

Route::get('remove_drivenow_daily/{id}', 'Resource\ProviderResource@remove_drivenow_daily')->name('drivenow.remove_daily_pay');

Route::post('change_drivenow_daily', 'Resource\ProviderResource@change_drivenow_daily')->name('drivenow.daily.change');

Route::get('/online_engine/{status}/{id}', 'Resource\ProviderResource@engine_control')->name('drivenow.online_control');

Route::get('/drivenow_invoice/{id}', 'Resource\ProviderResource@invoice_history')->name('drivenow.invoices');

Route::get('/drivenow_invoices', 'Resource\ProviderResource@invoice_histories')->name('drivenow.invoice_histories');

Route::get('drivenow_payment_review', 'Resource\ProviderResource@drivenow_payment_review')->name('drivenow.payment_review');

Route::get('/drivenow_add_charge', 'Resource\ProviderResource@drivenow_additional')->name('drivenow.add_charges');

Route::get('/drivenow_addional_charges/{id}', 'Resource\ProviderResource@additional_charges')->name('drivenow.driver_addional_charges');

Route::get('/drivenow_kyc', 'Resource\ProviderDocumentResource@drivenow_kyc')->name('drivenow.kyc');

Route::get('/drivenow_kyc/details/{id}', 'Resource\ProviderDocumentResource@drivenow_kyc_edit')->name('drivenow.kyc_edit');

Route::post('/drivenow_kyc', 'Resource\ProviderDocumentResource@drivenow_kyc_update')->name('drivenow.kyc.update');

Route::get('/drivenow/kyc/{id}/approve', 'Resource\ProviderDocumentResource@kyc_approve')->name('drivenow.kyc.approve');

Route::get('/drivenow/kyc/{id}/disapprove', 'Resource\ProviderDocumentResource@kyc_disapprove')->name('drivenow.kyc.disapprove');

//Drivenow Fleet Dashboard

Route::get('/drivenow_fleet', 'Resource\ProviderResource@drivenow_ut_due_payment')->name('drivenow.ut');

Route::get('/drivenow_fleet/transactions', 'Resource\ProviderResource@drivenow_ut_transactions')->name('drivenow.ut.transactions');

Route::get('/drivenow_fleet/due_break', 'Resource\ProviderResource@drivenow_ut_break')->name('drivenow.ut.drivenow_due_break');

// Route::get('/drivenow_fleet/failed_transaction', 'Resource\ProviderResource@drivenow_ut_due')->name('official_drivers.drivenow_due');

Route::get('/drivenow_fleet/drivenow_extra', 'Resource\ProviderResource@drivenow_ut_extra')->name('drivenow.ut.drivenow_extra');

Route::get('/drivenow_fleet/extra_due', 'Resource\ProviderResource@extra_ut_due')->name('drivenow.ut.extra_due');

Route::get('/drivenow_fleet/tracker', 'Resource\ProviderResource@drivenow_ut_tracker')->name('drivenow.ut.tracker');

Route::get('/drivenow_fleet/terminated', 'Resource\ProviderResource@drivenow_ut_terminated')->name('drivenow.ut.terminated');


//Fleet Dashboard

Route::get('/drivenow_vehicles', 'Resource\DrivenowResource@vehicle_list')->name('drivenow.vehicle.list');

Route::get('/drivenow_vehicles/{id}', 'Resource\DrivenowResource@vehicle_profile')->name('drivenow.vehicle.profile');

Route::get('/drivenow_vehicles', 'Resource\DrivenowResource@vehicle_list')->name('drivenow.vehicle.list');

Route::get('/drivenow_driver', 'Resource\DrivenowResource@driver_list')->name('drivenow.driver.list');

Route::get('/drivenow_settings','Resource\DrivenowResource@change_password')->name('drivenow.password');

Route::post('/drivenow_password_update','Resource\DrivenowResource@password_update')->name('drivenow.password.update');

Route::get('/drivenow_ut_transactions', 'Resource\DrivenowResource@transactions')->name('drivenow.transaction.history');

Route::get('/docs', 'Resource\DrivenowResource@docs_home')->name('drivenow.docs.home');

Route::get('/docs/list_driver', 'Resource\DrivenowResource@docs_list_driver')->name('drivenow.docs.list_driver');

Route::get('/docs/vehicle_profile', 'Resource\DrivenowResource@docs_vehicle_profile')->name('drivenow.docs.vehicle_profile');

Route::get('/docs/transactions', 'Resource\DrivenowResource@docs_transactions')->name('drivenow.docs.transactions');

Route::get('/docs/list_vehicles', 'Resource\DrivenowResource@docs_list_vehicles')->name('drivenow.docs.list_vehicles');

Route::get('drivenow_data', 'Resource\DrivenowResource@drivenow_data')->name('drivenow.data.driver');

Route::get('asset_data', 'Resource\DrivenowResource@asset_data')->name('drivenow.data.asset');

Route::get('ut_drivenow_data', 'Resource\DrivenowResource@ut_drivenow_data')->name('drivenow.data.ut_driver');

Route::get('ut_asset_data', 'Resource\DrivenowResource@ut_asset_data')->name('drivenow.data.ut_asset');

Route::get('drivenow_data/transactions', 'Resource\DrivenowResource@data_transaction')->name('drivenow.data.transactions');

Route::get('driver_transaction/{id}', 'Resource\DrivenowResource@driver_transaction')->name('drivenow.driver_transaction');

Route::get('drivenow_generate_token', 'Resource\DrivenowResource@generate_token')->name('drivenow.generate_token');

Route::get('/calc_credit_score', 'Resource\DrivenowResource@calculate_credit_score')->name('drivenow.calc_credit_score');




