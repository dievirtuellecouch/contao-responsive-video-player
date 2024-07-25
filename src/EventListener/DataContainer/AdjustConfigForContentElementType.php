<?php

namespace DVC\ResponsiveVideoPlayer\EventListener\DataContainer;

use Contao\ContentModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use DVC\ResponsiveVideoPlayer\Controller\ContentElement\ResponsiveVideoPlayerController;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsCallback(
    table: 'tl_content',
    target: 'config.onload'
)]
class AdjustConfigForContentElementType
{
    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    public function __invoke(DataContainer $dataContainer = null): void
    {
        if (!$this->isInEditMode($dataContainer)) {
            return;
        }

        if (!$this->isContentElementType($dataContainer)) {
            return;
        }

        $GLOBALS['TL_DCA']['tl_content']['fields']['playerSRC']['eval']['multiple'] = false;
        $GLOBALS['TL_DCA']['tl_content']['fields']['playerSRC']['eval']['fieldType'] = 'radio';
    }

    private function isInEditMode(DataContainer $dataContainer = null): bool
    {
        $actMode = $this->requestStack->getCurrentRequest()->query->get('act');

        return ($dataContainer !== null) && ($dataContainer->id !== null) && ($actMode === 'edit');
    }

    private function isContentElementType(DataContainer $dataContainer = null): bool
    {
        $element = ContentModel::findById($dataContainer->id);

        return $element !== null && $element->type === ResponsiveVideoPlayerController::TYPE;
    }
}
