<?php

namespace App\Jobs;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Repositories\LogRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Throwable;

use function Laravel\Prompts\error;

class StorePoItems implements ShouldQueue
{
    use Queueable;

    private Collection $items;
    private PurchaseOrder $purchaseOrder;
    private LogRepository $logRepository;

    /**
     * Create a new job instance.
     */
    public function __construct(
        Collection $items,
        PurchaseOrder $purchaseOrder
    ) {
        $this->items = $items;
        $this->purchaseOrder = $purchaseOrder;
        $this->logRepository = new LogRepository;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $totalAmount = 0;

        foreach ($this->items as $item) {
            PurchaseOrderItem::where('purchase_order_id', $this->purchaseOrder->id)
                ->where('pr_item_id', $item->pr_item_id)
                ->delete();

            $aoqItem = PurchaseOrderItem::create([
                'purchase_order_id' => $this->purchaseOrder->id,
                'pr_item_id' => $item->pr_item_id,
                'brand_model' => $item->brand_model,
                'description' => $item->description,
                'unit_cost' => $item->unit_cost,
                'total_cost' => $item->total_cost
            ]);

            $totalAmount += $item->total_cost;
        }

        $this->purchaseOrder->update([
           'total_amount' => round($totalAmount, 2)
        ]);
        $this->purchaseOrder->load('items');

        $this->logRepository->create([
            'message' => 'Items for Purchase Order were created successfully.',
            'details' => count($this->items) . ' items',
            'log_id' => $this->purchaseOrder->id,
            'log_module' => 'po',
            'data' => $this->purchaseOrder->items
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        $this->logRepository->create([
            'message' => 'Failed to create Purchase Order items.',
            'details' => $exception->getMessage(),
            'log_id' => $this->purchaseOrder->id,
            'log_module' => 'po',
            'data' => $this->items
        ], isError: true);
    }
}
