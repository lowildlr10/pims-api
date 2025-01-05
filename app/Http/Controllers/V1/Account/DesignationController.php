<?php

namespace App\Http\Controllers\V1\Account;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class DesignationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse | LengthAwarePaginator
    {
        $search = trim($request->get('search', ''));
        $perPage = $request->get('per_page', 50);
        $showAll = filter_var($request->get('show_all', false), FILTER_VALIDATE_BOOLEAN);
        $columnSort = $request->get('column_sort', 'designation_name');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        $designations = Designation::query();

        if (!empty($search)) {
            $designations = $designations->where(function($query) use ($search){
                $query->where('designation_name', 'ILIKE', "%{$search}%");
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            $designations = $designations->orderBy($columnSort, $sortDirection);
        }

        if ($paginated) {
            return $designations->paginate($perPage);
        } else {
            if ($showAll) {
                $designations = $designations->get();
            } else {
                $designations = $designations->limit($perPage)->get();
            }

            return response()->json([
                'data' => $designations
            ]);
        }
    }
}
