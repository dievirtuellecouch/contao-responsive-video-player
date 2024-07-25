<?php

namespace DVC\ResponsiveVideoPlayer\FileVariantProvider;

use Contao\CoreBundle\Filesystem\FilesystemItem;
use DVC\ResponsiveVideoPlayer\FileVariantProvider\VariantIdentifier;

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
        return match($this->identifier) {
            VariantIdentifier::VideoDesktop => true,
            VariantIdentifier::VideoMobile => true,
            VariantIdentifier::ImagePoster => false,
        };
    }

    public function isPosterImage(): bool
    {
        return $this->identifier === VariantIdentifier::ImagePoster;
    }

    private function prepareFile(): void
    {
        if (!$this->hasFile()) {
            return;
        }

        if ($this->identifier === VariantIdentifier::VideoMobile) {
            $metadata = \array_merge(
                $this->file->getExtraMetadata(),
                ['media' => '(max-width: 640px)']
            );

            $this->file = $this->file->withExtraMetadata($metadata);
        }
    }
}
