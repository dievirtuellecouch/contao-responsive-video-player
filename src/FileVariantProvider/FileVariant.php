<?php

namespace DVC\ResponsiveVideoPlayer\FileVariantProvider;

use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\ExtraMetadata;
use DVC\ResponsiveVideoPlayer\FileVariantProvider\VariantIdentifier;

/**
 * @internal Fix for Contao ≥5.5 where `getExtraMetadata()` - now returns an
 * `ExtraMetadata` value-object instead of a raw array.
 */
class FileVariant
{
    public function __construct(
        private ?FilesystemItem $file = null,
        private VariantIdentifier $identifier,
        private string $searchPath,
    ) {
        $this->prepareFile();
    }

    public function hasFile(): bool
    {
        return $this->file !== null;
    }

    public function getFile(): ?FilesystemItem
    {
        return $this->file;
    }

    public function isVideo(): bool
    {
        return match ($this->identifier) {
            VariantIdentifier::VideoDesktop, VariantIdentifier::VideoMobile => true,
            default => false,
        };
    }

    public function isPosterImage(): bool
    {
        return $this->identifier === VariantIdentifier::ImagePoster;
    }

    /**
     * Adds media-query metadata to the mobile video variant.
     */
    private function prepareFile(): void
    {
        if (!$this->hasFile()) {
            return;
        }

        // Only for the “mobile” source add an additional media query.
        if ($this->identifier === VariantIdentifier::VideoMobile) {
            // Ensure we always have an array when merging.
            $current = $this->file->getExtraMetadata(); // ExtraMetadata object

            // Convert to array, merge, then convert back to ExtraMetadata.
            $merged = array_merge($current->all(), ['media' => '(max-width: 640px)']);

            $this->file = $this->file->withExtraMetadata(new ExtraMetadata($merged));
        }
    }
}
