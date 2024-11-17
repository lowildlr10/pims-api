<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class RoleUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('firstname', 'System')->first();
        $roles = DB::table('roles')
            ->whereIn('role_name', ['Administrator'])
            ->get();

        foreach ($roles as $role) {
            $user->roles()->attach($role->id);
        }
    }
}
