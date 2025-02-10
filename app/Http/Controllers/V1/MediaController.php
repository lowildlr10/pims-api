<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Designation;
use App\Models\Position;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Drivers\Gd\Driver;

class MediaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // public function index(Request $request): JsonResponse
    // {
    //     $search = trim($request->get('search', ''));
    //     $perPage = $request->get('per_page', 50);
    //     $columnSort = $request->get('column_sort', 'firstname');
    //     $sortDirection = $request->get('sort_direction', 'desc');

    //     $users = User::with([
    //         'division:id,division_name',
    //         'section:id,section_name',
    //         'position:id,position_name',
    //         'designation:id,designation_name',
    //         'roles:id,role_name'
    //     ]);

    //     if (!empty($search)) {
    //         $users = $users->where(function($query) use ($search){
    //             $query->where('firstname', 'ILIKE', "%{$search}%")
    //                 ->orWhere('middlename', 'ILIKE', "%{$search}%")
    //                 ->orWhere('lastname', 'ILIKE', "%{$search}%")
    //                 ->orWhere('email', 'ILIKE', "%{$search}%")
    //                 ->orWhere('phone', 'ILIKE', "%{$search}%")
    //                 ->orWhere('sex', 'ILIKE', "%{$search}%")
    //                 ->orWhere('username', 'ILIKE', "%{$search}%")
    //                 ->orWhereRelation('position', 'position_name', 'ILIKE', "%{$search}%")
    //                 ->orWhereRelation('designation', 'designation_name', 'ILIKE', "%{$search}%")
    //                 ->orWhereRelation('division', 'division_name', 'ILIKE', "%{$search}%")
    //                 ->orWhereRelation('section', 'section_name', 'ILIKE', "%{$search}%")
    //                 ->orWhereRelation('roles', 'role_name', 'ILIKE', "%{$search}%");
    //         });
    //     }

    //     if (in_array($sortDirection, ['asc', 'desc'])) {
    //         $users = $users->orderBy($columnSort, $sortDirection);
    //     }

    //     $users = $users->paginate($perPage);

    //     return response()->json([
    //         'data' => $users
    //     ]);
    // }

    /**
     * Store a newly created resource in storage.
     */
    // public function store(Request $request): JsonResponse
    // {
    //     $validated = $request->validate([
    //         'firstname' => 'required|string',
    //         'middlename' => 'nullable|string',
    //         'lastname' => 'required|string',
    //         'sex' => 'required|string|in:male,female',
    //         'section' => 'required',
    //         'position' => 'required',
    //         'designation' => 'nullable',
    //         'username' => 'required|unique:users',
    //         'email' => 'email|unique:users|nullable',
    //         'phone' => 'string|unique:users|max:13|nullable',
    //         'password' => 'required|min:6',
    //         'avatar' => 'nullable|string',
    //         'signature' => 'nullable|string',
    //         'restricted' => 'required|boolean',
    //         'allow_signature' => 'required|boolean',
    //         'roles' => 'required|array'
    //     ]);

    //     try {
    //         $position = Position::updateOrCreate([
    //             'position_name' => $validated['position'],
    //         ], [
    //             'position_name' => $validated['position']
    //         ]);

    //         $designation = Designation::updateOrCreate([
    //             'designation_name' => $validated['designation'],
    //         ], [
    //             'designation_name' => $validated['designation']
    //         ]);

    //         $section = Section::find($validated['section']);

    //         $user = User::create(array_merge(
    //             $validated,
    //             [
    //                 'position_id' => $position->id,
    //                 'designation_id' => $designation->id,
    //                 'division_id' => $section->division_id,
    //                 'section_id' => $section->id,
    //                 'avatar' => null,
    //                 'signature' => null,
    //                 'password' => bcrypt($request->password)
    //             ]
    //         ));

    //         $user->roles()->sync($validated['roles']);

    //         if ($request->avatar && !empty($request->avatar)) {
    //             $avatar = $this->processAndSaveImage($request->avatar, $user->id);
    //             $user->avatar = $avatar;
    //         }

    //         if ($request->signature && !empty($request->signature)) {
    //             $signature = $this->processAndSaveImage($request->signature, $user->id);
    //             $user->signature = $signature;
    //         }

    //         $user->save();
    //     } catch (\Throwable $th) {
    //         return response()->json([
    //             'message' => 'User registration failed. Please try again.'
    //         ], 422);
    //     }

    //     return response()->json([
    //         'data' => [
    //             'data' => $user,
    //             'message' => 'User registered successfully.'
    //         ]
    //     ]);
    // }

    /**
     * Display the specified resource.
     */
    // public function show(User $user): JsonResponse
    // {
    //     $user = $user->with([
    //         'division:id,division_name',
    //         'section:id,section_name',
    //         'position:id,position_name',
    //         'designation:id,designation_name',
    //         'roles:id,role_name'
    //     ])
    //     ->find($user->id);

    //     return response()->json([
    //         'data' => [
    //             'data' => $user
    //         ]
    //     ]);
    // }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'image' => 'nullable|string',
        ]);

        $updateType = $request->get('update_type', '');
        $data = [];
        $successMessage = 'Success.';


            switch ($updateType) {
                case 'user-avatar':
                    $data = User::find($id);
                    $successMessage = 'Avatar updated successfully.';

                    if ($request->image !== $data->avatar && !empty($request->image)) {
                        $avatar = $this->processAndSaveImage($request->image, $data->id, 'avatars');
                    } else {
                        if (!empty($request->image)) {
                            $avatar = $request->image;
                        } else {
                            $avatar = null;
                        }
                    }

                    $data->update([
                        'avatar' => $avatar
                    ]);
                    break;

                case 'user-signature':
                    $data = User::find($id);
                    $successMessage = 'Signature updated successfully.';

                    if ($request->image !== $data->signature && !empty($request->image)) {
                        $signature = $this->processAndSaveImage($request->image, $data->id, 'signatures', 300);
                    } else {
                        if (!empty($request->image)) {
                            $signature = $request->image;
                        } else {
                            $signature = null;
                        }
                    }

                    $data->update([
                        'signature' => $signature
                    ]);
                    break;

                case 'company-logo':
                    $data = Company::find($id);
                    $successMessage = 'Logo updated successfully.';

                    if ($request->image !== $data->company_logo && !empty($request->image)) {
                        $logo = $this->processAndSaveImage($request->image, $data->id, 'company-logo', 200);
                    } else {
                        if (!empty($request->image)) {
                            $logo = $request->image;
                        } else {
                            $logo = null;
                        }
                    }

                    if ($request->image !== $data->company_logo && !empty($request->image)) {
                        $favicon = $this->processAndSaveImage($request->image, $data->id, 'company-favicon', 16, 'ico');
                    } else {
                        if (!empty($request->image)) {
                            $favicon = $request->image;
                        } else {
                            $favicon = null;
                        }
                    }

                    $data->update([
                        'company_logo' => $logo,
                        'favicon' => $favicon
                    ]);
                    break;

                case 'company-login-background':
                    $data = Company::find($id);
                    $successMessage = 'Login background image updated successfully.';

                    if ($request->image !== $data->login_background && !empty($request->image)) {
                        $backgroundImage = $this->processAndSaveImage(
                            $request->image,
                            $data->id,
                            'company-login-background',
                            1920,
                            'jpg',
                            30
                        );
                    } else {
                        if (!empty($request->image)) {
                            $backgroundImage = $request->image;
                        } else {
                            $backgroundImage = null;
                        }
                    }

                    $data->update([
                        'login_background' => $backgroundImage
                    ]);
                    break;

                default:
                    $successMessage = 'Success.';
                    break;
            }try {
        } catch (\Throwable $th) {
            switch ($updateType) {
                case 'user-avatar':
                    $errorMessage = 'Avatar update failed. Please try again.';
                    break;

                case 'user-signature':
                    $errorMessage = 'Signature update failed. Please try again.';
                    break;

                case 'company-logo':
                    $errorMessage = 'Company logo update failed. Please try again.';
                    break;

                case 'company-login-background':
                    $errorMessage = 'Login background image update failed. Please try again.';
                    break;

                default:
                    $errorMessage = 'Unknown error occured. Please try again.';
                    break;
            }

            return response()->json([
                'message' => $errorMessage
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $data,
                'message' => $successMessage
            ]
        ]);
    }

    /**
     * Softdelete the specified resource from storage.
     */
    // public function delete(User $user): JsonResponse
    // {
    //     if (User::count() === 1) {
    //         return response()->json([
    //             'message' => 'Unable to delete user. The system must have at least one user registered.'
    //         ], 422);
    //     }

    //     try {
    //         $user->delete();
    //     } catch (\Throwable $th) {
    //         return response()->json([
    //             'message' =>
    //                 $th->getCode() === '23000' ?
    //                     'Failed to delete category. There are records connected to this record.' :
    //                     'Unknown error occured. Please try again.',
    //         ], 422);
    //     }

    //     return response()->json([
    //         'message' => 'User deleted successfully',
    //     ]);
    // }

    private function processAndSaveImage(
        string $base64Data, string $imageName, string $imageDirectory = '', $width = 150, $format = 'png', $quality = 10
    ): string
    {
        $appUrl = env('APP_URL') ?? 'http://localhost';
        $image = Image::read($base64Data)->scale($width);

        $filename = "{$imageName}.{$format}";
        $relativeFileDirectory = !empty($imageDirectory) ? "{$imageDirectory}/{$filename}" : $filename;
        $publicDirectory = "public/images/{$imageDirectory}";
        $directory = "{$appUrl}/storage/images/{$relativeFileDirectory}";

        Storage::delete("{$publicDirectory}/{$filename}");

        if (!Storage::exists($publicDirectory)) {
            Storage::makeDirectory($publicDirectory);
        }

        if ($format === 'ico') $format = 'png';

        $image->encodeByExtension($format, progressive: true, quality: $quality)
            ->save(public_path(
                "/storage/images/{$relativeFileDirectory}"
            ));

        return $directory;
    }
}
