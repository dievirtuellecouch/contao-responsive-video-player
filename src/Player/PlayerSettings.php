<?php

declare(strict_types=1);

namespace DVC\ResponsiveVideoPlayer\Player;

use Contao\CoreBundle\String\HtmlAttributes;

final class PlayerSettings
{
    /** @param list<string> $options */
    public function createAttributes(array $options, bool $inlineByDefault = true): HtmlAttributes
    {
        $attributes = new HtmlAttributes();

        if ($inlineByDefault) {
            $attributes->set('playsinline');
        }

        foreach ($options as $option) {
            // The checkbox is now opt-in. Retain checked legacy values while
            // records move to the positive key on their next regular edit.
            if (in_array($option, ['player_controls', 'player_nocontrols'], true)) {
                $attributes->set('controls');
            } elseif (in_array($option, ['player_autoplay', 'player_muted', 'player_loop', 'player_playsinline'], true)) {
                $attributes->set(substr($option, 7));
            }
        }

        return $attributes;
    }

    /** @param list<string> $options */
    public function hideOverlays(array $options): bool
    {
        return in_array('player_hideoverlays', $options, true);
    }

    public function iconColor(?string $color): ?string
    {
        // Do not interpolate arbitrary stored values into an inline stylesheet.
        return null !== $color && 1 === preg_match('/\A[0-9a-f]{6}\z/i', $color)
            ? '#'.strtolower($color)
            : null;
    }
}
