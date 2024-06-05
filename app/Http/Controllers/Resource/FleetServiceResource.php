<?php

namespace App\Http\Controllers\Resource;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Setting;
use Exception;
use App\Helpers\Helper;
use App\Provider;
use App\Fleet;
use App\ProviderProfile;
use App\ProviderService;
use App\UserRequestPayment;
use App\UserRequests;
use App\ServiceType;
use App\FleetPrice;
use Auth;

class FleetServiceResource extends Controller
{
   /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $services = FleetPrice::where('fleet_id',Auth::user()->id)->with('service')->orderBy('created_at','asc')->get();
        if($request->ajax()) {
            return $services;
        } else {
            return view('fleet.service.index', compact('services'));
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $service = ServiceType::all();
        $count = FleetPrice::where('fleet_id',Auth::user()->id)->with('service')->count();
        return view('fleet.service.create', compact('count','service'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
        $this->validate($request, [
            'service_id' => 'required',
            'fixed' => 'required|numeric',
            'price' => 'required|numeric',
            'time' => 'required',
            'description' => 'required|max:255',
            'commission' => 'required|numeric',
            'drivercommission' => 'required|numeric',
            'calculator' => 'required|in:FLEXI,FIXED',
            'image' => 'mimes:ico,png,jpg,jpeg'
        ]);

        try {

            $fleetservice = new FleetPrice;
            $fleetservice->fleet_id = Auth::user()->id;
            $fleetservice->service_id = $request->service_id;
            $fleetservice->minimum_fare = $request->minimum_fare;
            $fleetservice->base_radius = $request->base_radius;
            $fleetservice->fixed = $request->fixed;
            $fleetservice->time = $request->time;
            $fleetservice->price = $request->price;
            $fleetservice->commission = $request->commission;
            $fleetservice->drivercommission = $request->drivercommission;
            $fleetservice->status = 0;
            $fleetservice->description = $request->description;

            if($request->hasFile('image')) {
                $fleetservice->image = Helper::upload_picture($request->image);
            }
            
            $fleetservice->save();            
            return back()->with('flash_success',trans('admindashboard.service_saved'));
        } catch (Exception $e) {            
            return back()->with('flash_error', trans('admindashboard.service_not_found'));
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\ServiceType  $serviceType
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            return ServiceType::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', trans('admindashboard.service_not_found'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\ServiceType  $serviceType
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try {
            $service = FleetPrice::where('fleet_id',Auth::user()->id)->where('id', $id)->with('service')->first();
            $count = FleetPrice::where('fleet_id',Auth::user()->id)->get()->count();
            return view('fleet.service.edit',compact('service','count'));
        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', trans('admindashboard.service_not_found'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\ServiceType  $serviceType
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $this->validate($request, [
            'fixed' => 'required|max:255',
            'price' => 'required|max:255',
            'time' => 'required|max:255',
            'image' => 'mimes:ico,png',
            'commission' => 'required|numeric',
            'drivercommission' => 'required|numeric',
        ]);


        try {

            $service = FleetPrice::findOrFail($id);
            $service->base_radius = $request->base_radius;
            $service->minimum_fare = $request->minimum_fare;
            $service->fixed = $request->fixed;
            $service->price = $request->price;
            $service->time = $request->time;
            $service->status = 0;
            $service->description = $request->description;
            // $service->commission = $request->commission;
            $service->drivercommission = $request->drivercommission;

            if($request->hasFile('image')) {
                $service->image = Helper::upload_picture($request->image);
            }
            $service->save();

            return redirect()->route('fleet.service.index')->with('flash_success', 'Service details updated successfully. Wait for admin approval.');    
        } 

        catch (ModelNotFoundException $e) {
            return back()->with('flash_error', trans('admindashboard.service_not_found'));
        }
    }

    public function statement($id){

        try{

            $requests = UserRequests::where('service_type_id',$id)
                        ->where('status','COMPLETED')
                        ->where('fleet', Auth::user()->id)
                        ->with('payment')
                        ->get();

            $rides = UserRequests::where('service_type_id',$id)->where('fleet', Auth::user()->id)->with('payment')->orderBy('id','desc')->paginate(10);
            $cancel_rides = UserRequests::where('status','CANCELLED')->where('fleet', Auth::user()->id)->where('service_type_id',$id)->count();
            $Provider = ServiceType::find($id);
            $revenue = UserRequestPayment::whereHas('request', function($query) use($id) {
                                    $query->where('service_type_id', $id )->where('fleet', Auth::user()->id);
                                })->select(\DB::raw(
                                   'SUM(ROUND(fixed) + ROUND(distance)) as overall' 
                               ))->get();


            $Joined = $Provider->created_at ? '- created '.$Provider->created_at->diffForHumans() : '';

            return view('fleet.service.statement', compact('rides','cancel_rides','revenue'))
                        ->with('page',$Provider->name."'s Overall Statement ". $Joined);

        } catch (Exception $e) {
            return back()->with('flash_error','Something Went Wrong!');
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\ServiceType  $serviceType
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if(Setting::get('demo_mode', 0) == 1) {
            return back()->with('flash_error','Disabled for demo purposes! Please contact us at info@casa.matech.digital');
        }
        
        try {
            FleetPrice::where('fleet_id',Auth::user()->id)->where('service_id',$id)->delete();
            ProviderService::where('service_type_id', $id)->delete();
            return back()->with('flash_success', trans('admindashboard.service_deleted'));
        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', trans('admindashboard.service_not_found'));
        } catch (Exception $e) {
            return back()->with('flash_error', trans('admindashboard.service_not_found'));
        }
    }
}
