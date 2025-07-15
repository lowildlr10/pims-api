<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Repositories\LogRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    private LogRepository $logRepository;

    public function __construct(LogRepository $logRepository)
    {
        $this->logRepository = $logRepository;
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request): JsonResponse
    {
        $company = Company::first();

        return response()->json([
            'data' => [
                'company' => $company,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
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

        $company = Company::first();

        if (! $company) {
            $this->logRepository->create([
                'message' => "Company data doesn't exist in the database.",
                'log_id' => $company->id,
                'log_module' => 'company',
                'data' => [
                    'company_model' => $company,
                    'payload' => $validated,
                ],
            ], isError: true);

            return response()->json([
                'message' => 'There is an issue with the company configuration. Please contact the administrator.',
            ], 422);
        }

        try {
            $themColors = json_decode($validated['theme_colors']);

            $company->update(array_merge(
                $validated,
                ['theme_colors' => $themColors]
            ));

            $this->logRepository->create([
                'message' => 'Company profile updated successfully.',
                'log_id' => $company->id,
                'log_module' => 'company',
                'data' => $company,
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => 'Company profile update failed.',
                'details' => $th->getMessage(),
                'log_id' => $company->id,
                'log_module' => 'company',
                'data' => $validated,
            ], isError: true);

            return response()->json([
                'message' => 'Company profile update failed. Please try again.',
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $company,
                'message' => 'Company profile updated successfully.',
            ],
        ]);
    }
}
