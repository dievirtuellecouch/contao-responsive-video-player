<?php

namespace DVC\ResponsiveVideoPlayer\FileVariantProvider;

use DVC\ResponsiveVideoPlayer\FileVariantProvider\FileVariant;
use DVC\ResponsiveVideoPlayer\FileVariantProvider\VariantIdentifier;

class VariantIdentifierFactory
{
    public static function create(): array
    {
        return [
            VariantIdentifier::VideoDesktop,
            VariantIdentifier::VideoMobile,
            VariantIdentifier::ImagePoster,
        ];
    }
}
