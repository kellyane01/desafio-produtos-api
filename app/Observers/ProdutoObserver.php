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
        $this->dispatchLog($produto, 'create');
        SyncProdutoSearchDocument::dispatchUpsert($produto);
    }

    public function updated(Produto $produto): void
    {
        if (! $produto->wasChanged()) {
            return;
        }

        $this->dispatchLog($produto, 'update');
        SyncProdutoSearchDocument::dispatchUpsert($produto);
    }

    public function deleted(Produto $produto): void
    {
        $this->dispatchLog($produto, 'delete');
        SyncProdutoSearchDocument::dispatchDelete($produto);
    }

    private function dispatchLog(Produto $produto, string $action): void
    {
        $modelId = $this->resolveModelId($produto);

        if ($modelId === null) {
            return;
        }

        $payload = $this->buildLogPayload($produto, $action);

        if ($payload === null) {
            return;
        }

        LogModelActivity::dispatch(
            action: $action,
            model: $produto->getMorphClass(),
            modelId: $modelId,
            data: $payload,
            userId: Auth::id(),
        );
    }

    private function buildLogPayload(Produto $produto, string $action): ?array
    {
        $keys = $this->loggableKeys($produto);

        $current = $this->filterAttributes($produto->getAttributes(), $keys);
        $original = $this->filterAttributes($produto->getOriginal(), $keys);

        return match ($action) {
            'create' => empty($current) ? null : ['after' => $current],
            'update' => $this->buildUpdatePayload($produto, $current, $original),
            'delete' => empty($original) ? null : ['before' => $original],
            default => empty($current) ? null : ['after' => $current],
        };
    }

    private function buildUpdatePayload(Produto $produto, array $current, array $original): ?array
    {
        $changedAttributes = array_intersect_key($produto->getChanges(), $current);

        if ($changedAttributes === []) {
            return null;
        }

        $before = [];
        $after = [];
        $diff = [];

        foreach (array_keys($changedAttributes) as $key) {
            $before[$key] = $original[$key] ?? null;
            $after[$key] = $current[$key] ?? null;
            $diff[$key] = [
                'old' => $original[$key] ?? null,
                'new' => $current[$key] ?? null,
            ];
        }

        return [
            'before' => $before,
            'after' => $after,
            'changes' => $diff,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    private function filterAttributes(array $attributes, array $keys): array
    {
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
