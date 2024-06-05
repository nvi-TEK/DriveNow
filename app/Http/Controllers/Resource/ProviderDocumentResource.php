<?php

namespace App\Http\Controllers\Resource;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\SendPushNotification;
use Log;
use Auth;
use Setting;
use Storage;
use App\Provider;
use App\ServiceType;
use App\ProviderService;
use App\ProviderDocument;
use App\Document;
use Carbon\Carbon;
use App\DriveNowDriverKYC;

class ProviderDocumentResource extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $provider)
    {
        try {
            $VehicleDocuments = Document::vehicle()->get();
            $DriverDocuments = Document::get();
            $Provider = Provider::findOrFail($provider);
            $ProviderService = ProviderService::where('provider_id',$provider)->with('service_type')->get();
           

            $ServiceTypes = ServiceType::all();
            return view('admin.providers.document.index', compact('Provider', 'ServiceTypes','ProviderService','VehicleDocuments','DriverDocuments'));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('admin.index');
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $provider)
    {
        if(Setting::get('demo_mode', 0) == 1) {
            return back()->with('flash_error', 'Disabled for demo purposes! Please contact us at ...');
        }

        $this->validate($request, [
                'service_type' => 'required|exists:service_types,id',
            ]);

        try {
            $ProviderService = ProviderService::where('provider_id', $provider)
                                ->where('service_type_id', $request->service_type)
                                ->firstOrFail();

            $ProviderService->update([
                    'service_type_id' => $request->service_type,
                    'status' => 'active',
                ]);

            // sending push to the provider

            (new SendPushNotification)->DocumentsVerfied($provider);

        } catch (ModelNotFoundException $e) {
            ProviderService::create([
                    'provider_id' => $provider,
                    'service_type_id' => $request->service_type,
                    'status' => 'active',
                ]);
        }

        return redirect()->route('admin.provider.document.index', $provider)->with('flash_success', 'Provider service type updated successfully!');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($provider, $id)
    {
        try {
            $Document = ProviderDocument::where('provider_id', $provider)
                ->where('document_id', $id)
                ->firstOrFail();

            return view('admin.providers.document.edit', compact('Document'));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('admin.index');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $provider, $id)
    {   
        if(Setting::get('demo_mode', 0) == 1) {
            return back()->with('flash_error', 'Disabled for demo purposes! Please contact us at ...');
        }

        try {

            $Document = ProviderDocument::where('provider_id', $provider)
                ->where('document_id', $id)
                ->firstOrFail();
            $Document->update(['status' => 'ACTIVE']);
            $doc = Document::find($Document->document_id);            
            (new SendPushNotification)->DriverDocumentApproved($provider, $doc->name);

            return redirect()->route('admin.provider.document.index', $provider)->with('flash_success', 'Provider document has been approved.');
        } catch (ModelNotFoundException $e) {
            return redirect()->route('admin.provider.document.index', $provider)->with('flash_error', 'Provider not found!');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($provider, $id)
    {
        try {

            $Document = ProviderDocument::where('provider_id', $provider)
                ->where('document_id', $id)
                ->firstOrFail();
            $Document->update(['status' => 'DECLINED']);

            $doc = Document::find($Document->document_id);

            $profile = Provider::find($provider);
            $to = $profile->country_code.$profile->mobile;
            $from = "Eganow Team";            
            $content = urlencode("This is the Eganow Team. Your ".$doc->name." has not been approved. This could be because it is not valid or not clear enough. Please check and upload a new copy in the drivers app. Thanks.");
            $clientId = env("HUBTEL_API_KEY");
            $clientSecret = env("HUBTEL_API_SECRET");            
            $sendSms = sendSMS($from, $to, $content, $clientId, $clientSecret);

            (new SendPushNotification)->DriverDocumentDeclined($provider, $doc->name); 

            return redirect()->route('admin.provider.document.index', $provider)->with('flash_success', 'Provider document has been declined');
        } catch (ModelNotFoundException $e) {
            return redirect()->route('admin.provider.document.index', $provider)->with('flash_error', 'Provider not found!');
        }
    }

    public function notifymissingdocuments($provider){ 
        try {
            $profile = Provider::find($provider);
            $available_documents = $profile->documents()->pluck('document_id');
            $missing_documents = Document::whereNotIn('id',$available_documents)->pluck('name');
            $docs = '';
                for ($i=0; $i < count($missing_documents) ; $i++) { 
                    $j = $i+1;
                    if($i == 0){
                        $docs .= "\n".$j. ". ". $missing_documents[$i]."\n";
                    }
                    else{
                        $docs .= $j. ". ". $missing_documents[$i]."\n";
                    }
                }
                    if($docs != ''){                        
                        $to = $profile->country_code.$profile->mobile;
                        $from = "Eganow Team";            
                        $content = urlencode("This is the Eganow Team. You have not uploaded your documents for approval yet.  Please upload in driver app for approval before you can drive on Eganow.".$docs);
                        $clientId = env("HUBTEL_API_KEY");
                        $clientSecret = env("HUBTEL_API_SECRET");            
                        $sendSms = sendSMS($from, $to, $content, $clientId, $clientSecret);
                    }
                    $profile->upload_notify = Carbon::now();
                    $profile->save();
          
            return $profile;
        } catch (Exception $e) {
            
            return back()->with('flash_error', 'Unable to send message');
        }
    }

    public function notify($provider, $id){
        try {
            $doc = Document::find($id);
            $profile = Provider::find($provider);
         
                        $to = $profile->country_code.$profile->mobile;
                        $from = "Eganow Driver";            
                        $content = urlencode("You have not uploaded your ".$doc->name.". Please upload in the driver app for approval.Thanks");
                        $clientId = env("HUBTEL_API_KEY");
                        $clientSecret = env("HUBTEL_API_SECRET");            
                        $sendSms = sendSMS($from, $to, $content, $clientId, $clientSecret);
                 
           
             return $profile;
        } catch (\Throwable $th) { 
            return back()->with('flash_error', 'Something went wrong. Please try again later.');
        }
    }

    public function drivenow_kyc(){
        try {
            $kyc = DriveNowDriverKYC::where('status', '!=',1)->with('driver','official')->get();
            $page = "Drive to Own Drive KYC";
            return view('admin.providers.profile.kyc_list', compact('kyc','page'));
        } catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something went wrong. Please try again later.');
        }
    }
    public function drivenow_kyc_edit($id)
    {

        try{
            $driver = Provider::where('id',$id)->first();
            $kyc = DriveNowDriverKYC::where('driver_id', $id)->with('driver','official','approved','uploaded')->first();

            if(count($kyc) == 0){
                $page = 'Add Driver KYC';
                return view('admin.providers.profile.kyc_create', compact('driver','page'));
            }else{
                $page = 'Update Driver KYC';
                return view('admin.providers.profile.kyc_edit', compact('driver','kyc','page'));
            }
            

        } catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something went wrong. Please try again later.');
        }
    }

    public function drivenow_kyc_update(Request $request)
    {
        try{
            // dd(json_encode($request->all()));
            $kyc = DriveNowDriverKYC::where('driver_id', $request->driver_id)->first();
            $Provider = Provider::where('id', $request->driver_id)->first();
            if(!$kyc){
                $kyc = New DriveNowDriverKYC;
            }

            $kyc->driver_id = $request->driver_id;
            $kyc->official_id = $request->official_id;
            $kyc->ghana_card_name = $request->ghana_card_name;
            $kyc->ghana_card_number = $request->ghana_card_number;
            $kyc->house_address = $request->house_address;
            $kyc->house_latitude = $request->house_latitude;
            $kyc->house_longitude = $request->house_longitude;
            $kyc->save();

            if ($request->hasFile('profile_picture')) {
                $name = $Provider->id."-kyc-".str_replace(' ','_',Carbon::now());
                $baseurl = 'replace with actual url to asset';                    
                $contents = file_get_contents($request->profile_picture);
                $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
                $s3_url = $baseurl.'/'.$name;
                $kyc->profile_picture = $s3_url; 
            }

            if ($request->hasFile('ghana_card_image')) {
                $name = $Provider->id."-kyc-".str_replace(' ','_',Carbon::now());
                $baseurl = 'replace with actual url to asset';                    
                $contents = file_get_contents($request->ghana_card_image);
                $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
                $s3_url = $baseurl.'/'.$name;
                $kyc->ghana_card_image = $s3_url; 
            }

            if ($request->hasFile('ghana_card_image_back')) {
                $name = $Provider->id."-kyc-".str_replace(' ','_',Carbon::now());
                $baseurl = 'replace with actual url to asset';                    
                $contents = file_get_contents($request->ghana_card_image_back);
                $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
                $s3_url = $baseurl.'/'.$name;
                $kyc->ghana_card_image_back = $s3_url; 
            }

            if ($request->hasFile('residence_image')) {
                $name = $Provider->id."-kyc-".str_replace(' ','_',Carbon::now());
                $baseurl = 'replace with actual url to asset';                    
                $contents = file_get_contents($request->residence_image);
                $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
                $s3_url = $baseurl.'/'.$name;
                $kyc->residence_image = $s3_url; 
            }

            if ($request->hasFile('water_bill_image')) {
                $name = $Provider->id."-kyc-".str_replace(' ','_',Carbon::now());
                $baseurl = 'replace with actual url to asset';                    
                $contents = file_get_contents($request->water_bill_image);
                $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
                $s3_url = $baseurl.'/'.$name;
                $kyc->water_bill_image = $s3_url; 
            }

            if ($request->hasFile('eb_bill_image')) {
                $name = $Provider->id."-kyc-".str_replace(' ','_',Carbon::now());
                $baseurl = 'replace with actual url to asset';                    
                $contents = file_get_contents($request->eb_bill_image);
                $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
                $s3_url = $baseurl.'/'.$name;
                $kyc->eb_bill_image = $s3_url; 
            }
            
            $kyc->g1_name = $request->g1_name;
            $kyc->g1_mobile = $request->g1_mobile;
            $kyc->g1_ghana_card_no = $request->g1_ghana_card_no;
            $kyc->g1_house_address = $request->g1_house_address;
            $kyc->g1_house_gps = $request->g1_house_gps;
            $kyc->g1_house_latitude = $request->g1_house_latitude;
            $kyc->g1_house_longitude = $request->g1_house_longitude;

            if ($request->hasFile('g1_profile_image')) {
                $name = $Provider->id."-kyc-".str_replace(' ','_',Carbon::now());
                $baseurl = 'replace with actual url to asset';                    
                $contents = file_get_contents($request->g1_profile_image);
                $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
                $s3_url = $baseurl.'/'.$name;
                $kyc->g1_profile_image = $s3_url; 
            }

            if ($request->hasFile('g1_ghana_card_image')) {
                $name = $Provider->id."-kyc-".str_replace(' ','_',Carbon::now());
                $baseurl = 'replace with actual url to asset';                    
                $contents = file_get_contents($request->g1_ghana_card_image);
                $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
                $s3_url = $baseurl.'/'.$name;
                $kyc->g1_ghana_card_image = $s3_url; 
            }

            if ($request->hasFile('g1_ghana_card_image_back')) {
                $name = $Provider->id."-kyc-".str_replace(' ','_',Carbon::now());
                $baseurl = 'replace with actual url to asset';                    
                $contents = file_get_contents($request->g1_ghana_card_image_back);
                $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
                $s3_url = $baseurl.'/'.$name;
                $kyc->g1_ghana_card_image_back = $s3_url; 
            }
            $kyc->g2_name = $request->g2_name;
            $kyc->g2_mobile = $request->g2_mobile;
            $kyc->g2_ghana_card_no = $request->g2_ghana_card_no;
            $kyc->g2_house_address = $request->g2_house_address;
            $kyc->g2_house_gps = $request->g2_house_gps;
            $kyc->g2_house_latitude = $request->g2_house_latitude;
            $kyc->g2_house_longitude = $request->g2_house_longitude;

            if ($request->hasFile('g2_profile_image')) {
                $name = $Provider->id."-kyc-".str_replace(' ','_',Carbon::now());
                $baseurl = 'replace with actual url to asset';                    
                $contents = file_get_contents($request->g2_profile_image);
                $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
                $s3_url = $baseurl.'/'.$name;
                $kyc->g2_profile_image = $s3_url; 
            }


            if ($request->hasFile('g2_ghana_card_image')) {
                $name = $Provider->id."-kyc-".str_replace(' ','_',Carbon::now());
                $baseurl = 'replace with actual url to asset';                    
                $contents = file_get_contents($request->g2_ghana_card_image);
                $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
                $s3_url = $baseurl.'/'.$name;
                $kyc->g2_ghana_card_image = $s3_url; 
            }

            if ($request->hasFile('g2_ghana_card_image_back')) {
                $name = $Provider->id."-kyc-".str_replace(' ','_',Carbon::now());
                $baseurl = 'replace with actual url to asset';                    
                $contents = file_get_contents($request->g2_ghana_card_image_back);
                $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
                $s3_url = $baseurl.'/'.$name;
                $kyc->g2_ghana_card_image_back = $s3_url; 
            }
            $kyc->uploaded_by = Auth::guard('admin')->user()->id;
            $kyc->uploaded_on = Carbon::now();
            $kyc->status = $request->status;
            $kyc->save();

            $flash_success = "KYC information saved successfully!";
            $driver = Provider::where('id', $request->driver_id)->first();
            $kyc = DriveNowDriverKYC::where('driver_id', $request->driver_id)->with('driver','official')->first();
            // return view('admin.providers.profile.kyc_edit', compact('driver','kyc','flash_success'));
            return redirect()->route('admin.drivenow.kyc_edit', $driver->id)->with('flash_success', "KYC information saved successfully!");
        } catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something went wrong. Please try again later.');
        }
    }

    public function kyc_approve($id)
    {
        try {
            
            $kyc = DriveNowDriverKYC::where('id', $id)->first();
            if($kyc->ghana_card_number != '') {
                $kyc->status = 1;
                $kyc->approved_by = Auth::guard('admin')->user()->id;
                $kyc->approved_on = Carbon::now();
                $kyc->save();
                return back()->with('flash_success', "Driver KYC details verfied");
            } else {
                return back()->with('flash_error', "Driver KYC details not found");
            }
        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    public function kyc_disapprove($id)
    {
        try {
            
            $kyc = DriveNowDriverKYC::where('id', $id)->first();
            if($kyc->ghana_card_number != '') {
                $kyc->status = 0;
                $kyc->approved_by = Auth::guard('admin')->user()->id;
                $kyc->approved_on = Carbon::now();
                $kyc->save();
                return back()->with('flash_success', "Driver KYC details verification declined");
            } else {
                return back()->with('flash_error', "Driver KYC details not found");
            }
        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }
}
