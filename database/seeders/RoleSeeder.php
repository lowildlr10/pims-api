<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    private $roles = [
        [
            'role_name' => 'Agency Head',
            'permissions' => [
                'head:*',
                'pr:view,approve,disapprove,print',
                'rfq:view,print',
                'aoq:view,print',
                'po:view,print',
                'iar:view,print',
                'ors:view,print',
                'dv:view,print',
                'inventory:view,print'
            ]
        ],
        [
            'role_name' => 'End User',
            'permissions' => [
                'user:*',
                'pr:view,create,update,submit,cancel,delete,print',
                'rfq:view,receive,submit,print',
                'aoq:view,print',
                'po:view,print',
                'iar:view,print',
                'ors:view,print',
                'dv:view,print'
            ]
        ],
        [
            'role_name' => 'Administrator',
            'permissions' => [
                'super:*',
                'pr:*',
                'rfq:*',
                'aoq:*',
                'po:*',
                'iar:*',
                'ors:*',
                'dv:*',
                'inventory:*',
                'payment:*'
            ]
        ],
        [
            'role_name' => 'Supply Officer',
            'permissions' => [
                'supply:*',
                'pr:view,create,update,cancel,delete,print',
                'rfq:*',
                'aoq:*',
                'po:*',
                'iar:*',
                'ors:create,update,delete,submit,print',
                'dv:create,update,delete,submit,print',
                'inventory:*'
            ]
        ],
        [
            'role_name' => 'Budget',
            'permissions' => [
                'budget:*',
                'pr:view,print',
                'rfq:view,print',
                'aoq:view,print',
                'po:view,print',
                'iar:view,print',
                'ors:*',
                'dv:view,print'
            ]
        ],
        [
            'role_name' => 'Accounting',
            'permissions' => [
                'accounting:*',
                'pr:view,print',
                'rfq:view,print',
                'aoq:view,print',
                'po:view,print',
                'iar:view,print',
                'ors:view,print',
                'dv:*'
            ]
        ],
        [
            'role_name' => 'Cashier',
            'permissions' => [
                'cashier:*',
                'pr:view,print',
                'rfq:view,print',
                'aoq:view,print',
                'po:view,print',
                'iar:view,print',
                'ors:view,print',
                'dv:view,print',
                'payment:*'
            ]
        ]
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->roles as $role) {
            Role::create($role);
        }
    }
}
