<?php

namespace DVC\ResponsiveVideoPlayer\FileVariantProvider\SearchStrategy;

use DVC\ResponsiveVideoPlayer\FileVariantProvider\VariantIdentifier;

class TrailingUnderscoreSearchStrategy implements SearchStrategyInterface
{
    public const NAME = 'trailing_underscore_search_strategy';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getSearchPath(string $basePath, string $suffix, string $extension): string
    {
        $directory = dirname($basePath);
        $stem = pathinfo($basePath, PATHINFO_FILENAME);
        $stem = preg_replace('/_(?:desktop|mobil|mobile|thumbnail)$/i', '', $stem);

        return ('.' === $directory ? '' : $directory.'/').$stem.'_'.$suffix.'.'.$extension;
    }

    public static function getSuffixOptions(VariantIdentifier $identifier): array
    {
        return match ($identifier) {
            VariantIdentifier::VideoDesktop => ['desktop'],
            VariantIdentifier::VideoMobile => ['mobil', 'mobile'],
            VariantIdentifier::ImagePoster => ['thumbnail'],
        };

        return [];
    }

    public static function getFileExtensionOptions(VariantIdentifier $identifier): array
    {
        return match ($identifier) {
            VariantIdentifier::VideoDesktop => ['webm', 'mp4'],
            VariantIdentifier::VideoMobile => ['webm', 'mp4'],
            VariantIdentifier::ImagePoster => ['webp', 'jpg', 'png'],
        };

        return [];
    }
}
