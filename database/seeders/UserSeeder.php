<?php

namespace Database\Seeders;

use App\Models\Designation;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $position = DB::table('positions')
            ->where('position_name', 'Computer Programmer')
            ->first();
        $designation = DB::table('designations')
            ->where('designation_name', 'System Administrator')
            ->first();
        $section = DB::table('sections')
            ->where('section_name', 'Section Test 0')
            ->first();

        User::create([
            'employee_id' => '1111',
            'firstname' => 'System',
            'lastname' => 'Administrator',
            'sex' => 'male',
            'division_id' => $section->division_id,
            'section_id' => $section->id,
            'position_id' => $position->id,
            'designation_id' => $designation->id,
            'username' => 'sysadmin',
            'email' => 'sysadmin@lguatok.com',
            'password' => bcrypt('passwd12345'),
            'restricted' => false,
            'allow_signature' => true
        ]);
    }
}
