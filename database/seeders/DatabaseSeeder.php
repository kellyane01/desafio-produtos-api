<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
        ]);

        $shouldSeedProdutos = filter_var(
            env('PRODUTO_SEED_ON_BOOT', false),
            FILTER_VALIDATE_BOOLEAN
        );

        if ($shouldSeedProdutos) {
            $this->call(ProdutoSeeder::class);
        } elseif ($this->command) {
            $this->command->getOutput()->writeln(
                'Skip ProdutoSeeder (set PRODUTO_SEED_ON_BOOT=true to auto-run).'
            );
        }
    }
}
