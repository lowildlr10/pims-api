<?php

namespace App\Http\Controllers\V1\Account;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use App\Models\Position;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Drivers\Gd\Driver;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse | LengthAwarePaginator
    {
        $search = trim($request->get('search', ''));
        $perPage = $request->get('per_page', 50);
        $showAll = filter_var($request->get('show_all', false), FILTER_VALIDATE_BOOLEAN);
        $columnSort = $request->get('column_sort', 'firstname');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        $users = User::with([
            'division:id,division_name',
            'section:id,section_name',
            'position:id,position_name',
            'designation:id,designation_name',
            'roles:id,role_name'
        ]);

        if (!empty($search)) {
            $users = $users->where(function($query) use ($search){
                $query->where('firstname', 'ILIKE', "%{$search}%")
                    ->orWhere('middlename', 'ILIKE', "%{$search}%")
                    ->orWhere('lastname', 'ILIKE', "%{$search}%")
                    ->orWhere('email', 'ILIKE', "%{$search}%")
                    ->orWhere('phone', 'ILIKE', "%{$search}%")
                    ->orWhere('sex', 'ILIKE', "%{$search}%")
                    ->orWhere('username', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('position', 'position_name', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('designation', 'designation_name', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('division', 'division_name', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('section', 'section_name', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('roles', 'role_name', 'ILIKE', "%{$search}%");
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            switch ($columnSort) {
                case 'fullname_formatted':
                    $users = $users->orderBy('firstname', $sortDirection);
                    break;
                case 'division_section':
                    $users = $users->orderBy('division_id', $sortDirection)
                        ->orderBy('section_id', $sortDirection);
                    break;
                case 'position_designation':
                    $users = $users->orderBy('position_id', $sortDirection)
                        ->orderBy('designation_id', $sortDirection);
                    break;
                default:
                    $users = $users->orderBy($columnSort, $sortDirection);
                    break;
            }
        }

        if ($paginated) {
            return $users->paginate($perPage);
        } else {
            if ($showAll) {
                $users = $users->get();
            } else {
                $users = $users->limit($perPage)->get();
            }

            return response()->json([
                'data' => $users
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|string',
            'firstname' => 'required|string',
            'middlename' => 'nullable|string',
            'lastname' => 'required|string',
            'sex' => 'required|string|in:male,female',
            'section_id' => 'required',
            'position' => 'required',
            'designation' => 'nullable',
            'username' => 'required|unique:users',
            'email' => 'email|unique:users|nullable',
            'phone' => 'string|unique:users|max:13|nullable',
            'password' => 'required|min:6',
            'avatar' => 'nullable|string',
            'signature' => 'nullable|string',
            'restricted' => 'required|in:true,false',
            'allow_signature' => 'boolean',
            'roles' => 'required|string'
        ]);
        $restricted = filter_var($validated['restricted'], FILTER_VALIDATE_BOOLEAN);

        try {
            $position = Position::updateOrCreate([
                'position_name' => $validated['position'],
            ], [
                'position_name' => $validated['position']
            ]);

            $designation = Designation::updateOrCreate([
                'designation_name' => $validated['designation'],
            ], [
                'designation_name' => $validated['designation']
            ]);

            $section = Section::find($validated['section_id']);

            $user = User::create(array_merge(
                $validated,
                [
                    'position_id' => $position->id,
                    'designation_id' => $designation->id,
                    'division_id' => $section->division_id,
                    'section_id' => $section->id,
                    'avatar' => null,
                    'signature' => null,
                    'password' => bcrypt($request->password)
                ]
            ));

            $roles = json_decode($validated['roles']);
            $user->roles()->sync($roles);

            if ($request->avatar && !empty($request->avatar)) {
                $avatar = $this->processAndSaveImage($request->avatar, $user->id);
                $user->avatar = $avatar;
            }

            if ($request->signature && !empty($request->signature)) {
                $signature = $this->processAndSaveImage($request->signature, $user->id);
                $user->signature = $signature;
            }

            $user->save();
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'User registration failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $user,
                'message' => 'User registered successfully.'
            ]
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): JsonResponse
    {
        $user = $user->with([
            'division:id,division_name',
            'section:id,section_name',
            'position:id,position_name',
            'designation:id,designation_name',
            'roles:id,role_name'
        ])
        ->find($user->id);

        return response()->json([
            'data' => [
                'data' => $user
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $updateType = $request->get('update_type', 'account-management');

        if ($updateType === 'account-management') {
            new Middleware('ability:super:*,account-user:*,account-user:update');
        }

        switch ($updateType) {
            case 'profile':
                $validated = $request->validate([
                    'firstname' => 'required|string',
                    'middlename' => 'nullable|string',
                    'lastname' => 'required|string',
                    'sex' => 'required|string|in:male,female',
                    'position' => 'required',
                    'designation' => 'nullable',
                    'username' => 'required|unique:users,username,' . $user->id,
                    'email' => 'email|unique:users,email,' . $user->id . '|nullable',
                    'phone' => 'nullable|string|unique:users,phone,' . $user->id . '|max:13',
                    'password' => 'nullable|min:6',
                ]);
                break;

            case 'avatar':
                $validated = $request->validate([
                    'avatar' => 'nullable|string',
                ]);
                break;

            case 'signature':
                $validated = $request->validate([
                    'allow_signature' => 'required|in:true,false',
                    'signature' => 'nullable|string',
                ]);
                $allowSignature = filter_var($validated['allow_signature'], FILTER_VALIDATE_BOOLEAN);
                break;

            default:
                $validated = $request->validate([
                    'employee_id' => 'required|string',
                    'firstname' => 'required|string',
                    'middlename' => 'nullable|string',
                    'lastname' => 'required|string',
                    'sex' => 'required|string|in:male,female',
                    'section_id' => 'required',
                    'position' => 'required',
                    'designation' => 'nullable',
                    'username' => 'required|unique:users,username,' . $user->id,
                    'email' => 'email|unique:users,email,' . $user->id . '|nullable',
                    'phone' => 'nullable|string|unique:users,phone,' . $user->id . '|max:13',
                    'password' => 'nullable|min:6',
                    'restricted' => 'required|in:true,false',
                    'roles' => 'required|string'
                ]);
                $restricted = filter_var($validated['restricted'], FILTER_VALIDATE_BOOLEAN);
                break;
        }

        try {
            if ($updateType === 'account-management' || $updateType === 'profile') {
                $position = Position::updateOrCreate([
                    'position_name' => $validated['position'],
                ], [
                    'position_name' => $validated['position']
                ]);

                $designation = Designation::updateOrCreate([
                    'designation_name' => $validated['designation'],
                ], [
                    'designation_name' => $validated['designation']
                ]);
            }

            if ($updateType === 'account-management') {
                $section = Section::find($validated['section_id']);
                $roles = json_decode($validated['roles']);
                $user->roles()->sync($roles);
            }

            if ($updateType === 'avatar') {
                if ($request->avatar !== $user->avatar && !empty($request->avatar)) {
                    $avatar = $this->processAndSaveImage($request->avatar, $user->id, 'avatars');
                } else {
                    if (!empty($request->avatar)) {
                        $avatar = $request->avatar;
                    } else {
                        $avatar = null;
                    }
                }
            }

            if ($updateType === 'signature') {
                if ($request->signature !== $user->signature && !empty($request->signature)) {
                    $signature = $this->processAndSaveImage($request->signature, $user->id, 'signatures');
                } else {
                    if (!empty($request->signature)) {
                        $signature = $request->signature;
                    } else {
                        $signature = null;
                    }
                }
            }

            switch ($updateType) {
                case 'profile':
                    $password = $validated['password'];
                    unset($validated['password']);
                    $updateData = array_merge(
                        $validated,
                        [
                            'position_id' => $position->id,
                            'designation_id' => $designation->id,
                        ],
                        !empty(trim($password))
                            ? ['password' => bcrypt($password)]
                            : []
                    );
                    break;

                case 'avatar':
                    $updateData = array_merge(
                        $validated,
                        [
                            'avatar' => $avatar,
                        ]
                    );
                    break;

                case 'signature':
                    $updateData = array_merge(
                        $validated,
                        [
                            'signature' => $signature,
                        ]
                    );
                    break;

                default:
                    $password = $validated['password'];
                    unset($validated['password']);
                    $updateData = array_merge(
                        $validated,
                        [
                            'position_id' => $position->id,
                            'designation_id' => $designation->id,
                            'division_id' => $section->division_id,
                            'section_id' => $section->id,
                            'restricted' => $restricted
                        ],
                        !empty(trim($password))
                            ? ['password' => bcrypt($password)]
                            : []
                    );
                    break;
            }

            $user->update($updateData);
        } catch (\Throwable $th) {
            if ($updateType === 'avatar') {
                $errorMessage = 'Avatar update failed. Please try again.';
            } else if ($updateType === 'signature') {
                $errorMessage = 'Signature update failed. Please try again.';
            } else {
                $errorMessage = 'User update failed. Please try again.';
            }

            return response()->json([
                'message' => $errorMessage
            ], 422);
        }

        if ($updateType === 'avatar') {
            $successMessage = 'Avatar updated successfully.';
        } else if ($updateType === 'signature') {
            $successMessage = 'Signature updated successfully.';
        } else {
            $successMessage = 'User updated successfully.';
        }

        return response()->json([
            'data' => [
                'data' => $request->except('password'),
                'message' => $successMessage
            ]
        ]);
    }

    private function processAndSaveImage(string $base64Data, string $imageName, string $imageDirectory = ''): string
    {
        $appUrl = env('APP_URL') ?? 'http://localhost';

        $width = 150;
        $image = Image::read($base64Data)->scale($width);

        $filename = "{$imageName}.png";
        $relativeFileDirectory = !empty($imageDirectory) ? "{$imageDirectory}/{$filename}" : $filename;
        $publicDirectory = "public/images/{$imageDirectory}";
        $directory = "{$appUrl}/storage/images/{$relativeFileDirectory}";

        Storage::delete("{$publicDirectory}/{$filename}");

        if (!Storage::exists($publicDirectory)) {
            Storage::makeDirectory($publicDirectory);
        }

        $image->encodeByExtension('png', progressive: true, quality: 10)
            ->save(public_path(
                "/storage/images/{$relativeFileDirectory}"
            ));

        return $directory;
    }
}
