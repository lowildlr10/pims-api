<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class LogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): LengthAwarePaginator
    {
        $user = auth()->user();

        $search = trim($request->get('search', ''));
        $perPage = $request->get('per_page', 50);
        $columnSort = $request->get('column_sort', 'logged_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $logId = $request->get('log_id', '');

        $logs = Log::with('user');

        if ($user->tokenCan('super:*')) {} else {
            if (empty($logId)) $logs = $logs->where('user_id', $user->id);
        }

        if (!empty($search) && empty($logId)) {
            $logs = $logs->where(function($query) use ($search){
                $query->where('log_id', 'ILIKE', "%{$search}%")
                    ->orWhere('log_module', 'ILIKE', "%{$search}%")
                    ->orWhere('log_type', 'ILIKE', "%{$search}%")
                    ->orWhere('message', 'ILIKE', "%{$search}%")
                    ->orWhere('details', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('user', 'firstname', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('user', 'middlename', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('user', 'lastname', 'ILIKE', "%{$search}%");
            });
        }

        if ($logId) $logs = $logs->where('log_id', $logId);

        if (in_array($sortDirection, ['asc', 'desc'])) {
            switch ($columnSort) {
                case 'user_formatted':
                    $columnSort = '';
                    $signatories = $signatories->orderBy(
                        User::select('firstname')->whereColumn('users.id', 'signatories.user_id')
                    );
                    break;
                case 'log_module_formatted':
                    $columnSort = 'log_module';
                    break;
                case 'log_type_formatted':
                    $columnSort = 'log_type';
                    break;
                case 'logged_at_formatted':
                    $columnSort = 'logged_at';
                    break;
                default:
                    break;
            }

            if ($columnSort) {
                $logs = $logs->orderBy($columnSort, $sortDirection);
            }
        }

        return $logs->paginate($perPage);
    }
}
