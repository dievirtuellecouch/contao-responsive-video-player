/** Speaker presentation and mute toggle; never changes playback or position. */
export class SoundPlayback {
    constructor(video, button) {
        this.video = video;
        this.button = button;
        this.onClick = (event) => {
            // A touch click must not also toggle the container's controls.
            event.stopPropagation();
            this.video.muted = this.isAudible();
            if (!this.video.muted && this.video.volume === 0) this.video.volume = 1;
            this.render();
        };
        this.onVolumeChange = () => this.render();
        // Showing the speaker is presentation only: do not modify the media.
        this.render();
        button.addEventListener('click', this.onClick);
        video.addEventListener('emptied', this.onVolumeChange);
        video.addEventListener('volumechange', this.onVolumeChange);
    }

    isAudible() {
        return !this.video.muted && this.video.volume > 0;
    }

    render() {
        const audible = this.isAudible();
        this.button.setAttribute('aria-pressed', audible ? 'true' : 'false');
        this.button.setAttribute('data-sound-state', audible ? 'audible' : 'muted');
        const label = this.button.getAttribute(audible ? 'data-label-mute' : 'data-label-sound');
        if (label) this.button.setAttribute('aria-label', label);
    }

}
