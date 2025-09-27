<?php

namespace App\Jobs;

use App\Models\Produto;
use App\Search\ProdutoIndexer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncProdutoSearchDocument implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public const OPERATION_UPSERT = 'upsert';

    public const OPERATION_DELETE = 'delete';

    public int $tries = 3;

    public function __construct(
        private readonly int $produtoId,
        private readonly string $operation = self::OPERATION_UPSERT,
    ) {
        $this->afterCommit = true;
        $this->onQueue('search-sync');
    }

    public static function dispatchUpsert(Produto $produto): void
    {
        self::dispatch($produto->getKey(), self::OPERATION_UPSERT);
    }

    public static function dispatchDelete(Produto $produto): void
    {
        self::dispatch($produto->getKey(), self::OPERATION_DELETE);
    }

    public function handle(ProdutoIndexer $indexer): void
    {
        if ($this->operation === self::OPERATION_DELETE) {
            $indexer->delete($this->produtoId);

            return;
        }

        $indexer->indexById($this->produtoId);
    }
}
