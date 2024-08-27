<?php

namespace DVC\ResponsiveVideoPlayer\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\FilesystemItemIterator;
use Contao\CoreBundle\Filesystem\FilesystemUtil;
use Contao\CoreBundle\Filesystem\SortMode;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FilesModel;
use Contao\StringUtil;
use DVC\ResponsiveVideoPlayer\FileVariantProvider\FileVariantCollection;
use DVC\ResponsiveVideoPlayer\FileVariantProvider\FileVariantProvider;
use DVC\ResponsiveVideoPlayer\Util\AssetUtility;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(category: 'media', type: self::TYPE)]
class ResponsiveVideoPlayerController extends AbstractContentElementController
{
    const TYPE = 'responsive_video_player';

    /**
     * @var array<string, UriInterface>
     */
    private array $publicUriByStoragePath = [];

    public function __construct(
        private readonly AssetUtility $assetUtility,
        private readonly FileVariantProvider $fileVariantProvider,
        private readonly VirtualFilesystem $filesStorage,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        /** @var FilesystemItemIterator $filesystemItems */
        $filesystemItems = FilesystemUtil::listContentsFromSerialized($this->filesStorage, $model->playerSRC ?: '');

        $baseFile = $filesystemItems->first();
        /** @var FileVariantCollection $fileVariants */
        $fileVariants = $this->fileVariantProvider->getVariantsFromBaseFile($baseFile);

        /** @var FilesystemItemIterator $filesystemItems */
        if ($fileVariants->hasOneVideoAtLeast()) {
            $filesystemItems = $fileVariants->getAllVideos();
        }
        $model->posterSRC = (string) $fileVariants->getPosterImage()?->getUuid() ?: '';

        if (!$sourceFiles = $this->getSourceFiles($filesystemItems)) {
            return new Response();
        }

        $figureData = $this->buildVideoFigureData($model, $sourceFiles);

        $template->set('figure', (object) $figureData);
        $template->set('source_files', $sourceFiles);

        return $template->getResponse();
    }

    /**
     * @param list<FilesystemItem> $sourceFiles
     *
     * @return array<string, array<string, string|HtmlAttributes|list<HtmlAttributes>>|string>
     */
    private function buildVideoFigureData(ContentModel $model, array $sourceFiles): array
    {
        $poster = null;

        if ($uuid = $model->posterSRC) {
            $filesModel = $this->getContaoAdapter(FilesModel::class);
            $poster = $filesModel->findByUuid($uuid);
        }

        $size = StringUtil::deserialize($model->playerSize, true);

        $attributes = $this->parsePlayerOptions($model)
            ->setIfExists('poster', $poster?->path)
            ->setIfExists('width', $size[0] ?? null)
            ->setIfExists('height', $size[1] ?? null)
            ->setIfExists('preload', $model->playerPreload)
        ;

        $range = $model->playerStart || $model->playerStop
            ? sprintf('#t=%s', implode(',', [$model->playerStart ?: '', $model->playerStop ?: '']))
            : '';

        $captions = [$model->playerCaption];

        $sources = array_map(
            function (FilesystemItem $item) use (&$captions, $range): HtmlAttributes {
                $captions[] = ($item->getExtraMetadata()['metadata'] ?? null)?->getDefault()?->getCaption();

                return (new HtmlAttributes())
                    ->setIfExists('type', $item->getMimeType(''))
                    ->set('src', $this->publicUriByStoragePath[$item->getPath()].$range)
                    ->setIfExists('media', $item->getExtraMetadata()['media'] ?? null)
                ;
            },
            $sourceFiles,
        );

        return [
            'media' => [
                'type' => 'video',
                'attributes' => $attributes,
                'sources' => $sources,
            ],
            'metadata' => new Metadata([
                Metadata::VALUE_CAPTION => array_filter($captions)[0] ?? '',
            ]),
        ];
    }

    private function parsePlayerOptions(ContentModel $model): HtmlAttributes
    {
        $attributes = new HtmlAttributes(['controls' => true]);

        foreach (StringUtil::deserialize($model->playerOptions, true) as $option) {
            if ('player_nocontrols' === $option) {
                $attributes->unset('controls');
                continue;
            }

            $attributes->set(substr($option, 7));
        }

        return $attributes;
    }

    /**
     * @return list<FilesystemItem>
     */
    private function getSourceFiles(FilesystemItemIterator $filesystemItems): array
    {
        $filesystemItems = $filesystemItems->sort(SortMode::mediaTypePriority);
        $items = [];

        foreach ($filesystemItems as $item) {
            if (!$publicUri = $this->filesStorage->generatePublicUri($item->getPath())) {
                continue;
            }

            $items[] = $item;
            $version = $this->assetUtility->getTimestampForFile($item->getPath());

            if ($version !== null) {
                $publicUri = $publicUri . '?v=' . $version;
            }

            $this->publicUriByStoragePath[$item->getPath()] = $publicUri;
        }

        return $items;
    }
}
