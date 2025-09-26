<?php

namespace Database\Factories;

use App\Models\Produto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Produto>
 */
class ProdutoFactory extends Factory
{
    protected $model = Produto::class;

    public function definition(): array
    {
        return [
            'nome' => sprintf(
                'Produto %s',
                Str::upper($this->faker->bothify('???-#####'))
            ),
            'descricao' => $this->faker->paragraph(),
            'preco' => $this->faker->randomFloat(2, 10, 5000),
            'categoria' => $this->faker->randomElement([
                'Eletronicos',
                'Casa',
                'Esporte',
                'Moda',
                'Livros',
                'Alimentos',
                'Brinquedos',
            ]),
            'estoque' => $this->faker->numberBetween(1, 500),
        ];
    }

    /**
     * Estado para produtos com estoque de grande volume.
     */
    public function grandeVolume(): self
    {
        return $this->state(fn (array $attributes) => [
            'estoque' => $this->faker->numberBetween(1000, 50000),
        ]);
    }
}
