# Development update from 5.7.4 (`dev/bsk`)

This is an additive, opt-in change. PHP/Contao requirements, existing player
options, controller constructor and stored speaker/colour fields stay the same.
The optional speaker now appears at the top right. Existing content is not
rewritten and the lightbox defaults to off.

Before installing, back up Composer files and the database. Add only the missing
`tl_content.playerLightboxPlay` boolean field (default false, NOT NULL) using a
reviewed schema change; avoid unrelated migrations. Restricted editors need
normal Contao field permission for `playerLightboxPlay`. On rollback keep this
additive column and its values.

Install the committed public assets and rebuild the production cache. No Node
build is needed in the consuming application. The lightbox requires native
`HTMLDialogElement.showModal()` support in current browsers. Project subclasses
which replace `getResponse()` must forward the optional `lightbox_play` template
value to expose the new feature; old overrides continue working without it.
Overrides of the `content` block must retain the new action if desired. No new
constructor argument or external JS/CSS dependency is introduced.

Run `node tests/lightbox-playback.mjs`, the existing regression tests and the
browser checks in `readme.md`. Inline playback, autoplay, loop, mute, controls,
colour and overlay settings retain their 5.7.4 semantics. Hiding central
overlays never hides either corner action.

# Upgrade from 5.7.3 to 5.7.4

## Compatibility and intentional changes

Contao `^5.7` and PHP `^8.3` remain the supported requirements. The package keeps
the requested 5.7 version line despite the intentional option/API changes below.
Runtime verification for this release uses Contao 5.7.9
and PHP 8.4; other allowed dependency combinations have not been exhaustively
tested. The existing ExtraMetadata sorting fix is retained.

- Native controls are **hidden by default**. Select **Show controls** to display
  them. The positive option key is `player_controls`. A checked legacy
  `player_nocontrols` value intentionally also means **show**, not hide. The DVC
  backend load adapter converts that key in memory; normal saving persists the
  positive key. No automatic content rewrite occurs. Review existing DVC player
  checkboxes during the upgrade. Other Contao player types are not changed.
- Autoplay starts playback on load, subject to browser policies. Loop repeats
  playback after its end. Neither changes native or overlay control visibility.
- **Hide overlay elements** only hides the central controls, not the speaker or
  native bar. The speaker only toggles sound; it never seeks or starts playback.
- The bundle controller constructor now also requires `PlayerSettings`. Update
  project subclasses/replacements and their service arguments. Overrides must
  forward `sound_once`, `icon_color` and `hide_overlays` to the shared template.
  Use `PlayerSettings::createAttributes()` for the independent playback options.
  Its inline-by-default argument preserves the bundle's previous inline default;
  overrides that require explicit opt-in may pass `false` as the second argument.
- Twig/DOM extension points `data-sound-once` and the stored field
  `playerSoundOnce` retain their internal names for compatibility, despite the
  final pure-toggle behaviour. Do not implement replay/once-only sound from
  those names. Theme overrides must respect `--video-player-icon-color` when set.

## Controlled installation

1. Back up code, Composer files and the database; check local controller/Twig/CSS
   overrides. Confirm the current source with `composer show --path`.
2. Require `dvc/contao-responsive-video-player:5.7.4`. Disable any higher-priority
   development path repository for this package. Perform a targeted Composer
   update with `--no-scripts`; do not update unrelated dependencies.
3. Two additive DCA fields need a **separately approved schema update** if absent:
   `tl_content.playerSoundOnce` (boolean, default false) and
   `tl_content.playerIconColor` (six-character hex value, default empty).
   Do not execute a general migration without reviewing its full plan. If both
   columns already exist, this release needs no further schema change. Never
   delete these fields or their values during an upgrade/rollback.
4. Allow the excluded fields for restricted editors as needed. Both new options
   are opt-in; existing videos do not automatically acquire a speaker/colour.
5. Install/refresh the shipped public assets using the application's existing
   Contao asset mechanism, rebuild the production cache and lint container/Twig.
   Do not run a Node build on the production server.
6. Verify the installed Git reference, actual loaded class path and public asset
   URLs. Run the README's installed-artifact tests without `--source`, then test
   real videos/posters, autoplay/mute, loop, native controls, overlays, keyboard
   and multiple players. Audible autoplay may correctly be blocked by browsers.

Poster and responsive variant discovery remain unchanged from 5.7.3. The EVT
application's explicit-poster override and structured-video metadata are
application features, not new guarantees or hardcoded content of this bundle.

## Rollback

Restore the previous application Composer files, controller/template overrides
and package version together, then rebuild assets/cache. Keep the additive
columns and saved settings. A Composer downgrade does not reverse editorial
saves from the new positive controls key; review those player options explicitly
against the backup rather than silently rewriting customer content.
