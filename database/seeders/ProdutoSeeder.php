<?php

namespace Database\Seeders;

use App\Models\Produto;
use Illuminate\Database\Seeder;

class ProdutoSeeder extends Seeder
{
    public function run(): void
    {
        $total = (int) env('PRODUTO_SEED_TOTAL', 100000);
        $batchSize = (int) env('PRODUTO_SEED_BATCH', 5000);

        $total = max($total, 0);
        $batchSize = $batchSize > 0 ? $batchSize : 5000;

        $created = 0;

        while ($created < $total) {
            $remaining = $total - $created;
            $currentBatch = (int) min($batchSize, $remaining);

            $normal = (int) floor($currentBatch * 0.9);
            $high = $currentBatch - $normal;

            if ($normal > 0) {
                Produto::factory()->count($normal)->create();
            }

            if ($high > 0) {
                Produto::factory()->grandeVolume()->count($high)->create();
            }

            $created += $currentBatch;

            if ($this->command) {
                $this->command->getOutput()->writeln(sprintf(
                    'Seeded %s/%s produtos...',
                    number_format($created),
                    number_format($total)
                ));
            }
        }
    }
}
