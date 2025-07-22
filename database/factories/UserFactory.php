<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Position;
use App\Models\Role;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sex = $this->faker->randomElement(['male', 'female']);

        $department = Department::inRandomOrder()->first() ?? Department::factory()->create();
        $section = Section::where('department_id', $department->id)->inRandomOrder()->first() ?? Section::factory()->for($department)->create();
        $position = Position::inRandomOrder()->first() ?? Position::factory()->create();
        $designation = Designation::inRandomOrder()->first();

        return [
            'id' => (string) Str::uuid(),
            'employee_id' => strtoupper('EMP-' . Str::random(6)),
            'firstname' => $this->faker->firstName($sex),
            'middlename' => $this->faker->optional()->lastName(),
            'lastname' => $this->faker->lastName(),
            'sex' => $sex,
            'department_id' => $department->id,
            'section_id' => $section->id,
            'position_id' => $position->id,
            'designation_id' => $designation?->id,
            'username' => $this->faker->unique()->userName,
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->unique()->numerify('09#########'),
            'password' => Hash::make('passwd123'), // Default password
            'avatar' => null,
            'allow_signature' => $this->faker->boolean(30),
            'signature' => null,
            'restricted' => false,
            'remember_token' => Str::random(10),
        ];
    }
}
