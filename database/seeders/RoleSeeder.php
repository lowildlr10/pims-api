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
                'account-user:view',
                'account-role:view',
                'account-department:view',
                'account-section:view',
                'company:*',
                'pr:view,approve,disapprove,print',
                'rfq:view,print',
                'aoq:view,print',
                'po:view,print',
                'iar:view,print',
                'ors:view,print',
                'dv:view,print',
                'inventory:view,print',
                'lib-fund-source:view',
                'lib-paper-size:*',
                'lib-unit-issue:*'
            ]
        ],
        [
            'role_name' => 'End User',
            'permissions' => [
                'user:*',
                'comapny:view',
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
                'account-user:*',
                'account-role:*',
                'account-department:*',
                'account-section:*',
                'company:*',
                'pr:*',
                'rfq:*',
                'aoq:*',
                'po:*',
                'iar:*',
                'ors:*',
                'dv:*',
                'inventory:*',
                'payment:*',
                'lib-fund-source:*',
                'lib-inv-class:*',
                'lib-item-class:*',
                'lib-mfo-pap:*',
                'lib-mode-proc:*',
                'lib-paper-size:*',
                'lib-supplier:*',
                'lib-uacs-code:*',
                'lib-unit-issue:*'
            ]
        ],
        [
            'role_name' => 'Supply Officer',
            'permissions' => [
                'supply:*',
                'comapny:view',
                'pr:view,create,update,cancel,delete,print',
                'rfq:*',
                'aoq:*',
                'po:*',
                'iar:*',
                'ors:create,update,delete,submit,print',
                'dv:create,update,delete,submit,print',
                'inventory:*',
                'lib-fund-source:*',
                'lib-inv-class:*',
                'lib-item-class:*',
                'lib-mode-proc:*',
                'lib-paper-size:*',
                'lib-supplier:*',
                'lib-unit-issue:*'
            ]
        ],
        [
            'role_name' => 'Budget',
            'permissions' => [
                'budget:*',
                'comapny:view',
                'pr:view,print',
                'rfq:view,print',
                'aoq:view,print',
                'po:view,print',
                'iar:view,print',
                'ors:*',
                'dv:view,print',
                'lib-fund-source:*',
                'lib-mfo-pap:*',
                'lib-paper-size:*',
                'lib-uacs-code:*',
                'lib-unit-issue:*'
            ]
        ],
        [
            'role_name' => 'Accounting',
            'permissions' => [
                'accounting:*',
                'comapny:view',
                'pr:view,print',
                'rfq:view,print',
                'aoq:view,print',
                'po:view,print',
                'iar:view,print',
                'ors:view,print',
                'dv:*',
                'lib-fund-source:*',
                'lib-mfo-pap:*',
                'lib-paper-size:*',
                'lib-uacs-code:*',
                'lib-unit-issue:*'
            ]
        ],
        [
            'role_name' => 'Cashier',
            'permissions' => [
                'cashier:*',
                'comapny:view',
                'pr:view,print',
                'rfq:view,print',
                'aoq:view,print',
                'po:view,print',
                'iar:view,print',
                'ors:view,print',
                'dv:view,print',
                'payment:*',
                'lib-fund-source:*',
                'lib-mfo-pap:*',
                'lib-uacs-code:*'
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
