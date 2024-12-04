<?php

namespace App\Http\Controllers\V1\Account;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DesignationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $search = trim($request->get('search', ''));
        $perPage = $request->get('per_page', 50);
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
            $designations = $designations->paginate($perPage);
        } else {
            $designations = $designations->limit($perPage)->get();
        }

        return response()->json([
            'data' => $designations
        ]);
    }
}
