<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\UserRequestPayment;
use App\UserRequests;
use App\Card;
use App\User;
use App\Http\Controllers\SendPushNotification;
use App\RaveTransaction;
use App\Provider;
use Setting;
use Exception;
use Auth;
use Log;
use App\Helpers\Helper;


class PaymentController extends Controller
{
	/**
     * payment for user.
     *
     * @return \Illuminate\Http\Response
     */

    public function payment(Request $request){
     
             $request_id = $request->request_id;
             Log::info($request->all());

        try{
                    $UserRequest = UserRequests::find($request_id);
                    $UserRequest->payment_mode = $request->payment_mode;
                    $UserRequest->save();

                    $User = User::where('id', $UserRequest->user_id)->first();
                    
                     // (new SendPushNotification)->PaymentModeChanged($UserRequest);

                    $RequestPayment = UserRequestPayment::where('request_id',$request_id)->first(); 
                    $payToken = $UserRequest->payToken;

        //Web Card Payment

            if($request->has('resp')){

                $request_id = session('request_id');
                $flutter = json_decode($request->resp);
                $flutter_data = $flutter->tx;
              
                if($flutter->success == 'true'){

                    $payment_id = 'FnxTxCRef'.$request_id;
                    $payment_mode = 'CARD';
                    $paid = '1';
                    $status = 'COMPLETED';
                    (new SendPushNotification)->PaymentConfirmed($UserRequest);

                }
            }

            //App Card Payment
            else if($request->payment_mode == 'CARD'){
                    $request_id = $request->request_id;
                    $payment_id = 'replace with payment id'.$request_id;
                    $payment_mode = 'CARD';
                    $paid = '0';
                    

                        $client1 = new \GuzzleHttp\Client();
                        $headers = ['Content-Type' => 'application/json'];

                        $status_url = "https://app.slydepay.com/api/merchant/invoice/checkstatus";
                        $status = $client1->post($status_url, [ 
                            'headers' => $headers,
                            'json' => ["emailOrMobileNumber"=>"replace with correct info",
                                        "merchantKey"=>"replace with card number",
                                        "payToken"=>$payToken,
                                        "confirmTransaction" => true]]);

                        $result = array();
                        $result = json_decode($status->getBody(),'true');

                        $rave_transactions = new RaveTransaction;
                        $rave_transactions->last_balance = $User->wallet_balance;
                        $rave_transactions->user_id = $User->id;
                        $rave_transactions->request_id = $request_id;
                        $rave_transactions->reference_id = $payment_id;

                        if($request->has('payment_id')){
                            $rave_transactions->rave_ref_id = $request->payment_id;
                        }else{
                            $rave_transactions->rave_ref_id = $UserRequest->payToken;
                        }
                        if($request->has('flwref')){
                            $rave_transactions->flwref = $request->flwref;
                        }
                        $rave_transactions->narration = "User paid Invoice Total using Card / Mobile Money Payment";
                        $rave_transactions->amount = $RequestPayment->amount_to_collect;
                        if($request->has('app_fee')){
                            $rave_transactions->transaction_fee = $request->app_fee;
                        }
                        $rave_transactions->type = "debit";
                        $rave_transactions->save();

                        if($result['success'] == TRUE && $result['result'] == "CONFIRMED"){
                            $rave_transactions->status = 1;
                            $paid = 1;

                            (new SendPushNotification)->PaymentConfirmed($UserRequest);

                        }else if($result['success'] == TRUE && $result['result'] == "CANCELLED"){
                            $rave_transactions->status = 0;
                            $paid = 0;
                            return response()->json(['success' => FALSE, 'message' => "Payment Failed, Try again!"], 200);
                        }else if($result['success'] == TRUE && $result['result'] == "PENDING"){
                            $rave_transactions->status = 2;
                            $paid = 0;
                        }else{
                            $rave_transactions->status = 2;
                            $paid = 0;
                        }
                    $rave_transactions->save();
                    $status = 'COMPLETED';
                    $RequestPayment = UserRequestPayment::where('request_id',$request_id)->first(); 
                    $RequestPayment->payment_id = $payment_id;
                    $RequestPayment->payment_mode = $payment_mode;
                    $RequestPayment->save();

                    $UserRequest = UserRequests::find($request_id);
                    $UserRequest->paid = $paid;
                    $UserRequest->status = $status;
                    $UserRequest->payment_mode = $payment_mode;
                    $UserRequest->save();

                    return response()->json(['success' => TRUE, 'message' => "Transaction successful, Please wait for confirmation"], 200); 
            }

            //App Mobile Money Payment
            else if($request->payment_mode == 'MOBILE'){
                    $request_id = $request->request_id;
                    $payment_id = 'FnxTxMRef'.$request_id;
                    $payment_mode = 'MOBILE';
                    $paid = 0;
                    

                    if(str_contains($request->network,"AIRTEL") == true){
                        $request->network = "AIRTELTIGO_MONEY";
                    }else if(str_contains($request->network,"VODAFONE") == true){
                        $request->network = "VODAFONE_CASH";
                    }

                    if($request->has('mobile')){
                        $mobile = $request->mobile;
                    }else if($request->has('mobile_number')){
                        $mobile = $request->mobile_number;
                    }else{
                        $mobile = $User->mobile;
                    }

                    $code = rand(100000, 999999);
                    $name = substr($User->first_name, 0, 2);
                    $req_id = $name.$code;
                    $trans_id = "TrMP".$code;

                    session(['request_id' => $request_id]);
                    $RequestPayment = UserRequestPayment::where('request_id',$request_id)->first();

                    $amount = number_format($RequestPayment->amount_to_collect,2);
                    //SlydePay Create and Send Invoice

                    try{
                            $client = new \GuzzleHttp\Client();
                            $invoice_url = "https://posapi.usebillbox.com/webpos/payNow";
                            $headers = ['Content-Type' => 'application/json', "appId" => "e489fa8d-63cc-9d02-545f-8f6f81a3ceec"];
                            $json = response()->json(["requestId"=> $req_id,
                                            "appReference"=> "replace with actual reference",
                                            "secret"=> "password",
                                            "serviceCode"=> "670",
                                            "amount"=> $amount,
                                            "currency"=> "GHS",
                                            "customerSegment"=> "",
                                            "reference"=> "Paid GHS ".$amount." for the trip",
                                            "transactionId" => $trans_id,
                                            "provider" => $request->network,
                                            "walletRef" => $mobile,
                                            "customerName" => $User->first_name ." ". $User->last_name,
                                            "customerMobile" => $mobile]);

                            Log::info($json);
                            $res = $client->post($invoice_url, [ 
                                'headers' => $headers,
                                'json' => ["requestId"=> $req_id,
                                            "appReference"=> "replace with actual reference",
                                            "secret"=> "password",
                                            "serviceCode"=> "670",
                                            "amount"=> $amount,
                                            "currency"=> "GHS",
                                            "customerSegment"=> "",
                                            "reference"=> "Paid GHS ".$amount." for the trip",
                                            "transactionId" => $trans_id,
                                            "provider" => $request->network,
                                            "walletRef" => $mobile,
                                            "customerName" => $User->first_name ." ". $User->last_name,
                                            "customerMobile" => $mobile]]);

                            $code = $res->getStatusCode();
                            $result = array();
                            $result = json_decode($res->getBody(),'true');
                            Log::info($result);
                            if($result['success'] != 'true'){
                                return response()->json(['success' => FALSE, 'message' => 'Payment Failed, Try Again!']); 
                            }

                            if($result['success'] == TRUE){
                               
                                $paid = 0;

                                $rave_transactions = new RaveTransaction;
                                $rave_transactions->last_balance = $User->wallet_balance;
                                $rave_transactions->user_id = $User->id;
                                $rave_transactions->request_id = $request_id;
                                $rave_transactions->reference_id = $req_id;
                                $rave_transactions->rave_ref_id = $trans_id;
                                
                                $rave_transactions->narration = "User paid Invoice Total using MoMo Payment";
                                $rave_transactions->amount = $RequestPayment->amount_to_collect;
                                
                                $rave_transactions->status = 2;
                                $rave_transactions->type = "debit";
                                $rave_transactions->save();

                                $code = rand(1000, 9999);
                                $name = substr($User->first_name, 0, 2);
                                $reference = "TrMPs".$code.$name;

                                //Check SlydePay Payment Status

                                $client1 = new \GuzzleHttp\Client();
                                $status_url = "https://posapi.usebillbox.com/webpos/checkPaymentStatus";
                                $headers = ['Content-Type' => 'application/json', "appId" => "e489fa8d-63cc-9d02-545f-8f6f81a3ceec"];
                                
                                $status = $client1->post($status_url, [ 
                                    'headers' => $headers,
                                    'json' => ["requestId" => $reference,
                                                "appReference" => "replace with actual reference",
                                                "secret" => "password",
                                                "transactionId" => $trans_id]]);

                                $result = array();
                                $result = json_decode($status->getBody(),'true');

                                 if($result['success'] == TRUE && $result['result']['status'] == "CONFIRMED"){

                                    //Update User Transaction status
                                    $rave_transactions->status = 1;
                                    $rave_transactions->flwref = $result['result']['receiptNo'];
                                    $paid = 1;

                                    //Update Driver Wallet for Mobile payment Transaction
                                    $update_provider = Provider::find($UserRequest->provider_id);
                                    $update_provider->wallet_balance += $UserRequest->money_to_wallet;
                                    $update_provider->save();

                                    $code = rand(1000, 9999);
                                    $name = substr($update_provider->first_name, 0, 2);
                                    $reference = "TWP".$code.$name;

                                    //Create Entry on Transaction table for adding money to driver wallet
                                    $driver_transactions = new RaveTransaction;
                                    $driver_transactions->last_balance = $update_provider->wallet_balance;
                                    $driver_transactions->last_availbale_balance = $update_provider->available_balance;
                                    $driver_transactions->driver_id = $update_provider->id;
                                    $driver_transactions->reference_id = $reference;
                                    $driver_transactions->narration = "Payment for trip: ". $UserRequest->booking_id;
                                    $driver_transactions->amount = number_format($UserRequest->money_to_wallet,2);
                                    $driver_transactions->status = 1;
                                    $driver_transactions->type = "credit";
                                    $driver_transactions->credit = 0;
                                    $driver_transactions->save();


                                    if($UserRequest->service_type->is_delivery == 1){
                                            $service_flow = "Delivery";
                                    }else{
                                        $service_flow= "Ride";
                                    }
                                    (new SendPushNotification)->PaymentConfirmed($UserRequest, $service_flow);



                                }else if($result['success'] == TRUE && $result['result']['status'] == "FAILED"){
                                    $rave_transactions->status = 0;
                                    $paid = 0;
                                    return response()->json(['success' => FALSE, 'message' => 'Payment Failed, Try Again!']);
                                }else if($result['success'] == TRUE && $result['result']['status'] == "PENDING"){
                                    $rave_transactions->status = 2;
                                    $paid = 0;
                                }else{
                                    $rave_transactions->status = 2;
                                    $paid = 0;
                                }
                                $rave_transactions->save();

                                
                                $status = 'COMPLETED';
                                $RequestPayment = UserRequestPayment::where('request_id',$request_id)->first(); 
                                $RequestPayment->payment_id = $payment_id;
                                $RequestPayment->payment_mode = $payment_mode;
                                $RequestPayment->save();

                                $UserRequest = UserRequests::find($request_id);
                                $UserRequest->paid = $paid;
                                $UserRequest->status = $status;
                                $UserRequest->payment_mode = $payment_mode;
                                $UserRequest->payToken = $trans_id;
                                $UserRequest->save();

                                return response()->json(['success' => TRUE, 'message' => "Transaction successful, Please wait for confirmation"], 200);
                            }else{
                                return response()->json(['success' => FALSE, 'message' => 'Payment Failed, Try Again!']); 
                            }

                        }catch(Exception $e){
                            Log::info($e);
                            return response()->json(['success' => FALSE, 'message' => 'Payment Failed, Try Again!']);  
                        }               

            }
            else if($request->payment_mode == 'CASH'){

                        $request_id = $request->request_id;
                        $payment_id = 'FNXTxCASHRef'.$request_id;
                        $payment_mode = 'CASH';
                        $paid = '0';
                        $status = $UserRequest->status;

            }
            //Web Mobile Money Payment
            else{
                $request_id = session('request_id');
                $payment_id = 'FnxTxMRef'.$request_id;
                $payment_mode = 'MOBILE';
                $status = $UserRequest->status;
            }

            //Update the payment status

    		    $RequestPayment = UserRequestPayment::where('request_id',$request_id)->first(); 
                $RequestPayment->payment_id = $payment_id;
                $RequestPayment->payment_mode = $payment_mode;
                $RequestPayment->save();

                $UserRequest = UserRequests::find($request_id);
                $UserRequest->paid = $paid;
                $UserRequest->status = $status;
                $UserRequest->payment_mode = $payment_mode;
                $UserRequest->save();

                if($request->ajax()){
                   return response()->json(['success' => TRUE, 'message' => trans('api.paid')]); 
                }else{
                    return redirect('dashboard')->with('flash_success','Paid');
                }
        }
        catch (Exception $e) {
            if($request->ajax()){

                return response()->json(['success' => FALSE, 'message' => 'Something Went Wrong']); 

            }else{

                return back()->with('flash_error','Try again later');
            }
                
        }
    }

     public function confirm_transaction(Request $request){

            $request_id = $request->request_id;

            $UserRequest = UserRequests::find($request_id);

            $User = User::where('id', $UserRequest->user_id)->first();

            if($request->has('payToken')){
                $payToken = $request->payToken;
            }else{
                $payToken = $UserRequest->payToken;
            }

            $transaction = RaveTransaction::where('user_id', Auth::user()->id)->where('rave_ref_id', $payToken)->where('request_id', $request_id)->first();
            
            $code = rand(1000, 9999);
            $name = substr($User->first_name, 0, 2);
            $reference = "TrMPs".$code.$name;
            $client1 = new \GuzzleHttp\Client();
            $status_url = "https://posapi.usebillbox.com/webpos/checkPaymentStatus";
            $headers = ['Content-Type' => 'application/json', "appId" => "e489fa8d-63cc-9d02-545f-8f6f81a3ceec"];
            
            $status = $client1->post($status_url, [ 
                'headers' => $headers,
                'json' => ["requestId" => $reference,
                            "appReference" => "replace with actual reference",
                            "secret" => "password",
                            "transactionId" => $payToken]]);

            $result = array();
            $result = json_decode($status->getBody(),'true');

            if($result['success'] == TRUE && $result['result']['status'] == "CONFIRMED"){
                $transaction->status = 1;
                $UserRequest->paid = 1;

                    if($UserRequest->service_type->is_delivery == 1){
                                    $service_flow = "Delivery";
                    }else{
                        $service_flow= "Ride";
                    }
                    (new SendPushNotification)->PaymentConfirmed($UserRequest, $service_flow);

            }else if($result['success'] == TRUE && $result['result']['status'] == "FAILED"){
                $transaction->status = 0;
                $UserRequest->paid = 0;
            }else if($result['success'] == TRUE && $result['result']['status'] == "PENDING"){
                $transaction->status = 2;
                $UserRequest->paid = 0;
            }else{
                $transaction->status = 2;
                $UserRequest->paid = 0;
            }

            $UserRequest->save();
            $transaction->save();

            Log::info("Trxn ID: ". $payToken. "Status: ".$result['result']['status']);

            return response()->json(['success' => TRUE, 'data' => $UserRequest], 200);
     }

    


    /**
     * add wallet money for user.
     *
     * @return \Illuminate\Http\Response
     */
    public function add_money(Request $request){

        $this->validate($request, [
                'amount' => 'required|integer',
                'card_id' => 'required|exists:cards,card_id,user_id,'.Auth::user()->id
            ]);

        try{
            
            $StripeWalletCharge = $request->amount * 100;

            \Stripe\Stripe::setApiKey(Setting::get('stripe_secret_key'));

            $Charge = \Stripe\Charge::create(array(
                  "amount" => $StripeWalletCharge,
                  "currency" => "usd",
                  "customer" => Auth::user()->stripe_cust_id,
                  "card" => $request->card_id,
                  "description" => "Adding Money for ".Auth::user()->email,
                  "receipt_email" => Auth::user()->email
                ));

            $update_user = User::find(Auth::user()->id);
            $update_user->wallet_balance += $request->amount;
            $update_user->save();

            Card::where('user_id',Auth::user()->id)->update(['is_default' => 0]);
            Card::where('card_id',$request->card_id)->update(['is_default' => 1]);

            //sending push on adding wallet money
            (new SendPushNotification)->WalletMoney(Auth::user()->id,currency($request->amount));

            if($request->ajax()){
               return response()->json(['message' => currency($request->amount).trans('api.added_to_your_wallet'), 'user' => $update_user]); 
            }else{
                return redirect('wallet')->with('flash_success',currency($request->amount).' added to your wallet');
            }

        } catch(\Stripe\StripeInvalidRequestError $e){
          
            if($request->ajax()){
                 return response()->json(['error' => $e->getMessage()], 500);
            }else{
                return back()->with('flash_error',$e->getMessage());
            }
        } 

    }

    public function slydePayCallback(Request $request)
    {
        if($request->has('pay_token')){
            $payToken = $request->pay_token;
        }
        if($payToken != ''){

            if($request->status == 0){
                $data['status'] = 1;
                $data['flwref'] = $request->transac_id;
                $message = "Payment successful!";
                return response()->json(['success' => TRUE, 'message' => $message], 200);
            }else{
                $data['status'] = 0;
                $data['flwref'] = $request->transac_id;
                $message = "Payment failed, Try again!";
                return response()->json(['success' => FALSE, 'message' => $message], 200);
            }

        }else{
                $data['status'] = 0;
                $data['flwref'] = '';
                $message = "Payment failed, Try again!";
                return response()->json(['success' => FALSE, 'message' => $message], 200);
        }

        
        
    }

    public function create_invoice_wallet(Request $request){
        try{
             //Create Invoice and Generate SlydePay PayToken
            $client = new \GuzzleHttp\Client();
            $invoice_url = "https://posapi.usebillbox.com/webpos/createInvoice";
            $headers = ['Content-Type' => 'application/json', "appId" => "e489fa8d-63cc-9d02-545f-8f6f81a3ceec"];
            $payment_id = "SPFNXWR".rand(100,999);
            
            $code = rand(1000, 9999);
            $req_id = "FNXIN".$code;

            $res = $client->post($invoice_url, [ 
                    'headers' => $headers,
                    'json' => ["requestId" => $req_id,
                                "appReference" => "replace with actual reference",
                                "secret" => "password",
                                "merchantOrderId" => $payment_id,
                                "serviceCode" => "670",
                                "currency" => "GHS",
                                "amount" => $request->amount,
                                "reference" => "Wallet Topup of ". $request->amount]]);

            $code = $res->getStatusCode();
            $result = array();
            $result = json_decode($res->getBody(),'true');
            $payToken = $result['result']['invoiceNum'];
            Log::info("PayToken: ". $payToken);
            return response()->json(['success' => TRUE, 'payToken' => $payToken, 'merchant_id' => $payment_id]); 

        }catch (Exception $e) {
            Log::info($e);
            return response()->json(['success' => FALSE, 'message' => 'Payment Error']); 
        }
    }


   


}
