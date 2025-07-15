<?php

namespace App\Jobs;

use App\Models\AbstractQuotation;
use App\Models\AbstractQuotationDetail;
use App\Models\AbstractQuotationItem;
use App\Repositories\LogRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Throwable;

class StoreAbstractItems implements ShouldQueue
{
    use Queueable;

    private Collection $items;

    private AbstractQuotation $abstractQuotation;

    private LogRepository $logRepository;

    /**
     * Create a new job instance.
     */
    public function __construct(
        Collection $items,
        AbstractQuotation $abstractQuotation
    ) {
        $this->items = $items;
        $this->abstractQuotation = $abstractQuotation;
        $this->logRepository = new LogRepository;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach ($this->items as $item) {
            AbstractQuotationItem::where('abstract_quotation_id', $this->abstractQuotation->id)
                ->where('pr_item_id', $item['pr_item_id'])
                ->delete();

            $aoqItem = AbstractQuotationItem::create([
                'abstract_quotation_id' => $this->abstractQuotation->id,
                'pr_item_id' => $item['pr_item_id'],
                'awardee_id' => isset($item['awardee_id']) && ! empty($item['awardee_id']) ? $item['awardee_id'] : null,
                'document_type' => isset($item['document_type']) && ! empty($item['document_type'])
                    ? $item['document_type'] : null,
                'included' => $item['included'],
            ]);

            foreach ($item['details'] ?? [] as $detail) {
                $quantity = intval($detail['quantity']);
                $unitCost = floatval($detail['unit_cost']);
                $totalCost = round($quantity * $unitCost, 2);

                AbstractQuotationDetail::create([
                    'abstract_quotation_id' => $this->abstractQuotation->id,
                    'aoq_item_id' => $aoqItem->id,
                    'supplier_id' => $detail['supplier_id'],
                    'brand_model' => $detail['brand_model'],
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                ]);
            }
        }

        $this->abstractQuotation->load([
            'items', 'items.details',
        ]);

        $this->logRepository->create([
            'message' => 'Items for Abstract of Quotation were created successfully.',
            'details' => count($this->items).' items',
            'log_id' => $this->abstractQuotation->id,
            'log_module' => 'aoq',
            'data' => $this->abstractQuotation->items,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        $this->logRepository->create([
            'message' => 'Failed to create Abstract of Quotation items.',
            'details' => $exception->getMessage(),
            'log_id' => $this->abstractQuotation->id,
            'log_module' => 'aoq',
            'data' => $this->items,
        ], isError: true);
    }
}
