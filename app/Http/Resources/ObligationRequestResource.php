<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ObligationRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_request_id' => $this->purchase_request_id,
            'purchase_order_id' => $this->purchase_order_id,
            'obr_no' => $this->obr_no,
            'office' => $this->office,
            'address' => $this->address,
            'particulars' => $this->particulars,
            'total_amount' => $this->total_amount,
            'total_amount_formatted' => number_format($this->total_amount, 2),
            'funding' => $this->funding,
            'compliance_status' => $this->compliance_status,
            'sig_head_id' => $this->sig_head_id,
            'head_signed_date' => $this->head_signed_date,
            'sig_budget_id' => $this->sig_budget_id,
            'budget_signed_date' => $this->budget_signed_date,
            'disapproved_reason' => $this->disapproved_reason,
            'status' => $this->status?->value,
            'status_formatted' => $this->status?->label(),
            'status_timestamps' => $this->status_timestamps,
            'payee' => new SupplierResource($this->whenLoaded('payee')),
            'responsibility_center_id' => $this->responsibility_center_id,
            'responsibility_center' => new ResponsibilityCenterResource($this->whenLoaded('responsibility_center')),
            'purchase_order' => new PurchaseOrderResource($this->whenLoaded('purchase_order')),
            'signatory_budget' => new SignatoryResource($this->whenLoaded('signatory_budget')),
            'signatory_head' => new SignatoryResource($this->whenLoaded('signatory_head')),
            'fpps' => $this->whenLoaded('fpps', fn () => $this->fpps->map(fn ($fpp) => [
                'id' => $fpp->id,
                'fpp_id' => $fpp->fpp_id,
                'fpp' => $fpp->fpp ? [
                    'id' => $fpp->fpp->id,
                    'code' => $fpp->fpp->code,
                    'description' => $fpp->fpp->description,
                ] : null,
            ])),
            'accounts' => $this->whenLoaded('accounts', fn () => $this->accounts->map(fn ($account) => [
                'id' => $account->id,
                'account_id' => $account->account_id,
                'item_sequence' => $account->item_sequence,
                'amount' => $account->amount,
                'account' => $account->account ? [
                    'id' => $account->account->id,
                    'code' => $account->account->code,
                    'name' => $account->account->name,
                ] : null,
            ])),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
