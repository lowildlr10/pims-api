<?php

namespace App\Http\Controllers\V1\Account;

use App\Enums\DocumentPrintType;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use ValueError;

/**
 * @group Users
 * APIs for managing users
 */
class UserController extends Controller
{
    public function __construct(
        protected UserService $service
    ) {}

    /**
     * List Users
     *
     * Retrieve a paginated list of users.
     *
     * @queryParam search string Search by name, email, username, etc.
     * @queryParam per_page int Number of items per page. Default 50.
     * @queryParam show_inactive boolean Show inactive users. Default false.
     * @queryParam column_sort string Sort field. Default firstname.
     * @queryParam sort_direction string Sort direction (asc/desc). Default desc.
     * @queryParam document string Filter by document type (pr).
     *
     * @response 200 {
     *   "data": [...],
     *   "meta": {...}
     * }
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only([
            'search',
            'per_page',
            'show_inactive',
            'column_sort',
            'sort_direction',
        ]);

        $filters['show_inactive'] = filter_var($filters['show_inactive'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $document = $request->get('document', '');

        try {
            $documentEnum = DocumentPrintType::from($document);
        } catch (ValueError $e) {
            $documentEnum = DocumentPrintType::UNDEFINED;
        }

        if ($documentEnum !== DocumentPrintType::UNDEFINED) {
            $hasAccess = $this->service->checkDocumentAccess($document);

            if (! $hasAccess) {
                $filters['search'] = Auth::user()->id;
            }
        }

        $users = $this->service->getAll($filters);

        return UserResource::collection($users);
    }

    /**
     * Create User
     *
     * Create a new user.
     *
     * @bodyParam employee_id string required The employee ID.
     * @bodyParam firstname string required The first name.
     * @bodyParam middlename string optional The middle name.
     * @bodyParam lastname string required The last name.
     * @bodyParam sex string required The sex (male/female).
     * @bodyParam department_id string required The department ID.
     * @bodyParam section_id string optional The section ID.
     * @bodyParam position string required The position name.
     * @bodyParam designation string optional The designation name.
     * @bodyParam username string required The username.
     * @bodyParam email string optional The email.
     * @bodyParam phone string optional The phone number.
     * @bodyParam password string required The password.
     * @bodyParam restricted boolean required Whether the user is restricted.
     * @bodyParam roles array required The role IDs.
     *
     * @response 201 {
     *   "data": {...},
     *   "message": "User registered successfully."
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|string|unique:users,employee_id',
            'firstname' => 'required|string',
            'middlename' => 'nullable|string',
            'lastname' => 'required|string',
            'sex' => 'required|string|in:male,female',
            'department_id' => 'required',
            'section_id' => 'nullable',
            'position' => 'required',
            'designation' => 'nullable',
            'username' => 'required|unique:users',
            'email' => 'email|unique:users|nullable',
            'phone' => 'string|max:13|nullable',
            'password' => 'required|min:6',
            'restricted' => 'required|boolean',
            'roles' => 'required|array',
        ]);

        try {
            $user = $this->service->create($validated);

            return response()->json([
                'data' => new UserResource($user),
                'message' => 'User registered successfully.',
            ], 201);
        } catch (\Throwable $th) {
            $this->service->logError('User registration failed.', $th, $validated);

            return response()->json([
                'message' => 'User registration failed. Please try again.',
            ], 422);
        }
    }

    /**
     * Show User
     *
     * Get a specific user by ID.
     *
     * @urlParam id string required The user UUID.
     *
     * @response 200 {
     *   "data": {...}
     * }
     */
    public function show(string $id): JsonResponse
    {
        $user = $this->service->getById($id);

        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Update User
     *
     * Update an existing user.
     *
     * @urlParam id string required The user UUID.
     *
     * @bodyParam update_type string The update type (account-management, profile, allow_signature).
     * @bodyParam employee_id string required The employee ID.
     * @bodyParam firstname string required The first name.
     * @bodyParam lastname string required The last name.
     * @bodyParam sex string required The sex.
     * @bodyParam position string required The position name.
     * @bodyParam designation string optional The designation name.
     * @bodyParam username string required The username.
     * @bodyParam email string optional The email.
     * @bodyParam password string optional The password.
     * @bodyParam restricted boolean The restricted status.
     * @bodyParam roles array The role IDs.
     * @bodyParam allow_signature boolean For allow_signature update type.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "User updated successfully."
     * }
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $updateType = $request->get('update_type', 'account-management');

        switch ($updateType) {
            case 'profile':
                $validated = $request->validate([
                    'firstname' => 'required|string',
                    'middlename' => 'nullable|string',
                    'lastname' => 'required|string',
                    'sex' => 'required|string|in:male,female',
                    'position' => 'required',
                    'designation' => 'nullable',
                    'username' => 'required|unique:users,username,'.$id,
                    'email' => 'email|unique:users,email,'.$id.'|nullable',
                    'phone' => 'nullable|string|max:13',
                    'password' => 'nullable|min:6',
                ]);
                break;

            case 'allow_signature':
                $validated = $request->validate([
                    'allow_signature' => 'required|boolean',
                ]);
                break;

            default:
                $validated = $request->validate([
                    'employee_id' => 'required|string|unique:users,employee_id,'.$id,
                    'firstname' => 'required|string',
                    'middlename' => 'nullable|string',
                    'lastname' => 'required|string',
                    'sex' => 'required|string|in:male,female',
                    'department_id' => 'required',
                    'section_id' => 'nullable',
                    'position' => 'required',
                    'designation' => 'nullable',
                    'username' => 'required|unique:users,username,'.$id,
                    'email' => 'email|unique:users,email,'.$id.'|nullable',
                    'phone' => 'nullable|string|max:13',
                    'password' => 'nullable|min:6',
                    'restricted' => 'required|boolean',
                    'roles' => 'required|array',
                ]);
                break;
        }

        try {
            $user = $this->service->update($id, array_merge($validated, ['update_type' => $updateType]));

            $successMessage = match ($updateType) {
                'allow_signature' => 'Signature allowed successfully.',
                default => 'User updated successfully.',
            };

            return response()->json([
                'data' => new UserResource($user),
                'message' => $successMessage,
            ]);
        } catch (\Throwable $th) {
            $errorMessage = match ($updateType) {
                'allow_signature' => 'Failed to allow signature. Please try again.',
                default => 'User update failed. Please try again.',
            };

            $this->service->logError($errorMessage, $th, $validated);

            return response()->json([
                'message' => $errorMessage,
            ], 422);
        }
    }
}
