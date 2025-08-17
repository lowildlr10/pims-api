<?php

namespace Database\Factories;

use App\Models\{
    Department,
    Section,
    FundingSource,
    User,
    SignatoryDetail,
    UnitIssue,
    PurchaseRequest,
    PurchaseRequestItem
};
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseRequest>
 */
class PurchaseRequestFactory extends Factory
{
    protected $model = PurchaseRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get valid department-section pairs
        $department = Department::inRandomOrder()->first();
        $section = Section::where('department_id', $department->id)->inRandomOrder()->first();

        // Get signatories with 'pr' document and the right types
        $cashAvailability = SignatoryDetail::where('document', 'pr')
            ->where('signatory_type', 'cash_availability')
            ->inRandomOrder()
            ->first()?->signatory;
        $approvedBy = SignatoryDetail::where('document', 'pr')
            ->where('signatory_type', 'approved_by')
            ->inRandomOrder()
            ->first()?->signatory;

        $now = now();
        
        $year = $this->faker->numberBetween(2020, now()->year);
        $month = str_pad($this->faker->numberBetween(1, 12), 2, '0', STR_PAD_LEFT);
        $day = $this->faker->numberBetween(1, cal_days_in_month(CAL_GREGORIAN, $month, $year));

        // Simulate a sequence tracker per year-month during this factory execution
        static $sequenceTracker = [];

        $key = "{$year}-{$month}";
        if (!isset($sequenceTracker[$key])) {
            $sequenceTracker[$key] = 1;
        } else {
            $sequenceTracker[$key]++;
        }

        $sequence = $sequenceTracker[$key];
        $pr_no = "{$year}-{$sequence}-{$month}";
        $prDate = now()->setDate($year, $month, $day);

        $data = [
            'id' => Str::uuid(),
            'department_id' => $department->id,
            'section_id' => $section?->id,
            'pr_no' => $pr_no,
            'pr_date' => $prDate,
            'sai_no' => $this->faker->optional()->bothify('SAI-###??'),
            'sai_date' => $this->faker->optional()->date(),
            'alobs_no' => $this->faker->optional()->bothify('ALO-###??'),
            'alobs_date' => $this->faker->optional()->date(),
            'purpose' => $this->faker->sentence(),
            'funding_source_id' => FundingSource::inRandomOrder()->first()?->id,
            'requested_by_id' => User::inRandomOrder()->first()?->id,
            'sig_cash_availability_id' => $cashAvailability?->id,
            'sig_approved_by_id' => $approvedBy?->id,
            'rfq_batch' => 1,
            'total_estimated_cost' => 0,
            'status' => 'draft',
            'status_timestamps' => [
                'draft' => $now->toDateTimeString(),
            ],
        ];

        return $data;
    }

    public function configure(): static
    {
        return $this->afterCreating(function (PurchaseRequest $pr) {
            $itemCount = rand(2, 10);
            $totalCost = 0;

            for ($i = 0; $i < $itemCount; $i++) {
                $quantity = rand(1, 100);
                $unitCost = $this->faker->randomFloat(2, 10, 5000);
                $estimatedCost = $quantity * $unitCost;

                PurchaseRequestItem::create([
                    'id' => Str::uuid(),
                    'purchase_request_id' => $pr->id,
                    'item_sequence' => $i,
                    'quantity' => $quantity,
                    'unit_issue_id' => UnitIssue::inRandomOrder()->first()?->id,
                    'description' => $this->faker->sentence(),
                    'stock_no' => $i + 1,
                    'estimated_unit_cost' => $unitCost,
                    'estimated_cost' => $estimatedCost
                ]);

                $totalCost += $estimatedCost;
            }

            $pr->update([
                'total_estimated_cost' => $totalCost,
            ]);
        });
    }
}
