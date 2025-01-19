<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show(Request $request): JsonResponse
    {
        $company = Company::first();

        return response()->json([
            'data' => [
                'company' => $company
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string',
            'theme_colors' => 'required|string'
        ]);

        $company = Company::first();

        if (!$company) {
            return response()->json([
                'message' => 'There is an issue with the company configuration. Please contact the administrator.'
            ], 422);
        }

        try {
            $themColors = json_decode($validated['theme_colors']);

            $company->update(array_merge(
                $validated,
                ['theme_colors' => $themColors]
            ));
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Company profile update failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $company,
                'message' => 'Company profile updated successfully.'
            ]
        ]);
    }
}
