<?php

namespace DVC\ResponsiveVideoPlayer\FileVariantProvider;

use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\FilesystemItemIterator;
use Contao\CoreBundle\Filesystem\ExtraMetadata;

/**
 * Fix: compatible with Contao ≥5.5 where getExtraMetadata() returns an
 * ExtraMetadata object instead of a raw array.
 */
class FileVariantCollection
{
    public function __construct(
        private array $items = [],
    ) {
    }

    public function add(FileVariant $fileVariant): void
    {
        $this->items[] = $fileVariant;
    }

    public function getPosterImage(): ?FilesystemItem
    {
        foreach ($this->items as $fileVariant) {
            if (!$fileVariant->hasFile()) {
                continue;
            }

            if (!$fileVariant->isPosterImage()) {
                continue;
            }

            $file = $fileVariant->getFile();

            // Only accept actual image files as poster
            if ($file && str_starts_with($file->getMimeType(''), 'image/')) {
                return $file;
            }
        }

        return null;
    }

    public function getAllVideos(): FilesystemItemIterator
    {
        $videoVariantWithFile = \array_filter(
            $this->items,
            static fn (FileVariant $variant) => $variant->isVideo() && $variant->hasFile()
        );

        $items = \array_map(static fn (FileVariant $variant) => $variant->getFile(), $videoVariantWithFile);

        // Deduplicate by storage path. Prefer the variant WITHOUT a media query
        // so we always have a desktop/fallback source if paths collide.
        $unique = [];
        foreach ($items as $item) {
            if (!$item) {
                continue;
            }

            $path = $item->getPath();
            $hasMedia = null !== $item->getExtraMetadata()->get('media');

            if (!isset($unique[$path])) {
                $unique[$path] = $item;
                continue;
            }

            // If existing entry has a media query but the new one has none, replace it
            $existingHasMedia = null !== $unique[$path]->getExtraMetadata()->get('media');
            if ($existingHasMedia && !$hasMedia) {
                $unique[$path] = $item;
            }
            // otherwise keep existing
        }

        $items = \array_values($unique);

        usort($items, static fn (FilesystemItem $a, FilesystemItem $b): int => self::sortByMediaTypePriority($a, $b));

        return new FilesystemItemIterator($items);
    }

    public function hasOneVideoAtLeast(): bool
    {
        return !empty($this->getAllVideos()->toArray());
    }

    /**
     * Mobile (<640 px) variants should come first, so we sort by the presence
     * of a `media` attribute in the Extra‑Metadata.
     */
    private static function sortByMediaTypePriority(FilesystemItem $a, FilesystemItem $b): int
    {
        $aIsFile = $a->isFile();

        // folders ("streams") always come after files
        if (0 !== ($sort = ($b->isFile() <=> $aIsFile))) {
            return $sort;
        }

        if (!$aIsFile) {
            return 0;
        }

        // Prefer items that define a media query in their extra metadata
        $sortOrderA = (null !== $a->getExtraMetadata()->get('media')) ? -1 : 1;
        $sortOrderB = (null !== $b->getExtraMetadata()->get('media')) ? -1 : 1;

        return $sortOrderA <=> $sortOrderB;
    }

     /**
     * Normalises Extra‑Metadata to a plain array for legacy helper functions.
     */
    private static function extraToArray(ExtraMetadata|array $extra): array
    {
        return $extra instanceof ExtraMetadata ? $extra->all() : $extra;
    }
}
