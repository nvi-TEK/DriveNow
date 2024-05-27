<?php

namespace App\Http\Controllers\Resource;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Log;
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

class ServiceResource extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $services = ServiceType::all();
        if($request->ajax()) {
            return $services;
        } else {
            return view('admin.service.index', compact('services'));
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.service.create');
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
            'name' => 'required|max:255',
            'fixed' => 'required',
            'price' => 'required',
            'time' => 'required',
            'commission' => 'required',
            'drivercommission' => 'required',
            'description' => 'required|max:255',
            'calculator' => 'required|in:FLEXI,FIXED',
            'image' => 'mimes:ico,png,jpg,jpeg'
        ]);

        try {

            $service = new ServiceType;

            $service->name = $request->name;
            $service->base_radius = $request->base_radius;
            $service->minimum_fare = $request->minimum_fare;
            $service->fixed = $request->fixed;
            $service->price = $request->price;
            $service->time = $request->time;
            $service->is_delivery = $request->is_delivery;
            $service->description = $request->description;
            $service->calculator = $request->calculator;
            $service->commission = $request->commission;
            $service->drivercommission = $request->drivercommission;

            if($request->hasFile('image')) {
                $service['image'] = Helper::upload_picture($request->image);
            }
            
            $service->save();

            return back()->with('flash_success','Service Type Saved Successfully');
        } catch (Exception $e) {
            dd("Exception", $e);
            return back()->with('flash_error', 'Service Type Not Found');
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
            return back()->with('flash_error', 'Service Type Not Found');
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
            return view('admin.service.edit',compact('service'));
        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', 'Service Type Not Found');
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
        if(Setting::get('demo_mode', 0) == 1) {
            return back()->with('flash_error','Disabled for demo purposes! Please contact us at ...');
        }

        $this->validate($request, [
            'name' => 'required|max:255',
            'fixed' => 'required',
            'price' => 'required',
            'commission' => 'required',
            'drivercommission' => 'required',
            'image' => 'mimes:ico,png,jpg,jpeg'
        ]);

        try {

            $service = ServiceType::findOrFail($id);

            if($request->hasFile('image')) {
                if($service->image) {
                    Helper::delete_picture($service->image);
                }
                $service->image = Helper::upload_picture($request->image);
            }
            $service->name = $request->name;
            $service->base_radius = $request->base_radius;
            $service->minimum_fare = $request->minimum_fare;
            $service->fixed = $request->fixed;
            $service->price = $request->price;
            $service->time = $request->time;
            $service->is_delivery = $request->is_delivery;
            $service->description = $request->description;
            $service->calculator = $request->calculator;
            $service->commission = $request->commission;
            $service->drivercommission = $request->drivercommission;
            $service->save();

            return redirect()->route('admin.service.index')->with('flash_success', 'Service Type Updated Successfully');    
        } 

        catch (ModelNotFoundException $e) {
            return back()->with('flash_error', 'Service Type Not Found');
        }
    }

    public function statement($id){

        try{            
            $requests = UserRequests::where('service_type_id',$id)
                        ->where('status','COMPLETED')
                        ->with('payment')
                        ->get();

            $rides = UserRequests::where('service_type_id',$id)->with('payment')->orderBy('id','desc')->paginate(10);
            $cancel_rides = UserRequests::where('status','CANCELLED')->where('service_type_id',$id)->count();
            $Provider = ServiceType::find($id);
            $revenue = UserRequestPayment::whereHas('request', function($query) use($id) {
                                    $query->where('service_type_id', $id );
                                })->select(\DB::raw(
                                   'SUM(ROUND(fixed) + ROUND(distance)) as overall' 
                               ))->get();


            $Joined = $Provider->created_at ? '- created '.$Provider->created_at->diffForHumans() : '';

            return view('admin.service.statement', compact('rides','cancel_rides','revenue'))
                        ->with('page',$Provider->name."'s Overall Statement ". $Joined);

        } catch (Exception $e) {
            return back()->with('flash_error','Something Went Wrong!');
        }
    }

    public function fleet_statement($id, $fleet){

        try{

            $requests = UserRequests::where('service_type_id',$id)
                        ->where('fleet_id', $fleet)
                        ->where('status','COMPLETED')
                        ->with('payment')
                        ->get();

                        $Rides = UserRequests::where('service_type_id',$id)
                            ->where('fleet_id', $fleet)
                            ->where('status','<>','CANCELLED')
                            ->get()->pluck('id');

            $rides = UserRequests::where('service_type_id',$id)->where('fleet_id', $fleet)->with('payment')->orderBy('id','desc')->paginate(10);
            $cancel_rides = UserRequests::where('status','CANCELLED')->where('fleet_id', $fleet)->where('service_type_id',$id)->count();
            $Provider = ServiceType::find($id);
            $revenue = UserRequestPayment::whereIn('request_id', $Rides)->select(\DB::raw(
                                   'SUM(ROUND(fixed) + ROUND(distance_taken)) as overall' 
                               ))->get();


            $Joined = $Provider->created_at ? '- created '.$Provider->created_at->diffForHumans() : '';

            return view('fleet.statement.service-statement', compact('rides','cancel_rides','revenue'))
                        ->with('page',$Provider->name."'s Overall Statement ". $Joined);

        } catch (Exception $e) {
            
            return back()->with('flash_error','Something Went Wrong!');
        }
    }

    public function assign_service_drivers($id){
        $Providers = ProviderService::where('service_type_id', '!=', $id)->get();
        foreach ($Providers as $provider) {
            ProviderService::create([
                    'provider_id' => $provider->id,
                    'service_type_id' => $id,
                    'status' => 'active',
                ]);
        }
        return back()->with('flash_success','Assigned Services to drivers!');
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
            return back()->with('flash_error','Disabled for demo purposes! Please contact us at ...');
        }
        
        try {
            ServiceType::find($id)->delete();
            ProviderService::where('service_type_id', $id)->delete();
            return back()->with('flash_success', 'Service Type deleted successfully');
        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', 'Service Type Not Found');
        } catch (Exception $e) {
            return back()->with('flash_error', 'Service Type Not Found');
        }
    }
}