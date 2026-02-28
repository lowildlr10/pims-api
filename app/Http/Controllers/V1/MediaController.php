<?php

namespace App\Http\Controllers\V1;

use App\Enums\FileUploadType;
use App\Http\Controllers\Controller;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ValueError;

/**
 * @group Media Management
 * APIs for managing file uploads and media
 */
class MediaController extends Controller
{
    public function __construct(
        protected MediaService $service
    ) {}

    /**
     * Upload Media File
     *
     * Store a new file upload.
     *
     * @bodyParam file string nullable The base64 encoded file content.
     * @bodyParam type string required The file upload type.
     * @bodyParam parent_id string required The parent entity ID.
     * @bodyParam disk string nullable The storage disk. Default: public.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Successfully uploaded file"
     * }
     * @response 422 {
     *   "message": "Invalid file upload type."
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => 'nullable|string',
            'type' => 'required',
            'parent_id' => 'required',
            'disk' => 'nullable|string',
        ]);

        try {
            $type = FileUploadType::from($validated['type']);
        } catch (ValueError $e) {
            return response()->json([
                'message' => 'Invalid file upload type.',
            ], 422);
        }

        try {
            $file = $this->service->upload(
                $validated['parent_id'],
                $validated['file'],
                $type,
                $validated['disk'] ?? 'public'
            );

            return response()->json([
                'data' => $file,
                'message' => 'Successfully uploaded file',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('File upload failed.', $th, $validated);

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Get Media File
     *
     * Retrieve a file by parent ID and type.
     *
     * @queryParam type string required The file upload type.
     * @queryParam parent_id string required The parent entity ID.
     * @queryParam disk string nullable The storage disk. Default: public.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "OK"
     * }
     * @response 422 {
     *   "message": "Invalid file upload type."
     * }
     */
    public function show(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required',
            'parent_id' => 'required',
            'disk' => 'nullable|string',
        ]);

        try {
            $type = FileUploadType::from($validated['type']);
        } catch (ValueError $e) {
            return response()->json([
                'message' => 'Invalid file upload type.',
            ], 422);
        }

        try {
            $file = $this->service->get(
                $validated['parent_id'],
                $type,
                $validated['disk'] ?? 'public'
            );

            return response()->json([
                'data' => $file,
                'message' => 'OK',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Failed to get file.', $th, $validated);

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }
}
