<?php

namespace Database\Seeders\Production;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Position;
use App\Models\Role;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    private $users = [
        [
            'position_name' => 'System Administrator',
            'designation_name' => 'System Administrator',
            'department_name' => 'Information and Communications Technology Office',
            'section_name' => 'Systems Development',
            'role_name' => 'Administrator',
            'employee_id' => '1111',
            'firstname' => 'System',
            'lastname' => 'Administrator',
            'sex' => 'male',
            'username' => 'sysadmin',
            'email' => 'sysadmin@email.com',
            'restricted' => false,
            'allow_signature' => false,
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->users as $user) {
            $position = Position::create([
                'position_name' => $user['position_name'],
            ]);
            $designation = Designation::create([
                'designation_name' => $user['designation_name'],
            ]);
            $role = DB::table('roles')
                ->where('role_name', $user['role_name'])
                ->first();
            $department = Department::create([
                'department_name' => $user['department_name'],
            ]);
            $section = Section::create([
                'department_id' => $department->id,
                'section_name' => $user['section_name'],
            ]);

            $user = User::create([
                'employee_id' => $user['employee_id'],
                'firstname' => $user['firstname'],
                'lastname' => $user['lastname'],
                'sex' => $user['sex'],
                'department_id' => $department->id,
                'section_id' => $section->id,
                'position_id' => $position->id,
                'designation_id' => $designation->id,
                'username' => $user['username'],
                'email' => $user['email'],
                'password' => bcrypt('passwd12345'),
                'restricted' => $user['restricted'],
                'allow_signature' => $user['allow_signature'],
            ]);

            $user->roles()->attach($role->id);
        }

        $roles = Role::all();

        // Generate 20 users (adjust number as needed)
        User::factory(10)->create()->each(function (User $user) use ($roles) {
            // Attach a random role to each user
            $user->roles()->attach($roles->random()->id);
        });
    }
}
