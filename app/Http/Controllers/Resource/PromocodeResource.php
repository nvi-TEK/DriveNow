<?php

namespace App\Http\Controllers\Resource;

use App\Promocode;
use App\Fleet;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Controllers\Controller;

class PromocodeResource extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $promocodes = Promocode::orderBy('created_at' , 'desc')->get();
        return view('admin.promocode.index', compact('promocodes'));
    }

    public function Fleetindex()
    {
        $promocodes = Promocode::where('fleet_id', Auth::user()->id)->orderBy('created_at' , 'desc')->get();
        return view('fleet.promocode.index', compact('promocodes'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $fleet = Fleet::get();
        return view('admin.promocode.create', compact('fleet'));
    }

    public function Fleetcreate()
    {
        return view('fleet.promocode.create');
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
            'promo_code' => 'required|max:100|unique:promocodes',
            'discount' => 'required|numeric',
            'expiration' => 'required',
            'type' => 'required',
            'driver_contribution' => 'required',
            'admin_contribution' => 'required',
        ]);

        try{

            Promocode::create($request->all());
            return back()->with('flash_success','Promocode Saved Successfully');

        } 

        catch (ModelNotFoundException $e) {
            return back()->with('flash_errors', 'Promocode Not Found');
        }
    }

    public function Fleetstore(Request $request)
    {
        $this->validate($request, [
            'promo_code' => 'required|max:100|unique:promocodes',
            'discount' => 'required|numeric',
            'expiration' => 'required',
        ]);

        try{

            Promocode::create($request->all());
            return back()->with('flash_success','Promocode Saved Successfully');

        } 

        catch (ModelNotFoundException $e) {
            return back()->with('flash_errors', 'Promocode Not Found');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Promocode  $promocode
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            return Promocode::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return $e;
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Promocode  $promocode
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try {
            $promocode = Promocode::findOrFail($id);
            $fleet = Fleet::get();
            return view('admin.promocode.edit',compact('promocode','fleet'));
        } catch (ModelNotFoundException $e) {
            return $e;
        }
    }

    public function Fleetedit($id)
    {
        try {
            $promocode = Promocode::findOrFail($id);
            return view('fleet.promocode.edit',compact('promocode'));
        } catch (ModelNotFoundException $e) {
            return $e;
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Promocode  $promocode
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'promo_code' => 'required|max:100',
            'discount' => 'required|numeric',
            'expiration' => 'required',
        ]);

        try {

           $promo = Promocode::findOrFail($id);

            $promo->promo_code = $request->promo_code;
            $promo->type = $request->type;
            $promo->admin_contribution = $request->admin_contribution;
            $promo->driver_contribution = $request->driver_contribution;
            $promo->fleet_id = $request->fleet_id;
            $promo->discount = $request->discount;
            if($request->has('count_max')){
                $promo->count_max = $request->count_max;
            }
            $promo->expiration = $request->expiration;
            $promo->save();

            return redirect()->route('admin.promocode.index')->with('flash_success', 'Promocode Updated Successfully');    
        } 

        catch (ModelNotFoundException $e) {
            return back()->with('flash_errors', 'Promocode Not Found');
        }
    }

    public function Fleetupdate(Request $request, $id)
    {
        $this->validate($request, [
            'promo_code' => 'required|max:100',
            'discount' => 'required|numeric',
            'expiration' => 'required',
        ]);

        try {

           $promo = Promocode::findOrFail($id);

            $promo->promo_code = $request->promo_code;
            $promo->fleet_id = $request->fleet_id;
            $promo->discount = $request->discount;
            $promo->expiration = $request->expiration;
            $promo->save();

            return redirect()->route('fleet.promocode.index')->with('flash_success', 'Promocode Updated Successfully');    
        } 

        catch (ModelNotFoundException $e) {
            return back()->with('flash_errors', 'Promocode Not Found');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Promocode  $promocode
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            Promocode::find($id)->delete();
            return back()->with('flash_success', 'Promocode deleted successfully');
        } 
        catch (ModelNotFoundException $e) {
            return back()->with('flash_errors', 'Promocode Not Found');
        }
    }
}
