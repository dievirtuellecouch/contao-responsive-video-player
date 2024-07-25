<?php

namespace DVC\ResponsiveVideoPlayer\EventSubscriber;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\Asset\Packages;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AddAssetsKernelRequestSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Packages $packages,
        private readonly ScopeMatcher $scopeMatcher,
    ) {
    }

    public static function getSubscribedEvents()
    {
        return [KernelEvents::REQUEST => 'onKernelRequest'];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$this->scopeMatcher->isFrontendRequest($request)) {
            return;
        }

        $GLOBALS['TL_CSS'][] = self::relativePath($this->packages->getUrl('video-player.css', 'responsive_video_player')) . '|static';
    }

    private static function relativePath(string $path): string
    {
        return preg_replace('/^\//', '', $path);
    }
}
