<?php

declare(strict_types=1);

use DVC\ResponsiveVideoPlayer\Player\PlayerSettings;

$autoload = $argv[1] ?? dirname(__DIR__, 3).'/vendor/autoload.php';
require $autoload;
if (in_array('--source', $argv, true)) {
    require dirname(__DIR__).'/src/Player/PlayerSettings.php';
}
$settings = new PlayerSettings();
$checks = 0;
foreach (range(0, 63) as $case) {
    $options = [];
    foreach (['player_autoplay', 'player_loop', 'player_muted', 'player_controls', 'player_playsinline', 'player_hideoverlays'] as $bit => $option) {
        if ($case & (1 << $bit)) {
            $options[] = $option;
        }
    }
        foreach ([false, true] as $inlineDefault) {
            $attributes = $settings->createAttributes($options, $inlineDefault);
            foreach (['autoplay', 'muted', 'loop', 'playsinline', 'controls'] as $attribute) {
                $expected = match ($attribute) {
                    'controls' => in_array('player_controls', $options, true),
                    'muted' => in_array('player_muted', $options, true),
                    'loop' => in_array('player_loop', $options, true),
                    'playsinline' => $inlineDefault || in_array('player_playsinline', $options, true),
                    default => in_array('player_'.$attribute, $options, true),
                };
                if (isset($attributes[$attribute]) !== $expected) {
                    throw new RuntimeException('Incorrect attribute '.$attribute);
                }
                ++$checks;
            }
            if ($settings->hideOverlays($options) !== in_array('player_hideoverlays', $options, true)
                || isset($attributes['hideoverlays'])) {
                throw new RuntimeException('Overlay option leaked into media attributes or depends on playback.');
            }
            ++$checks;
        }
}
if (!isset($settings->createAttributes(['player_nocontrols'])['controls'])
    || isset($settings->createAttributes([])['controls'])) {
    throw new RuntimeException('Controls default or checked legacy option incorrect.');
}
$checks += 2;
foreach ([null => null, '' => null, 'ffAA00' => '#ffaa00', 'ffffff' => '#ffffff', 'invalid' => null, 'fff;display:none' => null, '#ffaa00' => null] as $input => $expected) {
    if ($settings->iconColor($input) !== $expected) {
        throw new RuntimeException('Unsafe or incorrect colour');
    }
    ++$checks;
}
printf("PASS: %d settings/colour checks; loaded %s\n", $checks, (new ReflectionClass($settings))->getFileName());
