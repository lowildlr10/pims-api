<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompanyResource;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Company
 * APIs for managing company settings
 */
class CompanyController extends Controller
{
    public function __construct(
        protected CompanyService $service
    ) {}

    /**
     * Get Company
     *
     * Retrieve the company profile.
     *
     * @response 200 {
     *   "data": {...}
     * }
     */
    public function show(): JsonResponse
    {
        $company = $this->service->get();

        if (! $company) {
            return response()->json(['message' => 'Company not found.'], 404);
        }

        return response()->json([
            'data' => new CompanyResource($company->load('head')),
        ]);
    }

    /**
     * Update Company
     *
     * Update the company profile.
     *
     * @bodyParam company_name string required The company name.
     * @bodyParam address string optional The address.
     * @bodyParam municipality string optional The municipality.
     * @bodyParam province string optional The province.
     * @bodyParam region string optional The region.
     * @bodyParam company_type string optional The company type.
     * @bodyParam company_head_id string optional The company head user ID.
     * @bodyParam favicon string optional The favicon.
     * @bodyParam company_logo string optional The company logo.
     * @bodyParam login_background string optional The login background.
     * @bodyParam theme_colors string required The theme colors as JSON.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Company profile updated successfully."
     * }
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_name' => 'required|string',
            'address' => 'nullable|string',
            'municipality' => 'nullable|string',
            'province' => 'nullable|string',
            'region' => 'nullable|string',
            'company_type' => 'nullable|string',
            'company_head_id' => 'nullable|string',
            'favicon' => 'nullable|string',
            'company_logo' => 'nullable|string',
            'login_background' => 'nullable|string',
            'theme_colors' => 'required|string',
        ]);

        try {
            $company = $this->service->update($validated);

            return response()->json([
                'data' => new CompanyResource($company->load('head')),
                'message' => 'Company profile updated successfully.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Company profile update failed.', $th, $validated);

            return response()->json([
                'message' => 'Company profile update failed. Please try again.',
            ], 422);
        }
    }
}
