# Responsive Video Player

Contao 5.7 video player with responsive file variants. Requires PHP 8.3 or newer.

## Optional speaker toggle and icon colour (6.0.0)

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

Version 6.0.0 is a major upgrade because the controls checkbox deliberately has
the opposite meaning and the controller receives an additional service. It
continues to support Contao 5.7; see [UPGRADE.md](UPGRADE.md) before updating.
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
