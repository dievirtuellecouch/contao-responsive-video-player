<?php
// Run against the application's locked Contao dependencies; no CMS upgrade required.
require $argv[1] ?? __DIR__.'/../vendor/autoload.php';
spl_autoload_register(static function ($class) {
    $prefix = 'DVC\\ResponsiveVideoPlayer\\';
    if (str_starts_with($class, $prefix)) require __DIR__.'/../src/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
}, true, true);

use Contao\CoreBundle\Filesystem\{FilesystemItem, FilesystemItemIterator, ExtraMetadata, VirtualFilesystem};
use DVC\ResponsiveVideoPlayer\FileVariantProvider\{FileVariant, FileVariantCollection, VariantIdentifier as V, FileVariantProvider, SearchStrategyProvider};
use DVC\ResponsiveVideoPlayer\FileVariantProvider\SearchStrategy\TrailingUnderscoreSearchStrategy as Strategy;
use DVC\ResponsiveVideoPlayer\Controller\ContentElement\ResponsiveVideoPlayerController as Controller;
use DVC\ResponsiveVideoPlayer\Util\AssetUtility;
use Contao\CoreBundle\Filesystem\PublicUri\OptionsInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Uid\Uuid;
use Nyholm\Psr7\Uri;

$container = new Symfony\Component\DependencyInjection\ContainerBuilder();
$container->setParameter('kernel.project_dir', '/tmp');
$container->setParameter('kernel.cache_dir', '/tmp/qck-test-cache');
Contao\System::setContainer($container);
(new ReflectionProperty(Contao\Model::class, 'arrColumnCastTypes'))->setValue(null, ['tl_content'=>[]]);
$count=0;
function check($condition, $message) { global $count; if (!$condition) throw new RuntimeException($message); ++$count; echo "PASS $message\n"; }
function item($path, $mime='video/mp4', $extra=[]) {return new FilesystemItem(true,$path,123,1024,$mime,new ExtraMetadata($extra));}
class Storage extends VirtualFilesystem {
    public array $items=[];
    public array $missing=[];
    public function __construct() {}
    public function fileExists(Uuid|string $location, int $accessFlags=self::NONE): bool {return isset($this->items[(string)$location]) && !in_array((string)$location,$this->missing,true);}
    public function get(Uuid|string $location, int $accessFlags=self::NONE): ?FilesystemItem {return $this->items[(string)$location]??null;}
    public function generatePublicUri(Uuid|string $location, ?OptionsInterface $options=null): ?UriInterface {return new Uri('/files/'.$location);}
}
$s = new Strategy();
check($s->getSearchPath('a_b/movie_desktop.mp4','mobil','mp4')==='a_b/movie_mobil.mp4','variant suffix in basename');
check($s->getSearchPath('a_b/movie.mp4','thumbnail','jpg')==='a_b/movie_thumbnail.jpg','directory underscores preserved');
check($s->getSearchPath('movie_2026.mp4','mobile','webm')==='movie_2026_mobile.webm','ordinary filename underscores preserved');
$mobile = new FileVariant(item('clip_mobil.mp4','video/mp4',['custom'=>'kept']),V::VideoMobile,'');
check($mobile->getFile()->getExtraMetadata()->get('media')==='(max-width: 640px)','mobile media metadata');
check($mobile->getFile()->getExtraMetadata()->get('custom')==='kept','existing ExtraMetadata preserved');
$c=new FileVariantCollection();$c->add(new FileVariant(item('clip_desktop.webm','video/webm'),V::VideoDesktop,''));$c->add($mobile);
$c->add(new FileVariant(null,V::VideoMobile,''));$c->add(new FileVariant(item('clip_thumbnail.jpg','image/jpeg'),V::ImagePoster,''));
check(count($c->getAllVideos()->toArray())===2,'absent variants ignored');
check($c->getPosterImage()->getPath()==='clip_thumbnail.jpg','poster detected');
check($c->getAllVideos()->first()->getPath()==='clip_mobil.mp4','mobile ordered before desktop');
$c->add(new FileVariant(item('clip_desktop.webm','video/webm'),V::VideoMobile,''));
check(count($c->getAllVideos()->toArray())===2,'duplicate sources removed');
$c->add(new FileVariant(item('bad.mp4','image/png'),V::VideoDesktop,''));
check(count($c->getAllVideos()->toArray())===2,'nonvideo source rejected');
$storage=new Storage();$strategies=new SearchStrategyProvider();$strategies->addSearchStrategy($s);$provider=new FileVariantProvider($storage,$strategies);
$storage->items=['clip_desktop.mp4'=>item('clip_desktop.mp4'),'clip_mobil.mp4'=>item('clip_mobil.mp4')];$storage->missing=['clip_mobil.mp4'];
check(count($provider->getVariantsFromBaseFile($storage->items['clip_desktop.mp4'])->getAllVideos()->toArray())===1,'stale DBAFS mobile record ignored');
check(!$provider->getVariantsFromBaseFile(null)->hasOneVideoAtLeast(),'empty selection');
$controller=new Controller(new AssetUtility('/tmp','files','/tmp/public'),$provider,$storage);
$m=new ReflectionMethod($controller,'getSourceFiles');
$files=$m->invoke($controller,$c->getAllVideos());
check($files[0]->getPath()==='clip_mobil.mp4','controller preserves mobile precedence across formats');
$empty=$m->invoke($controller,new FilesystemItemIterator([item('not-video.jpg','image/jpeg')]));
check($empty===[],'controller rejects nonvideo base');
$reflect=new ReflectionClass(Contao\ContentModel::class);$model=$reflect->newInstanceWithoutConstructor();
$model->playerOptions=serialize(['player_muted','player_loop','player_nocontrols']);
$attributes=(new ReflectionMethod($controller,'parsePlayerOptions'))->invoke($controller,$model);
check(!isset($attributes['controls']) && isset($attributes['muted']) && isset($attributes['loop']) && isset($attributes['playsinline']),'options and inline playback');
$model->playerOptions='';$model->posterSRC='';$model->playerSize=serialize([1920,1080]);$model->playerStart='2';$model->playerStop='5';$model->playerCaption='Caption';$model->playerPreload='metadata';
$figure=(new ReflectionMethod($controller,'buildVideoFigureData'))->invoke($controller,$model,$files);
check(str_ends_with((string)$figure['media']['sources'][0]['src'],'#t=2,5'),'start/stop fragment');
check((string)$figure['media']['attributes']['width']==='1920' && (string)$figure['media']['attributes']['height']==='1080','video dimensions');
check($figure['metadata']->getCaption()==='Caption','explicit caption');
$model->playerStart='';$model->playerStop='5';
$figure=(new ReflectionMethod($controller,'buildVideoFigureData'))->invoke($controller,$model,$files);
check(str_ends_with((string)$figure['media']['sources'][0]['src'],'#t=0,5'),'stop-only fragment starts at zero');
$model->playerStart='3';$model->playerStop='';
$figure=(new ReflectionMethod($controller,'buildVideoFigureData'))->invoke($controller,$model,$files);
check(str_ends_with((string)$figure['media']['sources'][0]['src'],'#t=3'),'start-only fragment');
$bag = new Contao\CoreBundle\File\MetadataBag(['de'=>new Contao\CoreBundle\File\Metadata(['caption'=>'Metadata caption'])], ['de']);
$captionItem=item('caption.mp4','video/mp4',['localized'=>$bag]);
$captionFiles=$m->invoke($controller,new FilesystemItemIterator([$captionItem]));$model->playerCaption='';
$figure=(new ReflectionMethod($controller,'buildVideoFigureData'))->invoke($controller,$model,$captionFiles);
check($figure['metadata']->getCaption()==='Metadata caption','file metadata caption fallback');
echo "$count PHP assertions passed\n";
