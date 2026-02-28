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
        $columnSort = $filters['column_sort'] ?? 'po_no';
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
                : $inventorySupplies = $inventorySupplies->limit($perPage)->get();

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

                case 'funding_source_title':
                    break;

                case 'supplier_name':
                    $purchaseOrders = $purchaseOrders->orderBy(
                        Supplier::select('supplier_name')->whereColumn('suppliers.id', 'purchase_orders.supplier_id'),
                        $sortDirection
                    );
                    break;

                case 'delivery_date_formatted':
                    $purchaseOrders = $purchaseOrders->orderBy('delivery_date', $sortDirection);
                    break;

                default:
                    $purchaseOrders = $purchaseOrders->orderBy($columnSort, $sortDirection);
                    break;
            }
        }

        if ($paginated) {
            return $purchaseOrders->paginate($perPage);
        } else {
            $purchaseOrders = $showAll
                ? $purchaseOrders->get()
                : $purchaseOrders = $purchaseOrders->limit($perPage)->get();

            return $purchaseOrders;
        }
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
