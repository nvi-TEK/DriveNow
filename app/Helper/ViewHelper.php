<?php

use App\PromocodeUsage; 
use Twilio\Rest\Client;


function currency($value = '')
{
	if($value == ""){
		return Setting::get('currency')." 0";
	}else{
		return Setting::get('currency')." ".$value;
	}
}

function distance($value = '')
{
    if($value == ""){
        return "0".Setting::get('distance', 'Km');
    }else{
        return $value.Setting::get('distance', 'Km');
    }
}

function img($img){
	if($img == ""){
		return asset('main/avatar.jpg');
	}else if (strpos($img, 'http') !== false) {
        return $img;
    }else{
		return asset('storage/'.$img);
	}
}

function promo_used_count($promo_id)
{
	return PromocodeUsage::where('status','USED')->where('promocode_id',$promo_id)->count();
}

function curl($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $return = curl_exec($ch);
    curl_close ($ch);
    return $return;
}

function calc_distance($lat1, $lon1, $lat2, $lon2, $unit) {

  $theta = $lon1 - $lon2;
  $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
  $dist = acos($dist);
  $dist = rad2deg($dist);
  $miles = $dist * 60 * 1.1515;
  $unit = strtoupper($unit);

  if ($unit == "K") {
    return ($miles * 1.609344);
  } else if ($unit == "N") {
      return ($miles * 0.8684);
    } else {
        return $miles;
      }
  }

function sendMessageTwilio($to, $content)
    {
      try{

        $account_sid = env('TWILIO_SID');
        $auth_token = env('TWILIO_AUTH_TOKEN');
        $twilio_number = env('TWILIO_NUMBER');

        $client = new Client($account_sid, $auth_token);

        $message = $client->messages->create($to, ['from' => $twilio_number, 'body' => $content]);


        return $message;

      } catch (Exception $e) {
             return FALSE;
        }
        
    }

function pushSMS($cc, $to, $message){
    
try{
    $to = str_replace(" ", "", $to);
            $from = "Eganow";
            if(str_contains($cc,"23") == true){
                $content = urlencode($message);
                $clientId = env("HUBTEL_API_KEY");
                $clientSecret = env("HUBTEL_API_SECRET");

                // $sendSms =  (new HubtelMessage)
                // ->from($from)
                // ->to($to)
                // ->content($content);

                $sendSms = sendSMS($from, $to, $content, $clientId, $clientSecret);
                if(count($sendSms) > 1){
                    return TRUE;
                }
                else if(count($sendSms) == 1 || $sendSms == FALSE){
                    $content = $message;
                    $mobile = $to;
                    if($mobile[0] == 0){
                        $receiver = "233".substr($mobile,1);
                    }else{
                        $receiver = "233".$mobile;
                    }
                    $sendMessage = sendMessageRancard($receiver, $content);
                    // if($mobile[0] == 0){
                    //     $receiver = $mobile;
                    // }else{
                    //     $receiver = "0".$mobile; 
                    // }

                    // $client = new \GuzzleHttp\Client();

                    // $url = "https://app.rancardmobility.com/rmcs/sendMessage.jsp?username=f3En@x&password=F@4YNh$&to=".$receiver."&from=Eganow&text=".$content."&smsc=RANCARD";
                    
                    // $headers = ['Content-Type' => 'application/json'];
                    
                    // $res = $client->get($url, ['headers' => $headers]);

                    // $code = (string)$res->getBody();
                    // $codeT = str_replace("\n","",$code);
                
                    if($sendMessage['code'] == "200"){
                        \Log::info($sendMessage['message']);
                        return TRUE;
                    }else{
                        $to = $cc . $to;
                        $content = $message;
                        $sendTwilio = sendMessageTwilio($to, $content);
                        //Log::info($sendTwilio);
                        if($sendTwilio){
                           return TRUE; 
                        }else{
                            return FALSE;
                        }
                    }
                }
                
                
            }
            else{
                $to = $cc . $to ;
                $content = $message;
                $sendTwilio = sendMessageTwilio($to, $content);
                //Log::info($sendTwilio);
                if($sendTwilio){
                   return TRUE; 
                }else{
                    return FALSE;
                }
            }
        }catch (Exception $e) {
            \Log::info($e);
            return FALSE;
        }

}

function sendSMS($from, $to, $content, $clientId, $clientSecret)
    {
        try{
            $send_sms = "https://api.hubtel.com/v1/messages/sendsend?From=Eganow&To=".$to."&Content=".$content."&ClientID=".$clientId."&ClientSecret=".$clientSecret."&RegisteredDelivery=true";
            
            $json = curl($send_sms);
            $details = json_decode($json, TRUE);
            return $details;

            } catch (Exception $e) {
             return FALSE;
        }
    }

function sendMessageRancard($to, $content){
    try{
        $senderID = env('RANCARD_SENDER_ID');
        $api_key = env('RANCARD_API_KEY');
        $client1 = new \GuzzleHttp\Client();
        $url = "https://unify-base.rancard.com/api/v2/sms/public/sendMessage";
        $headers = ['Content-Type' => 'application/json'];

        $status = $client1->post($url, [ 
            'headers' => $headers,
            'json' => ["apiKey" => $api_key,
                        "contacts" => [$to],
                        "message" => $content,
                        "scheduled" => false,
                        "hasPlaceholders" => false,
                        "senderId" => $senderID]]);

        $result = array();
        $result = json_decode($status->getBody(),'true');

        return $result;

    }catch(Exception $e){
        \Log::info($e);
        return FALSE;
    }

    
}