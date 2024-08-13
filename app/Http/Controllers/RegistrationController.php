<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Organization;
use App\Models\User;
use App\Models\Team;
use App\Models\Member;

class RegistrationController extends Controller
{
    //
    public function create(){
        return Inertia::render('Auth/Registration',[
            'organizations'=>Organization::whereNotNull('registration_code')->get()
        ]);
    }
    public function store(Request $request){
        $this->validate($request,[
            'given_name'=>'required',
            'family_name'=>'required',
            'organization_id' => 'required',
            'registration_code' => 'required',
            'email'=>'required|email',
            'password'=>'required',
        ]);
        $organization=Organization::find($request->organization_id);
        if(!$organization){
            return redirect()->route('/');
            //return redirect()->back()->withErrors(['message'=>'Organization not found']);
        }

        if($request->registration_code != $organization->registration_code){
            return redirect()->back()->withErrors([
                'code'=>'code_error',
                'message'=>'Registration code incorrect, please confirm with the organization administrator.'
            ]);
        }

        //make sure user account can create
        $user=User::where('email',$request->email)->first();
        if($user){ // has user account
            $member=$organization->members->where('user_id',$user->id)->first();
            if($member){ //has user account and also same organization member
                return redirect()->back()->withErrors([
                    'code'=>'account_registered',
                    'message'=>'The email is already in use.'
                ]);
            }
        }else{ // no user account, ofcourse no member record as well
            $name=explode(' ',$request->family_name);
            $user=User::create([
                'name' => end($name),
                'email' => $request->email,
                'password' => Hash::make($request->password)
            ]);
            $user->ownedTeams()->save(Team::forceCreate([
                'user_id' => $user->id,
                'name' => $user->name."'s Team",
                //'name' => explode(' ', $user->name, 2)[0]."'s Team",
                'personal_team' => true,
            ]));
        }
        //if no member, but the user account is surely exist
        if(empty($member)){
            $member=Member::create([
                'user_id'=>$user->id,
                'given_name'=>$request->given_name,
                'middle_name'=>$request->middle_name??null,
                'family_name'=>$request->family_name,
                'email'=>$request->email
            ]);
        }
        $member->organizations()->attach($organization->id);

        return to_route('login');

    }
}

