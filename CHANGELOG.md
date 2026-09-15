# Changelog

## 0.6.3

- Ignore missing video variants, including stale Contao file-index records, so an
  absent mobile video cannot break a page with a valid desktop video.
- Preserve mobile source precedence across MP4/WebM and existing extra metadata.
- Restrict variant matching to filename suffixes and ignore nonvideo sources.
- Retain manually selected posters and use file captions when no explicit caption
  is entered. Normalize start-only and stop-only media fragments.
- Fix fullscreen detection and support iOS video fullscreen. Handle rejected
  playback/fullscreen requests and synchronize controls with media events.
- Enable inline playback by default, preserve keyboard control visibility, prevent
  repeated button backgrounds and add translated button labels.
- Preserve backend previews and add PHP/JavaScript regression coverage.

This maintenance release is based on 0.6.2; the separate 5.7.x branch is unchanged.
