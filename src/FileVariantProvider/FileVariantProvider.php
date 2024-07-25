<?php

namespace DVC\ResponsiveVideoPlayer\FileVariantProvider;

use BenTools\CartesianProduct\CartesianProduct;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use DVC\ResponsiveVideoPlayer\FileVariantProvider\FileVariant;
use DVC\ResponsiveVideoPlayer\FileVariantProvider\FileVariantCollection;
use DVC\ResponsiveVideoPlayer\FileVariantProvider\SearchStrategy\SearchStrategyInterface;
use DVC\ResponsiveVideoPlayer\FileVariantProvider\SearchStrategyProvider;
use DVC\ResponsiveVideoPlayer\FileVariantProvider\VariantIdentifier;
use DVC\ResponsiveVideoPlayer\FileVariantProvider\VariantIdentifierFactory;

class FileVariantProvider
{
    const REPLACEMENT_MASK = '${1}_%s.%s';
    const SEARCH_PATTERN = '/(.*)_(.*)\.(.*)/';

    public function __construct(
        private readonly VirtualFilesystem $filesStorage,
        private readonly SearchStrategyProvider $searchStrategyProvider,
    ){
    }

    public function getVariantsFromBaseFile(?FilesystemItem $baseFile): FileVariantCollection
    {
        if ($baseFile === null) {
            return new FileVariantCollection();
        }

        if (!$this->searchStrategyProvider->hasStrategies())
        {
            return new FileVariantCollection();
        }

        $collection = new FileVariantCollection();
        $strategy = $this->searchStrategyProvider->getFirst();

        if ($strategy === null) {
            return $collection;
        }

        foreach (VariantIdentifierFactory::create() as $identifier) {
            $combinations = new CartesianProduct([
                'suffix' => $strategy::getSuffixOptions($identifier),
                'extension' => $strategy::getFileExtensionOptions($identifier),
            ]);

            foreach ($combinations as $combination) {
                $collection->add($this->createVariant(
                    $baseFile,
                    $identifier,
                    $strategy,
                    $combination['suffix'],
                    $combination['extension'],
                ));
            }
        }

        return $collection;
    }

    private function createVariant(
        FilesystemItem $baseFile,
        VariantIdentifier $identifier,
        SearchStrategyInterface $strategy,
        string $suffix,
        string $extension
    ): FileVariant {
        $searchPath = $strategy->getSearchPath(
            $baseFile->getPath(),
            $suffix,
            $extension,
        );

        $possibleFile = $this->filesStorage->get($searchPath);

        return new FileVariant(
            $possibleFile,
            $identifier,
            $searchPath,
        );
    }
}
