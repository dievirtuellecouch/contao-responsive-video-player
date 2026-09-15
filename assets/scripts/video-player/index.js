import { documentReady } from "./util/helper.js";

export function initVideoPlayer(container) {
    if (container.dataset.videoPlayerInitialized) return;
    const video = container.querySelector('video');
    if (!video) return;
    container.dataset.videoPlayerInitialized = 'true';
    const playButtons = [...container.querySelectorAll('[data-action="play-toggle"]')];
    const fullscreenButtons = [...container.querySelectorAll('[data-action="fullscreen"]')];
    const hover = window.matchMedia('(hover: hover)');
    let hasPlayed = !video.paused;
    let hideTimer;

    const hideControls = (hide) => {
        container.dataset.hideControls = String(hide && !video.paused);
    };
    const showControls = () => {
        clearTimeout(hideTimer);
        hideControls(false);
        if (!video.paused && !container.contains(document.activeElement)) {
            hideTimer = setTimeout(() => hideControls(true), hover.matches ? 750 : 2000);
        }
    };
    const render = () => {
        clearTimeout(hideTimer);
        const playing = !video.paused && !video.ended;
        if (playing) hasPlayed = true;
        container.dataset.state = playing ? 'playing' : 'paused';
        container.dataset.showPlayOverlay = String(!hasPlayed);
        for (const button of playButtons) {
            button.dataset.state = playing ? 'playing' : 'paused';
            button.setAttribute('aria-label', playing ? button.dataset.labelPause : button.dataset.labelPlay);
        }
        showControls();
    };
    const playbackFailed = () => {
        // A rejected autoplay/play request must leave a usable play button.
        render();
    };
    for (const button of playButtons) {
        button.addEventListener('click', (event) => {
            event.stopPropagation();
            if (!video.paused) {
                video.pause();
            } else {
                try { Promise.resolve(video.play()).catch(playbackFailed); }
                catch { playbackFailed(); }
            }
        });
    }
    const fullscreen = video.requestFullscreen || video.webkitRequestFullscreen || video.webkitEnterFullscreen;
    for (const button of fullscreenButtons) {
        button.hidden = !fullscreen;
        button.addEventListener('click', (event) => {
            event.stopPropagation();
            if (!fullscreen) return;
            try { Promise.resolve(fullscreen.call(video)).catch(showControls); }
            catch { showControls(); }
        });
    }
    container.classList.toggle('video-player_can-fullscreen', Boolean(fullscreen));
    for (const event of ['play', 'pause', 'ended', 'error', 'emptied']) video.addEventListener(event, render);
    container.addEventListener('mousemove', () => { if (hover.matches) showControls(); });
    container.addEventListener('click', () => {
        if (hover.matches || video.paused) return;
        if (container.dataset.hideControls === 'true') showControls();
        else { clearTimeout(hideTimer); hideControls(true); }
    });
    container.addEventListener('focusin', () => { clearTimeout(hideTimer); hideControls(false); });
    container.addEventListener('focusout', showControls);
    hover.addEventListener('change', showControls);
    render();
}

function initAll() {
    document.querySelectorAll('[data-module="video-player"]').forEach(initVideoPlayer);
}
documentReady(initAll);
