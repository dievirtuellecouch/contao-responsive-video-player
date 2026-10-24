<?php

declare(strict_types=1);

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use DVC\ResponsiveVideoPlayer\Controller\ContentElement\ResponsiveVideoPlayerController;

$GLOBALS['TL_DCA']['tl_content']['palettes'][ResponsiveVideoPlayerController::TYPE] = $GLOBALS['TL_DCA']['tl_content']['palettes']['player'];

PaletteManipulator::create()
    ->addField(['playerSoundOnce', 'playerIconColor'], 'playerOptions', PaletteManipulator::POSITION_AFTER)
    ->applyToPalette(ResponsiveVideoPlayerController::TYPE, 'tl_content');

$GLOBALS['TL_DCA']['tl_content']['fields']['playerSoundOnce'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'clr dvc-video-player-speaker-option'],
    'sql' => ['type' => 'boolean', 'default' => false],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['playerIconColor'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => [
        'maxlength' => 6,
        'colorpicker' => true,
        'isHexColor' => true,
        'decodeEntities' => true,
        'tl_class' => 'w50 wizard',
    ],
    'sql' => "varchar(6) COLLATE ascii_bin NOT NULL default ''",
];
