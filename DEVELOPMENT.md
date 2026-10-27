# BSK development version

The `dev/bsk` branch starts from release `5.7.4`, also used by EVT.
Composer installs this branch as `dev-dev/bsk`.

In the BSK development application, clone this repository into
`bundles/contao-responsive-video-player` and register that directory as a
Composer path repository with symlinking enabled and version `dev-dev/bsk`.
The installed package in `vendor/dvc/contao-responsive-video-player` then points
to the editable Git checkout. Keep the VCS repository configured as a fallback
for environments without the local path repository.

Run the regression tests described in `readme.md` against the application's
`vendor/autoload.php`. After changing frontend sources, rebuild and commit the
shipped assets as described there. Clear the application's production cache
after changing service definitions, DCA or templates.

Installation requires the two additive database fields documented in
`UPGRADE.md`. Review only this bundle's schema changes, without applying
unrelated migrations.
