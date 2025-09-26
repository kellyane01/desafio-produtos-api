<?php

namespace Database\Factories;

use App\Models\Log;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Log>
 */
class LogFactory extends Factory
{
    protected $model = Log::class;

    public function definition(): array
    {
        return [
            'action' => $this->faker->randomElement(['create', 'update', 'delete']),
            'model' => $this->faker->randomElement(['Produto', 'Pedido']),
            'model_id' => $this->faker->numberBetween(1, 5000),
            'data' => [
                'before' => $this->faker->words(2, true),
                'after' => $this->faker->words(2, true),
            ],
            'user_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
