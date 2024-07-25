<?php

namespace DVC\ResponsiveVideoPlayer\FileVariantProvider\SearchStrategy;

use DVC\ResponsiveVideoPlayer\FileVariantProvider\VariantIdentifier;

interface SearchStrategyInterface
{
    public function getName(): string;

    public function getSearchPath(string $basePath, string $suffix, string $extension): string;

    public static function getSuffixOptions(VariantIdentifier $identifier): array;

    public static function getFileExtensionOptions(VariantIdentifier $identifier): array;
}
