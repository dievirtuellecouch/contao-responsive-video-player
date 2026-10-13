# Responsive Video Player

Contao 5.7 video player with responsive file variants. Requires PHP 8.3 or newer.

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
