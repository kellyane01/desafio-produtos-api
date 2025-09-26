<?php

namespace App\Search;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProdutoSearchResult
{
    /**
     * @param array<int, string> $suggestions
     * @param array<int, array<string, mixed>> $highlights
     */
    public function __construct(
        private readonly LengthAwarePaginator $paginator,
        private readonly array $suggestions = [],
        private readonly array $highlights = [],
        private readonly bool $usingElasticsearch = false,
        private readonly ?float $maxScore = null,
    ) {
    }

    public function paginator(): LengthAwarePaginator
    {
        return $this->paginator;
    }

    /**
     * @return array<int, string>
     */
    public function suggestions(): array
    {
        return $this->suggestions;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function highlights(): array
    {
        return $this->highlights;
    }

    public function usingElasticsearch(): bool
    {
        return $this->usingElasticsearch;
    }

    public function maxScore(): ?float
    {
        return $this->maxScore;
    }

    public static function wrap(LengthAwarePaginator $paginator): self
    {
        return new self($paginator);
    }
}
