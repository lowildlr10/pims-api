<?php

namespace App\Interfaces;

use App\Enums\FileUploadType;

interface MediaRepositoryInterface
{
    public function upload(string $id, string $file, FileUploadType $type): string;

    public function get(string $id, FileUploadType $type): string;
}
