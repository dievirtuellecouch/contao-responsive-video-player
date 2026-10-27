<?php

declare(strict_types=1);

use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use DVC\ResponsiveVideoPlayer\FileVariantProvider\FileVariantProvider;
use DVC\ResponsiveVideoPlayer\FileVariantProvider\SearchStrategy\TrailingUnderscoreSearchStrategy;
use DVC\ResponsiveVideoPlayer\FileVariantProvider\SearchStrategyProvider;
use Symfony\Component\Uid\Uuid;

require $argv[1] ?? throw new RuntimeException('Usage: php tests/variant-provider.php /path/to/vendor/autoload.php');
$video=static fn(string $path)=>new FilesystemItem(true,$path,mimeType:'video/mp4');
$image=static fn(string $path)=>new FilesystemItem(true,$path,mimeType:'image/jpeg');
$make=static function(array $items): FileVariantProvider {
    $storage=new class($items) extends VirtualFilesystem {
        public function __construct(private array $items) {}
        public function get(Uuid|string $location,int $accessFlags=self::NONE): ?FilesystemItem {return $this->items[(string)$location]??null;}
    };
    $strategies=new SearchStrategyProvider();$strategies->addSearchStrategy(new TrailingUnderscoreSearchStrategy());
    return new FileVariantProvider($storage,$strategies);
};
$assert=static function(bool $condition,string $message):void {if(!$condition)throw new RuntimeException($message);};
foreach(['movie.mp4','folder_with_suffix/movie.mp4'] as $path){
    $base=$video($path);$variants=$make([$path=>$base])->getVariantsFromBaseFile($base);
    $assert(!$variants->hasOneVideoAtLeast() && null===$variants->getPosterImage(),'Plain video misclassified as responsive sibling or poster');
}
$base=$video('movie_main.mp4');$mobile=$video('movie_mobil.mp4');
$variants=$make(['movie_main.mp4'=>$base,'movie_mobil.mp4'=>$mobile])->getVariantsFromBaseFile($base);
$paths=array_map(static fn($v)=>$v->getPath(),$variants->getAllVideos()->toArray());
$assert($paths===['movie_mobil.mp4','movie_main.mp4'],'Explicit source discarded when only mobile sibling exists');
$base=$video('movie_desktop.mp4');$poster=$image('movie_thumbnail.jpg');
$variants=$make(['movie_desktop.mp4'=>$base,'movie_thumbnail.jpg'=>$poster])->getVariantsFromBaseFile($base);
$assert($variants->getPosterImage()===$poster && count($variants->getAllVideos()->toArray())===1,'Standard video/poster discovery changed');
$variants=$make(['movie_desktop.mp4'=>$base,'movie_thumbnail.jpg'=>$video('movie_thumbnail.jpg')])->getVariantsFromBaseFile($base);
$assert(null===$variants->getPosterImage(),'Non-image exposed as poster');
echo "PASS: plain filenames, underscored folders, mobile-only sibling with explicit desktop fallback, standard variants and poster media types.\n";
