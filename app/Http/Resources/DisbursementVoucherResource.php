<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DisbursementVoucherResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_request_id' => $this->purchase_request_id,
            'purchase_order_id' => $this->purchase_order_id,
            'obligation_request_id' => $this->obligation_request_id,
            'dv_no' => $this->dv_no,
            'mode_payment' => $this->mode_payment,
            'payee_id' => $this->payee_id,
            'address' => $this->address,
            'office' => $this->office,
            'responsibility_center_id' => $this->responsibility_center_id,
            'explanation' => $this->explanation,
            'total_amount' => $this->total_amount,
            'total_amount_formatted' => number_format($this->total_amount, 2),
            'accountant_certified_choices' => $this->accountant_certified_choices,
            'sig_accountant_id' => $this->sig_accountant_id,
            'accountant_signed_date' => $this->accountant_signed_date,
            'sig_treasurer_id' => $this->sig_treasurer_id,
            'treasurer_signed_date' => $this->treasurer_signed_date,
            'sig_head_id' => $this->sig_head_id,
            'head_signed_date' => $this->head_signed_date,
            'check_no' => $this->check_no,
            'bank_name' => $this->bank_name,
            'check_date' => $this->check_date,
            'received_name' => $this->received_name,
            'received_date' => $this->received_date,
            'or_other_document' => $this->or_other_document,
            'jev_no' => $this->jev_no,
            'jev_date' => $this->jev_date,
            'disapproved_reason' => $this->disapproved_reason,
            'status' => $this->status?->value,
            'status_formatted' => $this->status?->label(),
            'status_timestamps' => $this->status_timestamps,
            'payee' => new SupplierResource($this->whenLoaded('payee')),
            'responsibility_center' => new ResponsibilityCenterResource($this->whenLoaded('responsibility_center')),
            'purchase_order' => new PurchaseOrderResource($this->whenLoaded('purchase_order')),
            'obligation_request' => $this->whenLoaded('obligation_request', fn () => [
                'id' => $this->obligation_request->id,
                'obr_no' => $this->obligation_request->obr_no,
            ]),
            'signatory_accountant' => new SignatoryResource($this->whenLoaded('signatory_accountant')),
            'signatory_treasurer' => new SignatoryResource($this->whenLoaded('signatory_treasurer')),
            'signatory_head' => new SignatoryResource($this->whenLoaded('signatory_head')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
