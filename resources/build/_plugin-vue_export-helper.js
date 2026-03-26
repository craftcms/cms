const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["./bg-BG.js","./bg2.js","./cs-CZ.js","./cs2.js","./de-DE.js","./de2.js","./en-AU.js","./en2.js","./en-GB.js","./en-US.js","./es-ES.js","./es2.js","./fr-FR.js","./fr2.js","./fr-BE.js","./hu-HU.js","./hu2.js","./it-IT.js","./it2.js","./nl-BE.js","./nl2.js","./nl-NL.js","./pl-PL.js","./pl2.js","./ro-RO.js","./ro2.js","./ru-RU.js","./ru2.js","./sk-SK.js","./sk2.js","./tr-TR.js","./tr.js","./uk-UA.js","./uk2.js","./bg-BG2.js","./bg3.js","./cs-CZ2.js","./cs3.js","./de-DE2.js","./de3.js","./en-AU2.js","./en3.js","./en-GB2.js","./en-US2.js","./es-ES2.js","./es3.js","./fr-FR2.js","./fr3.js","./fr-BE2.js","./hu-HU2.js","./hu3.js","./it-IT2.js","./it3.js","./nl-BE2.js","./nl3.js","./nl-NL2.js","./pl-PL2.js","./pl3.js","./ro-RO2.js","./ro3.js","./ru-RU2.js","./ru3.js","./sk-SK2.js","./sk3.js","./uk-UA2.js","./uk3.js"])))=>i.map(i=>d[i]);
import{a as e,c as t,f as n,i as r,l as i,o as a,r as o,s,u as c}from"./Queue-wGK97jCw.js";import{t as l}from"./decorate-DpHfxayW.js";import{a as u,c as d,d as f,f as p,i as m,n as h,p as g,r as _,t as v}from"./lit.js";import{a as y,i as b,o as x,r as S,t as C}from"./decorators.js";import{a as w,i as T,n as E,o as D,s as ee}from"./nav-item-D3exy0bq.js";import"./nav-list-DezDYLKE.js";var te=``,ne=``;function re(e){te=e}function ie(e=``){if(!te){let e=document.querySelector(`[data-webawesome]`);if(e?.hasAttribute(`data-webawesome`)){let t=new URL(e.getAttribute(`data-webawesome`)??``,window.location.href).pathname;re(t)}else{let e=[...document.getElementsByTagName(`script`)].find(e=>e.src.endsWith(`webawesome.js`)||e.src.endsWith(`webawesome.loader.js`)||e.src.endsWith(`webawesome.ssr-loader.js`));e&&re(String(e.getAttribute(`src`)).split(`/`).slice(0,-1).join(`/`))}}return te.replace(/\/$/,``)+(e?`/${e.replace(/^\//,``)}`:``)}function ae(e){ne=e}function oe(){if(!ne){let e=document.querySelector(`[data-fa-kit-code]`);e&&ae(e.getAttribute(`data-fa-kit-code`)||``)}return ne}var se=`7.0.1`;function ce(e,t,n){let r=oe(),i=r.length>0,a=`solid`;return t===`notdog`?(n===`solid`&&(a=`solid`),n===`duo-solid`&&(a=`duo-solid`),`https://ka-p.fontawesome.com/releases/v${se}/svgs/notdog-${a}/${e}.svg?token=${encodeURIComponent(r)}`):t===`chisel`?`https://ka-p.fontawesome.com/releases/v${se}/svgs/chisel-regular/${e}.svg?token=${encodeURIComponent(r)}`:t===`etch`?`https://ka-p.fontawesome.com/releases/v${se}/svgs/etch-solid/${e}.svg?token=${encodeURIComponent(r)}`:t===`jelly`?(n===`regular`&&(a=`regular`),n===`duo-regular`&&(a=`duo-regular`),n===`fill-regular`&&(a=`fill-regular`),`https://ka-p.fontawesome.com/releases/v${se}/svgs/jelly-${a}/${e}.svg?token=${encodeURIComponent(r)}`):t===`slab`?((n===`solid`||n===`regular`)&&(a=`regular`),n===`press-regular`&&(a=`press-regular`),`https://ka-p.fontawesome.com/releases/v${se}/svgs/slab-${a}/${e}.svg?token=${encodeURIComponent(r)}`):t===`thumbprint`?`https://ka-p.fontawesome.com/releases/v${se}/svgs/thumbprint-light/${e}.svg?token=${encodeURIComponent(r)}`:t===`whiteboard`?`https://ka-p.fontawesome.com/releases/v${se}/svgs/whiteboard-semibold/${e}.svg?token=${encodeURIComponent(r)}`:(t===`classic`&&(n===`thin`&&(a=`thin`),n===`light`&&(a=`light`),n===`regular`&&(a=`regular`),n===`solid`&&(a=`solid`)),t===`sharp`&&(n===`thin`&&(a=`sharp-thin`),n===`light`&&(a=`sharp-light`),n===`regular`&&(a=`sharp-regular`),n===`solid`&&(a=`sharp-solid`)),t===`duotone`&&(n===`thin`&&(a=`duotone-thin`),n===`light`&&(a=`duotone-light`),n===`regular`&&(a=`duotone-regular`),n===`solid`&&(a=`duotone`)),t===`sharp-duotone`&&(n===`thin`&&(a=`sharp-duotone-thin`),n===`light`&&(a=`sharp-duotone-light`),n===`regular`&&(a=`sharp-duotone-regular`),n===`solid`&&(a=`sharp-duotone-solid`)),t===`brands`&&(a=`brands`),i?`https://ka-p.fontawesome.com/releases/v${se}/svgs/${a}/${e}.svg?token=${encodeURIComponent(r)}`:`https://ka-f.fontawesome.com/releases/v${se}/svgs/${a}/${e}.svg`)}var le={name:`default`,resolver:(e,t=`classic`,n=`solid`)=>ce(e,t,n),mutator:(e,t)=>{if(t?.family&&!e.hasAttribute(`data-duotone-initialized`)){let{family:n,variant:r}=t;if(n===`duotone`||n===`sharp-duotone`||n===`notdog`&&r===`duo-solid`||n===`jelly`&&r===`duo-regular`||n===`thumbprint`){let n=[...e.querySelectorAll(`path`)],r=n.find(e=>!e.hasAttribute(`opacity`)),i=n.find(e=>e.hasAttribute(`opacity`));if(!r||!i)return;if(r.setAttribute(`data-duotone-primary`,``),i.setAttribute(`data-duotone-secondary`,``),t.swapOpacity&&r&&i){let e=i.getAttribute(`opacity`)||`0.4`;r.style.setProperty(`--path-opacity`,e),i.style.setProperty(`--path-opacity`,`1`)}e.setAttribute(`data-duotone-initialized`,``)}}}},ue=`modulepreload`,de=function(e,t){return new URL(e,t).href},fe={},O=function(e,t,n){let r=Promise.resolve();if(t&&t.length>0){let e=document.getElementsByTagName(`link`),i=document.querySelector(`meta[property=csp-nonce]`),a=i?.nonce||i?.getAttribute(`nonce`);function o(e){return Promise.all(e.map(e=>Promise.resolve(e).then(e=>({status:`fulfilled`,value:e}),e=>({status:`rejected`,reason:e}))))}r=o(t.map(t=>{if(t=de(t,n),t in fe)return;fe[t]=!0;let r=t.endsWith(`.css`),i=r?`[rel="stylesheet"]`:``;if(n)for(let n=e.length-1;n>=0;n--){let i=e[n];if(i.href===t&&(!r||i.rel===`stylesheet`))return}else if(document.querySelector(`link[href="${t}"]${i}`))return;let o=document.createElement(`link`);if(o.rel=r?`stylesheet`:ue,r||(o.as=`script`),o.crossOrigin=``,o.href=t,a&&o.setAttribute(`nonce`,a),document.head.appendChild(o),r)return new Promise((e,n)=>{o.addEventListener(`load`,e),o.addEventListener(`error`,()=>n(Error(`Unable to preload CSS for ${t}`)))})}))}function i(e){let t=new Event(`vite:preloadError`,{cancelable:!0});if(t.payload=e,window.dispatchEvent(t),!t.defaultPrevented)throw e}return r.then(t=>{for(let e of t||[])e.status===`rejected`&&i(e.reason);return e().catch(i)})};new MutationObserver(e=>{for(let{addedNodes:t}of e)for(let e of t)e.nodeType===Node.ELEMENT_NODE&&pe(e)});async function pe(e){let t=e instanceof Element?e.tagName.toLowerCase():``,n=t?.startsWith(`wa-`),r=[...e.querySelectorAll(`:not(:defined)`)].map(e=>e.tagName.toLowerCase()).filter(e=>e.startsWith(`wa-`));n&&!customElements.get(t)&&r.push(t);let i=[...new Set(r)],a=await Promise.allSettled(i.map(e=>me(e)));for(let e of a)e.status===`rejected`&&console.warn(e.reason);await new Promise(requestAnimationFrame),e.dispatchEvent(new CustomEvent(`wa-discovery-complete`,{bubbles:!1,cancelable:!1,composed:!0}))}function me(e){if(customElements.get(e))return Promise.resolve();let t=e.replace(/^wa-/i,``),n=ie(`components/${t}/${t}.js`);return new Promise((t,r)=>{O(()=>import(n).then(()=>t()),[],import.meta.url).catch(()=>r(Error(`Unable to autoload <${e}> from ${n}`)))})}var he=new Set,ge=new Map,_e,ve=`ltr`,ye=`en`,be=typeof MutationObserver<`u`&&typeof document<`u`&&document.documentElement!==void 0;if(be){let e=new MutationObserver(Se);ve=document.documentElement.dir||`ltr`,ye=document.documentElement.lang||navigator.language,e.observe(document.documentElement,{attributes:!0,attributeFilter:[`dir`,`lang`]})}function xe(...e){e.map(e=>{let t=e.$code.toLowerCase();ge.has(t)?ge.set(t,Object.assign(Object.assign({},ge.get(t)),e)):ge.set(t,e),_e||=e}),Se()}function Se(){be&&(ve=document.documentElement.dir||`ltr`,ye=document.documentElement.lang||navigator.language),[...he.keys()].map(e=>{typeof e.requestUpdate==`function`&&e.requestUpdate()})}var Ce=class{constructor(e){this.host=e,this.host.addController(this)}hostConnected(){he.add(this.host)}hostDisconnected(){he.delete(this.host)}dir(){return`${this.host.dir||ve}`.toLowerCase()}lang(){return`${this.host.lang||ye}`.toLowerCase()}getTranslationData(e){let t=new Intl.Locale(e.replace(/_/g,`-`)),n=t?.language.toLowerCase(),r=(t?.region)?.toLowerCase()??``;return{locale:t,language:n,region:r,primary:ge.get(`${n}-${r}`),secondary:ge.get(n)}}exists(e,t){let{primary:n,secondary:r}=this.getTranslationData(t.lang??this.lang());return t=Object.assign({includeFallback:!1},t),!!(n&&n[e]||r&&r[e]||t.includeFallback&&_e&&_e[e])}term(e,...t){let{primary:n,secondary:r}=this.getTranslationData(this.lang()),i;if(n&&n[e])i=n[e];else if(r&&r[e])i=r[e];else if(_e&&_e[e])i=_e[e];else return console.error(`No translation found for: ${String(e)}`),String(e);return typeof i==`function`?i(...t):i}date(e,t){return e=new Date(e),new Intl.DateTimeFormat(this.lang(),t).format(e)}number(e,t){return e=Number(e),isNaN(e)?``:new Intl.NumberFormat(this.lang(),t).format(e)}relativeTime(e,t,n){return new Intl.RelativeTimeFormat(this.lang(),n).format(e,t)}},we={$code:`en`,$name:`English`,$dir:`ltr`,carousel:`Carousel`,clearEntry:`Clear entry`,close:`Close`,copied:`Copied`,copy:`Copy`,currentValue:`Current value`,error:`Error`,goToSlide:(e,t)=>`Go to slide ${e} of ${t}`,hidePassword:`Hide password`,loading:`Loading`,nextSlide:`Next slide`,numOptionsSelected:e=>e===0?`No options selected`:e===1?`1 option selected`:`${e} options selected`,pauseAnimation:`Pause animation`,playAnimation:`Play animation`,previousSlide:`Previous slide`,progress:`Progress`,remove:`Remove`,resize:`Resize`,scrollableRegion:`Scrollable region`,scrollToEnd:`Scroll to end`,scrollToStart:`Scroll to start`,selectAColorFromTheScreen:`Select a color from the screen`,showPassword:`Show password`,slideNum:e=>`Slide ${e}`,toggleColorFormat:`Toggle color format`,zoomIn:`Zoom in`,zoomOut:`Zoom out`};xe(we);var Te=we,Ee=class extends Ce{};xe(Te);function De(e){return`data:image/svg+xml,${encodeURIComponent(e)}`}var Oe={solid:{check:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M434.8 70.1c14.3 10.4 17.5 30.4 7.1 44.7l-256 352c-5.5 7.6-14 12.3-23.4 13.1s-18.5-2.7-25.1-9.3l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l101.5 101.5 234-321.7c10.4-14.3 30.4-17.5 44.7-7.1z"/></svg>`,"chevron-down":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M201.4 406.6c12.5 12.5 32.8 12.5 45.3 0l192-192c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L224 338.7 54.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l192 192z"/></svg>`,"chevron-left":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l192 192c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L77.3 256 246.6 86.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-192 192z"/></svg>`,"chevron-right":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M311.1 233.4c12.5 12.5 12.5 32.8 0 45.3l-192 192c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L243.2 256 73.9 86.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l192 192z"/></svg>`,circle:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0z"/></svg>`,eyedropper:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M341.6 29.2l-101.6 101.6-9.4-9.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3l-9.4-9.4 101.6-101.6c39-39 39-102.2 0-141.1s-102.2-39-141.1 0zM55.4 323.3c-15 15-23.4 35.4-23.4 56.6l0 42.4-26.6 39.9c-8.5 12.7-6.8 29.6 4 40.4s27.7 12.5 40.4 4l39.9-26.6 42.4 0c21.2 0 41.6-8.4 56.6-23.4l109.4-109.4-45.3-45.3-109.4 109.4c-3 3-7.1 4.7-11.3 4.7l-36.1 0 0-36.1c0-4.2 1.7-8.3 4.7-11.3l109.4-109.4-45.3-45.3-109.4 109.4z"/></svg>`,"grip-vertical":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M128 40c0-22.1-17.9-40-40-40L40 0C17.9 0 0 17.9 0 40L0 88c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zm0 192c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zM0 424l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zM320 40c0-22.1-17.9-40-40-40L232 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zM192 232l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zM320 424c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48z"/></svg>`,indeterminate:`<svg part="indeterminate-icon" class="icon" viewBox="0 0 16 16"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" stroke-linecap="round"><g stroke="currentColor" stroke-width="2"><g transform="translate(2.285714 6.857143)"><path d="M10.2857143,1.14285714 L1.14285714,1.14285714"/></g></g></g></svg>`,minus:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M0 256c0-17.7 14.3-32 32-32l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 288c-17.7 0-32-14.3-32-32z"/></svg>`,pause:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M48 32C21.5 32 0 53.5 0 80L0 432c0 26.5 21.5 48 48 48l64 0c26.5 0 48-21.5 48-48l0-352c0-26.5-21.5-48-48-48L48 32zm224 0c-26.5 0-48 21.5-48 48l0 352c0 26.5 21.5 48 48 48l64 0c26.5 0 48-21.5 48-48l0-352c0-26.5-21.5-48-48-48l-64 0z"/></svg>`,play:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M91.2 36.9c-12.4-6.8-27.4-6.5-39.6 .7S32 57.9 32 72l0 368c0 14.1 7.5 27.2 19.6 34.4s27.2 7.5 39.6 .7l336-184c12.8-7 20.8-20.5 20.8-35.1s-8-28.1-20.8-35.1l-336-184z"/></svg>`,star:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M309.5-18.9c-4.1-8-12.4-13.1-21.4-13.1s-17.3 5.1-21.4 13.1L193.1 125.3 33.2 150.7c-8.9 1.4-16.3 7.7-19.1 16.3s-.5 18 5.8 24.4l114.4 114.5-25.2 159.9c-1.4 8.9 2.3 17.9 9.6 23.2s16.9 6.1 25 2L288.1 417.6 432.4 491c8 4.1 17.7 3.3 25-2s11-14.2 9.6-23.2L441.7 305.9 556.1 191.4c6.4-6.4 8.6-15.8 5.8-24.4s-10.1-14.9-19.1-16.3L383 125.3 309.5-18.9z"/></svg>`,user:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M224 248a120 120 0 1 0 0-240 120 120 0 1 0 0 240zm-29.7 56C95.8 304 16 383.8 16 482.3 16 498.7 29.3 512 45.7 512l356.6 0c16.4 0 29.7-13.3 29.7-29.7 0-98.5-79.8-178.3-178.3-178.3l-59.4 0z"/></svg>`,xmark:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M55.1 73.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L147.2 256 9.9 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192.5 301.3 329.9 438.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.8 256 375.1 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192.5 210.7 55.1 73.4z"/></svg>`},regular:{"circle-question":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M464 256a208 208 0 1 0 -416 0 208 208 0 1 0 416 0zM0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0zm256-80c-17.7 0-32 14.3-32 32 0 13.3-10.7 24-24 24s-24-10.7-24-24c0-44.2 35.8-80 80-80s80 35.8 80 80c0 47.2-36 67.2-56 74.5l0 3.8c0 13.3-10.7 24-24 24s-24-10.7-24-24l0-8.1c0-20.5 14.8-35.2 30.1-40.2 6.4-2.1 13.2-5.5 18.2-10.3 4.3-4.2 7.7-10 7.7-19.6 0-17.7-14.3-32-32-32zM224 368a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>`,"circle-xmark":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M256 48a208 208 0 1 1 0 416 208 208 0 1 1 0-416zm0 464a256 256 0 1 0 0-512 256 256 0 1 0 0 512zM167 167c-9.4 9.4-9.4 24.6 0 33.9l55 55-55 55c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l55-55 55 55c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-55-55 55-55c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-55 55-55-55c-9.4-9.4-24.6-9.4-33.9 0z"/></svg>`,copy:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M384 336l-192 0c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l133.5 0c4.2 0 8.3 1.7 11.3 4.7l58.5 58.5c3 3 4.7 7.1 4.7 11.3L400 320c0 8.8-7.2 16-16 16zM192 384l192 0c35.3 0 64-28.7 64-64l0-197.5c0-17-6.7-33.3-18.7-45.3L370.7 18.7C358.7 6.7 342.5 0 325.5 0L192 0c-35.3 0-64 28.7-64 64l0 256c0 35.3 28.7 64 64 64zM64 128c-35.3 0-64 28.7-64 64L0 448c0 35.3 28.7 64 64 64l192 0c35.3 0 64-28.7 64-64l0-16-48 0 0 16c0 8.8-7.2 16-16 16L64 464c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l16 0 0-48-16 0z"/></svg>`,eye:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M288 80C222.8 80 169.2 109.6 128.1 147.7 89.6 183.5 63 226 49.4 256 63 286 89.6 328.5 128.1 364.3 169.2 402.4 222.8 432 288 432s118.8-29.6 159.9-67.7C486.4 328.5 513 286 526.6 256 513 226 486.4 183.5 447.9 147.7 406.8 109.6 353.2 80 288 80zM95.4 112.6C142.5 68.8 207.2 32 288 32s145.5 36.8 192.6 80.6c46.8 43.5 78.1 95.4 93 131.1 3.3 7.9 3.3 16.7 0 24.6-14.9 35.7-46.2 87.7-93 131.1-47.1 43.7-111.8 80.6-192.6 80.6S142.5 443.2 95.4 399.4c-46.8-43.5-78.1-95.4-93-131.1-3.3-7.9-3.3-16.7 0-24.6 14.9-35.7 46.2-87.7 93-131.1zM288 336c44.2 0 80-35.8 80-80 0-29.6-16.1-55.5-40-69.3-1.4 59.7-49.6 107.9-109.3 109.3 13.8 23.9 39.7 40 69.3 40zm-79.6-88.4c2.5 .3 5 .4 7.6 .4 35.3 0 64-28.7 64-64 0-2.6-.2-5.1-.4-7.6-37.4 3.9-67.2 33.7-71.1 71.1zm45.6-115c10.8-3 22.2-4.5 33.9-4.5 8.8 0 17.5 .9 25.8 2.6 .3 .1 .5 .1 .8 .2 57.9 12.2 101.4 63.7 101.4 125.2 0 70.7-57.3 128-128 128-61.6 0-113-43.5-125.2-101.4-1.8-8.6-2.8-17.5-2.8-26.6 0-11 1.4-21.8 4-32 .2-.7 .3-1.3 .5-1.9 11.9-43.4 46.1-77.6 89.5-89.5z"/></svg>`,"eye-slash":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M41-24.9c-9.4-9.4-24.6-9.4-33.9 0S-2.3-.3 7 9.1l528 528c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-96.4-96.4c2.7-2.4 5.4-4.8 8-7.2 46.8-43.5 78.1-95.4 93-131.1 3.3-7.9 3.3-16.7 0-24.6-14.9-35.7-46.2-87.7-93-131.1-47.1-43.7-111.8-80.6-192.6-80.6-56.8 0-105.6 18.2-146 44.2L41-24.9zM176.9 111.1c32.1-18.9 69.2-31.1 111.1-31.1 65.2 0 118.8 29.6 159.9 67.7 38.5 35.7 65.1 78.3 78.6 108.3-13.6 30-40.2 72.5-78.6 108.3-3.1 2.8-6.2 5.6-9.4 8.4L393.8 328c14-20.5 22.2-45.3 22.2-72 0-70.7-57.3-128-128-128-26.7 0-51.5 8.2-72 22.2l-39.1-39.1zm182 182l-108-108c11.1-5.8 23.7-9.1 37.1-9.1 44.2 0 80 35.8 80 80 0 13.4-3.3 26-9.1 37.1zM103.4 173.2l-34-34c-32.6 36.8-55 75.8-66.9 104.5-3.3 7.9-3.3 16.7 0 24.6 14.9 35.7 46.2 87.7 93 131.1 47.1 43.7 111.8 80.6 192.6 80.6 37.3 0 71.2-7.9 101.5-20.6L352.2 422c-20 6.4-41.4 10-64.2 10-65.2 0-118.8-29.6-159.9-67.7-38.5-35.7-65.1-78.3-78.6-108.3 10.4-23.1 28.6-53.6 54-82.8z"/></svg>`,star:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M288.1-32c9 0 17.3 5.1 21.4 13.1L383 125.3 542.9 150.7c8.9 1.4 16.3 7.7 19.1 16.3s.5 18-5.8 24.4L441.7 305.9 467 465.8c1.4 8.9-2.3 17.9-9.6 23.2s-17 6.1-25 2L288.1 417.6 143.8 491c-8 4.1-17.7 3.3-25-2s-11-14.2-9.6-23.2L134.4 305.9 20 191.4c-6.4-6.4-8.6-15.8-5.8-24.4s10.1-14.9 19.1-16.3l159.9-25.4 73.6-144.2c4.1-8 12.4-13.1 21.4-13.1zm0 76.8L230.3 158c-3.5 6.8-10 11.6-17.6 12.8l-125.5 20 89.8 89.9c5.4 5.4 7.9 13.1 6.7 20.7l-19.8 125.5 113.3-57.6c6.8-3.5 14.9-3.5 21.8 0l113.3 57.6-19.8-125.5c-1.2-7.6 1.3-15.3 6.7-20.7l89.8-89.9-125.5-20c-7.6-1.2-14.1-6-17.6-12.8L288.1 44.8z"/></svg>`}},ke={name:`system`,resolver:(e,t=`classic`,n=`solid`)=>{let r=Oe[n][e]??Oe.regular[e]??Oe.regular[`circle-question`];return r?De(r):``}},Ae=`classic`,je=[le,ke],Me=[];function Ne(e){Me.push(e)}function Pe(e){Me=Me.filter(t=>t!==e)}function Fe(e){return je.find(t=>t.name===e)}function Ie(e,t){Le(e),je.push({name:e,resolver:t.resolver,mutator:t.mutator,spriteSheet:t.spriteSheet}),Me.forEach(t=>{t.library===e&&t.setIcon()})}function Le(e){je=je.filter(t=>t.name!==e)}function Re(){return Ae}var ze=Object.defineProperty,Be=Object.getOwnPropertyDescriptor,Ve=e=>{throw TypeError(e)},k=(e,t,n,r)=>{for(var i=r>1?void 0:r?Be(t,n):t,a=e.length-1,o;a>=0;a--)(o=e[a])&&(i=(r?o(t,n,i):o(i))||i);return r&&i&&ze(t,n,i),i},He=(e,t,n)=>t.has(e)||Ve(`Cannot `+n),Ue=(e,t,n)=>(He(e,t,`read from private field`),n?n.call(e):t.get(e)),We=(e,t,n)=>t.has(e)?Ve(`Cannot add the same private member more than once`):t instanceof WeakSet?t.add(e):t.set(e,n),Ge=(e,t,n,r)=>(He(e,t,`write to private field`),r?r.call(e,n):t.set(e,n),n),Ke={alert:`triangle-exclamation`,asc:`arrow-down-short-wide`,asset:`image`,assets:`image`,circleuarr:`circle-arrow-up`,collapse:`down-left-and-up-right-to-center`,condition:`diamond`,darr:`arrow-down`,date:`calendar`,desc:`arrow-down-wide-short`,disabled:`circle-dashed`,done:`circle-check`,downangle:`angle-down`,draft:`scribble`,edit:`pencil`,enabled:`circle`,expand:`up-right-and-down-left-from-center`,external:`arrow-up-right-from-square`,field:`pen-to-square`,help:`circle-question`,home:`house`,info:`circle-info`,insecure:`unlock`,larr:`arrow-left`,layout:`table-layout`,leftangle:`angle-left`,listrtl:`list-flip`,location:`location-dot`,mail:`envelope`,menu:`bars`,move:`grip-dots`,newstamp:`certificate`,paperplane:`paper-plane`,plugin:`plug`,rarr:`arrow-right`,refresh:`arrows-rotate`,remove:`xmark`,rightangle:`angle-right`,rotate:`rotate-left`,routes:`signs-post`,search:`magnifying-glass`,secure:`lock`,settings:`gear`,shareleft:`share-flip`,shuteye:`eye-slash`,"sidebar-left":`sidebar`,"sidebar-right":`sidebar-flip`,"sidebar-start":`sidebar`,"sidebar-end":`sidebar-flip`,structure:`list-tree`,structurertl:`list-tree-flip`,template:`file-code`,time:`clock`,tool:`wrench`,uarr:`arrow-up`,upangle:`angle-up`,view:`eye`,wand:`wand-magic-sparkles`};function qe(e,t=`classic`,n=`regular`){let r=`solid`,i=n,a=e.endsWith(`.svg`)?e.split(`.svg`)[0]:e;if(e.includes(`/`)){let[t,...n]=e.split(`/`);i=t??i,a=n.join(`/`)}return i===`thin`?r=`thin`:i===`light`?r=`light`:i===`regular`?r=`regular`:i===`solid`&&(r=`solid`),t===`brands`&&(r=`brands`),i===`custom-icons`&&(r=`custom-icons`),a=Ke[a]??a,`/vendor/craft/icons/${r}/${a}.svg`}function Je(){Ie(`default`,{resolver:(e,t=`classic`,n=`solid`)=>qe(e,t,n),mutator:e=>e.setAttribute(`fill`,`currentColor`)})}var Ye=class extends HTMLElement{constructor(...e){super(...e),this.cookieName=null,this.state=`collapsed`,this.expanded=!1,this.handleOpen=()=>{this.trigger?.setAttribute(`aria-expanded`,`true`),this.expanded=!0,this.dispatchEvent(new CustomEvent(`open`)),this.target&&(this.target.dataset.state=`expanded`),this.cookieName&&window.Craft?.setCookie(this.cookieName,`expanded`)},this.handleClose=()=>{this.trigger?.setAttribute(`aria-expanded`,`false`),this.expanded=!1,this.dispatchEvent(new CustomEvent(`close`)),this.target&&(this.target.dataset.state=`collapsed`),this.cookieName&&window.Craft?.setCookie(this.cookieName,`collapsed`)}}get trigger(){return this.querySelector(`button[type="button"]`)}get target(){if(!this.trigger)return console.warn(`No trigger found for disclosure.`),null;let e=this.trigger.getAttribute(`aria-controls`);return e?document.getElementById(e):(console.warn(`No target selector found for disclosure.`),null)}connectedCallback(){if(!this.trigger){console.error(`craft-disclosure elements must include a button`,this);return}if(!this.target){console.error(`No target with id ${this.trigger.getAttribute(`aria-controls`)} found for disclosure. `,this.trigger);return}this.cookieName=this.getAttribute(`cookie-name`),this.state=this.getAttribute(`state`)??`expanded`,this.trigger.setAttribute(`aria-expanded`,this.state===`expanded`?`true`:`false`),this.trigger.addEventListener(`click`,this.toggle.bind(this)),this.state===`expanded`?this.open():this.close()}disconnectedCallback(){this.open(),this.trigger?.removeEventListener(`click`,this.toggle.bind(this))}attributeChangedCallback(e,t,n){e===`state`&&(n===`expanded`?this.handleOpen():this.handleClose())}toggle(){this.expanded?this.close():this.open()}open(){this.setAttribute(`state`,`expanded`)}close(){this.setAttribute(`state`,`collapsed`)}};Ye.observedAttributes=[`state`],customElements.get(`craft-disclosure`)||customElements.define(`craft-disclosure`,Ye);var Xe=p`
  :host {
    --_size: var(--size, 24px);
  }

  .wrapper {
    display: inline-flex;
    justify-content: center;
  }

  .hidden {
    display: none;
  }

  @keyframes rotator {
    0% {
      transform: rotate(0);
    }

    100% {
      transform: rotate(1turn);
    }
  }

  .spinner {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--_size);
    height: var(--_size);
  }

  .spinner:before {
    display: block;
    content: '';
    font-size: 0;
    animation: rotator 0.7s linear infinite;
    box-sizing: border-box;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    border: 2px solid transparent;
    border-inline-end-color: currentcolor;
    border-block-end-color: currentcolor;
    opacity: 0.8;
  }
`,Ze=class extends v{constructor(...e){super(...e),this.visible=!0}show(){this.visible=!0,this.dispatchEvent(new CustomEvent(`show`))}hide(){this.visible=!1,this.dispatchEvent(new CustomEvent(`hide`))}focus(){this.wrapper?.focus()}render(){return d`
      <div
        tabindex="-1"
        class="${T({wrapper:!0,hidden:!this.visible})}"
      >
        <div class="spinner"></div>
        <span class="message visually-hidden"><slot /></span>
      </div>
    `}};Ze.styles=[Xe],l([y({reflect:!0})],Ze.prototype,`visible`,void 0),l([S(`.wrapper`)],Ze.prototype,`wrapper`,void 0),customElements.get(`craft-spinner`)||customElements.define(`craft-spinner`,Ze);var Qe=class extends Event{constructor(){super(`wa-reposition`,{bubbles:!0,cancelable:!1,composed:!0})}},$e=`:host {
  box-sizing: border-box !important;
}

:host *,
:host *::before,
:host *::after {
  box-sizing: inherit !important;
}

[hidden] {
  display: none !important;
}
`,et,tt=class extends v{constructor(){super(),We(this,et,!1),this.initialReflectedProperties=new Map,this.didSSR=!!this.shadowRoot,this.customStates={set:(e,t)=>{if(this.internals?.states)try{t?this.internals.states.add(e):this.internals.states.delete(e)}catch(e){if(String(e).includes(`must start with '--'`))console.error(`Your browser implements an outdated version of CustomStateSet. Consider using a polyfill`);else throw e}},has:e=>{if(!this.internals?.states)return!1;try{return this.internals.states.has(e)}catch{return!1}}};try{this.internals=this.attachInternals()}catch{console.error(`Element internals are not supported in your browser. Consider using a polyfill`)}this.customStates.set(`wa-defined`,!0);let e=this.constructor;for(let[t,n]of e.elementProperties)n.default===`inherit`&&n.initial!==void 0&&typeof t==`string`&&this.customStates.set(`initial-${t}-${n.initial}`,!0)}static get styles(){return[$e,...Array.isArray(this.css)?this.css:this.css?[this.css]:[]].map(e=>typeof e==`string`?g(e):e)}attributeChangedCallback(e,t,n){Ue(this,et)||(this.constructor.elementProperties.forEach((e,t)=>{e.reflect&&this[t]!=null&&this.initialReflectedProperties.set(t,this[t])}),Ge(this,et,!0)),super.attributeChangedCallback(e,t,n)}willUpdate(e){super.willUpdate(e),this.initialReflectedProperties.forEach((t,n)=>{e.has(n)&&this[n]==null&&(this[n]=t)})}firstUpdated(e){super.firstUpdated(e),this.didSSR&&this.shadowRoot?.querySelectorAll(`slot`).forEach(e=>{e.dispatchEvent(new Event(`slotchange`,{bubbles:!0,composed:!1,cancelable:!1}))})}update(e){try{super.update(e)}catch(e){if(this.didSSR&&!this.hasUpdated){let t=new Event(`lit-hydration-error`,{bubbles:!0,composed:!0,cancelable:!1});t.error=e,this.dispatchEvent(t)}throw e}}relayNativeEvent(e,t){e.stopImmediatePropagation(),this.dispatchEvent(new e.constructor(e.type,{...e,...t}))}};et=new WeakMap,k([y()],tt.prototype,`dir`,2),k([y()],tt.prototype,`lang`,2),k([y({type:Boolean,reflect:!0,attribute:`did-ssr`})],tt.prototype,`didSSR`,2);var nt=Math.min,rt=Math.max,it=Math.round,at=Math.floor,ot=e=>({x:e,y:e}),st={left:`right`,right:`left`,bottom:`top`,top:`bottom`},ct={start:`end`,end:`start`};function lt(e,t,n){return rt(e,nt(t,n))}function ut(e,t){return typeof e==`function`?e(t):e}function dt(e){return e.split(`-`)[0]}function ft(e){return e.split(`-`)[1]}function pt(e){return e===`x`?`y`:`x`}function mt(e){return e===`y`?`height`:`width`}var ht=new Set([`top`,`bottom`]);function gt(e){return ht.has(dt(e))?`y`:`x`}function _t(e){return pt(gt(e))}function vt(e,t,n){n===void 0&&(n=!1);let r=ft(e),i=_t(e),a=mt(i),o=i===`x`?r===(n?`end`:`start`)?`right`:`left`:r===`start`?`bottom`:`top`;return t.reference[a]>t.floating[a]&&(o=Dt(o)),[o,Dt(o)]}function yt(e){let t=Dt(e);return[bt(e),t,bt(t)]}function bt(e){return e.replace(/start|end/g,e=>ct[e])}var xt=[`left`,`right`],St=[`right`,`left`],Ct=[`top`,`bottom`],wt=[`bottom`,`top`];function Tt(e,t,n){switch(e){case`top`:case`bottom`:return n?t?St:xt:t?xt:St;case`left`:case`right`:return t?Ct:wt;default:return[]}}function Et(e,t,n,r){let i=ft(e),a=Tt(dt(e),n===`start`,r);return i&&(a=a.map(e=>e+`-`+i),t&&(a=a.concat(a.map(bt)))),a}function Dt(e){return e.replace(/left|right|bottom|top/g,e=>st[e])}function Ot(e){return{top:0,right:0,bottom:0,left:0,...e}}function kt(e){return typeof e==`number`?{top:e,right:e,bottom:e,left:e}:Ot(e)}function At(e){let{x:t,y:n,width:r,height:i}=e;return{width:r,height:i,top:n,left:t,right:t+r,bottom:n+i,x:t,y:n}}function jt(e,t,n){let{reference:r,floating:i}=e,a=gt(t),o=_t(t),s=mt(o),c=dt(t),l=a===`y`,u=r.x+r.width/2-i.width/2,d=r.y+r.height/2-i.height/2,f=r[s]/2-i[s]/2,p;switch(c){case`top`:p={x:u,y:r.y-i.height};break;case`bottom`:p={x:u,y:r.y+r.height};break;case`right`:p={x:r.x+r.width,y:d};break;case`left`:p={x:r.x-i.width,y:d};break;default:p={x:r.x,y:r.y}}switch(ft(t)){case`start`:p[o]-=f*(n&&l?-1:1);break;case`end`:p[o]+=f*(n&&l?-1:1);break}return p}var Mt=async(e,t,n)=>{let{placement:r=`bottom`,strategy:i=`absolute`,middleware:a=[],platform:o}=n,s=a.filter(Boolean),c=await(o.isRTL==null?void 0:o.isRTL(t)),l=await o.getElementRects({reference:e,floating:t,strategy:i}),{x:u,y:d}=jt(l,r,c),f=r,p={},m=0;for(let n=0;n<s.length;n++){let{name:a,fn:h}=s[n],{x:g,y:_,data:v,reset:y}=await h({x:u,y:d,initialPlacement:r,placement:f,strategy:i,middlewareData:p,rects:l,platform:o,elements:{reference:e,floating:t}});u=g??u,d=_??d,p={...p,[a]:{...p[a],...v}},y&&m<=50&&(m++,typeof y==`object`&&(y.placement&&(f=y.placement),y.rects&&(l=y.rects===!0?await o.getElementRects({reference:e,floating:t,strategy:i}):y.rects),{x:u,y:d}=jt(l,f,c)),n=-1)}return{x:u,y:d,placement:f,strategy:i,middlewareData:p}};async function Nt(e,t){t===void 0&&(t={});let{x:n,y:r,platform:i,rects:a,elements:o,strategy:s}=e,{boundary:c=`clippingAncestors`,rootBoundary:l=`viewport`,elementContext:u=`floating`,altBoundary:d=!1,padding:f=0}=ut(t,e),p=kt(f),m=o[d?u===`floating`?`reference`:`floating`:u],h=At(await i.getClippingRect({element:await(i.isElement==null?void 0:i.isElement(m))??!0?m:m.contextElement||await(i.getDocumentElement==null?void 0:i.getDocumentElement(o.floating)),boundary:c,rootBoundary:l,strategy:s})),g=u===`floating`?{x:n,y:r,width:a.floating.width,height:a.floating.height}:a.reference,_=await(i.getOffsetParent==null?void 0:i.getOffsetParent(o.floating)),v=await(i.isElement==null?void 0:i.isElement(_))&&await(i.getScale==null?void 0:i.getScale(_))||{x:1,y:1},y=At(i.convertOffsetParentRelativeRectToViewportRelativeRect?await i.convertOffsetParentRelativeRectToViewportRelativeRect({elements:o,rect:g,offsetParent:_,strategy:s}):g);return{top:(h.top-y.top+p.top)/v.y,bottom:(y.bottom-h.bottom+p.bottom)/v.y,left:(h.left-y.left+p.left)/v.x,right:(y.right-h.right+p.right)/v.x}}var Pt=e=>({name:`arrow`,options:e,async fn(t){let{x:n,y:r,placement:i,rects:a,platform:o,elements:s,middlewareData:c}=t,{element:l,padding:u=0}=ut(e,t)||{};if(l==null)return{};let d=kt(u),f={x:n,y:r},p=_t(i),m=mt(p),h=await o.getDimensions(l),g=p===`y`,_=g?`top`:`left`,v=g?`bottom`:`right`,y=g?`clientHeight`:`clientWidth`,b=a.reference[m]+a.reference[p]-f[p]-a.floating[m],x=f[p]-a.reference[p],S=await(o.getOffsetParent==null?void 0:o.getOffsetParent(l)),C=S?S[y]:0;(!C||!await(o.isElement==null?void 0:o.isElement(S)))&&(C=s.floating[y]||a.floating[m]);let w=b/2-x/2,T=C/2-h[m]/2-1,E=nt(d[_],T),D=nt(d[v],T),ee=E,te=C-h[m]-D,ne=C/2-h[m]/2+w,re=lt(ee,ne,te),ie=!c.arrow&&ft(i)!=null&&ne!==re&&a.reference[m]/2-(ne<ee?E:D)-h[m]/2<0,ae=ie?ne<ee?ne-ee:ne-te:0;return{[p]:f[p]+ae,data:{[p]:re,centerOffset:ne-re-ae,...ie&&{alignmentOffset:ae}},reset:ie}}}),Ft=function(e){return e===void 0&&(e={}),{name:`flip`,options:e,async fn(t){var n;let{placement:r,middlewareData:i,rects:a,initialPlacement:o,platform:s,elements:c}=t,{mainAxis:l=!0,crossAxis:u=!0,fallbackPlacements:d,fallbackStrategy:f=`bestFit`,fallbackAxisSideDirection:p=`none`,flipAlignment:m=!0,...h}=ut(e,t);if((n=i.arrow)!=null&&n.alignmentOffset)return{};let g=dt(r),_=gt(o),v=dt(o)===o,y=await(s.isRTL==null?void 0:s.isRTL(c.floating)),b=d||(v||!m?[Dt(o)]:yt(o)),x=p!==`none`;!d&&x&&b.push(...Et(o,m,p,y));let S=[o,...b],C=await Nt(t,h),w=[],T=i.flip?.overflows||[];if(l&&w.push(C[g]),u){let e=vt(r,a,y);w.push(C[e[0]],C[e[1]])}if(T=[...T,{placement:r,overflows:w}],!w.every(e=>e<=0)){let e=(i.flip?.index||0)+1,t=S[e];if(t&&(!(u===`alignment`&&_!==gt(t))||T.every(e=>gt(e.placement)===_?e.overflows[0]>0:!0)))return{data:{index:e,overflows:T},reset:{placement:t}};let n=T.filter(e=>e.overflows[0]<=0).sort((e,t)=>e.overflows[1]-t.overflows[1])[0]?.placement;if(!n)switch(f){case`bestFit`:{let e=T.filter(e=>{if(x){let t=gt(e.placement);return t===_||t===`y`}return!0}).map(e=>[e.placement,e.overflows.filter(e=>e>0).reduce((e,t)=>e+t,0)]).sort((e,t)=>e[1]-t[1])[0]?.[0];e&&(n=e);break}case`initialPlacement`:n=o;break}if(r!==n)return{reset:{placement:n}}}return{}}}},It=new Set([`left`,`top`]);async function Lt(e,t){let{placement:n,platform:r,elements:i}=e,a=await(r.isRTL==null?void 0:r.isRTL(i.floating)),o=dt(n),s=ft(n),c=gt(n)===`y`,l=It.has(o)?-1:1,u=a&&c?-1:1,d=ut(t,e),{mainAxis:f,crossAxis:p,alignmentAxis:m}=typeof d==`number`?{mainAxis:d,crossAxis:0,alignmentAxis:null}:{mainAxis:d.mainAxis||0,crossAxis:d.crossAxis||0,alignmentAxis:d.alignmentAxis};return s&&typeof m==`number`&&(p=s===`end`?m*-1:m),c?{x:p*u,y:f*l}:{x:f*l,y:p*u}}var Rt=function(e){return e===void 0&&(e=0),{name:`offset`,options:e,async fn(t){var n;let{x:r,y:i,placement:a,middlewareData:o}=t,s=await Lt(t,e);return a===o.offset?.placement&&(n=o.arrow)!=null&&n.alignmentOffset?{}:{x:r+s.x,y:i+s.y,data:{...s,placement:a}}}}},zt=function(e){return e===void 0&&(e={}),{name:`shift`,options:e,async fn(t){let{x:n,y:r,placement:i}=t,{mainAxis:a=!0,crossAxis:o=!1,limiter:s={fn:e=>{let{x:t,y:n}=e;return{x:t,y:n}}},...c}=ut(e,t),l={x:n,y:r},u=await Nt(t,c),d=gt(dt(i)),f=pt(d),p=l[f],m=l[d];if(a){let e=f===`y`?`top`:`left`,t=f===`y`?`bottom`:`right`,n=p+u[e],r=p-u[t];p=lt(n,p,r)}if(o){let e=d===`y`?`top`:`left`,t=d===`y`?`bottom`:`right`,n=m+u[e],r=m-u[t];m=lt(n,m,r)}let h=s.fn({...t,[f]:p,[d]:m});return{...h,data:{x:h.x-n,y:h.y-r,enabled:{[f]:a,[d]:o}}}}}},Bt=function(e){return e===void 0&&(e={}),{name:`size`,options:e,async fn(t){var n,r;let{placement:i,rects:a,platform:o,elements:s}=t,{apply:c=()=>{},...l}=ut(e,t),u=await Nt(t,l),d=dt(i),f=ft(i),p=gt(i)===`y`,{width:m,height:h}=a.floating,g,_;d===`top`||d===`bottom`?(g=d,_=f===(await(o.isRTL==null?void 0:o.isRTL(s.floating))?`start`:`end`)?`left`:`right`):(_=d,g=f===`end`?`top`:`bottom`);let v=h-u.top-u.bottom,y=m-u.left-u.right,b=nt(h-u[g],v),x=nt(m-u[_],y),S=!t.middlewareData.shift,C=b,w=x;if((n=t.middlewareData.shift)!=null&&n.enabled.x&&(w=y),(r=t.middlewareData.shift)!=null&&r.enabled.y&&(C=v),S&&!f){let e=rt(u.left,0),t=rt(u.right,0),n=rt(u.top,0),r=rt(u.bottom,0);p?w=m-2*(e!==0||t!==0?e+t:rt(u.left,u.right)):C=h-2*(n!==0||r!==0?n+r:rt(u.top,u.bottom))}await c({...t,availableWidth:w,availableHeight:C});let T=await o.getDimensions(s.floating);return m!==T.width||h!==T.height?{reset:{rects:!0}}:{}}}};function Vt(){return typeof window<`u`}function Ht(e){return Gt(e)?(e.nodeName||``).toLowerCase():`#document`}function Ut(e){var t;return(e==null||(t=e.ownerDocument)==null?void 0:t.defaultView)||window}function Wt(e){return((Gt(e)?e.ownerDocument:e.document)||window.document)?.documentElement}function Gt(e){return Vt()?e instanceof Node||e instanceof Ut(e).Node:!1}function Kt(e){return Vt()?e instanceof Element||e instanceof Ut(e).Element:!1}function qt(e){return Vt()?e instanceof HTMLElement||e instanceof Ut(e).HTMLElement:!1}function Jt(e){return!Vt()||typeof ShadowRoot>`u`?!1:e instanceof ShadowRoot||e instanceof Ut(e).ShadowRoot}var Yt=new Set([`inline`,`contents`]);function Xt(e){let{overflow:t,overflowX:n,overflowY:r,display:i}=un(e);return/auto|scroll|overlay|hidden|clip/.test(t+r+n)&&!Yt.has(i)}var Zt=new Set([`table`,`td`,`th`]);function Qt(e){return Zt.has(Ht(e))}var $t=[`:popover-open`,`:modal`];function en(e){return $t.some(t=>{try{return e.matches(t)}catch{return!1}})}var tn=[`transform`,`translate`,`scale`,`rotate`,`perspective`],nn=[`transform`,`translate`,`scale`,`rotate`,`perspective`,`filter`],rn=[`paint`,`layout`,`strict`,`content`];function an(e){let t=sn(),n=Kt(e)?un(e):e;return tn.some(e=>n[e]?n[e]!==`none`:!1)||(n.containerType?n.containerType!==`normal`:!1)||!t&&(n.backdropFilter?n.backdropFilter!==`none`:!1)||!t&&(n.filter?n.filter!==`none`:!1)||nn.some(e=>(n.willChange||``).includes(e))||rn.some(e=>(n.contain||``).includes(e))}function on(e){let t=fn(e);for(;qt(t)&&!ln(t);){if(an(t))return t;if(en(t))return null;t=fn(t)}return null}function sn(){return typeof CSS>`u`||!CSS.supports?!1:CSS.supports(`-webkit-backdrop-filter`,`none`)}var cn=new Set([`html`,`body`,`#document`]);function ln(e){return cn.has(Ht(e))}function un(e){return Ut(e).getComputedStyle(e)}function dn(e){return Kt(e)?{scrollLeft:e.scrollLeft,scrollTop:e.scrollTop}:{scrollLeft:e.scrollX,scrollTop:e.scrollY}}function fn(e){if(Ht(e)===`html`)return e;let t=e.assignedSlot||e.parentNode||Jt(e)&&e.host||Wt(e);return Jt(t)?t.host:t}function pn(e){let t=fn(e);return ln(t)?e.ownerDocument?e.ownerDocument.body:e.body:qt(t)&&Xt(t)?t:pn(t)}function mn(e,t,n){t===void 0&&(t=[]),n===void 0&&(n=!0);let r=pn(e),i=r===e.ownerDocument?.body,a=Ut(r);if(i){let e=hn(a);return t.concat(a,a.visualViewport||[],Xt(r)?r:[],e&&n?mn(e):[])}return t.concat(r,mn(r,[],n))}function hn(e){return e.parent&&Object.getPrototypeOf(e.parent)?e.frameElement:null}function gn(e){let t=un(e),n=parseFloat(t.width)||0,r=parseFloat(t.height)||0,i=qt(e),a=i?e.offsetWidth:n,o=i?e.offsetHeight:r,s=it(n)!==a||it(r)!==o;return s&&(n=a,r=o),{width:n,height:r,$:s}}function _n(e){return Kt(e)?e:e.contextElement}function vn(e){let t=_n(e);if(!qt(t))return ot(1);let n=t.getBoundingClientRect(),{width:r,height:i,$:a}=gn(t),o=(a?it(n.width):n.width)/r,s=(a?it(n.height):n.height)/i;return(!o||!Number.isFinite(o))&&(o=1),(!s||!Number.isFinite(s))&&(s=1),{x:o,y:s}}var yn=ot(0);function bn(e){let t=Ut(e);return!sn()||!t.visualViewport?yn:{x:t.visualViewport.offsetLeft,y:t.visualViewport.offsetTop}}function xn(e,t,n){return t===void 0&&(t=!1),!n||t&&n!==Ut(e)?!1:t}function Sn(e,t,n,r){t===void 0&&(t=!1),n===void 0&&(n=!1);let i=e.getBoundingClientRect(),a=_n(e),o=ot(1);t&&(r?Kt(r)&&(o=vn(r)):o=vn(e));let s=xn(a,n,r)?bn(a):ot(0),c=(i.left+s.x)/o.x,l=(i.top+s.y)/o.y,u=i.width/o.x,d=i.height/o.y;if(a){let e=Ut(a),t=r&&Kt(r)?Ut(r):r,n=e,i=hn(n);for(;i&&r&&t!==n;){let e=vn(i),t=i.getBoundingClientRect(),r=un(i),a=t.left+(i.clientLeft+parseFloat(r.paddingLeft))*e.x,o=t.top+(i.clientTop+parseFloat(r.paddingTop))*e.y;c*=e.x,l*=e.y,u*=e.x,d*=e.y,c+=a,l+=o,n=Ut(i),i=hn(n)}}return At({width:u,height:d,x:c,y:l})}function Cn(e,t){let n=dn(e).scrollLeft;return t?t.left+n:Sn(Wt(e)).left+n}function wn(e,t){let n=e.getBoundingClientRect();return{x:n.left+t.scrollLeft-Cn(e,n),y:n.top+t.scrollTop}}function Tn(e){let{elements:t,rect:n,offsetParent:r,strategy:i}=e,a=i===`fixed`,o=Wt(r),s=t?en(t.floating):!1;if(r===o||s&&a)return n;let c={scrollLeft:0,scrollTop:0},l=ot(1),u=ot(0),d=qt(r);if((d||!d&&!a)&&((Ht(r)!==`body`||Xt(o))&&(c=dn(r)),qt(r))){let e=Sn(r);l=vn(r),u.x=e.x+r.clientLeft,u.y=e.y+r.clientTop}let f=o&&!d&&!a?wn(o,c):ot(0);return{width:n.width*l.x,height:n.height*l.y,x:n.x*l.x-c.scrollLeft*l.x+u.x+f.x,y:n.y*l.y-c.scrollTop*l.y+u.y+f.y}}function En(e){return Array.from(e.getClientRects())}function Dn(e){let t=Wt(e),n=dn(e),r=e.ownerDocument.body,i=rt(t.scrollWidth,t.clientWidth,r.scrollWidth,r.clientWidth),a=rt(t.scrollHeight,t.clientHeight,r.scrollHeight,r.clientHeight),o=-n.scrollLeft+Cn(e),s=-n.scrollTop;return un(r).direction===`rtl`&&(o+=rt(t.clientWidth,r.clientWidth)-i),{width:i,height:a,x:o,y:s}}var On=25;function kn(e,t){let n=Ut(e),r=Wt(e),i=n.visualViewport,a=r.clientWidth,o=r.clientHeight,s=0,c=0;if(i){a=i.width,o=i.height;let e=sn();(!e||e&&t===`fixed`)&&(s=i.offsetLeft,c=i.offsetTop)}let l=Cn(r);if(l<=0){let e=r.ownerDocument,t=e.body,n=getComputedStyle(t),i=e.compatMode===`CSS1Compat`&&parseFloat(n.marginLeft)+parseFloat(n.marginRight)||0,o=Math.abs(r.clientWidth-t.clientWidth-i);o<=On&&(a-=o)}else l<=On&&(a+=l);return{width:a,height:o,x:s,y:c}}var An=new Set([`absolute`,`fixed`]);function jn(e,t){let n=Sn(e,!0,t===`fixed`),r=n.top+e.clientTop,i=n.left+e.clientLeft,a=qt(e)?vn(e):ot(1);return{width:e.clientWidth*a.x,height:e.clientHeight*a.y,x:i*a.x,y:r*a.y}}function Mn(e,t,n){let r;if(t===`viewport`)r=kn(e,n);else if(t===`document`)r=Dn(Wt(e));else if(Kt(t))r=jn(t,n);else{let n=bn(e);r={x:t.x-n.x,y:t.y-n.y,width:t.width,height:t.height}}return At(r)}function Nn(e,t){let n=fn(e);return n===t||!Kt(n)||ln(n)?!1:un(n).position===`fixed`||Nn(n,t)}function Pn(e,t){let n=t.get(e);if(n)return n;let r=mn(e,[],!1).filter(e=>Kt(e)&&Ht(e)!==`body`),i=null,a=un(e).position===`fixed`,o=a?fn(e):e;for(;Kt(o)&&!ln(o);){let t=un(o),n=an(o);!n&&t.position===`fixed`&&(i=null),(a?!n&&!i:!n&&t.position===`static`&&i&&An.has(i.position)||Xt(o)&&!n&&Nn(e,o))?r=r.filter(e=>e!==o):i=t,o=fn(o)}return t.set(e,r),r}function Fn(e){let{element:t,boundary:n,rootBoundary:r,strategy:i}=e,a=[...n===`clippingAncestors`?en(t)?[]:Pn(t,this._c):[].concat(n),r],o=a[0],s=a.reduce((e,n)=>{let r=Mn(t,n,i);return e.top=rt(r.top,e.top),e.right=nt(r.right,e.right),e.bottom=nt(r.bottom,e.bottom),e.left=rt(r.left,e.left),e},Mn(t,o,i));return{width:s.right-s.left,height:s.bottom-s.top,x:s.left,y:s.top}}function In(e){let{width:t,height:n}=gn(e);return{width:t,height:n}}function Ln(e,t,n){let r=qt(t),i=Wt(t),a=n===`fixed`,o=Sn(e,!0,a,t),s={scrollLeft:0,scrollTop:0},c=ot(0);function l(){c.x=Cn(i)}if(r||!r&&!a)if((Ht(t)!==`body`||Xt(i))&&(s=dn(t)),r){let e=Sn(t,!0,a,t);c.x=e.x+t.clientLeft,c.y=e.y+t.clientTop}else i&&l();a&&!r&&i&&l();let u=i&&!r&&!a?wn(i,s):ot(0);return{x:o.left+s.scrollLeft-c.x-u.x,y:o.top+s.scrollTop-c.y-u.y,width:o.width,height:o.height}}function Rn(e){return un(e).position===`static`}function zn(e,t){if(!qt(e)||un(e).position===`fixed`)return null;if(t)return t(e);let n=e.offsetParent;return Wt(e)===n&&(n=n.ownerDocument.body),n}function Bn(e,t){let n=Ut(e);if(en(e))return n;if(!qt(e)){let t=fn(e);for(;t&&!ln(t);){if(Kt(t)&&!Rn(t))return t;t=fn(t)}return n}let r=zn(e,t);for(;r&&Qt(r)&&Rn(r);)r=zn(r,t);return r&&ln(r)&&Rn(r)&&!an(r)?n:r||on(e)||n}var Vn=async function(e){let t=this.getOffsetParent||Bn,n=this.getDimensions,r=await n(e.floating);return{reference:Ln(e.reference,await t(e.floating),e.strategy),floating:{x:0,y:0,width:r.width,height:r.height}}};function Hn(e){return un(e).direction===`rtl`}var Un={convertOffsetParentRelativeRectToViewportRelativeRect:Tn,getDocumentElement:Wt,getClippingRect:Fn,getOffsetParent:Bn,getElementRects:Vn,getClientRects:En,getDimensions:In,getScale:vn,isElement:Kt,isRTL:Hn};function Wn(e,t){return e.x===t.x&&e.y===t.y&&e.width===t.width&&e.height===t.height}function Gn(e,t){let n=null,r,i=Wt(e);function a(){var e;clearTimeout(r),(e=n)==null||e.disconnect(),n=null}function o(s,c){s===void 0&&(s=!1),c===void 0&&(c=1),a();let l=e.getBoundingClientRect(),{left:u,top:d,width:f,height:p}=l;if(s||t(),!f||!p)return;let m=at(d),h=at(i.clientWidth-(u+f)),g=at(i.clientHeight-(d+p)),_=at(u),v={rootMargin:-m+`px `+-h+`px `+-g+`px `+-_+`px`,threshold:rt(0,nt(1,c))||1},y=!0;function b(t){let n=t[0].intersectionRatio;if(n!==c){if(!y)return o();n?o(!1,n):r=setTimeout(()=>{o(!1,1e-7)},1e3)}n===1&&!Wn(l,e.getBoundingClientRect())&&o(),y=!1}try{n=new IntersectionObserver(b,{...v,root:i.ownerDocument})}catch{n=new IntersectionObserver(b,v)}n.observe(e)}return o(!0),a}function Kn(e,t,n,r){r===void 0&&(r={});let{ancestorScroll:i=!0,ancestorResize:a=!0,elementResize:o=typeof ResizeObserver==`function`,layoutShift:s=typeof IntersectionObserver==`function`,animationFrame:c=!1}=r,l=_n(e),u=i||a?[...l?mn(l):[],...mn(t)]:[];u.forEach(e=>{i&&e.addEventListener(`scroll`,n,{passive:!0}),a&&e.addEventListener(`resize`,n)});let d=l&&s?Gn(l,n):null,f=-1,p=null;o&&(p=new ResizeObserver(e=>{let[r]=e;r&&r.target===l&&p&&(p.unobserve(t),cancelAnimationFrame(f),f=requestAnimationFrame(()=>{var e;(e=p)==null||e.observe(t)})),n()}),l&&!c&&p.observe(l),p.observe(t));let m,h=c?Sn(e):null;c&&g();function g(){let t=Sn(e);h&&!Wn(h,t)&&n(),h=t,m=requestAnimationFrame(g)}return n(),()=>{var e;u.forEach(e=>{i&&e.removeEventListener(`scroll`,n),a&&e.removeEventListener(`resize`,n)}),d?.(),(e=p)==null||e.disconnect(),p=null,c&&cancelAnimationFrame(m)}}var qn=Rt,Jn=zt,Yn=Ft,Xn=Bt,Zn=Pt,Qn=(e,t,n)=>{let r=new Map,i={platform:Un,...n},a={...i.platform,_c:r};return Mt(e,t,{...i,platform:a})};function $n(e){return tr(e)}function er(e){return e.assignedSlot?e.assignedSlot:e.parentNode instanceof ShadowRoot?e.parentNode.host:e.parentNode}function tr(e){for(let t=e;t;t=er(t))if(t instanceof Element&&getComputedStyle(t).display===`none`)return null;for(let t=er(e);t;t=er(t)){if(!(t instanceof Element))continue;let e=getComputedStyle(t);if(e.display!==`contents`&&(e.position!==`static`||an(e)||t.tagName===`BODY`))return t}return null}var nr=`:host {
  --arrow-color: black;
  --arrow-size: var(--wa-tooltip-arrow-size);
  --show-duration: 100ms;
  --hide-duration: 100ms;

  /*
     * These properties are computed to account for the arrow's dimensions after being rotated 45º. The constant
     * 0.7071 is derived from sin(45), which is the diagonal size of the arrow's container after rotating.
     */
  --arrow-size-diagonal: calc(var(--arrow-size) * 0.7071);
  --arrow-padding-offset: calc(var(--arrow-size-diagonal) - var(--arrow-size));

  display: contents;
}

.popup {
  position: absolute;
  isolation: isolate;
  max-width: var(--auto-size-available-width, none);
  max-height: var(--auto-size-available-height, none);

  /* Clear UA styles for [popover] */
  :where(&) {
    inset: unset;
    padding: unset;
    margin: unset;
    width: unset;
    height: unset;
    color: unset;
    background: unset;
    border: unset;
    overflow: unset;
  }
}

.popup-fixed {
  position: fixed;
}

.popup:not(.popup-active) {
  display: none;
}

.arrow {
  position: absolute;
  width: calc(var(--arrow-size-diagonal) * 2);
  height: calc(var(--arrow-size-diagonal) * 2);
  rotate: 45deg;
  background: var(--arrow-color);
  z-index: 3;
}

:host([data-current-placement~='left']) .arrow {
  rotate: -45deg;
}

:host([data-current-placement~='right']) .arrow {
  rotate: 135deg;
}

:host([data-current-placement~='bottom']) .arrow {
  rotate: 225deg;
}

/* Hover bridge */
.popup-hover-bridge:not(.popup-hover-bridge-visible) {
  display: none;
}

.popup-hover-bridge {
  position: fixed;
  z-index: 899;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  clip-path: polygon(
    var(--hover-bridge-top-left-x, 0) var(--hover-bridge-top-left-y, 0),
    var(--hover-bridge-top-right-x, 0) var(--hover-bridge-top-right-y, 0),
    var(--hover-bridge-bottom-right-x, 0) var(--hover-bridge-bottom-right-y, 0),
    var(--hover-bridge-bottom-left-x, 0) var(--hover-bridge-bottom-left-y, 0)
  );
}

/* Built-in animations */
.show {
  animation: show var(--show-duration) ease;
}

.hide {
  animation: show var(--hide-duration) ease reverse;
}

@keyframes show {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.show-with-scale {
  animation: show-with-scale var(--show-duration) ease;
}

.hide-with-scale {
  animation: show-with-scale var(--hide-duration) ease reverse;
}

@keyframes show-with-scale {
  from {
    opacity: 0;
    scale: 0.8;
  }
  to {
    opacity: 1;
    scale: 1;
  }
}
`;function rr(e){return typeof e==`object`&&!!e&&`getBoundingClientRect`in e&&(`contextElement`in e?e instanceof Element:!0)}var ir=globalThis?.HTMLElement?.prototype.hasOwnProperty(`popover`),A=class extends tt{constructor(){super(...arguments),this.localize=new Ee(this),this.active=!1,this.placement=`top`,this.boundary=`viewport`,this.distance=0,this.skidding=0,this.arrow=!1,this.arrowPlacement=`anchor`,this.arrowPadding=10,this.flip=!1,this.flipFallbackPlacements=``,this.flipFallbackStrategy=`best-fit`,this.flipPadding=0,this.shift=!1,this.shiftPadding=0,this.autoSizePadding=0,this.hoverBridge=!1,this.updateHoverBridge=()=>{if(this.hoverBridge&&this.anchorEl){let e=this.anchorEl.getBoundingClientRect(),t=this.popup.getBoundingClientRect(),n=this.placement.includes(`top`)||this.placement.includes(`bottom`),r=0,i=0,a=0,o=0,s=0,c=0,l=0,u=0;n?e.top<t.top?(r=e.left,i=e.bottom,a=e.right,o=e.bottom,s=t.left,c=t.top,l=t.right,u=t.top):(r=t.left,i=t.bottom,a=t.right,o=t.bottom,s=e.left,c=e.top,l=e.right,u=e.top):e.left<t.left?(r=e.right,i=e.top,a=t.left,o=t.top,s=e.right,c=e.bottom,l=t.left,u=t.bottom):(r=t.right,i=t.top,a=e.left,o=e.top,s=t.right,c=t.bottom,l=e.left,u=e.bottom),this.style.setProperty(`--hover-bridge-top-left-x`,`${r}px`),this.style.setProperty(`--hover-bridge-top-left-y`,`${i}px`),this.style.setProperty(`--hover-bridge-top-right-x`,`${a}px`),this.style.setProperty(`--hover-bridge-top-right-y`,`${o}px`),this.style.setProperty(`--hover-bridge-bottom-left-x`,`${s}px`),this.style.setProperty(`--hover-bridge-bottom-left-y`,`${c}px`),this.style.setProperty(`--hover-bridge-bottom-right-x`,`${l}px`),this.style.setProperty(`--hover-bridge-bottom-right-y`,`${u}px`)}}}async connectedCallback(){super.connectedCallback(),await this.updateComplete,this.start()}disconnectedCallback(){super.disconnectedCallback(),this.stop()}async updated(e){super.updated(e),e.has(`active`)&&(this.active?this.start():this.stop()),e.has(`anchor`)&&this.handleAnchorChange(),this.active&&(await this.updateComplete,this.reposition())}async handleAnchorChange(){await this.stop(),this.anchor&&typeof this.anchor==`string`?this.anchorEl=this.getRootNode().getElementById(this.anchor):this.anchor instanceof Element||rr(this.anchor)?this.anchorEl=this.anchor:this.anchorEl=this.querySelector(`[slot="anchor"]`),this.anchorEl instanceof HTMLSlotElement&&(this.anchorEl=this.anchorEl.assignedElements({flatten:!0})[0]),this.anchorEl&&this.start()}start(){!this.anchorEl||!this.active||(this.popup.showPopover?.(),this.cleanup=Kn(this.anchorEl,this.popup,()=>{this.reposition()}))}async stop(){return new Promise(e=>{this.popup.hidePopover?.(),this.cleanup?(this.cleanup(),this.cleanup=void 0,this.removeAttribute(`data-current-placement`),this.style.removeProperty(`--auto-size-available-width`),this.style.removeProperty(`--auto-size-available-height`),requestAnimationFrame(()=>e())):e()})}reposition(){if(!this.active||!this.anchorEl)return;let e=[qn({mainAxis:this.distance,crossAxis:this.skidding})];this.sync?e.push(Xn({apply:({rects:e})=>{let t=this.sync===`width`||this.sync===`both`,n=this.sync===`height`||this.sync===`both`;this.popup.style.width=t?`${e.reference.width}px`:``,this.popup.style.height=n?`${e.reference.height}px`:``}})):(this.popup.style.width=``,this.popup.style.height=``);let t;ir&&!rr(this.anchor)&&this.boundary===`scroll`&&(t=mn(this.anchorEl).filter(e=>e instanceof Element)),this.flip&&e.push(Yn({boundary:this.flipBoundary||t,fallbackPlacements:this.flipFallbackPlacements,fallbackStrategy:this.flipFallbackStrategy===`best-fit`?`bestFit`:`initialPlacement`,padding:this.flipPadding})),this.shift&&e.push(Jn({boundary:this.shiftBoundary||t,padding:this.shiftPadding})),this.autoSize?e.push(Xn({boundary:this.autoSizeBoundary||t,padding:this.autoSizePadding,apply:({availableWidth:e,availableHeight:t})=>{this.autoSize===`vertical`||this.autoSize===`both`?this.style.setProperty(`--auto-size-available-height`,`${t}px`):this.style.removeProperty(`--auto-size-available-height`),this.autoSize===`horizontal`||this.autoSize===`both`?this.style.setProperty(`--auto-size-available-width`,`${e}px`):this.style.removeProperty(`--auto-size-available-width`)}})):(this.style.removeProperty(`--auto-size-available-width`),this.style.removeProperty(`--auto-size-available-height`)),this.arrow&&e.push(Zn({element:this.arrowEl,padding:this.arrowPadding}));let n=ir?e=>Un.getOffsetParent(e,$n):Un.getOffsetParent;Qn(this.anchorEl,this.popup,{placement:this.placement,middleware:e,strategy:ir?`absolute`:`fixed`,platform:{...Un,getOffsetParent:n}}).then(({x:e,y:t,middlewareData:n,placement:r})=>{let i=this.localize.dir()===`rtl`,a={top:`bottom`,right:`left`,bottom:`top`,left:`right`}[r.split(`-`)[0]];if(this.setAttribute(`data-current-placement`,r),Object.assign(this.popup.style,{left:`${e}px`,top:`${t}px`}),this.arrow){let e=n.arrow.x,t=n.arrow.y,r=``,o=``,s=``,c=``;if(this.arrowPlacement===`start`){let n=typeof e==`number`?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:``;r=typeof t==`number`?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:``,o=i?n:``,c=i?``:n}else if(this.arrowPlacement===`end`){let n=typeof e==`number`?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:``;o=i?``:n,c=i?n:``,s=typeof t==`number`?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:``}else this.arrowPlacement===`center`?(c=typeof e==`number`?`calc(50% - var(--arrow-size-diagonal))`:``,r=typeof t==`number`?`calc(50% - var(--arrow-size-diagonal))`:``):(c=typeof e==`number`?`${e}px`:``,r=typeof t==`number`?`${t}px`:``);Object.assign(this.arrowEl.style,{top:r,right:o,bottom:s,left:c,[a]:`calc(var(--arrow-size-diagonal) * -1)`})}}),requestAnimationFrame(()=>this.updateHoverBridge()),this.dispatchEvent(new Qe)}render(){return d`
      <slot name="anchor" @slotchange=${this.handleAnchorChange}></slot>

      <span
        part="hover-bridge"
        class=${T({"popup-hover-bridge":!0,"popup-hover-bridge-visible":this.hoverBridge&&this.active})}
      ></span>

      <div
        popover="manual"
        part="popup"
        class=${T({popup:!0,"popup-active":this.active,"popup-fixed":!ir,"popup-has-arrow":this.arrow})}
      >
        <slot></slot>
        ${this.arrow?d`<div part="arrow" class="arrow" role="presentation"></div>`:``}
      </div>
    `}};A.css=nr,k([S(`.popup`)],A.prototype,`popup`,2),k([S(`.arrow`)],A.prototype,`arrowEl`,2),k([y()],A.prototype,`anchor`,2),k([y({type:Boolean,reflect:!0})],A.prototype,`active`,2),k([y({reflect:!0})],A.prototype,`placement`,2),k([y()],A.prototype,`boundary`,2),k([y({type:Number})],A.prototype,`distance`,2),k([y({type:Number})],A.prototype,`skidding`,2),k([y({type:Boolean})],A.prototype,`arrow`,2),k([y({attribute:`arrow-placement`})],A.prototype,`arrowPlacement`,2),k([y({attribute:`arrow-padding`,type:Number})],A.prototype,`arrowPadding`,2),k([y({type:Boolean})],A.prototype,`flip`,2),k([y({attribute:`flip-fallback-placements`,converter:{fromAttribute:e=>e.split(` `).map(e=>e.trim()).filter(e=>e!==``),toAttribute:e=>e.join(` `)}})],A.prototype,`flipFallbackPlacements`,2),k([y({attribute:`flip-fallback-strategy`})],A.prototype,`flipFallbackStrategy`,2),k([y({type:Object})],A.prototype,`flipBoundary`,2),k([y({attribute:`flip-padding`,type:Number})],A.prototype,`flipPadding`,2),k([y({type:Boolean})],A.prototype,`shift`,2),k([y({type:Object})],A.prototype,`shiftBoundary`,2),k([y({attribute:`shift-padding`,type:Number})],A.prototype,`shiftPadding`,2),k([y({attribute:`auto-size`})],A.prototype,`autoSize`,2),k([y()],A.prototype,`sync`,2),k([y({type:Object})],A.prototype,`autoSizeBoundary`,2),k([y({attribute:`auto-size-padding`,type:Number})],A.prototype,`autoSizePadding`,2),k([y({attribute:`hover-bridge`,type:Boolean})],A.prototype,`hoverBridge`,2),A=k([x(`wa-popup`)],A);var ar=class extends Event{constructor(){super(`wa-after-hide`,{bubbles:!0,cancelable:!1,composed:!0})}},or=class extends Event{constructor(){super(`wa-after-show`,{bubbles:!0,cancelable:!1,composed:!0})}},sr=class extends Event{constructor(e){super(`wa-hide`,{bubbles:!0,cancelable:!0,composed:!0}),this.detail=e}},cr=class extends Event{constructor(){super(`wa-show`,{bubbles:!0,cancelable:!0,composed:!0})}},lr=`useandom-26T198340PX75pxJACKVERYMINDBUSHWOLF_GQZbfghjklqvwyzrict`,ur=(e=21)=>{let t=``,n=crypto.getRandomValues(new Uint8Array(e|=0));for(;e--;)t+=lr[n[e]&63];return t};function dr(e=``){return`${e}${ur()}`}function fr(e,t){return new Promise(n=>{function r(i){i.target===e&&(e.removeEventListener(t,r),n())}e.addEventListener(t,r)})}function pr(e,t){return new Promise(n=>{let r=new AbortController,{signal:i}=r;if(e.classList.contains(t))return;e.classList.remove(t),e.classList.add(t);let a=()=>{e.classList.remove(t),n(),r.abort()};e.addEventListener(`animationend`,a,{once:!0,signal:i}),e.addEventListener(`animationcancel`,a,{once:!0,signal:i})})}function mr(e,t){let n={waitUntilFirstUpdate:!1,...t};return(t,r)=>{let{update:i}=t,a=Array.isArray(e)?e:[e];t.update=function(e){a.forEach(t=>{let i=t;if(e.has(i)){let t=e.get(i),a=this[i];t!==a&&(!n.waitUntilFirstUpdate||this.hasUpdated)&&this[r](t,a)}}),i.call(this,e)}}}var hr=`:host {
  --max-width: 30ch;

  /** These styles are added so we don't interfere in the DOM. */
  display: inline-block;
  position: absolute;

  /** Defaults for inherited CSS properties */
  color: var(--wa-tooltip-content-color);
  font-size: var(--wa-tooltip-font-size);
  line-height: var(--wa-tooltip-line-height);
  text-align: start;
  white-space: normal;
}

.tooltip {
  --arrow-size: var(--wa-tooltip-arrow-size);
  --arrow-color: var(--wa-tooltip-background-color);
}

.tooltip::part(popup) {
  z-index: 1000;
}

.tooltip[placement^='top']::part(popup) {
  transform-origin: bottom;
}

.tooltip[placement^='bottom']::part(popup) {
  transform-origin: top;
}

.tooltip[placement^='left']::part(popup) {
  transform-origin: right;
}

.tooltip[placement^='right']::part(popup) {
  transform-origin: left;
}

.body {
  display: block;
  width: max-content;
  max-width: var(--max-width);
  border-radius: var(--wa-tooltip-border-radius);
  background-color: var(--wa-tooltip-background-color);
  border: var(--wa-tooltip-border-width) var(--wa-tooltip-border-style) var(--wa-tooltip-border-color);
  padding: 0.25em 0.5em;
  user-select: none;
  -webkit-user-select: none;
}

.tooltip::part(arrow) {
  border-bottom: var(--wa-tooltip-border-width) var(--wa-tooltip-border-style) var(--wa-tooltip-border-color);
  border-right: var(--wa-tooltip-border-width) var(--wa-tooltip-border-style) var(--wa-tooltip-border-color);
}
`,gr=class extends tt{constructor(){super(...arguments),this.placement=`top`,this.disabled=!1,this.distance=8,this.open=!1,this.skidding=0,this.showDelay=150,this.hideDelay=0,this.trigger=`hover focus`,this.withoutArrow=!1,this.for=null,this.anchor=null,this.eventController=new AbortController,this.handleBlur=()=>{this.hasTrigger(`focus`)&&this.hide()},this.handleClick=()=>{this.hasTrigger(`click`)&&(this.open?this.hide():this.show())},this.handleFocus=()=>{this.hasTrigger(`focus`)&&this.show()},this.handleDocumentKeyDown=e=>{e.key===`Escape`&&(e.stopPropagation(),this.hide())},this.handleMouseOver=()=>{this.hasTrigger(`hover`)&&(clearTimeout(this.hoverTimeout),this.hoverTimeout=window.setTimeout(()=>this.show(),this.showDelay))},this.handleMouseOut=()=>{this.hasTrigger(`hover`)&&(clearTimeout(this.hoverTimeout),this.hoverTimeout=window.setTimeout(()=>this.hide(),this.hideDelay))}}connectedCallback(){super.connectedCallback(),this.eventController.signal.aborted&&(this.eventController=new AbortController),this.open&&(this.open=!1,this.updateComplete.then(()=>{this.open=!0})),this.id||=dr(`wa-tooltip-`),this.for&&this.anchor?(this.anchor=null,this.handleForChange()):this.for&&this.handleForChange()}disconnectedCallback(){super.disconnectedCallback(),document.removeEventListener(`keydown`,this.handleDocumentKeyDown),this.eventController.abort(),this.anchor&&this.removeFromAriaLabelledBy(this.anchor,this.id)}firstUpdated(){this.body.hidden=!this.open,this.open&&(this.popup.active=!0,this.popup.reposition())}hasTrigger(e){return this.trigger.split(` `).includes(e)}addToAriaLabelledBy(e,t){let n=(e.getAttribute(`aria-labelledby`)||``).split(/\s+/).filter(Boolean);n.includes(t)||(n.push(t),e.setAttribute(`aria-labelledby`,n.join(` `)))}removeFromAriaLabelledBy(e,t){let n=(e.getAttribute(`aria-labelledby`)||``).split(/\s+/).filter(Boolean).filter(e=>e!==t);n.length>0?e.setAttribute(`aria-labelledby`,n.join(` `)):e.removeAttribute(`aria-labelledby`)}async handleOpenChange(){if(this.open){if(this.disabled)return;let e=new cr;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}document.addEventListener(`keydown`,this.handleDocumentKeyDown,{signal:this.eventController.signal}),this.body.hidden=!1,this.popup.active=!0,await pr(this.popup.popup,`show-with-scale`),this.popup.reposition(),this.dispatchEvent(new or)}else{let e=new sr;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}document.removeEventListener(`keydown`,this.handleDocumentKeyDown),await pr(this.popup.popup,`hide-with-scale`),this.popup.active=!1,this.body.hidden=!0,this.dispatchEvent(new ar)}}handleForChange(){let e=this.getRootNode();if(!e)return;let t=this.for?e.getElementById(this.for):null,n=this.anchor;if(t===n)return;let{signal:r}=this.eventController;t&&(this.addToAriaLabelledBy(t,this.id),t.addEventListener(`blur`,this.handleBlur,{capture:!0,signal:r}),t.addEventListener(`focus`,this.handleFocus,{capture:!0,signal:r}),t.addEventListener(`click`,this.handleClick,{signal:r}),t.addEventListener(`mouseover`,this.handleMouseOver,{signal:r}),t.addEventListener(`mouseout`,this.handleMouseOut,{signal:r})),n&&(this.removeFromAriaLabelledBy(n,this.id),n.removeEventListener(`blur`,this.handleBlur,{capture:!0}),n.removeEventListener(`focus`,this.handleFocus,{capture:!0}),n.removeEventListener(`click`,this.handleClick),n.removeEventListener(`mouseover`,this.handleMouseOver),n.removeEventListener(`mouseout`,this.handleMouseOut)),this.anchor=t}async handleOptionsChange(){this.hasUpdated&&(await this.updateComplete,this.popup.reposition())}handleDisabledChange(){this.disabled&&this.open&&this.hide()}async show(){if(!this.open)return this.open=!0,fr(this,`wa-after-show`)}async hide(){if(this.open)return this.open=!1,fr(this,`wa-after-hide`)}render(){return d`
      <wa-popup
        part="base"
        exportparts="
          popup:base__popup,
          arrow:base__arrow
        "
        class=${T({tooltip:!0,"tooltip-open":this.open})}
        placement=${this.placement}
        distance=${this.distance}
        skidding=${this.skidding}
        flip
        shift
        ?arrow=${!this.withoutArrow}
        hover-bridge
        .anchor=${this.anchor}
      >
        <div part="body" class="body">
          <slot></slot>
        </div>
      </wa-popup>
    `}};gr.css=hr,gr.dependencies={"wa-popup":A},k([S(`slot:not([name])`)],gr.prototype,`defaultSlot`,2),k([S(`.body`)],gr.prototype,`body`,2),k([S(`wa-popup`)],gr.prototype,`popup`,2),k([y()],gr.prototype,`placement`,2),k([y({type:Boolean,reflect:!0})],gr.prototype,`disabled`,2),k([y({type:Number})],gr.prototype,`distance`,2),k([y({type:Boolean,reflect:!0})],gr.prototype,`open`,2),k([y({type:Number})],gr.prototype,`skidding`,2),k([y({attribute:`show-delay`,type:Number})],gr.prototype,`showDelay`,2),k([y({attribute:`hide-delay`,type:Number})],gr.prototype,`hideDelay`,2),k([y()],gr.prototype,`trigger`,2),k([y({attribute:`without-arrow`,type:Boolean,reflect:!0})],gr.prototype,`withoutArrow`,2),k([y()],gr.prototype,`for`,2),k([b()],gr.prototype,`anchor`,2),k([mr(`open`,{waitUntilFirstUpdate:!0})],gr.prototype,`handleOpenChange`,1),k([mr(`for`)],gr.prototype,`handleForChange`,1),k([mr([`distance`,`placement`,`skidding`])],gr.prototype,`handleOptionsChange`,1),k([mr(`disabled`)],gr.prototype,`handleDisabledChange`,1),gr=k([x(`wa-tooltip`)],gr);var _r=class extends gr{static get styles(){return[gr.styles,p`
        wa-popup {
          --wa-z-index-tooltip: var(--c-tooltip-z-index, 1000);
          --wa-tooltip-background-color: var(
            --c-tooltip-fill,
            var(--c-surface-overlay)
          );
          --wa-tooltip-border-color: var(
            --c-tooltip-border,
            var(--c-color-neutral-border-quiet)
          );
          --wa-tooltip-content-color: var(--c-tooltip-text, currentColor);
          --wa-tooltip-padding: var(
            --c-tooltip-padding,
            calc(4rem / 16) calc(8rem / 16)
          );
          --wa-tooltip-arrow-size: var(--c-tooltip-arrow-size, 5px);
          --wa-tooltip-font-family: inherit;
          --wa-tooltip-font-size: var(
            --c-tooltip-font-size,
            var(--c-text-base)
          );
          --wa-tooltip-font-weight: var(--c-tooltip-font-weight, 400);
          --wa-tooltip-line-height: var(--c-tooltip-line-height, 1.3);
          --wa-tooltip-border-radius: var(
            --c-tooltip-border-radius,
            var(--c-radius-sm)
          );
          font-weight: 400;
          color: var(--c-tooltip-text, currentColor);
          box-shadow: var(--c-shadow-md);
        }
      `]}};customElements.get(`c-tooltip`)||customElements.define(`c-tooltip`,_r);var vr=p`
  :host {
    display: inline-block;
  }

  .copy-button {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background-color: transparent;
    appearance: none;
    padding: 0;
    font-size: inherit;
    font-family: inherit;
    color: inherit;
    border: none;
    cursor: pointer;
  }
`,yr=class extends v{constructor(...e){super(...e),this.isCopying=!1,this.value=``,this.disabled=!1}async copyValue(){if(!(this.isCopying||this.disabled)){this.isCopying=!0;try{await navigator.clipboard.writeText(this.value),this.dispatchEvent(new CustomEvent(`craft-copy`,{bubbles:!0,cancelable:!1,composed:!0,detail:{value:this.value}}))}catch{this.dispatchEvent(new CustomEvent(`craft-error`,{cancelable:!1,composed:!0,bubbles:!0}))}finally{this.isCopying=!1}}}render(){return d`
      <button
        type="button"
        @click="${this.copyValue}"
        ?disabled=${this.disabled}
        class="copy-button"
        part="button"
      >
        <slot></slot>
        <sl-visually-hidden>Copy to clipboard</sl-visually-hidden>
      </button>
    `}};yr.styles=[vr],l([b()],yr.prototype,`isCopying`,void 0),l([y({type:String})],yr.prototype,`value`,void 0),l([y({type:Boolean})],yr.prototype,`disabled`,void 0),customElements.get(`craft-copy-button`)||customElements.define(`craft-copy-button`,yr);var br=p`
  :host {
    box-sizing: border-box;
  }

  :host *,
  :host *::before,
  :host *::after {
    box-sizing: inherit;
  }

  [hidden] {
    display: none !important;
  }
`,xr=p`
  :host {
    --craft-tooltip-font-size: calc(12rem / 16);
    display: inline-block;
  }

  slot {
    display: inline-flex;
  }

  .copy-attribute {
    font-family: var(--font-mono);
    font-size: var(--c-copy-attribute-font-size, var(--c-text-sm));
    flex-wrap: nowrap;
    display: inline-flex;
    justify-content: center;
    align-items: center;
    gap: 5px;
  }

  .copy-attribute::part(button) {
    --_border: var(
      --c-copy-attribute-border,
      1px solid hsla(209, 20%, 25%, 0.1)
    );
    border-radius: var(--c-copy-attribute-radius, 4px);
    background-color: var(--c-copy-attribute-fill, transparent);
    color: var(--c-copy-attribute-text, inherit);
    border: var(--_border);
    padding-inline: 5px;
    min-height: calc(20rem / 16);
  }

  .copy-attribute::part(button):not(.copy-attribute--success):not(
      .copy-attribute--error
    ):hover,
  .copy-attribute::part(button):not(.copy-attribute--success):not(
      .copy-attribute--error
    ):focus {
    border-color: var(--c-color-neutral-border-quiet);
    color: var(--c-text-default);
  }

  .copy-attribute--success::part(button) {
    background-color: var(
      --c-copy-attribute-success-fill,
      var(--c-copy-attribute-fill)
    );
    color: var(--c-copy-attribute-success-text, var(--c-copy-attribute-text));
    border: var(--c-copy-attribute-success-border, var(--_border));
  }

  .copy-attribute--error::part(button) {
    background-color: var(
      --c-copy-attribute-error-fill,
      var(--c-copy-attribute-fill)
    );
    color: var(--c-copy-attribute-error-text, var(--c-copy-attribute-text));
    border: var(--c-copy-attribute-error-border, var(--_border));
  }

  .icon {
    display: inline-block;
    width: 0.9em;
    height: 0.9em;
  }

  svg {
    fill: currentColor;
    width: 100%;
    height: 100%;
  }
`,Sr={"icon.in":{keyframes:[{scale:.25,opacity:.25},{scale:1,opacity:1}],options:{duration:100}},"icon.out":{keyframes:[{scale:1,opacity:1},{scale:.25,opacity:.25}],options:{duration:100}}},Cr=class extends v{constructor(){super(),this.status=`rest`,this.value=``,this.disabled=!1,this.feedbackDuration=1e3,this.tooltipLabel=`Copy`,this.addEventListener(`craft-copy`,()=>{this.showStatus(`success`)}),this.addEventListener(`craft-error`,()=>{this.showStatus(`error`)})}getId(){return`attribute-${this.value.replace(/([a-z])([A-Z])/g,`$1-$2`).replace(/[\s_]+/g,`-`).toLowerCase()}`}async showStatus(e){let t=e===`success`?this.successIconEl:this.errorIconEl;this.tooltipLabel=e===`success`?`Copied`:`Copy failed`,await t.animate(Sr[`icon.out`].keyframes,Sr[`icon.out`].options),this.copyIconEl.hidden=!0,t.hidden=!1,await t.animate(Sr[`icon.in`].keyframes,Sr[`icon.in`].options),this.status=e,setTimeout(async()=>{await t.animate(Sr[`icon.out`].keyframes,Sr[`icon.out`].options),t.hidden=!0,this.copyIconEl.hidden=!1,await this.copyIconEl.animate(Sr[`icon.in`].keyframes,Sr[`icon.in`].options),this.status=`rest`,this.tooltipLabel=`Copy`},this.feedbackDuration)}render(){return d`
      <c-tooltip for="${this.getId()}">${this.tooltipLabel}</c-tooltip>
      <craft-copy-button
        value="${this.value}"
        id="${this.getId()}"
        class=${T({"copy-attribute":!0,"copy-attribute--success":this.status===`success`,"copy-attribute--error":this.status===`error`})}
      >
        ${this.value}

        <slot name="copy-icon" part="copy-icon">
          <span class="icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
              <path
                d="M288 448H64V224h64v-64H64c-35.3 0-64 28.7-64 64v224c0 35.3 28.7 64 64 64h224c35.3 0 64-28.7 64-64v-64h-64v64zM160 130h64v92h-64v-92zm288 0h64v92h-64v-92zM290 352v-64h92v64h-92zm0-288V0h92v64h-92zM224 98V64h34V0h-34c-35.3 0-64 28.7-64 64v34M414 64h34v34h64V64c0-35.3-28.7-64-64-64h-34m34 254v34h-34v64h34c35.3 0 64-28.7 64-64v-34M258 288h-34v-34h-64v34c0 35.3 28.7 64 64 64h34"
              />
            </svg>
          </span>
        </slot>

        <slot name="success-icon" part="success-icon" hidden>
          <craft-icon name="check"></craft-icon>
        </slot>

        <slot name="error-icon" part="error-icon" hidden>
          <craft-icon name="x"></craft-icon>
        </slot>
      </craft-copy-button>
    `}};Cr.styles=[br,xr],l([b()],Cr.prototype,`status`,void 0),l([S(`slot[name="copy-icon"]`)],Cr.prototype,`copyIconEl`,void 0),l([S(`slot[name="success-icon"]`)],Cr.prototype,`successIconEl`,void 0),l([S(`slot[name="error-icon"]`)],Cr.prototype,`errorIconEl`,void 0),l([S(`craft-copy-button`)],Cr.prototype,`copyButtonEl`,void 0),l([y({type:String})],Cr.prototype,`value`,void 0),l([y({type:Boolean,reflect:!0})],Cr.prototype,`disabled`,void 0),l([y({attribute:`feedback-duration`,type:Number})],Cr.prototype,`feedbackDuration`,void 0),l([y({reflect:!1})],Cr.prototype,`tooltipLabel`,void 0),customElements.get(`craft-copy-attribute`)||customElements.define(`craft-copy-attribute`,Cr);var wr=new WeakMap;function Tr(e,t){let n=t;for(;n;){if(wr.get(n)===e)return!0;n=Object.getPrototypeOf(n)}return!1}function Er(e){return t=>{if(Tr(e,t))return t;let n=e(t);return wr.set(n,e),n}}var Dr=Er(e=>class extends e{static get properties(){return{disabled:{type:Boolean,reflect:!0}}}constructor(){super(),this._requestedToBeDisabled=!1,this.__isUserSettingDisabled=!0,this.__restoreDisabledTo=!1,this.disabled=!1}makeRequestToBeDisabled(){this._requestedToBeDisabled===!1&&(this._requestedToBeDisabled=!0,this.__restoreDisabledTo=this.disabled,this.__internalSetDisabled(!0))}retractRequestToBeDisabled(){this._requestedToBeDisabled===!0&&(this._requestedToBeDisabled=!1,this.__internalSetDisabled(this.__restoreDisabledTo))}__internalSetDisabled(e){this.__isUserSettingDisabled=!1,this.disabled=e,this.__isUserSettingDisabled=!0}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`disabled`&&(this.__isUserSettingDisabled&&(this.__restoreDisabledTo=this.disabled),this.disabled===!1&&this._requestedToBeDisabled===!0&&this.__internalSetDisabled(!0))}click(){this.disabled||super.click()}}),Or=Er(e=>class extends Dr(e){static get properties(){return{tabIndex:{type:Number,reflect:!0,attribute:`tabindex`}}}constructor(){super(),this.__isUserSettingTabIndex=!0,this.__restoreTabIndexTo=0,this.__internalSetTabIndex(0)}makeRequestToBeDisabled(){super.makeRequestToBeDisabled(),this._requestedToBeDisabled===!1&&this.tabIndex!=null&&(this.__restoreTabIndexTo=this.tabIndex)}retractRequestToBeDisabled(){super.retractRequestToBeDisabled(),this._requestedToBeDisabled===!0&&this.__internalSetTabIndex(this.__restoreTabIndexTo)}static enabledWarnings=super.enabledWarnings?.filter(e=>e!==`change-in-update`)||[];__internalSetTabIndex(e){this.__isUserSettingTabIndex=!1,this.tabIndex=e,this.__isUserSettingTabIndex=!0}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`disabled`&&(this.disabled?this.__internalSetTabIndex(-1):this.__internalSetTabIndex(this.__restoreTabIndexTo)),e===`tabIndex`&&(this.__isUserSettingTabIndex&&this.tabIndex!=null&&(this.__restoreTabIndexTo=this.tabIndex),this.tabIndex!==-1&&this._requestedToBeDisabled===!0&&this.__internalSetTabIndex(-1))}firstUpdated(e){super.firstUpdated(e),this.disabled&&this.__internalSetTabIndex(-1)}}),{I:kr}=u,Ar=e=>e===null||typeof e!=`object`&&typeof e!=`function`,jr=(e,t)=>t===void 0?e?._$litType$!==void 0:e?._$litType$===t,Mr=e=>e.strings===void 0,Nr=()=>document.createComment(``),Pr=(e,t,n)=>{let r=e._$AA.parentNode,i=t===void 0?e._$AB:t._$AA;if(n===void 0)n=new kr(r.insertBefore(Nr(),i),r.insertBefore(Nr(),i),e,e.options);else{let t=n._$AB.nextSibling,a=n._$AM,o=a!==e;if(o){let t;n._$AQ?.(e),n._$AM=e,n._$AP!==void 0&&(t=e._$AU)!==a._$AU&&n._$AP(t)}if(t!==i||o){let e=n._$AA;for(;e!==t;){let t=e.nextSibling;r.insertBefore(e,i),e=t}}}return n},Fr=(e,t,n=e)=>(e._$AI(t,n),e),Ir={},Lr=(e,t=Ir)=>e._$AH=t,Rr=e=>e._$AH,zr=e=>{e._$AR(),e._$AA.remove()};function Br(e){return e instanceof Node?`node`:jr(e)?`template-result`:!Array.isArray(e)&&typeof e==`object`&&`template`in e?`slot-rerender-object`:null}var Vr=Er(e=>class extends e{get slots(){return{}}constructor(){super(),this.__renderMetaPerSlot=new Map,this.__slotsThatNeedRerender=new Set,this.__slotsProvidedByUserOnFirstConnected=new Set,this.__privateSlots=new Set}connectedCallback(){super.connectedCallback(),this._connectSlotMixin()}__rerenderSlot(e){let t=this.slots[e]();this.__renderTemplateInScopedContext({renderAsDirectHostChild:t.renderAsDirectHostChild,template:t.template,slotName:e}),t.afterRender?.()}update(e){super.update(e);for(let e of this.__slotsThatNeedRerender)this.__rerenderSlot(e)}__renderTemplateInScopedContext({template:e,slotName:t,renderAsDirectHostChild:n}){if(!this.__renderMetaPerSlot.has(t)){let r=!!ShadowRoot.prototype.createElement;this.shadowRoot||console.error(`[SlotMixin] No shadowRoot was found`);let i=(r?this.shadowRoot:document).createElement(`div`),a=document.createComment(`_start_slot_${t}_`),o=document.createComment(`_end_slot_${t}_`);i.appendChild(a),i.appendChild(o);let{creationScope:s,host:c}=this.renderOptions;if(h(e,i,{renderBefore:o,creationScope:s,host:c}),n){let e=Array.from(i.childNodes);this.__appendNodes({nodes:e,renderParent:this,slotName:t})}else i.slot=t,this.appendChild(i);this.__renderMetaPerSlot.set(t,{renderTargetThatRespectsShadowRootScoping:i,renderBefore:o});return}let{renderBefore:r,renderTargetThatRespectsShadowRootScoping:i}=this.__renderMetaPerSlot.get(t),a=n?this:i,{creationScope:o,host:s}=this.renderOptions;h(e,a,{creationScope:o,host:s,renderBefore:r}),n&&r.previousElementSibling&&!r.previousElementSibling.slot&&(r.previousElementSibling.slot=t)}__appendNodes({nodes:e,renderParent:t=this,slotName:n}){for(let r of e)r instanceof Element&&n&&n!==``&&r.setAttribute(`slot`,n),t.appendChild(r)}__initSlots(e){for(let t of e){if(this.__slotsProvidedByUserOnFirstConnected.has(t))continue;let e=this.slots[t]();if(e!==void 0)switch(this.__isConnectedSlotMixin||this.__privateSlots.add(t),Br(e)){case`template-result`:this.__renderTemplateInScopedContext({template:e,renderAsDirectHostChild:!0,slotName:t});break;case`node`:this.__appendNodes({nodes:[e],renderParent:this,slotName:t});break;case`slot-rerender-object`:this.__slotsThatNeedRerender.add(t),e.firstRenderOnConnected&&this.__rerenderSlot(t);break;default:throw Error(`Slot "${t}" configured inside "get slots()" (in prototype) of ${this.constructor.name} may return these types: TemplateResult | Node | {template:TemplateResult, afterRender?:function} | undefined.
              You provided: ${e}`)}}}_connectSlotMixin(){if(this.__isConnectedSlotMixin)return;let e=Object.keys(this.slots);for(let t of e)(t===``?Array.from(this.children).find(e=>!e.hasAttribute(`slot`)):Array.from(this.children).find(e=>e.slot===t))&&this.__slotsProvidedByUserOnFirstConnected.add(t);this.__initSlots(e),this.__isConnectedSlotMixin=!0}_isPrivateSlot(e){return this.__privateSlots.has(e)}});function Hr(e=`google-chrome`){let t=globalThis.navigator,n=!!t.userAgentData&&t.userAgentData.brands.some(e=>e.brand===`Chromium`);if(e===`chromium`)return n;let r=globalThis.navigator?.vendor,i=globalThis.opr!==void 0,a=globalThis.userAgent?.indexOf(`Edge`)>-1,o=globalThis.userAgent?.match(`CriOS`);if(e===`ios`)return o;if(e===`google-chrome`)return n!=null&&r===`Google Inc.`&&i===!1&&a===!1}var Ur={isIE11:/Trident/.test(globalThis.navigator?.userAgent),isChrome:Hr(),isIOSChrome:Hr(`ios`),isChromium:Hr(`chromium`),isFirefox:globalThis.navigator?.userAgent.toLowerCase().indexOf(`firefox`)>-1,isMac:globalThis.navigator?.appVersion?.indexOf(`Mac`)!==-1,isIOS:/iPhone|iPad|iPod/i.test(globalThis.navigator?.userAgent),isMacSafari:globalThis.navigator?.vendor&&globalThis.navigator?.vendor.indexOf(`Apple`)>-1&&globalThis.navigator?.userAgent&&globalThis.navigator?.userAgent.indexOf(`CriOS`)===-1&&globalThis.navigator?.userAgent.indexOf(`FxiOS`)===-1&&globalThis.navigator?.appVersion.indexOf(`Mac`)!==-1};function Wr(e=``){return`${e.length>0?`${e}-`:``}${Math.random().toString(36).substr(2,10)}`}var Gr=e=>e.key===` `||e.key===`Enter`,Kr=e=>e.key===` `,qr=class extends Or(v){static get properties(){return{active:{type:Boolean,reflect:!0},type:{type:String,reflect:!0}}}render(){return d` <div class="button-content"><slot></slot></div> `}static get styles(){return[p`
        :host {
          position: relative;
          display: inline-flex;
          box-sizing: border-box;
          vertical-align: middle;
          line-height: 24px;
          background-color: #eee; /* minimal styling to make it recognizable as btn */
          padding: 8px; /* padding to fix with min-height */
          outline: none; /* focus style handled below */
          cursor: default; /* we should always see the default arrow, never a caret */
          /* TODO: remove, native button also allows selection. Could be usability concern... */
          user-select: none;
          -webkit-user-select: none;
          -moz-user-select: none;
          -ms-user-select: none;
        }

        :host::before {
          content: '';

          /* center vertically and horizontally */
          position: absolute;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);

          /* Minimum click area to meet [WCAG Success Criterion 2.5.5 Target Size (Enhanced)](https://www.w3.org/TR/WCAG22/#target-size-enhanced) */
          min-height: 44px;
          min-width: 44px;
          width: 100%;
          height: 100%;
        }

        .button-content {
          display: flex;
          align-items: center;
          justify-content: center;
        }

        /* Show focus styles on keyboard focus. */
        :host(:focus:not([disabled])),
        :host(:focus-visible) {
          /* if you extend, please overwrite */
          outline: 2px solid #bde4ff;
        }

        /* Hide focus styles if they're not needed, for example,
        when an element receives focus via the mouse. */
        :host(:focus:not(:focus-visible)) {
          outline: 0;
        }

        :host(:hover) {
          /* if you extend, please overwrite */
          background: #f4f6f7;
        }

        :host(:active), /* keep native :active to render quickly where possible */
        :host([active]) /* use custom [active] to fix IE11 */ {
          /* if you extend, please overwrite */
          background: gray;
        }

        :host([hidden]) {
          display: none;
        }

        :host([disabled]) {
          pointer-events: none;
          /* if you extend, please overwrite */
          background: lightgray;
          color: #adadad;
          fill: #adadad;
        }
      `]}constructor(){super(),this.type=`button`,this.active=!1,this.__setupEvents()}connectedCallback(){super.connectedCallback(),this.hasAttribute(`role`)||this.setAttribute(`role`,`button`)}updated(e){super.updated(e),e.has(`disabled`)&&(this.disabled?this.setAttribute(`aria-disabled`,`true`):this.getAttribute(`aria-disabled`)!==null&&this.removeAttribute(`aria-disabled`))}__setupEvents(){this.addEventListener(`mousedown`,this.__mousedownHandler),this.addEventListener(`keydown`,this.__keydownHandler),this.addEventListener(`keyup`,this.__keyupHandler)}__mousedownHandler(){this.active=!0;let e=()=>{this.active=!1,document.removeEventListener(`mouseup`,e),this.removeEventListener(`mouseup`,e)};document.addEventListener(`mouseup`,e),this.addEventListener(`mouseup`,e)}__keydownHandler(e){if(this.active||!Gr(e)){Kr(e)&&e.preventDefault();return}Kr(e)&&e.preventDefault(),this.active=!0;let t=e=>{Gr(e)&&(this.active=!1,document.removeEventListener(`keyup`,t,!0))};document.addEventListener(`keyup`,t,!0)}__keyupHandler(e){if(Gr(e)){if(e.target&&e.target!==this)return;this.click()}}},Jr=class extends qr{constructor(){super(),this.type=`reset`,this.__setupDelegationInConstructor(),this.__submitAndResetHelperButton=document.createElement(`button`),this.__preventEventLeakage=this.__preventEventLeakage.bind(this)}connectedCallback(){super.connectedCallback(),this.updateComplete.then(()=>{this._setupSubmitAndResetHelperOnConnected()})}disconnectedCallback(){super.disconnectedCallback(),this._teardownSubmitAndResetHelperOnDisconnected()}__preventEventLeakage(e){e.target===this.__submitAndResetHelperButton&&e.stopImmediatePropagation()}_setupSubmitAndResetHelperOnConnected(){this.appendChild(this.__submitAndResetHelperButton),this._form=this.__submitAndResetHelperButton.form,this.removeChild(this.__submitAndResetHelperButton),this._form&&this._form.addEventListener(`click`,this.__preventEventLeakage)}_teardownSubmitAndResetHelperOnDisconnected(){this._form&&this._form.removeEventListener(`click`,this.__preventEventLeakage)}async __clickDelegationHandler(e){this._form||await this.updateComplete,(this.type===`submit`||this.type===`reset`)&&e.target===this&&this._form&&(this.__submitAndResetHelperButton.type=this.type,this._form.appendChild(this.__submitAndResetHelperButton),this.__submitAndResetHelperButton.click(),this._form.removeChild(this.__submitAndResetHelperButton))}__setupDelegationInConstructor(){this.addEventListener(`click`,this.__clickDelegationHandler,!0)}},Yr=new WeakMap;function Xr(){let e=document.createElement(`button`);return e.tabIndex=-1,e.type=`submit`,e.setAttribute(`aria-hidden`,`true`),e.style.cssText=`
    position: absolute;
    top: 0;
    left: 0;
    clip: rect(0 0 0 0);
    clip-path: inset(50%);
    overflow: hidden;
    white-space: nowrap;
    height: 1px;
    width: 1px;
    padding: 0; /* reset default agent styles */
    border: 0; /* reset default agent styles */
  `,e}var Zr=class extends Jr{get _nativeButtonNode(){return Yr.get(this._form)?.helper||null}constructor(){super(),this.type=`submit`,this.__implicitSubmitHelperButton=null}_setupSubmitAndResetHelperOnConnected(){if(super._setupSubmitAndResetHelperOnConnected(),!this._form||this.type!==`submit`)return;let e=this._form;if(!Yr.get(this._form)){let t=Xr(),n=document.createElement(`div`);n.appendChild(t),Yr.set(this._form,{lionButtons:new Set,helper:t,observer:new MutationObserver(()=>{e.appendChild(n)})}),e.appendChild(n),Yr.get(e)?.observer.observe(n,{childList:!0})}Yr.get(e)?.lionButtons.add(this)}_teardownSubmitAndResetHelperOnDisconnected(){if(super._teardownSubmitAndResetHelperOnDisconnected(),this._form){let e=Yr.get(this._form);e&&(e.lionButtons.delete(this),e.lionButtons.size||(this._form.contains(e.helper)&&e.helper.remove(),Yr.get(this._form)?.observer.disconnect(),Yr.delete(this._form)))}}},Qr=p`
  :host {
    cursor: pointer;
    font: inherit;
    display: inline-flex;
    justify-content: center;
    gap: var(--c-spacing-sm);
    align-items: center;
    border-radius: var(--c-button-radius, var(--c-form-control-radius));
    padding-inline: var(
      --c-button-spacing-inline,
      var(--c-form-control-spacing-inline)
    );
    padding-block: 0;
    width: auto;
    min-height: var(--c-button-height, var(--c-size-control-md));
    min-width: var(--c-button-width, var(--c-size-control-md));
    white-space: nowrap;

    /* Colorable styles */
    color: var(--c-color-on-loud, var(--c-color-neutral-on-loud));
    border-width: var(--c-button-border-width, 1px);
    border-style: var(--c-button-border-style, solid);
    border-color: var(
      --c-color-border-loud,
      var(--c-color-neutral-border-loud)
    );
    background-color: var(
      --c-color-fill-loud,
      var(--c-color-neutral-fill-loud)
    );
  }

  @media (hover: hover) {
    :host(:hover) {
      background-color: color-mix(
        in oklab,
        var(--c-color-fill-loud, var(--c-button-default-fill)),
        var(--c-color-mix-hover)
      );
      color: var(--c-color-on-loud);
    }
  }

  :host(:not(:disabled):not(.loading):active) {
    color: var(--c-color-on-loud);
    background-color: color-mix(
      in oklab,
      var(--c-color-fill-loud, var(--c-color-neutral-fill-normal)),
      var(--c-color-mix-active)
    );
  }

  /*
  Sizes
   */
  :host([size~='zero']) {
    min-width: 0;
    min-height: 0;
    padding-inline: 0;
  }

  :host([size~='small']) {
    padding-inline: var(--c-spacing-sm);
    min-width: var(--c-size-control-sm);
    min-height: var(--c-size-control-sm);
    font-size: 0.9em;
  }

  :host([size~='large']) {
    padding-inline: var(--c-spacing-lg);
    min-height: var(--c-size-control-lg);
    min-width: var(--c-size-control-lg);
  }

  :host([loading]) {
    position: relative;

    .prefix,
    .label,
    .suffix {
      visibility: hidden;
    }

    craft-spinner {
      --size: 1.25em;
      position: absolute;
      inset-block-start: calc(50% - var(--size) / 2);
      inset-inline-start: calc(50% - var(--size) / 2);
    }
  }

  /*
  Icon
   */
  :host([icon]) {
    aspect-ratio: 1;
    padding-inline: 0;
    padding-block: 0;
    display: inline-flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    line-height: 1;
  }

  :host([icon][size~='small']) {
    font-size: 0.8em;
  }

  /*
  Appearances 
   */

  /* Plain */
  :host([appearance~='plain']) {
    background-color: transparent;
    border-color: transparent;
    color: inherit;
  }

  :host([appearance~='plain']:hover) {
    background-color: color-mix(
      in oklab,
      var(--c-color-fill-quiet, var(--c-button-default-fill)),
      var(--c-color-mix-hover)
    );
    color: var(--c-color-on-quiet);
  }

  :host([appearance~='plain']:active) {
    color: var(--c-color-on-quiet, var(--c-color-neutral-on-quiet));
    background-color: color-mix(
      in oklab,
      var(--c-color-fill-quiet, var(--c-color-neutral-fill-quiet)),
      var(--c-color-mix-active)
    );
  }

  /* Filled */
  :host([appearance~='filled']) {
    background-color: var(
      --c-color-fill-normal,
      var(--c-color-neutral-fill-normal)
    );
    border-color: transparent;
    color: var(--c-color-on-normal, var(--c-color-neutral-on-normal));
  }

  :host([appearance~='filled']:hover) {
    background-color: color-mix(
      in oklab,
      var(--c-color-fill-normal, var(--c-color-neutral-fill-normal)),
      var(--c-color-mix-hover)
    );
    color: var(--c-color-on-normal, var(--c-color-neutral-on-normal));
  }

  :host([appearance~='filled']:active) {
    color: var(--c-color-on-quiet, var(--c-color-neutral-on-quiet));
    background-color: color-mix(
      in oklab,
      var(--c-color-fill-quiet, var(--c-color-neutral-fill-quiet)),
      var(--c-color-mix-active)
    );
  }

  /* Dashed */
  :host([appearance~='dashed']) {
    background-color: transparent;
    border-color: var(--c-color-border-normal);
    border-style: dashed;
    color: var(--c-color-on-quiet);
  }

  :host([appearance~='dashed']:hover) {
    background-color: color-mix(
      in oklab,
      var(--c-color-fill-quiet, var(--c-button-default-fill)),
      var(--c-color-mix-hover)
    );
    color: var(--c-color-on-quiet);
  }

  :host([appearance~='dashed']:active) {
    color: var(--c-color-on-quiet, var(--c-color-neutral-on-quiet));
    background-color: color-mix(
      in oklab,
      var(--c-color-fill-quiet, var(--c-color-neutral-fill-quiet)),
      var(--c-color-mix-active)
    );
  }

  /*
  Variants (aka fill colors) 
   */
  :host([variant~='primary']) {
    --c-color-fill-loud: var(--c-color-brand-fill-loud);
    --c-color-fill-normal: var(--c-color-brand-fill-normal);
    --c-color-fill-quiet: var(--c-color-brand-fill-quiet);
    --c-color-border-loud: var(--c-color-brand-border-loud);
    --c-color-border-normal: var(--c-color-brand-border-normal);
    --c-color-border-quiet: var(--c-color-brand-border-quiet);
    --c-color-on-loud: var(--c-color-brand-on-loud);
    --c-color-on-normal: var(--c-color-brand-on-normal);
    --c-color-on-quiet: var(--c-color-brand-on-quiet);
  }

  :host([variant='default']) {
    --c-color-fill-loud: var(--c-color-neutral-fill-loud);
    --c-color-fill-normal: var(--c-color-neutral-fill-normal);
    --c-color-fill-quiet: var(--c-color-neutral-fill-quiet);
    --c-color-border-loud: var(--c-color-neutral-border-loud);
    --c-color-border-normal: var(--c-color-neutral-border-normal);
    --c-color-border-quiet: var(--c-color-neutral-border-quiet);
    --c-color-on-loud: var(--c-color-neutral-on-loud);
    --c-color-on-normal: var(--c-color-neutral-on-normal);
    --c-color-on-quiet: var(--c-color-neutral-on-quiet);
  }

  :host([variant~='danger']) {
    --c-color-fill-loud: var(--c-color-danger-fill-loud);
    --c-color-fill-normal: var(--c-color-danger-fill-normal);
    --c-color-fill-quiet: var(--c-color-danger-fill-quiet);
    --c-color-border-loud: var(--c-color-danger-border-loud);
    --c-color-border-normal: var(--c-color-danger-border-normal);
    --c-color-border-quiet: var(--c-color-danger-border-quiet);
    --c-color-on-loud: var(--c-color-danger-on-loud);
    --c-color-on-normal: var(--c-color-danger-on-normal);
    --c-color-on-quiet: var(--c-color-danger-on-quiet);
  }

  .button-content {
    display: flex;
    align-items: center;
    gap: 0.25em;
    width: 100%;
  }

  .prefix,
  .suffix {
    display: inline-flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
  }

  .button-content--start {
    justify-content: start;
  }

  .button-content--end {
    justify-content: end;
  }

  craft-button-group craft-button {
    border-radius: 0;
  }

  craft-button-reset,
  craft-button-submit {
    /* Temporarily make it very obvious when these are used */
    outline: 10px solid var(--c-button-danger-border);
  }

  .a11y-error {
    position: relative;
    outline: 2px solid var(--c-color-danger-border-normal) !important;
    background-color: rgba(255, 0, 0, 0.1) !important;

    &:after {
      content: '!';
      position: absolute;
      display: inline-flex;
      font-size: calc(11rem / 16);
      padding: 0.125em 0.5em 0.25em;
      inset-block-start: -2px;
      inset-inline-start: 0;
      background: var(--c-color-danger-bg-emphasis);
      color: white;
      transform: translateX(-100%);
    }
  }
`,$r=Object.prototype.toString;function ei(e){return typeof e==`function`||$r.call(e)===`[object Function]`}function ti(e){var t=Number(e);return isNaN(t)?0:t===0||!isFinite(t)?t:(t>0?1:-1)*Math.floor(Math.abs(t))}var ni=2**53-1;function ri(e){var t=ti(e);return Math.min(Math.max(t,0),ni)}function ii(e,t){var n=Array,r=Object(e);if(e==null)throw TypeError(`Array.from requires an array-like object - not null or undefined`);if(t!==void 0&&!ei(t))throw TypeError(`Array.from: when provided, the second argument must be a function`);for(var i=ri(r.length),a=ei(n)?Object(new n(i)):Array(i),o=0,s;o<i;)s=r[o],t?a[o]=t(s,o):a[o]=s,o+=1;return a.length=i,a}function ai(e){"@babel/helpers - typeof";return ai=typeof Symbol==`function`&&typeof Symbol.iterator==`symbol`?function(e){return typeof e}:function(e){return e&&typeof Symbol==`function`&&e.constructor===Symbol&&e!==Symbol.prototype?`symbol`:typeof e},ai(e)}function oi(e,t){if(!(e instanceof t))throw TypeError(`Cannot call a class as a function`)}function si(e,t){for(var n=0;n<t.length;n++){var r=t[n];r.enumerable=r.enumerable||!1,r.configurable=!0,`value`in r&&(r.writable=!0),Object.defineProperty(e,ui(r.key),r)}}function ci(e,t,n){return t&&si(e.prototype,t),n&&si(e,n),Object.defineProperty(e,`prototype`,{writable:!1}),e}function li(e,t,n){return t=ui(t),t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function ui(e){var t=di(e,`string`);return ai(t)==`symbol`?t:t+``}function di(e,t){if(ai(e)!=`object`||!e)return e;var n=e[Symbol.toPrimitive];if(n!==void 0){var r=n.call(e,t||`default`);if(ai(r)!=`object`)return r;throw TypeError(`@@toPrimitive must return a primitive value.`)}return(t===`string`?String:Number)(e)}var fi=function(){function e(){var t=arguments.length>0&&arguments[0]!==void 0?arguments[0]:[];oi(this,e),li(this,`items`,void 0),this.items=t}return ci(e,[{key:`add`,value:function(e){return this.has(e)===!1&&this.items.push(e),this}},{key:`clear`,value:function(){this.items=[]}},{key:`delete`,value:function(e){var t=this.items.length;return this.items=this.items.filter(function(t){return t!==e}),t!==this.items.length}},{key:`forEach`,value:function(e){var t=this;this.items.forEach(function(n){e(n,n,t)})}},{key:`has`,value:function(e){return this.items.indexOf(e)!==-1}},{key:`size`,get:function(){return this.items.length}}])}(),pi=typeof Set>`u`?Set:fi;function mi(e){return e.localName??e.tagName.toLowerCase()}var hi={article:`article`,aside:`complementary`,button:`button`,datalist:`listbox`,dd:`definition`,details:`group`,dialog:`dialog`,dt:`term`,fieldset:`group`,figure:`figure`,form:`form`,footer:`contentinfo`,h1:`heading`,h2:`heading`,h3:`heading`,h4:`heading`,h5:`heading`,h6:`heading`,header:`banner`,hr:`separator`,html:`document`,legend:`legend`,li:`listitem`,math:`math`,main:`main`,menu:`list`,nav:`navigation`,ol:`list`,optgroup:`group`,option:`option`,output:`status`,progress:`progressbar`,section:`region`,summary:`button`,table:`table`,tbody:`rowgroup`,textarea:`textbox`,tfoot:`rowgroup`,td:`cell`,th:`columnheader`,thead:`rowgroup`,tr:`row`,ul:`list`},gi={caption:new Set([`aria-label`,`aria-labelledby`]),code:new Set([`aria-label`,`aria-labelledby`]),deletion:new Set([`aria-label`,`aria-labelledby`]),emphasis:new Set([`aria-label`,`aria-labelledby`]),generic:new Set([`aria-label`,`aria-labelledby`,`aria-roledescription`]),insertion:new Set([`aria-label`,`aria-labelledby`]),none:new Set([`aria-label`,`aria-labelledby`]),paragraph:new Set([`aria-label`,`aria-labelledby`]),presentation:new Set([`aria-label`,`aria-labelledby`]),strong:new Set([`aria-label`,`aria-labelledby`]),subscript:new Set([`aria-label`,`aria-labelledby`]),superscript:new Set([`aria-label`,`aria-labelledby`])};function _i(e,t){return[`aria-atomic`,`aria-busy`,`aria-controls`,`aria-current`,`aria-description`,`aria-describedby`,`aria-details`,`aria-dropeffect`,`aria-flowto`,`aria-grabbed`,`aria-hidden`,`aria-keyshortcuts`,`aria-label`,`aria-labelledby`,`aria-live`,`aria-owns`,`aria-relevant`,`aria-roledescription`].some(function(n){var r;return e.hasAttribute(n)&&!((r=gi[t])!=null&&r.has(n))})}function vi(e,t){return _i(e,t)}function yi(e){var t=xi(e);if(t===null||Si.indexOf(t)!==-1){var n=bi(e);if(Si.indexOf(t||``)===-1||vi(e,n||``))return n}return t}function bi(e){var t=hi[mi(e)];if(t!==void 0)return t;switch(mi(e)){case`a`:case`area`:case`link`:if(e.hasAttribute(`href`))return`link`;break;case`img`:return e.getAttribute(`alt`)===``&&!vi(e,`img`)?`presentation`:`img`;case`input`:var n=e.type;switch(n){case`button`:case`image`:case`reset`:case`submit`:return`button`;case`checkbox`:case`radio`:return n;case`range`:return`slider`;case`email`:case`tel`:case`text`:case`url`:return e.hasAttribute(`list`)?`combobox`:`textbox`;case`search`:return e.hasAttribute(`list`)?`combobox`:`searchbox`;case`number`:return`spinbutton`;default:return null}case`select`:return e.hasAttribute(`multiple`)||e.size>1?`listbox`:`combobox`}return null}function xi(e){var t=e.getAttribute(`role`);if(t!==null){var n=t.trim().split(` `)[0];if(n.length>0)return n}return null}var Si=[`presentation`,`none`];function Ci(e){return e!==null&&e.nodeType===e.ELEMENT_NODE}function wi(e){return Ci(e)&&mi(e)===`caption`}function Ti(e){return Ci(e)&&mi(e)===`input`}function Ei(e){return Ci(e)&&mi(e)===`optgroup`}function Di(e){return Ci(e)&&mi(e)===`select`}function Oi(e){return Ci(e)&&mi(e)===`table`}function ki(e){return Ci(e)&&mi(e)===`textarea`}function Ai(e){var t=(e.ownerDocument===null?e:e.ownerDocument).defaultView;if(t===null)throw TypeError(`no window available`);return t}function ji(e){return Ci(e)&&mi(e)===`fieldset`}function Mi(e){return Ci(e)&&mi(e)===`legend`}function Ni(e){return Ci(e)&&mi(e)===`slot`}function Pi(e){return Ci(e)&&e.ownerSVGElement!==void 0}function Fi(e){return Ci(e)&&mi(e)===`svg`}function Ii(e){return Pi(e)&&mi(e)===`title`}function Li(e,t){if(Ci(e)&&e.hasAttribute(t)){var n=e.getAttribute(t).split(` `),r=e.getRootNode?e.getRootNode():e.ownerDocument;return n.map(function(e){return r.getElementById(e)}).filter(function(e){return e!==null})}return[]}function Ri(e,t){return Ci(e)?t.indexOf(yi(e))!==-1:!1}function zi(e){return e.trim().replace(/\s\s+/g,` `)}function Bi(e,t){if(!Ci(e))return!1;if(e.hasAttribute(`hidden`)||e.getAttribute(`aria-hidden`)===`true`)return!0;var n=t(e);return n.getPropertyValue(`display`)===`none`||n.getPropertyValue(`visibility`)===`hidden`}function Vi(e){return Ri(e,[`button`,`combobox`,`listbox`,`textbox`])||Hi(e,`range`)}function Hi(e,t){if(!Ci(e))return!1;switch(t){case`range`:return Ri(e,[`meter`,`progressbar`,`scrollbar`,`slider`,`spinbutton`]);default:throw TypeError(`No knowledge about abstract role '${t}'. This is likely a bug :(`)}}function Ui(e,t){var n=ii(e.querySelectorAll(t));return Li(e,`aria-owns`).forEach(function(e){n.push.apply(n,ii(e.querySelectorAll(t)))}),n}function Wi(e){return Di(e)?e.selectedOptions||Ui(e,`[selected]`):Ui(e,`[aria-selected="true"]`)}function Gi(e){return Ri(e,Si)}function Ki(e){return wi(e)}function qi(e){return Ri(e,[`button`,`cell`,`checkbox`,`columnheader`,`gridcell`,`heading`,`label`,`legend`,`link`,`menuitem`,`menuitemcheckbox`,`menuitemradio`,`option`,`radio`,`row`,`rowheader`,`switch`,`tab`,`tooltip`,`treeitem`])}function Ji(e){return!1}function Yi(e){return Ti(e)||ki(e)?e.value:e.textContent||``}function Xi(e){var t=e.getPropertyValue(`content`);return/^["'].*["']$/.test(t)?t.slice(1,-1):``}function Zi(e){var t=mi(e);return t===`button`||t===`input`&&e.getAttribute(`type`)!==`hidden`||t===`meter`||t===`output`||t===`progress`||t===`select`||t===`textarea`}function Qi(e){if(Zi(e))return e;var t=null;return e.childNodes.forEach(function(e){if(t===null&&Ci(e)){var n=Qi(e);n!==null&&(t=n)}}),t}function $i(e){if(e.control!==void 0)return e.control;var t=e.getAttribute(`for`);return t===null?Qi(e):e.ownerDocument.getElementById(t)}function ea(e){var t=e.labels;if(t===null)return t;if(t!==void 0)return ii(t);if(!Zi(e))return null;var n=e.ownerDocument;return ii(n.querySelectorAll(`label`)).filter(function(t){return $i(t)===e})}function ta(e){var t=e.assignedNodes();return t.length===0?ii(e.childNodes):t}function na(e){var t=arguments.length>1&&arguments[1]!==void 0?arguments[1]:{},n=new pi,r=typeof Map>`u`?void 0:new Map,i=Ai(e),a=t.compute,o=a===void 0?`name`:a,s=t.computedStyleSupportsPseudoElements,c=s===void 0?t.getComputedStyle!==void 0:s,l=t.getComputedStyle,u=l===void 0?i.getComputedStyle.bind(i):l,d=t.hidden,f=d===void 0?!1:d,p=function(e,t){if(t!==void 0)throw Error(`use uncachedGetComputedStyle directly for pseudo elements`);if(r===void 0)return u(e);var n=r.get(e);if(n)return n;var i=u(e,t);return r.set(e,i),i};function m(e,t){var n=``;if(Ci(e)&&c&&(n=`${Xi(u(e,`::before`))} ${n}`),(Ni(e)?ta(e):ii(e.childNodes).concat(Li(e,`aria-owns`))).forEach(function(e){var r=v(e,{isEmbeddedInLabel:t.isEmbeddedInLabel,isReferenced:!1,recursion:!0}),i=(Ci(e)?p(e).getPropertyValue(`display`):`inline`)===`inline`?``:` `;n+=`${i}${r}${i}`}),Ci(e)&&c){var r=Xi(u(e,`::after`));n=`${n} ${r}`}return n.trim()}function h(e,t){var r=e.getAttributeNode(t);return r!==null&&!n.has(r)&&r.value.trim()!==``?(n.add(r),r.value):null}function g(e){return Ci(e)?h(e,`title`):null}function _(e){if(!Ci(e))return null;if(ji(e)){n.add(e);for(var t=ii(e.childNodes),r=0;r<t.length;r+=1){var i=t[r];if(Mi(i))return v(i,{isEmbeddedInLabel:!1,isReferenced:!1,recursion:!1})}}else if(Oi(e)){n.add(e);for(var a=ii(e.childNodes),o=0;o<a.length;o+=1){var s=a[o];if(wi(s))return v(s,{isEmbeddedInLabel:!1,isReferenced:!1,recursion:!1})}}else if(Fi(e)){n.add(e);for(var c=ii(e.childNodes),l=0;l<c.length;l+=1){var u=c[l];if(Ii(u))return u.textContent}return null}else if(mi(e)===`img`||mi(e)===`area`){var d=h(e,`alt`);if(d!==null)return d}else if(Ei(e)){var f=h(e,`label`);if(f!==null)return f}if(Ti(e)&&(e.type===`button`||e.type===`submit`||e.type===`reset`)){var p=h(e,`value`);if(p!==null)return p;if(e.type===`submit`)return`Submit`;if(e.type===`reset`)return`Reset`}var g=ea(e);if(g!==null&&g.length!==0)return n.add(e),ii(g).map(function(e){return v(e,{isEmbeddedInLabel:!0,isReferenced:!1,recursion:!0})}).filter(function(e){return e.length>0}).join(` `);if(Ti(e)&&e.type===`image`){var _=h(e,`alt`);if(_!==null)return _;var y=h(e,`title`);return y===null?`Submit Query`:y}if(Ri(e,[`button`])){var b=m(e,{isEmbeddedInLabel:!1,isReferenced:!1});if(b!==``)return b}return null}function v(e,t){if(n.has(e))return``;if(!f&&Bi(e,p)&&!t.isReferenced)return n.add(e),``;var r=Ci(e)?e.getAttributeNode(`aria-labelledby`):null,i=r!==null&&!n.has(r)?Li(e,`aria-labelledby`):[];if(o===`name`&&!t.isReferenced&&i.length>0)return n.add(r),i.map(function(e){return v(e,{isEmbeddedInLabel:t.isEmbeddedInLabel,isReferenced:!0,recursion:!1})}).join(` `);var a=t.recursion&&Vi(e)&&o===`name`;if(!a){var s=(Ci(e)&&e.getAttribute(`aria-label`)||``).trim();if(s!==``&&o===`name`)return n.add(e),s;if(!Gi(e)){var c=_(e);if(c!==null)return n.add(e),c}}if(Ri(e,[`menu`]))return n.add(e),``;if(a||t.isEmbeddedInLabel||t.isReferenced){if(Ri(e,[`combobox`,`listbox`])){n.add(e);var l=Wi(e);return l.length===0?Ti(e)?e.value:``:ii(l).map(function(e){return v(e,{isEmbeddedInLabel:t.isEmbeddedInLabel,isReferenced:!1,recursion:!0})}).join(` `)}if(Hi(e,`range`))return n.add(e),e.hasAttribute(`aria-valuetext`)?e.getAttribute(`aria-valuetext`):e.hasAttribute(`aria-valuenow`)?e.getAttribute(`aria-valuenow`):e.getAttribute(`value`)||``;if(Ri(e,[`textbox`]))return n.add(e),Yi(e)}if(qi(e)||Ci(e)&&t.isReferenced||Ki(e)||Ji(e)){var u=m(e,{isEmbeddedInLabel:t.isEmbeddedInLabel,isReferenced:!1});if(u!==``)return n.add(e),u}if(e.nodeType===e.TEXT_NODE)return n.add(e),e.textContent||``;if(t.recursion)return n.add(e),m(e,{isEmbeddedInLabel:t.isEmbeddedInLabel,isReferenced:!1});var d=g(e);return d===null?(n.add(e),``):(n.add(e),d)}return zi(v(e,{isEmbeddedInLabel:!1,isReferenced:o===`description`,recursion:!1}))}function ra(e){return Ri(e,[`caption`,`code`,`deletion`,`emphasis`,`generic`,`insertion`,`none`,`paragraph`,`presentation`,`strong`,`subscript`,`superscript`])}function ia(e){var t=arguments.length>1&&arguments[1]!==void 0?arguments[1]:{};return ra(e)?``:na(e,t)}var aa=class extends Zr{constructor(...e){super(...e),this.appearance=`accent`,this.variant=`default`,this.size=`medium`,this.loading=!1,this.align=`center`,this._hasAccessibilityError=!1}static get styles(){return[...super.styles,Qr]}async firstUpdated(e){super.firstUpdated(e),await this.updateComplete;let t=this.querySelectorAll(`craft-icon, craft-spinner`);await Promise.all(Array.from(t).map(e=>e.updateComplete)),this.accessibleName||=ia(this),this._hasAccessibilityError=!this.accessibleName||this.accessibleName.trim()===``}render(){return d`
      <div
        class="${T({"button-content":!0,"button-content--start":this.align===`start`,"button-content--end":this.align===`end`,"a11y-error":this._hasAccessibilityError})}"
        part="content"
      >
        <slot name="prefix" class="prefix" part="prefix"></slot>
        <slot class="label" part="label"></slot>
        <slot name="suffix" class="suffix" part="suffix"></slot>
      </div>
      ${this.loading?d`<craft-spinner part="spinner"></craft-spinner>`:_}
    `}};l([y()],aa.prototype,`accessibleName`,void 0),l([y({reflect:!0})],aa.prototype,`appearance`,void 0),l([y({reflect:!0})],aa.prototype,`variant`,void 0),l([y({reflect:!0})],aa.prototype,`size`,void 0),l([y({reflect:!0,type:Boolean})],aa.prototype,`loading`,void 0),l([y()],aa.prototype,`align`,void 0),l([b()],aa.prototype,`_hasAccessibilityError`,void 0),customElements.get(`craft-button`)||customElements.define(`craft-button`,aa);var oa=class extends Event{constructor(){super(`wa-load`,{bubbles:!0,cancelable:!1,composed:!0})}},sa=class extends Event{constructor(){super(`wa-error`,{bubbles:!0,cancelable:!1,composed:!0})}},ca=`:host {
  --primary-color: currentColor;
  --primary-opacity: 1;
  --secondary-color: currentColor;
  --secondary-opacity: 0.4;

  box-sizing: content-box;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  vertical-align: -0.125em;
}

/* Standard */
:host(:not([auto-width])) {
  width: 1.25em;
  height: 1em;
}

/* Auto-width */
:host([auto-width]) {
  width: auto;
  height: 1em;
}

svg {
  height: 1em;
  fill: currentColor;
  overflow: visible;

  /* Duotone colors with path-specific opacity fallback */
  path[data-duotone-primary] {
    color: var(--primary-color);
    opacity: var(--path-opacity, var(--primary-opacity));
  }

  path[data-duotone-secondary] {
    color: var(--secondary-color);
    opacity: var(--path-opacity, var(--secondary-opacity));
  }
}
`,la=Symbol(),ua=Symbol(),da,fa=new Map,pa=class extends tt{constructor(){super(...arguments),this.svg=null,this.autoWidth=!1,this.swapOpacity=!1,this.label=``,this.library=`default`,this.resolveIcon=async(e,t)=>{let n;if(t?.spriteSheet){this.hasUpdated||await this.updateComplete,this.svg=d`<svg part="svg">
        <use part="use" href="${e}"></use>
      </svg>`,await this.updateComplete;let n=this.shadowRoot.querySelector(`[part='svg']`);return typeof t.mutator==`function`&&t.mutator(n,this),this.svg}try{if(n=await fetch(e,{mode:`cors`}),!n.ok)return n.status===410?la:ua}catch{return ua}try{let e=document.createElement(`div`);e.innerHTML=await n.text();let t=e.firstElementChild;if(t?.tagName?.toLowerCase()!==`svg`)return la;da||=new DOMParser;let r=da.parseFromString(t.outerHTML,`text/html`).body.querySelector(`svg`);return r?(r.part.add(`svg`),document.adoptNode(r)):la}catch{return la}}}connectedCallback(){super.connectedCallback(),Ne(this)}firstUpdated(e){super.firstUpdated(e),this.setIcon()}disconnectedCallback(){super.disconnectedCallback(),Pe(this)}getIconSource(){let e=Fe(this.library),t=this.family||Re();return this.name&&e?{url:e.resolver(this.name,t,this.variant,this.autoWidth),fromLibrary:!0}:{url:this.src,fromLibrary:!1}}handleLabelChange(){typeof this.label==`string`&&this.label.length>0?(this.setAttribute(`role`,`img`),this.setAttribute(`aria-label`,this.label),this.removeAttribute(`aria-hidden`)):(this.removeAttribute(`role`),this.removeAttribute(`aria-label`),this.setAttribute(`aria-hidden`,`true`))}async setIcon(){let{url:e,fromLibrary:t}=this.getIconSource(),n=t?Fe(this.library):void 0;if(!e){this.svg=null;return}let r=fa.get(e);r||(r=this.resolveIcon(e,n),fa.set(e,r));let i=await r;if(i===ua&&fa.delete(e),e===this.getIconSource().url){if(jr(i)){this.svg=i;return}switch(i){case ua:case la:this.svg=null,this.dispatchEvent(new sa);break;default:this.svg=i.cloneNode(!0),n?.mutator?.(this.svg,this),this.dispatchEvent(new oa)}}}updated(e){super.updated(e);let t=Fe(this.library),n=this.shadowRoot?.querySelector(`svg`);n&&t?.mutator?.(n,this)}render(){return this.hasUpdated?this.svg:d`<svg part="svg" fill="currentColor" width="16" height="16"></svg>`}};pa.css=ca,k([b()],pa.prototype,`svg`,2),k([y({reflect:!0})],pa.prototype,`name`,2),k([y({reflect:!0})],pa.prototype,`family`,2),k([y({reflect:!0})],pa.prototype,`variant`,2),k([y({attribute:`auto-width`,type:Boolean,reflect:!0})],pa.prototype,`autoWidth`,2),k([y({attribute:`swap-opacity`,type:Boolean,reflect:!0})],pa.prototype,`swapOpacity`,2),k([y()],pa.prototype,`src`,2),k([y()],pa.prototype,`label`,2),k([y({reflect:!0})],pa.prototype,`library`,2),k([mr(`label`)],pa.prototype,`handleLabelChange`,1),k([mr([`family`,`name`,`library`,`variant`,`src`,`autoWidth`,`swapOpacity`])],pa.prototype,`setIcon`,1),pa=k([x(`wa-icon`)],pa);var ma=class extends pa{static get styles(){return[pa.styles,p`
        :host {
          font-size: 0.8em;
        }
      `]}};customElements.get(`craft-icon`)||customElements.define(`craft-icon`,ma);var ha=p`
  :host {
    --color-start: red;
    --color-end: blue;
    --color-text: inherit;

    --size: calc(30rem / 16);
    display: contents;
  }

  .avatar {
    display: inline-flex;
    width: var(--size);
    aspect-ratio: 1;
    background-color: white;
    border-radius: var(--c-radius-full);
  }

  .avatar__text {
    line-height: 1;
    font-weight: 500;
    font-family: var(--c-font-body, sans-serif);
    text-anchor: middle;
    fill: currentColor;
    user-select: none;
    pointer-events: none;
  }
`,ga=class extends v{constructor(...e){super(...e),this.label=null,this._gradientId=null}connectedCallback(){super.connectedCallback(),this._gradientId=`avatar-gradient-${Math.random().toString(36).slice(2,8)}`}text(){return this.label?this.label.split(` `).map(e=>e.charAt(0).toUpperCase()).join(``):`?`}render(){return d`
      <span class="avatar">
        <svg
          viewBox="0 0 100 100"
          xmlns="http://www.w3.org/2000/svg"
          role="img"
        >
          ${this.label?d`<title>${this.label}</title>`:``}
          <defs>
            <linearGradient
              id="${this._gradientId}"
              x1="0"
              y1="1"
              x2="1"
              y2="0"
            >
              <stop offset="0%" style="stop-color:var(--color-start)"></stop>
              <stop offset="100%" style="stop-color:var(--color-end)"></stop>
            </linearGradient>
          </defs>
          <circle
            cx="50"
            cy="50"
            r="50"
            fill="url(#${this._gradientId})"
            opacity="0.25"
          ></circle>
          <text class="avatar__text" x="50" y="64" font-size="44">
            ${this.text()}
          </text>
        </svg>
      </span>
    `}};ga.styles=[ha],l([y()],ga.prototype,`label`,void 0),l([b()],ga.prototype,`_gradientId`,void 0),customElements.get(`craft-avatar`)||customElements.define(`craft-avatar`,ga);var _a=p`
  font: inherit;
  color: var(--c-input-text, var(--c-text-default));
  position: relative;
  min-height: var(--c-input-height, var(--c-size-control-md));
  border-width: var(--c-input-border-width, var(--c-form-control-border-width));
  border-style: var(--c-input-border-style, var(--c-form-control-border-style));
  border-color: var(--c-input-border-color, var(--c-form-control-border-color));
  border-radius: var(--c-input-radius, var(--c-radius-sm));
  padding-block: 0;
  width: 100%;
  flex: 1 1 auto;
  background-color: var(--c-input-fill, var(--c-form-control-fill));
  box-shadow: var(--c-input-shadow);

  /* Detect mobile devices and up the font size of inputs to avoid zoom on focus */
  @media (pointer: none), (pointer: coarse) {
    font-size: 1rem;
  }
`,va=p`
  :host(:not([label-sr-only])) .form-field__group-one {
    margin-block-end: var(--c-spacing-sm);
  }

  :host([has-feedback-for='error']) {
    color: var(--c-color-danger-on-normal);

    ::slotted([slot='input']) {
      border-color: var(--c-color-danger-border-loud);
    }
  }

  ::slotted(label) {
    line-height: 1;
    font-weight: bold;
    font-size: var(--text-sm);
  }

  .form-field__help-text {
    font-size: 1em;
    color: var(--c-text-quiet);
  }

  .input-group__after {
    margin-block-start: var(--c-spacing-sm);
  }
`,ya=p`
  ${va}

  ::slotted([slot='input']) {
    font: inherit;
    padding-block: 0;
    border: none;
    appearance: none;
    padding-inline: var(--c-input-spacing-inline);
    background-color: transparent;
  }

  .input-group__container {
    ${_a}
  }

  .input-group__prefix,
  .input-group__suffix {
    padding-inline: var(--c-input-spacing-inline);
    display: grid;
    place-items: center;
  }

  .input-group__prefix + .input-group__input {
    border-radius-start-start: 0;
    border-radius-start-end: 0;
  }

  :host([size~='small']) ::slotted([slot='input']) {
    --c-input-height: var(--c-size-control-sm);
    --c-input-spacing-inline: var(--c-spacing-sm);
  }

  :host([center]) ::slotted([slot='input']) {
    text-align: center;
  }
`,ba=window,xa=new WeakMap;function Sa(e){ba.applyFocusVisiblePolyfill&&!xa.has(e)&&(ba.applyFocusVisiblePolyfill(e),xa.set(e,void 0))}var Ca=Er(e=>class extends e{static get properties(){return{focused:{type:Boolean,reflect:!0},focusedVisible:{type:Boolean,reflect:!0,attribute:`focused-visible`},autofocus:{type:Boolean,reflect:!0}}}constructor(){super(),this.focused=!1,this.focusedVisible=!1,this.autofocus=!1}firstUpdated(e){super.firstUpdated(e),this.__registerEventsForFocusMixin(),this.__syncAutofocusToFocusableElement()}disconnectedCallback(){super.disconnectedCallback(),this.__teardownEventsForFocusMixin()}updated(e){super.updated(e),e.has(`autofocus`)&&this.__syncAutofocusToFocusableElement()}__syncAutofocusToFocusableElement(){this._focusableNode&&(this.hasAttribute(`autofocus`)?this._focusableNode.setAttribute(`autofocus`,``):this._focusableNode.removeAttribute(`autofocus`))}focus(){this._focusableNode?.focus()}blur(){this._focusableNode?.blur()}get _focusableNode(){return this._inputNode||document.createElement(`input`)}__onFocus(){if(this.focused=!0,typeof ba.applyFocusVisiblePolyfill==`function`)this.focusedVisible=this._focusableNode.hasAttribute(`data-focus-visible-added`);else try{this.focusedVisible=this._focusableNode.matches(`:focus-visible`)}catch{this.focusedVisible=!1}}__onBlur(){this.focused=!1,this.focusedVisible=!1}__registerEventsForFocusMixin(){Sa(this.getRootNode()),this.__redispatchFocus=e=>{e.stopPropagation(),this.dispatchEvent(new Event(`focus`))},this._focusableNode.addEventListener(`focus`,this.__redispatchFocus),this.__redispatchBlur=e=>{e.stopPropagation(),this.dispatchEvent(new Event(`blur`))},this._focusableNode.addEventListener(`blur`,this.__redispatchBlur),this.__redispatchFocusin=e=>{e.stopPropagation(),this.__onFocus(),this.dispatchEvent(new Event(`focusin`,{bubbles:!0,composed:!0}))},this._focusableNode.addEventListener(`focusin`,this.__redispatchFocusin),this.__redispatchFocusout=e=>{e.stopPropagation(),this.__onBlur(),this.dispatchEvent(new Event(`focusout`,{bubbles:!0,composed:!0}))},this._focusableNode.addEventListener(`focusout`,this.__redispatchFocusout)}__teardownEventsForFocusMixin(){this._focusableNode&&(this._focusableNode?.removeEventListener(`focus`,this.__redispatchFocus),this._focusableNode?.removeEventListener(`blur`,this.__redispatchBlur),this._focusableNode?.removeEventListener(`focusin`,this.__redispatchFocusin),this._focusableNode?.removeEventListener(`focusout`,this.__redispatchFocusout))}});function wa(e,t){return t={exports:{}},e(t,t.exports),t.exports}var Ta=`long`,Ea=`short`,Da=`narrow`,j=`numeric`,Oa=`2-digit`,ka={number:{decimal:{style:`decimal`},integer:{style:`decimal`,maximumFractionDigits:0},currency:{style:`currency`,currency:`USD`},percent:{style:`percent`},default:{style:`decimal`}},date:{short:{month:j,day:j,year:Oa},medium:{month:Ea,day:j,year:j},long:{month:Ta,day:j,year:j},full:{month:Ta,day:j,year:j,weekday:Ta},default:{month:Ea,day:j,year:j}},time:{short:{hour:j,minute:j},medium:{hour:j,minute:j,second:j},long:{hour:j,minute:j,second:j,timeZoneName:Ea},full:{hour:j,minute:j,second:j,timeZoneName:Ea},default:{hour:j,minute:j,second:j}},duration:{default:{hours:{minimumIntegerDigits:1,maximumFractionDigits:0},minutes:{minimumIntegerDigits:2,maximumFractionDigits:0},seconds:{minimumIntegerDigits:2,maximumFractionDigits:3}}},parseNumberPattern:function(e){if(e){var t={},n=e.match(/\b[A-Z]{3}\b/i),r=e.replace(/[^¤]/g,``).length;if(!r&&n&&(r=1),r?(t.style=`currency`,t.currencyDisplay=r===1?`symbol`:r===2?`code`:`name`,t.currency=n?n[0].toUpperCase():`USD`):e.indexOf(`%`)>=0&&(t.style=`percent`),!/[@#0]/.test(e))return t.style?t:void 0;if(t.useGrouping=e.indexOf(`,`)>=0,/E\+?[@#0]+/i.test(e)||e.indexOf(`@`)>=0){var i=e.replace(/E\+?[@#0]+|[^@#0]/gi,``);t.minimumSignificantDigits=Math.min(Math.max(i.replace(/[^@0]/g,``).length,1),21),t.maximumSignificantDigits=Math.min(Math.max(i.length,1),21)}else{for(var a=e.replace(/[^#0.]/g,``).split(`.`),o=a[0],s=o.length-1;o[s]===`0`;)--s;t.minimumIntegerDigits=Math.min(Math.max(o.length-1-s,1),21);var c=a[1]||``;for(s=0;c[s]===`0`;)++s;for(t.minimumFractionDigits=Math.min(Math.max(s,0),20);c[s]===`#`;)++s;t.maximumFractionDigits=Math.min(Math.max(s,0),20)}return t}},parseDatePattern:function(e){if(e){for(var t={},n=0;n<e.length;){for(var r=e[n],i=1;e[++n]===r;)++i;switch(r){case`G`:t.era=i===5?Da:i===4?Ta:Ea;break;case`y`:case`Y`:t.year=i===2?Oa:j;break;case`M`:case`L`:i=Math.min(Math.max(i-1,0),4),t.month=[j,Oa,Ea,Ta,Da][i];break;case`E`:case`e`:case`c`:t.weekday=i===5?Da:i===4?Ta:Ea;break;case`d`:case`D`:t.day=i===2?Oa:j;break;case`h`:case`K`:t.hour12=!0,t.hour=i===2?Oa:j;break;case`H`:case`k`:t.hour12=!1,t.hour=i===2?Oa:j;break;case`m`:t.minute=i===2?Oa:j;break;case`s`:case`S`:t.second=i===2?Oa:j;break;case`z`:case`Z`:case`v`:case`V`:t.timeZoneName=i===1?Ea:Ta;break}}return Object.keys(t).length?t:void 0}}},Aa=function(e,t){if(typeof e==`string`&&t[e])return e;for(var n=[].concat(e||[]),r=0,i=n.length;r<i;++r)for(var a=n[r].split(`-`);a.length;){var o=a.join(`-`);if(t[o])return o;a.pop()}},ja=`zero`,M=`one`,Ma=`two`,N=`few`,Na=`many`,P=`other`,F=[function(e){return+e==1?M:P},function(e){var t=+e;return 0<=t&&t<=1?M:P},function(e){var t=Math.floor(Math.abs(+e)),n=+e;return t===0||n===1?M:P},function(e){var t=+e;return t===0?ja:t===1?M:t===2?Ma:3<=t%100&&t%100<=10?N:11<=t%100&&t%100<=99?Na:P},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length;return t===1&&n===0?M:P},function(e){var t=+e;return t%10==1&&t%100!=11?M:2<=t%10&&t%10<=4&&(t%100<12||14<t%100)?N:t%10==0||5<=t%10&&t%10<=9||11<=t%100&&t%100<=14?Na:P},function(e){var t=+e;return t%10==1&&t%100!=11&&t%100!=71&&t%100!=91?M:t%10==2&&t%100!=12&&t%100!=72&&t%100!=92?Ma:(3<=t%10&&t%10<=4||t%10==9)&&(t%100<10||19<t%100)&&(t%100<70||79<t%100)&&(t%100<90||99<t%100)?N:t!==0&&t%1e6==0?Na:P},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length,r=+(e+`.`).split(`.`)[1];return n===0&&t%10==1&&t%100!=11||r%10==1&&r%100!=11?M:n===0&&2<=t%10&&t%10<=4&&(t%100<12||14<t%100)||2<=r%10&&r%10<=4&&(r%100<12||14<r%100)?N:P},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length;return t===1&&n===0?M:2<=t&&t<=4&&n===0?N:n===0?P:Na},function(e){var t=+e;return t===0?ja:t===1?M:t===2?Ma:t===3?N:t===6?Na:P},function(e){var t=Math.floor(Math.abs(+e)),n=+(``+e).replace(/^[^.]*.?|0+$/g,``);return+e==1||n!==0&&(t===0||t===1)?M:P},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length,r=+(e+`.`).split(`.`)[1];return n===0&&t%100==1||r%100==1?M:n===0&&t%100==2||r%100==2?Ma:n===0&&3<=t%100&&t%100<=4||3<=r%100&&r%100<=4?N:P},function(e){var t=Math.floor(Math.abs(+e));return t===0||t===1?M:P},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length,r=+(e+`.`).split(`.`)[1];return n===0&&(t===1||t===2||t===3)||n===0&&t%10!=4&&t%10!=6&&t%10!=9||n!==0&&r%10!=4&&r%10!=6&&r%10!=9?M:P},function(e){var t=+e;return t===1?M:t===2?Ma:3<=t&&t<=6?N:7<=t&&t<=10?Na:P},function(e){var t=+e;return t===1||t===11?M:t===2||t===12?Ma:3<=t&&t<=10||13<=t&&t<=19?N:P},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length;return n===0&&t%10==1?M:n===0&&t%10==2?Ma:n===0&&(t%100==0||t%100==20||t%100==40||t%100==60||t%100==80)?N:n===0?P:Na},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length,r=+e;return t===1&&n===0?M:t===2&&n===0?Ma:n===0&&(r<0||10<r)&&r%10==0?Na:P},function(e){var t=Math.floor(Math.abs(+e)),n=+(``+e).replace(/^[^.]*.?|0+$/g,``);return n===0&&t%10==1&&t%100!=11||n!==0?M:P},function(e){var t=+e;return t===1?M:t===2?Ma:P},function(e){var t=+e;return t===0?ja:t===1?M:P},function(e){var t=Math.floor(Math.abs(+e)),n=+e;return n===0?ja:(t===0||t===1)&&n!==0?M:P},function(e){var t=+(e+`.`).split(`.`)[1],n=+e;return n%10==1&&(n%100<11||19<n%100)?M:2<=n%10&&n%10<=9&&(n%100<11||19<n%100)?N:t===0?P:Na},function(e){var t=(e+`.`).split(`.`)[1].length,n=+(e+`.`).split(`.`)[1],r=+e;return r%10==0||11<=r%100&&r%100<=19||t===2&&11<=n%100&&n%100<=19?ja:r%10==1&&r%100!=11||t===2&&n%10==1&&n%100!=11||t!==2&&n%10==1?M:P},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length,r=+(e+`.`).split(`.`)[1];return n===0&&t%10==1&&t%100!=11||r%10==1&&r%100!=11?M:P},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length,r=+e;return t===1&&n===0?M:n!==0||r===0||r!==1&&1<=r%100&&r%100<=19?N:P},function(e){var t=+e;return t===1?M:t===0||2<=t%100&&t%100<=10?N:11<=t%100&&t%100<=19?Na:P},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length;return t===1&&n===0?M:n===0&&2<=t%10&&t%10<=4&&(t%100<12||14<t%100)?N:n===0&&t!==1&&0<=t%10&&t%10<=1||n===0&&5<=t%10&&t%10<=9||n===0&&12<=t%100&&t%100<=14?Na:P},function(e){var t=Math.floor(Math.abs(+e));return 0<=t&&t<=1?M:P},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length;return n===0&&t%10==1&&t%100!=11?M:n===0&&2<=t%10&&t%10<=4&&(t%100<12||14<t%100)?N:n===0&&t%10==0||n===0&&5<=t%10&&t%10<=9||n===0&&11<=t%100&&t%100<=14?Na:P},function(e){var t=Math.floor(Math.abs(+e)),n=+e;return t===0||n===1?M:2<=n&&n<=10?N:P},function(e){var t=Math.floor(Math.abs(+e)),n=+(e+`.`).split(`.`)[1],r=+e;return r===0||r===1||t===0&&n===1?M:P},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length;return n===0&&t%100==1?M:n===0&&t%100==2?Ma:n===0&&3<=t%100&&t%100<=4||n!==0?N:P},function(e){var t=+e;return 0<=t&&t<=1||11<=t&&t<=99?M:P},function(e){var t=+e;return t===1||t===5||t===7||t===8||t===9||t===10?M:t===2||t===3?Ma:t===4?N:t===6?Na:P},function(e){var t=Math.floor(Math.abs(+e));return t%10==1||t%10==2||t%10==5||t%10==7||t%10==8||t%100==20||t%100==50||t%100==70||t%100==80?M:t%10==3||t%10==4||t%1e3==100||t%1e3==200||t%1e3==300||t%1e3==400||t%1e3==500||t%1e3==600||t%1e3==700||t%1e3==800||t%1e3==900?N:t===0||t%10==6||t%100==40||t%100==60||t%100==90?Na:P},function(e){var t=+e;return(t%10==2||t%10==3)&&t%100!=12&&t%100!=13?N:P},function(e){var t=+e;return t===1||t===3?M:t===2?Ma:t===4?N:P},function(e){var t=+e;return t===0||t===7||t===8||t===9?ja:t===1?M:t===2?Ma:t===3||t===4?N:t===5||t===6?Na:P},function(e){var t=+e;return t%10==1&&t%100!=11?M:t%10==2&&t%100!=12?Ma:t%10==3&&t%100!=13?N:P},function(e){var t=+e;return t===1||t===11?M:t===2||t===12?Ma:t===3||t===13?N:P},function(e){var t=+e;return t===1?M:t===2||t===3?Ma:t===4?N:t===6?Na:P},function(e){var t=+e;return t===1||t===5?M:P},function(e){var t=+e;return t===11||t===8||t===80||t===800?Na:P},function(e){var t=Math.floor(Math.abs(+e));return t===1?M:t===0||2<=t%100&&t%100<=20||t%100==40||t%100==60||t%100==80?Na:P},function(e){var t=+e;return t%10==6||t%10==9||t%10==0&&t!==0?Na:P},function(e){var t=Math.floor(Math.abs(+e));return t%10==1&&t%100!=11?M:t%10==2&&t%100!=12?Ma:(t%10==7||t%10==8)&&t%100!=17&&t%100!=18?Na:P},function(e){var t=+e;return t===1?M:t===2||t===3?Ma:t===4?N:P},function(e){var t=+e;return 1<=t&&t<=4?M:P},function(e){var t=+e;return t===1||t===5||7<=t&&t<=9?M:t===2||t===3?Ma:t===4?N:t===6?Na:P},function(e){var t=+e;return t===1?M:t%10==4&&t%100!=14?Na:P},function(e){var t=+e;return(t%10==1||t%10==2)&&t%100!=11&&t%100!=12?M:P},function(e){var t=+e;return t%10==6||t%10==9||t===10?N:P},function(e){var t=+e;return t%10==3&&t%100!=13?N:P}],Pa={af:{cardinal:F[0]},ak:{cardinal:F[1]},am:{cardinal:F[2]},ar:{cardinal:F[3]},ars:{cardinal:F[3]},as:{cardinal:F[2],ordinal:F[34]},asa:{cardinal:F[0]},ast:{cardinal:F[4]},az:{cardinal:F[0],ordinal:F[35]},be:{cardinal:F[5],ordinal:F[36]},bem:{cardinal:F[0]},bez:{cardinal:F[0]},bg:{cardinal:F[0]},bh:{cardinal:F[1]},bn:{cardinal:F[2],ordinal:F[34]},br:{cardinal:F[6]},brx:{cardinal:F[0]},bs:{cardinal:F[7]},ca:{cardinal:F[4],ordinal:F[37]},ce:{cardinal:F[0]},cgg:{cardinal:F[0]},chr:{cardinal:F[0]},ckb:{cardinal:F[0]},cs:{cardinal:F[8]},cy:{cardinal:F[9],ordinal:F[38]},da:{cardinal:F[10]},de:{cardinal:F[4]},dsb:{cardinal:F[11]},dv:{cardinal:F[0]},ee:{cardinal:F[0]},el:{cardinal:F[0]},en:{cardinal:F[4],ordinal:F[39]},eo:{cardinal:F[0]},es:{cardinal:F[0]},et:{cardinal:F[4]},eu:{cardinal:F[0]},fa:{cardinal:F[2]},ff:{cardinal:F[12]},fi:{cardinal:F[4]},fil:{cardinal:F[13],ordinal:F[0]},fo:{cardinal:F[0]},fr:{cardinal:F[12],ordinal:F[0]},fur:{cardinal:F[0]},fy:{cardinal:F[4]},ga:{cardinal:F[14],ordinal:F[0]},gd:{cardinal:F[15],ordinal:F[40]},gl:{cardinal:F[4]},gsw:{cardinal:F[0]},gu:{cardinal:F[2],ordinal:F[41]},guw:{cardinal:F[1]},gv:{cardinal:F[16]},ha:{cardinal:F[0]},haw:{cardinal:F[0]},he:{cardinal:F[17]},hi:{cardinal:F[2],ordinal:F[41]},hr:{cardinal:F[7]},hsb:{cardinal:F[11]},hu:{cardinal:F[0],ordinal:F[42]},hy:{cardinal:F[12],ordinal:F[0]},ia:{cardinal:F[4]},io:{cardinal:F[4]},is:{cardinal:F[18]},it:{cardinal:F[4],ordinal:F[43]},iu:{cardinal:F[19]},iw:{cardinal:F[17]},jgo:{cardinal:F[0]},ji:{cardinal:F[4]},jmc:{cardinal:F[0]},ka:{cardinal:F[0],ordinal:F[44]},kab:{cardinal:F[12]},kaj:{cardinal:F[0]},kcg:{cardinal:F[0]},kk:{cardinal:F[0],ordinal:F[45]},kkj:{cardinal:F[0]},kl:{cardinal:F[0]},kn:{cardinal:F[2]},ks:{cardinal:F[0]},ksb:{cardinal:F[0]},ksh:{cardinal:F[20]},ku:{cardinal:F[0]},kw:{cardinal:F[19]},ky:{cardinal:F[0]},lag:{cardinal:F[21]},lb:{cardinal:F[0]},lg:{cardinal:F[0]},ln:{cardinal:F[1]},lt:{cardinal:F[22]},lv:{cardinal:F[23]},mas:{cardinal:F[0]},mg:{cardinal:F[1]},mgo:{cardinal:F[0]},mk:{cardinal:F[24],ordinal:F[46]},ml:{cardinal:F[0]},mn:{cardinal:F[0]},mo:{cardinal:F[25],ordinal:F[0]},mr:{cardinal:F[2],ordinal:F[47]},mt:{cardinal:F[26]},nah:{cardinal:F[0]},naq:{cardinal:F[19]},nb:{cardinal:F[0]},nd:{cardinal:F[0]},ne:{cardinal:F[0],ordinal:F[48]},nl:{cardinal:F[4]},nn:{cardinal:F[0]},nnh:{cardinal:F[0]},no:{cardinal:F[0]},nr:{cardinal:F[0]},nso:{cardinal:F[1]},ny:{cardinal:F[0]},nyn:{cardinal:F[0]},om:{cardinal:F[0]},or:{cardinal:F[0],ordinal:F[49]},os:{cardinal:F[0]},pa:{cardinal:F[1]},pap:{cardinal:F[0]},pl:{cardinal:F[27]},prg:{cardinal:F[23]},ps:{cardinal:F[0]},pt:{cardinal:F[28]},"pt-PT":{cardinal:F[4]},rm:{cardinal:F[0]},ro:{cardinal:F[25],ordinal:F[0]},rof:{cardinal:F[0]},ru:{cardinal:F[29]},rwk:{cardinal:F[0]},saq:{cardinal:F[0]},sc:{cardinal:F[4],ordinal:F[43]},scn:{cardinal:F[4],ordinal:F[43]},sd:{cardinal:F[0]},sdh:{cardinal:F[0]},se:{cardinal:F[19]},seh:{cardinal:F[0]},sh:{cardinal:F[7]},shi:{cardinal:F[30]},si:{cardinal:F[31]},sk:{cardinal:F[8]},sl:{cardinal:F[32]},sma:{cardinal:F[19]},smi:{cardinal:F[19]},smj:{cardinal:F[19]},smn:{cardinal:F[19]},sms:{cardinal:F[19]},sn:{cardinal:F[0]},so:{cardinal:F[0]},sq:{cardinal:F[0],ordinal:F[50]},sr:{cardinal:F[7]},ss:{cardinal:F[0]},ssy:{cardinal:F[0]},st:{cardinal:F[0]},sv:{cardinal:F[4],ordinal:F[51]},sw:{cardinal:F[4]},syr:{cardinal:F[0]},ta:{cardinal:F[0]},te:{cardinal:F[0]},teo:{cardinal:F[0]},ti:{cardinal:F[1]},tig:{cardinal:F[0]},tk:{cardinal:F[0],ordinal:F[52]},tl:{cardinal:F[13],ordinal:F[0]},tn:{cardinal:F[0]},tr:{cardinal:F[0]},ts:{cardinal:F[0]},tzm:{cardinal:F[33]},ug:{cardinal:F[0]},uk:{cardinal:F[29],ordinal:F[53]},ur:{cardinal:F[4]},uz:{cardinal:F[0]},ve:{cardinal:F[0]},vo:{cardinal:F[0]},vun:{cardinal:F[0]},wa:{cardinal:F[1]},wae:{cardinal:F[0]},xh:{cardinal:F[0]},xog:{cardinal:F[0]},yi:{cardinal:F[4]},zu:{cardinal:F[2]},lo:{ordinal:F[0]},ms:{ordinal:F[0]},vi:{ordinal:F[0]}},Fa=wa(function(e,t){t=e.exports=function(e,t,r){return n(e,null,t||`en`,r||{},!0)},t.toParts=function(e,t,r){return n(e,null,t||`en`,r||{},!1)};function n(e,t,n,i,a){var o=e.map(function(e){return r(e,t,n,i,a)});return a?o.length===1?o[0]:function(e){for(var t=``,n=0;n<o.length;++n)t+=o[n](e);return t}:function(e){return o.reduce(function(t,n){return t.concat(n(e))},[])}}function r(e,t,r,a,o){if(typeof e==`string`){var s=e;return function(){return s}}var c=e[0],l=e[1];if(t&&e[0]===`#`){c=t[0];var u=t[2],f=(a.number||d.number)([c,`number`],r);return function(e){return f(i(c,e)-u,e)}}var p;l===`plural`||l===`selectordinal`?(p={},Object.keys(e[3]).forEach(function(t){p[t]=n(e[3][t],e,r,a,o)}),e=[e[0],e[1],e[2],p]):e[2]&&typeof e[2]==`object`&&(p={},Object.keys(e[2]).forEach(function(t){p[t]=n(e[2][t],e,r,a,o)}),e=[e[0],e[1],p]);var m=l&&(a[l]||d[l]);if(m){var h=m(e,r);return function(e){return h(i(c,e),e)}}return o?function(e){return String(i(c,e))}:function(e){return i(c,e)}}function i(e,t){if(t&&e in t)return t[e];for(var n=e.split(`.`),r=t,i=0,a=n.length;r&&i<a;++i)r=r[n[i]];return r}function a(e,t){var n=e[2],r=ka.number[n]||ka.parseNumberPattern(n)||ka.number.default;return new Intl.NumberFormat(t,r).format}function o(e,t){var n=e[2],r=ka.duration[n]||ka.duration.default,i=new Intl.NumberFormat(t,r.seconds).format,a=new Intl.NumberFormat(t,r.minutes).format,o=new Intl.NumberFormat(t,r.hours).format,s=/^fi$|^fi-|^da/.test(String(t))?`.`:`:`;return function(e,t){if(e=+e,!isFinite(e))return i(e);var n=~~(e/60/60),r=~~(e/60%60),c=(n?o(Math.abs(n))+s:``)+a(Math.abs(r))+s+i(Math.abs(e%60));return e<0?o(-1).replace(o(1),c):c}}function s(e,t){var n=e[1],r=e[2],i=ka[n][r]||ka.parseDatePattern(r)||ka[n].default;return new Intl.DateTimeFormat(t,i).format}function c(e,t){var n=e[1]===`selectordinal`?`ordinal`:`cardinal`,r=e[2],i=e[3],a;if(Intl.PluralRules&&Intl.PluralRules.supportedLocalesOf(t).length>0)a=new Intl.PluralRules(t,{type:n});else{var o=Aa(t,Pa);a={select:o&&Pa[o][n]||l}}return function(e,t){return(i[`=`+ +e]||i[a.select(e-r)]||i.other)(t)}}function l(){return`other`}function u(e,t){var n=e[2];return function(e,t){return(n[e]||n.other)(t)}}var d={number:a,ordinal:a,spellout:a,duration:o,date:s,time:s,plural:c,selectordinal:c,select:u};t.types=d});Fa.toParts,Fa.types;var Ia=wa(function(e,t){var n=`{`,r=`}`,i=`,`,a=`#`,o=`<`,s=`>`,c=`</`,l=`/>`,u=`'`,d=`offset:`,f=[`number`,`date`,`time`,`ordinal`,`duration`,`spellout`],p=[`plural`,`select`,`selectordinal`];t=e.exports=function(e,t){return m({pattern:String(e),index:0,tagsType:t&&t.tagsType||null,tokens:t&&t.tokens||null},``)};function m(e,t){var n=e.pattern,i=n.length,a=[],o=e.index,s=h(e,t);for(s&&a.push(s),s&&e.tokens&&e.tokens.push([`text`,n.slice(o,e.index)]);e.index<i;){if(n[e.index]===r){if(!t)throw E(e);break}if(t&&e.tagsType&&n.slice(e.index,e.index+c.length)===c)break;a.push(v(e)),o=e.index,s=h(e,t),s&&a.push(s),s&&e.tokens&&e.tokens.push([`text`,n.slice(o,e.index)])}return a}function h(e,t){for(var i=e.pattern,s=i.length,c=t===`plural`||t===`selectordinal`,l=!!e.tagsType,d=t===`{style}`,f=``;e.index<s;){var p=i[e.index];if(p===n||p===r||c&&p===a||l&&p===o||d&&g(p.charCodeAt(0)))break;if(p===u)if(p=i[++e.index],p===u)f+=p,++e.index;else if(p===n||p===r||c&&p===a||l&&p===o||d)for(f+=p;++e.index<s;)if(p=i[e.index],p===u&&i[e.index+1]===u)f+=u,++e.index;else if(p===u){++e.index;break}else f+=p;else f+=u;else f+=p,++e.index}return f}function g(e){return e>=9&&e<=13||e===32||e===133||e===160||e===6158||e>=8192&&e<=8205||e===8232||e===8233||e===8239||e===8287||e===8288||e===12288||e===65279}function _(e){for(var t=e.pattern,n=t.length,r=e.index;e.index<n&&g(t.charCodeAt(e.index));)++e.index;r<e.index&&e.tokens&&e.tokens.push([`space`,e.pattern.slice(r,e.index)])}function v(e){var t=e.pattern;if(t[e.index]===a)return e.tokens&&e.tokens.push([`syntax`,a]),++e.index,[a];var o=y(e);if(o)return o;if(t[e.index]!==n)throw E(e,n);e.tokens&&e.tokens.push([`syntax`,n]),++e.index,_(e);var s=b(e);if(!s)throw E(e,`placeholder id`);e.tokens&&e.tokens.push([`id`,s]),_(e);var c=t[e.index];if(c===r)return e.tokens&&e.tokens.push([`syntax`,r]),++e.index,[s];if(c!==i)throw E(e,i+` or `+r);e.tokens&&e.tokens.push([`syntax`,i]),++e.index,_(e);var l=b(e);if(!l)throw E(e,`placeholder type`);if(e.tokens&&e.tokens.push([`type`,l]),_(e),c=t[e.index],c===r){if(e.tokens&&e.tokens.push([`syntax`,r]),l===`plural`||l===`selectordinal`||l===`select`)throw E(e,l+` sub-messages`);return++e.index,[s,l]}if(c!==i)throw E(e,i+` or `+r);e.tokens&&e.tokens.push([`syntax`,i]),++e.index,_(e);var u;if(l===`plural`||l===`selectordinal`){var d=S(e);_(e),u=[s,l,d,w(e,l)]}else if(l===`select`)u=[s,l,w(e,l)];else if(f.indexOf(l)>=0)u=[s,l,x(e)];else{var p=e.index,m=x(e);_(e),t[e.index]===n&&(e.index=p,m=w(e,l)),u=[s,l,m]}if(_(e),t[e.index]!==r)throw E(e,r);return e.tokens&&e.tokens.push([`syntax`,r]),++e.index,u}function y(e){var t=e.tagsType;if(!(!t||e.pattern[e.index]!==o)){if(e.pattern.slice(e.index,e.index+c.length)===c)throw E(e,null,`closing tag without matching opening tag`);e.tokens&&e.tokens.push([`syntax`,o]),++e.index;var n=b(e,!0);if(!n)throw E(e,`placeholder id`);if(e.tokens&&e.tokens.push([`id`,n]),_(e),e.pattern.slice(e.index,e.index+l.length)===l)return e.tokens&&e.tokens.push([`syntax`,l]),e.index+=l.length,[n,t];if(e.pattern[e.index]!==s)throw E(e,s);e.tokens&&e.tokens.push([`syntax`,s]),++e.index;var r=m(e,t),i=e.index;if(e.pattern.slice(e.index,e.index+c.length)!==c)throw E(e,c+n+s);e.tokens&&e.tokens.push([`syntax`,c]),e.index+=c.length;var a=b(e,!0);if(a&&e.tokens&&e.tokens.push([`id`,a]),n!==a)throw e.index=i,E(e,c+n+s,c+a+s);if(_(e),e.pattern[e.index]!==s)throw E(e,s);return e.tokens&&e.tokens.push([`syntax`,s]),++e.index,[n,t,{children:r}]}}function b(e,t){for(var c=e.pattern,l=c.length,d=``;e.index<l;){var f=c[e.index];if(f===n||f===r||f===i||f===a||f===u||g(f.charCodeAt(0))||t&&(f===o||f===s||f===`/`))break;d+=f,++e.index}return d}function x(e){var t=e.index,n=h(e,`{style}`);if(!n)throw E(e,`placeholder style name`);return e.tokens&&e.tokens.push([`style`,e.pattern.slice(t,e.index)]),n}function S(e){var t=e.pattern,n=t.length,r=0;if(t.slice(e.index,e.index+d.length)===d){e.tokens&&e.tokens.push([`offset`,`offset`],[`syntax`,`:`]),e.index+=d.length,_(e);for(var i=e.index;e.index<n&&C(t.charCodeAt(e.index));)++e.index;if(i===e.index)throw E(e,`offset number`);e.tokens&&e.tokens.push([`number`,t.slice(i,e.index)]),r=+t.slice(i,e.index)}return r}function C(e){return e>=48&&e<=57}function w(e,t){for(var n=e.pattern,i=n.length,a={};e.index<i&&n[e.index]!==r;){var o=b(e);if(!o)throw E(e,`sub-message selector`);e.tokens&&e.tokens.push([`selector`,o]),_(e),a[o]=T(e,t),_(e)}if(!a.other&&p.indexOf(t)>=0)throw E(e,null,null,`"other" sub-message must be specified in `+t);return a}function T(e,t){if(e.pattern[e.index]!==n)throw E(e,n+` to start sub-message`);e.tokens&&e.tokens.push([`syntax`,n]),++e.index;var i=m(e,t);if(e.pattern[e.index]!==r)throw E(e,r+` to end sub-message`);return e.tokens&&e.tokens.push([`syntax`,r]),++e.index,i}function E(e,t,n,r){var i=e.pattern,a=i.slice(0,e.index).split(/\r?\n/),o=e.index,s=a.length,c=a.slice(-1)[0].length;return n||=e.index>=i.length?`end of message pattern`:b(e)||i[e.index],r||=D(t,n),r+=` in `+i.replace(/\r?\n/g,`
`),new ee(r,t,n,o,s,c)}function D(e,t){return e?`Expected `+e+` but found `+t:`Unexpected `+t+` found`}function ee(e,t,n,r,i,a){Error.call(this,e),this.name=`SyntaxError`,this.message=e,this.expected=t,this.found=n,this.offset=r,this.line=i,this.column=a}ee.prototype=Object.create(Error.prototype),t.SyntaxError=ee});Ia.SyntaxError;var La=RegExp(`^(`+Object.keys(Pa).join(`|`)+`)\\b`),Ra=new WeakMap;function za(e,t,n){if(!(this instanceof za)||Ra.has(this))throw TypeError(`calling MessageFormat constructor without new is invalid`);var r=Ia(e);Ra.set(this,{ast:r,format:Fa(r,t,n&&n.types),locale:za.supportedLocalesOf(t)[0]||`en`,locales:t,options:n})}var Ba=za;Object.defineProperties(za.prototype,{format:{configurable:!0,get:function(){var e=Ra.get(this);if(!e)throw TypeError(`MessageFormat.prototype.format called on value that's not an object initialized as a MessageFormat`);return e.format}},formatToParts:{configurable:!0,writable:!0,value:function(e){var t=Ra.get(this);if(!t)throw TypeError(`MessageFormat.prototype.formatToParts called on value that's not an object initialized as a MessageFormat`);return(t.toParts||=Fa.toParts(t.ast,t.locales,t.options&&t.options.types))(e)}},resolvedOptions:{configurable:!0,writable:!0,value:function(){var e=Ra.get(this);if(!e)throw TypeError(`MessageFormat.prototype.resolvedOptions called on value that's not an object initialized as a MessageFormat`);return{locale:e.locale}}}}),typeof Symbol<`u`&&Object.defineProperty(za.prototype,Symbol.toStringTag,{value:`Object`}),Object.defineProperties(za,{supportedLocalesOf:{configurable:!0,writable:!0,value:function(e){return[].concat(Intl.NumberFormat.supportedLocalesOf(e),Intl.DateTimeFormat.supportedLocalesOf(e),Intl.PluralRules?Intl.PluralRules.supportedLocalesOf(e):[],[].concat(e||[]).filter(function(e){return La.test(e)})).filter(function(e,t,n){return n.indexOf(e)===t})}}});function Va(e){return!!(e&&e.default&&typeof e.default==`object`&&Object.keys(e).length===1)}var Ha=globalThis.document?.documentElement,Ua=class extends EventTarget{formatNumberOptions={returnIfNaN:``,postProcessors:new Map};formatDateOptions={postProcessors:new Map};#e=!1;#t=``;#n=null;__storage={};__namespacePatternsMap=new Map;__namespaceLoadersCache={};__namespaceLoaderPromisesCache={};get locale(){return this.#e?this.#t||``:Ha.lang||``}set locale(e){if(this.#r(e),!this.#e){let t=Ha.lang;this._setHtmlLangAttribute(e),this._onLocaleChanged(e,t);return}let t=this.#t;this.#t=e,this.#n===null&&this._setHtmlLangAttribute(e),this._onLocaleChanged(e,t)}get loadingComplete(){return typeof this.__namespaceLoaderPromisesCache[this.locale]==`object`?Promise.all(Object.values(this.__namespaceLoaderPromisesCache[this.locale])):Promise.resolve()}constructor({allowOverridesForExistingNamespaces:e=!1,autoLoadOnLocaleChange:t=!1,showKeyAsFallback:n=!1,fallbackLocale:r=``}={}){super(),this.__allowOverridesForExistingNamespaces=e,this._autoLoadOnLocaleChange=!!t,this._showKeyAsFallback=n,this._fallbackLocale=r;let i=Ha.getAttribute(`data-localize-lang`);this.#e=!!i,this.#e&&(this.locale=i,this._setupTranslationToolSupport()),Ha.lang||=this.locale||`en-GB`,this._setupHtmlLangAttributeObserver()}addData(e,t,n){if(!this.__allowOverridesForExistingNamespaces&&this._isNamespaceInCache(e,t))throw Error(`Namespace "${t}" has been already added for the locale "${e}".`);this.__storage[e]=this.__storage[e]||{},this.__allowOverridesForExistingNamespaces?this.__storage[e][t]={...this.__storage[e][t],...n}:this.__storage[e][t]=n}setupNamespaceLoader(e,t){this.__namespacePatternsMap.set(e,t)}loadNamespaces(e,{locale:t}={}){return Promise.all(e.map(e=>this.loadNamespace(e,{locale:t})))}loadNamespace(e,{locale:t=this.locale}={locale:this.locale}){let n=typeof e==`object`,r=n?Object.keys(e)[0]:e;return this._isNamespaceInCache(t,r)?Promise.resolve():this._getCachedNamespaceLoaderPromise(t,r)||this._loadNamespaceData(t,e,n,r)}msg(e,t,n={}){let r=n.locale?n.locale:this.locale,i=this._getMessageForKeys(e,r);return i?new Ba(i,r).format(t):``}teardown(){this._teardownHtmlLangAttributeObserver()}reset(){this.__storage={},this.__namespacePatternsMap=new Map,this.__namespaceLoadersCache={},this.__namespaceLoaderPromisesCache={}}setDatePostProcessorForLocale({locale:e,postProcessor:t}){this.formatDateOptions?.postProcessors.set(e,t)}setNumberPostProcessorForLocale({locale:e,postProcessor:t}){this.formatNumberOptions?.postProcessors.set(e,t)}_setupTranslationToolSupport(){this.#n=Ha.lang||null}_setHtmlLangAttribute(e){this._teardownHtmlLangAttributeObserver(),Ha.lang=e,this._setupHtmlLangAttributeObserver()}_setupHtmlLangAttributeObserver(){this._htmlLangAttributeObserver||=new MutationObserver(e=>{e.forEach(e=>{this.#e?Ha.lang===`auto`?(this.#n=null,this._setHtmlLangAttribute(this.locale)):this.#n=document.documentElement.lang:this._onLocaleChanged(document.documentElement.lang,e.oldValue||``)})}),this._htmlLangAttributeObserver.observe(document.documentElement,{attributes:!0,attributeFilter:[`lang`],attributeOldValue:!0})}_teardownHtmlLangAttributeObserver(){this._htmlLangAttributeObserver&&this._htmlLangAttributeObserver.disconnect()}_isNamespaceInCache(e,t){return!!(this.__storage[e]&&this.__storage[e][t])}_getCachedNamespaceLoaderPromise(e,t){return this.__namespaceLoaderPromisesCache[e]?this.__namespaceLoaderPromisesCache[e][t]:null}_loadNamespaceData(e,t,n,r){let i=this._getNamespaceLoader(t,n,r),a=this._getNamespaceLoaderPromise(i,e,r);return this._cacheNamespaceLoaderPromise(e,r,a),a.then(t=>{if(this.__namespaceLoaderPromisesCache[e]&&this.__namespaceLoaderPromisesCache[e][r]===a){let n=Va(t)?t.default:t;this.addData(e,r,n)}})}_getNamespaceLoader(e,t,n){let r=this.__namespaceLoadersCache[n];if(r||(t?(r=e[n],this.__namespaceLoadersCache[n]=r):(r=this._lookupNamespaceLoader(n),this.__namespaceLoadersCache[n]=r)),!r)throw Error(`Namespace "${n}" was not properly setup.`);return this.__namespaceLoadersCache[n]=r,r}_getNamespaceLoaderPromise(e,t,n,r=this._fallbackLocale){return e(t,n).catch(()=>{let i=this._getLangFromLocale(t);return e(i,n).catch(()=>{if(r)return this._getNamespaceLoaderPromise(e,r,n,``).catch(()=>{let e=this._getLangFromLocale(r);throw Error(`Data for namespace "${n}" and current locale "${t}" or fallback locale "${r}" could not be loaded. Make sure you have data either for locale "${t}" (and/or generic language "${i}") or for fallback "${r}" (and/or "${e}").`)});throw Error(`Data for namespace "${n}" and locale "${t}" could not be loaded. Make sure you have data for locale "${t}" (and/or generic language "${i}").`)})})}_cacheNamespaceLoaderPromise(e,t,n){this.__namespaceLoaderPromisesCache[e]||(this.__namespaceLoaderPromisesCache[e]={}),this.__namespaceLoaderPromisesCache[e][t]=n}_lookupNamespaceLoader(e){for(let[t,n]of this.__namespacePatternsMap){let r=typeof t==`string`&&t===e,i=typeof t==`object`&&t.constructor.name===`RegExp`&&t.test(e);if(r||i)return n}return null}_getLangFromLocale(e){return e.substring(0,2)}_onLocaleChanged(e,t){this.dispatchEvent(new CustomEvent(`__localeChanging`)),e!==t&&(this._autoLoadOnLocaleChange?(this._loadAllMissing(e,t),this.loadingComplete.then(()=>{this.dispatchEvent(new CustomEvent(`localeChanged`,{detail:{newLocale:e,oldLocale:t}}))})):this.dispatchEvent(new CustomEvent(`localeChanged`,{detail:{newLocale:e,oldLocale:t}})))}_loadAllMissing(e,t){let n=this.__storage[t]||{},r=this.__storage[e]||{};Object.keys(n).forEach(t=>{r[t]||this.loadNamespace(t,{locale:e})})}_getMessageForKeys(e,t){if(typeof e==`string`)return this._getMessageForKey(e,t);let n=Array.from(e).reverse(),r,i;for(;n.length;)if(r=n.pop(),i=this._getMessageForKey(r,t),i)return i}_getMessageForKey(e,t){if(!e||e.indexOf(`:`)===-1)throw Error(`Namespace is missing in the key "${e}". The format for keys is "namespace:name".`);let[n,r]=e.split(`:`),i=this.__storage[t],a=i?i[n]:{},o=r.split(`.`).reduce((e,t)=>typeof e==`object`?e[t]:e,a);return String(o||(this._showKeyAsFallback?e:``))}#r(e){if(!e.includes(`-`))throw Error(`
      Locale was set to ${e}.
      Language only locales are not allowed, please use the full language locale e.g. 'en-GB' instead of 'en'.
      See https://github.com/ing-bank/lion/issues/187 for more information.
    `)}get _supportExternalTranslationTools(){return this.#e}set _supportExternalTranslationTools(e){this.#e=e}get _langAttrSetByTranslationTool(){return this.#t}set _langAttrSetByTranslationTool(e){this.#t=e}},Wa=Symbol.for(`lion::SingletonManagerClassStorage`),Ga=globalThis||window,Ka=new class{constructor(){this._map=Ga[Wa]?Ga[Wa]:Ga[Wa]=new Map}set(e,t){this.has(e)||this._map.set(e,t)}get(e){return this._map.get(e)}has(e){return this._map.has(e)}};function qa(){if(Ka.has(`@lion/ui::localize::0.x`))return Ka.get(`@lion/ui::localize::0.x`);let e=new Ua({autoLoadOnLocaleChange:!0,fallbackLocale:`en-GB`});return Ka.set(`@lion/ui::localize::0.x`,e),e}var Ja=(e,t)=>{let n=e._$AN;if(n===void 0)return!1;for(let e of n)e._$AO?.(t,!1),Ja(e,t);return!0},Ya=e=>{let t,n;do{if((t=e._$AM)===void 0)break;n=t._$AN,n.delete(e),e=t}while(n?.size===0)},Xa=e=>{for(let t;t=e._$AM;e=t){let n=t._$AN;if(n===void 0)t._$AN=n=new Set;else if(n.has(e))break;n.add(e),$a(t)}};function Za(e){this._$AN===void 0?this._$AM=e:(Ya(this),this._$AM=e,Xa(this))}function Qa(e,t=!1,n=0){let r=this._$AH,i=this._$AN;if(i!==void 0&&i.size!==0)if(t)if(Array.isArray(r))for(let e=n;e<r.length;e++)Ja(r[e],!1),Ya(r[e]);else r!=null&&(Ja(r,!1),Ya(r));else Ja(this,e)}var $a=e=>{e.type==ee.CHILD&&(e._$AP??=Qa,e._$AQ??=Za)},eo=class extends D{constructor(){super(...arguments),this._$AN=void 0}_$AT(e,t,n){super._$AT(e,t,n),Xa(this),this.isConnected=e._$AU}_$AO(e,t=!0){e!==this.isConnected&&(this.isConnected=e,e?this.reconnected?.():this.disconnected?.()),t&&(Ja(this,e),Ya(this))}setValue(e){if(Mr(this._$Ct))this._$Ct._$AI(e,this);else{let t=[...this._$Ct._$AH];t[this._$Ci]=e,this._$Ct._$AI(t,this,0)}}disconnected(){}reconnected(){}},to=class{constructor(e){this.G=e}disconnect(){this.G=void 0}reconnect(e){this.G=e}deref(){return this.G}},no=class{constructor(){this.Y=void 0,this.Z=void 0}get(){return this.Y}pause(){this.Y??=new Promise((e=>this.Z=e))}resume(){this.Z?.(),this.Y=this.Z=void 0}},ro=e=>!Ar(e)&&typeof e.then==`function`,io=1073741823,ao=w(class extends eo{constructor(){super(...arguments),this._$Cwt=io,this._$Cbt=[],this._$CK=new to(this),this._$CX=new no}render(...e){return e.find((e=>!ro(e)))??m}update(e,t){let n=this._$Cbt,r=n.length;this._$Cbt=t;let i=this._$CK,a=this._$CX;this.isConnected||this.disconnected();for(let e=0;e<t.length&&!(e>this._$Cwt);e++){let o=t[e];if(!ro(o))return this._$Cwt=e,o;e<r&&o===n[e]||(this._$Cwt=io,r=0,Promise.resolve(o).then((async e=>{for(;a.get();)await a.get();let t=i.deref();if(t!==void 0){let n=t._$Cbt.indexOf(o);n>-1&&n<t._$Cwt&&(t._$Cwt=n,t.setValue(e))}})))}return m}disconnected(){this._$CK.disconnect(),this._$CX.pause()}reconnected(){this._$CK.reconnect(this),this._$CX.resume()}}),oo=Er(e=>class extends e{static get localizeNamespaces(){return[]}static get waitForLocalizeNamespaces(){return!0}constructor(){super(),this._localizeManager=qa(),this.__boundLocalizeOnLocaleChanged=(...e)=>{let t=Array.from(e)[0];this.__localizeOnLocaleChanged(t)},this.__boundLocalizeOnLocaleChanging=()=>{this.__localizeOnLocaleChanging()},this.__localizeStartLoadingNamespaces(),this.localizeNamespacesLoaded&&this.localizeNamespacesLoaded.then(()=>{this.__localizeMessageSync=!0})}async scheduleUpdate(){Object.getPrototypeOf(this).constructor.waitForLocalizeNamespaces&&await this.localizeNamespacesLoaded,super.scheduleUpdate()}connectedCallback(){super.connectedCallback(),this.localizeNamespacesLoaded&&this.localizeNamespacesLoaded.then(()=>this.onLocaleReady()),this._localizeManager.addEventListener(`__localeChanging`,this.__boundLocalizeOnLocaleChanging),this._localizeManager.addEventListener(`localeChanged`,this.__boundLocalizeOnLocaleChanged)}disconnectedCallback(){super.disconnectedCallback(),this._localizeManager.removeEventListener(`__localeChanging`,this.__boundLocalizeOnLocaleChanging),this._localizeManager.removeEventListener(`localeChanged`,this.__boundLocalizeOnLocaleChanged)}msgLit(e,t,n){return this.__localizeMessageSync?this._localizeManager.msg(e,t,n):this.localizeNamespacesLoaded?ao(this.localizeNamespacesLoaded.then(()=>this._localizeManager.msg(e,t,n)),_):``}__getUniqueNamespaces(){let e=[],t=new Set;return Object.getPrototypeOf(this).constructor.localizeNamespaces.forEach(t.add.bind(t)),t.forEach(t=>{e.push(t)}),e}__localizeStartLoadingNamespaces(){this.localizeNamespacesLoaded=this._localizeManager.loadNamespaces(this.__getUniqueNamespaces())}__localizeOnLocaleChanging(){this.__localizeStartLoadingNamespaces()}__localizeOnLocaleChanged(e){this.onLocaleChanged(e.detail.newLocale,e.detail.oldLocale)}onLocaleReady(){this.onLocaleUpdated()}onLocaleChanged(e,t){this.onLocaleUpdated(),this.requestUpdate()}onLocaleUpdated(){}}),so=`3.0.0`,co=window.scopedElementsVersions||(window.scopedElementsVersions=[]);co.includes(so)||co.push(so);var lo=Er(e=>class extends e{static scopedElements;static get scopedElementsVersion(){return so}static __registry;get registry(){return this.constructor.__registry}set registry(e){this.constructor.__registry=e}attachShadow(e){let{scopedElements:t}=this.constructor;if(!this.registry||this.registry===this.constructor.__registry&&!Object.prototype.hasOwnProperty.call(this.constructor,`__registry`)){this.registry=new CustomElementRegistry;for(let[e,n]of Object.entries(t??{}))this.registry.define(e,n)}return super.attachShadow({...e,customElements:this.registry,registry:this.registry})}}),uo=Er(e=>class extends lo(e){createRenderRoot(){let{shadowRootOptions:e,elementStyles:t}=this.constructor,n=this.attachShadow(e);return this.renderOptions.creationScope=n,f(n,t),this.renderOptions.renderBefore??=n.firstChild,n}});function fo(){return!!(globalThis.ShadowRoot?.prototype.createElement&&globalThis.ShadowRoot?.prototype.importNode)}var po=Er(e=>class extends uo(e){constructor(){super()}createScopedElement(e){return(fo()?this.shadowRoot:document).createElement(e)}defineScopedElement(e,t){let n=this.registry.get(e),r=n&&n!==t;return!fo()&&r&&console.error([`You are trying to re-register the "${e}" custom element with a different class via ScopedElementsMixin.`,`This is only possible with a CustomElementRegistry.`,`Your browser does not support this feature so you will need to load a polyfill for it.`,`Load "@webcomponents/scoped-custom-element-registry" before you register ANY web component to the global customElements registry.`,`e.g. add "<script src="/node_modules/@webcomponents/scoped-custom-element-registry/scoped-custom-element-registry.min.js"><\/script>" as your first script tag.`,`For more details you can visit https://open-wc.org/docs/development/scoped-elements/`].join(`
`)),n?this.registry.get(e):this.registry.define(e,t)}attachShadow(e){let{scopedElements:t}=this.constructor;if(!this.registry||this.registry===this.constructor.__registry&&!Object.prototype.hasOwnProperty.call(this.constructor,`__registry`)){this.registry=fo()?new CustomElementRegistry:customElements;for(let[e,n]of Object.entries(t??{}))this.defineScopedElement(e,n)}return Element.prototype.attachShadow.call(this,{...e,customElements:this.registry,registry:this.registry})}createRenderRoot(){let{shadowRootOptions:e,elementStyles:t}=this.constructor,n=this.attachShadow(e);return fo()&&(this.renderOptions.creationScope=n),n instanceof ShadowRoot&&(f(n,t),this.renderOptions.renderBefore=this.renderOptions.renderBefore||n.firstChild),n}}),mo=class{constructor(){this.__running=!1,this.__queue=[]}add(e){this.__queue.push(e),this.__running||(this.complete=new Promise(e=>{this.__callComplete=e}),this.__run())}async __run(){this.__running=!0,await this.__queue[0](),this.__queue.shift(),this.__queue.length>0?this.__run():(this.__running=!1,this.__callComplete&&this.__callComplete())}};function ho(e){return e.charAt(0).toUpperCase()+e.slice(1)}var go=Er(e=>class extends e{constructor(){super(),this.__SyncUpdatableNamespace={}}firstUpdated(e){super.firstUpdated(e),this.__syncUpdatableInitialize()}connectedCallback(){super.connectedCallback(),this.__SyncUpdatableNamespace.connected=!0}disconnectedCallback(){super.disconnectedCallback(),this.__SyncUpdatableNamespace.connected=!1}static enabledWarnings=super.enabledWarnings?.filter(e=>e!==`change-in-update`)||[];static __syncUpdatableHasChanged(e,t,n){let r=this.elementProperties;return r.get(e)&&r.get(e).hasChanged?r.get(e).hasChanged(t,n):t!==n}__syncUpdatableInitialize(){let e=this.__SyncUpdatableNamespace,t=this.constructor;e.initialized=!0,e.queue&&Array.from(e.queue).forEach(e=>{t.__syncUpdatableHasChanged(e,this[e],void 0)&&this.updateSync(e,void 0)})}requestUpdate(e,t,n){if(super.requestUpdate(e,t,n),e===void 0)return;this.__SyncUpdatableNamespace=this.__SyncUpdatableNamespace||{};let r=this.__SyncUpdatableNamespace,i=this.constructor;r.initialized?i.__syncUpdatableHasChanged(e,this[e],t)&&this.updateSync(e,t):(r.queue=r.queue||new Set,r.queue.add(e))}updateSync(e,t){}}),_o=e=>{switch(e){case`bg-BG`:return O(()=>import(`./bg-BG.js`),__vite__mapDeps([0,1]),import.meta.url);case`bg`:return O(()=>import(`./bg2.js`),[],import.meta.url);case`cs-CZ`:return O(()=>import(`./cs-CZ.js`),__vite__mapDeps([2,3]),import.meta.url);case`cs`:return O(()=>import(`./cs2.js`),[],import.meta.url);case`de-DE`:return O(()=>import(`./de-DE.js`),__vite__mapDeps([4,5]),import.meta.url);case`de`:return O(()=>import(`./de2.js`),[],import.meta.url);case`en-AU`:return O(()=>import(`./en-AU.js`),__vite__mapDeps([6,7]),import.meta.url);case`en-GB`:return O(()=>import(`./en-GB.js`),__vite__mapDeps([8,7]),import.meta.url);case`en-US`:return O(()=>import(`./en-US.js`),__vite__mapDeps([9,7]),import.meta.url);case`en-PH`:case`en`:return O(()=>import(`./en2.js`),[],import.meta.url);case`es-ES`:return O(()=>import(`./es-ES.js`),__vite__mapDeps([10,11]),import.meta.url);case`es`:return O(()=>import(`./es2.js`),[],import.meta.url);case`fr-FR`:return O(()=>import(`./fr-FR.js`),__vite__mapDeps([12,13]),import.meta.url);case`fr-BE`:return O(()=>import(`./fr-BE.js`),__vite__mapDeps([14,13]),import.meta.url);case`fr`:return O(()=>import(`./fr2.js`),[],import.meta.url);case`hu-HU`:return O(()=>import(`./hu-HU.js`),__vite__mapDeps([15,16]),import.meta.url);case`hu`:return O(()=>import(`./hu2.js`),[],import.meta.url);case`it-IT`:return O(()=>import(`./it-IT.js`),__vite__mapDeps([17,18]),import.meta.url);case`it`:return O(()=>import(`./it2.js`),[],import.meta.url);case`nl-BE`:return O(()=>import(`./nl-BE.js`),__vite__mapDeps([19,20]),import.meta.url);case`nl-NL`:return O(()=>import(`./nl-NL.js`),__vite__mapDeps([21,20]),import.meta.url);case`nl`:return O(()=>import(`./nl2.js`),[],import.meta.url);case`pl-PL`:return O(()=>import(`./pl-PL.js`),__vite__mapDeps([22,23]),import.meta.url);case`pl`:return O(()=>import(`./pl2.js`),[],import.meta.url);case`ro-RO`:return O(()=>import(`./ro-RO.js`),__vite__mapDeps([24,25]),import.meta.url);case`ro`:return O(()=>import(`./ro2.js`),[],import.meta.url);case`ru-RU`:return O(()=>import(`./ru-RU.js`),__vite__mapDeps([26,27]),import.meta.url);case`ru`:return O(()=>import(`./ru2.js`),[],import.meta.url);case`sk-SK`:return O(()=>import(`./sk-SK.js`),__vite__mapDeps([28,29]),import.meta.url);case`sk`:return O(()=>import(`./sk2.js`),[],import.meta.url);case`tr-TR`:return O(()=>import(`./tr-TR.js`),__vite__mapDeps([30,31]),import.meta.url);case`tr`:return O(()=>import(`./tr.js`),[],import.meta.url);case`uk-UA`:return O(()=>import(`./uk-UA.js`),__vite__mapDeps([32,33]),import.meta.url);case`uk`:return O(()=>import(`./uk2.js`),[],import.meta.url);case`zh-CN`:case`zh`:return O(()=>import(`./zh2.js`),[],import.meta.url);default:return O(()=>import(`./en2.js`),[],import.meta.url)}},vo=e=>`${e[0].toUpperCase()}${e.slice(1)}`,yo=class extends oo(v){static get properties(){return{feedbackData:{attribute:!1}}}static localizeNamespaces=[{"lion-form-core":_o},...super.localizeNamespaces];static get styles(){return[p`
        .validation-feedback__type {
          position: absolute;
          width: 1px;
          height: 1px;
          overflow: hidden;
          clip-path: inset(100%);
          clip: rect(1px, 1px, 1px, 1px);
          white-space: nowrap;
          border: 0;
          margin: 0;
          padding: 0;
        }
      `]}constructor(){super(),this.feedbackData=void 0}_messageTemplate({message:e}){return e}updated(e){super.updated(e),this.feedbackData&&this.feedbackData[0]?(this.setAttribute(`type`,this.feedbackData[0].type),this.currentType=this.feedbackData[0].type):this.currentType!==`success`&&this.removeAttribute(`type`)}render(){return d`
      ${this.feedbackData&&this.feedbackData.map(({message:e,type:t,validator:n})=>d`
          <div class="validation-feedback__type">
            ${e&&t?this._localizeManager.msg(`lion-form-core:validation${vo(t)}`):_}
          </div>
          ${this._messageTemplate({message:e,type:t,validator:n})}
        `)}
    `}},bo=class{constructor(e){this.type=`unparseable`,this.viewValue=e}toString(){return JSON.stringify({type:this.type,viewValue:this.viewValue})}},xo=[Node.DOCUMENT_POSITION_PRECEDING,Node.DOCUMENT_POSITION_CONTAINS,Node.DOCUMENT_POSITION_CONTAINS|Node.DOCUMENT_POSITION_PRECEDING];function So(e,{reverse:t}={}){let n=(e,t)=>{let n=e.compareDocumentPosition(t);return xo.includes(n)?1:-1},r=e.filter(e=>e);return r.sort(n),t&&r.reverse(),r}var Co=Er(e=>class extends e{constructor(){super(),this.name=``,this._parentFormGroup=void 0,this.allowCrossRootRegistration=!1}get name(){return this.__name||``}set name(e){let t=this.name;this.__name=e.toString(),this.requestUpdate(`name`,t)}static get properties(){return{name:{type:String,reflect:!0},allowCrossRootRegistration:{type:Boolean,attribute:`allow-cross-root-registration`}}}connectedCallback(){super.connectedCallback(),this.dispatchEvent(new CustomEvent(`form-element-register`,{detail:{element:this},bubbles:!0,composed:!!this.allowCrossRootRegistration}))}disconnectedCallback(){super.disconnectedCallback(),this.__unregisterFormElement()}__unregisterFormElement(){this._parentFormGroup&&this._parentFormGroup.removeFormElement(this)}}),wo=Er(e=>class extends Co(Dr(Vr(e))){static get properties(){return{readOnly:{type:Boolean,attribute:`readonly`,reflect:!0},label:String,labelSrOnly:{type:Boolean,attribute:`label-sr-only`,reflect:!0},helpText:{type:String,attribute:`help-text`},modelValue:{attribute:!1},_ariaLabelledNodes:{attribute:!1},_ariaDescribedNodes:{attribute:!1},_repropagationRole:{attribute:!1},_isRepropagationEndpoint:{attribute:!1}}}get label(){return this.__label??(this._labelNode?.textContent||``)}set label(e){let t=this.label;this.__label=e,this.requestUpdate(`label`,t)}get helpText(){return this.__helpText??(this._helpTextNode?.textContent||``)}set helpText(e){let t=this.helpText;this.__helpText=e,this.requestUpdate(`helpText`,t)}get fieldName(){return this.__fieldName||this.label||this.name||``}set fieldName(e){this.__fieldName=e}get slots(){return{...super.slots,label:()=>{let e=document.createElement(`label`);return e.textContent=this.label,e},"help-text":()=>{let e=document.createElement(`div`);return e.textContent=this.helpText,e}}}get _inputNode(){return this.__getDirectSlotChild(`input`)}get _labelNode(){return this.__getDirectSlotChild(`label`)}get _helpTextNode(){return this.__getDirectSlotChild(`help-text`)}get _feedbackNode(){return this.__getDirectSlotChild(`feedback`)}static enabledWarnings=super.enabledWarnings?.filter(e=>e!==`change-in-update`)||[];constructor(){super(),this.readOnly=!1,this.labelSrOnly=!1,this._inputId=Wr(this.localName),this._ariaLabelledNodes=[],this._ariaDescribedNodes=[],this._repropagationRole=`child`,this._isRepropagationEndpoint=!1,this.addEventListener(`model-value-changed`,this.__repropagateChildrenValues),this._onLabelClick=this._onLabelClick.bind(this)}connectedCallback(){super.connectedCallback(),this._enhanceLightDomClasses(),this._enhanceLightDomA11y(),this._triggerInitialModelValueChangedEvent(),this._labelNode&&this._labelNode.addEventListener(`click`,this._onLabelClick)}disconnectedCallback(){super.disconnectedCallback(),this._labelNode&&this._labelNode.removeEventListener(`click`,this._onLabelClick)}updated(e){super.updated(e),e.has(`disabled`)&&this._inputNode?.setAttribute(`aria-disabled`,`${!!this.disabled}`),e.has(`_ariaLabelledNodes`)&&this.__reflectAriaAttr(`aria-labelledby`,this._ariaLabelledNodes,this.__reorderAriaLabelledNodes),e.has(`_ariaDescribedNodes`)&&this.__reflectAriaAttr(`aria-describedby`,this._ariaDescribedNodes,this.__reorderAriaDescribedNodes),e.has(`label`)&&this.__label!==void 0&&this._labelNode&&(this._labelNode.textContent=this.label),e.has(`helpText`)&&this.__helpText!==void 0&&this._helpTextNode&&(this._helpTextNode.textContent=this.helpText),e.has(`name`)&&this.dispatchEvent(new CustomEvent(`form-element-name-changed`,{detail:{oldName:e.get(`name`),newName:this.name},bubbles:!0}))}_triggerInitialModelValueChangedEvent(){this._dispatchInitialModelValueChangedEvent()}_enhanceLightDomClasses(){this._inputNode&&this._inputNode.classList.add(`form-control`)}_enhanceLightDomA11y(){let{_inputNode:e,_labelNode:t,_helpTextNode:n,_feedbackNode:r}=this;e&&(e.id=e.id||this._inputId),t&&(t.setAttribute(`for`,this._inputId),this.addToAriaLabelledBy(t,{idPrefix:`label`})),n&&this.addToAriaDescribedBy(n,{idPrefix:`help-text`}),r&&(this.addEventListener(`focusin`,()=>{r.setAttribute(`aria-live`,`polite`)}),this.addEventListener(`focusout`,()=>{r.setAttribute(`aria-live`,`assertive`)}),this.addToAriaDescribedBy(r,{idPrefix:`feedback`})),this._enhanceLightDomA11yForAdditionalSlots()}_enhanceLightDomA11yForAdditionalSlots(e=[`prefix`,`suffix`,`before`,`after`]){e.forEach(e=>{let t=this.__getDirectSlotChild(e);t&&(t.hasAttribute(`data-label`)&&this.addToAriaLabelledBy(t,{idPrefix:e}),t.hasAttribute(`data-description`)&&this.addToAriaDescribedBy(t,{idPrefix:e}))})}__reflectAriaAttr(e,t,n){if(this._inputNode){if(n){let e=t.filter(e=>this.contains(e)),n=t.filter(e=>!this.contains(e)),r=[...So(e.map(e=>e.assignedSlot||e))],i=[];r.forEach(t=>{e.forEach(e=>{t.name===e.slot&&i.push(e)})}),t=[...i,...n]}let r=t.map(e=>e.id).join(` `);this._inputNode.setAttribute(e,r)}}render(){return d`
        <div class="form-field__group-one">${this._groupOneTemplate()}</div>
        <div class="form-field__group-two">${this._groupTwoTemplate()}</div>
      `}_groupOneTemplate(){return d` ${this._labelTemplate()} ${this._helpTextTemplate()} `}_groupTwoTemplate(){return d` ${this._inputGroupTemplate()} ${this._feedbackTemplate()} `}_labelTemplate(){return d`
        <div class="form-field__label">
          <slot name="label"></slot>
        </div>
      `}_helpTextTemplate(){return d`
        <small class="form-field__help-text">
          <slot name="help-text"></slot>
        </small>
      `}_inputGroupTemplate(){return d`
        <div class="input-group">
          ${this._inputGroupBeforeTemplate()}
          <div class="input-group__container">
            ${this._inputGroupPrefixTemplate()} ${this._inputGroupInputTemplate()}
            ${this._inputGroupSuffixTemplate()}
          </div>
          ${this._inputGroupAfterTemplate()}
        </div>
      `}_inputGroupBeforeTemplate(){return d`
        <div class="input-group__before">
          <slot name="before"></slot>
        </div>
      `}_inputGroupPrefixTemplate(){return Array.from(this.children).find(e=>e.slot===`prefix`)?d`
            <div class="input-group__prefix">
              <slot name="prefix"></slot>
            </div>
          `:_}_inputGroupInputTemplate(){return d`
        <div class="input-group__input">
          <slot name="input"></slot>
        </div>
      `}_inputGroupSuffixTemplate(){return Array.from(this.children).find(e=>e.slot===`suffix`)?d`
            <div class="input-group__suffix">
              <slot name="suffix"></slot>
            </div>
          `:_}_inputGroupAfterTemplate(){return d`
        <div class="input-group__after">
          <slot name="after"></slot>
        </div>
      `}_feedbackTemplate(){return d`
        <div class="form-field__feedback">
          <slot name="feedback"></slot>
        </div>
      `}_isEmpty(e=this.modelValue){let t=e;return this.modelValue instanceof bo&&(t=this.modelValue.viewValue),typeof t==`object`&&t&&!(t instanceof Date)?!Object.keys(t).length:!t&&!(typeof t==`number`&&(t===0||Number.isNaN(t)))&&!(typeof t==`boolean`&&t===!1)}static get styles(){return[p`
          /**********************
            {block} .form-field
           ********************/

          :host {
            display: block;
          }

          :host([hidden]) {
            display: none;
          }

          :host([disabled]) {
            pointer-events: none;
          }

          :host([disabled]) .form-field__label ::slotted(*),
          :host([disabled]) .form-field__help-text ::slotted(*) {
            color: var(--disabled-text-color, #767676);
          }

          :host([label-sr-only]) .form-field__label {
            position: absolute;
            top: 0;
            width: 1px;
            height: 1px;
            overflow: hidden;
            clip-path: inset(100%);
            clip: rect(1px, 1px, 1px, 1px);
            white-space: nowrap;
            border: 0;
            margin: 0;
            padding: 0;
          }

          /***********************
            {block} .input-group
           *********************/

          .input-group__container {
            display: flex;
          }

          .input-group__input {
            flex: 1;
            display: flex;
          }

          /***** {state} :disabled *****/
          :host([disabled]) .input-group ::slotted([slot='input']) {
            color: var(--disabled-text-color, #767676);
          }

          /***********************
            {block} .form-control
           **********************/

          .input-group__container > .input-group__input ::slotted(.form-control) {
            flex: 1 1 auto;
            margin: 0; /* remove input margin in Safari */
            font-size: 100%; /* normalize default input font-size */
          }
        `]}_getAriaDescriptionElements(){return[this._helpTextNode,this._feedbackNode]}addToAriaLabelledBy(e,{idPrefix:t=``,reorder:n=!0}={}){e.id=e.id||`${t}-${this._inputId}`,this._ariaLabelledNodes.includes(e)||(this._ariaLabelledNodes=[...this._ariaLabelledNodes,e],this.__reorderAriaLabelledNodes=!!n)}removeFromAriaLabelledBy(e){this._ariaLabelledNodes.includes(e)&&(this._ariaLabelledNodes.splice(this._ariaLabelledNodes.indexOf(e),1),this._ariaLabelledNodes=[...this._ariaLabelledNodes],this.__reorderAriaLabelledNodes=!1)}addToAriaDescribedBy(e,{idPrefix:t=``,reorder:n=!0}={}){e.id=e.id||`${t}-${this._inputId}`,this._ariaDescribedNodes.includes(e)||(this._ariaDescribedNodes=[...this._ariaDescribedNodes,e],this.__reorderAriaDescribedNodes=!!n)}removeFromAriaDescribedBy(e){this._ariaDescribedNodes.includes(e)&&(this._ariaDescribedNodes.splice(this._ariaDescribedNodes.indexOf(e),1),this._ariaDescribedNodes=[...this._ariaDescribedNodes],this.__reorderAriaLabelledNodes=!1)}__getDirectSlotChild(e){return Array.from(this.children).find(t=>t.slot===e)}_dispatchInitialModelValueChangedEvent(){this._repropagationRole!==`child`&&(this.__repropagateChildrenInitialized=!0,this.dispatchEvent(new CustomEvent(`model-value-changed`,{bubbles:!0,detail:{formPath:[this],initialize:!0,isTriggeredByUser:!1}})))}_onBeforeRepropagateChildrenValues(e){}__repropagateChildrenValues(e){this._onBeforeRepropagateChildrenValues(e);let t=e.detail&&e.detail.element||e.target,n=this._isRepropagationEndpoint||this._repropagationRole===`choice-group`;if(t===this)return;e.stopImmediatePropagation();let r=this._repropagationRole!==`child`&&!this.__repropagateChildrenInitialized,i=e.detail&&e.detail.initialize;if(r||i||!this._repropagationCondition(t))return;let a=[];n||(a=e.detail&&e.detail.formPath||[t]);let o=[...a,this];this.dispatchEvent(new CustomEvent(`model-value-changed`,{bubbles:!0,detail:{formPath:o,isTriggeredByUser:!!e.detail?.isTriggeredByUser}}))}_repropagationCondition(e){return!!e}_onLabelClick(){}}),To=class extends EventTarget{constructor(e,t){super(),this.__param=e,this.__config=t||{},this.type=t?.type||`error`}static _$isValidator$=!0;static validatorName=``;static async=!1;execute(e,t,n){if(!this.constructor.validatorName)throw Error(`A validator needs to have a name! Please set it via "static get validatorName() { return 'IsCat'; }"`);return!0}set param(e){this.__param=e,this.dispatchEvent(new Event(`param-changed`))}get param(){return this.__param}set config(e){this.__config=e,this.dispatchEvent(new Event(`config-changed`))}get config(){return this.__config}async _getMessage(e){let t=this.constructor,n={name:t.validatorName,type:this.type,params:this.param,config:this.config,...e};if(this.config.getMessage){if(typeof this.config.getMessage==`function`)return this.config.getMessage(n);throw Error(`You must provide a value for getMessage of type 'function', you provided a value of type: ${typeof this.config.getMessage}`)}return t.getMessage(n)}static async getMessage(e){return`Please configure an error message for "${this.name}" by overriding "static async getMessage()"`}onFormControlConnect(e){}onFormControlDisconnect(e){}abortExecution(){}};function Eo(e=[],t=[]){return e.filter(e=>!t.includes(e)).concat(t.filter(t=>!e.includes(t)))}function Do(e){return e instanceof bo?e.viewValue:e}var Oo=Er(e=>class extends wo(go(Dr(Vr(po(e))))){static get scopedElements(){return{...super.scopedElements,"lion-validation-feedback":yo}}static get properties(){return{validators:{attribute:!1},hasFeedbackFor:{attribute:!1},shouldShowFeedbackFor:{attribute:!1},showsFeedbackFor:{type:Array,attribute:`shows-feedback-for`,reflect:!0,converter:{fromAttribute:e=>e.split(`,`),toAttribute:e=>e.join(`,`)}},validationStates:{attribute:!1},isPending:{type:Boolean,attribute:`is-pending`,reflect:!0},defaultValidators:{attribute:!1},_visibleMessagesAmount:{attribute:!1},__childModelValueChanged:{attribute:!1}}}static get validationTypes(){return[`error`]}get operationMode(){return`enter`}get slots(){return{...super.slots,feedback:()=>{let e=this.createScopedElement(`lion-validation-feedback`);return e.setAttribute(`data-tag-name`,`lion-validation-feedback`),e}}}get _allValidators(){return[...this.validators,...this.defaultValidators]}constructor(){super(),this.hasFeedbackFor=[],this.showsFeedbackFor=[],this.shouldShowFeedbackFor=[],this.validationStates={},this.isPending=!1,this.validators=[],this.defaultValidators=[],this._visibleMessagesAmount=1,this.__syncValidationResult=[],this.__asyncValidationResult=[],this.__validationResult=[],this.__prevValidationResult=[],this.__prevShownValidationResult=[],this.__childModelValueChanged=!1,this._onValidatorUpdated=this._onValidatorUpdated.bind(this),this._updateFeedbackComponent=this._updateFeedbackComponent.bind(this)}connectedCallback(){super.connectedCallback(),qa().addEventListener(`localeChanged`,this._updateFeedbackComponent)}disconnectedCallback(){super.disconnectedCallback(),qa().removeEventListener(`localeChanged`,this._updateFeedbackComponent)}firstUpdated(e){super.firstUpdated(e),this.__isValidateInitialized=!0,this.validate(),this._repropagationRole!==`child`&&this.addEventListener(`model-value-changed`,()=>{this.__childModelValueChanged=!0})}updateSync(e,t){if(super.updateSync(e,t),e===`validators`?(this.__setupValidators(),this.validate({clearCurrentResult:!0})):e===`modelValue`&&this.validate({clearCurrentResult:!0}),[`touched`,`dirty`,`prefilled`,`focused`,`submitted`,`hasFeedbackFor`,`filled`].includes(e)&&this._updateShouldShowFeedbackFor(),e===`showsFeedbackFor`){this._inputNode&&this._inputNode.setAttribute(`aria-invalid`,`${this._hasFeedbackVisibleFor(`error`)}`);let e=Eo(this.showsFeedbackFor,t);e.length>0&&this.dispatchEvent(new Event(`showsFeedbackForChanged`,{bubbles:!0})),e.forEach(e=>{this.dispatchEvent(new Event(`showsFeedbackFor${ho(e)}Changed`,{bubbles:!0}))})}e===`shouldShowFeedbackFor`&&Eo(this.shouldShowFeedbackFor,t).length>0&&this.dispatchEvent(new Event(`shouldShowFeedbackForChanged`,{bubbles:!0}))}async validate({clearCurrentResult:e=!1}={}){if(this.validateComplete=new Promise(e=>{this.__validateCompleteResolve=e}),this.disabled){this.__clearValidationResults(),this.__finishValidationPass(),this._updateFeedbackComponent();return}this.__isValidateInitialized&&(this.__prevValidationResult=this.__validationResult,e&&this.__clearValidationResults(),await this.__executeValidators())}#e(e){let t=e;for(;t;){if(t.constructor.validatorName===`Required`)return!0;t=Object.getPrototypeOf(t)}return!1}async __executeValidators(){let e=Do(this.modelValue),t=this.__isEmpty(e);if(this.__syncValidationResult=[],t){let e=!this._isFormOrFieldset,t=this._allValidators.find(e=>e.constructor?.validatorName===`Required`);if(t&&(this.__syncValidationResult=[{validator:t,outcome:!0}]),e){this.__finishValidationPass({syncValidationResult:this.__syncValidationResult});return}}let n=[],r=[],i=[];for(let e of this._allValidators)e?.executeOnResults?n.push(e):this.#e(e)||(e.constructor.async?i.push(e):r.push(e));let a=!!i.length;this.__syncValidationResult=[...this.__syncValidationResult,...this.__executeSyncValidators(r,e)],this.__finishValidationPass({syncValidationResult:this.__syncValidationResult,metaValidators:n}),a?(this.isPending=!0,this.__asyncValidationResult=await this.__executeAsyncValidators(i,e),this.isPending=!1,this.__finishValidationPass({syncValidationResult:this.__syncValidationResult,asyncValidationResult:this.__asyncValidationResult,metaValidators:n}),this.__validateCompleteResolve?.(!0)):this.__validateCompleteResolve?.(!0)}__executeSyncValidators(e,t){return e.map(e=>({validator:e,outcome:e.execute(t,e.param,{node:this})})).filter(e=>!!e.outcome)}async __executeAsyncValidators(e,t){let n=e.map(e=>e.execute(t,e.param,{node:this})),r=await Promise.all(n);return r.map((t,n)=>({validator:e[n],outcome:r[n]})).filter(e=>!!e.outcome)}__executeMetaValidators(e,t){return t.length?this._isEmpty(this.modelValue)?(this.__prevShownValidationResult=[],[]):t.map(t=>({validator:t,outcome:t.executeOnResults({regularValidationResult:e.map(e=>e.validator),prevValidationResult:this.__prevValidationResult.map(e=>e.validator),prevShownValidationResult:this.__prevShownValidationResult.map(e=>e.validator)})})).filter(e=>!!e.outcome):[]}__finishValidationPass({syncValidationResult:e=[],asyncValidationResult:t=[],metaValidators:n=[]}={}){let r=[...e,...t];this.__validationResult=[...this.__executeMetaValidators(r,n),...r];let i=this.constructor.validationTypes.reduce((e,t)=>({...e,[t]:{}}),{});for(let{validator:e,outcome:t}of this.__validationResult){i[e.type]||(i[e.type]={});let n=e.constructor;i[e.type][n.validatorName]=t}this.validationStates=i,this.hasFeedbackFor=[...new Set(this.__validationResult.map(({validator:e})=>e.type))],this.dispatchEvent(new Event(`validate-performed`,{bubbles:!0}))}__clearValidationResults(){this.__syncValidationResult=[],this.__asyncValidationResult=[]}_onValidatorUpdated(e){(e.type===`param-changed`||e.type===`config-changed`)&&this.validate()}__setupValidators(){let e=[`param-changed`,`config-changed`];for(let t of this.__prevValidators||[]){for(let n of e)t.removeEventListener?.(n,this._onValidatorUpdated);t.onFormControlDisconnect(this)}for(let t of this._allValidators){if(t.constructor._$isValidator$===void 0){let e=`Validators array only accepts class instances of Validator. Type "${Array.isArray(t)?`array`:typeof t}" found. This may be caused by having multiple installations of "@lion/ui/form-core.js".`;throw console.error(e,this),Error(e)}let n=this.constructor,r=t.constructor;if(n.validationTypes.indexOf(t.type)===-1){let e=`This component does not support the validator type "${t.type}" used in "${r.validatorName}". You may change your validators type or add it to the components "static get validationTypes() {}".`;throw console.error(e,this),Error(e)}for(let n of e)t.addEventListener?.(n,e=>{this._onValidatorUpdated(e,{validator:t})});t.onFormControlConnect(this)}this.__prevValidators=this._allValidators}__isEmpty(e){return typeof this._isEmpty==`function`?this._isEmpty(e):this.modelValue===null||this.modelValue===void 0||this.modelValue===``}async __getFeedbackMessages(e){let t=await this.fieldName;return Promise.all(e.map(async({validator:e,outcome:n})=>(e.config.fieldName&&(t=await e.config.fieldName),{message:await e._getMessage({modelValue:this.modelValue,formControl:this,fieldName:t,outcome:n}),type:e.type,validator:e,visibilityDuration:e.config?.visibilityDuration||3e3})))}_updateFeedbackComponent(){window.clearTimeout(this.removeMessage);let{_feedbackNode:e}=this;e&&(this.__feedbackQueue||=new mo,this.showsFeedbackFor.length>0?this.__feedbackQueue.add(async()=>{this.__prioritizedResult=this._prioritizeAndFilterFeedback({validationResult:this.__validationResult.map(e=>e.validator)}).map(e=>this.__validationResult.find(t=>e===t.validator)).filter(Boolean),this.__prioritizedResult.length>0&&(this.__prevShownValidationResult=this.__prioritizedResult);let t=await this.__getFeedbackMessages(this.__prioritizedResult);e.feedbackData=t||[],t?.[0]&&t[0].type===`success`&&t[0].visibilityDuration!==1/0&&(this.removeMessage=window.setTimeout(()=>{e.removeAttribute(`type`),e.feedbackData=[]},t[0].visibilityDuration))}):this.__feedbackQueue.add(async()=>{e.feedbackData=[]}),this.feedbackComplete=this.__feedbackQueue.complete)}_showFeedbackConditionFor(e,t){return!0}get _feedbackConditionMeta(){return{modelValue:this.modelValue,el:this}}feedbackCondition(e,t=this._feedbackConditionMeta,n=this._showFeedbackConditionFor.bind(this)){return n(e,t)}_hasFeedbackVisibleFor(e){return this.hasFeedbackFor?.includes(e)&&this.shouldShowFeedbackFor?.includes(e)}updated(e){if(super.updated(e),(e.has(`shouldShowFeedbackFor`)||e.has(`hasFeedbackFor`))&&(this.showsFeedbackFor=this.constructor.validationTypes.map(e=>this._hasFeedbackVisibleFor(e)?e:void 0).filter(Boolean),this._updateFeedbackComponent()),e.has(`__childModelValueChanged`)&&this.__childModelValueChanged&&(this.validate({clearCurrentResult:!0}),this.__childModelValueChanged=!1),e.has(`validationStates`)){let t=e.get(`validationStates`);t&&Object.entries(this.validationStates).forEach(([e,n])=>{t[e]&&JSON.stringify(n)!==JSON.stringify(t[e])&&this.dispatchEvent(new CustomEvent(`${e}StateChanged`,{detail:n}))})}}_updateShouldShowFeedbackFor(){let e=this.constructor.validationTypes.map(e=>this.feedbackCondition(e,this._feedbackConditionMeta,this._showFeedbackConditionFor.bind(this))?e:void 0).filter(Boolean);JSON.stringify(this.shouldShowFeedbackFor)!==JSON.stringify(e)&&(this.shouldShowFeedbackFor=e)}_prioritizeAndFilterFeedback({validationResult:e}){let t=this.constructor.validationTypes;return e.filter(e=>this.feedbackCondition(e.type,this._feedbackConditionMeta,this._showFeedbackConditionFor.bind(this))).sort((e,n)=>t.indexOf(e.type)-t.indexOf(n.type)).slice(0,this._visibleMessagesAmount)}}),ko=Er(e=>class extends Oo(wo(e)){static get properties(){return{formattedValue:{attribute:!1},serializedValue:{attribute:!1},formatOptions:{attribute:!1}}}#e={didFormatterOutputSyncToView:!1,didFormatterRun:!1};requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`modelValue`&&this.modelValue!==t&&this._onModelValueChanged({modelValue:this.modelValue},{modelValue:t}),e===`serializedValue`&&this.serializedValue!==t&&this._calculateValues({source:`serialized`}),e===`formattedValue`&&this.formattedValue!==t&&this._calculateValues({source:`formatted`})}get value(){return this._inputNode?.value||this.__value||``}set value(e){this._inputNode?(this._inputNode.value=e,this.__value=void 0):this.__value=e}preprocessor(e,t){}parser(e,t){return e}formatter(e,t){return e}serializer(e){return e===void 0?``:e}deserializer(e){return e===void 0?``:e}_calculateValues({source:e}={source:null}){this.__preventRecursiveTrigger||(this.__preventRecursiveTrigger=!0,e!==`model`&&(e===`serialized`?this.modelValue=this.deserializer(this.serializedValue):e===`formatted`&&(this.modelValue=this._callParser())),e!==`formatted`&&(this.formattedValue=this._callFormatter()),e!==`serialized`&&(this.serializedValue=this.serializer(this.modelValue)),this._reflectBackFormattedValueToUser(),this.__preventRecursiveTrigger=!1,this.__prevViewValue=this.value)}_callParser(e=this.formattedValue){if(e===``)return``;if(typeof e!=`string`)return;let t=this.parser(e,{...this.formatOptions,mode:this.#t(),viewValueStates:this.#n()});return t===void 0?new bo(e):t}_callFormatter(){return this.#e.didFormatterRun=!1,this._isHandlingUserInput&&this.hasFeedbackFor?.includes(`error`)&&this._inputNode?this.value:this.modelValue instanceof bo?this.modelValue.viewValue:(this.#e.didFormatterRun=!0,this.formatter(this.modelValue,{...this.formatOptions,mode:this.#t(),viewValueStates:this.#n()}))}_onModelValueChanged(...e){this._calculateValues({source:`model`}),this._dispatchModelValueChangedEvent(...e)}_dispatchModelValueChangedEvent(...e){this.dispatchEvent(new CustomEvent(`model-value-changed`,{bubbles:!0,detail:{formPath:[this],isTriggeredByUser:!!this._isHandlingUserInput}}))}_syncValueUpwards(){this.__isHandlingComposition||this.__handlePreprocessor();let e=this.formattedValue;this.modelValue=this._callParser(this.value),e===this.formattedValue&&this.__prevViewValue!==this.value&&this._calculateValues()}__handlePreprocessor(){let e=this.value.length;this._inputNode&&`selectionStart`in this._inputNode&&this._inputNode?.type!==`range`&&(e=this._inputNode.selectionStart);let t=this.preprocessor(this.value,{...this.formatOptions,currentCaretIndex:e,prevViewValue:this.__prevViewValue});if(t!==void 0){if(typeof t==`string`)this.value=t;else if(typeof t==`object`){let{viewValue:e,caretIndex:n}=t;this.value=e,n&&this._inputNode&&`selectionStart`in this._inputNode&&(this._inputNode.selectionStart=n,this._inputNode.selectionEnd=n)}}}_reflectBackFormattedValueToUser(){this._reflectBackOn()&&(this.value=this.formattedValue===void 0?``:this.formattedValue,this.#e.didFormatterOutputSyncToView=!!this.formattedValue&&this.#e.didFormatterRun)}_reflectBackOn(){return!this._isHandlingUserInput}_proxyInputEvent(){this.dispatchEvent(new Event(`user-input-changed`,{bubbles:!0}))}_onUserInputChanged(){this._isHandlingUserInput=!0,this._syncValueUpwards(),this._isHandlingUserInput=!1}__onCompositionEvent({type:e}){e===`compositionstart`?this.__isHandlingComposition=!0:e===`compositionend`&&(this.__isHandlingComposition=!1,this._syncValueUpwards())}constructor(){super(),this.formatOn=`change`,this.formatOptions={mode:`auto`},this.formattedValue=void 0,this.serializedValue=void 0,this._isPasting=!1,this._isHandlingUserInput=!1,this.__prevViewValue=``,this.__onCompositionEvent=this.__onCompositionEvent.bind(this),this.addEventListener(`user-input-changed`,this._onUserInputChanged),this.addEventListener(`paste`,this.__onPaste),this._reflectBackFormattedValueToUser=this._reflectBackFormattedValueToUser.bind(this),this._reflectBackFormattedValueDebounced=()=>{setTimeout(this._reflectBackFormattedValueToUser)}}__onPaste(){this._isPasting=!0,setTimeout(()=>{this._isPasting=!1})}connectedCallback(){super.connectedCallback(),this.modelValue===void 0&&this._syncValueUpwards(),this.__prevViewValue=this.value,this._reflectBackFormattedValueToUser(),this._inputNode&&(this._inputNode.addEventListener(this.formatOn,this._reflectBackFormattedValueDebounced),this._inputNode.addEventListener(`input`,this._proxyInputEvent),this._inputNode.addEventListener(`compositionstart`,this.__onCompositionEvent),this._inputNode.addEventListener(`compositionend`,this.__onCompositionEvent))}disconnectedCallback(){super.disconnectedCallback(),this._inputNode&&(this._inputNode.removeEventListener(`input`,this._proxyInputEvent),this._inputNode.removeEventListener(this.formatOn,this._reflectBackFormattedValueDebounced),this._inputNode.removeEventListener(`compositionstart`,this.__onCompositionEvent),this._inputNode.removeEventListener(`compositionend`,this.__onCompositionEvent))}#t(){return this._isPasting?`pasted`:this._isHandlingUserInput&&this.__prevViewValue?`user-edited`:`auto`}#n(){let e=[];return this.#e.didFormatterOutputSyncToView&&e.push(`formatted`),e}}),Ao=Er(e=>class extends wo(e){static get properties(){return{touched:{type:Boolean,reflect:!0},dirty:{type:Boolean,reflect:!0},filled:{type:Boolean,reflect:!0},prefilled:{attribute:!1},submitted:{attribute:!1}}}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`touched`&&this.touched!==t&&this._onTouchedChanged(),e===`modelValue`&&(this.filled=!this._isEmpty()),e===`dirty`&&this.dirty!==t&&this._onDirtyChanged()}constructor(){super(),this.touched=!1,this.dirty=!1,this.prefilled=!1,this.filled=!1,this._leaveEvent=`blur`,this._valueChangedEvent=`model-value-changed`,this._iStateOnLeave=this._iStateOnLeave.bind(this),this._iStateOnValueChange=this._iStateOnValueChange.bind(this)}connectedCallback(){super.connectedCallback(),this.addEventListener(this._leaveEvent,this._iStateOnLeave),this.addEventListener(this._valueChangedEvent,this._iStateOnValueChange),this.initInteractionState()}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener(this._leaveEvent,this._iStateOnLeave),this.removeEventListener(this._valueChangedEvent,this._iStateOnValueChange)}initInteractionState(){this.dirty=!1,this.prefilled=!this._isEmpty()}_iStateOnLeave(){this.touched=!0,this.prefilled=!this._isEmpty()}_iStateOnValueChange(){this.dirty=!0}resetInteractionState(){this.touched=!1,this.submitted=!1,this.dirty=!1,this.prefilled=!this._isEmpty()}_onTouchedChanged(){this.dispatchEvent(new Event(`touched-changed`,{bubbles:!0,composed:!0}))}_onDirtyChanged(){this.dispatchEvent(new Event(`dirty-changed`,{bubbles:!0,composed:!0}))}_showFeedbackConditionFor(e,t){return t.touched&&t.dirty||t.prefilled||t.submitted}get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,submitted:this.submitted,touched:this.touched,dirty:this.dirty,filled:this.filled,prefilled:this.prefilled}}}),jo=class extends wo(Ao(Ca(ko(Oo(Vr(v)))))){firstUpdated(e){super.firstUpdated(e),this._initialModelValue=this.modelValue}connectedCallback(){super.connectedCallback(),this._onChange=this._onChange.bind(this),this._inputNode.addEventListener(`change`,this._onChange),this.classList.add(`form-field`)}disconnectedCallback(){super.disconnectedCallback(),this._inputNode?.removeEventListener(`change`,this._onChange)}resetInteractionState(){super.resetInteractionState(),this.submitted=!1}reset(){this.modelValue=this._initialModelValue,this.resetInteractionState()}clear(){this.modelValue=``}_onChange(e){this.dispatchEvent(new Event(`user-input-changed`,{bubbles:!0}))}get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,focused:this.focused}}get _focusableNode(){return this._inputNode}},Mo=class extends Array{_keys(){return Object.keys(this).filter(e=>Number.isNaN(Number(e)))}},No=Er(e=>class extends Co(e){static get properties(){return{_isFormOrFieldset:{type:Boolean}}}constructor(){super(),this.formElements=new Mo,this._isFormOrFieldset=!1,this._onRequestToAddFormElement=this._onRequestToAddFormElement.bind(this),this._onRequestToChangeFormElementName=this._onRequestToChangeFormElementName.bind(this),this.addEventListener(`form-element-register`,this._onRequestToAddFormElement),this.addEventListener(`form-element-name-changed`,this._onRequestToChangeFormElementName),this.initComplete=new Promise((e,t)=>{this.__resolveInitComplete=e,this.__rejectInitComplete=t}),this.registrationComplete=new Promise((e,t)=>{this.__resolveRegistrationComplete=e,this.__rejectRegistrationComplete=t}),this.registrationComplete.done=!1,this.registrationComplete.then(()=>{this.registrationComplete.done=!0,this.__resolveInitComplete(void 0)},()=>{throw this.registrationComplete.done=!0,this.__rejectInitComplete(void 0),Error(`Registration could not finish. Please use await el.registrationComplete;`)})}connectedCallback(){super.connectedCallback(),this._completeRegistration()}_completeRegistration(){Promise.resolve().then(()=>this.__resolveRegistrationComplete(void 0))}disconnectedCallback(){super.disconnectedCallback(),this.registrationComplete.done===!1&&Promise.resolve().then(()=>{Promise.resolve().then(()=>{this.__rejectRegistrationComplete()})})}isRegisteredFormElement(e){return this.formElements.some(t=>t===e)}addFormElement(e,t){if(e._parentFormGroup=this,t>=0?this.formElements.splice(t,0,e):this.formElements.push(e),this._isFormOrFieldset){let{name:n}=e;if(n===this.name)throw console.info(`Error Node:`,e),TypeError(`You can not have the same name "${n}" as your parent`);if(n.substr(-2)===`[]`)Array.isArray(this.formElements[n])||(this.formElements[n]=new Mo),t>0?this.formElements[n].splice(t,0,e):this.formElements[n].push(e);else if(!this.formElements[n])this.formElements[n]=e;else throw console.info(`Error Node:`,e),TypeError(`Name "${n}" is already registered - if you want an array add [] to the end`)}}removeFormElement(e){let t=this.formElements.indexOf(e);if(t>-1&&this.formElements.splice(t,1),this._isFormOrFieldset){let{name:t}=e;if(t.substr(-2)===`[]`&&this.formElements[t]){let n=this.formElements[t].indexOf(e);n>-1&&this.formElements[t].splice(n,1)}else this.formElements[t]&&delete this.formElements[t]}}_onRequestToAddFormElement(e){let t=e.detail.element;if(t===this||this.isRegisteredFormElement(t))return;e.stopPropagation();let n=-1;if(this.formElements&&Array.isArray(this.formElements)){for(let[e,r]of this.formElements.entries())if(!(r.compareDocumentPosition(t)&Node.DOCUMENT_POSITION_FOLLOWING)){n=e;break}}this.addFormElement(t,n)}_onRequestToChangeFormElementName(e){let t=this.formElements[e.detail.oldName];t&&(this.formElements[e.detail.newName]=t,delete this.formElements[e.detail.oldName])}_onRequestToRemoveFormElement(e){let t=e.detail.element;t!==this&&this.isRegisteredFormElement(t)&&(e.stopPropagation(),this.removeFormElement(t))}}),Po=Er(e=>class extends e{constructor(){super(),this.registrationTarget=void 0,this.__redispatchEventForFormRegistrarPortalMixin=this.__redispatchEventForFormRegistrarPortalMixin.bind(this),this.addEventListener(`form-element-register`,this.__redispatchEventForFormRegistrarPortalMixin)}__redispatchEventForFormRegistrarPortalMixin(e){if(e.stopPropagation(),!this.registrationTarget)throw Error(`A FormRegistrarPortal element requires a .registrationTarget`);this.registrationTarget.dispatchEvent(new CustomEvent(`form-element-register`,{detail:{element:e.detail.element},bubbles:!0}))}}),Fo=Er(e=>class extends ko(Ca(wo(e))){static get properties(){return{autocomplete:{type:String,reflect:!0}}}constructor(){super(),this.autocomplete=void 0}get _inputNode(){return super._inputNode}get selectionStart(){let e=this._inputNode;return e&&e.selectionStart?e.selectionStart:0}set selectionStart(e){let t=this._inputNode;t&&t.selectionStart&&(t.selectionStart=e)}get selectionEnd(){let e=this._inputNode;return e&&e.selectionEnd?e.selectionEnd:0}set selectionEnd(e){let t=this._inputNode;t&&t.selectionEnd&&(t.selectionEnd=e)}get value(){return this._inputNode&&this._inputNode.value||this.__value||``}set value(e){this._inputNode?(this._inputNode.value!==e&&this._setValueAndPreserveCaret(e),this.__value=void 0):this.__value=e}_setValueAndPreserveCaret(e){if(this.focused)try{if(!(this._inputNode instanceof HTMLSelectElement)){let t=this._inputNode.selectionStart;this._inputNode.value=e,this._inputNode.selectionStart=t,this._inputNode.selectionEnd=t}}catch{this._inputNode.value=e}else this._inputNode.value=e}_reflectBackFormattedValueToUser(){if(super._reflectBackFormattedValueToUser(),this._reflectBackOn()&&this.focused)try{this._inputNode.selectionStart=this._inputNode.value.length}catch{}}get _focusableNode(){return this._inputNode}}),Io=Er(e=>class extends No(Oo(Ao(e))){static get properties(){return{multipleChoice:{type:Boolean,attribute:`multiple-choice`}}}get modelValue(){let e=this._getCheckedElements();return this.multipleChoice?e.map(e=>e.choiceValue):e[0]?e[0].choiceValue:``}set modelValue(e){let t=(t,n)=>typeof t.choiceValue==`object`?JSON.stringify(t.choiceValue)===JSON.stringify(e):t.choiceValue===n;this.__isInitialModelValue?this.registrationComplete.then(()=>{this.__isInitialModelValue=!1,this._setCheckedElements(e,t),this.requestUpdate(`modelValue`,this._oldModelValue)}):(this._setCheckedElements(e,t),this.requestUpdate(`modelValue`,this._oldModelValue)),this._oldModelValue=this.modelValue}get serializedValue(){let e=this._getCheckedElements();return this.multipleChoice?e.map(e=>e.serializedValue.value):e[0]?e[0].serializedValue.value:``}set serializedValue(e){let t=(e,t)=>e.serializedValue.value===t;this.__isInitialSerializedValue?this.registrationComplete.then(()=>{this.__isInitialSerializedValue=!1,this._setCheckedElements(e,t),this.requestUpdate(`serializedValue`)}):(this._setCheckedElements(e,t),this.requestUpdate(`serializedValue`))}get formattedValue(){let e=this._getCheckedElements();return this.multipleChoice?e.map(e=>e.formattedValue):e[0]?e[0].formattedValue:``}set formattedValue(e){let t=(e,t)=>e.formattedValue===t;this.__isInitialFormattedValue?this.registrationComplete.then(()=>{this.__isInitialFormattedValue=!1,this._setCheckedElements(e,t)}):this._setCheckedElements(e,t)}get operationMode(){return this._repropagationRole===`choice-group`?`select`:`enter`}constructor(){super(),this.multipleChoice=!1,this._repropagationRole=`choice-group`,this.__isInitialModelValue=!0,this.__isInitialSerializedValue=!0,this.__isInitialFormattedValue=!0}connectedCallback(){super.connectedCallback(),this.registrationComplete.then(()=>{this.__isInitialModelValue=!1,this.__isInitialSerializedValue=!1,this.__isInitialFormattedValue=!1})}_completeRegistration(){Promise.resolve().then(()=>super._completeRegistration())}updated(e){super.updated(e),e.has(`name`)&&this.name!==e.get(`name`)&&this.formElements.forEach(e=>{e.name=this.name})}addFormElement(e,t){this._throwWhenInvalidChildModelValue(e),e.name=this.name,super.addFormElement(e,t)}clear(){this.multipleChoice?this.modelValue=[]:this.modelValue=``}_triggerInitialModelValueChangedEvent(){this.registrationComplete.then(()=>{this._dispatchInitialModelValueChangedEvent()})}_getFromAllFormElementsFilter(e,t){return!0}_getFromAllFormElements(e,t){let n=t||this._getFromAllFormElementsFilter;return e===`modelValue`||e===`serializedValue`||e===`formattedValue`?this[e]:this.formElements.filter(t=>n(t,e)).map(e=>e.property)}_throwWhenInvalidChildModelValue(e){if(typeof e.modelValue.checked!=`boolean`||!Object.prototype.hasOwnProperty.call(e.modelValue,`value`))throw Error(`The ${this.tagName.toLowerCase()} name="${this.name}" does not allow to register ${e.tagName.toLowerCase()} with .modelValue="${e.modelValue}" - The modelValue should represent an Object { value: "foo", checked: false }`)}_isEmpty(){return this.multipleChoice?this.modelValue.length===0:typeof this.modelValue==`string`&&this.modelValue===``||this.modelValue===void 0||this.modelValue===null}_checkSingleChoiceElements(e){let{target:t}=e;if(t.checked===!1)return;let n=t.name;this.formElements.filter(e=>e.name===n).forEach(e=>{e!==t&&(e.checked=!1)})}_getCheckedElements(){return this.formElements.filter(e=>e.checked&&!e.disabled)}_setCheckedElements(e,t){if(e==null){this.formElements.forEach(e=>e.checked=!1);return}for(let n=0;n<this.formElements.length;n+=1)if(this.multipleChoice){let t=e.includes(this.formElements[n].modelValue.value);typeof this.formElements[n].modelValue.value==`object`&&(t=e.map(e=>JSON.stringify(e)).includes(JSON.stringify(this.formElements[n].modelValue.value))),this.formElements[n].checked=t}else t(this.formElements[n],e)?this.formElements[n].checked=!0:this.formElements[n].checked=!1}__setChoiceGroupTouched(){let e=this.modelValue;e!=null&&e!==this.__previousCheckedValue&&(this.touched=!0,this.__previousCheckedValue=e)}_onBeforeRepropagateChildrenValues(e){let t=e.detail&&e.detail.element||e.target;this.multipleChoice||!t.checked||(this.formElements.forEach(e=>{t.choiceValue!==e.choiceValue&&(e.checked=!1)}),this.__setChoiceGroupTouched(),this.requestUpdate(`modelValue`,this._oldModelValue),this._oldModelValue=this.modelValue)}_repropagationCondition(e){return!(this._repropagationRole===`choice-group`&&!this.multipleChoice&&!e.checked)}}),Lo=(e,t={})=>e.value!==t.value||e.checked!==t.checked,Ro=Er(e=>class extends ko(e){static get properties(){return{checked:{type:Boolean,reflect:!0},disabled:{type:Boolean,reflect:!0},modelValue:{type:Object,hasChanged:Lo},choiceValue:{type:Object}}}get choiceValue(){return this.modelValue.value}set choiceValue(e){this.requestUpdate(`choiceValue`,this.choiceValue),this.modelValue.value!==e&&(this.modelValue={value:e,checked:this.modelValue.checked})}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`modelValue`?this.modelValue.checked!==this.checked&&this.__syncModelCheckedToChecked(this.modelValue.checked):e===`checked`&&this.modelValue.checked!==this.checked&&this.__syncCheckedToModel(this.checked)}firstUpdated(e){super.firstUpdated(e),e.has(`checked`)&&this.__syncCheckedToInputElement()}updated(e){super.updated(e),e.has(`modelValue`)&&this.__syncCheckedToInputElement(),e.has(`name`)&&this._parentFormGroup&&this._parentFormGroup.name!==this.name&&this._syncNameToParentFormGroup()}constructor(){super(),this.modelValue={value:``,checked:!1},this.disabled=!1,this._preventDuplicateLabelClick=this._preventDuplicateLabelClick.bind(this),this._toggleChecked=this._toggleChecked.bind(this)}static get styles(){return[...super.styles||[],p`
          :host {
            display: flex;
            flex-wrap: wrap;
          }

          :host([hidden]) {
            display: none;
          }

          .choice-field__graphic-container {
            display: none;
          }
          .choice-field__help-text {
            display: block;
            flex-basis: 100%;
          }
        `]}render(){return d`
        <slot name="input"></slot>
        <div class="choice-field__graphic-container" aria-hidden="true">
          ${this._choiceGraphicTemplate()}
        </div>
        <div class="choice-field__label">
          <slot name="label"></slot>
        </div>
        <small class="choice-field__help-text">
          <slot name="help-text"></slot>
        </small>
        ${this._afterTemplate()}
      `}_choiceGraphicTemplate(){return _}_afterTemplate(){return _}connectedCallback(){super.connectedCallback(),this._labelNode&&this._labelNode.addEventListener(`click`,this._preventDuplicateLabelClick),this.addEventListener(`user-input-changed`,this._toggleChecked)}disconnectedCallback(){super.disconnectedCallback(),this._labelNode&&this._labelNode.removeEventListener(`click`,this._preventDuplicateLabelClick),this.removeEventListener(`user-input-changed`,this._toggleChecked)}_preventDuplicateLabelClick(e){let t=e=>{e.stopImmediatePropagation(),this._inputNode.removeEventListener(`click`,t)};this._inputNode.addEventListener(`click`,t)}_toggleChecked(e){this.disabled||(this._isHandlingUserInput=!0,this.checked=!this.checked,this._isHandlingUserInput=!1)}_syncNameToParentFormGroup(){this._parentFormGroup.tagName.includes(this.tagName)&&(this.name=this._parentFormGroup?.name||``)}__syncModelCheckedToChecked(e){this.checked=e}__syncCheckedToModel(e){this.modelValue={value:this.choiceValue,checked:e}}__syncCheckedToInputElement(){this._inputNode&&(this._inputNode.checked=this.checked)}_proxyInputEvent(){}_onModelValueChanged({modelValue:e},t){let n;t&&t.modelValue&&(n=t.modelValue),this.constructor.elementProperties.get(`modelValue`).hasChanged(e,n)&&super._onModelValueChanged({modelValue:e})}parser(){return this.modelValue}formatter(e){return e&&e.value!==void 0?e.value:e}clear(){this.checked=!1}_isEmpty(){return!this.checked}_syncValueUpwards(){}}),zo=class extends To{static get validatorName(){return`FormElementsHaveNoError`}execute(e,t,n){return n?.node._anyFormElementHasFeedbackFor(`error`)}static async getMessage(){return``}},Bo=Er(e=>class extends No(wo(Oo(Dr(Vr(e))))){static get properties(){return{submitted:{type:Boolean,reflect:!0},focused:{type:Boolean,reflect:!0},dirty:{type:Boolean,reflect:!0},touched:{type:Boolean,reflect:!0},prefilled:{type:Boolean,reflect:!0}}}get _inputNode(){return this}get modelValue(){return this._getFromAllFormElements(`modelValue`)}set modelValue(e){this.__isInitialModelValue?(this.__isInitialModelValue=!1,this.registrationComplete.then(()=>{this._setValueMapForAllFormElements(`modelValue`,e)})):this._setValueMapForAllFormElements(`modelValue`,e)}get serializedValue(){return this._getFromAllFormElements(`serializedValue`)}set serializedValue(e){this.__isInitialSerializedValue?(this.__isInitialSerializedValue=!1,this.registrationComplete.then(()=>{this._setValueMapForAllFormElements(`serializedValue`,e)})):this._setValueMapForAllFormElements(`serializedValue`,e)}get formattedValue(){return this._getFromAllFormElements(`formattedValue`)}set formattedValue(e){this._setValueMapForAllFormElements(`formattedValue`,e)}get prefilled(){return this._everyFormElementHas(`prefilled`)}constructor(){super(),this.value=``,this.disabled=!1,this.submitted=!1,this.dirty=!1,this.touched=!1,this.focused=!1,this.__addedSubValidators=!1,this.__isInitialModelValue=!0,this.__isInitialSerializedValue=!0,this._checkForOutsideClick=this._checkForOutsideClick.bind(this),this.addEventListener(`focusin`,this._syncFocused),this.addEventListener(`focusout`,this._onFocusOut),this.addEventListener(`dirty-changed`,this._syncDirty),this.addEventListener(`validate-performed`,this.__onChildValidatePerformed),this.defaultValidators=[new zo],this.__descriptionElementsInParentChain=new Set,this.__pendingValues={modelValue:{},serializedValue:{}}}connectedCallback(){super.connectedCallback(),this.setAttribute(`role`,`group`),this.initComplete.then(()=>{this.__isInitialModelValue=!1,this.__isInitialSerializedValue=!1,this.__initInteractionStates()})}disconnectedCallback(){super.disconnectedCallback(),this.__hasActiveOutsideClickHandling&&=(document.removeEventListener(`click`,this._checkForOutsideClick),!1),this.__descriptionElementsInParentChain.clear()}__initInteractionStates(){this.formElements.forEach(e=>{typeof e.initInteractionState==`function`&&e.initInteractionState()})}_triggerInitialModelValueChangedEvent(){this.registrationComplete.then(()=>{this._dispatchInitialModelValueChangedEvent()})}updated(e){super.updated(e),e.has(`disabled`)&&(this.disabled?this.__requestChildrenToBeDisabled():this.__retractRequestChildrenToBeDisabled()),e.has(`focused`)&&this.focused===!0&&this.__setupOutsideClickHandling()}__setupOutsideClickHandling(){this.__hasActiveOutsideClickHandling||=(document.addEventListener(`click`,this._checkForOutsideClick),!0)}_checkForOutsideClick(e){this.contains(e.target)||(this.touched=!0)}__requestChildrenToBeDisabled(){this.formElements.forEach(e=>{e.makeRequestToBeDisabled&&e.makeRequestToBeDisabled()})}__retractRequestChildrenToBeDisabled(){this.formElements.forEach(e=>{e.retractRequestToBeDisabled&&e.retractRequestToBeDisabled()})}_inputGroupTemplate(){return d`
        <div class="input-group">
          <slot></slot>
        </div>
      `}submitGroup(){this.submitted=!0,this.formElements.forEach(e=>{typeof e.submitGroup==`function`?e.submitGroup():e.submitted=!0})}resetGroup(){this.formElements.forEach(e=>{typeof e.resetGroup==`function`?e.resetGroup():typeof e.reset==`function`&&e.reset()}),this.resetInteractionState()}clearGroup(){this.formElements.forEach(e=>{typeof e.clearGroup==`function`?e.clearGroup():typeof e.clear==`function`&&e.clear()}),this.resetInteractionState()}resetInteractionState(){this.submitted=!1,this.touched=!1,this.dirty=!1,this.formElements.forEach(e=>{typeof e.resetInteractionState==`function`&&e.resetInteractionState()})}_getFromAllFormElementsFilter(e,t){return!e.disabled}_getFromAllFormElements(e,t){let n={},r=t||this._getFromAllFormElementsFilter;return this.formElements._keys().forEach(t=>{let i=this.formElements[t];i instanceof Mo?n[t]=i.filter(t=>r(t,e)).map(t=>t[e]):r(i,e)&&(typeof i._getFromAllFormElements==`function`?n[t]=i._getFromAllFormElements(e):n[t]=i[e])}),n}_setValueForAllFormElements(e,t){this.formElements.forEach(n=>{n[e]=t})}_setValueMapForAllFormElements(e,t){t&&typeof t==`object`&&Object.keys(t).forEach(n=>{Array.isArray(this.formElements[n])&&this.formElements[n].forEach((r,i)=>{r[e]=t[n][i]}),this.formElements[n]?this.formElements[n][e]=t[n]:this.__pendingValues[e][n]=t[n]})}_anyFormElementHas(e){return Object.keys(this.formElements).some(t=>Array.isArray(this.formElements[t])?this.formElements[t].some(t=>!!t[e]):!!this.formElements[t][e])}_anyFormElementHasFeedbackFor(e){return Object.keys(this.formElements).some(t=>Array.isArray(this.formElements[t])?this.formElements[t].some(t=>!!(t.hasFeedbackFor&&t.hasFeedbackFor.includes(e))):!!(this.formElements[t].hasFeedbackFor&&this.formElements[t].hasFeedbackFor.includes(e)))}_everyFormElementHas(e){return Object.keys(this.formElements).every(t=>Array.isArray(this.formElements[t])?this.formElements[t].every(t=>!!t[e]):!!this.formElements[t][e])}__onChildValidatePerformed(e){e&&this.isRegisteredFormElement(e.target)&&this.validate()}_syncFocused(){this.focused=this._anyFormElementHas(`focused`)}_onFocusOut(e){let t=this.formElements[this.formElements.length-1];e.target===t&&(this.touched=!0),this.focused=!1}_syncDirty(){this.dirty=this._anyFormElementHas(`dirty`)}__storeAllDescriptionElementsInParentChain(){let e=this;for(;e;)So(e._getAriaDescriptionElements(),{reverse:!0}).forEach(e=>{e.getAttribute(`slot`)===`feedback`&&this.__descriptionElementsInParentChain.add(e)}),e=e._parentFormGroup}__linkParentMessages(e){this.__descriptionElementsInParentChain.forEach(t=>{typeof e.addToAriaDescribedBy==`function`&&e.addToAriaDescribedBy(t,{reorder:!1})})}__unlinkParentMessages(e){this.__descriptionElementsInParentChain.forEach(t=>{typeof e.removeFromAriaDescribedBy==`function`&&e.removeFromAriaDescribedBy(t)})}addFormElement(e,t){if(super.addFormElement(e,t),this.disabled&&e.makeRequestToBeDisabled(),this.__descriptionElementsInParentChain.size||this.__storeAllDescriptionElementsInParentChain(),this.__linkParentMessages(e),this.validate({clearCurrentResult:!0}),!e.modelValue){let t=this.__pendingValues;t.modelValue&&t.modelValue[e.name]?e.modelValue=t.modelValue[e.name]:t.serializedValue&&t.serializedValue[e.name]&&(e.serializedValue=t.serializedValue[e.name])}}get _initialModelValue(){return this._getFromAllFormElements(`_initialModelValue`)}removeFormElement(e){super.removeFormElement(e),this.validate({clearCurrentResult:!0}),typeof e.removeFromAriaLabelledBy==`function`&&this._labelNode&&e.removeFromAriaLabelledBy(this._labelNode,{reorder:!1}),this.__unlinkParentMessages(e)}_isEmpty(){return this.formElements.every(e=>e._isEmpty?.())}}),Vo=class extends Fo(jo){static get properties(){return{readOnly:{type:Boolean,attribute:`readonly`,reflect:!0},type:{type:String,reflect:!0},placeholder:{type:String,reflect:!0}}}get slots(){return{...super.slots,input:()=>{let e=document.createElement(`input`),t=this.getAttribute(`value`);return t&&e.setAttribute(`value`,t),e}}}get _inputNode(){return super._inputNode}constructor(){super(),this.readOnly=!1,this.type=`text`,this.placeholder=``}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`readOnly`&&this.__delegateReadOnly()}firstUpdated(e){super.firstUpdated(e),this.__delegateReadOnly()}updated(e){super.updated(e),e.has(`type`)&&(this._inputNode.type=this.type),e.has(`placeholder`)&&(this._inputNode.placeholder=this.placeholder),e.has(`disabled`)&&(this._inputNode.disabled=this.disabled,this.validate()),e.has(`name`)&&(this._inputNode.name=this.name),e.has(`autocomplete`)&&(this._inputNode.autocomplete=this.autocomplete)}__delegateReadOnly(){this._inputNode&&(this._inputNode.readOnly=this.readOnly)}},Ho=p`
  /* If an input has a "maxlength" attribute, it should not grow */
  :host([maxlength]) {
    .input-group__container {
      display: inline-flex;
      width: auto;
    }

    ::slotted(.form-control) {
      width: auto;
      flex: 0 0 auto;
    }
  }

  craft-input input[type='checkbox'],
  craft-input input[type='radio'] {
    background-color: var(--c-input-fill, var(--c-form-control-fill));
    border-width: var(
      --c-input-border-width,
      var(--c-form-control-border-width)
    );
    border-style: var(
      --c-input-border-style,
      var(--c-form-control-border-style)
    );
    border-color: var(
      --c-input-border-color,
      var(--c-form-control-border-color)
    );
    border-radius: var(--c-input-radius, var(--c-radius-sm));
  }

  [slot='help-text'] {
    font-size: var(--c-text-base);
    color: var(--c-text-quiet);
  }
`,Uo=class extends Vo{constructor(...e){super(...e),this.size=`medium`,this.small=!1,this.center=!1}static get styles(){return[...super.styles,ya,Ho]}connectedCallback(){super.connectedCallback(),this._inputNode&&this.maxlength&&this.maxlength>0&&(this._inputNode.size=this.maxlength)}};l([y({type:Number,reflect:!0})],Uo.prototype,`maxlength`,void 0),l([y({type:String,reflect:!0})],Uo.prototype,`size`,void 0),l([y({reflect:!0,type:Boolean})],Uo.prototype,`small`,void 0),l([y({reflect:!0,type:Boolean})],Uo.prototype,`center`,void 0),customElements.get(`craft-input`)||customElements.define(`craft-input`,Uo);var Wo=e=>e??_,Go=class extends To{static validatorName=`IsAcceptedFile`;static checkFileSize(e,t){return e<=t}static getExtension(e){return e?.slice(e.lastIndexOf(`.`))}static isExtensionAllowed(e,t){return t?.find(t=>t.toUpperCase()===e.toUpperCase())}static isFileTypeAllowed(e,t){return t?.find(t=>t.toUpperCase()===e.toUpperCase())}execute(e,t=this.param){let n,r,i=this.constructor,{allowedFileTypes:a,allowedFileExtensions:o,maxFileSize:s}=t;return a?.length?(n=e.some(e=>!i.isFileTypeAllowed(e.type,a)),n):o?.length?(r=e.some(e=>!i.isExtensionAllowed(i.getExtension(e.name),o)),r):e.findIndex(e=>!i.checkFileSize(e.size,s))>-1}static async getMessage(){return``}},Ko=class extends To{static validatorName=`DuplicateFileNames`;constructor(e,t){super(e,t),this.type=`info`}execute(e,t=this.param){return t.show}static async getMessage(){return qa().msg(`lion-input-file:uploadTextDuplicateFileName`)}},qo=524288e3,Jo={type:`FILE_TYPE`,size:`FILE_SIZE`},Yo={fail:`FAIL`,pass:`SUCCESS`},Xo=class{constructor(e,t){this.failedProp=[],this.systemFile=e,this._acceptCriteria=t,this.uploadFileStatus(),this.failedProp.length===0&&this.createDownloadUrl(e)}_getFileNameExtension(e){return e.slice(e.lastIndexOf(`.`))}uploadFileStatus(){if(this._acceptCriteria.allowedFileExtensions.length){let e=this._getFileNameExtension(this.systemFile.name);Go.isExtensionAllowed(e,this._acceptCriteria.allowedFileExtensions)||(this.status=Yo.fail,this.failedProp.push(Jo.type))}else if(this._acceptCriteria.allowedFileTypes.length){let e=this.systemFile.type;Go.isFileTypeAllowed(e,this._acceptCriteria.allowedFileTypes)||(this.status=Yo.fail,this.failedProp.push(Jo.type))}Go.checkFileSize(this.systemFile.size,this._acceptCriteria.maxFileSize)?this.status!==Yo.fail&&(this.status=Yo.pass):(this.status=Yo.fail,this.failedProp.push(Jo.size))}createDownloadUrl(e){this.downloadUrl=window.URL.createObjectURL(e)}},Zo=(e,t,n)=>{let r=new Map;for(let i=t;i<=n;i++)r.set(e[i],i);return r},Qo=w(class extends D{constructor(e){if(super(e),e.type!==ee.CHILD)throw Error(`repeat() can only be used in text expressions`)}dt(e,t,n){let r;n===void 0?n=t:t!==void 0&&(r=t);let i=[],a=[],o=0;for(let t of e)i[o]=r?r(t,o):o,a[o]=n(t,o),o++;return{values:a,keys:i}}render(e,t,n){return this.dt(e,t,n).values}update(e,[t,n,r]){let i=Rr(e),{values:a,keys:o}=this.dt(t,n,r);if(!Array.isArray(i))return this.ut=o,a;let s=this.ut??=[],c=[],l,u,d=0,f=i.length-1,p=0,h=a.length-1;for(;d<=f&&p<=h;)if(i[d]===null)d++;else if(i[f]===null)f--;else if(s[d]===o[p])c[p]=Fr(i[d],a[p]),d++,p++;else if(s[f]===o[h])c[h]=Fr(i[f],a[h]),f--,h--;else if(s[d]===o[h])c[h]=Fr(i[d],a[h]),Pr(e,c[h+1],i[d]),d++,h--;else if(s[f]===o[p])c[p]=Fr(i[f],a[p]),Pr(e,i[d],i[f]),f--,p++;else if(l===void 0&&(l=Zo(o,p,h),u=Zo(s,d,f)),l.has(s[d]))if(l.has(s[f])){let t=u.get(o[p]),n=t===void 0?null:i[t];if(n===null){let t=Pr(e,i[d]);Fr(t,a[p]),c[p]=t}else c[p]=Fr(n,a[p]),Pr(e,i[d],n),i[t]=null;p++}else zr(i[f]),f--;else zr(i[d]),d++;for(;p<=h;){let t=Pr(e,c[h+1]);Fr(t,a[p]),c[p++]=t}for(;d<=f;){let e=i[d++];e!==null&&zr(e)}return this.ut=o,Lr(e,c),m}}),$o=e=>{switch(e){case`bg-BG`:return O(()=>import(`./bg-BG2.js`),__vite__mapDeps([34,35]),import.meta.url);case`bg`:return O(()=>import(`./bg3.js`),[],import.meta.url);case`cs-CZ`:return O(()=>import(`./cs-CZ2.js`),__vite__mapDeps([36,37]),import.meta.url);case`cs`:return O(()=>import(`./cs3.js`),[],import.meta.url);case`de-DE`:return O(()=>import(`./de-DE2.js`),__vite__mapDeps([38,39]),import.meta.url);case`de`:return O(()=>import(`./de3.js`),[],import.meta.url);case`en-AU`:return O(()=>import(`./en-AU2.js`),__vite__mapDeps([40,41]),import.meta.url);case`en-GB`:return O(()=>import(`./en-GB2.js`),__vite__mapDeps([42,41]),import.meta.url);case`en-US`:return O(()=>import(`./en-US2.js`),__vite__mapDeps([43,41]),import.meta.url);case`en-PH`:case`en`:return O(()=>import(`./en3.js`),[],import.meta.url);case`es-ES`:return O(()=>import(`./es-ES2.js`),__vite__mapDeps([44,45]),import.meta.url);case`es`:return O(()=>import(`./es3.js`),[],import.meta.url);case`fr-FR`:return O(()=>import(`./fr-FR2.js`),__vite__mapDeps([46,47]),import.meta.url);case`fr-BE`:return O(()=>import(`./fr-BE2.js`),__vite__mapDeps([48,47]),import.meta.url);case`fr`:return O(()=>import(`./fr3.js`),[],import.meta.url);case`hu-HU`:return O(()=>import(`./hu-HU2.js`),__vite__mapDeps([49,50]),import.meta.url);case`hu`:return O(()=>import(`./hu3.js`),[],import.meta.url);case`it-IT`:return O(()=>import(`./it-IT2.js`),__vite__mapDeps([51,52]),import.meta.url);case`it`:return O(()=>import(`./it3.js`),[],import.meta.url);case`nl-BE`:return O(()=>import(`./nl-BE2.js`),__vite__mapDeps([53,54]),import.meta.url);case`nl-NL`:return O(()=>import(`./nl-NL2.js`),__vite__mapDeps([55,54]),import.meta.url);case`nl`:return O(()=>import(`./nl3.js`),[],import.meta.url);case`pl-PL`:return O(()=>import(`./pl-PL2.js`),__vite__mapDeps([56,57]),import.meta.url);case`pl`:return O(()=>import(`./pl3.js`),[],import.meta.url);case`ro-RO`:return O(()=>import(`./ro-RO2.js`),__vite__mapDeps([58,59]),import.meta.url);case`ro`:return O(()=>import(`./ro3.js`),[],import.meta.url);case`ru-RU`:return O(()=>import(`./ru-RU2.js`),__vite__mapDeps([60,61]),import.meta.url);case`ru`:return O(()=>import(`./ru3.js`),[],import.meta.url);case`sk-SK`:return O(()=>import(`./sk-SK2.js`),__vite__mapDeps([62,63]),import.meta.url);case`sk`:return O(()=>import(`./sk3.js`),[],import.meta.url);case`uk-UA`:return O(()=>import(`./uk-UA2.js`),__vite__mapDeps([64,65]),import.meta.url);case`uk`:return O(()=>import(`./uk3.js`),[],import.meta.url);case`zh-CN`:case`zh`:return O(()=>import(`./zh3.js`),[],import.meta.url);default:return O(()=>import(`./en3.js`),[],import.meta.url)}},es=class extends oo(po(v)){static get scopedElements(){return{...super.scopedElements,"lion-validation-feedback":yo}}static get properties(){return{fileList:{type:Array},multiple:{type:Boolean}}}static localizeNamespaces=[{"lion-input-file":$o},...super.localizeNamespaces];constructor(){super(),this.fileList=[],this.multiple=!1}updated(e){super.updated(e),e.has(`fileList`)&&this._enhanceLightDomA11y()}_enhanceLightDomA11y(){let e=this.shadowRoot?.querySelectorAll(`[id^="file-feedback"]`),t=this.parentNode?.parentNode;e?.forEach(e=>{t?.addEventListener(`focusin`,()=>{e.setAttribute(`aria-live`,`polite`)}),t?.addEventListener(`focusout`,()=>{e.setAttribute(`aria-live`,`assertive`)})})}_removeFile(e){this.dispatchEvent(new CustomEvent(`file-remove-requested`,{detail:{removedFile:e,status:e.status,uploadResponse:e.response}}))}_validationFeedbackTemplate(e,t){return d`
      <lion-validation-feedback
        id="file-feedback-${t}"
        .feedbackData="${e}"
        aria-live="assertive"
      ></lion-validation-feedback>
    `}_listItemBeforeTemplate(e){return _}_listItemAfterTemplate(e,t){return d`
      <button
        class="selected__list__item__remove-button"
        aria-label="${this.msgLit(`lion-input-file:removeButtonLabel`,{fileName:e.systemFile.name})}"
        @click=${()=>this._removeFile(e)}
      >
        ${this._removeButtonContentTemplate()}
      </button>
    `}_removeButtonContentTemplate(){return d`✖️`}_selectedListItemTemplate(e){let t=Wr();return d`
      <div class="selected__list__item" status="${e.status?e.status.toLowerCase():``}">
        <div class="selected__list__item__label">
          ${this._listItemBeforeTemplate(e)}
          <span id="selected-list-item-label-${t}" class="selected__list__item__label__text">
            <span class="sr-only">${this.msgLit(`lion-input-file:fileNameDescriptionLabel`)}</span>
            ${e.downloadUrl&&e.status!==`LOADING`?d`
                  <a
                    class="selected__list__item__label__link"
                    href="${e.downloadUrl}"
                    target="${e.downloadUrl.startsWith(`blob`)?`_blank`:``}"
                    rel="${Wo(e.downloadUrl.startsWith(`blob`)?`noopener noreferrer`:void 0)}"
                    >${e.systemFile?.name}</a
                  >
                `:e.systemFile?.name}
          </span>
          ${this._listItemAfterTemplate(e,t)}
        </div>
        ${e.status===`FAIL`&&e.validationFeedback?d`
              ${Qo(e.validationFeedback,e=>d`
                  ${this._validationFeedbackTemplate([e],t)}
                `)}
            `:_}
      </div>
    `}render(){return this.fileList?.length?d`
          ${this.multiple?d`
                <ul class="selected__list">
                  ${this.fileList.map(e=>d` <li>${this._selectedListItemTemplate(e)}</li> `)}
                </ul>
              `:d` ${this._selectedListItemTemplate(this.fileList[0])} `}
        `:_}static get styles(){return[p`
        .selected__list {
          list-style-type: none;
          margin-block-start: 0;
          margin-block-end: 0;
          padding-inline-start: 0;
        }

        .sr-only {
          position: absolute;
          width: 1px;
          height: 1px;
          overflow: hidden;
          clip-path: inset(100%);
          clip: rect(1px, 1px, 1px, 1px);
          white-space: nowrap;
          border: 0;
          margin: 0;
          padding: 0;
        }
      `]}};function ts(e,t=2){if(!+e)return`0 Bytes`;let n=1024,r=t<0?0:t,i=[` bytes`,`KB`,`MB`,`GB`,`TB`,`PB`,`EB`,`ZB`,`YB`],a=Math.floor(Math.log(e)/Math.log(n));return`${parseFloat((e/n**a).toFixed(r))}${i[a]}`}var ns=class extends po(oo(jo)){static get scopedElements(){return{...super.scopedElements,"lion-selected-file-list":es}}static get properties(){return{accept:{type:String},multiple:{type:Boolean,reflect:!0},buttonLabel:{type:String,attribute:`button-label`},maxFileSize:{type:Number,attribute:`max-file-size`},enableDropZone:{type:Boolean,attribute:`enable-drop-zone`},uploadOnSelect:{type:Boolean,attribute:`upload-on-select`},isDragging:{type:Boolean,attribute:`is-dragging`,reflect:!0},uploadResponse:{type:Array,state:!1},_selectedFilesMetaData:{type:Array,state:!0}}}static localizeNamespaces=[{"lion-input-file":$o},...super.localizeNamespaces];static get validationTypes(){return[`error`,`info`]}get slots(){return{...super.slots,input:()=>d`<input .value="${Wo(this.getAttribute(`value`))}" />`,"file-select-button":()=>d`<button
          type="button"
          id="select-button-${this._inputId}"
          @click="${this.__openDialogOnBtnClick}"
        >
          ${this.buttonLabel}
        </button>`,after:()=>d`<div data-description></div>`,"selected-file-list":()=>({template:d`
          <lion-selected-file-list
            .fileList=${this._selectedFilesMetaData}
            .multiple=${this.multiple}
          ></lion-selected-file-list>
        `,renderAsDirectHostChild:!0})}}get _inputNode(){return super._inputNode}get _buttonNode(){return this.querySelector(`#select-button-${this._inputId}`)}get buttonLabel(){return this.__buttonLabel||this._buttonNode?.textContent?.trim()||``}set buttonLabel(e){let t=this.buttonLabel;this.__buttonLabel=e,this.requestUpdate(`buttonLabel`,t)}get _focusableNode(){return this._buttonNode}get _isDragAndDropSupported(){return`draggable`in document.createElement(`div`)}constructor(){super(),this.type=`file`,this._selectedFilesMetaData=[],this.uploadResponse=[],this.__initialUploadResponse=this.uploadResponse,this.uploadOnSelect=!1,this.multiple=!1,this.enableDropZone=!1,this.maxFileSize=qo,this.accept=``,this.buttonLabel=``,this._initialButtonLabel=``,this.modelValue=[],this._onRemoveFile=this._onRemoveFile.bind(this),this.__duplicateFileNamesValidator=new Ko({show:!1}),this.__previouslyParsedFiles=null}get _fileListNode(){return Array.from(this.children).find(e=>e.slot===`selected-file-list`)}connectedCallback(){super.connectedCallback(),this.__initialUploadResponse=this.uploadResponse,this._initialButtonLabel=this.buttonLabel,this._inputNode.addEventListener(`change`,this._onChange),this._inputNode.addEventListener(`click`,this._onClick)}disconnectedCallback(){super.disconnectedCallback(),this._inputNode.removeEventListener(`change`,this._onChange),this._inputNode.removeEventListener(`click`,this._onClick)}onLocaleUpdated(){super.onLocaleUpdated(),this.multiple?this.buttonLabel=this._initialButtonLabel||this.msgLit(`lion-input-file:selectTextMultipleFile`):this.buttonLabel=this._initialButtonLabel||this.msgLit(`lion-input-file:selectTextSingleFile`)}get operationMode(){return`upload`}get _acceptCriteria(){let e=[],t=[];if(this.accept){let n=this.accept.replace(/\s+/g,``).split(`,`);e=n.filter(e=>e.includes(`/`)),t=n.filter(e=>!e.includes(`/`))}return{allowedFileTypes:e,allowedFileExtensions:t,maxFileSize:this.maxFileSize}}reset(){super.reset(),this._selectedFilesMetaData=[],this.uploadResponse=this.__initialUploadResponse,this.modelValue=[],this.dirty=!1}clear(){this._selectedFilesMetaData=[],this.uploadResponse=[],this.modelValue=[]}_showFeedbackConditionFor(e,t){return super._showFeedbackConditionFor(e,t)&&!(this.validationStates.error?.FileTypeAllowed||this.validationStates.error?.FileSizeAllowed)}parser(){if(this.__previouslyParsedFiles===this._inputNode.files)return this.modelValue;this.__previouslyParsedFiles=this._inputNode.files;let e=this._inputNode.files?Array.from(this._inputNode.files):[];return this.multiple?[...this.modelValue??[],...e]:e}formatter(e){return this._inputNode?.value||``}__setupDragDropEventListeners(){let e=this.shadowRoot?.querySelector(`.input-file__drop-zone`);[`dragenter`,`dragover`,`dragleave`].forEach(t=>{e?.addEventListener(t,e=>{e.preventDefault(),e.stopPropagation(),this.isDragging=t!==`dragleave`},!1)}),window.addEventListener(`drop`,e=>{e.target===this._inputNode&&e.preventDefault(),this.isDragging=!1},!1)}firstUpdated(e){super.firstUpdated(e),this.__setupFileValidators(),this._inputNode&&(this._inputNode.type=this.type,this._inputNode.setAttribute(`tabindex`,`-1`),this._inputNode.multiple=this.multiple,this.accept.length&&(this._inputNode.accept=this.accept)),this.enableDropZone&&this._isDragAndDropSupported&&(this.__setupDragDropEventListeners(),this.setAttribute(`drop-zone`,``)),this._fileListNode.addEventListener(`file-remove-requested`,this._onRemoveFile)}updated(e){super.updated(e),e.has(`disabled`)&&(this._inputNode.disabled=this.disabled,this.validate()),e.has(`buttonLabel`)&&this._buttonNode&&(this._buttonNode.textContent=this.buttonLabel),e.has(`name`)&&(this._inputNode.name=this.name),e.has(`_ariaLabelledNodes`)&&this.__syncAriaLabelledByAttributesToButton(),e.has(`_ariaDescribedNodes`)&&this.__syncAriaDescribedByAttributesToButton(),e.has(`uploadResponse`)&&(this._selectedFilesMetaData.length===0&&this.uploadResponse.forEach(e=>{let t={systemFile:{name:e.name},response:e,status:e.status,validationFeedback:[{message:e.errorMessage}]};this._selectedFilesMetaData=[...this._selectedFilesMetaData,t]}),this._selectedFilesMetaData.forEach(e=>{!this.uploadResponse.some(t=>t.name===e.systemFile.name)&&this.uploadOnSelect?this.__removeFileFromList(e):(this.uploadResponse.forEach(t=>{t.name===e.systemFile.name&&(e.response=t,e.downloadUrl=t.downloadUrl?t.downloadUrl:e.downloadUrl,e.status=t.status,e.validationFeedback=[{type:typeof t.errorMessage==`string`&&t.errorMessage?.length>0?`error`:`success`,message:t.errorMessage??``}])}),this._selectedFilesMetaData=[...this._selectedFilesMetaData])}),this._updateUploadButtonDescription())}__computeNewAddedFiles(e){let t=e.filter(e=>this._selectedFilesMetaData.findIndex(t=>t.systemFile.name===e.name)===-1);return this.__duplicateFileNamesValidator.param={show:e.length!==t.length},this.validate(),t}_processDroppedFiles(e){if(e.preventDefault(),this.isDragging=!1,!(e.dataTransfer&&e.dataTransfer.items.length>1&&!this.multiple||!e.dataTransfer?.files)){if(this._inputNode.files=e.dataTransfer.files,this.multiple){let t=this.__computeNewAddedFiles(Array.from(e.dataTransfer.files));this.modelValue=[...this.modelValue??[],...t]}else this.modelValue=Array.from(e.dataTransfer.files);this._processFiles(Array.from(e.dataTransfer.files))}}_onChange(e){this.touched=!0,this._onUserInputChanged(),this._processFiles(e?.target?.files)}_onClick(e){e.target.value=``}__syncAriaLabelledByAttributesToButton(){if(this._inputNode.hasAttribute(`aria-labelledby`)){let e=this._inputNode.getAttribute(`aria-labelledby`);this._buttonNode?.setAttribute(`aria-labelledby`,`select-button-${this._inputId} ${e}`)}}__syncAriaDescribedByAttributesToButton(){if(this._inputNode.hasAttribute(`aria-describedby`)){let e=this._inputNode.getAttribute(`aria-describedby`)||``;this._buttonNode?.setAttribute(`aria-describedby`,e)}}__setupFileValidators(){this.defaultValidators=[new Go(this._acceptCriteria),this.__duplicateFileNamesValidator]}_processFiles(e){let t=this.__computeNewAddedFiles(Array.from(e));!this.multiple&&t.length>0&&(this._selectedFilesMetaData=[],this.uploadResponse=[]);let n;for(let e of t.values())n=new Xo(e,this._acceptCriteria),n.failedProp?.length?(this._handleErroredFiles(n),this.uploadResponse=[...this.uploadResponse,{name:n.systemFile.name,status:`FAIL`,errorMessage:n.validationFeedback[0].message}]):this.uploadResponse=[...this.uploadResponse,{name:n.systemFile.name,status:`SUCCESS`}],this._selectedFilesMetaData=[...this._selectedFilesMetaData,n],this._handleErrors();let r=this._selectedFilesMetaData.filter(({systemFile:e,status:n})=>t.includes(e)&&n===`SUCCESS`).map(({systemFile:e})=>e);r.length>0&&this._dispatchFileListChangeEvent(r)}_dispatchFileListChangeEvent(e){this.dispatchEvent(new CustomEvent(`file-list-changed`,{detail:{newFiles:e}}))}_handleErrors(){let e=!1;if(this._selectedFilesMetaData.forEach(t=>{t.failedProp&&t.failedProp.length>0&&(e=!0)}),e)this.hasFeedbackFor?.push(`error`),this.shouldShowFeedbackFor.push(`error`);else if(this._prevHasErrors&&this.hasFeedbackFor.includes(`error`)){let e=this.hasFeedbackFor.indexOf(`error`);this.hasFeedbackFor.slice(e,e+1);let t=this.shouldShowFeedbackFor.indexOf(`error`);this.shouldShowFeedbackFor.slice(t,t+1)}this._prevHasErrors=e}_handleErroredFiles(e){e.validationFeedback=[];let{allowedFileExtensions:t,allowedFileTypes:n}=this._acceptCriteria,r=[],i=0,a;t.length?(r=t,a=r.pop(),i=r.length):n.length&&(n.forEach(e=>{if(e.endsWith(`/*`))r.push(e.slice(0,-2));else if(e===`text/plain`)r.push(`text`);else{let t=e.indexOf(`/`),n=e.slice(t+1);if(!n.includes(`+`))r.push(`.${n}`);else{let e=n.split(`+`);r.push(`.${e[0]}`)}}}),a=r.pop(),i=r.length);let o=``;o=a?i?`${this.msgLit(`lion-input-file:allowedFileValidatorComplex`,{allowedTypesArray:r.join(`, `),allowedTypesLastItem:a,maxSize:ts(this.maxFileSize)})}`:`${this.msgLit(`lion-input-file:allowedFileValidatorSimple`,{allowedType:a,maxSize:ts(this.maxFileSize)})}`:`${this.msgLit(`lion-input-file:allowedFileSize`,{maxSize:ts(this.maxFileSize)})}`;let s={message:o,type:`error`};e.validationFeedback?.push(s)}_updateUploadButtonDescription(){let e=[],t;this._selectedFilesMetaData.forEach(n=>{n.status===`FAIL`&&(t=n.validationFeedback?n.validationFeedback[0].message.toString():``,e.push(n.systemFile.name))});let n=this.querySelector(`[slot="after"]`);if(n)if(!this._selectedFilesMetaData||this._selectedFilesMetaData.length===0)this.uploadOnSelect?n.textContent=this.msgLit(`lion-input-file:noFilesUploaded`):n.textContent=this.msgLit(`lion-input-file:noFilesSelected`);else if(this._selectedFilesMetaData.length===1){let{name:e}=this._selectedFilesMetaData[0].systemFile;this.uploadOnSelect?n.textContent=t||this.msgLit(`lion-input-file:fileUploaded`)+(e??``):n.textContent=t||this.msgLit(`lion-input-file:fileSelected`)+(e??``)}else this.uploadOnSelect?n.textContent=`${this.msgLit(`lion-input-file:filesUploaded`,{numberOfFiles:this._selectedFilesMetaData.length})} ${t?this.msgLit(`lion-input-file:generalValidatorMessage`,{validatorMessage:t,listOfErroneousFiles:e.join(`, `)}):``}`:n.textContent=`${this.msgLit(`lion-input-file:filesSelected`,{numberOfFiles:this._selectedFilesMetaData.length})} ${t?this.msgLit(`lion-input-file:generalValidatorMessage`,{validatorMessage:t,listOfErroneousFiles:e.join(`, `)}):``}`}__removeFileFromList(e){this._selectedFilesMetaData=this._selectedFilesMetaData.filter(t=>t.systemFile.name!==e.systemFile.name),this.modelValue&&=this.modelValue.filter(t=>t.name!==e.systemFile.name),this._inputNode.value=``,this._handleErrors(),this._updateUploadButtonDescription()}_onRemoveFile(e){if(this.disabled)return;let{removedFile:t}=e.detail;!this.uploadOnSelect&&t&&this.__removeFileFromList(t),this._removeFile(t)}_removeFile(e){this.dispatchEvent(new CustomEvent(`file-removed`,{detail:{removedFile:e,status:e.status,uploadResponse:e.response}}))}_reflectBackOn(){return!1}_isEmpty(){return this.modelValue?.length===0}_dropZoneTemplate(){return d`
      <div @drop="${this._processDroppedFiles}" class="input-file__drop-zone">
        <div class="input-file__drop-zone__text">
          ${this.msgLit(`lion-input-file:dragAndDropText`)}
        </div>
        <slot name="file-select-button"></slot>
      </div>
    `}_inputGroupAfterTemplate(){return d` <slot name="selected-file-list"></slot> `}_inputGroupInputTemplate(){return d`
      <slot name="input"> </slot>
      <slot name="after"> </slot>
      ${this.enableDropZone&&this._isDragAndDropSupported?this._dropZoneTemplate():d`
            <div class="input-group__file-select-button">
              <slot name="file-select-button"></slot>
            </div>
          `}
    `}static get styles(){return[super.styles,p`
        .input-group__container {
          position: relative;
          display: flex;
          flex-direction: column;
          width: fit-content;
        }

        :host([drop-zone]) .input-group__container {
          width: auto;
        }

        .input-group__container ::slotted(input[type='file']) {
          /** Invisible, since means of interaction is button */
          position: absolute;
          opacity: 0;
          /** Full cover positioned, so it will be a drag and drop surface */
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
        }

        .input-file__drop-zone {
          display: flex;
          position: relative;
          flex-direction: column;
          align-items: center;
          border: dashed 2px black;
          padding: 24px 0;
        }

        .input-group__container ::slotted([slot='after']) {
          position: absolute;
          width: 1px;
          height: 1px;
          overflow: hidden;
          clip-path: inset(100%);
          clip: rect(1px, 1px, 1px, 1px);
          white-space: nowrap;
          border: 0;
          margin: 0;
          padding: 0;
        }
      `]}__openDialogOnBtnClick(e){e.preventDefault(),e.stopPropagation(),this._inputNode.click()}},rs=class extends es{static get styles(){return[...super.styles,p`
        ul {
          display: flex;
          flex-direction: column;
          gap: var(--c-spacing-sm);
        }

        li {
          display: flex;
          align-items: center;
          gap: var(--c-spacing-sm);
          padding: var(--c-spacing-sm);
          border: 1px solid var(--c-color-neutral-border-quiet);
          border-radius: var(--c-radius-sm);
          background-color: var(--c-surface-default);
        }

        .file-name {
          flex: 1;
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
        }

        .remove-button {
          flex-shrink: 0;
        }

        .preview-thumb {
          width: var(--thumbnail-size, calc(120rem / 16));
          height: auto;
        }

        .selected__list__item__label {
          display: flex;
          align-items: center;
          gap: var(--c-spacing-sm);
        }

        .selected__list__item__remove-button {
          margin-inline-start: var(--c-spacing-md);
        }
      `]}_listItemAfterTemplate(e,t){return d`
      <craft-button
        icon
        size="small"
        variant="plain"
        class="selected__list__item__remove-button"
        aria-label="${this.msgLit(`lion-input-file:removeButtonLabel`,{fileName:e.systemFile.name})}"
        @click=${()=>this._removeFile(e)}
      >
        ${this._removeButtonContentTemplate()}
      </craft-button>
    `}_removeButtonContentTemplate(){return d`<craft-icon name="x"></craft-icon>`}_listItemBeforeTemplate(e){return d`<img src="${e.downloadUrl}" alt="" class="preview-thumb" />`}},is=p`
  /* Add any craft-specific styles for input-file here */
  ::slotted([slot='selected-file-list']) {
    margin-block-start: var(--c-spacing-lg);
  }
`,as=class extends ns{static get styles(){return[...super.styles,ya,is]}get slots(){return{...super.slots,"file-select-button":()=>d`<craft-button
          type="button"
          id="select-button-${this._inputId}"
          @click="${this.__openDialogOnBtnClick}"
        >
          ${this.buttonLabel}
        </craft-button>`}}static get scopedElements(){return{...super.scopedElements,"lion-selected-file-list":rs}}};customElements.get(`craft-input-file`)||customElements.define(`craft-input-file`,as);var os=function(e,t,n,r,i){if(r===`m`)throw TypeError(`Private method is not writable`);if(r===`a`&&!i)throw TypeError(`Private accessor was defined without a setter`);if(typeof t==`function`?e!==t||!i:!t.has(e))throw TypeError(`Cannot write private member to an object whose class did not declare it`);return r===`a`?i.call(e,n):i?i.value=n:t.set(e,n),n},ss=function(e,t,n,r){if(n===`a`&&!r)throw TypeError(`Private accessor was defined without a getter`);if(typeof t==`function`?e!==t||!r:!t.has(e))throw TypeError(`Cannot read private member from an object whose class did not declare it`);return n===`m`?r:n===`a`?r.call(e):r?r.value:t.get(e)},cs,ls=class{formatToParts(e){let t=[];for(let n of e)t.push({type:`element`,value:n}),t.push({type:`literal`,value:`, `});return t.slice(0,-1)}},us=typeof Intl<`u`&&Intl.ListFormat||ls,ds=[[`years`,`year`],[`months`,`month`],[`weeks`,`week`],[`days`,`day`],[`hours`,`hour`],[`minutes`,`minute`],[`seconds`,`second`],[`milliseconds`,`millisecond`]],fs={minimumIntegerDigits:2},ps=class{constructor(e,t={}){cs.set(this,void 0);let n=String(t.style||`short`);n!==`long`&&n!==`short`&&n!==`narrow`&&n!==`digital`&&(n=`short`);let r=n===`digital`?`numeric`:n,i=t.hours||r;r=i===`2-digit`?`numeric`:i;let a=t.minutes||r;r=a===`2-digit`?`numeric`:a;let o=t.seconds||r;r=o===`2-digit`?`numeric`:o;let s=t.milliseconds||r;os(this,cs,{locale:e,style:n,years:t.years||n===`digital`?`short`:n,yearsDisplay:t.yearsDisplay===`always`?`always`:`auto`,months:t.months||n===`digital`?`short`:n,monthsDisplay:t.monthsDisplay===`always`?`always`:`auto`,weeks:t.weeks||n===`digital`?`short`:n,weeksDisplay:t.weeksDisplay===`always`?`always`:`auto`,days:t.days||n===`digital`?`short`:n,daysDisplay:t.daysDisplay===`always`?`always`:`auto`,hours:i,hoursDisplay:t.hoursDisplay===`always`||n===`digital`?`always`:`auto`,minutes:a,minutesDisplay:t.minutesDisplay===`always`||n===`digital`?`always`:`auto`,seconds:o,secondsDisplay:t.secondsDisplay===`always`||n===`digital`?`always`:`auto`,milliseconds:s,millisecondsDisplay:t.millisecondsDisplay===`always`?`always`:`auto`},`f`)}resolvedOptions(){return ss(this,cs,`f`)}formatToParts(e){let t=[],n=ss(this,cs,`f`),r=n.style,i=n.locale;for(let[a,o]of ds){let s=e[a];if(n[`${a}Display`]===`auto`&&!s)continue;let c=n[a],l=c===`2-digit`?fs:c===`numeric`?{}:{style:`unit`,unit:o,unitDisplay:c},u=new Intl.NumberFormat(i,l).format(s);a===`months`&&(c===`narrow`||r===`narrow`&&u.endsWith(`m`))&&(u=u.replace(/(\d+)m$/,`$1mo`)),t.push(u)}return new us(i,{type:`unit`,style:r===`digital`?`short`:r}).formatToParts(t)}format(e){return this.formatToParts(e).map(e=>e.value).join(``)}};cs=new WeakMap;var ms=/^[-+]?P(?:(\d+)Y)?(?:(\d+)M)?(?:(\d+)W)?(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?)?$/,hs=[`year`,`month`,`week`,`day`,`hour`,`minute`,`second`,`millisecond`],gs=e=>ms.test(e),_s=class e{constructor(e=0,t=0,n=0,r=0,i=0,a=0,o=0,s=0){this.years=e,this.months=t,this.weeks=n,this.days=r,this.hours=i,this.minutes=a,this.seconds=o,this.milliseconds=s,this.years||=0,this.sign||=Math.sign(this.years),this.months||=0,this.sign||=Math.sign(this.months),this.weeks||=0,this.sign||=Math.sign(this.weeks),this.days||=0,this.sign||=Math.sign(this.days),this.hours||=0,this.sign||=Math.sign(this.hours),this.minutes||=0,this.sign||=Math.sign(this.minutes),this.seconds||=0,this.sign||=Math.sign(this.seconds),this.milliseconds||=0,this.sign||=Math.sign(this.milliseconds),this.blank=this.sign===0}abs(){return new e(Math.abs(this.years),Math.abs(this.months),Math.abs(this.weeks),Math.abs(this.days),Math.abs(this.hours),Math.abs(this.minutes),Math.abs(this.seconds),Math.abs(this.milliseconds))}static from(t){if(typeof t==`string`){let n=String(t).trim(),r=n.startsWith(`-`)?-1:1,i=n.match(ms)?.slice(1).map(e=>(Number(e)||0)*r);return i?new e(...i):new e}else if(typeof t==`object`){let{years:n,months:r,weeks:i,days:a,hours:o,minutes:s,seconds:c,milliseconds:l}=t;return new e(n,r,i,a,o,s,c,l)}throw RangeError(`invalid duration`)}static compare(t,n){let r=Date.now(),i=Math.abs(vs(r,e.from(t)).getTime()-r),a=Math.abs(vs(r,e.from(n)).getTime()-r);return i>a?-1:i<a?1:0}toLocaleString(e,t){return new ps(e,t).format(this)}};function vs(e,t){let n=new Date(e);return t.sign<0?(n.setUTCSeconds(n.getUTCSeconds()+t.seconds),n.setUTCMinutes(n.getUTCMinutes()+t.minutes),n.setUTCHours(n.getUTCHours()+t.hours),n.setUTCDate(n.getUTCDate()+t.weeks*7+t.days),n.setUTCMonth(n.getUTCMonth()+t.months),n.setUTCFullYear(n.getUTCFullYear()+t.years)):(n.setUTCFullYear(n.getUTCFullYear()+t.years),n.setUTCMonth(n.getUTCMonth()+t.months),n.setUTCDate(n.getUTCDate()+t.weeks*7+t.days),n.setUTCHours(n.getUTCHours()+t.hours),n.setUTCMinutes(n.getUTCMinutes()+t.minutes),n.setUTCSeconds(n.getUTCSeconds()+t.seconds)),n}function ys(e,t=`second`,n=Date.now()){let r=e.getTime()-n;if(r===0)return new _s;let i=Math.sign(r),a=Math.abs(r),o=Math.floor(a/1e3),s=Math.floor(o/60),c=Math.floor(s/60),l=Math.floor(c/24),u=Math.floor(l/30),d=Math.floor(u/12),f=hs.indexOf(t)||hs.length;return new _s(f>=0?d*i:0,f>=1?(u-d*12)*i:0,0,f>=3?(l-u*30)*i:0,f>=4?(c-l*24)*i:0,f>=5?(s-c*60)*i:0,f>=6?(o-s*60)*i:0,f>=7?(a-o*1e3)*i:0)}function bs(e,{relativeTo:t=Date.now()}={}){if(t=new Date(t),e.blank)return e;let n=e.sign,r=Math.abs(e.years),i=Math.abs(e.months),a=Math.abs(e.weeks),o=Math.abs(e.days),s=Math.abs(e.hours),c=Math.abs(e.minutes),l=Math.abs(e.seconds),u=Math.abs(e.milliseconds);u>=900&&(l+=Math.round(u/1e3)),(l||c||s||o||a||i||r)&&(u=0),l>=55&&(c+=Math.round(l/60)),(c||s||o||a||i||r)&&(l=0),c>=55&&(s+=Math.round(c/60)),(s||o||a||i||r)&&(c=0),o&&s>=12&&(o+=Math.round(s/24)),!o&&s>=21&&(o+=Math.round(s/24)),(o||a||i||r)&&(s=0);let d=t.getFullYear(),f=t.getMonth(),p=t.getDate();if(o>=27||r+i+o){let e=new Date(t);e.setDate(1),e.setMonth(f+i*n+1),e.setDate(0);let s=Math.max(0,p-e.getDate()),c=new Date(t);c.setFullYear(d+r*n),c.setDate(p-s),c.setMonth(f+i*n),c.setDate(p-s+o*n);let l=c.getFullYear()-t.getFullYear(),u=c.getMonth()-t.getMonth(),m=Math.abs(Math.round((Number(c)-Number(t))/864e5))+s,h=Math.abs(l*12+u);m<27?(o>=6?(a+=Math.round(o/7),o=0):o=m,i=r=0):h<=11?(i=h,r=0):(i=0,r=l*n),(i||r)&&(o=0)}return r&&(i=0),a>=4&&(i+=Math.round(a/4)),(i||r)&&(a=0),o&&a&&!i&&!r&&(a+=Math.round(o/7),o=0),new _s(r*n,i*n,a*n,o*n,s*n,c*n,l*n,u*n)}function xs(e,t){let n=bs(e,t);if(n.blank)return[0,`second`];for(let e of hs){if(e===`millisecond`)continue;let t=n[`${e}s`];if(t)return[t,e]}return[0,`second`]}var I=function(e,t,n,r){if(n===`a`&&!r)throw TypeError(`Private accessor was defined without a getter`);if(typeof t==`function`?e!==t||!r:!t.has(e))throw TypeError(`Cannot read private member from an object whose class did not declare it`);return n===`m`?r:n===`a`?r.call(e):r?r.value:t.get(e)},Ss=function(e,t,n,r,i){if(r===`m`)throw TypeError(`Private method is not writable`);if(r===`a`&&!i)throw TypeError(`Private accessor was defined without a setter`);if(typeof t==`function`?e!==t||!i:!t.has(e))throw TypeError(`Cannot write private member to an object whose class did not declare it`);return r===`a`?i.call(e,n):i?i.value=n:t.set(e,n),n},Cs,ws,Ts,Es,Ds,Os,ks,As,js,Ms,Ns,Ps,Fs,Is,Ls=globalThis.HTMLElement||null,Rs=new _s,zs=new _s(0,0,0,0,0,1),Bs=class extends Event{constructor(e,t,n,r){super(`relative-time-updated`,{bubbles:!0,composed:!0}),this.oldText=e,this.newText=t,this.oldTitle=n,this.newTitle=r}};function Vs(e){if(!e.date)return 1/0;if(e.format===`duration`||e.format===`elapsed`){let t=e.precision;if(t===`second`)return 1e3;if(t===`minute`)return 60*1e3}let t=Math.abs(Date.now()-e.date.getTime());return t<60*1e3?1e3:t<3600*1e3?60*1e3:3600*1e3}var Hs=new class{constructor(){this.elements=new Set,this.time=1/0,this.timer=-1}observe(e){if(this.elements.has(e))return;this.elements.add(e);let t=e.date;if(t&&t.getTime()){let t=Vs(e),n=Date.now()+t;n<this.time&&(clearTimeout(this.timer),this.timer=setTimeout(()=>this.update(),t),this.time=n)}}unobserve(e){this.elements.has(e)&&this.elements.delete(e)}update(){if(clearTimeout(this.timer),!this.elements.size)return;let e=1/0;for(let t of this.elements)e=Math.min(e,Vs(t)),t.update();this.time=Math.min(3600*1e3,e),this.timer=setTimeout(()=>this.update(),this.time),this.time+=Date.now()}},Us=class extends Ls{constructor(){super(...arguments),Cs.add(this),ws.set(this,!1),Ts.set(this,!1),Ds.set(this,this.shadowRoot?this.shadowRoot:this.attachShadow?this.attachShadow({mode:`open`}):this),Is.set(this,null)}static define(e=`relative-time`,t=customElements){return t.define(e,this),this}get timeZone(){return this.closest(`[time-zone]`)?.getAttribute(`time-zone`)||this.ownerDocument.documentElement.getAttribute(`time-zone`)||void 0}static get observedAttributes(){return[`second`,`minute`,`hour`,`weekday`,`day`,`month`,`year`,`time-zone-name`,`prefix`,`threshold`,`tense`,`precision`,`format`,`format-style`,`no-title`,`datetime`,`lang`,`title`,`aria-hidden`,`time-zone`]}get onRelativeTimeUpdated(){return I(this,Is,`f`)}set onRelativeTimeUpdated(e){I(this,Is,`f`)&&this.removeEventListener(`relative-time-updated`,I(this,Is,`f`)),Ss(this,Is,typeof e==`object`||typeof e==`function`?e:null,`f`),typeof e==`function`&&this.addEventListener(`relative-time-updated`,e)}get second(){let e=this.getAttribute(`second`);if(e===`numeric`||e===`2-digit`)return e}set second(e){this.setAttribute(`second`,e||``)}get minute(){let e=this.getAttribute(`minute`);if(e===`numeric`||e===`2-digit`)return e}set minute(e){this.setAttribute(`minute`,e||``)}get hour(){let e=this.getAttribute(`hour`);if(e===`numeric`||e===`2-digit`)return e}set hour(e){this.setAttribute(`hour`,e||``)}get weekday(){let e=this.getAttribute(`weekday`);if(e===`long`||e===`short`||e===`narrow`)return e;if(this.format===`datetime`&&e!==``)return this.formatStyle}set weekday(e){this.setAttribute(`weekday`,e||``)}get day(){let e=this.getAttribute(`day`)??`numeric`;if(e===`numeric`||e===`2-digit`)return e}set day(e){this.setAttribute(`day`,e||``)}get month(){let e=this.format,t=this.getAttribute(`month`);if(t!==``&&(t??=e===`datetime`?this.formatStyle:`short`,t===`numeric`||t===`2-digit`||t===`short`||t===`long`||t===`narrow`))return t}set month(e){this.setAttribute(`month`,e||``)}get year(){let e=this.getAttribute(`year`);if(e===`numeric`||e===`2-digit`)return e;if(!this.hasAttribute(`year`)&&new Date().getUTCFullYear()!==this.date?.getUTCFullYear())return`numeric`}set year(e){this.setAttribute(`year`,e||``)}get timeZoneName(){let e=this.getAttribute(`time-zone-name`);if(e===`long`||e===`short`||e===`shortOffset`||e===`longOffset`||e===`shortGeneric`||e===`longGeneric`)return e}set timeZoneName(e){this.setAttribute(`time-zone-name`,e||``)}get prefix(){return this.getAttribute(`prefix`)??(this.format===`datetime`?``:`on`)}set prefix(e){this.setAttribute(`prefix`,e)}get threshold(){let e=this.getAttribute(`threshold`);return e&&gs(e)?e:`P30D`}set threshold(e){this.setAttribute(`threshold`,e)}get tense(){let e=this.getAttribute(`tense`);return e===`past`?`past`:e===`future`?`future`:`auto`}set tense(e){this.setAttribute(`tense`,e)}get precision(){let e=this.getAttribute(`precision`);return hs.includes(e)?e:this.format===`micro`?`minute`:`second`}set precision(e){this.setAttribute(`precision`,e)}get format(){let e=this.getAttribute(`format`);return e===`datetime`?`datetime`:e===`relative`?`relative`:e===`duration`?`duration`:e===`micro`?`micro`:e===`elapsed`?`elapsed`:`auto`}set format(e){this.setAttribute(`format`,e)}get formatStyle(){let e=this.getAttribute(`format-style`);if(e===`long`)return`long`;if(e===`short`)return`short`;if(e===`narrow`)return`narrow`;let t=this.format;return t===`elapsed`||t===`micro`?`narrow`:t===`datetime`?`short`:`long`}set formatStyle(e){this.setAttribute(`format-style`,e)}get noTitle(){return this.hasAttribute(`no-title`)}set noTitle(e){this.toggleAttribute(`no-title`,e)}get datetime(){return this.getAttribute(`datetime`)||``}set datetime(e){this.setAttribute(`datetime`,e)}get date(){let e=Date.parse(this.datetime);return Number.isNaN(e)?null:new Date(e)}set date(e){this.datetime=e?.toISOString()||``}connectedCallback(){this.update()}disconnectedCallback(){Hs.unobserve(this)}attributeChangedCallback(e,t,n){t!==n&&(e===`title`&&Ss(this,ws,n!==null&&(this.date&&I(this,Cs,`m`,Os).call(this,this.date))!==n,`f`),!I(this,Ts,`f`)&&!(e===`title`&&I(this,ws,`f`))&&Ss(this,Ts,(async()=>{await Promise.resolve(),this.update(),Ss(this,Ts,!1,`f`)})(),`f`))}update(){let e=I(this,Ds,`f`).textContent||this.textContent||``,t=this.getAttribute(`title`)||``,n=t,r=this.date;if(typeof Intl>`u`||!Intl.DateTimeFormat||!r){I(this,Ds,`f`).textContent=e;return}let i=Date.now();I(this,ws,`f`)||(n=I(this,Cs,`m`,Os).call(this,r)||``,n&&!this.noTitle&&this.setAttribute(`title`,n));let a=ys(r,this.precision,i),o=I(this,Cs,`m`,ks).call(this,a),s=e,c=I(this,Cs,`m`,Fs).call(this,o);s=c?I(this,Cs,`m`,Ns).call(this,r):o===`duration`?I(this,Cs,`m`,As).call(this,a):o===`relative`?I(this,Cs,`m`,js).call(this,a):I(this,Cs,`m`,Ms).call(this,r),s?I(this,Cs,`m`,Ps).call(this,s):this.shadowRoot===I(this,Ds,`f`)&&this.textContent&&I(this,Cs,`m`,Ps).call(this,this.textContent),(s!==e||n!==t)&&this.dispatchEvent(new Bs(e,s,t,n)),(o===`relative`||o===`duration`)&&!c?Hs.observe(this):Hs.unobserve(this)}};ws=new WeakMap,Ts=new WeakMap,Ds=new WeakMap,Is=new WeakMap,Cs=new WeakSet,Es=function(){let e=this.closest(`[lang]`)?.getAttribute(`lang`)||this.ownerDocument.documentElement.getAttribute(`lang`);try{return new Intl.Locale(e??``).toString()}catch{return`default`}},Os=function(e){return new Intl.DateTimeFormat(I(this,Cs,`a`,Es),{day:`numeric`,month:`short`,year:`numeric`,hour:`numeric`,minute:`2-digit`,timeZoneName:`short`,timeZone:this.timeZone}).format(e)},ks=function(e){let t=this.format;if(t===`datetime`)return`datetime`;if(t===`duration`||t===`elapsed`||t===`micro`)return`duration`;if((t===`auto`||t===`relative`)&&typeof Intl<`u`&&Intl.RelativeTimeFormat){let t=this.tense;if(t===`past`||t===`future`||_s.compare(e,this.threshold)===1)return`relative`}return`datetime`},As=function(e){let t=I(this,Cs,`a`,Es),n=this.format,r=this.formatStyle,i=this.tense,a=Rs;n===`micro`?(e=bs(e),a=zs,e.months===0&&(this.tense===`past`&&e.sign!==-1||this.tense===`future`&&e.sign!==1)&&(e=zs)):(i===`past`&&e.sign!==-1||i===`future`&&e.sign!==1)&&(e=a);let o=`${this.precision}sDisplay`;return e.blank?a.toLocaleString(t,{style:r,[o]:`always`}):e.abs().toLocaleString(t,{style:r})},js=function(e){let t=new Intl.RelativeTimeFormat(I(this,Cs,`a`,Es),{numeric:`auto`,style:this.formatStyle}),n=this.tense;n===`future`&&e.sign!==1&&(e=Rs),n===`past`&&e.sign!==-1&&(e=Rs);let[r,i]=xs(e);return i===`second`&&r<10?t.format(0,this.precision===`millisecond`?`second`:this.precision):t.format(r,i)},Ms=function(e){let t=new Intl.DateTimeFormat(I(this,Cs,`a`,Es),{second:this.second,minute:this.minute,hour:this.hour,weekday:this.weekday,day:this.day,month:this.month,year:this.year,timeZoneName:this.timeZoneName,timeZone:this.timeZone});return`${this.prefix} ${t.format(e)}`.trim()},Ns=function(e){return new Intl.DateTimeFormat(I(this,Cs,`a`,Es),{day:`numeric`,month:`short`,year:`numeric`,hour:`numeric`,minute:`2-digit`,timeZoneName:`short`,timeZone:this.timeZone}).format(e)},Ps=function(e){if(this.hasAttribute(`aria-hidden`)&&this.getAttribute(`aria-hidden`)===`true`){let t=document.createElement(`span`);t.setAttribute(`aria-hidden`,`true`),t.textContent=e,I(this,Ds,`f`).replaceChildren(t)}else I(this,Ds,`f`).textContent=e},Fs=function(e){return e===`duration`?!1:this.ownerDocument.documentElement.getAttribute(`data-prefers-absolute-time`)===`true`||this.ownerDocument.body?.getAttribute(`data-prefers-absolute-time`)===`true`};var Ws=typeof globalThis<`u`?globalThis:window;try{Ws.RelativeTimeElement=Us.define()}catch(e){if(!(Ws.DOMException&&e instanceof DOMException&&e.name===`NotSupportedError`)&&!(e instanceof ReferenceError))throw e}var Gs=class extends Uo{static get styles(){return[...super.styles,p`
        .input-group__input {
          font-family: var(--c-font-mono);
          font-size: 0.9em;
        }
      `]}constructor(){super(),this.autocorrect=!1}firstUpdated(e){super.firstUpdated(e),this._inputNode?.setAttribute(`autocapitalize`,`off`)}};customElements.get(`craft-input-handle`)||customElements.define(`craft-input-handle`,Gs),Je();var Ks=class extends Vo{static get styles(){return[...super.styles,ya,p`
        .input-group__container {
          position: relative;
        }

        .input-group__suffix {
          position: absolute;
          inset-inline-end: var(--c-input-spacing-inline);
          inset-block-start: 50%;
          transform: translateY(calc(-50%));
        }
      `]}constructor(){super(),this._visible=!1,this.reveal=()=>{this._visible=!this._visible,this.type=this._visible?`text`:`password`},this.renderSuffix=()=>d`
      <craft-button
        type="button"
        icon
        size="small"
        variant="plain"
        @click="${this.reveal}"
        appearance="plain"
      >
        <span class="icon"
          >${this._visible?d`<craft-icon name="eye-slash"></craft-icon>`:d`<craft-icon name="eye"></craft-icon>`}
        </span>
      </craft-button>
    `,this.type=`password`}get slots(){return{...super.slots,suffix:()=>({template:this.renderSuffix()})}}};l([b()],Ks.prototype,`_visible`,void 0),customElements.get(`craft-input-password`)||customElements.define(`craft-input-password`,Ks);var qs=p`
  :host {
    display: contents;
  }

  .chip {
    display: inline-flex;
    min-height: var(--c-chip-height, var(--c-size-control-sm));
    min-width: auto;
    border-radius: var(--c-chip-radius, var(--c-radius-md));
    padding-inline: var(--c-chip-spacing-inline, var(--c-spacing-md));
    padding-block: var(--c-chip-spacing-block, var(--c-spacing-sm));
    align-items: start;
    box-shadow: var(--c-chip-shadow, var(--c-shadow-sm));

    /* colorable styles */
    color: var(--c-color-on-quiet, var(--c-color-neutral-on-quiet));
    border-width: var(--c-chip-border-width, 1px);
    border-style: var(--c-chip-border-style, solid);
    border-color: var(
      --c-color-border-quiet,
      var(--c-color-neutral-border-quiet)
    );
    background-color: var(--c-color-fill-quiet, var(--c-surface-raised));
  }

  .chip[appearance='plain'],
  .chip--plain {
    padding-block: 0;
    padding-inline: 0;
    border-color: transparent;
    background-color: transparent;
    box-shadow: none;
  }

  .chip[size='small'],
  .chip--small {
    padding-block: 0;
    min-height: var(--c-size-control-sm);
  }

  chip[size='medium'],
  .chip--medium {
    padding-block: 0;
    min-height: var(--c-size-control-md);
  }

  .chip__prefix,
  .chip__body,
  .chip__suffix {
    display: inline-flex;
    flex-direction: column;
    align-self: center;
  }

  .chip__body {
    flex: 1 1 auto;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .chip__prefix {
    padding-inline-end: var(--c-spacing-md);
  }

  .chip__suffix {
    padding-inline-start: var(--c-spacing-md);
  }

  :host(:not([variant='plain'])) .chip__suffix {
    margin-inline-end: calc(var(--c-spacing-sm) * -1);
  }
`,Js=class extends v{constructor(...e){super(...e),this.size=``,this.variant=``,this.icon=null}renderPrefix(){return d`<div class="chip__prefix" part="prefix">
      <slot name="prefix">
        ${this.icon?d`<craft-icon name="${this.icon}"></craft-icon>`:_}
      </slot>
    </div>`}render(){let e=!!this.querySelector(`[slot="prefix"]`)||this.icon,t=!!this.querySelector(`[slot="suffix"]`);return d`
      <div
        part="chip"
        class="${T({chip:!0,"chip--small":this.size===`small`,"chip--medium":this.size===`medium`,"chip--large":this.size===`large`,"chip--plain":this.variant===`plain`})}"
      >
        ${e?this.renderPrefix():_}
        <div class="chip__body">
          <slot></slot>
        </div>
        ${t?d`<div class="chip__suffix" part="suffix">
              <slot name="suffix"></slot>
            </div>`:_}
      </div>
    `}};Js.styles=[qs],l([y()],Js.prototype,`size`,void 0),l([y()],Js.prototype,`variant`,void 0),l([y()],Js.prototype,`icon`,void 0),customElements.get(`craft-chip`)||customElements.define(`craft-chip`,Js);var Ys=p`
  :host {
    display: inline-flex;
    --size: var(--c-size-icon-xs);
  }

  .status {
    display: inline-flex;
    width: var(--size);
    aspect-ratio: 1;
    border-radius: var(--c-radius-full);
    border: 1px solid transparent;
  }

  .status--live {
    background-color: var(--c-status-live-fill);
    border-color: var(--c-status-live-border);
  }

  .status--enabled {
    background-color: var(--c-status-enabled-fill);
    border-color: var(--c-status-enabled-border);
  }

  .status--pending {
    background-color: var(--c-status-pending-fill);
    border-color: var(--c-status-pending-border);
  }

  .status--expired {
    background-color: var(--c-status-expired-fill);
    border-color: var(--c-status-expired-border);
  }

  .status--disabled {
    background-color: var(--c-status-disabled-fill);
    border: 1px solid var(--c-status-disabled-border);
  }
`,Xs=class extends v{constructor(...e){super(...e),this.label=null,this.status=null}getLabel(){return!this.label&&this.status?`Status: ${this.status}`:this.label}render(){return d`
      <span
        class="${T({status:!0,"status--live":this.status===`live`,"status--enabled":this.status===`enabled`,"status--pending":this.status===`pending`,"status--expired":this.status===`expired`,"status--disabled":this.status===`disabled`})}"
        role="img"
        aria-label="${this.getLabel()}"
      ></span>
    `}};Xs.styles=[Ys],l([y()],Xs.prototype,`label`,void 0),l([y()],Xs.prototype,`status`,void 0),customElements.get(`craft-status`)||customElements.define(`craft-status`,Xs);var Zs=new Map;function Qs(e){var t=Zs.get(e);t&&t.destroy()}function $s(e){var t=Zs.get(e);t&&t.update()}var ec=null;typeof window>`u`?((ec=function(e){return e}).destroy=function(e){return e},ec.update=function(e){return e}):((ec=function(e,t){return e&&Array.prototype.forEach.call(e.length?e:[e],function(e){return function(e){if(e&&e.nodeName&&e.nodeName===`TEXTAREA`&&!Zs.has(e)){var t,n=null,r=window.getComputedStyle(e),i=(t=e.value,function(){o({testForHeightReduction:t===``||!e.value.startsWith(t),restoreTextAlign:null}),t=e.value}),a=function(t){e.removeEventListener(`autosize:destroy`,a),e.removeEventListener(`autosize:update`,s),e.removeEventListener(`input`,i),window.removeEventListener(`resize`,s),Object.keys(t).forEach(function(n){return e.style[n]=t[n]}),Zs.delete(e)}.bind(e,{height:e.style.height,resize:e.style.resize,textAlign:e.style.textAlign,overflowY:e.style.overflowY,overflowX:e.style.overflowX,wordWrap:e.style.wordWrap});e.addEventListener(`autosize:destroy`,a),e.addEventListener(`autosize:update`,s),e.addEventListener(`input`,i),window.addEventListener(`resize`,s),e.style.overflowX=`hidden`,e.style.wordWrap=`break-word`,Zs.set(e,{destroy:a,update:s}),s()}function o(t){var i,a,s=t.restoreTextAlign,c=s===void 0?null:s,l=t.testForHeightReduction,u=l===void 0||l,d=r.overflowY;if(e.scrollHeight!==0&&(r.resize===`vertical`?e.style.resize=`none`:r.resize===`both`&&(e.style.resize=`horizontal`),u&&(i=function(e){for(var t=[];e&&e.parentNode&&e.parentNode instanceof Element;)e.parentNode.scrollTop&&t.push([e.parentNode,e.parentNode.scrollTop]),e=e.parentNode;return function(){return t.forEach(function(e){var t=e[0],n=e[1];t.style.scrollBehavior=`auto`,t.scrollTop=n,t.style.scrollBehavior=null})}}(e),e.style.height=``),a=r.boxSizing===`content-box`?e.scrollHeight-(parseFloat(r.paddingTop)+parseFloat(r.paddingBottom)):e.scrollHeight+parseFloat(r.borderTopWidth)+parseFloat(r.borderBottomWidth),r.maxHeight!==`none`&&a>parseFloat(r.maxHeight)?(r.overflowY===`hidden`&&(e.style.overflow=`scroll`),a=parseFloat(r.maxHeight)):r.overflowY!==`hidden`&&(e.style.overflow=`hidden`),e.style.height=a+`px`,c&&(e.style.textAlign=c),i&&i(),n!==a&&(e.dispatchEvent(new Event(`autosize:resized`,{bubbles:!0})),n=a),d!==r.overflow&&!c)){var f=r.textAlign;r.overflow===`hidden`&&(e.style.textAlign=f===`start`?`end`:`start`),o({restoreTextAlign:f,testForHeightReduction:!0})}}function s(){o({testForHeightReduction:!0,restoreTextAlign:null})}}(e)}),e}).destroy=function(e){return e&&Array.prototype.forEach.call(e.length?e:[e],Qs),e},ec.update=function(e){return e&&Array.prototype.forEach.call(e.length?e:[e],$s),e});var tc=ec,nc=class extends jo{get _inputNode(){return Array.from(this.children).find(e=>e.slot===`input`)}},rc=class extends Fo(nc){static get properties(){return{maxRows:{type:Number,attribute:`max-rows`},rows:{type:Number,reflect:!0},readOnly:{type:Boolean,attribute:`readonly`,reflect:!0},placeholder:{type:String,reflect:!0}}}get slots(){return{...super.slots,input:()=>{let e=document.createElement(`textarea`);return e.style.resize!==void 0&&(e.style.resize=`none`),e}}}constructor(){super(),this.rows=2,this.maxRows=6,this.readOnly=!1,this.placeholder=``}connectedCallback(){super.connectedCallback(),this.__initializeAutoresize(),this.__intersectionObserver=new IntersectionObserver(()=>this.resizeTextarea()),this.__intersectionObserver.observe(this)}updated(e){if(super.updated(e),e.has(`name`)&&(this._inputNode.name=this.name),e.has(`autocomplete`)&&(this._inputNode.autocomplete=this.autocomplete),e.has(`disabled`)&&(this._inputNode.disabled=this.disabled,this.validate()),e.has(`rows`)){let e=this._inputNode;e&&(e.rows=this.rows)}if(e.has(`readOnly`)){let e=this._inputNode;e&&(e.readOnly=this.readOnly)}if(e.has(`placeholder`)){let e=this._inputNode;e&&(e.placeholder=this.placeholder)}e.has(`modelValue`)&&this.resizeTextarea(),(e.has(`maxRows`)||e.has(`rows`))&&this.setTextareaMaxHeight()}disconnectedCallback(){super.disconnectedCallback(),tc.destroy(this._inputNode)}setTextareaMaxHeight(){let{value:e}=this._inputNode;this._inputNode.value=``,this.resizeTextarea();let t=window.getComputedStyle(this._inputNode,null),n=parseFloat(t.lineHeight)||parseFloat(t.height)/this.rows,r=parseFloat(t.paddingTop)+parseFloat(t.paddingBottom),i=parseFloat(t.borderTopWidth)+parseFloat(t.borderBottomWidth),a=t.boxSizing===`border-box`?r+i:0;this._inputNode.style.maxHeight=`${n*this.maxRows+a}px`,this._inputNode.value=e,this.resizeTextarea()}static get styles(){return[...super.styles,p`
        .input-group__container > .input-group__input ::slotted(.form-control) {
          box-sizing: content-box;
          overflow-x: hidden; /* for FF adds height to the TextArea to reserve place for scroll-bars */
        }

        /* Workaround https://bugzilla.mozilla.org/show_bug.cgi?id=1739079 */
        :host([disabled]) ::slotted(textarea) {
          user-select: none;
        }
      `]}get updateComplete(){return this.__textareaUpdateComplete?Promise.all([this.__textareaUpdateComplete,super.updateComplete]):super.updateComplete}resizeTextarea(){tc.update(this._inputNode)}__initializeAutoresize(){this.__shady_native_contains?this.__textareaUpdateComplete=this.__waitForTextareaRenderedInRealDOM().then(()=>{this.__startAutoresize(),this.__textareaUpdateComplete=null}):this.__startAutoresize()}async __waitForTextareaRenderedInRealDOM(){let e=3;for(;e!==0&&!this.__shady_native_contains(this._inputNode);)await new Promise(e=>setTimeout(e)),--e}__startAutoresize(){tc(this._inputNode),this.setTextareaMaxHeight()}},ic=p`
  :host(:not([label-sr-only])) .form-field__group-one {
    margin-bottom: var(--c-spacing-sm);
  }

  :host([monospace]) ::slotted([slot='input']) {
    font-family: var(--c-font-mono, monospace) !important;
    font-size: var(--c-text-sm);
  }

  ::slotted(label) {
    font-weight: bold;
  }

  ::slotted([slot='input']) {
    padding-block: var(--c-spacing-md);
    line-height: var(--leading-normal);
  }
`,ac=class extends rc{constructor(...e){super(...e),this.monospace=!1}static get styles(){return[...super.styles,ya,ic]}};l([y({type:Boolean,reflect:!0})],ac.prototype,`monospace`,void 0),customElements.get(`craft-textarea`)||customElements.define(`craft-textarea`,ac);var oc=p`
  :host {
    display: flex;
    gap: var(--c-spacing-1px);
  }

  ::slotted(craft-button),
  ::slotted(button) {
    border-radius: 0;
  }

  ::slotted(craft-button:first-child) {
    border-start-start-radius: var(--c-radius-sm);
    border-end-start-radius: var(--c-radius-sm);
  }

  ::slotted(craft-button:last-child) {
    border-start-end-radius: var(--c-radius-sm);
    border-end-end-radius: var(--c-radius-sm);
  }
`,sc=class extends v{render(){return d`<slot></slot>`}};sc.styles=[oc],customElements.get(`craft-button-group`)||customElements.define(`craft-button-group`,sc);var cc=class extends jo{static get properties(){return{autocomplete:{type:String}}}constructor(){super(),this.autocomplete=void 0}get _inputNode(){return Array.from(this.children).find(e=>e.slot===`input`)}},lc=class extends cc{get operationMode(){return`select`}connectedCallback(){super.connectedCallback(),this._inputNode.addEventListener(`change`,this._proxyChangeEvent),this.__selectObserver=new MutationObserver(()=>{this._syncValueUpwards(),this._calculateValues({source:`model`})}),this.__selectObserver.observe(this._inputNode,{attributes:!0,childList:!0,subtree:!0})}updated(e){super.updated(e),e.has(`disabled`)&&(this._inputNode.disabled=this.disabled,this.validate()),e.has(`name`)&&(this._inputNode.name=this.name),e.has(`autocomplete`)&&(this._inputNode.autocomplete=this.autocomplete)}disconnectedCallback(){super.disconnectedCallback(),this._inputNode.removeEventListener(`change`,this._proxyChangeEvent),this.__selectObserver?.disconnect()}formatter(e){let t=Array.from(this._inputNode.options).find(t=>t.value===e);return t?t.text:``}_reflectBackFormattedValueToUser(){this._reflectBackOn()&&(this.value=this.modelValue===void 0?``:this.modelValue)}_proxyChangeEvent(){this.dispatchEvent(new CustomEvent(`user-input-changed`,{bubbles:!0,composed:!0}))}},uc=p`
  ${va}

  :host {
    width: 100%;
  }

  :host([small]) .input-group__input {
    --c-input-height: calc(var(--c-size-control-sm) - 2px);
  }

  ::slotted(.form-control) {
    width: 100%;
    height: 100%;
    appearance: none;
    border: 0;
    min-height: none;
    padding-inline: var(--c-input-spacing-inline)
      calc(var(--c-input-spacing-inline) * 1.5 + 1em);
    border-radius: var(--c-input-radius);
  }

  .input-group__input {
    ${_a}
    padding-inline: 0;
    position: relative;
    min-height: calc(var(--c-input-height, var(--c-size-control-md)) - 2px);
  }

  .indicator {
    position: absolute;
    inset-block-start: 50%;
    inset-inline-end: var(--c-input-spacing-inline);
    transform: translateY(-50%);
    width: 1em;
    height: 1em;
  }
`,dc=class extends lc{constructor(...e){super(...e),this.small=!1}static get styles(){return[...super.styles,uc]}_inputGroupInputTemplate(){return d`
      <div class="input-group__input">
        <slot name="input"></slot>
        <craft-icon
          class="indicator"
          name="chevron-down"
          style="font-size: 0.8em"
        ></craft-icon>
      </div>
    `}};l([y({reflect:!0,type:Boolean})],dc.prototype,`small`,void 0),customElements.get(`craft-select`)||customElements.define(`craft-select`,dc);var fc=class extends Po(v){static get properties(){return{tabIndex:{type:Number,reflect:!0,attribute:`tabindex`}}}constructor(){super(),this.tabIndex=0}connectedCallback(){super.connectedCallback(),this.setAttribute(`role`,`listbox`)}createRenderRoot(){return this}};function pc(e,t){Array.from(e.childNodes).forEach(e=>{e.hasAttribute&&e.hasAttribute(`slot`)||t.appendChild(e)})}var mc=Er(e=>class extends wo(po(Io(Vr(No(e))))){static get properties(){return{orientation:String,selectionFollowsFocus:{type:Boolean,attribute:`selection-follows-focus`},rotateKeyboardNavigation:{type:Boolean,attribute:`rotate-keyboard-navigation`},hasNoDefaultSelected:{type:Boolean,reflect:!0,attribute:`has-no-default-selected`},_noTypeAhead:{type:Boolean}}}static get styles(){return[...super.styles||[],p`
          :host {
            display: block;
          }

          :host([hidden]) {
            display: none;
          }

          :host([disabled]) {
            color: #adadad;
          }

          :host([orientation='horizontal']) ::slotted([role='listbox']) {
            display: flex;
          }
        `]}_inputGroupInputTemplate(){return d`
        <div class="input-group__input">
          <slot name="input"></slot>
          <slot id="options-outlet"></slot>
        </div>
      `}static get scopedElements(){return{...super.scopedElements,"lion-options":fc}}get slots(){return{...super.slots,input:()=>{let e=this.createScopedElement(`lion-options`);return e.setAttribute(`data-tag-name`,`lion-options`),e.registrationTarget=this,e}}}get _inputNode(){return this.querySelector(`[slot="input"]`)}get _listboxNode(){return this._inputNode}get _listboxActiveDescendantNode(){return this._listboxNode.querySelector(`#${this._listboxActiveDescendant}`)}get _listboxSlot(){return this.shadowRoot.querySelector(`slot[name=input]`)}get _scrollTargetNode(){return this._listboxNode}get _activeDescendantOwnerNode(){return this._listboxNode}get activeIndex(){return this.formElements.findIndex(e=>e.active===!0)}set activeIndex(e){if(this.formElements[e]){let t=this.formElements[e];this.__setChildActive(t)}else this.__setChildActive(null)}get checkedIndex(){let e=this.formElements;return this.multipleChoice?e.filter(e=>e.checked).map(t=>e.indexOf(t)):e.indexOf(e.find(e=>e.checked))}set checkedIndex(e){this.setCheckedIndex(e)}constructor(){super(),this.hasNoDefaultSelected=!1,this.orientation=`vertical`,this.rotateKeyboardNavigation=!1,this.selectionFollowsFocus=!1,this._noTypeAhead=!1,this._typeAheadTimeout=1e3,this._listboxActiveDescendant=null,this.__hasInitialSelectedFormElement=!1,this._repropagationRole=`choice-group`,this._listboxReceivesNoFocus=!1,this._oldModelValue=void 0,this._listboxOnKeyDown=this._listboxOnKeyDown.bind(this),this._listboxOnClick=this._listboxOnClick.bind(this),this._listboxOnKeyUp=this._listboxOnKeyUp.bind(this),this._onChildActiveChanged=this._onChildActiveChanged.bind(this),this.__proxyChildModelValueChanged=this.__proxyChildModelValueChanged.bind(this),this.__preventScrollingWithArrowKeys=this.__preventScrollingWithArrowKeys.bind(this),this.__typedChars=[]}connectedCallback(){this._listboxNode&&(this._listboxNode.registrationTarget=this),super.connectedCallback(),this._setupListboxNode(),this.__setupEventListeners(),this.registrationComplete.then(()=>{this.__initInteractionStates()})}firstUpdated(e){super.firstUpdated(e),this.__moveOptionsToListboxNode(),this.registrationComplete.then(()=>{this._initialModelValue=this.modelValue}),new MutationObserver(()=>{this._onListboxContentChanged()}).observe(this._listboxNode,{childList:!0})}updated(e){super.updated(e),e.has(`disabled`)&&(this.disabled?this.__requestOptionsToBeDisabled():this.__retractRequestOptionsToBeDisabled())}disconnectedCallback(){super.disconnectedCallback(),this._teardownListboxNode(),this.__teardownEventListeners()}setCheckedIndex(e){if(this.multipleChoice&&Array.isArray(e)){this._uncheckChildren(this.formElements.filter(t=>t===e)),e.forEach(e=>{this.formElements[e]&&(this.formElements[e].checked=!this.formElements[e].checked)});return}typeof e==`number`&&(e===-1&&this._uncheckChildren(),this.formElements[e]&&(this.formElements[e].disabled?this._uncheckChildren():this.multipleChoice?this.formElements[e].checked=!this.formElements[e].checked:this.formElements[e].checked=!0))}addFormElement(e,t){super.addFormElement(e,t),e.id=e.id||`${this.localName}-option-${Wr()}`,this.disabled&&e.makeRequestToBeDisabled(),this.__setAttributeForAllFormElements(`aria-setsize`,this.formElements.length),this.formElements.forEach((e,t)=>{e.setAttribute(`aria-posinset`,t+1)}),this.__proxyChildModelValueChanged({target:e}),this.resetInteractionState()}resetInteractionState(){super.resetInteractionState(),this.submitted=!1}reset(){this.modelValue=this._initialModelValue,this.activeIndex=-1,this.resetInteractionState()}clear(){super.clear(),this.setCheckedIndex(-1),this.resetInteractionState()}_handleTypeAhead(e,{setAsChecked:t}){let{key:n,code:r}=e;if(r.startsWith(`Key`)||r.startsWith(`Digit`)||r.startsWith(`Numpad`)){e.preventDefault(),this.__typedChars.push(n);let r=this.__typedChars.join(``),i=this.formElements.findIndex(e=>e.modelValue.value.toLowerCase().startsWith(r));i>=0&&(t&&this.setCheckedIndex(i),this.activeIndex=i),this.__pendingTypeAheadTimeout&&window.clearTimeout(this.__pendingTypeAheadTimeout),this.__pendingTypeAheadTimeout=setTimeout(()=>{this.__typedChars=[]},this._typeAheadTimeout)}}_getCheckedElements(){return this.formElements.filter(e=>e.checked)}_setupListboxNode(){this._listboxNode?this.__setupListboxNodeInteractions():this._listboxSlot&&this._listboxSlot.addEventListener(`slotchange`,()=>{this.__setupListboxNodeInteractions()})}_onListboxContentChanged(){}_teardownListboxNode(){this._listboxNode&&(this._listboxNode.removeEventListener(`keydown`,this._listboxOnKeyDown),this._listboxNode.removeEventListener(`click`,this._listboxOnClick),this._listboxNode.removeEventListener(`keyup`,this._listboxOnKeyUp))}_getNextEnabledOption(e,t=1){return this.__getEnabledOption(e,t)}_getPreviousEnabledOption(e,t=-1){return this.__getEnabledOption(e,t)}_onChildActiveChanged({target:e}){e.active===!0&&this.__setChildActive(e)}_listboxOnKeyDown(e){if(this.disabled)return;this._isHandlingUserInput=!0,setTimeout(()=>{this._isHandlingUserInput=!1});let{key:t}=e;switch(t){case` `:case`Enter`:if(t===` `&&this._listboxReceivesNoFocus||(t===` `&&e.preventDefault(),!this.formElements[this.activeIndex])||this.formElements[this.activeIndex].disabled)return;this.formElements[this.activeIndex].href&&this.formElements[this.activeIndex].click(),this.setCheckedIndex(this.activeIndex);break;case`ArrowUp`:e.preventDefault(),this.orientation===`vertical`&&(this.activeIndex=this._getPreviousEnabledOption(this.activeIndex));break;case`ArrowLeft`:if(this._listboxReceivesNoFocus)return;e.preventDefault(),this.orientation===`horizontal`&&(this.activeIndex=this._getPreviousEnabledOption(this.activeIndex));break;case`ArrowDown`:e.preventDefault(),this.orientation===`vertical`&&(this.activeIndex=this._getNextEnabledOption(this.activeIndex));break;case`ArrowRight`:if(this._listboxReceivesNoFocus)return;e.preventDefault(),this.orientation===`horizontal`&&(this.activeIndex=this._getNextEnabledOption(this.activeIndex));break;case`Home`:if(this._listboxReceivesNoFocus)return;e.preventDefault(),this.activeIndex=this._getNextEnabledOption(0,0);break;case`End`:if(this._listboxReceivesNoFocus)return;e.preventDefault(),this.activeIndex=this._getPreviousEnabledOption(this.formElements.length-1,0);break;default:this._noTypeAhead||this._handleTypeAhead(e,{setAsChecked:this.selectionFollowsFocus&&!this.multipleChoice})}[`ArrowUp`,`ArrowDown`,`ArrowLeft`,`ArrowRight`,`Home`,`End`].includes(t)&&this.selectionFollowsFocus&&!this.multipleChoice&&this.setCheckedIndex(this.activeIndex)}_listboxOnClick(e){}_listboxOnKeyUp(e){if(this.disabled)return;this._isHandlingUserInput=!0,setTimeout(()=>{this._isHandlingUserInput=!1});let{key:t}=e;switch(t){case`ArrowUp`:case`ArrowDown`:case`Home`:case`End`:case`Enter`:e.preventDefault()}}_onLabelClick(){this._listboxNode.focus()}_scrollIntoView(e,t){e.scrollIntoView({behavior:`smooth`,block:`nearest`})}__setupEventListeners(){this._listboxNode.addEventListener(`active-changed`,this._onChildActiveChanged),this._listboxNode.addEventListener(`model-value-changed`,this.__proxyChildModelValueChanged)}__teardownEventListeners(){this._listboxNode.removeEventListener(`active-changed`,this._onChildActiveChanged),this._listboxNode.removeEventListener(`model-value-changed`,this.__proxyChildModelValueChanged)}__setChildActive(e){if(this.formElements.forEach(t=>{t.active=e===t}),!e){this._activeDescendantOwnerNode.removeAttribute(`aria-activedescendant`);return}this._activeDescendantOwnerNode.setAttribute(`aria-activedescendant`,e.id),this._scrollIntoView(e,this._scrollTargetNode)}_uncheckChildren(e=[]){let t=Array.isArray(e)?e:[e];this.formElements.forEach(e=>{t.includes(e)||(e.checked=!1)})}__onChildCheckedChanged(e){let{target:t}=e;e.stopPropagation&&e.stopPropagation(),t.checked&&!this.multipleChoice&&this._uncheckChildren(t)}__setAttributeForAllFormElements(e,t){this.formElements.forEach(n=>{n.setAttribute(e,t)})}__proxyChildModelValueChanged(e){e.stopPropagation&&e.stopPropagation(),this.__onChildCheckedChanged(e),this.requestUpdate(`modelValue`,this._oldModelValue),e.detail&&e.detail.formPath&&this.dispatchEvent(new CustomEvent(`model-value-changed`,{detail:{formPath:e.detail.formPath,isTriggeredByUser:e.detail.isTriggeredByUser||this._isHandlingUserInput,element:e.target}})),this._oldModelValue=this.modelValue}__getEnabledOption(e,t){let n=e=>t===1?e<this.formElements.length:e>=0;for(let r=e+t;n(r);r+=t)if(this.formElements[r]&&!this.formElements[r].hasAttribute(`aria-hidden`))return r;if(this.rotateKeyboardNavigation){let e=t===-1?this.formElements.length-1:0;for(let r=e;n(r);r+=t)if(this.formElements[r]&&!this.formElements[r].hasAttribute(`aria-hidden`))return r}return e}__moveOptionsToListboxNode(){let e=this.shadowRoot.getElementById(`options-outlet`);e&&(pc(this,this._listboxNode),e.addEventListener(`slotchange`,()=>{pc(this,this._listboxNode)}))}__preventScrollingWithArrowKeys(e){if(this.disabled)return;let{key:t}=e;switch(t){case`ArrowUp`:case`ArrowDown`:case`Home`:case`End`:e.preventDefault()}}__setupListboxNodeInteractions(){this._listboxNode.setAttribute(`role`,`listbox`),this._listboxNode.setAttribute(`aria-orientation`,this.orientation),this._listboxNode.setAttribute(`aria-multiselectable`,`${this.multipleChoice}`),this._listboxNode.setAttribute(`tabindex`,`0`),this._listboxNode.addEventListener(`click`,this._listboxOnClick),this._listboxNode.addEventListener(`keyup`,this._listboxOnKeyUp),this._listboxNode.addEventListener(`keydown`,this._listboxOnKeyDown),this._scrollTargetNode.addEventListener(`keydown`,this.__preventScrollingWithArrowKeys)}__requestOptionsToBeDisabled(){this.formElements.forEach(e=>{e.makeRequestToBeDisabled&&e.makeRequestToBeDisabled()})}__retractRequestOptionsToBeDisabled(){this.formElements.forEach(e=>{e.retractRequestToBeDisabled&&e.retractRequestToBeDisabled()})}__initInteractionStates(){this.initInteractionState()}}),hc=class extends mc(Ca(Ao(Oo(v)))){get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,focused:this.focused}}get _focusableNode(){return this._inputNode}},gc=class extends Dr(Ro(Co(Vr(v)))){static get properties(){return{active:{type:Boolean,reflect:!0}}}static get styles(){return[p`
        :host {
          display: block;
          background-color: white;
          padding: 4px;
          cursor: default;
        }

        :host([hidden]) {
          display: none;
        }

        :host(:hover) {
          background-color: #eee;
        }
        :host([active]) {
          background-color: #ddd;
        }

        :host([checked]) {
          background-color: #bde4ff;
        }

        :host([disabled]) {
          color: #adadad;
        }
      `]}get slots(){return{}}constructor(){super(),this.active=!1,this.__onClick=this.__onClick.bind(this),this.__registerEventListeners()}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`active`&&this.active!==t&&this.dispatchEvent(new Event(`active-changed`,{bubbles:!0}))}updated(e){super.updated(e),e.has(`checked`)&&this.setAttribute(`aria-selected`,`${this.checked}`),e.has(`disabled`)&&this.setAttribute(`aria-disabled`,`${this.disabled}`)}render(){return d`
      <div class="choice-field__label">
        <slot></slot>
      </div>
    `}connectedCallback(){super.connectedCallback(),this.setAttribute(`role`,`option`)}__registerEventListeners(){this.addEventListener(`click`,this.__onClick)}__unRegisterEventListeners(){this.removeEventListener(`click`,this.__onClick)}__onClick(){if(this.disabled)return;let e=this._parentFormGroup;this._isHandlingUserInput=!0,e&&e.multipleChoice?(this.checked=!this.checked,this.active=!this.active):(this.checked=!0,this.active=!0),this._isHandlingUserInput=!1}},_c=p`
  :host([checked]) {
    background-color: var(--c-color-neutral-fill-loud);
    color: var(--c-color-neutral-on-loud);
  }

  :host {
    --c-option-wide-threshold: 640;
    padding-inline: var(--c-spacing-md);
    padding-block: var(--c-spacing-sm);
    font: inherit;
    border-radius: var(--c-radius-sm);
  }

  :host(:hover) {
    background-color: var(--c-color-neutral-fill-normal);
    color: var(--c-color-neutral-on-normal);
  }

  :host([active]) {
    background-color: var(--c-color-neutral-fill-loud);
    color: var(--c-color-neutral-on-loud);
  }

  :host([checked]) {
    background-color: var(--c-color-neutral-fill-loud);
    color: var(--c-color-neutral-on-loud);
  }

  :host([disabled]) {
    color: var(--c-color-neutral-on-normal);
  }

  .hint {
    color: color-mix(in srgb, currentColor, transparent 25%);
    align-self: end;
    font-size: 0.8em;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  :host([active]) .hint {
    color: var(--c-color-neutral-on-loud);
  }

  .choice-field__label {
    display: grid;
    max-width: 100%;
  }

  :host([wide]) .choice-field__label {
    align-items: baseline;
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
`,vc=new WeakMap,yc=class extends gc{static get styles(){return[...gc.styles,_c]}constructor(){super(),this.hint=null,c(this,vc,640),i(vc,this,parseInt(getComputedStyle(this).getPropertyValue(`--c-option-wide-threshold`)||`640`,10))}connectedCallback(){super.connectedCallback();let e=this.getBoundingClientRect().width??0;this.toggleAttribute(`wide`,e>=s(vc,this))}render(){return d`
      <div class="choice-field__label">
        <slot></slot>
        ${this.hint?d`<span class="hint">${this.hint}</span>`:_}
        <slot name="suffix"></slot>
      </div>
    `}};l([y()],yc.prototype,`hint`,void 0),customElements.get(`craft-option`)||customElements.define(`craft-option`,yc);var bc=`@layer wa-utilities {
  :host([size='small']),
  .wa-size-s {
    font-size: var(--wa-font-size-s);
  }

  :host([size='medium']),
  .wa-size-m {
    font-size: var(--wa-font-size-m);
  }

  :host([size='large']),
  .wa-size-l {
    font-size: var(--wa-font-size-l);
  }
}
`,xc=class extends Event{constructor(e){super(`wa-select`,{bubbles:!0,cancelable:!0,composed:!0}),this.detail=e}};function*Sc(e=document.activeElement){e!=null&&(yield e,`shadowRoot`in e&&e.shadowRoot&&e.shadowRoot.mode!==`closed`&&(yield*Sc(e.shadowRoot.activeElement)))}var Cc=`:host {
  --show-duration: 50ms;
  --hide-duration: 50ms;
  display: contents;
}

#menu {
  display: flex;
  flex-direction: column;
  width: max-content;
  margin: 0;
  padding: 0.25em;
  border: var(--wa-border-style) var(--wa-border-width-s) var(--wa-color-surface-border);
  border-radius: var(--wa-border-radius-m);
  background-color: var(--wa-color-surface-raised);
  box-shadow: var(--wa-shadow-m);
  color: var(--wa-color-text-normal);
  text-align: start;
  user-select: none;
  overflow: auto;
  max-width: var(--auto-size-available-width) !important;
  max-height: var(--auto-size-available-height) !important;

  &.show {
    animation: show var(--show-duration) ease;
  }

  &.hide {
    animation: show var(--hide-duration) ease reverse;
  }

  ::slotted(h1),
  ::slotted(h2),
  ::slotted(h3),
  ::slotted(h4),
  ::slotted(h5),
  ::slotted(h6) {
    display: block !important;
    margin: 0.25em 0 !important;
    padding: 0.25em 0.75em !important;
    color: var(--wa-color-text-quiet) !important;
    font-family: var(--wa-font-family-body) !important;
    font-weight: var(--wa-font-weight-semibold) !important;
    font-size: var(--wa-font-size-smaller) !important;
  }

  ::slotted(wa-divider) {
    --spacing: 0.25em; /* Component-specific, left as-is */
  }
}

wa-popup[data-current-placement^='top'] #menu {
  transform-origin: bottom;
}

wa-popup[data-current-placement^='bottom'] #menu {
  transform-origin: top;
}

wa-popup[data-current-placement^='left'] #menu {
  transform-origin: right;
}

wa-popup[data-current-placement^='right'] #menu {
  transform-origin: left;
}

wa-popup[data-current-placement='left-start'] #menu {
  transform-origin: right top;
}

wa-popup[data-current-placement='left-end'] #menu {
  transform-origin: right bottom;
}

wa-popup[data-current-placement='right-start'] #menu {
  transform-origin: left top;
}

wa-popup[data-current-placement='right-end'] #menu {
  transform-origin: left bottom;
}

@keyframes show {
  from {
    scale: 0.9;
    opacity: 0;
  }
  to {
    scale: 1;
    opacity: 1;
  }
}
`,wc=new Set,Tc=class extends tt{constructor(){super(...arguments),this.submenuCleanups=new Map,this.localize=new Ee(this),this.userTypedQuery=``,this.openSubmenuStack=[],this.open=!1,this.size=`medium`,this.placement=`bottom-start`,this.distance=0,this.skidding=0,this.handleDocumentKeyDown=async e=>{let t=this.localize.dir()===`rtl`;if(e.key===`Escape`){let t=this.getTrigger();e.preventDefault(),e.stopPropagation(),this.open=!1,t?.focus();return}let n=[...Sc()].find(e=>e.localName===`wa-dropdown-item`),r=n?.localName===`wa-dropdown-item`,i=this.getCurrentSubmenuItem(),a=!!i,o,s,c;a?(o=this.getSubmenuItems(i),s=o.find(e=>e.active||e===n),c=s?o.indexOf(s):-1):(o=this.getItems(),s=o.find(e=>e.active||e===n),c=s?o.indexOf(s):-1);let l;if(e.key===`ArrowUp`&&(e.preventDefault(),e.stopPropagation(),l=c>0?o[c-1]:o[o.length-1]),e.key===`ArrowDown`&&(e.preventDefault(),e.stopPropagation(),l=c!==-1&&c<o.length-1?o[c+1]:o[0]),e.key===(t?`ArrowLeft`:`ArrowRight`)&&r&&s&&s.hasSubmenu){e.preventDefault(),e.stopPropagation(),s.submenuOpen=!0,this.addToSubmenuStack(s),setTimeout(()=>{let e=this.getSubmenuItems(s);e.length>0&&(e.forEach((e,t)=>e.active=t===0),e[0].focus())},0);return}if(e.key===(t?`ArrowRight`:`ArrowLeft`)&&a){e.preventDefault(),e.stopPropagation();let t=this.removeFromSubmenuStack();t&&(t.submenuOpen=!1,setTimeout(()=>{t.focus(),t.active=!0,(t.slot===`submenu`?this.getSubmenuItems(t.parentElement):this.getItems()).forEach(e=>{e!==t&&(e.active=!1)})},0));return}if((e.key===`Home`||e.key===`End`)&&(e.preventDefault(),e.stopPropagation(),l=e.key===`Home`?o[0]:o[o.length-1]),e.key===`Tab`&&await this.hideMenu(),e.key.length===1&&!(e.metaKey||e.ctrlKey||e.altKey)&&!(e.key===` `&&this.userTypedQuery===``)&&(clearTimeout(this.userTypedTimeout),this.userTypedTimeout=setTimeout(()=>{this.userTypedQuery=``},1e3),this.userTypedQuery+=e.key,o.some(e=>{let t=(e.textContent||``).trim().toLowerCase(),n=this.userTypedQuery.trim().toLowerCase();return t.startsWith(n)?(l=e,!0):!1})),l){e.preventDefault(),e.stopPropagation(),o.forEach(e=>e.active=e===l),l.focus();return}(e.key===`Enter`||e.key===` `&&this.userTypedQuery===``)&&r&&s&&(e.preventDefault(),e.stopPropagation(),s.hasSubmenu?(s.submenuOpen=!0,this.addToSubmenuStack(s),setTimeout(()=>{let e=this.getSubmenuItems(s);e.length>0&&(e.forEach((e,t)=>e.active=t===0),e[0].focus())},0)):this.makeSelection(s))},this.handleDocumentPointerDown=e=>{e.composedPath().some(e=>e instanceof HTMLElement?e===this||e.closest(`wa-dropdown, [part="submenu"]`):!1)||(this.open=!1)},this.handleGlobalMouseMove=e=>{let t=this.getCurrentSubmenuItem();if(!t?.submenuOpen||!t.submenuElement)return;let n=t.submenuElement.getBoundingClientRect(),r=this.localize.dir()===`rtl`,i=r?n.right:n.left,a=r?Math.max(e.clientX,i):Math.min(e.clientX,i),o=Math.max(n.top,Math.min(e.clientY,n.bottom));t.submenuElement.style.setProperty(`--safe-triangle-cursor-x`,`${a}px`),t.submenuElement.style.setProperty(`--safe-triangle-cursor-y`,`${o}px`);let s=t.matches(`:hover`),c=t.submenuElement?.matches(`:hover`)||!!e.composedPath().find(e=>e instanceof HTMLElement&&e.closest(`[part="submenu"]`)===t.submenuElement);!s&&!c&&setTimeout(()=>{!t.matches(`:hover`)&&!t.submenuElement?.matches(`:hover`)&&(t.submenuOpen=!1)},100)}}disconnectedCallback(){super.disconnectedCallback(),clearInterval(this.userTypedTimeout),this.closeAllSubmenus(),this.submenuCleanups.forEach(e=>e()),this.submenuCleanups.clear(),document.removeEventListener(`mousemove`,this.handleGlobalMouseMove)}firstUpdated(){this.syncAriaAttributes()}async updated(e){e.has(`open`)&&(this.customStates.set(`open`,this.open),this.open?await this.showMenu():(this.closeAllSubmenus(),await this.hideMenu())),e.has(`size`)&&this.syncItemSizes()}getItems(e=!1){let t=this.defaultSlot.assignedElements({flatten:!0}).filter(e=>e.localName===`wa-dropdown-item`);return e?t:t.filter(e=>!e.disabled)}getSubmenuItems(e,t=!1){let n=e.shadowRoot?.querySelector(`slot[name="submenu"]`)||e.querySelector(`slot[name="submenu"]`);if(!n)return[];let r=n.assignedElements({flatten:!0}).filter(e=>e.localName===`wa-dropdown-item`);return t?r:r.filter(e=>!e.disabled)}syncItemSizes(){this.defaultSlot.assignedElements({flatten:!0}).filter(e=>e.localName===`wa-dropdown-item`).forEach(e=>e.size=this.size)}addToSubmenuStack(e){let t=this.openSubmenuStack.indexOf(e);t===-1?this.openSubmenuStack.push(e):this.openSubmenuStack=this.openSubmenuStack.slice(0,t+1)}removeFromSubmenuStack(){return this.openSubmenuStack.pop()}getCurrentSubmenuItem(){return this.openSubmenuStack.length>0?this.openSubmenuStack[this.openSubmenuStack.length-1]:void 0}closeAllSubmenus(){this.getItems(!0).forEach(e=>{e.submenuOpen=!1}),this.openSubmenuStack=[]}closeSiblingSubmenus(e){let t=e.closest(`wa-dropdown-item:not([slot="submenu"])`),n;n=t?this.getSubmenuItems(t,!0):this.getItems(!0),n.forEach(t=>{t!==e&&t.submenuOpen&&(t.submenuOpen=!1)}),this.openSubmenuStack.includes(e)||this.openSubmenuStack.push(e)}getTrigger(){return this.querySelector(`[slot="trigger"]`)}async showMenu(){if(!this.getTrigger())return;let e=new cr;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}wc.forEach(e=>e.open=!1),this.popup.active=!0,this.open=!0,wc.add(this),this.syncAriaAttributes(),document.addEventListener(`keydown`,this.handleDocumentKeyDown),document.addEventListener(`pointerdown`,this.handleDocumentPointerDown),document.addEventListener(`mousemove`,this.handleGlobalMouseMove),this.menu.classList.remove(`hide`),await pr(this.menu,`show`);let t=this.getItems();t.length>0&&(t.forEach((e,t)=>e.active=t===0),t[0].focus()),this.dispatchEvent(new or)}async hideMenu(){let e=new sr({source:this});if(this.dispatchEvent(e),e.defaultPrevented){this.open=!0;return}this.open=!1,wc.delete(this),this.syncAriaAttributes(),document.removeEventListener(`keydown`,this.handleDocumentKeyDown),document.removeEventListener(`pointerdown`,this.handleDocumentPointerDown),document.removeEventListener(`mousemove`,this.handleGlobalMouseMove),this.menu.classList.remove(`show`),await pr(this.menu,`hide`),this.popup.active=this.open,this.dispatchEvent(new ar)}handleMenuClick(e){let t=e.target.closest(`wa-dropdown-item`);if(!(!t||t.disabled)){if(t.hasSubmenu){t.submenuOpen||=(this.closeSiblingSubmenus(t),this.addToSubmenuStack(t),!0),e.stopPropagation();return}this.makeSelection(t)}}async handleMenuSlotChange(){let e=this.getItems(!0);await Promise.all(e.map(e=>e.updateComplete)),this.syncItemSizes();let t=e.some(e=>e.type===`checkbox`),n=e.some(e=>e.hasSubmenu);e.forEach((e,r)=>{e.active=r===0,e.checkboxAdjacent=t,e.submenuAdjacent=n})}handleTriggerClick(){this.open=!this.open}handleSubmenuOpening(e){let t=e.detail.item;this.closeSiblingSubmenus(t),this.addToSubmenuStack(t),this.setupSubmenuPosition(t),this.processSubmenuItems(t)}setupSubmenuPosition(e){if(!e.submenuElement)return;this.cleanupSubmenuPosition(e);let t=Kn(e,e.submenuElement,()=>{this.positionSubmenu(e),this.updateSafeTriangleCoordinates(e)});this.submenuCleanups.set(e,t);let n=e.submenuElement.querySelector(`slot[name="submenu"]`);n&&(n.removeEventListener(`slotchange`,Tc.handleSubmenuSlotChange),n.addEventListener(`slotchange`,Tc.handleSubmenuSlotChange),Tc.handleSubmenuSlotChange({target:n}))}static handleSubmenuSlotChange(e){let t=e.target;if(!t)return;let n=t.assignedElements().filter(e=>e.localName===`wa-dropdown-item`);if(n.length===0)return;let r=n.some(e=>e.hasSubmenu),i=n.some(e=>e.type===`checkbox`);n.forEach(e=>{e.submenuAdjacent=r,e.checkboxAdjacent=i})}processSubmenuItems(e){if(!e.submenuElement)return;let t=this.getSubmenuItems(e,!0),n=t.some(e=>e.hasSubmenu);t.forEach(e=>{e.submenuAdjacent=n})}cleanupSubmenuPosition(e){let t=this.submenuCleanups.get(e);t&&(t(),this.submenuCleanups.delete(e))}positionSubmenu(e){if(!e.submenuElement)return;let t=this.localize.dir()===`rtl`?`left-start`:`right-start`;Qn(e,e.submenuElement,{placement:t,middleware:[qn({mainAxis:0,crossAxis:-5}),Yn({fallbackStrategy:`bestFit`}),Jn({padding:8})]}).then(({x:t,y:n,placement:r})=>{e.submenuElement.setAttribute(`data-placement`,r),Object.assign(e.submenuElement.style,{left:`${t}px`,top:`${n}px`})})}updateSafeTriangleCoordinates(e){if(!e.submenuElement||!e.submenuOpen)return;if(document.activeElement?.matches(`:focus-visible`)){e.submenuElement.style.setProperty(`--safe-triangle-visible`,`none`);return}e.submenuElement.style.setProperty(`--safe-triangle-visible`,`block`);let t=e.submenuElement.getBoundingClientRect(),n=this.localize.dir()===`rtl`;e.submenuElement.style.setProperty(`--safe-triangle-submenu-start-x`,`${n?t.right:t.left}px`),e.submenuElement.style.setProperty(`--safe-triangle-submenu-start-y`,`${t.top}px`),e.submenuElement.style.setProperty(`--safe-triangle-submenu-end-x`,`${n?t.right:t.left}px`),e.submenuElement.style.setProperty(`--safe-triangle-submenu-end-y`,`${t.bottom}px`)}makeSelection(e){let t=this.getTrigger();if(e.disabled)return;e.type===`checkbox`&&(e.checked=!e.checked);let n=new xc({item:e});this.dispatchEvent(n),n.defaultPrevented||(this.open=!1,t?.focus())}async syncAriaAttributes(){let e=this.getTrigger(),t;e&&(e.localName===`wa-button`?(await customElements.whenDefined(`wa-button`),await e.updateComplete,t=e.shadowRoot.querySelector(`[part="base"]`)):t=e,t.hasAttribute(`id`)||t.setAttribute(`id`,dr(`wa-dropdown-trigger-`)),t.setAttribute(`aria-haspopup`,`menu`),t.setAttribute(`aria-expanded`,this.open?`true`:`false`),this.menu.setAttribute(`aria-expanded`,`false`))}render(){let e=this.hasUpdated?this.popup.active:this.open;return d`
      <wa-popup
        placement=${this.placement}
        distance=${this.distance}
        skidding=${this.skidding}
        ?active=${e}
        flip
        flip-fallback-strategy="best-fit"
        shift
        shift-padding="10"
        auto-size="vertical"
        auto-size-padding="10"
      >
        <slot
          name="trigger"
          slot="anchor"
          @click=${this.handleTriggerClick}
          @slotchange=${this.syncAriaAttributes}
        ></slot>
        <div
          id="menu"
          part="menu"
          role="menu"
          tabindex="-1"
          aria-orientation="vertical"
          @click=${this.handleMenuClick}
          @submenu-opening=${this.handleSubmenuOpening}
        >
          <slot @slotchange=${this.handleMenuSlotChange}></slot>
        </div>
      </wa-popup>
    `}};Tc.css=[bc,Cc],k([S(`slot:not([name])`)],Tc.prototype,`defaultSlot`,2),k([S(`#menu`)],Tc.prototype,`menu`,2),k([S(`wa-popup`)],Tc.prototype,`popup`,2),k([y({type:Boolean,reflect:!0})],Tc.prototype,`open`,2),k([y({reflect:!0})],Tc.prototype,`size`,2),k([y({reflect:!0})],Tc.prototype,`placement`,2),k([y({type:Number})],Tc.prototype,`distance`,2),k([y({type:Number})],Tc.prototype,`skidding`,2),Tc=k([x(`wa-dropdown`)],Tc);var Ec=class{constructor(e,...t){this.slotNames=[],this.handleSlotChange=e=>{let t=e.target;(this.slotNames.includes(`[default]`)&&!t.name||t.name&&this.slotNames.includes(t.name))&&this.host.requestUpdate()},(this.host=e).addController(this),this.slotNames=t}hasDefaultSlot(){return[...this.host.childNodes].some(e=>{if(e.nodeType===Node.TEXT_NODE&&e.textContent.trim()!==``)return!0;if(e.nodeType===Node.ELEMENT_NODE){let t=e;if(t.tagName.toLowerCase()===`wa-visually-hidden`)return!1;if(!t.hasAttribute(`slot`))return!0}return!1})}hasNamedSlot(e){return this.host.querySelector(`:scope > [slot="${e}"]`)!==null}test(e){return e===`[default]`?this.hasDefaultSlot():this.hasNamedSlot(e)}hostConnected(){this.host.shadowRoot.addEventListener(`slotchange`,this.handleSlotChange)}hostDisconnected(){this.host.shadowRoot.removeEventListener(`slotchange`,this.handleSlotChange)}},Dc=`:host {
  display: flex;
  position: relative;
  align-items: center;
  padding: 0.5em 1em;
  border-radius: var(--wa-border-radius-s);
  isolation: isolate;
  color: var(--wa-color-text-normal);
  line-height: var(--wa-line-height-condensed);
  cursor: pointer;
  transition:
    100ms background-color ease,
    100ms color ease;
}

@media (hover: hover) {
  :host(:hover:not(:state(disabled))) {
    background-color: var(--wa-color-neutral-fill-normal);
  }
}

:host(:focus-visible) {
  z-index: 1;
  outline: var(--wa-focus-ring);
  background-color: var(--wa-color-neutral-fill-normal);
}

:host(:state(disabled)) {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Danger variant */
:host([variant='danger']),
:host([variant='danger']) #details {
  color: var(--wa-color-danger-on-quiet);
}

@media (hover: hover) {
  :host([variant='danger']:hover) {
    background-color: var(--wa-color-danger-fill-normal);
    color: var(--wa-color-danger-on-normal);
  }
}

:host([variant='danger']:focus-visible) {
  background-color: var(--wa-color-danger-fill-normal);
  color: var(--wa-color-danger-on-normal);
}

:host([checkbox-adjacent]) {
  padding-inline-start: 2em;
}

/* Only add padding when item actually has a submenu */
:host([submenu-adjacent]:not(:state(has-submenu))) #details {
  padding-inline-end: 0;
}

:host(:state(has-submenu)[submenu-adjacent]) #details {
  padding-inline-end: 1.75em;
}

#check {
  visibility: hidden;
  margin-inline-start: -1.5em;
  margin-inline-end: 0.5em;
  font-size: var(--wa-font-size-smaller);
}

:host(:state(checked)) #check {
  visibility: visible;
}

#icon ::slotted(*) {
  display: flex;
  flex: 0 0 auto;
  align-items: center;
  margin-inline-end: 0.75em !important;
  font-size: var(--wa-font-size-smaller);
}

#label {
  flex: 1 1 auto;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

#details {
  display: flex;
  flex: 0 0 auto;
  align-items: center;
  justify-content: end;
  color: var(--wa-color-text-quiet);
  font-size: var(--wa-font-size-smaller) !important;
}

#details ::slotted(*) {
  margin-inline-start: 2em !important;
}

/* Submenu indicator icon */
#submenu-indicator {
  position: absolute;
  inset-inline-end: 1em;
  color: var(--wa-color-neutral-on-quiet);
  font-size: var(--wa-font-size-smaller);
}

/* Flip chevron icon when RTL */
:host(:dir(rtl)) #submenu-indicator {
  transform: scaleX(-1);
}

/* Submenu styles */
#submenu {
  display: flex;
  z-index: 10;
  position: absolute;
  top: 0;
  left: 0;
  flex-direction: column;
  width: max-content;
  margin: 0;
  padding: 0.25em;
  border: var(--wa-border-style) var(--wa-border-width-s) var(--wa-color-surface-border);
  border-radius: var(--wa-border-radius-m);
  background-color: var(--wa-color-surface-raised);
  box-shadow: var(--wa-shadow-m);
  color: var(--wa-color-text-normal);
  text-align: start;
  user-select: none;

  /* Override default popover styles */
  &[popover] {
    margin: 0;
    inset: auto;
    padding: 0.25em;
    overflow: visible;
    border-radius: var(--wa-border-radius-m);
  }

  &.show {
    animation: submenu-show var(--show-duration, 50ms) ease;
  }

  &.hide {
    animation: submenu-show var(--show-duration, 50ms) ease reverse;
  }

  /* Submenu placement transform origins */
  &[data-placement^='top'] {
    transform-origin: bottom;
  }

  &[data-placement^='bottom'] {
    transform-origin: top;
  }

  &[data-placement^='left'] {
    transform-origin: right;
  }

  &[data-placement^='right'] {
    transform-origin: left;
  }

  &[data-placement='left-start'] {
    transform-origin: right top;
  }

  &[data-placement='left-end'] {
    transform-origin: right bottom;
  }

  &[data-placement='right-start'] {
    transform-origin: left top;
  }

  &[data-placement='right-end'] {
    transform-origin: left bottom;
  }

  /* Safe triangle styling */
  &::before {
    display: none;
    z-index: 9;
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    background-color: transparent;
    content: '';
    clip-path: polygon(
      var(--safe-triangle-cursor-x, 0) var(--safe-triangle-cursor-y, 0),
      var(--safe-triangle-submenu-start-x, 0) var(--safe-triangle-submenu-start-y, 0),
      var(--safe-triangle-submenu-end-x, 0) var(--safe-triangle-submenu-end-y, 0)
    );
    pointer-events: auto; /* Enable mouse events on the triangle */
  }

  &[data-visible]::before {
    display: block;
  }
}

::slotted(wa-dropdown-item) {
  font-size: inherit;
}

::slotted(wa-divider) {
  --spacing: 0.25em;
}

@keyframes submenu-show {
  from {
    scale: 0.9;
    opacity: 0;
  }
  to {
    scale: 1;
    opacity: 1;
  }
}
`,Oc=class extends tt{constructor(){super(...arguments),this.hasSlotController=new Ec(this,`[default]`,`start`,`end`),this.active=!1,this.variant=`default`,this.size=`medium`,this.checkboxAdjacent=!1,this.submenuAdjacent=!1,this.type=`normal`,this.checked=!1,this.disabled=!1,this.submenuOpen=!1,this.hasSubmenu=!1,this.handleSlotChange=()=>{this.hasSubmenu=this.hasSlotController.test(`submenu`),this.updateHasSubmenuState(),this.hasSubmenu?(this.setAttribute(`aria-haspopup`,`menu`),this.setAttribute(`aria-expanded`,this.submenuOpen?`true`:`false`)):(this.removeAttribute(`aria-haspopup`),this.removeAttribute(`aria-expanded`))}}connectedCallback(){super.connectedCallback(),this.addEventListener(`mouseenter`,this.handleMouseEnter.bind(this)),this.shadowRoot.addEventListener(`slotchange`,this.handleSlotChange)}disconnectedCallback(){super.disconnectedCallback(),this.closeSubmenu(),this.removeEventListener(`mouseenter`,this.handleMouseEnter),this.shadowRoot.removeEventListener(`slotchange`,this.handleSlotChange)}firstUpdated(){this.setAttribute(`tabindex`,`-1`),this.hasSubmenu=this.hasSlotController.test(`submenu`),this.updateHasSubmenuState()}updated(e){e.has(`active`)&&(this.setAttribute(`tabindex`,this.active?`0`:`-1`),this.customStates.set(`active`,this.active)),e.has(`checked`)&&(this.setAttribute(`aria-checked`,this.checked?`true`:`false`),this.customStates.set(`checked`,this.checked)),e.has(`disabled`)&&(this.setAttribute(`aria-disabled`,this.disabled?`true`:`false`),this.customStates.set(`disabled`,this.disabled)),e.has(`type`)&&(this.type===`checkbox`?this.setAttribute(`role`,`menuitemcheckbox`):this.setAttribute(`role`,`menuitem`)),e.has(`submenuOpen`)&&(this.customStates.set(`submenu-open`,this.submenuOpen),this.submenuOpen?this.openSubmenu():this.closeSubmenu())}updateHasSubmenuState(){this.customStates.set(`has-submenu`,this.hasSubmenu)}async openSubmenu(){!this.hasSubmenu||!this.submenuElement||(this.notifyParentOfOpening(),this.submenuElement.showPopover(),this.submenuElement.hidden=!1,this.submenuElement.setAttribute(`data-visible`,``),this.submenuOpen=!0,this.setAttribute(`aria-expanded`,`true`),await pr(this.submenuElement,`show`),setTimeout(()=>{let e=this.getSubmenuItems();e.length>0&&(e.forEach((e,t)=>e.active=t===0),e[0].focus())},0))}notifyParentOfOpening(){let e=new CustomEvent(`submenu-opening`,{bubbles:!0,composed:!0,detail:{item:this}});this.dispatchEvent(e);let t=this.parentElement;t&&[...t.children].filter(e=>e!==this&&e.localName===`wa-dropdown-item`&&e.getAttribute(`slot`)===this.getAttribute(`slot`)&&e.submenuOpen).forEach(e=>{e.submenuOpen=!1})}async closeSubmenu(){!this.hasSubmenu||!this.submenuElement||(this.submenuOpen=!1,this.setAttribute(`aria-expanded`,`false`),this.submenuElement.hidden||(await pr(this.submenuElement,`hide`),this.submenuElement.hidden=!0,this.submenuElement.removeAttribute(`data-visible`),this.submenuElement.hidePopover()))}getSubmenuItems(){return[...this.children].filter(e=>e.localName===`wa-dropdown-item`&&e.getAttribute(`slot`)===`submenu`&&!e.hasAttribute(`disabled`))}handleMouseEnter(){this.hasSubmenu&&!this.disabled&&(this.notifyParentOfOpening(),this.submenuOpen=!0)}render(){return d`
      ${this.type===`checkbox`?d`
            <wa-icon
              id="check"
              part="checkmark"
              exportparts="svg:checkmark__svg"
              library="system"
              name="check"
            ></wa-icon>
          `:``}

      <span id="icon" part="icon">
        <slot name="icon"></slot>
      </span>

      <span id="label" part="label">
        <slot></slot>
      </span>

      <span id="details" part="details">
        <slot name="details"></slot>
      </span>

      ${this.hasSubmenu?d`
            <wa-icon
              id="submenu-indicator"
              part="submenu-icon"
              exportparts="svg:submenu-icon__svg"
              library="system"
              name="chevron-right"
            ></wa-icon>
          `:``}
      ${this.hasSubmenu?d`
            <div
              id="submenu"
              part="submenu"
              popover="manual"
              role="menu"
              tabindex="-1"
              aria-orientation="vertical"
              hidden
            >
              <slot name="submenu"></slot>
            </div>
          `:``}
    `}};Oc.css=Dc,k([S(`#submenu`)],Oc.prototype,`submenuElement`,2),k([y({type:Boolean})],Oc.prototype,`active`,2),k([y({reflect:!0})],Oc.prototype,`variant`,2),k([y({reflect:!0})],Oc.prototype,`size`,2),k([y({attribute:`checkbox-adjacent`,type:Boolean,reflect:!0})],Oc.prototype,`checkboxAdjacent`,2),k([y({attribute:`submenu-adjacent`,type:Boolean,reflect:!0})],Oc.prototype,`submenuAdjacent`,2),k([y()],Oc.prototype,`value`,2),k([y({reflect:!0})],Oc.prototype,`type`,2),k([y({type:Boolean})],Oc.prototype,`checked`,2),k([y({type:Boolean,reflect:!0})],Oc.prototype,`disabled`,2),k([y({type:Boolean,reflect:!0})],Oc.prototype,`submenuOpen`,2),k([b()],Oc.prototype,`hasSubmenu`,2),Oc=k([x(`wa-dropdown-item`)],Oc);var kc=class extends Tc{static get styles(){return[Tc.styles,p`
        :host {
          --wa-border-style: solid;
          --wa-border-width-s: 1px;
          --wa-color-surface-raised: var(--c-surface-raised);
          --wa-color-surface-border: var(--c-color-neutral-border-quiet);
          --wa-border-radius-m: var(--c-radius-lg);
        }

        #menu {
          gap: 1px;
        }
      `]}},Ac=class extends Oc{static get styles(){return[Oc.styles,p`
        @layer components.dropdown-item {
          :host {
            --wa-font-weight-action: 400;
            --wa-space-s: var(--c-spacing-sm);
            --wa-color-neutral-fill-normal: var(--c-color-neutral-fill-quiet);
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            border-radius: var(--c-radius-sm);
            padding-block: calc(var(--c-spacing, 0.25rem) * 1);
            padding-inline: calc(var(--c-spacing, 0.25rem) * 2.5);
          }
        }
      `]}};customElements.get(`craft-dropdown`)||customElements.define(`craft-dropdown`,kc),customElements.get(`craft-dropdown-item`)||customElements.define(`craft-dropdown-item`,Ac);function jc({el:e,uid:t}){e.setAttribute(`id`,`panel-${t}`),e.setAttribute(`role`,`tabpanel`),e.setAttribute(`aria-labelledby`,`button-${t}`),e.hasAttribute(`tabindex`)||e.setAttribute(`tabindex`,`0`)}function Mc(e){e.setAttribute(`selected`,`true`)}function Nc(e){e.removeAttribute(`selected`)}function Pc({el:e,uid:t,clickHandler:n,keydownHandler:r,keyupHandler:i}){e.setAttribute(`id`,`button-${t}`),e.setAttribute(`role`,`tab`),e.setAttribute(`aria-controls`,`panel-${t}`),e.addEventListener(`click`,n),e.addEventListener(`keyup`,i),e.addEventListener(`keydown`,r)}function Fc({el:e,clickHandler:t,keydownHandler:n,keyupHandler:r}){e.removeAttribute(`id`),e.removeAttribute(`role`),e.removeAttribute(`aria-controls`),e.removeEventListener(`click`,t),e.removeEventListener(`keyup`,r),e.removeEventListener(`keydown`,n)}function Ic(e,t=!1){t&&e.focus(),e.setAttribute(`selected`,`true`),e.setAttribute(`aria-selected`,`true`),e.setAttribute(`tabindex`,`0`)}function Lc(e){e.removeAttribute(`selected`),e.setAttribute(`aria-selected`,`false`),e.setAttribute(`tabindex`,`-1`)}function Rc(e){let t=e;switch(t.key){case`ArrowDown`:case`ArrowRight`:case`ArrowUp`:case`ArrowLeft`:case`Home`:case`End`:t.preventDefault()}}var zc=class extends v{static get properties(){return{selectedIndex:{type:Number,attribute:`selected-index`,reflect:!0}}}static get styles(){return[p`
        .tabs__tab-group {
          display: flex;
        }

        .tabs__tab-group ::slotted([slot='tab'][selected]) {
          font-weight: bold;
        }

        .tabs__panels ::slotted([slot='panel']) {
          visibility: hidden;
          display: none;
        }

        .tabs__panels ::slotted([slot='panel'][selected]) {
          visibility: visible;
          display: block;
        }

        .tabs__panels {
          display: block;
        }
      `]}render(){return d`
      <div class="tabs__tab-group" role="tablist">
        <slot name="tab"></slot>
      </div>
      <div class="tabs__panels">
        <slot name="panel"></slot>
      </div>
    `}constructor(){super(),this.selectedIndex=0}firstUpdated(e){super.firstUpdated(e),this.__setupSlots(),this.tabs[0]?.disabled&&(this.selectedIndex=this.tabs.findIndex(e=>!e.disabled))}get tabs(){return Array.from(this.children).filter(e=>e.slot===`tab`)}get panels(){return Array.from(this.children).filter(e=>e.slot===`panel`)}static enabledWarnings=super.enabledWarnings?.filter(e=>e!==`change-in-update`)||[];__setupSlots(){if(this.shadowRoot){let e=this.shadowRoot.querySelector(`slot[name=tab]`);e&&e.addEventListener(`slotchange`,()=>{this.__cleanStore(),this.__setupStore(),this.__updateSelected(!1)})}}__setupStore(){this.__store=[],this.tabs.length!==this.panels.length&&console.warn(`The amount of tabs (${this.tabs.length}) doesn't match the amount of panels (${this.panels.length}).`),this.tabs.forEach((e,t)=>{let n={uid:Wr(),el:e,button:e,panel:this.panels[t],clickHandler:this.__createButtonClickHandler(t),keydownHandler:Rc.bind(this),keyupHandler:this.__handleButtonKeyup.bind(this)};jc({...n,el:n.panel}),Pc(n),Nc(n.panel),Lc(n.button),this.__store&&this.__store.push(n)})}__cleanStore(){this.__store&&=(this.__store.forEach(e=>{Fc(e)}),[])}__getNextNotDisabledTab(e,t,n){let r=[],i=e.filter((e,t)=>!e.disabled&&t>this.selectedIndex),a=e.filter((e,t)=>!e.disabled&&t<this.selectedIndex);return r=n===`right`?[...i,...a]:[...a.reverse(),...i.reverse()],r[0]}__getNextAvailableIndex(e,t){let n=this.tabs[this.selectedIndex];if(this.tabs.every(e=>!e.disabled))return e;if(t===`ArrowRight`||t===`ArrowDown`){let e=this.__getNextNotDisabledTab(this.tabs,n,`right`);return this.tabs.findIndex(t=>e===t)}if(t===`ArrowLeft`||t===`ArrowUp`){let e=this.__getNextNotDisabledTab(this.tabs,n,`left`);return this.tabs.findIndex(t=>e===t)}if(t===`Home`)return this.tabs.findIndex(e=>!e.disabled);if(t===`End`){let e=this.tabs.map((e,t)=>({disabled:e.disabled,index:t})).filter(e=>!e.disabled);return e[e.length-1].index}return-1}__createButtonClickHandler(e){return()=>{this._setSelectedIndexWithFocus(e)}}__handleButtonKeyup(e){let t=e;if(typeof this.selectedIndex==`number`)switch(t.key){case`ArrowDown`:case`ArrowRight`:this.selectedIndex+1>=this._pairCount?this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(0,t.key)):this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this.selectedIndex+1,t.key));break;case`ArrowUp`:case`ArrowLeft`:this.selectedIndex<=0?this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this._pairCount-1,t.key)):this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this.selectedIndex-1,t.key));break;case`Home`:this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(0,t.key));break;case`End`:this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this._pairCount-1,t.key));break}}get selectedIndex(){return this.__selectedIndex||0}set selectedIndex(e){if(e===this.__selectedIndex)return;let t=this.__selectedIndex;this.__selectedIndex=e,this.__updateSelected(!1),this.dispatchEvent(new Event(`selected-changed`)),this.requestUpdate(`selectedIndex`,t)}_setSelectedIndexWithFocus(e){if(e===-1)return;let t=this.__selectedIndex;this.__selectedIndex=e,this.__updateSelected(!0),this.dispatchEvent(new Event(`selected-changed`)),this.requestUpdate(`selectedIndex`,t)}get _pairCount(){return this.__store&&this.__store.length||0}__updateSelected(e=!1){if(!(this.__store&&typeof this.selectedIndex==`number`&&this.__store[this.selectedIndex]))return;let t=this.tabs.find(e=>e.hasAttribute(`selected`)),n=this.panels.find(e=>e.hasAttribute(`selected`));t&&Lc(t),n&&Nc(n);let{button:r,panel:i}=this.__store[this.selectedIndex];r&&Ic(r,e),i&&Mc(i)}},Bc=p`
  :host {
    display: block;
  }

  :host([layout='vertical']) .tabs__tab-group {
    flex-direction: column;
  }

  .tabs__panels {
    padding-block-start: var(--c-spacing-lg);
  }

  .tabs__tab-group {
    gap: var(--c-spacing-md);
    border-bottom: 1px solid
      var(--c-tabs-border-end, var(--c-color-neutral-border-quiet));
  }
`,Vc=class extends zc{static get styles(){return[...super.styles,Bc]}};customElements.get(`craft-tabs`)||customElements.define(`craft-tabs`,Vc);var Hc=p`
  :host {
    display: block;
  }

  .card {
    color: var(--c-card-color, var(--c-color-neutral-on-quiet));
    background-color: var(--c-card-fill, var(--c-color-neutral-fill-quiet));
    border: 1px solid var(--c-card-border, var(--c-color-neutral-border-quiet));
    border-radius: var(--c-card-radius, var(--c-radius-md));
    box-shadow: var(--c-card-shadow, var(--c-shadow-sm));
    position: relative;
  }

  .card__header,
  .card__footer {
    font-size: 0.875em;
    padding-block: var(--c-card-padding-block, var(--c-spacing-sm));
    padding-inline-start: var(--c-card-padding-inline, var(--c-spacing-md));
    padding-inline-end: var(--c-card-padding-inline, var(--c-spacing-sm));
    color: var(--c-card-bars-text, var(--c-color-neutral-on-quiet));
    background-color: var(
      --c-card-bars-fill,
      var(--c-color-neutral-fill-quiet)
    );
    border-width: 0;
    border-color: var(--c-card-border, var(--c-color-neutral-border-quiet));
    border-style: solid;
  }

  .card__footer {
    border-block-start-width: 1px;
    border-end-start-radius: var(--c-card-radius, var(--c-radius-md));
    border-end-end-radius: var(--c-card-radius, var(--c-radius-md));
  }

  .card__header {
    border-start-start-radius: var(--c-card-radius, var(--c-radius-md));
    border-start-end-radius: var(--c-card-radius, var(--c-radius-md));
    border-block-end-width: 1px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .card__body {
    padding-inline: var(--c-card-padding-inline, var(--c-spacing-md));
    padding-block: var(--c-card-padding-block, var(--c-spacing-md));
  }

  .card__actions {
    display: flex;
    gap: var(--c-spacing-sm);
  }
`,Uc=class extends v{constructor(...e){super(...e),this.label=``}render(){let e=!!this.label||!!this.querySelector(`[slot="header"]`)||!!this.querySelector(`[slot="label"]`)||!!this.querySelector(`[slot="actions"]`),t=!!this.querySelector(`[slot="footer"]`);return d`
      <div class="card">
        <div>
          ${e?d`<div class="card__header">
                <slot name="header">
                  <slot name="label" class="card__label" part="label"
                    >${this.label}</slot
                  >
                  <slot name="actions"></slot>
                </slot>
              </div>`:_}

          <div class="card__body">
            <slot></slot>
          </div>

          ${t?d`<div class="card__footer"><slot name="footer"></slot></div>`:_}
        </div>
      </div>
    `}};Uc.styles=[Hc],l([y()],Uc.prototype,`label`,void 0),customElements.get(`craft-card`)||customElements.define(`craft-card`,Uc);var Wc=p`
  :host {
    display: inline-flex;
    padding-inline: var(--c-tab-spacing-inline, 1em);
    padding-block: var(--c-tab-spacing-block, 0.5em);
    position: relative;
    cursor: pointer;

    &:after {
      content: '';
      position: absolute;
      inset-block-end: -1px;
      inset-inline: 0;
      display: block;
      width: 100%;
      height: calc(2rem / 16);
      background-color: transparent;
    }
  }

  :host([selected]) {
    font-weight: 400;

    &:after {
      background-color: var(
        --c-tab-border-active,
        var(--c-color-accent-border-loud)
      );
    }
  }
`,Gc=class extends v{render(){return d`<slot></slot> `}};Gc.styles=[Wc],customElements.get(`craft-tab`)||customElements.define(`craft-tab`,Gc);var Kc=class extends Or(v){static get properties(){return{checked:{type:Boolean,reflect:!0}}}static get styles(){return[p`
        :host {
          display: inline-block;
          position: relative;
          width: 36px;
          height: 16px;
          outline: 0;
        }

        :host([hidden]) {
          display: none;
        }

        .btn {
          position: relative;
          height: 100%;
          outline: 0;
        }

        :host(:focus:not([disabled])) .switch-button__thumb {
          /* if you extend, please overwrite */
          outline: 2px solid #bde4ff;
        }

        .switch-button__track {
          background: #eee;
          width: 100%;
          height: 100%;
        }

        .switch-button__thumb {
          background: #ccc;
          width: 50%;
          height: 100%;
          position: absolute;
          top: 0;
        }

        :host([checked]) .switch-button__thumb {
          right: 0;
        }
      `]}render(){return d`
      <div class="btn">
        <div class="switch-button__track"></div>
        <div class="switch-button__thumb"></div>
      </div>
    `}constructor(){super(),this.value=``,this.checked=!1,this.__initialized=!1,this._toggleChecked=this._toggleChecked.bind(this),this.__handleKeydown=this._handleKeydown.bind(this),this.__handleKeyup=this._handleKeyup.bind(this)}connectedCallback(){super.connectedCallback(),this.setAttribute(`role`,`switch`),this.setAttribute(`aria-checked`,`${this.checked}`),this.addEventListener(`click`,this._toggleChecked),this.addEventListener(`keydown`,this.__handleKeydown),this.addEventListener(`keyup`,this.__handleKeyup)}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener(`click`,this._toggleChecked),this.removeEventListener(`keydown`,this.__handleKeydown),this.removeEventListener(`keyup`,this.__handleKeyup)}_toggleChecked(){this.disabled||(this.focus(),this.checked=!this.checked)}__checkedStateChange(){this.dispatchEvent(new Event(`checked-changed`,{bubbles:!0})),this.setAttribute(`aria-checked`,`${this.checked}`)}_handleKeydown(e){e.key===` `&&e.preventDefault()}_handleKeyup(e){[` `,`Enter`].includes(e.key)&&this._toggleChecked()}updated(e){super.updated(e),e.has(`disabled`)&&this.setAttribute(`aria-disabled`,`${this.disabled}`)}requestUpdate(e,t,n){super.requestUpdate(e,t,n),this.__initialized&&this.isConnected&&e===`checked`&&this.checked!==t&&!this.disabled&&this.__checkedStateChange()}firstUpdated(e){super.firstUpdated(e),this.__initialized=!0}},qc=class extends po(Ro(jo)){static get styles(){return[...super.styles,p`
        :host([hidden]) {
          display: none;
        }

        :host([disabled]) {
          color: #adadad;
        }
      `]}static get scopedElements(){return{...super.scopedElements,"lion-switch-button":Kc}}get _inputNode(){return Array.from(this.children).find(e=>e.slot===`input`)}get slots(){return{...super.slots,input:()=>{let e=this.createScopedElement(`lion-switch-button`);return e.setAttribute(`data-tag-name`,`lion-switch-button`),e}}}render(){return d`
      <div class="form-field__group-one">${this._groupOneTemplate()}</div>
      <div class="form-field__group-two">${this._groupTwoTemplate()}</div>
    `}_groupOneTemplate(){return d`${this._labelTemplate()} ${this._helpTextTemplate()} ${this._feedbackTemplate()}`}_groupTwoTemplate(){return d`${this._inputGroupTemplate()}`}constructor(){super(),this.checked=!1,this.__handleButtonSwitchCheckedChanged=this.__handleButtonSwitchCheckedChanged.bind(this)}connectedCallback(){super.connectedCallback(),this.addEventListener(`checked-changed`,this.__handleButtonSwitchCheckedChanged),this._labelNode&&this._labelNode.addEventListener(`click`,this._toggleChecked),this._syncButtonSwitch()}disconnectedCallback(){super.disconnectedCallback(),this._inputNode&&this.removeEventListener(`checked-changed`,this.__handleButtonSwitchCheckedChanged),this._labelNode&&this._labelNode.removeEventListener(`click`,this._toggleChecked)}updated(e){super.updated(e),e.has(`disabled`)&&this._syncButtonSwitch()}_toggleChecked(e){e.preventDefault(),super._toggleChecked(e)}_isEmpty(){return!1}__handleButtonSwitchCheckedChanged(e){e.stopPropagation(),this._isHandlingUserInput=!0,this.checked=this._inputNode.checked,this._isHandlingUserInput=!1}_syncButtonSwitch(){this._inputNode.disabled=this.disabled}_onLabelClick(){this.disabled||this._inputNode.focus()}},Jc=class extends Kc{static get styles(){return[...super.styles,p`
        :host {
          --c-switch-height: var(--c-size-control-sm);
          --c-switch-thumb-offset: 6px;
          --c-switch-thumb-height: calc(
            var(--c-switch-height) - var(--c-switch-thumb-offset)
          );
          display: flex;
          height: var(--c-switch-height);
          width: calc(var(--c-switch-height) * 2);
          margin: -1px;
        }

        :host([size='small']) {
          --c-switch-height: var(--c-size-control-xs);
          --c-switch-thumb-offset: 4px;
        }

        .btn {
          width: 100%;
        }

        .switch-button__track {
          --tw-inset-shadow-color: var(--color-slate-300);
          margin-inline: -1px;
          background-color: var(--c-color-neutral-fill-quiet);
          border-radius: var(--c-radius-full);
          border: 1px solid var(--c-form-control-border-color);
          box-shadow: var(--c-input-shadow);
        }

        .switch-button__thumb {
          height: var(--c-switch-thumb-height);
          width: auto;
          aspect-ratio: 1;
          border-radius: var(--c-radius-full);
          border: 1px solid var(--c-form-control-border-color);
          background-color: var(--c-switch-thumb-fill, var(--c-surface-raised));
          inset-block-start: calc(var(--c-switch-thumb-offset) / 2);
          inset-inline-start: calc(var(--c-switch-thumb-offset) / 2);
          inset-inline-end: auto;
        }

        :host([checked]) .switch-button__track {
          background-color: var(--c-color-success-fill-loud);
        }

        :host([checked]) .switch-button__thumb {
          border: 1px solid var(--c-color-success-border-loud);
          inset-inline-start: auto;
          inset-inline-end: calc(
            (var(--c-switch-height) - var(--c-switch-thumb-height)) / 2
          );
        }

        :host([checked]) .switch-button__thumb:after {
          content: '';
          position: absolute;
          inset-block-start: 3px;
          inset-inline-start: 4px;
          mask-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 448 512'%3E%3C!--! Font Awesome Pro 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2024 Fonticons, Inc.--%3E%3Cpath d='M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7l233.4-233.3c12.5-12.5 32.8-12.5 45.3 0z'/%3E%3C/svg%3E");
          mask-repeat: no-repeat;
          width: calc(var(--c-switch-thumb-height) - 6px);
          aspect-ratio: 1;
          background-color: var(--c-color-success-border-loud);
        }
      `]}};customElements.get(`craft-switch-button`)||customElements.define(`craft-switch-button`,Jc);var Yc=p`
  :host {
    display: grid;
  }

  .input-group {
    display: inline-flex;
  }

  ::slotted(label) {
    font-weight: bold;
  }
`,Xc=class extends qc{constructor(...e){super(...e),this.size=`medium`}static get styles(){return[...super.styles,va,Yc]}get slots(){return{...super.slots,input:()=>{let e=this.createScopedElement(`craft-switch-button`);return e.setAttribute(`size`,this.size),e.setAttribute(`data-tag-name`,`craft-switch-button`),e}}}static get scopedElements(){return{...super.scopedElements,"craft-switch-button":Jc}}};l([y({type:String,reflect:!0})],Xc.prototype,`size`,void 0),customElements.get(`craft-switch`)||customElements.define(`craft-switch`,Xc);var Zc=p`
  .breadcrumbs {
    display: flex;
    align-items: center;
  }
`,Qc=class extends v{constructor(...e){super(...e),this.label=``,this.items=[],this.visibleItems=0,this.firstRender=!0}getSeparator(){let e=this.separatorSlot.assignedElements({flatten:!0})[0].cloneNode(!0);return[e,...e.querySelectorAll(`[id]`)].forEach(e=>e.removeAttribute(`id`)),e.setAttribute(`data-default`,``),e.slot=`separator`,e}calculateBreadcrumbItemsWidth(){this.items=this.breadcrumbsElements.map((e,t)=>{let n=e.offsetWidth;return e.hasAttribute(`hidden`)&&(e.removeAttribute(`hidden`),n=e.offsetWidth,e.setAttribute(`hidden`,``)),{label:e.innerText,href:e.href,value:t.toString(),offsetWidth:n,isVisible:!0}})}async handleSlotChange(){let e=[...this.defaultSlot.assignedElements({flatten:!0})].filter(e=>e.tagName.toLowerCase()===`craft-breadcrumb-item`);if(e.forEach((t,n)=>{let r=t.querySelector(`[slot="separator"]`);r===null?t.append(this.getSeparator()):r.hasAttribute(`data-default`)&&r.replaceWith(this.getSeparator()),n===e.length-1?t.setAttribute(`aria-current`,`page`):t.removeAttribute(`aria-current`)}),this.breadcrumbsElements.length===0){this.items=[],this.visibleItems=0;return}await Promise.all(this.breadcrumbsElements.map(e=>e.updateComplete)),this.calculateBreadcrumbItemsWidth(),this.visibleItems=0,this.adjustOverflow()}connectedCallback(){super.connectedCallback(),this.hasAttribute(`role`)||this.setAttribute(`role`,`navigation`),this.resizeObserver=new ResizeObserver(()=>{if(this.firstRender){this.firstRender=!1;return}this.adjustOverflow()}),this.resizeObserver.observe(this)}adjustOverflow(){let e=this.getBoundingClientRect().width;console.log({availableSpace:e})}disconnectedCallback(){this.resizeObserver?.unobserve(this),super.disconnectedCallback()}render(){return d`
      <nav class="breadcrumbs" aria-label="${this.label}">
        <slot @slotchange="${this.handleSlotChange}"></slot>
      </nav>

      <span hidden aria-hidden="true">
        <slot name="separator"><span class="separator">/</span></slot>
      </span>
    `}};Qc.styles=[Zc],l([S(`slot`)],Qc.prototype,`defaultSlot`,void 0),l([S(`slot[name="separator"]`)],Qc.prototype,`separatorSlot`,void 0),l([C({selector:`craft-breadcrumb-item`})],Qc.prototype,`breadcrumbsElements`,void 0),l([y()],Qc.prototype,`label`,void 0),l([b()],Qc.prototype,`items`,void 0),l([b()],Qc.prototype,`visibleItems`,void 0),customElements.get(`craft-breadcrumbs`)||customElements.define(`craft-breadcrumbs`,Qc);var $c=`:host {
  color: var(--wa-color-text-link);
  display: inline-flex;
  align-items: center;
  font: inherit;
  font-weight: var(--wa-font-weight-action);
  line-height: var(--wa-line-height-normal);
  white-space: nowrap;
}

:host(:last-of-type) {
  color: var(--wa-color-text-quiet);
}

.label {
  display: inline-block;
  font: inherit;
  text-decoration: none;
  color: currentColor;
  background: none;
  border: none;
  border-radius: var(--wa-border-radius-m);
  padding: 0;
  margin: 0;
  cursor: pointer;
  transition: color var(--wa-transition-normal) var(--wa-transition-easing);
}

@media (hover: hover) {
  :host(:not(:last-of-type)) .label:hover {
    color: color-mix(in oklab, currentColor, var(--wa-color-mix-hover));
  }
}

:host(:not(:last-of-type)) .label:active {
  color: color-mix(in oklab, currentColor, var(--wa-color-mix-active));
}

.label:focus {
  outline: none;
}

.label:focus-visible {
  outline: var(--wa-focus-ring);
  outline-offset: var(--wa-focus-ring-offset);
}

.start,
.end {
  display: none;
  flex: 0 0 auto;
  display: flex;
  align-items: center;
}

.start,
.end {
  display: inline-flex;
  color: var(--wa-color-text-quiet);
}

::slotted([slot='start']) {
  margin-inline-end: var(--wa-space-s);
}

::slotted([slot='end']) {
  margin-inline-start: var(--wa-space-s);
}

:host(:last-of-type) .separator {
  display: none;
}

.separator {
  color: var(--wa-color-text-quiet);
  display: inline-flex;
  align-items: center;
  margin: 0 var(--wa-space-s);
  user-select: none;
  -webkit-user-select: none;
}
`,el=class extends tt{constructor(){super(...arguments),this.renderType=`button`,this.rel=`noreferrer noopener`}setRenderType(){let e=this.defaultSlot.assignedElements({flatten:!0}).filter(e=>e.tagName.toLowerCase()===`wa-dropdown`).length>0;if(this.href){this.renderType=`link`;return}if(e){this.renderType=`dropdown`;return}this.renderType=`button`}hrefChanged(){this.setRenderType()}handleSlotChange(){this.setRenderType()}render(){return d`
      <span part="start" class="start">
        <slot name="start"></slot>
      </span>

      ${this.renderType===`link`?d`
            <a
              part="label"
              class="label label-link"
              href="${this.href}"
              target="${Wo(this.target?this.target:void 0)}"
              rel=${Wo(this.target?this.rel:void 0)}
            >
              <slot></slot>
            </a>
          `:``}
      ${this.renderType===`button`?d`
            <button part="label" type="button" class="label label-button">
              <slot @slotchange=${this.handleSlotChange}></slot>
            </button>
          `:``}
      ${this.renderType===`dropdown`?d`
            <div part="label" class="label label-dropdown">
              <slot @slotchange=${this.handleSlotChange}></slot>
            </div>
          `:``}

      <span part="end" class="end">
        <slot name="end"></slot>
      </span>

      <span part="separator" class="separator" aria-hidden="true">
        <slot name="separator"></slot>
      </span>
    `}};el.css=$c,k([S(`slot:not([name])`)],el.prototype,`defaultSlot`,2),k([b()],el.prototype,`renderType`,2),k([y()],el.prototype,`href`,2),k([y()],el.prototype,`target`,2),k([y()],el.prototype,`rel`,2),k([mr(`href`,{waitUntilFirstUpdate:!0})],el.prototype,`hrefChanged`,1),el=k([x(`wa-breadcrumb-item`)],el);var tl=class extends el{static get styles(){return[el.styles,p`
        :host {
          --wa-font-weight-action: 400;
          --wa-space-s: var(--c-spacing-sm);
          white-space: nowrap;
          display: inline-flex;
          align-items: center;
          color: inherit;
        }

        .start {
          margin-inline-end: var(--c-spacing-sm);
        }

        .end {
          margin-inline-start: var(--c-spacing-sm);
        }

        .separator {
          color: var(--c-text-quiet);
          margin: 0 var(--c-spacing-md);
        }
      `]}};customElements.get(`craft-breadcrumb-item`)||customElements.define(`craft-breadcrumb-item`,tl);var nl=`:host {
  --arrow-size: 0.375rem;
  --max-width: 25rem;
  --show-duration: 100ms;
  --hide-duration: 100ms;

  /* Internal calculated properties */
  --arrow-diagonal-size: calc((var(--arrow-size) * sin(45deg)));

  display: contents;

  /** Defaults for inherited CSS properties */
  font-size: var(--wa-font-size-m);
  line-height: var(--wa-line-height-normal);
  text-align: start;
  white-space: normal;
}

/* The native dialog element */
.dialog {
  display: none;
  position: fixed;
  inset: 0;
  width: 100%;
  height: 100%;
  margin: 0;
  padding: 0;
  border: none;
  background: transparent;
  overflow: visible;
  pointer-events: none;

  &:focus {
    outline: none;
  }

  &[open] {
    display: block;
  }
}

/* The <wa-popup> element */
.popover {
  --arrow-size: inherit;
  --show-duration: inherit;
  --hide-duration: inherit;

  pointer-events: auto;

  &::part(arrow) {
    background-color: var(--wa-color-surface-default);
    border-top: none;
    border-left: none;
    border-bottom: solid var(--wa-panel-border-width) var(--wa-color-surface-border);
    border-right: solid var(--wa-panel-border-width) var(--wa-color-surface-border);
    box-shadow: none;
  }
}

.popover[placement^='top']::part(popup) {
  transform-origin: bottom;
}

.popover[placement^='bottom']::part(popup) {
  transform-origin: top;
}

.popover[placement^='left']::part(popup) {
  transform-origin: right;
}

.popover[placement^='right']::part(popup) {
  transform-origin: left;
}

/* Body */
.body {
  display: flex;
  flex-direction: column;
  width: max-content;
  max-width: var(--max-width);
  padding: var(--wa-space-l);
  background-color: var(--wa-color-surface-default);
  border: var(--wa-panel-border-width) solid var(--wa-color-surface-border);
  border-radius: var(--wa-panel-border-radius);
  border-style: var(--wa-panel-border-style);
  box-shadow: var(--wa-shadow-l);
  color: var(--wa-color-text-normal);
  user-select: none;
  -webkit-user-select: none;
}
`,rl=new Set,il=class extends tt{constructor(){super(...arguments),this.anchor=null,this.placement=`top`,this.open=!1,this.distance=8,this.skidding=0,this.for=null,this.withoutArrow=!1,this.eventController=new AbortController,this.handleAnchorClick=()=>{this.open=!this.open},this.handleBodyClick=e=>{e.target.closest(`[data-popover="close"]`)&&(e.stopPropagation(),this.open=!1)},this.handleDocumentKeyDown=e=>{e.key===`Escape`&&(e.preventDefault(),this.open=!1,this.anchor&&typeof this.anchor.focus==`function`&&this.anchor.focus())},this.handleDocumentClick=e=>{let t=e.target;this.anchor&&e.composedPath().includes(this.anchor)||t.closest(`wa-popover`)!==this&&(this.open=!1)}}connectedCallback(){super.connectedCallback(),this.id||=dr(`wa-popover-`)}disconnectedCallback(){super.disconnectedCallback(),document.removeEventListener(`keydown`,this.handleDocumentKeyDown),this.eventController.abort()}firstUpdated(){this.open&&(this.dialog.show(),this.popup.active=!0,this.popup.reposition())}updated(e){e.has(`open`)&&this.customStates.set(`open`,this.open)}async handleOpenChange(){if(this.open){let e=new cr;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}rl.forEach(e=>e.open=!1),document.addEventListener(`keydown`,this.handleDocumentKeyDown,{signal:this.eventController.signal}),document.addEventListener(`click`,this.handleDocumentClick,{signal:this.eventController.signal}),this.dialog.show(),this.popup.active=!0,rl.add(this),requestAnimationFrame(()=>{let e=this.querySelector(`[autofocus]`);e&&typeof e.focus==`function`?e.focus():this.dialog.focus()}),await pr(this.popup.popup,`show-with-scale`),this.popup.reposition(),this.dispatchEvent(new or)}else{let e=new sr;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!0;return}document.removeEventListener(`keydown`,this.handleDocumentKeyDown),document.removeEventListener(`click`,this.handleDocumentClick),rl.delete(this),await pr(this.popup.popup,`hide-with-scale`),this.popup.active=!1,this.dialog.close(),this.dispatchEvent(new ar)}}handleForChange(){let e=this.getRootNode();if(!e)return;let t=this.for?e.getElementById(this.for):null,n=this.anchor;if(t===n)return;let{signal:r}=this.eventController;t&&t.addEventListener(`click`,this.handleAnchorClick,{signal:r}),n&&n.removeEventListener(`click`,this.handleAnchorClick),this.anchor=t,this.for&&!t&&console.warn(`A popover was assigned to an element with an ID of "${this.for}" but the element could not be found.`,this)}async handleOptionsChange(){this.hasUpdated&&(await this.updateComplete,this.popup.reposition())}async show(){if(!this.open)return this.open=!0,fr(this,`wa-after-show`)}async hide(){if(this.open)return this.open=!1,fr(this,`wa-after-hide`)}render(){return d`
      <dialog part="dialog" class="dialog">
        <wa-popup
          part="popup"
          exportparts="
            popup:popup__popup,
            arrow:popup__arrow
          "
          class=${T({popover:!0,"popover-open":this.open})}
          placement=${this.placement}
          distance=${this.distance}
          skidding=${this.skidding}
          flip
          shift
          ?arrow=${!this.withoutArrow}
          .anchor=${this.anchor}
        >
          <div part="body" class="body" @click=${this.handleBodyClick}>
            <slot></slot>
          </div>
        </wa-popup>
      </dialog>
    `}};il.css=nl,il.dependencies={"wa-popup":A},k([S(`dialog`)],il.prototype,`dialog`,2),k([S(`.body`)],il.prototype,`body`,2),k([S(`wa-popup`)],il.prototype,`popup`,2),k([b()],il.prototype,`anchor`,2),k([y()],il.prototype,`placement`,2),k([y({type:Boolean,reflect:!0})],il.prototype,`open`,2),k([y({type:Number})],il.prototype,`distance`,2),k([y({type:Number})],il.prototype,`skidding`,2),k([y()],il.prototype,`for`,2),k([y({attribute:`without-arrow`,type:Boolean,reflect:!0})],il.prototype,`withoutArrow`,2),k([mr(`open`,{waitUntilFirstUpdate:!0})],il.prototype,`handleOpenChange`,1),k([mr(`for`)],il.prototype,`handleForChange`,1),k([mr([`distance`,`placement`,`skidding`])],il.prototype,`handleOptionsChange`,1),il=k([x(`wa-popover`)],il);var al=class extends il{static get styles(){return[il.styles,p`
        :host {
          --wa-border-style: solid;
          --wa-border-width-s: 1px;
          --wa-color-surface-default: var(--c-surface-raised);
          --wa-color-surface-raised: var(--c-surface-raised);
          --wa-color-surface-border: var(--c-color-neutral-border-quiet);
          --wa-border-radius-m: var(--c-radius-lg);
        }

        .body {
          padding: var(--c-spacing-md);
        }
      `]}};customElements.get(`craft-popover`)||customElements.define(`craft-popover`,al);var ol=new Set;function sl(){let e=document.documentElement.clientWidth;return Math.abs(window.innerWidth-e)}function cl(){let e=Number(getComputedStyle(document.body).paddingRight.replace(/px/,``));return isNaN(e)||!e?0:e}function ll(e){if(ol.add(e),!document.documentElement.classList.contains(`wa-scroll-lock`)){let e=sl()+cl(),t=getComputedStyle(document.documentElement).scrollbarGutter;(!t||t===`auto`)&&(t=`stable`),e<2&&(t=``),document.documentElement.style.setProperty(`--wa-scroll-lock-gutter`,t),document.documentElement.classList.add(`wa-scroll-lock`),document.documentElement.style.setProperty(`--wa-scroll-lock-size`,`${e}px`)}}function ul(e){ol.delete(e),ol.size===0&&(document.documentElement.classList.remove(`wa-scroll-lock`),document.documentElement.style.removeProperty(`--wa-scroll-lock-size`))}function dl(e){return e.split(` `).map(e=>e.trim()).filter(e=>e!==``)}var fl=`:host {
  --size: 25rem;
  --spacing: var(--wa-space-l);
  --show-duration: 200ms;
  --hide-duration: 200ms;

  display: none;
}

:host([open]) {
  display: block;
}

.drawer {
  display: flex;
  flex-direction: column;
  top: 0;
  inset-inline-start: 0;
  width: 100%;
  height: 100%;
  max-width: 100%;
  max-height: 100%;
  overflow: hidden;
  background-color: var(--wa-color-surface-raised);
  border: none;
  box-shadow: var(--wa-shadow-l);
  overflow: auto;
  padding: 0;
  margin: 0;
  animation-duration: var(--show-duration);
  animation-timing-function: ease;

  &.show::backdrop {
    animation: show-backdrop var(--show-duration, 200ms) ease;
  }

  &.hide::backdrop {
    animation: show-backdrop var(--hide-duration, 200ms) ease reverse;
  }

  &.show.top {
    animation: show-drawer-from-top var(--show-duration) ease;
  }

  &.hide.top {
    animation: show-drawer-from-top var(--hide-duration) ease reverse;
  }

  &.show.end {
    animation: show-drawer-from-end var(--show-duration) ease;

    &:dir(rtl) {
      animation-name: show-drawer-from-start;
    }
  }

  &.hide.end {
    animation: show-drawer-from-end var(--hide-duration) ease reverse;

    &:dir(rtl) {
      animation-name: show-drawer-from-start;
    }
  }

  &.show.bottom {
    animation: show-drawer-from-bottom var(--show-duration) ease;
  }

  &.hide.bottom {
    animation: show-drawer-from-bottom var(--hide-duration) ease reverse;
  }

  &.show.start {
    animation: show-drawer-from-start var(--show-duration) ease;

    &:dir(rtl) {
      animation-name: show-drawer-from-end;
    }
  }

  &.hide.start {
    animation: show-drawer-from-start var(--hide-duration) ease reverse;

    &:dir(rtl) {
      animation-name: show-drawer-from-end;
    }
  }

  &.pulse {
    animation: pulse 250ms ease;
  }
}

.drawer:focus {
  outline: none;
}

.top {
  top: 0;
  inset-inline-end: auto;
  bottom: auto;
  inset-inline-start: 0;
  width: 100%;
  height: var(--size);
}

.end {
  top: 0;
  inset-inline-end: 0;
  bottom: auto;
  inset-inline-start: auto;
  width: var(--size);
  height: 100%;
}

.bottom {
  top: auto;
  inset-inline-end: auto;
  bottom: 0;
  inset-inline-start: 0;
  width: 100%;
  height: var(--size);
}

.start {
  top: 0;
  inset-inline-end: auto;
  bottom: auto;
  inset-inline-start: 0;
  width: var(--size);
  height: 100%;
}

.header {
  display: flex;
  flex-wrap: nowrap;
  padding-inline-start: var(--spacing);
  padding-block-end: 0;

  /* Subtract the close button's padding so that the X is visually aligned with the edges of the dialog content */
  padding-inline-end: calc(var(--spacing) - var(--wa-form-control-padding-block));
  padding-block-start: calc(var(--spacing) - var(--wa-form-control-padding-block));
}

.title {
  align-self: center;
  flex: 1 1 auto;
  font: inherit;
  font-size: var(--wa-font-size-l);
  font-weight: var(--wa-font-weight-heading);
  line-height: var(--wa-line-height-condensed);
  margin: 0;
}

.header-actions {
  align-self: start;
  display: flex;
  flex-shrink: 0;
  flex-wrap: wrap;
  justify-content: end;
  gap: var(--wa-space-2xs);
  padding-inline-start: var(--spacing);
}

.header-actions wa-button,
.header-actions ::slotted(wa-button) {
  flex: 0 0 auto;
  display: flex;
  align-items: center;
}

.body {
  flex: 1 1 auto;
  display: block;
  padding: var(--spacing);
  overflow: auto;
  -webkit-overflow-scrolling: touch;

  &:focus {
    outline: none;
  }

  &:focus-visible {
    outline: var(--wa-focus-ring);
    outline-offset: var(--wa-focus-ring-offset);
  }
}

.footer {
  display: flex;
  flex-wrap: wrap;
  gap: var(--wa-space-xs);
  justify-content: end;
  padding: var(--spacing);
  padding-block-start: 0;
}

.footer ::slotted(wa-button:not(:last-of-type)) {
  margin-inline-end: var(--wa-spacing-xs);
}

.drawer::backdrop {
  /*
      NOTE: the ::backdrop element doesn't inherit properly in Safari yet, but it will in 17.4! At that time, we can
      remove the fallback values here.
    */
  background-color: var(--wa-color-overlay-modal, rgb(0 0 0 / 0.25));
}

@keyframes pulse {
  0% {
    scale: 1;
  }
  50% {
    scale: 1.01;
  }
  100% {
    scale: 1;
  }
}

@keyframes show-drawer {
  from {
    opacity: 0;
    scale: 0.8;
  }
  to {
    opacity: 1;
    scale: 1;
  }
}

@keyframes show-drawer-from-top {
  from {
    opacity: 0;
    translate: 0 -100%;
  }
  to {
    opacity: 1;
    translate: 0 0;
  }
}

@keyframes show-drawer-from-end {
  from {
    opacity: 0;
    translate: 100%;
  }
  to {
    opacity: 1;
    translate: 0 0;
  }
}

@keyframes show-drawer-from-bottom {
  from {
    opacity: 0;
    translate: 0 100%;
  }
  to {
    opacity: 1;
    translate: 0 0;
  }
}

@keyframes show-drawer-from-start {
  from {
    opacity: 0;
    translate: -100% 0;
  }
  to {
    opacity: 1;
    translate: 0 0;
  }
}

@keyframes show-backdrop {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@media (forced-colors: active) {
  .drawer {
    border: solid 1px white;
  }
}
`,pl=class extends tt{constructor(){super(...arguments),this.localize=new Ee(this),this.hasSlotController=new Ec(this,`footer`,`header-actions`,`label`),this.open=!1,this.label=``,this.placement=`end`,this.withoutHeader=!1,this.lightDismiss=!0,this.handleDocumentKeyDown=e=>{e.key===`Escape`&&this.open&&(e.preventDefault(),e.stopPropagation(),this.requestClose(this.drawer))}}firstUpdated(){this.open&&(this.addOpenListeners(),this.drawer.showModal(),ll(this))}disconnectedCallback(){super.disconnectedCallback(),ul(this),this.removeOpenListeners()}async requestClose(e){let t=new sr({source:e});if(this.dispatchEvent(t),t.defaultPrevented){this.open=!0,pr(this.drawer,`pulse`);return}this.removeOpenListeners(),await pr(this.drawer,`hide`),this.open=!1,this.drawer.close(),ul(this);let n=this.originalTrigger;typeof n?.focus==`function`&&setTimeout(()=>n.focus()),this.dispatchEvent(new ar)}addOpenListeners(){document.addEventListener(`keydown`,this.handleDocumentKeyDown)}removeOpenListeners(){document.removeEventListener(`keydown`,this.handleDocumentKeyDown)}handleDialogCancel(e){e.preventDefault(),!this.drawer.classList.contains(`hide`)&&e.target===this.drawer&&this.requestClose(this.drawer)}handleDialogClick(e){let t=e.target.closest(`[data-drawer="close"]`);t&&(e.stopPropagation(),this.requestClose(t))}async handleDialogPointerDown(e){e.target===this.drawer&&(this.lightDismiss?this.requestClose(this.drawer):await pr(this.drawer,`pulse`))}handleOpenChange(){this.open&&!this.drawer.open?this.show():this.drawer.open&&(this.open=!0,this.requestClose(this.drawer))}async show(){let e=new cr;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}this.addOpenListeners(),this.originalTrigger=document.activeElement,this.open=!0,this.drawer.showModal(),ll(this),requestAnimationFrame(()=>{let e=this.querySelector(`[autofocus]`);e&&typeof e.focus==`function`?e.focus():this.drawer.focus()}),await pr(this.drawer,`show`),this.dispatchEvent(new or)}render(){let e=!this.withoutHeader,t=this.hasSlotController.test(`footer`);return d`
      <dialog
        part="dialog"
        class=${T({drawer:!0,open:this.open,top:this.placement===`top`,end:this.placement===`end`,bottom:this.placement===`bottom`,start:this.placement===`start`})}
        @cancel=${this.handleDialogCancel}
        @click=${this.handleDialogClick}
        @pointerdown=${this.handleDialogPointerDown}
      >
        ${e?d`
              <header part="header" class="header">
                <h2 part="title" class="title" id="title">
                  <!-- If there's no label, use an invisible character to prevent the header from collapsing -->
                  <slot name="label"> ${this.label.length>0?this.label:`​`} </slot>
                </h2>
                <div part="header-actions" class="header-actions">
                  <slot name="header-actions"></slot>
                  <wa-button
                    part="close-button"
                    exportparts="base:close-button__base"
                    class="close"
                    appearance="plain"
                    @click="${e=>this.requestClose(e.target)}"
                  >
                    <wa-icon
                      name="xmark"
                      label=${this.localize.term(`close`)}
                      library="system"
                      variant="solid"
                    ></wa-icon>
                  </wa-button>
                </div>
              </header>
            `:``}

        <div part="body" class="body"><slot></slot></div>

        ${t?d`
              <footer part="footer" class="footer">
                <slot name="footer"></slot>
              </footer>
            `:``}
      </dialog>
    `}};pl.css=fl,k([S(`.drawer`)],pl.prototype,`drawer`,2),k([y({type:Boolean,reflect:!0})],pl.prototype,`open`,2),k([y({reflect:!0})],pl.prototype,`label`,2),k([y({reflect:!0})],pl.prototype,`placement`,2),k([y({attribute:`without-header`,type:Boolean,reflect:!0})],pl.prototype,`withoutHeader`,2),k([y({attribute:`light-dismiss`,type:Boolean})],pl.prototype,`lightDismiss`,2),k([mr(`open`,{waitUntilFirstUpdate:!0})],pl.prototype,`handleOpenChange`,1),pl=k([x(`wa-drawer`)],pl),document.addEventListener(`click`,e=>{let t=e.target.closest(`[data-drawer]`);if(t instanceof Element){let[e,n]=dl(t.getAttribute(`data-drawer`)||``);if(e===`open`&&n?.length){let e=t.getRootNode().getElementById(n);e?.localName===`wa-drawer`?e.open=!0:console.warn(`A drawer with an ID of "${n}" could not be found in this document.`)}}}),document.body.addEventListener(`pointerdown`,()=>{});var ml=()=>({checkValidity(e){let t=e.input,n={message:``,isValid:!0,invalidKeys:[]};if(!t)return n;let r=!0;if(`checkValidity`in t&&(r=t.checkValidity()),r)return n;if(n.isValid=!1,`validationMessage`in t&&(n.message=t.validationMessage),!(`validity`in t))return n.invalidKeys.push(`customError`),n;for(let e in t.validity){if(e===`valid`)continue;let r=e;t.validity[r]&&n.invalidKeys.push(r)}return n}}),hl=class extends Event{constructor(){super(`wa-invalid`,{bubbles:!0,cancelable:!1,composed:!0})}},gl=()=>({observedAttributes:[`custom-error`],checkValidity(e){let t={message:``,isValid:!0,invalidKeys:[]};return e.customError&&(t.message=e.customError,t.isValid=!1,t.invalidKeys=[`customError`]),t}}),_l=class extends tt{constructor(){super(),this.name=null,this.disabled=!1,this.required=!1,this.assumeInteractionOn=[`input`],this.validators=[],this.valueHasChanged=!1,this.hasInteracted=!1,this.customError=null,this.emittedEvents=[],this.emitInvalid=e=>{e.target===this&&(this.hasInteracted=!0,this.dispatchEvent(new hl))},this.handleInteraction=e=>{let t=this.emittedEvents;t.includes(e.type)||t.push(e.type),t.length===this.assumeInteractionOn?.length&&(this.hasInteracted=!0)},this.addEventListener(`invalid`,this.emitInvalid)}static get validators(){return[gl()]}static get observedAttributes(){let e=new Set(super.observedAttributes||[]);for(let t of this.validators)if(t.observedAttributes)for(let n of t.observedAttributes)e.add(n);return[...e]}connectedCallback(){super.connectedCallback(),this.updateValidity(),this.assumeInteractionOn.forEach(e=>{this.addEventListener(e,this.handleInteraction)})}firstUpdated(...e){super.firstUpdated(...e),this.updateValidity()}willUpdate(e){if(e.has(`customError`)&&(this.customError||=null,this.setCustomValidity(this.customError||``)),e.has(`value`)||e.has(`disabled`)){let e=this.value;if(Array.isArray(e)){if(this.name){let t=new FormData;for(let n of e)t.append(this.name,n);this.setValue(t,t)}}else this.setValue(e,e)}e.has(`disabled`)&&(this.customStates.set(`disabled`,this.disabled),(this.hasAttribute(`disabled`)||!this.matches(`:disabled`))&&this.toggleAttribute(`disabled`,this.disabled)),this.updateValidity(),super.willUpdate(e)}get labels(){return this.internals.labels}getForm(){return this.internals.form}get validity(){return this.internals.validity}get willValidate(){return this.internals.willValidate}get validationMessage(){return this.internals.validationMessage}checkValidity(){return this.updateValidity(),this.internals.checkValidity()}reportValidity(){return this.updateValidity(),this.hasInteracted=!0,this.internals.reportValidity()}get validationTarget(){return this.input||void 0}setValidity(...e){let t=e[0],n=e[1],r=e[2];r||=this.validationTarget,this.internals.setValidity(t,n,r||void 0),this.requestUpdate(`validity`),this.setCustomStates()}setCustomStates(){let e=!!this.required,t=this.internals.validity.valid,n=this.hasInteracted;this.customStates.set(`required`,e),this.customStates.set(`optional`,!e),this.customStates.set(`invalid`,!t),this.customStates.set(`valid`,t),this.customStates.set(`user-invalid`,!t&&n),this.customStates.set(`user-valid`,t&&n)}setCustomValidity(e){if(!e){this.customError=null,this.setValidity({});return}this.customError=e,this.setValidity({customError:!0},e,this.validationTarget)}formResetCallback(){this.resetValidity(),this.hasInteracted=!1,this.valueHasChanged=!1,this.emittedEvents=[],this.updateValidity()}formDisabledCallback(e){this.disabled=e,this.updateValidity()}formStateRestoreCallback(e,t){this.value=e,t===`restore`&&this.resetValidity(),this.updateValidity()}setValue(...e){let[t,n]=e;this.internals.setFormValue(t,n)}get allValidators(){let e=this.constructor.validators||[],t=this.validators||[];return[...e,...t]}resetValidity(){this.setCustomValidity(``),this.setValidity({})}updateValidity(){if(this.disabled||this.hasAttribute(`disabled`)||!this.willValidate){this.resetValidity();return}let e=this.allValidators;if(!e?.length)return;let t={customError:!!this.customError},n=this.validationTarget||this.input||void 0,r=``;for(let n of e){let{isValid:e,message:i,invalidKeys:a}=n.checkValidity(this);e||(r||=i,a?.length>=0&&a.forEach(e=>t[e]=!0))}r||=this.validationMessage,this.setValidity(t,r,n)}};_l.formAssociated=!0,k([y({reflect:!0})],_l.prototype,`name`,2),k([y({type:Boolean})],_l.prototype,`disabled`,2),k([y({state:!0,attribute:!1})],_l.prototype,`valueHasChanged`,2),k([y({state:!0,attribute:!1})],_l.prototype,`hasInteracted`,2),k([y({attribute:`custom-error`,reflect:!0})],_l.prototype,`customError`,2),k([y({attribute:!1,state:!0,type:Object})],_l.prototype,`validity`,1);var vl=`@layer wa-utilities {
  :where(:root),
  .wa-neutral,
  :host([variant='neutral']) {
    --wa-color-fill-loud: var(--wa-color-neutral-fill-loud);
    --wa-color-fill-normal: var(--wa-color-neutral-fill-normal);
    --wa-color-fill-quiet: var(--wa-color-neutral-fill-quiet);
    --wa-color-border-loud: var(--wa-color-neutral-border-loud);
    --wa-color-border-normal: var(--wa-color-neutral-border-normal);
    --wa-color-border-quiet: var(--wa-color-neutral-border-quiet);
    --wa-color-on-loud: var(--wa-color-neutral-on-loud);
    --wa-color-on-normal: var(--wa-color-neutral-on-normal);
    --wa-color-on-quiet: var(--wa-color-neutral-on-quiet);
  }

  .wa-brand,
  :host([variant='brand']) {
    --wa-color-fill-loud: var(--wa-color-brand-fill-loud);
    --wa-color-fill-normal: var(--wa-color-brand-fill-normal);
    --wa-color-fill-quiet: var(--wa-color-brand-fill-quiet);
    --wa-color-border-loud: var(--wa-color-brand-border-loud);
    --wa-color-border-normal: var(--wa-color-brand-border-normal);
    --wa-color-border-quiet: var(--wa-color-brand-border-quiet);
    --wa-color-on-loud: var(--wa-color-brand-on-loud);
    --wa-color-on-normal: var(--wa-color-brand-on-normal);
    --wa-color-on-quiet: var(--wa-color-brand-on-quiet);
  }

  .wa-success,
  :host([variant='success']) {
    --wa-color-fill-loud: var(--wa-color-success-fill-loud);
    --wa-color-fill-normal: var(--wa-color-success-fill-normal);
    --wa-color-fill-quiet: var(--wa-color-success-fill-quiet);
    --wa-color-border-loud: var(--wa-color-success-border-loud);
    --wa-color-border-normal: var(--wa-color-success-border-normal);
    --wa-color-border-quiet: var(--wa-color-success-border-quiet);
    --wa-color-on-loud: var(--wa-color-success-on-loud);
    --wa-color-on-normal: var(--wa-color-success-on-normal);
    --wa-color-on-quiet: var(--wa-color-success-on-quiet);
  }

  .wa-warning,
  :host([variant='warning']) {
    --wa-color-fill-loud: var(--wa-color-warning-fill-loud);
    --wa-color-fill-normal: var(--wa-color-warning-fill-normal);
    --wa-color-fill-quiet: var(--wa-color-warning-fill-quiet);
    --wa-color-border-loud: var(--wa-color-warning-border-loud);
    --wa-color-border-normal: var(--wa-color-warning-border-normal);
    --wa-color-border-quiet: var(--wa-color-warning-border-quiet);
    --wa-color-on-loud: var(--wa-color-warning-on-loud);
    --wa-color-on-normal: var(--wa-color-warning-on-normal);
    --wa-color-on-quiet: var(--wa-color-warning-on-quiet);
  }

  .wa-danger,
  :host([variant='danger']) {
    --wa-color-fill-loud: var(--wa-color-danger-fill-loud);
    --wa-color-fill-normal: var(--wa-color-danger-fill-normal);
    --wa-color-fill-quiet: var(--wa-color-danger-fill-quiet);
    --wa-color-border-loud: var(--wa-color-danger-border-loud);
    --wa-color-border-normal: var(--wa-color-danger-border-normal);
    --wa-color-border-quiet: var(--wa-color-danger-border-quiet);
    --wa-color-on-loud: var(--wa-color-danger-on-loud);
    --wa-color-on-normal: var(--wa-color-danger-on-normal);
    --wa-color-on-quiet: var(--wa-color-danger-on-quiet);
  }
}
`,yl=Symbol.for(``),bl=e=>{if(e?.r===yl)return e?._$litStatic$},xl=(e,...t)=>({_$litStatic$:t.reduce(((t,n,r)=>t+(e=>{if(e._$litStatic$!==void 0)return e._$litStatic$;throw Error(`Value passed to 'literal' function must be a 'literal' result: ${e}. Use 'unsafeStatic' to pass non-literal values, but\n            take care to ensure page security.`)})(n)+e[r+1]),e[0]),r:yl}),Sl=new Map,Cl=(e=>(t,...n)=>{let r=n.length,i,a,o=[],s=[],c,l=0,u=!1;for(;l<r;){for(c=t[l];l<r&&(a=n[l],i=bl(a))!==void 0;)c+=i+t[++l],u=!0;l!==r&&s.push(a),o.push(c),l++}if(l===r&&o.push(t[r]),u){let e=o.join(`$$lit$$`);(t=Sl.get(e))===void 0&&(o.raw=o,Sl.set(e,t=o)),n=s}return e(t,...n)})(d),wl=`@layer wa-component {
  :host {
    display: inline-block;

    /* Workaround because Chrome doesn't like :host(:has()) below
     * https://issues.chromium.org/issues/40062355
     * Firefox doesn't like this nested rule, so both are needed */
    &:has(wa-badge) {
      position: relative;
    }
  }

  /* Apply relative positioning only when needed to position wa-badge
   * This avoids creating a new stacking context for every button */
  :host(:has(wa-badge)) {
    position: relative;
  }
}

.button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  user-select: none;
  -webkit-user-select: none;
  white-space: nowrap;
  vertical-align: middle;
  transition-property: background, border, box-shadow, color;
  transition-duration: var(--wa-transition-fast);
  transition-timing-function: var(--wa-transition-easing);
  cursor: pointer;
  padding: 0 var(--wa-form-control-padding-inline);
  font-family: inherit;
  font-size: inherit;
  font-weight: var(--wa-font-weight-action);
  line-height: calc(var(--wa-form-control-height) - var(--border-width) * 2);
  height: var(--wa-form-control-height);
  width: 100%;

  background-color: var(--wa-color-fill-loud, var(--wa-color-neutral-fill-loud));
  border-color: transparent;
  color: var(--wa-color-on-loud, var(--wa-color-neutral-on-loud));
  border-radius: var(--wa-form-control-border-radius);
  border-style: var(--wa-border-style);
  border-width: var(--wa-border-width-s);
}

/* Appearance modifiers */
:host([appearance='plain']) {
  .button {
    color: var(--wa-color-on-quiet, var(--wa-color-neutral-on-quiet));
    background-color: transparent;
    border-color: transparent;
  }
  @media (hover: hover) {
    .button:not(.disabled):not(.loading):hover {
      color: var(--wa-color-on-quiet, var(--wa-color-neutral-on-quiet));
      background-color: var(--wa-color-fill-quiet, var(--wa-color-neutral-fill-quiet));
    }
  }
  .button:not(.disabled):not(.loading):active {
    color: var(--wa-color-on-quiet, var(--wa-color-neutral-on-quiet));
    background-color: color-mix(
      in oklab,
      var(--wa-color-fill-quiet, var(--wa-color-neutral-fill-quiet)),
      var(--wa-color-mix-active)
    );
  }
}

:host([appearance='outlined']) {
  .button {
    color: var(--wa-color-on-quiet, var(--wa-color-neutral-on-quiet));
    background-color: transparent;
    border-color: var(--wa-color-border-loud, var(--wa-color-neutral-border-loud));
  }
  @media (hover: hover) {
    .button:not(.disabled):not(.loading):hover {
      color: var(--wa-color-on-quiet, var(--wa-color-neutral-on-quiet));
      background-color: var(--wa-color-fill-quiet, var(--wa-color-neutral-fill-quiet));
    }
  }
  .button:not(.disabled):not(.loading):active {
    color: var(--wa-color-on-quiet, var(--wa-color-neutral-on-quiet));
    background-color: color-mix(
      in oklab,
      var(--wa-color-fill-quiet, var(--wa-color-neutral-fill-quiet)),
      var(--wa-color-mix-active)
    );
  }
}

:host([appearance='filled']) {
  .button {
    color: var(--wa-color-on-normal, var(--wa-color-neutral-on-normal));
    background-color: var(--wa-color-fill-normal, var(--wa-color-neutral-fill-normal));
    border-color: transparent;
  }
  @media (hover: hover) {
    .button:not(.disabled):not(.loading):hover {
      color: var(--wa-color-on-normal, var(--wa-color-neutral-on-normal));
      background-color: color-mix(
        in oklab,
        var(--wa-color-fill-normal, var(--wa-color-neutral-fill-normal)),
        var(--wa-color-mix-hover)
      );
    }
  }
  .button:not(.disabled):not(.loading):active {
    color: var(--wa-color-on-normal, var(--wa-color-neutral-on-normal));
    background-color: color-mix(
      in oklab,
      var(--wa-color-fill-normal, var(--wa-color-neutral-fill-normal)),
      var(--wa-color-mix-active)
    );
  }
}

:host([appearance='filled-outlined']) {
  .button {
    color: var(--wa-color-on-normal, var(--wa-color-neutral-on-normal));
    background-color: var(--wa-color-fill-normal, var(--wa-color-neutral-fill-normal));
    border-color: var(--wa-color-border-normal, var(--wa-color-neutral-border-normal));
  }
  @media (hover: hover) {
    .button:not(.disabled):not(.loading):hover {
      color: var(--wa-color-on-normal, var(--wa-color-neutral-on-normal));
      background-color: color-mix(
        in oklab,
        var(--wa-color-fill-normal, var(--wa-color-neutral-fill-normal)),
        var(--wa-color-mix-hover)
      );
    }
  }
  .button:not(.disabled):not(.loading):active {
    color: var(--wa-color-on-normal, var(--wa-color-neutral-on-normal));
    background-color: color-mix(
      in oklab,
      var(--wa-color-fill-normal, var(--wa-color-neutral-fill-normal)),
      var(--wa-color-mix-active)
    );
  }
}

:host([appearance='accent']) {
  .button {
    color: var(--wa-color-on-loud, var(--wa-color-neutral-on-loud));
    background-color: var(--wa-color-fill-loud, var(--wa-color-neutral-fill-loud));
    border-color: transparent;
  }
  @media (hover: hover) {
    .button:not(.disabled):not(.loading):hover {
      background-color: color-mix(
        in oklab,
        var(--wa-color-fill-loud, var(--wa-color-neutral-fill-loud)),
        var(--wa-color-mix-hover)
      );
    }
  }
  .button:not(.disabled):not(.loading):active {
    background-color: color-mix(
      in oklab,
      var(--wa-color-fill-loud, var(--wa-color-neutral-fill-loud)),
      var(--wa-color-mix-active)
    );
  }
}

/* Focus states */
.button:focus {
  outline: none;
}

.button:focus-visible {
  outline: var(--wa-focus-ring);
  outline-offset: var(--wa-focus-ring-offset);
}

/* Disabled state */
.button.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* When disabled, prevent mouse events from bubbling up from children */
.button.disabled * {
  pointer-events: none;
}

/* Keep it last so Safari doesn't stop parsing this block */
.button::-moz-focus-inner {
  border: 0;
}

/* Icon buttons */
.button.is-icon-button {
  outline-offset: 2px;
  width: var(--wa-form-control-height);
  aspect-ratio: 1;
}

.button.is-icon-button:has(wa-icon) {
  width: auto;
}

/* Pill modifier */
:host([pill]) .button {
  border-radius: var(--wa-border-radius-pill);
}

/*
 * Label
 */

.start,
.end {
  flex: 0 0 auto;
  display: flex;
  align-items: center;
  pointer-events: none;
}

.label {
  display: inline-block;
}

.is-icon-button .label {
  display: flex;
}

.label::slotted(wa-icon) {
  align-self: center;
}

/*
 * Caret modifier
 */

wa-icon[part='caret'] {
  display: flex;
  align-self: center;
  align-items: center;

  &::part(svg) {
    width: 0.875em;
    height: 0.875em;
  }

  .button:has(&) .end {
    display: none;
  }
}

/*
 * Loading modifier
 */

.loading {
  position: relative;
  cursor: wait;

  .start,
  .label,
  .end,
  .caret {
    visibility: hidden;
  }

  wa-spinner {
    --indicator-color: currentColor;
    --track-color: color-mix(in oklab, currentColor, transparent 90%);

    position: absolute;
    font-size: 1em;
    height: 1em;
    width: 1em;
    top: calc(50% - 0.5em);
    left: calc(50% - 0.5em);
  }
}

/*
 * Badges
 */

.button ::slotted(wa-badge) {
  border-color: var(--wa-color-surface-default);
  position: absolute;
  inset-block-start: 0;
  inset-inline-end: 0;
  translate: 50% -50%;
  pointer-events: none;
}

:host(:dir(rtl)) ::slotted(wa-badge) {
  translate: -50% -50%;
}

/*
* Button spacing
*/

slot[name='start']::slotted(*) {
  margin-inline-end: 0.75em;
}

slot[name='end']::slotted(*),
.button:not(.visually-hidden-label) [part='caret'] {
  margin-inline-start: 0.75em;
}

/*
 * Button group border radius modifications
 */

/* Remove border radius from all grouped buttons by default */
:host(.wa-button-group__button) .button {
  border-radius: 0;
}

/* Horizontal orientation */
:host(.wa-button-group__horizontal.wa-button-group__button-first) .button {
  border-start-start-radius: var(--wa-form-control-border-radius);
  border-end-start-radius: var(--wa-form-control-border-radius);
}

:host(.wa-button-group__horizontal.wa-button-group__button-last) .button {
  border-start-end-radius: var(--wa-form-control-border-radius);
  border-end-end-radius: var(--wa-form-control-border-radius);
}

/* Vertical orientation */
:host(.wa-button-group__vertical) {
  flex: 1 1 auto;
}

:host(.wa-button-group__vertical) .button {
  width: 100%;
  justify-content: start;
}

:host(.wa-button-group__vertical.wa-button-group__button-first) .button {
  border-start-start-radius: var(--wa-form-control-border-radius);
  border-start-end-radius: var(--wa-form-control-border-radius);
}

:host(.wa-button-group__vertical.wa-button-group__button-last) .button {
  border-end-start-radius: var(--wa-form-control-border-radius);
  border-end-end-radius: var(--wa-form-control-border-radius);
}

/* Handle pill modifier for button groups */
:host([pill].wa-button-group__horizontal.wa-button-group__button-first) .button {
  border-start-start-radius: var(--wa-border-radius-pill);
  border-end-start-radius: var(--wa-border-radius-pill);
}

:host([pill].wa-button-group__horizontal.wa-button-group__button-last) .button {
  border-start-end-radius: var(--wa-border-radius-pill);
  border-end-end-radius: var(--wa-border-radius-pill);
}

:host([pill].wa-button-group__vertical.wa-button-group__button-first) .button {
  border-start-start-radius: var(--wa-border-radius-pill);
  border-start-end-radius: var(--wa-border-radius-pill);
}

:host([pill].wa-button-group__vertical.wa-button-group__button-last) .button {
  border-end-start-radius: var(--wa-border-radius-pill);
  border-end-end-radius: var(--wa-border-radius-pill);
}
`,L=class extends _l{constructor(){super(...arguments),this.assumeInteractionOn=[`click`],this.hasSlotController=new Ec(this,`[default]`,`start`,`end`),this.localize=new Ee(this),this.invalid=!1,this.isIconButton=!1,this.title=``,this.variant=`neutral`,this.appearance=`accent`,this.size=`medium`,this.withCaret=!1,this.disabled=!1,this.loading=!1,this.pill=!1,this.type=`button`,this.form=null}static get validators(){return[...super.validators,ml()]}constructLightDOMButton(){let e=document.createElement(`button`);return e.type=this.type,e.style.position=`absolute`,e.style.width=`0`,e.style.height=`0`,e.style.clipPath=`inset(50%)`,e.style.overflow=`hidden`,e.style.whiteSpace=`nowrap`,this.name&&(e.name=this.name),e.value=this.value||``,[`form`,`formaction`,`formenctype`,`formmethod`,`formnovalidate`,`formtarget`].forEach(t=>{this.hasAttribute(t)&&e.setAttribute(t,this.getAttribute(t))}),e}handleClick(){if(!this.getForm())return;let e=this.constructLightDOMButton();this.parentElement?.append(e),e.click(),e.remove()}handleInvalid(){this.dispatchEvent(new hl)}handleLabelSlotChange(){let e=this.labelSlot.assignedNodes({flatten:!0}),t=!1,n=!1,r=!1,i=!1;[...e].forEach(e=>{if(e.nodeType===Node.ELEMENT_NODE){let r=e;r.localName===`wa-icon`?(n=!0,t||=r.label!==void 0):i=!0}else e.nodeType===Node.TEXT_NODE&&(e.textContent?.trim()||``).length>0&&(r=!0)}),this.isIconButton=n&&!r&&!i,this.isIconButton&&!t&&console.warn(`Icon buttons must have a label for screen readers. Add <wa-icon label="..."> to remove this warning.`,this)}isButton(){return!this.href}isLink(){return!!this.href}handleDisabledChange(){this.updateValidity()}setValue(...e){}click(){this.button.click()}focus(e){this.button.focus(e)}blur(){this.button.blur()}render(){let e=this.isLink(),t=e?xl`a`:xl`button`;return Cl`
      <${t}
        part="base"
        class=${T({button:!0,caret:this.withCaret,disabled:this.disabled,loading:this.loading,rtl:this.localize.dir()===`rtl`,"has-label":this.hasSlotController.test(`[default]`),"has-start":this.hasSlotController.test(`start`),"has-end":this.hasSlotController.test(`end`),"is-icon-button":this.isIconButton})}
        ?disabled=${Wo(e?void 0:this.disabled)}
        type=${Wo(e?void 0:this.type)}
        title=${this.title}
        name=${Wo(e?void 0:this.name)}
        value=${Wo(e?void 0:this.value)}
        href=${Wo(e?this.href:void 0)}
        target=${Wo(e?this.target:void 0)}
        download=${Wo(e?this.download:void 0)}
        rel=${Wo(e&&this.rel?this.rel:void 0)}
        role=${Wo(e?void 0:`button`)}
        aria-disabled=${this.disabled?`true`:`false`}
        tabindex=${this.disabled?`-1`:`0`}
        @invalid=${this.isButton()?this.handleInvalid:null}
        @click=${this.handleClick}
      >
        <slot name="start" part="start" class="start"></slot>
        <slot part="label" class="label" @slotchange=${this.handleLabelSlotChange}></slot>
        <slot name="end" part="end" class="end"></slot>
        ${this.withCaret?Cl`
                <wa-icon part="caret" class="caret" library="system" name="chevron-down" variant="solid"></wa-icon>
              `:``}
        ${this.loading?Cl`<wa-spinner part="spinner"></wa-spinner>`:``}
      </${t}>
    `}};L.shadowRootOptions={..._l.shadowRootOptions,delegatesFocus:!0},L.css=[wl,vl,bc],k([S(`.button`)],L.prototype,`button`,2),k([S(`slot:not([name])`)],L.prototype,`labelSlot`,2),k([b()],L.prototype,`invalid`,2),k([b()],L.prototype,`isIconButton`,2),k([y()],L.prototype,`title`,2),k([y({reflect:!0})],L.prototype,`variant`,2),k([y({reflect:!0})],L.prototype,`appearance`,2),k([y({reflect:!0})],L.prototype,`size`,2),k([y({attribute:`with-caret`,type:Boolean,reflect:!0})],L.prototype,`withCaret`,2),k([y({type:Boolean})],L.prototype,`disabled`,2),k([y({type:Boolean,reflect:!0})],L.prototype,`loading`,2),k([y({type:Boolean,reflect:!0})],L.prototype,`pill`,2),k([y()],L.prototype,`type`,2),k([y({reflect:!0})],L.prototype,`name`,2),k([y({reflect:!0})],L.prototype,`value`,2),k([y({reflect:!0})],L.prototype,`href`,2),k([y()],L.prototype,`target`,2),k([y()],L.prototype,`rel`,2),k([y()],L.prototype,`download`,2),k([y({reflect:!0})],L.prototype,`form`,2),k([y({attribute:`formaction`})],L.prototype,`formAction`,2),k([y({attribute:`formenctype`})],L.prototype,`formEnctype`,2),k([y({attribute:`formmethod`})],L.prototype,`formMethod`,2),k([y({attribute:`formnovalidate`,type:Boolean})],L.prototype,`formNoValidate`,2),k([y({attribute:`formtarget`})],L.prototype,`formTarget`,2),k([mr(`disabled`,{waitUntilFirstUpdate:!0})],L.prototype,`handleDisabledChange`,1),L=k([x(`wa-button`)],L);var Tl=`:host {
  --track-width: 2px;
  --track-color: var(--wa-color-neutral-fill-normal);
  --indicator-color: var(--wa-color-brand-fill-loud);
  --speed: 2s;

  /* Resizing a spinner element using anything but font-size will break the animation because the animation uses em units.
   Therefore, if a spinner is used in a flex container without \`flex: none\` applied, the spinner can grow/shrink and
   break the animation. The use of \`flex: none\` on the host element prevents this by always having the spinner sized
   according to its actual dimensions.
  */
  flex: none;
  display: inline-flex;
  width: 1em;
  height: 1em;
}

svg {
  width: 100%;
  height: 100%;
  aspect-ratio: 1;
  animation: spin var(--speed) linear infinite;
}

.track {
  stroke: var(--track-color);
}

.indicator {
  stroke: var(--indicator-color);
  stroke-dasharray: 75, 100;
  stroke-dashoffset: -5;
  animation: dash 1.5s ease-in-out infinite;
  stroke-linecap: round;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

@keyframes dash {
  0% {
    stroke-dasharray: 1, 150;
    stroke-dashoffset: 0;
  }
  50% {
    stroke-dasharray: 90, 150;
    stroke-dashoffset: -35;
  }
  100% {
    stroke-dasharray: 90, 150;
    stroke-dashoffset: -124;
  }
}
`,El=class extends tt{constructor(){super(...arguments),this.localize=new Ee(this)}render(){return d`
      <svg
        part="base"
        role="progressbar"
        aria-label=${this.localize.term(`loading`)}
        fill="none"
        viewBox="0 0 50 50"
        xmlns="http://www.w3.org/2000/svg"
      >
        <circle class="track" cx="25" cy="25" r="20" fill="none" stroke-width="5" />
        <circle class="indicator" cx="25" cy="25" r="20" fill="none" stroke-width="5" />
      </svg>
    `}};El.css=Tl,El=k([x(`wa-spinner`)],El);var Dl=class extends pl{static get styles(){return[pl.styles,p`
        :host {
          --wa-color-surface-raised: var(--c-surface-raised);
          --spacing: var(--c-spacing-lg);
        }
      `]}};customElements.get(`craft-drawer`)||customElements.define(`craft-drawer`,Dl);var Ol=`:host {
  --width: 31rem;
  --spacing: var(--wa-space-l);
  --show-duration: 200ms;
  --hide-duration: 200ms;

  display: none;
}

:host([open]) {
  display: block;
}

.dialog {
  display: flex;
  flex-direction: column;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  width: var(--width);
  max-width: calc(100% - var(--wa-space-2xl));
  max-height: calc(100% - var(--wa-space-2xl));
  background-color: var(--wa-color-surface-raised);
  border-radius: var(--wa-panel-border-radius);
  border: none;
  box-shadow: var(--wa-shadow-l);
  padding: 0;
  margin: auto;

  &.show {
    animation: show-dialog var(--show-duration) ease;

    &::backdrop {
      animation: show-backdrop var(--show-duration, 200ms) ease;
    }
  }

  &.hide {
    animation: show-dialog var(--hide-duration) ease reverse;

    &::backdrop {
      animation: show-backdrop var(--hide-duration, 200ms) ease reverse;
    }
  }

  &.pulse {
    animation: pulse 250ms ease;
  }
}

.dialog:focus {
  outline: none;
}

/* Ensure there's enough vertical padding for phones that don't update vh when chrome appears (e.g. iPhone) */
@media screen and (max-width: 420px) {
  .dialog {
    max-height: 80vh;
  }
}

.open {
  display: flex;
  opacity: 1;
}

.header {
  flex: 0 0 auto;
  display: flex;
  flex-wrap: nowrap;

  padding-inline-start: var(--spacing);
  padding-block-end: 0;

  /* Subtract the close button's padding so that the X is visually aligned with the edges of the dialog content */
  padding-inline-end: calc(var(--spacing) - var(--wa-form-control-padding-block));
  padding-block-start: calc(var(--spacing) - var(--wa-form-control-padding-block));
}

.title {
  align-self: center;
  flex: 1 1 auto;
  font-family: inherit;
  font-size: var(--wa-font-size-l);
  font-weight: var(--wa-font-weight-heading);
  line-height: var(--wa-line-height-condensed);
  margin: 0;
}

.header-actions {
  align-self: start;
  display: flex;
  flex-shrink: 0;
  flex-wrap: wrap;
  justify-content: end;
  gap: var(--wa-space-2xs);
  padding-inline-start: var(--spacing);
}

.header-actions wa-button,
.header-actions ::slotted(wa-button) {
  flex: 0 0 auto;
  display: flex;
  align-items: center;
}

.body {
  flex: 1 1 auto;
  display: block;
  padding: var(--spacing);
  overflow: auto;
  -webkit-overflow-scrolling: touch;

  &:focus {
    outline: none;
  }

  &:focus-visible {
    outline: var(--wa-focus-ring);
    outline-offset: var(--wa-focus-ring-offset);
  }
}

.footer {
  flex: 0 0 auto;
  display: flex;
  flex-wrap: wrap;
  gap: var(--wa-space-xs);
  justify-content: end;
  padding: var(--spacing);
  padding-block-start: 0;
}

.footer ::slotted(wa-button:not(:first-of-type)) {
  margin-inline-start: var(--wa-spacing-xs);
}

.dialog::backdrop {
  /*
    NOTE: the ::backdrop element doesn't inherit properly in Safari yet, but it will in 17.4! At that time, we can
    remove the fallback values here.
  */
  background-color: var(--wa-color-overlay-modal, rgb(0 0 0 / 0.25));
}

@keyframes pulse {
  0% {
    scale: 1;
  }
  50% {
    scale: 1.02;
  }
  100% {
    scale: 1;
  }
}

@keyframes show-dialog {
  from {
    opacity: 0;
    scale: 0.8;
  }
  to {
    opacity: 1;
    scale: 1;
  }
}

@keyframes show-backdrop {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@media (forced-colors: active) {
  .dialog {
    border: solid 1px white;
  }
}
`,kl=class extends tt{constructor(){super(...arguments),this.localize=new Ee(this),this.hasSlotController=new Ec(this,`footer`,`header-actions`,`label`),this.open=!1,this.label=``,this.withoutHeader=!1,this.lightDismiss=!1,this.handleDocumentKeyDown=e=>{e.key===`Escape`&&this.open&&(e.preventDefault(),e.stopPropagation(),this.requestClose(this.dialog))}}firstUpdated(){this.open&&(this.addOpenListeners(),this.dialog.showModal(),ll(this))}disconnectedCallback(){super.disconnectedCallback(),ul(this),this.removeOpenListeners()}async requestClose(e){let t=new sr({source:e});if(this.dispatchEvent(t),t.defaultPrevented){this.open=!0,pr(this.dialog,`pulse`);return}this.removeOpenListeners(),await pr(this.dialog,`hide`),this.open=!1,this.dialog.close(),ul(this);let n=this.originalTrigger;typeof n?.focus==`function`&&setTimeout(()=>n.focus()),this.dispatchEvent(new ar)}addOpenListeners(){document.addEventListener(`keydown`,this.handleDocumentKeyDown)}removeOpenListeners(){document.removeEventListener(`keydown`,this.handleDocumentKeyDown)}handleDialogCancel(e){e.preventDefault(),!this.dialog.classList.contains(`hide`)&&e.target===this.dialog&&this.requestClose(this.dialog)}handleDialogClick(e){let t=e.target.closest(`[data-dialog="close"]`);t&&(e.stopPropagation(),this.requestClose(t))}async handleDialogPointerDown(e){e.target===this.dialog&&(this.lightDismiss?this.requestClose(this.dialog):await pr(this.dialog,`pulse`))}handleOpenChange(){this.open&&!this.dialog.open?this.show():!this.open&&this.dialog.open&&(this.open=!0,this.requestClose(this.dialog))}async show(){let e=new cr;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}this.addOpenListeners(),this.originalTrigger=document.activeElement,this.open=!0,this.dialog.showModal(),ll(this),requestAnimationFrame(()=>{let e=this.querySelector(`[autofocus]`);e&&typeof e.focus==`function`?e.focus():this.dialog.focus()}),await pr(this.dialog,`show`),this.dispatchEvent(new or)}render(){let e=!this.withoutHeader,t=this.hasSlotController.test(`footer`);return d`
      <dialog
        part="dialog"
        class=${T({dialog:!0,open:this.open})}
        @cancel=${this.handleDialogCancel}
        @click=${this.handleDialogClick}
        @pointerdown=${this.handleDialogPointerDown}
      >
        ${e?d`
              <header part="header" class="header">
                <h2 part="title" class="title" id="title">
                  <!-- If there's no label, use an invisible character to prevent the header from collapsing -->
                  <slot name="label"> ${this.label.length>0?this.label:`​`} </slot>
                </h2>
                <div part="header-actions" class="header-actions">
                  <slot name="header-actions"></slot>
                  <wa-button
                    part="close-button"
                    exportparts="base:close-button__base"
                    class="close"
                    appearance="plain"
                    @click="${e=>this.requestClose(e.target)}"
                  >
                    <wa-icon
                      name="xmark"
                      label=${this.localize.term(`close`)}
                      library="system"
                      variant="solid"
                    ></wa-icon>
                  </wa-button>
                </div>
              </header>
            `:``}

        <div part="body" class="body"><slot></slot></div>

        ${t?d`
              <footer part="footer" class="footer">
                <slot name="footer"></slot>
              </footer>
            `:``}
      </dialog>
    `}};kl.css=Ol,k([S(`.dialog`)],kl.prototype,`dialog`,2),k([y({type:Boolean,reflect:!0})],kl.prototype,`open`,2),k([y({reflect:!0})],kl.prototype,`label`,2),k([y({attribute:`without-header`,type:Boolean,reflect:!0})],kl.prototype,`withoutHeader`,2),k([y({attribute:`light-dismiss`,type:Boolean})],kl.prototype,`lightDismiss`,2),k([mr(`open`,{waitUntilFirstUpdate:!0})],kl.prototype,`handleOpenChange`,1),kl=k([x(`wa-dialog`)],kl),document.addEventListener(`click`,e=>{let t=e.target.closest(`[data-dialog]`);if(t instanceof Element){let[e,n]=dl(t.getAttribute(`data-dialog`)||``);if(e===`open`&&n?.length){let e=t.getRootNode().getElementById(n);e?.localName===`wa-dialog`?e.open=!0:console.warn(`A dialog with an ID of "${n}" could not be found in this document.`)}}}),document.addEventListener(`pointerdown`,()=>{});var Al=class extends kl{static get styles(){return[kl.styles,p`
        :host {
          --spacing: var(--c-spacing-lg);
          --wa-space-2xl: var(--c-spacing-xl);
        }

        .title {
          font-size: 1.25em;
        }

        .header {
          padding-inline: var(--c-spacing-lg);
          padding-block-start: var(--c-spacing-lg);
          padding-block-end: var(--c-spacing-md);
        }
      `]}};customElements.get(`craft-dialog`)||customElements.define(`craft-dialog`,Al);var jl=class extends Io(Bo(v)){constructor(){super(),this.multipleChoice=!0}},Ml=class extends Ro(Vo){connectedCallback(){super.connectedCallback(),this.type=`checkbox`}},Nl=class extends Ml{static get styles(){return[...super.styles||[],p`
        :host .choice-field__nested-checkboxes {
          display: block;
        }
        ::slotted(*) {
          padding-left: 8px;
        }
      `]}static get properties(){return{indeterminate:{type:Boolean,reflect:!0},mixedState:{type:Boolean,reflect:!0,attribute:`mixed-state`}}}get _checkboxGroupNode(){return this._parentFormGroup}get _subCheckboxes(){return this.__subCheckboxes}_storeIndeterminateState(){this._indeterminateSubStates=this._subCheckboxes.map(e=>e.checked)}_setOldState(){this.indeterminate?this._oldState=`indeterminate`:this._oldState=this.checked?`checked`:`unchecked`}_setOwnCheckedState(){let e=this._subCheckboxes;if(!e.length)return;this.__settingOwnChecked=!0;let t=e.filter(e=>e.checked);switch(e.length-t.length){case 0:this.indeterminate=!1,this.checked=!0;break;case e.length:this.indeterminate=!1,this.checked=!1;break;default:{this.indeterminate=!0;let n=e.filter(e=>e.disabled&&e.checked===!1);this.checked=e.length-t.length-n.length===0}}this.updateComplete.then(()=>{this.__settingOwnChecked=!1})}_setBasedOnMixedState(){switch(this._oldState){case`checked`:this.checked=!1,this.indeterminate=!1;break;case`unchecked`:this.checked=!1,this.indeterminate=!0;break;case`indeterminate`:this.checked=!0,this.indeterminate=!1;break}}__onModelValueChanged(e){if(!this.disabled){if(e.detail.formPath[0]===this&&!this.__settingOwnChecked){this.mixedState&&!(e=>e.every(t=>t===e[0]))(this._indeterminateSubStates)&&this._setBasedOnMixedState(),this.__settingOwnSubs=!0;let e=this._subCheckboxes,t=e.filter(e=>e.checked),n=e.filter(e=>e.disabled),r=e.length>0&&e.length===t.length;e.length>0&&e.length===n.length&&(this.checked=r),this.indeterminate&&this.mixedState?this._subCheckboxes.forEach((e,t)=>{e.checked=this._indeterminateSubStates[t]}):this._subCheckboxes.filter(e=>!e.disabled).forEach(e=>{e.checked=this._inputNode.checked}),this.updateComplete.then(()=>{this.__settingOwnSubs=!1})}else this._setOwnCheckedState(),this.updateComplete.then(()=>{!this.__settingOwnSubs&&!this.__settingOwnChecked&&this.mixedState&&this._storeIndeterminateState()});this.mixedState&&this._setOldState()}}_afterTemplate(){return d`
      <div class="choice-field__nested-checkboxes" role="list">
        <slot></slot>
      </div>
    `}_onRequestToAddFormElement(e){e.target.hasAttribute(`role`)||e.target?.setAttribute(`role`,`listitem`),this.__addToSubCheckboxes(e.detail.element),this._setOwnCheckedState()}_onRequestToRemoveFormElement(e){e.target.getAttribute(`role`)===`listitem`&&e.target?.removeAttribute(`role`),this.__removeFromSubCheckboxes(e.detail.element)}__addToSubCheckboxes(e){e!==this&&this.contains(e)&&this.__subCheckboxes.push(e)}__removeFromSubCheckboxes(e){let t=this.__subCheckboxes.indexOf(e);t!==-1&&this.__subCheckboxes.splice(t,1)}constructor(){super(),this.indeterminate=!1,this._onRequestToAddFormElement=this._onRequestToAddFormElement.bind(this),this.__onModelValueChanged=this.__onModelValueChanged.bind(this),this.__subCheckboxes=[],this._indeterminateSubStates=[],this.mixedState=!1}connectedCallback(){super.connectedCallback(),this.addEventListener(`model-value-changed`,this.__onModelValueChanged),this.addEventListener(`form-element-register`,this._onRequestToAddFormElement)}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener(`model-value-changed`,this.__onModelValueChanged),this.removeEventListener(`form-element-register`,this._onRequestToAddFormElement)}firstUpdated(e){super.firstUpdated(e),this._setOldState(),this.indeterminate&&this._storeIndeterminateState()}updated(e){super.updated(e),(e.has(`indeterminate`)||e.has(`checked`))&&(this._inputNode.indeterminate=this.indeterminate)}},Pl=class extends jl{static get styles(){return[...jl.styles,p`
        .input-group {
          display: grid;
          gap: var(--c-spacing-sm);
        }

        .form-field__group-two {
          margin-top: var(--c-spacing-sm);
        }

        ::slotted(label) {
          font-weight: bold;
        }
      `]}};customElements.get(`craft-checkbox-group`)||customElements.define(`craft-checkbox-group`,Pl);var Fl=class extends Ml{static get styles(){return[...Ml.styles,p`
        /* same as radio, potentially consolidate */
        :host {
          display: grid;
          align-items: center;
          gap: 0 var(--c-spacing-md);
          grid-template-areas: 'input label' '. help-text';
          grid-template-columns: auto 1fr;
          grid-template-rows: repeat(2, auto);
        }

        ::slotted(label) {
          font: inherit;
          grid-area: label;
        }

        ::slotted([slot='input']) {
          background-color: var(--c-input-fill, var(--c-form-control-fill));
          border-width: var(
            --c-input-border-width,
            var(--c-form-control-border-width)
          );
          border-style: var(
            --c-input-border-style,
            var(--c-form-control-border-style)
          );
          border-color: var(
            --c-input-border-color,
            var(--c-form-control-border-color)
          );
          border-radius: var(--c-input-radius, var(--c-radius-sm));
        }

        .choice-field__help-text {
          font-size: 1em;
          color: var(--c-text-quiet);
          grid-area: help-text;
        }
      `]}};customElements.get(`craft-checkbox`)||customElements.define(`craft-checkbox`,Fl);var Il=class extends Nl{static get styles(){return[...Nl.styles,p`
        :host {
          display: flex;
          align-items: center;
          gap: 0 var(--c-spacing-md);
        }

        ::slotted(label) {
          font-weight: bold;
        }

        ::slotted(*) {
          padding-left: 0;
        }
      `]}};customElements.get(`craft-checkbox-indeterminate`)||customElements.define(`craft-checkbox-indeterminate`,Il);var Ll=p`
  :host([variant='default']) {
    --c-color-fill-loud: var(--c-color-neutral-fill-loud);
    --c-color-fill-normal: var(--c-color-neutral-fill-normal);
    --c-color-fill-quiet: var(--c-color-neutral-fill-quiet);
    --c-color-border-loud: var(--c-color-neutral-border-loud);
    --c-color-border-normal: var(--c-color-neutral-border-normal);
    --c-color-border-quiet: var(--c-color-neutral-border-quiet);
    --c-color-on-loud: var(--c-color-neutral-on-loud);
    --c-color-on-normal: var(--c-color-neutral-on-normal);
    --c-color-on-quiet: var(--c-color-neutral-on-quiet);
  }

  :host([variant='danger']) {
    --c-color-fill-loud: var(--c-color-danger-fill-loud);
    --c-color-fill-normal: var(--c-color-danger-fill-normal);
    --c-color-fill-quiet: var(--c-color-danger-fill-quiet);
    --c-color-border-loud: var(--c-color-danger-border-loud);
    --c-color-border-normal: var(--c-color-danger-border-normal);
    --c-color-border-quiet: var(--c-color-danger-border-quiet);
    --c-color-on-loud: var(--c-color-danger-on-loud);
    --c-color-on-normal: var(--c-color-danger-on-normal);
    --c-color-on-quiet: var(--c-color-danger-on-quiet);
  }

  :host([variant='info']) {
    --c-color-fill-loud: var(--c-color-info-fill-loud);
    --c-color-fill-normal: var(--c-color-info-fill-normal);
    --c-color-fill-quiet: var(--c-color-info-fill-quiet);
    --c-color-border-loud: var(--c-color-info-border-loud);
    --c-color-border-normal: var(--c-color-info-border-normal);
    --c-color-border-quiet: var(--c-color-info-border-quiet);
    --c-color-on-loud: var(--c-color-info-on-loud);
    --c-color-on-normal: var(--c-color-info-on-normal);
    --c-color-on-quiet: var(--c-color-info-on-quiet);
  }

  :host([variant='warning']) {
    --c-color-fill-loud: var(--c-color-warning-fill-loud);
    --c-color-fill-normal: var(--c-color-warning-fill-normal);
    --c-color-fill-quiet: var(--c-color-warning-fill-quiet);
    --c-color-border-loud: var(--c-color-warning-border-loud);
    --c-color-border-normal: var(--c-color-warning-border-normal);
    --c-color-border-quiet: var(--c-color-warning-border-quiet);
    --c-color-on-loud: var(--c-color-warning-on-loud);
    --c-color-on-normal: var(--c-color-warning-on-normal);
    --c-color-on-quiet: var(--c-color-warning-on-quiet);
  }

  :host([variant='success']) {
    --c-color-fill-loud: var(--c-color-success-fill-loud);
    --c-color-fill-normal: var(--c-color-success-fill-normal);
    --c-color-fill-quiet: var(--c-color-success-fill-quiet);
    --c-color-border-loud: var(--c-color-success-border-loud);
    --c-color-border-normal: var(--c-color-success-border-normal);
    --c-color-border-quiet: var(--c-color-success-border-quiet);
    --c-color-on-loud: var(--c-color-success-on-loud);
    --c-color-on-normal: var(--c-color-success-on-normal);
    --c-color-on-quiet: var(--c-color-success-on-quiet);
  }
`,Rl=p`
  :host {
    --c-color-fill-loud: var(--c-color-neutral-fill-loud);
    --c-color-fill-normal: var(--c-color-neutral-fill-normal);
    --c-color-fill-quiet: var(--c-color-neutral-fill-quiet);
    --c-color-border-loud: var(--c-color-neutral-border-loud);
    --c-color-border-normal: var(--c-color-neutral-border-normal);
    --c-color-border-quiet: var(--c-color-neutral-border-quiet);
    --c-color-on-loud: var(--c-color-neutral-on-loud);
    --c-color-on-normal: var(--c-color-neutral-on-normal);
    --c-color-on-quiet: var(--c-color-neutral-on-quiet);
    --_radius: var(--c-callout-radius, var(--c-radius-md));
    display: flex;
    gap: var(--c-spacing-sm);
    align-items: start;
    padding: var(--c-spacing-sm) var(--c-spacing-md);
    border: 1px solid transparent;
  }

  .callout__body {
    display: grid;
    gap: var(--c-spacing-sm);
  }

  .callout__title {
    font-weight: bold;
  }

  .callout__icon {
    width: auto;
    height: 1lh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
  }

  ::slotted(code) {
    font-size: 0.9em;
    display: inline-flex;
    padding: 0 var(--c-spacing-sm);
    border: 1px solid rgba(0, 0, 0, 0.2);
    background-color: rgba(0, 0, 0, 0.05);
    border-radius: var(--c-radius-sm);
  }

  :host([inline]) {
    display: inline-flex;
    padding-inline: var(--c-spacing-sm);
    padding-block: 0;
    line-height: 1.25rem;
    font-size: 0.9em;
  }

  :host([rounded~='all']) {
    border-radius: var(--_radius);
  }

  :host([rounded~='none']) {
    border-radius: 0;
  }

  :host([rounded~='start']) {
    border-start-start-radius: var(--_radius);
    border-start-end-radius: var(--_radius);
  }

  :host([rounded~='end']) {
    border-end-start-radius: var(--_radius);
    border-end-end-radius: var(--_radius);
  }

  :host([appearance~='accent']) {
    --c-text-link: var(--c-color-on-loud);
    background-color: var(--c-color-fill-loud);
    color: var(--c-color-on-loud);
    border-color: var(--c-color-border-loud);
  }

  :host([appearance~='fill']) {
    --c-text-link: var(--c-color-on-normal);
    border-color: transparent;
    background-color: var(--c-color-fill-normal);
    color: var(--c-color-on-normal);
  }

  :host([appearance~='outline-fill']) {
    --c-text-link: var(--c-color-on-normal);
    border-color: var(--c-color-border-normal);
    background-color: var(--c-color-fill-normal);
    color: var(--c-color-on-normal);
  }

  :host([appearance~='outline']) {
    --c-text-link: var(--c-color-on-quiet);
    border-color: var(--c-color-border-quiet);
    background-color: transparent;
    color: var(--c-color-on-quiet);
  }

  :host([appearance~='plain']) {
    --c-text-link: var(--c-color-on-quiet);
    background-color: transparent;
    border-color: transparent;
    color: var(--c-color-on-quiet);
  }
`,zl=class extends v{constructor(...t){super(...t),this.variant=a.Default,this.appearance=e.OutlineFill,this.title=``,this.icon=null,this.rounded=`all`,this.inline=!1}getDefaultIcon(){switch(this.variant){case a.Info:return`lightbulb`;case a.Success:return`circle-check`;case a.Warning:return`circle-exclamation`;case a.Danger:return`triangle-exclamation`;default:return null}}render(){return d`
      ${this.icon||this.querySelector(`[slot="icon"]`)?d`<slot name="icon" class="callout__icon">
            <craft-icon
              name="${this.getDefaultIcon()}"
              style="font-size: 0.9em"
            ></craft-icon>
          </slot>`:_}
      <div class="callout__body">
        <slot name="title" class="callout__title">${this.title}</slot>
        <div class="callout__description">
          <slot></slot>
        </div>
      </div>
    `}};zl.styles=[Ll,Rl],l([y({reflect:!0})],zl.prototype,`variant`,void 0),l([y({reflect:!0})],zl.prototype,`appearance`,void 0),l([y()],zl.prototype,`title`,void 0),l([y()],zl.prototype,`icon`,void 0),l([y({reflect:!0})],zl.prototype,`rounded`,void 0),l([y({reflect:!0,type:Boolean})],zl.prototype,`inline`,void 0),customElements.get(`craft-callout`)||customElements.define(`craft-callout`,zl);var Bl=p`
  :host {
    display: contents;
  }

  .action-item {
    border-color: var(--c-color-border-quiet, transparent);
    color: var(--c-color-on-quiet, inherit);
    background-color: transparent;

    font: inherit;
    text-align: left;
    display: flex;
    width: 100%;
    align-items: center;
    text-decoration: none;
    padding-inline: var(--c-spacing-sm);
    padding-block: var(--c-spacing-sm);
    border-radius: var(--c-radius-md);
    position: relative;
    border-width: 0;
    border-style: solid;
  }

  @media (hover: hover) {
    :host(:hover) .action-item:not(:disabled) {
      background-color: var(
        --c-color-fill-quiet,
        var(--c-color-neutral-fill-quiet)
      );
      color: var(--c-color-on-quiet, var(--c-color-neutral-on-quiet));
    }
  }

  :host([active]) .action-item {
    background-color: var(--c-color-fill-loud);
    color: var(--c-color-on-loud);
  }

  .action-item:disabled {
    opacity: 0.5;
  }

  .action-item:not(:disabled) {
    cursor: pointer;
  }

  .action-item__check,
  .action-item__icon,
  .action-item__suffix {
    min-height: 1lh;
  }

  .action-item__check,
  .action-item__icon {
    min-width: 1lh;
    display: inline-grid;
    place-items: center;
    align-self: start;
  }

  .action-item__check {
    aspect-ratio: 1;
  }

  .action-item__suffix {
    align-self: center;
  }

  .action-item__label {
    flex: 1 1 auto;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    margin-inline: var(--c-spacing-sm);
  }

  :host([variant='danger']) .action-item {
    color: var(--c-color-on-quiet);
  }

  @media (hover: hover) {
    :host(:hover[variant='danger']) .action-item:not(:disabled) {
      background-color: var(--c-color-fill-quiet);
      color: var(--c-color-on-quiet);
    }
  }
`,Vl=class extends v{constructor(...e){super(...e),this.icon=null,this.href=null,this.disabled=!1,this.variant=a.Default,this.checked=!1,this.active=!1,this.type=`normal`}renderBody(){let e=!!this.querySelector(`[slot="icon"]`)||!!this.icon;return d`
      ${this.type===`checkbox`?d` <span class="action-item__check">
            <slot name="checkmark">
              ${this.checked?d`<craft-icon name="check"></craft-icon>`:_}
            </slot>
          </span>`:_}
      ${e?d`<span class="action-item__icon">
            <slot name="icon">
              ${this.icon?d`<craft-icon name="${this.icon}"></craft-icon>`:_}
            </slot>
          </span>`:_}

      <span class="action-item__label">
        <slot></slot>
      </span>

      <span class="action-item__suffix">
        <slot name="suffix"></slot>
      </span>
    `}render(){return this.href?d`
          <a
            class="${T({"action-item":!0,"action-item--checkbox":this.type===`checkbox`})}"
            href="${this.href}"
          >
            ${this.renderBody()}
          </a>
        `:d`
          <button
            type="button"
            class="${T({"action-item":!0,"action-item--checkbox":this.type===`checkbox`})}"
            ?disabled="${this.disabled}"
          >
            ${this.renderBody()}
          </button>
        `}};Vl.styles=[Ll,Bl],l([y()],Vl.prototype,`icon`,void 0),l([y()],Vl.prototype,`href`,void 0),l([y({type:Boolean})],Vl.prototype,`disabled`,void 0),l([y({reflect:!0})],Vl.prototype,`variant`,void 0),l([y({type:Boolean})],Vl.prototype,`checked`,void 0),l([y({type:Boolean})],Vl.prototype,`active`,void 0),l([y()],Vl.prototype,`type`,void 0),customElements.get(`craft-action-item`)||customElements.define(`craft-action-item`,Vl);var Hl=p`
  body > *[inert] {
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    pointer-events: none;
  }

  body.overlays-scroll-lock {
    overflow: hidden;
  }

  body.overlays-scroll-lock-ios-fix {
    position: fixed;
    width: 100%;
  }

  html.overlays-scroll-lock-ios-fix {
    height: 100vh;
  }
`,Ul=class e{static __createGlobalStyleNode(){let e=document.createElement(`style`);return e.setAttribute(`data-overlays`,``),e.textContent=Hl.cssText,document.head.appendChild(e),e}get list(){return this.__list}get shownList(){return this.__shownList}constructor(){this.__list=[],this.__shownList=[],this.__siblingsInert=!1,this.__blockingMap=new WeakMap,e.__globalStyleNode||=e.__createGlobalStyleNode()}add(e){if(this.list.find(t=>e===t))throw Error(`controller instance is already added`);return this.list.push(e),e}remove(e){if(!this.list.find(t=>e===t))throw Error(`could not find controller to remove`);this.__list=this.list.filter(t=>t!==e),this.__shownList=this.shownList.filter(t=>t!==e)}show(e){this.list.find(t=>e===t)&&this.hide(e),this.__shownList.unshift(e),Array.from(this.__shownList).reverse().forEach((e,t)=>{e.elevation=t+1})}hide(e){if(!this.list.find(t=>e===t))throw Error(`could not find controller to hide`);this.__shownList=this.shownList.filter(t=>t!==e)}teardown(){this.list.forEach(e=>{e.teardown()}),this.__list=[],this.__shownList=[],this.__siblingsInert=!1,e.__globalStyleNode&&=(document.head.removeChild(e.__globalStyleNode),void 0)}get siblingsInert(){return this.__siblingsInert}disableTrapsKeyboardFocusForAll(){this.shownList.forEach(e=>{e.trapsKeyboardFocus===!0&&e.disableTrapsKeyboardFocus&&e.disableTrapsKeyboardFocus({findNewTrap:!1})})}informTrapsKeyboardFocusGotEnabled(e){this.siblingsInert===!1&&e===`global`&&(this.__siblingsInert=!0)}informTrapsKeyboardFocusGotDisabled({disabledCtrl:e,findNewTrap:t=!0}={}){let n=this.shownList.find(t=>t!==e&&t.trapsKeyboardFocus===!0);n?t&&n.enableTrapsKeyboardFocus():this.siblingsInert===!0&&(this.__siblingsInert=!1)}requestToPreventScroll(){let{isIOS:e,isMacSafari:t}=Ur;document.body.classList.add(`overlays-scroll-lock`),(e||t)&&document.body.classList.add(`overlays-scroll-lock-ios-fix`),e&&document.documentElement.classList.add(`overlays-scroll-lock-ios-fix`)}requestToEnableScroll(){if(this.shownList.some(e=>e.preventsScroll===!0))return;let{isIOS:e,isMacSafari:t}=Ur;document.body.classList.remove(`overlays-scroll-lock`),(e||t)&&document.body.classList.remove(`overlays-scroll-lock-ios-fix`),e&&document.documentElement.classList.remove(`overlays-scroll-lock-ios-fix`)}requestToShowOnly(e){let t=this.shownList.filter(t=>t!==e);t.forEach(e=>e.hide()),this.__blockingMap.set(e,t)}retractRequestToShowOnly(e){this.__blockingMap.has(e)&&this.__blockingMap.get(e).forEach(e=>e.show())}};Ul.__globalStyleNode=void 0;var Wl=Ka.get(`@lion/ui::overlays::0.x`)||new Ul;function Gl(){let e=document.activeElement||document.body;for(;e&&e.shadowRoot&&e.shadowRoot.activeElement;)e=e.shadowRoot.activeElement;return e}var Kl=({visibility:e,display:t})=>e!==`hidden`&&t!==`none`,ql=({display:e})=>e===`contents`;function Jl(e){if(!e||!e.isConnected||!Kl(e.style))return!1;let t=window.getComputedStyle(e);return Kl(t)?ql(t)?!0:!!(e.offsetWidth||e.offsetHeight||e.getClientRects().length):!1}function Yl(e,t){let n=Math.max(e.tabIndex,0),r=Math.max(t.tabIndex,0);return n===0||r===0?r>n:n>r}function Xl(e,t){let n=[];for(;e.length>0&&t.length>0;)Yl(e[0],t[0])?n.push(t.shift()):n.push(e.shift());return[...n,...e,...t]}function Zl(e){let t=e.length;if(t<2)return e;let n=Math.ceil(t/2);return Xl(Zl(e.slice(0,n)),Zl(e.slice(n)))}var Ql=`matches`in Element.prototype?`matches`:`msMatchesSelector`;function $l(e){return e[Ql](`input, select, textarea, button, object`)?e[Ql](`:not([disabled])`):e[Ql](`a[href], area[href], iframe, [tabindex], [contentEditable]`)}function eu(e){return $l(e)?Number(e.getAttribute(`tabindex`)||0):-1}function tu(e){if(e.localName===`slot`)return e.assignedNodes({flatten:!0});let{children:t}=e.shadowRoot||e;return t||[]}function nu(e){return e.nodeType===Node.ELEMENT_NODE?e.localName===`slot`?!0:Jl(e):!1}function ru(e,t){if(!nu(e))return!1;let n=e,r=eu(n),i=r>0;r>=0&&t.push(n);let a=tu(n);for(let e=0;e<a.length;e+=1)i=ru(a[e],t)||i;return i}function iu(e){let t=[];return ru(e,t)?Zl(t):t}function au(e,t,n={}){function r(e){return`getAttribute`in e}function i(e){if(!r(e))return null;let t=e.getAttribute(`slot`),i=null;if(t){let r=n[t];r&&(i=r.filter(t=>t?.element===e)[0]||null)}return i}let a=i(e);if(a)return a.deepContains;function o(t){if(!r(e))return;let i=e.getAttribute(`slot`);i&&(n[i]=n[i]||[],n[i].push({element:e,deepContains:t}))}let s=e.contains(t);if(s)return o(!0),!0;function c(e){return e.tagName===`SLOT`}function l(e){return c(e)?e.assignedElements():[]}function u(e){return e.nodeType===Node.DOCUMENT_FRAGMENT_NODE}function d(e){let i=!1;for(let a=0;a<e.length;a+=1){let o=e[a];if(o&&(r(o)||u(o))&&au(o,t,n)){i=!0;break}}return i}function f(e){for(let t=0;t<e.children.length;t+=1){let n=e.children[t],r=i(n);if(r){s=r.deepContains||s;break}let a=l(n);if(d([n.shadowRoot,...a])){s=!0;break}n.children.length>0&&f(n)}}return e instanceof HTMLElement&&e.shadowRoot&&(s=au(e.shadowRoot,t,n),s)?(o(!0),!0):(f(e),o(s),s)}var ou={enter:13,space:32,escape:27,tab:9};function su(e,t){let n=iu(e),r;r=n.length>=2?[n[0],n[n.length-1]]:n.length===1?[n[0],n[0]]:[e,e],t.shiftKey&&r.reverse();let[i,a]=r,o=Gl();o===e||n.includes(o)&&a!==o||(t.preventDefault(),i.focus())}function cu(e){let t=iu(e),n=t.find(e=>e.hasAttribute(`autofocus`))||e,r,i;n===e&&(e.tabIndex=-1,e.style.setProperty(`outline`,`none`)),n.focus();function a(t){t.keyCode===ou.tab&&su(e,t)}function o(){r=document.createElement(`div`),r.style.display=`none`,r.setAttribute(`data-is-tab-detection-element`,``),e.insertBefore(r,e.children[0]),i=new MutationObserver(t=>{for(let n of t)if(n.type===`childList`){let t=!Array.from(e.children).find(e=>e.hasAttribute(`data-is-tab-detection-element`)),r=Array.from(n.addedNodes).find(e=>e instanceof HTMLElement&&e.hasAttribute(`data-is-tab-detection-element`));t&&!r&&(i.disconnect(),o())}}),i.observe(e,{childList:!0})}function s(){return r.compareDocumentPosition(document.activeElement)===Node.DOCUMENT_POSITION_PRECEDING}function c({resetToRoot:n=!1}={}){if(au(e,Gl()))return;let r;r=n?e:t[s()?0:t.length-1],r&&r.focus()}function l(){window.removeEventListener(`focusin`,l),c()}function u(){setTimeout(()=>{au(e,Gl())||c({resetToRoot:!0})}),window.addEventListener(`focusin`,l)}function d(){window.removeEventListener(`keydown`,a),window.removeEventListener(`focusin`,l),window.removeEventListener(`focusout`,u),i.disconnect(),Array.from(e.children).includes(r)&&e.removeChild(r),e.style.removeProperty(`outline`)}return window.addEventListener(`keydown`,a),window.addEventListener(`focusout`,u),o(),{disconnect:d}}var lu=p`
  .overlays {
    position: fixed;
    z-index: 200;
  }

  .overlays__overlay-container {
    display: flex;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
  }

  .overlays__overlay-container::backdrop {
    display: none;
  }

  .overlays__overlay-container--top-left {
    justify-content: flex-start;
    align-items: flex-start;
  }

  .overlays__overlay-container--top {
    justify-content: center;
    align-items: flex-start;
  }

  .overlays__overlay-container--top-right {
    justify-content: flex-end;
    align-items: flex-start;
  }

  .overlays__overlay-container--right {
    justify-content: flex-end;
    align-items: center;
  }

  .overlays__overlay-container--bottom-left {
    justify-content: flex-start;
    align-items: flex-end;
  }

  .overlays__overlay-container--bottom {
    justify-content: center;
    align-items: flex-end;
  }

  .overlays__overlay-container--bottom-right {
    justify-content: flex-end;
    align-items: flex-end;
  }

  .overlays__overlay-container--left {
    justify-content: flex-start;
    align-items: center;
  }

  .overlays__overlay-container--center {
    justify-content: center;
    align-items: center;
  }

  .overlays__overlay--bottom-sheet {
    width: 100%;
  }

  ::slotted(.overlays__overlay),
  .overlays__overlay {
    pointer-events: auto;
  }

  .overlays__backdrop {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: -1;
    background-color: #333333;
    display: none;
  }

  .overlays__backdrop--visible {
    display: block;
  }

  .overlays__backdrop--animation-in {
    animation: overlays-backdrop-fade-in 300ms;
    opacity: 0.3;
  }

  .overlays__backdrop--animation-out {
    animation: overlays-backdrop-fade-out 300ms;
    opacity: 0;
  }

  @keyframes overlays-backdrop-fade-in {
    from {
      opacity: 0;
    }
  }

  @keyframes overlays-backdrop-fade-out {
    from {
      opacity: 0.3;
    }
  }

  @media screen and (prefers-reduced-motion: reduce) {
    .overlays .overlays__backdrop--animation-in {
      animation: overlays-backdrop-fade-in 1ms;
    }

    .overlays .overlays__backdrop--animation-out {
      animation: overlays-backdrop-fade-out 1ms;
    }
  }

  dialog[data-overlay-outer-wrapper] {
    background-image: none;
    border-style: none;
    padding: 0px;
  }

  /** 
   * We don't want to use pseudo el ::backdrop.  
   * We have our own, that creates more flexibility wrt scrolling etc.
   */
  dialog[data-overlay-outer-wrapper]::backdrop {
    display: none;
  }
`,uu={supportsAdoptingStyleSheets:window.ShadowRoot&&(window.ShadyCSS===void 0||window.ShadyCSS.nativeShadow)&&`adoptedStyleSheets`in Document.prototype&&`replace`in CSSStyleSheet.prototype,adoptStyle:void 0,adoptStyles:void 0},du=new WeakMap;function fu(e){return Array.from(e.cssRules).map(e=>e.cssText).join(``)}function pu(e,t,{teardown:n=!1}={}){let r=e===document?document.body:e,i=t.cssText||fu(t);if(n){let e=Array.from(r.querySelectorAll(`style`));for(let t of e)if(t.textContent===i){t.remove();break}}else{let e=document.createElement(`style`),t=window.litNonce;t!==void 0&&e.setAttribute(`nonce`,t),e.textContent=i,r.appendChild(e)}}function mu(e,t,{teardown:n=!1}={}){let r=!1;e&&!du.has(e)&&du.set(e,[]);let i=du.get(e)??[],a=i.find(e=>t===e);return a&&n?i.splice(i.indexOf(t),1):!a&&!n?i.push(t):(a&&!n||!a&&n)&&(r=!0),{haltFurtherExecution:r}}function hu(e,t,{teardown:n=!1}={}){let{haltFurtherExecution:r}=mu(e,t,{teardown:n});if(r)return;if(!uu.supportsAdoptingStyleSheets||Ur.isIOS){pu(e,t,{teardown:n});return}let i=t instanceof CSSStyleSheet?t:t.styleSheet;if(!i)throw Error(`Please provide a CSSResultOrNative style`);n?e.adoptedStyleSheets.includes(i)&&e.adoptedStyleSheets.splice(e.adoptedStyleSheets.indexOf(i),1):e.adoptedStyleSheets=[...e.adoptedStyleSheets,i]}function gu(e,t,{teardown:n=!1}={}){for(let r of t)uu.adoptStyle(e,r,{teardown:n})}uu.adoptStyle=hu,uu.adoptStyles=gu;function _u({wrappingDialogNodeL1:e,contentWrapperNodeL2:t,contentNodeL3:n}){if(!(t.isConnected||n.isConnected))throw Error(`[OverlayController] Could not find a render target, since the provided contentNode is not connected to the DOM. Make sure that it is connected, e.g. by doing "document.body.appendChild(contentNode)", before passing it on.`);let r,i=document.createComment(`tempMarker`);t.isConnected?(r=t.parentElement||t.getRootNode(),r.insertBefore(i,t),e.appendChild(t)):n.assignedSlot?(r=n.assignedSlot.parentElement||n.assignedSlot.getRootNode(),r.insertBefore(i,n.assignedSlot),e.appendChild(t),t.appendChild(n.assignedSlot)):(r=n.parentElement||n.getRootNode(),r.insertBefore(i,n),e.appendChild(t),t.appendChild(n)),r.insertBefore(e,i),r?.removeChild(i)}async function vu(){return O(()=>import(`./popper.js`),[],import.meta.url)}var yu=new WeakMap,bu=class e extends EventTarget{constructor(e={},t=Wl){super(),this.manager=t,this.__sharedConfig=e,this.__activeElementRightBeforeHide=null,this.config={},this._defaultConfig={placementMode:void 0,contentNode:e.contentNode,contentWrapperNode:e.contentWrapperNode,invokerNode:e.invokerNode,backdropNode:e.backdropNode,referenceNode:void 0,elementToFocusAfterHide:e.invokerNode,inheritsReferenceWidth:`none`,hasBackdrop:!1,isBlocking:!1,preventsScroll:!1,trapsKeyboardFocus:!1,hidesOnEsc:!1,hidesOnOutsideEsc:!1,hidesOnOutsideClick:!1,isTooltip:!1,isAlertDialog:!1,invokerRelation:`description`,visibilityTriggerFunction:void 0,handlesAccessibility:!1,popperConfig:{placement:`top`,strategy:`fixed`,modifiers:[{name:`preventOverflow`,enabled:!0,options:{boundariesElement:`viewport`,padding:8}},{name:`flip`,options:{boundariesElement:`viewport`,padding:16}},{name:`offset`,enabled:!0,options:{offset:[0,8]}},{name:`arrow`,enabled:!1}]},viewportConfig:{placement:`center`},zIndex:9999},this._contentId=`overlay-content--${Math.random().toString(36).slice(2,10)}`,this.__originalAttrs=new Map,this.updateConfig(e),this.__hasActiveTrapsKeyboardFocus=!1,this.__hasActiveBackdrop=!0,this.__escKeyHandler=this.__escKeyHandler.bind(this),this.__cancelHandler=this.__cancelHandler.bind(this)}get invoker(){return this.invokerNode}get content(){return this.__wrappingDialogNode}get placementMode(){return this.config?.placementMode}get invokerNode(){return this.config?.invokerNode}get referenceNode(){return this.config?.referenceNode}get contentNode(){return this.config?.contentNode}get contentWrapperNode(){return this.__contentWrapperNode||this.config?.contentWrapperNode}get backdropNode(){return this.__backdropNode||this.config?.backdropNode}get elementToFocusAfterHide(){return this.__elementToFocusAfterHide||this.config?.elementToFocusAfterHide}get hasBackdrop(){return!!this.backdropNode||this.config?.hasBackdrop}get isBlocking(){return this.config?.isBlocking}get preventsScroll(){return this.config?.preventsScroll}get trapsKeyboardFocus(){return this.config?.trapsKeyboardFocus}get hidesOnEsc(){return this.config?.hidesOnEsc}get hidesOnOutsideClick(){return this.config?.hidesOnOutsideClick}get hidesOnOutsideEsc(){return this.config?.hidesOnOutsideEsc}get inheritsReferenceWidth(){return this.config?.inheritsReferenceWidth}get handlesAccessibility(){return this.config?.handlesAccessibility}get isTooltip(){return this.config?.isTooltip}get isAlertDialog(){return this.config?.isAlertDialog}get invokerRelation(){return this.config?.invokerRelation}get popperConfig(){return this.config?.popperConfig}get viewportConfig(){return this.config?.viewportConfig}get visibilityTriggerFunction(){return this.config?.visibilityTriggerFunction}get _referenceNode(){return this.referenceNode||this.invokerNode}set elevation(e){this.__wrappingDialogNode.style.zIndex=`${this.config.zIndex+e}`}get elevation(){return Number(this.contentWrapperNode?.style.zIndex)}updateConfig(e){this.teardown(),this.__prevConfig=this.config,this.config={...this._defaultConfig,...this.__sharedConfig,...e,popperConfig:{...this._defaultConfig.popperConfig||{},...this.__sharedConfig.popperConfig||{},...e.popperConfig||{},modifiers:[...this._defaultConfig.popperConfig?.modifiers||[],...this.__sharedConfig.popperConfig?.modifiers||[],...e.popperConfig?.modifiers||[]]}},this.__validateConfiguration(this.config),this._init(),this.__elementToFocusAfterHide=void 0,this.#e()||this.manager.add(this)}#e(){return!!this.manager.list.find(e=>this===e)}__validateConfiguration(e){if(!e.placementMode)throw Error(`[OverlayController] You need to provide a .placementMode ("global"|"local")`);if(![`global`,`local`].includes(e.placementMode))throw Error(`[OverlayController] "${e.placementMode}" is not a valid .placementMode, use ("global"|"local")`);if(!e.contentNode)throw Error(`[OverlayController] You need to provide a .contentNode`);if(e.isTooltip&&!e.handlesAccessibility)throw Error(`[OverlayController] .isTooltip only takes effect when .handlesAccessibility is enabled`)}_init(){this.__contentHasBeenInitialized||=(this.__initContentDomStructure(),!0),this.contentWrapperNode.removeAttribute(`style`),this.contentWrapperNode.removeAttribute(`class`),this.placementMode===`local`&&(e.popperModule||=vu()),this.__handleOverlayStyles({phase:`init`}),this._handleFeatures({phase:`init`})}__handleOverlayStyles({phase:e}){let t=this.contentWrapperNode?.getRootNode();e===`init`?uu.adoptStyle(t,lu):e===`teardown`&&uu.adoptStyle(t,lu,{teardown:!0})}__initContentDomStructure(){let e=document.createElement(this.config?._noDialogEl?`div`:`dialog`);e.setAttribute(`role`,`none`),e.setAttribute(`data-overlay-outer-wrapper`,``),e.style.cssText=`display:none; z-index: ${this.config.zIndex}; padding: 0;`,this.__wrappingDialogNode=e,this.config?.contentWrapperNode||(this.__contentWrapperNode=document.createElement(`div`)),this.contentWrapperNode.setAttribute(`data-id`,`content-wrapper`),_u({wrappingDialogNodeL1:e,contentWrapperNodeL2:this.contentWrapperNode,contentNodeL3:this.contentNode}),e.open=!0,this.isTooltip&&e.setAttribute(`tabindex`,`-1`),this.__wrappingDialogNode.style.display=`none`,this.contentWrapperNode.style.zIndex=`1`,getComputedStyle(this.contentNode).position===`absolute`&&(this.contentNode.style.position=`static`),HTMLDialogElement&&`closedBy`in HTMLDialogElement.prototype?e.closedBy=`none`:(e.addEventListener(`keydown`,e=>{e.key===`Escape`&&e.preventDefault()}),e.addEventListener(`keyup`,e=>{e.key===`Escape`&&e.preventDefault()}),e.addEventListener(`cancel`,e=>{e.stopPropagation()}),e.addEventListener(`close`,e=>{e.stopPropagation()}))}_handleZIndex({phase:e}){if(this.placementMode===`local`&&e===`setup`){let e=Number(getComputedStyle(this.contentNode).zIndex);(e<1||Number.isNaN(e))&&(this.contentNode.style.zIndex=`1`)}}__setupTeardownAccessibility({phase:e}){if(e===`init`){this.__storeOriginalAttrs(this.contentNode,[`role`,`id`]);let e=this.trapsKeyboardFocus;if(this.invokerNode){let t=[`aria-labelledby`,`aria-describedby`];e||t.push(`aria-expanded`),this.__storeOriginalAttrs(this.invokerNode,t)}this.contentNode.id||this.contentNode.setAttribute(`id`,this._contentId),this.isTooltip?(this.invokerNode&&this.invokerNode.setAttribute(this.invokerRelation===`label`?`aria-labelledby`:`aria-describedby`,this._contentId),this.contentNode.setAttribute(`role`,`tooltip`)):(this.invokerNode&&!e&&this.invokerNode.setAttribute(`aria-expanded`,`${this.isShown}`),this.isAlertDialog?this.contentNode.setAttribute(`role`,`alertdialog`):this.contentNode.getAttribute(`role`)||this.contentNode.setAttribute(`role`,`dialog`))}else e===`teardown`&&this.__restoreOriginalAttrs()}__storeOriginalAttrs(e,t){let n={};t.forEach(t=>{n[t]=e.getAttribute(t)}),this.__originalAttrs.set(e,n)}__restoreOriginalAttrs(){for(let[e,t]of this.__originalAttrs)Object.entries(t).forEach(([t,n])=>{n===null?e.removeAttribute(t):e.setAttribute(t,n)});this.__originalAttrs.clear()}get isShown(){return this.__wrappingDialogNode?.style.display!==`none`}async show(e=this.elementToFocusAfterHide){if(this._showComplete&&await this._showComplete,this._showComplete=new Promise(e=>{this._showResolve=e}),this.manager&&this.manager.show(this),this.isShown){this._showResolve();return}let t=new CustomEvent(`before-show`,{cancelable:!0});this.dispatchEvent(t),t.defaultPrevented||(`HTMLDialogElement`in window&&this.__wrappingDialogNode instanceof HTMLDialogElement&&(this.__wrappingDialogNode.open=!0),this.__wrappingDialogNode.style.display=``,this._keepBodySize({phase:`before-show`}),await this._handleFeatures({phase:`show`}),this._keepBodySize({phase:`show`}),await this._handlePosition({phase:`show`}),this.__elementToFocusAfterHide=e,this.dispatchEvent(new Event(`show`)),await this._transitionShow({backdropNode:this.backdropNode,contentNode:this.contentNode})),this._showResolve()}async _handlePosition({phase:e}){if(this.placementMode===`global`){let t=`overlays__overlay-container--${this.viewportConfig.placement}`;e===`show`?(this.contentWrapperNode.classList.add(`overlays__overlay-container`),this.contentWrapperNode.classList.add(t),this.contentNode.classList.add(`overlays__overlay`)):e===`hide`&&(this.contentWrapperNode.classList.remove(`overlays__overlay-container`),this.contentWrapperNode.classList.remove(t),this.contentNode.classList.remove(`overlays__overlay`))}else this.placementMode===`local`&&e===`show`&&(await this.__createPopperInstance(),this._popper.forceUpdate())}_keepBodySize({phase:e}){if(this.preventsScroll)switch(e){case`before-show`:this.__bodyClientWidth=document.body.clientWidth,this.__bodyClientHeight=document.body.clientHeight,this.__bodyMarginRightInline=document.body.style.marginRight,this.__bodyMarginBottomInline=document.body.style.marginBottom;break;case`show`:{if(window.getComputedStyle){let e=window.getComputedStyle(document.body);this.__bodyMarginRight=parseInt(e.getPropertyValue(`margin-right`),10),this.__bodyMarginBottom=parseInt(e.getPropertyValue(`margin-bottom`),10)}else this.__bodyMarginRight=0,this.__bodyMarginBottom=0;let e=document.body.clientWidth-this.__bodyClientWidth,t=document.body.clientHeight-this.__bodyClientHeight,n=this.__bodyMarginRight+e,r=this.__bodyMarginBottom+t;window.CSS?.number&&document.body.attributeStyleMap?.set?(document.body.attributeStyleMap.set(`margin-right`,CSS.px(n)),document.body.attributeStyleMap.set(`margin-bottom`,CSS.px(r))):(document.body.style.marginRight=`${n}px`,document.body.style.marginBottom=`${r}px`);break}case`hide`:document.body.style.marginRight=this.__bodyMarginRightInline||``,document.body.style.marginBottom=this.__bodyMarginBottomInline||``;break}}async hide(){if(this._hideComplete=new Promise(e=>{this._hideResolve=e}),this.__activeElementRightBeforeHide=this.contentNode.getRootNode().activeElement,this.manager&&this.manager.hide(this),!this.isShown){this._hideResolve();return}let e=new CustomEvent(`before-hide`,{cancelable:!0});this.dispatchEvent(e),e.defaultPrevented||(await this._transitionHide({backdropNode:this.backdropNode,contentNode:this.contentNode}),`HTMLDialogElement`in window&&this.__wrappingDialogNode instanceof HTMLDialogElement&&this.__wrappingDialogNode.close(),this.__wrappingDialogNode.style.display=`none`,this._handleFeatures({phase:`hide`}),this._keepBodySize({phase:`hide`}),this.dispatchEvent(new Event(`hide`)),this._restoreFocus()),this._hideResolve()}async transitionHide(e){}async _transitionHide({backdropNode:e,contentNode:t}){await this.transitionHide({backdropNode:e,contentNode:t}),this._handlePosition({phase:`hide`}),e&&e.classList.remove(`overlays__backdrop--animation-in`)}async transitionShow(e){}async _transitionShow(e){await this.transitionShow({backdropNode:this.backdropNode,contentNode:this.contentNode}),e.backdropNode&&e.backdropNode.classList.add(`overlays__backdrop--animation-in`)}_restoreFocus(){this.__activeElementRightBeforeHide instanceof HTMLElement&&this.contentNode.contains(this.__activeElementRightBeforeHide)&&(this.elementToFocusAfterHide instanceof HTMLElement?(this.elementToFocusAfterHide.focus(),this.elementToFocusAfterHide.scrollIntoView({block:`nearest`})):this.__activeElementRightBeforeHide.blur())}async toggle(){return this.isShown?this.hide():this.show()}_handleFeatures({phase:e}){this._handleZIndex({phase:e}),this.preventsScroll&&this._handlePreventsScroll({phase:e}),this.isBlocking&&this._handleBlocking({phase:e}),this.hasBackdrop&&this._handleBackdrop({phase:e}),this.trapsKeyboardFocus&&this._handleTrapsKeyboardFocus({phase:e}),this.hidesOnEsc&&this._handleHidesOnEsc({phase:e}),this.hidesOnOutsideEsc&&this._handleHidesOnOutsideEsc({phase:e}),this.hidesOnOutsideClick&&this._handleHidesOnOutsideClick({phase:e}),this.handlesAccessibility&&this._handleAccessibility({phase:e}),this.inheritsReferenceWidth&&this._handleInheritsReferenceWidth(),this.visibilityTriggerFunction&&this._handleVisibilityTriggers({phase:e})}_handleVisibilityTriggers({phase:e}){typeof this.visibilityTriggerFunction==`function`&&(e===`init`&&(this.__visibilityTriggerHandler=this.visibilityTriggerFunction({phase:e,controller:this})),this.__visibilityTriggerHandler[e]&&this.__visibilityTriggerHandler[e]())}_handlePreventsScroll({phase:e}){switch(e){case`show`:this.manager.requestToPreventScroll();break;case`hide`:this.manager.requestToEnableScroll();break}}_handleBlocking({phase:e}){switch(e){case`show`:this.manager.requestToShowOnly(this);break;case`hide`:this.manager.retractRequestToShowOnly(this);break}}get hasActiveBackdrop(){return this.__hasActiveBackdrop}_handleBackdrop({phase:e}){switch(e){case`init`:this.__backdropInitialized||=(this.config?.backdropNode||(this.__backdropNode=document.createElement(`div`),this.__backdropNode.classList.add(`overlays__backdrop`)),this.__wrappingDialogNode.prepend(this.backdropNode),!0);break;case`show`:this.config.hasBackdrop&&this.backdropNode.classList.add(`overlays__backdrop--visible`),this.__hasActiveBackdrop=!0;break;case`hide`:case`teardown`:this.backdropNode.classList.remove(`overlays__backdrop--visible`),this.__hasActiveBackdrop=!1;break}}get hasActiveTrapsKeyboardFocus(){return this.__hasActiveTrapsKeyboardFocus}_handleTrapsKeyboardFocus({phase:e}){e===`show`?(`showModal`in this.__wrappingDialogNode&&(this.__wrappingDialogNode.close(),this.__wrappingDialogNode.showModal()),this.enableTrapsKeyboardFocus()):(e===`hide`||e===`teardown`)&&this.disableTrapsKeyboardFocus()}enableTrapsKeyboardFocus(){this.__hasActiveTrapsKeyboardFocus||(this.manager&&this.manager.disableTrapsKeyboardFocusForAll(),this.contentNode.shadowRoot&&console.warn(`[overlays]: For best accessibility (compatibility with Safari + VoiceOver), provide a contentNode that is not a host for a shadow root`),this._containFocusHandler=cu(this.contentNode),this.__hasActiveTrapsKeyboardFocus=!0,this.manager&&this.manager.informTrapsKeyboardFocusGotEnabled(this.placementMode))}disableTrapsKeyboardFocus({findNewTrap:e=!0}={}){this.__hasActiveTrapsKeyboardFocus&&(this._containFocusHandler&&=(this._containFocusHandler.disconnect(),void 0),this.__hasActiveTrapsKeyboardFocus=!1,this.manager&&this.manager.informTrapsKeyboardFocusGotDisabled({disabledCtrl:this,findNewTrap:e}))}__cancelHandler(e){e.preventDefault()}__escKeyHandler(e){e.key!==`Escape`||yu.has(e)||(e.composedPath().includes(this.contentNode)||au(this.contentNode,e.target))&&(this.hide(),yu.set(e,this))}#t=e=>{e.key===`Escape`&&(e.composedPath().includes(this.contentNode)||au(this.contentNode,e.target)||this.hide())};_handleHidesOnEsc({phase:e}){e===`show`?(this.contentNode.addEventListener(`keyup`,this.__escKeyHandler),this.invokerNode&&this.invokerNode.addEventListener(`keyup`,this.__escKeyHandler)):(e===`hide`||e===`teardown`)&&(this.contentNode.removeEventListener(`keyup`,this.__escKeyHandler),this.invokerNode&&this.invokerNode.removeEventListener(`keyup`,this.__escKeyHandler))}_handleHidesOnOutsideEsc({phase:e}){e===`show`?document.addEventListener(`keyup`,this.#t):(e===`hide`||e===`teardown`)&&document.removeEventListener(`keyup`,this.#t)}_handleInheritsReferenceWidth(){if(!this._referenceNode||this.placementMode===`global`)return;let e=`${this._referenceNode.getBoundingClientRect().width}px`;switch(this.inheritsReferenceWidth){case`max`:this.contentWrapperNode.style.maxWidth=e;break;case`full`:this.contentWrapperNode.style.width=e;break;case`min`:this.contentWrapperNode.style.minWidth=e,this.contentWrapperNode.style.width=`auto`;break}}_handleHidesOnOutsideClick({phase:e}){let t=e===`show`?`addEventListener`:`removeEventListener`;if(e===`show`){let e=!1,t=!1;this.__onInsideMouseDown=()=>{e=!0},this.__onInsideMouseUp=()=>{t=!0},this.__onDocumentMouseUp=()=>{setTimeout(()=>{!e&&!t&&this.hide(),e=!1,t=!1})},this.__onWindowBlur=()=>{setTimeout(()=>{this.hide()})}}this.contentWrapperNode[t](`mousedown`,this.__onInsideMouseDown,!0),this.contentWrapperNode[t](`mouseup`,this.__onInsideMouseUp,!0),this.invokerNode&&(this.invokerNode[t](`mousedown`,this.__onInsideMouseDown,!0),this.invokerNode[t](`mouseup`,this.__onInsideMouseUp,!0)),document.documentElement[t](`mouseup`,this.__onDocumentMouseUp,!0),window[t](`blur`,this.__onWindowBlur)}_handleAccessibility({phase:e}){(e===`init`||e===`teardown`)&&this.__setupTeardownAccessibility({phase:e});let t=this.trapsKeyboardFocus;this.invokerNode&&!this.isTooltip&&!t&&this.invokerNode.setAttribute(`aria-expanded`,`${e===`show`}`)}teardown(){this.__handleOverlayStyles({phase:`teardown`}),this._handleFeatures({phase:`teardown`}),this.#e()&&this.manager.remove(this)}async __createPopperInstance(){if(this._popper&&=(this._popper.destroy(),void 0),e.popperModule!==void 0){let{createPopper:t}=await e.popperModule;this._popper=t(this._referenceNode,this.contentWrapperNode,{...this.config?.popperConfig})}}_hasDisabledInvoker(){return this.invokerNode?this.invokerNode.disabled||this.invokerNode.getAttribute(`aria-disabled`)===`true`:!1}};bu.popperModule=void 0;function xu(e,t){if(typeof e!=`object`||typeof t!=`object`||e===null||t===null)return e===t;let n=Object.keys(e),r=Object.keys(t);return n.length===r.length?n.every(n=>xu(e[n],t[n])):!1}var Su=Er(e=>class extends e{static get properties(){return{opened:{type:Boolean,reflect:!0}}}#e=!1;constructor(){super(),this.opened=!1,this.config={},this.toggle=this.toggle.bind(this),this.open=this.open.bind(this),this.close=this.close.bind(this)}get config(){return this.__config}set config(e){let t=!xu(this.config,e);this._overlayCtrl&&t&&this._overlayCtrl.updateConfig(e),this.__config=e,this._overlayCtrl&&t&&this.__syncToOverlayController()}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`opened`&&this.opened!==t&&this.dispatchEvent(new CustomEvent(`opened-changed`,{detail:{opened:this.opened}}))}_defineOverlay({contentNode:e,invokerNode:t,referenceNode:n,backdropNode:r,contentWrapperNode:i}){let a=this._defineOverlayConfig()||{};return new bu({contentNode:e,invokerNode:t,referenceNode:n,backdropNode:r,contentWrapperNode:i,...a,...this.config,popperConfig:{...a.popperConfig||{},...this.config?.popperConfig||{},modifiers:[...a.popperConfig?.modifiers||[],...this.config?.popperConfig?.modifiers||[]]}})}_defineOverlayConfig(){return{placementMode:`local`}}updated(e){super.updated(e),e.has(`opened`)&&this._overlayCtrl&&!this.__blockSyncToOverlayCtrl&&this.__syncToOverlayController()}_setupOpenCloseListeners(){this.__closeEventInContentNodeHandler=e=>{e.stopPropagation(),this._overlayCtrl.hide()},this._overlayContentNode&&this._overlayContentNode.addEventListener(`close-overlay`,this.__closeEventInContentNodeHandler)}_teardownOpenCloseListeners(){this._overlayContentNode&&this._overlayContentNode.removeEventListener(`close-overlay`,this.__closeEventInContentNodeHandler)}connectedCallback(){super.connectedCallback(),this.updateComplete.then(()=>{this.isConnected&&(this.#e||=(this._setupOverlayCtrl(),!0))})}async disconnectedCallback(){super.disconnectedCallback(),await this._isPermanentlyDisconnected()&&(this._teardownOverlayCtrl(),this.#e=!1)}static enabledWarnings=super.enabledWarnings?.filter(e=>e!==`change-in-update`)||[];get _overlayInvokerNode(){return Array.from(this.children).find(e=>e.slot===`invoker`)}get _overlayReferenceNode(){}get _overlayBackdropNode(){return this.__cachedOverlayBackdropNode||=Array.from(this.children).find(e=>e.slot===`backdrop`),this.__cachedOverlayBackdropNode}get _overlayContentNode(){return this._cachedOverlayContentNode||=Array.from(this.children).find(e=>e.slot===`content`)||this.config.contentNode,this._cachedOverlayContentNode}get _overlayContentWrapperNode(){return this.shadowRoot?.querySelector(`#overlay-content-node-wrapper`)}_setupOverlayCtrl(){if(this.#e)return;let e={contentNode:this._overlayContentNode,contentWrapperNode:this._overlayContentWrapperNode,invokerNode:this._overlayInvokerNode,referenceNode:this._overlayReferenceNode,backdropNode:this._overlayBackdropNode};this._overlayCtrl?this._overlayCtrl.updateConfig(e):this._overlayCtrl=this._defineOverlay(e),this.__syncToOverlayController(),this.__setupSyncFromOverlayController(),this._setupOpenCloseListeners()}_teardownOverlayCtrl(){this._overlayCtrl&&(this._teardownOpenCloseListeners(),this.__teardownSyncFromOverlayController(),this._overlayCtrl.teardown())}async _setOpenedWithoutPropertyEffects(e){this.__blockSyncToOverlayCtrl=!0,this.opened=e,await this.updateComplete,this.__blockSyncToOverlayCtrl=!1}__setupSyncFromOverlayController(){this.__onOverlayCtrlShow=()=>{this.opened=!0},this.__onOverlayCtrlHide=()=>{this.opened=!1},this.__onBeforeShow=e=>{let t=new CustomEvent(`before-opened`,{cancelable:!0});this.dispatchEvent(t),t.defaultPrevented&&(this._setOpenedWithoutPropertyEffects(this._overlayCtrl.isShown),e.preventDefault())},this.__onBeforeHide=e=>{let t=new CustomEvent(`before-closed`,{cancelable:!0});this.dispatchEvent(t),t.defaultPrevented&&(this._setOpenedWithoutPropertyEffects(this._overlayCtrl.isShown),e.preventDefault())},this._overlayCtrl.addEventListener(`show`,this.__onOverlayCtrlShow),this._overlayCtrl.addEventListener(`hide`,this.__onOverlayCtrlHide),this._overlayCtrl.addEventListener(`before-show`,this.__onBeforeShow),this._overlayCtrl.addEventListener(`before-hide`,this.__onBeforeHide)}__teardownSyncFromOverlayController(){this._overlayCtrl.removeEventListener(`show`,this.__onOverlayCtrlShow),this._overlayCtrl.removeEventListener(`hide`,this.__onOverlayCtrlHide),this._overlayCtrl.removeEventListener(`before-show`,this.__onBeforeShow),this._overlayCtrl.removeEventListener(`before-hide`,this.__onBeforeHide)}__syncToOverlayController(){this.opened?this._overlayCtrl.show():this._overlayCtrl.hide()}async toggle(){await this._overlayCtrl.toggle()}async open(){await this._overlayCtrl.show()}async close(){await this._overlayCtrl.hide()}repositionOverlay(){let e=this._overlayCtrl;e.placementMode===`local`&&e._popper&&e._popper.update()}async _isPermanentlyDisconnected(){return await this.updateComplete,!this.isConnected}});function Cu(){return{visibilityTriggerFunction:({controller:e})=>{function t(){e._hasDisabledInvoker()||e.toggle()}return{init:()=>{e.invokerNode?.addEventListener(`click`,t)},teardown:()=>{e.invokerNode?.removeEventListener(`click`,t)}}}}}var wu=()=>({placementMode:`local`,inheritsReferenceWidth:`min`,hidesOnOutsideClick:!0,hidesOnEsc:!0,popperConfig:{placement:`bottom-start`,modifiers:[{name:`offset`,enabled:!1}]},handlesAccessibility:!0,...Cu()}),Tu=class extends Su(v){_defineOverlayConfig(){return{...wu()}}_addEventListeners(){this.actionItems.forEach(e=>{e.addEventListener(`click`,e=>{e.target?.dispatchEvent(new Event(`close-overlay`,{bubbles:!0}))})})}_setupInvoker(){let e=this.invokerNodes[0];e&&(e.setAttribute(`id`,`invoker-${this.uid}`),e.setAttribute(`aria-controls`,`content-${this.uid}`))}_setupContent(){let e=this.contentNodes[0];e&&(e.setAttribute(`id`,`content-${this.uid}`),e.setAttribute(`role`,`none`))}_setupOverlayCtrl(){super._setupOverlayCtrl(),this._setupInvoker(),this._setupContent()}firstUpdated(){this.uid=Wr(),this._addEventListeners()}render(){return d`
      <slot name="invoker"></slot>
      <slot name="backdrop"></slot>
      <slot name="content"></slot>
    `}};Tu.styles=p`
    ::slotted([slot='content']) {
      font-size: var(--c-text-base);
      font-weight: 400;
      display: grid;
      gap: var(--c-spacing-xs);
      border: 1px solid var(--c-color-neutral-border-quiet);
      border-radius: var(--c-radius-md);
      background-color: var(--c-surface-overlay);
      box-shadow: var(--c-shadow-sm);
      padding: var(--c-spacing-sm);
      min-width: calc(180rem / 16);
      max-width: calc(320rem / 16);
    }

    ::slotted(hr) {
      margin: 0;
    }
  `,l([C({selector:`craft-action-item`})],Tu.prototype,`actionItems`,void 0),l([C({slot:`invoker`})],Tu.prototype,`invokerNodes`,void 0),l([C({slot:`content`})],Tu.prototype,`contentNodes`,void 0),customElements.get(`craft-action-menu`)||customElements.define(`craft-action-menu`,Tu);var Eu=new WeakMap;function Du(e,t){Array.from(e.childNodes).forEach(n=>{if(n.nodeName===`#text`){let r=RegExp(`^(.*?)(${t})(.*)$`,`i`),i=n.nodeValue.match(r);if(i){let t=document.createTextNode(i[1]);e.appendChild(t);let r=document.createElement(`b`);r.textContent=i[2],e.appendChild(r);let a=document.createTextNode(i[3]);e.appendChild(a),e.removeChild(n),Eu.set(e,()=>{e.appendChild(n),e.contains(t)&&t.parentNode!==null&&t.parentNode.removeChild(t),e.contains(r)&&r.parentNode!==null&&r.parentNode.removeChild(r),e.contains(a)&&a.parentNode!==null&&a.parentNode.removeChild(a)})}}else Du(n,t)})}function Ou(e){Eu.has(e)&&Eu.get(e)(),Array.from(e.childNodes).forEach(e=>{e.nodeName===`#text`?Eu.has(e)&&Eu.get(e)():Ou(e)})}var ku=class extends To{static get validatorName(){return`MatchesOption`}execute(e,t,n){return n?.node.modelValue instanceof bo}};function Au(e){return Array.isArray(e)?e:[e]}var ju=Er(e=>class extends Io(e){static get properties(){return{allowCustomChoice:{type:Boolean,attribute:`allow-custom-choice`},modelValue:{type:Object}}}get modelValue(){return this.__getChoicesFrom(super.modelValue)}set modelValue(e){if(super.modelValue=e,e==null||e===``)this._customChoices=new Set;else if(this.allowCustomChoice){let t=this.modelValue;this._customChoices=new Set(Au(e)),this.requestUpdate(`modelValue`,t)}}get formattedValue(){return this.__getChoicesFrom(super.formattedValue)}set formattedValue(e){if(super.formattedValue=e,e==null)this._customChoices=new Set;else if(this.allowCustomChoice){let t=this.modelValue;this._customChoices=new Set(Au(e).map(e=>this.formElements.find(t=>t.formattedValue===e)?.modelValue||e)),this.requestUpdate(`modelValue`,t)}}get serializedValue(){return this.__getChoicesFrom(super.serializedValue)}set serializedValue(e){if(super.serializedValue=e,e==null)this._customChoices=new Set;else if(this.allowCustomChoice){let t=this.modelValue;this._customChoices=new Set(Au(e).map(e=>this.formElements.find(t=>t.serializedValue===e)?.modelValue||e)),this.requestUpdate(`modelValue`,t)}}get customChoices(){if(!this.allowCustomChoice)return[];let e=this._getCheckedElements();return Array.from(this._customChoices).filter(t=>!e.some(e=>e.choiceValue===t))}constructor(){super(),this.allowCustomChoice=!1,this._customChoices=new Set}__getChoicesFrom(e){let t=e;return this.allowCustomChoice?this.multipleChoice?[...Au(t),...this.customChoices]:t===``?this._customChoices.values().next().value||``:t:t}_isEmpty(){return super._isEmpty()&&this._customChoices.size===0}clear(){this._customChoices=new Set,super.clear()}parser(e){return this.allowCustomChoice&&Array.isArray(e)?e.filter(e=>e.trim()!==``):e}}),Mu=new WeakMap,Nu=class extends oo(Su(ju(hc))){static get properties(){return{autocomplete:{type:String,reflect:!0},matchMode:{type:String,attribute:`match-mode`},showAllOnEmpty:{type:Boolean,attribute:`show-all-on-empty`},requireOptionMatch:{type:Boolean},allowCustomChoice:{type:Boolean,attribute:`allow-custom-choice`},__shouldAutocompleteNextUpdate:Boolean}}static get styles(){return[...super.styles,p`
        .input-group__input {
          display: flex;
          flex: 1;
        }

        .input-group__container {
          display: flex;
          border-bottom: 1px solid;
        }

        * > ::slotted([slot='input']) {
          outline: none;
          flex: 1;
          box-sizing: border-box;
          border: none;
          width: 100%;
          /* border-bottom: 1px solid; */
        }

        * > ::slotted([role='listbox']) {
          max-height: 200px;
          display: block;
          overflow: auto;
          z-index: 1;
          background: white;
        }
      `]}static get localizeNamespaces(){return[{"lion-combobox":e=>{switch(e){case`bg-BG`:case`bg`:return O(()=>import(`./bg.js`),[],import.meta.url);case`cs-CZ`:case`cs`:return O(()=>import(`./cs.js`),[],import.meta.url);case`de-AT`:case`de-DE`:case`de`:return O(()=>import(`./de.js`),[],import.meta.url);case`en-AU`:case`en-GB`:case`en-PH`:case`en-US`:case`en`:return O(()=>import(`./en.js`),[],import.meta.url);case`es-ES`:case`es`:return O(()=>import(`./es.js`),[],import.meta.url);case`fr-FR`:case`fr-BE`:case`fr`:return O(()=>import(`./fr.js`),[],import.meta.url);case`hu-HU`:case`hu`:return O(()=>import(`./hu.js`),[],import.meta.url);case`it-IT`:case`it`:return O(()=>import(`./it.js`),[],import.meta.url);case`nl-BE`:case`nl-NL`:case`nl`:return O(()=>import(`./nl.js`),[],import.meta.url);case`pl-PL`:case`pl`:return O(()=>import(`./pl.js`),[],import.meta.url);case`ro-RO`:case`ro`:return O(()=>import(`./ro.js`),[],import.meta.url);case`ru-RU`:case`ru`:return O(()=>import(`./ru.js`),[],import.meta.url);case`sk-SK`:case`sk`:return O(()=>import(`./sk.js`),[],import.meta.url);case`uk-UA`:case`uk`:return O(()=>import(`./uk.js`),[],import.meta.url);case`zh-CN`:case`zh`:return O(()=>import(`./zh.js`),[],import.meta.url);default:return O(()=>import(`./en.js`),[],import.meta.url)}}},...super.localizeNamespaces]}get modelValue(){let e=super.modelValue;return e===``?this.parser(this.value):e}set modelValue(e){super.modelValue=e}get value(){return this._inputNode?.value||this.__value||``}set value(e){this._inputNode?(this._inputNode.value=e,this.__value=void 0):this.__value=e}reset(){super.reset(),this.multipleChoice||(this.value=this._initialModelValue),this._resetListboxOptions()}_resetListboxOptions(){this.formElements.forEach((e,t)=>{this._unhighlightMatchedOption(e),!this.showAllOnEmpty||!this.opened?e.style.display=`none`:(e.style.display=``,e.setAttribute(`aria-posinset`,`${t+1}`),e.setAttribute(`aria-setsize`,`${this.formElements.length}`),e.removeAttribute(`aria-hidden`))})}_inputGroupInputTemplate(){return d`
      <div class="input-group__input">
        <slot name="selection-display"></slot>
        <slot name="input"></slot>
      </div>
    `}_overlayListboxTemplate(){return d`
      <div
        id="overlay-content-node-wrapper"
        role="dialog"
        aria-label="${this.msgLit(`lion-combobox:optionsPopup`)}"
      >
        <slot name="listbox"></slot>
      </div>
      <slot id="options-outlet"></slot>
    `}_groupTwoTemplate(){return d` ${super._groupTwoTemplate()} ${this._overlayListboxTemplate()}`}get slots(){return{...super.slots,input:()=>{if(this._ariaVersion===`1.1`){let e=document.createElement(`div`),t=document.createElement(`input`);return t.style.cssText=`
          border: none;
          outline: none;
          width: 100%;
          height: 100%;
          font: inherit;
          background: inherit;
          color: inherit;
          border-radius: inherit;
          box-sizing: border-box;
          padding: 0;`,e.appendChild(t),e}return document.createElement(`input`)},listbox:super.slots.input}}get _comboboxNode(){return this.querySelector(`[slot="input"]`)}get _selectionDisplayNode(){return this.querySelector(`[slot="selection-display"]`)}get _inputNode(){return this._ariaVersion===`1.1`&&this._comboboxNode&&this._comboboxNode.querySelector(`input`)||this._comboboxNode}get _overlayContentNode(){return this._listboxNode}get _overlayReferenceNode(){return this.shadowRoot.querySelector(`.input-group__container`)}get _overlayInvokerNode(){return this._inputNode}get _listboxNode(){return this._overlayCtrl&&this._overlayCtrl.contentNode||Array.from(this.children).find(e=>e.slot===`listbox`)}get _activeDescendantOwnerNode(){return this._inputNode}get requireOptionMatch(){return!this.allowCustomChoice}set requireOptionMatch(e){this.allowCustomChoice=!e}constructor(){super(),this.autocomplete=`both`,this.matchMode=`all`,this.showAllOnEmpty=!1,this.requireOptionMatch=!0,this.rotateKeyboardNavigation=!0,this.selectionFollowsFocus=!0,this.defaultValidators.push(new ku),this._ariaVersion=Ur.isChromium?`1.1`:`1.0`,this._listboxReceivesNoFocus=!0,this._noTypeAhead=!0,this.__prevCboxValueNonSelected=``,this.__prevCboxValue=``,this.__hadUserIntendsInlineAutoFill=!1,this.__listboxContentChanged=!1,this._onKeyUp=this._onKeyUp.bind(this),this._textboxOnClick=this._textboxOnClick.bind(this),this._textboxOnInput=this._textboxOnInput.bind(this),this._textboxOnKeydown=this._textboxOnKeydown.bind(this)}connectedCallback(){super.connectedCallback(),this._selectionDisplayNode&&(this._selectionDisplayNode.comboboxElement=this),(this.disabled||this.readOnly)&&this.__setComboboxDisabledAndReadOnly()}requestUpdate(e,t,n){if(super.requestUpdate(e,t,n),(e===`disabled`||e===`readOnly`)&&this.__setComboboxDisabledAndReadOnly(),e===`modelValue`&&this.modelValue&&this.modelValue!==t&&this._syncToTextboxCondition(this.modelValue,this._oldModelValue))if(this.multipleChoice)this._syncToTextboxMultiple(this.modelValue,this._oldModelValue);else{let e=this._getTextboxValueFromOption(this.formElements[this.checkedIndex]);this._setTextboxValue(e)}}parser(e){return this.requireOptionMatch&&this.checkedIndex===-1&&e!==``&&!Array.isArray(e)?new bo(e):super.parser(e)}__unsyncCheckedIndexOnInputChange(){let e=this._autoSelectCondition(),t=this.formElements[this.checkedIndex];if(!this.multipleChoice&&!e&&t){let e=this._getTextboxValueFromOption(t);this._inputNode.value.startsWith(e)||this.setCheckedIndex(-1)}}updated(e){super.updated(e),e.has(`__shouldAutocompleteNextUpdate`)&&this.__unsyncCheckedIndexOnInputChange(),e.has(`opened`)&&(this.opened&&(this.activeIndex=-1),!this.opened&&e.get(`opened`)!==void 0&&(this.__onOverlayClose(),this.activeIndex=-1)),e.has(`autocomplete`)&&this._inputNode.setAttribute(`aria-autocomplete`,this.autocomplete),e.has(`disabled`)&&this.setAttribute(`aria-disabled`,`${this.disabled}`),e.has(`__shouldAutocompleteNextUpdate`)&&this.__shouldAutocompleteNextUpdate&&(this._handleAutocompletion(),this.__shouldAutocompleteNextUpdate=!1,this.__listboxContentChanged=!1),typeof this._selectionDisplayNode?.onComboboxElementUpdated==`function`&&this._selectionDisplayNode.onComboboxElementUpdated(e)}matchCondition(e,t){let n=-1,r=this._getTextboxValueFromOption(e);return typeof r==`string`&&typeof t==`string`&&(n=r.toLowerCase().indexOf(t.toLowerCase())),this.matchMode===`all`?n>-1:n===0}_showOverlayCondition({lastKey:e}){return this.disabled||this.readOnly||e&&([`Tab`,`Escape`].includes(e)||!this.multipleChoice&&[`Enter`].includes(e))?!1:this.filled||this.showAllOnEmpty||!this.filled&&this.multipleChoice&&this.__prevCboxValueNonSelected?!0:this.opened}_getTextboxValueFromOption(e){return e?e.choiceValue:this.modelValue instanceof bo?this.modelValue.viewValue:this.modelValue}_onListboxContentChanged(){super._onListboxContentChanged(),this.__shouldAutocompleteNextUpdate=!0,this.__listboxContentChanged=!0}_textboxOnInput(e){this.__shouldAutocompleteNextUpdate=!0,this.opened=this._showOverlayCondition({})}_textboxOnKeydown(e){e.key===`Tab`&&(this.opened=!1)}_listboxOnClick(e){super._listboxOnClick(e),this._inputNode.focus(),this.multipleChoice?(this._inputNode.value=``,this._resetListboxOptions()):(this.activeIndex=-1,this.opened=!1)}_setTextboxValue(e){this._inputNode&&this._inputNode.value!==e&&(this._inputNode.value=e)}__onOverlayClose(){this.multipleChoice?this._syncToTextboxMultiple(this.modelValue,this._oldModelValue):this.checkedIndex!==-1&&this._syncToTextboxCondition(this.modelValue,this._oldModelValue,{phase:`overlay-close`})&&(this._inputNode.value=this._getTextboxValueFromOption(this.formElements[this.checkedIndex]))}_repropagationCondition(e){return super._repropagationCondition(e)||this.formElements.every(e=>!e.checked)}_onFilterMatch(e,t){this._highlightMatchedOption(e,t),e.style.display=``}_highlightMatchedOption(e,t){if(Du(e,t),e.textContent){let t=document.createElement(`span`);t.setAttribute(`aria-label`,e.textContent.replace(/\s+/g,` `)),Array.from(e.childNodes).forEach(e=>{t.appendChild(e)}),e.appendChild(t),Mu.set(e,()=>{Array.from(t.childNodes).forEach(t=>{e.appendChild(t)}),e.contains(t)&&e.removeChild(t)})}}_onFilterUnmatch(e,t,n){this._unhighlightMatchedOption(e),e.style.display=`none`}_unhighlightMatchedOption(e){Ou(e),Mu.has(e)&&Mu.get(e)()}__computeUserIntendsAutoFill({prevValue:e,curValue:t}){let n=e.length<t.length,r=e.length&&t.length&&e[0].toLowerCase()!==t[0].toLowerCase();return n||r||this.__listboxContentChanged&&this.__hadUserIntendsInlineAutoFill}_handleAutocompletion(){let e=this._inputNode.selectionStart!==this._inputNode.selectionEnd&&this._inputNode.value.length!==this._inputNode.selectionStart,t=this._inputNode.value,n=this._inputNode.selectionStart,r=e&&n?t.slice(0,n):t,i=e||this.__hadSelectionLastAutofill?this.__prevCboxValueNonSelected:this.__prevCboxValue,a=!r,o=[],s=!1,c=this.__computeUserIntendsAutoFill({prevValue:i,curValue:r}),l=this.autocomplete===`both`||this.autocomplete===`inline`,u=this._autoSelectCondition(),d=this.autocomplete===`inline`||this.autocomplete===`none`;this.formElements.forEach((e,t)=>{let n=this.matchCondition(e,r),f=!1;if(f=a?this.showAllOnEmpty:d||n,u&&!s&&n&&!e.disabled){let n=()=>{this.activeIndex=t,this.selectionFollowsFocus&&!this.multipleChoice&&this.setCheckedIndex(this.activeIndex),s=!0};if(c)if(l){let t=this._getTextboxValueFromOption(e);t&&typeof t==`string`&&typeof r==`string`&&t.toLowerCase().indexOf(r.toLowerCase())===0&&(this.__textboxInlineComplete(e),n())}else n()}e.onFilterUnmatch?e.onFilterUnmatch(r,i):this._onFilterUnmatch(e,r,i),e.setAttribute(`aria-hidden`,`true`),e.removeAttribute(`aria-posinset`),e.removeAttribute(`aria-setsize`),f&&(o.push(e),e.onFilterMatch?e.onFilterMatch(r):this._onFilterMatch(e,r))});let f=o.length;o.forEach((e,t)=>{e.setAttribute(`aria-posinset`,`${t+1}`),e.setAttribute(`aria-setsize`,`${f}`),e.removeAttribute(`aria-hidden`)}),u&&!s&&!this.multipleChoice&&(this.setCheckedIndex(-1),i!==r&&(this.activeIndex=-1),this.modelValue=this.parser(t)),this.__prevCboxValueNonSelected=r,this.__prevCboxValue=this._inputNode.value,this.__hadSelectionLastAutofill=this._inputNode.value.length!==this._inputNode.selectionStart,this.__hadUserIntendsInlineAutoFill=c,this._overlayCtrl&&this._overlayCtrl._popper&&this._overlayCtrl._popper.update()}__textboxInlineComplete(e=this.formElements[this.activeIndex]){let t=this._getTextboxValueFromOption(e);if(this._inputNode.value!==t){let e=this._inputNode.value.length;this._inputNode.value=t,this._inputNode.selectionStart=e,this._inputNode.selectionEnd=this._inputNode.value.length}}_autoSelectCondition(){return this.autocomplete===`both`||this.autocomplete===`inline`}_setupListboxNode(){super._setupListboxNode(),this._listboxNode.removeAttribute(`tabindex`)}_defineOverlayConfig(){return{...wu(),elementToFocusAfterHide:void 0,invokerNode:this._comboboxNode,visibilityTriggerFunction:void 0}}_setupOverlayCtrl(){super._setupOverlayCtrl(),this.__shouldAutocompleteNextUpdate=!0,this.__setupCombobox()}_teardownOverlayCtrl(){super._teardownOverlayCtrl(),this.__teardownCombobox()}_setupOpenCloseListeners(){super._setupOpenCloseListeners(),this._inputNode.addEventListener(`keyup`,this._onKeyUp),this._inputNode.addEventListener(`click`,this._textboxOnClick)}_teardownOpenCloseListeners(){super._teardownOpenCloseListeners(),this._inputNode.removeEventListener(`keyup`,this._onKeyUp),this._inputNode.removeEventListener(`click`,this._textboxOnClick)}_listboxOnKeyDown(e){let{key:t}=e;switch(t){case`Escape`:this.opened=!1,super._listboxOnKeyDown(e),this._setTextboxValue(``);break;case`Backspace`:case`Delete`:this.requireOptionMatch?super._listboxOnKeyDown(e):this.opened=!1;break;case`Enter`:this.opened&&e.preventDefault(),!this.requireOptionMatch&&this.multipleChoice&&(!this.formElements[this.activeIndex]||this.formElements[this.activeIndex].hasAttribute(`aria-hidden`)||!this.opened)?(this.modelValue=this.parser([...this.modelValue,this._inputNode.value]),this._inputNode.value=``,this.opened=!1):(super._listboxOnKeyDown(e),this._resetListboxOptions()),this.multipleChoice?this._inputNode.value=``:this.opened=!1;break;default:super._listboxOnKeyDown(e);break}}_syncToTextboxCondition(e,t,{phase:n}={}){return this.autocomplete===`both`||this.autocomplete===`inline`||!this.focused}_syncToTextboxMultiple(e,t=[]){if(this.requireOptionMatch){let n=e.filter(e=>!t.includes(e)),r=this.formElements.filter(e=>n.includes(e.choiceValue)).map(e=>this._getTextboxValueFromOption(e)).join(` `);this._setTextboxValue(r)}}_enhanceLightDomClasses(){let e=this.querySelector(`[slot=input]`);e&&e.classList.add(`form-control`)}__setComboboxDisabledAndReadOnly(){this._comboboxNode&&(this._comboboxNode.toggleAttribute(`disabled`,this.disabled),this._comboboxNode.setAttribute(`aria-disabled`,`${this.disabled}`),this._comboboxNode.toggleAttribute(`readonly`,this.readOnly),this._comboboxNode.setAttribute(`aria-readonly`,`${this.readOnly}`)),this._inputNode&&(this._inputNode.toggleAttribute(`disabled`,this.disabled),this._inputNode.toggleAttribute(`readOnly`,this.readOnly),this._inputNode.setAttribute(`aria-readonly`,`${this.readOnly}`),this._inputNode.tabIndex=this.disabled?-1:0)}__setupCombobox(){this._comboboxNode.setAttribute(`role`,`combobox`),this._comboboxNode.setAttribute(`aria-haspopup`,`listbox`),this._inputNode.setAttribute(`aria-autocomplete`,this.autocomplete),this._comboboxNode.setAttribute(`aria-controls`,this._listboxNode.id),this._ariaVersion===`1.1`?this._comboboxNode.setAttribute(`aria-owns`,this._listboxNode.id):this._inputNode.setAttribute(`aria-owns`,this._listboxNode.id),this._listboxNode.setAttribute(`aria-labelledby`,this._labelNode.id),this._inputNode.addEventListener(`keydown`,this._listboxOnKeyDown),this._inputNode.addEventListener(`input`,this._textboxOnInput),this._inputNode.addEventListener(`keydown`,this._textboxOnKeydown)}__teardownCombobox(){this._inputNode.removeEventListener(`keydown`,this._listboxOnKeyDown),this._inputNode.removeEventListener(`input`,this._textboxOnInput),this._inputNode.removeEventListener(`keydown`,this._textboxOnKeydown)}_onKeyUp(e){let t=e&&e.key;this.opened=this._showOverlayCondition({lastKey:t,currentValue:this._inputNode.value})}_textboxOnClick(e){this.opened=this._showOverlayCondition({})}clear(){this.value=``,super.clear(),this.__shouldAutocompleteNextUpdate=!0}},Pu=p`
  ${va}

  :host {
    width: 100%;
  }

  ::slotted(.form-control) {
    width: 100%;
    height: 100%;
    appearance: none;
    border: 0;
    min-height: none;
    padding-inline: var(--c-input-spacing-inline)
      calc(var(--c-input-spacing-inline) * 1.5 + 1em);
    border-radius: var(--c-input-radius);
  }

  ::slotted([slot='listbox']) {
    display: grid;
    gap: var(--c-spacing-xs);
    border: 1px solid var(--c-color-neutral-border-quiet);
    border-radius: var(--c-radius-md);
    background-color: var(--c-surface-overlay);
    box-shadow: var(--c-shadow-sm);
    padding: var(--c-spacing-sm);
  }

  .input-group__input {
    ${_a}
    padding-inline: 0;
    position: relative;
    min-height: calc(var(--c-input-height, var(--c-size-control-md)) - 2px);
  }

  .input-group__container {
    border: 0;
  }

  .indicator {
    position: absolute;
    inset-block-start: 50%;
    inset-inline-end: var(--c-input-spacing-inline);
    transform: translateY(-50%);
    width: 1em;
    height: 1em;
  }
`,Fu=class extends Nu{static get styles(){return[...super.styles,Pu]}constructor(){super(),this.defaultValidators=[]}_inputGroupInputTemplate(){return d`
      <div class="input-group__input">
        <slot name="input"></slot>
        <craft-icon
          class="indicator"
          name="chevron-down"
          style="font-size: 0.8em"
        ></craft-icon>
      </div>
    `}parser(e){return e===``?super.parser(e):e}_getTextboxValueFromOption(e){return e?e.textContent?.trim()||``:super._getTextboxValueFromOption(e)}};customElements.get(`craft-combobox`)||customElements.define(`craft-combobox`,Fu);var Iu=class extends v{constructor(...e){super(...e),this.variant=a.Default,this.label=null}render(){return d`<span
      class="${T({indicator:!0,"indicator--success":this.variant===a.Success,"indicator--danger":this.variant===a.Danger,"indicator--warning":this.variant===a.Warning,"indicator--info":this.variant===a.Info,"indicator--empty":this.variant===`empty`})}"
    >
      <slot></slot>
    </span>`}};Iu.styles=[Ll,p`
      .indicator {
        display: inline-flex;
        aspect-ratio: 1;
        width: var(--c-indicator-size, 0.5em);
        border-radius: var(--c-radius-full);
        color: var(--c-color-on-loud);
        background-color: var(--c-color-fill-loud);
        border: 1px solid var(--c-color-border-loud);
      }

      .indicator--empty {
        background-color: var(--c-color-neutral-fill-quiet);
        border: 1px solid var(--c-color-neutral-border-normal);
      }
    `],l([y({reflect:!0})],Iu.prototype,`variant`,void 0),l([y()],Iu.prototype,`label`,void 0),customElements.get(`craft-indicator`)||customElements.define(`craft-indicator`,Iu);var Lu=class extends v{constructor(){super(),this.alt=!1,this.shift=!1,this.os=`Unknown`,this.os=this.detectOS()}connectedCallback(){super.connectedCallback(),this.os===`Unknown`&&(this.os=this.detectOS())}detectOS(){let e=navigator.platform.toLowerCase();return e.includes(`mac`)||/iphone|ipad|ipod/.test(e)?`Mac`:e.includes(`win`)?`Windows`:e.includes(`linux`)?`Linux`:`Unknown`}renderShortcutPrefix(){switch(this.os){case`Mac`:return`${this.alt?`⌥`:``}${this.shift?`⇧`:``}⌘`;case`Linux`:return`Super+${this.alt?`Alt+`:``}${this.shift?`Shift+`:``}`;default:return`Ctrl+${this.alt?`Alt+`:``}${this.shift?`Shift+`:``}`}}render(){return d`<span class="shortcut"
      >${this.renderShortcutPrefix()}<slot></slot
    ></span>`}};Lu.styles=p`
    :host {
      display: inline-flex;
    }

    .shortcut {
      font-size: 0.9em;
      padding: 0 var(--c-spacing-sm);
      background-color: var(--c-color-neutral-fill-quiet);
      border: 1px solid var(--c-color-neutral-border-quiet);
      border-radius: var(--c-radius-sm);
      box-shadow: var(--c-shadow-sm);
    }
  `,l([y({type:Boolean})],Lu.prototype,`alt`,void 0),l([y({type:Boolean})],Lu.prototype,`shift`,void 0),l([y()],Lu.prototype,`os`,void 0),customElements.get(`craft-shortcut`)||customElements.define(`craft-shortcut`,Lu);var Ru=p`
  :host {
    --_height: var(--c-progress-bar-height, 0.375rem);
    --_radius: var(--c-progress-bar-radius, var(--c-radius-full));
    --_track-color: var(
      --c-progress-bar-track-color,
      var(--c-color-neutral-fill-quiet)
    );
    --_fill-color: var(
      --c-progress-bar-fill-color,
      var(--c-color-accent-border-normal)
    );

    display: block;
  }

  :host([show-status]) {
    display: grid;
    grid-template-columns: 1fr auto;
    align-items: center;
    gap: 0.5em;
  }

  :host([hidden]) {
    display: none;
  }

  .progress-bar {
    border-radius: var(--_radius);
    border: 2px solid var(--_fill-color);
    padding: 2px;
    max-width: 100%;
    height: var(--_height);
    display: flex;
    flex-direction: column;
    justify-content: center;
  }

  .progress-bar__fill {
    border-radius: calc(var(--_radius) - 4px);
    height: 100%;
    background-color: var(--_fill-color);
  }

  .progress-bar__fill--smooth {
    transition: width 0.2s ease-out;
  }

  .progress-bar--pending .progress-bar__fill {
    width: 100%;
    background: repeating-linear-gradient(
      -45deg,
      var(--_fill-color),
      var(--_fill-color) 10px,
      var(--_track-color) 10px,
      var(--_track-color) 20px
    );
    background-size: 300% 100%;
    background-position: 0 0;
    animation: progress-bar-pending 1s linear infinite;
  }

  @keyframes progress-bar-pending {
    0% {
      background-position: 0 0;
    }
    100% {
      background-position: 28.28px 0; /* sqrt(2) * 20px for 45deg stripes */
    }
  }

  @media (prefers-reduced-motion: reduce) {
    .progress-bar--pending .progress-bar__fill {
      animation: none;
    }

    .progress-bar__fill--smooth {
      transition: none;
    }
  }

  .progress-bar__status {
    font-size: 0.9em;
    fon
  }

  .visually-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
  }
`,zu=new WeakMap,Bu=class extends v{constructor(...e){super(...e),this.progress=0,this.total=0,this.processed=0,this.showStatus=!1,this.pending=!1,this.smooth=!1,this.label=`Progress`,c(this,zu,0)}updated(e){if((e.has(`total`)||e.has(`processed`))&&this.total>0){let e=Math.min(100,Math.round(this.processed/this.total*100));e>=100&&s(zu,this)<100&&this.dispatchEvent(new CustomEvent(`complete`,{bubbles:!0,composed:!0})),this.progress=e}e.has(`progress`)&&(this.progress>0&&this.pending&&(this.pending=!1),i(zu,this,this.progress))}get progressPercent(){return Math.min(100,Math.max(0,this.progress))}get statusText(){return this.total>0?`${this.processed} / ${this.total}`:`${this.progressPercent}%`}reset(){this.progress=0,this.processed=0,this.pending=!0,i(zu,this,0)}show(){this.hidden=!1}hide(){this.hidden=!0}render(){let e={width:this.pending?`100%`:`${this.progressPercent}%`};return d`
      <div
        class=${T({"progress-bar":!0,"progress-bar--pending":this.pending})}
        part="track"
        role="progressbar"
        aria-valuenow=${this.pending?_:this.progressPercent}
        aria-valuemin="0"
        aria-valuemax="100"
        aria-label=${this.label}
      >
        <div
          class=${T({"progress-bar__fill":!0,"progress-bar__fill--smooth":this.smooth&&!this.pending})}
          part="fill"
          style=${E(e)}
        ></div>
      </div>
      ${this.showStatus?d`<div class="progress-bar__status" part="status">
            ${this.statusText}
          </div>`:_}
      <span class="visually-hidden">
        ${this.pending?`Loading`:`${this.progressPercent}%`}
      </span>
    `}};Bu.styles=[Ru],l([y({type:Number})],Bu.prototype,`progress`,void 0),l([y({type:Number})],Bu.prototype,`total`,void 0),l([y({type:Number})],Bu.prototype,`processed`,void 0),l([y({type:Boolean,attribute:`show-status`})],Bu.prototype,`showStatus`,void 0),l([y({type:Boolean,reflect:!0})],Bu.prototype,`pending`,void 0),l([y({type:Boolean})],Bu.prototype,`smooth`,void 0),l([y({type:String})],Bu.prototype,`label`,void 0),customElements.get(`craft-progress-bar`)||customElements.define(`craft-progress-bar`,Bu);var Vu=class extends Io(Bo(v)){connectedCallback(){super.connectedCallback(),this.setAttribute(`role`,`radiogroup`)}resetGroup(){let e;this.formElements.forEach(t=>{typeof t.resetGroup==`function`?t.resetGroup():typeof t.reset==`function`&&(t.reset(),t.checked&&(e=t.choiceValue))}),this.modelValue=e,this.resetInteractionState()}},Hu=class extends Ro(Vo){connectedCallback(){super.connectedCallback(),this.type=`radio`}},Uu=class extends Vu{static get styles(){return[...super.styles,ya,p`
        .input-group {
          display: grid;
          gap: var(--c-spacing-xs);
        }
      `]}};customElements.get(`craft-radio-group`)||customElements.define(`craft-radio-group`,Uu);var Wu=class extends Hu{static get styles(){return[...super.styles,p`
        /* same as checkbox, potentially consolidate */
        :host {
          gap: var(--c-spacing-sm);
        }
      `]}};customElements.get(`craft-radio`)||customElements.define(`craft-radio`,Wu);var Gu=class e{constructor(t={}){this.config={...e.defaultCookieOptions,...t}}set(e,t,n={}){let{path:r,domain:i,maxAge:a,expires:o,secure:s,sameSite:c,prefix:l}=Object.assign({},this.config,n),u=`${this.config.prefix}:${e}=${encodeURIComponent(t)}`;r&&(u+=`;path=${r}`),i&&(u+=`;domain=${i}`),a?u+=`;max-age-in-seconds=${a}`:o&&(u+=`;expires=${o.toUTCString()}`),s&&(u+=`;secure`),document.cookie=u}get(e){return document.cookie.replace(RegExp(`(?:(?:^|.*;\\s*)${this.config.prefix}:${e}\\s*\\=\\s*([^;]*).*$)|^.*$`),`$1`)}remove(e){this.set(e,``,{expires:new Date(`1970-01-01T00:00:00`)})}};Gu.defaultCookieOptions={path:`/`,domain:null,secure:!1,sameSite:`strict`,prefix:`Craft`};var Ku=class{constructor(){this.refreshPromise=null,this.tokenName=null,this.tokenValue=null,this.refreshPromise=null}async getToken(){return this.tokenValue||await this.refreshToken(),this.tokenValue}async refreshToken(){return this.refreshPromise||=Yu.get(`users/session-info`).then(({data:e})=>{let{csrfTokenName:t,csrfTokenValue:n}=e;return this.tokenName=t??null,this.tokenValue=n??null,this.tokenValue}).finally(()=>{this.refreshPromise=null}),this.refreshPromise}clearToken(){this.tokenValue=null}};function qu(e=``){return`/admin/actions/${e}`}function Ju(){return{"X-Registered-Asset-Bundles":[...new Set(Cp.registeredAssetBundles)].join(`,`),"X-Registered-Js-Files":[...new Set(Cp.registeredJsFiles)].join(`,`)}}var Yu=o.create({baseURL:qu()}),Xu=new Ku;Yu.interceptors.request.use(async e=>{e.headers.set(`X-Requested-With`,`XMLHttpRequest`);let t=Ju();return Object.entries(t).forEach(([t,n])=>{e.headers.set(t,n)}),e}),Yu.interceptors.response.use(e=>e,async e=>{let t=e.config;if(e.response?.status===419||e.response?.status===403&&!t._retry){t._retry=!0;try{return Xu.clearToken(),t.headers[`X-CSRF-Token`]=await Xu.refreshToken(),o(t)}catch(e){return console.error(`Failed to refresh CSRF token:`,e),Promise.reject(e)}}return Promise.reject(e)});var Zu=!1,Qu=null;async function $u(e){if(!Zu){if(Qu)return Qu;Zu=!0;try{return(await Yu.post(`app/api-headers`,void 0,{cancelToken:e})).data}catch{}finally{Zu=!1}}}var ed=o.create({baseURL:`https://api.craftcms.com/v1/`});async function td(e){return Qu?Object.entries(Qu).forEach(([t,n])=>{e.headers.set(t,n)}):(e.params=e.params||{},e.params.processCraftHeaders=1),e}async function nd(e,t){if(Qu)return;let{data:n}=await Yu.post(`app/process-api-response-headers`,{headers:e},{cancelToken:t});return Qu=n,Zu=!1,Qu}async function rd(e){return await nd(e.headers,e.config.cancelToken),e}ed.interceptors.request.use(async e=>{let{cancelToken:t}=e,n=await $u(t);n&&Object.entries(n).forEach(([t,n])=>{e.headers.set(t,n)});let r={...e,params:{...Cp.apiParams||{},...e.params,v:new Date().getTime()}};return n||(r.params.processCraftHeaders=1),Cp.httpProxy&&(r.proxy=Cp.httpProxy),r}),ed.interceptors.request.use(td),ed.interceptors.response.use(rd);var id={Á:`A`,á:`a`,Ä:`A`,ä:`a`,À:`A`,à:`a`,Â:`A`,â:`a`,É:`E`,é:`e`,Ë:`E`,ë:`e`,È:`E`,è:`e`,Ê:`E`,ê:`e`,Í:`I`,í:`i`,Ï:`I`,ï:`i`,Ì:`I`,ì:`i`,Î:`I`,î:`i`,Ó:`O`,ó:`o`,Ö:`O`,ö:`o`,Ò:`O`,ò:`o`,Ô:`O`,ô:`o`,Ú:`U`,ú:`u`,Ü:`U`,ü:`u`,Ù:`U`,ù:`u`,Û:`U`,û:`u`,Ý:`Y`,ý:`y`,Ÿ:`Y`,А:`A`,Б:`B`,В:`V`,Г:`G`,Д:`D`,Ѓ:`Gj`,Е:`E`,Ж:`Z`,З:`Z`,Ѕ:`Dz`,И:`I`,Ј:`j`,К:`K`,Л:`L`,Љ:`Lj`,М:`M`,Н:`N`,Њ:`Nj`,О:`O`,П:`P`,Р:`R`,С:`S`,Т:`T`,Ќ:`Kj`,У:`U`,Ф:`F`,Х:`X`,Ц:`C`,Ч:`C`,Џ:`Dz`,Ш:`S`,а:`a`,б:`b`,в:`v`,г:`g`,д:`d`,ѓ:`gj`,е:`e`,ж:`z`,з:`z`,ѕ:`dz`,и:`i`,ј:`j`,к:`k`,л:`l`,љ:`lj`,м:`m`,н:`n`,њ:`nj`,о:`o`,п:`p`,р:`r`,с:`s`,т:`t`,ќ:`kj`,у:`u`,ф:`f`,х:`x`,ц:`c`,ч:`c`,џ:`dz`,ш:`s`,æ:`ae`,ǽ:`ae`,Ã:`A`,Å:`A`,Ǻ:`A`,Ă:`A`,Ǎ:`A`,Æ:`AE`,Ǽ:`AE`,ã:`a`,å:`a`,ǻ:`a`,ă:`a`,ǎ:`a`,ª:`a`,Ĉ:`C`,Ċ:`C`,Ç:`C`,ç:`c`,ĉ:`c`,ċ:`c`,Ð:`D`,Đ:`D`,ð:`d`,đ:`d`,Ĕ:`E`,Ė:`E`,ĕ:`e`,ė:`e`,ƒ:`f`,Ĝ:`G`,Ġ:`G`,ĝ:`g`,ġ:`g`,Ĥ:`H`,Ħ:`H`,ĥ:`h`,ħ:`h`,Ĩ:`I`,Ĭ:`I`,Ǐ:`I`,Į:`I`,Ĳ:`IJ`,ĩ:`i`,ĭ:`i`,ǐ:`i`,į:`i`,ĳ:`ij`,Ĵ:`J`,ĵ:`j`,Ĺ:`L`,Ľ:`L`,Ŀ:`L`,ĺ:`l`,ľ:`l`,ŀ:`l`,Ñ:`N`,ñ:`n`,ŉ:`n`,Õ:`O`,Ō:`O`,Ŏ:`O`,Ǒ:`O`,Ő:`O`,Ơ:`O`,Ø:`O`,Ǿ:`O`,Œ:`OE`,õ:`o`,ō:`o`,ŏ:`o`,ǒ:`o`,ő:`o`,ơ:`o`,ø:`o`,ǿ:`o`,º:`o`,œ:`oe`,Ŕ:`R`,Ŗ:`R`,ŕ:`r`,ŗ:`r`,Ŝ:`S`,Ș:`S`,ŝ:`s`,ș:`s`,ſ:`s`,Ţ:`T`,Ț:`T`,Ŧ:`T`,Þ:`TH`,ţ:`t`,ț:`t`,ŧ:`t`,þ:`th`,Ũ:`U`,Ŭ:`U`,Ű:`U`,Ų:`U`,Ư:`U`,Ǔ:`U`,Ǖ:`U`,Ǘ:`U`,Ǚ:`U`,Ǜ:`U`,ũ:`u`,ŭ:`u`,ű:`u`,ų:`u`,ư:`u`,ǔ:`u`,ǖ:`u`,ǘ:`u`,ǚ:`u`,ǜ:`u`,Ŵ:`W`,ŵ:`w`,Ŷ:`Y`,ÿ:`y`,ŷ:`y`,ΑΥ:`AU`,ΑΎ:`AU`,Αυ:`Au`,Αύ:`Au`,ΕΊ:`I`,ΕΙ:`I`,Ει:`Ei`,ΕΥ:`EF`,ΕΎ:`EU`,Εί:`I`,Ευ:`Ef`,Εύ:`Eu`,ΟΙ:`I`,ΟΊ:`I`,ΟΥ:`U`,ΟΎ:`OU`,Οι:`Oi`,Οί:`I`,Ου:`Oy`,Ού:`Ou`,ΥΙ:`I`,ΎΙ:`I`,Υι:`Yi`,Ύι:`I`,ΥΊ:`I`,Υί:`I`,αυ:`au`,αύ:`au`,εί:`i`,ει:`ei`,ευ:`ef`,εύ:`eu`,οι:`oi`,οί:`i`,ου:`oy`,ού:`ou`,υι:`yi`,ύι:`i`,υί:`i`,Α:`A`,Ά:`A`,Β:`B`,Δ:`D`,Ε:`E`,Έ:`E`,Φ:`F`,Γ:`G`,Η:`H`,Ή:`I`,Ι:`I`,Ί:`I`,Ϊ:`I`,Κ:`K`,Ξ:`Ks`,Λ:`L`,Μ:`M`,Ν:`N`,Π:`P`,Ο:`O`,Ό:`O`,Ψ:`Ps`,Ρ:`R`,Σ:`S`,Τ:`T`,Θ:`Th`,Ω:`O`,Ώ:`W`,Χ:`X`,ϒ:`Y`,Υ:`Y`,Ύ:`Y`,Ϋ:`Y`,Ζ:`Z`,α:`a`,ά:`a`,β:`v`,δ:`d`,ε:`e`,έ:`e`,φ:`f`,γ:`gh`,η:`i`,ή:`i`,ι:`i`,ί:`i`,ϊ:`i`,ΐ:`i`,κ:`k`,ξ:`ks`,λ:`l`,μ:`m`,ν:`n`,ο:`o`,ό:`o`,π:`p`,ψ:`ps`,ρ:`r`,σ:`s`,ς:`s`,τ:`t`,ϑ:`th`,θ:`th`,ϐ:`v`,ω:`o`,ώ:`w`,χ:`kh`,υ:`i`,ύ:`y`,ΰ:`y`,ϋ:`y`,ζ:`z`,अ:`a`,आ:`aa`,ए:`e`,ई:`ii`,ऍ:`ei`,ऎ:`ae`,ऐ:`ai`,इ:`i`,ओ:`o`,ऑ:`oi`,ऒ:`oii`,ऊ:`uu`,औ:`ou`,उ:`u`,ब:`B`,भ:`Bha`,च:`Ca`,छ:`Chha`,ड:`Da`,ढ:`Dha`,फ:`Fa`,फ़:`Fi`,ग:`Ga`,घ:`Gha`,ग़:`Ghi`,ह:`Ha`,ज:`Ja`,झ:`Jha`,क:`Ka`,ख:`Kha`,ख़:`Khi`,ल:`L`,ळ:`Li`,ऌ:`Li`,ऴ:`Lii`,ॡ:`Lii`,म:`Ma`,न:`Na`,ङ:`Na`,ञ:`Nia`,ण:`Nae`,ऩ:`Ni`,ॐ:`oms`,प:`Pa`,क़:`Qi`,र:`Ra`,ऋ:`Ri`,ॠ:`Ri`,ऱ:`Ri`,स:`Sa`,श:`Sha`,ष:`Shha`,ट:`Ta`,त:`Ta`,ठ:`Tha`,द:`Tha`,थ:`Tha`,ध:`Thha`,ड़:`ugDha`,ढ़:`ugDhha`,व:`Va`,य:`Ya`,य़:`Yi`,ज़:`Za`,Ա:`A`,Բ:`B`,Գ:`G`,Դ:`D`,Ե:`E`,Զ:`Z`,Է:`E`,Ը:`Y`,Թ:`Th`,Ժ:`Zh`,Ի:`I`,Լ:`L`,Խ:`Kh`,Ծ:`Ts`,Կ:`K`,Հ:`H`,Ձ:`Dz`,Ղ:`Gh`,Ճ:`Tch`,Մ:`M`,Յ:`Y`,Ն:`N`,Շ:`Sh`,Ո:`Vo`,Չ:`Ch`,Պ:`P`,Ջ:`J`,Ռ:`R`,Ս:`S`,Վ:`V`,Տ:`T`,Ր:`R`,Ց:`C`,Ւ:`u`,Փ:`Ph`,Ք:`Q`,և:`ev`,Օ:`O`,Ֆ:`F`,ա:`a`,բ:`b`,գ:`g`,դ:`d`,ե:`e`,զ:`z`,է:`e`,ը:`y`,թ:`th`,ժ:`zh`,ի:`i`,լ:`l`,խ:`kh`,ծ:`ts`,կ:`k`,հ:`h`,ձ:`dz`,ղ:`gh`,ճ:`tch`,մ:`m`,յ:`y`,ն:`n`,շ:`sh`,ո:`vo`,չ:`ch`,պ:`p`,ջ:`j`,ռ:`r`,ս:`s`,վ:`v`,տ:`t`,ր:`r`,ց:`c`,ւ:`u`,փ:`ph`,ք:`q`,օ:`o`,ֆ:`f`,Ž:`Z`,Ň:`N`,Ş:`S`,ž:`z`,ň:`n`,ş:`s`,ı:`i`,İ:`I`,ğ:`g`,Ğ:`G`,ьо:`yo`,Й:`i`,Щ:`Shh`,Ъ:`Ie`,Ь:``,Ю:`Iu`,Я:`Ia`,й:`i`,щ:`shh`,ъ:`ie`,ь:``,ю:`iu`,я:`ia`,Ē:`E`,ē:`e`,န်ုပ်:`nub`,"ောင်":`aung`,"ိုက်":`aik`,"ိုဒ်":`ok`,"ိုင်":`aing`,"ိုလ်":`ol`,"ေါင်":`aung`,သြော:`aw`,"ောက်":`auk`,"ိတ်":`eik`,"ုတ်":`ok`,"ုန်":`on`,"ေတ်":`it`,"ုဒ်":`ait`,"ာန်":`an`,"ိန်":`ein`,"ွတ်":`ut`,"ေါ်":`aw`,"ွန်":`un`,"ိပ်":`eik`,"ုပ်":`ok`,"ွပ်":`ut`,"ိမ်":`ein`,"ုမ်":`on`,"ော်":`aw`,"ွမ်":`un`,က်:`et`,"ေါ":`aw`,"ော":`aw`,"ျွ":`ywa`,"ြွ":`yw`,"ို":`o`,"ုံ":`on`,တ်:`at`,င်:`in`,ည်:`i`,ဒ်:`d`,န်:`an`,ပ်:`at`,မ်:`an`,စျ:`za`,ယ်:`e`,ဉ်:`in`,စ်:`it`,"ိံ":`ein`,"ဲ":`e`,"း":``,"ာ":`a`,"ါ":`a`,"ေ":`e`,"ံ":`an`,"ိ":`i`,"ီ":`i`,"ု":`u`,"ူ":`u`,"်":`at`,"္":``,"့":``,က:`k`,"၉":`9`,တ:`t`,ရ:`ya`,ယ:`y`,မ:`m`,ဘ:`ba`,ဗ:`b`,ဖ:`pa`,ပ:`p`,န:`n`,ဓ:`da`,ဒ:`d`,ထ:`ta`,ဏ:`na`,ဝ:`w`,ဎ:`da`,ဍ:`d`,ဌ:`ta`,ဋ:`t`,ည:`ny`,ဇ:`z`,ဆ:`sa`,စ:`s`,င:`ng`,ဃ:`ga`,ဂ:`g`,လ:`l`,သ:`th`,"၈":`8`,ဩ:`aw`,ခ:`kh`,"၆":`6`,"၅":`5`,"၄":`4`,"၃":`3`,"၂":`2`,"၁":`1`,"၀":`0`,"၌":`hnaik`,"၍":`ywae`,ဪ:`aw`,ဦ:`-u`,ဟ:`h`,ဉ:`u`,ဤ:`-i`,ဣ:`i`,"၏":`-e`,ဧ:`e`,"ှ":`h`,"ွ":`w`,"ျ":`ya`,"ြ":`y`,အ:`a`,ဠ:`la`,"၇":`7`,DŽ:`DZ`,Dž:`Dz`,dž:`dz`,Ǳ:`DZ`,ǲ:`Dz`,ǳ:`dz`,Ǉ:`LJ`,ǈ:`Lj`,ǉ:`lj`,Ǌ:`NJ`,ǋ:`Nj`,ǌ:`nj`,č:`c`,Č:`C`,ć:`c`,Ć:`C`,š:`s`,Š:`S`,ა:`a`,ბ:`b`,გ:`g`,დ:`d`,ე:`e`,ვ:`v`,ზ:`z`,თ:`t`,ი:`i`,კ:`k`,ლ:`l`,მ:`m`,ნ:`n`,ო:`o`,პ:`p`,ჟ:`zh`,რ:`r`,ს:`s`,ტ:`t`,უ:`u`,ფ:`f`,ქ:`q`,ღ:`gh`,ყ:`y`,შ:`sh`,ჩ:`ch`,ც:`ts`,ძ:`dz`,წ:`ts`,ჭ:`ch`,ხ:`kh`,ჯ:`j`,ჰ:`h`,Ё:`E`,ё:`e`,Ы:`Y`,ы:`y`,Э:`E`,э:`e`,І:`I`,і:`i`,Ѳ:`F`,ѳ:`f`,Ѣ:`E`,ѣ:`e`,Ѵ:`I`,ѵ:`i`,Є:`Je`,є:`je`,Ѥ:`Je`,ѥ:`je`,Ꙋ:`U`,ꙋ:`u`,Ѡ:`O`,ѡ:`o`,Ѿ:`Ot`,ѿ:`ot`,Ѫ:`U`,ѫ:`u`,Ѧ:`Ja`,ѧ:`ja`,Ѭ:`Ju`,ѭ:`ju`,Ѩ:`Ja`,ѩ:`Ja`,Ѯ:`Ks`,ѯ:`ks`,Ѱ:`Ps`,ѱ:`ps`,Ґ:`G`,ґ:`g`,Ї:`Yi`,ї:`yi`,Ә:`A`,Ғ:`G`,Қ:`Q`,Ң:`N`,Ө:`O`,Ұ:`U`,Ү:`U`,Һ:`H`,ә:`a`,ғ:`g`,қ:`q`,ң:`n`,ө:`o`,ұ:`u`,ү:`u`,һ:`h`,ď:`d`,Ď:`D`,ě:`e`,Ě:`E`,ř:`r`,Ř:`R`,ť:`t`,Ť:`T`,ů:`u`,Ů:`U`,ą:`a`,ę:`e`,ł:`l`,ń:`n`,ś:`s`,ź:`z`,ż:`z`,Ą:`A`,Ę:`E`,Ł:`L`,Ń:`N`,Ś:`S`,Ź:`Z`,Ż:`Z`,ā:`a`,ģ:`g`,ī:`i`,ķ:`k`,ļ:`l`,ņ:`n`,ū:`u`,Ā:`A`,Ģ:`G`,Ī:`I`,Ķ:`k`,Ļ:`L`,Ņ:`N`,Ū:`U`,Ả:`A`,Ạ:`A`,Ắ:`A`,Ằ:`A`,Ẳ:`A`,Ẵ:`A`,Ặ:`A`,Ấ:`A`,Ầ:`A`,Ẩ:`A`,Ẫ:`A`,Ậ:`A`,ả:`a`,ạ:`a`,ắ:`a`,ằ:`a`,ẳ:`a`,ẵ:`a`,ặ:`a`,ấ:`a`,ầ:`a`,ẩ:`a`,ẫ:`a`,ậ:`a`,Ẻ:`E`,Ẽ:`E`,Ẹ:`E`,Ế:`E`,Ề:`E`,Ể:`E`,Ễ:`E`,Ệ:`E`,ẻ:`e`,ẽ:`e`,ẹ:`e`,ế:`e`,ề:`e`,ể:`e`,ễ:`e`,ệ:`e`,Ỉ:`I`,Ị:`I`,ỉ:`i`,ị:`i`,Ỏ:`O`,Ọ:`O`,Ố:`O`,Ồ:`O`,Ổ:`O`,Ỗ:`O`,Ộ:`O`,Ớ:`O`,Ờ:`O`,Ở:`O`,Ỡ:`O`,Ợ:`O`,ỏ:`o`,ọ:`o`,ố:`o`,ồ:`o`,ổ:`o`,ỗ:`o`,ộ:`o`,ớ:`o`,ờ:`o`,ở:`o`,ỡ:`o`,ợ:`o`,Ủ:`U`,Ụ:`U`,Ứ:`U`,Ừ:`U`,Ử:`U`,Ữ:`U`,Ự:`U`,ủ:`u`,ụ:`u`,ứ:`u`,ừ:`u`,ử:`u`,ữ:`u`,ự:`u`,Ỳ:`Y`,Ỷ:`Y`,Ỹ:`Y`,Ỵ:`Y`,ỳ:`y`,ỷ:`y`,ỹ:`y`,ỵ:`y`,ا:`a`,ب:`b`,پ:`p`,ت:`t`,ث:`th`,ج:`g`,چ:`ch`,ح:`h`,خ:`kh`,د:`d`,ذ:`th`,ر:`r`,ز:`z`,س:`s`,ش:`sh`,ص:`s`,ض:`d`,ط:`t`,ظ:`th`,ع:`aa`,غ:`gh`,ف:`f`,ق:`k`,ک:`k`,گ:`g`,ل:`l`,ژ:`zh`,ك:`k`,م:`m`,ن:`n`,ه:`h`,و:`o`,ی:`y`,آ:`a`,"٠":`0`,"١":`1`,"٢":`2`,"٣":`3`,"٤":`4`,"٥":`5`,"٦":`6`,"٧":`7`,"٨":`8`,"٩":`9`,أ:`a`,ي:`y`,إ:`a`,ؤ:`o`,ئ:`y`,ء:`aa`,ђ:`dj`,ћ:`c`,Ђ:`Dj`,Ћ:`C`,ə:`e`,Ə:`E`,ß:`ss`,ẞ:`SS`,ভ্ল:`vl`,পশ:`psh`,ব্ধ:`bdh`,ব্জ:`bj`,ব্দ:`bd`,ব্ব:`bb`,ব্ল:`bl`,ভ:`v`,ব:`b`,চ্ঞ:`cNG`,চ্ছ:`cch`,চ্চ:`cc`,ছ:`ch`,চ:`c`,ধ্ন:`dhn`,ধ্ম:`dhm`,দ্ঘ:`dgh`,দ্ধ:`ddh`,দ্ভ:`dv`,দ্ম:`dm`,ড্ড:`DD`,ঢ:`Dh`,ধ:`dh`,দ্গ:`dg`,দ্দ:`dd`,ড:`D`,দ:`d`,"।":`.`,ঘ্ন:`Ghn`,গ্ধ:`Gdh`,গ্ণ:`GN`,গ্ন:`Gn`,গ্ম:`Gm`,গ্ল:`Gl`,জ্ঞ:`jNG`,ঘ:`Gh`,গ:`g`,হ্ণ:`hN`,হ্ন:`hn`,হ্ম:`hm`,হ্ল:`hl`,হ:`h`,জ্ঝ:`jjh`,ঝ:`jh`,জ্জ:`jj`,জ:`j`,ক্ষ্ণ:`kxN`,ক্ষ্ম:`kxm`,ক্ষ:`ksh`,কশ:`ksh`,ক্ক:`kk`,ক্ট:`kT`,ক্ত:`kt`,ক্ল:`kl`,ক্স:`ks`,খ:`kh`,ক:`k`,ল্ভ:`lv`,ল্ধ:`ldh`,লখ:`lkh`,লঘ:`lgh`,লফ:`lph`,ল্ক:`lk`,ল্গ:`lg`,ল্ট:`lT`,ল্ড:`lD`,ল্প:`lp`,ল্ম:`lm`,ল্ল:`ll`,ল্ব:`lb`,ল:`l`,ম্থ:`mth`,ম্ফ:`mf`,ম্ভ:`mv`,মপ্ল:`mpl`,ম্ন:`mn`,ম্প:`mp`,ম্ম:`mm`,ম্ল:`ml`,ম্ব:`mb`,ম:`m`,"০":`0`,"১":`1`,"২":`2`,"৩":`3`,"৪":`4`,"৫":`5`,"৬":`6`,"৭":`7`,"৮":`8`,"৯":`9`,ঙ্ক্ষ:`Ngkx`,ঞ্ছ:`nch`,ঙ্ঘ:`ngh`,ঙ্খ:`nkh`,ঞ্ঝ:`njh`,ঙ্গৌ:`ngOU`,ঙ্গৈ:`ngOI`,ঞ্চ:`nc`,ঙ্ক:`nk`,ঙ্ষ:`Ngx`,ঙ্গ:`ngo`,ঙ্ম:`Ngm`,ঞ্জ:`nj`,ন্ধ:`ndh`,ন্ঠ:`nTh`,ণ্ঠ:`NTh`,ন্থ:`nth`,ঙ্গা:`nga`,ঙ্গি:`ngi`,ঙ্গী:`ngI`,ঙ্গু:`ngu`,ঙ্গূ:`ngU`,ঙ্গে:`nge`,ঙ্গো:`ngO`,ণ্ঢ:`NDh`,নশ:`nsh`,ঙর:`Ngr`,ঞর:`NGr`,"ংর":`ngr`,ঙ:`Ng`,ঞ:`NG`,"ং":`ng`,ন্ন:`nn`,ণ্ণ:`NN`,ণ্ন:`Nn`,ন্ম:`nm`,ণ্ম:`Nm`,ন্দ:`nd`,ন্ট:`nT`,ণ্ট:`NT`,ন্ড:`nD`,ণ্ড:`ND`,ন্ত:`nt`,ন্স:`ns`,ন:`n`,ণ:`N`,"ৈ":`OI`,"ৌ":`OU`,"ো":`O`,ঐ:`OI`,ঔ:`OU`,অ:`o`,ও:`oo`,ফ্ল:`fl`,প্ট:`pT`,প্ত:`pt`,প্ন:`pn`,প্প:`pp`,প্ল:`pl`,প্স:`ps`,ফ:`f`,প:`p`,"ৃ":`rri`,ঋ:`rri`,রর‍্য:`rry`,"্র্য":`ry`,"্রর":`rr`,ড়্গ:`Rg`,ঢ়:`Rh`,ড়:`R`,র:`r`,"্র":`r`,শ্ছ:`Sch`,ষ্ঠ:`ShTh`,ষ্ফ:`Shf`,স্ক্ল:`skl`,স্খ:`skh`,স্থ:`sth`,স্ফ:`sf`,শ্চ:`Sc`,শ্ত:`St`,শ্ন:`Sn`,শ্ম:`Sm`,শ্ল:`Sl`,ষ্ক:`Shk`,ষ্ট:`ShT`,ষ্ণ:`ShN`,ষ্প:`Shp`,ষ্ম:`Shm`,স্প্ল:`spl`,স্ক:`sk`,স্ট:`sT`,স্ত:`st`,স্ন:`sn`,স্প:`sp`,স্ম:`sm`,স্ল:`sl`,শ:`S`,ষ:`Sh`,স:`s`,"ু":`u`,উ:`u`,অ্য:`oZ`,ত্থ:`tth`,ৎ:`tt`,ট্ট:`TT`,ট্ম:`Tm`,ঠ:`Th`,ত্ন:`tn`,ত্ম:`tm`,থ:`th`,ত্ত:`tt`,ট:`T`,ত:`t`,অ্যা:`AZ`,"া":`a`,আ:`a`,য়া:`ya`,য়:`y`,"ি":`i`,ই:`i`,"ী":`ee`,ঈ:`ee`,"ূ":`uu`,ঊ:`uu`,"ে":`e`,এ:`e`,য:`z`,"্য":`Z`,ইয়:`y`,ওয়:`w`,"্ব":`w`,এক্স:`x`,"ঃ":`:`,"ঁ":`nn`,"্‌":``,"˚":`0`,"¹":`1`,"²":`2`,"³":`3`,"⁴":`4`,"⁵":`5`,"⁶":`6`,"⁷":`7`,"⁸":`8`,"⁹":`9`,"₀":`0`,"₁":`1`,"₂":`2`,"₃":`3`,"₄":`4`,"₅":`5`,"₆":`6`,"₇":`7`,"₈":`8`,"₉":`9`,"௦":`0`,"௧":`1`,"௨":`2`,"௩":`3`,"௪":`4`,"௫":`5`,"௬":`6`,"௭":`7`,"௮":`8`,"௯":`9`,"௰":`10`,"௱":`100`,"௲":`1000`,Ꜳ:`AA`,ꜳ:`aa`,Ꜵ:`AO`,ꜵ:`ao`,Ꜷ:`AU`,ꜷ:`au`,Ꜹ:`AV`,ꜹ:`av`,Ꜻ:`av`,ꜻ:`av`,Ꜽ:`AY`,ꜽ:`ay`,ȸ:`db`,ʣ:`dz`,ʥ:`dz`,ʤ:`dezh`,"🙰":`et`,ﬀ:`ff`,ﬃ:`ffi`,ﬄ:`ffl`,ﬁ:`fi`,ﬂ:`fl`,ʩ:`feng`,ʪ:`ls`,ʫ:`lz`,ɮ:`lezh`,ȹ:`qp`,ʨ:`tc`,ʦ:`ts`,ʧ:`tesh`,Ꝏ:`OO`,ꝏ:`oo`,ﬆ:`st`,ﬅ:`st`,Ꜩ:`TZ`,ꜩ:`tz`,ᵫ:`ue`,Aι:`Ai`,αι:`ai`,ἀ:`a`,ἁ:`a`,ἂ:`a`,ἃ:`a`,ἄ:`a`,ἅ:`a`,ἆ:`a`,ἇ:`a`,Ἀ:`A`,Ἁ:`A`,Ἂ:`A`,Ἃ:`A`,Ἄ:`A`,Ἅ:`A`,Ἆ:`A`,Ἇ:`A`,ᾰ:`a`,ᾱ:`a`,ᾲ:`a`,ᾳ:`a`,ᾴ:`a`,ᾶ:`a`,ᾷ:`a`,Ᾰ:`A`,Ᾱ:`A`,Ὰ:`A`,Ά:`A`,ᾼ:`A`,A̧:`A`,a̧:`a`,Ⱥ:`A`,ⱥ:`a`,Ȧ:`A`,ȧ:`a`,Ɓ:`B`,C̈:`C`,c̈:`c`,C̨:`C`,c̨:`c`,Ȼ:`C`,ȼ:`c`,C̀:`C`,c̀:`c`,C̣:`C`,c̣:`c`,C̄:`C`,c̄:`c`,C̃:`C`,c̃:`c`,Ȩ:`E`,ȩ:`e`,Ɇ:`E`,ɇ:`e`,I̧:`I`,i̧:`i`,Ɨ:`I`,ɨ:`i`,i:`i`,J́́:`J`,j́:`j`,J̀̀:`J`,j̀:`j`,J̈:`J`,j̈:`j`,J̧:`J`,j̧:`j`,J̨:`J`,j̨:`j`,Ɉ:`J`,ɉ:`j`,J̌:`J`,ǰ:`j`,J̇:`J`,j:`j`,J̣:`J`,j̣:`j`,J̄:`J`,j̄:`j`,J̃:`J`,j̃:`j`,ĸ:`k`,L̀:`L`,l̀:`l`,L̂:`L`,l̂:`l`,L̈:`L`,l̈:`l`,L̨:`L`,l̨:`l`,Ƚ:`L`,ƚ:`l`,L̇:`L`,l̇:`l`,Ḷ:`L`,ḷ:`l`,L̄:`L`,l̄:`l`,L̃:`L`,l̃:`l`,Ŋ:`N`,ŋ:`n`,Ǹ:`N`,ǹ:`n`,N̂:`N`,n̂:`n`,N̈:`N`,n̈:`n`,N̨:`N`,n̨:`n`,Ꞥ:`N`,ꞥ:`n`,Ṅ:`N`,ṅ:`n`,Ṇ:`N`,ṇ:`n`,N̄:`N`,n̄:`n`,O̧:`O`,o̧:`o`,Ǫ:`O`,ǫ:`o`,Ɵ:`O`,ɵ:`o`,Ȯ:`O`,ȯ:`o`,S̀:`S`,s̀:`s`,Ŝ̀:`S`,S̈:`S`,s̈:`s`,S̨:`S`,s̨:`s`,Ꞩ:`S`,ꞩ:`s`,Ṡ:`S`,ṡ:`s`,Ṣ:`S`,ṣ:`s`,S̄:`S`,s̄:`s`,S̃:`S`,s̃:`s`,T́:`T`,t́:`t`,T̀:`T`,t̀:`t`,T̂:`T`,t̂:`t`,T̈:`T`,ẗ:`t`,T̨:`T`,t̨:`t`,Ⱦ:`T`,ⱦ:`t`,Ṫ:`T`,ṫ:`t`,Ṭ:`T`,ṭ:`t`,T̄:`T`,t̄:`t`,T̃:`T`,t̃:`t`,U̧:`U`,u̧:`u`,Ʉ:`U`,ʉ:`u`,U̇:`U`,u̇:`u`,Ʊ:`U`,ʊ:`u`,Ẁ:`W`,ẁ:`w`,Ẃ:`W`,ẃ:`w`,Ẅ:`W`,ẅ:`w`,Ꙗ:`Ja`,ꙗ:`ja`,Y̧:`Y`,y̧:`y`,Y̨:`Y`,y̨:`y`,Ɏ:`Y`,ɏ:`y`,Y̌:`Y`,y̌:`y`,Ẏ:`Y`,ẏ:`y`,Ȳ:`Y`,ȳ:`y`,Z̀:`Z`,z̀:`z`,Ẑ:`Z`,ẑ:`z`,Z̈:`Z`,z̈:`z`,Z̧:`Z`,z̧:`z`,Z̨:`Z`,z̨:`z`,Ƶ:`Z`,ƶ:`z`,Ẓ:`Z`,ẓ:`z`,Z̄:`Z`,z̄:`z`,Z̃:`Z`,z̃:`z`,"\xA0":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `,"\u2028":` `,"\u2029":` `,"​":` `," ":` `," ":` `,"　":` `,ﾠ:` `,"«":`<<`,"»":`>>`,"‘":`'`,"’":`'`,"‚":`'`,"‛":`'`,"“":`"`,"”":`"`,"„":`"`,"‟":`"`,"‹":`'`,"›":`'`,"–":`-`,"—":`-`,"…":`...`,"€":`EUR`,$:`$`,"₢":`Cr`,"₣":`Fr.`,"£":`PS`,"₤":`L.`,ℳ:`M`,"₥":`mil`,"₦":`N`,"₧":`Pts`,"₨":`Rs`,රු:`LKR`,ரூ:`LKR`,"௹":`Rs`,रू:`NPR`,"₹":`Rs`,"૱":`Rs`,"₩":`W`,"₪":`NS`,"₸":`KZT`,"₫":`D`,"֏":`AMD`,"₭":`K`,"₺":`TL`,"₼":`AZN`,"₮":`T`,"₯":`Dr`,"₲":`PYG`,"₾":`GEL`,"₳":`ARA`,"₴":`UAH`,"₽":`RUB`,"₵":`GHS`,"₡":`CL`,"¢":`c`,"¥":`YEN`,円:`JPY`,"৳":`BDT`,元:`CNY`,"﷼":`SAR`,"៛":`KR`,"₠":`ECU`,"¤":`$?`,"฿":`THB`,"؋":`AFN`};function ad(e,t=id){e=e.normalize(`NFC`);let n=``,r;for(let i=0;i<e.length;i++)r=e.charAt(i),n+=typeof t[r]==`string`?t[r]:r;return n}function od(e,t={}){let n={allowNonAlphaStart:!1,handleCasing:`camel`,...t};var r=e.replace(/<(.*?)>/g,``);r=r.replace(/['"‘’“”ʻ\[\]\(\)\{\}:]/g,``),r=r.toLowerCase(),r=ad(r),n.allowNonAlphaStart||(r=r.replace(/^[^a-z]+/,``));let i=r.split(/[^a-z0-9]+/).filter(Boolean);if(r=``,n.handleCasing===`snake`)return i.join(`_`);for(let e=0;e<i.length;e++)n.handleCasing!==`pascal`&&e===0?r+=i[e]:r+=i[e].charAt(0).toUpperCase()+i[e].substring(1);return r}function sd(e,t={}){let n={prefix:``,suffix:``,...t},r=od(e,{handleCasing:`snake`}).toUpperCase();return r?`${n.prefix}${r}${n.suffix}`:``}function cd(e){let t=e.replace(/<(.*?)>/g,``);return t=t.toLowerCase(),t=ad(t),t=t.replace(/^[^a-z]+/,``),t=t.replace(/[^a-z0-9]+$/,``),t.split(/[^a-z0-9]+/).filter(Boolean).join(`-`)}var ld={START:`asset-indexes/start-indexing`,STOP:`asset-indexes/stop-indexing-session`,PROCESS:`asset-indexes/process-indexing-session`,OVERVIEW:`asset-indexes/indexing-session-overview`,FINISH:`asset-indexes/finish-indexing-session`},ud=new WeakMap,dd=new WeakMap,fd=new WeakMap,pd=new WeakMap,md=new WeakMap,hd=new WeakMap,gd=new WeakMap,R=new WeakSet,_d=class{constructor(e={}){r(this,R),c(this,ud,new Map),c(this,dd,null),c(this,fd,0),c(this,pd,[]),c(this,md,[]),c(this,hd,new Set),c(this,gd,new Map);let{existingSessions:n=[],maxConcurrentConnections:i=3,autoResume:a=!0}=e;this.maxConcurrentConnections=i;for(let e of n)s(ud,this).set(e.id,e);a&&(t(R,this,xd).call(this),s(dd,this)!==null&&t(R,this,Sd).call(this))}getSessions(){return Array.from(s(ud,this).values())}getCurrentSessionId(){return s(dd,this)}isProcessing(){return s(fd,this)>0}on(e,t){return s(gd,this).has(e)||s(gd,this).set(e,new Set),s(gd,this).get(e).add(t),()=>{s(gd,this).get(e)?.delete(t)}}async startIndexing(e){let n=await Yu.post(ld.START,e),{data:r}=n;return r.session&&(s(ud,this).set(r.session.id,r.session),i(dd,this,r.session.id),t(R,this,yd).call(this),r.stop||t(R,this,Sd).call(this)),r.stop&&t(R,this,bd).call(this,r.stop),n}stopSession(e){t(R,this,Cd).call(this,e),t(R,this,wd).call(this,{sessionId:e,action:ld.STOP,params:{sessionId:e},priority:!0})}getSessionOverview(e){t(R,this,wd).call(this,{sessionId:e,action:ld.OVERVIEW,params:{sessionId:e},priority:!0})}finishSession(e){t(R,this,wd).call(this,{sessionId:e.sessionId,action:ld.FINISH,params:e,priority:!0})}destroy(){s(ud,this).clear(),i(pd,this,[]),i(md,this,[]),s(gd,this).clear(),i(dd,this,null),i(fd,this,0)}};function vd(e,t){s(gd,this).get(e)?.forEach(e=>e(t))}function yd(e){t(R,this,vd).call(this,`change`,{sessions:this.getSessions(),currentSessionId:s(dd,this),reviewSessionId:e})}function bd(e){s(ud,this).delete(e),s(dd,this)===e&&i(dd,this,null),t(R,this,yd).call(this)}function xd(){for(let[e,t]of s(ud,this))if(!t.actionRequired&&!s(hd,this).has(e)){i(dd,this,e);return}i(dd,this,null)}function Sd(){if(s(dd,this)||t(R,this,xd).call(this),!s(dd,this))return;let e=s(ud,this).get(s(dd,this));if(!e)return;let n=e.totalEntries-e.processedEntries,r=this.maxConcurrentConnections-s(fd,this),i=Math.min(r,n);for(let n=0;n<i;n++)t(R,this,wd).call(this,{sessionId:e.id,action:ld.PROCESS,params:{sessionId:s(dd,this)},priority:!1});e.processIfRootEmpty&&t(R,this,wd).call(this,{sessionId:e.id,action:ld.PROCESS,params:{sessionId:s(dd,this)},priority:!1})}function Cd(e){s(hd,this).add(e),i(pd,this,s(pd,this).filter(t=>t.sessionId!==e))}function wd(e){e.priority?s(md,this).push(e):s(pd,this).push(e),t(R,this,Td).call(this)}function Td(){if(!(s(pd,this).length+s(md,this).length===0||s(fd,this)>=this.maxConcurrentConnections))for(;s(pd,this).length+s(md,this).length>0&&s(fd,this)<this.maxConcurrentConnections;){var e;i(fd,this,(e=s(fd,this),e++,e));let n=s(md,this).length>0?s(md,this).shift():s(pd,this).shift();t(R,this,Ed).call(this,n)}}async function Ed(e){try{let n=await Yu.post(e.action,e.params);t(R,this,Dd).call(this,n.data)}catch(n){t(R,this,Od).call(this,n,e)}finally{var n;i(fd,this,(n=s(fd,this),n--,n)),t(R,this,Td).call(this)}}function Dd(e){let n;e.session&&(s(ud,this).set(e.session.id,e.session),t(R,this,xd).call(this),e.session.actionRequired&&!e.skipDialog?s(hd,this).has(e.session.id)||(n=e.session.id):s(hd,this).has(e.session.id)||t(R,this,Sd).call(this)),t(R,this,xd).call(this),e.stop&&(s(ud,this).delete(e.stop),s(dd,this)===e.stop&&i(dd,this,null)),t(R,this,yd).call(this,n),s(ud,this).size===0&&t(R,this,vd).call(this,`complete`,{})}function Od(e,n){t(R,this,xd).call(this);let r=e?.response?.data?.message||e.message||`An error occurred during indexing.`;t(R,this,vd).call(this,`error`,{message:r,sessionId:n.sessionId}),t(R,this,Td).call(this)}function kd(e){return typeof e==`symbol`||e instanceof Symbol}function Ad(e,t,{signal:n,edges:r}={}){let i,a=null,o=r!=null&&r.includes(`leading`),s=r==null||r.includes(`trailing`),c=()=>{a!==null&&(e.apply(i,a),i=void 0,a=null)},l=()=>{s&&c(),p()},u=null,d=()=>{u!=null&&clearTimeout(u),u=setTimeout(()=>{u=null,l()},t)},f=()=>{u!==null&&(clearTimeout(u),u=null)},p=()=>{f(),i=void 0,a=null},m=()=>{c()},h=function(...e){if(n?.aborted)return;i=this,a=e;let t=u==null;d(),o&&t&&c()};return h.schedule=d,h.cancel=p,h.flush=m,n?.addEventListener(`abort`,p,{once:!0}),h}function jd(){}function Md(e){return e==null||typeof e!=`object`&&typeof e!=`function`}function Nd(e){return ArrayBuffer.isView(e)&&!(e instanceof DataView)}function Pd(e){if(Md(e))return e;if(Array.isArray(e)||Nd(e)||e instanceof ArrayBuffer||typeof SharedArrayBuffer<`u`&&e instanceof SharedArrayBuffer)return e.slice(0);let t=Object.getPrototypeOf(e);if(t==null)return Object.assign(Object.create(t),e);let n=t.constructor;if(e instanceof Date||e instanceof Map||e instanceof Set)return new n(e);if(e instanceof RegExp){let t=new n(e);return t.lastIndex=e.lastIndex,t}if(e instanceof DataView)return new n(e.buffer.slice(0));if(e instanceof Error){let t;return t=e instanceof AggregateError?new n(e.errors,e.message,{cause:e.cause}):new n(e.message,{cause:e.cause}),t.stack=e.stack,Object.assign(t,e),t}return typeof File<`u`&&e instanceof File?new n([e],e.name,{type:e.type,lastModified:e.lastModified}):typeof e==`object`?Object.assign(Object.create(t),e):e}function Fd(e){return Object.getOwnPropertySymbols(e).filter(t=>Object.prototype.propertyIsEnumerable.call(e,t))}function Id(e){return e==null?e===void 0?`[object Undefined]`:`[object Null]`:Object.prototype.toString.call(e)}var Ld=`[object RegExp]`,Rd=`[object String]`,zd=`[object Number]`,Bd=`[object Boolean]`,Vd=`[object Arguments]`,Hd=`[object Symbol]`,Ud=`[object Date]`,Wd=`[object Map]`,Gd=`[object Set]`,Kd=`[object Array]`,qd=`[object Function]`,Jd=`[object ArrayBuffer]`,Yd=`[object Object]`,Xd=`[object Error]`,Zd=`[object DataView]`,Qd=`[object Uint8Array]`,$d=`[object Uint8ClampedArray]`,ef=`[object Uint16Array]`,tf=`[object Uint32Array]`,nf=`[object BigUint64Array]`,rf=`[object Int8Array]`,af=`[object Int16Array]`,of=`[object Int32Array]`,sf=`[object BigInt64Array]`,cf=`[object Float32Array]`,lf=`[object Float64Array]`;function uf(e,t){return df(e,void 0,e,new Map,t)}function df(e,t,n,r=new Map,i=void 0){let a=i?.(e,t,n,r);if(a!==void 0)return a;if(Md(e))return e;if(r.has(e))return r.get(e);if(Array.isArray(e)){let t=Array(e.length);r.set(e,t);for(let a=0;a<e.length;a++)t[a]=df(e[a],a,n,r,i);return Object.hasOwn(e,`index`)&&(t.index=e.index),Object.hasOwn(e,`input`)&&(t.input=e.input),t}if(e instanceof Date)return new Date(e.getTime());if(e instanceof RegExp){let t=new RegExp(e.source,e.flags);return t.lastIndex=e.lastIndex,t}if(e instanceof Map){let t=new Map;r.set(e,t);for(let[a,o]of e)t.set(a,df(o,a,n,r,i));return t}if(e instanceof Set){let t=new Set;r.set(e,t);for(let a of e)t.add(df(a,void 0,n,r,i));return t}if(typeof Buffer<`u`&&Buffer.isBuffer(e))return e.subarray();if(Nd(e)){let t=new(Object.getPrototypeOf(e)).constructor(e.length);r.set(e,t);for(let a=0;a<e.length;a++)t[a]=df(e[a],a,n,r,i);return t}if(e instanceof ArrayBuffer||typeof SharedArrayBuffer<`u`&&e instanceof SharedArrayBuffer)return e.slice(0);if(e instanceof DataView){let t=new DataView(e.buffer.slice(0),e.byteOffset,e.byteLength);return r.set(e,t),ff(t,e,n,r,i),t}if(typeof File<`u`&&e instanceof File){let t=new File([e],e.name,{type:e.type});return r.set(e,t),ff(t,e,n,r,i),t}if(typeof Blob<`u`&&e instanceof Blob){let t=new Blob([e],{type:e.type});return r.set(e,t),ff(t,e,n,r,i),t}if(e instanceof Error){let t=structuredClone(e);return r.set(e,t),t.message=e.message,t.name=e.name,t.stack=e.stack,t.cause=e.cause,t.constructor=e.constructor,ff(t,e,n,r,i),t}if(e instanceof Boolean){let t=new Boolean(e.valueOf());return r.set(e,t),ff(t,e,n,r,i),t}if(e instanceof Number){let t=new Number(e.valueOf());return r.set(e,t),ff(t,e,n,r,i),t}if(e instanceof String){let t=new String(e.valueOf());return r.set(e,t),ff(t,e,n,r,i),t}if(typeof e==`object`&&pf(e)){let t=Object.create(Object.getPrototypeOf(e));return r.set(e,t),ff(t,e,n,r,i),t}return e}function ff(e,t,n=e,r,i){let a=[...Object.keys(t),...Fd(t)];for(let o=0;o<a.length;o++){let s=a[o],c=Object.getOwnPropertyDescriptor(e,s);(c==null||c.writable)&&(e[s]=df(t[s],s,n,r,i))}}function pf(e){switch(Id(e)){case Vd:case Kd:case Jd:case Zd:case Bd:case Ud:case cf:case lf:case rf:case af:case of:case Wd:case zd:case Yd:case Ld:case Gd:case Rd:case Hd:case Qd:case $d:case ef:case tf:return!0;default:return!1}}function mf(e){return df(e,void 0,e,new Map,void 0)}function hf(e){if(!e||typeof e!=`object`)return!1;let t=Object.getPrototypeOf(e);return t===null||t===Object.prototype||Object.getPrototypeOf(t)===null?Object.prototype.toString.call(e)===`[object Object]`:!1}function gf(e){return e===`__proto__`}function _f(e){if(typeof e!=`object`||!e)return!1;if(Object.getPrototypeOf(e)===null)return!0;if(Object.prototype.toString.call(e)!==`[object Object]`){let t=e[Symbol.toStringTag];return t==null||!Object.getOwnPropertyDescriptor(e,Symbol.toStringTag)?.writable?!1:e.toString()===`[object ${t}]`}let t=e;for(;Object.getPrototypeOf(t)!==null;)t=Object.getPrototypeOf(t);return Object.getPrototypeOf(e)===t}function vf(e,t){return e===t||Number.isNaN(e)&&Number.isNaN(t)}function yf(e,t,n){return bf(e,t,void 0,void 0,void 0,void 0,n)}function bf(e,t,n,r,i,a,o){let s=o(e,t,n,r,i,a);if(s!==void 0)return s;if(typeof e==typeof t)switch(typeof e){case`bigint`:case`string`:case`boolean`:case`symbol`:case`undefined`:return e===t;case`number`:return e===t||Object.is(e,t);case`function`:return e===t;case`object`:return xf(e,t,a,o)}return xf(e,t,a,o)}function xf(e,t,n,r){if(Object.is(e,t))return!0;let i=Id(e),a=Id(t);if(i===`[object Arguments]`&&(i=Yd),a===`[object Arguments]`&&(a=Yd),i!==a)return!1;switch(i){case Rd:return e.toString()===t.toString();case zd:return vf(e.valueOf(),t.valueOf());case Bd:case Ud:case Hd:return Object.is(e.valueOf(),t.valueOf());case Ld:return e.source===t.source&&e.flags===t.flags;case qd:return e===t}n??=new Map;let o=n.get(e),s=n.get(t);if(o!=null&&s!=null)return o===t;n.set(e,t),n.set(t,e);try{switch(i){case Wd:if(e.size!==t.size)return!1;for(let[i,a]of e.entries())if(!t.has(i)||!bf(a,t.get(i),i,e,t,n,r))return!1;return!0;case Gd:{if(e.size!==t.size)return!1;let i=Array.from(e.values()),a=Array.from(t.values());for(let o=0;o<i.length;o++){let s=i[o],c=a.findIndex(i=>bf(s,i,void 0,e,t,n,r));if(c===-1)return!1;a.splice(c,1)}return!0}case Kd:case Qd:case $d:case ef:case tf:case nf:case rf:case af:case of:case sf:case cf:case lf:if(typeof Buffer<`u`&&Buffer.isBuffer(e)!==Buffer.isBuffer(t)||e.length!==t.length)return!1;for(let i=0;i<e.length;i++)if(!bf(e[i],t[i],i,e,t,n,r))return!1;return!0;case Jd:return e.byteLength===t.byteLength?xf(new Uint8Array(e),new Uint8Array(t),n,r):!1;case Zd:return e.byteLength!==t.byteLength||e.byteOffset!==t.byteOffset?!1:xf(new Uint8Array(e),new Uint8Array(t),n,r);case Xd:return e.name===t.name&&e.message===t.message;case Yd:{if(!(xf(e.constructor,t.constructor,n,r)||hf(e)&&hf(t)))return!1;let i=[...Object.keys(e),...Fd(e)],a=[...Object.keys(t),...Fd(t)];if(i.length!==a.length)return!1;for(let a=0;a<i.length;a++){let o=i[a],s=e[o];if(!Object.hasOwn(t,o))return!1;let c=t[o];if(!bf(s,c,o,e,t,n,r))return!1}return!0}default:return!1}}finally{n.delete(e),n.delete(t)}}function Sf(e,t){return yf(e,t,jd)}function Cf(e){return Number.isSafeInteger(e)&&e>=0}var wf={"&":`&amp;`,"<":`&lt;`,">":`&gt;`,'"':`&quot;`,"'":`&#39;`};function Tf(e){return e.replace(/[&<>"']/g,e=>wf[e])}function Ef(e){return e!=null&&typeof e!=`function`&&Cf(e.length)}function Df(e){switch(typeof e){case`number`:case`symbol`:return!1;case`string`:return e.includes(`.`)||e.includes(`[`)||e.includes(`]`)}}function Of(e){return typeof e==`string`||typeof e==`symbol`?e:Object.is(e?.valueOf?.(),-0)?`-0`:String(e)}function kf(e){if(e==null)return``;if(typeof e==`string`)return e;if(Array.isArray(e))return e.map(kf).join(`,`);let t=String(e);return t===`0`&&Object.is(Number(e),-0)?`-0`:t}function Af(e){if(Array.isArray(e))return e.map(Of);if(typeof e==`symbol`)return[e];e=kf(e);let t=[],n=e.length;if(n===0)return t;let r=0,i=``,a=``,o=!1;for(e.charCodeAt(0)===46&&(t.push(``),r++);r<n;){let s=e[r];a?s===`\\`&&r+1<n?(r++,i+=e[r]):s===a?a=``:i+=s:o?s===`"`||s===`'`?a=s:s===`]`?(o=!1,t.push(i),i=``):i+=s:s===`[`?(o=!0,i&&=(t.push(i),``)):s===`.`?i&&=(t.push(i),``):i+=s,r++}return i&&t.push(i),t}function jf(e,t,n){if(e==null)return n;switch(typeof t){case`string`:{if(gf(t))return n;let r=e[t];return r===void 0?Df(t)?jf(e,Af(t),n):n:r}case`number`:case`symbol`:{typeof t==`number`&&(t=Of(t));let r=e[t];return r===void 0?n:r}default:{if(Array.isArray(t))return Mf(e,t,n);if(t=Object.is(t?.valueOf(),-0)?`-0`:String(t),gf(t))return n;let r=e[t];return r===void 0?n:r}}}function Mf(e,t,n){if(t.length===0)return n;let r=e;for(let e=0;e<t.length;e++){if(r==null||gf(t[e]))return n;r=r[t[e]]}return r===void 0?n:r}function Nf(e){return e!==null&&(typeof e==`object`||typeof e==`function`)}function Pf(e,t){return uf(e,(n,r,i,a)=>{let o=t?.(n,r,i,a);if(o!==void 0)return o;if(typeof e==`object`){if(Id(e)===`[object Object]`&&typeof e.constructor!=`function`){let t={};return a.set(e,t),ff(t,e,i,a),t}switch(Object.prototype.toString.call(e)){case zd:case Rd:case Bd:{let t=new e.constructor(e?.valueOf());return ff(t,e),t}case Vd:{let t={};return ff(t,e),t.length=e.length,t[Symbol.iterator]=e[Symbol.iterator],t}default:return}}})}function Ff(e){return Pf(e)}var If=/^(?:0|[1-9]\d*)$/;function Lf(e,t=2**53-1){switch(typeof e){case`number`:return Number.isInteger(e)&&e>=0&&e<t;case`symbol`:return!1;case`string`:return If.test(e)}}function Rf(e){return typeof e==`object`&&!!e&&Id(e)===`[object Arguments]`}function zf(e,t){let n;if(n=Array.isArray(t)?t:typeof t==`string`&&Df(t)&&e?.[t]==null?Af(t):[t],n.length===0)return!1;let r=e;for(let e=0;e<n.length;e++){let t=n[e];if((r==null||!Object.hasOwn(r,t))&&!((Array.isArray(r)||Rf(r))&&Lf(t)&&t<r.length))return!1;r=r[t]}return!0}function Bf(e){return typeof e==`object`&&!!e}function Vf(e){return Bf(e)&&Ef(e)}var Hf=/\.|\[(?:[^[\]]*|(["'])(?:(?!\1)[^\\]|\\.)*?\1)\]/,Uf=/^\w*$/;function Wf(e,t){return Array.isArray(e)?!1:typeof e==`number`||typeof e==`boolean`||e==null||kd(e)?!0:typeof e==`string`&&(Uf.test(e)||!Hf.test(e))||t!=null&&Object.hasOwn(t,e)}var Gf=(e,t,n)=>{let r=e[t];(!(Object.hasOwn(e,t)&&vf(r,n))||n===void 0&&!(t in e))&&(e[t]=n)};function Kf(e,t,n,r){if(e==null&&!Nf(e))return e;let i;i=Wf(t,e)?[t]:Array.isArray(t)?t:Af(t);let a=n(jf(e,i)),o=e;for(let t=0;t<i.length&&o!=null;t++){let n=Of(i[t]);if(gf(n))continue;let s;if(t===i.length-1)s=a;else{let a=o[n],c=r?.(a,n,e);s=c===void 0?Nf(a)?a:Lf(i[t+1])?[]:{}:c}Gf(o,n,s),o=o[n]}return e}function qf(e,t,n){return Kf(e,t,()=>n,()=>void 0)}function Jf(e,t=0,n={}){typeof n!=`object`&&(n={});let{leading:r=!1,trailing:i=!0,maxWait:a}=n,o=[,,];r&&(o[0]=`leading`),i&&(o[1]=`trailing`);let s,c=null,l=Ad(function(...t){s=e.apply(this,t),c=null},t,{edges:o}),u=function(...t){return a!=null&&(c===null&&(c=Date.now()),Date.now()-c>=a)?(s=e.apply(this,t),c=Date.now(),l.cancel(),l.schedule(),s):(l.apply(this,t),s)};return u.cancel=l.cancel,u.flush=()=>(l.flush(),s),u}function Yf(e){return Nd(e)}function Xf(e,...t){let n=t.slice(0,-1),r=t[t.length-1],i=e;for(let e=0;e<n.length;e++){let t=n[e];i=Zf(i,t,r,new Map)}return i}function Zf(e,t,n,r){if(Md(e)&&(e=Object(e)),typeof t!=`object`||!t)return e;if(r.has(t))return Pd(r.get(t));if(r.set(t,e),Array.isArray(t)){t=t.slice();for(let e=0;e<t.length;e++)t[e]=t[e]??void 0}let i=[...Object.keys(t),...Fd(t)];for(let a=0;a<i.length;a++){let o=i[a];if(gf(o))continue;let s=t[o],c=e[o];if(Rf(s)&&(s={...s}),Rf(c)&&(c={...c}),typeof Buffer<`u`&&Buffer.isBuffer(s)&&(s=Ff(s)),Array.isArray(s))if(Array.isArray(c)){let e=[],t=Reflect.ownKeys(c);for(let n=0;n<t.length;n++){let r=t[n];e[r]=c[r]}c=e}else if(Vf(c)){let e=[];for(let t=0;t<c.length;t++)e[t]=c[t];c=e}else c=[];let l=n(c,s,o,e,t,r);l===void 0?Array.isArray(s)||Bf(c)&&Bf(s)&&(_f(c)||_f(s)||Yf(c)||Yf(s))?e[o]=Zf(c,s,n,r):c==null&&_f(s)?e[o]=Zf({},s,n,r):c==null&&Yf(s)?e[o]=Ff(s):(c===void 0||s!==void 0)&&(e[o]=s):e[o]=l}return e}function Qf(e,...t){return Xf(e,...t,jd)}function $f(e){return Tf(kf(e))}var ep=e=>typeof File<`u`&&e instanceof File||e instanceof Blob||typeof FileList<`u`&&e instanceof FileList&&e.length>0,tp=e=>e instanceof FormData?!0:ep(e)||typeof e==`object`&&!!e&&Object.values(e).some(e=>tp(e)),np=class extends Error{response;constructor(e){super(`HTTP error ${e.status}`),this.name=`HttpResponseError`,this.response=e}},rp=class extends Error{constructor(e=`Request was cancelled`){super(e),this.name=`HttpCancelledError`}},ip=class extends Error{constructor(e=`Network error`){super(e),this.name=`HttpNetworkError`}};function ap(e){let t=new URLSearchParams;return Object.entries(e).forEach(([e,n])=>{n!=null&&(Array.isArray(n)?n.forEach(n=>t.append(`${e}[]`,String(n))):typeof n==`object`?t.append(e,JSON.stringify(n)):t.append(e,String(n)))}),t.toString()}function op(e,t,n){if(t&&!e.startsWith(`http://`)&&!e.startsWith(`https://`)&&(e=t.replace(/\/$/,``)+`/`+e.replace(/^\//,``)),n&&Object.keys(n).length>0){let t=ap(n);t&&(e+=(e.includes(`?`)?`&`:`?`)+t)}return e}function sp(){return typeof window>`u`?null:window.axios?.defaults?.headers?.common?.[`X-Requested-With`]??null}function cp(e,t=new FormData,n=null){for(let r in e)Object.prototype.hasOwnProperty.call(e,r)&&lp(t,n?`${n}[${r}]`:r,e[r]);return t}function lp(e,t,n){if(Array.isArray(n))return n.forEach((n,r)=>lp(e,`${t}[${r}]`,n));if(n instanceof Date)return e.append(t,n.toISOString());if(typeof File<`u`&&n instanceof File)return e.append(t,n,n.name);if(n instanceof Blob)return e.append(t,n);if(typeof n==`boolean`)return e.append(t,n?`1`:`0`);if(typeof n==`string`)return e.append(t,n);if(typeof n==`number`)return e.append(t,`${n}`);if(n==null)return e.append(t,``);cp(n,e,t)}function up(e,t){if(e!=null)return e instanceof FormData?e:typeof e==`object`&&tp(e)?cp(e):typeof e==`object`||t[`Content-Type`]?.includes(`application/json`)?JSON.stringify(e):String(e)}function dp(e){let t={};return e.forEach((e,n)=>{t[n.toLowerCase()]=e}),t}function fp(e={}){let t=e.xsrfCookieName??`XSRF-TOKEN`,n=e.xsrfHeaderName??`X-XSRF-TOKEN`;function r(){if(typeof document>`u`)return null;let e=document.cookie.match(RegExp(`(^|;\\s*)`+t+`=([^;]*)`));return e?decodeURIComponent(e[2]):null}return{setXsrfCookieName(e){t=e},setXsrfHeaderName(e){n=e},async request(e){let t=op(e.url,e.baseURL,e.params),i=e.method.toUpperCase(),a={},o=sp();o&&(a[`X-Requested-With`]=o),e.data!==void 0&&![`GET`,`DELETE`].includes(i)&&!(e.data instanceof FormData)&&!tp(e.data)&&(a[`Content-Type`]=`application/json`),e.headers&&Object.entries(e.headers).forEach(([e,t])=>{t!==void 0&&(a[e]=String(t))});let s=r();s&&![`GET`,`HEAD`,`OPTIONS`].includes(i)&&(a[n]=s);let c=e.signal,l,u=e.timeout??3e4;if(u>0&&!c){let e=new AbortController;c=e.signal,l=setTimeout(()=>e.abort(),u)}let d=[`GET`,`DELETE`].includes(i)?void 0:up(e.data,a);d instanceof FormData&&delete a[`Content-Type`];try{let n=await fetch(t,{method:i,headers:a,body:d,signal:c,credentials:e.credentials??`same-origin`});l&&clearTimeout(l);let r;r=n.headers.get(`content-type`)?.includes(`application/json`)?await n.json():await n.text();let o={status:n.status,data:r,headers:dp(n.headers)};if(!n.ok)throw new np(o);return o}catch(e){throw l&&clearTimeout(l),e instanceof np?e:e instanceof DOMException&&e.name===`AbortError`?new rp:e instanceof TypeError?new ip(e.message):e}}}}var pp=fp(),mp=pp,hp=void 0,gp=void 0,_p=`same-origin`,vp=e=>`${e.method}:${e.baseURL??hp??``}${e.url}`,yp=e=>e.status===204&&e.headers[`precognition-success`]===`true`,bp={},xp={get:(e,t={},n={})=>wp(Sp(`get`,e,t,n)),post:(e,t={},n={})=>wp(Sp(`post`,e,t,n)),patch:(e,t={},n={})=>wp(Sp(`patch`,e,t,n)),put:(e,t={},n={})=>wp(Sp(`put`,e,t,n)),delete:(e,t={},n={})=>wp(Sp(`delete`,e,t,n)),useHttpClient(e){return mp=e,xp},withBaseURL(e){return hp=e,xp},withTimeout(e){return gp=e,xp},withCredentials(e){return _p=typeof e==`string`?e:e?`include`:`omit`,xp},fingerprintRequestsUsing(e){return vp=e===null?()=>null:e,xp},determineSuccessUsing(e){return yp=e,xp},withXsrfCookieName(e){return pp.setXsrfCookieName(e),xp},withXsrfHeaderName(e){return pp.setXsrfHeaderName(e),xp}},Sp=(e,t,n,r)=>({url:t,method:e,...r,...[`get`,`delete`].includes(e)?{params:Qf({},n,r?.params)}:{data:Qf({},n,r?.data)}}),wp=(e={})=>{let t=[Tp,Dp,Op].reduce((e,t)=>t(e),e);return(t.onBefore??(()=>!0))()===!1?Promise.resolve(null):((t.onStart??(()=>null))(),mp.request({method:t.method,url:t.url,baseURL:t.baseURL??hp,data:t.data,params:t.params,headers:t.headers,signal:t.signal,timeout:t.timeout,credentials:_p}).then(async e=>{t.precognitive&&kp(e);let n=e.status,r=e;return t.precognitive&&t.onPrecognitionSuccess&&yp(e)&&(r=await Promise.resolve(t.onPrecognitionSuccess(e)??r)),t.onSuccess&&Ep(n)&&(r=await Promise.resolve(t.onSuccess(r)??r)),(jp(t,n)??(e=>e))(r)??r},e=>{if(Ap(e))return Promise.reject(e);let n=e;return t.precognitive&&kp(n.response),(jp(t,n.response.status)??((e,t)=>Promise.reject(t)))(n.response,n)}).finally(t.onFinish??(()=>null)))},Tp=e=>{let t=e.only??e.validate;return{...e,timeout:e.timeout??gp,precognitive:e.precognitive!==!1,fingerprint:e.fingerprint===void 0?vp(e,mp):e.fingerprint,headers:{...e.headers,Accept:`application/json`,"Content-Type":Mp(e),...e.precognitive===!1?{}:{Precognition:!0},...t?{"Precognition-Validate-Only":Array.from(t).join()}:{}}}},Ep=e=>e>=200&&e<300,Dp=e=>typeof e.fingerprint==`string`?(bp[e.fingerprint]?.abort(),delete bp[e.fingerprint],e):e,Op=e=>typeof e.fingerprint!=`string`||e.signal||!e.precognitive?e:(bp[e.fingerprint]=new AbortController,{...e,signal:bp[e.fingerprint].signal}),kp=e=>{if(e.headers?.precognition!==`true`)throw Error(`Did not receive a Precognition response. Ensure you have the Precognition middleware in place for the route.`)},Ap=e=>!(e instanceof np)||typeof e.response?.status!=`number`,jp=(e,t)=>({401:e.onUnauthorized,403:e.onForbidden,404:e.onNotFound,409:e.onConflict,422:e.onValidationError,423:e.onLocked})[t],Mp=e=>e.headers?.[`Content-Type`]??e.headers?.[`Content-type`]??e.headers?.[`content-type`]??(tp(e.data)?`multipart/form-data`:`application/json`),Np=(e,t)=>{if(!e.includes(`*`))return[e];let n=e.split(`.`),r=[``];for(let e of n)if(e===`*`){let e=[];for(let n of r){let r=n?jf(t,n):t;if(Array.isArray(r))for(let t=0;t<r.length;t++)e.push(n?`${n}.${t}`:String(t));else if(typeof r==`object`&&r)for(let t of Object.keys(r))e.push(n?`${n}.${t}`:t)}r=e}else r=r.map(t=>t?`${t}.${e}`:e);return r},Pp=(e,t)=>t.includes(`*`)?RegExp(`^`+t.replace(/\./g,`\\.`).replace(/\*/g,`[^.]+`)+`$`).test(e):e===t,Fp=(e,t)=>Object.fromEntries(Object.entries(e).filter(([e])=>!t.some(t=>Pp(e,t)))),Ip=(e,t={})=>{let n={errorsChanged:[],touchedChanged:[],validatingChanged:[],validatedChanged:[]},r=!1,i=!1,a=e=>e===i?[]:(i=e,n.validatingChanged),o=[],s=e=>{let t=[...new Set(e)];return o.length!==t.length||!t.every(e=>o.includes(e))?(o=t,n.validatedChanged):[]},c=()=>o.filter(e=>d[e]===void 0),l=[],u=e=>{let t=[...new Set(e)];return l.length!==t.length||!t.every(e=>l.includes(e))?(l=t,n.touchedChanged):[]},d={},f=e=>{let t=Rp(e);return Sf(d,t)?[]:(d=t,n.errorsChanged)},p=e=>{let t={...d};return delete t[zp(e)],f(t)},m=()=>Object.keys(d).length>0,h=1500,g=e=>{h=e,S.cancel(),S=x()},_=t,v=null,y=[],b=null,x=()=>Jf(t=>{e({get:(e,n={},r={})=>xp.get(e,T(n),C(r,t,n)),post:(e,n={},r={})=>xp.post(e,T(n),C(r,t,n)),patch:(e,n={},r={})=>xp.patch(e,T(n),C(r,t,n)),put:(e,n={},r={})=>xp.put(e,T(n),C(r,t,n)),delete:(e,n={},r={})=>xp.delete(e,T(n),C(r,t,n))}).catch(e=>e instanceof rp||e instanceof np&&e.response?.status===422?null:Promise.reject(e))},h,{leading:!0,trailing:!0}),S=x(),C=(e,t,n={})=>{let r={...e,...t},i=Array.from(r.only??r.validate??l);return{...t,...Qf({},e,t),only:i,timeout:r.timeout??5e3,onValidationError:(e,t)=>([...s([...o,...i]),...f(Qf(Fp({...d},i),e.data.errors))].forEach(e=>e()),r.onValidationError?r.onValidationError(e,t):Promise.reject(t)),onSuccess:e=>(s([...o,...i]).forEach(e=>e()),r.onSuccess?r.onSuccess(e):e),onPrecognitionSuccess:e=>([...s([...o,...i]),...f(Fp({...d},i))].forEach(e=>e()),r.onPrecognitionSuccess?r.onPrecognitionSuccess(e):e),onBefore:()=>{let e=l.some(e=>e.includes(`*`)),t=e?[...new Set(l.flatMap(e=>Np(e,n)))]:l;return r.onBeforeValidation&&r.onBeforeValidation({data:n,touched:t},{data:_,touched:y})===!1||(r.onBefore||(()=>!0))()===!1?!1:(e&&u(t).forEach(e=>e()),b=l,v=n,!0)},onStart:()=>{a(!0).forEach(e=>e()),(r.onStart??(()=>null))()},onFinish:()=>{a(!1).forEach(e=>e()),y=b,_=v,b=v=null,(r.onFinish??(()=>null))()}}},w=(e,t,n)=>{if(e===void 0){let e=Array.from(n?.only??n?.validate??[]);u([...l,...e]).forEach(e=>e()),S(n??{});return}if(ep(t)&&!r){console.warn(`Precognition file validation is not active. Call the "validateFiles" function on your form to enable it.`);return}e=zp(e),(e.includes(`*`)||jf(_,e)!==t)&&(u([e,...l]).forEach(e=>e()),S(n??{}))},T=e=>r===!1?Bp(e):e,E={touched:()=>l,validate(e,t,n){return typeof e==`object`&&!(`target`in e)&&(n=e,e=t=void 0),w(e,t,n),E},touch(e){let t=Array.isArray(e)?e:[zp(e)];return u([...l,...t]).forEach(e=>e()),E},validating:()=>i,valid:c,errors:()=>d,hasErrors:m,setErrors(e){return f(e).forEach(e=>e()),E},forgetError(e){return p(e).forEach(e=>e()),E},defaults(e){return t=e,_=e,E},reset(...e){if(e.length===0)u([]).forEach(e=>e());else{let n=[...l];e.forEach(e=>{n.includes(e)&&n.splice(n.indexOf(e),1),qf(_,e,jf(t,e))}),u(n).forEach(e=>e())}return E},setTimeout(e){return g(e),E},on(e,t){return n[e].push(t),E},validateFiles(){return r=!0,E},withoutFileValidation(){return r=!1,E}};return E},Lp=e=>Object.keys(e).reduce((t,n)=>({...t,[n]:Array.isArray(e[n])?e[n][0]:e[n]}),{}),Rp=e=>Object.keys(e).reduce((t,n)=>({...t,[n]:typeof e[n]==`string`?[e[n]]:e[n]}),{}),zp=e=>typeof e==`string`?e:e.target.name,Bp=e=>{let t={...e};return Object.keys(t).forEach(e=>{let n=t[e];if(n!==null){if(ep(n)){delete t[e];return}if(Array.isArray(n)){t[e]=Object.values(Bp({...n}));return}if(typeof n==`object`){t[e]=Bp(t[e]);return}}}),t},Vp=new class{constructor(e){this.config={},this.defaults=e}extend(e){return e&&(this.defaults={...this.defaults,...e}),this}replace(e){this.config=e}get(e){return zf(this.config,e)?jf(this.config,e):jf(this.defaults,e)}set(e,t){typeof e==`string`?qf(this.config,e,t):Object.entries(e).forEach(([e,t])=>{qf(this.config,e,t)})}}({form:{recentlySuccessfulDuration:2e3,forceIndicesArrayFormatInFormData:!0,withAllErrors:!1},prefetch:{cacheFor:3e4,hoverDelay:75}});function Hp(e,t){let n;return function(...r){clearTimeout(n),n=setTimeout(()=>e.apply(this,r),t)}}function Up(e,t){return document.dispatchEvent(new CustomEvent(`inertia:${e}`,t))}var Wp=e=>Up(`before`,{cancelable:!0,detail:{visit:e}}),Gp=e=>Up(`error`,{detail:{errors:e}}),Kp=e=>Up(`networkError`,{cancelable:!0,detail:{error:e}}),qp=e=>Up(`finish`,{detail:{visit:e}}),Jp=e=>Up(`httpException`,{cancelable:!0,detail:{response:e}}),Yp=e=>Up(`beforeUpdate`,{detail:{page:e}}),Xp=e=>Up(`navigate`,{detail:{page:e}}),Zp=e=>Up(`progress`,{detail:{progress:e}}),Qp=e=>Up(`start`,{detail:{visit:e}}),$p=e=>Up(`success`,{detail:{page:e}}),em=(e,t)=>Up(`prefetched`,{detail:{fetchedAt:Date.now(),response:e,visit:t}}),tm=e=>Up(`prefetching`,{detail:{visit:e}}),nm=e=>Up(`flash`,{detail:{flash:e}}),rm=class{static{this.locationVisitKey=`inertiaLocationVisit`}static set(e,t){typeof window<`u`&&window.sessionStorage.setItem(e,JSON.stringify(t))}static get(e){if(typeof window<`u`)return JSON.parse(window.sessionStorage.getItem(e)||`null`)}static merge(e,t){let n=this.get(e);n===null?this.set(e,t):this.set(e,{...n,...t})}static remove(e){typeof window<`u`&&window.sessionStorage.removeItem(e)}static removeNested(e,t){let n=this.get(e);n!==null&&(delete n[t],this.set(e,n))}static exists(e){try{return this.get(e)!==null}catch{return!1}}static clear(){typeof window<`u`&&window.sessionStorage.clear()}},im=async e=>{if(typeof window>`u`)throw Error(`Unable to encrypt history`);let t=lm(),n=await fm(await pm());if(!n)throw Error(`Unable to encrypt history`);return await sm(t,n,e)},am={key:`historyKey`,iv:`historyIv`},om=async e=>{let t=lm(),n=await pm();if(!n)throw Error(`Unable to decrypt history`);return await cm(t,n,e)},sm=async(e,t,n)=>{if(typeof window>`u`)throw Error(`Unable to encrypt history`);if(window.crypto.subtle===void 0)return console.warn(`Encryption is not supported in this environment. SSL is required.`),Promise.resolve(n);let r=new TextEncoder,i=JSON.stringify(n),a=new Uint8Array(i.length*3),o=r.encodeInto(i,a);return window.crypto.subtle.encrypt({name:`AES-GCM`,iv:e},t,a.subarray(0,o.written))},cm=async(e,t,n)=>{if(window.crypto.subtle===void 0)return console.warn(`Decryption is not supported in this environment. SSL is required.`),Promise.resolve(n);let r=await window.crypto.subtle.decrypt({name:`AES-GCM`,iv:e},t,n);return JSON.parse(new TextDecoder().decode(r))},lm=()=>{let e=rm.get(am.iv);if(e)return new Uint8Array(e);let t=window.crypto.getRandomValues(new Uint8Array(12));return rm.set(am.iv,Array.from(t)),t},um=async()=>window.crypto.subtle===void 0?(console.warn(`Encryption is not supported in this environment. SSL is required.`),Promise.resolve(null)):window.crypto.subtle.generateKey({name:`AES-GCM`,length:256},!0,[`encrypt`,`decrypt`]),dm=async e=>{if(window.crypto.subtle===void 0)return console.warn(`Encryption is not supported in this environment. SSL is required.`),Promise.resolve();let t=await window.crypto.subtle.exportKey(`raw`,e);rm.set(am.key,Array.from(new Uint8Array(t)))},fm=async e=>{if(e)return e;let t=await um();return t?(await dm(t),t):null},pm=async()=>{let e=rm.get(am.key);return e?await window.crypto.subtle.importKey(`raw`,new Uint8Array(e),{name:`AES-GCM`,length:256},!0,[`encrypt`,`decrypt`]):null},mm=(e,t,n)=>{if(e===t)return!0;for(let r in e)if(!n.includes(r)&&e[r]!==t[r]&&!hm(e[r],t[r]))return!1;for(let r in t)if(!n.includes(r)&&!(r in e))return!1;return!0},hm=(e,t)=>{switch(typeof e){case`object`:return mm(e,t,[]);case`function`:return e.toString()===t.toString();default:return e===t}},gm={ms:1,s:1e3,m:1e3*60,h:1e3*60*60,d:1e3*60*60*24},_m=e=>{if(typeof e==`number`)return e;for(let[t,n]of Object.entries(gm))if(e.endsWith(t))return parseFloat(e)*n;return parseInt(e)},vm=new class{constructor(){this.cached=[],this.inFlightRequests=[],this.removalTimers=[],this.currentUseId=null}add(e,t,{cacheFor:n,cacheTags:r}){if(this.findInFlight(e))return Promise.resolve();let i=this.findCached(e);if(!e.fresh&&i&&i.staleTimestamp>Date.now())return Promise.resolve();let[a,o]=this.extractStaleValues(n),s=new Promise((n,r)=>{t({...e,onCancel:()=>{this.remove(e),e.onCancel(),r()},onError:t=>{this.remove(e),e.onError(t),r()},onPrefetching(t){e.onPrefetching(t)},onPrefetched(t,n){e.onPrefetched(t,n)},onPrefetchResponse(e){n(e)},onPrefetchError(t){vm.removeFromInFlight(e),r(t)}})}).then(t=>{this.remove(e);let n=t.getPageResponse();z.mergeOncePropsIntoResponse(n),this.cached.push({params:{...e},staleTimestamp:Date.now()+a,expiresAt:Date.now()+o,response:s,singleUse:o===0,timestamp:Date.now(),inFlight:!1,tags:Array.isArray(r)?r:[r]});let i=this.getShortestOncePropTtl(n);return this.scheduleForRemoval(e,i?Math.min(o,i):o),this.removeFromInFlight(e),t.handlePrefetch(),t});return this.inFlightRequests.push({params:{...e},response:s,staleTimestamp:null,inFlight:!0}),s}removeAll(){this.cached=[],this.removalTimers.forEach(e=>{clearTimeout(e.timer)}),this.removalTimers=[]}removeByTags(e){this.cached=this.cached.filter(t=>!t.tags.some(t=>e.includes(t)))}remove(e){this.cached=this.cached.filter(t=>!this.paramsAreEqual(t.params,e)),this.clearTimer(e)}removeFromInFlight(e){this.inFlightRequests=this.inFlightRequests.filter(t=>!this.paramsAreEqual(t.params,e))}extractStaleValues(e){let[t,n]=this.cacheForToStaleAndExpires(e);return[_m(t),_m(n)]}cacheForToStaleAndExpires(e){if(!Array.isArray(e))return[e,e];switch(e.length){case 0:return[0,0];case 1:return[e[0],e[0]];default:return[e[0],e[1]]}}clearTimer(e){let t=this.removalTimers.find(t=>this.paramsAreEqual(t.params,e));t&&(clearTimeout(t.timer),this.removalTimers=this.removalTimers.filter(e=>e!==t))}scheduleForRemoval(e,t){if(!(typeof window>`u`)&&(this.clearTimer(e),t>0)){let n=window.setTimeout(()=>this.remove(e),t);this.removalTimers.push({params:e,timer:n})}}get(e){return this.findCached(e)||this.findInFlight(e)}use(e,t){let n=`${t.url.pathname}-${Date.now()}-${Math.random().toString(36).substring(7)}`;return this.currentUseId=n,e.response.then(e=>{if(this.currentUseId===n)return e.mergeParams({...t,onPrefetched:()=>{}}),this.removeSingleUseItems(t),e.handle()})}removeSingleUseItems(e){this.cached=this.cached.filter(t=>this.paramsAreEqual(t.params,e)?!t.singleUse:!0)}findCached(e){return this.cached.find(t=>this.paramsAreEqual(t.params,e))||null}findInFlight(e){return this.inFlightRequests.find(t=>this.paramsAreEqual(t.params,e))||null}withoutPurposePrefetchHeader(e){let t=mf(e);return t.headers.Purpose===`prefetch`&&delete t.headers.Purpose,t}paramsAreEqual(e,t){return mm(this.withoutPurposePrefetchHeader(e),this.withoutPurposePrefetchHeader(t),[`showProgress`,`replace`,`prefetch`,`preserveScroll`,`preserveState`,`onBefore`,`onBeforeUpdate`,`onStart`,`onProgress`,`onFinish`,`onCancel`,`onSuccess`,`onError`,`onFlash`,`onPrefetched`,`onCancelToken`,`onPrefetching`,`async`,`viewTransition`,`optimistic`,`component`,`pageProps`])}updateCachedOncePropsFromCurrentPage(){this.cached.forEach(e=>{e.response.then(t=>{let n=t.getPageResponse();z.mergeOncePropsIntoResponse(n,{force:!0});for(let[e,t]of Object.entries(n.deferredProps??{})){let r=t.filter(e=>jf(n.props,e)===void 0);r.length>0?n.deferredProps[e]=r:delete n.deferredProps[e]}let r=this.getShortestOncePropTtl(n);if(r===null)return;let i=e.expiresAt-Date.now(),a=Math.min(i,r);a>0?this.scheduleForRemoval(e.params,a):this.remove(e.params)})})}getShortestOncePropTtl(e){let t=Object.values(e.onceProps??{}).map(e=>e.expiresAt).filter(e=>!!e);return t.length===0?null:Math.min(...t)-Date.now()}},ym=(e,t=1)=>{window.requestAnimationFrame(()=>{t>1?ym(e,t-1):e()})},bm=e=>{if(typeof window>`u`)return null;let t=document.querySelector(`script[data-page="${e}"][type="application/json"]`);return t?.textContent?JSON.parse(t.textContent):null},xm=typeof window>`u`,Sm=!xm&&/Firefox/i.test(window.navigator.userAgent),Cm=class{static save(){B.saveScrollPositions(this.getScrollRegions())}static getScrollRegions(){return Array.from(this.regions()).map(e=>({top:e.scrollTop,left:e.scrollLeft}))}static regions(){return document.querySelectorAll(`[scroll-region]`)}static scrollToTop(){if(Sm&&getComputedStyle(document.documentElement).scrollBehavior===`smooth`)return ym(()=>window.scrollTo(0,0),2);window.scrollTo(0,0)}static reset(){!xm&&window.location.hash||this.scrollToTop(),this.regions().forEach(e=>{typeof e.scrollTo==`function`?e.scrollTo(0,0):(e.scrollTop=0,e.scrollLeft=0)}),this.save(),this.scrollToAnchor()}static scrollToAnchor(){let e=xm?null:window.location.hash;e&&setTimeout(()=>{let t=document.getElementById(e.slice(1));t?t.scrollIntoView():this.scrollToTop()})}static restore(e){xm||window.requestAnimationFrame(()=>{this.restoreDocument(),this.restoreScrollRegions(e)})}static restoreScrollRegions(e){xm||this.regions().forEach((t,n)=>{let r=e[n];r&&(typeof t.scrollTo==`function`?t.scrollTo(r.left,r.top):(t.scrollTop=r.top,t.scrollLeft=r.left))})}static restoreDocument(){let e=B.getDocumentScrollPosition();window.scrollTo(e.left,e.top)}static onScroll(e){let t=e.target;typeof t.hasAttribute==`function`&&t.hasAttribute(`scroll-region`)&&this.save()}static onWindowScroll(){B.saveDocumentScrollPosition({top:window.scrollY,left:window.scrollX})}},wm=e=>typeof File<`u`&&e instanceof File||e instanceof Blob||typeof FileList<`u`&&e instanceof FileList&&e.length>0;function Tm(e){return wm(e)||e instanceof FormData&&Array.from(e.values()).some(e=>Tm(e))||typeof e==`object`&&!!e&&Object.values(e).some(e=>Tm(e))}var Em=e=>e instanceof FormData;function Dm(e,t=new FormData,n=null,r=`brackets`){e||={};for(let i in e)Object.prototype.hasOwnProperty.call(e,i)&&km(t,Om(n,i,`indices`),e[i],r);return t}function Om(e,t,n){return e?n===`brackets`?`${e}[]`:`${e}[${t}]`:t}function km(e,t,n,r){if(Array.isArray(n))return Array.from(n.keys()).forEach(i=>km(e,Om(t,i.toString(),r),n[i],r));if(n instanceof Date)return e.append(t,n.toISOString());if(n instanceof File)return e.append(t,n,n.name);if(n instanceof Blob)return e.append(t,n);if(typeof n==`boolean`)return e.append(t,n?`1`:`0`);if(typeof n==`string`)return e.append(t,n);if(typeof n==`number`)return e.append(t,`${n}`);if(n==null)return e.append(t,``);Dm(n,e,t,r)}function Am(e){return/\[\d+\]/.test(decodeURIComponent(e.search))}function jm(e){if(!e||e===`?`)return{};let t={};return e.replace(/^\?/,``).split(`&`).filter(Boolean).forEach(e=>{let[n,r]=Nm(e);Fm(t,Pm(n),Pm(r))}),t}function Mm(e,t){let n=[];return Lm(e,``,n,t),n.length?`?`+n.join(`&`):``}function Nm(e){let t=e.indexOf(`=`);return t===-1?[e,``]:[e.substring(0,t),e.substring(t+1)]}function Pm(e){return decodeURIComponent(e.replace(/\+/g,` `))}function Fm(e,t,n){let r=Im(t),i=e;for(;r.length>1;){let e=r.shift(),t=r[0]===``;(typeof i[e]!=`object`||i[e]===null)&&(i[e]=t?[]:{}),i=i[e]}let a=r.shift();a===``&&Array.isArray(i)?i.push(n):i[a]=n}function Im(e){let t=[],n=e.split(`[`)[0];n&&t.push(n);let r,i=/\[([^\]]*)\]/g;for(;(r=i.exec(e))!==null;)t.push(r[1]);return t}function Lm(e,t,n,r){if(e!==void 0){if(e===null){n.push(`${t}=`);return}if(Array.isArray(e)){e.forEach((e,i)=>{Lm(e,r===`indices`?`${t}[${i}]`:`${t}[]`,n,r)});return}if(typeof e==`object`){Object.keys(e).forEach(i=>{Lm(e[i],t?`${t}[${i}]`:i,n,r)});return}n.push(`${t}=${encodeURIComponent(String(e))}`)}}function Rm(e){return new URL(e.toString(),typeof window>`u`?void 0:window.location.toString())}var zm=(e,t,n,r,i)=>{let a=typeof e==`string`?Rm(e):e;if((Tm(t)||r)&&!Em(t)&&(Vp.get(`form.forceIndicesArrayFormatInFormData`)&&(i=`indices`),t=Dm(t,new FormData,null,i)),Em(t))return[a,t];let[o,s]=Bm(n,a,t,i);return[Rm(o),s]};function Bm(e,t,n,r=`brackets`){let i=e===`get`&&!Em(n)&&Object.keys(n).length>0,a=qm(t.toString()),o=a||t.toString().startsWith(`/`)||t.toString()===``,s=!o&&!t.toString().startsWith(`#`)&&!t.toString().startsWith(`?`),c=/^[.]{1,2}([/]|$)/.test(t.toString()),l=t.toString().includes(`?`)||i,u=t.toString().includes(`#`),d=new URL(t.toString(),typeof window>`u`?`http://localhost`:window.location.toString());if(i){let e=Am(d)?`indices`:r;d.search=Mm({...jm(d.search),...n},e)}return[[a?`${d.protocol}//${d.host}`:``,o?d.pathname:``,s?d.pathname.substring(c?0:1):``,l?d.search:``,u?d.hash:``].join(``),i?{}:n]}function Vm(e){return e=new URL(e.href),e.hash=``,e}var Hm=(e,t)=>{e.hash&&!t.hash&&Vm(e).href===t.href&&(t.hash=e.hash)},Um=(e,t)=>Vm(e).href===Vm(t).href,Wm=(e,t)=>e.origin===t.origin&&e.pathname===t.pathname;function Gm(e){return typeof e==`object`&&!!e&&e!==void 0&&`url`in e&&`method`in e}function Km(e){return e.component?typeof e.component==`string`?e.component:(console.error(`The "component" property on the URL method pair received multiple components (${Object.keys(e.component).join(`, `)}), but only a single component string is supported for instant visits. Use the withComponent() method to specify which component to use.`),null):null}function qm(e){return/^([a-z][a-z0-9+.-]*:)?\/\/[^/]/i.test(e)}var z=new class{constructor(){this.componentId={},this.listeners=[],this.isFirstPageLoad=!0,this.cleared=!1,this.pendingDeferredProps=null,this.historyQuotaExceeded=!1,this.optimisticBaseline={},this.pendingOptimistics=[],this.optimisticCounter=0}init({initialPage:e,swapComponent:t,resolveComponent:n,onFlash:r}){return this.page={...e,flash:e.flash??{}},this.swapComponent=t,this.resolveComponent=n,this.onFlashCallback=r,$m.on(`historyQuotaExceeded`,()=>{this.historyQuotaExceeded=!0}),this}set(e,{replace:t=!1,preserveScroll:n=!1,preserveState:r=!1,viewTransition:i=!1}={}){Object.keys(e.deferredProps||{}).length&&(this.pendingDeferredProps={deferredProps:e.deferredProps,component:e.component,url:e.url},e.initialDeferredProps===void 0&&(e.initialDeferredProps=e.deferredProps)),this.componentId={};let a=this.componentId;return e.clearHistory&&B.clear(),this.resolve(e.component,e).then(o=>{if(a!==this.componentId)return;e.rememberedState??={};let s=typeof window>`u`,c=s?new URL(e.url):window.location,l=!s&&n?Cm.getScrollRegions():[];t||=Um(Rm(e.url),c);let u={...e,flash:{}};return new Promise(e=>t?B.replaceState(u,e):B.pushState(u,e)).then(()=>{let a=!this.isTheSame(e);if(!a&&Object.keys(e.props.errors||{}).length>0&&(i=!1),this.page=e,this.cleared=!1,this.hasOnceProps()&&vm.updateCachedOncePropsFromCurrentPage(),a&&this.fireEventsFor(`newComponent`),this.isFirstPageLoad&&this.fireEventsFor(`firstLoad`),this.isFirstPageLoad=!1,this.historyQuotaExceeded){this.historyQuotaExceeded=!1;return}return this.swap({component:o,page:e,preserveState:r,viewTransition:i}).then(()=>{n?window.requestAnimationFrame(()=>Cm.restoreScrollRegions(l)):Cm.reset(),this.pendingDeferredProps&&this.pendingDeferredProps.component===e.component&&this.pendingDeferredProps.url===e.url&&$m.fireInternalEvent(`loadDeferredProps`,this.pendingDeferredProps.deferredProps),this.pendingDeferredProps=null,t||Xp(e)})})})}setQuietly(e,{preserveState:t=!1}={}){return this.resolve(e.component,e).then(n=>(this.page=e,this.cleared=!1,B.setCurrent(e),this.swap({component:n,page:e,preserveState:t,viewTransition:!1})))}clear(){this.cleared=!0}isCleared(){return this.cleared}get(){return this.page}getWithoutFlashData(){return{...this.page,flash:{}}}hasOnceProps(){return Object.keys(this.page.onceProps??{}).length>0}merge(e){this.page={...this.page,...e}}setPropsQuietly(e){return this.page={...this.page,props:e},this.resolve(this.page.component,this.page).then(e=>this.swap({component:e,page:this.page,preserveState:!0,viewTransition:!1}))}setFlash(e){this.page={...this.page,flash:e},this.onFlashCallback?.(e)}setUrlHash(e){this.page.url.includes(e)||(this.page.url+=e)}remember(e){this.page.rememberedState=e}swap({component:e,page:t,preserveState:n,viewTransition:r}){let i=()=>this.swapComponent({component:e,page:t,preserveState:n});if(!r||!document?.startViewTransition||document.visibilityState===`hidden`)return i();let a=typeof r==`boolean`?()=>null:r;return new Promise(e=>{a(document.startViewTransition(()=>i().then(e)))})}resolve(e,t){return Promise.resolve(this.resolveComponent(e,t))}nextOptimisticId(){return++this.optimisticCounter}setBaseline(e,t){e in this.optimisticBaseline||(this.optimisticBaseline[e]=t)}updateBaseline(e,t){e in this.optimisticBaseline&&(this.optimisticBaseline[e]=t)}hasBaseline(e){return e in this.optimisticBaseline}registerOptimistic(e,t){this.pendingOptimistics.push({id:e,callback:t})}unregisterOptimistic(e){this.pendingOptimistics=this.pendingOptimistics.filter(t=>t.id!==e)}replayOptimistics(){let e=Object.keys(this.optimisticBaseline);if(e.length===0)return{};let t=mf(this.page.props);for(let n of e)t[n]=mf(this.optimisticBaseline[n]);for(let{callback:e}of this.pendingOptimistics){let n=e(mf(t));n&&Object.assign(t,n)}let n={};for(let r of e)n[r]=t[r];return n}pendingOptimisticCount(){return this.pendingOptimistics.length}clearOptimisticState(){this.optimisticBaseline={},this.pendingOptimistics=[]}isTheSame(e){return this.page.component===e.component}on(e,t){return this.listeners.push({event:e,callback:t}),()=>{this.listeners=this.listeners.filter(n=>n.event!==e&&n.callback!==t)}}fireEventsFor(e){this.listeners.filter(t=>t.event===e).forEach(e=>e.callback())}mergeOncePropsIntoResponse(e,{force:t=!1}={}){Object.entries(e.onceProps??{}).forEach(([n,r])=>{let i=this.page.onceProps?.[n];i!==void 0&&(t||jf(e.props,r.prop)===void 0)&&(qf(e.props,r.prop,jf(this.page.props,i.prop)),e.onceProps[n].expiresAt=i.expiresAt)})}},Jm=class{constructor(){this.items=[],this.processingPromise=null}add(e){return this.items.push(e),this.process()}process(){return this.processingPromise??=this.processNext().finally(()=>{this.processingPromise=null}),this.processingPromise}processNext(){let e=this.items.shift();return e?Promise.resolve(e()).then(()=>this.processNext()):Promise.resolve()}},Ym=typeof window>`u`,Xm=new Jm,Zm=!Ym&&/CriOS/.test(window.navigator.userAgent),Qm=class{constructor(){this.rememberedState=`rememberedState`,this.scrollRegions=`scrollRegions`,this.preserveUrl=!1,this.current={},this.initialState=null}remember(e,t){this.replaceState({...z.getWithoutFlashData(),rememberedState:{...z.get()?.rememberedState??{},[t]:e}})}restore(e){if(!Ym)return this.current[this.rememberedState]?.[e]===void 0?this.initialState?.[this.rememberedState]?.[e]:this.current[this.rememberedState]?.[e]}pushState(e,t=null){if(!Ym){if(this.preserveUrl){t&&t();return}this.current=e,Xm.add(()=>this.getPageData(e).then(n=>{let r=()=>this.doPushState({page:n},e.url).then(()=>t?.());return Zm?new Promise(e=>{setTimeout(()=>r().then(e))}):r()}))}}clonePageProps(e){try{return structuredClone(e.props),e}catch{return{...e,props:mf(e.props)}}}getPageData(e){let t=this.clonePageProps(e);return new Promise(n=>e.encryptHistory?im(t).then(n):n(t))}processQueue(){return Xm.process()}decrypt(e=null){if(Ym)return Promise.resolve(e??z.get());let t=e??window.history.state?.page;return this.decryptPageData(t).then(e=>{if(!e)throw Error(`Unable to decrypt history`);return this.initialState===null?this.initialState=e??void 0:this.current=e??{},e})}decryptPageData(e){return e instanceof ArrayBuffer?om(e):Promise.resolve(e)}saveScrollPositions(e){Xm.add(()=>Promise.resolve().then(()=>{if(window.history.state?.page&&!Sf(this.getScrollRegions(),e))return this.doReplaceState({page:window.history.state.page,scrollRegions:e})}))}saveDocumentScrollPosition(e){Xm.add(()=>Promise.resolve().then(()=>{if(window.history.state?.page&&!Sf(this.getDocumentScrollPosition(),e))return this.doReplaceState({page:window.history.state.page,documentScrollPosition:e})}))}getScrollRegions(){return window.history.state?.scrollRegions||[]}getDocumentScrollPosition(){return window.history.state?.documentScrollPosition||{top:0,left:0}}replaceState(e,t=null){if(Sf(this.current,e)){t&&t();return}let{flash:n,...r}=e;if(z.merge(r),!Ym){if(this.preserveUrl){t&&t();return}this.current=e,Xm.add(()=>this.getPageData(e).then(n=>{let r=()=>this.doReplaceState({page:n},e.url).then(()=>t?.());return Zm?new Promise(e=>{setTimeout(()=>r().then(e))}):r()}))}}isHistoryThrottleError(e){return e instanceof Error&&e.name===`SecurityError`&&(e.message.includes(`history.pushState`)||e.message.includes(`history.replaceState`))}isQuotaExceededError(e){return e instanceof Error&&e.name===`QuotaExceededError`}withThrottleProtection(e){return Promise.resolve().then(()=>{try{return e()}catch(e){if(!this.isHistoryThrottleError(e))throw e;console.error(e.message)}})}doReplaceState(e,t){return this.withThrottleProtection(()=>{window.history.replaceState({...e,scrollRegions:e.scrollRegions??window.history.state?.scrollRegions,documentScrollPosition:e.documentScrollPosition??window.history.state?.documentScrollPosition},``,t)})}doPushState(e,t){return this.withThrottleProtection(()=>{try{window.history.pushState(e,``,t)}catch(e){if(!this.isQuotaExceededError(e))throw e;$m.fireInternalEvent(`historyQuotaExceeded`,t)}})}getState(e,t){return this.current?.[e]??t}deleteState(e){this.current[e]!==void 0&&(delete this.current[e],this.replaceState(this.current))}clearInitialState(e){this.initialState&&this.initialState[e]!==void 0&&delete this.initialState[e]}browserHasHistoryEntry(){return!Ym&&!!window.history.state?.page}clear(){rm.remove(am.key),rm.remove(am.iv)}setCurrent(e){this.current=e}isValidState(e){return!!e.page}getAllState(){return this.current}};typeof window<`u`&&window.history.scrollRestoration&&(window.history.scrollRestoration=`manual`);var B=new Qm,$m=new class{constructor(){this.internalListeners=[]}init(){typeof window<`u`&&(window.addEventListener(`popstate`,this.handlePopstateEvent.bind(this)),window.addEventListener(`pageshow`,this.handlePageshowEvent.bind(this)),window.addEventListener(`scroll`,Hp(Cm.onWindowScroll.bind(Cm),100),!0)),typeof document<`u`&&document.addEventListener(`scroll`,Hp(Cm.onScroll.bind(Cm),100),!0)}onGlobalEvent(e,t){return this.registerListener(`inertia:${e}`,(e=>{let n=t(e);e.cancelable&&!e.defaultPrevented&&n===!1&&e.preventDefault()}))}on(e,t){return this.internalListeners.push({event:e,listener:t}),()=>{this.internalListeners=this.internalListeners.filter(e=>e.listener!==t)}}onMissingHistoryItem(){z.clear(),this.fireInternalEvent(`missingHistoryItem`)}fireInternalEvent(e,...t){this.internalListeners.filter(t=>t.event===e).forEach(e=>e.listener(...t))}registerListener(e,t){return document.addEventListener(e,t),()=>document.removeEventListener(e,t)}handlePageshowEvent(e){e.persisted&&B.decrypt().catch(()=>this.onMissingHistoryItem())}handlePopstateEvent(e){let t=e.state||null;if(t===null){let e=Rm(z.get().url);e.hash=window.location.hash,B.replaceState({...z.getWithoutFlashData(),url:e.href}),Cm.reset();return}if(!B.isValidState(t))return this.onMissingHistoryItem();B.decrypt(t.page).then(e=>{if(z.get().version!==e.version){this.onMissingHistoryItem();return}Eg.cancelAll({prefetch:!1}),z.setQuietly(e,{preserveState:!1}).then(()=>{Cm.restore(B.getScrollRegions()),Xp(z.get());let t={},n=z.get().props;for(let[r,i]of Object.entries(e.initialDeferredProps??e.deferredProps??{})){let e=i.filter(e=>jf(n,e)===void 0);e.length>0&&(t[r]=e)}Object.keys(t).length>0&&this.fireInternalEvent(`loadDeferredProps`,t)})}).catch(()=>{this.onMissingHistoryItem()})}},eh=new class{constructor(){this.type=this.resolveType()}resolveType(){return typeof window>`u`?`navigate`:window.performance?.getEntriesByType(`navigation`)[0]?.type??`navigate`}get(){return this.type}isBackForward(){return this.type===`back_forward`}isReload(){return this.type===`reload`}},th=class{static handle(){this.clearRememberedStateOnReload(),[this.handleBackForward,this.handleLocation,this.handleDefault].find(e=>e.bind(this)())}static clearRememberedStateOnReload(){eh.isReload()&&(B.deleteState(B.rememberedState),B.clearInitialState(B.rememberedState))}static handleBackForward(){if(!eh.isBackForward()||!B.browserHasHistoryEntry())return!1;let e=B.getScrollRegions();return B.decrypt().then(t=>{z.set(t,{preserveScroll:!0,preserveState:!0}).then(()=>{Cm.restore(e),Xp(z.get())})}).catch(()=>{$m.onMissingHistoryItem()}),!0}static handleLocation(){if(!rm.exists(rm.locationVisitKey))return!1;let e=rm.get(rm.locationVisitKey)||{};return rm.remove(rm.locationVisitKey),typeof window<`u`&&z.setUrlHash(window.location.hash),B.decrypt(z.get()).then(()=>{let t=B.getState(B.rememberedState,{}),n=B.getScrollRegions();z.remember(t),z.set(z.get(),{preserveScroll:e.preserveScroll,preserveState:!0}).then(()=>{e.preserveScroll&&Cm.restore(n),Xp(z.get())})}).catch(()=>{$m.onMissingHistoryItem()}),!0}static handleDefault(){typeof window<`u`&&z.setUrlHash(window.location.hash),z.set(z.get(),{preserveScroll:!0,preserveState:!0}).then(()=>{eh.isReload()?Cm.restore(B.getScrollRegions()):Cm.scrollToAnchor();let e=z.get();Xp(e);let t=e.flash;Object.keys(t).length>0&&queueMicrotask(()=>nm(t))})}},nh=class{constructor(e,t,n){this.id=null,this.throttle=!1,this.keepAlive=!1,this.cbCount=0,this.keepAlive=n.keepAlive??!1,this.cb=t,this.interval=e,(n.autoStart??!0)&&this.start()}stop(){this.id&&clearInterval(this.id)}start(){typeof window>`u`||(this.stop(),this.id=window.setInterval(()=>{(!this.throttle||this.cbCount%10==0)&&this.cb(),this.throttle&&this.cbCount++},this.interval))}isInBackground(e){this.throttle=this.keepAlive?!1:e,this.throttle&&(this.cbCount=0)}},rh=new class{constructor(){this.polls=[],this.setupVisibilityListener()}add(e,t,n){let r=new nh(e,t,n);return this.polls.push(r),{stop:()=>r.stop(),start:()=>r.start()}}clear(){this.polls.forEach(e=>e.stop()),this.polls=[]}setupVisibilityListener(){typeof document>`u`||document.addEventListener(`visibilitychange`,()=>{this.polls.forEach(e=>e.isInBackground(document.hidden))},!1)}},ih=new class{constructor(){this.requestHandlers=[],this.responseHandlers=[],this.errorHandlers=[]}onRequest(e){return this.requestHandlers.push(e),()=>{this.requestHandlers=this.requestHandlers.filter(t=>t!==e)}}onResponse(e){return this.responseHandlers.push(e),()=>{this.responseHandlers=this.responseHandlers.filter(t=>t!==e)}}onError(e){return this.errorHandlers.push(e),()=>{this.errorHandlers=this.errorHandlers.filter(t=>t!==e)}}async processRequest(e){let t=e;for(let e of this.requestHandlers)t=await e(t);return t}async processResponse(e){let t=e;for(let e of this.responseHandlers)t=await e(t);return t}async processError(e){for(let t of this.errorHandlers)await t(e)}},ah=class extends Error{constructor(e,t,n){super(n?`${e} (${n})`:e),this.name=`HttpError`,this.code=t,this.url=n}},oh=class extends ah{constructor(e,t,n){super(e,`ERR_HTTP_RESPONSE`,n),this.name=`HttpResponseError`,this.response=t}},sh=class extends ah{constructor(e=`Request was cancelled`,t){super(e,`ERR_CANCELLED`,t),this.name=`HttpCancelledError`}},ch=class extends ah{constructor(e,t,n){super(e,`ERR_NETWORK`,t),this.name=`HttpNetworkError`,this.cause=n}};function lh(e){let t=document.cookie.match(RegExp(`(^|;\\s*)(`+e+`)=([^;]*)`));return t?decodeURIComponent(t[3]):null}function uh(e){let t={};return e.getAllResponseHeaders().split(`\r
`).forEach(e=>{let n=e.indexOf(`:`);n>0&&(t[e.slice(0,n).toLowerCase().trim()]=e.slice(n+1).trim())}),t}function dh(e,t){if(!t.headers)return;let n=t.data instanceof FormData;Object.entries(t.headers).forEach(([t,r])=>{(t.toLowerCase()!==`content-type`||!n)&&e.setRequestHeader(t,String(r))})}function fh(e,t){if(!t||Object.keys(t).length===0)return e;let[n]=Bm(`get`,e,t);return n}var ph=class{constructor(e={}){this.xsrfCookieName=e.xsrfCookieName??`XSRF-TOKEN`,this.xsrfHeaderName=e.xsrfHeaderName??`X-XSRF-TOKEN`}async request(e){let t=await ih.processRequest(e);try{let e=await this.doRequest(t);return await ih.processResponse(e)}catch(e){throw(e instanceof oh||e instanceof ch||e instanceof sh)&&await ih.processError(e),e}}doRequest(e){return new Promise((t,n)=>{let r=new XMLHttpRequest,i=fh(e.url,e.params);r.open(e.method.toUpperCase(),i,!0);let a=lh(this.xsrfCookieName);a&&r.setRequestHeader(this.xsrfHeaderName,a);let o=null;e.data!==null&&e.data!==void 0&&(e.data instanceof FormData?o=e.data:typeof e.data==`object`?(o=JSON.stringify(e.data),!e.headers?.[`Content-Type`]&&!e.headers?.[`content-type`]&&r.setRequestHeader(`Content-Type`,`application/json`)):o=String(e.data)),dh(r,e),e.onUploadProgress&&(r.upload.onprogress=t=>{e.onUploadProgress({progress:t.lengthComputable?t.loaded/t.total:void 0,loaded:t.loaded,total:t.lengthComputable?t.total:void 0})}),e.signal&&e.signal.addEventListener(`abort`,()=>r.abort()),r.onabort=()=>n(new sh(`Request was cancelled`,e.url)),r.onerror=()=>n(new ch(`Network error`,e.url)),r.onload=()=>{let i={status:r.status,data:r.responseText,headers:uh(r)};r.status>=400?n(new oh(`Request failed with status ${r.status}`,i,e.url)):t(i)},r.send(o)})}},mh=new ph;function hh(e){return!(`request`in e)}var gh={getClient(){return mh},setClient(e){if(!hh(e)){mh=e;return}mh=new ph(e),e.xsrfCookieName&&xp.withXsrfCookieName(e.xsrfCookieName),e.xsrfHeaderName&&xp.withXsrfHeaderName(e.xsrfHeaderName)},onRequest:ih.onRequest.bind(ih),onResponse:ih.onResponse.bind(ih),onError:ih.onError.bind(ih),processRequest:ih.processRequest.bind(ih),processResponse:ih.processResponse.bind(ih),processError:ih.processError.bind(ih)},_h=class e{constructor(e){if(this.callbacks=[],!e.prefetch)this.params=e;else{let t={onBefore:this.wrapCallback(e,`onBefore`),onBeforeUpdate:this.wrapCallback(e,`onBeforeUpdate`),onStart:this.wrapCallback(e,`onStart`),onProgress:this.wrapCallback(e,`onProgress`),onFinish:this.wrapCallback(e,`onFinish`),onCancel:this.wrapCallback(e,`onCancel`),onSuccess:this.wrapCallback(e,`onSuccess`),onError:this.wrapCallback(e,`onError`),onHttpException:this.wrapCallback(e,`onHttpException`),onNetworkError:this.wrapCallback(e,`onNetworkError`),onFlash:this.wrapCallback(e,`onFlash`),onCancelToken:this.wrapCallback(e,`onCancelToken`),onPrefetched:this.wrapCallback(e,`onPrefetched`),onPrefetching:this.wrapCallback(e,`onPrefetching`)};this.params={...e,...t,onPrefetchResponse:e.onPrefetchResponse||(()=>{}),onPrefetchError:e.onPrefetchError||(()=>{})}}}static create(t){return new e(t)}data(){return this.params.method===`get`?null:this.params.data}queryParams(){return this.params.method===`get`?this.params.data:{}}isPartial(){return this.params.only.length>0||this.params.except.length>0||this.params.reset.length>0}isPrefetch(){return this.params.prefetch===!0}isDeferredPropsRequest(){return this.params.deferredProps===!0}onCancelToken(e){this.params.onCancelToken({cancel:e})}markAsFinished(){this.params.completed=!0,this.params.cancelled=!1,this.params.interrupted=!1}markAsCancelled({cancelled:e=!0,interrupted:t=!1}){this.params.onCancel(),this.params.completed=!1,this.params.cancelled=e,this.params.interrupted=t}wasCancelledAtAll(){return this.params.cancelled||this.params.interrupted}onFinish(){this.params.onFinish(this.params)}onStart(){this.params.onStart(this.params)}onPrefetching(){this.params.onPrefetching(this.params)}onPrefetchResponse(e){this.params.onPrefetchResponse&&this.params.onPrefetchResponse(e)}onPrefetchError(e){this.params.onPrefetchError&&this.params.onPrefetchError(e)}all(){return this.params}headers(){let e={...this.params.headers};this.isPartial()&&(e[`X-Inertia-Partial-Component`]=z.get().component);let t=this.params.only.concat(this.params.reset);return t.length>0&&(e[`X-Inertia-Partial-Data`]=t.join(`,`)),this.params.except.length>0&&(e[`X-Inertia-Partial-Except`]=this.params.except.join(`,`)),this.params.reset.length>0&&(e[`X-Inertia-Reset`]=this.params.reset.join(`,`)),this.params.errorBag&&this.params.errorBag.length>0&&(e[`X-Inertia-Error-Bag`]=this.params.errorBag),e}setPreserveOptions(t){this.params.preserveScroll=e.resolvePreserveOption(this.params.preserveScroll,t),this.params.preserveState=e.resolvePreserveOption(this.params.preserveState,t)}runCallbacks(){this.callbacks.forEach(({name:e,args:t})=>{this.params[e](...t)})}merge(e){this.params={...this.params,...e}}wrapCallback(e,t){return(...n)=>{this.recordCallback(t,n),e[t](...n)}}recordCallback(e,t){this.callbacks.push({name:e,args:t})}static resolvePreserveOption(e,t){return typeof e==`function`?e(t):e===`errors`?Object.keys(t.props.errors||{}).length>0:e}},vh={createIframeAndPage(e){typeof e==`object`&&(e=`All Inertia requests must receive a valid Inertia response, however a plain JSON response was received.<hr>${JSON.stringify(e)}`);let t=document.createElement(`html`);t.innerHTML=e,t.querySelectorAll(`a`).forEach(e=>e.setAttribute(`target`,`_top`));let n=document.createElement(`iframe`);return n.style.backgroundColor=`white`,n.style.borderRadius=`5px`,n.style.width=`100%`,n.style.height=`100%`,{iframe:n,page:t}},show(e){let{iframe:t,page:n}=this.createIframeAndPage(e);t.style.boxSizing=`border-box`,t.style.display=`block`;let r=document.createElement(`dialog`);r.id=`inertia-error-dialog`,Object.assign(r.style,{width:`calc(100vw - 100px)`,height:`calc(100vh - 100px)`,padding:`0`,margin:`auto`,border:`none`,backgroundColor:`transparent`});let i=document.createElement(`style`);if(i.textContent=`
      dialog#inertia-error-dialog::backdrop {
        background-color: rgba(0, 0, 0, 0.6);
      }

      dialog#inertia-error-dialog:focus {
        outline: none;
      }
    `,document.head.appendChild(i),r.addEventListener(`click`,e=>{e.target===r&&r.close()}),r.addEventListener(`close`,()=>{i.remove(),r.remove()}),r.appendChild(t),document.body.prepend(r),r.showModal(),r.focus(),!t.contentWindow)throw Error(`iframe not yet ready.`);t.contentWindow.document.open(),t.contentWindow.document.write(n.outerHTML),t.contentWindow.document.close()}},yh=new Jm,bh=class e{constructor(e,t,n){this.requestParams=e,this.response=t,this.originatingPage=n,this.wasPrefetched=!1,this.processed=!1}static create(t,n,r){return new e(t,n,r)}isProcessed(){return this.processed}async handlePrefetch(){Um(this.requestParams.all().url,window.location)&&this.handle()}async handle(){return yh.add(()=>this.process())}async process(){if(this.requestParams.all().prefetch)return this.wasPrefetched=!0,this.requestParams.all().prefetch=!1,this.requestParams.all().onPrefetched(this.response,this.requestParams.all()),em(this.response,this.requestParams.all()),Promise.resolve();if(this.requestParams.runCallbacks(),this.processed=!0,!this.isInertiaResponse())return this.handleNonInertiaResponse();if(this.isHttpException()){let e={...this.response,data:this.getDataFromResponse(this.response.data)};if(this.requestParams.all().onHttpException(e)===!1||!Jp(e))return}await B.processQueue(),B.preserveUrl=this.requestParams.all().preserveUrl,await this.setPage();let e=z.get().props.errors||{};if(Object.keys(e).length>0){let t=this.getScopedErrors(e);return Gp(t),this.requestParams.all().onError(t)}Eg.flushByCacheTags(this.requestParams.all().invalidateCacheTags||[]),this.wasPrefetched||Eg.flush(z.get().url);let{flash:t}=z.get();Object.keys(t).length>0&&!this.requestParams.isDeferredPropsRequest()&&(nm(t),this.requestParams.all().onFlash(t)),$p(z.get()),await this.requestParams.all().onSuccess(z.get()),B.preserveUrl=!1}mergeParams(e){this.requestParams.merge(e)}getPageResponse(){let e=this.getDataFromResponse(this.response.data);return typeof e==`object`?this.response.data={...e,flash:e.flash??{}}:this.response.data=e}async handleNonInertiaResponse(){if(this.isInertiaRedirect()){Eg.visit(this.getHeader(`x-inertia-redirect`),{...this.requestParams.all(),method:`get`,data:{}});return}if(this.isLocationVisit()){let e=Rm(this.getHeader(`x-inertia-location`));return Hm(this.requestParams.all().url,e),this.locationVisit(e)}let e={...this.response,data:this.getDataFromResponse(this.response.data)};if(this.requestParams.all().onHttpException(e)!==!1&&Jp(e))return vh.show(e.data)}isInertiaResponse(){return this.hasHeader(`x-inertia`)}isHttpException(){return this.response.status>=400}hasStatus(e){return this.response.status===e}getHeader(e){return this.response.headers[e]}hasHeader(e){return this.getHeader(e)!==void 0}isInertiaRedirect(){return this.hasStatus(409)&&this.hasHeader(`x-inertia-redirect`)}isLocationVisit(){return this.hasStatus(409)&&this.hasHeader(`x-inertia-location`)}locationVisit(e){try{if(rm.set(rm.locationVisitKey,{preserveScroll:this.requestParams.all().preserveScroll===!0}),typeof window>`u`)return;Um(window.location,e)?window.location.reload():window.location.href=e.href}catch{return!1}}async setPage(){let e=this.getPageResponse();return this.shouldSetPage(e)?(this.mergeProps(e),z.mergeOncePropsIntoResponse(e),this.preserveOptimisticProps(e),this.preserveEqualProps(e),await this.setRememberedState(e),this.requestParams.setPreserveOptions(e),e.url=B.preserveUrl?z.get().url:this.pageUrl(e),this.requestParams.all().onBeforeUpdate(e),Yp(e),z.set(e,{replace:this.requestParams.all().replace,preserveScroll:this.requestParams.all().preserveScroll,preserveState:this.requestParams.all().preserveState,viewTransition:this.requestParams.all().viewTransition})):Promise.resolve()}getDataFromResponse(e){if(typeof e!=`string`)return e;try{return JSON.parse(e)}catch{return e}}shouldSetPage(e){if(!this.requestParams.all().async||this.originatingPage.component!==e.component)return!0;if(this.originatingPage.component!==z.get().component)return!1;let t=Rm(this.originatingPage.url),n=Rm(z.get().url);return t.origin===n.origin&&t.pathname===n.pathname}pageUrl(e){let t=Rm(e.url);return e.preserveFragment?t.hash=this.requestParams.all().url.hash:Hm(this.requestParams.all().url,t),t.pathname+t.search+t.hash}preserveOptimisticProps(e){if(Eg.hasPendingOptimistic())for(let t of Object.keys(e.props))z.hasBaseline(t)&&(z.updateBaseline(t,e.props[t]),e.props[t]=z.get().props[t])}preserveEqualProps(e){if(e.component!==z.get().component)return;let t=z.get().props;Object.entries(e.props).forEach(([n,r])=>{Sf(r,t[n])&&(e.props[n]=t[n])})}mergeProps(e){if(!this.requestParams.isPartial()||e.component!==z.get().component)return;let t=e.mergeProps||[],n=e.prependProps||[],r=e.deepMergeProps||[],i=e.matchPropsOn||[],a=(t,n)=>{let r=jf(z.get().props,t),a=jf(e.props,t);if(Array.isArray(a)){let o=this.mergeOrMatchItems(r||[],a,t,i,n);qf(e.props,t,o)}else if(typeof a==`object`&&a){let n={...r||{},...a};qf(e.props,t,n)}};t.forEach(e=>a(e,!0)),n.forEach(e=>a(e,!1)),r.forEach(t=>{let n=jf(z.get().props,t),r=jf(e.props,t),a=(e,t,n)=>Array.isArray(t)?this.mergeOrMatchItems(e,t,n,i):typeof t==`object`&&t?Object.keys(t).reduce((r,i)=>(r[i]=a(e?e[i]:void 0,t[i],`${n}.${i}`),r),{...e}):t;qf(e.props,t,a(n,r,t))});let o=new Set([...this.requestParams.all().only,...this.requestParams.all().except].filter(e=>e.includes(`.`)).map(e=>e.split(`.`)[0]));for(let t of o){let n=z.get().props[t];this.isObject(n)&&this.isObject(e.props[t])&&(e.props[t]=this.deepMergeObjects(n,e.props[t]))}e.props={...z.get().props,...e.props},this.shouldPreserveErrors(e)&&(e.props.errors=z.get().props.errors),z.get().scrollProps&&(e.scrollProps={...z.get().scrollProps||{},...e.scrollProps||{}}),z.hasOnceProps()&&(e.onceProps={...z.get().onceProps||{},...e.onceProps||{}}),e.flash={...z.get().flash,...this.requestParams.isDeferredPropsRequest()?{}:e.flash};let s=z.get().initialDeferredProps;s&&Object.keys(s).length>0&&(e.initialDeferredProps=s)}shouldPreserveErrors(e){if(!this.requestParams.all().preserveErrors)return!1;let t=z.get().props.errors;if(!t||Object.keys(t).length===0)return!1;let n=e.props.errors;return!(n&&Object.keys(n).length>0)}isObject(e){return e&&typeof e==`object`&&!Array.isArray(e)}deepMergeObjects(e,t){let n={...e};for(let r of Object.keys(t)){let i=e[r],a=t[r];this.isObject(i)&&this.isObject(a)?n[r]=this.deepMergeObjects(i,a):n[r]=a}return n}mergeOrMatchItems(e,t,n,r,i=!0){let a=Array.isArray(e)?e:[],o=r.find(e=>e.split(`.`).slice(0,-1).join(`.`)===n);if(!o)return i?[...a,...t]:[...t,...a];let s=o.split(`.`).pop()||``,c=new Map;return t.forEach(e=>{this.hasUniqueProperty(e,s)&&c.set(e[s],e)}),i?this.appendWithMatching(a,t,c,s):this.prependWithMatching(a,t,c,s)}appendWithMatching(e,t,n,r){let i=e.map(e=>this.hasUniqueProperty(e,r)&&n.has(e[r])?n.get(e[r]):e),a=t.filter(t=>this.hasUniqueProperty(t,r)?!e.some(e=>this.hasUniqueProperty(e,r)&&e[r]===t[r]):!0);return[...i,...a]}prependWithMatching(e,t,n,r){let i=e.filter(e=>this.hasUniqueProperty(e,r)?!n.has(e[r]):!0);return[...t,...i]}hasUniqueProperty(e,t){return e&&typeof e==`object`&&t in e}async setRememberedState(e){let t=await B.getState(B.rememberedState,{});this.requestParams.all().preserveState&&t&&e.component===z.get().component&&(e.rememberedState=t)}getScopedErrors(e){return this.requestParams.all().errorBag?e[this.requestParams.all().errorBag||``]||{}:e}},xh=class e{constructor(e,t,{optimistic:n=!1}={}){this.page=t,this.requestHasFinished=!1,this.requestParams=_h.create(e),this.cancelToken=new AbortController,this.optimistic=n}static create(t,n,r){return new e(t,n,r)}isPrefetch(){return this.requestParams.isPrefetch()}isOptimistic(){return this.optimistic}isPendingOptimistic(){return this.isOptimistic()&&(!this.response||!this.response.isProcessed())}async send(){this.requestParams.onCancelToken(()=>this.cancel({cancelled:!0})),Qp(this.requestParams.all()),this.requestParams.onStart(),this.requestParams.all().prefetch&&(this.requestParams.onPrefetching(),tm(this.requestParams.all()));let e=this.requestParams.all().prefetch;return gh.getClient().request({method:this.requestParams.all().method,url:Vm(this.requestParams.all().url).href,data:this.requestParams.data(),signal:this.cancelToken.signal,headers:this.getHeaders(),onUploadProgress:this.onProgress.bind(this)}).then(e=>(this.response=bh.create(this.requestParams,e,this.page),this.response.handle())).catch(e=>e instanceof oh?(this.response=bh.create(this.requestParams,e.response,this.page),this.response.handle()):Promise.reject(e)).catch(t=>{if(!(t instanceof sh)&&this.requestParams.all().onNetworkError(t)!==!1&&Kp(t))return e&&this.requestParams.onPrefetchError(t),Promise.reject(t)}).finally(()=>{this.finish(),e&&this.response&&this.requestParams.onPrefetchResponse(this.response)})}finish(){this.requestParams.wasCancelledAtAll()||(this.requestParams.markAsFinished(),this.fireFinishEvents())}fireFinishEvents(){this.requestHasFinished||(this.requestHasFinished=!0,qp(this.requestParams.all()),this.requestParams.onFinish())}cancel({cancelled:e=!1,interrupted:t=!1}){this.requestHasFinished||(this.cancelToken.abort(),this.requestParams.markAsCancelled({cancelled:e,interrupted:t}),this.fireFinishEvents())}onProgress(e){this.requestParams.data()instanceof FormData&&(e.percentage=e.progress?Math.round(e.progress*100):0,Zp(e),this.requestParams.all().onProgress(e))}getHeaders(){let e={...this.requestParams.headers(),Accept:`text/html, application/xhtml+xml`,"X-Requested-With":`XMLHttpRequest`,"X-Inertia":!0},t=z.get();t.version&&(e[`X-Inertia-Version`]=t.version);let n=Object.entries(t.onceProps||{}).filter(([,e])=>jf(t.props,e.prop)===void 0?!1:!e.expiresAt||e.expiresAt>Date.now()).map(([e])=>e);return n.length>0&&(e[`X-Inertia-Except-Once-Props`]=n.join(`,`)),e}},Sh=class{constructor({maxConcurrent:e,interruptible:t}){this.requests=[],this.maxConcurrent=e,this.interruptible=t}send(e){this.requests.push(e),e.send().finally(()=>{this.requests=this.requests.filter(t=>t!==e)})}interruptInFlight(){this.cancel({interrupted:!0},!1)}cancelInFlight({prefetch:e=!0,optimistic:t=!0}={}){this.requests.filter(t=>e||!t.isPrefetch()).filter(e=>t||!e.isOptimistic()).forEach(e=>e.cancel({cancelled:!0}))}cancel({cancelled:e=!1,interrupted:t=!1}={},n=!1){!n&&!this.shouldCancel()||this.requests.shift()?.cancel({cancelled:e,interrupted:t})}shouldCancel(){return this.interruptible&&this.requests.length>=this.maxConcurrent}hasPendingOptimistic(){return this.requests.some(e=>e.isPendingOptimistic())}},Ch=()=>{},wh=class{constructor(){this.syncRequestStream=new Sh({maxConcurrent:1,interruptible:!0}),this.asyncRequestStream=new Sh({maxConcurrent:1/0,interruptible:!1}),this.clientVisitQueue=new Jm,this.pendingOptimisticCallback=void 0}init({initialPage:e,resolveComponent:t,swapComponent:n,onFlash:r}){z.init({initialPage:e,resolveComponent:t,swapComponent:n,onFlash:r}),th.handle(),$m.init(),$m.on(`missingHistoryItem`,()=>{typeof window<`u`&&this.visit(window.location.href,{preserveState:!0,preserveScroll:!0,replace:!0})}),$m.on(`loadDeferredProps`,e=>{this.loadDeferredProps(e)}),$m.on(`historyQuotaExceeded`,e=>{window.location.href=e})}optimistic(e){return this.pendingOptimisticCallback=e,this}get(e,t={},n={}){return this.visit(e,{...n,method:`get`,data:t})}post(e,t={},n={}){return this.visit(e,{preserveState:!0,...n,method:`post`,data:t})}put(e,t={},n={}){return this.visit(e,{preserveState:!0,...n,method:`put`,data:t})}patch(e,t={},n={}){return this.visit(e,{preserveState:!0,...n,method:`patch`,data:t})}delete(e,t={}){return this.visit(e,{preserveState:!0,...t,method:`delete`})}reload(e={}){return this.doReload(e)}doReload(e={}){if(!(typeof window>`u`))return this.visit(window.location.href,{...e,preserveScroll:!0,preserveState:!0,async:!0,headers:{...e.headers||{},"Cache-Control":`no-cache`}})}remember(e,t=`default`){B.remember(e,t)}restore(e=`default`){return B.restore(e)}on(e,t){return typeof window>`u`?()=>{}:$m.onGlobalEvent(e,t)}hasPendingOptimistic(){return this.asyncRequestStream.hasPendingOptimistic()}cancelAll({async:e=!0,prefetch:t=!0,sync:n=!0}={}){e&&this.asyncRequestStream.cancelInFlight({prefetch:t}),n&&this.syncRequestStream.cancelInFlight()}poll(e,t={},n={}){return rh.add(e,()=>this.reload({preserveErrors:!0,...t}),{autoStart:n.autoStart??!0,keepAlive:n.keepAlive??!1})}visit(e,t={}){t.optimistic=t.optimistic??this.pendingOptimisticCallback,this.pendingOptimisticCallback=void 0,t.optimistic&&(t.async=t.async??!0);let n=this.getPendingVisit(e,{...t,showProgress:t.showProgress??(!t.async||!!t.optimistic)}),r=this.getVisitEvents(t);if(r.onBefore(n)===!1||!Wp(n))return;let i=Rm(z.get().url);(n.only.length>0||n.except.length>0||n.reset.length>0?Wm(n.url,i):Um(n.url,i))||this.asyncRequestStream.cancelInFlight({prefetch:!1,optimistic:!1}),n.async||this.syncRequestStream.interruptInFlight(),t.optimistic&&this.applyOptimisticUpdate(t.optimistic,r),!z.isCleared()&&!n.preserveUrl&&Cm.save();let a={...n,...r},o=()=>{let e=vm.get(a);e?(fg.reveal(e.inFlight),vm.use(e,a)):(fg.reveal(!0),(n.async?this.asyncRequestStream:this.syncRequestStream).send(xh.create(a,z.get(),{optimistic:!!t.optimistic})))};Array.isArray(n.component)&&(console.error(`The "component" prop received an array of components (${n.component.join(`, `)}), but only a single component string is supported for instant visits. Pass an explicit component name instead.`),n.component=null),n.component?B.processQueue().then(()=>{this.performInstantSwap(n).then(()=>{a.preserveState=!0,a.replace=!0,a.viewTransition=!1,o()})}):o()}getCached(e,t={}){return vm.findCached(this.getPrefetchParams(e,t))}flush(e,t={}){vm.remove(this.getPrefetchParams(e,t))}flushAll(){vm.removeAll()}flushByCacheTags(e){vm.removeByTags(Array.isArray(e)?e:[e])}getPrefetching(e,t={}){return vm.findInFlight(this.getPrefetchParams(e,t))}prefetch(e,t={},n={}){if((t.method??(Gm(e)?e.method:`get`))!==`get`)throw Error(`Prefetch requests must use the GET method`);let r=this.getPendingVisit(e,{...t,async:!0,showProgress:!1,prefetch:!0,viewTransition:!1});if(r.url.origin+r.url.pathname+r.url.search===window.location.origin+window.location.pathname+window.location.search)return;let i=this.getVisitEvents(t);if(i.onBefore(r)===!1||!Wp(r))return;fg.hide(),this.asyncRequestStream.interruptInFlight();let a={...r,...i};new Promise(e=>{let t=()=>{z.get()?e():setTimeout(t,50)};t()}).then(()=>{vm.add(a,e=>{this.asyncRequestStream.send(xh.create(e,z.get()))},{cacheFor:Vp.get(`prefetch.cacheFor`),cacheTags:[],...n})})}clearHistory(){B.clear()}decryptHistory(){return B.decrypt()}resolveComponent(e,t){return z.resolve(e,t)}replace(e){this.clientVisit(e,{replace:!0})}replaceProp(e,t,n){this.replace({preserveScroll:!0,preserveState:!0,props(n){let r=typeof t==`function`?t(jf(n,e),n):t;return qf(mf(n),e,r)},...n||{}})}appendToProp(e,t,n){this.replaceProp(e,(e,n)=>{let r=typeof t==`function`?t(e,n):t;return Array.isArray(e)||(e=e===void 0?[]:[e]),[...e,r]},n)}prependToProp(e,t,n){this.replaceProp(e,(e,n)=>{let r=typeof t==`function`?t(e,n):t;return Array.isArray(e)||(e=e===void 0?[]:[e]),[r,...e]},n)}push(e){this.clientVisit(e)}flash(e,t){let n=z.get().flash,r;if(typeof e==`function`)r=e(n);else if(typeof e==`string`)r={...n,[e]:t};else if(e&&Object.keys(e).length)r={...n,...e};else return;z.setFlash(r),Object.keys(r).length&&nm(r)}clientVisit(e,{replace:t=!1}={}){this.clientVisitQueue.add(()=>this.performClientVisit(e,{replace:t}))}performClientVisit(e,{replace:t=!1}={}){let n=z.get(),r=typeof e.props==`function`?Object.fromEntries(Object.values(n.onceProps??{}).map(e=>[e.prop,jf(n.props,e.prop)])):{},i=typeof e.props==`function`?e.props(n.props,r):e.props??n.props,a=typeof e.flash==`function`?e.flash(n.flash):e.flash,{viewTransition:o,onError:s,onFinish:c,onFlash:l,onSuccess:u,...d}=e,f={...n,...d,flash:a??{},props:i},p=_h.resolvePreserveOption(e.preserveScroll??!1,f),m=_h.resolvePreserveOption(e.preserveState??!1,f);return z.set(f,{replace:t,preserveScroll:p,preserveState:m,viewTransition:o}).then(()=>{let t=z.get().flash;Object.keys(t).length>0&&(nm(t),l?.(t));let n=z.get().props.errors||{};if(Object.keys(n).length===0){u?.(z.get());return}let r=e.errorBag?n[e.errorBag||``]||{}:n;s?.(r)}).finally(()=>c?.(e))}performInstantSwap(e){let t=z.get(),n=Object.fromEntries((t.sharedProps??[]).filter(e=>e in t.props).map(e=>[e,t.props[e]])),r=typeof e.pageProps==`function`?e.pageProps(mf(t.props),mf(n)):e.pageProps,i=r===null?{...n}:{...r},a={component:e.component,url:e.url.pathname+e.url.search+e.url.hash,version:t.version,props:{...i,errors:{}},flash:{},clearHistory:!1,encryptHistory:t.encryptHistory,sharedProps:t.sharedProps,rememberedState:{}};return z.set(a,{replace:e.replace,preserveScroll:_h.resolvePreserveOption(e.preserveScroll,a),preserveState:!1,viewTransition:e.viewTransition})}getPrefetchParams(e,t){return{...this.getPendingVisit(e,{...t,async:!0,showProgress:!1,prefetch:!0,viewTransition:!1}),...this.getVisitEvents(t)}}getPendingVisit(e,t){if(Gm(e)){let n=e;e=n.url,t.method=t.method??n.method}let n=Vp.get(`visitOptions`),r=n&&n(e.toString(),mf(t))||{},i={method:`get`,data:{},replace:!1,preserveScroll:!1,preserveState:!1,only:[],except:[],headers:{},errorBag:``,forceFormData:!1,queryStringArrayFormat:`brackets`,async:!1,showProgress:!0,fresh:!1,reset:[],preserveUrl:!1,preserveErrors:!1,prefetch:!1,invalidateCacheTags:[],viewTransition:!1,component:null,pageProps:null,...t,...r},[a,o]=zm(e,i.data,i.method,i.forceFormData,i.queryStringArrayFormat),s={cancelled:!1,completed:!1,interrupted:!1,...i,url:a,data:o};return s.prefetch&&(s.headers.Purpose=`prefetch`),s}getVisitEvents(e){return{onCancelToken:e.onCancelToken||Ch,onBefore:e.onBefore||Ch,onBeforeUpdate:e.onBeforeUpdate||Ch,onStart:e.onStart||Ch,onProgress:e.onProgress||Ch,onFinish:e.onFinish||Ch,onCancel:e.onCancel||Ch,onSuccess:e.onSuccess||Ch,onError:e.onError||Ch,onHttpException:e.onHttpException||Ch,onNetworkError:e.onNetworkError||Ch,onFlash:e.onFlash||Ch,onPrefetched:e.onPrefetched||Ch,onPrefetching:e.onPrefetching||Ch}}applyOptimisticUpdate(e,t){let n=z.get().props,r=e(mf(n));if(!r)return;let i=[];for(let e of Object.keys(r))Sf(n[e],r[e])||i.push(e);if(i.length===0)return;let a=z.nextOptimisticId(),o=z.get().component;for(let e of i)z.setBaseline(e,mf(n[e]));z.registerOptimistic(a,e),z.setPropsQuietly({...n,...r});let s=!0,c=t.onSuccess;t.onSuccess=e=>(s=!1,c(e));let l=t.onFinish;t.onFinish=e=>{if(z.unregisterOptimistic(a),s&&z.get().component===o){let e=z.replayOptimistics();Object.keys(e).length>0&&z.setPropsQuietly({...z.get().props,...e})}return z.pendingOptimisticCount()===0&&z.clearOptimisticState(),l(e)}}loadDeferredProps(e){e&&Object.values(e).forEach(e=>{this.doReload({only:e,deferredProps:!0,preserveErrors:!0})})}},Th=class{static createWayfinderCallback(...e){return()=>e.length===1?Gm(e[0])?e[0]:e[0]():{method:typeof e[0]==`function`?e[0]():e[0],url:typeof e[1]==`function`?e[1]():e[1]}}static parseUseFormArguments(...e){return e.length===0?{rememberKey:null,data:{},precognitionEndpoint:null}:e.length===1?{rememberKey:null,data:e[0],precognitionEndpoint:null}:e.length===2?typeof e[0]==`string`?{rememberKey:e[0],data:e[1],precognitionEndpoint:null}:{rememberKey:null,data:e[1],precognitionEndpoint:this.createWayfinderCallback(e[0])}:{rememberKey:null,data:e[2],precognitionEndpoint:this.createWayfinderCallback(e[0],e[1])}}static parseSubmitArguments(e,t){return e.length===3||e.length===2&&typeof e[0]==`string`?{method:e[0],url:e[1],options:e[2]??{}}:Gm(e[0])?{...e[0],options:e[1]??{}}:{...t(),options:e[0]??{}}}static mergeHeadersForValidation(e,t,n){let r=e=>(e.headers={...n??{},...e.headers??{}},e);return e&&typeof e==`object`&&!(`target`in e)?e=r(e):t&&typeof t==`object`?t=r(t):typeof e==`string`?t=r(t??{}):e=r(e??{}),[e,t]}};function Eh(e){return e.includes(`.`)?e.replace(/\\\./g,`__ESCAPED_DOT__`).split(/(\[[^\]]*\])/).filter(Boolean).map(e=>e.startsWith(`[`)&&e.endsWith(`]`)?e:e.split(`.`).reduce((e,t,n)=>n===0?t:`${e}[${t}]`)).join(``).replace(/__ESCAPED_DOT__/g,`.`):e}function Dh(e){let t=[],n=/([^\[\]]+)|\[(\d*)\]/g,r;for(;(r=n.exec(e))!==null;)r[1]===void 0?r[2]!==void 0&&t.push(r[2]===``?``:Number(r[2])):t.push(r[1]);return t}function Oh(e,t,n){let r=e;for(let e=0;e<t.length-1;e++)t[e]in r||(r[t[e]]={}),r=r[t[e]];r[t[t.length-1]]=n}function kh(e){let t=Object.keys(e),n=t.filter(e=>/^\d+$/.test(e)).map(Number).sort((e,t)=>e-t);return t.length===n.length&&n.length>0&&n[0]===0&&n.every((e,t)=>e===t)}function Ah(e){if(Array.isArray(e))return e.map(Ah);if(typeof e!=`object`||!e||wm(e))return e;if(kh(e)){let t=[];for(let n=0;n<Object.keys(e).length;n++)t[n]=Ah(e[n]);return t}let t={};for(let n in e)t[n]=Ah(e[n]);return t}function jh(e){let t={};for(let[n,r]of e.entries()){if(r instanceof File&&r.size===0&&r.name===``)continue;let e=Dh(Eh(n));if(e[e.length-1]===``){let n=e.slice(0,-1),i=jf(t,n);if(Array.isArray(i))i.push(r);else if(i&&typeof i==`object`&&!wm(i)){let e=Object.keys(i).filter(e=>/^\d+$/.test(e)).map(Number).sort((e,t)=>e-t);qf(t,n,e.length>0?[...e.map(e=>i[e]),r]:[r])}else qf(t,n,[r]);continue}Oh(t,e.map(String),r)}return Ah(t)}var Mh={buildDOMElement(e){let t=document.createElement(`template`);t.innerHTML=e;let n=t.content.firstChild;if(!e.startsWith(`<script `))return n;let r=document.createElement(`script`);return r.innerHTML=n.innerHTML,n.getAttributeNames().forEach(e=>{r.setAttribute(e,n.getAttribute(e)||``)}),r},isInertiaManagedElement(e){return e.nodeType===Node.ELEMENT_NODE&&e.getAttribute(`data-inertia`)!==null},findMatchingElementIndex(e,t){let n=e.getAttribute(`data-inertia`);return n===null?-1:t.findIndex(e=>e.getAttribute(`data-inertia`)===n)},update:Hp(function(e){let t=e.map(e=>this.buildDOMElement(e));Array.from(document.head.childNodes).filter(e=>this.isInertiaManagedElement(e)).forEach(e=>{let n=this.findMatchingElementIndex(e,t);if(n===-1){e.remove();return}let r=t.splice(n,1)[0];r&&!e.isEqualNode(r)&&e.replaceWith(r)}),t.forEach(e=>document.head.appendChild(e))},1)};function Nh(e,t,n){let r={},i=0;function a(){let e=i+=1;return r[e]=[],e.toString()}function o(e){e===null||Object.keys(r).indexOf(e)===-1||(delete r[e],u())}function s(e){Object.keys(r).indexOf(e)===-1&&(r[e]=[])}function c(e,t=[]){e!==null&&Object.keys(r).indexOf(e)>-1&&(r[e]=t),u()}function l(){let e=t(``),n={...e?{title:`<title data-inertia="">${e}</title>`}:{}},i=Object.values(r).reduce((e,t)=>e.concat(t),[]).reduce((e,n)=>{if(n.indexOf(`<`)===-1)return e;if(n.indexOf(`<title `)===0){let r=n.match(/(<title [^>]+>)(.*?)(<\/title>)/);return e.title=r?`${r[1]}${t(r[2])}${r[3]}`:n,e}let r=n.match(/ data-inertia="[^"]+"/);return r?e[r[0]]=n:e[Object.keys(e).length]=n,e},n);return Object.values(i)}function u(){e?n(l()):Mh.update(l())}return u(),{forceUpdate:u,createProvider:function(){let e=a();return{reconnect:()=>s(e),update:t=>c(e,t),disconnect:()=>o(e)}}}}new Jm;function Ph(){let e={},t={},n={shared:e,named:t},r=new Set,i=!1,a=()=>{n={shared:e,named:t}},o=()=>{i||(i=!0,queueMicrotask(()=>{i=!1,r.forEach(e=>e())}))};return{set(t){let n={...e,...t};Sf(e,n)||(e=n,a(),o())},setFor(e,n){let r=t[e]||{},i={...r,...n};Sf(r,i)||(t={...t,[e]:i},a(),o())},reset(){e={},t={},a(),o()},subscribe(e){return r.add(e),()=>r.delete(e)},get:()=>n}}function Fh(e){return typeof e==`object`&&!!e&&!Array.isArray(e)}function Ih(e){return Fh(e)&&`component`in e}function Lh(e,t){return!Fh(e)||t(e)||`component`in e?!1:Object.values(e).some(e=>t(e)||Array.isArray(e)&&t(e[0])||Ih(e)&&t(e.component))}function Rh(e,t){return Fh(e)&&!t(e)&&!(`component`in e)&&!Lh(e,t)}function zh(e,t){return Array.isArray(e)&&e.length===2&&t(e[0])&&Fh(e[1])&&!t(e[1])}function Bh(e,t){if(Array.isArray(e)&&t(e[0]))return{component:e[0],props:e[1]??{}};if(Ih(e)&&t(e.component))return{component:e.component,props:e.props??{}};if(t(e))return{component:e,props:{}};throw Error(`Invalid layout definition: received ${typeof e}`)}function Vh(e,t,n){return!e||n&&n(e)?[]:Lh(e,t)?Object.entries(e).map(([e,n])=>({...Bh(n,t),name:e})):zh(e,t)?[{component:e[0],props:e[1]??{}}]:Array.isArray(e)?e.map(e=>Bh(e,t)):Ih(e)&&t(e.component)?[{component:e.component,props:e.props??{}}]:t(e)?[{component:e,props:{}}]:[]}function Hh(e){return e.target instanceof HTMLElement&&e.target.isContentEditable||e.defaultPrevented}function Uh(e){let t=e.currentTarget.tagName.toLowerCase()===`a`;return!(Hh(e)||t&&e.altKey||t&&e.ctrlKey||t&&e.metaKey||t&&e.shiftKey||t&&`button`in e&&e.button!==0)}function Wh(e){let t=e.currentTarget.tagName.toLowerCase()===`button`;return!Hh(e)&&(e.key===`Enter`||t&&e.key===` `)}var Gh=`nprogress`,Kh,qh,Jh={minimum:.08,easing:`linear`,speed:200,trickle:!0,trickleSpeed:200,showSpinner:!0,barSelector:`[role="bar"]`,spinnerSelector:`[role="spinner"]`,parent:`body`,color:`#29d`,includeCSS:!0,popover:null,template:[`<div class="bar" role="bar">`,`<div class="peg"></div>`,`</div>`,`<div class="spinner" role="spinner">`,`<div class="spinner-icon"></div>`,`</div>`].join(``)},Yh=null,Xh=!1,Zh=e=>{Object.assign(Jh,e),Kh=Jh.popover??`popover`in HTMLElement.prototype,Jh.includeCSS&&ug(Jh.color),qh=document.createElement(`div`),qh.id=Gh,qh.innerHTML=Jh.template,Kh&&(qh.popover=`manual`)},Qh=e=>{let t=$h();e=sg(e,Jh.minimum,1),Yh=e===1?null:e;let n=rg(!t),r=n.querySelector(Jh.barSelector),i=Jh.speed,a=Jh.easing;n.offsetWidth,lg(t=>{let o={transition:`all ${i}ms ${a}`,transform:`translate3d(${cg(e)}%,0,0)`};for(let e in o)r.style[e]=o[e];if(e!==1)return setTimeout(t,i);n.style.transition=`none`,n.style.opacity=`1`,n.offsetWidth,setTimeout(()=>{n.style.transition=`all ${i}ms linear`,n.style.opacity=`0`,setTimeout(()=>{ag(),n.style.transition=``,n.style.opacity=``,t()},i)},i)})},$h=()=>typeof Yh==`number`,eg=()=>{Yh||Qh(0);let e=function(){setTimeout(function(){Yh&&(ng(),e())},Jh.trickleSpeed)};Jh.trickle&&e()},tg=e=>{!e&&!Yh||(ng(.3+.5*Math.random()),Qh(1))},ng=e=>{let t=Yh;if(t===null)return eg();if(!(t>1))return e=typeof e==`number`?e:(()=>{let e={.1:[0,.2],.04:[.2,.5],.02:[.5,.8],.005:[.8,.99]};for(let n in e)if(t>=e[n][0]&&t<e[n][1])return parseFloat(n);return 0})(),Qh(sg(t+e,0,.994))},rg=e=>{if(og())return document.getElementById(Gh);document.documentElement.classList.add(`${Gh}-busy`);let t=qh.querySelector(Jh.barSelector),n=e?`-100`:cg(Yh||0);if(t.style.transition=`all 0 linear`,t.style.transform=`translate3d(${n}%,0,0)`,Jh.showSpinner||qh.querySelector(Jh.spinnerSelector)?.remove(),Kh)document.body.appendChild(qh),Xh||qh.showPopover();else{let e=ig();e!==document.body&&e.classList.add(`${Gh}-custom-parent`),e.appendChild(qh),Xh&&(qh.style.display=`none`)}return qh},ig=()=>document.querySelector(Jh.parent),ag=()=>{if(document.documentElement.classList.remove(`${Gh}-busy`),Kh&&qh?.isConnected)try{qh.hidePopover()}catch{}Kh||ig().classList.remove(`${Gh}-custom-parent`),qh?.remove()},og=()=>document.getElementById(Gh)!==null;function sg(e,t,n){return e<t?t:e>n?n:e}var cg=e=>(-1+e)*100,lg=(()=>{let e=[],t=()=>{let n=e.shift();n&&n(t)};return n=>{e.push(n),e.length===1&&t()}})(),ug=e=>{let t=document.createElement(`style`);t.textContent=`
    #${Gh} {
      pointer-events: none;
      background: none;
      border: none;
      margin: 0;
      padding: 0;
      overflow: visible;
      inset: unset;
      width: 100%;
      height: 0;
      position: fixed;
      top: 0;
      left: 0;
    }

    #${Gh}::backdrop {
      display: none;
    }

    #${Gh} .bar {
      background: ${e};

      position: fixed;
      z-index: 1031;
      top: 0;
      left: 0;

      width: 100%;
      height: 2px;
    }

    #${Gh} .peg {
      display: block;
      position: absolute;
      right: 0px;
      width: 100px;
      height: 100%;
      box-shadow: 0 0 10px ${e}, 0 0 5px ${e};
      opacity: 1.0;

      transform: rotate(3deg) translate(0px, -4px);
    }

    #${Gh} .spinner {
      display: block;
      position: fixed;
      z-index: 1031;
      top: 15px;
      right: 15px;
    }

    #${Gh} .spinner-icon {
      width: 18px;
      height: 18px;
      box-sizing: border-box;

      border: solid 2px transparent;
      border-top-color: ${e};
      border-left-color: ${e};
      border-radius: 50%;

      animation: ${Gh}-spinner 400ms linear infinite;
    }

    .${Gh}-custom-parent {
      overflow: hidden;
      position: relative;
    }

    .${Gh}-custom-parent #${Gh} .spinner,
    .${Gh}-custom-parent #${Gh} .bar {
      position: absolute;
    }

    @keyframes ${Gh}-spinner {
      0%   { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  `,document.head.appendChild(t)},dg={configure:Zh,isStarted:$h,done:tg,set:Qh,remove:ag,start:eg,status:Yh,show:()=>{if(Xh=!1,qh?.isConnected)if(Kh)try{qh.showPopover()}catch{}else qh.style.display=``},hide:()=>{if(Xh=!0,qh?.isConnected)if(Kh)try{qh.hidePopover()}catch{}else qh.style.display=`none`}},fg=new class{constructor(){this.hideCount=0}start(){dg.start()}reveal(e=!1){this.hideCount=Math.max(0,this.hideCount-1),(e||this.hideCount===0)&&dg.show()}hide(){this.hideCount++,dg.hide()}set(e){dg.set(Math.max(0,Math.min(1,e)))}finish(){dg.done()}reset(){dg.set(0)}remove(){dg.done(),dg.remove()}isStarted(){return dg.isStarted()}getStatus(){return dg.status}};function pg(e){document.addEventListener(`inertia:start`,t=>mg(t,e)),document.addEventListener(`inertia:progress`,hg)}function mg(e,t){e.detail.visit.showProgress||fg.hide();let n=setTimeout(()=>fg.start(),t);document.addEventListener(`inertia:finish`,e=>gg(e,n),{once:!0})}function hg(e){fg.isStarted()&&e.detail.progress?.percentage&&fg.set(Math.max(fg.getStatus(),e.detail.progress.percentage/100*.9))}function gg(e,t){clearTimeout(t),fg.isStarted()&&(e.detail.visit.completed?fg.finish():e.detail.visit.interrupted?fg.reset():e.detail.visit.cancelled&&fg.remove())}function _g({delay:e=250,color:t=`#29d`,includeCSS:n=!0,showSpinner:r=!1,popover:i=null}={}){pg(e),dg.configure({showSpinner:r,includeCSS:n,color:t,popover:i})}var vg=Symbol(`FormComponentReset`);function yg(e){return e instanceof HTMLInputElement||e instanceof HTMLSelectElement||e instanceof HTMLTextAreaElement}function bg(e,t){let n=e.value,r=e.checked;switch(e.type.toLowerCase()){case`checkbox`:e.checked=t.includes(e.value);break;case`radio`:e.checked=t[0]===e.value;break;case`file`:e.value=``;break;case`button`:case`submit`:case`reset`:case`image`:break;default:e.value=t[0]!==null&&t[0]!==void 0?String(t[0]):``}return e.value!==n||e.checked!==r}function xg(e,t){let n=e.value,r=Array.from(e.selectedOptions).map(e=>e.value);if(e.multiple){let n=t.map(e=>String(e));Array.from(e.options).forEach(e=>{e.selected=n.includes(e.value)})}else e.value=t[0]===void 0?``:String(t[0]);let i=Array.from(e.selectedOptions).map(e=>e.value);return e.multiple?JSON.stringify(r.sort())!==JSON.stringify(i.sort()):e.value!==n}function Sg(e,t){if(e.disabled){if(e instanceof HTMLInputElement){let t=e.value,n=e.checked;switch(e.type.toLowerCase()){case`checkbox`:case`radio`:return e.checked=e.defaultChecked,e.checked!==n;case`file`:return e.value=``,t!==``;case`button`:case`submit`:case`reset`:case`image`:return!1;default:return e.value=e.defaultValue,e.value!==t}}else if(e instanceof HTMLSelectElement){let t=Array.from(e.selectedOptions).map(e=>e.value);Array.from(e.options).forEach(e=>{e.selected=e.defaultSelected});let n=Array.from(e.selectedOptions).map(e=>e.value);return JSON.stringify(t.sort())!==JSON.stringify(n.sort())}else if(e instanceof HTMLTextAreaElement){let t=e.value;return e.value=e.defaultValue,e.value!==t}return!1}if(e instanceof HTMLInputElement)return bg(e,t);if(e instanceof HTMLSelectElement)return xg(e,t);if(e instanceof HTMLTextAreaElement){let n=e.value;return e.value=t[0]===void 0?``:String(t[0]),e.value!==n}return!1}function Cg(e,t){let n=!1;return e instanceof RadioNodeList||e instanceof HTMLCollection?Array.from(e).forEach((e,r)=>{e instanceof Element&&yg(e)&&(e instanceof HTMLInputElement&&[`checkbox`,`radio`].includes(e.type.toLowerCase())?Sg(e,t)&&(n=!0):Sg(e,t[r]===void 0?[t[0]??null].filter(Boolean):[t[r]])&&(n=!0))}):yg(e)&&(n=Sg(e,t)),n}function wg(e,t,n){if(!e)return;let r=!n||n.length===0;if(r){let r=new FormData(e),i=Array.from(e.elements).map(e=>yg(e)?e.name:``).filter(Boolean);n=[...new Set([...t.keys(),...r.keys(),...i])]}let i=!1;n.forEach(n=>{let r=e.elements.namedItem(n);r&&Cg(r,t.getAll(n))&&(i=!0)}),i&&r&&e.dispatchEvent(new CustomEvent(`reset`,{bubbles:!0,cancelable:!0,detail:{[vg]:!0}}))}function Tg(e,t,n){return`<script data-page="${e}" type="application/json">${JSON.stringify(t).replace(/\//g,`\\/`)}<\/script><div data-server-rendered="true" id="${e}">${n}</div>`}var Eg=new wh;function Dg(e){let t=Object.create(null);for(let n of e.split(`,`))t[n]=1;return e=>e in t}var Og={},kg=()=>{},Ag=Object.assign,jg=(e,t)=>{let n=e.indexOf(t);n>-1&&e.splice(n,1)},Mg=Object.prototype.hasOwnProperty,Ng=(e,t)=>Mg.call(e,t),Pg=Array.isArray,Fg=e=>Hg(e)===`[object Map]`,Ig=e=>Hg(e)===`[object Set]`,Lg=e=>typeof e==`function`,Rg=e=>typeof e==`string`,zg=e=>typeof e==`symbol`,Bg=e=>typeof e==`object`&&!!e,Vg=Object.prototype.toString,Hg=e=>Vg.call(e),Ug=e=>Hg(e).slice(8,-1),Wg=e=>Hg(e)===`[object Object]`,Gg=e=>Rg(e)&&e!==`NaN`&&e[0]!==`-`&&``+parseInt(e,10)===e,Kg=(e,t)=>!Object.is(e,t),qg=(e,t,n,r=!1)=>{Object.defineProperty(e,t,{configurable:!0,enumerable:!1,writable:r,value:n})},Jg,Yg=class{constructor(e=!1){this.detached=e,this._active=!0,this._on=0,this.effects=[],this.cleanups=[],this._isPaused=!1,this.__v_skip=!0,this.parent=Jg,!e&&Jg&&(this.index=(Jg.scopes||=[]).push(this)-1)}get active(){return this._active}pause(){if(this._active){this._isPaused=!0;let e,t;if(this.scopes)for(e=0,t=this.scopes.length;e<t;e++)this.scopes[e].pause();for(e=0,t=this.effects.length;e<t;e++)this.effects[e].pause()}}resume(){if(this._active&&this._isPaused){this._isPaused=!1;let e,t;if(this.scopes)for(e=0,t=this.scopes.length;e<t;e++)this.scopes[e].resume();for(e=0,t=this.effects.length;e<t;e++)this.effects[e].resume()}}run(e){if(this._active){let t=Jg;try{return Jg=this,e()}finally{Jg=t}}}on(){++this._on===1&&(this.prevScope=Jg,Jg=this)}off(){this._on>0&&--this._on===0&&(Jg=this.prevScope,this.prevScope=void 0)}stop(e){if(this._active){this._active=!1;let t,n;for(t=0,n=this.effects.length;t<n;t++)this.effects[t].stop();for(this.effects.length=0,t=0,n=this.cleanups.length;t<n;t++)this.cleanups[t]();if(this.cleanups.length=0,this.scopes){for(t=0,n=this.scopes.length;t<n;t++)this.scopes[t].stop(!0);this.scopes.length=0}if(!this.detached&&this.parent&&!e){let e=this.parent.scopes.pop();e&&e!==this&&(this.parent.scopes[this.index]=e,e.index=this.index)}this.parent=void 0}}};function Xg(e){return new Yg(e)}function Zg(){return Jg}function Qg(e,t=!1){Jg&&Jg.cleanups.push(e)}var V,$g=new WeakSet,e_=class{constructor(e){this.fn=e,this.deps=void 0,this.depsTail=void 0,this.flags=5,this.next=void 0,this.cleanup=void 0,this.scheduler=void 0,Jg&&Jg.active&&Jg.effects.push(this)}pause(){this.flags|=64}resume(){this.flags&64&&(this.flags&=-65,$g.has(this)&&($g.delete(this),this.trigger()))}notify(){this.flags&2&&!(this.flags&32)||this.flags&8||i_(this)}run(){if(!(this.flags&1))return this.fn();this.flags|=2,y_(this),s_(this);let e=V,t=h_;V=this,h_=!0;try{return this.fn()}finally{c_(this),V=e,h_=t,this.flags&=-3}}stop(){if(this.flags&1){for(let e=this.deps;e;e=e.nextDep)d_(e);this.deps=this.depsTail=void 0,y_(this),this.onStop&&this.onStop(),this.flags&=-2}}trigger(){this.flags&64?$g.add(this):this.scheduler?this.scheduler():this.runIfDirty()}runIfDirty(){l_(this)&&this.run()}get dirty(){return l_(this)}},t_=0,n_,r_;function i_(e,t=!1){if(e.flags|=8,t){e.next=r_,r_=e;return}e.next=n_,n_=e}function a_(){t_++}function o_(){if(--t_>0)return;if(r_){let e=r_;for(r_=void 0;e;){let t=e.next;e.next=void 0,e.flags&=-9,e=t}}let e;for(;n_;){let t=n_;for(n_=void 0;t;){let n=t.next;if(t.next=void 0,t.flags&=-9,t.flags&1)try{t.trigger()}catch(t){e||=t}t=n}}if(e)throw e}function s_(e){for(let t=e.deps;t;t=t.nextDep)t.version=-1,t.prevActiveLink=t.dep.activeLink,t.dep.activeLink=t}function c_(e){let t,n=e.depsTail,r=n;for(;r;){let e=r.prevDep;r.version===-1?(r===n&&(n=e),d_(r),f_(r)):t=r,r.dep.activeLink=r.prevActiveLink,r.prevActiveLink=void 0,r=e}e.deps=t,e.depsTail=n}function l_(e){for(let t=e.deps;t;t=t.nextDep)if(t.dep.version!==t.version||t.dep.computed&&(u_(t.dep.computed)||t.dep.version!==t.version))return!0;return!!e._dirty}function u_(e){if(e.flags&4&&!(e.flags&16)||(e.flags&=-17,e.globalVersion===b_)||(e.globalVersion=b_,!e.isSSR&&e.flags&128&&(!e.deps&&!e._dirty||!l_(e))))return;e.flags|=2;let t=e.dep,n=V,r=h_;V=e,h_=!0;try{s_(e);let n=e.fn(e._value);(t.version===0||Kg(n,e._value))&&(e.flags|=128,e._value=n,t.version++)}catch(e){throw t.version++,e}finally{V=n,h_=r,c_(e),e.flags&=-3}}function d_(e,t=!1){let{dep:n,prevSub:r,nextSub:i}=e;if(r&&(r.nextSub=i,e.prevSub=void 0),i&&(i.prevSub=r,e.nextSub=void 0),n.subs===e&&(n.subs=r,!r&&n.computed)){n.computed.flags&=-5;for(let e=n.computed.deps;e;e=e.nextDep)d_(e,!0)}!t&&!--n.sc&&n.map&&n.map.delete(n.key)}function f_(e){let{prevDep:t,nextDep:n}=e;t&&(t.nextDep=n,e.prevDep=void 0),n&&(n.prevDep=t,e.nextDep=void 0)}function p_(e,t){e.effect instanceof e_&&(e=e.effect.fn);let n=new e_(e);t&&Ag(n,t);try{n.run()}catch(e){throw n.stop(),e}let r=n.run.bind(n);return r.effect=n,r}function m_(e){e.effect.stop()}var h_=!0,g_=[];function __(){g_.push(h_),h_=!1}function v_(){let e=g_.pop();h_=e===void 0?!0:e}function y_(e){let{cleanup:t}=e;if(e.cleanup=void 0,t){let e=V;V=void 0;try{t()}finally{V=e}}}var b_=0,x_=class{constructor(e,t){this.sub=e,this.dep=t,this.version=t.version,this.nextDep=this.prevDep=this.nextSub=this.prevSub=this.prevActiveLink=void 0}},S_=class{constructor(e){this.computed=e,this.version=0,this.activeLink=void 0,this.subs=void 0,this.map=void 0,this.key=void 0,this.sc=0,this.__v_skip=!0}track(e){if(!V||!h_||V===this.computed)return;let t=this.activeLink;if(t===void 0||t.sub!==V)t=this.activeLink=new x_(V,this),V.deps?(t.prevDep=V.depsTail,V.depsTail.nextDep=t,V.depsTail=t):V.deps=V.depsTail=t,C_(t);else if(t.version===-1&&(t.version=this.version,t.nextDep)){let e=t.nextDep;e.prevDep=t.prevDep,t.prevDep&&(t.prevDep.nextDep=e),t.prevDep=V.depsTail,t.nextDep=void 0,V.depsTail.nextDep=t,V.depsTail=t,V.deps===t&&(V.deps=e)}return t}trigger(e){this.version++,b_++,this.notify(e)}notify(e){a_();try{for(let e=this.subs;e;e=e.prevSub)e.sub.notify()&&e.sub.dep.notify()}finally{o_()}}};function C_(e){if(e.dep.sc++,e.sub.flags&4){let t=e.dep.computed;if(t&&!e.dep.subs){t.flags|=20;for(let e=t.deps;e;e=e.nextDep)C_(e)}let n=e.dep.subs;n!==e&&(e.prevSub=n,n&&(n.nextSub=e)),e.dep.subs=e}}var w_=new WeakMap,T_=Symbol(``),E_=Symbol(``),D_=Symbol(``);function O_(e,t,n){if(h_&&V){let t=w_.get(e);t||w_.set(e,t=new Map);let r=t.get(n);r||(t.set(n,r=new S_),r.map=t,r.key=n),r.track()}}function k_(e,t,n,r,i,a){let o=w_.get(e);if(!o){b_++;return}let s=e=>{e&&e.trigger()};if(a_(),t===`clear`)o.forEach(s);else{let i=Pg(e),a=i&&Gg(n);if(i&&n===`length`){let e=Number(r);o.forEach((t,n)=>{(n===`length`||n===D_||!zg(n)&&n>=e)&&s(t)})}else switch((n!==void 0||o.has(void 0))&&s(o.get(n)),a&&s(o.get(D_)),t){case`add`:i?a&&s(o.get(`length`)):(s(o.get(T_)),Fg(e)&&s(o.get(E_)));break;case`delete`:i||(s(o.get(T_)),Fg(e)&&s(o.get(E_)));break;case`set`:Fg(e)&&s(o.get(T_));break}}o_()}function A_(e,t){let n=w_.get(e);return n&&n.get(t)}function j_(e){let t=H(e);return t===e?t:(O_(t,`iterate`,D_),bv(e)?t:t.map(Cv))}function M_(e){return O_(e=H(e),`iterate`,D_),e}function N_(e,t){return yv(e)?wv(vv(e)?Cv(t):t):Cv(t)}var P_={__proto__:null,[Symbol.iterator](){return F_(this,Symbol.iterator,e=>N_(this,e))},concat(...e){return j_(this).concat(...e.map(e=>Pg(e)?j_(e):e))},entries(){return F_(this,`entries`,e=>(e[1]=N_(this,e[1]),e))},every(e,t){return L_(this,`every`,e,t,void 0,arguments)},filter(e,t){return L_(this,`filter`,e,t,e=>e.map(e=>N_(this,e)),arguments)},find(e,t){return L_(this,`find`,e,t,e=>N_(this,e),arguments)},findIndex(e,t){return L_(this,`findIndex`,e,t,void 0,arguments)},findLast(e,t){return L_(this,`findLast`,e,t,e=>N_(this,e),arguments)},findLastIndex(e,t){return L_(this,`findLastIndex`,e,t,void 0,arguments)},forEach(e,t){return L_(this,`forEach`,e,t,void 0,arguments)},includes(...e){return z_(this,`includes`,e)},indexOf(...e){return z_(this,`indexOf`,e)},join(e){return j_(this).join(e)},lastIndexOf(...e){return z_(this,`lastIndexOf`,e)},map(e,t){return L_(this,`map`,e,t,void 0,arguments)},pop(){return B_(this,`pop`)},push(...e){return B_(this,`push`,e)},reduce(e,...t){return R_(this,`reduce`,e,t)},reduceRight(e,...t){return R_(this,`reduceRight`,e,t)},shift(){return B_(this,`shift`)},some(e,t){return L_(this,`some`,e,t,void 0,arguments)},splice(...e){return B_(this,`splice`,e)},toReversed(){return j_(this).toReversed()},toSorted(e){return j_(this).toSorted(e)},toSpliced(...e){return j_(this).toSpliced(...e)},unshift(...e){return B_(this,`unshift`,e)},values(){return F_(this,`values`,e=>N_(this,e))}};function F_(e,t,n){let r=M_(e),i=r[t]();return r!==e&&!bv(e)&&(i._next=i.next,i.next=()=>{let e=i._next();return e.done||(e.value=n(e.value)),e}),i}var I_=Array.prototype;function L_(e,t,n,r,i,a){let o=M_(e),s=o!==e&&!bv(e),c=o[t];if(c!==I_[t]){let t=c.apply(e,a);return s?Cv(t):t}let l=n;o!==e&&(s?l=function(t,r){return n.call(this,N_(e,t),r,e)}:n.length>2&&(l=function(t,r){return n.call(this,t,r,e)}));let u=c.call(o,l,r);return s&&i?i(u):u}function R_(e,t,n,r){let i=M_(e),a=i!==e&&!bv(e),o=n,s=!1;i!==e&&(a?(s=r.length===0,o=function(t,r,i){return s&&(s=!1,t=N_(e,t)),n.call(this,t,N_(e,r),i,e)}):n.length>3&&(o=function(t,r,i){return n.call(this,t,r,i,e)}));let c=i[t](o,...r);return s?N_(e,c):c}function z_(e,t,n){let r=H(e);O_(r,`iterate`,D_);let i=r[t](...n);return(i===-1||i===!1)&&xv(n[0])?(n[0]=H(n[0]),r[t](...n)):i}function B_(e,t,n=[]){__(),a_();let r=H(e)[t].apply(e,n);return o_(),v_(),r}var V_=Dg(`__proto__,__v_isRef,__isVue`),H_=new Set(Object.getOwnPropertyNames(Symbol).filter(e=>e!==`arguments`&&e!==`caller`).map(e=>Symbol[e]).filter(zg));function U_(e){zg(e)||(e=String(e));let t=H(this);return O_(t,`has`,e),t.hasOwnProperty(e)}var W_=class{constructor(e=!1,t=!1){this._isReadonly=e,this._isShallow=t}get(e,t,n){if(t===`__v_skip`)return e.__v_skip;let r=this._isReadonly,i=this._isShallow;if(t===`__v_isReactive`)return!r;if(t===`__v_isReadonly`)return r;if(t===`__v_isShallow`)return i;if(t===`__v_raw`)return n===(r?i?uv:lv:i?cv:sv).get(e)||Object.getPrototypeOf(e)===Object.getPrototypeOf(n)?e:void 0;let a=Pg(e);if(!r){let e;if(a&&(e=P_[t]))return e;if(t===`hasOwnProperty`)return U_}let o=Reflect.get(e,t,Tv(e)?e:n);if((zg(t)?H_.has(t):V_(t))||(r||O_(e,`get`,t),i))return o;if(Tv(o)){let e=a&&Gg(t)?o:o.value;return r&&Bg(e)?hv(e):e}return Bg(o)?r?hv(o):pv(o):o}},G_=class extends W_{constructor(e=!1){super(!1,e)}set(e,t,n,r){let i=e[t],a=Pg(e)&&Gg(t);if(!this._isShallow){let e=yv(i);if(!bv(n)&&!yv(n)&&(i=H(i),n=H(n)),!a&&Tv(i)&&!Tv(n))return e||(i.value=n),!0}let o=a?Number(t)<e.length:Ng(e,t),s=Reflect.set(e,t,n,Tv(e)?e:r);return e===H(r)&&(o?Kg(n,i)&&k_(e,`set`,t,n,i):k_(e,`add`,t,n)),s}deleteProperty(e,t){let n=Ng(e,t),r=e[t],i=Reflect.deleteProperty(e,t);return i&&n&&k_(e,`delete`,t,void 0,r),i}has(e,t){let n=Reflect.has(e,t);return(!zg(t)||!H_.has(t))&&O_(e,`has`,t),n}ownKeys(e){return O_(e,`iterate`,Pg(e)?`length`:T_),Reflect.ownKeys(e)}},K_=class extends W_{constructor(e=!1){super(!0,e)}set(e,t){return!0}deleteProperty(e,t){return!0}},q_=new G_,J_=new K_,Y_=new G_(!0),X_=new K_(!0),Z_=e=>e,Q_=e=>Reflect.getPrototypeOf(e);function $_(e,t,n){return function(...r){let i=this.__v_raw,a=H(i),o=Fg(a),s=e===`entries`||e===Symbol.iterator&&o,c=e===`keys`&&o,l=i[e](...r),u=n?Z_:t?wv:Cv;return!t&&O_(a,`iterate`,c?E_:T_),Ag(Object.create(l),{next(){let{value:e,done:t}=l.next();return t?{value:e,done:t}:{value:s?[u(e[0]),u(e[1])]:u(e),done:t}}})}}function ev(e){return function(...t){return e===`delete`?!1:e===`clear`?void 0:this}}function tv(e,t){let n={get(n){let r=this.__v_raw,i=H(r),a=H(n);e||(Kg(n,a)&&O_(i,`get`,n),O_(i,`get`,a));let{has:o}=Q_(i),s=t?Z_:e?wv:Cv;if(o.call(i,n))return s(r.get(n));if(o.call(i,a))return s(r.get(a));r!==i&&r.get(n)},get size(){let t=this.__v_raw;return!e&&O_(H(t),`iterate`,T_),t.size},has(t){let n=this.__v_raw,r=H(n),i=H(t);return e||(Kg(t,i)&&O_(r,`has`,t),O_(r,`has`,i)),t===i?n.has(t):n.has(t)||n.has(i)},forEach(n,r){let i=this,a=i.__v_raw,o=H(a),s=t?Z_:e?wv:Cv;return!e&&O_(o,`iterate`,T_),a.forEach((e,t)=>n.call(r,s(e),s(t),i))}};return Ag(n,e?{add:ev(`add`),set:ev(`set`),delete:ev(`delete`),clear:ev(`clear`)}:{add(e){let n=H(this),r=Q_(n),i=H(e),a=!t&&!bv(e)&&!yv(e)?i:e;return r.has.call(n,a)||Kg(e,a)&&r.has.call(n,e)||Kg(i,a)&&r.has.call(n,i)||(n.add(a),k_(n,`add`,a,a)),this},set(e,n){!t&&!bv(n)&&!yv(n)&&(n=H(n));let r=H(this),{has:i,get:a}=Q_(r),o=i.call(r,e);o||=(e=H(e),i.call(r,e));let s=a.call(r,e);return r.set(e,n),o?Kg(n,s)&&k_(r,`set`,e,n,s):k_(r,`add`,e,n),this},delete(e){let t=H(this),{has:n,get:r}=Q_(t),i=n.call(t,e);i||=(e=H(e),n.call(t,e));let a=r?r.call(t,e):void 0,o=t.delete(e);return i&&k_(t,`delete`,e,void 0,a),o},clear(){let e=H(this),t=e.size!==0,n=e.clear();return t&&k_(e,`clear`,void 0,void 0,void 0),n}}),[`keys`,`values`,`entries`,Symbol.iterator].forEach(r=>{n[r]=$_(r,e,t)}),n}function nv(e,t){let n=tv(e,t);return(t,r,i)=>r===`__v_isReactive`?!e:r===`__v_isReadonly`?e:r===`__v_raw`?t:Reflect.get(Ng(n,r)&&r in t?n:t,r,i)}var rv={get:nv(!1,!1)},iv={get:nv(!1,!0)},av={get:nv(!0,!1)},ov={get:nv(!0,!0)},sv=new WeakMap,cv=new WeakMap,lv=new WeakMap,uv=new WeakMap;function dv(e){switch(e){case`Object`:case`Array`:return 1;case`Map`:case`Set`:case`WeakMap`:case`WeakSet`:return 2;default:return 0}}function fv(e){return e.__v_skip||!Object.isExtensible(e)?0:dv(Ug(e))}function pv(e){return yv(e)?e:_v(e,!1,q_,rv,sv)}function mv(e){return _v(e,!1,Y_,iv,cv)}function hv(e){return _v(e,!0,J_,av,lv)}function gv(e){return _v(e,!0,X_,ov,uv)}function _v(e,t,n,r,i){if(!Bg(e)||e.__v_raw&&!(t&&e.__v_isReactive))return e;let a=fv(e);if(a===0)return e;let o=i.get(e);if(o)return o;let s=new Proxy(e,a===2?r:n);return i.set(e,s),s}function vv(e){return yv(e)?vv(e.__v_raw):!!(e&&e.__v_isReactive)}function yv(e){return!!(e&&e.__v_isReadonly)}function bv(e){return!!(e&&e.__v_isShallow)}function xv(e){return e?!!e.__v_raw:!1}function H(e){let t=e&&e.__v_raw;return t?H(t):e}function Sv(e){return!Ng(e,`__v_skip`)&&Object.isExtensible(e)&&qg(e,`__v_skip`,!0),e}var Cv=e=>Bg(e)?pv(e):e,wv=e=>Bg(e)?hv(e):e;function Tv(e){return e?e.__v_isRef===!0:!1}function Ev(e){return Ov(e,!1)}function Dv(e){return Ov(e,!0)}function Ov(e,t){return Tv(e)?e:new kv(e,t)}var kv=class{constructor(e,t){this.dep=new S_,this.__v_isRef=!0,this.__v_isShallow=!1,this._rawValue=t?e:H(e),this._value=t?e:Cv(e),this.__v_isShallow=t}get value(){return this.dep.track(),this._value}set value(e){let t=this._rawValue,n=this.__v_isShallow||bv(e)||yv(e);e=n?e:H(e),Kg(e,t)&&(this._rawValue=e,this._value=n?e:Cv(e),this.dep.trigger())}};function Av(e){e.dep&&e.dep.trigger()}function jv(e){return Tv(e)?e.value:e}function Mv(e){return Lg(e)?e():jv(e)}var Nv={get:(e,t,n)=>t===`__v_raw`?e:jv(Reflect.get(e,t,n)),set:(e,t,n,r)=>{let i=e[t];return Tv(i)&&!Tv(n)?(i.value=n,!0):Reflect.set(e,t,n,r)}};function Pv(e){return vv(e)?e:new Proxy(e,Nv)}var Fv=class{constructor(e){this.__v_isRef=!0,this._value=void 0;let t=this.dep=new S_,{get:n,set:r}=e(t.track.bind(t),t.trigger.bind(t));this._get=n,this._set=r}get value(){return this._value=this._get()}set value(e){this._set(e)}};function Iv(e){return new Fv(e)}function Lv(e){let t=Pg(e)?Array(e.length):{};for(let n in e)t[n]=Vv(e,n);return t}var Rv=class{constructor(e,t,n){this._object=e,this._defaultValue=n,this.__v_isRef=!0,this._value=void 0,this._key=zg(t)?t:String(t),this._raw=H(e);let r=!0,i=e;if(!Pg(e)||zg(this._key)||!Gg(this._key))do r=!xv(i)||bv(i);while(r&&(i=i.__v_raw));this._shallow=r}get value(){let e=this._object[this._key];return this._shallow&&(e=jv(e)),this._value=e===void 0?this._defaultValue:e}set value(e){if(this._shallow&&Tv(this._raw[this._key])){let t=this._object[this._key];if(Tv(t)){t.value=e;return}}this._object[this._key]=e}get dep(){return A_(this._raw,this._key)}},zv=class{constructor(e){this._getter=e,this.__v_isRef=!0,this.__v_isReadonly=!0,this._value=void 0}get value(){return this._value=this._getter()}};function Bv(e,t,n){return Tv(e)?e:Lg(e)?new zv(e):Bg(e)&&arguments.length>1?Vv(e,t,n):Ev(e)}function Vv(e,t,n){return new Rv(e,t,n)}var Hv=class{constructor(e,t,n){this.fn=e,this.setter=t,this._value=void 0,this.dep=new S_(this),this.__v_isRef=!0,this.deps=void 0,this.depsTail=void 0,this.flags=16,this.globalVersion=b_-1,this.next=void 0,this.effect=this,this.__v_isReadonly=!t,this.isSSR=n}notify(){if(this.flags|=16,!(this.flags&8)&&V!==this)return i_(this,!0),!0}get value(){let e=this.dep.track();return u_(this),e&&(e.version=this.dep.version),this._value}set value(e){this.setter&&this.setter(e)}};function Uv(e,t,n=!1){let r,i;return Lg(e)?r=e:(r=e.get,i=e.set),new Hv(r,i,n)}var Wv={GET:`get`,HAS:`has`,ITERATE:`iterate`},Gv={SET:`set`,ADD:`add`,DELETE:`delete`,CLEAR:`clear`},Kv={},qv=new WeakMap,Jv=void 0;function Yv(){return Jv}function Xv(e,t=!1,n=Jv){if(n){let t=qv.get(n);t||qv.set(n,t=[]),t.push(e)}}function Zv(e,t,n=Og){let{immediate:r,deep:i,once:a,scheduler:o,augmentJob:s,call:c}=n,l=e=>i?e:bv(e)||i===!1||i===0?Qv(e,1):Qv(e),u,d,f,p,m=!1,h=!1;if(Tv(e)?(d=()=>e.value,m=bv(e)):vv(e)?(d=()=>l(e),m=!0):Pg(e)?(h=!0,m=e.some(e=>vv(e)||bv(e)),d=()=>e.map(e=>{if(Tv(e))return e.value;if(vv(e))return l(e);if(Lg(e))return c?c(e,2):e()})):d=Lg(e)?t?c?()=>c(e,2):e:()=>{if(f){__();try{f()}finally{v_()}}let t=Jv;Jv=u;try{return c?c(e,3,[p]):e(p)}finally{Jv=t}}:kg,t&&i){let e=d,t=i===!0?1/0:i;d=()=>Qv(e(),t)}let g=Zg(),_=()=>{u.stop(),g&&g.active&&jg(g.effects,u)};if(a&&t){let e=t;t=(...t)=>{e(...t),_()}}let v=h?Array(e.length).fill(Kv):Kv,y=e=>{if(!(!(u.flags&1)||!u.dirty&&!e))if(t){let e=u.run();if(i||m||(h?e.some((e,t)=>Kg(e,v[t])):Kg(e,v))){f&&f();let n=Jv;Jv=u;try{let n=[e,v===Kv?void 0:h&&v[0]===Kv?[]:v,p];v=e,c?c(t,3,n):t(...n)}finally{Jv=n}}}else u.run()};return s&&s(y),u=new e_(d),u.scheduler=o?()=>o(y,!1):y,p=e=>Xv(e,!1,u),f=u.onStop=()=>{let e=qv.get(u);if(e){if(c)c(e,4);else for(let t of e)t();qv.delete(u)}},t?r?y(!0):v=u.run():o?o(y.bind(null,!0),!0):u.run(),_.pause=u.pause.bind(u),_.resume=u.resume.bind(u),_.stop=_,_}function Qv(e,t=1/0,n){if(t<=0||!Bg(e)||e.__v_skip||(n||=new Map,(n.get(e)||0)>=t))return e;if(n.set(e,t),t--,Tv(e))Qv(e.value,t,n);else if(Pg(e))for(let r=0;r<e.length;r++)Qv(e[r],t,n);else if(Ig(e)||Fg(e))e.forEach(e=>{Qv(e,t,n)});else if(Wg(e)){for(let r in e)Qv(e[r],t,n);for(let r of Object.getOwnPropertySymbols(e))Object.prototype.propertyIsEnumerable.call(e,r)&&Qv(e[r],t,n)}return e}function $v(e){let t=Object.create(null);for(let n of e.split(`,`))t[n]=1;return e=>e in t}var U={},ey=[],ty=()=>{},ny=()=>!1,ry=e=>e.charCodeAt(0)===111&&e.charCodeAt(1)===110&&(e.charCodeAt(2)>122||e.charCodeAt(2)<97),iy=e=>e.startsWith(`onUpdate:`),ay=Object.assign,oy=(e,t)=>{let n=e.indexOf(t);n>-1&&e.splice(n,1)},sy=Object.prototype.hasOwnProperty,W=(e,t)=>sy.call(e,t),G=Array.isArray,cy=e=>_y(e)===`[object Map]`,ly=e=>_y(e)===`[object Set]`,uy=e=>_y(e)===`[object Date]`,dy=e=>_y(e)===`[object RegExp]`,K=e=>typeof e==`function`,fy=e=>typeof e==`string`,py=e=>typeof e==`symbol`,my=e=>typeof e==`object`&&!!e,hy=e=>(my(e)||K(e))&&K(e.then)&&K(e.catch),gy=Object.prototype.toString,_y=e=>gy.call(e),vy=e=>_y(e)===`[object Object]`,yy=$v(`,key,ref,ref_for,ref_key,onVnodeBeforeMount,onVnodeMounted,onVnodeBeforeUpdate,onVnodeUpdated,onVnodeBeforeUnmount,onVnodeUnmounted`),by=e=>{let t=Object.create(null);return(n=>t[n]||(t[n]=e(n)))},xy=/-\w/g,Sy=by(e=>e.replace(xy,e=>e.slice(1).toUpperCase())),Cy=/\B([A-Z])/g,wy=by(e=>e.replace(Cy,`-$1`).toLowerCase()),Ty=by(e=>e.charAt(0).toUpperCase()+e.slice(1)),Ey=by(e=>e?`on${Ty(e)}`:``),Dy=(e,t)=>!Object.is(e,t),Oy=(e,...t)=>{for(let n=0;n<e.length;n++)e[n](...t)},ky=(e,t,n,r=!1)=>{Object.defineProperty(e,t,{configurable:!0,enumerable:!1,writable:r,value:n})},Ay=e=>{let t=parseFloat(e);return isNaN(t)?e:t},jy=e=>{let t=fy(e)?Number(e):NaN;return isNaN(t)?e:t},My,Ny=()=>My||=typeof globalThis<`u`?globalThis:typeof self<`u`?self:typeof window<`u`?window:typeof global<`u`?global:{},Py=$v(`Infinity,undefined,NaN,isFinite,isNaN,parseFloat,parseInt,decodeURI,decodeURIComponent,encodeURI,encodeURIComponent,Math,Number,Date,Array,Object,Boolean,String,RegExp,Map,Set,JSON,Intl,BigInt,console,Error,Symbol`);function Fy(e){if(G(e)){let t={};for(let n=0;n<e.length;n++){let r=e[n],i=fy(r)?zy(r):Fy(r);if(i)for(let e in i)t[e]=i[e]}return t}else if(fy(e)||my(e))return e}var Iy=/;(?![^(]*\))/g,Ly=/:([^]+)/,Ry=/\/\*[^]*?\*\//g;function zy(e){let t={};return e.replace(Ry,``).split(Iy).forEach(e=>{if(e){let n=e.split(Ly);n.length>1&&(t[n[0].trim()]=n[1].trim())}}),t}function By(e){let t=``;if(fy(e))t=e;else if(G(e))for(let n=0;n<e.length;n++){let r=By(e[n]);r&&(t+=r+` `)}else if(my(e))for(let n in e)e[n]&&(t+=n+` `);return t.trim()}function Vy(e){if(!e)return null;let{class:t,style:n}=e;return t&&!fy(t)&&(e.class=By(t)),n&&(e.style=Fy(n)),e}function Hy(e,t){if(e.length!==t.length)return!1;let n=!0;for(let r=0;n&&r<e.length;r++)n=Uy(e[r],t[r]);return n}function Uy(e,t){if(e===t)return!0;let n=uy(e),r=uy(t);if(n||r)return n&&r?e.getTime()===t.getTime():!1;if(n=py(e),r=py(t),n||r)return e===t;if(n=G(e),r=G(t),n||r)return n&&r?Hy(e,t):!1;if(n=my(e),r=my(t),n||r){if(!n||!r||Object.keys(e).length!==Object.keys(t).length)return!1;for(let n in e){let r=e.hasOwnProperty(n),i=t.hasOwnProperty(n);if(r&&!i||!r&&i||!Uy(e[n],t[n]))return!1}}return String(e)===String(t)}var Wy=e=>!!(e&&e.__v_isRef===!0),Gy=e=>fy(e)?e:e==null?``:G(e)||my(e)&&(e.toString===gy||!K(e.toString))?Wy(e)?Gy(e.value):JSON.stringify(e,Ky,2):String(e),Ky=(e,t)=>Wy(t)?Ky(e,t.value):cy(t)?{[`Map(${t.size})`]:[...t.entries()].reduce((e,[t,n],r)=>(e[qy(t,r)+` =>`]=n,e),{})}:ly(t)?{[`Set(${t.size})`]:[...t.values()].map(e=>qy(e))}:py(t)?qy(t):my(t)&&!G(t)&&!vy(t)?String(t):t,qy=(e,t=``)=>py(e)?`Symbol(${e.description??t})`:e,Jy=[];function Yy(e){Jy.push(e)}function Xy(){Jy.pop()}function Zy(e,t){}var Qy={SETUP_FUNCTION:0,0:`SETUP_FUNCTION`,RENDER_FUNCTION:1,1:`RENDER_FUNCTION`,NATIVE_EVENT_HANDLER:5,5:`NATIVE_EVENT_HANDLER`,COMPONENT_EVENT_HANDLER:6,6:`COMPONENT_EVENT_HANDLER`,VNODE_HOOK:7,7:`VNODE_HOOK`,DIRECTIVE_HOOK:8,8:`DIRECTIVE_HOOK`,TRANSITION_HOOK:9,9:`TRANSITION_HOOK`,APP_ERROR_HANDLER:10,10:`APP_ERROR_HANDLER`,APP_WARN_HANDLER:11,11:`APP_WARN_HANDLER`,FUNCTION_REF:12,12:`FUNCTION_REF`,ASYNC_COMPONENT_LOADER:13,13:`ASYNC_COMPONENT_LOADER`,SCHEDULER:14,14:`SCHEDULER`,COMPONENT_UPDATE:15,15:`COMPONENT_UPDATE`,APP_UNMOUNT_CLEANUP:16,16:`APP_UNMOUNT_CLEANUP`},$y={sp:`serverPrefetch hook`,bc:`beforeCreate hook`,c:`created hook`,bm:`beforeMount hook`,m:`mounted hook`,bu:`beforeUpdate hook`,u:`updated`,bum:`beforeUnmount hook`,um:`unmounted hook`,a:`activated hook`,da:`deactivated hook`,ec:`errorCaptured hook`,rtc:`renderTracked hook`,rtg:`renderTriggered hook`,0:`setup function`,1:`render function`,2:`watcher getter`,3:`watcher callback`,4:`watcher cleanup function`,5:`native event handler`,6:`component event handler`,7:`vnode hook`,8:`directive hook`,9:`transition hook`,10:`app errorHandler`,11:`app warnHandler`,12:`ref function`,13:`async component loader`,14:`scheduler flush`,15:`component update`,16:`app unmount cleanup function`};function eb(e,t,n,r){try{return r?e(...r):e()}catch(e){nb(e,t,n)}}function tb(e,t,n,r){if(K(e)){let i=eb(e,t,n,r);return i&&hy(i)&&i.catch(e=>{nb(e,t,n)}),i}if(G(e)){let i=[];for(let a=0;a<e.length;a++)i.push(tb(e[a],t,n,r));return i}}function nb(e,t,n,r=!0){let i=t?t.vnode:null,{errorHandler:a,throwUnhandledErrorInProduction:o}=t&&t.appContext.config||U;if(t){let r=t.parent,i=t.proxy,o=`https://vuejs.org/error-reference/#runtime-${n}`;for(;r;){let t=r.ec;if(t){for(let n=0;n<t.length;n++)if(t[n](e,i,o)===!1)return}r=r.parent}if(a){__(),eb(a,null,10,[e,i,o]),v_();return}}rb(e,n,i,r,o)}function rb(e,t,n,r=!0,i=!1){if(i)throw e;console.error(e)}var ib=[],ab=-1,ob=[],sb=null,cb=0,lb=Promise.resolve(),ub=null;function db(e){let t=ub||lb;return e?t.then(this?e.bind(this):e):t}function fb(e){let t=ab+1,n=ib.length;for(;t<n;){let r=t+n>>>1,i=ib[r],a=vb(i);a<e||a===e&&i.flags&2?t=r+1:n=r}return t}function pb(e){if(!(e.flags&1)){let t=vb(e),n=ib[ib.length-1];!n||!(e.flags&2)&&t>=vb(n)?ib.push(e):ib.splice(fb(t),0,e),e.flags|=1,mb()}}function mb(){ub||=lb.then(yb)}function hb(e){G(e)?ob.push(...e):sb&&e.id===-1?sb.splice(cb+1,0,e):e.flags&1||(ob.push(e),e.flags|=1),mb()}function gb(e,t,n=ab+1){for(;n<ib.length;n++){let t=ib[n];if(t&&t.flags&2){if(e&&t.id!==e.uid)continue;ib.splice(n,1),n--,t.flags&4&&(t.flags&=-2),t(),t.flags&4||(t.flags&=-2)}}}function _b(e){if(ob.length){let e=[...new Set(ob)].sort((e,t)=>vb(e)-vb(t));if(ob.length=0,sb){sb.push(...e);return}for(sb=e,cb=0;cb<sb.length;cb++){let e=sb[cb];e.flags&4&&(e.flags&=-2),e.flags&8||e(),e.flags&=-2}sb=null,cb=0}}var vb=e=>e.id==null?e.flags&2?-1:1/0:e.id;function yb(e){try{for(ab=0;ab<ib.length;ab++){let e=ib[ab];e&&!(e.flags&8)&&(e.flags&4&&(e.flags&=-2),eb(e,e.i,e.i?15:14),e.flags&4||(e.flags&=-2))}}finally{for(;ab<ib.length;ab++){let e=ib[ab];e&&(e.flags&=-2)}ab=-1,ib.length=0,_b(e),ub=null,(ib.length||ob.length)&&yb(e)}}var bb,xb=[];function Sb(e,t){bb=e,bb?(bb.enabled=!0,xb.forEach(({event:e,args:t})=>bb.emit(e,...t)),xb=[]):typeof window<`u`&&window.HTMLElement&&!(window.navigator?.userAgent)?.includes(`jsdom`)?((t.__VUE_DEVTOOLS_HOOK_REPLAY__=t.__VUE_DEVTOOLS_HOOK_REPLAY__||[]).push(e=>{Sb(e,t)}),setTimeout(()=>{bb||(t.__VUE_DEVTOOLS_HOOK_REPLAY__=null,xb=[])},3e3)):xb=[]}var Cb=null,wb=null;function Tb(e){let t=Cb;return Cb=e,wb=e&&e.type.__scopeId||null,t}function Eb(e){wb=e}function Db(){wb=null}var Ob=e=>kb;function kb(e,t=Cb,n){if(!t||e._n)return e;let r=(...n)=>{r._d&&ww(-1);let i=Tb(t),a;try{a=e(...n)}finally{Tb(i),r._d&&ww(1)}return a};return r._n=!0,r._c=!0,r._d=!0,r}function Ab(e,t){if(Cb===null)return e;let n=fT(Cb),r=e.dirs||=[];for(let e=0;e<t.length;e++){let[i,a,o,s=U]=t[e];i&&(K(i)&&(i={mounted:i,updated:i}),i.deep&&Qv(a),r.push({dir:i,instance:n,value:a,oldValue:void 0,arg:o,modifiers:s}))}return e}function jb(e,t,n,r){let i=e.dirs,a=t&&t.dirs;for(let o=0;o<i.length;o++){let s=i[o];a&&(s.oldValue=a[o].value);let c=s.dir[r];c&&(__(),tb(c,n,8,[e.el,s,e,t]),v_())}}function Mb(e,t){if(Jw){let n=Jw.provides,r=Jw.parent&&Jw.parent.provides;r===n&&(n=Jw.provides=Object.create(r)),n[e]=t}}function Nb(e,t,n=!1){let r=Yw();if(r||dC){let i=dC?dC._context.provides:r?r.parent==null||r.ce?r.vnode.appContext&&r.vnode.appContext.provides:r.parent.provides:void 0;if(i&&e in i)return i[e];if(arguments.length>1)return n&&K(t)?t.call(r&&r.proxy):t}}function Pb(){return!!(Yw()||dC)}var Fb=Symbol.for(`v-scx`),Ib=()=>Nb(Fb);function Lb(e,t){return Vb(e,null,t)}function Rb(e,t){return Vb(e,null,{flush:`post`})}function zb(e,t){return Vb(e,null,{flush:`sync`})}function Bb(e,t,n){return Vb(e,t,n)}function Vb(e,t,n=U){let{immediate:r,deep:i,flush:a,once:o}=n,s=ay({},n),c=t&&r||!t&&a!==`post`,l;if(tT){if(a===`sync`){let e=Ib();l=e.__watcherHandles||=[]}else if(!c){let e=()=>{};return e.stop=ty,e.resume=ty,e.pause=ty,e}}let u=Jw;s.call=(e,t,n)=>tb(e,u,t,n);let d=!1;a===`post`?s.scheduler=e=>{WC(e,u&&u.suspense)}:a!==`sync`&&(d=!0,s.scheduler=(e,t)=>{t?e():pb(e)}),s.augmentJob=e=>{t&&(e.flags|=4),d&&(e.flags|=2,u&&(e.id=u.uid,e.i=u))};let f=Zv(e,t,s);return tT&&(l?l.push(f):c&&f()),f}function Hb(e,t,n){let r=this.proxy,i=fy(e)?e.includes(`.`)?Ub(r,e):()=>r[e]:e.bind(r,r),a;K(t)?a=t:(a=t.handler,n=t);let o=Qw(this),s=Vb(i,a.bind(r),n);return o(),s}function Ub(e,t){let n=t.split(`.`);return()=>{let t=e;for(let e=0;e<n.length&&t;e++)t=t[n[e]];return t}}var Wb=Symbol(`_vte`),Gb=e=>e.__isTeleport,Kb=e=>e&&(e.disabled||e.disabled===``),qb=e=>e&&(e.defer||e.defer===``),Jb=e=>typeof SVGElement<`u`&&e instanceof SVGElement,Yb=e=>typeof MathMLElement==`function`&&e instanceof MathMLElement,Xb=(e,t)=>{let n=e&&e.to;return fy(n)?t?t(n):null:n},Zb={name:`Teleport`,__isTeleport:!0,process(e,t,n,r,i,a,o,s,c,l){let{mc:u,pc:d,pbc:f,o:{insert:p,querySelector:m,createText:h,createComment:g}}=l,_=Kb(t.props),{shapeFlag:v,children:y,dynamicChildren:b}=t;if(e==null){let e=t.el=h(``),l=t.anchor=h(``);p(e,n,r),p(l,n,r);let d=(e,t)=>{v&16&&u(y,e,t,i,a,o,s,c)},f=()=>{let e=t.target=Xb(t.props,m),n=nx(e,t,h,p);e&&(o!==`svg`&&Jb(e)?o=`svg`:o!==`mathml`&&Yb(e)&&(o=`mathml`),i&&i.isCE&&(i.ce._teleportTargets||(i.ce._teleportTargets=new Set)).add(e),_||(d(e,n),tx(t,!1)))};_&&(d(n,l),tx(t,!0)),qb(t.props)||a&&a.pendingBranch?(t.el.__isMounted=!1,WC(()=>{t.el.__isMounted===!1&&(f(),delete t.el.__isMounted)},a)):f()}else{t.el=e.el,t.targetStart=e.targetStart;let u=t.anchor=e.anchor,p=t.target=e.target,h=t.targetAnchor=e.targetAnchor;if(e.el.__isMounted===!1){WC(()=>{Zb.process(e,t,n,r,i,a,o,s,c,l)},a);return}let g=Kb(e.props),v=g?n:p,y=g?u:h;if(o===`svg`||Jb(p)?o=`svg`:(o===`mathml`||Yb(p))&&(o=`mathml`),b?(f(e.dynamicChildren,b,v,i,a,o,s),ZC(e,t,!0)):c||d(e,t,v,y,i,a,o,s,!1),_)g?t.props&&e.props&&t.props.to!==e.props.to&&(t.props.to=e.props.to):Qb(t,n,u,l,1);else if((t.props&&t.props.to)!==(e.props&&e.props.to)){let e=t.target=Xb(t.props,m);e&&Qb(t,e,null,l,0)}else g&&Qb(t,p,h,l,1);tx(t,_)}},remove(e,t,n,{um:r,o:{remove:i}},a){let{shapeFlag:o,children:s,anchor:c,targetStart:l,targetAnchor:u,target:d,props:f}=e;if(d&&(i(l),i(u)),a&&i(c),o&16){let e=a||!Kb(f);for(let i=0;i<s.length;i++){let a=s[i];r(a,t,n,e,!!a.dynamicChildren)}}},move:Qb,hydrate:$b};function Qb(e,t,n,{o:{insert:r},m:i},a=2){a===0&&r(e.targetAnchor,t,n);let{el:o,anchor:s,shapeFlag:c,children:l,props:u}=e,d=a===2;if(d&&r(o,t,n),(!d||Kb(u))&&c&16)for(let e=0;e<l.length;e++)i(l[e],t,n,2);d&&r(s,t,n)}function $b(e,t,n,r,i,a,{o:{nextSibling:o,parentNode:s,querySelector:c,insert:l,createText:u}},d){function f(e,n){let r=n;for(;r;){if(r&&r.nodeType===8){if(r.data===`teleport start anchor`)t.targetStart=r;else if(r.data===`teleport anchor`){t.targetAnchor=r,e._lpa=t.targetAnchor&&o(t.targetAnchor);break}}r=o(r)}}function p(e,t){t.anchor=d(o(e),t,s(e),n,r,i,a)}let m=t.target=Xb(t.props,c),h=Kb(t.props);if(m){let c=m._lpa||m.firstChild;t.shapeFlag&16&&(h?(p(e,t),f(m,c),t.targetAnchor||nx(m,t,u,l,s(e)===m?e:null)):(t.anchor=o(e),f(m,c),t.targetAnchor||nx(m,t,u,l),d(c&&o(c),t,m,n,r,i,a))),tx(t,h)}else h&&t.shapeFlag&16&&(p(e,t),t.targetStart=e,t.targetAnchor=o(e));return t.anchor&&o(t.anchor)}var ex=Zb;function tx(e,t){let n=e.ctx;if(n&&n.ut){let r,i;for(t?(r=e.el,i=e.anchor):(r=e.targetStart,i=e.targetAnchor);r&&r!==i;)r.nodeType===1&&r.setAttribute(`data-v-owner`,n.uid),r=r.nextSibling;n.ut()}}function nx(e,t,n,r,i=null){let a=t.targetStart=n(``),o=t.targetAnchor=n(``);return a[Wb]=o,e&&(r(a,e,i),r(o,e,i)),o}var rx=Symbol(`_leaveCb`),ix=Symbol(`_enterCb`);function ax(){let e={isMounted:!1,isLeaving:!1,isUnmounting:!1,leavingVNodes:new Map};return aS(()=>{e.isMounted=!0}),cS(()=>{e.isUnmounting=!0}),e}var ox=[Function,Array],sx={mode:String,appear:Boolean,persisted:Boolean,onBeforeEnter:ox,onEnter:ox,onAfterEnter:ox,onEnterCancelled:ox,onBeforeLeave:ox,onLeave:ox,onAfterLeave:ox,onLeaveCancelled:ox,onBeforeAppear:ox,onAppear:ox,onAfterAppear:ox,onAppearCancelled:ox},cx=e=>{let t=e.subTree;return t.component?cx(t.component):t},lx={name:`BaseTransition`,props:sx,setup(e,{slots:t}){let n=Yw(),r=ax();return()=>{let i=t.default&&_x(t.default(),!0);if(!i||!i.length)return;let a=ux(i),o=H(e),{mode:s}=o;if(r.isLeaving)return mx(a);let c=hx(a);if(!c)return mx(a);let l=px(c,o,r,n,e=>l=e);c.type!==_w&&gx(c,l);let u=n.subTree&&hx(n.subTree);if(u&&u.type!==_w&&!kw(u,c)&&cx(n).type!==_w){let e=px(u,o,r,n);if(gx(u,e),s===`out-in`&&c.type!==_w)return r.isLeaving=!0,e.afterLeave=()=>{r.isLeaving=!1,n.job.flags&8||n.update(),delete e.afterLeave,u=void 0},mx(a);s===`in-out`&&c.type!==_w?e.delayLeave=(e,t,n)=>{let i=fx(r,u);i[String(u.key)]=u,e[rx]=()=>{t(),e[rx]=void 0,delete l.delayedLeave,u=void 0},l.delayedLeave=()=>{n(),delete l.delayedLeave,u=void 0}}:u=void 0}else u&&=void 0;return a}}};function ux(e){let t=e[0];if(e.length>1){for(let n of e)if(n.type!==_w){t=n;break}}return t}var dx=lx;function fx(e,t){let{leavingVNodes:n}=e,r=n.get(t.type);return r||(r=Object.create(null),n.set(t.type,r)),r}function px(e,t,n,r,i){let{appear:a,mode:o,persisted:s=!1,onBeforeEnter:c,onEnter:l,onAfterEnter:u,onEnterCancelled:d,onBeforeLeave:f,onLeave:p,onAfterLeave:m,onLeaveCancelled:h,onBeforeAppear:g,onAppear:_,onAfterAppear:v,onAppearCancelled:y}=t,b=String(e.key),x=fx(n,e),S=(e,t)=>{e&&tb(e,r,9,t)},C=(e,t)=>{let n=t[1];S(e,t),G(e)?e.every(e=>e.length<=1)&&n():e.length<=1&&n()},w={mode:o,persisted:s,beforeEnter(t){let r=c;if(!n.isMounted)if(a)r=g||c;else return;t[rx]&&t[rx](!0);let i=x[b];i&&kw(e,i)&&i.el[rx]&&i.el[rx](),S(r,[t])},enter(t){if(x[b]===e)return;let r=l,i=u,o=d;if(!n.isMounted)if(a)r=_||l,i=v||u,o=y||d;else return;let s=!1;t[ix]=e=>{s||(s=!0,S(e?o:i,[t]),w.delayedLeave&&w.delayedLeave(),t[ix]=void 0)};let c=t[ix].bind(null,!1);r?C(r,[t,c]):c()},leave(t,r){let i=String(e.key);if(t[ix]&&t[ix](!0),n.isUnmounting)return r();S(f,[t]);let a=!1;t[rx]=n=>{a||(a=!0,r(),S(n?h:m,[t]),t[rx]=void 0,x[i]===e&&delete x[i])};let o=t[rx].bind(null,!1);x[i]=e,p?C(p,[t,o]):o()},clone(e){let a=px(e,t,n,r,i);return i&&i(a),a}};return w}function mx(e){if(qx(e))return e=Iw(e),e.children=null,e}function hx(e){if(!qx(e))return Gb(e.type)&&e.children?ux(e.children):e;if(e.component)return e.component.subTree;let{shapeFlag:t,children:n}=e;if(n){if(t&16)return n[0];if(t&32&&K(n.default))return n.default()}}function gx(e,t){e.shapeFlag&6&&e.component?(e.transition=t,gx(e.component.subTree,t)):e.shapeFlag&128?(e.ssContent.transition=t.clone(e.ssContent),e.ssFallback.transition=t.clone(e.ssFallback)):e.transition=t}function _x(e,t=!1,n){let r=[],i=0;for(let a=0;a<e.length;a++){let o=e[a],s=n==null?o.key:String(n)+String(o.key==null?a:o.key);o.type===hw?(o.patchFlag&128&&i++,r=r.concat(_x(o.children,t,s))):(t||o.type!==_w)&&r.push(s==null?o:Iw(o,{key:s}))}if(i>1)for(let e=0;e<r.length;e++)r[e].patchFlag=-2;return r}function vx(e,t){return K(e)?ay({name:e.name},t,{setup:e}):e}function yx(){let e=Yw();return e?(e.appContext.config.idPrefix||`v`)+`-`+e.ids[0]+ e.ids[1]++:``}function bx(e){e.ids=[e.ids[0]+ e.ids[2]+++`-`,0,0]}function xx(e){let t=Yw(),n=Dv(null);if(t){let r=t.refs===U?t.refs={}:t.refs;Object.defineProperty(r,e,{enumerable:!0,get:()=>n.value,set:e=>n.value=e})}return n}function Sx(e,t){let n;return!!((n=Object.getOwnPropertyDescriptor(e,t))&&!n.configurable)}var Cx=new WeakMap;function wx(e,t,n,r,i=!1){if(G(e)){e.forEach((e,a)=>wx(e,t&&(G(t)?t[a]:t),n,r,i));return}if(Wx(r)&&!i){r.shapeFlag&512&&r.type.__asyncResolved&&r.component.subTree.component&&wx(e,t,n,r.component.subTree);return}let a=r.shapeFlag&4?fT(r.component):r.el,o=i?null:a,{i:s,r:c}=e,l=t&&t.r,u=s.refs===U?s.refs={}:s.refs,d=s.setupState,f=H(d),p=d===U?ny:e=>Sx(u,e)?!1:W(f,e),m=(e,t)=>!(t&&Sx(u,t));if(l!=null&&l!==c){if(Tx(t),fy(l))u[l]=null,p(l)&&(d[l]=null);else if(Tv(l)){let e=t;m(l,e.k)&&(l.value=null),e.k&&(u[e.k]=null)}}if(K(c))eb(c,s,12,[o,u]);else{let t=fy(c),r=Tv(c);if(t||r){let s=()=>{if(e.f){let n=t?p(c)?d[c]:u[c]:m(c)||!e.k?c.value:u[e.k];if(i)G(n)&&oy(n,a);else if(G(n))n.includes(a)||n.push(a);else if(t)u[c]=[a],p(c)&&(d[c]=u[c]);else{let t=[a];m(c,e.k)&&(c.value=t),e.k&&(u[e.k]=t)}}else t?(u[c]=o,p(c)&&(d[c]=o)):r&&(m(c,e.k)&&(c.value=o),e.k&&(u[e.k]=o))};if(o){let t=()=>{s(),Cx.delete(e)};t.id=-1,Cx.set(e,t),WC(t,n)}else Tx(e),s()}}}function Tx(e){let t=Cx.get(e);t&&(t.flags|=8,Cx.delete(e))}var Ex=!1,Dx=()=>{Ex||=(console.error(`Hydration completed but contains mismatches.`),!0)},Ox=e=>e.namespaceURI.includes(`svg`)&&e.tagName!==`foreignObject`,kx=e=>e.namespaceURI.includes(`MathML`),Ax=e=>{if(e.nodeType===1){if(Ox(e))return`svg`;if(kx(e))return`mathml`}},jx=e=>e.nodeType===8;function Mx(e){let{mt:t,p:n,o:{patchProp:r,createText:i,nextSibling:a,parentNode:o,remove:s,insert:c,createComment:l}}=e,u=(e,t)=>{if(!t.hasChildNodes()){n(null,e,t),_b(),t._vnode=e;return}d(t.firstChild,e,null,null,null),_b(),t._vnode=e},d=(n,r,s,l,u,y=!1)=>{y||=!!r.dynamicChildren;let b=jx(n)&&n.data===`[`,x=()=>h(n,r,s,l,u,b),{type:S,ref:C,shapeFlag:w,patchFlag:T}=r,E=n.nodeType;r.el=n,T===-2&&(y=!1,r.dynamicChildren=null);let D=null;switch(S){case gw:E===3?(n.data!==r.children&&(Dx(),n.data=r.children),D=a(n)):r.children===``?(c(r.el=i(``),o(n),n),D=n):D=x();break;case _w:v(n)?(D=a(n),_(r.el=n.content.firstChild,n,s)):D=E!==8||b?x():a(n);break;case vw:if(b&&(n=a(n),E=n.nodeType),E===1||E===3){D=n;let e=!r.children.length;for(let t=0;t<r.staticCount;t++)e&&(r.children+=D.nodeType===1?D.outerHTML:D.data),t===r.staticCount-1&&(r.anchor=D),D=a(D);return b?a(D):D}else x();break;case hw:D=b?m(n,r,s,l,u,y):x();break;default:if(w&1)D=(E!==1||r.type.toLowerCase()!==n.tagName.toLowerCase())&&!v(n)?x():f(n,r,s,l,u,y);else if(w&6){r.slotScopeIds=u;let e=o(n);if(D=b?g(n):jx(n)&&n.data===`teleport start`?g(n,n.data,`teleport end`):a(n),t(r,e,null,s,l,Ax(e),y),Wx(r)&&!r.type.__asyncResolved){let t;b?(t=q(hw),t.anchor=D?D.previousSibling:e.lastChild):t=n.nodeType===3?Lw(``):q(`div`),t.el=n,r.component.subTree=t}}else w&64?D=E===8?r.type.hydrate(n,r,s,l,u,y,e,p):x():w&128&&(D=r.type.hydrate(n,r,s,l,Ax(o(n)),u,y,e,d))}return C!=null&&wx(C,null,l,r),D},f=(e,t,n,i,a,o)=>{o||=!!t.dynamicChildren;let{type:c,props:l,patchFlag:u,shapeFlag:d,dirs:f,transition:m}=t,h=c===`input`||c===`option`;if(h||u!==-1){f&&jb(t,null,n,`created`);let c=!1;if(v(e)){c=XC(null,m)&&n&&n.vnode.props&&n.vnode.props.appear;let r=e.content.firstChild;if(c){let e=r.getAttribute(`class`);e&&(r.$cls=e),m.beforeEnter(r)}_(r,e,n),t.el=e=r}if(d&16&&!(l&&(l.innerHTML||l.textContent))){let r=p(e.firstChild,t,e,n,i,a,o);for(;r;){Fx(e,1)||Dx();let t=r;r=r.nextSibling,s(t)}}else if(d&8){let n=t.children;n[0]===`
`&&(e.tagName===`PRE`||e.tagName===`TEXTAREA`)&&(n=n.slice(1));let{textContent:r}=e;r!==n&&r!==n.replace(/\r\n|\r/g,`
`)&&(Fx(e,0)||Dx(),e.textContent=t.children)}if(l){if(h||!o||u&48){let t=e.tagName.includes(`-`);for(let i in l)(h&&(i.endsWith(`value`)||i===`indeterminate`)||ry(i)&&!yy(i)||i[0]===`.`||t&&!yy(i))&&r(e,i,null,l[i],void 0,n)}else if(l.onClick)r(e,`onClick`,null,l.onClick,void 0,n);else if(u&4&&vv(l.style))for(let e in l.style)l.style[e]}let g;(g=l&&l.onVnodeBeforeMount)&&Ww(g,n,t),f&&jb(t,null,n,`beforeMount`),((g=l&&l.onVnodeMounted)||f||c)&&fw(()=>{g&&Ww(g,n,t),c&&m.enter(e),f&&jb(t,null,n,`mounted`)},i)}return e.nextSibling},p=(e,t,r,o,s,l,u)=>{u||=!!t.dynamicChildren;let f=t.children,p=f.length;for(let t=0;t<p;t++){let m=u?f[t]:f[t]=Bw(f[t]),h=m.type===gw;e?(h&&!u&&t+1<p&&Bw(f[t+1]).type===gw&&(c(i(e.data.slice(m.children.length)),r,a(e)),e.data=m.children),e=d(e,m,o,s,l,u)):h&&!m.children?c(m.el=i(``),r):(Fx(r,1)||Dx(),n(null,m,r,null,o,s,Ax(r),l))}return e},m=(e,t,n,r,i,s)=>{let{slotScopeIds:u}=t;u&&(i=i?i.concat(u):u);let d=o(e),f=p(a(e),t,d,n,r,i,s);return f&&jx(f)&&f.data===`]`?a(t.anchor=f):(Dx(),c(t.anchor=l(`]`),d,f),f)},h=(e,t,r,i,c,l)=>{if(Fx(e.parentElement,1)||Dx(),t.el=null,l){let t=g(e);for(;;){let n=a(e);if(n&&n!==t)s(n);else break}}let u=a(e),d=o(e);return s(e),n(null,t,d,u,r,i,Ax(d),c),r&&(r.vnode.el=t.el,TC(r,t.el)),u},g=(e,t=`[`,n=`]`)=>{let r=0;for(;e;)if(e=a(e),e&&jx(e)&&(e.data===t&&r++,e.data===n)){if(r===0)return a(e);r--}return e},_=(e,t,n)=>{let r=t.parentNode;r&&r.replaceChild(e,t);let i=n;for(;i;)i.vnode.el===t&&(i.vnode.el=i.subTree.el=e),i=i.parent},v=e=>e.nodeType===1&&e.tagName===`TEMPLATE`;return[u,d]}var Nx=`data-allow-mismatch`,Px={0:`text`,1:`children`,2:`class`,3:`style`,4:`attribute`};function Fx(e,t){if(t===0||t===1)for(;e&&!e.hasAttribute(Nx);)e=e.parentElement;let n=e&&e.getAttribute(Nx);if(n==null)return!1;if(n===``)return!0;{let e=n.split(`,`);return t===0&&e.includes(`children`)?!0:e.includes(Px[t])}}var Ix=Ny().requestIdleCallback||(e=>setTimeout(e,1)),Lx=Ny().cancelIdleCallback||(e=>clearTimeout(e)),Rx=(e=1e4)=>t=>{let n=Ix(t,{timeout:e});return()=>Lx(n)};function zx(e){let{top:t,left:n,bottom:r,right:i}=e.getBoundingClientRect(),{innerHeight:a,innerWidth:o}=window;return(t>0&&t<a||r>0&&r<a)&&(n>0&&n<o||i>0&&i<o)}var Bx=e=>(t,n)=>{let r=new IntersectionObserver(e=>{for(let n of e)if(n.isIntersecting){r.disconnect(),t();break}},e);return n(e=>{if(e instanceof Element){if(zx(e))return t(),r.disconnect(),!1;r.observe(e)}}),()=>r.disconnect()},Vx=e=>t=>{if(e){let n=matchMedia(e);if(n.matches)t();else return n.addEventListener(`change`,t,{once:!0}),()=>n.removeEventListener(`change`,t)}},Hx=(e=[])=>(t,n)=>{fy(e)&&(e=[e]);let r=!1,i=e=>{r||(r=!0,a(),t(),e.target.dispatchEvent(new e.constructor(e.type,e)))},a=()=>{n(t=>{for(let n of e)t.removeEventListener(n,i)})};return n(t=>{for(let n of e)t.addEventListener(n,i,{once:!0})}),a};function Ux(e,t){if(jx(e)&&e.data===`[`){let n=1,r=e.nextSibling;for(;r;){if(r.nodeType===1){if(t(r)===!1)break}else if(jx(r))if(r.data===`]`){if(--n===0)break}else r.data===`[`&&n++;r=r.nextSibling}}else t(e)}var Wx=e=>!!e.type.__asyncLoader;function Gx(e){K(e)&&(e={loader:e});let{loader:t,loadingComponent:n,errorComponent:r,delay:i=200,hydrate:a,timeout:o,suspensible:s=!0,onError:c}=e,l=null,u,d=0,f=()=>(d++,l=null,p()),p=()=>{let e;return l||(e=l=t().catch(e=>{if(e=e instanceof Error?e:Error(String(e)),c)return new Promise((t,n)=>{c(e,()=>t(f()),()=>n(e),d+1)});throw e}).then(t=>e!==l&&l?l:(t&&(t.__esModule||t[Symbol.toStringTag]===`Module`)&&(t=t.default),u=t,t)))};return vx({name:`AsyncComponentWrapper`,__asyncLoader:p,__asyncHydrate(e,t,n){let r=!1;(t.bu||=[]).push(()=>r=!0);let i=()=>{r||n()},o=a?()=>{let n=a(i,t=>Ux(e,t));n&&(t.bum||=[]).push(n)}:i;u?o():p().then(()=>!t.isUnmounted&&o())},get __asyncResolved(){return u},setup(){let e=Jw;if(bx(e),u)return()=>Kx(u,e);let t=t=>{l=null,nb(t,e,13,!r)};if(s&&e.suspense||tT)return p().then(t=>()=>Kx(t,e)).catch(e=>(t(e),()=>r?q(r,{error:e}):null));let a=Ev(!1),c=Ev(),d=Ev(!!i);return i&&setTimeout(()=>{d.value=!1},i),o!=null&&setTimeout(()=>{if(!a.value&&!c.value){let e=Error(`Async component timed out after ${o}ms.`);t(e),c.value=e}},o),p().then(()=>{a.value=!0,e.parent&&qx(e.parent.vnode)&&e.parent.update()}).catch(e=>{t(e),c.value=e}),()=>{if(a.value&&u)return Kx(u,e);if(c.value&&r)return q(r,{error:c.value});if(n&&!d.value)return Kx(n,e)}}})}function Kx(e,t){let{ref:n,props:r,children:i,ce:a}=t.vnode,o=q(e,r,i);return o.ref=n,o.ce=a,delete t.vnode.ce,o}var qx=e=>e.type.__isKeepAlive,Jx={name:`KeepAlive`,__isKeepAlive:!0,props:{include:[String,RegExp,Array],exclude:[String,RegExp,Array],max:[String,Number]},setup(e,{slots:t}){let n=Yw(),r=n.ctx;if(!r.renderer)return()=>{let e=t.default&&t.default();return e&&e.length===1?e[0]:e};let i=new Map,a=new Set,o=null,s=n.suspense,{renderer:{p:c,m:l,um:u,o:{createElement:d}}}=r,f=d(`div`);r.activate=(e,t,n,r,i)=>{let a=e.component;l(e,t,n,0,s),c(a.vnode,e,t,n,a,s,r,e.slotScopeIds,i),WC(()=>{a.isDeactivated=!1,a.a&&Oy(a.a);let t=e.props&&e.props.onVnodeMounted;t&&Ww(t,a.parent,e)},s)},r.deactivate=e=>{let t=e.component;ew(t.m),ew(t.a),l(e,f,null,1,s),WC(()=>{t.da&&Oy(t.da);let n=e.props&&e.props.onVnodeUnmounted;n&&Ww(n,t.parent,e),t.isDeactivated=!0},s)};function p(e){eS(e),u(e,n,s,!0)}function m(e){i.forEach((t,n)=>{let r=pT(Wx(t)?t.type.__asyncResolved||{}:t.type);r&&!e(r)&&h(n)})}function h(e){let t=i.get(e);t&&(!o||!kw(t,o))?p(t):o&&eS(o),i.delete(e),a.delete(e)}Bb(()=>[e.include,e.exclude],([e,t])=>{e&&m(t=>Yx(e,t)),t&&m(e=>!Yx(t,e))},{flush:`post`,deep:!0});let g=null,_=()=>{g!=null&&(nw(n.subTree.type)?WC(()=>{i.set(g,tS(n.subTree))},n.subTree.suspense):i.set(g,tS(n.subTree)))};return aS(_),sS(_),cS(()=>{i.forEach(e=>{let{subTree:t,suspense:r}=n,i=tS(t);if(e.type===i.type&&e.key===i.key){eS(i);let e=i.component.da;e&&WC(e,r);return}p(e)})}),()=>{if(g=null,!t.default)return o=null;let n=t.default(),r=n[0];if(n.length>1)return o=null,n;if(!Ow(r)||!(r.shapeFlag&4)&&!(r.shapeFlag&128))return o=null,r;let s=tS(r);if(s.type===_w)return o=null,s;let c=s.type,l=pT(Wx(s)?s.type.__asyncResolved||{}:c),{include:u,exclude:d,max:f}=e;if(u&&(!l||!Yx(u,l))||d&&l&&Yx(d,l))return s.shapeFlag&=-257,o=s,r;let p=s.key==null?c:s.key,m=i.get(p);return s.el&&(s=Iw(s),r.shapeFlag&128&&(r.ssContent=s)),g=p,m?(s.el=m.el,s.component=m.component,s.transition&&gx(s,s.transition),s.shapeFlag|=512,a.delete(p),a.add(p)):(a.add(p),f&&a.size>parseInt(f,10)&&h(a.values().next().value)),s.shapeFlag|=256,o=s,nw(r.type)?r:s}}};function Yx(e,t){return G(e)?e.some(e=>Yx(e,t)):fy(e)?e.split(`,`).includes(t):dy(e)?(e.lastIndex=0,e.test(t)):!1}function Xx(e,t){Qx(e,`a`,t)}function Zx(e,t){Qx(e,`da`,t)}function Qx(e,t,n=Jw){let r=e.__wdc||=()=>{let t=n;for(;t;){if(t.isDeactivated)return;t=t.parent}return e()};if(nS(t,r,n),n){let e=n.parent;for(;e&&e.parent;)qx(e.parent.vnode)&&$x(r,t,n,e),e=e.parent}}function $x(e,t,n,r){let i=nS(t,e,r,!0);lS(()=>{oy(r[t],i)},n)}function eS(e){e.shapeFlag&=-257,e.shapeFlag&=-513}function tS(e){return e.shapeFlag&128?e.ssContent:e}function nS(e,t,n=Jw,r=!1){if(n){let i=n[e]||(n[e]=[]),a=t.__weh||=(...r)=>{__();let i=Qw(n),a=tb(t,n,e,r);return i(),v_(),a};return r?i.unshift(a):i.push(a),a}}var rS=e=>(t,n=Jw)=>{(!tT||e===`sp`)&&nS(e,(...e)=>t(...e),n)},iS=rS(`bm`),aS=rS(`m`),oS=rS(`bu`),sS=rS(`u`),cS=rS(`bum`),lS=rS(`um`),uS=rS(`sp`),dS=rS(`rtg`),fS=rS(`rtc`);function pS(e,t=Jw){nS(`ec`,e,t)}var mS=`components`,hS=`directives`;function gS(e,t){return bS(mS,e,!0,t)||e}var _S=Symbol.for(`v-ndc`);function vS(e){return fy(e)?bS(mS,e,!1)||e:e||_S}function yS(e){return bS(hS,e)}function bS(e,t,n=!0,r=!1){let i=Cb||Jw;if(i){let n=i.type;if(e===mS){let e=pT(n,!1);if(e&&(e===t||e===Sy(t)||e===Ty(Sy(t))))return n}let a=xS(i[e]||n[e],t)||xS(i.appContext[e],t);return!a&&r?n:a}}function xS(e,t){return e&&(e[t]||e[Sy(t)]||e[Ty(Sy(t))])}function SS(e,t,n,r){let i,a=n&&n[r],o=G(e);if(o||fy(e)){let n=o&&vv(e),r=!1,s=!1;n&&(r=!bv(e),s=yv(e),e=M_(e)),i=Array(e.length);for(let n=0,o=e.length;n<o;n++)i[n]=t(r?s?wv(Cv(e[n])):Cv(e[n]):e[n],n,void 0,a&&a[n])}else if(typeof e==`number`){i=Array(e);for(let n=0;n<e;n++)i[n]=t(n+1,n,void 0,a&&a[n])}else if(my(e))if(e[Symbol.iterator])i=Array.from(e,(e,n)=>t(e,n,void 0,a&&a[n]));else{let n=Object.keys(e);i=Array(n.length);for(let r=0,o=n.length;r<o;r++){let o=n[r];i[r]=t(e[o],o,r,a&&a[r])}}else i=[];return n&&(n[r]=i),i}function CS(e,t){for(let n=0;n<t.length;n++){let r=t[n];if(G(r))for(let t=0;t<r.length;t++)e[r[t].name]=r[t].fn;else r&&(e[r.name]=r.key?(...e)=>{let t=r.fn(...e);return t&&(t.key=r.key),t}:r.fn)}return e}function wS(e,t,n={},r,i){if(Cb.ce||Cb.parent&&Wx(Cb.parent)&&Cb.parent.ce){let e=Object.keys(n).length>0;return t!==`default`&&(n.name=t),xw(),Dw(hw,null,[q(`slot`,n,r&&r())],e?-2:64)}let a=e[t];a&&a._c&&(a._d=!1),xw();let o=a&&TS(a(n)),s=n.key||o&&o.key,c=Dw(hw,{key:(s&&!py(s)?s:`_${t}`)+(!o&&r?`_fb`:``)},o||(r?r():[]),o&&e._===1?64:-2);return!i&&c.scopeId&&(c.slotScopeIds=[c.scopeId+`-s`]),a&&a._c&&(a._d=!0),c}function TS(e){return e.some(e=>Ow(e)?!(e.type===_w||e.type===hw&&!TS(e.children)):!0)?e:null}function ES(e,t){let n={};for(let r in e)n[t&&/[A-Z]/.test(r)?`on:${r}`:Ey(r)]=e[r];return n}var DS=e=>e?eT(e)?fT(e):DS(e.parent):null,OS=ay(Object.create(null),{$:e=>e,$el:e=>e.vnode.el,$data:e=>e.data,$props:e=>e.props,$attrs:e=>e.attrs,$slots:e=>e.slots,$refs:e=>e.refs,$parent:e=>DS(e.parent),$root:e=>DS(e.root),$host:e=>e.ce,$emit:e=>e.emit,$options:e=>QS(e),$forceUpdate:e=>e.f||=()=>{pb(e.update)},$nextTick:e=>e.n||=db.bind(e.proxy),$watch:e=>Hb.bind(e)}),kS=(e,t)=>e!==U&&!e.__isScriptSetup&&W(e,t),AS={get({_:e},t){if(t===`__v_skip`)return!0;let{ctx:n,setupState:r,data:i,props:a,accessCache:o,type:s,appContext:c}=e;if(t[0]!==`$`){let e=o[t];if(e!==void 0)switch(e){case 1:return r[t];case 2:return i[t];case 4:return n[t];case 3:return a[t]}else if(kS(r,t))return o[t]=1,r[t];else if(i!==U&&W(i,t))return o[t]=2,i[t];else if(W(a,t))return o[t]=3,a[t];else if(n!==U&&W(n,t))return o[t]=4,n[t];else qS&&(o[t]=0)}let l=OS[t],u,d;if(l)return t===`$attrs`&&O_(e.attrs,`get`,``),l(e);if((u=s.__cssModules)&&(u=u[t]))return u;if(n!==U&&W(n,t))return o[t]=4,n[t];if(d=c.config.globalProperties,W(d,t))return d[t]},set({_:e},t,n){let{data:r,setupState:i,ctx:a}=e;return kS(i,t)?(i[t]=n,!0):r!==U&&W(r,t)?(r[t]=n,!0):W(e.props,t)||t[0]===`$`&&t.slice(1)in e?!1:(a[t]=n,!0)},has({_:{data:e,setupState:t,accessCache:n,ctx:r,appContext:i,props:a,type:o}},s){let c;return!!(n[s]||e!==U&&s[0]!==`$`&&W(e,s)||kS(t,s)||W(a,s)||W(r,s)||W(OS,s)||W(i.config.globalProperties,s)||(c=o.__cssModules)&&c[s])},defineProperty(e,t,n){return n.get==null?W(n,`value`)&&this.set(e,t,n.value,null):e._.accessCache[t]=0,Reflect.defineProperty(e,t,n)}},jS=ay({},AS,{get(e,t){if(t!==Symbol.unscopables)return AS.get(e,t,e)},has(e,t){return t[0]!==`_`&&!Py(t)}});function MS(){return null}function NS(){return null}function PS(e){}function FS(e){}function IS(){return null}function LS(){}function RS(e,t){return null}function zS(){return VS(`useSlots`).slots}function BS(){return VS(`useAttrs`).attrs}function VS(e){let t=Yw();return t.setupContext||=dT(t)}function HS(e){return G(e)?e.reduce((e,t)=>(e[t]=null,e),{}):e}function US(e,t){let n=HS(e);for(let e in t){if(e.startsWith(`__skip`))continue;let r=n[e];r?G(r)||K(r)?r=n[e]={type:r,default:t[e]}:r.default=t[e]:r===null&&(r=n[e]={default:t[e]}),r&&t[`__skip_${e}`]&&(r.skipFactory=!0)}return n}function WS(e,t){return!e||!t?e||t:G(e)&&G(t)?e.concat(t):ay({},HS(e),HS(t))}function GS(e,t){let n={};for(let r in e)t.includes(r)||Object.defineProperty(n,r,{enumerable:!0,get:()=>e[r]});return n}function KS(e){let t=Yw(),n=tT,r=e();$w(),n&&Zw(!1);let i=()=>{Qw(t),n&&Zw(!0)},a=()=>{Yw()!==t&&t.scope.off(),$w(),n&&Zw(!1)};return hy(r)&&(r=r.catch(e=>{throw i(),Promise.resolve().then(()=>Promise.resolve().then(a)),e})),[r,()=>{i(),Promise.resolve().then(a)}]}var qS=!0;function JS(e){let t=QS(e),n=e.proxy,r=e.ctx;qS=!1,t.beforeCreate&&XS(t.beforeCreate,e,`bc`);let{data:i,computed:a,methods:o,watch:s,provide:c,inject:l,created:u,beforeMount:d,mounted:f,beforeUpdate:p,updated:m,activated:h,deactivated:g,beforeDestroy:_,beforeUnmount:v,destroyed:y,unmounted:b,render:x,renderTracked:S,renderTriggered:C,errorCaptured:w,serverPrefetch:T,expose:E,inheritAttrs:D,components:ee,directives:te,filters:ne}=t;if(l&&YS(l,r,null),o)for(let e in o){let t=o[e];K(t)&&(r[e]=t.bind(n))}if(i){let t=i.call(n,n);my(t)&&(e.data=pv(t))}if(qS=!0,a)for(let e in a){let t=a[e],i=J({get:K(t)?t.bind(n,n):K(t.get)?t.get.bind(n,n):ty,set:!K(t)&&K(t.set)?t.set.bind(n):ty});Object.defineProperty(r,e,{enumerable:!0,configurable:!0,get:()=>i.value,set:e=>i.value=e})}if(s)for(let e in s)ZS(s[e],r,n,e);if(c){let e=K(c)?c.call(n):c;Reflect.ownKeys(e).forEach(t=>{Mb(t,e[t])})}u&&XS(u,e,`c`);function re(e,t){G(t)?t.forEach(t=>e(t.bind(n))):t&&e(t.bind(n))}if(re(iS,d),re(aS,f),re(oS,p),re(sS,m),re(Xx,h),re(Zx,g),re(pS,w),re(fS,S),re(dS,C),re(cS,v),re(lS,b),re(uS,T),G(E))if(E.length){let t=e.exposed||={};E.forEach(e=>{Object.defineProperty(t,e,{get:()=>n[e],set:t=>n[e]=t,enumerable:!0})})}else e.exposed||={};x&&e.render===ty&&(e.render=x),D!=null&&(e.inheritAttrs=D),ee&&(e.components=ee),te&&(e.directives=te),T&&bx(e)}function YS(e,t,n=ty){G(e)&&(e=rC(e));for(let n in e){let r=e[n],i;i=my(r)?`default`in r?Nb(r.from||n,r.default,!0):Nb(r.from||n):Nb(r),Tv(i)?Object.defineProperty(t,n,{enumerable:!0,configurable:!0,get:()=>i.value,set:e=>i.value=e}):t[n]=i}}function XS(e,t,n){tb(G(e)?e.map(e=>e.bind(t.proxy)):e.bind(t.proxy),t,n)}function ZS(e,t,n,r){let i=r.includes(`.`)?Ub(n,r):()=>n[r];if(fy(e)){let n=t[e];K(n)&&Bb(i,n)}else if(K(e))Bb(i,e.bind(n));else if(my(e))if(G(e))e.forEach(e=>ZS(e,t,n,r));else{let r=K(e.handler)?e.handler.bind(n):t[e.handler];K(r)&&Bb(i,r,e)}}function QS(e){let t=e.type,{mixins:n,extends:r}=t,{mixins:i,optionsCache:a,config:{optionMergeStrategies:o}}=e.appContext,s=a.get(t),c;return s?c=s:!i.length&&!n&&!r?c=t:(c={},i.length&&i.forEach(e=>$S(c,e,o,!0)),$S(c,t,o)),my(t)&&a.set(t,c),c}function $S(e,t,n,r=!1){let{mixins:i,extends:a}=t;a&&$S(e,a,n,!0),i&&i.forEach(t=>$S(e,t,n,!0));for(let i in t)if(!(r&&i===`expose`)){let r=eC[i]||n&&n[i];e[i]=r?r(e[i],t[i]):t[i]}return e}var eC={data:tC,props:oC,emits:oC,methods:aC,computed:aC,beforeCreate:iC,created:iC,beforeMount:iC,mounted:iC,beforeUpdate:iC,updated:iC,beforeDestroy:iC,beforeUnmount:iC,destroyed:iC,unmounted:iC,activated:iC,deactivated:iC,errorCaptured:iC,serverPrefetch:iC,components:aC,directives:aC,watch:sC,provide:tC,inject:nC};function tC(e,t){return t?e?function(){return ay(K(e)?e.call(this,this):e,K(t)?t.call(this,this):t)}:t:e}function nC(e,t){return aC(rC(e),rC(t))}function rC(e){if(G(e)){let t={};for(let n=0;n<e.length;n++)t[e[n]]=e[n];return t}return e}function iC(e,t){return e?[...new Set([].concat(e,t))]:t}function aC(e,t){return e?ay(Object.create(null),e,t):t}function oC(e,t){return e?G(e)&&G(t)?[...new Set([...e,...t])]:ay(Object.create(null),HS(e),HS(t??{})):t}function sC(e,t){if(!e)return t;if(!t)return e;let n=ay(Object.create(null),e);for(let r in t)n[r]=iC(e[r],t[r]);return n}function cC(){return{app:null,config:{isNativeTag:ny,performance:!1,globalProperties:{},optionMergeStrategies:{},errorHandler:void 0,warnHandler:void 0,compilerOptions:{}},mixins:[],components:{},directives:{},provides:Object.create(null),optionsCache:new WeakMap,propsCache:new WeakMap,emitsCache:new WeakMap}}var lC=0;function uC(e,t){return function(n,r=null){K(n)||(n=ay({},n)),r!=null&&!my(r)&&(r=null);let i=cC(),a=new WeakSet,o=[],s=!1,c=i.app={_uid:lC++,_component:n,_props:r,_container:null,_context:i,_instance:null,version:yT,get config(){return i.config},set config(e){},use(e,...t){return a.has(e)||(e&&K(e.install)?(a.add(e),e.install(c,...t)):K(e)&&(a.add(e),e(c,...t))),c},mixin(e){return i.mixins.includes(e)||i.mixins.push(e),c},component(e,t){return t?(i.components[e]=t,c):i.components[e]},directive(e,t){return t?(i.directives[e]=t,c):i.directives[e]},mount(a,o,l){if(!s){let u=c._ceVNode||q(n,r);return u.appContext=i,l===!0?l=`svg`:l===!1&&(l=void 0),o&&t?t(u,a):e(u,a,l),s=!0,c._container=a,a.__vue_app__=c,fT(u.component)}},onUnmount(e){o.push(e)},unmount(){s&&(tb(o,c._instance,16),e(null,c._container),delete c._container.__vue_app__)},provide(e,t){return i.provides[e]=t,c},runWithContext(e){let t=dC;dC=c;try{return e()}finally{dC=t}}};return c}}var dC=null;function fC(e,t,n=U){let r=Yw(),i=Sy(t),a=wy(t),o=pC(e,i),s=Iv((o,s)=>{let c,l=U,u;return zb(()=>{let t=e[i];Dy(c,t)&&(c=t,s())}),{get(){return o(),n.get?n.get(c):c},set(e){let o=n.set?n.set(e):e;if(!Dy(o,c)&&!(l!==U&&Dy(e,l)))return;let d=r.vnode.props;d&&(t in d||i in d||a in d)&&(`onUpdate:${t}`in d||`onUpdate:${i}`in d||`onUpdate:${a}`in d)||(c=e,s()),r.emit(`update:${t}`,o),Dy(e,o)&&Dy(e,l)&&!Dy(o,u)&&s(),l=e,u=o}}});return s[Symbol.iterator]=()=>{let e=0;return{next(){return e<2?{value:e++?o||U:s,done:!1}:{done:!0}}}},s}var pC=(e,t)=>t===`modelValue`||t===`model-value`?e.modelModifiers:e[`${t}Modifiers`]||e[`${Sy(t)}Modifiers`]||e[`${wy(t)}Modifiers`];function mC(e,t,...n){if(e.isUnmounted)return;let r=e.vnode.props||U,i=n,a=t.startsWith(`update:`),o=a&&pC(r,t.slice(7));o&&(o.trim&&(i=n.map(e=>fy(e)?e.trim():e)),o.number&&(i=n.map(Ay)));let s,c=r[s=Ey(t)]||r[s=Ey(Sy(t))];!c&&a&&(c=r[s=Ey(wy(t))]),c&&tb(c,e,6,i);let l=r[s+`Once`];if(l){if(!e.emitted)e.emitted={};else if(e.emitted[s])return;e.emitted[s]=!0,tb(l,e,6,i)}}var hC=new WeakMap;function gC(e,t,n=!1){let r=n?hC:t.emitsCache,i=r.get(e);if(i!==void 0)return i;let a=e.emits,o={},s=!1;if(!K(e)){let r=e=>{let n=gC(e,t,!0);n&&(s=!0,ay(o,n))};!n&&t.mixins.length&&t.mixins.forEach(r),e.extends&&r(e.extends),e.mixins&&e.mixins.forEach(r)}return!a&&!s?(my(e)&&r.set(e,null),null):(G(a)?a.forEach(e=>o[e]=null):ay(o,a),my(e)&&r.set(e,o),o)}function _C(e,t){return!e||!ry(t)?!1:(t=t.slice(2).replace(/Once$/,``),W(e,t[0].toLowerCase()+t.slice(1))||W(e,wy(t))||W(e,t))}function vC(e){let{type:t,vnode:n,proxy:r,withProxy:i,propsOptions:[a],slots:o,attrs:s,emit:c,render:l,renderCache:u,props:d,data:f,setupState:p,ctx:m,inheritAttrs:h}=e,g=Tb(e),_,v;try{if(n.shapeFlag&4){let e=i||r,t=e;_=Bw(l.call(t,e,u,d,p,f,m)),v=s}else{let e=t;_=Bw(e.length>1?e(d,{attrs:s,slots:o,emit:c}):e(d,null)),v=t.props?s:bC(s)}}catch(t){yw.length=0,nb(t,e,1),_=q(_w)}let y=_;if(v&&h!==!1){let e=Object.keys(v),{shapeFlag:t}=y;e.length&&t&7&&(a&&e.some(iy)&&(v=xC(v,a)),y=Iw(y,v,!1,!0))}return n.dirs&&(y=Iw(y,null,!1,!0),y.dirs=y.dirs?y.dirs.concat(n.dirs):n.dirs),n.transition&&gx(y,n.transition),_=y,Tb(g),_}function yC(e,t=!0){let n;for(let t=0;t<e.length;t++){let r=e[t];if(Ow(r)){if(r.type!==_w||r.children===`v-if`){if(n)return;n=r}}else return}return n}var bC=e=>{let t;for(let n in e)(n===`class`||n===`style`||ry(n))&&((t||={})[n]=e[n]);return t},xC=(e,t)=>{let n={};for(let r in e)(!iy(r)||!(r.slice(9)in t))&&(n[r]=e[r]);return n};function SC(e,t,n){let{props:r,children:i,component:a}=e,{props:o,children:s,patchFlag:c}=t,l=a.emitsOptions;if(t.dirs||t.transition)return!0;if(n&&c>=0){if(c&1024)return!0;if(c&16)return r?CC(r,o,l):!!o;if(c&8){let e=t.dynamicProps;for(let t=0;t<e.length;t++){let n=e[t];if(wC(o,r,n)&&!_C(l,n))return!0}}}else return(i||s)&&(!s||!s.$stable)?!0:r===o?!1:r?o?CC(r,o,l):!0:!!o;return!1}function CC(e,t,n){let r=Object.keys(t);if(r.length!==Object.keys(e).length)return!0;for(let i=0;i<r.length;i++){let a=r[i];if(wC(t,e,a)&&!_C(n,a))return!0}return!1}function wC(e,t,n){let r=e[n],i=t[n];return n===`style`&&my(r)&&my(i)?!Uy(r,i):r!==i}function TC({vnode:e,parent:t,suspense:n},r){for(;t;){let n=t.subTree;if(n.suspense&&n.suspense.activeBranch===e&&(n.suspense.vnode.el=n.el=r,e=n),n===e)(e=t.vnode).el=r,t=t.parent;else break}n&&n.activeBranch===e&&(n.vnode.el=r)}var EC={},DC=()=>Object.create(EC),OC=e=>Object.getPrototypeOf(e)===EC;function kC(e,t,n,r=!1){let i={},a=DC();e.propsDefaults=Object.create(null),jC(e,t,i,a);for(let t in e.propsOptions[0])t in i||(i[t]=void 0);n?e.props=r?i:mv(i):e.type.props?e.props=i:e.props=a,e.attrs=a}function AC(e,t,n,r){let{props:i,attrs:a,vnode:{patchFlag:o}}=e,s=H(i),[c]=e.propsOptions,l=!1;if((r||o>0)&&!(o&16)){if(o&8){let n=e.vnode.dynamicProps;for(let r=0;r<n.length;r++){let o=n[r];if(_C(e.emitsOptions,o))continue;let u=t[o];if(c)if(W(a,o))u!==a[o]&&(a[o]=u,l=!0);else{let t=Sy(o);i[t]=MC(c,s,t,u,e,!1)}else u!==a[o]&&(a[o]=u,l=!0)}}}else{jC(e,t,i,a)&&(l=!0);let r;for(let a in s)(!t||!W(t,a)&&((r=wy(a))===a||!W(t,r)))&&(c?n&&(n[a]!==void 0||n[r]!==void 0)&&(i[a]=MC(c,s,a,void 0,e,!0)):delete i[a]);if(a!==s)for(let e in a)(!t||!W(t,e))&&(delete a[e],l=!0)}l&&k_(e.attrs,`set`,``)}function jC(e,t,n,r){let[i,a]=e.propsOptions,o=!1,s;if(t)for(let c in t){if(yy(c))continue;let l=t[c],u;i&&W(i,u=Sy(c))?!a||!a.includes(u)?n[u]=l:(s||={})[u]=l:_C(e.emitsOptions,c)||(!(c in r)||l!==r[c])&&(r[c]=l,o=!0)}if(a){let t=H(n),r=s||U;for(let o=0;o<a.length;o++){let s=a[o];n[s]=MC(i,t,s,r[s],e,!W(r,s))}}return o}function MC(e,t,n,r,i,a){let o=e[n];if(o!=null){let e=W(o,`default`);if(e&&r===void 0){let e=o.default;if(o.type!==Function&&!o.skipFactory&&K(e)){let{propsDefaults:a}=i;if(n in a)r=a[n];else{let o=Qw(i);r=a[n]=e.call(null,t),o()}}else r=e;i.ce&&i.ce._setProp(n,r)}o[0]&&(a&&!e?r=!1:o[1]&&(r===``||r===wy(n))&&(r=!0))}return r}var NC=new WeakMap;function PC(e,t,n=!1){let r=n?NC:t.propsCache,i=r.get(e);if(i)return i;let a=e.props,o={},s=[],c=!1;if(!K(e)){let r=e=>{c=!0;let[n,r]=PC(e,t,!0);ay(o,n),r&&s.push(...r)};!n&&t.mixins.length&&t.mixins.forEach(r),e.extends&&r(e.extends),e.mixins&&e.mixins.forEach(r)}if(!a&&!c)return my(e)&&r.set(e,ey),ey;if(G(a))for(let e=0;e<a.length;e++){let t=Sy(a[e]);FC(t)&&(o[t]=U)}else if(a)for(let e in a){let t=Sy(e);if(FC(t)){let n=a[e],r=o[t]=G(n)||K(n)?{type:n}:ay({},n),i=r.type,c=!1,l=!0;if(G(i))for(let e=0;e<i.length;++e){let t=i[e],n=K(t)&&t.name;if(n===`Boolean`){c=!0;break}else n===`String`&&(l=!1)}else c=K(i)&&i.name===`Boolean`;r[0]=c,r[1]=l,(c||W(r,`default`))&&s.push(t)}}let l=[o,s];return my(e)&&r.set(e,l),l}function FC(e){return e[0]!==`$`&&!yy(e)}var IC=e=>e===`_`||e===`_ctx`||e===`$stable`,LC=e=>G(e)?e.map(Bw):[Bw(e)],RC=(e,t,n)=>{if(t._n)return t;let r=kb((...e)=>LC(t(...e)),n);return r._c=!1,r},zC=(e,t,n)=>{let r=e._ctx;for(let n in e){if(IC(n))continue;let i=e[n];if(K(i))t[n]=RC(n,i,r);else if(i!=null){let e=LC(i);t[n]=()=>e}}},BC=(e,t)=>{let n=LC(t);e.slots.default=()=>n},VC=(e,t,n)=>{for(let r in t)(n||!IC(r))&&(e[r]=t[r])},HC=(e,t,n)=>{let r=e.slots=DC();if(e.vnode.shapeFlag&32){let e=t._;e?(VC(r,t,n),n&&ky(r,`_`,e,!0)):zC(t,r)}else t&&BC(e,t)},UC=(e,t,n)=>{let{vnode:r,slots:i}=e,a=!0,o=U;if(r.shapeFlag&32){let e=t._;e?n&&e===1?a=!1:VC(i,t,n):(a=!t.$stable,zC(t,i)),o=t}else t&&(BC(e,t),o={default:1});if(a)for(let e in i)!IC(e)&&o[e]==null&&delete i[e]},WC=fw;function GC(e){return qC(e)}function KC(e){return qC(e,Mx)}function qC(e,t){let n=Ny();n.__VUE__=!0;let{insert:r,remove:i,patchProp:a,createElement:o,createText:s,createComment:c,setText:l,setElementText:u,parentNode:d,nextSibling:f,setScopeId:p=ty,insertStaticContent:m}=e,h=(e,t,n,r=null,i=null,a=null,o=void 0,s=null,c=!!t.dynamicChildren)=>{if(e===t)return;e&&!kw(e,t)&&(r=me(e),ue(e,i,a,!0),e=null),t.patchFlag===-2&&(c=!1,t.dynamicChildren=null);let{type:l,ref:u,shapeFlag:d}=t;switch(l){case gw:g(e,t,n,r);break;case _w:_(e,t,n,r);break;case vw:e??v(t,n,r,o);break;case hw:ee(e,t,n,r,i,a,o,s,c);break;default:d&1?x(e,t,n,r,i,a,o,s,c):d&6?te(e,t,n,r,i,a,o,s,c):(d&64||d&128)&&l.process(e,t,n,r,i,a,o,s,c,_e)}u!=null&&i?wx(u,e&&e.ref,a,t||e,!t):u==null&&e&&e.ref!=null&&wx(e.ref,null,a,e,!0)},g=(e,t,n,i)=>{if(e==null)r(t.el=s(t.children),n,i);else{let n=t.el=e.el;t.children!==e.children&&l(n,t.children)}},_=(e,t,n,i)=>{e==null?r(t.el=c(t.children||``),n,i):t.el=e.el},v=(e,t,n,r)=>{[e.el,e.anchor]=m(e.children,t,n,r,e.el,e.anchor)},y=({el:e,anchor:t},n,i)=>{let a;for(;e&&e!==t;)a=f(e),r(e,n,i),e=a;r(t,n,i)},b=({el:e,anchor:t})=>{let n;for(;e&&e!==t;)n=f(e),i(e),e=n;i(t)},x=(e,t,n,r,i,a,o,s,c)=>{if(t.type===`svg`?o=`svg`:t.type===`math`&&(o=`mathml`),e==null)S(t,n,r,i,a,o,s,c);else{let n=e.el&&e.el._isVueCE?e.el:null;try{n&&n._beginPatch(),T(e,t,i,a,o,s,c)}finally{n&&n._endPatch()}}},S=(e,t,n,i,s,c,l,d)=>{let f,p,{props:m,shapeFlag:h,transition:g,dirs:_}=e;if(f=e.el=o(e.type,c,m&&m.is,m),h&8?u(f,e.children):h&16&&w(e.children,f,null,i,s,JC(e,c),l,d),_&&jb(e,null,i,`created`),C(f,e,e.scopeId,l,i),m){for(let e in m)e!==`value`&&!yy(e)&&a(f,e,null,m[e],c,i);`value`in m&&a(f,`value`,null,m.value,c),(p=m.onVnodeBeforeMount)&&Ww(p,i,e)}_&&jb(e,null,i,`beforeMount`);let v=XC(s,g);v&&g.beforeEnter(f),r(f,t,n),((p=m&&m.onVnodeMounted)||v||_)&&WC(()=>{try{p&&Ww(p,i,e),v&&g.enter(f),_&&jb(e,null,i,`mounted`)}finally{}},s)},C=(e,t,n,r,i)=>{if(n&&p(e,n),r)for(let t=0;t<r.length;t++)p(e,r[t]);if(i){let n=i.subTree;if(t===n||nw(n.type)&&(n.ssContent===t||n.ssFallback===t)){let t=i.vnode;C(e,t,t.scopeId,t.slotScopeIds,i.parent)}}},w=(e,t,n,r,i,a,o,s,c=0)=>{for(let l=c;l<e.length;l++)h(null,e[l]=s?Vw(e[l]):Bw(e[l]),t,n,r,i,a,o,s)},T=(e,t,n,r,i,o,s)=>{let c=t.el=e.el,{patchFlag:l,dynamicChildren:d,dirs:f}=t;l|=e.patchFlag&16;let p=e.props||U,m=t.props||U,h;if(n&&YC(n,!1),(h=m.onVnodeBeforeUpdate)&&Ww(h,n,t,e),f&&jb(t,e,n,`beforeUpdate`),n&&YC(n,!0),(p.innerHTML&&m.innerHTML==null||p.textContent&&m.textContent==null)&&u(c,``),d?E(e.dynamicChildren,d,c,n,r,JC(t,i),o):s||oe(e,t,c,null,n,r,JC(t,i),o,!1),l>0){if(l&16)D(c,p,m,n,i);else if(l&2&&p.class!==m.class&&a(c,`class`,null,m.class,i),l&4&&a(c,`style`,p.style,m.style,i),l&8){let e=t.dynamicProps;for(let t=0;t<e.length;t++){let r=e[t],o=p[r],s=m[r];(s!==o||r===`value`)&&a(c,r,o,s,i,n)}}l&1&&e.children!==t.children&&u(c,t.children)}else !s&&d==null&&D(c,p,m,n,i);((h=m.onVnodeUpdated)||f)&&WC(()=>{h&&Ww(h,n,t,e),f&&jb(t,e,n,`updated`)},r)},E=(e,t,n,r,i,a,o)=>{for(let s=0;s<t.length;s++){let c=e[s],l=t[s];h(c,l,c.el&&(c.type===hw||!kw(c,l)||c.shapeFlag&198)?d(c.el):n,null,r,i,a,o,!0)}},D=(e,t,n,r,i)=>{if(t!==n){if(t!==U)for(let o in t)!yy(o)&&!(o in n)&&a(e,o,t[o],null,i,r);for(let o in n){if(yy(o))continue;let s=n[o],c=t[o];s!==c&&o!==`value`&&a(e,o,c,s,i,r)}`value`in n&&a(e,`value`,t.value,n.value,i)}},ee=(e,t,n,i,a,o,c,l,u)=>{let d=t.el=e?e.el:s(``),f=t.anchor=e?e.anchor:s(``),{patchFlag:p,dynamicChildren:m,slotScopeIds:h}=t;h&&(l=l?l.concat(h):h),e==null?(r(d,n,i),r(f,n,i),w(t.children||[],n,f,a,o,c,l,u)):p>0&&p&64&&m&&e.dynamicChildren&&e.dynamicChildren.length===m.length?(E(e.dynamicChildren,m,n,a,o,c,l),(t.key!=null||a&&t===a.subTree)&&ZC(e,t,!0)):oe(e,t,n,f,a,o,c,l,u)},te=(e,t,n,r,i,a,o,s,c)=>{t.slotScopeIds=s,e==null?t.shapeFlag&512?i.ctx.activate(t,n,r,o,c):ne(t,n,r,i,a,o,c):re(e,t,c)},ne=(e,t,n,r,i,a,o)=>{let s=e.component=qw(e,r,i);if(qx(e)&&(s.ctx.renderer=_e),nT(s,!1,o),s.asyncDep){if(i&&i.registerDep(s,ie,o),!e.el){let r=s.subTree=q(_w);_(null,r,t,n),e.placeholder=r.el}}else ie(s,e,t,n,i,a,o)},re=(e,t,n)=>{let r=t.component=e.component;if(SC(e,t,n))if(r.asyncDep&&!r.asyncResolved){ae(r,t,n);return}else r.next=t,r.update();else t.el=e.el,r.vnode=t},ie=(e,t,n,r,i,a,o)=>{let s=()=>{if(e.isMounted){let{next:t,bu:n,u:r,parent:s,vnode:c}=e;{let n=$C(e);if(n){t&&(t.el=c.el,ae(e,t,o)),n.asyncDep.then(()=>{WC(()=>{e.isUnmounted||l()},i)});return}}let u=t,f;YC(e,!1),t?(t.el=c.el,ae(e,t,o)):t=c,n&&Oy(n),(f=t.props&&t.props.onVnodeBeforeUpdate)&&Ww(f,s,t,c),YC(e,!0);let p=vC(e),m=e.subTree;e.subTree=p,h(m,p,d(m.el),me(m),e,i,a),t.el=p.el,u===null&&TC(e,p.el),r&&WC(r,i),(f=t.props&&t.props.onVnodeUpdated)&&WC(()=>Ww(f,s,t,c),i)}else{let o,{el:s,props:c}=t,{bm:l,m:u,parent:d,root:f,type:p}=e,m=Wx(t);if(YC(e,!1),l&&Oy(l),!m&&(o=c&&c.onVnodeBeforeMount)&&Ww(o,d,t),YC(e,!0),s&&ye){let t=()=>{e.subTree=vC(e),ye(s,e.subTree,e,i,null)};m&&p.__asyncHydrate?p.__asyncHydrate(s,e,t):t()}else{f.ce&&f.ce._hasShadowRoot()&&f.ce._injectChildStyle(p,e.parent?e.parent.type:void 0);let o=e.subTree=vC(e);h(null,o,n,r,e,i,a),t.el=o.el}if(u&&WC(u,i),!m&&(o=c&&c.onVnodeMounted)){let e=t;WC(()=>Ww(o,d,e),i)}(t.shapeFlag&256||d&&Wx(d.vnode)&&d.vnode.shapeFlag&256)&&e.a&&WC(e.a,i),e.isMounted=!0,t=n=r=null}};e.scope.on();let c=e.effect=new e_(s);e.scope.off();let l=e.update=c.run.bind(c),u=e.job=c.runIfDirty.bind(c);u.i=e,u.id=e.uid,c.scheduler=()=>pb(u),YC(e,!0),l()},ae=(e,t,n)=>{t.component=e;let r=e.vnode.props;e.vnode=t,e.next=null,AC(e,t.props,r,n),UC(e,t.children,n),__(),gb(e),v_()},oe=(e,t,n,r,i,a,o,s,c=!1)=>{let l=e&&e.children,d=e?e.shapeFlag:0,f=t.children,{patchFlag:p,shapeFlag:m}=t;if(p>0){if(p&128){ce(l,f,n,r,i,a,o,s,c);return}else if(p&256){se(l,f,n,r,i,a,o,s,c);return}}m&8?(d&16&&pe(l,i,a),f!==l&&u(n,f)):d&16?m&16?ce(l,f,n,r,i,a,o,s,c):pe(l,i,a,!0):(d&8&&u(n,``),m&16&&w(f,n,r,i,a,o,s,c))},se=(e,t,n,r,i,a,o,s,c)=>{e||=ey,t||=ey;let l=e.length,u=t.length,d=Math.min(l,u),f;for(f=0;f<d;f++){let r=t[f]=c?Vw(t[f]):Bw(t[f]);h(e[f],r,n,null,i,a,o,s,c)}l>u?pe(e,i,a,!0,!1,d):w(t,n,r,i,a,o,s,c,d)},ce=(e,t,n,r,i,a,o,s,c)=>{let l=0,u=t.length,d=e.length-1,f=u-1;for(;l<=d&&l<=f;){let r=e[l],u=t[l]=c?Vw(t[l]):Bw(t[l]);if(kw(r,u))h(r,u,n,null,i,a,o,s,c);else break;l++}for(;l<=d&&l<=f;){let r=e[d],l=t[f]=c?Vw(t[f]):Bw(t[f]);if(kw(r,l))h(r,l,n,null,i,a,o,s,c);else break;d--,f--}if(l>d){if(l<=f){let e=f+1,d=e<u?t[e].el:r;for(;l<=f;)h(null,t[l]=c?Vw(t[l]):Bw(t[l]),n,d,i,a,o,s,c),l++}}else if(l>f)for(;l<=d;)ue(e[l],i,a,!0),l++;else{let p=l,m=l,g=new Map;for(l=m;l<=f;l++){let e=t[l]=c?Vw(t[l]):Bw(t[l]);e.key!=null&&g.set(e.key,l)}let _,v=0,y=f-m+1,b=!1,x=0,S=Array(y);for(l=0;l<y;l++)S[l]=0;for(l=p;l<=d;l++){let r=e[l];if(v>=y){ue(r,i,a,!0);continue}let u;if(r.key!=null)u=g.get(r.key);else for(_=m;_<=f;_++)if(S[_-m]===0&&kw(r,t[_])){u=_;break}u===void 0?ue(r,i,a,!0):(S[u-m]=l+1,u>=x?x=u:b=!0,h(r,t[u],n,null,i,a,o,s,c),v++)}let C=b?QC(S):ey;for(_=C.length-1,l=y-1;l>=0;l--){let e=m+l,d=t[e],f=t[e+1],p=e+1<u?f.el||tw(f):r;S[l]===0?h(null,d,n,p,i,a,o,s,c):b&&(_<0||l!==C[_]?le(d,n,p,2):_--)}}},le=(e,t,n,a,o=null)=>{let{el:s,type:c,transition:l,children:u,shapeFlag:d}=e;if(d&6){le(e.component.subTree,t,n,a);return}if(d&128){e.suspense.move(t,n,a);return}if(d&64){c.move(e,t,n,_e);return}if(c===hw){r(s,t,n);for(let e=0;e<u.length;e++)le(u[e],t,n,a);r(e.anchor,t,n);return}if(c===vw){y(e,t,n);return}if(a!==2&&d&1&&l)if(a===0)l.beforeEnter(s),r(s,t,n),WC(()=>l.enter(s),o);else{let{leave:a,delayLeave:o,afterLeave:c}=l,u=()=>{e.ctx.isUnmounted?i(s):r(s,t,n)},d=()=>{s._isLeaving&&s[rx](!0),a(s,()=>{u(),c&&c()})};o?o(s,u,d):d()}else r(s,t,n)},ue=(e,t,n,r=!1,i=!1)=>{let{type:a,props:o,ref:s,children:c,dynamicChildren:l,shapeFlag:u,patchFlag:d,dirs:f,cacheIndex:p,memo:m}=e;if(d===-2&&(i=!1),s!=null&&(__(),wx(s,null,n,e,!0),v_()),p!=null&&(t.renderCache[p]=void 0),u&256){t.ctx.deactivate(e);return}let h=u&1&&f,g=!Wx(e),_;if(g&&(_=o&&o.onVnodeBeforeUnmount)&&Ww(_,t,e),u&6)O(e.component,n,r);else{if(u&128){e.suspense.unmount(n,r);return}h&&jb(e,null,t,`beforeUnmount`),u&64?e.type.remove(e,t,n,_e,r):l&&!l.hasOnce&&(a!==hw||d>0&&d&64)?pe(l,t,n,!1,!0):(a===hw&&d&384||!i&&u&16)&&pe(c,t,n),r&&de(e)}let v=m!=null&&p==null;(g&&(_=o&&o.onVnodeUnmounted)||h||v)&&WC(()=>{_&&Ww(_,t,e),h&&jb(e,null,t,`unmounted`),v&&(e.el=null)},n)},de=e=>{let{type:t,el:n,anchor:r,transition:a}=e;if(t===hw){fe(n,r);return}if(t===vw){b(e);return}let o=()=>{i(n),a&&!a.persisted&&a.afterLeave&&a.afterLeave()};if(e.shapeFlag&1&&a&&!a.persisted){let{leave:t,delayLeave:r}=a,i=()=>t(n,o);r?r(e.el,o,i):i()}else o()},fe=(e,t)=>{let n;for(;e!==t;)n=f(e),i(e),e=n;i(t)},O=(e,t,n)=>{let{bum:r,scope:i,job:a,subTree:o,um:s,m:c,a:l}=e;ew(c),ew(l),r&&Oy(r),i.stop(),a&&(a.flags|=8,ue(o,e,t,n)),s&&WC(s,t),WC(()=>{e.isUnmounted=!0},t)},pe=(e,t,n,r=!1,i=!1,a=0)=>{for(let o=a;o<e.length;o++)ue(e[o],t,n,r,i)},me=e=>{if(e.shapeFlag&6)return me(e.component.subTree);if(e.shapeFlag&128)return e.suspense.next();let t=f(e.anchor||e.el),n=t&&t[Wb];return n?f(n):t},he=!1,ge=(e,t,n)=>{let r;e==null?t._vnode&&(ue(t._vnode,null,null,!0),r=t._vnode.component):h(t._vnode||null,e,t,null,null,null,n),t._vnode=e,he||=(he=!0,gb(r),_b(),!1)},_e={p:h,um:ue,m:le,r:de,mt:ne,mc:w,pc:oe,pbc:E,n:me,o:e},ve,ye;return t&&([ve,ye]=t(_e)),{render:ge,hydrate:ve,createApp:uC(ge,ve)}}function JC({type:e,props:t},n){return n===`svg`&&e===`foreignObject`||n===`mathml`&&e===`annotation-xml`&&t&&t.encoding&&t.encoding.includes(`html`)?void 0:n}function YC({effect:e,job:t},n){n?(e.flags|=32,t.flags|=4):(e.flags&=-33,t.flags&=-5)}function XC(e,t){return(!e||e&&!e.pendingBranch)&&t&&!t.persisted}function ZC(e,t,n=!1){let r=e.children,i=t.children;if(G(r)&&G(i))for(let e=0;e<r.length;e++){let t=r[e],a=i[e];a.shapeFlag&1&&!a.dynamicChildren&&((a.patchFlag<=0||a.patchFlag===32)&&(a=i[e]=Vw(i[e]),a.el=t.el),!n&&a.patchFlag!==-2&&ZC(t,a)),a.type===gw&&(a.patchFlag===-1&&(a=i[e]=Vw(a)),a.el=t.el),a.type===_w&&!a.el&&(a.el=t.el)}}function QC(e){let t=e.slice(),n=[0],r,i,a,o,s,c=e.length;for(r=0;r<c;r++){let c=e[r];if(c!==0){if(i=n[n.length-1],e[i]<c){t[r]=i,n.push(r);continue}for(a=0,o=n.length-1;a<o;)s=a+o>>1,e[n[s]]<c?a=s+1:o=s;c<e[n[a]]&&(a>0&&(t[r]=n[a-1]),n[a]=r)}}for(a=n.length,o=n[a-1];a-- >0;)n[a]=o,o=t[o];return n}function $C(e){let t=e.subTree.component;if(t)return t.asyncDep&&!t.asyncResolved?t:$C(t)}function ew(e){if(e)for(let t=0;t<e.length;t++)e[t].flags|=8}function tw(e){if(e.placeholder)return e.placeholder;let t=e.component;return t?tw(t.subTree):null}var nw=e=>e.__isSuspense,rw=0,iw={name:`Suspense`,__isSuspense:!0,process(e,t,n,r,i,a,o,s,c,l){if(e==null)ow(t,n,r,i,a,o,s,c,l);else{if(a&&a.deps>0&&!e.suspense.isInFallback){t.suspense=e.suspense,t.suspense.vnode=t,t.el=e.el;return}sw(e,t,n,r,i,o,s,c,l)}},hydrate:lw,normalize:uw};function aw(e,t){let n=e.props&&e.props[t];K(n)&&n()}function ow(e,t,n,r,i,a,o,s,c){let{p:l,o:{createElement:u}}=c,d=u(`div`),f=e.suspense=cw(e,i,r,t,d,n,a,o,s,c);l(null,f.pendingBranch=e.ssContent,d,null,r,f,a,o),f.deps>0?(aw(e,`onPending`),aw(e,`onFallback`),l(null,e.ssFallback,t,n,r,null,a,o),pw(f,e.ssFallback)):f.resolve(!1,!0)}function sw(e,t,n,r,i,a,o,s,{p:c,um:l,o:{createElement:u}}){let d=t.suspense=e.suspense;d.vnode=t,t.el=e.el;let f=t.ssContent,p=t.ssFallback,{activeBranch:m,pendingBranch:h,isInFallback:g,isHydrating:_}=d;if(h)d.pendingBranch=f,kw(h,f)?(c(h,f,d.hiddenContainer,null,i,d,a,o,s),d.deps<=0?d.resolve():g&&(_||(c(m,p,n,r,i,null,a,o,s),pw(d,p)))):(d.pendingId=rw++,_?(d.isHydrating=!1,d.activeBranch=h):l(h,i,d),d.deps=0,d.effects.length=0,d.hiddenContainer=u(`div`),g?(c(null,f,d.hiddenContainer,null,i,d,a,o,s),d.deps<=0?d.resolve():(c(m,p,n,r,i,null,a,o,s),pw(d,p))):m&&kw(m,f)?(c(m,f,n,r,i,d,a,o,s),d.resolve(!0)):(c(null,f,d.hiddenContainer,null,i,d,a,o,s),d.deps<=0&&d.resolve()));else if(m&&kw(m,f))c(m,f,n,r,i,d,a,o,s),pw(d,f);else if(aw(t,`onPending`),d.pendingBranch=f,f.shapeFlag&512?d.pendingId=f.component.suspenseId:d.pendingId=rw++,c(null,f,d.hiddenContainer,null,i,d,a,o,s),d.deps<=0)d.resolve();else{let{timeout:e,pendingId:t}=d;e>0?setTimeout(()=>{d.pendingId===t&&d.fallback(p)},e):e===0&&d.fallback(p)}}function cw(e,t,n,r,i,a,o,s,c,l,u=!1){let{p:d,m:f,um:p,n:m,o:{parentNode:h,remove:g}}=l,_,v=mw(e);v&&t&&t.pendingBranch&&(_=t.pendingId,t.deps++);let y=e.props?jy(e.props.timeout):void 0,b=a,x={vnode:e,parent:t,parentComponent:n,namespace:o,container:r,hiddenContainer:i,deps:0,pendingId:rw++,timeout:typeof y==`number`?y:-1,activeBranch:null,isFallbackMountPending:!1,pendingBranch:null,isInFallback:!u,isHydrating:u,isUnmounted:!1,effects:[],resolve(e=!1,n=!1){let{vnode:r,activeBranch:i,pendingBranch:o,pendingId:s,effects:c,parentComponent:l,container:u,isInFallback:d}=x,g=!1;x.isHydrating?x.isHydrating=!1:e||(g=i&&o.transition&&o.transition.mode===`out-in`,g&&(i.transition.afterLeave=()=>{s===x.pendingId&&(f(o,u,a===b?m(i):a,0),hb(c),d&&r.ssFallback&&(r.ssFallback.el=null))}),i&&!x.isFallbackMountPending&&(h(i.el)===u&&(a=m(i)),p(i,l,x,!0),!g&&d&&r.ssFallback&&WC(()=>r.ssFallback.el=null,x)),g||f(o,u,a,0)),x.isFallbackMountPending=!1,pw(x,o),x.pendingBranch=null,x.isInFallback=!1;let y=x.parent,S=!1;for(;y;){if(y.pendingBranch){y.effects.push(...c),S=!0;break}y=y.parent}!S&&!g&&hb(c),x.effects=[],v&&t&&t.pendingBranch&&_===t.pendingId&&(t.deps--,t.deps===0&&!n&&t.resolve()),aw(r,`onResolve`)},fallback(e){if(!x.pendingBranch)return;let{vnode:t,activeBranch:n,parentComponent:r,container:i,namespace:a}=x;aw(t,`onFallback`);let o=m(n),l=()=>{x.isFallbackMountPending=!1,x.isInFallback&&(d(null,e,i,o,r,null,a,s,c),pw(x,e))},u=e.transition&&e.transition.mode===`out-in`;u&&(x.isFallbackMountPending=!0,n.transition.afterLeave=l),x.isInFallback=!0,p(n,r,null,!0),u||l()},move(e,t,n){x.activeBranch&&f(x.activeBranch,e,t,n),x.container=e},next(){return x.activeBranch&&m(x.activeBranch)},registerDep(e,t,n){let r=!!x.pendingBranch;r&&x.deps++;let i=e.vnode.el;e.asyncDep.catch(t=>{nb(t,e,0)}).then(a=>{if(e.isUnmounted||x.isUnmounted||x.pendingId!==e.suspenseId)return;e.asyncResolved=!0;let{vnode:s}=e;iT(e,a,!1),i&&(s.el=i);let c=!i&&e.subTree.el;t(e,s,h(i||e.subTree.el),i?null:m(e.subTree),x,o,n),c&&(s.placeholder=null,g(c)),TC(e,s.el),r&&--x.deps===0&&x.resolve()})},unmount(e,t){x.isUnmounted=!0,x.activeBranch&&p(x.activeBranch,n,e,t),x.pendingBranch&&p(x.pendingBranch,n,e,t)}};return x}function lw(e,t,n,r,i,a,o,s,c){let l=t.suspense=cw(t,r,n,e.parentNode,document.createElement(`div`),null,i,a,o,s,!0),u=c(e,l.pendingBranch=t.ssContent,n,l,a,o);return l.deps===0&&l.resolve(!1,!0),u}function uw(e){let{shapeFlag:t,children:n}=e,r=t&32;e.ssContent=dw(r?n.default:n),e.ssFallback=r?dw(n.fallback):q(_w)}function dw(e){let t;if(K(e)){let n=Cw&&e._c;n&&(e._d=!1,xw()),e=e(),n&&(e._d=!0,t=bw,Sw())}return G(e)&&(e=yC(e)),e=Bw(e),t&&!e.dynamicChildren&&(e.dynamicChildren=t.filter(t=>t!==e)),e}function fw(e,t){t&&t.pendingBranch?G(e)?t.effects.push(...e):t.effects.push(e):hb(e)}function pw(e,t){e.activeBranch=t;let{vnode:n,parentComponent:r}=e,i=t.el;for(;!i&&t.component;)t=t.component.subTree,i=t.el;n.el=i,r&&r.subTree===n&&(r.vnode.el=i,TC(r,i))}function mw(e){let t=e.props&&e.props.suspensible;return t!=null&&t!==!1}var hw=Symbol.for(`v-fgt`),gw=Symbol.for(`v-txt`),_w=Symbol.for(`v-cmt`),vw=Symbol.for(`v-stc`),yw=[],bw=null;function xw(e=!1){yw.push(bw=e?null:[])}function Sw(){yw.pop(),bw=yw[yw.length-1]||null}var Cw=1;function ww(e,t=!1){Cw+=e,e<0&&bw&&t&&(bw.hasOnce=!0)}function Tw(e){return e.dynamicChildren=Cw>0?bw||ey:null,Sw(),Cw>0&&bw&&bw.push(e),e}function Ew(e,t,n,r,i,a){return Tw(Nw(e,t,n,r,i,a,!0))}function Dw(e,t,n,r,i){return Tw(q(e,t,n,r,i,!0))}function Ow(e){return e?e.__v_isVNode===!0:!1}function kw(e,t){return e.type===t.type&&e.key===t.key}function Aw(e){}var jw=({key:e})=>e??null,Mw=({ref:e,ref_key:t,ref_for:n})=>(typeof e==`number`&&(e=``+e),e==null?null:fy(e)||Tv(e)||K(e)?{i:Cb,r:e,k:t,f:!!n}:e);function Nw(e,t=null,n=null,r=0,i=null,a=e===hw?0:1,o=!1,s=!1){let c={__v_isVNode:!0,__v_skip:!0,type:e,props:t,key:t&&jw(t),ref:t&&Mw(t),scopeId:wb,slotScopeIds:null,children:n,component:null,suspense:null,ssContent:null,ssFallback:null,dirs:null,transition:null,el:null,anchor:null,target:null,targetStart:null,targetAnchor:null,staticCount:0,shapeFlag:a,patchFlag:r,dynamicProps:i,dynamicChildren:null,appContext:null,ctx:Cb};return s?(Hw(c,n),a&128&&e.normalize(c)):n&&(c.shapeFlag|=fy(n)?8:16),Cw>0&&!o&&bw&&(c.patchFlag>0||a&6)&&c.patchFlag!==32&&bw.push(c),c}var q=Pw;function Pw(e,t=null,n=null,r=0,i=null,a=!1){if((!e||e===_S)&&(e=_w),Ow(e)){let r=Iw(e,t,!0);return n&&Hw(r,n),Cw>0&&!a&&bw&&(r.shapeFlag&6?bw[bw.indexOf(e)]=r:bw.push(r)),r.patchFlag=-2,r}if(mT(e)&&(e=e.__vccOpts),t){t=Fw(t);let{class:e,style:n}=t;e&&!fy(e)&&(t.class=By(e)),my(n)&&(xv(n)&&!G(n)&&(n=ay({},n)),t.style=Fy(n))}let o=fy(e)?1:nw(e)?128:Gb(e)?64:my(e)?4:K(e)?2:0;return Nw(e,t,n,r,i,o,a,!0)}function Fw(e){return e?xv(e)||OC(e)?ay({},e):e:null}function Iw(e,t,n=!1,r=!1){let{props:i,ref:a,patchFlag:o,children:s,transition:c}=e,l=t?Uw(i||{},t):i,u={__v_isVNode:!0,__v_skip:!0,type:e.type,props:l,key:l&&jw(l),ref:t&&t.ref?n&&a?G(a)?a.concat(Mw(t)):[a,Mw(t)]:Mw(t):a,scopeId:e.scopeId,slotScopeIds:e.slotScopeIds,children:s,target:e.target,targetStart:e.targetStart,targetAnchor:e.targetAnchor,staticCount:e.staticCount,shapeFlag:e.shapeFlag,patchFlag:t&&e.type!==hw?o===-1?16:o|16:o,dynamicProps:e.dynamicProps,dynamicChildren:e.dynamicChildren,appContext:e.appContext,dirs:e.dirs,transition:c,component:e.component,suspense:e.suspense,ssContent:e.ssContent&&Iw(e.ssContent),ssFallback:e.ssFallback&&Iw(e.ssFallback),placeholder:e.placeholder,el:e.el,anchor:e.anchor,ctx:e.ctx,ce:e.ce};return c&&r&&gx(u,c.clone(u)),u}function Lw(e=` `,t=0){return q(gw,null,e,t)}function Rw(e,t){let n=q(vw,null,e);return n.staticCount=t,n}function zw(e=``,t=!1){return t?(xw(),Dw(_w,null,e)):q(_w,null,e)}function Bw(e){return e==null||typeof e==`boolean`?q(_w):G(e)?q(hw,null,e.slice()):Ow(e)?Vw(e):q(gw,null,String(e))}function Vw(e){return e.el===null&&e.patchFlag!==-1||e.memo?e:Iw(e)}function Hw(e,t){let n=0,{shapeFlag:r}=e;if(t==null)t=null;else if(G(t))n=16;else if(typeof t==`object`)if(r&65){let n=t.default;n&&(n._c&&(n._d=!1),Hw(e,n()),n._c&&(n._d=!0));return}else{n=32;let r=t._;!r&&!OC(t)?t._ctx=Cb:r===3&&Cb&&(Cb.slots._===1?t._=1:(t._=2,e.patchFlag|=1024))}else K(t)?(t={default:t,_ctx:Cb},n=32):(t=String(t),r&64?(n=16,t=[Lw(t)]):n=8);e.children=t,e.shapeFlag|=n}function Uw(...e){let t={};for(let n=0;n<e.length;n++){let r=e[n];for(let e in r)if(e===`class`)t.class!==r.class&&(t.class=By([t.class,r.class]));else if(e===`style`)t.style=Fy([t.style,r.style]);else if(ry(e)){let n=t[e],i=r[e];i&&n!==i&&!(G(n)&&n.includes(i))?t[e]=n?[].concat(n,i):i:i==null&&n==null&&!iy(e)&&(t[e]=i)}else e!==``&&(t[e]=r[e])}return t}function Ww(e,t,n,r=null){tb(e,t,7,[n,r])}var Gw=cC(),Kw=0;function qw(e,t,n){let r=e.type,i=(t?t.appContext:e.appContext)||Gw,a={uid:Kw++,vnode:e,type:r,parent:t,appContext:i,root:null,next:null,subTree:null,effect:null,update:null,job:null,scope:new Yg(!0),render:null,proxy:null,exposed:null,exposeProxy:null,withProxy:null,provides:t?t.provides:Object.create(i.provides),ids:t?t.ids:[``,0,0],accessCache:null,renderCache:[],components:null,directives:null,propsOptions:PC(r,i),emitsOptions:gC(r,i),emit:null,emitted:null,propsDefaults:U,inheritAttrs:r.inheritAttrs,ctx:U,data:U,props:U,attrs:U,slots:U,refs:U,setupState:U,setupContext:null,suspense:n,suspenseId:n?n.pendingId:0,asyncDep:null,asyncResolved:!1,isMounted:!1,isUnmounted:!1,isDeactivated:!1,bc:null,c:null,bm:null,m:null,bu:null,u:null,um:null,bum:null,da:null,a:null,rtg:null,rtc:null,ec:null,sp:null};return a.ctx={_:a},a.root=t?t.root:a,a.emit=mC.bind(null,a),e.ce&&e.ce(a),a}var Jw=null,Yw=()=>Jw||Cb,Xw,Zw;{let e=Ny(),t=(t,n)=>{let r;return(r=e[t])||(r=e[t]=[]),r.push(n),e=>{r.length>1?r.forEach(t=>t(e)):r[0](e)}};Xw=t(`__VUE_INSTANCE_SETTERS__`,e=>Jw=e),Zw=t(`__VUE_SSR_SETTERS__`,e=>tT=e)}var Qw=e=>{let t=Jw;return Xw(e),e.scope.on(),()=>{e.scope.off(),Xw(t)}},$w=()=>{Jw&&Jw.scope.off(),Xw(null)};function eT(e){return e.vnode.shapeFlag&4}var tT=!1;function nT(e,t=!1,n=!1){t&&Zw(t);let{props:r,children:i}=e.vnode,a=eT(e);kC(e,r,a,t),HC(e,i,n||t);let o=a?rT(e,t):void 0;return t&&Zw(!1),o}function rT(e,t){let n=e.type;e.accessCache=Object.create(null),e.proxy=new Proxy(e.ctx,AS);let{setup:r}=n;if(r){__();let n=e.setupContext=r.length>1?dT(e):null,i=Qw(e),a=eb(r,e,0,[e.props,n]),o=hy(a);if(v_(),i(),(o||e.sp)&&!Wx(e)&&bx(e),o){if(a.then($w,$w),t)return a.then(n=>{iT(e,n,t)}).catch(t=>{nb(t,e,0)});e.asyncDep=a}else iT(e,a,t)}else lT(e,t)}function iT(e,t,n){K(t)?e.type.__ssrInlineRender?e.ssrRender=t:e.render=t:my(t)&&(e.setupState=Pv(t)),lT(e,n)}var aT,oT;function sT(e){aT=e,oT=e=>{e.render._rc&&(e.withProxy=new Proxy(e.ctx,jS))}}var cT=()=>!aT;function lT(e,t,n){let r=e.type;if(!e.render){if(!t&&aT&&!r.render){let t=r.template||QS(e).template;if(t){let{isCustomElement:n,compilerOptions:i}=e.appContext.config,{delimiters:a,compilerOptions:o}=r,s=ay(ay({isCustomElement:n,delimiters:a},i),o);r.render=aT(t,s)}}e.render=r.render||ty,oT&&oT(e)}{let t=Qw(e);__();try{JS(e)}finally{v_(),t()}}}var uT={get(e,t){return O_(e,`get`,``),e[t]}};function dT(e){return{attrs:new Proxy(e.attrs,uT),slots:e.slots,emit:e.emit,expose:t=>{e.exposed=t||{}}}}function fT(e){return e.exposed?e.exposeProxy||=new Proxy(Pv(Sv(e.exposed)),{get(t,n){if(n in t)return t[n];if(n in OS)return OS[n](e)},has(e,t){return t in e||t in OS}}):e.proxy}function pT(e,t=!0){return K(e)?e.displayName||e.name:e.name||t&&e.__name}function mT(e){return K(e)&&`__vccOpts`in e}var J=(e,t)=>Uv(e,t,tT);function hT(e,t,n){try{ww(-1);let r=arguments.length;return r===2?my(t)&&!G(t)?Ow(t)?q(e,null,[t]):q(e,t):q(e,null,t):(r>3?n=Array.prototype.slice.call(arguments,2):r===3&&Ow(n)&&(n=[n]),q(e,t,n))}finally{ww(1)}}function gT(){return;function e(t,n,r){let i=t[r];if(G(i)&&i.includes(n)||my(i)&&n in i||t.extends&&e(t.extends,n,r)||t.mixins&&t.mixins.some(t=>e(t,n,r)))return!0}}function _T(e,t,n,r){let i=n[r];if(i&&vT(i,e))return i;let a=t();return a.memo=e.slice(),a.cacheIndex=r,n[r]=a}function vT(e,t){let n=e.memo;if(n.length!=t.length)return!1;for(let e=0;e<n.length;e++)if(Dy(n[e],t[e]))return!1;return Cw>0&&bw&&bw.push(e),!0}var yT=`3.5.31`,bT=ty,xT=$y,ST=bb,CT=Sb,wT={createComponentInstance:qw,setupComponent:nT,renderComponentRoot:vC,setCurrentRenderingInstance:Tb,isVNode:Ow,normalizeVNode:Bw,getComponentPublicInstance:fT,ensureValidVNode:TS,pushWarningContext:Yy,popWarningContext:Xy};function TT(e){let t=Object.create(null);for(let n of e.split(`,`))t[n]=1;return e=>e in t}var ET={},DT=()=>{},OT=e=>e.charCodeAt(0)===111&&e.charCodeAt(1)===110&&(e.charCodeAt(2)>122||e.charCodeAt(2)<97),kT=e=>e.startsWith(`onUpdate:`),AT=Object.assign,jT=Object.prototype.hasOwnProperty,MT=(e,t)=>jT.call(e,t),NT=Array.isArray,PT=e=>VT(e)===`[object Set]`,FT=e=>VT(e)===`[object Date]`,IT=e=>typeof e==`function`,LT=e=>typeof e==`string`,RT=e=>typeof e==`symbol`,zT=e=>typeof e==`object`&&!!e,BT=Object.prototype.toString,VT=e=>BT.call(e),HT=e=>VT(e)===`[object Object]`,UT=e=>{let t=Object.create(null);return(n=>t[n]||(t[n]=e(n)))},WT=/-\w/g,GT=UT(e=>e.replace(WT,e=>e.slice(1).toUpperCase())),KT=/\B([A-Z])/g,qT=UT(e=>e.replace(KT,`-$1`).toLowerCase()),JT=UT(e=>e.charAt(0).toUpperCase()+e.slice(1)),YT=(e,...t)=>{for(let n=0;n<e.length;n++)e[n](...t)},XT=e=>{let t=parseFloat(e);return isNaN(t)?e:t},ZT=e=>{let t=LT(e)?Number(e):NaN;return isNaN(t)?e:t},QT=`itemscope,allowfullscreen,formnovalidate,ismap,nomodule,novalidate,readonly`,$T=TT(QT);QT+``;function eE(e){return!!e||e===``}function tE(e,t){if(e.length!==t.length)return!1;let n=!0;for(let r=0;n&&r<e.length;r++)n=nE(e[r],t[r]);return n}function nE(e,t){if(e===t)return!0;let n=FT(e),r=FT(t);if(n||r)return n&&r?e.getTime()===t.getTime():!1;if(n=RT(e),r=RT(t),n||r)return e===t;if(n=NT(e),r=NT(t),n||r)return n&&r?tE(e,t):!1;if(n=zT(e),r=zT(t),n||r){if(!n||!r||Object.keys(e).length!==Object.keys(t).length)return!1;for(let n in e){let r=e.hasOwnProperty(n),i=t.hasOwnProperty(n);if(r&&!i||!r&&i||!nE(e[n],t[n]))return!1}}return String(e)===String(t)}function rE(e,t){return e.findIndex(e=>nE(e,t))}function iE(e){return e==null?`initial`:typeof e==`string`?e===``?` `:e:String(e)}var aE=n({BaseTransition:()=>dx,BaseTransitionPropsValidators:()=>sx,Comment:()=>_w,DeprecationTypes:()=>null,EffectScope:()=>Yg,ErrorCodes:()=>Qy,ErrorTypeStrings:()=>xT,Fragment:()=>hw,KeepAlive:()=>Jx,ReactiveEffect:()=>e_,Static:()=>vw,Suspense:()=>iw,Teleport:()=>ex,Text:()=>gw,TrackOpTypes:()=>Wv,Transition:()=>yE,TransitionGroup:()=>DD,TriggerOpTypes:()=>Gv,VueElement:()=>yD,assertNumber:()=>Zy,callWithAsyncErrorHandling:()=>tb,callWithErrorHandling:()=>eb,camelize:()=>Sy,capitalize:()=>Ty,cloneVNode:()=>Iw,compatUtils:()=>null,computed:()=>J,createApp:()=>cO,createBlock:()=>Dw,createCommentVNode:()=>zw,createElementBlock:()=>Ew,createElementVNode:()=>Nw,createHydrationRenderer:()=>KC,createPropsRestProxy:()=>GS,createRenderer:()=>GC,createSSRApp:()=>lO,createSlots:()=>CS,createStaticVNode:()=>Rw,createTextVNode:()=>Lw,createVNode:()=>q,customRef:()=>Iv,defineAsyncComponent:()=>Gx,defineComponent:()=>vx,defineCustomElement:()=>gD,defineEmits:()=>NS,defineExpose:()=>PS,defineModel:()=>LS,defineOptions:()=>FS,defineProps:()=>MS,defineSSRCustomElement:()=>_D,defineSlots:()=>IS,devtools:()=>ST,effect:()=>p_,effectScope:()=>Xg,getCurrentInstance:()=>Yw,getCurrentScope:()=>Zg,getCurrentWatcher:()=>Yv,getTransitionRawChildren:()=>_x,guardReactiveProps:()=>Fw,h:()=>hT,handleError:()=>nb,hasInjectionContext:()=>Pb,hydrate:()=>sO,hydrateOnIdle:()=>Rx,hydrateOnInteraction:()=>Hx,hydrateOnMediaQuery:()=>Vx,hydrateOnVisible:()=>Bx,initCustomFormatter:()=>gT,initDirectivesForSSR:()=>pO,inject:()=>Nb,isMemoSame:()=>vT,isProxy:()=>xv,isReactive:()=>vv,isReadonly:()=>yv,isRef:()=>Tv,isRuntimeOnly:()=>cT,isShallow:()=>bv,isVNode:()=>Ow,markRaw:()=>Sv,mergeDefaults:()=>US,mergeModels:()=>WS,mergeProps:()=>Uw,nextTick:()=>db,nodeOps:()=>pE,normalizeClass:()=>By,normalizeProps:()=>Vy,normalizeStyle:()=>Fy,onActivated:()=>Xx,onBeforeMount:()=>iS,onBeforeUnmount:()=>cS,onBeforeUpdate:()=>oS,onDeactivated:()=>Zx,onErrorCaptured:()=>pS,onMounted:()=>aS,onRenderTracked:()=>fS,onRenderTriggered:()=>dS,onScopeDispose:()=>Qg,onServerPrefetch:()=>uS,onUnmounted:()=>lS,onUpdated:()=>sS,onWatcherCleanup:()=>Xv,openBlock:()=>xw,patchProp:()=>fD,popScopeId:()=>Db,provide:()=>Mb,proxyRefs:()=>Pv,pushScopeId:()=>Eb,queuePostFlushCb:()=>hb,reactive:()=>pv,readonly:()=>hv,ref:()=>Ev,registerRuntimeCompiler:()=>sT,render:()=>oO,renderList:()=>SS,renderSlot:()=>wS,resolveComponent:()=>gS,resolveDirective:()=>yS,resolveDynamicComponent:()=>vS,resolveFilter:()=>null,resolveTransitionHooks:()=>px,setBlockTracking:()=>ww,setDevtoolsHook:()=>CT,setTransitionHooks:()=>gx,shallowReactive:()=>mv,shallowReadonly:()=>gv,shallowRef:()=>Dv,ssrContextKey:()=>Fb,ssrUtils:()=>wT,stop:()=>m_,toDisplayString:()=>Gy,toHandlerKey:()=>Ey,toHandlers:()=>ES,toRaw:()=>H,toRef:()=>Bv,toRefs:()=>Lv,toValue:()=>Mv,transformVNodeArgs:()=>Aw,triggerRef:()=>Av,unref:()=>jv,useAttrs:()=>BS,useCssModule:()=>SD,useCssVars:()=>VE,useHost:()=>bD,useId:()=>yx,useModel:()=>fC,useSSRContext:()=>Ib,useShadowRoot:()=>xD,useSlots:()=>zS,useTemplateRef:()=>xx,useTransitionState:()=>ax,vModelCheckbox:()=>zD,vModelDynamic:()=>KD,vModelRadio:()=>VD,vModelSelect:()=>HD,vModelText:()=>RD,vShow:()=>LE,version:()=>yT,warn:()=>bT,watch:()=>Bb,watchEffect:()=>Lb,watchPostEffect:()=>Rb,watchSyncEffect:()=>zb,withAsyncContext:()=>KS,withCtx:()=>kb,withDefaults:()=>RS,withDirectives:()=>Ab,withKeys:()=>eO,withMemo:()=>_T,withModifiers:()=>QD,withScopeId:()=>Ob}),oE=void 0,sE=typeof window<`u`&&window.trustedTypes;if(sE)try{oE=sE.createPolicy(`vue`,{createHTML:e=>e})}catch{}var cE=oE?e=>oE.createHTML(e):e=>e,lE=`http://www.w3.org/2000/svg`,uE=`http://www.w3.org/1998/Math/MathML`,dE=typeof document<`u`?document:null,fE=dE&&dE.createElement(`template`),pE={insert:(e,t,n)=>{t.insertBefore(e,n||null)},remove:e=>{let t=e.parentNode;t&&t.removeChild(e)},createElement:(e,t,n,r)=>{let i=t===`svg`?dE.createElementNS(lE,e):t===`mathml`?dE.createElementNS(uE,e):n?dE.createElement(e,{is:n}):dE.createElement(e);return e===`select`&&r&&r.multiple!=null&&i.setAttribute(`multiple`,r.multiple),i},createText:e=>dE.createTextNode(e),createComment:e=>dE.createComment(e),setText:(e,t)=>{e.nodeValue=t},setElementText:(e,t)=>{e.textContent=t},parentNode:e=>e.parentNode,nextSibling:e=>e.nextSibling,querySelector:e=>dE.querySelector(e),setScopeId(e,t){e.setAttribute(t,``)},insertStaticContent(e,t,n,r,i,a){let o=n?n.previousSibling:t.lastChild;if(i&&(i===a||i.nextSibling))for(;t.insertBefore(i.cloneNode(!0),n),!(i===a||!(i=i.nextSibling)););else{fE.innerHTML=cE(r===`svg`?`<svg>${e}</svg>`:r===`mathml`?`<math>${e}</math>`:e);let i=fE.content;if(r===`svg`||r===`mathml`){let e=i.firstChild;for(;e.firstChild;)i.appendChild(e.firstChild);i.removeChild(e)}t.insertBefore(i,n)}return[o?o.nextSibling:t.firstChild,n?n.previousSibling:t.lastChild]}},mE=`transition`,hE=`animation`,gE=Symbol(`_vtc`),_E={name:String,type:String,css:{type:Boolean,default:!0},duration:[String,Number,Object],enterFromClass:String,enterActiveClass:String,enterToClass:String,appearFromClass:String,appearActiveClass:String,appearToClass:String,leaveFromClass:String,leaveActiveClass:String,leaveToClass:String},vE=AT({},sx,_E),yE=(e=>(e.displayName=`Transition`,e.props=vE,e))((e,{slots:t})=>hT(dx,SE(e),t)),bE=(e,t=[])=>{NT(e)?e.forEach(e=>e(...t)):e&&e(...t)},xE=e=>e?NT(e)?e.some(e=>e.length>1):e.length>1:!1;function SE(e){let t={};for(let n in e)n in _E||(t[n]=e[n]);if(e.css===!1)return t;let{name:n=`v`,type:r,duration:i,enterFromClass:a=`${n}-enter-from`,enterActiveClass:o=`${n}-enter-active`,enterToClass:s=`${n}-enter-to`,appearFromClass:c=a,appearActiveClass:l=o,appearToClass:u=s,leaveFromClass:d=`${n}-leave-from`,leaveActiveClass:f=`${n}-leave-active`,leaveToClass:p=`${n}-leave-to`}=e,m=CE(i),h=m&&m[0],g=m&&m[1],{onBeforeEnter:_,onEnter:v,onEnterCancelled:y,onLeave:b,onLeaveCancelled:x,onBeforeAppear:S=_,onAppear:C=v,onAppearCancelled:w=y}=t,T=(e,t,n,r)=>{e._enterCancelled=r,EE(e,t?u:s),EE(e,t?l:o),n&&n()},E=(e,t)=>{e._isLeaving=!1,EE(e,d),EE(e,p),EE(e,f),t&&t()},D=e=>(t,n)=>{let i=e?C:v,o=()=>T(t,e,n);bE(i,[t,o]),DE(()=>{EE(t,e?c:a),TE(t,e?u:s),xE(i)||kE(t,r,h,o)})};return AT(t,{onBeforeEnter(e){bE(_,[e]),TE(e,a),TE(e,o)},onBeforeAppear(e){bE(S,[e]),TE(e,c),TE(e,l)},onEnter:D(!1),onAppear:D(!0),onLeave(e,t){e._isLeaving=!0;let n=()=>E(e,t);TE(e,d),e._enterCancelled?(TE(e,f),NE(e)):(NE(e),TE(e,f)),DE(()=>{e._isLeaving&&(EE(e,d),TE(e,p),xE(b)||kE(e,r,g,n))}),bE(b,[e,n])},onEnterCancelled(e){T(e,!1,void 0,!0),bE(y,[e])},onAppearCancelled(e){T(e,!0,void 0,!0),bE(w,[e])},onLeaveCancelled(e){E(e),bE(x,[e])}})}function CE(e){if(e==null)return null;if(zT(e))return[wE(e.enter),wE(e.leave)];{let t=wE(e);return[t,t]}}function wE(e){return ZT(e)}function TE(e,t){t.split(/\s+/).forEach(t=>t&&e.classList.add(t)),(e[gE]||(e[gE]=new Set)).add(t)}function EE(e,t){t.split(/\s+/).forEach(t=>t&&e.classList.remove(t));let n=e[gE];n&&(n.delete(t),n.size||(e[gE]=void 0))}function DE(e){requestAnimationFrame(()=>{requestAnimationFrame(e)})}var OE=0;function kE(e,t,n,r){let i=e._endId=++OE,a=()=>{i===e._endId&&r()};if(n!=null)return setTimeout(a,n);let{type:o,timeout:s,propCount:c}=AE(e,t);if(!o)return r();let l=o+`end`,u=0,d=()=>{e.removeEventListener(l,f),a()},f=t=>{t.target===e&&++u>=c&&d()};setTimeout(()=>{u<c&&d()},s+1),e.addEventListener(l,f)}function AE(e,t){let n=window.getComputedStyle(e),r=e=>(n[e]||``).split(`, `),i=r(`${mE}Delay`),a=r(`${mE}Duration`),o=jE(i,a),s=r(`${hE}Delay`),c=r(`${hE}Duration`),l=jE(s,c),u=null,d=0,f=0;t===mE?o>0&&(u=mE,d=o,f=a.length):t===hE?l>0&&(u=hE,d=l,f=c.length):(d=Math.max(o,l),u=d>0?o>l?mE:hE:null,f=u?u===mE?a.length:c.length:0);let p=u===mE&&/\b(?:transform|all)(?:,|$)/.test(r(`${mE}Property`).toString());return{type:u,timeout:d,propCount:f,hasTransform:p}}function jE(e,t){for(;e.length<t.length;)e=e.concat(e);return Math.max(...t.map((t,n)=>ME(t)+ME(e[n])))}function ME(e){return e===`auto`?0:Number(e.slice(0,-1).replace(`,`,`.`))*1e3}function NE(e){return(e?e.ownerDocument:document).body.offsetHeight}function PE(e,t,n){let r=e[gE];r&&(t=(t?[t,...r]:[...r]).join(` `)),t==null?e.removeAttribute(`class`):n?e.setAttribute(`class`,t):e.className=t}var FE=Symbol(`_vod`),IE=Symbol(`_vsh`),LE={name:`show`,beforeMount(e,{value:t},{transition:n}){e[FE]=e.style.display===`none`?``:e.style.display,n&&t?n.beforeEnter(e):RE(e,t)},mounted(e,{value:t},{transition:n}){n&&t&&n.enter(e)},updated(e,{value:t,oldValue:n},{transition:r}){!t!=!n&&(r?t?(r.beforeEnter(e),RE(e,!0),r.enter(e)):r.leave(e,()=>{RE(e,!1)}):RE(e,t))},beforeUnmount(e,{value:t}){RE(e,t)}};function RE(e,t){e.style.display=t?e[FE]:`none`,e[IE]=!t}function zE(){LE.getSSRProps=({value:e})=>{if(!e)return{style:{display:`none`}}}}var BE=Symbol(``);function VE(e){let t=Yw();if(!t)return;let n=t.ut=(n=e(t.proxy))=>{Array.from(document.querySelectorAll(`[data-v-owner="${t.uid}"]`)).forEach(e=>UE(e,n))},r=()=>{let r=e(t.proxy);t.ce?UE(t.ce,r):HE(t.subTree,r),n(r)};oS(()=>{hb(r)}),aS(()=>{Bb(r,DT,{flush:`post`});let e=new MutationObserver(r);e.observe(t.subTree.el.parentNode,{childList:!0}),lS(()=>e.disconnect())})}function HE(e,t){if(e.shapeFlag&128){let n=e.suspense;e=n.activeBranch,n.pendingBranch&&!n.isHydrating&&n.effects.push(()=>{HE(n.activeBranch,t)})}for(;e.component;)e=e.component.subTree;if(e.shapeFlag&1&&e.el)UE(e.el,t);else if(e.type===hw)e.children.forEach(e=>HE(e,t));else if(e.type===vw){let{el:n,anchor:r}=e;for(;n&&(UE(n,t),n!==r);)n=n.nextSibling}}function UE(e,t){if(e.nodeType===1){let n=e.style,r=``;for(let e in t){let i=iE(t[e]);n.setProperty(`--${e}`,i),r+=`--${e}: ${i};`}n[BE]=r}}var WE=/(?:^|;)\s*display\s*:/;function GE(e,t,n){let r=e.style,i=LT(n),a=!1;if(n&&!i){if(t)if(LT(t))for(let e of t.split(`;`)){let t=e.slice(0,e.indexOf(`:`)).trim();n[t]??qE(r,t,``)}else for(let e in t)n[e]??qE(r,e,``);for(let e in n)e===`display`&&(a=!0),qE(r,e,n[e])}else if(i){if(t!==n){let e=r[BE];e&&(n+=`;`+e),r.cssText=n,a=WE.test(n)}}else t&&e.removeAttribute(`style`);FE in e&&(e[FE]=a?r.display:``,e[IE]&&(r.display=`none`))}var KE=/\s*!important$/;function qE(e,t,n){if(NT(n))n.forEach(n=>qE(e,t,n));else if(n??=``,t.startsWith(`--`))e.setProperty(t,n);else{let r=XE(e,t);KE.test(n)?e.setProperty(qT(r),n.replace(KE,``),`important`):e[r]=n}}var JE=[`Webkit`,`Moz`,`ms`],YE={};function XE(e,t){let n=YE[t];if(n)return n;let r=Sy(t);if(r!==`filter`&&r in e)return YE[t]=r;r=JT(r);for(let n=0;n<JE.length;n++){let i=JE[n]+r;if(i in e)return YE[t]=i}return t}var ZE=`http://www.w3.org/1999/xlink`;function QE(e,t,n,r,i,a=$T(t)){r&&t.startsWith(`xlink:`)?n==null?e.removeAttributeNS(ZE,t.slice(6,t.length)):e.setAttributeNS(ZE,t,n):n==null||a&&!eE(n)?e.removeAttribute(t):e.setAttribute(t,a?``:RT(n)?String(n):n)}function $E(e,t,n,r,i){if(t===`innerHTML`||t===`textContent`){n!=null&&(e[t]=t===`innerHTML`?cE(n):n);return}let a=e.tagName;if(t===`value`&&a!==`PROGRESS`&&!a.includes(`-`)){let r=a===`OPTION`?e.getAttribute(`value`)||``:e.value,i=n==null?e.type===`checkbox`?`on`:``:String(n);(r!==i||!(`_value`in e))&&(e.value=i),n??e.removeAttribute(t),e._value=n;return}let o=!1;if(n===``||n==null){let r=typeof e[t];r===`boolean`?n=eE(n):n==null&&r===`string`?(n=``,o=!0):r===`number`&&(n=0,o=!0)}try{e[t]=n}catch{}o&&e.removeAttribute(i||t)}function eD(e,t,n,r){e.addEventListener(t,n,r)}function tD(e,t,n,r){e.removeEventListener(t,n,r)}var nD=Symbol(`_vei`);function rD(e,t,n,r,i=null){let a=e[nD]||(e[nD]={}),o=a[t];if(r&&o)o.value=r;else{let[n,s]=aD(t);r?eD(e,n,a[t]=lD(r,i),s):o&&(tD(e,n,o,s),a[t]=void 0)}}var iD=/(?:Once|Passive|Capture)$/;function aD(e){let t;if(iD.test(e)){t={};let n;for(;n=e.match(iD);)e=e.slice(0,e.length-n[0].length),t[n[0].toLowerCase()]=!0}return[e[2]===`:`?e.slice(3):qT(e.slice(2)),t]}var oD=0,sD=Promise.resolve(),cD=()=>oD||=(sD.then(()=>oD=0),Date.now());function lD(e,t){let n=e=>{if(!e._vts)e._vts=Date.now();else if(e._vts<=n.attached)return;tb(uD(e,n.value),t,5,[e])};return n.value=e,n.attached=cD(),n}function uD(e,t){if(NT(t)){let n=e.stopImmediatePropagation;return e.stopImmediatePropagation=()=>{n.call(e),e._stopped=!0},t.map(e=>t=>!t._stopped&&e&&e(t))}else return t}var dD=e=>e.charCodeAt(0)===111&&e.charCodeAt(1)===110&&e.charCodeAt(2)>96&&e.charCodeAt(2)<123,fD=(e,t,n,r,i,a)=>{let o=i===`svg`;t===`class`?PE(e,r,o):t===`style`?GE(e,n,r):OT(t)?kT(t)||rD(e,t,n,r,a):(t[0]===`.`?(t=t.slice(1),!0):t[0]===`^`?(t=t.slice(1),!1):pD(e,t,r,o))?($E(e,t,r),!e.tagName.includes(`-`)&&(t===`value`||t===`checked`||t===`selected`)&&QE(e,t,r,o,a,t!==`value`)):e._isVueCE&&(mD(e,t)||e._def.__asyncLoader&&(/[A-Z]/.test(t)||!LT(r)))?$E(e,GT(t),r,a,t):(t===`true-value`?e._trueValue=r:t===`false-value`&&(e._falseValue=r),QE(e,t,r,o))};function pD(e,t,n,r){if(r)return!!(t===`innerHTML`||t===`textContent`||t in e&&dD(t)&&IT(n));if(t===`spellcheck`||t===`draggable`||t===`translate`||t===`autocorrect`||t===`sandbox`&&e.tagName===`IFRAME`||t===`form`||t===`list`&&e.tagName===`INPUT`||t===`type`&&e.tagName===`TEXTAREA`)return!1;if(t===`width`||t===`height`){let t=e.tagName;if(t===`IMG`||t===`VIDEO`||t===`CANVAS`||t===`SOURCE`)return!1}return dD(t)&&LT(n)?!1:t in e}function mD(e,t){let n=e._def.props;if(!n)return!1;let r=GT(t);return Array.isArray(n)?n.some(e=>GT(e)===r):Object.keys(n).some(e=>GT(e)===r)}var hD={};function gD(e,t,n){let r=vx(e,t);HT(r)&&(r=AT({},r,t));class i extends yD{constructor(e){super(r,e,n)}}return i.def=r,i}var _D=((e,t)=>gD(e,t,lO)),vD=typeof HTMLElement<`u`?HTMLElement:class{},yD=class e extends vD{constructor(e,t={},n=cO){super(),this._def=e,this._props=t,this._createApp=n,this._isVueCE=!0,this._instance=null,this._app=null,this._nonce=this._def.nonce,this._connected=!1,this._resolved=!1,this._patching=!1,this._dirty=!1,this._numberProps=null,this._styleChildren=new WeakSet,this._styleAnchors=new WeakMap,this._ob=null,this.shadowRoot&&n!==cO?this._root=this.shadowRoot:e.shadowRoot===!1?this._root=this:(this.attachShadow(AT({},e.shadowRootOptions,{mode:`open`})),this._root=this.shadowRoot)}connectedCallback(){if(!this.isConnected)return;!this.shadowRoot&&!this._resolved&&this._parseSlots(),this._connected=!0;let t=this;for(;t&&=t.assignedSlot||t.parentNode||t.host;)if(t instanceof e){this._parent=t;break}this._instance||(this._resolved?this._mount(this._def):t&&t._pendingResolve?this._pendingResolve=t._pendingResolve.then(()=>{this._pendingResolve=void 0,this._resolveDef()}):this._resolveDef())}_setParent(e=this._parent){e&&(this._instance.parent=e._instance,this._inheritParentContext(e))}_inheritParentContext(e=this._parent){e&&this._app&&Object.setPrototypeOf(this._app._context.provides,e._instance.provides)}disconnectedCallback(){this._connected=!1,db(()=>{this._connected||(this._ob&&=(this._ob.disconnect(),null),this._app&&this._app.unmount(),this._instance&&(this._instance.ce=void 0),this._app=this._instance=null,this._teleportTargets&&=(this._teleportTargets.clear(),void 0))})}_processMutations(e){for(let t of e)this._setAttr(t.attributeName)}_resolveDef(){if(this._pendingResolve)return;for(let e=0;e<this.attributes.length;e++)this._setAttr(this.attributes[e].name);this._ob=new MutationObserver(this._processMutations.bind(this)),this._ob.observe(this,{attributes:!0});let e=(e,t=!1)=>{this._resolved=!0,this._pendingResolve=void 0;let{props:n,styles:r}=e,i;if(n&&!NT(n))for(let e in n){let t=n[e];(t===Number||t&&t.type===Number)&&(e in this._props&&(this._props[e]=ZT(this._props[e])),(i||=Object.create(null))[GT(e)]=!0)}this._numberProps=i,this._resolveProps(e),this.shadowRoot&&this._applyStyles(r),this._mount(e)},t=this._def.__asyncLoader;t?this._pendingResolve=t().then(t=>{t.configureApp=this._def.configureApp,e(this._def=t,!0)}):e(this._def)}_mount(e){this._app=this._createApp(e),this._inheritParentContext(),e.configureApp&&e.configureApp(this._app),this._app._ceVNode=this._createVNode(),this._app.mount(this._root);let t=this._instance&&this._instance.exposed;if(t)for(let e in t)MT(this,e)||Object.defineProperty(this,e,{get:()=>jv(t[e])})}_resolveProps(e){let{props:t}=e,n=NT(t)?t:Object.keys(t||{});for(let e of Object.keys(this))e[0]!==`_`&&n.includes(e)&&this._setProp(e,this[e]);for(let e of n.map(GT))Object.defineProperty(this,e,{get(){return this._getProp(e)},set(t){this._setProp(e,t,!0,!this._patching)}})}_setAttr(e){if(e.startsWith(`data-v-`))return;let t=this.hasAttribute(e),n=t?this.getAttribute(e):hD,r=GT(e);t&&this._numberProps&&this._numberProps[r]&&(n=ZT(n)),this._setProp(r,n,!1,!0)}_getProp(e){return this._props[e]}_setProp(e,t,n=!0,r=!1){if(t!==this._props[e]&&(this._dirty=!0,t===hD?delete this._props[e]:(this._props[e]=t,e===`key`&&this._app&&(this._app._ceVNode.key=t)),r&&this._instance&&this._update(),n)){let n=this._ob;n&&(this._processMutations(n.takeRecords()),n.disconnect()),t===!0?this.setAttribute(qT(e),``):typeof t==`string`||typeof t==`number`?this.setAttribute(qT(e),t+``):t||this.removeAttribute(qT(e)),n&&n.observe(this,{attributes:!0})}}_update(){let e=this._createVNode();this._app&&(e.appContext=this._app._context),oO(e,this._root)}_createVNode(){let e={};this.shadowRoot||(e.onVnodeMounted=e.onVnodeUpdated=this._renderSlots.bind(this));let t=q(this._def,AT(e,this._props));return this._instance||(t.ce=e=>{this._instance=e,e.ce=this,e.isCE=!0;let t=(e,t)=>{this.dispatchEvent(new CustomEvent(e,HT(t[0])?AT({detail:t},t[0]):{detail:t}))};e.emit=(e,...n)=>{t(e,n),qT(e)!==e&&t(qT(e),n)},this._setParent()}),t}_applyStyles(e,t,n){if(!e)return;if(t){if(t===this._def||this._styleChildren.has(t))return;this._styleChildren.add(t)}let r=this._nonce,i=this.shadowRoot,a=n?this._getStyleAnchor(n)||this._getStyleAnchor(this._def):this._getRootStyleInsertionAnchor(i),o=null;for(let s=e.length-1;s>=0;s--){let c=document.createElement(`style`);r&&c.setAttribute(`nonce`,r),c.textContent=e[s],i.insertBefore(c,o||a),o=c,s===0&&(n||this._styleAnchors.set(this._def,c),t&&this._styleAnchors.set(t,c))}}_getStyleAnchor(e){if(!e)return null;let t=this._styleAnchors.get(e);return t&&t.parentNode===this.shadowRoot?t:(t&&this._styleAnchors.delete(e),null)}_getRootStyleInsertionAnchor(e){for(let t=0;t<e.childNodes.length;t++){let n=e.childNodes[t];if(!(n instanceof HTMLStyleElement))return n}return null}_parseSlots(){let e=this._slots={},t;for(;t=this.firstChild;){let n=t.nodeType===1&&t.getAttribute(`slot`)||`default`;(e[n]||(e[n]=[])).push(t),this.removeChild(t)}}_renderSlots(){let e=this._getSlots(),t=this._instance.type.__scopeId;for(let n=0;n<e.length;n++){let r=e[n],i=r.getAttribute(`name`)||`default`,a=this._slots[i],o=r.parentNode;if(a)for(let e of a){if(t&&e.nodeType===1){let n=t+`-s`,r=document.createTreeWalker(e,1);e.setAttribute(n,``);let i;for(;i=r.nextNode();)i.setAttribute(n,``)}o.insertBefore(e,r)}else for(;r.firstChild;)o.insertBefore(r.firstChild,r);o.removeChild(r)}}_getSlots(){let e=[this];this._teleportTargets&&e.push(...this._teleportTargets);let t=new Set;for(let n of e){let e=n.querySelectorAll(`slot`);for(let n=0;n<e.length;n++)t.add(e[n])}return Array.from(t)}_injectChildStyle(e,t){this._applyStyles(e.styles,e,t)}_beginPatch(){this._patching=!0,this._dirty=!1}_endPatch(){this._patching=!1,this._dirty&&this._instance&&this._update()}_hasShadowRoot(){return this._def.shadowRoot!==!1}_removeChildStyle(e){}};function bD(e){let t=Yw();return t&&t.ce||null}function xD(){let e=bD();return e&&e.shadowRoot}function SD(e=`$style`){{let t=Yw();if(!t)return ET;let n=t.type.__cssModules;return n&&n[e]||ET}}var CD=new WeakMap,wD=new WeakMap,TD=Symbol(`_moveCb`),ED=Symbol(`_enterCb`),DD=(e=>(delete e.props.mode,e))({name:`TransitionGroup`,props:AT({},vE,{tag:String,moveClass:String}),setup(e,{slots:t}){let n=Yw(),r=ax(),i,a;return sS(()=>{if(!i.length)return;let t=e.moveClass||`${e.name||`v`}-move`;if(!MD(i[0].el,n.vnode.el,t)){i=[];return}i.forEach(OD),i.forEach(kD);let r=i.filter(AD);NE(n.vnode.el),r.forEach(e=>{let n=e.el,r=n.style;TE(n,t),r.transform=r.webkitTransform=r.transitionDuration=``;let i=n[TD]=e=>{e&&e.target!==n||(!e||e.propertyName.endsWith(`transform`))&&(n.removeEventListener(`transitionend`,i),n[TD]=null,EE(n,t))};n.addEventListener(`transitionend`,i)}),i=[]}),()=>{let o=H(e),s=SE(o),c=o.tag||hw;if(i=[],a)for(let e=0;e<a.length;e++){let t=a[e];t.el&&t.el instanceof Element&&(i.push(t),gx(t,px(t,s,r,n)),CD.set(t,jD(t.el)))}a=t.default?_x(t.default()):[];for(let e=0;e<a.length;e++){let t=a[e];t.key!=null&&gx(t,px(t,s,r,n))}return q(c,null,a)}}});function OD(e){let t=e.el;t[TD]&&t[TD](),t[ED]&&t[ED]()}function kD(e){wD.set(e,jD(e.el))}function AD(e){let t=CD.get(e),n=wD.get(e),r=t.left-n.left,i=t.top-n.top;if(r||i){let t=e.el,n=t.style,a=t.getBoundingClientRect(),o=1,s=1;return t.offsetWidth&&(o=a.width/t.offsetWidth),t.offsetHeight&&(s=a.height/t.offsetHeight),(!Number.isFinite(o)||o===0)&&(o=1),(!Number.isFinite(s)||s===0)&&(s=1),Math.abs(o-1)<.01&&(o=1),Math.abs(s-1)<.01&&(s=1),n.transform=n.webkitTransform=`translate(${r/o}px,${i/s}px)`,n.transitionDuration=`0s`,e}}function jD(e){let t=e.getBoundingClientRect();return{left:t.left,top:t.top}}function MD(e,t,n){let r=e.cloneNode(),i=e[gE];i&&i.forEach(e=>{e.split(/\s+/).forEach(e=>e&&r.classList.remove(e))}),n.split(/\s+/).forEach(e=>e&&r.classList.add(e)),r.style.display=`none`;let a=t.nodeType===1?t:t.parentNode;a.appendChild(r);let{hasTransform:o}=AE(r);return a.removeChild(r),o}var ND=e=>{let t=e.props[`onUpdate:modelValue`]||!1;return NT(t)?e=>YT(t,e):t};function PD(e){e.target.composing=!0}function FD(e){let t=e.target;t.composing&&(t.composing=!1,t.dispatchEvent(new Event(`input`)))}var ID=Symbol(`_assign`);function LD(e,t,n){return t&&(e=e.trim()),n&&(e=XT(e)),e}var RD={created(e,{modifiers:{lazy:t,trim:n,number:r}},i){e[ID]=ND(i);let a=r||i.props&&i.props.type===`number`;eD(e,t?`change`:`input`,t=>{t.target.composing||e[ID](LD(e.value,n,a))}),(n||a)&&eD(e,`change`,()=>{e.value=LD(e.value,n,a)}),t||(eD(e,`compositionstart`,PD),eD(e,`compositionend`,FD),eD(e,`change`,FD))},mounted(e,{value:t}){e.value=t??``},beforeUpdate(e,{value:t,oldValue:n,modifiers:{lazy:r,trim:i,number:a}},o){if(e[ID]=ND(o),e.composing)return;let s=(a||e.type===`number`)&&!/^0\d/.test(e.value)?XT(e.value):e.value,c=t??``;if(s===c)return;let l=e.getRootNode();(l instanceof Document||l instanceof ShadowRoot)&&l.activeElement===e&&e.type!==`range`&&(r&&t===n||i&&e.value.trim()===c)||(e.value=c)}},zD={deep:!0,created(e,t,n){e[ID]=ND(n),eD(e,`change`,()=>{let t=e._modelValue,n=WD(e),r=e.checked,i=e[ID];if(NT(t)){let e=rE(t,n),a=e!==-1;if(r&&!a)i(t.concat(n));else if(!r&&a){let n=[...t];n.splice(e,1),i(n)}}else if(PT(t)){let e=new Set(t);r?e.add(n):e.delete(n),i(e)}else i(GD(e,r))})},mounted:BD,beforeUpdate(e,t,n){e[ID]=ND(n),BD(e,t,n)}};function BD(e,{value:t,oldValue:n},r){e._modelValue=t;let i;if(NT(t))i=rE(t,r.props.value)>-1;else if(PT(t))i=t.has(r.props.value);else{if(t===n)return;i=nE(t,GD(e,!0))}e.checked!==i&&(e.checked=i)}var VD={created(e,{value:t},n){e.checked=nE(t,n.props.value),e[ID]=ND(n),eD(e,`change`,()=>{e[ID](WD(e))})},beforeUpdate(e,{value:t,oldValue:n},r){e[ID]=ND(r),t!==n&&(e.checked=nE(t,r.props.value))}},HD={deep:!0,created(e,{value:t,modifiers:{number:n}},r){let i=PT(t);eD(e,`change`,()=>{let t=Array.prototype.filter.call(e.options,e=>e.selected).map(e=>n?XT(WD(e)):WD(e));e[ID](e.multiple?i?new Set(t):t:t[0]),e._assigning=!0,db(()=>{e._assigning=!1})}),e[ID]=ND(r)},mounted(e,{value:t}){UD(e,t)},beforeUpdate(e,t,n){e[ID]=ND(n)},updated(e,{value:t}){e._assigning||UD(e,t)}};function UD(e,t){let n=e.multiple,r=NT(t);if(!(n&&!r&&!PT(t))){for(let i=0,a=e.options.length;i<a;i++){let a=e.options[i],o=WD(a);if(n)if(r){let e=typeof o;e===`string`||e===`number`?a.selected=t.some(e=>String(e)===String(o)):a.selected=rE(t,o)>-1}else a.selected=t.has(o);else if(nE(WD(a),t)){e.selectedIndex!==i&&(e.selectedIndex=i);return}}!n&&e.selectedIndex!==-1&&(e.selectedIndex=-1)}}function WD(e){return`_value`in e?e._value:e.value}function GD(e,t){let n=t?`_trueValue`:`_falseValue`;return n in e?e[n]:t}var KD={created(e,t,n){JD(e,t,n,null,`created`)},mounted(e,t,n){JD(e,t,n,null,`mounted`)},beforeUpdate(e,t,n,r){JD(e,t,n,r,`beforeUpdate`)},updated(e,t,n,r){JD(e,t,n,r,`updated`)}};function qD(e,t){switch(e){case`SELECT`:return HD;case`TEXTAREA`:return RD;default:switch(t){case`checkbox`:return zD;case`radio`:return VD;default:return RD}}}function JD(e,t,n,r,i){let a=qD(e.tagName,n.props&&n.props.type)[i];a&&a(e,t,n,r)}function YD(){RD.getSSRProps=({value:e})=>({value:e}),VD.getSSRProps=({value:e},t)=>{if(t.props&&nE(t.props.value,e))return{checked:!0}},zD.getSSRProps=({value:e},t)=>{if(NT(e)){if(t.props&&rE(e,t.props.value)>-1)return{checked:!0}}else if(PT(e)){if(t.props&&e.has(t.props.value))return{checked:!0}}else if(e)return{checked:!0}},KD.getSSRProps=(e,t)=>{if(typeof t.type!=`string`)return;let n=qD(t.type.toUpperCase(),t.props&&t.props.type);if(n.getSSRProps)return n.getSSRProps(e,t)}}var XD=[`ctrl`,`shift`,`alt`,`meta`],ZD={stop:e=>e.stopPropagation(),prevent:e=>e.preventDefault(),self:e=>e.target!==e.currentTarget,ctrl:e=>!e.ctrlKey,shift:e=>!e.shiftKey,alt:e=>!e.altKey,meta:e=>!e.metaKey,left:e=>`button`in e&&e.button!==0,middle:e=>`button`in e&&e.button!==1,right:e=>`button`in e&&e.button!==2,exact:(e,t)=>XD.some(n=>e[`${n}Key`]&&!t.includes(n))},QD=(e,t)=>{if(!e)return e;let n=e._withMods||={},r=t.join(`.`);return n[r]||(n[r]=((n,...r)=>{for(let e=0;e<t.length;e++){let r=ZD[t[e]];if(r&&r(n,t))return}return e(n,...r)}))},$D={esc:`escape`,space:` `,up:`arrow-up`,left:`arrow-left`,right:`arrow-right`,down:`arrow-down`,delete:`backspace`},eO=(e,t)=>{let n=e._withKeys||={},r=t.join(`.`);return n[r]||(n[r]=(n=>{if(!(`key`in n))return;let r=qT(n.key);if(t.some(e=>e===r||$D[e]===r))return e(n)}))},tO=AT({patchProp:fD},pE),nO,rO=!1;function iO(){return nO||=GC(tO)}function aO(){return nO=rO?nO:KC(tO),rO=!0,nO}var oO=((...e)=>{iO().render(...e)}),sO=((...e)=>{aO().hydrate(...e)}),cO=((...e)=>{let t=iO().createApp(...e),{mount:n}=t;return t.mount=e=>{let r=dO(e);if(!r)return;let i=t._component;!IT(i)&&!i.render&&!i.template&&(i.template=r.innerHTML),r.nodeType===1&&(r.textContent=``);let a=n(r,!1,uO(r));return r instanceof Element&&(r.removeAttribute(`v-cloak`),r.setAttribute(`data-v-app`,``)),a},t}),lO=((...e)=>{let t=aO().createApp(...e),{mount:n}=t;return t.mount=e=>{let t=dO(e);if(t)return n(t,!0,uO(t))},t});function uO(e){if(e instanceof SVGElement)return`svg`;if(typeof MathMLElement==`function`&&e instanceof MathMLElement)return`mathml`}function dO(e){return LT(e)?document.querySelector(e):e}var fO=!1,pO=()=>{fO||(fO=!0,YD(),zE())};function mO(e){let t=Object.create(null);for(let n of e.split(`,`))t[n]=1;return e=>e in t}var hO={},gO=()=>{},_O=()=>!1,vO=e=>e.charCodeAt(0)===111&&e.charCodeAt(1)===110&&(e.charCodeAt(2)>122||e.charCodeAt(2)<97),yO=Object.assign,bO=Array.isArray,xO=e=>typeof e==`string`,SO=e=>typeof e==`symbol`,CO=e=>typeof e==`object`&&!!e,wO=mO(`,key,ref,ref_for,ref_key,onVnodeBeforeMount,onVnodeMounted,onVnodeBeforeUpdate,onVnodeUpdated,onVnodeBeforeUnmount,onVnodeUnmounted`),TO=mO(`bind,cloak,else-if,else,for,html,if,model,on,once,pre,show,slot,text,memo`),EO=e=>{let t=Object.create(null);return(n=>t[n]||(t[n]=e(n)))},DO=/-\w/g,OO=EO(e=>e.replace(DO,e=>e.slice(1).toUpperCase())),kO=EO(e=>e.charAt(0).toUpperCase()+e.slice(1)),AO=EO(e=>e?`on${kO(e)}`:``);function jO(e,t){return e+JSON.stringify(t,(e,t)=>typeof t==`function`?t.toString():t)}var MO=/;(?![^(]*\))/g,NO=/:([^]+)/,PO=/\/\*[^]*?\*\//g;function FO(e){let t={};return e.replace(PO,``).split(MO).forEach(e=>{if(e){let n=e.split(NO);n.length>1&&(t[n[0].trim()]=n[1].trim())}}),t}var IO=`html,body,base,head,link,meta,style,title,address,article,aside,footer,header,hgroup,h1,h2,h3,h4,h5,h6,nav,section,div,dd,dl,dt,figcaption,figure,picture,hr,img,li,main,ol,p,pre,ul,a,b,abbr,bdi,bdo,br,cite,code,data,dfn,em,i,kbd,mark,q,rp,rt,ruby,s,samp,small,span,strong,sub,sup,time,u,var,wbr,area,audio,map,track,video,embed,object,param,source,canvas,script,noscript,del,ins,caption,col,colgroup,table,thead,tbody,td,th,tr,button,datalist,fieldset,form,input,label,legend,meter,optgroup,option,output,progress,select,textarea,details,dialog,menu,summary,template,blockquote,iframe,tfoot`,LO=`svg,animate,animateMotion,animateTransform,circle,clipPath,color-profile,defs,desc,discard,ellipse,feBlend,feColorMatrix,feComponentTransfer,feComposite,feConvolveMatrix,feDiffuseLighting,feDisplacementMap,feDistantLight,feDropShadow,feFlood,feFuncA,feFuncB,feFuncG,feFuncR,feGaussianBlur,feImage,feMerge,feMergeNode,feMorphology,feOffset,fePointLight,feSpecularLighting,feSpotLight,feTile,feTurbulence,filter,foreignObject,g,hatch,hatchpath,image,line,linearGradient,marker,mask,mesh,meshgradient,meshpatch,meshrow,metadata,mpath,path,pattern,polygon,polyline,radialGradient,rect,set,solidcolor,stop,switch,symbol,text,textPath,title,tspan,unknown,use,view`,RO=`annotation,annotation-xml,maction,maligngroup,malignmark,math,menclose,merror,mfenced,mfrac,mfraction,mglyph,mi,mlabeledtr,mlongdiv,mmultiscripts,mn,mo,mover,mpadded,mphantom,mprescripts,mroot,mrow,ms,mscarries,mscarry,msgroup,msline,mspace,msqrt,msrow,mstack,mstyle,msub,msubsup,msup,mtable,mtd,mtext,mtr,munder,munderover,none,semantics`,zO=`area,base,br,col,embed,hr,img,input,link,meta,param,source,track,wbr`,BO=mO(IO),VO=mO(LO),HO=mO(RO),UO=mO(zO),WO=Symbol(``),GO=Symbol(``),KO=Symbol(``),qO=Symbol(``),JO=Symbol(``),YO=Symbol(``),XO=Symbol(``),ZO=Symbol(``),QO=Symbol(``),$O=Symbol(``),ek=Symbol(``),tk=Symbol(``),nk=Symbol(``),rk=Symbol(``),ik=Symbol(``),ak=Symbol(``),ok=Symbol(``),sk=Symbol(``),ck=Symbol(``),lk=Symbol(``),uk=Symbol(``),dk=Symbol(``),fk=Symbol(``),pk=Symbol(``),mk=Symbol(``),hk=Symbol(``),gk=Symbol(``),_k=Symbol(``),vk=Symbol(``),yk=Symbol(``),bk=Symbol(``),xk=Symbol(``),Sk=Symbol(``),Ck=Symbol(``),wk=Symbol(``),Tk=Symbol(``),Ek=Symbol(``),Dk=Symbol(``),Ok=Symbol(``),kk={[WO]:`Fragment`,[GO]:`Teleport`,[KO]:`Suspense`,[qO]:`KeepAlive`,[JO]:`BaseTransition`,[YO]:`openBlock`,[XO]:`createBlock`,[ZO]:`createElementBlock`,[QO]:`createVNode`,[$O]:`createElementVNode`,[ek]:`createCommentVNode`,[tk]:`createTextVNode`,[nk]:`createStaticVNode`,[rk]:`resolveComponent`,[ik]:`resolveDynamicComponent`,[ak]:`resolveDirective`,[ok]:`resolveFilter`,[sk]:`withDirectives`,[ck]:`renderList`,[lk]:`renderSlot`,[uk]:`createSlots`,[dk]:`toDisplayString`,[fk]:`mergeProps`,[pk]:`normalizeClass`,[mk]:`normalizeStyle`,[hk]:`normalizeProps`,[gk]:`guardReactiveProps`,[_k]:`toHandlers`,[vk]:`camelize`,[yk]:`capitalize`,[bk]:`toHandlerKey`,[xk]:`setBlockTracking`,[Sk]:`pushScopeId`,[Ck]:`popScopeId`,[wk]:`withCtx`,[Tk]:`unref`,[Ek]:`isRef`,[Dk]:`withMemo`,[Ok]:`isMemoSame`};function Ak(e){Object.getOwnPropertySymbols(e).forEach(t=>{kk[t]=e[t]})}var jk={start:{line:1,column:1,offset:0},end:{line:1,column:1,offset:0},source:``};function Mk(e,t=``){return{type:0,source:t,children:e,helpers:new Set,components:[],directives:[],hoists:[],imports:[],cached:[],temps:0,codegenNode:void 0,loc:jk}}function Nk(e,t,n,r,i,a,o,s=!1,c=!1,l=!1,u=jk){return e&&(s?(e.helper(YO),e.helper(Wk(e.inSSR,l))):e.helper(Uk(e.inSSR,l)),o&&e.helper(sk)),{type:13,tag:t,props:n,children:r,patchFlag:i,dynamicProps:a,directives:o,isBlock:s,disableTracking:c,isComponent:l,loc:u}}function Pk(e,t=jk){return{type:17,loc:t,elements:e}}function Fk(e,t=jk){return{type:15,loc:t,properties:e}}function Ik(e,t){return{type:16,loc:jk,key:xO(e)?Y(e,!0):e,value:t}}function Y(e,t=!1,n=jk,r=0){return{type:4,loc:n,content:e,isStatic:t,constType:t?3:r}}function Lk(e,t=jk){return{type:8,loc:t,children:e}}function Rk(e,t=[],n=jk){return{type:14,loc:n,callee:e,arguments:t}}function zk(e,t=void 0,n=!1,r=!1,i=jk){return{type:18,params:e,returns:t,newline:n,isSlot:r,loc:i}}function Bk(e,t,n,r=!0){return{type:19,test:e,consequent:t,alternate:n,newline:r,loc:jk}}function Vk(e,t,n=!1,r=!1){return{type:20,index:e,value:t,needPauseTracking:n,inVOnce:r,needArraySpread:!1,loc:jk}}function Hk(e){return{type:21,body:e,loc:jk}}function Uk(e,t){return e||t?QO:$O}function Wk(e,t){return e||t?XO:ZO}function Gk(e,{helper:t,removeHelper:n,inSSR:r}){e.isBlock||(e.isBlock=!0,n(Uk(r,e.isComponent)),t(YO),t(Wk(r,e.isComponent)))}var Kk=new Uint8Array([123,123]),qk=new Uint8Array([125,125]);function Jk(e){return e>=97&&e<=122||e>=65&&e<=90}function Yk(e){return e===32||e===10||e===9||e===12||e===13}function Xk(e){return e===47||e===62||Yk(e)}function Zk(e){let t=new Uint8Array(e.length);for(let n=0;n<e.length;n++)t[n]=e.charCodeAt(n);return t}var Qk={Cdata:new Uint8Array([67,68,65,84,65,91]),CdataEnd:new Uint8Array([93,93,62]),CommentEnd:new Uint8Array([45,45,62]),ScriptEnd:new Uint8Array([60,47,115,99,114,105,112,116]),StyleEnd:new Uint8Array([60,47,115,116,121,108,101]),TitleEnd:new Uint8Array([60,47,116,105,116,108,101]),TextareaEnd:new Uint8Array([60,47,116,101,120,116,97,114,101,97])},$k=class{constructor(e,t){this.stack=e,this.cbs=t,this.state=1,this.buffer=``,this.sectionStart=0,this.index=0,this.entityStart=0,this.baseState=1,this.inRCDATA=!1,this.inXML=!1,this.inVPre=!1,this.newlines=[],this.mode=0,this.delimiterOpen=Kk,this.delimiterClose=qk,this.delimiterIndex=-1,this.currentSequence=void 0,this.sequenceIndex=0}get inSFCRoot(){return this.mode===2&&this.stack.length===0}reset(){this.state=1,this.mode=0,this.buffer=``,this.sectionStart=0,this.index=0,this.baseState=1,this.inRCDATA=!1,this.currentSequence=void 0,this.newlines.length=0,this.delimiterOpen=Kk,this.delimiterClose=qk}getPos(e){let t=1,n=e+1,r=this.newlines.length,i=-1;if(r>100){let t=-1,n=r;for(;t+1<n;){let r=t+n>>>1;this.newlines[r]<e?t=r:n=r}i=t}else for(let t=r-1;t>=0;t--)if(e>this.newlines[t]){i=t;break}return i>=0&&(t=i+2,n=e-this.newlines[i]),{column:n,line:t,offset:e}}peek(){return this.buffer.charCodeAt(this.index+1)}stateText(e){e===60?(this.index>this.sectionStart&&this.cbs.ontext(this.sectionStart,this.index),this.state=5,this.sectionStart=this.index):!this.inVPre&&e===this.delimiterOpen[0]&&(this.state=2,this.delimiterIndex=0,this.stateInterpolationOpen(e))}stateInterpolationOpen(e){if(e===this.delimiterOpen[this.delimiterIndex])if(this.delimiterIndex===this.delimiterOpen.length-1){let e=this.index+1-this.delimiterOpen.length;e>this.sectionStart&&this.cbs.ontext(this.sectionStart,e),this.state=3,this.sectionStart=e}else this.delimiterIndex++;else this.inRCDATA?(this.state=32,this.stateInRCDATA(e)):(this.state=1,this.stateText(e))}stateInterpolation(e){e===this.delimiterClose[0]&&(this.state=4,this.delimiterIndex=0,this.stateInterpolationClose(e))}stateInterpolationClose(e){e===this.delimiterClose[this.delimiterIndex]?this.delimiterIndex===this.delimiterClose.length-1?(this.cbs.oninterpolation(this.sectionStart,this.index+1),this.inRCDATA?this.state=32:this.state=1,this.sectionStart=this.index+1):this.delimiterIndex++:(this.state=3,this.stateInterpolation(e))}stateSpecialStartSequence(e){let t=this.sequenceIndex===this.currentSequence.length;if(!(t?Xk(e):(e|32)===this.currentSequence[this.sequenceIndex]))this.inRCDATA=!1;else if(!t){this.sequenceIndex++;return}this.sequenceIndex=0,this.state=6,this.stateInTagName(e)}stateInRCDATA(e){if(this.sequenceIndex===this.currentSequence.length){if(e===62||Yk(e)){let t=this.index-this.currentSequence.length;if(this.sectionStart<t){let e=this.index;this.index=t,this.cbs.ontext(this.sectionStart,t),this.index=e}this.sectionStart=t+2,this.stateInClosingTagName(e),this.inRCDATA=!1;return}this.sequenceIndex=0}(e|32)===this.currentSequence[this.sequenceIndex]?this.sequenceIndex+=1:this.sequenceIndex===0?this.currentSequence===Qk.TitleEnd||this.currentSequence===Qk.TextareaEnd&&!this.inSFCRoot?!this.inVPre&&e===this.delimiterOpen[0]&&(this.state=2,this.delimiterIndex=0,this.stateInterpolationOpen(e)):this.fastForwardTo(60)&&(this.sequenceIndex=1):this.sequenceIndex=Number(e===60)}stateCDATASequence(e){e===Qk.Cdata[this.sequenceIndex]?++this.sequenceIndex===Qk.Cdata.length&&(this.state=28,this.currentSequence=Qk.CdataEnd,this.sequenceIndex=0,this.sectionStart=this.index+1):(this.sequenceIndex=0,this.state=23,this.stateInDeclaration(e))}fastForwardTo(e){for(;++this.index<this.buffer.length;){let t=this.buffer.charCodeAt(this.index);if(t===10&&this.newlines.push(this.index),t===e)return!0}return this.index=this.buffer.length-1,!1}stateInCommentLike(e){e===this.currentSequence[this.sequenceIndex]?++this.sequenceIndex===this.currentSequence.length&&(this.currentSequence===Qk.CdataEnd?this.cbs.oncdata(this.sectionStart,this.index-2):this.cbs.oncomment(this.sectionStart,this.index-2),this.sequenceIndex=0,this.sectionStart=this.index+1,this.state=1):this.sequenceIndex===0?this.fastForwardTo(this.currentSequence[0])&&(this.sequenceIndex=1):e!==this.currentSequence[this.sequenceIndex-1]&&(this.sequenceIndex=0)}startSpecial(e,t){this.enterRCDATA(e,t),this.state=31}enterRCDATA(e,t){this.inRCDATA=!0,this.currentSequence=e,this.sequenceIndex=t}stateBeforeTagName(e){e===33?(this.state=22,this.sectionStart=this.index+1):e===63?(this.state=24,this.sectionStart=this.index+1):Jk(e)?(this.sectionStart=this.index,this.mode===0?this.state=6:this.inSFCRoot?this.state=34:this.inXML?this.state=6:e===116?this.state=30:this.state=e===115?29:6):e===47?this.state=8:(this.state=1,this.stateText(e))}stateInTagName(e){Xk(e)&&this.handleTagName(e)}stateInSFCRootTagName(e){if(Xk(e)){let t=this.buffer.slice(this.sectionStart,this.index);t!==`template`&&this.enterRCDATA(Zk(`</`+t),0),this.handleTagName(e)}}handleTagName(e){this.cbs.onopentagname(this.sectionStart,this.index),this.sectionStart=-1,this.state=11,this.stateBeforeAttrName(e)}stateBeforeClosingTagName(e){Yk(e)||(e===62?(this.state=1,this.sectionStart=this.index+1):(this.state=Jk(e)?9:27,this.sectionStart=this.index))}stateInClosingTagName(e){(e===62||Yk(e))&&(this.cbs.onclosetag(this.sectionStart,this.index),this.sectionStart=-1,this.state=10,this.stateAfterClosingTagName(e))}stateAfterClosingTagName(e){e===62&&(this.state=1,this.sectionStart=this.index+1)}stateBeforeAttrName(e){e===62?(this.cbs.onopentagend(this.index),this.inRCDATA?this.state=32:this.state=1,this.sectionStart=this.index+1):e===47?this.state=7:e===60&&this.peek()===47?(this.cbs.onopentagend(this.index),this.state=5,this.sectionStart=this.index):Yk(e)||this.handleAttrStart(e)}handleAttrStart(e){e===118&&this.peek()===45?(this.state=13,this.sectionStart=this.index):e===46||e===58||e===64||e===35?(this.cbs.ondirname(this.index,this.index+1),this.state=14,this.sectionStart=this.index+1):(this.state=12,this.sectionStart=this.index)}stateInSelfClosingTag(e){e===62?(this.cbs.onselfclosingtag(this.index),this.state=1,this.sectionStart=this.index+1,this.inRCDATA=!1):Yk(e)||(this.state=11,this.stateBeforeAttrName(e))}stateInAttrName(e){(e===61||Xk(e))&&(this.cbs.onattribname(this.sectionStart,this.index),this.handleAttrNameEnd(e))}stateInDirName(e){e===61||Xk(e)?(this.cbs.ondirname(this.sectionStart,this.index),this.handleAttrNameEnd(e)):e===58?(this.cbs.ondirname(this.sectionStart,this.index),this.state=14,this.sectionStart=this.index+1):e===46&&(this.cbs.ondirname(this.sectionStart,this.index),this.state=16,this.sectionStart=this.index+1)}stateInDirArg(e){e===61||Xk(e)?(this.cbs.ondirarg(this.sectionStart,this.index),this.handleAttrNameEnd(e)):e===91?this.state=15:e===46&&(this.cbs.ondirarg(this.sectionStart,this.index),this.state=16,this.sectionStart=this.index+1)}stateInDynamicDirArg(e){e===93?this.state=14:(e===61||Xk(e))&&(this.cbs.ondirarg(this.sectionStart,this.index+1),this.handleAttrNameEnd(e))}stateInDirModifier(e){e===61||Xk(e)?(this.cbs.ondirmodifier(this.sectionStart,this.index),this.handleAttrNameEnd(e)):e===46&&(this.cbs.ondirmodifier(this.sectionStart,this.index),this.sectionStart=this.index+1)}handleAttrNameEnd(e){this.sectionStart=this.index,this.state=17,this.cbs.onattribnameend(this.index),this.stateAfterAttrName(e)}stateAfterAttrName(e){e===61?this.state=18:e===47||e===62?(this.cbs.onattribend(0,this.sectionStart),this.sectionStart=-1,this.state=11,this.stateBeforeAttrName(e)):Yk(e)||(this.cbs.onattribend(0,this.sectionStart),this.handleAttrStart(e))}stateBeforeAttrValue(e){e===34?(this.state=19,this.sectionStart=this.index+1):e===39?(this.state=20,this.sectionStart=this.index+1):Yk(e)||(this.sectionStart=this.index,this.state=21,this.stateInAttrValueNoQuotes(e))}handleInAttrValue(e,t){(e===t||this.fastForwardTo(t))&&(this.cbs.onattribdata(this.sectionStart,this.index),this.sectionStart=-1,this.cbs.onattribend(t===34?3:2,this.index+1),this.state=11)}stateInAttrValueDoubleQuotes(e){this.handleInAttrValue(e,34)}stateInAttrValueSingleQuotes(e){this.handleInAttrValue(e,39)}stateInAttrValueNoQuotes(e){Yk(e)||e===62?(this.cbs.onattribdata(this.sectionStart,this.index),this.sectionStart=-1,this.cbs.onattribend(1,this.index),this.state=11,this.stateBeforeAttrName(e)):(e===39||e===60||e===61||e===96)&&this.cbs.onerr(18,this.index)}stateBeforeDeclaration(e){e===91?(this.state=26,this.sequenceIndex=0):this.state=e===45?25:23}stateInDeclaration(e){(e===62||this.fastForwardTo(62))&&(this.state=1,this.sectionStart=this.index+1)}stateInProcessingInstruction(e){(e===62||this.fastForwardTo(62))&&(this.cbs.onprocessinginstruction(this.sectionStart,this.index),this.state=1,this.sectionStart=this.index+1)}stateBeforeComment(e){e===45?(this.state=28,this.currentSequence=Qk.CommentEnd,this.sequenceIndex=2,this.sectionStart=this.index+1):this.state=23}stateInSpecialComment(e){(e===62||this.fastForwardTo(62))&&(this.cbs.oncomment(this.sectionStart,this.index),this.state=1,this.sectionStart=this.index+1)}stateBeforeSpecialS(e){e===Qk.ScriptEnd[3]?this.startSpecial(Qk.ScriptEnd,4):e===Qk.StyleEnd[3]?this.startSpecial(Qk.StyleEnd,4):(this.state=6,this.stateInTagName(e))}stateBeforeSpecialT(e){e===Qk.TitleEnd[3]?this.startSpecial(Qk.TitleEnd,4):e===Qk.TextareaEnd[3]?this.startSpecial(Qk.TextareaEnd,4):(this.state=6,this.stateInTagName(e))}startEntity(){}stateInEntity(){}parse(e){for(this.buffer=e;this.index<this.buffer.length;){let e=this.buffer.charCodeAt(this.index);switch(e===10&&this.state!==33&&this.newlines.push(this.index),this.state){case 1:this.stateText(e);break;case 2:this.stateInterpolationOpen(e);break;case 3:this.stateInterpolation(e);break;case 4:this.stateInterpolationClose(e);break;case 31:this.stateSpecialStartSequence(e);break;case 32:this.stateInRCDATA(e);break;case 26:this.stateCDATASequence(e);break;case 19:this.stateInAttrValueDoubleQuotes(e);break;case 12:this.stateInAttrName(e);break;case 13:this.stateInDirName(e);break;case 14:this.stateInDirArg(e);break;case 15:this.stateInDynamicDirArg(e);break;case 16:this.stateInDirModifier(e);break;case 28:this.stateInCommentLike(e);break;case 27:this.stateInSpecialComment(e);break;case 11:this.stateBeforeAttrName(e);break;case 6:this.stateInTagName(e);break;case 34:this.stateInSFCRootTagName(e);break;case 9:this.stateInClosingTagName(e);break;case 5:this.stateBeforeTagName(e);break;case 17:this.stateAfterAttrName(e);break;case 20:this.stateInAttrValueSingleQuotes(e);break;case 18:this.stateBeforeAttrValue(e);break;case 8:this.stateBeforeClosingTagName(e);break;case 10:this.stateAfterClosingTagName(e);break;case 29:this.stateBeforeSpecialS(e);break;case 30:this.stateBeforeSpecialT(e);break;case 21:this.stateInAttrValueNoQuotes(e);break;case 7:this.stateInSelfClosingTag(e);break;case 23:this.stateInDeclaration(e);break;case 22:this.stateBeforeDeclaration(e);break;case 25:this.stateBeforeComment(e);break;case 24:this.stateInProcessingInstruction(e);break;case 33:this.stateInEntity();break}this.index++}this.cleanup(),this.finish()}cleanup(){this.sectionStart!==this.index&&(this.state===1||this.state===32&&this.sequenceIndex===0?(this.cbs.ontext(this.sectionStart,this.index),this.sectionStart=this.index):(this.state===19||this.state===20||this.state===21)&&(this.cbs.onattribdata(this.sectionStart,this.index),this.sectionStart=this.index))}finish(){this.handleTrailingData(),this.cbs.onend()}handleTrailingData(){let e=this.buffer.length;this.sectionStart>=e||(this.state===28?this.currentSequence===Qk.CdataEnd?this.cbs.oncdata(this.sectionStart,e):this.cbs.oncomment(this.sectionStart,e):this.state===6||this.state===11||this.state===18||this.state===17||this.state===12||this.state===13||this.state===14||this.state===15||this.state===16||this.state===20||this.state===19||this.state===21||this.state===9||this.cbs.ontext(this.sectionStart,e))}emitCodePoint(e,t){}};function eA(e,{compatConfig:t}){let n=t&&t[e];return e===`MODE`?n||3:n}function tA(e,t){let n=eA(`MODE`,t),r=eA(e,t);return n===3?r===!0:r!==!1}function nA(e,t,n,...r){return tA(e,t)}function rA(e){throw e}function iA(e){}function X(e,t,n,r){let i=`https://vuejs.org/error-reference/#compiler-${e}`,a=SyntaxError(String(i));return a.code=e,a.loc=t,a}var aA=e=>e.type===4&&e.isStatic;function oA(e){switch(e){case`Teleport`:case`teleport`:return GO;case`Suspense`:case`suspense`:return KO;case`KeepAlive`:case`keep-alive`:return qO;case`BaseTransition`:case`base-transition`:return JO}}var sA=/^$|^\d|[^\$\w\xA0-\uFFFF]/,cA=e=>!sA.test(e),lA=/[A-Za-z_$\xA0-\uFFFF]/,uA=/[\.\?\w$\xA0-\uFFFF]/,dA=/\s+[.[]\s*|\s*[.[]\s+/g,fA=e=>e.type===4?e.content:e.loc.source,pA=e=>{let t=fA(e).trim().replace(dA,e=>e.trim()),n=0,r=[],i=0,a=0,o=null;for(let e=0;e<t.length;e++){let s=t.charAt(e);switch(n){case 0:if(s===`[`)r.push(n),n=1,i++;else if(s===`(`)r.push(n),n=2,a++;else if(!(e===0?lA:uA).test(s))return!1;break;case 1:s===`'`||s===`"`||s==="`"?(r.push(n),n=3,o=s):s===`[`?i++:s===`]`&&(--i||(n=r.pop()));break;case 2:if(s===`'`||s===`"`||s==="`")r.push(n),n=3,o=s;else if(s===`(`)a++;else if(s===`)`){if(e===t.length-1)return!1;--a||(n=r.pop())}break;case 3:s===o&&(n=r.pop(),o=null);break}}return!i&&!a},mA=/^\s*(?:async\s*)?(?:\([^)]*?\)|[\w$_]+)\s*(?::[^=]+)?=>|^\s*(?:async\s+)?function(?:\s+[\w$]+)?\s*\(/,hA=e=>mA.test(fA(e));function gA(e,t,n=!1){for(let r=0;r<e.props.length;r++){let i=e.props[r];if(i.type===7&&(n||i.exp)&&(xO(t)?i.name===t:t.test(i.name)))return i}}function _A(e,t,n=!1,r=!1){for(let i=0;i<e.props.length;i++){let a=e.props[i];if(a.type===6){if(n)continue;if(a.name===t&&(a.value||r))return a}else if(a.name===`bind`&&(a.exp||r)&&vA(a.arg,t))return a}}function vA(e,t){return!!(e&&aA(e)&&e.content===t)}function yA(e){return e.props.some(e=>e.type===7&&e.name===`bind`&&(!e.arg||e.arg.type!==4||!e.arg.isStatic))}function bA(e){return e.type===5||e.type===2}function xA(e){return e.type===7&&e.name===`pre`}function SA(e){return e.type===7&&e.name===`slot`}function CA(e){return e.type===1&&e.tagType===3}function wA(e){return e.type===1&&e.tagType===2}var TA=new Set([hk,gk]);function EA(e,t=[]){if(e&&!xO(e)&&e.type===14){let n=e.callee;if(!xO(n)&&TA.has(n))return EA(e.arguments[0],t.concat(e))}return[e,t]}function DA(e,t,n){let r,i=e.type===13?e.props:e.arguments[2],a=[],o;if(i&&!xO(i)&&i.type===14){let e=EA(i);i=e[0],a=e[1],o=a[a.length-1]}if(i==null||xO(i))r=Fk([t]);else if(i.type===14){let e=i.arguments[0];!xO(e)&&e.type===15?OA(t,e)||e.properties.unshift(t):i.callee===_k?r=Rk(n.helper(fk),[Fk([t]),i]):i.arguments.unshift(Fk([t])),!r&&(r=i)}else i.type===15?(OA(t,i)||i.properties.unshift(t),r=i):(r=Rk(n.helper(fk),[Fk([t]),i]),o&&o.callee===gk&&(o=a[a.length-2]));e.type===13?o?o.arguments[0]=r:e.props=r:o?o.arguments[0]=r:e.arguments[2]=r}function OA(e,t){let n=!1;if(e.key.type===4){let r=e.key.content;n=t.properties.some(e=>e.key.type===4&&e.key.content===r)}return n}function kA(e,t){return`_${t}_${e.replace(/[^\w]/g,(t,n)=>t===`-`?`_`:e.charCodeAt(n).toString())}`}function AA(e){return e.type===14&&e.callee===Dk?e.arguments[1].returns:e}var jA=/([\s\S]*?)\s+(?:in|of)\s+(\S[\s\S]*)/;function MA(e){for(let t=0;t<e.length;t++)if(!Yk(e.charCodeAt(t)))return!1;return!0}function NA(e){return e.type===2&&MA(e.content)||e.type===12&&NA(e.content)}function PA(e){return e.type===3||NA(e)}var FA={parseMode:`base`,ns:0,delimiters:[`{{`,`}}`],getNamespace:()=>0,isVoidTag:_O,isPreTag:_O,isIgnoreNewlineTag:_O,isCustomElement:_O,onError:rA,onWarn:iA,comments:!1,prefixIdentifiers:!1},Z=FA,IA=null,LA=``,RA=null,Q=null,zA=``,BA=-1,VA=-1,HA=0,UA=!1,WA=null,GA=[],KA=new $k(GA,{onerr:gj,ontext(e,t){QA(XA(e,t),e,t)},ontextentity(e,t,n){QA(e,t,n)},oninterpolation(e,t){if(UA)return QA(XA(e,t),e,t);let n=e+KA.delimiterOpen.length,r=t-KA.delimiterClose.length;for(;Yk(LA.charCodeAt(n));)n++;for(;Yk(LA.charCodeAt(r-1));)r--;let i=XA(n,r);i.includes(`&`)&&(i=Z.decodeEntities(i,!1)),uj({type:5,content:hj(i,!1,dj(n,r)),loc:dj(e,t)})},onopentagname(e,t){let n=XA(e,t);RA={type:1,tag:n,ns:Z.getNamespace(n,GA[0],Z.ns),tagType:0,props:[],children:[],loc:dj(e-1,t),codegenNode:void 0}},onopentagend(e){ZA(e)},onclosetag(e,t){let n=XA(e,t);if(!Z.isVoidTag(n)){let r=!1;for(let e=0;e<GA.length;e++)if(GA[e].tag.toLowerCase()===n.toLowerCase()){r=!0,e>0&&gj(24,GA[0].loc.start.offset);for(let n=0;n<=e;n++)$A(GA.shift(),t,n<e);break}r||gj(23,tj(e,60))}},onselfclosingtag(e){let t=RA.tag;RA.isSelfClosing=!0,ZA(e),GA[0]&&GA[0].tag===t&&$A(GA.shift(),e)},onattribname(e,t){Q={type:6,name:XA(e,t),nameLoc:dj(e,t),value:void 0,loc:dj(e)}},ondirname(e,t){let n=XA(e,t),r=n===`.`||n===`:`?`bind`:n===`@`?`on`:n===`#`?`slot`:n.slice(2);if(!UA&&r===``&&gj(26,e),UA||r===``)Q={type:6,name:n,nameLoc:dj(e,t),value:void 0,loc:dj(e)};else if(Q={type:7,name:r,rawName:n,exp:void 0,arg:void 0,modifiers:n===`.`?[Y(`prop`)]:[],loc:dj(e)},r===`pre`){UA=KA.inVPre=!0,WA=RA;let e=RA.props;for(let t=0;t<e.length;t++)e[t].type===7&&(e[t]=mj(e[t]))}},ondirarg(e,t){if(e===t)return;let n=XA(e,t);if(UA&&!xA(Q))Q.name+=n,pj(Q.nameLoc,t);else{let r=n[0]!==`[`;Q.arg=hj(r?n:n.slice(1,-1),r,dj(e,t),r?3:0)}},ondirmodifier(e,t){let n=XA(e,t);if(UA&&!xA(Q))Q.name+=`.`+n,pj(Q.nameLoc,t);else if(Q.name===`slot`){let e=Q.arg;e&&(e.content+=`.`+n,pj(e.loc,t))}else{let r=Y(n,!0,dj(e,t));Q.modifiers.push(r)}},onattribdata(e,t){zA+=XA(e,t),BA<0&&(BA=e),VA=t},onattribentity(e,t,n){zA+=e,BA<0&&(BA=t),VA=n},onattribnameend(e){let t=Q.loc.start.offset,n=XA(t,e);Q.type===7&&(Q.rawName=n),RA.props.some(e=>(e.type===7?e.rawName:e.name)===n)&&gj(2,t)},onattribend(e,t){if(RA&&Q){if(pj(Q.loc,t),e!==0)if(zA.includes(`&`)&&(zA=Z.decodeEntities(zA,!0)),Q.type===6)Q.name===`class`&&(zA=lj(zA).trim()),e===1&&!zA&&gj(13,t),Q.value={type:2,content:zA,loc:e===1?dj(BA,VA):dj(BA-1,VA+1)},KA.inSFCRoot&&RA.tag===`template`&&Q.name===`lang`&&zA&&zA!==`html`&&KA.enterRCDATA(Zk(`</template`),0);else{Q.exp=hj(zA,!1,dj(BA,VA),0,0),Q.name===`for`&&(Q.forParseResult=YA(Q.exp));let e=-1;Q.name===`bind`&&(e=Q.modifiers.findIndex(e=>e.content===`sync`))>-1&&nA(`COMPILER_V_BIND_SYNC`,Z,Q.loc,Q.arg.loc.source)&&(Q.name=`model`,Q.modifiers.splice(e,1))}(Q.type!==7||Q.name!==`pre`)&&RA.props.push(Q)}zA=``,BA=VA=-1},oncomment(e,t){Z.comments&&uj({type:3,content:XA(e,t),loc:dj(e-4,t+3)})},onend(){let e=LA.length;for(let t=0;t<GA.length;t++)$A(GA[t],e-1),gj(24,GA[t].loc.start.offset)},oncdata(e,t){GA[0].ns===0?gj(1,e-9):QA(XA(e,t),e,t)},onprocessinginstruction(e){(GA[0]?GA[0].ns:Z.ns)===0&&gj(21,e-1)}}),qA=/,([^,\}\]]*)(?:,([^,\}\]]*))?$/,JA=/^\(|\)$/g;function YA(e){let t=e.loc,n=e.content,r=n.match(jA);if(!r)return;let[,i,a]=r,o=(e,n,r=!1)=>{let i=t.start.offset+n;return hj(e,!1,dj(i,i+e.length),0,r?1:0)},s={source:o(a.trim(),n.indexOf(a,i.length)),value:void 0,key:void 0,index:void 0,finalized:!1},c=i.trim().replace(JA,``).trim(),l=i.indexOf(c),u=c.match(qA);if(u){c=c.replace(qA,``).trim();let e=u[1].trim(),t;if(e&&(t=n.indexOf(e,l+c.length),s.key=o(e,t,!0)),u[2]){let r=u[2].trim();r&&(s.index=o(r,n.indexOf(r,s.key?t+e.length:l+c.length),!0))}}return c&&(s.value=o(c,l,!0)),s}function XA(e,t){return LA.slice(e,t)}function ZA(e){KA.inSFCRoot&&(RA.innerLoc=dj(e+1,e+1)),uj(RA);let{tag:t,ns:n}=RA;n===0&&Z.isPreTag(t)&&HA++,Z.isVoidTag(t)?$A(RA,e):(GA.unshift(RA),(n===1||n===2)&&(KA.inXML=!0)),RA=null}function QA(e,t,n){{let t=GA[0]&&GA[0].tag;t!==`script`&&t!==`style`&&e.includes(`&`)&&(e=Z.decodeEntities(e,!1))}let r=GA[0]||IA,i=r.children[r.children.length-1];i&&i.type===2?(i.content+=e,pj(i.loc,n)):r.children.push({type:2,content:e,loc:dj(t,n)})}function $A(e,t,n=!1){n?pj(e.loc,tj(t,60)):pj(e.loc,ej(t,62)+1),KA.inSFCRoot&&(e.children.length?e.innerLoc.end=yO({},e.children[e.children.length-1].loc.end):e.innerLoc.end=yO({},e.innerLoc.start),e.innerLoc.source=XA(e.innerLoc.start.offset,e.innerLoc.end.offset));let{tag:r,ns:i,children:a}=e;if(UA||(r===`slot`?e.tagType=2:rj(e)?e.tagType=3:ij(e)&&(e.tagType=1)),KA.inRCDATA||(e.children=sj(a)),i===0&&Z.isIgnoreNewlineTag(r)){let e=a[0];e&&e.type===2&&(e.content=e.content.replace(/^\r?\n/,``))}i===0&&Z.isPreTag(r)&&HA--,WA===e&&(UA=KA.inVPre=!1,WA=null),KA.inXML&&(GA[0]?GA[0].ns:Z.ns)===0&&(KA.inXML=!1);{let t=e.props;if(!KA.inSFCRoot&&tA(`COMPILER_NATIVE_TEMPLATE`,Z)&&e.tag===`template`&&!rj(e)){let t=GA[0]||IA,n=t.children.indexOf(e);t.children.splice(n,1,...e.children)}let n=t.find(e=>e.type===6&&e.name===`inline-template`);n&&nA(`COMPILER_INLINE_TEMPLATE`,Z,n.loc)&&e.children.length&&(n.value={type:2,content:XA(e.children[0].loc.start.offset,e.children[e.children.length-1].loc.end.offset),loc:n.loc})}}function ej(e,t){let n=e;for(;LA.charCodeAt(n)!==t&&n<LA.length-1;)n++;return n}function tj(e,t){let n=e;for(;LA.charCodeAt(n)!==t&&n>=0;)n--;return n}var nj=new Set([`if`,`else`,`else-if`,`for`,`slot`]);function rj({tag:e,props:t}){if(e===`template`){for(let e=0;e<t.length;e++)if(t[e].type===7&&nj.has(t[e].name))return!0}return!1}function ij({tag:e,props:t}){if(Z.isCustomElement(e))return!1;if(e===`component`||aj(e.charCodeAt(0))||oA(e)||Z.isBuiltInComponent&&Z.isBuiltInComponent(e)||Z.isNativeTag&&!Z.isNativeTag(e))return!0;for(let e=0;e<t.length;e++){let n=t[e];if(n.type===6){if(n.name===`is`&&n.value&&(n.value.content.startsWith(`vue:`)||nA(`COMPILER_IS_ON_ELEMENT`,Z,n.loc)))return!0}else if(n.name===`bind`&&vA(n.arg,`is`)&&nA(`COMPILER_IS_ON_ELEMENT`,Z,n.loc))return!0}return!1}function aj(e){return e>64&&e<91}var oj=/\r\n/g;function sj(e){let t=Z.whitespace!==`preserve`,n=!1;for(let r=0;r<e.length;r++){let i=e[r];if(i.type===2)if(HA)i.content=i.content.replace(oj,`
`);else if(MA(i.content)){let a=e[r-1]&&e[r-1].type,o=e[r+1]&&e[r+1].type;!a||!o||t&&(a===3&&(o===3||o===1)||a===1&&(o===3||o===1&&cj(i.content)))?(n=!0,e[r]=null):i.content=` `}else t&&(i.content=lj(i.content))}return n?e.filter(Boolean):e}function cj(e){for(let t=0;t<e.length;t++){let n=e.charCodeAt(t);if(n===10||n===13)return!0}return!1}function lj(e){let t=``,n=!1;for(let r=0;r<e.length;r++)Yk(e.charCodeAt(r))?n||=(t+=` `,!0):(t+=e[r],n=!1);return t}function uj(e){(GA[0]||IA).children.push(e)}function dj(e,t){return{start:KA.getPos(e),end:t==null?t:KA.getPos(t),source:t==null?t:XA(e,t)}}function fj(e){return dj(e.start.offset,e.end.offset)}function pj(e,t){e.end=KA.getPos(t),e.source=XA(e.start.offset,t)}function mj(e){let t={type:6,name:e.rawName,nameLoc:dj(e.loc.start.offset,e.loc.start.offset+e.rawName.length),value:void 0,loc:e.loc};if(e.exp){let n=e.exp.loc;n.end.offset<e.loc.end.offset&&(n.start.offset--,n.start.column--,n.end.offset++,n.end.column++),t.value={type:2,content:e.exp.content,loc:n}}return t}function hj(e,t=!1,n,r=0,i=0){return Y(e,t,n,r)}function gj(e,t,n){Z.onError(X(e,dj(t,t),void 0,n))}function _j(){KA.reset(),RA=null,Q=null,zA=``,BA=-1,VA=-1,GA.length=0}function vj(e,t){if(_j(),LA=e,Z=yO({},FA),t){let e;for(e in t)t[e]!=null&&(Z[e]=t[e])}KA.mode=Z.parseMode===`html`?1:Z.parseMode===`sfc`?2:0,KA.inXML=Z.ns===1||Z.ns===2;let n=t&&t.delimiters;n&&(KA.delimiterOpen=Zk(n[0]),KA.delimiterClose=Zk(n[1]));let r=IA=Mk([],e);return KA.parse(LA),r.loc=dj(0,e.length),r.children=sj(r.children),IA=null,r}function yj(e,t){xj(e,void 0,t,!!bj(e))}function bj(e){let t=e.children.filter(e=>e.type!==3);return t.length===1&&t[0].type===1&&!wA(t[0])?t[0]:null}function xj(e,t,n,r=!1,i=!1){let{children:a}=e,o=[];for(let t=0;t<a.length;t++){let s=a[t];if(s.type===1&&s.tagType===0){let e=r?0:Sj(s,n);if(e>0){if(e>=2){s.codegenNode.patchFlag=-1,o.push(s);continue}}else{let e=s.codegenNode;if(e.type===13){let t=e.patchFlag;if((t===void 0||t===512||t===1)&&Tj(s,n)>=2){let t=Ej(s);t&&(e.props=n.hoist(t))}e.dynamicProps&&=n.hoist(e.dynamicProps)}}}else if(s.type===12&&(r?0:Sj(s,n))>=2){s.codegenNode.type===14&&s.codegenNode.arguments.length>0&&s.codegenNode.arguments.push(`-1`),o.push(s);continue}if(s.type===1){let t=s.tagType===1;t&&n.scopes.vSlot++,xj(s,e,n,!1,i),t&&n.scopes.vSlot--}else if(s.type===11)xj(s,e,n,s.children.length===1,!0);else if(s.type===9)for(let t=0;t<s.branches.length;t++)xj(s.branches[t],e,n,s.branches[t].children.length===1,i)}let s=!1;if(o.length===a.length&&e.type===1){if(e.tagType===0&&e.codegenNode&&e.codegenNode.type===13&&bO(e.codegenNode.children))e.codegenNode.children=c(Pk(e.codegenNode.children)),s=!0;else if(e.tagType===1&&e.codegenNode&&e.codegenNode.type===13&&e.codegenNode.children&&!bO(e.codegenNode.children)&&e.codegenNode.children.type===15){let t=l(e.codegenNode,`default`);t&&(t.returns=c(Pk(t.returns)),s=!0)}else if(e.tagType===3&&t&&t.type===1&&t.tagType===1&&t.codegenNode&&t.codegenNode.type===13&&t.codegenNode.children&&!bO(t.codegenNode.children)&&t.codegenNode.children.type===15){let n=gA(e,`slot`,!0),r=n&&n.arg&&l(t.codegenNode,n.arg);r&&(r.returns=c(Pk(r.returns)),s=!0)}}if(!s)for(let e of o)e.codegenNode=n.cache(e.codegenNode);function c(e){let t=n.cache(e);return t.needArraySpread=!0,t}function l(e,t){if(e.children&&!bO(e.children)&&e.children.type===15){let n=e.children.properties.find(e=>e.key===t||e.key.content===t);return n&&n.value}}o.length&&n.transformHoist&&n.transformHoist(a,n,e)}function Sj(e,t){let{constantCache:n}=t;switch(e.type){case 1:if(e.tagType!==0)return 0;let r=n.get(e);if(r!==void 0)return r;let i=e.codegenNode;if(i.type!==13||i.isBlock&&e.tag!==`svg`&&e.tag!==`foreignObject`&&e.tag!==`math`)return 0;if(i.patchFlag===void 0){let r=3,a=Tj(e,t);if(a===0)return n.set(e,0),0;a<r&&(r=a);for(let i=0;i<e.children.length;i++){let a=Sj(e.children[i],t);if(a===0)return n.set(e,0),0;a<r&&(r=a)}if(r>1)for(let i=0;i<e.props.length;i++){let a=e.props[i];if(a.type===7&&a.name===`bind`&&a.exp){let i=Sj(a.exp,t);if(i===0)return n.set(e,0),0;i<r&&(r=i)}}if(i.isBlock){for(let t=0;t<e.props.length;t++)if(e.props[t].type===7)return n.set(e,0),0;t.removeHelper(YO),t.removeHelper(Wk(t.inSSR,i.isComponent)),i.isBlock=!1,t.helper(Uk(t.inSSR,i.isComponent))}return n.set(e,r),r}else return n.set(e,0),0;case 2:case 3:return 3;case 9:case 11:case 10:return 0;case 5:case 12:return Sj(e.content,t);case 4:return e.constType;case 8:let a=3;for(let n=0;n<e.children.length;n++){let r=e.children[n];if(xO(r)||SO(r))continue;let i=Sj(r,t);if(i===0)return 0;i<a&&(a=i)}return a;case 20:return 2;default:return 0}}var Cj=new Set([pk,mk,hk,gk]);function wj(e,t){if(e.type===14&&!xO(e.callee)&&Cj.has(e.callee)){let n=e.arguments[0];if(n.type===4)return Sj(n,t);if(n.type===14)return wj(n,t)}return 0}function Tj(e,t){let n=3,r=Ej(e);if(r&&r.type===15){let{properties:e}=r;for(let r=0;r<e.length;r++){let{key:i,value:a}=e[r],o=Sj(i,t);if(o===0)return o;o<n&&(n=o);let s;if(s=a.type===4?Sj(a,t):a.type===14?wj(a,t):0,s===0)return s;s<n&&(n=s)}}return n}function Ej(e){let t=e.codegenNode;if(t.type===13)return t.props}function Dj(e,{filename:t=``,prefixIdentifiers:n=!1,hoistStatic:r=!1,hmr:i=!1,cacheHandlers:a=!1,nodeTransforms:o=[],directiveTransforms:s={},transformHoist:c=null,isBuiltInComponent:l=gO,isCustomElement:u=gO,expressionPlugins:d=[],scopeId:f=null,slotted:p=!0,ssr:m=!1,inSSR:h=!1,ssrCssVars:g=``,bindingMetadata:_=hO,inline:v=!1,isTS:y=!1,onError:b=rA,onWarn:x=iA,compatConfig:S}){let C=t.replace(/\?.*$/,``).match(/([^/\\]+)\.\w+$/),w={filename:t,selfName:C&&kO(OO(C[1])),prefixIdentifiers:n,hoistStatic:r,hmr:i,cacheHandlers:a,nodeTransforms:o,directiveTransforms:s,transformHoist:c,isBuiltInComponent:l,isCustomElement:u,expressionPlugins:d,scopeId:f,slotted:p,ssr:m,inSSR:h,ssrCssVars:g,bindingMetadata:_,inline:v,isTS:y,onError:b,onWarn:x,compatConfig:S,root:e,helpers:new Map,components:new Set,directives:new Set,hoists:[],imports:[],cached:[],constantCache:new WeakMap,temps:0,identifiers:Object.create(null),scopes:{vFor:0,vSlot:0,vPre:0,vOnce:0},parent:null,grandParent:null,currentNode:e,childIndex:0,inVOnce:!1,helper(e){let t=w.helpers.get(e)||0;return w.helpers.set(e,t+1),e},removeHelper(e){let t=w.helpers.get(e);if(t){let n=t-1;n?w.helpers.set(e,n):w.helpers.delete(e)}},helperString(e){return`_${kk[w.helper(e)]}`},replaceNode(e){w.parent.children[w.childIndex]=w.currentNode=e},removeNode(e){let t=w.parent.children,n=e?t.indexOf(e):w.currentNode?w.childIndex:-1;!e||e===w.currentNode?(w.currentNode=null,w.onNodeRemoved()):w.childIndex>n&&(w.childIndex--,w.onNodeRemoved()),w.parent.children.splice(n,1)},onNodeRemoved:gO,addIdentifiers(e){},removeIdentifiers(e){},hoist(e){xO(e)&&(e=Y(e)),w.hoists.push(e);let t=Y(`_hoisted_${w.hoists.length}`,!1,e.loc,2);return t.hoisted=e,t},cache(e,t=!1,n=!1){let r=Vk(w.cached.length,e,t,n);return w.cached.push(r),r}};return w.filters=new Set,w}function Oj(e,t){let n=Dj(e,t);jj(e,n),t.hoistStatic&&yj(e,n),t.ssr||kj(e,n),e.helpers=new Set([...n.helpers.keys()]),e.components=[...n.components],e.directives=[...n.directives],e.imports=n.imports,e.hoists=n.hoists,e.temps=n.temps,e.cached=n.cached,e.transformed=!0,e.filters=[...n.filters]}function kj(e,t){let{helper:n}=t,{children:r}=e;if(r.length===1){let n=bj(e);if(n&&n.codegenNode){let r=n.codegenNode;r.type===13&&Gk(r,t),e.codegenNode=r}else e.codegenNode=r[0]}else r.length>1&&(e.codegenNode=Nk(t,n(WO),void 0,e.children,64,void 0,void 0,!0,void 0,!1))}function Aj(e,t){let n=0,r=()=>{n--};for(;n<e.children.length;n++){let i=e.children[n];xO(i)||(t.grandParent=t.parent,t.parent=e,t.childIndex=n,t.onNodeRemoved=r,jj(i,t))}}function jj(e,t){t.currentNode=e;let{nodeTransforms:n}=t,r=[];for(let i=0;i<n.length;i++){let a=n[i](e,t);if(a&&(bO(a)?r.push(...a):r.push(a)),t.currentNode)e=t.currentNode;else return}switch(e.type){case 3:t.ssr||t.helper(ek);break;case 5:t.ssr||t.helper(dk);break;case 9:for(let n=0;n<e.branches.length;n++)jj(e.branches[n],t);break;case 10:case 11:case 1:case 0:Aj(e,t);break}t.currentNode=e;let i=r.length;for(;i--;)r[i]()}function Mj(e,t){let n=xO(e)?t=>t===e:t=>e.test(t);return(e,r)=>{if(e.type===1){let{props:i}=e;if(e.tagType===3&&i.some(SA))return;let a=[];for(let o=0;o<i.length;o++){let s=i[o];if(s.type===7&&n(s.name)){i.splice(o,1),o--;let n=t(e,s,r);n&&a.push(n)}}return a}}}var Nj=`/*@__PURE__*/`,Pj=e=>`${kk[e]}: _${kk[e]}`;function Fj(e,{mode:t=`function`,prefixIdentifiers:n=t===`module`,sourceMap:r=!1,filename:i=`template.vue.html`,scopeId:a=null,optimizeImports:o=!1,runtimeGlobalName:s=`Vue`,runtimeModuleName:c=`vue`,ssrRuntimeModuleName:l=`vue/server-renderer`,ssr:u=!1,isTS:d=!1,inSSR:f=!1}){let p={mode:t,prefixIdentifiers:n,sourceMap:r,filename:i,scopeId:a,optimizeImports:o,runtimeGlobalName:s,runtimeModuleName:c,ssrRuntimeModuleName:l,ssr:u,isTS:d,inSSR:f,source:e.source,code:``,column:1,line:1,offset:0,indentLevel:0,pure:!1,map:void 0,helper(e){return`_${kk[e]}`},push(e,t=-2,n){p.code+=e},indent(){m(++p.indentLevel)},deindent(e=!1){e?--p.indentLevel:m(--p.indentLevel)},newline(){m(p.indentLevel)}};function m(e){p.push(`
`+`  `.repeat(e),0)}return p}function Ij(e,t={}){let n=Fj(e,t);t.onContextCreated&&t.onContextCreated(n);let{mode:r,push:i,prefixIdentifiers:a,indent:o,deindent:s,newline:c,scopeId:l,ssr:u}=n,d=Array.from(e.helpers),f=d.length>0,p=!a&&r!==`module`;if(Lj(e,n),i(`function ${u?`ssrRender`:`render`}(${(u?[`_ctx`,`_push`,`_parent`,`_attrs`]:[`_ctx`,`_cache`]).join(`, `)}) {`),o(),p&&(i(`with (_ctx) {`),o(),f&&(i(`const { ${d.map(Pj).join(`, `)} } = _Vue
`,-1),c())),e.components.length&&(Rj(e.components,`component`,n),(e.directives.length||e.temps>0)&&c()),e.directives.length&&(Rj(e.directives,`directive`,n),e.temps>0&&c()),e.filters&&e.filters.length&&(c(),Rj(e.filters,`filter`,n),c()),e.temps>0){i(`let `);for(let t=0;t<e.temps;t++)i(`${t>0?`, `:``}_temp${t}`)}return(e.components.length||e.directives.length||e.temps)&&(i(`
`,0),c()),u||i(`return `),e.codegenNode?Hj(e.codegenNode,n):i(`null`),p&&(s(),i(`}`)),s(),i(`}`),{ast:e,code:n.code,preamble:``,map:n.map?n.map.toJSON():void 0}}function Lj(e,t){let{ssr:n,prefixIdentifiers:r,push:i,newline:a,runtimeModuleName:o,runtimeGlobalName:s,ssrRuntimeModuleName:c}=t,l=s,u=Array.from(e.helpers);u.length>0&&(i(`const _Vue = ${l}
`,-1),e.hoists.length&&i(`const { ${[QO,$O,ek,tk,nk].filter(e=>u.includes(e)).map(Pj).join(`, `)} } = _Vue
`,-1)),zj(e.hoists,t),a(),i(`return `)}function Rj(e,t,{helper:n,push:r,newline:i,isTS:a}){let o=n(t===`filter`?ok:t===`component`?rk:ak);for(let n=0;n<e.length;n++){let s=e[n],c=s.endsWith(`__self`);c&&(s=s.slice(0,-6)),r(`const ${kA(s,t)} = ${o}(${JSON.stringify(s)}${c?`, true`:``})${a?`!`:``}`),n<e.length-1&&i()}}function zj(e,t){if(!e.length)return;t.pure=!0;let{push:n,newline:r}=t;r();for(let i=0;i<e.length;i++){let a=e[i];a&&(n(`const _hoisted_${i+1} = `),Hj(a,t),r())}t.pure=!1}function Bj(e,t){let n=e.length>3||!1;t.push(`[`),n&&t.indent(),Vj(e,t,n),n&&t.deindent(),t.push(`]`)}function Vj(e,t,n=!1,r=!0){let{push:i,newline:a}=t;for(let o=0;o<e.length;o++){let s=e[o];xO(s)?i(s,-3):bO(s)?Bj(s,t):Hj(s,t),o<e.length-1&&(n?(r&&i(`,`),a()):r&&i(`, `))}}function Hj(e,t){if(xO(e)){t.push(e,-3);return}if(SO(e)){t.push(t.helper(e));return}switch(e.type){case 1:case 9:case 11:Hj(e.codegenNode,t);break;case 2:Uj(e,t);break;case 4:Wj(e,t);break;case 5:Gj(e,t);break;case 12:Hj(e.codegenNode,t);break;case 8:Kj(e,t);break;case 3:Jj(e,t);break;case 13:Yj(e,t);break;case 14:Zj(e,t);break;case 15:Qj(e,t);break;case 17:$j(e,t);break;case 18:eM(e,t);break;case 19:tM(e,t);break;case 20:nM(e,t);break;case 21:Vj(e.body,t,!0,!1);break;case 22:break;case 23:break;case 24:break;case 25:break;case 26:break;case 10:break;default:}}function Uj(e,t){t.push(JSON.stringify(e.content),-3,e)}function Wj(e,t){let{content:n,isStatic:r}=e;t.push(r?JSON.stringify(n):n,-3,e)}function Gj(e,t){let{push:n,helper:r,pure:i}=t;i&&n(Nj),n(`${r(dk)}(`),Hj(e.content,t),n(`)`)}function Kj(e,t){for(let n=0;n<e.children.length;n++){let r=e.children[n];xO(r)?t.push(r,-3):Hj(r,t)}}function qj(e,t){let{push:n}=t;e.type===8?(n(`[`),Kj(e,t),n(`]`)):e.isStatic?n(cA(e.content)?e.content:JSON.stringify(e.content),-2,e):n(`[${e.content}]`,-3,e)}function Jj(e,t){let{push:n,helper:r,pure:i}=t;i&&n(Nj),n(`${r(ek)}(${JSON.stringify(e.content)})`,-3,e)}function Yj(e,t){let{push:n,helper:r,pure:i}=t,{tag:a,props:o,children:s,patchFlag:c,dynamicProps:l,directives:u,isBlock:d,disableTracking:f,isComponent:p}=e,m;c&&(m=String(c)),u&&n(r(sk)+`(`),d&&n(`(${r(YO)}(${f?`true`:``}), `),i&&n(Nj),n(r(d?Wk(t.inSSR,p):Uk(t.inSSR,p))+`(`,-2,e),Vj(Xj([a,o,s,m,l]),t),n(`)`),d&&n(`)`),u&&(n(`, `),Hj(u,t),n(`)`))}function Xj(e){let t=e.length;for(;t--&&e[t]==null;);return e.slice(0,t+1).map(e=>e||`null`)}function Zj(e,t){let{push:n,helper:r,pure:i}=t,a=xO(e.callee)?e.callee:r(e.callee);i&&n(Nj),n(a+`(`,-2,e),Vj(e.arguments,t),n(`)`)}function Qj(e,t){let{push:n,indent:r,deindent:i,newline:a}=t,{properties:o}=e;if(!o.length){n(`{}`,-2,e);return}let s=o.length>1||!1;n(s?`{`:`{ `),s&&r();for(let e=0;e<o.length;e++){let{key:r,value:i}=o[e];qj(r,t),n(`: `),Hj(i,t),e<o.length-1&&(n(`,`),a())}s&&i(),n(s?`}`:` }`)}function $j(e,t){Bj(e.elements,t)}function eM(e,t){let{push:n,indent:r,deindent:i}=t,{params:a,returns:o,body:s,newline:c,isSlot:l}=e;l&&n(`_${kk[wk]}(`),n(`(`,-2,e),bO(a)?Vj(a,t):a&&Hj(a,t),n(`) => `),(c||s)&&(n(`{`),r()),o?(c&&n(`return `),bO(o)?Bj(o,t):Hj(o,t)):s&&Hj(s,t),(c||s)&&(i(),n(`}`)),l&&(e.isNonScopedSlot&&n(`, undefined, true`),n(`)`))}function tM(e,t){let{test:n,consequent:r,alternate:i,newline:a}=e,{push:o,indent:s,deindent:c,newline:l}=t;if(n.type===4){let e=!cA(n.content);e&&o(`(`),Wj(n,t),e&&o(`)`)}else o(`(`),Hj(n,t),o(`)`);a&&s(),t.indentLevel++,a||o(` `),o(`? `),Hj(r,t),t.indentLevel--,a&&l(),a||o(` `),o(`: `);let u=i.type===19;u||t.indentLevel++,Hj(i,t),u||t.indentLevel--,a&&c(!0)}function nM(e,t){let{push:n,helper:r,indent:i,deindent:a,newline:o}=t,{needPauseTracking:s,needArraySpread:c}=e;c&&n(`[...(`),n(`_cache[${e.index}] || (`),s&&(i(),n(`${r(xk)}(-1`),e.inVOnce&&n(`, true`),n(`),`),o(),n(`(`)),n(`_cache[${e.index}] = `),Hj(e.value,t),s&&(n(`).cacheIndex = ${e.index},`),o(),n(`${r(xk)}(1),`),o(),n(`_cache[${e.index}]`),a()),n(`)`),c&&n(`)]`)}RegExp(`\\b`+`arguments,await,break,case,catch,class,const,continue,debugger,default,delete,do,else,export,extends,finally,for,function,if,import,let,new,return,super,switch,throw,try,var,void,while,with,yield`.split(`,`).join(`\\b|\\b`)+`\\b`);var rM=Mj(/^(?:if|else|else-if)$/,(e,t,n)=>iM(e,t,n,(e,t,r)=>{let i=n.parent.children,a=i.indexOf(e),o=0;for(;a-->=0;){let e=i[a];e&&e.type===9&&(o+=e.branches.length)}return()=>{if(r)e.codegenNode=oM(t,o,n);else{let r=cM(e.codegenNode);r.alternate=oM(t,o+e.branches.length-1,n)}}}));function iM(e,t,n,r){if(t.name!==`else`&&(!t.exp||!t.exp.content.trim())){let r=t.exp?t.exp.loc:e.loc;n.onError(X(28,t.loc)),t.exp=Y(`true`,!1,r)}if(t.name===`if`){let i=aM(e,t),a={type:9,loc:fj(e.loc),branches:[i]};if(n.replaceNode(a),r)return r(a,i,!0)}else{let i=n.parent.children,a=i.indexOf(e);for(;a-->=-1;){let o=i[a];if(o&&PA(o)){n.removeNode(o);continue}if(o&&o.type===9){(t.name===`else-if`||t.name===`else`)&&o.branches[o.branches.length-1].condition===void 0&&n.onError(X(30,e.loc)),n.removeNode();let i=aM(e,t);o.branches.push(i);let a=r&&r(o,i,!1);jj(i,n),a&&a(),n.currentNode=null}else n.onError(X(30,e.loc));break}}}function aM(e,t){let n=e.tagType===3;return{type:10,loc:e.loc,condition:t.name===`else`?void 0:t.exp,children:n&&!gA(e,`for`)?e.children:[e],userKey:_A(e,`key`),isTemplateIf:n}}function oM(e,t,n){return e.condition?Bk(e.condition,sM(e,t,n),Rk(n.helper(ek),[`""`,`true`])):sM(e,t,n)}function sM(e,t,n){let{helper:r}=n,i=Ik(`key`,Y(`${t}`,!1,jk,2)),{children:a}=e,o=a[0];if(a.length!==1||o.type!==1)if(a.length===1&&o.type===11){let e=o.codegenNode;return DA(e,i,n),e}else return Nk(n,r(WO),Fk([i]),a,64,void 0,void 0,!0,!1,!1,e.loc);else{let e=o.codegenNode,t=AA(e);return t.type===13&&Gk(t,n),DA(t,i,n),e}}function cM(e){for(;;)if(e.type===19)if(e.alternate.type===19)e=e.alternate;else return e;else e.type===20&&(e=e.value)}var lM=Mj(`for`,(e,t,n)=>{let{helper:r,removeHelper:i}=n;return uM(e,t,n,t=>{let a=Rk(r(ck),[t.source]),o=CA(e),s=gA(e,`memo`),c=_A(e,`key`,!1,!0);c&&c.type;let l=c&&(c.type===6?c.value?Y(c.value.content,!0):void 0:c.exp),u=c&&l?Ik(`key`,l):null,d=t.source.type===4&&t.source.constType>0,f=d?64:c?128:256;return t.codegenNode=Nk(n,r(WO),void 0,a,f,void 0,void 0,!0,!d,!1,e.loc),()=>{let c,{children:f}=t,p=f.length!==1||f[0].type!==1,m=wA(e)?e:o&&e.children.length===1&&wA(e.children[0])?e.children[0]:null;if(m?(c=m.codegenNode,o&&u&&DA(c,u,n)):p?c=Nk(n,r(WO),u?Fk([u]):void 0,e.children,64,void 0,void 0,!0,void 0,!1):(c=f[0].codegenNode,o&&u&&DA(c,u,n),c.isBlock!==!d&&(c.isBlock?(i(YO),i(Wk(n.inSSR,c.isComponent))):i(Uk(n.inSSR,c.isComponent))),c.isBlock=!d,c.isBlock?(r(YO),r(Wk(n.inSSR,c.isComponent))):r(Uk(n.inSSR,c.isComponent))),s){let e=zk(fM(t.parseResult,[Y(`_cached`)]));e.body=Hk([Lk([`const _memo = (`,s.exp,`)`]),Lk([`if (_cached && _cached.el`,...l?[` && _cached.key === `,l]:[],` && ${n.helperString(Ok)}(_cached, _memo)) return _cached`]),Lk([`const _item = `,c]),Y(`_item.memo = _memo`),Y(`return _item`)]),a.arguments.push(e,Y(`_cache`),Y(String(n.cached.length))),n.cached.push(null)}else a.arguments.push(zk(fM(t.parseResult),c,!0))}})});function uM(e,t,n,r){if(!t.exp){n.onError(X(31,t.loc));return}let i=t.forParseResult;if(!i){n.onError(X(32,t.loc));return}dM(i,n);let{addIdentifiers:a,removeIdentifiers:o,scopes:s}=n,{source:c,value:l,key:u,index:d}=i,f={type:11,loc:t.loc,source:c,valueAlias:l,keyAlias:u,objectIndexAlias:d,parseResult:i,children:CA(e)?e.children:[e]};n.replaceNode(f),s.vFor++;let p=r&&r(f);return()=>{s.vFor--,p&&p()}}function dM(e,t){e.finalized||=!0}function fM({value:e,key:t,index:n},r=[]){return pM([e,t,n,...r])}function pM(e){let t=e.length;for(;t--&&!e[t];);return e.slice(0,t+1).map((e,t)=>e||Y(`_`.repeat(t+1),!1))}var mM=Y(`undefined`,!1),hM=(e,t)=>{if(e.type===1&&(e.tagType===1||e.tagType===3)){let n=gA(e,`slot`);if(n)return n.exp,t.scopes.vSlot++,()=>{t.scopes.vSlot--}}},gM=(e,t,n,r)=>zk(e,n,!1,!0,n.length?n[0].loc:r);function _M(e,t,n=gM){t.helper(wk);let{children:r,loc:i}=e,a=[],o=[],s=t.scopes.vSlot>0||t.scopes.vFor>0,c=gA(e,`slot`,!0);if(c){let{arg:e,exp:t}=c;e&&!aA(e)&&(s=!0),a.push(Ik(e||Y(`default`,!0),n(t,void 0,r,i)))}let l=!1,u=!1,d=[],f=new Set,p=0;for(let e=0;e<r.length;e++){let i=r[e],m;if(!CA(i)||!(m=gA(i,`slot`,!0))){i.type!==3&&d.push(i);continue}if(c){t.onError(X(37,m.loc));break}l=!0;let{children:h,loc:g}=i,{arg:_=Y(`default`,!0),exp:v,loc:y}=m,b;aA(_)?b=_?_.content:`default`:s=!0;let x=gA(i,`for`),S=n(v,x,h,g),C,w;if(C=gA(i,`if`))s=!0,o.push(Bk(C.exp,vM(_,S,p++),mM));else if(w=gA(i,/^else(?:-if)?$/,!0)){let n=e,i;for(;n--&&(i=r[n],PA(i)););if(i&&CA(i)&&gA(i,/^(?:else-)?if$/)){let e=o[o.length-1];for(;e.alternate.type===19;)e=e.alternate;e.alternate=w.exp?Bk(w.exp,vM(_,S,p++),mM):vM(_,S,p++)}else t.onError(X(30,w.loc))}else if(x){s=!0;let e=x.forParseResult;e?(dM(e,t),o.push(Rk(t.helper(ck),[e.source,zk(fM(e),vM(_,S),!0)]))):t.onError(X(32,x.loc))}else{if(b){if(f.has(b)){t.onError(X(38,y));continue}f.add(b),b===`default`&&(u=!0)}a.push(Ik(_,S))}}if(!c){let e=(e,r)=>{let a=n(e,void 0,r,i);return t.compatConfig&&(a.isNonScopedSlot=!0),Ik(`default`,a)};l?d.length&&!d.every(NA)&&(u?t.onError(X(39,d[0].loc)):a.push(e(void 0,d))):a.push(e(void 0,r))}let m=s?2:yM(e.children)?3:1,h=Fk(a.concat(Ik(`_`,Y(m+``,!1))),i);return o.length&&(h=Rk(t.helper(uk),[h,Pk(o)])),{slots:h,hasDynamicSlots:s}}function vM(e,t,n){let r=[Ik(`name`,e),Ik(`fn`,t)];return n!=null&&r.push(Ik(`key`,Y(String(n),!0))),Fk(r)}function yM(e){for(let t=0;t<e.length;t++){let n=e[t];switch(n.type){case 1:if(n.tagType===2||yM(n.children))return!0;break;case 9:if(yM(n.branches))return!0;break;case 10:case 11:if(yM(n.children))return!0;break}}return!1}var bM=new WeakMap,xM=(e,t)=>function(){if(e=t.currentNode,!(e.type===1&&(e.tagType===0||e.tagType===1)))return;let{tag:n,props:r}=e,i=e.tagType===1,a=i?SM(e,t):`"${n}"`,o=CO(a)&&a.callee===ik,s,c,l=0,u,d,f,p=o||a===GO||a===KO||!i&&(n===`svg`||n===`foreignObject`||n===`math`);if(r.length>0){let n=CM(e,t,void 0,i,o);s=n.props,l=n.patchFlag,d=n.dynamicPropNames;let r=n.directives;f=r&&r.length?Pk(r.map(e=>EM(e,t))):void 0,n.shouldUseBlock&&(p=!0)}if(e.children.length>0)if(a===qO&&(p=!0,l|=1024),i&&a!==GO&&a!==qO){let{slots:n,hasDynamicSlots:r}=_M(e,t);c=n,r&&(l|=1024)}else if(e.children.length===1&&a!==GO){let n=e.children[0],r=n.type,i=r===5||r===8;i&&Sj(n,t)===0&&(l|=1),c=i||r===2?n:e.children}else c=e.children;d&&d.length&&(u=DM(d)),e.codegenNode=Nk(t,a,s,c,l===0?void 0:l,u,f,!!p,!1,i,e.loc)};function SM(e,t,n=!1){let{tag:r}=e,i=OM(r),a=_A(e,`is`,!1,!0);if(a)if(i||tA(`COMPILER_IS_ON_ELEMENT`,t)){let e;if(a.type===6?e=a.value&&Y(a.value.content,!0):(e=a.exp,e||=Y(`is`,!1,a.arg.loc)),e)return Rk(t.helper(ik),[e])}else a.type===6&&a.value.content.startsWith(`vue:`)&&(r=a.value.content.slice(4));let o=oA(r)||t.isBuiltInComponent(r);return o?(n||t.helper(o),o):(t.helper(rk),t.components.add(r),kA(r,`component`))}function CM(e,t,n=e.props,r,i,a=!1){let{tag:o,loc:s,children:c}=e,l=[],u=[],d=[],f=c.length>0,p=!1,m=0,h=!1,g=!1,_=!1,v=!1,y=!1,b=!1,x=[],S=e=>{l.length&&(u.push(Fk(wM(l),s)),l=[]),e&&u.push(e)},C=()=>{t.scopes.vFor>0&&l.push(Ik(Y(`ref_for`,!0),Y(`true`)))},w=({key:e,value:n})=>{if(aA(e)){let a=e.content,o=vO(a);if(o&&(!r||i)&&a.toLowerCase()!==`onclick`&&a!==`onUpdate:modelValue`&&!wO(a)&&(v=!0),o&&wO(a)&&(b=!0),o&&n.type===14&&(n=n.arguments[0]),n.type===20||(n.type===4||n.type===8)&&Sj(n,t)>0)return;a===`ref`?h=!0:a===`class`?g=!0:a===`style`?_=!0:a!==`key`&&!x.includes(a)&&x.push(a),r&&(a===`class`||a===`style`)&&!x.includes(a)&&x.push(a)}else y=!0};for(let i=0;i<n.length;i++){let c=n[i];if(c.type===6){let{loc:e,name:n,nameLoc:r,value:i}=c;if(n===`ref`&&(h=!0,C()),n===`is`&&(OM(o)||i&&i.content.startsWith(`vue:`)||tA(`COMPILER_IS_ON_ELEMENT`,t)))continue;l.push(Ik(Y(n,!0,r),Y(i?i.content:``,!0,i?i.loc:e)))}else{let{name:n,arg:i,exp:h,loc:g,modifiers:_}=c,v=n===`bind`,b=n===`on`;if(n===`slot`){r||t.onError(X(40,g));continue}if(n===`once`||n===`memo`||n===`is`||v&&vA(i,`is`)&&(OM(o)||tA(`COMPILER_IS_ON_ELEMENT`,t))||b&&a)continue;if((v&&vA(i,`key`)||b&&f&&vA(i,`vue:before-update`))&&(p=!0),v&&vA(i,`ref`)&&C(),!i&&(v||b)){if(y=!0,h)if(v){if(S(),tA(`COMPILER_V_BIND_OBJECT_ORDER`,t)){u.unshift(h);continue}C(),S(),u.push(h)}else S({type:14,loc:g,callee:t.helper(_k),arguments:r?[h]:[h,`true`]});else t.onError(X(v?34:35,g));continue}v&&_.some(e=>e.content===`prop`)&&(m|=32);let x=t.directiveTransforms[n];if(x){let{props:n,needRuntime:r}=x(c,e,t);!a&&n.forEach(w),b&&i&&!aA(i)?S(Fk(n,s)):l.push(...n),r&&(d.push(c),SO(r)&&bM.set(c,r))}else TO(n)||(d.push(c),f&&(p=!0))}}let T;if(u.length?(S(),T=u.length>1?Rk(t.helper(fk),u,s):u[0]):l.length&&(T=Fk(wM(l),s)),y?m|=16:(g&&!r&&(m|=2),_&&!r&&(m|=4),x.length&&(m|=8),v&&(m|=32)),!p&&(m===0||m===32)&&(h||b||d.length>0)&&(m|=512),!t.inSSR&&T)switch(T.type){case 15:let e=-1,n=-1,r=!1;for(let t=0;t<T.properties.length;t++){let i=T.properties[t].key;aA(i)?i.content===`class`?e=t:i.content===`style`&&(n=t):i.isHandlerKey||(r=!0)}let i=T.properties[e],a=T.properties[n];r?T=Rk(t.helper(hk),[T]):(i&&!aA(i.value)&&(i.value=Rk(t.helper(pk),[i.value])),a&&(_||a.value.type===4&&a.value.content.trim()[0]===`[`||a.value.type===17)&&(a.value=Rk(t.helper(mk),[a.value])));break;case 14:break;default:T=Rk(t.helper(hk),[Rk(t.helper(gk),[T])]);break}return{props:T,directives:d,patchFlag:m,dynamicPropNames:x,shouldUseBlock:p}}function wM(e){let t=new Map,n=[];for(let r=0;r<e.length;r++){let i=e[r];if(i.key.type===8||!i.key.isStatic){n.push(i);continue}let a=i.key.content,o=t.get(a);o?(a===`style`||a===`class`||vO(a))&&TM(o,i):(t.set(a,i),n.push(i))}return n}function TM(e,t){e.value.type===17?e.value.elements.push(t.value):e.value=Pk([e.value,t.value],e.loc)}function EM(e,t){let n=[],r=bM.get(e);r?n.push(t.helperString(r)):(t.helper(ak),t.directives.add(e.name),n.push(kA(e.name,`directive`)));let{loc:i}=e;if(e.exp&&n.push(e.exp),e.arg&&(e.exp||n.push(`void 0`),n.push(e.arg)),Object.keys(e.modifiers).length){e.arg||(e.exp||n.push(`void 0`),n.push(`void 0`));let t=Y(`true`,!1,i);n.push(Fk(e.modifiers.map(e=>Ik(e,t)),i))}return Pk(n,e.loc)}function DM(e){let t=`[`;for(let n=0,r=e.length;n<r;n++)t+=JSON.stringify(e[n]),n<r-1&&(t+=`, `);return t+`]`}function OM(e){return e===`component`||e===`Component`}var kM=(e,t)=>{if(wA(e)){let{children:n,loc:r}=e,{slotName:i,slotProps:a}=AM(e,t),o=[t.prefixIdentifiers?`_ctx.$slots`:`$slots`,i,`{}`,`undefined`,`true`],s=2;a&&(o[2]=a,s=3),n.length&&(o[3]=zk([],n,!1,!1,r),s=4),t.scopeId&&!t.slotted&&(s=5),o.splice(s),e.codegenNode=Rk(t.helper(lk),o,r)}};function AM(e,t){let n=`"default"`,r,i=[];for(let t=0;t<e.props.length;t++){let r=e.props[t];r.type===6?r.value&&(r.name===`name`?n=JSON.stringify(r.value.content):(r.name=OO(r.name),i.push(r))):r.name===`bind`&&vA(r.arg,`name`)?r.exp?n=r.exp:r.arg&&r.arg.type===4&&(n=r.exp=Y(OO(r.arg.content),!1,r.arg.loc)):(r.name===`bind`&&r.arg&&aA(r.arg)&&(r.arg.content=OO(r.arg.content)),i.push(r))}if(i.length>0){let{props:n,directives:a}=CM(e,t,i,!1,!1);r=n,a.length&&t.onError(X(36,a[0].loc))}return{slotName:n,slotProps:r}}var jM=(e,t,n,r)=>{let{loc:i,modifiers:a,arg:o}=e;!e.exp&&!a.length&&n.onError(X(35,i));let s;if(o.type===4)if(o.isStatic){let e=o.content;e.startsWith(`vue:`)&&(e=`vnode-${e.slice(4)}`),s=Y(t.tagType!==0||e.startsWith(`vnode`)||!/[A-Z]/.test(e)?AO(OO(e)):`on:${e}`,!0,o.loc)}else s=Lk([`${n.helperString(bk)}(`,o,`)`]);else s=o,s.children.unshift(`${n.helperString(bk)}(`),s.children.push(`)`);let c=e.exp;c&&!c.content.trim()&&(c=void 0);let l=n.cacheHandlers&&!c&&!n.inVOnce;if(c){let e=pA(c),t=!(e||hA(c)),n=c.content.includes(`;`);(t||l&&e)&&(c=Lk([`${t?`$event`:`(...args)`} => ${n?`{`:`(`}`,c,n?`}`:`)`]))}let u={props:[Ik(s,c||Y(`() => {}`,!1,i))]};return r&&(u=r(u)),l&&(u.props[0].value=n.cache(u.props[0].value)),u.props.forEach(e=>e.key.isHandlerKey=!0),u},MM=(e,t,n)=>{let{modifiers:r,loc:i}=e,a=e.arg,{exp:o}=e;return o&&o.type===4&&!o.content.trim()&&(o=void 0),a.type===4?a.isStatic||(a.content=a.content?`${a.content} || ""`:`""`):(a.children.unshift(`(`),a.children.push(`) || ""`)),r.some(e=>e.content===`camel`)&&(a.type===4?a.isStatic?a.content=OO(a.content):a.content=`${n.helperString(vk)}(${a.content})`:(a.children.unshift(`${n.helperString(vk)}(`),a.children.push(`)`))),n.inSSR||(r.some(e=>e.content===`prop`)&&NM(a,`.`),r.some(e=>e.content===`attr`)&&NM(a,`^`)),{props:[Ik(a,o)]}},NM=(e,t)=>{e.type===4?e.isStatic?e.content=t+e.content:e.content=`\`${t}\${${e.content}}\``:(e.children.unshift(`'${t}' + (`),e.children.push(`)`))},PM=(e,t)=>{if(e.type===0||e.type===1||e.type===11||e.type===10)return()=>{let n=e.children,r,i=!1;for(let e=0;e<n.length;e++){let t=n[e];if(bA(t)){i=!0;for(let i=e+1;i<n.length;i++){let a=n[i];if(bA(a))r||=n[e]=Lk([t],t.loc),r.children.push(` + `,a),n.splice(i,1),i--;else{r=void 0;break}}}}if(!(!i||n.length===1&&(e.type===0||e.type===1&&e.tagType===0&&!e.props.find(e=>e.type===7&&!t.directiveTransforms[e.name])&&e.tag!==`template`)))for(let e=0;e<n.length;e++){let r=n[e];if(bA(r)||r.type===8){let i=[];(r.type!==2||r.content!==` `)&&i.push(r),!t.ssr&&Sj(r,t)===0&&i.push(`1`),n[e]={type:12,content:r,loc:r.loc,codegenNode:Rk(t.helper(tk),i)}}}}},FM=new WeakSet,IM=(e,t)=>{if(e.type===1&&gA(e,`once`,!0))return FM.has(e)||t.inVOnce||t.inSSR?void 0:(FM.add(e),t.inVOnce=!0,t.helper(xk),()=>{t.inVOnce=!1;let e=t.currentNode;e.codegenNode&&=t.cache(e.codegenNode,!0,!0)})},LM=(e,t,n)=>{let{exp:r,arg:i}=e;if(!r)return n.onError(X(41,e.loc)),RM();let a=r.loc.source.trim(),o=r.type===4?r.content:a,s=n.bindingMetadata[a];if(s===`props`||s===`props-aliased`)return n.onError(X(44,r.loc)),RM();if(s===`literal-const`||s===`setup-const`)return n.onError(X(45,r.loc)),RM();if(!o.trim()||!pA(r))return n.onError(X(42,r.loc)),RM();let c=i||Y(`modelValue`,!0),l=i?aA(i)?`onUpdate:${OO(i.content)}`:Lk([`"onUpdate:" + `,i]):`onUpdate:modelValue`,u;u=Lk([`${n.isTS?`($event: any)`:`$event`} => ((`,r,`) = $event)`]);let d=[Ik(c,e.exp),Ik(l,u)];if(e.modifiers.length&&t.tagType===1){let t=e.modifiers.map(e=>e.content).map(e=>(cA(e)?e:JSON.stringify(e))+`: true`).join(`, `),n=i?aA(i)?`${i.content}Modifiers`:Lk([i,` + "Modifiers"`]):`modelModifiers`;d.push(Ik(n,Y(`{ ${t} }`,!1,e.loc,2)))}return RM(d)};function RM(e=[]){return{props:e}}var zM=/[\w).+\-_$\]]/,BM=(e,t)=>{tA(`COMPILER_FILTERS`,t)&&(e.type===5?VM(e.content,t):e.type===1&&e.props.forEach(e=>{e.type===7&&e.name!==`for`&&e.exp&&VM(e.exp,t)}))};function VM(e,t){if(e.type===4)HM(e,t);else for(let n=0;n<e.children.length;n++){let r=e.children[n];typeof r==`object`&&(r.type===4?HM(r,t):r.type===8?VM(e,t):r.type===5&&VM(r.content,t))}}function HM(e,t){let n=e.content,r=!1,i=!1,a=!1,o=!1,s=0,c=0,l=0,u=0,d,f,p,m,h=[];for(p=0;p<n.length;p++)if(f=d,d=n.charCodeAt(p),r)d===39&&f!==92&&(r=!1);else if(i)d===34&&f!==92&&(i=!1);else if(a)d===96&&f!==92&&(a=!1);else if(o)d===47&&f!==92&&(o=!1);else if(d===124&&n.charCodeAt(p+1)!==124&&n.charCodeAt(p-1)!==124&&!s&&!c&&!l)m===void 0?(u=p+1,m=n.slice(0,p).trim()):g();else{switch(d){case 34:i=!0;break;case 39:r=!0;break;case 96:a=!0;break;case 40:l++;break;case 41:l--;break;case 91:c++;break;case 93:c--;break;case 123:s++;break;case 125:s--;break}if(d===47){let e=p-1,t;for(;e>=0&&(t=n.charAt(e),t===` `);e--);(!t||!zM.test(t))&&(o=!0)}}m===void 0?m=n.slice(0,p).trim():u!==0&&g();function g(){h.push(n.slice(u,p).trim()),u=p+1}if(h.length){for(p=0;p<h.length;p++)m=UM(m,h[p],t);e.content=m,e.ast=void 0}}function UM(e,t,n){n.helper(ok);let r=t.indexOf(`(`);if(r<0)return n.filters.add(t),`${kA(t,`filter`)}(${e})`;{let i=t.slice(0,r),a=t.slice(r+1);return n.filters.add(i),`${kA(i,`filter`)}(${e}${a===`)`?a:`,`+a}`}}var WM=new WeakSet,GM=(e,t)=>{if(e.type===1){let n=gA(e,`memo`);return!n||WM.has(e)||t.inSSR?void 0:(WM.add(e),()=>{let r=e.codegenNode||t.currentNode.codegenNode;r&&r.type===13&&(e.tagType!==1&&Gk(r,t),e.codegenNode=Rk(t.helper(Dk),[n.exp,zk(void 0,r),`_cache`,String(t.cached.length)]),t.cached.push(null))})}},KM=(e,t)=>{if(e.type===1){for(let n of e.props)if(n.type===7&&n.name===`bind`&&(!n.exp||n.exp.type===4&&!n.exp.content.trim())&&n.arg){let e=n.arg;if(e.type!==4||!e.isStatic)t.onError(X(53,e.loc)),n.exp=Y(``,!0,e.loc);else{let t=OO(e.content);(lA.test(t[0])||t[0]===`-`)&&(n.exp=Y(t,!1,e.loc))}}}};function qM(e){return[[KM,IM,rM,GM,lM,...[BM],...[],kM,xM,hM,PM],{on:jM,bind:MM,model:LM}]}function JM(e,t={}){let n=t.onError||rA,r=t.mode===`module`;t.prefixIdentifiers===!0?n(X(48)):r&&n(X(49)),t.cacheHandlers&&n(X(50)),t.scopeId&&!r&&n(X(51));let i=yO({},t,{prefixIdentifiers:!1}),a=xO(e)?vj(e,i):e,[o,s]=qM();return Oj(a,yO({},i,{nodeTransforms:[...o,...t.nodeTransforms||[]],directiveTransforms:yO({},s,t.directiveTransforms||{})})),Ij(a,i)}var YM=()=>({props:[]}),XM=Symbol(``),ZM=Symbol(``),QM=Symbol(``),$M=Symbol(``),eN=Symbol(``),tN=Symbol(``),nN=Symbol(``),rN=Symbol(``),iN=Symbol(``),aN=Symbol(``);Ak({[XM]:`vModelRadio`,[ZM]:`vModelCheckbox`,[QM]:`vModelText`,[$M]:`vModelSelect`,[eN]:`vModelDynamic`,[tN]:`withModifiers`,[nN]:`withKeys`,[rN]:`vShow`,[iN]:`Transition`,[aN]:`TransitionGroup`});var oN;function sN(e,t=!1){return oN||=document.createElement(`div`),t?(oN.innerHTML=`<div foo="${e.replace(/"/g,`&quot;`)}">`,oN.children[0].getAttribute(`foo`)):(oN.innerHTML=e,oN.textContent)}var cN={parseMode:`html`,isVoidTag:UO,isNativeTag:e=>BO(e)||VO(e)||HO(e),isPreTag:e=>e===`pre`,isIgnoreNewlineTag:e=>e===`pre`||e===`textarea`,decodeEntities:sN,isBuiltInComponent:e=>{if(e===`Transition`||e===`transition`)return iN;if(e===`TransitionGroup`||e===`transition-group`)return aN},getNamespace(e,t,n){let r=t?t.ns:n;if(t&&r===2)if(t.tag===`annotation-xml`){if(e===`svg`)return 1;t.props.some(e=>e.type===6&&e.name===`encoding`&&e.value!=null&&(e.value.content===`text/html`||e.value.content===`application/xhtml+xml`))&&(r=0)}else /^m(?:[ions]|text)$/.test(t.tag)&&e!==`mglyph`&&e!==`malignmark`&&(r=0);else t&&r===1&&(t.tag===`foreignObject`||t.tag===`desc`||t.tag===`title`)&&(r=0);if(r===0){if(e===`svg`)return 1;if(e===`math`)return 2}return r}},lN=e=>{e.type===1&&e.props.forEach((t,n)=>{t.type===6&&t.name===`style`&&t.value&&(e.props[n]={type:7,name:`bind`,arg:Y(`style`,!0,t.loc),exp:uN(t.value.content,t.loc),modifiers:[],loc:t.loc})})},uN=(e,t)=>{let n=FO(e);return Y(JSON.stringify(n),!1,t,3)};function dN(e,t){return X(e,t,void 0)}var fN=(e,t,n)=>{let{exp:r,loc:i}=e;return r||n.onError(dN(54,i)),t.children.length&&(n.onError(dN(55,i)),t.children.length=0),{props:[Ik(Y(`innerHTML`,!0,i),r||Y(``,!0))]}},pN=(e,t,n)=>{let{exp:r,loc:i}=e;return r||n.onError(dN(56,i)),t.children.length&&(n.onError(dN(57,i)),t.children.length=0),{props:[Ik(Y(`textContent`,!0),r?Sj(r,n)>0?r:Rk(n.helperString(dk),[r],i):Y(``,!0))]}},mN=(e,t,n)=>{let r=LM(e,t,n);if(!r.props.length||t.tagType===1)return r;e.arg&&n.onError(dN(59,e.arg.loc));let{tag:i}=t,a=n.isCustomElement(i);if(i===`input`||i===`textarea`||i===`select`||a){let o=QM,s=!1;if(i===`input`||a){let r=_A(t,`type`);if(r){if(r.type===7)o=eN;else if(r.value)switch(r.value.content){case`radio`:o=XM;break;case`checkbox`:o=ZM;break;case`file`:s=!0,n.onError(dN(60,e.loc));break;default:break}}else yA(t)&&(o=eN)}else i===`select`&&(o=$M);s||(r.needRuntime=n.helper(o))}else n.onError(dN(58,e.loc));return r.props=r.props.filter(e=>!(e.key.type===4&&e.key.content===`modelValue`)),r},hN=mO(`passive,once,capture`),gN=mO(`stop,prevent,self,ctrl,shift,alt,meta,exact,middle`),_N=mO(`left,right`),vN=mO(`onkeyup,onkeydown,onkeypress`),yN=(e,t,n,r)=>{let i=[],a=[],o=[];for(let s=0;s<t.length;s++){let c=t[s].content;c===`native`&&nA(`COMPILER_V_ON_NATIVE`,n,r)||hN(c)?o.push(c):_N(c)?aA(e)?vN(e.content.toLowerCase())?i.push(c):a.push(c):(i.push(c),a.push(c)):gN(c)?a.push(c):i.push(c)}return{keyModifiers:i,nonKeyModifiers:a,eventOptionModifiers:o}},bN=(e,t)=>aA(e)&&e.content.toLowerCase()===`onclick`?Y(t,!0):e.type===4?e:Lk([`(`,e,`) === "onClick" ? "${t}" : (`,e,`)`]),xN=(e,t,n)=>jM(e,t,n,t=>{let{modifiers:r}=e;if(!r.length)return t;let{key:i,value:a}=t.props[0],{keyModifiers:o,nonKeyModifiers:s,eventOptionModifiers:c}=yN(i,r,n,e.loc);if(s.includes(`right`)&&(i=bN(i,`onContextmenu`)),s.includes(`middle`)&&(i=bN(i,`onMouseup`)),s.length&&(a=Rk(n.helper(tN),[a,JSON.stringify(s)])),o.length&&(!aA(i)||vN(i.content.toLowerCase()))&&(a=Rk(n.helper(nN),[a,JSON.stringify(o)])),c.length){let e=c.map(kO).join(``);i=aA(i)?Y(`${i.content}${e}`,!0):Lk([`(`,i,`) + "${e}"`])}return{props:[Ik(i,a)]}}),SN=(e,t,n)=>{let{exp:r,loc:i}=e;return r||n.onError(dN(62,i)),{props:[],needRuntime:n.helper(rN)}},CN=(e,t)=>{e.type===1&&e.tagType===0&&(e.tag===`script`||e.tag===`style`)&&t.removeNode()},wN=[lN,...[]],TN={cloak:YM,html:fN,text:pN,model:mN,on:xN,show:SN};function EN(e,t={}){return JM(e,yO({},cN,t,{nodeTransforms:[CN,...wN,...t.nodeTransforms||[]],directiveTransforms:yO({},TN,t.directiveTransforms||{}),transformHoist:null}))}var DN=Object.create(null);function ON(e,t){if(!xO(e))if(e.nodeType)e=e.innerHTML;else return gO;let n=jO(e,t),r=DN[n];if(r)return r;if(e[0]===`#`){let t=document.querySelector(e);e=t?t.innerHTML:``}let i=yO({hoistStatic:!0,onError:void 0,onWarn:gO},t);!i.isCustomElement&&typeof customElements<`u`&&(i.isCustomElement=e=>!!customElements.get(e));let{code:a}=EN(e,i),o=Function(`Vue`,a)(aE);return o._rc=!0,DN[n]=o}sT(ON);var kN=Ph(),AN=Ev(kN.get());kN.subscribe(()=>{AN.value=kN.get()});function jN(){kN.reset(),AN.value=kN.get()}var MN={created(){if(!this.$options.remember)return;Array.isArray(this.$options.remember)&&(this.$options.remember={data:this.$options.remember}),typeof this.$options.remember==`string`&&(this.$options.remember={data:[this.$options.remember]}),typeof this.$options.remember.data==`string`&&(this.$options.remember={data:[this.$options.remember.data]});let e=this.$options.remember.key instanceof Function?this.$options.remember.key.call(this):this.$options.remember.key,t=Eg.restore(e),n=this.$options.remember.data.filter(e=>!(this[e]!==null&&typeof this[e]==`object`&&this[e].__rememberable===!1)),r=e=>this[e]!==null&&typeof this[e]==`object`&&typeof this[e].__remember==`function`&&typeof this[e].__restore==`function`;n.forEach(i=>{this[i]!==void 0&&t!==void 0&&t[i]!==void 0&&(r(i)?this[i].__restore(t[i]):this[i]=t[i]),this.$watch(i,()=>{Eg.remember(n.reduce((e,t)=>({...e,[t]:mf(r(t)?this[t].__remember():this[t])}),{}),e)},{immediate:!0,deep:!0})})}};function NN(e){let{data:t,rememberKey:n}=e,{precognitionEndpoint:r}=e,i=typeof t==`function`,a=()=>i?t():t,o=n?Eg.restore(n):null,s=mf(o?.data??mf(a())),c=e=>e,l=null,u=null,d=()=>u??hP.get(`form.withAllErrors`),f,p=!1,m=[],h=pv({...mf(s),isDirty:!1,errors:{},hasErrors:!1,processing:!1,progress:null,wasSuccessful:!1,recentlySuccessful:!1,withPrecognition(...e){r=Th.createWayfinderCallback(...e);let t=this,n=Ip(e=>{let{method:t,url:n}=r(),i=mf(c(this.data()));return e[t](n,i)},mf(s));l=n,n.on(`validatingChanged`,()=>{t.validating=n.validating()}).on(`validatedChanged`,()=>{t.__valid=n.valid()}).on(`touchedChanged`,()=>{t.__touched=n.touched()}).on(`errorsChanged`,()=>{let e=d()?n.errors():Lp(n.errors());this.errors={},this.setError(e),t.__valid=n.valid()});let i=(e,t)=>(t(e),e);return Object.assign(t,{__touched:[],__valid:[],validating:!1,validator:()=>n,withAllErrors:()=>i(t,()=>u=!0),valid:e=>t.__valid.includes(e),invalid:e=>e in this.errors,setValidationTimeout:e=>i(t,()=>n.setTimeout(e)),validateFiles:()=>i(t,()=>n.validateFiles()),withoutFileValidation:()=>i(t,()=>n.withoutFileValidation()),touch:(e,...r)=>(Array.isArray(e)?n.touch(e):typeof e==`string`?n.touch([e,...r]):n.touch(e),t),touched:e=>typeof e==`string`?t.__touched.includes(e):t.__touched.length>0,validate:(e,r)=>{if(typeof e==`object`&&!(`target`in e)&&(r=e,e=void 0),e===void 0)n.validate(r);else{let t=zp(e),i=c(this.data());n.validate(t,jf(i,t),r)}return t},setErrors:e=>i(t,()=>this.setError(e)),forgetError:e=>i(t,()=>this.clearErrors(zp(e)))}),t},data(){return Object.keys(s).reduce((e,t)=>qf(e,t,jf(this,t)),{})},transform(e){return c=e,this},defaults(e,t){if(i)throw Error("You cannot call `defaults()` when using a function to define your form data.");return p=!0,e===void 0?(s=mf(this.data()),this.isDirty=!1):s=typeof e==`string`?qf(mf(s),e,t):Object.assign({},mf(s),e),l?.defaults(s),this},reset(...e){let t=mf(i?mf(a()):s);return e.length===0?(i&&(s=t),Object.assign(this,t)):e.filter(e=>zf(t,e)).forEach(e=>{i&&qf(s,e,jf(t,e)),qf(this,e,jf(t,e))}),l?.reset(...e),this},setError(e,t){let n=typeof e==`string`?{[e]:t}:e;return Object.assign(this.errors,n),this.hasErrors=Object.keys(this.errors).length>0,l?.setErrors(n),this},clearErrors(...e){return this.errors=Object.keys(this.errors).reduce((t,n)=>({...t,...e.length>0&&!e.includes(n)?{[n]:this.errors[n]}:{}}),{}),this.hasErrors=Object.keys(this.errors).length>0,l&&(e.length===0?l.setErrors({}):e.forEach(l.forgetError)),this},resetAndClearErrors(...e){return this.reset(...e),this.clearErrors(...e),this},__rememberable:n===null,__remember(){let e=this.data();if(m.length>0){let t={...e};return m.forEach(e=>delete t[e]),{data:t,errors:this.errors}}return{data:e,errors:this.errors}},__restore(e){Object.assign(this,e.data),this.setError(e.errors)}});return o?.errors&&h.setError(o.errors),Bb(h,()=>{h.isDirty=!Sf(h.data(),s)},{immediate:!0,deep:!0}),Bb(h,e=>{if(!n)return;let t=Eg.restore(n),r=mf(e.__remember());Sf(t,r)||Eg.remember(r,n)},{immediate:!0,deep:!0}),r&&h.withPrecognition(r),{form:h,setDefaults:e=>{s=e},getTransform:()=>c,getPrecognitionEndpoint:()=>r??null,markAsSuccessful:()=>{h.clearErrors(),h.wasSuccessful=!0,h.recentlySuccessful=!0,f=setTimeout(()=>h.recentlySuccessful=!1,hP.get(`form.recentlySuccessfulDuration`))},wasDefaultsCalledInOnSuccess:()=>p,resetDefaultsCalledInOnSuccess:()=>{p=!1},setRememberExcludeKeys:e=>{m=e},resetBeforeSubmit:()=>{h.wasSuccessful=!1,h.recentlySuccessful=!1,clearTimeout(f)},finishProcessing:()=>{h.processing=!1,h.progress=null},withAllErrors:{enabled:d,enable:()=>{u=!0}}}}var PN=null,FN=!1;function IN(e){if(FN)return;PN===null&&(FN=!0,PN=new Set(Object.keys(LN({}))),FN=!1);let t=Object.keys(e).filter(e=>PN.has(e));t.length>0&&console.error(`[Inertia] useForm() data contains field(s) that conflict with form properties: ${t.map(e=>`"${e}"`).join(`, `)}. These fields will be overwritten by form methods/properties. Please rename these fields.`)}function LN(...e){let{rememberKey:t,data:n,precognitionEndpoint:r}=Th.parseUseFormArguments(...e);IN(mf(typeof n==`function`?n():n));let i=null,a=null,{form:o,setDefaults:s,getTransform:c,getPrecognitionEndpoint:l,markAsSuccessful:u,wasDefaultsCalledInOnSuccess:d,resetDefaultsCalledInOnSuccess:f,setRememberExcludeKeys:p,resetBeforeSubmit:m,finishProcessing:h}=NN({data:n,rememberKey:t,precognitionEndpoint:r}),g=o,_=e=>(t,n={})=>{g.submit(e,t,n)};return Object.assign(g,{submit(...e){let{method:t,url:n,options:r}=Th.parseSubmitArguments(e,l());f();let o={...r,onCancelToken:e=>(i=e,r.onCancelToken?.(e)),onBefore:e=>(m(),r.onBefore?.(e)),onStart:e=>(g.processing=!0,r.onStart?.(e)),onProgress:e=>(g.progress=e??null,r.onProgress?.(e)),onSuccess:async e=>{u();let t=r.onSuccess?await r.onSuccess(e):null;return d()||(s(mf(g.data())),g.isDirty=!1),t},onError:e=>(g.clearErrors().setError(e),r.onError?.(e)),onCancel:()=>r.onCancel?.(),onFinish:e=>(h(),i=null,r.onFinish?.(e))};o.optimistic=o.optimistic??a??void 0,a=null;let p=c()(g.data());t===`delete`?Eg.delete(n,{...o,data:p}):Eg[t](n,p,o)},get:_(`get`),post:_(`post`),put:_(`put`),patch:_(`patch`),delete:_(`delete`),cancel(){i&&i.cancel()},dontRemember(...e){return p(e),g},optimistic(e){return a=e,g}}),l(),g}function RN(e){if(!e)return!1;if(typeof e==`function`)return!0;if(typeof e==`object`){let t=e;return typeof t.render==`function`||typeof t.setup==`function`||typeof t.template==`string`||`__file`in t||`__name`in t}return!1}function zN(e){if(typeof e!=`function`)return!1;let t=e;return t.length===2&&t.prototype===void 0}var BN=Ev(void 0),$=Ev(),VN=null,HN=Dv(null),UN=Ev(void 0),WN,GN=vx({name:`Inertia`,props:{initialPage:{type:Object,required:!0},initialComponent:{type:Object,required:!1},resolveComponent:{type:Function,required:!1},titleCallback:{type:Function,required:!1,default:e=>e},onHeadUpdate:{type:Function,required:!1,default:()=>()=>{}},defaultLayout:{type:Function,required:!1}},setup({initialPage:e,initialComponent:t,resolveComponent:n,titleCallback:r,onHeadUpdate:i,defaultLayout:a}){BN.value=t?Sv(t):void 0,$.value={...e,flash:e.flash??{}},UN.value=void 0;let o=typeof window>`u`;return WN=Nh(o,r||(e=>e),i||(()=>{})),o||(Eg.init({initialPage:e,resolveComponent:n,swapComponent:async e=>{e.preserveState||jN(),BN.value=Sv(e.component),$.value=e.page,UN.value=e.preserveState?UN.value:Date.now()},onFlash:e=>{$.value={...$.value,flash:e}}}),Eg.on(`navigate`,()=>WN.forceUpdate())),()=>{if(BN.value){BN.value.inheritAttrs=!!BN.value.inheritAttrs;let e=hT(BN.value,{...$.value.props,key:UN.value});if(HN.value&&=(BN.value.layout=HN.value,null),BN.value.layout&&zN(BN.value.layout))return BN.value.layout(hT,e);let t,n=null,r=BN.value.layout;if(typeof r==`function`&&r.length<=1&&r.prototype===void 0){let e=r($.value.props);Rh(e,RN)?(t=a?.($.value.component,$.value),n=e):t=e}else Rh(r,RN)?(t=a?.($.value.component,$.value),n=r):t=r??a?.($.value.component,$.value);if(t){let r=Vh(t,RN,BN.value.layout&&!n?zN:void 0);if(n&&(r=r.map(e=>({...e,props:{...e.props,...n}}))),r.length>0){let t=o?{shared:{},named:{}}:AN.value;return r.reduceRight((e,n)=>{let r=n.component;return r.inheritAttrs=!!r.inheritAttrs,hT(r,{...$.value.props,...n.props,...t.shared,...n.name&&t.named[n.name]||{}},()=>e)},e)}}return e}}}}),KN={install(e){Eg.form=LN,Object.defineProperty(e.config.globalProperties,`$inertia`,{get:()=>Eg}),Object.defineProperty(e.config.globalProperties,`$page`,{get:()=>$.value}),Object.defineProperty(e.config.globalProperties,`$headManager`,{get:()=>WN}),e.mixin(MN)}};function qN(){return VN||=pv({props:J(()=>$.value?.props),url:J(()=>$.value?.url),component:J(()=>$.value?.component),version:J(()=>$.value?.version),clearHistory:J(()=>$.value?.clearHistory),deferredProps:J(()=>$.value?.deferredProps),mergeProps:J(()=>$.value?.mergeProps),prependProps:J(()=>$.value?.prependProps),deepMergeProps:J(()=>$.value?.deepMergeProps),matchPropsOn:J(()=>$.value?.matchPropsOn),rememberedState:J(()=>$.value?.rememberedState),encryptHistory:J(()=>$.value?.encryptHistory),scrollProps:J(()=>$.value?.scrollProps),flash:J(()=>$.value?.flash)}),VN}async function JN({id:e=`app`,resolve:t,setup:n,title:r,progress:i={},page:a,render:o,defaults:s={},http:c,layout:l,withApp:u}={}){hP.replace(s),c&&gh.setClient(c);let d=typeof window>`u`,f=(e,n)=>Promise.resolve(t(e,n)).then(e=>e.default||e);if(d&&!a&&!o)return async(t,i)=>{let a=[],o={initialPage:t,initialComponent:await f(t.component,t),resolveComponent:f,titleCallback:r,onHeadUpdate:e=>a=e,defaultLayout:l},s;n?s=n({el:null,App:GN,props:o,plugin:KN}):(s=lO({render:()=>hT(GN,o)}),s.use(KN),u&&u(s,{ssr:!0}));let c=Tg(e,t,await i(s));return{head:a,body:c}};let p=a||bm(e),m=[],h=await Promise.all([f(p.component,p),Eg.decryptHistory().catch(()=>{})]).then(([t])=>{let i={initialPage:p,initialComponent:t,resolveComponent:f,titleCallback:r,onHeadUpdate:d?e=>m=e:void 0,defaultLayout:l};if(d)return n({el:null,App:GN,props:i,plugin:KN});let a=document.getElementById(e);if(n)return n({el:a,App:GN,props:i,plugin:KN});if(a.hasAttribute(`data-server-rendered`)){let e=lO({render:()=>hT(GN,i)});e.use(KN),u&&u(e,{ssr:!1}),e.mount(a)}else{let e=cO({render:()=>hT(GN,i)});e.use(KN),u&&u(e,{ssr:!1}),e.mount(a)}});if(!d&&i&&_g(i),d&&o&&h){let t=Tg(e,p,await o(h));return{head:m,body:t}}}var YN=(e,t,n)=>e.length===0&&t.length===0?!0:e.length>0?n.some(t=>e.includes(t)):n.some(e=>!t.includes(e)),XN=vx({name:`Deferred`,props:{data:{type:[String,Array],required:!0}},slots:Object,setup(e,{slots:t}){let n=Ev(!1),r=new Set,i=qN(),a=null,o=null;return aS(()=>{let t=Array.isArray(e.data)?e.data:[e.data];a=Eg.on(`start`,e=>{let i=e.detail.visit;i.preserveState===!0&&Wm(i.url,window.location)&&YN(i.only,i.except,t)&&(r.add(i),n.value=!0)}),o=Eg.on(`finish`,e=>{let t=e.detail.visit;r.has(t)&&(r.delete(t),n.value=r.size>0)})}),lS(()=>{a?.(),o?.(),r.clear()}),()=>{let r=Array.isArray(e.data)?e.data:[e.data];if(!t.fallback)throw Error("`<Deferred>` requires a `<template #fallback>` slot");return r.every(e=>jf(i.props,e)!==void 0)?t.default?.({reloading:n.value}):t.fallback({})}}}),ZN=()=>void 0,QN=Symbol(`InertiaFormContext`),$N=vx({name:`Form`,slots:Object,props:{action:{type:[String,Object],default:``},method:{type:String,default:`get`},headers:{type:Object,default:()=>({})},queryStringArrayFormat:{type:String,default:`brackets`},errorBag:{type:[String,null],default:null},showProgress:{type:Boolean,default:!0},transform:{type:Function,default:e=>e},options:{type:Object,default:()=>({})},resetOnError:{type:[Boolean,Array],default:!1},resetOnSuccess:{type:[Boolean,Array],default:!1},setDefaultsOnSuccess:{type:Boolean,default:!1},onCancelToken:{type:Function,default:ZN},onBefore:{type:Function,default:ZN},onStart:{type:Function,default:ZN},onProgress:{type:Function,default:ZN},onFinish:{type:Function,default:ZN},onCancel:{type:Function,default:ZN},onSuccess:{type:Function,default:ZN},onError:{type:Function,default:ZN},onSubmitComplete:{type:Function,default:ZN},disableWhileProcessing:{type:Boolean,default:!1},invalidateCacheTags:{type:[String,Array],default:()=>[]},validateFiles:{type:Boolean,default:!1},validationTimeout:{type:Number,default:1500},optimistic:{type:Function,default:void 0},withAllErrors:{type:Boolean,default:null},component:{type:String,default:null},instant:{type:Boolean,default:!1}},setup(e,{slots:t,attrs:n,expose:r}){let i=()=>{let[t,n]=h();return e.transform(n)},a=LN({}).withPrecognition(()=>s.value,()=>h()[0]).transform(i).setValidationTimeout(e.validationTimeout);e.validateFiles&&a.validateFiles(),(e.withAllErrors??Vp.get(`form.withAllErrors`))&&a.withAllErrors();let o=Ev(),s=J(()=>Gm(e.action)?e.action.method:e.method.toLowerCase()),c=J(()=>e.component?e.component:e.instant&&Gm(e.action)?Km(e.action):null),l=Ev(!1),u=Ev(new FormData),d=e=>{e.type===`reset`&&e.detail?.[vg]&&e.preventDefault(),l.value=e.type===`reset`?!1:!Sf(m(),jh(u.value))},f=[`input`,`change`,`reset`];aS(()=>{u.value=p(),a.defaults(m()),f.forEach(e=>o.value.addEventListener(e,d))}),Bb(()=>e.validateFiles,e=>e?a.validateFiles():a.withoutFileValidation()),Bb(()=>e.validationTimeout,e=>a.setValidationTimeout(e)),cS(()=>f.forEach(e=>o.value?.removeEventListener(e,d)));let p=e=>new FormData(o.value,e),m=e=>jh(p(e)),h=t=>Bm(s.value,Gm(e.action)?e.action.url:e.action,m(t),e.queryStringArrayFormat),g=t=>{let[n,r]=h(t);if(t?.getAttribute(`formtarget`)===`_blank`&&s.value===`get`){window.open(n,`_blank`);return}let o=e=>{e&&(e===!0?_():e.length>0&&_(...e))},l={headers:e.headers,queryStringArrayFormat:e.queryStringArrayFormat,errorBag:e.errorBag,showProgress:e.showProgress,invalidateCacheTags:e.invalidateCacheTags,component:c.value,optimistic:e.optimistic?t=>e.optimistic(t,r):void 0,onCancelToken:e.onCancelToken,onBefore:e.onBefore,onStart:e.onStart,onProgress:e.onProgress,onFinish:e.onFinish,onCancel:e.onCancel,onSuccess:(...t)=>{e.onSuccess?.(...t),e.onSubmitComplete?.(x),o(e.resetOnSuccess),e.setDefaultsOnSuccess===!0&&b()},onError:(...t)=>{e.onError?.(...t),o(e.resetOnError)},...e.options};a.transform(()=>e.transform(r)).submit(s.value,n,l),a.transform(i)},_=(...e)=>{wg(o.value,u.value,e),a.reset(...e)},v=(...e)=>{a.clearErrors(...e)},y=(...e)=>{v(...e),_(...e)},b=()=>{u.value=p(),l.value=!1},x={get errors(){return a.errors},get hasErrors(){return a.hasErrors},get processing(){return a.processing},get progress(){return a.progress},get wasSuccessful(){return a.wasSuccessful},get recentlySuccessful(){return a.recentlySuccessful},get validating(){return a.validating},clearErrors:v,resetAndClearErrors:y,setError:(e,t)=>a.setError(typeof e==`string`?{[e]:t}:e),get isDirty(){return l.value},reset:_,submit:g,defaults:b,getData:m,getFormData:p,touch:a.touch,valid:a.valid,invalid:a.invalid,touched:a.touched,validate:(t,n)=>a.validate(...Th.mergeHeadersForValidation(t,n,e.headers)),validator:()=>a.validator()};return r(x),Mb(QN,x),()=>hT(`form`,{...n,ref:o,action:Gm(e.action)?e.action.url:e.action,method:s.value,onSubmit:e=>{e.preventDefault(),g(e.submitter)},inert:e.disableWhileProcessing&&a.processing},t.default?t.default(x):[])}});function eP(e){return typeof e.type==`string`&&[`area`,`base`,`br`,`col`,`embed`,`hr`,`img`,`input`,`keygen`,`link`,`meta`,`param`,`source`,`track`,`wbr`].indexOf(e.type)>-1}function tP(e){e.props=e.props||{},e.props[`data-inertia`]=e.props[`head-key`]===void 0?``:e.props[`head-key`];let t=Object.keys(e.props).reduce((t,n)=>{let r=String(e.props[n]);return[`key`,`head-key`].includes(n)?t:r===``?t+` ${n}`:t+` ${n}="${$f(r)}"`},``);return`<${String(e.type)}${t}>`}function nP(e){let{children:t}=e;return typeof t==`string`?t:Array.isArray(t)?t.reduce((e,t)=>e+cP(t),``):``}function rP(e){return typeof e.type==`function`}function iP(e){return typeof e.type==`object`}function aP(e){return/(comment|cmt)/i.test(e.type.toString())}function oP(e){return/(fragment|fgt|symbol\(\))/i.test(e.type.toString())}function sP(e){return/(text|txt)/i.test(e.type.toString())}function cP(e){if(sP(e))return String(e.children);if(oP(e)||aP(e))return``;let t=tP(e);return e.children&&(t+=nP(e)),eP(e)||(t+=`</${String(e.type)}>`),t}function lP(e,t){return t&&!e.find(e=>e.startsWith(`<title`))&&e.push(`<title data-inertia="">${t}</title>`),e}function uP(e,t){return lP(e.flatMap(e=>dP(e)).map(e=>cP(e)).filter(e=>e),t)}function dP(e){return rP(e)?dP(e.type()):iP(e)?(console.warn(`Using components in the <Head> component is not supported.`),[]):sP(e)&&e.children?e:oP(e)&&e.children?e.children.flatMap(e=>dP(e)):aP(e)?[]:e}var fP=vx({props:{title:{type:String,required:!1}},setup(e,{slots:t}){let n=WN.createProvider();return cS(()=>{n.disconnect()}),()=>{n.update(uP(t.default?t.default():[],e.title))}}}),pP=()=>{},mP=vx({name:`Link`,props:{as:{type:[String,Object],default:`a`},data:{type:Object,default:()=>({})},href:{type:[String,Object],default:``},method:{type:String,default:`get`},replace:{type:Boolean,default:!1},preserveScroll:{type:[Boolean,String,Function],default:!1},preserveState:{type:[Boolean,String,Function],default:null},preserveUrl:{type:Boolean,default:!1},only:{type:Array,default:()=>[]},except:{type:Array,default:()=>[]},headers:{type:Object,default:()=>({})},queryStringArrayFormat:{type:String,default:`brackets`},async:{type:Boolean,default:!1},prefetch:{type:[Boolean,String,Array],default:!1},cacheFor:{type:[Number,String,Array],default:0},onStart:{type:Function,default:pP},onProgress:{type:Function,default:pP},onFinish:{type:Function,default:pP},onBefore:{type:Function,default:pP},onCancel:{type:Function,default:pP},onSuccess:{type:Function,default:pP},onError:{type:Function,default:pP},onCancelToken:{type:Function,default:pP},onPrefetching:{type:Function,default:pP},onPrefetched:{type:Function,default:pP},cacheTags:{type:[String,Array],default:()=>[]},viewTransition:{type:[Boolean,Object],default:!1},component:{type:String,default:null},instant:{type:Boolean,default:!1},pageProps:{type:[Object,Function],default:null}},setup(e,{slots:t,attrs:n}){let r=Ev(0),i=Ev(),a=J(()=>e.prefetch===!0?[`hover`]:e.prefetch===!1?[]:Array.isArray(e.prefetch)?e.prefetch:[e.prefetch]),o=J(()=>e.cacheFor===0?a.value.length===1&&a.value[0]===`click`?0:hP.get(`prefetch.cacheFor`):e.cacheFor);aS(()=>{a.value.includes(`mount`)&&g()}),lS(()=>{clearTimeout(i.value)});let s=J(()=>Gm(e.href)?e.href.method:(e.method??`get`).toLowerCase()),c=J(()=>typeof e.as!=`string`||e.as.toLowerCase()!==`a`?e.as:s.value===`get`?e.as.toLowerCase():`button`),l=J(()=>Bm(s.value,Gm(e.href)?e.href.url:e.href,e.data||{},e.queryStringArrayFormat)),u=J(()=>l.value[0]),d=J(()=>l.value[1]),f=J(()=>e.component?e.component:e.instant&&Gm(e.href)?Km(e.href):null),p=J(()=>c.value===`button`?{type:`button`}:c.value===`a`||typeof c.value!=`string`?{href:u.value}:{}),m=J(()=>({data:d.value,method:s.value,replace:e.replace,preserveScroll:e.preserveScroll,preserveState:e.preserveState??s.value!==`get`,preserveUrl:e.preserveUrl,only:e.only,except:e.except,headers:e.headers,async:e.async,component:f.value,pageProps:e.pageProps})),h=J(()=>({...m.value,viewTransition:e.viewTransition,onCancelToken:e.onCancelToken,onBefore:e.onBefore,onStart:t=>{r.value++,e.onStart?.(t)},onProgress:e.onProgress,onFinish:t=>{r.value--,e.onFinish?.(t)},onCancel:e.onCancel,onSuccess:e.onSuccess,onError:e.onError})),g=()=>{Eg.prefetch(u.value,{...m.value,onPrefetching:e.onPrefetching,onPrefetched:e.onPrefetched},{cacheFor:o.value,cacheTags:e.cacheTags})},_={onClick:e=>{Uh(e)&&(e.preventDefault(),Eg.visit(u.value,h.value))}},v={onMouseenter:()=>{i.value=setTimeout(()=>{g()},hP.get(`prefetch.hoverDelay`))},onMouseleave:()=>{clearTimeout(i.value)},onClick:_.onClick},y={onMousedown:e=>{Uh(e)&&(e.preventDefault(),g())},onKeydown:e=>{Wh(e)&&(e.preventDefault(),g())},onMouseup:e=>{Uh(e)&&(e.preventDefault(),Eg.visit(u.value,h.value))},onKeyup:e=>{Wh(e)&&(e.preventDefault(),Eg.visit(u.value,h.value))},onClick:e=>{Uh(e)&&e.preventDefault()}};return()=>hT(c.value,{...n,...p.value,"data-loading":r.value>0?``:void 0,...a.value.includes(`hover`)?v:a.value.includes(`click`)?y:_},t)}}),hP=Vp.extend({}),gP=(e,t)=>{let n=e.__vccOpts||e;for(let[e,r]of t)n[e]=r;return n};export{Gy as $,Pb as A,wS as B,CS as C,Yw as D,vx as E,aS as F,xx as G,yx as H,lS as I,kb as J,Bb as K,xw as L,WS as M,Uw as N,Fw as O,db as P,Fy as Q,Mb as R,Ew as S,q as T,fC as U,vS as V,zS as W,By as X,Ab as Y,Vy as Z,Iw as _,fP as a,Dv as at,Dw as b,qN as c,Av as ct,VE as d,_d as dt,Zg as et,KD as f,cd as ft,ex as g,O as gt,hw as h,ed as ht,$N as i,Ev as it,Nb as j,hT as k,yE as l,jv as lt,QD as m,sd as mt,JN as n,Qg as nt,mP as o,H as ot,RD as p,od as pt,Lb as q,XN as r,pv as rt,LN as s,Mv as st,gP as t,Tv as tt,cO as u,Eg as ut,J as v,Lw as w,zw as x,Nw as y,SS as z};