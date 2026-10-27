// One modal at a time, shared by all instances without changing their settings.
let activeLightbox = null;

export class LightboxPlayback {
    constructor(video, button) {
        this.video = video;
        this.button = button;
        button.addEventListener('click', (event) => {
            event.stopPropagation();
            this.open();
        });
    }

    open() {
        if (this.dialog?.open) return;
        activeLightbox?.close();

        const dialog = document.createElement('dialog');
        dialog.className = 'dvc-video-lightbox';
        dialog.setAttribute('aria-label', this.button.dataset.labelDialog || 'Video player');
        const player = document.createElement('video');
        player.controls = true;
        player.tabIndex = 0;
        player.playsInline = true;
        player.autoplay = true;
        player.muted = false;
        player.defaultMuted = false;
        player.volume = 1;
        player.preload = 'auto';
        // Fresh media element: no loop, mute, IDs, background controls or time offset.
        // Reuse the actual responsive source selected by the browser.
        if (this.video.currentSrc || this.video.src) {
            player.src = this.video.currentSrc || this.video.src;
        } else {
            for (const source of this.video.querySelectorAll('source')) {
                player.append(source.cloneNode(true));
            }
        }
        if (this.video.poster) player.poster = this.video.poster;
        for (const track of this.video.querySelectorAll('track')) {
            player.append(track.cloneNode(true));
        }

        const bar = document.createElement('div');
        bar.className = 'dvc-video-lightbox__bar';
        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'dvc-video-lightbox__close';
        close.setAttribute('aria-label', this.button.dataset.labelClose || 'Close video');
        close.textContent = '×';
        close.addEventListener('click', () => this.close());
        bar.append(close);
        dialog.append(player, bar);
        document.body.append(dialog);

        this.dialog = dialog;
        this.player = player;
        this.wasPlaying = !this.video.paused && !this.video.ended;
        this.previousFocus = document.activeElement;
        const root = document.documentElement;
        this.previousOverflow = root.style.overflow;
        this.previousPadding = root.style.paddingRight;
        const scrollbar = window.innerWidth - root.clientWidth;
        root.style.paddingRight = `${parseFloat(getComputedStyle(root).paddingRight) + scrollbar}px`;
        root.style.overflow = 'hidden';
        this.video.pause();
        activeLightbox = this;

        dialog.addEventListener('cancel', (event) => {
            event.preventDefault();
            this.close();
        });
        dialog.addEventListener('close', () => {
            // A previously removed dialog must not close a newly opened one.
            if (this.dialog === dialog) this.close();
        });
        let startedOutside = false;
        const outside = (event) => {
            const rect = dialog.getBoundingClientRect();
            return event.clientX < rect.left || event.clientX > rect.right
                || event.clientY < rect.top || event.clientY > rect.bottom;
        };
        dialog.addEventListener('pointerdown', (event) => { startedOutside = outside(event); });
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog && startedOutside && outside(event)) this.close();
        });
        dialog.addEventListener('keydown', (event) => {
            if (event.key !== 'Tab') return;
            // Explicitly wrap focus: Chromium can otherwise focus the body
            // after the last action when native media controls use shadow DOM.
            if (!event.shiftKey && document.activeElement === close) {
                event.preventDefault();
                player.focus({preventScroll: true});
            } else if (event.shiftKey && document.activeElement === player) {
                event.preventDefault();
                close.focus({preventScroll: true});
            }
        });
        dialog.showModal();
        close.focus({preventScroll: true});
        // Keep play() inside the user gesture so audible playback is permitted.
        // If a browser still rejects playback, native controls remain available.
        player.play()?.catch(() => {});
    }

    close() {
        const dialog = this.dialog;
        if (!dialog) return;
        this.dialog = null;
        this.player.pause();
        this.player.removeAttribute('src');
        this.player.querySelectorAll('source, track').forEach((node) => node.remove());
        this.player.load();
        if (dialog.open) dialog.close();
        dialog.remove();
        document.documentElement.style.overflow = this.previousOverflow;
        document.documentElement.style.paddingRight = this.previousPadding;
        if (activeLightbox === this) activeLightbox = null;
        if (this.wasPlaying && this.video.isConnected) this.video.play()?.catch(() => {});
        if (this.previousFocus?.isConnected) this.previousFocus.focus({preventScroll: true});
    }
}
