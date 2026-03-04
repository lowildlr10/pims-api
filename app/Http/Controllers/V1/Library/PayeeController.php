<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Library - Payees
 * APIs for retrieving payees (suppliers and users combined)
 */
class PayeeController extends Controller
{
    /**
     * List Payees
     *
     * Retrieve a combined list of suppliers and users as payees.
     *
     * @queryParam search string Search by name.
     * @queryParam show_inactive boolean Include inactive suppliers. Default false.
     *
     * @response 200 {
     *   "data": [
     *     {"id": "uuid", "name": "Supplier Name", "type": "App\\Models\\Supplier", "payee_value": "App\\Models\\Supplier:uuid"},
     *     {"id": "uuid", "name": "User Full Name", "type": "App\\Models\\User", "payee_value": "App\\Models\\User:uuid"}
     *   ]
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->get('search', '');
        $showInactive = filter_var($request->get('show_inactive', false), FILTER_VALIDATE_BOOLEAN);

        $supplierQuery = Supplier::query()->select(['id', 'supplier_name', 'active']);

        if (! $showInactive) {
            $supplierQuery->where('active', true);
        }

        if (! empty($search)) {
            $supplierQuery->where('supplier_name', 'ILIKE', "%{$search}%");
        }

        $suppliers = $supplierQuery->orderBy('supplier_name')->get()->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->supplier_name,
            'type' => 'App\\Models\\Supplier',
            'payee_value' => 'App\\Models\\Supplier:'.$s->id,
        ]);

        $userQuery = User::query()->select(['id', 'firstname', 'middlename', 'lastname']);

        if (! empty($search)) {
            $userQuery->where(function ($q) use ($search) {
                $q->where('firstname', 'ILIKE', "%{$search}%")
                    ->orWhere('lastname', 'ILIKE', "%{$search}%")
                    ->orWhere('middlename', 'ILIKE', "%{$search}%");
            });
        }

        $users = $userQuery->orderBy('firstname')->get()->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->fullname,
            'type' => 'App\\Models\\User',
            'payee_value' => 'App\\Models\\User:'.$u->id,
        ]);

        $payees = $suppliers->merge($users)->sortBy('name')->values();

        return response()->json([
            'data' => $payees,
        ]);
    }
}
