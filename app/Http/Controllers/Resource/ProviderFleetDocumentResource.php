<?php

namespace App\Http\Controllers\Resource;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\SendPushNotification;

use App\Provider;
use App\ServiceType;
use App\ProviderService;
use App\ProviderDocument;
use App\Document;
use App\FleetPrice;
use Auth;
use Log;

class ProviderFleetDocumentResource extends Controller
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
            // $ServiceTypes = ServiceType::all();
            $ServiceTypes = FleetPrice::where('fleet_id',Auth::user()->id)->with('service')->orderBy('created_at','asc')->get();
            return view('fleet.providers.document.index', compact('Provider', 'ServiceTypes','ProviderService','VehicleDocuments','DriverDocuments'));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('fleet.index');
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

        return redirect()->route('fleet.provider.document.index', $provider)->with('flash_success', 'Provider service type updated successfully!');
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
            $Document = ProviderDocument::where('provider_id', $provider)->where('document_id',$id)
                ->first();

            return view('fleet.providers.document.edit', compact('Document'));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('fleet.index');
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
        try {

            $Document = ProviderDocument::where('provider_id', $provider)
                ->where('document_id', $id)
                ->firstOrFail();
            $Document->update(['status' => 'ACTIVE']);

            return redirect()
                ->route('fleet.provider.document.index', $provider)
                ->with('flash_success', 'Provider document has been approved.');
        } catch (ModelNotFoundException $e) {
            
            return redirect()
                ->route('fleet.provider.document.index', $provider)
                ->with('flash_error', 'Provider not found!');
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
            $Document->delete();

            return redirect()
                ->route('fleet.provider.document.index', $provider)
                ->with('flash_success', 'Provider document has been deleted');
        } catch (ModelNotFoundException $e) {
            return redirect()
                ->route('fleet.provider.document.index', $provider)
                ->with('flash_error', 'Provider not found!');
        }
    }

    /**
     * Delete the service type of the provider.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function service_destroy(Request $request, $provider, $id)
    {
        try {

            $ProviderService = ProviderService::where('provider_id', $provider)->where('service_type_id', $id)->firstOrFail();
            $ProviderService->delete();

            return redirect()
                ->route('fleet.provider.document.index', $provider)
                ->with('flash_success', 'Provider service has been deleted.');
        } catch (ModelNotFoundException $e) {
            return redirect()
                ->route('fleet.provider.document.index', $provider)
                ->with('flash_error', 'Provider service not found!');
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
                        $content = urlencode("This is the Eganow Team. You have not uploaded your documents for approval yet.  Please upload in driver app for approval  before you can drive on Eganow.".$docs);
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
}
