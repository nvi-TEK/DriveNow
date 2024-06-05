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

class AdminFleetServiceResource extends Controller
{
   /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($fleet)
    {

        try {
            $Fleet = Fleet::findOrFail($fleet);
            $FleetService = FleetPrice::where('fleet_id',$fleet)->with('service')->get();
            $ServiceTypes = ServiceType::all();
            return view('admin.fleet.assign_service', compact('Fleet', 'ServiceTypes','FleetService'));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('admin.fleet.index');
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $count = FleetPrice::where('fleet_id',Auth::user()->id)->with('service')->count();
        return view('admin.fleet.service.create', compact('count'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $fleet)
    {        
        try {

            $fleetserice = FleetPrice::where(['fleet_id' => $fleet, 'service_id' => $request->service_id])->first();
            if(empty($fleetserice))
            {
                $service = ServiceType::find($request->service_id);

                $fleetservice = new FleetPrice;
                $fleetservice->fleet_id = $fleet;
                $fleetservice->service_id = $request->service_id;
                $fleetservice->fixed = $service->fixed;
                $fleetservice->price = $service->price;
                $fleetservice->status = 1;
                $fleetservice->description = $service->description;
                $fleetservice->image = $service->image;                        
                $fleetservice->save();            
                return back()->with('flash_success',trans('admindashboard.service_saved'));
            }    
            else{
                return back()->with('flash_error', trans('admindashboard.service_exists'));
            }        
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
            $service = ServiceType::findOrFail($id);
            $position = FleetPrice::where('fleet_id',Auth::user()->id)->where('service_id', $id)->first();
            $count = FleetPrice::where('fleet_id',Auth::user()->id)->get()->count();
            return view('admin.fleet.service.edit',compact('service','count','position'));
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
            'name' => 'required|max:255',
            'image' => 'mimes:ico,png'
        ]);


        try {

            $service = FleetPrice::findOrFail($id);

            $service->name = $request->name;
            $service->fixed = $request->fixed;
            $service->price = $request->price;
            $service->time = $request->time;
            $service->status = 0;
            $service->description = $request->description;
            $service->calculator = $request->calculator;

            if($request->hasFile('image')) {
                $service->image = Helper::upload_picture($request->image);
            }
            $service->save();

            return redirect()->route('admin.fleet.service.index')->with('flash_success',  trans('admindashboard.service_updated'));    
        } 

        catch (ModelNotFoundException $e) {
            return back()->with('flash_error', trans('admindashboard.service_not_found'));
        }
    }

    public function approve($id)
    {

        try {

            $service = FleetPrice::findOrFail($id);
            $service->status = 1;
            $service->save();

            return back()->with('flash_success',  'Service Approved');    
        } 

        catch (ModelNotFoundException $e) {
            return back()->with('flash_error', trans('admindashboard.service_not_found'));
        }
    }

    public function decline($id)
    {

        try {

            $service = FleetPrice::findOrFail($id);
            $service->status = 0;
            $service->save();

            return back()->with('flash_success',  'Service Approve request Declined');    
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

            return view('admin.fleet.service.statement', compact('rides','cancel_rides','revenue'))
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

     public function EditPricing($fleet, $id)
    {
        try {
            $service = FleetPrice::where('fleet_id',$fleet)->where('id',$id)->first();
            $Fleet = Fleet::where('id', $fleet)->first();

            return view('admin.fleet.fleet_service_pricing', compact('Fleet','service'));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('admin.index');
        }
    }

        public function UpdatePricing(Request $request, $id)
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
            $service->fixed = $request->fixed;
            $service->price = $request->price;
            $service->time = $request->time;
            $service->status = 1;
            $service->description = $request->description;
            $service->commission = $request->commission;
            $service->drivercommission = $request->drivercommission;
            $service->base_radius = $request->base_radius;
            $service->minimum_fare = $request->minimum_fare;
            $service->commission = $request->commission;
            $service->drivercommission = $request->drivercommission;

            if($request->hasFile('image')) {
                $service->image = Helper::upload_picture($request->image);
            }
            $service->save();

            return redirect()->route('admin.fleet.assign.service', $service->fleet_id)->with('flash_success', 'Service Pricing updated successfully.');    
        } 

        catch (ModelNotFoundException $e) {
            return back()->with('flash_error', trans('admindashboard.service_not_found'));
        }
    }
}
