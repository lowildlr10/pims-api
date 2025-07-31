<?php

namespace Database\Factories;

use App\Models\Signatory;
use App\Models\SignatoryDetail;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Signatory>
 */
class SignatoryFactory extends Factory
{
    protected $model = Signatory::class;
    
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'active' => true,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Signatory $signatory) {
            $config = [
                'pr' => ['cash_availability', 'approved_by'],
                'rfq' => ['approval_lce', 'approval_bac'],
                'aoq' => ['twg_chairperson', 'twg_member', 'chairman', 'vice_chairman', 'member'],
                'po' => ['authorized_official'],
                'iar' => ['inspection'],
                'ris' => ['approved_by', 'issued_by'],
                'ics' => ['received_from'],
                'are' => ['received_from'],
            ];

            $documents = collect($config)->keys()->random(rand(1, 2));

            foreach ($documents as $document) {
                foreach ($config[$document] as $type) {
                    SignatoryDetail::create([
                        'id' => (string) Str::uuid(),
                        'signatory_id' => $signatory->id,
                        'document' => $document,
                        'signatory_type' => $type,
                        'position' => $signatory->user->position->position_name ?? 'Unknown Position',
                    ]);
                }
            }
        });
    }
}
