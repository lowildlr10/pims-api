<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AbstractQuotation extends Model
{
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'purchase_request_id',
        'bids_awards_committee_id',
        'mode_procurement_id',
        'solicitation_no',
        'solicitation_date',
        'opened_on',
        'abstract_no',
        'bac_action',
        'sig_twg_chairperson_id',
        'sig_twg_member_1_id',
        'sig_twg_member_2_id',
        'sig_chairman_id',
        'sig_vice_chairman_id',
        'sig_member_1_id',
        'sig_member_2_id',
        'sig_member_3_id',
        'status',
        'pending_at',
        'approved_at'
    ];
}
