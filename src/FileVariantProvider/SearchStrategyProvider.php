<?php

namespace DVC\ResponsiveVideoPlayer\FileVariantProvider;

use DVC\ResponsiveVideoPlayer\FileVariantProvider\SearchStrategy\SearchStrategyInterface;

class SearchStrategyProvider
{
    private array $strategies = [];

    public function addSearchStrategy(SearchStrategyInterface $searchStrategy): void
    {
        $this->strategies[] = $searchStrategy;
    }

    public function getAll(): array
    {
        return $this->strategies;
    }

    public function getFirst(): ?SearchStrategyInterface
    {
        return \array_values($this->strategies)[0] ?? null;
    }

    public function hasStrategies(): bool
    {
        return !empty($this->getAll());
    }
}
