# Changelog

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
