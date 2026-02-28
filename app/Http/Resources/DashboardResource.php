<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'active' => $this['active'],
            'pending_approval' => $this['pending_approval'],
            'disapproved' => $this['disapproved'],
            'completed' => $this['completed'],
            'show_pr_workflow' => $this['show_pr_workflow'],
            'pr_workflow' => $this['pr_workflow'],
            'show_po_workflow' => $this['show_po_workflow'],
            'po_workflow' => $this['po_workflow'],
            'show_budget_workflow' => $this['show_budget_workflow'],
            'budget_workflow' => $this['budget_workflow'],
            'show_accounting_workflow' => $this['show_accounting_workflow'],
            'accounting_workflow' => $this['accounting_workflow'],
        ];
    }
}
