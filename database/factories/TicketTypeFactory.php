<?php

namespace Database\Factories;

use App\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketType>
 */
class TicketTypeFactory extends Factory
{
    protected $model = TicketType::class;

    public function definition(): array
    {
        return [
            'name' => [
                'en' => fake()->words(2, true),
                'ar' => 'تذكرة '.fake()->word(),
            ],
            'description' => [
                'en' => fake()->sentence(),
                'ar' => 'وصف التذكرة',
            ],
            'price' => fake()->randomFloat(2, 5, 100),
            'persons_count' => 1,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
