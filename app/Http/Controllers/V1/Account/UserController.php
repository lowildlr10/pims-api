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
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Drivers\Gd\Driver;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $search = trim($request->get('search', ''));
        $perPage = $request->get('per_page', 50);
        $columnSort = $request->get('column_sort', 'firstname');
        $sortDirection = $request->get('sort_direction', 'desc');

        $users = User::with([
            'department:id,department_name',
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
                    ->orWhereRelation('department', 'department_name', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('section', 'section_name', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('roles', 'role_name', 'ILIKE', "%{$search}%");
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            $users = $users->orderBy($columnSort, $sortDirection);
        }

        $users = $users->paginate($perPage);

        return response()->json([
            'data' => $users
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'firstname' => 'required|string',
            'middlename' => 'nullable|string',
            'lastname' => 'required|string',
            'sex' => 'required|string|in:male,female',
            'section' => 'required',
            'position' => 'required',
            'designation' => 'nullable',
            'username' => 'required|unique:users',
            'email' => 'email|unique:users|nullable',
            'phone' => 'string|unique:users|max:13|nullable',
            'password' => 'required|min:6',
            'avatar' => 'nullable|string',
            'signature' => 'nullable|string',
            'restricted' => 'required|boolean',
            'allow_signature' => 'required|boolean',
            'roles' => 'required|array'
        ]);

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

            $section = Section::find($validated['section']);

            $user = User::create(array_merge(
                $validated,
                [
                    'position_id' => $position->id,
                    'designation_id' => $designation->id,
                    'department_id' => $section->department_id,
                    'section_id' => $section->id,
                    'avatar' => null,
                    'signature' => null,
                    'password' => bcrypt($request->password)
                ]
            ));

            $user->roles()->sync($validated['roles']);

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
            'department:id,department_name',
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
        $validated = $request->validate([
            'firstname' => 'required|string',
            'middlename' => 'nullable|string',
            'lastname' => 'required|string',
            'sex' => 'required|string|in:male,female',
            'section' => 'required',
            'position' => 'required',
            'designation' => 'nullable',
            'username' => 'required|unique:users,username,' . $user->id,
            'email' => 'email|unique:users,email,' . $user->id . '|nullable',
            'phone' => 'nullable|string|unique:users,phone,' . $user->id . '|max:13',
            'password' => 'nullable|min:6',
            'avatar' => 'nullable|string',
            'signature' => 'nullable|string',
            'restricted' => 'required|boolean',
            'allow_signature' => 'required|boolean',
            'roles' => 'required|array'
        ]);

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

            $section = Section::find($validated['section']);

            $user->roles()->sync($validated['roles']);

            if ($request->avatar !== $user->avatar && !empty($request->avatar)) {
                $avatar = $this->processAndSaveImage($request->avatar, $id);
            } else {
                if (!empty($request->avatar)) {
                    $avatar = $request->avatar;
                } else {
                    $avatar = null;
                }
            }

            if ($request->signature !== $user->signature && !empty($request->signature)) {
                $signature = $this->processAndSaveImage($request->signature, $id);
            } else {
                if (!empty($request->signature)) {
                    $signature = $request->signature;
                } else {
                    $signature = null;
                }
            }

            $user->update(array_merge(
                $validated,
                [
                    'position_id' => $position->id,
                    'designation_id' => $designation->id,
                    'department_id' => $section->department_id,
                    'section_id' => $section->id,
                    'avatar' => $avatar,
                    'signature' => $signature,
                ],
                !empty(trim($request->password))
                    ? ['password' => bcrypt($request->password)]
                    : []
            ));
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'User update failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $request->except('password'),
                'message' => 'User updated successfully.'
            ]
        ]);
    }

    /**
     * Softdelete the specified resource from storage.
     */
    public function delete(User $user): JsonResponse
    {
        if (User::count() === 1) {
            return response()->json([
                'message' => 'Unable to delete user. The system must have at least one user registered.'
            ], 422);
        }

        try {
            $user->delete();
        } catch (\Throwable $th) {
            return response()->json([
                'message' =>
                    $th->getCode() === '23000' ?
                        'Failed to delete category. There are records connected to this record.' :
                        'Unknown error occured. Please try again.',
            ], 422);
        }

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }

    private function processAndSaveImage(string $base64Data, string $imageName, string $imageDirectory = ''): string
    {
        $appUrl = env('APP_URL') ?? 'http://localhost';

        $width = 150;
        $image = Image::read($base64Data)->scale($width);

        $filename = "{$imageName}.png";
        $relativeFileDirectory = !empty($imageDirectory) ? "{$imageDirectory}/{$fileName}" : $fileName;
        $publicDirectory = "public/images/{$imageDirectory}";
        $directory = "{$appUrl}/storage/images/{$relativeFileDirectory}";

        if (!Storage::exists($publicDirectory)) {
            Storage::makeDirectory($publicDirectory);
        }

        $image->encodeByExtension('png', progressive: true, quality: 10)
            ->save(public_path(
                "storage/images/{$relativeFileDirectory}"
            ));

        return $directory;
    }
}
