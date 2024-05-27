<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Provider;
use App\ProviderDevice;
use App\UserRequests;
use Exception;
use App\Notification;

class SendPushNotification extends Controller
{
	/**
     * New Ride Accepted by a Driver.
     *
     * @return void
     */
    public function RideAccepted($request, $flow){
    	return $this->sendPushToUser($request->user_id, trans('api.push.request_accepted'), 'Accepted', 'Eganow: Request Accepted', '', $flow);
    }

     /**
     * Driver Started to your location.
     *
     * @return void
     */
    public function DriverStarted($request, $flow){
        return $this->sendPushToUser($request->user_id, trans('api.push.started'), 'Started', 'Eganow: Driver Started', '', $flow);
    }

    /**
     * Driver Arrived at your location.
     *
     * @return void
     */
    public function Arrived($request, $flow){

        return $this->sendPushToUser($request->user_id, trans('api.push.arrived'), 'Arrived', 'Eganow: Driver Arrived', '', $flow);
    }

    public function DriverApproved($id){
        $notification = new Notification;
        $notification->driver_id = $id;
        $notification->title = "Eganow: Driver Approved";
        $notification->message = trans('api.push.document_verfied');
        $notification->save();
        return $this->sendPushToProvider($id, trans('api.push.document_verfied'), 'Approved', 'Eganow: Driver Approved', '', '');
    }

    public function DriverDocumentApproved($id, $doc_name){
        $notification = new Notification;
        $notification->driver_id = $id;
        $notification->title = "Eganow: Driver Approved Document";
        $notification->message = "Admin has approved your ". $doc_name ." document";
        $notification->save();
        return $this->sendPushToProvider($id, 'Admin has approved your '. $doc_name .' document', 'Document Approved', 'Eganow: Driver Approved Document', '', '');
    }

    public function DriverDocumentDeclined($id, $doc_name){
        $notification = new Notification;
        $notification->driver_id = $id;
        $notification->title = "Eganow: Driver Declined Document";
        $notification->message = "Admin has declined your ". $doc_name ." document. Please check and resubmit";
        $notification->save();
        return $this->sendPushToProvider($id, 'Admin has declined your '. $doc_name .' document. Please check and resubmit', 'Document Declined', 'Eganow: Driver Declined Document', '', '');
    }

    public function DriverDisapproved($id){
        $notification = new Notification;
        $notification->driver_id = $id;
        $notification->title = "Eganow: Account suspended";
        $notification->message = "Your account has been suspended by service admin. Please contact our helpdesk";
        $notification->save();
        return $this->sendPushToProvider($id, 'Your account has been suspended by service admin. Please contact our helpdesk', 'Dispproved', 'Eganow: Driver Disapproved', '', '');
    }

    public function DriverTrialEnd($id){
        $notification = new Notification;
        $notification->driver_id = $id;
        $notification->title = "Eganow: Trial Period Expired";
        $notification->message = "Your 14 days trial has expired. Please upload necessary documents to continue to drive on Eganow";
        $notification->save();
        return $this->sendPushToProvider($id, 'Your 14 days trial has expired. Please upload necessary documents to continue to drive on Eganow', 'Dispproved', 'Eganow: Trial Period Expired', '', '');
    }

    public function DriverTrialStart($id){
        $notification = new Notification;
        $notification->driver_id = $id;
        $notification->title = "Eganow: Trial Period Activated";
        $notification->message = "Your account has been temporarily enabled to start work. You have not uploaded your documents yet and will need to upload all documents within 14 days";
        $notification->save();
        return $this->sendPushToProvider($id, 'Your account has been temporarily enabled to start work. You have not uploaded your documents yet and will need to upload all documents within 14 days', 'Approved', 'Eganow: Trial Period Activated', '', '');
    }

    public function DriverOffline($id){
        $notification = new Notification;
        $notification->driver_id = $id;
        $notification->title = "Eganow: You are Offline";
        $notification->message = "You have been switched offline as you have been online for more than 12 hours";
        $notification->save();

        return $this->sendPushToProvider($id, 'You have been switched offline as you have been online for more than 12 hours', 'Offline', 'Eganow: You are Offline', '', '');
    }

    public function DriverInActivity($id){
        // $notification = new Notification;
        // $notification->driver_id = $id;
        // $notification->title = "Eganow: Location lost";
        // $notification->message = "Location lost. Please reopen app to update your location to receive requests.";
        // $notification->save();

        return $this->sendPushToProvider($id, 'Location lost. Please reopen app to update your location to receive requests.', 'locationlost', 'Eganow: Location lost', '', '');
    }

    public function DriverOnline($id){
        $notification = new Notification;
        $notification->driver_id = $id;
        $notification->title = "Eganow: You are Online";
        $notification->message = "Eganow is live! You are online. Drive on Eganow, Drive for your future";
        $notification->save();
        return $this->sendPushToProvider($id, 'Eganow is live! You are online. Drive on Eganow, Drive for your future', 'Online', 'Eganow: You are Online', '', '');
    }

    public function AdminDriverOffline($id){
        $notification = new Notification;
        $notification->driver_id = $id;
        $notification->title = "Eganow: You are Offline";
        $notification->message = "Go Online to receive Request";
        $notification->save();

        return $this->sendPushToProvider($id, 'Go Online to receive Request', 'Offline', 'Eganow: You are Offline', '', '');
    }

     /**
     * Driver Picked.
     *
     * @return void
     */
    public function DriverPicked($request, $flow){

        return $this->sendPushToUser($request->user_id, trans('api.push.pickedup'),  'Picked', 'Eganow: Driver Pickedup', '', $flow);
    }

    public function RideCode($request, $flow){
        $notification = new Notification;
        $notification->user_id = $request->user_id;
        $notification->request_id = $request->id;
        $notification->title = "Eganow: Ride code";
        $notification->message = "Your driver has arrived. Please give this ride code to driver: ".$request->confirmation_code;
        $notification->save();
        return $this->sendPushToUser($request->user_id, 'Ride code: '.$request->confirmation_code, 'Picked', 'Eganow Ride Code', '', $flow);
    }

    public function ConfirmationCode($request, $flow){
        $notification = new Notification;
        $notification->user_id = $request->user_id;
        $notification->request_id = $request->id;
        $notification->title = "Eganow: Delivery Confirmation code";
        $notification->message = "Your Delivery code is : " . $request->confirmation_code ."  Please write in safe place";
        $notification->save();
        return $this->sendPushToUser($request->user_id, 'Your Delivery code is : ' . $request->confirmation_code .'  Please write in safe place', 'Picked', 'Eganow: Driver Pickedup', '', $flow);
    }

    /**
     * Driver Dropped.
     *
     * @return void
     */
    public function DriverDropped($request, $flow){

        return $this->sendPushToUser($request->user_id, trans('api.push.dropped'), 'Dropped', 'Eganow: ', '', $flow);
    }

    /**
     * Driver Confirmed the payment.
     *
     * @return void
     */
    public function PaymentConfirmed($request, $flow){

        return $this->sendPushToUser($request->user_id, trans('api.push.payment_confirmed'), 'Payment',  'Eganow: Payment Confirmed', '', $flow);
    }
    
    public function PaymentModeChanged($request){

        return $this->sendPushToProvider($request->provider_id, trans('api.push.payment_changed').$request->payment_mode , 'Payment',  'Eganow: Payment Method has been changed', '', '');
    }

    public function PaymentModeChangedDriver($request, $flow){

        return $this->sendPushToUser($request->user_id, trans('api.push.payment_changed').$request->payment_mode , 'Payment',  'Eganow: Payment Method has been changed to Cash', '', $flow);
    }

    /**
     * Driver Confirmed the payment.
     *
     * @return void
     */
    public function DriverRated($request, $flow){

        return $this->sendPushToUser($request->user_id, trans('api.push.dropped'), 'Driver Rated', 'Eganow: Driver Rated', '', $flow );
    }

    /**
     * Driver Confirmed the payment.
     *
     * @return void
     */
    public function UserRated($request){

        return $this->sendPushToProvider($request->provider_id, trans('api.push.user_rated'), 'User Rated', 'Eganow: User Rated', '', '');
    }

    public function RequestAssigned($provider, $flow){

        return $this->sendPushToProvider($provider, 'New request assigned to you. Please check.', 'User Rated', 'Eganow: New Request Assigned', '', $flow);

    }

     /**
     * Driver Arrived at your location.
     *
     * @return void
     */
    public function user_schedule($user){

        return $this->sendPushToUser($user, trans('api.push.schedule_start'), 'User Schedule', 'Eganow: Schedule Start', '', '');
    }

    /**
     * New Incoming request
     *
     * @return void
     */
    public function provider_schedule($provider){

        return $this->sendPushToProvider($provider, trans('api.push.schedule_start'), 'Driver Schedule', 'Eganow: Schedule Start', '', '');

    }

    public function user_schedule_hour($user){

        return $this->sendPushToUser($user, trans('api.push.user_schedule_start_hour'), 'User Schedule', 'Eganow: Schedule Service Reminder', '', '');
    }

    /**
     * New Incoming request
     *
     * @return void
     */
    public function provider_schedule_hour($provider){

        return $this->sendPushToProvider($provider, trans('api.push.driver_schedule_start_hour'), 'Driver Schedule', 'Eganow: Schedule Service Reminder', '', '');

    }

    /**
     * New Incoming request
     *
     * @return void
     */
    public function IncomingRequest($provider){
        // $notification = new Notification;
        // $notification->driver_id = $provider;
        // $notification->title = "Eganow: New Incoming Request";
        // $notification->message = trans('api.push.incoming_request');
        // $notification->save();
        return $this->sendPushToProvider($provider, trans('api.push.incoming_request'), 'New Request', 'Eganow: New Request', '', '');

    }

    /**
     * New Ride Accepted by a Driver.
     *
     * @return void
     */
    public function UserCancellRide($request){

        return $this->sendPushToProvider($request->current_provider_id, trans('api.push.user_cancelled'), 'User Cancel', 'Eganow: User has Cancelled', '', '');
    }

    public function AdminUserCancellRide($request, $flow){

        return $this->sendPushToProvider($request->current_provider_id, 'Your current Trip cancelled by Eganow', 'Admin Cancel', 'Eganow: Trip cancelled', '', $flow);
    }

    public function AdminDriverCancellRide($request, $flow){

        return $this->sendPushToUser($request->user_id, 'Your current Trip cancelled by Eganow', 'Admin Cancel', 'Eganow: Trip cancelled', '', $flow);
    }


    /**
     * New Ride Accepted by a Driver.
     *
     * @return void
     */
    public function ProviderCancellRide($request, $flow){

        $provider = Provider::find($request->current_provider_id);
        $message = $provider->first_name .' has cancelled the request!';

        return $this->sendPushToUser($request->user_id, trans('api.push.provider_cancelled'), 'Driver Cancel', 'Eganow: Request Cancellation', '', $flow);
    }


     /**
     * Money added to user wallet.
     *
     * @return void
     */
    public function ProviderNotAvailable($user_id, $service, $flow){

        return $this->sendPushToUser($user_id,'Sorry, no drivers available at this moment on '.$service.' Service. Please try our other service types', 'No Driver', 'Eganow: Drivers not available', '', $flow);
    }

    public function ScheduleConfirmed($request){

        return $this->sendPushToUser($request->user_id,'Your Scheduled booking has been confirmed', 'Scheduled Confirmation', 'Eganow: Scheduled booking Confirmed', '', '');
    }

    /** User Changed the destination */

    public function UserChangeDestination($id){

        return $this->sendPushToProvider($id, trans('api.push.user_change_destination'), 'User Destination Changed', 'Eganow Driver: Destination Changed', '', '');
    }


    /** User Changed the destination */

    public function DriverChangeDestination($id, $flow){

        return $this->sendPushToUser($id, trans('api.push.driver_change_destination'), 'Driver Destination Changed', 'Eganow: Destination Changed', '', $flow);
    }

    public function UserChangeDestinationReject($id){

        return $this->sendPushToProvider($id, trans('api.push.user_change_destination_reject'), 'User Destination Changed Reject', 'Eganow Driver: Destination Changed', '', '');
    }


    /** User Changed the destination */

    public function DriverChangeDestinationReject($id, $flow){

        return $this->sendPushToUser($id, trans('api.push.driver_change_destination_reject'), 'Driver Destination Changed Reject', 'Eganow: Destination Changed', '', $flow);
    }

    public function UserChangeDestinationRequest($id, $latitude, $longitude, $request_id, $address, $fare, $title){
        
        $details['request_id'] = $request_id;
        $details['latitude'] = $latitude;
        $details['longitude'] = $longitude;
        $details['title'] = $title;
        $details['address'] = $address;
        $details['fare'] = $fare;

        return $this->sendPushToProvider($id, trans('api.push.user_change_destination_request'), 'User Destination Request', 'Eganow Driver: Request to change Destination', $details, '');
    }


    /** User Changed the destination */

    public function DriverChangeDestinationRequest($id, $latitude, $longitude, $request_id, $address, $fare, $title, $flow){

        $details['request_id'] = $request_id;
        $details['latitude'] = $latitude;
        $details['longitude'] = $longitude;
        $details['title'] = $title;
        $details['address'] = $address;
        $details['fare'] = $fare;

        return $this->sendPushToUser($id, trans('api.push.driver_change_destination_request'), 'Driver Destination Request', 'Eganow: Request to change Destination', $details, $flow);
    }

    /** Custom push to user */

    public function CustomPushUser($id,$message,$title){

        // $notification = new Notification;
        // $notification->user_id = $id;
        // $notification->title = "A Message from Eganow";
        // $notification->message = $message;
        // $notification->save();
        return $this->sendPushToUser($id, $message, 'Custom Push', $title, '', '');
    }

    
    /** Custom push to Driver */

    public function CustomPushDriver($id,$message,$title){
        // $notification = new Notification;
        // $notification->driver_id = $id;
        // $notification->title = "A Message from Eganow";
        // $notification->message = $message;
        // $notification->save();

        return $this->sendPushToProvider($id, $message, 'Custom Push', $title, '', '');
    }

    public function DriverBreakTime($id,$message){
        
        return $this->sendPushToProvider($id, $message, 'Break Time', 'Eganow: Offline Alert', '', '');
    }

    public function DriverEngineUpdate($id,$message){
        
        return $this->sendPushToProvider($id, $message, 'Break Time', 'Eganow: Payment Due', '', '');
    }


     /**
     * Message from Driver.
     *
     * @return void
     */

    public function ChatUserPush($id, $message, $request_id, $flow){
        $user = User::find($id);
        $details = array();
        $request = UserRequests::find($request_id);
        $provider = Provider::find($request->current_provider_id);
        $details['user_id'] = $user->id;
        $details['user_name'] = $user->first_name .' '. $user->last_name;
        $details['user_image'] = $user->picture;
        $details['driver_id'] = $provider->id;
        $details['driver_name'] = $provider->first_name .' '. $provider->last_name;
        $details['driver_image'] = $provider->avatar;
        $details['request'] = $request_id;
        return $this->sendPushToUser($id, $message,'User Message', 'Eganow: '.$user->first_name.' has sent Message', $details, $flow);
    }

    /**
     * Message from User.
     *
     * @return void
     */
    
    public function ChatProviderPush($id, $message, $request_id, $flow){
        $provider = Provider::find($id);
         $details = array();
        $request = UserRequests::find($request_id);
        $user = User::find($request->user_id);
        $details['user_id'] = $user->id;
        $details['user_name'] = $user->first_name .' '. $user->last_name;
        $details['user_image'] = $user->picture;
        $details['driver_id'] = $provider->id;
        $details['driver_name'] = $provider->first_name .' '. $provider->last_name;
        $details['driver_image'] = $provider->avatar;
        $details['request'] = $request_id;
        return $this->sendPushToProvider($id, $message, 'User Message', 'Eganow: '.$provider->first_name.' has sent Message', $details, $flow);
    }

    public function DistanceAway($id, $kilometer, $mins, $request_id){
        $user = User::find($id);
        $details = array();
        $request = UserRequests::find($request_id);
        $provider = Provider::find($request->current_provider_id);
        $details['user_id'] = $user->id;
        $details['user_name'] = $user->first_name .' '. $user->last_name;
        $details['user_image'] = $user->picture;
        $details['driver_id'] = $provider->id;
        $details['driver_name'] = $provider->first_name .' '. $provider->last_name;
        $details['driver_image'] = $provider->avatar;
        $details['kilometer'] = $kilometer;
        $details['minutes'] = $mins;
        return $this->sendPushToUser($id, $mins, 'Driver Distance', 'Eganow: Your Driver '.$provider->first_name.' '.$mins. ' away', $details, '');
    }
    

    /**
     * Driver Documents verfied.
     *
     * @return void
     */
    public function DocumentsVerfied($provider_id){
        $notification = new Notification;
        $notification->driver_id = $provider_id;
        $notification->title = "Eganow: Document has been Verified";
        $notification->message = trans('api.push.document_verfied');
        $notification->save();

        return $this->sendPushToProvider($provider_id, trans('api.push.document_verfied'), 'Document Verified', 'Eganow: Document has been Verified', '', '');
    }


    /**
     * Money added to user wallet.
     *
     * @return void
     */
    public function WalletMoney($user_id, $money){
        $notification = new Notification;
        $notification->user_id = $user_id;
        $notification->title = "Eganow: Money has added to your wallet";
        $notification->message = $money." ".trans('api.push.added_money_to_wallet');
        $notification->save();

        return $this->sendPushToUser($user_id, $money.' '.trans('api.push.added_money_to_wallet'), 'Money to Wallet', 'Eganow: Money has added to your wallet', '', '');
    }

    /**
     * Money charged from user wallet.
     *
     * @return void
     */
    public function ChargedWalletMoney($user_id, $money){
        $notification = new Notification;
        $notification->user_id = $user_id;
        $notification->title = "Eganow: Money has charged from your wallet";
        $notification->message = $money." ".trans('api.push.charged_from_wallet');
        $notification->save();
        return $this->sendPushToUser($user_id, $money.' '.trans('api.push.charged_from_wallet'), 'Charge from Wallet', 'Eganow: Money has charged from your wallet', '', '');
    }

    public function PaymentComplete($user_id, $pay, $flow){

        if($pay == "CASH"){
            $mes = "Complete cash payment";
        }else{
            $mes = "Complete  payment in app";
        }

        return $this->sendPushToUser($user_id, $mes,  'Payment', 'Eganow: Trip Ended', '', $flow);
    }

    /**
     * Sending Push to a user Device.
     *
     * @return void
     */
    public function sendPushToUser($user_id, $push_message, $type, $title, $details, $trip){

    	try{

	    	$user = User::findOrFail($user_id);
            if($type == 'User Message'){
           $message = \PushNotification::Message($push_message, array('type' => $type,'title' => $title, 'user_id' => $details['user_id'], 'user_name' => $details['user_name'], 'user_image' => $details['user_image'], 'driver_id' => $details['driver_id'], 'driver_name' => $details['driver_name'], 'driver_image' => $details['driver_image'], 'request_id' => $details['request']));
            }
            else{
            $message = \PushNotification::Message($push_message, array('type' => $type,'title' => $title, 'details' => $details, 'trip' => $trip)); 
            }

            if($user->device_token != ""){

    	    	if($user->device_type == 'ios'){
                    // \Log::info('User iOS Push: '. $push_message);
    	    		$data = \PushNotification::app('IOSUser')
    		            ->to($user->device_token)
    		            ->send($message);
                        return $data;

    	    	}elseif($user->device_type == 'android'){
    	    		// \Log::info('User Android Push: '. $push_message);
    	    		$data = \PushNotification::app('AndroidUser')
    		            ->to($user->device_token)
    		            ->send($message);

                        return $data;

    	    	}
            }

    	} catch(Exception $e){
            \Log::info('User Push Error'.$e);
    		return $e;
    	}

    }

    /**
     * Sending Push to a user Device.
     *
     * @return void
     */
    public function sendPushToProvider($provider_id, $push_message, $type, $title, $details, $trip){

    	try{

	    	$provider = ProviderDevice::where('provider_id',$provider_id)->first();

            if($type == 'User Message'){
                $message = \PushNotification::Message($push_message, array('type' => $type,'title' => $title, 'user_id' => $details['user_id'], 'user_name' => $details['user_name'], 'user_image' => $details['user_image'], 'driver_id' => $details['driver_id'], 'driver_name' => $details['driver_name'], 'driver_image' => $details['driver_image'], 'request_id' => $details['request']));
            }elseif($type == 'New Request'){
                $message = \PushNotification::Message($push_message, array('type' => $type,'title' => $title, 'details' => $details, 'sound' => 'alert_tone.mp3'));     
            }
            else{
                $message = \PushNotification::Message($push_message, array('type' => $type,'title' => $title, 'details' => $details, 'trip' => $trip)); 
            }

            if($provider->token != ""){

            	if($provider->type == 'ios'){
            		// \Log::info('Provider iOS Push: '. $push_message);
            		$data = \PushNotification::app('IOSProvider')
        	            ->to($provider->token)
        	            ->send($message);
                        return $data;

            	}elseif($provider->type == 'android'){
            		// \Log::info('Provider Android Push: '. $push_message);
            		$data = \PushNotification::app('AndroidProvider')
        	            ->to($provider->token)
        	            ->send($message);
                        return $data;
            	}
            }

    	} catch(Exception $e){
            \Log::info('Provider Push Error'.$e);
    		return $e;
    	}

    }

}
