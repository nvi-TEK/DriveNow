<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Chat;
use App\User;
use App\Provider;
use App\UserRequests;
use App\RequestFilter;
use App\ProviderService;
use App\Referral;
use Setting;
use Log;
use App\Marketers;
use App\MarketerReferrals;
use App\DriverContracts;
use App\OfficialDriver;
use GuzzleHttp\Client;

use Tymon\JWTAuth\Exceptions\JWTException;

use Auth;
use Config;
use JWTAuth;
use Exception;
use Socialite;


use Carbon\Carbon;
use App\Http\Controllers\SendPushNotification;

class ChatController extends Controller
{
    public function save(Request $request)
    {
        
        $this->validate($request, [
                "user_id" => "required|integer",
                "provider_id" => "required|integer",
                "request_id" => "required|integer",
                "type" => "required|in:up,pu",
                "message" => "required",
            ]);
        $UserRequest = UserRequests::find($request->request_id);
        
            if($UserRequest->service_type->is_delivery == 1){
                $service_flow = "Delivery";
            }else{
                $service_flow= "Ride";
            }
        if($request->type == "up"){
            $user = User::find($request->user_id);
            $message = 'Message from User '. $user->first_name .': '. $request->message;
        (new SendPushNotification)->ChatProviderPush($request->provider_id, $message, $request->request_id, $service_flow);
        }
        else if($request->type == "pu"){
            $provider = Provider::find($request->provider_id);
            $message = 'Message from Driver '. $provider->first_name .': '. $request->message;
            (new SendPushNotification)->ChatUserPush($request->user_id, $message, $request->request_id, $service_flow);
        }
        $chat = new Chat;
        $chat->user_id = $request->user_id;
        $chat->provider_id = $request->provider_id;
        $chat->request_id = $request->request_id;
        $chat->type = $request->type;
        $chat->message = $request->message;
        $chat->save();
        return $chat;
    }

    public function addRider(Request $request)
    {
        $referral = new Referral;
        $referral->name = $request->rider_name;
        $referral->mobile = $request->mobile;
        $referral->type = 'rider';
        $referral->save();
        return $referral;
    }


    

    public function updatelocation(Request $request)
    {
        $this->validate($request, [
                'latitude' => 'required',
                'longitude' => 'required',
            ]);
        $data = array();

        if($Provider = Provider::find($request->provider_id)){

            $Provider->latitude = $request->latitude;
            $Provider->longitude = $request->longitude;
            $Provider->save();

            $UserRequest = UserRequests::findOrFail($request->request_id);
            if($UserRequest->status == 'ACCEPTED' || $UserRequest->status == 'ARRIVED' || $UserRequest->status == 'STARTED'){
                $s_latitude = $request->latitude;
                $s_longitude = $request->longitude;
                $d_latitude = $UserRequest->s_latitude;
                $d_longitude = $UserRequest->s_longitude;
            }
            if($UserRequest->status == 'PICKEDUP' || $UserRequest->status == 'DROPPED' || $UserRequest->status == 'COMPLETED'){
                $s_latitude = $request->latitude;
                $s_longitude = $request->longitude;
                $d_latitude = $UserRequest->d_latitude;
                $d_longitude = $UserRequest->d_longitude;
            }

            return $Provider;

        } else {
            return response()->json(['error' => 'Provider Not Found!']);
        }
    }

    public function getStatus(Request $request)
    {
        try{

            $provider = $request->provider_id;

            $AfterAssignProvider = RequestFilter::with(['request.user', 'request.payment', 'request' ,'request.service_type','request.provider_profiles'])
                ->where('provider_id', $provider)
                ->whereHas('request', function($query) use ($provider) {
                        $query->where('status','<>', 'CANCELLED');
                        $query->where('status','<>', 'SCHEDULED');
                        $query->where('provider_id', $provider );
                        $query->where('current_provider_id', $provider);
                    });
            
            $BeforeAssignProvider = RequestFilter::with(['request.user', 'request.payment', 'request','request.service_type','request.provider_profiles'])
                ->where('provider_id', $provider)
                ->whereHas('request', function($query) use ($provider){
                        $query->where('status','<>', 'CANCELLED');
                        $query->where('status','<>', 'SCHEDULED');
                        $query->where('current_provider_id',$provider);
                    });

            $IncomingRequests = $BeforeAssignProvider->union($AfterAssignProvider)->get();


             if(!empty($request->latitude)) {
                $Provider->update([
                        'latitude' => $request->latitude,
                        'longitude' => $request->longitude,
                ]);
            }

            $Timeout = Setting::get('provider_select_timeout', 180);
            $user_timeout = Setting::get('trip_search_time', 60);
                if(!empty($IncomingRequests)){
                    for ($i=0; $i < sizeof($IncomingRequests); $i++) {
                        $ExpiredTime = $user_timeout - (time() - strtotime($IncomingRequests[$i]->request->assigned_at));
                        if($IncomingRequests[$i]->request->status == 'SEARCHING' && $ExpiredTime < 0) {
                            UserRequests::where('id', $IncomingRequests[$i]->id)->update(['status' => 'CANCELLED']);

                            // No longer need request specific rows from RequestMeta
                            RequestFilter::where('request_id', $IncomingRequests[$i]->id)->delete();
                            Log::info('No Drivers found on time out period');
                            //  request push to user provider not available
                            (new SendPushNotification)->ProviderNotAvailable($IncomingRequests[$i]->user_id);
                        }
                        $IncomingRequests[$i]->time_left_to_respond = $Timeout - (time() - strtotime($IncomingRequests[$i]->request->assigned_at));
                        if($IncomingRequests[$i]->request->status == 'SEARCHING' && $IncomingRequests[$i]->time_left_to_respond < 0) {
                            Log::info("Provider timeout: ".$IncomingRequests[$i]->time_left_to_respond);
                            $this->assign_next_provider($IncomingRequests[$i]->request->id);
                        }
                                            
                        
                        if($IncomingRequests[$i]->request->payment != null){
                            if($IncomingRequests[$i]->request->payment_mode == 'MOBILE'){
                                $IncomingRequests[$i]->request->payment['payment_image'] = asset('asset/img/mobile.png');
                            }
                            if($IncomingRequests[$i]->request->payment_mode == 'CARD'){
                                $IncomingRequests[$i]->request->payment['payment_image'] = asset('asset/img/card.png');
                            }
                            if($IncomingRequests[$i]->request->payment_mode == 'CASH'){
                                $IncomingRequests[$i]->request->payment['payment_image'] = asset('asset/img/cash.png');
                            }
                        } 
                    }
                }
                $service = ProviderService::where('provider_id',$provider)
                                            ->with('service_type')
                                            ->get();

            $Provider = Provider::find($provider);
        
            $Response = [
                    'success' => TRUE,
                    'account_status' => $Provider->status,
                    'document_uploaded' => $Provider->document_uploaded,
                    'provider_image' => $Provider->avatar,
                    'service_status' => $Provider->availability,
                    'requests' => $IncomingRequests,
                    'provider' => $Provider,
                ];

            return $Response;

        } catch (ModelNotFoundException $e) {
            
            return response()->json(['success' => FALSE, 'message' => 'Something went wrong']);
        }
    }

     public function marketer_stats($refer)
    {
        $marketter = Marketers::where('referral_code', $refer)->first();
            if (is_null($marketter))
                {
                return redirect('/');
                } 
            else
                {
                $totalCount = MarketerReferrals::where('marketer_id', $marketter->id)->count();
                $providerCount = MarketerReferrals::where('marketer_id', $marketter->id)->where('driver_id', '!=', '')->count();
                $userCount = MarketerReferrals::where('marketer_id', $marketter->id)->where('user_id', '!=', '')->count();
                $totalAmount = MarketerReferrals::where('marketer_id', $marketter->id)->sum('amount');
                return view('marketer_status', compact('marketter', 'totalCount', 'providerCount', 'userCount','totalAmount'));
                }
    }

    public function RaveCallback(Request $request){
        Log::info($request->all());

        $client = new \GuzzleHttp\Client();

        $url = "https://api.flutterwave.com/v3/transactions/".$request->transaction_id."/verify";

        $headers = ['Content-Type' => 'application/json', 'Authorization' => "Bearer FLWSECK_TEST-d34a32229227445f3a39121e66d6f4ea-X"];
        
        $res = $client->get($url, ['headers' => $headers]);

        $code = $res->getBody();
        $token_details = json_decode($code, TRUE);
        dd($token_details);

    }

    public function agree(Request $request, $id)
    {

        $Provider = Provider::where('id', $id)->first();

        if($request->has('contract_id')){
            $driver_contract = DriverContracts::where('driver_id',$Provider->id)->where('id', $request->contract_id)->where('status',0)->first();
            if($driver_contract){
                $driver_contract->status = 1;
                $driver_contract->agreed_on = Carbon::now();
                $driver_contract->save();
            }
        }

        $Provider->agreed = 1;
        $Provider->agreed_on = Carbon::now();
        $Provider->save();

        $official_driver = OfficialDriver::where('driver_id', $Provider->id)->where('status', '!=', 1)->first();
        $official_driver->agreed = 1;
        $official_driver->agreed_on = Carbon::now();
        $official_driver->save();
        

        // return back();
        return redirect()->route('provider.drivenow');
            
    }
    

//     public function fetch_yt(){
//         try{
//             $array =array( 
//                         array("id"=>'ueazHJ4Zrek'), 
// array("id"=>'eTUut0nC38Y'), 
// array("id"=>'SoY5PeAy1A4')
//                     );
//             foreach ($array as $key => $arr) {
                
//                 $client = new \GuzzleHttp\Client();

//                 $url = "http://clientportal.conceptbiu.com/unifiedapi/admin/cron/fetch-youtube-by-video?client_id=4811&api_key=AIzaSyCLmcidrANMxBnaBGHqbOVXJ5cWYii10IU&topic_id=422&videoId=".$arr['id'];

//                 Log::info($url);

//                 $headers = ['Content-Type' => 'application/json', 'Authorization' => "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyIjoiNCRhbWl0LmNsaWVudEBjb25jZXB0Yml1LmNvbSJ9.VeM2QtccR6VhDsa8vC1XHQjU0GQA-wmdJRPaA2_cXmk"];
                
//                 $res = $client->get($url, ['headers' => $headers]);

//                 $code = $res->getBody();
//                 $token_details = json_decode($code, TRUE);
//                 Log::info($token_details);
                
//             }
            
//         }catch(Exception $e){
//             Log::info($e);
//         }
//     }

//     public function fetch_yt(){
//         try{
//             $array = array(
//                     // array("name"=>"India Today","handle"=>"@indiatoday"),
//                     // array("name"=>"IndiaGlitz Tamil","handle"=>"@IGtamil"),
//                     // array("name"=>"Indiatimes","handle"=>"@indiatimes"),
//                     // array("name"=>"MKN News","handle"=>"@MKNNEWSCHANNEL"),
//                     // array("name"=>"Movie Talkies","handle"=>"@movietalkies"),
//                     // array("name"=>"MY FM","handle"=>"@943MYFMIndia"),
//                     // array("name"=>"Navbharat Times नवभारत टाइम्स","handle"=>"@navbharattimes"),
//                     // array("name"=>"NDTV","handle"=>"@NDTV"),
//                     // array("name"=>"NDTV India","handle"=>"@ndtvindia"), 
//                     // array("name"=>"Radio City India","handle"=>"@myradiocityindia"),
//                     // array("name"=>"Red FM India","handle"=>"@redfmindia"),
//                     // array("name"=>"FilmiBeat","handle"=>"@filmibeat"),
//                     // array("name"=>"Curly Tales","handle"=>"@curlytalesdigital"),
//                     // array("name"=>"Lehren Mix","handle"=>"@LehrenMix"),
//                     // array("name"=>"Lehren Retro","handle"=>"@LehrenRetro"),
//                     // array("name"=>"Lehren Small Screen","handle"=>"@LehrenSmallScreen"),
//                     // array("name"=>"Lehren TV","handle"=>"@LehrenTV"),
//                     // array("name"=>"LET'S OTT⏳","handle"=>"@letsott2312"),
//                     // array("name"=>"Live Hindustan","handle"=>"@Livehindustan"),
//                     // array("name"=>"Bollywood Hungama","handle"=>"@BollywoodHungama"),
//                     // array("name"=>"Bollywood Life","handle"=>"@BollywoodLife"),
//                     // array("name"=>"Bollywood Spy","handle"=>"@BollywoodSpy"),
//                     // array("name"=>"BollywoodKilla","handle"=>"@BollywoodKilla"),
//                     // array("name"=>"Boogle Bollywood","handle"=>"@BoogleBollywood"),
//                     // array("name"=>"BookMyShow","handle"=>"@BookMyShowBoB"),
//                     // array("name"=>"BTV Bharat","handle"=>"@BTVBharat_TV"),
//                     // array("name"=>"Cine Speaks","handle"=>"@CineSpeaks"),
//                     // array("name"=>"Cineblues","handle"=>"@cineblues3780"),
//                     // array("name"=>"Cinestaan","handle"=>"@Cinestaan"),
//                     // array("name"=>"GOODTiMES","handle"=>"@mygoodtimes"),
//                     // array("name"=>"Mashable India","handle"=>"@Mashable_India"),
//                     // array("name"=>"Film Window","handle"=>"@FilmWindow"),
//                     // array("name"=>"Filme Shilmy","handle"=>"@filmeshilmy"),
//                     // array("name"=>"Filmline News","handle"=>"@FilmLineNews"),
//                     // array("name"=>"Filmy Galaxy","handle"=>"@FilmyGalaxy"),
//                     // array("name"=>"Filmy Khichdi","handle"=>"@fkhichdi"),
//                     // array("name"=>"First India Filmy","handle"=>"@FirstIndiaFilmyy"),
//                     // array("name"=>"Movie Talkies","handle"=>"@movietalkies"),
//                     // array("name"=>"Moviemate Media","handle"=>"@MovieMateMedia"),
//                     // array("name"=>"Moviez Adda","handle"=>"@MoviezAdda"),
//                     // array("name"=>"Movified","handle"=>"@Movified"),
//                     // array("name"=>"DESIFEED Video","handle"=>"@DESIFEEDVideo"),
//                     // array("name"=>"Desimartini","handle"=>"@DesiMartiniMovies"),
//                     // array("name"=>"Digital Paparazzi","handle"=>"@daddycool181"),
//                     // array("name"=>"OutlookMagazine","handle"=>"@OutlookMagazine"),
//                     // array("name"=>"ScoopWhoop","handle"=>"@Scoopwhoop"),
//                     // array("name"=>"SheThePeople TV","handle"=>"@SheThePeopleTV"),
//                     // array("name"=>"Telly Bytes - Tele News India","handle"=>"@tellybytes"),
//                     // array("name"=>"Telly Glam","handle"=>"@tellyglam"),
//                     // array("name"=>"Telly Masala","handle"=>"@tellymasala"),
//                     // array("name"=>"Telly Reporter","handle"=>"@TellyReporter"),
//                     // array("name"=>"Telly Tashan","handle"=>"@TellyTashan"),
//                     // array("name"=>"Telly Tweets","handle"=>"@TellyTweets"),
//                     // array("name"=>"TellyChakkar","handle"=>"@tellychakkar"),
//                     // array("name"=>"The Filmy Charcha - TFC","handle"=>"@TheFilmyCharcha"),
//                     // array("name"=>"The Indian Express","handle"=>"@indianexpress"),
//                     // array("name"=>"YourStory","handle"=>"@yourstorytv"),
//                     // array("name"=>"Fever FM","handle"=>"@FeverFMOfficial"),
//                     // array("name"=>"Fifafooz","handle"=>"@FIFAFOOZ"),
//                     // array("name"=>"Humans of Cinema","handle"=>"@HumansofCinema"),
//                     // array("name"=>"INDIWORLD ENTERTAINMENT","handle"=>"@indiworldentertainment"),
//                     // array("name"=>"Navodaya Times","handle"=>"@NavodayaTimestv"),
//                     // array("name"=>"NBT Entertainment","handle"=>"@NBTEnt"),
//                     // array("name"=>"Telly Chaska","handle"=>"@tellychaska"),
                    
//                     // array("name"=>"The Envoy Web","handle"=>"@theenvoyweb1147"),
//                     // array("name"=>"The Envoy Web India","handle"=>"@theenvoywebindia8918"),
//                     // array("name"=>"The Federal","handle"=>"@TheFederal"),
//                     // array("name"=>"Firstpost","handle"=>"@Firstpostt"),
//                     // array("name"=>"Forbes","handle"=>"@Forbes"),
//                     // array("name"=>"Gadgets 360","handle"=>"@Gadgets360"),
                   
//                     // array("name"=>"GQ India","handle"=>"@GQIndia"),
//                     // array("name"=>"Hauterrfly","handle"=>"@hauterrfly"),
                    
//                     // array("name"=>"iDIVA","handle"=>"@iDIVAOfficial"),
//                     // array("name"=>"India Forums","handle"=>"@indiaforums"),
//                     // array("name"=>"India News","handle"=>"@indianewspage"),
                    
//                     // array("name"=>"India99 TV","handle"=>"@India99TV"),
                    
//                     // array("name"=>"IndiaTV","handle"=>"@IndiaTV"),
                    
//                     // array("name"=>"Inshorts","handle"=>"@InshortsApp"),
//                     // array("name"=>"IshqFM","handle"=>"@ishqtak"),
//                     // array("name"=>"IWMBuzz","handle"=>"@IWMBuzz"),
//                     // array("name"=>"Koimoi","handle"=>"@koimoi"),
//                     // array("name"=>"LatestLY","handle"=>"@LatestLYIndia"),
                    
//                     // array("name"=>"LOKMAT","handle"=>"@Lokmat"),
//                     // array("name"=>"Loksatta","handle"=>"@Loksatta"),
//                     // array("name"=>"Manas Bollywood","handle"=>"@manasbollywood"),
//                     // array("name"=>"Mashable India","handle"=>"@Mashable_India"),
//                     // array("name"=>"MensXP","handle"=>"@mensxp"),
//                     // array("name"=>"midday india","handle"=>"@middayindia"),
                    
//                     // array("name"=>"moneycontrol","handle"=>"@moneycontrol"),
                    
//                     // array("name"=>"NEWJ","handle"=>"@NEWJplus"),
//                     // array("name"=>"News18 India","handle"=>"@news18India"),
//                     // array("name"=>"NewsX","handle"=>"@newsxlive"),
//                     // array("name"=>"Online DNA","handle"=>"@DNAIndiaNews"),
                    
//                     // array("name"=>"PeepingMoon","handle"=>"@peepingmoon2646"),
//                     // array("name"=>"Photofit Buzz","handle"=>"@photofitbuzz8750"),
                    
//                     // array("name"=>"Pop Diaries","handle"=>"@ipopdiaries"),
//                     // array("name"=>"POPxoDaily","handle"=>"@PopxoDaily"),
//                     // array("name"=>"Press News TV","handle"=>"@pressnewstv"),
//                     // array("name"=>"Puja Talwar","handle"=>"@pujatalwar"),
//                     // array("name"=>"Punjab Kesari TV","handle"=>"@punjabkesaritv"),
                   
//                     // array("name"=>"Radio Nasha Official","handle"=>"@RadioNashaOfficial"),
//                     // array("name"=>"Radio One International","handle"=>"@RadioOneInternational"),
//                     // array("name"=>"Rajshri","handle"=>"@Rajshri"),
                   
//                     // array("name"=>"SaasBahuAurBetiyaanOfficial","handle"=>"@SaasBahuAurBetiyaanOfficial"),
//                     // array("name"=>"Sanskriti Media","handle"=>"@sanskritimedia9441"),
                    
//                     // array("name"=>"Scroll.in","handle"=>"@ScrollIn"),
                    
//                     // array("name"=>"SHOWSHA","handle"=>"@SHOWSHAIndia"),
//                     array("name"=>"Social Ketchup","handle"=>"@socialketchup2562"),
//                     // array("name"=>"Social Samosa","handle"=>"@SocialSamosa-SocialMedia"),
                    
                   
                    
//                     // array("name"=>"The Lallantop","handle"=>"@TheLallantop"),
//                     // array("name"=>"The Quint","handle"=>"@TheQuint"),
//                     // array("name"=>"The Wire","handle"=>"@TheWireNews"),
                    
//                     // array("name"=>"TV Tweets","handle"=>"@tvtweets9621"),
//                     // array("name"=>"TV9 Bharatvarsh","handle"=>"@TV9Bharatvarsh"),
//                     // array("name"=>"Tweak India","handle"=>"@TweakIndia"),
                    
//                     // array("name"=>"WhatonOTT","handle"=>"@whatonott4990"),
//                     // array("name"=>"WION","handle"=>"@WION"),
                    
//                     // array("name"=>"Zee News","handle"=>"@zeenews"),
//                     // array("name"=>"zoom","handle"=>"@zoomtv")
//                 );
// // $api_key = "AIzaSyBWea1bA5LMbSHozaXDfpHKKukTIhsaZok";
// // $api_key = "AIzaSyCNKHrp9seGxc9rjteUxFv8w8bDMaanEtc";
// // $api_key = "AIzaSyBEMQU1-Xr981epiKT_r-6c2KSkJ1pm_-0";
// // $api_key = "AIzaSyDu4dU9Y18OSOffAJ7bu_qBT-VMF_GifR0";
// // $api_key = "AIzaSyCLmcidrANMxBnaBGHqbOVXJ5cWYii10IU";
// // $api_key = "AIzaSyC6FcXiogq3xnUEx5K7ls3hBmTP0fH6COU";
// // $api_key = "AIzaSyBEMQU1-Xr981epiKT_r-6c2KSkJ1pm_-0";
// // $api_key = "AIzaSyDQheH-4GSeK26TuvKCjSCK2NeZWHuG9Hk";
// // $api_key = "AIzaSyAxQf-E02Q_WuTv3vCdc8yXaNJyuZ6Ton0";
// // $api_key = "AIzaSyAFjoViSBkgxBaHknLuB7G_db2ZKzWqP4k";
// $api_key = "AIzaSyAu-xf1yZ8AUkNemDRItyR4Yghpry0IXcY";

//                 foreach ($array as $key => $arr) {
                        
//                         $client = new \GuzzleHttp\Client();

//                         $url = "http://clientportal.conceptbiu.com/unifiedapi/admin/cron/fetch-youtube-channel?client_id=4811&searchParams[from_date]=09/01/2023&searchParams[to_date]=09/30/2023&channel_user_name=".$arr['handle']."&channel_name=".$arr['name']."&max_count=50&api_key=".$api_key."&topic_ids[]=437&topic_ids[]=436&topic_ids[]=435&topic_ids[]=434&topic_ids[]=433&topic_ids[]=432&topic_ids[]=431&topic_ids[]=430&topic_ids[]=429&topic_ids[]=428&topic_ids[]=427&topic_ids[]=422&topic_ids[]=976";

//                         Log::info($url);

//                         $headers = ['Content-Type' => 'application/json', 'Authorization' => "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyIjoiNCRhbWl0LmNsaWVudEBjb25jZXB0Yml1LmNvbSJ9.VeM2QtccR6VhDsa8vC1XHQjU0GQA-wmdJRPaA2_cXmk"];
                        
//                         $res = $client->get($url, ['headers' => $headers]);

//                         $code = $res->getBody();
//                         $token_details = json_decode($code, TRUE);
//                         Log::info($token_details);
                        
//                     }
                    
                    
//                 }catch(Exception $e){
//                     Log::info($e);
//                 }

    
//     }


}

