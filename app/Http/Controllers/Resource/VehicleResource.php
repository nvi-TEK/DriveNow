<?php

namespace App\Http\Controllers\Resource;

use App\Fleet;
use App\DriveNowVehicleSupplier;
use App\DriveNowVehiclePayment;
use App\DriveNowVehicleRepairHistory;
use App\DriveNowVehicle;
use App\Provider;
use App\Bank;
use Auth;
use App\Admin;
use App\ProviderProfile;
use App\ProviderDevice;
use Illuminate\Http\Request;
use App\Helpers\Helper;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Controllers\Controller;
use Exception;
use Setting;
use Log;
use App\FleetSubaccount;
use GuzzleHttp\Client;
use Carbon\Carbon;
use Storage;
use App\OfficialDriver;
use App\SupplierFleet;
use App\ExpenseCategory;
use App\OfficeExpense;

class VehicleResource extends Controller
{
    /**
     * Used to Get Provider
     */
    public function getFleets($id){  
    Log::info('coming');      
        $fleets = SupplierFleet::where('supplier_id', $id)->where('status', '!=',1)->get();                                                                         
        $html = '';
        if(!empty($fleets)){
            foreach($fleets as $fleet){
                if($fleet->id != '' && $fleet->name != ''){
                    $html .= '<option value="'.@$fleet->id.'">'.@$fleet->name.'</option>';
                }
            }
        }    
        return $html;        
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function suppliers()
    { 
        //where('status', '!=', 1)->
        $suppliers = DriveNowVehicleSupplier::orderBy('created_at' , 'desc')->where('status', '!=',1)->get();

            foreach ($suppliers as $key => $supplier) {
                $amount_due = DriveNowVehiclePayment::where('supplier_id',$supplier->id)->where('status',0)->sum('amount');
                if($amount_due){
                    $supplier->amount_due = $amount_due;
                }else{
                    $supplier->amount_due = 0;
                }
                $amount_paid = DriveNowVehiclePayment::where('supplier_id',$supplier->id)->where('status',1)->sum('amount');
                if($amount_paid){
                    $supplier->amount_paid = $amount_paid;
                }else{
                    $supplier->amount_paid = 0;
                }
            }
        return view('admin.vehicles.supplier.index', compact('suppliers'));
    }

        /**
     * Add the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function add_supplier()
    {        
        try {
            $banks = Bank::all();     
            return view('admin.vehicles.supplier.create', compact('banks'));
        } catch (\Throwable $th) {
            return back()->with('flash_error', 'Something went wrong');
        }        
    }

     /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store_supplier(Request $request)
    {
        
        try{     
                $supplier = $request->all();
                

                $supplier = new DriveNowVehicleSupplier;
                $supplier->name = $request->name;
                $supplier->contact_name = $request->contact_name;
                $supplier->contact = $request->contact;
                $supplier->email = $request->email;
                if($request->has('acc_no')) 
                    $supplier->acc_no = $request->acc_no;

                if($request->has('acc_name')) 
                    $supplier->acc_name = $request->acc_name;

                if ($request->has('bank_name'))
                    $supplier->bank_name = $request->bank_name;

                if ($request->has('bank_code'))
                    $supplier->bank_code = $request->bank_code;

                $supplier->address = $request->address;

                if ($request->hasFile('image')){
                    $name = $supplier->id."-supplier-".str_replace(' ','_',Carbon::now());
                    $baseurl = 'replace with asset url';                    
                    $contents = file_get_contents($request->image);
                    $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
                    $s3_url = $baseurl.'/'.$name;
                    $supplier->image = $s3_url;   
                }
                
                $supplier->save(); 

                return redirect()->route('admin.drivenow.suppliers.index')->with('flash_success', 'Supplier Created Successfully');
                
            
        } 

        catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something Went Wrong');
        }
    }

        /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Fleet  $fleet
     * @return \Illuminate\Http\Response
     */
    public function view_supplier($id)
    {
        try {
            $supplier = DriveNowVehicleSupplier::findOrFail($id);
            $banks = Bank::all();                     
            return view('admin.vehicles.supplier.edit',compact('supplier','banks'));
        } catch (ModelNotFoundException $e) {
            return $e;
        }
    }

        /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Fleet  $fleet
     * @return \Illuminate\Http\Response
     */
    public function update_supplier(Request $request)
    {

        try{     
                $supplier = DriveNowVehicleSupplier::where('id', $request->id)->first();
                $supplier->name = $request->name;
                $supplier->contact_name = $request->contact_name;
                $supplier->contact = $request->contact;
                $supplier->email = $request->email;
                if($request->has('acc_no')) 
                    $supplier->acc_no = $request->acc_no;

                if($request->has('acc_name')) 
                    $supplier->acc_name = $request->acc_name;

                if ($request->has('bank_name'))
                    $supplier->bank_name = $request->bank_name;

                if ($request->has('bank_code'))
                    $supplier->bank_code = $request->bank_code;

                $supplier->address = $request->address;

                if ($request->hasFile('image')){
                    $name = $supplier->id."-supplier-".str_replace(' ','_',Carbon::now());
                    $baseurl = 'replace with asset url';                    
                    $contents = file_get_contents($request->image);
                    $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
                    $s3_url = $baseurl.'/'.$name;
                    $supplier->image = $s3_url;   
                }
                
                $supplier->save(); 

                return redirect()->route('admin.drivenow.suppliers.index')->with('flash_success', 'Supplier Created Successfully');
                
            
        } 

        catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something Went Wrong');
        }
    }

        /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Fleet  $Fleet
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        
        try {
            $supplier = DriveNowVehicleSupplier::find($id);
            $supplier->status = 1;
            $supplier->save();
            return back()->with('flash_success', 'Supplier deleted successfully');
        } 
        catch (Exception $e) {
            return back()->with('flash_error', 'Supplier Not Found');
        }
    }


    //List Vehicles 

    public function vehicles(Request $request)
    {
        $official_drivers = OfficialDriver::where('status', '!=', 1)->whereNull('vehicle_id')->where('vehicle_number','!=', '')->get();
        foreach ($official_drivers as $official_driver) {
            $vehicle = new DriveNowVehicle;
            $vehicle->reg_no = $official_driver->vehicle_number;
            $vehicle->supplier_id = 1;
            $vehicle->make = $official_driver->vehicle_make;
            $vehicle->model = $official_driver->vehicle_model;
            $vehicle->year = $official_driver->vehicle_year;
            $vehicle->imei = $official_driver->imei_number;
            $vehicle->car_picture = $official_driver->vehicle_image;
            $vehicle->driver_id = $official_driver->driver_id;
            $vehicle->official_id = $official_driver->id;
            $vehicle->allocated_date = $official_driver->agreement_start_date;
            $vehicle->status = 5;
            $vehicle->save();
            $official_driver->vehicle_id = $vehicle->id;
            $official_driver->save();
        }
        if($request->has('supplier')){
            $vehicles = DriveNowVehicle::with('supplier','fleet')->where('status', '!=', 6)->where('supplier_id', $request->supplier)->orderBy('created_at' , 'desc')->get();
            $vehicles_allocated = DriveNowVehicle::with('supplier','fleet')->where('supplier_id', $request->supplier)->where('status',5)->orderBy('created_at' , 'desc')->count();

            $vehicles_incoming = DriveNowVehicle::with('supplier','fleet')->where('supplier_id', $request->supplier)->where('status',1)->orderBy('created_at' , 'desc')->count();
            $vehicles_depot = DriveNowVehicle::with('supplier','fleet')->where('supplier_id', $request->supplier)->where('status',2)->orderBy('updated_at' , 'desc')->count();
            $vehicles_tracked = DriveNowVehicle::with('supplier','fleet')->where('supplier_id', $request->supplier)->where('status',3)->orderBy('updated_at' , 'desc')->count();
            $vehicles_awaiting = DriveNowVehicle::with('supplier','fleet')->where('supplier_id', $request->supplier)->where('status',4)->orderBy('updated_at' , 'desc')->count();
            $vehicles_issues = DriveNowVehicle::with('supplier','fleet')->where('supplier_id', $request->supplier)->where('status',7)->orderBy('updated_at' , 'desc')->count();
            $vehicles_oos = DriveNowVehicle::with('supplier','fleet')->where('supplier_id', $request->supplier)->where('status',8)->orderBy('updated_at' , 'desc')->count();
            $date = Carbon::today()->addDays(20);
            $d = date('d');
            $vehicles_attention = DriveNowVehicle::with('supplier','fleet')->where('supplier_id', $request->supplier)->orwhere('insurance_expire', '<=', $date)->orWhere('road_worthy_expire', '<=', $date)
            // ->orWhere('maintenance_date', '<', $d)
            ->orderBy('created_at' , 'desc')->count();
        }else if($request->has('fleet')){
            $vehicles = DriveNowVehicle::with('supplier','fleet')->where('status', '!=', 6)->where('fleet_id', $request->fleet)->orderBy('created_at' , 'desc')->get();
            $vehicles_allocated = DriveNowVehicle::with('supplier','fleet')->where('fleet_id', $request->fleet)->where('status',5)->orderBy('created_at' , 'desc')->count();
            $vehicles_incoming = DriveNowVehicle::with('supplier','fleet')->where('fleet_id', $request->fleet)->where('status',1)->orderBy('created_at' , 'desc')->count();
            $vehicles_depot = DriveNowVehicle::with('supplier','fleet')->where('fleet_id', $request->fleet)->where('status',2)->orderBy('updated_at' , 'desc')->count();
            $vehicles_tracked = DriveNowVehicle::with('supplier','fleet')->where('fleet_id', $request->fleet)->where('status',3)->orderBy('updated_at' , 'desc')->count();
            $vehicles_awaiting = DriveNowVehicle::with('supplier','fleet')->where('fleet_id', $request->fleet)->where('status',4)->orderBy('updated_at' , 'desc')->count();
            $vehicles_issues = DriveNowVehicle::with('supplier','fleet')->where('fleet_id', $request->fleet)->where('status',7)->orderBy('updated_at' , 'desc')->count();
            $vehicles_oos = DriveNowVehicle::with('supplier','fleet')->where('fleet_id', $request->fleet)->where('status',8)->orderBy('updated_at' , 'desc')->count();
            $date = Carbon::today()->addDays(20);
            $d = date('d');
            $vehicles_attention = DriveNowVehicle::with('supplier','fleet')->where('fleet_id', $request->fleet)->orwhere('insurance_expire', '<=', $date)->orWhere('road_worthy_expire', '<=', $date)
            // ->orWhere('maintenance_date', '<', $d)
            ->orderBy('created_at' , 'desc')->count();
        }else{
            if($request->has('filter')){
                $vehicles = DriveNowVehicle::with('supplier','fleet')->where('status', '!=', 6)->where('status','=',$request->filter)->orderBy('created_at' , 'desc')->get();
            }else{

                $vehicles = DriveNowVehicle::with('supplier','fleet')->where('status', '!=', 6)->orderBy('created_at' , 'desc')->get();
                
            }
            if($request->has('attention')){
                $date = Carbon::today()->addDays(20);
                $d = date('d');
                $vehicles = DriveNowVehicle::with('supplier','fleet')->where('status', '!=', 6)->where('insurance_expire', '<=', $date)->orderBy('created_at' , 'desc')->get();
            }
            
            $vehicles_allocated = DriveNowVehicle::with('supplier','fleet')->where('status',5)->orderBy('created_at' , 'desc')->count();
            $vehicles_incoming = DriveNowVehicle::with('supplier','fleet')->where('status',1)->orderBy('created_at' , 'desc')->count();
            $vehicles_depot = DriveNowVehicle::with('supplier','fleet')->where('status',2)->orderBy('updated_at' , 'desc')->count();
            $vehicles_tracked = DriveNowVehicle::with('supplier','fleet')->where('status',3)->orderBy('updated_at' , 'desc')->count();
            $vehicles_awaiting = DriveNowVehicle::with('supplier','fleet')->where('status',4)->orderBy('updated_at' , 'desc')->count();
            $vehicles_issues = DriveNowVehicle::with('supplier','fleet')->where('status',7)->orderBy('updated_at' , 'desc')->count();
            $vehicles_oos = DriveNowVehicle::with('supplier','fleet')->where('status',8)->orderBy('updated_at' , 'desc')->count();
            $date = Carbon::today()->addDays(20);
            $d = date('d');
            $vehicles_attention = DriveNowVehicle::with('supplier','fleet')->where('status', '!=', 6)->where('insurance_expire', '<=', $date)
            // ->orWhere('maintenance_date', '<', $d)
            ->orderBy('created_at' , 'desc')->count();
        }

        foreach ($vehicles as $vehicle) {

            // $vehicle->reg_no = str_replace("22-", "22", $vehicle->reg_no);
            // $vehicle->save();
            if($vehicle->fleet){
                if($vehicle->fleet->monthly_due != ''){
                $m = date('m'); $y = date('Y');
                $payment = DriveNowVehiclePayment::whereMonth('due_on',$m)->whereYear('due_on', $y)->where('car_id',$vehicle->id)->first();
                    if(count($payment)==0 ){
                        $payment = new DriveNowVehiclePayment;
                        $payment->supplier_id = $vehicle->supplier_id;
                        $payment->fleet_id = $vehicle->fleet_id;
                        $payment->car_id = $vehicle->id;
                        $payment->amount = $vehicle->fleet->monthly_due;
                        $payment->due_on = date('Y')."-".date('m')."-".$vehicle->fleet->due_date;
                        $payment->status = 0;
                        $payment->save();
                    }
                }
            }       
        }

        return view('admin.vehicles.profile.index', compact('vehicles','vehicles_allocated','vehicles_incoming','vehicles_depot','vehicles_tracked','vehicles_awaiting','vehicles_attention','page','vehicles_issues', 
'vehicles_oos'));
    }


            /**
     * Add the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function add_vehicle()
    {        
        try {
            $suppliers = DriveNowVehicleSupplier::where('status', '!=', 1)->get();     
            return view('admin.vehicles.profile.create', compact('suppliers'));
        } catch (\Throwable $th) {
            return back()->with('flash_error', 'Something went wrong');
        }        
    }

     /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store_vehicle(Request $request)
    {

        try{
                $vehicle = DriveNowVehicle::where('reg_no', $request->reg_no)
                // ->orWhere('imei',$request->imei)
                ->get();
                if(count($vehicle) >0){
                    return back()->with('flash_error', 'Vehicle with same IMEI / Registration number already exist!');
                }
                $vehicle = new DriveNowVehicle;
                $vehicle->reg_no = $request->reg_no;
                $vehicle->supplier_id = $request->supplier_id;
                $vehicle->fleet_id = $request->fleet_id;
                $vehicle->make = $request->make;
                $vehicle->model = $request->model;
                $vehicle->mileage = $request->mileage;
                $vehicle->year = $request->year;
                $vehicle->road_worthy_expire = $request->road_worthy_expire;
                $vehicle->insurance_type = $request->insurance_type;
                $vehicle->insurance_expire = $request->insurance_expire;
                $vehicle->imei = $request->imei;
                $vehicle->maintenance_date = $request->maintenance_date;
                $vehicle->status = $request->status;
                $vehicle->vehicle_color = $request->vehicle_color;
                $vehicle->chasis_no = $request->chasis_no;
                $vehicle->transmission_type = $request->transmission_type;

                if ($request->hasFile('car_picture')){
                    $name = "car-".$vehicle->id."-".str_replace(' ','_',Carbon::now());
                        $baseurl = 'replace with asset url';                    
                        $contents = file_get_contents($request->car_picture);
                        $path = Storage::disk('s3')->put('providers/'.$name, $contents);
                        $s3_url = $baseurl.'/'.$name;
                    $vehicle->car_picture = $s3_url;
                }

                 if ($request->hasFile('insurance_file')){
                    $name = "car-insure".$vehicle->id."-".str_replace(' ','_',Carbon::now());
                        $baseurl = 'replace with asset url';                    
                        $contents = file_get_contents($request->insurance_file);
                        $path = Storage::disk('s3')->put('providers/'.$name, $contents);
                        $s3_url = $baseurl.'/'.$name;
                    $vehicle->insurance_file = $s3_url;
                }

                 if ($request->hasFile('road_worthy_file')){
                    $name = "car-road".$vehicle->id."-".str_replace(' ','_',Carbon::now());
                        $baseurl = 'replace with asset url';                    
                        $contents = file_get_contents($request->road_worthy_file);
                        $path = Storage::disk('s3')->put('providers/'.$name, $contents);
                        $s3_url = $baseurl.'/'.$name;
                    $vehicle->road_worthy_file = $s3_url;
                }


                if ($request->hasFile('image1')){
                    $name = $vehicle->id."-car-".str_replace(' ','_',Carbon::now());
                    $baseurl = 'replace with asset url';                    
                    $contents = file_get_contents($request->image1);
                    $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
                    $s3_url = $baseurl.'/'.$name;
                    $vehicle->image1 = $s3_url;   
                }

                if ($request->hasFile('image2')){
                    $name = $vehicle->id."-car-".str_replace(' ','_',Carbon::now());
                    $baseurl = 'replace with asset url';                    
                    $contents = file_get_contents($request->image2);
                    $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
                    $s3_url = $baseurl.'/'.$name;
                    $vehicle->image2 = $s3_url;   
                }

                if ($request->hasFile('image3')){
                    $name = $vehicle->id."-car-".str_replace(' ','_',Carbon::now());
                    $baseurl = 'replace with asset url';                    
                    $contents = file_get_contents($request->image3);
                    $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
                    $s3_url = $baseurl.'/'.$name;
                    $vehicle->image3 = $s3_url;   
                }

                if ($request->hasFile('image4')){
                    $name = $vehicle->id."-car-".str_replace(' ','_',Carbon::now());
                    $baseurl = 'replace with asset url';                    
                    $contents = file_get_contents($request->image4);
                    $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
                    $s3_url = $baseurl.'/'.$name;
                    $vehicle->image4 = $s3_url;   
                }

                if ($request->hasFile('image5')){
                    $name = $vehicle->id."-car-".str_replace(' ','_',Carbon::now());
                    $baseurl = 'replace with asset url';                    
                    $contents = file_get_contents($request->image5);
                    $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
                    $s3_url = $baseurl.'/'.$name;
                    $vehicle->image5 = $s3_url;   
                }
                
                $vehicle->save(); 

                return redirect()->route('admin.drivenow.vehicles.index')->with('flash_success', 'Vehicle Created Successfully');
                
            
        } 

        catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something Went Wrong');
        }
    }

        /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Fleet  $fleet
     * @return \Illuminate\Http\Response
     */
    public function view_vehicle($id)
    {
        try {
            $vehicle = DriveNowVehicle::findOrFail($id);
            $suppliers = DriveNowVehicleSupplier::all();                     
            return view('admin.vehicles.profile.edit',compact('vehicle','suppliers'));
        } catch (ModelNotFoundException $e) {
            return $e;
        }
    }

        /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Fleet  $fleet
     * @return \Illuminate\Http\Response
     */
    public function update_vehicle(Request $request)
    {
// dd($request->all());
        try{     
                $vehicle = DriveNowVehicle::where('id',$request->vehicle_id)->first();
                if($request->has('status_up')){
                    $vehicle->status = $request->status;
                    $vehicle->save();
                    return redirect()->route('admin.drivenow.vehicles.index')->with('flash_success', 'Vehicle status Updated Successfully');

                }
                if($request->has('sim_up')){
                    $vehicle->sim = $request->sim;
                    $vehicle->save();
                    return redirect()->route('admin.drivenow.vehicles.index')->with('flash_success', 'Vehicle SIM Number Updated Successfully');

                }

                $vehicle->reg_no = $request->reg_no;
                $vehicle->supplier_id = $request->supplier_id;
                $vehicle->fleet_id = $request->fleet_id;
                $vehicle->make = $request->make;
                $vehicle->model = $request->model;
                $vehicle->mileage = $request->mileage;
                $vehicle->year = $request->year;
                if($request->road_worthy_expire !=''){
                    $vehicle->road_worthy_expire = $request->road_worthy_expire;
                }
                if($request->insurance_expire !=''){
                    $vehicle->insurance_expire = $request->insurance_expire;
                }
                
                $vehicle->insurance_type = $request->insurance_type;
                
                $vehicle->imei = $request->imei;
                $vehicle->maintenance_date = $request->maintenance_date;
                $vehicle->status = $request->status;
                $vehicle->vehicle_color = $request->vehicle_color;
                $vehicle->chasis_no = $request->chasis_no;
                $vehicle->transmission_type = $request->transmission_type;
                
                $vehicle->save(); 
                if ($request->hasFile('car_picture')){
                    $name = "car-".$vehicle->id."-".str_replace(' ','_',Carbon::now());
                        $baseurl = 'replace with asset url';                    
                        $contents = file_get_contents($request->car_picture);
                        $path = Storage::disk('s3')->put('providers/'.$name, $contents);
                        $s3_url = $baseurl.'/'.$name;
                    $vehicle->car_picture = $s3_url;
                }

                 if ($request->hasFile('insurance_file')){
                    $name = "car-insure".$vehicle->id."-".str_replace(' ','_',Carbon::now());
                        $baseurl = 'replace with asset url';                    
                        $contents = file_get_contents($request->insurance_file);
                        $path = Storage::disk('s3')->put('providers/'.$name, $contents);
                        $s3_url = $baseurl.'/'.$name;
                    $vehicle->insurance_file = $s3_url;
                }

                 if ($request->hasFile('road_worthy_file')){
                    $name = "car-road".$vehicle->id."-".str_replace(' ','_',Carbon::now());
                        $baseurl = 'replace with asset url';                    
                        $contents = file_get_contents($request->road_worthy_file);
                        $path = Storage::disk('s3')->put('providers/'.$name, $contents);
                        $s3_url = $baseurl.'/'.$name;
                    $vehicle->road_worthy_file = $s3_url;
                }


                if ($request->hasFile('image1')){
                    $name = $vehicle->id."-car-".str_replace(' ','_',Carbon::now());
                    $baseurl = 'replace with asset url';                    
                    $contents = file_get_contents($request->image1);
                    $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
                    $s3_url = $baseurl.'/'.$name;
                    $vehicle->image1 = $s3_url;   
                }

                if ($request->hasFile('image2')){
                    $name = $vehicle->id."-car-".str_replace(' ','_',Carbon::now());
                    $baseurl = 'replace with asset url';                    
                    $contents = file_get_contents($request->image2);
                    $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
                    $s3_url = $baseurl.'/'.$name;
                    $vehicle->image2 = $s3_url;   
                }

                if ($request->hasFile('image3')){
                    $name = $vehicle->id."-car-".str_replace(' ','_',Carbon::now());
                    $baseurl = 'replace with asset url';                    
                    $contents = file_get_contents($request->image3);
                    $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
                    $s3_url = $baseurl.'/'.$name;
                    $vehicle->image3 = $s3_url;   
                }

                if ($request->hasFile('image4')){
                    $name = $vehicle->id."-car-".str_replace(' ','_',Carbon::now());
                    $baseurl = 'replace with asset url';                    
                    $contents = file_get_contents($request->image4);
                    $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
                    $s3_url = $baseurl.'/'.$name;
                    $vehicle->image4 = $s3_url;   
                }

                if ($request->hasFile('image5')){
                    $name = $vehicle->id."-car-".str_replace(' ','_',Carbon::now());
                    $baseurl = 'replace with asset url';                    
                    $contents = file_get_contents($request->image5);
                    $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
                    $s3_url = $baseurl.'/'.$name;
                    $vehicle->image5 = $s3_url;   
                }
                
                $vehicle->save(); 

                return redirect()->route('admin.drivenow.vehicles.index')->with('flash_success', 'Vehicle Updated Successfully');
                
            
        } 

        catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something Went Wrong');
        }
    }

        /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Fleet  $Fleet
     * @return \Illuminate\Http\Response
     */
    public function destroy_vehicle($id)
    {
        
        try {
            $supplier = DriveNowVehicle::find($id);
            $supplier->status = 6;
            $supplier->save();
            return back()->with('flash_success', 'Vehicle deleted successfully');
        } 
        catch (Exception $e) {
            return back()->with('flash_error', 'Vehicle Not Found');
        }
    }


    public function vehicle_payments(Request $request){
        try{
            if($request->has('supplier')){
                $payments = DriveNowVehiclePayment::where('supplier_id', $request->supplier)->get();
                $page = $supplier->contact_name."'s Vehicle Due Payments";
            }else if($request->has('fleet')){
                $payments = DriveNowVehiclePayment::where('fleet_id', $request->fleet)->get();
                $page = $supplier->contact_name."'s Vehicle Due Payments";
            }else{
                $payments = DriveNowVehiclePayment::get();
                $page = "Vehicle Due Payments";
            }
            
            return view('admin.vehicles.payments.transactions', compact('payments','page'));

        }catch (Exception $e){
            return back()->with('flash_error', 'Something Went Wrong');
        }
    }

    public function vehicle_pay_approve($id){
        try{
            $payment = DriveNowVehiclePayment::where('id',$id)->first();
            $payment->status = 1;
            $payment->approved_by = Auth::guard('admin')->user()->id;
            $payment->save();
            return redirect()->route('admin.drivenow.vehicles_pay.index')->with('flash_success', 'Payment Confirmed');

        }catch (Exception $e){
            return back()->with('flash_error', 'Something Went Wrong');
        }
    }


    public function vehicle_pay_reverse($id){
        try{
            $payment = DriveNowVehiclePayment::where('id',$id)->first();
            $payment->status = 0;
            $payment->approved_by = Auth::guard('admin')->user()->id;
            $payment->save();
            return redirect()->route('admin.drivenow.vehicles_pay.index')->with('flash_success', 'Payment Reversed');

        }catch (Exception $e){
            return back()->with('flash_error', 'Something Went Wrong');
        }
    }

    public function vehicle_pay_all($id){
        try{
            $payment = DriveNowVehiclePayment::where('fleet_id',$id)->where('status','!=',1)->update(['status' => 1, 'approved_by' => Auth::guard('admin')->user()->id]);
            // $payment->status = 1;
            // $payment->approved_by = Auth::guard('admin')->user()->id;
            // $payment->save();
            return redirect()->route('admin.drivenow.suppliers.index')->with('flash_success', 'Payment Confirmed');

        }catch (Exception $e){
            Log::info($e);
            return back()->with('flash_error', 'Something Went Wrong');
        }
    }


   

    /**
     * Display the specified resource.
     *
     * @param  \App\Fleet  $fleet
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // 
    }


    public function statement($id){

        try{

            $requests = UserRequests::where('provider_id',$id)
                        ->where('status','COMPLETED')
                        ->with('payment')
                        ->get();

            $rides = UserRequests::where('provider_id',$id)->with('payment')->orderBy('id','desc')->paginate(10);
            $cancel_rides = UserRequests::where('status','CANCELLED')->where('provider_id',$id)->count();
            $Provider = Provider::find($id);
            $revenue = UserRequestPayment::whereHas('request', function($query) use($id) {
                                    $query->where('provider_id', $id );
                                })->select(\DB::raw(
                                   'SUM(ROUND(fixed) + ROUND(distance)) as overall, SUM(ROUND(commision)) as commission' 
                               ))->get();


            $Joined = $Provider->created_at ? '- Joined '.$Provider->created_at->diffForHumans() : '';

            return view('admin.providers.statement', compact('rides','cancel_rides','revenue'))
                        ->with('page',$Provider->first_name."'s Overall Statement ". $Joined);

        } catch (Exception $e) {
            return back()->with('flash_error','Something Went Wrong!');
        }
    }



    /**
     * Used to update fleet auto payout status
     */
    public function autopayout(Request $request){
         try {
             $fleet = Fleet::find($request->id);
            if($request->status == '1') {
                $fleet->auto_payout = 0;
            }
            else{
                $fleet->auto_payout = 1;
            }
             $fleet->save();
             return 1; 
         } catch (\Throwable $th) {
             return 0;
         }
    }




    public function supplier_fleet()
    { 
        $suppliers = SupplierFleet::orderBy('created_at' , 'desc')->where('status', '!=',1)->get();
        foreach ($suppliers as $key => $supplier) {
            $supplier->vehicles = DriveNowVehicle::where('fleet_id',$supplier->id)->where('status','!=',6)->count();
            $amount_due = DriveNowVehiclePayment::where('fleet_id',$supplier->id)->where('status',0)->sum('amount');
            if($amount_due){
                $supplier->amount_due = $amount_due;
            }else{
                $supplier->amount_due = 0;
            }
            $amount_paid = DriveNowVehiclePayment::where('fleet_id',$supplier->id)->where('status',1)->sum('amount');
            if($amount_paid){
                $supplier->amount_paid = $amount_paid;
            }else{
                $supplier->amount_paid = 0;
            }
        }

        return view('admin.vehicles.supplier.fleet.index', compact('suppliers'));
    }

        /**
     * Add the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function add_supplier_fleet()
    {        
        try {
            $suppliers = DriveNowVehicleSupplier::where('status', '!=', 1)->get();
            $banks = Bank::all();     
            return view('admin.vehicles.supplier.fleet.create', compact('banks','suppliers'));
        } catch (\Throwable $th) {
            return back()->with('flash_error', 'Something went wrong');
        }        
    }

     /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store_supplier_fleet(Request $request)
    {
        try{     
                $supplier = new SupplierFleet;
                $supplier->supplier_id = $request->supplier_id;
                $supplier->name = $request->name;
                $supplier->contact = $request->contact;
                $supplier->due_length = $request->due_length;
                $supplier->initial_amount = $request->initial_amount;
                $supplier->monthly_due = $request->monthly_due;
                $supplier->due_date = $request->due_date;
                $supplier->vehicle_cost = $request->vehicle_cost;
                if($request->has('acc_no')) 
                    $supplier->acc_no = $request->acc_no;

                if($request->has('acc_name')) 
                    $supplier->acc_name = $request->acc_name;

                if ($request->has('bank_name'))
                    $supplier->bank_name = $request->bank_name;

                if ($request->has('bank_code'))
                    $supplier->bank_code = $request->bank_code;

            $supplier->weekly = $request->weekly;
            $supplier->company_share = $request->company_share;
            $supplier->management_fee = $request->management_fee;
            $supplier->maintenance_fee = $request->maintenance_fee;
            $supplier->insurance_fee = $request->insurance_fee;
            $supplier->road_worthy_fee = $request->road_worthy_fee;

                
                $supplier->save(); 

                return redirect()->route('admin.drivenow.suppliers.fleet.index')->with('flash_success', 'Supplier Fleet Created Successfully');
        }catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something Went Wrong');
        }
    }

        /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Fleet  $fleet
     * @return \Illuminate\Http\Response
     */
    public function view_supplier_fleet($id)
    {
        try {
            $suppliers = DriveNowVehicleSupplier::where('status', '!=', 1)->get();
            $fleet = SupplierFleet::findOrFail($id);
            $banks = Bank::all();                     
            return view('admin.vehicles.supplier.fleet.edit',compact('suppliers','fleet','banks'));
        } catch (ModelNotFoundException $e) {
            return $e;
        }
    }

        /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Fleet  $fleet
     * @return \Illuminate\Http\Response
     */
    public function update_supplier_fleet(Request $request)
    {

        try{     
            $supplier = SupplierFleet::where('id', $request->id)->first();
            $supplier->name = $request->name;
            $supplier->contact = $request->contact;
            $supplier->due_length = $request->due_length;
            $supplier->initial_amount = $request->initial_amount;
            $supplier->monthly_due = $request->monthly_due;
            $supplier->due_date = $request->due_date;
            $supplier->vehicle_cost = $request->vehicle_cost;
            if($request->has('acc_no')) 
                $supplier->acc_no = $request->acc_no;

            if($request->has('acc_name')) 
                $supplier->acc_name = $request->acc_name;

            if ($request->has('bank_name'))
                $supplier->bank_name = $request->bank_name;

            if ($request->has('bank_code'))
                $supplier->bank_code = $request->bank_code;

            $supplier->weekly = $request->weekly;
            $supplier->company_share = $request->company_share;
            $supplier->management_fee = $request->management_fee;
            $supplier->maintenance_fee = $request->maintenance_fee;
            $supplier->insurance_fee = $request->insurance_fee;
            $supplier->road_worthy_fee = $request->road_worthy_fee;
            
            $supplier->save(); 

            return redirect()->route('admin.drivenow.suppliers.fleet.index')->with('flash_success', 'Supplier Fleet Created Successfully');
        }catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something Went Wrong');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Fleet  $Fleet
     * @return \Illuminate\Http\Response
     */
    public function destroy_fleet($id)
    {
        try {
            $supplier = SupplierFleet::find($id);
            $supplier->status = 1;
            $supplier->save();
            return back()->with('flash_success', 'Supplier Fleet deleted successfully');
        } 
        catch (Exception $e) {
            return back()->with('flash_error', 'Supplier Not Found');
        }
    }

    public function add_expenses(Request $request)
    {
        
        try {
            $expense = new OfficeExpense;
            $str_result = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $code = substr(str_shuffle($str_result),0, 4);
            $exp_id = "FE".$code.date('H');
            $expense->exp_id = $exp_id;
            $expense->category = $request->type;
            $expense->car_id = $request->car_id;
            $expense->paid_to = $request->paid;
            $expense->amount = $request->amount;
            $expense->date = $request->date;
            $expense->description = $request->reason;
            $expense->added_by = Auth::guard('admin')->user()->id;
            $expense->status = 0;
            
            if($request->has('acc_no')) 
                $expense->acc_no = $request->acc_no;

            if ($request->has('bank_name'))
                $expense->bank_name = $request->bank_name;

            if ($request->has('bank_name_id'))
                $expense->bank_name_id = $request->bank_name_id;

            if ($request->has('bank_code'))
                $expense->bank_code = $request->bank_code;
            $expense->save();

            $repair = DriveNowVehicleRepairHistory::where('id',$request->repair_id)->first();
            $repair->status = 1;
            $repair->save();

            return redirect()->route('admin.expenses.index')->with('flash_success', 'Expense Request created');
        } 
        catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something Went Wrong');
        }
    }

    public function repair_history(Request $request)
    {
        $repairs = DriveNowVehicleRepairHistory::orderBy('created_at' , 'desc')->get();
        $categories = ExpenseCategory::where('status','!=', 1)->where('type',1)->get();
        $vehicles = DriveNowVehicle::where('status','!=',6)->get();
        $users = Admin::where('role', '!=', 'admin')->get();
        $banks = Bank::all();
        // dd($repairs[352]);
        return view('admin.vehicles.profile.repair_history',compact('repairs','categories','vehicles','users','banks'));

    }
    public function add_repair(Request $request)
    {
        $repair = new DriveNowVehicleRepairHistory;
        $repair->car_id = $request->car_id;
        $repair->amount = $request->amount;
        // $repair->reason = $request->reason;
        $repair->type = $request->type;
        $repair->date = $request->date;
        $repair->description = $request->description;
        $repair->added_by = Auth::guard('admin')->user()->id;
        $repair->paid = $request->paid;
        $repair->status = 0;
        $repair->save();
        if ($request->hasFile('image1')){
            $name = $repair->id."-repair-".str_replace(' ','_',Carbon::now());
            $baseurl = 'replace with asset url';                    
            $contents = file_get_contents($request->image1);
            $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
            $s3_url = $baseurl.'/'.$name;
            $repair->image1 = $s3_url;   
        }

        if ($request->hasFile('image2')){
            $name = $repair->id."-car-".str_replace(' ','_',Carbon::now());
            $baseurl = 'replace with asset url';                    
            $contents = file_get_contents($request->image2);
            $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
            $s3_url = $baseurl.'/'.$name;
            $repair->image2 = $s3_url;   
        }

        if ($request->hasFile('image3')){
            $name = $repair->id."-car-".str_replace(' ','_',Carbon::now());
            $baseurl = 'replace with asset url';                    
            $contents = file_get_contents($request->image3);
            $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
            $s3_url = $baseurl.'/'.$name;
            $repair->image3 = $s3_url;   
        }
        $repair->save();
        // if($request->paid != 0){
        //     $expense = new OfficeExpense;
        //     $expense->category = $request->type;
        //     $expense->car_id = $request->car_id;
        //     $expense->paid_to = $request->paid;
        //     $expense->amount = $request->amount;
        //     $expense->date = $request->date;
        //     $expense->description = $request->description;
        //     $expense->added_by = Auth::guard('admin')->user()->id;
        //     $expense->status = 0;
        //     $expense->save();
        // }

        return redirect()->route('admin.vehicles.repair')->with('flash_success', 'Repair history created successfully');
        
    }

    public function approve_repair($id)
    {
        
        try {
            $exp_category = DriveNowVehicleRepairHistory::where('id', $id)->first();

            $exp_category->approved_by = Auth::guard('admin')->user()->id;
            $exp_category->status = 1;   
            $exp_category->save();

           return redirect()->route('admin.vehicles.repair')->with('flash_success', 'Repair report Approved');

        } 
        catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something Went Wrong');
        }
    }

    public function decline_repair($id)
    {
        
        try {
            $exp_category = DriveNowVehicleRepairHistory::where('id', $id)->first();
            $exp_category->approved_by = Auth::guard('admin')->user()->id;
            $exp_category->status = 2;   
            $exp_category->save();

           return redirect()->route('admin.vehicles.repair')->with('flash_success', 'Repair report Declined');

        } 
        catch (Exception $e) {
            return back()->with('flash_error', 'Something Went Wrong');
        }
    }

    // Tracker

    public function vehicle_tracker(Request $request){
        try{
            $imeis = $n_imeis = '';
            // $official_drivers = OfficialDriver::with('provider')->where('status','!=', 1)->get();
            // $imeis = '';
            // $over = count($official_drivers)-1;
            // //Fetching IMEI Number to feed Tro Traker api
            // for ($i=0; $i < count($official_drivers); $i++) {  
            
            //     if($official_drivers[$i]->imei_number !=''){
            //         $imeis .= str_replace(' ', '',$official_drivers[$i]->imei_number) .",";
            //         $official_drivers[$i]->imei_number = str_replace(' ', '',$official_drivers[$i]->imei_number);
            //         $official_drivers[$i]->save();
            //     }
            // }
            // $imeis = substr_replace($imeis,"",-1);

            $tro_access_token = Setting::get('tro_access_token','');
            if($tro_access_token == ''){
                $time = Carbon::now()->timestamp;
                $account = "replace with account name";
                $password = "replace with acoount password";
                $signature = md5(md5($password).$time);

                $token_url = "http://api.protrack365.com/api/authorization?time=".$time."&account=".$account."&signature=".$signature;

                $token_json = curl($token_url);

                $token_details = json_decode($token_json, TRUE);

                $tro_access_token = $token_details['record']['access_token'];
                Setting::set('tro_access_token', $tro_access_token);
                Setting::save();
                Log::info("Tro Access Token Called". $tro_access_token);
            }
            if($tro_access_token !=''){
                $info_url = "http://api.protrack365.com/api/device/list?access_token=".$tro_access_token;

                $info_json = curl($info_url);

                $info_details = json_decode($info_json, TRUE);

                $official_drivers = array();
                if($info_details){
                    if($info_details['code']== '10012'){
                        $time = Carbon::now()->timestamp;
                        $account = "replace with account name";
                        $password = "replace with acoount password";
                        $signature = md5(md5($password).$time);

                        $token_url = "http://api.protrack365.com/api/authorization?time=".$time."&account=".$account."&signature=".$signature;

                        $token_json = curl($token_url);

                        $token_details = json_decode($token_json, TRUE);

                        $tro_access_token = $token_details['record']['access_token'];
                        Setting::set('tro_access_token', $tro_access_token);
                        Setting::save();
                        Log::info("Tro Access Token Called");
                        $info_url = "http://api.protrack365.com/api/device/list?access_token=".$tro_access_token;

                        $info_json = curl($info_url);

                        $info_details = json_decode($info_json, TRUE);
                        Log::info(json_encode($info_details));
                    }
                    $k = $l =0; $veh = array();

                    for ($i=0; $i < count($info_details['record']); $i++) { 
                        $drivenow_vehicle = DriveNowVehicle::where('reg_no', $info_details['record'][$i]['platenumber'])->with('driver')->first();
                        
                        if($drivenow_vehicle){
                            $k = $k+1;
                            $drivenow_vehicle->imei = $info_details['record'][$i]['imei'];
                            $drivenow_vehicle->sim = $info_details['record'][$i]['simcard'];
                            $drivenow_vehicle->save();
                            if($drivenow_vehicle->imei != ''){
                                $imeis .= $drivenow_vehicle->imei.","; 
                            }
                            
                        }else{
                            $l = $l + 1;
                            // $imeis .= $info_details['record'][$i]['imei'].",";
                            $n_imeis .= $info_details['record'][$i]['imei'].",";
                            
                        }
                    }
                    $vehicle_not_tracked = $l;
                    $imeis = substr_replace($imeis,"",-1);
                    if($request->has('n')){
                        $imeis = substr_replace($request->n,"",-1);
                    }
                    $status_url = "http://api.protrack365.com/api/track?access_token=".$tro_access_token."&imeis=".$imeis;

                    $status_json = curl($status_url);

                    $status_details = json_decode($status_json, TRUE);
                                        $online_vehicles = $offline_vehicles = $blocked = $active = $total_vehicles = 0 ;
                    $drivenow_vehicles = array();
                    $j = 0;
                    for ($i=0; $i < count($status_details['record']); $i++) { 
                        Log::info("Before: ". json_encode($status_details['record'][$i]));
                        $drivenow_vehicle = DriveNowVehicle::where('imei', $status_details['record'][$i]['imei'])->with('driver')->first();

                            if($drivenow_vehicle){

                                $drivenow_vehicles[$j] = $drivenow_vehicle;
                                $drivenow_vehicles[$j]->latitude = $status_details['record'][$i]['latitude'];
                                $drivenow_vehicles[$j]->longitude = $status_details['record'][$i]['longitude'];
                                $drivenow_vehicles[$j]->car_speed = $status_details['record'][$i]['speed'];
                                $drivenow_vehicles[$j]->accstatus = $status_details['record'][$i]['oilpowerstatus'];
                                $drivenow_vehicles[$j]->datastatus = $status_details['record'][$i]['datastatus'];
                                $drivenow_vehicles[$j]->hearttime = Carbon::createFromTimestamp($status_details['record'][$i]['hearttime'], new \DateTimeZone('Europe/London'))->diffForHumans();
                                $j = $j+1;
                            }
                            
                        
                        $official_drivers = OfficialDriver::where('imei_number',$status_details['record'][$i]['imei'])->first();
                        
                        if($official_drivers){
                            $official_driver = OfficialDriver::findOrFail($official_drivers->id);
                            if($status_details['record'][$i]['oilpowerstatus'] == 0){
                                $official_driver->engine_status = 1;
                                $official_driver->save();
                            }else{
                                $official_driver->engine_status = 0;
                                $official_driver->save();
                            }
                        }
                        if($status_details['record'][$i]['datastatus'] == 2){
                            $online_vehicles += 1;
                        }
                        if($status_details['record'][$i]['datastatus'] == 4){
                            $offline_vehicles += 1;

                        }
                        $total_vehicles = count($status_details['record']);

                        if($status_details['record'][$i]['oilpowerstatus'] == 1){
                            $active += 1;
                        }
                        if($status_details['record'][$i]['oilpowerstatus'] == 0){
                            $blocked += 1;
                        }

                        
                    
                    }
                }
            }
            
            return view('admin.vehicles.profile.tracker', compact('drivenow_vehicles', 'online_vehicles','offline_vehicles', 'blocked', 'active','total_vehicles','vehicle_not_tracked','n_imeis'));
        } catch (ModelNotFoundException $e) {
            Log::info($e);
            return back()->with('flash_error', 'Server Error!');
        }
    }

    public function tro_tracker(){
        try{
            $imeis = '';

            $tro_access_token = Setting::get('tro_access_token','');
            if($tro_access_token == ''){
                $time = Carbon::now()->timestamp;
                $account = "replace with account name";
                $password = "replace with acoount password";
                $signature = md5(md5($password).$time);

                $token_url = "http://api.protrack365.com/api/authorization?time=".$time."&account=".$account."&signature=".$signature;

                $token_json = curl($token_url);

                $token_details = json_decode($token_json, TRUE);

                $tro_access_token = $token_details['record']['access_token'];
                Setting::set('tro_access_token', $tro_access_token);
                Setting::save();
                Log::info("Tro Access Token Called");
            }

            if($tro_access_token !=''){
                $info_url = "http://api.protrack365.com/api/device/list?access_token=".$tro_access_token;

                $info_json = curl($info_url);

                $info_details = json_decode($info_json, TRUE);

                $official_drivers = array();
                if($info_details){
                    if($info_details['code']== '10012'){
                        $time = Carbon::now()->timestamp;
                        $account = "replace with account name";
                        $password = "replace with acoount password";
                        $signature = md5(md5($password).$time);

                        $token_url = "http://api.protrack365.com/api/authorization?time=".$time."&account=".$account."&signature=".$signature;

                        $token_json = curl($token_url);

                        $token_details = json_decode($token_json, TRUE);

                        $tro_access_token = $token_details['record']['access_token'];
                        Setting::set('tro_access_token', $tro_access_token);
                        Setting::save();
                        Log::info("Tro Access Token Called");
                        $info_url = "http://api.protrack365.com/api/device/list?access_token=".$tro_access_token;

                        $info_json = curl($info_url);

                        $info_details = json_decode($info_json, TRUE);
                    }
                    
                    for ($i=0; $i < count($info_details['record']); $i++) { 
                        $drivenow_vehicle = DriveNowVehicle::where('imei', $info_details['record'][$i]['imei'])->with('driver')->first();
                        if($drivenow_vehicle){
                            $imeis .= $drivenow_vehicle->imei.","; 
                        }
                    }
                    $imeis = substr_replace($imeis,"",-1);

                    $status_url = "http://api.protrack365.com/api/track?access_token=".$tro_access_token."&imeis=".$imeis;

                    $status_json = curl($status_url);

                    $status_details = json_decode($status_json, TRUE);

                    $online_vehicles = $offline_vehicles = $blocked = $active = 0;

                    for ($i=0; $i < count($status_details['record']); $i++) { 

                        $drivenow_vehicles[$i] = DriveNowVehicle::where('imei', $status_details['record'][$i]['imei'])->with('driver')->first();
                        
                        $drivenow_vehicles[$i]->latitude = $status_details['record'][$i]['latitude'];
                        $drivenow_vehicles[$i]->longitude = $status_details['record'][$i]['longitude'];
                        $drivenow_vehicles[$i]->car_speed = $status_details['record'][$i]['speed'];
                        $drivenow_vehicles[$i]->accstatus = $status_details['record'][$i]['oilpowerstatus'];
                        $drivenow_vehicles[$i]->datastatus = $status_details['record'][$i]['datastatus'];
                        if($status_details['record'][$i]['datastatus'] == 2){
                            $online_vehicles += 1;
                        }
                        if($status_details['record'][$i]['datastatus'] == 4){
                            $offline_vehicles += 1;

                        }

                        if($status_details['record'][$i]['oilpowerstatus'] == 1){
                            $active += 1;
                        }
                        if($status_details['record'][$i]['oilpowerstatus'] == 0){
                            $blocked += 1;
                        }

                        $drivenow_vehicles[$i]->hearttime = Carbon::createFromTimestamp($status_details['record'][$i]['hearttime'], new \DateTimeZone('Europe/London'))->diffForHumans();
                    
                    }
                }
            }

            return view('admin.vehicles.profile.tracker', compact('drivenow_vehicles', 'online_vehicles','offline_vehicles', 'blocked', 'active'));
        } catch (ModelNotFoundException $e) {
            Log::info($e);
            return back()->with('flash_error', 'Server Error!');
        }
    }


}
