<?php

namespace Database\Factories;

use App\Models\GateCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GateCode>
 */
class GateCodeFactory extends Factory
{
    protected $model = GateCode::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('??####')),
            'valid_for_date' => now()->toDateString(),
            'starts_at' => '08:00:00',
            'ends_at' => '22:00:00',
            'is_active' => true,
        ];
    }
}
