<?php

namespace Database\Seeders\Production;

use App\Models\Role;
use Illuminate\Database\Seeder;

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
                'po:view,approve,print',
                'iar:view,print',
                'obr:view,print',
                'dv:view,print',

                'account-department:view',
                'account-section:view',
                'account-user:view',
                
                'inv-supply:view',
                'inv-issuance:view,print',

                'lib-account-class:view',
                'lib-account:view',
                'lib-bid-committee:view',
                'lib-fpp:view',
                'lib-fund-source:view',
                'lib-item-class:view',
                'lib-mode-proc:view',
                'lib-paper-size:view',
                'lib-responsibility-center:view',
                'lib-signatory:view',
                'lib-supplier:view',
                'lib-unit-issue:view',

                'company:*',
                'system-log:*',
            ],
        ],
        [
            'role_name' => 'End User',
            'permissions' => [
                'user:*',

                'pr:view,create,update,submit,issue-rfq,cancel,print',
                'rfq:view,create,update,issue,cancel,print',
                'aoq:view,print',
                'po:view,print',
                'iar:view,print',
                'obr:view,print',
                'dv:view,print',

                'account-department:view',
                'account-section:view',
                'account-user:view',
                
                'lib-account:view',
                'lib-bid-committee:view',
                'lib-fpp:view',
                'lib-fund-source:view',
                'lib-mode-proc:view',
                'lib-paper-size:view',
                'lib-responsibility-center:view',
                'lib-signatory:view',
                'lib-supplier:view',
                'lib-unit-issue:view',

                'system-log:*',
            ],
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
                'obr:*',
                'dv:*',

                'account-department:*',
                'account-section:*',
                'account-user:*',
                
                'inv-supply:*',
                'inv-issuance:*',

                'lib-account-class:*',
                'lib-account:*',
                'lib-bid-committee:*',
                'lib-fpp:*',
                'lib-fund-source:*',
                'lib-item-class:*',
                'lib-mode-proc:*',
                'lib-paper-size:*',
                'lib-responsibility-center:*',
                'lib-signatory:*',
                'lib-supplier:*',
                'lib-unit-issue:*',

                'company:*',
                'system-log:*',
            ],
        ],
        [
            'role_name' => 'Supply Officer',
            'permissions' => [
                'supply:*',

                'pr:*',
                'rfq:*',
                'aoq:*',
                'po:*',
                'iar:*',
                'obr:view,create,update,pending,print',
                'dv:view,create,update,pending,print',

                'account-department:view',
                'account-section:view',
                'account-user:view',
                
                'inv-supply:*',
                'inv-issuance:*',

                'lib-account:view',
                'lib-bid-committee:*',
                'lib-fpp:view',
                'lib-fund-source:*',
                'lib-item-class:*',
                'lib-mode-proc:*',
                'lib-paper-size:view',
                'lib-responsibility-center:view',
                'lib-signatory:view',
                'lib-supplier:*',
                'lib-unit-issue:*',

                'system-log:*',
            ],
        ],
        [
            'role_name' => 'Budget',
            'permissions' => [
                'budget:*',

                'pr:view,approve-cash-available,print',
                'rfq:view,print',
                'aoq:view,print',
                'po:view,print',
                'iar:view,print',
                'obr:*',
                'dv:view,print',

                'account-department:view',
                'account-section:view',
                'account-user:view',
                
                'lib-account-class:*',
                'lib-account:*',
                'lib-fpp:*',
                'lib-fund-source:*',
                'lib-mode-proc:view',
                'lib-paper-size:view',
                'lib-responsibility-center:*',
                'lib-signatory:view',
                'lib-supplier:view',
                'lib-unit-issue:view',
                
                'system-log:*',
            ],
        ],
        [
            'role_name' => 'Accountant',
            'permissions' => [
                'accountant:*',

                'pr:view,approve-cash-available,print',
                'rfq:view,print',
                'aoq:view,print',
                'po:view,print',
                'iar:view,print',
                'obr:view,print',
                'dv:*',

                'account-department:view',
                'account-section:view',
                'account-user:view',
                
                'lib-account-class:*',
                'lib-account:*',
                'lib-fpp:*',
                'lib-fund-source:*',
                'lib-mode-proc:view',
                'lib-paper-size:view',
                'lib-responsibility-center:*',
                'lib-signatory:view',
                'lib-supplier:view',
                'lib-unit-issue:view',

                'system-log:*',
            ],
        ],
        [
            'role_name' => 'Cashier',
            'permissions' => [
                'cashier:*',

                'pr:view,approve-cash-available,print',
                'rfq:view,print',
                'aoq:view,print',
                'po:view,print',
                'iar:view,print',
                'obr:view,print',
                'dv:view,uodate,paid,print',

                'account-department:view',
                'account-section:view',
                'account-user:view',
                
                'lib-account-class:*',
                'lib-account:*',
                'lib-fpp:*',
                'lib-fund-source:*',
                'lib-mode-proc:view',
                'lib-paper-size:view',
                'lib-responsibility-center:*',
                'lib-signatory:view',
                'lib-supplier:view',
                'lib-unit-issue:view',

                'system-log:*',
            ],
        ],
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
