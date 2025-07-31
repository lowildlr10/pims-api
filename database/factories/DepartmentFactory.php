<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;
    
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'department_name' => $this->faker->company(),
            'active' => true,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Department $department) {
            Section::factory()
                ->count(rand(2, 4))
                ->for($department)
                ->create();
        });
    }
}
