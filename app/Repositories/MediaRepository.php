<?php

namespace App\Repositories;

use App\Enums\FileUploadType;
use App\Interfaces\MediaRepositoryInterface;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class MediaRepository implements MediaRepositoryInterface
{
    protected LogRepository $logRepository;

    public function __construct(LogRepository $logRepository)
    {
        $this->logRepository = $logRepository;
    }

    public function upload(string $id, string $file, FileUploadType $type, string $disk = 'public'): string
    {
        try {
            $message = '';
            $logModule = '';
            $fileDirectory = '';

            switch ($type) {
                case FileUploadType::AVATAR:
                    $logModule = 'account-user';
                    $data = User::find($id);

                    if ($file !== $data->avatar && ! empty($file)) {
                        $fileDirectory = $this->processAndSaveImage(
                            $file,
                            $id,
                            'avatars',
                            220,
                            disk: $disk
                        );
                    } else {
                        if (! empty($file)) {
                            $fileDirectory = $file;
                        } else {
                            $fileDirectory = null;
                        }
                    }

                    $data->update([
                        'avatar' => $fileDirectory,
                    ]);

                    $message = 'The avatar has been successfully uploaded.';
                    break;

                case FileUploadType::SIGNATURE:
                    $logModule = 'account-user';
                    $data = User::find($id);

                    if ($file !== $data->avatar && ! empty($file)) {
                        $fileDirectory = $this->processAndSaveImage(
                            $file,
                            $id,
                            'signatures',
                            300,
                            disk: $disk
                        );
                    } else {
                        if (! empty($file)) {
                            $fileDirectory = $file;
                        } else {
                            $fileDirectory = null;
                        }
                    }

                    $data->update([
                        'signature' => $fileDirectory,
                    ]);

                    $message = 'The signature has been successfully uploaded.';
                    break;

                case FileUploadType::LOGO:
                    $logModule = 'company';
                    $data = Company::find($id);

                    if ($file !== $data->company_logo && ! empty($file)) {
                        $fileDirectory = $this->processAndSaveImage(
                            $file,
                            $id,
                            'logo',
                            300,
                            disk: $disk
                        );
                    } else {
                        if (! empty($file)) {
                            $fileDirectory = $file;
                        } else {
                            $fileDirectory = null;
                        }
                    }

                    if ($file !== $data->company_logo && ! empty($file)) {
                        $faviconDirectory = $this->processAndSaveImage(
                            $file,
                            $id,
                            'favicon',
                            16,
                            'ico',
                            disk: $disk
                        );
                    } else {
                        if (! empty($file)) {
                            $faviconDirectory = $file;
                        } else {
                            $faviconDirectory = null;
                        }
                    }

                    $data->update([
                        'company_logo' => $fileDirectory,
                        'favicon' => $faviconDirectory,
                    ]);

                    $message = 'The logo has been successfully uploaded.';
                    break;

                case FileUploadType::LOGIN_BACKGROUND:
                    $logModule = 'company';
                    $data = Company::find($id);

                    if ($file !== $data->login_background && ! empty($file)) {
                        $fileDirectory = $this->processAndSaveImage(
                            $file,
                            $id,
                            'login-background',
                            1920,
                            'jpg',
                            40,
                            disk: $disk
                        );
                    } else {
                        if (! empty($file)) {
                            $fileDirectory = $file;
                        } else {
                            $fileDirectory = null;
                        }
                    }

                    $data->update([
                        'login_background' => $fileDirectory,
                    ]);

                    $message = 'The login background has been successfully uploaded.';
                    break;

                default:
                    throw new \Exception('Invalid file upload type.');
            }

            $this->logRepository->create([
                'message' => $message,
                'log_id' => $id,
                'log_module' => $logModule,
                'data' => $data,
            ]);

            return $fileDirectory;
        } catch (\Throwable $th) {
            throw new \Exception('File upload failed. Please try again.');
        }
    }

    public function get(string $id, FileUploadType $type, string $disk = 'public'): string
    {
        try {
            $data = '';

            switch ($type) {
                case FileUploadType::AVATAR:
                    $user = User::find($id);

                    if (! empty($user) && ! empty($user->avatar)) {
                        $path = Storage::disk($disk)->path($user->avatar);

                        if (file_exists($path)) {
                            $type = mime_content_type($path);
                            $data = base64_encode(file_get_contents($path));
                        }
                    }
                    break;

                case FileUploadType::SIGNATURE:
                    $user = User::find($id);

                    if (! empty($user) && ! empty($user->signature)) {
                        $path = Storage::disk($disk)->path($user->signature);

                        if (file_exists($path)) {
                            $type = mime_content_type($path);
                            $data = base64_encode(file_get_contents($path));
                        }
                    }
                    break;

                case FileUploadType::FAVICON:
                    $company = Company::find($id);

                    if (! empty($company) && ! empty($company->favicon)) {
                        $path = Storage::disk($disk)->path($company->favicon);

                        if (file_exists($path)) {
                            $type = mime_content_type($path);
                            $data = base64_encode(file_get_contents($path));
                        }
                    }
                    break;

                case FileUploadType::LOGO:
                    $company = Company::find($id);

                    if (! empty($company) && ! empty($company->company_logo)) {
                        $path = Storage::disk($disk)->path($company->company_logo);

                        if (file_exists($path)) {
                            $type = mime_content_type($path);
                            $data = base64_encode(file_get_contents($path));
                        }
                    }
                    break;

                case FileUploadType::LOGIN_BACKGROUND:
                    $company = Company::find($id);

                    if (! empty($company) && ! empty($company->login_background)) {
                        $path = Storage::disk($disk)->path($company->login_background);

                        if (file_exists($path)) {
                            $type = mime_content_type($path);
                            $data = base64_encode(file_get_contents($path));
                        }
                    }
                    break;

                default:
                    break;
            }

            if (empty($data)) {
                throw new \Exception('File not found.', 404);
            }

            return "data:$type;base64,$data";
        } catch (\Throwable $th) {
            throw new \Exception('Failed to fetch the file. Please try again.');
        }
    }

    private function processAndSaveImage(
        string $base64Data,
        string $imageName,
        string $imageDirectory = '',
        int $width = 150,
        string $format = 'png',
        int $quality = 20,
        string $disk = 'public'
    ): string {
        // Read and scale the image from base64
        $image = Image::read($base64Data)->scale($width);

        // Construct the filename and relative path
        $filename = "{$imageName}.{$format}";
        $relativePath = ! empty($imageDirectory) ? "{$imageDirectory}/{$filename}" : $filename;
        $storageDirectory = ! empty($imageDirectory) ? "images/{$imageDirectory}" : 'images';

        // Ensure the directory exists in the public disk
        if (! Storage::disk($disk)->exists($storageDirectory)) {
            Storage::disk($disk)->makeDirectory($storageDirectory);
        }

        if ($format === 'ico') {
            $format = 'png';
        }

        // Encode the image with quality and progressive settings
        $encodedImage = $image->encodeByExtension($format, quality: $quality, progressive: true);

        // Save the image to the $disk disk
        Storage::disk($disk)->put("images/{$relativePath}", (string) $encodedImage);

        // Return the relative public path (can be used with asset() or URL::to())
        return "images/{$relativePath}";
    }
}
