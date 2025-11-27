<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            MemberSeeder::class,
            OrganizationSeeder::class,
            CompetitionScoreSeeder::class,
            FormSeeder::class,
            UserSeeder::class,
            GuardianSeeder::class,
            ConfigSeeder::class,
            PositionSeeder::class,
            ExamSeeder::class
        ]);
    }
}
