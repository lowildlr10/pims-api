<?php

namespace App\Http\Controllers\V1\Account;

use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $search = trim($request->get('search', ''));
        $perPage = $request->get('per_page', 50);
        $columnSort = $request->get('column_sort', 'position_name');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        $positions = Position::query();

        if (!empty($search)) {
            $positions = $positions->where(function($query) use ($search){
                $query->where('position_name', 'ILIKE', "%{$search}%");
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            $positions = $positions->orderBy($columnSort, $sortDirection);
        }

        if ($paginated) {
            $positions = $positions->paginate($perPage);
        } else {
            $positions = $positions->limit($perPage)->get();
        }

        return response()->json([
            'data' => $positions
        ]);
    }
}
