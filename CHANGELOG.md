# Changelog

## Unreleased — dev/bsk

- Expose the speaker and Lightbox Play actions as the reusable Twig block
  `corner_controls`, so project content overrides can retain the optional
  actions without copying their markup. Default rendering stays unchanged.

- Default all player button colours to white unless the icon picker/theme
  explicitly supplies a colour. The lightbox close button inherits the opening
  action's colour, including an explicit picker value.

- Move the optional speaker from the top left to the top right.
- Add opt-in **Lightbox Play** (`playerLightboxPlay`, default false): an always
  visible top-left action using the reference site's outlined play triangle,
  the existing icon colour picker and the same hover scaling as the speaker.
- Ship the complete native-dialog lightbox, styles and JavaScript in this bundle.
  Playback starts in a fresh, unmuted player with native controls; the selected
  responsive source and caption tracks are retained. The embedded player pauses
  until dismissal, then resumes only if it was previously playing.
- Support Escape, close button, backdrop dismissal, focus trapping/restoration,
  scroll locking, multiple players and reduced motion. The backdrop is neutral
  dark grey with a 4px blur. Browser playback restrictions still apply.
- Accept legacy `_dekstop` filenames alongside `_desktop`, preserving existing
  media files and the normal mobile breakpoint.
- Keep all 5.7.4 option semantics, controller constructor arguments and Twig
  extension points. Existing players acquire no lightbox action unless enabled.


## 5.7.4 — 2026-10-24

This release continues the requested 5.7 version line for Contao 5.7 and PHP 8.3+.
It includes an intentional controls-option inversion and controller constructor
change. These require reviewing [UPGRADE.md](UPGRADE.md) before updating, even
though the package retains the 5.7 version line. The temporary 6.0.0 release/tag
was withdrawn at the maintainer's request; functionality is unchanged.

### Added

- Optional accessible top-left speaker: pure sound toggle without seeking,
  play/pause calls, loop changes or automatic muting at the end.
- Showing the speaker is presentation-only: initial mute, inline playback,
  autoplay and loop options are preserved exactly. Initially audible playback
  shows waves; muted playback shows a cross. Both directions preserve position.
- Optional backend hex colour picker for all player icons and their borders.
- Separate “Hide overlay elements / Overlay-Elemente verbergen” option in the
  existing options field, scoped to the DVC player. Native controls, central
  overlay controls and the speaker can be configured independently. Autoplay
  only starts on load; loop alone controls repetition and is listed before it.
  Neither changes control visibility. The speaker never changes the loop.
  The hide-overlays checkbox follows show-controls directly in the backend.
- Speaker toggles never start a paused, unstarted or ended video and preserve
  its position. Existing field and option values are retained.
- Full-frame overlays no longer intercept native media controls. Paused play
  buttons remain clickable after playback; invisible controls are not hit targets.
- Backend option renamed to “Show speaker / Lautsprecher anzeigen”, retaining
  stored settings. Muted speaker/cross and audible speaker/waves icons switch
  with the real sound state, including changes through native controls.
- German/English autoplay warning directly under its checkbox. Generic field
  description removed for DVC players only, leaving core/YouTube unchanged.
- Final option order: inline, loop, autoplay, muted, show controls, hide overlays.
- Remove only the speaker checkbox's native backend top offset so it continues
  the options list; namespaced styles are also loaded in the backend.
- Controls checkbox inverted and renamed to Show controls: off by default,
  checked shows native controls. Positive `player_controls` key with a scoped
  backend load adapter for checked legacy `player_nocontrols` values. Existing
  checkbox states intentionally follow the new inverted meaning. No data rewrite
  or schema migration; other Contao player types remain unchanged.
- German/English field labels and speaker action labels; safe colour validation.
- Settings and JavaScript regression tests; packaged rebuilt assets.

### Fixed

- Detect either fullscreen API (not both required), avoid unhandled play promise
  rejections and guard against repeated initialisation.

### Upgrade

- Two additive DCA fields require an explicitly approved schema update after a
  backup. Default values keep all existing players opt-out and their colours
  unchanged. No editorial content migration is needed.
- Install bundled assets and rebuild the application cache. Existing project
  controller overrides must accept/inject `PlayerSettings`, forward `sound_once`,
  `icon_color` and `hide_overlays`, and use the shared settings when building
  media attributes. Composer derives the package version from its Git tag.
- Theme overrides for play/stop must use `--video-player-icon-color` when set.
- Poster/variant selection, preload and layout are not changed by this release.
  Autoplay is independent of control visibility; browser autoplay policies apply.

## 5.7.3 — 2026-10-13

### Fixed

- Use normalized metadata arrays when sorting video variants. Contao 5.7
  returns `ExtraMetadata` objects; passing them to `array_key_exists()` caused
  a TypeError when two or more file-based video variants were found.
- Preserve the existing mobile-first priority, stream ordering and metadata.
  Poster selection and playback options are unchanged by this fix.

### Tests

- Add 12 read-only regression cases using actual Contao filesystem classes,
  covering empty/missing variants, desktop/mobile combinations, null-valued
  metadata keys, stable sorting, streams, posters and incremental additions.

### Upgrade

- Targeted Composer update only; no schema changes or migration required.
- Rebuild the application cache after installation. No frontend asset rebuild
  is needed for this release: templates and shipped JavaScript/CSS are unchanged.

## 5.7.2 — 2026-08-21

- Enable inline video playback by default.
- Preserve a configured poster when no filename-derived poster variant exists.
