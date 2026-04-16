const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["./bg-BG.js","./bg2.js","./cs-CZ.js","./cs2.js","./de-DE.js","./de2.js","./en-AU.js","./en2.js","./en-GB.js","./en-US.js","./es-ES.js","./es2.js","./fr-FR.js","./fr2.js","./fr-BE.js","./hu-HU.js","./hu2.js","./it-IT.js","./it2.js","./nl-BE.js","./nl2.js","./nl-NL.js","./pl-PL.js","./pl2.js","./ro-RO.js","./ro2.js","./ru-RU.js","./ru2.js","./sk-SK.js","./sk2.js","./tr-TR.js","./tr.js","./uk-UA.js","./uk2.js","./bg-BG2.js","./bg3.js","./cs-CZ2.js","./cs3.js","./de-DE2.js","./de3.js","./en-AU2.js","./en3.js","./en-GB2.js","./en-US2.js","./es-ES2.js","./es3.js","./fr-FR2.js","./fr3.js","./fr-BE2.js","./hu-HU2.js","./hu3.js","./it-IT2.js","./it3.js","./nl-BE2.js","./nl3.js","./nl-NL2.js","./pl-PL2.js","./pl3.js","./ro-RO2.js","./ro3.js","./ru-RU2.js","./ru3.js","./sk-SK2.js","./sk3.js","./uk-UA2.js","./uk3.js"])))=>i.map(i=>d[i]);
import{a as e,c as t,d as n,f as r,i,l as a,o,p as s,r as c,s as l,u}from"./Queue-wGK97jCw.js";import{t as d}from"./decorate-DpHfxayW.js";import{a as f,c as p,d as m,f as h,i as g,n as _,p as v,r as y,t as b}from"./lit.js";import{a as x,i as S,o as C,r as w,t as T}from"./decorators.js";import{a as E,i as D,n as ee,o as te,s as ne}from"./nav-item-D3exy0bq.js";import"./nav-list-DezDYLKE.js";var re=``,ie=``;function ae(e){re=e}function oe(e=``){if(!re){let e=document.querySelector(`[data-webawesome]`);if(e?.hasAttribute(`data-webawesome`)){let t=new URL(e.getAttribute(`data-webawesome`)??``,window.location.href).pathname;ae(t)}else{let e=[...document.getElementsByTagName(`script`)].find(e=>e.src.endsWith(`webawesome.js`)||e.src.endsWith(`webawesome.loader.js`)||e.src.endsWith(`webawesome.ssr-loader.js`));e&&ae(String(e.getAttribute(`src`)).split(`/`).slice(0,-1).join(`/`))}}return re.replace(/\/$/,``)+(e?`/${e.replace(/^\//,``)}`:``)}function se(e){ie=e}function ce(){if(!ie){let e=document.querySelector(`[data-fa-kit-code]`);e&&se(e.getAttribute(`data-fa-kit-code`)||``)}return ie}var le=`7.0.1`;function ue(e,t,n){let r=ce(),i=r.length>0,a=`solid`;return t===`notdog`?(n===`solid`&&(a=`solid`),n===`duo-solid`&&(a=`duo-solid`),`https://ka-p.fontawesome.com/releases/v${le}/svgs/notdog-${a}/${e}.svg?token=${encodeURIComponent(r)}`):t===`chisel`?`https://ka-p.fontawesome.com/releases/v${le}/svgs/chisel-regular/${e}.svg?token=${encodeURIComponent(r)}`:t===`etch`?`https://ka-p.fontawesome.com/releases/v${le}/svgs/etch-solid/${e}.svg?token=${encodeURIComponent(r)}`:t===`jelly`?(n===`regular`&&(a=`regular`),n===`duo-regular`&&(a=`duo-regular`),n===`fill-regular`&&(a=`fill-regular`),`https://ka-p.fontawesome.com/releases/v${le}/svgs/jelly-${a}/${e}.svg?token=${encodeURIComponent(r)}`):t===`slab`?((n===`solid`||n===`regular`)&&(a=`regular`),n===`press-regular`&&(a=`press-regular`),`https://ka-p.fontawesome.com/releases/v${le}/svgs/slab-${a}/${e}.svg?token=${encodeURIComponent(r)}`):t===`thumbprint`?`https://ka-p.fontawesome.com/releases/v${le}/svgs/thumbprint-light/${e}.svg?token=${encodeURIComponent(r)}`:t===`whiteboard`?`https://ka-p.fontawesome.com/releases/v${le}/svgs/whiteboard-semibold/${e}.svg?token=${encodeURIComponent(r)}`:(t===`classic`&&(n===`thin`&&(a=`thin`),n===`light`&&(a=`light`),n===`regular`&&(a=`regular`),n===`solid`&&(a=`solid`)),t===`sharp`&&(n===`thin`&&(a=`sharp-thin`),n===`light`&&(a=`sharp-light`),n===`regular`&&(a=`sharp-regular`),n===`solid`&&(a=`sharp-solid`)),t===`duotone`&&(n===`thin`&&(a=`duotone-thin`),n===`light`&&(a=`duotone-light`),n===`regular`&&(a=`duotone-regular`),n===`solid`&&(a=`duotone`)),t===`sharp-duotone`&&(n===`thin`&&(a=`sharp-duotone-thin`),n===`light`&&(a=`sharp-duotone-light`),n===`regular`&&(a=`sharp-duotone-regular`),n===`solid`&&(a=`sharp-duotone-solid`)),t===`brands`&&(a=`brands`),i?`https://ka-p.fontawesome.com/releases/v${le}/svgs/${a}/${e}.svg?token=${encodeURIComponent(r)}`:`https://ka-f.fontawesome.com/releases/v${le}/svgs/${a}/${e}.svg`)}var de={name:`default`,resolver:(e,t=`classic`,n=`solid`)=>ue(e,t,n),mutator:(e,t)=>{if(t?.family&&!e.hasAttribute(`data-duotone-initialized`)){let{family:n,variant:r}=t;if(n===`duotone`||n===`sharp-duotone`||n===`notdog`&&r===`duo-solid`||n===`jelly`&&r===`duo-regular`||n===`thumbprint`){let n=[...e.querySelectorAll(`path`)],r=n.find(e=>!e.hasAttribute(`opacity`)),i=n.find(e=>e.hasAttribute(`opacity`));if(!r||!i)return;if(r.setAttribute(`data-duotone-primary`,``),i.setAttribute(`data-duotone-secondary`,``),t.swapOpacity&&r&&i){let e=i.getAttribute(`opacity`)||`0.4`;r.style.setProperty(`--path-opacity`,e),i.style.setProperty(`--path-opacity`,`1`)}e.setAttribute(`data-duotone-initialized`,``)}}}},fe=`modulepreload`,pe=function(e,t){return new URL(e,t).href},me={},O=function(e,t,n){let r=Promise.resolve();if(t&&t.length>0){let e=document.getElementsByTagName(`link`),i=document.querySelector(`meta[property=csp-nonce]`),a=i?.nonce||i?.getAttribute(`nonce`);function o(e){return Promise.all(e.map(e=>Promise.resolve(e).then(e=>({status:`fulfilled`,value:e}),e=>({status:`rejected`,reason:e}))))}r=o(t.map(t=>{if(t=pe(t,n),t in me)return;me[t]=!0;let r=t.endsWith(`.css`),i=r?`[rel="stylesheet"]`:``;if(n)for(let n=e.length-1;n>=0;n--){let i=e[n];if(i.href===t&&(!r||i.rel===`stylesheet`))return}else if(document.querySelector(`link[href="${t}"]${i}`))return;let o=document.createElement(`link`);if(o.rel=r?`stylesheet`:fe,r||(o.as=`script`),o.crossOrigin=``,o.href=t,a&&o.setAttribute(`nonce`,a),document.head.appendChild(o),r)return new Promise((e,n)=>{o.addEventListener(`load`,e),o.addEventListener(`error`,()=>n(Error(`Unable to preload CSS for ${t}`)))})}))}function i(e){let t=new Event(`vite:preloadError`,{cancelable:!0});if(t.payload=e,window.dispatchEvent(t),!t.defaultPrevented)throw e}return r.then(t=>{for(let e of t||[])e.status===`rejected`&&i(e.reason);return e().catch(i)})};new MutationObserver(e=>{for(let{addedNodes:t}of e)for(let e of t)e.nodeType===Node.ELEMENT_NODE&&he(e)});async function he(e){let t=e instanceof Element?e.tagName.toLowerCase():``,n=t?.startsWith(`wa-`),r=[...e.querySelectorAll(`:not(:defined)`)].map(e=>e.tagName.toLowerCase()).filter(e=>e.startsWith(`wa-`));n&&!customElements.get(t)&&r.push(t);let i=[...new Set(r)],a=await Promise.allSettled(i.map(e=>ge(e)));for(let e of a)e.status===`rejected`&&console.warn(e.reason);await new Promise(requestAnimationFrame),e.dispatchEvent(new CustomEvent(`wa-discovery-complete`,{bubbles:!1,cancelable:!1,composed:!0}))}function ge(e){if(customElements.get(e))return Promise.resolve();let t=e.replace(/^wa-/i,``),n=oe(`components/${t}/${t}.js`);return new Promise((t,r)=>{O(()=>import(n).then(()=>t()),[],import.meta.url).catch(()=>r(Error(`Unable to autoload <${e}> from ${n}`)))})}var _e=new Set,ve=new Map,ye,be=`ltr`,xe=`en`,Se=typeof MutationObserver<`u`&&typeof document<`u`&&document.documentElement!==void 0;if(Se){let e=new MutationObserver(we);be=document.documentElement.dir||`ltr`,xe=document.documentElement.lang||navigator.language,e.observe(document.documentElement,{attributes:!0,attributeFilter:[`dir`,`lang`]})}function Ce(...e){e.map(e=>{let t=e.$code.toLowerCase();ve.has(t)?ve.set(t,Object.assign(Object.assign({},ve.get(t)),e)):ve.set(t,e),ye||=e}),we()}function we(){Se&&(be=document.documentElement.dir||`ltr`,xe=document.documentElement.lang||navigator.language),[..._e.keys()].map(e=>{typeof e.requestUpdate==`function`&&e.requestUpdate()})}var Te=class{constructor(e){this.host=e,this.host.addController(this)}hostConnected(){_e.add(this.host)}hostDisconnected(){_e.delete(this.host)}dir(){return`${this.host.dir||be}`.toLowerCase()}lang(){return`${this.host.lang||xe}`.toLowerCase()}getTranslationData(e){let t=new Intl.Locale(e.replace(/_/g,`-`)),n=t?.language.toLowerCase(),r=(t?.region)?.toLowerCase()??``;return{locale:t,language:n,region:r,primary:ve.get(`${n}-${r}`),secondary:ve.get(n)}}exists(e,t){let{primary:n,secondary:r}=this.getTranslationData(t.lang??this.lang());return t=Object.assign({includeFallback:!1},t),!!(n&&n[e]||r&&r[e]||t.includeFallback&&ye&&ye[e])}term(e,...t){let{primary:n,secondary:r}=this.getTranslationData(this.lang()),i;if(n&&n[e])i=n[e];else if(r&&r[e])i=r[e];else if(ye&&ye[e])i=ye[e];else return console.error(`No translation found for: ${String(e)}`),String(e);return typeof i==`function`?i(...t):i}date(e,t){return e=new Date(e),new Intl.DateTimeFormat(this.lang(),t).format(e)}number(e,t){return e=Number(e),isNaN(e)?``:new Intl.NumberFormat(this.lang(),t).format(e)}relativeTime(e,t,n){return new Intl.RelativeTimeFormat(this.lang(),n).format(e,t)}},Ee={$code:`en`,$name:`English`,$dir:`ltr`,carousel:`Carousel`,clearEntry:`Clear entry`,close:`Close`,copied:`Copied`,copy:`Copy`,currentValue:`Current value`,error:`Error`,goToSlide:(e,t)=>`Go to slide ${e} of ${t}`,hidePassword:`Hide password`,loading:`Loading`,nextSlide:`Next slide`,numOptionsSelected:e=>e===0?`No options selected`:e===1?`1 option selected`:`${e} options selected`,pauseAnimation:`Pause animation`,playAnimation:`Play animation`,previousSlide:`Previous slide`,progress:`Progress`,remove:`Remove`,resize:`Resize`,scrollableRegion:`Scrollable region`,scrollToEnd:`Scroll to end`,scrollToStart:`Scroll to start`,selectAColorFromTheScreen:`Select a color from the screen`,showPassword:`Show password`,slideNum:e=>`Slide ${e}`,toggleColorFormat:`Toggle color format`,zoomIn:`Zoom in`,zoomOut:`Zoom out`};Ce(Ee);var De=Ee,Oe=class extends Te{};Ce(De);function ke(e){return`data:image/svg+xml,${encodeURIComponent(e)}`}var Ae={solid:{check:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M434.8 70.1c14.3 10.4 17.5 30.4 7.1 44.7l-256 352c-5.5 7.6-14 12.3-23.4 13.1s-18.5-2.7-25.1-9.3l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l101.5 101.5 234-321.7c10.4-14.3 30.4-17.5 44.7-7.1z"/></svg>`,"chevron-down":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M201.4 406.6c12.5 12.5 32.8 12.5 45.3 0l192-192c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L224 338.7 54.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l192 192z"/></svg>`,"chevron-left":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l192 192c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L77.3 256 246.6 86.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-192 192z"/></svg>`,"chevron-right":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M311.1 233.4c12.5 12.5 12.5 32.8 0 45.3l-192 192c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L243.2 256 73.9 86.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l192 192z"/></svg>`,circle:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0z"/></svg>`,eyedropper:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M341.6 29.2l-101.6 101.6-9.4-9.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3l-9.4-9.4 101.6-101.6c39-39 39-102.2 0-141.1s-102.2-39-141.1 0zM55.4 323.3c-15 15-23.4 35.4-23.4 56.6l0 42.4-26.6 39.9c-8.5 12.7-6.8 29.6 4 40.4s27.7 12.5 40.4 4l39.9-26.6 42.4 0c21.2 0 41.6-8.4 56.6-23.4l109.4-109.4-45.3-45.3-109.4 109.4c-3 3-7.1 4.7-11.3 4.7l-36.1 0 0-36.1c0-4.2 1.7-8.3 4.7-11.3l109.4-109.4-45.3-45.3-109.4 109.4z"/></svg>`,"grip-vertical":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M128 40c0-22.1-17.9-40-40-40L40 0C17.9 0 0 17.9 0 40L0 88c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zm0 192c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zM0 424l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zM320 40c0-22.1-17.9-40-40-40L232 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zM192 232l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zM320 424c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48z"/></svg>`,indeterminate:`<svg part="indeterminate-icon" class="icon" viewBox="0 0 16 16"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" stroke-linecap="round"><g stroke="currentColor" stroke-width="2"><g transform="translate(2.285714 6.857143)"><path d="M10.2857143,1.14285714 L1.14285714,1.14285714"/></g></g></g></svg>`,minus:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M0 256c0-17.7 14.3-32 32-32l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 288c-17.7 0-32-14.3-32-32z"/></svg>`,pause:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M48 32C21.5 32 0 53.5 0 80L0 432c0 26.5 21.5 48 48 48l64 0c26.5 0 48-21.5 48-48l0-352c0-26.5-21.5-48-48-48L48 32zm224 0c-26.5 0-48 21.5-48 48l0 352c0 26.5 21.5 48 48 48l64 0c26.5 0 48-21.5 48-48l0-352c0-26.5-21.5-48-48-48l-64 0z"/></svg>`,play:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M91.2 36.9c-12.4-6.8-27.4-6.5-39.6 .7S32 57.9 32 72l0 368c0 14.1 7.5 27.2 19.6 34.4s27.2 7.5 39.6 .7l336-184c12.8-7 20.8-20.5 20.8-35.1s-8-28.1-20.8-35.1l-336-184z"/></svg>`,star:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M309.5-18.9c-4.1-8-12.4-13.1-21.4-13.1s-17.3 5.1-21.4 13.1L193.1 125.3 33.2 150.7c-8.9 1.4-16.3 7.7-19.1 16.3s-.5 18 5.8 24.4l114.4 114.5-25.2 159.9c-1.4 8.9 2.3 17.9 9.6 23.2s16.9 6.1 25 2L288.1 417.6 432.4 491c8 4.1 17.7 3.3 25-2s11-14.2 9.6-23.2L441.7 305.9 556.1 191.4c6.4-6.4 8.6-15.8 5.8-24.4s-10.1-14.9-19.1-16.3L383 125.3 309.5-18.9z"/></svg>`,user:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M224 248a120 120 0 1 0 0-240 120 120 0 1 0 0 240zm-29.7 56C95.8 304 16 383.8 16 482.3 16 498.7 29.3 512 45.7 512l356.6 0c16.4 0 29.7-13.3 29.7-29.7 0-98.5-79.8-178.3-178.3-178.3l-59.4 0z"/></svg>`,xmark:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M55.1 73.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L147.2 256 9.9 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192.5 301.3 329.9 438.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.8 256 375.1 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192.5 210.7 55.1 73.4z"/></svg>`},regular:{"circle-question":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M464 256a208 208 0 1 0 -416 0 208 208 0 1 0 416 0zM0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0zm256-80c-17.7 0-32 14.3-32 32 0 13.3-10.7 24-24 24s-24-10.7-24-24c0-44.2 35.8-80 80-80s80 35.8 80 80c0 47.2-36 67.2-56 74.5l0 3.8c0 13.3-10.7 24-24 24s-24-10.7-24-24l0-8.1c0-20.5 14.8-35.2 30.1-40.2 6.4-2.1 13.2-5.5 18.2-10.3 4.3-4.2 7.7-10 7.7-19.6 0-17.7-14.3-32-32-32zM224 368a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>`,"circle-xmark":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M256 48a208 208 0 1 1 0 416 208 208 0 1 1 0-416zm0 464a256 256 0 1 0 0-512 256 256 0 1 0 0 512zM167 167c-9.4 9.4-9.4 24.6 0 33.9l55 55-55 55c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l55-55 55 55c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-55-55 55-55c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-55 55-55-55c-9.4-9.4-24.6-9.4-33.9 0z"/></svg>`,copy:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M384 336l-192 0c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l133.5 0c4.2 0 8.3 1.7 11.3 4.7l58.5 58.5c3 3 4.7 7.1 4.7 11.3L400 320c0 8.8-7.2 16-16 16zM192 384l192 0c35.3 0 64-28.7 64-64l0-197.5c0-17-6.7-33.3-18.7-45.3L370.7 18.7C358.7 6.7 342.5 0 325.5 0L192 0c-35.3 0-64 28.7-64 64l0 256c0 35.3 28.7 64 64 64zM64 128c-35.3 0-64 28.7-64 64L0 448c0 35.3 28.7 64 64 64l192 0c35.3 0 64-28.7 64-64l0-16-48 0 0 16c0 8.8-7.2 16-16 16L64 464c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l16 0 0-48-16 0z"/></svg>`,eye:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M288 80C222.8 80 169.2 109.6 128.1 147.7 89.6 183.5 63 226 49.4 256 63 286 89.6 328.5 128.1 364.3 169.2 402.4 222.8 432 288 432s118.8-29.6 159.9-67.7C486.4 328.5 513 286 526.6 256 513 226 486.4 183.5 447.9 147.7 406.8 109.6 353.2 80 288 80zM95.4 112.6C142.5 68.8 207.2 32 288 32s145.5 36.8 192.6 80.6c46.8 43.5 78.1 95.4 93 131.1 3.3 7.9 3.3 16.7 0 24.6-14.9 35.7-46.2 87.7-93 131.1-47.1 43.7-111.8 80.6-192.6 80.6S142.5 443.2 95.4 399.4c-46.8-43.5-78.1-95.4-93-131.1-3.3-7.9-3.3-16.7 0-24.6 14.9-35.7 46.2-87.7 93-131.1zM288 336c44.2 0 80-35.8 80-80 0-29.6-16.1-55.5-40-69.3-1.4 59.7-49.6 107.9-109.3 109.3 13.8 23.9 39.7 40 69.3 40zm-79.6-88.4c2.5 .3 5 .4 7.6 .4 35.3 0 64-28.7 64-64 0-2.6-.2-5.1-.4-7.6-37.4 3.9-67.2 33.7-71.1 71.1zm45.6-115c10.8-3 22.2-4.5 33.9-4.5 8.8 0 17.5 .9 25.8 2.6 .3 .1 .5 .1 .8 .2 57.9 12.2 101.4 63.7 101.4 125.2 0 70.7-57.3 128-128 128-61.6 0-113-43.5-125.2-101.4-1.8-8.6-2.8-17.5-2.8-26.6 0-11 1.4-21.8 4-32 .2-.7 .3-1.3 .5-1.9 11.9-43.4 46.1-77.6 89.5-89.5z"/></svg>`,"eye-slash":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M41-24.9c-9.4-9.4-24.6-9.4-33.9 0S-2.3-.3 7 9.1l528 528c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-96.4-96.4c2.7-2.4 5.4-4.8 8-7.2 46.8-43.5 78.1-95.4 93-131.1 3.3-7.9 3.3-16.7 0-24.6-14.9-35.7-46.2-87.7-93-131.1-47.1-43.7-111.8-80.6-192.6-80.6-56.8 0-105.6 18.2-146 44.2L41-24.9zM176.9 111.1c32.1-18.9 69.2-31.1 111.1-31.1 65.2 0 118.8 29.6 159.9 67.7 38.5 35.7 65.1 78.3 78.6 108.3-13.6 30-40.2 72.5-78.6 108.3-3.1 2.8-6.2 5.6-9.4 8.4L393.8 328c14-20.5 22.2-45.3 22.2-72 0-70.7-57.3-128-128-128-26.7 0-51.5 8.2-72 22.2l-39.1-39.1zm182 182l-108-108c11.1-5.8 23.7-9.1 37.1-9.1 44.2 0 80 35.8 80 80 0 13.4-3.3 26-9.1 37.1zM103.4 173.2l-34-34c-32.6 36.8-55 75.8-66.9 104.5-3.3 7.9-3.3 16.7 0 24.6 14.9 35.7 46.2 87.7 93 131.1 47.1 43.7 111.8 80.6 192.6 80.6 37.3 0 71.2-7.9 101.5-20.6L352.2 422c-20 6.4-41.4 10-64.2 10-65.2 0-118.8-29.6-159.9-67.7-38.5-35.7-65.1-78.3-78.6-108.3 10.4-23.1 28.6-53.6 54-82.8z"/></svg>`,star:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M288.1-32c9 0 17.3 5.1 21.4 13.1L383 125.3 542.9 150.7c8.9 1.4 16.3 7.7 19.1 16.3s.5 18-5.8 24.4L441.7 305.9 467 465.8c1.4 8.9-2.3 17.9-9.6 23.2s-17 6.1-25 2L288.1 417.6 143.8 491c-8 4.1-17.7 3.3-25-2s-11-14.2-9.6-23.2L134.4 305.9 20 191.4c-6.4-6.4-8.6-15.8-5.8-24.4s10.1-14.9 19.1-16.3l159.9-25.4 73.6-144.2c4.1-8 12.4-13.1 21.4-13.1zm0 76.8L230.3 158c-3.5 6.8-10 11.6-17.6 12.8l-125.5 20 89.8 89.9c5.4 5.4 7.9 13.1 6.7 20.7l-19.8 125.5 113.3-57.6c6.8-3.5 14.9-3.5 21.8 0l113.3 57.6-19.8-125.5c-1.2-7.6 1.3-15.3 6.7-20.7l89.8-89.9-125.5-20c-7.6-1.2-14.1-6-17.6-12.8L288.1 44.8z"/></svg>`}},je={name:`system`,resolver:(e,t=`classic`,n=`solid`)=>{let r=Ae[n][e]??Ae.regular[e]??Ae.regular[`circle-question`];return r?ke(r):``}},Me=`classic`,Ne=[de,je],Pe=[];function Fe(e){Pe.push(e)}function Ie(e){Pe=Pe.filter(t=>t!==e)}function Le(e){return Ne.find(t=>t.name===e)}function Re(e,t){ze(e),Ne.push({name:e,resolver:t.resolver,mutator:t.mutator,spriteSheet:t.spriteSheet}),Pe.forEach(t=>{t.library===e&&t.setIcon()})}function ze(e){Ne=Ne.filter(t=>t.name!==e)}function Be(){return Me}var Ve=Object.defineProperty,He=Object.getOwnPropertyDescriptor,Ue=e=>{throw TypeError(e)},k=(e,t,n,r)=>{for(var i=r>1?void 0:r?He(t,n):t,a=e.length-1,o;a>=0;a--)(o=e[a])&&(i=(r?o(t,n,i):o(i))||i);return r&&i&&Ve(t,n,i),i},We=(e,t,n)=>t.has(e)||Ue(`Cannot `+n),Ge=(e,t,n)=>(We(e,t,`read from private field`),n?n.call(e):t.get(e)),Ke=(e,t,n)=>t.has(e)?Ue(`Cannot add the same private member more than once`):t instanceof WeakSet?t.add(e):t.set(e,n),qe=(e,t,n,r)=>(We(e,t,`write to private field`),r?r.call(e,n):t.set(e,n),n),Je={alert:`triangle-exclamation`,asc:`arrow-down-short-wide`,asset:`image`,assets:`image`,circleuarr:`circle-arrow-up`,collapse:`down-left-and-up-right-to-center`,condition:`diamond`,darr:`arrow-down`,date:`calendar`,desc:`arrow-down-wide-short`,disabled:`circle-dashed`,done:`circle-check`,downangle:`angle-down`,draft:`scribble`,edit:`pencil`,enabled:`circle`,expand:`up-right-and-down-left-from-center`,external:`arrow-up-right-from-square`,field:`pen-to-square`,help:`circle-question`,home:`house`,info:`circle-info`,insecure:`unlock`,larr:`arrow-left`,layout:`table-layout`,leftangle:`angle-left`,listrtl:`list-flip`,location:`location-dot`,mail:`envelope`,menu:`bars`,move:`grip-dots`,newstamp:`certificate`,paperplane:`paper-plane`,plugin:`plug`,rarr:`arrow-right`,refresh:`arrows-rotate`,remove:`xmark`,rightangle:`angle-right`,rotate:`rotate-left`,routes:`signs-post`,search:`magnifying-glass`,secure:`lock`,settings:`gear`,shareleft:`share-flip`,shuteye:`eye-slash`,"sidebar-left":`sidebar`,"sidebar-right":`sidebar-flip`,"sidebar-start":`sidebar`,"sidebar-end":`sidebar-flip`,structure:`list-tree`,structurertl:`list-tree-flip`,template:`file-code`,time:`clock`,tool:`wrench`,uarr:`arrow-up`,upangle:`angle-up`,view:`eye`,wand:`wand-magic-sparkles`};function Ye(e,t=`classic`,n=`regular`){let r=`solid`,i=n,a=e.endsWith(`.svg`)?e.split(`.svg`)[0]:e;if(e.includes(`/`)){let[t,...n]=e.split(`/`);i=t??i,a=n.join(`/`)}return i===`thin`?r=`thin`:i===`light`?r=`light`:i===`regular`?r=`regular`:i===`solid`&&(r=`solid`),t===`brands`&&(r=`brands`),i===`custom-icons`&&(r=`custom-icons`),a=Je[a]??a,`/vendor/craft/icons/${r}/${a}.svg`}function Xe(){Re(`default`,{resolver:(e,t=`classic`,n=`solid`)=>Ye(e,t,n),mutator:e=>e.setAttribute(`fill`,`currentColor`)})}var Ze=class extends HTMLElement{constructor(...e){super(...e),this.cookieName=null,this.state=`collapsed`,this.expanded=!1,this.handleOpen=()=>{this.trigger?.setAttribute(`aria-expanded`,`true`),this.expanded=!0,this.dispatchEvent(new CustomEvent(`open`)),this.target&&(this.target.dataset.state=`expanded`),this.cookieName&&window.Craft?.setCookie(this.cookieName,`expanded`)},this.handleClose=()=>{this.trigger?.setAttribute(`aria-expanded`,`false`),this.expanded=!1,this.dispatchEvent(new CustomEvent(`close`)),this.target&&(this.target.dataset.state=`collapsed`),this.cookieName&&window.Craft?.setCookie(this.cookieName,`collapsed`)}}get trigger(){return this.querySelector(`button[type="button"]`)}get target(){if(!this.trigger)return console.warn(`No trigger found for disclosure.`),null;let e=this.trigger.getAttribute(`aria-controls`);return e?document.getElementById(e):(console.warn(`No target selector found for disclosure.`),null)}connectedCallback(){if(!this.trigger){console.error(`craft-disclosure elements must include a button`,this);return}if(!this.target){console.error(`No target with id ${this.trigger.getAttribute(`aria-controls`)} found for disclosure. `,this.trigger);return}this.cookieName=this.getAttribute(`cookie-name`),this.state=this.getAttribute(`state`)??`expanded`,this.trigger.setAttribute(`aria-expanded`,this.state===`expanded`?`true`:`false`),this.trigger.addEventListener(`click`,this.toggle.bind(this)),this.state===`expanded`?this.open():this.close()}disconnectedCallback(){this.open(),this.trigger?.removeEventListener(`click`,this.toggle.bind(this))}attributeChangedCallback(e,t,n){e===`state`&&(n===`expanded`?this.handleOpen():this.handleClose())}toggle(){this.expanded?this.close():this.open()}open(){this.setAttribute(`state`,`expanded`)}close(){this.setAttribute(`state`,`collapsed`)}};Ze.observedAttributes=[`state`],customElements.get(`craft-disclosure`)||customElements.define(`craft-disclosure`,Ze);var Qe=h`
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
`,$e=class extends b{constructor(...e){super(...e),this.visible=!0}show(){this.visible=!0,this.dispatchEvent(new CustomEvent(`show`))}hide(){this.visible=!1,this.dispatchEvent(new CustomEvent(`hide`))}focus(){this.wrapper?.focus()}render(){return p`
      <div
        tabindex="-1"
        class="${D({wrapper:!0,hidden:!this.visible})}"
      >
        <div class="spinner"></div>
        <span class="message visually-hidden"><slot /></span>
      </div>
    `}};$e.styles=[Qe],d([x({reflect:!0})],$e.prototype,`visible`,void 0),d([w(`.wrapper`)],$e.prototype,`wrapper`,void 0),customElements.get(`craft-spinner`)||customElements.define(`craft-spinner`,$e);var et=class extends Event{constructor(){super(`wa-reposition`,{bubbles:!0,cancelable:!1,composed:!0})}},tt=`:host {
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
`,nt,rt=class extends b{constructor(){super(),Ke(this,nt,!1),this.initialReflectedProperties=new Map,this.didSSR=!!this.shadowRoot,this.customStates={set:(e,t)=>{if(this.internals?.states)try{t?this.internals.states.add(e):this.internals.states.delete(e)}catch(e){if(String(e).includes(`must start with '--'`))console.error(`Your browser implements an outdated version of CustomStateSet. Consider using a polyfill`);else throw e}},has:e=>{if(!this.internals?.states)return!1;try{return this.internals.states.has(e)}catch{return!1}}};try{this.internals=this.attachInternals()}catch{console.error(`Element internals are not supported in your browser. Consider using a polyfill`)}this.customStates.set(`wa-defined`,!0);let e=this.constructor;for(let[t,n]of e.elementProperties)n.default===`inherit`&&n.initial!==void 0&&typeof t==`string`&&this.customStates.set(`initial-${t}-${n.initial}`,!0)}static get styles(){return[tt,...Array.isArray(this.css)?this.css:this.css?[this.css]:[]].map(e=>typeof e==`string`?v(e):e)}attributeChangedCallback(e,t,n){Ge(this,nt)||(this.constructor.elementProperties.forEach((e,t)=>{e.reflect&&this[t]!=null&&this.initialReflectedProperties.set(t,this[t])}),qe(this,nt,!0)),super.attributeChangedCallback(e,t,n)}willUpdate(e){super.willUpdate(e),this.initialReflectedProperties.forEach((t,n)=>{e.has(n)&&this[n]==null&&(this[n]=t)})}firstUpdated(e){super.firstUpdated(e),this.didSSR&&this.shadowRoot?.querySelectorAll(`slot`).forEach(e=>{e.dispatchEvent(new Event(`slotchange`,{bubbles:!0,composed:!1,cancelable:!1}))})}update(e){try{super.update(e)}catch(e){if(this.didSSR&&!this.hasUpdated){let t=new Event(`lit-hydration-error`,{bubbles:!0,composed:!0,cancelable:!1});t.error=e,this.dispatchEvent(t)}throw e}}relayNativeEvent(e,t){e.stopImmediatePropagation(),this.dispatchEvent(new e.constructor(e.type,{...e,...t}))}};nt=new WeakMap,k([x()],rt.prototype,`dir`,2),k([x()],rt.prototype,`lang`,2),k([x({type:Boolean,reflect:!0,attribute:`did-ssr`})],rt.prototype,`didSSR`,2);var it=Math.min,at=Math.max,ot=Math.round,st=Math.floor,ct=e=>({x:e,y:e}),lt={left:`right`,right:`left`,bottom:`top`,top:`bottom`},ut={start:`end`,end:`start`};function dt(e,t,n){return at(e,it(t,n))}function ft(e,t){return typeof e==`function`?e(t):e}function pt(e){return e.split(`-`)[0]}function mt(e){return e.split(`-`)[1]}function ht(e){return e===`x`?`y`:`x`}function gt(e){return e===`y`?`height`:`width`}var _t=new Set([`top`,`bottom`]);function vt(e){return _t.has(pt(e))?`y`:`x`}function yt(e){return ht(vt(e))}function bt(e,t,n){n===void 0&&(n=!1);let r=mt(e),i=yt(e),a=gt(i),o=i===`x`?r===(n?`end`:`start`)?`right`:`left`:r===`start`?`bottom`:`top`;return t.reference[a]>t.floating[a]&&(o=kt(o)),[o,kt(o)]}function xt(e){let t=kt(e);return[St(e),t,St(t)]}function St(e){return e.replace(/start|end/g,e=>ut[e])}var Ct=[`left`,`right`],wt=[`right`,`left`],Tt=[`top`,`bottom`],Et=[`bottom`,`top`];function Dt(e,t,n){switch(e){case`top`:case`bottom`:return n?t?wt:Ct:t?Ct:wt;case`left`:case`right`:return t?Tt:Et;default:return[]}}function Ot(e,t,n,r){let i=mt(e),a=Dt(pt(e),n===`start`,r);return i&&(a=a.map(e=>e+`-`+i),t&&(a=a.concat(a.map(St)))),a}function kt(e){return e.replace(/left|right|bottom|top/g,e=>lt[e])}function At(e){return{top:0,right:0,bottom:0,left:0,...e}}function jt(e){return typeof e==`number`?{top:e,right:e,bottom:e,left:e}:At(e)}function Mt(e){let{x:t,y:n,width:r,height:i}=e;return{width:r,height:i,top:n,left:t,right:t+r,bottom:n+i,x:t,y:n}}function Nt(e,t,n){let{reference:r,floating:i}=e,a=vt(t),o=yt(t),s=gt(o),c=pt(t),l=a===`y`,u=r.x+r.width/2-i.width/2,d=r.y+r.height/2-i.height/2,f=r[s]/2-i[s]/2,p;switch(c){case`top`:p={x:u,y:r.y-i.height};break;case`bottom`:p={x:u,y:r.y+r.height};break;case`right`:p={x:r.x+r.width,y:d};break;case`left`:p={x:r.x-i.width,y:d};break;default:p={x:r.x,y:r.y}}switch(mt(t)){case`start`:p[o]-=f*(n&&l?-1:1);break;case`end`:p[o]+=f*(n&&l?-1:1);break}return p}var Pt=async(e,t,n)=>{let{placement:r=`bottom`,strategy:i=`absolute`,middleware:a=[],platform:o}=n,s=a.filter(Boolean),c=await(o.isRTL==null?void 0:o.isRTL(t)),l=await o.getElementRects({reference:e,floating:t,strategy:i}),{x:u,y:d}=Nt(l,r,c),f=r,p={},m=0;for(let n=0;n<s.length;n++){let{name:a,fn:h}=s[n],{x:g,y:_,data:v,reset:y}=await h({x:u,y:d,initialPlacement:r,placement:f,strategy:i,middlewareData:p,rects:l,platform:o,elements:{reference:e,floating:t}});u=g??u,d=_??d,p={...p,[a]:{...p[a],...v}},y&&m<=50&&(m++,typeof y==`object`&&(y.placement&&(f=y.placement),y.rects&&(l=y.rects===!0?await o.getElementRects({reference:e,floating:t,strategy:i}):y.rects),{x:u,y:d}=Nt(l,f,c)),n=-1)}return{x:u,y:d,placement:f,strategy:i,middlewareData:p}};async function Ft(e,t){t===void 0&&(t={});let{x:n,y:r,platform:i,rects:a,elements:o,strategy:s}=e,{boundary:c=`clippingAncestors`,rootBoundary:l=`viewport`,elementContext:u=`floating`,altBoundary:d=!1,padding:f=0}=ft(t,e),p=jt(f),m=o[d?u===`floating`?`reference`:`floating`:u],h=Mt(await i.getClippingRect({element:await(i.isElement==null?void 0:i.isElement(m))??!0?m:m.contextElement||await(i.getDocumentElement==null?void 0:i.getDocumentElement(o.floating)),boundary:c,rootBoundary:l,strategy:s})),g=u===`floating`?{x:n,y:r,width:a.floating.width,height:a.floating.height}:a.reference,_=await(i.getOffsetParent==null?void 0:i.getOffsetParent(o.floating)),v=await(i.isElement==null?void 0:i.isElement(_))&&await(i.getScale==null?void 0:i.getScale(_))||{x:1,y:1},y=Mt(i.convertOffsetParentRelativeRectToViewportRelativeRect?await i.convertOffsetParentRelativeRectToViewportRelativeRect({elements:o,rect:g,offsetParent:_,strategy:s}):g);return{top:(h.top-y.top+p.top)/v.y,bottom:(y.bottom-h.bottom+p.bottom)/v.y,left:(h.left-y.left+p.left)/v.x,right:(y.right-h.right+p.right)/v.x}}var It=e=>({name:`arrow`,options:e,async fn(t){let{x:n,y:r,placement:i,rects:a,platform:o,elements:s,middlewareData:c}=t,{element:l,padding:u=0}=ft(e,t)||{};if(l==null)return{};let d=jt(u),f={x:n,y:r},p=yt(i),m=gt(p),h=await o.getDimensions(l),g=p===`y`,_=g?`top`:`left`,v=g?`bottom`:`right`,y=g?`clientHeight`:`clientWidth`,b=a.reference[m]+a.reference[p]-f[p]-a.floating[m],x=f[p]-a.reference[p],S=await(o.getOffsetParent==null?void 0:o.getOffsetParent(l)),C=S?S[y]:0;(!C||!await(o.isElement==null?void 0:o.isElement(S)))&&(C=s.floating[y]||a.floating[m]);let w=b/2-x/2,T=C/2-h[m]/2-1,E=it(d[_],T),D=it(d[v],T),ee=E,te=C-h[m]-D,ne=C/2-h[m]/2+w,re=dt(ee,ne,te),ie=!c.arrow&&mt(i)!=null&&ne!==re&&a.reference[m]/2-(ne<ee?E:D)-h[m]/2<0,ae=ie?ne<ee?ne-ee:ne-te:0;return{[p]:f[p]+ae,data:{[p]:re,centerOffset:ne-re-ae,...ie&&{alignmentOffset:ae}},reset:ie}}}),Lt=function(e){return e===void 0&&(e={}),{name:`flip`,options:e,async fn(t){var n;let{placement:r,middlewareData:i,rects:a,initialPlacement:o,platform:s,elements:c}=t,{mainAxis:l=!0,crossAxis:u=!0,fallbackPlacements:d,fallbackStrategy:f=`bestFit`,fallbackAxisSideDirection:p=`none`,flipAlignment:m=!0,...h}=ft(e,t);if((n=i.arrow)!=null&&n.alignmentOffset)return{};let g=pt(r),_=vt(o),v=pt(o)===o,y=await(s.isRTL==null?void 0:s.isRTL(c.floating)),b=d||(v||!m?[kt(o)]:xt(o)),x=p!==`none`;!d&&x&&b.push(...Ot(o,m,p,y));let S=[o,...b],C=await Ft(t,h),w=[],T=i.flip?.overflows||[];if(l&&w.push(C[g]),u){let e=bt(r,a,y);w.push(C[e[0]],C[e[1]])}if(T=[...T,{placement:r,overflows:w}],!w.every(e=>e<=0)){let e=(i.flip?.index||0)+1,t=S[e];if(t&&(!(u===`alignment`&&_!==vt(t))||T.every(e=>vt(e.placement)===_?e.overflows[0]>0:!0)))return{data:{index:e,overflows:T},reset:{placement:t}};let n=T.filter(e=>e.overflows[0]<=0).sort((e,t)=>e.overflows[1]-t.overflows[1])[0]?.placement;if(!n)switch(f){case`bestFit`:{let e=T.filter(e=>{if(x){let t=vt(e.placement);return t===_||t===`y`}return!0}).map(e=>[e.placement,e.overflows.filter(e=>e>0).reduce((e,t)=>e+t,0)]).sort((e,t)=>e[1]-t[1])[0]?.[0];e&&(n=e);break}case`initialPlacement`:n=o;break}if(r!==n)return{reset:{placement:n}}}return{}}}},Rt=new Set([`left`,`top`]);async function zt(e,t){let{placement:n,platform:r,elements:i}=e,a=await(r.isRTL==null?void 0:r.isRTL(i.floating)),o=pt(n),s=mt(n),c=vt(n)===`y`,l=Rt.has(o)?-1:1,u=a&&c?-1:1,d=ft(t,e),{mainAxis:f,crossAxis:p,alignmentAxis:m}=typeof d==`number`?{mainAxis:d,crossAxis:0,alignmentAxis:null}:{mainAxis:d.mainAxis||0,crossAxis:d.crossAxis||0,alignmentAxis:d.alignmentAxis};return s&&typeof m==`number`&&(p=s===`end`?m*-1:m),c?{x:p*u,y:f*l}:{x:f*l,y:p*u}}var Bt=function(e){return e===void 0&&(e=0),{name:`offset`,options:e,async fn(t){var n;let{x:r,y:i,placement:a,middlewareData:o}=t,s=await zt(t,e);return a===o.offset?.placement&&(n=o.arrow)!=null&&n.alignmentOffset?{}:{x:r+s.x,y:i+s.y,data:{...s,placement:a}}}}},Vt=function(e){return e===void 0&&(e={}),{name:`shift`,options:e,async fn(t){let{x:n,y:r,placement:i}=t,{mainAxis:a=!0,crossAxis:o=!1,limiter:s={fn:e=>{let{x:t,y:n}=e;return{x:t,y:n}}},...c}=ft(e,t),l={x:n,y:r},u=await Ft(t,c),d=vt(pt(i)),f=ht(d),p=l[f],m=l[d];if(a){let e=f===`y`?`top`:`left`,t=f===`y`?`bottom`:`right`,n=p+u[e],r=p-u[t];p=dt(n,p,r)}if(o){let e=d===`y`?`top`:`left`,t=d===`y`?`bottom`:`right`,n=m+u[e],r=m-u[t];m=dt(n,m,r)}let h=s.fn({...t,[f]:p,[d]:m});return{...h,data:{x:h.x-n,y:h.y-r,enabled:{[f]:a,[d]:o}}}}}},Ht=function(e){return e===void 0&&(e={}),{name:`size`,options:e,async fn(t){var n,r;let{placement:i,rects:a,platform:o,elements:s}=t,{apply:c=()=>{},...l}=ft(e,t),u=await Ft(t,l),d=pt(i),f=mt(i),p=vt(i)===`y`,{width:m,height:h}=a.floating,g,_;d===`top`||d===`bottom`?(g=d,_=f===(await(o.isRTL==null?void 0:o.isRTL(s.floating))?`start`:`end`)?`left`:`right`):(_=d,g=f===`end`?`top`:`bottom`);let v=h-u.top-u.bottom,y=m-u.left-u.right,b=it(h-u[g],v),x=it(m-u[_],y),S=!t.middlewareData.shift,C=b,w=x;if((n=t.middlewareData.shift)!=null&&n.enabled.x&&(w=y),(r=t.middlewareData.shift)!=null&&r.enabled.y&&(C=v),S&&!f){let e=at(u.left,0),t=at(u.right,0),n=at(u.top,0),r=at(u.bottom,0);p?w=m-2*(e!==0||t!==0?e+t:at(u.left,u.right)):C=h-2*(n!==0||r!==0?n+r:at(u.top,u.bottom))}await c({...t,availableWidth:w,availableHeight:C});let T=await o.getDimensions(s.floating);return m!==T.width||h!==T.height?{reset:{rects:!0}}:{}}}};function Ut(){return typeof window<`u`}function Wt(e){return qt(e)?(e.nodeName||``).toLowerCase():`#document`}function Gt(e){var t;return(e==null||(t=e.ownerDocument)==null?void 0:t.defaultView)||window}function Kt(e){return((qt(e)?e.ownerDocument:e.document)||window.document)?.documentElement}function qt(e){return Ut()?e instanceof Node||e instanceof Gt(e).Node:!1}function Jt(e){return Ut()?e instanceof Element||e instanceof Gt(e).Element:!1}function Yt(e){return Ut()?e instanceof HTMLElement||e instanceof Gt(e).HTMLElement:!1}function Xt(e){return!Ut()||typeof ShadowRoot>`u`?!1:e instanceof ShadowRoot||e instanceof Gt(e).ShadowRoot}var Zt=new Set([`inline`,`contents`]);function Qt(e){let{overflow:t,overflowX:n,overflowY:r,display:i}=fn(e);return/auto|scroll|overlay|hidden|clip/.test(t+r+n)&&!Zt.has(i)}var $t=new Set([`table`,`td`,`th`]);function en(e){return $t.has(Wt(e))}var tn=[`:popover-open`,`:modal`];function nn(e){return tn.some(t=>{try{return e.matches(t)}catch{return!1}})}var rn=[`transform`,`translate`,`scale`,`rotate`,`perspective`],an=[`transform`,`translate`,`scale`,`rotate`,`perspective`,`filter`],on=[`paint`,`layout`,`strict`,`content`];function sn(e){let t=ln(),n=Jt(e)?fn(e):e;return rn.some(e=>n[e]?n[e]!==`none`:!1)||(n.containerType?n.containerType!==`normal`:!1)||!t&&(n.backdropFilter?n.backdropFilter!==`none`:!1)||!t&&(n.filter?n.filter!==`none`:!1)||an.some(e=>(n.willChange||``).includes(e))||on.some(e=>(n.contain||``).includes(e))}function cn(e){let t=mn(e);for(;Yt(t)&&!dn(t);){if(sn(t))return t;if(nn(t))return null;t=mn(t)}return null}function ln(){return typeof CSS>`u`||!CSS.supports?!1:CSS.supports(`-webkit-backdrop-filter`,`none`)}var un=new Set([`html`,`body`,`#document`]);function dn(e){return un.has(Wt(e))}function fn(e){return Gt(e).getComputedStyle(e)}function pn(e){return Jt(e)?{scrollLeft:e.scrollLeft,scrollTop:e.scrollTop}:{scrollLeft:e.scrollX,scrollTop:e.scrollY}}function mn(e){if(Wt(e)===`html`)return e;let t=e.assignedSlot||e.parentNode||Xt(e)&&e.host||Kt(e);return Xt(t)?t.host:t}function hn(e){let t=mn(e);return dn(t)?e.ownerDocument?e.ownerDocument.body:e.body:Yt(t)&&Qt(t)?t:hn(t)}function gn(e,t,n){t===void 0&&(t=[]),n===void 0&&(n=!0);let r=hn(e),i=r===e.ownerDocument?.body,a=Gt(r);if(i){let e=_n(a);return t.concat(a,a.visualViewport||[],Qt(r)?r:[],e&&n?gn(e):[])}return t.concat(r,gn(r,[],n))}function _n(e){return e.parent&&Object.getPrototypeOf(e.parent)?e.frameElement:null}function vn(e){let t=fn(e),n=parseFloat(t.width)||0,r=parseFloat(t.height)||0,i=Yt(e),a=i?e.offsetWidth:n,o=i?e.offsetHeight:r,s=ot(n)!==a||ot(r)!==o;return s&&(n=a,r=o),{width:n,height:r,$:s}}function yn(e){return Jt(e)?e:e.contextElement}function bn(e){let t=yn(e);if(!Yt(t))return ct(1);let n=t.getBoundingClientRect(),{width:r,height:i,$:a}=vn(t),o=(a?ot(n.width):n.width)/r,s=(a?ot(n.height):n.height)/i;return(!o||!Number.isFinite(o))&&(o=1),(!s||!Number.isFinite(s))&&(s=1),{x:o,y:s}}var xn=ct(0);function Sn(e){let t=Gt(e);return!ln()||!t.visualViewport?xn:{x:t.visualViewport.offsetLeft,y:t.visualViewport.offsetTop}}function Cn(e,t,n){return t===void 0&&(t=!1),!n||t&&n!==Gt(e)?!1:t}function wn(e,t,n,r){t===void 0&&(t=!1),n===void 0&&(n=!1);let i=e.getBoundingClientRect(),a=yn(e),o=ct(1);t&&(r?Jt(r)&&(o=bn(r)):o=bn(e));let s=Cn(a,n,r)?Sn(a):ct(0),c=(i.left+s.x)/o.x,l=(i.top+s.y)/o.y,u=i.width/o.x,d=i.height/o.y;if(a){let e=Gt(a),t=r&&Jt(r)?Gt(r):r,n=e,i=_n(n);for(;i&&r&&t!==n;){let e=bn(i),t=i.getBoundingClientRect(),r=fn(i),a=t.left+(i.clientLeft+parseFloat(r.paddingLeft))*e.x,o=t.top+(i.clientTop+parseFloat(r.paddingTop))*e.y;c*=e.x,l*=e.y,u*=e.x,d*=e.y,c+=a,l+=o,n=Gt(i),i=_n(n)}}return Mt({width:u,height:d,x:c,y:l})}function Tn(e,t){let n=pn(e).scrollLeft;return t?t.left+n:wn(Kt(e)).left+n}function En(e,t){let n=e.getBoundingClientRect();return{x:n.left+t.scrollLeft-Tn(e,n),y:n.top+t.scrollTop}}function Dn(e){let{elements:t,rect:n,offsetParent:r,strategy:i}=e,a=i===`fixed`,o=Kt(r),s=t?nn(t.floating):!1;if(r===o||s&&a)return n;let c={scrollLeft:0,scrollTop:0},l=ct(1),u=ct(0),d=Yt(r);if((d||!d&&!a)&&((Wt(r)!==`body`||Qt(o))&&(c=pn(r)),Yt(r))){let e=wn(r);l=bn(r),u.x=e.x+r.clientLeft,u.y=e.y+r.clientTop}let f=o&&!d&&!a?En(o,c):ct(0);return{width:n.width*l.x,height:n.height*l.y,x:n.x*l.x-c.scrollLeft*l.x+u.x+f.x,y:n.y*l.y-c.scrollTop*l.y+u.y+f.y}}function On(e){return Array.from(e.getClientRects())}function kn(e){let t=Kt(e),n=pn(e),r=e.ownerDocument.body,i=at(t.scrollWidth,t.clientWidth,r.scrollWidth,r.clientWidth),a=at(t.scrollHeight,t.clientHeight,r.scrollHeight,r.clientHeight),o=-n.scrollLeft+Tn(e),s=-n.scrollTop;return fn(r).direction===`rtl`&&(o+=at(t.clientWidth,r.clientWidth)-i),{width:i,height:a,x:o,y:s}}var An=25;function jn(e,t){let n=Gt(e),r=Kt(e),i=n.visualViewport,a=r.clientWidth,o=r.clientHeight,s=0,c=0;if(i){a=i.width,o=i.height;let e=ln();(!e||e&&t===`fixed`)&&(s=i.offsetLeft,c=i.offsetTop)}let l=Tn(r);if(l<=0){let e=r.ownerDocument,t=e.body,n=getComputedStyle(t),i=e.compatMode===`CSS1Compat`&&parseFloat(n.marginLeft)+parseFloat(n.marginRight)||0,o=Math.abs(r.clientWidth-t.clientWidth-i);o<=An&&(a-=o)}else l<=An&&(a+=l);return{width:a,height:o,x:s,y:c}}var Mn=new Set([`absolute`,`fixed`]);function Nn(e,t){let n=wn(e,!0,t===`fixed`),r=n.top+e.clientTop,i=n.left+e.clientLeft,a=Yt(e)?bn(e):ct(1);return{width:e.clientWidth*a.x,height:e.clientHeight*a.y,x:i*a.x,y:r*a.y}}function Pn(e,t,n){let r;if(t===`viewport`)r=jn(e,n);else if(t===`document`)r=kn(Kt(e));else if(Jt(t))r=Nn(t,n);else{let n=Sn(e);r={x:t.x-n.x,y:t.y-n.y,width:t.width,height:t.height}}return Mt(r)}function Fn(e,t){let n=mn(e);return n===t||!Jt(n)||dn(n)?!1:fn(n).position===`fixed`||Fn(n,t)}function In(e,t){let n=t.get(e);if(n)return n;let r=gn(e,[],!1).filter(e=>Jt(e)&&Wt(e)!==`body`),i=null,a=fn(e).position===`fixed`,o=a?mn(e):e;for(;Jt(o)&&!dn(o);){let t=fn(o),n=sn(o);!n&&t.position===`fixed`&&(i=null),(a?!n&&!i:!n&&t.position===`static`&&i&&Mn.has(i.position)||Qt(o)&&!n&&Fn(e,o))?r=r.filter(e=>e!==o):i=t,o=mn(o)}return t.set(e,r),r}function Ln(e){let{element:t,boundary:n,rootBoundary:r,strategy:i}=e,a=[...n===`clippingAncestors`?nn(t)?[]:In(t,this._c):[].concat(n),r],o=a[0],s=a.reduce((e,n)=>{let r=Pn(t,n,i);return e.top=at(r.top,e.top),e.right=it(r.right,e.right),e.bottom=it(r.bottom,e.bottom),e.left=at(r.left,e.left),e},Pn(t,o,i));return{width:s.right-s.left,height:s.bottom-s.top,x:s.left,y:s.top}}function Rn(e){let{width:t,height:n}=vn(e);return{width:t,height:n}}function zn(e,t,n){let r=Yt(t),i=Kt(t),a=n===`fixed`,o=wn(e,!0,a,t),s={scrollLeft:0,scrollTop:0},c=ct(0);function l(){c.x=Tn(i)}if(r||!r&&!a)if((Wt(t)!==`body`||Qt(i))&&(s=pn(t)),r){let e=wn(t,!0,a,t);c.x=e.x+t.clientLeft,c.y=e.y+t.clientTop}else i&&l();a&&!r&&i&&l();let u=i&&!r&&!a?En(i,s):ct(0);return{x:o.left+s.scrollLeft-c.x-u.x,y:o.top+s.scrollTop-c.y-u.y,width:o.width,height:o.height}}function Bn(e){return fn(e).position===`static`}function Vn(e,t){if(!Yt(e)||fn(e).position===`fixed`)return null;if(t)return t(e);let n=e.offsetParent;return Kt(e)===n&&(n=n.ownerDocument.body),n}function Hn(e,t){let n=Gt(e);if(nn(e))return n;if(!Yt(e)){let t=mn(e);for(;t&&!dn(t);){if(Jt(t)&&!Bn(t))return t;t=mn(t)}return n}let r=Vn(e,t);for(;r&&en(r)&&Bn(r);)r=Vn(r,t);return r&&dn(r)&&Bn(r)&&!sn(r)?n:r||cn(e)||n}var Un=async function(e){let t=this.getOffsetParent||Hn,n=this.getDimensions,r=await n(e.floating);return{reference:zn(e.reference,await t(e.floating),e.strategy),floating:{x:0,y:0,width:r.width,height:r.height}}};function Wn(e){return fn(e).direction===`rtl`}var Gn={convertOffsetParentRelativeRectToViewportRelativeRect:Dn,getDocumentElement:Kt,getClippingRect:Ln,getOffsetParent:Hn,getElementRects:Un,getClientRects:On,getDimensions:Rn,getScale:bn,isElement:Jt,isRTL:Wn};function Kn(e,t){return e.x===t.x&&e.y===t.y&&e.width===t.width&&e.height===t.height}function qn(e,t){let n=null,r,i=Kt(e);function a(){var e;clearTimeout(r),(e=n)==null||e.disconnect(),n=null}function o(s,c){s===void 0&&(s=!1),c===void 0&&(c=1),a();let l=e.getBoundingClientRect(),{left:u,top:d,width:f,height:p}=l;if(s||t(),!f||!p)return;let m=st(d),h=st(i.clientWidth-(u+f)),g=st(i.clientHeight-(d+p)),_=st(u),v={rootMargin:-m+`px `+-h+`px `+-g+`px `+-_+`px`,threshold:at(0,it(1,c))||1},y=!0;function b(t){let n=t[0].intersectionRatio;if(n!==c){if(!y)return o();n?o(!1,n):r=setTimeout(()=>{o(!1,1e-7)},1e3)}n===1&&!Kn(l,e.getBoundingClientRect())&&o(),y=!1}try{n=new IntersectionObserver(b,{...v,root:i.ownerDocument})}catch{n=new IntersectionObserver(b,v)}n.observe(e)}return o(!0),a}function Jn(e,t,n,r){r===void 0&&(r={});let{ancestorScroll:i=!0,ancestorResize:a=!0,elementResize:o=typeof ResizeObserver==`function`,layoutShift:s=typeof IntersectionObserver==`function`,animationFrame:c=!1}=r,l=yn(e),u=i||a?[...l?gn(l):[],...gn(t)]:[];u.forEach(e=>{i&&e.addEventListener(`scroll`,n,{passive:!0}),a&&e.addEventListener(`resize`,n)});let d=l&&s?qn(l,n):null,f=-1,p=null;o&&(p=new ResizeObserver(e=>{let[r]=e;r&&r.target===l&&p&&(p.unobserve(t),cancelAnimationFrame(f),f=requestAnimationFrame(()=>{var e;(e=p)==null||e.observe(t)})),n()}),l&&!c&&p.observe(l),p.observe(t));let m,h=c?wn(e):null;c&&g();function g(){let t=wn(e);h&&!Kn(h,t)&&n(),h=t,m=requestAnimationFrame(g)}return n(),()=>{var e;u.forEach(e=>{i&&e.removeEventListener(`scroll`,n),a&&e.removeEventListener(`resize`,n)}),d?.(),(e=p)==null||e.disconnect(),p=null,c&&cancelAnimationFrame(m)}}var Yn=Bt,Xn=Vt,Zn=Lt,Qn=Ht,$n=It,er=(e,t,n)=>{let r=new Map,i={platform:Gn,...n},a={...i.platform,_c:r};return Pt(e,t,{...i,platform:a})};function tr(e){return rr(e)}function nr(e){return e.assignedSlot?e.assignedSlot:e.parentNode instanceof ShadowRoot?e.parentNode.host:e.parentNode}function rr(e){for(let t=e;t;t=nr(t))if(t instanceof Element&&getComputedStyle(t).display===`none`)return null;for(let t=nr(e);t;t=nr(t)){if(!(t instanceof Element))continue;let e=getComputedStyle(t);if(e.display!==`contents`&&(e.position!==`static`||sn(e)||t.tagName===`BODY`))return t}return null}var ir=`:host {
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
`;function ar(e){return typeof e==`object`&&!!e&&`getBoundingClientRect`in e&&(`contextElement`in e?e instanceof Element:!0)}var or=globalThis?.HTMLElement?.prototype.hasOwnProperty(`popover`),A=class extends rt{constructor(){super(...arguments),this.localize=new Oe(this),this.active=!1,this.placement=`top`,this.boundary=`viewport`,this.distance=0,this.skidding=0,this.arrow=!1,this.arrowPlacement=`anchor`,this.arrowPadding=10,this.flip=!1,this.flipFallbackPlacements=``,this.flipFallbackStrategy=`best-fit`,this.flipPadding=0,this.shift=!1,this.shiftPadding=0,this.autoSizePadding=0,this.hoverBridge=!1,this.updateHoverBridge=()=>{if(this.hoverBridge&&this.anchorEl){let e=this.anchorEl.getBoundingClientRect(),t=this.popup.getBoundingClientRect(),n=this.placement.includes(`top`)||this.placement.includes(`bottom`),r=0,i=0,a=0,o=0,s=0,c=0,l=0,u=0;n?e.top<t.top?(r=e.left,i=e.bottom,a=e.right,o=e.bottom,s=t.left,c=t.top,l=t.right,u=t.top):(r=t.left,i=t.bottom,a=t.right,o=t.bottom,s=e.left,c=e.top,l=e.right,u=e.top):e.left<t.left?(r=e.right,i=e.top,a=t.left,o=t.top,s=e.right,c=e.bottom,l=t.left,u=t.bottom):(r=t.right,i=t.top,a=e.left,o=e.top,s=t.right,c=t.bottom,l=e.left,u=e.bottom),this.style.setProperty(`--hover-bridge-top-left-x`,`${r}px`),this.style.setProperty(`--hover-bridge-top-left-y`,`${i}px`),this.style.setProperty(`--hover-bridge-top-right-x`,`${a}px`),this.style.setProperty(`--hover-bridge-top-right-y`,`${o}px`),this.style.setProperty(`--hover-bridge-bottom-left-x`,`${s}px`),this.style.setProperty(`--hover-bridge-bottom-left-y`,`${c}px`),this.style.setProperty(`--hover-bridge-bottom-right-x`,`${l}px`),this.style.setProperty(`--hover-bridge-bottom-right-y`,`${u}px`)}}}async connectedCallback(){super.connectedCallback(),await this.updateComplete,this.start()}disconnectedCallback(){super.disconnectedCallback(),this.stop()}async updated(e){super.updated(e),e.has(`active`)&&(this.active?this.start():this.stop()),e.has(`anchor`)&&this.handleAnchorChange(),this.active&&(await this.updateComplete,this.reposition())}async handleAnchorChange(){await this.stop(),this.anchor&&typeof this.anchor==`string`?this.anchorEl=this.getRootNode().getElementById(this.anchor):this.anchor instanceof Element||ar(this.anchor)?this.anchorEl=this.anchor:this.anchorEl=this.querySelector(`[slot="anchor"]`),this.anchorEl instanceof HTMLSlotElement&&(this.anchorEl=this.anchorEl.assignedElements({flatten:!0})[0]),this.anchorEl&&this.start()}start(){!this.anchorEl||!this.active||(this.popup.showPopover?.(),this.cleanup=Jn(this.anchorEl,this.popup,()=>{this.reposition()}))}async stop(){return new Promise(e=>{this.popup.hidePopover?.(),this.cleanup?(this.cleanup(),this.cleanup=void 0,this.removeAttribute(`data-current-placement`),this.style.removeProperty(`--auto-size-available-width`),this.style.removeProperty(`--auto-size-available-height`),requestAnimationFrame(()=>e())):e()})}reposition(){if(!this.active||!this.anchorEl)return;let e=[Yn({mainAxis:this.distance,crossAxis:this.skidding})];this.sync?e.push(Qn({apply:({rects:e})=>{let t=this.sync===`width`||this.sync===`both`,n=this.sync===`height`||this.sync===`both`;this.popup.style.width=t?`${e.reference.width}px`:``,this.popup.style.height=n?`${e.reference.height}px`:``}})):(this.popup.style.width=``,this.popup.style.height=``);let t;or&&!ar(this.anchor)&&this.boundary===`scroll`&&(t=gn(this.anchorEl).filter(e=>e instanceof Element)),this.flip&&e.push(Zn({boundary:this.flipBoundary||t,fallbackPlacements:this.flipFallbackPlacements,fallbackStrategy:this.flipFallbackStrategy===`best-fit`?`bestFit`:`initialPlacement`,padding:this.flipPadding})),this.shift&&e.push(Xn({boundary:this.shiftBoundary||t,padding:this.shiftPadding})),this.autoSize?e.push(Qn({boundary:this.autoSizeBoundary||t,padding:this.autoSizePadding,apply:({availableWidth:e,availableHeight:t})=>{this.autoSize===`vertical`||this.autoSize===`both`?this.style.setProperty(`--auto-size-available-height`,`${t}px`):this.style.removeProperty(`--auto-size-available-height`),this.autoSize===`horizontal`||this.autoSize===`both`?this.style.setProperty(`--auto-size-available-width`,`${e}px`):this.style.removeProperty(`--auto-size-available-width`)}})):(this.style.removeProperty(`--auto-size-available-width`),this.style.removeProperty(`--auto-size-available-height`)),this.arrow&&e.push($n({element:this.arrowEl,padding:this.arrowPadding}));let n=or?e=>Gn.getOffsetParent(e,tr):Gn.getOffsetParent;er(this.anchorEl,this.popup,{placement:this.placement,middleware:e,strategy:or?`absolute`:`fixed`,platform:{...Gn,getOffsetParent:n}}).then(({x:e,y:t,middlewareData:n,placement:r})=>{let i=this.localize.dir()===`rtl`,a={top:`bottom`,right:`left`,bottom:`top`,left:`right`}[r.split(`-`)[0]];if(this.setAttribute(`data-current-placement`,r),Object.assign(this.popup.style,{left:`${e}px`,top:`${t}px`}),this.arrow){let e=n.arrow.x,t=n.arrow.y,r=``,o=``,s=``,c=``;if(this.arrowPlacement===`start`){let n=typeof e==`number`?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:``;r=typeof t==`number`?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:``,o=i?n:``,c=i?``:n}else if(this.arrowPlacement===`end`){let n=typeof e==`number`?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:``;o=i?``:n,c=i?n:``,s=typeof t==`number`?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:``}else this.arrowPlacement===`center`?(c=typeof e==`number`?`calc(50% - var(--arrow-size-diagonal))`:``,r=typeof t==`number`?`calc(50% - var(--arrow-size-diagonal))`:``):(c=typeof e==`number`?`${e}px`:``,r=typeof t==`number`?`${t}px`:``);Object.assign(this.arrowEl.style,{top:r,right:o,bottom:s,left:c,[a]:`calc(var(--arrow-size-diagonal) * -1)`})}}),requestAnimationFrame(()=>this.updateHoverBridge()),this.dispatchEvent(new et)}render(){return p`
      <slot name="anchor" @slotchange=${this.handleAnchorChange}></slot>

      <span
        part="hover-bridge"
        class=${D({"popup-hover-bridge":!0,"popup-hover-bridge-visible":this.hoverBridge&&this.active})}
      ></span>

      <div
        popover="manual"
        part="popup"
        class=${D({popup:!0,"popup-active":this.active,"popup-fixed":!or,"popup-has-arrow":this.arrow})}
      >
        <slot></slot>
        ${this.arrow?p`<div part="arrow" class="arrow" role="presentation"></div>`:``}
      </div>
    `}};A.css=ir,k([w(`.popup`)],A.prototype,`popup`,2),k([w(`.arrow`)],A.prototype,`arrowEl`,2),k([x()],A.prototype,`anchor`,2),k([x({type:Boolean,reflect:!0})],A.prototype,`active`,2),k([x({reflect:!0})],A.prototype,`placement`,2),k([x()],A.prototype,`boundary`,2),k([x({type:Number})],A.prototype,`distance`,2),k([x({type:Number})],A.prototype,`skidding`,2),k([x({type:Boolean})],A.prototype,`arrow`,2),k([x({attribute:`arrow-placement`})],A.prototype,`arrowPlacement`,2),k([x({attribute:`arrow-padding`,type:Number})],A.prototype,`arrowPadding`,2),k([x({type:Boolean})],A.prototype,`flip`,2),k([x({attribute:`flip-fallback-placements`,converter:{fromAttribute:e=>e.split(` `).map(e=>e.trim()).filter(e=>e!==``),toAttribute:e=>e.join(` `)}})],A.prototype,`flipFallbackPlacements`,2),k([x({attribute:`flip-fallback-strategy`})],A.prototype,`flipFallbackStrategy`,2),k([x({type:Object})],A.prototype,`flipBoundary`,2),k([x({attribute:`flip-padding`,type:Number})],A.prototype,`flipPadding`,2),k([x({type:Boolean})],A.prototype,`shift`,2),k([x({type:Object})],A.prototype,`shiftBoundary`,2),k([x({attribute:`shift-padding`,type:Number})],A.prototype,`shiftPadding`,2),k([x({attribute:`auto-size`})],A.prototype,`autoSize`,2),k([x()],A.prototype,`sync`,2),k([x({type:Object})],A.prototype,`autoSizeBoundary`,2),k([x({attribute:`auto-size-padding`,type:Number})],A.prototype,`autoSizePadding`,2),k([x({attribute:`hover-bridge`,type:Boolean})],A.prototype,`hoverBridge`,2),A=k([C(`wa-popup`)],A);var sr=class extends Event{constructor(){super(`wa-after-hide`,{bubbles:!0,cancelable:!1,composed:!0})}},cr=class extends Event{constructor(){super(`wa-after-show`,{bubbles:!0,cancelable:!1,composed:!0})}},lr=class extends Event{constructor(e){super(`wa-hide`,{bubbles:!0,cancelable:!0,composed:!0}),this.detail=e}},ur=class extends Event{constructor(){super(`wa-show`,{bubbles:!0,cancelable:!0,composed:!0})}},dr=`useandom-26T198340PX75pxJACKVERYMINDBUSHWOLF_GQZbfghjklqvwyzrict`,fr=(e=21)=>{let t=``,n=crypto.getRandomValues(new Uint8Array(e|=0));for(;e--;)t+=dr[n[e]&63];return t};function pr(e=``){return`${e}${fr()}`}function mr(e,t){return new Promise(n=>{function r(i){i.target===e&&(e.removeEventListener(t,r),n())}e.addEventListener(t,r)})}function hr(e,t){return new Promise(n=>{let r=new AbortController,{signal:i}=r;if(e.classList.contains(t))return;e.classList.remove(t),e.classList.add(t);let a=()=>{e.classList.remove(t),n(),r.abort()};e.addEventListener(`animationend`,a,{once:!0,signal:i}),e.addEventListener(`animationcancel`,a,{once:!0,signal:i})})}function gr(e,t){let n={waitUntilFirstUpdate:!1,...t};return(t,r)=>{let{update:i}=t,a=Array.isArray(e)?e:[e];t.update=function(e){a.forEach(t=>{let i=t;if(e.has(i)){let t=e.get(i),a=this[i];t!==a&&(!n.waitUntilFirstUpdate||this.hasUpdated)&&this[r](t,a)}}),i.call(this,e)}}}var _r=`:host {
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
`,vr=class extends rt{constructor(){super(...arguments),this.placement=`top`,this.disabled=!1,this.distance=8,this.open=!1,this.skidding=0,this.showDelay=150,this.hideDelay=0,this.trigger=`hover focus`,this.withoutArrow=!1,this.for=null,this.anchor=null,this.eventController=new AbortController,this.handleBlur=()=>{this.hasTrigger(`focus`)&&this.hide()},this.handleClick=()=>{this.hasTrigger(`click`)&&(this.open?this.hide():this.show())},this.handleFocus=()=>{this.hasTrigger(`focus`)&&this.show()},this.handleDocumentKeyDown=e=>{e.key===`Escape`&&(e.stopPropagation(),this.hide())},this.handleMouseOver=()=>{this.hasTrigger(`hover`)&&(clearTimeout(this.hoverTimeout),this.hoverTimeout=window.setTimeout(()=>this.show(),this.showDelay))},this.handleMouseOut=()=>{this.hasTrigger(`hover`)&&(clearTimeout(this.hoverTimeout),this.hoverTimeout=window.setTimeout(()=>this.hide(),this.hideDelay))}}connectedCallback(){super.connectedCallback(),this.eventController.signal.aborted&&(this.eventController=new AbortController),this.open&&(this.open=!1,this.updateComplete.then(()=>{this.open=!0})),this.id||=pr(`wa-tooltip-`),this.for&&this.anchor?(this.anchor=null,this.handleForChange()):this.for&&this.handleForChange()}disconnectedCallback(){super.disconnectedCallback(),document.removeEventListener(`keydown`,this.handleDocumentKeyDown),this.eventController.abort(),this.anchor&&this.removeFromAriaLabelledBy(this.anchor,this.id)}firstUpdated(){this.body.hidden=!this.open,this.open&&(this.popup.active=!0,this.popup.reposition())}hasTrigger(e){return this.trigger.split(` `).includes(e)}addToAriaLabelledBy(e,t){let n=(e.getAttribute(`aria-labelledby`)||``).split(/\s+/).filter(Boolean);n.includes(t)||(n.push(t),e.setAttribute(`aria-labelledby`,n.join(` `)))}removeFromAriaLabelledBy(e,t){let n=(e.getAttribute(`aria-labelledby`)||``).split(/\s+/).filter(Boolean).filter(e=>e!==t);n.length>0?e.setAttribute(`aria-labelledby`,n.join(` `)):e.removeAttribute(`aria-labelledby`)}async handleOpenChange(){if(this.open){if(this.disabled)return;let e=new ur;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}document.addEventListener(`keydown`,this.handleDocumentKeyDown,{signal:this.eventController.signal}),this.body.hidden=!1,this.popup.active=!0,await hr(this.popup.popup,`show-with-scale`),this.popup.reposition(),this.dispatchEvent(new cr)}else{let e=new lr;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}document.removeEventListener(`keydown`,this.handleDocumentKeyDown),await hr(this.popup.popup,`hide-with-scale`),this.popup.active=!1,this.body.hidden=!0,this.dispatchEvent(new sr)}}handleForChange(){let e=this.getRootNode();if(!e)return;let t=this.for?e.getElementById(this.for):null,n=this.anchor;if(t===n)return;let{signal:r}=this.eventController;t&&(this.addToAriaLabelledBy(t,this.id),t.addEventListener(`blur`,this.handleBlur,{capture:!0,signal:r}),t.addEventListener(`focus`,this.handleFocus,{capture:!0,signal:r}),t.addEventListener(`click`,this.handleClick,{signal:r}),t.addEventListener(`mouseover`,this.handleMouseOver,{signal:r}),t.addEventListener(`mouseout`,this.handleMouseOut,{signal:r})),n&&(this.removeFromAriaLabelledBy(n,this.id),n.removeEventListener(`blur`,this.handleBlur,{capture:!0}),n.removeEventListener(`focus`,this.handleFocus,{capture:!0}),n.removeEventListener(`click`,this.handleClick),n.removeEventListener(`mouseover`,this.handleMouseOver),n.removeEventListener(`mouseout`,this.handleMouseOut)),this.anchor=t}async handleOptionsChange(){this.hasUpdated&&(await this.updateComplete,this.popup.reposition())}handleDisabledChange(){this.disabled&&this.open&&this.hide()}async show(){if(!this.open)return this.open=!0,mr(this,`wa-after-show`)}async hide(){if(this.open)return this.open=!1,mr(this,`wa-after-hide`)}render(){return p`
      <wa-popup
        part="base"
        exportparts="
          popup:base__popup,
          arrow:base__arrow
        "
        class=${D({tooltip:!0,"tooltip-open":this.open})}
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
    `}};vr.css=_r,vr.dependencies={"wa-popup":A},k([w(`slot:not([name])`)],vr.prototype,`defaultSlot`,2),k([w(`.body`)],vr.prototype,`body`,2),k([w(`wa-popup`)],vr.prototype,`popup`,2),k([x()],vr.prototype,`placement`,2),k([x({type:Boolean,reflect:!0})],vr.prototype,`disabled`,2),k([x({type:Number})],vr.prototype,`distance`,2),k([x({type:Boolean,reflect:!0})],vr.prototype,`open`,2),k([x({type:Number})],vr.prototype,`skidding`,2),k([x({attribute:`show-delay`,type:Number})],vr.prototype,`showDelay`,2),k([x({attribute:`hide-delay`,type:Number})],vr.prototype,`hideDelay`,2),k([x()],vr.prototype,`trigger`,2),k([x({attribute:`without-arrow`,type:Boolean,reflect:!0})],vr.prototype,`withoutArrow`,2),k([x()],vr.prototype,`for`,2),k([S()],vr.prototype,`anchor`,2),k([gr(`open`,{waitUntilFirstUpdate:!0})],vr.prototype,`handleOpenChange`,1),k([gr(`for`)],vr.prototype,`handleForChange`,1),k([gr([`distance`,`placement`,`skidding`])],vr.prototype,`handleOptionsChange`,1),k([gr(`disabled`)],vr.prototype,`handleDisabledChange`,1),vr=k([C(`wa-tooltip`)],vr);var yr=class extends vr{static get styles(){return[vr.styles,h`
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
      `]}};customElements.get(`c-tooltip`)||customElements.define(`c-tooltip`,yr);var br=h`
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
`,xr=class extends b{constructor(...e){super(...e),this.isCopying=!1,this.value=``,this.disabled=!1}async copyValue(){if(!(this.isCopying||this.disabled)){this.isCopying=!0;try{await navigator.clipboard.writeText(this.value),this.dispatchEvent(new CustomEvent(`craft-copy`,{bubbles:!0,cancelable:!1,composed:!0,detail:{value:this.value}}))}catch{this.dispatchEvent(new CustomEvent(`craft-error`,{cancelable:!1,composed:!0,bubbles:!0}))}finally{this.isCopying=!1}}}render(){return p`
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
    `}};xr.styles=[br],d([S()],xr.prototype,`isCopying`,void 0),d([x({type:String})],xr.prototype,`value`,void 0),d([x({type:Boolean})],xr.prototype,`disabled`,void 0),customElements.get(`craft-copy-button`)||customElements.define(`craft-copy-button`,xr);var Sr=h`
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
`,Cr=h`
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
`,wr={"icon.in":{keyframes:[{scale:.25,opacity:.25},{scale:1,opacity:1}],options:{duration:100}},"icon.out":{keyframes:[{scale:1,opacity:1},{scale:.25,opacity:.25}],options:{duration:100}}},Tr=class extends b{constructor(){super(),this.status=`rest`,this.value=``,this.disabled=!1,this.feedbackDuration=1e3,this.tooltipLabel=`Copy`,this.addEventListener(`craft-copy`,()=>{this.showStatus(`success`)}),this.addEventListener(`craft-error`,()=>{this.showStatus(`error`)})}getId(){return`attribute-${this.value.replace(/([a-z])([A-Z])/g,`$1-$2`).replace(/[\s_]+/g,`-`).toLowerCase()}`}async showStatus(e){let t=e===`success`?this.successIconEl:this.errorIconEl;this.tooltipLabel=e===`success`?`Copied`:`Copy failed`,await t.animate(wr[`icon.out`].keyframes,wr[`icon.out`].options),this.copyIconEl.hidden=!0,t.hidden=!1,await t.animate(wr[`icon.in`].keyframes,wr[`icon.in`].options),this.status=e,setTimeout(async()=>{await t.animate(wr[`icon.out`].keyframes,wr[`icon.out`].options),t.hidden=!0,this.copyIconEl.hidden=!1,await this.copyIconEl.animate(wr[`icon.in`].keyframes,wr[`icon.in`].options),this.status=`rest`,this.tooltipLabel=`Copy`},this.feedbackDuration)}render(){return p`
      <c-tooltip for="${this.getId()}">${this.tooltipLabel}</c-tooltip>
      <craft-copy-button
        value="${this.value}"
        id="${this.getId()}"
        class=${D({"copy-attribute":!0,"copy-attribute--success":this.status===`success`,"copy-attribute--error":this.status===`error`})}
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
    `}};Tr.styles=[Sr,Cr],d([S()],Tr.prototype,`status`,void 0),d([w(`slot[name="copy-icon"]`)],Tr.prototype,`copyIconEl`,void 0),d([w(`slot[name="success-icon"]`)],Tr.prototype,`successIconEl`,void 0),d([w(`slot[name="error-icon"]`)],Tr.prototype,`errorIconEl`,void 0),d([w(`craft-copy-button`)],Tr.prototype,`copyButtonEl`,void 0),d([x({type:String})],Tr.prototype,`value`,void 0),d([x({type:Boolean,reflect:!0})],Tr.prototype,`disabled`,void 0),d([x({attribute:`feedback-duration`,type:Number})],Tr.prototype,`feedbackDuration`,void 0),d([x({reflect:!1})],Tr.prototype,`tooltipLabel`,void 0),customElements.get(`craft-copy-attribute`)||customElements.define(`craft-copy-attribute`,Tr);var Er=new WeakMap;function Dr(e,t){let n=t;for(;n;){if(Er.get(n)===e)return!0;n=Object.getPrototypeOf(n)}return!1}function Or(e){return t=>{if(Dr(e,t))return t;let n=e(t);return Er.set(n,e),n}}var kr=Or(e=>class extends e{static get properties(){return{disabled:{type:Boolean,reflect:!0}}}constructor(){super(),this._requestedToBeDisabled=!1,this.__isUserSettingDisabled=!0,this.__restoreDisabledTo=!1,this.disabled=!1}makeRequestToBeDisabled(){this._requestedToBeDisabled===!1&&(this._requestedToBeDisabled=!0,this.__restoreDisabledTo=this.disabled,this.__internalSetDisabled(!0))}retractRequestToBeDisabled(){this._requestedToBeDisabled===!0&&(this._requestedToBeDisabled=!1,this.__internalSetDisabled(this.__restoreDisabledTo))}__internalSetDisabled(e){this.__isUserSettingDisabled=!1,this.disabled=e,this.__isUserSettingDisabled=!0}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`disabled`&&(this.__isUserSettingDisabled&&(this.__restoreDisabledTo=this.disabled),this.disabled===!1&&this._requestedToBeDisabled===!0&&this.__internalSetDisabled(!0))}click(){this.disabled||super.click()}}),Ar=Or(e=>class extends kr(e){static get properties(){return{tabIndex:{type:Number,reflect:!0,attribute:`tabindex`}}}constructor(){super(),this.__isUserSettingTabIndex=!0,this.__restoreTabIndexTo=0,this.__internalSetTabIndex(0)}makeRequestToBeDisabled(){super.makeRequestToBeDisabled(),this._requestedToBeDisabled===!1&&this.tabIndex!=null&&(this.__restoreTabIndexTo=this.tabIndex)}retractRequestToBeDisabled(){super.retractRequestToBeDisabled(),this._requestedToBeDisabled===!0&&this.__internalSetTabIndex(this.__restoreTabIndexTo)}static enabledWarnings=super.enabledWarnings?.filter(e=>e!==`change-in-update`)||[];__internalSetTabIndex(e){this.__isUserSettingTabIndex=!1,this.tabIndex=e,this.__isUserSettingTabIndex=!0}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`disabled`&&(this.disabled?this.__internalSetTabIndex(-1):this.__internalSetTabIndex(this.__restoreTabIndexTo)),e===`tabIndex`&&(this.__isUserSettingTabIndex&&this.tabIndex!=null&&(this.__restoreTabIndexTo=this.tabIndex),this.tabIndex!==-1&&this._requestedToBeDisabled===!0&&this.__internalSetTabIndex(-1))}firstUpdated(e){super.firstUpdated(e),this.disabled&&this.__internalSetTabIndex(-1)}}),{I:jr}=f,Mr=e=>e===null||typeof e!=`object`&&typeof e!=`function`,Nr=(e,t)=>t===void 0?e?._$litType$!==void 0:e?._$litType$===t,Pr=e=>e.strings===void 0,Fr=()=>document.createComment(``),Ir=(e,t,n)=>{let r=e._$AA.parentNode,i=t===void 0?e._$AB:t._$AA;if(n===void 0)n=new jr(r.insertBefore(Fr(),i),r.insertBefore(Fr(),i),e,e.options);else{let t=n._$AB.nextSibling,a=n._$AM,o=a!==e;if(o){let t;n._$AQ?.(e),n._$AM=e,n._$AP!==void 0&&(t=e._$AU)!==a._$AU&&n._$AP(t)}if(t!==i||o){let e=n._$AA;for(;e!==t;){let t=e.nextSibling;r.insertBefore(e,i),e=t}}}return n},Lr=(e,t,n=e)=>(e._$AI(t,n),e),Rr={},zr=(e,t=Rr)=>e._$AH=t,Br=e=>e._$AH,Vr=e=>{e._$AR(),e._$AA.remove()};function Hr(e){return e instanceof Node?`node`:Nr(e)?`template-result`:!Array.isArray(e)&&typeof e==`object`&&`template`in e?`slot-rerender-object`:null}var Ur=Or(e=>class extends e{get slots(){return{}}constructor(){super(),this.__renderMetaPerSlot=new Map,this.__slotsThatNeedRerender=new Set,this.__slotsProvidedByUserOnFirstConnected=new Set,this.__privateSlots=new Set}connectedCallback(){super.connectedCallback(),this._connectSlotMixin()}__rerenderSlot(e){let t=this.slots[e]();this.__renderTemplateInScopedContext({renderAsDirectHostChild:t.renderAsDirectHostChild,template:t.template,slotName:e}),t.afterRender?.()}update(e){super.update(e);for(let e of this.__slotsThatNeedRerender)this.__rerenderSlot(e)}__renderTemplateInScopedContext({template:e,slotName:t,renderAsDirectHostChild:n}){if(!this.__renderMetaPerSlot.has(t)){let r=!!ShadowRoot.prototype.createElement;this.shadowRoot||console.error(`[SlotMixin] No shadowRoot was found`);let i=(r?this.shadowRoot:document).createElement(`div`),a=document.createComment(`_start_slot_${t}_`),o=document.createComment(`_end_slot_${t}_`);i.appendChild(a),i.appendChild(o);let{creationScope:s,host:c}=this.renderOptions;if(_(e,i,{renderBefore:o,creationScope:s,host:c}),n){let e=Array.from(i.childNodes);this.__appendNodes({nodes:e,renderParent:this,slotName:t})}else i.slot=t,this.appendChild(i);this.__renderMetaPerSlot.set(t,{renderTargetThatRespectsShadowRootScoping:i,renderBefore:o});return}let{renderBefore:r,renderTargetThatRespectsShadowRootScoping:i}=this.__renderMetaPerSlot.get(t),a=n?this:i,{creationScope:o,host:s}=this.renderOptions;_(e,a,{creationScope:o,host:s,renderBefore:r}),n&&r.previousElementSibling&&!r.previousElementSibling.slot&&(r.previousElementSibling.slot=t)}__appendNodes({nodes:e,renderParent:t=this,slotName:n}){for(let r of e)r instanceof Element&&n&&n!==``&&r.setAttribute(`slot`,n),t.appendChild(r)}__initSlots(e){for(let t of e){if(this.__slotsProvidedByUserOnFirstConnected.has(t))continue;let e=this.slots[t]();if(e!==void 0)switch(this.__isConnectedSlotMixin||this.__privateSlots.add(t),Hr(e)){case`template-result`:this.__renderTemplateInScopedContext({template:e,renderAsDirectHostChild:!0,slotName:t});break;case`node`:this.__appendNodes({nodes:[e],renderParent:this,slotName:t});break;case`slot-rerender-object`:this.__slotsThatNeedRerender.add(t),e.firstRenderOnConnected&&this.__rerenderSlot(t);break;default:throw Error(`Slot "${t}" configured inside "get slots()" (in prototype) of ${this.constructor.name} may return these types: TemplateResult | Node | {template:TemplateResult, afterRender?:function} | undefined.
              You provided: ${e}`)}}}_connectSlotMixin(){if(this.__isConnectedSlotMixin)return;let e=Object.keys(this.slots);for(let t of e)(t===``?Array.from(this.children).find(e=>!e.hasAttribute(`slot`)):Array.from(this.children).find(e=>e.slot===t))&&this.__slotsProvidedByUserOnFirstConnected.add(t);this.__initSlots(e),this.__isConnectedSlotMixin=!0}_isPrivateSlot(e){return this.__privateSlots.has(e)}});function Wr(e=`google-chrome`){let t=globalThis.navigator,n=!!t.userAgentData&&t.userAgentData.brands.some(e=>e.brand===`Chromium`);if(e===`chromium`)return n;let r=globalThis.navigator?.vendor,i=globalThis.opr!==void 0,a=globalThis.userAgent?.indexOf(`Edge`)>-1,o=globalThis.userAgent?.match(`CriOS`);if(e===`ios`)return o;if(e===`google-chrome`)return n!=null&&r===`Google Inc.`&&i===!1&&a===!1}var Gr={isIE11:/Trident/.test(globalThis.navigator?.userAgent),isChrome:Wr(),isIOSChrome:Wr(`ios`),isChromium:Wr(`chromium`),isFirefox:globalThis.navigator?.userAgent.toLowerCase().indexOf(`firefox`)>-1,isMac:globalThis.navigator?.appVersion?.indexOf(`Mac`)!==-1,isIOS:/iPhone|iPad|iPod/i.test(globalThis.navigator?.userAgent),isMacSafari:globalThis.navigator?.vendor&&globalThis.navigator?.vendor.indexOf(`Apple`)>-1&&globalThis.navigator?.userAgent&&globalThis.navigator?.userAgent.indexOf(`CriOS`)===-1&&globalThis.navigator?.userAgent.indexOf(`FxiOS`)===-1&&globalThis.navigator?.appVersion.indexOf(`Mac`)!==-1};function Kr(e=``){return`${e.length>0?`${e}-`:``}${Math.random().toString(36).substr(2,10)}`}var qr=e=>e.key===` `||e.key===`Enter`,Jr=e=>e.key===` `,Yr=class extends Ar(b){static get properties(){return{active:{type:Boolean,reflect:!0},type:{type:String,reflect:!0}}}render(){return p` <div class="button-content"><slot></slot></div> `}static get styles(){return[h`
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
      `]}constructor(){super(),this.type=`button`,this.active=!1,this.__setupEvents()}connectedCallback(){super.connectedCallback(),this.hasAttribute(`role`)||this.setAttribute(`role`,`button`)}updated(e){super.updated(e),e.has(`disabled`)&&(this.disabled?this.setAttribute(`aria-disabled`,`true`):this.getAttribute(`aria-disabled`)!==null&&this.removeAttribute(`aria-disabled`))}__setupEvents(){this.addEventListener(`mousedown`,this.__mousedownHandler),this.addEventListener(`keydown`,this.__keydownHandler),this.addEventListener(`keyup`,this.__keyupHandler)}__mousedownHandler(){this.active=!0;let e=()=>{this.active=!1,document.removeEventListener(`mouseup`,e),this.removeEventListener(`mouseup`,e)};document.addEventListener(`mouseup`,e),this.addEventListener(`mouseup`,e)}__keydownHandler(e){if(this.active||!qr(e)){Jr(e)&&e.preventDefault();return}Jr(e)&&e.preventDefault(),this.active=!0;let t=e=>{qr(e)&&(this.active=!1,document.removeEventListener(`keyup`,t,!0))};document.addEventListener(`keyup`,t,!0)}__keyupHandler(e){if(qr(e)){if(e.target&&e.target!==this)return;this.click()}}},Xr=class extends Yr{constructor(){super(),this.type=`reset`,this.__setupDelegationInConstructor(),this.__submitAndResetHelperButton=document.createElement(`button`),this.__preventEventLeakage=this.__preventEventLeakage.bind(this)}connectedCallback(){super.connectedCallback(),this.updateComplete.then(()=>{this._setupSubmitAndResetHelperOnConnected()})}disconnectedCallback(){super.disconnectedCallback(),this._teardownSubmitAndResetHelperOnDisconnected()}__preventEventLeakage(e){e.target===this.__submitAndResetHelperButton&&e.stopImmediatePropagation()}_setupSubmitAndResetHelperOnConnected(){this.appendChild(this.__submitAndResetHelperButton),this._form=this.__submitAndResetHelperButton.form,this.removeChild(this.__submitAndResetHelperButton),this._form&&this._form.addEventListener(`click`,this.__preventEventLeakage)}_teardownSubmitAndResetHelperOnDisconnected(){this._form&&this._form.removeEventListener(`click`,this.__preventEventLeakage)}async __clickDelegationHandler(e){this._form||await this.updateComplete,(this.type===`submit`||this.type===`reset`)&&e.target===this&&this._form&&(this.__submitAndResetHelperButton.type=this.type,this._form.appendChild(this.__submitAndResetHelperButton),this.__submitAndResetHelperButton.click(),this._form.removeChild(this.__submitAndResetHelperButton))}__setupDelegationInConstructor(){this.addEventListener(`click`,this.__clickDelegationHandler,!0)}},Zr=new WeakMap;function Qr(){let e=document.createElement(`button`);return e.tabIndex=-1,e.type=`submit`,e.setAttribute(`aria-hidden`,`true`),e.style.cssText=`
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
  `,e}var $r=class extends Xr{get _nativeButtonNode(){return Zr.get(this._form)?.helper||null}constructor(){super(),this.type=`submit`,this.__implicitSubmitHelperButton=null}_setupSubmitAndResetHelperOnConnected(){if(super._setupSubmitAndResetHelperOnConnected(),!this._form||this.type!==`submit`)return;let e=this._form;if(!Zr.get(this._form)){let t=Qr(),n=document.createElement(`div`);n.appendChild(t),Zr.set(this._form,{lionButtons:new Set,helper:t,observer:new MutationObserver(()=>{e.appendChild(n)})}),e.appendChild(n),Zr.get(e)?.observer.observe(n,{childList:!0})}Zr.get(e)?.lionButtons.add(this)}_teardownSubmitAndResetHelperOnDisconnected(){if(super._teardownSubmitAndResetHelperOnDisconnected(),this._form){let e=Zr.get(this._form);e&&(e.lionButtons.delete(this),e.lionButtons.size||(this._form.contains(e.helper)&&e.helper.remove(),Zr.get(this._form)?.observer.disconnect(),Zr.delete(this._form)))}}},ei=h`
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
`,ti=Object.prototype.toString;function ni(e){return typeof e==`function`||ti.call(e)===`[object Function]`}function ri(e){var t=Number(e);return isNaN(t)?0:t===0||!isFinite(t)?t:(t>0?1:-1)*Math.floor(Math.abs(t))}var ii=2**53-1;function ai(e){var t=ri(e);return Math.min(Math.max(t,0),ii)}function oi(e,t){var n=Array,r=Object(e);if(e==null)throw TypeError(`Array.from requires an array-like object - not null or undefined`);if(t!==void 0&&!ni(t))throw TypeError(`Array.from: when provided, the second argument must be a function`);for(var i=ai(r.length),a=ni(n)?Object(new n(i)):Array(i),o=0,s;o<i;)s=r[o],t?a[o]=t(s,o):a[o]=s,o+=1;return a.length=i,a}function si(e){"@babel/helpers - typeof";return si=typeof Symbol==`function`&&typeof Symbol.iterator==`symbol`?function(e){return typeof e}:function(e){return e&&typeof Symbol==`function`&&e.constructor===Symbol&&e!==Symbol.prototype?`symbol`:typeof e},si(e)}function ci(e,t){if(!(e instanceof t))throw TypeError(`Cannot call a class as a function`)}function li(e,t){for(var n=0;n<t.length;n++){var r=t[n];r.enumerable=r.enumerable||!1,r.configurable=!0,`value`in r&&(r.writable=!0),Object.defineProperty(e,fi(r.key),r)}}function ui(e,t,n){return t&&li(e.prototype,t),n&&li(e,n),Object.defineProperty(e,`prototype`,{writable:!1}),e}function di(e,t,n){return t=fi(t),t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function fi(e){var t=pi(e,`string`);return si(t)==`symbol`?t:t+``}function pi(e,t){if(si(e)!=`object`||!e)return e;var n=e[Symbol.toPrimitive];if(n!==void 0){var r=n.call(e,t||`default`);if(si(r)!=`object`)return r;throw TypeError(`@@toPrimitive must return a primitive value.`)}return(t===`string`?String:Number)(e)}var mi=function(){function e(){var t=arguments.length>0&&arguments[0]!==void 0?arguments[0]:[];ci(this,e),di(this,`items`,void 0),this.items=t}return ui(e,[{key:`add`,value:function(e){return this.has(e)===!1&&this.items.push(e),this}},{key:`clear`,value:function(){this.items=[]}},{key:`delete`,value:function(e){var t=this.items.length;return this.items=this.items.filter(function(t){return t!==e}),t!==this.items.length}},{key:`forEach`,value:function(e){var t=this;this.items.forEach(function(n){e(n,n,t)})}},{key:`has`,value:function(e){return this.items.indexOf(e)!==-1}},{key:`size`,get:function(){return this.items.length}}])}(),hi=typeof Set>`u`?Set:mi;function gi(e){return e.localName??e.tagName.toLowerCase()}var _i={article:`article`,aside:`complementary`,button:`button`,datalist:`listbox`,dd:`definition`,details:`group`,dialog:`dialog`,dt:`term`,fieldset:`group`,figure:`figure`,form:`form`,footer:`contentinfo`,h1:`heading`,h2:`heading`,h3:`heading`,h4:`heading`,h5:`heading`,h6:`heading`,header:`banner`,hr:`separator`,html:`document`,legend:`legend`,li:`listitem`,math:`math`,main:`main`,menu:`list`,nav:`navigation`,ol:`list`,optgroup:`group`,option:`option`,output:`status`,progress:`progressbar`,section:`region`,summary:`button`,table:`table`,tbody:`rowgroup`,textarea:`textbox`,tfoot:`rowgroup`,td:`cell`,th:`columnheader`,thead:`rowgroup`,tr:`row`,ul:`list`},vi={caption:new Set([`aria-label`,`aria-labelledby`]),code:new Set([`aria-label`,`aria-labelledby`]),deletion:new Set([`aria-label`,`aria-labelledby`]),emphasis:new Set([`aria-label`,`aria-labelledby`]),generic:new Set([`aria-label`,`aria-labelledby`,`aria-roledescription`]),insertion:new Set([`aria-label`,`aria-labelledby`]),none:new Set([`aria-label`,`aria-labelledby`]),paragraph:new Set([`aria-label`,`aria-labelledby`]),presentation:new Set([`aria-label`,`aria-labelledby`]),strong:new Set([`aria-label`,`aria-labelledby`]),subscript:new Set([`aria-label`,`aria-labelledby`]),superscript:new Set([`aria-label`,`aria-labelledby`])};function yi(e,t){return[`aria-atomic`,`aria-busy`,`aria-controls`,`aria-current`,`aria-description`,`aria-describedby`,`aria-details`,`aria-dropeffect`,`aria-flowto`,`aria-grabbed`,`aria-hidden`,`aria-keyshortcuts`,`aria-label`,`aria-labelledby`,`aria-live`,`aria-owns`,`aria-relevant`,`aria-roledescription`].some(function(n){var r;return e.hasAttribute(n)&&!((r=vi[t])!=null&&r.has(n))})}function bi(e,t){return yi(e,t)}function xi(e){var t=Ci(e);if(t===null||wi.indexOf(t)!==-1){var n=Si(e);if(wi.indexOf(t||``)===-1||bi(e,n||``))return n}return t}function Si(e){var t=_i[gi(e)];if(t!==void 0)return t;switch(gi(e)){case`a`:case`area`:case`link`:if(e.hasAttribute(`href`))return`link`;break;case`img`:return e.getAttribute(`alt`)===``&&!bi(e,`img`)?`presentation`:`img`;case`input`:var n=e.type;switch(n){case`button`:case`image`:case`reset`:case`submit`:return`button`;case`checkbox`:case`radio`:return n;case`range`:return`slider`;case`email`:case`tel`:case`text`:case`url`:return e.hasAttribute(`list`)?`combobox`:`textbox`;case`search`:return e.hasAttribute(`list`)?`combobox`:`searchbox`;case`number`:return`spinbutton`;default:return null}case`select`:return e.hasAttribute(`multiple`)||e.size>1?`listbox`:`combobox`}return null}function Ci(e){var t=e.getAttribute(`role`);if(t!==null){var n=t.trim().split(` `)[0];if(n.length>0)return n}return null}var wi=[`presentation`,`none`];function Ti(e){return e!==null&&e.nodeType===e.ELEMENT_NODE}function Ei(e){return Ti(e)&&gi(e)===`caption`}function Di(e){return Ti(e)&&gi(e)===`input`}function Oi(e){return Ti(e)&&gi(e)===`optgroup`}function ki(e){return Ti(e)&&gi(e)===`select`}function Ai(e){return Ti(e)&&gi(e)===`table`}function ji(e){return Ti(e)&&gi(e)===`textarea`}function Mi(e){var t=(e.ownerDocument===null?e:e.ownerDocument).defaultView;if(t===null)throw TypeError(`no window available`);return t}function Ni(e){return Ti(e)&&gi(e)===`fieldset`}function Pi(e){return Ti(e)&&gi(e)===`legend`}function Fi(e){return Ti(e)&&gi(e)===`slot`}function Ii(e){return Ti(e)&&e.ownerSVGElement!==void 0}function Li(e){return Ti(e)&&gi(e)===`svg`}function Ri(e){return Ii(e)&&gi(e)===`title`}function zi(e,t){if(Ti(e)&&e.hasAttribute(t)){var n=e.getAttribute(t).split(` `),r=e.getRootNode?e.getRootNode():e.ownerDocument;return n.map(function(e){return r.getElementById(e)}).filter(function(e){return e!==null})}return[]}function Bi(e,t){return Ti(e)?t.indexOf(xi(e))!==-1:!1}function Vi(e){return e.trim().replace(/\s\s+/g,` `)}function Hi(e,t){if(!Ti(e))return!1;if(e.hasAttribute(`hidden`)||e.getAttribute(`aria-hidden`)===`true`)return!0;var n=t(e);return n.getPropertyValue(`display`)===`none`||n.getPropertyValue(`visibility`)===`hidden`}function Ui(e){return Bi(e,[`button`,`combobox`,`listbox`,`textbox`])||Wi(e,`range`)}function Wi(e,t){if(!Ti(e))return!1;switch(t){case`range`:return Bi(e,[`meter`,`progressbar`,`scrollbar`,`slider`,`spinbutton`]);default:throw TypeError(`No knowledge about abstract role '${t}'. This is likely a bug :(`)}}function Gi(e,t){var n=oi(e.querySelectorAll(t));return zi(e,`aria-owns`).forEach(function(e){n.push.apply(n,oi(e.querySelectorAll(t)))}),n}function Ki(e){return ki(e)?e.selectedOptions||Gi(e,`[selected]`):Gi(e,`[aria-selected="true"]`)}function qi(e){return Bi(e,wi)}function Ji(e){return Ei(e)}function Yi(e){return Bi(e,[`button`,`cell`,`checkbox`,`columnheader`,`gridcell`,`heading`,`label`,`legend`,`link`,`menuitem`,`menuitemcheckbox`,`menuitemradio`,`option`,`radio`,`row`,`rowheader`,`switch`,`tab`,`tooltip`,`treeitem`])}function Xi(e){return!1}function Zi(e){return Di(e)||ji(e)?e.value:e.textContent||``}function Qi(e){var t=e.getPropertyValue(`content`);return/^["'].*["']$/.test(t)?t.slice(1,-1):``}function $i(e){var t=gi(e);return t===`button`||t===`input`&&e.getAttribute(`type`)!==`hidden`||t===`meter`||t===`output`||t===`progress`||t===`select`||t===`textarea`}function ea(e){if($i(e))return e;var t=null;return e.childNodes.forEach(function(e){if(t===null&&Ti(e)){var n=ea(e);n!==null&&(t=n)}}),t}function ta(e){if(e.control!==void 0)return e.control;var t=e.getAttribute(`for`);return t===null?ea(e):e.ownerDocument.getElementById(t)}function na(e){var t=e.labels;if(t===null)return t;if(t!==void 0)return oi(t);if(!$i(e))return null;var n=e.ownerDocument;return oi(n.querySelectorAll(`label`)).filter(function(t){return ta(t)===e})}function ra(e){var t=e.assignedNodes();return t.length===0?oi(e.childNodes):t}function ia(e){var t=arguments.length>1&&arguments[1]!==void 0?arguments[1]:{},n=new hi,r=typeof Map>`u`?void 0:new Map,i=Mi(e),a=t.compute,o=a===void 0?`name`:a,s=t.computedStyleSupportsPseudoElements,c=s===void 0?t.getComputedStyle!==void 0:s,l=t.getComputedStyle,u=l===void 0?i.getComputedStyle.bind(i):l,d=t.hidden,f=d===void 0?!1:d,p=function(e,t){if(t!==void 0)throw Error(`use uncachedGetComputedStyle directly for pseudo elements`);if(r===void 0)return u(e);var n=r.get(e);if(n)return n;var i=u(e,t);return r.set(e,i),i};function m(e,t){var n=``;if(Ti(e)&&c&&(n=`${Qi(u(e,`::before`))} ${n}`),(Fi(e)?ra(e):oi(e.childNodes).concat(zi(e,`aria-owns`))).forEach(function(e){var r=v(e,{isEmbeddedInLabel:t.isEmbeddedInLabel,isReferenced:!1,recursion:!0}),i=(Ti(e)?p(e).getPropertyValue(`display`):`inline`)===`inline`?``:` `;n+=`${i}${r}${i}`}),Ti(e)&&c){var r=Qi(u(e,`::after`));n=`${n} ${r}`}return n.trim()}function h(e,t){var r=e.getAttributeNode(t);return r!==null&&!n.has(r)&&r.value.trim()!==``?(n.add(r),r.value):null}function g(e){return Ti(e)?h(e,`title`):null}function _(e){if(!Ti(e))return null;if(Ni(e)){n.add(e);for(var t=oi(e.childNodes),r=0;r<t.length;r+=1){var i=t[r];if(Pi(i))return v(i,{isEmbeddedInLabel:!1,isReferenced:!1,recursion:!1})}}else if(Ai(e)){n.add(e);for(var a=oi(e.childNodes),o=0;o<a.length;o+=1){var s=a[o];if(Ei(s))return v(s,{isEmbeddedInLabel:!1,isReferenced:!1,recursion:!1})}}else if(Li(e)){n.add(e);for(var c=oi(e.childNodes),l=0;l<c.length;l+=1){var u=c[l];if(Ri(u))return u.textContent}return null}else if(gi(e)===`img`||gi(e)===`area`){var d=h(e,`alt`);if(d!==null)return d}else if(Oi(e)){var f=h(e,`label`);if(f!==null)return f}if(Di(e)&&(e.type===`button`||e.type===`submit`||e.type===`reset`)){var p=h(e,`value`);if(p!==null)return p;if(e.type===`submit`)return`Submit`;if(e.type===`reset`)return`Reset`}var g=na(e);if(g!==null&&g.length!==0)return n.add(e),oi(g).map(function(e){return v(e,{isEmbeddedInLabel:!0,isReferenced:!1,recursion:!0})}).filter(function(e){return e.length>0}).join(` `);if(Di(e)&&e.type===`image`){var _=h(e,`alt`);if(_!==null)return _;var y=h(e,`title`);return y===null?`Submit Query`:y}if(Bi(e,[`button`])){var b=m(e,{isEmbeddedInLabel:!1,isReferenced:!1});if(b!==``)return b}return null}function v(e,t){if(n.has(e))return``;if(!f&&Hi(e,p)&&!t.isReferenced)return n.add(e),``;var r=Ti(e)?e.getAttributeNode(`aria-labelledby`):null,i=r!==null&&!n.has(r)?zi(e,`aria-labelledby`):[];if(o===`name`&&!t.isReferenced&&i.length>0)return n.add(r),i.map(function(e){return v(e,{isEmbeddedInLabel:t.isEmbeddedInLabel,isReferenced:!0,recursion:!1})}).join(` `);var a=t.recursion&&Ui(e)&&o===`name`;if(!a){var s=(Ti(e)&&e.getAttribute(`aria-label`)||``).trim();if(s!==``&&o===`name`)return n.add(e),s;if(!qi(e)){var c=_(e);if(c!==null)return n.add(e),c}}if(Bi(e,[`menu`]))return n.add(e),``;if(a||t.isEmbeddedInLabel||t.isReferenced){if(Bi(e,[`combobox`,`listbox`])){n.add(e);var l=Ki(e);return l.length===0?Di(e)?e.value:``:oi(l).map(function(e){return v(e,{isEmbeddedInLabel:t.isEmbeddedInLabel,isReferenced:!1,recursion:!0})}).join(` `)}if(Wi(e,`range`))return n.add(e),e.hasAttribute(`aria-valuetext`)?e.getAttribute(`aria-valuetext`):e.hasAttribute(`aria-valuenow`)?e.getAttribute(`aria-valuenow`):e.getAttribute(`value`)||``;if(Bi(e,[`textbox`]))return n.add(e),Zi(e)}if(Yi(e)||Ti(e)&&t.isReferenced||Ji(e)||Xi(e)){var u=m(e,{isEmbeddedInLabel:t.isEmbeddedInLabel,isReferenced:!1});if(u!==``)return n.add(e),u}if(e.nodeType===e.TEXT_NODE)return n.add(e),e.textContent||``;if(t.recursion)return n.add(e),m(e,{isEmbeddedInLabel:t.isEmbeddedInLabel,isReferenced:!1});var d=g(e);return d===null?(n.add(e),``):(n.add(e),d)}return Vi(v(e,{isEmbeddedInLabel:!1,isReferenced:o===`description`,recursion:!1}))}function aa(e){return Bi(e,[`caption`,`code`,`deletion`,`emphasis`,`generic`,`insertion`,`none`,`paragraph`,`presentation`,`strong`,`subscript`,`superscript`])}function oa(e){var t=arguments.length>1&&arguments[1]!==void 0?arguments[1]:{};return aa(e)?``:ia(e,t)}var sa=class extends $r{constructor(...e){super(...e),this.appearance=`accent`,this.variant=`default`,this.size=`medium`,this.loading=!1,this.align=`center`,this._hasAccessibilityError=!1}static get styles(){return[...super.styles,ei]}async firstUpdated(e){super.firstUpdated(e),await this.updateComplete;let t=this.querySelectorAll(`craft-icon, craft-spinner`);await Promise.all(Array.from(t).map(e=>e.updateComplete)),this.accessibleName||=oa(this),this._hasAccessibilityError=!this.accessibleName||this.accessibleName.trim()===``}render(){return p`
      <div
        class="${D({"button-content":!0,"button-content--start":this.align===`start`,"button-content--end":this.align===`end`,"a11y-error":this._hasAccessibilityError})}"
        part="content"
      >
        <slot name="prefix" class="prefix" part="prefix"></slot>
        <slot class="label" part="label"></slot>
        <slot name="suffix" class="suffix" part="suffix"></slot>
      </div>
      ${this.loading?p`<craft-spinner part="spinner"></craft-spinner>`:y}
    `}};d([x()],sa.prototype,`accessibleName`,void 0),d([x({reflect:!0})],sa.prototype,`appearance`,void 0),d([x({reflect:!0})],sa.prototype,`variant`,void 0),d([x({reflect:!0})],sa.prototype,`size`,void 0),d([x({reflect:!0,type:Boolean})],sa.prototype,`loading`,void 0),d([x()],sa.prototype,`align`,void 0),d([S()],sa.prototype,`_hasAccessibilityError`,void 0),customElements.get(`craft-button`)||customElements.define(`craft-button`,sa);var ca=class extends Event{constructor(){super(`wa-load`,{bubbles:!0,cancelable:!1,composed:!0})}},la=class extends Event{constructor(){super(`wa-error`,{bubbles:!0,cancelable:!1,composed:!0})}},ua=`:host {
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
`,da=Symbol(),fa=Symbol(),pa,ma=new Map,ha=class extends rt{constructor(){super(...arguments),this.svg=null,this.autoWidth=!1,this.swapOpacity=!1,this.label=``,this.library=`default`,this.resolveIcon=async(e,t)=>{let n;if(t?.spriteSheet){this.hasUpdated||await this.updateComplete,this.svg=p`<svg part="svg">
        <use part="use" href="${e}"></use>
      </svg>`,await this.updateComplete;let n=this.shadowRoot.querySelector(`[part='svg']`);return typeof t.mutator==`function`&&t.mutator(n,this),this.svg}try{if(n=await fetch(e,{mode:`cors`}),!n.ok)return n.status===410?da:fa}catch{return fa}try{let e=document.createElement(`div`);e.innerHTML=await n.text();let t=e.firstElementChild;if(t?.tagName?.toLowerCase()!==`svg`)return da;pa||=new DOMParser;let r=pa.parseFromString(t.outerHTML,`text/html`).body.querySelector(`svg`);return r?(r.part.add(`svg`),document.adoptNode(r)):da}catch{return da}}}connectedCallback(){super.connectedCallback(),Fe(this)}firstUpdated(e){super.firstUpdated(e),this.setIcon()}disconnectedCallback(){super.disconnectedCallback(),Ie(this)}getIconSource(){let e=Le(this.library),t=this.family||Be();return this.name&&e?{url:e.resolver(this.name,t,this.variant,this.autoWidth),fromLibrary:!0}:{url:this.src,fromLibrary:!1}}handleLabelChange(){typeof this.label==`string`&&this.label.length>0?(this.setAttribute(`role`,`img`),this.setAttribute(`aria-label`,this.label),this.removeAttribute(`aria-hidden`)):(this.removeAttribute(`role`),this.removeAttribute(`aria-label`),this.setAttribute(`aria-hidden`,`true`))}async setIcon(){let{url:e,fromLibrary:t}=this.getIconSource(),n=t?Le(this.library):void 0;if(!e){this.svg=null;return}let r=ma.get(e);r||(r=this.resolveIcon(e,n),ma.set(e,r));let i=await r;if(i===fa&&ma.delete(e),e===this.getIconSource().url){if(Nr(i)){this.svg=i;return}switch(i){case fa:case da:this.svg=null,this.dispatchEvent(new la);break;default:this.svg=i.cloneNode(!0),n?.mutator?.(this.svg,this),this.dispatchEvent(new ca)}}}updated(e){super.updated(e);let t=Le(this.library),n=this.shadowRoot?.querySelector(`svg`);n&&t?.mutator?.(n,this)}render(){return this.hasUpdated?this.svg:p`<svg part="svg" fill="currentColor" width="16" height="16"></svg>`}};ha.css=ua,k([S()],ha.prototype,`svg`,2),k([x({reflect:!0})],ha.prototype,`name`,2),k([x({reflect:!0})],ha.prototype,`family`,2),k([x({reflect:!0})],ha.prototype,`variant`,2),k([x({attribute:`auto-width`,type:Boolean,reflect:!0})],ha.prototype,`autoWidth`,2),k([x({attribute:`swap-opacity`,type:Boolean,reflect:!0})],ha.prototype,`swapOpacity`,2),k([x()],ha.prototype,`src`,2),k([x()],ha.prototype,`label`,2),k([x({reflect:!0})],ha.prototype,`library`,2),k([gr(`label`)],ha.prototype,`handleLabelChange`,1),k([gr([`family`,`name`,`library`,`variant`,`src`,`autoWidth`,`swapOpacity`])],ha.prototype,`setIcon`,1),ha=k([C(`wa-icon`)],ha);var ga=class extends ha{static get styles(){return[ha.styles,h`
        :host {
          font-size: 0.8em;
        }
      `]}};customElements.get(`craft-icon`)||customElements.define(`craft-icon`,ga);var _a=h`
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
`,va=class extends b{constructor(...e){super(...e),this.label=null,this._gradientId=null}connectedCallback(){super.connectedCallback(),this._gradientId=`avatar-gradient-${Math.random().toString(36).slice(2,8)}`}text(){return this.label?this.label.split(` `).map(e=>e.charAt(0).toUpperCase()).join(``):`?`}render(){return p`
      <span class="avatar">
        <svg
          viewBox="0 0 100 100"
          xmlns="http://www.w3.org/2000/svg"
          role="img"
        >
          ${this.label?p`<title>${this.label}</title>`:``}
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
    `}};va.styles=[_a],d([x()],va.prototype,`label`,void 0),d([S()],va.prototype,`_gradientId`,void 0),customElements.get(`craft-avatar`)||customElements.define(`craft-avatar`,va);var ya=h`
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
`,ba=h`
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
`,xa=h`
  ${ba}

  ::slotted([slot='input']) {
    font: inherit;
    padding-block: 0;
    border: none;
    appearance: none;
    padding-inline: var(--c-input-spacing-inline);
    background-color: transparent;
  }

  .input-group__container {
    ${ya}
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
`,Sa=window,Ca=new WeakMap;function wa(e){Sa.applyFocusVisiblePolyfill&&!Ca.has(e)&&(Sa.applyFocusVisiblePolyfill(e),Ca.set(e,void 0))}var Ta=Or(e=>class extends e{static get properties(){return{focused:{type:Boolean,reflect:!0},focusedVisible:{type:Boolean,reflect:!0,attribute:`focused-visible`},autofocus:{type:Boolean,reflect:!0}}}constructor(){super(),this.focused=!1,this.focusedVisible=!1,this.autofocus=!1}firstUpdated(e){super.firstUpdated(e),this.__registerEventsForFocusMixin(),this.__syncAutofocusToFocusableElement()}disconnectedCallback(){super.disconnectedCallback(),this.__teardownEventsForFocusMixin()}updated(e){super.updated(e),e.has(`autofocus`)&&this.__syncAutofocusToFocusableElement()}__syncAutofocusToFocusableElement(){this._focusableNode&&(this.hasAttribute(`autofocus`)?this._focusableNode.setAttribute(`autofocus`,``):this._focusableNode.removeAttribute(`autofocus`))}focus(){this._focusableNode?.focus()}blur(){this._focusableNode?.blur()}get _focusableNode(){return this._inputNode||document.createElement(`input`)}__onFocus(){if(this.focused=!0,typeof Sa.applyFocusVisiblePolyfill==`function`)this.focusedVisible=this._focusableNode.hasAttribute(`data-focus-visible-added`);else try{this.focusedVisible=this._focusableNode.matches(`:focus-visible`)}catch{this.focusedVisible=!1}}__onBlur(){this.focused=!1,this.focusedVisible=!1}__registerEventsForFocusMixin(){wa(this.getRootNode()),this.__redispatchFocus=e=>{e.stopPropagation(),this.dispatchEvent(new Event(`focus`))},this._focusableNode.addEventListener(`focus`,this.__redispatchFocus),this.__redispatchBlur=e=>{e.stopPropagation(),this.dispatchEvent(new Event(`blur`))},this._focusableNode.addEventListener(`blur`,this.__redispatchBlur),this.__redispatchFocusin=e=>{e.stopPropagation(),this.__onFocus(),this.dispatchEvent(new Event(`focusin`,{bubbles:!0,composed:!0}))},this._focusableNode.addEventListener(`focusin`,this.__redispatchFocusin),this.__redispatchFocusout=e=>{e.stopPropagation(),this.__onBlur(),this.dispatchEvent(new Event(`focusout`,{bubbles:!0,composed:!0}))},this._focusableNode.addEventListener(`focusout`,this.__redispatchFocusout)}__teardownEventsForFocusMixin(){this._focusableNode&&(this._focusableNode?.removeEventListener(`focus`,this.__redispatchFocus),this._focusableNode?.removeEventListener(`blur`,this.__redispatchBlur),this._focusableNode?.removeEventListener(`focusin`,this.__redispatchFocusin),this._focusableNode?.removeEventListener(`focusout`,this.__redispatchFocusout))}});function Ea(e,t){return t={exports:{}},e(t,t.exports),t.exports}var Da=`long`,Oa=`short`,ka=`narrow`,j=`numeric`,Aa=`2-digit`,ja={number:{decimal:{style:`decimal`},integer:{style:`decimal`,maximumFractionDigits:0},currency:{style:`currency`,currency:`USD`},percent:{style:`percent`},default:{style:`decimal`}},date:{short:{month:j,day:j,year:Aa},medium:{month:Oa,day:j,year:j},long:{month:Da,day:j,year:j},full:{month:Da,day:j,year:j,weekday:Da},default:{month:Oa,day:j,year:j}},time:{short:{hour:j,minute:j},medium:{hour:j,minute:j,second:j},long:{hour:j,minute:j,second:j,timeZoneName:Oa},full:{hour:j,minute:j,second:j,timeZoneName:Oa},default:{hour:j,minute:j,second:j}},duration:{default:{hours:{minimumIntegerDigits:1,maximumFractionDigits:0},minutes:{minimumIntegerDigits:2,maximumFractionDigits:0},seconds:{minimumIntegerDigits:2,maximumFractionDigits:3}}},parseNumberPattern:function(e){if(e){var t={},n=e.match(/\b[A-Z]{3}\b/i),r=e.replace(/[^¤]/g,``).length;if(!r&&n&&(r=1),r?(t.style=`currency`,t.currencyDisplay=r===1?`symbol`:r===2?`code`:`name`,t.currency=n?n[0].toUpperCase():`USD`):e.indexOf(`%`)>=0&&(t.style=`percent`),!/[@#0]/.test(e))return t.style?t:void 0;if(t.useGrouping=e.indexOf(`,`)>=0,/E\+?[@#0]+/i.test(e)||e.indexOf(`@`)>=0){var i=e.replace(/E\+?[@#0]+|[^@#0]/gi,``);t.minimumSignificantDigits=Math.min(Math.max(i.replace(/[^@0]/g,``).length,1),21),t.maximumSignificantDigits=Math.min(Math.max(i.length,1),21)}else{for(var a=e.replace(/[^#0.]/g,``).split(`.`),o=a[0],s=o.length-1;o[s]===`0`;)--s;t.minimumIntegerDigits=Math.min(Math.max(o.length-1-s,1),21);var c=a[1]||``;for(s=0;c[s]===`0`;)++s;for(t.minimumFractionDigits=Math.min(Math.max(s,0),20);c[s]===`#`;)++s;t.maximumFractionDigits=Math.min(Math.max(s,0),20)}return t}},parseDatePattern:function(e){if(e){for(var t={},n=0;n<e.length;){for(var r=e[n],i=1;e[++n]===r;)++i;switch(r){case`G`:t.era=i===5?ka:i===4?Da:Oa;break;case`y`:case`Y`:t.year=i===2?Aa:j;break;case`M`:case`L`:i=Math.min(Math.max(i-1,0),4),t.month=[j,Aa,Oa,Da,ka][i];break;case`E`:case`e`:case`c`:t.weekday=i===5?ka:i===4?Da:Oa;break;case`d`:case`D`:t.day=i===2?Aa:j;break;case`h`:case`K`:t.hour12=!0,t.hour=i===2?Aa:j;break;case`H`:case`k`:t.hour12=!1,t.hour=i===2?Aa:j;break;case`m`:t.minute=i===2?Aa:j;break;case`s`:case`S`:t.second=i===2?Aa:j;break;case`z`:case`Z`:case`v`:case`V`:t.timeZoneName=i===1?Oa:Da;break}}return Object.keys(t).length?t:void 0}}},Ma=function(e,t){if(typeof e==`string`&&t[e])return e;for(var n=[].concat(e||[]),r=0,i=n.length;r<i;++r)for(var a=n[r].split(`-`);a.length;){var o=a.join(`-`);if(t[o])return o;a.pop()}},Na=`zero`,M=`one`,Pa=`two`,N=`few`,Fa=`many`,P=`other`,F=[function(e){return+e==1?M:P},function(e){var t=+e;return 0<=t&&t<=1?M:P},function(e){var t=Math.floor(Math.abs(+e)),n=+e;return t===0||n===1?M:P},function(e){var t=+e;return t===0?Na:t===1?M:t===2?Pa:3<=t%100&&t%100<=10?N:11<=t%100&&t%100<=99?Fa:P},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length;return t===1&&n===0?M:P},function(e){var t=+e;return t%10==1&&t%100!=11?M:2<=t%10&&t%10<=4&&(t%100<12||14<t%100)?N:t%10==0||5<=t%10&&t%10<=9||11<=t%100&&t%100<=14?Fa:P},function(e){var t=+e;return t%10==1&&t%100!=11&&t%100!=71&&t%100!=91?M:t%10==2&&t%100!=12&&t%100!=72&&t%100!=92?Pa:(3<=t%10&&t%10<=4||t%10==9)&&(t%100<10||19<t%100)&&(t%100<70||79<t%100)&&(t%100<90||99<t%100)?N:t!==0&&t%1e6==0?Fa:P},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length,r=+(e+`.`).split(`.`)[1];return n===0&&t%10==1&&t%100!=11||r%10==1&&r%100!=11?M:n===0&&2<=t%10&&t%10<=4&&(t%100<12||14<t%100)||2<=r%10&&r%10<=4&&(r%100<12||14<r%100)?N:P},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length;return t===1&&n===0?M:2<=t&&t<=4&&n===0?N:n===0?P:Fa},function(e){var t=+e;return t===0?Na:t===1?M:t===2?Pa:t===3?N:t===6?Fa:P},function(e){var t=Math.floor(Math.abs(+e)),n=+(``+e).replace(/^[^.]*.?|0+$/g,``);return+e==1||n!==0&&(t===0||t===1)?M:P},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length,r=+(e+`.`).split(`.`)[1];return n===0&&t%100==1||r%100==1?M:n===0&&t%100==2||r%100==2?Pa:n===0&&3<=t%100&&t%100<=4||3<=r%100&&r%100<=4?N:P},function(e){var t=Math.floor(Math.abs(+e));return t===0||t===1?M:P},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length,r=+(e+`.`).split(`.`)[1];return n===0&&(t===1||t===2||t===3)||n===0&&t%10!=4&&t%10!=6&&t%10!=9||n!==0&&r%10!=4&&r%10!=6&&r%10!=9?M:P},function(e){var t=+e;return t===1?M:t===2?Pa:3<=t&&t<=6?N:7<=t&&t<=10?Fa:P},function(e){var t=+e;return t===1||t===11?M:t===2||t===12?Pa:3<=t&&t<=10||13<=t&&t<=19?N:P},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length;return n===0&&t%10==1?M:n===0&&t%10==2?Pa:n===0&&(t%100==0||t%100==20||t%100==40||t%100==60||t%100==80)?N:n===0?P:Fa},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length,r=+e;return t===1&&n===0?M:t===2&&n===0?Pa:n===0&&(r<0||10<r)&&r%10==0?Fa:P},function(e){var t=Math.floor(Math.abs(+e)),n=+(``+e).replace(/^[^.]*.?|0+$/g,``);return n===0&&t%10==1&&t%100!=11||n!==0?M:P},function(e){var t=+e;return t===1?M:t===2?Pa:P},function(e){var t=+e;return t===0?Na:t===1?M:P},function(e){var t=Math.floor(Math.abs(+e)),n=+e;return n===0?Na:(t===0||t===1)&&n!==0?M:P},function(e){var t=+(e+`.`).split(`.`)[1],n=+e;return n%10==1&&(n%100<11||19<n%100)?M:2<=n%10&&n%10<=9&&(n%100<11||19<n%100)?N:t===0?P:Fa},function(e){var t=(e+`.`).split(`.`)[1].length,n=+(e+`.`).split(`.`)[1],r=+e;return r%10==0||11<=r%100&&r%100<=19||t===2&&11<=n%100&&n%100<=19?Na:r%10==1&&r%100!=11||t===2&&n%10==1&&n%100!=11||t!==2&&n%10==1?M:P},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length,r=+(e+`.`).split(`.`)[1];return n===0&&t%10==1&&t%100!=11||r%10==1&&r%100!=11?M:P},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length,r=+e;return t===1&&n===0?M:n!==0||r===0||r!==1&&1<=r%100&&r%100<=19?N:P},function(e){var t=+e;return t===1?M:t===0||2<=t%100&&t%100<=10?N:11<=t%100&&t%100<=19?Fa:P},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length;return t===1&&n===0?M:n===0&&2<=t%10&&t%10<=4&&(t%100<12||14<t%100)?N:n===0&&t!==1&&0<=t%10&&t%10<=1||n===0&&5<=t%10&&t%10<=9||n===0&&12<=t%100&&t%100<=14?Fa:P},function(e){var t=Math.floor(Math.abs(+e));return 0<=t&&t<=1?M:P},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length;return n===0&&t%10==1&&t%100!=11?M:n===0&&2<=t%10&&t%10<=4&&(t%100<12||14<t%100)?N:n===0&&t%10==0||n===0&&5<=t%10&&t%10<=9||n===0&&11<=t%100&&t%100<=14?Fa:P},function(e){var t=Math.floor(Math.abs(+e)),n=+e;return t===0||n===1?M:2<=n&&n<=10?N:P},function(e){var t=Math.floor(Math.abs(+e)),n=+(e+`.`).split(`.`)[1],r=+e;return r===0||r===1||t===0&&n===1?M:P},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length;return n===0&&t%100==1?M:n===0&&t%100==2?Pa:n===0&&3<=t%100&&t%100<=4||n!==0?N:P},function(e){var t=+e;return 0<=t&&t<=1||11<=t&&t<=99?M:P},function(e){var t=+e;return t===1||t===5||t===7||t===8||t===9||t===10?M:t===2||t===3?Pa:t===4?N:t===6?Fa:P},function(e){var t=Math.floor(Math.abs(+e));return t%10==1||t%10==2||t%10==5||t%10==7||t%10==8||t%100==20||t%100==50||t%100==70||t%100==80?M:t%10==3||t%10==4||t%1e3==100||t%1e3==200||t%1e3==300||t%1e3==400||t%1e3==500||t%1e3==600||t%1e3==700||t%1e3==800||t%1e3==900?N:t===0||t%10==6||t%100==40||t%100==60||t%100==90?Fa:P},function(e){var t=+e;return(t%10==2||t%10==3)&&t%100!=12&&t%100!=13?N:P},function(e){var t=+e;return t===1||t===3?M:t===2?Pa:t===4?N:P},function(e){var t=+e;return t===0||t===7||t===8||t===9?Na:t===1?M:t===2?Pa:t===3||t===4?N:t===5||t===6?Fa:P},function(e){var t=+e;return t%10==1&&t%100!=11?M:t%10==2&&t%100!=12?Pa:t%10==3&&t%100!=13?N:P},function(e){var t=+e;return t===1||t===11?M:t===2||t===12?Pa:t===3||t===13?N:P},function(e){var t=+e;return t===1?M:t===2||t===3?Pa:t===4?N:t===6?Fa:P},function(e){var t=+e;return t===1||t===5?M:P},function(e){var t=+e;return t===11||t===8||t===80||t===800?Fa:P},function(e){var t=Math.floor(Math.abs(+e));return t===1?M:t===0||2<=t%100&&t%100<=20||t%100==40||t%100==60||t%100==80?Fa:P},function(e){var t=+e;return t%10==6||t%10==9||t%10==0&&t!==0?Fa:P},function(e){var t=Math.floor(Math.abs(+e));return t%10==1&&t%100!=11?M:t%10==2&&t%100!=12?Pa:(t%10==7||t%10==8)&&t%100!=17&&t%100!=18?Fa:P},function(e){var t=+e;return t===1?M:t===2||t===3?Pa:t===4?N:P},function(e){var t=+e;return 1<=t&&t<=4?M:P},function(e){var t=+e;return t===1||t===5||7<=t&&t<=9?M:t===2||t===3?Pa:t===4?N:t===6?Fa:P},function(e){var t=+e;return t===1?M:t%10==4&&t%100!=14?Fa:P},function(e){var t=+e;return(t%10==1||t%10==2)&&t%100!=11&&t%100!=12?M:P},function(e){var t=+e;return t%10==6||t%10==9||t===10?N:P},function(e){var t=+e;return t%10==3&&t%100!=13?N:P}],Ia={af:{cardinal:F[0]},ak:{cardinal:F[1]},am:{cardinal:F[2]},ar:{cardinal:F[3]},ars:{cardinal:F[3]},as:{cardinal:F[2],ordinal:F[34]},asa:{cardinal:F[0]},ast:{cardinal:F[4]},az:{cardinal:F[0],ordinal:F[35]},be:{cardinal:F[5],ordinal:F[36]},bem:{cardinal:F[0]},bez:{cardinal:F[0]},bg:{cardinal:F[0]},bh:{cardinal:F[1]},bn:{cardinal:F[2],ordinal:F[34]},br:{cardinal:F[6]},brx:{cardinal:F[0]},bs:{cardinal:F[7]},ca:{cardinal:F[4],ordinal:F[37]},ce:{cardinal:F[0]},cgg:{cardinal:F[0]},chr:{cardinal:F[0]},ckb:{cardinal:F[0]},cs:{cardinal:F[8]},cy:{cardinal:F[9],ordinal:F[38]},da:{cardinal:F[10]},de:{cardinal:F[4]},dsb:{cardinal:F[11]},dv:{cardinal:F[0]},ee:{cardinal:F[0]},el:{cardinal:F[0]},en:{cardinal:F[4],ordinal:F[39]},eo:{cardinal:F[0]},es:{cardinal:F[0]},et:{cardinal:F[4]},eu:{cardinal:F[0]},fa:{cardinal:F[2]},ff:{cardinal:F[12]},fi:{cardinal:F[4]},fil:{cardinal:F[13],ordinal:F[0]},fo:{cardinal:F[0]},fr:{cardinal:F[12],ordinal:F[0]},fur:{cardinal:F[0]},fy:{cardinal:F[4]},ga:{cardinal:F[14],ordinal:F[0]},gd:{cardinal:F[15],ordinal:F[40]},gl:{cardinal:F[4]},gsw:{cardinal:F[0]},gu:{cardinal:F[2],ordinal:F[41]},guw:{cardinal:F[1]},gv:{cardinal:F[16]},ha:{cardinal:F[0]},haw:{cardinal:F[0]},he:{cardinal:F[17]},hi:{cardinal:F[2],ordinal:F[41]},hr:{cardinal:F[7]},hsb:{cardinal:F[11]},hu:{cardinal:F[0],ordinal:F[42]},hy:{cardinal:F[12],ordinal:F[0]},ia:{cardinal:F[4]},io:{cardinal:F[4]},is:{cardinal:F[18]},it:{cardinal:F[4],ordinal:F[43]},iu:{cardinal:F[19]},iw:{cardinal:F[17]},jgo:{cardinal:F[0]},ji:{cardinal:F[4]},jmc:{cardinal:F[0]},ka:{cardinal:F[0],ordinal:F[44]},kab:{cardinal:F[12]},kaj:{cardinal:F[0]},kcg:{cardinal:F[0]},kk:{cardinal:F[0],ordinal:F[45]},kkj:{cardinal:F[0]},kl:{cardinal:F[0]},kn:{cardinal:F[2]},ks:{cardinal:F[0]},ksb:{cardinal:F[0]},ksh:{cardinal:F[20]},ku:{cardinal:F[0]},kw:{cardinal:F[19]},ky:{cardinal:F[0]},lag:{cardinal:F[21]},lb:{cardinal:F[0]},lg:{cardinal:F[0]},ln:{cardinal:F[1]},lt:{cardinal:F[22]},lv:{cardinal:F[23]},mas:{cardinal:F[0]},mg:{cardinal:F[1]},mgo:{cardinal:F[0]},mk:{cardinal:F[24],ordinal:F[46]},ml:{cardinal:F[0]},mn:{cardinal:F[0]},mo:{cardinal:F[25],ordinal:F[0]},mr:{cardinal:F[2],ordinal:F[47]},mt:{cardinal:F[26]},nah:{cardinal:F[0]},naq:{cardinal:F[19]},nb:{cardinal:F[0]},nd:{cardinal:F[0]},ne:{cardinal:F[0],ordinal:F[48]},nl:{cardinal:F[4]},nn:{cardinal:F[0]},nnh:{cardinal:F[0]},no:{cardinal:F[0]},nr:{cardinal:F[0]},nso:{cardinal:F[1]},ny:{cardinal:F[0]},nyn:{cardinal:F[0]},om:{cardinal:F[0]},or:{cardinal:F[0],ordinal:F[49]},os:{cardinal:F[0]},pa:{cardinal:F[1]},pap:{cardinal:F[0]},pl:{cardinal:F[27]},prg:{cardinal:F[23]},ps:{cardinal:F[0]},pt:{cardinal:F[28]},"pt-PT":{cardinal:F[4]},rm:{cardinal:F[0]},ro:{cardinal:F[25],ordinal:F[0]},rof:{cardinal:F[0]},ru:{cardinal:F[29]},rwk:{cardinal:F[0]},saq:{cardinal:F[0]},sc:{cardinal:F[4],ordinal:F[43]},scn:{cardinal:F[4],ordinal:F[43]},sd:{cardinal:F[0]},sdh:{cardinal:F[0]},se:{cardinal:F[19]},seh:{cardinal:F[0]},sh:{cardinal:F[7]},shi:{cardinal:F[30]},si:{cardinal:F[31]},sk:{cardinal:F[8]},sl:{cardinal:F[32]},sma:{cardinal:F[19]},smi:{cardinal:F[19]},smj:{cardinal:F[19]},smn:{cardinal:F[19]},sms:{cardinal:F[19]},sn:{cardinal:F[0]},so:{cardinal:F[0]},sq:{cardinal:F[0],ordinal:F[50]},sr:{cardinal:F[7]},ss:{cardinal:F[0]},ssy:{cardinal:F[0]},st:{cardinal:F[0]},sv:{cardinal:F[4],ordinal:F[51]},sw:{cardinal:F[4]},syr:{cardinal:F[0]},ta:{cardinal:F[0]},te:{cardinal:F[0]},teo:{cardinal:F[0]},ti:{cardinal:F[1]},tig:{cardinal:F[0]},tk:{cardinal:F[0],ordinal:F[52]},tl:{cardinal:F[13],ordinal:F[0]},tn:{cardinal:F[0]},tr:{cardinal:F[0]},ts:{cardinal:F[0]},tzm:{cardinal:F[33]},ug:{cardinal:F[0]},uk:{cardinal:F[29],ordinal:F[53]},ur:{cardinal:F[4]},uz:{cardinal:F[0]},ve:{cardinal:F[0]},vo:{cardinal:F[0]},vun:{cardinal:F[0]},wa:{cardinal:F[1]},wae:{cardinal:F[0]},xh:{cardinal:F[0]},xog:{cardinal:F[0]},yi:{cardinal:F[4]},zu:{cardinal:F[2]},lo:{ordinal:F[0]},ms:{ordinal:F[0]},vi:{ordinal:F[0]}},La=Ea(function(e,t){t=e.exports=function(e,t,r){return n(e,null,t||`en`,r||{},!0)},t.toParts=function(e,t,r){return n(e,null,t||`en`,r||{},!1)};function n(e,t,n,i,a){var o=e.map(function(e){return r(e,t,n,i,a)});return a?o.length===1?o[0]:function(e){for(var t=``,n=0;n<o.length;++n)t+=o[n](e);return t}:function(e){return o.reduce(function(t,n){return t.concat(n(e))},[])}}function r(e,t,r,a,o){if(typeof e==`string`){var s=e;return function(){return s}}var c=e[0],l=e[1];if(t&&e[0]===`#`){c=t[0];var u=t[2],f=(a.number||d.number)([c,`number`],r);return function(e){return f(i(c,e)-u,e)}}var p;l===`plural`||l===`selectordinal`?(p={},Object.keys(e[3]).forEach(function(t){p[t]=n(e[3][t],e,r,a,o)}),e=[e[0],e[1],e[2],p]):e[2]&&typeof e[2]==`object`&&(p={},Object.keys(e[2]).forEach(function(t){p[t]=n(e[2][t],e,r,a,o)}),e=[e[0],e[1],p]);var m=l&&(a[l]||d[l]);if(m){var h=m(e,r);return function(e){return h(i(c,e),e)}}return o?function(e){return String(i(c,e))}:function(e){return i(c,e)}}function i(e,t){if(t&&e in t)return t[e];for(var n=e.split(`.`),r=t,i=0,a=n.length;r&&i<a;++i)r=r[n[i]];return r}function a(e,t){var n=e[2],r=ja.number[n]||ja.parseNumberPattern(n)||ja.number.default;return new Intl.NumberFormat(t,r).format}function o(e,t){var n=e[2],r=ja.duration[n]||ja.duration.default,i=new Intl.NumberFormat(t,r.seconds).format,a=new Intl.NumberFormat(t,r.minutes).format,o=new Intl.NumberFormat(t,r.hours).format,s=/^fi$|^fi-|^da/.test(String(t))?`.`:`:`;return function(e,t){if(e=+e,!isFinite(e))return i(e);var n=~~(e/60/60),r=~~(e/60%60),c=(n?o(Math.abs(n))+s:``)+a(Math.abs(r))+s+i(Math.abs(e%60));return e<0?o(-1).replace(o(1),c):c}}function s(e,t){var n=e[1],r=e[2],i=ja[n][r]||ja.parseDatePattern(r)||ja[n].default;return new Intl.DateTimeFormat(t,i).format}function c(e,t){var n=e[1]===`selectordinal`?`ordinal`:`cardinal`,r=e[2],i=e[3],a;if(Intl.PluralRules&&Intl.PluralRules.supportedLocalesOf(t).length>0)a=new Intl.PluralRules(t,{type:n});else{var o=Ma(t,Ia);a={select:o&&Ia[o][n]||l}}return function(e,t){return(i[`=`+ +e]||i[a.select(e-r)]||i.other)(t)}}function l(){return`other`}function u(e,t){var n=e[2];return function(e,t){return(n[e]||n.other)(t)}}var d={number:a,ordinal:a,spellout:a,duration:o,date:s,time:s,plural:c,selectordinal:c,select:u};t.types=d});La.toParts,La.types;var Ra=Ea(function(e,t){var n=`{`,r=`}`,i=`,`,a=`#`,o=`<`,s=`>`,c=`</`,l=`/>`,u=`'`,d=`offset:`,f=[`number`,`date`,`time`,`ordinal`,`duration`,`spellout`],p=[`plural`,`select`,`selectordinal`];t=e.exports=function(e,t){return m({pattern:String(e),index:0,tagsType:t&&t.tagsType||null,tokens:t&&t.tokens||null},``)};function m(e,t){var n=e.pattern,i=n.length,a=[],o=e.index,s=h(e,t);for(s&&a.push(s),s&&e.tokens&&e.tokens.push([`text`,n.slice(o,e.index)]);e.index<i;){if(n[e.index]===r){if(!t)throw E(e);break}if(t&&e.tagsType&&n.slice(e.index,e.index+c.length)===c)break;a.push(v(e)),o=e.index,s=h(e,t),s&&a.push(s),s&&e.tokens&&e.tokens.push([`text`,n.slice(o,e.index)])}return a}function h(e,t){for(var i=e.pattern,s=i.length,c=t===`plural`||t===`selectordinal`,l=!!e.tagsType,d=t===`{style}`,f=``;e.index<s;){var p=i[e.index];if(p===n||p===r||c&&p===a||l&&p===o||d&&g(p.charCodeAt(0)))break;if(p===u)if(p=i[++e.index],p===u)f+=p,++e.index;else if(p===n||p===r||c&&p===a||l&&p===o||d)for(f+=p;++e.index<s;)if(p=i[e.index],p===u&&i[e.index+1]===u)f+=u,++e.index;else if(p===u){++e.index;break}else f+=p;else f+=u;else f+=p,++e.index}return f}function g(e){return e>=9&&e<=13||e===32||e===133||e===160||e===6158||e>=8192&&e<=8205||e===8232||e===8233||e===8239||e===8287||e===8288||e===12288||e===65279}function _(e){for(var t=e.pattern,n=t.length,r=e.index;e.index<n&&g(t.charCodeAt(e.index));)++e.index;r<e.index&&e.tokens&&e.tokens.push([`space`,e.pattern.slice(r,e.index)])}function v(e){var t=e.pattern;if(t[e.index]===a)return e.tokens&&e.tokens.push([`syntax`,a]),++e.index,[a];var o=y(e);if(o)return o;if(t[e.index]!==n)throw E(e,n);e.tokens&&e.tokens.push([`syntax`,n]),++e.index,_(e);var s=b(e);if(!s)throw E(e,`placeholder id`);e.tokens&&e.tokens.push([`id`,s]),_(e);var c=t[e.index];if(c===r)return e.tokens&&e.tokens.push([`syntax`,r]),++e.index,[s];if(c!==i)throw E(e,i+` or `+r);e.tokens&&e.tokens.push([`syntax`,i]),++e.index,_(e);var l=b(e);if(!l)throw E(e,`placeholder type`);if(e.tokens&&e.tokens.push([`type`,l]),_(e),c=t[e.index],c===r){if(e.tokens&&e.tokens.push([`syntax`,r]),l===`plural`||l===`selectordinal`||l===`select`)throw E(e,l+` sub-messages`);return++e.index,[s,l]}if(c!==i)throw E(e,i+` or `+r);e.tokens&&e.tokens.push([`syntax`,i]),++e.index,_(e);var u;if(l===`plural`||l===`selectordinal`){var d=S(e);_(e),u=[s,l,d,w(e,l)]}else if(l===`select`)u=[s,l,w(e,l)];else if(f.indexOf(l)>=0)u=[s,l,x(e)];else{var p=e.index,m=x(e);_(e),t[e.index]===n&&(e.index=p,m=w(e,l)),u=[s,l,m]}if(_(e),t[e.index]!==r)throw E(e,r);return e.tokens&&e.tokens.push([`syntax`,r]),++e.index,u}function y(e){var t=e.tagsType;if(!(!t||e.pattern[e.index]!==o)){if(e.pattern.slice(e.index,e.index+c.length)===c)throw E(e,null,`closing tag without matching opening tag`);e.tokens&&e.tokens.push([`syntax`,o]),++e.index;var n=b(e,!0);if(!n)throw E(e,`placeholder id`);if(e.tokens&&e.tokens.push([`id`,n]),_(e),e.pattern.slice(e.index,e.index+l.length)===l)return e.tokens&&e.tokens.push([`syntax`,l]),e.index+=l.length,[n,t];if(e.pattern[e.index]!==s)throw E(e,s);e.tokens&&e.tokens.push([`syntax`,s]),++e.index;var r=m(e,t),i=e.index;if(e.pattern.slice(e.index,e.index+c.length)!==c)throw E(e,c+n+s);e.tokens&&e.tokens.push([`syntax`,c]),e.index+=c.length;var a=b(e,!0);if(a&&e.tokens&&e.tokens.push([`id`,a]),n!==a)throw e.index=i,E(e,c+n+s,c+a+s);if(_(e),e.pattern[e.index]!==s)throw E(e,s);return e.tokens&&e.tokens.push([`syntax`,s]),++e.index,[n,t,{children:r}]}}function b(e,t){for(var c=e.pattern,l=c.length,d=``;e.index<l;){var f=c[e.index];if(f===n||f===r||f===i||f===a||f===u||g(f.charCodeAt(0))||t&&(f===o||f===s||f===`/`))break;d+=f,++e.index}return d}function x(e){var t=e.index,n=h(e,`{style}`);if(!n)throw E(e,`placeholder style name`);return e.tokens&&e.tokens.push([`style`,e.pattern.slice(t,e.index)]),n}function S(e){var t=e.pattern,n=t.length,r=0;if(t.slice(e.index,e.index+d.length)===d){e.tokens&&e.tokens.push([`offset`,`offset`],[`syntax`,`:`]),e.index+=d.length,_(e);for(var i=e.index;e.index<n&&C(t.charCodeAt(e.index));)++e.index;if(i===e.index)throw E(e,`offset number`);e.tokens&&e.tokens.push([`number`,t.slice(i,e.index)]),r=+t.slice(i,e.index)}return r}function C(e){return e>=48&&e<=57}function w(e,t){for(var n=e.pattern,i=n.length,a={};e.index<i&&n[e.index]!==r;){var o=b(e);if(!o)throw E(e,`sub-message selector`);e.tokens&&e.tokens.push([`selector`,o]),_(e),a[o]=T(e,t),_(e)}if(!a.other&&p.indexOf(t)>=0)throw E(e,null,null,`"other" sub-message must be specified in `+t);return a}function T(e,t){if(e.pattern[e.index]!==n)throw E(e,n+` to start sub-message`);e.tokens&&e.tokens.push([`syntax`,n]),++e.index;var i=m(e,t);if(e.pattern[e.index]!==r)throw E(e,r+` to end sub-message`);return e.tokens&&e.tokens.push([`syntax`,r]),++e.index,i}function E(e,t,n,r){var i=e.pattern,a=i.slice(0,e.index).split(/\r?\n/),o=e.index,s=a.length,c=a.slice(-1)[0].length;return n||=e.index>=i.length?`end of message pattern`:b(e)||i[e.index],r||=D(t,n),r+=` in `+i.replace(/\r?\n/g,`
`),new ee(r,t,n,o,s,c)}function D(e,t){return e?`Expected `+e+` but found `+t:`Unexpected `+t+` found`}function ee(e,t,n,r,i,a){Error.call(this,e),this.name=`SyntaxError`,this.message=e,this.expected=t,this.found=n,this.offset=r,this.line=i,this.column=a}ee.prototype=Object.create(Error.prototype),t.SyntaxError=ee});Ra.SyntaxError;var za=RegExp(`^(`+Object.keys(Ia).join(`|`)+`)\\b`),Ba=new WeakMap;function Va(e,t,n){if(!(this instanceof Va)||Ba.has(this))throw TypeError(`calling MessageFormat constructor without new is invalid`);var r=Ra(e);Ba.set(this,{ast:r,format:La(r,t,n&&n.types),locale:Va.supportedLocalesOf(t)[0]||`en`,locales:t,options:n})}var Ha=Va;Object.defineProperties(Va.prototype,{format:{configurable:!0,get:function(){var e=Ba.get(this);if(!e)throw TypeError(`MessageFormat.prototype.format called on value that's not an object initialized as a MessageFormat`);return e.format}},formatToParts:{configurable:!0,writable:!0,value:function(e){var t=Ba.get(this);if(!t)throw TypeError(`MessageFormat.prototype.formatToParts called on value that's not an object initialized as a MessageFormat`);return(t.toParts||=La.toParts(t.ast,t.locales,t.options&&t.options.types))(e)}},resolvedOptions:{configurable:!0,writable:!0,value:function(){var e=Ba.get(this);if(!e)throw TypeError(`MessageFormat.prototype.resolvedOptions called on value that's not an object initialized as a MessageFormat`);return{locale:e.locale}}}}),typeof Symbol<`u`&&Object.defineProperty(Va.prototype,Symbol.toStringTag,{value:`Object`}),Object.defineProperties(Va,{supportedLocalesOf:{configurable:!0,writable:!0,value:function(e){return[].concat(Intl.NumberFormat.supportedLocalesOf(e),Intl.DateTimeFormat.supportedLocalesOf(e),Intl.PluralRules?Intl.PluralRules.supportedLocalesOf(e):[],[].concat(e||[]).filter(function(e){return za.test(e)})).filter(function(e,t,n){return n.indexOf(e)===t})}}});function Ua(e){return!!(e&&e.default&&typeof e.default==`object`&&Object.keys(e).length===1)}var Wa=globalThis.document?.documentElement,Ga=class extends EventTarget{formatNumberOptions={returnIfNaN:``,postProcessors:new Map};formatDateOptions={postProcessors:new Map};#e=!1;#t=``;#n=null;__storage={};__namespacePatternsMap=new Map;__namespaceLoadersCache={};__namespaceLoaderPromisesCache={};get locale(){return this.#e?this.#t||``:Wa.lang||``}set locale(e){if(this.#r(e),!this.#e){let t=Wa.lang;this._setHtmlLangAttribute(e),this._onLocaleChanged(e,t);return}let t=this.#t;this.#t=e,this.#n===null&&this._setHtmlLangAttribute(e),this._onLocaleChanged(e,t)}get loadingComplete(){return typeof this.__namespaceLoaderPromisesCache[this.locale]==`object`?Promise.all(Object.values(this.__namespaceLoaderPromisesCache[this.locale])):Promise.resolve()}constructor({allowOverridesForExistingNamespaces:e=!1,autoLoadOnLocaleChange:t=!1,showKeyAsFallback:n=!1,fallbackLocale:r=``}={}){super(),this.__allowOverridesForExistingNamespaces=e,this._autoLoadOnLocaleChange=!!t,this._showKeyAsFallback=n,this._fallbackLocale=r;let i=Wa.getAttribute(`data-localize-lang`);this.#e=!!i,this.#e&&(this.locale=i,this._setupTranslationToolSupport()),Wa.lang||=this.locale||`en-GB`,this._setupHtmlLangAttributeObserver()}addData(e,t,n){if(!this.__allowOverridesForExistingNamespaces&&this._isNamespaceInCache(e,t))throw Error(`Namespace "${t}" has been already added for the locale "${e}".`);this.__storage[e]=this.__storage[e]||{},this.__allowOverridesForExistingNamespaces?this.__storage[e][t]={...this.__storage[e][t],...n}:this.__storage[e][t]=n}setupNamespaceLoader(e,t){this.__namespacePatternsMap.set(e,t)}loadNamespaces(e,{locale:t}={}){return Promise.all(e.map(e=>this.loadNamespace(e,{locale:t})))}loadNamespace(e,{locale:t=this.locale}={locale:this.locale}){let n=typeof e==`object`,r=n?Object.keys(e)[0]:e;return this._isNamespaceInCache(t,r)?Promise.resolve():this._getCachedNamespaceLoaderPromise(t,r)||this._loadNamespaceData(t,e,n,r)}msg(e,t,n={}){let r=n.locale?n.locale:this.locale,i=this._getMessageForKeys(e,r);return i?new Ha(i,r).format(t):``}teardown(){this._teardownHtmlLangAttributeObserver()}reset(){this.__storage={},this.__namespacePatternsMap=new Map,this.__namespaceLoadersCache={},this.__namespaceLoaderPromisesCache={}}setDatePostProcessorForLocale({locale:e,postProcessor:t}){this.formatDateOptions?.postProcessors.set(e,t)}setNumberPostProcessorForLocale({locale:e,postProcessor:t}){this.formatNumberOptions?.postProcessors.set(e,t)}_setupTranslationToolSupport(){this.#n=Wa.lang||null}_setHtmlLangAttribute(e){this._teardownHtmlLangAttributeObserver(),Wa.lang=e,this._setupHtmlLangAttributeObserver()}_setupHtmlLangAttributeObserver(){this._htmlLangAttributeObserver||=new MutationObserver(e=>{e.forEach(e=>{this.#e?Wa.lang===`auto`?(this.#n=null,this._setHtmlLangAttribute(this.locale)):this.#n=document.documentElement.lang:this._onLocaleChanged(document.documentElement.lang,e.oldValue||``)})}),this._htmlLangAttributeObserver.observe(document.documentElement,{attributes:!0,attributeFilter:[`lang`],attributeOldValue:!0})}_teardownHtmlLangAttributeObserver(){this._htmlLangAttributeObserver&&this._htmlLangAttributeObserver.disconnect()}_isNamespaceInCache(e,t){return!!(this.__storage[e]&&this.__storage[e][t])}_getCachedNamespaceLoaderPromise(e,t){return this.__namespaceLoaderPromisesCache[e]?this.__namespaceLoaderPromisesCache[e][t]:null}_loadNamespaceData(e,t,n,r){let i=this._getNamespaceLoader(t,n,r),a=this._getNamespaceLoaderPromise(i,e,r);return this._cacheNamespaceLoaderPromise(e,r,a),a.then(t=>{if(this.__namespaceLoaderPromisesCache[e]&&this.__namespaceLoaderPromisesCache[e][r]===a){let n=Ua(t)?t.default:t;this.addData(e,r,n)}})}_getNamespaceLoader(e,t,n){let r=this.__namespaceLoadersCache[n];if(r||(t?(r=e[n],this.__namespaceLoadersCache[n]=r):(r=this._lookupNamespaceLoader(n),this.__namespaceLoadersCache[n]=r)),!r)throw Error(`Namespace "${n}" was not properly setup.`);return this.__namespaceLoadersCache[n]=r,r}_getNamespaceLoaderPromise(e,t,n,r=this._fallbackLocale){return e(t,n).catch(()=>{let i=this._getLangFromLocale(t);return e(i,n).catch(()=>{if(r)return this._getNamespaceLoaderPromise(e,r,n,``).catch(()=>{let e=this._getLangFromLocale(r);throw Error(`Data for namespace "${n}" and current locale "${t}" or fallback locale "${r}" could not be loaded. Make sure you have data either for locale "${t}" (and/or generic language "${i}") or for fallback "${r}" (and/or "${e}").`)});throw Error(`Data for namespace "${n}" and locale "${t}" could not be loaded. Make sure you have data for locale "${t}" (and/or generic language "${i}").`)})})}_cacheNamespaceLoaderPromise(e,t,n){this.__namespaceLoaderPromisesCache[e]||(this.__namespaceLoaderPromisesCache[e]={}),this.__namespaceLoaderPromisesCache[e][t]=n}_lookupNamespaceLoader(e){for(let[t,n]of this.__namespacePatternsMap){let r=typeof t==`string`&&t===e,i=typeof t==`object`&&t.constructor.name===`RegExp`&&t.test(e);if(r||i)return n}return null}_getLangFromLocale(e){return e.substring(0,2)}_onLocaleChanged(e,t){this.dispatchEvent(new CustomEvent(`__localeChanging`)),e!==t&&(this._autoLoadOnLocaleChange?(this._loadAllMissing(e,t),this.loadingComplete.then(()=>{this.dispatchEvent(new CustomEvent(`localeChanged`,{detail:{newLocale:e,oldLocale:t}}))})):this.dispatchEvent(new CustomEvent(`localeChanged`,{detail:{newLocale:e,oldLocale:t}})))}_loadAllMissing(e,t){let n=this.__storage[t]||{},r=this.__storage[e]||{};Object.keys(n).forEach(t=>{r[t]||this.loadNamespace(t,{locale:e})})}_getMessageForKeys(e,t){if(typeof e==`string`)return this._getMessageForKey(e,t);let n=Array.from(e).reverse(),r,i;for(;n.length;)if(r=n.pop(),i=this._getMessageForKey(r,t),i)return i}_getMessageForKey(e,t){if(!e||e.indexOf(`:`)===-1)throw Error(`Namespace is missing in the key "${e}". The format for keys is "namespace:name".`);let[n,r]=e.split(`:`),i=this.__storage[t],a=i?i[n]:{},o=r.split(`.`).reduce((e,t)=>typeof e==`object`?e[t]:e,a);return String(o||(this._showKeyAsFallback?e:``))}#r(e){if(!e.includes(`-`))throw Error(`
      Locale was set to ${e}.
      Language only locales are not allowed, please use the full language locale e.g. 'en-GB' instead of 'en'.
      See https://github.com/ing-bank/lion/issues/187 for more information.
    `)}get _supportExternalTranslationTools(){return this.#e}set _supportExternalTranslationTools(e){this.#e=e}get _langAttrSetByTranslationTool(){return this.#t}set _langAttrSetByTranslationTool(e){this.#t=e}},Ka=Symbol.for(`lion::SingletonManagerClassStorage`),qa=globalThis||window,Ja=new class{constructor(){this._map=qa[Ka]?qa[Ka]:qa[Ka]=new Map}set(e,t){this.has(e)||this._map.set(e,t)}get(e){return this._map.get(e)}has(e){return this._map.has(e)}};function Ya(){if(Ja.has(`@lion/ui::localize::0.x`))return Ja.get(`@lion/ui::localize::0.x`);let e=new Ga({autoLoadOnLocaleChange:!0,fallbackLocale:`en-GB`});return Ja.set(`@lion/ui::localize::0.x`,e),e}var Xa=(e,t)=>{let n=e._$AN;if(n===void 0)return!1;for(let e of n)e._$AO?.(t,!1),Xa(e,t);return!0},Za=e=>{let t,n;do{if((t=e._$AM)===void 0)break;n=t._$AN,n.delete(e),e=t}while(n?.size===0)},Qa=e=>{for(let t;t=e._$AM;e=t){let n=t._$AN;if(n===void 0)t._$AN=n=new Set;else if(n.has(e))break;n.add(e),to(t)}};function $a(e){this._$AN===void 0?this._$AM=e:(Za(this),this._$AM=e,Qa(this))}function eo(e,t=!1,n=0){let r=this._$AH,i=this._$AN;if(i!==void 0&&i.size!==0)if(t)if(Array.isArray(r))for(let e=n;e<r.length;e++)Xa(r[e],!1),Za(r[e]);else r!=null&&(Xa(r,!1),Za(r));else Xa(this,e)}var to=e=>{e.type==ne.CHILD&&(e._$AP??=eo,e._$AQ??=$a)},no=class extends te{constructor(){super(...arguments),this._$AN=void 0}_$AT(e,t,n){super._$AT(e,t,n),Qa(this),this.isConnected=e._$AU}_$AO(e,t=!0){e!==this.isConnected&&(this.isConnected=e,e?this.reconnected?.():this.disconnected?.()),t&&(Xa(this,e),Za(this))}setValue(e){if(Pr(this._$Ct))this._$Ct._$AI(e,this);else{let t=[...this._$Ct._$AH];t[this._$Ci]=e,this._$Ct._$AI(t,this,0)}}disconnected(){}reconnected(){}},ro=class{constructor(e){this.G=e}disconnect(){this.G=void 0}reconnect(e){this.G=e}deref(){return this.G}},io=class{constructor(){this.Y=void 0,this.Z=void 0}get(){return this.Y}pause(){this.Y??=new Promise((e=>this.Z=e))}resume(){this.Z?.(),this.Y=this.Z=void 0}},ao=e=>!Mr(e)&&typeof e.then==`function`,oo=1073741823,so=E(class extends no{constructor(){super(...arguments),this._$Cwt=oo,this._$Cbt=[],this._$CK=new ro(this),this._$CX=new io}render(...e){return e.find((e=>!ao(e)))??g}update(e,t){let n=this._$Cbt,r=n.length;this._$Cbt=t;let i=this._$CK,a=this._$CX;this.isConnected||this.disconnected();for(let e=0;e<t.length&&!(e>this._$Cwt);e++){let o=t[e];if(!ao(o))return this._$Cwt=e,o;e<r&&o===n[e]||(this._$Cwt=oo,r=0,Promise.resolve(o).then((async e=>{for(;a.get();)await a.get();let t=i.deref();if(t!==void 0){let n=t._$Cbt.indexOf(o);n>-1&&n<t._$Cwt&&(t._$Cwt=n,t.setValue(e))}})))}return g}disconnected(){this._$CK.disconnect(),this._$CX.pause()}reconnected(){this._$CK.reconnect(this),this._$CX.resume()}}),co=Or(e=>class extends e{static get localizeNamespaces(){return[]}static get waitForLocalizeNamespaces(){return!0}constructor(){super(),this._localizeManager=Ya(),this.__boundLocalizeOnLocaleChanged=(...e)=>{let t=Array.from(e)[0];this.__localizeOnLocaleChanged(t)},this.__boundLocalizeOnLocaleChanging=()=>{this.__localizeOnLocaleChanging()},this.__localizeStartLoadingNamespaces(),this.localizeNamespacesLoaded&&this.localizeNamespacesLoaded.then(()=>{this.__localizeMessageSync=!0})}async scheduleUpdate(){Object.getPrototypeOf(this).constructor.waitForLocalizeNamespaces&&await this.localizeNamespacesLoaded,super.scheduleUpdate()}connectedCallback(){super.connectedCallback(),this.localizeNamespacesLoaded&&this.localizeNamespacesLoaded.then(()=>this.onLocaleReady()),this._localizeManager.addEventListener(`__localeChanging`,this.__boundLocalizeOnLocaleChanging),this._localizeManager.addEventListener(`localeChanged`,this.__boundLocalizeOnLocaleChanged)}disconnectedCallback(){super.disconnectedCallback(),this._localizeManager.removeEventListener(`__localeChanging`,this.__boundLocalizeOnLocaleChanging),this._localizeManager.removeEventListener(`localeChanged`,this.__boundLocalizeOnLocaleChanged)}msgLit(e,t,n){return this.__localizeMessageSync?this._localizeManager.msg(e,t,n):this.localizeNamespacesLoaded?so(this.localizeNamespacesLoaded.then(()=>this._localizeManager.msg(e,t,n)),y):``}__getUniqueNamespaces(){let e=[],t=new Set;return Object.getPrototypeOf(this).constructor.localizeNamespaces.forEach(t.add.bind(t)),t.forEach(t=>{e.push(t)}),e}__localizeStartLoadingNamespaces(){this.localizeNamespacesLoaded=this._localizeManager.loadNamespaces(this.__getUniqueNamespaces())}__localizeOnLocaleChanging(){this.__localizeStartLoadingNamespaces()}__localizeOnLocaleChanged(e){this.onLocaleChanged(e.detail.newLocale,e.detail.oldLocale)}onLocaleReady(){this.onLocaleUpdated()}onLocaleChanged(e,t){this.onLocaleUpdated(),this.requestUpdate()}onLocaleUpdated(){}}),lo=`3.0.0`,uo=window.scopedElementsVersions||(window.scopedElementsVersions=[]);uo.includes(lo)||uo.push(lo);var fo=Or(e=>class extends e{static scopedElements;static get scopedElementsVersion(){return lo}static __registry;get registry(){return this.constructor.__registry}set registry(e){this.constructor.__registry=e}attachShadow(e){let{scopedElements:t}=this.constructor;if(!this.registry||this.registry===this.constructor.__registry&&!Object.prototype.hasOwnProperty.call(this.constructor,`__registry`)){this.registry=new CustomElementRegistry;for(let[e,n]of Object.entries(t??{}))this.registry.define(e,n)}return super.attachShadow({...e,customElements:this.registry,registry:this.registry})}}),po=Or(e=>class extends fo(e){createRenderRoot(){let{shadowRootOptions:e,elementStyles:t}=this.constructor,n=this.attachShadow(e);return this.renderOptions.creationScope=n,m(n,t),this.renderOptions.renderBefore??=n.firstChild,n}});function mo(){return!!(globalThis.ShadowRoot?.prototype.createElement&&globalThis.ShadowRoot?.prototype.importNode)}var ho=Or(e=>class extends po(e){constructor(){super()}createScopedElement(e){return(mo()?this.shadowRoot:document).createElement(e)}defineScopedElement(e,t){let n=this.registry.get(e),r=n&&n!==t;return!mo()&&r&&console.error([`You are trying to re-register the "${e}" custom element with a different class via ScopedElementsMixin.`,`This is only possible with a CustomElementRegistry.`,`Your browser does not support this feature so you will need to load a polyfill for it.`,`Load "@webcomponents/scoped-custom-element-registry" before you register ANY web component to the global customElements registry.`,`e.g. add "<script src="/node_modules/@webcomponents/scoped-custom-element-registry/scoped-custom-element-registry.min.js"><\/script>" as your first script tag.`,`For more details you can visit https://open-wc.org/docs/development/scoped-elements/`].join(`
`)),n?this.registry.get(e):this.registry.define(e,t)}attachShadow(e){let{scopedElements:t}=this.constructor;if(!this.registry||this.registry===this.constructor.__registry&&!Object.prototype.hasOwnProperty.call(this.constructor,`__registry`)){this.registry=mo()?new CustomElementRegistry:customElements;for(let[e,n]of Object.entries(t??{}))this.defineScopedElement(e,n)}return Element.prototype.attachShadow.call(this,{...e,customElements:this.registry,registry:this.registry})}createRenderRoot(){let{shadowRootOptions:e,elementStyles:t}=this.constructor,n=this.attachShadow(e);return mo()&&(this.renderOptions.creationScope=n),n instanceof ShadowRoot&&(m(n,t),this.renderOptions.renderBefore=this.renderOptions.renderBefore||n.firstChild),n}}),go=class{constructor(){this.__running=!1,this.__queue=[]}add(e){this.__queue.push(e),this.__running||(this.complete=new Promise(e=>{this.__callComplete=e}),this.__run())}async __run(){this.__running=!0,await this.__queue[0](),this.__queue.shift(),this.__queue.length>0?this.__run():(this.__running=!1,this.__callComplete&&this.__callComplete())}};function _o(e){return e.charAt(0).toUpperCase()+e.slice(1)}var vo=Or(e=>class extends e{constructor(){super(),this.__SyncUpdatableNamespace={}}firstUpdated(e){super.firstUpdated(e),this.__syncUpdatableInitialize()}connectedCallback(){super.connectedCallback(),this.__SyncUpdatableNamespace.connected=!0}disconnectedCallback(){super.disconnectedCallback(),this.__SyncUpdatableNamespace.connected=!1}static enabledWarnings=super.enabledWarnings?.filter(e=>e!==`change-in-update`)||[];static __syncUpdatableHasChanged(e,t,n){let r=this.elementProperties;return r.get(e)&&r.get(e).hasChanged?r.get(e).hasChanged(t,n):t!==n}__syncUpdatableInitialize(){let e=this.__SyncUpdatableNamespace,t=this.constructor;e.initialized=!0,e.queue&&Array.from(e.queue).forEach(e=>{t.__syncUpdatableHasChanged(e,this[e],void 0)&&this.updateSync(e,void 0)})}requestUpdate(e,t,n){if(super.requestUpdate(e,t,n),e===void 0)return;this.__SyncUpdatableNamespace=this.__SyncUpdatableNamespace||{};let r=this.__SyncUpdatableNamespace,i=this.constructor;r.initialized?i.__syncUpdatableHasChanged(e,this[e],t)&&this.updateSync(e,t):(r.queue=r.queue||new Set,r.queue.add(e))}updateSync(e,t){}}),yo=e=>{switch(e){case`bg-BG`:return O(()=>import(`./bg-BG.js`),__vite__mapDeps([0,1]),import.meta.url);case`bg`:return O(()=>import(`./bg2.js`),[],import.meta.url);case`cs-CZ`:return O(()=>import(`./cs-CZ.js`),__vite__mapDeps([2,3]),import.meta.url);case`cs`:return O(()=>import(`./cs2.js`),[],import.meta.url);case`de-DE`:return O(()=>import(`./de-DE.js`),__vite__mapDeps([4,5]),import.meta.url);case`de`:return O(()=>import(`./de2.js`),[],import.meta.url);case`en-AU`:return O(()=>import(`./en-AU.js`),__vite__mapDeps([6,7]),import.meta.url);case`en-GB`:return O(()=>import(`./en-GB.js`),__vite__mapDeps([8,7]),import.meta.url);case`en-US`:return O(()=>import(`./en-US.js`),__vite__mapDeps([9,7]),import.meta.url);case`en-PH`:case`en`:return O(()=>import(`./en2.js`),[],import.meta.url);case`es-ES`:return O(()=>import(`./es-ES.js`),__vite__mapDeps([10,11]),import.meta.url);case`es`:return O(()=>import(`./es2.js`),[],import.meta.url);case`fr-FR`:return O(()=>import(`./fr-FR.js`),__vite__mapDeps([12,13]),import.meta.url);case`fr-BE`:return O(()=>import(`./fr-BE.js`),__vite__mapDeps([14,13]),import.meta.url);case`fr`:return O(()=>import(`./fr2.js`),[],import.meta.url);case`hu-HU`:return O(()=>import(`./hu-HU.js`),__vite__mapDeps([15,16]),import.meta.url);case`hu`:return O(()=>import(`./hu2.js`),[],import.meta.url);case`it-IT`:return O(()=>import(`./it-IT.js`),__vite__mapDeps([17,18]),import.meta.url);case`it`:return O(()=>import(`./it2.js`),[],import.meta.url);case`nl-BE`:return O(()=>import(`./nl-BE.js`),__vite__mapDeps([19,20]),import.meta.url);case`nl-NL`:return O(()=>import(`./nl-NL.js`),__vite__mapDeps([21,20]),import.meta.url);case`nl`:return O(()=>import(`./nl2.js`),[],import.meta.url);case`pl-PL`:return O(()=>import(`./pl-PL.js`),__vite__mapDeps([22,23]),import.meta.url);case`pl`:return O(()=>import(`./pl2.js`),[],import.meta.url);case`ro-RO`:return O(()=>import(`./ro-RO.js`),__vite__mapDeps([24,25]),import.meta.url);case`ro`:return O(()=>import(`./ro2.js`),[],import.meta.url);case`ru-RU`:return O(()=>import(`./ru-RU.js`),__vite__mapDeps([26,27]),import.meta.url);case`ru`:return O(()=>import(`./ru2.js`),[],import.meta.url);case`sk-SK`:return O(()=>import(`./sk-SK.js`),__vite__mapDeps([28,29]),import.meta.url);case`sk`:return O(()=>import(`./sk2.js`),[],import.meta.url);case`tr-TR`:return O(()=>import(`./tr-TR.js`),__vite__mapDeps([30,31]),import.meta.url);case`tr`:return O(()=>import(`./tr.js`),[],import.meta.url);case`uk-UA`:return O(()=>import(`./uk-UA.js`),__vite__mapDeps([32,33]),import.meta.url);case`uk`:return O(()=>import(`./uk2.js`),[],import.meta.url);case`zh-CN`:case`zh`:return O(()=>import(`./zh2.js`),[],import.meta.url);default:return O(()=>import(`./en2.js`),[],import.meta.url)}},bo=e=>`${e[0].toUpperCase()}${e.slice(1)}`,xo=class extends co(b){static get properties(){return{feedbackData:{attribute:!1}}}static localizeNamespaces=[{"lion-form-core":yo},...super.localizeNamespaces];static get styles(){return[h`
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
      `]}constructor(){super(),this.feedbackData=void 0}_messageTemplate({message:e}){return e}updated(e){super.updated(e),this.feedbackData&&this.feedbackData[0]?(this.setAttribute(`type`,this.feedbackData[0].type),this.currentType=this.feedbackData[0].type):this.currentType!==`success`&&this.removeAttribute(`type`)}render(){return p`
      ${this.feedbackData&&this.feedbackData.map(({message:e,type:t,validator:n})=>p`
          <div class="validation-feedback__type">
            ${e&&t?this._localizeManager.msg(`lion-form-core:validation${bo(t)}`):y}
          </div>
          ${this._messageTemplate({message:e,type:t,validator:n})}
        `)}
    `}},So=class{constructor(e){this.type=`unparseable`,this.viewValue=e}toString(){return JSON.stringify({type:this.type,viewValue:this.viewValue})}},Co=[Node.DOCUMENT_POSITION_PRECEDING,Node.DOCUMENT_POSITION_CONTAINS,Node.DOCUMENT_POSITION_CONTAINS|Node.DOCUMENT_POSITION_PRECEDING];function wo(e,{reverse:t}={}){let n=(e,t)=>{let n=e.compareDocumentPosition(t);return Co.includes(n)?1:-1},r=e.filter(e=>e);return r.sort(n),t&&r.reverse(),r}var To=Or(e=>class extends e{constructor(){super(),this.name=``,this._parentFormGroup=void 0,this.allowCrossRootRegistration=!1}get name(){return this.__name||``}set name(e){let t=this.name;this.__name=e.toString(),this.requestUpdate(`name`,t)}static get properties(){return{name:{type:String,reflect:!0},allowCrossRootRegistration:{type:Boolean,attribute:`allow-cross-root-registration`}}}connectedCallback(){super.connectedCallback(),this.dispatchEvent(new CustomEvent(`form-element-register`,{detail:{element:this},bubbles:!0,composed:!!this.allowCrossRootRegistration}))}disconnectedCallback(){super.disconnectedCallback(),this.__unregisterFormElement()}__unregisterFormElement(){this._parentFormGroup&&this._parentFormGroup.removeFormElement(this)}}),Eo=Or(e=>class extends To(kr(Ur(e))){static get properties(){return{readOnly:{type:Boolean,attribute:`readonly`,reflect:!0},label:String,labelSrOnly:{type:Boolean,attribute:`label-sr-only`,reflect:!0},helpText:{type:String,attribute:`help-text`},modelValue:{attribute:!1},_ariaLabelledNodes:{attribute:!1},_ariaDescribedNodes:{attribute:!1},_repropagationRole:{attribute:!1},_isRepropagationEndpoint:{attribute:!1}}}get label(){return this.__label??(this._labelNode?.textContent||``)}set label(e){let t=this.label;this.__label=e,this.requestUpdate(`label`,t)}get helpText(){return this.__helpText??(this._helpTextNode?.textContent||``)}set helpText(e){let t=this.helpText;this.__helpText=e,this.requestUpdate(`helpText`,t)}get fieldName(){return this.__fieldName||this.label||this.name||``}set fieldName(e){this.__fieldName=e}get slots(){return{...super.slots,label:()=>{let e=document.createElement(`label`);return e.textContent=this.label,e},"help-text":()=>{let e=document.createElement(`div`);return e.textContent=this.helpText,e}}}get _inputNode(){return this.__getDirectSlotChild(`input`)}get _labelNode(){return this.__getDirectSlotChild(`label`)}get _helpTextNode(){return this.__getDirectSlotChild(`help-text`)}get _feedbackNode(){return this.__getDirectSlotChild(`feedback`)}static enabledWarnings=super.enabledWarnings?.filter(e=>e!==`change-in-update`)||[];constructor(){super(),this.readOnly=!1,this.labelSrOnly=!1,this._inputId=Kr(this.localName),this._ariaLabelledNodes=[],this._ariaDescribedNodes=[],this._repropagationRole=`child`,this._isRepropagationEndpoint=!1,this.addEventListener(`model-value-changed`,this.__repropagateChildrenValues),this._onLabelClick=this._onLabelClick.bind(this)}connectedCallback(){super.connectedCallback(),this._enhanceLightDomClasses(),this._enhanceLightDomA11y(),this._triggerInitialModelValueChangedEvent(),this._labelNode&&this._labelNode.addEventListener(`click`,this._onLabelClick)}disconnectedCallback(){super.disconnectedCallback(),this._labelNode&&this._labelNode.removeEventListener(`click`,this._onLabelClick)}updated(e){super.updated(e),e.has(`disabled`)&&this._inputNode?.setAttribute(`aria-disabled`,`${!!this.disabled}`),e.has(`_ariaLabelledNodes`)&&this.__reflectAriaAttr(`aria-labelledby`,this._ariaLabelledNodes,this.__reorderAriaLabelledNodes),e.has(`_ariaDescribedNodes`)&&this.__reflectAriaAttr(`aria-describedby`,this._ariaDescribedNodes,this.__reorderAriaDescribedNodes),e.has(`label`)&&this.__label!==void 0&&this._labelNode&&(this._labelNode.textContent=this.label),e.has(`helpText`)&&this.__helpText!==void 0&&this._helpTextNode&&(this._helpTextNode.textContent=this.helpText),e.has(`name`)&&this.dispatchEvent(new CustomEvent(`form-element-name-changed`,{detail:{oldName:e.get(`name`),newName:this.name},bubbles:!0}))}_triggerInitialModelValueChangedEvent(){this._dispatchInitialModelValueChangedEvent()}_enhanceLightDomClasses(){this._inputNode&&this._inputNode.classList.add(`form-control`)}_enhanceLightDomA11y(){let{_inputNode:e,_labelNode:t,_helpTextNode:n,_feedbackNode:r}=this;e&&(e.id=e.id||this._inputId),t&&(t.setAttribute(`for`,this._inputId),this.addToAriaLabelledBy(t,{idPrefix:`label`})),n&&this.addToAriaDescribedBy(n,{idPrefix:`help-text`}),r&&(this.addEventListener(`focusin`,()=>{r.setAttribute(`aria-live`,`polite`)}),this.addEventListener(`focusout`,()=>{r.setAttribute(`aria-live`,`assertive`)}),this.addToAriaDescribedBy(r,{idPrefix:`feedback`})),this._enhanceLightDomA11yForAdditionalSlots()}_enhanceLightDomA11yForAdditionalSlots(e=[`prefix`,`suffix`,`before`,`after`]){e.forEach(e=>{let t=this.__getDirectSlotChild(e);t&&(t.hasAttribute(`data-label`)&&this.addToAriaLabelledBy(t,{idPrefix:e}),t.hasAttribute(`data-description`)&&this.addToAriaDescribedBy(t,{idPrefix:e}))})}__reflectAriaAttr(e,t,n){if(this._inputNode){if(n){let e=t.filter(e=>this.contains(e)),n=t.filter(e=>!this.contains(e)),r=[...wo(e.map(e=>e.assignedSlot||e))],i=[];r.forEach(t=>{e.forEach(e=>{t.name===e.slot&&i.push(e)})}),t=[...i,...n]}let r=t.map(e=>e.id).join(` `);this._inputNode.setAttribute(e,r)}}render(){return p`
        <div class="form-field__group-one">${this._groupOneTemplate()}</div>
        <div class="form-field__group-two">${this._groupTwoTemplate()}</div>
      `}_groupOneTemplate(){return p` ${this._labelTemplate()} ${this._helpTextTemplate()} `}_groupTwoTemplate(){return p` ${this._inputGroupTemplate()} ${this._feedbackTemplate()} `}_labelTemplate(){return p`
        <div class="form-field__label">
          <slot name="label"></slot>
        </div>
      `}_helpTextTemplate(){return p`
        <small class="form-field__help-text">
          <slot name="help-text"></slot>
        </small>
      `}_inputGroupTemplate(){return p`
        <div class="input-group">
          ${this._inputGroupBeforeTemplate()}
          <div class="input-group__container">
            ${this._inputGroupPrefixTemplate()} ${this._inputGroupInputTemplate()}
            ${this._inputGroupSuffixTemplate()}
          </div>
          ${this._inputGroupAfterTemplate()}
        </div>
      `}_inputGroupBeforeTemplate(){return p`
        <div class="input-group__before">
          <slot name="before"></slot>
        </div>
      `}_inputGroupPrefixTemplate(){return Array.from(this.children).find(e=>e.slot===`prefix`)?p`
            <div class="input-group__prefix">
              <slot name="prefix"></slot>
            </div>
          `:y}_inputGroupInputTemplate(){return p`
        <div class="input-group__input">
          <slot name="input"></slot>
        </div>
      `}_inputGroupSuffixTemplate(){return Array.from(this.children).find(e=>e.slot===`suffix`)?p`
            <div class="input-group__suffix">
              <slot name="suffix"></slot>
            </div>
          `:y}_inputGroupAfterTemplate(){return p`
        <div class="input-group__after">
          <slot name="after"></slot>
        </div>
      `}_feedbackTemplate(){return p`
        <div class="form-field__feedback">
          <slot name="feedback"></slot>
        </div>
      `}_isEmpty(e=this.modelValue){let t=e;return this.modelValue instanceof So&&(t=this.modelValue.viewValue),typeof t==`object`&&t&&!(t instanceof Date)?!Object.keys(t).length:!t&&!(typeof t==`number`&&(t===0||Number.isNaN(t)))&&!(typeof t==`boolean`&&t===!1)}static get styles(){return[h`
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
        `]}_getAriaDescriptionElements(){return[this._helpTextNode,this._feedbackNode]}addToAriaLabelledBy(e,{idPrefix:t=``,reorder:n=!0}={}){e.id=e.id||`${t}-${this._inputId}`,this._ariaLabelledNodes.includes(e)||(this._ariaLabelledNodes=[...this._ariaLabelledNodes,e],this.__reorderAriaLabelledNodes=!!n)}removeFromAriaLabelledBy(e){this._ariaLabelledNodes.includes(e)&&(this._ariaLabelledNodes.splice(this._ariaLabelledNodes.indexOf(e),1),this._ariaLabelledNodes=[...this._ariaLabelledNodes],this.__reorderAriaLabelledNodes=!1)}addToAriaDescribedBy(e,{idPrefix:t=``,reorder:n=!0}={}){e.id=e.id||`${t}-${this._inputId}`,this._ariaDescribedNodes.includes(e)||(this._ariaDescribedNodes=[...this._ariaDescribedNodes,e],this.__reorderAriaDescribedNodes=!!n)}removeFromAriaDescribedBy(e){this._ariaDescribedNodes.includes(e)&&(this._ariaDescribedNodes.splice(this._ariaDescribedNodes.indexOf(e),1),this._ariaDescribedNodes=[...this._ariaDescribedNodes],this.__reorderAriaLabelledNodes=!1)}__getDirectSlotChild(e){return Array.from(this.children).find(t=>t.slot===e)}_dispatchInitialModelValueChangedEvent(){this._repropagationRole!==`child`&&(this.__repropagateChildrenInitialized=!0,this.dispatchEvent(new CustomEvent(`model-value-changed`,{bubbles:!0,detail:{formPath:[this],initialize:!0,isTriggeredByUser:!1}})))}_onBeforeRepropagateChildrenValues(e){}__repropagateChildrenValues(e){this._onBeforeRepropagateChildrenValues(e);let t=e.detail&&e.detail.element||e.target,n=this._isRepropagationEndpoint||this._repropagationRole===`choice-group`;if(t===this)return;e.stopImmediatePropagation();let r=this._repropagationRole!==`child`&&!this.__repropagateChildrenInitialized,i=e.detail&&e.detail.initialize;if(r||i||!this._repropagationCondition(t))return;let a=[];n||(a=e.detail&&e.detail.formPath||[t]);let o=[...a,this];this.dispatchEvent(new CustomEvent(`model-value-changed`,{bubbles:!0,detail:{formPath:o,isTriggeredByUser:!!e.detail?.isTriggeredByUser}}))}_repropagationCondition(e){return!!e}_onLabelClick(){}}),Do=class extends EventTarget{constructor(e,t){super(),this.__param=e,this.__config=t||{},this.type=t?.type||`error`}static _$isValidator$=!0;static validatorName=``;static async=!1;execute(e,t,n){if(!this.constructor.validatorName)throw Error(`A validator needs to have a name! Please set it via "static get validatorName() { return 'IsCat'; }"`);return!0}set param(e){this.__param=e,this.dispatchEvent(new Event(`param-changed`))}get param(){return this.__param}set config(e){this.__config=e,this.dispatchEvent(new Event(`config-changed`))}get config(){return this.__config}async _getMessage(e){let t=this.constructor,n={name:t.validatorName,type:this.type,params:this.param,config:this.config,...e};if(this.config.getMessage){if(typeof this.config.getMessage==`function`)return this.config.getMessage(n);throw Error(`You must provide a value for getMessage of type 'function', you provided a value of type: ${typeof this.config.getMessage}`)}return t.getMessage(n)}static async getMessage(e){return`Please configure an error message for "${this.name}" by overriding "static async getMessage()"`}onFormControlConnect(e){}onFormControlDisconnect(e){}abortExecution(){}};function Oo(e=[],t=[]){return e.filter(e=>!t.includes(e)).concat(t.filter(t=>!e.includes(t)))}function ko(e){return e instanceof So?e.viewValue:e}var Ao=Or(e=>class extends Eo(vo(kr(Ur(ho(e))))){static get scopedElements(){return{...super.scopedElements,"lion-validation-feedback":xo}}static get properties(){return{validators:{attribute:!1},hasFeedbackFor:{attribute:!1},shouldShowFeedbackFor:{attribute:!1},showsFeedbackFor:{type:Array,attribute:`shows-feedback-for`,reflect:!0,converter:{fromAttribute:e=>e.split(`,`),toAttribute:e=>e.join(`,`)}},validationStates:{attribute:!1},isPending:{type:Boolean,attribute:`is-pending`,reflect:!0},defaultValidators:{attribute:!1},_visibleMessagesAmount:{attribute:!1},__childModelValueChanged:{attribute:!1}}}static get validationTypes(){return[`error`]}get operationMode(){return`enter`}get slots(){return{...super.slots,feedback:()=>{let e=this.createScopedElement(`lion-validation-feedback`);return e.setAttribute(`data-tag-name`,`lion-validation-feedback`),e}}}get _allValidators(){return[...this.validators,...this.defaultValidators]}constructor(){super(),this.hasFeedbackFor=[],this.showsFeedbackFor=[],this.shouldShowFeedbackFor=[],this.validationStates={},this.isPending=!1,this.validators=[],this.defaultValidators=[],this._visibleMessagesAmount=1,this.__syncValidationResult=[],this.__asyncValidationResult=[],this.__validationResult=[],this.__prevValidationResult=[],this.__prevShownValidationResult=[],this.__childModelValueChanged=!1,this._onValidatorUpdated=this._onValidatorUpdated.bind(this),this._updateFeedbackComponent=this._updateFeedbackComponent.bind(this)}connectedCallback(){super.connectedCallback(),Ya().addEventListener(`localeChanged`,this._updateFeedbackComponent)}disconnectedCallback(){super.disconnectedCallback(),Ya().removeEventListener(`localeChanged`,this._updateFeedbackComponent)}firstUpdated(e){super.firstUpdated(e),this.__isValidateInitialized=!0,this.validate(),this._repropagationRole!==`child`&&this.addEventListener(`model-value-changed`,()=>{this.__childModelValueChanged=!0})}updateSync(e,t){if(super.updateSync(e,t),e===`validators`?(this.__setupValidators(),this.validate({clearCurrentResult:!0})):e===`modelValue`&&this.validate({clearCurrentResult:!0}),[`touched`,`dirty`,`prefilled`,`focused`,`submitted`,`hasFeedbackFor`,`filled`].includes(e)&&this._updateShouldShowFeedbackFor(),e===`showsFeedbackFor`){this._inputNode&&this._inputNode.setAttribute(`aria-invalid`,`${this._hasFeedbackVisibleFor(`error`)}`);let e=Oo(this.showsFeedbackFor,t);e.length>0&&this.dispatchEvent(new Event(`showsFeedbackForChanged`,{bubbles:!0})),e.forEach(e=>{this.dispatchEvent(new Event(`showsFeedbackFor${_o(e)}Changed`,{bubbles:!0}))})}e===`shouldShowFeedbackFor`&&Oo(this.shouldShowFeedbackFor,t).length>0&&this.dispatchEvent(new Event(`shouldShowFeedbackForChanged`,{bubbles:!0}))}async validate({clearCurrentResult:e=!1}={}){if(this.validateComplete=new Promise(e=>{this.__validateCompleteResolve=e}),this.disabled){this.__clearValidationResults(),this.__finishValidationPass(),this._updateFeedbackComponent();return}this.__isValidateInitialized&&(this.__prevValidationResult=this.__validationResult,e&&this.__clearValidationResults(),await this.__executeValidators())}#e(e){let t=e;for(;t;){if(t.constructor.validatorName===`Required`)return!0;t=Object.getPrototypeOf(t)}return!1}async __executeValidators(){let e=ko(this.modelValue),t=this.__isEmpty(e);if(this.__syncValidationResult=[],t){let e=!this._isFormOrFieldset,t=this._allValidators.find(e=>e.constructor?.validatorName===`Required`);if(t&&(this.__syncValidationResult=[{validator:t,outcome:!0}]),e){this.__finishValidationPass({syncValidationResult:this.__syncValidationResult});return}}let n=[],r=[],i=[];for(let e of this._allValidators)e?.executeOnResults?n.push(e):this.#e(e)||(e.constructor.async?i.push(e):r.push(e));let a=!!i.length;this.__syncValidationResult=[...this.__syncValidationResult,...this.__executeSyncValidators(r,e)],this.__finishValidationPass({syncValidationResult:this.__syncValidationResult,metaValidators:n}),a?(this.isPending=!0,this.__asyncValidationResult=await this.__executeAsyncValidators(i,e),this.isPending=!1,this.__finishValidationPass({syncValidationResult:this.__syncValidationResult,asyncValidationResult:this.__asyncValidationResult,metaValidators:n}),this.__validateCompleteResolve?.(!0)):this.__validateCompleteResolve?.(!0)}__executeSyncValidators(e,t){return e.map(e=>({validator:e,outcome:e.execute(t,e.param,{node:this})})).filter(e=>!!e.outcome)}async __executeAsyncValidators(e,t){let n=e.map(e=>e.execute(t,e.param,{node:this})),r=await Promise.all(n);return r.map((t,n)=>({validator:e[n],outcome:r[n]})).filter(e=>!!e.outcome)}__executeMetaValidators(e,t){return t.length?this._isEmpty(this.modelValue)?(this.__prevShownValidationResult=[],[]):t.map(t=>({validator:t,outcome:t.executeOnResults({regularValidationResult:e.map(e=>e.validator),prevValidationResult:this.__prevValidationResult.map(e=>e.validator),prevShownValidationResult:this.__prevShownValidationResult.map(e=>e.validator)})})).filter(e=>!!e.outcome):[]}__finishValidationPass({syncValidationResult:e=[],asyncValidationResult:t=[],metaValidators:n=[]}={}){let r=[...e,...t];this.__validationResult=[...this.__executeMetaValidators(r,n),...r];let i=this.constructor.validationTypes.reduce((e,t)=>({...e,[t]:{}}),{});for(let{validator:e,outcome:t}of this.__validationResult){i[e.type]||(i[e.type]={});let n=e.constructor;i[e.type][n.validatorName]=t}this.validationStates=i,this.hasFeedbackFor=[...new Set(this.__validationResult.map(({validator:e})=>e.type))],this.dispatchEvent(new Event(`validate-performed`,{bubbles:!0}))}__clearValidationResults(){this.__syncValidationResult=[],this.__asyncValidationResult=[]}_onValidatorUpdated(e){(e.type===`param-changed`||e.type===`config-changed`)&&this.validate()}__setupValidators(){let e=[`param-changed`,`config-changed`];for(let t of this.__prevValidators||[]){for(let n of e)t.removeEventListener?.(n,this._onValidatorUpdated);t.onFormControlDisconnect(this)}for(let t of this._allValidators){if(t.constructor._$isValidator$===void 0){let e=`Validators array only accepts class instances of Validator. Type "${Array.isArray(t)?`array`:typeof t}" found. This may be caused by having multiple installations of "@lion/ui/form-core.js".`;throw console.error(e,this),Error(e)}let n=this.constructor,r=t.constructor;if(n.validationTypes.indexOf(t.type)===-1){let e=`This component does not support the validator type "${t.type}" used in "${r.validatorName}". You may change your validators type or add it to the components "static get validationTypes() {}".`;throw console.error(e,this),Error(e)}for(let n of e)t.addEventListener?.(n,e=>{this._onValidatorUpdated(e,{validator:t})});t.onFormControlConnect(this)}this.__prevValidators=this._allValidators}__isEmpty(e){return typeof this._isEmpty==`function`?this._isEmpty(e):this.modelValue===null||this.modelValue===void 0||this.modelValue===``}async __getFeedbackMessages(e){let t=await this.fieldName;return Promise.all(e.map(async({validator:e,outcome:n})=>(e.config.fieldName&&(t=await e.config.fieldName),{message:await e._getMessage({modelValue:this.modelValue,formControl:this,fieldName:t,outcome:n}),type:e.type,validator:e,visibilityDuration:e.config?.visibilityDuration||3e3})))}_updateFeedbackComponent(){window.clearTimeout(this.removeMessage);let{_feedbackNode:e}=this;e&&(this.__feedbackQueue||=new go,this.showsFeedbackFor.length>0?this.__feedbackQueue.add(async()=>{this.__prioritizedResult=this._prioritizeAndFilterFeedback({validationResult:this.__validationResult.map(e=>e.validator)}).map(e=>this.__validationResult.find(t=>e===t.validator)).filter(Boolean),this.__prioritizedResult.length>0&&(this.__prevShownValidationResult=this.__prioritizedResult);let t=await this.__getFeedbackMessages(this.__prioritizedResult);e.feedbackData=t||[],t?.[0]&&t[0].type===`success`&&t[0].visibilityDuration!==1/0&&(this.removeMessage=window.setTimeout(()=>{e.removeAttribute(`type`),e.feedbackData=[]},t[0].visibilityDuration))}):this.__feedbackQueue.add(async()=>{e.feedbackData=[]}),this.feedbackComplete=this.__feedbackQueue.complete)}_showFeedbackConditionFor(e,t){return!0}get _feedbackConditionMeta(){return{modelValue:this.modelValue,el:this}}feedbackCondition(e,t=this._feedbackConditionMeta,n=this._showFeedbackConditionFor.bind(this)){return n(e,t)}_hasFeedbackVisibleFor(e){return this.hasFeedbackFor?.includes(e)&&this.shouldShowFeedbackFor?.includes(e)}updated(e){if(super.updated(e),(e.has(`shouldShowFeedbackFor`)||e.has(`hasFeedbackFor`))&&(this.showsFeedbackFor=this.constructor.validationTypes.map(e=>this._hasFeedbackVisibleFor(e)?e:void 0).filter(Boolean),this._updateFeedbackComponent()),e.has(`__childModelValueChanged`)&&this.__childModelValueChanged&&(this.validate({clearCurrentResult:!0}),this.__childModelValueChanged=!1),e.has(`validationStates`)){let t=e.get(`validationStates`);t&&Object.entries(this.validationStates).forEach(([e,n])=>{t[e]&&JSON.stringify(n)!==JSON.stringify(t[e])&&this.dispatchEvent(new CustomEvent(`${e}StateChanged`,{detail:n}))})}}_updateShouldShowFeedbackFor(){let e=this.constructor.validationTypes.map(e=>this.feedbackCondition(e,this._feedbackConditionMeta,this._showFeedbackConditionFor.bind(this))?e:void 0).filter(Boolean);JSON.stringify(this.shouldShowFeedbackFor)!==JSON.stringify(e)&&(this.shouldShowFeedbackFor=e)}_prioritizeAndFilterFeedback({validationResult:e}){let t=this.constructor.validationTypes;return e.filter(e=>this.feedbackCondition(e.type,this._feedbackConditionMeta,this._showFeedbackConditionFor.bind(this))).sort((e,n)=>t.indexOf(e.type)-t.indexOf(n.type)).slice(0,this._visibleMessagesAmount)}}),jo=Or(e=>class extends Ao(Eo(e)){static get properties(){return{formattedValue:{attribute:!1},serializedValue:{attribute:!1},formatOptions:{attribute:!1}}}#e={didFormatterOutputSyncToView:!1,didFormatterRun:!1};requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`modelValue`&&this.modelValue!==t&&this._onModelValueChanged({modelValue:this.modelValue},{modelValue:t}),e===`serializedValue`&&this.serializedValue!==t&&this._calculateValues({source:`serialized`}),e===`formattedValue`&&this.formattedValue!==t&&this._calculateValues({source:`formatted`})}get value(){return this._inputNode?.value||this.__value||``}set value(e){this._inputNode?(this._inputNode.value=e,this.__value=void 0):this.__value=e}preprocessor(e,t){}parser(e,t){return e}formatter(e,t){return e}serializer(e){return e===void 0?``:e}deserializer(e){return e===void 0?``:e}_calculateValues({source:e}={source:null}){this.__preventRecursiveTrigger||(this.__preventRecursiveTrigger=!0,e!==`model`&&(e===`serialized`?this.modelValue=this.deserializer(this.serializedValue):e===`formatted`&&(this.modelValue=this._callParser())),e!==`formatted`&&(this.formattedValue=this._callFormatter()),e!==`serialized`&&(this.serializedValue=this.serializer(this.modelValue)),this._reflectBackFormattedValueToUser(),this.__preventRecursiveTrigger=!1,this.__prevViewValue=this.value)}_callParser(e=this.formattedValue){if(e===``)return``;if(typeof e!=`string`)return;let t=this.parser(e,{...this.formatOptions,mode:this.#t(),viewValueStates:this.#n()});return t===void 0?new So(e):t}_callFormatter(){return this.#e.didFormatterRun=!1,this._isHandlingUserInput&&this.hasFeedbackFor?.includes(`error`)&&this._inputNode?this.value:this.modelValue instanceof So?this.modelValue.viewValue:(this.#e.didFormatterRun=!0,this.formatter(this.modelValue,{...this.formatOptions,mode:this.#t(),viewValueStates:this.#n()}))}_onModelValueChanged(...e){this._calculateValues({source:`model`}),this._dispatchModelValueChangedEvent(...e)}_dispatchModelValueChangedEvent(...e){this.dispatchEvent(new CustomEvent(`model-value-changed`,{bubbles:!0,detail:{formPath:[this],isTriggeredByUser:!!this._isHandlingUserInput}}))}_syncValueUpwards(){this.__isHandlingComposition||this.__handlePreprocessor();let e=this.formattedValue;this.modelValue=this._callParser(this.value),e===this.formattedValue&&this.__prevViewValue!==this.value&&this._calculateValues()}__handlePreprocessor(){let e=this.value.length;this._inputNode&&`selectionStart`in this._inputNode&&this._inputNode?.type!==`range`&&(e=this._inputNode.selectionStart);let t=this.preprocessor(this.value,{...this.formatOptions,currentCaretIndex:e,prevViewValue:this.__prevViewValue});if(t!==void 0){if(typeof t==`string`)this.value=t;else if(typeof t==`object`){let{viewValue:e,caretIndex:n}=t;this.value=e,n&&this._inputNode&&`selectionStart`in this._inputNode&&(this._inputNode.selectionStart=n,this._inputNode.selectionEnd=n)}}}_reflectBackFormattedValueToUser(){this._reflectBackOn()&&(this.value=this.formattedValue===void 0?``:this.formattedValue,this.#e.didFormatterOutputSyncToView=!!this.formattedValue&&this.#e.didFormatterRun)}_reflectBackOn(){return!this._isHandlingUserInput}_proxyInputEvent(){this.dispatchEvent(new Event(`user-input-changed`,{bubbles:!0}))}_onUserInputChanged(){this._isHandlingUserInput=!0,this._syncValueUpwards(),this._isHandlingUserInput=!1}__onCompositionEvent({type:e}){e===`compositionstart`?this.__isHandlingComposition=!0:e===`compositionend`&&(this.__isHandlingComposition=!1,this._syncValueUpwards())}constructor(){super(),this.formatOn=`change`,this.formatOptions={mode:`auto`},this.formattedValue=void 0,this.serializedValue=void 0,this._isPasting=!1,this._isHandlingUserInput=!1,this.__prevViewValue=``,this.__onCompositionEvent=this.__onCompositionEvent.bind(this),this.addEventListener(`user-input-changed`,this._onUserInputChanged),this.addEventListener(`paste`,this.__onPaste),this._reflectBackFormattedValueToUser=this._reflectBackFormattedValueToUser.bind(this),this._reflectBackFormattedValueDebounced=()=>{setTimeout(this._reflectBackFormattedValueToUser)}}__onPaste(){this._isPasting=!0,setTimeout(()=>{this._isPasting=!1})}connectedCallback(){super.connectedCallback(),this.modelValue===void 0&&this._syncValueUpwards(),this.__prevViewValue=this.value,this._reflectBackFormattedValueToUser(),this._inputNode&&(this._inputNode.addEventListener(this.formatOn,this._reflectBackFormattedValueDebounced),this._inputNode.addEventListener(`input`,this._proxyInputEvent),this._inputNode.addEventListener(`compositionstart`,this.__onCompositionEvent),this._inputNode.addEventListener(`compositionend`,this.__onCompositionEvent))}disconnectedCallback(){super.disconnectedCallback(),this._inputNode&&(this._inputNode.removeEventListener(`input`,this._proxyInputEvent),this._inputNode.removeEventListener(this.formatOn,this._reflectBackFormattedValueDebounced),this._inputNode.removeEventListener(`compositionstart`,this.__onCompositionEvent),this._inputNode.removeEventListener(`compositionend`,this.__onCompositionEvent))}#t(){return this._isPasting?`pasted`:this._isHandlingUserInput&&this.__prevViewValue?`user-edited`:`auto`}#n(){let e=[];return this.#e.didFormatterOutputSyncToView&&e.push(`formatted`),e}}),Mo=Or(e=>class extends Eo(e){static get properties(){return{touched:{type:Boolean,reflect:!0},dirty:{type:Boolean,reflect:!0},filled:{type:Boolean,reflect:!0},prefilled:{attribute:!1},submitted:{attribute:!1}}}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`touched`&&this.touched!==t&&this._onTouchedChanged(),e===`modelValue`&&(this.filled=!this._isEmpty()),e===`dirty`&&this.dirty!==t&&this._onDirtyChanged()}constructor(){super(),this.touched=!1,this.dirty=!1,this.prefilled=!1,this.filled=!1,this._leaveEvent=`blur`,this._valueChangedEvent=`model-value-changed`,this._iStateOnLeave=this._iStateOnLeave.bind(this),this._iStateOnValueChange=this._iStateOnValueChange.bind(this)}connectedCallback(){super.connectedCallback(),this.addEventListener(this._leaveEvent,this._iStateOnLeave),this.addEventListener(this._valueChangedEvent,this._iStateOnValueChange),this.initInteractionState()}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener(this._leaveEvent,this._iStateOnLeave),this.removeEventListener(this._valueChangedEvent,this._iStateOnValueChange)}initInteractionState(){this.dirty=!1,this.prefilled=!this._isEmpty()}_iStateOnLeave(){this.touched=!0,this.prefilled=!this._isEmpty()}_iStateOnValueChange(){this.dirty=!0}resetInteractionState(){this.touched=!1,this.submitted=!1,this.dirty=!1,this.prefilled=!this._isEmpty()}_onTouchedChanged(){this.dispatchEvent(new Event(`touched-changed`,{bubbles:!0,composed:!0}))}_onDirtyChanged(){this.dispatchEvent(new Event(`dirty-changed`,{bubbles:!0,composed:!0}))}_showFeedbackConditionFor(e,t){return t.touched&&t.dirty||t.prefilled||t.submitted}get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,submitted:this.submitted,touched:this.touched,dirty:this.dirty,filled:this.filled,prefilled:this.prefilled}}}),No=class extends Eo(Mo(Ta(jo(Ao(Ur(b)))))){firstUpdated(e){super.firstUpdated(e),this._initialModelValue=this.modelValue}connectedCallback(){super.connectedCallback(),this._onChange=this._onChange.bind(this),this._inputNode.addEventListener(`change`,this._onChange),this.classList.add(`form-field`)}disconnectedCallback(){super.disconnectedCallback(),this._inputNode?.removeEventListener(`change`,this._onChange)}resetInteractionState(){super.resetInteractionState(),this.submitted=!1}reset(){this.modelValue=this._initialModelValue,this.resetInteractionState()}clear(){this.modelValue=``}_onChange(e){this.dispatchEvent(new Event(`user-input-changed`,{bubbles:!0}))}get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,focused:this.focused}}get _focusableNode(){return this._inputNode}},Po=class extends Array{_keys(){return Object.keys(this).filter(e=>Number.isNaN(Number(e)))}},Fo=Or(e=>class extends To(e){static get properties(){return{_isFormOrFieldset:{type:Boolean}}}constructor(){super(),this.formElements=new Po,this._isFormOrFieldset=!1,this._onRequestToAddFormElement=this._onRequestToAddFormElement.bind(this),this._onRequestToChangeFormElementName=this._onRequestToChangeFormElementName.bind(this),this.addEventListener(`form-element-register`,this._onRequestToAddFormElement),this.addEventListener(`form-element-name-changed`,this._onRequestToChangeFormElementName),this.initComplete=new Promise((e,t)=>{this.__resolveInitComplete=e,this.__rejectInitComplete=t}),this.registrationComplete=new Promise((e,t)=>{this.__resolveRegistrationComplete=e,this.__rejectRegistrationComplete=t}),this.registrationComplete.done=!1,this.registrationComplete.then(()=>{this.registrationComplete.done=!0,this.__resolveInitComplete(void 0)},()=>{throw this.registrationComplete.done=!0,this.__rejectInitComplete(void 0),Error(`Registration could not finish. Please use await el.registrationComplete;`)})}connectedCallback(){super.connectedCallback(),this._completeRegistration()}_completeRegistration(){Promise.resolve().then(()=>this.__resolveRegistrationComplete(void 0))}disconnectedCallback(){super.disconnectedCallback(),this.registrationComplete.done===!1&&Promise.resolve().then(()=>{Promise.resolve().then(()=>{this.__rejectRegistrationComplete()})})}isRegisteredFormElement(e){return this.formElements.some(t=>t===e)}addFormElement(e,t){if(e._parentFormGroup=this,t>=0?this.formElements.splice(t,0,e):this.formElements.push(e),this._isFormOrFieldset){let{name:n}=e;if(n===this.name)throw console.info(`Error Node:`,e),TypeError(`You can not have the same name "${n}" as your parent`);if(n.substr(-2)===`[]`)Array.isArray(this.formElements[n])||(this.formElements[n]=new Po),t>0?this.formElements[n].splice(t,0,e):this.formElements[n].push(e);else if(!this.formElements[n])this.formElements[n]=e;else throw console.info(`Error Node:`,e),TypeError(`Name "${n}" is already registered - if you want an array add [] to the end`)}}removeFormElement(e){let t=this.formElements.indexOf(e);if(t>-1&&this.formElements.splice(t,1),this._isFormOrFieldset){let{name:t}=e;if(t.substr(-2)===`[]`&&this.formElements[t]){let n=this.formElements[t].indexOf(e);n>-1&&this.formElements[t].splice(n,1)}else this.formElements[t]&&delete this.formElements[t]}}_onRequestToAddFormElement(e){let t=e.detail.element;if(t===this||this.isRegisteredFormElement(t))return;e.stopPropagation();let n=-1;if(this.formElements&&Array.isArray(this.formElements)){for(let[e,r]of this.formElements.entries())if(!(r.compareDocumentPosition(t)&Node.DOCUMENT_POSITION_FOLLOWING)){n=e;break}}this.addFormElement(t,n)}_onRequestToChangeFormElementName(e){let t=this.formElements[e.detail.oldName];t&&(this.formElements[e.detail.newName]=t,delete this.formElements[e.detail.oldName])}_onRequestToRemoveFormElement(e){let t=e.detail.element;t!==this&&this.isRegisteredFormElement(t)&&(e.stopPropagation(),this.removeFormElement(t))}}),Io=Or(e=>class extends e{constructor(){super(),this.registrationTarget=void 0,this.__redispatchEventForFormRegistrarPortalMixin=this.__redispatchEventForFormRegistrarPortalMixin.bind(this),this.addEventListener(`form-element-register`,this.__redispatchEventForFormRegistrarPortalMixin)}__redispatchEventForFormRegistrarPortalMixin(e){if(e.stopPropagation(),!this.registrationTarget)throw Error(`A FormRegistrarPortal element requires a .registrationTarget`);this.registrationTarget.dispatchEvent(new CustomEvent(`form-element-register`,{detail:{element:e.detail.element},bubbles:!0}))}}),Lo=Or(e=>class extends jo(Ta(Eo(e))){static get properties(){return{autocomplete:{type:String,reflect:!0}}}constructor(){super(),this.autocomplete=void 0}get _inputNode(){return super._inputNode}get selectionStart(){let e=this._inputNode;return e&&e.selectionStart?e.selectionStart:0}set selectionStart(e){let t=this._inputNode;t&&t.selectionStart&&(t.selectionStart=e)}get selectionEnd(){let e=this._inputNode;return e&&e.selectionEnd?e.selectionEnd:0}set selectionEnd(e){let t=this._inputNode;t&&t.selectionEnd&&(t.selectionEnd=e)}get value(){return this._inputNode&&this._inputNode.value||this.__value||``}set value(e){this._inputNode?(this._inputNode.value!==e&&this._setValueAndPreserveCaret(e),this.__value=void 0):this.__value=e}_setValueAndPreserveCaret(e){if(this.focused)try{if(!(this._inputNode instanceof HTMLSelectElement)){let t=this._inputNode.selectionStart;this._inputNode.value=e,this._inputNode.selectionStart=t,this._inputNode.selectionEnd=t}}catch{this._inputNode.value=e}else this._inputNode.value=e}_reflectBackFormattedValueToUser(){if(super._reflectBackFormattedValueToUser(),this._reflectBackOn()&&this.focused)try{this._inputNode.selectionStart=this._inputNode.value.length}catch{}}get _focusableNode(){return this._inputNode}}),Ro=Or(e=>class extends Fo(Ao(Mo(e))){static get properties(){return{multipleChoice:{type:Boolean,attribute:`multiple-choice`}}}get modelValue(){let e=this._getCheckedElements();return this.multipleChoice?e.map(e=>e.choiceValue):e[0]?e[0].choiceValue:``}set modelValue(e){let t=(t,n)=>typeof t.choiceValue==`object`?JSON.stringify(t.choiceValue)===JSON.stringify(e):t.choiceValue===n;this.__isInitialModelValue?this.registrationComplete.then(()=>{this.__isInitialModelValue=!1,this._setCheckedElements(e,t),this.requestUpdate(`modelValue`,this._oldModelValue)}):(this._setCheckedElements(e,t),this.requestUpdate(`modelValue`,this._oldModelValue)),this._oldModelValue=this.modelValue}get serializedValue(){let e=this._getCheckedElements();return this.multipleChoice?e.map(e=>e.serializedValue.value):e[0]?e[0].serializedValue.value:``}set serializedValue(e){let t=(e,t)=>e.serializedValue.value===t;this.__isInitialSerializedValue?this.registrationComplete.then(()=>{this.__isInitialSerializedValue=!1,this._setCheckedElements(e,t),this.requestUpdate(`serializedValue`)}):(this._setCheckedElements(e,t),this.requestUpdate(`serializedValue`))}get formattedValue(){let e=this._getCheckedElements();return this.multipleChoice?e.map(e=>e.formattedValue):e[0]?e[0].formattedValue:``}set formattedValue(e){let t=(e,t)=>e.formattedValue===t;this.__isInitialFormattedValue?this.registrationComplete.then(()=>{this.__isInitialFormattedValue=!1,this._setCheckedElements(e,t)}):this._setCheckedElements(e,t)}get operationMode(){return this._repropagationRole===`choice-group`?`select`:`enter`}constructor(){super(),this.multipleChoice=!1,this._repropagationRole=`choice-group`,this.__isInitialModelValue=!0,this.__isInitialSerializedValue=!0,this.__isInitialFormattedValue=!0}connectedCallback(){super.connectedCallback(),this.registrationComplete.then(()=>{this.__isInitialModelValue=!1,this.__isInitialSerializedValue=!1,this.__isInitialFormattedValue=!1})}_completeRegistration(){Promise.resolve().then(()=>super._completeRegistration())}updated(e){super.updated(e),e.has(`name`)&&this.name!==e.get(`name`)&&this.formElements.forEach(e=>{e.name=this.name})}addFormElement(e,t){this._throwWhenInvalidChildModelValue(e),e.name=this.name,super.addFormElement(e,t)}clear(){this.multipleChoice?this.modelValue=[]:this.modelValue=``}_triggerInitialModelValueChangedEvent(){this.registrationComplete.then(()=>{this._dispatchInitialModelValueChangedEvent()})}_getFromAllFormElementsFilter(e,t){return!0}_getFromAllFormElements(e,t){let n=t||this._getFromAllFormElementsFilter;return e===`modelValue`||e===`serializedValue`||e===`formattedValue`?this[e]:this.formElements.filter(t=>n(t,e)).map(e=>e.property)}_throwWhenInvalidChildModelValue(e){if(typeof e.modelValue.checked!=`boolean`||!Object.prototype.hasOwnProperty.call(e.modelValue,`value`))throw Error(`The ${this.tagName.toLowerCase()} name="${this.name}" does not allow to register ${e.tagName.toLowerCase()} with .modelValue="${e.modelValue}" - The modelValue should represent an Object { value: "foo", checked: false }`)}_isEmpty(){return this.multipleChoice?this.modelValue.length===0:typeof this.modelValue==`string`&&this.modelValue===``||this.modelValue===void 0||this.modelValue===null}_checkSingleChoiceElements(e){let{target:t}=e;if(t.checked===!1)return;let n=t.name;this.formElements.filter(e=>e.name===n).forEach(e=>{e!==t&&(e.checked=!1)})}_getCheckedElements(){return this.formElements.filter(e=>e.checked&&!e.disabled)}_setCheckedElements(e,t){if(e==null){this.formElements.forEach(e=>e.checked=!1);return}for(let n=0;n<this.formElements.length;n+=1)if(this.multipleChoice){let t=e.includes(this.formElements[n].modelValue.value);typeof this.formElements[n].modelValue.value==`object`&&(t=e.map(e=>JSON.stringify(e)).includes(JSON.stringify(this.formElements[n].modelValue.value))),this.formElements[n].checked=t}else t(this.formElements[n],e)?this.formElements[n].checked=!0:this.formElements[n].checked=!1}__setChoiceGroupTouched(){let e=this.modelValue;e!=null&&e!==this.__previousCheckedValue&&(this.touched=!0,this.__previousCheckedValue=e)}_onBeforeRepropagateChildrenValues(e){let t=e.detail&&e.detail.element||e.target;this.multipleChoice||!t.checked||(this.formElements.forEach(e=>{t.choiceValue!==e.choiceValue&&(e.checked=!1)}),this.__setChoiceGroupTouched(),this.requestUpdate(`modelValue`,this._oldModelValue),this._oldModelValue=this.modelValue)}_repropagationCondition(e){return!(this._repropagationRole===`choice-group`&&!this.multipleChoice&&!e.checked)}}),zo=(e,t={})=>e.value!==t.value||e.checked!==t.checked,Bo=Or(e=>class extends jo(e){static get properties(){return{checked:{type:Boolean,reflect:!0},disabled:{type:Boolean,reflect:!0},modelValue:{type:Object,hasChanged:zo},choiceValue:{type:Object}}}get choiceValue(){return this.modelValue.value}set choiceValue(e){this.requestUpdate(`choiceValue`,this.choiceValue),this.modelValue.value!==e&&(this.modelValue={value:e,checked:this.modelValue.checked})}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`modelValue`?this.modelValue.checked!==this.checked&&this.__syncModelCheckedToChecked(this.modelValue.checked):e===`checked`&&this.modelValue.checked!==this.checked&&this.__syncCheckedToModel(this.checked)}firstUpdated(e){super.firstUpdated(e),e.has(`checked`)&&this.__syncCheckedToInputElement()}updated(e){super.updated(e),e.has(`modelValue`)&&this.__syncCheckedToInputElement(),e.has(`name`)&&this._parentFormGroup&&this._parentFormGroup.name!==this.name&&this._syncNameToParentFormGroup()}constructor(){super(),this.modelValue={value:``,checked:!1},this.disabled=!1,this._preventDuplicateLabelClick=this._preventDuplicateLabelClick.bind(this),this._toggleChecked=this._toggleChecked.bind(this)}static get styles(){return[...super.styles||[],h`
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
        `]}render(){return p`
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
      `}_choiceGraphicTemplate(){return y}_afterTemplate(){return y}connectedCallback(){super.connectedCallback(),this._labelNode&&this._labelNode.addEventListener(`click`,this._preventDuplicateLabelClick),this.addEventListener(`user-input-changed`,this._toggleChecked)}disconnectedCallback(){super.disconnectedCallback(),this._labelNode&&this._labelNode.removeEventListener(`click`,this._preventDuplicateLabelClick),this.removeEventListener(`user-input-changed`,this._toggleChecked)}_preventDuplicateLabelClick(e){let t=e=>{e.stopImmediatePropagation(),this._inputNode.removeEventListener(`click`,t)};this._inputNode.addEventListener(`click`,t)}_toggleChecked(e){this.disabled||(this._isHandlingUserInput=!0,this.checked=!this.checked,this._isHandlingUserInput=!1)}_syncNameToParentFormGroup(){this._parentFormGroup.tagName.includes(this.tagName)&&(this.name=this._parentFormGroup?.name||``)}__syncModelCheckedToChecked(e){this.checked=e}__syncCheckedToModel(e){this.modelValue={value:this.choiceValue,checked:e}}__syncCheckedToInputElement(){this._inputNode&&(this._inputNode.checked=this.checked)}_proxyInputEvent(){}_onModelValueChanged({modelValue:e},t){let n;t&&t.modelValue&&(n=t.modelValue),this.constructor.elementProperties.get(`modelValue`).hasChanged(e,n)&&super._onModelValueChanged({modelValue:e})}parser(){return this.modelValue}formatter(e){return e&&e.value!==void 0?e.value:e}clear(){this.checked=!1}_isEmpty(){return!this.checked}_syncValueUpwards(){}}),Vo=class extends Do{static get validatorName(){return`FormElementsHaveNoError`}execute(e,t,n){return n?.node._anyFormElementHasFeedbackFor(`error`)}static async getMessage(){return``}},Ho=Or(e=>class extends Fo(Eo(Ao(kr(Ur(e))))){static get properties(){return{submitted:{type:Boolean,reflect:!0},focused:{type:Boolean,reflect:!0},dirty:{type:Boolean,reflect:!0},touched:{type:Boolean,reflect:!0},prefilled:{type:Boolean,reflect:!0}}}get _inputNode(){return this}get modelValue(){return this._getFromAllFormElements(`modelValue`)}set modelValue(e){this.__isInitialModelValue?(this.__isInitialModelValue=!1,this.registrationComplete.then(()=>{this._setValueMapForAllFormElements(`modelValue`,e)})):this._setValueMapForAllFormElements(`modelValue`,e)}get serializedValue(){return this._getFromAllFormElements(`serializedValue`)}set serializedValue(e){this.__isInitialSerializedValue?(this.__isInitialSerializedValue=!1,this.registrationComplete.then(()=>{this._setValueMapForAllFormElements(`serializedValue`,e)})):this._setValueMapForAllFormElements(`serializedValue`,e)}get formattedValue(){return this._getFromAllFormElements(`formattedValue`)}set formattedValue(e){this._setValueMapForAllFormElements(`formattedValue`,e)}get prefilled(){return this._everyFormElementHas(`prefilled`)}constructor(){super(),this.value=``,this.disabled=!1,this.submitted=!1,this.dirty=!1,this.touched=!1,this.focused=!1,this.__addedSubValidators=!1,this.__isInitialModelValue=!0,this.__isInitialSerializedValue=!0,this._checkForOutsideClick=this._checkForOutsideClick.bind(this),this.addEventListener(`focusin`,this._syncFocused),this.addEventListener(`focusout`,this._onFocusOut),this.addEventListener(`dirty-changed`,this._syncDirty),this.addEventListener(`validate-performed`,this.__onChildValidatePerformed),this.defaultValidators=[new Vo],this.__descriptionElementsInParentChain=new Set,this.__pendingValues={modelValue:{},serializedValue:{}}}connectedCallback(){super.connectedCallback(),this.setAttribute(`role`,`group`),this.initComplete.then(()=>{this.__isInitialModelValue=!1,this.__isInitialSerializedValue=!1,this.__initInteractionStates()})}disconnectedCallback(){super.disconnectedCallback(),this.__hasActiveOutsideClickHandling&&=(document.removeEventListener(`click`,this._checkForOutsideClick),!1),this.__descriptionElementsInParentChain.clear()}__initInteractionStates(){this.formElements.forEach(e=>{typeof e.initInteractionState==`function`&&e.initInteractionState()})}_triggerInitialModelValueChangedEvent(){this.registrationComplete.then(()=>{this._dispatchInitialModelValueChangedEvent()})}updated(e){super.updated(e),e.has(`disabled`)&&(this.disabled?this.__requestChildrenToBeDisabled():this.__retractRequestChildrenToBeDisabled()),e.has(`focused`)&&this.focused===!0&&this.__setupOutsideClickHandling()}__setupOutsideClickHandling(){this.__hasActiveOutsideClickHandling||=(document.addEventListener(`click`,this._checkForOutsideClick),!0)}_checkForOutsideClick(e){this.contains(e.target)||(this.touched=!0)}__requestChildrenToBeDisabled(){this.formElements.forEach(e=>{e.makeRequestToBeDisabled&&e.makeRequestToBeDisabled()})}__retractRequestChildrenToBeDisabled(){this.formElements.forEach(e=>{e.retractRequestToBeDisabled&&e.retractRequestToBeDisabled()})}_inputGroupTemplate(){return p`
        <div class="input-group">
          <slot></slot>
        </div>
      `}submitGroup(){this.submitted=!0,this.formElements.forEach(e=>{typeof e.submitGroup==`function`?e.submitGroup():e.submitted=!0})}resetGroup(){this.formElements.forEach(e=>{typeof e.resetGroup==`function`?e.resetGroup():typeof e.reset==`function`&&e.reset()}),this.resetInteractionState()}clearGroup(){this.formElements.forEach(e=>{typeof e.clearGroup==`function`?e.clearGroup():typeof e.clear==`function`&&e.clear()}),this.resetInteractionState()}resetInteractionState(){this.submitted=!1,this.touched=!1,this.dirty=!1,this.formElements.forEach(e=>{typeof e.resetInteractionState==`function`&&e.resetInteractionState()})}_getFromAllFormElementsFilter(e,t){return!e.disabled}_getFromAllFormElements(e,t){let n={},r=t||this._getFromAllFormElementsFilter;return this.formElements._keys().forEach(t=>{let i=this.formElements[t];i instanceof Po?n[t]=i.filter(t=>r(t,e)).map(t=>t[e]):r(i,e)&&(typeof i._getFromAllFormElements==`function`?n[t]=i._getFromAllFormElements(e):n[t]=i[e])}),n}_setValueForAllFormElements(e,t){this.formElements.forEach(n=>{n[e]=t})}_setValueMapForAllFormElements(e,t){t&&typeof t==`object`&&Object.keys(t).forEach(n=>{Array.isArray(this.formElements[n])&&this.formElements[n].forEach((r,i)=>{r[e]=t[n][i]}),this.formElements[n]?this.formElements[n][e]=t[n]:this.__pendingValues[e][n]=t[n]})}_anyFormElementHas(e){return Object.keys(this.formElements).some(t=>Array.isArray(this.formElements[t])?this.formElements[t].some(t=>!!t[e]):!!this.formElements[t][e])}_anyFormElementHasFeedbackFor(e){return Object.keys(this.formElements).some(t=>Array.isArray(this.formElements[t])?this.formElements[t].some(t=>!!(t.hasFeedbackFor&&t.hasFeedbackFor.includes(e))):!!(this.formElements[t].hasFeedbackFor&&this.formElements[t].hasFeedbackFor.includes(e)))}_everyFormElementHas(e){return Object.keys(this.formElements).every(t=>Array.isArray(this.formElements[t])?this.formElements[t].every(t=>!!t[e]):!!this.formElements[t][e])}__onChildValidatePerformed(e){e&&this.isRegisteredFormElement(e.target)&&this.validate()}_syncFocused(){this.focused=this._anyFormElementHas(`focused`)}_onFocusOut(e){let t=this.formElements[this.formElements.length-1];e.target===t&&(this.touched=!0),this.focused=!1}_syncDirty(){this.dirty=this._anyFormElementHas(`dirty`)}__storeAllDescriptionElementsInParentChain(){let e=this;for(;e;)wo(e._getAriaDescriptionElements(),{reverse:!0}).forEach(e=>{e.getAttribute(`slot`)===`feedback`&&this.__descriptionElementsInParentChain.add(e)}),e=e._parentFormGroup}__linkParentMessages(e){this.__descriptionElementsInParentChain.forEach(t=>{typeof e.addToAriaDescribedBy==`function`&&e.addToAriaDescribedBy(t,{reorder:!1})})}__unlinkParentMessages(e){this.__descriptionElementsInParentChain.forEach(t=>{typeof e.removeFromAriaDescribedBy==`function`&&e.removeFromAriaDescribedBy(t)})}addFormElement(e,t){if(super.addFormElement(e,t),this.disabled&&e.makeRequestToBeDisabled(),this.__descriptionElementsInParentChain.size||this.__storeAllDescriptionElementsInParentChain(),this.__linkParentMessages(e),this.validate({clearCurrentResult:!0}),!e.modelValue){let t=this.__pendingValues;t.modelValue&&t.modelValue[e.name]?e.modelValue=t.modelValue[e.name]:t.serializedValue&&t.serializedValue[e.name]&&(e.serializedValue=t.serializedValue[e.name])}}get _initialModelValue(){return this._getFromAllFormElements(`_initialModelValue`)}removeFormElement(e){super.removeFormElement(e),this.validate({clearCurrentResult:!0}),typeof e.removeFromAriaLabelledBy==`function`&&this._labelNode&&e.removeFromAriaLabelledBy(this._labelNode,{reorder:!1}),this.__unlinkParentMessages(e)}_isEmpty(){return this.formElements.every(e=>e._isEmpty?.())}}),Uo=class extends Lo(No){static get properties(){return{readOnly:{type:Boolean,attribute:`readonly`,reflect:!0},type:{type:String,reflect:!0},placeholder:{type:String,reflect:!0}}}get slots(){return{...super.slots,input:()=>{let e=document.createElement(`input`),t=this.getAttribute(`value`);return t&&e.setAttribute(`value`,t),e}}}get _inputNode(){return super._inputNode}constructor(){super(),this.readOnly=!1,this.type=`text`,this.placeholder=``}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`readOnly`&&this.__delegateReadOnly()}firstUpdated(e){super.firstUpdated(e),this.__delegateReadOnly()}updated(e){super.updated(e),e.has(`type`)&&(this._inputNode.type=this.type),e.has(`placeholder`)&&(this._inputNode.placeholder=this.placeholder),e.has(`disabled`)&&(this._inputNode.disabled=this.disabled,this.validate()),e.has(`name`)&&(this._inputNode.name=this.name),e.has(`autocomplete`)&&(this._inputNode.autocomplete=this.autocomplete)}__delegateReadOnly(){this._inputNode&&(this._inputNode.readOnly=this.readOnly)}},Wo=h`
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
`,Go=class extends Uo{constructor(...e){super(...e),this.size=`medium`,this.small=!1,this.center=!1}static get styles(){return[...super.styles,xa,Wo]}connectedCallback(){super.connectedCallback(),this._inputNode&&this.maxlength&&this.maxlength>0&&(this._inputNode.size=this.maxlength)}};d([x({type:Number,reflect:!0})],Go.prototype,`maxlength`,void 0),d([x({type:String,reflect:!0})],Go.prototype,`size`,void 0),d([x({reflect:!0,type:Boolean})],Go.prototype,`small`,void 0),d([x({reflect:!0,type:Boolean})],Go.prototype,`center`,void 0),customElements.get(`craft-input`)||customElements.define(`craft-input`,Go);var Ko=e=>e??y,qo=class extends Do{static validatorName=`IsAcceptedFile`;static checkFileSize(e,t){return e<=t}static getExtension(e){return e?.slice(e.lastIndexOf(`.`))}static isExtensionAllowed(e,t){return t?.find(t=>t.toUpperCase()===e.toUpperCase())}static isFileTypeAllowed(e,t){return t?.find(t=>t.toUpperCase()===e.toUpperCase())}execute(e,t=this.param){let n,r,i=this.constructor,{allowedFileTypes:a,allowedFileExtensions:o,maxFileSize:s}=t;return a?.length?(n=e.some(e=>!i.isFileTypeAllowed(e.type,a)),n):o?.length?(r=e.some(e=>!i.isExtensionAllowed(i.getExtension(e.name),o)),r):e.findIndex(e=>!i.checkFileSize(e.size,s))>-1}static async getMessage(){return``}},Jo=class extends Do{static validatorName=`DuplicateFileNames`;constructor(e,t){super(e,t),this.type=`info`}execute(e,t=this.param){return t.show}static async getMessage(){return Ya().msg(`lion-input-file:uploadTextDuplicateFileName`)}},Yo=524288e3,Xo={type:`FILE_TYPE`,size:`FILE_SIZE`},Zo={fail:`FAIL`,pass:`SUCCESS`},Qo=class{constructor(e,t){this.failedProp=[],this.systemFile=e,this._acceptCriteria=t,this.uploadFileStatus(),this.failedProp.length===0&&this.createDownloadUrl(e)}_getFileNameExtension(e){return e.slice(e.lastIndexOf(`.`))}uploadFileStatus(){if(this._acceptCriteria.allowedFileExtensions.length){let e=this._getFileNameExtension(this.systemFile.name);qo.isExtensionAllowed(e,this._acceptCriteria.allowedFileExtensions)||(this.status=Zo.fail,this.failedProp.push(Xo.type))}else if(this._acceptCriteria.allowedFileTypes.length){let e=this.systemFile.type;qo.isFileTypeAllowed(e,this._acceptCriteria.allowedFileTypes)||(this.status=Zo.fail,this.failedProp.push(Xo.type))}qo.checkFileSize(this.systemFile.size,this._acceptCriteria.maxFileSize)?this.status!==Zo.fail&&(this.status=Zo.pass):(this.status=Zo.fail,this.failedProp.push(Xo.size))}createDownloadUrl(e){this.downloadUrl=window.URL.createObjectURL(e)}},$o=(e,t,n)=>{let r=new Map;for(let i=t;i<=n;i++)r.set(e[i],i);return r},es=E(class extends te{constructor(e){if(super(e),e.type!==ne.CHILD)throw Error(`repeat() can only be used in text expressions`)}dt(e,t,n){let r;n===void 0?n=t:t!==void 0&&(r=t);let i=[],a=[],o=0;for(let t of e)i[o]=r?r(t,o):o,a[o]=n(t,o),o++;return{values:a,keys:i}}render(e,t,n){return this.dt(e,t,n).values}update(e,[t,n,r]){let i=Br(e),{values:a,keys:o}=this.dt(t,n,r);if(!Array.isArray(i))return this.ut=o,a;let s=this.ut??=[],c=[],l,u,d=0,f=i.length-1,p=0,m=a.length-1;for(;d<=f&&p<=m;)if(i[d]===null)d++;else if(i[f]===null)f--;else if(s[d]===o[p])c[p]=Lr(i[d],a[p]),d++,p++;else if(s[f]===o[m])c[m]=Lr(i[f],a[m]),f--,m--;else if(s[d]===o[m])c[m]=Lr(i[d],a[m]),Ir(e,c[m+1],i[d]),d++,m--;else if(s[f]===o[p])c[p]=Lr(i[f],a[p]),Ir(e,i[d],i[f]),f--,p++;else if(l===void 0&&(l=$o(o,p,m),u=$o(s,d,f)),l.has(s[d]))if(l.has(s[f])){let t=u.get(o[p]),n=t===void 0?null:i[t];if(n===null){let t=Ir(e,i[d]);Lr(t,a[p]),c[p]=t}else c[p]=Lr(n,a[p]),Ir(e,i[d],n),i[t]=null;p++}else Vr(i[f]),f--;else Vr(i[d]),d++;for(;p<=m;){let t=Ir(e,c[m+1]);Lr(t,a[p]),c[p++]=t}for(;d<=f;){let e=i[d++];e!==null&&Vr(e)}return this.ut=o,zr(e,c),g}}),ts=e=>{switch(e){case`bg-BG`:return O(()=>import(`./bg-BG2.js`),__vite__mapDeps([34,35]),import.meta.url);case`bg`:return O(()=>import(`./bg3.js`),[],import.meta.url);case`cs-CZ`:return O(()=>import(`./cs-CZ2.js`),__vite__mapDeps([36,37]),import.meta.url);case`cs`:return O(()=>import(`./cs3.js`),[],import.meta.url);case`de-DE`:return O(()=>import(`./de-DE2.js`),__vite__mapDeps([38,39]),import.meta.url);case`de`:return O(()=>import(`./de3.js`),[],import.meta.url);case`en-AU`:return O(()=>import(`./en-AU2.js`),__vite__mapDeps([40,41]),import.meta.url);case`en-GB`:return O(()=>import(`./en-GB2.js`),__vite__mapDeps([42,41]),import.meta.url);case`en-US`:return O(()=>import(`./en-US2.js`),__vite__mapDeps([43,41]),import.meta.url);case`en-PH`:case`en`:return O(()=>import(`./en3.js`),[],import.meta.url);case`es-ES`:return O(()=>import(`./es-ES2.js`),__vite__mapDeps([44,45]),import.meta.url);case`es`:return O(()=>import(`./es3.js`),[],import.meta.url);case`fr-FR`:return O(()=>import(`./fr-FR2.js`),__vite__mapDeps([46,47]),import.meta.url);case`fr-BE`:return O(()=>import(`./fr-BE2.js`),__vite__mapDeps([48,47]),import.meta.url);case`fr`:return O(()=>import(`./fr3.js`),[],import.meta.url);case`hu-HU`:return O(()=>import(`./hu-HU2.js`),__vite__mapDeps([49,50]),import.meta.url);case`hu`:return O(()=>import(`./hu3.js`),[],import.meta.url);case`it-IT`:return O(()=>import(`./it-IT2.js`),__vite__mapDeps([51,52]),import.meta.url);case`it`:return O(()=>import(`./it3.js`),[],import.meta.url);case`nl-BE`:return O(()=>import(`./nl-BE2.js`),__vite__mapDeps([53,54]),import.meta.url);case`nl-NL`:return O(()=>import(`./nl-NL2.js`),__vite__mapDeps([55,54]),import.meta.url);case`nl`:return O(()=>import(`./nl3.js`),[],import.meta.url);case`pl-PL`:return O(()=>import(`./pl-PL2.js`),__vite__mapDeps([56,57]),import.meta.url);case`pl`:return O(()=>import(`./pl3.js`),[],import.meta.url);case`ro-RO`:return O(()=>import(`./ro-RO2.js`),__vite__mapDeps([58,59]),import.meta.url);case`ro`:return O(()=>import(`./ro3.js`),[],import.meta.url);case`ru-RU`:return O(()=>import(`./ru-RU2.js`),__vite__mapDeps([60,61]),import.meta.url);case`ru`:return O(()=>import(`./ru3.js`),[],import.meta.url);case`sk-SK`:return O(()=>import(`./sk-SK2.js`),__vite__mapDeps([62,63]),import.meta.url);case`sk`:return O(()=>import(`./sk3.js`),[],import.meta.url);case`uk-UA`:return O(()=>import(`./uk-UA2.js`),__vite__mapDeps([64,65]),import.meta.url);case`uk`:return O(()=>import(`./uk3.js`),[],import.meta.url);case`zh-CN`:case`zh`:return O(()=>import(`./zh3.js`),[],import.meta.url);default:return O(()=>import(`./en3.js`),[],import.meta.url)}},ns=class extends co(ho(b)){static get scopedElements(){return{...super.scopedElements,"lion-validation-feedback":xo}}static get properties(){return{fileList:{type:Array},multiple:{type:Boolean}}}static localizeNamespaces=[{"lion-input-file":ts},...super.localizeNamespaces];constructor(){super(),this.fileList=[],this.multiple=!1}updated(e){super.updated(e),e.has(`fileList`)&&this._enhanceLightDomA11y()}_enhanceLightDomA11y(){let e=this.shadowRoot?.querySelectorAll(`[id^="file-feedback"]`),t=this.parentNode?.parentNode;e?.forEach(e=>{t?.addEventListener(`focusin`,()=>{e.setAttribute(`aria-live`,`polite`)}),t?.addEventListener(`focusout`,()=>{e.setAttribute(`aria-live`,`assertive`)})})}_removeFile(e){this.dispatchEvent(new CustomEvent(`file-remove-requested`,{detail:{removedFile:e,status:e.status,uploadResponse:e.response}}))}_validationFeedbackTemplate(e,t){return p`
      <lion-validation-feedback
        id="file-feedback-${t}"
        .feedbackData="${e}"
        aria-live="assertive"
      ></lion-validation-feedback>
    `}_listItemBeforeTemplate(e){return y}_listItemAfterTemplate(e,t){return p`
      <button
        class="selected__list__item__remove-button"
        aria-label="${this.msgLit(`lion-input-file:removeButtonLabel`,{fileName:e.systemFile.name})}"
        @click=${()=>this._removeFile(e)}
      >
        ${this._removeButtonContentTemplate()}
      </button>
    `}_removeButtonContentTemplate(){return p`✖️`}_selectedListItemTemplate(e){let t=Kr();return p`
      <div class="selected__list__item" status="${e.status?e.status.toLowerCase():``}">
        <div class="selected__list__item__label">
          ${this._listItemBeforeTemplate(e)}
          <span id="selected-list-item-label-${t}" class="selected__list__item__label__text">
            <span class="sr-only">${this.msgLit(`lion-input-file:fileNameDescriptionLabel`)}</span>
            ${e.downloadUrl&&e.status!==`LOADING`?p`
                  <a
                    class="selected__list__item__label__link"
                    href="${e.downloadUrl}"
                    target="${e.downloadUrl.startsWith(`blob`)?`_blank`:``}"
                    rel="${Ko(e.downloadUrl.startsWith(`blob`)?`noopener noreferrer`:void 0)}"
                    >${e.systemFile?.name}</a
                  >
                `:e.systemFile?.name}
          </span>
          ${this._listItemAfterTemplate(e,t)}
        </div>
        ${e.status===`FAIL`&&e.validationFeedback?p`
              ${es(e.validationFeedback,e=>p`
                  ${this._validationFeedbackTemplate([e],t)}
                `)}
            `:y}
      </div>
    `}render(){return this.fileList?.length?p`
          ${this.multiple?p`
                <ul class="selected__list">
                  ${this.fileList.map(e=>p` <li>${this._selectedListItemTemplate(e)}</li> `)}
                </ul>
              `:p` ${this._selectedListItemTemplate(this.fileList[0])} `}
        `:y}static get styles(){return[h`
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
      `]}};function rs(e,t=2){if(!+e)return`0 Bytes`;let n=1024,r=t<0?0:t,i=[` bytes`,`KB`,`MB`,`GB`,`TB`,`PB`,`EB`,`ZB`,`YB`],a=Math.floor(Math.log(e)/Math.log(n));return`${parseFloat((e/n**a).toFixed(r))}${i[a]}`}var is=class extends ho(co(No)){static get scopedElements(){return{...super.scopedElements,"lion-selected-file-list":ns}}static get properties(){return{accept:{type:String},multiple:{type:Boolean,reflect:!0},buttonLabel:{type:String,attribute:`button-label`},maxFileSize:{type:Number,attribute:`max-file-size`},enableDropZone:{type:Boolean,attribute:`enable-drop-zone`},uploadOnSelect:{type:Boolean,attribute:`upload-on-select`},isDragging:{type:Boolean,attribute:`is-dragging`,reflect:!0},uploadResponse:{type:Array,state:!1},_selectedFilesMetaData:{type:Array,state:!0}}}static localizeNamespaces=[{"lion-input-file":ts},...super.localizeNamespaces];static get validationTypes(){return[`error`,`info`]}get slots(){return{...super.slots,input:()=>p`<input .value="${Ko(this.getAttribute(`value`))}" />`,"file-select-button":()=>p`<button
          type="button"
          id="select-button-${this._inputId}"
          @click="${this.__openDialogOnBtnClick}"
        >
          ${this.buttonLabel}
        </button>`,after:()=>p`<div data-description></div>`,"selected-file-list":()=>({template:p`
          <lion-selected-file-list
            .fileList=${this._selectedFilesMetaData}
            .multiple=${this.multiple}
          ></lion-selected-file-list>
        `,renderAsDirectHostChild:!0})}}get _inputNode(){return super._inputNode}get _buttonNode(){return this.querySelector(`#select-button-${this._inputId}`)}get buttonLabel(){return this.__buttonLabel||this._buttonNode?.textContent?.trim()||``}set buttonLabel(e){let t=this.buttonLabel;this.__buttonLabel=e,this.requestUpdate(`buttonLabel`,t)}get _focusableNode(){return this._buttonNode}get _isDragAndDropSupported(){return`draggable`in document.createElement(`div`)}constructor(){super(),this.type=`file`,this._selectedFilesMetaData=[],this.uploadResponse=[],this.__initialUploadResponse=this.uploadResponse,this.uploadOnSelect=!1,this.multiple=!1,this.enableDropZone=!1,this.maxFileSize=Yo,this.accept=``,this.buttonLabel=``,this._initialButtonLabel=``,this.modelValue=[],this._onRemoveFile=this._onRemoveFile.bind(this),this.__duplicateFileNamesValidator=new Jo({show:!1}),this.__previouslyParsedFiles=null}get _fileListNode(){return Array.from(this.children).find(e=>e.slot===`selected-file-list`)}connectedCallback(){super.connectedCallback(),this.__initialUploadResponse=this.uploadResponse,this._initialButtonLabel=this.buttonLabel,this._inputNode.addEventListener(`change`,this._onChange),this._inputNode.addEventListener(`click`,this._onClick)}disconnectedCallback(){super.disconnectedCallback(),this._inputNode.removeEventListener(`change`,this._onChange),this._inputNode.removeEventListener(`click`,this._onClick)}onLocaleUpdated(){super.onLocaleUpdated(),this.multiple?this.buttonLabel=this._initialButtonLabel||this.msgLit(`lion-input-file:selectTextMultipleFile`):this.buttonLabel=this._initialButtonLabel||this.msgLit(`lion-input-file:selectTextSingleFile`)}get operationMode(){return`upload`}get _acceptCriteria(){let e=[],t=[];if(this.accept){let n=this.accept.replace(/\s+/g,``).split(`,`);e=n.filter(e=>e.includes(`/`)),t=n.filter(e=>!e.includes(`/`))}return{allowedFileTypes:e,allowedFileExtensions:t,maxFileSize:this.maxFileSize}}reset(){super.reset(),this._selectedFilesMetaData=[],this.uploadResponse=this.__initialUploadResponse,this.modelValue=[],this.dirty=!1}clear(){this._selectedFilesMetaData=[],this.uploadResponse=[],this.modelValue=[]}_showFeedbackConditionFor(e,t){return super._showFeedbackConditionFor(e,t)&&!(this.validationStates.error?.FileTypeAllowed||this.validationStates.error?.FileSizeAllowed)}parser(){if(this.__previouslyParsedFiles===this._inputNode.files)return this.modelValue;this.__previouslyParsedFiles=this._inputNode.files;let e=this._inputNode.files?Array.from(this._inputNode.files):[];return this.multiple?[...this.modelValue??[],...e]:e}formatter(e){return this._inputNode?.value||``}__setupDragDropEventListeners(){let e=this.shadowRoot?.querySelector(`.input-file__drop-zone`);[`dragenter`,`dragover`,`dragleave`].forEach(t=>{e?.addEventListener(t,e=>{e.preventDefault(),e.stopPropagation(),this.isDragging=t!==`dragleave`},!1)}),window.addEventListener(`drop`,e=>{e.target===this._inputNode&&e.preventDefault(),this.isDragging=!1},!1)}firstUpdated(e){super.firstUpdated(e),this.__setupFileValidators(),this._inputNode&&(this._inputNode.type=this.type,this._inputNode.setAttribute(`tabindex`,`-1`),this._inputNode.multiple=this.multiple,this.accept.length&&(this._inputNode.accept=this.accept)),this.enableDropZone&&this._isDragAndDropSupported&&(this.__setupDragDropEventListeners(),this.setAttribute(`drop-zone`,``)),this._fileListNode.addEventListener(`file-remove-requested`,this._onRemoveFile)}updated(e){super.updated(e),e.has(`disabled`)&&(this._inputNode.disabled=this.disabled,this.validate()),e.has(`buttonLabel`)&&this._buttonNode&&(this._buttonNode.textContent=this.buttonLabel),e.has(`name`)&&(this._inputNode.name=this.name),e.has(`_ariaLabelledNodes`)&&this.__syncAriaLabelledByAttributesToButton(),e.has(`_ariaDescribedNodes`)&&this.__syncAriaDescribedByAttributesToButton(),e.has(`uploadResponse`)&&(this._selectedFilesMetaData.length===0&&this.uploadResponse.forEach(e=>{let t={systemFile:{name:e.name},response:e,status:e.status,validationFeedback:[{message:e.errorMessage}]};this._selectedFilesMetaData=[...this._selectedFilesMetaData,t]}),this._selectedFilesMetaData.forEach(e=>{!this.uploadResponse.some(t=>t.name===e.systemFile.name)&&this.uploadOnSelect?this.__removeFileFromList(e):(this.uploadResponse.forEach(t=>{t.name===e.systemFile.name&&(e.response=t,e.downloadUrl=t.downloadUrl?t.downloadUrl:e.downloadUrl,e.status=t.status,e.validationFeedback=[{type:typeof t.errorMessage==`string`&&t.errorMessage?.length>0?`error`:`success`,message:t.errorMessage??``}])}),this._selectedFilesMetaData=[...this._selectedFilesMetaData])}),this._updateUploadButtonDescription())}__computeNewAddedFiles(e){let t=e.filter(e=>this._selectedFilesMetaData.findIndex(t=>t.systemFile.name===e.name)===-1);return this.__duplicateFileNamesValidator.param={show:e.length!==t.length},this.validate(),t}_processDroppedFiles(e){if(e.preventDefault(),this.isDragging=!1,!(e.dataTransfer&&e.dataTransfer.items.length>1&&!this.multiple||!e.dataTransfer?.files)){if(this._inputNode.files=e.dataTransfer.files,this.multiple){let t=this.__computeNewAddedFiles(Array.from(e.dataTransfer.files));this.modelValue=[...this.modelValue??[],...t]}else this.modelValue=Array.from(e.dataTransfer.files);this._processFiles(Array.from(e.dataTransfer.files))}}_onChange(e){this.touched=!0,this._onUserInputChanged(),this._processFiles(e?.target?.files)}_onClick(e){e.target.value=``}__syncAriaLabelledByAttributesToButton(){if(this._inputNode.hasAttribute(`aria-labelledby`)){let e=this._inputNode.getAttribute(`aria-labelledby`);this._buttonNode?.setAttribute(`aria-labelledby`,`select-button-${this._inputId} ${e}`)}}__syncAriaDescribedByAttributesToButton(){if(this._inputNode.hasAttribute(`aria-describedby`)){let e=this._inputNode.getAttribute(`aria-describedby`)||``;this._buttonNode?.setAttribute(`aria-describedby`,e)}}__setupFileValidators(){this.defaultValidators=[new qo(this._acceptCriteria),this.__duplicateFileNamesValidator]}_processFiles(e){let t=this.__computeNewAddedFiles(Array.from(e));!this.multiple&&t.length>0&&(this._selectedFilesMetaData=[],this.uploadResponse=[]);let n;for(let e of t.values())n=new Qo(e,this._acceptCriteria),n.failedProp?.length?(this._handleErroredFiles(n),this.uploadResponse=[...this.uploadResponse,{name:n.systemFile.name,status:`FAIL`,errorMessage:n.validationFeedback[0].message}]):this.uploadResponse=[...this.uploadResponse,{name:n.systemFile.name,status:`SUCCESS`}],this._selectedFilesMetaData=[...this._selectedFilesMetaData,n],this._handleErrors();let r=this._selectedFilesMetaData.filter(({systemFile:e,status:n})=>t.includes(e)&&n===`SUCCESS`).map(({systemFile:e})=>e);r.length>0&&this._dispatchFileListChangeEvent(r)}_dispatchFileListChangeEvent(e){this.dispatchEvent(new CustomEvent(`file-list-changed`,{detail:{newFiles:e}}))}_handleErrors(){let e=!1;if(this._selectedFilesMetaData.forEach(t=>{t.failedProp&&t.failedProp.length>0&&(e=!0)}),e)this.hasFeedbackFor?.push(`error`),this.shouldShowFeedbackFor.push(`error`);else if(this._prevHasErrors&&this.hasFeedbackFor.includes(`error`)){let e=this.hasFeedbackFor.indexOf(`error`);this.hasFeedbackFor.slice(e,e+1);let t=this.shouldShowFeedbackFor.indexOf(`error`);this.shouldShowFeedbackFor.slice(t,t+1)}this._prevHasErrors=e}_handleErroredFiles(e){e.validationFeedback=[];let{allowedFileExtensions:t,allowedFileTypes:n}=this._acceptCriteria,r=[],i=0,a;t.length?(r=t,a=r.pop(),i=r.length):n.length&&(n.forEach(e=>{if(e.endsWith(`/*`))r.push(e.slice(0,-2));else if(e===`text/plain`)r.push(`text`);else{let t=e.indexOf(`/`),n=e.slice(t+1);if(!n.includes(`+`))r.push(`.${n}`);else{let e=n.split(`+`);r.push(`.${e[0]}`)}}}),a=r.pop(),i=r.length);let o=``;o=a?i?`${this.msgLit(`lion-input-file:allowedFileValidatorComplex`,{allowedTypesArray:r.join(`, `),allowedTypesLastItem:a,maxSize:rs(this.maxFileSize)})}`:`${this.msgLit(`lion-input-file:allowedFileValidatorSimple`,{allowedType:a,maxSize:rs(this.maxFileSize)})}`:`${this.msgLit(`lion-input-file:allowedFileSize`,{maxSize:rs(this.maxFileSize)})}`;let s={message:o,type:`error`};e.validationFeedback?.push(s)}_updateUploadButtonDescription(){let e=[],t;this._selectedFilesMetaData.forEach(n=>{n.status===`FAIL`&&(t=n.validationFeedback?n.validationFeedback[0].message.toString():``,e.push(n.systemFile.name))});let n=this.querySelector(`[slot="after"]`);if(n)if(!this._selectedFilesMetaData||this._selectedFilesMetaData.length===0)this.uploadOnSelect?n.textContent=this.msgLit(`lion-input-file:noFilesUploaded`):n.textContent=this.msgLit(`lion-input-file:noFilesSelected`);else if(this._selectedFilesMetaData.length===1){let{name:e}=this._selectedFilesMetaData[0].systemFile;this.uploadOnSelect?n.textContent=t||this.msgLit(`lion-input-file:fileUploaded`)+(e??``):n.textContent=t||this.msgLit(`lion-input-file:fileSelected`)+(e??``)}else this.uploadOnSelect?n.textContent=`${this.msgLit(`lion-input-file:filesUploaded`,{numberOfFiles:this._selectedFilesMetaData.length})} ${t?this.msgLit(`lion-input-file:generalValidatorMessage`,{validatorMessage:t,listOfErroneousFiles:e.join(`, `)}):``}`:n.textContent=`${this.msgLit(`lion-input-file:filesSelected`,{numberOfFiles:this._selectedFilesMetaData.length})} ${t?this.msgLit(`lion-input-file:generalValidatorMessage`,{validatorMessage:t,listOfErroneousFiles:e.join(`, `)}):``}`}__removeFileFromList(e){this._selectedFilesMetaData=this._selectedFilesMetaData.filter(t=>t.systemFile.name!==e.systemFile.name),this.modelValue&&=this.modelValue.filter(t=>t.name!==e.systemFile.name),this._inputNode.value=``,this._handleErrors(),this._updateUploadButtonDescription()}_onRemoveFile(e){if(this.disabled)return;let{removedFile:t}=e.detail;!this.uploadOnSelect&&t&&this.__removeFileFromList(t),this._removeFile(t)}_removeFile(e){this.dispatchEvent(new CustomEvent(`file-removed`,{detail:{removedFile:e,status:e.status,uploadResponse:e.response}}))}_reflectBackOn(){return!1}_isEmpty(){return this.modelValue?.length===0}_dropZoneTemplate(){return p`
      <div @drop="${this._processDroppedFiles}" class="input-file__drop-zone">
        <div class="input-file__drop-zone__text">
          ${this.msgLit(`lion-input-file:dragAndDropText`)}
        </div>
        <slot name="file-select-button"></slot>
      </div>
    `}_inputGroupAfterTemplate(){return p` <slot name="selected-file-list"></slot> `}_inputGroupInputTemplate(){return p`
      <slot name="input"> </slot>
      <slot name="after"> </slot>
      ${this.enableDropZone&&this._isDragAndDropSupported?this._dropZoneTemplate():p`
            <div class="input-group__file-select-button">
              <slot name="file-select-button"></slot>
            </div>
          `}
    `}static get styles(){return[super.styles,h`
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
      `]}__openDialogOnBtnClick(e){e.preventDefault(),e.stopPropagation(),this._inputNode.click()}},as=class extends ns{static get styles(){return[...super.styles,h`
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
      `]}_listItemAfterTemplate(e,t){return p`
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
    `}_removeButtonContentTemplate(){return p`<craft-icon name="x"></craft-icon>`}_listItemBeforeTemplate(e){return p`<img src="${e.downloadUrl}" alt="" class="preview-thumb" />`}},os=h`
  /* Add any craft-specific styles for input-file here */
  ::slotted([slot='selected-file-list']) {
    margin-block-start: var(--c-spacing-lg);
  }
`,ss=class extends is{static get styles(){return[...super.styles,xa,os]}get slots(){return{...super.slots,"file-select-button":()=>p`<craft-button
          type="button"
          id="select-button-${this._inputId}"
          @click="${this.__openDialogOnBtnClick}"
        >
          ${this.buttonLabel}
        </craft-button>`}}static get scopedElements(){return{...super.scopedElements,"lion-selected-file-list":as}}};customElements.get(`craft-input-file`)||customElements.define(`craft-input-file`,ss);var cs=function(e,t,n,r,i){if(r===`m`)throw TypeError(`Private method is not writable`);if(r===`a`&&!i)throw TypeError(`Private accessor was defined without a setter`);if(typeof t==`function`?e!==t||!i:!t.has(e))throw TypeError(`Cannot write private member to an object whose class did not declare it`);return r===`a`?i.call(e,n):i?i.value=n:t.set(e,n),n},ls=function(e,t,n,r){if(n===`a`&&!r)throw TypeError(`Private accessor was defined without a getter`);if(typeof t==`function`?e!==t||!r:!t.has(e))throw TypeError(`Cannot read private member from an object whose class did not declare it`);return n===`m`?r:n===`a`?r.call(e):r?r.value:t.get(e)},us,ds=class{formatToParts(e){let t=[];for(let n of e)t.push({type:`element`,value:n}),t.push({type:`literal`,value:`, `});return t.slice(0,-1)}},fs=typeof Intl<`u`&&Intl.ListFormat||ds,ps=[[`years`,`year`],[`months`,`month`],[`weeks`,`week`],[`days`,`day`],[`hours`,`hour`],[`minutes`,`minute`],[`seconds`,`second`],[`milliseconds`,`millisecond`]],ms={minimumIntegerDigits:2},hs=class{constructor(e,t={}){us.set(this,void 0);let n=String(t.style||`short`);n!==`long`&&n!==`short`&&n!==`narrow`&&n!==`digital`&&(n=`short`);let r=n===`digital`?`numeric`:n,i=t.hours||r;r=i===`2-digit`?`numeric`:i;let a=t.minutes||r;r=a===`2-digit`?`numeric`:a;let o=t.seconds||r;r=o===`2-digit`?`numeric`:o;let s=t.milliseconds||r;cs(this,us,{locale:e,style:n,years:t.years||n===`digital`?`short`:n,yearsDisplay:t.yearsDisplay===`always`?`always`:`auto`,months:t.months||n===`digital`?`short`:n,monthsDisplay:t.monthsDisplay===`always`?`always`:`auto`,weeks:t.weeks||n===`digital`?`short`:n,weeksDisplay:t.weeksDisplay===`always`?`always`:`auto`,days:t.days||n===`digital`?`short`:n,daysDisplay:t.daysDisplay===`always`?`always`:`auto`,hours:i,hoursDisplay:t.hoursDisplay===`always`||n===`digital`?`always`:`auto`,minutes:a,minutesDisplay:t.minutesDisplay===`always`||n===`digital`?`always`:`auto`,seconds:o,secondsDisplay:t.secondsDisplay===`always`||n===`digital`?`always`:`auto`,milliseconds:s,millisecondsDisplay:t.millisecondsDisplay===`always`?`always`:`auto`},`f`)}resolvedOptions(){return ls(this,us,`f`)}formatToParts(e){let t=[],n=ls(this,us,`f`),r=n.style,i=n.locale;for(let[a,o]of ps){let s=e[a];if(n[`${a}Display`]===`auto`&&!s)continue;let c=n[a],l=c===`2-digit`?ms:c===`numeric`?{}:{style:`unit`,unit:o,unitDisplay:c},u=new Intl.NumberFormat(i,l).format(s);a===`months`&&(c===`narrow`||r===`narrow`&&u.endsWith(`m`))&&(u=u.replace(/(\d+)m$/,`$1mo`)),t.push(u)}return new fs(i,{type:`unit`,style:r===`digital`?`short`:r}).formatToParts(t)}format(e){return this.formatToParts(e).map(e=>e.value).join(``)}};us=new WeakMap;var gs=/^[-+]?P(?:(\d+)Y)?(?:(\d+)M)?(?:(\d+)W)?(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?)?$/,_s=[`year`,`month`,`week`,`day`,`hour`,`minute`,`second`,`millisecond`],vs=e=>gs.test(e),ys=class e{constructor(e=0,t=0,n=0,r=0,i=0,a=0,o=0,s=0){this.years=e,this.months=t,this.weeks=n,this.days=r,this.hours=i,this.minutes=a,this.seconds=o,this.milliseconds=s,this.years||=0,this.sign||=Math.sign(this.years),this.months||=0,this.sign||=Math.sign(this.months),this.weeks||=0,this.sign||=Math.sign(this.weeks),this.days||=0,this.sign||=Math.sign(this.days),this.hours||=0,this.sign||=Math.sign(this.hours),this.minutes||=0,this.sign||=Math.sign(this.minutes),this.seconds||=0,this.sign||=Math.sign(this.seconds),this.milliseconds||=0,this.sign||=Math.sign(this.milliseconds),this.blank=this.sign===0}abs(){return new e(Math.abs(this.years),Math.abs(this.months),Math.abs(this.weeks),Math.abs(this.days),Math.abs(this.hours),Math.abs(this.minutes),Math.abs(this.seconds),Math.abs(this.milliseconds))}static from(t){if(typeof t==`string`){let n=String(t).trim(),r=n.startsWith(`-`)?-1:1,i=n.match(gs)?.slice(1).map(e=>(Number(e)||0)*r);return i?new e(...i):new e}else if(typeof t==`object`){let{years:n,months:r,weeks:i,days:a,hours:o,minutes:s,seconds:c,milliseconds:l}=t;return new e(n,r,i,a,o,s,c,l)}throw RangeError(`invalid duration`)}static compare(t,n){let r=Date.now(),i=Math.abs(bs(r,e.from(t)).getTime()-r),a=Math.abs(bs(r,e.from(n)).getTime()-r);return i>a?-1:i<a?1:0}toLocaleString(e,t){return new hs(e,t).format(this)}};function bs(e,t){let n=new Date(e);return t.sign<0?(n.setUTCSeconds(n.getUTCSeconds()+t.seconds),n.setUTCMinutes(n.getUTCMinutes()+t.minutes),n.setUTCHours(n.getUTCHours()+t.hours),n.setUTCDate(n.getUTCDate()+t.weeks*7+t.days),n.setUTCMonth(n.getUTCMonth()+t.months),n.setUTCFullYear(n.getUTCFullYear()+t.years)):(n.setUTCFullYear(n.getUTCFullYear()+t.years),n.setUTCMonth(n.getUTCMonth()+t.months),n.setUTCDate(n.getUTCDate()+t.weeks*7+t.days),n.setUTCHours(n.getUTCHours()+t.hours),n.setUTCMinutes(n.getUTCMinutes()+t.minutes),n.setUTCSeconds(n.getUTCSeconds()+t.seconds)),n}function xs(e,t=`second`,n=Date.now()){let r=e.getTime()-n;if(r===0)return new ys;let i=Math.sign(r),a=Math.abs(r),o=Math.floor(a/1e3),s=Math.floor(o/60),c=Math.floor(s/60),l=Math.floor(c/24),u=Math.floor(l/30),d=Math.floor(u/12),f=_s.indexOf(t)||_s.length;return new ys(f>=0?d*i:0,f>=1?(u-d*12)*i:0,0,f>=3?(l-u*30)*i:0,f>=4?(c-l*24)*i:0,f>=5?(s-c*60)*i:0,f>=6?(o-s*60)*i:0,f>=7?(a-o*1e3)*i:0)}function Ss(e,{relativeTo:t=Date.now()}={}){if(t=new Date(t),e.blank)return e;let n=e.sign,r=Math.abs(e.years),i=Math.abs(e.months),a=Math.abs(e.weeks),o=Math.abs(e.days),s=Math.abs(e.hours),c=Math.abs(e.minutes),l=Math.abs(e.seconds),u=Math.abs(e.milliseconds);u>=900&&(l+=Math.round(u/1e3)),(l||c||s||o||a||i||r)&&(u=0),l>=55&&(c+=Math.round(l/60)),(c||s||o||a||i||r)&&(l=0),c>=55&&(s+=Math.round(c/60)),(s||o||a||i||r)&&(c=0),o&&s>=12&&(o+=Math.round(s/24)),!o&&s>=21&&(o+=Math.round(s/24)),(o||a||i||r)&&(s=0);let d=t.getFullYear(),f=t.getMonth(),p=t.getDate();if(o>=27||r+i+o){let e=new Date(t);e.setDate(1),e.setMonth(f+i*n+1),e.setDate(0);let s=Math.max(0,p-e.getDate()),c=new Date(t);c.setFullYear(d+r*n),c.setDate(p-s),c.setMonth(f+i*n),c.setDate(p-s+o*n);let l=c.getFullYear()-t.getFullYear(),u=c.getMonth()-t.getMonth(),m=Math.abs(Math.round((Number(c)-Number(t))/864e5))+s,h=Math.abs(l*12+u);m<27?(o>=6?(a+=Math.round(o/7),o=0):o=m,i=r=0):h<=11?(i=h,r=0):(i=0,r=l*n),(i||r)&&(o=0)}return r&&(i=0),a>=4&&(i+=Math.round(a/4)),(i||r)&&(a=0),o&&a&&!i&&!r&&(a+=Math.round(o/7),o=0),new ys(r*n,i*n,a*n,o*n,s*n,c*n,l*n,u*n)}function Cs(e,t){let n=Ss(e,t);if(n.blank)return[0,`second`];for(let e of _s){if(e===`millisecond`)continue;let t=n[`${e}s`];if(t)return[t,e]}return[0,`second`]}var I=function(e,t,n,r){if(n===`a`&&!r)throw TypeError(`Private accessor was defined without a getter`);if(typeof t==`function`?e!==t||!r:!t.has(e))throw TypeError(`Cannot read private member from an object whose class did not declare it`);return n===`m`?r:n===`a`?r.call(e):r?r.value:t.get(e)},ws=function(e,t,n,r,i){if(r===`m`)throw TypeError(`Private method is not writable`);if(r===`a`&&!i)throw TypeError(`Private accessor was defined without a setter`);if(typeof t==`function`?e!==t||!i:!t.has(e))throw TypeError(`Cannot write private member to an object whose class did not declare it`);return r===`a`?i.call(e,n):i?i.value=n:t.set(e,n),n},Ts,Es,Ds,Os,ks,As,js,Ms,Ns,Ps,Fs,Is,Ls,Rs,zs=globalThis.HTMLElement||null,Bs=new ys,Vs=new ys(0,0,0,0,0,1),Hs=class extends Event{constructor(e,t,n,r){super(`relative-time-updated`,{bubbles:!0,composed:!0}),this.oldText=e,this.newText=t,this.oldTitle=n,this.newTitle=r}};function Us(e){if(!e.date)return 1/0;if(e.format===`duration`||e.format===`elapsed`){let t=e.precision;if(t===`second`)return 1e3;if(t===`minute`)return 60*1e3}let t=Math.abs(Date.now()-e.date.getTime());return t<60*1e3?1e3:t<3600*1e3?60*1e3:3600*1e3}var Ws=new class{constructor(){this.elements=new Set,this.time=1/0,this.timer=-1}observe(e){if(this.elements.has(e))return;this.elements.add(e);let t=e.date;if(t&&t.getTime()){let t=Us(e),n=Date.now()+t;n<this.time&&(clearTimeout(this.timer),this.timer=setTimeout(()=>this.update(),t),this.time=n)}}unobserve(e){this.elements.has(e)&&this.elements.delete(e)}update(){if(clearTimeout(this.timer),!this.elements.size)return;let e=1/0;for(let t of this.elements)e=Math.min(e,Us(t)),t.update();this.time=Math.min(3600*1e3,e),this.timer=setTimeout(()=>this.update(),this.time),this.time+=Date.now()}},Gs=class extends zs{constructor(){super(...arguments),Ts.add(this),Es.set(this,!1),Ds.set(this,!1),ks.set(this,this.shadowRoot?this.shadowRoot:this.attachShadow?this.attachShadow({mode:`open`}):this),Rs.set(this,null)}static define(e=`relative-time`,t=customElements){return t.define(e,this),this}get timeZone(){return this.closest(`[time-zone]`)?.getAttribute(`time-zone`)||this.ownerDocument.documentElement.getAttribute(`time-zone`)||void 0}static get observedAttributes(){return[`second`,`minute`,`hour`,`weekday`,`day`,`month`,`year`,`time-zone-name`,`prefix`,`threshold`,`tense`,`precision`,`format`,`format-style`,`no-title`,`datetime`,`lang`,`title`,`aria-hidden`,`time-zone`]}get onRelativeTimeUpdated(){return I(this,Rs,`f`)}set onRelativeTimeUpdated(e){I(this,Rs,`f`)&&this.removeEventListener(`relative-time-updated`,I(this,Rs,`f`)),ws(this,Rs,typeof e==`object`||typeof e==`function`?e:null,`f`),typeof e==`function`&&this.addEventListener(`relative-time-updated`,e)}get second(){let e=this.getAttribute(`second`);if(e===`numeric`||e===`2-digit`)return e}set second(e){this.setAttribute(`second`,e||``)}get minute(){let e=this.getAttribute(`minute`);if(e===`numeric`||e===`2-digit`)return e}set minute(e){this.setAttribute(`minute`,e||``)}get hour(){let e=this.getAttribute(`hour`);if(e===`numeric`||e===`2-digit`)return e}set hour(e){this.setAttribute(`hour`,e||``)}get weekday(){let e=this.getAttribute(`weekday`);if(e===`long`||e===`short`||e===`narrow`)return e;if(this.format===`datetime`&&e!==``)return this.formatStyle}set weekday(e){this.setAttribute(`weekday`,e||``)}get day(){let e=this.getAttribute(`day`)??`numeric`;if(e===`numeric`||e===`2-digit`)return e}set day(e){this.setAttribute(`day`,e||``)}get month(){let e=this.format,t=this.getAttribute(`month`);if(t!==``&&(t??=e===`datetime`?this.formatStyle:`short`,t===`numeric`||t===`2-digit`||t===`short`||t===`long`||t===`narrow`))return t}set month(e){this.setAttribute(`month`,e||``)}get year(){let e=this.getAttribute(`year`);if(e===`numeric`||e===`2-digit`)return e;if(!this.hasAttribute(`year`)&&new Date().getUTCFullYear()!==this.date?.getUTCFullYear())return`numeric`}set year(e){this.setAttribute(`year`,e||``)}get timeZoneName(){let e=this.getAttribute(`time-zone-name`);if(e===`long`||e===`short`||e===`shortOffset`||e===`longOffset`||e===`shortGeneric`||e===`longGeneric`)return e}set timeZoneName(e){this.setAttribute(`time-zone-name`,e||``)}get prefix(){return this.getAttribute(`prefix`)??(this.format===`datetime`?``:`on`)}set prefix(e){this.setAttribute(`prefix`,e)}get threshold(){let e=this.getAttribute(`threshold`);return e&&vs(e)?e:`P30D`}set threshold(e){this.setAttribute(`threshold`,e)}get tense(){let e=this.getAttribute(`tense`);return e===`past`?`past`:e===`future`?`future`:`auto`}set tense(e){this.setAttribute(`tense`,e)}get precision(){let e=this.getAttribute(`precision`);return _s.includes(e)?e:this.format===`micro`?`minute`:`second`}set precision(e){this.setAttribute(`precision`,e)}get format(){let e=this.getAttribute(`format`);return e===`datetime`?`datetime`:e===`relative`?`relative`:e===`duration`?`duration`:e===`micro`?`micro`:e===`elapsed`?`elapsed`:`auto`}set format(e){this.setAttribute(`format`,e)}get formatStyle(){let e=this.getAttribute(`format-style`);if(e===`long`)return`long`;if(e===`short`)return`short`;if(e===`narrow`)return`narrow`;let t=this.format;return t===`elapsed`||t===`micro`?`narrow`:t===`datetime`?`short`:`long`}set formatStyle(e){this.setAttribute(`format-style`,e)}get noTitle(){return this.hasAttribute(`no-title`)}set noTitle(e){this.toggleAttribute(`no-title`,e)}get datetime(){return this.getAttribute(`datetime`)||``}set datetime(e){this.setAttribute(`datetime`,e)}get date(){let e=Date.parse(this.datetime);return Number.isNaN(e)?null:new Date(e)}set date(e){this.datetime=e?.toISOString()||``}connectedCallback(){this.update()}disconnectedCallback(){Ws.unobserve(this)}attributeChangedCallback(e,t,n){t!==n&&(e===`title`&&ws(this,Es,n!==null&&(this.date&&I(this,Ts,`m`,As).call(this,this.date))!==n,`f`),!I(this,Ds,`f`)&&!(e===`title`&&I(this,Es,`f`))&&ws(this,Ds,(async()=>{await Promise.resolve(),this.update(),ws(this,Ds,!1,`f`)})(),`f`))}update(){let e=I(this,ks,`f`).textContent||this.textContent||``,t=this.getAttribute(`title`)||``,n=t,r=this.date;if(typeof Intl>`u`||!Intl.DateTimeFormat||!r){I(this,ks,`f`).textContent=e;return}let i=Date.now();I(this,Es,`f`)||(n=I(this,Ts,`m`,As).call(this,r)||``,n&&!this.noTitle&&this.setAttribute(`title`,n));let a=xs(r,this.precision,i),o=I(this,Ts,`m`,js).call(this,a),s=e,c=I(this,Ts,`m`,Ls).call(this,o);s=c?I(this,Ts,`m`,Fs).call(this,r):o===`duration`?I(this,Ts,`m`,Ms).call(this,a):o===`relative`?I(this,Ts,`m`,Ns).call(this,a):I(this,Ts,`m`,Ps).call(this,r),s?I(this,Ts,`m`,Is).call(this,s):this.shadowRoot===I(this,ks,`f`)&&this.textContent&&I(this,Ts,`m`,Is).call(this,this.textContent),(s!==e||n!==t)&&this.dispatchEvent(new Hs(e,s,t,n)),(o===`relative`||o===`duration`)&&!c?Ws.observe(this):Ws.unobserve(this)}};Es=new WeakMap,Ds=new WeakMap,ks=new WeakMap,Rs=new WeakMap,Ts=new WeakSet,Os=function(){let e=this.closest(`[lang]`)?.getAttribute(`lang`)||this.ownerDocument.documentElement.getAttribute(`lang`);try{return new Intl.Locale(e??``).toString()}catch{return`default`}},As=function(e){return new Intl.DateTimeFormat(I(this,Ts,`a`,Os),{day:`numeric`,month:`short`,year:`numeric`,hour:`numeric`,minute:`2-digit`,timeZoneName:`short`,timeZone:this.timeZone}).format(e)},js=function(e){let t=this.format;if(t===`datetime`)return`datetime`;if(t===`duration`||t===`elapsed`||t===`micro`)return`duration`;if((t===`auto`||t===`relative`)&&typeof Intl<`u`&&Intl.RelativeTimeFormat){let t=this.tense;if(t===`past`||t===`future`||ys.compare(e,this.threshold)===1)return`relative`}return`datetime`},Ms=function(e){let t=I(this,Ts,`a`,Os),n=this.format,r=this.formatStyle,i=this.tense,a=Bs;n===`micro`?(e=Ss(e),a=Vs,e.months===0&&(this.tense===`past`&&e.sign!==-1||this.tense===`future`&&e.sign!==1)&&(e=Vs)):(i===`past`&&e.sign!==-1||i===`future`&&e.sign!==1)&&(e=a);let o=`${this.precision}sDisplay`;return e.blank?a.toLocaleString(t,{style:r,[o]:`always`}):e.abs().toLocaleString(t,{style:r})},Ns=function(e){let t=new Intl.RelativeTimeFormat(I(this,Ts,`a`,Os),{numeric:`auto`,style:this.formatStyle}),n=this.tense;n===`future`&&e.sign!==1&&(e=Bs),n===`past`&&e.sign!==-1&&(e=Bs);let[r,i]=Cs(e);return i===`second`&&r<10?t.format(0,this.precision===`millisecond`?`second`:this.precision):t.format(r,i)},Ps=function(e){let t=new Intl.DateTimeFormat(I(this,Ts,`a`,Os),{second:this.second,minute:this.minute,hour:this.hour,weekday:this.weekday,day:this.day,month:this.month,year:this.year,timeZoneName:this.timeZoneName,timeZone:this.timeZone});return`${this.prefix} ${t.format(e)}`.trim()},Fs=function(e){return new Intl.DateTimeFormat(I(this,Ts,`a`,Os),{day:`numeric`,month:`short`,year:`numeric`,hour:`numeric`,minute:`2-digit`,timeZoneName:`short`,timeZone:this.timeZone}).format(e)},Is=function(e){if(this.hasAttribute(`aria-hidden`)&&this.getAttribute(`aria-hidden`)===`true`){let t=document.createElement(`span`);t.setAttribute(`aria-hidden`,`true`),t.textContent=e,I(this,ks,`f`).replaceChildren(t)}else I(this,ks,`f`).textContent=e},Ls=function(e){return e===`duration`?!1:this.ownerDocument.documentElement.getAttribute(`data-prefers-absolute-time`)===`true`||this.ownerDocument.body?.getAttribute(`data-prefers-absolute-time`)===`true`};var Ks=typeof globalThis<`u`?globalThis:window;try{Ks.RelativeTimeElement=Gs.define()}catch(e){if(!(Ks.DOMException&&e instanceof DOMException&&e.name===`NotSupportedError`)&&!(e instanceof ReferenceError))throw e}var qs=class extends Go{static get styles(){return[...super.styles,h`
        .input-group__input {
          font-family: var(--c-font-mono);
          font-size: 0.9em;
        }
      `]}constructor(){super(),this.autocorrect=!1}firstUpdated(e){super.firstUpdated(e),this._inputNode?.setAttribute(`autocapitalize`,`off`)}};customElements.get(`craft-input-handle`)||customElements.define(`craft-input-handle`,qs),Xe();var Js=class extends Uo{static get styles(){return[...super.styles,xa,h`
        .input-group__container {
          position: relative;
        }

        .input-group__suffix {
          position: absolute;
          inset-inline-end: var(--c-input-spacing-inline);
          inset-block-start: 50%;
          transform: translateY(calc(-50%));
        }
      `]}constructor(){super(),this._visible=!1,this.reveal=()=>{this._visible=!this._visible,this.type=this._visible?`text`:`password`},this.renderSuffix=()=>p`
      <craft-button
        type="button"
        icon
        size="small"
        variant="plain"
        @click="${this.reveal}"
        appearance="plain"
      >
        <span class="icon"
          >${this._visible?p`<craft-icon name="eye-slash"></craft-icon>`:p`<craft-icon name="eye"></craft-icon>`}
        </span>
      </craft-button>
    `,this.type=`password`}get slots(){return{...super.slots,suffix:()=>({template:this.renderSuffix()})}}};d([S()],Js.prototype,`_visible`,void 0),customElements.get(`craft-input-password`)||customElements.define(`craft-input-password`,Js);var Ys=h`
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
`,Xs=class extends b{constructor(...e){super(...e),this.size=``,this.variant=``,this.icon=null}renderPrefix(){return p`<div class="chip__prefix" part="prefix">
      <slot name="prefix">
        ${this.icon?p`<craft-icon name="${this.icon}"></craft-icon>`:y}
      </slot>
    </div>`}render(){let e=!!this.querySelector(`[slot="prefix"]`)||this.icon,t=!!this.querySelector(`[slot="suffix"]`);return p`
      <div
        part="chip"
        class="${D({chip:!0,"chip--small":this.size===`small`,"chip--medium":this.size===`medium`,"chip--large":this.size===`large`,"chip--plain":this.variant===`plain`})}"
      >
        ${e?this.renderPrefix():y}
        <div class="chip__body">
          <slot></slot>
        </div>
        ${t?p`<div class="chip__suffix" part="suffix">
              <slot name="suffix"></slot>
            </div>`:y}
      </div>
    `}};Xs.styles=[Ys],d([x()],Xs.prototype,`size`,void 0),d([x()],Xs.prototype,`variant`,void 0),d([x()],Xs.prototype,`icon`,void 0),customElements.get(`craft-chip`)||customElements.define(`craft-chip`,Xs);var Zs=h`
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
`,Qs=class extends b{constructor(...e){super(...e),this.label=null,this.status=null}getLabel(){return!this.label&&this.status?`Status: ${this.status}`:this.label}render(){return p`
      <span
        class="${D({status:!0,"status--live":this.status===`live`,"status--enabled":this.status===`enabled`,"status--pending":this.status===`pending`,"status--expired":this.status===`expired`,"status--disabled":this.status===`disabled`})}"
        role="img"
        aria-label="${this.getLabel()}"
      ></span>
    `}};Qs.styles=[Zs],d([x()],Qs.prototype,`label`,void 0),d([x()],Qs.prototype,`status`,void 0),customElements.get(`craft-status`)||customElements.define(`craft-status`,Qs);var $s=new Map;function ec(e){var t=$s.get(e);t&&t.destroy()}function tc(e){var t=$s.get(e);t&&t.update()}var nc=null;typeof window>`u`?((nc=function(e){return e}).destroy=function(e){return e},nc.update=function(e){return e}):((nc=function(e,t){return e&&Array.prototype.forEach.call(e.length?e:[e],function(e){return function(e){if(e&&e.nodeName&&e.nodeName===`TEXTAREA`&&!$s.has(e)){var t,n=null,r=window.getComputedStyle(e),i=(t=e.value,function(){o({testForHeightReduction:t===``||!e.value.startsWith(t),restoreTextAlign:null}),t=e.value}),a=function(t){e.removeEventListener(`autosize:destroy`,a),e.removeEventListener(`autosize:update`,s),e.removeEventListener(`input`,i),window.removeEventListener(`resize`,s),Object.keys(t).forEach(function(n){return e.style[n]=t[n]}),$s.delete(e)}.bind(e,{height:e.style.height,resize:e.style.resize,textAlign:e.style.textAlign,overflowY:e.style.overflowY,overflowX:e.style.overflowX,wordWrap:e.style.wordWrap});e.addEventListener(`autosize:destroy`,a),e.addEventListener(`autosize:update`,s),e.addEventListener(`input`,i),window.addEventListener(`resize`,s),e.style.overflowX=`hidden`,e.style.wordWrap=`break-word`,$s.set(e,{destroy:a,update:s}),s()}function o(t){var i,a,s=t.restoreTextAlign,c=s===void 0?null:s,l=t.testForHeightReduction,u=l===void 0||l,d=r.overflowY;if(e.scrollHeight!==0&&(r.resize===`vertical`?e.style.resize=`none`:r.resize===`both`&&(e.style.resize=`horizontal`),u&&(i=function(e){for(var t=[];e&&e.parentNode&&e.parentNode instanceof Element;)e.parentNode.scrollTop&&t.push([e.parentNode,e.parentNode.scrollTop]),e=e.parentNode;return function(){return t.forEach(function(e){var t=e[0],n=e[1];t.style.scrollBehavior=`auto`,t.scrollTop=n,t.style.scrollBehavior=null})}}(e),e.style.height=``),a=r.boxSizing===`content-box`?e.scrollHeight-(parseFloat(r.paddingTop)+parseFloat(r.paddingBottom)):e.scrollHeight+parseFloat(r.borderTopWidth)+parseFloat(r.borderBottomWidth),r.maxHeight!==`none`&&a>parseFloat(r.maxHeight)?(r.overflowY===`hidden`&&(e.style.overflow=`scroll`),a=parseFloat(r.maxHeight)):r.overflowY!==`hidden`&&(e.style.overflow=`hidden`),e.style.height=a+`px`,c&&(e.style.textAlign=c),i&&i(),n!==a&&(e.dispatchEvent(new Event(`autosize:resized`,{bubbles:!0})),n=a),d!==r.overflow&&!c)){var f=r.textAlign;r.overflow===`hidden`&&(e.style.textAlign=f===`start`?`end`:`start`),o({restoreTextAlign:f,testForHeightReduction:!0})}}function s(){o({testForHeightReduction:!0,restoreTextAlign:null})}}(e)}),e}).destroy=function(e){return e&&Array.prototype.forEach.call(e.length?e:[e],ec),e},nc.update=function(e){return e&&Array.prototype.forEach.call(e.length?e:[e],tc),e});var rc=nc,ic=class extends No{get _inputNode(){return Array.from(this.children).find(e=>e.slot===`input`)}},ac=class extends Lo(ic){static get properties(){return{maxRows:{type:Number,attribute:`max-rows`},rows:{type:Number,reflect:!0},readOnly:{type:Boolean,attribute:`readonly`,reflect:!0},placeholder:{type:String,reflect:!0}}}get slots(){return{...super.slots,input:()=>{let e=document.createElement(`textarea`);return e.style.resize!==void 0&&(e.style.resize=`none`),e}}}constructor(){super(),this.rows=2,this.maxRows=6,this.readOnly=!1,this.placeholder=``}connectedCallback(){super.connectedCallback(),this.__initializeAutoresize(),this.__intersectionObserver=new IntersectionObserver(()=>this.resizeTextarea()),this.__intersectionObserver.observe(this)}updated(e){if(super.updated(e),e.has(`name`)&&(this._inputNode.name=this.name),e.has(`autocomplete`)&&(this._inputNode.autocomplete=this.autocomplete),e.has(`disabled`)&&(this._inputNode.disabled=this.disabled,this.validate()),e.has(`rows`)){let e=this._inputNode;e&&(e.rows=this.rows)}if(e.has(`readOnly`)){let e=this._inputNode;e&&(e.readOnly=this.readOnly)}if(e.has(`placeholder`)){let e=this._inputNode;e&&(e.placeholder=this.placeholder)}e.has(`modelValue`)&&this.resizeTextarea(),(e.has(`maxRows`)||e.has(`rows`))&&this.setTextareaMaxHeight()}disconnectedCallback(){super.disconnectedCallback(),rc.destroy(this._inputNode)}setTextareaMaxHeight(){let{value:e}=this._inputNode;this._inputNode.value=``,this.resizeTextarea();let t=window.getComputedStyle(this._inputNode,null),n=parseFloat(t.lineHeight)||parseFloat(t.height)/this.rows,r=parseFloat(t.paddingTop)+parseFloat(t.paddingBottom),i=parseFloat(t.borderTopWidth)+parseFloat(t.borderBottomWidth),a=t.boxSizing===`border-box`?r+i:0;this._inputNode.style.maxHeight=`${n*this.maxRows+a}px`,this._inputNode.value=e,this.resizeTextarea()}static get styles(){return[...super.styles,h`
        .input-group__container > .input-group__input ::slotted(.form-control) {
          box-sizing: content-box;
          overflow-x: hidden; /* for FF adds height to the TextArea to reserve place for scroll-bars */
        }

        /* Workaround https://bugzilla.mozilla.org/show_bug.cgi?id=1739079 */
        :host([disabled]) ::slotted(textarea) {
          user-select: none;
        }
      `]}get updateComplete(){return this.__textareaUpdateComplete?Promise.all([this.__textareaUpdateComplete,super.updateComplete]):super.updateComplete}resizeTextarea(){rc.update(this._inputNode)}__initializeAutoresize(){this.__shady_native_contains?this.__textareaUpdateComplete=this.__waitForTextareaRenderedInRealDOM().then(()=>{this.__startAutoresize(),this.__textareaUpdateComplete=null}):this.__startAutoresize()}async __waitForTextareaRenderedInRealDOM(){let e=3;for(;e!==0&&!this.__shady_native_contains(this._inputNode);)await new Promise(e=>setTimeout(e)),--e}__startAutoresize(){rc(this._inputNode),this.setTextareaMaxHeight()}},oc=h`
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
`,sc=class extends ac{constructor(...e){super(...e),this.monospace=!1}static get styles(){return[...super.styles,xa,oc]}};d([x({type:Boolean,reflect:!0})],sc.prototype,`monospace`,void 0),customElements.get(`craft-textarea`)||customElements.define(`craft-textarea`,sc);var cc=h`
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
`,lc=class extends b{render(){return p`<slot></slot>`}};lc.styles=[cc],customElements.get(`craft-button-group`)||customElements.define(`craft-button-group`,lc);var uc=class extends No{static get properties(){return{autocomplete:{type:String}}}constructor(){super(),this.autocomplete=void 0}get _inputNode(){return Array.from(this.children).find(e=>e.slot===`input`)}},dc=class extends uc{get operationMode(){return`select`}connectedCallback(){super.connectedCallback(),this._inputNode.addEventListener(`change`,this._proxyChangeEvent),this.__selectObserver=new MutationObserver(()=>{this._syncValueUpwards(),this._calculateValues({source:`model`})}),this.__selectObserver.observe(this._inputNode,{attributes:!0,childList:!0,subtree:!0})}updated(e){super.updated(e),e.has(`disabled`)&&(this._inputNode.disabled=this.disabled,this.validate()),e.has(`name`)&&(this._inputNode.name=this.name),e.has(`autocomplete`)&&(this._inputNode.autocomplete=this.autocomplete)}disconnectedCallback(){super.disconnectedCallback(),this._inputNode.removeEventListener(`change`,this._proxyChangeEvent),this.__selectObserver?.disconnect()}formatter(e){let t=Array.from(this._inputNode.options).find(t=>t.value===e);return t?t.text:``}_reflectBackFormattedValueToUser(){this._reflectBackOn()&&(this.value=this.modelValue===void 0?``:this.modelValue)}_proxyChangeEvent(){this.dispatchEvent(new CustomEvent(`user-input-changed`,{bubbles:!0,composed:!0}))}},fc=h`
  ${ba}

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
    ${ya}
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
`,pc=class extends dc{constructor(...e){super(...e),this.small=!1}static get styles(){return[...super.styles,fc]}_inputGroupInputTemplate(){return p`
      <div class="input-group__input">
        <slot name="input"></slot>
        <craft-icon
          class="indicator"
          name="chevron-down"
          style="font-size: 0.8em"
        ></craft-icon>
      </div>
    `}};d([x({reflect:!0,type:Boolean})],pc.prototype,`small`,void 0),customElements.get(`craft-select`)||customElements.define(`craft-select`,pc);var mc=class extends Io(b){static get properties(){return{tabIndex:{type:Number,reflect:!0,attribute:`tabindex`}}}constructor(){super(),this.tabIndex=0}connectedCallback(){super.connectedCallback(),this.setAttribute(`role`,`listbox`)}createRenderRoot(){return this}};function hc(e,t){Array.from(e.childNodes).forEach(e=>{e.hasAttribute&&e.hasAttribute(`slot`)||t.appendChild(e)})}var gc=Or(e=>class extends Eo(ho(Ro(Ur(Fo(e))))){static get properties(){return{orientation:String,selectionFollowsFocus:{type:Boolean,attribute:`selection-follows-focus`},rotateKeyboardNavigation:{type:Boolean,attribute:`rotate-keyboard-navigation`},hasNoDefaultSelected:{type:Boolean,reflect:!0,attribute:`has-no-default-selected`},_noTypeAhead:{type:Boolean}}}static get styles(){return[...super.styles||[],h`
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
        `]}_inputGroupInputTemplate(){return p`
        <div class="input-group__input">
          <slot name="input"></slot>
          <slot id="options-outlet"></slot>
        </div>
      `}static get scopedElements(){return{...super.scopedElements,"lion-options":mc}}get slots(){return{...super.slots,input:()=>{let e=this.createScopedElement(`lion-options`);return e.setAttribute(`data-tag-name`,`lion-options`),e.registrationTarget=this,e}}}get _inputNode(){return this.querySelector(`[slot="input"]`)}get _listboxNode(){return this._inputNode}get _listboxActiveDescendantNode(){return this._listboxNode.querySelector(`#${this._listboxActiveDescendant}`)}get _listboxSlot(){return this.shadowRoot.querySelector(`slot[name=input]`)}get _scrollTargetNode(){return this._listboxNode}get _activeDescendantOwnerNode(){return this._listboxNode}get activeIndex(){return this.formElements.findIndex(e=>e.active===!0)}set activeIndex(e){if(this.formElements[e]){let t=this.formElements[e];this.__setChildActive(t)}else this.__setChildActive(null)}get checkedIndex(){let e=this.formElements;return this.multipleChoice?e.filter(e=>e.checked).map(t=>e.indexOf(t)):e.indexOf(e.find(e=>e.checked))}set checkedIndex(e){this.setCheckedIndex(e)}constructor(){super(),this.hasNoDefaultSelected=!1,this.orientation=`vertical`,this.rotateKeyboardNavigation=!1,this.selectionFollowsFocus=!1,this._noTypeAhead=!1,this._typeAheadTimeout=1e3,this._listboxActiveDescendant=null,this.__hasInitialSelectedFormElement=!1,this._repropagationRole=`choice-group`,this._listboxReceivesNoFocus=!1,this._oldModelValue=void 0,this._listboxOnKeyDown=this._listboxOnKeyDown.bind(this),this._listboxOnClick=this._listboxOnClick.bind(this),this._listboxOnKeyUp=this._listboxOnKeyUp.bind(this),this._onChildActiveChanged=this._onChildActiveChanged.bind(this),this.__proxyChildModelValueChanged=this.__proxyChildModelValueChanged.bind(this),this.__preventScrollingWithArrowKeys=this.__preventScrollingWithArrowKeys.bind(this),this.__typedChars=[]}connectedCallback(){this._listboxNode&&(this._listboxNode.registrationTarget=this),super.connectedCallback(),this._setupListboxNode(),this.__setupEventListeners(),this.registrationComplete.then(()=>{this.__initInteractionStates()})}firstUpdated(e){super.firstUpdated(e),this.__moveOptionsToListboxNode(),this.registrationComplete.then(()=>{this._initialModelValue=this.modelValue}),new MutationObserver(()=>{this._onListboxContentChanged()}).observe(this._listboxNode,{childList:!0})}updated(e){super.updated(e),e.has(`disabled`)&&(this.disabled?this.__requestOptionsToBeDisabled():this.__retractRequestOptionsToBeDisabled())}disconnectedCallback(){super.disconnectedCallback(),this._teardownListboxNode(),this.__teardownEventListeners()}setCheckedIndex(e){if(this.multipleChoice&&Array.isArray(e)){this._uncheckChildren(this.formElements.filter(t=>t===e)),e.forEach(e=>{this.formElements[e]&&(this.formElements[e].checked=!this.formElements[e].checked)});return}typeof e==`number`&&(e===-1&&this._uncheckChildren(),this.formElements[e]&&(this.formElements[e].disabled?this._uncheckChildren():this.multipleChoice?this.formElements[e].checked=!this.formElements[e].checked:this.formElements[e].checked=!0))}addFormElement(e,t){super.addFormElement(e,t),e.id=e.id||`${this.localName}-option-${Kr()}`,this.disabled&&e.makeRequestToBeDisabled(),this.__setAttributeForAllFormElements(`aria-setsize`,this.formElements.length),this.formElements.forEach((e,t)=>{e.setAttribute(`aria-posinset`,t+1)}),this.__proxyChildModelValueChanged({target:e}),this.resetInteractionState()}resetInteractionState(){super.resetInteractionState(),this.submitted=!1}reset(){this.modelValue=this._initialModelValue,this.activeIndex=-1,this.resetInteractionState()}clear(){super.clear(),this.setCheckedIndex(-1),this.resetInteractionState()}_handleTypeAhead(e,{setAsChecked:t}){let{key:n,code:r}=e;if(r.startsWith(`Key`)||r.startsWith(`Digit`)||r.startsWith(`Numpad`)){e.preventDefault(),this.__typedChars.push(n);let r=this.__typedChars.join(``),i=this.formElements.findIndex(e=>e.modelValue.value.toLowerCase().startsWith(r));i>=0&&(t&&this.setCheckedIndex(i),this.activeIndex=i),this.__pendingTypeAheadTimeout&&window.clearTimeout(this.__pendingTypeAheadTimeout),this.__pendingTypeAheadTimeout=setTimeout(()=>{this.__typedChars=[]},this._typeAheadTimeout)}}_getCheckedElements(){return this.formElements.filter(e=>e.checked)}_setupListboxNode(){this._listboxNode?this.__setupListboxNodeInteractions():this._listboxSlot&&this._listboxSlot.addEventListener(`slotchange`,()=>{this.__setupListboxNodeInteractions()})}_onListboxContentChanged(){}_teardownListboxNode(){this._listboxNode&&(this._listboxNode.removeEventListener(`keydown`,this._listboxOnKeyDown),this._listboxNode.removeEventListener(`click`,this._listboxOnClick),this._listboxNode.removeEventListener(`keyup`,this._listboxOnKeyUp))}_getNextEnabledOption(e,t=1){return this.__getEnabledOption(e,t)}_getPreviousEnabledOption(e,t=-1){return this.__getEnabledOption(e,t)}_onChildActiveChanged({target:e}){e.active===!0&&this.__setChildActive(e)}_listboxOnKeyDown(e){if(this.disabled)return;this._isHandlingUserInput=!0,setTimeout(()=>{this._isHandlingUserInput=!1});let{key:t}=e;switch(t){case` `:case`Enter`:if(t===` `&&this._listboxReceivesNoFocus||(t===` `&&e.preventDefault(),!this.formElements[this.activeIndex])||this.formElements[this.activeIndex].disabled)return;this.formElements[this.activeIndex].href&&this.formElements[this.activeIndex].click(),this.setCheckedIndex(this.activeIndex);break;case`ArrowUp`:e.preventDefault(),this.orientation===`vertical`&&(this.activeIndex=this._getPreviousEnabledOption(this.activeIndex));break;case`ArrowLeft`:if(this._listboxReceivesNoFocus)return;e.preventDefault(),this.orientation===`horizontal`&&(this.activeIndex=this._getPreviousEnabledOption(this.activeIndex));break;case`ArrowDown`:e.preventDefault(),this.orientation===`vertical`&&(this.activeIndex=this._getNextEnabledOption(this.activeIndex));break;case`ArrowRight`:if(this._listboxReceivesNoFocus)return;e.preventDefault(),this.orientation===`horizontal`&&(this.activeIndex=this._getNextEnabledOption(this.activeIndex));break;case`Home`:if(this._listboxReceivesNoFocus)return;e.preventDefault(),this.activeIndex=this._getNextEnabledOption(0,0);break;case`End`:if(this._listboxReceivesNoFocus)return;e.preventDefault(),this.activeIndex=this._getPreviousEnabledOption(this.formElements.length-1,0);break;default:this._noTypeAhead||this._handleTypeAhead(e,{setAsChecked:this.selectionFollowsFocus&&!this.multipleChoice})}[`ArrowUp`,`ArrowDown`,`ArrowLeft`,`ArrowRight`,`Home`,`End`].includes(t)&&this.selectionFollowsFocus&&!this.multipleChoice&&this.setCheckedIndex(this.activeIndex)}_listboxOnClick(e){}_listboxOnKeyUp(e){if(this.disabled)return;this._isHandlingUserInput=!0,setTimeout(()=>{this._isHandlingUserInput=!1});let{key:t}=e;switch(t){case`ArrowUp`:case`ArrowDown`:case`Home`:case`End`:case`Enter`:e.preventDefault()}}_onLabelClick(){this._listboxNode.focus()}_scrollIntoView(e,t){e.scrollIntoView({behavior:`smooth`,block:`nearest`})}__setupEventListeners(){this._listboxNode.addEventListener(`active-changed`,this._onChildActiveChanged),this._listboxNode.addEventListener(`model-value-changed`,this.__proxyChildModelValueChanged)}__teardownEventListeners(){this._listboxNode.removeEventListener(`active-changed`,this._onChildActiveChanged),this._listboxNode.removeEventListener(`model-value-changed`,this.__proxyChildModelValueChanged)}__setChildActive(e){if(this.formElements.forEach(t=>{t.active=e===t}),!e){this._activeDescendantOwnerNode.removeAttribute(`aria-activedescendant`);return}this._activeDescendantOwnerNode.setAttribute(`aria-activedescendant`,e.id),this._scrollIntoView(e,this._scrollTargetNode)}_uncheckChildren(e=[]){let t=Array.isArray(e)?e:[e];this.formElements.forEach(e=>{t.includes(e)||(e.checked=!1)})}__onChildCheckedChanged(e){let{target:t}=e;e.stopPropagation&&e.stopPropagation(),t.checked&&!this.multipleChoice&&this._uncheckChildren(t)}__setAttributeForAllFormElements(e,t){this.formElements.forEach(n=>{n.setAttribute(e,t)})}__proxyChildModelValueChanged(e){e.stopPropagation&&e.stopPropagation(),this.__onChildCheckedChanged(e),this.requestUpdate(`modelValue`,this._oldModelValue),e.detail&&e.detail.formPath&&this.dispatchEvent(new CustomEvent(`model-value-changed`,{detail:{formPath:e.detail.formPath,isTriggeredByUser:e.detail.isTriggeredByUser||this._isHandlingUserInput,element:e.target}})),this._oldModelValue=this.modelValue}__getEnabledOption(e,t){let n=e=>t===1?e<this.formElements.length:e>=0;for(let r=e+t;n(r);r+=t)if(this.formElements[r]&&!this.formElements[r].hasAttribute(`aria-hidden`))return r;if(this.rotateKeyboardNavigation){let e=t===-1?this.formElements.length-1:0;for(let r=e;n(r);r+=t)if(this.formElements[r]&&!this.formElements[r].hasAttribute(`aria-hidden`))return r}return e}__moveOptionsToListboxNode(){let e=this.shadowRoot.getElementById(`options-outlet`);e&&(hc(this,this._listboxNode),e.addEventListener(`slotchange`,()=>{hc(this,this._listboxNode)}))}__preventScrollingWithArrowKeys(e){if(this.disabled)return;let{key:t}=e;switch(t){case`ArrowUp`:case`ArrowDown`:case`Home`:case`End`:e.preventDefault()}}__setupListboxNodeInteractions(){this._listboxNode.setAttribute(`role`,`listbox`),this._listboxNode.setAttribute(`aria-orientation`,this.orientation),this._listboxNode.setAttribute(`aria-multiselectable`,`${this.multipleChoice}`),this._listboxNode.setAttribute(`tabindex`,`0`),this._listboxNode.addEventListener(`click`,this._listboxOnClick),this._listboxNode.addEventListener(`keyup`,this._listboxOnKeyUp),this._listboxNode.addEventListener(`keydown`,this._listboxOnKeyDown),this._scrollTargetNode.addEventListener(`keydown`,this.__preventScrollingWithArrowKeys)}__requestOptionsToBeDisabled(){this.formElements.forEach(e=>{e.makeRequestToBeDisabled&&e.makeRequestToBeDisabled()})}__retractRequestOptionsToBeDisabled(){this.formElements.forEach(e=>{e.retractRequestToBeDisabled&&e.retractRequestToBeDisabled()})}__initInteractionStates(){this.initInteractionState()}}),_c=class extends gc(Ta(Mo(Ao(b)))){get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,focused:this.focused}}get _focusableNode(){return this._inputNode}},vc=class extends kr(Bo(To(Ur(b)))){static get properties(){return{active:{type:Boolean,reflect:!0}}}static get styles(){return[h`
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
      `]}get slots(){return{}}constructor(){super(),this.active=!1,this.__onClick=this.__onClick.bind(this),this.__registerEventListeners()}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`active`&&this.active!==t&&this.dispatchEvent(new Event(`active-changed`,{bubbles:!0}))}updated(e){super.updated(e),e.has(`checked`)&&this.setAttribute(`aria-selected`,`${this.checked}`),e.has(`disabled`)&&this.setAttribute(`aria-disabled`,`${this.disabled}`)}render(){return p`
      <div class="choice-field__label">
        <slot></slot>
      </div>
    `}connectedCallback(){super.connectedCallback(),this.setAttribute(`role`,`option`)}__registerEventListeners(){this.addEventListener(`click`,this.__onClick)}__unRegisterEventListeners(){this.removeEventListener(`click`,this.__onClick)}__onClick(){if(this.disabled)return;let e=this._parentFormGroup;this._isHandlingUserInput=!0,e&&e.multipleChoice?(this.checked=!this.checked,this.active=!this.active):(this.checked=!0,this.active=!0),this._isHandlingUserInput=!1}},yc=h`
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
`,bc=new WeakMap,xc=class extends vc{static get styles(){return[...vc.styles,yc]}constructor(){super(),this.hint=null,u(this,bc,640),a(bc,this,parseInt(getComputedStyle(this).getPropertyValue(`--c-option-wide-threshold`)||`640`,10))}connectedCallback(){super.connectedCallback();let e=this.getBoundingClientRect().width??0;this.toggleAttribute(`wide`,e>=l(bc,this))}render(){return p`
      <div class="choice-field__label">
        <slot></slot>
        ${this.hint?p`<span class="hint">${this.hint}</span>`:y}
        <slot name="suffix"></slot>
      </div>
    `}};d([x()],xc.prototype,`hint`,void 0),customElements.get(`craft-option`)||customElements.define(`craft-option`,xc);var Sc=`@layer wa-utilities {
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
`,Cc=class extends Event{constructor(e){super(`wa-select`,{bubbles:!0,cancelable:!0,composed:!0}),this.detail=e}};function*wc(e=document.activeElement){e!=null&&(yield e,`shadowRoot`in e&&e.shadowRoot&&e.shadowRoot.mode!==`closed`&&(yield*wc(e.shadowRoot.activeElement)))}var Tc=`:host {
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
`,Ec=new Set,Dc=class extends rt{constructor(){super(...arguments),this.submenuCleanups=new Map,this.localize=new Oe(this),this.userTypedQuery=``,this.openSubmenuStack=[],this.open=!1,this.size=`medium`,this.placement=`bottom-start`,this.distance=0,this.skidding=0,this.handleDocumentKeyDown=async e=>{let t=this.localize.dir()===`rtl`;if(e.key===`Escape`){let t=this.getTrigger();e.preventDefault(),e.stopPropagation(),this.open=!1,t?.focus();return}let n=[...wc()].find(e=>e.localName===`wa-dropdown-item`),r=n?.localName===`wa-dropdown-item`,i=this.getCurrentSubmenuItem(),a=!!i,o,s,c;a?(o=this.getSubmenuItems(i),s=o.find(e=>e.active||e===n),c=s?o.indexOf(s):-1):(o=this.getItems(),s=o.find(e=>e.active||e===n),c=s?o.indexOf(s):-1);let l;if(e.key===`ArrowUp`&&(e.preventDefault(),e.stopPropagation(),l=c>0?o[c-1]:o[o.length-1]),e.key===`ArrowDown`&&(e.preventDefault(),e.stopPropagation(),l=c!==-1&&c<o.length-1?o[c+1]:o[0]),e.key===(t?`ArrowLeft`:`ArrowRight`)&&r&&s&&s.hasSubmenu){e.preventDefault(),e.stopPropagation(),s.submenuOpen=!0,this.addToSubmenuStack(s),setTimeout(()=>{let e=this.getSubmenuItems(s);e.length>0&&(e.forEach((e,t)=>e.active=t===0),e[0].focus())},0);return}if(e.key===(t?`ArrowRight`:`ArrowLeft`)&&a){e.preventDefault(),e.stopPropagation();let t=this.removeFromSubmenuStack();t&&(t.submenuOpen=!1,setTimeout(()=>{t.focus(),t.active=!0,(t.slot===`submenu`?this.getSubmenuItems(t.parentElement):this.getItems()).forEach(e=>{e!==t&&(e.active=!1)})},0));return}if((e.key===`Home`||e.key===`End`)&&(e.preventDefault(),e.stopPropagation(),l=e.key===`Home`?o[0]:o[o.length-1]),e.key===`Tab`&&await this.hideMenu(),e.key.length===1&&!(e.metaKey||e.ctrlKey||e.altKey)&&!(e.key===` `&&this.userTypedQuery===``)&&(clearTimeout(this.userTypedTimeout),this.userTypedTimeout=setTimeout(()=>{this.userTypedQuery=``},1e3),this.userTypedQuery+=e.key,o.some(e=>{let t=(e.textContent||``).trim().toLowerCase(),n=this.userTypedQuery.trim().toLowerCase();return t.startsWith(n)?(l=e,!0):!1})),l){e.preventDefault(),e.stopPropagation(),o.forEach(e=>e.active=e===l),l.focus();return}(e.key===`Enter`||e.key===` `&&this.userTypedQuery===``)&&r&&s&&(e.preventDefault(),e.stopPropagation(),s.hasSubmenu?(s.submenuOpen=!0,this.addToSubmenuStack(s),setTimeout(()=>{let e=this.getSubmenuItems(s);e.length>0&&(e.forEach((e,t)=>e.active=t===0),e[0].focus())},0)):this.makeSelection(s))},this.handleDocumentPointerDown=e=>{e.composedPath().some(e=>e instanceof HTMLElement?e===this||e.closest(`wa-dropdown, [part="submenu"]`):!1)||(this.open=!1)},this.handleGlobalMouseMove=e=>{let t=this.getCurrentSubmenuItem();if(!t?.submenuOpen||!t.submenuElement)return;let n=t.submenuElement.getBoundingClientRect(),r=this.localize.dir()===`rtl`,i=r?n.right:n.left,a=r?Math.max(e.clientX,i):Math.min(e.clientX,i),o=Math.max(n.top,Math.min(e.clientY,n.bottom));t.submenuElement.style.setProperty(`--safe-triangle-cursor-x`,`${a}px`),t.submenuElement.style.setProperty(`--safe-triangle-cursor-y`,`${o}px`);let s=t.matches(`:hover`),c=t.submenuElement?.matches(`:hover`)||!!e.composedPath().find(e=>e instanceof HTMLElement&&e.closest(`[part="submenu"]`)===t.submenuElement);!s&&!c&&setTimeout(()=>{!t.matches(`:hover`)&&!t.submenuElement?.matches(`:hover`)&&(t.submenuOpen=!1)},100)}}disconnectedCallback(){super.disconnectedCallback(),clearInterval(this.userTypedTimeout),this.closeAllSubmenus(),this.submenuCleanups.forEach(e=>e()),this.submenuCleanups.clear(),document.removeEventListener(`mousemove`,this.handleGlobalMouseMove)}firstUpdated(){this.syncAriaAttributes()}async updated(e){e.has(`open`)&&(this.customStates.set(`open`,this.open),this.open?await this.showMenu():(this.closeAllSubmenus(),await this.hideMenu())),e.has(`size`)&&this.syncItemSizes()}getItems(e=!1){let t=this.defaultSlot.assignedElements({flatten:!0}).filter(e=>e.localName===`wa-dropdown-item`);return e?t:t.filter(e=>!e.disabled)}getSubmenuItems(e,t=!1){let n=e.shadowRoot?.querySelector(`slot[name="submenu"]`)||e.querySelector(`slot[name="submenu"]`);if(!n)return[];let r=n.assignedElements({flatten:!0}).filter(e=>e.localName===`wa-dropdown-item`);return t?r:r.filter(e=>!e.disabled)}syncItemSizes(){this.defaultSlot.assignedElements({flatten:!0}).filter(e=>e.localName===`wa-dropdown-item`).forEach(e=>e.size=this.size)}addToSubmenuStack(e){let t=this.openSubmenuStack.indexOf(e);t===-1?this.openSubmenuStack.push(e):this.openSubmenuStack=this.openSubmenuStack.slice(0,t+1)}removeFromSubmenuStack(){return this.openSubmenuStack.pop()}getCurrentSubmenuItem(){return this.openSubmenuStack.length>0?this.openSubmenuStack[this.openSubmenuStack.length-1]:void 0}closeAllSubmenus(){this.getItems(!0).forEach(e=>{e.submenuOpen=!1}),this.openSubmenuStack=[]}closeSiblingSubmenus(e){let t=e.closest(`wa-dropdown-item:not([slot="submenu"])`),n;n=t?this.getSubmenuItems(t,!0):this.getItems(!0),n.forEach(t=>{t!==e&&t.submenuOpen&&(t.submenuOpen=!1)}),this.openSubmenuStack.includes(e)||this.openSubmenuStack.push(e)}getTrigger(){return this.querySelector(`[slot="trigger"]`)}async showMenu(){if(!this.getTrigger())return;let e=new ur;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}Ec.forEach(e=>e.open=!1),this.popup.active=!0,this.open=!0,Ec.add(this),this.syncAriaAttributes(),document.addEventListener(`keydown`,this.handleDocumentKeyDown),document.addEventListener(`pointerdown`,this.handleDocumentPointerDown),document.addEventListener(`mousemove`,this.handleGlobalMouseMove),this.menu.classList.remove(`hide`),await hr(this.menu,`show`);let t=this.getItems();t.length>0&&(t.forEach((e,t)=>e.active=t===0),t[0].focus()),this.dispatchEvent(new cr)}async hideMenu(){let e=new lr({source:this});if(this.dispatchEvent(e),e.defaultPrevented){this.open=!0;return}this.open=!1,Ec.delete(this),this.syncAriaAttributes(),document.removeEventListener(`keydown`,this.handleDocumentKeyDown),document.removeEventListener(`pointerdown`,this.handleDocumentPointerDown),document.removeEventListener(`mousemove`,this.handleGlobalMouseMove),this.menu.classList.remove(`show`),await hr(this.menu,`hide`),this.popup.active=this.open,this.dispatchEvent(new sr)}handleMenuClick(e){let t=e.target.closest(`wa-dropdown-item`);if(!(!t||t.disabled)){if(t.hasSubmenu){t.submenuOpen||=(this.closeSiblingSubmenus(t),this.addToSubmenuStack(t),!0),e.stopPropagation();return}this.makeSelection(t)}}async handleMenuSlotChange(){let e=this.getItems(!0);await Promise.all(e.map(e=>e.updateComplete)),this.syncItemSizes();let t=e.some(e=>e.type===`checkbox`),n=e.some(e=>e.hasSubmenu);e.forEach((e,r)=>{e.active=r===0,e.checkboxAdjacent=t,e.submenuAdjacent=n})}handleTriggerClick(){this.open=!this.open}handleSubmenuOpening(e){let t=e.detail.item;this.closeSiblingSubmenus(t),this.addToSubmenuStack(t),this.setupSubmenuPosition(t),this.processSubmenuItems(t)}setupSubmenuPosition(e){if(!e.submenuElement)return;this.cleanupSubmenuPosition(e);let t=Jn(e,e.submenuElement,()=>{this.positionSubmenu(e),this.updateSafeTriangleCoordinates(e)});this.submenuCleanups.set(e,t);let n=e.submenuElement.querySelector(`slot[name="submenu"]`);n&&(n.removeEventListener(`slotchange`,Dc.handleSubmenuSlotChange),n.addEventListener(`slotchange`,Dc.handleSubmenuSlotChange),Dc.handleSubmenuSlotChange({target:n}))}static handleSubmenuSlotChange(e){let t=e.target;if(!t)return;let n=t.assignedElements().filter(e=>e.localName===`wa-dropdown-item`);if(n.length===0)return;let r=n.some(e=>e.hasSubmenu),i=n.some(e=>e.type===`checkbox`);n.forEach(e=>{e.submenuAdjacent=r,e.checkboxAdjacent=i})}processSubmenuItems(e){if(!e.submenuElement)return;let t=this.getSubmenuItems(e,!0),n=t.some(e=>e.hasSubmenu);t.forEach(e=>{e.submenuAdjacent=n})}cleanupSubmenuPosition(e){let t=this.submenuCleanups.get(e);t&&(t(),this.submenuCleanups.delete(e))}positionSubmenu(e){if(!e.submenuElement)return;let t=this.localize.dir()===`rtl`?`left-start`:`right-start`;er(e,e.submenuElement,{placement:t,middleware:[Yn({mainAxis:0,crossAxis:-5}),Zn({fallbackStrategy:`bestFit`}),Xn({padding:8})]}).then(({x:t,y:n,placement:r})=>{e.submenuElement.setAttribute(`data-placement`,r),Object.assign(e.submenuElement.style,{left:`${t}px`,top:`${n}px`})})}updateSafeTriangleCoordinates(e){if(!e.submenuElement||!e.submenuOpen)return;if(document.activeElement?.matches(`:focus-visible`)){e.submenuElement.style.setProperty(`--safe-triangle-visible`,`none`);return}e.submenuElement.style.setProperty(`--safe-triangle-visible`,`block`);let t=e.submenuElement.getBoundingClientRect(),n=this.localize.dir()===`rtl`;e.submenuElement.style.setProperty(`--safe-triangle-submenu-start-x`,`${n?t.right:t.left}px`),e.submenuElement.style.setProperty(`--safe-triangle-submenu-start-y`,`${t.top}px`),e.submenuElement.style.setProperty(`--safe-triangle-submenu-end-x`,`${n?t.right:t.left}px`),e.submenuElement.style.setProperty(`--safe-triangle-submenu-end-y`,`${t.bottom}px`)}makeSelection(e){let t=this.getTrigger();if(e.disabled)return;e.type===`checkbox`&&(e.checked=!e.checked);let n=new Cc({item:e});this.dispatchEvent(n),n.defaultPrevented||(this.open=!1,t?.focus())}async syncAriaAttributes(){let e=this.getTrigger(),t;e&&(e.localName===`wa-button`?(await customElements.whenDefined(`wa-button`),await e.updateComplete,t=e.shadowRoot.querySelector(`[part="base"]`)):t=e,t.hasAttribute(`id`)||t.setAttribute(`id`,pr(`wa-dropdown-trigger-`)),t.setAttribute(`aria-haspopup`,`menu`),t.setAttribute(`aria-expanded`,this.open?`true`:`false`),this.menu.setAttribute(`aria-expanded`,`false`))}render(){let e=this.hasUpdated?this.popup.active:this.open;return p`
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
    `}};Dc.css=[Sc,Tc],k([w(`slot:not([name])`)],Dc.prototype,`defaultSlot`,2),k([w(`#menu`)],Dc.prototype,`menu`,2),k([w(`wa-popup`)],Dc.prototype,`popup`,2),k([x({type:Boolean,reflect:!0})],Dc.prototype,`open`,2),k([x({reflect:!0})],Dc.prototype,`size`,2),k([x({reflect:!0})],Dc.prototype,`placement`,2),k([x({type:Number})],Dc.prototype,`distance`,2),k([x({type:Number})],Dc.prototype,`skidding`,2),Dc=k([C(`wa-dropdown`)],Dc);var Oc=class{constructor(e,...t){this.slotNames=[],this.handleSlotChange=e=>{let t=e.target;(this.slotNames.includes(`[default]`)&&!t.name||t.name&&this.slotNames.includes(t.name))&&this.host.requestUpdate()},(this.host=e).addController(this),this.slotNames=t}hasDefaultSlot(){return[...this.host.childNodes].some(e=>{if(e.nodeType===Node.TEXT_NODE&&e.textContent.trim()!==``)return!0;if(e.nodeType===Node.ELEMENT_NODE){let t=e;if(t.tagName.toLowerCase()===`wa-visually-hidden`)return!1;if(!t.hasAttribute(`slot`))return!0}return!1})}hasNamedSlot(e){return this.host.querySelector(`:scope > [slot="${e}"]`)!==null}test(e){return e===`[default]`?this.hasDefaultSlot():this.hasNamedSlot(e)}hostConnected(){this.host.shadowRoot.addEventListener(`slotchange`,this.handleSlotChange)}hostDisconnected(){this.host.shadowRoot.removeEventListener(`slotchange`,this.handleSlotChange)}},kc=`:host {
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
`,Ac=class extends rt{constructor(){super(...arguments),this.hasSlotController=new Oc(this,`[default]`,`start`,`end`),this.active=!1,this.variant=`default`,this.size=`medium`,this.checkboxAdjacent=!1,this.submenuAdjacent=!1,this.type=`normal`,this.checked=!1,this.disabled=!1,this.submenuOpen=!1,this.hasSubmenu=!1,this.handleSlotChange=()=>{this.hasSubmenu=this.hasSlotController.test(`submenu`),this.updateHasSubmenuState(),this.hasSubmenu?(this.setAttribute(`aria-haspopup`,`menu`),this.setAttribute(`aria-expanded`,this.submenuOpen?`true`:`false`)):(this.removeAttribute(`aria-haspopup`),this.removeAttribute(`aria-expanded`))}}connectedCallback(){super.connectedCallback(),this.addEventListener(`mouseenter`,this.handleMouseEnter.bind(this)),this.shadowRoot.addEventListener(`slotchange`,this.handleSlotChange)}disconnectedCallback(){super.disconnectedCallback(),this.closeSubmenu(),this.removeEventListener(`mouseenter`,this.handleMouseEnter),this.shadowRoot.removeEventListener(`slotchange`,this.handleSlotChange)}firstUpdated(){this.setAttribute(`tabindex`,`-1`),this.hasSubmenu=this.hasSlotController.test(`submenu`),this.updateHasSubmenuState()}updated(e){e.has(`active`)&&(this.setAttribute(`tabindex`,this.active?`0`:`-1`),this.customStates.set(`active`,this.active)),e.has(`checked`)&&(this.setAttribute(`aria-checked`,this.checked?`true`:`false`),this.customStates.set(`checked`,this.checked)),e.has(`disabled`)&&(this.setAttribute(`aria-disabled`,this.disabled?`true`:`false`),this.customStates.set(`disabled`,this.disabled)),e.has(`type`)&&(this.type===`checkbox`?this.setAttribute(`role`,`menuitemcheckbox`):this.setAttribute(`role`,`menuitem`)),e.has(`submenuOpen`)&&(this.customStates.set(`submenu-open`,this.submenuOpen),this.submenuOpen?this.openSubmenu():this.closeSubmenu())}updateHasSubmenuState(){this.customStates.set(`has-submenu`,this.hasSubmenu)}async openSubmenu(){!this.hasSubmenu||!this.submenuElement||(this.notifyParentOfOpening(),this.submenuElement.showPopover(),this.submenuElement.hidden=!1,this.submenuElement.setAttribute(`data-visible`,``),this.submenuOpen=!0,this.setAttribute(`aria-expanded`,`true`),await hr(this.submenuElement,`show`),setTimeout(()=>{let e=this.getSubmenuItems();e.length>0&&(e.forEach((e,t)=>e.active=t===0),e[0].focus())},0))}notifyParentOfOpening(){let e=new CustomEvent(`submenu-opening`,{bubbles:!0,composed:!0,detail:{item:this}});this.dispatchEvent(e);let t=this.parentElement;t&&[...t.children].filter(e=>e!==this&&e.localName===`wa-dropdown-item`&&e.getAttribute(`slot`)===this.getAttribute(`slot`)&&e.submenuOpen).forEach(e=>{e.submenuOpen=!1})}async closeSubmenu(){!this.hasSubmenu||!this.submenuElement||(this.submenuOpen=!1,this.setAttribute(`aria-expanded`,`false`),this.submenuElement.hidden||(await hr(this.submenuElement,`hide`),this.submenuElement.hidden=!0,this.submenuElement.removeAttribute(`data-visible`),this.submenuElement.hidePopover()))}getSubmenuItems(){return[...this.children].filter(e=>e.localName===`wa-dropdown-item`&&e.getAttribute(`slot`)===`submenu`&&!e.hasAttribute(`disabled`))}handleMouseEnter(){this.hasSubmenu&&!this.disabled&&(this.notifyParentOfOpening(),this.submenuOpen=!0)}render(){return p`
      ${this.type===`checkbox`?p`
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

      ${this.hasSubmenu?p`
            <wa-icon
              id="submenu-indicator"
              part="submenu-icon"
              exportparts="svg:submenu-icon__svg"
              library="system"
              name="chevron-right"
            ></wa-icon>
          `:``}
      ${this.hasSubmenu?p`
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
    `}};Ac.css=kc,k([w(`#submenu`)],Ac.prototype,`submenuElement`,2),k([x({type:Boolean})],Ac.prototype,`active`,2),k([x({reflect:!0})],Ac.prototype,`variant`,2),k([x({reflect:!0})],Ac.prototype,`size`,2),k([x({attribute:`checkbox-adjacent`,type:Boolean,reflect:!0})],Ac.prototype,`checkboxAdjacent`,2),k([x({attribute:`submenu-adjacent`,type:Boolean,reflect:!0})],Ac.prototype,`submenuAdjacent`,2),k([x()],Ac.prototype,`value`,2),k([x({reflect:!0})],Ac.prototype,`type`,2),k([x({type:Boolean})],Ac.prototype,`checked`,2),k([x({type:Boolean,reflect:!0})],Ac.prototype,`disabled`,2),k([x({type:Boolean,reflect:!0})],Ac.prototype,`submenuOpen`,2),k([S()],Ac.prototype,`hasSubmenu`,2),Ac=k([C(`wa-dropdown-item`)],Ac);var jc=class extends Dc{static get styles(){return[Dc.styles,h`
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
      `]}},Mc=class extends Ac{static get styles(){return[Ac.styles,h`
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
      `]}};customElements.get(`craft-dropdown`)||customElements.define(`craft-dropdown`,jc),customElements.get(`craft-dropdown-item`)||customElements.define(`craft-dropdown-item`,Mc);function Nc({el:e,uid:t}){e.setAttribute(`id`,`panel-${t}`),e.setAttribute(`role`,`tabpanel`),e.setAttribute(`aria-labelledby`,`button-${t}`),e.hasAttribute(`tabindex`)||e.setAttribute(`tabindex`,`0`)}function Pc(e){e.setAttribute(`selected`,`true`)}function Fc(e){e.removeAttribute(`selected`)}function Ic({el:e,uid:t,clickHandler:n,keydownHandler:r,keyupHandler:i}){e.setAttribute(`id`,`button-${t}`),e.setAttribute(`role`,`tab`),e.setAttribute(`aria-controls`,`panel-${t}`),e.addEventListener(`click`,n),e.addEventListener(`keyup`,i),e.addEventListener(`keydown`,r)}function Lc({el:e,clickHandler:t,keydownHandler:n,keyupHandler:r}){e.removeAttribute(`id`),e.removeAttribute(`role`),e.removeAttribute(`aria-controls`),e.removeEventListener(`click`,t),e.removeEventListener(`keyup`,r),e.removeEventListener(`keydown`,n)}function Rc(e,t=!1){t&&e.focus(),e.setAttribute(`selected`,`true`),e.setAttribute(`aria-selected`,`true`),e.setAttribute(`tabindex`,`0`)}function zc(e){e.removeAttribute(`selected`),e.setAttribute(`aria-selected`,`false`),e.setAttribute(`tabindex`,`-1`)}function Bc(e){let t=e;switch(t.key){case`ArrowDown`:case`ArrowRight`:case`ArrowUp`:case`ArrowLeft`:case`Home`:case`End`:t.preventDefault()}}var Vc=class extends b{static get properties(){return{selectedIndex:{type:Number,attribute:`selected-index`,reflect:!0}}}static get styles(){return[h`
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
      `]}render(){return p`
      <div class="tabs__tab-group" role="tablist">
        <slot name="tab"></slot>
      </div>
      <div class="tabs__panels">
        <slot name="panel"></slot>
      </div>
    `}constructor(){super(),this.selectedIndex=0}firstUpdated(e){super.firstUpdated(e),this.__setupSlots(),this.tabs[0]?.disabled&&(this.selectedIndex=this.tabs.findIndex(e=>!e.disabled))}get tabs(){return Array.from(this.children).filter(e=>e.slot===`tab`)}get panels(){return Array.from(this.children).filter(e=>e.slot===`panel`)}static enabledWarnings=super.enabledWarnings?.filter(e=>e!==`change-in-update`)||[];__setupSlots(){if(this.shadowRoot){let e=this.shadowRoot.querySelector(`slot[name=tab]`);e&&e.addEventListener(`slotchange`,()=>{this.__cleanStore(),this.__setupStore(),this.__updateSelected(!1)})}}__setupStore(){this.__store=[],this.tabs.length!==this.panels.length&&console.warn(`The amount of tabs (${this.tabs.length}) doesn't match the amount of panels (${this.panels.length}).`),this.tabs.forEach((e,t)=>{let n={uid:Kr(),el:e,button:e,panel:this.panels[t],clickHandler:this.__createButtonClickHandler(t),keydownHandler:Bc.bind(this),keyupHandler:this.__handleButtonKeyup.bind(this)};Nc({...n,el:n.panel}),Ic(n),Fc(n.panel),zc(n.button),this.__store&&this.__store.push(n)})}__cleanStore(){this.__store&&=(this.__store.forEach(e=>{Lc(e)}),[])}__getNextNotDisabledTab(e,t,n){let r=[],i=e.filter((e,t)=>!e.disabled&&t>this.selectedIndex),a=e.filter((e,t)=>!e.disabled&&t<this.selectedIndex);return r=n===`right`?[...i,...a]:[...a.reverse(),...i.reverse()],r[0]}__getNextAvailableIndex(e,t){let n=this.tabs[this.selectedIndex];if(this.tabs.every(e=>!e.disabled))return e;if(t===`ArrowRight`||t===`ArrowDown`){let e=this.__getNextNotDisabledTab(this.tabs,n,`right`);return this.tabs.findIndex(t=>e===t)}if(t===`ArrowLeft`||t===`ArrowUp`){let e=this.__getNextNotDisabledTab(this.tabs,n,`left`);return this.tabs.findIndex(t=>e===t)}if(t===`Home`)return this.tabs.findIndex(e=>!e.disabled);if(t===`End`){let e=this.tabs.map((e,t)=>({disabled:e.disabled,index:t})).filter(e=>!e.disabled);return e[e.length-1].index}return-1}__createButtonClickHandler(e){return()=>{this._setSelectedIndexWithFocus(e)}}__handleButtonKeyup(e){let t=e;if(typeof this.selectedIndex==`number`)switch(t.key){case`ArrowDown`:case`ArrowRight`:this.selectedIndex+1>=this._pairCount?this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(0,t.key)):this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this.selectedIndex+1,t.key));break;case`ArrowUp`:case`ArrowLeft`:this.selectedIndex<=0?this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this._pairCount-1,t.key)):this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this.selectedIndex-1,t.key));break;case`Home`:this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(0,t.key));break;case`End`:this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this._pairCount-1,t.key));break}}get selectedIndex(){return this.__selectedIndex||0}set selectedIndex(e){if(e===this.__selectedIndex)return;let t=this.__selectedIndex;this.__selectedIndex=e,this.__updateSelected(!1),this.dispatchEvent(new Event(`selected-changed`)),this.requestUpdate(`selectedIndex`,t)}_setSelectedIndexWithFocus(e){if(e===-1)return;let t=this.__selectedIndex;this.__selectedIndex=e,this.__updateSelected(!0),this.dispatchEvent(new Event(`selected-changed`)),this.requestUpdate(`selectedIndex`,t)}get _pairCount(){return this.__store&&this.__store.length||0}__updateSelected(e=!1){if(!(this.__store&&typeof this.selectedIndex==`number`&&this.__store[this.selectedIndex]))return;let t=this.tabs.find(e=>e.hasAttribute(`selected`)),n=this.panels.find(e=>e.hasAttribute(`selected`));t&&zc(t),n&&Fc(n);let{button:r,panel:i}=this.__store[this.selectedIndex];r&&Rc(r,e),i&&Pc(i)}},Hc=h`
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
`,Uc=class extends Vc{static get styles(){return[...super.styles,Hc]}};customElements.get(`craft-tabs`)||customElements.define(`craft-tabs`,Uc);var Wc=h`
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
`,Gc=class extends b{constructor(...e){super(...e),this.label=``}render(){let e=!!this.label||!!this.querySelector(`[slot="header"]`)||!!this.querySelector(`[slot="label"]`)||!!this.querySelector(`[slot="actions"]`),t=!!this.querySelector(`[slot="footer"]`);return p`
      <div class="card">
        <div>
          ${e?p`<div class="card__header">
                <slot name="header">
                  <slot name="label" class="card__label" part="label"
                    >${this.label}</slot
                  >
                  <slot name="actions"></slot>
                </slot>
              </div>`:y}

          <div class="card__body">
            <slot></slot>
          </div>

          ${t?p`<div class="card__footer"><slot name="footer"></slot></div>`:y}
        </div>
      </div>
    `}};Gc.styles=[Wc],d([x()],Gc.prototype,`label`,void 0),customElements.get(`craft-card`)||customElements.define(`craft-card`,Gc);var Kc=h`
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
`,qc=class extends b{render(){return p`<slot></slot> `}};qc.styles=[Kc],customElements.get(`craft-tab`)||customElements.define(`craft-tab`,qc);var Jc=class extends Ar(b){static get properties(){return{checked:{type:Boolean,reflect:!0}}}static get styles(){return[h`
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
      `]}render(){return p`
      <div class="btn">
        <div class="switch-button__track"></div>
        <div class="switch-button__thumb"></div>
      </div>
    `}constructor(){super(),this.value=``,this.checked=!1,this.__initialized=!1,this._toggleChecked=this._toggleChecked.bind(this),this.__handleKeydown=this._handleKeydown.bind(this),this.__handleKeyup=this._handleKeyup.bind(this)}connectedCallback(){super.connectedCallback(),this.setAttribute(`role`,`switch`),this.setAttribute(`aria-checked`,`${this.checked}`),this.addEventListener(`click`,this._toggleChecked),this.addEventListener(`keydown`,this.__handleKeydown),this.addEventListener(`keyup`,this.__handleKeyup)}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener(`click`,this._toggleChecked),this.removeEventListener(`keydown`,this.__handleKeydown),this.removeEventListener(`keyup`,this.__handleKeyup)}_toggleChecked(){this.disabled||(this.focus(),this.checked=!this.checked)}__checkedStateChange(){this.dispatchEvent(new Event(`checked-changed`,{bubbles:!0})),this.setAttribute(`aria-checked`,`${this.checked}`)}_handleKeydown(e){e.key===` `&&e.preventDefault()}_handleKeyup(e){[` `,`Enter`].includes(e.key)&&this._toggleChecked()}updated(e){super.updated(e),e.has(`disabled`)&&this.setAttribute(`aria-disabled`,`${this.disabled}`)}requestUpdate(e,t,n){super.requestUpdate(e,t,n),this.__initialized&&this.isConnected&&e===`checked`&&this.checked!==t&&!this.disabled&&this.__checkedStateChange()}firstUpdated(e){super.firstUpdated(e),this.__initialized=!0}},Yc=class extends ho(Bo(No)){static get styles(){return[...super.styles,h`
        :host([hidden]) {
          display: none;
        }

        :host([disabled]) {
          color: #adadad;
        }
      `]}static get scopedElements(){return{...super.scopedElements,"lion-switch-button":Jc}}get _inputNode(){return Array.from(this.children).find(e=>e.slot===`input`)}get slots(){return{...super.slots,input:()=>{let e=this.createScopedElement(`lion-switch-button`);return e.setAttribute(`data-tag-name`,`lion-switch-button`),e}}}render(){return p`
      <div class="form-field__group-one">${this._groupOneTemplate()}</div>
      <div class="form-field__group-two">${this._groupTwoTemplate()}</div>
    `}_groupOneTemplate(){return p`${this._labelTemplate()} ${this._helpTextTemplate()} ${this._feedbackTemplate()}`}_groupTwoTemplate(){return p`${this._inputGroupTemplate()}`}constructor(){super(),this.checked=!1,this.__handleButtonSwitchCheckedChanged=this.__handleButtonSwitchCheckedChanged.bind(this)}connectedCallback(){super.connectedCallback(),this.addEventListener(`checked-changed`,this.__handleButtonSwitchCheckedChanged),this._labelNode&&this._labelNode.addEventListener(`click`,this._toggleChecked),this._syncButtonSwitch()}disconnectedCallback(){super.disconnectedCallback(),this._inputNode&&this.removeEventListener(`checked-changed`,this.__handleButtonSwitchCheckedChanged),this._labelNode&&this._labelNode.removeEventListener(`click`,this._toggleChecked)}updated(e){super.updated(e),e.has(`disabled`)&&this._syncButtonSwitch()}_toggleChecked(e){e.preventDefault(),super._toggleChecked(e)}_isEmpty(){return!1}__handleButtonSwitchCheckedChanged(e){e.stopPropagation(),this._isHandlingUserInput=!0,this.checked=this._inputNode.checked,this._isHandlingUserInput=!1}_syncButtonSwitch(){this._inputNode.disabled=this.disabled}_onLabelClick(){this.disabled||this._inputNode.focus()}},Xc=class extends Jc{static get styles(){return[...super.styles,h`
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
      `]}};customElements.get(`craft-switch-button`)||customElements.define(`craft-switch-button`,Xc);var Zc=h`
  :host {
    display: grid;
  }

  .input-group {
    display: inline-flex;
  }

  ::slotted(label) {
    font-weight: bold;
  }
`,Qc=class extends Yc{constructor(...e){super(...e),this.size=`medium`}static get styles(){return[...super.styles,ba,Zc]}get slots(){return{...super.slots,input:()=>{let e=this.createScopedElement(`craft-switch-button`);return e.setAttribute(`size`,this.size),e.setAttribute(`data-tag-name`,`craft-switch-button`),e}}}static get scopedElements(){return{...super.scopedElements,"craft-switch-button":Xc}}};d([x({type:String,reflect:!0})],Qc.prototype,`size`,void 0),customElements.get(`craft-switch`)||customElements.define(`craft-switch`,Qc);var $c=h`
  .breadcrumbs {
    display: flex;
    align-items: center;
  }
`,el=class extends b{constructor(...e){super(...e),this.label=``,this.items=[],this.visibleItems=0,this.firstRender=!0}getSeparator(){let e=this.separatorSlot.assignedElements({flatten:!0})[0].cloneNode(!0);return[e,...e.querySelectorAll(`[id]`)].forEach(e=>e.removeAttribute(`id`)),e.setAttribute(`data-default`,``),e.slot=`separator`,e}calculateBreadcrumbItemsWidth(){this.items=this.breadcrumbsElements.map((e,t)=>{let n=e.offsetWidth;return e.hasAttribute(`hidden`)&&(e.removeAttribute(`hidden`),n=e.offsetWidth,e.setAttribute(`hidden`,``)),{label:e.innerText,href:e.href,value:t.toString(),offsetWidth:n,isVisible:!0}})}async handleSlotChange(){let e=[...this.defaultSlot.assignedElements({flatten:!0})].filter(e=>e.tagName.toLowerCase()===`craft-breadcrumb-item`);if(e.forEach((t,n)=>{let r=t.querySelector(`[slot="separator"]`);r===null?t.append(this.getSeparator()):r.hasAttribute(`data-default`)&&r.replaceWith(this.getSeparator()),n===e.length-1?t.setAttribute(`aria-current`,`page`):t.removeAttribute(`aria-current`)}),this.breadcrumbsElements.length===0){this.items=[],this.visibleItems=0;return}await Promise.all(this.breadcrumbsElements.map(e=>e.updateComplete)),this.calculateBreadcrumbItemsWidth(),this.visibleItems=0,this.adjustOverflow()}connectedCallback(){super.connectedCallback(),this.hasAttribute(`role`)||this.setAttribute(`role`,`navigation`),this.resizeObserver=new ResizeObserver(()=>{if(this.firstRender){this.firstRender=!1;return}this.adjustOverflow()}),this.resizeObserver.observe(this)}adjustOverflow(){let e=this.getBoundingClientRect().width;console.log({availableSpace:e})}disconnectedCallback(){this.resizeObserver?.unobserve(this),super.disconnectedCallback()}render(){return p`
      <nav class="breadcrumbs" aria-label="${this.label}">
        <slot @slotchange="${this.handleSlotChange}"></slot>
      </nav>

      <span hidden aria-hidden="true">
        <slot name="separator"><span class="separator">/</span></slot>
      </span>
    `}};el.styles=[$c],d([w(`slot`)],el.prototype,`defaultSlot`,void 0),d([w(`slot[name="separator"]`)],el.prototype,`separatorSlot`,void 0),d([T({selector:`craft-breadcrumb-item`})],el.prototype,`breadcrumbsElements`,void 0),d([x()],el.prototype,`label`,void 0),d([S()],el.prototype,`items`,void 0),d([S()],el.prototype,`visibleItems`,void 0),customElements.get(`craft-breadcrumbs`)||customElements.define(`craft-breadcrumbs`,el);var tl=`:host {
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
`,nl=class extends rt{constructor(){super(...arguments),this.renderType=`button`,this.rel=`noreferrer noopener`}setRenderType(){let e=this.defaultSlot.assignedElements({flatten:!0}).filter(e=>e.tagName.toLowerCase()===`wa-dropdown`).length>0;if(this.href){this.renderType=`link`;return}if(e){this.renderType=`dropdown`;return}this.renderType=`button`}hrefChanged(){this.setRenderType()}handleSlotChange(){this.setRenderType()}render(){return p`
      <span part="start" class="start">
        <slot name="start"></slot>
      </span>

      ${this.renderType===`link`?p`
            <a
              part="label"
              class="label label-link"
              href="${this.href}"
              target="${Ko(this.target?this.target:void 0)}"
              rel=${Ko(this.target?this.rel:void 0)}
            >
              <slot></slot>
            </a>
          `:``}
      ${this.renderType===`button`?p`
            <button part="label" type="button" class="label label-button">
              <slot @slotchange=${this.handleSlotChange}></slot>
            </button>
          `:``}
      ${this.renderType===`dropdown`?p`
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
    `}};nl.css=tl,k([w(`slot:not([name])`)],nl.prototype,`defaultSlot`,2),k([S()],nl.prototype,`renderType`,2),k([x()],nl.prototype,`href`,2),k([x()],nl.prototype,`target`,2),k([x()],nl.prototype,`rel`,2),k([gr(`href`,{waitUntilFirstUpdate:!0})],nl.prototype,`hrefChanged`,1),nl=k([C(`wa-breadcrumb-item`)],nl);var rl=class extends nl{static get styles(){return[nl.styles,h`
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
      `]}};customElements.get(`craft-breadcrumb-item`)||customElements.define(`craft-breadcrumb-item`,rl);var il=`:host {
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
`,al=new Set,ol=class extends rt{constructor(){super(...arguments),this.anchor=null,this.placement=`top`,this.open=!1,this.distance=8,this.skidding=0,this.for=null,this.withoutArrow=!1,this.eventController=new AbortController,this.handleAnchorClick=()=>{this.open=!this.open},this.handleBodyClick=e=>{e.target.closest(`[data-popover="close"]`)&&(e.stopPropagation(),this.open=!1)},this.handleDocumentKeyDown=e=>{e.key===`Escape`&&(e.preventDefault(),this.open=!1,this.anchor&&typeof this.anchor.focus==`function`&&this.anchor.focus())},this.handleDocumentClick=e=>{let t=e.target;this.anchor&&e.composedPath().includes(this.anchor)||t.closest(`wa-popover`)!==this&&(this.open=!1)}}connectedCallback(){super.connectedCallback(),this.id||=pr(`wa-popover-`)}disconnectedCallback(){super.disconnectedCallback(),document.removeEventListener(`keydown`,this.handleDocumentKeyDown),this.eventController.abort()}firstUpdated(){this.open&&(this.dialog.show(),this.popup.active=!0,this.popup.reposition())}updated(e){e.has(`open`)&&this.customStates.set(`open`,this.open)}async handleOpenChange(){if(this.open){let e=new ur;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}al.forEach(e=>e.open=!1),document.addEventListener(`keydown`,this.handleDocumentKeyDown,{signal:this.eventController.signal}),document.addEventListener(`click`,this.handleDocumentClick,{signal:this.eventController.signal}),this.dialog.show(),this.popup.active=!0,al.add(this),requestAnimationFrame(()=>{let e=this.querySelector(`[autofocus]`);e&&typeof e.focus==`function`?e.focus():this.dialog.focus()}),await hr(this.popup.popup,`show-with-scale`),this.popup.reposition(),this.dispatchEvent(new cr)}else{let e=new lr;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!0;return}document.removeEventListener(`keydown`,this.handleDocumentKeyDown),document.removeEventListener(`click`,this.handleDocumentClick),al.delete(this),await hr(this.popup.popup,`hide-with-scale`),this.popup.active=!1,this.dialog.close(),this.dispatchEvent(new sr)}}handleForChange(){let e=this.getRootNode();if(!e)return;let t=this.for?e.getElementById(this.for):null,n=this.anchor;if(t===n)return;let{signal:r}=this.eventController;t&&t.addEventListener(`click`,this.handleAnchorClick,{signal:r}),n&&n.removeEventListener(`click`,this.handleAnchorClick),this.anchor=t,this.for&&!t&&console.warn(`A popover was assigned to an element with an ID of "${this.for}" but the element could not be found.`,this)}async handleOptionsChange(){this.hasUpdated&&(await this.updateComplete,this.popup.reposition())}async show(){if(!this.open)return this.open=!0,mr(this,`wa-after-show`)}async hide(){if(this.open)return this.open=!1,mr(this,`wa-after-hide`)}render(){return p`
      <dialog part="dialog" class="dialog">
        <wa-popup
          part="popup"
          exportparts="
            popup:popup__popup,
            arrow:popup__arrow
          "
          class=${D({popover:!0,"popover-open":this.open})}
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
    `}};ol.css=il,ol.dependencies={"wa-popup":A},k([w(`dialog`)],ol.prototype,`dialog`,2),k([w(`.body`)],ol.prototype,`body`,2),k([w(`wa-popup`)],ol.prototype,`popup`,2),k([S()],ol.prototype,`anchor`,2),k([x()],ol.prototype,`placement`,2),k([x({type:Boolean,reflect:!0})],ol.prototype,`open`,2),k([x({type:Number})],ol.prototype,`distance`,2),k([x({type:Number})],ol.prototype,`skidding`,2),k([x()],ol.prototype,`for`,2),k([x({attribute:`without-arrow`,type:Boolean,reflect:!0})],ol.prototype,`withoutArrow`,2),k([gr(`open`,{waitUntilFirstUpdate:!0})],ol.prototype,`handleOpenChange`,1),k([gr(`for`)],ol.prototype,`handleForChange`,1),k([gr([`distance`,`placement`,`skidding`])],ol.prototype,`handleOptionsChange`,1),ol=k([C(`wa-popover`)],ol);var sl=class extends ol{static get styles(){return[ol.styles,h`
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
      `]}};customElements.get(`craft-popover`)||customElements.define(`craft-popover`,sl);var cl=new Set;function ll(){let e=document.documentElement.clientWidth;return Math.abs(window.innerWidth-e)}function ul(){let e=Number(getComputedStyle(document.body).paddingRight.replace(/px/,``));return isNaN(e)||!e?0:e}function dl(e){if(cl.add(e),!document.documentElement.classList.contains(`wa-scroll-lock`)){let e=ll()+ul(),t=getComputedStyle(document.documentElement).scrollbarGutter;(!t||t===`auto`)&&(t=`stable`),e<2&&(t=``),document.documentElement.style.setProperty(`--wa-scroll-lock-gutter`,t),document.documentElement.classList.add(`wa-scroll-lock`),document.documentElement.style.setProperty(`--wa-scroll-lock-size`,`${e}px`)}}function fl(e){cl.delete(e),cl.size===0&&(document.documentElement.classList.remove(`wa-scroll-lock`),document.documentElement.style.removeProperty(`--wa-scroll-lock-size`))}function pl(e){return e.split(` `).map(e=>e.trim()).filter(e=>e!==``)}var ml=`:host {
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
`,hl=class extends rt{constructor(){super(...arguments),this.localize=new Oe(this),this.hasSlotController=new Oc(this,`footer`,`header-actions`,`label`),this.open=!1,this.label=``,this.placement=`end`,this.withoutHeader=!1,this.lightDismiss=!0,this.handleDocumentKeyDown=e=>{e.key===`Escape`&&this.open&&(e.preventDefault(),e.stopPropagation(),this.requestClose(this.drawer))}}firstUpdated(){this.open&&(this.addOpenListeners(),this.drawer.showModal(),dl(this))}disconnectedCallback(){super.disconnectedCallback(),fl(this),this.removeOpenListeners()}async requestClose(e){let t=new lr({source:e});if(this.dispatchEvent(t),t.defaultPrevented){this.open=!0,hr(this.drawer,`pulse`);return}this.removeOpenListeners(),await hr(this.drawer,`hide`),this.open=!1,this.drawer.close(),fl(this);let n=this.originalTrigger;typeof n?.focus==`function`&&setTimeout(()=>n.focus()),this.dispatchEvent(new sr)}addOpenListeners(){document.addEventListener(`keydown`,this.handleDocumentKeyDown)}removeOpenListeners(){document.removeEventListener(`keydown`,this.handleDocumentKeyDown)}handleDialogCancel(e){e.preventDefault(),!this.drawer.classList.contains(`hide`)&&e.target===this.drawer&&this.requestClose(this.drawer)}handleDialogClick(e){let t=e.target.closest(`[data-drawer="close"]`);t&&(e.stopPropagation(),this.requestClose(t))}async handleDialogPointerDown(e){e.target===this.drawer&&(this.lightDismiss?this.requestClose(this.drawer):await hr(this.drawer,`pulse`))}handleOpenChange(){this.open&&!this.drawer.open?this.show():this.drawer.open&&(this.open=!0,this.requestClose(this.drawer))}async show(){let e=new ur;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}this.addOpenListeners(),this.originalTrigger=document.activeElement,this.open=!0,this.drawer.showModal(),dl(this),requestAnimationFrame(()=>{let e=this.querySelector(`[autofocus]`);e&&typeof e.focus==`function`?e.focus():this.drawer.focus()}),await hr(this.drawer,`show`),this.dispatchEvent(new cr)}render(){let e=!this.withoutHeader,t=this.hasSlotController.test(`footer`);return p`
      <dialog
        part="dialog"
        class=${D({drawer:!0,open:this.open,top:this.placement===`top`,end:this.placement===`end`,bottom:this.placement===`bottom`,start:this.placement===`start`})}
        @cancel=${this.handleDialogCancel}
        @click=${this.handleDialogClick}
        @pointerdown=${this.handleDialogPointerDown}
      >
        ${e?p`
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

        ${t?p`
              <footer part="footer" class="footer">
                <slot name="footer"></slot>
              </footer>
            `:``}
      </dialog>
    `}};hl.css=ml,k([w(`.drawer`)],hl.prototype,`drawer`,2),k([x({type:Boolean,reflect:!0})],hl.prototype,`open`,2),k([x({reflect:!0})],hl.prototype,`label`,2),k([x({reflect:!0})],hl.prototype,`placement`,2),k([x({attribute:`without-header`,type:Boolean,reflect:!0})],hl.prototype,`withoutHeader`,2),k([x({attribute:`light-dismiss`,type:Boolean})],hl.prototype,`lightDismiss`,2),k([gr(`open`,{waitUntilFirstUpdate:!0})],hl.prototype,`handleOpenChange`,1),hl=k([C(`wa-drawer`)],hl),document.addEventListener(`click`,e=>{let t=e.target.closest(`[data-drawer]`);if(t instanceof Element){let[e,n]=pl(t.getAttribute(`data-drawer`)||``);if(e===`open`&&n?.length){let e=t.getRootNode().getElementById(n);e?.localName===`wa-drawer`?e.open=!0:console.warn(`A drawer with an ID of "${n}" could not be found in this document.`)}}}),document.body.addEventListener(`pointerdown`,()=>{});var gl=()=>({checkValidity(e){let t=e.input,n={message:``,isValid:!0,invalidKeys:[]};if(!t)return n;let r=!0;if(`checkValidity`in t&&(r=t.checkValidity()),r)return n;if(n.isValid=!1,`validationMessage`in t&&(n.message=t.validationMessage),!(`validity`in t))return n.invalidKeys.push(`customError`),n;for(let e in t.validity){if(e===`valid`)continue;let r=e;t.validity[r]&&n.invalidKeys.push(r)}return n}}),_l=class extends Event{constructor(){super(`wa-invalid`,{bubbles:!0,cancelable:!1,composed:!0})}},vl=()=>({observedAttributes:[`custom-error`],checkValidity(e){let t={message:``,isValid:!0,invalidKeys:[]};return e.customError&&(t.message=e.customError,t.isValid=!1,t.invalidKeys=[`customError`]),t}}),yl=class extends rt{constructor(){super(),this.name=null,this.disabled=!1,this.required=!1,this.assumeInteractionOn=[`input`],this.validators=[],this.valueHasChanged=!1,this.hasInteracted=!1,this.customError=null,this.emittedEvents=[],this.emitInvalid=e=>{e.target===this&&(this.hasInteracted=!0,this.dispatchEvent(new _l))},this.handleInteraction=e=>{let t=this.emittedEvents;t.includes(e.type)||t.push(e.type),t.length===this.assumeInteractionOn?.length&&(this.hasInteracted=!0)},this.addEventListener(`invalid`,this.emitInvalid)}static get validators(){return[vl()]}static get observedAttributes(){let e=new Set(super.observedAttributes||[]);for(let t of this.validators)if(t.observedAttributes)for(let n of t.observedAttributes)e.add(n);return[...e]}connectedCallback(){super.connectedCallback(),this.updateValidity(),this.assumeInteractionOn.forEach(e=>{this.addEventListener(e,this.handleInteraction)})}firstUpdated(...e){super.firstUpdated(...e),this.updateValidity()}willUpdate(e){if(e.has(`customError`)&&(this.customError||=null,this.setCustomValidity(this.customError||``)),e.has(`value`)||e.has(`disabled`)){let e=this.value;if(Array.isArray(e)){if(this.name){let t=new FormData;for(let n of e)t.append(this.name,n);this.setValue(t,t)}}else this.setValue(e,e)}e.has(`disabled`)&&(this.customStates.set(`disabled`,this.disabled),(this.hasAttribute(`disabled`)||!this.matches(`:disabled`))&&this.toggleAttribute(`disabled`,this.disabled)),this.updateValidity(),super.willUpdate(e)}get labels(){return this.internals.labels}getForm(){return this.internals.form}get validity(){return this.internals.validity}get willValidate(){return this.internals.willValidate}get validationMessage(){return this.internals.validationMessage}checkValidity(){return this.updateValidity(),this.internals.checkValidity()}reportValidity(){return this.updateValidity(),this.hasInteracted=!0,this.internals.reportValidity()}get validationTarget(){return this.input||void 0}setValidity(...e){let t=e[0],n=e[1],r=e[2];r||=this.validationTarget,this.internals.setValidity(t,n,r||void 0),this.requestUpdate(`validity`),this.setCustomStates()}setCustomStates(){let e=!!this.required,t=this.internals.validity.valid,n=this.hasInteracted;this.customStates.set(`required`,e),this.customStates.set(`optional`,!e),this.customStates.set(`invalid`,!t),this.customStates.set(`valid`,t),this.customStates.set(`user-invalid`,!t&&n),this.customStates.set(`user-valid`,t&&n)}setCustomValidity(e){if(!e){this.customError=null,this.setValidity({});return}this.customError=e,this.setValidity({customError:!0},e,this.validationTarget)}formResetCallback(){this.resetValidity(),this.hasInteracted=!1,this.valueHasChanged=!1,this.emittedEvents=[],this.updateValidity()}formDisabledCallback(e){this.disabled=e,this.updateValidity()}formStateRestoreCallback(e,t){this.value=e,t===`restore`&&this.resetValidity(),this.updateValidity()}setValue(...e){let[t,n]=e;this.internals.setFormValue(t,n)}get allValidators(){let e=this.constructor.validators||[],t=this.validators||[];return[...e,...t]}resetValidity(){this.setCustomValidity(``),this.setValidity({})}updateValidity(){if(this.disabled||this.hasAttribute(`disabled`)||!this.willValidate){this.resetValidity();return}let e=this.allValidators;if(!e?.length)return;let t={customError:!!this.customError},n=this.validationTarget||this.input||void 0,r=``;for(let n of e){let{isValid:e,message:i,invalidKeys:a}=n.checkValidity(this);e||(r||=i,a?.length>=0&&a.forEach(e=>t[e]=!0))}r||=this.validationMessage,this.setValidity(t,r,n)}};yl.formAssociated=!0,k([x({reflect:!0})],yl.prototype,`name`,2),k([x({type:Boolean})],yl.prototype,`disabled`,2),k([x({state:!0,attribute:!1})],yl.prototype,`valueHasChanged`,2),k([x({state:!0,attribute:!1})],yl.prototype,`hasInteracted`,2),k([x({attribute:`custom-error`,reflect:!0})],yl.prototype,`customError`,2),k([x({attribute:!1,state:!0,type:Object})],yl.prototype,`validity`,1);var bl=`@layer wa-utilities {
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
`,xl=Symbol.for(``),Sl=e=>{if(e?.r===xl)return e?._$litStatic$},Cl=(e,...t)=>({_$litStatic$:t.reduce(((t,n,r)=>t+(e=>{if(e._$litStatic$!==void 0)return e._$litStatic$;throw Error(`Value passed to 'literal' function must be a 'literal' result: ${e}. Use 'unsafeStatic' to pass non-literal values, but\n            take care to ensure page security.`)})(n)+e[r+1]),e[0]),r:xl}),wl=new Map,Tl=(e=>(t,...n)=>{let r=n.length,i,a,o=[],s=[],c,l=0,u=!1;for(;l<r;){for(c=t[l];l<r&&(a=n[l],i=Sl(a))!==void 0;)c+=i+t[++l],u=!0;l!==r&&s.push(a),o.push(c),l++}if(l===r&&o.push(t[r]),u){let e=o.join(`$$lit$$`);(t=wl.get(e))===void 0&&(o.raw=o,wl.set(e,t=o)),n=s}return e(t,...n)})(p),El=`@layer wa-component {
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
`,L=class extends yl{constructor(){super(...arguments),this.assumeInteractionOn=[`click`],this.hasSlotController=new Oc(this,`[default]`,`start`,`end`),this.localize=new Oe(this),this.invalid=!1,this.isIconButton=!1,this.title=``,this.variant=`neutral`,this.appearance=`accent`,this.size=`medium`,this.withCaret=!1,this.disabled=!1,this.loading=!1,this.pill=!1,this.type=`button`,this.form=null}static get validators(){return[...super.validators,gl()]}constructLightDOMButton(){let e=document.createElement(`button`);return e.type=this.type,e.style.position=`absolute`,e.style.width=`0`,e.style.height=`0`,e.style.clipPath=`inset(50%)`,e.style.overflow=`hidden`,e.style.whiteSpace=`nowrap`,this.name&&(e.name=this.name),e.value=this.value||``,[`form`,`formaction`,`formenctype`,`formmethod`,`formnovalidate`,`formtarget`].forEach(t=>{this.hasAttribute(t)&&e.setAttribute(t,this.getAttribute(t))}),e}handleClick(){if(!this.getForm())return;let e=this.constructLightDOMButton();this.parentElement?.append(e),e.click(),e.remove()}handleInvalid(){this.dispatchEvent(new _l)}handleLabelSlotChange(){let e=this.labelSlot.assignedNodes({flatten:!0}),t=!1,n=!1,r=!1,i=!1;[...e].forEach(e=>{if(e.nodeType===Node.ELEMENT_NODE){let r=e;r.localName===`wa-icon`?(n=!0,t||=r.label!==void 0):i=!0}else e.nodeType===Node.TEXT_NODE&&(e.textContent?.trim()||``).length>0&&(r=!0)}),this.isIconButton=n&&!r&&!i,this.isIconButton&&!t&&console.warn(`Icon buttons must have a label for screen readers. Add <wa-icon label="..."> to remove this warning.`,this)}isButton(){return!this.href}isLink(){return!!this.href}handleDisabledChange(){this.updateValidity()}setValue(...e){}click(){this.button.click()}focus(e){this.button.focus(e)}blur(){this.button.blur()}render(){let e=this.isLink(),t=e?Cl`a`:Cl`button`;return Tl`
      <${t}
        part="base"
        class=${D({button:!0,caret:this.withCaret,disabled:this.disabled,loading:this.loading,rtl:this.localize.dir()===`rtl`,"has-label":this.hasSlotController.test(`[default]`),"has-start":this.hasSlotController.test(`start`),"has-end":this.hasSlotController.test(`end`),"is-icon-button":this.isIconButton})}
        ?disabled=${Ko(e?void 0:this.disabled)}
        type=${Ko(e?void 0:this.type)}
        title=${this.title}
        name=${Ko(e?void 0:this.name)}
        value=${Ko(e?void 0:this.value)}
        href=${Ko(e?this.href:void 0)}
        target=${Ko(e?this.target:void 0)}
        download=${Ko(e?this.download:void 0)}
        rel=${Ko(e&&this.rel?this.rel:void 0)}
        role=${Ko(e?void 0:`button`)}
        aria-disabled=${this.disabled?`true`:`false`}
        tabindex=${this.disabled?`-1`:`0`}
        @invalid=${this.isButton()?this.handleInvalid:null}
        @click=${this.handleClick}
      >
        <slot name="start" part="start" class="start"></slot>
        <slot part="label" class="label" @slotchange=${this.handleLabelSlotChange}></slot>
        <slot name="end" part="end" class="end"></slot>
        ${this.withCaret?Tl`
                <wa-icon part="caret" class="caret" library="system" name="chevron-down" variant="solid"></wa-icon>
              `:``}
        ${this.loading?Tl`<wa-spinner part="spinner"></wa-spinner>`:``}
      </${t}>
    `}};L.shadowRootOptions={...yl.shadowRootOptions,delegatesFocus:!0},L.css=[El,bl,Sc],k([w(`.button`)],L.prototype,`button`,2),k([w(`slot:not([name])`)],L.prototype,`labelSlot`,2),k([S()],L.prototype,`invalid`,2),k([S()],L.prototype,`isIconButton`,2),k([x()],L.prototype,`title`,2),k([x({reflect:!0})],L.prototype,`variant`,2),k([x({reflect:!0})],L.prototype,`appearance`,2),k([x({reflect:!0})],L.prototype,`size`,2),k([x({attribute:`with-caret`,type:Boolean,reflect:!0})],L.prototype,`withCaret`,2),k([x({type:Boolean})],L.prototype,`disabled`,2),k([x({type:Boolean,reflect:!0})],L.prototype,`loading`,2),k([x({type:Boolean,reflect:!0})],L.prototype,`pill`,2),k([x()],L.prototype,`type`,2),k([x({reflect:!0})],L.prototype,`name`,2),k([x({reflect:!0})],L.prototype,`value`,2),k([x({reflect:!0})],L.prototype,`href`,2),k([x()],L.prototype,`target`,2),k([x()],L.prototype,`rel`,2),k([x()],L.prototype,`download`,2),k([x({reflect:!0})],L.prototype,`form`,2),k([x({attribute:`formaction`})],L.prototype,`formAction`,2),k([x({attribute:`formenctype`})],L.prototype,`formEnctype`,2),k([x({attribute:`formmethod`})],L.prototype,`formMethod`,2),k([x({attribute:`formnovalidate`,type:Boolean})],L.prototype,`formNoValidate`,2),k([x({attribute:`formtarget`})],L.prototype,`formTarget`,2),k([gr(`disabled`,{waitUntilFirstUpdate:!0})],L.prototype,`handleDisabledChange`,1),L=k([C(`wa-button`)],L);var Dl=`:host {
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
`,Ol=class extends rt{constructor(){super(...arguments),this.localize=new Oe(this)}render(){return p`
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
    `}};Ol.css=Dl,Ol=k([C(`wa-spinner`)],Ol);var kl=class extends hl{static get styles(){return[hl.styles,h`
        :host {
          --wa-color-surface-raised: var(--c-surface-raised);
          --spacing: var(--c-spacing-lg);
        }
      `]}};customElements.get(`craft-drawer`)||customElements.define(`craft-drawer`,kl);var Al=`:host {
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
`,jl=class extends rt{constructor(){super(...arguments),this.localize=new Oe(this),this.hasSlotController=new Oc(this,`footer`,`header-actions`,`label`),this.open=!1,this.label=``,this.withoutHeader=!1,this.lightDismiss=!1,this.handleDocumentKeyDown=e=>{e.key===`Escape`&&this.open&&(e.preventDefault(),e.stopPropagation(),this.requestClose(this.dialog))}}firstUpdated(){this.open&&(this.addOpenListeners(),this.dialog.showModal(),dl(this))}disconnectedCallback(){super.disconnectedCallback(),fl(this),this.removeOpenListeners()}async requestClose(e){let t=new lr({source:e});if(this.dispatchEvent(t),t.defaultPrevented){this.open=!0,hr(this.dialog,`pulse`);return}this.removeOpenListeners(),await hr(this.dialog,`hide`),this.open=!1,this.dialog.close(),fl(this);let n=this.originalTrigger;typeof n?.focus==`function`&&setTimeout(()=>n.focus()),this.dispatchEvent(new sr)}addOpenListeners(){document.addEventListener(`keydown`,this.handleDocumentKeyDown)}removeOpenListeners(){document.removeEventListener(`keydown`,this.handleDocumentKeyDown)}handleDialogCancel(e){e.preventDefault(),!this.dialog.classList.contains(`hide`)&&e.target===this.dialog&&this.requestClose(this.dialog)}handleDialogClick(e){let t=e.target.closest(`[data-dialog="close"]`);t&&(e.stopPropagation(),this.requestClose(t))}async handleDialogPointerDown(e){e.target===this.dialog&&(this.lightDismiss?this.requestClose(this.dialog):await hr(this.dialog,`pulse`))}handleOpenChange(){this.open&&!this.dialog.open?this.show():!this.open&&this.dialog.open&&(this.open=!0,this.requestClose(this.dialog))}async show(){let e=new ur;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}this.addOpenListeners(),this.originalTrigger=document.activeElement,this.open=!0,this.dialog.showModal(),dl(this),requestAnimationFrame(()=>{let e=this.querySelector(`[autofocus]`);e&&typeof e.focus==`function`?e.focus():this.dialog.focus()}),await hr(this.dialog,`show`),this.dispatchEvent(new cr)}render(){let e=!this.withoutHeader,t=this.hasSlotController.test(`footer`);return p`
      <dialog
        part="dialog"
        class=${D({dialog:!0,open:this.open})}
        @cancel=${this.handleDialogCancel}
        @click=${this.handleDialogClick}
        @pointerdown=${this.handleDialogPointerDown}
      >
        ${e?p`
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

        ${t?p`
              <footer part="footer" class="footer">
                <slot name="footer"></slot>
              </footer>
            `:``}
      </dialog>
    `}};jl.css=Al,k([w(`.dialog`)],jl.prototype,`dialog`,2),k([x({type:Boolean,reflect:!0})],jl.prototype,`open`,2),k([x({reflect:!0})],jl.prototype,`label`,2),k([x({attribute:`without-header`,type:Boolean,reflect:!0})],jl.prototype,`withoutHeader`,2),k([x({attribute:`light-dismiss`,type:Boolean})],jl.prototype,`lightDismiss`,2),k([gr(`open`,{waitUntilFirstUpdate:!0})],jl.prototype,`handleOpenChange`,1),jl=k([C(`wa-dialog`)],jl),document.addEventListener(`click`,e=>{let t=e.target.closest(`[data-dialog]`);if(t instanceof Element){let[e,n]=pl(t.getAttribute(`data-dialog`)||``);if(e===`open`&&n?.length){let e=t.getRootNode().getElementById(n);e?.localName===`wa-dialog`?e.open=!0:console.warn(`A dialog with an ID of "${n}" could not be found in this document.`)}}}),document.addEventListener(`pointerdown`,()=>{});var Ml=class extends jl{static get styles(){return[jl.styles,h`
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
      `]}};customElements.get(`craft-dialog`)||customElements.define(`craft-dialog`,Ml);var Nl=class extends Ro(Ho(b)){constructor(){super(),this.multipleChoice=!0}},Pl=class extends Bo(Uo){connectedCallback(){super.connectedCallback(),this.type=`checkbox`}},Fl=class extends Pl{static get styles(){return[...super.styles||[],h`
        :host .choice-field__nested-checkboxes {
          display: block;
        }
        ::slotted(*) {
          padding-left: 8px;
        }
      `]}static get properties(){return{indeterminate:{type:Boolean,reflect:!0},mixedState:{type:Boolean,reflect:!0,attribute:`mixed-state`}}}get _checkboxGroupNode(){return this._parentFormGroup}get _subCheckboxes(){return this.__subCheckboxes}_storeIndeterminateState(){this._indeterminateSubStates=this._subCheckboxes.map(e=>e.checked)}_setOldState(){this.indeterminate?this._oldState=`indeterminate`:this._oldState=this.checked?`checked`:`unchecked`}_setOwnCheckedState(){let e=this._subCheckboxes;if(!e.length)return;this.__settingOwnChecked=!0;let t=e.filter(e=>e.checked);switch(e.length-t.length){case 0:this.indeterminate=!1,this.checked=!0;break;case e.length:this.indeterminate=!1,this.checked=!1;break;default:{this.indeterminate=!0;let n=e.filter(e=>e.disabled&&e.checked===!1);this.checked=e.length-t.length-n.length===0}}this.updateComplete.then(()=>{this.__settingOwnChecked=!1})}_setBasedOnMixedState(){switch(this._oldState){case`checked`:this.checked=!1,this.indeterminate=!1;break;case`unchecked`:this.checked=!1,this.indeterminate=!0;break;case`indeterminate`:this.checked=!0,this.indeterminate=!1;break}}__onModelValueChanged(e){if(!this.disabled){if(e.detail.formPath[0]===this&&!this.__settingOwnChecked){this.mixedState&&!(e=>e.every(t=>t===e[0]))(this._indeterminateSubStates)&&this._setBasedOnMixedState(),this.__settingOwnSubs=!0;let e=this._subCheckboxes,t=e.filter(e=>e.checked),n=e.filter(e=>e.disabled),r=e.length>0&&e.length===t.length;e.length>0&&e.length===n.length&&(this.checked=r),this.indeterminate&&this.mixedState?this._subCheckboxes.forEach((e,t)=>{e.checked=this._indeterminateSubStates[t]}):this._subCheckboxes.filter(e=>!e.disabled).forEach(e=>{e.checked=this._inputNode.checked}),this.updateComplete.then(()=>{this.__settingOwnSubs=!1})}else this._setOwnCheckedState(),this.updateComplete.then(()=>{!this.__settingOwnSubs&&!this.__settingOwnChecked&&this.mixedState&&this._storeIndeterminateState()});this.mixedState&&this._setOldState()}}_afterTemplate(){return p`
      <div class="choice-field__nested-checkboxes" role="list">
        <slot></slot>
      </div>
    `}_onRequestToAddFormElement(e){e.target.hasAttribute(`role`)||e.target?.setAttribute(`role`,`listitem`),this.__addToSubCheckboxes(e.detail.element),this._setOwnCheckedState()}_onRequestToRemoveFormElement(e){e.target.getAttribute(`role`)===`listitem`&&e.target?.removeAttribute(`role`),this.__removeFromSubCheckboxes(e.detail.element)}__addToSubCheckboxes(e){e!==this&&this.contains(e)&&this.__subCheckboxes.push(e)}__removeFromSubCheckboxes(e){let t=this.__subCheckboxes.indexOf(e);t!==-1&&this.__subCheckboxes.splice(t,1)}constructor(){super(),this.indeterminate=!1,this._onRequestToAddFormElement=this._onRequestToAddFormElement.bind(this),this.__onModelValueChanged=this.__onModelValueChanged.bind(this),this.__subCheckboxes=[],this._indeterminateSubStates=[],this.mixedState=!1}connectedCallback(){super.connectedCallback(),this.addEventListener(`model-value-changed`,this.__onModelValueChanged),this.addEventListener(`form-element-register`,this._onRequestToAddFormElement)}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener(`model-value-changed`,this.__onModelValueChanged),this.removeEventListener(`form-element-register`,this._onRequestToAddFormElement)}firstUpdated(e){super.firstUpdated(e),this._setOldState(),this.indeterminate&&this._storeIndeterminateState()}updated(e){super.updated(e),(e.has(`indeterminate`)||e.has(`checked`))&&(this._inputNode.indeterminate=this.indeterminate)}},Il=class extends Nl{static get styles(){return[...Nl.styles,h`
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
      `]}};customElements.get(`craft-checkbox-group`)||customElements.define(`craft-checkbox-group`,Il);var Ll=class extends Pl{static get styles(){return[...Pl.styles,h`
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
      `]}};customElements.get(`craft-checkbox`)||customElements.define(`craft-checkbox`,Ll);var Rl=class extends Fl{static get styles(){return[...Fl.styles,h`
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
      `]}};customElements.get(`craft-checkbox-indeterminate`)||customElements.define(`craft-checkbox-indeterminate`,Rl);var zl=h`
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
`,Bl=h`
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
`,Vl=class extends b{constructor(...t){super(...t),this.variant=o.Default,this.appearance=e.OutlineFill,this.title=``,this.icon=null,this.rounded=`all`,this.inline=!1}getDefaultIcon(){switch(this.variant){case o.Info:return`lightbulb`;case o.Success:return`circle-check`;case o.Warning:return`circle-exclamation`;case o.Danger:return`triangle-exclamation`;default:return null}}render(){return p`
      ${this.icon||this.querySelector(`[slot="icon"]`)?p`<slot name="icon" class="callout__icon">
            <craft-icon
              name="${this.getDefaultIcon()}"
              style="font-size: 0.9em"
            ></craft-icon>
          </slot>`:y}
      <div class="callout__body">
        <slot name="title" class="callout__title">${this.title}</slot>
        <div class="callout__description">
          <slot></slot>
        </div>
      </div>
    `}};Vl.styles=[zl,Bl],d([x({reflect:!0})],Vl.prototype,`variant`,void 0),d([x({reflect:!0})],Vl.prototype,`appearance`,void 0),d([x()],Vl.prototype,`title`,void 0),d([x()],Vl.prototype,`icon`,void 0),d([x({reflect:!0})],Vl.prototype,`rounded`,void 0),d([x({reflect:!0,type:Boolean})],Vl.prototype,`inline`,void 0),customElements.get(`craft-callout`)||customElements.define(`craft-callout`,Vl);var Hl=h`
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
`,Ul=class extends b{constructor(...e){super(...e),this.icon=null,this.href=null,this.disabled=!1,this.variant=o.Default,this.checked=!1,this.active=!1,this.type=`normal`}renderBody(){let e=!!this.querySelector(`[slot="icon"]`)||!!this.icon;return p`
      ${this.type===`checkbox`?p` <span class="action-item__check">
            <slot name="checkmark">
              ${this.checked?p`<craft-icon name="check"></craft-icon>`:y}
            </slot>
          </span>`:y}
      ${e?p`<span class="action-item__icon">
            <slot name="icon">
              ${this.icon?p`<craft-icon name="${this.icon}"></craft-icon>`:y}
            </slot>
          </span>`:y}

      <span class="action-item__label">
        <slot></slot>
      </span>

      <span class="action-item__suffix">
        <slot name="suffix"></slot>
      </span>
    `}render(){return this.href?p`
          <a
            class="${D({"action-item":!0,"action-item--checkbox":this.type===`checkbox`})}"
            href="${this.href}"
          >
            ${this.renderBody()}
          </a>
        `:p`
          <button
            type="button"
            class="${D({"action-item":!0,"action-item--checkbox":this.type===`checkbox`})}"
            ?disabled="${this.disabled}"
          >
            ${this.renderBody()}
          </button>
        `}};Ul.styles=[zl,Hl],d([x()],Ul.prototype,`icon`,void 0),d([x()],Ul.prototype,`href`,void 0),d([x({type:Boolean})],Ul.prototype,`disabled`,void 0),d([x({reflect:!0})],Ul.prototype,`variant`,void 0),d([x({type:Boolean})],Ul.prototype,`checked`,void 0),d([x({type:Boolean})],Ul.prototype,`active`,void 0),d([x()],Ul.prototype,`type`,void 0),customElements.get(`craft-action-item`)||customElements.define(`craft-action-item`,Ul);var Wl=h`
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
`,Gl=class e{static __createGlobalStyleNode(){let e=document.createElement(`style`);return e.setAttribute(`data-overlays`,``),e.textContent=Wl.cssText,document.head.appendChild(e),e}get list(){return this.__list}get shownList(){return this.__shownList}constructor(){this.__list=[],this.__shownList=[],this.__siblingsInert=!1,this.__blockingMap=new WeakMap,e.__globalStyleNode||=e.__createGlobalStyleNode()}add(e){if(this.list.find(t=>e===t))throw Error(`controller instance is already added`);return this.list.push(e),e}remove(e){if(!this.list.find(t=>e===t))throw Error(`could not find controller to remove`);this.__list=this.list.filter(t=>t!==e),this.__shownList=this.shownList.filter(t=>t!==e)}show(e){this.list.find(t=>e===t)&&this.hide(e),this.__shownList.unshift(e),Array.from(this.__shownList).reverse().forEach((e,t)=>{e.elevation=t+1})}hide(e){if(!this.list.find(t=>e===t))throw Error(`could not find controller to hide`);this.__shownList=this.shownList.filter(t=>t!==e)}teardown(){this.list.forEach(e=>{e.teardown()}),this.__list=[],this.__shownList=[],this.__siblingsInert=!1,e.__globalStyleNode&&=(document.head.removeChild(e.__globalStyleNode),void 0)}get siblingsInert(){return this.__siblingsInert}disableTrapsKeyboardFocusForAll(){this.shownList.forEach(e=>{e.trapsKeyboardFocus===!0&&e.disableTrapsKeyboardFocus&&e.disableTrapsKeyboardFocus({findNewTrap:!1})})}informTrapsKeyboardFocusGotEnabled(e){this.siblingsInert===!1&&e===`global`&&(this.__siblingsInert=!0)}informTrapsKeyboardFocusGotDisabled({disabledCtrl:e,findNewTrap:t=!0}={}){let n=this.shownList.find(t=>t!==e&&t.trapsKeyboardFocus===!0);n?t&&n.enableTrapsKeyboardFocus():this.siblingsInert===!0&&(this.__siblingsInert=!1)}requestToPreventScroll(){let{isIOS:e,isMacSafari:t}=Gr;document.body.classList.add(`overlays-scroll-lock`),(e||t)&&document.body.classList.add(`overlays-scroll-lock-ios-fix`),e&&document.documentElement.classList.add(`overlays-scroll-lock-ios-fix`)}requestToEnableScroll(){if(this.shownList.some(e=>e.preventsScroll===!0))return;let{isIOS:e,isMacSafari:t}=Gr;document.body.classList.remove(`overlays-scroll-lock`),(e||t)&&document.body.classList.remove(`overlays-scroll-lock-ios-fix`),e&&document.documentElement.classList.remove(`overlays-scroll-lock-ios-fix`)}requestToShowOnly(e){let t=this.shownList.filter(t=>t!==e);t.forEach(e=>e.hide()),this.__blockingMap.set(e,t)}retractRequestToShowOnly(e){this.__blockingMap.has(e)&&this.__blockingMap.get(e).forEach(e=>e.show())}};Gl.__globalStyleNode=void 0;var Kl=Ja.get(`@lion/ui::overlays::0.x`)||new Gl;function ql(){let e=document.activeElement||document.body;for(;e&&e.shadowRoot&&e.shadowRoot.activeElement;)e=e.shadowRoot.activeElement;return e}var Jl=({visibility:e,display:t})=>e!==`hidden`&&t!==`none`,Yl=({display:e})=>e===`contents`;function Xl(e){if(!e||!e.isConnected||!Jl(e.style))return!1;let t=window.getComputedStyle(e);return Jl(t)?Yl(t)?!0:!!(e.offsetWidth||e.offsetHeight||e.getClientRects().length):!1}function Zl(e,t){let n=Math.max(e.tabIndex,0),r=Math.max(t.tabIndex,0);return n===0||r===0?r>n:n>r}function Ql(e,t){let n=[];for(;e.length>0&&t.length>0;)Zl(e[0],t[0])?n.push(t.shift()):n.push(e.shift());return[...n,...e,...t]}function $l(e){let t=e.length;if(t<2)return e;let n=Math.ceil(t/2);return Ql($l(e.slice(0,n)),$l(e.slice(n)))}var eu=`matches`in Element.prototype?`matches`:`msMatchesSelector`;function tu(e){return e[eu](`input, select, textarea, button, object`)?e[eu](`:not([disabled])`):e[eu](`a[href], area[href], iframe, [tabindex], [contentEditable]`)}function nu(e){return tu(e)?Number(e.getAttribute(`tabindex`)||0):-1}function ru(e){if(e.localName===`slot`)return e.assignedNodes({flatten:!0});let{children:t}=e.shadowRoot||e;return t||[]}function iu(e){return e.nodeType===Node.ELEMENT_NODE?e.localName===`slot`?!0:Xl(e):!1}function au(e,t){if(!iu(e))return!1;let n=e,r=nu(n),i=r>0;r>=0&&t.push(n);let a=ru(n);for(let e=0;e<a.length;e+=1)i=au(a[e],t)||i;return i}function ou(e){let t=[];return au(e,t)?$l(t):t}function su(e,t,n={}){function r(e){return`getAttribute`in e}function i(e){if(!r(e))return null;let t=e.getAttribute(`slot`),i=null;if(t){let r=n[t];r&&(i=r.filter(t=>t?.element===e)[0]||null)}return i}let a=i(e);if(a)return a.deepContains;function o(t){if(!r(e))return;let i=e.getAttribute(`slot`);i&&(n[i]=n[i]||[],n[i].push({element:e,deepContains:t}))}let s=e.contains(t);if(s)return o(!0),!0;function c(e){return e.tagName===`SLOT`}function l(e){return c(e)?e.assignedElements():[]}function u(e){return e.nodeType===Node.DOCUMENT_FRAGMENT_NODE}function d(e){let i=!1;for(let a=0;a<e.length;a+=1){let o=e[a];if(o&&(r(o)||u(o))&&su(o,t,n)){i=!0;break}}return i}function f(e){for(let t=0;t<e.children.length;t+=1){let n=e.children[t],r=i(n);if(r){s=r.deepContains||s;break}let a=l(n);if(d([n.shadowRoot,...a])){s=!0;break}n.children.length>0&&f(n)}}return e instanceof HTMLElement&&e.shadowRoot&&(s=su(e.shadowRoot,t,n),s)?(o(!0),!0):(f(e),o(s),s)}var cu={enter:13,space:32,escape:27,tab:9};function lu(e,t){let n=ou(e),r;r=n.length>=2?[n[0],n[n.length-1]]:n.length===1?[n[0],n[0]]:[e,e],t.shiftKey&&r.reverse();let[i,a]=r,o=ql();o===e||n.includes(o)&&a!==o||(t.preventDefault(),i.focus())}function uu(e){let t=ou(e),n=t.find(e=>e.hasAttribute(`autofocus`))||e,r,i;n===e&&(e.tabIndex=-1,e.style.setProperty(`outline`,`none`)),n.focus();function a(t){t.keyCode===cu.tab&&lu(e,t)}function o(){r=document.createElement(`div`),r.style.display=`none`,r.setAttribute(`data-is-tab-detection-element`,``),e.insertBefore(r,e.children[0]),i=new MutationObserver(t=>{for(let n of t)if(n.type===`childList`){let t=!Array.from(e.children).find(e=>e.hasAttribute(`data-is-tab-detection-element`)),r=Array.from(n.addedNodes).find(e=>e instanceof HTMLElement&&e.hasAttribute(`data-is-tab-detection-element`));t&&!r&&(i.disconnect(),o())}}),i.observe(e,{childList:!0})}function s(){return r.compareDocumentPosition(document.activeElement)===Node.DOCUMENT_POSITION_PRECEDING}function c({resetToRoot:n=!1}={}){if(su(e,ql()))return;let r;r=n?e:t[s()?0:t.length-1],r&&r.focus()}function l(){window.removeEventListener(`focusin`,l),c()}function u(){setTimeout(()=>{su(e,ql())||c({resetToRoot:!0})}),window.addEventListener(`focusin`,l)}function d(){window.removeEventListener(`keydown`,a),window.removeEventListener(`focusin`,l),window.removeEventListener(`focusout`,u),i.disconnect(),Array.from(e.children).includes(r)&&e.removeChild(r),e.style.removeProperty(`outline`)}return window.addEventListener(`keydown`,a),window.addEventListener(`focusout`,u),o(),{disconnect:d}}var du=h`
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
`,fu={supportsAdoptingStyleSheets:window.ShadowRoot&&(window.ShadyCSS===void 0||window.ShadyCSS.nativeShadow)&&`adoptedStyleSheets`in Document.prototype&&`replace`in CSSStyleSheet.prototype,adoptStyle:void 0,adoptStyles:void 0},pu=new WeakMap;function mu(e){return Array.from(e.cssRules).map(e=>e.cssText).join(``)}function hu(e,t,{teardown:n=!1}={}){let r=e===document?document.body:e,i=t.cssText||mu(t);if(n){let e=Array.from(r.querySelectorAll(`style`));for(let t of e)if(t.textContent===i){t.remove();break}}else{let e=document.createElement(`style`),t=window.litNonce;t!==void 0&&e.setAttribute(`nonce`,t),e.textContent=i,r.appendChild(e)}}function gu(e,t,{teardown:n=!1}={}){let r=!1;e&&!pu.has(e)&&pu.set(e,[]);let i=pu.get(e)??[],a=i.find(e=>t===e);return a&&n?i.splice(i.indexOf(t),1):!a&&!n?i.push(t):(a&&!n||!a&&n)&&(r=!0),{haltFurtherExecution:r}}function _u(e,t,{teardown:n=!1}={}){let{haltFurtherExecution:r}=gu(e,t,{teardown:n});if(r)return;if(!fu.supportsAdoptingStyleSheets||Gr.isIOS){hu(e,t,{teardown:n});return}let i=t instanceof CSSStyleSheet?t:t.styleSheet;if(!i)throw Error(`Please provide a CSSResultOrNative style`);n?e.adoptedStyleSheets.includes(i)&&e.adoptedStyleSheets.splice(e.adoptedStyleSheets.indexOf(i),1):e.adoptedStyleSheets=[...e.adoptedStyleSheets,i]}function vu(e,t,{teardown:n=!1}={}){for(let r of t)fu.adoptStyle(e,r,{teardown:n})}fu.adoptStyle=_u,fu.adoptStyles=vu;function yu({wrappingDialogNodeL1:e,contentWrapperNodeL2:t,contentNodeL3:n}){if(!(t.isConnected||n.isConnected))throw Error(`[OverlayController] Could not find a render target, since the provided contentNode is not connected to the DOM. Make sure that it is connected, e.g. by doing "document.body.appendChild(contentNode)", before passing it on.`);let r,i=document.createComment(`tempMarker`);t.isConnected?(r=t.parentElement||t.getRootNode(),r.insertBefore(i,t),e.appendChild(t)):n.assignedSlot?(r=n.assignedSlot.parentElement||n.assignedSlot.getRootNode(),r.insertBefore(i,n.assignedSlot),e.appendChild(t),t.appendChild(n.assignedSlot)):(r=n.parentElement||n.getRootNode(),r.insertBefore(i,n),e.appendChild(t),t.appendChild(n)),r.insertBefore(e,i),r?.removeChild(i)}async function bu(){return O(()=>import(`./popper.js`),[],import.meta.url)}var xu=new WeakMap,Su=class e extends EventTarget{constructor(e={},t=Kl){super(),this.manager=t,this.__sharedConfig=e,this.__activeElementRightBeforeHide=null,this.config={},this._defaultConfig={placementMode:void 0,contentNode:e.contentNode,contentWrapperNode:e.contentWrapperNode,invokerNode:e.invokerNode,backdropNode:e.backdropNode,referenceNode:void 0,elementToFocusAfterHide:e.invokerNode,inheritsReferenceWidth:`none`,hasBackdrop:!1,isBlocking:!1,preventsScroll:!1,trapsKeyboardFocus:!1,hidesOnEsc:!1,hidesOnOutsideEsc:!1,hidesOnOutsideClick:!1,isTooltip:!1,isAlertDialog:!1,invokerRelation:`description`,visibilityTriggerFunction:void 0,handlesAccessibility:!1,popperConfig:{placement:`top`,strategy:`fixed`,modifiers:[{name:`preventOverflow`,enabled:!0,options:{boundariesElement:`viewport`,padding:8}},{name:`flip`,options:{boundariesElement:`viewport`,padding:16}},{name:`offset`,enabled:!0,options:{offset:[0,8]}},{name:`arrow`,enabled:!1}]},viewportConfig:{placement:`center`},zIndex:9999},this._contentId=`overlay-content--${Math.random().toString(36).slice(2,10)}`,this.__originalAttrs=new Map,this.updateConfig(e),this.__hasActiveTrapsKeyboardFocus=!1,this.__hasActiveBackdrop=!0,this.__escKeyHandler=this.__escKeyHandler.bind(this),this.__cancelHandler=this.__cancelHandler.bind(this)}get invoker(){return this.invokerNode}get content(){return this.__wrappingDialogNode}get placementMode(){return this.config?.placementMode}get invokerNode(){return this.config?.invokerNode}get referenceNode(){return this.config?.referenceNode}get contentNode(){return this.config?.contentNode}get contentWrapperNode(){return this.__contentWrapperNode||this.config?.contentWrapperNode}get backdropNode(){return this.__backdropNode||this.config?.backdropNode}get elementToFocusAfterHide(){return this.__elementToFocusAfterHide||this.config?.elementToFocusAfterHide}get hasBackdrop(){return!!this.backdropNode||this.config?.hasBackdrop}get isBlocking(){return this.config?.isBlocking}get preventsScroll(){return this.config?.preventsScroll}get trapsKeyboardFocus(){return this.config?.trapsKeyboardFocus}get hidesOnEsc(){return this.config?.hidesOnEsc}get hidesOnOutsideClick(){return this.config?.hidesOnOutsideClick}get hidesOnOutsideEsc(){return this.config?.hidesOnOutsideEsc}get inheritsReferenceWidth(){return this.config?.inheritsReferenceWidth}get handlesAccessibility(){return this.config?.handlesAccessibility}get isTooltip(){return this.config?.isTooltip}get isAlertDialog(){return this.config?.isAlertDialog}get invokerRelation(){return this.config?.invokerRelation}get popperConfig(){return this.config?.popperConfig}get viewportConfig(){return this.config?.viewportConfig}get visibilityTriggerFunction(){return this.config?.visibilityTriggerFunction}get _referenceNode(){return this.referenceNode||this.invokerNode}set elevation(e){this.__wrappingDialogNode.style.zIndex=`${this.config.zIndex+e}`}get elevation(){return Number(this.contentWrapperNode?.style.zIndex)}updateConfig(e){this.teardown(),this.__prevConfig=this.config,this.config={...this._defaultConfig,...this.__sharedConfig,...e,popperConfig:{...this._defaultConfig.popperConfig||{},...this.__sharedConfig.popperConfig||{},...e.popperConfig||{},modifiers:[...this._defaultConfig.popperConfig?.modifiers||[],...this.__sharedConfig.popperConfig?.modifiers||[],...e.popperConfig?.modifiers||[]]}},this.__validateConfiguration(this.config),this._init(),this.__elementToFocusAfterHide=void 0,this.#e()||this.manager.add(this)}#e(){return!!this.manager.list.find(e=>this===e)}__validateConfiguration(e){if(!e.placementMode)throw Error(`[OverlayController] You need to provide a .placementMode ("global"|"local")`);if(![`global`,`local`].includes(e.placementMode))throw Error(`[OverlayController] "${e.placementMode}" is not a valid .placementMode, use ("global"|"local")`);if(!e.contentNode)throw Error(`[OverlayController] You need to provide a .contentNode`);if(e.isTooltip&&!e.handlesAccessibility)throw Error(`[OverlayController] .isTooltip only takes effect when .handlesAccessibility is enabled`)}_init(){this.__contentHasBeenInitialized||=(this.__initContentDomStructure(),!0),this.contentWrapperNode.removeAttribute(`style`),this.contentWrapperNode.removeAttribute(`class`),this.placementMode===`local`&&(e.popperModule||=bu()),this.__handleOverlayStyles({phase:`init`}),this._handleFeatures({phase:`init`})}__handleOverlayStyles({phase:e}){let t=this.contentWrapperNode?.getRootNode();e===`init`?fu.adoptStyle(t,du):e===`teardown`&&fu.adoptStyle(t,du,{teardown:!0})}__initContentDomStructure(){let e=document.createElement(this.config?._noDialogEl?`div`:`dialog`);e.setAttribute(`role`,`none`),e.setAttribute(`data-overlay-outer-wrapper`,``),e.style.cssText=`display:none; z-index: ${this.config.zIndex}; padding: 0;`,this.__wrappingDialogNode=e,this.config?.contentWrapperNode||(this.__contentWrapperNode=document.createElement(`div`)),this.contentWrapperNode.setAttribute(`data-id`,`content-wrapper`),yu({wrappingDialogNodeL1:e,contentWrapperNodeL2:this.contentWrapperNode,contentNodeL3:this.contentNode}),e.open=!0,this.isTooltip&&e.setAttribute(`tabindex`,`-1`),this.__wrappingDialogNode.style.display=`none`,this.contentWrapperNode.style.zIndex=`1`,getComputedStyle(this.contentNode).position===`absolute`&&(this.contentNode.style.position=`static`),HTMLDialogElement&&`closedBy`in HTMLDialogElement.prototype?e.closedBy=`none`:(e.addEventListener(`keydown`,e=>{e.key===`Escape`&&e.preventDefault()}),e.addEventListener(`keyup`,e=>{e.key===`Escape`&&e.preventDefault()}),e.addEventListener(`cancel`,e=>{e.stopPropagation()}),e.addEventListener(`close`,e=>{e.stopPropagation()}))}_handleZIndex({phase:e}){if(this.placementMode===`local`&&e===`setup`){let e=Number(getComputedStyle(this.contentNode).zIndex);(e<1||Number.isNaN(e))&&(this.contentNode.style.zIndex=`1`)}}__setupTeardownAccessibility({phase:e}){if(e===`init`){this.__storeOriginalAttrs(this.contentNode,[`role`,`id`]);let e=this.trapsKeyboardFocus;if(this.invokerNode){let t=[`aria-labelledby`,`aria-describedby`];e||t.push(`aria-expanded`),this.__storeOriginalAttrs(this.invokerNode,t)}this.contentNode.id||this.contentNode.setAttribute(`id`,this._contentId),this.isTooltip?(this.invokerNode&&this.invokerNode.setAttribute(this.invokerRelation===`label`?`aria-labelledby`:`aria-describedby`,this._contentId),this.contentNode.setAttribute(`role`,`tooltip`)):(this.invokerNode&&!e&&this.invokerNode.setAttribute(`aria-expanded`,`${this.isShown}`),this.isAlertDialog?this.contentNode.setAttribute(`role`,`alertdialog`):this.contentNode.getAttribute(`role`)||this.contentNode.setAttribute(`role`,`dialog`))}else e===`teardown`&&this.__restoreOriginalAttrs()}__storeOriginalAttrs(e,t){let n={};t.forEach(t=>{n[t]=e.getAttribute(t)}),this.__originalAttrs.set(e,n)}__restoreOriginalAttrs(){for(let[e,t]of this.__originalAttrs)Object.entries(t).forEach(([t,n])=>{n===null?e.removeAttribute(t):e.setAttribute(t,n)});this.__originalAttrs.clear()}get isShown(){return this.__wrappingDialogNode?.style.display!==`none`}async show(e=this.elementToFocusAfterHide){if(this._showComplete&&await this._showComplete,this._showComplete=new Promise(e=>{this._showResolve=e}),this.manager&&this.manager.show(this),this.isShown){this._showResolve();return}let t=new CustomEvent(`before-show`,{cancelable:!0});this.dispatchEvent(t),t.defaultPrevented||(`HTMLDialogElement`in window&&this.__wrappingDialogNode instanceof HTMLDialogElement&&(this.__wrappingDialogNode.open=!0),this.__wrappingDialogNode.style.display=``,this._keepBodySize({phase:`before-show`}),await this._handleFeatures({phase:`show`}),this._keepBodySize({phase:`show`}),await this._handlePosition({phase:`show`}),this.__elementToFocusAfterHide=e,this.dispatchEvent(new Event(`show`)),await this._transitionShow({backdropNode:this.backdropNode,contentNode:this.contentNode})),this._showResolve()}async _handlePosition({phase:e}){if(this.placementMode===`global`){let t=`overlays__overlay-container--${this.viewportConfig.placement}`;e===`show`?(this.contentWrapperNode.classList.add(`overlays__overlay-container`),this.contentWrapperNode.classList.add(t),this.contentNode.classList.add(`overlays__overlay`)):e===`hide`&&(this.contentWrapperNode.classList.remove(`overlays__overlay-container`),this.contentWrapperNode.classList.remove(t),this.contentNode.classList.remove(`overlays__overlay`))}else this.placementMode===`local`&&e===`show`&&(await this.__createPopperInstance(),this._popper.forceUpdate())}_keepBodySize({phase:e}){if(this.preventsScroll)switch(e){case`before-show`:this.__bodyClientWidth=document.body.clientWidth,this.__bodyClientHeight=document.body.clientHeight,this.__bodyMarginRightInline=document.body.style.marginRight,this.__bodyMarginBottomInline=document.body.style.marginBottom;break;case`show`:{if(window.getComputedStyle){let e=window.getComputedStyle(document.body);this.__bodyMarginRight=parseInt(e.getPropertyValue(`margin-right`),10),this.__bodyMarginBottom=parseInt(e.getPropertyValue(`margin-bottom`),10)}else this.__bodyMarginRight=0,this.__bodyMarginBottom=0;let e=document.body.clientWidth-this.__bodyClientWidth,t=document.body.clientHeight-this.__bodyClientHeight,n=this.__bodyMarginRight+e,r=this.__bodyMarginBottom+t;window.CSS?.number&&document.body.attributeStyleMap?.set?(document.body.attributeStyleMap.set(`margin-right`,CSS.px(n)),document.body.attributeStyleMap.set(`margin-bottom`,CSS.px(r))):(document.body.style.marginRight=`${n}px`,document.body.style.marginBottom=`${r}px`);break}case`hide`:document.body.style.marginRight=this.__bodyMarginRightInline||``,document.body.style.marginBottom=this.__bodyMarginBottomInline||``;break}}async hide(){if(this._hideComplete=new Promise(e=>{this._hideResolve=e}),this.__activeElementRightBeforeHide=this.contentNode.getRootNode().activeElement,this.manager&&this.manager.hide(this),!this.isShown){this._hideResolve();return}let e=new CustomEvent(`before-hide`,{cancelable:!0});this.dispatchEvent(e),e.defaultPrevented||(await this._transitionHide({backdropNode:this.backdropNode,contentNode:this.contentNode}),`HTMLDialogElement`in window&&this.__wrappingDialogNode instanceof HTMLDialogElement&&this.__wrappingDialogNode.close(),this.__wrappingDialogNode.style.display=`none`,this._handleFeatures({phase:`hide`}),this._keepBodySize({phase:`hide`}),this.dispatchEvent(new Event(`hide`)),this._restoreFocus()),this._hideResolve()}async transitionHide(e){}async _transitionHide({backdropNode:e,contentNode:t}){await this.transitionHide({backdropNode:e,contentNode:t}),this._handlePosition({phase:`hide`}),e&&e.classList.remove(`overlays__backdrop--animation-in`)}async transitionShow(e){}async _transitionShow(e){await this.transitionShow({backdropNode:this.backdropNode,contentNode:this.contentNode}),e.backdropNode&&e.backdropNode.classList.add(`overlays__backdrop--animation-in`)}_restoreFocus(){this.__activeElementRightBeforeHide instanceof HTMLElement&&this.contentNode.contains(this.__activeElementRightBeforeHide)&&(this.elementToFocusAfterHide instanceof HTMLElement?(this.elementToFocusAfterHide.focus(),this.elementToFocusAfterHide.scrollIntoView({block:`nearest`})):this.__activeElementRightBeforeHide.blur())}async toggle(){return this.isShown?this.hide():this.show()}_handleFeatures({phase:e}){this._handleZIndex({phase:e}),this.preventsScroll&&this._handlePreventsScroll({phase:e}),this.isBlocking&&this._handleBlocking({phase:e}),this.hasBackdrop&&this._handleBackdrop({phase:e}),this.trapsKeyboardFocus&&this._handleTrapsKeyboardFocus({phase:e}),this.hidesOnEsc&&this._handleHidesOnEsc({phase:e}),this.hidesOnOutsideEsc&&this._handleHidesOnOutsideEsc({phase:e}),this.hidesOnOutsideClick&&this._handleHidesOnOutsideClick({phase:e}),this.handlesAccessibility&&this._handleAccessibility({phase:e}),this.inheritsReferenceWidth&&this._handleInheritsReferenceWidth(),this.visibilityTriggerFunction&&this._handleVisibilityTriggers({phase:e})}_handleVisibilityTriggers({phase:e}){typeof this.visibilityTriggerFunction==`function`&&(e===`init`&&(this.__visibilityTriggerHandler=this.visibilityTriggerFunction({phase:e,controller:this})),this.__visibilityTriggerHandler[e]&&this.__visibilityTriggerHandler[e]())}_handlePreventsScroll({phase:e}){switch(e){case`show`:this.manager.requestToPreventScroll();break;case`hide`:this.manager.requestToEnableScroll();break}}_handleBlocking({phase:e}){switch(e){case`show`:this.manager.requestToShowOnly(this);break;case`hide`:this.manager.retractRequestToShowOnly(this);break}}get hasActiveBackdrop(){return this.__hasActiveBackdrop}_handleBackdrop({phase:e}){switch(e){case`init`:this.__backdropInitialized||=(this.config?.backdropNode||(this.__backdropNode=document.createElement(`div`),this.__backdropNode.classList.add(`overlays__backdrop`)),this.__wrappingDialogNode.prepend(this.backdropNode),!0);break;case`show`:this.config.hasBackdrop&&this.backdropNode.classList.add(`overlays__backdrop--visible`),this.__hasActiveBackdrop=!0;break;case`hide`:case`teardown`:this.backdropNode.classList.remove(`overlays__backdrop--visible`),this.__hasActiveBackdrop=!1;break}}get hasActiveTrapsKeyboardFocus(){return this.__hasActiveTrapsKeyboardFocus}_handleTrapsKeyboardFocus({phase:e}){e===`show`?(`showModal`in this.__wrappingDialogNode&&(this.__wrappingDialogNode.close(),this.__wrappingDialogNode.showModal()),this.enableTrapsKeyboardFocus()):(e===`hide`||e===`teardown`)&&this.disableTrapsKeyboardFocus()}enableTrapsKeyboardFocus(){this.__hasActiveTrapsKeyboardFocus||(this.manager&&this.manager.disableTrapsKeyboardFocusForAll(),this.contentNode.shadowRoot&&console.warn(`[overlays]: For best accessibility (compatibility with Safari + VoiceOver), provide a contentNode that is not a host for a shadow root`),this._containFocusHandler=uu(this.contentNode),this.__hasActiveTrapsKeyboardFocus=!0,this.manager&&this.manager.informTrapsKeyboardFocusGotEnabled(this.placementMode))}disableTrapsKeyboardFocus({findNewTrap:e=!0}={}){this.__hasActiveTrapsKeyboardFocus&&(this._containFocusHandler&&=(this._containFocusHandler.disconnect(),void 0),this.__hasActiveTrapsKeyboardFocus=!1,this.manager&&this.manager.informTrapsKeyboardFocusGotDisabled({disabledCtrl:this,findNewTrap:e}))}__cancelHandler(e){e.preventDefault()}__escKeyHandler(e){e.key!==`Escape`||xu.has(e)||(e.composedPath().includes(this.contentNode)||su(this.contentNode,e.target))&&(this.hide(),xu.set(e,this))}#t=e=>{e.key===`Escape`&&(e.composedPath().includes(this.contentNode)||su(this.contentNode,e.target)||this.hide())};_handleHidesOnEsc({phase:e}){e===`show`?(this.contentNode.addEventListener(`keyup`,this.__escKeyHandler),this.invokerNode&&this.invokerNode.addEventListener(`keyup`,this.__escKeyHandler)):(e===`hide`||e===`teardown`)&&(this.contentNode.removeEventListener(`keyup`,this.__escKeyHandler),this.invokerNode&&this.invokerNode.removeEventListener(`keyup`,this.__escKeyHandler))}_handleHidesOnOutsideEsc({phase:e}){e===`show`?document.addEventListener(`keyup`,this.#t):(e===`hide`||e===`teardown`)&&document.removeEventListener(`keyup`,this.#t)}_handleInheritsReferenceWidth(){if(!this._referenceNode||this.placementMode===`global`)return;let e=`${this._referenceNode.getBoundingClientRect().width}px`;switch(this.inheritsReferenceWidth){case`max`:this.contentWrapperNode.style.maxWidth=e;break;case`full`:this.contentWrapperNode.style.width=e;break;case`min`:this.contentWrapperNode.style.minWidth=e,this.contentWrapperNode.style.width=`auto`;break}}_handleHidesOnOutsideClick({phase:e}){let t=e===`show`?`addEventListener`:`removeEventListener`;if(e===`show`){let e=!1,t=!1;this.__onInsideMouseDown=()=>{e=!0},this.__onInsideMouseUp=()=>{t=!0},this.__onDocumentMouseUp=()=>{setTimeout(()=>{!e&&!t&&this.hide(),e=!1,t=!1})},this.__onWindowBlur=()=>{setTimeout(()=>{this.hide()})}}this.contentWrapperNode[t](`mousedown`,this.__onInsideMouseDown,!0),this.contentWrapperNode[t](`mouseup`,this.__onInsideMouseUp,!0),this.invokerNode&&(this.invokerNode[t](`mousedown`,this.__onInsideMouseDown,!0),this.invokerNode[t](`mouseup`,this.__onInsideMouseUp,!0)),document.documentElement[t](`mouseup`,this.__onDocumentMouseUp,!0),window[t](`blur`,this.__onWindowBlur)}_handleAccessibility({phase:e}){(e===`init`||e===`teardown`)&&this.__setupTeardownAccessibility({phase:e});let t=this.trapsKeyboardFocus;this.invokerNode&&!this.isTooltip&&!t&&this.invokerNode.setAttribute(`aria-expanded`,`${e===`show`}`)}teardown(){this.__handleOverlayStyles({phase:`teardown`}),this._handleFeatures({phase:`teardown`}),this.#e()&&this.manager.remove(this)}async __createPopperInstance(){if(this._popper&&=(this._popper.destroy(),void 0),e.popperModule!==void 0){let{createPopper:t}=await e.popperModule;this._popper=t(this._referenceNode,this.contentWrapperNode,{...this.config?.popperConfig})}}_hasDisabledInvoker(){return this.invokerNode?this.invokerNode.disabled||this.invokerNode.getAttribute(`aria-disabled`)===`true`:!1}};Su.popperModule=void 0;function Cu(e,t){if(typeof e!=`object`||typeof t!=`object`||e===null||t===null)return e===t;let n=Object.keys(e),r=Object.keys(t);return n.length===r.length?n.every(n=>Cu(e[n],t[n])):!1}var wu=Or(e=>class extends e{static get properties(){return{opened:{type:Boolean,reflect:!0}}}#e=!1;constructor(){super(),this.opened=!1,this.config={},this.toggle=this.toggle.bind(this),this.open=this.open.bind(this),this.close=this.close.bind(this)}get config(){return this.__config}set config(e){let t=!Cu(this.config,e);this._overlayCtrl&&t&&this._overlayCtrl.updateConfig(e),this.__config=e,this._overlayCtrl&&t&&this.__syncToOverlayController()}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`opened`&&this.opened!==t&&this.dispatchEvent(new CustomEvent(`opened-changed`,{detail:{opened:this.opened}}))}_defineOverlay({contentNode:e,invokerNode:t,referenceNode:n,backdropNode:r,contentWrapperNode:i}){let a=this._defineOverlayConfig()||{};return new Su({contentNode:e,invokerNode:t,referenceNode:n,backdropNode:r,contentWrapperNode:i,...a,...this.config,popperConfig:{...a.popperConfig||{},...this.config?.popperConfig||{},modifiers:[...a.popperConfig?.modifiers||[],...this.config?.popperConfig?.modifiers||[]]}})}_defineOverlayConfig(){return{placementMode:`local`}}updated(e){super.updated(e),e.has(`opened`)&&this._overlayCtrl&&!this.__blockSyncToOverlayCtrl&&this.__syncToOverlayController()}_setupOpenCloseListeners(){this.__closeEventInContentNodeHandler=e=>{e.stopPropagation(),this._overlayCtrl.hide()},this._overlayContentNode&&this._overlayContentNode.addEventListener(`close-overlay`,this.__closeEventInContentNodeHandler)}_teardownOpenCloseListeners(){this._overlayContentNode&&this._overlayContentNode.removeEventListener(`close-overlay`,this.__closeEventInContentNodeHandler)}connectedCallback(){super.connectedCallback(),this.updateComplete.then(()=>{this.isConnected&&(this.#e||=(this._setupOverlayCtrl(),!0))})}async disconnectedCallback(){super.disconnectedCallback(),await this._isPermanentlyDisconnected()&&(this._teardownOverlayCtrl(),this.#e=!1)}static enabledWarnings=super.enabledWarnings?.filter(e=>e!==`change-in-update`)||[];get _overlayInvokerNode(){return Array.from(this.children).find(e=>e.slot===`invoker`)}get _overlayReferenceNode(){}get _overlayBackdropNode(){return this.__cachedOverlayBackdropNode||=Array.from(this.children).find(e=>e.slot===`backdrop`),this.__cachedOverlayBackdropNode}get _overlayContentNode(){return this._cachedOverlayContentNode||=Array.from(this.children).find(e=>e.slot===`content`)||this.config.contentNode,this._cachedOverlayContentNode}get _overlayContentWrapperNode(){return this.shadowRoot?.querySelector(`#overlay-content-node-wrapper`)}_setupOverlayCtrl(){if(this.#e)return;let e={contentNode:this._overlayContentNode,contentWrapperNode:this._overlayContentWrapperNode,invokerNode:this._overlayInvokerNode,referenceNode:this._overlayReferenceNode,backdropNode:this._overlayBackdropNode};this._overlayCtrl?this._overlayCtrl.updateConfig(e):this._overlayCtrl=this._defineOverlay(e),this.__syncToOverlayController(),this.__setupSyncFromOverlayController(),this._setupOpenCloseListeners()}_teardownOverlayCtrl(){this._overlayCtrl&&(this._teardownOpenCloseListeners(),this.__teardownSyncFromOverlayController(),this._overlayCtrl.teardown())}async _setOpenedWithoutPropertyEffects(e){this.__blockSyncToOverlayCtrl=!0,this.opened=e,await this.updateComplete,this.__blockSyncToOverlayCtrl=!1}__setupSyncFromOverlayController(){this.__onOverlayCtrlShow=()=>{this.opened=!0},this.__onOverlayCtrlHide=()=>{this.opened=!1},this.__onBeforeShow=e=>{let t=new CustomEvent(`before-opened`,{cancelable:!0});this.dispatchEvent(t),t.defaultPrevented&&(this._setOpenedWithoutPropertyEffects(this._overlayCtrl.isShown),e.preventDefault())},this.__onBeforeHide=e=>{let t=new CustomEvent(`before-closed`,{cancelable:!0});this.dispatchEvent(t),t.defaultPrevented&&(this._setOpenedWithoutPropertyEffects(this._overlayCtrl.isShown),e.preventDefault())},this._overlayCtrl.addEventListener(`show`,this.__onOverlayCtrlShow),this._overlayCtrl.addEventListener(`hide`,this.__onOverlayCtrlHide),this._overlayCtrl.addEventListener(`before-show`,this.__onBeforeShow),this._overlayCtrl.addEventListener(`before-hide`,this.__onBeforeHide)}__teardownSyncFromOverlayController(){this._overlayCtrl.removeEventListener(`show`,this.__onOverlayCtrlShow),this._overlayCtrl.removeEventListener(`hide`,this.__onOverlayCtrlHide),this._overlayCtrl.removeEventListener(`before-show`,this.__onBeforeShow),this._overlayCtrl.removeEventListener(`before-hide`,this.__onBeforeHide)}__syncToOverlayController(){this.opened?this._overlayCtrl.show():this._overlayCtrl.hide()}async toggle(){await this._overlayCtrl.toggle()}async open(){await this._overlayCtrl.show()}async close(){await this._overlayCtrl.hide()}repositionOverlay(){let e=this._overlayCtrl;e.placementMode===`local`&&e._popper&&e._popper.update()}async _isPermanentlyDisconnected(){return await this.updateComplete,!this.isConnected}});function Tu(){return{visibilityTriggerFunction:({controller:e})=>{function t(){e._hasDisabledInvoker()||e.toggle()}return{init:()=>{e.invokerNode?.addEventListener(`click`,t)},teardown:()=>{e.invokerNode?.removeEventListener(`click`,t)}}}}}var Eu=()=>({placementMode:`local`,inheritsReferenceWidth:`min`,hidesOnOutsideClick:!0,hidesOnEsc:!0,popperConfig:{placement:`bottom-start`,modifiers:[{name:`offset`,enabled:!1}]},handlesAccessibility:!0,...Tu()}),Du=class extends wu(b){_defineOverlayConfig(){return{...Eu()}}_addEventListeners(){this.actionItems.forEach(e=>{e.addEventListener(`click`,e=>{e.target?.dispatchEvent(new Event(`close-overlay`,{bubbles:!0}))})})}_setupInvoker(){let e=this.invokerNodes[0];e&&(e.setAttribute(`id`,`invoker-${this.uid}`),e.setAttribute(`aria-controls`,`content-${this.uid}`))}_setupContent(){let e=this.contentNodes[0];e&&(e.setAttribute(`id`,`content-${this.uid}`),e.setAttribute(`role`,`none`))}_setupOverlayCtrl(){super._setupOverlayCtrl(),this._setupInvoker(),this._setupContent()}firstUpdated(){this.uid=Kr(),this._addEventListeners()}render(){return p`
      <slot name="invoker"></slot>
      <slot name="backdrop"></slot>
      <slot name="content"></slot>
    `}};Du.styles=h`
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
  `,d([T({selector:`craft-action-item`})],Du.prototype,`actionItems`,void 0),d([T({slot:`invoker`})],Du.prototype,`invokerNodes`,void 0),d([T({slot:`content`})],Du.prototype,`contentNodes`,void 0),customElements.get(`craft-action-menu`)||customElements.define(`craft-action-menu`,Du);var Ou=new WeakMap;function ku(e,t){Array.from(e.childNodes).forEach(n=>{if(n.nodeName===`#text`){let r=RegExp(`^(.*?)(${t})(.*)$`,`i`),i=n.nodeValue.match(r);if(i){let t=document.createTextNode(i[1]);e.appendChild(t);let r=document.createElement(`b`);r.textContent=i[2],e.appendChild(r);let a=document.createTextNode(i[3]);e.appendChild(a),e.removeChild(n),Ou.set(e,()=>{e.appendChild(n),e.contains(t)&&t.parentNode!==null&&t.parentNode.removeChild(t),e.contains(r)&&r.parentNode!==null&&r.parentNode.removeChild(r),e.contains(a)&&a.parentNode!==null&&a.parentNode.removeChild(a)})}}else ku(n,t)})}function Au(e){Ou.has(e)&&Ou.get(e)(),Array.from(e.childNodes).forEach(e=>{e.nodeName===`#text`?Ou.has(e)&&Ou.get(e)():Au(e)})}var ju=class extends Do{static get validatorName(){return`MatchesOption`}execute(e,t,n){return n?.node.modelValue instanceof So}};function Mu(e){return Array.isArray(e)?e:[e]}var Nu=Or(e=>class extends Ro(e){static get properties(){return{allowCustomChoice:{type:Boolean,attribute:`allow-custom-choice`},modelValue:{type:Object}}}get modelValue(){return this.__getChoicesFrom(super.modelValue)}set modelValue(e){if(super.modelValue=e,e==null||e===``)this._customChoices=new Set;else if(this.allowCustomChoice){let t=this.modelValue;this._customChoices=new Set(Mu(e)),this.requestUpdate(`modelValue`,t)}}get formattedValue(){return this.__getChoicesFrom(super.formattedValue)}set formattedValue(e){if(super.formattedValue=e,e==null)this._customChoices=new Set;else if(this.allowCustomChoice){let t=this.modelValue;this._customChoices=new Set(Mu(e).map(e=>this.formElements.find(t=>t.formattedValue===e)?.modelValue||e)),this.requestUpdate(`modelValue`,t)}}get serializedValue(){return this.__getChoicesFrom(super.serializedValue)}set serializedValue(e){if(super.serializedValue=e,e==null)this._customChoices=new Set;else if(this.allowCustomChoice){let t=this.modelValue;this._customChoices=new Set(Mu(e).map(e=>this.formElements.find(t=>t.serializedValue===e)?.modelValue||e)),this.requestUpdate(`modelValue`,t)}}get customChoices(){if(!this.allowCustomChoice)return[];let e=this._getCheckedElements();return Array.from(this._customChoices).filter(t=>!e.some(e=>e.choiceValue===t))}constructor(){super(),this.allowCustomChoice=!1,this._customChoices=new Set}__getChoicesFrom(e){let t=e;return this.allowCustomChoice?this.multipleChoice?[...Mu(t),...this.customChoices]:t===``?this._customChoices.values().next().value||``:t:t}_isEmpty(){return super._isEmpty()&&this._customChoices.size===0}clear(){this._customChoices=new Set,super.clear()}parser(e){return this.allowCustomChoice&&Array.isArray(e)?e.filter(e=>e.trim()!==``):e}}),Pu=new WeakMap,Fu=class extends co(wu(Nu(_c))){static get properties(){return{autocomplete:{type:String,reflect:!0},matchMode:{type:String,attribute:`match-mode`},showAllOnEmpty:{type:Boolean,attribute:`show-all-on-empty`},requireOptionMatch:{type:Boolean},allowCustomChoice:{type:Boolean,attribute:`allow-custom-choice`},__shouldAutocompleteNextUpdate:Boolean}}static get styles(){return[...super.styles,h`
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
      `]}static get localizeNamespaces(){return[{"lion-combobox":e=>{switch(e){case`bg-BG`:case`bg`:return O(()=>import(`./bg.js`),[],import.meta.url);case`cs-CZ`:case`cs`:return O(()=>import(`./cs.js`),[],import.meta.url);case`de-AT`:case`de-DE`:case`de`:return O(()=>import(`./de.js`),[],import.meta.url);case`en-AU`:case`en-GB`:case`en-PH`:case`en-US`:case`en`:return O(()=>import(`./en.js`),[],import.meta.url);case`es-ES`:case`es`:return O(()=>import(`./es.js`),[],import.meta.url);case`fr-FR`:case`fr-BE`:case`fr`:return O(()=>import(`./fr.js`),[],import.meta.url);case`hu-HU`:case`hu`:return O(()=>import(`./hu.js`),[],import.meta.url);case`it-IT`:case`it`:return O(()=>import(`./it.js`),[],import.meta.url);case`nl-BE`:case`nl-NL`:case`nl`:return O(()=>import(`./nl.js`),[],import.meta.url);case`pl-PL`:case`pl`:return O(()=>import(`./pl.js`),[],import.meta.url);case`ro-RO`:case`ro`:return O(()=>import(`./ro.js`),[],import.meta.url);case`ru-RU`:case`ru`:return O(()=>import(`./ru.js`),[],import.meta.url);case`sk-SK`:case`sk`:return O(()=>import(`./sk.js`),[],import.meta.url);case`uk-UA`:case`uk`:return O(()=>import(`./uk.js`),[],import.meta.url);case`zh-CN`:case`zh`:return O(()=>import(`./zh.js`),[],import.meta.url);default:return O(()=>import(`./en.js`),[],import.meta.url)}}},...super.localizeNamespaces]}get modelValue(){let e=super.modelValue;return e===``?this.parser(this.value):e}set modelValue(e){super.modelValue=e}get value(){return this._inputNode?.value||this.__value||``}set value(e){this._inputNode?(this._inputNode.value=e,this.__value=void 0):this.__value=e}reset(){super.reset(),this.multipleChoice||(this.value=this._initialModelValue),this._resetListboxOptions()}_resetListboxOptions(){this.formElements.forEach((e,t)=>{this._unhighlightMatchedOption(e),!this.showAllOnEmpty||!this.opened?e.style.display=`none`:(e.style.display=``,e.setAttribute(`aria-posinset`,`${t+1}`),e.setAttribute(`aria-setsize`,`${this.formElements.length}`),e.removeAttribute(`aria-hidden`))})}_inputGroupInputTemplate(){return p`
      <div class="input-group__input">
        <slot name="selection-display"></slot>
        <slot name="input"></slot>
      </div>
    `}_overlayListboxTemplate(){return p`
      <div
        id="overlay-content-node-wrapper"
        role="dialog"
        aria-label="${this.msgLit(`lion-combobox:optionsPopup`)}"
      >
        <slot name="listbox"></slot>
      </div>
      <slot id="options-outlet"></slot>
    `}_groupTwoTemplate(){return p` ${super._groupTwoTemplate()} ${this._overlayListboxTemplate()}`}get slots(){return{...super.slots,input:()=>{if(this._ariaVersion===`1.1`){let e=document.createElement(`div`),t=document.createElement(`input`);return t.style.cssText=`
          border: none;
          outline: none;
          width: 100%;
          height: 100%;
          font: inherit;
          background: inherit;
          color: inherit;
          border-radius: inherit;
          box-sizing: border-box;
          padding: 0;`,e.appendChild(t),e}return document.createElement(`input`)},listbox:super.slots.input}}get _comboboxNode(){return this.querySelector(`[slot="input"]`)}get _selectionDisplayNode(){return this.querySelector(`[slot="selection-display"]`)}get _inputNode(){return this._ariaVersion===`1.1`&&this._comboboxNode&&this._comboboxNode.querySelector(`input`)||this._comboboxNode}get _overlayContentNode(){return this._listboxNode}get _overlayReferenceNode(){return this.shadowRoot.querySelector(`.input-group__container`)}get _overlayInvokerNode(){return this._inputNode}get _listboxNode(){return this._overlayCtrl&&this._overlayCtrl.contentNode||Array.from(this.children).find(e=>e.slot===`listbox`)}get _activeDescendantOwnerNode(){return this._inputNode}get requireOptionMatch(){return!this.allowCustomChoice}set requireOptionMatch(e){this.allowCustomChoice=!e}constructor(){super(),this.autocomplete=`both`,this.matchMode=`all`,this.showAllOnEmpty=!1,this.requireOptionMatch=!0,this.rotateKeyboardNavigation=!0,this.selectionFollowsFocus=!0,this.defaultValidators.push(new ju),this._ariaVersion=Gr.isChromium?`1.1`:`1.0`,this._listboxReceivesNoFocus=!0,this._noTypeAhead=!0,this.__prevCboxValueNonSelected=``,this.__prevCboxValue=``,this.__hadUserIntendsInlineAutoFill=!1,this.__listboxContentChanged=!1,this._onKeyUp=this._onKeyUp.bind(this),this._textboxOnClick=this._textboxOnClick.bind(this),this._textboxOnInput=this._textboxOnInput.bind(this),this._textboxOnKeydown=this._textboxOnKeydown.bind(this)}connectedCallback(){super.connectedCallback(),this._selectionDisplayNode&&(this._selectionDisplayNode.comboboxElement=this),(this.disabled||this.readOnly)&&this.__setComboboxDisabledAndReadOnly()}requestUpdate(e,t,n){if(super.requestUpdate(e,t,n),(e===`disabled`||e===`readOnly`)&&this.__setComboboxDisabledAndReadOnly(),e===`modelValue`&&this.modelValue&&this.modelValue!==t&&this._syncToTextboxCondition(this.modelValue,this._oldModelValue))if(this.multipleChoice)this._syncToTextboxMultiple(this.modelValue,this._oldModelValue);else{let e=this._getTextboxValueFromOption(this.formElements[this.checkedIndex]);this._setTextboxValue(e)}}parser(e){return this.requireOptionMatch&&this.checkedIndex===-1&&e!==``&&!Array.isArray(e)?new So(e):super.parser(e)}__unsyncCheckedIndexOnInputChange(){let e=this._autoSelectCondition(),t=this.formElements[this.checkedIndex];if(!this.multipleChoice&&!e&&t){let e=this._getTextboxValueFromOption(t);this._inputNode.value.startsWith(e)||this.setCheckedIndex(-1)}}updated(e){super.updated(e),e.has(`__shouldAutocompleteNextUpdate`)&&this.__unsyncCheckedIndexOnInputChange(),e.has(`opened`)&&(this.opened&&(this.activeIndex=-1),!this.opened&&e.get(`opened`)!==void 0&&(this.__onOverlayClose(),this.activeIndex=-1)),e.has(`autocomplete`)&&this._inputNode.setAttribute(`aria-autocomplete`,this.autocomplete),e.has(`disabled`)&&this.setAttribute(`aria-disabled`,`${this.disabled}`),e.has(`__shouldAutocompleteNextUpdate`)&&this.__shouldAutocompleteNextUpdate&&(this._handleAutocompletion(),this.__shouldAutocompleteNextUpdate=!1,this.__listboxContentChanged=!1),typeof this._selectionDisplayNode?.onComboboxElementUpdated==`function`&&this._selectionDisplayNode.onComboboxElementUpdated(e)}matchCondition(e,t){let n=-1,r=this._getTextboxValueFromOption(e);return typeof r==`string`&&typeof t==`string`&&(n=r.toLowerCase().indexOf(t.toLowerCase())),this.matchMode===`all`?n>-1:n===0}_showOverlayCondition({lastKey:e}){return this.disabled||this.readOnly||e&&([`Tab`,`Escape`].includes(e)||!this.multipleChoice&&[`Enter`].includes(e))?!1:this.filled||this.showAllOnEmpty||!this.filled&&this.multipleChoice&&this.__prevCboxValueNonSelected?!0:this.opened}_getTextboxValueFromOption(e){return e?e.choiceValue:this.modelValue instanceof So?this.modelValue.viewValue:this.modelValue}_onListboxContentChanged(){super._onListboxContentChanged(),this.__shouldAutocompleteNextUpdate=!0,this.__listboxContentChanged=!0}_textboxOnInput(e){this.__shouldAutocompleteNextUpdate=!0,this.opened=this._showOverlayCondition({})}_textboxOnKeydown(e){e.key===`Tab`&&(this.opened=!1)}_listboxOnClick(e){super._listboxOnClick(e),this._inputNode.focus(),this.multipleChoice?(this._inputNode.value=``,this._resetListboxOptions()):(this.activeIndex=-1,this.opened=!1)}_setTextboxValue(e){this._inputNode&&this._inputNode.value!==e&&(this._inputNode.value=e)}__onOverlayClose(){this.multipleChoice?this._syncToTextboxMultiple(this.modelValue,this._oldModelValue):this.checkedIndex!==-1&&this._syncToTextboxCondition(this.modelValue,this._oldModelValue,{phase:`overlay-close`})&&(this._inputNode.value=this._getTextboxValueFromOption(this.formElements[this.checkedIndex]))}_repropagationCondition(e){return super._repropagationCondition(e)||this.formElements.every(e=>!e.checked)}_onFilterMatch(e,t){this._highlightMatchedOption(e,t),e.style.display=``}_highlightMatchedOption(e,t){if(ku(e,t),e.textContent){let t=document.createElement(`span`);t.setAttribute(`aria-label`,e.textContent.replace(/\s+/g,` `)),Array.from(e.childNodes).forEach(e=>{t.appendChild(e)}),e.appendChild(t),Pu.set(e,()=>{Array.from(t.childNodes).forEach(t=>{e.appendChild(t)}),e.contains(t)&&e.removeChild(t)})}}_onFilterUnmatch(e,t,n){this._unhighlightMatchedOption(e),e.style.display=`none`}_unhighlightMatchedOption(e){Au(e),Pu.has(e)&&Pu.get(e)()}__computeUserIntendsAutoFill({prevValue:e,curValue:t}){let n=e.length<t.length,r=e.length&&t.length&&e[0].toLowerCase()!==t[0].toLowerCase();return n||r||this.__listboxContentChanged&&this.__hadUserIntendsInlineAutoFill}_handleAutocompletion(){let e=this._inputNode.selectionStart!==this._inputNode.selectionEnd&&this._inputNode.value.length!==this._inputNode.selectionStart,t=this._inputNode.value,n=this._inputNode.selectionStart,r=e&&n?t.slice(0,n):t,i=e||this.__hadSelectionLastAutofill?this.__prevCboxValueNonSelected:this.__prevCboxValue,a=!r,o=[],s=!1,c=this.__computeUserIntendsAutoFill({prevValue:i,curValue:r}),l=this.autocomplete===`both`||this.autocomplete===`inline`,u=this._autoSelectCondition(),d=this.autocomplete===`inline`||this.autocomplete===`none`;this.formElements.forEach((e,t)=>{let n=this.matchCondition(e,r),f=!1;if(f=a?this.showAllOnEmpty:d||n,u&&!s&&n&&!e.disabled){let n=()=>{this.activeIndex=t,this.selectionFollowsFocus&&!this.multipleChoice&&this.setCheckedIndex(this.activeIndex),s=!0};if(c)if(l){let t=this._getTextboxValueFromOption(e);t&&typeof t==`string`&&typeof r==`string`&&t.toLowerCase().indexOf(r.toLowerCase())===0&&(this.__textboxInlineComplete(e),n())}else n()}e.onFilterUnmatch?e.onFilterUnmatch(r,i):this._onFilterUnmatch(e,r,i),e.setAttribute(`aria-hidden`,`true`),e.removeAttribute(`aria-posinset`),e.removeAttribute(`aria-setsize`),f&&(o.push(e),e.onFilterMatch?e.onFilterMatch(r):this._onFilterMatch(e,r))});let f=o.length;o.forEach((e,t)=>{e.setAttribute(`aria-posinset`,`${t+1}`),e.setAttribute(`aria-setsize`,`${f}`),e.removeAttribute(`aria-hidden`)}),u&&!s&&!this.multipleChoice&&(this.setCheckedIndex(-1),i!==r&&(this.activeIndex=-1),this.modelValue=this.parser(t)),this.__prevCboxValueNonSelected=r,this.__prevCboxValue=this._inputNode.value,this.__hadSelectionLastAutofill=this._inputNode.value.length!==this._inputNode.selectionStart,this.__hadUserIntendsInlineAutoFill=c,this._overlayCtrl&&this._overlayCtrl._popper&&this._overlayCtrl._popper.update()}__textboxInlineComplete(e=this.formElements[this.activeIndex]){let t=this._getTextboxValueFromOption(e);if(this._inputNode.value!==t){let e=this._inputNode.value.length;this._inputNode.value=t,this._inputNode.selectionStart=e,this._inputNode.selectionEnd=this._inputNode.value.length}}_autoSelectCondition(){return this.autocomplete===`both`||this.autocomplete===`inline`}_setupListboxNode(){super._setupListboxNode(),this._listboxNode.removeAttribute(`tabindex`)}_defineOverlayConfig(){return{...Eu(),elementToFocusAfterHide:void 0,invokerNode:this._comboboxNode,visibilityTriggerFunction:void 0}}_setupOverlayCtrl(){super._setupOverlayCtrl(),this.__shouldAutocompleteNextUpdate=!0,this.__setupCombobox()}_teardownOverlayCtrl(){super._teardownOverlayCtrl(),this.__teardownCombobox()}_setupOpenCloseListeners(){super._setupOpenCloseListeners(),this._inputNode.addEventListener(`keyup`,this._onKeyUp),this._inputNode.addEventListener(`click`,this._textboxOnClick)}_teardownOpenCloseListeners(){super._teardownOpenCloseListeners(),this._inputNode.removeEventListener(`keyup`,this._onKeyUp),this._inputNode.removeEventListener(`click`,this._textboxOnClick)}_listboxOnKeyDown(e){let{key:t}=e;switch(t){case`Escape`:this.opened=!1,super._listboxOnKeyDown(e),this._setTextboxValue(``);break;case`Backspace`:case`Delete`:this.requireOptionMatch?super._listboxOnKeyDown(e):this.opened=!1;break;case`Enter`:this.opened&&e.preventDefault(),!this.requireOptionMatch&&this.multipleChoice&&(!this.formElements[this.activeIndex]||this.formElements[this.activeIndex].hasAttribute(`aria-hidden`)||!this.opened)?(this.modelValue=this.parser([...this.modelValue,this._inputNode.value]),this._inputNode.value=``,this.opened=!1):(super._listboxOnKeyDown(e),this._resetListboxOptions()),this.multipleChoice?this._inputNode.value=``:this.opened=!1;break;default:super._listboxOnKeyDown(e);break}}_syncToTextboxCondition(e,t,{phase:n}={}){return this.autocomplete===`both`||this.autocomplete===`inline`||!this.focused}_syncToTextboxMultiple(e,t=[]){if(this.requireOptionMatch){let n=e.filter(e=>!t.includes(e)),r=this.formElements.filter(e=>n.includes(e.choiceValue)).map(e=>this._getTextboxValueFromOption(e)).join(` `);this._setTextboxValue(r)}}_enhanceLightDomClasses(){let e=this.querySelector(`[slot=input]`);e&&e.classList.add(`form-control`)}__setComboboxDisabledAndReadOnly(){this._comboboxNode&&(this._comboboxNode.toggleAttribute(`disabled`,this.disabled),this._comboboxNode.setAttribute(`aria-disabled`,`${this.disabled}`),this._comboboxNode.toggleAttribute(`readonly`,this.readOnly),this._comboboxNode.setAttribute(`aria-readonly`,`${this.readOnly}`)),this._inputNode&&(this._inputNode.toggleAttribute(`disabled`,this.disabled),this._inputNode.toggleAttribute(`readOnly`,this.readOnly),this._inputNode.setAttribute(`aria-readonly`,`${this.readOnly}`),this._inputNode.tabIndex=this.disabled?-1:0)}__setupCombobox(){this._comboboxNode.setAttribute(`role`,`combobox`),this._comboboxNode.setAttribute(`aria-haspopup`,`listbox`),this._inputNode.setAttribute(`aria-autocomplete`,this.autocomplete),this._comboboxNode.setAttribute(`aria-controls`,this._listboxNode.id),this._ariaVersion===`1.1`?this._comboboxNode.setAttribute(`aria-owns`,this._listboxNode.id):this._inputNode.setAttribute(`aria-owns`,this._listboxNode.id),this._listboxNode.setAttribute(`aria-labelledby`,this._labelNode.id),this._inputNode.addEventListener(`keydown`,this._listboxOnKeyDown),this._inputNode.addEventListener(`input`,this._textboxOnInput),this._inputNode.addEventListener(`keydown`,this._textboxOnKeydown)}__teardownCombobox(){this._inputNode.removeEventListener(`keydown`,this._listboxOnKeyDown),this._inputNode.removeEventListener(`input`,this._textboxOnInput),this._inputNode.removeEventListener(`keydown`,this._textboxOnKeydown)}_onKeyUp(e){let t=e&&e.key;this.opened=this._showOverlayCondition({lastKey:t,currentValue:this._inputNode.value})}_textboxOnClick(e){this.opened=this._showOverlayCondition({})}clear(){this.value=``,super.clear(),this.__shouldAutocompleteNextUpdate=!0}},Iu=h`
  ${ba}

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
    ${ya}
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
`,Lu=class extends Fu{static get styles(){return[...super.styles,Iu]}constructor(){super(),this.defaultValidators=[]}_inputGroupInputTemplate(){return p`
      <div class="input-group__input">
        <slot name="input"></slot>
        <craft-icon
          class="indicator"
          name="chevron-down"
          style="font-size: 0.8em"
        ></craft-icon>
      </div>
    `}parser(e){return e===``?super.parser(e):e}_getTextboxValueFromOption(e){return e?e.textContent?.trim()||``:super._getTextboxValueFromOption(e)}};customElements.get(`craft-combobox`)||customElements.define(`craft-combobox`,Lu);var Ru=class extends b{constructor(...e){super(...e),this.variant=o.Default,this.label=null}render(){return p`<span
      class="${D({indicator:!0,"indicator--success":this.variant===o.Success,"indicator--danger":this.variant===o.Danger,"indicator--warning":this.variant===o.Warning,"indicator--info":this.variant===o.Info,"indicator--empty":this.variant===`empty`})}"
    >
      <slot></slot>
    </span>`}};Ru.styles=[zl,h`
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
    `],d([x({reflect:!0})],Ru.prototype,`variant`,void 0),d([x()],Ru.prototype,`label`,void 0),customElements.get(`craft-indicator`)||customElements.define(`craft-indicator`,Ru);var zu=class extends b{constructor(){super(),this.alt=!1,this.shift=!1,this.os=`Unknown`,this.os=this.detectOS()}connectedCallback(){super.connectedCallback(),this.os===`Unknown`&&(this.os=this.detectOS())}detectOS(){let e=navigator.platform.toLowerCase();return e.includes(`mac`)||/iphone|ipad|ipod/.test(e)?`Mac`:e.includes(`win`)?`Windows`:e.includes(`linux`)?`Linux`:`Unknown`}renderShortcutPrefix(){switch(this.os){case`Mac`:return`${this.alt?`⌥`:``}${this.shift?`⇧`:``}⌘`;case`Linux`:return`Super+${this.alt?`Alt+`:``}${this.shift?`Shift+`:``}`;default:return`Ctrl+${this.alt?`Alt+`:``}${this.shift?`Shift+`:``}`}}render(){return p`<span class="shortcut"
      >${this.renderShortcutPrefix()}<slot></slot
    ></span>`}};zu.styles=h`
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
  `,d([x({type:Boolean})],zu.prototype,`alt`,void 0),d([x({type:Boolean})],zu.prototype,`shift`,void 0),d([x()],zu.prototype,`os`,void 0),customElements.get(`craft-shortcut`)||customElements.define(`craft-shortcut`,zu);var Bu=h`
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
`,Vu=new WeakMap,Hu=class extends b{constructor(...e){super(...e),this.progress=0,this.total=0,this.processed=0,this.showStatus=!1,this.pending=!1,this.smooth=!1,this.label=`Progress`,u(this,Vu,0)}updated(e){if((e.has(`total`)||e.has(`processed`))&&this.total>0){let e=Math.min(100,Math.round(this.processed/this.total*100));e>=100&&l(Vu,this)<100&&this.dispatchEvent(new CustomEvent(`complete`,{bubbles:!0,composed:!0})),this.progress=e}e.has(`progress`)&&(this.progress>0&&this.pending&&(this.pending=!1),a(Vu,this,this.progress))}get progressPercent(){return Math.min(100,Math.max(0,this.progress))}get statusText(){return this.total>0?`${this.processed} / ${this.total}`:`${this.progressPercent}%`}reset(){this.progress=0,this.processed=0,this.pending=!0,a(Vu,this,0)}show(){this.hidden=!1}hide(){this.hidden=!0}render(){let e={width:this.pending?`100%`:`${this.progressPercent}%`};return p`
      <div
        class=${D({"progress-bar":!0,"progress-bar--pending":this.pending})}
        part="track"
        role="progressbar"
        aria-valuenow=${this.pending?y:this.progressPercent}
        aria-valuemin="0"
        aria-valuemax="100"
        aria-label=${this.label}
      >
        <div
          class=${D({"progress-bar__fill":!0,"progress-bar__fill--smooth":this.smooth&&!this.pending})}
          part="fill"
          style=${ee(e)}
        ></div>
      </div>
      ${this.showStatus?p`<div class="progress-bar__status" part="status">
            ${this.statusText}
          </div>`:y}
      <span class="visually-hidden">
        ${this.pending?`Loading`:`${this.progressPercent}%`}
      </span>
    `}};Hu.styles=[Bu],d([x({type:Number})],Hu.prototype,`progress`,void 0),d([x({type:Number})],Hu.prototype,`total`,void 0),d([x({type:Number})],Hu.prototype,`processed`,void 0),d([x({type:Boolean,attribute:`show-status`})],Hu.prototype,`showStatus`,void 0),d([x({type:Boolean,reflect:!0})],Hu.prototype,`pending`,void 0),d([x({type:Boolean})],Hu.prototype,`smooth`,void 0),d([x({type:String})],Hu.prototype,`label`,void 0),customElements.get(`craft-progress-bar`)||customElements.define(`craft-progress-bar`,Hu);var Uu=class extends Ro(Ho(b)){connectedCallback(){super.connectedCallback(),this.setAttribute(`role`,`radiogroup`)}resetGroup(){let e;this.formElements.forEach(t=>{typeof t.resetGroup==`function`?t.resetGroup():typeof t.reset==`function`&&(t.reset(),t.checked&&(e=t.choiceValue))}),this.modelValue=e,this.resetInteractionState()}},Wu=class extends Bo(Uo){connectedCallback(){super.connectedCallback(),this.type=`radio`}},Gu=class extends Uu{static get styles(){return[...super.styles,xa,h`
        .input-group {
          display: grid;
          gap: var(--c-spacing-xs);
        }
      `]}};customElements.get(`craft-radio-group`)||customElements.define(`craft-radio-group`,Gu);var Ku=class extends Wu{static get styles(){return[...super.styles,h`
        /* same as checkbox, potentially consolidate */
        :host {
          gap: var(--c-spacing-sm);
        }
      `]}};customElements.get(`craft-radio`)||customElements.define(`craft-radio`,Ku);var qu=class e{constructor(t={}){this.config={...e.defaultCookieOptions,...t}}set(e,t,n={}){let{path:r,domain:i,maxAge:a,expires:o,secure:s,sameSite:c,prefix:l}=Object.assign({},this.config,n),u=`${this.config.prefix}:${e}=${encodeURIComponent(t)}`;r&&(u+=`;path=${r}`),i&&(u+=`;domain=${i}`),a?u+=`;max-age-in-seconds=${a}`:o&&(u+=`;expires=${o.toUTCString()}`),s&&(u+=`;secure`),document.cookie=u}get(e){return document.cookie.replace(RegExp(`(?:(?:^|.*;\\s*)${this.config.prefix}:${e}\\s*\\=\\s*([^;]*).*$)|^.*$`),`$1`)}remove(e){this.set(e,``,{expires:new Date(`1970-01-01T00:00:00`)})}};qu.defaultCookieOptions={path:`/`,domain:null,secure:!1,sameSite:`strict`,prefix:`Craft`};var Ju=class{constructor(){this.refreshPromise=null,this.tokenName=null,this.tokenValue=null,this.refreshPromise=null}async getToken(){return this.tokenValue||await this.refreshToken(),this.tokenValue}async refreshToken(){return this.refreshPromise||=Zu.get(`users/session-info`).then(({data:e})=>{let{csrfTokenName:t,csrfTokenValue:n}=e;return this.tokenName=t??null,this.tokenValue=n??null,this.tokenValue}).finally(()=>{this.refreshPromise=null}),this.refreshPromise}clearToken(){this.tokenValue=null}};function Yu(e=``){return`/admin/actions/${e}`}function Xu(){return{"X-Registered-Asset-Bundles":[...new Set(Cp.registeredAssetBundles)].join(`,`),"X-Registered-Js-Files":[...new Set(Cp.registeredJsFiles)].join(`,`)}}var Zu=c.create({baseURL:Yu()}),Qu=new Ju;Zu.interceptors.request.use(async e=>{e.headers.set(`X-Requested-With`,`XMLHttpRequest`);let t=Xu();return Object.entries(t).forEach(([t,n])=>{e.headers.set(t,n)}),e}),Zu.interceptors.response.use(e=>e,async e=>{let t=e.config;if(e.response?.status===419||e.response?.status===403&&!t._retry){t._retry=!0;try{return Qu.clearToken(),t.headers[`X-CSRF-Token`]=await Qu.refreshToken(),c(t)}catch(e){return console.error(`Failed to refresh CSRF token:`,e),Promise.reject(e)}}return Promise.reject(e)});var $u=!1,ed=null;async function td(e){if(!$u){if(ed)return ed;$u=!0;try{return(await Zu.post(`app/api-headers`,void 0,{cancelToken:e})).data}catch{}finally{$u=!1}}}var nd=c.create({baseURL:`https://api.craftcms.com/v1/`});async function rd(e){return ed?Object.entries(ed).forEach(([t,n])=>{e.headers.set(t,n)}):(e.params=e.params||{},e.params.processCraftHeaders=1),e}async function id(e,t){if(ed)return;let{data:n}=await Zu.post(`app/process-api-response-headers`,{headers:e},{cancelToken:t});return ed=n,$u=!1,ed}async function ad(e){return await id(e.headers,e.config.cancelToken),e}nd.interceptors.request.use(async e=>{let{cancelToken:t}=e,n=await td(t);n&&Object.entries(n).forEach(([t,n])=>{e.headers.set(t,n)});let r={...e,params:{...Cp.apiParams||{},...e.params,v:new Date().getTime()}};return n||(r.params.processCraftHeaders=1),Cp.httpProxy&&(r.proxy=Cp.httpProxy),r}),nd.interceptors.request.use(rd),nd.interceptors.response.use(ad);var od={Á:`A`,á:`a`,Ä:`A`,ä:`a`,À:`A`,à:`a`,Â:`A`,â:`a`,É:`E`,é:`e`,Ë:`E`,ë:`e`,È:`E`,è:`e`,Ê:`E`,ê:`e`,Í:`I`,í:`i`,Ï:`I`,ï:`i`,Ì:`I`,ì:`i`,Î:`I`,î:`i`,Ó:`O`,ó:`o`,Ö:`O`,ö:`o`,Ò:`O`,ò:`o`,Ô:`O`,ô:`o`,Ú:`U`,ú:`u`,Ü:`U`,ü:`u`,Ù:`U`,ù:`u`,Û:`U`,û:`u`,Ý:`Y`,ý:`y`,Ÿ:`Y`,А:`A`,Б:`B`,В:`V`,Г:`G`,Д:`D`,Ѓ:`Gj`,Е:`E`,Ж:`Z`,З:`Z`,Ѕ:`Dz`,И:`I`,Ј:`j`,К:`K`,Л:`L`,Љ:`Lj`,М:`M`,Н:`N`,Њ:`Nj`,О:`O`,П:`P`,Р:`R`,С:`S`,Т:`T`,Ќ:`Kj`,У:`U`,Ф:`F`,Х:`X`,Ц:`C`,Ч:`C`,Џ:`Dz`,Ш:`S`,а:`a`,б:`b`,в:`v`,г:`g`,д:`d`,ѓ:`gj`,е:`e`,ж:`z`,з:`z`,ѕ:`dz`,и:`i`,ј:`j`,к:`k`,л:`l`,љ:`lj`,м:`m`,н:`n`,њ:`nj`,о:`o`,п:`p`,р:`r`,с:`s`,т:`t`,ќ:`kj`,у:`u`,ф:`f`,х:`x`,ц:`c`,ч:`c`,џ:`dz`,ш:`s`,æ:`ae`,ǽ:`ae`,Ã:`A`,Å:`A`,Ǻ:`A`,Ă:`A`,Ǎ:`A`,Æ:`AE`,Ǽ:`AE`,ã:`a`,å:`a`,ǻ:`a`,ă:`a`,ǎ:`a`,ª:`a`,Ĉ:`C`,Ċ:`C`,Ç:`C`,ç:`c`,ĉ:`c`,ċ:`c`,Ð:`D`,Đ:`D`,ð:`d`,đ:`d`,Ĕ:`E`,Ė:`E`,ĕ:`e`,ė:`e`,ƒ:`f`,Ĝ:`G`,Ġ:`G`,ĝ:`g`,ġ:`g`,Ĥ:`H`,Ħ:`H`,ĥ:`h`,ħ:`h`,Ĩ:`I`,Ĭ:`I`,Ǐ:`I`,Į:`I`,Ĳ:`IJ`,ĩ:`i`,ĭ:`i`,ǐ:`i`,į:`i`,ĳ:`ij`,Ĵ:`J`,ĵ:`j`,Ĺ:`L`,Ľ:`L`,Ŀ:`L`,ĺ:`l`,ľ:`l`,ŀ:`l`,Ñ:`N`,ñ:`n`,ŉ:`n`,Õ:`O`,Ō:`O`,Ŏ:`O`,Ǒ:`O`,Ő:`O`,Ơ:`O`,Ø:`O`,Ǿ:`O`,Œ:`OE`,õ:`o`,ō:`o`,ŏ:`o`,ǒ:`o`,ő:`o`,ơ:`o`,ø:`o`,ǿ:`o`,º:`o`,œ:`oe`,Ŕ:`R`,Ŗ:`R`,ŕ:`r`,ŗ:`r`,Ŝ:`S`,Ș:`S`,ŝ:`s`,ș:`s`,ſ:`s`,Ţ:`T`,Ț:`T`,Ŧ:`T`,Þ:`TH`,ţ:`t`,ț:`t`,ŧ:`t`,þ:`th`,Ũ:`U`,Ŭ:`U`,Ű:`U`,Ų:`U`,Ư:`U`,Ǔ:`U`,Ǖ:`U`,Ǘ:`U`,Ǚ:`U`,Ǜ:`U`,ũ:`u`,ŭ:`u`,ű:`u`,ų:`u`,ư:`u`,ǔ:`u`,ǖ:`u`,ǘ:`u`,ǚ:`u`,ǜ:`u`,Ŵ:`W`,ŵ:`w`,Ŷ:`Y`,ÿ:`y`,ŷ:`y`,ΑΥ:`AU`,ΑΎ:`AU`,Αυ:`Au`,Αύ:`Au`,ΕΊ:`I`,ΕΙ:`I`,Ει:`Ei`,ΕΥ:`EF`,ΕΎ:`EU`,Εί:`I`,Ευ:`Ef`,Εύ:`Eu`,ΟΙ:`I`,ΟΊ:`I`,ΟΥ:`U`,ΟΎ:`OU`,Οι:`Oi`,Οί:`I`,Ου:`Oy`,Ού:`Ou`,ΥΙ:`I`,ΎΙ:`I`,Υι:`Yi`,Ύι:`I`,ΥΊ:`I`,Υί:`I`,αυ:`au`,αύ:`au`,εί:`i`,ει:`ei`,ευ:`ef`,εύ:`eu`,οι:`oi`,οί:`i`,ου:`oy`,ού:`ou`,υι:`yi`,ύι:`i`,υί:`i`,Α:`A`,Ά:`A`,Β:`B`,Δ:`D`,Ε:`E`,Έ:`E`,Φ:`F`,Γ:`G`,Η:`H`,Ή:`I`,Ι:`I`,Ί:`I`,Ϊ:`I`,Κ:`K`,Ξ:`Ks`,Λ:`L`,Μ:`M`,Ν:`N`,Π:`P`,Ο:`O`,Ό:`O`,Ψ:`Ps`,Ρ:`R`,Σ:`S`,Τ:`T`,Θ:`Th`,Ω:`O`,Ώ:`W`,Χ:`X`,ϒ:`Y`,Υ:`Y`,Ύ:`Y`,Ϋ:`Y`,Ζ:`Z`,α:`a`,ά:`a`,β:`v`,δ:`d`,ε:`e`,έ:`e`,φ:`f`,γ:`gh`,η:`i`,ή:`i`,ι:`i`,ί:`i`,ϊ:`i`,ΐ:`i`,κ:`k`,ξ:`ks`,λ:`l`,μ:`m`,ν:`n`,ο:`o`,ό:`o`,π:`p`,ψ:`ps`,ρ:`r`,σ:`s`,ς:`s`,τ:`t`,ϑ:`th`,θ:`th`,ϐ:`v`,ω:`o`,ώ:`w`,χ:`kh`,υ:`i`,ύ:`y`,ΰ:`y`,ϋ:`y`,ζ:`z`,अ:`a`,आ:`aa`,ए:`e`,ई:`ii`,ऍ:`ei`,ऎ:`ae`,ऐ:`ai`,इ:`i`,ओ:`o`,ऑ:`oi`,ऒ:`oii`,ऊ:`uu`,औ:`ou`,उ:`u`,ब:`B`,भ:`Bha`,च:`Ca`,छ:`Chha`,ड:`Da`,ढ:`Dha`,फ:`Fa`,फ़:`Fi`,ग:`Ga`,घ:`Gha`,ग़:`Ghi`,ह:`Ha`,ज:`Ja`,झ:`Jha`,क:`Ka`,ख:`Kha`,ख़:`Khi`,ल:`L`,ळ:`Li`,ऌ:`Li`,ऴ:`Lii`,ॡ:`Lii`,म:`Ma`,न:`Na`,ङ:`Na`,ञ:`Nia`,ण:`Nae`,ऩ:`Ni`,ॐ:`oms`,प:`Pa`,क़:`Qi`,र:`Ra`,ऋ:`Ri`,ॠ:`Ri`,ऱ:`Ri`,स:`Sa`,श:`Sha`,ष:`Shha`,ट:`Ta`,त:`Ta`,ठ:`Tha`,द:`Tha`,थ:`Tha`,ध:`Thha`,ड़:`ugDha`,ढ़:`ugDhha`,व:`Va`,य:`Ya`,य़:`Yi`,ज़:`Za`,Ա:`A`,Բ:`B`,Գ:`G`,Դ:`D`,Ե:`E`,Զ:`Z`,Է:`E`,Ը:`Y`,Թ:`Th`,Ժ:`Zh`,Ի:`I`,Լ:`L`,Խ:`Kh`,Ծ:`Ts`,Կ:`K`,Հ:`H`,Ձ:`Dz`,Ղ:`Gh`,Ճ:`Tch`,Մ:`M`,Յ:`Y`,Ն:`N`,Շ:`Sh`,Ո:`Vo`,Չ:`Ch`,Պ:`P`,Ջ:`J`,Ռ:`R`,Ս:`S`,Վ:`V`,Տ:`T`,Ր:`R`,Ց:`C`,Ւ:`u`,Փ:`Ph`,Ք:`Q`,և:`ev`,Օ:`O`,Ֆ:`F`,ա:`a`,բ:`b`,գ:`g`,դ:`d`,ե:`e`,զ:`z`,է:`e`,ը:`y`,թ:`th`,ժ:`zh`,ի:`i`,լ:`l`,խ:`kh`,ծ:`ts`,կ:`k`,հ:`h`,ձ:`dz`,ղ:`gh`,ճ:`tch`,մ:`m`,յ:`y`,ն:`n`,շ:`sh`,ո:`vo`,չ:`ch`,պ:`p`,ջ:`j`,ռ:`r`,ս:`s`,վ:`v`,տ:`t`,ր:`r`,ց:`c`,ւ:`u`,փ:`ph`,ք:`q`,օ:`o`,ֆ:`f`,Ž:`Z`,Ň:`N`,Ş:`S`,ž:`z`,ň:`n`,ş:`s`,ı:`i`,İ:`I`,ğ:`g`,Ğ:`G`,ьо:`yo`,Й:`i`,Щ:`Shh`,Ъ:`Ie`,Ь:``,Ю:`Iu`,Я:`Ia`,й:`i`,щ:`shh`,ъ:`ie`,ь:``,ю:`iu`,я:`ia`,Ē:`E`,ē:`e`,န်ုပ်:`nub`,"ောင်":`aung`,"ိုက်":`aik`,"ိုဒ်":`ok`,"ိုင်":`aing`,"ိုလ်":`ol`,"ေါင်":`aung`,သြော:`aw`,"ောက်":`auk`,"ိတ်":`eik`,"ုတ်":`ok`,"ုန်":`on`,"ေတ်":`it`,"ုဒ်":`ait`,"ာန်":`an`,"ိန်":`ein`,"ွတ်":`ut`,"ေါ်":`aw`,"ွန်":`un`,"ိပ်":`eik`,"ုပ်":`ok`,"ွပ်":`ut`,"ိမ်":`ein`,"ုမ်":`on`,"ော်":`aw`,"ွမ်":`un`,က်:`et`,"ေါ":`aw`,"ော":`aw`,"ျွ":`ywa`,"ြွ":`yw`,"ို":`o`,"ုံ":`on`,တ်:`at`,င်:`in`,ည်:`i`,ဒ်:`d`,န်:`an`,ပ်:`at`,မ်:`an`,စျ:`za`,ယ်:`e`,ဉ်:`in`,စ်:`it`,"ိံ":`ein`,"ဲ":`e`,"း":``,"ာ":`a`,"ါ":`a`,"ေ":`e`,"ံ":`an`,"ိ":`i`,"ီ":`i`,"ု":`u`,"ူ":`u`,"်":`at`,"္":``,"့":``,က:`k`,"၉":`9`,တ:`t`,ရ:`ya`,ယ:`y`,မ:`m`,ဘ:`ba`,ဗ:`b`,ဖ:`pa`,ပ:`p`,န:`n`,ဓ:`da`,ဒ:`d`,ထ:`ta`,ဏ:`na`,ဝ:`w`,ဎ:`da`,ဍ:`d`,ဌ:`ta`,ဋ:`t`,ည:`ny`,ဇ:`z`,ဆ:`sa`,စ:`s`,င:`ng`,ဃ:`ga`,ဂ:`g`,လ:`l`,သ:`th`,"၈":`8`,ဩ:`aw`,ခ:`kh`,"၆":`6`,"၅":`5`,"၄":`4`,"၃":`3`,"၂":`2`,"၁":`1`,"၀":`0`,"၌":`hnaik`,"၍":`ywae`,ဪ:`aw`,ဦ:`-u`,ဟ:`h`,ဉ:`u`,ဤ:`-i`,ဣ:`i`,"၏":`-e`,ဧ:`e`,"ှ":`h`,"ွ":`w`,"ျ":`ya`,"ြ":`y`,အ:`a`,ဠ:`la`,"၇":`7`,DŽ:`DZ`,Dž:`Dz`,dž:`dz`,Ǳ:`DZ`,ǲ:`Dz`,ǳ:`dz`,Ǉ:`LJ`,ǈ:`Lj`,ǉ:`lj`,Ǌ:`NJ`,ǋ:`Nj`,ǌ:`nj`,č:`c`,Č:`C`,ć:`c`,Ć:`C`,š:`s`,Š:`S`,ა:`a`,ბ:`b`,გ:`g`,დ:`d`,ე:`e`,ვ:`v`,ზ:`z`,თ:`t`,ი:`i`,კ:`k`,ლ:`l`,მ:`m`,ნ:`n`,ო:`o`,პ:`p`,ჟ:`zh`,რ:`r`,ს:`s`,ტ:`t`,უ:`u`,ფ:`f`,ქ:`q`,ღ:`gh`,ყ:`y`,შ:`sh`,ჩ:`ch`,ც:`ts`,ძ:`dz`,წ:`ts`,ჭ:`ch`,ხ:`kh`,ჯ:`j`,ჰ:`h`,Ё:`E`,ё:`e`,Ы:`Y`,ы:`y`,Э:`E`,э:`e`,І:`I`,і:`i`,Ѳ:`F`,ѳ:`f`,Ѣ:`E`,ѣ:`e`,Ѵ:`I`,ѵ:`i`,Є:`Je`,є:`je`,Ѥ:`Je`,ѥ:`je`,Ꙋ:`U`,ꙋ:`u`,Ѡ:`O`,ѡ:`o`,Ѿ:`Ot`,ѿ:`ot`,Ѫ:`U`,ѫ:`u`,Ѧ:`Ja`,ѧ:`ja`,Ѭ:`Ju`,ѭ:`ju`,Ѩ:`Ja`,ѩ:`Ja`,Ѯ:`Ks`,ѯ:`ks`,Ѱ:`Ps`,ѱ:`ps`,Ґ:`G`,ґ:`g`,Ї:`Yi`,ї:`yi`,Ә:`A`,Ғ:`G`,Қ:`Q`,Ң:`N`,Ө:`O`,Ұ:`U`,Ү:`U`,Һ:`H`,ә:`a`,ғ:`g`,қ:`q`,ң:`n`,ө:`o`,ұ:`u`,ү:`u`,һ:`h`,ď:`d`,Ď:`D`,ě:`e`,Ě:`E`,ř:`r`,Ř:`R`,ť:`t`,Ť:`T`,ů:`u`,Ů:`U`,ą:`a`,ę:`e`,ł:`l`,ń:`n`,ś:`s`,ź:`z`,ż:`z`,Ą:`A`,Ę:`E`,Ł:`L`,Ń:`N`,Ś:`S`,Ź:`Z`,Ż:`Z`,ā:`a`,ģ:`g`,ī:`i`,ķ:`k`,ļ:`l`,ņ:`n`,ū:`u`,Ā:`A`,Ģ:`G`,Ī:`I`,Ķ:`k`,Ļ:`L`,Ņ:`N`,Ū:`U`,Ả:`A`,Ạ:`A`,Ắ:`A`,Ằ:`A`,Ẳ:`A`,Ẵ:`A`,Ặ:`A`,Ấ:`A`,Ầ:`A`,Ẩ:`A`,Ẫ:`A`,Ậ:`A`,ả:`a`,ạ:`a`,ắ:`a`,ằ:`a`,ẳ:`a`,ẵ:`a`,ặ:`a`,ấ:`a`,ầ:`a`,ẩ:`a`,ẫ:`a`,ậ:`a`,Ẻ:`E`,Ẽ:`E`,Ẹ:`E`,Ế:`E`,Ề:`E`,Ể:`E`,Ễ:`E`,Ệ:`E`,ẻ:`e`,ẽ:`e`,ẹ:`e`,ế:`e`,ề:`e`,ể:`e`,ễ:`e`,ệ:`e`,Ỉ:`I`,Ị:`I`,ỉ:`i`,ị:`i`,Ỏ:`O`,Ọ:`O`,Ố:`O`,Ồ:`O`,Ổ:`O`,Ỗ:`O`,Ộ:`O`,Ớ:`O`,Ờ:`O`,Ở:`O`,Ỡ:`O`,Ợ:`O`,ỏ:`o`,ọ:`o`,ố:`o`,ồ:`o`,ổ:`o`,ỗ:`o`,ộ:`o`,ớ:`o`,ờ:`o`,ở:`o`,ỡ:`o`,ợ:`o`,Ủ:`U`,Ụ:`U`,Ứ:`U`,Ừ:`U`,Ử:`U`,Ữ:`U`,Ự:`U`,ủ:`u`,ụ:`u`,ứ:`u`,ừ:`u`,ử:`u`,ữ:`u`,ự:`u`,Ỳ:`Y`,Ỷ:`Y`,Ỹ:`Y`,Ỵ:`Y`,ỳ:`y`,ỷ:`y`,ỹ:`y`,ỵ:`y`,ا:`a`,ب:`b`,پ:`p`,ت:`t`,ث:`th`,ج:`g`,چ:`ch`,ح:`h`,خ:`kh`,د:`d`,ذ:`th`,ر:`r`,ز:`z`,س:`s`,ش:`sh`,ص:`s`,ض:`d`,ط:`t`,ظ:`th`,ع:`aa`,غ:`gh`,ف:`f`,ق:`k`,ک:`k`,گ:`g`,ل:`l`,ژ:`zh`,ك:`k`,م:`m`,ن:`n`,ه:`h`,و:`o`,ی:`y`,آ:`a`,"٠":`0`,"١":`1`,"٢":`2`,"٣":`3`,"٤":`4`,"٥":`5`,"٦":`6`,"٧":`7`,"٨":`8`,"٩":`9`,أ:`a`,ي:`y`,إ:`a`,ؤ:`o`,ئ:`y`,ء:`aa`,ђ:`dj`,ћ:`c`,Ђ:`Dj`,Ћ:`C`,ə:`e`,Ə:`E`,ß:`ss`,ẞ:`SS`,ভ্ল:`vl`,পশ:`psh`,ব্ধ:`bdh`,ব্জ:`bj`,ব্দ:`bd`,ব্ব:`bb`,ব্ল:`bl`,ভ:`v`,ব:`b`,চ্ঞ:`cNG`,চ্ছ:`cch`,চ্চ:`cc`,ছ:`ch`,চ:`c`,ধ্ন:`dhn`,ধ্ম:`dhm`,দ্ঘ:`dgh`,দ্ধ:`ddh`,দ্ভ:`dv`,দ্ম:`dm`,ড্ড:`DD`,ঢ:`Dh`,ধ:`dh`,দ্গ:`dg`,দ্দ:`dd`,ড:`D`,দ:`d`,"।":`.`,ঘ্ন:`Ghn`,গ্ধ:`Gdh`,গ্ণ:`GN`,গ্ন:`Gn`,গ্ম:`Gm`,গ্ল:`Gl`,জ্ঞ:`jNG`,ঘ:`Gh`,গ:`g`,হ্ণ:`hN`,হ্ন:`hn`,হ্ম:`hm`,হ্ল:`hl`,হ:`h`,জ্ঝ:`jjh`,ঝ:`jh`,জ্জ:`jj`,জ:`j`,ক্ষ্ণ:`kxN`,ক্ষ্ম:`kxm`,ক্ষ:`ksh`,কশ:`ksh`,ক্ক:`kk`,ক্ট:`kT`,ক্ত:`kt`,ক্ল:`kl`,ক্স:`ks`,খ:`kh`,ক:`k`,ল্ভ:`lv`,ল্ধ:`ldh`,লখ:`lkh`,লঘ:`lgh`,লফ:`lph`,ল্ক:`lk`,ল্গ:`lg`,ল্ট:`lT`,ল্ড:`lD`,ল্প:`lp`,ল্ম:`lm`,ল্ল:`ll`,ল্ব:`lb`,ল:`l`,ম্থ:`mth`,ম্ফ:`mf`,ম্ভ:`mv`,মপ্ল:`mpl`,ম্ন:`mn`,ম্প:`mp`,ম্ম:`mm`,ম্ল:`ml`,ম্ব:`mb`,ম:`m`,"০":`0`,"১":`1`,"২":`2`,"৩":`3`,"৪":`4`,"৫":`5`,"৬":`6`,"৭":`7`,"৮":`8`,"৯":`9`,ঙ্ক্ষ:`Ngkx`,ঞ্ছ:`nch`,ঙ্ঘ:`ngh`,ঙ্খ:`nkh`,ঞ্ঝ:`njh`,ঙ্গৌ:`ngOU`,ঙ্গৈ:`ngOI`,ঞ্চ:`nc`,ঙ্ক:`nk`,ঙ্ষ:`Ngx`,ঙ্গ:`ngo`,ঙ্ম:`Ngm`,ঞ্জ:`nj`,ন্ধ:`ndh`,ন্ঠ:`nTh`,ণ্ঠ:`NTh`,ন্থ:`nth`,ঙ্গা:`nga`,ঙ্গি:`ngi`,ঙ্গী:`ngI`,ঙ্গু:`ngu`,ঙ্গূ:`ngU`,ঙ্গে:`nge`,ঙ্গো:`ngO`,ণ্ঢ:`NDh`,নশ:`nsh`,ঙর:`Ngr`,ঞর:`NGr`,"ংর":`ngr`,ঙ:`Ng`,ঞ:`NG`,"ং":`ng`,ন্ন:`nn`,ণ্ণ:`NN`,ণ্ন:`Nn`,ন্ম:`nm`,ণ্ম:`Nm`,ন্দ:`nd`,ন্ট:`nT`,ণ্ট:`NT`,ন্ড:`nD`,ণ্ড:`ND`,ন্ত:`nt`,ন্স:`ns`,ন:`n`,ণ:`N`,"ৈ":`OI`,"ৌ":`OU`,"ো":`O`,ঐ:`OI`,ঔ:`OU`,অ:`o`,ও:`oo`,ফ্ল:`fl`,প্ট:`pT`,প্ত:`pt`,প্ন:`pn`,প্প:`pp`,প্ল:`pl`,প্স:`ps`,ফ:`f`,প:`p`,"ৃ":`rri`,ঋ:`rri`,রর‍্য:`rry`,"্র্য":`ry`,"্রর":`rr`,ড়্গ:`Rg`,ঢ়:`Rh`,ড়:`R`,র:`r`,"্র":`r`,শ্ছ:`Sch`,ষ্ঠ:`ShTh`,ষ্ফ:`Shf`,স্ক্ল:`skl`,স্খ:`skh`,স্থ:`sth`,স্ফ:`sf`,শ্চ:`Sc`,শ্ত:`St`,শ্ন:`Sn`,শ্ম:`Sm`,শ্ল:`Sl`,ষ্ক:`Shk`,ষ্ট:`ShT`,ষ্ণ:`ShN`,ষ্প:`Shp`,ষ্ম:`Shm`,স্প্ল:`spl`,স্ক:`sk`,স্ট:`sT`,স্ত:`st`,স্ন:`sn`,স্প:`sp`,স্ম:`sm`,স্ল:`sl`,শ:`S`,ষ:`Sh`,স:`s`,"ু":`u`,উ:`u`,অ্য:`oZ`,ত্থ:`tth`,ৎ:`tt`,ট্ট:`TT`,ট্ম:`Tm`,ঠ:`Th`,ত্ন:`tn`,ত্ম:`tm`,থ:`th`,ত্ত:`tt`,ট:`T`,ত:`t`,অ্যা:`AZ`,"া":`a`,আ:`a`,য়া:`ya`,য়:`y`,"ি":`i`,ই:`i`,"ী":`ee`,ঈ:`ee`,"ূ":`uu`,ঊ:`uu`,"ে":`e`,এ:`e`,য:`z`,"্য":`Z`,ইয়:`y`,ওয়:`w`,"্ব":`w`,এক্স:`x`,"ঃ":`:`,"ঁ":`nn`,"্‌":``,"˚":`0`,"¹":`1`,"²":`2`,"³":`3`,"⁴":`4`,"⁵":`5`,"⁶":`6`,"⁷":`7`,"⁸":`8`,"⁹":`9`,"₀":`0`,"₁":`1`,"₂":`2`,"₃":`3`,"₄":`4`,"₅":`5`,"₆":`6`,"₇":`7`,"₈":`8`,"₉":`9`,"௦":`0`,"௧":`1`,"௨":`2`,"௩":`3`,"௪":`4`,"௫":`5`,"௬":`6`,"௭":`7`,"௮":`8`,"௯":`9`,"௰":`10`,"௱":`100`,"௲":`1000`,Ꜳ:`AA`,ꜳ:`aa`,Ꜵ:`AO`,ꜵ:`ao`,Ꜷ:`AU`,ꜷ:`au`,Ꜹ:`AV`,ꜹ:`av`,Ꜻ:`av`,ꜻ:`av`,Ꜽ:`AY`,ꜽ:`ay`,ȸ:`db`,ʣ:`dz`,ʥ:`dz`,ʤ:`dezh`,"🙰":`et`,ﬀ:`ff`,ﬃ:`ffi`,ﬄ:`ffl`,ﬁ:`fi`,ﬂ:`fl`,ʩ:`feng`,ʪ:`ls`,ʫ:`lz`,ɮ:`lezh`,ȹ:`qp`,ʨ:`tc`,ʦ:`ts`,ʧ:`tesh`,Ꝏ:`OO`,ꝏ:`oo`,ﬆ:`st`,ﬅ:`st`,Ꜩ:`TZ`,ꜩ:`tz`,ᵫ:`ue`,Aι:`Ai`,αι:`ai`,ἀ:`a`,ἁ:`a`,ἂ:`a`,ἃ:`a`,ἄ:`a`,ἅ:`a`,ἆ:`a`,ἇ:`a`,Ἀ:`A`,Ἁ:`A`,Ἂ:`A`,Ἃ:`A`,Ἄ:`A`,Ἅ:`A`,Ἆ:`A`,Ἇ:`A`,ᾰ:`a`,ᾱ:`a`,ᾲ:`a`,ᾳ:`a`,ᾴ:`a`,ᾶ:`a`,ᾷ:`a`,Ᾰ:`A`,Ᾱ:`A`,Ὰ:`A`,Ά:`A`,ᾼ:`A`,A̧:`A`,a̧:`a`,Ⱥ:`A`,ⱥ:`a`,Ȧ:`A`,ȧ:`a`,Ɓ:`B`,C̈:`C`,c̈:`c`,C̨:`C`,c̨:`c`,Ȼ:`C`,ȼ:`c`,C̀:`C`,c̀:`c`,C̣:`C`,c̣:`c`,C̄:`C`,c̄:`c`,C̃:`C`,c̃:`c`,Ȩ:`E`,ȩ:`e`,Ɇ:`E`,ɇ:`e`,I̧:`I`,i̧:`i`,Ɨ:`I`,ɨ:`i`,i:`i`,J́́:`J`,j́:`j`,J̀̀:`J`,j̀:`j`,J̈:`J`,j̈:`j`,J̧:`J`,j̧:`j`,J̨:`J`,j̨:`j`,Ɉ:`J`,ɉ:`j`,J̌:`J`,ǰ:`j`,J̇:`J`,j:`j`,J̣:`J`,j̣:`j`,J̄:`J`,j̄:`j`,J̃:`J`,j̃:`j`,ĸ:`k`,L̀:`L`,l̀:`l`,L̂:`L`,l̂:`l`,L̈:`L`,l̈:`l`,L̨:`L`,l̨:`l`,Ƚ:`L`,ƚ:`l`,L̇:`L`,l̇:`l`,Ḷ:`L`,ḷ:`l`,L̄:`L`,l̄:`l`,L̃:`L`,l̃:`l`,Ŋ:`N`,ŋ:`n`,Ǹ:`N`,ǹ:`n`,N̂:`N`,n̂:`n`,N̈:`N`,n̈:`n`,N̨:`N`,n̨:`n`,Ꞥ:`N`,ꞥ:`n`,Ṅ:`N`,ṅ:`n`,Ṇ:`N`,ṇ:`n`,N̄:`N`,n̄:`n`,O̧:`O`,o̧:`o`,Ǫ:`O`,ǫ:`o`,Ɵ:`O`,ɵ:`o`,Ȯ:`O`,ȯ:`o`,S̀:`S`,s̀:`s`,Ŝ̀:`S`,S̈:`S`,s̈:`s`,S̨:`S`,s̨:`s`,Ꞩ:`S`,ꞩ:`s`,Ṡ:`S`,ṡ:`s`,Ṣ:`S`,ṣ:`s`,S̄:`S`,s̄:`s`,S̃:`S`,s̃:`s`,T́:`T`,t́:`t`,T̀:`T`,t̀:`t`,T̂:`T`,t̂:`t`,T̈:`T`,ẗ:`t`,T̨:`T`,t̨:`t`,Ⱦ:`T`,ⱦ:`t`,Ṫ:`T`,ṫ:`t`,Ṭ:`T`,ṭ:`t`,T̄:`T`,t̄:`t`,T̃:`T`,t̃:`t`,U̧:`U`,u̧:`u`,Ʉ:`U`,ʉ:`u`,U̇:`U`,u̇:`u`,Ʊ:`U`,ʊ:`u`,Ẁ:`W`,ẁ:`w`,Ẃ:`W`,ẃ:`w`,Ẅ:`W`,ẅ:`w`,Ꙗ:`Ja`,ꙗ:`ja`,Y̧:`Y`,y̧:`y`,Y̨:`Y`,y̨:`y`,Ɏ:`Y`,ɏ:`y`,Y̌:`Y`,y̌:`y`,Ẏ:`Y`,ẏ:`y`,Ȳ:`Y`,ȳ:`y`,Z̀:`Z`,z̀:`z`,Ẑ:`Z`,ẑ:`z`,Z̈:`Z`,z̈:`z`,Z̧:`Z`,z̧:`z`,Z̨:`Z`,z̨:`z`,Ƶ:`Z`,ƶ:`z`,Ẓ:`Z`,ẓ:`z`,Z̄:`Z`,z̄:`z`,Z̃:`Z`,z̃:`z`,"\xA0":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `,"\u2028":` `,"\u2029":` `,"​":` `," ":` `," ":` `,"　":` `,ﾠ:` `,"«":`<<`,"»":`>>`,"‘":`'`,"’":`'`,"‚":`'`,"‛":`'`,"“":`"`,"”":`"`,"„":`"`,"‟":`"`,"‹":`'`,"›":`'`,"–":`-`,"—":`-`,"…":`...`,"€":`EUR`,$:`$`,"₢":`Cr`,"₣":`Fr.`,"£":`PS`,"₤":`L.`,ℳ:`M`,"₥":`mil`,"₦":`N`,"₧":`Pts`,"₨":`Rs`,රු:`LKR`,ரூ:`LKR`,"௹":`Rs`,रू:`NPR`,"₹":`Rs`,"૱":`Rs`,"₩":`W`,"₪":`NS`,"₸":`KZT`,"₫":`D`,"֏":`AMD`,"₭":`K`,"₺":`TL`,"₼":`AZN`,"₮":`T`,"₯":`Dr`,"₲":`PYG`,"₾":`GEL`,"₳":`ARA`,"₴":`UAH`,"₽":`RUB`,"₵":`GHS`,"₡":`CL`,"¢":`c`,"¥":`YEN`,円:`JPY`,"৳":`BDT`,元:`CNY`,"﷼":`SAR`,"៛":`KR`,"₠":`ECU`,"¤":`$?`,"฿":`THB`,"؋":`AFN`};function sd(e,t=od){e=e.normalize(`NFC`);let n=``,r;for(let i=0;i<e.length;i++)r=e.charAt(i),n+=typeof t[r]==`string`?t[r]:r;return n}function cd(e,t={}){let n={allowNonAlphaStart:!1,handleCasing:`camel`,...t};var r=e.replace(/<(.*?)>/g,``);r=r.replace(/['"‘’“”ʻ\[\]\(\)\{\}:]/g,``),r=r.toLowerCase(),r=sd(r),n.allowNonAlphaStart||(r=r.replace(/^[^a-z]+/,``));let i=r.split(/[^a-z0-9]+/).filter(Boolean);if(r=``,n.handleCasing===`snake`)return i.join(`_`);for(let e=0;e<i.length;e++)n.handleCasing!==`pascal`&&e===0?r+=i[e]:r+=i[e].charAt(0).toUpperCase()+i[e].substring(1);return r}function ld(e,t={}){let n={prefix:``,suffix:``,...t},r=cd(e,{handleCasing:`snake`}).toUpperCase();return r?`${n.prefix}${r}${n.suffix}`:``}function ud(e){let t=e.replace(/<(.*?)>/g,``);return t=t.toLowerCase(),t=sd(t),t=t.replace(/^[^a-z]+/,``),t=t.replace(/[^a-z0-9]+$/,``),t.split(/[^a-z0-9]+/).filter(Boolean).join(`-`)}var dd={START:`asset-indexes/start-indexing`,STOP:`asset-indexes/stop-indexing-session`,PROCESS:`asset-indexes/process-indexing-session`,OVERVIEW:`asset-indexes/indexing-session-overview`,FINISH:`asset-indexes/finish-indexing-session`},fd=new WeakMap,pd=new WeakMap,md=new WeakMap,hd=new WeakMap,gd=new WeakMap,_d=new WeakMap,vd=new WeakMap,R=new WeakSet,yd=class{constructor(e={}){i(this,R),u(this,fd,new Map),u(this,pd,null),u(this,md,0),u(this,hd,[]),u(this,gd,[]),u(this,_d,new Set),u(this,vd,new Map);let{existingSessions:n=[],maxConcurrentConnections:r=3,autoResume:a=!0}=e;this.maxConcurrentConnections=r;for(let e of n)l(fd,this).set(e.id,e);a&&(t(R,this,Cd).call(this),l(pd,this)!==null&&t(R,this,wd).call(this))}getSessions(){return Array.from(l(fd,this).values())}getCurrentSessionId(){return l(pd,this)}isProcessing(){return l(md,this)>0}on(e,t){return l(vd,this).has(e)||l(vd,this).set(e,new Set),l(vd,this).get(e).add(t),()=>{l(vd,this).get(e)?.delete(t)}}async startIndexing(e){let n=await Zu.post(dd.START,e),{data:r}=n;return r.session&&(l(fd,this).set(r.session.id,r.session),a(pd,this,r.session.id),t(R,this,xd).call(this),r.stop||t(R,this,wd).call(this)),r.stop&&t(R,this,Sd).call(this,r.stop),n}stopSession(e){t(R,this,Td).call(this,e),t(R,this,Ed).call(this,{sessionId:e,action:dd.STOP,params:{sessionId:e},priority:!0})}getSessionOverview(e){t(R,this,Ed).call(this,{sessionId:e,action:dd.OVERVIEW,params:{sessionId:e},priority:!0})}finishSession(e){t(R,this,Ed).call(this,{sessionId:e.sessionId,action:dd.FINISH,params:e,priority:!0})}destroy(){l(fd,this).clear(),a(hd,this,[]),a(gd,this,[]),l(vd,this).clear(),a(pd,this,null),a(md,this,0)}};function bd(e,t){l(vd,this).get(e)?.forEach(e=>e(t))}function xd(e){t(R,this,bd).call(this,`change`,{sessions:this.getSessions(),currentSessionId:l(pd,this),reviewSessionId:e})}function Sd(e){l(fd,this).delete(e),l(pd,this)===e&&a(pd,this,null),t(R,this,xd).call(this)}function Cd(){for(let[e,t]of l(fd,this))if(!t.actionRequired&&!l(_d,this).has(e)){a(pd,this,e);return}a(pd,this,null)}function wd(){if(l(pd,this)||t(R,this,Cd).call(this),!l(pd,this))return;let e=l(fd,this).get(l(pd,this));if(!e)return;let n=e.totalEntries-e.processedEntries,r=this.maxConcurrentConnections-l(md,this),i=Math.min(r,n);for(let n=0;n<i;n++)t(R,this,Ed).call(this,{sessionId:e.id,action:dd.PROCESS,params:{sessionId:l(pd,this)},priority:!1});e.processIfRootEmpty&&t(R,this,Ed).call(this,{sessionId:e.id,action:dd.PROCESS,params:{sessionId:l(pd,this)},priority:!1})}function Td(e){l(_d,this).add(e),a(hd,this,l(hd,this).filter(t=>t.sessionId!==e))}function Ed(e){e.priority?l(gd,this).push(e):l(hd,this).push(e),t(R,this,Dd).call(this)}function Dd(){if(!(l(hd,this).length+l(gd,this).length===0||l(md,this)>=this.maxConcurrentConnections))for(;l(hd,this).length+l(gd,this).length>0&&l(md,this)<this.maxConcurrentConnections;){var e;a(md,this,(e=l(md,this),e++,e));let n=l(gd,this).length>0?l(gd,this).shift():l(hd,this).shift();t(R,this,Od).call(this,n)}}async function Od(e){try{let n=await Zu.post(e.action,e.params);t(R,this,kd).call(this,n.data)}catch(n){t(R,this,Ad).call(this,n,e)}finally{var n;a(md,this,(n=l(md,this),n--,n)),t(R,this,Dd).call(this)}}function kd(e){let n;e.session&&(l(fd,this).set(e.session.id,e.session),t(R,this,Cd).call(this),e.session.actionRequired&&!e.skipDialog?l(_d,this).has(e.session.id)||(n=e.session.id):l(_d,this).has(e.session.id)||t(R,this,wd).call(this)),t(R,this,Cd).call(this),e.stop&&(l(fd,this).delete(e.stop),l(pd,this)===e.stop&&a(pd,this,null)),t(R,this,xd).call(this,n),l(fd,this).size===0&&t(R,this,bd).call(this,`complete`,{})}function Ad(e,n){t(R,this,Cd).call(this);let r=e?.response?.data?.message||e.message||`An error occurred during indexing.`;t(R,this,bd).call(this,`error`,{message:r,sessionId:n.sessionId}),t(R,this,Dd).call(this)}var jd=typeof global==`object`&&global&&global.Object===Object&&global,Md=typeof self==`object`&&self&&self.Object===Object&&self,Nd=jd||Md||Function(`return this`)(),Pd=Nd.Symbol,Fd=Object.prototype,Id=Fd.hasOwnProperty,Ld=Fd.toString,Rd=Pd?Pd.toStringTag:void 0;function zd(e){var t=Id.call(e,Rd),n=e[Rd];try{e[Rd]=void 0;var r=!0}catch{}var i=Ld.call(e);return r&&(t?e[Rd]=n:delete e[Rd]),i}var Bd=Object.prototype.toString;function Vd(e){return Bd.call(e)}var Hd=`[object Null]`,Ud=`[object Undefined]`,Wd=Pd?Pd.toStringTag:void 0;function Gd(e){return e==null?e===void 0?Ud:Hd:Wd&&Wd in Object(e)?zd(e):Vd(e)}function Kd(e){return typeof e==`object`&&!!e}var qd=`[object Symbol]`;function Jd(e){return typeof e==`symbol`||Kd(e)&&Gd(e)==qd}function Yd(e,t){for(var n=-1,r=e==null?0:e.length,i=Array(r);++n<r;)i[n]=t(e[n],n,e);return i}var Xd=Array.isArray,Zd=1/0,Qd=Pd?Pd.prototype:void 0,$d=Qd?Qd.toString:void 0;function ef(e){if(typeof e==`string`)return e;if(Xd(e))return Yd(e,ef)+``;if(Jd(e))return $d?$d.call(e):``;var t=e+``;return t==`0`&&1/e==-Zd?`-0`:t}function tf(e){var t=typeof e;return e!=null&&(t==`object`||t==`function`)}var nf=`[object AsyncFunction]`,rf=`[object Function]`,af=`[object GeneratorFunction]`,of=`[object Proxy]`;function sf(e){if(!tf(e))return!1;var t=Gd(e);return t==rf||t==af||t==nf||t==of}var cf=Nd[`__core-js_shared__`],lf=function(){var e=/[^.]+$/.exec(cf&&cf.keys&&cf.keys.IE_PROTO||``);return e?`Symbol(src)_1.`+e:``}();function uf(e){return!!lf&&lf in e}var df=Function.prototype.toString;function ff(e){if(e!=null){try{return df.call(e)}catch{}try{return e+``}catch{}}return``}var pf=/[\\^$.*+?()[\]{}|]/g,mf=/^\[object .+?Constructor\]$/,hf=Function.prototype,gf=Object.prototype,_f=hf.toString,vf=gf.hasOwnProperty,yf=RegExp(`^`+_f.call(vf).replace(pf,`\\$&`).replace(/hasOwnProperty|(function).*?(?=\\\()| for .+?(?=\\\])/g,`$1.*?`)+`$`);function bf(e){return!tf(e)||uf(e)?!1:(sf(e)?yf:mf).test(ff(e))}function xf(e,t){return e?.[t]}function Sf(e,t){var n=xf(e,t);return bf(n)?n:void 0}var Cf=Sf(Nd,`WeakMap`),wf=Object.create,Tf=function(){function e(){}return function(t){if(!tf(t))return{};if(wf)return wf(t);e.prototype=t;var n=new e;return e.prototype=void 0,n}}();function Ef(e,t){var n=-1,r=e.length;for(t||=Array(r);++n<r;)t[n]=e[n];return t}var Df=function(){try{var e=Sf(Object,`defineProperty`);return e({},``,{}),e}catch{}}();function Of(e,t){for(var n=-1,r=e==null?0:e.length;++n<r&&t(e[n],n,e)!==!1;);return e}var kf=9007199254740991,Af=/^(?:0|[1-9]\d*)$/;function jf(e,t){var n=typeof e;return t??=kf,!!t&&(n==`number`||n!=`symbol`&&Af.test(e))&&e>-1&&e%1==0&&e<t}function Mf(e,t,n){t==`__proto__`&&Df?Df(e,t,{configurable:!0,enumerable:!0,value:n,writable:!0}):e[t]=n}function Nf(e,t){return e===t||e!==e&&t!==t}var Pf=Object.prototype.hasOwnProperty;function Ff(e,t,n){var r=e[t];(!(Pf.call(e,t)&&Nf(r,n))||n===void 0&&!(t in e))&&Mf(e,t,n)}function If(e,t,n,r){var i=!n;n||={};for(var a=-1,o=t.length;++a<o;){var s=t[a],c=r?r(n[s],e[s],s,n,e):void 0;c===void 0&&(c=e[s]),i?Mf(n,s,c):Ff(n,s,c)}return n}var Lf=9007199254740991;function Rf(e){return typeof e==`number`&&e>-1&&e%1==0&&e<=Lf}function zf(e){return e!=null&&Rf(e.length)&&!sf(e)}var Bf=Object.prototype;function Vf(e){var t=e&&e.constructor;return e===(typeof t==`function`&&t.prototype||Bf)}function Hf(e,t){for(var n=-1,r=Array(e);++n<e;)r[n]=t(n);return r}var Uf=`[object Arguments]`;function Wf(e){return Kd(e)&&Gd(e)==Uf}var Gf=Object.prototype,Kf=Gf.hasOwnProperty,qf=Gf.propertyIsEnumerable,Jf=Wf(function(){return arguments}())?Wf:function(e){return Kd(e)&&Kf.call(e,`callee`)&&!qf.call(e,`callee`)};function Yf(){return!1}var Xf=typeof exports==`object`&&exports&&!exports.nodeType&&exports,Zf=Xf&&typeof module==`object`&&module&&!module.nodeType&&module,Qf=Zf&&Zf.exports===Xf?Nd.Buffer:void 0,$f=(Qf?Qf.isBuffer:void 0)||Yf,ep=`[object Arguments]`,tp=`[object Array]`,np=`[object Boolean]`,rp=`[object Date]`,ip=`[object Error]`,ap=`[object Function]`,op=`[object Map]`,sp=`[object Number]`,cp=`[object Object]`,lp=`[object RegExp]`,up=`[object Set]`,dp=`[object String]`,fp=`[object WeakMap]`,pp=`[object ArrayBuffer]`,mp=`[object DataView]`,hp=`[object Float32Array]`,gp=`[object Float64Array]`,_p=`[object Int8Array]`,vp=`[object Int16Array]`,yp=`[object Int32Array]`,bp=`[object Uint8Array]`,xp=`[object Uint8ClampedArray]`,Sp=`[object Uint16Array]`,wp=`[object Uint32Array]`,Tp={};Tp[hp]=Tp[gp]=Tp[_p]=Tp[vp]=Tp[yp]=Tp[bp]=Tp[xp]=Tp[Sp]=Tp[wp]=!0,Tp[ep]=Tp[tp]=Tp[pp]=Tp[np]=Tp[mp]=Tp[rp]=Tp[ip]=Tp[ap]=Tp[op]=Tp[sp]=Tp[cp]=Tp[lp]=Tp[up]=Tp[dp]=Tp[fp]=!1;function Ep(e){return Kd(e)&&Rf(e.length)&&!!Tp[Gd(e)]}function Dp(e){return function(t){return e(t)}}var Op=typeof exports==`object`&&exports&&!exports.nodeType&&exports,kp=Op&&typeof module==`object`&&module&&!module.nodeType&&module,Ap=kp&&kp.exports===Op&&jd.process,jp=function(){try{return kp&&kp.require&&kp.require(`util`).types||Ap&&Ap.binding&&Ap.binding(`util`)}catch{}}(),Mp=jp&&jp.isTypedArray,Np=Mp?Dp(Mp):Ep,Pp=Object.prototype.hasOwnProperty;function Fp(e,t){var n=Xd(e),r=!n&&Jf(e),i=!n&&!r&&$f(e),a=!n&&!r&&!i&&Np(e),o=n||r||i||a,s=o?Hf(e.length,String):[],c=s.length;for(var l in e)(t||Pp.call(e,l))&&!(o&&(l==`length`||i&&(l==`offset`||l==`parent`)||a&&(l==`buffer`||l==`byteLength`||l==`byteOffset`)||jf(l,c)))&&s.push(l);return s}function Ip(e,t){return function(n){return e(t(n))}}var Lp=Ip(Object.keys,Object),Rp=Object.prototype.hasOwnProperty;function zp(e){if(!Vf(e))return Lp(e);var t=[];for(var n in Object(e))Rp.call(e,n)&&n!=`constructor`&&t.push(n);return t}function Bp(e){return zf(e)?Fp(e):zp(e)}function Vp(e){var t=[];if(e!=null)for(var n in Object(e))t.push(n);return t}var Hp=Object.prototype.hasOwnProperty;function Up(e){if(!tf(e))return Vp(e);var t=Vf(e),n=[];for(var r in e)r==`constructor`&&(t||!Hp.call(e,r))||n.push(r);return n}function Wp(e){return zf(e)?Fp(e,!0):Up(e)}var Gp=/\.|\[(?:[^[\]]*|(["'])(?:(?!\1)[^\\]|\\.)*?\1)\]/,Kp=/^\w*$/;function qp(e,t){if(Xd(e))return!1;var n=typeof e;return n==`number`||n==`symbol`||n==`boolean`||e==null||Jd(e)?!0:Kp.test(e)||!Gp.test(e)||t!=null&&e in Object(t)}var Jp=Sf(Object,`create`);function Yp(){this.__data__=Jp?Jp(null):{},this.size=0}function Xp(e){var t=this.has(e)&&delete this.__data__[e];return this.size-=t?1:0,t}var Zp=`__lodash_hash_undefined__`,Qp=Object.prototype.hasOwnProperty;function $p(e){var t=this.__data__;if(Jp){var n=t[e];return n===Zp?void 0:n}return Qp.call(t,e)?t[e]:void 0}var em=Object.prototype.hasOwnProperty;function tm(e){var t=this.__data__;return Jp?t[e]!==void 0:em.call(t,e)}var nm=`__lodash_hash_undefined__`;function rm(e,t){var n=this.__data__;return this.size+=this.has(e)?0:1,n[e]=Jp&&t===void 0?nm:t,this}function im(e){var t=-1,n=e==null?0:e.length;for(this.clear();++t<n;){var r=e[t];this.set(r[0],r[1])}}im.prototype.clear=Yp,im.prototype.delete=Xp,im.prototype.get=$p,im.prototype.has=tm,im.prototype.set=rm;function am(){this.__data__=[],this.size=0}function om(e,t){for(var n=e.length;n--;)if(Nf(e[n][0],t))return n;return-1}var sm=Array.prototype.splice;function cm(e){var t=this.__data__,n=om(t,e);return n<0?!1:(n==t.length-1?t.pop():sm.call(t,n,1),--this.size,!0)}function lm(e){var t=this.__data__,n=om(t,e);return n<0?void 0:t[n][1]}function um(e){return om(this.__data__,e)>-1}function dm(e,t){var n=this.__data__,r=om(n,e);return r<0?(++this.size,n.push([e,t])):n[r][1]=t,this}function fm(e){var t=-1,n=e==null?0:e.length;for(this.clear();++t<n;){var r=e[t];this.set(r[0],r[1])}}fm.prototype.clear=am,fm.prototype.delete=cm,fm.prototype.get=lm,fm.prototype.has=um,fm.prototype.set=dm;var pm=Sf(Nd,`Map`);function mm(){this.size=0,this.__data__={hash:new im,map:new(pm||fm),string:new im}}function hm(e){var t=typeof e;return t==`string`||t==`number`||t==`symbol`||t==`boolean`?e!==`__proto__`:e===null}function gm(e,t){var n=e.__data__;return hm(t)?n[typeof t==`string`?`string`:`hash`]:n.map}function _m(e){var t=gm(this,e).delete(e);return this.size-=t?1:0,t}function vm(e){return gm(this,e).get(e)}function ym(e){return gm(this,e).has(e)}function bm(e,t){var n=gm(this,e),r=n.size;return n.set(e,t),this.size+=n.size==r?0:1,this}function xm(e){var t=-1,n=e==null?0:e.length;for(this.clear();++t<n;){var r=e[t];this.set(r[0],r[1])}}xm.prototype.clear=mm,xm.prototype.delete=_m,xm.prototype.get=vm,xm.prototype.has=ym,xm.prototype.set=bm;var Sm=`Expected a function`;function Cm(e,t){if(typeof e!=`function`||t!=null&&typeof t!=`function`)throw TypeError(Sm);var n=function(){var r=arguments,i=t?t.apply(this,r):r[0],a=n.cache;if(a.has(i))return a.get(i);var o=e.apply(this,r);return n.cache=a.set(i,o)||a,o};return n.cache=new(Cm.Cache||xm),n}Cm.Cache=xm;var wm=500;function Tm(e){var t=Cm(e,function(e){return n.size===wm&&n.clear(),e}),n=t.cache;return t}var Em=/[^.[\]]+|\[(?:(-?\d+(?:\.\d+)?)|(["'])((?:(?!\2)[^\\]|\\.)*?)\2)\]|(?=(?:\.|\[\])(?:\.|\[\]|$))/g,Dm=/\\(\\)?/g,Om=Tm(function(e){var t=[];return e.charCodeAt(0)===46&&t.push(``),e.replace(Em,function(e,n,r,i){t.push(r?i.replace(Dm,`$1`):n||e)}),t});function km(e){return e==null?``:ef(e)}function Am(e,t){return Xd(e)?e:qp(e,t)?[e]:Om(km(e))}var jm=1/0;function Mm(e){if(typeof e==`string`||Jd(e))return e;var t=e+``;return t==`0`&&1/e==-jm?`-0`:t}function Nm(e,t){t=Am(t,e);for(var n=0,r=t.length;e!=null&&n<r;)e=e[Mm(t[n++])];return n&&n==r?e:void 0}function Pm(e,t,n){var r=e==null?void 0:Nm(e,t);return r===void 0?n:r}function Fm(e,t){for(var n=-1,r=t.length,i=e.length;++n<r;)e[i+n]=t[n];return e}var Im=Ip(Object.getPrototypeOf,Object);function Lm(e){return function(t){return e?.[t]}}function Rm(){this.__data__=new fm,this.size=0}function zm(e){var t=this.__data__,n=t.delete(e);return this.size=t.size,n}function Bm(e){return this.__data__.get(e)}function Vm(e){return this.__data__.has(e)}var Hm=200;function Um(e,t){var n=this.__data__;if(n instanceof fm){var r=n.__data__;if(!pm||r.length<Hm-1)return r.push([e,t]),this.size=++n.size,this;n=this.__data__=new xm(r)}return n.set(e,t),this.size=n.size,this}function Wm(e){this.size=(this.__data__=new fm(e)).size}Wm.prototype.clear=Rm,Wm.prototype.delete=zm,Wm.prototype.get=Bm,Wm.prototype.has=Vm,Wm.prototype.set=Um;function Gm(e,t){return e&&If(t,Bp(t),e)}function Km(e,t){return e&&If(t,Wp(t),e)}var qm=typeof exports==`object`&&exports&&!exports.nodeType&&exports,Jm=qm&&typeof module==`object`&&module&&!module.nodeType&&module,Ym=Jm&&Jm.exports===qm?Nd.Buffer:void 0,Xm=Ym?Ym.allocUnsafe:void 0;function Zm(e,t){if(t)return e.slice();var n=e.length,r=Xm?Xm(n):new e.constructor(n);return e.copy(r),r}function Qm(e,t){for(var n=-1,r=e==null?0:e.length,i=0,a=[];++n<r;){var o=e[n];t(o,n,e)&&(a[i++]=o)}return a}function $m(){return[]}var eh=Object.prototype.propertyIsEnumerable,th=Object.getOwnPropertySymbols,nh=th?function(e){return e==null?[]:(e=Object(e),Qm(th(e),function(t){return eh.call(e,t)}))}:$m;function rh(e,t){return If(e,nh(e),t)}var ih=Object.getOwnPropertySymbols?function(e){for(var t=[];e;)Fm(t,nh(e)),e=Im(e);return t}:$m;function ah(e,t){return If(e,ih(e),t)}function oh(e,t,n){var r=t(e);return Xd(e)?r:Fm(r,n(e))}function sh(e){return oh(e,Bp,nh)}function ch(e){return oh(e,Wp,ih)}var lh=Sf(Nd,`DataView`),uh=Sf(Nd,`Promise`),dh=Sf(Nd,`Set`),fh=`[object Map]`,ph=`[object Object]`,mh=`[object Promise]`,hh=`[object Set]`,gh=`[object WeakMap]`,_h=`[object DataView]`,vh=ff(lh),yh=ff(pm),bh=ff(uh),xh=ff(dh),Sh=ff(Cf),Ch=Gd;(lh&&Ch(new lh(new ArrayBuffer(1)))!=_h||pm&&Ch(new pm)!=fh||uh&&Ch(uh.resolve())!=mh||dh&&Ch(new dh)!=hh||Cf&&Ch(new Cf)!=gh)&&(Ch=function(e){var t=Gd(e),n=t==ph?e.constructor:void 0,r=n?ff(n):``;if(r)switch(r){case vh:return _h;case yh:return fh;case bh:return mh;case xh:return hh;case Sh:return gh}return t});var wh=Ch,Th=Object.prototype.hasOwnProperty;function Eh(e){var t=e.length,n=new e.constructor(t);return t&&typeof e[0]==`string`&&Th.call(e,`index`)&&(n.index=e.index,n.input=e.input),n}var Dh=Nd.Uint8Array;function Oh(e){var t=new e.constructor(e.byteLength);return new Dh(t).set(new Dh(e)),t}function kh(e,t){var n=t?Oh(e.buffer):e.buffer;return new e.constructor(n,e.byteOffset,e.byteLength)}var Ah=/\w*$/;function jh(e){var t=new e.constructor(e.source,Ah.exec(e));return t.lastIndex=e.lastIndex,t}var Mh=Pd?Pd.prototype:void 0,Nh=Mh?Mh.valueOf:void 0;function Ph(e){return Nh?Object(Nh.call(e)):{}}function Fh(e,t){var n=t?Oh(e.buffer):e.buffer;return new e.constructor(n,e.byteOffset,e.length)}var Ih=`[object Boolean]`,Lh=`[object Date]`,Rh=`[object Map]`,zh=`[object Number]`,Bh=`[object RegExp]`,Vh=`[object Set]`,Hh=`[object String]`,Uh=`[object Symbol]`,Wh=`[object ArrayBuffer]`,Gh=`[object DataView]`,Kh=`[object Float32Array]`,qh=`[object Float64Array]`,Jh=`[object Int8Array]`,Yh=`[object Int16Array]`,Xh=`[object Int32Array]`,Zh=`[object Uint8Array]`,Qh=`[object Uint8ClampedArray]`,$h=`[object Uint16Array]`,eg=`[object Uint32Array]`;function tg(e,t,n){var r=e.constructor;switch(t){case Wh:return Oh(e);case Ih:case Lh:return new r(+e);case Gh:return kh(e,n);case Kh:case qh:case Jh:case Yh:case Xh:case Zh:case Qh:case $h:case eg:return Fh(e,n);case Rh:return new r;case zh:case Hh:return new r(e);case Bh:return jh(e);case Vh:return new r;case Uh:return Ph(e)}}function ng(e){return typeof e.constructor==`function`&&!Vf(e)?Tf(Im(e)):{}}var rg=`[object Map]`;function ig(e){return Kd(e)&&wh(e)==rg}var ag=jp&&jp.isMap,og=ag?Dp(ag):ig,sg=`[object Set]`;function cg(e){return Kd(e)&&wh(e)==sg}var lg=jp&&jp.isSet,ug=lg?Dp(lg):cg,dg=1,fg=2,pg=4,mg=`[object Arguments]`,hg=`[object Array]`,gg=`[object Boolean]`,_g=`[object Date]`,vg=`[object Error]`,yg=`[object Function]`,bg=`[object GeneratorFunction]`,xg=`[object Map]`,Sg=`[object Number]`,Cg=`[object Object]`,wg=`[object RegExp]`,Tg=`[object Set]`,Eg=`[object String]`,Dg=`[object Symbol]`,Og=`[object WeakMap]`,kg=`[object ArrayBuffer]`,Ag=`[object DataView]`,jg=`[object Float32Array]`,Mg=`[object Float64Array]`,Ng=`[object Int8Array]`,Pg=`[object Int16Array]`,Fg=`[object Int32Array]`,Ig=`[object Uint8Array]`,Lg=`[object Uint8ClampedArray]`,Rg=`[object Uint16Array]`,zg=`[object Uint32Array]`,z={};z[mg]=z[hg]=z[kg]=z[Ag]=z[gg]=z[_g]=z[jg]=z[Mg]=z[Ng]=z[Pg]=z[Fg]=z[xg]=z[Sg]=z[Cg]=z[wg]=z[Tg]=z[Eg]=z[Dg]=z[Ig]=z[Lg]=z[Rg]=z[zg]=!0,z[vg]=z[yg]=z[Og]=!1;function Bg(e,t,n,r,i,a){var o,s=t&dg,c=t&fg,l=t&pg;if(n&&(o=i?n(e,r,i,a):n(e)),o!==void 0)return o;if(!tf(e))return e;var u=Xd(e);if(u){if(o=Eh(e),!s)return Ef(e,o)}else{var d=wh(e),f=d==yg||d==bg;if($f(e))return Zm(e,s);if(d==Cg||d==mg||f&&!i){if(o=c||f?{}:ng(e),!s)return c?ah(e,Km(o,e)):rh(e,Gm(o,e))}else{if(!z[d])return i?e:{};o=tg(e,d,s)}}a||=new Wm;var p=a.get(e);if(p)return p;a.set(e,o),ug(e)?e.forEach(function(r){o.add(Bg(r,t,n,r,e,a))}):og(e)&&e.forEach(function(r,i){o.set(i,Bg(r,t,n,i,e,a))});var m=u?void 0:(l?c?ch:sh:c?Wp:Bp)(e);return Of(m||e,function(r,i){m&&(i=r,r=e[i]),Ff(o,i,Bg(r,t,n,i,e,a))}),o}var Vg=1,Hg=4;function Ug(e){return Bg(e,Vg|Hg)}var Wg=`__lodash_hash_undefined__`;function Gg(e){return this.__data__.set(e,Wg),this}function Kg(e){return this.__data__.has(e)}function qg(e){var t=-1,n=e==null?0:e.length;for(this.__data__=new xm;++t<n;)this.add(e[t])}qg.prototype.add=qg.prototype.push=Gg,qg.prototype.has=Kg;function Jg(e,t){for(var n=-1,r=e==null?0:e.length;++n<r;)if(t(e[n],n,e))return!0;return!1}function Yg(e,t){return e.has(t)}var Xg=1,Zg=2;function Qg(e,t,n,r,i,a){var o=n&Xg,s=e.length,c=t.length;if(s!=c&&!(o&&c>s))return!1;var l=a.get(e),u=a.get(t);if(l&&u)return l==t&&u==e;var d=-1,f=!0,p=n&Zg?new qg:void 0;for(a.set(e,t),a.set(t,e);++d<s;){var m=e[d],h=t[d];if(r)var g=o?r(h,m,d,t,e,a):r(m,h,d,e,t,a);if(g!==void 0){if(g)continue;f=!1;break}if(p){if(!Jg(t,function(e,t){if(!Yg(p,t)&&(m===e||i(m,e,n,r,a)))return p.push(t)})){f=!1;break}}else if(!(m===h||i(m,h,n,r,a))){f=!1;break}}return a.delete(e),a.delete(t),f}function $g(e){var t=-1,n=Array(e.size);return e.forEach(function(e,r){n[++t]=[r,e]}),n}function e_(e){var t=-1,n=Array(e.size);return e.forEach(function(e){n[++t]=e}),n}var t_=1,n_=2,r_=`[object Boolean]`,i_=`[object Date]`,a_=`[object Error]`,o_=`[object Map]`,s_=`[object Number]`,c_=`[object RegExp]`,l_=`[object Set]`,u_=`[object String]`,d_=`[object Symbol]`,f_=`[object ArrayBuffer]`,p_=`[object DataView]`,m_=Pd?Pd.prototype:void 0,h_=m_?m_.valueOf:void 0;function g_(e,t,n,r,i,a,o){switch(n){case p_:if(e.byteLength!=t.byteLength||e.byteOffset!=t.byteOffset)return!1;e=e.buffer,t=t.buffer;case f_:return!(e.byteLength!=t.byteLength||!a(new Dh(e),new Dh(t)));case r_:case i_:case s_:return Nf(+e,+t);case a_:return e.name==t.name&&e.message==t.message;case c_:case u_:return e==t+``;case o_:var s=$g;case l_:var c=r&t_;if(s||=e_,e.size!=t.size&&!c)return!1;var l=o.get(e);if(l)return l==t;r|=n_,o.set(e,t);var u=Qg(s(e),s(t),r,i,a,o);return o.delete(e),u;case d_:if(h_)return h_.call(e)==h_.call(t)}return!1}var __=1,v_=Object.prototype.hasOwnProperty;function y_(e,t,n,r,i,a){var o=n&__,s=sh(e),c=s.length;if(c!=sh(t).length&&!o)return!1;for(var l=c;l--;){var u=s[l];if(!(o?u in t:v_.call(t,u)))return!1}var d=a.get(e),f=a.get(t);if(d&&f)return d==t&&f==e;var p=!0;a.set(e,t),a.set(t,e);for(var m=o;++l<c;){u=s[l];var h=e[u],g=t[u];if(r)var _=o?r(g,h,u,t,e,a):r(h,g,u,e,t,a);if(!(_===void 0?h===g||i(h,g,n,r,a):_)){p=!1;break}m||=u==`constructor`}if(p&&!m){var v=e.constructor,y=t.constructor;v!=y&&`constructor`in e&&`constructor`in t&&!(typeof v==`function`&&v instanceof v&&typeof y==`function`&&y instanceof y)&&(p=!1)}return a.delete(e),a.delete(t),p}var b_=1,x_=`[object Arguments]`,S_=`[object Array]`,C_=`[object Object]`,w_=Object.prototype.hasOwnProperty;function T_(e,t,n,r,i,a){var o=Xd(e),s=Xd(t),c=o?S_:wh(e),l=s?S_:wh(t);c=c==x_?C_:c,l=l==x_?C_:l;var u=c==C_,d=l==C_,f=c==l;if(f&&$f(e)){if(!$f(t))return!1;o=!0,u=!1}if(f&&!u)return a||=new Wm,o||Np(e)?Qg(e,t,n,r,i,a):g_(e,t,c,n,r,i,a);if(!(n&b_)){var p=u&&w_.call(e,`__wrapped__`),m=d&&w_.call(t,`__wrapped__`);if(p||m){var h=p?e.value():e,g=m?t.value():t;return a||=new Wm,i(h,g,n,r,a)}}return f?(a||=new Wm,y_(e,t,n,r,i,a)):!1}function E_(e,t,n,r,i){return e===t?!0:e==null||t==null||!Kd(e)&&!Kd(t)?e!==e&&t!==t:T_(e,t,n,r,E_,i)}function D_(e,t,n){t=Am(t,e);for(var r=-1,i=t.length,a=!1;++r<i;){var o=Mm(t[r]);if(!(a=e!=null&&n(e,o)))break;e=e[o]}return a||++r!=i?a:(i=e==null?0:e.length,!!i&&Rf(i)&&jf(o,i)&&(Xd(e)||Jf(e)))}var O_=Lm({"&":`&amp;`,"<":`&lt;`,">":`&gt;`,'"':`&quot;`,"'":`&#39;`}),k_=/[&<>"']/g,A_=RegExp(k_.source);function j_(e){return e=km(e),e&&A_.test(e)?e.replace(k_,O_):e}var M_=Object.prototype.hasOwnProperty;function N_(e,t){return e!=null&&M_.call(e,t)}function P_(e,t){return e!=null&&D_(e,t,N_)}function F_(e,t){return E_(e,t)}function I_(e,t,n,r){if(!tf(e))return e;t=Am(t,e);for(var i=-1,a=t.length,o=a-1,s=e;s!=null&&++i<a;){var c=Mm(t[i]),l=n;if(c===`__proto__`||c===`constructor`||c===`prototype`)return e;if(i!=o){var u=s[c];l=r?r(u,c,s):void 0,l===void 0&&(l=tf(u)?u:jf(t[i+1])?[]:{})}Ff(s,c,l),s=s[c]}return e}function L_(e,t,n){return e==null?e:I_(e,t,n)}var R_=n(((e,t)=>{t.exports=TypeError})),z_=n(((e,t)=>{t.exports={}})),B_=n(((e,t)=>{var n=typeof Map==`function`&&Map.prototype,r=Object.getOwnPropertyDescriptor&&n?Object.getOwnPropertyDescriptor(Map.prototype,`size`):null,i=n&&r&&typeof r.get==`function`?r.get:null,a=n&&Map.prototype.forEach,o=typeof Set==`function`&&Set.prototype,s=Object.getOwnPropertyDescriptor&&o?Object.getOwnPropertyDescriptor(Set.prototype,`size`):null,c=o&&s&&typeof s.get==`function`?s.get:null,l=o&&Set.prototype.forEach,u=typeof WeakMap==`function`&&WeakMap.prototype?WeakMap.prototype.has:null,d=typeof WeakSet==`function`&&WeakSet.prototype?WeakSet.prototype.has:null,f=typeof WeakRef==`function`&&WeakRef.prototype?WeakRef.prototype.deref:null,p=Boolean.prototype.valueOf,m=Object.prototype.toString,h=Function.prototype.toString,g=String.prototype.match,_=String.prototype.slice,v=String.prototype.replace,y=String.prototype.toUpperCase,b=String.prototype.toLowerCase,x=RegExp.prototype.test,S=Array.prototype.concat,C=Array.prototype.join,w=Array.prototype.slice,T=Math.floor,E=typeof BigInt==`function`?BigInt.prototype.valueOf:null,D=Object.getOwnPropertySymbols,ee=typeof Symbol==`function`&&typeof Symbol.iterator==`symbol`?Symbol.prototype.toString:null,te=typeof Symbol==`function`&&typeof Symbol.iterator==`object`,ne=typeof Symbol==`function`&&Symbol.toStringTag?Symbol.toStringTag:null,re=Object.prototype.propertyIsEnumerable,ie=(typeof Reflect==`function`?Reflect.getPrototypeOf:Object.getPrototypeOf)||([].__proto__===Array.prototype?function(e){return e.__proto__}:null);function ae(e,t){if(e===1/0||e===-1/0||e!==e||e&&e>-1e3&&e<1e3||x.call(/e/,t))return t;var n=/[0-9](?=(?:[0-9]{3})+(?![0-9]))/g;if(typeof e==`number`){var r=e<0?-T(-e):T(e);if(r!==e){var i=String(r),a=_.call(t,i.length+1);return v.call(i,n,`$&_`)+`.`+v.call(v.call(a,/([0-9]{3})/g,`$&_`),/_$/,``)}}return v.call(t,n,`$&_`)}var oe=z_(),se=oe.custom,ce=be(se)?se:null,le={__proto__:null,double:`"`,single:`'`},ue={__proto__:null,double:/(["\\])/g,single:/(['\\])/g};t.exports=function e(t,n,r,o){var s=n||{};if(Ce(s,`quoteStyle`)&&!Ce(le,s.quoteStyle))throw TypeError(`option "quoteStyle" must be "single" or "double"`);if(Ce(s,`maxStringLength`)&&(typeof s.maxStringLength==`number`?s.maxStringLength<0&&s.maxStringLength!==1/0:s.maxStringLength!==null))throw TypeError('option "maxStringLength", if provided, must be a positive integer, Infinity, or `null`');var u=Ce(s,`customInspect`)?s.customInspect:!0;if(typeof u!=`boolean`&&u!==`symbol`)throw TypeError("option \"customInspect\", if provided, must be `true`, `false`, or `'symbol'`");if(Ce(s,`indent`)&&s.indent!==null&&s.indent!==`	`&&!(parseInt(s.indent,10)===s.indent&&s.indent>0))throw TypeError('option "indent" must be "\\t", an integer > 0, or `null`');if(Ce(s,`numericSeparator`)&&typeof s.numericSeparator!=`boolean`)throw TypeError('option "numericSeparator", if provided, must be `true` or `false`');var d=s.numericSeparator;if(t===void 0)return`undefined`;if(t===null)return`null`;if(typeof t==`boolean`)return t?`true`:`false`;if(typeof t==`string`)return Ne(t,s);if(typeof t==`number`){if(t===0)return 1/0/t>0?`0`:`-0`;var f=String(t);return d?ae(t,f):f}if(typeof t==`bigint`){var m=String(t)+`n`;return d?ae(t,m):m}var h=s.depth===void 0?5:s.depth;if(r===void 0&&(r=0),r>=h&&h>0&&typeof t==`object`)return me(t)?`[Array]`:`[Object]`;var g=ze(s,r);if(o===void 0)o=[];else if(Ee(o,t)>=0)return`[Circular]`;function y(t,n,i){if(n&&(o=w.call(o),o.push(n)),i){var a={depth:s.depth};return Ce(s,`quoteStyle`)&&(a.quoteStyle=s.quoteStyle),e(t,a,r+1,o)}return e(t,s,r+1,o)}if(typeof t==`function`&&!he(t)){var x=Te(t),T=Ve(t,y);return`[Function`+(x?`: `+x:` (anonymous)`)+`]`+(T.length>0?` { `+C.call(T,`, `)+` }`:``)}if(be(t)){var D=te?v.call(String(t),/^(Symbol\(.*\))_[^)]*$/,`$1`):ee.call(t);return typeof t==`object`&&!te?Fe(D):D}if(Me(t)){for(var se=`<`+b.call(String(t.nodeName)),ue=t.attributes||[],pe=0;pe<ue.length;pe++)se+=` `+ue[pe].name+`=`+de(fe(ue[pe].value),`double`,s);return se+=`>`,t.childNodes&&t.childNodes.length&&(se+=`...`),se+=`</`+b.call(String(t.nodeName))+`>`,se}if(me(t)){if(t.length===0)return`[]`;var Se=Ve(t,y);return g&&!Re(Se)?`[`+Be(Se,g)+`]`:`[ `+C.call(Se,`, `)+` ]`}if(ge(t)){var Pe=Ve(t,y);return!(`cause`in Error.prototype)&&`cause`in t&&!re.call(t,`cause`)?`{ [`+String(t)+`] `+C.call(S.call(`[cause]: `+y(t.cause),Pe),`, `)+` }`:Pe.length===0?`[`+String(t)+`]`:`{ [`+String(t)+`] `+C.call(Pe,`, `)+` }`}if(typeof t==`object`&&u){if(ce&&typeof t[ce]==`function`&&oe)return oe(t,{depth:h-r});if(u!==`symbol`&&typeof t.inspect==`function`)return t.inspect()}if(De(t)){var He=[];return a&&a.call(t,function(e,n){He.push(y(n,t,!0)+` => `+y(e,t))}),Le(`Map`,i.call(t),He,g)}if(Ae(t)){var Ue=[];return l&&l.call(t,function(e){Ue.push(y(e,t))}),Le(`Set`,c.call(t),Ue,g)}if(Oe(t))return Ie(`WeakMap`);if(je(t))return Ie(`WeakSet`);if(ke(t))return Ie(`WeakRef`);if(ve(t))return Fe(y(Number(t)));if(xe(t))return Fe(y(E.call(t)));if(ye(t))return Fe(p.call(t));if(_e(t))return Fe(y(String(t)));if(typeof window<`u`&&t===window)return`{ [object Window] }`;if(typeof globalThis<`u`&&t===globalThis||typeof global<`u`&&t===global)return`{ [object globalThis] }`;if(!O(t)&&!he(t)){var k=Ve(t,y),We=ie?ie(t)===Object.prototype:t instanceof Object||t.constructor===Object,Ge=t instanceof Object?``:`null prototype`,Ke=!We&&ne&&Object(t)===t&&ne in t?_.call(we(t),8,-1):Ge?`Object`:``,qe=(We||typeof t.constructor!=`function`?``:t.constructor.name?t.constructor.name+` `:``)+(Ke||Ge?`[`+C.call(S.call([],Ke||[],Ge||[]),`: `)+`] `:``);return k.length===0?qe+`{}`:g?qe+`{`+Be(k,g)+`}`:qe+`{ `+C.call(k,`, `)+` }`}return String(t)};function de(e,t,n){var r=le[n.quoteStyle||t];return r+e+r}function fe(e){return v.call(String(e),/"/g,`&quot;`)}function pe(e){return!ne||!(typeof e==`object`&&(ne in e||e[ne]!==void 0))}function me(e){return we(e)===`[object Array]`&&pe(e)}function O(e){return we(e)===`[object Date]`&&pe(e)}function he(e){return we(e)===`[object RegExp]`&&pe(e)}function ge(e){return we(e)===`[object Error]`&&pe(e)}function _e(e){return we(e)===`[object String]`&&pe(e)}function ve(e){return we(e)===`[object Number]`&&pe(e)}function ye(e){return we(e)===`[object Boolean]`&&pe(e)}function be(e){if(te)return e&&typeof e==`object`&&e instanceof Symbol;if(typeof e==`symbol`)return!0;if(!e||typeof e!=`object`||!ee)return!1;try{return ee.call(e),!0}catch{}return!1}function xe(e){if(!e||typeof e!=`object`||!E)return!1;try{return E.call(e),!0}catch{}return!1}var Se=Object.prototype.hasOwnProperty||function(e){return e in this};function Ce(e,t){return Se.call(e,t)}function we(e){return m.call(e)}function Te(e){if(e.name)return e.name;var t=g.call(h.call(e),/^function\s*([\w$]+)/);return t?t[1]:null}function Ee(e,t){if(e.indexOf)return e.indexOf(t);for(var n=0,r=e.length;n<r;n++)if(e[n]===t)return n;return-1}function De(e){if(!i||!e||typeof e!=`object`)return!1;try{i.call(e);try{c.call(e)}catch{return!0}return e instanceof Map}catch{}return!1}function Oe(e){if(!u||!e||typeof e!=`object`)return!1;try{u.call(e,u);try{d.call(e,d)}catch{return!0}return e instanceof WeakMap}catch{}return!1}function ke(e){if(!f||!e||typeof e!=`object`)return!1;try{return f.call(e),!0}catch{}return!1}function Ae(e){if(!c||!e||typeof e!=`object`)return!1;try{c.call(e);try{i.call(e)}catch{return!0}return e instanceof Set}catch{}return!1}function je(e){if(!d||!e||typeof e!=`object`)return!1;try{d.call(e,d);try{u.call(e,u)}catch{return!0}return e instanceof WeakSet}catch{}return!1}function Me(e){return!e||typeof e!=`object`?!1:typeof HTMLElement<`u`&&e instanceof HTMLElement?!0:typeof e.nodeName==`string`&&typeof e.getAttribute==`function`}function Ne(e,t){if(e.length>t.maxStringLength){var n=e.length-t.maxStringLength,r=`... `+n+` more character`+(n>1?`s`:``);return Ne(_.call(e,0,t.maxStringLength),t)+r}var i=ue[t.quoteStyle||`single`];return i.lastIndex=0,de(v.call(v.call(e,i,`\\$1`),/[\x00-\x1f]/g,Pe),`single`,t)}function Pe(e){var t=e.charCodeAt(0),n={8:`b`,9:`t`,10:`n`,12:`f`,13:`r`}[t];return n?`\\`+n:`\\x`+(t<16?`0`:``)+y.call(t.toString(16))}function Fe(e){return`Object(`+e+`)`}function Ie(e){return e+` { ? }`}function Le(e,t,n,r){var i=r?Be(n,r):C.call(n,`, `);return e+` (`+t+`) {`+i+`}`}function Re(e){for(var t=0;t<e.length;t++)if(Ee(e[t],`
`)>=0)return!1;return!0}function ze(e,t){var n;if(e.indent===`	`)n=`	`;else if(typeof e.indent==`number`&&e.indent>0)n=C.call(Array(e.indent+1),` `);else return null;return{base:n,prev:C.call(Array(t+1),n)}}function Be(e,t){if(e.length===0)return``;var n=`
`+t.prev+t.base;return n+C.call(e,`,`+n)+`
`+t.prev}function Ve(e,t){var n=me(e),r=[];if(n){r.length=e.length;for(var i=0;i<e.length;i++)r[i]=Ce(e,i)?t(e[i],e):``}var a=typeof D==`function`?D(e):[],o;if(te){o={};for(var s=0;s<a.length;s++)o[`$`+a[s]]=a[s]}for(var c in e)Ce(e,c)&&(n&&String(Number(c))===c&&c<e.length||te&&o[`$`+c]instanceof Symbol||(x.call(/[^\w$]/,c)?r.push(t(c,e)+`: `+t(e[c],e)):r.push(c+`: `+t(e[c],e))));if(typeof D==`function`)for(var l=0;l<a.length;l++)re.call(e,a[l])&&r.push(`[`+t(a[l])+`]: `+t(e[a[l]],e));return r}})),V_=n(((e,t)=>{var n=B_(),r=R_(),i=function(e,t,n){for(var r=e,i;(i=r.next)!=null;r=i)if(i.key===t)return r.next=i.next,n||(i.next=e.next,e.next=i),i},a=function(e,t){if(e){var n=i(e,t);return n&&n.value}},o=function(e,t,n){var r=i(e,t);r?r.value=n:e.next={key:t,next:e.next,value:n}},s=function(e,t){return e?!!i(e,t):!1},c=function(e,t){if(e)return i(e,t,!0)};t.exports=function(){var e,t={assert:function(e){if(!t.has(e))throw new r(`Side channel does not contain `+n(e))},delete:function(t){var n=e&&e.next,r=c(e,t);return r&&n&&n===r&&(e=void 0),!!r},get:function(t){return a(e,t)},has:function(t){return s(e,t)},set:function(t,n){e||={next:void 0},o(e,t,n)}};return t}})),H_=n(((e,t)=>{t.exports=Object})),U_=n(((e,t)=>{t.exports=Error})),W_=n(((e,t)=>{t.exports=EvalError})),G_=n(((e,t)=>{t.exports=RangeError})),K_=n(((e,t)=>{t.exports=ReferenceError})),q_=n(((e,t)=>{t.exports=SyntaxError})),J_=n(((e,t)=>{t.exports=URIError})),Y_=n(((e,t)=>{t.exports=Math.abs})),X_=n(((e,t)=>{t.exports=Math.floor})),Z_=n(((e,t)=>{t.exports=Math.max})),Q_=n(((e,t)=>{t.exports=Math.min})),$_=n(((e,t)=>{t.exports=Math.pow})),ev=n(((e,t)=>{t.exports=Math.round})),tv=n(((e,t)=>{t.exports=Number.isNaN||function(e){return e!==e}})),nv=n(((e,t)=>{var n=tv();t.exports=function(e){return n(e)||e===0?e:e<0?-1:1}})),rv=n(((e,t)=>{t.exports=Object.getOwnPropertyDescriptor})),iv=n(((e,t)=>{var n=rv();if(n)try{n([],`length`)}catch{n=null}t.exports=n})),av=n(((e,t)=>{var n=Object.defineProperty||!1;if(n)try{n({},`a`,{value:1})}catch{n=!1}t.exports=n})),ov=n(((e,t)=>{t.exports=function(){if(typeof Symbol!=`function`||typeof Object.getOwnPropertySymbols!=`function`)return!1;if(typeof Symbol.iterator==`symbol`)return!0;var e={},t=Symbol(`test`),n=Object(t);if(typeof t==`string`||Object.prototype.toString.call(t)!==`[object Symbol]`||Object.prototype.toString.call(n)!==`[object Symbol]`)return!1;var r=42;for(var i in e[t]=r,e)return!1;if(typeof Object.keys==`function`&&Object.keys(e).length!==0||typeof Object.getOwnPropertyNames==`function`&&Object.getOwnPropertyNames(e).length!==0)return!1;var a=Object.getOwnPropertySymbols(e);if(a.length!==1||a[0]!==t||!Object.prototype.propertyIsEnumerable.call(e,t))return!1;if(typeof Object.getOwnPropertyDescriptor==`function`){var o=Object.getOwnPropertyDescriptor(e,t);if(o.value!==r||o.enumerable!==!0)return!1}return!0}})),sv=n(((e,t)=>{var n=typeof Symbol<`u`&&Symbol,r=ov();t.exports=function(){return typeof n!=`function`||typeof Symbol!=`function`||typeof n(`foo`)!=`symbol`||typeof Symbol(`bar`)!=`symbol`?!1:r()}})),cv=n(((e,t)=>{t.exports=typeof Reflect<`u`&&Reflect.getPrototypeOf||null})),lv=n(((e,t)=>{t.exports=H_().getPrototypeOf||null})),uv=n(((e,t)=>{var n=`Function.prototype.bind called on incompatible `,r=Object.prototype.toString,i=Math.max,a=`[object Function]`,o=function(e,t){for(var n=[],r=0;r<e.length;r+=1)n[r]=e[r];for(var i=0;i<t.length;i+=1)n[i+e.length]=t[i];return n},s=function(e,t){for(var n=[],r=t||0,i=0;r<e.length;r+=1,i+=1)n[i]=e[r];return n},c=function(e,t){for(var n=``,r=0;r<e.length;r+=1)n+=e[r],r+1<e.length&&(n+=t);return n};t.exports=function(e){var t=this;if(typeof t!=`function`||r.apply(t)!==a)throw TypeError(n+t);for(var l=s(arguments,1),u,d=function(){if(this instanceof u){var n=t.apply(this,o(l,arguments));return Object(n)===n?n:this}return t.apply(e,o(l,arguments))},f=i(0,t.length-l.length),p=[],m=0;m<f;m++)p[m]=`$`+m;if(u=Function(`binder`,`return function (`+c(p,`,`)+`){ return binder.apply(this,arguments); }`)(d),t.prototype){var h=function(){};h.prototype=t.prototype,u.prototype=new h,h.prototype=null}return u}})),dv=n(((e,t)=>{var n=uv();t.exports=Function.prototype.bind||n})),fv=n(((e,t)=>{t.exports=Function.prototype.call})),pv=n(((e,t)=>{t.exports=Function.prototype.apply})),mv=n(((e,t)=>{t.exports=typeof Reflect<`u`&&Reflect&&Reflect.apply})),hv=n(((e,t)=>{var n=dv(),r=pv(),i=fv();t.exports=mv()||n.call(i,r)})),gv=n(((e,t)=>{var n=dv(),r=R_(),i=fv(),a=hv();t.exports=function(e){if(e.length<1||typeof e[0]!=`function`)throw new r(`a function is required`);return a(n,i,e)}})),_v=n(((e,t)=>{var n=gv(),r=iv(),i;try{i=[].__proto__===Array.prototype}catch(e){if(!e||typeof e!=`object`||!(`code`in e)||e.code!==`ERR_PROTO_ACCESS`)throw e}var a=!!i&&r&&r(Object.prototype,`__proto__`),o=Object,s=o.getPrototypeOf;t.exports=a&&typeof a.get==`function`?n([a.get]):typeof s==`function`?function(e){return s(e==null?e:o(e))}:!1})),vv=n(((e,t)=>{var n=cv(),r=lv(),i=_v();t.exports=n?function(e){return n(e)}:r?function(e){if(!e||typeof e!=`object`&&typeof e!=`function`)throw TypeError(`getProto: not an object`);return r(e)}:i?function(e){return i(e)}:null})),yv=n(((e,t)=>{var n=Function.prototype.call,r=Object.prototype.hasOwnProperty;t.exports=dv().call(n,r)})),bv=n(((e,t)=>{var n,r=H_(),i=U_(),a=W_(),o=G_(),s=K_(),c=q_(),l=R_(),u=J_(),d=Y_(),f=X_(),p=Z_(),m=Q_(),h=$_(),g=ev(),_=nv(),v=Function,y=function(e){try{return v(`"use strict"; return (`+e+`).constructor;`)()}catch{}},b=iv(),x=av(),S=function(){throw new l},C=b?function(){try{return arguments.callee,S}catch{try{return b(arguments,`callee`).get}catch{return S}}}():S,w=sv()(),T=vv(),E=lv(),D=cv(),ee=pv(),te=fv(),ne={},re=typeof Uint8Array>`u`||!T?n:T(Uint8Array),ie={__proto__:null,"%AggregateError%":typeof AggregateError>`u`?n:AggregateError,"%Array%":Array,"%ArrayBuffer%":typeof ArrayBuffer>`u`?n:ArrayBuffer,"%ArrayIteratorPrototype%":w&&T?T([][Symbol.iterator]()):n,"%AsyncFromSyncIteratorPrototype%":n,"%AsyncFunction%":ne,"%AsyncGenerator%":ne,"%AsyncGeneratorFunction%":ne,"%AsyncIteratorPrototype%":ne,"%Atomics%":typeof Atomics>`u`?n:Atomics,"%BigInt%":typeof BigInt>`u`?n:BigInt,"%BigInt64Array%":typeof BigInt64Array>`u`?n:BigInt64Array,"%BigUint64Array%":typeof BigUint64Array>`u`?n:BigUint64Array,"%Boolean%":Boolean,"%DataView%":typeof DataView>`u`?n:DataView,"%Date%":Date,"%decodeURI%":decodeURI,"%decodeURIComponent%":decodeURIComponent,"%encodeURI%":encodeURI,"%encodeURIComponent%":encodeURIComponent,"%Error%":i,"%eval%":eval,"%EvalError%":a,"%Float16Array%":typeof Float16Array>`u`?n:Float16Array,"%Float32Array%":typeof Float32Array>`u`?n:Float32Array,"%Float64Array%":typeof Float64Array>`u`?n:Float64Array,"%FinalizationRegistry%":typeof FinalizationRegistry>`u`?n:FinalizationRegistry,"%Function%":v,"%GeneratorFunction%":ne,"%Int8Array%":typeof Int8Array>`u`?n:Int8Array,"%Int16Array%":typeof Int16Array>`u`?n:Int16Array,"%Int32Array%":typeof Int32Array>`u`?n:Int32Array,"%isFinite%":isFinite,"%isNaN%":isNaN,"%IteratorPrototype%":w&&T?T(T([][Symbol.iterator]())):n,"%JSON%":typeof JSON==`object`?JSON:n,"%Map%":typeof Map>`u`?n:Map,"%MapIteratorPrototype%":typeof Map>`u`||!w||!T?n:T(new Map()[Symbol.iterator]()),"%Math%":Math,"%Number%":Number,"%Object%":r,"%Object.getOwnPropertyDescriptor%":b,"%parseFloat%":parseFloat,"%parseInt%":parseInt,"%Promise%":typeof Promise>`u`?n:Promise,"%Proxy%":typeof Proxy>`u`?n:Proxy,"%RangeError%":o,"%ReferenceError%":s,"%Reflect%":typeof Reflect>`u`?n:Reflect,"%RegExp%":RegExp,"%Set%":typeof Set>`u`?n:Set,"%SetIteratorPrototype%":typeof Set>`u`||!w||!T?n:T(new Set()[Symbol.iterator]()),"%SharedArrayBuffer%":typeof SharedArrayBuffer>`u`?n:SharedArrayBuffer,"%String%":String,"%StringIteratorPrototype%":w&&T?T(``[Symbol.iterator]()):n,"%Symbol%":w?Symbol:n,"%SyntaxError%":c,"%ThrowTypeError%":C,"%TypedArray%":re,"%TypeError%":l,"%Uint8Array%":typeof Uint8Array>`u`?n:Uint8Array,"%Uint8ClampedArray%":typeof Uint8ClampedArray>`u`?n:Uint8ClampedArray,"%Uint16Array%":typeof Uint16Array>`u`?n:Uint16Array,"%Uint32Array%":typeof Uint32Array>`u`?n:Uint32Array,"%URIError%":u,"%WeakMap%":typeof WeakMap>`u`?n:WeakMap,"%WeakRef%":typeof WeakRef>`u`?n:WeakRef,"%WeakSet%":typeof WeakSet>`u`?n:WeakSet,"%Function.prototype.call%":te,"%Function.prototype.apply%":ee,"%Object.defineProperty%":x,"%Object.getPrototypeOf%":E,"%Math.abs%":d,"%Math.floor%":f,"%Math.max%":p,"%Math.min%":m,"%Math.pow%":h,"%Math.round%":g,"%Math.sign%":_,"%Reflect.getPrototypeOf%":D};if(T)try{null.error}catch(e){ie[`%Error.prototype%`]=T(T(e))}var ae=function e(t){var n;if(t===`%AsyncFunction%`)n=y(`async function () {}`);else if(t===`%GeneratorFunction%`)n=y(`function* () {}`);else if(t===`%AsyncGeneratorFunction%`)n=y(`async function* () {}`);else if(t===`%AsyncGenerator%`){var r=e(`%AsyncGeneratorFunction%`);r&&(n=r.prototype)}else if(t===`%AsyncIteratorPrototype%`){var i=e(`%AsyncGenerator%`);i&&T&&(n=T(i.prototype))}return ie[t]=n,n},oe={__proto__:null,"%ArrayBufferPrototype%":[`ArrayBuffer`,`prototype`],"%ArrayPrototype%":[`Array`,`prototype`],"%ArrayProto_entries%":[`Array`,`prototype`,`entries`],"%ArrayProto_forEach%":[`Array`,`prototype`,`forEach`],"%ArrayProto_keys%":[`Array`,`prototype`,`keys`],"%ArrayProto_values%":[`Array`,`prototype`,`values`],"%AsyncFunctionPrototype%":[`AsyncFunction`,`prototype`],"%AsyncGenerator%":[`AsyncGeneratorFunction`,`prototype`],"%AsyncGeneratorPrototype%":[`AsyncGeneratorFunction`,`prototype`,`prototype`],"%BooleanPrototype%":[`Boolean`,`prototype`],"%DataViewPrototype%":[`DataView`,`prototype`],"%DatePrototype%":[`Date`,`prototype`],"%ErrorPrototype%":[`Error`,`prototype`],"%EvalErrorPrototype%":[`EvalError`,`prototype`],"%Float32ArrayPrototype%":[`Float32Array`,`prototype`],"%Float64ArrayPrototype%":[`Float64Array`,`prototype`],"%FunctionPrototype%":[`Function`,`prototype`],"%Generator%":[`GeneratorFunction`,`prototype`],"%GeneratorPrototype%":[`GeneratorFunction`,`prototype`,`prototype`],"%Int8ArrayPrototype%":[`Int8Array`,`prototype`],"%Int16ArrayPrototype%":[`Int16Array`,`prototype`],"%Int32ArrayPrototype%":[`Int32Array`,`prototype`],"%JSONParse%":[`JSON`,`parse`],"%JSONStringify%":[`JSON`,`stringify`],"%MapPrototype%":[`Map`,`prototype`],"%NumberPrototype%":[`Number`,`prototype`],"%ObjectPrototype%":[`Object`,`prototype`],"%ObjProto_toString%":[`Object`,`prototype`,`toString`],"%ObjProto_valueOf%":[`Object`,`prototype`,`valueOf`],"%PromisePrototype%":[`Promise`,`prototype`],"%PromiseProto_then%":[`Promise`,`prototype`,`then`],"%Promise_all%":[`Promise`,`all`],"%Promise_reject%":[`Promise`,`reject`],"%Promise_resolve%":[`Promise`,`resolve`],"%RangeErrorPrototype%":[`RangeError`,`prototype`],"%ReferenceErrorPrototype%":[`ReferenceError`,`prototype`],"%RegExpPrototype%":[`RegExp`,`prototype`],"%SetPrototype%":[`Set`,`prototype`],"%SharedArrayBufferPrototype%":[`SharedArrayBuffer`,`prototype`],"%StringPrototype%":[`String`,`prototype`],"%SymbolPrototype%":[`Symbol`,`prototype`],"%SyntaxErrorPrototype%":[`SyntaxError`,`prototype`],"%TypedArrayPrototype%":[`TypedArray`,`prototype`],"%TypeErrorPrototype%":[`TypeError`,`prototype`],"%Uint8ArrayPrototype%":[`Uint8Array`,`prototype`],"%Uint8ClampedArrayPrototype%":[`Uint8ClampedArray`,`prototype`],"%Uint16ArrayPrototype%":[`Uint16Array`,`prototype`],"%Uint32ArrayPrototype%":[`Uint32Array`,`prototype`],"%URIErrorPrototype%":[`URIError`,`prototype`],"%WeakMapPrototype%":[`WeakMap`,`prototype`],"%WeakSetPrototype%":[`WeakSet`,`prototype`]},se=dv(),ce=yv(),le=se.call(te,Array.prototype.concat),ue=se.call(ee,Array.prototype.splice),de=se.call(te,String.prototype.replace),fe=se.call(te,String.prototype.slice),pe=se.call(te,RegExp.prototype.exec),me=/[^%.[\]]+|\[(?:(-?\d+(?:\.\d+)?)|(["'])((?:(?!\2)[^\\]|\\.)*?)\2)\]|(?=(?:\.|\[\])(?:\.|\[\]|%$))/g,O=/\\(\\)?/g,he=function(e){var t=fe(e,0,1),n=fe(e,-1);if(t===`%`&&n!==`%`)throw new c("invalid intrinsic syntax, expected closing `%`");if(n===`%`&&t!==`%`)throw new c("invalid intrinsic syntax, expected opening `%`");var r=[];return de(e,me,function(e,t,n,i){r[r.length]=n?de(i,O,`$1`):t||e}),r},ge=function(e,t){var n=e,r;if(ce(oe,n)&&(r=oe[n],n=`%`+r[0]+`%`),ce(ie,n)){var i=ie[n];if(i===ne&&(i=ae(n)),i===void 0&&!t)throw new l(`intrinsic `+e+` exists, but is not available. Please file an issue!`);return{alias:r,name:n,value:i}}throw new c(`intrinsic `+e+` does not exist!`)};t.exports=function(e,t){if(typeof e!=`string`||e.length===0)throw new l(`intrinsic name must be a non-empty string`);if(arguments.length>1&&typeof t!=`boolean`)throw new l(`"allowMissing" argument must be a boolean`);if(pe(/^%?[^%]*%?$/,e)===null)throw new c("`%` may not be present anywhere but at the beginning and end of the intrinsic name");var n=he(e),r=n.length>0?n[0]:``,i=ge(`%`+r+`%`,t),a=i.name,o=i.value,s=!1,u=i.alias;u&&(r=u[0],ue(n,le([0,1],u)));for(var d=1,f=!0;d<n.length;d+=1){var p=n[d],m=fe(p,0,1),h=fe(p,-1);if((m===`"`||m===`'`||m==="`"||h===`"`||h===`'`||h==="`")&&m!==h)throw new c(`property names with quotes must have matching quotes`);if((p===`constructor`||!f)&&(s=!0),r+=`.`+p,a=`%`+r+`%`,ce(ie,a))o=ie[a];else if(o!=null){if(!(p in o)){if(!t)throw new l(`base intrinsic for `+e+` exists, but the property is not available.`);return}if(b&&d+1>=n.length){var g=b(o,p);f=!!g,o=f&&`get`in g&&!(`originalValue`in g.get)?g.get:o[p]}else f=ce(o,p),o=o[p];f&&!s&&(ie[a]=o)}}return o}})),xv=n(((e,t)=>{var n=bv(),r=gv(),i=r([n(`%String.prototype.indexOf%`)]);t.exports=function(e,t){var a=n(e,!!t);return typeof a==`function`&&i(e,`.prototype.`)>-1?r([a]):a}})),Sv=n(((e,t)=>{var n=bv(),r=xv(),i=B_(),a=R_(),o=n(`%Map%`,!0),s=r(`Map.prototype.get`,!0),c=r(`Map.prototype.set`,!0),l=r(`Map.prototype.has`,!0),u=r(`Map.prototype.delete`,!0),d=r(`Map.prototype.size`,!0);t.exports=!!o&&function(){var e,t={assert:function(e){if(!t.has(e))throw new a(`Side channel does not contain `+i(e))},delete:function(t){if(e){var n=u(e,t);return d(e)===0&&(e=void 0),n}return!1},get:function(t){if(e)return s(e,t)},has:function(t){return e?l(e,t):!1},set:function(t,n){e||=new o,c(e,t,n)}};return t}})),Cv=n(((e,t)=>{var n=bv(),r=xv(),i=B_(),a=Sv(),o=R_(),s=n(`%WeakMap%`,!0),c=r(`WeakMap.prototype.get`,!0),l=r(`WeakMap.prototype.set`,!0),u=r(`WeakMap.prototype.has`,!0),d=r(`WeakMap.prototype.delete`,!0);t.exports=s?function(){var e,t,n={assert:function(e){if(!n.has(e))throw new o(`Side channel does not contain `+i(e))},delete:function(n){if(s&&n&&(typeof n==`object`||typeof n==`function`)){if(e)return d(e,n)}else if(a&&t)return t.delete(n);return!1},get:function(n){return s&&n&&(typeof n==`object`||typeof n==`function`)&&e?c(e,n):t&&t.get(n)},has:function(n){return s&&n&&(typeof n==`object`||typeof n==`function`)&&e?u(e,n):!!t&&t.has(n)},set:function(n,r){s&&n&&(typeof n==`object`||typeof n==`function`)?(e||=new s,l(e,n,r)):a&&(t||=a(),t.set(n,r))}};return n}:a})),wv=n(((e,t)=>{var n=R_(),r=B_(),i=V_(),a=Sv(),o=Cv()||a||i;t.exports=function(){var e,t={assert:function(e){if(!t.has(e))throw new n(`Side channel does not contain `+r(e))},delete:function(t){return!!e&&e.delete(t)},get:function(t){return e&&e.get(t)},has:function(t){return!!e&&e.has(t)},set:function(t,n){e||=o(),e.set(t,n)}};return t}})),Tv=n(((e,t)=>{var n=String.prototype.replace,r=/%20/g,i={RFC1738:`RFC1738`,RFC3986:`RFC3986`};t.exports={default:i.RFC3986,formatters:{RFC1738:function(e){return n.call(e,r,`+`)},RFC3986:function(e){return String(e)}},RFC1738:i.RFC1738,RFC3986:i.RFC3986}})),Ev=n(((e,t)=>{var n=Tv(),r=Object.prototype.hasOwnProperty,i=Array.isArray,a=function(){for(var e=[],t=0;t<256;++t)e.push(`%`+((t<16?`0`:``)+t.toString(16)).toUpperCase());return e}(),o=function(e){for(;e.length>1;){var t=e.pop(),n=t.obj[t.prop];if(i(n)){for(var r=[],a=0;a<n.length;++a)n[a]!==void 0&&r.push(n[a]);t.obj[t.prop]=r}}},s=function(e,t){for(var n=t&&t.plainObjects?{__proto__:null}:{},r=0;r<e.length;++r)e[r]!==void 0&&(n[r]=e[r]);return n},c=function e(t,n,a){if(!n)return t;if(typeof n!=`object`&&typeof n!=`function`){if(i(t))t.push(n);else if(t&&typeof t==`object`)(a&&(a.plainObjects||a.allowPrototypes)||!r.call(Object.prototype,n))&&(t[n]=!0);else return[t,n];return t}if(!t||typeof t!=`object`)return[t].concat(n);var o=t;return i(t)&&!i(n)&&(o=s(t,a)),i(t)&&i(n)?(n.forEach(function(n,i){if(r.call(t,i)){var o=t[i];o&&typeof o==`object`&&n&&typeof n==`object`?t[i]=e(o,n,a):t.push(n)}else t[i]=n}),t):Object.keys(n).reduce(function(t,i){var o=n[i];return r.call(t,i)?t[i]=e(t[i],o,a):t[i]=o,t},o)},l=function(e,t){return Object.keys(t).reduce(function(e,n){return e[n]=t[n],e},e)},u=function(e,t,n){var r=e.replace(/\+/g,` `);if(n===`iso-8859-1`)return r.replace(/%[0-9a-f]{2}/gi,unescape);try{return decodeURIComponent(r)}catch{return r}},d=1024;t.exports={arrayToObject:s,assign:l,combine:function(e,t){return[].concat(e,t)},compact:function(e){for(var t=[{obj:{o:e},prop:`o`}],n=[],r=0;r<t.length;++r)for(var i=t[r],a=i.obj[i.prop],s=Object.keys(a),c=0;c<s.length;++c){var l=s[c],u=a[l];typeof u==`object`&&u&&n.indexOf(u)===-1&&(t.push({obj:a,prop:l}),n.push(u))}return o(t),e},decode:u,encode:function(e,t,r,i,o){if(e.length===0)return e;var s=e;if(typeof e==`symbol`?s=Symbol.prototype.toString.call(e):typeof e!=`string`&&(s=String(e)),r===`iso-8859-1`)return escape(s).replace(/%u[0-9a-f]{4}/gi,function(e){return`%26%23`+parseInt(e.slice(2),16)+`%3B`});for(var c=``,l=0;l<s.length;l+=d){for(var u=s.length>=d?s.slice(l,l+d):s,f=[],p=0;p<u.length;++p){var m=u.charCodeAt(p);if(m===45||m===46||m===95||m===126||m>=48&&m<=57||m>=65&&m<=90||m>=97&&m<=122||o===n.RFC1738&&(m===40||m===41)){f[f.length]=u.charAt(p);continue}if(m<128){f[f.length]=a[m];continue}if(m<2048){f[f.length]=a[192|m>>6]+a[128|m&63];continue}if(m<55296||m>=57344){f[f.length]=a[224|m>>12]+a[128|m>>6&63]+a[128|m&63];continue}p+=1,m=65536+((m&1023)<<10|u.charCodeAt(p)&1023),f[f.length]=a[240|m>>18]+a[128|m>>12&63]+a[128|m>>6&63]+a[128|m&63]}c+=f.join(``)}return c},isBuffer:function(e){return!e||typeof e!=`object`?!1:!!(e.constructor&&e.constructor.isBuffer&&e.constructor.isBuffer(e))},isRegExp:function(e){return Object.prototype.toString.call(e)===`[object RegExp]`},maybeMap:function(e,t){if(i(e)){for(var n=[],r=0;r<e.length;r+=1)n.push(t(e[r]));return n}return t(e)},merge:c}})),Dv=n(((e,t)=>{var n=wv(),r=Ev(),i=Tv(),a=Object.prototype.hasOwnProperty,o={brackets:function(e){return e+`[]`},comma:`comma`,indices:function(e,t){return e+`[`+t+`]`},repeat:function(e){return e}},s=Array.isArray,c=Array.prototype.push,l=function(e,t){c.apply(e,s(t)?t:[t])},u=Date.prototype.toISOString,d=i.default,f={addQueryPrefix:!1,allowDots:!1,allowEmptyArrays:!1,arrayFormat:`indices`,charset:`utf-8`,charsetSentinel:!1,commaRoundTrip:!1,delimiter:`&`,encode:!0,encodeDotInKeys:!1,encoder:r.encode,encodeValuesOnly:!1,filter:void 0,format:d,formatter:i.formatters[d],indices:!1,serializeDate:function(e){return u.call(e)},skipNulls:!1,strictNullHandling:!1},p=function(e){return typeof e==`string`||typeof e==`number`||typeof e==`boolean`||typeof e==`symbol`||typeof e==`bigint`},m={},h=function e(t,i,a,o,c,u,d,h,g,_,v,y,b,x,S,C,w,T){for(var E=t,D=T,ee=0,te=!1;(D=D.get(m))!==void 0&&!te;){var ne=D.get(t);if(ee+=1,ne!==void 0){if(ne===ee)throw RangeError(`Cyclic object value`);te=!0}D.get(m)===void 0&&(ee=0)}if(typeof _==`function`?E=_(i,E):E instanceof Date?E=b(E):a===`comma`&&s(E)&&(E=r.maybeMap(E,function(e){return e instanceof Date?b(e):e})),E===null){if(u)return g&&!C?g(i,f.encoder,w,`key`,x):i;E=``}if(p(E)||r.isBuffer(E))return g?[S(C?i:g(i,f.encoder,w,`key`,x))+`=`+S(g(E,f.encoder,w,`value`,x))]:[S(i)+`=`+S(String(E))];var re=[];if(E===void 0)return re;var ie;if(a===`comma`&&s(E))C&&g&&(E=r.maybeMap(E,g)),ie=[{value:E.length>0?E.join(`,`)||null:void 0}];else if(s(_))ie=_;else{var ae=Object.keys(E);ie=v?ae.sort(v):ae}var oe=h?String(i).replace(/\./g,`%2E`):String(i),se=o&&s(E)&&E.length===1?oe+`[]`:oe;if(c&&s(E)&&E.length===0)return se+`[]`;for(var ce=0;ce<ie.length;++ce){var le=ie[ce],ue=typeof le==`object`&&le&&le.value!==void 0?le.value:E[le];if(!(d&&ue===null)){var de=y&&h?String(le).replace(/\./g,`%2E`):String(le),fe=s(E)?typeof a==`function`?a(se,de):se:se+(y?`.`+de:`[`+de+`]`);T.set(t,ee);var pe=n();pe.set(m,T),l(re,e(ue,fe,a,o,c,u,d,h,a===`comma`&&C&&s(E)?null:g,_,v,y,b,x,S,C,w,pe))}}return re},g=function(e){if(!e)return f;if(e.allowEmptyArrays!==void 0&&typeof e.allowEmptyArrays!=`boolean`)throw TypeError("`allowEmptyArrays` option can only be `true` or `false`, when provided");if(e.encodeDotInKeys!==void 0&&typeof e.encodeDotInKeys!=`boolean`)throw TypeError("`encodeDotInKeys` option can only be `true` or `false`, when provided");if(e.encoder!==null&&e.encoder!==void 0&&typeof e.encoder!=`function`)throw TypeError(`Encoder has to be a function.`);var t=e.charset||f.charset;if(e.charset!==void 0&&e.charset!==`utf-8`&&e.charset!==`iso-8859-1`)throw TypeError(`The charset option must be either utf-8, iso-8859-1, or undefined`);var n=i.default;if(e.format!==void 0){if(!a.call(i.formatters,e.format))throw TypeError(`Unknown format option provided.`);n=e.format}var r=i.formatters[n],c=f.filter;(typeof e.filter==`function`||s(e.filter))&&(c=e.filter);var l=e.arrayFormat in o?e.arrayFormat:`indices`in e?e.indices?`indices`:`repeat`:f.arrayFormat;if(`commaRoundTrip`in e&&typeof e.commaRoundTrip!=`boolean`)throw TypeError("`commaRoundTrip` must be a boolean, or absent");var u=e.allowDots===void 0?e.encodeDotInKeys===!0?!0:f.allowDots:!!e.allowDots;return{addQueryPrefix:typeof e.addQueryPrefix==`boolean`?e.addQueryPrefix:f.addQueryPrefix,allowDots:u,allowEmptyArrays:typeof e.allowEmptyArrays==`boolean`?!!e.allowEmptyArrays:f.allowEmptyArrays,arrayFormat:l,charset:t,charsetSentinel:typeof e.charsetSentinel==`boolean`?e.charsetSentinel:f.charsetSentinel,commaRoundTrip:!!e.commaRoundTrip,delimiter:e.delimiter===void 0?f.delimiter:e.delimiter,encode:typeof e.encode==`boolean`?e.encode:f.encode,encodeDotInKeys:typeof e.encodeDotInKeys==`boolean`?e.encodeDotInKeys:f.encodeDotInKeys,encoder:typeof e.encoder==`function`?e.encoder:f.encoder,encodeValuesOnly:typeof e.encodeValuesOnly==`boolean`?e.encodeValuesOnly:f.encodeValuesOnly,filter:c,format:n,formatter:r,serializeDate:typeof e.serializeDate==`function`?e.serializeDate:f.serializeDate,skipNulls:typeof e.skipNulls==`boolean`?e.skipNulls:f.skipNulls,sort:typeof e.sort==`function`?e.sort:null,strictNullHandling:typeof e.strictNullHandling==`boolean`?e.strictNullHandling:f.strictNullHandling}};t.exports=function(e,t){var r=e,i=g(t),a,c;typeof i.filter==`function`?(c=i.filter,r=c(``,r)):s(i.filter)&&(c=i.filter,a=c);var u=[];if(typeof r!=`object`||!r)return``;var d=o[i.arrayFormat],f=d===`comma`&&i.commaRoundTrip;a||=Object.keys(r),i.sort&&a.sort(i.sort);for(var p=n(),m=0;m<a.length;++m){var _=a[m],v=r[_];i.skipNulls&&v===null||l(u,h(v,_,d,f,i.allowEmptyArrays,i.strictNullHandling,i.skipNulls,i.encodeDotInKeys,i.encode?i.encoder:null,i.filter,i.sort,i.allowDots,i.serializeDate,i.format,i.formatter,i.encodeValuesOnly,i.charset,p))}var y=u.join(i.delimiter),b=i.addQueryPrefix===!0?`?`:``;return i.charsetSentinel&&(i.charset===`iso-8859-1`?b+=`utf8=%26%2310003%3B&`:b+=`utf8=%E2%9C%93&`),y.length>0?b+y:``}})),Ov=n(((e,t)=>{var n=Ev(),r=Object.prototype.hasOwnProperty,i=Array.isArray,a={allowDots:!1,allowEmptyArrays:!1,allowPrototypes:!1,allowSparse:!1,arrayLimit:20,charset:`utf-8`,charsetSentinel:!1,comma:!1,decodeDotInKeys:!1,decoder:n.decode,delimiter:`&`,depth:5,duplicates:`combine`,ignoreQueryPrefix:!1,interpretNumericEntities:!1,parameterLimit:1e3,parseArrays:!0,plainObjects:!1,strictDepth:!1,strictNullHandling:!1,throwOnLimitExceeded:!1},o=function(e){return e.replace(/&#(\d+);/g,function(e,t){return String.fromCharCode(parseInt(t,10))})},s=function(e,t,n){if(e&&typeof e==`string`&&t.comma&&e.indexOf(`,`)>-1)return e.split(`,`);if(t.throwOnLimitExceeded&&n>=t.arrayLimit)throw RangeError(`Array limit exceeded. Only `+t.arrayLimit+` element`+(t.arrayLimit===1?``:`s`)+` allowed in an array.`);return e},c=`utf8=%26%2310003%3B`,l=`utf8=%E2%9C%93`,u=function(e,t){var u={__proto__:null},d=t.ignoreQueryPrefix?e.replace(/^\?/,``):e;d=d.replace(/%5B/gi,`[`).replace(/%5D/gi,`]`);var f=t.parameterLimit===1/0?void 0:t.parameterLimit,p=d.split(t.delimiter,t.throwOnLimitExceeded?f+1:f);if(t.throwOnLimitExceeded&&p.length>f)throw RangeError(`Parameter limit exceeded. Only `+f+` parameter`+(f===1?``:`s`)+` allowed.`);var m=-1,h,g=t.charset;if(t.charsetSentinel)for(h=0;h<p.length;++h)p[h].indexOf(`utf8=`)===0&&(p[h]===l?g=`utf-8`:p[h]===c&&(g=`iso-8859-1`),m=h,h=p.length);for(h=0;h<p.length;++h)if(h!==m){var _=p[h],v=_.indexOf(`]=`),y=v===-1?_.indexOf(`=`):v+1,b,x;y===-1?(b=t.decoder(_,a.decoder,g,`key`),x=t.strictNullHandling?null:``):(b=t.decoder(_.slice(0,y),a.decoder,g,`key`),x=n.maybeMap(s(_.slice(y+1),t,i(u[b])?u[b].length:0),function(e){return t.decoder(e,a.decoder,g,`value`)})),x&&t.interpretNumericEntities&&g===`iso-8859-1`&&(x=o(String(x))),_.indexOf(`[]=`)>-1&&(x=i(x)?[x]:x);var S=r.call(u,b);S&&t.duplicates===`combine`?u[b]=n.combine(u[b],x):(!S||t.duplicates===`last`)&&(u[b]=x)}return u},d=function(e,t,r,i){var a=0;if(e.length>0&&e[e.length-1]===`[]`){var o=e.slice(0,-1).join(``);a=Array.isArray(t)&&t[o]?t[o].length:0}for(var c=i?t:s(t,r,a),l=e.length-1;l>=0;--l){var u,d=e[l];if(d===`[]`&&r.parseArrays)u=r.allowEmptyArrays&&(c===``||r.strictNullHandling&&c===null)?[]:n.combine([],c);else{u=r.plainObjects?{__proto__:null}:{};var f=d.charAt(0)===`[`&&d.charAt(d.length-1)===`]`?d.slice(1,-1):d,p=r.decodeDotInKeys?f.replace(/%2E/g,`.`):f,m=parseInt(p,10);!r.parseArrays&&p===``?u={0:c}:!isNaN(m)&&d!==p&&String(m)===p&&m>=0&&r.parseArrays&&m<=r.arrayLimit?(u=[],u[m]=c):p!==`__proto__`&&(u[p]=c)}c=u}return c},f=function(e,t,n,i){if(e){var a=n.allowDots?e.replace(/\.([^.[]+)/g,`[$1]`):e,o=/(\[[^[\]]*])/,s=/(\[[^[\]]*])/g,c=n.depth>0&&o.exec(a),l=c?a.slice(0,c.index):a,u=[];if(l){if(!n.plainObjects&&r.call(Object.prototype,l)&&!n.allowPrototypes)return;u.push(l)}for(var f=0;n.depth>0&&(c=s.exec(a))!==null&&f<n.depth;){if(f+=1,!n.plainObjects&&r.call(Object.prototype,c[1].slice(1,-1))&&!n.allowPrototypes)return;u.push(c[1])}if(c){if(n.strictDepth===!0)throw RangeError(`Input depth exceeded depth option of `+n.depth+` and strictDepth is true`);u.push(`[`+a.slice(c.index)+`]`)}return d(u,t,n,i)}},p=function(e){if(!e)return a;if(e.allowEmptyArrays!==void 0&&typeof e.allowEmptyArrays!=`boolean`)throw TypeError("`allowEmptyArrays` option can only be `true` or `false`, when provided");if(e.decodeDotInKeys!==void 0&&typeof e.decodeDotInKeys!=`boolean`)throw TypeError("`decodeDotInKeys` option can only be `true` or `false`, when provided");if(e.decoder!==null&&e.decoder!==void 0&&typeof e.decoder!=`function`)throw TypeError(`Decoder has to be a function.`);if(e.charset!==void 0&&e.charset!==`utf-8`&&e.charset!==`iso-8859-1`)throw TypeError(`The charset option must be either utf-8, iso-8859-1, or undefined`);if(e.throwOnLimitExceeded!==void 0&&typeof e.throwOnLimitExceeded!=`boolean`)throw TypeError("`throwOnLimitExceeded` option must be a boolean");var t=e.charset===void 0?a.charset:e.charset,r=e.duplicates===void 0?a.duplicates:e.duplicates;if(r!==`combine`&&r!==`first`&&r!==`last`)throw TypeError(`The duplicates option must be either combine, first, or last`);return{allowDots:e.allowDots===void 0?e.decodeDotInKeys===!0?!0:a.allowDots:!!e.allowDots,allowEmptyArrays:typeof e.allowEmptyArrays==`boolean`?!!e.allowEmptyArrays:a.allowEmptyArrays,allowPrototypes:typeof e.allowPrototypes==`boolean`?e.allowPrototypes:a.allowPrototypes,allowSparse:typeof e.allowSparse==`boolean`?e.allowSparse:a.allowSparse,arrayLimit:typeof e.arrayLimit==`number`?e.arrayLimit:a.arrayLimit,charset:t,charsetSentinel:typeof e.charsetSentinel==`boolean`?e.charsetSentinel:a.charsetSentinel,comma:typeof e.comma==`boolean`?e.comma:a.comma,decodeDotInKeys:typeof e.decodeDotInKeys==`boolean`?e.decodeDotInKeys:a.decodeDotInKeys,decoder:typeof e.decoder==`function`?e.decoder:a.decoder,delimiter:typeof e.delimiter==`string`||n.isRegExp(e.delimiter)?e.delimiter:a.delimiter,depth:typeof e.depth==`number`||e.depth===!1?+e.depth:a.depth,duplicates:r,ignoreQueryPrefix:e.ignoreQueryPrefix===!0,interpretNumericEntities:typeof e.interpretNumericEntities==`boolean`?e.interpretNumericEntities:a.interpretNumericEntities,parameterLimit:typeof e.parameterLimit==`number`?e.parameterLimit:a.parameterLimit,parseArrays:e.parseArrays!==!1,plainObjects:typeof e.plainObjects==`boolean`?e.plainObjects:a.plainObjects,strictDepth:typeof e.strictDepth==`boolean`?!!e.strictDepth:a.strictDepth,strictNullHandling:typeof e.strictNullHandling==`boolean`?e.strictNullHandling:a.strictNullHandling,throwOnLimitExceeded:typeof e.throwOnLimitExceeded==`boolean`?e.throwOnLimitExceeded:!1}};t.exports=function(e,t){var r=p(t);if(e===``||e==null)return r.plainObjects?{__proto__:null}:{};for(var i=typeof e==`string`?u(e,r):e,a=r.plainObjects?{__proto__:null}:{},o=Object.keys(i),s=0;s<o.length;++s){var c=o[s],l=f(c,i[c],r,typeof e==`string`);a=n.merge(a,l,r)}return r.allowSparse===!0?a:n.compact(a)}})),kv=n(((e,t)=>{var n=Dv(),r=Ov();t.exports={formats:Tv(),parse:r,stringify:n}}));function Av(e,t){return function(){return e.apply(t,arguments)}}var{toString:jv}=Object.prototype,{getPrototypeOf:Mv}=Object,{iterator:Nv,toStringTag:Pv}=Symbol,Fv=(e=>t=>{let n=jv.call(t);return e[n]||(e[n]=n.slice(8,-1).toLowerCase())})(Object.create(null)),Iv=e=>(e=e.toLowerCase(),t=>Fv(t)===e),Lv=e=>t=>typeof t===e,{isArray:Rv}=Array,zv=Lv(`undefined`);function Bv(e){return e!==null&&!zv(e)&&e.constructor!==null&&!zv(e.constructor)&&Wv(e.constructor.isBuffer)&&e.constructor.isBuffer(e)}var Vv=Iv(`ArrayBuffer`);function Hv(e){let t;return t=typeof ArrayBuffer<`u`&&ArrayBuffer.isView?ArrayBuffer.isView(e):e&&e.buffer&&Vv(e.buffer),t}var Uv=Lv(`string`),Wv=Lv(`function`),Gv=Lv(`number`),Kv=e=>typeof e==`object`&&!!e,qv=e=>e===!0||e===!1,Jv=e=>{if(Fv(e)!==`object`)return!1;let t=Mv(e);return(t===null||t===Object.prototype||Object.getPrototypeOf(t)===null)&&!(Pv in e)&&!(Nv in e)},Yv=e=>{if(!Kv(e)||Bv(e))return!1;try{return Object.keys(e).length===0&&Object.getPrototypeOf(e)===Object.prototype}catch{return!1}},Xv=Iv(`Date`),Zv=Iv(`File`),Qv=Iv(`Blob`),$v=Iv(`FileList`),ey=e=>Kv(e)&&Wv(e.pipe),ty=e=>{let t;return e&&(typeof FormData==`function`&&e instanceof FormData||Wv(e.append)&&((t=Fv(e))===`formdata`||t===`object`&&Wv(e.toString)&&e.toString()===`[object FormData]`))},ny=Iv(`URLSearchParams`),[ry,iy,ay,oy]=[`ReadableStream`,`Request`,`Response`,`Headers`].map(Iv),sy=e=>e.trim?e.trim():e.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g,``);function cy(e,t,{allOwnKeys:n=!1}={}){if(e==null)return;let r,i;if(typeof e!=`object`&&(e=[e]),Rv(e))for(r=0,i=e.length;r<i;r++)t.call(null,e[r],r,e);else{if(Bv(e))return;let i=n?Object.getOwnPropertyNames(e):Object.keys(e),a=i.length,o;for(r=0;r<a;r++)o=i[r],t.call(null,e[o],o,e)}}function ly(e,t){if(Bv(e))return null;t=t.toLowerCase();let n=Object.keys(e),r=n.length,i;for(;r-- >0;)if(i=n[r],t===i.toLowerCase())return i;return null}var uy=typeof globalThis<`u`?globalThis:typeof self<`u`?self:typeof window<`u`?window:global,dy=e=>!zv(e)&&e!==uy;function fy(){let{caseless:e,skipUndefined:t}=dy(this)&&this||{},n={},r=(r,i)=>{let a=e&&ly(n,i)||i;Jv(n[a])&&Jv(r)?n[a]=fy(n[a],r):Jv(r)?n[a]=fy({},r):Rv(r)?n[a]=r.slice():(!t||!zv(r))&&(n[a]=r)};for(let e=0,t=arguments.length;e<t;e++)arguments[e]&&cy(arguments[e],r);return n}var py=(e,t,n,{allOwnKeys:r}={})=>(cy(t,(t,r)=>{n&&Wv(t)?e[r]=Av(t,n):e[r]=t},{allOwnKeys:r}),e),my=e=>(e.charCodeAt(0)===65279&&(e=e.slice(1)),e),hy=(e,t,n,r)=>{e.prototype=Object.create(t.prototype,r),e.prototype.constructor=e,Object.defineProperty(e,`super`,{value:t.prototype}),n&&Object.assign(e.prototype,n)},gy=(e,t,n,r)=>{let i,a,o,s={};if(t||={},e==null)return t;do{for(i=Object.getOwnPropertyNames(e),a=i.length;a-- >0;)o=i[a],(!r||r(o,e,t))&&!s[o]&&(t[o]=e[o],s[o]=!0);e=n!==!1&&Mv(e)}while(e&&(!n||n(e,t))&&e!==Object.prototype);return t},_y=(e,t,n)=>{e=String(e),(n===void 0||n>e.length)&&(n=e.length),n-=t.length;let r=e.indexOf(t,n);return r!==-1&&r===n},vy=e=>{if(!e)return null;if(Rv(e))return e;let t=e.length;if(!Gv(t))return null;let n=Array(t);for(;t-- >0;)n[t]=e[t];return n},yy=(e=>t=>e&&t instanceof e)(typeof Uint8Array<`u`&&Mv(Uint8Array)),by=(e,t)=>{let n=(e&&e[Nv]).call(e),r;for(;(r=n.next())&&!r.done;){let n=r.value;t.call(e,n[0],n[1])}},xy=(e,t)=>{let n,r=[];for(;(n=e.exec(t))!==null;)r.push(n);return r},Sy=Iv(`HTMLFormElement`),Cy=e=>e.toLowerCase().replace(/[-_\s]([a-z\d])(\w*)/g,function(e,t,n){return t.toUpperCase()+n}),wy=(({hasOwnProperty:e})=>(t,n)=>e.call(t,n))(Object.prototype),Ty=Iv(`RegExp`),Ey=(e,t)=>{let n=Object.getOwnPropertyDescriptors(e),r={};cy(n,(n,i)=>{let a;(a=t(n,i,e))!==!1&&(r[i]=a||n)}),Object.defineProperties(e,r)},Dy=e=>{Ey(e,(t,n)=>{if(Wv(e)&&[`arguments`,`caller`,`callee`].indexOf(n)!==-1)return!1;let r=e[n];if(Wv(r)){if(t.enumerable=!1,`writable`in t){t.writable=!1;return}t.set||=()=>{throw Error(`Can not rewrite read-only method '`+n+`'`)}}})},Oy=(e,t)=>{let n={},r=e=>{e.forEach(e=>{n[e]=!0})};return Rv(e)?r(e):r(String(e).split(t)),n},ky=()=>{},Ay=(e,t)=>e!=null&&Number.isFinite(e=+e)?e:t;function jy(e){return!!(e&&Wv(e.append)&&e[Pv]===`FormData`&&e[Nv])}var My=e=>{let t=Array(10),n=(e,r)=>{if(Kv(e)){if(t.indexOf(e)>=0)return;if(Bv(e))return e;if(!(`toJSON`in e)){t[r]=e;let i=Rv(e)?[]:{};return cy(e,(e,t)=>{let a=n(e,r+1);!zv(a)&&(i[t]=a)}),t[r]=void 0,i}}return e};return n(e,0)},Ny=Iv(`AsyncFunction`),Py=e=>e&&(Kv(e)||Wv(e))&&Wv(e.then)&&Wv(e.catch),Fy=((e,t)=>e?setImmediate:t?((e,t)=>(uy.addEventListener(`message`,({source:n,data:r})=>{n===uy&&r===e&&t.length&&t.shift()()},!1),n=>{t.push(n),uy.postMessage(e,`*`)}))(`axios@${Math.random()}`,[]):e=>setTimeout(e))(typeof setImmediate==`function`,Wv(uy.postMessage)),B={isArray:Rv,isArrayBuffer:Vv,isBuffer:Bv,isFormData:ty,isArrayBufferView:Hv,isString:Uv,isNumber:Gv,isBoolean:qv,isObject:Kv,isPlainObject:Jv,isEmptyObject:Yv,isReadableStream:ry,isRequest:iy,isResponse:ay,isHeaders:oy,isUndefined:zv,isDate:Xv,isFile:Zv,isBlob:Qv,isRegExp:Ty,isFunction:Wv,isStream:ey,isURLSearchParams:ny,isTypedArray:yy,isFileList:$v,forEach:cy,merge:fy,extend:py,trim:sy,stripBOM:my,inherits:hy,toFlatObject:gy,kindOf:Fv,kindOfTest:Iv,endsWith:_y,toArray:vy,forEachEntry:by,matchAll:xy,isHTMLForm:Sy,hasOwnProperty:wy,hasOwnProp:wy,reduceDescriptors:Ey,freezeMethods:Dy,toObjectSet:Oy,toCamelCase:Cy,noop:ky,toFiniteNumber:Ay,findKey:ly,global:uy,isContextDefined:dy,isSpecCompliantForm:jy,toJSONObject:My,isAsyncFn:Ny,isThenable:Py,setImmediate:Fy,asap:typeof queueMicrotask<`u`?queueMicrotask.bind(uy):typeof process<`u`&&process.nextTick||Fy,isIterable:e=>e!=null&&Wv(e[Nv])};function V(e,t,n,r,i){Error.call(this),Error.captureStackTrace?Error.captureStackTrace(this,this.constructor):this.stack=Error().stack,this.message=e,this.name=`AxiosError`,t&&(this.code=t),n&&(this.config=n),r&&(this.request=r),i&&(this.response=i,this.status=i.status?i.status:null)}B.inherits(V,Error,{toJSON:function(){return{message:this.message,name:this.name,description:this.description,number:this.number,fileName:this.fileName,lineNumber:this.lineNumber,columnNumber:this.columnNumber,stack:this.stack,config:B.toJSONObject(this.config),code:this.code,status:this.status}}});var Iy=V.prototype,Ly={};[`ERR_BAD_OPTION_VALUE`,`ERR_BAD_OPTION`,`ECONNABORTED`,`ETIMEDOUT`,`ERR_NETWORK`,`ERR_FR_TOO_MANY_REDIRECTS`,`ERR_DEPRECATED`,`ERR_BAD_RESPONSE`,`ERR_BAD_REQUEST`,`ERR_CANCELED`,`ERR_NOT_SUPPORT`,`ERR_INVALID_URL`].forEach(e=>{Ly[e]={value:e}}),Object.defineProperties(V,Ly),Object.defineProperty(Iy,`isAxiosError`,{value:!0}),V.from=(e,t,n,r,i,a)=>{let o=Object.create(Iy);B.toFlatObject(e,o,function(e){return e!==Error.prototype},e=>e!==`isAxiosError`);let s=e&&e.message?e.message:`Error`,c=t==null&&e?e.code:t;return V.call(o,s,c,n,r,i),e&&o.cause==null&&Object.defineProperty(o,`cause`,{value:e,configurable:!0}),o.name=e&&e.name||`Error`,a&&Object.assign(o,a),o};function Ry(e){return B.isPlainObject(e)||B.isArray(e)}function zy(e){return B.endsWith(e,`[]`)?e.slice(0,-2):e}function By(e,t,n){return e?e.concat(t).map(function(e,t){return e=zy(e),!n&&t?`[`+e+`]`:e}).join(n?`.`:``):t}function Vy(e){return B.isArray(e)&&!e.some(Ry)}var Hy=B.toFlatObject(B,{},null,function(e){return/^is[A-Z]/.test(e)});function Uy(e,t,n){if(!B.isObject(e))throw TypeError(`target must be an object`);t||=new FormData,n=B.toFlatObject(n,{metaTokens:!0,dots:!1,indexes:!1},!1,function(e,t){return!B.isUndefined(t[e])});let r=n.metaTokens,i=n.visitor||l,a=n.dots,o=n.indexes,s=(n.Blob||typeof Blob<`u`&&Blob)&&B.isSpecCompliantForm(t);if(!B.isFunction(i))throw TypeError(`visitor must be a function`);function c(e){if(e===null)return``;if(B.isDate(e))return e.toISOString();if(B.isBoolean(e))return e.toString();if(!s&&B.isBlob(e))throw new V(`Blob is not supported. Use a Buffer instead.`);return B.isArrayBuffer(e)||B.isTypedArray(e)?s&&typeof Blob==`function`?new Blob([e]):Buffer.from(e):e}function l(e,n,i){let s=e;if(e&&!i&&typeof e==`object`){if(B.endsWith(n,`{}`))n=r?n:n.slice(0,-2),e=JSON.stringify(e);else if(B.isArray(e)&&Vy(e)||(B.isFileList(e)||B.endsWith(n,`[]`))&&(s=B.toArray(e)))return n=zy(n),s.forEach(function(e,r){!(B.isUndefined(e)||e===null)&&t.append(o===!0?By([n],r,a):o===null?n:n+`[]`,c(e))}),!1}return Ry(e)?!0:(t.append(By(i,n,a),c(e)),!1)}let u=[],d=Object.assign(Hy,{defaultVisitor:l,convertValue:c,isVisitable:Ry});function f(e,n){if(!B.isUndefined(e)){if(u.indexOf(e)!==-1)throw Error(`Circular reference detected in `+n.join(`.`));u.push(e),B.forEach(e,function(e,r){(!(B.isUndefined(e)||e===null)&&i.call(t,e,B.isString(r)?r.trim():r,n,d))===!0&&f(e,n?n.concat(r):[r])}),u.pop()}}if(!B.isObject(e))throw TypeError(`data must be an object`);return f(e),t}function Wy(e){let t={"!":`%21`,"'":`%27`,"(":`%28`,")":`%29`,"~":`%7E`,"%20":`+`,"%00":`\0`};return encodeURIComponent(e).replace(/[!'()~]|%20|%00/g,function(e){return t[e]})}function Gy(e,t){this._pairs=[],e&&Uy(e,this,t)}var Ky=Gy.prototype;Ky.append=function(e,t){this._pairs.push([e,t])},Ky.toString=function(e){let t=e?function(t){return e.call(this,t,Wy)}:Wy;return this._pairs.map(function(e){return t(e[0])+`=`+t(e[1])},``).join(`&`)};function qy(e){return encodeURIComponent(e).replace(/%3A/gi,`:`).replace(/%24/g,`$`).replace(/%2C/gi,`,`).replace(/%20/g,`+`)}function Jy(e,t,n){if(!t)return e;let r=n&&n.encode||qy;B.isFunction(n)&&(n={serialize:n});let i=n&&n.serialize,a;if(a=i?i(t,n):B.isURLSearchParams(t)?t.toString():new Gy(t,n).toString(r),a){let t=e.indexOf(`#`);t!==-1&&(e=e.slice(0,t)),e+=(e.indexOf(`?`)===-1?`?`:`&`)+a}return e}var Yy=class{constructor(){this.handlers=[]}use(e,t,n){return this.handlers.push({fulfilled:e,rejected:t,synchronous:n?n.synchronous:!1,runWhen:n?n.runWhen:null}),this.handlers.length-1}eject(e){this.handlers[e]&&(this.handlers[e]=null)}clear(){this.handlers&&=[]}forEach(e){B.forEach(this.handlers,function(t){t!==null&&e(t)})}},Xy={silentJSONParsing:!0,forcedJSONParsing:!0,clarifyTimeoutError:!1},Zy={isBrowser:!0,classes:{URLSearchParams:typeof URLSearchParams<`u`?URLSearchParams:Gy,FormData:typeof FormData<`u`?FormData:null,Blob:typeof Blob<`u`?Blob:null},protocols:[`http`,`https`,`file`,`blob`,`url`,`data`]},Qy=r({hasBrowserEnv:()=>$y,hasStandardBrowserEnv:()=>tb,hasStandardBrowserWebWorkerEnv:()=>nb,navigator:()=>eb,origin:()=>rb}),$y=typeof window<`u`&&typeof document<`u`,eb=typeof navigator==`object`&&navigator||void 0,tb=$y&&(!eb||[`ReactNative`,`NativeScript`,`NS`].indexOf(eb.product)<0),nb=typeof WorkerGlobalScope<`u`&&self instanceof WorkerGlobalScope&&typeof self.importScripts==`function`,rb=$y&&window.location.href||`http://localhost`,ib={...Qy,...Zy};function ab(e,t){return Uy(e,new ib.classes.URLSearchParams,{visitor:function(e,t,n,r){return ib.isNode&&B.isBuffer(e)?(this.append(t,e.toString(`base64`)),!1):r.defaultVisitor.apply(this,arguments)},...t})}function ob(e){return B.matchAll(/\w+|\[(\w*)]/g,e).map(e=>e[0]===`[]`?``:e[1]||e[0])}function sb(e){let t={},n=Object.keys(e),r,i=n.length,a;for(r=0;r<i;r++)a=n[r],t[a]=e[a];return t}function cb(e){function t(e,n,r,i){let a=e[i++];if(a===`__proto__`)return!0;let o=Number.isFinite(+a),s=i>=e.length;return a=!a&&B.isArray(r)?r.length:a,s?(B.hasOwnProp(r,a)?r[a]=[r[a],n]:r[a]=n,!o):((!r[a]||!B.isObject(r[a]))&&(r[a]=[]),t(e,n,r[a],i)&&B.isArray(r[a])&&(r[a]=sb(r[a])),!o)}if(B.isFormData(e)&&B.isFunction(e.entries)){let n={};return B.forEachEntry(e,(e,r)=>{t(ob(e),r,n,0)}),n}return null}function lb(e,t,n){if(B.isString(e))try{return(t||JSON.parse)(e),B.trim(e)}catch(e){if(e.name!==`SyntaxError`)throw e}return(n||JSON.stringify)(e)}var ub={transitional:Xy,adapter:[`xhr`,`http`,`fetch`],transformRequest:[function(e,t){let n=t.getContentType()||``,r=n.indexOf(`application/json`)>-1,i=B.isObject(e);if(i&&B.isHTMLForm(e)&&(e=new FormData(e)),B.isFormData(e))return r?JSON.stringify(cb(e)):e;if(B.isArrayBuffer(e)||B.isBuffer(e)||B.isStream(e)||B.isFile(e)||B.isBlob(e)||B.isReadableStream(e))return e;if(B.isArrayBufferView(e))return e.buffer;if(B.isURLSearchParams(e))return t.setContentType(`application/x-www-form-urlencoded;charset=utf-8`,!1),e.toString();let a;if(i){if(n.indexOf(`application/x-www-form-urlencoded`)>-1)return ab(e,this.formSerializer).toString();if((a=B.isFileList(e))||n.indexOf(`multipart/form-data`)>-1){let t=this.env&&this.env.FormData;return Uy(a?{"files[]":e}:e,t&&new t,this.formSerializer)}}return i||r?(t.setContentType(`application/json`,!1),lb(e)):e}],transformResponse:[function(e){let t=this.transitional||ub.transitional,n=t&&t.forcedJSONParsing,r=this.responseType===`json`;if(B.isResponse(e)||B.isReadableStream(e))return e;if(e&&B.isString(e)&&(n&&!this.responseType||r)){let n=!(t&&t.silentJSONParsing)&&r;try{return JSON.parse(e,this.parseReviver)}catch(e){if(n)throw e.name===`SyntaxError`?V.from(e,V.ERR_BAD_RESPONSE,this,null,this.response):e}}return e}],timeout:0,xsrfCookieName:`XSRF-TOKEN`,xsrfHeaderName:`X-XSRF-TOKEN`,maxContentLength:-1,maxBodyLength:-1,env:{FormData:ib.classes.FormData,Blob:ib.classes.Blob},validateStatus:function(e){return e>=200&&e<300},headers:{common:{Accept:`application/json, text/plain, */*`,"Content-Type":void 0}}};B.forEach([`delete`,`get`,`head`,`post`,`put`,`patch`],e=>{ub.headers[e]={}});var db=B.toObjectSet([`age`,`authorization`,`content-length`,`content-type`,`etag`,`expires`,`from`,`host`,`if-modified-since`,`if-unmodified-since`,`last-modified`,`location`,`max-forwards`,`proxy-authorization`,`referer`,`retry-after`,`user-agent`]),fb=e=>{let t={},n,r,i;return e&&e.split(`
`).forEach(function(e){i=e.indexOf(`:`),n=e.substring(0,i).trim().toLowerCase(),r=e.substring(i+1).trim(),!(!n||t[n]&&db[n])&&(n===`set-cookie`?t[n]?t[n].push(r):t[n]=[r]:t[n]=t[n]?t[n]+`, `+r:r)}),t},pb=Symbol(`internals`);function mb(e){return e&&String(e).trim().toLowerCase()}function hb(e){return e===!1||e==null?e:B.isArray(e)?e.map(hb):String(e)}function gb(e){let t=Object.create(null),n=/([^\s,;=]+)\s*(?:=\s*([^,;]+))?/g,r;for(;r=n.exec(e);)t[r[1]]=r[2];return t}var _b=e=>/^[-_a-zA-Z0-9^`|~,!#$%&'*+.]+$/.test(e.trim());function vb(e,t,n,r,i){if(B.isFunction(r))return r.call(this,t,n);if(i&&(t=n),B.isString(t)){if(B.isString(r))return t.indexOf(r)!==-1;if(B.isRegExp(r))return r.test(t)}}function yb(e){return e.trim().toLowerCase().replace(/([a-z\d])(\w*)/g,(e,t,n)=>t.toUpperCase()+n)}function bb(e,t){let n=B.toCamelCase(` `+t);[`get`,`set`,`has`].forEach(r=>{Object.defineProperty(e,r+n,{value:function(e,n,i){return this[r].call(this,t,e,n,i)},configurable:!0})})}var xb=class{constructor(e){e&&this.set(e)}set(e,t,n){let r=this;function i(e,t,n){let i=mb(t);if(!i)throw Error(`header name must be a non-empty string`);let a=B.findKey(r,i);(!a||r[a]===void 0||n===!0||n===void 0&&r[a]!==!1)&&(r[a||t]=hb(e))}let a=(e,t)=>B.forEach(e,(e,n)=>i(e,n,t));if(B.isPlainObject(e)||e instanceof this.constructor)a(e,t);else if(B.isString(e)&&(e=e.trim())&&!_b(e))a(fb(e),t);else if(B.isObject(e)&&B.isIterable(e)){let n={},r,i;for(let t of e){if(!B.isArray(t))throw TypeError(`Object iterator must return a key-value pair`);n[i=t[0]]=(r=n[i])?B.isArray(r)?[...r,t[1]]:[r,t[1]]:t[1]}a(n,t)}else e!=null&&i(t,e,n);return this}get(e,t){if(e=mb(e),e){let n=B.findKey(this,e);if(n){let e=this[n];if(!t)return e;if(t===!0)return gb(e);if(B.isFunction(t))return t.call(this,e,n);if(B.isRegExp(t))return t.exec(e);throw TypeError(`parser must be boolean|regexp|function`)}}}has(e,t){if(e=mb(e),e){let n=B.findKey(this,e);return!!(n&&this[n]!==void 0&&(!t||vb(this,this[n],n,t)))}return!1}delete(e,t){let n=this,r=!1;function i(e){if(e=mb(e),e){let i=B.findKey(n,e);i&&(!t||vb(n,n[i],i,t))&&(delete n[i],r=!0)}}return B.isArray(e)?e.forEach(i):i(e),r}clear(e){let t=Object.keys(this),n=t.length,r=!1;for(;n--;){let i=t[n];(!e||vb(this,this[i],i,e,!0))&&(delete this[i],r=!0)}return r}normalize(e){let t=this,n={};return B.forEach(this,(r,i)=>{let a=B.findKey(n,i);if(a){t[a]=hb(r),delete t[i];return}let o=e?yb(i):String(i).trim();o!==i&&delete t[i],t[o]=hb(r),n[o]=!0}),this}concat(...e){return this.constructor.concat(this,...e)}toJSON(e){let t=Object.create(null);return B.forEach(this,(n,r)=>{n!=null&&n!==!1&&(t[r]=e&&B.isArray(n)?n.join(`, `):n)}),t}[Symbol.iterator](){return Object.entries(this.toJSON())[Symbol.iterator]()}toString(){return Object.entries(this.toJSON()).map(([e,t])=>e+`: `+t).join(`
`)}getSetCookie(){return this.get(`set-cookie`)||[]}get[Symbol.toStringTag](){return`AxiosHeaders`}static from(e){return e instanceof this?e:new this(e)}static concat(e,...t){let n=new this(e);return t.forEach(e=>n.set(e)),n}static accessor(e){let t=(this[pb]=this[pb]={accessors:{}}).accessors,n=this.prototype;function r(e){let r=mb(e);t[r]||(bb(n,e),t[r]=!0)}return B.isArray(e)?e.forEach(r):r(e),this}};xb.accessor([`Content-Type`,`Content-Length`,`Accept`,`Accept-Encoding`,`User-Agent`,`Authorization`]),B.reduceDescriptors(xb.prototype,({value:e},t)=>{let n=t[0].toUpperCase()+t.slice(1);return{get:()=>e,set(e){this[n]=e}}}),B.freezeMethods(xb);function Sb(e,t){let n=this||ub,r=t||n,i=xb.from(r.headers),a=r.data;return B.forEach(e,function(e){a=e.call(n,a,i.normalize(),t?t.status:void 0)}),i.normalize(),a}function Cb(e){return!!(e&&e.__CANCEL__)}function wb(e,t,n){V.call(this,e??`canceled`,V.ERR_CANCELED,t,n),this.name=`CanceledError`}B.inherits(wb,V,{__CANCEL__:!0});function Tb(e,t,n){let r=n.config.validateStatus;!n.status||!r||r(n.status)?e(n):t(new V(`Request failed with status code `+n.status,[V.ERR_BAD_REQUEST,V.ERR_BAD_RESPONSE][Math.floor(n.status/100)-4],n.config,n.request,n))}function Eb(e){let t=/^([-+\w]{1,25})(:?\/\/|:)/.exec(e);return t&&t[1]||``}function Db(e,t){e||=10;let n=Array(e),r=Array(e),i=0,a=0,o;return t=t===void 0?1e3:t,function(s){let c=Date.now(),l=r[a];o||=c,n[i]=s,r[i]=c;let u=a,d=0;for(;u!==i;)d+=n[u++],u%=e;if(i=(i+1)%e,i===a&&(a=(a+1)%e),c-o<t)return;let f=l&&c-l;return f?Math.round(d*1e3/f):void 0}}function Ob(e,t){let n=0,r=1e3/t,i,a,o=(t,r=Date.now())=>{n=r,i=null,a&&=(clearTimeout(a),null),e(...t)};return[(...e)=>{let t=Date.now(),s=t-n;s>=r?o(e,t):(i=e,a||=setTimeout(()=>{a=null,o(i)},r-s))},()=>i&&o(i)]}var kb=(e,t,n=3)=>{let r=0,i=Db(50,250);return Ob(n=>{let a=n.loaded,o=n.lengthComputable?n.total:void 0,s=a-r,c=i(s),l=a<=o;r=a,e({loaded:a,total:o,progress:o?a/o:void 0,bytes:s,rate:c||void 0,estimated:c&&o&&l?(o-a)/c:void 0,event:n,lengthComputable:o!=null,[t?`download`:`upload`]:!0})},n)},Ab=(e,t)=>{let n=e!=null;return[r=>t[0]({lengthComputable:n,total:e,loaded:r}),t[1]]},jb=e=>(...t)=>B.asap(()=>e(...t)),Mb=ib.hasStandardBrowserEnv?((e,t)=>n=>(n=new URL(n,ib.origin),e.protocol===n.protocol&&e.host===n.host&&(t||e.port===n.port)))(new URL(ib.origin),ib.navigator&&/(msie|trident)/i.test(ib.navigator.userAgent)):()=>!0,Nb=ib.hasStandardBrowserEnv?{write(e,t,n,r,i,a){let o=[e+`=`+encodeURIComponent(t)];B.isNumber(n)&&o.push(`expires=`+new Date(n).toGMTString()),B.isString(r)&&o.push(`path=`+r),B.isString(i)&&o.push(`domain=`+i),a===!0&&o.push(`secure`),document.cookie=o.join(`; `)},read(e){let t=document.cookie.match(RegExp(`(^|;\\s*)(`+e+`)=([^;]*)`));return t?decodeURIComponent(t[3]):null},remove(e){this.write(e,``,Date.now()-864e5)}}:{write(){},read(){return null},remove(){}};function Pb(e){return/^([a-z][a-z\d+\-.]*:)?\/\//i.test(e)}function Fb(e,t){return t?e.replace(/\/?\/$/,``)+`/`+t.replace(/^\/+/,``):e}function Ib(e,t,n){let r=!Pb(t);return e&&(r||n==0)?Fb(e,t):t}var Lb=e=>e instanceof xb?{...e}:e;function Rb(e,t){t||={};let n={};function r(e,t,n,r){return B.isPlainObject(e)&&B.isPlainObject(t)?B.merge.call({caseless:r},e,t):B.isPlainObject(t)?B.merge({},t):B.isArray(t)?t.slice():t}function i(e,t,n,i){if(!B.isUndefined(t))return r(e,t,n,i);if(!B.isUndefined(e))return r(void 0,e,n,i)}function a(e,t){if(!B.isUndefined(t))return r(void 0,t)}function o(e,t){if(!B.isUndefined(t))return r(void 0,t);if(!B.isUndefined(e))return r(void 0,e)}function s(n,i,a){if(a in t)return r(n,i);if(a in e)return r(void 0,n)}let c={url:a,method:a,data:a,baseURL:o,transformRequest:o,transformResponse:o,paramsSerializer:o,timeout:o,timeoutMessage:o,withCredentials:o,withXSRFToken:o,adapter:o,responseType:o,xsrfCookieName:o,xsrfHeaderName:o,onUploadProgress:o,onDownloadProgress:o,decompress:o,maxContentLength:o,maxBodyLength:o,beforeRedirect:o,transport:o,httpAgent:o,httpsAgent:o,cancelToken:o,socketPath:o,responseEncoding:o,validateStatus:s,headers:(e,t,n)=>i(Lb(e),Lb(t),n,!0)};return B.forEach(Object.keys({...e,...t}),function(r){let a=c[r]||i,o=a(e[r],t[r],r);B.isUndefined(o)&&a!==s||(n[r]=o)}),n}var zb=e=>{let t=Rb({},e),{data:n,withXSRFToken:r,xsrfHeaderName:i,xsrfCookieName:a,headers:o,auth:s}=t;if(t.headers=o=xb.from(o),t.url=Jy(Ib(t.baseURL,t.url,t.allowAbsoluteUrls),e.params,e.paramsSerializer),s&&o.set(`Authorization`,`Basic `+btoa((s.username||``)+`:`+(s.password?unescape(encodeURIComponent(s.password)):``))),B.isFormData(n)){if(ib.hasStandardBrowserEnv||ib.hasStandardBrowserWebWorkerEnv)o.setContentType(void 0);else if(B.isFunction(n.getHeaders)){let e=n.getHeaders(),t=[`content-type`,`content-length`];Object.entries(e).forEach(([e,n])=>{t.includes(e.toLowerCase())&&o.set(e,n)})}}if(ib.hasStandardBrowserEnv&&(r&&B.isFunction(r)&&(r=r(t)),r||r!==!1&&Mb(t.url))){let e=i&&a&&Nb.read(a);e&&o.set(i,e)}return t},Bb=typeof XMLHttpRequest<`u`&&function(e){return new Promise(function(t,n){let r=zb(e),i=r.data,a=xb.from(r.headers).normalize(),{responseType:o,onUploadProgress:s,onDownloadProgress:c}=r,l,u,d,f,p;function m(){f&&f(),p&&p(),r.cancelToken&&r.cancelToken.unsubscribe(l),r.signal&&r.signal.removeEventListener(`abort`,l)}let h=new XMLHttpRequest;h.open(r.method.toUpperCase(),r.url,!0),h.timeout=r.timeout;function g(){if(!h)return;let r=xb.from(`getAllResponseHeaders`in h&&h.getAllResponseHeaders());Tb(function(e){t(e),m()},function(e){n(e),m()},{data:!o||o===`text`||o===`json`?h.responseText:h.response,status:h.status,statusText:h.statusText,headers:r,config:e,request:h}),h=null}`onloadend`in h?h.onloadend=g:h.onreadystatechange=function(){!h||h.readyState!==4||h.status===0&&!(h.responseURL&&h.responseURL.indexOf(`file:`)===0)||setTimeout(g)},h.onabort=function(){h&&=(n(new V(`Request aborted`,V.ECONNABORTED,e,h)),null)},h.onerror=function(t){let r=new V(t&&t.message?t.message:`Network Error`,V.ERR_NETWORK,e,h);r.event=t||null,n(r),h=null},h.ontimeout=function(){let t=r.timeout?`timeout of `+r.timeout+`ms exceeded`:`timeout exceeded`,i=r.transitional||Xy;r.timeoutErrorMessage&&(t=r.timeoutErrorMessage),n(new V(t,i.clarifyTimeoutError?V.ETIMEDOUT:V.ECONNABORTED,e,h)),h=null},i===void 0&&a.setContentType(null),`setRequestHeader`in h&&B.forEach(a.toJSON(),function(e,t){h.setRequestHeader(t,e)}),B.isUndefined(r.withCredentials)||(h.withCredentials=!!r.withCredentials),o&&o!==`json`&&(h.responseType=r.responseType),c&&([d,p]=kb(c,!0),h.addEventListener(`progress`,d)),s&&h.upload&&([u,f]=kb(s),h.upload.addEventListener(`progress`,u),h.upload.addEventListener(`loadend`,f)),(r.cancelToken||r.signal)&&(l=t=>{h&&=(n(!t||t.type?new wb(null,e,h):t),h.abort(),null)},r.cancelToken&&r.cancelToken.subscribe(l),r.signal&&(r.signal.aborted?l():r.signal.addEventListener(`abort`,l)));let _=Eb(r.url);if(_&&ib.protocols.indexOf(_)===-1){n(new V(`Unsupported protocol `+_+`:`,V.ERR_BAD_REQUEST,e));return}h.send(i||null)})},Vb=(e,t)=>{let{length:n}=e=e?e.filter(Boolean):[];if(t||n){let n=new AbortController,r,i=function(e){if(!r){r=!0,o();let t=e instanceof Error?e:this.reason;n.abort(t instanceof V?t:new wb(t instanceof Error?t.message:t))}},a=t&&setTimeout(()=>{a=null,i(new V(`timeout ${t} of ms exceeded`,V.ETIMEDOUT))},t),o=()=>{e&&=(a&&clearTimeout(a),a=null,e.forEach(e=>{e.unsubscribe?e.unsubscribe(i):e.removeEventListener(`abort`,i)}),null)};e.forEach(e=>e.addEventListener(`abort`,i));let{signal:s}=n;return s.unsubscribe=()=>B.asap(o),s}},Hb=function*(e,t){let n=e.byteLength;if(!t||n<t){yield e;return}let r=0,i;for(;r<n;)i=r+t,yield e.slice(r,i),r=i},Ub=async function*(e,t){for await(let n of Wb(e))yield*Hb(n,t)},Wb=async function*(e){if(e[Symbol.asyncIterator]){yield*e;return}let t=e.getReader();try{for(;;){let{done:e,value:n}=await t.read();if(e)break;yield n}}finally{await t.cancel()}},Gb=(e,t,n,r)=>{let i=Ub(e,t),a=0,o,s=e=>{o||(o=!0,r&&r(e))};return new ReadableStream({async pull(e){try{let{done:t,value:r}=await i.next();if(t){s(),e.close();return}let o=r.byteLength;n&&n(a+=o),e.enqueue(new Uint8Array(r))}catch(e){throw s(e),e}},cancel(e){return s(e),i.return()}},{highWaterMark:2})},Kb=64*1024,{isFunction:qb}=B,Jb=(({Request:e,Response:t})=>({Request:e,Response:t}))(B.global),{ReadableStream:Yb,TextEncoder:Xb}=B.global,Zb=(e,...t)=>{try{return!!e(...t)}catch{return!1}},Qb=e=>{e=B.merge.call({skipUndefined:!0},Jb,e);let{fetch:t,Request:n,Response:r}=e,i=t?qb(t):typeof fetch==`function`,a=qb(n),o=qb(r);if(!i)return!1;let s=i&&qb(Yb),c=i&&(typeof Xb==`function`?(e=>t=>e.encode(t))(new Xb):async e=>new Uint8Array(await new n(e).arrayBuffer())),l=a&&s&&Zb(()=>{let e=!1,t=new n(ib.origin,{body:new Yb,method:`POST`,get duplex(){return e=!0,`half`}}).headers.has(`Content-Type`);return e&&!t}),u=o&&s&&Zb(()=>B.isReadableStream(new r(``).body)),d={stream:u&&(e=>e.body)};i&&[`text`,`arrayBuffer`,`blob`,`formData`,`stream`].forEach(e=>{!d[e]&&(d[e]=(t,n)=>{let r=t&&t[e];if(r)return r.call(t);throw new V(`Response type '${e}' is not supported`,V.ERR_NOT_SUPPORT,n)})});let f=async e=>{if(e==null)return 0;if(B.isBlob(e))return e.size;if(B.isSpecCompliantForm(e))return(await new n(ib.origin,{method:`POST`,body:e}).arrayBuffer()).byteLength;if(B.isArrayBufferView(e)||B.isArrayBuffer(e))return e.byteLength;if(B.isURLSearchParams(e)&&(e+=``),B.isString(e))return(await c(e)).byteLength},p=async(e,t)=>B.toFiniteNumber(e.getContentLength())??f(t);return async e=>{let{url:i,method:o,data:s,signal:c,cancelToken:f,timeout:m,onDownloadProgress:h,onUploadProgress:g,responseType:_,headers:v,withCredentials:y=`same-origin`,fetchOptions:b}=zb(e),x=t||fetch;_=_?(_+``).toLowerCase():`text`;let S=Vb([c,f&&f.toAbortSignal()],m),C=null,w=S&&S.unsubscribe&&(()=>{S.unsubscribe()}),T;try{if(g&&l&&o!==`get`&&o!==`head`&&(T=await p(v,s))!==0){let e=new n(i,{method:`POST`,body:s,duplex:`half`}),t;if(B.isFormData(s)&&(t=e.headers.get(`content-type`))&&v.setContentType(t),e.body){let[t,n]=Ab(T,kb(jb(g)));s=Gb(e.body,Kb,t,n)}}B.isString(y)||(y=y?`include`:`omit`);let t=a&&`credentials`in n.prototype,c={...b,signal:S,method:o.toUpperCase(),headers:v.normalize().toJSON(),body:s,duplex:`half`,credentials:t?y:void 0};C=a&&new n(i,c);let f=await(a?x(C,b):x(i,c)),m=u&&(_===`stream`||_===`response`);if(u&&(h||m&&w)){let e={};[`status`,`statusText`,`headers`].forEach(t=>{e[t]=f[t]});let t=B.toFiniteNumber(f.headers.get(`content-length`)),[n,i]=h&&Ab(t,kb(jb(h),!0))||[];f=new r(Gb(f.body,Kb,n,()=>{i&&i(),w&&w()}),e)}_||=`text`;let E=await d[B.findKey(d,_)||`text`](f,e);return!m&&w&&w(),await new Promise((t,n)=>{Tb(t,n,{data:E,headers:xb.from(f.headers),status:f.status,statusText:f.statusText,config:e,request:C})})}catch(t){throw w&&w(),t&&t.name===`TypeError`&&/Load failed|fetch/i.test(t.message)?Object.assign(new V(`Network Error`,V.ERR_NETWORK,e,C),{cause:t.cause||t}):V.from(t,t&&t.code,e,C)}}},$b=new Map,ex=e=>{let t=e?e.env:{},{fetch:n,Request:r,Response:i}=t,a=[r,i,n],o=a.length,s,c,l=$b;for(;o--;)s=a[o],c=l.get(s),c===void 0&&l.set(s,c=o?new Map:Qb(t)),l=c;return c};ex();var tx={http:null,xhr:Bb,fetch:{get:ex}};B.forEach(tx,(e,t)=>{if(e){try{Object.defineProperty(e,`name`,{value:t})}catch{}Object.defineProperty(e,`adapterName`,{value:t})}});var nx=e=>`- ${e}`,rx=e=>B.isFunction(e)||e===null||e===!1,ix={getAdapter:(e,t)=>{e=B.isArray(e)?e:[e];let{length:n}=e,r,i,a={};for(let o=0;o<n;o++){r=e[o];let n;if(i=r,!rx(r)&&(i=tx[(n=String(r)).toLowerCase()],i===void 0))throw new V(`Unknown adapter '${n}'`);if(i&&(B.isFunction(i)||(i=i.get(t))))break;a[n||`#`+o]=i}if(!i){let e=Object.entries(a).map(([e,t])=>`adapter ${e} `+(t===!1?`is not supported by the environment`:`is not available in the build`));throw new V(`There is no suitable adapter to dispatch the request `+(n?e.length>1?`since :
`+e.map(nx).join(`
`):` `+nx(e[0]):`as no adapter specified`),`ERR_NOT_SUPPORT`)}return i},adapters:tx};function ax(e){if(e.cancelToken&&e.cancelToken.throwIfRequested(),e.signal&&e.signal.aborted)throw new wb(null,e)}function ox(e){return ax(e),e.headers=xb.from(e.headers),e.data=Sb.call(e,e.transformRequest),[`post`,`put`,`patch`].indexOf(e.method)!==-1&&e.headers.setContentType(`application/x-www-form-urlencoded`,!1),ix.getAdapter(e.adapter||ub.adapter,e)(e).then(function(t){return ax(e),t.data=Sb.call(e,e.transformResponse,t),t.headers=xb.from(t.headers),t},function(t){return Cb(t)||(ax(e),t&&t.response&&(t.response.data=Sb.call(e,e.transformResponse,t.response),t.response.headers=xb.from(t.response.headers))),Promise.reject(t)})}var sx=`1.12.2`,cx={};[`object`,`boolean`,`number`,`function`,`string`,`symbol`].forEach((e,t)=>{cx[e]=function(n){return typeof n===e||`a`+(t<1?`n `:` `)+e}});var lx={};cx.transitional=function(e,t,n){function r(e,t){return`[Axios v`+sx+`] Transitional option '`+e+`'`+t+(n?`. `+n:``)}return(n,i,a)=>{if(e===!1)throw new V(r(i,` has been removed`+(t?` in `+t:``)),V.ERR_DEPRECATED);return t&&!lx[i]&&(lx[i]=!0,console.warn(r(i,` has been deprecated since v`+t+` and will be removed in the near future`))),e?e(n,i,a):!0}},cx.spelling=function(e){return(t,n)=>(console.warn(`${n} is likely a misspelling of ${e}`),!0)};function ux(e,t,n){if(typeof e!=`object`)throw new V(`options must be an object`,V.ERR_BAD_OPTION_VALUE);let r=Object.keys(e),i=r.length;for(;i-- >0;){let a=r[i],o=t[a];if(o){let t=e[a],n=t===void 0||o(t,a,e);if(n!==!0)throw new V(`option `+a+` must be `+n,V.ERR_BAD_OPTION_VALUE);continue}if(n!==!0)throw new V(`Unknown option `+a,V.ERR_BAD_OPTION)}}var dx={assertOptions:ux,validators:cx},fx=dx.validators,px=class{constructor(e){this.defaults=e||{},this.interceptors={request:new Yy,response:new Yy}}async request(e,t){try{return await this._request(e,t)}catch(e){if(e instanceof Error){let t={};Error.captureStackTrace?Error.captureStackTrace(t):t=Error();let n=t.stack?t.stack.replace(/^.+\n/,``):``;try{e.stack?n&&!String(e.stack).endsWith(n.replace(/^.+\n.+\n/,``))&&(e.stack+=`
`+n):e.stack=n}catch{}}throw e}}_request(e,t){typeof e==`string`?(t||={},t.url=e):t=e||{},t=Rb(this.defaults,t);let{transitional:n,paramsSerializer:r,headers:i}=t;n!==void 0&&dx.assertOptions(n,{silentJSONParsing:fx.transitional(fx.boolean),forcedJSONParsing:fx.transitional(fx.boolean),clarifyTimeoutError:fx.transitional(fx.boolean)},!1),r!=null&&(B.isFunction(r)?t.paramsSerializer={serialize:r}:dx.assertOptions(r,{encode:fx.function,serialize:fx.function},!0)),t.allowAbsoluteUrls!==void 0||(this.defaults.allowAbsoluteUrls===void 0?t.allowAbsoluteUrls=!0:t.allowAbsoluteUrls=this.defaults.allowAbsoluteUrls),dx.assertOptions(t,{baseUrl:fx.spelling(`baseURL`),withXsrfToken:fx.spelling(`withXSRFToken`)},!0),t.method=(t.method||this.defaults.method||`get`).toLowerCase();let a=i&&B.merge(i.common,i[t.method]);i&&B.forEach([`delete`,`get`,`head`,`post`,`put`,`patch`,`common`],e=>{delete i[e]}),t.headers=xb.concat(a,i);let o=[],s=!0;this.interceptors.request.forEach(function(e){typeof e.runWhen==`function`&&e.runWhen(t)===!1||(s&&=e.synchronous,o.unshift(e.fulfilled,e.rejected))});let c=[];this.interceptors.response.forEach(function(e){c.push(e.fulfilled,e.rejected)});let l,u=0,d;if(!s){let e=[ox.bind(this),void 0];for(e.unshift(...o),e.push(...c),d=e.length,l=Promise.resolve(t);u<d;)l=l.then(e[u++],e[u++]);return l}d=o.length;let f=t;for(;u<d;){let e=o[u++],t=o[u++];try{f=e(f)}catch(e){t.call(this,e);break}}try{l=ox.call(this,f)}catch(e){return Promise.reject(e)}for(u=0,d=c.length;u<d;)l=l.then(c[u++],c[u++]);return l}getUri(e){return e=Rb(this.defaults,e),Jy(Ib(e.baseURL,e.url,e.allowAbsoluteUrls),e.params,e.paramsSerializer)}};B.forEach([`delete`,`get`,`head`,`options`],function(e){px.prototype[e]=function(t,n){return this.request(Rb(n||{},{method:e,url:t,data:(n||{}).data}))}}),B.forEach([`post`,`put`,`patch`],function(e){function t(t){return function(n,r,i){return this.request(Rb(i||{},{method:e,headers:t?{"Content-Type":`multipart/form-data`}:{},url:n,data:r}))}}px.prototype[e]=t(),px.prototype[e+`Form`]=t(!0)});var mx=class e{constructor(e){if(typeof e!=`function`)throw TypeError(`executor must be a function.`);let t;this.promise=new Promise(function(e){t=e});let n=this;this.promise.then(e=>{if(!n._listeners)return;let t=n._listeners.length;for(;t-- >0;)n._listeners[t](e);n._listeners=null}),this.promise.then=e=>{let t,r=new Promise(e=>{n.subscribe(e),t=e}).then(e);return r.cancel=function(){n.unsubscribe(t)},r},e(function(e,r,i){n.reason||(n.reason=new wb(e,r,i),t(n.reason))})}throwIfRequested(){if(this.reason)throw this.reason}subscribe(e){if(this.reason){e(this.reason);return}this._listeners?this._listeners.push(e):this._listeners=[e]}unsubscribe(e){if(!this._listeners)return;let t=this._listeners.indexOf(e);t!==-1&&this._listeners.splice(t,1)}toAbortSignal(){let e=new AbortController,t=t=>{e.abort(t)};return this.subscribe(t),e.signal.unsubscribe=()=>this.unsubscribe(t),e.signal}static source(){let t;return{token:new e(function(e){t=e}),cancel:t}}};function hx(e){return function(t){return e.apply(null,t)}}function gx(e){return B.isObject(e)&&e.isAxiosError===!0}var _x={Continue:100,SwitchingProtocols:101,Processing:102,EarlyHints:103,Ok:200,Created:201,Accepted:202,NonAuthoritativeInformation:203,NoContent:204,ResetContent:205,PartialContent:206,MultiStatus:207,AlreadyReported:208,ImUsed:226,MultipleChoices:300,MovedPermanently:301,Found:302,SeeOther:303,NotModified:304,UseProxy:305,Unused:306,TemporaryRedirect:307,PermanentRedirect:308,BadRequest:400,Unauthorized:401,PaymentRequired:402,Forbidden:403,NotFound:404,MethodNotAllowed:405,NotAcceptable:406,ProxyAuthenticationRequired:407,RequestTimeout:408,Conflict:409,Gone:410,LengthRequired:411,PreconditionFailed:412,PayloadTooLarge:413,UriTooLong:414,UnsupportedMediaType:415,RangeNotSatisfiable:416,ExpectationFailed:417,ImATeapot:418,MisdirectedRequest:421,UnprocessableEntity:422,Locked:423,FailedDependency:424,TooEarly:425,UpgradeRequired:426,PreconditionRequired:428,TooManyRequests:429,RequestHeaderFieldsTooLarge:431,UnavailableForLegalReasons:451,InternalServerError:500,NotImplemented:501,BadGateway:502,ServiceUnavailable:503,GatewayTimeout:504,HttpVersionNotSupported:505,VariantAlsoNegotiates:506,InsufficientStorage:507,LoopDetected:508,NotExtended:510,NetworkAuthenticationRequired:511};Object.entries(_x).forEach(([e,t])=>{_x[t]=e});function vx(e){let t=new px(e),n=Av(px.prototype.request,t);return B.extend(n,px.prototype,t,{allOwnKeys:!0}),B.extend(n,t,null,{allOwnKeys:!0}),n.create=function(t){return vx(Rb(e,t))},n}var yx=vx(ub);yx.Axios=px,yx.CanceledError=wb,yx.CancelToken=mx,yx.isCancel=Cb,yx.VERSION=sx,yx.toFormData=Uy,yx.AxiosError=V,yx.Cancel=yx.CanceledError,yx.all=function(e){return Promise.all(e)},yx.spread=hx,yx.isAxiosError=gx,yx.mergeConfig=Rb,yx.AxiosHeaders=xb,yx.formToJSON=e=>cb(B.isHTMLForm(e)?new FormData(e):e),yx.getAdapter=ix.getAdapter,yx.HttpStatusCode=_x,yx.default=yx;var bx=s(kv(),1);function xx(e,t){let n;return function(...r){clearTimeout(n),n=setTimeout(()=>e.apply(this,r),t)}}function Sx(e,t){return document.dispatchEvent(new CustomEvent(`inertia:${e}`,t))}var Cx=e=>Sx(`before`,{cancelable:!0,detail:{visit:e}}),wx=e=>Sx(`error`,{detail:{errors:e}}),Tx=e=>Sx(`exception`,{cancelable:!0,detail:{exception:e}}),Ex=e=>Sx(`finish`,{detail:{visit:e}}),Dx=e=>Sx(`invalid`,{cancelable:!0,detail:{response:e}}),Ox=e=>Sx(`beforeUpdate`,{detail:{page:e}}),kx=e=>Sx(`navigate`,{detail:{page:e}}),Ax=e=>Sx(`progress`,{detail:{progress:e}}),jx=e=>Sx(`start`,{detail:{visit:e}}),Mx=e=>Sx(`success`,{detail:{page:e}}),Nx=(e,t)=>Sx(`prefetched`,{detail:{fetchedAt:Date.now(),response:e.data,visit:t}}),Px=e=>Sx(`prefetching`,{detail:{visit:e}}),Fx=class{static set(e,t){typeof window<`u`&&window.sessionStorage.setItem(e,JSON.stringify(t))}static get(e){if(typeof window<`u`)return JSON.parse(window.sessionStorage.getItem(e)||`null`)}static merge(e,t){let n=this.get(e);n===null?this.set(e,t):this.set(e,{...n,...t})}static remove(e){typeof window<`u`&&window.sessionStorage.removeItem(e)}static removeNested(e,t){let n=this.get(e);n!==null&&(delete n[t],this.set(e,n))}static exists(e){try{return this.get(e)!==null}catch{return!1}}static clear(){typeof window<`u`&&window.sessionStorage.clear()}};Fx.locationVisitKey=`inertiaLocationVisit`;var Ix=async e=>{if(typeof window>`u`)throw Error(`Unable to encrypt history`);let t=Vx(),n=await Wx(await Gx());if(!n)throw Error(`Unable to encrypt history`);return await zx(t,n,e)},Lx={key:`historyKey`,iv:`historyIv`},Rx=async e=>{let t=Vx(),n=await Gx();if(!n)throw Error(`Unable to decrypt history`);return await Bx(t,n,e)},zx=async(e,t,n)=>{if(typeof window>`u`)throw Error(`Unable to encrypt history`);if(window.crypto.subtle===void 0)return console.warn(`Encryption is not supported in this environment. SSL is required.`),Promise.resolve(n);let r=new TextEncoder,i=JSON.stringify(n),a=new Uint8Array(i.length*3),o=r.encodeInto(i,a);return window.crypto.subtle.encrypt({name:`AES-GCM`,iv:e},t,a.subarray(0,o.written))},Bx=async(e,t,n)=>{if(window.crypto.subtle===void 0)return console.warn(`Decryption is not supported in this environment. SSL is required.`),Promise.resolve(n);let r=await window.crypto.subtle.decrypt({name:`AES-GCM`,iv:e},t,n);return JSON.parse(new TextDecoder().decode(r))},Vx=()=>{let e=Fx.get(Lx.iv);if(e)return new Uint8Array(e);let t=window.crypto.getRandomValues(new Uint8Array(12));return Fx.set(Lx.iv,Array.from(t)),t},Hx=async()=>window.crypto.subtle===void 0?(console.warn(`Encryption is not supported in this environment. SSL is required.`),Promise.resolve(null)):window.crypto.subtle.generateKey({name:`AES-GCM`,length:256},!0,[`encrypt`,`decrypt`]),Ux=async e=>{if(window.crypto.subtle===void 0)return console.warn(`Encryption is not supported in this environment. SSL is required.`),Promise.resolve();let t=await window.crypto.subtle.exportKey(`raw`,e);Fx.set(Lx.key,Array.from(new Uint8Array(t)))},Wx=async e=>{if(e)return e;let t=await Hx();return t?(await Ux(t),t):null},Gx=async()=>{let e=Fx.get(Lx.key);return e?await window.crypto.subtle.importKey(`raw`,new Uint8Array(e),{name:`AES-GCM`,length:256},!0,[`encrypt`,`decrypt`]):null},Kx=class{static save(){U.saveScrollPositions(Array.from(this.regions()).map(e=>({top:e.scrollTop,left:e.scrollLeft})))}static regions(){return document.querySelectorAll(`[scroll-region]`)}static reset(){let e=typeof window<`u`?window.location.hash:null;e||window.scrollTo(0,0),this.regions().forEach(e=>{typeof e.scrollTo==`function`?e.scrollTo(0,0):(e.scrollTop=0,e.scrollLeft=0)}),this.save(),e&&setTimeout(()=>{let t=document.getElementById(e.slice(1));t?t.scrollIntoView():window.scrollTo(0,0)})}static restore(e){typeof window>`u`||window.requestAnimationFrame(()=>{this.restoreDocument(),this.regions().forEach((t,n)=>{let r=e[n];r&&(typeof t.scrollTo==`function`?t.scrollTo(r.left,r.top):(t.scrollTop=r.top,t.scrollLeft=r.left))})})}static restoreDocument(){let e=U.getDocumentScrollPosition();window.scrollTo(e.left,e.top)}static onScroll(e){let t=e.target;typeof t.hasAttribute==`function`&&t.hasAttribute(`scroll-region`)&&this.save()}static onWindowScroll(){U.saveDocumentScrollPosition({top:window.scrollY,left:window.scrollX})}};function qx(e){return e instanceof File||e instanceof Blob||e instanceof FileList&&e.length>0||e instanceof FormData&&Array.from(e.values()).some(e=>qx(e))||typeof e==`object`&&!!e&&Object.values(e).some(e=>qx(e))}var Jx=e=>e instanceof FormData;function Yx(e,t=new FormData,n=null){e||={};for(let r in e)Object.prototype.hasOwnProperty.call(e,r)&&Zx(t,Xx(n,r),e[r]);return t}function Xx(e,t){return e?e+`[`+t+`]`:t}function Zx(e,t,n){if(Array.isArray(n))return Array.from(n.keys()).forEach(r=>Zx(e,Xx(t,r.toString()),n[r]));if(n instanceof Date)return e.append(t,n.toISOString());if(n instanceof File)return e.append(t,n,n.name);if(n instanceof Blob)return e.append(t,n);if(typeof n==`boolean`)return e.append(t,n?`1`:`0`);if(typeof n==`string`)return e.append(t,n);if(typeof n==`number`)return e.append(t,`${n}`);if(n==null)return e.append(t,``);Yx(n,e,t)}function Qx(e){return new URL(e.toString(),typeof window>`u`?void 0:window.location.toString())}var $x=(e,t,n,r,i)=>{let a=typeof e==`string`?Qx(e):e;if((qx(t)||r)&&!Jx(t)&&(t=Yx(t)),Jx(t))return[a,t];let[o,s]=eS(n,a,t,i);return[Qx(o),s]};function eS(e,t,n,r=`brackets`){let i=e===`get`&&!Jx(n)&&Object.keys(n).length>0,a=aS(t.toString()),o=a||t.toString().startsWith(`/`)||t.toString()===``,s=!o&&!t.toString().startsWith(`#`)&&!t.toString().startsWith(`?`),c=/^[.]{1,2}([/]|$)/.test(t.toString()),l=t.toString().includes(`?`)||i,u=t.toString().includes(`#`),d=new URL(t.toString(),typeof window>`u`?`http://localhost`:window.location.toString());return i&&(d.search=bx.stringify({...bx.parse(d.search,{ignoreQueryPrefix:!0,parseArrays:!1}),...n},{encodeValuesOnly:!0,arrayFormat:r})),[[a?`${d.protocol}//${d.host}`:``,o?d.pathname:``,s?d.pathname.substring(c?0:1):``,l?d.search:``,u?d.hash:``].join(``),i?{}:n]}function tS(e){return e=new URL(e.href),e.hash=``,e}var nS=(e,t)=>{e.hash&&!t.hash&&tS(e).href===t.href&&(t.hash=e.hash)},rS=(e,t)=>tS(e).href===tS(t).href;function iS(e){return typeof e==`object`&&!!e&&e!==void 0&&`url`in e&&`method`in e}function aS(e){return/^[a-z][a-z0-9+.-]*:\/\//i.test(e)}var H=new class{constructor(){this.componentId={},this.listeners=[],this.isFirstPageLoad=!0,this.cleared=!1,this.pendingDeferredProps=null}init({initialPage:e,swapComponent:t,resolveComponent:n}){return this.page=e,this.swapComponent=t,this.resolveComponent=n,this}set(e,{replace:t=!1,preserveScroll:n=!1,preserveState:r=!1}={}){Object.keys(e.deferredProps||{}).length&&(this.pendingDeferredProps={deferredProps:e.deferredProps,component:e.component,url:e.url}),this.componentId={};let i=this.componentId;return e.clearHistory&&U.clear(),this.resolve(e.component).then(a=>{if(i!==this.componentId)return;e.rememberedState??={};let o=typeof window<`u`?window.location:new URL(e.url);return t||=rS(Qx(e.url),o),new Promise(n=>{t?U.replaceState(e,()=>n(null)):U.pushState(e,()=>n(null))}).then(()=>{let i=!this.isTheSame(e);return this.page=e,this.cleared=!1,i&&this.fireEventsFor(`newComponent`),this.isFirstPageLoad&&this.fireEventsFor(`firstLoad`),this.isFirstPageLoad=!1,this.swap({component:a,page:e,preserveState:r}).then(()=>{n||Kx.reset(),this.pendingDeferredProps&&this.pendingDeferredProps.component===e.component&&this.pendingDeferredProps.url===e.url&&dS.fireInternalEvent(`loadDeferredProps`,this.pendingDeferredProps.deferredProps),this.pendingDeferredProps=null,t||kx(e)})})})}setQuietly(e,{preserveState:t=!1}={}){return this.resolve(e.component).then(n=>(this.page=e,this.cleared=!1,U.setCurrent(e),this.swap({component:n,page:e,preserveState:t})))}clear(){this.cleared=!0}isCleared(){return this.cleared}get(){return this.page}merge(e){this.page={...this.page,...e}}setUrlHash(e){this.page.url.includes(e)||(this.page.url+=e)}remember(e){this.page.rememberedState=e}swap({component:e,page:t,preserveState:n}){return this.swapComponent({component:e,page:t,preserveState:n})}resolve(e){return Promise.resolve(this.resolveComponent(e))}isTheSame(e){return this.page.component===e.component}on(e,t){return this.listeners.push({event:e,callback:t}),()=>{this.listeners=this.listeners.filter(n=>n.event!==e&&n.callback!==t)}}fireEventsFor(e){this.listeners.filter(t=>t.event===e).forEach(e=>e.callback())}},oS=class{constructor(){this.items=[],this.processingPromise=null}add(e){return this.items.push(e),this.process()}process(){return this.processingPromise??=this.processNext().finally(()=>{this.processingPromise=null}),this.processingPromise}processNext(){let e=this.items.shift();return e?Promise.resolve(e()).then(()=>this.processNext()):Promise.resolve()}},sS=typeof window>`u`,cS=new oS,lS=!sS&&/CriOS/.test(window.navigator.userAgent),uS=class{constructor(){this.rememberedState=`rememberedState`,this.scrollRegions=`scrollRegions`,this.preserveUrl=!1,this.current={},this.initialState=null}remember(e,t){this.replaceState({...H.get(),rememberedState:{...H.get()?.rememberedState??{},[t]:e}})}restore(e){if(!sS)return this.current[this.rememberedState]?this.current[this.rememberedState]?.[e]:this.initialState?.[this.rememberedState]?.[e]}pushState(e,t=null){if(!sS){if(this.preserveUrl){t&&t();return}this.current=e,cS.add(()=>this.getPageData(e).then(n=>{let r=()=>this.doPushState({page:n},e.url).then(()=>t?.());return lS?new Promise(e=>{setTimeout(()=>r().then(e))}):r()}))}}getPageData(e){return new Promise(t=>e.encryptHistory?Ix(e).then(t):t(e))}processQueue(){return cS.process()}decrypt(e=null){if(sS)return Promise.resolve(e??H.get());let t=e??window.history.state?.page;return this.decryptPageData(t).then(e=>{if(!e)throw Error(`Unable to decrypt history`);return this.initialState===null?this.initialState=e??void 0:this.current=e??{},e})}decryptPageData(e){return e instanceof ArrayBuffer?Rx(e):Promise.resolve(e)}saveScrollPositions(e){cS.add(()=>Promise.resolve().then(()=>{if(window.history.state?.page)return this.doReplaceState({page:window.history.state.page,scrollRegions:e})}))}saveDocumentScrollPosition(e){cS.add(()=>Promise.resolve().then(()=>{if(window.history.state?.page)return this.doReplaceState({page:window.history.state.page,documentScrollPosition:e})}))}getScrollRegions(){return window.history.state?.scrollRegions||[]}getDocumentScrollPosition(){return window.history.state?.documentScrollPosition||{top:0,left:0}}replaceState(e,t=null){if(H.merge(e),!sS){if(this.preserveUrl){t&&t();return}this.current=e,cS.add(()=>this.getPageData(e).then(n=>{let r=()=>this.doReplaceState({page:n},e.url).then(()=>t?.());return lS?new Promise(e=>{setTimeout(()=>r().then(e))}):r()}))}}doReplaceState(e,t){return Promise.resolve().then(()=>window.history.replaceState({...e,scrollRegions:e.scrollRegions??window.history.state?.scrollRegions,documentScrollPosition:e.documentScrollPosition??window.history.state?.documentScrollPosition},``,t))}doPushState(e,t){return Promise.resolve().then(()=>window.history.pushState(e,``,t))}getState(e,t){return this.current?.[e]??t}deleteState(e){this.current[e]!==void 0&&(delete this.current[e],this.replaceState(this.current))}clearInitialState(e){this.initialState&&this.initialState[e]!==void 0&&delete this.initialState[e]}hasAnyState(){return!!this.getAllState()}clear(){Fx.remove(Lx.key),Fx.remove(Lx.iv)}setCurrent(e){this.current=e}isValidState(e){return!!e.page}getAllState(){return this.current}};typeof window<`u`&&window.history.scrollRestoration&&(window.history.scrollRestoration=`manual`);var U=new uS,dS=new class{constructor(){this.internalListeners=[]}init(){typeof window<`u`&&(window.addEventListener(`popstate`,this.handlePopstateEvent.bind(this)),window.addEventListener(`scroll`,xx(Kx.onWindowScroll.bind(Kx),100),!0)),typeof document<`u`&&document.addEventListener(`scroll`,xx(Kx.onScroll.bind(Kx),100),!0)}onGlobalEvent(e,t){return this.registerListener(`inertia:${e}`,(e=>{let n=t(e);e.cancelable&&!e.defaultPrevented&&n===!1&&e.preventDefault()}))}on(e,t){return this.internalListeners.push({event:e,listener:t}),()=>{this.internalListeners=this.internalListeners.filter(e=>e.listener!==t)}}onMissingHistoryItem(){H.clear(),this.fireInternalEvent(`missingHistoryItem`)}fireInternalEvent(e,...t){this.internalListeners.filter(t=>t.event===e).forEach(e=>e.listener(...t))}registerListener(e,t){return document.addEventListener(e,t),()=>document.removeEventListener(e,t)}handlePopstateEvent(e){let t=e.state||null;if(t===null){let e=Qx(H.get().url);e.hash=window.location.hash,U.replaceState({...H.get(),url:e.href}),Kx.reset();return}if(!U.isValidState(t))return this.onMissingHistoryItem();U.decrypt(t.page).then(e=>{if(H.get().version!==e.version){this.onMissingHistoryItem();return}pC.cancelAll(),H.setQuietly(e,{preserveState:!1}).then(()=>{Kx.restore(U.getScrollRegions()),kx(H.get())})}).catch(()=>{this.onMissingHistoryItem()})}},fS=new class{constructor(){this.type=this.resolveType()}resolveType(){return typeof window>`u`?`navigate`:window.performance&&window.performance.getEntriesByType&&window.performance.getEntriesByType(`navigation`).length>0?window.performance.getEntriesByType(`navigation`)[0].type:`navigate`}get(){return this.type}isBackForward(){return this.type===`back_forward`}isReload(){return this.type===`reload`}},pS=class{static handle(){this.clearRememberedStateOnReload(),[this.handleBackForward,this.handleLocation,this.handleDefault].find(e=>e.bind(this)())}static clearRememberedStateOnReload(){fS.isReload()&&(U.deleteState(U.rememberedState),U.clearInitialState(U.rememberedState))}static handleBackForward(){if(!fS.isBackForward()||!U.hasAnyState())return!1;let e=U.getScrollRegions();return U.decrypt().then(t=>{H.set(t,{preserveScroll:!0,preserveState:!0}).then(()=>{Kx.restore(e),kx(H.get())})}).catch(()=>{dS.onMissingHistoryItem()}),!0}static handleLocation(){if(!Fx.exists(Fx.locationVisitKey))return!1;let e=Fx.get(Fx.locationVisitKey)||{};return Fx.remove(Fx.locationVisitKey),typeof window<`u`&&H.setUrlHash(window.location.hash),U.decrypt(H.get()).then(()=>{let t=U.getState(U.rememberedState,{}),n=U.getScrollRegions();H.remember(t),H.set(H.get(),{preserveScroll:e.preserveScroll,preserveState:!0}).then(()=>{e.preserveScroll&&Kx.restore(n),kx(H.get())})}).catch(()=>{dS.onMissingHistoryItem()}),!0}static handleDefault(){typeof window<`u`&&H.setUrlHash(window.location.hash),H.set(H.get(),{preserveScroll:!0,preserveState:!0}).then(()=>{fS.isReload()&&Kx.restore(U.getScrollRegions()),kx(H.get())})}},mS=class{constructor(e,t,n){this.id=null,this.throttle=!1,this.keepAlive=!1,this.cbCount=0,this.keepAlive=n.keepAlive??!1,this.cb=t,this.interval=e,(n.autoStart??!0)&&this.start()}stop(){this.id&&clearInterval(this.id)}start(){typeof window>`u`||(this.stop(),this.id=window.setInterval(()=>{(!this.throttle||this.cbCount%10==0)&&this.cb(),this.throttle&&this.cbCount++},this.interval))}isInBackground(e){this.throttle=this.keepAlive?!1:e,this.throttle&&(this.cbCount=0)}},hS=new class{constructor(){this.polls=[],this.setupVisibilityListener()}add(e,t,n){let r=new mS(e,t,n);return this.polls.push(r),{stop:()=>r.stop(),start:()=>r.start()}}clear(){this.polls.forEach(e=>e.stop()),this.polls=[]}setupVisibilityListener(){typeof document>`u`||document.addEventListener(`visibilitychange`,()=>{this.polls.forEach(e=>e.isInBackground(document.hidden))},!1)}},gS=(e,t,n)=>{if(e===t)return!0;for(let r in e)if(!n.includes(r)&&e[r]!==t[r]&&!_S(e[r],t[r]))return!1;return!0},_S=(e,t)=>{switch(typeof e){case`object`:return gS(e,t,[]);case`function`:return e.toString()===t.toString();default:return e===t}},vS={ms:1,s:1e3,m:1e3*60,h:1e3*60*60,d:1e3*60*60*24},yS=e=>{if(typeof e==`number`)return e;for(let[t,n]of Object.entries(vS))if(e.endsWith(t))return parseFloat(e)*n;return parseInt(e)},bS=new class{constructor(){this.cached=[],this.inFlightRequests=[],this.removalTimers=[],this.currentUseId=null}add(e,t,{cacheFor:n,cacheTags:r}){if(this.findInFlight(e))return Promise.resolve();let i=this.findCached(e);if(!e.fresh&&i&&i.staleTimestamp>Date.now())return Promise.resolve();let[a,o]=this.extractStaleValues(n),s=new Promise((n,r)=>{t({...e,onCancel:()=>{this.remove(e),e.onCancel(),r()},onError:t=>{this.remove(e),e.onError(t),r()},onPrefetching(t){e.onPrefetching(t)},onPrefetched(t,n){e.onPrefetched(t,n)},onPrefetchResponse(e){n(e)},onPrefetchError(t){bS.removeFromInFlight(e),r(t)}})}).then(t=>(this.remove(e),this.cached.push({params:{...e},staleTimestamp:Date.now()+a,response:s,singleUse:o===0,timestamp:Date.now(),inFlight:!1,tags:Array.isArray(r)?r:[r]}),this.scheduleForRemoval(e,o),this.removeFromInFlight(e),t.handlePrefetch(),t));return this.inFlightRequests.push({params:{...e},response:s,staleTimestamp:null,inFlight:!0}),s}removeAll(){this.cached=[],this.removalTimers.forEach(e=>{clearTimeout(e.timer)}),this.removalTimers=[]}removeByTags(e){this.cached=this.cached.filter(t=>!t.tags.some(t=>e.includes(t)))}remove(e){this.cached=this.cached.filter(t=>!this.paramsAreEqual(t.params,e)),this.clearTimer(e)}removeFromInFlight(e){this.inFlightRequests=this.inFlightRequests.filter(t=>!this.paramsAreEqual(t.params,e))}extractStaleValues(e){let[t,n]=this.cacheForToStaleAndExpires(e);return[yS(t),yS(n)]}cacheForToStaleAndExpires(e){if(!Array.isArray(e))return[e,e];switch(e.length){case 0:return[0,0];case 1:return[e[0],e[0]];default:return[e[0],e[1]]}}clearTimer(e){let t=this.removalTimers.find(t=>this.paramsAreEqual(t.params,e));t&&(clearTimeout(t.timer),this.removalTimers=this.removalTimers.filter(e=>e!==t))}scheduleForRemoval(e,t){if(!(typeof window>`u`)&&(this.clearTimer(e),t>0)){let n=window.setTimeout(()=>this.remove(e),t);this.removalTimers.push({params:e,timer:n})}}get(e){return this.findCached(e)||this.findInFlight(e)}use(e,t){let n=`${t.url.pathname}-${Date.now()}-${Math.random().toString(36).substring(7)}`;return this.currentUseId=n,e.response.then(e=>{if(this.currentUseId===n)return e.mergeParams({...t,onPrefetched:()=>{}}),this.removeSingleUseItems(t),e.handle()})}removeSingleUseItems(e){this.cached=this.cached.filter(t=>this.paramsAreEqual(t.params,e)?!t.singleUse:!0)}findCached(e){return this.cached.find(t=>this.paramsAreEqual(t.params,e))||null}findInFlight(e){return this.inFlightRequests.find(t=>this.paramsAreEqual(t.params,e))||null}withoutPurposePrefetchHeader(e){let t=Ug(e);return t.headers.Purpose===`prefetch`&&delete t.headers.Purpose,t}paramsAreEqual(e,t){return gS(this.withoutPurposePrefetchHeader(e),this.withoutPurposePrefetchHeader(t),[`showProgress`,`replace`,`prefetch`,`onBefore`,`onBeforeUpdate`,`onStart`,`onProgress`,`onFinish`,`onCancel`,`onSuccess`,`onError`,`onPrefetched`,`onCancelToken`,`onPrefetching`,`async`])}},xS=class e{constructor(e){if(this.callbacks=[],!e.prefetch)this.params=e;else{let t={onBefore:this.wrapCallback(e,`onBefore`),onBeforeUpdate:this.wrapCallback(e,`onBeforeUpdate`),onStart:this.wrapCallback(e,`onStart`),onProgress:this.wrapCallback(e,`onProgress`),onFinish:this.wrapCallback(e,`onFinish`),onCancel:this.wrapCallback(e,`onCancel`),onSuccess:this.wrapCallback(e,`onSuccess`),onError:this.wrapCallback(e,`onError`),onCancelToken:this.wrapCallback(e,`onCancelToken`),onPrefetched:this.wrapCallback(e,`onPrefetched`),onPrefetching:this.wrapCallback(e,`onPrefetching`)};this.params={...e,...t,onPrefetchResponse:e.onPrefetchResponse||(()=>{}),onPrefetchError:e.onPrefetchError||(()=>{})}}}static create(t){return new e(t)}data(){return this.params.method===`get`?null:this.params.data}queryParams(){return this.params.method===`get`?this.params.data:{}}isPartial(){return this.params.only.length>0||this.params.except.length>0||this.params.reset.length>0}onCancelToken(e){this.params.onCancelToken({cancel:e})}markAsFinished(){this.params.completed=!0,this.params.cancelled=!1,this.params.interrupted=!1}markAsCancelled({cancelled:e=!0,interrupted:t=!1}){this.params.onCancel(),this.params.completed=!1,this.params.cancelled=e,this.params.interrupted=t}wasCancelledAtAll(){return this.params.cancelled||this.params.interrupted}onFinish(){this.params.onFinish(this.params)}onStart(){this.params.onStart(this.params)}onPrefetching(){this.params.onPrefetching(this.params)}onPrefetchResponse(e){this.params.onPrefetchResponse&&this.params.onPrefetchResponse(e)}onPrefetchError(e){this.params.onPrefetchError&&this.params.onPrefetchError(e)}all(){return this.params}headers(){let e={...this.params.headers};this.isPartial()&&(e[`X-Inertia-Partial-Component`]=H.get().component);let t=this.params.only.concat(this.params.reset);return t.length>0&&(e[`X-Inertia-Partial-Data`]=t.join(`,`)),this.params.except.length>0&&(e[`X-Inertia-Partial-Except`]=this.params.except.join(`,`)),this.params.reset.length>0&&(e[`X-Inertia-Reset`]=this.params.reset.join(`,`)),this.params.errorBag&&this.params.errorBag.length>0&&(e[`X-Inertia-Error-Bag`]=this.params.errorBag),e}setPreserveOptions(e){this.params.preserveScroll=this.resolvePreserveOption(this.params.preserveScroll,e),this.params.preserveState=this.resolvePreserveOption(this.params.preserveState,e)}runCallbacks(){this.callbacks.forEach(({name:e,args:t})=>{this.params[e](...t)})}merge(e){this.params={...this.params,...e}}wrapCallback(e,t){return(...n)=>{this.recordCallback(t,n),e[t](...n)}}recordCallback(e,t){this.callbacks.push({name:e,args:t})}resolvePreserveOption(e,t){return typeof e==`function`?e(t):e===`errors`?Object.keys(t.props.errors||{}).length>0:e}},SS={modal:null,listener:null,show(e){typeof e==`object`&&(e=`All Inertia requests must receive a valid Inertia response, however a plain JSON response was received.<hr>${JSON.stringify(e)}`);let t=document.createElement(`html`);t.innerHTML=e,t.querySelectorAll(`a`).forEach(e=>e.setAttribute(`target`,`_top`)),this.modal=document.createElement(`div`),this.modal.style.position=`fixed`,this.modal.style.width=`100vw`,this.modal.style.height=`100vh`,this.modal.style.padding=`50px`,this.modal.style.boxSizing=`border-box`,this.modal.style.backgroundColor=`rgba(0, 0, 0, .6)`,this.modal.style.zIndex=2e5,this.modal.addEventListener(`click`,()=>this.hide());let n=document.createElement(`iframe`);if(n.style.backgroundColor=`white`,n.style.borderRadius=`5px`,n.style.width=`100%`,n.style.height=`100%`,this.modal.appendChild(n),document.body.prepend(this.modal),document.body.style.overflow=`hidden`,!n.contentWindow)throw Error(`iframe not yet ready.`);n.contentWindow.document.open(),n.contentWindow.document.write(t.outerHTML),n.contentWindow.document.close(),this.listener=this.hideOnEscape.bind(this),document.addEventListener(`keydown`,this.listener)},hide(){this.modal.outerHTML=``,this.modal=null,document.body.style.overflow=`visible`,document.removeEventListener(`keydown`,this.listener)},hideOnEscape(e){e.keyCode===27&&this.hide()}},CS=new oS,wS=class e{constructor(e,t,n){this.requestParams=e,this.response=t,this.originatingPage=n,this.wasPrefetched=!1}static create(t,n,r){return new e(t,n,r)}async handlePrefetch(){rS(this.requestParams.all().url,window.location)&&this.handle()}async handle(){return CS.add(()=>this.process())}async process(){if(this.requestParams.all().prefetch)return this.wasPrefetched=!0,this.requestParams.all().prefetch=!1,this.requestParams.all().onPrefetched(this.response,this.requestParams.all()),Nx(this.response,this.requestParams.all()),Promise.resolve();if(this.requestParams.runCallbacks(),!this.isInertiaResponse())return this.handleNonInertiaResponse();await U.processQueue(),U.preserveUrl=this.requestParams.all().preserveUrl,await this.setPage();let e=H.get().props.errors||{};if(Object.keys(e).length>0){let t=this.getScopedErrors(e);return wx(t),this.requestParams.all().onError(t)}pC.flushByCacheTags(this.requestParams.all().invalidateCacheTags||[]),this.wasPrefetched||pC.flush(H.get().url),Mx(H.get()),await this.requestParams.all().onSuccess(H.get()),U.preserveUrl=!1}mergeParams(e){this.requestParams.merge(e)}async handleNonInertiaResponse(){if(this.isLocationVisit()){let e=Qx(this.getHeader(`x-inertia-location`));return nS(this.requestParams.all().url,e),this.locationVisit(e)}let e={...this.response,data:this.getDataFromResponse(this.response.data)};if(Dx(e))return SS.show(e.data)}isInertiaResponse(){return this.hasHeader(`x-inertia`)}hasStatus(e){return this.response.status===e}getHeader(e){return this.response.headers[e]}hasHeader(e){return this.getHeader(e)!==void 0}isLocationVisit(){return this.hasStatus(409)&&this.hasHeader(`x-inertia-location`)}locationVisit(e){try{if(Fx.set(Fx.locationVisitKey,{preserveScroll:this.requestParams.all().preserveScroll===!0}),typeof window>`u`)return;rS(window.location,e)?window.location.reload():window.location.href=e.href}catch{return!1}}async setPage(){let e=this.getDataFromResponse(this.response.data);return this.shouldSetPage(e)?(this.mergeProps(e),await this.setRememberedState(e),this.requestParams.setPreserveOptions(e),e.url=U.preserveUrl?H.get().url:this.pageUrl(e),this.requestParams.all().onBeforeUpdate(e),Ox(e),H.set(e,{replace:this.requestParams.all().replace,preserveScroll:this.requestParams.all().preserveScroll,preserveState:this.requestParams.all().preserveState})):Promise.resolve()}getDataFromResponse(e){if(typeof e!=`string`)return e;try{return JSON.parse(e)}catch{return e}}shouldSetPage(e){if(!this.requestParams.all().async||this.originatingPage.component!==e.component)return!0;if(this.originatingPage.component!==H.get().component)return!1;let t=Qx(this.originatingPage.url),n=Qx(H.get().url);return t.origin===n.origin&&t.pathname===n.pathname}pageUrl(e){let t=Qx(e.url);return nS(this.requestParams.all().url,t),t.pathname+t.search+t.hash}mergeProps(e){if(!this.requestParams.isPartial()||e.component!==H.get().component)return;let t=e.mergeProps||[],n=e.prependProps||[],r=e.deepMergeProps||[],i=e.matchPropsOn||[],a=(t,n)=>{let r=Pm(H.get().props,t),a=Pm(e.props,t);if(Array.isArray(a)){let o=this.mergeOrMatchItems(r||[],a,t,i,n);L_(e.props,t,o)}else if(typeof a==`object`&&a){let n={...r||{},...a};L_(e.props,t,n)}};t.forEach(e=>a(e,!0)),n.forEach(e=>a(e,!1)),r.forEach(t=>{let n=H.get().props[t],r=e.props[t],a=(e,t,n)=>Array.isArray(t)?this.mergeOrMatchItems(e,t,n,i):typeof t==`object`&&t?Object.keys(t).reduce((r,i)=>(r[i]=a(e?e[i]:void 0,t[i],`${n}.${i}`),r),{...e}):t;e.props[t]=a(n,r,t)}),e.props={...H.get().props,...e.props},H.get().scrollProps&&(e.scrollProps={...H.get().scrollProps||{},...e.scrollProps||{}})}mergeOrMatchItems(e,t,n,r,i=!0){let a=Array.isArray(e)?e:[],o=r.find(e=>e.split(`.`).slice(0,-1).join(`.`)===n);if(!o)return i?[...a,...t]:[...t,...a];let s=o.split(`.`).pop()||``,c=new Map;return t.forEach(e=>{this.hasUniqueProperty(e,s)&&c.set(e[s],e)}),i?this.appendWithMatching(a,t,c,s):this.prependWithMatching(a,t,c,s)}appendWithMatching(e,t,n,r){let i=e.map(e=>this.hasUniqueProperty(e,r)&&n.has(e[r])?n.get(e[r]):e),a=t.filter(t=>this.hasUniqueProperty(t,r)?!e.some(e=>this.hasUniqueProperty(e,r)&&e[r]===t[r]):!0);return[...i,...a]}prependWithMatching(e,t,n,r){let i=e.filter(e=>this.hasUniqueProperty(e,r)?!n.has(e[r]):!0);return[...t,...i]}hasUniqueProperty(e,t){return e&&typeof e==`object`&&t in e}async setRememberedState(e){let t=await U.getState(U.rememberedState,{});this.requestParams.all().preserveState&&t&&e.component===H.get().component&&(e.rememberedState=t)}getScopedErrors(e){return this.requestParams.all().errorBag?e[this.requestParams.all().errorBag||``]||{}:e}},TS=class e{constructor(e,t){this.page=t,this.requestHasFinished=!1,this.requestParams=xS.create(e),this.cancelToken=new AbortController}static create(t,n){return new e(t,n)}async send(){this.requestParams.onCancelToken(()=>this.cancel({cancelled:!0})),jx(this.requestParams.all()),this.requestParams.onStart(),this.requestParams.all().prefetch&&(this.requestParams.onPrefetching(),Px(this.requestParams.all()));let e=this.requestParams.all().prefetch;return yx({method:this.requestParams.all().method,url:tS(this.requestParams.all().url).href,data:this.requestParams.data(),params:this.requestParams.queryParams(),signal:this.cancelToken.signal,headers:this.getHeaders(),onUploadProgress:this.onProgress.bind(this),responseType:`text`}).then(e=>(this.response=wS.create(this.requestParams,e,this.page),this.response.handle())).catch(e=>e?.response?(this.response=wS.create(this.requestParams,e.response,this.page),this.response.handle()):Promise.reject(e)).catch(t=>{if(!yx.isCancel(t)&&Tx(t))return e&&this.requestParams.onPrefetchError(t),Promise.reject(t)}).finally(()=>{this.finish(),e&&this.response&&this.requestParams.onPrefetchResponse(this.response)})}finish(){this.requestParams.wasCancelledAtAll()||(this.requestParams.markAsFinished(),this.fireFinishEvents())}fireFinishEvents(){this.requestHasFinished||(this.requestHasFinished=!0,Ex(this.requestParams.all()),this.requestParams.onFinish())}cancel({cancelled:e=!1,interrupted:t=!1}){this.requestHasFinished||(this.cancelToken.abort(),this.requestParams.markAsCancelled({cancelled:e,interrupted:t}),this.fireFinishEvents())}onProgress(e){this.requestParams.data()instanceof FormData&&(e.percentage=e.progress?Math.round(e.progress*100):0,Ax(e),this.requestParams.all().onProgress(e))}getHeaders(){let e={...this.requestParams.headers(),Accept:`text/html, application/xhtml+xml`,"X-Requested-With":`XMLHttpRequest`,"X-Inertia":!0};return H.get().version&&(e[`X-Inertia-Version`]=H.get().version),e}},ES=class{constructor({maxConcurrent:e,interruptible:t}){this.requests=[],this.maxConcurrent=e,this.interruptible=t}send(e){this.requests.push(e),e.send().then(()=>{this.requests=this.requests.filter(t=>t!==e)})}interruptInFlight(){this.cancel({interrupted:!0},!1)}cancelInFlight(){this.cancel({cancelled:!0},!0)}cancel({cancelled:e=!1,interrupted:t=!1}={},n){this.shouldCancel(n)&&this.requests.shift()?.cancel({interrupted:t,cancelled:e})}shouldCancel(e){return e?!0:this.interruptible&&this.requests.length>=this.maxConcurrent}},DS=class{constructor(){this.syncRequestStream=new ES({maxConcurrent:1,interruptible:!0}),this.asyncRequestStream=new ES({maxConcurrent:1/0,interruptible:!1})}init({initialPage:e,resolveComponent:t,swapComponent:n}){H.init({initialPage:e,resolveComponent:t,swapComponent:n}),pS.handle(),dS.init(),dS.on(`missingHistoryItem`,()=>{typeof window<`u`&&this.visit(window.location.href,{preserveState:!0,preserveScroll:!0,replace:!0})}),dS.on(`loadDeferredProps`,e=>{this.loadDeferredProps(e)})}get(e,t={},n={}){return this.visit(e,{...n,method:`get`,data:t})}post(e,t={},n={}){return this.visit(e,{preserveState:!0,...n,method:`post`,data:t})}put(e,t={},n={}){return this.visit(e,{preserveState:!0,...n,method:`put`,data:t})}patch(e,t={},n={}){return this.visit(e,{preserveState:!0,...n,method:`patch`,data:t})}delete(e,t={}){return this.visit(e,{preserveState:!0,...t,method:`delete`})}reload(e={}){if(!(typeof window>`u`))return this.visit(window.location.href,{...e,preserveScroll:!0,preserveState:!0,async:!0,headers:{...e.headers||{},"Cache-Control":`no-cache`}})}remember(e,t=`default`){U.remember(e,t)}restore(e=`default`){return U.restore(e)}on(e,t){return typeof window>`u`?()=>{}:dS.onGlobalEvent(e,t)}cancel(){this.syncRequestStream.cancelInFlight()}cancelAll(){this.asyncRequestStream.cancelInFlight(),this.syncRequestStream.cancelInFlight()}poll(e,t={},n={}){return hS.add(e,()=>this.reload(t),{autoStart:n.autoStart??!0,keepAlive:n.keepAlive??!1})}visit(e,t={}){let n=this.getPendingVisit(e,{...t,showProgress:t.showProgress??!t.async}),r=this.getVisitEvents(t);if(r.onBefore(n)===!1||!Cx(n))return;let i=n.async?this.asyncRequestStream:this.syncRequestStream;i.interruptInFlight(),!H.isCleared()&&!n.preserveUrl&&Kx.save();let a={...n,...r},o=bS.get(a);o?(tC.reveal(o.inFlight),bS.use(o,a)):(tC.reveal(!0),i.send(TS.create(a,H.get())))}getCached(e,t={}){return bS.findCached(this.getPrefetchParams(e,t))}flush(e,t={}){bS.remove(this.getPrefetchParams(e,t))}flushAll(){bS.removeAll()}flushByCacheTags(e){bS.removeByTags(Array.isArray(e)?e:[e])}getPrefetching(e,t={}){return bS.findInFlight(this.getPrefetchParams(e,t))}prefetch(e,t={},n={}){if((t.method??(iS(e)?e.method:`get`))!==`get`)throw Error(`Prefetch requests must use the GET method`);let r=this.getPendingVisit(e,{...t,async:!0,showProgress:!1,prefetch:!0});if(r.url.origin+r.url.pathname+r.url.search===window.location.origin+window.location.pathname+window.location.search)return;let i=this.getVisitEvents(t);if(i.onBefore(r)===!1||!Cx(r))return;tC.hide(),this.asyncRequestStream.interruptInFlight();let a={...r,...i};new Promise(e=>{let t=()=>{H.get()?e():setTimeout(t,50)};t()}).then(()=>{bS.add(a,e=>{this.asyncRequestStream.send(TS.create(e,H.get()))},{cacheFor:3e4,cacheTags:[],...n})})}clearHistory(){U.clear()}decryptHistory(){return U.decrypt()}resolveComponent(e){return H.resolve(e)}replace(e){this.clientVisit(e,{replace:!0})}replaceProp(e,t,n){this.replace({preserveScroll:!0,preserveState:!0,props(n){let r=typeof t==`function`?t(Pm(n,e),n):t;return L_(Ug(n),e,r)},...n||{}})}appendToProp(e,t,n){this.replaceProp(e,(e,n)=>{let r=typeof t==`function`?t(e,n):t;return Array.isArray(e)||(e=e===void 0?[]:[e]),[...e,r]},n)}prependToProp(e,t,n){this.replaceProp(e,(e,n)=>{let r=typeof t==`function`?t(e,n):t;return Array.isArray(e)||(e=e===void 0?[]:[e]),[r,...e]},n)}push(e){this.clientVisit(e)}clientVisit(e,{replace:t=!1}={}){let n=H.get(),r=typeof e.props==`function`?e.props(n.props):e.props??n.props,{onError:i,onFinish:a,onSuccess:o,...s}=e;H.set({...n,...s,props:r},{replace:t,preserveScroll:e.preserveScroll,preserveState:e.preserveState}).then(()=>{let t=H.get().props.errors||{};if(Object.keys(t).length===0)return o?.(H.get());let n=e.errorBag?t[e.errorBag||``]||{}:t;return i?.(n)}).finally(()=>a?.(e))}getPrefetchParams(e,t){return{...this.getPendingVisit(e,{...t,async:!0,showProgress:!1,prefetch:!0}),...this.getVisitEvents(t)}}getPendingVisit(e,t,n={}){if(iS(e)){let n=e;e=n.url,t.method=t.method??n.method}let r={method:`get`,data:{},replace:!1,preserveScroll:!1,preserveState:!1,only:[],except:[],headers:{},errorBag:``,forceFormData:!1,queryStringArrayFormat:`brackets`,async:!1,showProgress:!0,fresh:!1,reset:[],preserveUrl:!1,prefetch:!1,invalidateCacheTags:[],...t},[i,a]=$x(e,r.data,r.method,r.forceFormData,r.queryStringArrayFormat),o={cancelled:!1,completed:!1,interrupted:!1,...r,...n,url:i,data:a};return o.prefetch&&(o.headers.Purpose=`prefetch`),o}getVisitEvents(e){return{onCancelToken:e.onCancelToken||(()=>{}),onBefore:e.onBefore||(()=>{}),onBeforeUpdate:e.onBeforeUpdate||(()=>{}),onStart:e.onStart||(()=>{}),onProgress:e.onProgress||(()=>{}),onFinish:e.onFinish||(()=>{}),onCancel:e.onCancel||(()=>{}),onSuccess:e.onSuccess||(()=>{}),onError:e.onError||(()=>{}),onPrefetched:e.onPrefetched||(()=>{}),onPrefetching:e.onPrefetching||(()=>{})}}loadDeferredProps(e){e&&Object.entries(e).forEach(([e,t])=>{this.reload({only:t})})}};function OS(e){return e.includes(`.`)?e.replace(/\\\./g,`__ESCAPED_DOT__`).split(/(\[[^\]]*\])/).filter(Boolean).map(e=>e.startsWith(`[`)&&e.endsWith(`]`)?e:e.split(`.`).reduce((e,t,n)=>n===0?t:`${e}[${t}]`)).join(``).replace(/__ESCAPED_DOT__/g,`.`):e}function kS(e){let t=[],n=/([^\[\]]+)|\[(\d*)\]/g,r;for(;(r=n.exec(e))!==null;)r[1]===void 0?r[2]!==void 0&&t.push(r[2]===``?``:Number(r[2])):t.push(r[1]);return t}function AS(e){let t={};for(let[n,r]of e.entries()){if(r instanceof File&&r.size===0&&r.name===``)continue;let e=kS(OS(n));if(e[e.length-1]===``){let n=e.slice(0,-1),i=Pm(t,n);Array.isArray(i)?i.push(r):L_(t,n,[r]);continue}L_(t,e,r)}return t}var jS={buildDOMElement(e){let t=document.createElement(`template`);t.innerHTML=e;let n=t.content.firstChild;if(!e.startsWith(`<script `))return n;let r=document.createElement(`script`);return r.innerHTML=n.innerHTML,n.getAttributeNames().forEach(e=>{r.setAttribute(e,n.getAttribute(e)||``)}),r},isInertiaManagedElement(e){return e.nodeType===Node.ELEMENT_NODE&&e.getAttribute(`inertia`)!==null},findMatchingElementIndex(e,t){let n=e.getAttribute(`inertia`);return n===null?-1:t.findIndex(e=>e.getAttribute(`inertia`)===n)},update:xx(function(e){let t=e.map(e=>this.buildDOMElement(e));Array.from(document.head.childNodes).filter(e=>this.isInertiaManagedElement(e)).forEach(e=>{let n=this.findMatchingElementIndex(e,t);if(n===-1){e?.parentNode?.removeChild(e);return}let r=t.splice(n,1)[0];r&&!e.isEqualNode(r)&&e?.parentNode?.replaceChild(r,e)}),t.forEach(e=>document.head.appendChild(e))},1)};function MS(e,t,n){let r={},i=0;function a(){let e=i+=1;return r[e]=[],e.toString()}function o(e){e===null||Object.keys(r).indexOf(e)===-1||(delete r[e],u())}function s(e){Object.keys(r).indexOf(e)===-1&&(r[e]=[])}function c(e,t=[]){e!==null&&Object.keys(r).indexOf(e)>-1&&(r[e]=t),u()}function l(){let e=t(``),n={...e?{title:`<title inertia="">${e}</title>`}:{}},i=Object.values(r).reduce((e,t)=>e.concat(t),[]).reduce((e,n)=>{if(n.indexOf(`<`)===-1)return e;if(n.indexOf(`<title `)===0){let r=n.match(/(<title [^>]+>)(.*?)(<\/title>)/);return e.title=r?`${r[1]}${t(r[2])}${r[3]}`:n,e}let r=n.match(/ inertia="[^"]+"/);return r?e[r[0]]=n:e[Object.keys(e).length]=n,e},n);return Object.values(i)}function u(){e?n(l()):jS.update(l())}return u(),{forceUpdate:u,createProvider:function(){let e=a();return{reconnect:()=>s(e),update:t=>c(e,t),disconnect:()=>o(e)}}}}new oS;function NS(e){let t=e.currentTarget.tagName.toLowerCase()===`a`;return!(e.target&&(e?.target).isContentEditable||e.defaultPrevented||t&&e.altKey||t&&e.ctrlKey||t&&e.metaKey||t&&e.shiftKey||t&&`button`in e&&e.button!==0)}function PS(e){let t=e.currentTarget.tagName.toLowerCase()===`button`;return e.key===`Enter`||t&&e.key===` `}var FS=`nprogress`,IS,LS={minimum:.08,easing:`linear`,positionUsing:`translate3d`,speed:200,trickle:!0,trickleSpeed:200,showSpinner:!0,barSelector:`[role="bar"]`,spinnerSelector:`[role="spinner"]`,parent:`body`,color:`#29d`,includeCSS:!0,template:[`<div class="bar" role="bar">`,`<div class="peg"></div>`,`</div>`,`<div class="spinner" role="spinner">`,`<div class="spinner-icon"></div>`,`</div>`].join(``)},RS=null,zS=e=>{Object.assign(LS,e),LS.includeCSS&&$S(LS.color),IS=document.createElement(`div`),IS.id=FS,IS.innerHTML=LS.template},BS=e=>{let t=VS();e=XS(e,LS.minimum,1),RS=e===1?null:e;let n=GS(!t),r=n.querySelector(LS.barSelector),i=LS.speed,a=LS.easing;n.offsetWidth,QS(t=>{let o=LS.positionUsing===`translate3d`?{transition:`all ${i}ms ${a}`,transform:`translate3d(${ZS(e)}%,0,0)`}:LS.positionUsing===`translate`?{transition:`all ${i}ms ${a}`,transform:`translate(${ZS(e)}%,0)`}:{marginLeft:`${ZS(e)}%`};for(let e in o)r.style[e]=o[e];if(e!==1)return setTimeout(t,i);n.style.transition=`none`,n.style.opacity=`1`,n.offsetWidth,setTimeout(()=>{n.style.transition=`all ${i}ms linear`,n.style.opacity=`0`,setTimeout(()=>{qS(),n.style.transition=``,n.style.opacity=``,t()},i)},i)})},VS=()=>typeof RS==`number`,HS=()=>{RS||BS(0);let e=function(){setTimeout(function(){RS&&(WS(),e())},LS.trickleSpeed)};LS.trickle&&e()},US=e=>{!e&&!RS||(WS(.3+.5*Math.random()),BS(1))},WS=e=>{let t=RS;if(t===null)return HS();if(!(t>1))return e=typeof e==`number`?e:(()=>{let e={.1:[0,.2],.04:[.2,.5],.02:[.5,.8],.005:[.8,.99]};for(let n in e)if(t>=e[n][0]&&t<e[n][1])return parseFloat(n);return 0})(),BS(XS(t+e,0,.994))},GS=e=>{if(JS())return document.getElementById(FS);document.documentElement.classList.add(`${FS}-busy`);let t=IS.querySelector(LS.barSelector),n=e?`-100`:ZS(RS||0),r=KS();return t.style.transition=`all 0 linear`,t.style.transform=`translate3d(${n}%,0,0)`,LS.showSpinner||IS.querySelector(LS.spinnerSelector)?.remove(),r!==document.body&&r.classList.add(`${FS}-custom-parent`),r.appendChild(IS),IS},KS=()=>YS(LS.parent)?LS.parent:document.querySelector(LS.parent),qS=()=>{document.documentElement.classList.remove(`${FS}-busy`),KS().classList.remove(`${FS}-custom-parent`),IS?.remove()},JS=()=>document.getElementById(FS)!==null,YS=e=>typeof HTMLElement==`object`?e instanceof HTMLElement:e&&typeof e==`object`&&e.nodeType===1&&typeof e.nodeName==`string`;function XS(e,t,n){return e<t?t:e>n?n:e}var ZS=e=>(-1+e)*100,QS=(()=>{let e=[],t=()=>{let n=e.shift();n&&n(t)};return n=>{e.push(n),e.length===1&&t()}})(),$S=e=>{let t=document.createElement(`style`);t.textContent=`
    #${FS} {
      pointer-events: none;
    }

    #${FS} .bar {
      background: ${e};

      position: fixed;
      z-index: 1031;
      top: 0;
      left: 0;

      width: 100%;
      height: 2px;
    }

    #${FS} .peg {
      display: block;
      position: absolute;
      right: 0px;
      width: 100px;
      height: 100%;
      box-shadow: 0 0 10px ${e}, 0 0 5px ${e};
      opacity: 1.0;

      transform: rotate(3deg) translate(0px, -4px);
    }

    #${FS} .spinner {
      display: block;
      position: fixed;
      z-index: 1031;
      top: 15px;
      right: 15px;
    }

    #${FS} .spinner-icon {
      width: 18px;
      height: 18px;
      box-sizing: border-box;

      border: solid 2px transparent;
      border-top-color: ${e};
      border-left-color: ${e};
      border-radius: 50%;

      animation: ${FS}-spinner 400ms linear infinite;
    }

    .${FS}-custom-parent {
      overflow: hidden;
      position: relative;
    }

    .${FS}-custom-parent #${FS} .spinner,
    .${FS}-custom-parent #${FS} .bar {
      position: absolute;
    }

    @keyframes ${FS}-spinner {
      0%   { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  `,document.head.appendChild(t)},eC={configure:zS,isStarted:VS,done:US,set:BS,remove:qS,start:HS,status:RS,show:()=>{IS&&(IS.style.display=``)},hide:()=>{IS&&(IS.style.display=`none`)}},tC=new class{constructor(){this.hideCount=0}start(){eC.start()}reveal(e=!1){this.hideCount=Math.max(0,this.hideCount-1),(e||this.hideCount===0)&&eC.show()}hide(){this.hideCount++,eC.hide()}set(e){eC.set(Math.max(0,Math.min(1,e)))}finish(){eC.done()}reset(){eC.set(0)}remove(){eC.done(),eC.remove()}isStarted(){return eC.isStarted()}getStatus(){return eC.status}};tC.reveal,tC.hide;function nC(e){document.addEventListener(`inertia:start`,t=>rC(t,e)),document.addEventListener(`inertia:progress`,iC)}function rC(e,t){e.detail.visit.showProgress||tC.hide();let n=setTimeout(()=>tC.start(),t);document.addEventListener(`inertia:finish`,e=>aC(e,n),{once:!0})}function iC(e){tC.isStarted()&&e.detail.progress?.percentage&&tC.set(Math.max(tC.getStatus(),e.detail.progress.percentage/100*.9))}function aC(e,t){clearTimeout(t),tC.isStarted()&&(e.detail.visit.completed?tC.finish():e.detail.visit.interrupted?tC.reset():e.detail.visit.cancelled&&tC.remove())}function oC({delay:e=250,color:t=`#29d`,includeCSS:n=!0,showSpinner:r=!1}={}){nC(e),eC.configure({showSpinner:r,includeCSS:n,color:t})}function sC(e){return e instanceof HTMLInputElement||e instanceof HTMLSelectElement||e instanceof HTMLTextAreaElement}function cC(e,t){let n=e.value,r=e.checked;switch(e.type.toLowerCase()){case`checkbox`:e.checked=t.includes(e.value);break;case`radio`:e.checked=t[0]===e.value;break;case`file`:e.value=``;break;case`button`:case`submit`:case`reset`:case`image`:break;default:e.value=t[0]!==null&&t[0]!==void 0?String(t[0]):``}return e.value!==n||e.checked!==r}function lC(e,t){let n=e.value,r=Array.from(e.selectedOptions).map(e=>e.value);if(e.multiple){let n=t.map(e=>String(e));Array.from(e.options).forEach(e=>{e.selected=n.includes(e.value)})}else e.value=t[0]===void 0?``:String(t[0]);let i=Array.from(e.selectedOptions).map(e=>e.value);return e.multiple?JSON.stringify(r.sort())!==JSON.stringify(i.sort()):e.value!==n}function uC(e,t){if(e.disabled){if(e instanceof HTMLInputElement){let t=e.value,n=e.checked;switch(e.type.toLowerCase()){case`checkbox`:case`radio`:return e.checked=e.defaultChecked,e.checked!==n;case`file`:return e.value=``,t!==``;case`button`:case`submit`:case`reset`:case`image`:return!1;default:return e.value=e.defaultValue,e.value!==t}}else if(e instanceof HTMLSelectElement){let t=Array.from(e.selectedOptions).map(e=>e.value);Array.from(e.options).forEach(e=>{e.selected=e.defaultSelected});let n=Array.from(e.selectedOptions).map(e=>e.value);return JSON.stringify(t.sort())!==JSON.stringify(n.sort())}else if(e instanceof HTMLTextAreaElement){let t=e.value;return e.value=e.defaultValue,e.value!==t}return!1}if(e instanceof HTMLInputElement)return cC(e,t);if(e instanceof HTMLSelectElement)return lC(e,t);if(e instanceof HTMLTextAreaElement){let n=e.value;return e.value=t[0]===void 0?``:String(t[0]),e.value!==n}return!1}function dC(e,t){let n=!1;return e instanceof RadioNodeList||e instanceof HTMLCollection?Array.from(e).forEach((e,r)=>{e instanceof Element&&sC(e)&&(e instanceof HTMLInputElement&&[`checkbox`,`radio`].includes(e.type.toLowerCase())?uC(e,t)&&(n=!0):uC(e,t[r]===void 0?[t[0]??null].filter(Boolean):[t[r]])&&(n=!0))}):sC(e)&&(n=uC(e,t)),n}function fC(e,t,n){if(!e)return;if(!n||n.length===0){let r=new FormData(e),i=Array.from(e.elements).map(e=>sC(e)?e.name:``).filter(Boolean);n=[...new Set([...t.keys(),...r.keys(),...i])]}let r=!1;n.forEach(n=>{let i=e.elements.namedItem(n);i&&dC(i,t.getAll(n))&&(r=!0)}),r&&e.dispatchEvent(new Event(`reset`,{bubbles:!0}))}var pC=new DS;function mC(e){let t=Object.create(null);for(let n of e.split(`,`))t[n]=1;return e=>e in t}var hC={},gC=()=>{},_C=Object.assign,vC=(e,t)=>{let n=e.indexOf(t);n>-1&&e.splice(n,1)},yC=Object.prototype.hasOwnProperty,bC=(e,t)=>yC.call(e,t),xC=Array.isArray,SC=e=>kC(e)===`[object Map]`,CC=e=>kC(e)===`[object Set]`,wC=e=>typeof e==`function`,TC=e=>typeof e==`string`,EC=e=>typeof e==`symbol`,DC=e=>typeof e==`object`&&!!e,OC=Object.prototype.toString,kC=e=>OC.call(e),AC=e=>kC(e).slice(8,-1),jC=e=>kC(e)===`[object Object]`,MC=e=>TC(e)&&e!==`NaN`&&e[0]!==`-`&&``+parseInt(e,10)===e,NC=(e,t)=>!Object.is(e,t),PC=(e,t,n,r=!1)=>{Object.defineProperty(e,t,{configurable:!0,enumerable:!1,writable:r,value:n})},FC,IC=class{constructor(e=!1){this.detached=e,this._active=!0,this._on=0,this.effects=[],this.cleanups=[],this._isPaused=!1,this.__v_skip=!0,this.parent=FC,!e&&FC&&(this.index=(FC.scopes||=[]).push(this)-1)}get active(){return this._active}pause(){if(this._active){this._isPaused=!0;let e,t;if(this.scopes)for(e=0,t=this.scopes.length;e<t;e++)this.scopes[e].pause();for(e=0,t=this.effects.length;e<t;e++)this.effects[e].pause()}}resume(){if(this._active&&this._isPaused){this._isPaused=!1;let e,t;if(this.scopes)for(e=0,t=this.scopes.length;e<t;e++)this.scopes[e].resume();for(e=0,t=this.effects.length;e<t;e++)this.effects[e].resume()}}run(e){if(this._active){let t=FC;try{return FC=this,e()}finally{FC=t}}}on(){++this._on===1&&(this.prevScope=FC,FC=this)}off(){this._on>0&&--this._on===0&&(FC=this.prevScope,this.prevScope=void 0)}stop(e){if(this._active){this._active=!1;let t,n;for(t=0,n=this.effects.length;t<n;t++)this.effects[t].stop();for(this.effects.length=0,t=0,n=this.cleanups.length;t<n;t++)this.cleanups[t]();if(this.cleanups.length=0,this.scopes){for(t=0,n=this.scopes.length;t<n;t++)this.scopes[t].stop(!0);this.scopes.length=0}if(!this.detached&&this.parent&&!e){let e=this.parent.scopes.pop();e&&e!==this&&(this.parent.scopes[this.index]=e,e.index=this.index)}this.parent=void 0}}};function LC(e){return new IC(e)}function RC(){return FC}function zC(e,t=!1){FC&&FC.cleanups.push(e)}var W,BC=new WeakSet,VC=class{constructor(e){this.fn=e,this.deps=void 0,this.depsTail=void 0,this.flags=5,this.next=void 0,this.cleanup=void 0,this.scheduler=void 0,FC&&FC.active&&FC.effects.push(this)}pause(){this.flags|=64}resume(){this.flags&64&&(this.flags&=-65,BC.has(this)&&(BC.delete(this),this.trigger()))}notify(){this.flags&2&&!(this.flags&32)||this.flags&8||GC(this)}run(){if(!(this.flags&1))return this.fn();this.flags|=2,ow(this),JC(this);let e=W,t=nw;W=this,nw=!0;try{return this.fn()}finally{YC(this),W=e,nw=t,this.flags&=-3}}stop(){if(this.flags&1){for(let e=this.deps;e;e=e.nextDep)QC(e);this.deps=this.depsTail=void 0,ow(this),this.onStop&&this.onStop(),this.flags&=-2}}trigger(){this.flags&64?BC.add(this):this.scheduler?this.scheduler():this.runIfDirty()}runIfDirty(){XC(this)&&this.run()}get dirty(){return XC(this)}},HC=0,UC,WC;function GC(e,t=!1){if(e.flags|=8,t){e.next=WC,WC=e;return}e.next=UC,UC=e}function KC(){HC++}function qC(){if(--HC>0)return;if(WC){let e=WC;for(WC=void 0;e;){let t=e.next;e.next=void 0,e.flags&=-9,e=t}}let e;for(;UC;){let t=UC;for(UC=void 0;t;){let n=t.next;if(t.next=void 0,t.flags&=-9,t.flags&1)try{t.trigger()}catch(t){e||=t}t=n}}if(e)throw e}function JC(e){for(let t=e.deps;t;t=t.nextDep)t.version=-1,t.prevActiveLink=t.dep.activeLink,t.dep.activeLink=t}function YC(e){let t,n=e.depsTail,r=n;for(;r;){let e=r.prevDep;r.version===-1?(r===n&&(n=e),QC(r),$C(r)):t=r,r.dep.activeLink=r.prevActiveLink,r.prevActiveLink=void 0,r=e}e.deps=t,e.depsTail=n}function XC(e){for(let t=e.deps;t;t=t.nextDep)if(t.dep.version!==t.version||t.dep.computed&&(ZC(t.dep.computed)||t.dep.version!==t.version))return!0;return!!e._dirty}function ZC(e){if(e.flags&4&&!(e.flags&16)||(e.flags&=-17,e.globalVersion===sw)||(e.globalVersion=sw,!e.isSSR&&e.flags&128&&(!e.deps&&!e._dirty||!XC(e))))return;e.flags|=2;let t=e.dep,n=W,r=nw;W=e,nw=!0;try{JC(e);let n=e.fn(e._value);(t.version===0||NC(n,e._value))&&(e.flags|=128,e._value=n,t.version++)}catch(e){throw t.version++,e}finally{W=n,nw=r,YC(e),e.flags&=-3}}function QC(e,t=!1){let{dep:n,prevSub:r,nextSub:i}=e;if(r&&(r.nextSub=i,e.prevSub=void 0),i&&(i.prevSub=r,e.nextSub=void 0),n.subs===e&&(n.subs=r,!r&&n.computed)){n.computed.flags&=-5;for(let e=n.computed.deps;e;e=e.nextDep)QC(e,!0)}!t&&!--n.sc&&n.map&&n.map.delete(n.key)}function $C(e){let{prevDep:t,nextDep:n}=e;t&&(t.nextDep=n,e.prevDep=void 0),n&&(n.prevDep=t,e.nextDep=void 0)}function ew(e,t){e.effect instanceof VC&&(e=e.effect.fn);let n=new VC(e);t&&_C(n,t);try{n.run()}catch(e){throw n.stop(),e}let r=n.run.bind(n);return r.effect=n,r}function tw(e){e.effect.stop()}var nw=!0,rw=[];function iw(){rw.push(nw),nw=!1}function aw(){let e=rw.pop();nw=e===void 0?!0:e}function ow(e){let{cleanup:t}=e;if(e.cleanup=void 0,t){let e=W;W=void 0;try{t()}finally{W=e}}}var sw=0,cw=class{constructor(e,t){this.sub=e,this.dep=t,this.version=t.version,this.nextDep=this.prevDep=this.nextSub=this.prevSub=this.prevActiveLink=void 0}},lw=class{constructor(e){this.computed=e,this.version=0,this.activeLink=void 0,this.subs=void 0,this.map=void 0,this.key=void 0,this.sc=0,this.__v_skip=!0}track(e){if(!W||!nw||W===this.computed)return;let t=this.activeLink;if(t===void 0||t.sub!==W)t=this.activeLink=new cw(W,this),W.deps?(t.prevDep=W.depsTail,W.depsTail.nextDep=t,W.depsTail=t):W.deps=W.depsTail=t,uw(t);else if(t.version===-1&&(t.version=this.version,t.nextDep)){let e=t.nextDep;e.prevDep=t.prevDep,t.prevDep&&(t.prevDep.nextDep=e),t.prevDep=W.depsTail,t.nextDep=void 0,W.depsTail.nextDep=t,W.depsTail=t,W.deps===t&&(W.deps=e)}return t}trigger(e){this.version++,sw++,this.notify(e)}notify(e){KC();try{for(let e=this.subs;e;e=e.prevSub)e.sub.notify()&&e.sub.dep.notify()}finally{qC()}}};function uw(e){if(e.dep.sc++,e.sub.flags&4){let t=e.dep.computed;if(t&&!e.dep.subs){t.flags|=20;for(let e=t.deps;e;e=e.nextDep)uw(e)}let n=e.dep.subs;n!==e&&(e.prevSub=n,n&&(n.nextSub=e)),e.dep.subs=e}}var dw=new WeakMap,fw=Symbol(``),pw=Symbol(``),mw=Symbol(``);function hw(e,t,n){if(nw&&W){let t=dw.get(e);t||dw.set(e,t=new Map);let r=t.get(n);r||(t.set(n,r=new lw),r.map=t,r.key=n),r.track()}}function gw(e,t,n,r,i,a){let o=dw.get(e);if(!o){sw++;return}let s=e=>{e&&e.trigger()};if(KC(),t===`clear`)o.forEach(s);else{let i=xC(e),a=i&&MC(n);if(i&&n===`length`){let e=Number(r);o.forEach((t,n)=>{(n===`length`||n===mw||!EC(n)&&n>=e)&&s(t)})}else switch((n!==void 0||o.has(void 0))&&s(o.get(n)),a&&s(o.get(mw)),t){case`add`:i?a&&s(o.get(`length`)):(s(o.get(fw)),SC(e)&&s(o.get(pw)));break;case`delete`:i||(s(o.get(fw)),SC(e)&&s(o.get(pw)));break;case`set`:SC(e)&&s(o.get(fw));break}}qC()}function _w(e,t){let n=dw.get(e);return n&&n.get(t)}function vw(e){let t=G(e);return t===e?t:(hw(t,`iterate`,mw),sT(e)?t:t.map(uT))}function yw(e){return hw(e=G(e),`iterate`,mw),e}function bw(e,t){return oT(e)?dT(aT(e)?uT(t):t):uT(t)}var xw={__proto__:null,[Symbol.iterator](){return Sw(this,Symbol.iterator,e=>bw(this,e))},concat(...e){return vw(this).concat(...e.map(e=>xC(e)?vw(e):e))},entries(){return Sw(this,`entries`,e=>(e[1]=bw(this,e[1]),e))},every(e,t){return ww(this,`every`,e,t,void 0,arguments)},filter(e,t){return ww(this,`filter`,e,t,e=>e.map(e=>bw(this,e)),arguments)},find(e,t){return ww(this,`find`,e,t,e=>bw(this,e),arguments)},findIndex(e,t){return ww(this,`findIndex`,e,t,void 0,arguments)},findLast(e,t){return ww(this,`findLast`,e,t,e=>bw(this,e),arguments)},findLastIndex(e,t){return ww(this,`findLastIndex`,e,t,void 0,arguments)},forEach(e,t){return ww(this,`forEach`,e,t,void 0,arguments)},includes(...e){return Ew(this,`includes`,e)},indexOf(...e){return Ew(this,`indexOf`,e)},join(e){return vw(this).join(e)},lastIndexOf(...e){return Ew(this,`lastIndexOf`,e)},map(e,t){return ww(this,`map`,e,t,void 0,arguments)},pop(){return Dw(this,`pop`)},push(...e){return Dw(this,`push`,e)},reduce(e,...t){return Tw(this,`reduce`,e,t)},reduceRight(e,...t){return Tw(this,`reduceRight`,e,t)},shift(){return Dw(this,`shift`)},some(e,t){return ww(this,`some`,e,t,void 0,arguments)},splice(...e){return Dw(this,`splice`,e)},toReversed(){return vw(this).toReversed()},toSorted(e){return vw(this).toSorted(e)},toSpliced(...e){return vw(this).toSpliced(...e)},unshift(...e){return Dw(this,`unshift`,e)},values(){return Sw(this,`values`,e=>bw(this,e))}};function Sw(e,t,n){let r=yw(e),i=r[t]();return r!==e&&!sT(e)&&(i._next=i.next,i.next=()=>{let e=i._next();return e.done||(e.value=n(e.value)),e}),i}var Cw=Array.prototype;function ww(e,t,n,r,i,a){let o=yw(e),s=o!==e&&!sT(e),c=o[t];if(c!==Cw[t]){let t=c.apply(e,a);return s?uT(t):t}let l=n;o!==e&&(s?l=function(t,r){return n.call(this,bw(e,t),r,e)}:n.length>2&&(l=function(t,r){return n.call(this,t,r,e)}));let u=c.call(o,l,r);return s&&i?i(u):u}function Tw(e,t,n,r){let i=yw(e),a=i!==e&&!sT(e),o=n,s=!1;i!==e&&(a?(s=r.length===0,o=function(t,r,i){return s&&(s=!1,t=bw(e,t)),n.call(this,t,bw(e,r),i,e)}):n.length>3&&(o=function(t,r,i){return n.call(this,t,r,i,e)}));let c=i[t](o,...r);return s?bw(e,c):c}function Ew(e,t,n){let r=G(e);hw(r,`iterate`,mw);let i=r[t](...n);return(i===-1||i===!1)&&cT(n[0])?(n[0]=G(n[0]),r[t](...n)):i}function Dw(e,t,n=[]){iw(),KC();let r=G(e)[t].apply(e,n);return qC(),aw(),r}var Ow=mC(`__proto__,__v_isRef,__isVue`),kw=new Set(Object.getOwnPropertyNames(Symbol).filter(e=>e!==`arguments`&&e!==`caller`).map(e=>Symbol[e]).filter(EC));function Aw(e){EC(e)||(e=String(e));let t=G(this);return hw(t,`has`,e),t.hasOwnProperty(e)}var jw=class{constructor(e=!1,t=!1){this._isReadonly=e,this._isShallow=t}get(e,t,n){if(t===`__v_skip`)return e.__v_skip;let r=this._isReadonly,i=this._isShallow;if(t===`__v_isReactive`)return!r;if(t===`__v_isReadonly`)return r;if(t===`__v_isShallow`)return i;if(t===`__v_raw`)return n===(r?i?Zw:Xw:i?Yw:Jw).get(e)||Object.getPrototypeOf(e)===Object.getPrototypeOf(n)?e:void 0;let a=xC(e);if(!r){let e;if(a&&(e=xw[t]))return e;if(t===`hasOwnProperty`)return Aw}let o=Reflect.get(e,t,fT(e)?e:n);if((EC(t)?kw.has(t):Ow(t))||(r||hw(e,`get`,t),i))return o;if(fT(o)){let e=a&&MC(t)?o:o.value;return r&&DC(e)?nT(e):e}return DC(o)?r?nT(o):eT(o):o}},Mw=class extends jw{constructor(e=!1){super(!1,e)}set(e,t,n,r){let i=e[t],a=xC(e)&&MC(t);if(!this._isShallow){let e=oT(i);if(!sT(n)&&!oT(n)&&(i=G(i),n=G(n)),!a&&fT(i)&&!fT(n))return e||(i.value=n),!0}let o=a?Number(t)<e.length:bC(e,t),s=Reflect.set(e,t,n,fT(e)?e:r);return e===G(r)&&(o?NC(n,i)&&gw(e,`set`,t,n,i):gw(e,`add`,t,n)),s}deleteProperty(e,t){let n=bC(e,t),r=e[t],i=Reflect.deleteProperty(e,t);return i&&n&&gw(e,`delete`,t,void 0,r),i}has(e,t){let n=Reflect.has(e,t);return(!EC(t)||!kw.has(t))&&hw(e,`has`,t),n}ownKeys(e){return hw(e,`iterate`,xC(e)?`length`:fw),Reflect.ownKeys(e)}},Nw=class extends jw{constructor(e=!1){super(!0,e)}set(e,t){return!0}deleteProperty(e,t){return!0}},Pw=new Mw,Fw=new Nw,Iw=new Mw(!0),Lw=new Nw(!0),Rw=e=>e,zw=e=>Reflect.getPrototypeOf(e);function Bw(e,t,n){return function(...r){let i=this.__v_raw,a=G(i),o=SC(a),s=e===`entries`||e===Symbol.iterator&&o,c=e===`keys`&&o,l=i[e](...r),u=n?Rw:t?dT:uT;return!t&&hw(a,`iterate`,c?pw:fw),_C(Object.create(l),{next(){let{value:e,done:t}=l.next();return t?{value:e,done:t}:{value:s?[u(e[0]),u(e[1])]:u(e),done:t}}})}}function Vw(e){return function(...t){return e===`delete`?!1:e===`clear`?void 0:this}}function Hw(e,t){let n={get(n){let r=this.__v_raw,i=G(r),a=G(n);e||(NC(n,a)&&hw(i,`get`,n),hw(i,`get`,a));let{has:o}=zw(i),s=t?Rw:e?dT:uT;if(o.call(i,n))return s(r.get(n));if(o.call(i,a))return s(r.get(a));r!==i&&r.get(n)},get size(){let t=this.__v_raw;return!e&&hw(G(t),`iterate`,fw),t.size},has(t){let n=this.__v_raw,r=G(n),i=G(t);return e||(NC(t,i)&&hw(r,`has`,t),hw(r,`has`,i)),t===i?n.has(t):n.has(t)||n.has(i)},forEach(n,r){let i=this,a=i.__v_raw,o=G(a),s=t?Rw:e?dT:uT;return!e&&hw(o,`iterate`,fw),a.forEach((e,t)=>n.call(r,s(e),s(t),i))}};return _C(n,e?{add:Vw(`add`),set:Vw(`set`),delete:Vw(`delete`),clear:Vw(`clear`)}:{add(e){let n=G(this),r=zw(n),i=G(e),a=!t&&!sT(e)&&!oT(e)?i:e;return r.has.call(n,a)||NC(e,a)&&r.has.call(n,e)||NC(i,a)&&r.has.call(n,i)||(n.add(a),gw(n,`add`,a,a)),this},set(e,n){!t&&!sT(n)&&!oT(n)&&(n=G(n));let r=G(this),{has:i,get:a}=zw(r),o=i.call(r,e);o||=(e=G(e),i.call(r,e));let s=a.call(r,e);return r.set(e,n),o?NC(n,s)&&gw(r,`set`,e,n,s):gw(r,`add`,e,n),this},delete(e){let t=G(this),{has:n,get:r}=zw(t),i=n.call(t,e);i||=(e=G(e),n.call(t,e));let a=r?r.call(t,e):void 0,o=t.delete(e);return i&&gw(t,`delete`,e,void 0,a),o},clear(){let e=G(this),t=e.size!==0,n=e.clear();return t&&gw(e,`clear`,void 0,void 0,void 0),n}}),[`keys`,`values`,`entries`,Symbol.iterator].forEach(r=>{n[r]=Bw(r,e,t)}),n}function Uw(e,t){let n=Hw(e,t);return(t,r,i)=>r===`__v_isReactive`?!e:r===`__v_isReadonly`?e:r===`__v_raw`?t:Reflect.get(bC(n,r)&&r in t?n:t,r,i)}var Ww={get:Uw(!1,!1)},Gw={get:Uw(!1,!0)},Kw={get:Uw(!0,!1)},qw={get:Uw(!0,!0)},Jw=new WeakMap,Yw=new WeakMap,Xw=new WeakMap,Zw=new WeakMap;function Qw(e){switch(e){case`Object`:case`Array`:return 1;case`Map`:case`Set`:case`WeakMap`:case`WeakSet`:return 2;default:return 0}}function $w(e){return e.__v_skip||!Object.isExtensible(e)?0:Qw(AC(e))}function eT(e){return oT(e)?e:iT(e,!1,Pw,Ww,Jw)}function tT(e){return iT(e,!1,Iw,Gw,Yw)}function nT(e){return iT(e,!0,Fw,Kw,Xw)}function rT(e){return iT(e,!0,Lw,qw,Zw)}function iT(e,t,n,r,i){if(!DC(e)||e.__v_raw&&!(t&&e.__v_isReactive))return e;let a=$w(e);if(a===0)return e;let o=i.get(e);if(o)return o;let s=new Proxy(e,a===2?r:n);return i.set(e,s),s}function aT(e){return oT(e)?aT(e.__v_raw):!!(e&&e.__v_isReactive)}function oT(e){return!!(e&&e.__v_isReadonly)}function sT(e){return!!(e&&e.__v_isShallow)}function cT(e){return e?!!e.__v_raw:!1}function G(e){let t=e&&e.__v_raw;return t?G(t):e}function lT(e){return!bC(e,`__v_skip`)&&Object.isExtensible(e)&&PC(e,`__v_skip`,!0),e}var uT=e=>DC(e)?eT(e):e,dT=e=>DC(e)?nT(e):e;function fT(e){return e?e.__v_isRef===!0:!1}function pT(e){return hT(e,!1)}function mT(e){return hT(e,!0)}function hT(e,t){return fT(e)?e:new gT(e,t)}var gT=class{constructor(e,t){this.dep=new lw,this.__v_isRef=!0,this.__v_isShallow=!1,this._rawValue=t?e:G(e),this._value=t?e:uT(e),this.__v_isShallow=t}get value(){return this.dep.track(),this._value}set value(e){let t=this._rawValue,n=this.__v_isShallow||sT(e)||oT(e);e=n?e:G(e),NC(e,t)&&(this._rawValue=e,this._value=n?e:uT(e),this.dep.trigger())}};function _T(e){e.dep&&e.dep.trigger()}function vT(e){return fT(e)?e.value:e}function yT(e){return wC(e)?e():vT(e)}var bT={get:(e,t,n)=>t===`__v_raw`?e:vT(Reflect.get(e,t,n)),set:(e,t,n,r)=>{let i=e[t];return fT(i)&&!fT(n)?(i.value=n,!0):Reflect.set(e,t,n,r)}};function xT(e){return aT(e)?e:new Proxy(e,bT)}var ST=class{constructor(e){this.__v_isRef=!0,this._value=void 0;let t=this.dep=new lw,{get:n,set:r}=e(t.track.bind(t),t.trigger.bind(t));this._get=n,this._set=r}get value(){return this._value=this._get()}set value(e){this._set(e)}};function CT(e){return new ST(e)}function wT(e){let t=xC(e)?Array(e.length):{};for(let n in e)t[n]=OT(e,n);return t}var TT=class{constructor(e,t,n){this._object=e,this._defaultValue=n,this.__v_isRef=!0,this._value=void 0,this._key=EC(t)?t:String(t),this._raw=G(e);let r=!0,i=e;if(!xC(e)||EC(this._key)||!MC(this._key))do r=!cT(i)||sT(i);while(r&&(i=i.__v_raw));this._shallow=r}get value(){let e=this._object[this._key];return this._shallow&&(e=vT(e)),this._value=e===void 0?this._defaultValue:e}set value(e){if(this._shallow&&fT(this._raw[this._key])){let t=this._object[this._key];if(fT(t)){t.value=e;return}}this._object[this._key]=e}get dep(){return _w(this._raw,this._key)}},ET=class{constructor(e){this._getter=e,this.__v_isRef=!0,this.__v_isReadonly=!0,this._value=void 0}get value(){return this._value=this._getter()}};function DT(e,t,n){return fT(e)?e:wC(e)?new ET(e):DC(e)&&arguments.length>1?OT(e,t,n):pT(e)}function OT(e,t,n){return new TT(e,t,n)}var kT=class{constructor(e,t,n){this.fn=e,this.setter=t,this._value=void 0,this.dep=new lw(this),this.__v_isRef=!0,this.deps=void 0,this.depsTail=void 0,this.flags=16,this.globalVersion=sw-1,this.next=void 0,this.effect=this,this.__v_isReadonly=!t,this.isSSR=n}notify(){if(this.flags|=16,!(this.flags&8)&&W!==this)return GC(this,!0),!0}get value(){let e=this.dep.track();return ZC(this),e&&(e.version=this.dep.version),this._value}set value(e){this.setter&&this.setter(e)}};function AT(e,t,n=!1){let r,i;return wC(e)?r=e:(r=e.get,i=e.set),new kT(r,i,n)}var jT={GET:`get`,HAS:`has`,ITERATE:`iterate`},MT={SET:`set`,ADD:`add`,DELETE:`delete`,CLEAR:`clear`},NT={},PT=new WeakMap,FT=void 0;function IT(){return FT}function LT(e,t=!1,n=FT){if(n){let t=PT.get(n);t||PT.set(n,t=[]),t.push(e)}}function RT(e,t,n=hC){let{immediate:r,deep:i,once:a,scheduler:o,augmentJob:s,call:c}=n,l=e=>i?e:sT(e)||i===!1||i===0?zT(e,1):zT(e),u,d,f,p,m=!1,h=!1;if(fT(e)?(d=()=>e.value,m=sT(e)):aT(e)?(d=()=>l(e),m=!0):xC(e)?(h=!0,m=e.some(e=>aT(e)||sT(e)),d=()=>e.map(e=>{if(fT(e))return e.value;if(aT(e))return l(e);if(wC(e))return c?c(e,2):e()})):d=wC(e)?t?c?()=>c(e,2):e:()=>{if(f){iw();try{f()}finally{aw()}}let t=FT;FT=u;try{return c?c(e,3,[p]):e(p)}finally{FT=t}}:gC,t&&i){let e=d,t=i===!0?1/0:i;d=()=>zT(e(),t)}let g=RC(),_=()=>{u.stop(),g&&g.active&&vC(g.effects,u)};if(a&&t){let e=t;t=(...t)=>{e(...t),_()}}let v=h?Array(e.length).fill(NT):NT,y=e=>{if(!(!(u.flags&1)||!u.dirty&&!e))if(t){let e=u.run();if(i||m||(h?e.some((e,t)=>NC(e,v[t])):NC(e,v))){f&&f();let n=FT;FT=u;try{let n=[e,v===NT?void 0:h&&v[0]===NT?[]:v,p];v=e,c?c(t,3,n):t(...n)}finally{FT=n}}}else u.run()};return s&&s(y),u=new VC(d),u.scheduler=o?()=>o(y,!1):y,p=e=>LT(e,!1,u),f=u.onStop=()=>{let e=PT.get(u);if(e){if(c)c(e,4);else for(let t of e)t();PT.delete(u)}},t?r?y(!0):v=u.run():o?o(y.bind(null,!0),!0):u.run(),_.pause=u.pause.bind(u),_.resume=u.resume.bind(u),_.stop=_,_}function zT(e,t=1/0,n){if(t<=0||!DC(e)||e.__v_skip||(n||=new Map,(n.get(e)||0)>=t))return e;if(n.set(e,t),t--,fT(e))zT(e.value,t,n);else if(xC(e))for(let r=0;r<e.length;r++)zT(e[r],t,n);else if(CC(e)||SC(e))e.forEach(e=>{zT(e,t,n)});else if(jC(e)){for(let r in e)zT(e[r],t,n);for(let r of Object.getOwnPropertySymbols(e))Object.prototype.propertyIsEnumerable.call(e,r)&&zT(e[r],t,n)}return e}function BT(e){let t=Object.create(null);for(let n of e.split(`,`))t[n]=1;return e=>e in t}var K={},VT=[],HT=()=>{},UT=()=>!1,WT=e=>e.charCodeAt(0)===111&&e.charCodeAt(1)===110&&(e.charCodeAt(2)>122||e.charCodeAt(2)<97),GT=e=>e.startsWith(`onUpdate:`),KT=Object.assign,qT=(e,t)=>{let n=e.indexOf(t);n>-1&&e.splice(n,1)},JT=Object.prototype.hasOwnProperty,q=(e,t)=>JT.call(e,t),J=Array.isArray,YT=e=>iE(e)===`[object Map]`,XT=e=>iE(e)===`[object Set]`,ZT=e=>iE(e)===`[object Date]`,QT=e=>iE(e)===`[object RegExp]`,Y=e=>typeof e==`function`,$T=e=>typeof e==`string`,eE=e=>typeof e==`symbol`,tE=e=>typeof e==`object`&&!!e,nE=e=>(tE(e)||Y(e))&&Y(e.then)&&Y(e.catch),rE=Object.prototype.toString,iE=e=>rE.call(e),aE=e=>iE(e)===`[object Object]`,oE=BT(`,key,ref,ref_for,ref_key,onVnodeBeforeMount,onVnodeMounted,onVnodeBeforeUpdate,onVnodeUpdated,onVnodeBeforeUnmount,onVnodeUnmounted`),sE=e=>{let t=Object.create(null);return(n=>t[n]||(t[n]=e(n)))},cE=/-\w/g,lE=sE(e=>e.replace(cE,e=>e.slice(1).toUpperCase())),uE=/\B([A-Z])/g,dE=sE(e=>e.replace(uE,`-$1`).toLowerCase()),fE=sE(e=>e.charAt(0).toUpperCase()+e.slice(1)),pE=sE(e=>e?`on${fE(e)}`:``),mE=(e,t)=>!Object.is(e,t),hE=(e,...t)=>{for(let n=0;n<e.length;n++)e[n](...t)},gE=(e,t,n,r=!1)=>{Object.defineProperty(e,t,{configurable:!0,enumerable:!1,writable:r,value:n})},_E=e=>{let t=parseFloat(e);return isNaN(t)?e:t},vE=e=>{let t=$T(e)?Number(e):NaN;return isNaN(t)?e:t},yE,bE=()=>yE||=typeof globalThis<`u`?globalThis:typeof self<`u`?self:typeof window<`u`?window:typeof global<`u`?global:{},xE=BT(`Infinity,undefined,NaN,isFinite,isNaN,parseFloat,parseInt,decodeURI,decodeURIComponent,encodeURI,encodeURIComponent,Math,Number,Date,Array,Object,Boolean,String,RegExp,Map,Set,JSON,Intl,BigInt,console,Error,Symbol`);function SE(e){if(J(e)){let t={};for(let n=0;n<e.length;n++){let r=e[n],i=$T(r)?EE(r):SE(r);if(i)for(let e in i)t[e]=i[e]}return t}else if($T(e)||tE(e))return e}var CE=/;(?![^(]*\))/g,wE=/:([^]+)/,TE=/\/\*[^]*?\*\//g;function EE(e){let t={};return e.replace(TE,``).split(CE).forEach(e=>{if(e){let n=e.split(wE);n.length>1&&(t[n[0].trim()]=n[1].trim())}}),t}function DE(e){let t=``;if($T(e))t=e;else if(J(e))for(let n=0;n<e.length;n++){let r=DE(e[n]);r&&(t+=r+` `)}else if(tE(e))for(let n in e)e[n]&&(t+=n+` `);return t.trim()}function OE(e){if(!e)return null;let{class:t,style:n}=e;return t&&!$T(t)&&(e.class=DE(t)),n&&(e.style=SE(n)),e}function kE(e,t){if(e.length!==t.length)return!1;let n=!0;for(let r=0;n&&r<e.length;r++)n=AE(e[r],t[r]);return n}function AE(e,t){if(e===t)return!0;let n=ZT(e),r=ZT(t);if(n||r)return n&&r?e.getTime()===t.getTime():!1;if(n=eE(e),r=eE(t),n||r)return e===t;if(n=J(e),r=J(t),n||r)return n&&r?kE(e,t):!1;if(n=tE(e),r=tE(t),n||r){if(!n||!r||Object.keys(e).length!==Object.keys(t).length)return!1;for(let n in e){let r=e.hasOwnProperty(n),i=t.hasOwnProperty(n);if(r&&!i||!r&&i||!AE(e[n],t[n]))return!1}}return String(e)===String(t)}var jE=e=>!!(e&&e.__v_isRef===!0),ME=e=>$T(e)?e:e==null?``:J(e)||tE(e)&&(e.toString===rE||!Y(e.toString))?jE(e)?ME(e.value):JSON.stringify(e,NE,2):String(e),NE=(e,t)=>jE(t)?NE(e,t.value):YT(t)?{[`Map(${t.size})`]:[...t.entries()].reduce((e,[t,n],r)=>(e[PE(t,r)+` =>`]=n,e),{})}:XT(t)?{[`Set(${t.size})`]:[...t.values()].map(e=>PE(e))}:eE(t)?PE(t):tE(t)&&!J(t)&&!aE(t)?String(t):t,PE=(e,t=``)=>eE(e)?`Symbol(${e.description??t})`:e,FE=[];function IE(e){FE.push(e)}function LE(){FE.pop()}function RE(e,t){}var zE={SETUP_FUNCTION:0,0:`SETUP_FUNCTION`,RENDER_FUNCTION:1,1:`RENDER_FUNCTION`,NATIVE_EVENT_HANDLER:5,5:`NATIVE_EVENT_HANDLER`,COMPONENT_EVENT_HANDLER:6,6:`COMPONENT_EVENT_HANDLER`,VNODE_HOOK:7,7:`VNODE_HOOK`,DIRECTIVE_HOOK:8,8:`DIRECTIVE_HOOK`,TRANSITION_HOOK:9,9:`TRANSITION_HOOK`,APP_ERROR_HANDLER:10,10:`APP_ERROR_HANDLER`,APP_WARN_HANDLER:11,11:`APP_WARN_HANDLER`,FUNCTION_REF:12,12:`FUNCTION_REF`,ASYNC_COMPONENT_LOADER:13,13:`ASYNC_COMPONENT_LOADER`,SCHEDULER:14,14:`SCHEDULER`,COMPONENT_UPDATE:15,15:`COMPONENT_UPDATE`,APP_UNMOUNT_CLEANUP:16,16:`APP_UNMOUNT_CLEANUP`},BE={sp:`serverPrefetch hook`,bc:`beforeCreate hook`,c:`created hook`,bm:`beforeMount hook`,m:`mounted hook`,bu:`beforeUpdate hook`,u:`updated`,bum:`beforeUnmount hook`,um:`unmounted hook`,a:`activated hook`,da:`deactivated hook`,ec:`errorCaptured hook`,rtc:`renderTracked hook`,rtg:`renderTriggered hook`,0:`setup function`,1:`render function`,2:`watcher getter`,3:`watcher callback`,4:`watcher cleanup function`,5:`native event handler`,6:`component event handler`,7:`vnode hook`,8:`directive hook`,9:`transition hook`,10:`app errorHandler`,11:`app warnHandler`,12:`ref function`,13:`async component loader`,14:`scheduler flush`,15:`component update`,16:`app unmount cleanup function`};function VE(e,t,n,r){try{return r?e(...r):e()}catch(e){UE(e,t,n)}}function HE(e,t,n,r){if(Y(e)){let i=VE(e,t,n,r);return i&&nE(i)&&i.catch(e=>{UE(e,t,n)}),i}if(J(e)){let i=[];for(let a=0;a<e.length;a++)i.push(HE(e[a],t,n,r));return i}}function UE(e,t,n,r=!0){let i=t?t.vnode:null,{errorHandler:a,throwUnhandledErrorInProduction:o}=t&&t.appContext.config||K;if(t){let r=t.parent,i=t.proxy,o=`https://vuejs.org/error-reference/#runtime-${n}`;for(;r;){let t=r.ec;if(t){for(let n=0;n<t.length;n++)if(t[n](e,i,o)===!1)return}r=r.parent}if(a){iw(),VE(a,null,10,[e,i,o]),aw();return}}WE(e,n,i,r,o)}function WE(e,t,n,r=!0,i=!1){if(i)throw e;console.error(e)}var GE=[],KE=-1,qE=[],JE=null,YE=0,XE=Promise.resolve(),ZE=null;function QE(e){let t=ZE||XE;return e?t.then(this?e.bind(this):e):t}function $E(e){let t=KE+1,n=GE.length;for(;t<n;){let r=t+n>>>1,i=GE[r],a=aD(i);a<e||a===e&&i.flags&2?t=r+1:n=r}return t}function eD(e){if(!(e.flags&1)){let t=aD(e),n=GE[GE.length-1];!n||!(e.flags&2)&&t>=aD(n)?GE.push(e):GE.splice($E(t),0,e),e.flags|=1,tD()}}function tD(){ZE||=XE.then(oD)}function nD(e){J(e)?qE.push(...e):JE&&e.id===-1?JE.splice(YE+1,0,e):e.flags&1||(qE.push(e),e.flags|=1),tD()}function rD(e,t,n=KE+1){for(;n<GE.length;n++){let t=GE[n];if(t&&t.flags&2){if(e&&t.id!==e.uid)continue;GE.splice(n,1),n--,t.flags&4&&(t.flags&=-2),t(),t.flags&4||(t.flags&=-2)}}}function iD(e){if(qE.length){let e=[...new Set(qE)].sort((e,t)=>aD(e)-aD(t));if(qE.length=0,JE){JE.push(...e);return}for(JE=e,YE=0;YE<JE.length;YE++){let e=JE[YE];e.flags&4&&(e.flags&=-2),e.flags&8||e(),e.flags&=-2}JE=null,YE=0}}var aD=e=>e.id==null?e.flags&2?-1:1/0:e.id;function oD(e){try{for(KE=0;KE<GE.length;KE++){let e=GE[KE];e&&!(e.flags&8)&&(e.flags&4&&(e.flags&=-2),VE(e,e.i,e.i?15:14),e.flags&4||(e.flags&=-2))}}finally{for(;KE<GE.length;KE++){let e=GE[KE];e&&(e.flags&=-2)}KE=-1,GE.length=0,iD(e),ZE=null,(GE.length||qE.length)&&oD(e)}}var sD,cD=[];function lD(e,t){sD=e,sD?(sD.enabled=!0,cD.forEach(({event:e,args:t})=>sD.emit(e,...t)),cD=[]):typeof window<`u`&&window.HTMLElement&&!(window.navigator?.userAgent)?.includes(`jsdom`)?((t.__VUE_DEVTOOLS_HOOK_REPLAY__=t.__VUE_DEVTOOLS_HOOK_REPLAY__||[]).push(e=>{lD(e,t)}),setTimeout(()=>{sD||(t.__VUE_DEVTOOLS_HOOK_REPLAY__=null,cD=[])},3e3)):cD=[]}var uD=null,dD=null;function fD(e){let t=uD;return uD=e,dD=e&&e.type.__scopeId||null,t}function pD(e){dD=e}function mD(){dD=null}var hD=e=>gD;function gD(e,t=uD,n){if(!t||e._n)return e;let r=(...n)=>{r._d&&dj(-1);let i=fD(t),a;try{a=e(...n)}finally{fD(i),r._d&&dj(1)}return a};return r._n=!0,r._c=!0,r._d=!0,r}function _D(e,t){if(uD===null)return e;let n=eM(uD),r=e.dirs||=[];for(let e=0;e<t.length;e++){let[i,a,o,s=K]=t[e];i&&(Y(i)&&(i={mounted:i,updated:i}),i.deep&&zT(a),r.push({dir:i,instance:n,value:a,oldValue:void 0,arg:o,modifiers:s}))}return e}function vD(e,t,n,r){let i=e.dirs,a=t&&t.dirs;for(let o=0;o<i.length;o++){let s=i[o];a&&(s.oldValue=a[o].value);let c=s.dir[r];c&&(iw(),HE(c,n,8,[e.el,s,e,t]),aw())}}function yD(e,t){if(Ij){let n=Ij.provides,r=Ij.parent&&Ij.parent.provides;r===n&&(n=Ij.provides=Object.create(r)),n[e]=t}}function bD(e,t,n=!1){let r=Lj();if(r||Qk){let i=Qk?Qk._context.provides:r?r.parent==null||r.ce?r.vnode.appContext&&r.vnode.appContext.provides:r.parent.provides:void 0;if(i&&e in i)return i[e];if(arguments.length>1)return n&&Y(t)?t.call(r&&r.proxy):t}}function xD(){return!!(Lj()||Qk)}var SD=Symbol.for(`v-scx`),CD=()=>bD(SD);function wD(e,t){return OD(e,null,t)}function TD(e,t){return OD(e,null,{flush:`post`})}function ED(e,t){return OD(e,null,{flush:`sync`})}function DD(e,t,n){return OD(e,t,n)}function OD(e,t,n=K){let{immediate:r,deep:i,flush:a,once:o}=n,s=KT({},n),c=t&&r||!t&&a!==`post`,l;if(Uj){if(a===`sync`){let e=CD();l=e.__watcherHandles||=[]}else if(!c){let e=()=>{};return e.stop=HT,e.resume=HT,e.pause=HT,e}}let u=Ij;s.call=(e,t,n)=>HE(e,u,t,n);let d=!1;a===`post`?s.scheduler=e=>{jA(e,u&&u.suspense)}:a!==`sync`&&(d=!0,s.scheduler=(e,t)=>{t?e():eD(e)}),s.augmentJob=e=>{t&&(e.flags|=4),d&&(e.flags|=2,u&&(e.id=u.uid,e.i=u))};let f=RT(e,t,s);return Uj&&(l?l.push(f):c&&f()),f}function kD(e,t,n){let r=this.proxy,i=$T(e)?e.includes(`.`)?AD(r,e):()=>r[e]:e.bind(r,r),a;Y(t)?a=t:(a=t.handler,n=t);let o=Bj(this),s=OD(i,a.bind(r),n);return o(),s}function AD(e,t){let n=t.split(`.`);return()=>{let t=e;for(let e=0;e<n.length&&t;e++)t=t[n[e]];return t}}var jD=Symbol(`_vte`),MD=e=>e.__isTeleport,ND=e=>e&&(e.disabled||e.disabled===``),PD=e=>e&&(e.defer||e.defer===``),FD=e=>typeof SVGElement<`u`&&e instanceof SVGElement,ID=e=>typeof MathMLElement==`function`&&e instanceof MathMLElement,LD=(e,t)=>{let n=e&&e.to;return $T(n)?t?t(n):null:n},RD={name:`Teleport`,__isTeleport:!0,process(e,t,n,r,i,a,o,s,c,l){let{mc:u,pc:d,pbc:f,o:{insert:p,querySelector:m,createText:h,createComment:g}}=l,_=ND(t.props),{shapeFlag:v,children:y,dynamicChildren:b}=t;if(e==null){let e=t.el=h(``),l=t.anchor=h(``);p(e,n,r),p(l,n,r);let d=(e,t)=>{v&16&&u(y,e,t,i,a,o,s,c)},f=()=>{let e=t.target=LD(t.props,m),n=UD(e,t,h,p);e&&(o!==`svg`&&FD(e)?o=`svg`:o!==`mathml`&&ID(e)&&(o=`mathml`),i&&i.isCE&&(i.ce._teleportTargets||(i.ce._teleportTargets=new Set)).add(e),_||(d(e,n),HD(t,!1)))};_&&(d(n,l),HD(t,!0)),PD(t.props)||a&&a.pendingBranch?(t.el.__isMounted=!1,jA(()=>{t.el.__isMounted===!1&&(f(),delete t.el.__isMounted)},a)):f()}else{t.el=e.el,t.targetStart=e.targetStart;let u=t.anchor=e.anchor,p=t.target=e.target,h=t.targetAnchor=e.targetAnchor;if(e.el.__isMounted===!1){jA(()=>{RD.process(e,t,n,r,i,a,o,s,c,l)},a);return}let g=ND(e.props),v=g?n:p,y=g?u:h;if(o===`svg`||FD(p)?o=`svg`:(o===`mathml`||ID(p))&&(o=`mathml`),b?(f(e.dynamicChildren,b,v,i,a,o,s),RA(e,t,!0)):c||d(e,t,v,y,i,a,o,s,!1),_)g?t.props&&e.props&&t.props.to!==e.props.to&&(t.props.to=e.props.to):zD(t,n,u,l,1);else if((t.props&&t.props.to)!==(e.props&&e.props.to)){let e=t.target=LD(t.props,m);e&&zD(t,e,null,l,0)}else g&&zD(t,p,h,l,1);HD(t,_)}},remove(e,t,n,{um:r,o:{remove:i}},a){let{shapeFlag:o,children:s,anchor:c,targetStart:l,targetAnchor:u,target:d,props:f}=e;if(d&&(i(l),i(u)),a&&i(c),o&16){let e=a||!ND(f);for(let i=0;i<s.length;i++){let a=s[i];r(a,t,n,e,!!a.dynamicChildren)}}},move:zD,hydrate:BD};function zD(e,t,n,{o:{insert:r},m:i},a=2){a===0&&r(e.targetAnchor,t,n);let{el:o,anchor:s,shapeFlag:c,children:l,props:u}=e,d=a===2;if(d&&r(o,t,n),(!d||ND(u))&&c&16)for(let e=0;e<l.length;e++)i(l[e],t,n,2);d&&r(s,t,n)}function BD(e,t,n,r,i,a,{o:{nextSibling:o,parentNode:s,querySelector:c,insert:l,createText:u}},d){function f(e,n){let r=n;for(;r;){if(r&&r.nodeType===8){if(r.data===`teleport start anchor`)t.targetStart=r;else if(r.data===`teleport anchor`){t.targetAnchor=r,e._lpa=t.targetAnchor&&o(t.targetAnchor);break}}r=o(r)}}function p(e,t){t.anchor=d(o(e),t,s(e),n,r,i,a)}let m=t.target=LD(t.props,c),h=ND(t.props);if(m){let c=m._lpa||m.firstChild;t.shapeFlag&16&&(h?(p(e,t),f(m,c),t.targetAnchor||UD(m,t,u,l,s(e)===m?e:null)):(t.anchor=o(e),f(m,c),t.targetAnchor||UD(m,t,u,l),d(c&&o(c),t,m,n,r,i,a))),HD(t,h)}else h&&t.shapeFlag&16&&(p(e,t),t.targetStart=e,t.targetAnchor=o(e));return t.anchor&&o(t.anchor)}var VD=RD;function HD(e,t){let n=e.ctx;if(n&&n.ut){let r,i;for(t?(r=e.el,i=e.anchor):(r=e.targetStart,i=e.targetAnchor);r&&r!==i;)r.nodeType===1&&r.setAttribute(`data-v-owner`,n.uid),r=r.nextSibling;n.ut()}}function UD(e,t,n,r,i=null){let a=t.targetStart=n(``),o=t.targetAnchor=n(``);return a[jD]=o,e&&(r(a,e,i),r(o,e,i)),o}var WD=Symbol(`_leaveCb`),GD=Symbol(`_enterCb`);function KD(){let e={isMounted:!1,isLeaving:!1,isUnmounting:!1,leavingVNodes:new Map};return KO(()=>{e.isMounted=!0}),YO(()=>{e.isUnmounting=!0}),e}var qD=[Function,Array],JD={mode:String,appear:Boolean,persisted:Boolean,onBeforeEnter:qD,onEnter:qD,onAfterEnter:qD,onEnterCancelled:qD,onBeforeLeave:qD,onLeave:qD,onAfterLeave:qD,onLeaveCancelled:qD,onBeforeAppear:qD,onAppear:qD,onAfterAppear:qD,onAppearCancelled:qD},YD=e=>{let t=e.subTree;return t.component?YD(t.component):t},XD={name:`BaseTransition`,props:JD,setup(e,{slots:t}){let n=Lj(),r=KD();return()=>{let i=t.default&&iO(t.default(),!0);if(!i||!i.length)return;let a=ZD(i),o=G(e),{mode:s}=o;if(r.isLeaving)return tO(a);let c=nO(a);if(!c)return tO(a);let l=eO(c,o,r,n,e=>l=e);c.type!==ij&&rO(c,l);let u=n.subTree&&nO(n.subTree);if(u&&u.type!==ij&&!gj(u,c)&&YD(n).type!==ij){let e=eO(u,o,r,n);if(rO(u,e),s===`out-in`&&c.type!==ij)return r.isLeaving=!0,e.afterLeave=()=>{r.isLeaving=!1,n.job.flags&8||n.update(),delete e.afterLeave,u=void 0},tO(a);s===`in-out`&&c.type!==ij?e.delayLeave=(e,t,n)=>{let i=$D(r,u);i[String(u.key)]=u,e[WD]=()=>{t(),e[WD]=void 0,delete l.delayedLeave,u=void 0},l.delayedLeave=()=>{n(),delete l.delayedLeave,u=void 0}}:u=void 0}else u&&=void 0;return a}}};function ZD(e){let t=e[0];if(e.length>1){for(let n of e)if(n.type!==ij){t=n;break}}return t}var QD=XD;function $D(e,t){let{leavingVNodes:n}=e,r=n.get(t.type);return r||(r=Object.create(null),n.set(t.type,r)),r}function eO(e,t,n,r,i){let{appear:a,mode:o,persisted:s=!1,onBeforeEnter:c,onEnter:l,onAfterEnter:u,onEnterCancelled:d,onBeforeLeave:f,onLeave:p,onAfterLeave:m,onLeaveCancelled:h,onBeforeAppear:g,onAppear:_,onAfterAppear:v,onAppearCancelled:y}=t,b=String(e.key),x=$D(n,e),S=(e,t)=>{e&&HE(e,r,9,t)},C=(e,t)=>{let n=t[1];S(e,t),J(e)?e.every(e=>e.length<=1)&&n():e.length<=1&&n()},w={mode:o,persisted:s,beforeEnter(t){let r=c;if(!n.isMounted)if(a)r=g||c;else return;t[WD]&&t[WD](!0);let i=x[b];i&&gj(e,i)&&i.el[WD]&&i.el[WD](),S(r,[t])},enter(t){if(x[b]===e)return;let r=l,i=u,o=d;if(!n.isMounted)if(a)r=_||l,i=v||u,o=y||d;else return;let s=!1;t[GD]=e=>{s||(s=!0,S(e?o:i,[t]),w.delayedLeave&&w.delayedLeave(),t[GD]=void 0)};let c=t[GD].bind(null,!1);r?C(r,[t,c]):c()},leave(t,r){let i=String(e.key);if(t[GD]&&t[GD](!0),n.isUnmounting)return r();S(f,[t]);let a=!1;t[WD]=n=>{a||(a=!0,r(),S(n?h:m,[t]),t[WD]=void 0,x[i]===e&&delete x[i])};let o=t[WD].bind(null,!1);x[i]=e,p?C(p,[t,o]):o()},clone(e){let a=eO(e,t,n,r,i);return i&&i(a),a}};return w}function tO(e){if(PO(e))return e=wj(e),e.children=null,e}function nO(e){if(!PO(e))return MD(e.type)&&e.children?ZD(e.children):e;if(e.component)return e.component.subTree;let{shapeFlag:t,children:n}=e;if(n){if(t&16)return n[0];if(t&32&&Y(n.default))return n.default()}}function rO(e,t){e.shapeFlag&6&&e.component?(e.transition=t,rO(e.component.subTree,t)):e.shapeFlag&128?(e.ssContent.transition=t.clone(e.ssContent),e.ssFallback.transition=t.clone(e.ssFallback)):e.transition=t}function iO(e,t=!1,n){let r=[],i=0;for(let a=0;a<e.length;a++){let o=e[a],s=n==null?o.key:String(n)+String(o.key==null?a:o.key);o.type===nj?(o.patchFlag&128&&i++,r=r.concat(iO(o.children,t,s))):(t||o.type!==ij)&&r.push(s==null?o:wj(o,{key:s}))}if(i>1)for(let e=0;e<r.length;e++)r[e].patchFlag=-2;return r}function aO(e,t){return Y(e)?KT({name:e.name},t,{setup:e}):e}function oO(){let e=Lj();return e?(e.appContext.config.idPrefix||`v`)+`-`+e.ids[0]+ e.ids[1]++:``}function sO(e){e.ids=[e.ids[0]+ e.ids[2]+++`-`,0,0]}function cO(e){let t=Lj(),n=mT(null);if(t){let r=t.refs===K?t.refs={}:t.refs;Object.defineProperty(r,e,{enumerable:!0,get:()=>n.value,set:e=>n.value=e})}return n}function lO(e,t){let n;return!!((n=Object.getOwnPropertyDescriptor(e,t))&&!n.configurable)}var uO=new WeakMap;function dO(e,t,n,r,i=!1){if(J(e)){e.forEach((e,a)=>dO(e,t&&(J(t)?t[a]:t),n,r,i));return}if(jO(r)&&!i){r.shapeFlag&512&&r.type.__asyncResolved&&r.component.subTree.component&&dO(e,t,n,r.component.subTree);return}let a=r.shapeFlag&4?eM(r.component):r.el,o=i?null:a,{i:s,r:c}=e,l=t&&t.r,u=s.refs===K?s.refs={}:s.refs,d=s.setupState,f=G(d),p=d===K?UT:e=>lO(u,e)?!1:q(f,e),m=(e,t)=>!(t&&lO(u,t));if(l!=null&&l!==c){if(fO(t),$T(l))u[l]=null,p(l)&&(d[l]=null);else if(fT(l)){let e=t;m(l,e.k)&&(l.value=null),e.k&&(u[e.k]=null)}}if(Y(c))VE(c,s,12,[o,u]);else{let t=$T(c),r=fT(c);if(t||r){let s=()=>{if(e.f){let n=t?p(c)?d[c]:u[c]:m(c)||!e.k?c.value:u[e.k];if(i)J(n)&&qT(n,a);else if(J(n))n.includes(a)||n.push(a);else if(t)u[c]=[a],p(c)&&(d[c]=u[c]);else{let t=[a];m(c,e.k)&&(c.value=t),e.k&&(u[e.k]=t)}}else t?(u[c]=o,p(c)&&(d[c]=o)):r&&(m(c,e.k)&&(c.value=o),e.k&&(u[e.k]=o))};if(o){let t=()=>{s(),uO.delete(e)};t.id=-1,uO.set(e,t),jA(t,n)}else fO(e),s()}}}function fO(e){let t=uO.get(e);t&&(t.flags|=8,uO.delete(e))}var pO=!1,mO=()=>{pO||=(console.error(`Hydration completed but contains mismatches.`),!0)},hO=e=>e.namespaceURI.includes(`svg`)&&e.tagName!==`foreignObject`,gO=e=>e.namespaceURI.includes(`MathML`),_O=e=>{if(e.nodeType===1){if(hO(e))return`svg`;if(gO(e))return`mathml`}},vO=e=>e.nodeType===8;function yO(e){let{mt:t,p:n,o:{patchProp:r,createText:i,nextSibling:a,parentNode:o,remove:s,insert:c,createComment:l}}=e,u=(e,t)=>{if(!t.hasChildNodes()){n(null,e,t),iD(),t._vnode=e;return}d(t.firstChild,e,null,null,null),iD(),t._vnode=e},d=(n,r,s,l,u,y=!1)=>{y||=!!r.dynamicChildren;let b=vO(n)&&n.data===`[`,x=()=>h(n,r,s,l,u,b),{type:S,ref:C,shapeFlag:w,patchFlag:T}=r,E=n.nodeType;r.el=n,T===-2&&(y=!1,r.dynamicChildren=null);let D=null;switch(S){case rj:E===3?(n.data!==r.children&&(mO(),n.data=r.children),D=a(n)):r.children===``?(c(r.el=i(``),o(n),n),D=n):D=x();break;case ij:v(n)?(D=a(n),_(r.el=n.content.firstChild,n,s)):D=E!==8||b?x():a(n);break;case aj:if(b&&(n=a(n),E=n.nodeType),E===1||E===3){D=n;let e=!r.children.length;for(let t=0;t<r.staticCount;t++)e&&(r.children+=D.nodeType===1?D.outerHTML:D.data),t===r.staticCount-1&&(r.anchor=D),D=a(D);return b?a(D):D}else x();break;case nj:D=b?m(n,r,s,l,u,y):x();break;default:if(w&1)D=(E!==1||r.type.toLowerCase()!==n.tagName.toLowerCase())&&!v(n)?x():f(n,r,s,l,u,y);else if(w&6){r.slotScopeIds=u;let e=o(n);if(D=b?g(n):vO(n)&&n.data===`teleport start`?g(n,n.data,`teleport end`):a(n),t(r,e,null,s,l,_O(e),y),jO(r)&&!r.type.__asyncResolved){let t;b?(t=xj(nj),t.anchor=D?D.previousSibling:e.lastChild):t=n.nodeType===3?Tj(``):xj(`div`),t.el=n,r.component.subTree=t}}else w&64?D=E===8?r.type.hydrate(n,r,s,l,u,y,e,p):x():w&128&&(D=r.type.hydrate(n,r,s,l,_O(o(n)),u,y,e,d))}return C!=null&&dO(C,null,l,r),D},f=(e,t,n,i,a,o)=>{o||=!!t.dynamicChildren;let{type:c,props:l,patchFlag:u,shapeFlag:d,dirs:f,transition:m}=t,h=c===`input`||c===`option`;if(h||u!==-1){f&&vD(t,null,n,`created`);let c=!1;if(v(e)){c=LA(null,m)&&n&&n.vnode.props&&n.vnode.props.appear;let r=e.content.firstChild;if(c){let e=r.getAttribute(`class`);e&&(r.$cls=e),m.beforeEnter(r)}_(r,e,n),t.el=e=r}if(d&16&&!(l&&(l.innerHTML||l.textContent))){let r=p(e.firstChild,t,e,n,i,a,o);for(;r;){SO(e,1)||mO();let t=r;r=r.nextSibling,s(t)}}else if(d&8){let n=t.children;n[0]===`
`&&(e.tagName===`PRE`||e.tagName===`TEXTAREA`)&&(n=n.slice(1));let{textContent:r}=e;r!==n&&r!==n.replace(/\r\n|\r/g,`
`)&&(SO(e,0)||mO(),e.textContent=t.children)}if(l){if(h||!o||u&48){let t=e.tagName.includes(`-`);for(let i in l)(h&&(i.endsWith(`value`)||i===`indeterminate`)||WT(i)&&!oE(i)||i[0]===`.`||t&&!oE(i))&&r(e,i,null,l[i],void 0,n)}else if(l.onClick)r(e,`onClick`,null,l.onClick,void 0,n);else if(u&4&&aT(l.style))for(let e in l.style)l.style[e]}let g;(g=l&&l.onVnodeBeforeMount)&&Mj(g,n,t),f&&vD(t,null,n,`beforeMount`),((g=l&&l.onVnodeMounted)||f||c)&&$A(()=>{g&&Mj(g,n,t),c&&m.enter(e),f&&vD(t,null,n,`mounted`)},i)}return e.nextSibling},p=(e,t,r,o,s,l,u)=>{u||=!!t.dynamicChildren;let f=t.children,p=f.length;for(let t=0;t<p;t++){let m=u?f[t]:f[t]=Oj(f[t]),h=m.type===rj;e?(h&&!u&&t+1<p&&Oj(f[t+1]).type===rj&&(c(i(e.data.slice(m.children.length)),r,a(e)),e.data=m.children),e=d(e,m,o,s,l,u)):h&&!m.children?c(m.el=i(``),r):(SO(r,1)||mO(),n(null,m,r,null,o,s,_O(r),l))}return e},m=(e,t,n,r,i,s)=>{let{slotScopeIds:u}=t;u&&(i=i?i.concat(u):u);let d=o(e),f=p(a(e),t,d,n,r,i,s);return f&&vO(f)&&f.data===`]`?a(t.anchor=f):(mO(),c(t.anchor=l(`]`),d,f),f)},h=(e,t,r,i,c,l)=>{if(SO(e.parentElement,1)||mO(),t.el=null,l){let t=g(e);for(;;){let n=a(e);if(n&&n!==t)s(n);else break}}let u=a(e),d=o(e);return s(e),n(null,t,d,u,r,i,_O(d),c),r&&(r.vnode.el=t.el,fA(r,t.el)),u},g=(e,t=`[`,n=`]`)=>{let r=0;for(;e;)if(e=a(e),e&&vO(e)&&(e.data===t&&r++,e.data===n)){if(r===0)return a(e);r--}return e},_=(e,t,n)=>{let r=t.parentNode;r&&r.replaceChild(e,t);let i=n;for(;i;)i.vnode.el===t&&(i.vnode.el=i.subTree.el=e),i=i.parent},v=e=>e.nodeType===1&&e.tagName===`TEMPLATE`;return[u,d]}var bO=`data-allow-mismatch`,xO={0:`text`,1:`children`,2:`class`,3:`style`,4:`attribute`};function SO(e,t){if(t===0||t===1)for(;e&&!e.hasAttribute(bO);)e=e.parentElement;let n=e&&e.getAttribute(bO);if(n==null)return!1;if(n===``)return!0;{let e=n.split(`,`);return t===0&&e.includes(`children`)?!0:e.includes(xO[t])}}var CO=bE().requestIdleCallback||(e=>setTimeout(e,1)),wO=bE().cancelIdleCallback||(e=>clearTimeout(e)),TO=(e=1e4)=>t=>{let n=CO(t,{timeout:e});return()=>wO(n)};function EO(e){let{top:t,left:n,bottom:r,right:i}=e.getBoundingClientRect(),{innerHeight:a,innerWidth:o}=window;return(t>0&&t<a||r>0&&r<a)&&(n>0&&n<o||i>0&&i<o)}var DO=e=>(t,n)=>{let r=new IntersectionObserver(e=>{for(let n of e)if(n.isIntersecting){r.disconnect(),t();break}},e);return n(e=>{if(e instanceof Element){if(EO(e))return t(),r.disconnect(),!1;r.observe(e)}}),()=>r.disconnect()},OO=e=>t=>{if(e){let n=matchMedia(e);if(n.matches)t();else return n.addEventListener(`change`,t,{once:!0}),()=>n.removeEventListener(`change`,t)}},kO=(e=[])=>(t,n)=>{$T(e)&&(e=[e]);let r=!1,i=e=>{r||(r=!0,a(),t(),e.target.dispatchEvent(new e.constructor(e.type,e)))},a=()=>{n(t=>{for(let n of e)t.removeEventListener(n,i)})};return n(t=>{for(let n of e)t.addEventListener(n,i,{once:!0})}),a};function AO(e,t){if(vO(e)&&e.data===`[`){let n=1,r=e.nextSibling;for(;r;){if(r.nodeType===1){if(t(r)===!1)break}else if(vO(r))if(r.data===`]`){if(--n===0)break}else r.data===`[`&&n++;r=r.nextSibling}}else t(e)}var jO=e=>!!e.type.__asyncLoader;function MO(e){Y(e)&&(e={loader:e});let{loader:t,loadingComponent:n,errorComponent:r,delay:i=200,hydrate:a,timeout:o,suspensible:s=!0,onError:c}=e,l=null,u,d=0,f=()=>(d++,l=null,p()),p=()=>{let e;return l||(e=l=t().catch(e=>{if(e=e instanceof Error?e:Error(String(e)),c)return new Promise((t,n)=>{c(e,()=>t(f()),()=>n(e),d+1)});throw e}).then(t=>e!==l&&l?l:(t&&(t.__esModule||t[Symbol.toStringTag]===`Module`)&&(t=t.default),u=t,t)))};return aO({name:`AsyncComponentWrapper`,__asyncLoader:p,__asyncHydrate(e,t,n){let r=!1;(t.bu||=[]).push(()=>r=!0);let i=()=>{r||n()},o=a?()=>{let n=a(i,t=>AO(e,t));n&&(t.bum||=[]).push(n)}:i;u?o():p().then(()=>!t.isUnmounted&&o())},get __asyncResolved(){return u},setup(){let e=Ij;if(sO(e),u)return()=>NO(u,e);let t=t=>{l=null,UE(t,e,13,!r)};if(s&&e.suspense||Uj)return p().then(t=>()=>NO(t,e)).catch(e=>(t(e),()=>r?xj(r,{error:e}):null));let a=pT(!1),c=pT(),d=pT(!!i);return i&&setTimeout(()=>{d.value=!1},i),o!=null&&setTimeout(()=>{if(!a.value&&!c.value){let e=Error(`Async component timed out after ${o}ms.`);t(e),c.value=e}},o),p().then(()=>{a.value=!0,e.parent&&PO(e.parent.vnode)&&e.parent.update()}).catch(e=>{t(e),c.value=e}),()=>{if(a.value&&u)return NO(u,e);if(c.value&&r)return xj(r,{error:c.value});if(n&&!d.value)return NO(n,e)}}})}function NO(e,t){let{ref:n,props:r,children:i,ce:a}=t.vnode,o=xj(e,r,i);return o.ref=n,o.ce=a,delete t.vnode.ce,o}var PO=e=>e.type.__isKeepAlive,FO={name:`KeepAlive`,__isKeepAlive:!0,props:{include:[String,RegExp,Array],exclude:[String,RegExp,Array],max:[String,Number]},setup(e,{slots:t}){let n=Lj(),r=n.ctx;if(!r.renderer)return()=>{let e=t.default&&t.default();return e&&e.length===1?e[0]:e};let i=new Map,a=new Set,o=null,s=n.suspense,{renderer:{p:c,m:l,um:u,o:{createElement:d}}}=r,f=d(`div`);r.activate=(e,t,n,r,i)=>{let a=e.component;l(e,t,n,0,s),c(a.vnode,e,t,n,a,s,r,e.slotScopeIds,i),jA(()=>{a.isDeactivated=!1,a.a&&hE(a.a);let t=e.props&&e.props.onVnodeMounted;t&&Mj(t,a.parent,e)},s)},r.deactivate=e=>{let t=e.component;VA(t.m),VA(t.a),l(e,f,null,1,s),jA(()=>{t.da&&hE(t.da);let n=e.props&&e.props.onVnodeUnmounted;n&&Mj(n,t.parent,e),t.isDeactivated=!0},s)};function p(e){VO(e),u(e,n,s,!0)}function m(e){i.forEach((t,n)=>{let r=tM(jO(t)?t.type.__asyncResolved||{}:t.type);r&&!e(r)&&h(n)})}function h(e){let t=i.get(e);t&&(!o||!gj(t,o))?p(t):o&&VO(o),i.delete(e),a.delete(e)}DD(()=>[e.include,e.exclude],([e,t])=>{e&&m(t=>IO(e,t)),t&&m(e=>!IO(t,e))},{flush:`post`,deep:!0});let g=null,_=()=>{g!=null&&(UA(n.subTree.type)?jA(()=>{i.set(g,HO(n.subTree))},n.subTree.suspense):i.set(g,HO(n.subTree)))};return KO(_),JO(_),YO(()=>{i.forEach(e=>{let{subTree:t,suspense:r}=n,i=HO(t);if(e.type===i.type&&e.key===i.key){VO(i);let e=i.component.da;e&&jA(e,r);return}p(e)})}),()=>{if(g=null,!t.default)return o=null;let n=t.default(),r=n[0];if(n.length>1)return o=null,n;if(!hj(r)||!(r.shapeFlag&4)&&!(r.shapeFlag&128))return o=null,r;let s=HO(r);if(s.type===ij)return o=null,s;let c=s.type,l=tM(jO(s)?s.type.__asyncResolved||{}:c),{include:u,exclude:d,max:f}=e;if(u&&(!l||!IO(u,l))||d&&l&&IO(d,l))return s.shapeFlag&=-257,o=s,r;let p=s.key==null?c:s.key,m=i.get(p);return s.el&&(s=wj(s),r.shapeFlag&128&&(r.ssContent=s)),g=p,m?(s.el=m.el,s.component=m.component,s.transition&&rO(s,s.transition),s.shapeFlag|=512,a.delete(p),a.add(p)):(a.add(p),f&&a.size>parseInt(f,10)&&h(a.values().next().value)),s.shapeFlag|=256,o=s,UA(r.type)?r:s}}};function IO(e,t){return J(e)?e.some(e=>IO(e,t)):$T(e)?e.split(`,`).includes(t):QT(e)?(e.lastIndex=0,e.test(t)):!1}function LO(e,t){zO(e,`a`,t)}function RO(e,t){zO(e,`da`,t)}function zO(e,t,n=Ij){let r=e.__wdc||=()=>{let t=n;for(;t;){if(t.isDeactivated)return;t=t.parent}return e()};if(UO(t,r,n),n){let e=n.parent;for(;e&&e.parent;)PO(e.parent.vnode)&&BO(r,t,n,e),e=e.parent}}function BO(e,t,n,r){let i=UO(t,e,r,!0);XO(()=>{qT(r[t],i)},n)}function VO(e){e.shapeFlag&=-257,e.shapeFlag&=-513}function HO(e){return e.shapeFlag&128?e.ssContent:e}function UO(e,t,n=Ij,r=!1){if(n){let i=n[e]||(n[e]=[]),a=t.__weh||=(...r)=>{iw();let i=Bj(n),a=HE(t,n,e,r);return i(),aw(),a};return r?i.unshift(a):i.push(a),a}}var WO=e=>(t,n=Ij)=>{(!Uj||e===`sp`)&&UO(e,(...e)=>t(...e),n)},GO=WO(`bm`),KO=WO(`m`),qO=WO(`bu`),JO=WO(`u`),YO=WO(`bum`),XO=WO(`um`),ZO=WO(`sp`),QO=WO(`rtg`),$O=WO(`rtc`);function ek(e,t=Ij){UO(`ec`,e,t)}var tk=`components`,nk=`directives`;function rk(e,t){return sk(tk,e,!0,t)||e}var ik=Symbol.for(`v-ndc`);function ak(e){return $T(e)?sk(tk,e,!1)||e:e||ik}function ok(e){return sk(nk,e)}function sk(e,t,n=!0,r=!1){let i=uD||Ij;if(i){let n=i.type;if(e===tk){let e=tM(n,!1);if(e&&(e===t||e===lE(t)||e===fE(lE(t))))return n}let a=ck(i[e]||n[e],t)||ck(i.appContext[e],t);return!a&&r?n:a}}function ck(e,t){return e&&(e[t]||e[lE(t)]||e[fE(lE(t))])}function lk(e,t,n,r){let i,a=n&&n[r],o=J(e);if(o||$T(e)){let n=o&&aT(e),r=!1,s=!1;n&&(r=!sT(e),s=oT(e),e=yw(e)),i=Array(e.length);for(let n=0,o=e.length;n<o;n++)i[n]=t(r?s?dT(uT(e[n])):uT(e[n]):e[n],n,void 0,a&&a[n])}else if(typeof e==`number`){i=Array(e);for(let n=0;n<e;n++)i[n]=t(n+1,n,void 0,a&&a[n])}else if(tE(e))if(e[Symbol.iterator])i=Array.from(e,(e,n)=>t(e,n,void 0,a&&a[n]));else{let n=Object.keys(e);i=Array(n.length);for(let r=0,o=n.length;r<o;r++){let o=n[r];i[r]=t(e[o],o,r,a&&a[r])}}else i=[];return n&&(n[r]=i),i}function uk(e,t){for(let n=0;n<t.length;n++){let r=t[n];if(J(r))for(let t=0;t<r.length;t++)e[r[t].name]=r[t].fn;else r&&(e[r.name]=r.key?(...e)=>{let t=r.fn(...e);return t&&(t.key=r.key),t}:r.fn)}return e}function dk(e,t,n={},r,i){if(uD.ce||uD.parent&&jO(uD.parent)&&uD.parent.ce){let e=Object.keys(n).length>0;return t!==`default`&&(n.name=t),cj(),mj(nj,null,[xj(`slot`,n,r&&r())],e?-2:64)}let a=e[t];a&&a._c&&(a._d=!1),cj();let o=a&&fk(a(n)),s=n.key||o&&o.key,c=mj(nj,{key:(s&&!eE(s)?s:`_${t}`)+(!o&&r?`_fb`:``)},o||(r?r():[]),o&&e._===1?64:-2);return!i&&c.scopeId&&(c.slotScopeIds=[c.scopeId+`-s`]),a&&a._c&&(a._d=!0),c}function fk(e){return e.some(e=>hj(e)?!(e.type===ij||e.type===nj&&!fk(e.children)):!0)?e:null}function pk(e,t){let n={};for(let r in e)n[t&&/[A-Z]/.test(r)?`on:${r}`:pE(r)]=e[r];return n}var mk=e=>e?Hj(e)?eM(e):mk(e.parent):null,hk=KT(Object.create(null),{$:e=>e,$el:e=>e.vnode.el,$data:e=>e.data,$props:e=>e.props,$attrs:e=>e.attrs,$slots:e=>e.slots,$refs:e=>e.refs,$parent:e=>mk(e.parent),$root:e=>mk(e.root),$host:e=>e.ce,$emit:e=>e.emit,$options:e=>zk(e),$forceUpdate:e=>e.f||=()=>{eD(e.update)},$nextTick:e=>e.n||=QE.bind(e.proxy),$watch:e=>kD.bind(e)}),gk=(e,t)=>e!==K&&!e.__isScriptSetup&&q(e,t),_k={get({_:e},t){if(t===`__v_skip`)return!0;let{ctx:n,setupState:r,data:i,props:a,accessCache:o,type:s,appContext:c}=e;if(t[0]!==`$`){let e=o[t];if(e!==void 0)switch(e){case 1:return r[t];case 2:return i[t];case 4:return n[t];case 3:return a[t]}else if(gk(r,t))return o[t]=1,r[t];else if(i!==K&&q(i,t))return o[t]=2,i[t];else if(q(a,t))return o[t]=3,a[t];else if(n!==K&&q(n,t))return o[t]=4,n[t];else Pk&&(o[t]=0)}let l=hk[t],u,d;if(l)return t===`$attrs`&&hw(e.attrs,`get`,``),l(e);if((u=s.__cssModules)&&(u=u[t]))return u;if(n!==K&&q(n,t))return o[t]=4,n[t];if(d=c.config.globalProperties,q(d,t))return d[t]},set({_:e},t,n){let{data:r,setupState:i,ctx:a}=e;return gk(i,t)?(i[t]=n,!0):r!==K&&q(r,t)?(r[t]=n,!0):q(e.props,t)||t[0]===`$`&&t.slice(1)in e?!1:(a[t]=n,!0)},has({_:{data:e,setupState:t,accessCache:n,ctx:r,appContext:i,props:a,type:o}},s){let c;return!!(n[s]||e!==K&&s[0]!==`$`&&q(e,s)||gk(t,s)||q(a,s)||q(r,s)||q(hk,s)||q(i.config.globalProperties,s)||(c=o.__cssModules)&&c[s])},defineProperty(e,t,n){return n.get==null?q(n,`value`)&&this.set(e,t,n.value,null):e._.accessCache[t]=0,Reflect.defineProperty(e,t,n)}},vk=KT({},_k,{get(e,t){if(t!==Symbol.unscopables)return _k.get(e,t,e)},has(e,t){return t[0]!==`_`&&!xE(t)}});function yk(){return null}function bk(){return null}function xk(e){}function Sk(e){}function Ck(){return null}function wk(){}function Tk(e,t){return null}function Ek(){return Ok(`useSlots`).slots}function Dk(){return Ok(`useAttrs`).attrs}function Ok(e){let t=Lj();return t.setupContext||=$j(t)}function kk(e){return J(e)?e.reduce((e,t)=>(e[t]=null,e),{}):e}function Ak(e,t){let n=kk(e);for(let e in t){if(e.startsWith(`__skip`))continue;let r=n[e];r?J(r)||Y(r)?r=n[e]={type:r,default:t[e]}:r.default=t[e]:r===null&&(r=n[e]={default:t[e]}),r&&t[`__skip_${e}`]&&(r.skipFactory=!0)}return n}function jk(e,t){return!e||!t?e||t:J(e)&&J(t)?e.concat(t):KT({},kk(e),kk(t))}function Mk(e,t){let n={};for(let r in e)t.includes(r)||Object.defineProperty(n,r,{enumerable:!0,get:()=>e[r]});return n}function Nk(e){let t=Lj(),n=Uj,r=e();Vj(),n&&zj(!1);let i=()=>{Bj(t),n&&zj(!0)},a=()=>{Lj()!==t&&t.scope.off(),Vj(),n&&zj(!1)};return nE(r)&&(r=r.catch(e=>{throw i(),Promise.resolve().then(()=>Promise.resolve().then(a)),e})),[r,()=>{i(),Promise.resolve().then(a)}]}var Pk=!0;function Fk(e){let t=zk(e),n=e.proxy,r=e.ctx;Pk=!1,t.beforeCreate&&Lk(t.beforeCreate,e,`bc`);let{data:i,computed:a,methods:o,watch:s,provide:c,inject:l,created:u,beforeMount:d,mounted:f,beforeUpdate:p,updated:m,activated:h,deactivated:g,beforeDestroy:_,beforeUnmount:v,destroyed:y,unmounted:b,render:x,renderTracked:S,renderTriggered:C,errorCaptured:w,serverPrefetch:T,expose:E,inheritAttrs:D,components:ee,directives:te,filters:ne}=t;if(l&&Ik(l,r,null),o)for(let e in o){let t=o[e];Y(t)&&(r[e]=t.bind(n))}if(i){let t=i.call(n,n);tE(t)&&(e.data=eT(t))}if(Pk=!0,a)for(let e in a){let t=a[e],i=X({get:Y(t)?t.bind(n,n):Y(t.get)?t.get.bind(n,n):HT,set:!Y(t)&&Y(t.set)?t.set.bind(n):HT});Object.defineProperty(r,e,{enumerable:!0,configurable:!0,get:()=>i.value,set:e=>i.value=e})}if(s)for(let e in s)Rk(s[e],r,n,e);if(c){let e=Y(c)?c.call(n):c;Reflect.ownKeys(e).forEach(t=>{yD(t,e[t])})}u&&Lk(u,e,`c`);function re(e,t){J(t)?t.forEach(t=>e(t.bind(n))):t&&e(t.bind(n))}if(re(GO,d),re(KO,f),re(qO,p),re(JO,m),re(LO,h),re(RO,g),re(ek,w),re($O,S),re(QO,C),re(YO,v),re(XO,b),re(ZO,T),J(E))if(E.length){let t=e.exposed||={};E.forEach(e=>{Object.defineProperty(t,e,{get:()=>n[e],set:t=>n[e]=t,enumerable:!0})})}else e.exposed||={};x&&e.render===HT&&(e.render=x),D!=null&&(e.inheritAttrs=D),ee&&(e.components=ee),te&&(e.directives=te),T&&sO(e)}function Ik(e,t,n=HT){J(e)&&(e=Wk(e));for(let n in e){let r=e[n],i;i=tE(r)?`default`in r?bD(r.from||n,r.default,!0):bD(r.from||n):bD(r),fT(i)?Object.defineProperty(t,n,{enumerable:!0,configurable:!0,get:()=>i.value,set:e=>i.value=e}):t[n]=i}}function Lk(e,t,n){HE(J(e)?e.map(e=>e.bind(t.proxy)):e.bind(t.proxy),t,n)}function Rk(e,t,n,r){let i=r.includes(`.`)?AD(n,r):()=>n[r];if($T(e)){let n=t[e];Y(n)&&DD(i,n)}else if(Y(e))DD(i,e.bind(n));else if(tE(e))if(J(e))e.forEach(e=>Rk(e,t,n,r));else{let r=Y(e.handler)?e.handler.bind(n):t[e.handler];Y(r)&&DD(i,r,e)}}function zk(e){let t=e.type,{mixins:n,extends:r}=t,{mixins:i,optionsCache:a,config:{optionMergeStrategies:o}}=e.appContext,s=a.get(t),c;return s?c=s:!i.length&&!n&&!r?c=t:(c={},i.length&&i.forEach(e=>Bk(c,e,o,!0)),Bk(c,t,o)),tE(t)&&a.set(t,c),c}function Bk(e,t,n,r=!1){let{mixins:i,extends:a}=t;a&&Bk(e,a,n,!0),i&&i.forEach(t=>Bk(e,t,n,!0));for(let i in t)if(!(r&&i===`expose`)){let r=Vk[i]||n&&n[i];e[i]=r?r(e[i],t[i]):t[i]}return e}var Vk={data:Hk,props:qk,emits:qk,methods:Kk,computed:Kk,beforeCreate:Gk,created:Gk,beforeMount:Gk,mounted:Gk,beforeUpdate:Gk,updated:Gk,beforeDestroy:Gk,beforeUnmount:Gk,destroyed:Gk,unmounted:Gk,activated:Gk,deactivated:Gk,errorCaptured:Gk,serverPrefetch:Gk,components:Kk,directives:Kk,watch:Jk,provide:Hk,inject:Uk};function Hk(e,t){return t?e?function(){return KT(Y(e)?e.call(this,this):e,Y(t)?t.call(this,this):t)}:t:e}function Uk(e,t){return Kk(Wk(e),Wk(t))}function Wk(e){if(J(e)){let t={};for(let n=0;n<e.length;n++)t[e[n]]=e[n];return t}return e}function Gk(e,t){return e?[...new Set([].concat(e,t))]:t}function Kk(e,t){return e?KT(Object.create(null),e,t):t}function qk(e,t){return e?J(e)&&J(t)?[...new Set([...e,...t])]:KT(Object.create(null),kk(e),kk(t??{})):t}function Jk(e,t){if(!e)return t;if(!t)return e;let n=KT(Object.create(null),e);for(let r in t)n[r]=Gk(e[r],t[r]);return n}function Yk(){return{app:null,config:{isNativeTag:UT,performance:!1,globalProperties:{},optionMergeStrategies:{},errorHandler:void 0,warnHandler:void 0,compilerOptions:{}},mixins:[],components:{},directives:{},provides:Object.create(null),optionsCache:new WeakMap,propsCache:new WeakMap,emitsCache:new WeakMap}}var Xk=0;function Zk(e,t){return function(n,r=null){Y(n)||(n=KT({},n)),r!=null&&!tE(r)&&(r=null);let i=Yk(),a=new WeakSet,o=[],s=!1,c=i.app={_uid:Xk++,_component:n,_props:r,_container:null,_context:i,_instance:null,version:sM,get config(){return i.config},set config(e){},use(e,...t){return a.has(e)||(e&&Y(e.install)?(a.add(e),e.install(c,...t)):Y(e)&&(a.add(e),e(c,...t))),c},mixin(e){return i.mixins.includes(e)||i.mixins.push(e),c},component(e,t){return t?(i.components[e]=t,c):i.components[e]},directive(e,t){return t?(i.directives[e]=t,c):i.directives[e]},mount(a,o,l){if(!s){let u=c._ceVNode||xj(n,r);return u.appContext=i,l===!0?l=`svg`:l===!1&&(l=void 0),o&&t?t(u,a):e(u,a,l),s=!0,c._container=a,a.__vue_app__=c,eM(u.component)}},onUnmount(e){o.push(e)},unmount(){s&&(HE(o,c._instance,16),e(null,c._container),delete c._container.__vue_app__)},provide(e,t){return i.provides[e]=t,c},runWithContext(e){let t=Qk;Qk=c;try{return e()}finally{Qk=t}}};return c}}var Qk=null;function $k(e,t,n=K){let r=Lj(),i=lE(t),a=dE(t),o=eA(e,i),s=CT((o,s)=>{let c,l=K,u;return ED(()=>{let t=e[i];mE(c,t)&&(c=t,s())}),{get(){return o(),n.get?n.get(c):c},set(e){let o=n.set?n.set(e):e;if(!mE(o,c)&&!(l!==K&&mE(e,l)))return;let d=r.vnode.props;d&&(t in d||i in d||a in d)&&(`onUpdate:${t}`in d||`onUpdate:${i}`in d||`onUpdate:${a}`in d)||(c=e,s()),r.emit(`update:${t}`,o),mE(e,o)&&mE(e,l)&&!mE(o,u)&&s(),l=e,u=o}}});return s[Symbol.iterator]=()=>{let e=0;return{next(){return e<2?{value:e++?o||K:s,done:!1}:{done:!0}}}},s}var eA=(e,t)=>t===`modelValue`||t===`model-value`?e.modelModifiers:e[`${t}Modifiers`]||e[`${lE(t)}Modifiers`]||e[`${dE(t)}Modifiers`];function tA(e,t,...n){if(e.isUnmounted)return;let r=e.vnode.props||K,i=n,a=t.startsWith(`update:`),o=a&&eA(r,t.slice(7));o&&(o.trim&&(i=n.map(e=>$T(e)?e.trim():e)),o.number&&(i=n.map(_E)));let s,c=r[s=pE(t)]||r[s=pE(lE(t))];!c&&a&&(c=r[s=pE(dE(t))]),c&&HE(c,e,6,i);let l=r[s+`Once`];if(l){if(!e.emitted)e.emitted={};else if(e.emitted[s])return;e.emitted[s]=!0,HE(l,e,6,i)}}var nA=new WeakMap;function rA(e,t,n=!1){let r=n?nA:t.emitsCache,i=r.get(e);if(i!==void 0)return i;let a=e.emits,o={},s=!1;if(!Y(e)){let r=e=>{let n=rA(e,t,!0);n&&(s=!0,KT(o,n))};!n&&t.mixins.length&&t.mixins.forEach(r),e.extends&&r(e.extends),e.mixins&&e.mixins.forEach(r)}return!a&&!s?(tE(e)&&r.set(e,null),null):(J(a)?a.forEach(e=>o[e]=null):KT(o,a),tE(e)&&r.set(e,o),o)}function iA(e,t){return!e||!WT(t)?!1:(t=t.slice(2).replace(/Once$/,``),q(e,t[0].toLowerCase()+t.slice(1))||q(e,dE(t))||q(e,t))}function aA(e){let{type:t,vnode:n,proxy:r,withProxy:i,propsOptions:[a],slots:o,attrs:s,emit:c,render:l,renderCache:u,props:d,data:f,setupState:p,ctx:m,inheritAttrs:h}=e,g=fD(e),_,v;try{if(n.shapeFlag&4){let e=i||r,t=e;_=Oj(l.call(t,e,u,d,p,f,m)),v=s}else{let e=t;_=Oj(e.length>1?e(d,{attrs:s,slots:o,emit:c}):e(d,null)),v=t.props?s:sA(s)}}catch(t){oj.length=0,UE(t,e,1),_=xj(ij)}let y=_;if(v&&h!==!1){let e=Object.keys(v),{shapeFlag:t}=y;e.length&&t&7&&(a&&e.some(GT)&&(v=cA(v,a)),y=wj(y,v,!1,!0))}return n.dirs&&(y=wj(y,null,!1,!0),y.dirs=y.dirs?y.dirs.concat(n.dirs):n.dirs),n.transition&&rO(y,n.transition),_=y,fD(g),_}function oA(e,t=!0){let n;for(let t=0;t<e.length;t++){let r=e[t];if(hj(r)){if(r.type!==ij||r.children===`v-if`){if(n)return;n=r}}else return}return n}var sA=e=>{let t;for(let n in e)(n===`class`||n===`style`||WT(n))&&((t||={})[n]=e[n]);return t},cA=(e,t)=>{let n={};for(let r in e)(!GT(r)||!(r.slice(9)in t))&&(n[r]=e[r]);return n};function lA(e,t,n){let{props:r,children:i,component:a}=e,{props:o,children:s,patchFlag:c}=t,l=a.emitsOptions;if(t.dirs||t.transition)return!0;if(n&&c>=0){if(c&1024)return!0;if(c&16)return r?uA(r,o,l):!!o;if(c&8){let e=t.dynamicProps;for(let t=0;t<e.length;t++){let n=e[t];if(dA(o,r,n)&&!iA(l,n))return!0}}}else return(i||s)&&(!s||!s.$stable)?!0:r===o?!1:r?o?uA(r,o,l):!0:!!o;return!1}function uA(e,t,n){let r=Object.keys(t);if(r.length!==Object.keys(e).length)return!0;for(let i=0;i<r.length;i++){let a=r[i];if(dA(t,e,a)&&!iA(n,a))return!0}return!1}function dA(e,t,n){let r=e[n],i=t[n];return n===`style`&&tE(r)&&tE(i)?!AE(r,i):r!==i}function fA({vnode:e,parent:t,suspense:n},r){for(;t;){let n=t.subTree;if(n.suspense&&n.suspense.activeBranch===e&&(n.suspense.vnode.el=n.el=r,e=n),n===e)(e=t.vnode).el=r,t=t.parent;else break}n&&n.activeBranch===e&&(n.vnode.el=r)}var pA={},mA=()=>Object.create(pA),hA=e=>Object.getPrototypeOf(e)===pA;function gA(e,t,n,r=!1){let i={},a=mA();e.propsDefaults=Object.create(null),vA(e,t,i,a);for(let t in e.propsOptions[0])t in i||(i[t]=void 0);n?e.props=r?i:tT(i):e.type.props?e.props=i:e.props=a,e.attrs=a}function _A(e,t,n,r){let{props:i,attrs:a,vnode:{patchFlag:o}}=e,s=G(i),[c]=e.propsOptions,l=!1;if((r||o>0)&&!(o&16)){if(o&8){let n=e.vnode.dynamicProps;for(let r=0;r<n.length;r++){let o=n[r];if(iA(e.emitsOptions,o))continue;let u=t[o];if(c)if(q(a,o))u!==a[o]&&(a[o]=u,l=!0);else{let t=lE(o);i[t]=yA(c,s,t,u,e,!1)}else u!==a[o]&&(a[o]=u,l=!0)}}}else{vA(e,t,i,a)&&(l=!0);let r;for(let a in s)(!t||!q(t,a)&&((r=dE(a))===a||!q(t,r)))&&(c?n&&(n[a]!==void 0||n[r]!==void 0)&&(i[a]=yA(c,s,a,void 0,e,!0)):delete i[a]);if(a!==s)for(let e in a)(!t||!q(t,e))&&(delete a[e],l=!0)}l&&gw(e.attrs,`set`,``)}function vA(e,t,n,r){let[i,a]=e.propsOptions,o=!1,s;if(t)for(let c in t){if(oE(c))continue;let l=t[c],u;i&&q(i,u=lE(c))?!a||!a.includes(u)?n[u]=l:(s||={})[u]=l:iA(e.emitsOptions,c)||(!(c in r)||l!==r[c])&&(r[c]=l,o=!0)}if(a){let t=G(n),r=s||K;for(let o=0;o<a.length;o++){let s=a[o];n[s]=yA(i,t,s,r[s],e,!q(r,s))}}return o}function yA(e,t,n,r,i,a){let o=e[n];if(o!=null){let e=q(o,`default`);if(e&&r===void 0){let e=o.default;if(o.type!==Function&&!o.skipFactory&&Y(e)){let{propsDefaults:a}=i;if(n in a)r=a[n];else{let o=Bj(i);r=a[n]=e.call(null,t),o()}}else r=e;i.ce&&i.ce._setProp(n,r)}o[0]&&(a&&!e?r=!1:o[1]&&(r===``||r===dE(n))&&(r=!0))}return r}var bA=new WeakMap;function xA(e,t,n=!1){let r=n?bA:t.propsCache,i=r.get(e);if(i)return i;let a=e.props,o={},s=[],c=!1;if(!Y(e)){let r=e=>{c=!0;let[n,r]=xA(e,t,!0);KT(o,n),r&&s.push(...r)};!n&&t.mixins.length&&t.mixins.forEach(r),e.extends&&r(e.extends),e.mixins&&e.mixins.forEach(r)}if(!a&&!c)return tE(e)&&r.set(e,VT),VT;if(J(a))for(let e=0;e<a.length;e++){let t=lE(a[e]);SA(t)&&(o[t]=K)}else if(a)for(let e in a){let t=lE(e);if(SA(t)){let n=a[e],r=o[t]=J(n)||Y(n)?{type:n}:KT({},n),i=r.type,c=!1,l=!0;if(J(i))for(let e=0;e<i.length;++e){let t=i[e],n=Y(t)&&t.name;if(n===`Boolean`){c=!0;break}else n===`String`&&(l=!1)}else c=Y(i)&&i.name===`Boolean`;r[0]=c,r[1]=l,(c||q(r,`default`))&&s.push(t)}}let l=[o,s];return tE(e)&&r.set(e,l),l}function SA(e){return e[0]!==`$`&&!oE(e)}var CA=e=>e===`_`||e===`_ctx`||e===`$stable`,wA=e=>J(e)?e.map(Oj):[Oj(e)],TA=(e,t,n)=>{if(t._n)return t;let r=gD((...e)=>wA(t(...e)),n);return r._c=!1,r},EA=(e,t,n)=>{let r=e._ctx;for(let n in e){if(CA(n))continue;let i=e[n];if(Y(i))t[n]=TA(n,i,r);else if(i!=null){let e=wA(i);t[n]=()=>e}}},DA=(e,t)=>{let n=wA(t);e.slots.default=()=>n},OA=(e,t,n)=>{for(let r in t)(n||!CA(r))&&(e[r]=t[r])},kA=(e,t,n)=>{let r=e.slots=mA();if(e.vnode.shapeFlag&32){let e=t._;e?(OA(r,t,n),n&&gE(r,`_`,e,!0)):EA(t,r)}else t&&DA(e,t)},AA=(e,t,n)=>{let{vnode:r,slots:i}=e,a=!0,o=K;if(r.shapeFlag&32){let e=t._;e?n&&e===1?a=!1:OA(i,t,n):(a=!t.$stable,EA(t,i)),o=t}else t&&(DA(e,t),o={default:1});if(a)for(let e in i)!CA(e)&&o[e]==null&&delete i[e]},jA=$A;function MA(e){return PA(e)}function NA(e){return PA(e,yO)}function PA(e,t){let n=bE();n.__VUE__=!0;let{insert:r,remove:i,patchProp:a,createElement:o,createText:s,createComment:c,setText:l,setElementText:u,parentNode:d,nextSibling:f,setScopeId:p=HT,insertStaticContent:m}=e,h=(e,t,n,r=null,i=null,a=null,o=void 0,s=null,c=!!t.dynamicChildren)=>{if(e===t)return;e&&!gj(e,t)&&(r=O(e),ue(e,i,a,!0),e=null),t.patchFlag===-2&&(c=!1,t.dynamicChildren=null);let{type:l,ref:u,shapeFlag:d}=t;switch(l){case rj:g(e,t,n,r);break;case ij:_(e,t,n,r);break;case aj:e??v(t,n,r,o);break;case nj:ee(e,t,n,r,i,a,o,s,c);break;default:d&1?x(e,t,n,r,i,a,o,s,c):d&6?te(e,t,n,r,i,a,o,s,c):(d&64||d&128)&&l.process(e,t,n,r,i,a,o,s,c,_e)}u!=null&&i?dO(u,e&&e.ref,a,t||e,!t):u==null&&e&&e.ref!=null&&dO(e.ref,null,a,e,!0)},g=(e,t,n,i)=>{if(e==null)r(t.el=s(t.children),n,i);else{let n=t.el=e.el;t.children!==e.children&&l(n,t.children)}},_=(e,t,n,i)=>{e==null?r(t.el=c(t.children||``),n,i):t.el=e.el},v=(e,t,n,r)=>{[e.el,e.anchor]=m(e.children,t,n,r,e.el,e.anchor)},y=({el:e,anchor:t},n,i)=>{let a;for(;e&&e!==t;)a=f(e),r(e,n,i),e=a;r(t,n,i)},b=({el:e,anchor:t})=>{let n;for(;e&&e!==t;)n=f(e),i(e),e=n;i(t)},x=(e,t,n,r,i,a,o,s,c)=>{if(t.type===`svg`?o=`svg`:t.type===`math`&&(o=`mathml`),e==null)S(t,n,r,i,a,o,s,c);else{let n=e.el&&e.el._isVueCE?e.el:null;try{n&&n._beginPatch(),T(e,t,i,a,o,s,c)}finally{n&&n._endPatch()}}},S=(e,t,n,i,s,c,l,d)=>{let f,p,{props:m,shapeFlag:h,transition:g,dirs:_}=e;if(f=e.el=o(e.type,c,m&&m.is,m),h&8?u(f,e.children):h&16&&w(e.children,f,null,i,s,FA(e,c),l,d),_&&vD(e,null,i,`created`),C(f,e,e.scopeId,l,i),m){for(let e in m)e!==`value`&&!oE(e)&&a(f,e,null,m[e],c,i);`value`in m&&a(f,`value`,null,m.value,c),(p=m.onVnodeBeforeMount)&&Mj(p,i,e)}_&&vD(e,null,i,`beforeMount`);let v=LA(s,g);v&&g.beforeEnter(f),r(f,t,n),((p=m&&m.onVnodeMounted)||v||_)&&jA(()=>{try{p&&Mj(p,i,e),v&&g.enter(f),_&&vD(e,null,i,`mounted`)}finally{}},s)},C=(e,t,n,r,i)=>{if(n&&p(e,n),r)for(let t=0;t<r.length;t++)p(e,r[t]);if(i){let n=i.subTree;if(t===n||UA(n.type)&&(n.ssContent===t||n.ssFallback===t)){let t=i.vnode;C(e,t,t.scopeId,t.slotScopeIds,i.parent)}}},w=(e,t,n,r,i,a,o,s,c=0)=>{for(let l=c;l<e.length;l++)h(null,e[l]=s?kj(e[l]):Oj(e[l]),t,n,r,i,a,o,s)},T=(e,t,n,r,i,o,s)=>{let c=t.el=e.el,{patchFlag:l,dynamicChildren:d,dirs:f}=t;l|=e.patchFlag&16;let p=e.props||K,m=t.props||K,h;if(n&&IA(n,!1),(h=m.onVnodeBeforeUpdate)&&Mj(h,n,t,e),f&&vD(t,e,n,`beforeUpdate`),n&&IA(n,!0),(p.innerHTML&&m.innerHTML==null||p.textContent&&m.textContent==null)&&u(c,``),d?E(e.dynamicChildren,d,c,n,r,FA(t,i),o):s||oe(e,t,c,null,n,r,FA(t,i),o,!1),l>0){if(l&16)D(c,p,m,n,i);else if(l&2&&p.class!==m.class&&a(c,`class`,null,m.class,i),l&4&&a(c,`style`,p.style,m.style,i),l&8){let e=t.dynamicProps;for(let t=0;t<e.length;t++){let r=e[t],o=p[r],s=m[r];(s!==o||r===`value`)&&a(c,r,o,s,i,n)}}l&1&&e.children!==t.children&&u(c,t.children)}else !s&&d==null&&D(c,p,m,n,i);((h=m.onVnodeUpdated)||f)&&jA(()=>{h&&Mj(h,n,t,e),f&&vD(t,e,n,`updated`)},r)},E=(e,t,n,r,i,a,o)=>{for(let s=0;s<t.length;s++){let c=e[s],l=t[s];h(c,l,c.el&&(c.type===nj||!gj(c,l)||c.shapeFlag&198)?d(c.el):n,null,r,i,a,o,!0)}},D=(e,t,n,r,i)=>{if(t!==n){if(t!==K)for(let o in t)!oE(o)&&!(o in n)&&a(e,o,t[o],null,i,r);for(let o in n){if(oE(o))continue;let s=n[o],c=t[o];s!==c&&o!==`value`&&a(e,o,c,s,i,r)}`value`in n&&a(e,`value`,t.value,n.value,i)}},ee=(e,t,n,i,a,o,c,l,u)=>{let d=t.el=e?e.el:s(``),f=t.anchor=e?e.anchor:s(``),{patchFlag:p,dynamicChildren:m,slotScopeIds:h}=t;h&&(l=l?l.concat(h):h),e==null?(r(d,n,i),r(f,n,i),w(t.children||[],n,f,a,o,c,l,u)):p>0&&p&64&&m&&e.dynamicChildren&&e.dynamicChildren.length===m.length?(E(e.dynamicChildren,m,n,a,o,c,l),(t.key!=null||a&&t===a.subTree)&&RA(e,t,!0)):oe(e,t,n,f,a,o,c,l,u)},te=(e,t,n,r,i,a,o,s,c)=>{t.slotScopeIds=s,e==null?t.shapeFlag&512?i.ctx.activate(t,n,r,o,c):ne(t,n,r,i,a,o,c):re(e,t,c)},ne=(e,t,n,r,i,a,o)=>{let s=e.component=Fj(e,r,i);if(PO(e)&&(s.ctx.renderer=_e),Wj(s,!1,o),s.asyncDep){if(i&&i.registerDep(s,ie,o),!e.el){let r=s.subTree=xj(ij);_(null,r,t,n),e.placeholder=r.el}}else ie(s,e,t,n,i,a,o)},re=(e,t,n)=>{let r=t.component=e.component;if(lA(e,t,n))if(r.asyncDep&&!r.asyncResolved){ae(r,t,n);return}else r.next=t,r.update();else t.el=e.el,r.vnode=t},ie=(e,t,n,r,i,a,o)=>{let s=()=>{if(e.isMounted){let{next:t,bu:n,u:r,parent:s,vnode:c}=e;{let n=BA(e);if(n){t&&(t.el=c.el,ae(e,t,o)),n.asyncDep.then(()=>{jA(()=>{e.isUnmounted||l()},i)});return}}let u=t,f;IA(e,!1),t?(t.el=c.el,ae(e,t,o)):t=c,n&&hE(n),(f=t.props&&t.props.onVnodeBeforeUpdate)&&Mj(f,s,t,c),IA(e,!0);let p=aA(e),m=e.subTree;e.subTree=p,h(m,p,d(m.el),O(m),e,i,a),t.el=p.el,u===null&&fA(e,p.el),r&&jA(r,i),(f=t.props&&t.props.onVnodeUpdated)&&jA(()=>Mj(f,s,t,c),i)}else{let o,{el:s,props:c}=t,{bm:l,m:u,parent:d,root:f,type:p}=e,m=jO(t);if(IA(e,!1),l&&hE(l),!m&&(o=c&&c.onVnodeBeforeMount)&&Mj(o,d,t),IA(e,!0),s&&ye){let t=()=>{e.subTree=aA(e),ye(s,e.subTree,e,i,null)};m&&p.__asyncHydrate?p.__asyncHydrate(s,e,t):t()}else{f.ce&&f.ce._hasShadowRoot()&&f.ce._injectChildStyle(p,e.parent?e.parent.type:void 0);let o=e.subTree=aA(e);h(null,o,n,r,e,i,a),t.el=o.el}if(u&&jA(u,i),!m&&(o=c&&c.onVnodeMounted)){let e=t;jA(()=>Mj(o,d,e),i)}(t.shapeFlag&256||d&&jO(d.vnode)&&d.vnode.shapeFlag&256)&&e.a&&jA(e.a,i),e.isMounted=!0,t=n=r=null}};e.scope.on();let c=e.effect=new VC(s);e.scope.off();let l=e.update=c.run.bind(c),u=e.job=c.runIfDirty.bind(c);u.i=e,u.id=e.uid,c.scheduler=()=>eD(u),IA(e,!0),l()},ae=(e,t,n)=>{t.component=e;let r=e.vnode.props;e.vnode=t,e.next=null,_A(e,t.props,r,n),AA(e,t.children,n),iw(),rD(e),aw()},oe=(e,t,n,r,i,a,o,s,c=!1)=>{let l=e&&e.children,d=e?e.shapeFlag:0,f=t.children,{patchFlag:p,shapeFlag:m}=t;if(p>0){if(p&128){ce(l,f,n,r,i,a,o,s,c);return}else if(p&256){se(l,f,n,r,i,a,o,s,c);return}}m&8?(d&16&&me(l,i,a),f!==l&&u(n,f)):d&16?m&16?ce(l,f,n,r,i,a,o,s,c):me(l,i,a,!0):(d&8&&u(n,``),m&16&&w(f,n,r,i,a,o,s,c))},se=(e,t,n,r,i,a,o,s,c)=>{e||=VT,t||=VT;let l=e.length,u=t.length,d=Math.min(l,u),f;for(f=0;f<d;f++){let r=t[f]=c?kj(t[f]):Oj(t[f]);h(e[f],r,n,null,i,a,o,s,c)}l>u?me(e,i,a,!0,!1,d):w(t,n,r,i,a,o,s,c,d)},ce=(e,t,n,r,i,a,o,s,c)=>{let l=0,u=t.length,d=e.length-1,f=u-1;for(;l<=d&&l<=f;){let r=e[l],u=t[l]=c?kj(t[l]):Oj(t[l]);if(gj(r,u))h(r,u,n,null,i,a,o,s,c);else break;l++}for(;l<=d&&l<=f;){let r=e[d],l=t[f]=c?kj(t[f]):Oj(t[f]);if(gj(r,l))h(r,l,n,null,i,a,o,s,c);else break;d--,f--}if(l>d){if(l<=f){let e=f+1,d=e<u?t[e].el:r;for(;l<=f;)h(null,t[l]=c?kj(t[l]):Oj(t[l]),n,d,i,a,o,s,c),l++}}else if(l>f)for(;l<=d;)ue(e[l],i,a,!0),l++;else{let p=l,m=l,g=new Map;for(l=m;l<=f;l++){let e=t[l]=c?kj(t[l]):Oj(t[l]);e.key!=null&&g.set(e.key,l)}let _,v=0,y=f-m+1,b=!1,x=0,S=Array(y);for(l=0;l<y;l++)S[l]=0;for(l=p;l<=d;l++){let r=e[l];if(v>=y){ue(r,i,a,!0);continue}let u;if(r.key!=null)u=g.get(r.key);else for(_=m;_<=f;_++)if(S[_-m]===0&&gj(r,t[_])){u=_;break}u===void 0?ue(r,i,a,!0):(S[u-m]=l+1,u>=x?x=u:b=!0,h(r,t[u],n,null,i,a,o,s,c),v++)}let C=b?zA(S):VT;for(_=C.length-1,l=y-1;l>=0;l--){let e=m+l,d=t[e],f=t[e+1],p=e+1<u?f.el||HA(f):r;S[l]===0?h(null,d,n,p,i,a,o,s,c):b&&(_<0||l!==C[_]?le(d,n,p,2):_--)}}},le=(e,t,n,a,o=null)=>{let{el:s,type:c,transition:l,children:u,shapeFlag:d}=e;if(d&6){le(e.component.subTree,t,n,a);return}if(d&128){e.suspense.move(t,n,a);return}if(d&64){c.move(e,t,n,_e);return}if(c===nj){r(s,t,n);for(let e=0;e<u.length;e++)le(u[e],t,n,a);r(e.anchor,t,n);return}if(c===aj){y(e,t,n);return}if(a!==2&&d&1&&l)if(a===0)l.beforeEnter(s),r(s,t,n),jA(()=>l.enter(s),o);else{let{leave:a,delayLeave:o,afterLeave:c}=l,u=()=>{e.ctx.isUnmounted?i(s):r(s,t,n)},d=()=>{s._isLeaving&&s[WD](!0),a(s,()=>{u(),c&&c()})};o?o(s,u,d):d()}else r(s,t,n)},ue=(e,t,n,r=!1,i=!1)=>{let{type:a,props:o,ref:s,children:c,dynamicChildren:l,shapeFlag:u,patchFlag:d,dirs:f,cacheIndex:p,memo:m}=e;if(d===-2&&(i=!1),s!=null&&(iw(),dO(s,null,n,e,!0),aw()),p!=null&&(t.renderCache[p]=void 0),u&256){t.ctx.deactivate(e);return}let h=u&1&&f,g=!jO(e),_;if(g&&(_=o&&o.onVnodeBeforeUnmount)&&Mj(_,t,e),u&6)pe(e.component,n,r);else{if(u&128){e.suspense.unmount(n,r);return}h&&vD(e,null,t,`beforeUnmount`),u&64?e.type.remove(e,t,n,_e,r):l&&!l.hasOnce&&(a!==nj||d>0&&d&64)?me(l,t,n,!1,!0):(a===nj&&d&384||!i&&u&16)&&me(c,t,n),r&&de(e)}let v=m!=null&&p==null;(g&&(_=o&&o.onVnodeUnmounted)||h||v)&&jA(()=>{_&&Mj(_,t,e),h&&vD(e,null,t,`unmounted`),v&&(e.el=null)},n)},de=e=>{let{type:t,el:n,anchor:r,transition:a}=e;if(t===nj){fe(n,r);return}if(t===aj){b(e);return}let o=()=>{i(n),a&&!a.persisted&&a.afterLeave&&a.afterLeave()};if(e.shapeFlag&1&&a&&!a.persisted){let{leave:t,delayLeave:r}=a,i=()=>t(n,o);r?r(e.el,o,i):i()}else o()},fe=(e,t)=>{let n;for(;e!==t;)n=f(e),i(e),e=n;i(t)},pe=(e,t,n)=>{let{bum:r,scope:i,job:a,subTree:o,um:s,m:c,a:l}=e;VA(c),VA(l),r&&hE(r),i.stop(),a&&(a.flags|=8,ue(o,e,t,n)),s&&jA(s,t),jA(()=>{e.isUnmounted=!0},t)},me=(e,t,n,r=!1,i=!1,a=0)=>{for(let o=a;o<e.length;o++)ue(e[o],t,n,r,i)},O=e=>{if(e.shapeFlag&6)return O(e.component.subTree);if(e.shapeFlag&128)return e.suspense.next();let t=f(e.anchor||e.el),n=t&&t[jD];return n?f(n):t},he=!1,ge=(e,t,n)=>{let r;e==null?t._vnode&&(ue(t._vnode,null,null,!0),r=t._vnode.component):h(t._vnode||null,e,t,null,null,null,n),t._vnode=e,he||=(he=!0,rD(r),iD(),!1)},_e={p:h,um:ue,m:le,r:de,mt:ne,mc:w,pc:oe,pbc:E,n:O,o:e},ve,ye;return t&&([ve,ye]=t(_e)),{render:ge,hydrate:ve,createApp:Zk(ge,ve)}}function FA({type:e,props:t},n){return n===`svg`&&e===`foreignObject`||n===`mathml`&&e===`annotation-xml`&&t&&t.encoding&&t.encoding.includes(`html`)?void 0:n}function IA({effect:e,job:t},n){n?(e.flags|=32,t.flags|=4):(e.flags&=-33,t.flags&=-5)}function LA(e,t){return(!e||e&&!e.pendingBranch)&&t&&!t.persisted}function RA(e,t,n=!1){let r=e.children,i=t.children;if(J(r)&&J(i))for(let e=0;e<r.length;e++){let t=r[e],a=i[e];a.shapeFlag&1&&!a.dynamicChildren&&((a.patchFlag<=0||a.patchFlag===32)&&(a=i[e]=kj(i[e]),a.el=t.el),!n&&a.patchFlag!==-2&&RA(t,a)),a.type===rj&&(a.patchFlag===-1&&(a=i[e]=kj(a)),a.el=t.el),a.type===ij&&!a.el&&(a.el=t.el)}}function zA(e){let t=e.slice(),n=[0],r,i,a,o,s,c=e.length;for(r=0;r<c;r++){let c=e[r];if(c!==0){if(i=n[n.length-1],e[i]<c){t[r]=i,n.push(r);continue}for(a=0,o=n.length-1;a<o;)s=a+o>>1,e[n[s]]<c?a=s+1:o=s;c<e[n[a]]&&(a>0&&(t[r]=n[a-1]),n[a]=r)}}for(a=n.length,o=n[a-1];a-- >0;)n[a]=o,o=t[o];return n}function BA(e){let t=e.subTree.component;if(t)return t.asyncDep&&!t.asyncResolved?t:BA(t)}function VA(e){if(e)for(let t=0;t<e.length;t++)e[t].flags|=8}function HA(e){if(e.placeholder)return e.placeholder;let t=e.component;return t?HA(t.subTree):null}var UA=e=>e.__isSuspense,WA=0,GA={name:`Suspense`,__isSuspense:!0,process(e,t,n,r,i,a,o,s,c,l){if(e==null)qA(t,n,r,i,a,o,s,c,l);else{if(a&&a.deps>0&&!e.suspense.isInFallback){t.suspense=e.suspense,t.suspense.vnode=t,t.el=e.el;return}JA(e,t,n,r,i,o,s,c,l)}},hydrate:XA,normalize:ZA};function KA(e,t){let n=e.props&&e.props[t];Y(n)&&n()}function qA(e,t,n,r,i,a,o,s,c){let{p:l,o:{createElement:u}}=c,d=u(`div`),f=e.suspense=YA(e,i,r,t,d,n,a,o,s,c);l(null,f.pendingBranch=e.ssContent,d,null,r,f,a,o),f.deps>0?(KA(e,`onPending`),KA(e,`onFallback`),l(null,e.ssFallback,t,n,r,null,a,o),ej(f,e.ssFallback)):f.resolve(!1,!0)}function JA(e,t,n,r,i,a,o,s,{p:c,um:l,o:{createElement:u}}){let d=t.suspense=e.suspense;d.vnode=t,t.el=e.el;let f=t.ssContent,p=t.ssFallback,{activeBranch:m,pendingBranch:h,isInFallback:g,isHydrating:_}=d;if(h)d.pendingBranch=f,gj(h,f)?(c(h,f,d.hiddenContainer,null,i,d,a,o,s),d.deps<=0?d.resolve():g&&(_||(c(m,p,n,r,i,null,a,o,s),ej(d,p)))):(d.pendingId=WA++,_?(d.isHydrating=!1,d.activeBranch=h):l(h,i,d),d.deps=0,d.effects.length=0,d.hiddenContainer=u(`div`),g?(c(null,f,d.hiddenContainer,null,i,d,a,o,s),d.deps<=0?d.resolve():(c(m,p,n,r,i,null,a,o,s),ej(d,p))):m&&gj(m,f)?(c(m,f,n,r,i,d,a,o,s),d.resolve(!0)):(c(null,f,d.hiddenContainer,null,i,d,a,o,s),d.deps<=0&&d.resolve()));else if(m&&gj(m,f))c(m,f,n,r,i,d,a,o,s),ej(d,f);else if(KA(t,`onPending`),d.pendingBranch=f,f.shapeFlag&512?d.pendingId=f.component.suspenseId:d.pendingId=WA++,c(null,f,d.hiddenContainer,null,i,d,a,o,s),d.deps<=0)d.resolve();else{let{timeout:e,pendingId:t}=d;e>0?setTimeout(()=>{d.pendingId===t&&d.fallback(p)},e):e===0&&d.fallback(p)}}function YA(e,t,n,r,i,a,o,s,c,l,u=!1){let{p:d,m:f,um:p,n:m,o:{parentNode:h,remove:g}}=l,_,v=tj(e);v&&t&&t.pendingBranch&&(_=t.pendingId,t.deps++);let y=e.props?vE(e.props.timeout):void 0,b=a,x={vnode:e,parent:t,parentComponent:n,namespace:o,container:r,hiddenContainer:i,deps:0,pendingId:WA++,timeout:typeof y==`number`?y:-1,activeBranch:null,isFallbackMountPending:!1,pendingBranch:null,isInFallback:!u,isHydrating:u,isUnmounted:!1,effects:[],resolve(e=!1,n=!1){let{vnode:r,activeBranch:i,pendingBranch:o,pendingId:s,effects:c,parentComponent:l,container:u,isInFallback:d}=x,g=!1;x.isHydrating?x.isHydrating=!1:e||(g=i&&o.transition&&o.transition.mode===`out-in`,g&&(i.transition.afterLeave=()=>{s===x.pendingId&&(f(o,u,a===b?m(i):a,0),nD(c),d&&r.ssFallback&&(r.ssFallback.el=null))}),i&&!x.isFallbackMountPending&&(h(i.el)===u&&(a=m(i)),p(i,l,x,!0),!g&&d&&r.ssFallback&&jA(()=>r.ssFallback.el=null,x)),g||f(o,u,a,0)),x.isFallbackMountPending=!1,ej(x,o),x.pendingBranch=null,x.isInFallback=!1;let y=x.parent,S=!1;for(;y;){if(y.pendingBranch){y.effects.push(...c),S=!0;break}y=y.parent}!S&&!g&&nD(c),x.effects=[],v&&t&&t.pendingBranch&&_===t.pendingId&&(t.deps--,t.deps===0&&!n&&t.resolve()),KA(r,`onResolve`)},fallback(e){if(!x.pendingBranch)return;let{vnode:t,activeBranch:n,parentComponent:r,container:i,namespace:a}=x;KA(t,`onFallback`);let o=m(n),l=()=>{x.isFallbackMountPending=!1,x.isInFallback&&(d(null,e,i,o,r,null,a,s,c),ej(x,e))},u=e.transition&&e.transition.mode===`out-in`;u&&(x.isFallbackMountPending=!0,n.transition.afterLeave=l),x.isInFallback=!0,p(n,r,null,!0),u||l()},move(e,t,n){x.activeBranch&&f(x.activeBranch,e,t,n),x.container=e},next(){return x.activeBranch&&m(x.activeBranch)},registerDep(e,t,n){let r=!!x.pendingBranch;r&&x.deps++;let i=e.vnode.el;e.asyncDep.catch(t=>{UE(t,e,0)}).then(a=>{if(e.isUnmounted||x.isUnmounted||x.pendingId!==e.suspenseId)return;e.asyncResolved=!0;let{vnode:s}=e;Kj(e,a,!1),i&&(s.el=i);let c=!i&&e.subTree.el;t(e,s,h(i||e.subTree.el),i?null:m(e.subTree),x,o,n),c&&(s.placeholder=null,g(c)),fA(e,s.el),r&&--x.deps===0&&x.resolve()})},unmount(e,t){x.isUnmounted=!0,x.activeBranch&&p(x.activeBranch,n,e,t),x.pendingBranch&&p(x.pendingBranch,n,e,t)}};return x}function XA(e,t,n,r,i,a,o,s,c){let l=t.suspense=YA(t,r,n,e.parentNode,document.createElement(`div`),null,i,a,o,s,!0),u=c(e,l.pendingBranch=t.ssContent,n,l,a,o);return l.deps===0&&l.resolve(!1,!0),u}function ZA(e){let{shapeFlag:t,children:n}=e,r=t&32;e.ssContent=QA(r?n.default:n),e.ssFallback=r?QA(n.fallback):xj(ij)}function QA(e){let t;if(Y(e)){let n=uj&&e._c;n&&(e._d=!1,cj()),e=e(),n&&(e._d=!0,t=sj,lj())}return J(e)&&(e=oA(e)),e=Oj(e),t&&!e.dynamicChildren&&(e.dynamicChildren=t.filter(t=>t!==e)),e}function $A(e,t){t&&t.pendingBranch?J(e)?t.effects.push(...e):t.effects.push(e):nD(e)}function ej(e,t){e.activeBranch=t;let{vnode:n,parentComponent:r}=e,i=t.el;for(;!i&&t.component;)t=t.component.subTree,i=t.el;n.el=i,r&&r.subTree===n&&(r.vnode.el=i,fA(r,i))}function tj(e){let t=e.props&&e.props.suspensible;return t!=null&&t!==!1}var nj=Symbol.for(`v-fgt`),rj=Symbol.for(`v-txt`),ij=Symbol.for(`v-cmt`),aj=Symbol.for(`v-stc`),oj=[],sj=null;function cj(e=!1){oj.push(sj=e?null:[])}function lj(){oj.pop(),sj=oj[oj.length-1]||null}var uj=1;function dj(e,t=!1){uj+=e,e<0&&sj&&t&&(sj.hasOnce=!0)}function fj(e){return e.dynamicChildren=uj>0?sj||VT:null,lj(),uj>0&&sj&&sj.push(e),e}function pj(e,t,n,r,i,a){return fj(bj(e,t,n,r,i,a,!0))}function mj(e,t,n,r,i){return fj(xj(e,t,n,r,i,!0))}function hj(e){return e?e.__v_isVNode===!0:!1}function gj(e,t){return e.type===t.type&&e.key===t.key}function _j(e){}var vj=({key:e})=>e??null,yj=({ref:e,ref_key:t,ref_for:n})=>(typeof e==`number`&&(e=``+e),e==null?null:$T(e)||fT(e)||Y(e)?{i:uD,r:e,k:t,f:!!n}:e);function bj(e,t=null,n=null,r=0,i=null,a=e===nj?0:1,o=!1,s=!1){let c={__v_isVNode:!0,__v_skip:!0,type:e,props:t,key:t&&vj(t),ref:t&&yj(t),scopeId:dD,slotScopeIds:null,children:n,component:null,suspense:null,ssContent:null,ssFallback:null,dirs:null,transition:null,el:null,anchor:null,target:null,targetStart:null,targetAnchor:null,staticCount:0,shapeFlag:a,patchFlag:r,dynamicProps:i,dynamicChildren:null,appContext:null,ctx:uD};return s?(Aj(c,n),a&128&&e.normalize(c)):n&&(c.shapeFlag|=$T(n)?8:16),uj>0&&!o&&sj&&(c.patchFlag>0||a&6)&&c.patchFlag!==32&&sj.push(c),c}var xj=Sj;function Sj(e,t=null,n=null,r=0,i=null,a=!1){if((!e||e===ik)&&(e=ij),hj(e)){let r=wj(e,t,!0);return n&&Aj(r,n),uj>0&&!a&&sj&&(r.shapeFlag&6?sj[sj.indexOf(e)]=r:sj.push(r)),r.patchFlag=-2,r}if(nM(e)&&(e=e.__vccOpts),t){t=Cj(t);let{class:e,style:n}=t;e&&!$T(e)&&(t.class=DE(e)),tE(n)&&(cT(n)&&!J(n)&&(n=KT({},n)),t.style=SE(n))}let o=$T(e)?1:UA(e)?128:MD(e)?64:tE(e)?4:Y(e)?2:0;return bj(e,t,n,r,i,o,a,!0)}function Cj(e){return e?cT(e)||hA(e)?KT({},e):e:null}function wj(e,t,n=!1,r=!1){let{props:i,ref:a,patchFlag:o,children:s,transition:c}=e,l=t?jj(i||{},t):i,u={__v_isVNode:!0,__v_skip:!0,type:e.type,props:l,key:l&&vj(l),ref:t&&t.ref?n&&a?J(a)?a.concat(yj(t)):[a,yj(t)]:yj(t):a,scopeId:e.scopeId,slotScopeIds:e.slotScopeIds,children:s,target:e.target,targetStart:e.targetStart,targetAnchor:e.targetAnchor,staticCount:e.staticCount,shapeFlag:e.shapeFlag,patchFlag:t&&e.type!==nj?o===-1?16:o|16:o,dynamicProps:e.dynamicProps,dynamicChildren:e.dynamicChildren,appContext:e.appContext,dirs:e.dirs,transition:c,component:e.component,suspense:e.suspense,ssContent:e.ssContent&&wj(e.ssContent),ssFallback:e.ssFallback&&wj(e.ssFallback),placeholder:e.placeholder,el:e.el,anchor:e.anchor,ctx:e.ctx,ce:e.ce};return c&&r&&rO(u,c.clone(u)),u}function Tj(e=` `,t=0){return xj(rj,null,e,t)}function Ej(e,t){let n=xj(aj,null,e);return n.staticCount=t,n}function Dj(e=``,t=!1){return t?(cj(),mj(ij,null,e)):xj(ij,null,e)}function Oj(e){return e==null||typeof e==`boolean`?xj(ij):J(e)?xj(nj,null,e.slice()):hj(e)?kj(e):xj(rj,null,String(e))}function kj(e){return e.el===null&&e.patchFlag!==-1||e.memo?e:wj(e)}function Aj(e,t){let n=0,{shapeFlag:r}=e;if(t==null)t=null;else if(J(t))n=16;else if(typeof t==`object`)if(r&65){let n=t.default;n&&(n._c&&(n._d=!1),Aj(e,n()),n._c&&(n._d=!0));return}else{n=32;let r=t._;!r&&!hA(t)?t._ctx=uD:r===3&&uD&&(uD.slots._===1?t._=1:(t._=2,e.patchFlag|=1024))}else Y(t)?(t={default:t,_ctx:uD},n=32):(t=String(t),r&64?(n=16,t=[Tj(t)]):n=8);e.children=t,e.shapeFlag|=n}function jj(...e){let t={};for(let n=0;n<e.length;n++){let r=e[n];for(let e in r)if(e===`class`)t.class!==r.class&&(t.class=DE([t.class,r.class]));else if(e===`style`)t.style=SE([t.style,r.style]);else if(WT(e)){let n=t[e],i=r[e];i&&n!==i&&!(J(n)&&n.includes(i))?t[e]=n?[].concat(n,i):i:i==null&&n==null&&!GT(e)&&(t[e]=i)}else e!==``&&(t[e]=r[e])}return t}function Mj(e,t,n,r=null){HE(e,t,7,[n,r])}var Nj=Yk(),Pj=0;function Fj(e,t,n){let r=e.type,i=(t?t.appContext:e.appContext)||Nj,a={uid:Pj++,vnode:e,type:r,parent:t,appContext:i,root:null,next:null,subTree:null,effect:null,update:null,job:null,scope:new IC(!0),render:null,proxy:null,exposed:null,exposeProxy:null,withProxy:null,provides:t?t.provides:Object.create(i.provides),ids:t?t.ids:[``,0,0],accessCache:null,renderCache:[],components:null,directives:null,propsOptions:xA(r,i),emitsOptions:rA(r,i),emit:null,emitted:null,propsDefaults:K,inheritAttrs:r.inheritAttrs,ctx:K,data:K,props:K,attrs:K,slots:K,refs:K,setupState:K,setupContext:null,suspense:n,suspenseId:n?n.pendingId:0,asyncDep:null,asyncResolved:!1,isMounted:!1,isUnmounted:!1,isDeactivated:!1,bc:null,c:null,bm:null,m:null,bu:null,u:null,um:null,bum:null,da:null,a:null,rtg:null,rtc:null,ec:null,sp:null};return a.ctx={_:a},a.root=t?t.root:a,a.emit=tA.bind(null,a),e.ce&&e.ce(a),a}var Ij=null,Lj=()=>Ij||uD,Rj,zj;{let e=bE(),t=(t,n)=>{let r;return(r=e[t])||(r=e[t]=[]),r.push(n),e=>{r.length>1?r.forEach(t=>t(e)):r[0](e)}};Rj=t(`__VUE_INSTANCE_SETTERS__`,e=>Ij=e),zj=t(`__VUE_SSR_SETTERS__`,e=>Uj=e)}var Bj=e=>{let t=Ij;return Rj(e),e.scope.on(),()=>{e.scope.off(),Rj(t)}},Vj=()=>{Ij&&Ij.scope.off(),Rj(null)};function Hj(e){return e.vnode.shapeFlag&4}var Uj=!1;function Wj(e,t=!1,n=!1){t&&zj(t);let{props:r,children:i}=e.vnode,a=Hj(e);gA(e,r,a,t),kA(e,i,n||t);let o=a?Gj(e,t):void 0;return t&&zj(!1),o}function Gj(e,t){let n=e.type;e.accessCache=Object.create(null),e.proxy=new Proxy(e.ctx,_k);let{setup:r}=n;if(r){iw();let n=e.setupContext=r.length>1?$j(e):null,i=Bj(e),a=VE(r,e,0,[e.props,n]),o=nE(a);if(aw(),i(),(o||e.sp)&&!jO(e)&&sO(e),o){if(a.then(Vj,Vj),t)return a.then(n=>{Kj(e,n,t)}).catch(t=>{UE(t,e,0)});e.asyncDep=a}else Kj(e,a,t)}else Zj(e,t)}function Kj(e,t,n){Y(t)?e.type.__ssrInlineRender?e.ssrRender=t:e.render=t:tE(t)&&(e.setupState=xT(t)),Zj(e,n)}var qj,Jj;function Yj(e){qj=e,Jj=e=>{e.render._rc&&(e.withProxy=new Proxy(e.ctx,vk))}}var Xj=()=>!qj;function Zj(e,t,n){let r=e.type;if(!e.render){if(!t&&qj&&!r.render){let t=r.template||zk(e).template;if(t){let{isCustomElement:n,compilerOptions:i}=e.appContext.config,{delimiters:a,compilerOptions:o}=r,s=KT(KT({isCustomElement:n,delimiters:a},i),o);r.render=qj(t,s)}}e.render=r.render||HT,Jj&&Jj(e)}{let t=Bj(e);iw();try{Fk(e)}finally{aw(),t()}}}var Qj={get(e,t){return hw(e,`get`,``),e[t]}};function $j(e){return{attrs:new Proxy(e.attrs,Qj),slots:e.slots,emit:e.emit,expose:t=>{e.exposed=t||{}}}}function eM(e){return e.exposed?e.exposeProxy||=new Proxy(xT(lT(e.exposed)),{get(t,n){if(n in t)return t[n];if(n in hk)return hk[n](e)},has(e,t){return t in e||t in hk}}):e.proxy}function tM(e,t=!0){return Y(e)?e.displayName||e.name:e.name||t&&e.__name}function nM(e){return Y(e)&&`__vccOpts`in e}var X=(e,t)=>AT(e,t,Uj);function rM(e,t,n){try{dj(-1);let r=arguments.length;return r===2?tE(t)&&!J(t)?hj(t)?xj(e,null,[t]):xj(e,t):xj(e,null,t):(r>3?n=Array.prototype.slice.call(arguments,2):r===3&&hj(n)&&(n=[n]),xj(e,t,n))}finally{dj(1)}}function iM(){return;function e(t,n,r){let i=t[r];if(J(i)&&i.includes(n)||tE(i)&&n in i||t.extends&&e(t.extends,n,r)||t.mixins&&t.mixins.some(t=>e(t,n,r)))return!0}}function aM(e,t,n,r){let i=n[r];if(i&&oM(i,e))return i;let a=t();return a.memo=e.slice(),a.cacheIndex=r,n[r]=a}function oM(e,t){let n=e.memo;if(n.length!=t.length)return!1;for(let e=0;e<n.length;e++)if(mE(n[e],t[e]))return!1;return uj>0&&sj&&sj.push(e),!0}var sM=`3.5.31`,cM=HT,lM=BE,uM=sD,dM=lD,fM={createComponentInstance:Fj,setupComponent:Wj,renderComponentRoot:aA,setCurrentRenderingInstance:fD,isVNode:hj,normalizeVNode:Oj,getComponentPublicInstance:eM,ensureValidVNode:fk,pushWarningContext:IE,popWarningContext:LE};function pM(e){let t=Object.create(null);for(let n of e.split(`,`))t[n]=1;return e=>e in t}var mM={},hM=()=>{},gM=e=>e.charCodeAt(0)===111&&e.charCodeAt(1)===110&&(e.charCodeAt(2)>122||e.charCodeAt(2)<97),_M=e=>e.startsWith(`onUpdate:`),vM=Object.assign,yM=Object.prototype.hasOwnProperty,bM=(e,t)=>yM.call(e,t),xM=Array.isArray,SM=e=>kM(e)===`[object Set]`,CM=e=>kM(e)===`[object Date]`,wM=e=>typeof e==`function`,TM=e=>typeof e==`string`,EM=e=>typeof e==`symbol`,DM=e=>typeof e==`object`&&!!e,OM=Object.prototype.toString,kM=e=>OM.call(e),AM=e=>kM(e)===`[object Object]`,jM=e=>{let t=Object.create(null);return(n=>t[n]||(t[n]=e(n)))},MM=/-\w/g,NM=jM(e=>e.replace(MM,e=>e.slice(1).toUpperCase())),PM=/\B([A-Z])/g,FM=jM(e=>e.replace(PM,`-$1`).toLowerCase()),IM=jM(e=>e.charAt(0).toUpperCase()+e.slice(1)),LM=(e,...t)=>{for(let n=0;n<e.length;n++)e[n](...t)},RM=e=>{let t=parseFloat(e);return isNaN(t)?e:t},zM=e=>{let t=TM(e)?Number(e):NaN;return isNaN(t)?e:t},BM=`itemscope,allowfullscreen,formnovalidate,ismap,nomodule,novalidate,readonly`,VM=pM(BM);BM+``;function HM(e){return!!e||e===``}function UM(e,t){if(e.length!==t.length)return!1;let n=!0;for(let r=0;n&&r<e.length;r++)n=WM(e[r],t[r]);return n}function WM(e,t){if(e===t)return!0;let n=CM(e),r=CM(t);if(n||r)return n&&r?e.getTime()===t.getTime():!1;if(n=EM(e),r=EM(t),n||r)return e===t;if(n=xM(e),r=xM(t),n||r)return n&&r?UM(e,t):!1;if(n=DM(e),r=DM(t),n||r){if(!n||!r||Object.keys(e).length!==Object.keys(t).length)return!1;for(let n in e){let r=e.hasOwnProperty(n),i=t.hasOwnProperty(n);if(r&&!i||!r&&i||!WM(e[n],t[n]))return!1}}return String(e)===String(t)}function GM(e,t){return e.findIndex(e=>WM(e,t))}function KM(e){return e==null?`initial`:typeof e==`string`?e===``?` `:e:String(e)}var qM=r({BaseTransition:()=>QD,BaseTransitionPropsValidators:()=>JD,Comment:()=>ij,DeprecationTypes:()=>null,EffectScope:()=>IC,ErrorCodes:()=>zE,ErrorTypeStrings:()=>lM,Fragment:()=>nj,KeepAlive:()=>FO,ReactiveEffect:()=>VC,Static:()=>aj,Suspense:()=>GA,Teleport:()=>VD,Text:()=>rj,TrackOpTypes:()=>jT,Transition:()=>sN,TransitionGroup:()=>hP,TriggerOpTypes:()=>MT,VueElement:()=>sP,assertNumber:()=>RE,callWithAsyncErrorHandling:()=>HE,callWithErrorHandling:()=>VE,camelize:()=>lE,capitalize:()=>fE,cloneVNode:()=>wj,compatUtils:()=>null,computed:()=>X,createApp:()=>XP,createBlock:()=>mj,createCommentVNode:()=>Dj,createElementBlock:()=>pj,createElementVNode:()=>bj,createHydrationRenderer:()=>NA,createPropsRestProxy:()=>Mk,createRenderer:()=>MA,createSSRApp:()=>ZP,createSlots:()=>uk,createStaticVNode:()=>Ej,createTextVNode:()=>Tj,createVNode:()=>xj,customRef:()=>CT,defineAsyncComponent:()=>MO,defineComponent:()=>aO,defineCustomElement:()=>iP,defineEmits:()=>bk,defineExpose:()=>xk,defineModel:()=>wk,defineOptions:()=>Sk,defineProps:()=>yk,defineSSRCustomElement:()=>aP,defineSlots:()=>Ck,devtools:()=>uM,effect:()=>ew,effectScope:()=>LC,getCurrentInstance:()=>Lj,getCurrentScope:()=>RC,getCurrentWatcher:()=>IT,getTransitionRawChildren:()=>iO,guardReactiveProps:()=>Cj,h:()=>rM,handleError:()=>UE,hasInjectionContext:()=>xD,hydrate:()=>YP,hydrateOnIdle:()=>TO,hydrateOnInteraction:()=>kO,hydrateOnMediaQuery:()=>OO,hydrateOnVisible:()=>DO,initCustomFormatter:()=>iM,initDirectivesForSSR:()=>tF,inject:()=>bD,isMemoSame:()=>oM,isProxy:()=>cT,isReactive:()=>aT,isReadonly:()=>oT,isRef:()=>fT,isRuntimeOnly:()=>Xj,isShallow:()=>sT,isVNode:()=>hj,markRaw:()=>lT,mergeDefaults:()=>Ak,mergeModels:()=>jk,mergeProps:()=>jj,nextTick:()=>QE,nodeOps:()=>tN,normalizeClass:()=>DE,normalizeProps:()=>OE,normalizeStyle:()=>SE,onActivated:()=>LO,onBeforeMount:()=>GO,onBeforeUnmount:()=>YO,onBeforeUpdate:()=>qO,onDeactivated:()=>RO,onErrorCaptured:()=>ek,onMounted:()=>KO,onRenderTracked:()=>$O,onRenderTriggered:()=>QO,onScopeDispose:()=>zC,onServerPrefetch:()=>ZO,onUnmounted:()=>XO,onUpdated:()=>JO,onWatcherCleanup:()=>LT,openBlock:()=>cj,patchProp:()=>eP,popScopeId:()=>mD,provide:()=>yD,proxyRefs:()=>xT,pushScopeId:()=>pD,queuePostFlushCb:()=>nD,reactive:()=>eT,readonly:()=>nT,ref:()=>pT,registerRuntimeCompiler:()=>Yj,render:()=>JP,renderList:()=>lk,renderSlot:()=>dk,resolveComponent:()=>rk,resolveDirective:()=>ok,resolveDynamicComponent:()=>ak,resolveFilter:()=>null,resolveTransitionHooks:()=>eO,setBlockTracking:()=>dj,setDevtoolsHook:()=>dM,setTransitionHooks:()=>rO,shallowReactive:()=>tT,shallowReadonly:()=>rT,shallowRef:()=>mT,ssrContextKey:()=>SD,ssrUtils:()=>fM,stop:()=>tw,toDisplayString:()=>ME,toHandlerKey:()=>pE,toHandlers:()=>pk,toRaw:()=>G,toRef:()=>DT,toRefs:()=>wT,toValue:()=>yT,transformVNodeArgs:()=>_j,triggerRef:()=>_T,unref:()=>vT,useAttrs:()=>Dk,useCssModule:()=>uP,useCssVars:()=>kN,useHost:()=>cP,useId:()=>oO,useModel:()=>$k,useSSRContext:()=>CD,useShadowRoot:()=>lP,useSlots:()=>Ek,useTemplateRef:()=>cO,useTransitionState:()=>KD,vModelCheckbox:()=>DP,vModelDynamic:()=>PP,vModelRadio:()=>kP,vModelSelect:()=>AP,vModelText:()=>EP,vShow:()=>TN,version:()=>sM,warn:()=>cM,watch:()=>DD,watchEffect:()=>wD,watchPostEffect:()=>TD,watchSyncEffect:()=>ED,withAsyncContext:()=>Nk,withCtx:()=>gD,withDefaults:()=>Tk,withDirectives:()=>_D,withKeys:()=>HP,withMemo:()=>aM,withModifiers:()=>BP,withScopeId:()=>hD}),JM=void 0,YM=typeof window<`u`&&window.trustedTypes;if(YM)try{JM=YM.createPolicy(`vue`,{createHTML:e=>e})}catch{}var XM=JM?e=>JM.createHTML(e):e=>e,ZM=`http://www.w3.org/2000/svg`,QM=`http://www.w3.org/1998/Math/MathML`,$M=typeof document<`u`?document:null,eN=$M&&$M.createElement(`template`),tN={insert:(e,t,n)=>{t.insertBefore(e,n||null)},remove:e=>{let t=e.parentNode;t&&t.removeChild(e)},createElement:(e,t,n,r)=>{let i=t===`svg`?$M.createElementNS(ZM,e):t===`mathml`?$M.createElementNS(QM,e):n?$M.createElement(e,{is:n}):$M.createElement(e);return e===`select`&&r&&r.multiple!=null&&i.setAttribute(`multiple`,r.multiple),i},createText:e=>$M.createTextNode(e),createComment:e=>$M.createComment(e),setText:(e,t)=>{e.nodeValue=t},setElementText:(e,t)=>{e.textContent=t},parentNode:e=>e.parentNode,nextSibling:e=>e.nextSibling,querySelector:e=>$M.querySelector(e),setScopeId(e,t){e.setAttribute(t,``)},insertStaticContent(e,t,n,r,i,a){let o=n?n.previousSibling:t.lastChild;if(i&&(i===a||i.nextSibling))for(;t.insertBefore(i.cloneNode(!0),n),!(i===a||!(i=i.nextSibling)););else{eN.innerHTML=XM(r===`svg`?`<svg>${e}</svg>`:r===`mathml`?`<math>${e}</math>`:e);let i=eN.content;if(r===`svg`||r===`mathml`){let e=i.firstChild;for(;e.firstChild;)i.appendChild(e.firstChild);i.removeChild(e)}t.insertBefore(i,n)}return[o?o.nextSibling:t.firstChild,n?n.previousSibling:t.lastChild]}},nN=`transition`,rN=`animation`,iN=Symbol(`_vtc`),aN={name:String,type:String,css:{type:Boolean,default:!0},duration:[String,Number,Object],enterFromClass:String,enterActiveClass:String,enterToClass:String,appearFromClass:String,appearActiveClass:String,appearToClass:String,leaveFromClass:String,leaveActiveClass:String,leaveToClass:String},oN=vM({},JD,aN),sN=(e=>(e.displayName=`Transition`,e.props=oN,e))((e,{slots:t})=>rM(QD,uN(e),t)),cN=(e,t=[])=>{xM(e)?e.forEach(e=>e(...t)):e&&e(...t)},lN=e=>e?xM(e)?e.some(e=>e.length>1):e.length>1:!1;function uN(e){let t={};for(let n in e)n in aN||(t[n]=e[n]);if(e.css===!1)return t;let{name:n=`v`,type:r,duration:i,enterFromClass:a=`${n}-enter-from`,enterActiveClass:o=`${n}-enter-active`,enterToClass:s=`${n}-enter-to`,appearFromClass:c=a,appearActiveClass:l=o,appearToClass:u=s,leaveFromClass:d=`${n}-leave-from`,leaveActiveClass:f=`${n}-leave-active`,leaveToClass:p=`${n}-leave-to`}=e,m=dN(i),h=m&&m[0],g=m&&m[1],{onBeforeEnter:_,onEnter:v,onEnterCancelled:y,onLeave:b,onLeaveCancelled:x,onBeforeAppear:S=_,onAppear:C=v,onAppearCancelled:w=y}=t,T=(e,t,n,r)=>{e._enterCancelled=r,mN(e,t?u:s),mN(e,t?l:o),n&&n()},E=(e,t)=>{e._isLeaving=!1,mN(e,d),mN(e,p),mN(e,f),t&&t()},D=e=>(t,n)=>{let i=e?C:v,o=()=>T(t,e,n);cN(i,[t,o]),hN(()=>{mN(t,e?c:a),pN(t,e?u:s),lN(i)||_N(t,r,h,o)})};return vM(t,{onBeforeEnter(e){cN(_,[e]),pN(e,a),pN(e,o)},onBeforeAppear(e){cN(S,[e]),pN(e,c),pN(e,l)},onEnter:D(!1),onAppear:D(!0),onLeave(e,t){e._isLeaving=!0;let n=()=>E(e,t);pN(e,d),e._enterCancelled?(pN(e,f),xN(e)):(xN(e),pN(e,f)),hN(()=>{e._isLeaving&&(mN(e,d),pN(e,p),lN(b)||_N(e,r,g,n))}),cN(b,[e,n])},onEnterCancelled(e){T(e,!1,void 0,!0),cN(y,[e])},onAppearCancelled(e){T(e,!0,void 0,!0),cN(w,[e])},onLeaveCancelled(e){E(e),cN(x,[e])}})}function dN(e){if(e==null)return null;if(DM(e))return[fN(e.enter),fN(e.leave)];{let t=fN(e);return[t,t]}}function fN(e){return zM(e)}function pN(e,t){t.split(/\s+/).forEach(t=>t&&e.classList.add(t)),(e[iN]||(e[iN]=new Set)).add(t)}function mN(e,t){t.split(/\s+/).forEach(t=>t&&e.classList.remove(t));let n=e[iN];n&&(n.delete(t),n.size||(e[iN]=void 0))}function hN(e){requestAnimationFrame(()=>{requestAnimationFrame(e)})}var gN=0;function _N(e,t,n,r){let i=e._endId=++gN,a=()=>{i===e._endId&&r()};if(n!=null)return setTimeout(a,n);let{type:o,timeout:s,propCount:c}=vN(e,t);if(!o)return r();let l=o+`end`,u=0,d=()=>{e.removeEventListener(l,f),a()},f=t=>{t.target===e&&++u>=c&&d()};setTimeout(()=>{u<c&&d()},s+1),e.addEventListener(l,f)}function vN(e,t){let n=window.getComputedStyle(e),r=e=>(n[e]||``).split(`, `),i=r(`${nN}Delay`),a=r(`${nN}Duration`),o=yN(i,a),s=r(`${rN}Delay`),c=r(`${rN}Duration`),l=yN(s,c),u=null,d=0,f=0;t===nN?o>0&&(u=nN,d=o,f=a.length):t===rN?l>0&&(u=rN,d=l,f=c.length):(d=Math.max(o,l),u=d>0?o>l?nN:rN:null,f=u?u===nN?a.length:c.length:0);let p=u===nN&&/\b(?:transform|all)(?:,|$)/.test(r(`${nN}Property`).toString());return{type:u,timeout:d,propCount:f,hasTransform:p}}function yN(e,t){for(;e.length<t.length;)e=e.concat(e);return Math.max(...t.map((t,n)=>bN(t)+bN(e[n])))}function bN(e){return e===`auto`?0:Number(e.slice(0,-1).replace(`,`,`.`))*1e3}function xN(e){return(e?e.ownerDocument:document).body.offsetHeight}function SN(e,t,n){let r=e[iN];r&&(t=(t?[t,...r]:[...r]).join(` `)),t==null?e.removeAttribute(`class`):n?e.setAttribute(`class`,t):e.className=t}var CN=Symbol(`_vod`),wN=Symbol(`_vsh`),TN={name:`show`,beforeMount(e,{value:t},{transition:n}){e[CN]=e.style.display===`none`?``:e.style.display,n&&t?n.beforeEnter(e):EN(e,t)},mounted(e,{value:t},{transition:n}){n&&t&&n.enter(e)},updated(e,{value:t,oldValue:n},{transition:r}){!t!=!n&&(r?t?(r.beforeEnter(e),EN(e,!0),r.enter(e)):r.leave(e,()=>{EN(e,!1)}):EN(e,t))},beforeUnmount(e,{value:t}){EN(e,t)}};function EN(e,t){e.style.display=t?e[CN]:`none`,e[wN]=!t}function DN(){TN.getSSRProps=({value:e})=>{if(!e)return{style:{display:`none`}}}}var ON=Symbol(``);function kN(e){let t=Lj();if(!t)return;let n=t.ut=(n=e(t.proxy))=>{Array.from(document.querySelectorAll(`[data-v-owner="${t.uid}"]`)).forEach(e=>jN(e,n))},r=()=>{let r=e(t.proxy);t.ce?jN(t.ce,r):AN(t.subTree,r),n(r)};qO(()=>{nD(r)}),KO(()=>{DD(r,hM,{flush:`post`});let e=new MutationObserver(r);e.observe(t.subTree.el.parentNode,{childList:!0}),XO(()=>e.disconnect())})}function AN(e,t){if(e.shapeFlag&128){let n=e.suspense;e=n.activeBranch,n.pendingBranch&&!n.isHydrating&&n.effects.push(()=>{AN(n.activeBranch,t)})}for(;e.component;)e=e.component.subTree;if(e.shapeFlag&1&&e.el)jN(e.el,t);else if(e.type===nj)e.children.forEach(e=>AN(e,t));else if(e.type===aj){let{el:n,anchor:r}=e;for(;n&&(jN(n,t),n!==r);)n=n.nextSibling}}function jN(e,t){if(e.nodeType===1){let n=e.style,r=``;for(let e in t){let i=KM(t[e]);n.setProperty(`--${e}`,i),r+=`--${e}: ${i};`}n[ON]=r}}var MN=/(?:^|;)\s*display\s*:/;function NN(e,t,n){let r=e.style,i=TM(n),a=!1;if(n&&!i){if(t)if(TM(t))for(let e of t.split(`;`)){let t=e.slice(0,e.indexOf(`:`)).trim();n[t]??FN(r,t,``)}else for(let e in t)n[e]??FN(r,e,``);for(let e in n)e===`display`&&(a=!0),FN(r,e,n[e])}else if(i){if(t!==n){let e=r[ON];e&&(n+=`;`+e),r.cssText=n,a=MN.test(n)}}else t&&e.removeAttribute(`style`);CN in e&&(e[CN]=a?r.display:``,e[wN]&&(r.display=`none`))}var PN=/\s*!important$/;function FN(e,t,n){if(xM(n))n.forEach(n=>FN(e,t,n));else if(n??=``,t.startsWith(`--`))e.setProperty(t,n);else{let r=RN(e,t);PN.test(n)?e.setProperty(FM(r),n.replace(PN,``),`important`):e[r]=n}}var IN=[`Webkit`,`Moz`,`ms`],LN={};function RN(e,t){let n=LN[t];if(n)return n;let r=lE(t);if(r!==`filter`&&r in e)return LN[t]=r;r=IM(r);for(let n=0;n<IN.length;n++){let i=IN[n]+r;if(i in e)return LN[t]=i}return t}var zN=`http://www.w3.org/1999/xlink`;function BN(e,t,n,r,i,a=VM(t)){r&&t.startsWith(`xlink:`)?n==null?e.removeAttributeNS(zN,t.slice(6,t.length)):e.setAttributeNS(zN,t,n):n==null||a&&!HM(n)?e.removeAttribute(t):e.setAttribute(t,a?``:EM(n)?String(n):n)}function VN(e,t,n,r,i){if(t===`innerHTML`||t===`textContent`){n!=null&&(e[t]=t===`innerHTML`?XM(n):n);return}let a=e.tagName;if(t===`value`&&a!==`PROGRESS`&&!a.includes(`-`)){let r=a===`OPTION`?e.getAttribute(`value`)||``:e.value,i=n==null?e.type===`checkbox`?`on`:``:String(n);(r!==i||!(`_value`in e))&&(e.value=i),n??e.removeAttribute(t),e._value=n;return}let o=!1;if(n===``||n==null){let r=typeof e[t];r===`boolean`?n=HM(n):n==null&&r===`string`?(n=``,o=!0):r===`number`&&(n=0,o=!0)}try{e[t]=n}catch{}o&&e.removeAttribute(i||t)}function HN(e,t,n,r){e.addEventListener(t,n,r)}function UN(e,t,n,r){e.removeEventListener(t,n,r)}var WN=Symbol(`_vei`);function GN(e,t,n,r,i=null){let a=e[WN]||(e[WN]={}),o=a[t];if(r&&o)o.value=r;else{let[n,s]=qN(t);r?HN(e,n,a[t]=ZN(r,i),s):o&&(UN(e,n,o,s),a[t]=void 0)}}var KN=/(?:Once|Passive|Capture)$/;function qN(e){let t;if(KN.test(e)){t={};let n;for(;n=e.match(KN);)e=e.slice(0,e.length-n[0].length),t[n[0].toLowerCase()]=!0}return[e[2]===`:`?e.slice(3):FM(e.slice(2)),t]}var JN=0,YN=Promise.resolve(),XN=()=>JN||=(YN.then(()=>JN=0),Date.now());function ZN(e,t){let n=e=>{if(!e._vts)e._vts=Date.now();else if(e._vts<=n.attached)return;HE(QN(e,n.value),t,5,[e])};return n.value=e,n.attached=XN(),n}function QN(e,t){if(xM(t)){let n=e.stopImmediatePropagation;return e.stopImmediatePropagation=()=>{n.call(e),e._stopped=!0},t.map(e=>t=>!t._stopped&&e&&e(t))}else return t}var $N=e=>e.charCodeAt(0)===111&&e.charCodeAt(1)===110&&e.charCodeAt(2)>96&&e.charCodeAt(2)<123,eP=(e,t,n,r,i,a)=>{let o=i===`svg`;t===`class`?SN(e,r,o):t===`style`?NN(e,n,r):gM(t)?_M(t)||GN(e,t,n,r,a):(t[0]===`.`?(t=t.slice(1),!0):t[0]===`^`?(t=t.slice(1),!1):tP(e,t,r,o))?(VN(e,t,r),!e.tagName.includes(`-`)&&(t===`value`||t===`checked`||t===`selected`)&&BN(e,t,r,o,a,t!==`value`)):e._isVueCE&&(nP(e,t)||e._def.__asyncLoader&&(/[A-Z]/.test(t)||!TM(r)))?VN(e,NM(t),r,a,t):(t===`true-value`?e._trueValue=r:t===`false-value`&&(e._falseValue=r),BN(e,t,r,o))};function tP(e,t,n,r){if(r)return!!(t===`innerHTML`||t===`textContent`||t in e&&$N(t)&&wM(n));if(t===`spellcheck`||t===`draggable`||t===`translate`||t===`autocorrect`||t===`sandbox`&&e.tagName===`IFRAME`||t===`form`||t===`list`&&e.tagName===`INPUT`||t===`type`&&e.tagName===`TEXTAREA`)return!1;if(t===`width`||t===`height`){let t=e.tagName;if(t===`IMG`||t===`VIDEO`||t===`CANVAS`||t===`SOURCE`)return!1}return $N(t)&&TM(n)?!1:t in e}function nP(e,t){let n=e._def.props;if(!n)return!1;let r=NM(t);return Array.isArray(n)?n.some(e=>NM(e)===r):Object.keys(n).some(e=>NM(e)===r)}var rP={};function iP(e,t,n){let r=aO(e,t);AM(r)&&(r=vM({},r,t));class i extends sP{constructor(e){super(r,e,n)}}return i.def=r,i}var aP=((e,t)=>iP(e,t,ZP)),oP=typeof HTMLElement<`u`?HTMLElement:class{},sP=class e extends oP{constructor(e,t={},n=XP){super(),this._def=e,this._props=t,this._createApp=n,this._isVueCE=!0,this._instance=null,this._app=null,this._nonce=this._def.nonce,this._connected=!1,this._resolved=!1,this._patching=!1,this._dirty=!1,this._numberProps=null,this._styleChildren=new WeakSet,this._styleAnchors=new WeakMap,this._ob=null,this.shadowRoot&&n!==XP?this._root=this.shadowRoot:e.shadowRoot===!1?this._root=this:(this.attachShadow(vM({},e.shadowRootOptions,{mode:`open`})),this._root=this.shadowRoot)}connectedCallback(){if(!this.isConnected)return;!this.shadowRoot&&!this._resolved&&this._parseSlots(),this._connected=!0;let t=this;for(;t&&=t.assignedSlot||t.parentNode||t.host;)if(t instanceof e){this._parent=t;break}this._instance||(this._resolved?this._mount(this._def):t&&t._pendingResolve?this._pendingResolve=t._pendingResolve.then(()=>{this._pendingResolve=void 0,this._resolveDef()}):this._resolveDef())}_setParent(e=this._parent){e&&(this._instance.parent=e._instance,this._inheritParentContext(e))}_inheritParentContext(e=this._parent){e&&this._app&&Object.setPrototypeOf(this._app._context.provides,e._instance.provides)}disconnectedCallback(){this._connected=!1,QE(()=>{this._connected||(this._ob&&=(this._ob.disconnect(),null),this._app&&this._app.unmount(),this._instance&&(this._instance.ce=void 0),this._app=this._instance=null,this._teleportTargets&&=(this._teleportTargets.clear(),void 0))})}_processMutations(e){for(let t of e)this._setAttr(t.attributeName)}_resolveDef(){if(this._pendingResolve)return;for(let e=0;e<this.attributes.length;e++)this._setAttr(this.attributes[e].name);this._ob=new MutationObserver(this._processMutations.bind(this)),this._ob.observe(this,{attributes:!0});let e=(e,t=!1)=>{this._resolved=!0,this._pendingResolve=void 0;let{props:n,styles:r}=e,i;if(n&&!xM(n))for(let e in n){let t=n[e];(t===Number||t&&t.type===Number)&&(e in this._props&&(this._props[e]=zM(this._props[e])),(i||=Object.create(null))[NM(e)]=!0)}this._numberProps=i,this._resolveProps(e),this.shadowRoot&&this._applyStyles(r),this._mount(e)},t=this._def.__asyncLoader;t?this._pendingResolve=t().then(t=>{t.configureApp=this._def.configureApp,e(this._def=t,!0)}):e(this._def)}_mount(e){this._app=this._createApp(e),this._inheritParentContext(),e.configureApp&&e.configureApp(this._app),this._app._ceVNode=this._createVNode(),this._app.mount(this._root);let t=this._instance&&this._instance.exposed;if(t)for(let e in t)bM(this,e)||Object.defineProperty(this,e,{get:()=>vT(t[e])})}_resolveProps(e){let{props:t}=e,n=xM(t)?t:Object.keys(t||{});for(let e of Object.keys(this))e[0]!==`_`&&n.includes(e)&&this._setProp(e,this[e]);for(let e of n.map(NM))Object.defineProperty(this,e,{get(){return this._getProp(e)},set(t){this._setProp(e,t,!0,!this._patching)}})}_setAttr(e){if(e.startsWith(`data-v-`))return;let t=this.hasAttribute(e),n=t?this.getAttribute(e):rP,r=NM(e);t&&this._numberProps&&this._numberProps[r]&&(n=zM(n)),this._setProp(r,n,!1,!0)}_getProp(e){return this._props[e]}_setProp(e,t,n=!0,r=!1){if(t!==this._props[e]&&(this._dirty=!0,t===rP?delete this._props[e]:(this._props[e]=t,e===`key`&&this._app&&(this._app._ceVNode.key=t)),r&&this._instance&&this._update(),n)){let n=this._ob;n&&(this._processMutations(n.takeRecords()),n.disconnect()),t===!0?this.setAttribute(FM(e),``):typeof t==`string`||typeof t==`number`?this.setAttribute(FM(e),t+``):t||this.removeAttribute(FM(e)),n&&n.observe(this,{attributes:!0})}}_update(){let e=this._createVNode();this._app&&(e.appContext=this._app._context),JP(e,this._root)}_createVNode(){let e={};this.shadowRoot||(e.onVnodeMounted=e.onVnodeUpdated=this._renderSlots.bind(this));let t=xj(this._def,vM(e,this._props));return this._instance||(t.ce=e=>{this._instance=e,e.ce=this,e.isCE=!0;let t=(e,t)=>{this.dispatchEvent(new CustomEvent(e,AM(t[0])?vM({detail:t},t[0]):{detail:t}))};e.emit=(e,...n)=>{t(e,n),FM(e)!==e&&t(FM(e),n)},this._setParent()}),t}_applyStyles(e,t,n){if(!e)return;if(t){if(t===this._def||this._styleChildren.has(t))return;this._styleChildren.add(t)}let r=this._nonce,i=this.shadowRoot,a=n?this._getStyleAnchor(n)||this._getStyleAnchor(this._def):this._getRootStyleInsertionAnchor(i),o=null;for(let s=e.length-1;s>=0;s--){let c=document.createElement(`style`);r&&c.setAttribute(`nonce`,r),c.textContent=e[s],i.insertBefore(c,o||a),o=c,s===0&&(n||this._styleAnchors.set(this._def,c),t&&this._styleAnchors.set(t,c))}}_getStyleAnchor(e){if(!e)return null;let t=this._styleAnchors.get(e);return t&&t.parentNode===this.shadowRoot?t:(t&&this._styleAnchors.delete(e),null)}_getRootStyleInsertionAnchor(e){for(let t=0;t<e.childNodes.length;t++){let n=e.childNodes[t];if(!(n instanceof HTMLStyleElement))return n}return null}_parseSlots(){let e=this._slots={},t;for(;t=this.firstChild;){let n=t.nodeType===1&&t.getAttribute(`slot`)||`default`;(e[n]||(e[n]=[])).push(t),this.removeChild(t)}}_renderSlots(){let e=this._getSlots(),t=this._instance.type.__scopeId;for(let n=0;n<e.length;n++){let r=e[n],i=r.getAttribute(`name`)||`default`,a=this._slots[i],o=r.parentNode;if(a)for(let e of a){if(t&&e.nodeType===1){let n=t+`-s`,r=document.createTreeWalker(e,1);e.setAttribute(n,``);let i;for(;i=r.nextNode();)i.setAttribute(n,``)}o.insertBefore(e,r)}else for(;r.firstChild;)o.insertBefore(r.firstChild,r);o.removeChild(r)}}_getSlots(){let e=[this];this._teleportTargets&&e.push(...this._teleportTargets);let t=new Set;for(let n of e){let e=n.querySelectorAll(`slot`);for(let n=0;n<e.length;n++)t.add(e[n])}return Array.from(t)}_injectChildStyle(e,t){this._applyStyles(e.styles,e,t)}_beginPatch(){this._patching=!0,this._dirty=!1}_endPatch(){this._patching=!1,this._dirty&&this._instance&&this._update()}_hasShadowRoot(){return this._def.shadowRoot!==!1}_removeChildStyle(e){}};function cP(e){let t=Lj();return t&&t.ce||null}function lP(){let e=cP();return e&&e.shadowRoot}function uP(e=`$style`){{let t=Lj();if(!t)return mM;let n=t.type.__cssModules;return n&&n[e]||mM}}var dP=new WeakMap,fP=new WeakMap,pP=Symbol(`_moveCb`),mP=Symbol(`_enterCb`),hP=(e=>(delete e.props.mode,e))({name:`TransitionGroup`,props:vM({},oN,{tag:String,moveClass:String}),setup(e,{slots:t}){let n=Lj(),r=KD(),i,a;return JO(()=>{if(!i.length)return;let t=e.moveClass||`${e.name||`v`}-move`;if(!bP(i[0].el,n.vnode.el,t)){i=[];return}i.forEach(gP),i.forEach(_P);let r=i.filter(vP);xN(n.vnode.el),r.forEach(e=>{let n=e.el,r=n.style;pN(n,t),r.transform=r.webkitTransform=r.transitionDuration=``;let i=n[pP]=e=>{e&&e.target!==n||(!e||e.propertyName.endsWith(`transform`))&&(n.removeEventListener(`transitionend`,i),n[pP]=null,mN(n,t))};n.addEventListener(`transitionend`,i)}),i=[]}),()=>{let o=G(e),s=uN(o),c=o.tag||nj;if(i=[],a)for(let e=0;e<a.length;e++){let t=a[e];t.el&&t.el instanceof Element&&(i.push(t),rO(t,eO(t,s,r,n)),dP.set(t,yP(t.el)))}a=t.default?iO(t.default()):[];for(let e=0;e<a.length;e++){let t=a[e];t.key!=null&&rO(t,eO(t,s,r,n))}return xj(c,null,a)}}});function gP(e){let t=e.el;t[pP]&&t[pP](),t[mP]&&t[mP]()}function _P(e){fP.set(e,yP(e.el))}function vP(e){let t=dP.get(e),n=fP.get(e),r=t.left-n.left,i=t.top-n.top;if(r||i){let t=e.el,n=t.style,a=t.getBoundingClientRect(),o=1,s=1;return t.offsetWidth&&(o=a.width/t.offsetWidth),t.offsetHeight&&(s=a.height/t.offsetHeight),(!Number.isFinite(o)||o===0)&&(o=1),(!Number.isFinite(s)||s===0)&&(s=1),Math.abs(o-1)<.01&&(o=1),Math.abs(s-1)<.01&&(s=1),n.transform=n.webkitTransform=`translate(${r/o}px,${i/s}px)`,n.transitionDuration=`0s`,e}}function yP(e){let t=e.getBoundingClientRect();return{left:t.left,top:t.top}}function bP(e,t,n){let r=e.cloneNode(),i=e[iN];i&&i.forEach(e=>{e.split(/\s+/).forEach(e=>e&&r.classList.remove(e))}),n.split(/\s+/).forEach(e=>e&&r.classList.add(e)),r.style.display=`none`;let a=t.nodeType===1?t:t.parentNode;a.appendChild(r);let{hasTransform:o}=vN(r);return a.removeChild(r),o}var xP=e=>{let t=e.props[`onUpdate:modelValue`]||!1;return xM(t)?e=>LM(t,e):t};function SP(e){e.target.composing=!0}function CP(e){let t=e.target;t.composing&&(t.composing=!1,t.dispatchEvent(new Event(`input`)))}var wP=Symbol(`_assign`);function TP(e,t,n){return t&&(e=e.trim()),n&&(e=RM(e)),e}var EP={created(e,{modifiers:{lazy:t,trim:n,number:r}},i){e[wP]=xP(i);let a=r||i.props&&i.props.type===`number`;HN(e,t?`change`:`input`,t=>{t.target.composing||e[wP](TP(e.value,n,a))}),(n||a)&&HN(e,`change`,()=>{e.value=TP(e.value,n,a)}),t||(HN(e,`compositionstart`,SP),HN(e,`compositionend`,CP),HN(e,`change`,CP))},mounted(e,{value:t}){e.value=t??``},beforeUpdate(e,{value:t,oldValue:n,modifiers:{lazy:r,trim:i,number:a}},o){if(e[wP]=xP(o),e.composing)return;let s=(a||e.type===`number`)&&!/^0\d/.test(e.value)?RM(e.value):e.value,c=t??``;if(s===c)return;let l=e.getRootNode();(l instanceof Document||l instanceof ShadowRoot)&&l.activeElement===e&&e.type!==`range`&&(r&&t===n||i&&e.value.trim()===c)||(e.value=c)}},DP={deep:!0,created(e,t,n){e[wP]=xP(n),HN(e,`change`,()=>{let t=e._modelValue,n=MP(e),r=e.checked,i=e[wP];if(xM(t)){let e=GM(t,n),a=e!==-1;if(r&&!a)i(t.concat(n));else if(!r&&a){let n=[...t];n.splice(e,1),i(n)}}else if(SM(t)){let e=new Set(t);r?e.add(n):e.delete(n),i(e)}else i(NP(e,r))})},mounted:OP,beforeUpdate(e,t,n){e[wP]=xP(n),OP(e,t,n)}};function OP(e,{value:t,oldValue:n},r){e._modelValue=t;let i;if(xM(t))i=GM(t,r.props.value)>-1;else if(SM(t))i=t.has(r.props.value);else{if(t===n)return;i=WM(t,NP(e,!0))}e.checked!==i&&(e.checked=i)}var kP={created(e,{value:t},n){e.checked=WM(t,n.props.value),e[wP]=xP(n),HN(e,`change`,()=>{e[wP](MP(e))})},beforeUpdate(e,{value:t,oldValue:n},r){e[wP]=xP(r),t!==n&&(e.checked=WM(t,r.props.value))}},AP={deep:!0,created(e,{value:t,modifiers:{number:n}},r){let i=SM(t);HN(e,`change`,()=>{let t=Array.prototype.filter.call(e.options,e=>e.selected).map(e=>n?RM(MP(e)):MP(e));e[wP](e.multiple?i?new Set(t):t:t[0]),e._assigning=!0,QE(()=>{e._assigning=!1})}),e[wP]=xP(r)},mounted(e,{value:t}){jP(e,t)},beforeUpdate(e,t,n){e[wP]=xP(n)},updated(e,{value:t}){e._assigning||jP(e,t)}};function jP(e,t){let n=e.multiple,r=xM(t);if(!(n&&!r&&!SM(t))){for(let i=0,a=e.options.length;i<a;i++){let a=e.options[i],o=MP(a);if(n)if(r){let e=typeof o;e===`string`||e===`number`?a.selected=t.some(e=>String(e)===String(o)):a.selected=GM(t,o)>-1}else a.selected=t.has(o);else if(WM(MP(a),t)){e.selectedIndex!==i&&(e.selectedIndex=i);return}}!n&&e.selectedIndex!==-1&&(e.selectedIndex=-1)}}function MP(e){return`_value`in e?e._value:e.value}function NP(e,t){let n=t?`_trueValue`:`_falseValue`;return n in e?e[n]:t}var PP={created(e,t,n){IP(e,t,n,null,`created`)},mounted(e,t,n){IP(e,t,n,null,`mounted`)},beforeUpdate(e,t,n,r){IP(e,t,n,r,`beforeUpdate`)},updated(e,t,n,r){IP(e,t,n,r,`updated`)}};function FP(e,t){switch(e){case`SELECT`:return AP;case`TEXTAREA`:return EP;default:switch(t){case`checkbox`:return DP;case`radio`:return kP;default:return EP}}}function IP(e,t,n,r,i){let a=FP(e.tagName,n.props&&n.props.type)[i];a&&a(e,t,n,r)}function LP(){EP.getSSRProps=({value:e})=>({value:e}),kP.getSSRProps=({value:e},t)=>{if(t.props&&WM(t.props.value,e))return{checked:!0}},DP.getSSRProps=({value:e},t)=>{if(xM(e)){if(t.props&&GM(e,t.props.value)>-1)return{checked:!0}}else if(SM(e)){if(t.props&&e.has(t.props.value))return{checked:!0}}else if(e)return{checked:!0}},PP.getSSRProps=(e,t)=>{if(typeof t.type!=`string`)return;let n=FP(t.type.toUpperCase(),t.props&&t.props.type);if(n.getSSRProps)return n.getSSRProps(e,t)}}var RP=[`ctrl`,`shift`,`alt`,`meta`],zP={stop:e=>e.stopPropagation(),prevent:e=>e.preventDefault(),self:e=>e.target!==e.currentTarget,ctrl:e=>!e.ctrlKey,shift:e=>!e.shiftKey,alt:e=>!e.altKey,meta:e=>!e.metaKey,left:e=>`button`in e&&e.button!==0,middle:e=>`button`in e&&e.button!==1,right:e=>`button`in e&&e.button!==2,exact:(e,t)=>RP.some(n=>e[`${n}Key`]&&!t.includes(n))},BP=(e,t)=>{if(!e)return e;let n=e._withMods||={},r=t.join(`.`);return n[r]||(n[r]=((n,...r)=>{for(let e=0;e<t.length;e++){let r=zP[t[e]];if(r&&r(n,t))return}return e(n,...r)}))},VP={esc:`escape`,space:` `,up:`arrow-up`,left:`arrow-left`,right:`arrow-right`,down:`arrow-down`,delete:`backspace`},HP=(e,t)=>{let n=e._withKeys||={},r=t.join(`.`);return n[r]||(n[r]=(n=>{if(!(`key`in n))return;let r=FM(n.key);if(t.some(e=>e===r||VP[e]===r))return e(n)}))},UP=vM({patchProp:eP},tN),WP,GP=!1;function KP(){return WP||=MA(UP)}function qP(){return WP=GP?WP:NA(UP),GP=!0,WP}var JP=((...e)=>{KP().render(...e)}),YP=((...e)=>{qP().hydrate(...e)}),XP=((...e)=>{let t=KP().createApp(...e),{mount:n}=t;return t.mount=e=>{let r=$P(e);if(!r)return;let i=t._component;!wM(i)&&!i.render&&!i.template&&(i.template=r.innerHTML),r.nodeType===1&&(r.textContent=``);let a=n(r,!1,QP(r));return r instanceof Element&&(r.removeAttribute(`v-cloak`),r.setAttribute(`data-v-app`,``)),a},t}),ZP=((...e)=>{let t=qP().createApp(...e),{mount:n}=t;return t.mount=e=>{let t=$P(e);if(t)return n(t,!0,QP(t))},t});function QP(e){if(e instanceof SVGElement)return`svg`;if(typeof MathMLElement==`function`&&e instanceof MathMLElement)return`mathml`}function $P(e){return TM(e)?document.querySelector(e):e}var eF=!1,tF=()=>{eF||(eF=!0,LP(),DN())};function nF(e){let t=Object.create(null);for(let n of e.split(`,`))t[n]=1;return e=>e in t}var rF={},iF=()=>{},aF=()=>!1,oF=e=>e.charCodeAt(0)===111&&e.charCodeAt(1)===110&&(e.charCodeAt(2)>122||e.charCodeAt(2)<97),sF=Object.assign,cF=Array.isArray,lF=e=>typeof e==`string`,uF=e=>typeof e==`symbol`,dF=e=>typeof e==`object`&&!!e,fF=nF(`,key,ref,ref_for,ref_key,onVnodeBeforeMount,onVnodeMounted,onVnodeBeforeUpdate,onVnodeUpdated,onVnodeBeforeUnmount,onVnodeUnmounted`),pF=nF(`bind,cloak,else-if,else,for,html,if,model,on,once,pre,show,slot,text,memo`),mF=e=>{let t=Object.create(null);return(n=>t[n]||(t[n]=e(n)))},hF=/-\w/g,gF=mF(e=>e.replace(hF,e=>e.slice(1).toUpperCase())),_F=mF(e=>e.charAt(0).toUpperCase()+e.slice(1)),vF=mF(e=>e?`on${_F(e)}`:``);function yF(e,t){return e+JSON.stringify(t,(e,t)=>typeof t==`function`?t.toString():t)}var bF=/;(?![^(]*\))/g,xF=/:([^]+)/,SF=/\/\*[^]*?\*\//g;function CF(e){let t={};return e.replace(SF,``).split(bF).forEach(e=>{if(e){let n=e.split(xF);n.length>1&&(t[n[0].trim()]=n[1].trim())}}),t}var wF=`html,body,base,head,link,meta,style,title,address,article,aside,footer,header,hgroup,h1,h2,h3,h4,h5,h6,nav,section,div,dd,dl,dt,figcaption,figure,picture,hr,img,li,main,ol,p,pre,ul,a,b,abbr,bdi,bdo,br,cite,code,data,dfn,em,i,kbd,mark,q,rp,rt,ruby,s,samp,small,span,strong,sub,sup,time,u,var,wbr,area,audio,map,track,video,embed,object,param,source,canvas,script,noscript,del,ins,caption,col,colgroup,table,thead,tbody,td,th,tr,button,datalist,fieldset,form,input,label,legend,meter,optgroup,option,output,progress,select,textarea,details,dialog,menu,summary,template,blockquote,iframe,tfoot`,TF=`svg,animate,animateMotion,animateTransform,circle,clipPath,color-profile,defs,desc,discard,ellipse,feBlend,feColorMatrix,feComponentTransfer,feComposite,feConvolveMatrix,feDiffuseLighting,feDisplacementMap,feDistantLight,feDropShadow,feFlood,feFuncA,feFuncB,feFuncG,feFuncR,feGaussianBlur,feImage,feMerge,feMergeNode,feMorphology,feOffset,fePointLight,feSpecularLighting,feSpotLight,feTile,feTurbulence,filter,foreignObject,g,hatch,hatchpath,image,line,linearGradient,marker,mask,mesh,meshgradient,meshpatch,meshrow,metadata,mpath,path,pattern,polygon,polyline,radialGradient,rect,set,solidcolor,stop,switch,symbol,text,textPath,title,tspan,unknown,use,view`,EF=`annotation,annotation-xml,maction,maligngroup,malignmark,math,menclose,merror,mfenced,mfrac,mfraction,mglyph,mi,mlabeledtr,mlongdiv,mmultiscripts,mn,mo,mover,mpadded,mphantom,mprescripts,mroot,mrow,ms,mscarries,mscarry,msgroup,msline,mspace,msqrt,msrow,mstack,mstyle,msub,msubsup,msup,mtable,mtd,mtext,mtr,munder,munderover,none,semantics`,DF=`area,base,br,col,embed,hr,img,input,link,meta,param,source,track,wbr`,OF=nF(wF),kF=nF(TF),AF=nF(EF),jF=nF(DF),MF=Symbol(``),NF=Symbol(``),PF=Symbol(``),FF=Symbol(``),IF=Symbol(``),LF=Symbol(``),RF=Symbol(``),zF=Symbol(``),BF=Symbol(``),VF=Symbol(``),HF=Symbol(``),UF=Symbol(``),WF=Symbol(``),GF=Symbol(``),KF=Symbol(``),qF=Symbol(``),JF=Symbol(``),YF=Symbol(``),XF=Symbol(``),ZF=Symbol(``),QF=Symbol(``),$F=Symbol(``),eI=Symbol(``),tI=Symbol(``),nI=Symbol(``),rI=Symbol(``),iI=Symbol(``),aI=Symbol(``),oI=Symbol(``),sI=Symbol(``),cI=Symbol(``),lI=Symbol(``),uI=Symbol(``),dI=Symbol(``),fI=Symbol(``),pI=Symbol(``),mI=Symbol(``),hI=Symbol(``),gI=Symbol(``),_I={[MF]:`Fragment`,[NF]:`Teleport`,[PF]:`Suspense`,[FF]:`KeepAlive`,[IF]:`BaseTransition`,[LF]:`openBlock`,[RF]:`createBlock`,[zF]:`createElementBlock`,[BF]:`createVNode`,[VF]:`createElementVNode`,[HF]:`createCommentVNode`,[UF]:`createTextVNode`,[WF]:`createStaticVNode`,[GF]:`resolveComponent`,[KF]:`resolveDynamicComponent`,[qF]:`resolveDirective`,[JF]:`resolveFilter`,[YF]:`withDirectives`,[XF]:`renderList`,[ZF]:`renderSlot`,[QF]:`createSlots`,[$F]:`toDisplayString`,[eI]:`mergeProps`,[tI]:`normalizeClass`,[nI]:`normalizeStyle`,[rI]:`normalizeProps`,[iI]:`guardReactiveProps`,[aI]:`toHandlers`,[oI]:`camelize`,[sI]:`capitalize`,[cI]:`toHandlerKey`,[lI]:`setBlockTracking`,[uI]:`pushScopeId`,[dI]:`popScopeId`,[fI]:`withCtx`,[pI]:`unref`,[mI]:`isRef`,[hI]:`withMemo`,[gI]:`isMemoSame`};function vI(e){Object.getOwnPropertySymbols(e).forEach(t=>{_I[t]=e[t]})}var yI={start:{line:1,column:1,offset:0},end:{line:1,column:1,offset:0},source:``};function bI(e,t=``){return{type:0,source:t,children:e,helpers:new Set,components:[],directives:[],hoists:[],imports:[],cached:[],temps:0,codegenNode:void 0,loc:yI}}function xI(e,t,n,r,i,a,o,s=!1,c=!1,l=!1,u=yI){return e&&(s?(e.helper(LF),e.helper(MI(e.inSSR,l))):e.helper(jI(e.inSSR,l)),o&&e.helper(YF)),{type:13,tag:t,props:n,children:r,patchFlag:i,dynamicProps:a,directives:o,isBlock:s,disableTracking:c,isComponent:l,loc:u}}function SI(e,t=yI){return{type:17,loc:t,elements:e}}function CI(e,t=yI){return{type:15,loc:t,properties:e}}function wI(e,t){return{type:16,loc:yI,key:lF(e)?Z(e,!0):e,value:t}}function Z(e,t=!1,n=yI,r=0){return{type:4,loc:n,content:e,isStatic:t,constType:t?3:r}}function TI(e,t=yI){return{type:8,loc:t,children:e}}function EI(e,t=[],n=yI){return{type:14,loc:n,callee:e,arguments:t}}function DI(e,t=void 0,n=!1,r=!1,i=yI){return{type:18,params:e,returns:t,newline:n,isSlot:r,loc:i}}function OI(e,t,n,r=!0){return{type:19,test:e,consequent:t,alternate:n,newline:r,loc:yI}}function kI(e,t,n=!1,r=!1){return{type:20,index:e,value:t,needPauseTracking:n,inVOnce:r,needArraySpread:!1,loc:yI}}function AI(e){return{type:21,body:e,loc:yI}}function jI(e,t){return e||t?BF:VF}function MI(e,t){return e||t?RF:zF}function NI(e,{helper:t,removeHelper:n,inSSR:r}){e.isBlock||(e.isBlock=!0,n(jI(r,e.isComponent)),t(LF),t(MI(r,e.isComponent)))}var PI=new Uint8Array([123,123]),FI=new Uint8Array([125,125]);function II(e){return e>=97&&e<=122||e>=65&&e<=90}function LI(e){return e===32||e===10||e===9||e===12||e===13}function RI(e){return e===47||e===62||LI(e)}function zI(e){let t=new Uint8Array(e.length);for(let n=0;n<e.length;n++)t[n]=e.charCodeAt(n);return t}var BI={Cdata:new Uint8Array([67,68,65,84,65,91]),CdataEnd:new Uint8Array([93,93,62]),CommentEnd:new Uint8Array([45,45,62]),ScriptEnd:new Uint8Array([60,47,115,99,114,105,112,116]),StyleEnd:new Uint8Array([60,47,115,116,121,108,101]),TitleEnd:new Uint8Array([60,47,116,105,116,108,101]),TextareaEnd:new Uint8Array([60,47,116,101,120,116,97,114,101,97])},VI=class{constructor(e,t){this.stack=e,this.cbs=t,this.state=1,this.buffer=``,this.sectionStart=0,this.index=0,this.entityStart=0,this.baseState=1,this.inRCDATA=!1,this.inXML=!1,this.inVPre=!1,this.newlines=[],this.mode=0,this.delimiterOpen=PI,this.delimiterClose=FI,this.delimiterIndex=-1,this.currentSequence=void 0,this.sequenceIndex=0}get inSFCRoot(){return this.mode===2&&this.stack.length===0}reset(){this.state=1,this.mode=0,this.buffer=``,this.sectionStart=0,this.index=0,this.baseState=1,this.inRCDATA=!1,this.currentSequence=void 0,this.newlines.length=0,this.delimiterOpen=PI,this.delimiterClose=FI}getPos(e){let t=1,n=e+1,r=this.newlines.length,i=-1;if(r>100){let t=-1,n=r;for(;t+1<n;){let r=t+n>>>1;this.newlines[r]<e?t=r:n=r}i=t}else for(let t=r-1;t>=0;t--)if(e>this.newlines[t]){i=t;break}return i>=0&&(t=i+2,n=e-this.newlines[i]),{column:n,line:t,offset:e}}peek(){return this.buffer.charCodeAt(this.index+1)}stateText(e){e===60?(this.index>this.sectionStart&&this.cbs.ontext(this.sectionStart,this.index),this.state=5,this.sectionStart=this.index):!this.inVPre&&e===this.delimiterOpen[0]&&(this.state=2,this.delimiterIndex=0,this.stateInterpolationOpen(e))}stateInterpolationOpen(e){if(e===this.delimiterOpen[this.delimiterIndex])if(this.delimiterIndex===this.delimiterOpen.length-1){let e=this.index+1-this.delimiterOpen.length;e>this.sectionStart&&this.cbs.ontext(this.sectionStart,e),this.state=3,this.sectionStart=e}else this.delimiterIndex++;else this.inRCDATA?(this.state=32,this.stateInRCDATA(e)):(this.state=1,this.stateText(e))}stateInterpolation(e){e===this.delimiterClose[0]&&(this.state=4,this.delimiterIndex=0,this.stateInterpolationClose(e))}stateInterpolationClose(e){e===this.delimiterClose[this.delimiterIndex]?this.delimiterIndex===this.delimiterClose.length-1?(this.cbs.oninterpolation(this.sectionStart,this.index+1),this.inRCDATA?this.state=32:this.state=1,this.sectionStart=this.index+1):this.delimiterIndex++:(this.state=3,this.stateInterpolation(e))}stateSpecialStartSequence(e){let t=this.sequenceIndex===this.currentSequence.length;if(!(t?RI(e):(e|32)===this.currentSequence[this.sequenceIndex]))this.inRCDATA=!1;else if(!t){this.sequenceIndex++;return}this.sequenceIndex=0,this.state=6,this.stateInTagName(e)}stateInRCDATA(e){if(this.sequenceIndex===this.currentSequence.length){if(e===62||LI(e)){let t=this.index-this.currentSequence.length;if(this.sectionStart<t){let e=this.index;this.index=t,this.cbs.ontext(this.sectionStart,t),this.index=e}this.sectionStart=t+2,this.stateInClosingTagName(e),this.inRCDATA=!1;return}this.sequenceIndex=0}(e|32)===this.currentSequence[this.sequenceIndex]?this.sequenceIndex+=1:this.sequenceIndex===0?this.currentSequence===BI.TitleEnd||this.currentSequence===BI.TextareaEnd&&!this.inSFCRoot?!this.inVPre&&e===this.delimiterOpen[0]&&(this.state=2,this.delimiterIndex=0,this.stateInterpolationOpen(e)):this.fastForwardTo(60)&&(this.sequenceIndex=1):this.sequenceIndex=Number(e===60)}stateCDATASequence(e){e===BI.Cdata[this.sequenceIndex]?++this.sequenceIndex===BI.Cdata.length&&(this.state=28,this.currentSequence=BI.CdataEnd,this.sequenceIndex=0,this.sectionStart=this.index+1):(this.sequenceIndex=0,this.state=23,this.stateInDeclaration(e))}fastForwardTo(e){for(;++this.index<this.buffer.length;){let t=this.buffer.charCodeAt(this.index);if(t===10&&this.newlines.push(this.index),t===e)return!0}return this.index=this.buffer.length-1,!1}stateInCommentLike(e){e===this.currentSequence[this.sequenceIndex]?++this.sequenceIndex===this.currentSequence.length&&(this.currentSequence===BI.CdataEnd?this.cbs.oncdata(this.sectionStart,this.index-2):this.cbs.oncomment(this.sectionStart,this.index-2),this.sequenceIndex=0,this.sectionStart=this.index+1,this.state=1):this.sequenceIndex===0?this.fastForwardTo(this.currentSequence[0])&&(this.sequenceIndex=1):e!==this.currentSequence[this.sequenceIndex-1]&&(this.sequenceIndex=0)}startSpecial(e,t){this.enterRCDATA(e,t),this.state=31}enterRCDATA(e,t){this.inRCDATA=!0,this.currentSequence=e,this.sequenceIndex=t}stateBeforeTagName(e){e===33?(this.state=22,this.sectionStart=this.index+1):e===63?(this.state=24,this.sectionStart=this.index+1):II(e)?(this.sectionStart=this.index,this.mode===0?this.state=6:this.inSFCRoot?this.state=34:this.inXML?this.state=6:e===116?this.state=30:this.state=e===115?29:6):e===47?this.state=8:(this.state=1,this.stateText(e))}stateInTagName(e){RI(e)&&this.handleTagName(e)}stateInSFCRootTagName(e){if(RI(e)){let t=this.buffer.slice(this.sectionStart,this.index);t!==`template`&&this.enterRCDATA(zI(`</`+t),0),this.handleTagName(e)}}handleTagName(e){this.cbs.onopentagname(this.sectionStart,this.index),this.sectionStart=-1,this.state=11,this.stateBeforeAttrName(e)}stateBeforeClosingTagName(e){LI(e)||(e===62?(this.state=1,this.sectionStart=this.index+1):(this.state=II(e)?9:27,this.sectionStart=this.index))}stateInClosingTagName(e){(e===62||LI(e))&&(this.cbs.onclosetag(this.sectionStart,this.index),this.sectionStart=-1,this.state=10,this.stateAfterClosingTagName(e))}stateAfterClosingTagName(e){e===62&&(this.state=1,this.sectionStart=this.index+1)}stateBeforeAttrName(e){e===62?(this.cbs.onopentagend(this.index),this.inRCDATA?this.state=32:this.state=1,this.sectionStart=this.index+1):e===47?this.state=7:e===60&&this.peek()===47?(this.cbs.onopentagend(this.index),this.state=5,this.sectionStart=this.index):LI(e)||this.handleAttrStart(e)}handleAttrStart(e){e===118&&this.peek()===45?(this.state=13,this.sectionStart=this.index):e===46||e===58||e===64||e===35?(this.cbs.ondirname(this.index,this.index+1),this.state=14,this.sectionStart=this.index+1):(this.state=12,this.sectionStart=this.index)}stateInSelfClosingTag(e){e===62?(this.cbs.onselfclosingtag(this.index),this.state=1,this.sectionStart=this.index+1,this.inRCDATA=!1):LI(e)||(this.state=11,this.stateBeforeAttrName(e))}stateInAttrName(e){(e===61||RI(e))&&(this.cbs.onattribname(this.sectionStart,this.index),this.handleAttrNameEnd(e))}stateInDirName(e){e===61||RI(e)?(this.cbs.ondirname(this.sectionStart,this.index),this.handleAttrNameEnd(e)):e===58?(this.cbs.ondirname(this.sectionStart,this.index),this.state=14,this.sectionStart=this.index+1):e===46&&(this.cbs.ondirname(this.sectionStart,this.index),this.state=16,this.sectionStart=this.index+1)}stateInDirArg(e){e===61||RI(e)?(this.cbs.ondirarg(this.sectionStart,this.index),this.handleAttrNameEnd(e)):e===91?this.state=15:e===46&&(this.cbs.ondirarg(this.sectionStart,this.index),this.state=16,this.sectionStart=this.index+1)}stateInDynamicDirArg(e){e===93?this.state=14:(e===61||RI(e))&&(this.cbs.ondirarg(this.sectionStart,this.index+1),this.handleAttrNameEnd(e))}stateInDirModifier(e){e===61||RI(e)?(this.cbs.ondirmodifier(this.sectionStart,this.index),this.handleAttrNameEnd(e)):e===46&&(this.cbs.ondirmodifier(this.sectionStart,this.index),this.sectionStart=this.index+1)}handleAttrNameEnd(e){this.sectionStart=this.index,this.state=17,this.cbs.onattribnameend(this.index),this.stateAfterAttrName(e)}stateAfterAttrName(e){e===61?this.state=18:e===47||e===62?(this.cbs.onattribend(0,this.sectionStart),this.sectionStart=-1,this.state=11,this.stateBeforeAttrName(e)):LI(e)||(this.cbs.onattribend(0,this.sectionStart),this.handleAttrStart(e))}stateBeforeAttrValue(e){e===34?(this.state=19,this.sectionStart=this.index+1):e===39?(this.state=20,this.sectionStart=this.index+1):LI(e)||(this.sectionStart=this.index,this.state=21,this.stateInAttrValueNoQuotes(e))}handleInAttrValue(e,t){(e===t||this.fastForwardTo(t))&&(this.cbs.onattribdata(this.sectionStart,this.index),this.sectionStart=-1,this.cbs.onattribend(t===34?3:2,this.index+1),this.state=11)}stateInAttrValueDoubleQuotes(e){this.handleInAttrValue(e,34)}stateInAttrValueSingleQuotes(e){this.handleInAttrValue(e,39)}stateInAttrValueNoQuotes(e){LI(e)||e===62?(this.cbs.onattribdata(this.sectionStart,this.index),this.sectionStart=-1,this.cbs.onattribend(1,this.index),this.state=11,this.stateBeforeAttrName(e)):(e===39||e===60||e===61||e===96)&&this.cbs.onerr(18,this.index)}stateBeforeDeclaration(e){e===91?(this.state=26,this.sequenceIndex=0):this.state=e===45?25:23}stateInDeclaration(e){(e===62||this.fastForwardTo(62))&&(this.state=1,this.sectionStart=this.index+1)}stateInProcessingInstruction(e){(e===62||this.fastForwardTo(62))&&(this.cbs.onprocessinginstruction(this.sectionStart,this.index),this.state=1,this.sectionStart=this.index+1)}stateBeforeComment(e){e===45?(this.state=28,this.currentSequence=BI.CommentEnd,this.sequenceIndex=2,this.sectionStart=this.index+1):this.state=23}stateInSpecialComment(e){(e===62||this.fastForwardTo(62))&&(this.cbs.oncomment(this.sectionStart,this.index),this.state=1,this.sectionStart=this.index+1)}stateBeforeSpecialS(e){e===BI.ScriptEnd[3]?this.startSpecial(BI.ScriptEnd,4):e===BI.StyleEnd[3]?this.startSpecial(BI.StyleEnd,4):(this.state=6,this.stateInTagName(e))}stateBeforeSpecialT(e){e===BI.TitleEnd[3]?this.startSpecial(BI.TitleEnd,4):e===BI.TextareaEnd[3]?this.startSpecial(BI.TextareaEnd,4):(this.state=6,this.stateInTagName(e))}startEntity(){}stateInEntity(){}parse(e){for(this.buffer=e;this.index<this.buffer.length;){let e=this.buffer.charCodeAt(this.index);switch(e===10&&this.state!==33&&this.newlines.push(this.index),this.state){case 1:this.stateText(e);break;case 2:this.stateInterpolationOpen(e);break;case 3:this.stateInterpolation(e);break;case 4:this.stateInterpolationClose(e);break;case 31:this.stateSpecialStartSequence(e);break;case 32:this.stateInRCDATA(e);break;case 26:this.stateCDATASequence(e);break;case 19:this.stateInAttrValueDoubleQuotes(e);break;case 12:this.stateInAttrName(e);break;case 13:this.stateInDirName(e);break;case 14:this.stateInDirArg(e);break;case 15:this.stateInDynamicDirArg(e);break;case 16:this.stateInDirModifier(e);break;case 28:this.stateInCommentLike(e);break;case 27:this.stateInSpecialComment(e);break;case 11:this.stateBeforeAttrName(e);break;case 6:this.stateInTagName(e);break;case 34:this.stateInSFCRootTagName(e);break;case 9:this.stateInClosingTagName(e);break;case 5:this.stateBeforeTagName(e);break;case 17:this.stateAfterAttrName(e);break;case 20:this.stateInAttrValueSingleQuotes(e);break;case 18:this.stateBeforeAttrValue(e);break;case 8:this.stateBeforeClosingTagName(e);break;case 10:this.stateAfterClosingTagName(e);break;case 29:this.stateBeforeSpecialS(e);break;case 30:this.stateBeforeSpecialT(e);break;case 21:this.stateInAttrValueNoQuotes(e);break;case 7:this.stateInSelfClosingTag(e);break;case 23:this.stateInDeclaration(e);break;case 22:this.stateBeforeDeclaration(e);break;case 25:this.stateBeforeComment(e);break;case 24:this.stateInProcessingInstruction(e);break;case 33:this.stateInEntity();break}this.index++}this.cleanup(),this.finish()}cleanup(){this.sectionStart!==this.index&&(this.state===1||this.state===32&&this.sequenceIndex===0?(this.cbs.ontext(this.sectionStart,this.index),this.sectionStart=this.index):(this.state===19||this.state===20||this.state===21)&&(this.cbs.onattribdata(this.sectionStart,this.index),this.sectionStart=this.index))}finish(){this.handleTrailingData(),this.cbs.onend()}handleTrailingData(){let e=this.buffer.length;this.sectionStart>=e||(this.state===28?this.currentSequence===BI.CdataEnd?this.cbs.oncdata(this.sectionStart,e):this.cbs.oncomment(this.sectionStart,e):this.state===6||this.state===11||this.state===18||this.state===17||this.state===12||this.state===13||this.state===14||this.state===15||this.state===16||this.state===20||this.state===19||this.state===21||this.state===9||this.cbs.ontext(this.sectionStart,e))}emitCodePoint(e,t){}};function HI(e,{compatConfig:t}){let n=t&&t[e];return e===`MODE`?n||3:n}function UI(e,t){let n=HI(`MODE`,t),r=HI(e,t);return n===3?r===!0:r!==!1}function WI(e,t,n,...r){return UI(e,t)}function GI(e){throw e}function KI(e){}function qI(e,t,n,r){let i=`https://vuejs.org/error-reference/#compiler-${e}`,a=SyntaxError(String(i));return a.code=e,a.loc=t,a}var JI=e=>e.type===4&&e.isStatic;function YI(e){switch(e){case`Teleport`:case`teleport`:return NF;case`Suspense`:case`suspense`:return PF;case`KeepAlive`:case`keep-alive`:return FF;case`BaseTransition`:case`base-transition`:return IF}}var XI=/^$|^\d|[^\$\w\xA0-\uFFFF]/,ZI=e=>!XI.test(e),QI=/[A-Za-z_$\xA0-\uFFFF]/,$I=/[\.\?\w$\xA0-\uFFFF]/,eL=/\s+[.[]\s*|\s*[.[]\s+/g,tL=e=>e.type===4?e.content:e.loc.source,nL=e=>{let t=tL(e).trim().replace(eL,e=>e.trim()),n=0,r=[],i=0,a=0,o=null;for(let e=0;e<t.length;e++){let s=t.charAt(e);switch(n){case 0:if(s===`[`)r.push(n),n=1,i++;else if(s===`(`)r.push(n),n=2,a++;else if(!(e===0?QI:$I).test(s))return!1;break;case 1:s===`'`||s===`"`||s==="`"?(r.push(n),n=3,o=s):s===`[`?i++:s===`]`&&(--i||(n=r.pop()));break;case 2:if(s===`'`||s===`"`||s==="`")r.push(n),n=3,o=s;else if(s===`(`)a++;else if(s===`)`){if(e===t.length-1)return!1;--a||(n=r.pop())}break;case 3:s===o&&(n=r.pop(),o=null);break}}return!i&&!a},rL=/^\s*(?:async\s*)?(?:\([^)]*?\)|[\w$_]+)\s*(?::[^=]+)?=>|^\s*(?:async\s+)?function(?:\s+[\w$]+)?\s*\(/,iL=e=>rL.test(tL(e));function aL(e,t,n=!1){for(let r=0;r<e.props.length;r++){let i=e.props[r];if(i.type===7&&(n||i.exp)&&(lF(t)?i.name===t:t.test(i.name)))return i}}function oL(e,t,n=!1,r=!1){for(let i=0;i<e.props.length;i++){let a=e.props[i];if(a.type===6){if(n)continue;if(a.name===t&&(a.value||r))return a}else if(a.name===`bind`&&(a.exp||r)&&sL(a.arg,t))return a}}function sL(e,t){return!!(e&&JI(e)&&e.content===t)}function cL(e){return e.props.some(e=>e.type===7&&e.name===`bind`&&(!e.arg||e.arg.type!==4||!e.arg.isStatic))}function lL(e){return e.type===5||e.type===2}function uL(e){return e.type===7&&e.name===`pre`}function dL(e){return e.type===7&&e.name===`slot`}function fL(e){return e.type===1&&e.tagType===3}function pL(e){return e.type===1&&e.tagType===2}var mL=new Set([rI,iI]);function hL(e,t=[]){if(e&&!lF(e)&&e.type===14){let n=e.callee;if(!lF(n)&&mL.has(n))return hL(e.arguments[0],t.concat(e))}return[e,t]}function gL(e,t,n){let r,i=e.type===13?e.props:e.arguments[2],a=[],o;if(i&&!lF(i)&&i.type===14){let e=hL(i);i=e[0],a=e[1],o=a[a.length-1]}if(i==null||lF(i))r=CI([t]);else if(i.type===14){let e=i.arguments[0];!lF(e)&&e.type===15?_L(t,e)||e.properties.unshift(t):i.callee===aI?r=EI(n.helper(eI),[CI([t]),i]):i.arguments.unshift(CI([t])),!r&&(r=i)}else i.type===15?(_L(t,i)||i.properties.unshift(t),r=i):(r=EI(n.helper(eI),[CI([t]),i]),o&&o.callee===iI&&(o=a[a.length-2]));e.type===13?o?o.arguments[0]=r:e.props=r:o?o.arguments[0]=r:e.arguments[2]=r}function _L(e,t){let n=!1;if(e.key.type===4){let r=e.key.content;n=t.properties.some(e=>e.key.type===4&&e.key.content===r)}return n}function vL(e,t){return`_${t}_${e.replace(/[^\w]/g,(t,n)=>t===`-`?`_`:e.charCodeAt(n).toString())}`}function yL(e){return e.type===14&&e.callee===hI?e.arguments[1].returns:e}var bL=/([\s\S]*?)\s+(?:in|of)\s+(\S[\s\S]*)/;function xL(e){for(let t=0;t<e.length;t++)if(!LI(e.charCodeAt(t)))return!1;return!0}function SL(e){return e.type===2&&xL(e.content)||e.type===12&&SL(e.content)}function CL(e){return e.type===3||SL(e)}var wL={parseMode:`base`,ns:0,delimiters:[`{{`,`}}`],getNamespace:()=>0,isVoidTag:aF,isPreTag:aF,isIgnoreNewlineTag:aF,isCustomElement:aF,onError:GI,onWarn:KI,comments:!1,prefixIdentifiers:!1},Q=wL,TL=null,EL=``,DL=null,$=null,OL=``,kL=-1,AL=-1,jL=0,ML=!1,NL=null,PL=[],FL=new VI(PL,{onerr:aR,ontext(e,t){VL(zL(e,t),e,t)},ontextentity(e,t,n){VL(e,t,n)},oninterpolation(e,t){if(ML)return VL(zL(e,t),e,t);let n=e+FL.delimiterOpen.length,r=t-FL.delimiterClose.length;for(;LI(EL.charCodeAt(n));)n++;for(;LI(EL.charCodeAt(r-1));)r--;let i=zL(n,r);i.includes(`&`)&&(i=Q.decodeEntities(i,!1)),$L({type:5,content:iR(i,!1,eR(n,r)),loc:eR(e,t)})},onopentagname(e,t){let n=zL(e,t);DL={type:1,tag:n,ns:Q.getNamespace(n,PL[0],Q.ns),tagType:0,props:[],children:[],loc:eR(e-1,t),codegenNode:void 0}},onopentagend(e){BL(e)},onclosetag(e,t){let n=zL(e,t);if(!Q.isVoidTag(n)){let r=!1;for(let e=0;e<PL.length;e++)if(PL[e].tag.toLowerCase()===n.toLowerCase()){r=!0,e>0&&aR(24,PL[0].loc.start.offset);for(let n=0;n<=e;n++)HL(PL.shift(),t,n<e);break}r||aR(23,WL(e,60))}},onselfclosingtag(e){let t=DL.tag;DL.isSelfClosing=!0,BL(e),PL[0]&&PL[0].tag===t&&HL(PL.shift(),e)},onattribname(e,t){$={type:6,name:zL(e,t),nameLoc:eR(e,t),value:void 0,loc:eR(e)}},ondirname(e,t){let n=zL(e,t),r=n===`.`||n===`:`?`bind`:n===`@`?`on`:n===`#`?`slot`:n.slice(2);if(!ML&&r===``&&aR(26,e),ML||r===``)$={type:6,name:n,nameLoc:eR(e,t),value:void 0,loc:eR(e)};else if($={type:7,name:r,rawName:n,exp:void 0,arg:void 0,modifiers:n===`.`?[Z(`prop`)]:[],loc:eR(e)},r===`pre`){ML=FL.inVPre=!0,NL=DL;let e=DL.props;for(let t=0;t<e.length;t++)e[t].type===7&&(e[t]=rR(e[t]))}},ondirarg(e,t){if(e===t)return;let n=zL(e,t);if(ML&&!uL($))$.name+=n,nR($.nameLoc,t);else{let r=n[0]!==`[`;$.arg=iR(r?n:n.slice(1,-1),r,eR(e,t),r?3:0)}},ondirmodifier(e,t){let n=zL(e,t);if(ML&&!uL($))$.name+=`.`+n,nR($.nameLoc,t);else if($.name===`slot`){let e=$.arg;e&&(e.content+=`.`+n,nR(e.loc,t))}else{let r=Z(n,!0,eR(e,t));$.modifiers.push(r)}},onattribdata(e,t){OL+=zL(e,t),kL<0&&(kL=e),AL=t},onattribentity(e,t,n){OL+=e,kL<0&&(kL=t),AL=n},onattribnameend(e){let t=$.loc.start.offset,n=zL(t,e);$.type===7&&($.rawName=n),DL.props.some(e=>(e.type===7?e.rawName:e.name)===n)&&aR(2,t)},onattribend(e,t){if(DL&&$){if(nR($.loc,t),e!==0)if(OL.includes(`&`)&&(OL=Q.decodeEntities(OL,!0)),$.type===6)$.name===`class`&&(OL=QL(OL).trim()),e===1&&!OL&&aR(13,t),$.value={type:2,content:OL,loc:e===1?eR(kL,AL):eR(kL-1,AL+1)},FL.inSFCRoot&&DL.tag===`template`&&$.name===`lang`&&OL&&OL!==`html`&&FL.enterRCDATA(zI(`</template`),0);else{$.exp=iR(OL,!1,eR(kL,AL),0,0),$.name===`for`&&($.forParseResult=RL($.exp));let e=-1;$.name===`bind`&&(e=$.modifiers.findIndex(e=>e.content===`sync`))>-1&&WI(`COMPILER_V_BIND_SYNC`,Q,$.loc,$.arg.loc.source)&&($.name=`model`,$.modifiers.splice(e,1))}($.type!==7||$.name!==`pre`)&&DL.props.push($)}OL=``,kL=AL=-1},oncomment(e,t){Q.comments&&$L({type:3,content:zL(e,t),loc:eR(e-4,t+3)})},onend(){let e=EL.length;for(let t=0;t<PL.length;t++)HL(PL[t],e-1),aR(24,PL[t].loc.start.offset)},oncdata(e,t){PL[0].ns===0?aR(1,e-9):VL(zL(e,t),e,t)},onprocessinginstruction(e){(PL[0]?PL[0].ns:Q.ns)===0&&aR(21,e-1)}}),IL=/,([^,\}\]]*)(?:,([^,\}\]]*))?$/,LL=/^\(|\)$/g;function RL(e){let t=e.loc,n=e.content,r=n.match(bL);if(!r)return;let[,i,a]=r,o=(e,n,r=!1)=>{let i=t.start.offset+n;return iR(e,!1,eR(i,i+e.length),0,r?1:0)},s={source:o(a.trim(),n.indexOf(a,i.length)),value:void 0,key:void 0,index:void 0,finalized:!1},c=i.trim().replace(LL,``).trim(),l=i.indexOf(c),u=c.match(IL);if(u){c=c.replace(IL,``).trim();let e=u[1].trim(),t;if(e&&(t=n.indexOf(e,l+c.length),s.key=o(e,t,!0)),u[2]){let r=u[2].trim();r&&(s.index=o(r,n.indexOf(r,s.key?t+e.length:l+c.length),!0))}}return c&&(s.value=o(c,l,!0)),s}function zL(e,t){return EL.slice(e,t)}function BL(e){FL.inSFCRoot&&(DL.innerLoc=eR(e+1,e+1)),$L(DL);let{tag:t,ns:n}=DL;n===0&&Q.isPreTag(t)&&jL++,Q.isVoidTag(t)?HL(DL,e):(PL.unshift(DL),(n===1||n===2)&&(FL.inXML=!0)),DL=null}function VL(e,t,n){{let t=PL[0]&&PL[0].tag;t!==`script`&&t!==`style`&&e.includes(`&`)&&(e=Q.decodeEntities(e,!1))}let r=PL[0]||TL,i=r.children[r.children.length-1];i&&i.type===2?(i.content+=e,nR(i.loc,n)):r.children.push({type:2,content:e,loc:eR(t,n)})}function HL(e,t,n=!1){n?nR(e.loc,WL(t,60)):nR(e.loc,UL(t,62)+1),FL.inSFCRoot&&(e.children.length?e.innerLoc.end=sF({},e.children[e.children.length-1].loc.end):e.innerLoc.end=sF({},e.innerLoc.start),e.innerLoc.source=zL(e.innerLoc.start.offset,e.innerLoc.end.offset));let{tag:r,ns:i,children:a}=e;if(ML||(r===`slot`?e.tagType=2:KL(e)?e.tagType=3:qL(e)&&(e.tagType=1)),FL.inRCDATA||(e.children=XL(a)),i===0&&Q.isIgnoreNewlineTag(r)){let e=a[0];e&&e.type===2&&(e.content=e.content.replace(/^\r?\n/,``))}i===0&&Q.isPreTag(r)&&jL--,NL===e&&(ML=FL.inVPre=!1,NL=null),FL.inXML&&(PL[0]?PL[0].ns:Q.ns)===0&&(FL.inXML=!1);{let t=e.props;if(!FL.inSFCRoot&&UI(`COMPILER_NATIVE_TEMPLATE`,Q)&&e.tag===`template`&&!KL(e)){let t=PL[0]||TL,n=t.children.indexOf(e);t.children.splice(n,1,...e.children)}let n=t.find(e=>e.type===6&&e.name===`inline-template`);n&&WI(`COMPILER_INLINE_TEMPLATE`,Q,n.loc)&&e.children.length&&(n.value={type:2,content:zL(e.children[0].loc.start.offset,e.children[e.children.length-1].loc.end.offset),loc:n.loc})}}function UL(e,t){let n=e;for(;EL.charCodeAt(n)!==t&&n<EL.length-1;)n++;return n}function WL(e,t){let n=e;for(;EL.charCodeAt(n)!==t&&n>=0;)n--;return n}var GL=new Set([`if`,`else`,`else-if`,`for`,`slot`]);function KL({tag:e,props:t}){if(e===`template`){for(let e=0;e<t.length;e++)if(t[e].type===7&&GL.has(t[e].name))return!0}return!1}function qL({tag:e,props:t}){if(Q.isCustomElement(e))return!1;if(e===`component`||JL(e.charCodeAt(0))||YI(e)||Q.isBuiltInComponent&&Q.isBuiltInComponent(e)||Q.isNativeTag&&!Q.isNativeTag(e))return!0;for(let e=0;e<t.length;e++){let n=t[e];if(n.type===6){if(n.name===`is`&&n.value&&(n.value.content.startsWith(`vue:`)||WI(`COMPILER_IS_ON_ELEMENT`,Q,n.loc)))return!0}else if(n.name===`bind`&&sL(n.arg,`is`)&&WI(`COMPILER_IS_ON_ELEMENT`,Q,n.loc))return!0}return!1}function JL(e){return e>64&&e<91}var YL=/\r\n/g;function XL(e){let t=Q.whitespace!==`preserve`,n=!1;for(let r=0;r<e.length;r++){let i=e[r];if(i.type===2)if(jL)i.content=i.content.replace(YL,`
`);else if(xL(i.content)){let a=e[r-1]&&e[r-1].type,o=e[r+1]&&e[r+1].type;!a||!o||t&&(a===3&&(o===3||o===1)||a===1&&(o===3||o===1&&ZL(i.content)))?(n=!0,e[r]=null):i.content=` `}else t&&(i.content=QL(i.content))}return n?e.filter(Boolean):e}function ZL(e){for(let t=0;t<e.length;t++){let n=e.charCodeAt(t);if(n===10||n===13)return!0}return!1}function QL(e){let t=``,n=!1;for(let r=0;r<e.length;r++)LI(e.charCodeAt(r))?n||=(t+=` `,!0):(t+=e[r],n=!1);return t}function $L(e){(PL[0]||TL).children.push(e)}function eR(e,t){return{start:FL.getPos(e),end:t==null?t:FL.getPos(t),source:t==null?t:zL(e,t)}}function tR(e){return eR(e.start.offset,e.end.offset)}function nR(e,t){e.end=FL.getPos(t),e.source=zL(e.start.offset,t)}function rR(e){let t={type:6,name:e.rawName,nameLoc:eR(e.loc.start.offset,e.loc.start.offset+e.rawName.length),value:void 0,loc:e.loc};if(e.exp){let n=e.exp.loc;n.end.offset<e.loc.end.offset&&(n.start.offset--,n.start.column--,n.end.offset++,n.end.column++),t.value={type:2,content:e.exp.content,loc:n}}return t}function iR(e,t=!1,n,r=0,i=0){return Z(e,t,n,r)}function aR(e,t,n){Q.onError(qI(e,eR(t,t),void 0,n))}function oR(){FL.reset(),DL=null,$=null,OL=``,kL=-1,AL=-1,PL.length=0}function sR(e,t){if(oR(),EL=e,Q=sF({},wL),t){let e;for(e in t)t[e]!=null&&(Q[e]=t[e])}FL.mode=Q.parseMode===`html`?1:Q.parseMode===`sfc`?2:0,FL.inXML=Q.ns===1||Q.ns===2;let n=t&&t.delimiters;n&&(FL.delimiterOpen=zI(n[0]),FL.delimiterClose=zI(n[1]));let r=TL=bI([],e);return FL.parse(EL),r.loc=eR(0,e.length),r.children=XL(r.children),TL=null,r}function cR(e,t){uR(e,void 0,t,!!lR(e))}function lR(e){let t=e.children.filter(e=>e.type!==3);return t.length===1&&t[0].type===1&&!pL(t[0])?t[0]:null}function uR(e,t,n,r=!1,i=!1){let{children:a}=e,o=[];for(let t=0;t<a.length;t++){let s=a[t];if(s.type===1&&s.tagType===0){let e=r?0:dR(s,n);if(e>0){if(e>=2){s.codegenNode.patchFlag=-1,o.push(s);continue}}else{let e=s.codegenNode;if(e.type===13){let t=e.patchFlag;if((t===void 0||t===512||t===1)&&mR(s,n)>=2){let t=hR(s);t&&(e.props=n.hoist(t))}e.dynamicProps&&=n.hoist(e.dynamicProps)}}}else if(s.type===12&&(r?0:dR(s,n))>=2){s.codegenNode.type===14&&s.codegenNode.arguments.length>0&&s.codegenNode.arguments.push(`-1`),o.push(s);continue}if(s.type===1){let t=s.tagType===1;t&&n.scopes.vSlot++,uR(s,e,n,!1,i),t&&n.scopes.vSlot--}else if(s.type===11)uR(s,e,n,s.children.length===1,!0);else if(s.type===9)for(let t=0;t<s.branches.length;t++)uR(s.branches[t],e,n,s.branches[t].children.length===1,i)}let s=!1;if(o.length===a.length&&e.type===1){if(e.tagType===0&&e.codegenNode&&e.codegenNode.type===13&&cF(e.codegenNode.children))e.codegenNode.children=c(SI(e.codegenNode.children)),s=!0;else if(e.tagType===1&&e.codegenNode&&e.codegenNode.type===13&&e.codegenNode.children&&!cF(e.codegenNode.children)&&e.codegenNode.children.type===15){let t=l(e.codegenNode,`default`);t&&(t.returns=c(SI(t.returns)),s=!0)}else if(e.tagType===3&&t&&t.type===1&&t.tagType===1&&t.codegenNode&&t.codegenNode.type===13&&t.codegenNode.children&&!cF(t.codegenNode.children)&&t.codegenNode.children.type===15){let n=aL(e,`slot`,!0),r=n&&n.arg&&l(t.codegenNode,n.arg);r&&(r.returns=c(SI(r.returns)),s=!0)}}if(!s)for(let e of o)e.codegenNode=n.cache(e.codegenNode);function c(e){let t=n.cache(e);return t.needArraySpread=!0,t}function l(e,t){if(e.children&&!cF(e.children)&&e.children.type===15){let n=e.children.properties.find(e=>e.key===t||e.key.content===t);return n&&n.value}}o.length&&n.transformHoist&&n.transformHoist(a,n,e)}function dR(e,t){let{constantCache:n}=t;switch(e.type){case 1:if(e.tagType!==0)return 0;let r=n.get(e);if(r!==void 0)return r;let i=e.codegenNode;if(i.type!==13||i.isBlock&&e.tag!==`svg`&&e.tag!==`foreignObject`&&e.tag!==`math`)return 0;if(i.patchFlag===void 0){let r=3,a=mR(e,t);if(a===0)return n.set(e,0),0;a<r&&(r=a);for(let i=0;i<e.children.length;i++){let a=dR(e.children[i],t);if(a===0)return n.set(e,0),0;a<r&&(r=a)}if(r>1)for(let i=0;i<e.props.length;i++){let a=e.props[i];if(a.type===7&&a.name===`bind`&&a.exp){let i=dR(a.exp,t);if(i===0)return n.set(e,0),0;i<r&&(r=i)}}if(i.isBlock){for(let t=0;t<e.props.length;t++)if(e.props[t].type===7)return n.set(e,0),0;t.removeHelper(LF),t.removeHelper(MI(t.inSSR,i.isComponent)),i.isBlock=!1,t.helper(jI(t.inSSR,i.isComponent))}return n.set(e,r),r}else return n.set(e,0),0;case 2:case 3:return 3;case 9:case 11:case 10:return 0;case 5:case 12:return dR(e.content,t);case 4:return e.constType;case 8:let a=3;for(let n=0;n<e.children.length;n++){let r=e.children[n];if(lF(r)||uF(r))continue;let i=dR(r,t);if(i===0)return 0;i<a&&(a=i)}return a;case 20:return 2;default:return 0}}var fR=new Set([tI,nI,rI,iI]);function pR(e,t){if(e.type===14&&!lF(e.callee)&&fR.has(e.callee)){let n=e.arguments[0];if(n.type===4)return dR(n,t);if(n.type===14)return pR(n,t)}return 0}function mR(e,t){let n=3,r=hR(e);if(r&&r.type===15){let{properties:e}=r;for(let r=0;r<e.length;r++){let{key:i,value:a}=e[r],o=dR(i,t);if(o===0)return o;o<n&&(n=o);let s;if(s=a.type===4?dR(a,t):a.type===14?pR(a,t):0,s===0)return s;s<n&&(n=s)}}return n}function hR(e){let t=e.codegenNode;if(t.type===13)return t.props}function gR(e,{filename:t=``,prefixIdentifiers:n=!1,hoistStatic:r=!1,hmr:i=!1,cacheHandlers:a=!1,nodeTransforms:o=[],directiveTransforms:s={},transformHoist:c=null,isBuiltInComponent:l=iF,isCustomElement:u=iF,expressionPlugins:d=[],scopeId:f=null,slotted:p=!0,ssr:m=!1,inSSR:h=!1,ssrCssVars:g=``,bindingMetadata:_=rF,inline:v=!1,isTS:y=!1,onError:b=GI,onWarn:x=KI,compatConfig:S}){let C=t.replace(/\?.*$/,``).match(/([^/\\]+)\.\w+$/),w={filename:t,selfName:C&&_F(gF(C[1])),prefixIdentifiers:n,hoistStatic:r,hmr:i,cacheHandlers:a,nodeTransforms:o,directiveTransforms:s,transformHoist:c,isBuiltInComponent:l,isCustomElement:u,expressionPlugins:d,scopeId:f,slotted:p,ssr:m,inSSR:h,ssrCssVars:g,bindingMetadata:_,inline:v,isTS:y,onError:b,onWarn:x,compatConfig:S,root:e,helpers:new Map,components:new Set,directives:new Set,hoists:[],imports:[],cached:[],constantCache:new WeakMap,temps:0,identifiers:Object.create(null),scopes:{vFor:0,vSlot:0,vPre:0,vOnce:0},parent:null,grandParent:null,currentNode:e,childIndex:0,inVOnce:!1,helper(e){let t=w.helpers.get(e)||0;return w.helpers.set(e,t+1),e},removeHelper(e){let t=w.helpers.get(e);if(t){let n=t-1;n?w.helpers.set(e,n):w.helpers.delete(e)}},helperString(e){return`_${_I[w.helper(e)]}`},replaceNode(e){w.parent.children[w.childIndex]=w.currentNode=e},removeNode(e){let t=w.parent.children,n=e?t.indexOf(e):w.currentNode?w.childIndex:-1;!e||e===w.currentNode?(w.currentNode=null,w.onNodeRemoved()):w.childIndex>n&&(w.childIndex--,w.onNodeRemoved()),w.parent.children.splice(n,1)},onNodeRemoved:iF,addIdentifiers(e){},removeIdentifiers(e){},hoist(e){lF(e)&&(e=Z(e)),w.hoists.push(e);let t=Z(`_hoisted_${w.hoists.length}`,!1,e.loc,2);return t.hoisted=e,t},cache(e,t=!1,n=!1){let r=kI(w.cached.length,e,t,n);return w.cached.push(r),r}};return w.filters=new Set,w}function _R(e,t){let n=gR(e,t);bR(e,n),t.hoistStatic&&cR(e,n),t.ssr||vR(e,n),e.helpers=new Set([...n.helpers.keys()]),e.components=[...n.components],e.directives=[...n.directives],e.imports=n.imports,e.hoists=n.hoists,e.temps=n.temps,e.cached=n.cached,e.transformed=!0,e.filters=[...n.filters]}function vR(e,t){let{helper:n}=t,{children:r}=e;if(r.length===1){let n=lR(e);if(n&&n.codegenNode){let r=n.codegenNode;r.type===13&&NI(r,t),e.codegenNode=r}else e.codegenNode=r[0]}else r.length>1&&(e.codegenNode=xI(t,n(MF),void 0,e.children,64,void 0,void 0,!0,void 0,!1))}function yR(e,t){let n=0,r=()=>{n--};for(;n<e.children.length;n++){let i=e.children[n];lF(i)||(t.grandParent=t.parent,t.parent=e,t.childIndex=n,t.onNodeRemoved=r,bR(i,t))}}function bR(e,t){t.currentNode=e;let{nodeTransforms:n}=t,r=[];for(let i=0;i<n.length;i++){let a=n[i](e,t);if(a&&(cF(a)?r.push(...a):r.push(a)),t.currentNode)e=t.currentNode;else return}switch(e.type){case 3:t.ssr||t.helper(HF);break;case 5:t.ssr||t.helper($F);break;case 9:for(let n=0;n<e.branches.length;n++)bR(e.branches[n],t);break;case 10:case 11:case 1:case 0:yR(e,t);break}t.currentNode=e;let i=r.length;for(;i--;)r[i]()}function xR(e,t){let n=lF(e)?t=>t===e:t=>e.test(t);return(e,r)=>{if(e.type===1){let{props:i}=e;if(e.tagType===3&&i.some(dL))return;let a=[];for(let o=0;o<i.length;o++){let s=i[o];if(s.type===7&&n(s.name)){i.splice(o,1),o--;let n=t(e,s,r);n&&a.push(n)}}return a}}}var SR=`/*@__PURE__*/`,CR=e=>`${_I[e]}: _${_I[e]}`;function wR(e,{mode:t=`function`,prefixIdentifiers:n=t===`module`,sourceMap:r=!1,filename:i=`template.vue.html`,scopeId:a=null,optimizeImports:o=!1,runtimeGlobalName:s=`Vue`,runtimeModuleName:c=`vue`,ssrRuntimeModuleName:l=`vue/server-renderer`,ssr:u=!1,isTS:d=!1,inSSR:f=!1}){let p={mode:t,prefixIdentifiers:n,sourceMap:r,filename:i,scopeId:a,optimizeImports:o,runtimeGlobalName:s,runtimeModuleName:c,ssrRuntimeModuleName:l,ssr:u,isTS:d,inSSR:f,source:e.source,code:``,column:1,line:1,offset:0,indentLevel:0,pure:!1,map:void 0,helper(e){return`_${_I[e]}`},push(e,t=-2,n){p.code+=e},indent(){m(++p.indentLevel)},deindent(e=!1){e?--p.indentLevel:m(--p.indentLevel)},newline(){m(p.indentLevel)}};function m(e){p.push(`
`+`  `.repeat(e),0)}return p}function TR(e,t={}){let n=wR(e,t);t.onContextCreated&&t.onContextCreated(n);let{mode:r,push:i,prefixIdentifiers:a,indent:o,deindent:s,newline:c,scopeId:l,ssr:u}=n,d=Array.from(e.helpers),f=d.length>0,p=!a&&r!==`module`;if(ER(e,n),i(`function ${u?`ssrRender`:`render`}(${(u?[`_ctx`,`_push`,`_parent`,`_attrs`]:[`_ctx`,`_cache`]).join(`, `)}) {`),o(),p&&(i(`with (_ctx) {`),o(),f&&(i(`const { ${d.map(CR).join(`, `)} } = _Vue
`,-1),c())),e.components.length&&(DR(e.components,`component`,n),(e.directives.length||e.temps>0)&&c()),e.directives.length&&(DR(e.directives,`directive`,n),e.temps>0&&c()),e.filters&&e.filters.length&&(c(),DR(e.filters,`filter`,n),c()),e.temps>0){i(`let `);for(let t=0;t<e.temps;t++)i(`${t>0?`, `:``}_temp${t}`)}return(e.components.length||e.directives.length||e.temps)&&(i(`
`,0),c()),u||i(`return `),e.codegenNode?jR(e.codegenNode,n):i(`null`),p&&(s(),i(`}`)),s(),i(`}`),{ast:e,code:n.code,preamble:``,map:n.map?n.map.toJSON():void 0}}function ER(e,t){let{ssr:n,prefixIdentifiers:r,push:i,newline:a,runtimeModuleName:o,runtimeGlobalName:s,ssrRuntimeModuleName:c}=t,l=s,u=Array.from(e.helpers);u.length>0&&(i(`const _Vue = ${l}
`,-1),e.hoists.length&&i(`const { ${[BF,VF,HF,UF,WF].filter(e=>u.includes(e)).map(CR).join(`, `)} } = _Vue
`,-1)),OR(e.hoists,t),a(),i(`return `)}function DR(e,t,{helper:n,push:r,newline:i,isTS:a}){let o=n(t===`filter`?JF:t===`component`?GF:qF);for(let n=0;n<e.length;n++){let s=e[n],c=s.endsWith(`__self`);c&&(s=s.slice(0,-6)),r(`const ${vL(s,t)} = ${o}(${JSON.stringify(s)}${c?`, true`:``})${a?`!`:``}`),n<e.length-1&&i()}}function OR(e,t){if(!e.length)return;t.pure=!0;let{push:n,newline:r}=t;r();for(let i=0;i<e.length;i++){let a=e[i];a&&(n(`const _hoisted_${i+1} = `),jR(a,t),r())}t.pure=!1}function kR(e,t){let n=e.length>3||!1;t.push(`[`),n&&t.indent(),AR(e,t,n),n&&t.deindent(),t.push(`]`)}function AR(e,t,n=!1,r=!0){let{push:i,newline:a}=t;for(let o=0;o<e.length;o++){let s=e[o];lF(s)?i(s,-3):cF(s)?kR(s,t):jR(s,t),o<e.length-1&&(n?(r&&i(`,`),a()):r&&i(`, `))}}function jR(e,t){if(lF(e)){t.push(e,-3);return}if(uF(e)){t.push(t.helper(e));return}switch(e.type){case 1:case 9:case 11:jR(e.codegenNode,t);break;case 2:MR(e,t);break;case 4:NR(e,t);break;case 5:PR(e,t);break;case 12:jR(e.codegenNode,t);break;case 8:FR(e,t);break;case 3:LR(e,t);break;case 13:RR(e,t);break;case 14:BR(e,t);break;case 15:VR(e,t);break;case 17:HR(e,t);break;case 18:UR(e,t);break;case 19:WR(e,t);break;case 20:GR(e,t);break;case 21:AR(e.body,t,!0,!1);break;case 22:break;case 23:break;case 24:break;case 25:break;case 26:break;case 10:break;default:}}function MR(e,t){t.push(JSON.stringify(e.content),-3,e)}function NR(e,t){let{content:n,isStatic:r}=e;t.push(r?JSON.stringify(n):n,-3,e)}function PR(e,t){let{push:n,helper:r,pure:i}=t;i&&n(SR),n(`${r($F)}(`),jR(e.content,t),n(`)`)}function FR(e,t){for(let n=0;n<e.children.length;n++){let r=e.children[n];lF(r)?t.push(r,-3):jR(r,t)}}function IR(e,t){let{push:n}=t;e.type===8?(n(`[`),FR(e,t),n(`]`)):e.isStatic?n(ZI(e.content)?e.content:JSON.stringify(e.content),-2,e):n(`[${e.content}]`,-3,e)}function LR(e,t){let{push:n,helper:r,pure:i}=t;i&&n(SR),n(`${r(HF)}(${JSON.stringify(e.content)})`,-3,e)}function RR(e,t){let{push:n,helper:r,pure:i}=t,{tag:a,props:o,children:s,patchFlag:c,dynamicProps:l,directives:u,isBlock:d,disableTracking:f,isComponent:p}=e,m;c&&(m=String(c)),u&&n(r(YF)+`(`),d&&n(`(${r(LF)}(${f?`true`:``}), `),i&&n(SR),n(r(d?MI(t.inSSR,p):jI(t.inSSR,p))+`(`,-2,e),AR(zR([a,o,s,m,l]),t),n(`)`),d&&n(`)`),u&&(n(`, `),jR(u,t),n(`)`))}function zR(e){let t=e.length;for(;t--&&e[t]==null;);return e.slice(0,t+1).map(e=>e||`null`)}function BR(e,t){let{push:n,helper:r,pure:i}=t,a=lF(e.callee)?e.callee:r(e.callee);i&&n(SR),n(a+`(`,-2,e),AR(e.arguments,t),n(`)`)}function VR(e,t){let{push:n,indent:r,deindent:i,newline:a}=t,{properties:o}=e;if(!o.length){n(`{}`,-2,e);return}let s=o.length>1||!1;n(s?`{`:`{ `),s&&r();for(let e=0;e<o.length;e++){let{key:r,value:i}=o[e];IR(r,t),n(`: `),jR(i,t),e<o.length-1&&(n(`,`),a())}s&&i(),n(s?`}`:` }`)}function HR(e,t){kR(e.elements,t)}function UR(e,t){let{push:n,indent:r,deindent:i}=t,{params:a,returns:o,body:s,newline:c,isSlot:l}=e;l&&n(`_${_I[fI]}(`),n(`(`,-2,e),cF(a)?AR(a,t):a&&jR(a,t),n(`) => `),(c||s)&&(n(`{`),r()),o?(c&&n(`return `),cF(o)?kR(o,t):jR(o,t)):s&&jR(s,t),(c||s)&&(i(),n(`}`)),l&&(e.isNonScopedSlot&&n(`, undefined, true`),n(`)`))}function WR(e,t){let{test:n,consequent:r,alternate:i,newline:a}=e,{push:o,indent:s,deindent:c,newline:l}=t;if(n.type===4){let e=!ZI(n.content);e&&o(`(`),NR(n,t),e&&o(`)`)}else o(`(`),jR(n,t),o(`)`);a&&s(),t.indentLevel++,a||o(` `),o(`? `),jR(r,t),t.indentLevel--,a&&l(),a||o(` `),o(`: `);let u=i.type===19;u||t.indentLevel++,jR(i,t),u||t.indentLevel--,a&&c(!0)}function GR(e,t){let{push:n,helper:r,indent:i,deindent:a,newline:o}=t,{needPauseTracking:s,needArraySpread:c}=e;c&&n(`[...(`),n(`_cache[${e.index}] || (`),s&&(i(),n(`${r(lI)}(-1`),e.inVOnce&&n(`, true`),n(`),`),o(),n(`(`)),n(`_cache[${e.index}] = `),jR(e.value,t),s&&(n(`).cacheIndex = ${e.index},`),o(),n(`${r(lI)}(1),`),o(),n(`_cache[${e.index}]`),a()),n(`)`),c&&n(`)]`)}RegExp(`\\b`+`arguments,await,break,case,catch,class,const,continue,debugger,default,delete,do,else,export,extends,finally,for,function,if,import,let,new,return,super,switch,throw,try,var,void,while,with,yield`.split(`,`).join(`\\b|\\b`)+`\\b`);var KR=xR(/^(?:if|else|else-if)$/,(e,t,n)=>qR(e,t,n,(e,t,r)=>{let i=n.parent.children,a=i.indexOf(e),o=0;for(;a-->=0;){let e=i[a];e&&e.type===9&&(o+=e.branches.length)}return()=>{if(r)e.codegenNode=YR(t,o,n);else{let r=ZR(e.codegenNode);r.alternate=YR(t,o+e.branches.length-1,n)}}}));function qR(e,t,n,r){if(t.name!==`else`&&(!t.exp||!t.exp.content.trim())){let r=t.exp?t.exp.loc:e.loc;n.onError(qI(28,t.loc)),t.exp=Z(`true`,!1,r)}if(t.name===`if`){let i=JR(e,t),a={type:9,loc:tR(e.loc),branches:[i]};if(n.replaceNode(a),r)return r(a,i,!0)}else{let i=n.parent.children,a=i.indexOf(e);for(;a-->=-1;){let o=i[a];if(o&&CL(o)){n.removeNode(o);continue}if(o&&o.type===9){(t.name===`else-if`||t.name===`else`)&&o.branches[o.branches.length-1].condition===void 0&&n.onError(qI(30,e.loc)),n.removeNode();let i=JR(e,t);o.branches.push(i);let a=r&&r(o,i,!1);bR(i,n),a&&a(),n.currentNode=null}else n.onError(qI(30,e.loc));break}}}function JR(e,t){let n=e.tagType===3;return{type:10,loc:e.loc,condition:t.name===`else`?void 0:t.exp,children:n&&!aL(e,`for`)?e.children:[e],userKey:oL(e,`key`),isTemplateIf:n}}function YR(e,t,n){return e.condition?OI(e.condition,XR(e,t,n),EI(n.helper(HF),[`""`,`true`])):XR(e,t,n)}function XR(e,t,n){let{helper:r}=n,i=wI(`key`,Z(`${t}`,!1,yI,2)),{children:a}=e,o=a[0];if(a.length!==1||o.type!==1)if(a.length===1&&o.type===11){let e=o.codegenNode;return gL(e,i,n),e}else return xI(n,r(MF),CI([i]),a,64,void 0,void 0,!0,!1,!1,e.loc);else{let e=o.codegenNode,t=yL(e);return t.type===13&&NI(t,n),gL(t,i,n),e}}function ZR(e){for(;;)if(e.type===19)if(e.alternate.type===19)e=e.alternate;else return e;else e.type===20&&(e=e.value)}var QR=xR(`for`,(e,t,n)=>{let{helper:r,removeHelper:i}=n;return $R(e,t,n,t=>{let a=EI(r(XF),[t.source]),o=fL(e),s=aL(e,`memo`),c=oL(e,`key`,!1,!0);c&&c.type;let l=c&&(c.type===6?c.value?Z(c.value.content,!0):void 0:c.exp),u=c&&l?wI(`key`,l):null,d=t.source.type===4&&t.source.constType>0,f=d?64:c?128:256;return t.codegenNode=xI(n,r(MF),void 0,a,f,void 0,void 0,!0,!d,!1,e.loc),()=>{let c,{children:f}=t,p=f.length!==1||f[0].type!==1,m=pL(e)?e:o&&e.children.length===1&&pL(e.children[0])?e.children[0]:null;if(m?(c=m.codegenNode,o&&u&&gL(c,u,n)):p?c=xI(n,r(MF),u?CI([u]):void 0,e.children,64,void 0,void 0,!0,void 0,!1):(c=f[0].codegenNode,o&&u&&gL(c,u,n),c.isBlock!==!d&&(c.isBlock?(i(LF),i(MI(n.inSSR,c.isComponent))):i(jI(n.inSSR,c.isComponent))),c.isBlock=!d,c.isBlock?(r(LF),r(MI(n.inSSR,c.isComponent))):r(jI(n.inSSR,c.isComponent))),s){let e=DI(tz(t.parseResult,[Z(`_cached`)]));e.body=AI([TI([`const _memo = (`,s.exp,`)`]),TI([`if (_cached && _cached.el`,...l?[` && _cached.key === `,l]:[],` && ${n.helperString(gI)}(_cached, _memo)) return _cached`]),TI([`const _item = `,c]),Z(`_item.memo = _memo`),Z(`return _item`)]),a.arguments.push(e,Z(`_cache`),Z(String(n.cached.length))),n.cached.push(null)}else a.arguments.push(DI(tz(t.parseResult),c,!0))}})});function $R(e,t,n,r){if(!t.exp){n.onError(qI(31,t.loc));return}let i=t.forParseResult;if(!i){n.onError(qI(32,t.loc));return}ez(i,n);let{addIdentifiers:a,removeIdentifiers:o,scopes:s}=n,{source:c,value:l,key:u,index:d}=i,f={type:11,loc:t.loc,source:c,valueAlias:l,keyAlias:u,objectIndexAlias:d,parseResult:i,children:fL(e)?e.children:[e]};n.replaceNode(f),s.vFor++;let p=r&&r(f);return()=>{s.vFor--,p&&p()}}function ez(e,t){e.finalized||=!0}function tz({value:e,key:t,index:n},r=[]){return nz([e,t,n,...r])}function nz(e){let t=e.length;for(;t--&&!e[t];);return e.slice(0,t+1).map((e,t)=>e||Z(`_`.repeat(t+1),!1))}var rz=Z(`undefined`,!1),iz=(e,t)=>{if(e.type===1&&(e.tagType===1||e.tagType===3)){let n=aL(e,`slot`);if(n)return n.exp,t.scopes.vSlot++,()=>{t.scopes.vSlot--}}},az=(e,t,n,r)=>DI(e,n,!1,!0,n.length?n[0].loc:r);function oz(e,t,n=az){t.helper(fI);let{children:r,loc:i}=e,a=[],o=[],s=t.scopes.vSlot>0||t.scopes.vFor>0,c=aL(e,`slot`,!0);if(c){let{arg:e,exp:t}=c;e&&!JI(e)&&(s=!0),a.push(wI(e||Z(`default`,!0),n(t,void 0,r,i)))}let l=!1,u=!1,d=[],f=new Set,p=0;for(let e=0;e<r.length;e++){let i=r[e],m;if(!fL(i)||!(m=aL(i,`slot`,!0))){i.type!==3&&d.push(i);continue}if(c){t.onError(qI(37,m.loc));break}l=!0;let{children:h,loc:g}=i,{arg:_=Z(`default`,!0),exp:v,loc:y}=m,b;JI(_)?b=_?_.content:`default`:s=!0;let x=aL(i,`for`),S=n(v,x,h,g),C,w;if(C=aL(i,`if`))s=!0,o.push(OI(C.exp,sz(_,S,p++),rz));else if(w=aL(i,/^else(?:-if)?$/,!0)){let n=e,i;for(;n--&&(i=r[n],CL(i)););if(i&&fL(i)&&aL(i,/^(?:else-)?if$/)){let e=o[o.length-1];for(;e.alternate.type===19;)e=e.alternate;e.alternate=w.exp?OI(w.exp,sz(_,S,p++),rz):sz(_,S,p++)}else t.onError(qI(30,w.loc))}else if(x){s=!0;let e=x.forParseResult;e?(ez(e,t),o.push(EI(t.helper(XF),[e.source,DI(tz(e),sz(_,S),!0)]))):t.onError(qI(32,x.loc))}else{if(b){if(f.has(b)){t.onError(qI(38,y));continue}f.add(b),b===`default`&&(u=!0)}a.push(wI(_,S))}}if(!c){let e=(e,r)=>{let a=n(e,void 0,r,i);return t.compatConfig&&(a.isNonScopedSlot=!0),wI(`default`,a)};l?d.length&&!d.every(SL)&&(u?t.onError(qI(39,d[0].loc)):a.push(e(void 0,d))):a.push(e(void 0,r))}let m=s?2:cz(e.children)?3:1,h=CI(a.concat(wI(`_`,Z(m+``,!1))),i);return o.length&&(h=EI(t.helper(QF),[h,SI(o)])),{slots:h,hasDynamicSlots:s}}function sz(e,t,n){let r=[wI(`name`,e),wI(`fn`,t)];return n!=null&&r.push(wI(`key`,Z(String(n),!0))),CI(r)}function cz(e){for(let t=0;t<e.length;t++){let n=e[t];switch(n.type){case 1:if(n.tagType===2||cz(n.children))return!0;break;case 9:if(cz(n.branches))return!0;break;case 10:case 11:if(cz(n.children))return!0;break}}return!1}var lz=new WeakMap,uz=(e,t)=>function(){if(e=t.currentNode,!(e.type===1&&(e.tagType===0||e.tagType===1)))return;let{tag:n,props:r}=e,i=e.tagType===1,a=i?dz(e,t):`"${n}"`,o=dF(a)&&a.callee===KF,s,c,l=0,u,d,f,p=o||a===NF||a===PF||!i&&(n===`svg`||n===`foreignObject`||n===`math`);if(r.length>0){let n=fz(e,t,void 0,i,o);s=n.props,l=n.patchFlag,d=n.dynamicPropNames;let r=n.directives;f=r&&r.length?SI(r.map(e=>hz(e,t))):void 0,n.shouldUseBlock&&(p=!0)}if(e.children.length>0)if(a===FF&&(p=!0,l|=1024),i&&a!==NF&&a!==FF){let{slots:n,hasDynamicSlots:r}=oz(e,t);c=n,r&&(l|=1024)}else if(e.children.length===1&&a!==NF){let n=e.children[0],r=n.type,i=r===5||r===8;i&&dR(n,t)===0&&(l|=1),c=i||r===2?n:e.children}else c=e.children;d&&d.length&&(u=gz(d)),e.codegenNode=xI(t,a,s,c,l===0?void 0:l,u,f,!!p,!1,i,e.loc)};function dz(e,t,n=!1){let{tag:r}=e,i=_z(r),a=oL(e,`is`,!1,!0);if(a)if(i||UI(`COMPILER_IS_ON_ELEMENT`,t)){let e;if(a.type===6?e=a.value&&Z(a.value.content,!0):(e=a.exp,e||=Z(`is`,!1,a.arg.loc)),e)return EI(t.helper(KF),[e])}else a.type===6&&a.value.content.startsWith(`vue:`)&&(r=a.value.content.slice(4));let o=YI(r)||t.isBuiltInComponent(r);return o?(n||t.helper(o),o):(t.helper(GF),t.components.add(r),vL(r,`component`))}function fz(e,t,n=e.props,r,i,a=!1){let{tag:o,loc:s,children:c}=e,l=[],u=[],d=[],f=c.length>0,p=!1,m=0,h=!1,g=!1,_=!1,v=!1,y=!1,b=!1,x=[],S=e=>{l.length&&(u.push(CI(pz(l),s)),l=[]),e&&u.push(e)},C=()=>{t.scopes.vFor>0&&l.push(wI(Z(`ref_for`,!0),Z(`true`)))},w=({key:e,value:n})=>{if(JI(e)){let a=e.content,o=oF(a);if(o&&(!r||i)&&a.toLowerCase()!==`onclick`&&a!==`onUpdate:modelValue`&&!fF(a)&&(v=!0),o&&fF(a)&&(b=!0),o&&n.type===14&&(n=n.arguments[0]),n.type===20||(n.type===4||n.type===8)&&dR(n,t)>0)return;a===`ref`?h=!0:a===`class`?g=!0:a===`style`?_=!0:a!==`key`&&!x.includes(a)&&x.push(a),r&&(a===`class`||a===`style`)&&!x.includes(a)&&x.push(a)}else y=!0};for(let i=0;i<n.length;i++){let c=n[i];if(c.type===6){let{loc:e,name:n,nameLoc:r,value:i}=c;if(n===`ref`&&(h=!0,C()),n===`is`&&(_z(o)||i&&i.content.startsWith(`vue:`)||UI(`COMPILER_IS_ON_ELEMENT`,t)))continue;l.push(wI(Z(n,!0,r),Z(i?i.content:``,!0,i?i.loc:e)))}else{let{name:n,arg:i,exp:h,loc:g,modifiers:_}=c,v=n===`bind`,b=n===`on`;if(n===`slot`){r||t.onError(qI(40,g));continue}if(n===`once`||n===`memo`||n===`is`||v&&sL(i,`is`)&&(_z(o)||UI(`COMPILER_IS_ON_ELEMENT`,t))||b&&a)continue;if((v&&sL(i,`key`)||b&&f&&sL(i,`vue:before-update`))&&(p=!0),v&&sL(i,`ref`)&&C(),!i&&(v||b)){if(y=!0,h)if(v){if(S(),UI(`COMPILER_V_BIND_OBJECT_ORDER`,t)){u.unshift(h);continue}C(),S(),u.push(h)}else S({type:14,loc:g,callee:t.helper(aI),arguments:r?[h]:[h,`true`]});else t.onError(qI(v?34:35,g));continue}v&&_.some(e=>e.content===`prop`)&&(m|=32);let x=t.directiveTransforms[n];if(x){let{props:n,needRuntime:r}=x(c,e,t);!a&&n.forEach(w),b&&i&&!JI(i)?S(CI(n,s)):l.push(...n),r&&(d.push(c),uF(r)&&lz.set(c,r))}else pF(n)||(d.push(c),f&&(p=!0))}}let T;if(u.length?(S(),T=u.length>1?EI(t.helper(eI),u,s):u[0]):l.length&&(T=CI(pz(l),s)),y?m|=16:(g&&!r&&(m|=2),_&&!r&&(m|=4),x.length&&(m|=8),v&&(m|=32)),!p&&(m===0||m===32)&&(h||b||d.length>0)&&(m|=512),!t.inSSR&&T)switch(T.type){case 15:let e=-1,n=-1,r=!1;for(let t=0;t<T.properties.length;t++){let i=T.properties[t].key;JI(i)?i.content===`class`?e=t:i.content===`style`&&(n=t):i.isHandlerKey||(r=!0)}let i=T.properties[e],a=T.properties[n];r?T=EI(t.helper(rI),[T]):(i&&!JI(i.value)&&(i.value=EI(t.helper(tI),[i.value])),a&&(_||a.value.type===4&&a.value.content.trim()[0]===`[`||a.value.type===17)&&(a.value=EI(t.helper(nI),[a.value])));break;case 14:break;default:T=EI(t.helper(rI),[EI(t.helper(iI),[T])]);break}return{props:T,directives:d,patchFlag:m,dynamicPropNames:x,shouldUseBlock:p}}function pz(e){let t=new Map,n=[];for(let r=0;r<e.length;r++){let i=e[r];if(i.key.type===8||!i.key.isStatic){n.push(i);continue}let a=i.key.content,o=t.get(a);o?(a===`style`||a===`class`||oF(a))&&mz(o,i):(t.set(a,i),n.push(i))}return n}function mz(e,t){e.value.type===17?e.value.elements.push(t.value):e.value=SI([e.value,t.value],e.loc)}function hz(e,t){let n=[],r=lz.get(e);r?n.push(t.helperString(r)):(t.helper(qF),t.directives.add(e.name),n.push(vL(e.name,`directive`)));let{loc:i}=e;if(e.exp&&n.push(e.exp),e.arg&&(e.exp||n.push(`void 0`),n.push(e.arg)),Object.keys(e.modifiers).length){e.arg||(e.exp||n.push(`void 0`),n.push(`void 0`));let t=Z(`true`,!1,i);n.push(CI(e.modifiers.map(e=>wI(e,t)),i))}return SI(n,e.loc)}function gz(e){let t=`[`;for(let n=0,r=e.length;n<r;n++)t+=JSON.stringify(e[n]),n<r-1&&(t+=`, `);return t+`]`}function _z(e){return e===`component`||e===`Component`}var vz=(e,t)=>{if(pL(e)){let{children:n,loc:r}=e,{slotName:i,slotProps:a}=yz(e,t),o=[t.prefixIdentifiers?`_ctx.$slots`:`$slots`,i,`{}`,`undefined`,`true`],s=2;a&&(o[2]=a,s=3),n.length&&(o[3]=DI([],n,!1,!1,r),s=4),t.scopeId&&!t.slotted&&(s=5),o.splice(s),e.codegenNode=EI(t.helper(ZF),o,r)}};function yz(e,t){let n=`"default"`,r,i=[];for(let t=0;t<e.props.length;t++){let r=e.props[t];r.type===6?r.value&&(r.name===`name`?n=JSON.stringify(r.value.content):(r.name=gF(r.name),i.push(r))):r.name===`bind`&&sL(r.arg,`name`)?r.exp?n=r.exp:r.arg&&r.arg.type===4&&(n=r.exp=Z(gF(r.arg.content),!1,r.arg.loc)):(r.name===`bind`&&r.arg&&JI(r.arg)&&(r.arg.content=gF(r.arg.content)),i.push(r))}if(i.length>0){let{props:n,directives:a}=fz(e,t,i,!1,!1);r=n,a.length&&t.onError(qI(36,a[0].loc))}return{slotName:n,slotProps:r}}var bz=(e,t,n,r)=>{let{loc:i,modifiers:a,arg:o}=e;!e.exp&&!a.length&&n.onError(qI(35,i));let s;if(o.type===4)if(o.isStatic){let e=o.content;e.startsWith(`vue:`)&&(e=`vnode-${e.slice(4)}`),s=Z(t.tagType!==0||e.startsWith(`vnode`)||!/[A-Z]/.test(e)?vF(gF(e)):`on:${e}`,!0,o.loc)}else s=TI([`${n.helperString(cI)}(`,o,`)`]);else s=o,s.children.unshift(`${n.helperString(cI)}(`),s.children.push(`)`);let c=e.exp;c&&!c.content.trim()&&(c=void 0);let l=n.cacheHandlers&&!c&&!n.inVOnce;if(c){let e=nL(c),t=!(e||iL(c)),n=c.content.includes(`;`);(t||l&&e)&&(c=TI([`${t?`$event`:`(...args)`} => ${n?`{`:`(`}`,c,n?`}`:`)`]))}let u={props:[wI(s,c||Z(`() => {}`,!1,i))]};return r&&(u=r(u)),l&&(u.props[0].value=n.cache(u.props[0].value)),u.props.forEach(e=>e.key.isHandlerKey=!0),u},xz=(e,t,n)=>{let{modifiers:r,loc:i}=e,a=e.arg,{exp:o}=e;return o&&o.type===4&&!o.content.trim()&&(o=void 0),a.type===4?a.isStatic||(a.content=a.content?`${a.content} || ""`:`""`):(a.children.unshift(`(`),a.children.push(`) || ""`)),r.some(e=>e.content===`camel`)&&(a.type===4?a.isStatic?a.content=gF(a.content):a.content=`${n.helperString(oI)}(${a.content})`:(a.children.unshift(`${n.helperString(oI)}(`),a.children.push(`)`))),n.inSSR||(r.some(e=>e.content===`prop`)&&Sz(a,`.`),r.some(e=>e.content===`attr`)&&Sz(a,`^`)),{props:[wI(a,o)]}},Sz=(e,t)=>{e.type===4?e.isStatic?e.content=t+e.content:e.content=`\`${t}\${${e.content}}\``:(e.children.unshift(`'${t}' + (`),e.children.push(`)`))},Cz=(e,t)=>{if(e.type===0||e.type===1||e.type===11||e.type===10)return()=>{let n=e.children,r,i=!1;for(let e=0;e<n.length;e++){let t=n[e];if(lL(t)){i=!0;for(let i=e+1;i<n.length;i++){let a=n[i];if(lL(a))r||=n[e]=TI([t],t.loc),r.children.push(` + `,a),n.splice(i,1),i--;else{r=void 0;break}}}}if(!(!i||n.length===1&&(e.type===0||e.type===1&&e.tagType===0&&!e.props.find(e=>e.type===7&&!t.directiveTransforms[e.name])&&e.tag!==`template`)))for(let e=0;e<n.length;e++){let r=n[e];if(lL(r)||r.type===8){let i=[];(r.type!==2||r.content!==` `)&&i.push(r),!t.ssr&&dR(r,t)===0&&i.push(`1`),n[e]={type:12,content:r,loc:r.loc,codegenNode:EI(t.helper(UF),i)}}}}},wz=new WeakSet,Tz=(e,t)=>{if(e.type===1&&aL(e,`once`,!0))return wz.has(e)||t.inVOnce||t.inSSR?void 0:(wz.add(e),t.inVOnce=!0,t.helper(lI),()=>{t.inVOnce=!1;let e=t.currentNode;e.codegenNode&&=t.cache(e.codegenNode,!0,!0)})},Ez=(e,t,n)=>{let{exp:r,arg:i}=e;if(!r)return n.onError(qI(41,e.loc)),Dz();let a=r.loc.source.trim(),o=r.type===4?r.content:a,s=n.bindingMetadata[a];if(s===`props`||s===`props-aliased`)return n.onError(qI(44,r.loc)),Dz();if(s===`literal-const`||s===`setup-const`)return n.onError(qI(45,r.loc)),Dz();if(!o.trim()||!nL(r))return n.onError(qI(42,r.loc)),Dz();let c=i||Z(`modelValue`,!0),l=i?JI(i)?`onUpdate:${gF(i.content)}`:TI([`"onUpdate:" + `,i]):`onUpdate:modelValue`,u;u=TI([`${n.isTS?`($event: any)`:`$event`} => ((`,r,`) = $event)`]);let d=[wI(c,e.exp),wI(l,u)];if(e.modifiers.length&&t.tagType===1){let t=e.modifiers.map(e=>e.content).map(e=>(ZI(e)?e:JSON.stringify(e))+`: true`).join(`, `),n=i?JI(i)?`${i.content}Modifiers`:TI([i,` + "Modifiers"`]):`modelModifiers`;d.push(wI(n,Z(`{ ${t} }`,!1,e.loc,2)))}return Dz(d)};function Dz(e=[]){return{props:e}}var Oz=/[\w).+\-_$\]]/,kz=(e,t)=>{UI(`COMPILER_FILTERS`,t)&&(e.type===5?Az(e.content,t):e.type===1&&e.props.forEach(e=>{e.type===7&&e.name!==`for`&&e.exp&&Az(e.exp,t)}))};function Az(e,t){if(e.type===4)jz(e,t);else for(let n=0;n<e.children.length;n++){let r=e.children[n];typeof r==`object`&&(r.type===4?jz(r,t):r.type===8?Az(e,t):r.type===5&&Az(r.content,t))}}function jz(e,t){let n=e.content,r=!1,i=!1,a=!1,o=!1,s=0,c=0,l=0,u=0,d,f,p,m,h=[];for(p=0;p<n.length;p++)if(f=d,d=n.charCodeAt(p),r)d===39&&f!==92&&(r=!1);else if(i)d===34&&f!==92&&(i=!1);else if(a)d===96&&f!==92&&(a=!1);else if(o)d===47&&f!==92&&(o=!1);else if(d===124&&n.charCodeAt(p+1)!==124&&n.charCodeAt(p-1)!==124&&!s&&!c&&!l)m===void 0?(u=p+1,m=n.slice(0,p).trim()):g();else{switch(d){case 34:i=!0;break;case 39:r=!0;break;case 96:a=!0;break;case 40:l++;break;case 41:l--;break;case 91:c++;break;case 93:c--;break;case 123:s++;break;case 125:s--;break}if(d===47){let e=p-1,t;for(;e>=0&&(t=n.charAt(e),t===` `);e--);(!t||!Oz.test(t))&&(o=!0)}}m===void 0?m=n.slice(0,p).trim():u!==0&&g();function g(){h.push(n.slice(u,p).trim()),u=p+1}if(h.length){for(p=0;p<h.length;p++)m=Mz(m,h[p],t);e.content=m,e.ast=void 0}}function Mz(e,t,n){n.helper(JF);let r=t.indexOf(`(`);if(r<0)return n.filters.add(t),`${vL(t,`filter`)}(${e})`;{let i=t.slice(0,r),a=t.slice(r+1);return n.filters.add(i),`${vL(i,`filter`)}(${e}${a===`)`?a:`,`+a}`}}var Nz=new WeakSet,Pz=(e,t)=>{if(e.type===1){let n=aL(e,`memo`);return!n||Nz.has(e)||t.inSSR?void 0:(Nz.add(e),()=>{let r=e.codegenNode||t.currentNode.codegenNode;r&&r.type===13&&(e.tagType!==1&&NI(r,t),e.codegenNode=EI(t.helper(hI),[n.exp,DI(void 0,r),`_cache`,String(t.cached.length)]),t.cached.push(null))})}},Fz=(e,t)=>{if(e.type===1){for(let n of e.props)if(n.type===7&&n.name===`bind`&&(!n.exp||n.exp.type===4&&!n.exp.content.trim())&&n.arg){let e=n.arg;if(e.type!==4||!e.isStatic)t.onError(qI(53,e.loc)),n.exp=Z(``,!0,e.loc);else{let t=gF(e.content);(QI.test(t[0])||t[0]===`-`)&&(n.exp=Z(t,!1,e.loc))}}}};function Iz(e){return[[Fz,Tz,KR,Pz,QR,...[kz],...[],vz,uz,iz,Cz],{on:bz,bind:xz,model:Ez}]}function Lz(e,t={}){let n=t.onError||GI,r=t.mode===`module`;t.prefixIdentifiers===!0?n(qI(48)):r&&n(qI(49)),t.cacheHandlers&&n(qI(50)),t.scopeId&&!r&&n(qI(51));let i=sF({},t,{prefixIdentifiers:!1}),a=lF(e)?sR(e,i):e,[o,s]=Iz();return _R(a,sF({},i,{nodeTransforms:[...o,...t.nodeTransforms||[]],directiveTransforms:sF({},s,t.directiveTransforms||{})})),TR(a,i)}var Rz=()=>({props:[]}),zz=Symbol(``),Bz=Symbol(``),Vz=Symbol(``),Hz=Symbol(``),Uz=Symbol(``),Wz=Symbol(``),Gz=Symbol(``),Kz=Symbol(``),qz=Symbol(``),Jz=Symbol(``);vI({[zz]:`vModelRadio`,[Bz]:`vModelCheckbox`,[Vz]:`vModelText`,[Hz]:`vModelSelect`,[Uz]:`vModelDynamic`,[Wz]:`withModifiers`,[Gz]:`withKeys`,[Kz]:`vShow`,[qz]:`Transition`,[Jz]:`TransitionGroup`});var Yz;function Xz(e,t=!1){return Yz||=document.createElement(`div`),t?(Yz.innerHTML=`<div foo="${e.replace(/"/g,`&quot;`)}">`,Yz.children[0].getAttribute(`foo`)):(Yz.innerHTML=e,Yz.textContent)}var Zz={parseMode:`html`,isVoidTag:jF,isNativeTag:e=>OF(e)||kF(e)||AF(e),isPreTag:e=>e===`pre`,isIgnoreNewlineTag:e=>e===`pre`||e===`textarea`,decodeEntities:Xz,isBuiltInComponent:e=>{if(e===`Transition`||e===`transition`)return qz;if(e===`TransitionGroup`||e===`transition-group`)return Jz},getNamespace(e,t,n){let r=t?t.ns:n;if(t&&r===2)if(t.tag===`annotation-xml`){if(e===`svg`)return 1;t.props.some(e=>e.type===6&&e.name===`encoding`&&e.value!=null&&(e.value.content===`text/html`||e.value.content===`application/xhtml+xml`))&&(r=0)}else /^m(?:[ions]|text)$/.test(t.tag)&&e!==`mglyph`&&e!==`malignmark`&&(r=0);else t&&r===1&&(t.tag===`foreignObject`||t.tag===`desc`||t.tag===`title`)&&(r=0);if(r===0){if(e===`svg`)return 1;if(e===`math`)return 2}return r}},Qz=e=>{e.type===1&&e.props.forEach((t,n)=>{t.type===6&&t.name===`style`&&t.value&&(e.props[n]={type:7,name:`bind`,arg:Z(`style`,!0,t.loc),exp:$z(t.value.content,t.loc),modifiers:[],loc:t.loc})})},$z=(e,t)=>{let n=CF(e);return Z(JSON.stringify(n),!1,t,3)};function eB(e,t){return qI(e,t,void 0)}var tB=(e,t,n)=>{let{exp:r,loc:i}=e;return r||n.onError(eB(54,i)),t.children.length&&(n.onError(eB(55,i)),t.children.length=0),{props:[wI(Z(`innerHTML`,!0,i),r||Z(``,!0))]}},nB=(e,t,n)=>{let{exp:r,loc:i}=e;return r||n.onError(eB(56,i)),t.children.length&&(n.onError(eB(57,i)),t.children.length=0),{props:[wI(Z(`textContent`,!0),r?dR(r,n)>0?r:EI(n.helperString($F),[r],i):Z(``,!0))]}},rB=(e,t,n)=>{let r=Ez(e,t,n);if(!r.props.length||t.tagType===1)return r;e.arg&&n.onError(eB(59,e.arg.loc));let{tag:i}=t,a=n.isCustomElement(i);if(i===`input`||i===`textarea`||i===`select`||a){let o=Vz,s=!1;if(i===`input`||a){let r=oL(t,`type`);if(r){if(r.type===7)o=Uz;else if(r.value)switch(r.value.content){case`radio`:o=zz;break;case`checkbox`:o=Bz;break;case`file`:s=!0,n.onError(eB(60,e.loc));break;default:break}}else cL(t)&&(o=Uz)}else i===`select`&&(o=Hz);s||(r.needRuntime=n.helper(o))}else n.onError(eB(58,e.loc));return r.props=r.props.filter(e=>!(e.key.type===4&&e.key.content===`modelValue`)),r},iB=nF(`passive,once,capture`),aB=nF(`stop,prevent,self,ctrl,shift,alt,meta,exact,middle`),oB=nF(`left,right`),sB=nF(`onkeyup,onkeydown,onkeypress`),cB=(e,t,n,r)=>{let i=[],a=[],o=[];for(let s=0;s<t.length;s++){let c=t[s].content;c===`native`&&WI(`COMPILER_V_ON_NATIVE`,n,r)||iB(c)?o.push(c):oB(c)?JI(e)?sB(e.content.toLowerCase())?i.push(c):a.push(c):(i.push(c),a.push(c)):aB(c)?a.push(c):i.push(c)}return{keyModifiers:i,nonKeyModifiers:a,eventOptionModifiers:o}},lB=(e,t)=>JI(e)&&e.content.toLowerCase()===`onclick`?Z(t,!0):e.type===4?e:TI([`(`,e,`) === "onClick" ? "${t}" : (`,e,`)`]),uB=(e,t,n)=>bz(e,t,n,t=>{let{modifiers:r}=e;if(!r.length)return t;let{key:i,value:a}=t.props[0],{keyModifiers:o,nonKeyModifiers:s,eventOptionModifiers:c}=cB(i,r,n,e.loc);if(s.includes(`right`)&&(i=lB(i,`onContextmenu`)),s.includes(`middle`)&&(i=lB(i,`onMouseup`)),s.length&&(a=EI(n.helper(Wz),[a,JSON.stringify(s)])),o.length&&(!JI(i)||sB(i.content.toLowerCase()))&&(a=EI(n.helper(Gz),[a,JSON.stringify(o)])),c.length){let e=c.map(_F).join(``);i=JI(i)?Z(`${i.content}${e}`,!0):TI([`(`,i,`) + "${e}"`])}return{props:[wI(i,a)]}}),dB=(e,t,n)=>{let{exp:r,loc:i}=e;return r||n.onError(eB(62,i)),{props:[],needRuntime:n.helper(Kz)}},fB=(e,t)=>{e.type===1&&e.tagType===0&&(e.tag===`script`||e.tag===`style`)&&t.removeNode()},pB=[Qz,...[]],mB={cloak:Rz,html:tB,text:nB,model:rB,on:uB,show:dB};function hB(e,t={}){return Lz(e,sF({},Zz,t,{nodeTransforms:[fB,...pB,...t.nodeTransforms||[]],directiveTransforms:sF({},mB,t.directiveTransforms||{}),transformHoist:null}))}var gB=Object.create(null);function _B(e,t){if(!lF(e))if(e.nodeType)e=e.innerHTML;else return iF;let n=yF(e,t),r=gB[n];if(r)return r;if(e[0]===`#`){let t=document.querySelector(e);e=t?t.innerHTML:``}let i=sF({hoistStatic:!0,onError:void 0,onWarn:iF},t);!i.isCustomElement&&typeof customElements<`u`&&(i.isCustomElement=e=>!!customElements.get(e));let{code:a}=hB(e,i),o=Function(`Vue`,a)(qM);return o._rc=!0,gB[n]=o}Yj(_B);var vB={created(){if(!this.$options.remember)return;Array.isArray(this.$options.remember)&&(this.$options.remember={data:this.$options.remember}),typeof this.$options.remember==`string`&&(this.$options.remember={data:[this.$options.remember]}),typeof this.$options.remember.data==`string`&&(this.$options.remember={data:[this.$options.remember.data]});let e=this.$options.remember.key instanceof Function?this.$options.remember.key.call(this):this.$options.remember.key,t=pC.restore(e),n=this.$options.remember.data.filter(e=>!(this[e]!==null&&typeof this[e]==`object`&&this[e].__rememberable===!1)),r=e=>this[e]!==null&&typeof this[e]==`object`&&typeof this[e].__remember==`function`&&typeof this[e].__restore==`function`;n.forEach(i=>{this[i]!==void 0&&t!==void 0&&t[i]!==void 0&&(r(i)?this[i].__restore(t[i]):this[i]=t[i]),this.$watch(i,()=>{pC.remember(n.reduce((e,t)=>({...e,[t]:Ug(r(t)?this[t].__remember():this[t])}),{}),e)},{immediate:!0,deep:!0})})}};function yB(e,t){let n=typeof e==`string`?e:null,r=(typeof e==`string`?t:e)??{},i=n?pC.restore(n):null,a=Ug(typeof r==`function`?r():r),o=null,s=null,c=e=>e,l=!1,u=eT({...i?i.data:Ug(a),isDirty:!1,errors:i?i.errors:{},hasErrors:!1,processing:!1,progress:null,wasSuccessful:!1,recentlySuccessful:!1,data(){return Object.keys(a).reduce((e,t)=>L_(e,t,Pm(this,t)),{})},transform(e){return c=e,this},defaults(e,t){if(typeof r==`function`)throw Error("You cannot call `defaults()` when using a function to define your form data.");return l=!0,e===void 0?(a=Ug(this.data()),this.isDirty=!1):a=typeof e==`string`?L_(Ug(a),e,t):Object.assign({},Ug(a),e),this},reset(...e){let t=Ug(typeof r==`function`?r():a),n=Ug(t);return e.length===0?(a=n,Object.assign(this,t)):e.filter(e=>P_(n,e)).forEach(e=>{L_(a,e,Pm(n,e)),L_(this,e,Pm(t,e))}),this},setError(e,t){return Object.assign(this.errors,typeof e==`string`?{[e]:t}:e),this.hasErrors=Object.keys(this.errors).length>0,this},clearErrors(...e){return this.errors=Object.keys(this.errors).reduce((t,n)=>({...t,...e.length>0&&!e.includes(n)?{[n]:this.errors[n]}:{}}),{}),this.hasErrors=Object.keys(this.errors).length>0,this},resetAndClearErrors(...e){return this.reset(...e),this.clearErrors(...e),this},submit(...e){let t=e[0]!==null&&typeof e[0]==`object`,n=t?e[0].method:e[0],r=t?e[0].url:e[1],i=(t?e[1]:e[2])??{};l=!1;let u=c(this.data()),d={...i,onCancelToken:e=>{if(o=e,i.onCancelToken)return i.onCancelToken(e)},onBefore:e=>{if(this.wasSuccessful=!1,this.recentlySuccessful=!1,clearTimeout(s),i.onBefore)return i.onBefore(e)},onStart:e=>{if(this.processing=!0,i.onStart)return i.onStart(e)},onProgress:e=>{if(this.progress=e,i.onProgress)return i.onProgress(e)},onSuccess:async e=>{this.processing=!1,this.progress=null,this.clearErrors(),this.wasSuccessful=!0,this.recentlySuccessful=!0,s=setTimeout(()=>this.recentlySuccessful=!1,2e3);let t=i.onSuccess?await i.onSuccess(e):null;return l||(a=Ug(this.data()),this.isDirty=!1),t},onError:e=>{if(this.processing=!1,this.progress=null,this.clearErrors().setError(e),i.onError)return i.onError(e)},onCancel:()=>{if(this.processing=!1,this.progress=null,i.onCancel)return i.onCancel()},onFinish:e=>{if(this.processing=!1,this.progress=null,o=null,i.onFinish)return i.onFinish(e)}};n===`delete`?pC.delete(r,{...d,data:u}):pC[n](r,u,d)},get(e,t){this.submit(`get`,e,t)},post(e,t){this.submit(`post`,e,t)},put(e,t){this.submit(`put`,e,t)},patch(e,t){this.submit(`patch`,e,t)},delete(e,t){this.submit(`delete`,e,t)},cancel(){o&&o.cancel()},__rememberable:n===null,__remember(){return{data:this.data(),errors:this.errors}},__restore(e){Object.assign(this,e.data),this.setError(e.errors)}});return DD(u,e=>{u.isDirty=!F_(u.data(),a),n&&pC.remember(Ug(e.__remember()),n)},{immediate:!0,deep:!0}),u}var bB=pT(null),xB=pT(null),SB=mT(null),CB=pT(null),wB=null,TB=aO({name:`Inertia`,props:{initialPage:{type:Object,required:!0},initialComponent:{type:Object,required:!1},resolveComponent:{type:Function,required:!1},titleCallback:{type:Function,required:!1,default:e=>e},onHeadUpdate:{type:Function,required:!1,default:()=>()=>{}}},setup({initialPage:e,initialComponent:t,resolveComponent:n,titleCallback:r,onHeadUpdate:i}){bB.value=t?lT(t):null,xB.value=e,CB.value=null;let a=typeof window>`u`;return wB=MS(a,r,i),a||(pC.init({initialPage:e,resolveComponent:n,swapComponent:async e=>{bB.value=lT(e.component),xB.value=e.page,CB.value=e.preserveState?CB.value:Date.now()}}),pC.on(`navigate`,()=>wB.forceUpdate())),()=>{if(bB.value){bB.value.inheritAttrs=!!bB.value.inheritAttrs;let e=rM(bB.value,{...xB.value.props,key:CB.value});return SB.value&&=(bB.value.layout=SB.value,null),bB.value.layout?typeof bB.value.layout==`function`?bB.value.layout(rM,e):(Array.isArray(bB.value.layout)?bB.value.layout:[bB.value.layout]).concat(e).reverse().reduce((e,t)=>(t.inheritAttrs=!!t.inheritAttrs,rM(t,{...xB.value.props},()=>e))):e}}}}),EB={install(e){pC.form=yB,Object.defineProperty(e.config.globalProperties,`$inertia`,{get:()=>pC}),Object.defineProperty(e.config.globalProperties,`$page`,{get:()=>xB.value}),Object.defineProperty(e.config.globalProperties,`$headManager`,{get:()=>wB}),e.mixin(vB)}};function DB(){return eT({props:X(()=>xB.value?.props),url:X(()=>xB.value?.url),component:X(()=>xB.value?.component),version:X(()=>xB.value?.version),clearHistory:X(()=>xB.value?.clearHistory),deferredProps:X(()=>xB.value?.deferredProps),mergeProps:X(()=>xB.value?.mergeProps),prependProps:X(()=>xB.value?.prependProps),deepMergeProps:X(()=>xB.value?.deepMergeProps),matchPropsOn:X(()=>xB.value?.matchPropsOn),rememberedState:X(()=>xB.value?.rememberedState),encryptHistory:X(()=>xB.value?.encryptHistory)})}async function OB({id:e=`app`,resolve:t,setup:n,title:r,progress:i={},page:a,render:o}){let s=typeof window>`u`,c=s?null:document.getElementById(e),l=a||JSON.parse(c.dataset.page),u=e=>Promise.resolve(t(e)).then(e=>e.default||e),d=[],f=await Promise.all([u(l.component),pC.decryptHistory().catch(()=>{})]).then(([e])=>n({el:c,App:TB,props:{initialPage:l,initialComponent:e,resolveComponent:u,titleCallback:r,onHeadUpdate:s?e=>d=e:null},plugin:EB}));if(!s&&i&&oC(i),s){let t=await o(ZP({render:()=>rM(`div`,{id:e,"data-page":JSON.stringify(l),innerHTML:f?o(f):``})}));return{head:d,body:t}}}var kB=aO({name:`Deferred`,props:{data:{type:[String,Array],required:!0}},render(){let e=Array.isArray(this.$props.data)?this.$props.data:[this.$props.data];if(!this.$slots.fallback)throw Error("`<Deferred>` requires a `<template #fallback>` slot");return e.every(e=>this.$page.props[e]!==void 0)?this.$slots.default():this.$slots.fallback()}}),AB=()=>void 0,jB=aO({name:`Form`,slots:Object,props:{action:{type:[String,Object],default:``},method:{type:String,default:`get`},headers:{type:Object,default:()=>({})},queryStringArrayFormat:{type:String,default:`brackets`},errorBag:{type:[String,null],default:null},showProgress:{type:Boolean,default:!0},transform:{type:Function,default:e=>e},options:{type:Object,default:()=>({})},resetOnError:{type:[Boolean,Array],default:!1},resetOnSuccess:{type:[Boolean,Array],default:!1},setDefaultsOnSuccess:{type:Boolean,default:!1},onCancelToken:{type:Function,default:AB},onBefore:{type:Function,default:AB},onStart:{type:Function,default:AB},onProgress:{type:Function,default:AB},onFinish:{type:Function,default:AB},onCancel:{type:Function,default:AB},onSuccess:{type:Function,default:AB},onError:{type:Function,default:AB},onSubmitComplete:{type:Function,default:AB},disableWhileProcessing:{type:Boolean,default:!1},invalidateCacheTags:{type:[String,Array],default:()=>[]}},setup(e,{slots:t,attrs:n,expose:r}){let i=yB({}),a=pT(),o=X(()=>iS(e.action)?e.action.method:e.method.toLowerCase()),s=pT(!1),c=pT(new FormData),l=e=>{s.value=e.type===`reset`?!1:!F_(f(),AS(c.value))},u=[`input`,`change`,`reset`];KO(()=>{c.value=d(),u.forEach(e=>a.value.addEventListener(e,l))}),YO(()=>u.forEach(e=>a.value?.removeEventListener(e,l)));let d=()=>new FormData(a.value),f=()=>AS(d()),p=()=>{let[t,n]=eS(o.value,iS(e.action)?e.action.url:e.action,f(),e.queryStringArrayFormat),r=e=>{e&&(e===!0?m():e.length>0&&m(...e))},a={headers:e.headers,errorBag:e.errorBag,showProgress:e.showProgress,invalidateCacheTags:e.invalidateCacheTags,onCancelToken:e.onCancelToken,onBefore:e.onBefore,onStart:e.onStart,onProgress:e.onProgress,onFinish:e.onFinish,onCancel:e.onCancel,onSuccess:(...t)=>{e.onSuccess(...t),e.onSubmitComplete(_),r(e.resetOnSuccess),e.setDefaultsOnSuccess===!0&&g()},onError:(...t)=>{e.onError(...t),r(e.resetOnError)},...e.options};i.transform(()=>e.transform(n)).submit(o.value,t,a)},m=(...e)=>{fC(a.value,c.value,e)},h=(...e)=>{i.clearErrors(...e),m(...e)},g=()=>{c.value=d(),s.value=!1},_={get errors(){return i.errors},get hasErrors(){return i.hasErrors},get processing(){return i.processing},get progress(){return i.progress},get wasSuccessful(){return i.wasSuccessful},get recentlySuccessful(){return i.recentlySuccessful},clearErrors:(...e)=>i.clearErrors(...e),resetAndClearErrors:h,setError:(e,t)=>i.setError(typeof e==`string`?{[e]:t}:e),get isDirty(){return s.value},reset:m,submit:p,defaults:g};return r(_),()=>rM(`form`,{...n,ref:a,action:iS(e.action)?e.action.url:e.action,method:o.value,onSubmit:e=>{e.preventDefault(),p()},inert:e.disableWhileProcessing&&i.processing},t.default?t.default(_):[])}}),MB=aO({props:{title:{type:String,required:!1}},data(){return{provider:this.$headManager.createProvider()}},beforeUnmount(){this.provider.disconnect()},methods:{isUnaryTag(e){return[`area`,`base`,`br`,`col`,`embed`,`hr`,`img`,`input`,`keygen`,`link`,`meta`,`param`,`source`,`track`,`wbr`].indexOf(e.type)>-1},renderTagStart(e){e.props=e.props||{},e.props.inertia=e.props[`head-key`]===void 0?``:e.props[`head-key`];let t=Object.keys(e.props).reduce((t,n)=>{let r=String(e.props[n]);return[`key`,`head-key`].includes(n)?t:r===``?t+` ${n}`:t+` ${n}="${j_(r)}"`},``);return`<${e.type}${t}>`},renderTagChildren(e){return typeof e.children==`string`?e.children:e.children.reduce((e,t)=>e+this.renderTag(t),``)},isFunctionNode(e){return typeof e.type==`function`},isComponentNode(e){return typeof e.type==`object`},isCommentNode(e){return/(comment|cmt)/i.test(e.type.toString())},isFragmentNode(e){return/(fragment|fgt|symbol\(\))/i.test(e.type.toString())},isTextNode(e){return/(text|txt)/i.test(e.type.toString())},renderTag(e){if(this.isTextNode(e))return e.children;if(this.isFragmentNode(e)||this.isCommentNode(e))return``;let t=this.renderTagStart(e);return e.children&&(t+=this.renderTagChildren(e)),this.isUnaryTag(e)||(t+=`</${e.type}>`),t},addTitleElement(e){return this.title&&!e.find(e=>e.startsWith(`<title`))&&e.push(`<title inertia>${this.title}</title>`),e},renderNodes(e){return this.addTitleElement(e.flatMap(e=>this.resolveNode(e)).map(e=>this.renderTag(e)).filter(e=>e))},resolveNode(e){return this.isFunctionNode(e)?this.resolveNode(e.type()):this.isComponentNode(e)?(console.warn(`Using components in the <Head> component is not supported.`),[]):this.isTextNode(e)&&e.children?e:this.isFragmentNode(e)&&e.children?e.children.flatMap(e=>this.resolveNode(e)):this.isCommentNode(e)?[]:e}},render(){this.provider.update(this.renderNodes(this.$slots.default?this.$slots.default():[]))}}),NB=()=>{},PB=aO({name:`Link`,props:{as:{type:[String,Object],default:`a`},data:{type:Object,default:()=>({})},href:{type:[String,Object],default:``},method:{type:String,default:`get`},replace:{type:Boolean,default:!1},preserveScroll:{type:Boolean,default:!1},preserveState:{type:Boolean,default:null},preserveUrl:{type:Boolean,default:!1},only:{type:Array,default:()=>[]},except:{type:Array,default:()=>[]},headers:{type:Object,default:()=>({})},queryStringArrayFormat:{type:String,default:`brackets`},async:{type:Boolean,default:!1},prefetch:{type:[Boolean,String,Array],default:!1},cacheFor:{type:[Number,String,Array],default:0},onStart:{type:Function,default:NB},onProgress:{type:Function,default:NB},onFinish:{type:Function,default:NB},onBefore:{type:Function,default:NB},onCancel:{type:Function,default:NB},onSuccess:{type:Function,default:NB},onError:{type:Function,default:NB},onCancelToken:{type:Function,default:NB},onPrefetching:{type:Function,default:NB},onPrefetched:{type:Function,default:NB},cacheTags:{type:[String,Array],default:()=>[]}},setup(e,{slots:t,attrs:n}){let r=pT(0),i=pT(null),a=X(()=>e.prefetch===!0?[`hover`]:e.prefetch===!1?[]:Array.isArray(e.prefetch)?e.prefetch:[e.prefetch]),o=X(()=>e.cacheFor===0?a.value.length===1&&a.value[0]===`click`?0:3e4:e.cacheFor);KO(()=>{a.value.includes(`mount`)&&h()}),XO(()=>{clearTimeout(i.value)});let s=X(()=>iS(e.href)?e.href.method:e.method.toLowerCase()),c=X(()=>typeof e.as!=`string`||e.as.toLowerCase()!==`a`?e.as:s.value===`get`?e.as.toLowerCase():`button`),l=X(()=>eS(s.value,iS(e.href)?e.href.url:e.href,e.data,e.queryStringArrayFormat)),u=X(()=>l.value[0]),d=X(()=>l.value[1]),f=X(()=>c.value===`button`?{type:`button`}:c.value===`a`||typeof c.value!=`string`?{href:u.value}:{}),p=X(()=>({data:d.value,method:s.value,replace:e.replace,preserveScroll:e.preserveScroll,preserveState:e.preserveState??s.value!==`get`,preserveUrl:e.preserveUrl,only:e.only,except:e.except,headers:e.headers,async:e.async})),m=X(()=>({...p.value,onCancelToken:e.onCancelToken,onBefore:e.onBefore,onStart:t=>{r.value++,e.onStart(t)},onProgress:e.onProgress,onFinish:t=>{r.value--,e.onFinish(t)},onCancel:e.onCancel,onSuccess:e.onSuccess,onError:e.onError})),h=()=>{pC.prefetch(u.value,{...p.value,onPrefetching:e.onPrefetching,onPrefetched:e.onPrefetched},{cacheFor:o.value,cacheTags:e.cacheTags})},g={onClick:e=>{NS(e)&&(e.preventDefault(),pC.visit(u.value,m.value))}},_={onMouseenter:()=>{i.value=setTimeout(()=>{h()},75)},onMouseleave:()=>{clearTimeout(i.value)},onClick:g.onClick},v={onMousedown:e=>{NS(e)&&(e.preventDefault(),h())},onKeydown:e=>{NS(e)&&PS(e)&&(e.preventDefault(),h())},onMouseup:e=>{e.preventDefault(),pC.visit(u.value,m.value)},onKeyup:e=>{PS(e)&&(e.preventDefault(),pC.visit(u.value,m.value))},onClick:e=>{NS(e)&&e.preventDefault()}};return()=>rM(c.value,{...n,...f.value,"data-loading":r.value>0?``:void 0,...a.value.includes(`hover`)?_:a.value.includes(`click`)?v:g},t)}}),FB=(e,t)=>{let n=e.__vccOpts||e;for(let[e,r]of t)n[e]=r;return n};export{RC as $,bD as A,ak as B,Tj as C,Cj as D,Lj as E,XO as F,DD as G,$k as H,cj as I,_D as J,wD as K,yD as L,jj as M,QE as N,rM as O,KO as P,ME as Q,lk as R,uk as S,aO as T,Ek as U,oO as V,cO as W,OE as X,DE as Y,SE as Z,X as _,MB as a,G as at,Dj as b,DB as c,vT as ct,PP as d,yd as dt,fT as et,EP as f,ud as ft,wj as g,O as gt,VD as h,nd as ht,jB as i,mT as it,jk as j,xD as k,sN as l,pC as lt,nj as m,ld as mt,OB as n,eT as nt,PB as o,yT as ot,BP as p,cd as pt,gD as q,kB as r,pT as rt,yB as s,_T as st,FB as t,zC as tt,kN as u,yx as ut,bj as v,xj as w,pj as x,mj as y,dk as z};