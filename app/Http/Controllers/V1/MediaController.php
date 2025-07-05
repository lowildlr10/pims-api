<?php

namespace App\Http\Controllers\V1;

use App\Enums\FileUploadType;
use App\Http\Controllers\Controller;
use App\Repositories\MediaRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ValueError;

class MediaController extends Controller
{
    public function __construct(MediaRepository $repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * Store the specified resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => 'nullable|string',
            'type' => 'required',
            'parent_id' => 'required',
            'disk' => 'nullable|string'
        ]);
        
        try {
            $type = FileUploadType::from($validated['type']);
        } catch (ValueError $e) {
            return response()->json([
                'message' => 'Invalid file upload type.'
            ], 422);
        }

        try {
            $file = $this->repository->upload(
                $validated['parent_id'], 
                $validated['file'], 
                $type,
                $request->get('disk', 'public')
            );
            
            return response()->json([
                'data' => [
                    'data' => $file,
                    'message' => 'Successfully uploaded file'
                ]
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage()
            ], 422);
        }
    }
    
    /**
     * Display the specified resource.
     */
    public function show(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required',
            'parent_id' => 'required',
            'disk' => 'nullable|string'
        ]);
        
        try {
            $type = FileUploadType::from($validated['type']);
        } catch (ValueError $e) {
            return response()->json([
                'message' => 'Invalid file upload type.'
            ], 422);
        }
        
        try {
            $file = $this->repository->get(
                $validated['parent_id'], 
                $type,
                $request->get('disk', 'public')
            );
            
            return response()->json([
                'data' => [
                    'data' => $file,
                    'message' => 'OK'
                ]
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage()
            ], 422);
        }
    }
}
