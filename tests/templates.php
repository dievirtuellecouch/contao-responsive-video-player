<?php

declare(strict_types=1);

// Read-only integration test against an installed Contao 5.7 application.
use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use DVC\ResponsiveVideoPlayer\Player\PlayerSettings;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\HttpFoundation\Request;

$root = realpath($argv[1] ?? '') ?: '';
if (!is_file($root.'/vendor/autoload.php')) {
    throw new RuntimeException('Usage: php tests/templates.php /path/to/contao [--template=responsive_video_player/my_theme]');
}
require $root.'/vendor/autoload.php';
$kernel = ContaoKernel::fromInput($root, new ArrayInput(['--env' => 'prod']));
$kernel->boot();
$container = $kernel->getContainer();
$request = Request::create('https://localhost/');
$request->attributes->set('_scope', 'frontend');
$container->get('request_stack')->push($request);
$container->get('contao.framework')->initialize();
$twig = $container->get('twig');
$settings = new PlayerSettings();
$templates = [
    'responsive_video_player' => '1280/720',
    'responsive_video_player/landscape_16_9' => '16/9',
    'responsive_video_player/classic_4_3' => '4/3',
    'responsive_video_player/square_1_1' => '1/1',
    'responsive_video_player/portrait_9_16' => '9/16',
    'responsive_video_player/cinema_21_9' => '21/9',
];
$builtins = $templates;
foreach ($argv as $argument) {
    if (str_starts_with($argument, '--template=')) {
        $templates[substr($argument, 11)] = '1280/720';
    }
}
$assert = static function (bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
};
$checks = 0;
foreach ($templates as $name => $ratio) {
    for ($case = 0; $case < 256; ++$case) {
        $controls = (bool) ($case & 1);
        $hidden = (bool) ($case & 2);
        $autoplay = (bool) ($case & 4);
        $loop = (bool) ($case & 8);
        $muted = (bool) ($case & 16);
        $speaker = (bool) ($case & 32);
        $lightbox = (bool) ($case & 64);
        $colour = ($case & 128) ? '#123abc' : '';
        $options = ['player_playsinline'];
        foreach (['player_controls' => $controls, 'player_hideoverlays' => $hidden, 'player_autoplay' => $autoplay, 'player_loop' => $loop, 'player_muted' => $muted] as $option => $enabled) {
            if ($enabled) $options[] = $option;
        }
        $context = [
            'type' => 'responsive_video_player', 'as_editor_view' => false,
            'data' => ['playerSRC' => '', 'playerSize' => serialize([1280,720]), 'playerOptions' => serialize($options), 'customTpl' => 'content_element/'.$name, 'rsce_data' => '', 'cssID' => serialize(['',''])],
            'source_files' => [], 'element_html_id' => null, 'element_css_classes' => '',
            'headline' => ['text' => '', 'tag_name' => 'h2'],
            'figure' => (object) [
                'media' => [
                    'type' => 'video',
                    'attributes' => $settings->createAttributes($options)->set('width',1280)->set('height',720),
                    'sources' => [new HtmlAttributes(['src' => '/files/test.mp4', 'type' => 'video/mp4'])],
                    'tracks' => [new HtmlAttributes(['src' => '/files/test.vtt', 'kind' => 'subtitles', 'srclang' => 'de', 'label' => 'Deutsch', 'default' => true])],
                ],
                'metadata' => new Metadata([]),
            ],
            'sound_once' => $speaker, 'lightbox_play' => $lightbox,
            'icon_color' => $colour, 'hide_overlays' => $hidden,
        ];
        $html = $twig->render('@Contao/content_element/'.$name.'.html.twig', $context);
        $dom = new DOMDocument();
        @$dom->loadHTML('<html><body>'.$html.'</body></html>');
        $xpath = new DOMXPath($dom);
        $video = $dom->getElementsByTagName('video')->item(0);
        $prefix = $name.' case '.$case.': ';
        $assert(null !== $video, $prefix.'video absent');
        foreach (['controls' => $controls, 'autoplay' => $autoplay, 'loop' => $loop, 'muted' => $muted, 'playsinline' => true] as $attribute => $expected) {
            $assert($video->hasAttribute($attribute) === $expected, $prefix.$attribute.' changed');
        }
        $assert(1 === $xpath->query('//video//track[@srclang="de" and @default]')->length, $prefix.'subtitle track lost');
        $assert((int) $speaker === $xpath->query('//*[@data-action="sound-once"]')->length, $prefix.'speaker mismatch');
        $assert((int) $lightbox === $xpath->query('//*[@data-action="lightbox-play"]')->length, $prefix.'lightbox mismatch');
        $assert(($hidden ? 0 : 2) === $xpath->query('//*[@data-action="play-toggle"]')->length, $prefix.'play controls mismatch');
        $fullscreenCount = $xpath->query('//*[@data-action="fullscreen"]')->length;
        if (isset($builtins[$name])) {
            $assert((int) (!$hidden && $controls) === $fullscreenCount, $prefix.'fullscreen mismatch');
        } else {
            // Themes may expose their fullscreen action independently of the
            // native bar, but must retain it when controls are explicitly on.
            $assert($hidden ? 0 === $fullscreenCount : ($controls ? 1 === $fullscreenCount : $fullscreenCount <= 1), $prefix.'theme fullscreen mismatch');
        }
        $rootElement = $xpath->query('//*[contains(concat(" ",normalize-space(@class)," ")," video-player ")]')->item(0);
        $assert(null !== $rootElement, $prefix.'player wrapper lost');
        $assert($rootElement->hasAttribute('data-module') === (!$hidden || $speaker || $lightbox), $prefix.'JS initialization mismatch');
        $assert($rootElement->hasAttribute('data-icon-color') === ('' !== $colour), $prefix.'picker mismatch');
        $style = preg_replace('/\s+/', '', $rootElement->getAttribute('style'));
        $assert(str_contains($style, '--aspect-ratio:'.$ratio.';'), $prefix.'layout ratio lost');
        ++$checks;
    }
    if (!isset($builtins[$name])) {
        // Retired per-theme JSON flags must not overrule current player options.
        $context['data']['rsce_data'] = '{"showPlayButton":"","showFullscreenButton":""}';
        $context['data']['playerOptions'] = serialize(['player_controls']);
        $context['hide_overlays'] = false;
        $legacyHtml = $twig->render('@Contao/content_element/'.$name.'.html.twig', $context);
        $assert(2 === substr_count($legacyHtml, 'data-action="play-toggle"'), $name.': legacy flags override current overlay setting');
    }
    $context['as_editor_view'] = true;
    $editorHtml = $twig->render('@Contao/content_element/'.$name.'.html.twig', $context);
    $assert(!str_contains($editorHtml, 'data-action='), $name.': interactive controls leaked into editor view');
    $assert(!str_contains($editorHtml, 'data-module="video-player"'), $name.': JS enabled for editor view without video');
    echo 'PASS: '.$name.' — 256 option combinations, native media flags, tracks, picker, corner actions, editor view.'.PHP_EOL;
}
echo 'PASS: '.$checks.' rendered template/option combinations; no content or database writes.'.PHP_EOL;
