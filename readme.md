# Responsive Video Player

Contao content element for responsive local videos, automatic poster discovery,
play/pause controls and fullscreen playback.

## File naming

Select a video in the content element. Related files in the same directory are
discovered using these suffixes:

- `name_desktop.mp4` / `.webm`: desktop and fallback source.
- `name_mobil.mp4` or `name_mobile.mp4` / `.webm`: source up to 640 CSS pixels.
- `name_thumbnail.webp`, `.jpg` or `.png`: poster (in that preference order).

Directory names and other underscores in the filename are preserved. If no
related video exists, the selected video is used. Missing physical files are
ignored even if Contao's file index still contains them. A manually selected
poster is retained when no automatic thumbnail exists.

Mobile sources precede desktop sources, including when formats differ. The
browser selects a source when loading the video; resizing an already playing
video does not force a reload.

## Playback

Contao's autoplay, muted, loop, preload, dimensions and start/stop options are
supported. Inline playback is enabled by default. Autoplay remains subject to
browser policy; use muted autoplay for background videos.

Manual players show custom play/pause and fullscreen buttons. The native-control
option independently controls the browser's own video controls. Autoplay players
do not show the custom buttons. Backend previews list source files without
rendering videos or frontend controls.

Fullscreen uses the standard API, prefixed WebKit API or iOS video fullscreen
API where available. Controls remain usable if a play/fullscreen request is
rejected. Keyboard focus keeps controls visible.

## Development and verification

```sh
npm ci
npm test
npm run build
php tests/php.php /path/to/contao/vendor/autoload.php
```

The PHP regression suite uses the application's existing dependencies and does
not migrate or modify its database. Build output and `public/manifest.json` must
be committed together.

Version 0.6.3 was verified with Contao 5.3.51, PHP 8.4 and MariaDB 10.11, including
real desktop/mobile-viewport playback in Chromium. WebKit/iOS fullscreen fallback
is covered by automated API tests; a physical iPhone was not available for testing.
