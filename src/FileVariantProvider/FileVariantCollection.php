<?php

namespace DVC\ResponsiveVideoPlayer\FileVariantProvider;

use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\FilesystemItemIterator;
use DVC\ResponsiveVideoPlayer\FileVariantProvider\FileVariant;

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

            return $fileVariant->getFile();
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

        usort($items, static fn (FilesystemItem $a, FilesystemItem $b): int => self::sortByMediaTypePriority($a, $b));

        return new FilesystemItemIterator($items);
    }

    public function hasOneVideoAtLeast(): bool
    {
        return !empty($this->getAllVideos()->toArray());
    }

    private static function sortByMediaTypePriority(FilesystemItem $a, FilesystemItem $b): int
    {
        $aIsFile = $a->isFile();

        if (0 !== ($sort = ($b->isFile() <=> $aIsFile))) {
            return $sort;
        }

        if (!$aIsFile) {
            return 0;
        }

        $sortOrderA = \array_key_exists('media', $a->getExtraMetadata()) ? -1 : 1;
        $sortOrderB = \array_key_exists('media', $b->getExtraMetadata()) ? -1 : 1;

        return (false === $sortOrderA ? PHP_INT_MAX : $sortOrderA) <=> (false === $sortOrderB ? PHP_INT_MAX : $sortOrderB);
    }
}
