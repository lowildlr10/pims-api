<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'approved_at',
        'awarded_at'
    ];

    /**
     * The abstract of quotation that has one bids awards committee.
     */
    public function bids_awards_committee(): HasOne
    {
        return $this->hasOne(BidsAwardsCommittee::class, 'id', 'bids_awards_committee_id');
    }

    /**
     * The abstract of quotation that has one mode procurement.
     */
    public function mode_procurement(): HasOne
    {
        return $this->hasOne(ProcurementMode::class, 'id', 'mode_procurement_id');
    }

    /**
     * The abstract of quotation that has many items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(AbstractQuotationItem::class);
    }

    /**
     * The abstract of quotation that has one TWG chairperson signatory.
     */
    public function signatory_twg_chairperson(): HasOne
    {
        return $this->hasOne(Signatory::class, 'id', 'sig_twg_chairperson_id');
    }

    /**
     * The abstract of quotation that has one 1st TWG member signatory.
     */
    public function signatory_twg_member_1(): HasOne
    {
        return $this->hasOne(Signatory::class, 'id', 'sig_twg_member_1_id');
    }

    /**
     * The abstract of quotation that has one 2nd TWG member signatory.
     */
    public function signatory_twg_member_2(): HasOne
    {
        return $this->hasOne(Signatory::class, 'id', 'sig_twg_member_2_id');
    }

    /**
     * The abstract of quotation that has one chairman signatory.
     */
    public function signatory_chairman(): HasOne
    {
        return $this->hasOne(Signatory::class, 'id', 'sig_chairman_id');
    }

    /**
     * The abstract of quotation that has one vice chairman signatory.
     */
    public function signatory_vice_chairman(): HasOne
    {
        return $this->hasOne(Signatory::class, 'id', 'sig_vice_chairman_id');
    }

    /**
     * The abstract of quotation that has one 1st member signatory.
     */
    public function signatory_member_1(): HasOne
    {
        return $this->hasOne(Signatory::class, 'id', 'sig_member_1_id');
    }

    /**
     * The abstract of quotation that has one 2nd member signatory.
     */
    public function signatory_member_2(): HasOne
    {
        return $this->hasOne(Signatory::class, 'id', 'sig_member_2_id');
    }

    /**
     * The abstract of quotation that has one 3rd member signatory.
     */
    public function signatory_member_3(): HasOne
    {
        return $this->hasOne(Signatory::class, 'id', 'sig_member_3_id');
    }

    /**
     * The abstract of quoation that belongs to purchase request.
     */
    public function purchase_request(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }
}
