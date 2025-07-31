<?php

namespace Database\Factories;

use App\Models\Signatory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SignatoryDetail>
 */
class SignatoryDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
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

        $document = $this->faker->randomElement(array_keys($config));
        $type = $this->faker->randomElement($config[$document]);

        return [
            'id' => (string) Str::uuid(),
            'signatory_id' => Signatory::factory(),
            'document' => $document,
            'signatory_type' => $type,
            'position' => $this->faker->jobTitle,
        ];
    }
}
