<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse | LengthAwarePaginator
    {
        $search = trim($request->get('search', ''));
        $perPage = $request->get('per_page', 50);
        $showAll = filter_var($request->get('show_all', false), FILTER_VALIDATE_BOOLEAN);
        $columnSort = $request->get('column_sort', 'location_name');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        $locations = Location::query();

        if (!empty($search)) {
            $locations = $locations->where(function($query) use ($search){
                $query->where('location_name', 'ILIKE', "%{$search}%");
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            $locations = $locations->orderBy($columnSort, $sortDirection);
        }

        if ($paginated) {
            return $locations->paginate($perPage);
        } else {
            if ($showAll) {
                $locations = $locations->get();
            } else {
                $locations = $locations->limit($perPage)->get();
            }

            return response()->json([
                'data' => $locations
            ]);
        }
    }
}
