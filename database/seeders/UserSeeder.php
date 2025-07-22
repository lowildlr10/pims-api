<?php

namespace Database\Seeders;

use App\Models\Designation;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    private $users = [
        [
            'position_name' => 'System Administrator',
            'designation_name' => 'System Administrator',
            'section_name' => 'Systems and Network Administration Section',
            'role_name' => 'Administrator',
            'employee_id' => '1111',
            'firstname' => 'System',
            'lastname' => 'Administrator',
            'sex' => 'male',
            'username' => 'sysadmin',
            'email' => 'sysadmin@email.com',
            'restricted' => false,
            'allow_signature' => true,
        ],
        // [
        //     'position_name' => 'Local Chief Executive',
        //     'designation_name' => 'Municipal Mayor',
        //     'section_name' => 'Municipal Mayor',
        //     'role_name' => 'Agency Head',
        //     'employee_id' => '1112',
        //     'firstname' => 'Juan',
        //     'lastname' => 'Dela Cruz',
        //     'sex' => 'male',
        //     'username' => 'mayor',
        //     'email' => 'mayor@email.com',
        //     'restricted' => false,
        //     'allow_signature' => true,
        // ],
        // [
        //     'position_name' => 'Supply Officer III',
        //     'designation_name' => 'Procurement and Supply',
        //     'section_name' => 'Procurement Section',
        //     'role_name' => 'Supply Officer',
        //     'employee_id' => '1113',
        //     'firstname' => 'Maria',
        //     'lastname' => 'Gomez',
        //     'sex' => 'female',
        //     'username' => 'supplyuser',
        //     'email' => 'supplyuser@email.com',
        //     'restricted' => false,
        //     'allow_signature' => true,
        // ],
        // [
        //     'position_name' => 'Budget Officer II',
        //     'designation_name' => 'Municipal Budget Officer',
        //     'section_name' => 'Budget Preparation Section',
        //     'role_name' => 'Budget',
        //     'employee_id' => '1114',
        //     'firstname' => 'Carlos',
        //     'lastname' => 'Santos',
        //     'sex' => 'male',
        //     'username' => 'budget',
        //     'email' => 'budget@email.com',
        //     'restricted' => false,
        //     'allow_signature' => true,
        // ],
        // [
        //     'position_name' => 'Municipal Accountant III',
        //     'designation_name' => 'Accounting Services',
        //     'section_name' => 'Accounting Section',
        //     'role_name' => 'Accounting',
        //     'employee_id' => '1115',
        //     'firstname' => 'Ana',
        //     'lastname' => 'Lopez',
        //     'sex' => 'female',
        //     'username' => 'accountant',
        //     'email' => 'accountant@email.com',
        //     'restricted' => false,
        //     'allow_signature' => true,
        // ],
        // [
        //     'position_name' => 'Municipal Cashier III',
        //     'designation_name' => 'Cash Handling',
        //     'section_name' => 'Cashiering Section',
        //     'role_name' => 'Cashier',
        //     'employee_id' => '1116',
        //     'firstname' => 'Roberto',
        //     'lastname' => 'Diaz',
        //     'sex' => 'male',
        //     'username' => 'cashier',
        //     'email' => 'cashier@email.com',
        //     'restricted' => false,
        //     'allow_signature' => true,
        // ],
        // [
        //     'position_name' => 'Planning Officer III',
        //     'designation_name' => 'Planning and Development',
        //     'section_name' => 'Planning Section',
        //     'role_name' => 'End User',
        //     'employee_id' => '1117',
        //     'firstname' => 'Luis',
        //     'lastname' => 'Marquez',
        //     'sex' => 'male',
        //     'username' => 'planning',
        //     'email' => 'planning@email.com',
        //     'restricted' => false,
        //     'allow_signature' => false,
        // ],
        // [
        //     'position_name' => 'IT Officer III',
        //     'designation_name' => 'Information Technology',
        //     'section_name' => 'IT Support Section',
        //     'role_name' => 'End User',
        //     'employee_id' => '1118',
        //     'firstname' => 'Sophia',
        //     'lastname' => 'Reyes',
        //     'sex' => 'female',
        //     'username' => 'misuser',
        //     'email' => 'misuser@email.com',
        //     'restricted' => false,
        //     'allow_signature' => false,
        // ],
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
            $section = DB::table('sections')
                ->first();

            $user = User::create([
                'employee_id' => $user['employee_id'],
                'firstname' => $user['firstname'],
                'lastname' => $user['lastname'],
                'sex' => $user['sex'],
                'department_id' => $section->department_id,
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
        User::factory(100)->create()->each(function (User $user) use ($roles) {
            // Attach a random role to each user
            $user->roles()->attach($roles->random()->id);
        });
    }
}
