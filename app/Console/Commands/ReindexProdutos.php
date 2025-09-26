<?php

namespace App\Console\Commands;

use App\Models\Produto;
use App\Search\ProdutoIndexer;
use Illuminate\Console\Command;

class ReindexProdutos extends Command
{
    protected $signature = 'produto:search:reindex {--fresh : Remove e recria o índice antes de reindexar} {--chunk=500 : Quantidade de registros processados por vez}';

    protected $description = 'Reindexa todos os produtos no Elasticsearch';

    public function handle(ProdutoIndexer $indexer): int
    {
        $chunkSize = (int) $this->option('chunk');
        if ($chunkSize <= 0) {
            $chunkSize = 500;
        }

        if ($this->option('fresh')) {
            $this->comment('Recriando índice do Elasticsearch...');
            $indexer->recreateIndex();
        } else {
            $indexer->ensureIndex();
        }

        $total = Produto::query()->count();

        if ($total === 0) {
            $this->info('Nenhum produto para indexar.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        Produto::query()
            ->orderBy('id')
            ->chunk($chunkSize, function ($produtos) use ($indexer, $bar) {
                /** @var \Illuminate\Database\Eloquent\Collection<int, Produto> $produtos */
                $indexer->bulk($produtos);
                $bar->advance($produtos->count());
            });

        $bar->finish();
        $this->newLine(2);
        $this->info('Indexação concluída com sucesso.');

        return self::SUCCESS;
    }
}
