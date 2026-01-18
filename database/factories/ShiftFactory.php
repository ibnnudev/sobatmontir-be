<?php

namespace Database\Factories;

use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ShiftFactory extends Factory
{
    protected $model = Shift::class;

    public function definition(): array
    {
        return [
            'workshop_id' => Str::uuid(),
            'cashier_id' => Str::uuid(),
            'opening_cash' => $this->faker->numberBetween(10000, 100000),
            'total_sales' => 0,
            'status' => Shift::STATUS_OPEN,
            'opened_at' => now(),
            'cash_in' => 0,
        ];
    }
}
