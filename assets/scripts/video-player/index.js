import { documentReady } from "./util/helper.js";

const VideoPlayer = function() {
    const elements = {
        container: null,
        video: null,
        buttons: {
            playToggle: [],
            fullscreen: [],
        },
    };

    const state = {
        isTouchDevice: null,
        isPlaying: false,
        hideControls: false,
        playHasBeenClickedAtLeastOneTime: false,
    };

    let controlsHideTimer = null;

    const onPlayToggle = function() {
        state.playHasBeenClickedAtLeastOneTime = true;

        if (elements.video.paused) {
            elements.video.play();
        }
        else {
            elements.video.pause();
            clearTimeout(controlsHideTimer);
        }

        render();
    };

    const onFullscreen = function() {
        if (elements.video.requestFullscreen) {
            elements.video.requestFullscreen();
            return;
        }

        if (elements.video.webkitRequestFullscreen) {
            elements.video.webkitRequestFullscreen();
        }
    };

    const onVideoStateChange = function() {
        state.isPlaying = !elements.video.paused;
        state.hideControls = state.isPlaying;

        for (const button of elements.buttons.playToggle) {
            button.setAttribute('data-state', state.isPlaying ? 'playing' : 'paused');
        }

        render();
    };

    var onContainerTouch = function() {
        if (!state.isPlaying) {
            return;
        }

        state.hideControls = !state.hideControls;
        render();

        if (!state.hideControls) {
            controlsHideTimer = setTimeout(hideControls, getControlsHideDelay());
        }
    };

    const hideControls = function() {
        clearTimeout(controlsHideTimer);

        if (!state.isPlaying) {
            return;
        }

        state.hideControls = true;
        render();
    };

    const getControlsHideDelay = function() {
        return state.isTouchDevice ? 2000 : 750;
    };

    const onControlsMouseover = function() {
        if (!state.playHasBeenClickedAtLeastOneTime) {
            return;
        }

        clearTimeout(controlsHideTimer);
        state.hideControls = false;
        render();

        controlsHideTimer = setTimeout(hideControls, getControlsHideDelay());
    };

    const checkFullscreenSupport = function() {
        if (!elements.video.requestFullscreen || !elements.video.webkitRequestFullscreen) {
            return;
        }
        
        elements.container.classList.add('video-player_can-fullscreen');
    };

    const registerControlVisibilityEvents = function() {
        if (state.isTouchDevice) {
            elements.container.addEventListener('click', onContainerTouch);
            elements.container.removeEventListener('mousemove', onControlsMouseover);
        }
        else {
            elements.container.removeEventListener('click', onContainerTouch);
            elements.container.addEventListener('mousemove', onControlsMouseover);
        }
    };

    const render = function() {
        elements.container.setAttribute('data-state', state.isPlaying ? 'playing' : 'paused');
        elements.container.setAttribute('data-hide-controls', state.hideControls);
        elements.container.setAttribute('data-show-play-overlay', !state.playHasBeenClickedAtLeastOneTime);
    };

    const checkTouchability = function() {
        const query = window.matchMedia(`(hover: hover)`);
        state.isTouchDevice = !query.matches;
        registerControlVisibilityEvents();

        query.addEventListener('change', (queryEvent) => {
            state.isTouchDevice = !queryEvent.matches;
            registerControlVisibilityEvents();
        });
    };

    const init = function(container) {
        if (container === null) {
            console.error('No container for video player found.');
            return;
        }

        elements.container = container;
        elements.video = container.querySelector('video');

        if (elements.video === null) {
            console.error('No video element inside video player found.');
            return;
        }

        elements.video.addEventListener('play', onVideoStateChange);
        elements.video.addEventListener('pause', onVideoStateChange);

        const buttonElements = container.querySelectorAll('[data-element="video-player-button"]');

        for (const buttonElement of buttonElements) {
            const buttonAction = buttonElement.getAttribute('data-action');

            switch (buttonAction) {
                case 'play-toggle':
                    elements.buttons.playToggle.push(buttonElement);
                    buttonElement.addEventListener('click', onPlayToggle);
                    break;

                case 'fullscreen':
                    elements.buttons.fullscreen.push(buttonElement);
                    buttonElement.addEventListener('click', onFullscreen);
                    break;
            }
        }

        checkTouchability();
        checkFullscreenSupport();
    };

    return {
        init: init,
    };
};

const initVideoPlayer = function() {
    const elements = document.querySelectorAll('[data-module="video-player"]');

    if (elements.length == 0) {
        return;
    }

    for (const element of elements) {
        let player = new VideoPlayer();
        player.init(element);
    }
};

documentReady(initVideoPlayer);
