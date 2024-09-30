<?php

namespace App\Actions\Fortify;

use App\Models\Team;
use App\Models\User;
use App\Models\Member;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Create a newly registered user.
     *
     * @param  array  $input
     * @return \App\Models\User
     */
    public function create(array $input)
    {
        Validator::make($input, [
            'name_zh' => ['required', 'string', 'max:255'],
            'name_fn' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            // 'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
            'organization_id' => 'required',
            // 'registration_code'=>['required',
            //     function($attribute, $value, $fail) use ($organization){
            //         if($organization==null){
            //             $fail('Registration Code Incorrect!');
            //         }
            //     }
            // ]
        ])->validate();

        $organization = Organization::where('id', $input['organization_id'])->first();

        return DB::transaction(function () use ($input, $organization) {
            return tap(User::create([
                'name' => $input['name_zh'],
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
            ]), function (User $user) use ($organization, $input) {
                $this->createTeam($user);
                $this->createMember($user, $organization, $input);
            });
        });
    }

    /**
     * Create a personal team for the user.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    protected function createTeam(User $user)
    {
        $user->ownedTeams()->save(Team::forceCreate([
            'user_id' => $user->id,
            'name' => explode(' ', $user->name, 2)[0] . "'s Team",
            'personal_team' => true,
        ]));
    }

    protected function createMember(User $user, $organization, $input)
    {
        $member = Member::forceCreate([
            'user_id' => $user->id,
            'name_zh' => $input['name_zh'],
            'name_fn' => $input['name_fn'],
            'display_name' => $input['name_zh'] . $input['name_fn'],
            'email' => $user->email
        ]);
        // dd($member->organizations()->attach($organization->id));
        $member->organizations()->attach($organization->id);
    }
}
