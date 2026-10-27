# Development and release installation

The current stable release is `5.7.5`. Composer derives the version from the Git
tag; `composer.json` deliberately has no hardcoded version field. Production
and reproducible application installs should require the stable tag through
`https://github.com/dievirtuellecouch/contao-responsive-video-player.git`.
They must not depend on an application-local bundle checkout or a path version
pretending to be a release.

The `dev/bsk` branch remains available for source development. To work on a local
checkout, explicitly register it as a Composer path repository with symlinking
and require `dev-dev/bsk`. Remove that path repository and switch back to the
stable requirement before validating a release installation. Keep local Git
checkouts outside `vendor` so Composer can replace installed artifacts safely.

Run the regression tests in `readme.md` against a compatible application.
For frontend source changes, use `npm ci --ignore-scripts` and `npx gulp` and
commit the generated public assets. Source changes to services, DCA or templates
require clearing the application's production cache. No Node build is needed
when consuming a release.

Schema requirements and reviewed upgrade steps are documented in `UPGRADE.md`.
No database content, credentials or project-specific layouts belong in a bundle
release.
