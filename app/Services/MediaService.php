<?php

namespace App\Services;

use App\Enums\FileUploadType;
use App\Repositories\LogRepository;
use App\Repositories\MediaRepository;

class MediaService
{
    public function __construct(
        private MediaRepository $repository,
        private LogRepository $logRepository
    ) {}

    public function upload(string $parentId, ?string $file, FileUploadType $type, string $disk = 'public')
    {
        $uploadedFile = $this->repository->upload($parentId, $file, $type, $disk);

        $this->logRepository->create([
            'message' => 'File uploaded successfully.',
            'log_id' => $uploadedFile->id ?? null,
            'log_module' => 'media',
            'data' => $uploadedFile,
        ]);

        return $uploadedFile;
    }

    public function get(string $parentId, FileUploadType $type, string $disk = 'public')
    {
        return $this->repository->get($parentId, $type, $disk);
    }

    public function logError(string $message, \Throwable $th, array $data = []): void
    {
        $this->logRepository->create([
            'message' => $message,
            'details' => $th->getMessage(),
            'log_module' => 'media',
            'data' => $data,
        ], isError: true);
    }
}
