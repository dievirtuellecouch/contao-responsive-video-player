<?php

declare(strict_types=1);

require $argv[1] ?? dirname(__DIR__, 3).'/vendor/autoload.php';

use DVC\ResponsiveVideoPlayer\FileVariantProvider\SearchStrategy\TrailingUnderscoreSearchStrategy;
use DVC\ResponsiveVideoPlayer\FileVariantProvider\VariantIdentifier;

$strategy = new TrailingUnderscoreSearchStrategy();
foreach (['desktop', 'dekstop'] as $suffix) {
    if (!in_array($suffix, $strategy::getSuffixOptions(VariantIdentifier::VideoDesktop), true)) {
        throw new RuntimeException('Missing desktop suffix '.$suffix);
    }
    $actual = $strategy->getSearchPath('videos/example_mobil.mp4', $suffix, 'mp4');
    if ($actual !== 'videos/example_'.$suffix.'.mp4') {
        throw new RuntimeException('Incorrect desktop search path '.$actual);
    }
}
if ($strategy::getSuffixOptions(VariantIdentifier::VideoMobile) !== ['mobil', 'mobile']
    || $strategy::getSuffixOptions(VariantIdentifier::ImagePoster) !== ['thumbnail']) {
    throw new RuntimeException('Existing mobile/poster discovery changed');
}
echo "PASS: standard and legacy desktop filenames; mobile/poster discovery unchanged.\n";
