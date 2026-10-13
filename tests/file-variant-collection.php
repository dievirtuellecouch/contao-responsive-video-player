<?php

declare(strict_types=1);

use Contao\CoreBundle\Filesystem\ExtraMetadata;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use DVC\ResponsiveVideoPlayer\FileVariantProvider\FileVariant;
use DVC\ResponsiveVideoPlayer\FileVariantProvider\FileVariantCollection;
use DVC\ResponsiveVideoPlayer\FileVariantProvider\VariantIdentifier;

// Use real Contao classes from a compatible test application. --source tests
// this checkout; without it, test the package installed by that application.
$autoload = $argv[1] ?? dirname(__DIR__).'/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "Usage: php tests/file-variant-collection.php /path/to/vendor/autoload.php [--source]\n");
    exit(1);
}

require $autoload;
if (in_array('--source', $argv, true)) {
    foreach (['VariantIdentifier', 'FileVariant', 'FileVariantCollection'] as $class) {
        require dirname(__DIR__).'/src/FileVariantProvider/'.$class.'.php';
    }
}

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$variant = static function (
    string $path,
    VariantIdentifier $identifier = VariantIdentifier::VideoDesktop,
    array $extra = [],
    bool $isFile = true,
): FileVariant {
    return new FileVariant(
        new FilesystemItem($isFile, $path, mimeType: $isFile ? 'video/mp4' : null, extraMetadata: new ExtraMetadata($extra)),
        $identifier,
        $path,
    );
};
$paths = static fn (FileVariantCollection $collection): array => array_map(
    static fn (FilesystemItem $item): string => $item->getPath(),
    array_values($collection->getAllVideos()->toArray()),
);
$tests = [];
$tests['empty collection'] = static function () use ($assert, $paths): void {
    $collection = new FileVariantCollection();
    $assert([] === $paths($collection) && !$collection->hasOneVideoAtLeast(), 'Empty collection reports a video.');
    $assert(null === $collection->getPosterImage(), 'Empty collection reports a poster.');
};
$tests['missing variants'] = static function () use ($assert, $paths): void {
    $collection = new FileVariantCollection([
        new FileVariant(null, VariantIdentifier::VideoDesktop, 'missing.mp4'),
        new FileVariant(null, VariantIdentifier::VideoMobile, 'missing_mobile.mp4'),
    ]);
    $assert([] === $paths($collection) && !$collection->hasOneVideoAtLeast(), 'Missing variants were retained.');
};
$tests['single desktop'] = static function () use ($assert, $paths, $variant): void {
    $collection = new FileVariantCollection([$variant('desktop.mp4')]);
    $assert(['desktop.mp4'] === $paths($collection) && $collection->hasOneVideoAtLeast(), 'Desktop video lost.');
};
$tests['single mobile preserves metadata'] = static function () use ($assert, $variant): void {
    $collection = new FileVariantCollection([$variant('mobile.mp4', VariantIdentifier::VideoMobile, ['custom' => 'keep'])]);
    $metadata = $collection->getAllVideos()->first()->getExtraMetadata()->all();
    $assert('keep' === $metadata['custom'] && '(max-width: 640px)' === $metadata['media'], 'Mobile metadata lost.');
};
$tests['two desktops with empty metadata'] = static function () use ($assert, $paths, $variant): void {
    $collection = new FileVariantCollection([$variant('first.mp4'), $variant('second.mp4')]);
    $assert(['first.mp4', 'second.mp4'] === $paths($collection), 'Equal-priority order changed.');
};
foreach ([false, true] as $reverse) {
    $tests['desktop/mobile order '.($reverse ? 'reversed' : 'normal')] = static function () use ($assert, $paths, $variant, $reverse): void {
        $variants = [$variant('desktop.mp4'), $variant('mobile.mp4', VariantIdentifier::VideoMobile)];
        $collection = new FileVariantCollection($reverse ? array_reverse($variants) : $variants);
        $assert(['mobile.mp4', 'desktop.mp4'] === $paths($collection), 'Mobile must precede desktop.');
        $assert($collection->hasOneVideoAtLeast(), 'Multiple videos not detected.');
        $assert(['mobile.mp4', 'desktop.mp4'] === $paths($collection), 'Repeated sorting is not stable.');
    };
}
$tests['media key with null value'] = static function () use ($assert, $paths, $variant): void {
    $collection = new FileVariantCollection([$variant('desktop.mp4'), $variant('media.mp4', extra: ['media' => null])]);
    $assert(['media.mp4', 'desktop.mp4'] === $paths($collection), 'Presence semantics changed to isset semantics.');
};
$tests['files precede streams'] = static function () use ($assert, $paths, $variant): void {
    $collection = new FileVariantCollection([
        $variant('stream-a', isFile: false),
        $variant('desktop.mp4'),
        $variant('stream-b', isFile: false),
        $variant('mobile.mp4', VariantIdentifier::VideoMobile),
    ]);
    $assert(['mobile.mp4', 'desktop.mp4', 'stream-a', 'stream-b'] === $paths($collection), 'File/stream priority changed.');
};
$tests['poster is not a video'] = static function () use ($assert, $paths, $variant): void {
    $poster = $variant('poster.png', VariantIdentifier::ImagePoster);
    $collection = new FileVariantCollection([$poster, $variant('desktop.mp4')]);
    $assert(['desktop.mp4'] === $paths($collection), 'Poster leaked into video sources.');
    $assert($poster->getFile() === $collection->getPosterImage(), 'Selected variant poster changed.');
};
$tests['poster only'] = static function () use ($assert, $paths, $variant): void {
    $collection = new FileVariantCollection([$variant('poster.png', VariantIdentifier::ImagePoster)]);
    $assert([] === $paths($collection) && !$collection->hasOneVideoAtLeast(), 'Poster-only collection reports video.');
};
$tests['add variants'] = static function () use ($assert, $paths, $variant): void {
    $collection = new FileVariantCollection();
    $collection->add($variant('desktop.mp4'));
    $collection->add($variant('mobile.mp4', VariantIdentifier::VideoMobile));
    $assert(['mobile.mp4', 'desktop.mp4'] === $paths($collection), 'Added variants were not sorted.');
};

printf("PHP %s; collection: %s\n", PHP_VERSION, (new ReflectionClass(FileVariantCollection::class))->getFileName());
$failures = 0;
foreach ($tests as $name => $test) {
    try {
        $test();
        printf("PASS: %s\n", $name);
    } catch (Throwable $error) {
        ++$failures;
        fprintf(STDERR, "FAIL: %s: %s\n", $name, $error->getMessage());
    }
}
printf("%d tests, %d failures; no filesystem or database writes.\n", count($tests), $failures);
exit($failures ? 1 : 0);
