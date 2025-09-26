<?php

namespace App\Observers;

use App\Jobs\LogModelActivity;
use App\Jobs\SyncProdutoSearchDocument;
use App\Models\Produto;
use Illuminate\Support\Facades\Auth;

class ProdutoObserver
{
    public function created(Produto $produto): void
    {
        $this->dispatchLog($produto, 'create', $produto->getAttributes());
        SyncProdutoSearchDocument::dispatchUpsert($produto);
    }

    public function updated(Produto $produto): void
    {
        if (! $produto->wasChanged()) {
            return;
        }

        $this->dispatchLog($produto, 'update', $produto->getAttributes());
        SyncProdutoSearchDocument::dispatchUpsert($produto);
    }

    public function deleted(Produto $produto): void
    {
        $this->dispatchLog($produto, 'delete', $produto->getOriginal());
        SyncProdutoSearchDocument::dispatchDelete($produto);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function dispatchLog(Produto $produto, string $action, array $attributes): void
    {
        $modelId = $this->resolveModelId($produto);

        if ($modelId === null) {
            return;
        }

        $data = $this->extractAttributes($produto, $attributes);

        LogModelActivity::dispatch(
            action: $action,
            model: class_basename($produto),
            modelId: $modelId,
            data: empty($data) ? null : $data,
            userId: Auth::id(),
        );
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function extractAttributes(Produto $produto, array $attributes): array
    {
        $keys = $this->loggableKeys($produto);

        return array_intersect_key($attributes, array_flip($keys));
    }

    /**
     * @return array<int, string>
     */
    private function loggableKeys(Produto $produto): array
    {
        $base = array_merge([
            $produto->getKeyName(),
        ], $produto->getFillable(), [
            'created_at',
            'updated_at',
            'deleted_at',
        ]);

        return array_values(array_unique(array_filter($base)));
    }

    private function resolveModelId(Produto $produto): ?int
    {
        $keyName = $produto->getKeyName();
        $id = $produto->getAttribute($keyName) ?? $produto->getOriginal($keyName);

        return $id !== null ? (int) $id : null;
    }
}
