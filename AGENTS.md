# AGENTS.md - Developer Guidelines for PIMS API

## Project Overview

This is a Laravel 12 PHP API application (PIMS - Procurement/Inventory Management System) with a Vite-powered frontend. The project uses PHP 8.2+ and follows Laravel conventions.

---

## Build and Lint Commands

### PHP Commands (Backend) - Using Sail

```bash
# Install dependencies
composer install

# Fix code style (Laravel Pint) - RUN THIS ON EVERY CODE CHANGE
./vendor/bin/sail composer fix-code
./vendor/bin/pint

# Check code style without fixing
./vendor/bin/pint --test

# Clear cached files
./vendor/bin/sail artisan optimize:clear
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan route:clear
./vendor/bin/sail artisan view:clear

# Run migrations
./vendor/bin/sail artisan migrate

# Start development server
./vendor/bin/sail artisan serve
# Or just
./vendor/bin/sail up
./vendor/bin/sail down
```

### Frontend Commands

```bash
# Install frontend dependencies
npm install

# Development server (hot reload)
npm run dev

# Production build
npm run build
```

---

## Code Style Guidelines

### General PHP Conventions

- **PHP Version**: PHP 8.2+ (strict typing enabled)
- **Framework**: Laravel 12
- **Code Style Tool**: Laravel Pint (PSR-12 based)
- **ALWAYS run `./vendor/bin/pint` after every code change before committing**

### File Organization

```
app/
├── Console/
├── Exceptions/
├── Http/
│   ├── Controllers/
│   │   └── V1/           # API Version 1
│   │       ├── Account/
│   │       ├── Procurement/
│   │       ├── Inventory/
│   │       ├── Library/
│   │       └── ...
│   ├── Middleware/
│   └── Requests/
├── Models/
├── Enums/                # PHP 8.4 Enums
├── Helpers/              # Static helper classes
├── Contracts/           # Repository interfaces (contracts)
├── Repositories/        # Repository implementations
├── Services/            # Service layer
├── Http/Resources/      # API Resources (Transformers)
└── Notifications/
```

### Naming Conventions

| Element | Convention | Example |
|---------|------------|---------|
| Models | PascalCase, singular | `PurchaseRequest`, `User` |
| Controllers | PascalCase, ends with Controller | `PurchaseRequestController` |
| Services | PascalCase, ends with Service | `PurchaseRequestService` |
| Repository Contract | PascalCase, ends with RepositoryInterface | `PurchaseRequestRepositoryInterface` |
| Repository Implementation | PascalCase, ends with Repository | `PurchaseRequestRepository` |
| API Resource | PascalCase, ends with Resource | `PurchaseRequestResource` |
| Enums | PascalCase, singular | `PurchaseRequestStatus` |
| Tables | snake_case, plural | `purchase_requests` |
| Methods | camelCase | `getData()`, `storeUpdate()` |
| Variables | camelCase | `$purchaseRequest`, `$validated` |
| Constants | SCREAMING_SNAKE_CASE | `API_VERSION_1` |
| Relationships | snake_case | `funding_source()`, `signatory_approval()` |

---

## Architecture Pattern: Service + Repository (Contract) + Resource

### 1. Repository Contract (Interface)

```php
<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PurchaseRequestRepositoryInterface
{
    public function getAll(array $filters): LengthAwarePaginator|Collection;
    public function getById(string $id): ?Model;
    public function create(array $data): Model;
    public function update(string $id, array $data): Model;
    public function delete(string $id): bool;
    public function getModel(): string;
}
```

### 2. Repository Implementation

```php
<?php

namespace App\Repositories;

use App\Contracts\PurchaseRequestRepositoryInterface;
use App\Models\PurchaseRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PurchaseRequestRepository implements PurchaseRequestRepositoryInterface
{
    public function __construct(
        protected PurchaseRequest $model
    ) {}

    public function getModel(): string
    {
        return PurchaseRequest::class;
    }

    public function getAll(array $filters): LengthAwarePaginator|Collection
    {
        $query = $this->model->query();

        // Apply filters...

        $paginated = $filters['paginated'] ?? true;
        $perPage = $filters['per_page'] ?? 50;
        $showAll = $filters['show_all'] ?? false;

        if ($paginated) {
            return $query->paginate($perPage);
        }

        return $showAll ? $query->get() : $query->limit($perPage)->get();
    }

    public function getById(string $id): ?Model
    {
        return $this->model->find($id);
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(string $id, array $data): Model
    {
        $model = $this->model->findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): bool
    {
        return $this->model->findOrFail($id)->delete();
    }
}
```

### 3. Service Layer

```php
<?php

namespace App\Services;

use App\Contracts\PurchaseRequestRepositoryInterface;
use App\Enums\PurchaseRequestStatus;
use App\Helpers\StatusTimestampsHelper;
use App\Models\PurchaseRequest;
use App\Repositories\LogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PurchaseRequestService
{
    public function __construct(
        protected PurchaseRequestRepositoryInterface $repository,
        protected LogRepository $logRepository
    ) {}

    public function getAll(array $filters): LengthAwarePaginator|Collection
    {
        $parsedFilters = $this->parseFilters($filters);

        return $this->repository->getAll($parsedFilters);
    }

    public function getById(string $id): ?PurchaseRequest
    {
        return $this->repository->getById($id);
    }

    public function create(array $data): PurchaseRequest
    {
        $data['pr_no'] = $this->generatePrNumber();
        $data['status'] = PurchaseRequestStatus::DRAFT;
        $data['status_timestamps'] = StatusTimestampsHelper::generate('draft_at', null);

        $purchaseRequest = $this->repository->create($data);

        $this->logRepository->create([
            'message' => 'Purchase request created successfully.',
            'log_id' => $purchaseRequest->id,
            'log_module' => 'pr',
            'data' => $purchaseRequest,
        ]);

        return $purchaseRequest;
    }

    public function update(string $id, array $data): PurchaseRequest
    {
        $purchaseRequest = $this->repository->update($id, $data);

        $this->logRepository->create([
            'message' => 'Purchase request updated successfully.',
            'log_id' => $purchaseRequest->id,
            'log_module' => 'pr',
            'data' => $purchaseRequest,
        ]);

        return $purchaseRequest;
    }

    public function logError(string $message, \Throwable $th, array $data): void
    {
        $this->logRepository->create([
            'message' => $message,
            'details' => $th->getMessage(),
            'log_module' => 'pr',
            'data' => $data,
        ], isError: true);
    }

    protected function parseFilters(array $filters): array
    {
        return [
            'search' => $filters['search'] ?? '',
            'per_page' => $filters['per_page'] ?? 50,
            'grouped' => filter_var($filters['grouped'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'show_all' => filter_var($filters['show_all'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'column_sort' => $filters['column_sort'] ?? 'pr_no',
            'sort_direction' => $filters['sort_direction'] ?? 'desc',
            'paginated' => filter_var($filters['paginated'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ];
    }

    protected function generatePrNumber(): string
    {
        return 'PR-'.date('Ymd-His');
    }
}
```

### 4. Controller with Scribe Annotations

```php
<?php

namespace App\Http\Controllers\V1\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseOrderResource;
use App\Http\Resources\PurchaseRequestResource;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

/**
 * @group Purchase Orders
 * APIs for managing purchase orders
 */
class PurchaseOrderController extends Controller
{
    public function __construct(
        protected PurchaseOrderService $service
    ) {}

    /**
     * List Purchase Orders
     *
     * Retrieve a paginated list of purchase orders grouped by PR.
     *
     * @queryParam search string Search by PR number, PO number, etc.
     * @queryParam per_page int Number of items per page. Default: 50.
     * @queryParam grouped boolean Group results by PR. Default: true.
     * @queryParam has_supplies_only boolean Show only POs with supplies. Default: false.
     * @queryParam show_all boolean Show all results without pagination. Default: false.
     * @queryParam column_sort string Sort field. Default: pr_no.
     * @queryParam sort_direction string Sort direction (asc/desc). Default: desc.
     * @queryParam paginated boolean Return paginated results. Default: true.
     * @queryParam status string Filter by status (comma-separated).
     *
     * @response 200 {
     *   "data": [...],
     *   "links": {...},
     *   "meta": {...}
     * }
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $filters = $request->only([
            'search',
            'per_page',
            'grouped',
            'has_supplies_only',
            'show_all',
            'column_sort',
            'sort_direction',
            'paginated',
            'status',
        ]);

        $grouped = filter_var($filters['grouped'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $paginated = filter_var($filters['paginated'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $user = Auth::user();

        if (! $grouped) {
            $results = $this->service->getAllUngrouped($filters);

            return response()->json([
                'data' => PurchaseOrderResource::collection($results),
            ]);
        }

        $result = $this->service->getAll($filters, $user);

        if ($paginated) {
            return PurchaseRequestResource::collection($result);
        }

        $showAll = filter_var($filters['show_all'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $results = $showAll ? $result->get() : $result->limit($filters['per_page'] ?? 50)->get();

        return response()->json([
            'data' => PurchaseRequestResource::collection($results),
        ]);
    }

    /**
     * Get Purchase Order
     *
     * Display the specified purchase order.
     *
     * @urlParam purchaseOrder string required The purchase order UUID.
     *
     * @response 200 {
     *   "data": {...}
     * }
     * @response 404 {
     *   "message": "Purchase order not found."
     * }
     */
    public function show(string $id): JsonResponse
    {
        $purchaseOrder = $this->service->getById($id);

        if (! $purchaseOrder) {
            return response()->json(['message' => 'Purchase order not found.'], 404);
        }

        return response()->json([
            'data' => new PurchaseOrderResource($purchaseOrder),
        ]);
    }

    /**
     * Update Purchase Order
     *
     * Update the specified purchase order in storage.
     *
     * @urlParam purchaseOrder string required The purchase order UUID.
     *
     * @bodyParam po_date date required The PO date.
     * @bodyParam place_delivery string required The place of delivery.
     * @bodyParam delivery_date date required The delivery date.
     * @bodyParam delivery_term string required The delivery term.
     * @bodyParam payment_term string required The payment term.
     * @bodyParam total_amount_words string required The total amount in words.
     * @bodyParam sig_approval_id string required The approval signatory ID.
     * @bodyParam items array required The PO items.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Purchase order updated successfully."
     * }
     * @response 422 {
     *   "message": "Error message"
     * }
     */
    public function update(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $validated = $request->validate([
            'po_date' => 'required',
            'place_delivery' => 'required',
            'delivery_date' => 'required',
            'delivery_term' => 'required',
            'payment_term' => 'required',
            'total_amount_words' => 'string|required',
            'sig_approval_id' => 'required',
            'items' => 'required|array|min:1',
        ]);

        try {
            $purchaseOrder = $this->service->createOrUpdate($validated, $purchaseOrder);

            return response()->json([
                'data' => new PurchaseOrderResource($purchaseOrder->load('items')),
                'message' => 'Purchase order updated successfully.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Purchase order update failed.', $th, $validated);

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }
}
```

#### Controller with Complex Pagination (grouped + paginated options)

For endpoints that support both grouped/ungrouped and paginated/non-paginated responses:

```php
<?php

namespace App\Http\Controllers\V1\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventorySupplyResource;
use App\Models\InventorySupply;
use App\Services\InventorySupplyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * @group Inventory Supplies
 * APIs for managing inventory supplies
 */
class InventorySupplyController extends Controller
{
    public function __construct(
        protected InventorySupplyService $service
    ) {}

    /**
     * List Inventory Supplies
     *
     * @queryParam search string Search by PO number, supply name, etc.
     * @queryParam per_page int Number of items per page. Default: 10.
     * @queryParam grouped boolean Group results by purchase order. Default: true.
     * @queryParam document_type string Filter by required document type.
     * @queryParam search_by_po boolean Search by purchase order ID. Default: false.
     * @queryParam show_all boolean Show all results without pagination. Default: false.
     * @queryParam column_sort string Sort field. Default: pr_no.
     * @queryParam sort_direction string Sort direction (asc/desc). Default: desc.
     * @queryParam paginated boolean Return paginated results. Default: true.
     *
     * @response 200 {
     *   "data": [...],
     *   "links": {...},
     *   "meta": {...}
     * }
     */
    public function index(Request $request): JsonResponse|LengthAwarePaginator
    {
        $filters = $request->only([
            'search',
            'per_page',
            'grouped',
            'document_type',
            'search_by_po',
            'show_all',
            'column_sort',
            'sort_direction',
            'paginated',
        ]);

        $result = $this->service->getAll($filters);

        if ($result instanceof LengthAwarePaginator) {
            return $result;
        }

        return response()->json([
            'data' => $result,
        ]);
    }
}
```

#### Service with Filter Parsing

```php
<?php

namespace App\Services;

use App\Interfaces\InventorySupplyInterface;
use App\Models\InventorySupply;
use App\Repositories\LogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class InventorySupplyService
{
    public function __construct(
        protected InventorySupplyInterface $repository,
        protected LogRepository $logRepository
    ) {}

    public function getAll(array $filters): LengthAwarePaginator|Collection
    {
        $parsedFilters = $this->parseFilters($filters);

        return $this->repository->getAll($parsedFilters);
    }

    public function getById(string $id): ?InventorySupply
    {
        return $this->repository->getById($id);
    }

    public function update(string $id, array $data): InventorySupply
    {
        $inventorySupply = $this->repository->update($id, $data);

        $this->logRepository->create([
            'message' => 'Inventory supply updated successfully.',
            'log_id' => $inventorySupply->id,
            'log_module' => 'inv-supply',
            'data' => $inventorySupply,
        ]);

        return $inventorySupply;
    }

    public function logError(string $message, \Throwable $th, array $data): void
    {
        $this->logRepository->create([
            'message' => $message,
            'details' => $th->getMessage(),
            'log_module' => 'inv-supply',
            'data' => $data,
        ], isError: true);
    }

    protected function parseFilters(array $filters): array
    {
        return [
            'search' => $filters['search'] ?? '',
            'per_page' => $filters['per_page'] ?? 10,
            'grouped' => filter_var($filters['grouped'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'document_type' => $filters['document_type'] ?? null,
            'search_by_po' => filter_var($filters['search_by_po'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'show_all' => filter_var($filters['show_all'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'column_sort' => $filters['column_sort'] ?? 'pr_no',
            'sort_direction' => $filters['sort_direction'] ?? 'desc',
            'paginated' => filter_var($filters['paginated'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ];
    }
}
```

#### Repository Interface

```php
<?php

namespace App\Interfaces;

use App\Models\InventorySupply;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface InventorySupplyInterface
{
    public function storeUpdate(array $data, ?InventorySupply $inventorySupply = null): InventorySupply;

    public function getAll(array $filters): LengthAwarePaginator|Collection;

    public function getById(string $id): ?InventorySupply;

    public function update(string $id, array $data): InventorySupply;
}
```

#### Repository Implementation with Complex Query

```php
<?php

namespace App\Repositories;

use App\Interfaces\InventorySupplyInterface;
use App\Models\InventorySupply;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class InventorySupplyRepository implements InventorySupplyInterface
{
    public function storeUpdate(array $data, ?InventorySupply $inventorySupply = null): InventorySupply
    {
        if (! empty($inventorySupply)) {
            $inventorySupply->update($data);
        } else {
            $inventorySupply = InventorySupply::updateOrCreate(
                [
                    'purchase_order_id' => $data['purchase_order_id'],
                    'po_item_id' => $data['po_item_id'],
                ],
                $data
            );
        }

        return $inventorySupply;
    }

    public function getAll(array $filters): LengthAwarePaginator|Collection
    {
        $search = $filters['search'] ?? '';
        $perPage = $filters['per_page'] ?? 10;
        $grouped = $filters['grouped'] ?? true;
        $documentType = $filters['document_type'] ?? null;
        $searchByPo = $filters['search_by_po'] ?? false;
        $showAll = $filters['show_all'] ?? false;
        $columnSort = $filters['column_sort'] ?? 'pr_no';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $paginated = $filters['paginated'] ?? true;

        if (! $grouped) {
            $inventorySupplies = InventorySupply::with([
                'unit_issue:id,unit_name',
                'item_classification:id,classification_name',
            ])
                ->when($search, function ($query) use ($search, $searchByPo) {
                    if ($searchByPo) {
                        $query->where(function ($query) use ($search) {
                            $query->whereRaw('CAST(purchase_order_id AS TEXT) = ?', [$search]);
                        });
                    } else {
                        $query->where(function ($query) use ($search) {
                            $query->whereRaw('CAST(id AS TEXT) = ?', [$search])
                                ->orWhereRaw('CAST(purchase_order_id AS TEXT) = ?', [$search])
                                ->orWhere('name', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%");
                        });
                    }
                })
                ->when($documentType, function ($query) use ($documentType) {
                    $query->where('required_document', $documentType);
                })
                ->orderBy('item_sequence');

            $inventorySupplies = $showAll
                ? $inventorySupplies->get()
                : $inventorySupplies->limit($perPage)->get();

            return $inventorySupplies;
        }

        $purchaseOrders = PurchaseOrder::query()
            ->select('id', 'purchase_request_id', 'supplier_id', 'po_no', 'status_timestamps')
            ->with([
                'purchase_request:id,funding_source_id',
                'purchase_request.funding_source:id,title',
                'supplier:id,supplier_name',
                'supplies' => function ($query) {
                    $query->select(
                        'id', 'purchase_order_id', 'created_at', 'name', 'description',
                        'unit_issue_id', 'item_classification_id', 'quantity',
                        'required_document', 'item_sequence'
                    )->orderBy('item_sequence', 'asc');
                },
                'supplies.unit_issue:id,unit_name',
                'supplies.item_classification:id,classification_name',
            ])
            ->has('supplies');

        if (! empty($search)) {
            $purchaseOrders = $purchaseOrders->where(function ($query) use ($search) {
                $query->whereRaw('CAST(id AS TEXT) = ?', [$search])
                    ->orWhere('po_date', 'ILIKE', "%{$search}%")
                    ->orWhere('status', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('supplier', 'supplier_name', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('supplies', function ($query) use ($search) {
                        $query->whereRaw('CAST(id AS TEXT) = ?', [$search])
                            ->orWhere('sku', 'ILIKE', "%{$search}%")
                            ->orWhere('upc', 'ILIKE', "%{$search}%")
                            ->orWhere('name', 'ILIKE', "%{$search}%")
                            ->orWhere('description', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('issuances', function ($query) use ($search) {
                        $query->whereRaw('CAST(id AS TEXT) = ?', [$search]);
                    })
                    ->orWhereRelation('inspection_acceptance_report', function ($query) use ($search) {
                        $query->whereRaw('CAST(id AS TEXT) = ?', [$search]);
                    });
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            switch ($columnSort) {
                case 'po_no':
                    $purchaseOrders = $purchaseOrders->orderByRaw("CAST(REPLACE(po_no, '-', '') AS INTEGER) {$sortDirection}");
                    break;

                case 'pr_no':
                    $purchaseOrders = $purchaseOrders->orderByRaw(
                        "(SELECT pr_no FROM purchase_requests WHERE purchase_requests.id = purchase_orders.purchase_request_id) {$sortDirection}"
                    );
                    break;

                case 'supplier_name':
                    $purchaseOrders = $purchaseOrders->orderBy(
                        Supplier::select('supplier_name')->whereColumn('suppliers.id', 'purchase_orders.supplier_id'),
                        $sortDirection
                    );
                    break;

                default:
                    $purchaseOrders = $purchaseOrders->orderBy($columnSort, $sortDirection);
                    break;
            }
        }

        if ($paginated) {
            return $purchaseOrders->paginate($perPage);
        }

        $purchaseOrders = $showAll
            ? $purchaseOrders->get()
            : $purchaseOrders->limit($perPage)->get();

        return $purchaseOrders;
    }

    public function getById(string $id): ?InventorySupply
    {
        return InventorySupply::find($id);
    }

    public function update(string $id, array $data): InventorySupply
    {
        $inventorySupply = InventorySupply::findOrFail($id);
        $inventorySupply->update($data);

        return $inventorySupply->fresh();
    }
}
```

### 5. API Resource (Transformer)

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pr_no' => $this->pr_no,
            'pr_date' => $this->pr_date,
            'pr_date_formatted' => $this->pr_date?->format('F d, Y'),
            'sai_no' => $this->sai_no,
            'purpose' => $this->purpose,
            'status' => $this->status,
            'status_formatted' => $this->status?->label(),
            'total_estimated_cost' => $this->total_estimated_cost,
            'total_estimated_cost_formatted' => $this->total_estimated_cost_formatted,
            'funding_source' => new FundingSourceResource($this->whenLoaded('funding_source')),
            'department' => new DepartmentResource($this->whenLoaded('department')),
            'items' => PurchaseRequestItemResource::collection($this->whenLoaded('items')),
            'requestor' => new UserResource($this->whenLoaded('requestor')),
            'status_timestamps' => $this->status_timestamps,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
```

### 6. Service Provider Registration

```php
<?php

// In AppServiceProvider or a dedicated RepositoryServiceProvider

use App\Contracts\PurchaseRequestRepositoryInterface;
use App\Repositories\PurchaseRequestRepository;
use App\Services\PurchaseRequestService;

public function register(): void
{
    // Register repositories
    $this->app->bind(
        PurchaseRequestRepositoryInterface::class,
        PurchaseRequestRepository::class
    );

    // Register services
    $this->app->singleton(PurchaseRequestService::class, function ($app) {
        return new PurchaseRequestService(
            $app->make(PurchaseRequestRepositoryInterface::class),
            $app->make(LogRepository::class)
        );
    });
}
```

---

## Import Organization

Order imports alphabetically within groups:

1. PHP built-in classes
2. Composer packages (Laravel, etc.)
3. Application classes

```php
use App\Enums\PurchaseRequestStatus;
use App\Helpers\StatusTimestampsHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseRequestResource;
use App\Models\PurchaseRequest;
use App\Services\PurchaseRequestService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
```

---

## Boolean Query Parameters

URL query parameters are always strings (`"false"`, `"true"`), not actual PHP booleans. Always use `filter_var(..., FILTER_VALIDATE_BOOLEAN)` to convert them properly:

```php
// In Controller
$grouped = filter_var($filters['grouped'] ?? true, FILTER_VALIDATE_BOOLEAN);
$paginated = filter_var($filters['paginated'] ?? true, FILTER_VALIDATE_BOOLEAN);
$showAll = filter_var($filters['show_all'] ?? false, FILTER_VALIDATE_BOOLEAN);

// Or parse all filters in Service
protected function parseFilters(array $filters): array
{
    return [
        'search' => $filters['search'] ?? '',
        'per_page' => $filters['per_page'] ?? 50,
        'grouped' => filter_var($filters['grouped'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'show_all' => filter_var($filters['show_all'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'paginated' => filter_var($filters['paginated'] ?? true, FILTER_VALIDATE_BOOLEAN),
    ];
}
```

---

## Type Hints and Return Types

Always use type hints and return types:

```php
// Controller method
public function index(Request $request): AnonymousResourceCollection
{
    // ...
}

// Service method
public function getAll(array $filters): LengthAwarePaginator
{
    // ...
}

// Model relationship
public function items(): HasMany
{
    return $this->hasMany(PurchaseRequestItem::class);
}
```

---

## Error Handling

Wrap business logic in try-catch blocks and return proper JSON responses:

```php
try {
    $purchaseRequest = $this->service->update($id, $validated);

    return response()->json([
        'data' => new PurchaseRequestResource($purchaseRequest),
        'message' => 'Purchase request updated successfully.',
    ]);
} catch (\Throwable $th) {
    $this->service->logError('Update failed.', $th, $validated);

    return response()->json([
        'message' => 'Update failed. Please try again.',
    ], 422);
}
```

---

## Authorization

Use Laravel's `tokenCan()` for API authorization in Services or Controllers:

```php
$canAccess = in_array(true, [
    $user->tokenCan('super:*'),
    $user->tokenCan('supply:*'),
    $user->tokenCan('pr:approve'),
]);

if (! $canAccess) {
    return response()->json(['message' => 'Unauthorized'], 403);
}
```

---

## Database Operations

- Use Eloquent ORM with relationships
- Use Repository pattern with contracts for data access
- Use Service layer for business logic
- Use transactions for multi-step operations
- Use `$fillable` for mass assignment protection

---

## API Response Format

Use API Resources for consistent responses:

```php
// Success with resource
return response()->json([
    'data' => new PurchaseRequestResource($purchaseRequest),
    'message' => 'Success message.',
]);

// Collection with pagination
return PurchaseRequestResource::collection($purchaseRequests->paginate($perPage))
    ->response()
    ->getData();

// Error response
return response()->json([
    'message' => 'Error message.',
], 422);
```

---

## Logging

Use `LogRepository` for application logging:

```php
$this->logRepository->create([
    'message' => 'Action performed.',
    'log_id' => $purchaseRequest->id,
    'log_module' => 'pr',
    'data' => $purchaseRequest,
], isError: true);
```

---

## Notifications

Use `NotificationRepository` for user notifications:

```php
$this->notificationRepository->notify(NotificationType::PR_APPROVED, [
    'pr' => $purchaseRequest
]);
```

---

## Validation

Use Laravel's Request validation or Form Requests:

```php
$validated = $request->validate([
    'department_id' => 'required',
    'items' => 'required|array|min:1',
    'items.*.quantity' => 'required|integer|min:1',
]);
```

---

## Enum Best Practices

Use PHP 8.4 backed enums with a `label()` method for status/choice fields. This provides human-readable labels for API responses:

```php
<?php

namespace App\Enums;

enum PurchaseRequestStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case APPROVED_CASH_AVAILABLE = 'approved_cash_available';
    case APPROVED = 'approved';
    case DISAPPROVED = 'disapproved';
    case FOR_CANVASSING = 'for_canvassing';
    case FOR_RECANVASSING = 'for_recanvassing';
    case FOR_ABSTRACT = 'for_abstract';
    case PARTIALLY_AWARDED = 'partially_awarded';
    case AWARDED = 'awarded';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING => 'Pending',
            self::APPROVED_CASH_AVAILABLE => 'Approved for Cash Availability',
            self::APPROVED => 'Approved',
            self::DISAPPROVED => 'Disapproved',
            self::FOR_CANVASSING => 'For Canvassing',
            self::FOR_RECANVASSING => 'For Recanvassing',
            self::FOR_ABSTRACT => 'For Abstract',
            self::PARTIALLY_AWARDED => 'Partially Awarded',
            self::AWARDED => 'Awarded',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }
}
```

### Using Enums with Eloquent

Cast the enum attribute in your Model:

```php
<?php

namespace App\Models;

use App\Enums\PurchaseRequestStatus;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequest extends Model
{
    protected function casts(): array
    {
        return [
            'status' => PurchaseRequestStatus::class,
            'status_timestamps' => 'array',
        ];
    }
}
```

### Converting Values

Convert string values to enums using `from()`:

```php
$currentStatus = PurchaseRequestStatus::from($purchaseRequest->status);

// Or use tryFrom() to avoid exceptions
$currentStatus = PurchaseRequestStatus::tryFrom($purchaseRequest->status);
```

### Using in API Resources

```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'status' => $this->status?->value,       // Raw value: 'draft'
        'status_formatted' => $this->status?->label(), // Human-readable: 'Draft'
        // ...
    ];
}
```

---

## Routes

- `routes/api.php` - Main API entry point, loads versioned routes
- `routes/v1/api.php` - V1 API routes (all routes in one file)
- `routes/v2/api.php` - V2 API routes (for future versions)

Use Scribe annotations in controllers for auto-generated API documentation.

---

## Database

- Uses MySQL/PostgreSQL (check `.env`)
- Migrations in `database/migrations/`
- Seeders in `database/seeders/`

---

## Development Workflow

1. Always work on the `dev` branch directly
2. Implement Repository Contract → Repository → Service → Controller → Resource
3. Run `./vendor/bin/pint` after every code change
4. Verify Scribe documentation at `/docs`
5. Commit with descriptive messages

---

## Important Notes

- **ALWAYS run `./vendor/bin/pint` before committing code**
- Use Sail (`./vendor/bin/sail`) for all artisan commands
- Follow the Service + Repository (Contract) + Resource pattern
- Add Scribe annotations (`@group`, `@endpoint`, `@bodyParam`, etc.) to all controllers
- Use API Resources for transforming model data
- Keep controllers thin, put business logic in Services
- **Do not over-engineer** - Keep code simple, readable, and pragmatic. Quality over complexity. Write professional code that is easy to understand and maintain.
