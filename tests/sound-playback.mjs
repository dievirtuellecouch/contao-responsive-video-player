import assert from 'node:assert/strict';
import {SoundPlayback} from '../assets/scripts/video-player/sound-playback.js';

class Video extends EventTarget {
    muted = true;
    defaultMuted = true;
    volume = 1;
    loop = false;
    autoplay = false;
    currentTime = 13;
    paused = true;
    ended = false;
    play() { throw new Error('Speaker must never call play()'); }
    pause() { throw new Error('Speaker must never call pause()'); }
}
class Button extends EventTarget {
    attributes = new Map();
    setAttribute(name, value) { this.attributes.set(name, value); }
    getAttribute(name) { return this.attributes.get(name) ?? null; }
}
let cases = 0;
for (const paused of [true, false]) {
    for (const muted of [true, false]) {
        for (const loop of [true, false]) {
            for (const ended of [true, false]) {
                const video = new Video();
                Object.assign(video, {paused, muted, defaultMuted: muted, loop, ended});
                const button = new Button();
                button.setAttribute('data-label-sound', 'Enable sound');
                button.setAttribute('data-label-mute', 'Mute video');
                new SoundPlayback(video, button);
                const unchanged = {paused, defaultMuted: muted, loop, ended, currentTime: 13, autoplay: false};
                const verify = (expectedMuted) => {
                    for (const [key, value] of Object.entries(unchanged)) assert.equal(video[key], value, key);
                    assert.equal(video.muted, expectedMuted);
                    assert.equal(button.getAttribute('data-sound-state'), expectedMuted ? 'muted' : 'audible');
                    assert.equal(button.getAttribute('aria-pressed'), expectedMuted ? 'false' : 'true');
                    assert.equal(button.getAttribute('aria-label'), expectedMuted ? 'Enable sound' : 'Mute video');
                };
                verify(muted);
                button.dispatchEvent(new Event('click', {bubbles: true}));
                verify(!muted);
                for (const name of ['ended', 'error', 'emptied', 'volumechange']) {
                    video.dispatchEvent(new Event(name));
                    verify(!muted);
                }
                button.dispatchEvent(new Event('click'));
                verify(muted);
                video.volume = 0;
                video.dispatchEvent(new Event('volumechange'));
                assert.equal(button.getAttribute('data-sound-state'), 'muted');
                button.dispatchEvent(new Event('click'));
                assert.equal(video.volume, 1);
                verify(false);
                video.muted = true;
                video.dispatchEvent(new Event('volumechange'));
                verify(true);
                cases += 1;
            }
        }
    }
}
console.log(`PASS: ${cases} speaker cases; only sound toggles, no seek/play/pause/loop changes or end reset.`);
