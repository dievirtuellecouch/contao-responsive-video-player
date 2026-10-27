# Changelog

## 5.7.5 — 2026-10-28

- Add opt-in **Lightbox Play**: a persistent top-right action opens a fresh
  player with sound, autoplay and native controls. The inline player pauses
  while the modal is open and resumes only if it was playing beforehand.
- Place the optional speaker at the top left. It remains a pure mute toggle.
- Ship the complete neutral dark/blurred native-dialog lightbox, close/backdrop
  and Escape handling, focus trapping/restoration, scroll locking and cleanup.
- Default all buttons to white, respecting an explicit picker/theme colour;
  preserve caption tracks in the inline and lightbox players.
- Ship overridable layout-only templates for 16:9, 4:3, 1:1, 9:16 and 21:9,
  inheriting every backend option from the common player template.
- Provide reusable `overlay_controls`, `corner_controls`, `speaker_button`
  and `lightbox_button` blocks for theme integration.
- Keep editor previews noninteractive. Fit icons inside their buttons and
  provide visible keyboard focus indicators.
- Respect an explicitly selected poster; use `_thumbnail` discovery as fallback.
- Support historical `_dekstop` names; keep plain filenames intact and retain
  selected desktop files when only a mobile sibling can be inferred. Reject
  non-image poster variants.
- Preserve PHP/Contao requirements, controller constructor, 5.7.4 option keys,
  existing custom template names and all other playback semantics. Lightbox
  Play defaults to off; `tl_content.playerLightboxPlay` is the only new column
  compared with 5.7.4. Review [UPGRADE.md](UPGRADE.md) before installation.
- Include controller, template-matrix and variant-provider regression tests,
  alongside the existing playback/settings and collection tests.

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
