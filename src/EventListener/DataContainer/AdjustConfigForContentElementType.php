<?php

namespace DVC\ResponsiveVideoPlayer\EventListener\DataContainer;

use Contao\ContentModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\StringUtil;
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

    public function __invoke(?DataContainer $dataContainer = null): void
    {
        if (!$this->isInEditMode($dataContainer)) {
            return;
        }

        if (!$this->isContentElementType($dataContainer)) {
            return;
        }

        // Help must be configured before Widget copies the field definition.
        $this->getPlayerOptions($dataContainer);
        $GLOBALS['TL_DCA']['tl_content']['fields']['playerSRC']['eval']['multiple'] = false;
        $GLOBALS['TL_DCA']['tl_content']['fields']['playerSRC']['eval']['fieldType'] = 'radio';
    }

    /** @return list<string> */
    #[AsCallback(table: 'tl_content', target: 'fields.playerOptions.options')]
    public function getPlayerOptions(?DataContainer $dataContainer = null): array
    {
        $options = $GLOBALS['TL_DCA']['tl_content']['fields']['playerOptions']['options'];
        $type = $this->getContentElementType($dataContainer);

        $reference = $GLOBALS['TL_LANG']['tl_content'];
        if (ResponsiveVideoPlayerController::TYPE === $type) {
            $label = $reference['player_autoplay'];
            $label = is_array($label) ? $label[0] : $label;
            $reference['player_autoplay'] = $label.'<br><span class="tl_help">'
                .StringUtil::specialchars($reference['player_autoplay_hint']).'</span>';
            $options = array_values(array_diff($options, ['player_loop', 'player_nocontrols', 'player_controls', 'player_hideoverlays', 'player_playsinline']));
            $autoplayPosition = array_search('player_autoplay', $options, true);
            array_splice($options, false === $autoplayPosition ? 0 : $autoplayPosition, 0, ['player_loop']);
            array_unshift($options, 'player_playsinline');
            array_push($options, 'player_controls', 'player_hideoverlays');
        }

        // Break the core reference before replacing it: never modify TL_LANG
        // or leak bundle-specific help into native video/YouTube elements.
        unset($GLOBALS['TL_DCA']['tl_content']['fields']['playerOptions']['reference']);
        $GLOBALS['TL_DCA']['tl_content']['fields']['playerOptions']['reference'] = $reference;
        $label = $GLOBALS['TL_LANG']['tl_content']['playerOptions'];
        unset($GLOBALS['TL_DCA']['tl_content']['fields']['playerOptions']['label']);
        $GLOBALS['TL_DCA']['tl_content']['fields']['playerOptions']['label'] =
            ResponsiveVideoPlayerController::TYPE === $type ? [$label[0], ''] : $label;

        return array_values(array_unique($options));
    }

    #[AsCallback(table: 'tl_content', target: 'fields.playerOptions.load')]
    public function loadPlayerOptions(mixed $value, ?DataContainer $dataContainer = null): mixed
    {
        if (ResponsiveVideoPlayerController::TYPE !== $this->getContentElementType($dataContainer)) {
            return $value;
        }
        $options = StringUtil::deserialize($value, true);
        if (!in_array('player_nocontrols', $options, true)) {
            return $value;
        }
        $options = array_values(array_unique(array_map(
            static fn (string $option): string => 'player_nocontrols' === $option ? 'player_controls' : $option,
            $options,
        )));

        return is_array($value) ? $options : serialize($options);
    }

    private function getContentElementType(?DataContainer $dataContainer): ?string
    {
        $type = $dataContainer?->getCurrentRecord()['type'] ?? null;

        return $type ?? ($dataContainer?->id ? ContentModel::findById($dataContainer->id)?->type : null);
    }

    private function isInEditMode(?DataContainer $dataContainer = null): bool
    {
        $actMode = $this->requestStack->getCurrentRequest()->query->get('act');

        return ($dataContainer !== null) && ($dataContainer->id !== null) && ($actMode === 'edit');
    }

    private function isContentElementType(?DataContainer $dataContainer = null): bool
    {
        $element = ContentModel::findById($dataContainer->id);

        return $element !== null && $element->type === ResponsiveVideoPlayerController::TYPE;
    }
}
