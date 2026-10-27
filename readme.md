# Responsive Video Player

Version **5.7.5**. Contao 5.7 video player with responsive file variants. Requires PHP 8.3 or newer.

## Included templates and overrides

The content element's template selector provides these bundle-owned templates:

| Template | Layout |
| --- | --- |
| `content_element/responsive_video_player` | Default; uses the configured size, otherwise 16:9 |
| `content_element/responsive_video_player/landscape_16_9` | 16:9 |
| `content_element/responsive_video_player/classic_4_3` | 4:3 |
| `content_element/responsive_video_player/square_1_1` | 1:1 |
| `content_element/responsive_video_player/portrait_9_16` | 9:16 |
| `content_element/responsive_video_player/cinema_21_9` | 21:9 |

Variants only choose the display ratio and preserve the source video. They do
not force autoplay, looping, muting, controls, speaker, Lightbox Play or colour.
Those remain content-element settings. All behaviour, icons, styles and scripts
ship with this bundle; no theme or project extension is required. An explicitly
selected poster wins over automatic `_thumbnail` discovery. Selected subtitle
tracks with Contao track metadata/labels are included inline and in the lightbox.

Override the base in
`templates/content_element/responsive_video_player.html.twig`, or an individual
variant at the matching path, for example
`templates/content_element/responsive_video_player/portrait_9_16.html.twig`:

```twig
{% extends '@Contao_ResponsiveVideoPlayerBundle/content_element/responsive_video_player/portrait_9_16.html.twig' %}

{% block attributes %}
    {% set attributes = attrs(attributes).addClass('my-video') %}
    {{ parent() }}
{% endblock %}
```

The explicit bundle namespace above addresses the original file and prevents
recursive inheritance. Shipped variants inherit the `@Contao` base, so a project
base override also applies to all variants. Clear Contao's production cache after
adding/removing templates. Existing project templates keep their own names.

Prefer small block overrides. A theme replacing `content` or `media` must retain
`overlay_controls` (if desired) and `corner_controls` beside the video. Individual
corner actions also have `speaker_button` and `lightbox_button` blocks. Keep the
`attributes` and `script` parent blocks and the `data-element` / `data-action`
attributes so the bundled JavaScript can initialize and handle all actions.
Scope frame dimensions, cropping and theme artwork to project classes; do not
copy project dimensions into the bundle. Project adapters must also preserve
`figure.media.tracks`, the media attributes and all optional template flags.
Editor previews intentionally show a file list without interactive controls.

Read-only integration tests against a compatible installed Contao application:

```bash
php tests/templates.php /path/to/contao
php tests/controller.php /path/to/contao
php tests/variant-provider.php /path/to/contao/vendor/autoload.php
# Also verify a project's own derived template, when installed:
php tests/templates.php /path/to/contao --template=responsive_video_player/my_theme
```

The template matrix renders every combination of native controls, central
visibility, autoplay, loop, mute, speaker, lightbox and picker (256 per template).
It verifies tracks, ratio inheritance, initialization and noninteractive editor
previews. Controller tests verify poster priority/fallback, subtitle metadata,
playback ranges and optional flags. They do not edit application content.

## Default button colours

Player buttons default to white. An explicit icon-picker value colours the
controls, including the close action of the lightbox. Project themes may retain
their own artwork and colour overrides without changing this bundle.

## Lightbox Play (5.7.5)

Enable **Lightbox Play** in the content element's player settings. An outlined
play triangle in a round button stays visible at the top right, including during
autoplay and when central overlays are hidden. The existing player icon colour
picker colours the triangle; its hover animation matches the speaker's scaling.
The optional speaker sits at the top left. Both are independent opt-in actions.

Clicking Lightbox Play opens a fresh native video player from the start with
sound, autoplay and the normal browser controls. It uses the currently selected
responsive source and caption tracks, without inheriting muted/loop settings.
The embedded video pauses during the modal, retains its position and settings,
and resumes on close only if it was playing beforehand. Close with Escape, the
close button or a click on the backdrop. Focus returns to the opening action.
The neutral dark backdrop blurs the page by 4px. No external lightbox library or
project JavaScript is needed. Browser audio/autoplay policies still apply.

Add the optional boolean `tl_content.playerLightboxPlay` column before activation
and grant restricted editors access to it. See [UPGRADE.md](UPGRADE.md). Disabled
is the default and preserves the previous version's behaviour.

```bash
node tests/lightbox-playback.mjs
php tests/search-strategy.php /path/to/app/vendor/autoload.php
```

Browser verification: confirm top-right lightbox and top-left speaker placement,
colour-picker inheritance, persistent corner visibility, hover/focus, desktop
and mobile source selection, audible playback with native controls, Escape,
backdrop/close button, keyboard focus, repeated open/close and independent
multiple players. Verify that previously paused inline videos stay paused and
playing videos resume at the retained position. Existing controls-only,
speaker-only and no-corner-action elements must continue working unchanged.

## Optional speaker toggle and icon colour (5.7.4)

Edit a **Responsive video player** content element. In the player settings:

- **Show speaker / Lautsprecher anzeigen** adds a small speaker in the
  top-left corner: a cross while muted, sound waves during audible playback.
  It only shows the icon and does not change any playback option on load.
  The mute checkbox alone controls initial muting; the loop checkbox alone
  controls repetition and autoplay alone controls starting on load.
- Clicking the speaker only toggles sound. It never seeks, starts, pauses or
  changes the loop, and never resets sound at the end. Paused videos stay paused;
  playing videos continue at their current position. Native sound changes update
  the icon, pressed state and translated label. Enabling sound from zero volume
  restores the volume to an audible level. The saved mute option is unchanged.
- **Player icon colour / Farbe der Player-Icons** is a six-digit hex colour
  picker shared by play, pause/stop, fullscreen and speaker. Empty preserves the
  template/theme defaults. Custom themes should consume
  `--video-player-icon-color` instead of hardcoding icon/border colours.

Both settings are opt-in and independent of the selected poster/template.
**Hide overlay elements / Overlay-Elemente verbergen** independently hides the
central play/pause controls. It is stored as `player_hideoverlays` in the existing
`playerOptions` field, only offered for this content type; no extra column or
migration is necessary. **Show controls / Steuerelemente anzeigen** is opt-in:
unchecked hides the native media bar; checked shows it. Overlay buttons and the
speaker remain independent. This uses the positive `player_controls` option.
The previously checked `player_nocontrols` value is deliberately interpreted as
checked Show controls too, implementing the requested inversion for existing
checkboxes. The backend load callback maps it to the positive key; a normal
editorial save writes the new key. No automatic database rewrite is performed.
Native Contao player/YouTube options keep their original meaning.
Autoplay only starts once on page load; loop only repeats at the end and never
starts a paused video on load. Neither determines control visibility.
The list starts with inline playback, loop, autoplay and mute. The final two
options are show controls and hide overlay elements.
An inline note under autoplay explains browser blocking of audible autoplay;
the generic player-options explanation is omitted for this content type only.
Videos need an audio track for sound. Browser autoplay policies remain in force;
neither autoplay nor showing the speaker implicitly changes muting.
The speaker checkbox directly continues the options list without Contao's
extra single-checkbox top margin. A scoped class keeps other fields unchanged.

New DCA fields: `tl_content.playerSoundOnce` (boolean, default false) and
`tl_content.playerIconColor` (varchar(6), default empty). Back up and approve the
schema update before activating this version. No existing content
values need conversion. Non-admin editor groups need access to the new excluded
fields through Contao's normal field permissions.
The label rename retains the existing `playerSoundOnce` field and values;
the two-icon state update requires no further schema change.

Version 5.7.4 continues the requested 5.7 version line. The controls checkbox
deliberately has the opposite meaning and the controller receives an additional
service; review [UPGRADE.md](UPGRADE.md) before updating. The temporarily
published 6.0.0 release/tag has been withdrawn; functionality is unchanged.
Release assets are committed under `public/`; no Node build is required on the
target server. For source development, run `npm ci --ignore-scripts` and
`npx gulp`. Composer derives the version from the Git tag.

## Regression tests

Use an installed Contao 5.7 test application to provide the real framework
classes. Tests use in-memory file objects and do not upload files or alter data.

```bash
# Test this source checkout against the test application's dependencies:
php tests/file-variant-collection.php /path/to/app/vendor/autoload.php --source

# Test the package actually installed in that application (no source override):
php tests/file-variant-collection.php /path/to/app/vendor/autoload.php
```

The output includes the loaded collection class path to distinguish source and
installed-artifact tests. A failed test returns a nonzero exit status.

See [CHANGELOG.md](CHANGELOG.md) for release and upgrade notes.

Additional tests (no database writes):

```bash
node tests/sound-playback.mjs
php tests/player-settings.php /path/to/app/vendor/autoload.php --source
php tests/player-settings.php /path/to/app/vendor/autoload.php
```
