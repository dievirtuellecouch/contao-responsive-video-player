# Responsive Video Player

Contao 5.7 video player with responsive file variants. Requires PHP 8.3 or newer.

## Default button colours

Player buttons default to white. An explicit icon-picker value colours the
controls, including the close action of the lightbox. Project themes may retain
their own artwork and colour overrides without changing this bundle.

## Lightbox Play (development branch `dev/bsk`)

Enable **Lightbox Play** in the content element's player settings. An outlined
play triangle in a round button stays visible at the top left, including during
autoplay and when central overlays are hidden. The existing player icon colour
picker colours the triangle; its hover animation matches the speaker's scaling.
The optional speaker sits at the top right. Both are independent opt-in actions.

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

Browser verification: confirm top-left lightbox and top-right speaker placement,
colour-picker inheritance, persistent corner visibility, hover/focus, desktop
and mobile source selection, audible playback with native controls, Escape,
backdrop/close button, keyboard focus, repeated open/close and independent
multiple players. Verify that previously paused inline videos stay paused and
playing videos resume at the retained position. Existing controls-only,
speaker-only and no-corner-action elements must continue working unchanged.

## Optional speaker toggle and icon colour (5.7.4)

Edit a **Responsive video player** content element. In the player settings:

- **Show speaker / Lautsprecher anzeigen** adds a small speaker in the
  top-right corner: a cross while muted, sound waves during audible playback.
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
