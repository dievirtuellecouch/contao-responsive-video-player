import assert from 'node:assert/strict';
import {LightboxPlayback} from '../assets/scripts/video-player/lightbox-playback.js';

class Element extends EventTarget {
    children = [];
    style = {};
    dataset = {};
    isConnected = true;
    paused = true;
    ended = false;
    muted = true;
    defaultMuted = true;
    loop = false;
    currentTime = 0;
    volume = 0;
    open = false;
    attributes = new Map();
    constructor(tag) { super(); this.tagName = tag; }
    append(...children) { this.children.push(...children); }
    setAttribute(key, value) { this.attributes.set(key, value); }
    removeAttribute(key) { this.attributes.delete(key); if (key === 'src') this.src = ''; }
    querySelectorAll(selector) { return this.children.filter(e => selector.split(', ').includes(e.tagName)); }
    cloneNode() { const e = new Element(this.tagName); Object.assign(e, {src: this.src, media: this.media}); return e; }
    focus() { document.activeElement = this; }
    showModal() { this.open = true; }
    close() { this.open = false; this.dispatchEvent(new Event('close')); }
    remove() { this.isConnected = false; }
    pause() { this.paused = true; }
    play() { this.paused = false; return Promise.resolve(); }
    load() { this.loaded = true; }
    getBoundingClientRect() { return {left: 10, right: 110, top: 10, bottom: 110}; }
}
globalThis.window = {innerWidth: 1200};
globalThis.document = {
    documentElement: {clientWidth: 1180, style: {overflow: 'auto', paddingRight: '4px'}},
    body: new Element('body'),
    createElement: tag => new Element(tag),
    activeElement: null,
};
globalThis.getComputedStyle = () => ({paddingRight: '4px'});
let checks = 0;
for (const playing of [true, false]) {
    for (const muted of [true, false]) {
        const video = new Element('video');
        Object.assign(video, {paused: !playing, muted, defaultMuted: muted, loop: true, currentTime: 37, currentSrc: 'https://example.test/mobile.mp4', poster: 'poster.jpg'});
        const track = new Element('track'); video.append(track);
        const button = new Element('button');
        button.dataset = {labelClose: 'Schließen', labelDialog: 'Videoplayer'};
        button.focus();
        const lightbox = new LightboxPlayback(video, button);
        button.dispatchEvent(new Event('click'));
        const dialog = lightbox.dialog, player = lightbox.player;
        assert.ok(dialog.open);
        assert.equal(dialog.attributes.get('aria-label'), 'Videoplayer');
        assert.equal(player.src, video.currentSrc);
        assert.equal(player.poster, video.poster);
        assert.equal(player.querySelectorAll('track').length, 1);
        assert.equal(player.currentTime, 0);
        assert.equal(player.loop, false);
        assert.equal(player.muted, false);
        assert.equal(player.defaultMuted, false);
        assert.equal(player.volume, 1);
        assert.equal(player.controls, true);
        assert.equal(player.autoplay, true);
        assert.equal(player.playsInline, true);
        assert.equal(player.paused, false);
        assert.equal(video.paused, true);
        assert.equal(document.documentElement.style.overflow, 'hidden');
        lightbox.open(); assert.equal(lightbox.dialog, dialog);
        dialog.dispatchEvent(new Event('cancel', {cancelable: true}));
        assert.equal(lightbox.dialog, null);
        assert.equal(dialog.isConnected, false);
        assert.equal(player.paused, true);
        assert.equal(player.loaded, true);
        assert.equal(player.src, '');
        assert.equal(video.paused, !playing);
        assert.equal(video.currentTime, 37);
        assert.equal(video.muted, muted);
        assert.equal(video.defaultMuted, muted);
        assert.equal(video.loop, true);
        assert.equal(document.activeElement, button);
        assert.equal(document.documentElement.style.overflow, 'auto');
        assert.equal(document.documentElement.style.paddingRight, '4px');
        lightbox.close();
        lightbox.open();
        const reopened = lightbox.dialog;
        dialog.dispatchEvent(new Event('close')); // Old close events cannot close the new modal.
        assert.equal(lightbox.dialog, reopened);
        lightbox.close();
        checks++;
    }
}
// Multiple players: opening another modal releases the old player first.
const first = new LightboxPlayback(new Element('video'), new Element('button'));
const second = new LightboxPlayback(new Element('video'), new Element('button'));
first.open(); second.open();
assert.equal(first.dialog, null);
assert.ok(second.dialog.open);
second.close();
// No currentSrc yet: retain responsive sources for native browser selection.
const fallback = new Element('video');
const source = new Element('source'); source.src = 'mobile.mp4'; source.media = '(max-width: 640px)';
fallback.append(source);
const third = new LightboxPlayback(fallback, new Element('button'));
third.open();
assert.equal(third.player.querySelectorAll('source')[0].src, 'mobile.mp4');
assert.equal(third.player.querySelectorAll('source')[0].media, '(max-width: 640px)');
third.close();
console.log(`PASS: ${checks} playback-state combinations; fresh audible modal, native controls, cleanup, focus, scrolling, reopen, multiple players and responsive fallback.`);
