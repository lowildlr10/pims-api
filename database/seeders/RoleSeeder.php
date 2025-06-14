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
                'account-division:view',
                'account-section:view',
                'company:*',
                'pr:view,create,update,approve,disapprove,approve-rfq,print',
                'rfq:view,print',
                'aoq:view,print',
                'po:view,print',
                'iar:view,print',
                'ors:view,print',
                'dv:view,print',
                'inv-supply:view,print',
                'inv-issuance:view,print',
                'lib-fund-source:view',
                'lib-item-class:view',
                'lib-mfo-pap:view',
                'lib-mode-proc:view',
                'lib-signatory:*',
                'lib-supplier:view',
                'lib-paper-size:*',
                'lib-uacs-class:view',
                'lib-uacs-code:view',
                'lib-unit-issue:*',
                'system-log:*'
            ]
        ],
        [
            'role_name' => 'End User',
            'permissions' => [
                'user:*',
                'comapny:view',
                'account-section:view',
                'pr:view,create,update,submit,cancel,print',
                'rfq:view,receive,submit,print',
                'aoq:view,print',
                'po:view,print',
                'iar:view,print',
                'ors:view,print',
                'dv:view,print',
                'lib-fund-source:view',
                'lib-paper-size:view',
                'lib-signatory:view',
                'lib-supplier:view',
                'lib-unit-issue:view',
                'system-log:*'
            ]
        ],
        [
            'role_name' => 'Administrator',
            'permissions' => [
                'super:*',
                'account-user:*',
                'account-role:*',
                'account-division:*',
                'account-section:*',
                'company:*',
                'pr:*',
                'rfq:*',
                'aoq:*',
                'po:*',
                'iar:*',
                'ors:*',
                'dv:*',
                'inv-supply:*',
                'inv-issuance:*',
                'payment:*',
                'lib-fund-source:*',
                'lib-item-class:*',
                'lib-mfo-pap:*',
                'lib-mode-proc:*',
                'lib-paper-size:*',
                'lib-signatory:*',
                'lib-supplier:*',
                'lib-uacs-class:*',
                'lib-uacs-code:*',
                'lib-unit-issue:*',
                'system-log:*'
            ]
        ],
        [
            'role_name' => 'Supply Officer',
            'permissions' => [
                'supply:*',
                'account-section:view',
                'account-user:view',
                'comapny:view',
                'pr:view,create,update,cancel,approve-rfq,print',
                'rfq:*',
                'aoq:*',
                'po:*',
                'iar:*',
                'ors:create,update,delete,submit,print',
                'dv:create,update,delete,submit,print',
                'inv-supply:*',
                'inv-issuance:*',
                'lib-fund-source:*',
                'lib-item-class:*',
                'lib-mode-proc:*',
                'lib-paper-size:*',
                'lib-signatory:*',
                'lib-supplier:*',
                'lib-unit-issue:*',
                'system-log:*'
            ]
        ],
        [
            'role_name' => 'Budget',
            'permissions' => [
                'budget:*',
                'account-section:view',
                'account-user:view',
                'comapny:view',
                'pr:view,create,update,approve-cash-available,cancel,submit,print',
                'rfq:view,print',
                'aoq:view,print',
                'po:view,print',
                'iar:view,print',
                'ors:*',
                'dv:view,print',
                'lib-fund-source:*',
                'lib-mfo-pap:*',
                'lib-paper-size:*',
                'lib-signatory:*',
                'lib-supplier:view',
                'lib-uacs-class:*',
                'lib-uacs-code:*',
                'lib-unit-issue:view',
                'system-log:*'
            ]
        ],
        [
            'role_name' => 'Accounting',
            'permissions' => [
                'accounting:*',
                'account-section:view',
                'account-user:view',
                'comapny:view',
                'pr:view,create,update,approve-cash-available,cancel,submit,print',
                'rfq:view,print',
                'aoq:view,print',
                'po:view,print',
                'iar:view,print',
                'ors:view,print',
                'dv:*',
                'lib-fund-source:*',
                'lib-mfo-pap:*',
                'lib-signatory:*',
                'lib-supplier:view',
                'lib-paper-size:*',
                'lib-uacs-class:*',
                'lib-uacs-code:*',
                'lib-unit-issue:view',
                'system-log:*'
            ]
        ],
        [
            'role_name' => 'Cashier',
            'permissions' => [
                'cashier:*',
                'account-section:view',
                'account-user:view',
                'comapny:view',
                'pr:view,create,update,approve-cash-available,cancel,submit,print',
                'rfq:view,print',
                'aoq:view,print',
                'po:view,print',
                'iar:view,print',
                'ors:view,print',
                'dv:view,print',
                'payment:*',
                'lib-fund-source:*',
                'lib-mfo-pap:*',
                'lib-signatory:*',
                'lib-supplier:view',
                'lib-uacs-class:*',
                'lib-uacs-code:*',
                'lib-unit-issue:view',
                'system-log:*'
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
