<?php

namespace App\Http\Controllers\Resource;

use App\Admin;
use App\UserRequests;
use Illuminate\Http\Request;
use App\Helpers\Helper;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Controllers\Controller;
use Exception;
use Setting;

class ModeratorResource extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = Admin::orderBy('updated_at' , 'desc')->whereNull('status')->get();
        
        return view('admin.moderators.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.moderators.create');
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
            'email' => 'required|unique:admins,email|email|max:255',
            'mobile' => 'between:6,13',
            'picture' => 'mimes:jpeg,jpg,bmp,png|max:5242880',
            'password' => 'required|min:6',
        ]);

        try{

            $user = new Admin;
            $user->password = bcrypt($request->password);
            if($request->hasFile('picture')) {
                $user->picture = Helper::upload_picture($request->file('picture'));
            }
            $user->name = $request->name;
            $user->mobile = $request->mobile;
            $user->email = $request->email;
            $user->role = $request->role;
            $user->save();


            return back()->with('flash_success','Moderator Details Saved Successfully');

        } 

        catch (Exception $e) {
            return back()->with('flash_error', 'Moderator Not Found');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $user = Admin::findOrFail($id);
            return view('admin.moderators.user-details', compact('user'));
        } catch (ModelNotFoundException $e) {
            return $e;
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try {
            $user = Admin::findOrFail($id);
            return view('admin.moderators.edit',compact('user'));
        } catch (ModelNotFoundException $e) {
            return $e;
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required|max:255',
            'mobile' => 'between:6,13',
            'picture' => 'mimes:jpeg,jpg,bmp,png|max:5242880',
        ]);

        try {

            $user = Admin::findOrFail($id);

            if($request->hasFile('picture')) {
                $user->picture = Helper::upload_picture($request->file('picture'));
            }

            $user->name = $request->name;
            $user->mobile = $request->mobile;
            $user->role = $request->role;
            $user->save();

            return redirect()->route('admin.moderator.index')->with('flash_success', 'Moderator Updated Successfully');    
        } 

        catch (ModelNotFoundException $e) {
            return back()->with('flash_error', 'User Not Found');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
         if(Setting::get('demo_mode', 0) == 1) {
            return back()->with('flash_error', 'Disabled for demo purposes! Please contact us at info@drivetry.com');
        }
        
        try {

            // Admin::find($id)->delete();
            $moderator = Admin::where('id',$id)->first();
            $moderator->status = 1;
            $moderator->save();

            return back()->with('flash_success', 'User deleted successfully');
        } 
        catch (Exception $e) {
            return back()->with('flash_error', 'User Not Found');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */

}
