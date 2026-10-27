<?php

declare(strict_types=1);

use Contao\ContentModel;
use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\File\MetadataBag;
use Contao\CoreBundle\File\TextTrack;
use Contao\CoreBundle\File\TextTrackType;
use Contao\CoreBundle\Filesystem\ExtraMetadata;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\PublicUri\OptionsInterface;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use DVC\ResponsiveVideoPlayer\Controller\ContentElement\ResponsiveVideoPlayerController;
use DVC\ResponsiveVideoPlayer\FileVariantProvider\FileVariant;
use DVC\ResponsiveVideoPlayer\FileVariantProvider\FileVariantCollection;
use DVC\ResponsiveVideoPlayer\FileVariantProvider\FileVariantProvider;
use DVC\ResponsiveVideoPlayer\FileVariantProvider\VariantIdentifier;
use DVC\ResponsiveVideoPlayer\Player\PlayerSettings;
use DVC\ResponsiveVideoPlayer\Util\AssetUtility;
use Nyholm\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

$root = realpath($argv[1] ?? '') ?: '';
if (!is_file($root.'/vendor/autoload.php')) throw new RuntimeException('Usage: php tests/controller.php /path/to/contao');
require $root.'/vendor/autoload.php';
$kernel = ContaoKernel::fromInput($root, new ArrayInput(['--env'=>'prod']));
$kernel->boot();
$container = $kernel->getContainer();
$container->get('contao.framework')->initialize();

$videoId = '11111111-1111-4111-8111-111111111111';
$trackId = '22222222-2222-4222-8222-222222222222';
$posterId = '33333333-3333-4333-8333-333333333333';
$explicitPosterId = '44444444-4444-4444-8444-444444444444';
$video = new FilesystemItem(true, 'sample_desktop.mp4', mimeType:'video/mp4');
$track = new FilesystemItem(true, 'sample.de.vtt', mimeType:'text/vtt', extraMetadata:new ExtraMetadata([
    'textTrack'=>new TextTrack('de',TextTrackType::subtitles),
    'localized'=>new MetadataBag(['de'=>new Metadata(['title'=>'Deutsch'])]),
]));
$poster = new FilesystemItem(true, 'sample_thumbnail.jpg', mimeType:'image/jpeg', extraMetadata:new ExtraMetadata(['uuid'=>Uuid::fromString($posterId)]));
$storage = new class([$videoId=>$video, $trackId=>$track]) extends VirtualFilesystem {
    public function __construct(private array $items) {}
    public function get(Uuid|string $location, int $accessFlags=self::NONE): ?FilesystemItem { return $this->items[(string)$location] ?? null; }
    public function generatePublicUri(Uuid|string $location, ?OptionsInterface $options=null): ?UriInterface { return new Uri('/files/'.(string)$location); }
};
$variants = new FileVariantCollection([new FileVariant($poster,VariantIdentifier::ImagePoster,'sample_thumbnail.jpg')]);
$provider = new class($variants) extends FileVariantProvider {
    public function __construct(private FileVariantCollection $variants) {}
    public function getVariantsFromBaseFile(?FilesystemItem $baseFile): FileVariantCollection { return $this->variants; }
};
$controller = new ResponsiveVideoPlayerController(new AssetUtility($root,'files',$root.'/public'),$provider,$storage,new PlayerSettings());
$controller->setContainer($container);
$method = new ReflectionMethod($controller,'getResponse');
$assert = static function (bool $condition,string $message): void { if (!$condition) throw new RuntimeException($message); };
foreach (['',$explicitPosterId] as $selectedPoster) {
    $model = new ContentModel();
    $model->playerSRC=serialize([Uuid::fromString($videoId)->toBinary()]);
    $model->posterSRC=$selectedPoster;
    $model->textTrackSRC=serialize([Uuid::fromString($trackId)->toBinary()]);
    $model->playerSize=serialize([1280,720]);
    $model->playerOptions=serialize(['player_muted']);
    $model->playerStart='2';$model->playerStop='7';
    $model->playerSoundOnce=true;$model->playerLightboxPlay=true;$model->playerIconColor='123abc';
    $captured=[];
    $template = new FragmentTemplate('unused',static function(FragmentTemplate $template) use (&$captured): Response {
        $captured=$template->getData();return new Response();
    });
    $method->invoke($controller,$template,$model,Request::create('https://localhost/'));
    $assert($model->posterSRC===($selectedPoster ?: $posterId),'Selected poster overwritten or automatic fallback lost');
    $figure=$captured['figure'];
    $assert(1===count($figure->media['tracks']),'Subtitle track omitted by controller');
    $trackAttributes=$figure->media['tracks'][0];
    $assert('de'===$trackAttributes['srclang'] && 'Deutsch'===$trackAttributes['label'] && 'subtitles'===$trackAttributes['kind'] && isset($trackAttributes['default']),'Track metadata/default lost');
    $assert('/files/sample_desktop.mp4#t=2,7'===$figure->media['sources'][0]['src'],'Playback range changed');
    $assert(true===$captured['sound_once'] && true===$captured['lightbox_play'] && '#123abc'===$captured['icon_color'],'Template feature flags/colour lost');
}
echo "PASS: explicit poster priority, automatic poster fallback, controller subtitle metadata, playback range and corner-control flags; no file/database writes.\n";
