<?php 

namespace App\Helpers;

use File;
use Setting;
use App\DriveNowTransaction;
use App\OfficialDriver;
use App\DriveNowRaveTransaction;
use App\DriveNowExtraPayment;
use App\DriveNowAdditionalTransactions;
use App\Provider;
use App\Http\Controllers\SendPushNotification;
use Log;
use Carbon\Carbon;

class Helper
{

     public static function upload_picture($picture)
    {
        $file_name = time();
        $file_name .= rand();
        $file_name = sha1($file_name);
        if ($picture) {
            $ext = $picture->getClientOriginalExtension();
            $picture->move(public_path() . "/uploads", $file_name . "." . $ext);
            $local_url = $file_name . "." . $ext;

            $s3_url = url('/').'/uploads/'.$local_url;
            
            return $s3_url;
        }
        return "";
    }

    public static function delete_picture($picture) {
        File::delete( public_path() . "/uploads/" . basename($picture));
        return true;
    }

    public static function generate_booking_id() {
        return Setting::get('booking_prefix').mt_rand(100000, 999999);
    }

    public static function getKey($seckey)
     {
      $hashedkey       = md5($seckey);
      $hashedkeylast12 = substr($hashedkey, -12);
      
      $seckeyadjusted        = str_replace("FLWSECK-", "", $seckey);
      $seckeyadjustedfirst12 = substr($seckeyadjusted, 0, 12);
      
      $encryptionkey = $seckeyadjustedfirst12 . $hashedkeylast12;
      return $encryptionkey;
      
     }

     // Convert all NULL values to empty strings
        public static function null_safe($arr)
        {
            $newArr = array();
           
            foreach ($arr as $key => $value) {
                $newArr[$key] = ($value == null) ? "" : $value;
            }
            return $newArr;
        }


    // This is the encryption function that encrypts your payload by passing the stringified format and your encryption Key.
    public static function encrypt3Des($data, $key)
     {
      $encData = openssl_encrypt($data, 'DES-EDE3', $key, OPENSSL_RAW_DATA);
            return base64_encode($encData);
     }

     public static function ConfirmPayment($id){
        
        $credit_pending_transaction = DriveNowRaveTransaction::where('id',$id)->first();

        $reference = $credit_pending_transaction->slp_ref_id;

        $DriveNowTransaction = DriveNowTransaction::where('id', $credit_pending_transaction->bill_id)->first();
        $official_driver = OfficialDriver::where('driver_id', $credit_pending_transaction->driver_id)->with('vehicle')->where('status', '!=', 1)->first();
        $total_repayment = DriveNowRaveTransaction::where('official_id', $official_driver->id)->where('status',1)->sum('amount');
        $add_repayment = DriveNowRaveTransaction::where('official_id', $official_driver->id)->where('status',1)->sum('add_charge');
        $vehicle_repayment_before = $total_repayment - $add_repayment;
        
        if($DriveNowTransaction){
            $credit_pending_transaction->due = $DriveNowTransaction->due;
            $credit_pending_transaction->add_charge = $DriveNowTransaction->add_charge;
        }

        if($reference != ''){
            try{
                $client = new \GuzzleHttp\Client();
                $url = "https://api.paystack.co/transaction/verify/".$reference;
                $headers = ['Content-Type' => 'application/json', "Authorization"=> "Bearer sk_live_235b9f11a958ce63b4a264b15187b2ada92befcb"];
                $status = $client->get($url, ['headers' => $headers]);
                $result = array();
                $result = json_decode($status->getBody(),'true');
                Log::info($result);
                $pay_status = $result['data']['status'];
                
                if($pay_status == 'abandoned'){
                    $credit_pending_transaction->status = 3;
                    $credit_pending_transaction->due_before = $official_driver->amount_due;
                    $credit_pending_transaction->due_after = $official_driver->amount_due;
                    $credit_pending_transaction->save();
                }
                if($pay_status == 'success'){ //Checking to Ensure the transaction was succesful
        
                    $channel = $result['data']['authorization']['channel'];
                    $network = $result['data']['authorization']['bank'];
                    $fees = $result['data']['fees'];
                    $id = $result['data']['id'];
                    
                    $credit_pending_transaction->status = 1;
                    $credit_pending_transaction->network = $network;
                    $credit_pending_transaction->fees = ($fees / 100);
                    $credit_pending_transaction->slp_resp = $id;
                    $credit_pending_transaction->due_before = $official_driver->amount_due;
                    $credit_pending_transaction->total_before = $vehicle_repayment_before;
                    $credit_pending_transaction->save();

                    


                    $extra_due = DriveNowExtraPayment::where('official_id', $official_driver->id)->where('status','!=',1);
                    $daily_extra = $extra_due->sum('daily_due');
                    $weekly_extra = $extra_due->sum('due');

                    $vehicle_ded = $credit_pending_transaction->amount;
                    $extra_ded = 0;
                    if($official_driver->extra_pay > 0){

                        //Calculating Percentage of payment to deduct from due
                        if($official_driver->daily_drivenow == 1 && $official_driver->daily_due > 0){
                            $tot_due = $official_driver->daily_due + $official_driver->daily_due_add;
                            $vehicle_per = $official_driver->daily_due / $tot_due;
                            $extra_per = $official_driver->daily_due_add / $tot_due;
                        }
                        if($official_driver->daily_drivenow == 1 && $official_driver->daily_due <= 0){
                            $tot_due = $official_driver->daily_payment + $daily_extra;
                            $vehicle_per = $official_driver->daily_payment / $tot_due;
                            $extra_per = $daily_extra / $tot_due;
                        }
                        if($official_driver->amount_due > 0){
                            $tot_due = $official_driver->amount_due + $official_driver->amount_due_add;
                            $vehicle_per = $official_driver->amount_due / $tot_due;
                            $extra_per = $official_driver->amount_due_add / $tot_due;
                        }
                        if($official_driver->amount_due <= 0){
                            $tot_due = $official_driver->weekly_payment + $weekly_extra;
                            $vehicle_per = $official_driver->weekly_payment / $tot_due;
                            $extra_per = $weekly_extra / $tot_due;
                        }

                        $extra_ded = $credit_pending_transaction->amount * $extra_per;

                        if($official_driver->daily_drivenow == 1){
                            $official_driver->daily_due_add = ($official_driver->daily_due_add - $extra_ded);

                        }
                        $official_driver->amount_due_add = ($official_driver->amount_due_add - $extra_ded);

                        $extra_dues = $extra_due->get();
                        foreach ($extra_dues as $key => $extras) {
                            if($official_driver->daily_drivenow == 1){
                                $add_due = $extra_ded * ($extras->daily_due/$daily_extra);
                            }else{
                                $add_due = $extra_ded * ($extras->due/$weekly_extra);
                            }
                                $drivenow_add_due = DriveNowAdditionalTransactions::where('tran_id', $credit_pending_transaction->id)->where('type',$extras->id)->first();
                                if(!$drivenow_add_due){
                                    $drivenow_add_due = New DriveNowAdditionalTransactions;
                                }
                                
                                $drivenow_add_due->tran_id = $credit_pending_transaction->id;
                                $drivenow_add_due->driver_id = $official_driver->driver_id;
                                $drivenow_add_due->official_id = $official_driver->id;
                                $drivenow_add_due->paid_amount = number_format($extra_ded,2);
                                $drivenow_add_due->type = $extras->id;
                                $drivenow_add_due->amount = number_format($add_due,2);
                                $drivenow_add_due->save();
                        }

                        if($extra_ded >= $official_driver->extra_pay){

                            $official_driver->extra_pay = 0; 
                            DriveNowExtraPayment::where('official_id', $official_driver->id)->where('status','!=',1)->update(['status'=>1, 'completed_at'=> Carbon::now()]);

                        }
                        else{
                            $official_driver->extra_pay = ($official_driver->extra_pay - $extra_ded);
                        }
                        $official_driver->save();
                        $vehicle_ded = ($credit_pending_transaction->amount * $vehicle_per);
                    }  

                    if($official_driver->daily_drivenow == 1){
                        $official_driver->daily_due = $official_driver->daily_due - $vehicle_ded;
                    }
                    $official_driver->amount_due = ($official_driver->amount_due - $vehicle_ded);
                    $official_driver->amount_paid = ($official_driver->amount_paid + $vehicle_ded);
                    
                    $official_driver->save();

                    if($official_driver->vehicle->fleet->management_fee != ''){
                        $total_fee = $official_driver->vehicle->fleet->management_fee + $official_driver->vehicle->fleet->maintenance_fee + $official_driver->vehicle->fleet->insurance_fee + $official_driver->vehicle->fleet->road_worthy_fee + $official_driver->vehicle->fleet->company_share+$official_driver->vehicle->fleet->weekly;

                        $management_fee = round(($official_driver->vehicle->fleet->management_fee / $total_fee ) * $vehicle_ded);
                        $weekly = round(($official_driver->vehicle->fleet->weekly / $total_fee ) * $vehicle_ded);
                        $company_share = round(($official_driver->vehicle->fleet->company_share / $total_fee ) * $vehicle_ded);
                        $road_worthy_fee = round(($official_driver->vehicle->fleet->road_worthy_fee / $total_fee ) * $vehicle_ded);
                        $insurance_fee = round(($official_driver->vehicle->fleet->insurance_fee / $total_fee ) * $vehicle_ded);
                        $maintenance_fee = round(($official_driver->vehicle->fleet->maintenance_fee / $total_fee ) * $vehicle_ded);


                        $credit_pending_transaction->management_fee = $management_fee ;
                        $credit_pending_transaction->weekly = $weekly;
                        $credit_pending_transaction->company_share = $company_share;
                        $credit_pending_transaction->road_worthy_fee = $road_worthy_fee;
                        $credit_pending_transaction->insurance_fee = $insurance_fee;
                        $credit_pending_transaction->maintenance_fee = $maintenance_fee;
                        
                        $credit_pending_transaction->save();
                    }
                    $total_repayment = DriveNowRaveTransaction::where('official_id', $official_driver->id)->where('status',1)->sum('amount');
                    $add_repayment = DriveNowRaveTransaction::where('official_id', $official_driver->id)->where('status',1)->sum('add_charge');

                    $vehicle_repayment_after = $total_repayment - $add_repayment;
                    $credit_pending_transaction->due = $vehicle_ded;
                    $credit_pending_transaction->add_charge = $extra_ded;
                    $credit_pending_transaction->due_after = $official_driver->amount_due;
                    $credit_pending_transaction->total_after = $vehicle_repayment_after;
                    $credit_pending_transaction->save();

                    $Provider = Provider::where('id',$official_driver->driver_id)->first();

                    // if($official_driver->amount_due <=0 &&  $official_driver->engine_status == 1){
                    //     if($official_driver->imei_number != ''){
                    //         try{
                    //             $time = Carbon::now()->timestamp;
                    //             $account = "";
                    //             $password = "";
                    //             $signature = md5(md5($password).$time);

                    //             $url = "http://api.protrack365.com/api/authorization?time=".$time."&account=".$account."&signature=".$signature;

                    //             $json = curl($url);

                    //             $details = json_decode($json, TRUE);
                    //             Log::info("Tro Track Status". json_encode($details));
                    //             if($details['code'] != '10009') {
                                   
                    //                 $tro_access_token = $details['record']['access_token'];
                    //                 if($tro_access_token !=''){
                    //                     //Turn ON the Engine
                    //                      $url = "http://api.protrack365.com/api/command/send?access_token=".$tro_access_token."&imei=".$official_driver->imei_number."&command=RELAY,0";

                    //                     $json = curl($url);

                    //                     $details = json_decode($json, TRUE);

                    //                     $message = "Your vehicle has been reactivated. Contact DriveNow driver support team if you have any issues.";

                    //                     (new SendPushNotification)->DriverEngineUpdate($Provider->id,$message);

                    //                     $official_driver->engine_restore_reason = 'Payment Due';
                    //                     $official_driver->engine_restore_by = 0;
                    //                     $official_driver->engine_restore_on = Carbon::now();
                    //                     $official_driver->engine_status = 0;
                    //                     $official_driver->save();

                    //                     //Send SMS Notification
                    //                         $content = "Your vehicle has been reactivated. Contact Eganow driver support team if you have any issues.";
                    //                         $mobile = $Provider->mobile;

                    //                         if($mobile[0] == 0){
                    //                             $receiver = "233".substr($mobile,1);
                    //                         }else{
                    //                             $receiver = "233".$mobile;
                    //                         }
                    //                         $sendMessage = sendMessageRancard($receiver, $content);
                    //                         // if($mobile[0] == 0){
                    //                         //     $receiver = $mobile;
                    //                         // }else{
                    //                         //     $receiver = "0".$mobile; 
                    //                         // }

                    //                         // $client = new \GuzzleHttp\Client();

                    //                         // $url = "https://app.rancardmobility.com/rmcs/sendMessage.jsp?username=f3En@x&password=F@4YNh$&to=".$receiver."&from=Eganow&text=".$content."&smsc=RANCARD";

                    //                         // $headers = ['Content-Type' => 'application/json'];
                                            
                    //                         // $res = $client->get($url, ['headers' => $headers]);

                    //                         // $code = (string)$res->getBody();
                    //                         // $codeT = str_replace("\n","",$code);

                    //                 }
                    //             }

                    //         }catch(\GuzzleHttp\Exception\RequestException $e){
                    //             Log::info($e->getResponse()->getBody()->getContents());
                    //             if($e->getResponse()->getStatusCode() == '404' || $e->getResponse()->getStatusCode() == '500'){
                                    
                    //             }
                    //         }
                    //     }
                    // }
                }else if($pay_status == 'failed'){
                    // $channel = $result['data']['authorization']['channel'];
                    // $network = $result['data']['authorization']['bank'];
                    $id = $result['data']['id'];
                    $credit_pending_transaction->status = 0;
                    // $credit_pending_transaction->network = $network;
                    //  $credit_pending_transaction->fees = ($fees / 100);
                    $credit_pending_transaction->slp_resp = $id;
                    $credit_pending_transaction->due_before = $official_driver->amount_due;
                    $credit_pending_transaction->due_after = $official_driver->amount_due;
                    $credit_pending_transaction->save();
                }else {
                    $credit_pending_transaction->status = 3;
                    // $credit_pending_transaction->network = $network;
                    //  $credit_pending_transaction->fees = ($fees / 100);
                    // $credit_pending_transaction->slp_resp = $id;
                    $credit_pending_transaction->due_before = $official_driver->amount_due;
                    $credit_pending_transaction->due_after = $official_driver->amount_due;
                    $credit_pending_transaction->save();
                }
            }catch(\GuzzleHttp\Exception\RequestException $e){
                Log::info("Paystack".$e);
                // if($e->getResponse()->getStatusCode() == '404' || $e->getResponse()->getStatusCode() == '500'){
                    $credit_pending_transaction->status = 3;
                    $credit_pending_transaction->due_before = $official_driver->amount_due;
                    $credit_pending_transaction->due_after = $official_driver->amount_due;
                    $credit_pending_transaction->save();
                // }
            }
        }
    }

}
