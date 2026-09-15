import test from 'node:test';
import assert from 'node:assert/strict';
class Element extends EventTarget {
    dataset = {}; hidden = false; attributes = {}; children = [];
    classList = { toggle: (k,v) => {this.attributes[k]=v;} };
    setAttribute(k,v) { this.attributes[k]=v; }
    contains(e) { return this===e || this.children.includes(e); }
}
const documentMock = new Element();
documentMock.readyState='loading'; documentMock.activeElement=null;
globalThis.document=documentMock;
let touch=false;
globalThis.window={matchMedia:()=>Object.assign(new Element(),{matches:!touch})};
const {initVideoPlayer}=await import('../assets/scripts/video-player/index.js');
function fixture(fullscreen='requestFullscreen') {
    const container=new Element(),video=new Element(),play=new Element(),full=new Element();
    Object.assign(play.dataset,{labelPlay:'Play',labelPause:'Pause'});
    Object.assign(video,{paused:true,ended:false,playCalls:0,pauseCalls:0,fullCalls:0,
      play(){this.playCalls++;this.paused=false;this.dispatchEvent(new Event('play'));return Promise.resolve();},
      pause(){this.pauseCalls++;this.paused=true;this.dispatchEvent(new Event('pause'));}});
    if(fullscreen)video[fullscreen]=function(){this.fullCalls++;return Promise.resolve();};
    container.children=[play,full];container.querySelector=()=>video;
    container.querySelectorAll=s=>s.includes('play-toggle')?[play]:[full];
    initVideoPlayer(container);return {container,video,play,full};
}
const click=e=>e.dispatchEvent(new Event('click'));
test('play/pause and labels follow media events; initialization is idempotent',()=>{
 const f=fixture();initVideoPlayer(f.container);assert.equal(f.container.dataset.showPlayOverlay,'true');click(f.play);
 assert.equal(f.video.playCalls,1);assert.equal(f.container.dataset.state,'playing');assert.equal(f.play.attributes['aria-label'],'Pause');
 click(f.play);assert.equal(f.video.pauseCalls,1);assert.equal(f.container.dataset.state,'paused');assert.equal(f.play.attributes['aria-label'],'Play');
});
for(const api of ['requestFullscreen','webkitRequestFullscreen','webkitEnterFullscreen'])test(`fullscreen API ${api}`,()=>{
 const f=fixture(api);assert.equal(f.full.hidden,false);click(f.full);assert.equal(f.video.fullCalls,1);
});
test('unsupported fullscreen is hidden',()=>{const f=fixture(null);assert.equal(f.full.hidden,true);click(f.full);assert.equal(f.video.fullCalls,0);});
test('rejected playback remains retryable without an unhandled promise',async()=>{
 const f=fixture();f.video.play=()=>Promise.reject(new Error('NotAllowedError'));click(f.play);await new Promise(r=>setImmediate(r));
 assert.equal(f.container.dataset.state,'paused');assert.equal(f.container.dataset.showPlayOverlay,'true');assert.equal(f.container.dataset.hideControls,'false');
});
test('rejected fullscreen preserves controls',async()=>{
 const f=fixture();f.video.requestFullscreen=()=>Promise.reject(new Error('not allowed'));
 // A new player picks up the rejected method.
 delete f.container.dataset.videoPlayerInitialized;initVideoPlayer(f.container);click(f.full);await new Promise(r=>setImmediate(r));assert.equal(f.container.dataset.hideControls,'false');
});
test('ended resets control state; external play/pause sync',()=>{
 const f=fixture();f.video.play();f.video.ended=true;f.video.paused=true;f.video.dispatchEvent(new Event('ended'));
 assert.equal(f.container.dataset.state,'paused');assert.equal(f.play.attributes['aria-label'],'Play');
});
test('keyboard focus keeps controls visible',()=>{
 const f=fixture();f.video.play();document.activeElement=f.play;f.container.dispatchEvent(new Event('focusin'));
 assert.equal(f.container.dataset.hideControls,'false');f.video.pause();document.activeElement=null;
});
test('touch tap reveals/hides controls',()=>{
 touch=true;const f=fixture();f.video.play();click(f.container);assert.equal(f.container.dataset.hideControls,'true');
 click(f.container);assert.equal(f.container.dataset.hideControls,'false');f.video.pause();touch=false;
});
test('missing video is safely ignored',()=>{const c=new Element();c.querySelector=()=>null;initVideoPlayer(c);assert.equal(c.dataset.videoPlayerInitialized,undefined);});
