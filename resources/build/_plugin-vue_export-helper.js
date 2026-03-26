const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["./bg-BG.js","./bg2.js","./cs-CZ.js","./cs2.js","./de-DE.js","./de2.js","./en-AU.js","./en2.js","./en-GB.js","./en-US.js","./es-ES.js","./es2.js","./fr-FR.js","./fr2.js","./fr-BE.js","./hu-HU.js","./hu2.js","./it-IT.js","./it2.js","./nl-BE.js","./nl2.js","./nl-NL.js","./pl-PL.js","./pl2.js","./ro-RO.js","./ro2.js","./ru-RU.js","./ru2.js","./sk-SK.js","./sk2.js","./tr-TR.js","./tr.js","./uk-UA.js","./uk2.js","./bg-BG2.js","./bg3.js","./cs-CZ2.js","./cs3.js","./de-DE2.js","./de3.js","./en-AU2.js","./en3.js","./en-GB2.js","./en-US2.js","./es-ES2.js","./es3.js","./fr-FR2.js","./fr3.js","./fr-BE2.js","./hu-HU2.js","./hu3.js","./it-IT2.js","./it3.js","./nl-BE2.js","./nl3.js","./nl-NL2.js","./pl-PL2.js","./pl3.js","./ro-RO2.js","./ro3.js","./ru-RU2.js","./ru3.js","./sk-SK2.js","./sk3.js","./uk-UA2.js","./uk3.js"])))=>i.map(i=>d[i]);
import{a as e,c as t,d as n,f as r,g as i,h as a,i as o,l as s,m as c,o as l,p as u,r as d,s as f,u as p}from"./Queue-wGK97jCw.js";import{t as m}from"./decorate-DpHfxayW.js";import{a as h,c as g,d as _,f as v,i as y,n as b,p as x,r as S,t as C}from"./lit.js";import{a as w,i as T,o as E,r as D,t as O}from"./decorators.js";import{a as k,i as A,n as ee,o as te,s as ne}from"./nav-item-D3exy0bq.js";var re=``,ie=``;function ae(e){re=e}function oe(e=``){if(!re){let e=document.querySelector(`[data-webawesome]`);if(e?.hasAttribute(`data-webawesome`)){let t=new URL(e.getAttribute(`data-webawesome`)??``,window.location.href).pathname;ae(t)}else{let e=[...document.getElementsByTagName(`script`)].find(e=>e.src.endsWith(`webawesome.js`)||e.src.endsWith(`webawesome.loader.js`)||e.src.endsWith(`webawesome.ssr-loader.js`));e&&ae(String(e.getAttribute(`src`)).split(`/`).slice(0,-1).join(`/`))}}return re.replace(/\/$/,``)+(e?`/${e.replace(/^\//,``)}`:``)}function se(e){ie=e}function ce(){if(!ie){let e=document.querySelector(`[data-fa-kit-code]`);e&&se(e.getAttribute(`data-fa-kit-code`)||``)}return ie}var le=`7.0.1`;function ue(e,t,n){let r=ce(),i=r.length>0,a=`solid`;return t===`notdog`?(n===`solid`&&(a=`solid`),n===`duo-solid`&&(a=`duo-solid`),`https://ka-p.fontawesome.com/releases/v${le}/svgs/notdog-${a}/${e}.svg?token=${encodeURIComponent(r)}`):t===`chisel`?`https://ka-p.fontawesome.com/releases/v${le}/svgs/chisel-regular/${e}.svg?token=${encodeURIComponent(r)}`:t===`etch`?`https://ka-p.fontawesome.com/releases/v${le}/svgs/etch-solid/${e}.svg?token=${encodeURIComponent(r)}`:t===`jelly`?(n===`regular`&&(a=`regular`),n===`duo-regular`&&(a=`duo-regular`),n===`fill-regular`&&(a=`fill-regular`),`https://ka-p.fontawesome.com/releases/v${le}/svgs/jelly-${a}/${e}.svg?token=${encodeURIComponent(r)}`):t===`slab`?((n===`solid`||n===`regular`)&&(a=`regular`),n===`press-regular`&&(a=`press-regular`),`https://ka-p.fontawesome.com/releases/v${le}/svgs/slab-${a}/${e}.svg?token=${encodeURIComponent(r)}`):t===`thumbprint`?`https://ka-p.fontawesome.com/releases/v${le}/svgs/thumbprint-light/${e}.svg?token=${encodeURIComponent(r)}`:t===`whiteboard`?`https://ka-p.fontawesome.com/releases/v${le}/svgs/whiteboard-semibold/${e}.svg?token=${encodeURIComponent(r)}`:(t===`classic`&&(n===`thin`&&(a=`thin`),n===`light`&&(a=`light`),n===`regular`&&(a=`regular`),n===`solid`&&(a=`solid`)),t===`sharp`&&(n===`thin`&&(a=`sharp-thin`),n===`light`&&(a=`sharp-light`),n===`regular`&&(a=`sharp-regular`),n===`solid`&&(a=`sharp-solid`)),t===`duotone`&&(n===`thin`&&(a=`duotone-thin`),n===`light`&&(a=`duotone-light`),n===`regular`&&(a=`duotone-regular`),n===`solid`&&(a=`duotone`)),t===`sharp-duotone`&&(n===`thin`&&(a=`sharp-duotone-thin`),n===`light`&&(a=`sharp-duotone-light`),n===`regular`&&(a=`sharp-duotone-regular`),n===`solid`&&(a=`sharp-duotone-solid`)),t===`brands`&&(a=`brands`),i?`https://ka-p.fontawesome.com/releases/v${le}/svgs/${a}/${e}.svg?token=${encodeURIComponent(r)}`:`https://ka-f.fontawesome.com/releases/v${le}/svgs/${a}/${e}.svg`)}var de={name:`default`,resolver:(e,t=`classic`,n=`solid`)=>ue(e,t,n),mutator:(e,t)=>{if(t?.family&&!e.hasAttribute(`data-duotone-initialized`)){let{family:n,variant:r}=t;if(n===`duotone`||n===`sharp-duotone`||n===`notdog`&&r===`duo-solid`||n===`jelly`&&r===`duo-regular`||n===`thumbprint`){let n=[...e.querySelectorAll(`path`)],r=n.find(e=>!e.hasAttribute(`opacity`)),i=n.find(e=>e.hasAttribute(`opacity`));if(!r||!i)return;if(r.setAttribute(`data-duotone-primary`,``),i.setAttribute(`data-duotone-secondary`,``),t.swapOpacity&&r&&i){let e=i.getAttribute(`opacity`)||`0.4`;r.style.setProperty(`--path-opacity`,e),i.style.setProperty(`--path-opacity`,`1`)}e.setAttribute(`data-duotone-initialized`,``)}}}},fe=`modulepreload`,pe=function(e,t){return new URL(e,t).href},me={},j=function(e,t,n){let r=Promise.resolve();if(t&&t.length>0){let e=document.getElementsByTagName(`link`),i=document.querySelector(`meta[property=csp-nonce]`),a=i?.nonce||i?.getAttribute(`nonce`);function o(e){return Promise.all(e.map(e=>Promise.resolve(e).then(e=>({status:`fulfilled`,value:e}),e=>({status:`rejected`,reason:e}))))}r=o(t.map(t=>{if(t=pe(t,n),t in me)return;me[t]=!0;let r=t.endsWith(`.css`),i=r?`[rel="stylesheet"]`:``;if(n)for(let n=e.length-1;n>=0;n--){let i=e[n];if(i.href===t&&(!r||i.rel===`stylesheet`))return}else if(document.querySelector(`link[href="${t}"]${i}`))return;let o=document.createElement(`link`);if(o.rel=r?`stylesheet`:fe,r||(o.as=`script`),o.crossOrigin=``,o.href=t,a&&o.setAttribute(`nonce`,a),document.head.appendChild(o),r)return new Promise((e,n)=>{o.addEventListener(`load`,e),o.addEventListener(`error`,()=>n(Error(`Unable to preload CSS for ${t}`)))})}))}function i(e){let t=new Event(`vite:preloadError`,{cancelable:!0});if(t.payload=e,window.dispatchEvent(t),!t.defaultPrevented)throw e}return r.then(t=>{for(let e of t||[])e.status===`rejected`&&i(e.reason);return e().catch(i)})};new MutationObserver(e=>{for(let{addedNodes:t}of e)for(let e of t)e.nodeType===Node.ELEMENT_NODE&&he(e)});async function he(e){let t=e instanceof Element?e.tagName.toLowerCase():``,n=t?.startsWith(`wa-`),r=[...e.querySelectorAll(`:not(:defined)`)].map(e=>e.tagName.toLowerCase()).filter(e=>e.startsWith(`wa-`));n&&!customElements.get(t)&&r.push(t);let i=[...new Set(r)],a=await Promise.allSettled(i.map(e=>ge(e)));for(let e of a)e.status===`rejected`&&console.warn(e.reason);await new Promise(requestAnimationFrame),e.dispatchEvent(new CustomEvent(`wa-discovery-complete`,{bubbles:!1,cancelable:!1,composed:!0}))}function ge(e){if(customElements.get(e))return Promise.resolve();let t=e.replace(/^wa-/i,``),n=oe(`components/${t}/${t}.js`);return new Promise((t,r)=>{j(()=>import(n).then(()=>t()),[],import.meta.url).catch(()=>r(Error(`Unable to autoload <${e}> from ${n}`)))})}var _e=new Set,ve=new Map,ye,be=`ltr`,xe=`en`,Se=typeof MutationObserver<`u`&&typeof document<`u`&&document.documentElement!==void 0;if(Se){let e=new MutationObserver(we);be=document.documentElement.dir||`ltr`,xe=document.documentElement.lang||navigator.language,e.observe(document.documentElement,{attributes:!0,attributeFilter:[`dir`,`lang`]})}function Ce(...e){e.map(e=>{let t=e.$code.toLowerCase();ve.has(t)?ve.set(t,Object.assign(Object.assign({},ve.get(t)),e)):ve.set(t,e),ye||=e}),we()}function we(){Se&&(be=document.documentElement.dir||`ltr`,xe=document.documentElement.lang||navigator.language),[..._e.keys()].map(e=>{typeof e.requestUpdate==`function`&&e.requestUpdate()})}var Te=class{constructor(e){this.host=e,this.host.addController(this)}hostConnected(){_e.add(this.host)}hostDisconnected(){_e.delete(this.host)}dir(){return`${this.host.dir||be}`.toLowerCase()}lang(){return`${this.host.lang||xe}`.toLowerCase()}getTranslationData(e){let t=new Intl.Locale(e.replace(/_/g,`-`)),n=t?.language.toLowerCase(),r=(t?.region)?.toLowerCase()??``;return{locale:t,language:n,region:r,primary:ve.get(`${n}-${r}`),secondary:ve.get(n)}}exists(e,t){let{primary:n,secondary:r}=this.getTranslationData(t.lang??this.lang());return t=Object.assign({includeFallback:!1},t),!!(n&&n[e]||r&&r[e]||t.includeFallback&&ye&&ye[e])}term(e,...t){let{primary:n,secondary:r}=this.getTranslationData(this.lang()),i;if(n&&n[e])i=n[e];else if(r&&r[e])i=r[e];else if(ye&&ye[e])i=ye[e];else return console.error(`No translation found for: ${String(e)}`),String(e);return typeof i==`function`?i(...t):i}date(e,t){return e=new Date(e),new Intl.DateTimeFormat(this.lang(),t).format(e)}number(e,t){return e=Number(e),isNaN(e)?``:new Intl.NumberFormat(this.lang(),t).format(e)}relativeTime(e,t,n){return new Intl.RelativeTimeFormat(this.lang(),n).format(e,t)}},Ee={$code:`en`,$name:`English`,$dir:`ltr`,carousel:`Carousel`,clearEntry:`Clear entry`,close:`Close`,copied:`Copied`,copy:`Copy`,currentValue:`Current value`,error:`Error`,goToSlide:(e,t)=>`Go to slide ${e} of ${t}`,hidePassword:`Hide password`,loading:`Loading`,nextSlide:`Next slide`,numOptionsSelected:e=>e===0?`No options selected`:e===1?`1 option selected`:`${e} options selected`,pauseAnimation:`Pause animation`,playAnimation:`Play animation`,previousSlide:`Previous slide`,progress:`Progress`,remove:`Remove`,resize:`Resize`,scrollableRegion:`Scrollable region`,scrollToEnd:`Scroll to end`,scrollToStart:`Scroll to start`,selectAColorFromTheScreen:`Select a color from the screen`,showPassword:`Show password`,slideNum:e=>`Slide ${e}`,toggleColorFormat:`Toggle color format`,zoomIn:`Zoom in`,zoomOut:`Zoom out`};Ce(Ee);var De=Ee,Oe=class extends Te{};Ce(De);function ke(e){return`data:image/svg+xml,${encodeURIComponent(e)}`}var Ae={solid:{check:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M434.8 70.1c14.3 10.4 17.5 30.4 7.1 44.7l-256 352c-5.5 7.6-14 12.3-23.4 13.1s-18.5-2.7-25.1-9.3l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l101.5 101.5 234-321.7c10.4-14.3 30.4-17.5 44.7-7.1z"/></svg>`,"chevron-down":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M201.4 406.6c12.5 12.5 32.8 12.5 45.3 0l192-192c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L224 338.7 54.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l192 192z"/></svg>`,"chevron-left":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l192 192c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L77.3 256 246.6 86.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-192 192z"/></svg>`,"chevron-right":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M311.1 233.4c12.5 12.5 12.5 32.8 0 45.3l-192 192c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L243.2 256 73.9 86.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l192 192z"/></svg>`,circle:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0z"/></svg>`,eyedropper:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M341.6 29.2l-101.6 101.6-9.4-9.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3l-9.4-9.4 101.6-101.6c39-39 39-102.2 0-141.1s-102.2-39-141.1 0zM55.4 323.3c-15 15-23.4 35.4-23.4 56.6l0 42.4-26.6 39.9c-8.5 12.7-6.8 29.6 4 40.4s27.7 12.5 40.4 4l39.9-26.6 42.4 0c21.2 0 41.6-8.4 56.6-23.4l109.4-109.4-45.3-45.3-109.4 109.4c-3 3-7.1 4.7-11.3 4.7l-36.1 0 0-36.1c0-4.2 1.7-8.3 4.7-11.3l109.4-109.4-45.3-45.3-109.4 109.4z"/></svg>`,"grip-vertical":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M128 40c0-22.1-17.9-40-40-40L40 0C17.9 0 0 17.9 0 40L0 88c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zm0 192c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zM0 424l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zM320 40c0-22.1-17.9-40-40-40L232 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zM192 232l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zM320 424c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48z"/></svg>`,indeterminate:`<svg part="indeterminate-icon" class="icon" viewBox="0 0 16 16"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" stroke-linecap="round"><g stroke="currentColor" stroke-width="2"><g transform="translate(2.285714 6.857143)"><path d="M10.2857143,1.14285714 L1.14285714,1.14285714"/></g></g></g></svg>`,minus:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M0 256c0-17.7 14.3-32 32-32l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 288c-17.7 0-32-14.3-32-32z"/></svg>`,pause:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M48 32C21.5 32 0 53.5 0 80L0 432c0 26.5 21.5 48 48 48l64 0c26.5 0 48-21.5 48-48l0-352c0-26.5-21.5-48-48-48L48 32zm224 0c-26.5 0-48 21.5-48 48l0 352c0 26.5 21.5 48 48 48l64 0c26.5 0 48-21.5 48-48l0-352c0-26.5-21.5-48-48-48l-64 0z"/></svg>`,play:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M91.2 36.9c-12.4-6.8-27.4-6.5-39.6 .7S32 57.9 32 72l0 368c0 14.1 7.5 27.2 19.6 34.4s27.2 7.5 39.6 .7l336-184c12.8-7 20.8-20.5 20.8-35.1s-8-28.1-20.8-35.1l-336-184z"/></svg>`,star:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M309.5-18.9c-4.1-8-12.4-13.1-21.4-13.1s-17.3 5.1-21.4 13.1L193.1 125.3 33.2 150.7c-8.9 1.4-16.3 7.7-19.1 16.3s-.5 18 5.8 24.4l114.4 114.5-25.2 159.9c-1.4 8.9 2.3 17.9 9.6 23.2s16.9 6.1 25 2L288.1 417.6 432.4 491c8 4.1 17.7 3.3 25-2s11-14.2 9.6-23.2L441.7 305.9 556.1 191.4c6.4-6.4 8.6-15.8 5.8-24.4s-10.1-14.9-19.1-16.3L383 125.3 309.5-18.9z"/></svg>`,user:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M224 248a120 120 0 1 0 0-240 120 120 0 1 0 0 240zm-29.7 56C95.8 304 16 383.8 16 482.3 16 498.7 29.3 512 45.7 512l356.6 0c16.4 0 29.7-13.3 29.7-29.7 0-98.5-79.8-178.3-178.3-178.3l-59.4 0z"/></svg>`,xmark:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M55.1 73.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L147.2 256 9.9 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192.5 301.3 329.9 438.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.8 256 375.1 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192.5 210.7 55.1 73.4z"/></svg>`},regular:{"circle-question":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M464 256a208 208 0 1 0 -416 0 208 208 0 1 0 416 0zM0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0zm256-80c-17.7 0-32 14.3-32 32 0 13.3-10.7 24-24 24s-24-10.7-24-24c0-44.2 35.8-80 80-80s80 35.8 80 80c0 47.2-36 67.2-56 74.5l0 3.8c0 13.3-10.7 24-24 24s-24-10.7-24-24l0-8.1c0-20.5 14.8-35.2 30.1-40.2 6.4-2.1 13.2-5.5 18.2-10.3 4.3-4.2 7.7-10 7.7-19.6 0-17.7-14.3-32-32-32zM224 368a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>`,"circle-xmark":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M256 48a208 208 0 1 1 0 416 208 208 0 1 1 0-416zm0 464a256 256 0 1 0 0-512 256 256 0 1 0 0 512zM167 167c-9.4 9.4-9.4 24.6 0 33.9l55 55-55 55c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l55-55 55 55c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-55-55 55-55c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-55 55-55-55c-9.4-9.4-24.6-9.4-33.9 0z"/></svg>`,copy:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M384 336l-192 0c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l133.5 0c4.2 0 8.3 1.7 11.3 4.7l58.5 58.5c3 3 4.7 7.1 4.7 11.3L400 320c0 8.8-7.2 16-16 16zM192 384l192 0c35.3 0 64-28.7 64-64l0-197.5c0-17-6.7-33.3-18.7-45.3L370.7 18.7C358.7 6.7 342.5 0 325.5 0L192 0c-35.3 0-64 28.7-64 64l0 256c0 35.3 28.7 64 64 64zM64 128c-35.3 0-64 28.7-64 64L0 448c0 35.3 28.7 64 64 64l192 0c35.3 0 64-28.7 64-64l0-16-48 0 0 16c0 8.8-7.2 16-16 16L64 464c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l16 0 0-48-16 0z"/></svg>`,eye:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M288 80C222.8 80 169.2 109.6 128.1 147.7 89.6 183.5 63 226 49.4 256 63 286 89.6 328.5 128.1 364.3 169.2 402.4 222.8 432 288 432s118.8-29.6 159.9-67.7C486.4 328.5 513 286 526.6 256 513 226 486.4 183.5 447.9 147.7 406.8 109.6 353.2 80 288 80zM95.4 112.6C142.5 68.8 207.2 32 288 32s145.5 36.8 192.6 80.6c46.8 43.5 78.1 95.4 93 131.1 3.3 7.9 3.3 16.7 0 24.6-14.9 35.7-46.2 87.7-93 131.1-47.1 43.7-111.8 80.6-192.6 80.6S142.5 443.2 95.4 399.4c-46.8-43.5-78.1-95.4-93-131.1-3.3-7.9-3.3-16.7 0-24.6 14.9-35.7 46.2-87.7 93-131.1zM288 336c44.2 0 80-35.8 80-80 0-29.6-16.1-55.5-40-69.3-1.4 59.7-49.6 107.9-109.3 109.3 13.8 23.9 39.7 40 69.3 40zm-79.6-88.4c2.5 .3 5 .4 7.6 .4 35.3 0 64-28.7 64-64 0-2.6-.2-5.1-.4-7.6-37.4 3.9-67.2 33.7-71.1 71.1zm45.6-115c10.8-3 22.2-4.5 33.9-4.5 8.8 0 17.5 .9 25.8 2.6 .3 .1 .5 .1 .8 .2 57.9 12.2 101.4 63.7 101.4 125.2 0 70.7-57.3 128-128 128-61.6 0-113-43.5-125.2-101.4-1.8-8.6-2.8-17.5-2.8-26.6 0-11 1.4-21.8 4-32 .2-.7 .3-1.3 .5-1.9 11.9-43.4 46.1-77.6 89.5-89.5z"/></svg>`,"eye-slash":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M41-24.9c-9.4-9.4-24.6-9.4-33.9 0S-2.3-.3 7 9.1l528 528c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-96.4-96.4c2.7-2.4 5.4-4.8 8-7.2 46.8-43.5 78.1-95.4 93-131.1 3.3-7.9 3.3-16.7 0-24.6-14.9-35.7-46.2-87.7-93-131.1-47.1-43.7-111.8-80.6-192.6-80.6-56.8 0-105.6 18.2-146 44.2L41-24.9zM176.9 111.1c32.1-18.9 69.2-31.1 111.1-31.1 65.2 0 118.8 29.6 159.9 67.7 38.5 35.7 65.1 78.3 78.6 108.3-13.6 30-40.2 72.5-78.6 108.3-3.1 2.8-6.2 5.6-9.4 8.4L393.8 328c14-20.5 22.2-45.3 22.2-72 0-70.7-57.3-128-128-128-26.7 0-51.5 8.2-72 22.2l-39.1-39.1zm182 182l-108-108c11.1-5.8 23.7-9.1 37.1-9.1 44.2 0 80 35.8 80 80 0 13.4-3.3 26-9.1 37.1zM103.4 173.2l-34-34c-32.6 36.8-55 75.8-66.9 104.5-3.3 7.9-3.3 16.7 0 24.6 14.9 35.7 46.2 87.7 93 131.1 47.1 43.7 111.8 80.6 192.6 80.6 37.3 0 71.2-7.9 101.5-20.6L352.2 422c-20 6.4-41.4 10-64.2 10-65.2 0-118.8-29.6-159.9-67.7-38.5-35.7-65.1-78.3-78.6-108.3 10.4-23.1 28.6-53.6 54-82.8z"/></svg>`,star:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M288.1-32c9 0 17.3 5.1 21.4 13.1L383 125.3 542.9 150.7c8.9 1.4 16.3 7.7 19.1 16.3s.5 18-5.8 24.4L441.7 305.9 467 465.8c1.4 8.9-2.3 17.9-9.6 23.2s-17 6.1-25 2L288.1 417.6 143.8 491c-8 4.1-17.7 3.3-25-2s-11-14.2-9.6-23.2L134.4 305.9 20 191.4c-6.4-6.4-8.6-15.8-5.8-24.4s10.1-14.9 19.1-16.3l159.9-25.4 73.6-144.2c4.1-8 12.4-13.1 21.4-13.1zm0 76.8L230.3 158c-3.5 6.8-10 11.6-17.6 12.8l-125.5 20 89.8 89.9c5.4 5.4 7.9 13.1 6.7 20.7l-19.8 125.5 113.3-57.6c6.8-3.5 14.9-3.5 21.8 0l113.3 57.6-19.8-125.5c-1.2-7.6 1.3-15.3 6.7-20.7l89.8-89.9-125.5-20c-7.6-1.2-14.1-6-17.6-12.8L288.1 44.8z"/></svg>`}},je={name:`system`,resolver:(e,t=`classic`,n=`solid`)=>{let r=Ae[n][e]??Ae.regular[e]??Ae.regular[`circle-question`];return r?ke(r):``}},Me=`classic`,Ne=[de,je],Pe=[];function Fe(e){Pe.push(e)}function Ie(e){Pe=Pe.filter(t=>t!==e)}function Le(e){return Ne.find(t=>t.name===e)}function Re(e,t){ze(e),Ne.push({name:e,resolver:t.resolver,mutator:t.mutator,spriteSheet:t.spriteSheet}),Pe.forEach(t=>{t.library===e&&t.setIcon()})}function ze(e){Ne=Ne.filter(t=>t.name!==e)}function Be(){return Me}var Ve=Object.defineProperty,He=Object.getOwnPropertyDescriptor,Ue=e=>{throw TypeError(e)},M=(e,t,n,r)=>{for(var i=r>1?void 0:r?He(t,n):t,a=e.length-1,o;a>=0;a--)(o=e[a])&&(i=(r?o(t,n,i):o(i))||i);return r&&i&&Ve(t,n,i),i},We=(e,t,n)=>t.has(e)||Ue(`Cannot `+n),Ge=(e,t,n)=>(We(e,t,`read from private field`),n?n.call(e):t.get(e)),Ke=(e,t,n)=>t.has(e)?Ue(`Cannot add the same private member more than once`):t instanceof WeakSet?t.add(e):t.set(e,n),qe=(e,t,n,r)=>(We(e,t,`write to private field`),r?r.call(e,n):t.set(e,n),n),Je={alert:`triangle-exclamation`,asc:`arrow-down-short-wide`,asset:`image`,assets:`image`,circleuarr:`circle-arrow-up`,collapse:`down-left-and-up-right-to-center`,condition:`diamond`,darr:`arrow-down`,date:`calendar`,desc:`arrow-down-wide-short`,disabled:`circle-dashed`,done:`circle-check`,downangle:`angle-down`,draft:`scribble`,edit:`pencil`,enabled:`circle`,expand:`up-right-and-down-left-from-center`,external:`arrow-up-right-from-square`,field:`pen-to-square`,help:`circle-question`,home:`house`,info:`circle-info`,insecure:`unlock`,larr:`arrow-left`,layout:`table-layout`,leftangle:`angle-left`,listrtl:`list-flip`,location:`location-dot`,mail:`envelope`,menu:`bars`,move:`grip-dots`,newstamp:`certificate`,paperplane:`paper-plane`,plugin:`plug`,rarr:`arrow-right`,refresh:`arrows-rotate`,remove:`xmark`,rightangle:`angle-right`,rotate:`rotate-left`,routes:`signs-post`,search:`magnifying-glass`,secure:`lock`,settings:`gear`,shareleft:`share-flip`,shuteye:`eye-slash`,"sidebar-left":`sidebar`,"sidebar-right":`sidebar-flip`,"sidebar-start":`sidebar`,"sidebar-end":`sidebar-flip`,structure:`list-tree`,structurertl:`list-tree-flip`,template:`file-code`,time:`clock`,tool:`wrench`,uarr:`arrow-up`,upangle:`angle-up`,view:`eye`,wand:`wand-magic-sparkles`};function Ye(e,t=`classic`,n=`regular`){let r=`solid`,i=n,a=e.endsWith(`.svg`)?e.split(`.svg`)[0]:e;if(e.includes(`/`)){let[t,...n]=e.split(`/`);i=t??i,a=n.join(`/`)}return i===`thin`?r=`thin`:i===`light`?r=`light`:i===`regular`?r=`regular`:i===`solid`&&(r=`solid`),t===`brands`&&(r=`brands`),i===`custom-icons`&&(r=`custom-icons`),a=Je[a]??a,`/vendor/craft/icons/${r}/${a}.svg`}function Xe(){Re(`default`,{resolver:(e,t=`classic`,n=`solid`)=>Ye(e,t,n),mutator:e=>e.setAttribute(`fill`,`currentColor`)})}var Ze=class extends HTMLElement{constructor(...e){super(...e),this.cookieName=null,this.state=`collapsed`,this.expanded=!1,this.handleOpen=()=>{this.trigger?.setAttribute(`aria-expanded`,`true`),this.expanded=!0,this.dispatchEvent(new CustomEvent(`open`)),this.target&&(this.target.dataset.state=`expanded`),this.cookieName&&window.Craft?.setCookie(this.cookieName,`expanded`)},this.handleClose=()=>{this.trigger?.setAttribute(`aria-expanded`,`false`),this.expanded=!1,this.dispatchEvent(new CustomEvent(`close`)),this.target&&(this.target.dataset.state=`collapsed`),this.cookieName&&window.Craft?.setCookie(this.cookieName,`collapsed`)}}get trigger(){return this.querySelector(`button[type="button"]`)}get target(){if(!this.trigger)return console.warn(`No trigger found for disclosure.`),null;let e=this.trigger.getAttribute(`aria-controls`);return e?document.getElementById(e):(console.warn(`No target selector found for disclosure.`),null)}connectedCallback(){if(!this.trigger){console.error(`craft-disclosure elements must include a button`,this);return}if(!this.target){console.error(`No target with id ${this.trigger.getAttribute(`aria-controls`)} found for disclosure. `,this.trigger);return}this.cookieName=this.getAttribute(`cookie-name`),this.state=this.getAttribute(`state`)??`expanded`,this.trigger.setAttribute(`aria-expanded`,this.state===`expanded`?`true`:`false`),this.trigger.addEventListener(`click`,this.toggle.bind(this)),this.state===`expanded`?this.open():this.close()}disconnectedCallback(){this.open(),this.trigger?.removeEventListener(`click`,this.toggle.bind(this))}attributeChangedCallback(e,t,n){e===`state`&&(n===`expanded`?this.handleOpen():this.handleClose())}toggle(){this.expanded?this.close():this.open()}open(){this.setAttribute(`state`,`expanded`)}close(){this.setAttribute(`state`,`collapsed`)}};Ze.observedAttributes=[`state`],customElements.get(`craft-disclosure`)||customElements.define(`craft-disclosure`,Ze);var Qe=v`
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
`,$e=class extends C{constructor(...e){super(...e),this.visible=!0}show(){this.visible=!0,this.dispatchEvent(new CustomEvent(`show`))}hide(){this.visible=!1,this.dispatchEvent(new CustomEvent(`hide`))}focus(){this.wrapper?.focus()}render(){return g`
      <div
        tabindex="-1"
        class="${A({wrapper:!0,hidden:!this.visible})}"
      >
        <div class="spinner"></div>
        <span class="message visually-hidden"><slot /></span>
      </div>
    `}};$e.styles=[Qe],m([w({reflect:!0})],$e.prototype,`visible`,void 0),m([D(`.wrapper`)],$e.prototype,`wrapper`,void 0),customElements.get(`craft-spinner`)||customElements.define(`craft-spinner`,$e);var et=class extends Event{constructor(){super(`wa-reposition`,{bubbles:!0,cancelable:!1,composed:!0})}},tt=`:host {
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
`,nt,rt=class extends C{constructor(){super(),Ke(this,nt,!1),this.initialReflectedProperties=new Map,this.didSSR=!!this.shadowRoot,this.customStates={set:(e,t)=>{if(this.internals?.states)try{t?this.internals.states.add(e):this.internals.states.delete(e)}catch(e){if(String(e).includes(`must start with '--'`))console.error(`Your browser implements an outdated version of CustomStateSet. Consider using a polyfill`);else throw e}},has:e=>{if(!this.internals?.states)return!1;try{return this.internals.states.has(e)}catch{return!1}}};try{this.internals=this.attachInternals()}catch{console.error(`Element internals are not supported in your browser. Consider using a polyfill`)}this.customStates.set(`wa-defined`,!0);let e=this.constructor;for(let[t,n]of e.elementProperties)n.default===`inherit`&&n.initial!==void 0&&typeof t==`string`&&this.customStates.set(`initial-${t}-${n.initial}`,!0)}static get styles(){return[tt,...Array.isArray(this.css)?this.css:this.css?[this.css]:[]].map(e=>typeof e==`string`?x(e):e)}attributeChangedCallback(e,t,n){Ge(this,nt)||(this.constructor.elementProperties.forEach((e,t)=>{e.reflect&&this[t]!=null&&this.initialReflectedProperties.set(t,this[t])}),qe(this,nt,!0)),super.attributeChangedCallback(e,t,n)}willUpdate(e){super.willUpdate(e),this.initialReflectedProperties.forEach((t,n)=>{e.has(n)&&this[n]==null&&(this[n]=t)})}firstUpdated(e){super.firstUpdated(e),this.didSSR&&this.shadowRoot?.querySelectorAll(`slot`).forEach(e=>{e.dispatchEvent(new Event(`slotchange`,{bubbles:!0,composed:!1,cancelable:!1}))})}update(e){try{super.update(e)}catch(e){if(this.didSSR&&!this.hasUpdated){let t=new Event(`lit-hydration-error`,{bubbles:!0,composed:!0,cancelable:!1});t.error=e,this.dispatchEvent(t)}throw e}}relayNativeEvent(e,t){e.stopImmediatePropagation(),this.dispatchEvent(new e.constructor(e.type,{...e,...t}))}};nt=new WeakMap,M([w()],rt.prototype,`dir`,2),M([w()],rt.prototype,`lang`,2),M([w({type:Boolean,reflect:!0,attribute:`did-ssr`})],rt.prototype,`didSSR`,2);var it=Math.min,at=Math.max,ot=Math.round,st=Math.floor,ct=e=>({x:e,y:e}),lt={left:`right`,right:`left`,bottom:`top`,top:`bottom`},ut={start:`end`,end:`start`};function dt(e,t,n){return at(e,it(t,n))}function ft(e,t){return typeof e==`function`?e(t):e}function pt(e){return e.split(`-`)[0]}function mt(e){return e.split(`-`)[1]}function ht(e){return e===`x`?`y`:`x`}function gt(e){return e===`y`?`height`:`width`}var _t=new Set([`top`,`bottom`]);function vt(e){return _t.has(pt(e))?`y`:`x`}function yt(e){return ht(vt(e))}function bt(e,t,n){n===void 0&&(n=!1);let r=mt(e),i=yt(e),a=gt(i),o=i===`x`?r===(n?`end`:`start`)?`right`:`left`:r===`start`?`bottom`:`top`;return t.reference[a]>t.floating[a]&&(o=kt(o)),[o,kt(o)]}function xt(e){let t=kt(e);return[St(e),t,St(t)]}function St(e){return e.replace(/start|end/g,e=>ut[e])}var Ct=[`left`,`right`],wt=[`right`,`left`],Tt=[`top`,`bottom`],Et=[`bottom`,`top`];function Dt(e,t,n){switch(e){case`top`:case`bottom`:return n?t?wt:Ct:t?Ct:wt;case`left`:case`right`:return t?Tt:Et;default:return[]}}function Ot(e,t,n,r){let i=mt(e),a=Dt(pt(e),n===`start`,r);return i&&(a=a.map(e=>e+`-`+i),t&&(a=a.concat(a.map(St)))),a}function kt(e){return e.replace(/left|right|bottom|top/g,e=>lt[e])}function At(e){return{top:0,right:0,bottom:0,left:0,...e}}function jt(e){return typeof e==`number`?{top:e,right:e,bottom:e,left:e}:At(e)}function Mt(e){let{x:t,y:n,width:r,height:i}=e;return{width:r,height:i,top:n,left:t,right:t+r,bottom:n+i,x:t,y:n}}function Nt(e,t,n){let{reference:r,floating:i}=e,a=vt(t),o=yt(t),s=gt(o),c=pt(t),l=a===`y`,u=r.x+r.width/2-i.width/2,d=r.y+r.height/2-i.height/2,f=r[s]/2-i[s]/2,p;switch(c){case`top`:p={x:u,y:r.y-i.height};break;case`bottom`:p={x:u,y:r.y+r.height};break;case`right`:p={x:r.x+r.width,y:d};break;case`left`:p={x:r.x-i.width,y:d};break;default:p={x:r.x,y:r.y}}switch(mt(t)){case`start`:p[o]-=f*(n&&l?-1:1);break;case`end`:p[o]+=f*(n&&l?-1:1);break}return p}var Pt=async(e,t,n)=>{let{placement:r=`bottom`,strategy:i=`absolute`,middleware:a=[],platform:o}=n,s=a.filter(Boolean),c=await(o.isRTL==null?void 0:o.isRTL(t)),l=await o.getElementRects({reference:e,floating:t,strategy:i}),{x:u,y:d}=Nt(l,r,c),f=r,p={},m=0;for(let n=0;n<s.length;n++){let{name:a,fn:h}=s[n],{x:g,y:_,data:v,reset:y}=await h({x:u,y:d,initialPlacement:r,placement:f,strategy:i,middlewareData:p,rects:l,platform:o,elements:{reference:e,floating:t}});u=g??u,d=_??d,p={...p,[a]:{...p[a],...v}},y&&m<=50&&(m++,typeof y==`object`&&(y.placement&&(f=y.placement),y.rects&&(l=y.rects===!0?await o.getElementRects({reference:e,floating:t,strategy:i}):y.rects),{x:u,y:d}=Nt(l,f,c)),n=-1)}return{x:u,y:d,placement:f,strategy:i,middlewareData:p}};async function Ft(e,t){t===void 0&&(t={});let{x:n,y:r,platform:i,rects:a,elements:o,strategy:s}=e,{boundary:c=`clippingAncestors`,rootBoundary:l=`viewport`,elementContext:u=`floating`,altBoundary:d=!1,padding:f=0}=ft(t,e),p=jt(f),m=o[d?u===`floating`?`reference`:`floating`:u],h=Mt(await i.getClippingRect({element:await(i.isElement==null?void 0:i.isElement(m))??!0?m:m.contextElement||await(i.getDocumentElement==null?void 0:i.getDocumentElement(o.floating)),boundary:c,rootBoundary:l,strategy:s})),g=u===`floating`?{x:n,y:r,width:a.floating.width,height:a.floating.height}:a.reference,_=await(i.getOffsetParent==null?void 0:i.getOffsetParent(o.floating)),v=await(i.isElement==null?void 0:i.isElement(_))&&await(i.getScale==null?void 0:i.getScale(_))||{x:1,y:1},y=Mt(i.convertOffsetParentRelativeRectToViewportRelativeRect?await i.convertOffsetParentRelativeRectToViewportRelativeRect({elements:o,rect:g,offsetParent:_,strategy:s}):g);return{top:(h.top-y.top+p.top)/v.y,bottom:(y.bottom-h.bottom+p.bottom)/v.y,left:(h.left-y.left+p.left)/v.x,right:(y.right-h.right+p.right)/v.x}}var It=e=>({name:`arrow`,options:e,async fn(t){let{x:n,y:r,placement:i,rects:a,platform:o,elements:s,middlewareData:c}=t,{element:l,padding:u=0}=ft(e,t)||{};if(l==null)return{};let d=jt(u),f={x:n,y:r},p=yt(i),m=gt(p),h=await o.getDimensions(l),g=p===`y`,_=g?`top`:`left`,v=g?`bottom`:`right`,y=g?`clientHeight`:`clientWidth`,b=a.reference[m]+a.reference[p]-f[p]-a.floating[m],x=f[p]-a.reference[p],S=await(o.getOffsetParent==null?void 0:o.getOffsetParent(l)),C=S?S[y]:0;(!C||!await(o.isElement==null?void 0:o.isElement(S)))&&(C=s.floating[y]||a.floating[m]);let w=b/2-x/2,T=C/2-h[m]/2-1,E=it(d[_],T),D=it(d[v],T),O=E,k=C-h[m]-D,A=C/2-h[m]/2+w,ee=dt(O,A,k),te=!c.arrow&&mt(i)!=null&&A!==ee&&a.reference[m]/2-(A<O?E:D)-h[m]/2<0,ne=te?A<O?A-O:A-k:0;return{[p]:f[p]+ne,data:{[p]:ee,centerOffset:A-ee-ne,...te&&{alignmentOffset:ne}},reset:te}}}),Lt=function(e){return e===void 0&&(e={}),{name:`flip`,options:e,async fn(t){var n;let{placement:r,middlewareData:i,rects:a,initialPlacement:o,platform:s,elements:c}=t,{mainAxis:l=!0,crossAxis:u=!0,fallbackPlacements:d,fallbackStrategy:f=`bestFit`,fallbackAxisSideDirection:p=`none`,flipAlignment:m=!0,...h}=ft(e,t);if((n=i.arrow)!=null&&n.alignmentOffset)return{};let g=pt(r),_=vt(o),v=pt(o)===o,y=await(s.isRTL==null?void 0:s.isRTL(c.floating)),b=d||(v||!m?[kt(o)]:xt(o)),x=p!==`none`;!d&&x&&b.push(...Ot(o,m,p,y));let S=[o,...b],C=await Ft(t,h),w=[],T=i.flip?.overflows||[];if(l&&w.push(C[g]),u){let e=bt(r,a,y);w.push(C[e[0]],C[e[1]])}if(T=[...T,{placement:r,overflows:w}],!w.every(e=>e<=0)){let e=(i.flip?.index||0)+1,t=S[e];if(t&&(!(u===`alignment`&&_!==vt(t))||T.every(e=>vt(e.placement)===_?e.overflows[0]>0:!0)))return{data:{index:e,overflows:T},reset:{placement:t}};let n=T.filter(e=>e.overflows[0]<=0).sort((e,t)=>e.overflows[1]-t.overflows[1])[0]?.placement;if(!n)switch(f){case`bestFit`:{let e=T.filter(e=>{if(x){let t=vt(e.placement);return t===_||t===`y`}return!0}).map(e=>[e.placement,e.overflows.filter(e=>e>0).reduce((e,t)=>e+t,0)]).sort((e,t)=>e[1]-t[1])[0]?.[0];e&&(n=e);break}case`initialPlacement`:n=o;break}if(r!==n)return{reset:{placement:n}}}return{}}}},Rt=new Set([`left`,`top`]);async function zt(e,t){let{placement:n,platform:r,elements:i}=e,a=await(r.isRTL==null?void 0:r.isRTL(i.floating)),o=pt(n),s=mt(n),c=vt(n)===`y`,l=Rt.has(o)?-1:1,u=a&&c?-1:1,d=ft(t,e),{mainAxis:f,crossAxis:p,alignmentAxis:m}=typeof d==`number`?{mainAxis:d,crossAxis:0,alignmentAxis:null}:{mainAxis:d.mainAxis||0,crossAxis:d.crossAxis||0,alignmentAxis:d.alignmentAxis};return s&&typeof m==`number`&&(p=s===`end`?m*-1:m),c?{x:p*u,y:f*l}:{x:f*l,y:p*u}}var Bt=function(e){return e===void 0&&(e=0),{name:`offset`,options:e,async fn(t){var n;let{x:r,y:i,placement:a,middlewareData:o}=t,s=await zt(t,e);return a===o.offset?.placement&&(n=o.arrow)!=null&&n.alignmentOffset?{}:{x:r+s.x,y:i+s.y,data:{...s,placement:a}}}}},Vt=function(e){return e===void 0&&(e={}),{name:`shift`,options:e,async fn(t){let{x:n,y:r,placement:i}=t,{mainAxis:a=!0,crossAxis:o=!1,limiter:s={fn:e=>{let{x:t,y:n}=e;return{x:t,y:n}}},...c}=ft(e,t),l={x:n,y:r},u=await Ft(t,c),d=vt(pt(i)),f=ht(d),p=l[f],m=l[d];if(a){let e=f===`y`?`top`:`left`,t=f===`y`?`bottom`:`right`,n=p+u[e],r=p-u[t];p=dt(n,p,r)}if(o){let e=d===`y`?`top`:`left`,t=d===`y`?`bottom`:`right`,n=m+u[e],r=m-u[t];m=dt(n,m,r)}let h=s.fn({...t,[f]:p,[d]:m});return{...h,data:{x:h.x-n,y:h.y-r,enabled:{[f]:a,[d]:o}}}}}},Ht=function(e){return e===void 0&&(e={}),{name:`size`,options:e,async fn(t){var n,r;let{placement:i,rects:a,platform:o,elements:s}=t,{apply:c=()=>{},...l}=ft(e,t),u=await Ft(t,l),d=pt(i),f=mt(i),p=vt(i)===`y`,{width:m,height:h}=a.floating,g,_;d===`top`||d===`bottom`?(g=d,_=f===(await(o.isRTL==null?void 0:o.isRTL(s.floating))?`start`:`end`)?`left`:`right`):(_=d,g=f===`end`?`top`:`bottom`);let v=h-u.top-u.bottom,y=m-u.left-u.right,b=it(h-u[g],v),x=it(m-u[_],y),S=!t.middlewareData.shift,C=b,w=x;if((n=t.middlewareData.shift)!=null&&n.enabled.x&&(w=y),(r=t.middlewareData.shift)!=null&&r.enabled.y&&(C=v),S&&!f){let e=at(u.left,0),t=at(u.right,0),n=at(u.top,0),r=at(u.bottom,0);p?w=m-2*(e!==0||t!==0?e+t:at(u.left,u.right)):C=h-2*(n!==0||r!==0?n+r:at(u.top,u.bottom))}await c({...t,availableWidth:w,availableHeight:C});let T=await o.getDimensions(s.floating);return m!==T.width||h!==T.height?{reset:{rects:!0}}:{}}}};function Ut(){return typeof window<`u`}function Wt(e){return qt(e)?(e.nodeName||``).toLowerCase():`#document`}function Gt(e){var t;return(e==null||(t=e.ownerDocument)==null?void 0:t.defaultView)||window}function Kt(e){return((qt(e)?e.ownerDocument:e.document)||window.document)?.documentElement}function qt(e){return Ut()?e instanceof Node||e instanceof Gt(e).Node:!1}function Jt(e){return Ut()?e instanceof Element||e instanceof Gt(e).Element:!1}function Yt(e){return Ut()?e instanceof HTMLElement||e instanceof Gt(e).HTMLElement:!1}function Xt(e){return!Ut()||typeof ShadowRoot>`u`?!1:e instanceof ShadowRoot||e instanceof Gt(e).ShadowRoot}var Zt=new Set([`inline`,`contents`]);function Qt(e){let{overflow:t,overflowX:n,overflowY:r,display:i}=fn(e);return/auto|scroll|overlay|hidden|clip/.test(t+r+n)&&!Zt.has(i)}var $t=new Set([`table`,`td`,`th`]);function en(e){return $t.has(Wt(e))}var tn=[`:popover-open`,`:modal`];function nn(e){return tn.some(t=>{try{return e.matches(t)}catch{return!1}})}var rn=[`transform`,`translate`,`scale`,`rotate`,`perspective`],an=[`transform`,`translate`,`scale`,`rotate`,`perspective`,`filter`],on=[`paint`,`layout`,`strict`,`content`];function sn(e){let t=ln(),n=Jt(e)?fn(e):e;return rn.some(e=>n[e]?n[e]!==`none`:!1)||(n.containerType?n.containerType!==`normal`:!1)||!t&&(n.backdropFilter?n.backdropFilter!==`none`:!1)||!t&&(n.filter?n.filter!==`none`:!1)||an.some(e=>(n.willChange||``).includes(e))||on.some(e=>(n.contain||``).includes(e))}function cn(e){let t=mn(e);for(;Yt(t)&&!dn(t);){if(sn(t))return t;if(nn(t))return null;t=mn(t)}return null}function ln(){return typeof CSS>`u`||!CSS.supports?!1:CSS.supports(`-webkit-backdrop-filter`,`none`)}var un=new Set([`html`,`body`,`#document`]);function dn(e){return un.has(Wt(e))}function fn(e){return Gt(e).getComputedStyle(e)}function pn(e){return Jt(e)?{scrollLeft:e.scrollLeft,scrollTop:e.scrollTop}:{scrollLeft:e.scrollX,scrollTop:e.scrollY}}function mn(e){if(Wt(e)===`html`)return e;let t=e.assignedSlot||e.parentNode||Xt(e)&&e.host||Kt(e);return Xt(t)?t.host:t}function hn(e){let t=mn(e);return dn(t)?e.ownerDocument?e.ownerDocument.body:e.body:Yt(t)&&Qt(t)?t:hn(t)}function gn(e,t,n){t===void 0&&(t=[]),n===void 0&&(n=!0);let r=hn(e),i=r===e.ownerDocument?.body,a=Gt(r);if(i){let e=_n(a);return t.concat(a,a.visualViewport||[],Qt(r)?r:[],e&&n?gn(e):[])}return t.concat(r,gn(r,[],n))}function _n(e){return e.parent&&Object.getPrototypeOf(e.parent)?e.frameElement:null}function vn(e){let t=fn(e),n=parseFloat(t.width)||0,r=parseFloat(t.height)||0,i=Yt(e),a=i?e.offsetWidth:n,o=i?e.offsetHeight:r,s=ot(n)!==a||ot(r)!==o;return s&&(n=a,r=o),{width:n,height:r,$:s}}function yn(e){return Jt(e)?e:e.contextElement}function bn(e){let t=yn(e);if(!Yt(t))return ct(1);let n=t.getBoundingClientRect(),{width:r,height:i,$:a}=vn(t),o=(a?ot(n.width):n.width)/r,s=(a?ot(n.height):n.height)/i;return(!o||!Number.isFinite(o))&&(o=1),(!s||!Number.isFinite(s))&&(s=1),{x:o,y:s}}var xn=ct(0);function Sn(e){let t=Gt(e);return!ln()||!t.visualViewport?xn:{x:t.visualViewport.offsetLeft,y:t.visualViewport.offsetTop}}function Cn(e,t,n){return t===void 0&&(t=!1),!n||t&&n!==Gt(e)?!1:t}function wn(e,t,n,r){t===void 0&&(t=!1),n===void 0&&(n=!1);let i=e.getBoundingClientRect(),a=yn(e),o=ct(1);t&&(r?Jt(r)&&(o=bn(r)):o=bn(e));let s=Cn(a,n,r)?Sn(a):ct(0),c=(i.left+s.x)/o.x,l=(i.top+s.y)/o.y,u=i.width/o.x,d=i.height/o.y;if(a){let e=Gt(a),t=r&&Jt(r)?Gt(r):r,n=e,i=_n(n);for(;i&&r&&t!==n;){let e=bn(i),t=i.getBoundingClientRect(),r=fn(i),a=t.left+(i.clientLeft+parseFloat(r.paddingLeft))*e.x,o=t.top+(i.clientTop+parseFloat(r.paddingTop))*e.y;c*=e.x,l*=e.y,u*=e.x,d*=e.y,c+=a,l+=o,n=Gt(i),i=_n(n)}}return Mt({width:u,height:d,x:c,y:l})}function Tn(e,t){let n=pn(e).scrollLeft;return t?t.left+n:wn(Kt(e)).left+n}function En(e,t){let n=e.getBoundingClientRect();return{x:n.left+t.scrollLeft-Tn(e,n),y:n.top+t.scrollTop}}function Dn(e){let{elements:t,rect:n,offsetParent:r,strategy:i}=e,a=i===`fixed`,o=Kt(r),s=t?nn(t.floating):!1;if(r===o||s&&a)return n;let c={scrollLeft:0,scrollTop:0},l=ct(1),u=ct(0),d=Yt(r);if((d||!d&&!a)&&((Wt(r)!==`body`||Qt(o))&&(c=pn(r)),Yt(r))){let e=wn(r);l=bn(r),u.x=e.x+r.clientLeft,u.y=e.y+r.clientTop}let f=o&&!d&&!a?En(o,c):ct(0);return{width:n.width*l.x,height:n.height*l.y,x:n.x*l.x-c.scrollLeft*l.x+u.x+f.x,y:n.y*l.y-c.scrollTop*l.y+u.y+f.y}}function On(e){return Array.from(e.getClientRects())}function kn(e){let t=Kt(e),n=pn(e),r=e.ownerDocument.body,i=at(t.scrollWidth,t.clientWidth,r.scrollWidth,r.clientWidth),a=at(t.scrollHeight,t.clientHeight,r.scrollHeight,r.clientHeight),o=-n.scrollLeft+Tn(e),s=-n.scrollTop;return fn(r).direction===`rtl`&&(o+=at(t.clientWidth,r.clientWidth)-i),{width:i,height:a,x:o,y:s}}var An=25;function jn(e,t){let n=Gt(e),r=Kt(e),i=n.visualViewport,a=r.clientWidth,o=r.clientHeight,s=0,c=0;if(i){a=i.width,o=i.height;let e=ln();(!e||e&&t===`fixed`)&&(s=i.offsetLeft,c=i.offsetTop)}let l=Tn(r);if(l<=0){let e=r.ownerDocument,t=e.body,n=getComputedStyle(t),i=e.compatMode===`CSS1Compat`&&parseFloat(n.marginLeft)+parseFloat(n.marginRight)||0,o=Math.abs(r.clientWidth-t.clientWidth-i);o<=An&&(a-=o)}else l<=An&&(a+=l);return{width:a,height:o,x:s,y:c}}var Mn=new Set([`absolute`,`fixed`]);function Nn(e,t){let n=wn(e,!0,t===`fixed`),r=n.top+e.clientTop,i=n.left+e.clientLeft,a=Yt(e)?bn(e):ct(1);return{width:e.clientWidth*a.x,height:e.clientHeight*a.y,x:i*a.x,y:r*a.y}}function Pn(e,t,n){let r;if(t===`viewport`)r=jn(e,n);else if(t===`document`)r=kn(Kt(e));else if(Jt(t))r=Nn(t,n);else{let n=Sn(e);r={x:t.x-n.x,y:t.y-n.y,width:t.width,height:t.height}}return Mt(r)}function Fn(e,t){let n=mn(e);return n===t||!Jt(n)||dn(n)?!1:fn(n).position===`fixed`||Fn(n,t)}function In(e,t){let n=t.get(e);if(n)return n;let r=gn(e,[],!1).filter(e=>Jt(e)&&Wt(e)!==`body`),i=null,a=fn(e).position===`fixed`,o=a?mn(e):e;for(;Jt(o)&&!dn(o);){let t=fn(o),n=sn(o);!n&&t.position===`fixed`&&(i=null),(a?!n&&!i:!n&&t.position===`static`&&i&&Mn.has(i.position)||Qt(o)&&!n&&Fn(e,o))?r=r.filter(e=>e!==o):i=t,o=mn(o)}return t.set(e,r),r}function Ln(e){let{element:t,boundary:n,rootBoundary:r,strategy:i}=e,a=[...n===`clippingAncestors`?nn(t)?[]:In(t,this._c):[].concat(n),r],o=a[0],s=a.reduce((e,n)=>{let r=Pn(t,n,i);return e.top=at(r.top,e.top),e.right=it(r.right,e.right),e.bottom=it(r.bottom,e.bottom),e.left=at(r.left,e.left),e},Pn(t,o,i));return{width:s.right-s.left,height:s.bottom-s.top,x:s.left,y:s.top}}function Rn(e){let{width:t,height:n}=vn(e);return{width:t,height:n}}function zn(e,t,n){let r=Yt(t),i=Kt(t),a=n===`fixed`,o=wn(e,!0,a,t),s={scrollLeft:0,scrollTop:0},c=ct(0);function l(){c.x=Tn(i)}if(r||!r&&!a)if((Wt(t)!==`body`||Qt(i))&&(s=pn(t)),r){let e=wn(t,!0,a,t);c.x=e.x+t.clientLeft,c.y=e.y+t.clientTop}else i&&l();a&&!r&&i&&l();let u=i&&!r&&!a?En(i,s):ct(0);return{x:o.left+s.scrollLeft-c.x-u.x,y:o.top+s.scrollTop-c.y-u.y,width:o.width,height:o.height}}function Bn(e){return fn(e).position===`static`}function Vn(e,t){if(!Yt(e)||fn(e).position===`fixed`)return null;if(t)return t(e);let n=e.offsetParent;return Kt(e)===n&&(n=n.ownerDocument.body),n}function Hn(e,t){let n=Gt(e);if(nn(e))return n;if(!Yt(e)){let t=mn(e);for(;t&&!dn(t);){if(Jt(t)&&!Bn(t))return t;t=mn(t)}return n}let r=Vn(e,t);for(;r&&en(r)&&Bn(r);)r=Vn(r,t);return r&&dn(r)&&Bn(r)&&!sn(r)?n:r||cn(e)||n}var Un=async function(e){let t=this.getOffsetParent||Hn,n=this.getDimensions,r=await n(e.floating);return{reference:zn(e.reference,await t(e.floating),e.strategy),floating:{x:0,y:0,width:r.width,height:r.height}}};function Wn(e){return fn(e).direction===`rtl`}var Gn={convertOffsetParentRelativeRectToViewportRelativeRect:Dn,getDocumentElement:Kt,getClippingRect:Ln,getOffsetParent:Hn,getElementRects:Un,getClientRects:On,getDimensions:Rn,getScale:bn,isElement:Jt,isRTL:Wn};function Kn(e,t){return e.x===t.x&&e.y===t.y&&e.width===t.width&&e.height===t.height}function qn(e,t){let n=null,r,i=Kt(e);function a(){var e;clearTimeout(r),(e=n)==null||e.disconnect(),n=null}function o(s,c){s===void 0&&(s=!1),c===void 0&&(c=1),a();let l=e.getBoundingClientRect(),{left:u,top:d,width:f,height:p}=l;if(s||t(),!f||!p)return;let m=st(d),h=st(i.clientWidth-(u+f)),g=st(i.clientHeight-(d+p)),_=st(u),v={rootMargin:-m+`px `+-h+`px `+-g+`px `+-_+`px`,threshold:at(0,it(1,c))||1},y=!0;function b(t){let n=t[0].intersectionRatio;if(n!==c){if(!y)return o();n?o(!1,n):r=setTimeout(()=>{o(!1,1e-7)},1e3)}n===1&&!Kn(l,e.getBoundingClientRect())&&o(),y=!1}try{n=new IntersectionObserver(b,{...v,root:i.ownerDocument})}catch{n=new IntersectionObserver(b,v)}n.observe(e)}return o(!0),a}function Jn(e,t,n,r){r===void 0&&(r={});let{ancestorScroll:i=!0,ancestorResize:a=!0,elementResize:o=typeof ResizeObserver==`function`,layoutShift:s=typeof IntersectionObserver==`function`,animationFrame:c=!1}=r,l=yn(e),u=i||a?[...l?gn(l):[],...gn(t)]:[];u.forEach(e=>{i&&e.addEventListener(`scroll`,n,{passive:!0}),a&&e.addEventListener(`resize`,n)});let d=l&&s?qn(l,n):null,f=-1,p=null;o&&(p=new ResizeObserver(e=>{let[r]=e;r&&r.target===l&&p&&(p.unobserve(t),cancelAnimationFrame(f),f=requestAnimationFrame(()=>{var e;(e=p)==null||e.observe(t)})),n()}),l&&!c&&p.observe(l),p.observe(t));let m,h=c?wn(e):null;c&&g();function g(){let t=wn(e);h&&!Kn(h,t)&&n(),h=t,m=requestAnimationFrame(g)}return n(),()=>{var e;u.forEach(e=>{i&&e.removeEventListener(`scroll`,n),a&&e.removeEventListener(`resize`,n)}),d?.(),(e=p)==null||e.disconnect(),p=null,c&&cancelAnimationFrame(m)}}var Yn=Bt,Xn=Vt,Zn=Lt,Qn=Ht,$n=It,er=(e,t,n)=>{let r=new Map,i={platform:Gn,...n},a={...i.platform,_c:r};return Pt(e,t,{...i,platform:a})};function tr(e){return rr(e)}function nr(e){return e.assignedSlot?e.assignedSlot:e.parentNode instanceof ShadowRoot?e.parentNode.host:e.parentNode}function rr(e){for(let t=e;t;t=nr(t))if(t instanceof Element&&getComputedStyle(t).display===`none`)return null;for(let t=nr(e);t;t=nr(t)){if(!(t instanceof Element))continue;let e=getComputedStyle(t);if(e.display!==`contents`&&(e.position!==`static`||sn(e)||t.tagName===`BODY`))return t}return null}var ir=`:host {
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
`;function ar(e){return typeof e==`object`&&!!e&&`getBoundingClientRect`in e&&(`contextElement`in e?e instanceof Element:!0)}var or=globalThis?.HTMLElement?.prototype.hasOwnProperty(`popover`),N=class extends rt{constructor(){super(...arguments),this.localize=new Oe(this),this.active=!1,this.placement=`top`,this.boundary=`viewport`,this.distance=0,this.skidding=0,this.arrow=!1,this.arrowPlacement=`anchor`,this.arrowPadding=10,this.flip=!1,this.flipFallbackPlacements=``,this.flipFallbackStrategy=`best-fit`,this.flipPadding=0,this.shift=!1,this.shiftPadding=0,this.autoSizePadding=0,this.hoverBridge=!1,this.updateHoverBridge=()=>{if(this.hoverBridge&&this.anchorEl){let e=this.anchorEl.getBoundingClientRect(),t=this.popup.getBoundingClientRect(),n=this.placement.includes(`top`)||this.placement.includes(`bottom`),r=0,i=0,a=0,o=0,s=0,c=0,l=0,u=0;n?e.top<t.top?(r=e.left,i=e.bottom,a=e.right,o=e.bottom,s=t.left,c=t.top,l=t.right,u=t.top):(r=t.left,i=t.bottom,a=t.right,o=t.bottom,s=e.left,c=e.top,l=e.right,u=e.top):e.left<t.left?(r=e.right,i=e.top,a=t.left,o=t.top,s=e.right,c=e.bottom,l=t.left,u=t.bottom):(r=t.right,i=t.top,a=e.left,o=e.top,s=t.right,c=t.bottom,l=e.left,u=e.bottom),this.style.setProperty(`--hover-bridge-top-left-x`,`${r}px`),this.style.setProperty(`--hover-bridge-top-left-y`,`${i}px`),this.style.setProperty(`--hover-bridge-top-right-x`,`${a}px`),this.style.setProperty(`--hover-bridge-top-right-y`,`${o}px`),this.style.setProperty(`--hover-bridge-bottom-left-x`,`${s}px`),this.style.setProperty(`--hover-bridge-bottom-left-y`,`${c}px`),this.style.setProperty(`--hover-bridge-bottom-right-x`,`${l}px`),this.style.setProperty(`--hover-bridge-bottom-right-y`,`${u}px`)}}}async connectedCallback(){super.connectedCallback(),await this.updateComplete,this.start()}disconnectedCallback(){super.disconnectedCallback(),this.stop()}async updated(e){super.updated(e),e.has(`active`)&&(this.active?this.start():this.stop()),e.has(`anchor`)&&this.handleAnchorChange(),this.active&&(await this.updateComplete,this.reposition())}async handleAnchorChange(){await this.stop(),this.anchor&&typeof this.anchor==`string`?this.anchorEl=this.getRootNode().getElementById(this.anchor):this.anchor instanceof Element||ar(this.anchor)?this.anchorEl=this.anchor:this.anchorEl=this.querySelector(`[slot="anchor"]`),this.anchorEl instanceof HTMLSlotElement&&(this.anchorEl=this.anchorEl.assignedElements({flatten:!0})[0]),this.anchorEl&&this.start()}start(){!this.anchorEl||!this.active||(this.popup.showPopover?.(),this.cleanup=Jn(this.anchorEl,this.popup,()=>{this.reposition()}))}async stop(){return new Promise(e=>{this.popup.hidePopover?.(),this.cleanup?(this.cleanup(),this.cleanup=void 0,this.removeAttribute(`data-current-placement`),this.style.removeProperty(`--auto-size-available-width`),this.style.removeProperty(`--auto-size-available-height`),requestAnimationFrame(()=>e())):e()})}reposition(){if(!this.active||!this.anchorEl)return;let e=[Yn({mainAxis:this.distance,crossAxis:this.skidding})];this.sync?e.push(Qn({apply:({rects:e})=>{let t=this.sync===`width`||this.sync===`both`,n=this.sync===`height`||this.sync===`both`;this.popup.style.width=t?`${e.reference.width}px`:``,this.popup.style.height=n?`${e.reference.height}px`:``}})):(this.popup.style.width=``,this.popup.style.height=``);let t;or&&!ar(this.anchor)&&this.boundary===`scroll`&&(t=gn(this.anchorEl).filter(e=>e instanceof Element)),this.flip&&e.push(Zn({boundary:this.flipBoundary||t,fallbackPlacements:this.flipFallbackPlacements,fallbackStrategy:this.flipFallbackStrategy===`best-fit`?`bestFit`:`initialPlacement`,padding:this.flipPadding})),this.shift&&e.push(Xn({boundary:this.shiftBoundary||t,padding:this.shiftPadding})),this.autoSize?e.push(Qn({boundary:this.autoSizeBoundary||t,padding:this.autoSizePadding,apply:({availableWidth:e,availableHeight:t})=>{this.autoSize===`vertical`||this.autoSize===`both`?this.style.setProperty(`--auto-size-available-height`,`${t}px`):this.style.removeProperty(`--auto-size-available-height`),this.autoSize===`horizontal`||this.autoSize===`both`?this.style.setProperty(`--auto-size-available-width`,`${e}px`):this.style.removeProperty(`--auto-size-available-width`)}})):(this.style.removeProperty(`--auto-size-available-width`),this.style.removeProperty(`--auto-size-available-height`)),this.arrow&&e.push($n({element:this.arrowEl,padding:this.arrowPadding}));let n=or?e=>Gn.getOffsetParent(e,tr):Gn.getOffsetParent;er(this.anchorEl,this.popup,{placement:this.placement,middleware:e,strategy:or?`absolute`:`fixed`,platform:{...Gn,getOffsetParent:n}}).then(({x:e,y:t,middlewareData:n,placement:r})=>{let i=this.localize.dir()===`rtl`,a={top:`bottom`,right:`left`,bottom:`top`,left:`right`}[r.split(`-`)[0]];if(this.setAttribute(`data-current-placement`,r),Object.assign(this.popup.style,{left:`${e}px`,top:`${t}px`}),this.arrow){let e=n.arrow.x,t=n.arrow.y,r=``,o=``,s=``,c=``;if(this.arrowPlacement===`start`){let n=typeof e==`number`?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:``;r=typeof t==`number`?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:``,o=i?n:``,c=i?``:n}else if(this.arrowPlacement===`end`){let n=typeof e==`number`?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:``;o=i?``:n,c=i?n:``,s=typeof t==`number`?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:``}else this.arrowPlacement===`center`?(c=typeof e==`number`?`calc(50% - var(--arrow-size-diagonal))`:``,r=typeof t==`number`?`calc(50% - var(--arrow-size-diagonal))`:``):(c=typeof e==`number`?`${e}px`:``,r=typeof t==`number`?`${t}px`:``);Object.assign(this.arrowEl.style,{top:r,right:o,bottom:s,left:c,[a]:`calc(var(--arrow-size-diagonal) * -1)`})}}),requestAnimationFrame(()=>this.updateHoverBridge()),this.dispatchEvent(new et)}render(){return g`
      <slot name="anchor" @slotchange=${this.handleAnchorChange}></slot>

      <span
        part="hover-bridge"
        class=${A({"popup-hover-bridge":!0,"popup-hover-bridge-visible":this.hoverBridge&&this.active})}
      ></span>

      <div
        popover="manual"
        part="popup"
        class=${A({popup:!0,"popup-active":this.active,"popup-fixed":!or,"popup-has-arrow":this.arrow})}
      >
        <slot></slot>
        ${this.arrow?g`<div part="arrow" class="arrow" role="presentation"></div>`:``}
      </div>
    `}};N.css=ir,M([D(`.popup`)],N.prototype,`popup`,2),M([D(`.arrow`)],N.prototype,`arrowEl`,2),M([w()],N.prototype,`anchor`,2),M([w({type:Boolean,reflect:!0})],N.prototype,`active`,2),M([w({reflect:!0})],N.prototype,`placement`,2),M([w()],N.prototype,`boundary`,2),M([w({type:Number})],N.prototype,`distance`,2),M([w({type:Number})],N.prototype,`skidding`,2),M([w({type:Boolean})],N.prototype,`arrow`,2),M([w({attribute:`arrow-placement`})],N.prototype,`arrowPlacement`,2),M([w({attribute:`arrow-padding`,type:Number})],N.prototype,`arrowPadding`,2),M([w({type:Boolean})],N.prototype,`flip`,2),M([w({attribute:`flip-fallback-placements`,converter:{fromAttribute:e=>e.split(` `).map(e=>e.trim()).filter(e=>e!==``),toAttribute:e=>e.join(` `)}})],N.prototype,`flipFallbackPlacements`,2),M([w({attribute:`flip-fallback-strategy`})],N.prototype,`flipFallbackStrategy`,2),M([w({type:Object})],N.prototype,`flipBoundary`,2),M([w({attribute:`flip-padding`,type:Number})],N.prototype,`flipPadding`,2),M([w({type:Boolean})],N.prototype,`shift`,2),M([w({type:Object})],N.prototype,`shiftBoundary`,2),M([w({attribute:`shift-padding`,type:Number})],N.prototype,`shiftPadding`,2),M([w({attribute:`auto-size`})],N.prototype,`autoSize`,2),M([w()],N.prototype,`sync`,2),M([w({type:Object})],N.prototype,`autoSizeBoundary`,2),M([w({attribute:`auto-size-padding`,type:Number})],N.prototype,`autoSizePadding`,2),M([w({attribute:`hover-bridge`,type:Boolean})],N.prototype,`hoverBridge`,2),N=M([E(`wa-popup`)],N);var sr=class extends Event{constructor(){super(`wa-after-hide`,{bubbles:!0,cancelable:!1,composed:!0})}},cr=class extends Event{constructor(){super(`wa-after-show`,{bubbles:!0,cancelable:!1,composed:!0})}},lr=class extends Event{constructor(e){super(`wa-hide`,{bubbles:!0,cancelable:!0,composed:!0}),this.detail=e}},ur=class extends Event{constructor(){super(`wa-show`,{bubbles:!0,cancelable:!0,composed:!0})}},dr=`useandom-26T198340PX75pxJACKVERYMINDBUSHWOLF_GQZbfghjklqvwyzrict`,fr=(e=21)=>{let t=``,n=crypto.getRandomValues(new Uint8Array(e|=0));for(;e--;)t+=dr[n[e]&63];return t};function pr(e=``){return`${e}${fr()}`}function mr(e,t){return new Promise(n=>{function r(i){i.target===e&&(e.removeEventListener(t,r),n())}e.addEventListener(t,r)})}function hr(e,t){return new Promise(n=>{let r=new AbortController,{signal:i}=r;if(e.classList.contains(t))return;e.classList.remove(t),e.classList.add(t);let a=()=>{e.classList.remove(t),n(),r.abort()};e.addEventListener(`animationend`,a,{once:!0,signal:i}),e.addEventListener(`animationcancel`,a,{once:!0,signal:i})})}function gr(e,t){let n={waitUntilFirstUpdate:!1,...t};return(t,r)=>{let{update:i}=t,a=Array.isArray(e)?e:[e];t.update=function(e){a.forEach(t=>{let i=t;if(e.has(i)){let t=e.get(i),a=this[i];t!==a&&(!n.waitUntilFirstUpdate||this.hasUpdated)&&this[r](t,a)}}),i.call(this,e)}}}var _r=`:host {
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
`,vr=class extends rt{constructor(){super(...arguments),this.placement=`top`,this.disabled=!1,this.distance=8,this.open=!1,this.skidding=0,this.showDelay=150,this.hideDelay=0,this.trigger=`hover focus`,this.withoutArrow=!1,this.for=null,this.anchor=null,this.eventController=new AbortController,this.handleBlur=()=>{this.hasTrigger(`focus`)&&this.hide()},this.handleClick=()=>{this.hasTrigger(`click`)&&(this.open?this.hide():this.show())},this.handleFocus=()=>{this.hasTrigger(`focus`)&&this.show()},this.handleDocumentKeyDown=e=>{e.key===`Escape`&&(e.stopPropagation(),this.hide())},this.handleMouseOver=()=>{this.hasTrigger(`hover`)&&(clearTimeout(this.hoverTimeout),this.hoverTimeout=window.setTimeout(()=>this.show(),this.showDelay))},this.handleMouseOut=()=>{this.hasTrigger(`hover`)&&(clearTimeout(this.hoverTimeout),this.hoverTimeout=window.setTimeout(()=>this.hide(),this.hideDelay))}}connectedCallback(){super.connectedCallback(),this.eventController.signal.aborted&&(this.eventController=new AbortController),this.open&&(this.open=!1,this.updateComplete.then(()=>{this.open=!0})),this.id||=pr(`wa-tooltip-`),this.for&&this.anchor?(this.anchor=null,this.handleForChange()):this.for&&this.handleForChange()}disconnectedCallback(){super.disconnectedCallback(),document.removeEventListener(`keydown`,this.handleDocumentKeyDown),this.eventController.abort(),this.anchor&&this.removeFromAriaLabelledBy(this.anchor,this.id)}firstUpdated(){this.body.hidden=!this.open,this.open&&(this.popup.active=!0,this.popup.reposition())}hasTrigger(e){return this.trigger.split(` `).includes(e)}addToAriaLabelledBy(e,t){let n=(e.getAttribute(`aria-labelledby`)||``).split(/\s+/).filter(Boolean);n.includes(t)||(n.push(t),e.setAttribute(`aria-labelledby`,n.join(` `)))}removeFromAriaLabelledBy(e,t){let n=(e.getAttribute(`aria-labelledby`)||``).split(/\s+/).filter(Boolean).filter(e=>e!==t);n.length>0?e.setAttribute(`aria-labelledby`,n.join(` `)):e.removeAttribute(`aria-labelledby`)}async handleOpenChange(){if(this.open){if(this.disabled)return;let e=new ur;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}document.addEventListener(`keydown`,this.handleDocumentKeyDown,{signal:this.eventController.signal}),this.body.hidden=!1,this.popup.active=!0,await hr(this.popup.popup,`show-with-scale`),this.popup.reposition(),this.dispatchEvent(new cr)}else{let e=new lr;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}document.removeEventListener(`keydown`,this.handleDocumentKeyDown),await hr(this.popup.popup,`hide-with-scale`),this.popup.active=!1,this.body.hidden=!0,this.dispatchEvent(new sr)}}handleForChange(){let e=this.getRootNode();if(!e)return;let t=this.for?e.getElementById(this.for):null,n=this.anchor;if(t===n)return;let{signal:r}=this.eventController;t&&(this.addToAriaLabelledBy(t,this.id),t.addEventListener(`blur`,this.handleBlur,{capture:!0,signal:r}),t.addEventListener(`focus`,this.handleFocus,{capture:!0,signal:r}),t.addEventListener(`click`,this.handleClick,{signal:r}),t.addEventListener(`mouseover`,this.handleMouseOver,{signal:r}),t.addEventListener(`mouseout`,this.handleMouseOut,{signal:r})),n&&(this.removeFromAriaLabelledBy(n,this.id),n.removeEventListener(`blur`,this.handleBlur,{capture:!0}),n.removeEventListener(`focus`,this.handleFocus,{capture:!0}),n.removeEventListener(`click`,this.handleClick),n.removeEventListener(`mouseover`,this.handleMouseOver),n.removeEventListener(`mouseout`,this.handleMouseOut)),this.anchor=t}async handleOptionsChange(){this.hasUpdated&&(await this.updateComplete,this.popup.reposition())}handleDisabledChange(){this.disabled&&this.open&&this.hide()}async show(){if(!this.open)return this.open=!0,mr(this,`wa-after-show`)}async hide(){if(this.open)return this.open=!1,mr(this,`wa-after-hide`)}render(){return g`
      <wa-popup
        part="base"
        exportparts="
          popup:base__popup,
          arrow:base__arrow
        "
        class=${A({tooltip:!0,"tooltip-open":this.open})}
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
    `}};vr.css=_r,vr.dependencies={"wa-popup":N},M([D(`slot:not([name])`)],vr.prototype,`defaultSlot`,2),M([D(`.body`)],vr.prototype,`body`,2),M([D(`wa-popup`)],vr.prototype,`popup`,2),M([w()],vr.prototype,`placement`,2),M([w({type:Boolean,reflect:!0})],vr.prototype,`disabled`,2),M([w({type:Number})],vr.prototype,`distance`,2),M([w({type:Boolean,reflect:!0})],vr.prototype,`open`,2),M([w({type:Number})],vr.prototype,`skidding`,2),M([w({attribute:`show-delay`,type:Number})],vr.prototype,`showDelay`,2),M([w({attribute:`hide-delay`,type:Number})],vr.prototype,`hideDelay`,2),M([w()],vr.prototype,`trigger`,2),M([w({attribute:`without-arrow`,type:Boolean,reflect:!0})],vr.prototype,`withoutArrow`,2),M([w()],vr.prototype,`for`,2),M([T()],vr.prototype,`anchor`,2),M([gr(`open`,{waitUntilFirstUpdate:!0})],vr.prototype,`handleOpenChange`,1),M([gr(`for`)],vr.prototype,`handleForChange`,1),M([gr([`distance`,`placement`,`skidding`])],vr.prototype,`handleOptionsChange`,1),M([gr(`disabled`)],vr.prototype,`handleDisabledChange`,1),vr=M([E(`wa-tooltip`)],vr);var yr=class extends vr{static get styles(){return[vr.styles,v`
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
      `]}};customElements.get(`c-tooltip`)||customElements.define(`c-tooltip`,yr);var br=v`
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
`,xr=class extends C{constructor(...e){super(...e),this.isCopying=!1,this.value=``,this.disabled=!1}async copyValue(){if(!(this.isCopying||this.disabled)){this.isCopying=!0;try{await navigator.clipboard.writeText(this.value),this.dispatchEvent(new CustomEvent(`craft-copy`,{bubbles:!0,cancelable:!1,composed:!0,detail:{value:this.value}}))}catch{this.dispatchEvent(new CustomEvent(`craft-error`,{cancelable:!1,composed:!0,bubbles:!0}))}finally{this.isCopying=!1}}}render(){return g`
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
    `}};xr.styles=[br],m([T()],xr.prototype,`isCopying`,void 0),m([w({type:String})],xr.prototype,`value`,void 0),m([w({type:Boolean})],xr.prototype,`disabled`,void 0),customElements.get(`craft-copy-button`)||customElements.define(`craft-copy-button`,xr);var Sr=v`
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
`,Cr=v`
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
`,wr={"icon.in":{keyframes:[{scale:.25,opacity:.25},{scale:1,opacity:1}],options:{duration:100}},"icon.out":{keyframes:[{scale:1,opacity:1},{scale:.25,opacity:.25}],options:{duration:100}}},Tr=class extends C{constructor(){super(),this.status=`rest`,this.value=``,this.disabled=!1,this.feedbackDuration=1e3,this.tooltipLabel=`Copy`,this.addEventListener(`craft-copy`,()=>{this.showStatus(`success`)}),this.addEventListener(`craft-error`,()=>{this.showStatus(`error`)})}getId(){return`attribute-${this.value.replace(/([a-z])([A-Z])/g,`$1-$2`).replace(/[\s_]+/g,`-`).toLowerCase()}`}async showStatus(e){let t=e===`success`?this.successIconEl:this.errorIconEl;this.tooltipLabel=e===`success`?`Copied`:`Copy failed`,await t.animate(wr[`icon.out`].keyframes,wr[`icon.out`].options),this.copyIconEl.hidden=!0,t.hidden=!1,await t.animate(wr[`icon.in`].keyframes,wr[`icon.in`].options),this.status=e,setTimeout(async()=>{await t.animate(wr[`icon.out`].keyframes,wr[`icon.out`].options),t.hidden=!0,this.copyIconEl.hidden=!1,await this.copyIconEl.animate(wr[`icon.in`].keyframes,wr[`icon.in`].options),this.status=`rest`,this.tooltipLabel=`Copy`},this.feedbackDuration)}render(){return g`
      <c-tooltip for="${this.getId()}">${this.tooltipLabel}</c-tooltip>
      <craft-copy-button
        value="${this.value}"
        id="${this.getId()}"
        class=${A({"copy-attribute":!0,"copy-attribute--success":this.status===`success`,"copy-attribute--error":this.status===`error`})}
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
    `}};Tr.styles=[Sr,Cr],m([T()],Tr.prototype,`status`,void 0),m([D(`slot[name="copy-icon"]`)],Tr.prototype,`copyIconEl`,void 0),m([D(`slot[name="success-icon"]`)],Tr.prototype,`successIconEl`,void 0),m([D(`slot[name="error-icon"]`)],Tr.prototype,`errorIconEl`,void 0),m([D(`craft-copy-button`)],Tr.prototype,`copyButtonEl`,void 0),m([w({type:String})],Tr.prototype,`value`,void 0),m([w({type:Boolean,reflect:!0})],Tr.prototype,`disabled`,void 0),m([w({attribute:`feedback-duration`,type:Number})],Tr.prototype,`feedbackDuration`,void 0),m([w({reflect:!1})],Tr.prototype,`tooltipLabel`,void 0),customElements.get(`craft-copy-attribute`)||customElements.define(`craft-copy-attribute`,Tr);var Er=new WeakMap;function Dr(e,t){let n=t;for(;n;){if(Er.get(n)===e)return!0;n=Object.getPrototypeOf(n)}return!1}function Or(e){return t=>{if(Dr(e,t))return t;let n=e(t);return Er.set(n,e),n}}var kr=Or(e=>class extends e{static get properties(){return{disabled:{type:Boolean,reflect:!0}}}constructor(){super(),this._requestedToBeDisabled=!1,this.__isUserSettingDisabled=!0,this.__restoreDisabledTo=!1,this.disabled=!1}makeRequestToBeDisabled(){this._requestedToBeDisabled===!1&&(this._requestedToBeDisabled=!0,this.__restoreDisabledTo=this.disabled,this.__internalSetDisabled(!0))}retractRequestToBeDisabled(){this._requestedToBeDisabled===!0&&(this._requestedToBeDisabled=!1,this.__internalSetDisabled(this.__restoreDisabledTo))}__internalSetDisabled(e){this.__isUserSettingDisabled=!1,this.disabled=e,this.__isUserSettingDisabled=!0}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`disabled`&&(this.__isUserSettingDisabled&&(this.__restoreDisabledTo=this.disabled),this.disabled===!1&&this._requestedToBeDisabled===!0&&this.__internalSetDisabled(!0))}click(){this.disabled||super.click()}}),Ar=Or(e=>class extends kr(e){static get properties(){return{tabIndex:{type:Number,reflect:!0,attribute:`tabindex`}}}constructor(){super(),this.__isUserSettingTabIndex=!0,this.__restoreTabIndexTo=0,this.__internalSetTabIndex(0)}makeRequestToBeDisabled(){super.makeRequestToBeDisabled(),this._requestedToBeDisabled===!1&&this.tabIndex!=null&&(this.__restoreTabIndexTo=this.tabIndex)}retractRequestToBeDisabled(){super.retractRequestToBeDisabled(),this._requestedToBeDisabled===!0&&this.__internalSetTabIndex(this.__restoreTabIndexTo)}static enabledWarnings=super.enabledWarnings?.filter(e=>e!==`change-in-update`)||[];__internalSetTabIndex(e){this.__isUserSettingTabIndex=!1,this.tabIndex=e,this.__isUserSettingTabIndex=!0}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`disabled`&&(this.disabled?this.__internalSetTabIndex(-1):this.__internalSetTabIndex(this.__restoreTabIndexTo)),e===`tabIndex`&&(this.__isUserSettingTabIndex&&this.tabIndex!=null&&(this.__restoreTabIndexTo=this.tabIndex),this.tabIndex!==-1&&this._requestedToBeDisabled===!0&&this.__internalSetTabIndex(-1))}firstUpdated(e){super.firstUpdated(e),this.disabled&&this.__internalSetTabIndex(-1)}}),{I:jr}=h,Mr=e=>e===null||typeof e!=`object`&&typeof e!=`function`,Nr=(e,t)=>t===void 0?e?._$litType$!==void 0:e?._$litType$===t,Pr=e=>e.strings===void 0,Fr=()=>document.createComment(``),Ir=(e,t,n)=>{let r=e._$AA.parentNode,i=t===void 0?e._$AB:t._$AA;if(n===void 0)n=new jr(r.insertBefore(Fr(),i),r.insertBefore(Fr(),i),e,e.options);else{let t=n._$AB.nextSibling,a=n._$AM,o=a!==e;if(o){let t;n._$AQ?.(e),n._$AM=e,n._$AP!==void 0&&(t=e._$AU)!==a._$AU&&n._$AP(t)}if(t!==i||o){let e=n._$AA;for(;e!==t;){let t=e.nextSibling;r.insertBefore(e,i),e=t}}}return n},Lr=(e,t,n=e)=>(e._$AI(t,n),e),Rr={},zr=(e,t=Rr)=>e._$AH=t,Br=e=>e._$AH,Vr=e=>{e._$AR(),e._$AA.remove()};function Hr(e){return e instanceof Node?`node`:Nr(e)?`template-result`:!Array.isArray(e)&&typeof e==`object`&&`template`in e?`slot-rerender-object`:null}var Ur=Or(e=>class extends e{get slots(){return{}}constructor(){super(),this.__renderMetaPerSlot=new Map,this.__slotsThatNeedRerender=new Set,this.__slotsProvidedByUserOnFirstConnected=new Set,this.__privateSlots=new Set}connectedCallback(){super.connectedCallback(),this._connectSlotMixin()}__rerenderSlot(e){let t=this.slots[e]();this.__renderTemplateInScopedContext({renderAsDirectHostChild:t.renderAsDirectHostChild,template:t.template,slotName:e}),t.afterRender?.()}update(e){super.update(e);for(let e of this.__slotsThatNeedRerender)this.__rerenderSlot(e)}__renderTemplateInScopedContext({template:e,slotName:t,renderAsDirectHostChild:n}){if(!this.__renderMetaPerSlot.has(t)){let r=!!ShadowRoot.prototype.createElement;this.shadowRoot||console.error(`[SlotMixin] No shadowRoot was found`);let i=(r?this.shadowRoot:document).createElement(`div`),a=document.createComment(`_start_slot_${t}_`),o=document.createComment(`_end_slot_${t}_`);i.appendChild(a),i.appendChild(o);let{creationScope:s,host:c}=this.renderOptions;if(b(e,i,{renderBefore:o,creationScope:s,host:c}),n){let e=Array.from(i.childNodes);this.__appendNodes({nodes:e,renderParent:this,slotName:t})}else i.slot=t,this.appendChild(i);this.__renderMetaPerSlot.set(t,{renderTargetThatRespectsShadowRootScoping:i,renderBefore:o});return}let{renderBefore:r,renderTargetThatRespectsShadowRootScoping:i}=this.__renderMetaPerSlot.get(t),a=n?this:i,{creationScope:o,host:s}=this.renderOptions;b(e,a,{creationScope:o,host:s,renderBefore:r}),n&&r.previousElementSibling&&!r.previousElementSibling.slot&&(r.previousElementSibling.slot=t)}__appendNodes({nodes:e,renderParent:t=this,slotName:n}){for(let r of e)r instanceof Element&&n&&n!==``&&r.setAttribute(`slot`,n),t.appendChild(r)}__initSlots(e){for(let t of e){if(this.__slotsProvidedByUserOnFirstConnected.has(t))continue;let e=this.slots[t]();if(e!==void 0)switch(this.__isConnectedSlotMixin||this.__privateSlots.add(t),Hr(e)){case`template-result`:this.__renderTemplateInScopedContext({template:e,renderAsDirectHostChild:!0,slotName:t});break;case`node`:this.__appendNodes({nodes:[e],renderParent:this,slotName:t});break;case`slot-rerender-object`:this.__slotsThatNeedRerender.add(t),e.firstRenderOnConnected&&this.__rerenderSlot(t);break;default:throw Error(`Slot "${t}" configured inside "get slots()" (in prototype) of ${this.constructor.name} may return these types: TemplateResult | Node | {template:TemplateResult, afterRender?:function} | undefined.
              You provided: ${e}`)}}}_connectSlotMixin(){if(this.__isConnectedSlotMixin)return;let e=Object.keys(this.slots);for(let t of e)(t===``?Array.from(this.children).find(e=>!e.hasAttribute(`slot`)):Array.from(this.children).find(e=>e.slot===t))&&this.__slotsProvidedByUserOnFirstConnected.add(t);this.__initSlots(e),this.__isConnectedSlotMixin=!0}_isPrivateSlot(e){return this.__privateSlots.has(e)}});function Wr(e=`google-chrome`){let t=globalThis.navigator,n=!!t.userAgentData&&t.userAgentData.brands.some(e=>e.brand===`Chromium`);if(e===`chromium`)return n;let r=globalThis.navigator?.vendor,i=globalThis.opr!==void 0,a=globalThis.userAgent?.indexOf(`Edge`)>-1,o=globalThis.userAgent?.match(`CriOS`);if(e===`ios`)return o;if(e===`google-chrome`)return n!=null&&r===`Google Inc.`&&i===!1&&a===!1}var Gr={isIE11:/Trident/.test(globalThis.navigator?.userAgent),isChrome:Wr(),isIOSChrome:Wr(`ios`),isChromium:Wr(`chromium`),isFirefox:globalThis.navigator?.userAgent.toLowerCase().indexOf(`firefox`)>-1,isMac:globalThis.navigator?.appVersion?.indexOf(`Mac`)!==-1,isIOS:/iPhone|iPad|iPod/i.test(globalThis.navigator?.userAgent),isMacSafari:globalThis.navigator?.vendor&&globalThis.navigator?.vendor.indexOf(`Apple`)>-1&&globalThis.navigator?.userAgent&&globalThis.navigator?.userAgent.indexOf(`CriOS`)===-1&&globalThis.navigator?.userAgent.indexOf(`FxiOS`)===-1&&globalThis.navigator?.appVersion.indexOf(`Mac`)!==-1};function Kr(e=``){return`${e.length>0?`${e}-`:``}${Math.random().toString(36).substr(2,10)}`}var qr=e=>e.key===` `||e.key===`Enter`,Jr=e=>e.key===` `,Yr=class extends Ar(C){static get properties(){return{active:{type:Boolean,reflect:!0},type:{type:String,reflect:!0}}}render(){return g` <div class="button-content"><slot></slot></div> `}static get styles(){return[v`
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
  `,e}var $r=class extends Xr{get _nativeButtonNode(){return Zr.get(this._form)?.helper||null}constructor(){super(),this.type=`submit`,this.__implicitSubmitHelperButton=null}_setupSubmitAndResetHelperOnConnected(){if(super._setupSubmitAndResetHelperOnConnected(),!this._form||this.type!==`submit`)return;let e=this._form;if(!Zr.get(this._form)){let t=Qr(),n=document.createElement(`div`);n.appendChild(t),Zr.set(this._form,{lionButtons:new Set,helper:t,observer:new MutationObserver(()=>{e.appendChild(n)})}),e.appendChild(n),Zr.get(e)?.observer.observe(n,{childList:!0})}Zr.get(e)?.lionButtons.add(this)}_teardownSubmitAndResetHelperOnDisconnected(){if(super._teardownSubmitAndResetHelperOnDisconnected(),this._form){let e=Zr.get(this._form);e&&(e.lionButtons.delete(this),e.lionButtons.size||(this._form.contains(e.helper)&&e.helper.remove(),Zr.get(this._form)?.observer.disconnect(),Zr.delete(this._form)))}}},ei=v`
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
`,ti=Object.prototype.toString;function ni(e){return typeof e==`function`||ti.call(e)===`[object Function]`}function ri(e){var t=Number(e);return isNaN(t)?0:t===0||!isFinite(t)?t:(t>0?1:-1)*Math.floor(Math.abs(t))}var ii=2**53-1;function ai(e){var t=ri(e);return Math.min(Math.max(t,0),ii)}function oi(e,t){var n=Array,r=Object(e);if(e==null)throw TypeError(`Array.from requires an array-like object - not null or undefined`);if(t!==void 0&&!ni(t))throw TypeError(`Array.from: when provided, the second argument must be a function`);for(var i=ai(r.length),a=ni(n)?Object(new n(i)):Array(i),o=0,s;o<i;)s=r[o],t?a[o]=t(s,o):a[o]=s,o+=1;return a.length=i,a}function si(e){"@babel/helpers - typeof";return si=typeof Symbol==`function`&&typeof Symbol.iterator==`symbol`?function(e){return typeof e}:function(e){return e&&typeof Symbol==`function`&&e.constructor===Symbol&&e!==Symbol.prototype?`symbol`:typeof e},si(e)}function ci(e,t){if(!(e instanceof t))throw TypeError(`Cannot call a class as a function`)}function li(e,t){for(var n=0;n<t.length;n++){var r=t[n];r.enumerable=r.enumerable||!1,r.configurable=!0,`value`in r&&(r.writable=!0),Object.defineProperty(e,fi(r.key),r)}}function ui(e,t,n){return t&&li(e.prototype,t),n&&li(e,n),Object.defineProperty(e,`prototype`,{writable:!1}),e}function di(e,t,n){return t=fi(t),t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function fi(e){var t=pi(e,`string`);return si(t)==`symbol`?t:t+``}function pi(e,t){if(si(e)!=`object`||!e)return e;var n=e[Symbol.toPrimitive];if(n!==void 0){var r=n.call(e,t||`default`);if(si(r)!=`object`)return r;throw TypeError(`@@toPrimitive must return a primitive value.`)}return(t===`string`?String:Number)(e)}var mi=function(){function e(){var t=arguments.length>0&&arguments[0]!==void 0?arguments[0]:[];ci(this,e),di(this,`items`,void 0),this.items=t}return ui(e,[{key:`add`,value:function(e){return this.has(e)===!1&&this.items.push(e),this}},{key:`clear`,value:function(){this.items=[]}},{key:`delete`,value:function(e){var t=this.items.length;return this.items=this.items.filter(function(t){return t!==e}),t!==this.items.length}},{key:`forEach`,value:function(e){var t=this;this.items.forEach(function(n){e(n,n,t)})}},{key:`has`,value:function(e){return this.items.indexOf(e)!==-1}},{key:`size`,get:function(){return this.items.length}}])}(),hi=typeof Set>`u`?Set:mi;function gi(e){return e.localName??e.tagName.toLowerCase()}var _i={article:`article`,aside:`complementary`,button:`button`,datalist:`listbox`,dd:`definition`,details:`group`,dialog:`dialog`,dt:`term`,fieldset:`group`,figure:`figure`,form:`form`,footer:`contentinfo`,h1:`heading`,h2:`heading`,h3:`heading`,h4:`heading`,h5:`heading`,h6:`heading`,header:`banner`,hr:`separator`,html:`document`,legend:`legend`,li:`listitem`,math:`math`,main:`main`,menu:`list`,nav:`navigation`,ol:`list`,optgroup:`group`,option:`option`,output:`status`,progress:`progressbar`,section:`region`,summary:`button`,table:`table`,tbody:`rowgroup`,textarea:`textbox`,tfoot:`rowgroup`,td:`cell`,th:`columnheader`,thead:`rowgroup`,tr:`row`,ul:`list`},vi={caption:new Set([`aria-label`,`aria-labelledby`]),code:new Set([`aria-label`,`aria-labelledby`]),deletion:new Set([`aria-label`,`aria-labelledby`]),emphasis:new Set([`aria-label`,`aria-labelledby`]),generic:new Set([`aria-label`,`aria-labelledby`,`aria-roledescription`]),insertion:new Set([`aria-label`,`aria-labelledby`]),none:new Set([`aria-label`,`aria-labelledby`]),paragraph:new Set([`aria-label`,`aria-labelledby`]),presentation:new Set([`aria-label`,`aria-labelledby`]),strong:new Set([`aria-label`,`aria-labelledby`]),subscript:new Set([`aria-label`,`aria-labelledby`]),superscript:new Set([`aria-label`,`aria-labelledby`])};function yi(e,t){return[`aria-atomic`,`aria-busy`,`aria-controls`,`aria-current`,`aria-description`,`aria-describedby`,`aria-details`,`aria-dropeffect`,`aria-flowto`,`aria-grabbed`,`aria-hidden`,`aria-keyshortcuts`,`aria-label`,`aria-labelledby`,`aria-live`,`aria-owns`,`aria-relevant`,`aria-roledescription`].some(function(n){var r;return e.hasAttribute(n)&&!((r=vi[t])!=null&&r.has(n))})}function bi(e,t){return yi(e,t)}function xi(e){var t=Ci(e);if(t===null||wi.indexOf(t)!==-1){var n=Si(e);if(wi.indexOf(t||``)===-1||bi(e,n||``))return n}return t}function Si(e){var t=_i[gi(e)];if(t!==void 0)return t;switch(gi(e)){case`a`:case`area`:case`link`:if(e.hasAttribute(`href`))return`link`;break;case`img`:return e.getAttribute(`alt`)===``&&!bi(e,`img`)?`presentation`:`img`;case`input`:var n=e.type;switch(n){case`button`:case`image`:case`reset`:case`submit`:return`button`;case`checkbox`:case`radio`:return n;case`range`:return`slider`;case`email`:case`tel`:case`text`:case`url`:return e.hasAttribute(`list`)?`combobox`:`textbox`;case`search`:return e.hasAttribute(`list`)?`combobox`:`searchbox`;case`number`:return`spinbutton`;default:return null}case`select`:return e.hasAttribute(`multiple`)||e.size>1?`listbox`:`combobox`}return null}function Ci(e){var t=e.getAttribute(`role`);if(t!==null){var n=t.trim().split(` `)[0];if(n.length>0)return n}return null}var wi=[`presentation`,`none`];function Ti(e){return e!==null&&e.nodeType===e.ELEMENT_NODE}function Ei(e){return Ti(e)&&gi(e)===`caption`}function Di(e){return Ti(e)&&gi(e)===`input`}function Oi(e){return Ti(e)&&gi(e)===`optgroup`}function ki(e){return Ti(e)&&gi(e)===`select`}function Ai(e){return Ti(e)&&gi(e)===`table`}function ji(e){return Ti(e)&&gi(e)===`textarea`}function Mi(e){var t=(e.ownerDocument===null?e:e.ownerDocument).defaultView;if(t===null)throw TypeError(`no window available`);return t}function Ni(e){return Ti(e)&&gi(e)===`fieldset`}function Pi(e){return Ti(e)&&gi(e)===`legend`}function Fi(e){return Ti(e)&&gi(e)===`slot`}function Ii(e){return Ti(e)&&e.ownerSVGElement!==void 0}function Li(e){return Ti(e)&&gi(e)===`svg`}function Ri(e){return Ii(e)&&gi(e)===`title`}function zi(e,t){if(Ti(e)&&e.hasAttribute(t)){var n=e.getAttribute(t).split(` `),r=e.getRootNode?e.getRootNode():e.ownerDocument;return n.map(function(e){return r.getElementById(e)}).filter(function(e){return e!==null})}return[]}function Bi(e,t){return Ti(e)?t.indexOf(xi(e))!==-1:!1}function Vi(e){return e.trim().replace(/\s\s+/g,` `)}function Hi(e,t){if(!Ti(e))return!1;if(e.hasAttribute(`hidden`)||e.getAttribute(`aria-hidden`)===`true`)return!0;var n=t(e);return n.getPropertyValue(`display`)===`none`||n.getPropertyValue(`visibility`)===`hidden`}function Ui(e){return Bi(e,[`button`,`combobox`,`listbox`,`textbox`])||Wi(e,`range`)}function Wi(e,t){if(!Ti(e))return!1;switch(t){case`range`:return Bi(e,[`meter`,`progressbar`,`scrollbar`,`slider`,`spinbutton`]);default:throw TypeError(`No knowledge about abstract role '${t}'. This is likely a bug :(`)}}function Gi(e,t){var n=oi(e.querySelectorAll(t));return zi(e,`aria-owns`).forEach(function(e){n.push.apply(n,oi(e.querySelectorAll(t)))}),n}function Ki(e){return ki(e)?e.selectedOptions||Gi(e,`[selected]`):Gi(e,`[aria-selected="true"]`)}function qi(e){return Bi(e,wi)}function Ji(e){return Ei(e)}function Yi(e){return Bi(e,[`button`,`cell`,`checkbox`,`columnheader`,`gridcell`,`heading`,`label`,`legend`,`link`,`menuitem`,`menuitemcheckbox`,`menuitemradio`,`option`,`radio`,`row`,`rowheader`,`switch`,`tab`,`tooltip`,`treeitem`])}function Xi(e){return!1}function Zi(e){return Di(e)||ji(e)?e.value:e.textContent||``}function Qi(e){var t=e.getPropertyValue(`content`);return/^["'].*["']$/.test(t)?t.slice(1,-1):``}function $i(e){var t=gi(e);return t===`button`||t===`input`&&e.getAttribute(`type`)!==`hidden`||t===`meter`||t===`output`||t===`progress`||t===`select`||t===`textarea`}function ea(e){if($i(e))return e;var t=null;return e.childNodes.forEach(function(e){if(t===null&&Ti(e)){var n=ea(e);n!==null&&(t=n)}}),t}function ta(e){if(e.control!==void 0)return e.control;var t=e.getAttribute(`for`);return t===null?ea(e):e.ownerDocument.getElementById(t)}function na(e){var t=e.labels;if(t===null)return t;if(t!==void 0)return oi(t);if(!$i(e))return null;var n=e.ownerDocument;return oi(n.querySelectorAll(`label`)).filter(function(t){return ta(t)===e})}function ra(e){var t=e.assignedNodes();return t.length===0?oi(e.childNodes):t}function ia(e){var t=arguments.length>1&&arguments[1]!==void 0?arguments[1]:{},n=new hi,r=typeof Map>`u`?void 0:new Map,i=Mi(e),a=t.compute,o=a===void 0?`name`:a,s=t.computedStyleSupportsPseudoElements,c=s===void 0?t.getComputedStyle!==void 0:s,l=t.getComputedStyle,u=l===void 0?i.getComputedStyle.bind(i):l,d=t.hidden,f=d===void 0?!1:d,p=function(e,t){if(t!==void 0)throw Error(`use uncachedGetComputedStyle directly for pseudo elements`);if(r===void 0)return u(e);var n=r.get(e);if(n)return n;var i=u(e,t);return r.set(e,i),i};function m(e,t){var n=``;if(Ti(e)&&c&&(n=`${Qi(u(e,`::before`))} ${n}`),(Fi(e)?ra(e):oi(e.childNodes).concat(zi(e,`aria-owns`))).forEach(function(e){var r=v(e,{isEmbeddedInLabel:t.isEmbeddedInLabel,isReferenced:!1,recursion:!0}),i=(Ti(e)?p(e).getPropertyValue(`display`):`inline`)===`inline`?``:` `;n+=`${i}${r}${i}`}),Ti(e)&&c){var r=Qi(u(e,`::after`));n=`${n} ${r}`}return n.trim()}function h(e,t){var r=e.getAttributeNode(t);return r!==null&&!n.has(r)&&r.value.trim()!==``?(n.add(r),r.value):null}function g(e){return Ti(e)?h(e,`title`):null}function _(e){if(!Ti(e))return null;if(Ni(e)){n.add(e);for(var t=oi(e.childNodes),r=0;r<t.length;r+=1){var i=t[r];if(Pi(i))return v(i,{isEmbeddedInLabel:!1,isReferenced:!1,recursion:!1})}}else if(Ai(e)){n.add(e);for(var a=oi(e.childNodes),o=0;o<a.length;o+=1){var s=a[o];if(Ei(s))return v(s,{isEmbeddedInLabel:!1,isReferenced:!1,recursion:!1})}}else if(Li(e)){n.add(e);for(var c=oi(e.childNodes),l=0;l<c.length;l+=1){var u=c[l];if(Ri(u))return u.textContent}return null}else if(gi(e)===`img`||gi(e)===`area`){var d=h(e,`alt`);if(d!==null)return d}else if(Oi(e)){var f=h(e,`label`);if(f!==null)return f}if(Di(e)&&(e.type===`button`||e.type===`submit`||e.type===`reset`)){var p=h(e,`value`);if(p!==null)return p;if(e.type===`submit`)return`Submit`;if(e.type===`reset`)return`Reset`}var g=na(e);if(g!==null&&g.length!==0)return n.add(e),oi(g).map(function(e){return v(e,{isEmbeddedInLabel:!0,isReferenced:!1,recursion:!0})}).filter(function(e){return e.length>0}).join(` `);if(Di(e)&&e.type===`image`){var _=h(e,`alt`);if(_!==null)return _;var y=h(e,`title`);return y===null?`Submit Query`:y}if(Bi(e,[`button`])){var b=m(e,{isEmbeddedInLabel:!1,isReferenced:!1});if(b!==``)return b}return null}function v(e,t){if(n.has(e))return``;if(!f&&Hi(e,p)&&!t.isReferenced)return n.add(e),``;var r=Ti(e)?e.getAttributeNode(`aria-labelledby`):null,i=r!==null&&!n.has(r)?zi(e,`aria-labelledby`):[];if(o===`name`&&!t.isReferenced&&i.length>0)return n.add(r),i.map(function(e){return v(e,{isEmbeddedInLabel:t.isEmbeddedInLabel,isReferenced:!0,recursion:!1})}).join(` `);var a=t.recursion&&Ui(e)&&o===`name`;if(!a){var s=(Ti(e)&&e.getAttribute(`aria-label`)||``).trim();if(s!==``&&o===`name`)return n.add(e),s;if(!qi(e)){var c=_(e);if(c!==null)return n.add(e),c}}if(Bi(e,[`menu`]))return n.add(e),``;if(a||t.isEmbeddedInLabel||t.isReferenced){if(Bi(e,[`combobox`,`listbox`])){n.add(e);var l=Ki(e);return l.length===0?Di(e)?e.value:``:oi(l).map(function(e){return v(e,{isEmbeddedInLabel:t.isEmbeddedInLabel,isReferenced:!1,recursion:!0})}).join(` `)}if(Wi(e,`range`))return n.add(e),e.hasAttribute(`aria-valuetext`)?e.getAttribute(`aria-valuetext`):e.hasAttribute(`aria-valuenow`)?e.getAttribute(`aria-valuenow`):e.getAttribute(`value`)||``;if(Bi(e,[`textbox`]))return n.add(e),Zi(e)}if(Yi(e)||Ti(e)&&t.isReferenced||Ji(e)||Xi(e)){var u=m(e,{isEmbeddedInLabel:t.isEmbeddedInLabel,isReferenced:!1});if(u!==``)return n.add(e),u}if(e.nodeType===e.TEXT_NODE)return n.add(e),e.textContent||``;if(t.recursion)return n.add(e),m(e,{isEmbeddedInLabel:t.isEmbeddedInLabel,isReferenced:!1});var d=g(e);return d===null?(n.add(e),``):(n.add(e),d)}return Vi(v(e,{isEmbeddedInLabel:!1,isReferenced:o===`description`,recursion:!1}))}function aa(e){return Bi(e,[`caption`,`code`,`deletion`,`emphasis`,`generic`,`insertion`,`none`,`paragraph`,`presentation`,`strong`,`subscript`,`superscript`])}function oa(e){var t=arguments.length>1&&arguments[1]!==void 0?arguments[1]:{};return aa(e)?``:ia(e,t)}var sa=class extends $r{constructor(...e){super(...e),this.appearance=`accent`,this.variant=`default`,this.size=`medium`,this.loading=!1,this.align=`center`,this._hasAccessibilityError=!1}static get styles(){return[...super.styles,ei]}async firstUpdated(e){super.firstUpdated(e),await this.updateComplete;let t=this.querySelectorAll(`craft-icon, craft-spinner`);await Promise.all(Array.from(t).map(e=>e.updateComplete)),this.accessibleName||=oa(this),this._hasAccessibilityError=!this.accessibleName||this.accessibleName.trim()===``}render(){return g`
      <div
        class="${A({"button-content":!0,"button-content--start":this.align===`start`,"button-content--end":this.align===`end`,"a11y-error":this._hasAccessibilityError})}"
        part="content"
      >
        <slot name="prefix" class="prefix" part="prefix"></slot>
        <slot class="label" part="label"></slot>
        <slot name="suffix" class="suffix" part="suffix"></slot>
      </div>
      ${this.loading?g`<craft-spinner part="spinner"></craft-spinner>`:S}
    `}};m([w()],sa.prototype,`accessibleName`,void 0),m([w({reflect:!0})],sa.prototype,`appearance`,void 0),m([w({reflect:!0})],sa.prototype,`variant`,void 0),m([w({reflect:!0})],sa.prototype,`size`,void 0),m([w({reflect:!0,type:Boolean})],sa.prototype,`loading`,void 0),m([w()],sa.prototype,`align`,void 0),m([T()],sa.prototype,`_hasAccessibilityError`,void 0),customElements.get(`craft-button`)||customElements.define(`craft-button`,sa);var ca=class extends Event{constructor(){super(`wa-load`,{bubbles:!0,cancelable:!1,composed:!0})}},la=class extends Event{constructor(){super(`wa-error`,{bubbles:!0,cancelable:!1,composed:!0})}},ua=`:host {
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
`,da=Symbol(),fa=Symbol(),pa,ma=new Map,ha=class extends rt{constructor(){super(...arguments),this.svg=null,this.autoWidth=!1,this.swapOpacity=!1,this.label=``,this.library=`default`,this.resolveIcon=async(e,t)=>{let n;if(t?.spriteSheet){this.hasUpdated||await this.updateComplete,this.svg=g`<svg part="svg">
        <use part="use" href="${e}"></use>
      </svg>`,await this.updateComplete;let n=this.shadowRoot.querySelector(`[part='svg']`);return typeof t.mutator==`function`&&t.mutator(n,this),this.svg}try{if(n=await fetch(e,{mode:`cors`}),!n.ok)return n.status===410?da:fa}catch{return fa}try{let e=document.createElement(`div`);e.innerHTML=await n.text();let t=e.firstElementChild;if(t?.tagName?.toLowerCase()!==`svg`)return da;pa||=new DOMParser;let r=pa.parseFromString(t.outerHTML,`text/html`).body.querySelector(`svg`);return r?(r.part.add(`svg`),document.adoptNode(r)):da}catch{return da}}}connectedCallback(){super.connectedCallback(),Fe(this)}firstUpdated(e){super.firstUpdated(e),this.setIcon()}disconnectedCallback(){super.disconnectedCallback(),Ie(this)}getIconSource(){let e=Le(this.library),t=this.family||Be();return this.name&&e?{url:e.resolver(this.name,t,this.variant,this.autoWidth),fromLibrary:!0}:{url:this.src,fromLibrary:!1}}handleLabelChange(){typeof this.label==`string`&&this.label.length>0?(this.setAttribute(`role`,`img`),this.setAttribute(`aria-label`,this.label),this.removeAttribute(`aria-hidden`)):(this.removeAttribute(`role`),this.removeAttribute(`aria-label`),this.setAttribute(`aria-hidden`,`true`))}async setIcon(){let{url:e,fromLibrary:t}=this.getIconSource(),n=t?Le(this.library):void 0;if(!e){this.svg=null;return}let r=ma.get(e);r||(r=this.resolveIcon(e,n),ma.set(e,r));let i=await r;if(i===fa&&ma.delete(e),e===this.getIconSource().url){if(Nr(i)){this.svg=i;return}switch(i){case fa:case da:this.svg=null,this.dispatchEvent(new la);break;default:this.svg=i.cloneNode(!0),n?.mutator?.(this.svg,this),this.dispatchEvent(new ca)}}}updated(e){super.updated(e);let t=Le(this.library),n=this.shadowRoot?.querySelector(`svg`);n&&t?.mutator?.(n,this)}render(){return this.hasUpdated?this.svg:g`<svg part="svg" fill="currentColor" width="16" height="16"></svg>`}};ha.css=ua,M([T()],ha.prototype,`svg`,2),M([w({reflect:!0})],ha.prototype,`name`,2),M([w({reflect:!0})],ha.prototype,`family`,2),M([w({reflect:!0})],ha.prototype,`variant`,2),M([w({attribute:`auto-width`,type:Boolean,reflect:!0})],ha.prototype,`autoWidth`,2),M([w({attribute:`swap-opacity`,type:Boolean,reflect:!0})],ha.prototype,`swapOpacity`,2),M([w()],ha.prototype,`src`,2),M([w()],ha.prototype,`label`,2),M([w({reflect:!0})],ha.prototype,`library`,2),M([gr(`label`)],ha.prototype,`handleLabelChange`,1),M([gr([`family`,`name`,`library`,`variant`,`src`,`autoWidth`,`swapOpacity`])],ha.prototype,`setIcon`,1),ha=M([E(`wa-icon`)],ha);var ga=class extends ha{static get styles(){return[ha.styles,v`
        :host {
          font-size: 0.8em;
        }
      `]}};customElements.get(`craft-icon`)||customElements.define(`craft-icon`,ga);var _a=v`
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
`,va=class extends C{constructor(...e){super(...e),this.label=null,this._gradientId=null}connectedCallback(){super.connectedCallback(),this._gradientId=`avatar-gradient-${Math.random().toString(36).slice(2,8)}`}text(){return this.label?this.label.split(` `).map(e=>e.charAt(0).toUpperCase()).join(``):`?`}render(){return g`
      <span class="avatar">
        <svg
          viewBox="0 0 100 100"
          xmlns="http://www.w3.org/2000/svg"
          role="img"
        >
          ${this.label?g`<title>${this.label}</title>`:``}
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
    `}};va.styles=[_a],m([w()],va.prototype,`label`,void 0),m([T()],va.prototype,`_gradientId`,void 0),customElements.get(`craft-avatar`)||customElements.define(`craft-avatar`,va);var ya=v`
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
`,ba=v`
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
`,xa=v`
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
`,Sa=window,Ca=new WeakMap;function wa(e){Sa.applyFocusVisiblePolyfill&&!Ca.has(e)&&(Sa.applyFocusVisiblePolyfill(e),Ca.set(e,void 0))}var Ta=Or(e=>class extends e{static get properties(){return{focused:{type:Boolean,reflect:!0},focusedVisible:{type:Boolean,reflect:!0,attribute:`focused-visible`},autofocus:{type:Boolean,reflect:!0}}}constructor(){super(),this.focused=!1,this.focusedVisible=!1,this.autofocus=!1}firstUpdated(e){super.firstUpdated(e),this.__registerEventsForFocusMixin(),this.__syncAutofocusToFocusableElement()}disconnectedCallback(){super.disconnectedCallback(),this.__teardownEventsForFocusMixin()}updated(e){super.updated(e),e.has(`autofocus`)&&this.__syncAutofocusToFocusableElement()}__syncAutofocusToFocusableElement(){this._focusableNode&&(this.hasAttribute(`autofocus`)?this._focusableNode.setAttribute(`autofocus`,``):this._focusableNode.removeAttribute(`autofocus`))}focus(){this._focusableNode?.focus()}blur(){this._focusableNode?.blur()}get _focusableNode(){return this._inputNode||document.createElement(`input`)}__onFocus(){if(this.focused=!0,typeof Sa.applyFocusVisiblePolyfill==`function`)this.focusedVisible=this._focusableNode.hasAttribute(`data-focus-visible-added`);else try{this.focusedVisible=this._focusableNode.matches(`:focus-visible`)}catch{this.focusedVisible=!1}}__onBlur(){this.focused=!1,this.focusedVisible=!1}__registerEventsForFocusMixin(){wa(this.getRootNode()),this.__redispatchFocus=e=>{e.stopPropagation(),this.dispatchEvent(new Event(`focus`))},this._focusableNode.addEventListener(`focus`,this.__redispatchFocus),this.__redispatchBlur=e=>{e.stopPropagation(),this.dispatchEvent(new Event(`blur`))},this._focusableNode.addEventListener(`blur`,this.__redispatchBlur),this.__redispatchFocusin=e=>{e.stopPropagation(),this.__onFocus(),this.dispatchEvent(new Event(`focusin`,{bubbles:!0,composed:!0}))},this._focusableNode.addEventListener(`focusin`,this.__redispatchFocusin),this.__redispatchFocusout=e=>{e.stopPropagation(),this.__onBlur(),this.dispatchEvent(new Event(`focusout`,{bubbles:!0,composed:!0}))},this._focusableNode.addEventListener(`focusout`,this.__redispatchFocusout)}__teardownEventsForFocusMixin(){this._focusableNode&&(this._focusableNode?.removeEventListener(`focus`,this.__redispatchFocus),this._focusableNode?.removeEventListener(`blur`,this.__redispatchBlur),this._focusableNode?.removeEventListener(`focusin`,this.__redispatchFocusin),this._focusableNode?.removeEventListener(`focusout`,this.__redispatchFocusout))}});function Ea(e,t){return t={exports:{}},e(t,t.exports),t.exports}var Da=`long`,Oa=`short`,ka=`narrow`,P=`numeric`,Aa=`2-digit`,ja={number:{decimal:{style:`decimal`},integer:{style:`decimal`,maximumFractionDigits:0},currency:{style:`currency`,currency:`USD`},percent:{style:`percent`},default:{style:`decimal`}},date:{short:{month:P,day:P,year:Aa},medium:{month:Oa,day:P,year:P},long:{month:Da,day:P,year:P},full:{month:Da,day:P,year:P,weekday:Da},default:{month:Oa,day:P,year:P}},time:{short:{hour:P,minute:P},medium:{hour:P,minute:P,second:P},long:{hour:P,minute:P,second:P,timeZoneName:Oa},full:{hour:P,minute:P,second:P,timeZoneName:Oa},default:{hour:P,minute:P,second:P}},duration:{default:{hours:{minimumIntegerDigits:1,maximumFractionDigits:0},minutes:{minimumIntegerDigits:2,maximumFractionDigits:0},seconds:{minimumIntegerDigits:2,maximumFractionDigits:3}}},parseNumberPattern:function(e){if(e){var t={},n=e.match(/\b[A-Z]{3}\b/i),r=e.replace(/[^¤]/g,``).length;if(!r&&n&&(r=1),r?(t.style=`currency`,t.currencyDisplay=r===1?`symbol`:r===2?`code`:`name`,t.currency=n?n[0].toUpperCase():`USD`):e.indexOf(`%`)>=0&&(t.style=`percent`),!/[@#0]/.test(e))return t.style?t:void 0;if(t.useGrouping=e.indexOf(`,`)>=0,/E\+?[@#0]+/i.test(e)||e.indexOf(`@`)>=0){var i=e.replace(/E\+?[@#0]+|[^@#0]/gi,``);t.minimumSignificantDigits=Math.min(Math.max(i.replace(/[^@0]/g,``).length,1),21),t.maximumSignificantDigits=Math.min(Math.max(i.length,1),21)}else{for(var a=e.replace(/[^#0.]/g,``).split(`.`),o=a[0],s=o.length-1;o[s]===`0`;)--s;t.minimumIntegerDigits=Math.min(Math.max(o.length-1-s,1),21);var c=a[1]||``;for(s=0;c[s]===`0`;)++s;for(t.minimumFractionDigits=Math.min(Math.max(s,0),20);c[s]===`#`;)++s;t.maximumFractionDigits=Math.min(Math.max(s,0),20)}return t}},parseDatePattern:function(e){if(e){for(var t={},n=0;n<e.length;){for(var r=e[n],i=1;e[++n]===r;)++i;switch(r){case`G`:t.era=i===5?ka:i===4?Da:Oa;break;case`y`:case`Y`:t.year=i===2?Aa:P;break;case`M`:case`L`:i=Math.min(Math.max(i-1,0),4),t.month=[P,Aa,Oa,Da,ka][i];break;case`E`:case`e`:case`c`:t.weekday=i===5?ka:i===4?Da:Oa;break;case`d`:case`D`:t.day=i===2?Aa:P;break;case`h`:case`K`:t.hour12=!0,t.hour=i===2?Aa:P;break;case`H`:case`k`:t.hour12=!1,t.hour=i===2?Aa:P;break;case`m`:t.minute=i===2?Aa:P;break;case`s`:case`S`:t.second=i===2?Aa:P;break;case`z`:case`Z`:case`v`:case`V`:t.timeZoneName=i===1?Oa:Da;break}}return Object.keys(t).length?t:void 0}}},Ma=function(e,t){if(typeof e==`string`&&t[e])return e;for(var n=[].concat(e||[]),r=0,i=n.length;r<i;++r)for(var a=n[r].split(`-`);a.length;){var o=a.join(`-`);if(t[o])return o;a.pop()}},Na=`zero`,F=`one`,Pa=`two`,I=`few`,Fa=`many`,L=`other`,R=[function(e){return+e==1?F:L},function(e){var t=+e;return 0<=t&&t<=1?F:L},function(e){var t=Math.floor(Math.abs(+e)),n=+e;return t===0||n===1?F:L},function(e){var t=+e;return t===0?Na:t===1?F:t===2?Pa:3<=t%100&&t%100<=10?I:11<=t%100&&t%100<=99?Fa:L},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length;return t===1&&n===0?F:L},function(e){var t=+e;return t%10==1&&t%100!=11?F:2<=t%10&&t%10<=4&&(t%100<12||14<t%100)?I:t%10==0||5<=t%10&&t%10<=9||11<=t%100&&t%100<=14?Fa:L},function(e){var t=+e;return t%10==1&&t%100!=11&&t%100!=71&&t%100!=91?F:t%10==2&&t%100!=12&&t%100!=72&&t%100!=92?Pa:(3<=t%10&&t%10<=4||t%10==9)&&(t%100<10||19<t%100)&&(t%100<70||79<t%100)&&(t%100<90||99<t%100)?I:t!==0&&t%1e6==0?Fa:L},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length,r=+(e+`.`).split(`.`)[1];return n===0&&t%10==1&&t%100!=11||r%10==1&&r%100!=11?F:n===0&&2<=t%10&&t%10<=4&&(t%100<12||14<t%100)||2<=r%10&&r%10<=4&&(r%100<12||14<r%100)?I:L},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length;return t===1&&n===0?F:2<=t&&t<=4&&n===0?I:n===0?L:Fa},function(e){var t=+e;return t===0?Na:t===1?F:t===2?Pa:t===3?I:t===6?Fa:L},function(e){var t=Math.floor(Math.abs(+e)),n=+(``+e).replace(/^[^.]*.?|0+$/g,``);return+e==1||n!==0&&(t===0||t===1)?F:L},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length,r=+(e+`.`).split(`.`)[1];return n===0&&t%100==1||r%100==1?F:n===0&&t%100==2||r%100==2?Pa:n===0&&3<=t%100&&t%100<=4||3<=r%100&&r%100<=4?I:L},function(e){var t=Math.floor(Math.abs(+e));return t===0||t===1?F:L},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length,r=+(e+`.`).split(`.`)[1];return n===0&&(t===1||t===2||t===3)||n===0&&t%10!=4&&t%10!=6&&t%10!=9||n!==0&&r%10!=4&&r%10!=6&&r%10!=9?F:L},function(e){var t=+e;return t===1?F:t===2?Pa:3<=t&&t<=6?I:7<=t&&t<=10?Fa:L},function(e){var t=+e;return t===1||t===11?F:t===2||t===12?Pa:3<=t&&t<=10||13<=t&&t<=19?I:L},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length;return n===0&&t%10==1?F:n===0&&t%10==2?Pa:n===0&&(t%100==0||t%100==20||t%100==40||t%100==60||t%100==80)?I:n===0?L:Fa},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length,r=+e;return t===1&&n===0?F:t===2&&n===0?Pa:n===0&&(r<0||10<r)&&r%10==0?Fa:L},function(e){var t=Math.floor(Math.abs(+e)),n=+(``+e).replace(/^[^.]*.?|0+$/g,``);return n===0&&t%10==1&&t%100!=11||n!==0?F:L},function(e){var t=+e;return t===1?F:t===2?Pa:L},function(e){var t=+e;return t===0?Na:t===1?F:L},function(e){var t=Math.floor(Math.abs(+e)),n=+e;return n===0?Na:(t===0||t===1)&&n!==0?F:L},function(e){var t=+(e+`.`).split(`.`)[1],n=+e;return n%10==1&&(n%100<11||19<n%100)?F:2<=n%10&&n%10<=9&&(n%100<11||19<n%100)?I:t===0?L:Fa},function(e){var t=(e+`.`).split(`.`)[1].length,n=+(e+`.`).split(`.`)[1],r=+e;return r%10==0||11<=r%100&&r%100<=19||t===2&&11<=n%100&&n%100<=19?Na:r%10==1&&r%100!=11||t===2&&n%10==1&&n%100!=11||t!==2&&n%10==1?F:L},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length,r=+(e+`.`).split(`.`)[1];return n===0&&t%10==1&&t%100!=11||r%10==1&&r%100!=11?F:L},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length,r=+e;return t===1&&n===0?F:n!==0||r===0||r!==1&&1<=r%100&&r%100<=19?I:L},function(e){var t=+e;return t===1?F:t===0||2<=t%100&&t%100<=10?I:11<=t%100&&t%100<=19?Fa:L},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length;return t===1&&n===0?F:n===0&&2<=t%10&&t%10<=4&&(t%100<12||14<t%100)?I:n===0&&t!==1&&0<=t%10&&t%10<=1||n===0&&5<=t%10&&t%10<=9||n===0&&12<=t%100&&t%100<=14?Fa:L},function(e){var t=Math.floor(Math.abs(+e));return 0<=t&&t<=1?F:L},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length;return n===0&&t%10==1&&t%100!=11?F:n===0&&2<=t%10&&t%10<=4&&(t%100<12||14<t%100)?I:n===0&&t%10==0||n===0&&5<=t%10&&t%10<=9||n===0&&11<=t%100&&t%100<=14?Fa:L},function(e){var t=Math.floor(Math.abs(+e)),n=+e;return t===0||n===1?F:2<=n&&n<=10?I:L},function(e){var t=Math.floor(Math.abs(+e)),n=+(e+`.`).split(`.`)[1],r=+e;return r===0||r===1||t===0&&n===1?F:L},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length;return n===0&&t%100==1?F:n===0&&t%100==2?Pa:n===0&&3<=t%100&&t%100<=4||n!==0?I:L},function(e){var t=+e;return 0<=t&&t<=1||11<=t&&t<=99?F:L},function(e){var t=+e;return t===1||t===5||t===7||t===8||t===9||t===10?F:t===2||t===3?Pa:t===4?I:t===6?Fa:L},function(e){var t=Math.floor(Math.abs(+e));return t%10==1||t%10==2||t%10==5||t%10==7||t%10==8||t%100==20||t%100==50||t%100==70||t%100==80?F:t%10==3||t%10==4||t%1e3==100||t%1e3==200||t%1e3==300||t%1e3==400||t%1e3==500||t%1e3==600||t%1e3==700||t%1e3==800||t%1e3==900?I:t===0||t%10==6||t%100==40||t%100==60||t%100==90?Fa:L},function(e){var t=+e;return(t%10==2||t%10==3)&&t%100!=12&&t%100!=13?I:L},function(e){var t=+e;return t===1||t===3?F:t===2?Pa:t===4?I:L},function(e){var t=+e;return t===0||t===7||t===8||t===9?Na:t===1?F:t===2?Pa:t===3||t===4?I:t===5||t===6?Fa:L},function(e){var t=+e;return t%10==1&&t%100!=11?F:t%10==2&&t%100!=12?Pa:t%10==3&&t%100!=13?I:L},function(e){var t=+e;return t===1||t===11?F:t===2||t===12?Pa:t===3||t===13?I:L},function(e){var t=+e;return t===1?F:t===2||t===3?Pa:t===4?I:t===6?Fa:L},function(e){var t=+e;return t===1||t===5?F:L},function(e){var t=+e;return t===11||t===8||t===80||t===800?Fa:L},function(e){var t=Math.floor(Math.abs(+e));return t===1?F:t===0||2<=t%100&&t%100<=20||t%100==40||t%100==60||t%100==80?Fa:L},function(e){var t=+e;return t%10==6||t%10==9||t%10==0&&t!==0?Fa:L},function(e){var t=Math.floor(Math.abs(+e));return t%10==1&&t%100!=11?F:t%10==2&&t%100!=12?Pa:(t%10==7||t%10==8)&&t%100!=17&&t%100!=18?Fa:L},function(e){var t=+e;return t===1?F:t===2||t===3?Pa:t===4?I:L},function(e){var t=+e;return 1<=t&&t<=4?F:L},function(e){var t=+e;return t===1||t===5||7<=t&&t<=9?F:t===2||t===3?Pa:t===4?I:t===6?Fa:L},function(e){var t=+e;return t===1?F:t%10==4&&t%100!=14?Fa:L},function(e){var t=+e;return(t%10==1||t%10==2)&&t%100!=11&&t%100!=12?F:L},function(e){var t=+e;return t%10==6||t%10==9||t===10?I:L},function(e){var t=+e;return t%10==3&&t%100!=13?I:L}],Ia={af:{cardinal:R[0]},ak:{cardinal:R[1]},am:{cardinal:R[2]},ar:{cardinal:R[3]},ars:{cardinal:R[3]},as:{cardinal:R[2],ordinal:R[34]},asa:{cardinal:R[0]},ast:{cardinal:R[4]},az:{cardinal:R[0],ordinal:R[35]},be:{cardinal:R[5],ordinal:R[36]},bem:{cardinal:R[0]},bez:{cardinal:R[0]},bg:{cardinal:R[0]},bh:{cardinal:R[1]},bn:{cardinal:R[2],ordinal:R[34]},br:{cardinal:R[6]},brx:{cardinal:R[0]},bs:{cardinal:R[7]},ca:{cardinal:R[4],ordinal:R[37]},ce:{cardinal:R[0]},cgg:{cardinal:R[0]},chr:{cardinal:R[0]},ckb:{cardinal:R[0]},cs:{cardinal:R[8]},cy:{cardinal:R[9],ordinal:R[38]},da:{cardinal:R[10]},de:{cardinal:R[4]},dsb:{cardinal:R[11]},dv:{cardinal:R[0]},ee:{cardinal:R[0]},el:{cardinal:R[0]},en:{cardinal:R[4],ordinal:R[39]},eo:{cardinal:R[0]},es:{cardinal:R[0]},et:{cardinal:R[4]},eu:{cardinal:R[0]},fa:{cardinal:R[2]},ff:{cardinal:R[12]},fi:{cardinal:R[4]},fil:{cardinal:R[13],ordinal:R[0]},fo:{cardinal:R[0]},fr:{cardinal:R[12],ordinal:R[0]},fur:{cardinal:R[0]},fy:{cardinal:R[4]},ga:{cardinal:R[14],ordinal:R[0]},gd:{cardinal:R[15],ordinal:R[40]},gl:{cardinal:R[4]},gsw:{cardinal:R[0]},gu:{cardinal:R[2],ordinal:R[41]},guw:{cardinal:R[1]},gv:{cardinal:R[16]},ha:{cardinal:R[0]},haw:{cardinal:R[0]},he:{cardinal:R[17]},hi:{cardinal:R[2],ordinal:R[41]},hr:{cardinal:R[7]},hsb:{cardinal:R[11]},hu:{cardinal:R[0],ordinal:R[42]},hy:{cardinal:R[12],ordinal:R[0]},ia:{cardinal:R[4]},io:{cardinal:R[4]},is:{cardinal:R[18]},it:{cardinal:R[4],ordinal:R[43]},iu:{cardinal:R[19]},iw:{cardinal:R[17]},jgo:{cardinal:R[0]},ji:{cardinal:R[4]},jmc:{cardinal:R[0]},ka:{cardinal:R[0],ordinal:R[44]},kab:{cardinal:R[12]},kaj:{cardinal:R[0]},kcg:{cardinal:R[0]},kk:{cardinal:R[0],ordinal:R[45]},kkj:{cardinal:R[0]},kl:{cardinal:R[0]},kn:{cardinal:R[2]},ks:{cardinal:R[0]},ksb:{cardinal:R[0]},ksh:{cardinal:R[20]},ku:{cardinal:R[0]},kw:{cardinal:R[19]},ky:{cardinal:R[0]},lag:{cardinal:R[21]},lb:{cardinal:R[0]},lg:{cardinal:R[0]},ln:{cardinal:R[1]},lt:{cardinal:R[22]},lv:{cardinal:R[23]},mas:{cardinal:R[0]},mg:{cardinal:R[1]},mgo:{cardinal:R[0]},mk:{cardinal:R[24],ordinal:R[46]},ml:{cardinal:R[0]},mn:{cardinal:R[0]},mo:{cardinal:R[25],ordinal:R[0]},mr:{cardinal:R[2],ordinal:R[47]},mt:{cardinal:R[26]},nah:{cardinal:R[0]},naq:{cardinal:R[19]},nb:{cardinal:R[0]},nd:{cardinal:R[0]},ne:{cardinal:R[0],ordinal:R[48]},nl:{cardinal:R[4]},nn:{cardinal:R[0]},nnh:{cardinal:R[0]},no:{cardinal:R[0]},nr:{cardinal:R[0]},nso:{cardinal:R[1]},ny:{cardinal:R[0]},nyn:{cardinal:R[0]},om:{cardinal:R[0]},or:{cardinal:R[0],ordinal:R[49]},os:{cardinal:R[0]},pa:{cardinal:R[1]},pap:{cardinal:R[0]},pl:{cardinal:R[27]},prg:{cardinal:R[23]},ps:{cardinal:R[0]},pt:{cardinal:R[28]},"pt-PT":{cardinal:R[4]},rm:{cardinal:R[0]},ro:{cardinal:R[25],ordinal:R[0]},rof:{cardinal:R[0]},ru:{cardinal:R[29]},rwk:{cardinal:R[0]},saq:{cardinal:R[0]},sc:{cardinal:R[4],ordinal:R[43]},scn:{cardinal:R[4],ordinal:R[43]},sd:{cardinal:R[0]},sdh:{cardinal:R[0]},se:{cardinal:R[19]},seh:{cardinal:R[0]},sh:{cardinal:R[7]},shi:{cardinal:R[30]},si:{cardinal:R[31]},sk:{cardinal:R[8]},sl:{cardinal:R[32]},sma:{cardinal:R[19]},smi:{cardinal:R[19]},smj:{cardinal:R[19]},smn:{cardinal:R[19]},sms:{cardinal:R[19]},sn:{cardinal:R[0]},so:{cardinal:R[0]},sq:{cardinal:R[0],ordinal:R[50]},sr:{cardinal:R[7]},ss:{cardinal:R[0]},ssy:{cardinal:R[0]},st:{cardinal:R[0]},sv:{cardinal:R[4],ordinal:R[51]},sw:{cardinal:R[4]},syr:{cardinal:R[0]},ta:{cardinal:R[0]},te:{cardinal:R[0]},teo:{cardinal:R[0]},ti:{cardinal:R[1]},tig:{cardinal:R[0]},tk:{cardinal:R[0],ordinal:R[52]},tl:{cardinal:R[13],ordinal:R[0]},tn:{cardinal:R[0]},tr:{cardinal:R[0]},ts:{cardinal:R[0]},tzm:{cardinal:R[33]},ug:{cardinal:R[0]},uk:{cardinal:R[29],ordinal:R[53]},ur:{cardinal:R[4]},uz:{cardinal:R[0]},ve:{cardinal:R[0]},vo:{cardinal:R[0]},vun:{cardinal:R[0]},wa:{cardinal:R[1]},wae:{cardinal:R[0]},xh:{cardinal:R[0]},xog:{cardinal:R[0]},yi:{cardinal:R[4]},zu:{cardinal:R[2]},lo:{ordinal:R[0]},ms:{ordinal:R[0]},vi:{ordinal:R[0]}},La=Ea(function(e,t){t=e.exports=function(e,t,r){return n(e,null,t||`en`,r||{},!0)},t.toParts=function(e,t,r){return n(e,null,t||`en`,r||{},!1)};function n(e,t,n,i,a){var o=e.map(function(e){return r(e,t,n,i,a)});return a?o.length===1?o[0]:function(e){for(var t=``,n=0;n<o.length;++n)t+=o[n](e);return t}:function(e){return o.reduce(function(t,n){return t.concat(n(e))},[])}}function r(e,t,r,a,o){if(typeof e==`string`){var s=e;return function(){return s}}var c=e[0],l=e[1];if(t&&e[0]===`#`){c=t[0];var u=t[2],f=(a.number||d.number)([c,`number`],r);return function(e){return f(i(c,e)-u,e)}}var p;l===`plural`||l===`selectordinal`?(p={},Object.keys(e[3]).forEach(function(t){p[t]=n(e[3][t],e,r,a,o)}),e=[e[0],e[1],e[2],p]):e[2]&&typeof e[2]==`object`&&(p={},Object.keys(e[2]).forEach(function(t){p[t]=n(e[2][t],e,r,a,o)}),e=[e[0],e[1],p]);var m=l&&(a[l]||d[l]);if(m){var h=m(e,r);return function(e){return h(i(c,e),e)}}return o?function(e){return String(i(c,e))}:function(e){return i(c,e)}}function i(e,t){if(t&&e in t)return t[e];for(var n=e.split(`.`),r=t,i=0,a=n.length;r&&i<a;++i)r=r[n[i]];return r}function a(e,t){var n=e[2],r=ja.number[n]||ja.parseNumberPattern(n)||ja.number.default;return new Intl.NumberFormat(t,r).format}function o(e,t){var n=e[2],r=ja.duration[n]||ja.duration.default,i=new Intl.NumberFormat(t,r.seconds).format,a=new Intl.NumberFormat(t,r.minutes).format,o=new Intl.NumberFormat(t,r.hours).format,s=/^fi$|^fi-|^da/.test(String(t))?`.`:`:`;return function(e,t){if(e=+e,!isFinite(e))return i(e);var n=~~(e/60/60),r=~~(e/60%60),c=(n?o(Math.abs(n))+s:``)+a(Math.abs(r))+s+i(Math.abs(e%60));return e<0?o(-1).replace(o(1),c):c}}function s(e,t){var n=e[1],r=e[2],i=ja[n][r]||ja.parseDatePattern(r)||ja[n].default;return new Intl.DateTimeFormat(t,i).format}function c(e,t){var n=e[1]===`selectordinal`?`ordinal`:`cardinal`,r=e[2],i=e[3],a;if(Intl.PluralRules&&Intl.PluralRules.supportedLocalesOf(t).length>0)a=new Intl.PluralRules(t,{type:n});else{var o=Ma(t,Ia);a={select:o&&Ia[o][n]||l}}return function(e,t){return(i[`=`+ +e]||i[a.select(e-r)]||i.other)(t)}}function l(){return`other`}function u(e,t){var n=e[2];return function(e,t){return(n[e]||n.other)(t)}}var d={number:a,ordinal:a,spellout:a,duration:o,date:s,time:s,plural:c,selectordinal:c,select:u};t.types=d});La.toParts,La.types;var Ra=Ea(function(e,t){var n=`{`,r=`}`,i=`,`,a=`#`,o=`<`,s=`>`,c=`</`,l=`/>`,u=`'`,d=`offset:`,f=[`number`,`date`,`time`,`ordinal`,`duration`,`spellout`],p=[`plural`,`select`,`selectordinal`];t=e.exports=function(e,t){return m({pattern:String(e),index:0,tagsType:t&&t.tagsType||null,tokens:t&&t.tokens||null},``)};function m(e,t){var n=e.pattern,i=n.length,a=[],o=e.index,s=h(e,t);for(s&&a.push(s),s&&e.tokens&&e.tokens.push([`text`,n.slice(o,e.index)]);e.index<i;){if(n[e.index]===r){if(!t)throw E(e);break}if(t&&e.tagsType&&n.slice(e.index,e.index+c.length)===c)break;a.push(v(e)),o=e.index,s=h(e,t),s&&a.push(s),s&&e.tokens&&e.tokens.push([`text`,n.slice(o,e.index)])}return a}function h(e,t){for(var i=e.pattern,s=i.length,c=t===`plural`||t===`selectordinal`,l=!!e.tagsType,d=t===`{style}`,f=``;e.index<s;){var p=i[e.index];if(p===n||p===r||c&&p===a||l&&p===o||d&&g(p.charCodeAt(0)))break;if(p===u)if(p=i[++e.index],p===u)f+=p,++e.index;else if(p===n||p===r||c&&p===a||l&&p===o||d)for(f+=p;++e.index<s;)if(p=i[e.index],p===u&&i[e.index+1]===u)f+=u,++e.index;else if(p===u){++e.index;break}else f+=p;else f+=u;else f+=p,++e.index}return f}function g(e){return e>=9&&e<=13||e===32||e===133||e===160||e===6158||e>=8192&&e<=8205||e===8232||e===8233||e===8239||e===8287||e===8288||e===12288||e===65279}function _(e){for(var t=e.pattern,n=t.length,r=e.index;e.index<n&&g(t.charCodeAt(e.index));)++e.index;r<e.index&&e.tokens&&e.tokens.push([`space`,e.pattern.slice(r,e.index)])}function v(e){var t=e.pattern;if(t[e.index]===a)return e.tokens&&e.tokens.push([`syntax`,a]),++e.index,[a];var o=y(e);if(o)return o;if(t[e.index]!==n)throw E(e,n);e.tokens&&e.tokens.push([`syntax`,n]),++e.index,_(e);var s=b(e);if(!s)throw E(e,`placeholder id`);e.tokens&&e.tokens.push([`id`,s]),_(e);var c=t[e.index];if(c===r)return e.tokens&&e.tokens.push([`syntax`,r]),++e.index,[s];if(c!==i)throw E(e,i+` or `+r);e.tokens&&e.tokens.push([`syntax`,i]),++e.index,_(e);var l=b(e);if(!l)throw E(e,`placeholder type`);if(e.tokens&&e.tokens.push([`type`,l]),_(e),c=t[e.index],c===r){if(e.tokens&&e.tokens.push([`syntax`,r]),l===`plural`||l===`selectordinal`||l===`select`)throw E(e,l+` sub-messages`);return++e.index,[s,l]}if(c!==i)throw E(e,i+` or `+r);e.tokens&&e.tokens.push([`syntax`,i]),++e.index,_(e);var u;if(l===`plural`||l===`selectordinal`){var d=S(e);_(e),u=[s,l,d,w(e,l)]}else if(l===`select`)u=[s,l,w(e,l)];else if(f.indexOf(l)>=0)u=[s,l,x(e)];else{var p=e.index,m=x(e);_(e),t[e.index]===n&&(e.index=p,m=w(e,l)),u=[s,l,m]}if(_(e),t[e.index]!==r)throw E(e,r);return e.tokens&&e.tokens.push([`syntax`,r]),++e.index,u}function y(e){var t=e.tagsType;if(!(!t||e.pattern[e.index]!==o)){if(e.pattern.slice(e.index,e.index+c.length)===c)throw E(e,null,`closing tag without matching opening tag`);e.tokens&&e.tokens.push([`syntax`,o]),++e.index;var n=b(e,!0);if(!n)throw E(e,`placeholder id`);if(e.tokens&&e.tokens.push([`id`,n]),_(e),e.pattern.slice(e.index,e.index+l.length)===l)return e.tokens&&e.tokens.push([`syntax`,l]),e.index+=l.length,[n,t];if(e.pattern[e.index]!==s)throw E(e,s);e.tokens&&e.tokens.push([`syntax`,s]),++e.index;var r=m(e,t),i=e.index;if(e.pattern.slice(e.index,e.index+c.length)!==c)throw E(e,c+n+s);e.tokens&&e.tokens.push([`syntax`,c]),e.index+=c.length;var a=b(e,!0);if(a&&e.tokens&&e.tokens.push([`id`,a]),n!==a)throw e.index=i,E(e,c+n+s,c+a+s);if(_(e),e.pattern[e.index]!==s)throw E(e,s);return e.tokens&&e.tokens.push([`syntax`,s]),++e.index,[n,t,{children:r}]}}function b(e,t){for(var c=e.pattern,l=c.length,d=``;e.index<l;){var f=c[e.index];if(f===n||f===r||f===i||f===a||f===u||g(f.charCodeAt(0))||t&&(f===o||f===s||f===`/`))break;d+=f,++e.index}return d}function x(e){var t=e.index,n=h(e,`{style}`);if(!n)throw E(e,`placeholder style name`);return e.tokens&&e.tokens.push([`style`,e.pattern.slice(t,e.index)]),n}function S(e){var t=e.pattern,n=t.length,r=0;if(t.slice(e.index,e.index+d.length)===d){e.tokens&&e.tokens.push([`offset`,`offset`],[`syntax`,`:`]),e.index+=d.length,_(e);for(var i=e.index;e.index<n&&C(t.charCodeAt(e.index));)++e.index;if(i===e.index)throw E(e,`offset number`);e.tokens&&e.tokens.push([`number`,t.slice(i,e.index)]),r=+t.slice(i,e.index)}return r}function C(e){return e>=48&&e<=57}function w(e,t){for(var n=e.pattern,i=n.length,a={};e.index<i&&n[e.index]!==r;){var o=b(e);if(!o)throw E(e,`sub-message selector`);e.tokens&&e.tokens.push([`selector`,o]),_(e),a[o]=T(e,t),_(e)}if(!a.other&&p.indexOf(t)>=0)throw E(e,null,null,`"other" sub-message must be specified in `+t);return a}function T(e,t){if(e.pattern[e.index]!==n)throw E(e,n+` to start sub-message`);e.tokens&&e.tokens.push([`syntax`,n]),++e.index;var i=m(e,t);if(e.pattern[e.index]!==r)throw E(e,r+` to end sub-message`);return e.tokens&&e.tokens.push([`syntax`,r]),++e.index,i}function E(e,t,n,r){var i=e.pattern,a=i.slice(0,e.index).split(/\r?\n/),o=e.index,s=a.length,c=a.slice(-1)[0].length;return n||=e.index>=i.length?`end of message pattern`:b(e)||i[e.index],r||=D(t,n),r+=` in `+i.replace(/\r?\n/g,`
`),new O(r,t,n,o,s,c)}function D(e,t){return e?`Expected `+e+` but found `+t:`Unexpected `+t+` found`}function O(e,t,n,r,i,a){Error.call(this,e),this.name=`SyntaxError`,this.message=e,this.expected=t,this.found=n,this.offset=r,this.line=i,this.column=a}O.prototype=Object.create(Error.prototype),t.SyntaxError=O});Ra.SyntaxError;var za=RegExp(`^(`+Object.keys(Ia).join(`|`)+`)\\b`),Ba=new WeakMap;function Va(e,t,n){if(!(this instanceof Va)||Ba.has(this))throw TypeError(`calling MessageFormat constructor without new is invalid`);var r=Ra(e);Ba.set(this,{ast:r,format:La(r,t,n&&n.types),locale:Va.supportedLocalesOf(t)[0]||`en`,locales:t,options:n})}var Ha=Va;Object.defineProperties(Va.prototype,{format:{configurable:!0,get:function(){var e=Ba.get(this);if(!e)throw TypeError(`MessageFormat.prototype.format called on value that's not an object initialized as a MessageFormat`);return e.format}},formatToParts:{configurable:!0,writable:!0,value:function(e){var t=Ba.get(this);if(!t)throw TypeError(`MessageFormat.prototype.formatToParts called on value that's not an object initialized as a MessageFormat`);return(t.toParts||=La.toParts(t.ast,t.locales,t.options&&t.options.types))(e)}},resolvedOptions:{configurable:!0,writable:!0,value:function(){var e=Ba.get(this);if(!e)throw TypeError(`MessageFormat.prototype.resolvedOptions called on value that's not an object initialized as a MessageFormat`);return{locale:e.locale}}}}),typeof Symbol<`u`&&Object.defineProperty(Va.prototype,Symbol.toStringTag,{value:`Object`}),Object.defineProperties(Va,{supportedLocalesOf:{configurable:!0,writable:!0,value:function(e){return[].concat(Intl.NumberFormat.supportedLocalesOf(e),Intl.DateTimeFormat.supportedLocalesOf(e),Intl.PluralRules?Intl.PluralRules.supportedLocalesOf(e):[],[].concat(e||[]).filter(function(e){return za.test(e)})).filter(function(e,t,n){return n.indexOf(e)===t})}}});function Ua(e){return!!(e&&e.default&&typeof e.default==`object`&&Object.keys(e).length===1)}var Wa=globalThis.document?.documentElement,Ga=class extends EventTarget{formatNumberOptions={returnIfNaN:``,postProcessors:new Map};formatDateOptions={postProcessors:new Map};#e=!1;#t=``;#n=null;__storage={};__namespacePatternsMap=new Map;__namespaceLoadersCache={};__namespaceLoaderPromisesCache={};get locale(){return this.#e?this.#t||``:Wa.lang||``}set locale(e){if(this.#r(e),!this.#e){let t=Wa.lang;this._setHtmlLangAttribute(e),this._onLocaleChanged(e,t);return}let t=this.#t;this.#t=e,this.#n===null&&this._setHtmlLangAttribute(e),this._onLocaleChanged(e,t)}get loadingComplete(){return typeof this.__namespaceLoaderPromisesCache[this.locale]==`object`?Promise.all(Object.values(this.__namespaceLoaderPromisesCache[this.locale])):Promise.resolve()}constructor({allowOverridesForExistingNamespaces:e=!1,autoLoadOnLocaleChange:t=!1,showKeyAsFallback:n=!1,fallbackLocale:r=``}={}){super(),this.__allowOverridesForExistingNamespaces=e,this._autoLoadOnLocaleChange=!!t,this._showKeyAsFallback=n,this._fallbackLocale=r;let i=Wa.getAttribute(`data-localize-lang`);this.#e=!!i,this.#e&&(this.locale=i,this._setupTranslationToolSupport()),Wa.lang||=this.locale||`en-GB`,this._setupHtmlLangAttributeObserver()}addData(e,t,n){if(!this.__allowOverridesForExistingNamespaces&&this._isNamespaceInCache(e,t))throw Error(`Namespace "${t}" has been already added for the locale "${e}".`);this.__storage[e]=this.__storage[e]||{},this.__allowOverridesForExistingNamespaces?this.__storage[e][t]={...this.__storage[e][t],...n}:this.__storage[e][t]=n}setupNamespaceLoader(e,t){this.__namespacePatternsMap.set(e,t)}loadNamespaces(e,{locale:t}={}){return Promise.all(e.map(e=>this.loadNamespace(e,{locale:t})))}loadNamespace(e,{locale:t=this.locale}={locale:this.locale}){let n=typeof e==`object`,r=n?Object.keys(e)[0]:e;return this._isNamespaceInCache(t,r)?Promise.resolve():this._getCachedNamespaceLoaderPromise(t,r)||this._loadNamespaceData(t,e,n,r)}msg(e,t,n={}){let r=n.locale?n.locale:this.locale,i=this._getMessageForKeys(e,r);return i?new Ha(i,r).format(t):``}teardown(){this._teardownHtmlLangAttributeObserver()}reset(){this.__storage={},this.__namespacePatternsMap=new Map,this.__namespaceLoadersCache={},this.__namespaceLoaderPromisesCache={}}setDatePostProcessorForLocale({locale:e,postProcessor:t}){this.formatDateOptions?.postProcessors.set(e,t)}setNumberPostProcessorForLocale({locale:e,postProcessor:t}){this.formatNumberOptions?.postProcessors.set(e,t)}_setupTranslationToolSupport(){this.#n=Wa.lang||null}_setHtmlLangAttribute(e){this._teardownHtmlLangAttributeObserver(),Wa.lang=e,this._setupHtmlLangAttributeObserver()}_setupHtmlLangAttributeObserver(){this._htmlLangAttributeObserver||=new MutationObserver(e=>{e.forEach(e=>{this.#e?Wa.lang===`auto`?(this.#n=null,this._setHtmlLangAttribute(this.locale)):this.#n=document.documentElement.lang:this._onLocaleChanged(document.documentElement.lang,e.oldValue||``)})}),this._htmlLangAttributeObserver.observe(document.documentElement,{attributes:!0,attributeFilter:[`lang`],attributeOldValue:!0})}_teardownHtmlLangAttributeObserver(){this._htmlLangAttributeObserver&&this._htmlLangAttributeObserver.disconnect()}_isNamespaceInCache(e,t){return!!(this.__storage[e]&&this.__storage[e][t])}_getCachedNamespaceLoaderPromise(e,t){return this.__namespaceLoaderPromisesCache[e]?this.__namespaceLoaderPromisesCache[e][t]:null}_loadNamespaceData(e,t,n,r){let i=this._getNamespaceLoader(t,n,r),a=this._getNamespaceLoaderPromise(i,e,r);return this._cacheNamespaceLoaderPromise(e,r,a),a.then(t=>{if(this.__namespaceLoaderPromisesCache[e]&&this.__namespaceLoaderPromisesCache[e][r]===a){let n=Ua(t)?t.default:t;this.addData(e,r,n)}})}_getNamespaceLoader(e,t,n){let r=this.__namespaceLoadersCache[n];if(r||(t?(r=e[n],this.__namespaceLoadersCache[n]=r):(r=this._lookupNamespaceLoader(n),this.__namespaceLoadersCache[n]=r)),!r)throw Error(`Namespace "${n}" was not properly setup.`);return this.__namespaceLoadersCache[n]=r,r}_getNamespaceLoaderPromise(e,t,n,r=this._fallbackLocale){return e(t,n).catch(()=>{let i=this._getLangFromLocale(t);return e(i,n).catch(()=>{if(r)return this._getNamespaceLoaderPromise(e,r,n,``).catch(()=>{let e=this._getLangFromLocale(r);throw Error(`Data for namespace "${n}" and current locale "${t}" or fallback locale "${r}" could not be loaded. Make sure you have data either for locale "${t}" (and/or generic language "${i}") or for fallback "${r}" (and/or "${e}").`)});throw Error(`Data for namespace "${n}" and locale "${t}" could not be loaded. Make sure you have data for locale "${t}" (and/or generic language "${i}").`)})})}_cacheNamespaceLoaderPromise(e,t,n){this.__namespaceLoaderPromisesCache[e]||(this.__namespaceLoaderPromisesCache[e]={}),this.__namespaceLoaderPromisesCache[e][t]=n}_lookupNamespaceLoader(e){for(let[t,n]of this.__namespacePatternsMap){let r=typeof t==`string`&&t===e,i=typeof t==`object`&&t.constructor.name===`RegExp`&&t.test(e);if(r||i)return n}return null}_getLangFromLocale(e){return e.substring(0,2)}_onLocaleChanged(e,t){this.dispatchEvent(new CustomEvent(`__localeChanging`)),e!==t&&(this._autoLoadOnLocaleChange?(this._loadAllMissing(e,t),this.loadingComplete.then(()=>{this.dispatchEvent(new CustomEvent(`localeChanged`,{detail:{newLocale:e,oldLocale:t}}))})):this.dispatchEvent(new CustomEvent(`localeChanged`,{detail:{newLocale:e,oldLocale:t}})))}_loadAllMissing(e,t){let n=this.__storage[t]||{},r=this.__storage[e]||{};Object.keys(n).forEach(t=>{r[t]||this.loadNamespace(t,{locale:e})})}_getMessageForKeys(e,t){if(typeof e==`string`)return this._getMessageForKey(e,t);let n=Array.from(e).reverse(),r,i;for(;n.length;)if(r=n.pop(),i=this._getMessageForKey(r,t),i)return i}_getMessageForKey(e,t){if(!e||e.indexOf(`:`)===-1)throw Error(`Namespace is missing in the key "${e}". The format for keys is "namespace:name".`);let[n,r]=e.split(`:`),i=this.__storage[t],a=i?i[n]:{},o=r.split(`.`).reduce((e,t)=>typeof e==`object`?e[t]:e,a);return String(o||(this._showKeyAsFallback?e:``))}#r(e){if(!e.includes(`-`))throw Error(`
      Locale was set to ${e}.
      Language only locales are not allowed, please use the full language locale e.g. 'en-GB' instead of 'en'.
      See https://github.com/ing-bank/lion/issues/187 for more information.
    `)}get _supportExternalTranslationTools(){return this.#e}set _supportExternalTranslationTools(e){this.#e=e}get _langAttrSetByTranslationTool(){return this.#t}set _langAttrSetByTranslationTool(e){this.#t=e}},Ka=Symbol.for(`lion::SingletonManagerClassStorage`),qa=globalThis||window,Ja=new class{constructor(){this._map=qa[Ka]?qa[Ka]:qa[Ka]=new Map}set(e,t){this.has(e)||this._map.set(e,t)}get(e){return this._map.get(e)}has(e){return this._map.has(e)}};function Ya(){if(Ja.has(`@lion/ui::localize::0.x`))return Ja.get(`@lion/ui::localize::0.x`);let e=new Ga({autoLoadOnLocaleChange:!0,fallbackLocale:`en-GB`});return Ja.set(`@lion/ui::localize::0.x`,e),e}var Xa=(e,t)=>{let n=e._$AN;if(n===void 0)return!1;for(let e of n)e._$AO?.(t,!1),Xa(e,t);return!0},Za=e=>{let t,n;do{if((t=e._$AM)===void 0)break;n=t._$AN,n.delete(e),e=t}while(n?.size===0)},Qa=e=>{for(let t;t=e._$AM;e=t){let n=t._$AN;if(n===void 0)t._$AN=n=new Set;else if(n.has(e))break;n.add(e),to(t)}};function $a(e){this._$AN===void 0?this._$AM=e:(Za(this),this._$AM=e,Qa(this))}function eo(e,t=!1,n=0){let r=this._$AH,i=this._$AN;if(i!==void 0&&i.size!==0)if(t)if(Array.isArray(r))for(let e=n;e<r.length;e++)Xa(r[e],!1),Za(r[e]);else r!=null&&(Xa(r,!1),Za(r));else Xa(this,e)}var to=e=>{e.type==ne.CHILD&&(e._$AP??=eo,e._$AQ??=$a)},no=class extends te{constructor(){super(...arguments),this._$AN=void 0}_$AT(e,t,n){super._$AT(e,t,n),Qa(this),this.isConnected=e._$AU}_$AO(e,t=!0){e!==this.isConnected&&(this.isConnected=e,e?this.reconnected?.():this.disconnected?.()),t&&(Xa(this,e),Za(this))}setValue(e){if(Pr(this._$Ct))this._$Ct._$AI(e,this);else{let t=[...this._$Ct._$AH];t[this._$Ci]=e,this._$Ct._$AI(t,this,0)}}disconnected(){}reconnected(){}},ro=class{constructor(e){this.G=e}disconnect(){this.G=void 0}reconnect(e){this.G=e}deref(){return this.G}},io=class{constructor(){this.Y=void 0,this.Z=void 0}get(){return this.Y}pause(){this.Y??=new Promise((e=>this.Z=e))}resume(){this.Z?.(),this.Y=this.Z=void 0}},ao=e=>!Mr(e)&&typeof e.then==`function`,oo=1073741823,so=k(class extends no{constructor(){super(...arguments),this._$Cwt=oo,this._$Cbt=[],this._$CK=new ro(this),this._$CX=new io}render(...e){return e.find((e=>!ao(e)))??y}update(e,t){let n=this._$Cbt,r=n.length;this._$Cbt=t;let i=this._$CK,a=this._$CX;this.isConnected||this.disconnected();for(let e=0;e<t.length&&!(e>this._$Cwt);e++){let o=t[e];if(!ao(o))return this._$Cwt=e,o;e<r&&o===n[e]||(this._$Cwt=oo,r=0,Promise.resolve(o).then((async e=>{for(;a.get();)await a.get();let t=i.deref();if(t!==void 0){let n=t._$Cbt.indexOf(o);n>-1&&n<t._$Cwt&&(t._$Cwt=n,t.setValue(e))}})))}return y}disconnected(){this._$CK.disconnect(),this._$CX.pause()}reconnected(){this._$CK.reconnect(this),this._$CX.resume()}}),co=Or(e=>class extends e{static get localizeNamespaces(){return[]}static get waitForLocalizeNamespaces(){return!0}constructor(){super(),this._localizeManager=Ya(),this.__boundLocalizeOnLocaleChanged=(...e)=>{let t=Array.from(e)[0];this.__localizeOnLocaleChanged(t)},this.__boundLocalizeOnLocaleChanging=()=>{this.__localizeOnLocaleChanging()},this.__localizeStartLoadingNamespaces(),this.localizeNamespacesLoaded&&this.localizeNamespacesLoaded.then(()=>{this.__localizeMessageSync=!0})}async scheduleUpdate(){Object.getPrototypeOf(this).constructor.waitForLocalizeNamespaces&&await this.localizeNamespacesLoaded,super.scheduleUpdate()}connectedCallback(){super.connectedCallback(),this.localizeNamespacesLoaded&&this.localizeNamespacesLoaded.then(()=>this.onLocaleReady()),this._localizeManager.addEventListener(`__localeChanging`,this.__boundLocalizeOnLocaleChanging),this._localizeManager.addEventListener(`localeChanged`,this.__boundLocalizeOnLocaleChanged)}disconnectedCallback(){super.disconnectedCallback(),this._localizeManager.removeEventListener(`__localeChanging`,this.__boundLocalizeOnLocaleChanging),this._localizeManager.removeEventListener(`localeChanged`,this.__boundLocalizeOnLocaleChanged)}msgLit(e,t,n){return this.__localizeMessageSync?this._localizeManager.msg(e,t,n):this.localizeNamespacesLoaded?so(this.localizeNamespacesLoaded.then(()=>this._localizeManager.msg(e,t,n)),S):``}__getUniqueNamespaces(){let e=[],t=new Set;return Object.getPrototypeOf(this).constructor.localizeNamespaces.forEach(t.add.bind(t)),t.forEach(t=>{e.push(t)}),e}__localizeStartLoadingNamespaces(){this.localizeNamespacesLoaded=this._localizeManager.loadNamespaces(this.__getUniqueNamespaces())}__localizeOnLocaleChanging(){this.__localizeStartLoadingNamespaces()}__localizeOnLocaleChanged(e){this.onLocaleChanged(e.detail.newLocale,e.detail.oldLocale)}onLocaleReady(){this.onLocaleUpdated()}onLocaleChanged(e,t){this.onLocaleUpdated(),this.requestUpdate()}onLocaleUpdated(){}}),lo=`3.0.0`,uo=window.scopedElementsVersions||(window.scopedElementsVersions=[]);uo.includes(lo)||uo.push(lo);var fo=Or(e=>class extends e{static scopedElements;static get scopedElementsVersion(){return lo}static __registry;get registry(){return this.constructor.__registry}set registry(e){this.constructor.__registry=e}attachShadow(e){let{scopedElements:t}=this.constructor;if(!this.registry||this.registry===this.constructor.__registry&&!Object.prototype.hasOwnProperty.call(this.constructor,`__registry`)){this.registry=new CustomElementRegistry;for(let[e,n]of Object.entries(t??{}))this.registry.define(e,n)}return super.attachShadow({...e,customElements:this.registry,registry:this.registry})}}),po=Or(e=>class extends fo(e){createRenderRoot(){let{shadowRootOptions:e,elementStyles:t}=this.constructor,n=this.attachShadow(e);return this.renderOptions.creationScope=n,_(n,t),this.renderOptions.renderBefore??=n.firstChild,n}});function mo(){return!!(globalThis.ShadowRoot?.prototype.createElement&&globalThis.ShadowRoot?.prototype.importNode)}var ho=Or(e=>class extends po(e){constructor(){super()}createScopedElement(e){return(mo()?this.shadowRoot:document).createElement(e)}defineScopedElement(e,t){let n=this.registry.get(e),r=n&&n!==t;return!mo()&&r&&console.error([`You are trying to re-register the "${e}" custom element with a different class via ScopedElementsMixin.`,`This is only possible with a CustomElementRegistry.`,`Your browser does not support this feature so you will need to load a polyfill for it.`,`Load "@webcomponents/scoped-custom-element-registry" before you register ANY web component to the global customElements registry.`,`e.g. add "<script src="/node_modules/@webcomponents/scoped-custom-element-registry/scoped-custom-element-registry.min.js"><\/script>" as your first script tag.`,`For more details you can visit https://open-wc.org/docs/development/scoped-elements/`].join(`
`)),n?this.registry.get(e):this.registry.define(e,t)}attachShadow(e){let{scopedElements:t}=this.constructor;if(!this.registry||this.registry===this.constructor.__registry&&!Object.prototype.hasOwnProperty.call(this.constructor,`__registry`)){this.registry=mo()?new CustomElementRegistry:customElements;for(let[e,n]of Object.entries(t??{}))this.defineScopedElement(e,n)}return Element.prototype.attachShadow.call(this,{...e,customElements:this.registry,registry:this.registry})}createRenderRoot(){let{shadowRootOptions:e,elementStyles:t}=this.constructor,n=this.attachShadow(e);return mo()&&(this.renderOptions.creationScope=n),n instanceof ShadowRoot&&(_(n,t),this.renderOptions.renderBefore=this.renderOptions.renderBefore||n.firstChild),n}}),go=class{constructor(){this.__running=!1,this.__queue=[]}add(e){this.__queue.push(e),this.__running||(this.complete=new Promise(e=>{this.__callComplete=e}),this.__run())}async __run(){this.__running=!0,await this.__queue[0](),this.__queue.shift(),this.__queue.length>0?this.__run():(this.__running=!1,this.__callComplete&&this.__callComplete())}};function _o(e){return e.charAt(0).toUpperCase()+e.slice(1)}var vo=Or(e=>class extends e{constructor(){super(),this.__SyncUpdatableNamespace={}}firstUpdated(e){super.firstUpdated(e),this.__syncUpdatableInitialize()}connectedCallback(){super.connectedCallback(),this.__SyncUpdatableNamespace.connected=!0}disconnectedCallback(){super.disconnectedCallback(),this.__SyncUpdatableNamespace.connected=!1}static enabledWarnings=super.enabledWarnings?.filter(e=>e!==`change-in-update`)||[];static __syncUpdatableHasChanged(e,t,n){let r=this.elementProperties;return r.get(e)&&r.get(e).hasChanged?r.get(e).hasChanged(t,n):t!==n}__syncUpdatableInitialize(){let e=this.__SyncUpdatableNamespace,t=this.constructor;e.initialized=!0,e.queue&&Array.from(e.queue).forEach(e=>{t.__syncUpdatableHasChanged(e,this[e],void 0)&&this.updateSync(e,void 0)})}requestUpdate(e,t,n){if(super.requestUpdate(e,t,n),e===void 0)return;this.__SyncUpdatableNamespace=this.__SyncUpdatableNamespace||{};let r=this.__SyncUpdatableNamespace,i=this.constructor;r.initialized?i.__syncUpdatableHasChanged(e,this[e],t)&&this.updateSync(e,t):(r.queue=r.queue||new Set,r.queue.add(e))}updateSync(e,t){}}),yo=e=>{switch(e){case`bg-BG`:return j(()=>import(`./bg-BG.js`),__vite__mapDeps([0,1]),import.meta.url);case`bg`:return j(()=>import(`./bg2.js`),[],import.meta.url);case`cs-CZ`:return j(()=>import(`./cs-CZ.js`),__vite__mapDeps([2,3]),import.meta.url);case`cs`:return j(()=>import(`./cs2.js`),[],import.meta.url);case`de-DE`:return j(()=>import(`./de-DE.js`),__vite__mapDeps([4,5]),import.meta.url);case`de`:return j(()=>import(`./de2.js`),[],import.meta.url);case`en-AU`:return j(()=>import(`./en-AU.js`),__vite__mapDeps([6,7]),import.meta.url);case`en-GB`:return j(()=>import(`./en-GB.js`),__vite__mapDeps([8,7]),import.meta.url);case`en-US`:return j(()=>import(`./en-US.js`),__vite__mapDeps([9,7]),import.meta.url);case`en-PH`:case`en`:return j(()=>import(`./en2.js`),[],import.meta.url);case`es-ES`:return j(()=>import(`./es-ES.js`),__vite__mapDeps([10,11]),import.meta.url);case`es`:return j(()=>import(`./es2.js`),[],import.meta.url);case`fr-FR`:return j(()=>import(`./fr-FR.js`),__vite__mapDeps([12,13]),import.meta.url);case`fr-BE`:return j(()=>import(`./fr-BE.js`),__vite__mapDeps([14,13]),import.meta.url);case`fr`:return j(()=>import(`./fr2.js`),[],import.meta.url);case`hu-HU`:return j(()=>import(`./hu-HU.js`),__vite__mapDeps([15,16]),import.meta.url);case`hu`:return j(()=>import(`./hu2.js`),[],import.meta.url);case`it-IT`:return j(()=>import(`./it-IT.js`),__vite__mapDeps([17,18]),import.meta.url);case`it`:return j(()=>import(`./it2.js`),[],import.meta.url);case`nl-BE`:return j(()=>import(`./nl-BE.js`),__vite__mapDeps([19,20]),import.meta.url);case`nl-NL`:return j(()=>import(`./nl-NL.js`),__vite__mapDeps([21,20]),import.meta.url);case`nl`:return j(()=>import(`./nl2.js`),[],import.meta.url);case`pl-PL`:return j(()=>import(`./pl-PL.js`),__vite__mapDeps([22,23]),import.meta.url);case`pl`:return j(()=>import(`./pl2.js`),[],import.meta.url);case`ro-RO`:return j(()=>import(`./ro-RO.js`),__vite__mapDeps([24,25]),import.meta.url);case`ro`:return j(()=>import(`./ro2.js`),[],import.meta.url);case`ru-RU`:return j(()=>import(`./ru-RU.js`),__vite__mapDeps([26,27]),import.meta.url);case`ru`:return j(()=>import(`./ru2.js`),[],import.meta.url);case`sk-SK`:return j(()=>import(`./sk-SK.js`),__vite__mapDeps([28,29]),import.meta.url);case`sk`:return j(()=>import(`./sk2.js`),[],import.meta.url);case`tr-TR`:return j(()=>import(`./tr-TR.js`),__vite__mapDeps([30,31]),import.meta.url);case`tr`:return j(()=>import(`./tr.js`),[],import.meta.url);case`uk-UA`:return j(()=>import(`./uk-UA.js`),__vite__mapDeps([32,33]),import.meta.url);case`uk`:return j(()=>import(`./uk2.js`),[],import.meta.url);case`zh-CN`:case`zh`:return j(()=>import(`./zh2.js`),[],import.meta.url);default:return j(()=>import(`./en2.js`),[],import.meta.url)}},bo=e=>`${e[0].toUpperCase()}${e.slice(1)}`,xo=class extends co(C){static get properties(){return{feedbackData:{attribute:!1}}}static localizeNamespaces=[{"lion-form-core":yo},...super.localizeNamespaces];static get styles(){return[v`
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
      `]}constructor(){super(),this.feedbackData=void 0}_messageTemplate({message:e}){return e}updated(e){super.updated(e),this.feedbackData&&this.feedbackData[0]?(this.setAttribute(`type`,this.feedbackData[0].type),this.currentType=this.feedbackData[0].type):this.currentType!==`success`&&this.removeAttribute(`type`)}render(){return g`
      ${this.feedbackData&&this.feedbackData.map(({message:e,type:t,validator:n})=>g`
          <div class="validation-feedback__type">
            ${e&&t?this._localizeManager.msg(`lion-form-core:validation${bo(t)}`):S}
          </div>
          ${this._messageTemplate({message:e,type:t,validator:n})}
        `)}
    `}},So=class{constructor(e){this.type=`unparseable`,this.viewValue=e}toString(){return JSON.stringify({type:this.type,viewValue:this.viewValue})}},Co=[Node.DOCUMENT_POSITION_PRECEDING,Node.DOCUMENT_POSITION_CONTAINS,Node.DOCUMENT_POSITION_CONTAINS|Node.DOCUMENT_POSITION_PRECEDING];function wo(e,{reverse:t}={}){let n=(e,t)=>{let n=e.compareDocumentPosition(t);return Co.includes(n)?1:-1},r=e.filter(e=>e);return r.sort(n),t&&r.reverse(),r}var To=Or(e=>class extends e{constructor(){super(),this.name=``,this._parentFormGroup=void 0,this.allowCrossRootRegistration=!1}get name(){return this.__name||``}set name(e){let t=this.name;this.__name=e.toString(),this.requestUpdate(`name`,t)}static get properties(){return{name:{type:String,reflect:!0},allowCrossRootRegistration:{type:Boolean,attribute:`allow-cross-root-registration`}}}connectedCallback(){super.connectedCallback(),this.dispatchEvent(new CustomEvent(`form-element-register`,{detail:{element:this},bubbles:!0,composed:!!this.allowCrossRootRegistration}))}disconnectedCallback(){super.disconnectedCallback(),this.__unregisterFormElement()}__unregisterFormElement(){this._parentFormGroup&&this._parentFormGroup.removeFormElement(this)}}),Eo=Or(e=>class extends To(kr(Ur(e))){static get properties(){return{readOnly:{type:Boolean,attribute:`readonly`,reflect:!0},label:String,labelSrOnly:{type:Boolean,attribute:`label-sr-only`,reflect:!0},helpText:{type:String,attribute:`help-text`},modelValue:{attribute:!1},_ariaLabelledNodes:{attribute:!1},_ariaDescribedNodes:{attribute:!1},_repropagationRole:{attribute:!1},_isRepropagationEndpoint:{attribute:!1}}}get label(){return this.__label??(this._labelNode?.textContent||``)}set label(e){let t=this.label;this.__label=e,this.requestUpdate(`label`,t)}get helpText(){return this.__helpText??(this._helpTextNode?.textContent||``)}set helpText(e){let t=this.helpText;this.__helpText=e,this.requestUpdate(`helpText`,t)}get fieldName(){return this.__fieldName||this.label||this.name||``}set fieldName(e){this.__fieldName=e}get slots(){return{...super.slots,label:()=>{let e=document.createElement(`label`);return e.textContent=this.label,e},"help-text":()=>{let e=document.createElement(`div`);return e.textContent=this.helpText,e}}}get _inputNode(){return this.__getDirectSlotChild(`input`)}get _labelNode(){return this.__getDirectSlotChild(`label`)}get _helpTextNode(){return this.__getDirectSlotChild(`help-text`)}get _feedbackNode(){return this.__getDirectSlotChild(`feedback`)}static enabledWarnings=super.enabledWarnings?.filter(e=>e!==`change-in-update`)||[];constructor(){super(),this.readOnly=!1,this.labelSrOnly=!1,this._inputId=Kr(this.localName),this._ariaLabelledNodes=[],this._ariaDescribedNodes=[],this._repropagationRole=`child`,this._isRepropagationEndpoint=!1,this.addEventListener(`model-value-changed`,this.__repropagateChildrenValues),this._onLabelClick=this._onLabelClick.bind(this)}connectedCallback(){super.connectedCallback(),this._enhanceLightDomClasses(),this._enhanceLightDomA11y(),this._triggerInitialModelValueChangedEvent(),this._labelNode&&this._labelNode.addEventListener(`click`,this._onLabelClick)}disconnectedCallback(){super.disconnectedCallback(),this._labelNode&&this._labelNode.removeEventListener(`click`,this._onLabelClick)}updated(e){super.updated(e),e.has(`disabled`)&&this._inputNode?.setAttribute(`aria-disabled`,`${!!this.disabled}`),e.has(`_ariaLabelledNodes`)&&this.__reflectAriaAttr(`aria-labelledby`,this._ariaLabelledNodes,this.__reorderAriaLabelledNodes),e.has(`_ariaDescribedNodes`)&&this.__reflectAriaAttr(`aria-describedby`,this._ariaDescribedNodes,this.__reorderAriaDescribedNodes),e.has(`label`)&&this.__label!==void 0&&this._labelNode&&(this._labelNode.textContent=this.label),e.has(`helpText`)&&this.__helpText!==void 0&&this._helpTextNode&&(this._helpTextNode.textContent=this.helpText),e.has(`name`)&&this.dispatchEvent(new CustomEvent(`form-element-name-changed`,{detail:{oldName:e.get(`name`),newName:this.name},bubbles:!0}))}_triggerInitialModelValueChangedEvent(){this._dispatchInitialModelValueChangedEvent()}_enhanceLightDomClasses(){this._inputNode&&this._inputNode.classList.add(`form-control`)}_enhanceLightDomA11y(){let{_inputNode:e,_labelNode:t,_helpTextNode:n,_feedbackNode:r}=this;e&&(e.id=e.id||this._inputId),t&&(t.setAttribute(`for`,this._inputId),this.addToAriaLabelledBy(t,{idPrefix:`label`})),n&&this.addToAriaDescribedBy(n,{idPrefix:`help-text`}),r&&(this.addEventListener(`focusin`,()=>{r.setAttribute(`aria-live`,`polite`)}),this.addEventListener(`focusout`,()=>{r.setAttribute(`aria-live`,`assertive`)}),this.addToAriaDescribedBy(r,{idPrefix:`feedback`})),this._enhanceLightDomA11yForAdditionalSlots()}_enhanceLightDomA11yForAdditionalSlots(e=[`prefix`,`suffix`,`before`,`after`]){e.forEach(e=>{let t=this.__getDirectSlotChild(e);t&&(t.hasAttribute(`data-label`)&&this.addToAriaLabelledBy(t,{idPrefix:e}),t.hasAttribute(`data-description`)&&this.addToAriaDescribedBy(t,{idPrefix:e}))})}__reflectAriaAttr(e,t,n){if(this._inputNode){if(n){let e=t.filter(e=>this.contains(e)),n=t.filter(e=>!this.contains(e)),r=[...wo(e.map(e=>e.assignedSlot||e))],i=[];r.forEach(t=>{e.forEach(e=>{t.name===e.slot&&i.push(e)})}),t=[...i,...n]}let r=t.map(e=>e.id).join(` `);this._inputNode.setAttribute(e,r)}}render(){return g`
        <div class="form-field__group-one">${this._groupOneTemplate()}</div>
        <div class="form-field__group-two">${this._groupTwoTemplate()}</div>
      `}_groupOneTemplate(){return g` ${this._labelTemplate()} ${this._helpTextTemplate()} `}_groupTwoTemplate(){return g` ${this._inputGroupTemplate()} ${this._feedbackTemplate()} `}_labelTemplate(){return g`
        <div class="form-field__label">
          <slot name="label"></slot>
        </div>
      `}_helpTextTemplate(){return g`
        <small class="form-field__help-text">
          <slot name="help-text"></slot>
        </small>
      `}_inputGroupTemplate(){return g`
        <div class="input-group">
          ${this._inputGroupBeforeTemplate()}
          <div class="input-group__container">
            ${this._inputGroupPrefixTemplate()} ${this._inputGroupInputTemplate()}
            ${this._inputGroupSuffixTemplate()}
          </div>
          ${this._inputGroupAfterTemplate()}
        </div>
      `}_inputGroupBeforeTemplate(){return g`
        <div class="input-group__before">
          <slot name="before"></slot>
        </div>
      `}_inputGroupPrefixTemplate(){return Array.from(this.children).find(e=>e.slot===`prefix`)?g`
            <div class="input-group__prefix">
              <slot name="prefix"></slot>
            </div>
          `:S}_inputGroupInputTemplate(){return g`
        <div class="input-group__input">
          <slot name="input"></slot>
        </div>
      `}_inputGroupSuffixTemplate(){return Array.from(this.children).find(e=>e.slot===`suffix`)?g`
            <div class="input-group__suffix">
              <slot name="suffix"></slot>
            </div>
          `:S}_inputGroupAfterTemplate(){return g`
        <div class="input-group__after">
          <slot name="after"></slot>
        </div>
      `}_feedbackTemplate(){return g`
        <div class="form-field__feedback">
          <slot name="feedback"></slot>
        </div>
      `}_isEmpty(e=this.modelValue){let t=e;return this.modelValue instanceof So&&(t=this.modelValue.viewValue),typeof t==`object`&&t&&!(t instanceof Date)?!Object.keys(t).length:!t&&!(typeof t==`number`&&(t===0||Number.isNaN(t)))&&!(typeof t==`boolean`&&t===!1)}static get styles(){return[v`
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
        `]}_getAriaDescriptionElements(){return[this._helpTextNode,this._feedbackNode]}addToAriaLabelledBy(e,{idPrefix:t=``,reorder:n=!0}={}){e.id=e.id||`${t}-${this._inputId}`,this._ariaLabelledNodes.includes(e)||(this._ariaLabelledNodes=[...this._ariaLabelledNodes,e],this.__reorderAriaLabelledNodes=!!n)}removeFromAriaLabelledBy(e){this._ariaLabelledNodes.includes(e)&&(this._ariaLabelledNodes.splice(this._ariaLabelledNodes.indexOf(e),1),this._ariaLabelledNodes=[...this._ariaLabelledNodes],this.__reorderAriaLabelledNodes=!1)}addToAriaDescribedBy(e,{idPrefix:t=``,reorder:n=!0}={}){e.id=e.id||`${t}-${this._inputId}`,this._ariaDescribedNodes.includes(e)||(this._ariaDescribedNodes=[...this._ariaDescribedNodes,e],this.__reorderAriaDescribedNodes=!!n)}removeFromAriaDescribedBy(e){this._ariaDescribedNodes.includes(e)&&(this._ariaDescribedNodes.splice(this._ariaDescribedNodes.indexOf(e),1),this._ariaDescribedNodes=[...this._ariaDescribedNodes],this.__reorderAriaLabelledNodes=!1)}__getDirectSlotChild(e){return Array.from(this.children).find(t=>t.slot===e)}_dispatchInitialModelValueChangedEvent(){this._repropagationRole!==`child`&&(this.__repropagateChildrenInitialized=!0,this.dispatchEvent(new CustomEvent(`model-value-changed`,{bubbles:!0,detail:{formPath:[this],initialize:!0,isTriggeredByUser:!1}})))}_onBeforeRepropagateChildrenValues(e){}__repropagateChildrenValues(e){this._onBeforeRepropagateChildrenValues(e);let t=e.detail&&e.detail.element||e.target,n=this._isRepropagationEndpoint||this._repropagationRole===`choice-group`;if(t===this)return;e.stopImmediatePropagation();let r=this._repropagationRole!==`child`&&!this.__repropagateChildrenInitialized,i=e.detail&&e.detail.initialize;if(r||i||!this._repropagationCondition(t))return;let a=[];n||(a=e.detail&&e.detail.formPath||[t]);let o=[...a,this];this.dispatchEvent(new CustomEvent(`model-value-changed`,{bubbles:!0,detail:{formPath:o,isTriggeredByUser:!!e.detail?.isTriggeredByUser}}))}_repropagationCondition(e){return!!e}_onLabelClick(){}}),Do=class extends EventTarget{constructor(e,t){super(),this.__param=e,this.__config=t||{},this.type=t?.type||`error`}static _$isValidator$=!0;static validatorName=``;static async=!1;execute(e,t,n){if(!this.constructor.validatorName)throw Error(`A validator needs to have a name! Please set it via "static get validatorName() { return 'IsCat'; }"`);return!0}set param(e){this.__param=e,this.dispatchEvent(new Event(`param-changed`))}get param(){return this.__param}set config(e){this.__config=e,this.dispatchEvent(new Event(`config-changed`))}get config(){return this.__config}async _getMessage(e){let t=this.constructor,n={name:t.validatorName,type:this.type,params:this.param,config:this.config,...e};if(this.config.getMessage){if(typeof this.config.getMessage==`function`)return this.config.getMessage(n);throw Error(`You must provide a value for getMessage of type 'function', you provided a value of type: ${typeof this.config.getMessage}`)}return t.getMessage(n)}static async getMessage(e){return`Please configure an error message for "${this.name}" by overriding "static async getMessage()"`}onFormControlConnect(e){}onFormControlDisconnect(e){}abortExecution(){}};function Oo(e=[],t=[]){return e.filter(e=>!t.includes(e)).concat(t.filter(t=>!e.includes(t)))}function ko(e){return e instanceof So?e.viewValue:e}var Ao=Or(e=>class extends Eo(vo(kr(Ur(ho(e))))){static get scopedElements(){return{...super.scopedElements,"lion-validation-feedback":xo}}static get properties(){return{validators:{attribute:!1},hasFeedbackFor:{attribute:!1},shouldShowFeedbackFor:{attribute:!1},showsFeedbackFor:{type:Array,attribute:`shows-feedback-for`,reflect:!0,converter:{fromAttribute:e=>e.split(`,`),toAttribute:e=>e.join(`,`)}},validationStates:{attribute:!1},isPending:{type:Boolean,attribute:`is-pending`,reflect:!0},defaultValidators:{attribute:!1},_visibleMessagesAmount:{attribute:!1},__childModelValueChanged:{attribute:!1}}}static get validationTypes(){return[`error`]}get operationMode(){return`enter`}get slots(){return{...super.slots,feedback:()=>{let e=this.createScopedElement(`lion-validation-feedback`);return e.setAttribute(`data-tag-name`,`lion-validation-feedback`),e}}}get _allValidators(){return[...this.validators,...this.defaultValidators]}constructor(){super(),this.hasFeedbackFor=[],this.showsFeedbackFor=[],this.shouldShowFeedbackFor=[],this.validationStates={},this.isPending=!1,this.validators=[],this.defaultValidators=[],this._visibleMessagesAmount=1,this.__syncValidationResult=[],this.__asyncValidationResult=[],this.__validationResult=[],this.__prevValidationResult=[],this.__prevShownValidationResult=[],this.__childModelValueChanged=!1,this._onValidatorUpdated=this._onValidatorUpdated.bind(this),this._updateFeedbackComponent=this._updateFeedbackComponent.bind(this)}connectedCallback(){super.connectedCallback(),Ya().addEventListener(`localeChanged`,this._updateFeedbackComponent)}disconnectedCallback(){super.disconnectedCallback(),Ya().removeEventListener(`localeChanged`,this._updateFeedbackComponent)}firstUpdated(e){super.firstUpdated(e),this.__isValidateInitialized=!0,this.validate(),this._repropagationRole!==`child`&&this.addEventListener(`model-value-changed`,()=>{this.__childModelValueChanged=!0})}updateSync(e,t){if(super.updateSync(e,t),e===`validators`?(this.__setupValidators(),this.validate({clearCurrentResult:!0})):e===`modelValue`&&this.validate({clearCurrentResult:!0}),[`touched`,`dirty`,`prefilled`,`focused`,`submitted`,`hasFeedbackFor`,`filled`].includes(e)&&this._updateShouldShowFeedbackFor(),e===`showsFeedbackFor`){this._inputNode&&this._inputNode.setAttribute(`aria-invalid`,`${this._hasFeedbackVisibleFor(`error`)}`);let e=Oo(this.showsFeedbackFor,t);e.length>0&&this.dispatchEvent(new Event(`showsFeedbackForChanged`,{bubbles:!0})),e.forEach(e=>{this.dispatchEvent(new Event(`showsFeedbackFor${_o(e)}Changed`,{bubbles:!0}))})}e===`shouldShowFeedbackFor`&&Oo(this.shouldShowFeedbackFor,t).length>0&&this.dispatchEvent(new Event(`shouldShowFeedbackForChanged`,{bubbles:!0}))}async validate({clearCurrentResult:e=!1}={}){if(this.validateComplete=new Promise(e=>{this.__validateCompleteResolve=e}),this.disabled){this.__clearValidationResults(),this.__finishValidationPass(),this._updateFeedbackComponent();return}this.__isValidateInitialized&&(this.__prevValidationResult=this.__validationResult,e&&this.__clearValidationResults(),await this.__executeValidators())}#e(e){let t=e;for(;t;){if(t.constructor.validatorName===`Required`)return!0;t=Object.getPrototypeOf(t)}return!1}async __executeValidators(){let e=ko(this.modelValue),t=this.__isEmpty(e);if(this.__syncValidationResult=[],t){let e=!this._isFormOrFieldset,t=this._allValidators.find(e=>e.constructor?.validatorName===`Required`);if(t&&(this.__syncValidationResult=[{validator:t,outcome:!0}]),e){this.__finishValidationPass({syncValidationResult:this.__syncValidationResult});return}}let n=[],r=[],i=[];for(let e of this._allValidators)e?.executeOnResults?n.push(e):this.#e(e)||(e.constructor.async?i.push(e):r.push(e));let a=!!i.length;this.__syncValidationResult=[...this.__syncValidationResult,...this.__executeSyncValidators(r,e)],this.__finishValidationPass({syncValidationResult:this.__syncValidationResult,metaValidators:n}),a?(this.isPending=!0,this.__asyncValidationResult=await this.__executeAsyncValidators(i,e),this.isPending=!1,this.__finishValidationPass({syncValidationResult:this.__syncValidationResult,asyncValidationResult:this.__asyncValidationResult,metaValidators:n}),this.__validateCompleteResolve?.(!0)):this.__validateCompleteResolve?.(!0)}__executeSyncValidators(e,t){return e.map(e=>({validator:e,outcome:e.execute(t,e.param,{node:this})})).filter(e=>!!e.outcome)}async __executeAsyncValidators(e,t){let n=e.map(e=>e.execute(t,e.param,{node:this})),r=await Promise.all(n);return r.map((t,n)=>({validator:e[n],outcome:r[n]})).filter(e=>!!e.outcome)}__executeMetaValidators(e,t){return t.length?this._isEmpty(this.modelValue)?(this.__prevShownValidationResult=[],[]):t.map(t=>({validator:t,outcome:t.executeOnResults({regularValidationResult:e.map(e=>e.validator),prevValidationResult:this.__prevValidationResult.map(e=>e.validator),prevShownValidationResult:this.__prevShownValidationResult.map(e=>e.validator)})})).filter(e=>!!e.outcome):[]}__finishValidationPass({syncValidationResult:e=[],asyncValidationResult:t=[],metaValidators:n=[]}={}){let r=[...e,...t];this.__validationResult=[...this.__executeMetaValidators(r,n),...r];let i=this.constructor.validationTypes.reduce((e,t)=>({...e,[t]:{}}),{});for(let{validator:e,outcome:t}of this.__validationResult){i[e.type]||(i[e.type]={});let n=e.constructor;i[e.type][n.validatorName]=t}this.validationStates=i,this.hasFeedbackFor=[...new Set(this.__validationResult.map(({validator:e})=>e.type))],this.dispatchEvent(new Event(`validate-performed`,{bubbles:!0}))}__clearValidationResults(){this.__syncValidationResult=[],this.__asyncValidationResult=[]}_onValidatorUpdated(e){(e.type===`param-changed`||e.type===`config-changed`)&&this.validate()}__setupValidators(){let e=[`param-changed`,`config-changed`];for(let t of this.__prevValidators||[]){for(let n of e)t.removeEventListener?.(n,this._onValidatorUpdated);t.onFormControlDisconnect(this)}for(let t of this._allValidators){if(t.constructor._$isValidator$===void 0){let e=`Validators array only accepts class instances of Validator. Type "${Array.isArray(t)?`array`:typeof t}" found. This may be caused by having multiple installations of "@lion/ui/form-core.js".`;throw console.error(e,this),Error(e)}let n=this.constructor,r=t.constructor;if(n.validationTypes.indexOf(t.type)===-1){let e=`This component does not support the validator type "${t.type}" used in "${r.validatorName}". You may change your validators type or add it to the components "static get validationTypes() {}".`;throw console.error(e,this),Error(e)}for(let n of e)t.addEventListener?.(n,e=>{this._onValidatorUpdated(e,{validator:t})});t.onFormControlConnect(this)}this.__prevValidators=this._allValidators}__isEmpty(e){return typeof this._isEmpty==`function`?this._isEmpty(e):this.modelValue===null||this.modelValue===void 0||this.modelValue===``}async __getFeedbackMessages(e){let t=await this.fieldName;return Promise.all(e.map(async({validator:e,outcome:n})=>(e.config.fieldName&&(t=await e.config.fieldName),{message:await e._getMessage({modelValue:this.modelValue,formControl:this,fieldName:t,outcome:n}),type:e.type,validator:e,visibilityDuration:e.config?.visibilityDuration||3e3})))}_updateFeedbackComponent(){window.clearTimeout(this.removeMessage);let{_feedbackNode:e}=this;e&&(this.__feedbackQueue||=new go,this.showsFeedbackFor.length>0?this.__feedbackQueue.add(async()=>{this.__prioritizedResult=this._prioritizeAndFilterFeedback({validationResult:this.__validationResult.map(e=>e.validator)}).map(e=>this.__validationResult.find(t=>e===t.validator)).filter(Boolean),this.__prioritizedResult.length>0&&(this.__prevShownValidationResult=this.__prioritizedResult);let t=await this.__getFeedbackMessages(this.__prioritizedResult);e.feedbackData=t||[],t?.[0]&&t[0].type===`success`&&t[0].visibilityDuration!==1/0&&(this.removeMessage=window.setTimeout(()=>{e.removeAttribute(`type`),e.feedbackData=[]},t[0].visibilityDuration))}):this.__feedbackQueue.add(async()=>{e.feedbackData=[]}),this.feedbackComplete=this.__feedbackQueue.complete)}_showFeedbackConditionFor(e,t){return!0}get _feedbackConditionMeta(){return{modelValue:this.modelValue,el:this}}feedbackCondition(e,t=this._feedbackConditionMeta,n=this._showFeedbackConditionFor.bind(this)){return n(e,t)}_hasFeedbackVisibleFor(e){return this.hasFeedbackFor?.includes(e)&&this.shouldShowFeedbackFor?.includes(e)}updated(e){if(super.updated(e),(e.has(`shouldShowFeedbackFor`)||e.has(`hasFeedbackFor`))&&(this.showsFeedbackFor=this.constructor.validationTypes.map(e=>this._hasFeedbackVisibleFor(e)?e:void 0).filter(Boolean),this._updateFeedbackComponent()),e.has(`__childModelValueChanged`)&&this.__childModelValueChanged&&(this.validate({clearCurrentResult:!0}),this.__childModelValueChanged=!1),e.has(`validationStates`)){let t=e.get(`validationStates`);t&&Object.entries(this.validationStates).forEach(([e,n])=>{t[e]&&JSON.stringify(n)!==JSON.stringify(t[e])&&this.dispatchEvent(new CustomEvent(`${e}StateChanged`,{detail:n}))})}}_updateShouldShowFeedbackFor(){let e=this.constructor.validationTypes.map(e=>this.feedbackCondition(e,this._feedbackConditionMeta,this._showFeedbackConditionFor.bind(this))?e:void 0).filter(Boolean);JSON.stringify(this.shouldShowFeedbackFor)!==JSON.stringify(e)&&(this.shouldShowFeedbackFor=e)}_prioritizeAndFilterFeedback({validationResult:e}){let t=this.constructor.validationTypes;return e.filter(e=>this.feedbackCondition(e.type,this._feedbackConditionMeta,this._showFeedbackConditionFor.bind(this))).sort((e,n)=>t.indexOf(e.type)-t.indexOf(n.type)).slice(0,this._visibleMessagesAmount)}}),jo=Or(e=>class extends Ao(Eo(e)){static get properties(){return{formattedValue:{attribute:!1},serializedValue:{attribute:!1},formatOptions:{attribute:!1}}}#e={didFormatterOutputSyncToView:!1,didFormatterRun:!1};requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`modelValue`&&this.modelValue!==t&&this._onModelValueChanged({modelValue:this.modelValue},{modelValue:t}),e===`serializedValue`&&this.serializedValue!==t&&this._calculateValues({source:`serialized`}),e===`formattedValue`&&this.formattedValue!==t&&this._calculateValues({source:`formatted`})}get value(){return this._inputNode?.value||this.__value||``}set value(e){this._inputNode?(this._inputNode.value=e,this.__value=void 0):this.__value=e}preprocessor(e,t){}parser(e,t){return e}formatter(e,t){return e}serializer(e){return e===void 0?``:e}deserializer(e){return e===void 0?``:e}_calculateValues({source:e}={source:null}){this.__preventRecursiveTrigger||(this.__preventRecursiveTrigger=!0,e!==`model`&&(e===`serialized`?this.modelValue=this.deserializer(this.serializedValue):e===`formatted`&&(this.modelValue=this._callParser())),e!==`formatted`&&(this.formattedValue=this._callFormatter()),e!==`serialized`&&(this.serializedValue=this.serializer(this.modelValue)),this._reflectBackFormattedValueToUser(),this.__preventRecursiveTrigger=!1,this.__prevViewValue=this.value)}_callParser(e=this.formattedValue){if(e===``)return``;if(typeof e!=`string`)return;let t=this.parser(e,{...this.formatOptions,mode:this.#t(),viewValueStates:this.#n()});return t===void 0?new So(e):t}_callFormatter(){return this.#e.didFormatterRun=!1,this._isHandlingUserInput&&this.hasFeedbackFor?.includes(`error`)&&this._inputNode?this.value:this.modelValue instanceof So?this.modelValue.viewValue:(this.#e.didFormatterRun=!0,this.formatter(this.modelValue,{...this.formatOptions,mode:this.#t(),viewValueStates:this.#n()}))}_onModelValueChanged(...e){this._calculateValues({source:`model`}),this._dispatchModelValueChangedEvent(...e)}_dispatchModelValueChangedEvent(...e){this.dispatchEvent(new CustomEvent(`model-value-changed`,{bubbles:!0,detail:{formPath:[this],isTriggeredByUser:!!this._isHandlingUserInput}}))}_syncValueUpwards(){this.__isHandlingComposition||this.__handlePreprocessor();let e=this.formattedValue;this.modelValue=this._callParser(this.value),e===this.formattedValue&&this.__prevViewValue!==this.value&&this._calculateValues()}__handlePreprocessor(){let e=this.value.length;this._inputNode&&`selectionStart`in this._inputNode&&this._inputNode?.type!==`range`&&(e=this._inputNode.selectionStart);let t=this.preprocessor(this.value,{...this.formatOptions,currentCaretIndex:e,prevViewValue:this.__prevViewValue});if(t!==void 0){if(typeof t==`string`)this.value=t;else if(typeof t==`object`){let{viewValue:e,caretIndex:n}=t;this.value=e,n&&this._inputNode&&`selectionStart`in this._inputNode&&(this._inputNode.selectionStart=n,this._inputNode.selectionEnd=n)}}}_reflectBackFormattedValueToUser(){this._reflectBackOn()&&(this.value=this.formattedValue===void 0?``:this.formattedValue,this.#e.didFormatterOutputSyncToView=!!this.formattedValue&&this.#e.didFormatterRun)}_reflectBackOn(){return!this._isHandlingUserInput}_proxyInputEvent(){this.dispatchEvent(new Event(`user-input-changed`,{bubbles:!0}))}_onUserInputChanged(){this._isHandlingUserInput=!0,this._syncValueUpwards(),this._isHandlingUserInput=!1}__onCompositionEvent({type:e}){e===`compositionstart`?this.__isHandlingComposition=!0:e===`compositionend`&&(this.__isHandlingComposition=!1,this._syncValueUpwards())}constructor(){super(),this.formatOn=`change`,this.formatOptions={mode:`auto`},this.formattedValue=void 0,this.serializedValue=void 0,this._isPasting=!1,this._isHandlingUserInput=!1,this.__prevViewValue=``,this.__onCompositionEvent=this.__onCompositionEvent.bind(this),this.addEventListener(`user-input-changed`,this._onUserInputChanged),this.addEventListener(`paste`,this.__onPaste),this._reflectBackFormattedValueToUser=this._reflectBackFormattedValueToUser.bind(this),this._reflectBackFormattedValueDebounced=()=>{setTimeout(this._reflectBackFormattedValueToUser)}}__onPaste(){this._isPasting=!0,setTimeout(()=>{this._isPasting=!1})}connectedCallback(){super.connectedCallback(),this.modelValue===void 0&&this._syncValueUpwards(),this.__prevViewValue=this.value,this._reflectBackFormattedValueToUser(),this._inputNode&&(this._inputNode.addEventListener(this.formatOn,this._reflectBackFormattedValueDebounced),this._inputNode.addEventListener(`input`,this._proxyInputEvent),this._inputNode.addEventListener(`compositionstart`,this.__onCompositionEvent),this._inputNode.addEventListener(`compositionend`,this.__onCompositionEvent))}disconnectedCallback(){super.disconnectedCallback(),this._inputNode&&(this._inputNode.removeEventListener(`input`,this._proxyInputEvent),this._inputNode.removeEventListener(this.formatOn,this._reflectBackFormattedValueDebounced),this._inputNode.removeEventListener(`compositionstart`,this.__onCompositionEvent),this._inputNode.removeEventListener(`compositionend`,this.__onCompositionEvent))}#t(){return this._isPasting?`pasted`:this._isHandlingUserInput&&this.__prevViewValue?`user-edited`:`auto`}#n(){let e=[];return this.#e.didFormatterOutputSyncToView&&e.push(`formatted`),e}}),Mo=Or(e=>class extends Eo(e){static get properties(){return{touched:{type:Boolean,reflect:!0},dirty:{type:Boolean,reflect:!0},filled:{type:Boolean,reflect:!0},prefilled:{attribute:!1},submitted:{attribute:!1}}}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`touched`&&this.touched!==t&&this._onTouchedChanged(),e===`modelValue`&&(this.filled=!this._isEmpty()),e===`dirty`&&this.dirty!==t&&this._onDirtyChanged()}constructor(){super(),this.touched=!1,this.dirty=!1,this.prefilled=!1,this.filled=!1,this._leaveEvent=`blur`,this._valueChangedEvent=`model-value-changed`,this._iStateOnLeave=this._iStateOnLeave.bind(this),this._iStateOnValueChange=this._iStateOnValueChange.bind(this)}connectedCallback(){super.connectedCallback(),this.addEventListener(this._leaveEvent,this._iStateOnLeave),this.addEventListener(this._valueChangedEvent,this._iStateOnValueChange),this.initInteractionState()}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener(this._leaveEvent,this._iStateOnLeave),this.removeEventListener(this._valueChangedEvent,this._iStateOnValueChange)}initInteractionState(){this.dirty=!1,this.prefilled=!this._isEmpty()}_iStateOnLeave(){this.touched=!0,this.prefilled=!this._isEmpty()}_iStateOnValueChange(){this.dirty=!0}resetInteractionState(){this.touched=!1,this.submitted=!1,this.dirty=!1,this.prefilled=!this._isEmpty()}_onTouchedChanged(){this.dispatchEvent(new Event(`touched-changed`,{bubbles:!0,composed:!0}))}_onDirtyChanged(){this.dispatchEvent(new Event(`dirty-changed`,{bubbles:!0,composed:!0}))}_showFeedbackConditionFor(e,t){return t.touched&&t.dirty||t.prefilled||t.submitted}get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,submitted:this.submitted,touched:this.touched,dirty:this.dirty,filled:this.filled,prefilled:this.prefilled}}}),No=class extends Eo(Mo(Ta(jo(Ao(Ur(C)))))){firstUpdated(e){super.firstUpdated(e),this._initialModelValue=this.modelValue}connectedCallback(){super.connectedCallback(),this._onChange=this._onChange.bind(this),this._inputNode.addEventListener(`change`,this._onChange),this.classList.add(`form-field`)}disconnectedCallback(){super.disconnectedCallback(),this._inputNode?.removeEventListener(`change`,this._onChange)}resetInteractionState(){super.resetInteractionState(),this.submitted=!1}reset(){this.modelValue=this._initialModelValue,this.resetInteractionState()}clear(){this.modelValue=``}_onChange(e){this.dispatchEvent(new Event(`user-input-changed`,{bubbles:!0}))}get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,focused:this.focused}}get _focusableNode(){return this._inputNode}},Po=class extends Array{_keys(){return Object.keys(this).filter(e=>Number.isNaN(Number(e)))}},Fo=Or(e=>class extends To(e){static get properties(){return{_isFormOrFieldset:{type:Boolean}}}constructor(){super(),this.formElements=new Po,this._isFormOrFieldset=!1,this._onRequestToAddFormElement=this._onRequestToAddFormElement.bind(this),this._onRequestToChangeFormElementName=this._onRequestToChangeFormElementName.bind(this),this.addEventListener(`form-element-register`,this._onRequestToAddFormElement),this.addEventListener(`form-element-name-changed`,this._onRequestToChangeFormElementName),this.initComplete=new Promise((e,t)=>{this.__resolveInitComplete=e,this.__rejectInitComplete=t}),this.registrationComplete=new Promise((e,t)=>{this.__resolveRegistrationComplete=e,this.__rejectRegistrationComplete=t}),this.registrationComplete.done=!1,this.registrationComplete.then(()=>{this.registrationComplete.done=!0,this.__resolveInitComplete(void 0)},()=>{throw this.registrationComplete.done=!0,this.__rejectInitComplete(void 0),Error(`Registration could not finish. Please use await el.registrationComplete;`)})}connectedCallback(){super.connectedCallback(),this._completeRegistration()}_completeRegistration(){Promise.resolve().then(()=>this.__resolveRegistrationComplete(void 0))}disconnectedCallback(){super.disconnectedCallback(),this.registrationComplete.done===!1&&Promise.resolve().then(()=>{Promise.resolve().then(()=>{this.__rejectRegistrationComplete()})})}isRegisteredFormElement(e){return this.formElements.some(t=>t===e)}addFormElement(e,t){if(e._parentFormGroup=this,t>=0?this.formElements.splice(t,0,e):this.formElements.push(e),this._isFormOrFieldset){let{name:n}=e;if(n===this.name)throw console.info(`Error Node:`,e),TypeError(`You can not have the same name "${n}" as your parent`);if(n.substr(-2)===`[]`)Array.isArray(this.formElements[n])||(this.formElements[n]=new Po),t>0?this.formElements[n].splice(t,0,e):this.formElements[n].push(e);else if(!this.formElements[n])this.formElements[n]=e;else throw console.info(`Error Node:`,e),TypeError(`Name "${n}" is already registered - if you want an array add [] to the end`)}}removeFormElement(e){let t=this.formElements.indexOf(e);if(t>-1&&this.formElements.splice(t,1),this._isFormOrFieldset){let{name:t}=e;if(t.substr(-2)===`[]`&&this.formElements[t]){let n=this.formElements[t].indexOf(e);n>-1&&this.formElements[t].splice(n,1)}else this.formElements[t]&&delete this.formElements[t]}}_onRequestToAddFormElement(e){let t=e.detail.element;if(t===this||this.isRegisteredFormElement(t))return;e.stopPropagation();let n=-1;if(this.formElements&&Array.isArray(this.formElements)){for(let[e,r]of this.formElements.entries())if(!(r.compareDocumentPosition(t)&Node.DOCUMENT_POSITION_FOLLOWING)){n=e;break}}this.addFormElement(t,n)}_onRequestToChangeFormElementName(e){let t=this.formElements[e.detail.oldName];t&&(this.formElements[e.detail.newName]=t,delete this.formElements[e.detail.oldName])}_onRequestToRemoveFormElement(e){let t=e.detail.element;t!==this&&this.isRegisteredFormElement(t)&&(e.stopPropagation(),this.removeFormElement(t))}}),Io=Or(e=>class extends e{constructor(){super(),this.registrationTarget=void 0,this.__redispatchEventForFormRegistrarPortalMixin=this.__redispatchEventForFormRegistrarPortalMixin.bind(this),this.addEventListener(`form-element-register`,this.__redispatchEventForFormRegistrarPortalMixin)}__redispatchEventForFormRegistrarPortalMixin(e){if(e.stopPropagation(),!this.registrationTarget)throw Error(`A FormRegistrarPortal element requires a .registrationTarget`);this.registrationTarget.dispatchEvent(new CustomEvent(`form-element-register`,{detail:{element:e.detail.element},bubbles:!0}))}}),Lo=Or(e=>class extends jo(Ta(Eo(e))){static get properties(){return{autocomplete:{type:String,reflect:!0}}}constructor(){super(),this.autocomplete=void 0}get _inputNode(){return super._inputNode}get selectionStart(){let e=this._inputNode;return e&&e.selectionStart?e.selectionStart:0}set selectionStart(e){let t=this._inputNode;t&&t.selectionStart&&(t.selectionStart=e)}get selectionEnd(){let e=this._inputNode;return e&&e.selectionEnd?e.selectionEnd:0}set selectionEnd(e){let t=this._inputNode;t&&t.selectionEnd&&(t.selectionEnd=e)}get value(){return this._inputNode&&this._inputNode.value||this.__value||``}set value(e){this._inputNode?(this._inputNode.value!==e&&this._setValueAndPreserveCaret(e),this.__value=void 0):this.__value=e}_setValueAndPreserveCaret(e){if(this.focused)try{if(!(this._inputNode instanceof HTMLSelectElement)){let t=this._inputNode.selectionStart;this._inputNode.value=e,this._inputNode.selectionStart=t,this._inputNode.selectionEnd=t}}catch{this._inputNode.value=e}else this._inputNode.value=e}_reflectBackFormattedValueToUser(){if(super._reflectBackFormattedValueToUser(),this._reflectBackOn()&&this.focused)try{this._inputNode.selectionStart=this._inputNode.value.length}catch{}}get _focusableNode(){return this._inputNode}}),Ro=Or(e=>class extends Fo(Ao(Mo(e))){static get properties(){return{multipleChoice:{type:Boolean,attribute:`multiple-choice`}}}get modelValue(){let e=this._getCheckedElements();return this.multipleChoice?e.map(e=>e.choiceValue):e[0]?e[0].choiceValue:``}set modelValue(e){let t=(t,n)=>typeof t.choiceValue==`object`?JSON.stringify(t.choiceValue)===JSON.stringify(e):t.choiceValue===n;this.__isInitialModelValue?this.registrationComplete.then(()=>{this.__isInitialModelValue=!1,this._setCheckedElements(e,t),this.requestUpdate(`modelValue`,this._oldModelValue)}):(this._setCheckedElements(e,t),this.requestUpdate(`modelValue`,this._oldModelValue)),this._oldModelValue=this.modelValue}get serializedValue(){let e=this._getCheckedElements();return this.multipleChoice?e.map(e=>e.serializedValue.value):e[0]?e[0].serializedValue.value:``}set serializedValue(e){let t=(e,t)=>e.serializedValue.value===t;this.__isInitialSerializedValue?this.registrationComplete.then(()=>{this.__isInitialSerializedValue=!1,this._setCheckedElements(e,t),this.requestUpdate(`serializedValue`)}):(this._setCheckedElements(e,t),this.requestUpdate(`serializedValue`))}get formattedValue(){let e=this._getCheckedElements();return this.multipleChoice?e.map(e=>e.formattedValue):e[0]?e[0].formattedValue:``}set formattedValue(e){let t=(e,t)=>e.formattedValue===t;this.__isInitialFormattedValue?this.registrationComplete.then(()=>{this.__isInitialFormattedValue=!1,this._setCheckedElements(e,t)}):this._setCheckedElements(e,t)}get operationMode(){return this._repropagationRole===`choice-group`?`select`:`enter`}constructor(){super(),this.multipleChoice=!1,this._repropagationRole=`choice-group`,this.__isInitialModelValue=!0,this.__isInitialSerializedValue=!0,this.__isInitialFormattedValue=!0}connectedCallback(){super.connectedCallback(),this.registrationComplete.then(()=>{this.__isInitialModelValue=!1,this.__isInitialSerializedValue=!1,this.__isInitialFormattedValue=!1})}_completeRegistration(){Promise.resolve().then(()=>super._completeRegistration())}updated(e){super.updated(e),e.has(`name`)&&this.name!==e.get(`name`)&&this.formElements.forEach(e=>{e.name=this.name})}addFormElement(e,t){this._throwWhenInvalidChildModelValue(e),e.name=this.name,super.addFormElement(e,t)}clear(){this.multipleChoice?this.modelValue=[]:this.modelValue=``}_triggerInitialModelValueChangedEvent(){this.registrationComplete.then(()=>{this._dispatchInitialModelValueChangedEvent()})}_getFromAllFormElementsFilter(e,t){return!0}_getFromAllFormElements(e,t){let n=t||this._getFromAllFormElementsFilter;return e===`modelValue`||e===`serializedValue`||e===`formattedValue`?this[e]:this.formElements.filter(t=>n(t,e)).map(e=>e.property)}_throwWhenInvalidChildModelValue(e){if(typeof e.modelValue.checked!=`boolean`||!Object.prototype.hasOwnProperty.call(e.modelValue,`value`))throw Error(`The ${this.tagName.toLowerCase()} name="${this.name}" does not allow to register ${e.tagName.toLowerCase()} with .modelValue="${e.modelValue}" - The modelValue should represent an Object { value: "foo", checked: false }`)}_isEmpty(){return this.multipleChoice?this.modelValue.length===0:typeof this.modelValue==`string`&&this.modelValue===``||this.modelValue===void 0||this.modelValue===null}_checkSingleChoiceElements(e){let{target:t}=e;if(t.checked===!1)return;let n=t.name;this.formElements.filter(e=>e.name===n).forEach(e=>{e!==t&&(e.checked=!1)})}_getCheckedElements(){return this.formElements.filter(e=>e.checked&&!e.disabled)}_setCheckedElements(e,t){if(e==null){this.formElements.forEach(e=>e.checked=!1);return}for(let n=0;n<this.formElements.length;n+=1)if(this.multipleChoice){let t=e.includes(this.formElements[n].modelValue.value);typeof this.formElements[n].modelValue.value==`object`&&(t=e.map(e=>JSON.stringify(e)).includes(JSON.stringify(this.formElements[n].modelValue.value))),this.formElements[n].checked=t}else t(this.formElements[n],e)?this.formElements[n].checked=!0:this.formElements[n].checked=!1}__setChoiceGroupTouched(){let e=this.modelValue;e!=null&&e!==this.__previousCheckedValue&&(this.touched=!0,this.__previousCheckedValue=e)}_onBeforeRepropagateChildrenValues(e){let t=e.detail&&e.detail.element||e.target;this.multipleChoice||!t.checked||(this.formElements.forEach(e=>{t.choiceValue!==e.choiceValue&&(e.checked=!1)}),this.__setChoiceGroupTouched(),this.requestUpdate(`modelValue`,this._oldModelValue),this._oldModelValue=this.modelValue)}_repropagationCondition(e){return!(this._repropagationRole===`choice-group`&&!this.multipleChoice&&!e.checked)}}),zo=(e,t={})=>e.value!==t.value||e.checked!==t.checked,Bo=Or(e=>class extends jo(e){static get properties(){return{checked:{type:Boolean,reflect:!0},disabled:{type:Boolean,reflect:!0},modelValue:{type:Object,hasChanged:zo},choiceValue:{type:Object}}}get choiceValue(){return this.modelValue.value}set choiceValue(e){this.requestUpdate(`choiceValue`,this.choiceValue),this.modelValue.value!==e&&(this.modelValue={value:e,checked:this.modelValue.checked})}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`modelValue`?this.modelValue.checked!==this.checked&&this.__syncModelCheckedToChecked(this.modelValue.checked):e===`checked`&&this.modelValue.checked!==this.checked&&this.__syncCheckedToModel(this.checked)}firstUpdated(e){super.firstUpdated(e),e.has(`checked`)&&this.__syncCheckedToInputElement()}updated(e){super.updated(e),e.has(`modelValue`)&&this.__syncCheckedToInputElement(),e.has(`name`)&&this._parentFormGroup&&this._parentFormGroup.name!==this.name&&this._syncNameToParentFormGroup()}constructor(){super(),this.modelValue={value:``,checked:!1},this.disabled=!1,this._preventDuplicateLabelClick=this._preventDuplicateLabelClick.bind(this),this._toggleChecked=this._toggleChecked.bind(this)}static get styles(){return[...super.styles||[],v`
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
        `]}render(){return g`
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
      `}_choiceGraphicTemplate(){return S}_afterTemplate(){return S}connectedCallback(){super.connectedCallback(),this._labelNode&&this._labelNode.addEventListener(`click`,this._preventDuplicateLabelClick),this.addEventListener(`user-input-changed`,this._toggleChecked)}disconnectedCallback(){super.disconnectedCallback(),this._labelNode&&this._labelNode.removeEventListener(`click`,this._preventDuplicateLabelClick),this.removeEventListener(`user-input-changed`,this._toggleChecked)}_preventDuplicateLabelClick(e){let t=e=>{e.stopImmediatePropagation(),this._inputNode.removeEventListener(`click`,t)};this._inputNode.addEventListener(`click`,t)}_toggleChecked(e){this.disabled||(this._isHandlingUserInput=!0,this.checked=!this.checked,this._isHandlingUserInput=!1)}_syncNameToParentFormGroup(){this._parentFormGroup.tagName.includes(this.tagName)&&(this.name=this._parentFormGroup?.name||``)}__syncModelCheckedToChecked(e){this.checked=e}__syncCheckedToModel(e){this.modelValue={value:this.choiceValue,checked:e}}__syncCheckedToInputElement(){this._inputNode&&(this._inputNode.checked=this.checked)}_proxyInputEvent(){}_onModelValueChanged({modelValue:e},t){let n;t&&t.modelValue&&(n=t.modelValue),this.constructor.elementProperties.get(`modelValue`).hasChanged(e,n)&&super._onModelValueChanged({modelValue:e})}parser(){return this.modelValue}formatter(e){return e&&e.value!==void 0?e.value:e}clear(){this.checked=!1}_isEmpty(){return!this.checked}_syncValueUpwards(){}}),Vo=class extends Do{static get validatorName(){return`FormElementsHaveNoError`}execute(e,t,n){return n?.node._anyFormElementHasFeedbackFor(`error`)}static async getMessage(){return``}},Ho=Or(e=>class extends Fo(Eo(Ao(kr(Ur(e))))){static get properties(){return{submitted:{type:Boolean,reflect:!0},focused:{type:Boolean,reflect:!0},dirty:{type:Boolean,reflect:!0},touched:{type:Boolean,reflect:!0},prefilled:{type:Boolean,reflect:!0}}}get _inputNode(){return this}get modelValue(){return this._getFromAllFormElements(`modelValue`)}set modelValue(e){this.__isInitialModelValue?(this.__isInitialModelValue=!1,this.registrationComplete.then(()=>{this._setValueMapForAllFormElements(`modelValue`,e)})):this._setValueMapForAllFormElements(`modelValue`,e)}get serializedValue(){return this._getFromAllFormElements(`serializedValue`)}set serializedValue(e){this.__isInitialSerializedValue?(this.__isInitialSerializedValue=!1,this.registrationComplete.then(()=>{this._setValueMapForAllFormElements(`serializedValue`,e)})):this._setValueMapForAllFormElements(`serializedValue`,e)}get formattedValue(){return this._getFromAllFormElements(`formattedValue`)}set formattedValue(e){this._setValueMapForAllFormElements(`formattedValue`,e)}get prefilled(){return this._everyFormElementHas(`prefilled`)}constructor(){super(),this.value=``,this.disabled=!1,this.submitted=!1,this.dirty=!1,this.touched=!1,this.focused=!1,this.__addedSubValidators=!1,this.__isInitialModelValue=!0,this.__isInitialSerializedValue=!0,this._checkForOutsideClick=this._checkForOutsideClick.bind(this),this.addEventListener(`focusin`,this._syncFocused),this.addEventListener(`focusout`,this._onFocusOut),this.addEventListener(`dirty-changed`,this._syncDirty),this.addEventListener(`validate-performed`,this.__onChildValidatePerformed),this.defaultValidators=[new Vo],this.__descriptionElementsInParentChain=new Set,this.__pendingValues={modelValue:{},serializedValue:{}}}connectedCallback(){super.connectedCallback(),this.setAttribute(`role`,`group`),this.initComplete.then(()=>{this.__isInitialModelValue=!1,this.__isInitialSerializedValue=!1,this.__initInteractionStates()})}disconnectedCallback(){super.disconnectedCallback(),this.__hasActiveOutsideClickHandling&&=(document.removeEventListener(`click`,this._checkForOutsideClick),!1),this.__descriptionElementsInParentChain.clear()}__initInteractionStates(){this.formElements.forEach(e=>{typeof e.initInteractionState==`function`&&e.initInteractionState()})}_triggerInitialModelValueChangedEvent(){this.registrationComplete.then(()=>{this._dispatchInitialModelValueChangedEvent()})}updated(e){super.updated(e),e.has(`disabled`)&&(this.disabled?this.__requestChildrenToBeDisabled():this.__retractRequestChildrenToBeDisabled()),e.has(`focused`)&&this.focused===!0&&this.__setupOutsideClickHandling()}__setupOutsideClickHandling(){this.__hasActiveOutsideClickHandling||=(document.addEventListener(`click`,this._checkForOutsideClick),!0)}_checkForOutsideClick(e){this.contains(e.target)||(this.touched=!0)}__requestChildrenToBeDisabled(){this.formElements.forEach(e=>{e.makeRequestToBeDisabled&&e.makeRequestToBeDisabled()})}__retractRequestChildrenToBeDisabled(){this.formElements.forEach(e=>{e.retractRequestToBeDisabled&&e.retractRequestToBeDisabled()})}_inputGroupTemplate(){return g`
        <div class="input-group">
          <slot></slot>
        </div>
      `}submitGroup(){this.submitted=!0,this.formElements.forEach(e=>{typeof e.submitGroup==`function`?e.submitGroup():e.submitted=!0})}resetGroup(){this.formElements.forEach(e=>{typeof e.resetGroup==`function`?e.resetGroup():typeof e.reset==`function`&&e.reset()}),this.resetInteractionState()}clearGroup(){this.formElements.forEach(e=>{typeof e.clearGroup==`function`?e.clearGroup():typeof e.clear==`function`&&e.clear()}),this.resetInteractionState()}resetInteractionState(){this.submitted=!1,this.touched=!1,this.dirty=!1,this.formElements.forEach(e=>{typeof e.resetInteractionState==`function`&&e.resetInteractionState()})}_getFromAllFormElementsFilter(e,t){return!e.disabled}_getFromAllFormElements(e,t){let n={},r=t||this._getFromAllFormElementsFilter;return this.formElements._keys().forEach(t=>{let i=this.formElements[t];i instanceof Po?n[t]=i.filter(t=>r(t,e)).map(t=>t[e]):r(i,e)&&(typeof i._getFromAllFormElements==`function`?n[t]=i._getFromAllFormElements(e):n[t]=i[e])}),n}_setValueForAllFormElements(e,t){this.formElements.forEach(n=>{n[e]=t})}_setValueMapForAllFormElements(e,t){t&&typeof t==`object`&&Object.keys(t).forEach(n=>{Array.isArray(this.formElements[n])&&this.formElements[n].forEach((r,i)=>{r[e]=t[n][i]}),this.formElements[n]?this.formElements[n][e]=t[n]:this.__pendingValues[e][n]=t[n]})}_anyFormElementHas(e){return Object.keys(this.formElements).some(t=>Array.isArray(this.formElements[t])?this.formElements[t].some(t=>!!t[e]):!!this.formElements[t][e])}_anyFormElementHasFeedbackFor(e){return Object.keys(this.formElements).some(t=>Array.isArray(this.formElements[t])?this.formElements[t].some(t=>!!(t.hasFeedbackFor&&t.hasFeedbackFor.includes(e))):!!(this.formElements[t].hasFeedbackFor&&this.formElements[t].hasFeedbackFor.includes(e)))}_everyFormElementHas(e){return Object.keys(this.formElements).every(t=>Array.isArray(this.formElements[t])?this.formElements[t].every(t=>!!t[e]):!!this.formElements[t][e])}__onChildValidatePerformed(e){e&&this.isRegisteredFormElement(e.target)&&this.validate()}_syncFocused(){this.focused=this._anyFormElementHas(`focused`)}_onFocusOut(e){let t=this.formElements[this.formElements.length-1];e.target===t&&(this.touched=!0),this.focused=!1}_syncDirty(){this.dirty=this._anyFormElementHas(`dirty`)}__storeAllDescriptionElementsInParentChain(){let e=this;for(;e;)wo(e._getAriaDescriptionElements(),{reverse:!0}).forEach(e=>{e.getAttribute(`slot`)===`feedback`&&this.__descriptionElementsInParentChain.add(e)}),e=e._parentFormGroup}__linkParentMessages(e){this.__descriptionElementsInParentChain.forEach(t=>{typeof e.addToAriaDescribedBy==`function`&&e.addToAriaDescribedBy(t,{reorder:!1})})}__unlinkParentMessages(e){this.__descriptionElementsInParentChain.forEach(t=>{typeof e.removeFromAriaDescribedBy==`function`&&e.removeFromAriaDescribedBy(t)})}addFormElement(e,t){if(super.addFormElement(e,t),this.disabled&&e.makeRequestToBeDisabled(),this.__descriptionElementsInParentChain.size||this.__storeAllDescriptionElementsInParentChain(),this.__linkParentMessages(e),this.validate({clearCurrentResult:!0}),!e.modelValue){let t=this.__pendingValues;t.modelValue&&t.modelValue[e.name]?e.modelValue=t.modelValue[e.name]:t.serializedValue&&t.serializedValue[e.name]&&(e.serializedValue=t.serializedValue[e.name])}}get _initialModelValue(){return this._getFromAllFormElements(`_initialModelValue`)}removeFormElement(e){super.removeFormElement(e),this.validate({clearCurrentResult:!0}),typeof e.removeFromAriaLabelledBy==`function`&&this._labelNode&&e.removeFromAriaLabelledBy(this._labelNode,{reorder:!1}),this.__unlinkParentMessages(e)}_isEmpty(){return this.formElements.every(e=>e._isEmpty?.())}}),Uo=class extends Lo(No){static get properties(){return{readOnly:{type:Boolean,attribute:`readonly`,reflect:!0},type:{type:String,reflect:!0},placeholder:{type:String,reflect:!0}}}get slots(){return{...super.slots,input:()=>{let e=document.createElement(`input`),t=this.getAttribute(`value`);return t&&e.setAttribute(`value`,t),e}}}get _inputNode(){return super._inputNode}constructor(){super(),this.readOnly=!1,this.type=`text`,this.placeholder=``}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`readOnly`&&this.__delegateReadOnly()}firstUpdated(e){super.firstUpdated(e),this.__delegateReadOnly()}updated(e){super.updated(e),e.has(`type`)&&(this._inputNode.type=this.type),e.has(`placeholder`)&&(this._inputNode.placeholder=this.placeholder),e.has(`disabled`)&&(this._inputNode.disabled=this.disabled,this.validate()),e.has(`name`)&&(this._inputNode.name=this.name),e.has(`autocomplete`)&&(this._inputNode.autocomplete=this.autocomplete)}__delegateReadOnly(){this._inputNode&&(this._inputNode.readOnly=this.readOnly)}},Wo=v`
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
`,Go=class extends Uo{constructor(...e){super(...e),this.size=`medium`,this.small=!1,this.center=!1}static get styles(){return[...super.styles,xa,Wo]}connectedCallback(){super.connectedCallback(),this._inputNode&&this.maxlength&&this.maxlength>0&&(this._inputNode.size=this.maxlength)}};m([w({type:Number,reflect:!0})],Go.prototype,`maxlength`,void 0),m([w({type:String,reflect:!0})],Go.prototype,`size`,void 0),m([w({reflect:!0,type:Boolean})],Go.prototype,`small`,void 0),m([w({reflect:!0,type:Boolean})],Go.prototype,`center`,void 0),customElements.get(`craft-input`)||customElements.define(`craft-input`,Go);var Ko=e=>e??S,qo=class extends Do{static validatorName=`IsAcceptedFile`;static checkFileSize(e,t){return e<=t}static getExtension(e){return e?.slice(e.lastIndexOf(`.`))}static isExtensionAllowed(e,t){return t?.find(t=>t.toUpperCase()===e.toUpperCase())}static isFileTypeAllowed(e,t){return t?.find(t=>t.toUpperCase()===e.toUpperCase())}execute(e,t=this.param){let n,r,i=this.constructor,{allowedFileTypes:a,allowedFileExtensions:o,maxFileSize:s}=t;return a?.length?(n=e.some(e=>!i.isFileTypeAllowed(e.type,a)),n):o?.length?(r=e.some(e=>!i.isExtensionAllowed(i.getExtension(e.name),o)),r):e.findIndex(e=>!i.checkFileSize(e.size,s))>-1}static async getMessage(){return``}},Jo=class extends Do{static validatorName=`DuplicateFileNames`;constructor(e,t){super(e,t),this.type=`info`}execute(e,t=this.param){return t.show}static async getMessage(){return Ya().msg(`lion-input-file:uploadTextDuplicateFileName`)}},Yo=524288e3,Xo={type:`FILE_TYPE`,size:`FILE_SIZE`},Zo={fail:`FAIL`,pass:`SUCCESS`},Qo=class{constructor(e,t){this.failedProp=[],this.systemFile=e,this._acceptCriteria=t,this.uploadFileStatus(),this.failedProp.length===0&&this.createDownloadUrl(e)}_getFileNameExtension(e){return e.slice(e.lastIndexOf(`.`))}uploadFileStatus(){if(this._acceptCriteria.allowedFileExtensions.length){let e=this._getFileNameExtension(this.systemFile.name);qo.isExtensionAllowed(e,this._acceptCriteria.allowedFileExtensions)||(this.status=Zo.fail,this.failedProp.push(Xo.type))}else if(this._acceptCriteria.allowedFileTypes.length){let e=this.systemFile.type;qo.isFileTypeAllowed(e,this._acceptCriteria.allowedFileTypes)||(this.status=Zo.fail,this.failedProp.push(Xo.type))}qo.checkFileSize(this.systemFile.size,this._acceptCriteria.maxFileSize)?this.status!==Zo.fail&&(this.status=Zo.pass):(this.status=Zo.fail,this.failedProp.push(Xo.size))}createDownloadUrl(e){this.downloadUrl=window.URL.createObjectURL(e)}},$o=(e,t,n)=>{let r=new Map;for(let i=t;i<=n;i++)r.set(e[i],i);return r},es=k(class extends te{constructor(e){if(super(e),e.type!==ne.CHILD)throw Error(`repeat() can only be used in text expressions`)}dt(e,t,n){let r;n===void 0?n=t:t!==void 0&&(r=t);let i=[],a=[],o=0;for(let t of e)i[o]=r?r(t,o):o,a[o]=n(t,o),o++;return{values:a,keys:i}}render(e,t,n){return this.dt(e,t,n).values}update(e,[t,n,r]){let i=Br(e),{values:a,keys:o}=this.dt(t,n,r);if(!Array.isArray(i))return this.ut=o,a;let s=this.ut??=[],c=[],l,u,d=0,f=i.length-1,p=0,m=a.length-1;for(;d<=f&&p<=m;)if(i[d]===null)d++;else if(i[f]===null)f--;else if(s[d]===o[p])c[p]=Lr(i[d],a[p]),d++,p++;else if(s[f]===o[m])c[m]=Lr(i[f],a[m]),f--,m--;else if(s[d]===o[m])c[m]=Lr(i[d],a[m]),Ir(e,c[m+1],i[d]),d++,m--;else if(s[f]===o[p])c[p]=Lr(i[f],a[p]),Ir(e,i[d],i[f]),f--,p++;else if(l===void 0&&(l=$o(o,p,m),u=$o(s,d,f)),l.has(s[d]))if(l.has(s[f])){let t=u.get(o[p]),n=t===void 0?null:i[t];if(n===null){let t=Ir(e,i[d]);Lr(t,a[p]),c[p]=t}else c[p]=Lr(n,a[p]),Ir(e,i[d],n),i[t]=null;p++}else Vr(i[f]),f--;else Vr(i[d]),d++;for(;p<=m;){let t=Ir(e,c[m+1]);Lr(t,a[p]),c[p++]=t}for(;d<=f;){let e=i[d++];e!==null&&Vr(e)}return this.ut=o,zr(e,c),y}}),ts=e=>{switch(e){case`bg-BG`:return j(()=>import(`./bg-BG2.js`),__vite__mapDeps([34,35]),import.meta.url);case`bg`:return j(()=>import(`./bg3.js`),[],import.meta.url);case`cs-CZ`:return j(()=>import(`./cs-CZ2.js`),__vite__mapDeps([36,37]),import.meta.url);case`cs`:return j(()=>import(`./cs3.js`),[],import.meta.url);case`de-DE`:return j(()=>import(`./de-DE2.js`),__vite__mapDeps([38,39]),import.meta.url);case`de`:return j(()=>import(`./de3.js`),[],import.meta.url);case`en-AU`:return j(()=>import(`./en-AU2.js`),__vite__mapDeps([40,41]),import.meta.url);case`en-GB`:return j(()=>import(`./en-GB2.js`),__vite__mapDeps([42,41]),import.meta.url);case`en-US`:return j(()=>import(`./en-US2.js`),__vite__mapDeps([43,41]),import.meta.url);case`en-PH`:case`en`:return j(()=>import(`./en3.js`),[],import.meta.url);case`es-ES`:return j(()=>import(`./es-ES2.js`),__vite__mapDeps([44,45]),import.meta.url);case`es`:return j(()=>import(`./es3.js`),[],import.meta.url);case`fr-FR`:return j(()=>import(`./fr-FR2.js`),__vite__mapDeps([46,47]),import.meta.url);case`fr-BE`:return j(()=>import(`./fr-BE2.js`),__vite__mapDeps([48,47]),import.meta.url);case`fr`:return j(()=>import(`./fr3.js`),[],import.meta.url);case`hu-HU`:return j(()=>import(`./hu-HU2.js`),__vite__mapDeps([49,50]),import.meta.url);case`hu`:return j(()=>import(`./hu3.js`),[],import.meta.url);case`it-IT`:return j(()=>import(`./it-IT2.js`),__vite__mapDeps([51,52]),import.meta.url);case`it`:return j(()=>import(`./it3.js`),[],import.meta.url);case`nl-BE`:return j(()=>import(`./nl-BE2.js`),__vite__mapDeps([53,54]),import.meta.url);case`nl-NL`:return j(()=>import(`./nl-NL2.js`),__vite__mapDeps([55,54]),import.meta.url);case`nl`:return j(()=>import(`./nl3.js`),[],import.meta.url);case`pl-PL`:return j(()=>import(`./pl-PL2.js`),__vite__mapDeps([56,57]),import.meta.url);case`pl`:return j(()=>import(`./pl3.js`),[],import.meta.url);case`ro-RO`:return j(()=>import(`./ro-RO2.js`),__vite__mapDeps([58,59]),import.meta.url);case`ro`:return j(()=>import(`./ro3.js`),[],import.meta.url);case`ru-RU`:return j(()=>import(`./ru-RU2.js`),__vite__mapDeps([60,61]),import.meta.url);case`ru`:return j(()=>import(`./ru3.js`),[],import.meta.url);case`sk-SK`:return j(()=>import(`./sk-SK2.js`),__vite__mapDeps([62,63]),import.meta.url);case`sk`:return j(()=>import(`./sk3.js`),[],import.meta.url);case`uk-UA`:return j(()=>import(`./uk-UA2.js`),__vite__mapDeps([64,65]),import.meta.url);case`uk`:return j(()=>import(`./uk3.js`),[],import.meta.url);case`zh-CN`:case`zh`:return j(()=>import(`./zh3.js`),[],import.meta.url);default:return j(()=>import(`./en3.js`),[],import.meta.url)}},ns=class extends co(ho(C)){static get scopedElements(){return{...super.scopedElements,"lion-validation-feedback":xo}}static get properties(){return{fileList:{type:Array},multiple:{type:Boolean}}}static localizeNamespaces=[{"lion-input-file":ts},...super.localizeNamespaces];constructor(){super(),this.fileList=[],this.multiple=!1}updated(e){super.updated(e),e.has(`fileList`)&&this._enhanceLightDomA11y()}_enhanceLightDomA11y(){let e=this.shadowRoot?.querySelectorAll(`[id^="file-feedback"]`),t=this.parentNode?.parentNode;e?.forEach(e=>{t?.addEventListener(`focusin`,()=>{e.setAttribute(`aria-live`,`polite`)}),t?.addEventListener(`focusout`,()=>{e.setAttribute(`aria-live`,`assertive`)})})}_removeFile(e){this.dispatchEvent(new CustomEvent(`file-remove-requested`,{detail:{removedFile:e,status:e.status,uploadResponse:e.response}}))}_validationFeedbackTemplate(e,t){return g`
      <lion-validation-feedback
        id="file-feedback-${t}"
        .feedbackData="${e}"
        aria-live="assertive"
      ></lion-validation-feedback>
    `}_listItemBeforeTemplate(e){return S}_listItemAfterTemplate(e,t){return g`
      <button
        class="selected__list__item__remove-button"
        aria-label="${this.msgLit(`lion-input-file:removeButtonLabel`,{fileName:e.systemFile.name})}"
        @click=${()=>this._removeFile(e)}
      >
        ${this._removeButtonContentTemplate()}
      </button>
    `}_removeButtonContentTemplate(){return g`✖️`}_selectedListItemTemplate(e){let t=Kr();return g`
      <div class="selected__list__item" status="${e.status?e.status.toLowerCase():``}">
        <div class="selected__list__item__label">
          ${this._listItemBeforeTemplate(e)}
          <span id="selected-list-item-label-${t}" class="selected__list__item__label__text">
            <span class="sr-only">${this.msgLit(`lion-input-file:fileNameDescriptionLabel`)}</span>
            ${e.downloadUrl&&e.status!==`LOADING`?g`
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
        ${e.status===`FAIL`&&e.validationFeedback?g`
              ${es(e.validationFeedback,e=>g`
                  ${this._validationFeedbackTemplate([e],t)}
                `)}
            `:S}
      </div>
    `}render(){return this.fileList?.length?g`
          ${this.multiple?g`
                <ul class="selected__list">
                  ${this.fileList.map(e=>g` <li>${this._selectedListItemTemplate(e)}</li> `)}
                </ul>
              `:g` ${this._selectedListItemTemplate(this.fileList[0])} `}
        `:S}static get styles(){return[v`
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
      `]}};function rs(e,t=2){if(!+e)return`0 Bytes`;let n=1024,r=t<0?0:t,i=[` bytes`,`KB`,`MB`,`GB`,`TB`,`PB`,`EB`,`ZB`,`YB`],a=Math.floor(Math.log(e)/Math.log(n));return`${parseFloat((e/n**a).toFixed(r))}${i[a]}`}var is=class extends ho(co(No)){static get scopedElements(){return{...super.scopedElements,"lion-selected-file-list":ns}}static get properties(){return{accept:{type:String},multiple:{type:Boolean,reflect:!0},buttonLabel:{type:String,attribute:`button-label`},maxFileSize:{type:Number,attribute:`max-file-size`},enableDropZone:{type:Boolean,attribute:`enable-drop-zone`},uploadOnSelect:{type:Boolean,attribute:`upload-on-select`},isDragging:{type:Boolean,attribute:`is-dragging`,reflect:!0},uploadResponse:{type:Array,state:!1},_selectedFilesMetaData:{type:Array,state:!0}}}static localizeNamespaces=[{"lion-input-file":ts},...super.localizeNamespaces];static get validationTypes(){return[`error`,`info`]}get slots(){return{...super.slots,input:()=>g`<input .value="${Ko(this.getAttribute(`value`))}" />`,"file-select-button":()=>g`<button
          type="button"
          id="select-button-${this._inputId}"
          @click="${this.__openDialogOnBtnClick}"
        >
          ${this.buttonLabel}
        </button>`,after:()=>g`<div data-description></div>`,"selected-file-list":()=>({template:g`
          <lion-selected-file-list
            .fileList=${this._selectedFilesMetaData}
            .multiple=${this.multiple}
          ></lion-selected-file-list>
        `,renderAsDirectHostChild:!0})}}get _inputNode(){return super._inputNode}get _buttonNode(){return this.querySelector(`#select-button-${this._inputId}`)}get buttonLabel(){return this.__buttonLabel||this._buttonNode?.textContent?.trim()||``}set buttonLabel(e){let t=this.buttonLabel;this.__buttonLabel=e,this.requestUpdate(`buttonLabel`,t)}get _focusableNode(){return this._buttonNode}get _isDragAndDropSupported(){return`draggable`in document.createElement(`div`)}constructor(){super(),this.type=`file`,this._selectedFilesMetaData=[],this.uploadResponse=[],this.__initialUploadResponse=this.uploadResponse,this.uploadOnSelect=!1,this.multiple=!1,this.enableDropZone=!1,this.maxFileSize=Yo,this.accept=``,this.buttonLabel=``,this._initialButtonLabel=``,this.modelValue=[],this._onRemoveFile=this._onRemoveFile.bind(this),this.__duplicateFileNamesValidator=new Jo({show:!1}),this.__previouslyParsedFiles=null}get _fileListNode(){return Array.from(this.children).find(e=>e.slot===`selected-file-list`)}connectedCallback(){super.connectedCallback(),this.__initialUploadResponse=this.uploadResponse,this._initialButtonLabel=this.buttonLabel,this._inputNode.addEventListener(`change`,this._onChange),this._inputNode.addEventListener(`click`,this._onClick)}disconnectedCallback(){super.disconnectedCallback(),this._inputNode.removeEventListener(`change`,this._onChange),this._inputNode.removeEventListener(`click`,this._onClick)}onLocaleUpdated(){super.onLocaleUpdated(),this.multiple?this.buttonLabel=this._initialButtonLabel||this.msgLit(`lion-input-file:selectTextMultipleFile`):this.buttonLabel=this._initialButtonLabel||this.msgLit(`lion-input-file:selectTextSingleFile`)}get operationMode(){return`upload`}get _acceptCriteria(){let e=[],t=[];if(this.accept){let n=this.accept.replace(/\s+/g,``).split(`,`);e=n.filter(e=>e.includes(`/`)),t=n.filter(e=>!e.includes(`/`))}return{allowedFileTypes:e,allowedFileExtensions:t,maxFileSize:this.maxFileSize}}reset(){super.reset(),this._selectedFilesMetaData=[],this.uploadResponse=this.__initialUploadResponse,this.modelValue=[],this.dirty=!1}clear(){this._selectedFilesMetaData=[],this.uploadResponse=[],this.modelValue=[]}_showFeedbackConditionFor(e,t){return super._showFeedbackConditionFor(e,t)&&!(this.validationStates.error?.FileTypeAllowed||this.validationStates.error?.FileSizeAllowed)}parser(){if(this.__previouslyParsedFiles===this._inputNode.files)return this.modelValue;this.__previouslyParsedFiles=this._inputNode.files;let e=this._inputNode.files?Array.from(this._inputNode.files):[];return this.multiple?[...this.modelValue??[],...e]:e}formatter(e){return this._inputNode?.value||``}__setupDragDropEventListeners(){let e=this.shadowRoot?.querySelector(`.input-file__drop-zone`);[`dragenter`,`dragover`,`dragleave`].forEach(t=>{e?.addEventListener(t,e=>{e.preventDefault(),e.stopPropagation(),this.isDragging=t!==`dragleave`},!1)}),window.addEventListener(`drop`,e=>{e.target===this._inputNode&&e.preventDefault(),this.isDragging=!1},!1)}firstUpdated(e){super.firstUpdated(e),this.__setupFileValidators(),this._inputNode&&(this._inputNode.type=this.type,this._inputNode.setAttribute(`tabindex`,`-1`),this._inputNode.multiple=this.multiple,this.accept.length&&(this._inputNode.accept=this.accept)),this.enableDropZone&&this._isDragAndDropSupported&&(this.__setupDragDropEventListeners(),this.setAttribute(`drop-zone`,``)),this._fileListNode.addEventListener(`file-remove-requested`,this._onRemoveFile)}updated(e){super.updated(e),e.has(`disabled`)&&(this._inputNode.disabled=this.disabled,this.validate()),e.has(`buttonLabel`)&&this._buttonNode&&(this._buttonNode.textContent=this.buttonLabel),e.has(`name`)&&(this._inputNode.name=this.name),e.has(`_ariaLabelledNodes`)&&this.__syncAriaLabelledByAttributesToButton(),e.has(`_ariaDescribedNodes`)&&this.__syncAriaDescribedByAttributesToButton(),e.has(`uploadResponse`)&&(this._selectedFilesMetaData.length===0&&this.uploadResponse.forEach(e=>{let t={systemFile:{name:e.name},response:e,status:e.status,validationFeedback:[{message:e.errorMessage}]};this._selectedFilesMetaData=[...this._selectedFilesMetaData,t]}),this._selectedFilesMetaData.forEach(e=>{!this.uploadResponse.some(t=>t.name===e.systemFile.name)&&this.uploadOnSelect?this.__removeFileFromList(e):(this.uploadResponse.forEach(t=>{t.name===e.systemFile.name&&(e.response=t,e.downloadUrl=t.downloadUrl?t.downloadUrl:e.downloadUrl,e.status=t.status,e.validationFeedback=[{type:typeof t.errorMessage==`string`&&t.errorMessage?.length>0?`error`:`success`,message:t.errorMessage??``}])}),this._selectedFilesMetaData=[...this._selectedFilesMetaData])}),this._updateUploadButtonDescription())}__computeNewAddedFiles(e){let t=e.filter(e=>this._selectedFilesMetaData.findIndex(t=>t.systemFile.name===e.name)===-1);return this.__duplicateFileNamesValidator.param={show:e.length!==t.length},this.validate(),t}_processDroppedFiles(e){if(e.preventDefault(),this.isDragging=!1,!(e.dataTransfer&&e.dataTransfer.items.length>1&&!this.multiple||!e.dataTransfer?.files)){if(this._inputNode.files=e.dataTransfer.files,this.multiple){let t=this.__computeNewAddedFiles(Array.from(e.dataTransfer.files));this.modelValue=[...this.modelValue??[],...t]}else this.modelValue=Array.from(e.dataTransfer.files);this._processFiles(Array.from(e.dataTransfer.files))}}_onChange(e){this.touched=!0,this._onUserInputChanged(),this._processFiles(e?.target?.files)}_onClick(e){e.target.value=``}__syncAriaLabelledByAttributesToButton(){if(this._inputNode.hasAttribute(`aria-labelledby`)){let e=this._inputNode.getAttribute(`aria-labelledby`);this._buttonNode?.setAttribute(`aria-labelledby`,`select-button-${this._inputId} ${e}`)}}__syncAriaDescribedByAttributesToButton(){if(this._inputNode.hasAttribute(`aria-describedby`)){let e=this._inputNode.getAttribute(`aria-describedby`)||``;this._buttonNode?.setAttribute(`aria-describedby`,e)}}__setupFileValidators(){this.defaultValidators=[new qo(this._acceptCriteria),this.__duplicateFileNamesValidator]}_processFiles(e){let t=this.__computeNewAddedFiles(Array.from(e));!this.multiple&&t.length>0&&(this._selectedFilesMetaData=[],this.uploadResponse=[]);let n;for(let e of t.values())n=new Qo(e,this._acceptCriteria),n.failedProp?.length?(this._handleErroredFiles(n),this.uploadResponse=[...this.uploadResponse,{name:n.systemFile.name,status:`FAIL`,errorMessage:n.validationFeedback[0].message}]):this.uploadResponse=[...this.uploadResponse,{name:n.systemFile.name,status:`SUCCESS`}],this._selectedFilesMetaData=[...this._selectedFilesMetaData,n],this._handleErrors();let r=this._selectedFilesMetaData.filter(({systemFile:e,status:n})=>t.includes(e)&&n===`SUCCESS`).map(({systemFile:e})=>e);r.length>0&&this._dispatchFileListChangeEvent(r)}_dispatchFileListChangeEvent(e){this.dispatchEvent(new CustomEvent(`file-list-changed`,{detail:{newFiles:e}}))}_handleErrors(){let e=!1;if(this._selectedFilesMetaData.forEach(t=>{t.failedProp&&t.failedProp.length>0&&(e=!0)}),e)this.hasFeedbackFor?.push(`error`),this.shouldShowFeedbackFor.push(`error`);else if(this._prevHasErrors&&this.hasFeedbackFor.includes(`error`)){let e=this.hasFeedbackFor.indexOf(`error`);this.hasFeedbackFor.slice(e,e+1);let t=this.shouldShowFeedbackFor.indexOf(`error`);this.shouldShowFeedbackFor.slice(t,t+1)}this._prevHasErrors=e}_handleErroredFiles(e){e.validationFeedback=[];let{allowedFileExtensions:t,allowedFileTypes:n}=this._acceptCriteria,r=[],i=0,a;t.length?(r=t,a=r.pop(),i=r.length):n.length&&(n.forEach(e=>{if(e.endsWith(`/*`))r.push(e.slice(0,-2));else if(e===`text/plain`)r.push(`text`);else{let t=e.indexOf(`/`),n=e.slice(t+1);if(!n.includes(`+`))r.push(`.${n}`);else{let e=n.split(`+`);r.push(`.${e[0]}`)}}}),a=r.pop(),i=r.length);let o=``;o=a?i?`${this.msgLit(`lion-input-file:allowedFileValidatorComplex`,{allowedTypesArray:r.join(`, `),allowedTypesLastItem:a,maxSize:rs(this.maxFileSize)})}`:`${this.msgLit(`lion-input-file:allowedFileValidatorSimple`,{allowedType:a,maxSize:rs(this.maxFileSize)})}`:`${this.msgLit(`lion-input-file:allowedFileSize`,{maxSize:rs(this.maxFileSize)})}`;let s={message:o,type:`error`};e.validationFeedback?.push(s)}_updateUploadButtonDescription(){let e=[],t;this._selectedFilesMetaData.forEach(n=>{n.status===`FAIL`&&(t=n.validationFeedback?n.validationFeedback[0].message.toString():``,e.push(n.systemFile.name))});let n=this.querySelector(`[slot="after"]`);if(n)if(!this._selectedFilesMetaData||this._selectedFilesMetaData.length===0)this.uploadOnSelect?n.textContent=this.msgLit(`lion-input-file:noFilesUploaded`):n.textContent=this.msgLit(`lion-input-file:noFilesSelected`);else if(this._selectedFilesMetaData.length===1){let{name:e}=this._selectedFilesMetaData[0].systemFile;this.uploadOnSelect?n.textContent=t||this.msgLit(`lion-input-file:fileUploaded`)+(e??``):n.textContent=t||this.msgLit(`lion-input-file:fileSelected`)+(e??``)}else this.uploadOnSelect?n.textContent=`${this.msgLit(`lion-input-file:filesUploaded`,{numberOfFiles:this._selectedFilesMetaData.length})} ${t?this.msgLit(`lion-input-file:generalValidatorMessage`,{validatorMessage:t,listOfErroneousFiles:e.join(`, `)}):``}`:n.textContent=`${this.msgLit(`lion-input-file:filesSelected`,{numberOfFiles:this._selectedFilesMetaData.length})} ${t?this.msgLit(`lion-input-file:generalValidatorMessage`,{validatorMessage:t,listOfErroneousFiles:e.join(`, `)}):``}`}__removeFileFromList(e){this._selectedFilesMetaData=this._selectedFilesMetaData.filter(t=>t.systemFile.name!==e.systemFile.name),this.modelValue&&=this.modelValue.filter(t=>t.name!==e.systemFile.name),this._inputNode.value=``,this._handleErrors(),this._updateUploadButtonDescription()}_onRemoveFile(e){if(this.disabled)return;let{removedFile:t}=e.detail;!this.uploadOnSelect&&t&&this.__removeFileFromList(t),this._removeFile(t)}_removeFile(e){this.dispatchEvent(new CustomEvent(`file-removed`,{detail:{removedFile:e,status:e.status,uploadResponse:e.response}}))}_reflectBackOn(){return!1}_isEmpty(){return this.modelValue?.length===0}_dropZoneTemplate(){return g`
      <div @drop="${this._processDroppedFiles}" class="input-file__drop-zone">
        <div class="input-file__drop-zone__text">
          ${this.msgLit(`lion-input-file:dragAndDropText`)}
        </div>
        <slot name="file-select-button"></slot>
      </div>
    `}_inputGroupAfterTemplate(){return g` <slot name="selected-file-list"></slot> `}_inputGroupInputTemplate(){return g`
      <slot name="input"> </slot>
      <slot name="after"> </slot>
      ${this.enableDropZone&&this._isDragAndDropSupported?this._dropZoneTemplate():g`
            <div class="input-group__file-select-button">
              <slot name="file-select-button"></slot>
            </div>
          `}
    `}static get styles(){return[super.styles,v`
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
      `]}__openDialogOnBtnClick(e){e.preventDefault(),e.stopPropagation(),this._inputNode.click()}},as=class extends ns{static get styles(){return[...super.styles,v`
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
      `]}_listItemAfterTemplate(e,t){return g`
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
    `}_removeButtonContentTemplate(){return g`<craft-icon name="x"></craft-icon>`}_listItemBeforeTemplate(e){return g`<img src="${e.downloadUrl}" alt="" class="preview-thumb" />`}},os=v`
  /* Add any craft-specific styles for input-file here */
  ::slotted([slot='selected-file-list']) {
    margin-block-start: var(--c-spacing-lg);
  }
`,ss=class extends is{static get styles(){return[...super.styles,xa,os]}get slots(){return{...super.slots,"file-select-button":()=>g`<craft-button
          type="button"
          id="select-button-${this._inputId}"
          @click="${this.__openDialogOnBtnClick}"
        >
          ${this.buttonLabel}
        </craft-button>`}}static get scopedElements(){return{...super.scopedElements,"lion-selected-file-list":as}}};customElements.get(`craft-input-file`)||customElements.define(`craft-input-file`,ss);var cs=function(e,t,n,r,i){if(r===`m`)throw TypeError(`Private method is not writable`);if(r===`a`&&!i)throw TypeError(`Private accessor was defined without a setter`);if(typeof t==`function`?e!==t||!i:!t.has(e))throw TypeError(`Cannot write private member to an object whose class did not declare it`);return r===`a`?i.call(e,n):i?i.value=n:t.set(e,n),n},ls=function(e,t,n,r){if(n===`a`&&!r)throw TypeError(`Private accessor was defined without a getter`);if(typeof t==`function`?e!==t||!r:!t.has(e))throw TypeError(`Cannot read private member from an object whose class did not declare it`);return n===`m`?r:n===`a`?r.call(e):r?r.value:t.get(e)},us,ds=class{formatToParts(e){let t=[];for(let n of e)t.push({type:`element`,value:n}),t.push({type:`literal`,value:`, `});return t.slice(0,-1)}},fs=typeof Intl<`u`&&Intl.ListFormat||ds,ps=[[`years`,`year`],[`months`,`month`],[`weeks`,`week`],[`days`,`day`],[`hours`,`hour`],[`minutes`,`minute`],[`seconds`,`second`],[`milliseconds`,`millisecond`]],ms={minimumIntegerDigits:2},hs=class{constructor(e,t={}){us.set(this,void 0);let n=String(t.style||`short`);n!==`long`&&n!==`short`&&n!==`narrow`&&n!==`digital`&&(n=`short`);let r=n===`digital`?`numeric`:n,i=t.hours||r;r=i===`2-digit`?`numeric`:i;let a=t.minutes||r;r=a===`2-digit`?`numeric`:a;let o=t.seconds||r;r=o===`2-digit`?`numeric`:o;let s=t.milliseconds||r;cs(this,us,{locale:e,style:n,years:t.years||n===`digital`?`short`:n,yearsDisplay:t.yearsDisplay===`always`?`always`:`auto`,months:t.months||n===`digital`?`short`:n,monthsDisplay:t.monthsDisplay===`always`?`always`:`auto`,weeks:t.weeks||n===`digital`?`short`:n,weeksDisplay:t.weeksDisplay===`always`?`always`:`auto`,days:t.days||n===`digital`?`short`:n,daysDisplay:t.daysDisplay===`always`?`always`:`auto`,hours:i,hoursDisplay:t.hoursDisplay===`always`||n===`digital`?`always`:`auto`,minutes:a,minutesDisplay:t.minutesDisplay===`always`||n===`digital`?`always`:`auto`,seconds:o,secondsDisplay:t.secondsDisplay===`always`||n===`digital`?`always`:`auto`,milliseconds:s,millisecondsDisplay:t.millisecondsDisplay===`always`?`always`:`auto`},`f`)}resolvedOptions(){return ls(this,us,`f`)}formatToParts(e){let t=[],n=ls(this,us,`f`),r=n.style,i=n.locale;for(let[a,o]of ps){let s=e[a];if(n[`${a}Display`]===`auto`&&!s)continue;let c=n[a],l=c===`2-digit`?ms:c===`numeric`?{}:{style:`unit`,unit:o,unitDisplay:c},u=new Intl.NumberFormat(i,l).format(s);a===`months`&&(c===`narrow`||r===`narrow`&&u.endsWith(`m`))&&(u=u.replace(/(\d+)m$/,`$1mo`)),t.push(u)}return new fs(i,{type:`unit`,style:r===`digital`?`short`:r}).formatToParts(t)}format(e){return this.formatToParts(e).map(e=>e.value).join(``)}};us=new WeakMap;var gs=/^[-+]?P(?:(\d+)Y)?(?:(\d+)M)?(?:(\d+)W)?(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?)?$/,_s=[`year`,`month`,`week`,`day`,`hour`,`minute`,`second`,`millisecond`],vs=e=>gs.test(e),ys=class e{constructor(e=0,t=0,n=0,r=0,i=0,a=0,o=0,s=0){this.years=e,this.months=t,this.weeks=n,this.days=r,this.hours=i,this.minutes=a,this.seconds=o,this.milliseconds=s,this.years||=0,this.sign||=Math.sign(this.years),this.months||=0,this.sign||=Math.sign(this.months),this.weeks||=0,this.sign||=Math.sign(this.weeks),this.days||=0,this.sign||=Math.sign(this.days),this.hours||=0,this.sign||=Math.sign(this.hours),this.minutes||=0,this.sign||=Math.sign(this.minutes),this.seconds||=0,this.sign||=Math.sign(this.seconds),this.milliseconds||=0,this.sign||=Math.sign(this.milliseconds),this.blank=this.sign===0}abs(){return new e(Math.abs(this.years),Math.abs(this.months),Math.abs(this.weeks),Math.abs(this.days),Math.abs(this.hours),Math.abs(this.minutes),Math.abs(this.seconds),Math.abs(this.milliseconds))}static from(t){if(typeof t==`string`){let n=String(t).trim(),r=n.startsWith(`-`)?-1:1,i=n.match(gs)?.slice(1).map(e=>(Number(e)||0)*r);return i?new e(...i):new e}else if(typeof t==`object`){let{years:n,months:r,weeks:i,days:a,hours:o,minutes:s,seconds:c,milliseconds:l}=t;return new e(n,r,i,a,o,s,c,l)}throw RangeError(`invalid duration`)}static compare(t,n){let r=Date.now(),i=Math.abs(bs(r,e.from(t)).getTime()-r),a=Math.abs(bs(r,e.from(n)).getTime()-r);return i>a?-1:i<a?1:0}toLocaleString(e,t){return new hs(e,t).format(this)}};function bs(e,t){let n=new Date(e);return t.sign<0?(n.setUTCSeconds(n.getUTCSeconds()+t.seconds),n.setUTCMinutes(n.getUTCMinutes()+t.minutes),n.setUTCHours(n.getUTCHours()+t.hours),n.setUTCDate(n.getUTCDate()+t.weeks*7+t.days),n.setUTCMonth(n.getUTCMonth()+t.months),n.setUTCFullYear(n.getUTCFullYear()+t.years)):(n.setUTCFullYear(n.getUTCFullYear()+t.years),n.setUTCMonth(n.getUTCMonth()+t.months),n.setUTCDate(n.getUTCDate()+t.weeks*7+t.days),n.setUTCHours(n.getUTCHours()+t.hours),n.setUTCMinutes(n.getUTCMinutes()+t.minutes),n.setUTCSeconds(n.getUTCSeconds()+t.seconds)),n}function xs(e,t=`second`,n=Date.now()){let r=e.getTime()-n;if(r===0)return new ys;let i=Math.sign(r),a=Math.abs(r),o=Math.floor(a/1e3),s=Math.floor(o/60),c=Math.floor(s/60),l=Math.floor(c/24),u=Math.floor(l/30),d=Math.floor(u/12),f=_s.indexOf(t)||_s.length;return new ys(f>=0?d*i:0,f>=1?(u-d*12)*i:0,0,f>=3?(l-u*30)*i:0,f>=4?(c-l*24)*i:0,f>=5?(s-c*60)*i:0,f>=6?(o-s*60)*i:0,f>=7?(a-o*1e3)*i:0)}function Ss(e,{relativeTo:t=Date.now()}={}){if(t=new Date(t),e.blank)return e;let n=e.sign,r=Math.abs(e.years),i=Math.abs(e.months),a=Math.abs(e.weeks),o=Math.abs(e.days),s=Math.abs(e.hours),c=Math.abs(e.minutes),l=Math.abs(e.seconds),u=Math.abs(e.milliseconds);u>=900&&(l+=Math.round(u/1e3)),(l||c||s||o||a||i||r)&&(u=0),l>=55&&(c+=Math.round(l/60)),(c||s||o||a||i||r)&&(l=0),c>=55&&(s+=Math.round(c/60)),(s||o||a||i||r)&&(c=0),o&&s>=12&&(o+=Math.round(s/24)),!o&&s>=21&&(o+=Math.round(s/24)),(o||a||i||r)&&(s=0);let d=t.getFullYear(),f=t.getMonth(),p=t.getDate();if(o>=27||r+i+o){let e=new Date(t);e.setDate(1),e.setMonth(f+i*n+1),e.setDate(0);let s=Math.max(0,p-e.getDate()),c=new Date(t);c.setFullYear(d+r*n),c.setDate(p-s),c.setMonth(f+i*n),c.setDate(p-s+o*n);let l=c.getFullYear()-t.getFullYear(),u=c.getMonth()-t.getMonth(),m=Math.abs(Math.round((Number(c)-Number(t))/864e5))+s,h=Math.abs(l*12+u);m<27?(o>=6?(a+=Math.round(o/7),o=0):o=m,i=r=0):h<=11?(i=h,r=0):(i=0,r=l*n),(i||r)&&(o=0)}return r&&(i=0),a>=4&&(i+=Math.round(a/4)),(i||r)&&(a=0),o&&a&&!i&&!r&&(a+=Math.round(o/7),o=0),new ys(r*n,i*n,a*n,o*n,s*n,c*n,l*n,u*n)}function Cs(e,t){let n=Ss(e,t);if(n.blank)return[0,`second`];for(let e of _s){if(e===`millisecond`)continue;let t=n[`${e}s`];if(t)return[t,e]}return[0,`second`]}var z=function(e,t,n,r){if(n===`a`&&!r)throw TypeError(`Private accessor was defined without a getter`);if(typeof t==`function`?e!==t||!r:!t.has(e))throw TypeError(`Cannot read private member from an object whose class did not declare it`);return n===`m`?r:n===`a`?r.call(e):r?r.value:t.get(e)},ws=function(e,t,n,r,i){if(r===`m`)throw TypeError(`Private method is not writable`);if(r===`a`&&!i)throw TypeError(`Private accessor was defined without a setter`);if(typeof t==`function`?e!==t||!i:!t.has(e))throw TypeError(`Cannot write private member to an object whose class did not declare it`);return r===`a`?i.call(e,n):i?i.value=n:t.set(e,n),n},Ts,Es,Ds,Os,ks,As,js,Ms,Ns,Ps,Fs,Is,Ls,Rs,zs=globalThis.HTMLElement||null,Bs=new ys,Vs=new ys(0,0,0,0,0,1),Hs=class extends Event{constructor(e,t,n,r){super(`relative-time-updated`,{bubbles:!0,composed:!0}),this.oldText=e,this.newText=t,this.oldTitle=n,this.newTitle=r}};function Us(e){if(!e.date)return 1/0;if(e.format===`duration`||e.format===`elapsed`){let t=e.precision;if(t===`second`)return 1e3;if(t===`minute`)return 60*1e3}let t=Math.abs(Date.now()-e.date.getTime());return t<60*1e3?1e3:t<3600*1e3?60*1e3:3600*1e3}var Ws=new class{constructor(){this.elements=new Set,this.time=1/0,this.timer=-1}observe(e){if(this.elements.has(e))return;this.elements.add(e);let t=e.date;if(t&&t.getTime()){let t=Us(e),n=Date.now()+t;n<this.time&&(clearTimeout(this.timer),this.timer=setTimeout(()=>this.update(),t),this.time=n)}}unobserve(e){this.elements.has(e)&&this.elements.delete(e)}update(){if(clearTimeout(this.timer),!this.elements.size)return;let e=1/0;for(let t of this.elements)e=Math.min(e,Us(t)),t.update();this.time=Math.min(3600*1e3,e),this.timer=setTimeout(()=>this.update(),this.time),this.time+=Date.now()}},Gs=class extends zs{constructor(){super(...arguments),Ts.add(this),Es.set(this,!1),Ds.set(this,!1),ks.set(this,this.shadowRoot?this.shadowRoot:this.attachShadow?this.attachShadow({mode:`open`}):this),Rs.set(this,null)}static define(e=`relative-time`,t=customElements){return t.define(e,this),this}get timeZone(){return this.closest(`[time-zone]`)?.getAttribute(`time-zone`)||this.ownerDocument.documentElement.getAttribute(`time-zone`)||void 0}static get observedAttributes(){return[`second`,`minute`,`hour`,`weekday`,`day`,`month`,`year`,`time-zone-name`,`prefix`,`threshold`,`tense`,`precision`,`format`,`format-style`,`no-title`,`datetime`,`lang`,`title`,`aria-hidden`,`time-zone`]}get onRelativeTimeUpdated(){return z(this,Rs,`f`)}set onRelativeTimeUpdated(e){z(this,Rs,`f`)&&this.removeEventListener(`relative-time-updated`,z(this,Rs,`f`)),ws(this,Rs,typeof e==`object`||typeof e==`function`?e:null,`f`),typeof e==`function`&&this.addEventListener(`relative-time-updated`,e)}get second(){let e=this.getAttribute(`second`);if(e===`numeric`||e===`2-digit`)return e}set second(e){this.setAttribute(`second`,e||``)}get minute(){let e=this.getAttribute(`minute`);if(e===`numeric`||e===`2-digit`)return e}set minute(e){this.setAttribute(`minute`,e||``)}get hour(){let e=this.getAttribute(`hour`);if(e===`numeric`||e===`2-digit`)return e}set hour(e){this.setAttribute(`hour`,e||``)}get weekday(){let e=this.getAttribute(`weekday`);if(e===`long`||e===`short`||e===`narrow`)return e;if(this.format===`datetime`&&e!==``)return this.formatStyle}set weekday(e){this.setAttribute(`weekday`,e||``)}get day(){let e=this.getAttribute(`day`)??`numeric`;if(e===`numeric`||e===`2-digit`)return e}set day(e){this.setAttribute(`day`,e||``)}get month(){let e=this.format,t=this.getAttribute(`month`);if(t!==``&&(t??=e===`datetime`?this.formatStyle:`short`,t===`numeric`||t===`2-digit`||t===`short`||t===`long`||t===`narrow`))return t}set month(e){this.setAttribute(`month`,e||``)}get year(){let e=this.getAttribute(`year`);if(e===`numeric`||e===`2-digit`)return e;if(!this.hasAttribute(`year`)&&new Date().getUTCFullYear()!==this.date?.getUTCFullYear())return`numeric`}set year(e){this.setAttribute(`year`,e||``)}get timeZoneName(){let e=this.getAttribute(`time-zone-name`);if(e===`long`||e===`short`||e===`shortOffset`||e===`longOffset`||e===`shortGeneric`||e===`longGeneric`)return e}set timeZoneName(e){this.setAttribute(`time-zone-name`,e||``)}get prefix(){return this.getAttribute(`prefix`)??(this.format===`datetime`?``:`on`)}set prefix(e){this.setAttribute(`prefix`,e)}get threshold(){let e=this.getAttribute(`threshold`);return e&&vs(e)?e:`P30D`}set threshold(e){this.setAttribute(`threshold`,e)}get tense(){let e=this.getAttribute(`tense`);return e===`past`?`past`:e===`future`?`future`:`auto`}set tense(e){this.setAttribute(`tense`,e)}get precision(){let e=this.getAttribute(`precision`);return _s.includes(e)?e:this.format===`micro`?`minute`:`second`}set precision(e){this.setAttribute(`precision`,e)}get format(){let e=this.getAttribute(`format`);return e===`datetime`?`datetime`:e===`relative`?`relative`:e===`duration`?`duration`:e===`micro`?`micro`:e===`elapsed`?`elapsed`:`auto`}set format(e){this.setAttribute(`format`,e)}get formatStyle(){let e=this.getAttribute(`format-style`);if(e===`long`)return`long`;if(e===`short`)return`short`;if(e===`narrow`)return`narrow`;let t=this.format;return t===`elapsed`||t===`micro`?`narrow`:t===`datetime`?`short`:`long`}set formatStyle(e){this.setAttribute(`format-style`,e)}get noTitle(){return this.hasAttribute(`no-title`)}set noTitle(e){this.toggleAttribute(`no-title`,e)}get datetime(){return this.getAttribute(`datetime`)||``}set datetime(e){this.setAttribute(`datetime`,e)}get date(){let e=Date.parse(this.datetime);return Number.isNaN(e)?null:new Date(e)}set date(e){this.datetime=e?.toISOString()||``}connectedCallback(){this.update()}disconnectedCallback(){Ws.unobserve(this)}attributeChangedCallback(e,t,n){t!==n&&(e===`title`&&ws(this,Es,n!==null&&(this.date&&z(this,Ts,`m`,As).call(this,this.date))!==n,`f`),!z(this,Ds,`f`)&&!(e===`title`&&z(this,Es,`f`))&&ws(this,Ds,(async()=>{await Promise.resolve(),this.update(),ws(this,Ds,!1,`f`)})(),`f`))}update(){let e=z(this,ks,`f`).textContent||this.textContent||``,t=this.getAttribute(`title`)||``,n=t,r=this.date;if(typeof Intl>`u`||!Intl.DateTimeFormat||!r){z(this,ks,`f`).textContent=e;return}let i=Date.now();z(this,Es,`f`)||(n=z(this,Ts,`m`,As).call(this,r)||``,n&&!this.noTitle&&this.setAttribute(`title`,n));let a=xs(r,this.precision,i),o=z(this,Ts,`m`,js).call(this,a),s=e,c=z(this,Ts,`m`,Ls).call(this,o);s=c?z(this,Ts,`m`,Fs).call(this,r):o===`duration`?z(this,Ts,`m`,Ms).call(this,a):o===`relative`?z(this,Ts,`m`,Ns).call(this,a):z(this,Ts,`m`,Ps).call(this,r),s?z(this,Ts,`m`,Is).call(this,s):this.shadowRoot===z(this,ks,`f`)&&this.textContent&&z(this,Ts,`m`,Is).call(this,this.textContent),(s!==e||n!==t)&&this.dispatchEvent(new Hs(e,s,t,n)),(o===`relative`||o===`duration`)&&!c?Ws.observe(this):Ws.unobserve(this)}};Es=new WeakMap,Ds=new WeakMap,ks=new WeakMap,Rs=new WeakMap,Ts=new WeakSet,Os=function(){let e=this.closest(`[lang]`)?.getAttribute(`lang`)||this.ownerDocument.documentElement.getAttribute(`lang`);try{return new Intl.Locale(e??``).toString()}catch{return`default`}},As=function(e){return new Intl.DateTimeFormat(z(this,Ts,`a`,Os),{day:`numeric`,month:`short`,year:`numeric`,hour:`numeric`,minute:`2-digit`,timeZoneName:`short`,timeZone:this.timeZone}).format(e)},js=function(e){let t=this.format;if(t===`datetime`)return`datetime`;if(t===`duration`||t===`elapsed`||t===`micro`)return`duration`;if((t===`auto`||t===`relative`)&&typeof Intl<`u`&&Intl.RelativeTimeFormat){let t=this.tense;if(t===`past`||t===`future`||ys.compare(e,this.threshold)===1)return`relative`}return`datetime`},Ms=function(e){let t=z(this,Ts,`a`,Os),n=this.format,r=this.formatStyle,i=this.tense,a=Bs;n===`micro`?(e=Ss(e),a=Vs,e.months===0&&(this.tense===`past`&&e.sign!==-1||this.tense===`future`&&e.sign!==1)&&(e=Vs)):(i===`past`&&e.sign!==-1||i===`future`&&e.sign!==1)&&(e=a);let o=`${this.precision}sDisplay`;return e.blank?a.toLocaleString(t,{style:r,[o]:`always`}):e.abs().toLocaleString(t,{style:r})},Ns=function(e){let t=new Intl.RelativeTimeFormat(z(this,Ts,`a`,Os),{numeric:`auto`,style:this.formatStyle}),n=this.tense;n===`future`&&e.sign!==1&&(e=Bs),n===`past`&&e.sign!==-1&&(e=Bs);let[r,i]=Cs(e);return i===`second`&&r<10?t.format(0,this.precision===`millisecond`?`second`:this.precision):t.format(r,i)},Ps=function(e){let t=new Intl.DateTimeFormat(z(this,Ts,`a`,Os),{second:this.second,minute:this.minute,hour:this.hour,weekday:this.weekday,day:this.day,month:this.month,year:this.year,timeZoneName:this.timeZoneName,timeZone:this.timeZone});return`${this.prefix} ${t.format(e)}`.trim()},Fs=function(e){return new Intl.DateTimeFormat(z(this,Ts,`a`,Os),{day:`numeric`,month:`short`,year:`numeric`,hour:`numeric`,minute:`2-digit`,timeZoneName:`short`,timeZone:this.timeZone}).format(e)},Is=function(e){if(this.hasAttribute(`aria-hidden`)&&this.getAttribute(`aria-hidden`)===`true`){let t=document.createElement(`span`);t.setAttribute(`aria-hidden`,`true`),t.textContent=e,z(this,ks,`f`).replaceChildren(t)}else z(this,ks,`f`).textContent=e},Ls=function(e){return e===`duration`?!1:this.ownerDocument.documentElement.getAttribute(`data-prefers-absolute-time`)===`true`||this.ownerDocument.body?.getAttribute(`data-prefers-absolute-time`)===`true`};var Ks=typeof globalThis<`u`?globalThis:window;try{Ks.RelativeTimeElement=Gs.define()}catch(e){if(!(Ks.DOMException&&e instanceof DOMException&&e.name===`NotSupportedError`)&&!(e instanceof ReferenceError))throw e}var qs=class extends Go{static get styles(){return[...super.styles,v`
        .input-group__input {
          font-family: var(--c-font-mono);
          font-size: 0.9em;
        }
      `]}constructor(){super(),this.autocorrect=!1}firstUpdated(e){super.firstUpdated(e),this._inputNode?.setAttribute(`autocapitalize`,`off`)}};customElements.get(`craft-input-handle`)||customElements.define(`craft-input-handle`,qs),Xe();var Js=class extends Uo{static get styles(){return[...super.styles,xa,v`
        .input-group__container {
          position: relative;
        }

        .input-group__suffix {
          position: absolute;
          inset-inline-end: var(--c-input-spacing-inline);
          inset-block-start: 50%;
          transform: translateY(calc(-50%));
        }
      `]}constructor(){super(),this._visible=!1,this.reveal=()=>{this._visible=!this._visible,this.type=this._visible?`text`:`password`},this.renderSuffix=()=>g`
      <craft-button
        type="button"
        icon
        size="small"
        variant="plain"
        @click="${this.reveal}"
        appearance="plain"
      >
        <span class="icon"
          >${this._visible?g`<craft-icon name="eye-slash"></craft-icon>`:g`<craft-icon name="eye"></craft-icon>`}
        </span>
      </craft-button>
    `,this.type=`password`}get slots(){return{...super.slots,suffix:()=>({template:this.renderSuffix()})}}};m([T()],Js.prototype,`_visible`,void 0),customElements.get(`craft-input-password`)||customElements.define(`craft-input-password`,Js);var Ys=v`
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
`,Xs=class extends C{constructor(...e){super(...e),this.size=``,this.variant=``,this.icon=null}renderPrefix(){return g`<div class="chip__prefix" part="prefix">
      <slot name="prefix">
        ${this.icon?g`<craft-icon name="${this.icon}"></craft-icon>`:S}
      </slot>
    </div>`}render(){let e=!!this.querySelector(`[slot="prefix"]`)||this.icon,t=!!this.querySelector(`[slot="suffix"]`);return g`
      <div
        part="chip"
        class="${A({chip:!0,"chip--small":this.size===`small`,"chip--medium":this.size===`medium`,"chip--large":this.size===`large`,"chip--plain":this.variant===`plain`})}"
      >
        ${e?this.renderPrefix():S}
        <div class="chip__body">
          <slot></slot>
        </div>
        ${t?g`<div class="chip__suffix" part="suffix">
              <slot name="suffix"></slot>
            </div>`:S}
      </div>
    `}};Xs.styles=[Ys],m([w()],Xs.prototype,`size`,void 0),m([w()],Xs.prototype,`variant`,void 0),m([w()],Xs.prototype,`icon`,void 0),customElements.get(`craft-chip`)||customElements.define(`craft-chip`,Xs);var Zs=v`
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
`,Qs=class extends C{constructor(...e){super(...e),this.label=null,this.status=null}getLabel(){return!this.label&&this.status?`Status: ${this.status}`:this.label}render(){return g`
      <span
        class="${A({status:!0,"status--live":this.status===`live`,"status--enabled":this.status===`enabled`,"status--pending":this.status===`pending`,"status--expired":this.status===`expired`,"status--disabled":this.status===`disabled`})}"
        role="img"
        aria-label="${this.getLabel()}"
      ></span>
    `}};Qs.styles=[Zs],m([w()],Qs.prototype,`label`,void 0),m([w()],Qs.prototype,`status`,void 0),customElements.get(`craft-status`)||customElements.define(`craft-status`,Qs);var $s=new Map;function ec(e){var t=$s.get(e);t&&t.destroy()}function tc(e){var t=$s.get(e);t&&t.update()}var nc=null;typeof window>`u`?((nc=function(e){return e}).destroy=function(e){return e},nc.update=function(e){return e}):((nc=function(e,t){return e&&Array.prototype.forEach.call(e.length?e:[e],function(e){return function(e){if(e&&e.nodeName&&e.nodeName===`TEXTAREA`&&!$s.has(e)){var t,n=null,r=window.getComputedStyle(e),i=(t=e.value,function(){o({testForHeightReduction:t===``||!e.value.startsWith(t),restoreTextAlign:null}),t=e.value}),a=function(t){e.removeEventListener(`autosize:destroy`,a),e.removeEventListener(`autosize:update`,s),e.removeEventListener(`input`,i),window.removeEventListener(`resize`,s),Object.keys(t).forEach(function(n){return e.style[n]=t[n]}),$s.delete(e)}.bind(e,{height:e.style.height,resize:e.style.resize,textAlign:e.style.textAlign,overflowY:e.style.overflowY,overflowX:e.style.overflowX,wordWrap:e.style.wordWrap});e.addEventListener(`autosize:destroy`,a),e.addEventListener(`autosize:update`,s),e.addEventListener(`input`,i),window.addEventListener(`resize`,s),e.style.overflowX=`hidden`,e.style.wordWrap=`break-word`,$s.set(e,{destroy:a,update:s}),s()}function o(t){var i,a,s=t.restoreTextAlign,c=s===void 0?null:s,l=t.testForHeightReduction,u=l===void 0||l,d=r.overflowY;if(e.scrollHeight!==0&&(r.resize===`vertical`?e.style.resize=`none`:r.resize===`both`&&(e.style.resize=`horizontal`),u&&(i=function(e){for(var t=[];e&&e.parentNode&&e.parentNode instanceof Element;)e.parentNode.scrollTop&&t.push([e.parentNode,e.parentNode.scrollTop]),e=e.parentNode;return function(){return t.forEach(function(e){var t=e[0],n=e[1];t.style.scrollBehavior=`auto`,t.scrollTop=n,t.style.scrollBehavior=null})}}(e),e.style.height=``),a=r.boxSizing===`content-box`?e.scrollHeight-(parseFloat(r.paddingTop)+parseFloat(r.paddingBottom)):e.scrollHeight+parseFloat(r.borderTopWidth)+parseFloat(r.borderBottomWidth),r.maxHeight!==`none`&&a>parseFloat(r.maxHeight)?(r.overflowY===`hidden`&&(e.style.overflow=`scroll`),a=parseFloat(r.maxHeight)):r.overflowY!==`hidden`&&(e.style.overflow=`hidden`),e.style.height=a+`px`,c&&(e.style.textAlign=c),i&&i(),n!==a&&(e.dispatchEvent(new Event(`autosize:resized`,{bubbles:!0})),n=a),d!==r.overflow&&!c)){var f=r.textAlign;r.overflow===`hidden`&&(e.style.textAlign=f===`start`?`end`:`start`),o({restoreTextAlign:f,testForHeightReduction:!0})}}function s(){o({testForHeightReduction:!0,restoreTextAlign:null})}}(e)}),e}).destroy=function(e){return e&&Array.prototype.forEach.call(e.length?e:[e],ec),e},nc.update=function(e){return e&&Array.prototype.forEach.call(e.length?e:[e],tc),e});var rc=nc,ic=class extends No{get _inputNode(){return Array.from(this.children).find(e=>e.slot===`input`)}},ac=class extends Lo(ic){static get properties(){return{maxRows:{type:Number,attribute:`max-rows`},rows:{type:Number,reflect:!0},readOnly:{type:Boolean,attribute:`readonly`,reflect:!0},placeholder:{type:String,reflect:!0}}}get slots(){return{...super.slots,input:()=>{let e=document.createElement(`textarea`);return e.style.resize!==void 0&&(e.style.resize=`none`),e}}}constructor(){super(),this.rows=2,this.maxRows=6,this.readOnly=!1,this.placeholder=``}connectedCallback(){super.connectedCallback(),this.__initializeAutoresize(),this.__intersectionObserver=new IntersectionObserver(()=>this.resizeTextarea()),this.__intersectionObserver.observe(this)}updated(e){if(super.updated(e),e.has(`name`)&&(this._inputNode.name=this.name),e.has(`autocomplete`)&&(this._inputNode.autocomplete=this.autocomplete),e.has(`disabled`)&&(this._inputNode.disabled=this.disabled,this.validate()),e.has(`rows`)){let e=this._inputNode;e&&(e.rows=this.rows)}if(e.has(`readOnly`)){let e=this._inputNode;e&&(e.readOnly=this.readOnly)}if(e.has(`placeholder`)){let e=this._inputNode;e&&(e.placeholder=this.placeholder)}e.has(`modelValue`)&&this.resizeTextarea(),(e.has(`maxRows`)||e.has(`rows`))&&this.setTextareaMaxHeight()}disconnectedCallback(){super.disconnectedCallback(),rc.destroy(this._inputNode)}setTextareaMaxHeight(){let{value:e}=this._inputNode;this._inputNode.value=``,this.resizeTextarea();let t=window.getComputedStyle(this._inputNode,null),n=parseFloat(t.lineHeight)||parseFloat(t.height)/this.rows,r=parseFloat(t.paddingTop)+parseFloat(t.paddingBottom),i=parseFloat(t.borderTopWidth)+parseFloat(t.borderBottomWidth),a=t.boxSizing===`border-box`?r+i:0;this._inputNode.style.maxHeight=`${n*this.maxRows+a}px`,this._inputNode.value=e,this.resizeTextarea()}static get styles(){return[...super.styles,v`
        .input-group__container > .input-group__input ::slotted(.form-control) {
          box-sizing: content-box;
          overflow-x: hidden; /* for FF adds height to the TextArea to reserve place for scroll-bars */
        }

        /* Workaround https://bugzilla.mozilla.org/show_bug.cgi?id=1739079 */
        :host([disabled]) ::slotted(textarea) {
          user-select: none;
        }
      `]}get updateComplete(){return this.__textareaUpdateComplete?Promise.all([this.__textareaUpdateComplete,super.updateComplete]):super.updateComplete}resizeTextarea(){rc.update(this._inputNode)}__initializeAutoresize(){this.__shady_native_contains?this.__textareaUpdateComplete=this.__waitForTextareaRenderedInRealDOM().then(()=>{this.__startAutoresize(),this.__textareaUpdateComplete=null}):this.__startAutoresize()}async __waitForTextareaRenderedInRealDOM(){let e=3;for(;e!==0&&!this.__shady_native_contains(this._inputNode);)await new Promise(e=>setTimeout(e)),--e}__startAutoresize(){rc(this._inputNode),this.setTextareaMaxHeight()}},oc=v`
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
`,sc=class extends ac{constructor(...e){super(...e),this.monospace=!1}static get styles(){return[...super.styles,xa,oc]}};m([w({type:Boolean,reflect:!0})],sc.prototype,`monospace`,void 0),customElements.get(`craft-textarea`)||customElements.define(`craft-textarea`,sc);var cc=v`
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
`,lc=class extends C{render(){return g`<slot></slot>`}};lc.styles=[cc],customElements.get(`craft-button-group`)||customElements.define(`craft-button-group`,lc);var uc=class extends No{static get properties(){return{autocomplete:{type:String}}}constructor(){super(),this.autocomplete=void 0}get _inputNode(){return Array.from(this.children).find(e=>e.slot===`input`)}},dc=class extends uc{get operationMode(){return`select`}connectedCallback(){super.connectedCallback(),this._inputNode.addEventListener(`change`,this._proxyChangeEvent),this.__selectObserver=new MutationObserver(()=>{this._syncValueUpwards(),this._calculateValues({source:`model`})}),this.__selectObserver.observe(this._inputNode,{attributes:!0,childList:!0,subtree:!0})}updated(e){super.updated(e),e.has(`disabled`)&&(this._inputNode.disabled=this.disabled,this.validate()),e.has(`name`)&&(this._inputNode.name=this.name),e.has(`autocomplete`)&&(this._inputNode.autocomplete=this.autocomplete)}disconnectedCallback(){super.disconnectedCallback(),this._inputNode.removeEventListener(`change`,this._proxyChangeEvent),this.__selectObserver?.disconnect()}formatter(e){let t=Array.from(this._inputNode.options).find(t=>t.value===e);return t?t.text:``}_reflectBackFormattedValueToUser(){this._reflectBackOn()&&(this.value=this.modelValue===void 0?``:this.modelValue)}_proxyChangeEvent(){this.dispatchEvent(new CustomEvent(`user-input-changed`,{bubbles:!0,composed:!0}))}},fc=v`
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
`,pc=class extends dc{constructor(...e){super(...e),this.small=!1}static get styles(){return[...super.styles,fc]}_inputGroupInputTemplate(){return g`
      <div class="input-group__input">
        <slot name="input"></slot>
        <craft-icon
          class="indicator"
          name="chevron-down"
          style="font-size: 0.8em"
        ></craft-icon>
      </div>
    `}};m([w({reflect:!0,type:Boolean})],pc.prototype,`small`,void 0),customElements.get(`craft-select`)||customElements.define(`craft-select`,pc);var mc=class extends Io(C){static get properties(){return{tabIndex:{type:Number,reflect:!0,attribute:`tabindex`}}}constructor(){super(),this.tabIndex=0}connectedCallback(){super.connectedCallback(),this.setAttribute(`role`,`listbox`)}createRenderRoot(){return this}};function hc(e,t){Array.from(e.childNodes).forEach(e=>{e.hasAttribute&&e.hasAttribute(`slot`)||t.appendChild(e)})}var gc=Or(e=>class extends Eo(ho(Ro(Ur(Fo(e))))){static get properties(){return{orientation:String,selectionFollowsFocus:{type:Boolean,attribute:`selection-follows-focus`},rotateKeyboardNavigation:{type:Boolean,attribute:`rotate-keyboard-navigation`},hasNoDefaultSelected:{type:Boolean,reflect:!0,attribute:`has-no-default-selected`},_noTypeAhead:{type:Boolean}}}static get styles(){return[...super.styles||[],v`
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
        `]}_inputGroupInputTemplate(){return g`
        <div class="input-group__input">
          <slot name="input"></slot>
          <slot id="options-outlet"></slot>
        </div>
      `}static get scopedElements(){return{...super.scopedElements,"lion-options":mc}}get slots(){return{...super.slots,input:()=>{let e=this.createScopedElement(`lion-options`);return e.setAttribute(`data-tag-name`,`lion-options`),e.registrationTarget=this,e}}}get _inputNode(){return this.querySelector(`[slot="input"]`)}get _listboxNode(){return this._inputNode}get _listboxActiveDescendantNode(){return this._listboxNode.querySelector(`#${this._listboxActiveDescendant}`)}get _listboxSlot(){return this.shadowRoot.querySelector(`slot[name=input]`)}get _scrollTargetNode(){return this._listboxNode}get _activeDescendantOwnerNode(){return this._listboxNode}get activeIndex(){return this.formElements.findIndex(e=>e.active===!0)}set activeIndex(e){if(this.formElements[e]){let t=this.formElements[e];this.__setChildActive(t)}else this.__setChildActive(null)}get checkedIndex(){let e=this.formElements;return this.multipleChoice?e.filter(e=>e.checked).map(t=>e.indexOf(t)):e.indexOf(e.find(e=>e.checked))}set checkedIndex(e){this.setCheckedIndex(e)}constructor(){super(),this.hasNoDefaultSelected=!1,this.orientation=`vertical`,this.rotateKeyboardNavigation=!1,this.selectionFollowsFocus=!1,this._noTypeAhead=!1,this._typeAheadTimeout=1e3,this._listboxActiveDescendant=null,this.__hasInitialSelectedFormElement=!1,this._repropagationRole=`choice-group`,this._listboxReceivesNoFocus=!1,this._oldModelValue=void 0,this._listboxOnKeyDown=this._listboxOnKeyDown.bind(this),this._listboxOnClick=this._listboxOnClick.bind(this),this._listboxOnKeyUp=this._listboxOnKeyUp.bind(this),this._onChildActiveChanged=this._onChildActiveChanged.bind(this),this.__proxyChildModelValueChanged=this.__proxyChildModelValueChanged.bind(this),this.__preventScrollingWithArrowKeys=this.__preventScrollingWithArrowKeys.bind(this),this.__typedChars=[]}connectedCallback(){this._listboxNode&&(this._listboxNode.registrationTarget=this),super.connectedCallback(),this._setupListboxNode(),this.__setupEventListeners(),this.registrationComplete.then(()=>{this.__initInteractionStates()})}firstUpdated(e){super.firstUpdated(e),this.__moveOptionsToListboxNode(),this.registrationComplete.then(()=>{this._initialModelValue=this.modelValue}),new MutationObserver(()=>{this._onListboxContentChanged()}).observe(this._listboxNode,{childList:!0})}updated(e){super.updated(e),e.has(`disabled`)&&(this.disabled?this.__requestOptionsToBeDisabled():this.__retractRequestOptionsToBeDisabled())}disconnectedCallback(){super.disconnectedCallback(),this._teardownListboxNode(),this.__teardownEventListeners()}setCheckedIndex(e){if(this.multipleChoice&&Array.isArray(e)){this._uncheckChildren(this.formElements.filter(t=>t===e)),e.forEach(e=>{this.formElements[e]&&(this.formElements[e].checked=!this.formElements[e].checked)});return}typeof e==`number`&&(e===-1&&this._uncheckChildren(),this.formElements[e]&&(this.formElements[e].disabled?this._uncheckChildren():this.multipleChoice?this.formElements[e].checked=!this.formElements[e].checked:this.formElements[e].checked=!0))}addFormElement(e,t){super.addFormElement(e,t),e.id=e.id||`${this.localName}-option-${Kr()}`,this.disabled&&e.makeRequestToBeDisabled(),this.__setAttributeForAllFormElements(`aria-setsize`,this.formElements.length),this.formElements.forEach((e,t)=>{e.setAttribute(`aria-posinset`,t+1)}),this.__proxyChildModelValueChanged({target:e}),this.resetInteractionState()}resetInteractionState(){super.resetInteractionState(),this.submitted=!1}reset(){this.modelValue=this._initialModelValue,this.activeIndex=-1,this.resetInteractionState()}clear(){super.clear(),this.setCheckedIndex(-1),this.resetInteractionState()}_handleTypeAhead(e,{setAsChecked:t}){let{key:n,code:r}=e;if(r.startsWith(`Key`)||r.startsWith(`Digit`)||r.startsWith(`Numpad`)){e.preventDefault(),this.__typedChars.push(n);let r=this.__typedChars.join(``),i=this.formElements.findIndex(e=>e.modelValue.value.toLowerCase().startsWith(r));i>=0&&(t&&this.setCheckedIndex(i),this.activeIndex=i),this.__pendingTypeAheadTimeout&&window.clearTimeout(this.__pendingTypeAheadTimeout),this.__pendingTypeAheadTimeout=setTimeout(()=>{this.__typedChars=[]},this._typeAheadTimeout)}}_getCheckedElements(){return this.formElements.filter(e=>e.checked)}_setupListboxNode(){this._listboxNode?this.__setupListboxNodeInteractions():this._listboxSlot&&this._listboxSlot.addEventListener(`slotchange`,()=>{this.__setupListboxNodeInteractions()})}_onListboxContentChanged(){}_teardownListboxNode(){this._listboxNode&&(this._listboxNode.removeEventListener(`keydown`,this._listboxOnKeyDown),this._listboxNode.removeEventListener(`click`,this._listboxOnClick),this._listboxNode.removeEventListener(`keyup`,this._listboxOnKeyUp))}_getNextEnabledOption(e,t=1){return this.__getEnabledOption(e,t)}_getPreviousEnabledOption(e,t=-1){return this.__getEnabledOption(e,t)}_onChildActiveChanged({target:e}){e.active===!0&&this.__setChildActive(e)}_listboxOnKeyDown(e){if(this.disabled)return;this._isHandlingUserInput=!0,setTimeout(()=>{this._isHandlingUserInput=!1});let{key:t}=e;switch(t){case` `:case`Enter`:if(t===` `&&this._listboxReceivesNoFocus||(t===` `&&e.preventDefault(),!this.formElements[this.activeIndex])||this.formElements[this.activeIndex].disabled)return;this.formElements[this.activeIndex].href&&this.formElements[this.activeIndex].click(),this.setCheckedIndex(this.activeIndex);break;case`ArrowUp`:e.preventDefault(),this.orientation===`vertical`&&(this.activeIndex=this._getPreviousEnabledOption(this.activeIndex));break;case`ArrowLeft`:if(this._listboxReceivesNoFocus)return;e.preventDefault(),this.orientation===`horizontal`&&(this.activeIndex=this._getPreviousEnabledOption(this.activeIndex));break;case`ArrowDown`:e.preventDefault(),this.orientation===`vertical`&&(this.activeIndex=this._getNextEnabledOption(this.activeIndex));break;case`ArrowRight`:if(this._listboxReceivesNoFocus)return;e.preventDefault(),this.orientation===`horizontal`&&(this.activeIndex=this._getNextEnabledOption(this.activeIndex));break;case`Home`:if(this._listboxReceivesNoFocus)return;e.preventDefault(),this.activeIndex=this._getNextEnabledOption(0,0);break;case`End`:if(this._listboxReceivesNoFocus)return;e.preventDefault(),this.activeIndex=this._getPreviousEnabledOption(this.formElements.length-1,0);break;default:this._noTypeAhead||this._handleTypeAhead(e,{setAsChecked:this.selectionFollowsFocus&&!this.multipleChoice})}[`ArrowUp`,`ArrowDown`,`ArrowLeft`,`ArrowRight`,`Home`,`End`].includes(t)&&this.selectionFollowsFocus&&!this.multipleChoice&&this.setCheckedIndex(this.activeIndex)}_listboxOnClick(e){}_listboxOnKeyUp(e){if(this.disabled)return;this._isHandlingUserInput=!0,setTimeout(()=>{this._isHandlingUserInput=!1});let{key:t}=e;switch(t){case`ArrowUp`:case`ArrowDown`:case`Home`:case`End`:case`Enter`:e.preventDefault()}}_onLabelClick(){this._listboxNode.focus()}_scrollIntoView(e,t){e.scrollIntoView({behavior:`smooth`,block:`nearest`})}__setupEventListeners(){this._listboxNode.addEventListener(`active-changed`,this._onChildActiveChanged),this._listboxNode.addEventListener(`model-value-changed`,this.__proxyChildModelValueChanged)}__teardownEventListeners(){this._listboxNode.removeEventListener(`active-changed`,this._onChildActiveChanged),this._listboxNode.removeEventListener(`model-value-changed`,this.__proxyChildModelValueChanged)}__setChildActive(e){if(this.formElements.forEach(t=>{t.active=e===t}),!e){this._activeDescendantOwnerNode.removeAttribute(`aria-activedescendant`);return}this._activeDescendantOwnerNode.setAttribute(`aria-activedescendant`,e.id),this._scrollIntoView(e,this._scrollTargetNode)}_uncheckChildren(e=[]){let t=Array.isArray(e)?e:[e];this.formElements.forEach(e=>{t.includes(e)||(e.checked=!1)})}__onChildCheckedChanged(e){let{target:t}=e;e.stopPropagation&&e.stopPropagation(),t.checked&&!this.multipleChoice&&this._uncheckChildren(t)}__setAttributeForAllFormElements(e,t){this.formElements.forEach(n=>{n.setAttribute(e,t)})}__proxyChildModelValueChanged(e){e.stopPropagation&&e.stopPropagation(),this.__onChildCheckedChanged(e),this.requestUpdate(`modelValue`,this._oldModelValue),e.detail&&e.detail.formPath&&this.dispatchEvent(new CustomEvent(`model-value-changed`,{detail:{formPath:e.detail.formPath,isTriggeredByUser:e.detail.isTriggeredByUser||this._isHandlingUserInput,element:e.target}})),this._oldModelValue=this.modelValue}__getEnabledOption(e,t){let n=e=>t===1?e<this.formElements.length:e>=0;for(let r=e+t;n(r);r+=t)if(this.formElements[r]&&!this.formElements[r].hasAttribute(`aria-hidden`))return r;if(this.rotateKeyboardNavigation){let e=t===-1?this.formElements.length-1:0;for(let r=e;n(r);r+=t)if(this.formElements[r]&&!this.formElements[r].hasAttribute(`aria-hidden`))return r}return e}__moveOptionsToListboxNode(){let e=this.shadowRoot.getElementById(`options-outlet`);e&&(hc(this,this._listboxNode),e.addEventListener(`slotchange`,()=>{hc(this,this._listboxNode)}))}__preventScrollingWithArrowKeys(e){if(this.disabled)return;let{key:t}=e;switch(t){case`ArrowUp`:case`ArrowDown`:case`Home`:case`End`:e.preventDefault()}}__setupListboxNodeInteractions(){this._listboxNode.setAttribute(`role`,`listbox`),this._listboxNode.setAttribute(`aria-orientation`,this.orientation),this._listboxNode.setAttribute(`aria-multiselectable`,`${this.multipleChoice}`),this._listboxNode.setAttribute(`tabindex`,`0`),this._listboxNode.addEventListener(`click`,this._listboxOnClick),this._listboxNode.addEventListener(`keyup`,this._listboxOnKeyUp),this._listboxNode.addEventListener(`keydown`,this._listboxOnKeyDown),this._scrollTargetNode.addEventListener(`keydown`,this.__preventScrollingWithArrowKeys)}__requestOptionsToBeDisabled(){this.formElements.forEach(e=>{e.makeRequestToBeDisabled&&e.makeRequestToBeDisabled()})}__retractRequestOptionsToBeDisabled(){this.formElements.forEach(e=>{e.retractRequestToBeDisabled&&e.retractRequestToBeDisabled()})}__initInteractionStates(){this.initInteractionState()}}),_c=class extends gc(Ta(Mo(Ao(C)))){get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,focused:this.focused}}get _focusableNode(){return this._inputNode}},vc=class extends kr(Bo(To(Ur(C)))){static get properties(){return{active:{type:Boolean,reflect:!0}}}static get styles(){return[v`
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
      `]}get slots(){return{}}constructor(){super(),this.active=!1,this.__onClick=this.__onClick.bind(this),this.__registerEventListeners()}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`active`&&this.active!==t&&this.dispatchEvent(new Event(`active-changed`,{bubbles:!0}))}updated(e){super.updated(e),e.has(`checked`)&&this.setAttribute(`aria-selected`,`${this.checked}`),e.has(`disabled`)&&this.setAttribute(`aria-disabled`,`${this.disabled}`)}render(){return g`
      <div class="choice-field__label">
        <slot></slot>
      </div>
    `}connectedCallback(){super.connectedCallback(),this.setAttribute(`role`,`option`)}__registerEventListeners(){this.addEventListener(`click`,this.__onClick)}__unRegisterEventListeners(){this.removeEventListener(`click`,this.__onClick)}__onClick(){if(this.disabled)return;let e=this._parentFormGroup;this._isHandlingUserInput=!0,e&&e.multipleChoice?(this.checked=!this.checked,this.active=!this.active):(this.checked=!0,this.active=!0),this._isHandlingUserInput=!1}},yc=v`
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
`,bc=new WeakMap,xc=class extends vc{static get styles(){return[...vc.styles,yc]}constructor(){super(),this.hint=null,u(this,bc,640),r(bc,this,parseInt(getComputedStyle(this).getPropertyValue(`--c-option-wide-threshold`)||`640`,10))}connectedCallback(){super.connectedCallback();let e=this.getBoundingClientRect().width??0;this.toggleAttribute(`wide`,e>=p(bc,this))}render(){return g`
      <div class="choice-field__label">
        <slot></slot>
        ${this.hint?g`<span class="hint">${this.hint}</span>`:S}
        <slot name="suffix"></slot>
      </div>
    `}};m([w()],xc.prototype,`hint`,void 0),customElements.get(`craft-option`)||customElements.define(`craft-option`,xc);var Sc=`@layer wa-utilities {
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
`,Ec=new Set,Dc=class extends rt{constructor(){super(...arguments),this.submenuCleanups=new Map,this.localize=new Oe(this),this.userTypedQuery=``,this.openSubmenuStack=[],this.open=!1,this.size=`medium`,this.placement=`bottom-start`,this.distance=0,this.skidding=0,this.handleDocumentKeyDown=async e=>{let t=this.localize.dir()===`rtl`;if(e.key===`Escape`){let t=this.getTrigger();e.preventDefault(),e.stopPropagation(),this.open=!1,t?.focus();return}let n=[...wc()].find(e=>e.localName===`wa-dropdown-item`),r=n?.localName===`wa-dropdown-item`,i=this.getCurrentSubmenuItem(),a=!!i,o,s,c;a?(o=this.getSubmenuItems(i),s=o.find(e=>e.active||e===n),c=s?o.indexOf(s):-1):(o=this.getItems(),s=o.find(e=>e.active||e===n),c=s?o.indexOf(s):-1);let l;if(e.key===`ArrowUp`&&(e.preventDefault(),e.stopPropagation(),l=c>0?o[c-1]:o[o.length-1]),e.key===`ArrowDown`&&(e.preventDefault(),e.stopPropagation(),l=c!==-1&&c<o.length-1?o[c+1]:o[0]),e.key===(t?`ArrowLeft`:`ArrowRight`)&&r&&s&&s.hasSubmenu){e.preventDefault(),e.stopPropagation(),s.submenuOpen=!0,this.addToSubmenuStack(s),setTimeout(()=>{let e=this.getSubmenuItems(s);e.length>0&&(e.forEach((e,t)=>e.active=t===0),e[0].focus())},0);return}if(e.key===(t?`ArrowRight`:`ArrowLeft`)&&a){e.preventDefault(),e.stopPropagation();let t=this.removeFromSubmenuStack();t&&(t.submenuOpen=!1,setTimeout(()=>{t.focus(),t.active=!0,(t.slot===`submenu`?this.getSubmenuItems(t.parentElement):this.getItems()).forEach(e=>{e!==t&&(e.active=!1)})},0));return}if((e.key===`Home`||e.key===`End`)&&(e.preventDefault(),e.stopPropagation(),l=e.key===`Home`?o[0]:o[o.length-1]),e.key===`Tab`&&await this.hideMenu(),e.key.length===1&&!(e.metaKey||e.ctrlKey||e.altKey)&&!(e.key===` `&&this.userTypedQuery===``)&&(clearTimeout(this.userTypedTimeout),this.userTypedTimeout=setTimeout(()=>{this.userTypedQuery=``},1e3),this.userTypedQuery+=e.key,o.some(e=>{let t=(e.textContent||``).trim().toLowerCase(),n=this.userTypedQuery.trim().toLowerCase();return t.startsWith(n)?(l=e,!0):!1})),l){e.preventDefault(),e.stopPropagation(),o.forEach(e=>e.active=e===l),l.focus();return}(e.key===`Enter`||e.key===` `&&this.userTypedQuery===``)&&r&&s&&(e.preventDefault(),e.stopPropagation(),s.hasSubmenu?(s.submenuOpen=!0,this.addToSubmenuStack(s),setTimeout(()=>{let e=this.getSubmenuItems(s);e.length>0&&(e.forEach((e,t)=>e.active=t===0),e[0].focus())},0)):this.makeSelection(s))},this.handleDocumentPointerDown=e=>{e.composedPath().some(e=>e instanceof HTMLElement?e===this||e.closest(`wa-dropdown, [part="submenu"]`):!1)||(this.open=!1)},this.handleGlobalMouseMove=e=>{let t=this.getCurrentSubmenuItem();if(!t?.submenuOpen||!t.submenuElement)return;let n=t.submenuElement.getBoundingClientRect(),r=this.localize.dir()===`rtl`,i=r?n.right:n.left,a=r?Math.max(e.clientX,i):Math.min(e.clientX,i),o=Math.max(n.top,Math.min(e.clientY,n.bottom));t.submenuElement.style.setProperty(`--safe-triangle-cursor-x`,`${a}px`),t.submenuElement.style.setProperty(`--safe-triangle-cursor-y`,`${o}px`);let s=t.matches(`:hover`),c=t.submenuElement?.matches(`:hover`)||!!e.composedPath().find(e=>e instanceof HTMLElement&&e.closest(`[part="submenu"]`)===t.submenuElement);!s&&!c&&setTimeout(()=>{!t.matches(`:hover`)&&!t.submenuElement?.matches(`:hover`)&&(t.submenuOpen=!1)},100)}}disconnectedCallback(){super.disconnectedCallback(),clearInterval(this.userTypedTimeout),this.closeAllSubmenus(),this.submenuCleanups.forEach(e=>e()),this.submenuCleanups.clear(),document.removeEventListener(`mousemove`,this.handleGlobalMouseMove)}firstUpdated(){this.syncAriaAttributes()}async updated(e){e.has(`open`)&&(this.customStates.set(`open`,this.open),this.open?await this.showMenu():(this.closeAllSubmenus(),await this.hideMenu())),e.has(`size`)&&this.syncItemSizes()}getItems(e=!1){let t=this.defaultSlot.assignedElements({flatten:!0}).filter(e=>e.localName===`wa-dropdown-item`);return e?t:t.filter(e=>!e.disabled)}getSubmenuItems(e,t=!1){let n=e.shadowRoot?.querySelector(`slot[name="submenu"]`)||e.querySelector(`slot[name="submenu"]`);if(!n)return[];let r=n.assignedElements({flatten:!0}).filter(e=>e.localName===`wa-dropdown-item`);return t?r:r.filter(e=>!e.disabled)}syncItemSizes(){this.defaultSlot.assignedElements({flatten:!0}).filter(e=>e.localName===`wa-dropdown-item`).forEach(e=>e.size=this.size)}addToSubmenuStack(e){let t=this.openSubmenuStack.indexOf(e);t===-1?this.openSubmenuStack.push(e):this.openSubmenuStack=this.openSubmenuStack.slice(0,t+1)}removeFromSubmenuStack(){return this.openSubmenuStack.pop()}getCurrentSubmenuItem(){return this.openSubmenuStack.length>0?this.openSubmenuStack[this.openSubmenuStack.length-1]:void 0}closeAllSubmenus(){this.getItems(!0).forEach(e=>{e.submenuOpen=!1}),this.openSubmenuStack=[]}closeSiblingSubmenus(e){let t=e.closest(`wa-dropdown-item:not([slot="submenu"])`),n;n=t?this.getSubmenuItems(t,!0):this.getItems(!0),n.forEach(t=>{t!==e&&t.submenuOpen&&(t.submenuOpen=!1)}),this.openSubmenuStack.includes(e)||this.openSubmenuStack.push(e)}getTrigger(){return this.querySelector(`[slot="trigger"]`)}async showMenu(){if(!this.getTrigger())return;let e=new ur;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}Ec.forEach(e=>e.open=!1),this.popup.active=!0,this.open=!0,Ec.add(this),this.syncAriaAttributes(),document.addEventListener(`keydown`,this.handleDocumentKeyDown),document.addEventListener(`pointerdown`,this.handleDocumentPointerDown),document.addEventListener(`mousemove`,this.handleGlobalMouseMove),this.menu.classList.remove(`hide`),await hr(this.menu,`show`);let t=this.getItems();t.length>0&&(t.forEach((e,t)=>e.active=t===0),t[0].focus()),this.dispatchEvent(new cr)}async hideMenu(){let e=new lr({source:this});if(this.dispatchEvent(e),e.defaultPrevented){this.open=!0;return}this.open=!1,Ec.delete(this),this.syncAriaAttributes(),document.removeEventListener(`keydown`,this.handleDocumentKeyDown),document.removeEventListener(`pointerdown`,this.handleDocumentPointerDown),document.removeEventListener(`mousemove`,this.handleGlobalMouseMove),this.menu.classList.remove(`show`),await hr(this.menu,`hide`),this.popup.active=this.open,this.dispatchEvent(new sr)}handleMenuClick(e){let t=e.target.closest(`wa-dropdown-item`);if(!(!t||t.disabled)){if(t.hasSubmenu){t.submenuOpen||=(this.closeSiblingSubmenus(t),this.addToSubmenuStack(t),!0),e.stopPropagation();return}this.makeSelection(t)}}async handleMenuSlotChange(){let e=this.getItems(!0);await Promise.all(e.map(e=>e.updateComplete)),this.syncItemSizes();let t=e.some(e=>e.type===`checkbox`),n=e.some(e=>e.hasSubmenu);e.forEach((e,r)=>{e.active=r===0,e.checkboxAdjacent=t,e.submenuAdjacent=n})}handleTriggerClick(){this.open=!this.open}handleSubmenuOpening(e){let t=e.detail.item;this.closeSiblingSubmenus(t),this.addToSubmenuStack(t),this.setupSubmenuPosition(t),this.processSubmenuItems(t)}setupSubmenuPosition(e){if(!e.submenuElement)return;this.cleanupSubmenuPosition(e);let t=Jn(e,e.submenuElement,()=>{this.positionSubmenu(e),this.updateSafeTriangleCoordinates(e)});this.submenuCleanups.set(e,t);let n=e.submenuElement.querySelector(`slot[name="submenu"]`);n&&(n.removeEventListener(`slotchange`,Dc.handleSubmenuSlotChange),n.addEventListener(`slotchange`,Dc.handleSubmenuSlotChange),Dc.handleSubmenuSlotChange({target:n}))}static handleSubmenuSlotChange(e){let t=e.target;if(!t)return;let n=t.assignedElements().filter(e=>e.localName===`wa-dropdown-item`);if(n.length===0)return;let r=n.some(e=>e.hasSubmenu),i=n.some(e=>e.type===`checkbox`);n.forEach(e=>{e.submenuAdjacent=r,e.checkboxAdjacent=i})}processSubmenuItems(e){if(!e.submenuElement)return;let t=this.getSubmenuItems(e,!0),n=t.some(e=>e.hasSubmenu);t.forEach(e=>{e.submenuAdjacent=n})}cleanupSubmenuPosition(e){let t=this.submenuCleanups.get(e);t&&(t(),this.submenuCleanups.delete(e))}positionSubmenu(e){if(!e.submenuElement)return;let t=this.localize.dir()===`rtl`?`left-start`:`right-start`;er(e,e.submenuElement,{placement:t,middleware:[Yn({mainAxis:0,crossAxis:-5}),Zn({fallbackStrategy:`bestFit`}),Xn({padding:8})]}).then(({x:t,y:n,placement:r})=>{e.submenuElement.setAttribute(`data-placement`,r),Object.assign(e.submenuElement.style,{left:`${t}px`,top:`${n}px`})})}updateSafeTriangleCoordinates(e){if(!e.submenuElement||!e.submenuOpen)return;if(document.activeElement?.matches(`:focus-visible`)){e.submenuElement.style.setProperty(`--safe-triangle-visible`,`none`);return}e.submenuElement.style.setProperty(`--safe-triangle-visible`,`block`);let t=e.submenuElement.getBoundingClientRect(),n=this.localize.dir()===`rtl`;e.submenuElement.style.setProperty(`--safe-triangle-submenu-start-x`,`${n?t.right:t.left}px`),e.submenuElement.style.setProperty(`--safe-triangle-submenu-start-y`,`${t.top}px`),e.submenuElement.style.setProperty(`--safe-triangle-submenu-end-x`,`${n?t.right:t.left}px`),e.submenuElement.style.setProperty(`--safe-triangle-submenu-end-y`,`${t.bottom}px`)}makeSelection(e){let t=this.getTrigger();if(e.disabled)return;e.type===`checkbox`&&(e.checked=!e.checked);let n=new Cc({item:e});this.dispatchEvent(n),n.defaultPrevented||(this.open=!1,t?.focus())}async syncAriaAttributes(){let e=this.getTrigger(),t;e&&(e.localName===`wa-button`?(await customElements.whenDefined(`wa-button`),await e.updateComplete,t=e.shadowRoot.querySelector(`[part="base"]`)):t=e,t.hasAttribute(`id`)||t.setAttribute(`id`,pr(`wa-dropdown-trigger-`)),t.setAttribute(`aria-haspopup`,`menu`),t.setAttribute(`aria-expanded`,this.open?`true`:`false`),this.menu.setAttribute(`aria-expanded`,`false`))}render(){let e=this.hasUpdated?this.popup.active:this.open;return g`
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
    `}};Dc.css=[Sc,Tc],M([D(`slot:not([name])`)],Dc.prototype,`defaultSlot`,2),M([D(`#menu`)],Dc.prototype,`menu`,2),M([D(`wa-popup`)],Dc.prototype,`popup`,2),M([w({type:Boolean,reflect:!0})],Dc.prototype,`open`,2),M([w({reflect:!0})],Dc.prototype,`size`,2),M([w({reflect:!0})],Dc.prototype,`placement`,2),M([w({type:Number})],Dc.prototype,`distance`,2),M([w({type:Number})],Dc.prototype,`skidding`,2),Dc=M([E(`wa-dropdown`)],Dc);var Oc=class{constructor(e,...t){this.slotNames=[],this.handleSlotChange=e=>{let t=e.target;(this.slotNames.includes(`[default]`)&&!t.name||t.name&&this.slotNames.includes(t.name))&&this.host.requestUpdate()},(this.host=e).addController(this),this.slotNames=t}hasDefaultSlot(){return[...this.host.childNodes].some(e=>{if(e.nodeType===Node.TEXT_NODE&&e.textContent.trim()!==``)return!0;if(e.nodeType===Node.ELEMENT_NODE){let t=e;if(t.tagName.toLowerCase()===`wa-visually-hidden`)return!1;if(!t.hasAttribute(`slot`))return!0}return!1})}hasNamedSlot(e){return this.host.querySelector(`:scope > [slot="${e}"]`)!==null}test(e){return e===`[default]`?this.hasDefaultSlot():this.hasNamedSlot(e)}hostConnected(){this.host.shadowRoot.addEventListener(`slotchange`,this.handleSlotChange)}hostDisconnected(){this.host.shadowRoot.removeEventListener(`slotchange`,this.handleSlotChange)}},kc=`:host {
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
`,Ac=class extends rt{constructor(){super(...arguments),this.hasSlotController=new Oc(this,`[default]`,`start`,`end`),this.active=!1,this.variant=`default`,this.size=`medium`,this.checkboxAdjacent=!1,this.submenuAdjacent=!1,this.type=`normal`,this.checked=!1,this.disabled=!1,this.submenuOpen=!1,this.hasSubmenu=!1,this.handleSlotChange=()=>{this.hasSubmenu=this.hasSlotController.test(`submenu`),this.updateHasSubmenuState(),this.hasSubmenu?(this.setAttribute(`aria-haspopup`,`menu`),this.setAttribute(`aria-expanded`,this.submenuOpen?`true`:`false`)):(this.removeAttribute(`aria-haspopup`),this.removeAttribute(`aria-expanded`))}}connectedCallback(){super.connectedCallback(),this.addEventListener(`mouseenter`,this.handleMouseEnter.bind(this)),this.shadowRoot.addEventListener(`slotchange`,this.handleSlotChange)}disconnectedCallback(){super.disconnectedCallback(),this.closeSubmenu(),this.removeEventListener(`mouseenter`,this.handleMouseEnter),this.shadowRoot.removeEventListener(`slotchange`,this.handleSlotChange)}firstUpdated(){this.setAttribute(`tabindex`,`-1`),this.hasSubmenu=this.hasSlotController.test(`submenu`),this.updateHasSubmenuState()}updated(e){e.has(`active`)&&(this.setAttribute(`tabindex`,this.active?`0`:`-1`),this.customStates.set(`active`,this.active)),e.has(`checked`)&&(this.setAttribute(`aria-checked`,this.checked?`true`:`false`),this.customStates.set(`checked`,this.checked)),e.has(`disabled`)&&(this.setAttribute(`aria-disabled`,this.disabled?`true`:`false`),this.customStates.set(`disabled`,this.disabled)),e.has(`type`)&&(this.type===`checkbox`?this.setAttribute(`role`,`menuitemcheckbox`):this.setAttribute(`role`,`menuitem`)),e.has(`submenuOpen`)&&(this.customStates.set(`submenu-open`,this.submenuOpen),this.submenuOpen?this.openSubmenu():this.closeSubmenu())}updateHasSubmenuState(){this.customStates.set(`has-submenu`,this.hasSubmenu)}async openSubmenu(){!this.hasSubmenu||!this.submenuElement||(this.notifyParentOfOpening(),this.submenuElement.showPopover(),this.submenuElement.hidden=!1,this.submenuElement.setAttribute(`data-visible`,``),this.submenuOpen=!0,this.setAttribute(`aria-expanded`,`true`),await hr(this.submenuElement,`show`),setTimeout(()=>{let e=this.getSubmenuItems();e.length>0&&(e.forEach((e,t)=>e.active=t===0),e[0].focus())},0))}notifyParentOfOpening(){let e=new CustomEvent(`submenu-opening`,{bubbles:!0,composed:!0,detail:{item:this}});this.dispatchEvent(e);let t=this.parentElement;t&&[...t.children].filter(e=>e!==this&&e.localName===`wa-dropdown-item`&&e.getAttribute(`slot`)===this.getAttribute(`slot`)&&e.submenuOpen).forEach(e=>{e.submenuOpen=!1})}async closeSubmenu(){!this.hasSubmenu||!this.submenuElement||(this.submenuOpen=!1,this.setAttribute(`aria-expanded`,`false`),this.submenuElement.hidden||(await hr(this.submenuElement,`hide`),this.submenuElement.hidden=!0,this.submenuElement.removeAttribute(`data-visible`),this.submenuElement.hidePopover()))}getSubmenuItems(){return[...this.children].filter(e=>e.localName===`wa-dropdown-item`&&e.getAttribute(`slot`)===`submenu`&&!e.hasAttribute(`disabled`))}handleMouseEnter(){this.hasSubmenu&&!this.disabled&&(this.notifyParentOfOpening(),this.submenuOpen=!0)}render(){return g`
      ${this.type===`checkbox`?g`
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

      ${this.hasSubmenu?g`
            <wa-icon
              id="submenu-indicator"
              part="submenu-icon"
              exportparts="svg:submenu-icon__svg"
              library="system"
              name="chevron-right"
            ></wa-icon>
          `:``}
      ${this.hasSubmenu?g`
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
    `}};Ac.css=kc,M([D(`#submenu`)],Ac.prototype,`submenuElement`,2),M([w({type:Boolean})],Ac.prototype,`active`,2),M([w({reflect:!0})],Ac.prototype,`variant`,2),M([w({reflect:!0})],Ac.prototype,`size`,2),M([w({attribute:`checkbox-adjacent`,type:Boolean,reflect:!0})],Ac.prototype,`checkboxAdjacent`,2),M([w({attribute:`submenu-adjacent`,type:Boolean,reflect:!0})],Ac.prototype,`submenuAdjacent`,2),M([w()],Ac.prototype,`value`,2),M([w({reflect:!0})],Ac.prototype,`type`,2),M([w({type:Boolean})],Ac.prototype,`checked`,2),M([w({type:Boolean,reflect:!0})],Ac.prototype,`disabled`,2),M([w({type:Boolean,reflect:!0})],Ac.prototype,`submenuOpen`,2),M([T()],Ac.prototype,`hasSubmenu`,2),Ac=M([E(`wa-dropdown-item`)],Ac);var jc=class extends Dc{static get styles(){return[Dc.styles,v`
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
      `]}},Mc=class extends Ac{static get styles(){return[Ac.styles,v`
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
      `]}};customElements.get(`craft-dropdown`)||customElements.define(`craft-dropdown`,jc),customElements.get(`craft-dropdown-item`)||customElements.define(`craft-dropdown-item`,Mc);function Nc({el:e,uid:t}){e.setAttribute(`id`,`panel-${t}`),e.setAttribute(`role`,`tabpanel`),e.setAttribute(`aria-labelledby`,`button-${t}`),e.hasAttribute(`tabindex`)||e.setAttribute(`tabindex`,`0`)}function Pc(e){e.setAttribute(`selected`,`true`)}function Fc(e){e.removeAttribute(`selected`)}function Ic({el:e,uid:t,clickHandler:n,keydownHandler:r,keyupHandler:i}){e.setAttribute(`id`,`button-${t}`),e.setAttribute(`role`,`tab`),e.setAttribute(`aria-controls`,`panel-${t}`),e.addEventListener(`click`,n),e.addEventListener(`keyup`,i),e.addEventListener(`keydown`,r)}function Lc({el:e,clickHandler:t,keydownHandler:n,keyupHandler:r}){e.removeAttribute(`id`),e.removeAttribute(`role`),e.removeAttribute(`aria-controls`),e.removeEventListener(`click`,t),e.removeEventListener(`keyup`,r),e.removeEventListener(`keydown`,n)}function Rc(e,t=!1){t&&e.focus(),e.setAttribute(`selected`,`true`),e.setAttribute(`aria-selected`,`true`),e.setAttribute(`tabindex`,`0`)}function zc(e){e.removeAttribute(`selected`),e.setAttribute(`aria-selected`,`false`),e.setAttribute(`tabindex`,`-1`)}function Bc(e){let t=e;switch(t.key){case`ArrowDown`:case`ArrowRight`:case`ArrowUp`:case`ArrowLeft`:case`Home`:case`End`:t.preventDefault()}}var Vc=class extends C{static get properties(){return{selectedIndex:{type:Number,attribute:`selected-index`,reflect:!0}}}static get styles(){return[v`
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
      `]}render(){return g`
      <div class="tabs__tab-group" role="tablist">
        <slot name="tab"></slot>
      </div>
      <div class="tabs__panels">
        <slot name="panel"></slot>
      </div>
    `}constructor(){super(),this.selectedIndex=0}firstUpdated(e){super.firstUpdated(e),this.__setupSlots(),this.tabs[0]?.disabled&&(this.selectedIndex=this.tabs.findIndex(e=>!e.disabled))}get tabs(){return Array.from(this.children).filter(e=>e.slot===`tab`)}get panels(){return Array.from(this.children).filter(e=>e.slot===`panel`)}static enabledWarnings=super.enabledWarnings?.filter(e=>e!==`change-in-update`)||[];__setupSlots(){if(this.shadowRoot){let e=this.shadowRoot.querySelector(`slot[name=tab]`);e&&e.addEventListener(`slotchange`,()=>{this.__cleanStore(),this.__setupStore(),this.__updateSelected(!1)})}}__setupStore(){this.__store=[],this.tabs.length!==this.panels.length&&console.warn(`The amount of tabs (${this.tabs.length}) doesn't match the amount of panels (${this.panels.length}).`),this.tabs.forEach((e,t)=>{let n={uid:Kr(),el:e,button:e,panel:this.panels[t],clickHandler:this.__createButtonClickHandler(t),keydownHandler:Bc.bind(this),keyupHandler:this.__handleButtonKeyup.bind(this)};Nc({...n,el:n.panel}),Ic(n),Fc(n.panel),zc(n.button),this.__store&&this.__store.push(n)})}__cleanStore(){this.__store&&=(this.__store.forEach(e=>{Lc(e)}),[])}__getNextNotDisabledTab(e,t,n){let r=[],i=e.filter((e,t)=>!e.disabled&&t>this.selectedIndex),a=e.filter((e,t)=>!e.disabled&&t<this.selectedIndex);return r=n===`right`?[...i,...a]:[...a.reverse(),...i.reverse()],r[0]}__getNextAvailableIndex(e,t){let n=this.tabs[this.selectedIndex];if(this.tabs.every(e=>!e.disabled))return e;if(t===`ArrowRight`||t===`ArrowDown`){let e=this.__getNextNotDisabledTab(this.tabs,n,`right`);return this.tabs.findIndex(t=>e===t)}if(t===`ArrowLeft`||t===`ArrowUp`){let e=this.__getNextNotDisabledTab(this.tabs,n,`left`);return this.tabs.findIndex(t=>e===t)}if(t===`Home`)return this.tabs.findIndex(e=>!e.disabled);if(t===`End`){let e=this.tabs.map((e,t)=>({disabled:e.disabled,index:t})).filter(e=>!e.disabled);return e[e.length-1].index}return-1}__createButtonClickHandler(e){return()=>{this._setSelectedIndexWithFocus(e)}}__handleButtonKeyup(e){let t=e;if(typeof this.selectedIndex==`number`)switch(t.key){case`ArrowDown`:case`ArrowRight`:this.selectedIndex+1>=this._pairCount?this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(0,t.key)):this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this.selectedIndex+1,t.key));break;case`ArrowUp`:case`ArrowLeft`:this.selectedIndex<=0?this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this._pairCount-1,t.key)):this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this.selectedIndex-1,t.key));break;case`Home`:this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(0,t.key));break;case`End`:this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this._pairCount-1,t.key));break}}get selectedIndex(){return this.__selectedIndex||0}set selectedIndex(e){if(e===this.__selectedIndex)return;let t=this.__selectedIndex;this.__selectedIndex=e,this.__updateSelected(!1),this.dispatchEvent(new Event(`selected-changed`)),this.requestUpdate(`selectedIndex`,t)}_setSelectedIndexWithFocus(e){if(e===-1)return;let t=this.__selectedIndex;this.__selectedIndex=e,this.__updateSelected(!0),this.dispatchEvent(new Event(`selected-changed`)),this.requestUpdate(`selectedIndex`,t)}get _pairCount(){return this.__store&&this.__store.length||0}__updateSelected(e=!1){if(!(this.__store&&typeof this.selectedIndex==`number`&&this.__store[this.selectedIndex]))return;let t=this.tabs.find(e=>e.hasAttribute(`selected`)),n=this.panels.find(e=>e.hasAttribute(`selected`));t&&zc(t),n&&Fc(n);let{button:r,panel:i}=this.__store[this.selectedIndex];r&&Rc(r,e),i&&Pc(i)}},Hc=v`
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
`,Uc=class extends Vc{static get styles(){return[...super.styles,Hc]}};customElements.get(`craft-tabs`)||customElements.define(`craft-tabs`,Uc);var Wc=v`
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
`,Gc=class extends C{constructor(...e){super(...e),this.label=``}render(){let e=!!this.label||!!this.querySelector(`[slot="header"]`)||!!this.querySelector(`[slot="label"]`)||!!this.querySelector(`[slot="actions"]`),t=!!this.querySelector(`[slot="footer"]`);return g`
      <div class="card">
        <div>
          ${e?g`<div class="card__header">
                <slot name="header">
                  <slot name="label" class="card__label" part="label"
                    >${this.label}</slot
                  >
                  <slot name="actions"></slot>
                </slot>
              </div>`:S}

          <div class="card__body">
            <slot></slot>
          </div>

          ${t?g`<div class="card__footer"><slot name="footer"></slot></div>`:S}
        </div>
      </div>
    `}};Gc.styles=[Wc],m([w()],Gc.prototype,`label`,void 0),customElements.get(`craft-card`)||customElements.define(`craft-card`,Gc);var Kc=v`
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
`,qc=class extends C{render(){return g`<slot></slot> `}};qc.styles=[Kc],customElements.get(`craft-tab`)||customElements.define(`craft-tab`,qc);var Jc=class extends Ar(C){static get properties(){return{checked:{type:Boolean,reflect:!0}}}static get styles(){return[v`
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
      `]}render(){return g`
      <div class="btn">
        <div class="switch-button__track"></div>
        <div class="switch-button__thumb"></div>
      </div>
    `}constructor(){super(),this.value=``,this.checked=!1,this.__initialized=!1,this._toggleChecked=this._toggleChecked.bind(this),this.__handleKeydown=this._handleKeydown.bind(this),this.__handleKeyup=this._handleKeyup.bind(this)}connectedCallback(){super.connectedCallback(),this.setAttribute(`role`,`switch`),this.setAttribute(`aria-checked`,`${this.checked}`),this.addEventListener(`click`,this._toggleChecked),this.addEventListener(`keydown`,this.__handleKeydown),this.addEventListener(`keyup`,this.__handleKeyup)}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener(`click`,this._toggleChecked),this.removeEventListener(`keydown`,this.__handleKeydown),this.removeEventListener(`keyup`,this.__handleKeyup)}_toggleChecked(){this.disabled||(this.focus(),this.checked=!this.checked)}__checkedStateChange(){this.dispatchEvent(new Event(`checked-changed`,{bubbles:!0})),this.setAttribute(`aria-checked`,`${this.checked}`)}_handleKeydown(e){e.key===` `&&e.preventDefault()}_handleKeyup(e){[` `,`Enter`].includes(e.key)&&this._toggleChecked()}updated(e){super.updated(e),e.has(`disabled`)&&this.setAttribute(`aria-disabled`,`${this.disabled}`)}requestUpdate(e,t,n){super.requestUpdate(e,t,n),this.__initialized&&this.isConnected&&e===`checked`&&this.checked!==t&&!this.disabled&&this.__checkedStateChange()}firstUpdated(e){super.firstUpdated(e),this.__initialized=!0}},Yc=class extends ho(Bo(No)){static get styles(){return[...super.styles,v`
        :host([hidden]) {
          display: none;
        }

        :host([disabled]) {
          color: #adadad;
        }
      `]}static get scopedElements(){return{...super.scopedElements,"lion-switch-button":Jc}}get _inputNode(){return Array.from(this.children).find(e=>e.slot===`input`)}get slots(){return{...super.slots,input:()=>{let e=this.createScopedElement(`lion-switch-button`);return e.setAttribute(`data-tag-name`,`lion-switch-button`),e}}}render(){return g`
      <div class="form-field__group-one">${this._groupOneTemplate()}</div>
      <div class="form-field__group-two">${this._groupTwoTemplate()}</div>
    `}_groupOneTemplate(){return g`${this._labelTemplate()} ${this._helpTextTemplate()} ${this._feedbackTemplate()}`}_groupTwoTemplate(){return g`${this._inputGroupTemplate()}`}constructor(){super(),this.checked=!1,this.__handleButtonSwitchCheckedChanged=this.__handleButtonSwitchCheckedChanged.bind(this)}connectedCallback(){super.connectedCallback(),this.addEventListener(`checked-changed`,this.__handleButtonSwitchCheckedChanged),this._labelNode&&this._labelNode.addEventListener(`click`,this._toggleChecked),this._syncButtonSwitch()}disconnectedCallback(){super.disconnectedCallback(),this._inputNode&&this.removeEventListener(`checked-changed`,this.__handleButtonSwitchCheckedChanged),this._labelNode&&this._labelNode.removeEventListener(`click`,this._toggleChecked)}updated(e){super.updated(e),e.has(`disabled`)&&this._syncButtonSwitch()}_toggleChecked(e){e.preventDefault(),super._toggleChecked(e)}_isEmpty(){return!1}__handleButtonSwitchCheckedChanged(e){e.stopPropagation(),this._isHandlingUserInput=!0,this.checked=this._inputNode.checked,this._isHandlingUserInput=!1}_syncButtonSwitch(){this._inputNode.disabled=this.disabled}_onLabelClick(){this.disabled||this._inputNode.focus()}},Xc=class extends Jc{static get styles(){return[...super.styles,v`
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
      `]}};customElements.get(`craft-switch-button`)||customElements.define(`craft-switch-button`,Xc);var Zc=v`
  :host {
    display: grid;
  }

  .input-group {
    display: inline-flex;
  }

  ::slotted(label) {
    font-weight: bold;
  }
`,Qc=class extends Yc{constructor(...e){super(...e),this.size=`medium`}static get styles(){return[...super.styles,ba,Zc]}get slots(){return{...super.slots,input:()=>{let e=this.createScopedElement(`craft-switch-button`);return e.setAttribute(`size`,this.size),e.setAttribute(`data-tag-name`,`craft-switch-button`),e}}}static get scopedElements(){return{...super.scopedElements,"craft-switch-button":Xc}}};m([w({type:String,reflect:!0})],Qc.prototype,`size`,void 0),customElements.get(`craft-switch`)||customElements.define(`craft-switch`,Qc);var $c=v`
  .breadcrumbs {
    display: flex;
    align-items: center;
  }
`,el=class extends C{constructor(...e){super(...e),this.label=``,this.items=[],this.visibleItems=0,this.firstRender=!0}getSeparator(){let e=this.separatorSlot.assignedElements({flatten:!0})[0].cloneNode(!0);return[e,...e.querySelectorAll(`[id]`)].forEach(e=>e.removeAttribute(`id`)),e.setAttribute(`data-default`,``),e.slot=`separator`,e}calculateBreadcrumbItemsWidth(){this.items=this.breadcrumbsElements.map((e,t)=>{let n=e.offsetWidth;return e.hasAttribute(`hidden`)&&(e.removeAttribute(`hidden`),n=e.offsetWidth,e.setAttribute(`hidden`,``)),{label:e.innerText,href:e.href,value:t.toString(),offsetWidth:n,isVisible:!0}})}async handleSlotChange(){let e=[...this.defaultSlot.assignedElements({flatten:!0})].filter(e=>e.tagName.toLowerCase()===`craft-breadcrumb-item`);if(e.forEach((t,n)=>{let r=t.querySelector(`[slot="separator"]`);r===null?t.append(this.getSeparator()):r.hasAttribute(`data-default`)&&r.replaceWith(this.getSeparator()),n===e.length-1?t.setAttribute(`aria-current`,`page`):t.removeAttribute(`aria-current`)}),this.breadcrumbsElements.length===0){this.items=[],this.visibleItems=0;return}await Promise.all(this.breadcrumbsElements.map(e=>e.updateComplete)),this.calculateBreadcrumbItemsWidth(),this.visibleItems=0,this.adjustOverflow()}connectedCallback(){super.connectedCallback(),this.hasAttribute(`role`)||this.setAttribute(`role`,`navigation`),this.resizeObserver=new ResizeObserver(()=>{if(this.firstRender){this.firstRender=!1;return}this.adjustOverflow()}),this.resizeObserver.observe(this)}adjustOverflow(){let e=this.getBoundingClientRect().width;console.log({availableSpace:e})}disconnectedCallback(){this.resizeObserver?.unobserve(this),super.disconnectedCallback()}render(){return g`
      <nav class="breadcrumbs" aria-label="${this.label}">
        <slot @slotchange="${this.handleSlotChange}"></slot>
      </nav>

      <span hidden aria-hidden="true">
        <slot name="separator"><span class="separator">/</span></slot>
      </span>
    `}};el.styles=[$c],m([D(`slot`)],el.prototype,`defaultSlot`,void 0),m([D(`slot[name="separator"]`)],el.prototype,`separatorSlot`,void 0),m([O({selector:`craft-breadcrumb-item`})],el.prototype,`breadcrumbsElements`,void 0),m([w()],el.prototype,`label`,void 0),m([T()],el.prototype,`items`,void 0),m([T()],el.prototype,`visibleItems`,void 0),customElements.get(`craft-breadcrumbs`)||customElements.define(`craft-breadcrumbs`,el);var tl=`:host {
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
`,nl=class extends rt{constructor(){super(...arguments),this.renderType=`button`,this.rel=`noreferrer noopener`}setRenderType(){let e=this.defaultSlot.assignedElements({flatten:!0}).filter(e=>e.tagName.toLowerCase()===`wa-dropdown`).length>0;if(this.href){this.renderType=`link`;return}if(e){this.renderType=`dropdown`;return}this.renderType=`button`}hrefChanged(){this.setRenderType()}handleSlotChange(){this.setRenderType()}render(){return g`
      <span part="start" class="start">
        <slot name="start"></slot>
      </span>

      ${this.renderType===`link`?g`
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
      ${this.renderType===`button`?g`
            <button part="label" type="button" class="label label-button">
              <slot @slotchange=${this.handleSlotChange}></slot>
            </button>
          `:``}
      ${this.renderType===`dropdown`?g`
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
    `}};nl.css=tl,M([D(`slot:not([name])`)],nl.prototype,`defaultSlot`,2),M([T()],nl.prototype,`renderType`,2),M([w()],nl.prototype,`href`,2),M([w()],nl.prototype,`target`,2),M([w()],nl.prototype,`rel`,2),M([gr(`href`,{waitUntilFirstUpdate:!0})],nl.prototype,`hrefChanged`,1),nl=M([E(`wa-breadcrumb-item`)],nl);var rl=class extends nl{static get styles(){return[nl.styles,v`
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
`,al=new Set,ol=class extends rt{constructor(){super(...arguments),this.anchor=null,this.placement=`top`,this.open=!1,this.distance=8,this.skidding=0,this.for=null,this.withoutArrow=!1,this.eventController=new AbortController,this.handleAnchorClick=()=>{this.open=!this.open},this.handleBodyClick=e=>{e.target.closest(`[data-popover="close"]`)&&(e.stopPropagation(),this.open=!1)},this.handleDocumentKeyDown=e=>{e.key===`Escape`&&(e.preventDefault(),this.open=!1,this.anchor&&typeof this.anchor.focus==`function`&&this.anchor.focus())},this.handleDocumentClick=e=>{let t=e.target;this.anchor&&e.composedPath().includes(this.anchor)||t.closest(`wa-popover`)!==this&&(this.open=!1)}}connectedCallback(){super.connectedCallback(),this.id||=pr(`wa-popover-`)}disconnectedCallback(){super.disconnectedCallback(),document.removeEventListener(`keydown`,this.handleDocumentKeyDown),this.eventController.abort()}firstUpdated(){this.open&&(this.dialog.show(),this.popup.active=!0,this.popup.reposition())}updated(e){e.has(`open`)&&this.customStates.set(`open`,this.open)}async handleOpenChange(){if(this.open){let e=new ur;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}al.forEach(e=>e.open=!1),document.addEventListener(`keydown`,this.handleDocumentKeyDown,{signal:this.eventController.signal}),document.addEventListener(`click`,this.handleDocumentClick,{signal:this.eventController.signal}),this.dialog.show(),this.popup.active=!0,al.add(this),requestAnimationFrame(()=>{let e=this.querySelector(`[autofocus]`);e&&typeof e.focus==`function`?e.focus():this.dialog.focus()}),await hr(this.popup.popup,`show-with-scale`),this.popup.reposition(),this.dispatchEvent(new cr)}else{let e=new lr;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!0;return}document.removeEventListener(`keydown`,this.handleDocumentKeyDown),document.removeEventListener(`click`,this.handleDocumentClick),al.delete(this),await hr(this.popup.popup,`hide-with-scale`),this.popup.active=!1,this.dialog.close(),this.dispatchEvent(new sr)}}handleForChange(){let e=this.getRootNode();if(!e)return;let t=this.for?e.getElementById(this.for):null,n=this.anchor;if(t===n)return;let{signal:r}=this.eventController;t&&t.addEventListener(`click`,this.handleAnchorClick,{signal:r}),n&&n.removeEventListener(`click`,this.handleAnchorClick),this.anchor=t,this.for&&!t&&console.warn(`A popover was assigned to an element with an ID of "${this.for}" but the element could not be found.`,this)}async handleOptionsChange(){this.hasUpdated&&(await this.updateComplete,this.popup.reposition())}async show(){if(!this.open)return this.open=!0,mr(this,`wa-after-show`)}async hide(){if(this.open)return this.open=!1,mr(this,`wa-after-hide`)}render(){return g`
      <dialog part="dialog" class="dialog">
        <wa-popup
          part="popup"
          exportparts="
            popup:popup__popup,
            arrow:popup__arrow
          "
          class=${A({popover:!0,"popover-open":this.open})}
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
    `}};ol.css=il,ol.dependencies={"wa-popup":N},M([D(`dialog`)],ol.prototype,`dialog`,2),M([D(`.body`)],ol.prototype,`body`,2),M([D(`wa-popup`)],ol.prototype,`popup`,2),M([T()],ol.prototype,`anchor`,2),M([w()],ol.prototype,`placement`,2),M([w({type:Boolean,reflect:!0})],ol.prototype,`open`,2),M([w({type:Number})],ol.prototype,`distance`,2),M([w({type:Number})],ol.prototype,`skidding`,2),M([w()],ol.prototype,`for`,2),M([w({attribute:`without-arrow`,type:Boolean,reflect:!0})],ol.prototype,`withoutArrow`,2),M([gr(`open`,{waitUntilFirstUpdate:!0})],ol.prototype,`handleOpenChange`,1),M([gr(`for`)],ol.prototype,`handleForChange`,1),M([gr([`distance`,`placement`,`skidding`])],ol.prototype,`handleOptionsChange`,1),ol=M([E(`wa-popover`)],ol);var sl=class extends ol{static get styles(){return[ol.styles,v`
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
`,hl=class extends rt{constructor(){super(...arguments),this.localize=new Oe(this),this.hasSlotController=new Oc(this,`footer`,`header-actions`,`label`),this.open=!1,this.label=``,this.placement=`end`,this.withoutHeader=!1,this.lightDismiss=!0,this.handleDocumentKeyDown=e=>{e.key===`Escape`&&this.open&&(e.preventDefault(),e.stopPropagation(),this.requestClose(this.drawer))}}firstUpdated(){this.open&&(this.addOpenListeners(),this.drawer.showModal(),dl(this))}disconnectedCallback(){super.disconnectedCallback(),fl(this),this.removeOpenListeners()}async requestClose(e){let t=new lr({source:e});if(this.dispatchEvent(t),t.defaultPrevented){this.open=!0,hr(this.drawer,`pulse`);return}this.removeOpenListeners(),await hr(this.drawer,`hide`),this.open=!1,this.drawer.close(),fl(this);let n=this.originalTrigger;typeof n?.focus==`function`&&setTimeout(()=>n.focus()),this.dispatchEvent(new sr)}addOpenListeners(){document.addEventListener(`keydown`,this.handleDocumentKeyDown)}removeOpenListeners(){document.removeEventListener(`keydown`,this.handleDocumentKeyDown)}handleDialogCancel(e){e.preventDefault(),!this.drawer.classList.contains(`hide`)&&e.target===this.drawer&&this.requestClose(this.drawer)}handleDialogClick(e){let t=e.target.closest(`[data-drawer="close"]`);t&&(e.stopPropagation(),this.requestClose(t))}async handleDialogPointerDown(e){e.target===this.drawer&&(this.lightDismiss?this.requestClose(this.drawer):await hr(this.drawer,`pulse`))}handleOpenChange(){this.open&&!this.drawer.open?this.show():this.drawer.open&&(this.open=!0,this.requestClose(this.drawer))}async show(){let e=new ur;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}this.addOpenListeners(),this.originalTrigger=document.activeElement,this.open=!0,this.drawer.showModal(),dl(this),requestAnimationFrame(()=>{let e=this.querySelector(`[autofocus]`);e&&typeof e.focus==`function`?e.focus():this.drawer.focus()}),await hr(this.drawer,`show`),this.dispatchEvent(new cr)}render(){let e=!this.withoutHeader,t=this.hasSlotController.test(`footer`);return g`
      <dialog
        part="dialog"
        class=${A({drawer:!0,open:this.open,top:this.placement===`top`,end:this.placement===`end`,bottom:this.placement===`bottom`,start:this.placement===`start`})}
        @cancel=${this.handleDialogCancel}
        @click=${this.handleDialogClick}
        @pointerdown=${this.handleDialogPointerDown}
      >
        ${e?g`
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

        ${t?g`
              <footer part="footer" class="footer">
                <slot name="footer"></slot>
              </footer>
            `:``}
      </dialog>
    `}};hl.css=ml,M([D(`.drawer`)],hl.prototype,`drawer`,2),M([w({type:Boolean,reflect:!0})],hl.prototype,`open`,2),M([w({reflect:!0})],hl.prototype,`label`,2),M([w({reflect:!0})],hl.prototype,`placement`,2),M([w({attribute:`without-header`,type:Boolean,reflect:!0})],hl.prototype,`withoutHeader`,2),M([w({attribute:`light-dismiss`,type:Boolean})],hl.prototype,`lightDismiss`,2),M([gr(`open`,{waitUntilFirstUpdate:!0})],hl.prototype,`handleOpenChange`,1),hl=M([E(`wa-drawer`)],hl),document.addEventListener(`click`,e=>{let t=e.target.closest(`[data-drawer]`);if(t instanceof Element){let[e,n]=pl(t.getAttribute(`data-drawer`)||``);if(e===`open`&&n?.length){let e=t.getRootNode().getElementById(n);e?.localName===`wa-drawer`?e.open=!0:console.warn(`A drawer with an ID of "${n}" could not be found in this document.`)}}}),document.body.addEventListener(`pointerdown`,()=>{});var gl=()=>({checkValidity(e){let t=e.input,n={message:``,isValid:!0,invalidKeys:[]};if(!t)return n;let r=!0;if(`checkValidity`in t&&(r=t.checkValidity()),r)return n;if(n.isValid=!1,`validationMessage`in t&&(n.message=t.validationMessage),!(`validity`in t))return n.invalidKeys.push(`customError`),n;for(let e in t.validity){if(e===`valid`)continue;let r=e;t.validity[r]&&n.invalidKeys.push(r)}return n}}),_l=class extends Event{constructor(){super(`wa-invalid`,{bubbles:!0,cancelable:!1,composed:!0})}},vl=()=>({observedAttributes:[`custom-error`],checkValidity(e){let t={message:``,isValid:!0,invalidKeys:[]};return e.customError&&(t.message=e.customError,t.isValid=!1,t.invalidKeys=[`customError`]),t}}),yl=class extends rt{constructor(){super(),this.name=null,this.disabled=!1,this.required=!1,this.assumeInteractionOn=[`input`],this.validators=[],this.valueHasChanged=!1,this.hasInteracted=!1,this.customError=null,this.emittedEvents=[],this.emitInvalid=e=>{e.target===this&&(this.hasInteracted=!0,this.dispatchEvent(new _l))},this.handleInteraction=e=>{let t=this.emittedEvents;t.includes(e.type)||t.push(e.type),t.length===this.assumeInteractionOn?.length&&(this.hasInteracted=!0)},this.addEventListener(`invalid`,this.emitInvalid)}static get validators(){return[vl()]}static get observedAttributes(){let e=new Set(super.observedAttributes||[]);for(let t of this.validators)if(t.observedAttributes)for(let n of t.observedAttributes)e.add(n);return[...e]}connectedCallback(){super.connectedCallback(),this.updateValidity(),this.assumeInteractionOn.forEach(e=>{this.addEventListener(e,this.handleInteraction)})}firstUpdated(...e){super.firstUpdated(...e),this.updateValidity()}willUpdate(e){if(e.has(`customError`)&&(this.customError||=null,this.setCustomValidity(this.customError||``)),e.has(`value`)||e.has(`disabled`)){let e=this.value;if(Array.isArray(e)){if(this.name){let t=new FormData;for(let n of e)t.append(this.name,n);this.setValue(t,t)}}else this.setValue(e,e)}e.has(`disabled`)&&(this.customStates.set(`disabled`,this.disabled),(this.hasAttribute(`disabled`)||!this.matches(`:disabled`))&&this.toggleAttribute(`disabled`,this.disabled)),this.updateValidity(),super.willUpdate(e)}get labels(){return this.internals.labels}getForm(){return this.internals.form}get validity(){return this.internals.validity}get willValidate(){return this.internals.willValidate}get validationMessage(){return this.internals.validationMessage}checkValidity(){return this.updateValidity(),this.internals.checkValidity()}reportValidity(){return this.updateValidity(),this.hasInteracted=!0,this.internals.reportValidity()}get validationTarget(){return this.input||void 0}setValidity(...e){let t=e[0],n=e[1],r=e[2];r||=this.validationTarget,this.internals.setValidity(t,n,r||void 0),this.requestUpdate(`validity`),this.setCustomStates()}setCustomStates(){let e=!!this.required,t=this.internals.validity.valid,n=this.hasInteracted;this.customStates.set(`required`,e),this.customStates.set(`optional`,!e),this.customStates.set(`invalid`,!t),this.customStates.set(`valid`,t),this.customStates.set(`user-invalid`,!t&&n),this.customStates.set(`user-valid`,t&&n)}setCustomValidity(e){if(!e){this.customError=null,this.setValidity({});return}this.customError=e,this.setValidity({customError:!0},e,this.validationTarget)}formResetCallback(){this.resetValidity(),this.hasInteracted=!1,this.valueHasChanged=!1,this.emittedEvents=[],this.updateValidity()}formDisabledCallback(e){this.disabled=e,this.updateValidity()}formStateRestoreCallback(e,t){this.value=e,t===`restore`&&this.resetValidity(),this.updateValidity()}setValue(...e){let[t,n]=e;this.internals.setFormValue(t,n)}get allValidators(){let e=this.constructor.validators||[],t=this.validators||[];return[...e,...t]}resetValidity(){this.setCustomValidity(``),this.setValidity({})}updateValidity(){if(this.disabled||this.hasAttribute(`disabled`)||!this.willValidate){this.resetValidity();return}let e=this.allValidators;if(!e?.length)return;let t={customError:!!this.customError},n=this.validationTarget||this.input||void 0,r=``;for(let n of e){let{isValid:e,message:i,invalidKeys:a}=n.checkValidity(this);e||(r||=i,a?.length>=0&&a.forEach(e=>t[e]=!0))}r||=this.validationMessage,this.setValidity(t,r,n)}};yl.formAssociated=!0,M([w({reflect:!0})],yl.prototype,`name`,2),M([w({type:Boolean})],yl.prototype,`disabled`,2),M([w({state:!0,attribute:!1})],yl.prototype,`valueHasChanged`,2),M([w({state:!0,attribute:!1})],yl.prototype,`hasInteracted`,2),M([w({attribute:`custom-error`,reflect:!0})],yl.prototype,`customError`,2),M([w({attribute:!1,state:!0,type:Object})],yl.prototype,`validity`,1);var bl=`@layer wa-utilities {
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
`,xl=Symbol.for(``),Sl=e=>{if(e?.r===xl)return e?._$litStatic$},Cl=(e,...t)=>({_$litStatic$:t.reduce(((t,n,r)=>t+(e=>{if(e._$litStatic$!==void 0)return e._$litStatic$;throw Error(`Value passed to 'literal' function must be a 'literal' result: ${e}. Use 'unsafeStatic' to pass non-literal values, but\n            take care to ensure page security.`)})(n)+e[r+1]),e[0]),r:xl}),wl=new Map,Tl=(e=>(t,...n)=>{let r=n.length,i,a,o=[],s=[],c,l=0,u=!1;for(;l<r;){for(c=t[l];l<r&&(a=n[l],i=Sl(a))!==void 0;)c+=i+t[++l],u=!0;l!==r&&s.push(a),o.push(c),l++}if(l===r&&o.push(t[r]),u){let e=o.join(`$$lit$$`);(t=wl.get(e))===void 0&&(o.raw=o,wl.set(e,t=o)),n=s}return e(t,...n)})(g),El=`@layer wa-component {
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
`,B=class extends yl{constructor(){super(...arguments),this.assumeInteractionOn=[`click`],this.hasSlotController=new Oc(this,`[default]`,`start`,`end`),this.localize=new Oe(this),this.invalid=!1,this.isIconButton=!1,this.title=``,this.variant=`neutral`,this.appearance=`accent`,this.size=`medium`,this.withCaret=!1,this.disabled=!1,this.loading=!1,this.pill=!1,this.type=`button`,this.form=null}static get validators(){return[...super.validators,gl()]}constructLightDOMButton(){let e=document.createElement(`button`);return e.type=this.type,e.style.position=`absolute`,e.style.width=`0`,e.style.height=`0`,e.style.clipPath=`inset(50%)`,e.style.overflow=`hidden`,e.style.whiteSpace=`nowrap`,this.name&&(e.name=this.name),e.value=this.value||``,[`form`,`formaction`,`formenctype`,`formmethod`,`formnovalidate`,`formtarget`].forEach(t=>{this.hasAttribute(t)&&e.setAttribute(t,this.getAttribute(t))}),e}handleClick(){if(!this.getForm())return;let e=this.constructLightDOMButton();this.parentElement?.append(e),e.click(),e.remove()}handleInvalid(){this.dispatchEvent(new _l)}handleLabelSlotChange(){let e=this.labelSlot.assignedNodes({flatten:!0}),t=!1,n=!1,r=!1,i=!1;[...e].forEach(e=>{if(e.nodeType===Node.ELEMENT_NODE){let r=e;r.localName===`wa-icon`?(n=!0,t||=r.label!==void 0):i=!0}else e.nodeType===Node.TEXT_NODE&&(e.textContent?.trim()||``).length>0&&(r=!0)}),this.isIconButton=n&&!r&&!i,this.isIconButton&&!t&&console.warn(`Icon buttons must have a label for screen readers. Add <wa-icon label="..."> to remove this warning.`,this)}isButton(){return!this.href}isLink(){return!!this.href}handleDisabledChange(){this.updateValidity()}setValue(...e){}click(){this.button.click()}focus(e){this.button.focus(e)}blur(){this.button.blur()}render(){let e=this.isLink(),t=e?Cl`a`:Cl`button`;return Tl`
      <${t}
        part="base"
        class=${A({button:!0,caret:this.withCaret,disabled:this.disabled,loading:this.loading,rtl:this.localize.dir()===`rtl`,"has-label":this.hasSlotController.test(`[default]`),"has-start":this.hasSlotController.test(`start`),"has-end":this.hasSlotController.test(`end`),"is-icon-button":this.isIconButton})}
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
    `}};B.shadowRootOptions={...yl.shadowRootOptions,delegatesFocus:!0},B.css=[El,bl,Sc],M([D(`.button`)],B.prototype,`button`,2),M([D(`slot:not([name])`)],B.prototype,`labelSlot`,2),M([T()],B.prototype,`invalid`,2),M([T()],B.prototype,`isIconButton`,2),M([w()],B.prototype,`title`,2),M([w({reflect:!0})],B.prototype,`variant`,2),M([w({reflect:!0})],B.prototype,`appearance`,2),M([w({reflect:!0})],B.prototype,`size`,2),M([w({attribute:`with-caret`,type:Boolean,reflect:!0})],B.prototype,`withCaret`,2),M([w({type:Boolean})],B.prototype,`disabled`,2),M([w({type:Boolean,reflect:!0})],B.prototype,`loading`,2),M([w({type:Boolean,reflect:!0})],B.prototype,`pill`,2),M([w()],B.prototype,`type`,2),M([w({reflect:!0})],B.prototype,`name`,2),M([w({reflect:!0})],B.prototype,`value`,2),M([w({reflect:!0})],B.prototype,`href`,2),M([w()],B.prototype,`target`,2),M([w()],B.prototype,`rel`,2),M([w()],B.prototype,`download`,2),M([w({reflect:!0})],B.prototype,`form`,2),M([w({attribute:`formaction`})],B.prototype,`formAction`,2),M([w({attribute:`formenctype`})],B.prototype,`formEnctype`,2),M([w({attribute:`formmethod`})],B.prototype,`formMethod`,2),M([w({attribute:`formnovalidate`,type:Boolean})],B.prototype,`formNoValidate`,2),M([w({attribute:`formtarget`})],B.prototype,`formTarget`,2),M([gr(`disabled`,{waitUntilFirstUpdate:!0})],B.prototype,`handleDisabledChange`,1),B=M([E(`wa-button`)],B);var Dl=`:host {
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
`,Ol=class extends rt{constructor(){super(...arguments),this.localize=new Oe(this)}render(){return g`
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
    `}};Ol.css=Dl,Ol=M([E(`wa-spinner`)],Ol);var kl=class extends hl{static get styles(){return[hl.styles,v`
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
`,jl=class extends rt{constructor(){super(...arguments),this.localize=new Oe(this),this.hasSlotController=new Oc(this,`footer`,`header-actions`,`label`),this.open=!1,this.label=``,this.withoutHeader=!1,this.lightDismiss=!1,this.handleDocumentKeyDown=e=>{e.key===`Escape`&&this.open&&(e.preventDefault(),e.stopPropagation(),this.requestClose(this.dialog))}}firstUpdated(){this.open&&(this.addOpenListeners(),this.dialog.showModal(),dl(this))}disconnectedCallback(){super.disconnectedCallback(),fl(this),this.removeOpenListeners()}async requestClose(e){let t=new lr({source:e});if(this.dispatchEvent(t),t.defaultPrevented){this.open=!0,hr(this.dialog,`pulse`);return}this.removeOpenListeners(),await hr(this.dialog,`hide`),this.open=!1,this.dialog.close(),fl(this);let n=this.originalTrigger;typeof n?.focus==`function`&&setTimeout(()=>n.focus()),this.dispatchEvent(new sr)}addOpenListeners(){document.addEventListener(`keydown`,this.handleDocumentKeyDown)}removeOpenListeners(){document.removeEventListener(`keydown`,this.handleDocumentKeyDown)}handleDialogCancel(e){e.preventDefault(),!this.dialog.classList.contains(`hide`)&&e.target===this.dialog&&this.requestClose(this.dialog)}handleDialogClick(e){let t=e.target.closest(`[data-dialog="close"]`);t&&(e.stopPropagation(),this.requestClose(t))}async handleDialogPointerDown(e){e.target===this.dialog&&(this.lightDismiss?this.requestClose(this.dialog):await hr(this.dialog,`pulse`))}handleOpenChange(){this.open&&!this.dialog.open?this.show():!this.open&&this.dialog.open&&(this.open=!0,this.requestClose(this.dialog))}async show(){let e=new ur;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}this.addOpenListeners(),this.originalTrigger=document.activeElement,this.open=!0,this.dialog.showModal(),dl(this),requestAnimationFrame(()=>{let e=this.querySelector(`[autofocus]`);e&&typeof e.focus==`function`?e.focus():this.dialog.focus()}),await hr(this.dialog,`show`),this.dispatchEvent(new cr)}render(){let e=!this.withoutHeader,t=this.hasSlotController.test(`footer`);return g`
      <dialog
        part="dialog"
        class=${A({dialog:!0,open:this.open})}
        @cancel=${this.handleDialogCancel}
        @click=${this.handleDialogClick}
        @pointerdown=${this.handleDialogPointerDown}
      >
        ${e?g`
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

        ${t?g`
              <footer part="footer" class="footer">
                <slot name="footer"></slot>
              </footer>
            `:``}
      </dialog>
    `}};jl.css=Al,M([D(`.dialog`)],jl.prototype,`dialog`,2),M([w({type:Boolean,reflect:!0})],jl.prototype,`open`,2),M([w({reflect:!0})],jl.prototype,`label`,2),M([w({attribute:`without-header`,type:Boolean,reflect:!0})],jl.prototype,`withoutHeader`,2),M([w({attribute:`light-dismiss`,type:Boolean})],jl.prototype,`lightDismiss`,2),M([gr(`open`,{waitUntilFirstUpdate:!0})],jl.prototype,`handleOpenChange`,1),jl=M([E(`wa-dialog`)],jl),document.addEventListener(`click`,e=>{let t=e.target.closest(`[data-dialog]`);if(t instanceof Element){let[e,n]=pl(t.getAttribute(`data-dialog`)||``);if(e===`open`&&n?.length){let e=t.getRootNode().getElementById(n);e?.localName===`wa-dialog`?e.open=!0:console.warn(`A dialog with an ID of "${n}" could not be found in this document.`)}}}),document.addEventListener(`pointerdown`,()=>{});var Ml=class extends jl{static get styles(){return[jl.styles,v`
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
      `]}};customElements.get(`craft-dialog`)||customElements.define(`craft-dialog`,Ml);var Nl=class extends Ro(Ho(C)){constructor(){super(),this.multipleChoice=!0}},Pl=class extends Bo(Uo){connectedCallback(){super.connectedCallback(),this.type=`checkbox`}},Fl=class extends Pl{static get styles(){return[...super.styles||[],v`
        :host .choice-field__nested-checkboxes {
          display: block;
        }
        ::slotted(*) {
          padding-left: 8px;
        }
      `]}static get properties(){return{indeterminate:{type:Boolean,reflect:!0},mixedState:{type:Boolean,reflect:!0,attribute:`mixed-state`}}}get _checkboxGroupNode(){return this._parentFormGroup}get _subCheckboxes(){return this.__subCheckboxes}_storeIndeterminateState(){this._indeterminateSubStates=this._subCheckboxes.map(e=>e.checked)}_setOldState(){this.indeterminate?this._oldState=`indeterminate`:this._oldState=this.checked?`checked`:`unchecked`}_setOwnCheckedState(){let e=this._subCheckboxes;if(!e.length)return;this.__settingOwnChecked=!0;let t=e.filter(e=>e.checked);switch(e.length-t.length){case 0:this.indeterminate=!1,this.checked=!0;break;case e.length:this.indeterminate=!1,this.checked=!1;break;default:{this.indeterminate=!0;let n=e.filter(e=>e.disabled&&e.checked===!1);this.checked=e.length-t.length-n.length===0}}this.updateComplete.then(()=>{this.__settingOwnChecked=!1})}_setBasedOnMixedState(){switch(this._oldState){case`checked`:this.checked=!1,this.indeterminate=!1;break;case`unchecked`:this.checked=!1,this.indeterminate=!0;break;case`indeterminate`:this.checked=!0,this.indeterminate=!1;break}}__onModelValueChanged(e){if(!this.disabled){if(e.detail.formPath[0]===this&&!this.__settingOwnChecked){this.mixedState&&!(e=>e.every(t=>t===e[0]))(this._indeterminateSubStates)&&this._setBasedOnMixedState(),this.__settingOwnSubs=!0;let e=this._subCheckboxes,t=e.filter(e=>e.checked),n=e.filter(e=>e.disabled),r=e.length>0&&e.length===t.length;e.length>0&&e.length===n.length&&(this.checked=r),this.indeterminate&&this.mixedState?this._subCheckboxes.forEach((e,t)=>{e.checked=this._indeterminateSubStates[t]}):this._subCheckboxes.filter(e=>!e.disabled).forEach(e=>{e.checked=this._inputNode.checked}),this.updateComplete.then(()=>{this.__settingOwnSubs=!1})}else this._setOwnCheckedState(),this.updateComplete.then(()=>{!this.__settingOwnSubs&&!this.__settingOwnChecked&&this.mixedState&&this._storeIndeterminateState()});this.mixedState&&this._setOldState()}}_afterTemplate(){return g`
      <div class="choice-field__nested-checkboxes" role="list">
        <slot></slot>
      </div>
    `}_onRequestToAddFormElement(e){e.target.hasAttribute(`role`)||e.target?.setAttribute(`role`,`listitem`),this.__addToSubCheckboxes(e.detail.element),this._setOwnCheckedState()}_onRequestToRemoveFormElement(e){e.target.getAttribute(`role`)===`listitem`&&e.target?.removeAttribute(`role`),this.__removeFromSubCheckboxes(e.detail.element)}__addToSubCheckboxes(e){e!==this&&this.contains(e)&&this.__subCheckboxes.push(e)}__removeFromSubCheckboxes(e){let t=this.__subCheckboxes.indexOf(e);t!==-1&&this.__subCheckboxes.splice(t,1)}constructor(){super(),this.indeterminate=!1,this._onRequestToAddFormElement=this._onRequestToAddFormElement.bind(this),this.__onModelValueChanged=this.__onModelValueChanged.bind(this),this.__subCheckboxes=[],this._indeterminateSubStates=[],this.mixedState=!1}connectedCallback(){super.connectedCallback(),this.addEventListener(`model-value-changed`,this.__onModelValueChanged),this.addEventListener(`form-element-register`,this._onRequestToAddFormElement)}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener(`model-value-changed`,this.__onModelValueChanged),this.removeEventListener(`form-element-register`,this._onRequestToAddFormElement)}firstUpdated(e){super.firstUpdated(e),this._setOldState(),this.indeterminate&&this._storeIndeterminateState()}updated(e){super.updated(e),(e.has(`indeterminate`)||e.has(`checked`))&&(this._inputNode.indeterminate=this.indeterminate)}},Il=class extends Nl{static get styles(){return[...Nl.styles,v`
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
      `]}};customElements.get(`craft-checkbox-group`)||customElements.define(`craft-checkbox-group`,Il);var Ll=class extends Pl{static get styles(){return[...Pl.styles,v`
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
      `]}};customElements.get(`craft-checkbox`)||customElements.define(`craft-checkbox`,Ll);var Rl=class extends Fl{static get styles(){return[...Fl.styles,v`
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
      `]}};customElements.get(`craft-checkbox-indeterminate`)||customElements.define(`craft-checkbox-indeterminate`,Rl);var zl=v`
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
`,Bl=v`
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
`,Vl=class extends C{constructor(...e){super(...e),this.variant=s.Default,this.appearance=t.OutlineFill,this.title=``,this.icon=null,this.rounded=`all`,this.inline=!1}getDefaultIcon(){switch(this.variant){case s.Info:return`lightbulb`;case s.Success:return`circle-check`;case s.Warning:return`circle-exclamation`;case s.Danger:return`triangle-exclamation`;default:return null}}render(){return g`
      ${this.icon||this.querySelector(`[slot="icon"]`)?g`<slot name="icon" class="callout__icon">
            <craft-icon
              name="${this.getDefaultIcon()}"
              style="font-size: 0.9em"
            ></craft-icon>
          </slot>`:S}
      <div class="callout__body">
        <slot name="title" class="callout__title">${this.title}</slot>
        <div class="callout__description">
          <slot></slot>
        </div>
      </div>
    `}};Vl.styles=[zl,Bl],m([w({reflect:!0})],Vl.prototype,`variant`,void 0),m([w({reflect:!0})],Vl.prototype,`appearance`,void 0),m([w()],Vl.prototype,`title`,void 0),m([w()],Vl.prototype,`icon`,void 0),m([w({reflect:!0})],Vl.prototype,`rounded`,void 0),m([w({reflect:!0,type:Boolean})],Vl.prototype,`inline`,void 0),customElements.get(`craft-callout`)||customElements.define(`craft-callout`,Vl);var Hl=v`
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
`,Ul=class extends C{constructor(...e){super(...e),this.icon=null,this.href=null,this.disabled=!1,this.variant=s.Default,this.checked=!1,this.active=!1,this.type=`normal`}renderBody(){let e=!!this.querySelector(`[slot="icon"]`)||!!this.icon;return g`
      ${this.type===`checkbox`?g` <span class="action-item__check">
            <slot name="checkmark">
              ${this.checked?g`<craft-icon name="check"></craft-icon>`:S}
            </slot>
          </span>`:S}
      ${e?g`<span class="action-item__icon">
            <slot name="icon">
              ${this.icon?g`<craft-icon name="${this.icon}"></craft-icon>`:S}
            </slot>
          </span>`:S}

      <span class="action-item__label">
        <slot></slot>
      </span>

      <span class="action-item__suffix">
        <slot name="suffix"></slot>
      </span>
    `}render(){return this.href?g`
          <a
            class="${A({"action-item":!0,"action-item--checkbox":this.type===`checkbox`})}"
            href="${this.href}"
          >
            ${this.renderBody()}
          </a>
        `:g`
          <button
            type="button"
            class="${A({"action-item":!0,"action-item--checkbox":this.type===`checkbox`})}"
            ?disabled="${this.disabled}"
          >
            ${this.renderBody()}
          </button>
        `}};Ul.styles=[zl,Hl],m([w()],Ul.prototype,`icon`,void 0),m([w()],Ul.prototype,`href`,void 0),m([w({type:Boolean})],Ul.prototype,`disabled`,void 0),m([w({reflect:!0})],Ul.prototype,`variant`,void 0),m([w({type:Boolean})],Ul.prototype,`checked`,void 0),m([w({type:Boolean})],Ul.prototype,`active`,void 0),m([w()],Ul.prototype,`type`,void 0),customElements.get(`craft-action-item`)||customElements.define(`craft-action-item`,Ul);var Wl=v`
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
`,Gl=class e{static __createGlobalStyleNode(){let e=document.createElement(`style`);return e.setAttribute(`data-overlays`,``),e.textContent=Wl.cssText,document.head.appendChild(e),e}get list(){return this.__list}get shownList(){return this.__shownList}constructor(){this.__list=[],this.__shownList=[],this.__siblingsInert=!1,this.__blockingMap=new WeakMap,e.__globalStyleNode||=e.__createGlobalStyleNode()}add(e){if(this.list.find(t=>e===t))throw Error(`controller instance is already added`);return this.list.push(e),e}remove(e){if(!this.list.find(t=>e===t))throw Error(`could not find controller to remove`);this.__list=this.list.filter(t=>t!==e),this.__shownList=this.shownList.filter(t=>t!==e)}show(e){this.list.find(t=>e===t)&&this.hide(e),this.__shownList.unshift(e),Array.from(this.__shownList).reverse().forEach((e,t)=>{e.elevation=t+1})}hide(e){if(!this.list.find(t=>e===t))throw Error(`could not find controller to hide`);this.__shownList=this.shownList.filter(t=>t!==e)}teardown(){this.list.forEach(e=>{e.teardown()}),this.__list=[],this.__shownList=[],this.__siblingsInert=!1,e.__globalStyleNode&&=(document.head.removeChild(e.__globalStyleNode),void 0)}get siblingsInert(){return this.__siblingsInert}disableTrapsKeyboardFocusForAll(){this.shownList.forEach(e=>{e.trapsKeyboardFocus===!0&&e.disableTrapsKeyboardFocus&&e.disableTrapsKeyboardFocus({findNewTrap:!1})})}informTrapsKeyboardFocusGotEnabled(e){this.siblingsInert===!1&&e===`global`&&(this.__siblingsInert=!0)}informTrapsKeyboardFocusGotDisabled({disabledCtrl:e,findNewTrap:t=!0}={}){let n=this.shownList.find(t=>t!==e&&t.trapsKeyboardFocus===!0);n?t&&n.enableTrapsKeyboardFocus():this.siblingsInert===!0&&(this.__siblingsInert=!1)}requestToPreventScroll(){let{isIOS:e,isMacSafari:t}=Gr;document.body.classList.add(`overlays-scroll-lock`),(e||t)&&document.body.classList.add(`overlays-scroll-lock-ios-fix`),e&&document.documentElement.classList.add(`overlays-scroll-lock-ios-fix`)}requestToEnableScroll(){if(this.shownList.some(e=>e.preventsScroll===!0))return;let{isIOS:e,isMacSafari:t}=Gr;document.body.classList.remove(`overlays-scroll-lock`),(e||t)&&document.body.classList.remove(`overlays-scroll-lock-ios-fix`),e&&document.documentElement.classList.remove(`overlays-scroll-lock-ios-fix`)}requestToShowOnly(e){let t=this.shownList.filter(t=>t!==e);t.forEach(e=>e.hide()),this.__blockingMap.set(e,t)}retractRequestToShowOnly(e){this.__blockingMap.has(e)&&this.__blockingMap.get(e).forEach(e=>e.show())}};Gl.__globalStyleNode=void 0;var Kl=Ja.get(`@lion/ui::overlays::0.x`)||new Gl;function ql(){let e=document.activeElement||document.body;for(;e&&e.shadowRoot&&e.shadowRoot.activeElement;)e=e.shadowRoot.activeElement;return e}var Jl=({visibility:e,display:t})=>e!==`hidden`&&t!==`none`,Yl=({display:e})=>e===`contents`;function Xl(e){if(!e||!e.isConnected||!Jl(e.style))return!1;let t=window.getComputedStyle(e);return Jl(t)?Yl(t)?!0:!!(e.offsetWidth||e.offsetHeight||e.getClientRects().length):!1}function Zl(e,t){let n=Math.max(e.tabIndex,0),r=Math.max(t.tabIndex,0);return n===0||r===0?r>n:n>r}function Ql(e,t){let n=[];for(;e.length>0&&t.length>0;)Zl(e[0],t[0])?n.push(t.shift()):n.push(e.shift());return[...n,...e,...t]}function $l(e){let t=e.length;if(t<2)return e;let n=Math.ceil(t/2);return Ql($l(e.slice(0,n)),$l(e.slice(n)))}var eu=`matches`in Element.prototype?`matches`:`msMatchesSelector`;function tu(e){return e[eu](`input, select, textarea, button, object`)?e[eu](`:not([disabled])`):e[eu](`a[href], area[href], iframe, [tabindex], [contentEditable]`)}function nu(e){return tu(e)?Number(e.getAttribute(`tabindex`)||0):-1}function ru(e){if(e.localName===`slot`)return e.assignedNodes({flatten:!0});let{children:t}=e.shadowRoot||e;return t||[]}function iu(e){return e.nodeType===Node.ELEMENT_NODE?e.localName===`slot`?!0:Xl(e):!1}function au(e,t){if(!iu(e))return!1;let n=e,r=nu(n),i=r>0;r>=0&&t.push(n);let a=ru(n);for(let e=0;e<a.length;e+=1)i=au(a[e],t)||i;return i}function ou(e){let t=[];return au(e,t)?$l(t):t}function su(e,t,n={}){function r(e){return`getAttribute`in e}function i(e){if(!r(e))return null;let t=e.getAttribute(`slot`),i=null;if(t){let r=n[t];r&&(i=r.filter(t=>t?.element===e)[0]||null)}return i}let a=i(e);if(a)return a.deepContains;function o(t){if(!r(e))return;let i=e.getAttribute(`slot`);i&&(n[i]=n[i]||[],n[i].push({element:e,deepContains:t}))}let s=e.contains(t);if(s)return o(!0),!0;function c(e){return e.tagName===`SLOT`}function l(e){return c(e)?e.assignedElements():[]}function u(e){return e.nodeType===Node.DOCUMENT_FRAGMENT_NODE}function d(e){let i=!1;for(let a=0;a<e.length;a+=1){let o=e[a];if(o&&(r(o)||u(o))&&su(o,t,n)){i=!0;break}}return i}function f(e){for(let t=0;t<e.children.length;t+=1){let n=e.children[t],r=i(n);if(r){s=r.deepContains||s;break}let a=l(n);if(d([n.shadowRoot,...a])){s=!0;break}n.children.length>0&&f(n)}}return e instanceof HTMLElement&&e.shadowRoot&&(s=su(e.shadowRoot,t,n),s)?(o(!0),!0):(f(e),o(s),s)}var cu={enter:13,space:32,escape:27,tab:9};function lu(e,t){let n=ou(e),r;r=n.length>=2?[n[0],n[n.length-1]]:n.length===1?[n[0],n[0]]:[e,e],t.shiftKey&&r.reverse();let[i,a]=r,o=ql();o===e||n.includes(o)&&a!==o||(t.preventDefault(),i.focus())}function uu(e){let t=ou(e),n=t.find(e=>e.hasAttribute(`autofocus`))||e,r,i;n===e&&(e.tabIndex=-1,e.style.setProperty(`outline`,`none`)),n.focus();function a(t){t.keyCode===cu.tab&&lu(e,t)}function o(){r=document.createElement(`div`),r.style.display=`none`,r.setAttribute(`data-is-tab-detection-element`,``),e.insertBefore(r,e.children[0]),i=new MutationObserver(t=>{for(let n of t)if(n.type===`childList`){let t=!Array.from(e.children).find(e=>e.hasAttribute(`data-is-tab-detection-element`)),r=Array.from(n.addedNodes).find(e=>e instanceof HTMLElement&&e.hasAttribute(`data-is-tab-detection-element`));t&&!r&&(i.disconnect(),o())}}),i.observe(e,{childList:!0})}function s(){return r.compareDocumentPosition(document.activeElement)===Node.DOCUMENT_POSITION_PRECEDING}function c({resetToRoot:n=!1}={}){if(su(e,ql()))return;let r;r=n?e:t[s()?0:t.length-1],r&&r.focus()}function l(){window.removeEventListener(`focusin`,l),c()}function u(){setTimeout(()=>{su(e,ql())||c({resetToRoot:!0})}),window.addEventListener(`focusin`,l)}function d(){window.removeEventListener(`keydown`,a),window.removeEventListener(`focusin`,l),window.removeEventListener(`focusout`,u),i.disconnect(),Array.from(e.children).includes(r)&&e.removeChild(r),e.style.removeProperty(`outline`)}return window.addEventListener(`keydown`,a),window.addEventListener(`focusout`,u),o(),{disconnect:d}}var du=v`
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
`,fu={supportsAdoptingStyleSheets:window.ShadowRoot&&(window.ShadyCSS===void 0||window.ShadyCSS.nativeShadow)&&`adoptedStyleSheets`in Document.prototype&&`replace`in CSSStyleSheet.prototype,adoptStyle:void 0,adoptStyles:void 0},pu=new WeakMap;function mu(e){return Array.from(e.cssRules).map(e=>e.cssText).join(``)}function hu(e,t,{teardown:n=!1}={}){let r=e===document?document.body:e,i=t.cssText||mu(t);if(n){let e=Array.from(r.querySelectorAll(`style`));for(let t of e)if(t.textContent===i){t.remove();break}}else{let e=document.createElement(`style`),t=window.litNonce;t!==void 0&&e.setAttribute(`nonce`,t),e.textContent=i,r.appendChild(e)}}function gu(e,t,{teardown:n=!1}={}){let r=!1;e&&!pu.has(e)&&pu.set(e,[]);let i=pu.get(e)??[],a=i.find(e=>t===e);return a&&n?i.splice(i.indexOf(t),1):!a&&!n?i.push(t):(a&&!n||!a&&n)&&(r=!0),{haltFurtherExecution:r}}function _u(e,t,{teardown:n=!1}={}){let{haltFurtherExecution:r}=gu(e,t,{teardown:n});if(r)return;if(!fu.supportsAdoptingStyleSheets||Gr.isIOS){hu(e,t,{teardown:n});return}let i=t instanceof CSSStyleSheet?t:t.styleSheet;if(!i)throw Error(`Please provide a CSSResultOrNative style`);n?e.adoptedStyleSheets.includes(i)&&e.adoptedStyleSheets.splice(e.adoptedStyleSheets.indexOf(i),1):e.adoptedStyleSheets=[...e.adoptedStyleSheets,i]}function vu(e,t,{teardown:n=!1}={}){for(let r of t)fu.adoptStyle(e,r,{teardown:n})}fu.adoptStyle=_u,fu.adoptStyles=vu;function yu({wrappingDialogNodeL1:e,contentWrapperNodeL2:t,contentNodeL3:n}){if(!(t.isConnected||n.isConnected))throw Error(`[OverlayController] Could not find a render target, since the provided contentNode is not connected to the DOM. Make sure that it is connected, e.g. by doing "document.body.appendChild(contentNode)", before passing it on.`);let r,i=document.createComment(`tempMarker`);t.isConnected?(r=t.parentElement||t.getRootNode(),r.insertBefore(i,t),e.appendChild(t)):n.assignedSlot?(r=n.assignedSlot.parentElement||n.assignedSlot.getRootNode(),r.insertBefore(i,n.assignedSlot),e.appendChild(t),t.appendChild(n.assignedSlot)):(r=n.parentElement||n.getRootNode(),r.insertBefore(i,n),e.appendChild(t),t.appendChild(n)),r.insertBefore(e,i),r?.removeChild(i)}async function bu(){return j(()=>import(`./popper.js`),[],import.meta.url)}var xu=new WeakMap,Su=class e extends EventTarget{constructor(e={},t=Kl){super(),this.manager=t,this.__sharedConfig=e,this.__activeElementRightBeforeHide=null,this.config={},this._defaultConfig={placementMode:void 0,contentNode:e.contentNode,contentWrapperNode:e.contentWrapperNode,invokerNode:e.invokerNode,backdropNode:e.backdropNode,referenceNode:void 0,elementToFocusAfterHide:e.invokerNode,inheritsReferenceWidth:`none`,hasBackdrop:!1,isBlocking:!1,preventsScroll:!1,trapsKeyboardFocus:!1,hidesOnEsc:!1,hidesOnOutsideEsc:!1,hidesOnOutsideClick:!1,isTooltip:!1,isAlertDialog:!1,invokerRelation:`description`,visibilityTriggerFunction:void 0,handlesAccessibility:!1,popperConfig:{placement:`top`,strategy:`fixed`,modifiers:[{name:`preventOverflow`,enabled:!0,options:{boundariesElement:`viewport`,padding:8}},{name:`flip`,options:{boundariesElement:`viewport`,padding:16}},{name:`offset`,enabled:!0,options:{offset:[0,8]}},{name:`arrow`,enabled:!1}]},viewportConfig:{placement:`center`},zIndex:9999},this._contentId=`overlay-content--${Math.random().toString(36).slice(2,10)}`,this.__originalAttrs=new Map,this.updateConfig(e),this.__hasActiveTrapsKeyboardFocus=!1,this.__hasActiveBackdrop=!0,this.__escKeyHandler=this.__escKeyHandler.bind(this),this.__cancelHandler=this.__cancelHandler.bind(this)}get invoker(){return this.invokerNode}get content(){return this.__wrappingDialogNode}get placementMode(){return this.config?.placementMode}get invokerNode(){return this.config?.invokerNode}get referenceNode(){return this.config?.referenceNode}get contentNode(){return this.config?.contentNode}get contentWrapperNode(){return this.__contentWrapperNode||this.config?.contentWrapperNode}get backdropNode(){return this.__backdropNode||this.config?.backdropNode}get elementToFocusAfterHide(){return this.__elementToFocusAfterHide||this.config?.elementToFocusAfterHide}get hasBackdrop(){return!!this.backdropNode||this.config?.hasBackdrop}get isBlocking(){return this.config?.isBlocking}get preventsScroll(){return this.config?.preventsScroll}get trapsKeyboardFocus(){return this.config?.trapsKeyboardFocus}get hidesOnEsc(){return this.config?.hidesOnEsc}get hidesOnOutsideClick(){return this.config?.hidesOnOutsideClick}get hidesOnOutsideEsc(){return this.config?.hidesOnOutsideEsc}get inheritsReferenceWidth(){return this.config?.inheritsReferenceWidth}get handlesAccessibility(){return this.config?.handlesAccessibility}get isTooltip(){return this.config?.isTooltip}get isAlertDialog(){return this.config?.isAlertDialog}get invokerRelation(){return this.config?.invokerRelation}get popperConfig(){return this.config?.popperConfig}get viewportConfig(){return this.config?.viewportConfig}get visibilityTriggerFunction(){return this.config?.visibilityTriggerFunction}get _referenceNode(){return this.referenceNode||this.invokerNode}set elevation(e){this.__wrappingDialogNode.style.zIndex=`${this.config.zIndex+e}`}get elevation(){return Number(this.contentWrapperNode?.style.zIndex)}updateConfig(e){this.teardown(),this.__prevConfig=this.config,this.config={...this._defaultConfig,...this.__sharedConfig,...e,popperConfig:{...this._defaultConfig.popperConfig||{},...this.__sharedConfig.popperConfig||{},...e.popperConfig||{},modifiers:[...this._defaultConfig.popperConfig?.modifiers||[],...this.__sharedConfig.popperConfig?.modifiers||[],...e.popperConfig?.modifiers||[]]}},this.__validateConfiguration(this.config),this._init(),this.__elementToFocusAfterHide=void 0,this.#e()||this.manager.add(this)}#e(){return!!this.manager.list.find(e=>this===e)}__validateConfiguration(e){if(!e.placementMode)throw Error(`[OverlayController] You need to provide a .placementMode ("global"|"local")`);if(![`global`,`local`].includes(e.placementMode))throw Error(`[OverlayController] "${e.placementMode}" is not a valid .placementMode, use ("global"|"local")`);if(!e.contentNode)throw Error(`[OverlayController] You need to provide a .contentNode`);if(e.isTooltip&&!e.handlesAccessibility)throw Error(`[OverlayController] .isTooltip only takes effect when .handlesAccessibility is enabled`)}_init(){this.__contentHasBeenInitialized||=(this.__initContentDomStructure(),!0),this.contentWrapperNode.removeAttribute(`style`),this.contentWrapperNode.removeAttribute(`class`),this.placementMode===`local`&&(e.popperModule||=bu()),this.__handleOverlayStyles({phase:`init`}),this._handleFeatures({phase:`init`})}__handleOverlayStyles({phase:e}){let t=this.contentWrapperNode?.getRootNode();e===`init`?fu.adoptStyle(t,du):e===`teardown`&&fu.adoptStyle(t,du,{teardown:!0})}__initContentDomStructure(){let e=document.createElement(this.config?._noDialogEl?`div`:`dialog`);e.setAttribute(`role`,`none`),e.setAttribute(`data-overlay-outer-wrapper`,``),e.style.cssText=`display:none; z-index: ${this.config.zIndex}; padding: 0;`,this.__wrappingDialogNode=e,this.config?.contentWrapperNode||(this.__contentWrapperNode=document.createElement(`div`)),this.contentWrapperNode.setAttribute(`data-id`,`content-wrapper`),yu({wrappingDialogNodeL1:e,contentWrapperNodeL2:this.contentWrapperNode,contentNodeL3:this.contentNode}),e.open=!0,this.isTooltip&&e.setAttribute(`tabindex`,`-1`),this.__wrappingDialogNode.style.display=`none`,this.contentWrapperNode.style.zIndex=`1`,getComputedStyle(this.contentNode).position===`absolute`&&(this.contentNode.style.position=`static`),HTMLDialogElement&&`closedBy`in HTMLDialogElement.prototype?e.closedBy=`none`:(e.addEventListener(`keydown`,e=>{e.key===`Escape`&&e.preventDefault()}),e.addEventListener(`keyup`,e=>{e.key===`Escape`&&e.preventDefault()}),e.addEventListener(`cancel`,e=>{e.stopPropagation()}),e.addEventListener(`close`,e=>{e.stopPropagation()}))}_handleZIndex({phase:e}){if(this.placementMode===`local`&&e===`setup`){let e=Number(getComputedStyle(this.contentNode).zIndex);(e<1||Number.isNaN(e))&&(this.contentNode.style.zIndex=`1`)}}__setupTeardownAccessibility({phase:e}){if(e===`init`){this.__storeOriginalAttrs(this.contentNode,[`role`,`id`]);let e=this.trapsKeyboardFocus;if(this.invokerNode){let t=[`aria-labelledby`,`aria-describedby`];e||t.push(`aria-expanded`),this.__storeOriginalAttrs(this.invokerNode,t)}this.contentNode.id||this.contentNode.setAttribute(`id`,this._contentId),this.isTooltip?(this.invokerNode&&this.invokerNode.setAttribute(this.invokerRelation===`label`?`aria-labelledby`:`aria-describedby`,this._contentId),this.contentNode.setAttribute(`role`,`tooltip`)):(this.invokerNode&&!e&&this.invokerNode.setAttribute(`aria-expanded`,`${this.isShown}`),this.isAlertDialog?this.contentNode.setAttribute(`role`,`alertdialog`):this.contentNode.getAttribute(`role`)||this.contentNode.setAttribute(`role`,`dialog`))}else e===`teardown`&&this.__restoreOriginalAttrs()}__storeOriginalAttrs(e,t){let n={};t.forEach(t=>{n[t]=e.getAttribute(t)}),this.__originalAttrs.set(e,n)}__restoreOriginalAttrs(){for(let[e,t]of this.__originalAttrs)Object.entries(t).forEach(([t,n])=>{n===null?e.removeAttribute(t):e.setAttribute(t,n)});this.__originalAttrs.clear()}get isShown(){return this.__wrappingDialogNode?.style.display!==`none`}async show(e=this.elementToFocusAfterHide){if(this._showComplete&&await this._showComplete,this._showComplete=new Promise(e=>{this._showResolve=e}),this.manager&&this.manager.show(this),this.isShown){this._showResolve();return}let t=new CustomEvent(`before-show`,{cancelable:!0});this.dispatchEvent(t),t.defaultPrevented||(`HTMLDialogElement`in window&&this.__wrappingDialogNode instanceof HTMLDialogElement&&(this.__wrappingDialogNode.open=!0),this.__wrappingDialogNode.style.display=``,this._keepBodySize({phase:`before-show`}),await this._handleFeatures({phase:`show`}),this._keepBodySize({phase:`show`}),await this._handlePosition({phase:`show`}),this.__elementToFocusAfterHide=e,this.dispatchEvent(new Event(`show`)),await this._transitionShow({backdropNode:this.backdropNode,contentNode:this.contentNode})),this._showResolve()}async _handlePosition({phase:e}){if(this.placementMode===`global`){let t=`overlays__overlay-container--${this.viewportConfig.placement}`;e===`show`?(this.contentWrapperNode.classList.add(`overlays__overlay-container`),this.contentWrapperNode.classList.add(t),this.contentNode.classList.add(`overlays__overlay`)):e===`hide`&&(this.contentWrapperNode.classList.remove(`overlays__overlay-container`),this.contentWrapperNode.classList.remove(t),this.contentNode.classList.remove(`overlays__overlay`))}else this.placementMode===`local`&&e===`show`&&(await this.__createPopperInstance(),this._popper.forceUpdate())}_keepBodySize({phase:e}){if(this.preventsScroll)switch(e){case`before-show`:this.__bodyClientWidth=document.body.clientWidth,this.__bodyClientHeight=document.body.clientHeight,this.__bodyMarginRightInline=document.body.style.marginRight,this.__bodyMarginBottomInline=document.body.style.marginBottom;break;case`show`:{if(window.getComputedStyle){let e=window.getComputedStyle(document.body);this.__bodyMarginRight=parseInt(e.getPropertyValue(`margin-right`),10),this.__bodyMarginBottom=parseInt(e.getPropertyValue(`margin-bottom`),10)}else this.__bodyMarginRight=0,this.__bodyMarginBottom=0;let e=document.body.clientWidth-this.__bodyClientWidth,t=document.body.clientHeight-this.__bodyClientHeight,n=this.__bodyMarginRight+e,r=this.__bodyMarginBottom+t;window.CSS?.number&&document.body.attributeStyleMap?.set?(document.body.attributeStyleMap.set(`margin-right`,CSS.px(n)),document.body.attributeStyleMap.set(`margin-bottom`,CSS.px(r))):(document.body.style.marginRight=`${n}px`,document.body.style.marginBottom=`${r}px`);break}case`hide`:document.body.style.marginRight=this.__bodyMarginRightInline||``,document.body.style.marginBottom=this.__bodyMarginBottomInline||``;break}}async hide(){if(this._hideComplete=new Promise(e=>{this._hideResolve=e}),this.__activeElementRightBeforeHide=this.contentNode.getRootNode().activeElement,this.manager&&this.manager.hide(this),!this.isShown){this._hideResolve();return}let e=new CustomEvent(`before-hide`,{cancelable:!0});this.dispatchEvent(e),e.defaultPrevented||(await this._transitionHide({backdropNode:this.backdropNode,contentNode:this.contentNode}),`HTMLDialogElement`in window&&this.__wrappingDialogNode instanceof HTMLDialogElement&&this.__wrappingDialogNode.close(),this.__wrappingDialogNode.style.display=`none`,this._handleFeatures({phase:`hide`}),this._keepBodySize({phase:`hide`}),this.dispatchEvent(new Event(`hide`)),this._restoreFocus()),this._hideResolve()}async transitionHide(e){}async _transitionHide({backdropNode:e,contentNode:t}){await this.transitionHide({backdropNode:e,contentNode:t}),this._handlePosition({phase:`hide`}),e&&e.classList.remove(`overlays__backdrop--animation-in`)}async transitionShow(e){}async _transitionShow(e){await this.transitionShow({backdropNode:this.backdropNode,contentNode:this.contentNode}),e.backdropNode&&e.backdropNode.classList.add(`overlays__backdrop--animation-in`)}_restoreFocus(){this.__activeElementRightBeforeHide instanceof HTMLElement&&this.contentNode.contains(this.__activeElementRightBeforeHide)&&(this.elementToFocusAfterHide instanceof HTMLElement?(this.elementToFocusAfterHide.focus(),this.elementToFocusAfterHide.scrollIntoView({block:`nearest`})):this.__activeElementRightBeforeHide.blur())}async toggle(){return this.isShown?this.hide():this.show()}_handleFeatures({phase:e}){this._handleZIndex({phase:e}),this.preventsScroll&&this._handlePreventsScroll({phase:e}),this.isBlocking&&this._handleBlocking({phase:e}),this.hasBackdrop&&this._handleBackdrop({phase:e}),this.trapsKeyboardFocus&&this._handleTrapsKeyboardFocus({phase:e}),this.hidesOnEsc&&this._handleHidesOnEsc({phase:e}),this.hidesOnOutsideEsc&&this._handleHidesOnOutsideEsc({phase:e}),this.hidesOnOutsideClick&&this._handleHidesOnOutsideClick({phase:e}),this.handlesAccessibility&&this._handleAccessibility({phase:e}),this.inheritsReferenceWidth&&this._handleInheritsReferenceWidth(),this.visibilityTriggerFunction&&this._handleVisibilityTriggers({phase:e})}_handleVisibilityTriggers({phase:e}){typeof this.visibilityTriggerFunction==`function`&&(e===`init`&&(this.__visibilityTriggerHandler=this.visibilityTriggerFunction({phase:e,controller:this})),this.__visibilityTriggerHandler[e]&&this.__visibilityTriggerHandler[e]())}_handlePreventsScroll({phase:e}){switch(e){case`show`:this.manager.requestToPreventScroll();break;case`hide`:this.manager.requestToEnableScroll();break}}_handleBlocking({phase:e}){switch(e){case`show`:this.manager.requestToShowOnly(this);break;case`hide`:this.manager.retractRequestToShowOnly(this);break}}get hasActiveBackdrop(){return this.__hasActiveBackdrop}_handleBackdrop({phase:e}){switch(e){case`init`:this.__backdropInitialized||=(this.config?.backdropNode||(this.__backdropNode=document.createElement(`div`),this.__backdropNode.classList.add(`overlays__backdrop`)),this.__wrappingDialogNode.prepend(this.backdropNode),!0);break;case`show`:this.config.hasBackdrop&&this.backdropNode.classList.add(`overlays__backdrop--visible`),this.__hasActiveBackdrop=!0;break;case`hide`:case`teardown`:this.backdropNode.classList.remove(`overlays__backdrop--visible`),this.__hasActiveBackdrop=!1;break}}get hasActiveTrapsKeyboardFocus(){return this.__hasActiveTrapsKeyboardFocus}_handleTrapsKeyboardFocus({phase:e}){e===`show`?(`showModal`in this.__wrappingDialogNode&&(this.__wrappingDialogNode.close(),this.__wrappingDialogNode.showModal()),this.enableTrapsKeyboardFocus()):(e===`hide`||e===`teardown`)&&this.disableTrapsKeyboardFocus()}enableTrapsKeyboardFocus(){this.__hasActiveTrapsKeyboardFocus||(this.manager&&this.manager.disableTrapsKeyboardFocusForAll(),this.contentNode.shadowRoot&&console.warn(`[overlays]: For best accessibility (compatibility with Safari + VoiceOver), provide a contentNode that is not a host for a shadow root`),this._containFocusHandler=uu(this.contentNode),this.__hasActiveTrapsKeyboardFocus=!0,this.manager&&this.manager.informTrapsKeyboardFocusGotEnabled(this.placementMode))}disableTrapsKeyboardFocus({findNewTrap:e=!0}={}){this.__hasActiveTrapsKeyboardFocus&&(this._containFocusHandler&&=(this._containFocusHandler.disconnect(),void 0),this.__hasActiveTrapsKeyboardFocus=!1,this.manager&&this.manager.informTrapsKeyboardFocusGotDisabled({disabledCtrl:this,findNewTrap:e}))}__cancelHandler(e){e.preventDefault()}__escKeyHandler(e){e.key!==`Escape`||xu.has(e)||(e.composedPath().includes(this.contentNode)||su(this.contentNode,e.target))&&(this.hide(),xu.set(e,this))}#t=e=>{e.key===`Escape`&&(e.composedPath().includes(this.contentNode)||su(this.contentNode,e.target)||this.hide())};_handleHidesOnEsc({phase:e}){e===`show`?(this.contentNode.addEventListener(`keyup`,this.__escKeyHandler),this.invokerNode&&this.invokerNode.addEventListener(`keyup`,this.__escKeyHandler)):(e===`hide`||e===`teardown`)&&(this.contentNode.removeEventListener(`keyup`,this.__escKeyHandler),this.invokerNode&&this.invokerNode.removeEventListener(`keyup`,this.__escKeyHandler))}_handleHidesOnOutsideEsc({phase:e}){e===`show`?document.addEventListener(`keyup`,this.#t):(e===`hide`||e===`teardown`)&&document.removeEventListener(`keyup`,this.#t)}_handleInheritsReferenceWidth(){if(!this._referenceNode||this.placementMode===`global`)return;let e=`${this._referenceNode.getBoundingClientRect().width}px`;switch(this.inheritsReferenceWidth){case`max`:this.contentWrapperNode.style.maxWidth=e;break;case`full`:this.contentWrapperNode.style.width=e;break;case`min`:this.contentWrapperNode.style.minWidth=e,this.contentWrapperNode.style.width=`auto`;break}}_handleHidesOnOutsideClick({phase:e}){let t=e===`show`?`addEventListener`:`removeEventListener`;if(e===`show`){let e=!1,t=!1;this.__onInsideMouseDown=()=>{e=!0},this.__onInsideMouseUp=()=>{t=!0},this.__onDocumentMouseUp=()=>{setTimeout(()=>{!e&&!t&&this.hide(),e=!1,t=!1})},this.__onWindowBlur=()=>{setTimeout(()=>{this.hide()})}}this.contentWrapperNode[t](`mousedown`,this.__onInsideMouseDown,!0),this.contentWrapperNode[t](`mouseup`,this.__onInsideMouseUp,!0),this.invokerNode&&(this.invokerNode[t](`mousedown`,this.__onInsideMouseDown,!0),this.invokerNode[t](`mouseup`,this.__onInsideMouseUp,!0)),document.documentElement[t](`mouseup`,this.__onDocumentMouseUp,!0),window[t](`blur`,this.__onWindowBlur)}_handleAccessibility({phase:e}){(e===`init`||e===`teardown`)&&this.__setupTeardownAccessibility({phase:e});let t=this.trapsKeyboardFocus;this.invokerNode&&!this.isTooltip&&!t&&this.invokerNode.setAttribute(`aria-expanded`,`${e===`show`}`)}teardown(){this.__handleOverlayStyles({phase:`teardown`}),this._handleFeatures({phase:`teardown`}),this.#e()&&this.manager.remove(this)}async __createPopperInstance(){if(this._popper&&=(this._popper.destroy(),void 0),e.popperModule!==void 0){let{createPopper:t}=await e.popperModule;this._popper=t(this._referenceNode,this.contentWrapperNode,{...this.config?.popperConfig})}}_hasDisabledInvoker(){return this.invokerNode?this.invokerNode.disabled||this.invokerNode.getAttribute(`aria-disabled`)===`true`:!1}};Su.popperModule=void 0;function Cu(e,t){if(typeof e!=`object`||typeof t!=`object`||e===null||t===null)return e===t;let n=Object.keys(e),r=Object.keys(t);return n.length===r.length?n.every(n=>Cu(e[n],t[n])):!1}var wu=Or(e=>class extends e{static get properties(){return{opened:{type:Boolean,reflect:!0}}}#e=!1;constructor(){super(),this.opened=!1,this.config={},this.toggle=this.toggle.bind(this),this.open=this.open.bind(this),this.close=this.close.bind(this)}get config(){return this.__config}set config(e){let t=!Cu(this.config,e);this._overlayCtrl&&t&&this._overlayCtrl.updateConfig(e),this.__config=e,this._overlayCtrl&&t&&this.__syncToOverlayController()}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`opened`&&this.opened!==t&&this.dispatchEvent(new CustomEvent(`opened-changed`,{detail:{opened:this.opened}}))}_defineOverlay({contentNode:e,invokerNode:t,referenceNode:n,backdropNode:r,contentWrapperNode:i}){let a=this._defineOverlayConfig()||{};return new Su({contentNode:e,invokerNode:t,referenceNode:n,backdropNode:r,contentWrapperNode:i,...a,...this.config,popperConfig:{...a.popperConfig||{},...this.config?.popperConfig||{},modifiers:[...a.popperConfig?.modifiers||[],...this.config?.popperConfig?.modifiers||[]]}})}_defineOverlayConfig(){return{placementMode:`local`}}updated(e){super.updated(e),e.has(`opened`)&&this._overlayCtrl&&!this.__blockSyncToOverlayCtrl&&this.__syncToOverlayController()}_setupOpenCloseListeners(){this.__closeEventInContentNodeHandler=e=>{e.stopPropagation(),this._overlayCtrl.hide()},this._overlayContentNode&&this._overlayContentNode.addEventListener(`close-overlay`,this.__closeEventInContentNodeHandler)}_teardownOpenCloseListeners(){this._overlayContentNode&&this._overlayContentNode.removeEventListener(`close-overlay`,this.__closeEventInContentNodeHandler)}connectedCallback(){super.connectedCallback(),this.updateComplete.then(()=>{this.isConnected&&(this.#e||=(this._setupOverlayCtrl(),!0))})}async disconnectedCallback(){super.disconnectedCallback(),await this._isPermanentlyDisconnected()&&(this._teardownOverlayCtrl(),this.#e=!1)}static enabledWarnings=super.enabledWarnings?.filter(e=>e!==`change-in-update`)||[];get _overlayInvokerNode(){return Array.from(this.children).find(e=>e.slot===`invoker`)}get _overlayReferenceNode(){}get _overlayBackdropNode(){return this.__cachedOverlayBackdropNode||=Array.from(this.children).find(e=>e.slot===`backdrop`),this.__cachedOverlayBackdropNode}get _overlayContentNode(){return this._cachedOverlayContentNode||=Array.from(this.children).find(e=>e.slot===`content`)||this.config.contentNode,this._cachedOverlayContentNode}get _overlayContentWrapperNode(){return this.shadowRoot?.querySelector(`#overlay-content-node-wrapper`)}_setupOverlayCtrl(){if(this.#e)return;let e={contentNode:this._overlayContentNode,contentWrapperNode:this._overlayContentWrapperNode,invokerNode:this._overlayInvokerNode,referenceNode:this._overlayReferenceNode,backdropNode:this._overlayBackdropNode};this._overlayCtrl?this._overlayCtrl.updateConfig(e):this._overlayCtrl=this._defineOverlay(e),this.__syncToOverlayController(),this.__setupSyncFromOverlayController(),this._setupOpenCloseListeners()}_teardownOverlayCtrl(){this._overlayCtrl&&(this._teardownOpenCloseListeners(),this.__teardownSyncFromOverlayController(),this._overlayCtrl.teardown())}async _setOpenedWithoutPropertyEffects(e){this.__blockSyncToOverlayCtrl=!0,this.opened=e,await this.updateComplete,this.__blockSyncToOverlayCtrl=!1}__setupSyncFromOverlayController(){this.__onOverlayCtrlShow=()=>{this.opened=!0},this.__onOverlayCtrlHide=()=>{this.opened=!1},this.__onBeforeShow=e=>{let t=new CustomEvent(`before-opened`,{cancelable:!0});this.dispatchEvent(t),t.defaultPrevented&&(this._setOpenedWithoutPropertyEffects(this._overlayCtrl.isShown),e.preventDefault())},this.__onBeforeHide=e=>{let t=new CustomEvent(`before-closed`,{cancelable:!0});this.dispatchEvent(t),t.defaultPrevented&&(this._setOpenedWithoutPropertyEffects(this._overlayCtrl.isShown),e.preventDefault())},this._overlayCtrl.addEventListener(`show`,this.__onOverlayCtrlShow),this._overlayCtrl.addEventListener(`hide`,this.__onOverlayCtrlHide),this._overlayCtrl.addEventListener(`before-show`,this.__onBeforeShow),this._overlayCtrl.addEventListener(`before-hide`,this.__onBeforeHide)}__teardownSyncFromOverlayController(){this._overlayCtrl.removeEventListener(`show`,this.__onOverlayCtrlShow),this._overlayCtrl.removeEventListener(`hide`,this.__onOverlayCtrlHide),this._overlayCtrl.removeEventListener(`before-show`,this.__onBeforeShow),this._overlayCtrl.removeEventListener(`before-hide`,this.__onBeforeHide)}__syncToOverlayController(){this.opened?this._overlayCtrl.show():this._overlayCtrl.hide()}async toggle(){await this._overlayCtrl.toggle()}async open(){await this._overlayCtrl.show()}async close(){await this._overlayCtrl.hide()}repositionOverlay(){let e=this._overlayCtrl;e.placementMode===`local`&&e._popper&&e._popper.update()}async _isPermanentlyDisconnected(){return await this.updateComplete,!this.isConnected}});function Tu(){return{visibilityTriggerFunction:({controller:e})=>{function t(){e._hasDisabledInvoker()||e.toggle()}return{init:()=>{e.invokerNode?.addEventListener(`click`,t)},teardown:()=>{e.invokerNode?.removeEventListener(`click`,t)}}}}}var Eu=()=>({placementMode:`local`,inheritsReferenceWidth:`min`,hidesOnOutsideClick:!0,hidesOnEsc:!0,popperConfig:{placement:`bottom-start`,modifiers:[{name:`offset`,enabled:!1}]},handlesAccessibility:!0,...Tu()}),Du=class extends wu(C){_defineOverlayConfig(){return{...Eu()}}_addEventListeners(){this.actionItems.forEach(e=>{e.addEventListener(`click`,e=>{e.target?.dispatchEvent(new Event(`close-overlay`,{bubbles:!0}))})})}_setupInvoker(){let e=this.invokerNodes[0];e&&(e.setAttribute(`id`,`invoker-${this.uid}`),e.setAttribute(`aria-controls`,`content-${this.uid}`))}_setupContent(){let e=this.contentNodes[0];e&&(e.setAttribute(`id`,`content-${this.uid}`),e.setAttribute(`role`,`none`))}_setupOverlayCtrl(){super._setupOverlayCtrl(),this._setupInvoker(),this._setupContent()}firstUpdated(){this.uid=Kr(),this._addEventListeners()}render(){return g`
      <slot name="invoker"></slot>
      <slot name="backdrop"></slot>
      <slot name="content"></slot>
    `}};Du.styles=v`
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
  `,m([O({selector:`craft-action-item`})],Du.prototype,`actionItems`,void 0),m([O({slot:`invoker`})],Du.prototype,`invokerNodes`,void 0),m([O({slot:`content`})],Du.prototype,`contentNodes`,void 0),customElements.get(`craft-action-menu`)||customElements.define(`craft-action-menu`,Du);var Ou=new WeakMap;function ku(e,t){Array.from(e.childNodes).forEach(n=>{if(n.nodeName===`#text`){let r=RegExp(`^(.*?)(${t})(.*)$`,`i`),i=n.nodeValue.match(r);if(i){let t=document.createTextNode(i[1]);e.appendChild(t);let r=document.createElement(`b`);r.textContent=i[2],e.appendChild(r);let a=document.createTextNode(i[3]);e.appendChild(a),e.removeChild(n),Ou.set(e,()=>{e.appendChild(n),e.contains(t)&&t.parentNode!==null&&t.parentNode.removeChild(t),e.contains(r)&&r.parentNode!==null&&r.parentNode.removeChild(r),e.contains(a)&&a.parentNode!==null&&a.parentNode.removeChild(a)})}}else ku(n,t)})}function Au(e){Ou.has(e)&&Ou.get(e)(),Array.from(e.childNodes).forEach(e=>{e.nodeName===`#text`?Ou.has(e)&&Ou.get(e)():Au(e)})}var ju=class extends Do{static get validatorName(){return`MatchesOption`}execute(e,t,n){return n?.node.modelValue instanceof So}};function Mu(e){return Array.isArray(e)?e:[e]}var Nu=Or(e=>class extends Ro(e){static get properties(){return{allowCustomChoice:{type:Boolean,attribute:`allow-custom-choice`},modelValue:{type:Object}}}get modelValue(){return this.__getChoicesFrom(super.modelValue)}set modelValue(e){if(super.modelValue=e,e==null||e===``)this._customChoices=new Set;else if(this.allowCustomChoice){let t=this.modelValue;this._customChoices=new Set(Mu(e)),this.requestUpdate(`modelValue`,t)}}get formattedValue(){return this.__getChoicesFrom(super.formattedValue)}set formattedValue(e){if(super.formattedValue=e,e==null)this._customChoices=new Set;else if(this.allowCustomChoice){let t=this.modelValue;this._customChoices=new Set(Mu(e).map(e=>this.formElements.find(t=>t.formattedValue===e)?.modelValue||e)),this.requestUpdate(`modelValue`,t)}}get serializedValue(){return this.__getChoicesFrom(super.serializedValue)}set serializedValue(e){if(super.serializedValue=e,e==null)this._customChoices=new Set;else if(this.allowCustomChoice){let t=this.modelValue;this._customChoices=new Set(Mu(e).map(e=>this.formElements.find(t=>t.serializedValue===e)?.modelValue||e)),this.requestUpdate(`modelValue`,t)}}get customChoices(){if(!this.allowCustomChoice)return[];let e=this._getCheckedElements();return Array.from(this._customChoices).filter(t=>!e.some(e=>e.choiceValue===t))}constructor(){super(),this.allowCustomChoice=!1,this._customChoices=new Set}__getChoicesFrom(e){let t=e;return this.allowCustomChoice?this.multipleChoice?[...Mu(t),...this.customChoices]:t===``?this._customChoices.values().next().value||``:t:t}_isEmpty(){return super._isEmpty()&&this._customChoices.size===0}clear(){this._customChoices=new Set,super.clear()}parser(e){return this.allowCustomChoice&&Array.isArray(e)?e.filter(e=>e.trim()!==``):e}}),Pu=new WeakMap,Fu=class extends co(wu(Nu(_c))){static get properties(){return{autocomplete:{type:String,reflect:!0},matchMode:{type:String,attribute:`match-mode`},showAllOnEmpty:{type:Boolean,attribute:`show-all-on-empty`},requireOptionMatch:{type:Boolean},allowCustomChoice:{type:Boolean,attribute:`allow-custom-choice`},__shouldAutocompleteNextUpdate:Boolean}}static get styles(){return[...super.styles,v`
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
      `]}static get localizeNamespaces(){return[{"lion-combobox":e=>{switch(e){case`bg-BG`:case`bg`:return j(()=>import(`./bg.js`),[],import.meta.url);case`cs-CZ`:case`cs`:return j(()=>import(`./cs.js`),[],import.meta.url);case`de-AT`:case`de-DE`:case`de`:return j(()=>import(`./de.js`),[],import.meta.url);case`en-AU`:case`en-GB`:case`en-PH`:case`en-US`:case`en`:return j(()=>import(`./en.js`),[],import.meta.url);case`es-ES`:case`es`:return j(()=>import(`./es.js`),[],import.meta.url);case`fr-FR`:case`fr-BE`:case`fr`:return j(()=>import(`./fr.js`),[],import.meta.url);case`hu-HU`:case`hu`:return j(()=>import(`./hu.js`),[],import.meta.url);case`it-IT`:case`it`:return j(()=>import(`./it.js`),[],import.meta.url);case`nl-BE`:case`nl-NL`:case`nl`:return j(()=>import(`./nl.js`),[],import.meta.url);case`pl-PL`:case`pl`:return j(()=>import(`./pl.js`),[],import.meta.url);case`ro-RO`:case`ro`:return j(()=>import(`./ro.js`),[],import.meta.url);case`ru-RU`:case`ru`:return j(()=>import(`./ru.js`),[],import.meta.url);case`sk-SK`:case`sk`:return j(()=>import(`./sk.js`),[],import.meta.url);case`uk-UA`:case`uk`:return j(()=>import(`./uk.js`),[],import.meta.url);case`zh-CN`:case`zh`:return j(()=>import(`./zh.js`),[],import.meta.url);default:return j(()=>import(`./en.js`),[],import.meta.url)}}},...super.localizeNamespaces]}get modelValue(){let e=super.modelValue;return e===``?this.parser(this.value):e}set modelValue(e){super.modelValue=e}get value(){return this._inputNode?.value||this.__value||``}set value(e){this._inputNode?(this._inputNode.value=e,this.__value=void 0):this.__value=e}reset(){super.reset(),this.multipleChoice||(this.value=this._initialModelValue),this._resetListboxOptions()}_resetListboxOptions(){this.formElements.forEach((e,t)=>{this._unhighlightMatchedOption(e),!this.showAllOnEmpty||!this.opened?e.style.display=`none`:(e.style.display=``,e.setAttribute(`aria-posinset`,`${t+1}`),e.setAttribute(`aria-setsize`,`${this.formElements.length}`),e.removeAttribute(`aria-hidden`))})}_inputGroupInputTemplate(){return g`
      <div class="input-group__input">
        <slot name="selection-display"></slot>
        <slot name="input"></slot>
      </div>
    `}_overlayListboxTemplate(){return g`
      <div
        id="overlay-content-node-wrapper"
        role="dialog"
        aria-label="${this.msgLit(`lion-combobox:optionsPopup`)}"
      >
        <slot name="listbox"></slot>
      </div>
      <slot id="options-outlet"></slot>
    `}_groupTwoTemplate(){return g` ${super._groupTwoTemplate()} ${this._overlayListboxTemplate()}`}get slots(){return{...super.slots,input:()=>{if(this._ariaVersion===`1.1`){let e=document.createElement(`div`),t=document.createElement(`input`);return t.style.cssText=`
          border: none;
          outline: none;
          width: 100%;
          height: 100%;
          font: inherit;
          background: inherit;
          color: inherit;
          border-radius: inherit;
          box-sizing: border-box;
          padding: 0;`,e.appendChild(t),e}return document.createElement(`input`)},listbox:super.slots.input}}get _comboboxNode(){return this.querySelector(`[slot="input"]`)}get _selectionDisplayNode(){return this.querySelector(`[slot="selection-display"]`)}get _inputNode(){return this._ariaVersion===`1.1`&&this._comboboxNode&&this._comboboxNode.querySelector(`input`)||this._comboboxNode}get _overlayContentNode(){return this._listboxNode}get _overlayReferenceNode(){return this.shadowRoot.querySelector(`.input-group__container`)}get _overlayInvokerNode(){return this._inputNode}get _listboxNode(){return this._overlayCtrl&&this._overlayCtrl.contentNode||Array.from(this.children).find(e=>e.slot===`listbox`)}get _activeDescendantOwnerNode(){return this._inputNode}get requireOptionMatch(){return!this.allowCustomChoice}set requireOptionMatch(e){this.allowCustomChoice=!e}constructor(){super(),this.autocomplete=`both`,this.matchMode=`all`,this.showAllOnEmpty=!1,this.requireOptionMatch=!0,this.rotateKeyboardNavigation=!0,this.selectionFollowsFocus=!0,this.defaultValidators.push(new ju),this._ariaVersion=Gr.isChromium?`1.1`:`1.0`,this._listboxReceivesNoFocus=!0,this._noTypeAhead=!0,this.__prevCboxValueNonSelected=``,this.__prevCboxValue=``,this.__hadUserIntendsInlineAutoFill=!1,this.__listboxContentChanged=!1,this._onKeyUp=this._onKeyUp.bind(this),this._textboxOnClick=this._textboxOnClick.bind(this),this._textboxOnInput=this._textboxOnInput.bind(this),this._textboxOnKeydown=this._textboxOnKeydown.bind(this)}connectedCallback(){super.connectedCallback(),this._selectionDisplayNode&&(this._selectionDisplayNode.comboboxElement=this),(this.disabled||this.readOnly)&&this.__setComboboxDisabledAndReadOnly()}requestUpdate(e,t,n){if(super.requestUpdate(e,t,n),(e===`disabled`||e===`readOnly`)&&this.__setComboboxDisabledAndReadOnly(),e===`modelValue`&&this.modelValue&&this.modelValue!==t&&this._syncToTextboxCondition(this.modelValue,this._oldModelValue))if(this.multipleChoice)this._syncToTextboxMultiple(this.modelValue,this._oldModelValue);else{let e=this._getTextboxValueFromOption(this.formElements[this.checkedIndex]);this._setTextboxValue(e)}}parser(e){return this.requireOptionMatch&&this.checkedIndex===-1&&e!==``&&!Array.isArray(e)?new So(e):super.parser(e)}__unsyncCheckedIndexOnInputChange(){let e=this._autoSelectCondition(),t=this.formElements[this.checkedIndex];if(!this.multipleChoice&&!e&&t){let e=this._getTextboxValueFromOption(t);this._inputNode.value.startsWith(e)||this.setCheckedIndex(-1)}}updated(e){super.updated(e),e.has(`__shouldAutocompleteNextUpdate`)&&this.__unsyncCheckedIndexOnInputChange(),e.has(`opened`)&&(this.opened&&(this.activeIndex=-1),!this.opened&&e.get(`opened`)!==void 0&&(this.__onOverlayClose(),this.activeIndex=-1)),e.has(`autocomplete`)&&this._inputNode.setAttribute(`aria-autocomplete`,this.autocomplete),e.has(`disabled`)&&this.setAttribute(`aria-disabled`,`${this.disabled}`),e.has(`__shouldAutocompleteNextUpdate`)&&this.__shouldAutocompleteNextUpdate&&(this._handleAutocompletion(),this.__shouldAutocompleteNextUpdate=!1,this.__listboxContentChanged=!1),typeof this._selectionDisplayNode?.onComboboxElementUpdated==`function`&&this._selectionDisplayNode.onComboboxElementUpdated(e)}matchCondition(e,t){let n=-1,r=this._getTextboxValueFromOption(e);return typeof r==`string`&&typeof t==`string`&&(n=r.toLowerCase().indexOf(t.toLowerCase())),this.matchMode===`all`?n>-1:n===0}_showOverlayCondition({lastKey:e}){return this.disabled||this.readOnly||e&&([`Tab`,`Escape`].includes(e)||!this.multipleChoice&&[`Enter`].includes(e))?!1:this.filled||this.showAllOnEmpty||!this.filled&&this.multipleChoice&&this.__prevCboxValueNonSelected?!0:this.opened}_getTextboxValueFromOption(e){return e?e.choiceValue:this.modelValue instanceof So?this.modelValue.viewValue:this.modelValue}_onListboxContentChanged(){super._onListboxContentChanged(),this.__shouldAutocompleteNextUpdate=!0,this.__listboxContentChanged=!0}_textboxOnInput(e){this.__shouldAutocompleteNextUpdate=!0,this.opened=this._showOverlayCondition({})}_textboxOnKeydown(e){e.key===`Tab`&&(this.opened=!1)}_listboxOnClick(e){super._listboxOnClick(e),this._inputNode.focus(),this.multipleChoice?(this._inputNode.value=``,this._resetListboxOptions()):(this.activeIndex=-1,this.opened=!1)}_setTextboxValue(e){this._inputNode&&this._inputNode.value!==e&&(this._inputNode.value=e)}__onOverlayClose(){this.multipleChoice?this._syncToTextboxMultiple(this.modelValue,this._oldModelValue):this.checkedIndex!==-1&&this._syncToTextboxCondition(this.modelValue,this._oldModelValue,{phase:`overlay-close`})&&(this._inputNode.value=this._getTextboxValueFromOption(this.formElements[this.checkedIndex]))}_repropagationCondition(e){return super._repropagationCondition(e)||this.formElements.every(e=>!e.checked)}_onFilterMatch(e,t){this._highlightMatchedOption(e,t),e.style.display=``}_highlightMatchedOption(e,t){if(ku(e,t),e.textContent){let t=document.createElement(`span`);t.setAttribute(`aria-label`,e.textContent.replace(/\s+/g,` `)),Array.from(e.childNodes).forEach(e=>{t.appendChild(e)}),e.appendChild(t),Pu.set(e,()=>{Array.from(t.childNodes).forEach(t=>{e.appendChild(t)}),e.contains(t)&&e.removeChild(t)})}}_onFilterUnmatch(e,t,n){this._unhighlightMatchedOption(e),e.style.display=`none`}_unhighlightMatchedOption(e){Au(e),Pu.has(e)&&Pu.get(e)()}__computeUserIntendsAutoFill({prevValue:e,curValue:t}){let n=e.length<t.length,r=e.length&&t.length&&e[0].toLowerCase()!==t[0].toLowerCase();return n||r||this.__listboxContentChanged&&this.__hadUserIntendsInlineAutoFill}_handleAutocompletion(){let e=this._inputNode.selectionStart!==this._inputNode.selectionEnd&&this._inputNode.value.length!==this._inputNode.selectionStart,t=this._inputNode.value,n=this._inputNode.selectionStart,r=e&&n?t.slice(0,n):t,i=e||this.__hadSelectionLastAutofill?this.__prevCboxValueNonSelected:this.__prevCboxValue,a=!r,o=[],s=!1,c=this.__computeUserIntendsAutoFill({prevValue:i,curValue:r}),l=this.autocomplete===`both`||this.autocomplete===`inline`,u=this._autoSelectCondition(),d=this.autocomplete===`inline`||this.autocomplete===`none`;this.formElements.forEach((e,t)=>{let n=this.matchCondition(e,r),f=!1;if(f=a?this.showAllOnEmpty:d||n,u&&!s&&n&&!e.disabled){let n=()=>{this.activeIndex=t,this.selectionFollowsFocus&&!this.multipleChoice&&this.setCheckedIndex(this.activeIndex),s=!0};if(c)if(l){let t=this._getTextboxValueFromOption(e);t&&typeof t==`string`&&typeof r==`string`&&t.toLowerCase().indexOf(r.toLowerCase())===0&&(this.__textboxInlineComplete(e),n())}else n()}e.onFilterUnmatch?e.onFilterUnmatch(r,i):this._onFilterUnmatch(e,r,i),e.setAttribute(`aria-hidden`,`true`),e.removeAttribute(`aria-posinset`),e.removeAttribute(`aria-setsize`),f&&(o.push(e),e.onFilterMatch?e.onFilterMatch(r):this._onFilterMatch(e,r))});let f=o.length;o.forEach((e,t)=>{e.setAttribute(`aria-posinset`,`${t+1}`),e.setAttribute(`aria-setsize`,`${f}`),e.removeAttribute(`aria-hidden`)}),u&&!s&&!this.multipleChoice&&(this.setCheckedIndex(-1),i!==r&&(this.activeIndex=-1),this.modelValue=this.parser(t)),this.__prevCboxValueNonSelected=r,this.__prevCboxValue=this._inputNode.value,this.__hadSelectionLastAutofill=this._inputNode.value.length!==this._inputNode.selectionStart,this.__hadUserIntendsInlineAutoFill=c,this._overlayCtrl&&this._overlayCtrl._popper&&this._overlayCtrl._popper.update()}__textboxInlineComplete(e=this.formElements[this.activeIndex]){let t=this._getTextboxValueFromOption(e);if(this._inputNode.value!==t){let e=this._inputNode.value.length;this._inputNode.value=t,this._inputNode.selectionStart=e,this._inputNode.selectionEnd=this._inputNode.value.length}}_autoSelectCondition(){return this.autocomplete===`both`||this.autocomplete===`inline`}_setupListboxNode(){super._setupListboxNode(),this._listboxNode.removeAttribute(`tabindex`)}_defineOverlayConfig(){return{...Eu(),elementToFocusAfterHide:void 0,invokerNode:this._comboboxNode,visibilityTriggerFunction:void 0}}_setupOverlayCtrl(){super._setupOverlayCtrl(),this.__shouldAutocompleteNextUpdate=!0,this.__setupCombobox()}_teardownOverlayCtrl(){super._teardownOverlayCtrl(),this.__teardownCombobox()}_setupOpenCloseListeners(){super._setupOpenCloseListeners(),this._inputNode.addEventListener(`keyup`,this._onKeyUp),this._inputNode.addEventListener(`click`,this._textboxOnClick)}_teardownOpenCloseListeners(){super._teardownOpenCloseListeners(),this._inputNode.removeEventListener(`keyup`,this._onKeyUp),this._inputNode.removeEventListener(`click`,this._textboxOnClick)}_listboxOnKeyDown(e){let{key:t}=e;switch(t){case`Escape`:this.opened=!1,super._listboxOnKeyDown(e),this._setTextboxValue(``);break;case`Backspace`:case`Delete`:this.requireOptionMatch?super._listboxOnKeyDown(e):this.opened=!1;break;case`Enter`:this.opened&&e.preventDefault(),!this.requireOptionMatch&&this.multipleChoice&&(!this.formElements[this.activeIndex]||this.formElements[this.activeIndex].hasAttribute(`aria-hidden`)||!this.opened)?(this.modelValue=this.parser([...this.modelValue,this._inputNode.value]),this._inputNode.value=``,this.opened=!1):(super._listboxOnKeyDown(e),this._resetListboxOptions()),this.multipleChoice?this._inputNode.value=``:this.opened=!1;break;default:super._listboxOnKeyDown(e);break}}_syncToTextboxCondition(e,t,{phase:n}={}){return this.autocomplete===`both`||this.autocomplete===`inline`||!this.focused}_syncToTextboxMultiple(e,t=[]){if(this.requireOptionMatch){let n=e.filter(e=>!t.includes(e)),r=this.formElements.filter(e=>n.includes(e.choiceValue)).map(e=>this._getTextboxValueFromOption(e)).join(` `);this._setTextboxValue(r)}}_enhanceLightDomClasses(){let e=this.querySelector(`[slot=input]`);e&&e.classList.add(`form-control`)}__setComboboxDisabledAndReadOnly(){this._comboboxNode&&(this._comboboxNode.toggleAttribute(`disabled`,this.disabled),this._comboboxNode.setAttribute(`aria-disabled`,`${this.disabled}`),this._comboboxNode.toggleAttribute(`readonly`,this.readOnly),this._comboboxNode.setAttribute(`aria-readonly`,`${this.readOnly}`)),this._inputNode&&(this._inputNode.toggleAttribute(`disabled`,this.disabled),this._inputNode.toggleAttribute(`readOnly`,this.readOnly),this._inputNode.setAttribute(`aria-readonly`,`${this.readOnly}`),this._inputNode.tabIndex=this.disabled?-1:0)}__setupCombobox(){this._comboboxNode.setAttribute(`role`,`combobox`),this._comboboxNode.setAttribute(`aria-haspopup`,`listbox`),this._inputNode.setAttribute(`aria-autocomplete`,this.autocomplete),this._comboboxNode.setAttribute(`aria-controls`,this._listboxNode.id),this._ariaVersion===`1.1`?this._comboboxNode.setAttribute(`aria-owns`,this._listboxNode.id):this._inputNode.setAttribute(`aria-owns`,this._listboxNode.id),this._listboxNode.setAttribute(`aria-labelledby`,this._labelNode.id),this._inputNode.addEventListener(`keydown`,this._listboxOnKeyDown),this._inputNode.addEventListener(`input`,this._textboxOnInput),this._inputNode.addEventListener(`keydown`,this._textboxOnKeydown)}__teardownCombobox(){this._inputNode.removeEventListener(`keydown`,this._listboxOnKeyDown),this._inputNode.removeEventListener(`input`,this._textboxOnInput),this._inputNode.removeEventListener(`keydown`,this._textboxOnKeydown)}_onKeyUp(e){let t=e&&e.key;this.opened=this._showOverlayCondition({lastKey:t,currentValue:this._inputNode.value})}_textboxOnClick(e){this.opened=this._showOverlayCondition({})}clear(){this.value=``,super.clear(),this.__shouldAutocompleteNextUpdate=!0}},Iu=v`
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
`,Lu=class extends Fu{static get styles(){return[...super.styles,Iu]}constructor(){super(),this.defaultValidators=[]}_inputGroupInputTemplate(){return g`
      <div class="input-group__input">
        <slot name="input"></slot>
        <craft-icon
          class="indicator"
          name="chevron-down"
          style="font-size: 0.8em"
        ></craft-icon>
      </div>
    `}parser(e){return e===``?super.parser(e):e}_getTextboxValueFromOption(e){return e?e.textContent?.trim()||``:super._getTextboxValueFromOption(e)}};customElements.get(`craft-combobox`)||customElements.define(`craft-combobox`,Lu);var Ru=class extends C{constructor(...e){super(...e),this.variant=s.Default,this.label=null}render(){return g`<span
      class="${A({indicator:!0,"indicator--success":this.variant===s.Success,"indicator--danger":this.variant===s.Danger,"indicator--warning":this.variant===s.Warning,"indicator--info":this.variant===s.Info,"indicator--empty":this.variant===`empty`})}"
    >
      <slot></slot>
    </span>`}};Ru.styles=[zl,v`
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
    `],m([w({reflect:!0})],Ru.prototype,`variant`,void 0),m([w()],Ru.prototype,`label`,void 0),customElements.get(`craft-indicator`)||customElements.define(`craft-indicator`,Ru);var zu=class extends C{constructor(){super(),this.alt=!1,this.shift=!1,this.os=`Unknown`,this.os=this.detectOS()}connectedCallback(){super.connectedCallback(),this.os===`Unknown`&&(this.os=this.detectOS())}detectOS(){let e=navigator.platform.toLowerCase();return e.includes(`mac`)||/iphone|ipad|ipod/.test(e)?`Mac`:e.includes(`win`)?`Windows`:e.includes(`linux`)?`Linux`:`Unknown`}renderShortcutPrefix(){switch(this.os){case`Mac`:return`${this.alt?`⌥`:``}${this.shift?`⇧`:``}⌘`;case`Linux`:return`Super+${this.alt?`Alt+`:``}${this.shift?`Shift+`:``}`;default:return`Ctrl+${this.alt?`Alt+`:``}${this.shift?`Shift+`:``}`}}render(){return g`<span class="shortcut"
      >${this.renderShortcutPrefix()}<slot></slot
    ></span>`}};zu.styles=v`
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
  `,m([w({type:Boolean})],zu.prototype,`alt`,void 0),m([w({type:Boolean})],zu.prototype,`shift`,void 0),m([w()],zu.prototype,`os`,void 0),customElements.get(`craft-shortcut`)||customElements.define(`craft-shortcut`,zu);var Bu=v`
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
`,Vu=new WeakMap,Hu=class extends C{constructor(...e){super(...e),this.progress=0,this.total=0,this.processed=0,this.showStatus=!1,this.pending=!1,this.smooth=!1,this.label=`Progress`,u(this,Vu,0)}updated(e){if((e.has(`total`)||e.has(`processed`))&&this.total>0){let e=Math.min(100,Math.round(this.processed/this.total*100));e>=100&&p(Vu,this)<100&&this.dispatchEvent(new CustomEvent(`complete`,{bubbles:!0,composed:!0})),this.progress=e}e.has(`progress`)&&(this.progress>0&&this.pending&&(this.pending=!1),r(Vu,this,this.progress))}get progressPercent(){return Math.min(100,Math.max(0,this.progress))}get statusText(){return this.total>0?`${this.processed} / ${this.total}`:`${this.progressPercent}%`}reset(){this.progress=0,this.processed=0,this.pending=!0,r(Vu,this,0)}show(){this.hidden=!1}hide(){this.hidden=!0}render(){let e={width:this.pending?`100%`:`${this.progressPercent}%`};return g`
      <div
        class=${A({"progress-bar":!0,"progress-bar--pending":this.pending})}
        part="track"
        role="progressbar"
        aria-valuenow=${this.pending?S:this.progressPercent}
        aria-valuemin="0"
        aria-valuemax="100"
        aria-label=${this.label}
      >
        <div
          class=${A({"progress-bar__fill":!0,"progress-bar__fill--smooth":this.smooth&&!this.pending})}
          part="fill"
          style=${ee(e)}
        ></div>
      </div>
      ${this.showStatus?g`<div class="progress-bar__status" part="status">
            ${this.statusText}
          </div>`:S}
      <span class="visually-hidden">
        ${this.pending?`Loading`:`${this.progressPercent}%`}
      </span>
    `}};Hu.styles=[Bu],m([w({type:Number})],Hu.prototype,`progress`,void 0),m([w({type:Number})],Hu.prototype,`total`,void 0),m([w({type:Number})],Hu.prototype,`processed`,void 0),m([w({type:Boolean,attribute:`show-status`})],Hu.prototype,`showStatus`,void 0),m([w({type:Boolean,reflect:!0})],Hu.prototype,`pending`,void 0),m([w({type:Boolean})],Hu.prototype,`smooth`,void 0),m([w({type:String})],Hu.prototype,`label`,void 0),customElements.get(`craft-progress-bar`)||customElements.define(`craft-progress-bar`,Hu);var Uu=class extends Ro(Ho(C)){connectedCallback(){super.connectedCallback(),this.setAttribute(`role`,`radiogroup`)}resetGroup(){let e;this.formElements.forEach(t=>{typeof t.resetGroup==`function`?t.resetGroup():typeof t.reset==`function`&&(t.reset(),t.checked&&(e=t.choiceValue))}),this.modelValue=e,this.resetInteractionState()}},Wu=class extends Bo(Uo){connectedCallback(){super.connectedCallback(),this.type=`radio`}},Gu=class extends Uu{static get styles(){return[...super.styles,xa,v`
        .input-group {
          display: grid;
          gap: var(--c-spacing-xs);
        }
      `]}};customElements.get(`craft-radio-group`)||customElements.define(`craft-radio-group`,Gu);var Ku=class extends Wu{static get styles(){return[...super.styles,v`
        /* same as checkbox, potentially consolidate */
        :host {
          gap: var(--c-spacing-sm);
        }
      `]}};customElements.get(`craft-radio`)||customElements.define(`craft-radio`,Ku);var qu=class e{constructor(t={}){this.config={...e.defaultCookieOptions,...t}}set(e,t,n={}){let{path:r,domain:i,maxAge:a,expires:o,secure:s,sameSite:c,prefix:l}=Object.assign({},this.config,n),u=`${this.config.prefix}:${e}=${encodeURIComponent(t)}`;r&&(u+=`;path=${r}`),i&&(u+=`;domain=${i}`),a?u+=`;max-age-in-seconds=${a}`:o&&(u+=`;expires=${o.toUTCString()}`),s&&(u+=`;secure`),document.cookie=u}get(e){return document.cookie.replace(RegExp(`(?:(?:^|.*;\\s*)${this.config.prefix}:${e}\\s*\\=\\s*([^;]*).*$)|^.*$`),`$1`)}remove(e){this.set(e,``,{expires:new Date(`1970-01-01T00:00:00`)})}};qu.defaultCookieOptions={path:`/`,domain:null,secure:!1,sameSite:`strict`,prefix:`Craft`};var Ju=class{constructor(){this.refreshPromise=null,this.tokenName=null,this.tokenValue=null,this.refreshPromise=null}async getToken(){return this.tokenValue||await this.refreshToken(),this.tokenValue}async refreshToken(){return this.refreshPromise||=Zu.get(`users/session-info`).then(({data:e})=>{let{csrfTokenName:t,csrfTokenValue:n}=e;return this.tokenName=t??null,this.tokenValue=n??null,this.tokenValue}).finally(()=>{this.refreshPromise=null}),this.refreshPromise}clearToken(){this.tokenValue=null}};function Yu(e=``){return`/admin/actions/${e}`}function Xu(){return{"X-Registered-Asset-Bundles":[...new Set(Cp.registeredAssetBundles)].join(`,`),"X-Registered-Js-Files":[...new Set(Cp.registeredJsFiles)].join(`,`)}}var Zu=l.create({baseURL:Yu()}),Qu=new Ju;Zu.interceptors.request.use(async e=>{e.headers.set(`X-Requested-With`,`XMLHttpRequest`);let t=Xu();return Object.entries(t).forEach(([t,n])=>{e.headers.set(t,n)}),e}),Zu.interceptors.response.use(e=>e,async e=>{let t=e.config;if(e.response?.status===419||e.response?.status===403&&!t._retry){t._retry=!0;try{return Qu.clearToken(),t.headers[`X-CSRF-Token`]=await Qu.refreshToken(),l(t)}catch(e){return console.error(`Failed to refresh CSRF token:`,e),Promise.reject(e)}}return Promise.reject(e)});var $u=!1,ed=null;async function td(e){if(!$u){if(ed)return ed;$u=!0;try{return(await Zu.post(`app/api-headers`,void 0,{cancelToken:e})).data}catch{}finally{$u=!1}}}var nd=l.create({baseURL:`https://api.craftcms.com/v1/`});async function rd(e){return ed?Object.entries(ed).forEach(([t,n])=>{e.headers.set(t,n)}):(e.params=e.params||{},e.params.processCraftHeaders=1),e}async function id(e,t){if(ed)return;let{data:n}=await Zu.post(`app/process-api-response-headers`,{headers:e},{cancelToken:t});return ed=n,$u=!1,ed}async function ad(e){return await id(e.headers,e.config.cancelToken),e}nd.interceptors.request.use(async e=>{let{cancelToken:t}=e,n=await td(t);n&&Object.entries(n).forEach(([t,n])=>{e.headers.set(t,n)});let r={...e,params:{...Cp.apiParams||{},...e.params,v:new Date().getTime()}};return n||(r.params.processCraftHeaders=1),Cp.httpProxy&&(r.proxy=Cp.httpProxy),r}),nd.interceptors.request.use(rd),nd.interceptors.response.use(ad);var od={Á:`A`,á:`a`,Ä:`A`,ä:`a`,À:`A`,à:`a`,Â:`A`,â:`a`,É:`E`,é:`e`,Ë:`E`,ë:`e`,È:`E`,è:`e`,Ê:`E`,ê:`e`,Í:`I`,í:`i`,Ï:`I`,ï:`i`,Ì:`I`,ì:`i`,Î:`I`,î:`i`,Ó:`O`,ó:`o`,Ö:`O`,ö:`o`,Ò:`O`,ò:`o`,Ô:`O`,ô:`o`,Ú:`U`,ú:`u`,Ü:`U`,ü:`u`,Ù:`U`,ù:`u`,Û:`U`,û:`u`,Ý:`Y`,ý:`y`,Ÿ:`Y`,А:`A`,Б:`B`,В:`V`,Г:`G`,Д:`D`,Ѓ:`Gj`,Е:`E`,Ж:`Z`,З:`Z`,Ѕ:`Dz`,И:`I`,Ј:`j`,К:`K`,Л:`L`,Љ:`Lj`,М:`M`,Н:`N`,Њ:`Nj`,О:`O`,П:`P`,Р:`R`,С:`S`,Т:`T`,Ќ:`Kj`,У:`U`,Ф:`F`,Х:`X`,Ц:`C`,Ч:`C`,Џ:`Dz`,Ш:`S`,а:`a`,б:`b`,в:`v`,г:`g`,д:`d`,ѓ:`gj`,е:`e`,ж:`z`,з:`z`,ѕ:`dz`,и:`i`,ј:`j`,к:`k`,л:`l`,љ:`lj`,м:`m`,н:`n`,њ:`nj`,о:`o`,п:`p`,р:`r`,с:`s`,т:`t`,ќ:`kj`,у:`u`,ф:`f`,х:`x`,ц:`c`,ч:`c`,џ:`dz`,ш:`s`,æ:`ae`,ǽ:`ae`,Ã:`A`,Å:`A`,Ǻ:`A`,Ă:`A`,Ǎ:`A`,Æ:`AE`,Ǽ:`AE`,ã:`a`,å:`a`,ǻ:`a`,ă:`a`,ǎ:`a`,ª:`a`,Ĉ:`C`,Ċ:`C`,Ç:`C`,ç:`c`,ĉ:`c`,ċ:`c`,Ð:`D`,Đ:`D`,ð:`d`,đ:`d`,Ĕ:`E`,Ė:`E`,ĕ:`e`,ė:`e`,ƒ:`f`,Ĝ:`G`,Ġ:`G`,ĝ:`g`,ġ:`g`,Ĥ:`H`,Ħ:`H`,ĥ:`h`,ħ:`h`,Ĩ:`I`,Ĭ:`I`,Ǐ:`I`,Į:`I`,Ĳ:`IJ`,ĩ:`i`,ĭ:`i`,ǐ:`i`,į:`i`,ĳ:`ij`,Ĵ:`J`,ĵ:`j`,Ĺ:`L`,Ľ:`L`,Ŀ:`L`,ĺ:`l`,ľ:`l`,ŀ:`l`,Ñ:`N`,ñ:`n`,ŉ:`n`,Õ:`O`,Ō:`O`,Ŏ:`O`,Ǒ:`O`,Ő:`O`,Ơ:`O`,Ø:`O`,Ǿ:`O`,Œ:`OE`,õ:`o`,ō:`o`,ŏ:`o`,ǒ:`o`,ő:`o`,ơ:`o`,ø:`o`,ǿ:`o`,º:`o`,œ:`oe`,Ŕ:`R`,Ŗ:`R`,ŕ:`r`,ŗ:`r`,Ŝ:`S`,Ș:`S`,ŝ:`s`,ș:`s`,ſ:`s`,Ţ:`T`,Ț:`T`,Ŧ:`T`,Þ:`TH`,ţ:`t`,ț:`t`,ŧ:`t`,þ:`th`,Ũ:`U`,Ŭ:`U`,Ű:`U`,Ų:`U`,Ư:`U`,Ǔ:`U`,Ǖ:`U`,Ǘ:`U`,Ǚ:`U`,Ǜ:`U`,ũ:`u`,ŭ:`u`,ű:`u`,ų:`u`,ư:`u`,ǔ:`u`,ǖ:`u`,ǘ:`u`,ǚ:`u`,ǜ:`u`,Ŵ:`W`,ŵ:`w`,Ŷ:`Y`,ÿ:`y`,ŷ:`y`,ΑΥ:`AU`,ΑΎ:`AU`,Αυ:`Au`,Αύ:`Au`,ΕΊ:`I`,ΕΙ:`I`,Ει:`Ei`,ΕΥ:`EF`,ΕΎ:`EU`,Εί:`I`,Ευ:`Ef`,Εύ:`Eu`,ΟΙ:`I`,ΟΊ:`I`,ΟΥ:`U`,ΟΎ:`OU`,Οι:`Oi`,Οί:`I`,Ου:`Oy`,Ού:`Ou`,ΥΙ:`I`,ΎΙ:`I`,Υι:`Yi`,Ύι:`I`,ΥΊ:`I`,Υί:`I`,αυ:`au`,αύ:`au`,εί:`i`,ει:`ei`,ευ:`ef`,εύ:`eu`,οι:`oi`,οί:`i`,ου:`oy`,ού:`ou`,υι:`yi`,ύι:`i`,υί:`i`,Α:`A`,Ά:`A`,Β:`B`,Δ:`D`,Ε:`E`,Έ:`E`,Φ:`F`,Γ:`G`,Η:`H`,Ή:`I`,Ι:`I`,Ί:`I`,Ϊ:`I`,Κ:`K`,Ξ:`Ks`,Λ:`L`,Μ:`M`,Ν:`N`,Π:`P`,Ο:`O`,Ό:`O`,Ψ:`Ps`,Ρ:`R`,Σ:`S`,Τ:`T`,Θ:`Th`,Ω:`O`,Ώ:`W`,Χ:`X`,ϒ:`Y`,Υ:`Y`,Ύ:`Y`,Ϋ:`Y`,Ζ:`Z`,α:`a`,ά:`a`,β:`v`,δ:`d`,ε:`e`,έ:`e`,φ:`f`,γ:`gh`,η:`i`,ή:`i`,ι:`i`,ί:`i`,ϊ:`i`,ΐ:`i`,κ:`k`,ξ:`ks`,λ:`l`,μ:`m`,ν:`n`,ο:`o`,ό:`o`,π:`p`,ψ:`ps`,ρ:`r`,σ:`s`,ς:`s`,τ:`t`,ϑ:`th`,θ:`th`,ϐ:`v`,ω:`o`,ώ:`w`,χ:`kh`,υ:`i`,ύ:`y`,ΰ:`y`,ϋ:`y`,ζ:`z`,अ:`a`,आ:`aa`,ए:`e`,ई:`ii`,ऍ:`ei`,ऎ:`ae`,ऐ:`ai`,इ:`i`,ओ:`o`,ऑ:`oi`,ऒ:`oii`,ऊ:`uu`,औ:`ou`,उ:`u`,ब:`B`,भ:`Bha`,च:`Ca`,छ:`Chha`,ड:`Da`,ढ:`Dha`,फ:`Fa`,फ़:`Fi`,ग:`Ga`,घ:`Gha`,ग़:`Ghi`,ह:`Ha`,ज:`Ja`,झ:`Jha`,क:`Ka`,ख:`Kha`,ख़:`Khi`,ल:`L`,ळ:`Li`,ऌ:`Li`,ऴ:`Lii`,ॡ:`Lii`,म:`Ma`,न:`Na`,ङ:`Na`,ञ:`Nia`,ण:`Nae`,ऩ:`Ni`,ॐ:`oms`,प:`Pa`,क़:`Qi`,र:`Ra`,ऋ:`Ri`,ॠ:`Ri`,ऱ:`Ri`,स:`Sa`,श:`Sha`,ष:`Shha`,ट:`Ta`,त:`Ta`,ठ:`Tha`,द:`Tha`,थ:`Tha`,ध:`Thha`,ड़:`ugDha`,ढ़:`ugDhha`,व:`Va`,य:`Ya`,य़:`Yi`,ज़:`Za`,Ա:`A`,Բ:`B`,Գ:`G`,Դ:`D`,Ե:`E`,Զ:`Z`,Է:`E`,Ը:`Y`,Թ:`Th`,Ժ:`Zh`,Ի:`I`,Լ:`L`,Խ:`Kh`,Ծ:`Ts`,Կ:`K`,Հ:`H`,Ձ:`Dz`,Ղ:`Gh`,Ճ:`Tch`,Մ:`M`,Յ:`Y`,Ն:`N`,Շ:`Sh`,Ո:`Vo`,Չ:`Ch`,Պ:`P`,Ջ:`J`,Ռ:`R`,Ս:`S`,Վ:`V`,Տ:`T`,Ր:`R`,Ց:`C`,Ւ:`u`,Փ:`Ph`,Ք:`Q`,և:`ev`,Օ:`O`,Ֆ:`F`,ա:`a`,բ:`b`,գ:`g`,դ:`d`,ե:`e`,զ:`z`,է:`e`,ը:`y`,թ:`th`,ժ:`zh`,ի:`i`,լ:`l`,խ:`kh`,ծ:`ts`,կ:`k`,հ:`h`,ձ:`dz`,ղ:`gh`,ճ:`tch`,մ:`m`,յ:`y`,ն:`n`,շ:`sh`,ո:`vo`,չ:`ch`,պ:`p`,ջ:`j`,ռ:`r`,ս:`s`,վ:`v`,տ:`t`,ր:`r`,ց:`c`,ւ:`u`,փ:`ph`,ք:`q`,օ:`o`,ֆ:`f`,Ž:`Z`,Ň:`N`,Ş:`S`,ž:`z`,ň:`n`,ş:`s`,ı:`i`,İ:`I`,ğ:`g`,Ğ:`G`,ьо:`yo`,Й:`i`,Щ:`Shh`,Ъ:`Ie`,Ь:``,Ю:`Iu`,Я:`Ia`,й:`i`,щ:`shh`,ъ:`ie`,ь:``,ю:`iu`,я:`ia`,Ē:`E`,ē:`e`,န်ုပ်:`nub`,"ောင်":`aung`,"ိုက်":`aik`,"ိုဒ်":`ok`,"ိုင်":`aing`,"ိုလ်":`ol`,"ေါင်":`aung`,သြော:`aw`,"ောက်":`auk`,"ိတ်":`eik`,"ုတ်":`ok`,"ုန်":`on`,"ေတ်":`it`,"ုဒ်":`ait`,"ာန်":`an`,"ိန်":`ein`,"ွတ်":`ut`,"ေါ်":`aw`,"ွန်":`un`,"ိပ်":`eik`,"ုပ်":`ok`,"ွပ်":`ut`,"ိမ်":`ein`,"ုမ်":`on`,"ော်":`aw`,"ွမ်":`un`,က်:`et`,"ေါ":`aw`,"ော":`aw`,"ျွ":`ywa`,"ြွ":`yw`,"ို":`o`,"ုံ":`on`,တ်:`at`,င်:`in`,ည်:`i`,ဒ်:`d`,န်:`an`,ပ်:`at`,မ်:`an`,စျ:`za`,ယ်:`e`,ဉ်:`in`,စ်:`it`,"ိံ":`ein`,"ဲ":`e`,"း":``,"ာ":`a`,"ါ":`a`,"ေ":`e`,"ံ":`an`,"ိ":`i`,"ီ":`i`,"ု":`u`,"ူ":`u`,"်":`at`,"္":``,"့":``,က:`k`,"၉":`9`,တ:`t`,ရ:`ya`,ယ:`y`,မ:`m`,ဘ:`ba`,ဗ:`b`,ဖ:`pa`,ပ:`p`,န:`n`,ဓ:`da`,ဒ:`d`,ထ:`ta`,ဏ:`na`,ဝ:`w`,ဎ:`da`,ဍ:`d`,ဌ:`ta`,ဋ:`t`,ည:`ny`,ဇ:`z`,ဆ:`sa`,စ:`s`,င:`ng`,ဃ:`ga`,ဂ:`g`,လ:`l`,သ:`th`,"၈":`8`,ဩ:`aw`,ခ:`kh`,"၆":`6`,"၅":`5`,"၄":`4`,"၃":`3`,"၂":`2`,"၁":`1`,"၀":`0`,"၌":`hnaik`,"၍":`ywae`,ဪ:`aw`,ဦ:`-u`,ဟ:`h`,ဉ:`u`,ဤ:`-i`,ဣ:`i`,"၏":`-e`,ဧ:`e`,"ှ":`h`,"ွ":`w`,"ျ":`ya`,"ြ":`y`,အ:`a`,ဠ:`la`,"၇":`7`,DŽ:`DZ`,Dž:`Dz`,dž:`dz`,Ǳ:`DZ`,ǲ:`Dz`,ǳ:`dz`,Ǉ:`LJ`,ǈ:`Lj`,ǉ:`lj`,Ǌ:`NJ`,ǋ:`Nj`,ǌ:`nj`,č:`c`,Č:`C`,ć:`c`,Ć:`C`,š:`s`,Š:`S`,ა:`a`,ბ:`b`,გ:`g`,დ:`d`,ე:`e`,ვ:`v`,ზ:`z`,თ:`t`,ი:`i`,კ:`k`,ლ:`l`,მ:`m`,ნ:`n`,ო:`o`,პ:`p`,ჟ:`zh`,რ:`r`,ს:`s`,ტ:`t`,უ:`u`,ფ:`f`,ქ:`q`,ღ:`gh`,ყ:`y`,შ:`sh`,ჩ:`ch`,ც:`ts`,ძ:`dz`,წ:`ts`,ჭ:`ch`,ხ:`kh`,ჯ:`j`,ჰ:`h`,Ё:`E`,ё:`e`,Ы:`Y`,ы:`y`,Э:`E`,э:`e`,І:`I`,і:`i`,Ѳ:`F`,ѳ:`f`,Ѣ:`E`,ѣ:`e`,Ѵ:`I`,ѵ:`i`,Є:`Je`,є:`je`,Ѥ:`Je`,ѥ:`je`,Ꙋ:`U`,ꙋ:`u`,Ѡ:`O`,ѡ:`o`,Ѿ:`Ot`,ѿ:`ot`,Ѫ:`U`,ѫ:`u`,Ѧ:`Ja`,ѧ:`ja`,Ѭ:`Ju`,ѭ:`ju`,Ѩ:`Ja`,ѩ:`Ja`,Ѯ:`Ks`,ѯ:`ks`,Ѱ:`Ps`,ѱ:`ps`,Ґ:`G`,ґ:`g`,Ї:`Yi`,ї:`yi`,Ә:`A`,Ғ:`G`,Қ:`Q`,Ң:`N`,Ө:`O`,Ұ:`U`,Ү:`U`,Һ:`H`,ә:`a`,ғ:`g`,қ:`q`,ң:`n`,ө:`o`,ұ:`u`,ү:`u`,һ:`h`,ď:`d`,Ď:`D`,ě:`e`,Ě:`E`,ř:`r`,Ř:`R`,ť:`t`,Ť:`T`,ů:`u`,Ů:`U`,ą:`a`,ę:`e`,ł:`l`,ń:`n`,ś:`s`,ź:`z`,ż:`z`,Ą:`A`,Ę:`E`,Ł:`L`,Ń:`N`,Ś:`S`,Ź:`Z`,Ż:`Z`,ā:`a`,ģ:`g`,ī:`i`,ķ:`k`,ļ:`l`,ņ:`n`,ū:`u`,Ā:`A`,Ģ:`G`,Ī:`I`,Ķ:`k`,Ļ:`L`,Ņ:`N`,Ū:`U`,Ả:`A`,Ạ:`A`,Ắ:`A`,Ằ:`A`,Ẳ:`A`,Ẵ:`A`,Ặ:`A`,Ấ:`A`,Ầ:`A`,Ẩ:`A`,Ẫ:`A`,Ậ:`A`,ả:`a`,ạ:`a`,ắ:`a`,ằ:`a`,ẳ:`a`,ẵ:`a`,ặ:`a`,ấ:`a`,ầ:`a`,ẩ:`a`,ẫ:`a`,ậ:`a`,Ẻ:`E`,Ẽ:`E`,Ẹ:`E`,Ế:`E`,Ề:`E`,Ể:`E`,Ễ:`E`,Ệ:`E`,ẻ:`e`,ẽ:`e`,ẹ:`e`,ế:`e`,ề:`e`,ể:`e`,ễ:`e`,ệ:`e`,Ỉ:`I`,Ị:`I`,ỉ:`i`,ị:`i`,Ỏ:`O`,Ọ:`O`,Ố:`O`,Ồ:`O`,Ổ:`O`,Ỗ:`O`,Ộ:`O`,Ớ:`O`,Ờ:`O`,Ở:`O`,Ỡ:`O`,Ợ:`O`,ỏ:`o`,ọ:`o`,ố:`o`,ồ:`o`,ổ:`o`,ỗ:`o`,ộ:`o`,ớ:`o`,ờ:`o`,ở:`o`,ỡ:`o`,ợ:`o`,Ủ:`U`,Ụ:`U`,Ứ:`U`,Ừ:`U`,Ử:`U`,Ữ:`U`,Ự:`U`,ủ:`u`,ụ:`u`,ứ:`u`,ừ:`u`,ử:`u`,ữ:`u`,ự:`u`,Ỳ:`Y`,Ỷ:`Y`,Ỹ:`Y`,Ỵ:`Y`,ỳ:`y`,ỷ:`y`,ỹ:`y`,ỵ:`y`,ا:`a`,ب:`b`,پ:`p`,ت:`t`,ث:`th`,ج:`g`,چ:`ch`,ح:`h`,خ:`kh`,د:`d`,ذ:`th`,ر:`r`,ز:`z`,س:`s`,ش:`sh`,ص:`s`,ض:`d`,ط:`t`,ظ:`th`,ع:`aa`,غ:`gh`,ف:`f`,ق:`k`,ک:`k`,گ:`g`,ل:`l`,ژ:`zh`,ك:`k`,م:`m`,ن:`n`,ه:`h`,و:`o`,ی:`y`,آ:`a`,"٠":`0`,"١":`1`,"٢":`2`,"٣":`3`,"٤":`4`,"٥":`5`,"٦":`6`,"٧":`7`,"٨":`8`,"٩":`9`,أ:`a`,ي:`y`,إ:`a`,ؤ:`o`,ئ:`y`,ء:`aa`,ђ:`dj`,ћ:`c`,Ђ:`Dj`,Ћ:`C`,ə:`e`,Ə:`E`,ß:`ss`,ẞ:`SS`,ভ্ল:`vl`,পশ:`psh`,ব্ধ:`bdh`,ব্জ:`bj`,ব্দ:`bd`,ব্ব:`bb`,ব্ল:`bl`,ভ:`v`,ব:`b`,চ্ঞ:`cNG`,চ্ছ:`cch`,চ্চ:`cc`,ছ:`ch`,চ:`c`,ধ্ন:`dhn`,ধ্ম:`dhm`,দ্ঘ:`dgh`,দ্ধ:`ddh`,দ্ভ:`dv`,দ্ম:`dm`,ড্ড:`DD`,ঢ:`Dh`,ধ:`dh`,দ্গ:`dg`,দ্দ:`dd`,ড:`D`,দ:`d`,"।":`.`,ঘ্ন:`Ghn`,গ্ধ:`Gdh`,গ্ণ:`GN`,গ্ন:`Gn`,গ্ম:`Gm`,গ্ল:`Gl`,জ্ঞ:`jNG`,ঘ:`Gh`,গ:`g`,হ্ণ:`hN`,হ্ন:`hn`,হ্ম:`hm`,হ্ল:`hl`,হ:`h`,জ্ঝ:`jjh`,ঝ:`jh`,জ্জ:`jj`,জ:`j`,ক্ষ্ণ:`kxN`,ক্ষ্ম:`kxm`,ক্ষ:`ksh`,কশ:`ksh`,ক্ক:`kk`,ক্ট:`kT`,ক্ত:`kt`,ক্ল:`kl`,ক্স:`ks`,খ:`kh`,ক:`k`,ল্ভ:`lv`,ল্ধ:`ldh`,লখ:`lkh`,লঘ:`lgh`,লফ:`lph`,ল্ক:`lk`,ল্গ:`lg`,ল্ট:`lT`,ল্ড:`lD`,ল্প:`lp`,ল্ম:`lm`,ল্ল:`ll`,ল্ব:`lb`,ল:`l`,ম্থ:`mth`,ম্ফ:`mf`,ম্ভ:`mv`,মপ্ল:`mpl`,ম্ন:`mn`,ম্প:`mp`,ম্ম:`mm`,ম্ল:`ml`,ম্ব:`mb`,ম:`m`,"০":`0`,"১":`1`,"২":`2`,"৩":`3`,"৪":`4`,"৫":`5`,"৬":`6`,"৭":`7`,"৮":`8`,"৯":`9`,ঙ্ক্ষ:`Ngkx`,ঞ্ছ:`nch`,ঙ্ঘ:`ngh`,ঙ্খ:`nkh`,ঞ্ঝ:`njh`,ঙ্গৌ:`ngOU`,ঙ্গৈ:`ngOI`,ঞ্চ:`nc`,ঙ্ক:`nk`,ঙ্ষ:`Ngx`,ঙ্গ:`ngo`,ঙ্ম:`Ngm`,ঞ্জ:`nj`,ন্ধ:`ndh`,ন্ঠ:`nTh`,ণ্ঠ:`NTh`,ন্থ:`nth`,ঙ্গা:`nga`,ঙ্গি:`ngi`,ঙ্গী:`ngI`,ঙ্গু:`ngu`,ঙ্গূ:`ngU`,ঙ্গে:`nge`,ঙ্গো:`ngO`,ণ্ঢ:`NDh`,নশ:`nsh`,ঙর:`Ngr`,ঞর:`NGr`,"ংর":`ngr`,ঙ:`Ng`,ঞ:`NG`,"ং":`ng`,ন্ন:`nn`,ণ্ণ:`NN`,ণ্ন:`Nn`,ন্ম:`nm`,ণ্ম:`Nm`,ন্দ:`nd`,ন্ট:`nT`,ণ্ট:`NT`,ন্ড:`nD`,ণ্ড:`ND`,ন্ত:`nt`,ন্স:`ns`,ন:`n`,ণ:`N`,"ৈ":`OI`,"ৌ":`OU`,"ো":`O`,ঐ:`OI`,ঔ:`OU`,অ:`o`,ও:`oo`,ফ্ল:`fl`,প্ট:`pT`,প্ত:`pt`,প্ন:`pn`,প্প:`pp`,প্ল:`pl`,প্স:`ps`,ফ:`f`,প:`p`,"ৃ":`rri`,ঋ:`rri`,রর‍্য:`rry`,"্র্য":`ry`,"্রর":`rr`,ড়্গ:`Rg`,ঢ়:`Rh`,ড়:`R`,র:`r`,"্র":`r`,শ্ছ:`Sch`,ষ্ঠ:`ShTh`,ষ্ফ:`Shf`,স্ক্ল:`skl`,স্খ:`skh`,স্থ:`sth`,স্ফ:`sf`,শ্চ:`Sc`,শ্ত:`St`,শ্ন:`Sn`,শ্ম:`Sm`,শ্ল:`Sl`,ষ্ক:`Shk`,ষ্ট:`ShT`,ষ্ণ:`ShN`,ষ্প:`Shp`,ষ্ম:`Shm`,স্প্ল:`spl`,স্ক:`sk`,স্ট:`sT`,স্ত:`st`,স্ন:`sn`,স্প:`sp`,স্ম:`sm`,স্ল:`sl`,শ:`S`,ষ:`Sh`,স:`s`,"ু":`u`,উ:`u`,অ্য:`oZ`,ত্থ:`tth`,ৎ:`tt`,ট্ট:`TT`,ট্ম:`Tm`,ঠ:`Th`,ত্ন:`tn`,ত্ম:`tm`,থ:`th`,ত্ত:`tt`,ট:`T`,ত:`t`,অ্যা:`AZ`,"া":`a`,আ:`a`,য়া:`ya`,য়:`y`,"ি":`i`,ই:`i`,"ী":`ee`,ঈ:`ee`,"ূ":`uu`,ঊ:`uu`,"ে":`e`,এ:`e`,য:`z`,"্য":`Z`,ইয়:`y`,ওয়:`w`,"্ব":`w`,এক্স:`x`,"ঃ":`:`,"ঁ":`nn`,"্‌":``,"˚":`0`,"¹":`1`,"²":`2`,"³":`3`,"⁴":`4`,"⁵":`5`,"⁶":`6`,"⁷":`7`,"⁸":`8`,"⁹":`9`,"₀":`0`,"₁":`1`,"₂":`2`,"₃":`3`,"₄":`4`,"₅":`5`,"₆":`6`,"₇":`7`,"₈":`8`,"₉":`9`,"௦":`0`,"௧":`1`,"௨":`2`,"௩":`3`,"௪":`4`,"௫":`5`,"௬":`6`,"௭":`7`,"௮":`8`,"௯":`9`,"௰":`10`,"௱":`100`,"௲":`1000`,Ꜳ:`AA`,ꜳ:`aa`,Ꜵ:`AO`,ꜵ:`ao`,Ꜷ:`AU`,ꜷ:`au`,Ꜹ:`AV`,ꜹ:`av`,Ꜻ:`av`,ꜻ:`av`,Ꜽ:`AY`,ꜽ:`ay`,ȸ:`db`,ʣ:`dz`,ʥ:`dz`,ʤ:`dezh`,"🙰":`et`,ﬀ:`ff`,ﬃ:`ffi`,ﬄ:`ffl`,ﬁ:`fi`,ﬂ:`fl`,ʩ:`feng`,ʪ:`ls`,ʫ:`lz`,ɮ:`lezh`,ȹ:`qp`,ʨ:`tc`,ʦ:`ts`,ʧ:`tesh`,Ꝏ:`OO`,ꝏ:`oo`,ﬆ:`st`,ﬅ:`st`,Ꜩ:`TZ`,ꜩ:`tz`,ᵫ:`ue`,Aι:`Ai`,αι:`ai`,ἀ:`a`,ἁ:`a`,ἂ:`a`,ἃ:`a`,ἄ:`a`,ἅ:`a`,ἆ:`a`,ἇ:`a`,Ἀ:`A`,Ἁ:`A`,Ἂ:`A`,Ἃ:`A`,Ἄ:`A`,Ἅ:`A`,Ἆ:`A`,Ἇ:`A`,ᾰ:`a`,ᾱ:`a`,ᾲ:`a`,ᾳ:`a`,ᾴ:`a`,ᾶ:`a`,ᾷ:`a`,Ᾰ:`A`,Ᾱ:`A`,Ὰ:`A`,Ά:`A`,ᾼ:`A`,A̧:`A`,a̧:`a`,Ⱥ:`A`,ⱥ:`a`,Ȧ:`A`,ȧ:`a`,Ɓ:`B`,C̈:`C`,c̈:`c`,C̨:`C`,c̨:`c`,Ȼ:`C`,ȼ:`c`,C̀:`C`,c̀:`c`,C̣:`C`,c̣:`c`,C̄:`C`,c̄:`c`,C̃:`C`,c̃:`c`,Ȩ:`E`,ȩ:`e`,Ɇ:`E`,ɇ:`e`,I̧:`I`,i̧:`i`,Ɨ:`I`,ɨ:`i`,i:`i`,J́́:`J`,j́:`j`,J̀̀:`J`,j̀:`j`,J̈:`J`,j̈:`j`,J̧:`J`,j̧:`j`,J̨:`J`,j̨:`j`,Ɉ:`J`,ɉ:`j`,J̌:`J`,ǰ:`j`,J̇:`J`,j:`j`,J̣:`J`,j̣:`j`,J̄:`J`,j̄:`j`,J̃:`J`,j̃:`j`,ĸ:`k`,L̀:`L`,l̀:`l`,L̂:`L`,l̂:`l`,L̈:`L`,l̈:`l`,L̨:`L`,l̨:`l`,Ƚ:`L`,ƚ:`l`,L̇:`L`,l̇:`l`,Ḷ:`L`,ḷ:`l`,L̄:`L`,l̄:`l`,L̃:`L`,l̃:`l`,Ŋ:`N`,ŋ:`n`,Ǹ:`N`,ǹ:`n`,N̂:`N`,n̂:`n`,N̈:`N`,n̈:`n`,N̨:`N`,n̨:`n`,Ꞥ:`N`,ꞥ:`n`,Ṅ:`N`,ṅ:`n`,Ṇ:`N`,ṇ:`n`,N̄:`N`,n̄:`n`,O̧:`O`,o̧:`o`,Ǫ:`O`,ǫ:`o`,Ɵ:`O`,ɵ:`o`,Ȯ:`O`,ȯ:`o`,S̀:`S`,s̀:`s`,Ŝ̀:`S`,S̈:`S`,s̈:`s`,S̨:`S`,s̨:`s`,Ꞩ:`S`,ꞩ:`s`,Ṡ:`S`,ṡ:`s`,Ṣ:`S`,ṣ:`s`,S̄:`S`,s̄:`s`,S̃:`S`,s̃:`s`,T́:`T`,t́:`t`,T̀:`T`,t̀:`t`,T̂:`T`,t̂:`t`,T̈:`T`,ẗ:`t`,T̨:`T`,t̨:`t`,Ⱦ:`T`,ⱦ:`t`,Ṫ:`T`,ṫ:`t`,Ṭ:`T`,ṭ:`t`,T̄:`T`,t̄:`t`,T̃:`T`,t̃:`t`,U̧:`U`,u̧:`u`,Ʉ:`U`,ʉ:`u`,U̇:`U`,u̇:`u`,Ʊ:`U`,ʊ:`u`,Ẁ:`W`,ẁ:`w`,Ẃ:`W`,ẃ:`w`,Ẅ:`W`,ẅ:`w`,Ꙗ:`Ja`,ꙗ:`ja`,Y̧:`Y`,y̧:`y`,Y̨:`Y`,y̨:`y`,Ɏ:`Y`,ɏ:`y`,Y̌:`Y`,y̌:`y`,Ẏ:`Y`,ẏ:`y`,Ȳ:`Y`,ȳ:`y`,Z̀:`Z`,z̀:`z`,Ẑ:`Z`,ẑ:`z`,Z̈:`Z`,z̈:`z`,Z̧:`Z`,z̧:`z`,Z̨:`Z`,z̨:`z`,Ƶ:`Z`,ƶ:`z`,Ẓ:`Z`,ẓ:`z`,Z̄:`Z`,z̄:`z`,Z̃:`Z`,z̃:`z`,"\xA0":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `,"\u2028":` `,"\u2029":` `,"​":` `," ":` `," ":` `,"　":` `,ﾠ:` `,"«":`<<`,"»":`>>`,"‘":`'`,"’":`'`,"‚":`'`,"‛":`'`,"“":`"`,"”":`"`,"„":`"`,"‟":`"`,"‹":`'`,"›":`'`,"–":`-`,"—":`-`,"…":`...`,"€":`EUR`,$:`$`,"₢":`Cr`,"₣":`Fr.`,"£":`PS`,"₤":`L.`,ℳ:`M`,"₥":`mil`,"₦":`N`,"₧":`Pts`,"₨":`Rs`,රු:`LKR`,ரூ:`LKR`,"௹":`Rs`,रू:`NPR`,"₹":`Rs`,"૱":`Rs`,"₩":`W`,"₪":`NS`,"₸":`KZT`,"₫":`D`,"֏":`AMD`,"₭":`K`,"₺":`TL`,"₼":`AZN`,"₮":`T`,"₯":`Dr`,"₲":`PYG`,"₾":`GEL`,"₳":`ARA`,"₴":`UAH`,"₽":`RUB`,"₵":`GHS`,"₡":`CL`,"¢":`c`,"¥":`YEN`,円:`JPY`,"৳":`BDT`,元:`CNY`,"﷼":`SAR`,"៛":`KR`,"₠":`ECU`,"¤":`$?`,"฿":`THB`,"؋":`AFN`};function sd(e,t=od){e=e.normalize(`NFC`);let n=``,r;for(let i=0;i<e.length;i++)r=e.charAt(i),n+=typeof t[r]==`string`?t[r]:r;return n}function cd(e,t={}){let n={allowNonAlphaStart:!1,handleCasing:`camel`,...t};var r=e.replace(/<(.*?)>/g,``);r=r.replace(/['"‘’“”ʻ\[\]\(\)\{\}:]/g,``),r=r.toLowerCase(),r=sd(r),n.allowNonAlphaStart||(r=r.replace(/^[^a-z]+/,``));let i=r.split(/[^a-z0-9]+/).filter(Boolean);if(r=``,n.handleCasing===`snake`)return i.join(`_`);for(let e=0;e<i.length;e++)n.handleCasing!==`pascal`&&e===0?r+=i[e]:r+=i[e].charAt(0).toUpperCase()+i[e].substring(1);return r}function ld(e,t={}){let n={prefix:``,suffix:``,...t},r=cd(e,{handleCasing:`snake`}).toUpperCase();return r?`${n.prefix}${r}${n.suffix}`:``}function ud(e){let t=e.replace(/<(.*?)>/g,``);return t=t.toLowerCase(),t=sd(t),t=t.replace(/^[^a-z]+/,``),t=t.replace(/[^a-z0-9]+$/,``),t.split(/[^a-z0-9]+/).filter(Boolean).join(`-`)}var dd={START:`asset-indexes/start-indexing`,STOP:`asset-indexes/stop-indexing-session`,PROCESS:`asset-indexes/process-indexing-session`,OVERVIEW:`asset-indexes/indexing-session-overview`,FINISH:`asset-indexes/finish-indexing-session`},fd=new WeakMap,pd=new WeakMap,md=new WeakMap,hd=new WeakMap,gd=new WeakMap,_d=new WeakMap,vd=new WeakMap,V=new WeakSet,yd=class{constructor(e={}){f(this,V),u(this,fd,new Map),u(this,pd,null),u(this,md,0),u(this,hd,[]),u(this,gd,[]),u(this,_d,new Set),u(this,vd,new Map);let{existingSessions:t=[],maxConcurrentConnections:r=3,autoResume:i=!0}=e;this.maxConcurrentConnections=r;for(let e of t)p(fd,this).set(e.id,e);i&&(n(V,this,Cd).call(this),p(pd,this)!==null&&n(V,this,wd).call(this))}getSessions(){return Array.from(p(fd,this).values())}getCurrentSessionId(){return p(pd,this)}isProcessing(){return p(md,this)>0}on(e,t){return p(vd,this).has(e)||p(vd,this).set(e,new Set),p(vd,this).get(e).add(t),()=>{p(vd,this).get(e)?.delete(t)}}async startIndexing(e){let t=await Zu.post(dd.START,e),{data:i}=t;return i.session&&(p(fd,this).set(i.session.id,i.session),r(pd,this,i.session.id),n(V,this,xd).call(this),i.stop||n(V,this,wd).call(this)),i.stop&&n(V,this,Sd).call(this,i.stop),t}stopSession(e){n(V,this,Td).call(this,e),n(V,this,Ed).call(this,{sessionId:e,action:dd.STOP,params:{sessionId:e},priority:!0})}getSessionOverview(e){n(V,this,Ed).call(this,{sessionId:e,action:dd.OVERVIEW,params:{sessionId:e},priority:!0})}finishSession(e){n(V,this,Ed).call(this,{sessionId:e.sessionId,action:dd.FINISH,params:e,priority:!0})}destroy(){p(fd,this).clear(),r(hd,this,[]),r(gd,this,[]),p(vd,this).clear(),r(pd,this,null),r(md,this,0)}};function bd(e,t){p(vd,this).get(e)?.forEach(e=>e(t))}function xd(e){n(V,this,bd).call(this,`change`,{sessions:this.getSessions(),currentSessionId:p(pd,this),reviewSessionId:e})}function Sd(e){p(fd,this).delete(e),p(pd,this)===e&&r(pd,this,null),n(V,this,xd).call(this)}function Cd(){for(let[e,t]of p(fd,this))if(!t.actionRequired&&!p(_d,this).has(e)){r(pd,this,e);return}r(pd,this,null)}function wd(){if(p(pd,this)||n(V,this,Cd).call(this),!p(pd,this))return;let e=p(fd,this).get(p(pd,this));if(!e)return;let t=e.totalEntries-e.processedEntries,r=this.maxConcurrentConnections-p(md,this),i=Math.min(r,t);for(let t=0;t<i;t++)n(V,this,Ed).call(this,{sessionId:e.id,action:dd.PROCESS,params:{sessionId:p(pd,this)},priority:!1});e.processIfRootEmpty&&n(V,this,Ed).call(this,{sessionId:e.id,action:dd.PROCESS,params:{sessionId:p(pd,this)},priority:!1})}function Td(e){p(_d,this).add(e),r(hd,this,p(hd,this).filter(t=>t.sessionId!==e))}function Ed(e){e.priority?p(gd,this).push(e):p(hd,this).push(e),n(V,this,Dd).call(this)}function Dd(){if(!(p(hd,this).length+p(gd,this).length===0||p(md,this)>=this.maxConcurrentConnections))for(;p(hd,this).length+p(gd,this).length>0&&p(md,this)<this.maxConcurrentConnections;){var e;r(md,this,(e=p(md,this),e++,e));let t=p(gd,this).length>0?p(gd,this).shift():p(hd,this).shift();n(V,this,Od).call(this,t)}}async function Od(e){try{let t=await Zu.post(e.action,e.params);n(V,this,kd).call(this,t.data)}catch(t){n(V,this,Ad).call(this,t,e)}finally{var t;r(md,this,(t=p(md,this),t--,t)),n(V,this,Dd).call(this)}}function kd(e){let t;e.session&&(p(fd,this).set(e.session.id,e.session),n(V,this,Cd).call(this),e.session.actionRequired&&!e.skipDialog?p(_d,this).has(e.session.id)||(t=e.session.id):p(_d,this).has(e.session.id)||n(V,this,wd).call(this)),n(V,this,Cd).call(this),e.stop&&(p(fd,this).delete(e.stop),p(pd,this)===e.stop&&r(pd,this,null)),n(V,this,xd).call(this,t),p(fd,this).size===0&&n(V,this,bd).call(this,`complete`,{})}function Ad(e,t){n(V,this,Cd).call(this);let r=e?.response?.data?.message||e.message||`An error occurred during indexing.`;n(V,this,bd).call(this,`error`,{message:r,sessionId:t.sessionId}),n(V,this,Dd).call(this)}var jd=typeof global==`object`&&global&&global.Object===Object&&global,Md=typeof self==`object`&&self&&self.Object===Object&&self,Nd=jd||Md||Function(`return this`)(),Pd=Nd.Symbol,Fd=Object.prototype,Id=Fd.hasOwnProperty,Ld=Fd.toString,Rd=Pd?Pd.toStringTag:void 0;function zd(e){var t=Id.call(e,Rd),n=e[Rd];try{e[Rd]=void 0;var r=!0}catch{}var i=Ld.call(e);return r&&(t?e[Rd]=n:delete e[Rd]),i}var Bd=Object.prototype.toString;function Vd(e){return Bd.call(e)}var Hd=`[object Null]`,Ud=`[object Undefined]`,Wd=Pd?Pd.toStringTag:void 0;function Gd(e){return e==null?e===void 0?Ud:Hd:Wd&&Wd in Object(e)?zd(e):Vd(e)}function Kd(e){return typeof e==`object`&&!!e}var qd=`[object Symbol]`;function Jd(e){return typeof e==`symbol`||Kd(e)&&Gd(e)==qd}function Yd(e,t){for(var n=-1,r=e==null?0:e.length,i=Array(r);++n<r;)i[n]=t(e[n],n,e);return i}var Xd=Array.isArray,Zd=1/0,Qd=Pd?Pd.prototype:void 0,$d=Qd?Qd.toString:void 0;function ef(e){if(typeof e==`string`)return e;if(Xd(e))return Yd(e,ef)+``;if(Jd(e))return $d?$d.call(e):``;var t=e+``;return t==`0`&&1/e==-Zd?`-0`:t}var tf=/\s/;function nf(e){for(var t=e.length;t--&&tf.test(e.charAt(t)););return t}var rf=/^\s+/;function af(e){return e&&e.slice(0,nf(e)+1).replace(rf,``)}function of(e){var t=typeof e;return e!=null&&(t==`object`||t==`function`)}var sf=NaN,cf=/^[-+]0x[0-9a-f]+$/i,lf=/^0b[01]+$/i,uf=/^0o[0-7]+$/i,df=parseInt;function ff(e){if(typeof e==`number`)return e;if(Jd(e))return sf;if(of(e)){var t=typeof e.valueOf==`function`?e.valueOf():e;e=of(t)?t+``:t}if(typeof e!=`string`)return e===0?e:+e;e=af(e);var n=lf.test(e);return n||uf.test(e)?df(e.slice(2),n?2:8):cf.test(e)?sf:+e}function pf(e){return e}var mf=`[object AsyncFunction]`,hf=`[object Function]`,gf=`[object GeneratorFunction]`,_f=`[object Proxy]`;function vf(e){if(!of(e))return!1;var t=Gd(e);return t==hf||t==gf||t==mf||t==_f}var yf=Nd[`__core-js_shared__`],bf=function(){var e=/[^.]+$/.exec(yf&&yf.keys&&yf.keys.IE_PROTO||``);return e?`Symbol(src)_1.`+e:``}();function xf(e){return!!bf&&bf in e}var Sf=Function.prototype.toString;function Cf(e){if(e!=null){try{return Sf.call(e)}catch{}try{return e+``}catch{}}return``}var wf=/[\\^$.*+?()[\]{}|]/g,Tf=/^\[object .+?Constructor\]$/,Ef=Function.prototype,Df=Object.prototype,Of=Ef.toString,kf=Df.hasOwnProperty,Af=RegExp(`^`+Of.call(kf).replace(wf,`\\$&`).replace(/hasOwnProperty|(function).*?(?=\\\()| for .+?(?=\\\])/g,`$1.*?`)+`$`);function jf(e){return!of(e)||xf(e)?!1:(vf(e)?Af:Tf).test(Cf(e))}function Mf(e,t){return e?.[t]}function Nf(e,t){var n=Mf(e,t);return jf(n)?n:void 0}var Pf=Nf(Nd,`WeakMap`),Ff=Object.create,If=function(){function e(){}return function(t){if(!of(t))return{};if(Ff)return Ff(t);e.prototype=t;var n=new e;return e.prototype=void 0,n}}();function Lf(e,t,n){switch(n.length){case 0:return e.call(t);case 1:return e.call(t,n[0]);case 2:return e.call(t,n[0],n[1]);case 3:return e.call(t,n[0],n[1],n[2])}return e.apply(t,n)}function Rf(e,t){var n=-1,r=e.length;for(t||=Array(r);++n<r;)t[n]=e[n];return t}var zf=800,Bf=16,Vf=Date.now;function Hf(e){var t=0,n=0;return function(){var r=Vf(),i=Bf-(r-n);if(n=r,i>0){if(++t>=zf)return arguments[0]}else t=0;return e.apply(void 0,arguments)}}function Uf(e){return function(){return e}}var Wf=function(){try{var e=Nf(Object,`defineProperty`);return e({},``,{}),e}catch{}}(),Gf=Hf(Wf?function(e,t){return Wf(e,`toString`,{configurable:!0,enumerable:!1,value:Uf(t),writable:!0})}:pf);function Kf(e,t){for(var n=-1,r=e==null?0:e.length;++n<r&&t(e[n],n,e)!==!1;);return e}var qf=9007199254740991,Jf=/^(?:0|[1-9]\d*)$/;function Yf(e,t){var n=typeof e;return t??=qf,!!t&&(n==`number`||n!=`symbol`&&Jf.test(e))&&e>-1&&e%1==0&&e<t}function Xf(e,t,n){t==`__proto__`&&Wf?Wf(e,t,{configurable:!0,enumerable:!0,value:n,writable:!0}):e[t]=n}function Zf(e,t){return e===t||e!==e&&t!==t}var Qf=Object.prototype.hasOwnProperty;function $f(e,t,n){var r=e[t];(!(Qf.call(e,t)&&Zf(r,n))||n===void 0&&!(t in e))&&Xf(e,t,n)}function ep(e,t,n,r){var i=!n;n||={};for(var a=-1,o=t.length;++a<o;){var s=t[a],c=r?r(n[s],e[s],s,n,e):void 0;c===void 0&&(c=e[s]),i?Xf(n,s,c):$f(n,s,c)}return n}var tp=Math.max;function np(e,t,n){return t=tp(t===void 0?e.length-1:t,0),function(){for(var r=arguments,i=-1,a=tp(r.length-t,0),o=Array(a);++i<a;)o[i]=r[t+i];i=-1;for(var s=Array(t+1);++i<t;)s[i]=r[i];return s[t]=n(o),Lf(e,this,s)}}function rp(e,t){return Gf(np(e,t,pf),e+``)}var ip=9007199254740991;function ap(e){return typeof e==`number`&&e>-1&&e%1==0&&e<=ip}function op(e){return e!=null&&ap(e.length)&&!vf(e)}function sp(e,t,n){if(!of(n))return!1;var r=typeof t;return(r==`number`?op(n)&&Yf(t,n.length):r==`string`&&t in n)?Zf(n[t],e):!1}function cp(e){return rp(function(t,n){var r=-1,i=n.length,a=i>1?n[i-1]:void 0,o=i>2?n[2]:void 0;for(a=e.length>3&&typeof a==`function`?(i--,a):void 0,o&&sp(n[0],n[1],o)&&(a=i<3?void 0:a,i=1),t=Object(t);++r<i;){var s=n[r];s&&e(t,s,r,a)}return t})}var lp=Object.prototype;function up(e){var t=e&&e.constructor;return e===(typeof t==`function`&&t.prototype||lp)}function dp(e,t){for(var n=-1,r=Array(e);++n<e;)r[n]=t(n);return r}var fp=`[object Arguments]`;function pp(e){return Kd(e)&&Gd(e)==fp}var mp=Object.prototype,hp=mp.hasOwnProperty,gp=mp.propertyIsEnumerable,_p=pp(function(){return arguments}())?pp:function(e){return Kd(e)&&hp.call(e,`callee`)&&!gp.call(e,`callee`)};function vp(){return!1}var yp=typeof exports==`object`&&exports&&!exports.nodeType&&exports,bp=yp&&typeof module==`object`&&module&&!module.nodeType&&module,xp=bp&&bp.exports===yp?Nd.Buffer:void 0,Sp=(xp?xp.isBuffer:void 0)||vp,wp=`[object Arguments]`,Tp=`[object Array]`,Ep=`[object Boolean]`,Dp=`[object Date]`,Op=`[object Error]`,kp=`[object Function]`,Ap=`[object Map]`,jp=`[object Number]`,Mp=`[object Object]`,Np=`[object RegExp]`,Pp=`[object Set]`,Fp=`[object String]`,Ip=`[object WeakMap]`,Lp=`[object ArrayBuffer]`,Rp=`[object DataView]`,zp=`[object Float32Array]`,Bp=`[object Float64Array]`,Vp=`[object Int8Array]`,Hp=`[object Int16Array]`,Up=`[object Int32Array]`,Wp=`[object Uint8Array]`,Gp=`[object Uint8ClampedArray]`,Kp=`[object Uint16Array]`,qp=`[object Uint32Array]`,Jp={};Jp[zp]=Jp[Bp]=Jp[Vp]=Jp[Hp]=Jp[Up]=Jp[Wp]=Jp[Gp]=Jp[Kp]=Jp[qp]=!0,Jp[wp]=Jp[Tp]=Jp[Lp]=Jp[Ep]=Jp[Rp]=Jp[Dp]=Jp[Op]=Jp[kp]=Jp[Ap]=Jp[jp]=Jp[Mp]=Jp[Np]=Jp[Pp]=Jp[Fp]=Jp[Ip]=!1;function Yp(e){return Kd(e)&&ap(e.length)&&!!Jp[Gd(e)]}function Xp(e){return function(t){return e(t)}}var Zp=typeof exports==`object`&&exports&&!exports.nodeType&&exports,Qp=Zp&&typeof module==`object`&&module&&!module.nodeType&&module,$p=Qp&&Qp.exports===Zp&&jd.process,em=function(){try{return Qp&&Qp.require&&Qp.require(`util`).types||$p&&$p.binding&&$p.binding(`util`)}catch{}}(),tm=em&&em.isTypedArray,nm=tm?Xp(tm):Yp,rm=Object.prototype.hasOwnProperty;function im(e,t){var n=Xd(e),r=!n&&_p(e),i=!n&&!r&&Sp(e),a=!n&&!r&&!i&&nm(e),o=n||r||i||a,s=o?dp(e.length,String):[],c=s.length;for(var l in e)(t||rm.call(e,l))&&!(o&&(l==`length`||i&&(l==`offset`||l==`parent`)||a&&(l==`buffer`||l==`byteLength`||l==`byteOffset`)||Yf(l,c)))&&s.push(l);return s}function am(e,t){return function(n){return e(t(n))}}var om=am(Object.keys,Object),sm=Object.prototype.hasOwnProperty;function cm(e){if(!up(e))return om(e);var t=[];for(var n in Object(e))sm.call(e,n)&&n!=`constructor`&&t.push(n);return t}function lm(e){return op(e)?im(e):cm(e)}function um(e){var t=[];if(e!=null)for(var n in Object(e))t.push(n);return t}var dm=Object.prototype.hasOwnProperty;function fm(e){if(!of(e))return um(e);var t=up(e),n=[];for(var r in e)r==`constructor`&&(t||!dm.call(e,r))||n.push(r);return n}function pm(e){return op(e)?im(e,!0):fm(e)}var mm=/\.|\[(?:[^[\]]*|(["'])(?:(?!\1)[^\\]|\\.)*?\1)\]/,hm=/^\w*$/;function gm(e,t){if(Xd(e))return!1;var n=typeof e;return n==`number`||n==`symbol`||n==`boolean`||e==null||Jd(e)?!0:hm.test(e)||!mm.test(e)||t!=null&&e in Object(t)}var _m=Nf(Object,`create`);function vm(){this.__data__=_m?_m(null):{},this.size=0}function ym(e){var t=this.has(e)&&delete this.__data__[e];return this.size-=t?1:0,t}var bm=`__lodash_hash_undefined__`,xm=Object.prototype.hasOwnProperty;function Sm(e){var t=this.__data__;if(_m){var n=t[e];return n===bm?void 0:n}return xm.call(t,e)?t[e]:void 0}var Cm=Object.prototype.hasOwnProperty;function wm(e){var t=this.__data__;return _m?t[e]!==void 0:Cm.call(t,e)}var Tm=`__lodash_hash_undefined__`;function Em(e,t){var n=this.__data__;return this.size+=this.has(e)?0:1,n[e]=_m&&t===void 0?Tm:t,this}function Dm(e){var t=-1,n=e==null?0:e.length;for(this.clear();++t<n;){var r=e[t];this.set(r[0],r[1])}}Dm.prototype.clear=vm,Dm.prototype.delete=ym,Dm.prototype.get=Sm,Dm.prototype.has=wm,Dm.prototype.set=Em;function Om(){this.__data__=[],this.size=0}function km(e,t){for(var n=e.length;n--;)if(Zf(e[n][0],t))return n;return-1}var Am=Array.prototype.splice;function jm(e){var t=this.__data__,n=km(t,e);return n<0?!1:(n==t.length-1?t.pop():Am.call(t,n,1),--this.size,!0)}function Mm(e){var t=this.__data__,n=km(t,e);return n<0?void 0:t[n][1]}function Nm(e){return km(this.__data__,e)>-1}function Pm(e,t){var n=this.__data__,r=km(n,e);return r<0?(++this.size,n.push([e,t])):n[r][1]=t,this}function Fm(e){var t=-1,n=e==null?0:e.length;for(this.clear();++t<n;){var r=e[t];this.set(r[0],r[1])}}Fm.prototype.clear=Om,Fm.prototype.delete=jm,Fm.prototype.get=Mm,Fm.prototype.has=Nm,Fm.prototype.set=Pm;var Im=Nf(Nd,`Map`);function Lm(){this.size=0,this.__data__={hash:new Dm,map:new(Im||Fm),string:new Dm}}function Rm(e){var t=typeof e;return t==`string`||t==`number`||t==`symbol`||t==`boolean`?e!==`__proto__`:e===null}function zm(e,t){var n=e.__data__;return Rm(t)?n[typeof t==`string`?`string`:`hash`]:n.map}function Bm(e){var t=zm(this,e).delete(e);return this.size-=t?1:0,t}function Vm(e){return zm(this,e).get(e)}function Hm(e){return zm(this,e).has(e)}function Um(e,t){var n=zm(this,e),r=n.size;return n.set(e,t),this.size+=n.size==r?0:1,this}function Wm(e){var t=-1,n=e==null?0:e.length;for(this.clear();++t<n;){var r=e[t];this.set(r[0],r[1])}}Wm.prototype.clear=Lm,Wm.prototype.delete=Bm,Wm.prototype.get=Vm,Wm.prototype.has=Hm,Wm.prototype.set=Um;var Gm=`Expected a function`;function Km(e,t){if(typeof e!=`function`||t!=null&&typeof t!=`function`)throw TypeError(Gm);var n=function(){var r=arguments,i=t?t.apply(this,r):r[0],a=n.cache;if(a.has(i))return a.get(i);var o=e.apply(this,r);return n.cache=a.set(i,o)||a,o};return n.cache=new(Km.Cache||Wm),n}Km.Cache=Wm;var qm=500;function Jm(e){var t=Km(e,function(e){return n.size===qm&&n.clear(),e}),n=t.cache;return t}var Ym=/[^.[\]]+|\[(?:(-?\d+(?:\.\d+)?)|(["'])((?:(?!\2)[^\\]|\\.)*?)\2)\]|(?=(?:\.|\[\])(?:\.|\[\]|$))/g,Xm=/\\(\\)?/g,Zm=Jm(function(e){var t=[];return e.charCodeAt(0)===46&&t.push(``),e.replace(Ym,function(e,n,r,i){t.push(r?i.replace(Xm,`$1`):n||e)}),t});function Qm(e){return e==null?``:ef(e)}function $m(e,t){return Xd(e)?e:gm(e,t)?[e]:Zm(Qm(e))}var eh=1/0;function th(e){if(typeof e==`string`||Jd(e))return e;var t=e+``;return t==`0`&&1/e==-eh?`-0`:t}function nh(e,t){t=$m(t,e);for(var n=0,r=t.length;e!=null&&n<r;)e=e[th(t[n++])];return n&&n==r?e:void 0}function rh(e,t,n){var r=e==null?void 0:nh(e,t);return r===void 0?n:r}function ih(e,t){for(var n=-1,r=t.length,i=e.length;++n<r;)e[i+n]=t[n];return e}var ah=Pd?Pd.isConcatSpreadable:void 0;function oh(e){return Xd(e)||_p(e)||!!(ah&&e&&e[ah])}function sh(e,t,n,r,i){var a=-1,o=e.length;for(n||=oh,i||=[];++a<o;){var s=e[a];t>0&&n(s)?t>1?sh(s,t-1,n,r,i):ih(i,s):r||(i[i.length]=s)}return i}function ch(e){return e!=null&&e.length?sh(e,1):[]}function lh(e){return Gf(np(e,void 0,ch),e+``)}var uh=am(Object.getPrototypeOf,Object),dh=`[object Object]`,fh=Function.prototype,ph=Object.prototype,mh=fh.toString,hh=ph.hasOwnProperty,gh=mh.call(Object);function _h(e){if(!Kd(e)||Gd(e)!=dh)return!1;var t=uh(e);if(t===null)return!0;var n=hh.call(t,`constructor`)&&t.constructor;return typeof n==`function`&&n instanceof n&&mh.call(n)==gh}function vh(e,t,n){var r=-1,i=e.length;t<0&&(t=-t>i?0:i+t),n=n>i?i:n,n<0&&(n+=i),i=t>n?0:n-t>>>0,t>>>=0;for(var a=Array(i);++r<i;)a[r]=e[r+t];return a}function yh(e){return function(t){return e?.[t]}}function bh(){this.__data__=new Fm,this.size=0}function xh(e){var t=this.__data__,n=t.delete(e);return this.size=t.size,n}function Sh(e){return this.__data__.get(e)}function Ch(e){return this.__data__.has(e)}var wh=200;function Th(e,t){var n=this.__data__;if(n instanceof Fm){var r=n.__data__;if(!Im||r.length<wh-1)return r.push([e,t]),this.size=++n.size,this;n=this.__data__=new Wm(r)}return n.set(e,t),this.size=n.size,this}function Eh(e){this.size=(this.__data__=new Fm(e)).size}Eh.prototype.clear=bh,Eh.prototype.delete=xh,Eh.prototype.get=Sh,Eh.prototype.has=Ch,Eh.prototype.set=Th;function Dh(e,t){return e&&ep(t,lm(t),e)}function Oh(e,t){return e&&ep(t,pm(t),e)}var kh=typeof exports==`object`&&exports&&!exports.nodeType&&exports,Ah=kh&&typeof module==`object`&&module&&!module.nodeType&&module,jh=Ah&&Ah.exports===kh?Nd.Buffer:void 0,Mh=jh?jh.allocUnsafe:void 0;function Nh(e,t){if(t)return e.slice();var n=e.length,r=Mh?Mh(n):new e.constructor(n);return e.copy(r),r}function Ph(e,t){for(var n=-1,r=e==null?0:e.length,i=0,a=[];++n<r;){var o=e[n];t(o,n,e)&&(a[i++]=o)}return a}function Fh(){return[]}var Ih=Object.prototype.propertyIsEnumerable,Lh=Object.getOwnPropertySymbols,Rh=Lh?function(e){return e==null?[]:(e=Object(e),Ph(Lh(e),function(t){return Ih.call(e,t)}))}:Fh;function zh(e,t){return ep(e,Rh(e),t)}var Bh=Object.getOwnPropertySymbols?function(e){for(var t=[];e;)ih(t,Rh(e)),e=uh(e);return t}:Fh;function Vh(e,t){return ep(e,Bh(e),t)}function Hh(e,t,n){var r=t(e);return Xd(e)?r:ih(r,n(e))}function Uh(e){return Hh(e,lm,Rh)}function Wh(e){return Hh(e,pm,Bh)}var Gh=Nf(Nd,`DataView`),Kh=Nf(Nd,`Promise`),qh=Nf(Nd,`Set`),Jh=`[object Map]`,Yh=`[object Object]`,Xh=`[object Promise]`,Zh=`[object Set]`,Qh=`[object WeakMap]`,$h=`[object DataView]`,eg=Cf(Gh),tg=Cf(Im),ng=Cf(Kh),rg=Cf(qh),ig=Cf(Pf),ag=Gd;(Gh&&ag(new Gh(new ArrayBuffer(1)))!=$h||Im&&ag(new Im)!=Jh||Kh&&ag(Kh.resolve())!=Xh||qh&&ag(new qh)!=Zh||Pf&&ag(new Pf)!=Qh)&&(ag=function(e){var t=Gd(e),n=t==Yh?e.constructor:void 0,r=n?Cf(n):``;if(r)switch(r){case eg:return $h;case tg:return Jh;case ng:return Xh;case rg:return Zh;case ig:return Qh}return t});var og=ag,sg=Object.prototype.hasOwnProperty;function cg(e){var t=e.length,n=new e.constructor(t);return t&&typeof e[0]==`string`&&sg.call(e,`index`)&&(n.index=e.index,n.input=e.input),n}var lg=Nd.Uint8Array;function ug(e){var t=new e.constructor(e.byteLength);return new lg(t).set(new lg(e)),t}function dg(e,t){var n=t?ug(e.buffer):e.buffer;return new e.constructor(n,e.byteOffset,e.byteLength)}var fg=/\w*$/;function pg(e){var t=new e.constructor(e.source,fg.exec(e));return t.lastIndex=e.lastIndex,t}var mg=Pd?Pd.prototype:void 0,hg=mg?mg.valueOf:void 0;function gg(e){return hg?Object(hg.call(e)):{}}function _g(e,t){var n=t?ug(e.buffer):e.buffer;return new e.constructor(n,e.byteOffset,e.length)}var vg=`[object Boolean]`,yg=`[object Date]`,bg=`[object Map]`,xg=`[object Number]`,Sg=`[object RegExp]`,Cg=`[object Set]`,wg=`[object String]`,Tg=`[object Symbol]`,Eg=`[object ArrayBuffer]`,Dg=`[object DataView]`,Og=`[object Float32Array]`,kg=`[object Float64Array]`,Ag=`[object Int8Array]`,jg=`[object Int16Array]`,Mg=`[object Int32Array]`,Ng=`[object Uint8Array]`,Pg=`[object Uint8ClampedArray]`,Fg=`[object Uint16Array]`,Ig=`[object Uint32Array]`;function Lg(e,t,n){var r=e.constructor;switch(t){case Eg:return ug(e);case vg:case yg:return new r(+e);case Dg:return dg(e,n);case Og:case kg:case Ag:case jg:case Mg:case Ng:case Pg:case Fg:case Ig:return _g(e,n);case bg:return new r;case xg:case wg:return new r(e);case Sg:return pg(e);case Cg:return new r;case Tg:return gg(e)}}function Rg(e){return typeof e.constructor==`function`&&!up(e)?If(uh(e)):{}}var zg=`[object Map]`;function Bg(e){return Kd(e)&&og(e)==zg}var Vg=em&&em.isMap,Hg=Vg?Xp(Vg):Bg,Ug=`[object Set]`;function Wg(e){return Kd(e)&&og(e)==Ug}var Gg=em&&em.isSet,Kg=Gg?Xp(Gg):Wg,qg=1,Jg=2,Yg=4,Xg=`[object Arguments]`,Zg=`[object Array]`,Qg=`[object Boolean]`,$g=`[object Date]`,e_=`[object Error]`,t_=`[object Function]`,n_=`[object GeneratorFunction]`,r_=`[object Map]`,i_=`[object Number]`,a_=`[object Object]`,o_=`[object RegExp]`,s_=`[object Set]`,c_=`[object String]`,l_=`[object Symbol]`,u_=`[object WeakMap]`,d_=`[object ArrayBuffer]`,f_=`[object DataView]`,p_=`[object Float32Array]`,m_=`[object Float64Array]`,h_=`[object Int8Array]`,g_=`[object Int16Array]`,__=`[object Int32Array]`,v_=`[object Uint8Array]`,y_=`[object Uint8ClampedArray]`,b_=`[object Uint16Array]`,x_=`[object Uint32Array]`,H={};H[Xg]=H[Zg]=H[d_]=H[f_]=H[Qg]=H[$g]=H[p_]=H[m_]=H[h_]=H[g_]=H[__]=H[r_]=H[i_]=H[a_]=H[o_]=H[s_]=H[c_]=H[l_]=H[v_]=H[y_]=H[b_]=H[x_]=!0,H[e_]=H[t_]=H[u_]=!1;function S_(e,t,n,r,i,a){var o,s=t&qg,c=t&Jg,l=t&Yg;if(n&&(o=i?n(e,r,i,a):n(e)),o!==void 0)return o;if(!of(e))return e;var u=Xd(e);if(u){if(o=cg(e),!s)return Rf(e,o)}else{var d=og(e),f=d==t_||d==n_;if(Sp(e))return Nh(e,s);if(d==a_||d==Xg||f&&!i){if(o=c||f?{}:Rg(e),!s)return c?Vh(e,Oh(o,e)):zh(e,Dh(o,e))}else{if(!H[d])return i?e:{};o=Lg(e,d,s)}}a||=new Eh;var p=a.get(e);if(p)return p;a.set(e,o),Kg(e)?e.forEach(function(r){o.add(S_(r,t,n,r,e,a))}):Hg(e)&&e.forEach(function(r,i){o.set(i,S_(r,t,n,i,e,a))});var m=u?void 0:(l?c?Wh:Uh:c?pm:lm)(e);return Kf(m||e,function(r,i){m&&(i=r,r=e[i]),$f(o,i,S_(r,t,n,i,e,a))}),o}var C_=1,w_=4;function T_(e){return S_(e,C_|w_)}var E_=`__lodash_hash_undefined__`;function D_(e){return this.__data__.set(e,E_),this}function O_(e){return this.__data__.has(e)}function k_(e){var t=-1,n=e==null?0:e.length;for(this.__data__=new Wm;++t<n;)this.add(e[t])}k_.prototype.add=k_.prototype.push=D_,k_.prototype.has=O_;function A_(e,t){for(var n=-1,r=e==null?0:e.length;++n<r;)if(t(e[n],n,e))return!0;return!1}function j_(e,t){return e.has(t)}var M_=1,N_=2;function P_(e,t,n,r,i,a){var o=n&M_,s=e.length,c=t.length;if(s!=c&&!(o&&c>s))return!1;var l=a.get(e),u=a.get(t);if(l&&u)return l==t&&u==e;var d=-1,f=!0,p=n&N_?new k_:void 0;for(a.set(e,t),a.set(t,e);++d<s;){var m=e[d],h=t[d];if(r)var g=o?r(h,m,d,t,e,a):r(m,h,d,e,t,a);if(g!==void 0){if(g)continue;f=!1;break}if(p){if(!A_(t,function(e,t){if(!j_(p,t)&&(m===e||i(m,e,n,r,a)))return p.push(t)})){f=!1;break}}else if(!(m===h||i(m,h,n,r,a))){f=!1;break}}return a.delete(e),a.delete(t),f}function F_(e){var t=-1,n=Array(e.size);return e.forEach(function(e,r){n[++t]=[r,e]}),n}function I_(e){var t=-1,n=Array(e.size);return e.forEach(function(e){n[++t]=e}),n}var L_=1,R_=2,z_=`[object Boolean]`,B_=`[object Date]`,V_=`[object Error]`,H_=`[object Map]`,U_=`[object Number]`,W_=`[object RegExp]`,G_=`[object Set]`,K_=`[object String]`,q_=`[object Symbol]`,J_=`[object ArrayBuffer]`,Y_=`[object DataView]`,X_=Pd?Pd.prototype:void 0,Z_=X_?X_.valueOf:void 0;function Q_(e,t,n,r,i,a,o){switch(n){case Y_:if(e.byteLength!=t.byteLength||e.byteOffset!=t.byteOffset)return!1;e=e.buffer,t=t.buffer;case J_:return!(e.byteLength!=t.byteLength||!a(new lg(e),new lg(t)));case z_:case B_:case U_:return Zf(+e,+t);case V_:return e.name==t.name&&e.message==t.message;case W_:case K_:return e==t+``;case H_:var s=F_;case G_:var c=r&L_;if(s||=I_,e.size!=t.size&&!c)return!1;var l=o.get(e);if(l)return l==t;r|=R_,o.set(e,t);var u=P_(s(e),s(t),r,i,a,o);return o.delete(e),u;case q_:if(Z_)return Z_.call(e)==Z_.call(t)}return!1}var $_=1,ev=Object.prototype.hasOwnProperty;function tv(e,t,n,r,i,a){var o=n&$_,s=Uh(e),c=s.length;if(c!=Uh(t).length&&!o)return!1;for(var l=c;l--;){var u=s[l];if(!(o?u in t:ev.call(t,u)))return!1}var d=a.get(e),f=a.get(t);if(d&&f)return d==t&&f==e;var p=!0;a.set(e,t),a.set(t,e);for(var m=o;++l<c;){u=s[l];var h=e[u],g=t[u];if(r)var _=o?r(g,h,u,t,e,a):r(h,g,u,e,t,a);if(!(_===void 0?h===g||i(h,g,n,r,a):_)){p=!1;break}m||=u==`constructor`}if(p&&!m){var v=e.constructor,y=t.constructor;v!=y&&`constructor`in e&&`constructor`in t&&!(typeof v==`function`&&v instanceof v&&typeof y==`function`&&y instanceof y)&&(p=!1)}return a.delete(e),a.delete(t),p}var nv=1,rv=`[object Arguments]`,iv=`[object Array]`,av=`[object Object]`,ov=Object.prototype.hasOwnProperty;function sv(e,t,n,r,i,a){var o=Xd(e),s=Xd(t),c=o?iv:og(e),l=s?iv:og(t);c=c==rv?av:c,l=l==rv?av:l;var u=c==av,d=l==av,f=c==l;if(f&&Sp(e)){if(!Sp(t))return!1;o=!0,u=!1}if(f&&!u)return a||=new Eh,o||nm(e)?P_(e,t,n,r,i,a):Q_(e,t,c,n,r,i,a);if(!(n&nv)){var p=u&&ov.call(e,`__wrapped__`),m=d&&ov.call(t,`__wrapped__`);if(p||m){var h=p?e.value():e,g=m?t.value():t;return a||=new Eh,i(h,g,n,r,a)}}return f?(a||=new Eh,tv(e,t,n,r,i,a)):!1}function cv(e,t,n,r,i){return e===t?!0:e==null||t==null||!Kd(e)&&!Kd(t)?e!==e&&t!==t:sv(e,t,n,r,cv,i)}function lv(e,t,n){t=$m(t,e);for(var r=-1,i=t.length,a=!1;++r<i;){var o=th(t[r]);if(!(a=e!=null&&n(e,o)))break;e=e[o]}return a||++r!=i?a:(i=e==null?0:e.length,!!i&&ap(i)&&Yf(o,i)&&(Xd(e)||_p(e)))}function uv(e){return function(t,n,r){for(var i=-1,a=Object(t),o=r(t),s=o.length;s--;){var c=o[e?s:++i];if(n(a[c],c,a)===!1)break}return t}}var dv=uv(),fv=function(){return Nd.Date.now()},pv=`Expected a function`,mv=Math.max,hv=Math.min;function gv(e,t,n){var r,i,a,o,s,c,l=0,u=!1,d=!1,f=!0;if(typeof e!=`function`)throw TypeError(pv);t=ff(t)||0,of(n)&&(u=!!n.leading,d=`maxWait`in n,a=d?mv(ff(n.maxWait)||0,t):a,f=`trailing`in n?!!n.trailing:f);function p(t){var n=r,a=i;return r=i=void 0,l=t,o=e.apply(a,n),o}function m(e){return l=e,s=setTimeout(_,t),u?p(e):o}function h(e){var n=e-c,r=e-l,i=t-n;return d?hv(i,a-r):i}function g(e){var n=e-c,r=e-l;return c===void 0||n>=t||n<0||d&&r>=a}function _(){var e=fv();if(g(e))return v(e);s=setTimeout(_,h(e))}function v(e){return s=void 0,f&&r?p(e):(r=i=void 0,o)}function y(){s!==void 0&&clearTimeout(s),l=0,r=c=i=s=void 0}function b(){return s===void 0?o:v(fv())}function x(){var e=fv(),n=g(e);if(r=arguments,i=this,c=e,n){if(s===void 0)return m(c);if(d)return clearTimeout(s),s=setTimeout(_,t),p(c)}return s===void 0&&(s=setTimeout(_,t)),o}return x.cancel=y,x.flush=b,x}function _v(e,t,n){(n!==void 0&&!Zf(e[t],n)||n===void 0&&!(t in e))&&Xf(e,t,n)}function vv(e){return Kd(e)&&op(e)}function yv(e,t){if(!(t===`constructor`&&typeof e[t]==`function`)&&t!=`__proto__`)return e[t]}function bv(e){return ep(e,pm(e))}function xv(e,t,n,r,i,a,o){var s=yv(e,n),c=yv(t,n),l=o.get(c);if(l){_v(e,n,l);return}var u=a?a(s,c,n+``,e,t,o):void 0,d=u===void 0;if(d){var f=Xd(c),p=!f&&Sp(c),m=!f&&!p&&nm(c);u=c,f||p||m?Xd(s)?u=s:vv(s)?u=Rf(s):p?(d=!1,u=Nh(c,!0)):m?(d=!1,u=_g(c,!0)):u=[]:_h(c)||_p(c)?(u=s,_p(s)?u=bv(s):(!of(s)||vf(s))&&(u=Rg(c))):d=!1}d&&(o.set(c,u),i(u,c,r,a,o),o.delete(c)),_v(e,n,u)}function Sv(e,t,n,r,i){e!==t&&dv(t,function(a,o){if(i||=new Eh,of(a))xv(e,t,o,n,Sv,r,i);else{var s=r?r(yv(e,o),a,o+``,e,t,i):void 0;s===void 0&&(s=a),_v(e,o,s)}},pm)}function Cv(e){var t=e==null?0:e.length;return t?e[t-1]:void 0}var wv=yh({"&":`&amp;`,"<":`&lt;`,">":`&gt;`,'"':`&quot;`,"'":`&#39;`}),Tv=/[&<>"']/g,Ev=RegExp(Tv.source);function Dv(e){return e=Qm(e),e&&Ev.test(e)?e.replace(Tv,wv):e}var Ov=Object.prototype.hasOwnProperty;function kv(e,t){return e!=null&&Ov.call(e,t)}function Av(e,t){return e!=null&&lv(e,t,kv)}function jv(e,t){return t.length<2?e:nh(e,vh(t,0,-1))}function Mv(e,t){return cv(e,t)}var Nv=cp(function(e,t,n){Sv(e,t,n)}),Pv=Object.prototype.hasOwnProperty;function Fv(e,t){t=$m(t,e);var n=-1,r=t.length;if(!r)return!0;for(var i=e==null||typeof e!=`object`&&typeof e!=`function`;++n<r;){var a=t[n];if(typeof a==`string`){if(a===`__proto__`&&!Pv.call(e,`__proto__`))return!1;if(a===`constructor`&&n+1<r&&typeof t[n+1]==`string`&&t[n+1]===`prototype`){if(i&&n===0)continue;return!1}}}var o=jv(e,t);return o==null||delete o[th(Cv(t))]}function Iv(e){return _h(e)?void 0:e}var Lv=1,Rv=2,zv=4,Bv=lh(function(e,t){var n={};if(e==null)return n;var r=!1;t=Yd(t,function(t){return t=$m(t,e),r||=t.length>1,t}),ep(e,Wh(e),n),r&&(n=S_(n,Lv|Rv|zv,Iv));for(var i=t.length;i--;)Fv(n,t[i]);return n});function Vv(e,t,n,r){if(!of(e))return e;t=$m(t,e);for(var i=-1,a=t.length,o=a-1,s=e;s!=null&&++i<a;){var c=th(t[i]),l=n;if(c===`__proto__`||c===`constructor`||c===`prototype`)return e;if(i!=o){var u=s[c];l=r?r(u,c,s):void 0,l===void 0&&(l=of(u)?u:Yf(t[i+1])?[]:{})}$f(s,c,l),s=s[c]}return e}function Hv(e,t,n){return e==null?e:Vv(e,t,n)}var Uv=c(((e,t)=>{t.exports=TypeError})),Wv=c(((e,t)=>{t.exports={}})),Gv=c(((e,t)=>{var n=typeof Map==`function`&&Map.prototype,r=Object.getOwnPropertyDescriptor&&n?Object.getOwnPropertyDescriptor(Map.prototype,`size`):null,i=n&&r&&typeof r.get==`function`?r.get:null,a=n&&Map.prototype.forEach,o=typeof Set==`function`&&Set.prototype,s=Object.getOwnPropertyDescriptor&&o?Object.getOwnPropertyDescriptor(Set.prototype,`size`):null,c=o&&s&&typeof s.get==`function`?s.get:null,l=o&&Set.prototype.forEach,u=typeof WeakMap==`function`&&WeakMap.prototype?WeakMap.prototype.has:null,d=typeof WeakSet==`function`&&WeakSet.prototype?WeakSet.prototype.has:null,f=typeof WeakRef==`function`&&WeakRef.prototype?WeakRef.prototype.deref:null,p=Boolean.prototype.valueOf,m=Object.prototype.toString,h=Function.prototype.toString,g=String.prototype.match,_=String.prototype.slice,v=String.prototype.replace,y=String.prototype.toUpperCase,b=String.prototype.toLowerCase,x=RegExp.prototype.test,S=Array.prototype.concat,C=Array.prototype.join,w=Array.prototype.slice,T=Math.floor,E=typeof BigInt==`function`?BigInt.prototype.valueOf:null,D=Object.getOwnPropertySymbols,O=typeof Symbol==`function`&&typeof Symbol.iterator==`symbol`?Symbol.prototype.toString:null,k=typeof Symbol==`function`&&typeof Symbol.iterator==`object`,A=typeof Symbol==`function`&&Symbol.toStringTag?Symbol.toStringTag:null,ee=Object.prototype.propertyIsEnumerable,te=(typeof Reflect==`function`?Reflect.getPrototypeOf:Object.getPrototypeOf)||([].__proto__===Array.prototype?function(e){return e.__proto__}:null);function ne(e,t){if(e===1/0||e===-1/0||e!==e||e&&e>-1e3&&e<1e3||x.call(/e/,t))return t;var n=/[0-9](?=(?:[0-9]{3})+(?![0-9]))/g;if(typeof e==`number`){var r=e<0?-T(-e):T(e);if(r!==e){var i=String(r),a=_.call(t,i.length+1);return v.call(i,n,`$&_`)+`.`+v.call(v.call(a,/([0-9]{3})/g,`$&_`),/_$/,``)}}return v.call(t,n,`$&_`)}var re=Wv(),ie=re.custom,ae=_e(ie)?ie:null,oe={__proto__:null,double:`"`,single:`'`},se={__proto__:null,double:/(["\\])/g,single:/(['\\])/g};t.exports=function e(t,n,r,o){var s=n||{};if(be(s,`quoteStyle`)&&!be(oe,s.quoteStyle))throw TypeError(`option "quoteStyle" must be "single" or "double"`);if(be(s,`maxStringLength`)&&(typeof s.maxStringLength==`number`?s.maxStringLength<0&&s.maxStringLength!==1/0:s.maxStringLength!==null))throw TypeError('option "maxStringLength", if provided, must be a positive integer, Infinity, or `null`');var u=be(s,`customInspect`)?s.customInspect:!0;if(typeof u!=`boolean`&&u!==`symbol`)throw TypeError("option \"customInspect\", if provided, must be `true`, `false`, or `'symbol'`");if(be(s,`indent`)&&s.indent!==null&&s.indent!==`	`&&!(parseInt(s.indent,10)===s.indent&&s.indent>0))throw TypeError('option "indent" must be "\\t", an integer > 0, or `null`');if(be(s,`numericSeparator`)&&typeof s.numericSeparator!=`boolean`)throw TypeError('option "numericSeparator", if provided, must be `true` or `false`');var d=s.numericSeparator;if(t===void 0)return`undefined`;if(t===null)return`null`;if(typeof t==`boolean`)return t?`true`:`false`;if(typeof t==`string`)return Ae(t,s);if(typeof t==`number`){if(t===0)return 1/0/t>0?`0`:`-0`;var f=String(t);return d?ne(t,f):f}if(typeof t==`bigint`){var m=String(t)+`n`;return d?ne(t,m):m}var h=s.depth===void 0?5:s.depth;if(r===void 0&&(r=0),r>=h&&h>0&&typeof t==`object`)return de(t)?`[Array]`:`[Object]`;var g=Ie(s,r);if(o===void 0)o=[];else if(Ce(o,t)>=0)return`[Circular]`;function y(t,n,i){if(n&&(o=w.call(o),o.push(n)),i){var a={depth:s.depth};return be(s,`quoteStyle`)&&(a.quoteStyle=s.quoteStyle),e(t,a,r+1,o)}return e(t,s,r+1,o)}if(typeof t==`function`&&!pe(t)){var x=Se(t),T=Re(t,y);return`[Function`+(x?`: `+x:` (anonymous)`)+`]`+(T.length>0?` { `+C.call(T,`, `)+` }`:``)}if(_e(t)){var D=k?v.call(String(t),/^(Symbol\(.*\))_[^)]*$/,`$1`):O.call(t);return typeof t==`object`&&!k?Me(D):D}if(ke(t)){for(var ie=`<`+b.call(String(t.nodeName)),se=t.attributes||[],ue=0;ue<se.length;ue++)ie+=` `+se[ue].name+`=`+ce(le(se[ue].value),`double`,s);return ie+=`>`,t.childNodes&&t.childNodes.length&&(ie+=`...`),ie+=`</`+b.call(String(t.nodeName))+`>`,ie}if(de(t)){if(t.length===0)return`[]`;var ye=Re(t,y);return g&&!Fe(ye)?`[`+Le(ye,g)+`]`:`[ `+C.call(ye,`, `)+` ]`}if(me(t)){var je=Re(t,y);return!(`cause`in Error.prototype)&&`cause`in t&&!ee.call(t,`cause`)?`{ [`+String(t)+`] `+C.call(S.call(`[cause]: `+y(t.cause),je),`, `)+` }`:je.length===0?`[`+String(t)+`]`:`{ [`+String(t)+`] `+C.call(je,`, `)+` }`}if(typeof t==`object`&&u){if(ae&&typeof t[ae]==`function`&&re)return re(t,{depth:h-r});if(u!==`symbol`&&typeof t.inspect==`function`)return t.inspect()}if(we(t)){var ze=[];return a&&a.call(t,function(e,n){ze.push(y(n,t,!0)+` => `+y(e,t))}),Pe(`Map`,i.call(t),ze,g)}if(De(t)){var Be=[];return l&&l.call(t,function(e){Be.push(y(e,t))}),Pe(`Set`,c.call(t),Be,g)}if(Te(t))return Ne(`WeakMap`);if(Oe(t))return Ne(`WeakSet`);if(Ee(t))return Ne(`WeakRef`);if(he(t))return Me(y(Number(t)));if(ve(t))return Me(y(E.call(t)));if(ge(t))return Me(p.call(t));if(j(t))return Me(y(String(t)));if(typeof window<`u`&&t===window)return`{ [object Window] }`;if(typeof globalThis<`u`&&t===globalThis||typeof global<`u`&&t===global)return`{ [object globalThis] }`;if(!fe(t)&&!pe(t)){var Ve=Re(t,y),He=te?te(t)===Object.prototype:t instanceof Object||t.constructor===Object,Ue=t instanceof Object?``:`null prototype`,M=!He&&A&&Object(t)===t&&A in t?_.call(xe(t),8,-1):Ue?`Object`:``,We=(He||typeof t.constructor!=`function`?``:t.constructor.name?t.constructor.name+` `:``)+(M||Ue?`[`+C.call(S.call([],M||[],Ue||[]),`: `)+`] `:``);return Ve.length===0?We+`{}`:g?We+`{`+Le(Ve,g)+`}`:We+`{ `+C.call(Ve,`, `)+` }`}return String(t)};function ce(e,t,n){var r=oe[n.quoteStyle||t];return r+e+r}function le(e){return v.call(String(e),/"/g,`&quot;`)}function ue(e){return!A||!(typeof e==`object`&&(A in e||e[A]!==void 0))}function de(e){return xe(e)===`[object Array]`&&ue(e)}function fe(e){return xe(e)===`[object Date]`&&ue(e)}function pe(e){return xe(e)===`[object RegExp]`&&ue(e)}function me(e){return xe(e)===`[object Error]`&&ue(e)}function j(e){return xe(e)===`[object String]`&&ue(e)}function he(e){return xe(e)===`[object Number]`&&ue(e)}function ge(e){return xe(e)===`[object Boolean]`&&ue(e)}function _e(e){if(k)return e&&typeof e==`object`&&e instanceof Symbol;if(typeof e==`symbol`)return!0;if(!e||typeof e!=`object`||!O)return!1;try{return O.call(e),!0}catch{}return!1}function ve(e){if(!e||typeof e!=`object`||!E)return!1;try{return E.call(e),!0}catch{}return!1}var ye=Object.prototype.hasOwnProperty||function(e){return e in this};function be(e,t){return ye.call(e,t)}function xe(e){return m.call(e)}function Se(e){if(e.name)return e.name;var t=g.call(h.call(e),/^function\s*([\w$]+)/);return t?t[1]:null}function Ce(e,t){if(e.indexOf)return e.indexOf(t);for(var n=0,r=e.length;n<r;n++)if(e[n]===t)return n;return-1}function we(e){if(!i||!e||typeof e!=`object`)return!1;try{i.call(e);try{c.call(e)}catch{return!0}return e instanceof Map}catch{}return!1}function Te(e){if(!u||!e||typeof e!=`object`)return!1;try{u.call(e,u);try{d.call(e,d)}catch{return!0}return e instanceof WeakMap}catch{}return!1}function Ee(e){if(!f||!e||typeof e!=`object`)return!1;try{return f.call(e),!0}catch{}return!1}function De(e){if(!c||!e||typeof e!=`object`)return!1;try{c.call(e);try{i.call(e)}catch{return!0}return e instanceof Set}catch{}return!1}function Oe(e){if(!d||!e||typeof e!=`object`)return!1;try{d.call(e,d);try{u.call(e,u)}catch{return!0}return e instanceof WeakSet}catch{}return!1}function ke(e){return!e||typeof e!=`object`?!1:typeof HTMLElement<`u`&&e instanceof HTMLElement?!0:typeof e.nodeName==`string`&&typeof e.getAttribute==`function`}function Ae(e,t){if(e.length>t.maxStringLength){var n=e.length-t.maxStringLength,r=`... `+n+` more character`+(n>1?`s`:``);return Ae(_.call(e,0,t.maxStringLength),t)+r}var i=se[t.quoteStyle||`single`];return i.lastIndex=0,ce(v.call(v.call(e,i,`\\$1`),/[\x00-\x1f]/g,je),`single`,t)}function je(e){var t=e.charCodeAt(0),n={8:`b`,9:`t`,10:`n`,12:`f`,13:`r`}[t];return n?`\\`+n:`\\x`+(t<16?`0`:``)+y.call(t.toString(16))}function Me(e){return`Object(`+e+`)`}function Ne(e){return e+` { ? }`}function Pe(e,t,n,r){var i=r?Le(n,r):C.call(n,`, `);return e+` (`+t+`) {`+i+`}`}function Fe(e){for(var t=0;t<e.length;t++)if(Ce(e[t],`
`)>=0)return!1;return!0}function Ie(e,t){var n;if(e.indent===`	`)n=`	`;else if(typeof e.indent==`number`&&e.indent>0)n=C.call(Array(e.indent+1),` `);else return null;return{base:n,prev:C.call(Array(t+1),n)}}function Le(e,t){if(e.length===0)return``;var n=`
`+t.prev+t.base;return n+C.call(e,`,`+n)+`
`+t.prev}function Re(e,t){var n=de(e),r=[];if(n){r.length=e.length;for(var i=0;i<e.length;i++)r[i]=be(e,i)?t(e[i],e):``}var a=typeof D==`function`?D(e):[],o;if(k){o={};for(var s=0;s<a.length;s++)o[`$`+a[s]]=a[s]}for(var c in e)be(e,c)&&(n&&String(Number(c))===c&&c<e.length||k&&o[`$`+c]instanceof Symbol||(x.call(/[^\w$]/,c)?r.push(t(c,e)+`: `+t(e[c],e)):r.push(c+`: `+t(e[c],e))));if(typeof D==`function`)for(var l=0;l<a.length;l++)ee.call(e,a[l])&&r.push(`[`+t(a[l])+`]: `+t(e[a[l]],e));return r}})),Kv=c(((e,t)=>{var n=Gv(),r=Uv(),i=function(e,t,n){for(var r=e,i;(i=r.next)!=null;r=i)if(i.key===t)return r.next=i.next,n||(i.next=e.next,e.next=i),i},a=function(e,t){if(e){var n=i(e,t);return n&&n.value}},o=function(e,t,n){var r=i(e,t);r?r.value=n:e.next={key:t,next:e.next,value:n}},s=function(e,t){return e?!!i(e,t):!1},c=function(e,t){if(e)return i(e,t,!0)};t.exports=function(){var e,t={assert:function(e){if(!t.has(e))throw new r(`Side channel does not contain `+n(e))},delete:function(t){var n=e&&e.next,r=c(e,t);return r&&n&&n===r&&(e=void 0),!!r},get:function(t){return a(e,t)},has:function(t){return s(e,t)},set:function(t,n){e||={next:void 0},o(e,t,n)}};return t}})),qv=c(((e,t)=>{t.exports=Object})),Jv=c(((e,t)=>{t.exports=Error})),Yv=c(((e,t)=>{t.exports=EvalError})),Xv=c(((e,t)=>{t.exports=RangeError})),Zv=c(((e,t)=>{t.exports=ReferenceError})),Qv=c(((e,t)=>{t.exports=SyntaxError})),$v=c(((e,t)=>{t.exports=URIError})),ey=c(((e,t)=>{t.exports=Math.abs})),ty=c(((e,t)=>{t.exports=Math.floor})),ny=c(((e,t)=>{t.exports=Math.max})),ry=c(((e,t)=>{t.exports=Math.min})),iy=c(((e,t)=>{t.exports=Math.pow})),ay=c(((e,t)=>{t.exports=Math.round})),oy=c(((e,t)=>{t.exports=Number.isNaN||function(e){return e!==e}})),sy=c(((e,t)=>{var n=oy();t.exports=function(e){return n(e)||e===0?e:e<0?-1:1}})),cy=c(((e,t)=>{t.exports=Object.getOwnPropertyDescriptor})),ly=c(((e,t)=>{var n=cy();if(n)try{n([],`length`)}catch{n=null}t.exports=n})),uy=c(((e,t)=>{var n=Object.defineProperty||!1;if(n)try{n({},`a`,{value:1})}catch{n=!1}t.exports=n})),dy=c(((e,t)=>{t.exports=function(){if(typeof Symbol!=`function`||typeof Object.getOwnPropertySymbols!=`function`)return!1;if(typeof Symbol.iterator==`symbol`)return!0;var e={},t=Symbol(`test`),n=Object(t);if(typeof t==`string`||Object.prototype.toString.call(t)!==`[object Symbol]`||Object.prototype.toString.call(n)!==`[object Symbol]`)return!1;var r=42;for(var i in e[t]=r,e)return!1;if(typeof Object.keys==`function`&&Object.keys(e).length!==0||typeof Object.getOwnPropertyNames==`function`&&Object.getOwnPropertyNames(e).length!==0)return!1;var a=Object.getOwnPropertySymbols(e);if(a.length!==1||a[0]!==t||!Object.prototype.propertyIsEnumerable.call(e,t))return!1;if(typeof Object.getOwnPropertyDescriptor==`function`){var o=Object.getOwnPropertyDescriptor(e,t);if(o.value!==r||o.enumerable!==!0)return!1}return!0}})),fy=c(((e,t)=>{var n=typeof Symbol<`u`&&Symbol,r=dy();t.exports=function(){return typeof n!=`function`||typeof Symbol!=`function`||typeof n(`foo`)!=`symbol`||typeof Symbol(`bar`)!=`symbol`?!1:r()}})),py=c(((e,t)=>{t.exports=typeof Reflect<`u`&&Reflect.getPrototypeOf||null})),my=c(((e,t)=>{t.exports=qv().getPrototypeOf||null})),hy=c(((e,t)=>{var n=`Function.prototype.bind called on incompatible `,r=Object.prototype.toString,i=Math.max,a=`[object Function]`,o=function(e,t){for(var n=[],r=0;r<e.length;r+=1)n[r]=e[r];for(var i=0;i<t.length;i+=1)n[i+e.length]=t[i];return n},s=function(e,t){for(var n=[],r=t||0,i=0;r<e.length;r+=1,i+=1)n[i]=e[r];return n},c=function(e,t){for(var n=``,r=0;r<e.length;r+=1)n+=e[r],r+1<e.length&&(n+=t);return n};t.exports=function(e){var t=this;if(typeof t!=`function`||r.apply(t)!==a)throw TypeError(n+t);for(var l=s(arguments,1),u,d=function(){if(this instanceof u){var n=t.apply(this,o(l,arguments));return Object(n)===n?n:this}return t.apply(e,o(l,arguments))},f=i(0,t.length-l.length),p=[],m=0;m<f;m++)p[m]=`$`+m;if(u=Function(`binder`,`return function (`+c(p,`,`)+`){ return binder.apply(this,arguments); }`)(d),t.prototype){var h=function(){};h.prototype=t.prototype,u.prototype=new h,h.prototype=null}return u}})),gy=c(((e,t)=>{var n=hy();t.exports=Function.prototype.bind||n})),_y=c(((e,t)=>{t.exports=Function.prototype.call})),vy=c(((e,t)=>{t.exports=Function.prototype.apply})),yy=c(((e,t)=>{t.exports=typeof Reflect<`u`&&Reflect&&Reflect.apply})),by=c(((e,t)=>{var n=gy(),r=vy(),i=_y();t.exports=yy()||n.call(i,r)})),xy=c(((e,t)=>{var n=gy(),r=Uv(),i=_y(),a=by();t.exports=function(e){if(e.length<1||typeof e[0]!=`function`)throw new r(`a function is required`);return a(n,i,e)}})),Sy=c(((e,t)=>{var n=xy(),r=ly(),i;try{i=[].__proto__===Array.prototype}catch(e){if(!e||typeof e!=`object`||!(`code`in e)||e.code!==`ERR_PROTO_ACCESS`)throw e}var a=!!i&&r&&r(Object.prototype,`__proto__`),o=Object,s=o.getPrototypeOf;t.exports=a&&typeof a.get==`function`?n([a.get]):typeof s==`function`?function(e){return s(e==null?e:o(e))}:!1})),Cy=c(((e,t)=>{var n=py(),r=my(),i=Sy();t.exports=n?function(e){return n(e)}:r?function(e){if(!e||typeof e!=`object`&&typeof e!=`function`)throw TypeError(`getProto: not an object`);return r(e)}:i?function(e){return i(e)}:null})),wy=c(((e,t)=>{var n=Function.prototype.call,r=Object.prototype.hasOwnProperty;t.exports=gy().call(n,r)})),Ty=c(((e,t)=>{var n,r=qv(),i=Jv(),a=Yv(),o=Xv(),s=Zv(),c=Qv(),l=Uv(),u=$v(),d=ey(),f=ty(),p=ny(),m=ry(),h=iy(),g=ay(),_=sy(),v=Function,y=function(e){try{return v(`"use strict"; return (`+e+`).constructor;`)()}catch{}},b=ly(),x=uy(),S=function(){throw new l},C=b?function(){try{return arguments.callee,S}catch{try{return b(arguments,`callee`).get}catch{return S}}}():S,w=fy()(),T=Cy(),E=my(),D=py(),O=vy(),k=_y(),A={},ee=typeof Uint8Array>`u`||!T?n:T(Uint8Array),te={__proto__:null,"%AggregateError%":typeof AggregateError>`u`?n:AggregateError,"%Array%":Array,"%ArrayBuffer%":typeof ArrayBuffer>`u`?n:ArrayBuffer,"%ArrayIteratorPrototype%":w&&T?T([][Symbol.iterator]()):n,"%AsyncFromSyncIteratorPrototype%":n,"%AsyncFunction%":A,"%AsyncGenerator%":A,"%AsyncGeneratorFunction%":A,"%AsyncIteratorPrototype%":A,"%Atomics%":typeof Atomics>`u`?n:Atomics,"%BigInt%":typeof BigInt>`u`?n:BigInt,"%BigInt64Array%":typeof BigInt64Array>`u`?n:BigInt64Array,"%BigUint64Array%":typeof BigUint64Array>`u`?n:BigUint64Array,"%Boolean%":Boolean,"%DataView%":typeof DataView>`u`?n:DataView,"%Date%":Date,"%decodeURI%":decodeURI,"%decodeURIComponent%":decodeURIComponent,"%encodeURI%":encodeURI,"%encodeURIComponent%":encodeURIComponent,"%Error%":i,"%eval%":eval,"%EvalError%":a,"%Float16Array%":typeof Float16Array>`u`?n:Float16Array,"%Float32Array%":typeof Float32Array>`u`?n:Float32Array,"%Float64Array%":typeof Float64Array>`u`?n:Float64Array,"%FinalizationRegistry%":typeof FinalizationRegistry>`u`?n:FinalizationRegistry,"%Function%":v,"%GeneratorFunction%":A,"%Int8Array%":typeof Int8Array>`u`?n:Int8Array,"%Int16Array%":typeof Int16Array>`u`?n:Int16Array,"%Int32Array%":typeof Int32Array>`u`?n:Int32Array,"%isFinite%":isFinite,"%isNaN%":isNaN,"%IteratorPrototype%":w&&T?T(T([][Symbol.iterator]())):n,"%JSON%":typeof JSON==`object`?JSON:n,"%Map%":typeof Map>`u`?n:Map,"%MapIteratorPrototype%":typeof Map>`u`||!w||!T?n:T(new Map()[Symbol.iterator]()),"%Math%":Math,"%Number%":Number,"%Object%":r,"%Object.getOwnPropertyDescriptor%":b,"%parseFloat%":parseFloat,"%parseInt%":parseInt,"%Promise%":typeof Promise>`u`?n:Promise,"%Proxy%":typeof Proxy>`u`?n:Proxy,"%RangeError%":o,"%ReferenceError%":s,"%Reflect%":typeof Reflect>`u`?n:Reflect,"%RegExp%":RegExp,"%Set%":typeof Set>`u`?n:Set,"%SetIteratorPrototype%":typeof Set>`u`||!w||!T?n:T(new Set()[Symbol.iterator]()),"%SharedArrayBuffer%":typeof SharedArrayBuffer>`u`?n:SharedArrayBuffer,"%String%":String,"%StringIteratorPrototype%":w&&T?T(``[Symbol.iterator]()):n,"%Symbol%":w?Symbol:n,"%SyntaxError%":c,"%ThrowTypeError%":C,"%TypedArray%":ee,"%TypeError%":l,"%Uint8Array%":typeof Uint8Array>`u`?n:Uint8Array,"%Uint8ClampedArray%":typeof Uint8ClampedArray>`u`?n:Uint8ClampedArray,"%Uint16Array%":typeof Uint16Array>`u`?n:Uint16Array,"%Uint32Array%":typeof Uint32Array>`u`?n:Uint32Array,"%URIError%":u,"%WeakMap%":typeof WeakMap>`u`?n:WeakMap,"%WeakRef%":typeof WeakRef>`u`?n:WeakRef,"%WeakSet%":typeof WeakSet>`u`?n:WeakSet,"%Function.prototype.call%":k,"%Function.prototype.apply%":O,"%Object.defineProperty%":x,"%Object.getPrototypeOf%":E,"%Math.abs%":d,"%Math.floor%":f,"%Math.max%":p,"%Math.min%":m,"%Math.pow%":h,"%Math.round%":g,"%Math.sign%":_,"%Reflect.getPrototypeOf%":D};if(T)try{null.error}catch(e){te[`%Error.prototype%`]=T(T(e))}var ne=function e(t){var n;if(t===`%AsyncFunction%`)n=y(`async function () {}`);else if(t===`%GeneratorFunction%`)n=y(`function* () {}`);else if(t===`%AsyncGeneratorFunction%`)n=y(`async function* () {}`);else if(t===`%AsyncGenerator%`){var r=e(`%AsyncGeneratorFunction%`);r&&(n=r.prototype)}else if(t===`%AsyncIteratorPrototype%`){var i=e(`%AsyncGenerator%`);i&&T&&(n=T(i.prototype))}return te[t]=n,n},re={__proto__:null,"%ArrayBufferPrototype%":[`ArrayBuffer`,`prototype`],"%ArrayPrototype%":[`Array`,`prototype`],"%ArrayProto_entries%":[`Array`,`prototype`,`entries`],"%ArrayProto_forEach%":[`Array`,`prototype`,`forEach`],"%ArrayProto_keys%":[`Array`,`prototype`,`keys`],"%ArrayProto_values%":[`Array`,`prototype`,`values`],"%AsyncFunctionPrototype%":[`AsyncFunction`,`prototype`],"%AsyncGenerator%":[`AsyncGeneratorFunction`,`prototype`],"%AsyncGeneratorPrototype%":[`AsyncGeneratorFunction`,`prototype`,`prototype`],"%BooleanPrototype%":[`Boolean`,`prototype`],"%DataViewPrototype%":[`DataView`,`prototype`],"%DatePrototype%":[`Date`,`prototype`],"%ErrorPrototype%":[`Error`,`prototype`],"%EvalErrorPrototype%":[`EvalError`,`prototype`],"%Float32ArrayPrototype%":[`Float32Array`,`prototype`],"%Float64ArrayPrototype%":[`Float64Array`,`prototype`],"%FunctionPrototype%":[`Function`,`prototype`],"%Generator%":[`GeneratorFunction`,`prototype`],"%GeneratorPrototype%":[`GeneratorFunction`,`prototype`,`prototype`],"%Int8ArrayPrototype%":[`Int8Array`,`prototype`],"%Int16ArrayPrototype%":[`Int16Array`,`prototype`],"%Int32ArrayPrototype%":[`Int32Array`,`prototype`],"%JSONParse%":[`JSON`,`parse`],"%JSONStringify%":[`JSON`,`stringify`],"%MapPrototype%":[`Map`,`prototype`],"%NumberPrototype%":[`Number`,`prototype`],"%ObjectPrototype%":[`Object`,`prototype`],"%ObjProto_toString%":[`Object`,`prototype`,`toString`],"%ObjProto_valueOf%":[`Object`,`prototype`,`valueOf`],"%PromisePrototype%":[`Promise`,`prototype`],"%PromiseProto_then%":[`Promise`,`prototype`,`then`],"%Promise_all%":[`Promise`,`all`],"%Promise_reject%":[`Promise`,`reject`],"%Promise_resolve%":[`Promise`,`resolve`],"%RangeErrorPrototype%":[`RangeError`,`prototype`],"%ReferenceErrorPrototype%":[`ReferenceError`,`prototype`],"%RegExpPrototype%":[`RegExp`,`prototype`],"%SetPrototype%":[`Set`,`prototype`],"%SharedArrayBufferPrototype%":[`SharedArrayBuffer`,`prototype`],"%StringPrototype%":[`String`,`prototype`],"%SymbolPrototype%":[`Symbol`,`prototype`],"%SyntaxErrorPrototype%":[`SyntaxError`,`prototype`],"%TypedArrayPrototype%":[`TypedArray`,`prototype`],"%TypeErrorPrototype%":[`TypeError`,`prototype`],"%Uint8ArrayPrototype%":[`Uint8Array`,`prototype`],"%Uint8ClampedArrayPrototype%":[`Uint8ClampedArray`,`prototype`],"%Uint16ArrayPrototype%":[`Uint16Array`,`prototype`],"%Uint32ArrayPrototype%":[`Uint32Array`,`prototype`],"%URIErrorPrototype%":[`URIError`,`prototype`],"%WeakMapPrototype%":[`WeakMap`,`prototype`],"%WeakSetPrototype%":[`WeakSet`,`prototype`]},ie=gy(),ae=wy(),oe=ie.call(k,Array.prototype.concat),se=ie.call(O,Array.prototype.splice),ce=ie.call(k,String.prototype.replace),le=ie.call(k,String.prototype.slice),ue=ie.call(k,RegExp.prototype.exec),de=/[^%.[\]]+|\[(?:(-?\d+(?:\.\d+)?)|(["'])((?:(?!\2)[^\\]|\\.)*?)\2)\]|(?=(?:\.|\[\])(?:\.|\[\]|%$))/g,fe=/\\(\\)?/g,pe=function(e){var t=le(e,0,1),n=le(e,-1);if(t===`%`&&n!==`%`)throw new c("invalid intrinsic syntax, expected closing `%`");if(n===`%`&&t!==`%`)throw new c("invalid intrinsic syntax, expected opening `%`");var r=[];return ce(e,de,function(e,t,n,i){r[r.length]=n?ce(i,fe,`$1`):t||e}),r},me=function(e,t){var n=e,r;if(ae(re,n)&&(r=re[n],n=`%`+r[0]+`%`),ae(te,n)){var i=te[n];if(i===A&&(i=ne(n)),i===void 0&&!t)throw new l(`intrinsic `+e+` exists, but is not available. Please file an issue!`);return{alias:r,name:n,value:i}}throw new c(`intrinsic `+e+` does not exist!`)};t.exports=function(e,t){if(typeof e!=`string`||e.length===0)throw new l(`intrinsic name must be a non-empty string`);if(arguments.length>1&&typeof t!=`boolean`)throw new l(`"allowMissing" argument must be a boolean`);if(ue(/^%?[^%]*%?$/,e)===null)throw new c("`%` may not be present anywhere but at the beginning and end of the intrinsic name");var n=pe(e),r=n.length>0?n[0]:``,i=me(`%`+r+`%`,t),a=i.name,o=i.value,s=!1,u=i.alias;u&&(r=u[0],se(n,oe([0,1],u)));for(var d=1,f=!0;d<n.length;d+=1){var p=n[d],m=le(p,0,1),h=le(p,-1);if((m===`"`||m===`'`||m==="`"||h===`"`||h===`'`||h==="`")&&m!==h)throw new c(`property names with quotes must have matching quotes`);if((p===`constructor`||!f)&&(s=!0),r+=`.`+p,a=`%`+r+`%`,ae(te,a))o=te[a];else if(o!=null){if(!(p in o)){if(!t)throw new l(`base intrinsic for `+e+` exists, but the property is not available.`);return}if(b&&d+1>=n.length){var g=b(o,p);f=!!g,o=f&&`get`in g&&!(`originalValue`in g.get)?g.get:o[p]}else f=ae(o,p),o=o[p];f&&!s&&(te[a]=o)}}return o}})),Ey=c(((e,t)=>{var n=Ty(),r=xy(),i=r([n(`%String.prototype.indexOf%`)]);t.exports=function(e,t){var a=n(e,!!t);return typeof a==`function`&&i(e,`.prototype.`)>-1?r([a]):a}})),Dy=c(((e,t)=>{var n=Ty(),r=Ey(),i=Gv(),a=Uv(),o=n(`%Map%`,!0),s=r(`Map.prototype.get`,!0),c=r(`Map.prototype.set`,!0),l=r(`Map.prototype.has`,!0),u=r(`Map.prototype.delete`,!0),d=r(`Map.prototype.size`,!0);t.exports=!!o&&function(){var e,t={assert:function(e){if(!t.has(e))throw new a(`Side channel does not contain `+i(e))},delete:function(t){if(e){var n=u(e,t);return d(e)===0&&(e=void 0),n}return!1},get:function(t){if(e)return s(e,t)},has:function(t){return e?l(e,t):!1},set:function(t,n){e||=new o,c(e,t,n)}};return t}})),Oy=c(((e,t)=>{var n=Ty(),r=Ey(),i=Gv(),a=Dy(),o=Uv(),s=n(`%WeakMap%`,!0),c=r(`WeakMap.prototype.get`,!0),l=r(`WeakMap.prototype.set`,!0),u=r(`WeakMap.prototype.has`,!0),d=r(`WeakMap.prototype.delete`,!0);t.exports=s?function(){var e,t,n={assert:function(e){if(!n.has(e))throw new o(`Side channel does not contain `+i(e))},delete:function(n){if(s&&n&&(typeof n==`object`||typeof n==`function`)){if(e)return d(e,n)}else if(a&&t)return t.delete(n);return!1},get:function(n){return s&&n&&(typeof n==`object`||typeof n==`function`)&&e?c(e,n):t&&t.get(n)},has:function(n){return s&&n&&(typeof n==`object`||typeof n==`function`)&&e?u(e,n):!!t&&t.has(n)},set:function(n,r){s&&n&&(typeof n==`object`||typeof n==`function`)?(e||=new s,l(e,n,r)):a&&(t||=a(),t.set(n,r))}};return n}:a})),ky=c(((e,t)=>{var n=Uv(),r=Gv(),i=Kv(),a=Dy(),o=Oy()||a||i;t.exports=function(){var e,t={assert:function(e){if(!t.has(e))throw new n(`Side channel does not contain `+r(e))},delete:function(t){return!!e&&e.delete(t)},get:function(t){return e&&e.get(t)},has:function(t){return!!e&&e.has(t)},set:function(t,n){e||=o(),e.set(t,n)}};return t}})),Ay=c(((e,t)=>{var n=String.prototype.replace,r=/%20/g,i={RFC1738:`RFC1738`,RFC3986:`RFC3986`};t.exports={default:i.RFC3986,formatters:{RFC1738:function(e){return n.call(e,r,`+`)},RFC3986:function(e){return String(e)}},RFC1738:i.RFC1738,RFC3986:i.RFC3986}})),jy=c(((e,t)=>{var n=Ay(),r=ky(),i=Object.prototype.hasOwnProperty,a=Array.isArray,o=r(),s=function(e,t){return o.set(e,t),e},c=function(e){return o.has(e)},l=function(e){return o.get(e)},u=function(e,t){o.set(e,t)},d=function(){for(var e=[],t=0;t<256;++t)e.push(`%`+((t<16?`0`:``)+t.toString(16)).toUpperCase());return e}(),f=function(e){for(;e.length>1;){var t=e.pop(),n=t.obj[t.prop];if(a(n)){for(var r=[],i=0;i<n.length;++i)n[i]!==void 0&&r.push(n[i]);t.obj[t.prop]=r}}},p=function(e,t){for(var n=t&&t.plainObjects?{__proto__:null}:{},r=0;r<e.length;++r)e[r]!==void 0&&(n[r]=e[r]);return n},m=function e(t,n,r){if(!n)return t;if(typeof n!=`object`&&typeof n!=`function`){if(a(t))t.push(n);else if(t&&typeof t==`object`)if(c(t)){var o=l(t)+1;t[o]=n,u(t,o)}else (r&&(r.plainObjects||r.allowPrototypes)||!i.call(Object.prototype,n))&&(t[n]=!0);else return[t,n];return t}if(!t||typeof t!=`object`){if(c(n)){for(var d=Object.keys(n),f=r&&r.plainObjects?{__proto__:null,0:t}:{0:t},m=0;m<d.length;m++){var h=parseInt(d[m],10);f[h+1]=n[d[m]]}return s(f,l(n)+1)}return[t].concat(n)}var g=t;return a(t)&&!a(n)&&(g=p(t,r)),a(t)&&a(n)?(n.forEach(function(n,a){if(i.call(t,a)){var o=t[a];o&&typeof o==`object`&&n&&typeof n==`object`?t[a]=e(o,n,r):t.push(n)}else t[a]=n}),t):Object.keys(n).reduce(function(t,a){var o=n[a];return i.call(t,a)?t[a]=e(t[a],o,r):t[a]=o,t},g)},h=function(e,t){return Object.keys(t).reduce(function(e,n){return e[n]=t[n],e},e)},g=function(e,t,n){var r=e.replace(/\+/g,` `);if(n===`iso-8859-1`)return r.replace(/%[0-9a-f]{2}/gi,unescape);try{return decodeURIComponent(r)}catch{return r}},_=1024;t.exports={arrayToObject:p,assign:h,combine:function(e,t,n,r){if(c(e)){var i=l(e)+1;return e[i]=t,u(e,i),e}var a=[].concat(e,t);return a.length>n?s(p(a,{plainObjects:r}),a.length-1):a},compact:function(e){for(var t=[{obj:{o:e},prop:`o`}],n=[],r=0;r<t.length;++r)for(var i=t[r],a=i.obj[i.prop],o=Object.keys(a),s=0;s<o.length;++s){var c=o[s],l=a[c];typeof l==`object`&&l&&n.indexOf(l)===-1&&(t.push({obj:a,prop:c}),n.push(l))}return f(t),e},decode:g,encode:function(e,t,r,i,a){if(e.length===0)return e;var o=e;if(typeof e==`symbol`?o=Symbol.prototype.toString.call(e):typeof e!=`string`&&(o=String(e)),r===`iso-8859-1`)return escape(o).replace(/%u[0-9a-f]{4}/gi,function(e){return`%26%23`+parseInt(e.slice(2),16)+`%3B`});for(var s=``,c=0;c<o.length;c+=_){for(var l=o.length>=_?o.slice(c,c+_):o,u=[],f=0;f<l.length;++f){var p=l.charCodeAt(f);if(p===45||p===46||p===95||p===126||p>=48&&p<=57||p>=65&&p<=90||p>=97&&p<=122||a===n.RFC1738&&(p===40||p===41)){u[u.length]=l.charAt(f);continue}if(p<128){u[u.length]=d[p];continue}if(p<2048){u[u.length]=d[192|p>>6]+d[128|p&63];continue}if(p<55296||p>=57344){u[u.length]=d[224|p>>12]+d[128|p>>6&63]+d[128|p&63];continue}f+=1,p=65536+((p&1023)<<10|l.charCodeAt(f)&1023),u[u.length]=d[240|p>>18]+d[128|p>>12&63]+d[128|p>>6&63]+d[128|p&63]}s+=u.join(``)}return s},isBuffer:function(e){return!e||typeof e!=`object`?!1:!!(e.constructor&&e.constructor.isBuffer&&e.constructor.isBuffer(e))},isOverflow:c,isRegExp:function(e){return Object.prototype.toString.call(e)===`[object RegExp]`},maybeMap:function(e,t){if(a(e)){for(var n=[],r=0;r<e.length;r+=1)n.push(t(e[r]));return n}return t(e)},merge:m}})),My=c(((e,t)=>{var n=ky(),r=jy(),i=Ay(),a=Object.prototype.hasOwnProperty,o={brackets:function(e){return e+`[]`},comma:`comma`,indices:function(e,t){return e+`[`+t+`]`},repeat:function(e){return e}},s=Array.isArray,c=Array.prototype.push,l=function(e,t){c.apply(e,s(t)?t:[t])},u=Date.prototype.toISOString,d=i.default,f={addQueryPrefix:!1,allowDots:!1,allowEmptyArrays:!1,arrayFormat:`indices`,charset:`utf-8`,charsetSentinel:!1,commaRoundTrip:!1,delimiter:`&`,encode:!0,encodeDotInKeys:!1,encoder:r.encode,encodeValuesOnly:!1,filter:void 0,format:d,formatter:i.formatters[d],indices:!1,serializeDate:function(e){return u.call(e)},skipNulls:!1,strictNullHandling:!1},p=function(e){return typeof e==`string`||typeof e==`number`||typeof e==`boolean`||typeof e==`symbol`||typeof e==`bigint`},m={},h=function e(t,i,a,o,c,u,d,h,g,_,v,y,b,x,S,C,w,T){for(var E=t,D=T,O=0,k=!1;(D=D.get(m))!==void 0&&!k;){var A=D.get(t);if(O+=1,A!==void 0){if(A===O)throw RangeError(`Cyclic object value`);k=!0}D.get(m)===void 0&&(O=0)}if(typeof _==`function`?E=_(i,E):E instanceof Date?E=b(E):a===`comma`&&s(E)&&(E=r.maybeMap(E,function(e){return e instanceof Date?b(e):e})),E===null){if(u)return g&&!C?g(i,f.encoder,w,`key`,x):i;E=``}if(p(E)||r.isBuffer(E))return g?[S(C?i:g(i,f.encoder,w,`key`,x))+`=`+S(g(E,f.encoder,w,`value`,x))]:[S(i)+`=`+S(String(E))];var ee=[];if(E===void 0)return ee;var te;if(a===`comma`&&s(E))C&&g&&(E=r.maybeMap(E,g)),te=[{value:E.length>0?E.join(`,`)||null:void 0}];else if(s(_))te=_;else{var ne=Object.keys(E);te=v?ne.sort(v):ne}var re=h?String(i).replace(/\./g,`%2E`):String(i),ie=o&&s(E)&&E.length===1?re+`[]`:re;if(c&&s(E)&&E.length===0)return ie+`[]`;for(var ae=0;ae<te.length;++ae){var oe=te[ae],se=typeof oe==`object`&&oe&&oe.value!==void 0?oe.value:E[oe];if(!(d&&se===null)){var ce=y&&h?String(oe).replace(/\./g,`%2E`):String(oe),le=s(E)?typeof a==`function`?a(ie,ce):ie:ie+(y?`.`+ce:`[`+ce+`]`);T.set(t,O);var ue=n();ue.set(m,T),l(ee,e(se,le,a,o,c,u,d,h,a===`comma`&&C&&s(E)?null:g,_,v,y,b,x,S,C,w,ue))}}return ee},g=function(e){if(!e)return f;if(e.allowEmptyArrays!==void 0&&typeof e.allowEmptyArrays!=`boolean`)throw TypeError("`allowEmptyArrays` option can only be `true` or `false`, when provided");if(e.encodeDotInKeys!==void 0&&typeof e.encodeDotInKeys!=`boolean`)throw TypeError("`encodeDotInKeys` option can only be `true` or `false`, when provided");if(e.encoder!==null&&e.encoder!==void 0&&typeof e.encoder!=`function`)throw TypeError(`Encoder has to be a function.`);var t=e.charset||f.charset;if(e.charset!==void 0&&e.charset!==`utf-8`&&e.charset!==`iso-8859-1`)throw TypeError(`The charset option must be either utf-8, iso-8859-1, or undefined`);var n=i.default;if(e.format!==void 0){if(!a.call(i.formatters,e.format))throw TypeError(`Unknown format option provided.`);n=e.format}var r=i.formatters[n],c=f.filter;(typeof e.filter==`function`||s(e.filter))&&(c=e.filter);var l=e.arrayFormat in o?e.arrayFormat:`indices`in e?e.indices?`indices`:`repeat`:f.arrayFormat;if(`commaRoundTrip`in e&&typeof e.commaRoundTrip!=`boolean`)throw TypeError("`commaRoundTrip` must be a boolean, or absent");var u=e.allowDots===void 0?e.encodeDotInKeys===!0?!0:f.allowDots:!!e.allowDots;return{addQueryPrefix:typeof e.addQueryPrefix==`boolean`?e.addQueryPrefix:f.addQueryPrefix,allowDots:u,allowEmptyArrays:typeof e.allowEmptyArrays==`boolean`?!!e.allowEmptyArrays:f.allowEmptyArrays,arrayFormat:l,charset:t,charsetSentinel:typeof e.charsetSentinel==`boolean`?e.charsetSentinel:f.charsetSentinel,commaRoundTrip:!!e.commaRoundTrip,delimiter:e.delimiter===void 0?f.delimiter:e.delimiter,encode:typeof e.encode==`boolean`?e.encode:f.encode,encodeDotInKeys:typeof e.encodeDotInKeys==`boolean`?e.encodeDotInKeys:f.encodeDotInKeys,encoder:typeof e.encoder==`function`?e.encoder:f.encoder,encodeValuesOnly:typeof e.encodeValuesOnly==`boolean`?e.encodeValuesOnly:f.encodeValuesOnly,filter:c,format:n,formatter:r,serializeDate:typeof e.serializeDate==`function`?e.serializeDate:f.serializeDate,skipNulls:typeof e.skipNulls==`boolean`?e.skipNulls:f.skipNulls,sort:typeof e.sort==`function`?e.sort:null,strictNullHandling:typeof e.strictNullHandling==`boolean`?e.strictNullHandling:f.strictNullHandling}};t.exports=function(e,t){var r=e,i=g(t),a,c;typeof i.filter==`function`?(c=i.filter,r=c(``,r)):s(i.filter)&&(c=i.filter,a=c);var u=[];if(typeof r!=`object`||!r)return``;var d=o[i.arrayFormat],f=d===`comma`&&i.commaRoundTrip;a||=Object.keys(r),i.sort&&a.sort(i.sort);for(var p=n(),m=0;m<a.length;++m){var _=a[m],v=r[_];i.skipNulls&&v===null||l(u,h(v,_,d,f,i.allowEmptyArrays,i.strictNullHandling,i.skipNulls,i.encodeDotInKeys,i.encode?i.encoder:null,i.filter,i.sort,i.allowDots,i.serializeDate,i.format,i.formatter,i.encodeValuesOnly,i.charset,p))}var y=u.join(i.delimiter),b=i.addQueryPrefix===!0?`?`:``;return i.charsetSentinel&&(i.charset===`iso-8859-1`?b+=`utf8=%26%2310003%3B&`:b+=`utf8=%E2%9C%93&`),y.length>0?b+y:``}})),Ny=c(((e,t)=>{var n=jy(),r=Object.prototype.hasOwnProperty,i=Array.isArray,a={allowDots:!1,allowEmptyArrays:!1,allowPrototypes:!1,allowSparse:!1,arrayLimit:20,charset:`utf-8`,charsetSentinel:!1,comma:!1,decodeDotInKeys:!1,decoder:n.decode,delimiter:`&`,depth:5,duplicates:`combine`,ignoreQueryPrefix:!1,interpretNumericEntities:!1,parameterLimit:1e3,parseArrays:!0,plainObjects:!1,strictDepth:!1,strictNullHandling:!1,throwOnLimitExceeded:!1},o=function(e){return e.replace(/&#(\d+);/g,function(e,t){return String.fromCharCode(parseInt(t,10))})},s=function(e,t,n){if(e&&typeof e==`string`&&t.comma&&e.indexOf(`,`)>-1)return e.split(`,`);if(t.throwOnLimitExceeded&&n>=t.arrayLimit)throw RangeError(`Array limit exceeded. Only `+t.arrayLimit+` element`+(t.arrayLimit===1?``:`s`)+` allowed in an array.`);return e},c=`utf8=%26%2310003%3B`,l=`utf8=%E2%9C%93`,u=function(e,t){var u={__proto__:null},d=t.ignoreQueryPrefix?e.replace(/^\?/,``):e;d=d.replace(/%5B/gi,`[`).replace(/%5D/gi,`]`);var f=t.parameterLimit===1/0?void 0:t.parameterLimit,p=d.split(t.delimiter,t.throwOnLimitExceeded?f+1:f);if(t.throwOnLimitExceeded&&p.length>f)throw RangeError(`Parameter limit exceeded. Only `+f+` parameter`+(f===1?``:`s`)+` allowed.`);var m=-1,h,g=t.charset;if(t.charsetSentinel)for(h=0;h<p.length;++h)p[h].indexOf(`utf8=`)===0&&(p[h]===l?g=`utf-8`:p[h]===c&&(g=`iso-8859-1`),m=h,h=p.length);for(h=0;h<p.length;++h)if(h!==m){var _=p[h],v=_.indexOf(`]=`),y=v===-1?_.indexOf(`=`):v+1,b,x;if(y===-1?(b=t.decoder(_,a.decoder,g,`key`),x=t.strictNullHandling?null:``):(b=t.decoder(_.slice(0,y),a.decoder,g,`key`),b!==null&&(x=n.maybeMap(s(_.slice(y+1),t,i(u[b])?u[b].length:0),function(e){return t.decoder(e,a.decoder,g,`value`)}))),x&&t.interpretNumericEntities&&g===`iso-8859-1`&&(x=o(String(x))),_.indexOf(`[]=`)>-1&&(x=i(x)?[x]:x),b!==null){var S=r.call(u,b);S&&t.duplicates===`combine`?u[b]=n.combine(u[b],x,t.arrayLimit,t.plainObjects):(!S||t.duplicates===`last`)&&(u[b]=x)}}return u},d=function(e,t,r,i){var a=0;if(e.length>0&&e[e.length-1]===`[]`){var o=e.slice(0,-1).join(``);a=Array.isArray(t)&&t[o]?t[o].length:0}for(var c=i?t:s(t,r,a),l=e.length-1;l>=0;--l){var u,d=e[l];if(d===`[]`&&r.parseArrays)u=n.isOverflow(c)?c:r.allowEmptyArrays&&(c===``||r.strictNullHandling&&c===null)?[]:n.combine([],c,r.arrayLimit,r.plainObjects);else{u=r.plainObjects?{__proto__:null}:{};var f=d.charAt(0)===`[`&&d.charAt(d.length-1)===`]`?d.slice(1,-1):d,p=r.decodeDotInKeys?f.replace(/%2E/g,`.`):f,m=parseInt(p,10);!r.parseArrays&&p===``?u={0:c}:!isNaN(m)&&d!==p&&String(m)===p&&m>=0&&r.parseArrays&&m<=r.arrayLimit?(u=[],u[m]=c):p!==`__proto__`&&(u[p]=c)}c=u}return c},f=function(e,t){var n=t.allowDots?e.replace(/\.([^.[]+)/g,`[$1]`):e;if(t.depth<=0)return!t.plainObjects&&r.call(Object.prototype,n)&&!t.allowPrototypes?void 0:[n];var i=/(\[[^[\]]*])/,a=/(\[[^[\]]*])/g,o=i.exec(n),s=o?n.slice(0,o.index):n,c=[];if(s){if(!t.plainObjects&&r.call(Object.prototype,s)&&!t.allowPrototypes)return;c.push(s)}for(var l=0;(o=a.exec(n))!==null&&l<t.depth;){l+=1;var u=o[1].slice(1,-1);if(!t.plainObjects&&r.call(Object.prototype,u)&&!t.allowPrototypes)return;c.push(o[1])}if(o){if(t.strictDepth===!0)throw RangeError(`Input depth exceeded depth option of `+t.depth+` and strictDepth is true`);c.push(`[`+n.slice(o.index)+`]`)}return c},p=function(e,t,n,r){if(e){var i=f(e,n);if(i)return d(i,t,n,r)}},m=function(e){if(!e)return a;if(e.allowEmptyArrays!==void 0&&typeof e.allowEmptyArrays!=`boolean`)throw TypeError("`allowEmptyArrays` option can only be `true` or `false`, when provided");if(e.decodeDotInKeys!==void 0&&typeof e.decodeDotInKeys!=`boolean`)throw TypeError("`decodeDotInKeys` option can only be `true` or `false`, when provided");if(e.decoder!==null&&e.decoder!==void 0&&typeof e.decoder!=`function`)throw TypeError(`Decoder has to be a function.`);if(e.charset!==void 0&&e.charset!==`utf-8`&&e.charset!==`iso-8859-1`)throw TypeError(`The charset option must be either utf-8, iso-8859-1, or undefined`);if(e.throwOnLimitExceeded!==void 0&&typeof e.throwOnLimitExceeded!=`boolean`)throw TypeError("`throwOnLimitExceeded` option must be a boolean");var t=e.charset===void 0?a.charset:e.charset,r=e.duplicates===void 0?a.duplicates:e.duplicates;if(r!==`combine`&&r!==`first`&&r!==`last`)throw TypeError(`The duplicates option must be either combine, first, or last`);return{allowDots:e.allowDots===void 0?e.decodeDotInKeys===!0?!0:a.allowDots:!!e.allowDots,allowEmptyArrays:typeof e.allowEmptyArrays==`boolean`?!!e.allowEmptyArrays:a.allowEmptyArrays,allowPrototypes:typeof e.allowPrototypes==`boolean`?e.allowPrototypes:a.allowPrototypes,allowSparse:typeof e.allowSparse==`boolean`?e.allowSparse:a.allowSparse,arrayLimit:typeof e.arrayLimit==`number`?e.arrayLimit:a.arrayLimit,charset:t,charsetSentinel:typeof e.charsetSentinel==`boolean`?e.charsetSentinel:a.charsetSentinel,comma:typeof e.comma==`boolean`?e.comma:a.comma,decodeDotInKeys:typeof e.decodeDotInKeys==`boolean`?e.decodeDotInKeys:a.decodeDotInKeys,decoder:typeof e.decoder==`function`?e.decoder:a.decoder,delimiter:typeof e.delimiter==`string`||n.isRegExp(e.delimiter)?e.delimiter:a.delimiter,depth:typeof e.depth==`number`||e.depth===!1?+e.depth:a.depth,duplicates:r,ignoreQueryPrefix:e.ignoreQueryPrefix===!0,interpretNumericEntities:typeof e.interpretNumericEntities==`boolean`?e.interpretNumericEntities:a.interpretNumericEntities,parameterLimit:typeof e.parameterLimit==`number`?e.parameterLimit:a.parameterLimit,parseArrays:e.parseArrays!==!1,plainObjects:typeof e.plainObjects==`boolean`?e.plainObjects:a.plainObjects,strictDepth:typeof e.strictDepth==`boolean`?!!e.strictDepth:a.strictDepth,strictNullHandling:typeof e.strictNullHandling==`boolean`?e.strictNullHandling:a.strictNullHandling,throwOnLimitExceeded:typeof e.throwOnLimitExceeded==`boolean`?e.throwOnLimitExceeded:!1}};t.exports=function(e,t){var r=m(t);if(e===``||e==null)return r.plainObjects?{__proto__:null}:{};for(var i=typeof e==`string`?u(e,r):e,a=r.plainObjects?{__proto__:null}:{},o=Object.keys(i),s=0;s<o.length;++s){var c=o[s],l=p(c,i[c],r,typeof e==`string`);a=n.merge(a,l,r)}return r.allowSparse===!0?a:n.compact(a)}})),Py=i(c(((e,t)=>{var n=My(),r=Ny();t.exports={formats:Ay(),parse:r,stringify:n}}))(),1),Fy=new class{constructor(e){this.config={},this.defaults=e}extend(e){return e&&(this.defaults={...this.defaults,...e}),this}replace(e){this.config=e}get(e){return Av(this.config,e)?rh(this.config,e):rh(this.defaults,e)}set(e,t){typeof e==`string`?Hv(this.config,e,t):Object.entries(e).forEach(([e,t])=>{Hv(this.config,e,t)})}}({form:{recentlySuccessfulDuration:2e3,forceIndicesArrayFormatInFormData:!0},future:{preserveEqualProps:!1,useDataInertiaHeadAttribute:!1,useDialogForErrorModal:!1,useScriptElementForInitialPage:!1},prefetch:{cacheFor:3e4,hoverDelay:75}});function Iy(e,t){let n;return function(...r){clearTimeout(n),n=setTimeout(()=>e.apply(this,r),t)}}function Ly(e,t){return document.dispatchEvent(new CustomEvent(`inertia:${e}`,t))}var Ry=e=>Ly(`before`,{cancelable:!0,detail:{visit:e}}),zy=e=>Ly(`error`,{detail:{errors:e}}),By=e=>Ly(`exception`,{cancelable:!0,detail:{exception:e}}),Vy=e=>Ly(`finish`,{detail:{visit:e}}),Hy=e=>Ly(`invalid`,{cancelable:!0,detail:{response:e}}),Uy=e=>Ly(`beforeUpdate`,{detail:{page:e}}),Wy=e=>Ly(`navigate`,{detail:{page:e}}),Gy=e=>Ly(`progress`,{detail:{progress:e}}),Ky=e=>Ly(`start`,{detail:{visit:e}}),qy=e=>Ly(`success`,{detail:{page:e}}),Jy=(e,t)=>Ly(`prefetched`,{detail:{fetchedAt:Date.now(),response:e.data,visit:t}}),Yy=e=>Ly(`prefetching`,{detail:{visit:e}}),Xy=e=>Ly(`flash`,{detail:{flash:e}}),Zy=class{static set(e,t){typeof window<`u`&&window.sessionStorage.setItem(e,JSON.stringify(t))}static get(e){if(typeof window<`u`)return JSON.parse(window.sessionStorage.getItem(e)||`null`)}static merge(e,t){let n=this.get(e);n===null?this.set(e,t):this.set(e,{...n,...t})}static remove(e){typeof window<`u`&&window.sessionStorage.removeItem(e)}static removeNested(e,t){let n=this.get(e);n!==null&&(delete n[t],this.set(e,n))}static exists(e){try{return this.get(e)!==null}catch{return!1}}static clear(){typeof window<`u`&&window.sessionStorage.clear()}};Zy.locationVisitKey=`inertiaLocationVisit`;var Qy=async e=>{if(typeof window>`u`)throw Error(`Unable to encrypt history`);let t=rb(),n=await ob(await sb());if(!n)throw Error(`Unable to encrypt history`);return await tb(t,n,e)},$y={key:`historyKey`,iv:`historyIv`},eb=async e=>{let t=rb(),n=await sb();if(!n)throw Error(`Unable to decrypt history`);return await nb(t,n,e)},tb=async(e,t,n)=>{if(typeof window>`u`)throw Error(`Unable to encrypt history`);if(window.crypto.subtle===void 0)return console.warn(`Encryption is not supported in this environment. SSL is required.`),Promise.resolve(n);let r=new TextEncoder,i=JSON.stringify(n),a=new Uint8Array(i.length*3),o=r.encodeInto(i,a);return window.crypto.subtle.encrypt({name:`AES-GCM`,iv:e},t,a.subarray(0,o.written))},nb=async(e,t,n)=>{if(window.crypto.subtle===void 0)return console.warn(`Decryption is not supported in this environment. SSL is required.`),Promise.resolve(n);let r=await window.crypto.subtle.decrypt({name:`AES-GCM`,iv:e},t,n);return JSON.parse(new TextDecoder().decode(r))},rb=()=>{let e=Zy.get($y.iv);if(e)return new Uint8Array(e);let t=window.crypto.getRandomValues(new Uint8Array(12));return Zy.set($y.iv,Array.from(t)),t},ib=async()=>window.crypto.subtle===void 0?(console.warn(`Encryption is not supported in this environment. SSL is required.`),Promise.resolve(null)):window.crypto.subtle.generateKey({name:`AES-GCM`,length:256},!0,[`encrypt`,`decrypt`]),ab=async e=>{if(window.crypto.subtle===void 0)return console.warn(`Encryption is not supported in this environment. SSL is required.`),Promise.resolve();let t=await window.crypto.subtle.exportKey(`raw`,e);Zy.set($y.key,Array.from(new Uint8Array(t)))},ob=async e=>{if(e)return e;let t=await ib();return t?(await ab(t),t):null},sb=async()=>{let e=Zy.get($y.key);return e?await window.crypto.subtle.importKey(`raw`,new Uint8Array(e),{name:`AES-GCM`,length:256},!0,[`encrypt`,`decrypt`]):null},cb=(e,t,n)=>{if(e===t)return!0;for(let r in e)if(!n.includes(r)&&e[r]!==t[r]&&!lb(e[r],t[r]))return!1;for(let r in t)if(!n.includes(r)&&!(r in e))return!1;return!0},lb=(e,t)=>{switch(typeof e){case`object`:return cb(e,t,[]);case`function`:return e.toString()===t.toString();default:return e===t}},ub={ms:1,s:1e3,m:1e3*60,h:1e3*60*60,d:1e3*60*60*24},db=e=>{if(typeof e==`number`)return e;for(let[t,n]of Object.entries(ub))if(e.endsWith(t))return parseFloat(e)*n;return parseInt(e)},fb=new class{constructor(){this.cached=[],this.inFlightRequests=[],this.removalTimers=[],this.currentUseId=null}add(e,t,{cacheFor:n,cacheTags:r}){if(this.findInFlight(e))return Promise.resolve();let i=this.findCached(e);if(!e.fresh&&i&&i.staleTimestamp>Date.now())return Promise.resolve();let[a,o]=this.extractStaleValues(n),s=new Promise((n,r)=>{t({...e,onCancel:()=>{this.remove(e),e.onCancel(),r()},onError:t=>{this.remove(e),e.onError(t),r()},onPrefetching(t){e.onPrefetching(t)},onPrefetched(t,n){e.onPrefetched(t,n)},onPrefetchResponse(e){n(e)},onPrefetchError(t){fb.removeFromInFlight(e),r(t)}})}).then(t=>{this.remove(e);let n=t.getPageResponse();U.mergeOncePropsIntoResponse(n),this.cached.push({params:{...e},staleTimestamp:Date.now()+a,expiresAt:Date.now()+o,response:s,singleUse:o===0,timestamp:Date.now(),inFlight:!1,tags:Array.isArray(r)?r:[r]});let i=this.getShortestOncePropTtl(n);return this.scheduleForRemoval(e,i?Math.min(o,i):o),this.removeFromInFlight(e),t.handlePrefetch(),t});return this.inFlightRequests.push({params:{...e},response:s,staleTimestamp:null,inFlight:!0}),s}removeAll(){this.cached=[],this.removalTimers.forEach(e=>{clearTimeout(e.timer)}),this.removalTimers=[]}removeByTags(e){this.cached=this.cached.filter(t=>!t.tags.some(t=>e.includes(t)))}remove(e){this.cached=this.cached.filter(t=>!this.paramsAreEqual(t.params,e)),this.clearTimer(e)}removeFromInFlight(e){this.inFlightRequests=this.inFlightRequests.filter(t=>!this.paramsAreEqual(t.params,e))}extractStaleValues(e){let[t,n]=this.cacheForToStaleAndExpires(e);return[db(t),db(n)]}cacheForToStaleAndExpires(e){if(!Array.isArray(e))return[e,e];switch(e.length){case 0:return[0,0];case 1:return[e[0],e[0]];default:return[e[0],e[1]]}}clearTimer(e){let t=this.removalTimers.find(t=>this.paramsAreEqual(t.params,e));t&&(clearTimeout(t.timer),this.removalTimers=this.removalTimers.filter(e=>e!==t))}scheduleForRemoval(e,t){if(!(typeof window>`u`)&&(this.clearTimer(e),t>0)){let n=window.setTimeout(()=>this.remove(e),t);this.removalTimers.push({params:e,timer:n})}}get(e){return this.findCached(e)||this.findInFlight(e)}use(e,t){let n=`${t.url.pathname}-${Date.now()}-${Math.random().toString(36).substring(7)}`;return this.currentUseId=n,e.response.then(e=>{if(this.currentUseId===n)return e.mergeParams({...t,onPrefetched:()=>{}}),this.removeSingleUseItems(t),e.handle()})}removeSingleUseItems(e){this.cached=this.cached.filter(t=>this.paramsAreEqual(t.params,e)?!t.singleUse:!0)}findCached(e){return this.cached.find(t=>this.paramsAreEqual(t.params,e))||null}findInFlight(e){return this.inFlightRequests.find(t=>this.paramsAreEqual(t.params,e))||null}withoutPurposePrefetchHeader(e){let t=T_(e);return t.headers.Purpose===`prefetch`&&delete t.headers.Purpose,t}paramsAreEqual(e,t){return cb(this.withoutPurposePrefetchHeader(e),this.withoutPurposePrefetchHeader(t),[`showProgress`,`replace`,`prefetch`,`preserveScroll`,`preserveState`,`onBefore`,`onBeforeUpdate`,`onStart`,`onProgress`,`onFinish`,`onCancel`,`onSuccess`,`onError`,`onFlash`,`onPrefetched`,`onCancelToken`,`onPrefetching`,`async`,`viewTransition`])}updateCachedOncePropsFromCurrentPage(){this.cached.forEach(e=>{e.response.then(t=>{let n=t.getPageResponse();U.mergeOncePropsIntoResponse(n,{force:!0});for(let[e,t]of Object.entries(n.deferredProps??{})){let r=t.filter(e=>n.props[e]===void 0);r.length>0?n.deferredProps[e]=r:delete n.deferredProps[e]}let r=this.getShortestOncePropTtl(n);if(r===null)return;let i=e.expiresAt-Date.now(),a=Math.min(i,r);a>0?this.scheduleForRemoval(e.params,a):this.remove(e.params)})})}getShortestOncePropTtl(e){let t=Object.values(e.onceProps??{}).map(e=>e.expiresAt).filter(e=>!!e);return t.length===0?null:Math.min(...t)-Date.now()}},pb=(e,t=1)=>{window.requestAnimationFrame(()=>{t>1?pb(e,t-1):e()})},mb=(e,t=!1)=>{if(typeof window>`u`)return null;if(!t){let t=document.getElementById(e);if(t?.dataset.page)return JSON.parse(t.dataset.page)}let n=document.querySelector(`script[data-page="${e}"][type="application/json"]`);return n?.textContent?JSON.parse(n.textContent):null},hb=typeof window>`u`,gb=!hb&&/Firefox/i.test(window.navigator.userAgent),_b=class{static save(){W.saveScrollPositions(this.getScrollRegions())}static getScrollRegions(){return Array.from(this.regions()).map(e=>({top:e.scrollTop,left:e.scrollLeft}))}static regions(){return document.querySelectorAll(`[scroll-region]`)}static scrollToTop(){if(gb&&getComputedStyle(document.documentElement).scrollBehavior===`smooth`)return pb(()=>window.scrollTo(0,0),2);window.scrollTo(0,0)}static reset(){!hb&&window.location.hash||this.scrollToTop(),this.regions().forEach(e=>{typeof e.scrollTo==`function`?e.scrollTo(0,0):(e.scrollTop=0,e.scrollLeft=0)}),this.save(),this.scrollToAnchor()}static scrollToAnchor(){let e=hb?null:window.location.hash;e&&setTimeout(()=>{let t=document.getElementById(e.slice(1));t?t.scrollIntoView():this.scrollToTop()})}static restore(e){hb||window.requestAnimationFrame(()=>{this.restoreDocument(),this.restoreScrollRegions(e)})}static restoreScrollRegions(e){hb||this.regions().forEach((t,n)=>{let r=e[n];r&&(typeof t.scrollTo==`function`?t.scrollTo(r.left,r.top):(t.scrollTop=r.top,t.scrollLeft=r.left))})}static restoreDocument(){let e=W.getDocumentScrollPosition();window.scrollTo(e.left,e.top)}static onScroll(e){let t=e.target;typeof t.hasAttribute==`function`&&t.hasAttribute(`scroll-region`)&&this.save()}static onWindowScroll(){W.saveDocumentScrollPosition({top:window.scrollY,left:window.scrollX})}},vb=e=>typeof File<`u`&&e instanceof File||e instanceof Blob||typeof FileList<`u`&&e instanceof FileList&&e.length>0;function yb(e){return vb(e)||e instanceof FormData&&Array.from(e.values()).some(e=>yb(e))||typeof e==`object`&&!!e&&Object.values(e).some(e=>yb(e))}var bb=e=>e instanceof FormData;function xb(e,t=new FormData,n=null,r=`brackets`){e||={};for(let i in e)Object.prototype.hasOwnProperty.call(e,i)&&Cb(t,Sb(n,i,`indices`),e[i],r);return t}function Sb(e,t,n){return e?n===`brackets`?`${e}[]`:`${e}[${t}]`:t}function Cb(e,t,n,r){if(Array.isArray(n))return Array.from(n.keys()).forEach(i=>Cb(e,Sb(t,i.toString(),r),n[i],r));if(n instanceof Date)return e.append(t,n.toISOString());if(n instanceof File)return e.append(t,n,n.name);if(n instanceof Blob)return e.append(t,n);if(typeof n==`boolean`)return e.append(t,n?`1`:`0`);if(typeof n==`string`)return e.append(t,n);if(typeof n==`number`)return e.append(t,`${n}`);if(n==null)return e.append(t,``);xb(n,e,t,r)}function wb(e){return new URL(e.toString(),typeof window>`u`?void 0:window.location.toString())}var Tb=(e,t,n,r,i)=>{let a=typeof e==`string`?wb(e):e;if((yb(t)||r)&&!bb(t)&&(Fy.get(`form.forceIndicesArrayFormatInFormData`)&&(i=`indices`),t=xb(t,new FormData,null,i)),bb(t))return[a,t];let[o,s]=Eb(n,a,t,i);return[wb(o),s]};function Eb(e,t,n,r=`brackets`){let i=e===`get`&&!bb(n)&&Object.keys(n).length>0,a=Mb(t.toString()),o=a||t.toString().startsWith(`/`)||t.toString()===``,s=!o&&!t.toString().startsWith(`#`)&&!t.toString().startsWith(`?`),c=/^[.]{1,2}([/]|$)/.test(t.toString()),l=t.toString().includes(`?`)||i,u=t.toString().includes(`#`),d=new URL(t.toString(),typeof window>`u`?`http://localhost`:window.location.toString());if(i){let e=/\[\d+\]/.test(decodeURIComponent(d.search));d.search=Py.stringify({...Py.parse(d.search,{ignoreQueryPrefix:!0,allowSparse:!0}),...n},{encodeValuesOnly:!0,arrayFormat:e?`indices`:r})}return[[a?`${d.protocol}//${d.host}`:``,o?d.pathname:``,s?d.pathname.substring(c?0:1):``,l?d.search:``,u?d.hash:``].join(``),i?{}:n]}function Db(e){return e=new URL(e.href),e.hash=``,e}var Ob=(e,t)=>{e.hash&&!t.hash&&Db(e).href===t.href&&(t.hash=e.hash)},kb=(e,t)=>Db(e).href===Db(t).href,Ab=(e,t)=>e.origin===t.origin&&e.pathname===t.pathname;function jb(e){return typeof e==`object`&&!!e&&e!==void 0&&`url`in e&&`method`in e}function Mb(e){return/^([a-z][a-z0-9+.-]*:)?\/\/[^/]/i.test(e)}var U=new class{constructor(){this.componentId={},this.listeners=[],this.isFirstPageLoad=!0,this.cleared=!1,this.pendingDeferredProps=null,this.historyQuotaExceeded=!1}init({initialPage:e,swapComponent:t,resolveComponent:n,onFlash:r}){return this.page={...e,flash:e.flash??{}},this.swapComponent=t,this.resolveComponent=n,this.onFlashCallback=r,Rb.on(`historyQuotaExceeded`,()=>{this.historyQuotaExceeded=!0}),this}set(e,{replace:t=!1,preserveScroll:n=!1,preserveState:r=!1,viewTransition:i=!1}={}){Object.keys(e.deferredProps||{}).length&&(this.pendingDeferredProps={deferredProps:e.deferredProps,component:e.component,url:e.url},e.initialDeferredProps===void 0&&(e.initialDeferredProps=e.deferredProps)),this.componentId={};let a=this.componentId;return e.clearHistory&&W.clear(),this.resolve(e.component).then(o=>{if(a!==this.componentId)return;e.rememberedState??={};let s=typeof window>`u`,c=s?new URL(e.url):window.location,l=!s&&n?_b.getScrollRegions():[];t||=kb(wb(e.url),c);let u={...e,flash:{}};return new Promise(e=>t?W.replaceState(u,e):W.pushState(u,e)).then(()=>{let a=!this.isTheSame(e);if(!a&&Object.keys(e.props.errors||{}).length>0&&(i=!1),this.page=e,this.cleared=!1,this.hasOnceProps()&&fb.updateCachedOncePropsFromCurrentPage(),a&&this.fireEventsFor(`newComponent`),this.isFirstPageLoad&&this.fireEventsFor(`firstLoad`),this.isFirstPageLoad=!1,this.historyQuotaExceeded){this.historyQuotaExceeded=!1;return}return this.swap({component:o,page:e,preserveState:r,viewTransition:i}).then(()=>{n?window.requestAnimationFrame(()=>_b.restoreScrollRegions(l)):_b.reset(),this.pendingDeferredProps&&this.pendingDeferredProps.component===e.component&&this.pendingDeferredProps.url===e.url&&Rb.fireInternalEvent(`loadDeferredProps`,this.pendingDeferredProps.deferredProps),this.pendingDeferredProps=null,t||Wy(e)})})})}setQuietly(e,{preserveState:t=!1}={}){return this.resolve(e.component).then(n=>(this.page=e,this.cleared=!1,W.setCurrent(e),this.swap({component:n,page:e,preserveState:t,viewTransition:!1})))}clear(){this.cleared=!0}isCleared(){return this.cleared}get(){return this.page}getWithoutFlashData(){return{...this.page,flash:{}}}hasOnceProps(){return Object.keys(this.page.onceProps??{}).length>0}merge(e){this.page={...this.page,...e}}setFlash(e){this.page={...this.page,flash:e},this.onFlashCallback?.(e)}setUrlHash(e){this.page.url.includes(e)||(this.page.url+=e)}remember(e){this.page.rememberedState=e}swap({component:e,page:t,preserveState:n,viewTransition:r}){let i=()=>this.swapComponent({component:e,page:t,preserveState:n});if(!r||!document?.startViewTransition)return i();let a=typeof r==`boolean`?()=>null:r;return new Promise(e=>{a(document.startViewTransition(()=>i().then(e)))})}resolve(e){return Promise.resolve(this.resolveComponent(e))}isTheSame(e){return this.page.component===e.component}on(e,t){return this.listeners.push({event:e,callback:t}),()=>{this.listeners=this.listeners.filter(n=>n.event!==e&&n.callback!==t)}}fireEventsFor(e){this.listeners.filter(t=>t.event===e).forEach(e=>e.callback())}mergeOncePropsIntoResponse(e,{force:t=!1}={}){Object.entries(e.onceProps??{}).forEach(([n,r])=>{let i=this.page.onceProps?.[n];i!==void 0&&(t||e.props[r.prop]===void 0)&&(e.props[r.prop]=this.page.props[i.prop],e.onceProps[n].expiresAt=i.expiresAt)})}},Nb=class{constructor(){this.items=[],this.processingPromise=null}add(e){return this.items.push(e),this.process()}process(){return this.processingPromise??=this.processNext().finally(()=>{this.processingPromise=null}),this.processingPromise}processNext(){let e=this.items.shift();return e?Promise.resolve(e()).then(()=>this.processNext()):Promise.resolve()}},Pb=typeof window>`u`,Fb=new Nb,Ib=!Pb&&/CriOS/.test(window.navigator.userAgent),Lb=class{constructor(){this.rememberedState=`rememberedState`,this.scrollRegions=`scrollRegions`,this.preserveUrl=!1,this.current={},this.initialState=null}remember(e,t){this.replaceState({...U.getWithoutFlashData(),rememberedState:{...U.get()?.rememberedState??{},[t]:e}})}restore(e){if(!Pb)return this.current[this.rememberedState]?.[e]===void 0?this.initialState?.[this.rememberedState]?.[e]:this.current[this.rememberedState]?.[e]}pushState(e,t=null){if(!Pb){if(this.preserveUrl){t&&t();return}this.current=e,Fb.add(()=>this.getPageData(e).then(n=>{let r=()=>this.doPushState({page:n},e.url).then(()=>t?.());return Ib?new Promise(e=>{setTimeout(()=>r().then(e))}):r()}))}}clonePageProps(e){try{return structuredClone(e.props),e}catch{return{...e,props:T_(e.props)}}}getPageData(e){let t=this.clonePageProps(e);return new Promise(n=>e.encryptHistory?Qy(t).then(n):n(t))}processQueue(){return Fb.process()}decrypt(e=null){if(Pb)return Promise.resolve(e??U.get());let t=e??window.history.state?.page;return this.decryptPageData(t).then(e=>{if(!e)throw Error(`Unable to decrypt history`);return this.initialState===null?this.initialState=e??void 0:this.current=e??{},e})}decryptPageData(e){return e instanceof ArrayBuffer?eb(e):Promise.resolve(e)}saveScrollPositions(e){Fb.add(()=>Promise.resolve().then(()=>{if(window.history.state?.page&&!Mv(this.getScrollRegions(),e))return this.doReplaceState({page:window.history.state.page,scrollRegions:e})}))}saveDocumentScrollPosition(e){Fb.add(()=>Promise.resolve().then(()=>{if(window.history.state?.page&&!Mv(this.getDocumentScrollPosition(),e))return this.doReplaceState({page:window.history.state.page,documentScrollPosition:e})}))}getScrollRegions(){return window.history.state?.scrollRegions||[]}getDocumentScrollPosition(){return window.history.state?.documentScrollPosition||{top:0,left:0}}replaceState(e,t=null){if(Mv(this.current,e)){t&&t();return}if(U.merge(e),!Pb){if(this.preserveUrl){t&&t();return}this.current=e,Fb.add(()=>this.getPageData(e).then(n=>{let r=()=>this.doReplaceState({page:n},e.url).then(()=>t?.());return Ib?new Promise(e=>{setTimeout(()=>r().then(e))}):r()}))}}isHistoryThrottleError(e){return e instanceof Error&&e.name===`SecurityError`&&(e.message.includes(`history.pushState`)||e.message.includes(`history.replaceState`))}isQuotaExceededError(e){return e instanceof Error&&e.name===`QuotaExceededError`}withThrottleProtection(e){return Promise.resolve().then(()=>{try{return e()}catch(e){if(!this.isHistoryThrottleError(e))throw e;console.error(e.message)}})}doReplaceState(e,t){return this.withThrottleProtection(()=>{window.history.replaceState({...e,scrollRegions:e.scrollRegions??window.history.state?.scrollRegions,documentScrollPosition:e.documentScrollPosition??window.history.state?.documentScrollPosition},``,t)})}doPushState(e,t){return this.withThrottleProtection(()=>{try{window.history.pushState(e,``,t)}catch(e){if(!this.isQuotaExceededError(e))throw e;Rb.fireInternalEvent(`historyQuotaExceeded`,t)}})}getState(e,t){return this.current?.[e]??t}deleteState(e){this.current[e]!==void 0&&(delete this.current[e],this.replaceState(this.current))}clearInitialState(e){this.initialState&&this.initialState[e]!==void 0&&delete this.initialState[e]}browserHasHistoryEntry(){return!Pb&&!!window.history.state?.page}clear(){Zy.remove($y.key),Zy.remove($y.iv)}setCurrent(e){this.current=e}isValidState(e){return!!e.page}getAllState(){return this.current}};typeof window<`u`&&window.history.scrollRestoration&&(window.history.scrollRestoration=`manual`);var W=new Lb,Rb=new class{constructor(){this.internalListeners=[]}init(){typeof window<`u`&&(window.addEventListener(`popstate`,this.handlePopstateEvent.bind(this)),window.addEventListener(`scroll`,Iy(_b.onWindowScroll.bind(_b),100),!0)),typeof document<`u`&&document.addEventListener(`scroll`,Iy(_b.onScroll.bind(_b),100),!0)}onGlobalEvent(e,t){return this.registerListener(`inertia:${e}`,(e=>{let n=t(e);e.cancelable&&!e.defaultPrevented&&n===!1&&e.preventDefault()}))}on(e,t){return this.internalListeners.push({event:e,listener:t}),()=>{this.internalListeners=this.internalListeners.filter(e=>e.listener!==t)}}onMissingHistoryItem(){U.clear(),this.fireInternalEvent(`missingHistoryItem`)}fireInternalEvent(e,...t){this.internalListeners.filter(t=>t.event===e).forEach(e=>e.listener(...t))}registerListener(e,t){return document.addEventListener(e,t),()=>document.removeEventListener(e,t)}handlePopstateEvent(e){let t=e.state||null;if(t===null){let e=wb(U.get().url);e.hash=window.location.hash,W.replaceState({...U.getWithoutFlashData(),url:e.href}),_b.reset();return}if(!W.isValidState(t))return this.onMissingHistoryItem();W.decrypt(t.page).then(e=>{if(U.get().version!==e.version){this.onMissingHistoryItem();return}Hx.cancelAll({prefetch:!1}),U.setQuietly(e,{preserveState:!1}).then(()=>{_b.restore(W.getScrollRegions()),Wy(U.get());let t={},n=U.get().props;for(let[r,i]of Object.entries(e.initialDeferredProps??e.deferredProps??{})){let e=i.filter(e=>n[e]===void 0);e.length>0&&(t[r]=e)}Object.keys(t).length>0&&this.fireInternalEvent(`loadDeferredProps`,t)})}).catch(()=>{this.onMissingHistoryItem()})}},zb=new class{constructor(){this.type=this.resolveType()}resolveType(){return typeof window>`u`?`navigate`:window.performance&&window.performance.getEntriesByType&&window.performance.getEntriesByType(`navigation`).length>0?window.performance.getEntriesByType(`navigation`)[0].type:`navigate`}get(){return this.type}isBackForward(){return this.type===`back_forward`}isReload(){return this.type===`reload`}},Bb=class{static handle(){this.clearRememberedStateOnReload(),[this.handleBackForward,this.handleLocation,this.handleDefault].find(e=>e.bind(this)())}static clearRememberedStateOnReload(){zb.isReload()&&(W.deleteState(W.rememberedState),W.clearInitialState(W.rememberedState))}static handleBackForward(){if(!zb.isBackForward()||!W.browserHasHistoryEntry())return!1;let e=W.getScrollRegions();return W.decrypt().then(t=>{U.set(t,{preserveScroll:!0,preserveState:!0}).then(()=>{_b.restore(e),Wy(U.get())})}).catch(()=>{Rb.onMissingHistoryItem()}),!0}static handleLocation(){if(!Zy.exists(Zy.locationVisitKey))return!1;let e=Zy.get(Zy.locationVisitKey)||{};return Zy.remove(Zy.locationVisitKey),typeof window<`u`&&U.setUrlHash(window.location.hash),W.decrypt(U.get()).then(()=>{let t=W.getState(W.rememberedState,{}),n=W.getScrollRegions();U.remember(t),U.set(U.get(),{preserveScroll:e.preserveScroll,preserveState:!0}).then(()=>{e.preserveScroll&&_b.restore(n),Wy(U.get())})}).catch(()=>{Rb.onMissingHistoryItem()}),!0}static handleDefault(){typeof window<`u`&&U.setUrlHash(window.location.hash),U.set(U.get(),{preserveScroll:!0,preserveState:!0}).then(()=>{zb.isReload()?_b.restore(W.getScrollRegions()):_b.scrollToAnchor();let e=U.get();Wy(e);let t=e.flash;Object.keys(t).length>0&&queueMicrotask(()=>Xy(t))})}},Vb=class{constructor(e,t,n){this.id=null,this.throttle=!1,this.keepAlive=!1,this.cbCount=0,this.keepAlive=n.keepAlive??!1,this.cb=t,this.interval=e,(n.autoStart??!0)&&this.start()}stop(){this.id&&clearInterval(this.id)}start(){typeof window>`u`||(this.stop(),this.id=window.setInterval(()=>{(!this.throttle||this.cbCount%10==0)&&this.cb(),this.throttle&&this.cbCount++},this.interval))}isInBackground(e){this.throttle=this.keepAlive?!1:e,this.throttle&&(this.cbCount=0)}},Hb=new class{constructor(){this.polls=[],this.setupVisibilityListener()}add(e,t,n){let r=new Vb(e,t,n);return this.polls.push(r),{stop:()=>r.stop(),start:()=>r.start()}}clear(){this.polls.forEach(e=>e.stop()),this.polls=[]}setupVisibilityListener(){typeof document>`u`||document.addEventListener(`visibilitychange`,()=>{this.polls.forEach(e=>e.isInBackground(document.hidden))},!1)}},Ub=class e{constructor(e){if(this.callbacks=[],!e.prefetch)this.params=e;else{let t={onBefore:this.wrapCallback(e,`onBefore`),onBeforeUpdate:this.wrapCallback(e,`onBeforeUpdate`),onStart:this.wrapCallback(e,`onStart`),onProgress:this.wrapCallback(e,`onProgress`),onFinish:this.wrapCallback(e,`onFinish`),onCancel:this.wrapCallback(e,`onCancel`),onSuccess:this.wrapCallback(e,`onSuccess`),onError:this.wrapCallback(e,`onError`),onFlash:this.wrapCallback(e,`onFlash`),onCancelToken:this.wrapCallback(e,`onCancelToken`),onPrefetched:this.wrapCallback(e,`onPrefetched`),onPrefetching:this.wrapCallback(e,`onPrefetching`)};this.params={...e,...t,onPrefetchResponse:e.onPrefetchResponse||(()=>{}),onPrefetchError:e.onPrefetchError||(()=>{})}}}static create(t){return new e(t)}data(){return this.params.method===`get`?null:this.params.data}queryParams(){return this.params.method===`get`?this.params.data:{}}isPartial(){return this.params.only.length>0||this.params.except.length>0||this.params.reset.length>0}isPrefetch(){return this.params.prefetch===!0}isDeferredPropsRequest(){return this.params.deferredProps===!0}onCancelToken(e){this.params.onCancelToken({cancel:e})}markAsFinished(){this.params.completed=!0,this.params.cancelled=!1,this.params.interrupted=!1}markAsCancelled({cancelled:e=!0,interrupted:t=!1}){this.params.onCancel(),this.params.completed=!1,this.params.cancelled=e,this.params.interrupted=t}wasCancelledAtAll(){return this.params.cancelled||this.params.interrupted}onFinish(){this.params.onFinish(this.params)}onStart(){this.params.onStart(this.params)}onPrefetching(){this.params.onPrefetching(this.params)}onPrefetchResponse(e){this.params.onPrefetchResponse&&this.params.onPrefetchResponse(e)}onPrefetchError(e){this.params.onPrefetchError&&this.params.onPrefetchError(e)}all(){return this.params}headers(){let e={...this.params.headers};this.isPartial()&&(e[`X-Inertia-Partial-Component`]=U.get().component);let t=this.params.only.concat(this.params.reset);return t.length>0&&(e[`X-Inertia-Partial-Data`]=t.join(`,`)),this.params.except.length>0&&(e[`X-Inertia-Partial-Except`]=this.params.except.join(`,`)),this.params.reset.length>0&&(e[`X-Inertia-Reset`]=this.params.reset.join(`,`)),this.params.errorBag&&this.params.errorBag.length>0&&(e[`X-Inertia-Error-Bag`]=this.params.errorBag),e}setPreserveOptions(t){this.params.preserveScroll=e.resolvePreserveOption(this.params.preserveScroll,t),this.params.preserveState=e.resolvePreserveOption(this.params.preserveState,t)}runCallbacks(){this.callbacks.forEach(({name:e,args:t})=>{this.params[e](...t)})}merge(e){this.params={...this.params,...e}}wrapCallback(e,t){return(...n)=>{this.recordCallback(t,n),e[t](...n)}}recordCallback(e,t){this.callbacks.push({name:e,args:t})}static resolvePreserveOption(e,t){return typeof e==`function`?e(t):e===`errors`?Object.keys(t.props.errors||{}).length>0:e}},Wb={modal:null,listener:null,createIframeAndPage(e){typeof e==`object`&&(e=`All Inertia requests must receive a valid Inertia response, however a plain JSON response was received.<hr>${JSON.stringify(e)}`);let t=document.createElement(`html`);t.innerHTML=e,t.querySelectorAll(`a`).forEach(e=>e.setAttribute(`target`,`_top`));let n=document.createElement(`iframe`);return n.style.backgroundColor=`white`,n.style.borderRadius=`5px`,n.style.width=`100%`,n.style.height=`100%`,{iframe:n,page:t}},show(e){let{iframe:t,page:n}=this.createIframeAndPage(e);if(this.modal=document.createElement(`div`),this.modal.style.position=`fixed`,this.modal.style.width=`100vw`,this.modal.style.height=`100vh`,this.modal.style.padding=`50px`,this.modal.style.boxSizing=`border-box`,this.modal.style.backgroundColor=`rgba(0, 0, 0, .6)`,this.modal.style.zIndex=2e5,this.modal.addEventListener(`click`,()=>this.hide()),this.modal.appendChild(t),document.body.prepend(this.modal),document.body.style.overflow=`hidden`,!t.contentWindow)throw Error(`iframe not yet ready.`);t.contentWindow.document.open(),t.contentWindow.document.write(n.outerHTML),t.contentWindow.document.close(),this.listener=this.hideOnEscape.bind(this),document.addEventListener(`keydown`,this.listener)},hide(){this.modal.outerHTML=``,this.modal=null,document.body.style.overflow=`visible`,document.removeEventListener(`keydown`,this.listener)},hideOnEscape(e){e.keyCode===27&&this.hide()}},Gb={show(e){let{iframe:t,page:n}=Wb.createIframeAndPage(e);t.style.boxSizing=`border-box`,t.style.display=`block`;let r=document.createElement(`dialog`);r.id=`inertia-error-dialog`,Object.assign(r.style,{width:`calc(100vw - 100px)`,height:`calc(100vh - 100px)`,padding:`0`,margin:`auto`,border:`none`,backgroundColor:`transparent`});let i=document.createElement(`style`);if(i.textContent=`
      dialog#inertia-error-dialog::backdrop {
        background-color: rgba(0, 0, 0, 0.6);
      }

      dialog#inertia-error-dialog:focus {
        outline: none;
      }
    `,document.head.appendChild(i),r.addEventListener(`click`,e=>{e.target===r&&r.close()}),r.addEventListener(`close`,()=>{i.remove(),r.remove()}),r.appendChild(t),document.body.prepend(r),r.showModal(),r.focus(),!t.contentWindow)throw Error(`iframe not yet ready.`);t.contentWindow.document.open(),t.contentWindow.document.write(n.outerHTML),t.contentWindow.document.close()}},Kb=new Nb,qb=class e{constructor(e,t,n){this.requestParams=e,this.response=t,this.originatingPage=n,this.wasPrefetched=!1}static create(t,n,r){return new e(t,n,r)}async handlePrefetch(){kb(this.requestParams.all().url,window.location)&&this.handle()}async handle(){return Kb.add(()=>this.process())}async process(){if(this.requestParams.all().prefetch)return this.wasPrefetched=!0,this.requestParams.all().prefetch=!1,this.requestParams.all().onPrefetched(this.response,this.requestParams.all()),Jy(this.response,this.requestParams.all()),Promise.resolve();if(this.requestParams.runCallbacks(),!this.isInertiaResponse())return this.handleNonInertiaResponse();await W.processQueue(),W.preserveUrl=this.requestParams.all().preserveUrl;let e=U.get().flash;await this.setPage();let t=U.get().props.errors||{};if(Object.keys(t).length>0){let e=this.getScopedErrors(t);return zy(e),this.requestParams.all().onError(e)}Hx.flushByCacheTags(this.requestParams.all().invalidateCacheTags||[]),this.wasPrefetched||Hx.flush(U.get().url);let{flash:n}=U.get();Object.keys(n).length>0&&(!this.requestParams.isPartial()||!Mv(n,e))&&(Xy(n),this.requestParams.all().onFlash(n)),qy(U.get()),await this.requestParams.all().onSuccess(U.get()),W.preserveUrl=!1}mergeParams(e){this.requestParams.merge(e)}getPageResponse(){let e=this.getDataFromResponse(this.response.data);return typeof e==`object`?this.response.data={...e,flash:e.flash??{}}:this.response.data=e}async handleNonInertiaResponse(){if(this.isLocationVisit()){let e=wb(this.getHeader(`x-inertia-location`));return Ob(this.requestParams.all().url,e),this.locationVisit(e)}let e={...this.response,data:this.getDataFromResponse(this.response.data)};if(Hy(e))return Fy.get(`future.useDialogForErrorModal`)?Gb.show(e.data):Wb.show(e.data)}isInertiaResponse(){return this.hasHeader(`x-inertia`)}hasStatus(e){return this.response.status===e}getHeader(e){return this.response.headers[e]}hasHeader(e){return this.getHeader(e)!==void 0}isLocationVisit(){return this.hasStatus(409)&&this.hasHeader(`x-inertia-location`)}locationVisit(e){try{if(Zy.set(Zy.locationVisitKey,{preserveScroll:this.requestParams.all().preserveScroll===!0}),typeof window>`u`)return;kb(window.location,e)?window.location.reload():window.location.href=e.href}catch{return!1}}async setPage(){let e=this.getPageResponse();return this.shouldSetPage(e)?(this.mergeProps(e),U.mergeOncePropsIntoResponse(e),this.preserveEqualProps(e),await this.setRememberedState(e),this.requestParams.setPreserveOptions(e),e.url=W.preserveUrl?U.get().url:this.pageUrl(e),this.requestParams.all().onBeforeUpdate(e),Uy(e),U.set(e,{replace:this.requestParams.all().replace,preserveScroll:this.requestParams.all().preserveScroll,preserveState:this.requestParams.all().preserveState,viewTransition:this.requestParams.all().viewTransition})):Promise.resolve()}getDataFromResponse(e){if(typeof e!=`string`)return e;try{return JSON.parse(e)}catch{return e}}shouldSetPage(e){if(!this.requestParams.all().async||this.originatingPage.component!==e.component)return!0;if(this.originatingPage.component!==U.get().component)return!1;let t=wb(this.originatingPage.url),n=wb(U.get().url);return t.origin===n.origin&&t.pathname===n.pathname}pageUrl(e){let t=wb(e.url);return Ob(this.requestParams.all().url,t),t.pathname+t.search+t.hash}preserveEqualProps(e){if(e.component!==U.get().component||Fy.get(`future.preserveEqualProps`)!==!0)return;let t=U.get().props;Object.entries(e.props).forEach(([n,r])=>{Mv(r,t[n])&&(e.props[n]=t[n])})}mergeProps(e){if(!this.requestParams.isPartial()||e.component!==U.get().component)return;let t=e.mergeProps||[],n=e.prependProps||[],r=e.deepMergeProps||[],i=e.matchPropsOn||[],a=(t,n)=>{let r=rh(U.get().props,t),a=rh(e.props,t);if(Array.isArray(a)){let o=this.mergeOrMatchItems(r||[],a,t,i,n);Hv(e.props,t,o)}else if(typeof a==`object`&&a){let n={...r||{},...a};Hv(e.props,t,n)}};if(t.forEach(e=>a(e,!0)),n.forEach(e=>a(e,!1)),r.forEach(t=>{let n=U.get().props[t],r=e.props[t],a=(e,t,n)=>Array.isArray(t)?this.mergeOrMatchItems(e,t,n,i):typeof t==`object`&&t?Object.keys(t).reduce((r,i)=>(r[i]=a(e?e[i]:void 0,t[i],`${n}.${i}`),r),{...e}):t;e.props[t]=a(n,r,t)}),e.props={...U.get().props,...e.props},this.requestParams.isDeferredPropsRequest()){let t=U.get().props.errors;t&&Object.keys(t).length>0&&(e.props.errors=t)}U.get().scrollProps&&(e.scrollProps={...U.get().scrollProps||{},...e.scrollProps||{}}),U.hasOnceProps()&&(e.onceProps={...U.get().onceProps||{},...e.onceProps||{}}),e.flash={...U.get().flash,...this.requestParams.isDeferredPropsRequest()?{}:e.flash};let o=U.get().initialDeferredProps;o&&Object.keys(o).length>0&&(e.initialDeferredProps=o)}mergeOrMatchItems(e,t,n,r,i=!0){let a=Array.isArray(e)?e:[],o=r.find(e=>e.split(`.`).slice(0,-1).join(`.`)===n);if(!o)return i?[...a,...t]:[...t,...a];let s=o.split(`.`).pop()||``,c=new Map;return t.forEach(e=>{this.hasUniqueProperty(e,s)&&c.set(e[s],e)}),i?this.appendWithMatching(a,t,c,s):this.prependWithMatching(a,t,c,s)}appendWithMatching(e,t,n,r){let i=e.map(e=>this.hasUniqueProperty(e,r)&&n.has(e[r])?n.get(e[r]):e),a=t.filter(t=>this.hasUniqueProperty(t,r)?!e.some(e=>this.hasUniqueProperty(e,r)&&e[r]===t[r]):!0);return[...i,...a]}prependWithMatching(e,t,n,r){let i=e.filter(e=>this.hasUniqueProperty(e,r)?!n.has(e[r]):!0);return[...t,...i]}hasUniqueProperty(e,t){return e&&typeof e==`object`&&t in e}async setRememberedState(e){let t=await W.getState(W.rememberedState,{});this.requestParams.all().preserveState&&t&&e.component===U.get().component&&(e.rememberedState=t)}getScopedErrors(e){return this.requestParams.all().errorBag?e[this.requestParams.all().errorBag||``]||{}:e}},Jb=class e{constructor(e,t){this.page=t,this.requestHasFinished=!1,this.requestParams=Ub.create(e),this.cancelToken=new AbortController}static create(t,n){return new e(t,n)}isPrefetch(){return this.requestParams.isPrefetch()}async send(){this.requestParams.onCancelToken(()=>this.cancel({cancelled:!0})),Ky(this.requestParams.all()),this.requestParams.onStart(),this.requestParams.all().prefetch&&(this.requestParams.onPrefetching(),Yy(this.requestParams.all()));let e=this.requestParams.all().prefetch;return l({method:this.requestParams.all().method,url:Db(this.requestParams.all().url).href,data:this.requestParams.data(),params:this.requestParams.queryParams(),signal:this.cancelToken.signal,headers:this.getHeaders(),onUploadProgress:this.onProgress.bind(this),responseType:`text`}).then(e=>(this.response=qb.create(this.requestParams,e,this.page),this.response.handle())).catch(e=>e?.response?(this.response=qb.create(this.requestParams,e.response,this.page),this.response.handle()):Promise.reject(e)).catch(t=>{if(!l.isCancel(t)&&By(t))return e&&this.requestParams.onPrefetchError(t),Promise.reject(t)}).finally(()=>{this.finish(),e&&this.response&&this.requestParams.onPrefetchResponse(this.response)})}finish(){this.requestParams.wasCancelledAtAll()||(this.requestParams.markAsFinished(),this.fireFinishEvents())}fireFinishEvents(){this.requestHasFinished||(this.requestHasFinished=!0,Vy(this.requestParams.all()),this.requestParams.onFinish())}cancel({cancelled:e=!1,interrupted:t=!1}){this.requestHasFinished||(this.cancelToken.abort(),this.requestParams.markAsCancelled({cancelled:e,interrupted:t}),this.fireFinishEvents())}onProgress(e){this.requestParams.data()instanceof FormData&&(e.percentage=e.progress?Math.round(e.progress*100):0,Gy(e),this.requestParams.all().onProgress(e))}getHeaders(){let e={...this.requestParams.headers(),Accept:`text/html, application/xhtml+xml`,"X-Requested-With":`XMLHttpRequest`,"X-Inertia":!0},t=U.get();t.version&&(e[`X-Inertia-Version`]=t.version);let n=Object.entries(t.onceProps||{}).filter(([,e])=>t.props[e.prop]===void 0?!1:!e.expiresAt||e.expiresAt>Date.now()).map(([e])=>e);return n.length>0&&(e[`X-Inertia-Except-Once-Props`]=n.join(`,`)),e}},Yb=class{constructor({maxConcurrent:e,interruptible:t}){this.requests=[],this.maxConcurrent=e,this.interruptible=t}send(e){this.requests.push(e),e.send().then(()=>{this.requests=this.requests.filter(t=>t!==e)})}interruptInFlight(){this.cancel({interrupted:!0},!1)}cancelInFlight({prefetch:e=!0}={}){this.requests.filter(t=>e||!t.isPrefetch()).forEach(e=>e.cancel({cancelled:!0}))}cancel({cancelled:e=!1,interrupted:t=!1}={},n=!1){!n&&!this.shouldCancel()||this.requests.shift()?.cancel({cancelled:e,interrupted:t})}shouldCancel(){return this.interruptible&&this.requests.length>=this.maxConcurrent}},Xb=class{constructor(){this.syncRequestStream=new Yb({maxConcurrent:1,interruptible:!0}),this.asyncRequestStream=new Yb({maxConcurrent:1/0,interruptible:!1}),this.clientVisitQueue=new Nb}init({initialPage:e,resolveComponent:t,swapComponent:n,onFlash:r}){U.init({initialPage:e,resolveComponent:t,swapComponent:n,onFlash:r}),Bb.handle(),Rb.init(),Rb.on(`missingHistoryItem`,()=>{typeof window<`u`&&this.visit(window.location.href,{preserveState:!0,preserveScroll:!0,replace:!0})}),Rb.on(`loadDeferredProps`,e=>{this.loadDeferredProps(e)}),Rb.on(`historyQuotaExceeded`,e=>{window.location.href=e})}get(e,t={},n={}){return this.visit(e,{...n,method:`get`,data:t})}post(e,t={},n={}){return this.visit(e,{preserveState:!0,...n,method:`post`,data:t})}put(e,t={},n={}){return this.visit(e,{preserveState:!0,...n,method:`put`,data:t})}patch(e,t={},n={}){return this.visit(e,{preserveState:!0,...n,method:`patch`,data:t})}delete(e,t={}){return this.visit(e,{preserveState:!0,...t,method:`delete`})}reload(e={}){return this.doReload(e)}doReload(e={}){if(!(typeof window>`u`))return this.visit(window.location.href,{...e,preserveScroll:!0,preserveState:!0,async:!0,headers:{...e.headers||{},"Cache-Control":`no-cache`}})}remember(e,t=`default`){W.remember(e,t)}restore(e=`default`){return W.restore(e)}on(e,t){return typeof window>`u`?()=>{}:Rb.onGlobalEvent(e,t)}cancel(){this.syncRequestStream.cancelInFlight()}cancelAll({async:e=!0,prefetch:t=!0,sync:n=!0}={}){e&&this.asyncRequestStream.cancelInFlight({prefetch:t}),n&&this.syncRequestStream.cancelInFlight()}poll(e,t={},n={}){return Hb.add(e,()=>this.reload(t),{autoStart:n.autoStart??!0,keepAlive:n.keepAlive??!1})}visit(e,t={}){let n=this.getPendingVisit(e,{...t,showProgress:t.showProgress??!t.async}),r=this.getVisitEvents(t);if(r.onBefore(n)===!1||!Ry(n))return;let i=wb(U.get().url);(n.only.length>0||n.except.length>0||n.reset.length>0?Ab(n.url,i):kb(n.url,i))||this.asyncRequestStream.cancelInFlight({prefetch:!1}),n.async||this.syncRequestStream.interruptInFlight(),!U.isCleared()&&!n.preserveUrl&&_b.save();let a={...n,...r},o=fb.get(a);o?(kx.reveal(o.inFlight),fb.use(o,a)):(kx.reveal(!0),(n.async?this.asyncRequestStream:this.syncRequestStream).send(Jb.create(a,U.get())))}getCached(e,t={}){return fb.findCached(this.getPrefetchParams(e,t))}flush(e,t={}){fb.remove(this.getPrefetchParams(e,t))}flushAll(){fb.removeAll()}flushByCacheTags(e){fb.removeByTags(Array.isArray(e)?e:[e])}getPrefetching(e,t={}){return fb.findInFlight(this.getPrefetchParams(e,t))}prefetch(e,t={},n={}){if((t.method??(jb(e)?e.method:`get`))!==`get`)throw Error(`Prefetch requests must use the GET method`);let r=this.getPendingVisit(e,{...t,async:!0,showProgress:!1,prefetch:!0,viewTransition:!1});if(r.url.origin+r.url.pathname+r.url.search===window.location.origin+window.location.pathname+window.location.search)return;let i=this.getVisitEvents(t);if(i.onBefore(r)===!1||!Ry(r))return;kx.hide(),this.asyncRequestStream.interruptInFlight();let a={...r,...i};new Promise(e=>{let t=()=>{U.get()?e():setTimeout(t,50)};t()}).then(()=>{fb.add(a,e=>{this.asyncRequestStream.send(Jb.create(e,U.get()))},{cacheFor:Fy.get(`prefetch.cacheFor`),cacheTags:[],...n})})}clearHistory(){W.clear()}decryptHistory(){return W.decrypt()}resolveComponent(e){return U.resolve(e)}replace(e){this.clientVisit(e,{replace:!0})}replaceProp(e,t,n){this.replace({preserveScroll:!0,preserveState:!0,props(n){let r=typeof t==`function`?t(rh(n,e),n):t;return Hv(T_(n),e,r)},...n||{}})}appendToProp(e,t,n){this.replaceProp(e,(e,n)=>{let r=typeof t==`function`?t(e,n):t;return Array.isArray(e)||(e=e===void 0?[]:[e]),[...e,r]},n)}prependToProp(e,t,n){this.replaceProp(e,(e,n)=>{let r=typeof t==`function`?t(e,n):t;return Array.isArray(e)||(e=e===void 0?[]:[e]),[r,...e]},n)}push(e){this.clientVisit(e)}flash(e,t){let n=U.get().flash,r;if(typeof e==`function`)r=e(n);else if(typeof e==`string`)r={...n,[e]:t};else if(e&&Object.keys(e).length)r={...n,...e};else return;U.setFlash(r),Object.keys(r).length&&Xy(r)}clientVisit(e,{replace:t=!1}={}){this.clientVisitQueue.add(()=>this.performClientVisit(e,{replace:t}))}performClientVisit(e,{replace:t=!1}={}){let n=U.get(),r=typeof e.props==`function`?Object.fromEntries(Object.values(n.onceProps??{}).map(e=>[e.prop,n.props[e.prop]])):{},i=typeof e.props==`function`?e.props(n.props,r):e.props??n.props,a=typeof e.flash==`function`?e.flash(n.flash):e.flash,{viewTransition:o,onError:s,onFinish:c,onFlash:l,onSuccess:u,...d}=e,f={...n,...d,flash:a??{},props:i},p=Ub.resolvePreserveOption(e.preserveScroll??!1,f),m=Ub.resolvePreserveOption(e.preserveState??!1,f);return U.set(f,{replace:t,preserveScroll:p,preserveState:m,viewTransition:o}).then(()=>{let t=U.get().flash;Object.keys(t).length>0&&(Xy(t),l?.(t));let n=U.get().props.errors||{};if(Object.keys(n).length===0){u?.(U.get());return}let r=e.errorBag?n[e.errorBag||``]||{}:n;s?.(r)}).finally(()=>c?.(e))}getPrefetchParams(e,t){return{...this.getPendingVisit(e,{...t,async:!0,showProgress:!1,prefetch:!0,viewTransition:!1}),...this.getVisitEvents(t)}}getPendingVisit(e,t,n={}){if(jb(e)){let n=e;e=n.url,t.method=t.method??n.method}let r=Fy.get(`visitOptions`),i=r&&r(e.toString(),T_(t))||{},a={method:`get`,data:{},replace:!1,preserveScroll:!1,preserveState:!1,only:[],except:[],headers:{},errorBag:``,forceFormData:!1,queryStringArrayFormat:`brackets`,async:!1,showProgress:!0,fresh:!1,reset:[],preserveUrl:!1,prefetch:!1,invalidateCacheTags:[],viewTransition:!1,...t,...i},[o,s]=Tb(e,a.data,a.method,a.forceFormData,a.queryStringArrayFormat),c={cancelled:!1,completed:!1,interrupted:!1,...a,...n,url:o,data:s};return c.prefetch&&(c.headers.Purpose=`prefetch`),c}getVisitEvents(e){return{onCancelToken:e.onCancelToken||(()=>{}),onBefore:e.onBefore||(()=>{}),onBeforeUpdate:e.onBeforeUpdate||(()=>{}),onStart:e.onStart||(()=>{}),onProgress:e.onProgress||(()=>{}),onFinish:e.onFinish||(()=>{}),onCancel:e.onCancel||(()=>{}),onSuccess:e.onSuccess||(()=>{}),onError:e.onError||(()=>{}),onFlash:e.onFlash||(()=>{}),onPrefetched:e.onPrefetched||(()=>{}),onPrefetching:e.onPrefetching||(()=>{})}}loadDeferredProps(e){e&&Object.entries(e).forEach(([e,t])=>{this.doReload({only:t,deferredProps:!0})})}},Zb=class{static createWayfinderCallback(...e){return()=>e.length===1?jb(e[0])?e[0]:e[0]():{method:typeof e[0]==`function`?e[0]():e[0],url:typeof e[1]==`function`?e[1]():e[1]}}static parseUseFormArguments(...e){return e.length===0?{rememberKey:null,data:{},precognitionEndpoint:null}:e.length===1?{rememberKey:null,data:e[0],precognitionEndpoint:null}:e.length===2?typeof e[0]==`string`?{rememberKey:e[0],data:e[1],precognitionEndpoint:null}:{rememberKey:null,data:e[1],precognitionEndpoint:this.createWayfinderCallback(e[0])}:{rememberKey:null,data:e[2],precognitionEndpoint:this.createWayfinderCallback(e[0],e[1])}}static parseSubmitArguments(e,t){return e.length===3||e.length===2&&typeof e[0]==`string`?{method:e[0],url:e[1],options:e[2]??{}}:jb(e[0])?{...e[0],options:e[1]??{}}:{...t(),options:e[0]??{}}}static mergeHeadersForValidation(e,t,n){let r=e=>(e.headers={...n??{},...e.headers??{}},e);return e&&typeof e==`object`&&!(`target`in e)?e=r(e):t&&typeof t==`object`?t=r(t):typeof e==`string`?t=r(t??{}):e=r(e??{}),[e,t]}};function Qb(e){return e.includes(`.`)?e.replace(/\\\./g,`__ESCAPED_DOT__`).split(/(\[[^\]]*\])/).filter(Boolean).map(e=>e.startsWith(`[`)&&e.endsWith(`]`)?e:e.split(`.`).reduce((e,t,n)=>n===0?t:`${e}[${t}]`)).join(``).replace(/__ESCAPED_DOT__/g,`.`):e}function $b(e){let t=[],n=/([^\[\]]+)|\[(\d*)\]/g,r;for(;(r=n.exec(e))!==null;)r[1]===void 0?r[2]!==void 0&&t.push(r[2]===``?``:Number(r[2])):t.push(r[1]);return t}function ex(e,t,n){let r=e;for(let e=0;e<t.length-1;e++)t[e]in r||(r[t[e]]={}),r=r[t[e]];r[t[t.length-1]]=n}function tx(e){let t=Object.keys(e),n=t.filter(e=>/^\d+$/.test(e)).map(Number).sort((e,t)=>e-t);return t.length===n.length&&n.length>0&&n[0]===0&&n.every((e,t)=>e===t)}function nx(e){if(Array.isArray(e))return e.map(nx);if(typeof e!=`object`||!e||vb(e))return e;if(tx(e)){let t=[];for(let n=0;n<Object.keys(e).length;n++)t[n]=nx(e[n]);return t}let t={};for(let n in e)t[n]=nx(e[n]);return t}function rx(e){let t={};for(let[n,r]of e.entries()){if(r instanceof File&&r.size===0&&r.name===``)continue;let e=$b(Qb(n));if(e[e.length-1]===``){let n=e.slice(0,-1),i=rh(t,n);if(Array.isArray(i))i.push(r);else if(i&&typeof i==`object`&&!vb(i)){let e=Object.keys(i).filter(e=>/^\d+$/.test(e)).map(Number).sort((e,t)=>e-t);Hv(t,n,e.length>0?[...e.map(e=>i[e]),r]:[r])}else Hv(t,n,[r]);continue}ex(t,e.map(String),r)}return nx(t)}var ix={preferredAttribute(){return Fy.get(`future.useDataInertiaHeadAttribute`)?`data-inertia`:`inertia`},buildDOMElement(e){let t=document.createElement(`template`);t.innerHTML=e;let n=t.content.firstChild;if(!e.startsWith(`<script `))return n;let r=document.createElement(`script`);return r.innerHTML=n.innerHTML,n.getAttributeNames().forEach(e=>{r.setAttribute(e,n.getAttribute(e)||``)}),r},isInertiaManagedElement(e){return e.nodeType===Node.ELEMENT_NODE&&e.getAttribute(this.preferredAttribute())!==null},findMatchingElementIndex(e,t){let n=this.preferredAttribute(),r=e.getAttribute(n);return r===null?-1:t.findIndex(e=>e.getAttribute(n)===r)},update:Iy(function(e){let t=e.map(e=>this.buildDOMElement(e));Array.from(document.head.childNodes).filter(e=>this.isInertiaManagedElement(e)).forEach(e=>{let n=this.findMatchingElementIndex(e,t);if(n===-1){e?.parentNode?.removeChild(e);return}let r=t.splice(n,1)[0];r&&!e.isEqualNode(r)&&e?.parentNode?.replaceChild(r,e)}),t.forEach(e=>document.head.appendChild(e))},1)};function ax(e,t,n){let r={},i=0;function a(){let e=i+=1;return r[e]=[],e.toString()}function o(e){e===null||Object.keys(r).indexOf(e)===-1||(delete r[e],u())}function s(e){Object.keys(r).indexOf(e)===-1&&(r[e]=[])}function c(e,t=[]){e!==null&&Object.keys(r).indexOf(e)>-1&&(r[e]=t),u()}function l(){let e=t(``),n=ix.preferredAttribute(),i={...e?{title:`<title ${n}="">${e}</title>`}:{}},a=Object.values(r).reduce((e,t)=>e.concat(t),[]).reduce((e,r)=>{if(r.indexOf(`<`)===-1)return e;if(r.indexOf(`<title `)===0){let n=r.match(/(<title [^>]+>)(.*?)(<\/title>)/);return e.title=n?`${n[1]}${t(n[2])}${n[3]}`:r,e}let i=r.match(n===`inertia`?/ inertia="[^"]+"/:/ data-inertia="[^"]+"/);return i?e[i[0]]=r:e[Object.keys(e).length]=r,e},i);return Object.values(a)}function u(){e?n(l()):ix.update(l())}return u(),{forceUpdate:u,createProvider:function(){let e=a();return{preferredAttribute:ix.preferredAttribute,reconnect:()=>s(e),update:t=>c(e,t),disconnect:()=>o(e)}}}}new Nb;function ox(e){return e.target instanceof HTMLElement&&e.target.isContentEditable||e.defaultPrevented}function sx(e){let t=e.currentTarget.tagName.toLowerCase()===`a`;return!(ox(e)||t&&e.altKey||t&&e.ctrlKey||t&&e.metaKey||t&&e.shiftKey||t&&`button`in e&&e.button!==0)}function cx(e){let t=e.currentTarget.tagName.toLowerCase()===`button`;return!ox(e)&&(e.key===`Enter`||t&&e.key===` `)}var lx=`nprogress`,ux,dx={minimum:.08,easing:`linear`,positionUsing:`translate3d`,speed:200,trickle:!0,trickleSpeed:200,showSpinner:!0,barSelector:`[role="bar"]`,spinnerSelector:`[role="spinner"]`,parent:`body`,color:`#29d`,includeCSS:!0,template:[`<div class="bar" role="bar">`,`<div class="peg"></div>`,`</div>`,`<div class="spinner" role="spinner">`,`<div class="spinner-icon"></div>`,`</div>`].join(``)},fx=null,px=e=>{Object.assign(dx,e),dx.includeCSS&&Dx(dx.color),ux=document.createElement(`div`),ux.id=lx,ux.innerHTML=dx.template},mx=e=>{let t=hx();e=wx(e,dx.minimum,1),fx=e===1?null:e;let n=yx(!t),r=n.querySelector(dx.barSelector),i=dx.speed,a=dx.easing;n.offsetWidth,Ex(t=>{let o=dx.positionUsing===`translate3d`?{transition:`all ${i}ms ${a}`,transform:`translate3d(${Tx(e)}%,0,0)`}:dx.positionUsing===`translate`?{transition:`all ${i}ms ${a}`,transform:`translate(${Tx(e)}%,0)`}:{marginLeft:`${Tx(e)}%`};for(let e in o)r.style[e]=o[e];if(e!==1)return setTimeout(t,i);n.style.transition=`none`,n.style.opacity=`1`,n.offsetWidth,setTimeout(()=>{n.style.transition=`all ${i}ms linear`,n.style.opacity=`0`,setTimeout(()=>{xx(),n.style.transition=``,n.style.opacity=``,t()},i)},i)})},hx=()=>typeof fx==`number`,gx=()=>{fx||mx(0);let e=function(){setTimeout(function(){fx&&(vx(),e())},dx.trickleSpeed)};dx.trickle&&e()},_x=e=>{!e&&!fx||(vx(.3+.5*Math.random()),mx(1))},vx=e=>{let t=fx;if(t===null)return gx();if(!(t>1))return e=typeof e==`number`?e:(()=>{let e={.1:[0,.2],.04:[.2,.5],.02:[.5,.8],.005:[.8,.99]};for(let n in e)if(t>=e[n][0]&&t<e[n][1])return parseFloat(n);return 0})(),mx(wx(t+e,0,.994))},yx=e=>{if(Sx())return document.getElementById(lx);document.documentElement.classList.add(`${lx}-busy`);let t=ux.querySelector(dx.barSelector),n=e?`-100`:Tx(fx||0),r=bx();return t.style.transition=`all 0 linear`,t.style.transform=`translate3d(${n}%,0,0)`,dx.showSpinner||ux.querySelector(dx.spinnerSelector)?.remove(),r!==document.body&&r.classList.add(`${lx}-custom-parent`),r.appendChild(ux),ux},bx=()=>Cx(dx.parent)?dx.parent:document.querySelector(dx.parent),xx=()=>{document.documentElement.classList.remove(`${lx}-busy`),bx().classList.remove(`${lx}-custom-parent`),ux?.remove()},Sx=()=>document.getElementById(lx)!==null,Cx=e=>typeof HTMLElement==`object`?e instanceof HTMLElement:e&&typeof e==`object`&&e.nodeType===1&&typeof e.nodeName==`string`;function wx(e,t,n){return e<t?t:e>n?n:e}var Tx=e=>(-1+e)*100,Ex=(()=>{let e=[],t=()=>{let n=e.shift();n&&n(t)};return n=>{e.push(n),e.length===1&&t()}})(),Dx=e=>{let t=document.createElement(`style`);t.textContent=`
    #${lx} {
      pointer-events: none;
    }

    #${lx} .bar {
      background: ${e};

      position: fixed;
      z-index: 1031;
      top: 0;
      left: 0;

      width: 100%;
      height: 2px;
    }

    #${lx} .peg {
      display: block;
      position: absolute;
      right: 0px;
      width: 100px;
      height: 100%;
      box-shadow: 0 0 10px ${e}, 0 0 5px ${e};
      opacity: 1.0;

      transform: rotate(3deg) translate(0px, -4px);
    }

    #${lx} .spinner {
      display: block;
      position: fixed;
      z-index: 1031;
      top: 15px;
      right: 15px;
    }

    #${lx} .spinner-icon {
      width: 18px;
      height: 18px;
      box-sizing: border-box;

      border: solid 2px transparent;
      border-top-color: ${e};
      border-left-color: ${e};
      border-radius: 50%;

      animation: ${lx}-spinner 400ms linear infinite;
    }

    .${lx}-custom-parent {
      overflow: hidden;
      position: relative;
    }

    .${lx}-custom-parent #${lx} .spinner,
    .${lx}-custom-parent #${lx} .bar {
      position: absolute;
    }

    @keyframes ${lx}-spinner {
      0%   { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  `,document.head.appendChild(t)},Ox={configure:px,isStarted:hx,done:_x,set:mx,remove:xx,start:gx,status:fx,show:()=>{ux&&(ux.style.display=``)},hide:()=>{ux&&(ux.style.display=`none`)}},kx=new class{constructor(){this.hideCount=0}start(){Ox.start()}reveal(e=!1){this.hideCount=Math.max(0,this.hideCount-1),(e||this.hideCount===0)&&Ox.show()}hide(){this.hideCount++,Ox.hide()}set(e){Ox.set(Math.max(0,Math.min(1,e)))}finish(){Ox.done()}reset(){Ox.set(0)}remove(){Ox.done(),Ox.remove()}isStarted(){return Ox.isStarted()}getStatus(){return Ox.status}};kx.reveal,kx.hide;function Ax(e){document.addEventListener(`inertia:start`,t=>jx(t,e)),document.addEventListener(`inertia:progress`,Mx)}function jx(e,t){e.detail.visit.showProgress||kx.hide();let n=setTimeout(()=>kx.start(),t);document.addEventListener(`inertia:finish`,e=>Nx(e,n),{once:!0})}function Mx(e){kx.isStarted()&&e.detail.progress?.percentage&&kx.set(Math.max(kx.getStatus(),e.detail.progress.percentage/100*.9))}function Nx(e,t){clearTimeout(t),kx.isStarted()&&(e.detail.visit.completed?kx.finish():e.detail.visit.interrupted?kx.reset():e.detail.visit.cancelled&&kx.remove())}function Px({delay:e=250,color:t=`#29d`,includeCSS:n=!0,showSpinner:r=!1}={}){Ax(e),Ox.configure({showSpinner:r,includeCSS:n,color:t})}var Fx=Symbol(`FormComponentReset`);function Ix(e){return e instanceof HTMLInputElement||e instanceof HTMLSelectElement||e instanceof HTMLTextAreaElement}function Lx(e,t){let n=e.value,r=e.checked;switch(e.type.toLowerCase()){case`checkbox`:e.checked=t.includes(e.value);break;case`radio`:e.checked=t[0]===e.value;break;case`file`:e.value=``;break;case`button`:case`submit`:case`reset`:case`image`:break;default:e.value=t[0]!==null&&t[0]!==void 0?String(t[0]):``}return e.value!==n||e.checked!==r}function Rx(e,t){let n=e.value,r=Array.from(e.selectedOptions).map(e=>e.value);if(e.multiple){let n=t.map(e=>String(e));Array.from(e.options).forEach(e=>{e.selected=n.includes(e.value)})}else e.value=t[0]===void 0?``:String(t[0]);let i=Array.from(e.selectedOptions).map(e=>e.value);return e.multiple?JSON.stringify(r.sort())!==JSON.stringify(i.sort()):e.value!==n}function zx(e,t){if(e.disabled){if(e instanceof HTMLInputElement){let t=e.value,n=e.checked;switch(e.type.toLowerCase()){case`checkbox`:case`radio`:return e.checked=e.defaultChecked,e.checked!==n;case`file`:return e.value=``,t!==``;case`button`:case`submit`:case`reset`:case`image`:return!1;default:return e.value=e.defaultValue,e.value!==t}}else if(e instanceof HTMLSelectElement){let t=Array.from(e.selectedOptions).map(e=>e.value);Array.from(e.options).forEach(e=>{e.selected=e.defaultSelected});let n=Array.from(e.selectedOptions).map(e=>e.value);return JSON.stringify(t.sort())!==JSON.stringify(n.sort())}else if(e instanceof HTMLTextAreaElement){let t=e.value;return e.value=e.defaultValue,e.value!==t}return!1}if(e instanceof HTMLInputElement)return Lx(e,t);if(e instanceof HTMLSelectElement)return Rx(e,t);if(e instanceof HTMLTextAreaElement){let n=e.value;return e.value=t[0]===void 0?``:String(t[0]),e.value!==n}return!1}function Bx(e,t){let n=!1;return e instanceof RadioNodeList||e instanceof HTMLCollection?Array.from(e).forEach((e,r)=>{e instanceof Element&&Ix(e)&&(e instanceof HTMLInputElement&&[`checkbox`,`radio`].includes(e.type.toLowerCase())?zx(e,t)&&(n=!0):zx(e,t[r]===void 0?[t[0]??null].filter(Boolean):[t[r]])&&(n=!0))}):Ix(e)&&(n=zx(e,t)),n}function Vx(e,t,n){if(!e)return;let r=!n||n.length===0;if(r){let r=new FormData(e),i=Array.from(e.elements).map(e=>Ix(e)?e.name:``).filter(Boolean);n=[...new Set([...t.keys(),...r.keys(),...i])]}let i=!1;n.forEach(n=>{let r=e.elements.namedItem(n);r&&Bx(r,t.getAll(n))&&(i=!0)}),i&&r&&e.dispatchEvent(new CustomEvent(`reset`,{bubbles:!0,cancelable:!0,detail:{[Fx]:!0}}))}var Hx=new Xb;function Ux(e){let t=Object.create(null);for(let n of e.split(`,`))t[n]=1;return e=>e in t}var Wx={},Gx=()=>{},Kx=Object.assign,qx=(e,t)=>{let n=e.indexOf(t);n>-1&&e.splice(n,1)},Jx=Object.prototype.hasOwnProperty,Yx=(e,t)=>Jx.call(e,t),Xx=Array.isArray,Zx=e=>iS(e)===`[object Map]`,Qx=e=>iS(e)===`[object Set]`,$x=e=>typeof e==`function`,eS=e=>typeof e==`string`,tS=e=>typeof e==`symbol`,nS=e=>typeof e==`object`&&!!e,rS=Object.prototype.toString,iS=e=>rS.call(e),aS=e=>iS(e).slice(8,-1),oS=e=>iS(e)===`[object Object]`,sS=e=>eS(e)&&e!==`NaN`&&e[0]!==`-`&&``+parseInt(e,10)===e,cS=(e,t)=>!Object.is(e,t),lS=(e,t,n,r=!1)=>{Object.defineProperty(e,t,{configurable:!0,enumerable:!1,writable:r,value:n})},uS,dS=class{constructor(e=!1){this.detached=e,this._active=!0,this._on=0,this.effects=[],this.cleanups=[],this._isPaused=!1,this.parent=uS,!e&&uS&&(this.index=(uS.scopes||=[]).push(this)-1)}get active(){return this._active}pause(){if(this._active){this._isPaused=!0;let e,t;if(this.scopes)for(e=0,t=this.scopes.length;e<t;e++)this.scopes[e].pause();for(e=0,t=this.effects.length;e<t;e++)this.effects[e].pause()}}resume(){if(this._active&&this._isPaused){this._isPaused=!1;let e,t;if(this.scopes)for(e=0,t=this.scopes.length;e<t;e++)this.scopes[e].resume();for(e=0,t=this.effects.length;e<t;e++)this.effects[e].resume()}}run(e){if(this._active){let t=uS;try{return uS=this,e()}finally{uS=t}}}on(){++this._on===1&&(this.prevScope=uS,uS=this)}off(){this._on>0&&--this._on===0&&(uS=this.prevScope,this.prevScope=void 0)}stop(e){if(this._active){this._active=!1;let t,n;for(t=0,n=this.effects.length;t<n;t++)this.effects[t].stop();for(this.effects.length=0,t=0,n=this.cleanups.length;t<n;t++)this.cleanups[t]();if(this.cleanups.length=0,this.scopes){for(t=0,n=this.scopes.length;t<n;t++)this.scopes[t].stop(!0);this.scopes.length=0}if(!this.detached&&this.parent&&!e){let e=this.parent.scopes.pop();e&&e!==this&&(this.parent.scopes[this.index]=e,e.index=this.index)}this.parent=void 0}}};function fS(e){return new dS(e)}function pS(){return uS}function mS(e,t=!1){uS&&uS.cleanups.push(e)}var G,hS=new WeakSet,gS=class{constructor(e){this.fn=e,this.deps=void 0,this.depsTail=void 0,this.flags=5,this.next=void 0,this.cleanup=void 0,this.scheduler=void 0,uS&&uS.active&&uS.effects.push(this)}pause(){this.flags|=64}resume(){this.flags&64&&(this.flags&=-65,hS.has(this)&&(hS.delete(this),this.trigger()))}notify(){this.flags&2&&!(this.flags&32)||this.flags&8||bS(this)}run(){if(!(this.flags&1))return this.fn();this.flags|=2,FS(this),CS(this);let e=G,t=jS;G=this,jS=!0;try{return this.fn()}finally{wS(this),G=e,jS=t,this.flags&=-3}}stop(){if(this.flags&1){for(let e=this.deps;e;e=e.nextDep)DS(e);this.deps=this.depsTail=void 0,FS(this),this.onStop&&this.onStop(),this.flags&=-2}}trigger(){this.flags&64?hS.add(this):this.scheduler?this.scheduler():this.runIfDirty()}runIfDirty(){TS(this)&&this.run()}get dirty(){return TS(this)}},_S=0,vS,yS;function bS(e,t=!1){if(e.flags|=8,t){e.next=yS,yS=e;return}e.next=vS,vS=e}function xS(){_S++}function SS(){if(--_S>0)return;if(yS){let e=yS;for(yS=void 0;e;){let t=e.next;e.next=void 0,e.flags&=-9,e=t}}let e;for(;vS;){let t=vS;for(vS=void 0;t;){let n=t.next;if(t.next=void 0,t.flags&=-9,t.flags&1)try{t.trigger()}catch(t){e||=t}t=n}}if(e)throw e}function CS(e){for(let t=e.deps;t;t=t.nextDep)t.version=-1,t.prevActiveLink=t.dep.activeLink,t.dep.activeLink=t}function wS(e){let t,n=e.depsTail,r=n;for(;r;){let e=r.prevDep;r.version===-1?(r===n&&(n=e),DS(r),OS(r)):t=r,r.dep.activeLink=r.prevActiveLink,r.prevActiveLink=void 0,r=e}e.deps=t,e.depsTail=n}function TS(e){for(let t=e.deps;t;t=t.nextDep)if(t.dep.version!==t.version||t.dep.computed&&(ES(t.dep.computed)||t.dep.version!==t.version))return!0;return!!e._dirty}function ES(e){if(e.flags&4&&!(e.flags&16)||(e.flags&=-17,e.globalVersion===IS)||(e.globalVersion=IS,!e.isSSR&&e.flags&128&&(!e.deps&&!e._dirty||!TS(e))))return;e.flags|=2;let t=e.dep,n=G,r=jS;G=e,jS=!0;try{CS(e);let n=e.fn(e._value);(t.version===0||cS(n,e._value))&&(e.flags|=128,e._value=n,t.version++)}catch(e){throw t.version++,e}finally{G=n,jS=r,wS(e),e.flags&=-3}}function DS(e,t=!1){let{dep:n,prevSub:r,nextSub:i}=e;if(r&&(r.nextSub=i,e.prevSub=void 0),i&&(i.prevSub=r,e.nextSub=void 0),n.subs===e&&(n.subs=r,!r&&n.computed)){n.computed.flags&=-5;for(let e=n.computed.deps;e;e=e.nextDep)DS(e,!0)}!t&&!--n.sc&&n.map&&n.map.delete(n.key)}function OS(e){let{prevDep:t,nextDep:n}=e;t&&(t.nextDep=n,e.prevDep=void 0),n&&(n.prevDep=t,e.nextDep=void 0)}function kS(e,t){e.effect instanceof gS&&(e=e.effect.fn);let n=new gS(e);t&&Kx(n,t);try{n.run()}catch(e){throw n.stop(),e}let r=n.run.bind(n);return r.effect=n,r}function AS(e){e.effect.stop()}var jS=!0,MS=[];function NS(){MS.push(jS),jS=!1}function PS(){let e=MS.pop();jS=e===void 0?!0:e}function FS(e){let{cleanup:t}=e;if(e.cleanup=void 0,t){let e=G;G=void 0;try{t()}finally{G=e}}}var IS=0,LS=class{constructor(e,t){this.sub=e,this.dep=t,this.version=t.version,this.nextDep=this.prevDep=this.nextSub=this.prevSub=this.prevActiveLink=void 0}},RS=class{constructor(e){this.computed=e,this.version=0,this.activeLink=void 0,this.subs=void 0,this.map=void 0,this.key=void 0,this.sc=0,this.__v_skip=!0}track(e){if(!G||!jS||G===this.computed)return;let t=this.activeLink;if(t===void 0||t.sub!==G)t=this.activeLink=new LS(G,this),G.deps?(t.prevDep=G.depsTail,G.depsTail.nextDep=t,G.depsTail=t):G.deps=G.depsTail=t,zS(t);else if(t.version===-1&&(t.version=this.version,t.nextDep)){let e=t.nextDep;e.prevDep=t.prevDep,t.prevDep&&(t.prevDep.nextDep=e),t.prevDep=G.depsTail,t.nextDep=void 0,G.depsTail.nextDep=t,G.depsTail=t,G.deps===t&&(G.deps=e)}return t}trigger(e){this.version++,IS++,this.notify(e)}notify(e){xS();try{for(let e=this.subs;e;e=e.prevSub)e.sub.notify()&&e.sub.dep.notify()}finally{SS()}}};function zS(e){if(e.dep.sc++,e.sub.flags&4){let t=e.dep.computed;if(t&&!e.dep.subs){t.flags|=20;for(let e=t.deps;e;e=e.nextDep)zS(e)}let n=e.dep.subs;n!==e&&(e.prevSub=n,n&&(n.nextSub=e)),e.dep.subs=e}}var BS=new WeakMap,VS=Symbol(``),HS=Symbol(``),US=Symbol(``);function WS(e,t,n){if(jS&&G){let t=BS.get(e);t||BS.set(e,t=new Map);let r=t.get(n);r||(t.set(n,r=new RS),r.map=t,r.key=n),r.track()}}function GS(e,t,n,r,i,a){let o=BS.get(e);if(!o){IS++;return}let s=e=>{e&&e.trigger()};if(xS(),t===`clear`)o.forEach(s);else{let i=Xx(e),a=i&&sS(n);if(i&&n===`length`){let e=Number(r);o.forEach((t,n)=>{(n===`length`||n===US||!tS(n)&&n>=e)&&s(t)})}else switch((n!==void 0||o.has(void 0))&&s(o.get(n)),a&&s(o.get(US)),t){case`add`:i?a&&s(o.get(`length`)):(s(o.get(VS)),Zx(e)&&s(o.get(HS)));break;case`delete`:i||(s(o.get(VS)),Zx(e)&&s(o.get(HS)));break;case`set`:Zx(e)&&s(o.get(VS));break}}SS()}function KS(e,t){let n=BS.get(e);return n&&n.get(t)}function qS(e){let t=K(e);return t===e?t:(WS(t,`iterate`,US),IC(e)?t:t.map(zC))}function JS(e){return WS(e=K(e),`iterate`,US),e}function YS(e,t){return FC(e)?BC(PC(e)?zC(t):t):zC(t)}var XS={__proto__:null,[Symbol.iterator](){return ZS(this,Symbol.iterator,e=>YS(this,e))},concat(...e){return qS(this).concat(...e.map(e=>Xx(e)?qS(e):e))},entries(){return ZS(this,`entries`,e=>(e[1]=YS(this,e[1]),e))},every(e,t){return $S(this,`every`,e,t,void 0,arguments)},filter(e,t){return $S(this,`filter`,e,t,e=>e.map(e=>YS(this,e)),arguments)},find(e,t){return $S(this,`find`,e,t,e=>YS(this,e),arguments)},findIndex(e,t){return $S(this,`findIndex`,e,t,void 0,arguments)},findLast(e,t){return $S(this,`findLast`,e,t,e=>YS(this,e),arguments)},findLastIndex(e,t){return $S(this,`findLastIndex`,e,t,void 0,arguments)},forEach(e,t){return $S(this,`forEach`,e,t,void 0,arguments)},includes(...e){return tC(this,`includes`,e)},indexOf(...e){return tC(this,`indexOf`,e)},join(e){return qS(this).join(e)},lastIndexOf(...e){return tC(this,`lastIndexOf`,e)},map(e,t){return $S(this,`map`,e,t,void 0,arguments)},pop(){return nC(this,`pop`)},push(...e){return nC(this,`push`,e)},reduce(e,...t){return eC(this,`reduce`,e,t)},reduceRight(e,...t){return eC(this,`reduceRight`,e,t)},shift(){return nC(this,`shift`)},some(e,t){return $S(this,`some`,e,t,void 0,arguments)},splice(...e){return nC(this,`splice`,e)},toReversed(){return qS(this).toReversed()},toSorted(e){return qS(this).toSorted(e)},toSpliced(...e){return qS(this).toSpliced(...e)},unshift(...e){return nC(this,`unshift`,e)},values(){return ZS(this,`values`,e=>YS(this,e))}};function ZS(e,t,n){let r=JS(e),i=r[t]();return r!==e&&!IC(e)&&(i._next=i.next,i.next=()=>{let e=i._next();return e.done||(e.value=n(e.value)),e}),i}var QS=Array.prototype;function $S(e,t,n,r,i,a){let o=JS(e),s=o!==e&&!IC(e),c=o[t];if(c!==QS[t]){let t=c.apply(e,a);return s?zC(t):t}let l=n;o!==e&&(s?l=function(t,r){return n.call(this,YS(e,t),r,e)}:n.length>2&&(l=function(t,r){return n.call(this,t,r,e)}));let u=c.call(o,l,r);return s&&i?i(u):u}function eC(e,t,n,r){let i=JS(e),a=n;return i!==e&&(IC(e)?n.length>3&&(a=function(t,r,i){return n.call(this,t,r,i,e)}):a=function(t,r,i){return n.call(this,t,YS(e,r),i,e)}),i[t](a,...r)}function tC(e,t,n){let r=K(e);WS(r,`iterate`,US);let i=r[t](...n);return(i===-1||i===!1)&&LC(n[0])?(n[0]=K(n[0]),r[t](...n)):i}function nC(e,t,n=[]){NS(),xS();let r=K(e)[t].apply(e,n);return SS(),PS(),r}var rC=Ux(`__proto__,__v_isRef,__isVue`),iC=new Set(Object.getOwnPropertyNames(Symbol).filter(e=>e!==`arguments`&&e!==`caller`).map(e=>Symbol[e]).filter(tS));function aC(e){tS(e)||(e=String(e));let t=K(this);return WS(t,`has`,e),t.hasOwnProperty(e)}var oC=class{constructor(e=!1,t=!1){this._isReadonly=e,this._isShallow=t}get(e,t,n){if(t===`__v_skip`)return e.__v_skip;let r=this._isReadonly,i=this._isShallow;if(t===`__v_isReactive`)return!r;if(t===`__v_isReadonly`)return r;if(t===`__v_isShallow`)return i;if(t===`__v_raw`)return n===(r?i?EC:TC:i?wC:CC).get(e)||Object.getPrototypeOf(e)===Object.getPrototypeOf(n)?e:void 0;let a=Xx(e);if(!r){let e;if(a&&(e=XS[t]))return e;if(t===`hasOwnProperty`)return aC}let o=Reflect.get(e,t,VC(e)?e:n);if((tS(t)?iC.has(t):rC(t))||(r||WS(e,`get`,t),i))return o;if(VC(o)){let e=a&&sS(t)?o:o.value;return r&&nS(e)?jC(e):e}return nS(o)?r?jC(o):kC(o):o}},sC=class extends oC{constructor(e=!1){super(!1,e)}set(e,t,n,r){let i=e[t],a=Xx(e)&&sS(t);if(!this._isShallow){let e=FC(i);if(!IC(n)&&!FC(n)&&(i=K(i),n=K(n)),!a&&VC(i)&&!VC(n))return e||(i.value=n),!0}let o=a?Number(t)<e.length:Yx(e,t),s=Reflect.set(e,t,n,VC(e)?e:r);return e===K(r)&&(o?cS(n,i)&&GS(e,`set`,t,n,i):GS(e,`add`,t,n)),s}deleteProperty(e,t){let n=Yx(e,t),r=e[t],i=Reflect.deleteProperty(e,t);return i&&n&&GS(e,`delete`,t,void 0,r),i}has(e,t){let n=Reflect.has(e,t);return(!tS(t)||!iC.has(t))&&WS(e,`has`,t),n}ownKeys(e){return WS(e,`iterate`,Xx(e)?`length`:VS),Reflect.ownKeys(e)}},cC=class extends oC{constructor(e=!1){super(!0,e)}set(e,t){return!0}deleteProperty(e,t){return!0}},lC=new sC,uC=new cC,dC=new sC(!0),fC=new cC(!0),pC=e=>e,mC=e=>Reflect.getPrototypeOf(e);function hC(e,t,n){return function(...r){let i=this.__v_raw,a=K(i),o=Zx(a),s=e===`entries`||e===Symbol.iterator&&o,c=e===`keys`&&o,l=i[e](...r),u=n?pC:t?BC:zC;return!t&&WS(a,`iterate`,c?HS:VS),Kx(Object.create(l),{next(){let{value:e,done:t}=l.next();return t?{value:e,done:t}:{value:s?[u(e[0]),u(e[1])]:u(e),done:t}}})}}function gC(e){return function(...t){return e===`delete`?!1:e===`clear`?void 0:this}}function _C(e,t){let n={get(n){let r=this.__v_raw,i=K(r),a=K(n);e||(cS(n,a)&&WS(i,`get`,n),WS(i,`get`,a));let{has:o}=mC(i),s=t?pC:e?BC:zC;if(o.call(i,n))return s(r.get(n));if(o.call(i,a))return s(r.get(a));r!==i&&r.get(n)},get size(){let t=this.__v_raw;return!e&&WS(K(t),`iterate`,VS),t.size},has(t){let n=this.__v_raw,r=K(n),i=K(t);return e||(cS(t,i)&&WS(r,`has`,t),WS(r,`has`,i)),t===i?n.has(t):n.has(t)||n.has(i)},forEach(n,r){let i=this,a=i.__v_raw,o=K(a),s=t?pC:e?BC:zC;return!e&&WS(o,`iterate`,VS),a.forEach((e,t)=>n.call(r,s(e),s(t),i))}};return Kx(n,e?{add:gC(`add`),set:gC(`set`),delete:gC(`delete`),clear:gC(`clear`)}:{add(e){!t&&!IC(e)&&!FC(e)&&(e=K(e));let n=K(this);return mC(n).has.call(n,e)||(n.add(e),GS(n,`add`,e,e)),this},set(e,n){!t&&!IC(n)&&!FC(n)&&(n=K(n));let r=K(this),{has:i,get:a}=mC(r),o=i.call(r,e);o||=(e=K(e),i.call(r,e));let s=a.call(r,e);return r.set(e,n),o?cS(n,s)&&GS(r,`set`,e,n,s):GS(r,`add`,e,n),this},delete(e){let t=K(this),{has:n,get:r}=mC(t),i=n.call(t,e);i||=(e=K(e),n.call(t,e));let a=r?r.call(t,e):void 0,o=t.delete(e);return i&&GS(t,`delete`,e,void 0,a),o},clear(){let e=K(this),t=e.size!==0,n=e.clear();return t&&GS(e,`clear`,void 0,void 0,void 0),n}}),[`keys`,`values`,`entries`,Symbol.iterator].forEach(r=>{n[r]=hC(r,e,t)}),n}function vC(e,t){let n=_C(e,t);return(t,r,i)=>r===`__v_isReactive`?!e:r===`__v_isReadonly`?e:r===`__v_raw`?t:Reflect.get(Yx(n,r)&&r in t?n:t,r,i)}var yC={get:vC(!1,!1)},bC={get:vC(!1,!0)},xC={get:vC(!0,!1)},SC={get:vC(!0,!0)},CC=new WeakMap,wC=new WeakMap,TC=new WeakMap,EC=new WeakMap;function DC(e){switch(e){case`Object`:case`Array`:return 1;case`Map`:case`Set`:case`WeakMap`:case`WeakSet`:return 2;default:return 0}}function OC(e){return e.__v_skip||!Object.isExtensible(e)?0:DC(aS(e))}function kC(e){return FC(e)?e:NC(e,!1,lC,yC,CC)}function AC(e){return NC(e,!1,dC,bC,wC)}function jC(e){return NC(e,!0,uC,xC,TC)}function MC(e){return NC(e,!0,fC,SC,EC)}function NC(e,t,n,r,i){if(!nS(e)||e.__v_raw&&!(t&&e.__v_isReactive))return e;let a=OC(e);if(a===0)return e;let o=i.get(e);if(o)return o;let s=new Proxy(e,a===2?r:n);return i.set(e,s),s}function PC(e){return FC(e)?PC(e.__v_raw):!!(e&&e.__v_isReactive)}function FC(e){return!!(e&&e.__v_isReadonly)}function IC(e){return!!(e&&e.__v_isShallow)}function LC(e){return e?!!e.__v_raw:!1}function K(e){let t=e&&e.__v_raw;return t?K(t):e}function RC(e){return!Yx(e,`__v_skip`)&&Object.isExtensible(e)&&lS(e,`__v_skip`,!0),e}var zC=e=>nS(e)?kC(e):e,BC=e=>nS(e)?jC(e):e;function VC(e){return e?e.__v_isRef===!0:!1}function HC(e){return WC(e,!1)}function UC(e){return WC(e,!0)}function WC(e,t){return VC(e)?e:new GC(e,t)}var GC=class{constructor(e,t){this.dep=new RS,this.__v_isRef=!0,this.__v_isShallow=!1,this._rawValue=t?e:K(e),this._value=t?e:zC(e),this.__v_isShallow=t}get value(){return this.dep.track(),this._value}set value(e){let t=this._rawValue,n=this.__v_isShallow||IC(e)||FC(e);e=n?e:K(e),cS(e,t)&&(this._rawValue=e,this._value=n?e:zC(e),this.dep.trigger())}};function KC(e){e.dep&&e.dep.trigger()}function qC(e){return VC(e)?e.value:e}function JC(e){return $x(e)?e():qC(e)}var YC={get:(e,t,n)=>t===`__v_raw`?e:qC(Reflect.get(e,t,n)),set:(e,t,n,r)=>{let i=e[t];return VC(i)&&!VC(n)?(i.value=n,!0):Reflect.set(e,t,n,r)}};function XC(e){return PC(e)?e:new Proxy(e,YC)}var ZC=class{constructor(e){this.__v_isRef=!0,this._value=void 0;let t=this.dep=new RS,{get:n,set:r}=e(t.track.bind(t),t.trigger.bind(t));this._get=n,this._set=r}get value(){return this._value=this._get()}set value(e){this._set(e)}};function QC(e){return new ZC(e)}function $C(e){let t=Xx(e)?Array(e.length):{};for(let n in e)t[n]=rw(e,n);return t}var ew=class{constructor(e,t,n){this._object=e,this._key=t,this._defaultValue=n,this.__v_isRef=!0,this._value=void 0,this._raw=K(e);let r=!0,i=e;if(!Xx(e)||!sS(String(t)))do r=!LC(i)||IC(i);while(r&&(i=i.__v_raw));this._shallow=r}get value(){let e=this._object[this._key];return this._shallow&&(e=qC(e)),this._value=e===void 0?this._defaultValue:e}set value(e){if(this._shallow&&VC(this._raw[this._key])){let t=this._object[this._key];if(VC(t)){t.value=e;return}}this._object[this._key]=e}get dep(){return KS(this._raw,this._key)}},tw=class{constructor(e){this._getter=e,this.__v_isRef=!0,this.__v_isReadonly=!0,this._value=void 0}get value(){return this._value=this._getter()}};function nw(e,t,n){return VC(e)?e:$x(e)?new tw(e):nS(e)&&arguments.length>1?rw(e,t,n):HC(e)}function rw(e,t,n){return new ew(e,t,n)}var iw=class{constructor(e,t,n){this.fn=e,this.setter=t,this._value=void 0,this.dep=new RS(this),this.__v_isRef=!0,this.deps=void 0,this.depsTail=void 0,this.flags=16,this.globalVersion=IS-1,this.next=void 0,this.effect=this,this.__v_isReadonly=!t,this.isSSR=n}notify(){if(this.flags|=16,!(this.flags&8)&&G!==this)return bS(this,!0),!0}get value(){let e=this.dep.track();return ES(this),e&&(e.version=this.dep.version),this._value}set value(e){this.setter&&this.setter(e)}};function aw(e,t,n=!1){let r,i;return $x(e)?r=e:(r=e.get,i=e.set),new iw(r,i,n)}var ow={GET:`get`,HAS:`has`,ITERATE:`iterate`},sw={SET:`set`,ADD:`add`,DELETE:`delete`,CLEAR:`clear`},cw={},lw=new WeakMap,uw=void 0;function dw(){return uw}function fw(e,t=!1,n=uw){if(n){let t=lw.get(n);t||lw.set(n,t=[]),t.push(e)}}function pw(e,t,n=Wx){let{immediate:r,deep:i,once:a,scheduler:o,augmentJob:s,call:c}=n,l=e=>i?e:IC(e)||i===!1||i===0?mw(e,1):mw(e),u,d,f,p,m=!1,h=!1;if(VC(e)?(d=()=>e.value,m=IC(e)):PC(e)?(d=()=>l(e),m=!0):Xx(e)?(h=!0,m=e.some(e=>PC(e)||IC(e)),d=()=>e.map(e=>{if(VC(e))return e.value;if(PC(e))return l(e);if($x(e))return c?c(e,2):e()})):d=$x(e)?t?c?()=>c(e,2):e:()=>{if(f){NS();try{f()}finally{PS()}}let t=uw;uw=u;try{return c?c(e,3,[p]):e(p)}finally{uw=t}}:Gx,t&&i){let e=d,t=i===!0?1/0:i;d=()=>mw(e(),t)}let g=pS(),_=()=>{u.stop(),g&&g.active&&qx(g.effects,u)};if(a&&t){let e=t;t=(...t)=>{e(...t),_()}}let v=h?Array(e.length).fill(cw):cw,y=e=>{if(!(!(u.flags&1)||!u.dirty&&!e))if(t){let e=u.run();if(i||m||(h?e.some((e,t)=>cS(e,v[t])):cS(e,v))){f&&f();let n=uw;uw=u;try{let n=[e,v===cw?void 0:h&&v[0]===cw?[]:v,p];v=e,c?c(t,3,n):t(...n)}finally{uw=n}}}else u.run()};return s&&s(y),u=new gS(d),u.scheduler=o?()=>o(y,!1):y,p=e=>fw(e,!1,u),f=u.onStop=()=>{let e=lw.get(u);if(e){if(c)c(e,4);else for(let t of e)t();lw.delete(u)}},t?r?y(!0):v=u.run():o?o(y.bind(null,!0),!0):u.run(),_.pause=u.pause.bind(u),_.resume=u.resume.bind(u),_.stop=_,_}function mw(e,t=1/0,n){if(t<=0||!nS(e)||e.__v_skip||(n||=new Map,(n.get(e)||0)>=t))return e;if(n.set(e,t),t--,VC(e))mw(e.value,t,n);else if(Xx(e))for(let r=0;r<e.length;r++)mw(e[r],t,n);else if(Qx(e)||Zx(e))e.forEach(e=>{mw(e,t,n)});else if(oS(e)){for(let r in e)mw(e[r],t,n);for(let r of Object.getOwnPropertySymbols(e))Object.prototype.propertyIsEnumerable.call(e,r)&&mw(e[r],t,n)}return e}function hw(e){let t=Object.create(null);for(let n of e.split(`,`))t[n]=1;return e=>e in t}var q={},gw=[],_w=()=>{},vw=()=>!1,yw=e=>e.charCodeAt(0)===111&&e.charCodeAt(1)===110&&(e.charCodeAt(2)>122||e.charCodeAt(2)<97),bw=e=>e.startsWith(`onUpdate:`),xw=Object.assign,Sw=(e,t)=>{let n=e.indexOf(t);n>-1&&e.splice(n,1)},Cw=Object.prototype.hasOwnProperty,ww=(e,t)=>Cw.call(e,t),J=Array.isArray,Tw=e=>Nw(e)===`[object Map]`,Ew=e=>Nw(e)===`[object Set]`,Dw=e=>Nw(e)===`[object RegExp]`,Y=e=>typeof e==`function`,Ow=e=>typeof e==`string`,kw=e=>typeof e==`symbol`,Aw=e=>typeof e==`object`&&!!e,jw=e=>(Aw(e)||Y(e))&&Y(e.then)&&Y(e.catch),Mw=Object.prototype.toString,Nw=e=>Mw.call(e),Pw=e=>Nw(e)===`[object Object]`,Fw=hw(`,key,ref,ref_for,ref_key,onVnodeBeforeMount,onVnodeMounted,onVnodeBeforeUpdate,onVnodeUpdated,onVnodeBeforeUnmount,onVnodeUnmounted`),Iw=e=>{let t=Object.create(null);return(n=>t[n]||(t[n]=e(n)))},Lw=/-\w/g,Rw=Iw(e=>e.replace(Lw,e=>e.slice(1).toUpperCase())),zw=/\B([A-Z])/g,Bw=Iw(e=>e.replace(zw,`-$1`).toLowerCase()),Vw=Iw(e=>e.charAt(0).toUpperCase()+e.slice(1)),Hw=Iw(e=>e?`on${Vw(e)}`:``),Uw=(e,t)=>!Object.is(e,t),Ww=(e,...t)=>{for(let n=0;n<e.length;n++)e[n](...t)},Gw=(e,t,n,r=!1)=>{Object.defineProperty(e,t,{configurable:!0,enumerable:!1,writable:r,value:n})},Kw=e=>{let t=parseFloat(e);return isNaN(t)?e:t},qw=e=>{let t=Ow(e)?Number(e):NaN;return isNaN(t)?e:t},Jw,Yw=()=>Jw||=typeof globalThis<`u`?globalThis:typeof self<`u`?self:typeof window<`u`?window:typeof global<`u`?global:{},Xw=hw(`Infinity,undefined,NaN,isFinite,isNaN,parseFloat,parseInt,decodeURI,decodeURIComponent,encodeURI,encodeURIComponent,Math,Number,Date,Array,Object,Boolean,String,RegExp,Map,Set,JSON,Intl,BigInt,console,Error,Symbol`);function Zw(e){if(J(e)){let t={};for(let n=0;n<e.length;n++){let r=e[n],i=Ow(r)?tT(r):Zw(r);if(i)for(let e in i)t[e]=i[e]}return t}else if(Ow(e)||Aw(e))return e}var Qw=/;(?![^(]*\))/g,$w=/:([^]+)/,eT=/\/\*[^]*?\*\//g;function tT(e){let t={};return e.replace(eT,``).split(Qw).forEach(e=>{if(e){let n=e.split($w);n.length>1&&(t[n[0].trim()]=n[1].trim())}}),t}function nT(e){let t=``;if(Ow(e))t=e;else if(J(e))for(let n=0;n<e.length;n++){let r=nT(e[n]);r&&(t+=r+` `)}else if(Aw(e))for(let n in e)e[n]&&(t+=n+` `);return t.trim()}function rT(e){if(!e)return null;let{class:t,style:n}=e;return t&&!Ow(t)&&(e.class=nT(t)),n&&(e.style=Zw(n)),e}var iT=e=>!!(e&&e.__v_isRef===!0),aT=e=>Ow(e)?e:e==null?``:J(e)||Aw(e)&&(e.toString===Mw||!Y(e.toString))?iT(e)?aT(e.value):JSON.stringify(e,oT,2):String(e),oT=(e,t)=>iT(t)?oT(e,t.value):Tw(t)?{[`Map(${t.size})`]:[...t.entries()].reduce((e,[t,n],r)=>(e[sT(t,r)+` =>`]=n,e),{})}:Ew(t)?{[`Set(${t.size})`]:[...t.values()].map(e=>sT(e))}:kw(t)?sT(t):Aw(t)&&!J(t)&&!Pw(t)?String(t):t,sT=(e,t=``)=>kw(e)?`Symbol(${e.description??t})`:e,cT=[];function lT(e){cT.push(e)}function uT(){cT.pop()}function dT(e,t){}var fT={SETUP_FUNCTION:0,0:`SETUP_FUNCTION`,RENDER_FUNCTION:1,1:`RENDER_FUNCTION`,NATIVE_EVENT_HANDLER:5,5:`NATIVE_EVENT_HANDLER`,COMPONENT_EVENT_HANDLER:6,6:`COMPONENT_EVENT_HANDLER`,VNODE_HOOK:7,7:`VNODE_HOOK`,DIRECTIVE_HOOK:8,8:`DIRECTIVE_HOOK`,TRANSITION_HOOK:9,9:`TRANSITION_HOOK`,APP_ERROR_HANDLER:10,10:`APP_ERROR_HANDLER`,APP_WARN_HANDLER:11,11:`APP_WARN_HANDLER`,FUNCTION_REF:12,12:`FUNCTION_REF`,ASYNC_COMPONENT_LOADER:13,13:`ASYNC_COMPONENT_LOADER`,SCHEDULER:14,14:`SCHEDULER`,COMPONENT_UPDATE:15,15:`COMPONENT_UPDATE`,APP_UNMOUNT_CLEANUP:16,16:`APP_UNMOUNT_CLEANUP`},pT={sp:`serverPrefetch hook`,bc:`beforeCreate hook`,c:`created hook`,bm:`beforeMount hook`,m:`mounted hook`,bu:`beforeUpdate hook`,u:`updated`,bum:`beforeUnmount hook`,um:`unmounted hook`,a:`activated hook`,da:`deactivated hook`,ec:`errorCaptured hook`,rtc:`renderTracked hook`,rtg:`renderTriggered hook`,0:`setup function`,1:`render function`,2:`watcher getter`,3:`watcher callback`,4:`watcher cleanup function`,5:`native event handler`,6:`component event handler`,7:`vnode hook`,8:`directive hook`,9:`transition hook`,10:`app errorHandler`,11:`app warnHandler`,12:`ref function`,13:`async component loader`,14:`scheduler flush`,15:`component update`,16:`app unmount cleanup function`};function mT(e,t,n,r){try{return r?e(...r):e()}catch(e){gT(e,t,n)}}function hT(e,t,n,r){if(Y(e)){let i=mT(e,t,n,r);return i&&jw(i)&&i.catch(e=>{gT(e,t,n)}),i}if(J(e)){let i=[];for(let a=0;a<e.length;a++)i.push(hT(e[a],t,n,r));return i}}function gT(e,t,n,r=!0){let i=t?t.vnode:null,{errorHandler:a,throwUnhandledErrorInProduction:o}=t&&t.appContext.config||q;if(t){let r=t.parent,i=t.proxy,o=`https://vuejs.org/error-reference/#runtime-${n}`;for(;r;){let t=r.ec;if(t){for(let n=0;n<t.length;n++)if(t[n](e,i,o)===!1)return}r=r.parent}if(a){NS(),mT(a,null,10,[e,i,o]),PS();return}}_T(e,n,i,r,o)}function _T(e,t,n,r=!0,i=!1){if(i)throw e;console.error(e)}var vT=[],yT=-1,bT=[],xT=null,ST=0,CT=Promise.resolve(),wT=null;function TT(e){let t=wT||CT;return e?t.then(this?e.bind(this):e):t}function ET(e){let t=yT+1,n=vT.length;for(;t<n;){let r=t+n>>>1,i=vT[r],a=MT(i);a<e||a===e&&i.flags&2?t=r+1:n=r}return t}function DT(e){if(!(e.flags&1)){let t=MT(e),n=vT[vT.length-1];!n||!(e.flags&2)&&t>=MT(n)?vT.push(e):vT.splice(ET(t),0,e),e.flags|=1,OT()}}function OT(){wT||=CT.then(NT)}function kT(e){J(e)?bT.push(...e):xT&&e.id===-1?xT.splice(ST+1,0,e):e.flags&1||(bT.push(e),e.flags|=1),OT()}function AT(e,t,n=yT+1){for(;n<vT.length;n++){let t=vT[n];if(t&&t.flags&2){if(e&&t.id!==e.uid)continue;vT.splice(n,1),n--,t.flags&4&&(t.flags&=-2),t(),t.flags&4||(t.flags&=-2)}}}function jT(e){if(bT.length){let e=[...new Set(bT)].sort((e,t)=>MT(e)-MT(t));if(bT.length=0,xT){xT.push(...e);return}for(xT=e,ST=0;ST<xT.length;ST++){let e=xT[ST];e.flags&4&&(e.flags&=-2),e.flags&8||e(),e.flags&=-2}xT=null,ST=0}}var MT=e=>e.id==null?e.flags&2?-1:1/0:e.id;function NT(e){try{for(yT=0;yT<vT.length;yT++){let e=vT[yT];e&&!(e.flags&8)&&(e.flags&4&&(e.flags&=-2),mT(e,e.i,e.i?15:14),e.flags&4||(e.flags&=-2))}}finally{for(;yT<vT.length;yT++){let e=vT[yT];e&&(e.flags&=-2)}yT=-1,vT.length=0,jT(e),wT=null,(vT.length||bT.length)&&NT(e)}}var PT,FT=[];function IT(e,t){PT=e,PT?(PT.enabled=!0,FT.forEach(({event:e,args:t})=>PT.emit(e,...t)),FT=[]):typeof window<`u`&&window.HTMLElement&&!(window.navigator?.userAgent)?.includes(`jsdom`)?((t.__VUE_DEVTOOLS_HOOK_REPLAY__=t.__VUE_DEVTOOLS_HOOK_REPLAY__||[]).push(e=>{IT(e,t)}),setTimeout(()=>{PT||(t.__VUE_DEVTOOLS_HOOK_REPLAY__=null,FT=[])},3e3)):FT=[]}var LT=null,RT=null;function zT(e){let t=LT;return LT=e,RT=e&&e.type.__scopeId||null,t}function BT(e){RT=e}function VT(){RT=null}var HT=e=>UT;function UT(e,t=LT,n){if(!t||e._n)return e;let r=(...n)=>{r._d&&Ik(-1);let i=zT(t),a;try{a=e(...n)}finally{zT(i),r._d&&Ik(1)}return a};return r._n=!0,r._c=!0,r._d=!0,r}function WT(e,t){if(LT===null)return e;let n=TA(LT),r=e.dirs||=[];for(let e=0;e<t.length;e++){let[i,a,o,s=q]=t[e];i&&(Y(i)&&(i={mounted:i,updated:i}),i.deep&&mw(a),r.push({dir:i,instance:n,value:a,oldValue:void 0,arg:o,modifiers:s}))}return e}function GT(e,t,n,r){let i=e.dirs,a=t&&t.dirs;for(let o=0;o<i.length;o++){let s=i[o];a&&(s.oldValue=a[o].value);let c=s.dir[r];c&&(NS(),hT(c,n,8,[e.el,s,e,t]),PS())}}function KT(e,t){if(sA){let n=sA.provides,r=sA.parent&&sA.parent.provides;r===n&&(n=sA.provides=Object.create(r)),n[e]=t}}function qT(e,t,n=!1){let r=cA();if(r||wO){let i=wO?wO._context.provides:r?r.parent==null||r.ce?r.vnode.appContext&&r.vnode.appContext.provides:r.parent.provides:void 0;if(i&&e in i)return i[e];if(arguments.length>1)return n&&Y(t)?t.call(r&&r.proxy):t}}function JT(){return!!(cA()||wO)}var YT=Symbol.for(`v-scx`),XT=()=>qT(YT);function ZT(e,t){return tE(e,null,t)}function QT(e,t){return tE(e,null,{flush:`post`})}function $T(e,t){return tE(e,null,{flush:`sync`})}function eE(e,t,n){return tE(e,t,n)}function tE(e,t,n=q){let{immediate:r,deep:i,flush:a,once:o}=n,s=xw({},n),c=t&&r||!t&&a!==`post`,l;if(mA){if(a===`sync`){let e=XT();l=e.__watcherHandles||=[]}else if(!c){let e=()=>{};return e.stop=_w,e.resume=_w,e.pause=_w,e}}let u=sA;s.call=(e,t,n)=>hT(e,u,t,n);let d=!1;a===`post`?s.scheduler=e=>{nk(e,u&&u.suspense)}:a!==`sync`&&(d=!0,s.scheduler=(e,t)=>{t?e():DT(e)}),s.augmentJob=e=>{t&&(e.flags|=4),d&&(e.flags|=2,u&&(e.id=u.uid,e.i=u))};let f=pw(e,t,s);return mA&&(l?l.push(f):c&&f()),f}function nE(e,t,n){let r=this.proxy,i=Ow(e)?e.includes(`.`)?rE(r,e):()=>r[e]:e.bind(r,r),a;Y(t)?a=t:(a=t.handler,n=t);let o=dA(this),s=tE(i,a.bind(r),n);return o(),s}function rE(e,t){let n=t.split(`.`);return()=>{let t=e;for(let e=0;e<n.length&&t;e++)t=t[n[e]];return t}}var iE=Symbol(`_vte`),aE=e=>e.__isTeleport,oE=e=>e&&(e.disabled||e.disabled===``),sE=e=>e&&(e.defer||e.defer===``),cE=e=>typeof SVGElement<`u`&&e instanceof SVGElement,lE=e=>typeof MathMLElement==`function`&&e instanceof MathMLElement,uE=(e,t)=>{let n=e&&e.to;return Ow(n)?t?t(n):null:n},dE={name:`Teleport`,__isTeleport:!0,process(e,t,n,r,i,a,o,s,c,l){let{mc:u,pc:d,pbc:f,o:{insert:p,querySelector:m,createText:h,createComment:g}}=l,_=oE(t.props),{shapeFlag:v,children:y,dynamicChildren:b}=t;if(e==null){let e=t.el=h(``),l=t.anchor=h(``);p(e,n,r),p(l,n,r);let d=(e,t)=>{v&16&&u(y,e,t,i,a,o,s,c)},f=()=>{let e=t.target=uE(t.props,m),n=gE(e,t,h,p);e&&(o!==`svg`&&cE(e)?o=`svg`:o!==`mathml`&&lE(e)&&(o=`mathml`),i&&i.isCE&&(i.ce._teleportTargets||(i.ce._teleportTargets=new Set)).add(e),_||(d(e,n),hE(t,!1)))};_&&(d(n,l),hE(t,!0)),sE(t.props)?(t.el.__isMounted=!1,nk(()=>{f(),delete t.el.__isMounted},a)):f()}else{if(sE(t.props)&&e.el.__isMounted===!1){nk(()=>{dE.process(e,t,n,r,i,a,o,s,c,l)},a);return}t.el=e.el,t.targetStart=e.targetStart;let u=t.anchor=e.anchor,p=t.target=e.target,h=t.targetAnchor=e.targetAnchor,g=oE(e.props),v=g?n:p,y=g?u:h;if(o===`svg`||cE(p)?o=`svg`:(o===`mathml`||lE(p))&&(o=`mathml`),b?(f(e.dynamicChildren,b,v,i,a,o,s),lk(e,t,!0)):c||d(e,t,v,y,i,a,o,s,!1),_)g?t.props&&e.props&&t.props.to!==e.props.to&&(t.props.to=e.props.to):fE(t,n,u,l,1);else if((t.props&&t.props.to)!==(e.props&&e.props.to)){let e=t.target=uE(t.props,m);e&&fE(t,e,null,l,0)}else g&&fE(t,p,h,l,1);hE(t,_)}},remove(e,t,n,{um:r,o:{remove:i}},a){let{shapeFlag:o,children:s,anchor:c,targetStart:l,targetAnchor:u,target:d,props:f}=e;if(d&&(i(l),i(u)),a&&i(c),o&16){let e=a||!oE(f);for(let i=0;i<s.length;i++){let a=s[i];r(a,t,n,e,!!a.dynamicChildren)}}},move:fE,hydrate:pE};function fE(e,t,n,{o:{insert:r},m:i},a=2){a===0&&r(e.targetAnchor,t,n);let{el:o,anchor:s,shapeFlag:c,children:l,props:u}=e,d=a===2;if(d&&r(o,t,n),(!d||oE(u))&&c&16)for(let e=0;e<l.length;e++)i(l[e],t,n,2);d&&r(s,t,n)}function pE(e,t,n,r,i,a,{o:{nextSibling:o,parentNode:s,querySelector:c,insert:l,createText:u}},d){function f(e,t,c,l){t.anchor=d(o(e),t,s(e),n,r,i,a),t.targetStart=c,t.targetAnchor=l}let p=t.target=uE(t.props,c),m=oE(t.props);if(p){let s=p._lpa||p.firstChild;if(t.shapeFlag&16)if(m)f(e,t,s,s&&o(s));else{t.anchor=o(e);let c=s;for(;c;){if(c&&c.nodeType===8){if(c.data===`teleport start anchor`)t.targetStart=c;else if(c.data===`teleport anchor`){t.targetAnchor=c,p._lpa=t.targetAnchor&&o(t.targetAnchor);break}}c=o(c)}t.targetAnchor||gE(p,t,u,l),d(s&&o(s),t,p,n,r,i,a)}hE(t,m)}else m&&t.shapeFlag&16&&f(e,t,e,o(e));return t.anchor&&o(t.anchor)}var mE=dE;function hE(e,t){let n=e.ctx;if(n&&n.ut){let r,i;for(t?(r=e.el,i=e.anchor):(r=e.targetStart,i=e.targetAnchor);r&&r!==i;)r.nodeType===1&&r.setAttribute(`data-v-owner`,n.uid),r=r.nextSibling;n.ut()}}function gE(e,t,n,r){let i=t.targetStart=n(``),a=t.targetAnchor=n(``);return i[iE]=a,e&&(r(i,e),r(a,e)),a}var _E=Symbol(`_leaveCb`),vE=Symbol(`_enterCb`);function yE(){let e={isMounted:!1,isLeaving:!1,isUnmounting:!1,leavingVNodes:new Map};return vD(()=>{e.isMounted=!0}),xD(()=>{e.isUnmounting=!0}),e}var bE=[Function,Array],xE={mode:String,appear:Boolean,persisted:Boolean,onBeforeEnter:bE,onEnter:bE,onAfterEnter:bE,onEnterCancelled:bE,onBeforeLeave:bE,onLeave:bE,onAfterLeave:bE,onLeaveCancelled:bE,onBeforeAppear:bE,onAppear:bE,onAfterAppear:bE,onAppearCancelled:bE},SE=e=>{let t=e.subTree;return t.component?SE(t.component):t},CE={name:`BaseTransition`,props:xE,setup(e,{slots:t}){let n=cA(),r=yE();return()=>{let i=t.default&&jE(t.default(),!0);if(!i||!i.length)return;let a=wE(i),o=K(e),{mode:s}=o;if(r.isLeaving)return OE(a);let c=kE(a);if(!c)return OE(a);let l=DE(c,o,r,n,e=>l=e);c.type!==kk&&AE(c,l);let u=n.subTree&&kE(n.subTree);if(u&&u.type!==kk&&!Vk(u,c)&&SE(n).type!==kk){let e=DE(u,o,r,n);if(AE(u,e),s===`out-in`&&c.type!==kk)return r.isLeaving=!0,e.afterLeave=()=>{r.isLeaving=!1,n.job.flags&8||n.update(),delete e.afterLeave,u=void 0},OE(a);s===`in-out`&&c.type!==kk?e.delayLeave=(e,t,n)=>{let i=EE(r,u);i[String(u.key)]=u,e[_E]=()=>{t(),e[_E]=void 0,delete l.delayedLeave,u=void 0},l.delayedLeave=()=>{n(),delete l.delayedLeave,u=void 0}}:u=void 0}else u&&=void 0;return a}}};function wE(e){let t=e[0];if(e.length>1){for(let n of e)if(n.type!==kk){t=n;break}}return t}var TE=CE;function EE(e,t){let{leavingVNodes:n}=e,r=n.get(t.type);return r||(r=Object.create(null),n.set(t.type,r)),r}function DE(e,t,n,r,i){let{appear:a,mode:o,persisted:s=!1,onBeforeEnter:c,onEnter:l,onAfterEnter:u,onEnterCancelled:d,onBeforeLeave:f,onLeave:p,onAfterLeave:m,onLeaveCancelled:h,onBeforeAppear:g,onAppear:_,onAfterAppear:v,onAppearCancelled:y}=t,b=String(e.key),x=EE(n,e),S=(e,t)=>{e&&hT(e,r,9,t)},C=(e,t)=>{let n=t[1];S(e,t),J(e)?e.every(e=>e.length<=1)&&n():e.length<=1&&n()},w={mode:o,persisted:s,beforeEnter(t){let r=c;if(!n.isMounted)if(a)r=g||c;else return;t[_E]&&t[_E](!0);let i=x[b];i&&Vk(e,i)&&i.el[_E]&&i.el[_E](),S(r,[t])},enter(e){let t=l,r=u,i=d;if(!n.isMounted)if(a)t=_||l,r=v||u,i=y||d;else return;let o=!1,s=e[vE]=t=>{o||(o=!0,S(t?i:r,[e]),w.delayedLeave&&w.delayedLeave(),e[vE]=void 0)};t?C(t,[e,s]):s()},leave(t,r){let i=String(e.key);if(t[vE]&&t[vE](!0),n.isUnmounting)return r();S(f,[t]);let a=!1,o=t[_E]=n=>{a||(a=!0,r(),S(n?h:m,[t]),t[_E]=void 0,x[i]===e&&delete x[i])};x[i]=e,p?C(p,[t,o]):o()},clone(e){let a=DE(e,t,n,r,i);return i&&i(a),a}};return w}function OE(e){if(oD(e))return e=Yk(e),e.children=null,e}function kE(e){if(!oD(e))return aE(e.type)&&e.children?wE(e.children):e;if(e.component)return e.component.subTree;let{shapeFlag:t,children:n}=e;if(n){if(t&16)return n[0];if(t&32&&Y(n.default))return n.default()}}function AE(e,t){e.shapeFlag&6&&e.component?(e.transition=t,AE(e.component.subTree,t)):e.shapeFlag&128?(e.ssContent.transition=t.clone(e.ssContent),e.ssFallback.transition=t.clone(e.ssFallback)):e.transition=t}function jE(e,t=!1,n){let r=[],i=0;for(let a=0;a<e.length;a++){let o=e[a],s=n==null?o.key:String(n)+String(o.key==null?a:o.key);o.type===Dk?(o.patchFlag&128&&i++,r=r.concat(jE(o.children,t,s))):(t||o.type!==kk)&&r.push(s==null?o:Yk(o,{key:s}))}if(i>1)for(let e=0;e<r.length;e++)r[e].patchFlag=-2;return r}function ME(e,t){return Y(e)?xw({name:e.name},t,{setup:e}):e}function NE(){let e=cA();return e?(e.appContext.config.idPrefix||`v`)+`-`+e.ids[0]+ e.ids[1]++:``}function PE(e){e.ids=[e.ids[0]+ e.ids[2]+++`-`,0,0]}function FE(e){let t=cA(),n=UC(null);if(t){let r=t.refs===q?t.refs={}:t.refs;Object.defineProperty(r,e,{enumerable:!0,get:()=>n.value,set:e=>n.value=e})}return n}var IE=new WeakMap;function LE(e,t,n,r,i=!1){if(J(e)){e.forEach((e,a)=>LE(e,t&&(J(t)?t[a]:t),n,r,i));return}if(rD(r)&&!i){r.shapeFlag&512&&r.type.__asyncResolved&&r.component.subTree.component&&LE(e,t,n,r.component.subTree);return}let a=r.shapeFlag&4?TA(r.component):r.el,o=i?null:a,{i:s,r:c}=e,l=t&&t.r,u=s.refs===q?s.refs={}:s.refs,d=s.setupState,f=K(d),p=d===q?vw:e=>ww(f,e),m=e=>!0;if(l!=null&&l!==c){if(RE(t),Ow(l))u[l]=null,p(l)&&(d[l]=null);else if(VC(l)){m(l)&&(l.value=null);let e=t;e.k&&(u[e.k]=null)}}if(Y(c))mT(c,s,12,[o,u]);else{let t=Ow(c),r=VC(c);if(t||r){let s=()=>{if(e.f){let n=t?p(c)?d[c]:u[c]:m(c)||!e.k?c.value:u[e.k];if(i)J(n)&&Sw(n,a);else if(J(n))n.includes(a)||n.push(a);else if(t)u[c]=[a],p(c)&&(d[c]=u[c]);else{let t=[a];m(c)&&(c.value=t),e.k&&(u[e.k]=t)}}else t?(u[c]=o,p(c)&&(d[c]=o)):r&&(m(c)&&(c.value=o),e.k&&(u[e.k]=o))};if(o){let t=()=>{s(),IE.delete(e)};t.id=-1,IE.set(e,t),nk(t,n)}else RE(e),s()}}}function RE(e){let t=IE.get(e);t&&(t.flags|=8,IE.delete(e))}var zE=!1,BE=()=>{zE||=(console.error(`Hydration completed but contains mismatches.`),!0)},VE=e=>e.namespaceURI.includes(`svg`)&&e.tagName!==`foreignObject`,HE=e=>e.namespaceURI.includes(`MathML`),UE=e=>{if(e.nodeType===1){if(VE(e))return`svg`;if(HE(e))return`mathml`}},WE=e=>e.nodeType===8;function GE(e){let{mt:t,p:n,o:{patchProp:r,createText:i,nextSibling:a,parentNode:o,remove:s,insert:c,createComment:l}}=e,u=(e,t)=>{if(!t.hasChildNodes()){n(null,e,t),jT(),t._vnode=e;return}d(t.firstChild,e,null,null,null),jT(),t._vnode=e},d=(n,r,s,l,u,y=!1)=>{y||=!!r.dynamicChildren;let b=WE(n)&&n.data===`[`,x=()=>h(n,r,s,l,u,b),{type:S,ref:C,shapeFlag:w,patchFlag:T}=r,E=n.nodeType;r.el=n,T===-2&&(y=!1,r.dynamicChildren=null);let D=null;switch(S){case Ok:E===3?(n.data!==r.children&&(BE(),n.data=r.children),D=a(n)):r.children===``?(c(r.el=i(``),o(n),n),D=n):D=x();break;case kk:v(n)?(D=a(n),_(r.el=n.content.firstChild,n,s)):D=E!==8||b?x():a(n);break;case Ak:if(b&&(n=a(n),E=n.nodeType),E===1||E===3){D=n;let e=!r.children.length;for(let t=0;t<r.staticCount;t++)e&&(r.children+=D.nodeType===1?D.outerHTML:D.data),t===r.staticCount-1&&(r.anchor=D),D=a(D);return b?a(D):D}else x();break;case Dk:D=b?m(n,r,s,l,u,y):x();break;default:if(w&1)D=(E!==1||r.type.toLowerCase()!==n.tagName.toLowerCase())&&!v(n)?x():f(n,r,s,l,u,y);else if(w&6){r.slotScopeIds=u;let e=o(n);if(D=b?g(n):WE(n)&&n.data===`teleport start`?g(n,n.data,`teleport end`):a(n),t(r,e,null,s,l,UE(e),y),rD(r)&&!r.type.__asyncResolved){let t;b?(t=Kk(Dk),t.anchor=D?D.previousSibling:e.lastChild):t=n.nodeType===3?Xk(``):Kk(`div`),t.el=n,r.component.subTree=t}}else w&64?D=E===8?r.type.hydrate(n,r,s,l,u,y,e,p):x():w&128&&(D=r.type.hydrate(n,r,s,l,UE(o(n)),u,y,e,d))}return C!=null&&LE(C,null,l,r),D},f=(e,t,n,i,a,o)=>{o||=!!t.dynamicChildren;let{type:c,props:l,patchFlag:u,shapeFlag:d,dirs:f,transition:m}=t,h=c===`input`||c===`option`;if(h||u!==-1){f&&GT(t,null,n,`created`);let c=!1;if(v(e)){c=ck(null,m)&&n&&n.vnode.props&&n.vnode.props.appear;let r=e.content.firstChild;if(c){let e=r.getAttribute(`class`);e&&(r.$cls=e),m.beforeEnter(r)}_(r,e,n),t.el=e=r}if(d&16&&!(l&&(l.innerHTML||l.textContent))){let r=p(e.firstChild,t,e,n,i,a,o);for(;r;){JE(e,1)||BE();let t=r;r=r.nextSibling,s(t)}}else if(d&8){let n=t.children;n[0]===`
`&&(e.tagName===`PRE`||e.tagName===`TEXTAREA`)&&(n=n.slice(1));let{textContent:r}=e;r!==n&&r!==n.replace(/\r\n|\r/g,`
`)&&(JE(e,0)||BE(),e.textContent=t.children)}if(l){if(h||!o||u&48){let t=e.tagName.includes(`-`);for(let i in l)(h&&(i.endsWith(`value`)||i===`indeterminate`)||yw(i)&&!Fw(i)||i[0]===`.`||t&&!Fw(i))&&r(e,i,null,l[i],void 0,n)}else if(l.onClick)r(e,`onClick`,null,l.onClick,void 0,n);else if(u&4&&PC(l.style))for(let e in l.style)l.style[e]}let g;(g=l&&l.onVnodeBeforeMount)&&rA(g,n,t),f&&GT(t,null,n,`beforeMount`),((g=l&&l.onVnodeMounted)||f||c)&&wk(()=>{g&&rA(g,n,t),c&&m.enter(e),f&&GT(t,null,n,`mounted`)},i)}return e.nextSibling},p=(e,t,r,o,s,l,u)=>{u||=!!t.dynamicChildren;let f=t.children,p=f.length;for(let t=0;t<p;t++){let m=u?f[t]:f[t]=$k(f[t]),h=m.type===Ok;e?(h&&!u&&t+1<p&&$k(f[t+1]).type===Ok&&(c(i(e.data.slice(m.children.length)),r,a(e)),e.data=m.children),e=d(e,m,o,s,l,u)):h&&!m.children?c(m.el=i(``),r):(JE(r,1)||BE(),n(null,m,r,null,o,s,UE(r),l))}return e},m=(e,t,n,r,i,s)=>{let{slotScopeIds:u}=t;u&&(i=i?i.concat(u):u);let d=o(e),f=p(a(e),t,d,n,r,i,s);return f&&WE(f)&&f.data===`]`?a(t.anchor=f):(BE(),c(t.anchor=l(`]`),d,f),f)},h=(e,t,r,i,c,l)=>{if(JE(e.parentElement,1)||BE(),t.el=null,l){let t=g(e);for(;;){let n=a(e);if(n&&n!==t)s(n);else break}}let u=a(e),d=o(e);return s(e),n(null,t,d,u,r,i,UE(d),c),r&&(r.vnode.el=t.el,LO(r,t.el)),u},g=(e,t=`[`,n=`]`)=>{let r=0;for(;e;)if(e=a(e),e&&WE(e)&&(e.data===t&&r++,e.data===n)){if(r===0)return a(e);r--}return e},_=(e,t,n)=>{let r=t.parentNode;r&&r.replaceChild(e,t);let i=n;for(;i;)i.vnode.el===t&&(i.vnode.el=i.subTree.el=e),i=i.parent},v=e=>e.nodeType===1&&e.tagName===`TEMPLATE`;return[u,d]}var KE=`data-allow-mismatch`,qE={0:`text`,1:`children`,2:`class`,3:`style`,4:`attribute`};function JE(e,t){if(t===0||t===1)for(;e&&!e.hasAttribute(KE);)e=e.parentElement;let n=e&&e.getAttribute(KE);if(n==null)return!1;if(n===``)return!0;{let e=n.split(`,`);return t===0&&e.includes(`children`)?!0:e.includes(qE[t])}}var YE=Yw().requestIdleCallback||(e=>setTimeout(e,1)),XE=Yw().cancelIdleCallback||(e=>clearTimeout(e)),ZE=(e=1e4)=>t=>{let n=YE(t,{timeout:e});return()=>XE(n)};function QE(e){let{top:t,left:n,bottom:r,right:i}=e.getBoundingClientRect(),{innerHeight:a,innerWidth:o}=window;return(t>0&&t<a||r>0&&r<a)&&(n>0&&n<o||i>0&&i<o)}var $E=e=>(t,n)=>{let r=new IntersectionObserver(e=>{for(let n of e)if(n.isIntersecting){r.disconnect(),t();break}},e);return n(e=>{if(e instanceof Element){if(QE(e))return t(),r.disconnect(),!1;r.observe(e)}}),()=>r.disconnect()},eD=e=>t=>{if(e){let n=matchMedia(e);if(n.matches)t();else return n.addEventListener(`change`,t,{once:!0}),()=>n.removeEventListener(`change`,t)}},tD=(e=[])=>(t,n)=>{Ow(e)&&(e=[e]);let r=!1,i=e=>{r||(r=!0,a(),t(),e.target.dispatchEvent(new e.constructor(e.type,e)))},a=()=>{n(t=>{for(let n of e)t.removeEventListener(n,i)})};return n(t=>{for(let n of e)t.addEventListener(n,i,{once:!0})}),a};function nD(e,t){if(WE(e)&&e.data===`[`){let n=1,r=e.nextSibling;for(;r;){if(r.nodeType===1){if(t(r)===!1)break}else if(WE(r))if(r.data===`]`){if(--n===0)break}else r.data===`[`&&n++;r=r.nextSibling}}else t(e)}var rD=e=>!!e.type.__asyncLoader;function iD(e){Y(e)&&(e={loader:e});let{loader:t,loadingComponent:n,errorComponent:r,delay:i=200,hydrate:a,timeout:o,suspensible:s=!0,onError:c}=e,l=null,u,d=0,f=()=>(d++,l=null,p()),p=()=>{let e;return l||(e=l=t().catch(e=>{if(e=e instanceof Error?e:Error(String(e)),c)return new Promise((t,n)=>{c(e,()=>t(f()),()=>n(e),d+1)});throw e}).then(t=>e!==l&&l?l:(t&&(t.__esModule||t[Symbol.toStringTag]===`Module`)&&(t=t.default),u=t,t)))};return ME({name:`AsyncComponentWrapper`,__asyncLoader:p,__asyncHydrate(e,t,n){let r=!1;(t.bu||=[]).push(()=>r=!0);let i=()=>{r||n()},o=a?()=>{let n=a(i,t=>nD(e,t));n&&(t.bum||=[]).push(n)}:i;u?o():p().then(()=>!t.isUnmounted&&o())},get __asyncResolved(){return u},setup(){let e=sA;if(PE(e),u)return()=>aD(u,e);let t=t=>{l=null,gT(t,e,13,!r)};if(s&&e.suspense||mA)return p().then(t=>()=>aD(t,e)).catch(e=>(t(e),()=>r?Kk(r,{error:e}):null));let a=HC(!1),c=HC(),d=HC(!!i);return i&&setTimeout(()=>{d.value=!1},i),o!=null&&setTimeout(()=>{if(!a.value&&!c.value){let e=Error(`Async component timed out after ${o}ms.`);t(e),c.value=e}},o),p().then(()=>{a.value=!0,e.parent&&oD(e.parent.vnode)&&e.parent.update()}).catch(e=>{t(e),c.value=e}),()=>{if(a.value&&u)return aD(u,e);if(c.value&&r)return Kk(r,{error:c.value});if(n&&!d.value)return aD(n,e)}}})}function aD(e,t){let{ref:n,props:r,children:i,ce:a}=t.vnode,o=Kk(e,r,i);return o.ref=n,o.ce=a,delete t.vnode.ce,o}var oD=e=>e.type.__isKeepAlive,sD={name:`KeepAlive`,__isKeepAlive:!0,props:{include:[String,RegExp,Array],exclude:[String,RegExp,Array],max:[String,Number]},setup(e,{slots:t}){let n=cA(),r=n.ctx;if(!r.renderer)return()=>{let e=t.default&&t.default();return e&&e.length===1?e[0]:e};let i=new Map,a=new Set,o=null,s=n.suspense,{renderer:{p:c,m:l,um:u,o:{createElement:d}}}=r,f=d(`div`);r.activate=(e,t,n,r,i)=>{let a=e.component;l(e,t,n,0,s),c(a.vnode,e,t,n,a,s,r,e.slotScopeIds,i),nk(()=>{a.isDeactivated=!1,a.a&&Ww(a.a);let t=e.props&&e.props.onVnodeMounted;t&&rA(t,a.parent,e)},s)},r.deactivate=e=>{let t=e.component;fk(t.m),fk(t.a),l(e,f,null,1,s),nk(()=>{t.da&&Ww(t.da);let n=e.props&&e.props.onVnodeUnmounted;n&&rA(n,t.parent,e),t.isDeactivated=!0},s)};function p(e){pD(e),u(e,n,s,!0)}function m(e){i.forEach((t,n)=>{let r=EA(rD(t)?t.type.__asyncResolved||{}:t.type);r&&!e(r)&&h(n)})}function h(e){let t=i.get(e);t&&(!o||!Vk(t,o))?p(t):o&&pD(o),i.delete(e),a.delete(e)}eE(()=>[e.include,e.exclude],([e,t])=>{e&&m(t=>cD(e,t)),t&&m(e=>!cD(t,e))},{flush:`post`,deep:!0});let g=null,_=()=>{g!=null&&(mk(n.subTree.type)?nk(()=>{i.set(g,mD(n.subTree))},n.subTree.suspense):i.set(g,mD(n.subTree)))};return vD(_),bD(_),xD(()=>{i.forEach(e=>{let{subTree:t,suspense:r}=n,i=mD(t);if(e.type===i.type&&e.key===i.key){pD(i);let e=i.component.da;e&&nk(e,r);return}p(e)})}),()=>{if(g=null,!t.default)return o=null;let n=t.default(),r=n[0];if(n.length>1)return o=null,n;if(!Bk(r)||!(r.shapeFlag&4)&&!(r.shapeFlag&128))return o=null,r;let s=mD(r);if(s.type===kk)return o=null,s;let c=s.type,l=EA(rD(s)?s.type.__asyncResolved||{}:c),{include:u,exclude:d,max:f}=e;if(u&&(!l||!cD(u,l))||d&&l&&cD(d,l))return s.shapeFlag&=-257,o=s,r;let p=s.key==null?c:s.key,m=i.get(p);return s.el&&(s=Yk(s),r.shapeFlag&128&&(r.ssContent=s)),g=p,m?(s.el=m.el,s.component=m.component,s.transition&&AE(s,s.transition),s.shapeFlag|=512,a.delete(p),a.add(p)):(a.add(p),f&&a.size>parseInt(f,10)&&h(a.values().next().value)),s.shapeFlag|=256,o=s,mk(r.type)?r:s}}};function cD(e,t){return J(e)?e.some(e=>cD(e,t)):Ow(e)?e.split(`,`).includes(t):Dw(e)?(e.lastIndex=0,e.test(t)):!1}function lD(e,t){dD(e,`a`,t)}function uD(e,t){dD(e,`da`,t)}function dD(e,t,n=sA){let r=e.__wdc||=()=>{let t=n;for(;t;){if(t.isDeactivated)return;t=t.parent}return e()};if(hD(t,r,n),n){let e=n.parent;for(;e&&e.parent;)oD(e.parent.vnode)&&fD(r,t,n,e),e=e.parent}}function fD(e,t,n,r){let i=hD(t,e,r,!0);SD(()=>{Sw(r[t],i)},n)}function pD(e){e.shapeFlag&=-257,e.shapeFlag&=-513}function mD(e){return e.shapeFlag&128?e.ssContent:e}function hD(e,t,n=sA,r=!1){if(n){let i=n[e]||(n[e]=[]),a=t.__weh||=(...r)=>{NS();let i=dA(n),a=hT(t,n,e,r);return i(),PS(),a};return r?i.unshift(a):i.push(a),a}}var gD=e=>(t,n=sA)=>{(!mA||e===`sp`)&&hD(e,(...e)=>t(...e),n)},_D=gD(`bm`),vD=gD(`m`),yD=gD(`bu`),bD=gD(`u`),xD=gD(`bum`),SD=gD(`um`),CD=gD(`sp`),wD=gD(`rtg`),TD=gD(`rtc`);function ED(e,t=sA){hD(`ec`,e,t)}var DD=`components`,OD=`directives`;function kD(e,t){return ND(DD,e,!0,t)||e}var AD=Symbol.for(`v-ndc`);function jD(e){return Ow(e)?ND(DD,e,!1)||e:e||AD}function MD(e){return ND(OD,e)}function ND(e,t,n=!0,r=!1){let i=LT||sA;if(i){let n=i.type;if(e===DD){let e=EA(n,!1);if(e&&(e===t||e===Rw(t)||e===Vw(Rw(t))))return n}let a=PD(i[e]||n[e],t)||PD(i.appContext[e],t);return!a&&r?n:a}}function PD(e,t){return e&&(e[t]||e[Rw(t)]||e[Vw(Rw(t))])}function FD(e,t,n,r){let i,a=n&&n[r],o=J(e);if(o||Ow(e)){let n=o&&PC(e),r=!1,s=!1;n&&(r=!IC(e),s=FC(e),e=JS(e)),i=Array(e.length);for(let n=0,o=e.length;n<o;n++)i[n]=t(r?s?BC(zC(e[n])):zC(e[n]):e[n],n,void 0,a&&a[n])}else if(typeof e==`number`){i=Array(e);for(let n=0;n<e;n++)i[n]=t(n+1,n,void 0,a&&a[n])}else if(Aw(e))if(e[Symbol.iterator])i=Array.from(e,(e,n)=>t(e,n,void 0,a&&a[n]));else{let n=Object.keys(e);i=Array(n.length);for(let r=0,o=n.length;r<o;r++){let o=n[r];i[r]=t(e[o],o,r,a&&a[r])}}else i=[];return n&&(n[r]=i),i}function ID(e,t){for(let n=0;n<t.length;n++){let r=t[n];if(J(r))for(let t=0;t<r.length;t++)e[r[t].name]=r[t].fn;else r&&(e[r.name]=r.key?(...e)=>{let t=r.fn(...e);return t&&(t.key=r.key),t}:r.fn)}return e}function LD(e,t,n={},r,i){if(LT.ce||LT.parent&&rD(LT.parent)&&LT.parent.ce){let e=Object.keys(n).length>0;return t!==`default`&&(n.name=t),Nk(),zk(Dk,null,[Kk(`slot`,n,r&&r())],e?-2:64)}let a=e[t];a&&a._c&&(a._d=!1),Nk();let o=a&&RD(a(n)),s=n.key||o&&o.key,c=zk(Dk,{key:(s&&!kw(s)?s:`_${t}`)+(!o&&r?`_fb`:``)},o||(r?r():[]),o&&e._===1?64:-2);return!i&&c.scopeId&&(c.slotScopeIds=[c.scopeId+`-s`]),a&&a._c&&(a._d=!0),c}function RD(e){return e.some(e=>Bk(e)?!(e.type===kk||e.type===Dk&&!RD(e.children)):!0)?e:null}function zD(e,t){let n={};for(let r in e)n[t&&/[A-Z]/.test(r)?`on:${r}`:Hw(r)]=e[r];return n}var BD=e=>e?pA(e)?TA(e):BD(e.parent):null,VD=xw(Object.create(null),{$:e=>e,$el:e=>e.vnode.el,$data:e=>e.data,$props:e=>e.props,$attrs:e=>e.attrs,$slots:e=>e.slots,$refs:e=>e.refs,$parent:e=>BD(e.parent),$root:e=>BD(e.root),$host:e=>e.ce,$emit:e=>e.emit,$options:e=>dO(e),$forceUpdate:e=>e.f||=()=>{DT(e.update)},$nextTick:e=>e.n||=TT.bind(e.proxy),$watch:e=>nE.bind(e)}),HD=(e,t)=>e!==q&&!e.__isScriptSetup&&ww(e,t),UD={get({_:e},t){if(t===`__v_skip`)return!0;let{ctx:n,setupState:r,data:i,props:a,accessCache:o,type:s,appContext:c}=e;if(t[0]!==`$`){let e=o[t];if(e!==void 0)switch(e){case 1:return r[t];case 2:return i[t];case 4:return n[t];case 3:return a[t]}else if(HD(r,t))return o[t]=1,r[t];else if(i!==q&&ww(i,t))return o[t]=2,i[t];else if(ww(a,t))return o[t]=3,a[t];else if(n!==q&&ww(n,t))return o[t]=4,n[t];else oO&&(o[t]=0)}let l=VD[t],u,d;if(l)return t===`$attrs`&&WS(e.attrs,`get`,``),l(e);if((u=s.__cssModules)&&(u=u[t]))return u;if(n!==q&&ww(n,t))return o[t]=4,n[t];if(d=c.config.globalProperties,ww(d,t))return d[t]},set({_:e},t,n){let{data:r,setupState:i,ctx:a}=e;return HD(i,t)?(i[t]=n,!0):r!==q&&ww(r,t)?(r[t]=n,!0):ww(e.props,t)||t[0]===`$`&&t.slice(1)in e?!1:(a[t]=n,!0)},has({_:{data:e,setupState:t,accessCache:n,ctx:r,appContext:i,props:a,type:o}},s){let c;return!!(n[s]||e!==q&&s[0]!==`$`&&ww(e,s)||HD(t,s)||ww(a,s)||ww(r,s)||ww(VD,s)||ww(i.config.globalProperties,s)||(c=o.__cssModules)&&c[s])},defineProperty(e,t,n){return n.get==null?ww(n,`value`)&&this.set(e,t,n.value,null):e._.accessCache[t]=0,Reflect.defineProperty(e,t,n)}},WD=xw({},UD,{get(e,t){if(t!==Symbol.unscopables)return UD.get(e,t,e)},has(e,t){return t[0]!==`_`&&!Xw(t)}});function GD(){return null}function KD(){return null}function qD(e){}function JD(e){}function YD(){return null}function XD(){}function ZD(e,t){return null}function QD(){return eO(`useSlots`).slots}function $D(){return eO(`useAttrs`).attrs}function eO(e){let t=cA();return t.setupContext||=wA(t)}function tO(e){return J(e)?e.reduce((e,t)=>(e[t]=null,e),{}):e}function nO(e,t){let n=tO(e);for(let e in t){if(e.startsWith(`__skip`))continue;let r=n[e];r?J(r)||Y(r)?r=n[e]={type:r,default:t[e]}:r.default=t[e]:r===null&&(r=n[e]={default:t[e]}),r&&t[`__skip_${e}`]&&(r.skipFactory=!0)}return n}function rO(e,t){return!e||!t?e||t:J(e)&&J(t)?e.concat(t):xw({},tO(e),tO(t))}function iO(e,t){let n={};for(let r in e)t.includes(r)||Object.defineProperty(n,r,{enumerable:!0,get:()=>e[r]});return n}function aO(e){let t=cA(),n=e();return fA(),jw(n)&&(n=n.catch(e=>{throw dA(t),e})),[n,()=>dA(t)]}var oO=!0;function sO(e){let t=dO(e),n=e.proxy,r=e.ctx;oO=!1,t.beforeCreate&&lO(t.beforeCreate,e,`bc`);let{data:i,computed:a,methods:o,watch:s,provide:c,inject:l,created:u,beforeMount:d,mounted:f,beforeUpdate:p,updated:m,activated:h,deactivated:g,beforeDestroy:_,beforeUnmount:v,destroyed:y,unmounted:b,render:x,renderTracked:S,renderTriggered:C,errorCaptured:w,serverPrefetch:T,expose:E,inheritAttrs:D,components:O,directives:k,filters:A}=t;if(l&&cO(l,r,null),o)for(let e in o){let t=o[e];Y(t)&&(r[e]=t.bind(n))}if(i){let t=i.call(n,n);Aw(t)&&(e.data=kC(t))}if(oO=!0,a)for(let e in a){let t=a[e],i=X({get:Y(t)?t.bind(n,n):Y(t.get)?t.get.bind(n,n):_w,set:!Y(t)&&Y(t.set)?t.set.bind(n):_w});Object.defineProperty(r,e,{enumerable:!0,configurable:!0,get:()=>i.value,set:e=>i.value=e})}if(s)for(let e in s)uO(s[e],r,n,e);if(c){let e=Y(c)?c.call(n):c;Reflect.ownKeys(e).forEach(t=>{KT(t,e[t])})}u&&lO(u,e,`c`);function ee(e,t){J(t)?t.forEach(t=>e(t.bind(n))):t&&e(t.bind(n))}if(ee(_D,d),ee(vD,f),ee(yD,p),ee(bD,m),ee(lD,h),ee(uD,g),ee(ED,w),ee(TD,S),ee(wD,C),ee(xD,v),ee(SD,b),ee(CD,T),J(E))if(E.length){let t=e.exposed||={};E.forEach(e=>{Object.defineProperty(t,e,{get:()=>n[e],set:t=>n[e]=t,enumerable:!0})})}else e.exposed||={};x&&e.render===_w&&(e.render=x),D!=null&&(e.inheritAttrs=D),O&&(e.components=O),k&&(e.directives=k),T&&PE(e)}function cO(e,t,n=_w){J(e)&&(e=gO(e));for(let n in e){let r=e[n],i;i=Aw(r)?`default`in r?qT(r.from||n,r.default,!0):qT(r.from||n):qT(r),VC(i)?Object.defineProperty(t,n,{enumerable:!0,configurable:!0,get:()=>i.value,set:e=>i.value=e}):t[n]=i}}function lO(e,t,n){hT(J(e)?e.map(e=>e.bind(t.proxy)):e.bind(t.proxy),t,n)}function uO(e,t,n,r){let i=r.includes(`.`)?rE(n,r):()=>n[r];if(Ow(e)){let n=t[e];Y(n)&&eE(i,n)}else if(Y(e))eE(i,e.bind(n));else if(Aw(e))if(J(e))e.forEach(e=>uO(e,t,n,r));else{let r=Y(e.handler)?e.handler.bind(n):t[e.handler];Y(r)&&eE(i,r,e)}}function dO(e){let t=e.type,{mixins:n,extends:r}=t,{mixins:i,optionsCache:a,config:{optionMergeStrategies:o}}=e.appContext,s=a.get(t),c;return s?c=s:!i.length&&!n&&!r?c=t:(c={},i.length&&i.forEach(e=>fO(c,e,o,!0)),fO(c,t,o)),Aw(t)&&a.set(t,c),c}function fO(e,t,n,r=!1){let{mixins:i,extends:a}=t;a&&fO(e,a,n,!0),i&&i.forEach(t=>fO(e,t,n,!0));for(let i in t)if(!(r&&i===`expose`)){let r=pO[i]||n&&n[i];e[i]=r?r(e[i],t[i]):t[i]}return e}var pO={data:mO,props:yO,emits:yO,methods:vO,computed:vO,beforeCreate:_O,created:_O,beforeMount:_O,mounted:_O,beforeUpdate:_O,updated:_O,beforeDestroy:_O,beforeUnmount:_O,destroyed:_O,unmounted:_O,activated:_O,deactivated:_O,errorCaptured:_O,serverPrefetch:_O,components:vO,directives:vO,watch:bO,provide:mO,inject:hO};function mO(e,t){return t?e?function(){return xw(Y(e)?e.call(this,this):e,Y(t)?t.call(this,this):t)}:t:e}function hO(e,t){return vO(gO(e),gO(t))}function gO(e){if(J(e)){let t={};for(let n=0;n<e.length;n++)t[e[n]]=e[n];return t}return e}function _O(e,t){return e?[...new Set([].concat(e,t))]:t}function vO(e,t){return e?xw(Object.create(null),e,t):t}function yO(e,t){return e?J(e)&&J(t)?[...new Set([...e,...t])]:xw(Object.create(null),tO(e),tO(t??{})):t}function bO(e,t){if(!e)return t;if(!t)return e;let n=xw(Object.create(null),e);for(let r in t)n[r]=_O(e[r],t[r]);return n}function xO(){return{app:null,config:{isNativeTag:vw,performance:!1,globalProperties:{},optionMergeStrategies:{},errorHandler:void 0,warnHandler:void 0,compilerOptions:{}},mixins:[],components:{},directives:{},provides:Object.create(null),optionsCache:new WeakMap,propsCache:new WeakMap,emitsCache:new WeakMap}}var SO=0;function CO(e,t){return function(n,r=null){Y(n)||(n=xw({},n)),r!=null&&!Aw(r)&&(r=null);let i=xO(),a=new WeakSet,o=[],s=!1,c=i.app={_uid:SO++,_component:n,_props:r,_container:null,_context:i,_instance:null,version:MA,get config(){return i.config},set config(e){},use(e,...t){return a.has(e)||(e&&Y(e.install)?(a.add(e),e.install(c,...t)):Y(e)&&(a.add(e),e(c,...t))),c},mixin(e){return i.mixins.includes(e)||i.mixins.push(e),c},component(e,t){return t?(i.components[e]=t,c):i.components[e]},directive(e,t){return t?(i.directives[e]=t,c):i.directives[e]},mount(a,o,l){if(!s){let u=c._ceVNode||Kk(n,r);return u.appContext=i,l===!0?l=`svg`:l===!1&&(l=void 0),o&&t?t(u,a):e(u,a,l),s=!0,c._container=a,a.__vue_app__=c,TA(u.component)}},onUnmount(e){o.push(e)},unmount(){s&&(hT(o,c._instance,16),e(null,c._container),delete c._container.__vue_app__)},provide(e,t){return i.provides[e]=t,c},runWithContext(e){let t=wO;wO=c;try{return e()}finally{wO=t}}};return c}}var wO=null;function TO(e,t,n=q){let r=cA(),i=Rw(t),a=Bw(t),o=EO(e,i),s=QC((o,s)=>{let c,l=q,u;return $T(()=>{let t=e[i];Uw(c,t)&&(c=t,s())}),{get(){return o(),n.get?n.get(c):c},set(e){let o=n.set?n.set(e):e;if(!Uw(o,c)&&!(l!==q&&Uw(e,l)))return;let d=r.vnode.props;d&&(t in d||i in d||a in d)&&(`onUpdate:${t}`in d||`onUpdate:${i}`in d||`onUpdate:${a}`in d)||(c=e,s()),r.emit(`update:${t}`,o),Uw(e,o)&&Uw(e,l)&&!Uw(o,u)&&s(),l=e,u=o}}});return s[Symbol.iterator]=()=>{let e=0;return{next(){return e<2?{value:e++?o||q:s,done:!1}:{done:!0}}}},s}var EO=(e,t)=>t===`modelValue`||t===`model-value`?e.modelModifiers:e[`${t}Modifiers`]||e[`${Rw(t)}Modifiers`]||e[`${Bw(t)}Modifiers`];function DO(e,t,...n){if(e.isUnmounted)return;let r=e.vnode.props||q,i=n,a=t.startsWith(`update:`),o=a&&EO(r,t.slice(7));o&&(o.trim&&(i=n.map(e=>Ow(e)?e.trim():e)),o.number&&(i=n.map(Kw)));let s,c=r[s=Hw(t)]||r[s=Hw(Rw(t))];!c&&a&&(c=r[s=Hw(Bw(t))]),c&&hT(c,e,6,i);let l=r[s+`Once`];if(l){if(!e.emitted)e.emitted={};else if(e.emitted[s])return;e.emitted[s]=!0,hT(l,e,6,i)}}var OO=new WeakMap;function kO(e,t,n=!1){let r=n?OO:t.emitsCache,i=r.get(e);if(i!==void 0)return i;let a=e.emits,o={},s=!1;if(!Y(e)){let r=e=>{let n=kO(e,t,!0);n&&(s=!0,xw(o,n))};!n&&t.mixins.length&&t.mixins.forEach(r),e.extends&&r(e.extends),e.mixins&&e.mixins.forEach(r)}return!a&&!s?(Aw(e)&&r.set(e,null),null):(J(a)?a.forEach(e=>o[e]=null):xw(o,a),Aw(e)&&r.set(e,o),o)}function AO(e,t){return!e||!yw(t)?!1:(t=t.slice(2).replace(/Once$/,``),ww(e,t[0].toLowerCase()+t.slice(1))||ww(e,Bw(t))||ww(e,t))}function jO(e){let{type:t,vnode:n,proxy:r,withProxy:i,propsOptions:[a],slots:o,attrs:s,emit:c,render:l,renderCache:u,props:d,data:f,setupState:p,ctx:m,inheritAttrs:h}=e,g=zT(e),_,v;try{if(n.shapeFlag&4){let e=i||r,t=e;_=$k(l.call(t,e,u,d,p,f,m)),v=s}else{let e=t;_=$k(e.length>1?e(d,{attrs:s,slots:o,emit:c}):e(d,null)),v=t.props?s:NO(s)}}catch(t){jk.length=0,gT(t,e,1),_=Kk(kk)}let y=_;if(v&&h!==!1){let e=Object.keys(v),{shapeFlag:t}=y;e.length&&t&7&&(a&&e.some(bw)&&(v=PO(v,a)),y=Yk(y,v,!1,!0))}return n.dirs&&(y=Yk(y,null,!1,!0),y.dirs=y.dirs?y.dirs.concat(n.dirs):n.dirs),n.transition&&AE(y,n.transition),_=y,zT(g),_}function MO(e,t=!0){let n;for(let t=0;t<e.length;t++){let r=e[t];if(Bk(r)){if(r.type!==kk||r.children===`v-if`){if(n)return;n=r}}else return}return n}var NO=e=>{let t;for(let n in e)(n===`class`||n===`style`||yw(n))&&((t||={})[n]=e[n]);return t},PO=(e,t)=>{let n={};for(let r in e)(!bw(r)||!(r.slice(9)in t))&&(n[r]=e[r]);return n};function FO(e,t,n){let{props:r,children:i,component:a}=e,{props:o,children:s,patchFlag:c}=t,l=a.emitsOptions;if(t.dirs||t.transition)return!0;if(n&&c>=0){if(c&1024)return!0;if(c&16)return r?IO(r,o,l):!!o;if(c&8){let e=t.dynamicProps;for(let t=0;t<e.length;t++){let n=e[t];if(o[n]!==r[n]&&!AO(l,n))return!0}}}else return(i||s)&&(!s||!s.$stable)?!0:r===o?!1:r?o?IO(r,o,l):!0:!!o;return!1}function IO(e,t,n){let r=Object.keys(t);if(r.length!==Object.keys(e).length)return!0;for(let i=0;i<r.length;i++){let a=r[i];if(t[a]!==e[a]&&!AO(n,a))return!0}return!1}function LO({vnode:e,parent:t},n){for(;t;){let r=t.subTree;if(r.suspense&&r.suspense.activeBranch===e&&(r.el=e.el),r===e)(e=t.vnode).el=n,t=t.parent;else break}}var RO={},zO=()=>Object.create(RO),BO=e=>Object.getPrototypeOf(e)===RO;function VO(e,t,n,r=!1){let i={},a=zO();e.propsDefaults=Object.create(null),UO(e,t,i,a);for(let t in e.propsOptions[0])t in i||(i[t]=void 0);n?e.props=r?i:AC(i):e.type.props?e.props=i:e.props=a,e.attrs=a}function HO(e,t,n,r){let{props:i,attrs:a,vnode:{patchFlag:o}}=e,s=K(i),[c]=e.propsOptions,l=!1;if((r||o>0)&&!(o&16)){if(o&8){let n=e.vnode.dynamicProps;for(let r=0;r<n.length;r++){let o=n[r];if(AO(e.emitsOptions,o))continue;let u=t[o];if(c)if(ww(a,o))u!==a[o]&&(a[o]=u,l=!0);else{let t=Rw(o);i[t]=WO(c,s,t,u,e,!1)}else u!==a[o]&&(a[o]=u,l=!0)}}}else{UO(e,t,i,a)&&(l=!0);let r;for(let a in s)(!t||!ww(t,a)&&((r=Bw(a))===a||!ww(t,r)))&&(c?n&&(n[a]!==void 0||n[r]!==void 0)&&(i[a]=WO(c,s,a,void 0,e,!0)):delete i[a]);if(a!==s)for(let e in a)(!t||!ww(t,e))&&(delete a[e],l=!0)}l&&GS(e.attrs,`set`,``)}function UO(e,t,n,r){let[i,a]=e.propsOptions,o=!1,s;if(t)for(let c in t){if(Fw(c))continue;let l=t[c],u;i&&ww(i,u=Rw(c))?!a||!a.includes(u)?n[u]=l:(s||={})[u]=l:AO(e.emitsOptions,c)||(!(c in r)||l!==r[c])&&(r[c]=l,o=!0)}if(a){let t=K(n),r=s||q;for(let o=0;o<a.length;o++){let s=a[o];n[s]=WO(i,t,s,r[s],e,!ww(r,s))}}return o}function WO(e,t,n,r,i,a){let o=e[n];if(o!=null){let e=ww(o,`default`);if(e&&r===void 0){let e=o.default;if(o.type!==Function&&!o.skipFactory&&Y(e)){let{propsDefaults:a}=i;if(n in a)r=a[n];else{let o=dA(i);r=a[n]=e.call(null,t),o()}}else r=e;i.ce&&i.ce._setProp(n,r)}o[0]&&(a&&!e?r=!1:o[1]&&(r===``||r===Bw(n))&&(r=!0))}return r}var GO=new WeakMap;function KO(e,t,n=!1){let r=n?GO:t.propsCache,i=r.get(e);if(i)return i;let a=e.props,o={},s=[],c=!1;if(!Y(e)){let r=e=>{c=!0;let[n,r]=KO(e,t,!0);xw(o,n),r&&s.push(...r)};!n&&t.mixins.length&&t.mixins.forEach(r),e.extends&&r(e.extends),e.mixins&&e.mixins.forEach(r)}if(!a&&!c)return Aw(e)&&r.set(e,gw),gw;if(J(a))for(let e=0;e<a.length;e++){let t=Rw(a[e]);qO(t)&&(o[t]=q)}else if(a)for(let e in a){let t=Rw(e);if(qO(t)){let n=a[e],r=o[t]=J(n)||Y(n)?{type:n}:xw({},n),i=r.type,c=!1,l=!0;if(J(i))for(let e=0;e<i.length;++e){let t=i[e],n=Y(t)&&t.name;if(n===`Boolean`){c=!0;break}else n===`String`&&(l=!1)}else c=Y(i)&&i.name===`Boolean`;r[0]=c,r[1]=l,(c||ww(r,`default`))&&s.push(t)}}let l=[o,s];return Aw(e)&&r.set(e,l),l}function qO(e){return e[0]!==`$`&&!Fw(e)}var JO=e=>e===`_`||e===`_ctx`||e===`$stable`,YO=e=>J(e)?e.map($k):[$k(e)],XO=(e,t,n)=>{if(t._n)return t;let r=UT((...e)=>YO(t(...e)),n);return r._c=!1,r},ZO=(e,t,n)=>{let r=e._ctx;for(let n in e){if(JO(n))continue;let i=e[n];if(Y(i))t[n]=XO(n,i,r);else if(i!=null){let e=YO(i);t[n]=()=>e}}},QO=(e,t)=>{let n=YO(t);e.slots.default=()=>n},$O=(e,t,n)=>{for(let r in t)(n||!JO(r))&&(e[r]=t[r])},ek=(e,t,n)=>{let r=e.slots=zO();if(e.vnode.shapeFlag&32){let e=t._;e?($O(r,t,n),n&&Gw(r,`_`,e,!0)):ZO(t,r)}else t&&QO(e,t)},tk=(e,t,n)=>{let{vnode:r,slots:i}=e,a=!0,o=q;if(r.shapeFlag&32){let e=t._;e?n&&e===1?a=!1:$O(i,t,n):(a=!t.$stable,ZO(t,i)),o=t}else t&&(QO(e,t),o={default:1});if(a)for(let e in i)!JO(e)&&o[e]==null&&delete i[e]},nk=wk;function rk(e){return ak(e)}function ik(e){return ak(e,GE)}function ak(e,t){let n=Yw();n.__VUE__=!0;let{insert:r,remove:i,patchProp:a,createElement:o,createText:s,createComment:c,setText:l,setElementText:u,parentNode:d,nextSibling:f,setScopeId:p=_w,insertStaticContent:m}=e,h=(e,t,n,r=null,i=null,a=null,o=void 0,s=null,c=!!t.dynamicChildren)=>{if(e===t)return;e&&!Vk(e,t)&&(r=fe(e),se(e,i,a,!0),e=null),t.patchFlag===-2&&(c=!1,t.dynamicChildren=null);let{type:l,ref:u,shapeFlag:d}=t;switch(l){case Ok:g(e,t,n,r);break;case kk:_(e,t,n,r);break;case Ak:e??v(t,n,r,o);break;case Dk:O(e,t,n,r,i,a,o,s,c);break;default:d&1?x(e,t,n,r,i,a,o,s,c):d&6?k(e,t,n,r,i,a,o,s,c):(d&64||d&128)&&l.process(e,t,n,r,i,a,o,s,c,j)}u!=null&&i?LE(u,e&&e.ref,a,t||e,!t):u==null&&e&&e.ref!=null&&LE(e.ref,null,a,e,!0)},g=(e,t,n,i)=>{if(e==null)r(t.el=s(t.children),n,i);else{let n=t.el=e.el;t.children!==e.children&&l(n,t.children)}},_=(e,t,n,i)=>{e==null?r(t.el=c(t.children||``),n,i):t.el=e.el},v=(e,t,n,r)=>{[e.el,e.anchor]=m(e.children,t,n,r,e.el,e.anchor)},y=({el:e,anchor:t},n,i)=>{let a;for(;e&&e!==t;)a=f(e),r(e,n,i),e=a;r(t,n,i)},b=({el:e,anchor:t})=>{let n;for(;e&&e!==t;)n=f(e),i(e),e=n;i(t)},x=(e,t,n,r,i,a,o,s,c)=>{if(t.type===`svg`?o=`svg`:t.type===`math`&&(o=`mathml`),e==null)S(t,n,r,i,a,o,s,c);else{let n=e.el&&e.el._isVueCE?e.el:null;try{n&&n._beginPatch(),T(e,t,i,a,o,s,c)}finally{n&&n._endPatch()}}},S=(e,t,n,i,s,c,l,d)=>{let f,p,{props:m,shapeFlag:h,transition:g,dirs:_}=e;if(f=e.el=o(e.type,c,m&&m.is,m),h&8?u(f,e.children):h&16&&w(e.children,f,null,i,s,ok(e,c),l,d),_&&GT(e,null,i,`created`),C(f,e,e.scopeId,l,i),m){for(let e in m)e!==`value`&&!Fw(e)&&a(f,e,null,m[e],c,i);`value`in m&&a(f,`value`,null,m.value,c),(p=m.onVnodeBeforeMount)&&rA(p,i,e)}_&&GT(e,null,i,`beforeMount`);let v=ck(s,g);v&&g.beforeEnter(f),r(f,t,n),((p=m&&m.onVnodeMounted)||v||_)&&nk(()=>{p&&rA(p,i,e),v&&g.enter(f),_&&GT(e,null,i,`mounted`)},s)},C=(e,t,n,r,i)=>{if(n&&p(e,n),r)for(let t=0;t<r.length;t++)p(e,r[t]);if(i){let n=i.subTree;if(t===n||mk(n.type)&&(n.ssContent===t||n.ssFallback===t)){let t=i.vnode;C(e,t,t.scopeId,t.slotScopeIds,i.parent)}}},w=(e,t,n,r,i,a,o,s,c=0)=>{for(let l=c;l<e.length;l++)h(null,e[l]=s?eA(e[l]):$k(e[l]),t,n,r,i,a,o,s)},T=(e,t,n,r,i,o,s)=>{let c=t.el=e.el,{patchFlag:l,dynamicChildren:d,dirs:f}=t;l|=e.patchFlag&16;let p=e.props||q,m=t.props||q,h;if(n&&sk(n,!1),(h=m.onVnodeBeforeUpdate)&&rA(h,n,t,e),f&&GT(t,e,n,`beforeUpdate`),n&&sk(n,!0),(p.innerHTML&&m.innerHTML==null||p.textContent&&m.textContent==null)&&u(c,``),d?E(e.dynamicChildren,d,c,n,r,ok(t,i),o):s||re(e,t,c,null,n,r,ok(t,i),o,!1),l>0){if(l&16)D(c,p,m,n,i);else if(l&2&&p.class!==m.class&&a(c,`class`,null,m.class,i),l&4&&a(c,`style`,p.style,m.style,i),l&8){let e=t.dynamicProps;for(let t=0;t<e.length;t++){let r=e[t],o=p[r],s=m[r];(s!==o||r===`value`)&&a(c,r,o,s,i,n)}}l&1&&e.children!==t.children&&u(c,t.children)}else !s&&d==null&&D(c,p,m,n,i);((h=m.onVnodeUpdated)||f)&&nk(()=>{h&&rA(h,n,t,e),f&&GT(t,e,n,`updated`)},r)},E=(e,t,n,r,i,a,o)=>{for(let s=0;s<t.length;s++){let c=e[s],l=t[s];h(c,l,c.el&&(c.type===Dk||!Vk(c,l)||c.shapeFlag&198)?d(c.el):n,null,r,i,a,o,!0)}},D=(e,t,n,r,i)=>{if(t!==n){if(t!==q)for(let o in t)!Fw(o)&&!(o in n)&&a(e,o,t[o],null,i,r);for(let o in n){if(Fw(o))continue;let s=n[o],c=t[o];s!==c&&o!==`value`&&a(e,o,c,s,i,r)}`value`in n&&a(e,`value`,t.value,n.value,i)}},O=(e,t,n,i,a,o,c,l,u)=>{let d=t.el=e?e.el:s(``),f=t.anchor=e?e.anchor:s(``),{patchFlag:p,dynamicChildren:m,slotScopeIds:h}=t;h&&(l=l?l.concat(h):h),e==null?(r(d,n,i),r(f,n,i),w(t.children||[],n,f,a,o,c,l,u)):p>0&&p&64&&m&&e.dynamicChildren&&e.dynamicChildren.length===m.length?(E(e.dynamicChildren,m,n,a,o,c,l),(t.key!=null||a&&t===a.subTree)&&lk(e,t,!0)):re(e,t,n,f,a,o,c,l,u)},k=(e,t,n,r,i,a,o,s,c)=>{t.slotScopeIds=s,e==null?t.shapeFlag&512?i.ctx.activate(t,n,r,o,c):A(t,n,r,i,a,o,c):ee(e,t,c)},A=(e,t,n,r,i,a,o)=>{let s=e.component=oA(e,r,i);if(oD(e)&&(s.ctx.renderer=j),hA(s,!1,o),s.asyncDep){if(i&&i.registerDep(s,te,o),!e.el){let r=s.subTree=Kk(kk);_(null,r,t,n),e.placeholder=r.el}}else te(s,e,t,n,i,a,o)},ee=(e,t,n)=>{let r=t.component=e.component;if(FO(e,t,n))if(r.asyncDep&&!r.asyncResolved){ne(r,t,n);return}else r.next=t,r.update();else t.el=e.el,r.vnode=t},te=(e,t,n,r,i,a,o)=>{let s=()=>{if(e.isMounted){let{next:t,bu:n,u:r,parent:c,vnode:l}=e;{let n=dk(e);if(n){t&&(t.el=l.el,ne(e,t,o)),n.asyncDep.then(()=>{e.isUnmounted||s()});return}}let u=t,f;sk(e,!1),t?(t.el=l.el,ne(e,t,o)):t=l,n&&Ww(n),(f=t.props&&t.props.onVnodeBeforeUpdate)&&rA(f,c,t,l),sk(e,!0);let p=jO(e),m=e.subTree;e.subTree=p,h(m,p,d(m.el),fe(m),e,i,a),t.el=p.el,u===null&&LO(e,p.el),r&&nk(r,i),(f=t.props&&t.props.onVnodeUpdated)&&nk(()=>rA(f,c,t,l),i)}else{let o,{el:s,props:c}=t,{bm:l,m:u,parent:d,root:f,type:p}=e,m=rD(t);if(sk(e,!1),l&&Ww(l),!m&&(o=c&&c.onVnodeBeforeMount)&&rA(o,d,t),sk(e,!0),s&&ge){let t=()=>{e.subTree=jO(e),ge(s,e.subTree,e,i,null)};m&&p.__asyncHydrate?p.__asyncHydrate(s,e,t):t()}else{f.ce&&f.ce._def.shadowRoot!==!1&&f.ce._injectChildStyle(p);let o=e.subTree=jO(e);h(null,o,n,r,e,i,a),t.el=o.el}if(u&&nk(u,i),!m&&(o=c&&c.onVnodeMounted)){let e=t;nk(()=>rA(o,d,e),i)}(t.shapeFlag&256||d&&rD(d.vnode)&&d.vnode.shapeFlag&256)&&e.a&&nk(e.a,i),e.isMounted=!0,t=n=r=null}};e.scope.on();let c=e.effect=new gS(s);e.scope.off();let l=e.update=c.run.bind(c),u=e.job=c.runIfDirty.bind(c);u.i=e,u.id=e.uid,c.scheduler=()=>DT(u),sk(e,!0),l()},ne=(e,t,n)=>{t.component=e;let r=e.vnode.props;e.vnode=t,e.next=null,HO(e,t.props,r,n),tk(e,t.children,n),NS(),AT(e),PS()},re=(e,t,n,r,i,a,o,s,c=!1)=>{let l=e&&e.children,d=e?e.shapeFlag:0,f=t.children,{patchFlag:p,shapeFlag:m}=t;if(p>0){if(p&128){ae(l,f,n,r,i,a,o,s,c);return}else if(p&256){ie(l,f,n,r,i,a,o,s,c);return}}m&8?(d&16&&de(l,i,a),f!==l&&u(n,f)):d&16?m&16?ae(l,f,n,r,i,a,o,s,c):de(l,i,a,!0):(d&8&&u(n,``),m&16&&w(f,n,r,i,a,o,s,c))},ie=(e,t,n,r,i,a,o,s,c)=>{e||=gw,t||=gw;let l=e.length,u=t.length,d=Math.min(l,u),f;for(f=0;f<d;f++){let r=t[f]=c?eA(t[f]):$k(t[f]);h(e[f],r,n,null,i,a,o,s,c)}l>u?de(e,i,a,!0,!1,d):w(t,n,r,i,a,o,s,c,d)},ae=(e,t,n,r,i,a,o,s,c)=>{let l=0,u=t.length,d=e.length-1,f=u-1;for(;l<=d&&l<=f;){let r=e[l],u=t[l]=c?eA(t[l]):$k(t[l]);if(Vk(r,u))h(r,u,n,null,i,a,o,s,c);else break;l++}for(;l<=d&&l<=f;){let r=e[d],l=t[f]=c?eA(t[f]):$k(t[f]);if(Vk(r,l))h(r,l,n,null,i,a,o,s,c);else break;d--,f--}if(l>d){if(l<=f){let e=f+1,d=e<u?t[e].el:r;for(;l<=f;)h(null,t[l]=c?eA(t[l]):$k(t[l]),n,d,i,a,o,s,c),l++}}else if(l>f)for(;l<=d;)se(e[l],i,a,!0),l++;else{let p=l,m=l,g=new Map;for(l=m;l<=f;l++){let e=t[l]=c?eA(t[l]):$k(t[l]);e.key!=null&&g.set(e.key,l)}let _,v=0,y=f-m+1,b=!1,x=0,S=Array(y);for(l=0;l<y;l++)S[l]=0;for(l=p;l<=d;l++){let r=e[l];if(v>=y){se(r,i,a,!0);continue}let u;if(r.key!=null)u=g.get(r.key);else for(_=m;_<=f;_++)if(S[_-m]===0&&Vk(r,t[_])){u=_;break}u===void 0?se(r,i,a,!0):(S[u-m]=l+1,u>=x?x=u:b=!0,h(r,t[u],n,null,i,a,o,s,c),v++)}let C=b?uk(S):gw;for(_=C.length-1,l=y-1;l>=0;l--){let e=m+l,d=t[e],f=t[e+1],p=e+1<u?f.el||pk(f):r;S[l]===0?h(null,d,n,p,i,a,o,s,c):b&&(_<0||l!==C[_]?oe(d,n,p,2):_--)}}},oe=(e,t,n,a,o=null)=>{let{el:s,type:c,transition:l,children:u,shapeFlag:d}=e;if(d&6){oe(e.component.subTree,t,n,a);return}if(d&128){e.suspense.move(t,n,a);return}if(d&64){c.move(e,t,n,j);return}if(c===Dk){r(s,t,n);for(let e=0;e<u.length;e++)oe(u[e],t,n,a);r(e.anchor,t,n);return}if(c===Ak){y(e,t,n);return}if(a!==2&&d&1&&l)if(a===0)l.beforeEnter(s),r(s,t,n),nk(()=>l.enter(s),o);else{let{leave:a,delayLeave:o,afterLeave:c}=l,u=()=>{e.ctx.isUnmounted?i(s):r(s,t,n)},d=()=>{s._isLeaving&&s[_E](!0),a(s,()=>{u(),c&&c()})};o?o(s,u,d):d()}else r(s,t,n)},se=(e,t,n,r=!1,i=!1)=>{let{type:a,props:o,ref:s,children:c,dynamicChildren:l,shapeFlag:u,patchFlag:d,dirs:f,cacheIndex:p}=e;if(d===-2&&(i=!1),s!=null&&(NS(),LE(s,null,n,e,!0),PS()),p!=null&&(t.renderCache[p]=void 0),u&256){t.ctx.deactivate(e);return}let m=u&1&&f,h=!rD(e),g;if(h&&(g=o&&o.onVnodeBeforeUnmount)&&rA(g,t,e),u&6)ue(e.component,n,r);else{if(u&128){e.suspense.unmount(n,r);return}m&&GT(e,null,t,`beforeUnmount`),u&64?e.type.remove(e,t,n,j,r):l&&!l.hasOnce&&(a!==Dk||d>0&&d&64)?de(l,t,n,!1,!0):(a===Dk&&d&384||!i&&u&16)&&de(c,t,n),r&&ce(e)}(h&&(g=o&&o.onVnodeUnmounted)||m)&&nk(()=>{g&&rA(g,t,e),m&&GT(e,null,t,`unmounted`)},n)},ce=e=>{let{type:t,el:n,anchor:r,transition:a}=e;if(t===Dk){le(n,r);return}if(t===Ak){b(e);return}let o=()=>{i(n),a&&!a.persisted&&a.afterLeave&&a.afterLeave()};if(e.shapeFlag&1&&a&&!a.persisted){let{leave:t,delayLeave:r}=a,i=()=>t(n,o);r?r(e.el,o,i):i()}else o()},le=(e,t)=>{let n;for(;e!==t;)n=f(e),i(e),e=n;i(t)},ue=(e,t,n)=>{let{bum:r,scope:i,job:a,subTree:o,um:s,m:c,a:l}=e;fk(c),fk(l),r&&Ww(r),i.stop(),a&&(a.flags|=8,se(o,e,t,n)),s&&nk(s,t),nk(()=>{e.isUnmounted=!0},t)},de=(e,t,n,r=!1,i=!1,a=0)=>{for(let o=a;o<e.length;o++)se(e[o],t,n,r,i)},fe=e=>{if(e.shapeFlag&6)return fe(e.component.subTree);if(e.shapeFlag&128)return e.suspense.next();let t=f(e.anchor||e.el),n=t&&t[iE];return n?f(n):t},pe=!1,me=(e,t,n)=>{let r;e==null?t._vnode&&(se(t._vnode,null,null,!0),r=t._vnode.component):h(t._vnode||null,e,t,null,null,null,n),t._vnode=e,pe||=(pe=!0,AT(r),jT(),!1)},j={p:h,um:se,m:oe,r:ce,mt:A,mc:w,pc:re,pbc:E,n:fe,o:e},he,ge;return t&&([he,ge]=t(j)),{render:me,hydrate:he,createApp:CO(me,he)}}function ok({type:e,props:t},n){return n===`svg`&&e===`foreignObject`||n===`mathml`&&e===`annotation-xml`&&t&&t.encoding&&t.encoding.includes(`html`)?void 0:n}function sk({effect:e,job:t},n){n?(e.flags|=32,t.flags|=4):(e.flags&=-33,t.flags&=-5)}function ck(e,t){return(!e||e&&!e.pendingBranch)&&t&&!t.persisted}function lk(e,t,n=!1){let r=e.children,i=t.children;if(J(r)&&J(i))for(let t=0;t<r.length;t++){let a=r[t],o=i[t];o.shapeFlag&1&&!o.dynamicChildren&&((o.patchFlag<=0||o.patchFlag===32)&&(o=i[t]=eA(i[t]),o.el=a.el),!n&&o.patchFlag!==-2&&lk(a,o)),o.type===Ok&&(o.patchFlag===-1?o.__elIndex=t+(e.type===Dk?1:0):o.el=a.el),o.type===kk&&!o.el&&(o.el=a.el)}}function uk(e){let t=e.slice(),n=[0],r,i,a,o,s,c=e.length;for(r=0;r<c;r++){let c=e[r];if(c!==0){if(i=n[n.length-1],e[i]<c){t[r]=i,n.push(r);continue}for(a=0,o=n.length-1;a<o;)s=a+o>>1,e[n[s]]<c?a=s+1:o=s;c<e[n[a]]&&(a>0&&(t[r]=n[a-1]),n[a]=r)}}for(a=n.length,o=n[a-1];a-- >0;)n[a]=o,o=t[o];return n}function dk(e){let t=e.subTree.component;if(t)return t.asyncDep&&!t.asyncResolved?t:dk(t)}function fk(e){if(e)for(let t=0;t<e.length;t++)e[t].flags|=8}function pk(e){if(e.placeholder)return e.placeholder;let t=e.component;return t?pk(t.subTree):null}var mk=e=>e.__isSuspense,hk=0,gk={name:`Suspense`,__isSuspense:!0,process(e,t,n,r,i,a,o,s,c,l){if(e==null)vk(t,n,r,i,a,o,s,c,l);else{if(a&&a.deps>0&&!e.suspense.isInFallback){t.suspense=e.suspense,t.suspense.vnode=t,t.el=e.el;return}yk(e,t,n,r,i,o,s,c,l)}},hydrate:xk,normalize:Sk};function _k(e,t){let n=e.props&&e.props[t];Y(n)&&n()}function vk(e,t,n,r,i,a,o,s,c){let{p:l,o:{createElement:u}}=c,d=u(`div`),f=e.suspense=bk(e,i,r,t,d,n,a,o,s,c);l(null,f.pendingBranch=e.ssContent,d,null,r,f,a,o),f.deps>0?(_k(e,`onPending`),_k(e,`onFallback`),l(null,e.ssFallback,t,n,r,null,a,o),Tk(f,e.ssFallback)):f.resolve(!1,!0)}function yk(e,t,n,r,i,a,o,s,{p:c,um:l,o:{createElement:u}}){let d=t.suspense=e.suspense;d.vnode=t,t.el=e.el;let f=t.ssContent,p=t.ssFallback,{activeBranch:m,pendingBranch:h,isInFallback:g,isHydrating:_}=d;if(h)d.pendingBranch=f,Vk(h,f)?(c(h,f,d.hiddenContainer,null,i,d,a,o,s),d.deps<=0?d.resolve():g&&(_||(c(m,p,n,r,i,null,a,o,s),Tk(d,p)))):(d.pendingId=hk++,_?(d.isHydrating=!1,d.activeBranch=h):l(h,i,d),d.deps=0,d.effects.length=0,d.hiddenContainer=u(`div`),g?(c(null,f,d.hiddenContainer,null,i,d,a,o,s),d.deps<=0?d.resolve():(c(m,p,n,r,i,null,a,o,s),Tk(d,p))):m&&Vk(m,f)?(c(m,f,n,r,i,d,a,o,s),d.resolve(!0)):(c(null,f,d.hiddenContainer,null,i,d,a,o,s),d.deps<=0&&d.resolve()));else if(m&&Vk(m,f))c(m,f,n,r,i,d,a,o,s),Tk(d,f);else if(_k(t,`onPending`),d.pendingBranch=f,f.shapeFlag&512?d.pendingId=f.component.suspenseId:d.pendingId=hk++,c(null,f,d.hiddenContainer,null,i,d,a,o,s),d.deps<=0)d.resolve();else{let{timeout:e,pendingId:t}=d;e>0?setTimeout(()=>{d.pendingId===t&&d.fallback(p)},e):e===0&&d.fallback(p)}}function bk(e,t,n,r,i,a,o,s,c,l,u=!1){let{p:d,m:f,um:p,n:m,o:{parentNode:h,remove:g}}=l,_,v=Ek(e);v&&t&&t.pendingBranch&&(_=t.pendingId,t.deps++);let y=e.props?qw(e.props.timeout):void 0,b=a,x={vnode:e,parent:t,parentComponent:n,namespace:o,container:r,hiddenContainer:i,deps:0,pendingId:hk++,timeout:typeof y==`number`?y:-1,activeBranch:null,pendingBranch:null,isInFallback:!u,isHydrating:u,isUnmounted:!1,effects:[],resolve(e=!1,n=!1){let{vnode:r,activeBranch:i,pendingBranch:o,pendingId:s,effects:c,parentComponent:l,container:u,isInFallback:d}=x,g=!1;x.isHydrating?x.isHydrating=!1:e||(g=i&&o.transition&&o.transition.mode===`out-in`,g&&(i.transition.afterLeave=()=>{s===x.pendingId&&(f(o,u,a===b?m(i):a,0),kT(c),d&&r.ssFallback&&(r.ssFallback.el=null))}),i&&(h(i.el)===u&&(a=m(i)),p(i,l,x,!0),!g&&d&&r.ssFallback&&nk(()=>r.ssFallback.el=null,x)),g||f(o,u,a,0)),Tk(x,o),x.pendingBranch=null,x.isInFallback=!1;let y=x.parent,S=!1;for(;y;){if(y.pendingBranch){y.effects.push(...c),S=!0;break}y=y.parent}!S&&!g&&kT(c),x.effects=[],v&&t&&t.pendingBranch&&_===t.pendingId&&(t.deps--,t.deps===0&&!n&&t.resolve()),_k(r,`onResolve`)},fallback(e){if(!x.pendingBranch)return;let{vnode:t,activeBranch:n,parentComponent:r,container:i,namespace:a}=x;_k(t,`onFallback`);let o=m(n),l=()=>{x.isInFallback&&(d(null,e,i,o,r,null,a,s,c),Tk(x,e))},u=e.transition&&e.transition.mode===`out-in`;u&&(n.transition.afterLeave=l),x.isInFallback=!0,p(n,r,null,!0),u||l()},move(e,t,n){x.activeBranch&&f(x.activeBranch,e,t,n),x.container=e},next(){return x.activeBranch&&m(x.activeBranch)},registerDep(e,t,n){let r=!!x.pendingBranch;r&&x.deps++;let i=e.vnode.el;e.asyncDep.catch(t=>{gT(t,e,0)}).then(a=>{if(e.isUnmounted||x.isUnmounted||x.pendingId!==e.suspenseId)return;e.asyncResolved=!0;let{vnode:s}=e;_A(e,a,!1),i&&(s.el=i);let c=!i&&e.subTree.el;t(e,s,h(i||e.subTree.el),i?null:m(e.subTree),x,o,n),c&&(s.placeholder=null,g(c)),LO(e,s.el),r&&--x.deps===0&&x.resolve()})},unmount(e,t){x.isUnmounted=!0,x.activeBranch&&p(x.activeBranch,n,e,t),x.pendingBranch&&p(x.pendingBranch,n,e,t)}};return x}function xk(e,t,n,r,i,a,o,s,c){let l=t.suspense=bk(t,r,n,e.parentNode,document.createElement(`div`),null,i,a,o,s,!0),u=c(e,l.pendingBranch=t.ssContent,n,l,a,o);return l.deps===0&&l.resolve(!1,!0),u}function Sk(e){let{shapeFlag:t,children:n}=e,r=t&32;e.ssContent=Ck(r?n.default:n),e.ssFallback=r?Ck(n.fallback):Kk(kk)}function Ck(e){let t;if(Y(e)){let n=Fk&&e._c;n&&(e._d=!1,Nk()),e=e(),n&&(e._d=!0,t=Mk,Pk())}return J(e)&&(e=MO(e)),e=$k(e),t&&!e.dynamicChildren&&(e.dynamicChildren=t.filter(t=>t!==e)),e}function wk(e,t){t&&t.pendingBranch?J(e)?t.effects.push(...e):t.effects.push(e):kT(e)}function Tk(e,t){e.activeBranch=t;let{vnode:n,parentComponent:r}=e,i=t.el;for(;!i&&t.component;)t=t.component.subTree,i=t.el;n.el=i,r&&r.subTree===n&&(r.vnode.el=i,LO(r,i))}function Ek(e){let t=e.props&&e.props.suspensible;return t!=null&&t!==!1}var Dk=Symbol.for(`v-fgt`),Ok=Symbol.for(`v-txt`),kk=Symbol.for(`v-cmt`),Ak=Symbol.for(`v-stc`),jk=[],Mk=null;function Nk(e=!1){jk.push(Mk=e?null:[])}function Pk(){jk.pop(),Mk=jk[jk.length-1]||null}var Fk=1;function Ik(e,t=!1){Fk+=e,e<0&&Mk&&t&&(Mk.hasOnce=!0)}function Lk(e){return e.dynamicChildren=Fk>0?Mk||gw:null,Pk(),Fk>0&&Mk&&Mk.push(e),e}function Rk(e,t,n,r,i,a){return Lk(Gk(e,t,n,r,i,a,!0))}function zk(e,t,n,r,i){return Lk(Kk(e,t,n,r,i,!0))}function Bk(e){return e?e.__v_isVNode===!0:!1}function Vk(e,t){return e.type===t.type&&e.key===t.key}function Hk(e){}var Uk=({key:e})=>e??null,Wk=({ref:e,ref_key:t,ref_for:n})=>(typeof e==`number`&&(e=``+e),e==null?null:Ow(e)||VC(e)||Y(e)?{i:LT,r:e,k:t,f:!!n}:e);function Gk(e,t=null,n=null,r=0,i=null,a=e===Dk?0:1,o=!1,s=!1){let c={__v_isVNode:!0,__v_skip:!0,type:e,props:t,key:t&&Uk(t),ref:t&&Wk(t),scopeId:RT,slotScopeIds:null,children:n,component:null,suspense:null,ssContent:null,ssFallback:null,dirs:null,transition:null,el:null,anchor:null,target:null,targetStart:null,targetAnchor:null,staticCount:0,shapeFlag:a,patchFlag:r,dynamicProps:i,dynamicChildren:null,appContext:null,ctx:LT};return s?(tA(c,n),a&128&&e.normalize(c)):n&&(c.shapeFlag|=Ow(n)?8:16),Fk>0&&!o&&Mk&&(c.patchFlag>0||a&6)&&c.patchFlag!==32&&Mk.push(c),c}var Kk=qk;function qk(e,t=null,n=null,r=0,i=null,a=!1){if((!e||e===AD)&&(e=kk),Bk(e)){let r=Yk(e,t,!0);return n&&tA(r,n),Fk>0&&!a&&Mk&&(r.shapeFlag&6?Mk[Mk.indexOf(e)]=r:Mk.push(r)),r.patchFlag=-2,r}if(DA(e)&&(e=e.__vccOpts),t){t=Jk(t);let{class:e,style:n}=t;e&&!Ow(e)&&(t.class=nT(e)),Aw(n)&&(LC(n)&&!J(n)&&(n=xw({},n)),t.style=Zw(n))}let o=Ow(e)?1:mk(e)?128:aE(e)?64:Aw(e)?4:Y(e)?2:0;return Gk(e,t,n,r,i,o,a,!0)}function Jk(e){return e?LC(e)||BO(e)?xw({},e):e:null}function Yk(e,t,n=!1,r=!1){let{props:i,ref:a,patchFlag:o,children:s,transition:c}=e,l=t?nA(i||{},t):i,u={__v_isVNode:!0,__v_skip:!0,type:e.type,props:l,key:l&&Uk(l),ref:t&&t.ref?n&&a?J(a)?a.concat(Wk(t)):[a,Wk(t)]:Wk(t):a,scopeId:e.scopeId,slotScopeIds:e.slotScopeIds,children:s,target:e.target,targetStart:e.targetStart,targetAnchor:e.targetAnchor,staticCount:e.staticCount,shapeFlag:e.shapeFlag,patchFlag:t&&e.type!==Dk?o===-1?16:o|16:o,dynamicProps:e.dynamicProps,dynamicChildren:e.dynamicChildren,appContext:e.appContext,dirs:e.dirs,transition:c,component:e.component,suspense:e.suspense,ssContent:e.ssContent&&Yk(e.ssContent),ssFallback:e.ssFallback&&Yk(e.ssFallback),placeholder:e.placeholder,el:e.el,anchor:e.anchor,ctx:e.ctx,ce:e.ce};return c&&r&&AE(u,c.clone(u)),u}function Xk(e=` `,t=0){return Kk(Ok,null,e,t)}function Zk(e,t){let n=Kk(Ak,null,e);return n.staticCount=t,n}function Qk(e=``,t=!1){return t?(Nk(),zk(kk,null,e)):Kk(kk,null,e)}function $k(e){return e==null||typeof e==`boolean`?Kk(kk):J(e)?Kk(Dk,null,e.slice()):Bk(e)?eA(e):Kk(Ok,null,String(e))}function eA(e){return e.el===null&&e.patchFlag!==-1||e.memo?e:Yk(e)}function tA(e,t){let n=0,{shapeFlag:r}=e;if(t==null)t=null;else if(J(t))n=16;else if(typeof t==`object`)if(r&65){let n=t.default;n&&(n._c&&(n._d=!1),tA(e,n()),n._c&&(n._d=!0));return}else{n=32;let r=t._;!r&&!BO(t)?t._ctx=LT:r===3&&LT&&(LT.slots._===1?t._=1:(t._=2,e.patchFlag|=1024))}else Y(t)?(t={default:t,_ctx:LT},n=32):(t=String(t),r&64?(n=16,t=[Xk(t)]):n=8);e.children=t,e.shapeFlag|=n}function nA(...e){let t={};for(let n=0;n<e.length;n++){let r=e[n];for(let e in r)if(e===`class`)t.class!==r.class&&(t.class=nT([t.class,r.class]));else if(e===`style`)t.style=Zw([t.style,r.style]);else if(yw(e)){let n=t[e],i=r[e];i&&n!==i&&!(J(n)&&n.includes(i))&&(t[e]=n?[].concat(n,i):i)}else e!==``&&(t[e]=r[e])}return t}function rA(e,t,n,r=null){hT(e,t,7,[n,r])}var iA=xO(),aA=0;function oA(e,t,n){let r=e.type,i=(t?t.appContext:e.appContext)||iA,a={uid:aA++,vnode:e,type:r,parent:t,appContext:i,root:null,next:null,subTree:null,effect:null,update:null,job:null,scope:new dS(!0),render:null,proxy:null,exposed:null,exposeProxy:null,withProxy:null,provides:t?t.provides:Object.create(i.provides),ids:t?t.ids:[``,0,0],accessCache:null,renderCache:[],components:null,directives:null,propsOptions:KO(r,i),emitsOptions:kO(r,i),emit:null,emitted:null,propsDefaults:q,inheritAttrs:r.inheritAttrs,ctx:q,data:q,props:q,attrs:q,slots:q,refs:q,setupState:q,setupContext:null,suspense:n,suspenseId:n?n.pendingId:0,asyncDep:null,asyncResolved:!1,isMounted:!1,isUnmounted:!1,isDeactivated:!1,bc:null,c:null,bm:null,m:null,bu:null,u:null,um:null,bum:null,da:null,a:null,rtg:null,rtc:null,ec:null,sp:null};return a.ctx={_:a},a.root=t?t.root:a,a.emit=DO.bind(null,a),e.ce&&e.ce(a),a}var sA=null,cA=()=>sA||LT,lA,uA;{let e=Yw(),t=(t,n)=>{let r;return(r=e[t])||(r=e[t]=[]),r.push(n),e=>{r.length>1?r.forEach(t=>t(e)):r[0](e)}};lA=t(`__VUE_INSTANCE_SETTERS__`,e=>sA=e),uA=t(`__VUE_SSR_SETTERS__`,e=>mA=e)}var dA=e=>{let t=sA;return lA(e),e.scope.on(),()=>{e.scope.off(),lA(t)}},fA=()=>{sA&&sA.scope.off(),lA(null)};function pA(e){return e.vnode.shapeFlag&4}var mA=!1;function hA(e,t=!1,n=!1){t&&uA(t);let{props:r,children:i}=e.vnode,a=pA(e);VO(e,r,a,t),ek(e,i,n||t);let o=a?gA(e,t):void 0;return t&&uA(!1),o}function gA(e,t){let n=e.type;e.accessCache=Object.create(null),e.proxy=new Proxy(e.ctx,UD);let{setup:r}=n;if(r){NS();let n=e.setupContext=r.length>1?wA(e):null,i=dA(e),a=mT(r,e,0,[e.props,n]),o=jw(a);if(PS(),i(),(o||e.sp)&&!rD(e)&&PE(e),o){if(a.then(fA,fA),t)return a.then(n=>{_A(e,n,t)}).catch(t=>{gT(t,e,0)});e.asyncDep=a}else _A(e,a,t)}else SA(e,t)}function _A(e,t,n){Y(t)?e.type.__ssrInlineRender?e.ssrRender=t:e.render=t:Aw(t)&&(e.setupState=XC(t)),SA(e,n)}var vA,yA;function bA(e){vA=e,yA=e=>{e.render._rc&&(e.withProxy=new Proxy(e.ctx,WD))}}var xA=()=>!vA;function SA(e,t,n){let r=e.type;if(!e.render){if(!t&&vA&&!r.render){let t=r.template||dO(e).template;if(t){let{isCustomElement:n,compilerOptions:i}=e.appContext.config,{delimiters:a,compilerOptions:o}=r,s=xw(xw({isCustomElement:n,delimiters:a},i),o);r.render=vA(t,s)}}e.render=r.render||_w,yA&&yA(e)}{let t=dA(e);NS();try{sO(e)}finally{PS(),t()}}}var CA={get(e,t){return WS(e,`get`,``),e[t]}};function wA(e){return{attrs:new Proxy(e.attrs,CA),slots:e.slots,emit:e.emit,expose:t=>{e.exposed=t||{}}}}function TA(e){return e.exposed?e.exposeProxy||=new Proxy(XC(RC(e.exposed)),{get(t,n){if(n in t)return t[n];if(n in VD)return VD[n](e)},has(e,t){return t in e||t in VD}}):e.proxy}function EA(e,t=!0){return Y(e)?e.displayName||e.name:e.name||t&&e.__name}function DA(e){return Y(e)&&`__vccOpts`in e}var X=(e,t)=>aw(e,t,mA);function OA(e,t,n){try{Ik(-1);let r=arguments.length;return r===2?Aw(t)&&!J(t)?Bk(t)?Kk(e,null,[t]):Kk(e,t):Kk(e,null,t):(r>3?n=Array.prototype.slice.call(arguments,2):r===3&&Bk(n)&&(n=[n]),Kk(e,t,n))}finally{Ik(1)}}function kA(){return;function e(t,n,r){let i=t[r];if(J(i)&&i.includes(n)||Aw(i)&&n in i||t.extends&&e(t.extends,n,r)||t.mixins&&t.mixins.some(t=>e(t,n,r)))return!0}}function AA(e,t,n,r){let i=n[r];if(i&&jA(i,e))return i;let a=t();return a.memo=e.slice(),a.cacheIndex=r,n[r]=a}function jA(e,t){let n=e.memo;if(n.length!=t.length)return!1;for(let e=0;e<n.length;e++)if(Uw(n[e],t[e]))return!1;return Fk>0&&Mk&&Mk.push(e),!0}var MA=`3.5.27`,NA=_w,PA=pT,FA=PT,IA=IT,LA={createComponentInstance:oA,setupComponent:hA,renderComponentRoot:jO,setCurrentRenderingInstance:zT,isVNode:Bk,normalizeVNode:$k,getComponentPublicInstance:TA,ensureValidVNode:RD,pushWarningContext:lT,popWarningContext:uT};function RA(e){let t=Object.create(null);for(let n of e.split(`,`))t[n]=1;return e=>e in t}var zA={},BA=()=>{},VA=e=>e.charCodeAt(0)===111&&e.charCodeAt(1)===110&&(e.charCodeAt(2)>122||e.charCodeAt(2)<97),HA=e=>e.startsWith(`onUpdate:`),UA=Object.assign,WA=Object.prototype.hasOwnProperty,GA=(e,t)=>WA.call(e,t),KA=Array.isArray,qA=e=>ej(e)===`[object Set]`,JA=e=>ej(e)===`[object Date]`,YA=e=>typeof e==`function`,XA=e=>typeof e==`string`,ZA=e=>typeof e==`symbol`,QA=e=>typeof e==`object`&&!!e,$A=Object.prototype.toString,ej=e=>$A.call(e),tj=e=>ej(e)===`[object Object]`,nj=e=>{let t=Object.create(null);return(n=>t[n]||(t[n]=e(n)))},rj=/-\w/g,ij=nj(e=>e.replace(rj,e=>e.slice(1).toUpperCase())),aj=/\B([A-Z])/g,oj=nj(e=>e.replace(aj,`-$1`).toLowerCase()),sj=nj(e=>e.charAt(0).toUpperCase()+e.slice(1)),cj=(e,...t)=>{for(let n=0;n<e.length;n++)e[n](...t)},lj=e=>{let t=parseFloat(e);return isNaN(t)?e:t},uj=e=>{let t=XA(e)?Number(e):NaN;return isNaN(t)?e:t},dj=`itemscope,allowfullscreen,formnovalidate,ismap,nomodule,novalidate,readonly`,fj=RA(dj);dj+``;function pj(e){return!!e||e===``}function mj(e,t){if(e.length!==t.length)return!1;let n=!0;for(let r=0;n&&r<e.length;r++)n=hj(e[r],t[r]);return n}function hj(e,t){if(e===t)return!0;let n=JA(e),r=JA(t);if(n||r)return n&&r?e.getTime()===t.getTime():!1;if(n=ZA(e),r=ZA(t),n||r)return e===t;if(n=KA(e),r=KA(t),n||r)return n&&r?mj(e,t):!1;if(n=QA(e),r=QA(t),n||r){if(!n||!r||Object.keys(e).length!==Object.keys(t).length)return!1;for(let n in e){let r=e.hasOwnProperty(n),i=t.hasOwnProperty(n);if(r&&!i||!r&&i||!hj(e[n],t[n]))return!1}}return String(e)===String(t)}function gj(e,t){return e.findIndex(e=>hj(e,t))}function _j(e){return e==null?`initial`:typeof e==`string`?e===``?` `:e:String(e)}var vj=a({BaseTransition:()=>TE,BaseTransitionPropsValidators:()=>xE,Comment:()=>kk,DeprecationTypes:()=>null,EffectScope:()=>dS,ErrorCodes:()=>fT,ErrorTypeStrings:()=>PA,Fragment:()=>Dk,KeepAlive:()=>sD,ReactiveEffect:()=>gS,Static:()=>Ak,Suspense:()=>gk,Teleport:()=>mE,Text:()=>Ok,TrackOpTypes:()=>ow,Transition:()=>Mj,TransitionGroup:()=>zM,TriggerOpTypes:()=>sw,VueElement:()=>jM,assertNumber:()=>dT,callWithAsyncErrorHandling:()=>hT,callWithErrorHandling:()=>mT,camelize:()=>Rw,capitalize:()=>Vw,cloneVNode:()=>Yk,compatUtils:()=>null,computed:()=>X,createApp:()=>yN,createBlock:()=>zk,createCommentVNode:()=>Qk,createElementBlock:()=>Rk,createElementVNode:()=>Gk,createHydrationRenderer:()=>ik,createPropsRestProxy:()=>iO,createRenderer:()=>rk,createSSRApp:()=>bN,createSlots:()=>ID,createStaticVNode:()=>Zk,createTextVNode:()=>Xk,createVNode:()=>Kk,customRef:()=>QC,defineAsyncComponent:()=>iD,defineComponent:()=>ME,defineCustomElement:()=>OM,defineEmits:()=>KD,defineExpose:()=>qD,defineModel:()=>XD,defineOptions:()=>JD,defineProps:()=>GD,defineSSRCustomElement:()=>kM,defineSlots:()=>YD,devtools:()=>FA,effect:()=>kS,effectScope:()=>fS,getCurrentInstance:()=>cA,getCurrentScope:()=>pS,getCurrentWatcher:()=>dw,getTransitionRawChildren:()=>jE,guardReactiveProps:()=>Jk,h:()=>OA,handleError:()=>gT,hasInjectionContext:()=>JT,hydrate:()=>vN,hydrateOnIdle:()=>ZE,hydrateOnInteraction:()=>tD,hydrateOnMediaQuery:()=>eD,hydrateOnVisible:()=>$E,initCustomFormatter:()=>kA,initDirectivesForSSR:()=>wN,inject:()=>qT,isMemoSame:()=>jA,isProxy:()=>LC,isReactive:()=>PC,isReadonly:()=>FC,isRef:()=>VC,isRuntimeOnly:()=>xA,isShallow:()=>IC,isVNode:()=>Bk,markRaw:()=>RC,mergeDefaults:()=>nO,mergeModels:()=>rO,mergeProps:()=>nA,nextTick:()=>TT,nodeOps:()=>Ej,normalizeClass:()=>nT,normalizeProps:()=>rT,normalizeStyle:()=>Zw,onActivated:()=>lD,onBeforeMount:()=>_D,onBeforeUnmount:()=>xD,onBeforeUpdate:()=>yD,onDeactivated:()=>uD,onErrorCaptured:()=>ED,onMounted:()=>vD,onRenderTracked:()=>TD,onRenderTriggered:()=>wD,onScopeDispose:()=>mS,onServerPrefetch:()=>CD,onUnmounted:()=>SD,onUpdated:()=>bD,onWatcherCleanup:()=>fw,openBlock:()=>Nk,patchProp:()=>TM,popScopeId:()=>VT,provide:()=>KT,proxyRefs:()=>XC,pushScopeId:()=>BT,queuePostFlushCb:()=>kT,reactive:()=>kC,readonly:()=>jC,ref:()=>HC,registerRuntimeCompiler:()=>bA,render:()=>_N,renderList:()=>FD,renderSlot:()=>LD,resolveComponent:()=>kD,resolveDirective:()=>MD,resolveDynamicComponent:()=>jD,resolveFilter:()=>null,resolveTransitionHooks:()=>DE,setBlockTracking:()=>Ik,setDevtoolsHook:()=>IA,setTransitionHooks:()=>AE,shallowReactive:()=>AC,shallowReadonly:()=>MC,shallowRef:()=>UC,ssrContextKey:()=>YT,ssrUtils:()=>LA,stop:()=>AS,toDisplayString:()=>aT,toHandlerKey:()=>Hw,toHandlers:()=>zD,toRaw:()=>K,toRef:()=>nw,toRefs:()=>$C,toValue:()=>JC,transformVNodeArgs:()=>Hk,triggerRef:()=>KC,unref:()=>qC,useAttrs:()=>$D,useCssModule:()=>PM,useCssVars:()=>eM,useHost:()=>MM,useId:()=>NE,useModel:()=>TO,useSSRContext:()=>XT,useShadowRoot:()=>NM,useSlots:()=>QD,useTemplateRef:()=>FE,useTransitionState:()=>yE,vModelCheckbox:()=>XM,vModelDynamic:()=>rN,vModelRadio:()=>QM,vModelSelect:()=>$M,vModelText:()=>YM,vShow:()=>Xj,version:()=>MA,warn:()=>NA,watch:()=>eE,watchEffect:()=>ZT,watchPostEffect:()=>QT,watchSyncEffect:()=>$T,withAsyncContext:()=>aO,withCtx:()=>UT,withDefaults:()=>ZD,withDirectives:()=>WT,withKeys:()=>dN,withMemo:()=>AA,withModifiers:()=>lN,withScopeId:()=>HT}),yj=void 0,bj=typeof window<`u`&&window.trustedTypes;if(bj)try{yj=bj.createPolicy(`vue`,{createHTML:e=>e})}catch{}var xj=yj?e=>yj.createHTML(e):e=>e,Sj=`http://www.w3.org/2000/svg`,Cj=`http://www.w3.org/1998/Math/MathML`,wj=typeof document<`u`?document:null,Tj=wj&&wj.createElement(`template`),Ej={insert:(e,t,n)=>{t.insertBefore(e,n||null)},remove:e=>{let t=e.parentNode;t&&t.removeChild(e)},createElement:(e,t,n,r)=>{let i=t===`svg`?wj.createElementNS(Sj,e):t===`mathml`?wj.createElementNS(Cj,e):n?wj.createElement(e,{is:n}):wj.createElement(e);return e===`select`&&r&&r.multiple!=null&&i.setAttribute(`multiple`,r.multiple),i},createText:e=>wj.createTextNode(e),createComment:e=>wj.createComment(e),setText:(e,t)=>{e.nodeValue=t},setElementText:(e,t)=>{e.textContent=t},parentNode:e=>e.parentNode,nextSibling:e=>e.nextSibling,querySelector:e=>wj.querySelector(e),setScopeId(e,t){e.setAttribute(t,``)},insertStaticContent(e,t,n,r,i,a){let o=n?n.previousSibling:t.lastChild;if(i&&(i===a||i.nextSibling))for(;t.insertBefore(i.cloneNode(!0),n),!(i===a||!(i=i.nextSibling)););else{Tj.innerHTML=xj(r===`svg`?`<svg>${e}</svg>`:r===`mathml`?`<math>${e}</math>`:e);let i=Tj.content;if(r===`svg`||r===`mathml`){let e=i.firstChild;for(;e.firstChild;)i.appendChild(e.firstChild);i.removeChild(e)}t.insertBefore(i,n)}return[o?o.nextSibling:t.firstChild,n?n.previousSibling:t.lastChild]}},Dj=`transition`,Oj=`animation`,kj=Symbol(`_vtc`),Aj={name:String,type:String,css:{type:Boolean,default:!0},duration:[String,Number,Object],enterFromClass:String,enterActiveClass:String,enterToClass:String,appearFromClass:String,appearActiveClass:String,appearToClass:String,leaveFromClass:String,leaveActiveClass:String,leaveToClass:String},jj=UA({},xE,Aj),Mj=(e=>(e.displayName=`Transition`,e.props=jj,e))((e,{slots:t})=>OA(TE,Fj(e),t)),Nj=(e,t=[])=>{KA(e)?e.forEach(e=>e(...t)):e&&e(...t)},Pj=e=>e?KA(e)?e.some(e=>e.length>1):e.length>1:!1;function Fj(e){let t={};for(let n in e)n in Aj||(t[n]=e[n]);if(e.css===!1)return t;let{name:n=`v`,type:r,duration:i,enterFromClass:a=`${n}-enter-from`,enterActiveClass:o=`${n}-enter-active`,enterToClass:s=`${n}-enter-to`,appearFromClass:c=a,appearActiveClass:l=o,appearToClass:u=s,leaveFromClass:d=`${n}-leave-from`,leaveActiveClass:f=`${n}-leave-active`,leaveToClass:p=`${n}-leave-to`}=e,m=Ij(i),h=m&&m[0],g=m&&m[1],{onBeforeEnter:_,onEnter:v,onEnterCancelled:y,onLeave:b,onLeaveCancelled:x,onBeforeAppear:S=_,onAppear:C=v,onAppearCancelled:w=y}=t,T=(e,t,n,r)=>{e._enterCancelled=r,zj(e,t?u:s),zj(e,t?l:o),n&&n()},E=(e,t)=>{e._isLeaving=!1,zj(e,d),zj(e,p),zj(e,f),t&&t()},D=e=>(t,n)=>{let i=e?C:v,o=()=>T(t,e,n);Nj(i,[t,o]),Bj(()=>{zj(t,e?c:a),Rj(t,e?u:s),Pj(i)||Hj(t,r,h,o)})};return UA(t,{onBeforeEnter(e){Nj(_,[e]),Rj(e,a),Rj(e,o)},onBeforeAppear(e){Nj(S,[e]),Rj(e,c),Rj(e,l)},onEnter:D(!1),onAppear:D(!0),onLeave(e,t){e._isLeaving=!0;let n=()=>E(e,t);Rj(e,d),e._enterCancelled?(Rj(e,f),Kj(e)):(Kj(e),Rj(e,f)),Bj(()=>{e._isLeaving&&(zj(e,d),Rj(e,p),Pj(b)||Hj(e,r,g,n))}),Nj(b,[e,n])},onEnterCancelled(e){T(e,!1,void 0,!0),Nj(y,[e])},onAppearCancelled(e){T(e,!0,void 0,!0),Nj(w,[e])},onLeaveCancelled(e){E(e),Nj(x,[e])}})}function Ij(e){if(e==null)return null;if(QA(e))return[Lj(e.enter),Lj(e.leave)];{let t=Lj(e);return[t,t]}}function Lj(e){return uj(e)}function Rj(e,t){t.split(/\s+/).forEach(t=>t&&e.classList.add(t)),(e[kj]||(e[kj]=new Set)).add(t)}function zj(e,t){t.split(/\s+/).forEach(t=>t&&e.classList.remove(t));let n=e[kj];n&&(n.delete(t),n.size||(e[kj]=void 0))}function Bj(e){requestAnimationFrame(()=>{requestAnimationFrame(e)})}var Vj=0;function Hj(e,t,n,r){let i=e._endId=++Vj,a=()=>{i===e._endId&&r()};if(n!=null)return setTimeout(a,n);let{type:o,timeout:s,propCount:c}=Uj(e,t);if(!o)return r();let l=o+`end`,u=0,d=()=>{e.removeEventListener(l,f),a()},f=t=>{t.target===e&&++u>=c&&d()};setTimeout(()=>{u<c&&d()},s+1),e.addEventListener(l,f)}function Uj(e,t){let n=window.getComputedStyle(e),r=e=>(n[e]||``).split(`, `),i=r(`${Dj}Delay`),a=r(`${Dj}Duration`),o=Wj(i,a),s=r(`${Oj}Delay`),c=r(`${Oj}Duration`),l=Wj(s,c),u=null,d=0,f=0;t===Dj?o>0&&(u=Dj,d=o,f=a.length):t===Oj?l>0&&(u=Oj,d=l,f=c.length):(d=Math.max(o,l),u=d>0?o>l?Dj:Oj:null,f=u?u===Dj?a.length:c.length:0);let p=u===Dj&&/\b(?:transform|all)(?:,|$)/.test(r(`${Dj}Property`).toString());return{type:u,timeout:d,propCount:f,hasTransform:p}}function Wj(e,t){for(;e.length<t.length;)e=e.concat(e);return Math.max(...t.map((t,n)=>Gj(t)+Gj(e[n])))}function Gj(e){return e===`auto`?0:Number(e.slice(0,-1).replace(`,`,`.`))*1e3}function Kj(e){return(e?e.ownerDocument:document).body.offsetHeight}function qj(e,t,n){let r=e[kj];r&&(t=(t?[t,...r]:[...r]).join(` `)),t==null?e.removeAttribute(`class`):n?e.setAttribute(`class`,t):e.className=t}var Jj=Symbol(`_vod`),Yj=Symbol(`_vsh`),Xj={name:`show`,beforeMount(e,{value:t},{transition:n}){e[Jj]=e.style.display===`none`?``:e.style.display,n&&t?n.beforeEnter(e):Zj(e,t)},mounted(e,{value:t},{transition:n}){n&&t&&n.enter(e)},updated(e,{value:t,oldValue:n},{transition:r}){!t!=!n&&(r?t?(r.beforeEnter(e),Zj(e,!0),r.enter(e)):r.leave(e,()=>{Zj(e,!1)}):Zj(e,t))},beforeUnmount(e,{value:t}){Zj(e,t)}};function Zj(e,t){e.style.display=t?e[Jj]:`none`,e[Yj]=!t}function Qj(){Xj.getSSRProps=({value:e})=>{if(!e)return{style:{display:`none`}}}}var $j=Symbol(``);function eM(e){let t=cA();if(!t)return;let n=t.ut=(n=e(t.proxy))=>{Array.from(document.querySelectorAll(`[data-v-owner="${t.uid}"]`)).forEach(e=>nM(e,n))},r=()=>{let r=e(t.proxy);t.ce?nM(t.ce,r):tM(t.subTree,r),n(r)};yD(()=>{kT(r)}),vD(()=>{eE(r,BA,{flush:`post`});let e=new MutationObserver(r);e.observe(t.subTree.el.parentNode,{childList:!0}),SD(()=>e.disconnect())})}function tM(e,t){if(e.shapeFlag&128){let n=e.suspense;e=n.activeBranch,n.pendingBranch&&!n.isHydrating&&n.effects.push(()=>{tM(n.activeBranch,t)})}for(;e.component;)e=e.component.subTree;if(e.shapeFlag&1&&e.el)nM(e.el,t);else if(e.type===Dk)e.children.forEach(e=>tM(e,t));else if(e.type===Ak){let{el:n,anchor:r}=e;for(;n&&(nM(n,t),n!==r);)n=n.nextSibling}}function nM(e,t){if(e.nodeType===1){let n=e.style,r=``;for(let e in t){let i=_j(t[e]);n.setProperty(`--${e}`,i),r+=`--${e}: ${i};`}n[$j]=r}}var rM=/(?:^|;)\s*display\s*:/;function iM(e,t,n){let r=e.style,i=XA(n),a=!1;if(n&&!i){if(t)if(XA(t))for(let e of t.split(`;`)){let t=e.slice(0,e.indexOf(`:`)).trim();n[t]??oM(r,t,``)}else for(let e in t)n[e]??oM(r,e,``);for(let e in n)e===`display`&&(a=!0),oM(r,e,n[e])}else if(i){if(t!==n){let e=r[$j];e&&(n+=`;`+e),r.cssText=n,a=rM.test(n)}}else t&&e.removeAttribute(`style`);Jj in e&&(e[Jj]=a?r.display:``,e[Yj]&&(r.display=`none`))}var aM=/\s*!important$/;function oM(e,t,n){if(KA(n))n.forEach(n=>oM(e,t,n));else if(n??=``,t.startsWith(`--`))e.setProperty(t,n);else{let r=lM(e,t);aM.test(n)?e.setProperty(oj(r),n.replace(aM,``),`important`):e[r]=n}}var sM=[`Webkit`,`Moz`,`ms`],cM={};function lM(e,t){let n=cM[t];if(n)return n;let r=Rw(t);if(r!==`filter`&&r in e)return cM[t]=r;r=sj(r);for(let n=0;n<sM.length;n++){let i=sM[n]+r;if(i in e)return cM[t]=i}return t}var uM=`http://www.w3.org/1999/xlink`;function dM(e,t,n,r,i,a=fj(t)){r&&t.startsWith(`xlink:`)?n==null?e.removeAttributeNS(uM,t.slice(6,t.length)):e.setAttributeNS(uM,t,n):n==null||a&&!pj(n)?e.removeAttribute(t):e.setAttribute(t,a?``:ZA(n)?String(n):n)}function fM(e,t,n,r,i){if(t===`innerHTML`||t===`textContent`){n!=null&&(e[t]=t===`innerHTML`?xj(n):n);return}let a=e.tagName;if(t===`value`&&a!==`PROGRESS`&&!a.includes(`-`)){let r=a===`OPTION`?e.getAttribute(`value`)||``:e.value,i=n==null?e.type===`checkbox`?`on`:``:String(n);(r!==i||!(`_value`in e))&&(e.value=i),n??e.removeAttribute(t),e._value=n;return}let o=!1;if(n===``||n==null){let r=typeof e[t];r===`boolean`?n=pj(n):n==null&&r===`string`?(n=``,o=!0):r===`number`&&(n=0,o=!0)}try{e[t]=n}catch{}o&&e.removeAttribute(i||t)}function pM(e,t,n,r){e.addEventListener(t,n,r)}function mM(e,t,n,r){e.removeEventListener(t,n,r)}var hM=Symbol(`_vei`);function gM(e,t,n,r,i=null){let a=e[hM]||(e[hM]={}),o=a[t];if(r&&o)o.value=r;else{let[n,s]=vM(t);r?pM(e,n,a[t]=SM(r,i),s):o&&(mM(e,n,o,s),a[t]=void 0)}}var _M=/(?:Once|Passive|Capture)$/;function vM(e){let t;if(_M.test(e)){t={};let n;for(;n=e.match(_M);)e=e.slice(0,e.length-n[0].length),t[n[0].toLowerCase()]=!0}return[e[2]===`:`?e.slice(3):oj(e.slice(2)),t]}var yM=0,bM=Promise.resolve(),xM=()=>yM||=(bM.then(()=>yM=0),Date.now());function SM(e,t){let n=e=>{if(!e._vts)e._vts=Date.now();else if(e._vts<=n.attached)return;hT(CM(e,n.value),t,5,[e])};return n.value=e,n.attached=xM(),n}function CM(e,t){if(KA(t)){let n=e.stopImmediatePropagation;return e.stopImmediatePropagation=()=>{n.call(e),e._stopped=!0},t.map(e=>t=>!t._stopped&&e&&e(t))}else return t}var wM=e=>e.charCodeAt(0)===111&&e.charCodeAt(1)===110&&e.charCodeAt(2)>96&&e.charCodeAt(2)<123,TM=(e,t,n,r,i,a)=>{let o=i===`svg`;t===`class`?qj(e,r,o):t===`style`?iM(e,n,r):VA(t)?HA(t)||gM(e,t,n,r,a):(t[0]===`.`?(t=t.slice(1),!0):t[0]===`^`?(t=t.slice(1),!1):EM(e,t,r,o))?(fM(e,t,r),!e.tagName.includes(`-`)&&(t===`value`||t===`checked`||t===`selected`)&&dM(e,t,r,o,a,t!==`value`)):e._isVueCE&&(/[A-Z]/.test(t)||!XA(r))?fM(e,ij(t),r,a,t):(t===`true-value`?e._trueValue=r:t===`false-value`&&(e._falseValue=r),dM(e,t,r,o))};function EM(e,t,n,r){if(r)return!!(t===`innerHTML`||t===`textContent`||t in e&&wM(t)&&YA(n));if(t===`spellcheck`||t===`draggable`||t===`translate`||t===`autocorrect`||t===`sandbox`&&e.tagName===`IFRAME`||t===`form`||t===`list`&&e.tagName===`INPUT`||t===`type`&&e.tagName===`TEXTAREA`)return!1;if(t===`width`||t===`height`){let t=e.tagName;if(t===`IMG`||t===`VIDEO`||t===`CANVAS`||t===`SOURCE`)return!1}return wM(t)&&XA(n)?!1:t in e}var DM={};function OM(e,t,n){let r=ME(e,t);tj(r)&&(r=UA({},r,t));class i extends jM{constructor(e){super(r,e,n)}}return i.def=r,i}var kM=((e,t)=>OM(e,t,bN)),AM=typeof HTMLElement<`u`?HTMLElement:class{},jM=class e extends AM{constructor(e,t={},n=yN){super(),this._def=e,this._props=t,this._createApp=n,this._isVueCE=!0,this._instance=null,this._app=null,this._nonce=this._def.nonce,this._connected=!1,this._resolved=!1,this._patching=!1,this._dirty=!1,this._numberProps=null,this._styleChildren=new WeakSet,this._ob=null,this.shadowRoot&&n!==yN?this._root=this.shadowRoot:e.shadowRoot===!1?this._root=this:(this.attachShadow(UA({},e.shadowRootOptions,{mode:`open`})),this._root=this.shadowRoot)}connectedCallback(){if(!this.isConnected)return;!this.shadowRoot&&!this._resolved&&this._parseSlots(),this._connected=!0;let t=this;for(;t&&=t.parentNode||t.host;)if(t instanceof e){this._parent=t;break}this._instance||(this._resolved?this._mount(this._def):t&&t._pendingResolve?this._pendingResolve=t._pendingResolve.then(()=>{this._pendingResolve=void 0,this._resolveDef()}):this._resolveDef())}_setParent(e=this._parent){e&&(this._instance.parent=e._instance,this._inheritParentContext(e))}_inheritParentContext(e=this._parent){e&&this._app&&Object.setPrototypeOf(this._app._context.provides,e._instance.provides)}disconnectedCallback(){this._connected=!1,TT(()=>{this._connected||(this._ob&&=(this._ob.disconnect(),null),this._app&&this._app.unmount(),this._instance&&(this._instance.ce=void 0),this._app=this._instance=null,this._teleportTargets&&=(this._teleportTargets.clear(),void 0))})}_processMutations(e){for(let t of e)this._setAttr(t.attributeName)}_resolveDef(){if(this._pendingResolve)return;for(let e=0;e<this.attributes.length;e++)this._setAttr(this.attributes[e].name);this._ob=new MutationObserver(this._processMutations.bind(this)),this._ob.observe(this,{attributes:!0});let e=(e,t=!1)=>{this._resolved=!0,this._pendingResolve=void 0;let{props:n,styles:r}=e,i;if(n&&!KA(n))for(let e in n){let t=n[e];(t===Number||t&&t.type===Number)&&(e in this._props&&(this._props[e]=uj(this._props[e])),(i||=Object.create(null))[ij(e)]=!0)}this._numberProps=i,this._resolveProps(e),this.shadowRoot&&this._applyStyles(r),this._mount(e)},t=this._def.__asyncLoader;t?this._pendingResolve=t().then(t=>{t.configureApp=this._def.configureApp,e(this._def=t,!0)}):e(this._def)}_mount(e){this._app=this._createApp(e),this._inheritParentContext(),e.configureApp&&e.configureApp(this._app),this._app._ceVNode=this._createVNode(),this._app.mount(this._root);let t=this._instance&&this._instance.exposed;if(t)for(let e in t)GA(this,e)||Object.defineProperty(this,e,{get:()=>qC(t[e])})}_resolveProps(e){let{props:t}=e,n=KA(t)?t:Object.keys(t||{});for(let e of Object.keys(this))e[0]!==`_`&&n.includes(e)&&this._setProp(e,this[e]);for(let e of n.map(ij))Object.defineProperty(this,e,{get(){return this._getProp(e)},set(t){this._setProp(e,t,!0,!this._patching)}})}_setAttr(e){if(e.startsWith(`data-v-`))return;let t=this.hasAttribute(e),n=t?this.getAttribute(e):DM,r=ij(e);t&&this._numberProps&&this._numberProps[r]&&(n=uj(n)),this._setProp(r,n,!1,!0)}_getProp(e){return this._props[e]}_setProp(e,t,n=!0,r=!1){if(t!==this._props[e]&&(this._dirty=!0,t===DM?delete this._props[e]:(this._props[e]=t,e===`key`&&this._app&&(this._app._ceVNode.key=t)),r&&this._instance&&this._update(),n)){let n=this._ob;n&&(this._processMutations(n.takeRecords()),n.disconnect()),t===!0?this.setAttribute(oj(e),``):typeof t==`string`||typeof t==`number`?this.setAttribute(oj(e),t+``):t||this.removeAttribute(oj(e)),n&&n.observe(this,{attributes:!0})}}_update(){let e=this._createVNode();this._app&&(e.appContext=this._app._context),_N(e,this._root)}_createVNode(){let e={};this.shadowRoot||(e.onVnodeMounted=e.onVnodeUpdated=this._renderSlots.bind(this));let t=Kk(this._def,UA(e,this._props));return this._instance||(t.ce=e=>{this._instance=e,e.ce=this,e.isCE=!0;let t=(e,t)=>{this.dispatchEvent(new CustomEvent(e,tj(t[0])?UA({detail:t},t[0]):{detail:t}))};e.emit=(e,...n)=>{t(e,n),oj(e)!==e&&t(oj(e),n)},this._setParent()}),t}_applyStyles(e,t){if(!e)return;if(t){if(t===this._def||this._styleChildren.has(t))return;this._styleChildren.add(t)}let n=this._nonce;for(let t=e.length-1;t>=0;t--){let r=document.createElement(`style`);n&&r.setAttribute(`nonce`,n),r.textContent=e[t],this.shadowRoot.prepend(r)}}_parseSlots(){let e=this._slots={},t;for(;t=this.firstChild;){let n=t.nodeType===1&&t.getAttribute(`slot`)||`default`;(e[n]||(e[n]=[])).push(t),this.removeChild(t)}}_renderSlots(){let e=this._getSlots(),t=this._instance.type.__scopeId;for(let n=0;n<e.length;n++){let r=e[n],i=r.getAttribute(`name`)||`default`,a=this._slots[i],o=r.parentNode;if(a)for(let e of a){if(t&&e.nodeType===1){let n=t+`-s`,r=document.createTreeWalker(e,1);e.setAttribute(n,``);let i;for(;i=r.nextNode();)i.setAttribute(n,``)}o.insertBefore(e,r)}else for(;r.firstChild;)o.insertBefore(r.firstChild,r);o.removeChild(r)}}_getSlots(){let e=[this];this._teleportTargets&&e.push(...this._teleportTargets);let t=new Set;for(let n of e){let e=n.querySelectorAll(`slot`);for(let n=0;n<e.length;n++)t.add(e[n])}return Array.from(t)}_injectChildStyle(e){this._applyStyles(e.styles,e)}_beginPatch(){this._patching=!0,this._dirty=!1}_endPatch(){this._patching=!1,this._dirty&&this._instance&&this._update()}_removeChildStyle(e){}};function MM(e){let t=cA();return t&&t.ce||null}function NM(){let e=MM();return e&&e.shadowRoot}function PM(e=`$style`){{let t=cA();if(!t)return zA;let n=t.type.__cssModules;return n&&n[e]||zA}}var FM=new WeakMap,IM=new WeakMap,LM=Symbol(`_moveCb`),RM=Symbol(`_enterCb`),zM=(e=>(delete e.props.mode,e))({name:`TransitionGroup`,props:UA({},jj,{tag:String,moveClass:String}),setup(e,{slots:t}){let n=cA(),r=yE(),i,a;return bD(()=>{if(!i.length)return;let t=e.moveClass||`${e.name||`v`}-move`;if(!UM(i[0].el,n.vnode.el,t)){i=[];return}i.forEach(BM),i.forEach(VM);let r=i.filter(HM);Kj(n.vnode.el),r.forEach(e=>{let n=e.el,r=n.style;Rj(n,t),r.transform=r.webkitTransform=r.transitionDuration=``;let i=n[LM]=e=>{e&&e.target!==n||(!e||e.propertyName.endsWith(`transform`))&&(n.removeEventListener(`transitionend`,i),n[LM]=null,zj(n,t))};n.addEventListener(`transitionend`,i)}),i=[]}),()=>{let o=K(e),s=Fj(o),c=o.tag||Dk;if(i=[],a)for(let e=0;e<a.length;e++){let t=a[e];t.el&&t.el instanceof Element&&(i.push(t),AE(t,DE(t,s,r,n)),FM.set(t,{left:t.el.offsetLeft,top:t.el.offsetTop}))}a=t.default?jE(t.default()):[];for(let e=0;e<a.length;e++){let t=a[e];t.key!=null&&AE(t,DE(t,s,r,n))}return Kk(c,null,a)}}});function BM(e){let t=e.el;t[LM]&&t[LM](),t[RM]&&t[RM]()}function VM(e){IM.set(e,{left:e.el.offsetLeft,top:e.el.offsetTop})}function HM(e){let t=FM.get(e),n=IM.get(e),r=t.left-n.left,i=t.top-n.top;if(r||i){let t=e.el.style;return t.transform=t.webkitTransform=`translate(${r}px,${i}px)`,t.transitionDuration=`0s`,e}}function UM(e,t,n){let r=e.cloneNode(),i=e[kj];i&&i.forEach(e=>{e.split(/\s+/).forEach(e=>e&&r.classList.remove(e))}),n.split(/\s+/).forEach(e=>e&&r.classList.add(e)),r.style.display=`none`;let a=t.nodeType===1?t:t.parentNode;a.appendChild(r);let{hasTransform:o}=Uj(r);return a.removeChild(r),o}var WM=e=>{let t=e.props[`onUpdate:modelValue`]||!1;return KA(t)?e=>cj(t,e):t};function GM(e){e.target.composing=!0}function KM(e){let t=e.target;t.composing&&(t.composing=!1,t.dispatchEvent(new Event(`input`)))}var qM=Symbol(`_assign`);function JM(e,t,n){return t&&(e=e.trim()),n&&(e=lj(e)),e}var YM={created(e,{modifiers:{lazy:t,trim:n,number:r}},i){e[qM]=WM(i);let a=r||i.props&&i.props.type===`number`;pM(e,t?`change`:`input`,t=>{t.target.composing||e[qM](JM(e.value,n,a))}),(n||a)&&pM(e,`change`,()=>{e.value=JM(e.value,n,a)}),t||(pM(e,`compositionstart`,GM),pM(e,`compositionend`,KM),pM(e,`change`,KM))},mounted(e,{value:t}){e.value=t??``},beforeUpdate(e,{value:t,oldValue:n,modifiers:{lazy:r,trim:i,number:a}},o){if(e[qM]=WM(o),e.composing)return;let s=(a||e.type===`number`)&&!/^0\d/.test(e.value)?lj(e.value):e.value,c=t??``;s!==c&&(document.activeElement===e&&e.type!==`range`&&(r&&t===n||i&&e.value.trim()===c)||(e.value=c))}},XM={deep:!0,created(e,t,n){e[qM]=WM(n),pM(e,`change`,()=>{let t=e._modelValue,n=tN(e),r=e.checked,i=e[qM];if(KA(t)){let e=gj(t,n),a=e!==-1;if(r&&!a)i(t.concat(n));else if(!r&&a){let n=[...t];n.splice(e,1),i(n)}}else if(qA(t)){let e=new Set(t);r?e.add(n):e.delete(n),i(e)}else i(nN(e,r))})},mounted:ZM,beforeUpdate(e,t,n){e[qM]=WM(n),ZM(e,t,n)}};function ZM(e,{value:t,oldValue:n},r){e._modelValue=t;let i;if(KA(t))i=gj(t,r.props.value)>-1;else if(qA(t))i=t.has(r.props.value);else{if(t===n)return;i=hj(t,nN(e,!0))}e.checked!==i&&(e.checked=i)}var QM={created(e,{value:t},n){e.checked=hj(t,n.props.value),e[qM]=WM(n),pM(e,`change`,()=>{e[qM](tN(e))})},beforeUpdate(e,{value:t,oldValue:n},r){e[qM]=WM(r),t!==n&&(e.checked=hj(t,r.props.value))}},$M={deep:!0,created(e,{value:t,modifiers:{number:n}},r){let i=qA(t);pM(e,`change`,()=>{let t=Array.prototype.filter.call(e.options,e=>e.selected).map(e=>n?lj(tN(e)):tN(e));e[qM](e.multiple?i?new Set(t):t:t[0]),e._assigning=!0,TT(()=>{e._assigning=!1})}),e[qM]=WM(r)},mounted(e,{value:t}){eN(e,t)},beforeUpdate(e,t,n){e[qM]=WM(n)},updated(e,{value:t}){e._assigning||eN(e,t)}};function eN(e,t){let n=e.multiple,r=KA(t);if(!(n&&!r&&!qA(t))){for(let i=0,a=e.options.length;i<a;i++){let a=e.options[i],o=tN(a);if(n)if(r){let e=typeof o;e===`string`||e===`number`?a.selected=t.some(e=>String(e)===String(o)):a.selected=gj(t,o)>-1}else a.selected=t.has(o);else if(hj(tN(a),t)){e.selectedIndex!==i&&(e.selectedIndex=i);return}}!n&&e.selectedIndex!==-1&&(e.selectedIndex=-1)}}function tN(e){return`_value`in e?e._value:e.value}function nN(e,t){let n=t?`_trueValue`:`_falseValue`;return n in e?e[n]:t}var rN={created(e,t,n){aN(e,t,n,null,`created`)},mounted(e,t,n){aN(e,t,n,null,`mounted`)},beforeUpdate(e,t,n,r){aN(e,t,n,r,`beforeUpdate`)},updated(e,t,n,r){aN(e,t,n,r,`updated`)}};function iN(e,t){switch(e){case`SELECT`:return $M;case`TEXTAREA`:return YM;default:switch(t){case`checkbox`:return XM;case`radio`:return QM;default:return YM}}}function aN(e,t,n,r,i){let a=iN(e.tagName,n.props&&n.props.type)[i];a&&a(e,t,n,r)}function oN(){YM.getSSRProps=({value:e})=>({value:e}),QM.getSSRProps=({value:e},t)=>{if(t.props&&hj(t.props.value,e))return{checked:!0}},XM.getSSRProps=({value:e},t)=>{if(KA(e)){if(t.props&&gj(e,t.props.value)>-1)return{checked:!0}}else if(qA(e)){if(t.props&&e.has(t.props.value))return{checked:!0}}else if(e)return{checked:!0}},rN.getSSRProps=(e,t)=>{if(typeof t.type!=`string`)return;let n=iN(t.type.toUpperCase(),t.props&&t.props.type);if(n.getSSRProps)return n.getSSRProps(e,t)}}var sN=[`ctrl`,`shift`,`alt`,`meta`],cN={stop:e=>e.stopPropagation(),prevent:e=>e.preventDefault(),self:e=>e.target!==e.currentTarget,ctrl:e=>!e.ctrlKey,shift:e=>!e.shiftKey,alt:e=>!e.altKey,meta:e=>!e.metaKey,left:e=>`button`in e&&e.button!==0,middle:e=>`button`in e&&e.button!==1,right:e=>`button`in e&&e.button!==2,exact:(e,t)=>sN.some(n=>e[`${n}Key`]&&!t.includes(n))},lN=(e,t)=>{let n=e._withMods||={},r=t.join(`.`);return n[r]||(n[r]=((n,...r)=>{for(let e=0;e<t.length;e++){let r=cN[t[e]];if(r&&r(n,t))return}return e(n,...r)}))},uN={esc:`escape`,space:` `,up:`arrow-up`,left:`arrow-left`,right:`arrow-right`,down:`arrow-down`,delete:`backspace`},dN=(e,t)=>{let n=e._withKeys||={},r=t.join(`.`);return n[r]||(n[r]=(n=>{if(!(`key`in n))return;let r=oj(n.key);if(t.some(e=>e===r||uN[e]===r))return e(n)}))},fN=UA({patchProp:TM},Ej),pN,mN=!1;function hN(){return pN||=rk(fN)}function gN(){return pN=mN?pN:ik(fN),mN=!0,pN}var _N=((...e)=>{hN().render(...e)}),vN=((...e)=>{gN().hydrate(...e)}),yN=((...e)=>{let t=hN().createApp(...e),{mount:n}=t;return t.mount=e=>{let r=SN(e);if(!r)return;let i=t._component;!YA(i)&&!i.render&&!i.template&&(i.template=r.innerHTML),r.nodeType===1&&(r.textContent=``);let a=n(r,!1,xN(r));return r instanceof Element&&(r.removeAttribute(`v-cloak`),r.setAttribute(`data-v-app`,``)),a},t}),bN=((...e)=>{let t=gN().createApp(...e),{mount:n}=t;return t.mount=e=>{let t=SN(e);if(t)return n(t,!0,xN(t))},t});function xN(e){if(e instanceof SVGElement)return`svg`;if(typeof MathMLElement==`function`&&e instanceof MathMLElement)return`mathml`}function SN(e){return XA(e)?document.querySelector(e):e}var CN=!1,wN=()=>{CN||(CN=!0,oN(),Qj())};function TN(e){let t=Object.create(null);for(let n of e.split(`,`))t[n]=1;return e=>e in t}var EN={},DN=()=>{},ON=()=>!1,kN=e=>e.charCodeAt(0)===111&&e.charCodeAt(1)===110&&(e.charCodeAt(2)>122||e.charCodeAt(2)<97),AN=Object.assign,jN=Array.isArray,MN=e=>typeof e==`string`,NN=e=>typeof e==`symbol`,PN=e=>typeof e==`object`&&!!e,FN=TN(`,key,ref,ref_for,ref_key,onVnodeBeforeMount,onVnodeMounted,onVnodeBeforeUpdate,onVnodeUpdated,onVnodeBeforeUnmount,onVnodeUnmounted`),IN=TN(`bind,cloak,else-if,else,for,html,if,model,on,once,pre,show,slot,text,memo`),LN=e=>{let t=Object.create(null);return(n=>t[n]||(t[n]=e(n)))},RN=/-\w/g,zN=LN(e=>e.replace(RN,e=>e.slice(1).toUpperCase())),BN=LN(e=>e.charAt(0).toUpperCase()+e.slice(1)),VN=LN(e=>e?`on${BN(e)}`:``);function HN(e,t){return e+JSON.stringify(t,(e,t)=>typeof t==`function`?t.toString():t)}var UN=/;(?![^(]*\))/g,WN=/:([^]+)/,GN=/\/\*[^]*?\*\//g;function KN(e){let t={};return e.replace(GN,``).split(UN).forEach(e=>{if(e){let n=e.split(WN);n.length>1&&(t[n[0].trim()]=n[1].trim())}}),t}var qN=`html,body,base,head,link,meta,style,title,address,article,aside,footer,header,hgroup,h1,h2,h3,h4,h5,h6,nav,section,div,dd,dl,dt,figcaption,figure,picture,hr,img,li,main,ol,p,pre,ul,a,b,abbr,bdi,bdo,br,cite,code,data,dfn,em,i,kbd,mark,q,rp,rt,ruby,s,samp,small,span,strong,sub,sup,time,u,var,wbr,area,audio,map,track,video,embed,object,param,source,canvas,script,noscript,del,ins,caption,col,colgroup,table,thead,tbody,td,th,tr,button,datalist,fieldset,form,input,label,legend,meter,optgroup,option,output,progress,select,textarea,details,dialog,menu,summary,template,blockquote,iframe,tfoot`,JN=`svg,animate,animateMotion,animateTransform,circle,clipPath,color-profile,defs,desc,discard,ellipse,feBlend,feColorMatrix,feComponentTransfer,feComposite,feConvolveMatrix,feDiffuseLighting,feDisplacementMap,feDistantLight,feDropShadow,feFlood,feFuncA,feFuncB,feFuncG,feFuncR,feGaussianBlur,feImage,feMerge,feMergeNode,feMorphology,feOffset,fePointLight,feSpecularLighting,feSpotLight,feTile,feTurbulence,filter,foreignObject,g,hatch,hatchpath,image,line,linearGradient,marker,mask,mesh,meshgradient,meshpatch,meshrow,metadata,mpath,path,pattern,polygon,polyline,radialGradient,rect,set,solidcolor,stop,switch,symbol,text,textPath,title,tspan,unknown,use,view`,YN=`annotation,annotation-xml,maction,maligngroup,malignmark,math,menclose,merror,mfenced,mfrac,mfraction,mglyph,mi,mlabeledtr,mlongdiv,mmultiscripts,mn,mo,mover,mpadded,mphantom,mprescripts,mroot,mrow,ms,mscarries,mscarry,msgroup,msline,mspace,msqrt,msrow,mstack,mstyle,msub,msubsup,msup,mtable,mtd,mtext,mtr,munder,munderover,none,semantics`,XN=`area,base,br,col,embed,hr,img,input,link,meta,param,source,track,wbr`,ZN=TN(qN),QN=TN(JN),$N=TN(YN),eP=TN(XN),tP=Symbol(``),nP=Symbol(``),rP=Symbol(``),iP=Symbol(``),aP=Symbol(``),oP=Symbol(``),sP=Symbol(``),cP=Symbol(``),lP=Symbol(``),uP=Symbol(``),dP=Symbol(``),fP=Symbol(``),pP=Symbol(``),mP=Symbol(``),hP=Symbol(``),gP=Symbol(``),_P=Symbol(``),vP=Symbol(``),yP=Symbol(``),bP=Symbol(``),xP=Symbol(``),SP=Symbol(``),CP=Symbol(``),wP=Symbol(``),TP=Symbol(``),EP=Symbol(``),DP=Symbol(``),OP=Symbol(``),kP=Symbol(``),AP=Symbol(``),jP=Symbol(``),MP=Symbol(``),NP=Symbol(``),PP=Symbol(``),FP=Symbol(``),IP=Symbol(``),LP=Symbol(``),RP=Symbol(``),zP=Symbol(``),BP={[tP]:`Fragment`,[nP]:`Teleport`,[rP]:`Suspense`,[iP]:`KeepAlive`,[aP]:`BaseTransition`,[oP]:`openBlock`,[sP]:`createBlock`,[cP]:`createElementBlock`,[lP]:`createVNode`,[uP]:`createElementVNode`,[dP]:`createCommentVNode`,[fP]:`createTextVNode`,[pP]:`createStaticVNode`,[mP]:`resolveComponent`,[hP]:`resolveDynamicComponent`,[gP]:`resolveDirective`,[_P]:`resolveFilter`,[vP]:`withDirectives`,[yP]:`renderList`,[bP]:`renderSlot`,[xP]:`createSlots`,[SP]:`toDisplayString`,[CP]:`mergeProps`,[wP]:`normalizeClass`,[TP]:`normalizeStyle`,[EP]:`normalizeProps`,[DP]:`guardReactiveProps`,[OP]:`toHandlers`,[kP]:`camelize`,[AP]:`capitalize`,[jP]:`toHandlerKey`,[MP]:`setBlockTracking`,[NP]:`pushScopeId`,[PP]:`popScopeId`,[FP]:`withCtx`,[IP]:`unref`,[LP]:`isRef`,[RP]:`withMemo`,[zP]:`isMemoSame`};function VP(e){Object.getOwnPropertySymbols(e).forEach(t=>{BP[t]=e[t]})}var HP={start:{line:1,column:1,offset:0},end:{line:1,column:1,offset:0},source:``};function UP(e,t=``){return{type:0,source:t,children:e,helpers:new Set,components:[],directives:[],hoists:[],imports:[],cached:[],temps:0,codegenNode:void 0,loc:HP}}function WP(e,t,n,r,i,a,o,s=!1,c=!1,l=!1,u=HP){return e&&(s?(e.helper(oP),e.helper(tF(e.inSSR,l))):e.helper(eF(e.inSSR,l)),o&&e.helper(vP)),{type:13,tag:t,props:n,children:r,patchFlag:i,dynamicProps:a,directives:o,isBlock:s,disableTracking:c,isComponent:l,loc:u}}function GP(e,t=HP){return{type:17,loc:t,elements:e}}function KP(e,t=HP){return{type:15,loc:t,properties:e}}function qP(e,t){return{type:16,loc:HP,key:MN(e)?Z(e,!0):e,value:t}}function Z(e,t=!1,n=HP,r=0){return{type:4,loc:n,content:e,isStatic:t,constType:t?3:r}}function JP(e,t=HP){return{type:8,loc:t,children:e}}function YP(e,t=[],n=HP){return{type:14,loc:n,callee:e,arguments:t}}function XP(e,t=void 0,n=!1,r=!1,i=HP){return{type:18,params:e,returns:t,newline:n,isSlot:r,loc:i}}function ZP(e,t,n,r=!0){return{type:19,test:e,consequent:t,alternate:n,newline:r,loc:HP}}function QP(e,t,n=!1,r=!1){return{type:20,index:e,value:t,needPauseTracking:n,inVOnce:r,needArraySpread:!1,loc:HP}}function $P(e){return{type:21,body:e,loc:HP}}function eF(e,t){return e||t?lP:uP}function tF(e,t){return e||t?sP:cP}function nF(e,{helper:t,removeHelper:n,inSSR:r}){e.isBlock||(e.isBlock=!0,n(eF(r,e.isComponent)),t(oP),t(tF(r,e.isComponent)))}var rF=new Uint8Array([123,123]),iF=new Uint8Array([125,125]);function aF(e){return e>=97&&e<=122||e>=65&&e<=90}function oF(e){return e===32||e===10||e===9||e===12||e===13}function sF(e){return e===47||e===62||oF(e)}function cF(e){let t=new Uint8Array(e.length);for(let n=0;n<e.length;n++)t[n]=e.charCodeAt(n);return t}var lF={Cdata:new Uint8Array([67,68,65,84,65,91]),CdataEnd:new Uint8Array([93,93,62]),CommentEnd:new Uint8Array([45,45,62]),ScriptEnd:new Uint8Array([60,47,115,99,114,105,112,116]),StyleEnd:new Uint8Array([60,47,115,116,121,108,101]),TitleEnd:new Uint8Array([60,47,116,105,116,108,101]),TextareaEnd:new Uint8Array([60,47,116,101,120,116,97,114,101,97])},uF=class{constructor(e,t){this.stack=e,this.cbs=t,this.state=1,this.buffer=``,this.sectionStart=0,this.index=0,this.entityStart=0,this.baseState=1,this.inRCDATA=!1,this.inXML=!1,this.inVPre=!1,this.newlines=[],this.mode=0,this.delimiterOpen=rF,this.delimiterClose=iF,this.delimiterIndex=-1,this.currentSequence=void 0,this.sequenceIndex=0}get inSFCRoot(){return this.mode===2&&this.stack.length===0}reset(){this.state=1,this.mode=0,this.buffer=``,this.sectionStart=0,this.index=0,this.baseState=1,this.inRCDATA=!1,this.currentSequence=void 0,this.newlines.length=0,this.delimiterOpen=rF,this.delimiterClose=iF}getPos(e){let t=1,n=e+1,r=this.newlines.length,i=-1;if(r>100){let t=-1,n=r;for(;t+1<n;){let r=t+n>>>1;this.newlines[r]<e?t=r:n=r}i=t}else for(let t=r-1;t>=0;t--)if(e>this.newlines[t]){i=t;break}return i>=0&&(t=i+2,n=e-this.newlines[i]),{column:n,line:t,offset:e}}peek(){return this.buffer.charCodeAt(this.index+1)}stateText(e){e===60?(this.index>this.sectionStart&&this.cbs.ontext(this.sectionStart,this.index),this.state=5,this.sectionStart=this.index):!this.inVPre&&e===this.delimiterOpen[0]&&(this.state=2,this.delimiterIndex=0,this.stateInterpolationOpen(e))}stateInterpolationOpen(e){if(e===this.delimiterOpen[this.delimiterIndex])if(this.delimiterIndex===this.delimiterOpen.length-1){let e=this.index+1-this.delimiterOpen.length;e>this.sectionStart&&this.cbs.ontext(this.sectionStart,e),this.state=3,this.sectionStart=e}else this.delimiterIndex++;else this.inRCDATA?(this.state=32,this.stateInRCDATA(e)):(this.state=1,this.stateText(e))}stateInterpolation(e){e===this.delimiterClose[0]&&(this.state=4,this.delimiterIndex=0,this.stateInterpolationClose(e))}stateInterpolationClose(e){e===this.delimiterClose[this.delimiterIndex]?this.delimiterIndex===this.delimiterClose.length-1?(this.cbs.oninterpolation(this.sectionStart,this.index+1),this.inRCDATA?this.state=32:this.state=1,this.sectionStart=this.index+1):this.delimiterIndex++:(this.state=3,this.stateInterpolation(e))}stateSpecialStartSequence(e){let t=this.sequenceIndex===this.currentSequence.length;if(!(t?sF(e):(e|32)===this.currentSequence[this.sequenceIndex]))this.inRCDATA=!1;else if(!t){this.sequenceIndex++;return}this.sequenceIndex=0,this.state=6,this.stateInTagName(e)}stateInRCDATA(e){if(this.sequenceIndex===this.currentSequence.length){if(e===62||oF(e)){let t=this.index-this.currentSequence.length;if(this.sectionStart<t){let e=this.index;this.index=t,this.cbs.ontext(this.sectionStart,t),this.index=e}this.sectionStart=t+2,this.stateInClosingTagName(e),this.inRCDATA=!1;return}this.sequenceIndex=0}(e|32)===this.currentSequence[this.sequenceIndex]?this.sequenceIndex+=1:this.sequenceIndex===0?this.currentSequence===lF.TitleEnd||this.currentSequence===lF.TextareaEnd&&!this.inSFCRoot?!this.inVPre&&e===this.delimiterOpen[0]&&(this.state=2,this.delimiterIndex=0,this.stateInterpolationOpen(e)):this.fastForwardTo(60)&&(this.sequenceIndex=1):this.sequenceIndex=Number(e===60)}stateCDATASequence(e){e===lF.Cdata[this.sequenceIndex]?++this.sequenceIndex===lF.Cdata.length&&(this.state=28,this.currentSequence=lF.CdataEnd,this.sequenceIndex=0,this.sectionStart=this.index+1):(this.sequenceIndex=0,this.state=23,this.stateInDeclaration(e))}fastForwardTo(e){for(;++this.index<this.buffer.length;){let t=this.buffer.charCodeAt(this.index);if(t===10&&this.newlines.push(this.index),t===e)return!0}return this.index=this.buffer.length-1,!1}stateInCommentLike(e){e===this.currentSequence[this.sequenceIndex]?++this.sequenceIndex===this.currentSequence.length&&(this.currentSequence===lF.CdataEnd?this.cbs.oncdata(this.sectionStart,this.index-2):this.cbs.oncomment(this.sectionStart,this.index-2),this.sequenceIndex=0,this.sectionStart=this.index+1,this.state=1):this.sequenceIndex===0?this.fastForwardTo(this.currentSequence[0])&&(this.sequenceIndex=1):e!==this.currentSequence[this.sequenceIndex-1]&&(this.sequenceIndex=0)}startSpecial(e,t){this.enterRCDATA(e,t),this.state=31}enterRCDATA(e,t){this.inRCDATA=!0,this.currentSequence=e,this.sequenceIndex=t}stateBeforeTagName(e){e===33?(this.state=22,this.sectionStart=this.index+1):e===63?(this.state=24,this.sectionStart=this.index+1):aF(e)?(this.sectionStart=this.index,this.mode===0?this.state=6:this.inSFCRoot?this.state=34:this.inXML?this.state=6:e===116?this.state=30:this.state=e===115?29:6):e===47?this.state=8:(this.state=1,this.stateText(e))}stateInTagName(e){sF(e)&&this.handleTagName(e)}stateInSFCRootTagName(e){if(sF(e)){let t=this.buffer.slice(this.sectionStart,this.index);t!==`template`&&this.enterRCDATA(cF(`</`+t),0),this.handleTagName(e)}}handleTagName(e){this.cbs.onopentagname(this.sectionStart,this.index),this.sectionStart=-1,this.state=11,this.stateBeforeAttrName(e)}stateBeforeClosingTagName(e){oF(e)||(e===62?(this.state=1,this.sectionStart=this.index+1):(this.state=aF(e)?9:27,this.sectionStart=this.index))}stateInClosingTagName(e){(e===62||oF(e))&&(this.cbs.onclosetag(this.sectionStart,this.index),this.sectionStart=-1,this.state=10,this.stateAfterClosingTagName(e))}stateAfterClosingTagName(e){e===62&&(this.state=1,this.sectionStart=this.index+1)}stateBeforeAttrName(e){e===62?(this.cbs.onopentagend(this.index),this.inRCDATA?this.state=32:this.state=1,this.sectionStart=this.index+1):e===47?this.state=7:e===60&&this.peek()===47?(this.cbs.onopentagend(this.index),this.state=5,this.sectionStart=this.index):oF(e)||this.handleAttrStart(e)}handleAttrStart(e){e===118&&this.peek()===45?(this.state=13,this.sectionStart=this.index):e===46||e===58||e===64||e===35?(this.cbs.ondirname(this.index,this.index+1),this.state=14,this.sectionStart=this.index+1):(this.state=12,this.sectionStart=this.index)}stateInSelfClosingTag(e){e===62?(this.cbs.onselfclosingtag(this.index),this.state=1,this.sectionStart=this.index+1,this.inRCDATA=!1):oF(e)||(this.state=11,this.stateBeforeAttrName(e))}stateInAttrName(e){(e===61||sF(e))&&(this.cbs.onattribname(this.sectionStart,this.index),this.handleAttrNameEnd(e))}stateInDirName(e){e===61||sF(e)?(this.cbs.ondirname(this.sectionStart,this.index),this.handleAttrNameEnd(e)):e===58?(this.cbs.ondirname(this.sectionStart,this.index),this.state=14,this.sectionStart=this.index+1):e===46&&(this.cbs.ondirname(this.sectionStart,this.index),this.state=16,this.sectionStart=this.index+1)}stateInDirArg(e){e===61||sF(e)?(this.cbs.ondirarg(this.sectionStart,this.index),this.handleAttrNameEnd(e)):e===91?this.state=15:e===46&&(this.cbs.ondirarg(this.sectionStart,this.index),this.state=16,this.sectionStart=this.index+1)}stateInDynamicDirArg(e){e===93?this.state=14:(e===61||sF(e))&&(this.cbs.ondirarg(this.sectionStart,this.index+1),this.handleAttrNameEnd(e))}stateInDirModifier(e){e===61||sF(e)?(this.cbs.ondirmodifier(this.sectionStart,this.index),this.handleAttrNameEnd(e)):e===46&&(this.cbs.ondirmodifier(this.sectionStart,this.index),this.sectionStart=this.index+1)}handleAttrNameEnd(e){this.sectionStart=this.index,this.state=17,this.cbs.onattribnameend(this.index),this.stateAfterAttrName(e)}stateAfterAttrName(e){e===61?this.state=18:e===47||e===62?(this.cbs.onattribend(0,this.sectionStart),this.sectionStart=-1,this.state=11,this.stateBeforeAttrName(e)):oF(e)||(this.cbs.onattribend(0,this.sectionStart),this.handleAttrStart(e))}stateBeforeAttrValue(e){e===34?(this.state=19,this.sectionStart=this.index+1):e===39?(this.state=20,this.sectionStart=this.index+1):oF(e)||(this.sectionStart=this.index,this.state=21,this.stateInAttrValueNoQuotes(e))}handleInAttrValue(e,t){(e===t||this.fastForwardTo(t))&&(this.cbs.onattribdata(this.sectionStart,this.index),this.sectionStart=-1,this.cbs.onattribend(t===34?3:2,this.index+1),this.state=11)}stateInAttrValueDoubleQuotes(e){this.handleInAttrValue(e,34)}stateInAttrValueSingleQuotes(e){this.handleInAttrValue(e,39)}stateInAttrValueNoQuotes(e){oF(e)||e===62?(this.cbs.onattribdata(this.sectionStart,this.index),this.sectionStart=-1,this.cbs.onattribend(1,this.index),this.state=11,this.stateBeforeAttrName(e)):(e===39||e===60||e===61||e===96)&&this.cbs.onerr(18,this.index)}stateBeforeDeclaration(e){e===91?(this.state=26,this.sequenceIndex=0):this.state=e===45?25:23}stateInDeclaration(e){(e===62||this.fastForwardTo(62))&&(this.state=1,this.sectionStart=this.index+1)}stateInProcessingInstruction(e){(e===62||this.fastForwardTo(62))&&(this.cbs.onprocessinginstruction(this.sectionStart,this.index),this.state=1,this.sectionStart=this.index+1)}stateBeforeComment(e){e===45?(this.state=28,this.currentSequence=lF.CommentEnd,this.sequenceIndex=2,this.sectionStart=this.index+1):this.state=23}stateInSpecialComment(e){(e===62||this.fastForwardTo(62))&&(this.cbs.oncomment(this.sectionStart,this.index),this.state=1,this.sectionStart=this.index+1)}stateBeforeSpecialS(e){e===lF.ScriptEnd[3]?this.startSpecial(lF.ScriptEnd,4):e===lF.StyleEnd[3]?this.startSpecial(lF.StyleEnd,4):(this.state=6,this.stateInTagName(e))}stateBeforeSpecialT(e){e===lF.TitleEnd[3]?this.startSpecial(lF.TitleEnd,4):e===lF.TextareaEnd[3]?this.startSpecial(lF.TextareaEnd,4):(this.state=6,this.stateInTagName(e))}startEntity(){}stateInEntity(){}parse(e){for(this.buffer=e;this.index<this.buffer.length;){let e=this.buffer.charCodeAt(this.index);switch(e===10&&this.state!==33&&this.newlines.push(this.index),this.state){case 1:this.stateText(e);break;case 2:this.stateInterpolationOpen(e);break;case 3:this.stateInterpolation(e);break;case 4:this.stateInterpolationClose(e);break;case 31:this.stateSpecialStartSequence(e);break;case 32:this.stateInRCDATA(e);break;case 26:this.stateCDATASequence(e);break;case 19:this.stateInAttrValueDoubleQuotes(e);break;case 12:this.stateInAttrName(e);break;case 13:this.stateInDirName(e);break;case 14:this.stateInDirArg(e);break;case 15:this.stateInDynamicDirArg(e);break;case 16:this.stateInDirModifier(e);break;case 28:this.stateInCommentLike(e);break;case 27:this.stateInSpecialComment(e);break;case 11:this.stateBeforeAttrName(e);break;case 6:this.stateInTagName(e);break;case 34:this.stateInSFCRootTagName(e);break;case 9:this.stateInClosingTagName(e);break;case 5:this.stateBeforeTagName(e);break;case 17:this.stateAfterAttrName(e);break;case 20:this.stateInAttrValueSingleQuotes(e);break;case 18:this.stateBeforeAttrValue(e);break;case 8:this.stateBeforeClosingTagName(e);break;case 10:this.stateAfterClosingTagName(e);break;case 29:this.stateBeforeSpecialS(e);break;case 30:this.stateBeforeSpecialT(e);break;case 21:this.stateInAttrValueNoQuotes(e);break;case 7:this.stateInSelfClosingTag(e);break;case 23:this.stateInDeclaration(e);break;case 22:this.stateBeforeDeclaration(e);break;case 25:this.stateBeforeComment(e);break;case 24:this.stateInProcessingInstruction(e);break;case 33:this.stateInEntity();break}this.index++}this.cleanup(),this.finish()}cleanup(){this.sectionStart!==this.index&&(this.state===1||this.state===32&&this.sequenceIndex===0?(this.cbs.ontext(this.sectionStart,this.index),this.sectionStart=this.index):(this.state===19||this.state===20||this.state===21)&&(this.cbs.onattribdata(this.sectionStart,this.index),this.sectionStart=this.index))}finish(){this.handleTrailingData(),this.cbs.onend()}handleTrailingData(){let e=this.buffer.length;this.sectionStart>=e||(this.state===28?this.currentSequence===lF.CdataEnd?this.cbs.oncdata(this.sectionStart,e):this.cbs.oncomment(this.sectionStart,e):this.state===6||this.state===11||this.state===18||this.state===17||this.state===12||this.state===13||this.state===14||this.state===15||this.state===16||this.state===20||this.state===19||this.state===21||this.state===9||this.cbs.ontext(this.sectionStart,e))}emitCodePoint(e,t){}};function dF(e,{compatConfig:t}){let n=t&&t[e];return e===`MODE`?n||3:n}function fF(e,t){let n=dF(`MODE`,t),r=dF(e,t);return n===3?r===!0:r!==!1}function pF(e,t,n,...r){return fF(e,t)}function mF(e){throw e}function hF(e){}function gF(e,t,n,r){let i=`https://vuejs.org/error-reference/#compiler-${e}`,a=SyntaxError(String(i));return a.code=e,a.loc=t,a}var _F=e=>e.type===4&&e.isStatic;function vF(e){switch(e){case`Teleport`:case`teleport`:return nP;case`Suspense`:case`suspense`:return rP;case`KeepAlive`:case`keep-alive`:return iP;case`BaseTransition`:case`base-transition`:return aP}}var yF=/^$|^\d|[^\$\w\xA0-\uFFFF]/,bF=e=>!yF.test(e),xF=/[A-Za-z_$\xA0-\uFFFF]/,SF=/[\.\?\w$\xA0-\uFFFF]/,CF=/\s+[.[]\s*|\s*[.[]\s+/g,wF=e=>e.type===4?e.content:e.loc.source,TF=e=>{let t=wF(e).trim().replace(CF,e=>e.trim()),n=0,r=[],i=0,a=0,o=null;for(let e=0;e<t.length;e++){let s=t.charAt(e);switch(n){case 0:if(s===`[`)r.push(n),n=1,i++;else if(s===`(`)r.push(n),n=2,a++;else if(!(e===0?xF:SF).test(s))return!1;break;case 1:s===`'`||s===`"`||s==="`"?(r.push(n),n=3,o=s):s===`[`?i++:s===`]`&&(--i||(n=r.pop()));break;case 2:if(s===`'`||s===`"`||s==="`")r.push(n),n=3,o=s;else if(s===`(`)a++;else if(s===`)`){if(e===t.length-1)return!1;--a||(n=r.pop())}break;case 3:s===o&&(n=r.pop(),o=null);break}}return!i&&!a},EF=/^\s*(?:async\s*)?(?:\([^)]*?\)|[\w$_]+)\s*(?::[^=]+)?=>|^\s*(?:async\s+)?function(?:\s+[\w$]+)?\s*\(/,DF=e=>EF.test(wF(e));function OF(e,t,n=!1){for(let r=0;r<e.props.length;r++){let i=e.props[r];if(i.type===7&&(n||i.exp)&&(MN(t)?i.name===t:t.test(i.name)))return i}}function kF(e,t,n=!1,r=!1){for(let i=0;i<e.props.length;i++){let a=e.props[i];if(a.type===6){if(n)continue;if(a.name===t&&(a.value||r))return a}else if(a.name===`bind`&&(a.exp||r)&&AF(a.arg,t))return a}}function AF(e,t){return!!(e&&_F(e)&&e.content===t)}function jF(e){return e.props.some(e=>e.type===7&&e.name===`bind`&&(!e.arg||e.arg.type!==4||!e.arg.isStatic))}function MF(e){return e.type===5||e.type===2}function NF(e){return e.type===7&&e.name===`pre`}function PF(e){return e.type===7&&e.name===`slot`}function FF(e){return e.type===1&&e.tagType===3}function IF(e){return e.type===1&&e.tagType===2}var LF=new Set([EP,DP]);function RF(e,t=[]){if(e&&!MN(e)&&e.type===14){let n=e.callee;if(!MN(n)&&LF.has(n))return RF(e.arguments[0],t.concat(e))}return[e,t]}function zF(e,t,n){let r,i=e.type===13?e.props:e.arguments[2],a=[],o;if(i&&!MN(i)&&i.type===14){let e=RF(i);i=e[0],a=e[1],o=a[a.length-1]}if(i==null||MN(i))r=KP([t]);else if(i.type===14){let e=i.arguments[0];!MN(e)&&e.type===15?BF(t,e)||e.properties.unshift(t):i.callee===OP?r=YP(n.helper(CP),[KP([t]),i]):i.arguments.unshift(KP([t])),!r&&(r=i)}else i.type===15?(BF(t,i)||i.properties.unshift(t),r=i):(r=YP(n.helper(CP),[KP([t]),i]),o&&o.callee===DP&&(o=a[a.length-2]));e.type===13?o?o.arguments[0]=r:e.props=r:o?o.arguments[0]=r:e.arguments[2]=r}function BF(e,t){let n=!1;if(e.key.type===4){let r=e.key.content;n=t.properties.some(e=>e.key.type===4&&e.key.content===r)}return n}function VF(e,t){return`_${t}_${e.replace(/[^\w]/g,(t,n)=>t===`-`?`_`:e.charCodeAt(n).toString())}`}function HF(e){return e.type===14&&e.callee===RP?e.arguments[1].returns:e}var UF=/([\s\S]*?)\s+(?:in|of)\s+(\S[\s\S]*)/;function WF(e){for(let t=0;t<e.length;t++)if(!oF(e.charCodeAt(t)))return!1;return!0}function GF(e){return e.type===2&&WF(e.content)||e.type===12&&GF(e.content)}function KF(e){return e.type===3||GF(e)}var qF={parseMode:`base`,ns:0,delimiters:[`{{`,`}}`],getNamespace:()=>0,isVoidTag:ON,isPreTag:ON,isIgnoreNewlineTag:ON,isCustomElement:ON,onError:mF,onWarn:hF,comments:!1,prefixIdentifiers:!1},Q=qF,JF=null,YF=``,XF=null,$=null,ZF=``,QF=-1,$F=-1,eI=0,tI=!1,nI=null,rI=[],iI=new uF(rI,{onerr:OI,ontext(e,t){uI(cI(e,t),e,t)},ontextentity(e,t,n){uI(e,t,n)},oninterpolation(e,t){if(tI)return uI(cI(e,t),e,t);let n=e+iI.delimiterOpen.length,r=t-iI.delimiterClose.length;for(;oF(YF.charCodeAt(n));)n++;for(;oF(YF.charCodeAt(r-1));)r--;let i=cI(n,r);i.includes(`&`)&&(i=Q.decodeEntities(i,!1)),SI({type:5,content:DI(i,!1,CI(n,r)),loc:CI(e,t)})},onopentagname(e,t){let n=cI(e,t);XF={type:1,tag:n,ns:Q.getNamespace(n,rI[0],Q.ns),tagType:0,props:[],children:[],loc:CI(e-1,t),codegenNode:void 0}},onopentagend(e){lI(e)},onclosetag(e,t){let n=cI(e,t);if(!Q.isVoidTag(n)){let r=!1;for(let e=0;e<rI.length;e++)if(rI[e].tag.toLowerCase()===n.toLowerCase()){r=!0,e>0&&OI(24,rI[0].loc.start.offset);for(let n=0;n<=e;n++)dI(rI.shift(),t,n<e);break}r||OI(23,pI(e,60))}},onselfclosingtag(e){let t=XF.tag;XF.isSelfClosing=!0,lI(e),rI[0]&&rI[0].tag===t&&dI(rI.shift(),e)},onattribname(e,t){$={type:6,name:cI(e,t),nameLoc:CI(e,t),value:void 0,loc:CI(e)}},ondirname(e,t){let n=cI(e,t),r=n===`.`||n===`:`?`bind`:n===`@`?`on`:n===`#`?`slot`:n.slice(2);if(!tI&&r===``&&OI(26,e),tI||r===``)$={type:6,name:n,nameLoc:CI(e,t),value:void 0,loc:CI(e)};else if($={type:7,name:r,rawName:n,exp:void 0,arg:void 0,modifiers:n===`.`?[Z(`prop`)]:[],loc:CI(e)},r===`pre`){tI=iI.inVPre=!0,nI=XF;let e=XF.props;for(let t=0;t<e.length;t++)e[t].type===7&&(e[t]=EI(e[t]))}},ondirarg(e,t){if(e===t)return;let n=cI(e,t);if(tI&&!NF($))$.name+=n,TI($.nameLoc,t);else{let r=n[0]!==`[`;$.arg=DI(r?n:n.slice(1,-1),r,CI(e,t),r?3:0)}},ondirmodifier(e,t){let n=cI(e,t);if(tI&&!NF($))$.name+=`.`+n,TI($.nameLoc,t);else if($.name===`slot`){let e=$.arg;e&&(e.content+=`.`+n,TI(e.loc,t))}else{let r=Z(n,!0,CI(e,t));$.modifiers.push(r)}},onattribdata(e,t){ZF+=cI(e,t),QF<0&&(QF=e),$F=t},onattribentity(e,t,n){ZF+=e,QF<0&&(QF=t),$F=n},onattribnameend(e){let t=$.loc.start.offset,n=cI(t,e);$.type===7&&($.rawName=n),XF.props.some(e=>(e.type===7?e.rawName:e.name)===n)&&OI(2,t)},onattribend(e,t){if(XF&&$){if(TI($.loc,t),e!==0)if(ZF.includes(`&`)&&(ZF=Q.decodeEntities(ZF,!0)),$.type===6)$.name===`class`&&(ZF=xI(ZF).trim()),e===1&&!ZF&&OI(13,t),$.value={type:2,content:ZF,loc:e===1?CI(QF,$F):CI(QF-1,$F+1)},iI.inSFCRoot&&XF.tag===`template`&&$.name===`lang`&&ZF&&ZF!==`html`&&iI.enterRCDATA(cF(`</template`),0);else{$.exp=DI(ZF,!1,CI(QF,$F),0,0),$.name===`for`&&($.forParseResult=sI($.exp));let e=-1;$.name===`bind`&&(e=$.modifiers.findIndex(e=>e.content===`sync`))>-1&&pF(`COMPILER_V_BIND_SYNC`,Q,$.loc,$.arg.loc.source)&&($.name=`model`,$.modifiers.splice(e,1))}($.type!==7||$.name!==`pre`)&&XF.props.push($)}ZF=``,QF=$F=-1},oncomment(e,t){Q.comments&&SI({type:3,content:cI(e,t),loc:CI(e-4,t+3)})},onend(){let e=YF.length;for(let t=0;t<rI.length;t++)dI(rI[t],e-1),OI(24,rI[t].loc.start.offset)},oncdata(e,t){rI[0].ns===0?OI(1,e-9):uI(cI(e,t),e,t)},onprocessinginstruction(e){(rI[0]?rI[0].ns:Q.ns)===0&&OI(21,e-1)}}),aI=/,([^,\}\]]*)(?:,([^,\}\]]*))?$/,oI=/^\(|\)$/g;function sI(e){let t=e.loc,n=e.content,r=n.match(UF);if(!r)return;let[,i,a]=r,o=(e,n,r=!1)=>{let i=t.start.offset+n;return DI(e,!1,CI(i,i+e.length),0,r?1:0)},s={source:o(a.trim(),n.indexOf(a,i.length)),value:void 0,key:void 0,index:void 0,finalized:!1},c=i.trim().replace(oI,``).trim(),l=i.indexOf(c),u=c.match(aI);if(u){c=c.replace(aI,``).trim();let e=u[1].trim(),t;if(e&&(t=n.indexOf(e,l+c.length),s.key=o(e,t,!0)),u[2]){let r=u[2].trim();r&&(s.index=o(r,n.indexOf(r,s.key?t+e.length:l+c.length),!0))}}return c&&(s.value=o(c,l,!0)),s}function cI(e,t){return YF.slice(e,t)}function lI(e){iI.inSFCRoot&&(XF.innerLoc=CI(e+1,e+1)),SI(XF);let{tag:t,ns:n}=XF;n===0&&Q.isPreTag(t)&&eI++,Q.isVoidTag(t)?dI(XF,e):(rI.unshift(XF),(n===1||n===2)&&(iI.inXML=!0)),XF=null}function uI(e,t,n){{let t=rI[0]&&rI[0].tag;t!==`script`&&t!==`style`&&e.includes(`&`)&&(e=Q.decodeEntities(e,!1))}let r=rI[0]||JF,i=r.children[r.children.length-1];i&&i.type===2?(i.content+=e,TI(i.loc,n)):r.children.push({type:2,content:e,loc:CI(t,n)})}function dI(e,t,n=!1){n?TI(e.loc,pI(t,60)):TI(e.loc,fI(t,62)+1),iI.inSFCRoot&&(e.children.length?e.innerLoc.end=AN({},e.children[e.children.length-1].loc.end):e.innerLoc.end=AN({},e.innerLoc.start),e.innerLoc.source=cI(e.innerLoc.start.offset,e.innerLoc.end.offset));let{tag:r,ns:i,children:a}=e;if(tI||(r===`slot`?e.tagType=2:hI(e)?e.tagType=3:gI(e)&&(e.tagType=1)),iI.inRCDATA||(e.children=yI(a)),i===0&&Q.isIgnoreNewlineTag(r)){let e=a[0];e&&e.type===2&&(e.content=e.content.replace(/^\r?\n/,``))}i===0&&Q.isPreTag(r)&&eI--,nI===e&&(tI=iI.inVPre=!1,nI=null),iI.inXML&&(rI[0]?rI[0].ns:Q.ns)===0&&(iI.inXML=!1);{let t=e.props;if(!iI.inSFCRoot&&fF(`COMPILER_NATIVE_TEMPLATE`,Q)&&e.tag===`template`&&!hI(e)){let t=rI[0]||JF,n=t.children.indexOf(e);t.children.splice(n,1,...e.children)}let n=t.find(e=>e.type===6&&e.name===`inline-template`);n&&pF(`COMPILER_INLINE_TEMPLATE`,Q,n.loc)&&e.children.length&&(n.value={type:2,content:cI(e.children[0].loc.start.offset,e.children[e.children.length-1].loc.end.offset),loc:n.loc})}}function fI(e,t){let n=e;for(;YF.charCodeAt(n)!==t&&n<YF.length-1;)n++;return n}function pI(e,t){let n=e;for(;YF.charCodeAt(n)!==t&&n>=0;)n--;return n}var mI=new Set([`if`,`else`,`else-if`,`for`,`slot`]);function hI({tag:e,props:t}){if(e===`template`){for(let e=0;e<t.length;e++)if(t[e].type===7&&mI.has(t[e].name))return!0}return!1}function gI({tag:e,props:t}){if(Q.isCustomElement(e))return!1;if(e===`component`||_I(e.charCodeAt(0))||vF(e)||Q.isBuiltInComponent&&Q.isBuiltInComponent(e)||Q.isNativeTag&&!Q.isNativeTag(e))return!0;for(let e=0;e<t.length;e++){let n=t[e];if(n.type===6){if(n.name===`is`&&n.value&&(n.value.content.startsWith(`vue:`)||pF(`COMPILER_IS_ON_ELEMENT`,Q,n.loc)))return!0}else if(n.name===`bind`&&AF(n.arg,`is`)&&pF(`COMPILER_IS_ON_ELEMENT`,Q,n.loc))return!0}return!1}function _I(e){return e>64&&e<91}var vI=/\r\n/g;function yI(e){let t=Q.whitespace!==`preserve`,n=!1;for(let r=0;r<e.length;r++){let i=e[r];if(i.type===2)if(eI)i.content=i.content.replace(vI,`
`);else if(WF(i.content)){let a=e[r-1]&&e[r-1].type,o=e[r+1]&&e[r+1].type;!a||!o||t&&(a===3&&(o===3||o===1)||a===1&&(o===3||o===1&&bI(i.content)))?(n=!0,e[r]=null):i.content=` `}else t&&(i.content=xI(i.content))}return n?e.filter(Boolean):e}function bI(e){for(let t=0;t<e.length;t++){let n=e.charCodeAt(t);if(n===10||n===13)return!0}return!1}function xI(e){let t=``,n=!1;for(let r=0;r<e.length;r++)oF(e.charCodeAt(r))?n||=(t+=` `,!0):(t+=e[r],n=!1);return t}function SI(e){(rI[0]||JF).children.push(e)}function CI(e,t){return{start:iI.getPos(e),end:t==null?t:iI.getPos(t),source:t==null?t:cI(e,t)}}function wI(e){return CI(e.start.offset,e.end.offset)}function TI(e,t){e.end=iI.getPos(t),e.source=cI(e.start.offset,t)}function EI(e){let t={type:6,name:e.rawName,nameLoc:CI(e.loc.start.offset,e.loc.start.offset+e.rawName.length),value:void 0,loc:e.loc};if(e.exp){let n=e.exp.loc;n.end.offset<e.loc.end.offset&&(n.start.offset--,n.start.column--,n.end.offset++,n.end.column++),t.value={type:2,content:e.exp.content,loc:n}}return t}function DI(e,t=!1,n,r=0,i=0){return Z(e,t,n,r)}function OI(e,t,n){Q.onError(gF(e,CI(t,t),void 0,n))}function kI(){iI.reset(),XF=null,$=null,ZF=``,QF=-1,$F=-1,rI.length=0}function AI(e,t){if(kI(),YF=e,Q=AN({},qF),t){let e;for(e in t)t[e]!=null&&(Q[e]=t[e])}iI.mode=Q.parseMode===`html`?1:Q.parseMode===`sfc`?2:0,iI.inXML=Q.ns===1||Q.ns===2;let n=t&&t.delimiters;n&&(iI.delimiterOpen=cF(n[0]),iI.delimiterClose=cF(n[1]));let r=JF=UP([],e);return iI.parse(YF),r.loc=CI(0,e.length),r.children=yI(r.children),JF=null,r}function jI(e,t){NI(e,void 0,t,!!MI(e))}function MI(e){let t=e.children.filter(e=>e.type!==3);return t.length===1&&t[0].type===1&&!IF(t[0])?t[0]:null}function NI(e,t,n,r=!1,i=!1){let{children:a}=e,o=[];for(let t=0;t<a.length;t++){let s=a[t];if(s.type===1&&s.tagType===0){let e=r?0:PI(s,n);if(e>0){if(e>=2){s.codegenNode.patchFlag=-1,o.push(s);continue}}else{let e=s.codegenNode;if(e.type===13){let t=e.patchFlag;if((t===void 0||t===512||t===1)&&LI(s,n)>=2){let t=RI(s);t&&(e.props=n.hoist(t))}e.dynamicProps&&=n.hoist(e.dynamicProps)}}}else if(s.type===12&&(r?0:PI(s,n))>=2){s.codegenNode.type===14&&s.codegenNode.arguments.length>0&&s.codegenNode.arguments.push(`-1`),o.push(s);continue}if(s.type===1){let t=s.tagType===1;t&&n.scopes.vSlot++,NI(s,e,n,!1,i),t&&n.scopes.vSlot--}else if(s.type===11)NI(s,e,n,s.children.length===1,!0);else if(s.type===9)for(let t=0;t<s.branches.length;t++)NI(s.branches[t],e,n,s.branches[t].children.length===1,i)}let s=!1;if(o.length===a.length&&e.type===1){if(e.tagType===0&&e.codegenNode&&e.codegenNode.type===13&&jN(e.codegenNode.children))e.codegenNode.children=c(GP(e.codegenNode.children)),s=!0;else if(e.tagType===1&&e.codegenNode&&e.codegenNode.type===13&&e.codegenNode.children&&!jN(e.codegenNode.children)&&e.codegenNode.children.type===15){let t=l(e.codegenNode,`default`);t&&(t.returns=c(GP(t.returns)),s=!0)}else if(e.tagType===3&&t&&t.type===1&&t.tagType===1&&t.codegenNode&&t.codegenNode.type===13&&t.codegenNode.children&&!jN(t.codegenNode.children)&&t.codegenNode.children.type===15){let n=OF(e,`slot`,!0),r=n&&n.arg&&l(t.codegenNode,n.arg);r&&(r.returns=c(GP(r.returns)),s=!0)}}if(!s)for(let e of o)e.codegenNode=n.cache(e.codegenNode);function c(e){let t=n.cache(e);return t.needArraySpread=!0,t}function l(e,t){if(e.children&&!jN(e.children)&&e.children.type===15){let n=e.children.properties.find(e=>e.key===t||e.key.content===t);return n&&n.value}}o.length&&n.transformHoist&&n.transformHoist(a,n,e)}function PI(e,t){let{constantCache:n}=t;switch(e.type){case 1:if(e.tagType!==0)return 0;let r=n.get(e);if(r!==void 0)return r;let i=e.codegenNode;if(i.type!==13||i.isBlock&&e.tag!==`svg`&&e.tag!==`foreignObject`&&e.tag!==`math`)return 0;if(i.patchFlag===void 0){let r=3,a=LI(e,t);if(a===0)return n.set(e,0),0;a<r&&(r=a);for(let i=0;i<e.children.length;i++){let a=PI(e.children[i],t);if(a===0)return n.set(e,0),0;a<r&&(r=a)}if(r>1)for(let i=0;i<e.props.length;i++){let a=e.props[i];if(a.type===7&&a.name===`bind`&&a.exp){let i=PI(a.exp,t);if(i===0)return n.set(e,0),0;i<r&&(r=i)}}if(i.isBlock){for(let t=0;t<e.props.length;t++)if(e.props[t].type===7)return n.set(e,0),0;t.removeHelper(oP),t.removeHelper(tF(t.inSSR,i.isComponent)),i.isBlock=!1,t.helper(eF(t.inSSR,i.isComponent))}return n.set(e,r),r}else return n.set(e,0),0;case 2:case 3:return 3;case 9:case 11:case 10:return 0;case 5:case 12:return PI(e.content,t);case 4:return e.constType;case 8:let a=3;for(let n=0;n<e.children.length;n++){let r=e.children[n];if(MN(r)||NN(r))continue;let i=PI(r,t);if(i===0)return 0;i<a&&(a=i)}return a;case 20:return 2;default:return 0}}var FI=new Set([wP,TP,EP,DP]);function II(e,t){if(e.type===14&&!MN(e.callee)&&FI.has(e.callee)){let n=e.arguments[0];if(n.type===4)return PI(n,t);if(n.type===14)return II(n,t)}return 0}function LI(e,t){let n=3,r=RI(e);if(r&&r.type===15){let{properties:e}=r;for(let r=0;r<e.length;r++){let{key:i,value:a}=e[r],o=PI(i,t);if(o===0)return o;o<n&&(n=o);let s;if(s=a.type===4?PI(a,t):a.type===14?II(a,t):0,s===0)return s;s<n&&(n=s)}}return n}function RI(e){let t=e.codegenNode;if(t.type===13)return t.props}function zI(e,{filename:t=``,prefixIdentifiers:n=!1,hoistStatic:r=!1,hmr:i=!1,cacheHandlers:a=!1,nodeTransforms:o=[],directiveTransforms:s={},transformHoist:c=null,isBuiltInComponent:l=DN,isCustomElement:u=DN,expressionPlugins:d=[],scopeId:f=null,slotted:p=!0,ssr:m=!1,inSSR:h=!1,ssrCssVars:g=``,bindingMetadata:_=EN,inline:v=!1,isTS:y=!1,onError:b=mF,onWarn:x=hF,compatConfig:S}){let C=t.replace(/\?.*$/,``).match(/([^/\\]+)\.\w+$/),w={filename:t,selfName:C&&BN(zN(C[1])),prefixIdentifiers:n,hoistStatic:r,hmr:i,cacheHandlers:a,nodeTransforms:o,directiveTransforms:s,transformHoist:c,isBuiltInComponent:l,isCustomElement:u,expressionPlugins:d,scopeId:f,slotted:p,ssr:m,inSSR:h,ssrCssVars:g,bindingMetadata:_,inline:v,isTS:y,onError:b,onWarn:x,compatConfig:S,root:e,helpers:new Map,components:new Set,directives:new Set,hoists:[],imports:[],cached:[],constantCache:new WeakMap,temps:0,identifiers:Object.create(null),scopes:{vFor:0,vSlot:0,vPre:0,vOnce:0},parent:null,grandParent:null,currentNode:e,childIndex:0,inVOnce:!1,helper(e){let t=w.helpers.get(e)||0;return w.helpers.set(e,t+1),e},removeHelper(e){let t=w.helpers.get(e);if(t){let n=t-1;n?w.helpers.set(e,n):w.helpers.delete(e)}},helperString(e){return`_${BP[w.helper(e)]}`},replaceNode(e){w.parent.children[w.childIndex]=w.currentNode=e},removeNode(e){let t=w.parent.children,n=e?t.indexOf(e):w.currentNode?w.childIndex:-1;!e||e===w.currentNode?(w.currentNode=null,w.onNodeRemoved()):w.childIndex>n&&(w.childIndex--,w.onNodeRemoved()),w.parent.children.splice(n,1)},onNodeRemoved:DN,addIdentifiers(e){},removeIdentifiers(e){},hoist(e){MN(e)&&(e=Z(e)),w.hoists.push(e);let t=Z(`_hoisted_${w.hoists.length}`,!1,e.loc,2);return t.hoisted=e,t},cache(e,t=!1,n=!1){let r=QP(w.cached.length,e,t,n);return w.cached.push(r),r}};return w.filters=new Set,w}function BI(e,t){let n=zI(e,t);UI(e,n),t.hoistStatic&&jI(e,n),t.ssr||VI(e,n),e.helpers=new Set([...n.helpers.keys()]),e.components=[...n.components],e.directives=[...n.directives],e.imports=n.imports,e.hoists=n.hoists,e.temps=n.temps,e.cached=n.cached,e.transformed=!0,e.filters=[...n.filters]}function VI(e,t){let{helper:n}=t,{children:r}=e;if(r.length===1){let n=MI(e);if(n&&n.codegenNode){let r=n.codegenNode;r.type===13&&nF(r,t),e.codegenNode=r}else e.codegenNode=r[0]}else r.length>1&&(e.codegenNode=WP(t,n(tP),void 0,e.children,64,void 0,void 0,!0,void 0,!1))}function HI(e,t){let n=0,r=()=>{n--};for(;n<e.children.length;n++){let i=e.children[n];MN(i)||(t.grandParent=t.parent,t.parent=e,t.childIndex=n,t.onNodeRemoved=r,UI(i,t))}}function UI(e,t){t.currentNode=e;let{nodeTransforms:n}=t,r=[];for(let i=0;i<n.length;i++){let a=n[i](e,t);if(a&&(jN(a)?r.push(...a):r.push(a)),t.currentNode)e=t.currentNode;else return}switch(e.type){case 3:t.ssr||t.helper(dP);break;case 5:t.ssr||t.helper(SP);break;case 9:for(let n=0;n<e.branches.length;n++)UI(e.branches[n],t);break;case 10:case 11:case 1:case 0:HI(e,t);break}t.currentNode=e;let i=r.length;for(;i--;)r[i]()}function WI(e,t){let n=MN(e)?t=>t===e:t=>e.test(t);return(e,r)=>{if(e.type===1){let{props:i}=e;if(e.tagType===3&&i.some(PF))return;let a=[];for(let o=0;o<i.length;o++){let s=i[o];if(s.type===7&&n(s.name)){i.splice(o,1),o--;let n=t(e,s,r);n&&a.push(n)}}return a}}}var GI=`/*@__PURE__*/`,KI=e=>`${BP[e]}: _${BP[e]}`;function qI(e,{mode:t=`function`,prefixIdentifiers:n=t===`module`,sourceMap:r=!1,filename:i=`template.vue.html`,scopeId:a=null,optimizeImports:o=!1,runtimeGlobalName:s=`Vue`,runtimeModuleName:c=`vue`,ssrRuntimeModuleName:l=`vue/server-renderer`,ssr:u=!1,isTS:d=!1,inSSR:f=!1}){let p={mode:t,prefixIdentifiers:n,sourceMap:r,filename:i,scopeId:a,optimizeImports:o,runtimeGlobalName:s,runtimeModuleName:c,ssrRuntimeModuleName:l,ssr:u,isTS:d,inSSR:f,source:e.source,code:``,column:1,line:1,offset:0,indentLevel:0,pure:!1,map:void 0,helper(e){return`_${BP[e]}`},push(e,t=-2,n){p.code+=e},indent(){m(++p.indentLevel)},deindent(e=!1){e?--p.indentLevel:m(--p.indentLevel)},newline(){m(p.indentLevel)}};function m(e){p.push(`
`+`  `.repeat(e),0)}return p}function JI(e,t={}){let n=qI(e,t);t.onContextCreated&&t.onContextCreated(n);let{mode:r,push:i,prefixIdentifiers:a,indent:o,deindent:s,newline:c,scopeId:l,ssr:u}=n,d=Array.from(e.helpers),f=d.length>0,p=!a&&r!==`module`;if(YI(e,n),i(`function ${u?`ssrRender`:`render`}(${(u?[`_ctx`,`_push`,`_parent`,`_attrs`]:[`_ctx`,`_cache`]).join(`, `)}) {`),o(),p&&(i(`with (_ctx) {`),o(),f&&(i(`const { ${d.map(KI).join(`, `)} } = _Vue
`,-1),c())),e.components.length&&(XI(e.components,`component`,n),(e.directives.length||e.temps>0)&&c()),e.directives.length&&(XI(e.directives,`directive`,n),e.temps>0&&c()),e.filters&&e.filters.length&&(c(),XI(e.filters,`filter`,n),c()),e.temps>0){i(`let `);for(let t=0;t<e.temps;t++)i(`${t>0?`, `:``}_temp${t}`)}return(e.components.length||e.directives.length||e.temps)&&(i(`
`,0),c()),u||i(`return `),e.codegenNode?eL(e.codegenNode,n):i(`null`),p&&(s(),i(`}`)),s(),i(`}`),{ast:e,code:n.code,preamble:``,map:n.map?n.map.toJSON():void 0}}function YI(e,t){let{ssr:n,prefixIdentifiers:r,push:i,newline:a,runtimeModuleName:o,runtimeGlobalName:s,ssrRuntimeModuleName:c}=t,l=s,u=Array.from(e.helpers);u.length>0&&(i(`const _Vue = ${l}
`,-1),e.hoists.length&&i(`const { ${[lP,uP,dP,fP,pP].filter(e=>u.includes(e)).map(KI).join(`, `)} } = _Vue
`,-1)),ZI(e.hoists,t),a(),i(`return `)}function XI(e,t,{helper:n,push:r,newline:i,isTS:a}){let o=n(t===`filter`?_P:t===`component`?mP:gP);for(let n=0;n<e.length;n++){let s=e[n],c=s.endsWith(`__self`);c&&(s=s.slice(0,-6)),r(`const ${VF(s,t)} = ${o}(${JSON.stringify(s)}${c?`, true`:``})${a?`!`:``}`),n<e.length-1&&i()}}function ZI(e,t){if(!e.length)return;t.pure=!0;let{push:n,newline:r}=t;r();for(let i=0;i<e.length;i++){let a=e[i];a&&(n(`const _hoisted_${i+1} = `),eL(a,t),r())}t.pure=!1}function QI(e,t){let n=e.length>3||!1;t.push(`[`),n&&t.indent(),$I(e,t,n),n&&t.deindent(),t.push(`]`)}function $I(e,t,n=!1,r=!0){let{push:i,newline:a}=t;for(let o=0;o<e.length;o++){let s=e[o];MN(s)?i(s,-3):jN(s)?QI(s,t):eL(s,t),o<e.length-1&&(n?(r&&i(`,`),a()):r&&i(`, `))}}function eL(e,t){if(MN(e)){t.push(e,-3);return}if(NN(e)){t.push(t.helper(e));return}switch(e.type){case 1:case 9:case 11:eL(e.codegenNode,t);break;case 2:tL(e,t);break;case 4:nL(e,t);break;case 5:rL(e,t);break;case 12:eL(e.codegenNode,t);break;case 8:iL(e,t);break;case 3:oL(e,t);break;case 13:sL(e,t);break;case 14:lL(e,t);break;case 15:uL(e,t);break;case 17:dL(e,t);break;case 18:fL(e,t);break;case 19:pL(e,t);break;case 20:mL(e,t);break;case 21:$I(e.body,t,!0,!1);break;case 22:break;case 23:break;case 24:break;case 25:break;case 26:break;case 10:break;default:}}function tL(e,t){t.push(JSON.stringify(e.content),-3,e)}function nL(e,t){let{content:n,isStatic:r}=e;t.push(r?JSON.stringify(n):n,-3,e)}function rL(e,t){let{push:n,helper:r,pure:i}=t;i&&n(GI),n(`${r(SP)}(`),eL(e.content,t),n(`)`)}function iL(e,t){for(let n=0;n<e.children.length;n++){let r=e.children[n];MN(r)?t.push(r,-3):eL(r,t)}}function aL(e,t){let{push:n}=t;e.type===8?(n(`[`),iL(e,t),n(`]`)):e.isStatic?n(bF(e.content)?e.content:JSON.stringify(e.content),-2,e):n(`[${e.content}]`,-3,e)}function oL(e,t){let{push:n,helper:r,pure:i}=t;i&&n(GI),n(`${r(dP)}(${JSON.stringify(e.content)})`,-3,e)}function sL(e,t){let{push:n,helper:r,pure:i}=t,{tag:a,props:o,children:s,patchFlag:c,dynamicProps:l,directives:u,isBlock:d,disableTracking:f,isComponent:p}=e,m;c&&(m=String(c)),u&&n(r(vP)+`(`),d&&n(`(${r(oP)}(${f?`true`:``}), `),i&&n(GI),n(r(d?tF(t.inSSR,p):eF(t.inSSR,p))+`(`,-2,e),$I(cL([a,o,s,m,l]),t),n(`)`),d&&n(`)`),u&&(n(`, `),eL(u,t),n(`)`))}function cL(e){let t=e.length;for(;t--&&e[t]==null;);return e.slice(0,t+1).map(e=>e||`null`)}function lL(e,t){let{push:n,helper:r,pure:i}=t,a=MN(e.callee)?e.callee:r(e.callee);i&&n(GI),n(a+`(`,-2,e),$I(e.arguments,t),n(`)`)}function uL(e,t){let{push:n,indent:r,deindent:i,newline:a}=t,{properties:o}=e;if(!o.length){n(`{}`,-2,e);return}let s=o.length>1||!1;n(s?`{`:`{ `),s&&r();for(let e=0;e<o.length;e++){let{key:r,value:i}=o[e];aL(r,t),n(`: `),eL(i,t),e<o.length-1&&(n(`,`),a())}s&&i(),n(s?`}`:` }`)}function dL(e,t){QI(e.elements,t)}function fL(e,t){let{push:n,indent:r,deindent:i}=t,{params:a,returns:o,body:s,newline:c,isSlot:l}=e;l&&n(`_${BP[FP]}(`),n(`(`,-2,e),jN(a)?$I(a,t):a&&eL(a,t),n(`) => `),(c||s)&&(n(`{`),r()),o?(c&&n(`return `),jN(o)?QI(o,t):eL(o,t)):s&&eL(s,t),(c||s)&&(i(),n(`}`)),l&&(e.isNonScopedSlot&&n(`, undefined, true`),n(`)`))}function pL(e,t){let{test:n,consequent:r,alternate:i,newline:a}=e,{push:o,indent:s,deindent:c,newline:l}=t;if(n.type===4){let e=!bF(n.content);e&&o(`(`),nL(n,t),e&&o(`)`)}else o(`(`),eL(n,t),o(`)`);a&&s(),t.indentLevel++,a||o(` `),o(`? `),eL(r,t),t.indentLevel--,a&&l(),a||o(` `),o(`: `);let u=i.type===19;u||t.indentLevel++,eL(i,t),u||t.indentLevel--,a&&c(!0)}function mL(e,t){let{push:n,helper:r,indent:i,deindent:a,newline:o}=t,{needPauseTracking:s,needArraySpread:c}=e;c&&n(`[...(`),n(`_cache[${e.index}] || (`),s&&(i(),n(`${r(MP)}(-1`),e.inVOnce&&n(`, true`),n(`),`),o(),n(`(`)),n(`_cache[${e.index}] = `),eL(e.value,t),s&&(n(`).cacheIndex = ${e.index},`),o(),n(`${r(MP)}(1),`),o(),n(`_cache[${e.index}]`),a()),n(`)`),c&&n(`)]`)}RegExp(`\\b`+`arguments,await,break,case,catch,class,const,continue,debugger,default,delete,do,else,export,extends,finally,for,function,if,import,let,new,return,super,switch,throw,try,var,void,while,with,yield`.split(`,`).join(`\\b|\\b`)+`\\b`);var hL=WI(/^(?:if|else|else-if)$/,(e,t,n)=>gL(e,t,n,(e,t,r)=>{let i=n.parent.children,a=i.indexOf(e),o=0;for(;a-->=0;){let e=i[a];e&&e.type===9&&(o+=e.branches.length)}return()=>{if(r)e.codegenNode=vL(t,o,n);else{let r=bL(e.codegenNode);r.alternate=vL(t,o+e.branches.length-1,n)}}}));function gL(e,t,n,r){if(t.name!==`else`&&(!t.exp||!t.exp.content.trim())){let r=t.exp?t.exp.loc:e.loc;n.onError(gF(28,t.loc)),t.exp=Z(`true`,!1,r)}if(t.name===`if`){let i=_L(e,t),a={type:9,loc:wI(e.loc),branches:[i]};if(n.replaceNode(a),r)return r(a,i,!0)}else{let i=n.parent.children,a=i.indexOf(e);for(;a-->=-1;){let o=i[a];if(o&&KF(o)){n.removeNode(o);continue}if(o&&o.type===9){(t.name===`else-if`||t.name===`else`)&&o.branches[o.branches.length-1].condition===void 0&&n.onError(gF(30,e.loc)),n.removeNode();let i=_L(e,t);o.branches.push(i);let a=r&&r(o,i,!1);UI(i,n),a&&a(),n.currentNode=null}else n.onError(gF(30,e.loc));break}}}function _L(e,t){let n=e.tagType===3;return{type:10,loc:e.loc,condition:t.name===`else`?void 0:t.exp,children:n&&!OF(e,`for`)?e.children:[e],userKey:kF(e,`key`),isTemplateIf:n}}function vL(e,t,n){return e.condition?ZP(e.condition,yL(e,t,n),YP(n.helper(dP),[`""`,`true`])):yL(e,t,n)}function yL(e,t,n){let{helper:r}=n,i=qP(`key`,Z(`${t}`,!1,HP,2)),{children:a}=e,o=a[0];if(a.length!==1||o.type!==1)if(a.length===1&&o.type===11){let e=o.codegenNode;return zF(e,i,n),e}else return WP(n,r(tP),KP([i]),a,64,void 0,void 0,!0,!1,!1,e.loc);else{let e=o.codegenNode,t=HF(e);return t.type===13&&nF(t,n),zF(t,i,n),e}}function bL(e){for(;;)if(e.type===19)if(e.alternate.type===19)e=e.alternate;else return e;else e.type===20&&(e=e.value)}var xL=WI(`for`,(e,t,n)=>{let{helper:r,removeHelper:i}=n;return SL(e,t,n,t=>{let a=YP(r(yP),[t.source]),o=FF(e),s=OF(e,`memo`),c=kF(e,`key`,!1,!0);c&&c.type;let l=c&&(c.type===6?c.value?Z(c.value.content,!0):void 0:c.exp),u=c&&l?qP(`key`,l):null,d=t.source.type===4&&t.source.constType>0,f=d?64:c?128:256;return t.codegenNode=WP(n,r(tP),void 0,a,f,void 0,void 0,!0,!d,!1,e.loc),()=>{let c,{children:f}=t,p=f.length!==1||f[0].type!==1,m=IF(e)?e:o&&e.children.length===1&&IF(e.children[0])?e.children[0]:null;if(m?(c=m.codegenNode,o&&u&&zF(c,u,n)):p?c=WP(n,r(tP),u?KP([u]):void 0,e.children,64,void 0,void 0,!0,void 0,!1):(c=f[0].codegenNode,o&&u&&zF(c,u,n),c.isBlock!==!d&&(c.isBlock?(i(oP),i(tF(n.inSSR,c.isComponent))):i(eF(n.inSSR,c.isComponent))),c.isBlock=!d,c.isBlock?(r(oP),r(tF(n.inSSR,c.isComponent))):r(eF(n.inSSR,c.isComponent))),s){let e=XP(wL(t.parseResult,[Z(`_cached`)]));e.body=$P([JP([`const _memo = (`,s.exp,`)`]),JP([`if (_cached`,...l?[` && _cached.key === `,l]:[],` && ${n.helperString(zP)}(_cached, _memo)) return _cached`]),JP([`const _item = `,c]),Z(`_item.memo = _memo`),Z(`return _item`)]),a.arguments.push(e,Z(`_cache`),Z(String(n.cached.length))),n.cached.push(null)}else a.arguments.push(XP(wL(t.parseResult),c,!0))}})});function SL(e,t,n,r){if(!t.exp){n.onError(gF(31,t.loc));return}let i=t.forParseResult;if(!i){n.onError(gF(32,t.loc));return}CL(i,n);let{addIdentifiers:a,removeIdentifiers:o,scopes:s}=n,{source:c,value:l,key:u,index:d}=i,f={type:11,loc:t.loc,source:c,valueAlias:l,keyAlias:u,objectIndexAlias:d,parseResult:i,children:FF(e)?e.children:[e]};n.replaceNode(f),s.vFor++;let p=r&&r(f);return()=>{s.vFor--,p&&p()}}function CL(e,t){e.finalized||=!0}function wL({value:e,key:t,index:n},r=[]){return TL([e,t,n,...r])}function TL(e){let t=e.length;for(;t--&&!e[t];);return e.slice(0,t+1).map((e,t)=>e||Z(`_`.repeat(t+1),!1))}var EL=Z(`undefined`,!1),DL=(e,t)=>{if(e.type===1&&(e.tagType===1||e.tagType===3)){let n=OF(e,`slot`);if(n)return n.exp,t.scopes.vSlot++,()=>{t.scopes.vSlot--}}},OL=(e,t,n,r)=>XP(e,n,!1,!0,n.length?n[0].loc:r);function kL(e,t,n=OL){t.helper(FP);let{children:r,loc:i}=e,a=[],o=[],s=t.scopes.vSlot>0||t.scopes.vFor>0,c=OF(e,`slot`,!0);if(c){let{arg:e,exp:t}=c;e&&!_F(e)&&(s=!0),a.push(qP(e||Z(`default`,!0),n(t,void 0,r,i)))}let l=!1,u=!1,d=[],f=new Set,p=0;for(let e=0;e<r.length;e++){let i=r[e],m;if(!FF(i)||!(m=OF(i,`slot`,!0))){i.type!==3&&d.push(i);continue}if(c){t.onError(gF(37,m.loc));break}l=!0;let{children:h,loc:g}=i,{arg:_=Z(`default`,!0),exp:v,loc:y}=m,b;_F(_)?b=_?_.content:`default`:s=!0;let x=OF(i,`for`),S=n(v,x,h,g),C,w;if(C=OF(i,`if`))s=!0,o.push(ZP(C.exp,AL(_,S,p++),EL));else if(w=OF(i,/^else(?:-if)?$/,!0)){let n=e,i;for(;n--&&(i=r[n],KF(i)););if(i&&FF(i)&&OF(i,/^(?:else-)?if$/)){let e=o[o.length-1];for(;e.alternate.type===19;)e=e.alternate;e.alternate=w.exp?ZP(w.exp,AL(_,S,p++),EL):AL(_,S,p++)}else t.onError(gF(30,w.loc))}else if(x){s=!0;let e=x.forParseResult;e?(CL(e,t),o.push(YP(t.helper(yP),[e.source,XP(wL(e),AL(_,S),!0)]))):t.onError(gF(32,x.loc))}else{if(b){if(f.has(b)){t.onError(gF(38,y));continue}f.add(b),b===`default`&&(u=!0)}a.push(qP(_,S))}}if(!c){let e=(e,r)=>{let a=n(e,void 0,r,i);return t.compatConfig&&(a.isNonScopedSlot=!0),qP(`default`,a)};l?d.length&&!d.every(GF)&&(u?t.onError(gF(39,d[0].loc)):a.push(e(void 0,d))):a.push(e(void 0,r))}let m=s?2:jL(e.children)?3:1,h=KP(a.concat(qP(`_`,Z(m+``,!1))),i);return o.length&&(h=YP(t.helper(xP),[h,GP(o)])),{slots:h,hasDynamicSlots:s}}function AL(e,t,n){let r=[qP(`name`,e),qP(`fn`,t)];return n!=null&&r.push(qP(`key`,Z(String(n),!0))),KP(r)}function jL(e){for(let t=0;t<e.length;t++){let n=e[t];switch(n.type){case 1:if(n.tagType===2||jL(n.children))return!0;break;case 9:if(jL(n.branches))return!0;break;case 10:case 11:if(jL(n.children))return!0;break}}return!1}var ML=new WeakMap,NL=(e,t)=>function(){if(e=t.currentNode,!(e.type===1&&(e.tagType===0||e.tagType===1)))return;let{tag:n,props:r}=e,i=e.tagType===1,a=i?PL(e,t):`"${n}"`,o=PN(a)&&a.callee===hP,s,c,l=0,u,d,f,p=o||a===nP||a===rP||!i&&(n===`svg`||n===`foreignObject`||n===`math`);if(r.length>0){let n=FL(e,t,void 0,i,o);s=n.props,l=n.patchFlag,d=n.dynamicPropNames;let r=n.directives;f=r&&r.length?GP(r.map(e=>RL(e,t))):void 0,n.shouldUseBlock&&(p=!0)}if(e.children.length>0)if(a===iP&&(p=!0,l|=1024),i&&a!==nP&&a!==iP){let{slots:n,hasDynamicSlots:r}=kL(e,t);c=n,r&&(l|=1024)}else if(e.children.length===1&&a!==nP){let n=e.children[0],r=n.type,i=r===5||r===8;i&&PI(n,t)===0&&(l|=1),c=i||r===2?n:e.children}else c=e.children;d&&d.length&&(u=zL(d)),e.codegenNode=WP(t,a,s,c,l===0?void 0:l,u,f,!!p,!1,i,e.loc)};function PL(e,t,n=!1){let{tag:r}=e,i=BL(r),a=kF(e,`is`,!1,!0);if(a)if(i||fF(`COMPILER_IS_ON_ELEMENT`,t)){let e;if(a.type===6?e=a.value&&Z(a.value.content,!0):(e=a.exp,e||=Z(`is`,!1,a.arg.loc)),e)return YP(t.helper(hP),[e])}else a.type===6&&a.value.content.startsWith(`vue:`)&&(r=a.value.content.slice(4));let o=vF(r)||t.isBuiltInComponent(r);return o?(n||t.helper(o),o):(t.helper(mP),t.components.add(r),VF(r,`component`))}function FL(e,t,n=e.props,r,i,a=!1){let{tag:o,loc:s,children:c}=e,l=[],u=[],d=[],f=c.length>0,p=!1,m=0,h=!1,g=!1,_=!1,v=!1,y=!1,b=!1,x=[],S=e=>{l.length&&(u.push(KP(IL(l),s)),l=[]),e&&u.push(e)},C=()=>{t.scopes.vFor>0&&l.push(qP(Z(`ref_for`,!0),Z(`true`)))},w=({key:e,value:n})=>{if(_F(e)){let a=e.content,o=kN(a);if(o&&(!r||i)&&a.toLowerCase()!==`onclick`&&a!==`onUpdate:modelValue`&&!FN(a)&&(v=!0),o&&FN(a)&&(b=!0),o&&n.type===14&&(n=n.arguments[0]),n.type===20||(n.type===4||n.type===8)&&PI(n,t)>0)return;a===`ref`?h=!0:a===`class`?g=!0:a===`style`?_=!0:a!==`key`&&!x.includes(a)&&x.push(a),r&&(a===`class`||a===`style`)&&!x.includes(a)&&x.push(a)}else y=!0};for(let i=0;i<n.length;i++){let c=n[i];if(c.type===6){let{loc:e,name:n,nameLoc:r,value:i}=c;if(n===`ref`&&(h=!0,C()),n===`is`&&(BL(o)||i&&i.content.startsWith(`vue:`)||fF(`COMPILER_IS_ON_ELEMENT`,t)))continue;l.push(qP(Z(n,!0,r),Z(i?i.content:``,!0,i?i.loc:e)))}else{let{name:n,arg:i,exp:h,loc:g,modifiers:_}=c,v=n===`bind`,b=n===`on`;if(n===`slot`){r||t.onError(gF(40,g));continue}if(n===`once`||n===`memo`||n===`is`||v&&AF(i,`is`)&&(BL(o)||fF(`COMPILER_IS_ON_ELEMENT`,t))||b&&a)continue;if((v&&AF(i,`key`)||b&&f&&AF(i,`vue:before-update`))&&(p=!0),v&&AF(i,`ref`)&&C(),!i&&(v||b)){if(y=!0,h)if(v){if(S(),fF(`COMPILER_V_BIND_OBJECT_ORDER`,t)){u.unshift(h);continue}C(),S(),u.push(h)}else S({type:14,loc:g,callee:t.helper(OP),arguments:r?[h]:[h,`true`]});else t.onError(gF(v?34:35,g));continue}v&&_.some(e=>e.content===`prop`)&&(m|=32);let x=t.directiveTransforms[n];if(x){let{props:n,needRuntime:r}=x(c,e,t);!a&&n.forEach(w),b&&i&&!_F(i)?S(KP(n,s)):l.push(...n),r&&(d.push(c),NN(r)&&ML.set(c,r))}else IN(n)||(d.push(c),f&&(p=!0))}}let T;if(u.length?(S(),T=u.length>1?YP(t.helper(CP),u,s):u[0]):l.length&&(T=KP(IL(l),s)),y?m|=16:(g&&!r&&(m|=2),_&&!r&&(m|=4),x.length&&(m|=8),v&&(m|=32)),!p&&(m===0||m===32)&&(h||b||d.length>0)&&(m|=512),!t.inSSR&&T)switch(T.type){case 15:let e=-1,n=-1,r=!1;for(let t=0;t<T.properties.length;t++){let i=T.properties[t].key;_F(i)?i.content===`class`?e=t:i.content===`style`&&(n=t):i.isHandlerKey||(r=!0)}let i=T.properties[e],a=T.properties[n];r?T=YP(t.helper(EP),[T]):(i&&!_F(i.value)&&(i.value=YP(t.helper(wP),[i.value])),a&&(_||a.value.type===4&&a.value.content.trim()[0]===`[`||a.value.type===17)&&(a.value=YP(t.helper(TP),[a.value])));break;case 14:break;default:T=YP(t.helper(EP),[YP(t.helper(DP),[T])]);break}return{props:T,directives:d,patchFlag:m,dynamicPropNames:x,shouldUseBlock:p}}function IL(e){let t=new Map,n=[];for(let r=0;r<e.length;r++){let i=e[r];if(i.key.type===8||!i.key.isStatic){n.push(i);continue}let a=i.key.content,o=t.get(a);o?(a===`style`||a===`class`||kN(a))&&LL(o,i):(t.set(a,i),n.push(i))}return n}function LL(e,t){e.value.type===17?e.value.elements.push(t.value):e.value=GP([e.value,t.value],e.loc)}function RL(e,t){let n=[],r=ML.get(e);r?n.push(t.helperString(r)):(t.helper(gP),t.directives.add(e.name),n.push(VF(e.name,`directive`)));let{loc:i}=e;if(e.exp&&n.push(e.exp),e.arg&&(e.exp||n.push(`void 0`),n.push(e.arg)),Object.keys(e.modifiers).length){e.arg||(e.exp||n.push(`void 0`),n.push(`void 0`));let t=Z(`true`,!1,i);n.push(KP(e.modifiers.map(e=>qP(e,t)),i))}return GP(n,e.loc)}function zL(e){let t=`[`;for(let n=0,r=e.length;n<r;n++)t+=JSON.stringify(e[n]),n<r-1&&(t+=`, `);return t+`]`}function BL(e){return e===`component`||e===`Component`}var VL=(e,t)=>{if(IF(e)){let{children:n,loc:r}=e,{slotName:i,slotProps:a}=HL(e,t),o=[t.prefixIdentifiers?`_ctx.$slots`:`$slots`,i,`{}`,`undefined`,`true`],s=2;a&&(o[2]=a,s=3),n.length&&(o[3]=XP([],n,!1,!1,r),s=4),t.scopeId&&!t.slotted&&(s=5),o.splice(s),e.codegenNode=YP(t.helper(bP),o,r)}};function HL(e,t){let n=`"default"`,r,i=[];for(let t=0;t<e.props.length;t++){let r=e.props[t];r.type===6?r.value&&(r.name===`name`?n=JSON.stringify(r.value.content):(r.name=zN(r.name),i.push(r))):r.name===`bind`&&AF(r.arg,`name`)?r.exp?n=r.exp:r.arg&&r.arg.type===4&&(n=r.exp=Z(zN(r.arg.content),!1,r.arg.loc)):(r.name===`bind`&&r.arg&&_F(r.arg)&&(r.arg.content=zN(r.arg.content)),i.push(r))}if(i.length>0){let{props:n,directives:a}=FL(e,t,i,!1,!1);r=n,a.length&&t.onError(gF(36,a[0].loc))}return{slotName:n,slotProps:r}}var UL=(e,t,n,r)=>{let{loc:i,modifiers:a,arg:o}=e;!e.exp&&!a.length&&n.onError(gF(35,i));let s;if(o.type===4)if(o.isStatic){let e=o.content;e.startsWith(`vue:`)&&(e=`vnode-${e.slice(4)}`),s=Z(t.tagType!==0||e.startsWith(`vnode`)||!/[A-Z]/.test(e)?VN(zN(e)):`on:${e}`,!0,o.loc)}else s=JP([`${n.helperString(jP)}(`,o,`)`]);else s=o,s.children.unshift(`${n.helperString(jP)}(`),s.children.push(`)`);let c=e.exp;c&&!c.content.trim()&&(c=void 0);let l=n.cacheHandlers&&!c&&!n.inVOnce;if(c){let e=TF(c),t=!(e||DF(c)),n=c.content.includes(`;`);(t||l&&e)&&(c=JP([`${t?`$event`:`(...args)`} => ${n?`{`:`(`}`,c,n?`}`:`)`]))}let u={props:[qP(s,c||Z(`() => {}`,!1,i))]};return r&&(u=r(u)),l&&(u.props[0].value=n.cache(u.props[0].value)),u.props.forEach(e=>e.key.isHandlerKey=!0),u},WL=(e,t,n)=>{let{modifiers:r,loc:i}=e,a=e.arg,{exp:o}=e;return o&&o.type===4&&!o.content.trim()&&(o=void 0),a.type===4?a.isStatic||(a.content=a.content?`${a.content} || ""`:`""`):(a.children.unshift(`(`),a.children.push(`) || ""`)),r.some(e=>e.content===`camel`)&&(a.type===4?a.isStatic?a.content=zN(a.content):a.content=`${n.helperString(kP)}(${a.content})`:(a.children.unshift(`${n.helperString(kP)}(`),a.children.push(`)`))),n.inSSR||(r.some(e=>e.content===`prop`)&&GL(a,`.`),r.some(e=>e.content===`attr`)&&GL(a,`^`)),{props:[qP(a,o)]}},GL=(e,t)=>{e.type===4?e.isStatic?e.content=t+e.content:e.content=`\`${t}\${${e.content}}\``:(e.children.unshift(`'${t}' + (`),e.children.push(`)`))},KL=(e,t)=>{if(e.type===0||e.type===1||e.type===11||e.type===10)return()=>{let n=e.children,r,i=!1;for(let e=0;e<n.length;e++){let t=n[e];if(MF(t)){i=!0;for(let i=e+1;i<n.length;i++){let a=n[i];if(MF(a))r||=n[e]=JP([t],t.loc),r.children.push(` + `,a),n.splice(i,1),i--;else{r=void 0;break}}}}if(!(!i||n.length===1&&(e.type===0||e.type===1&&e.tagType===0&&!e.props.find(e=>e.type===7&&!t.directiveTransforms[e.name])&&e.tag!==`template`)))for(let e=0;e<n.length;e++){let r=n[e];if(MF(r)||r.type===8){let i=[];(r.type!==2||r.content!==` `)&&i.push(r),!t.ssr&&PI(r,t)===0&&i.push(`1`),n[e]={type:12,content:r,loc:r.loc,codegenNode:YP(t.helper(fP),i)}}}}},qL=new WeakSet,JL=(e,t)=>{if(e.type===1&&OF(e,`once`,!0))return qL.has(e)||t.inVOnce||t.inSSR?void 0:(qL.add(e),t.inVOnce=!0,t.helper(MP),()=>{t.inVOnce=!1;let e=t.currentNode;e.codegenNode&&=t.cache(e.codegenNode,!0,!0)})},YL=(e,t,n)=>{let{exp:r,arg:i}=e;if(!r)return n.onError(gF(41,e.loc)),XL();let a=r.loc.source.trim(),o=r.type===4?r.content:a,s=n.bindingMetadata[a];if(s===`props`||s===`props-aliased`)return n.onError(gF(44,r.loc)),XL();if(s===`literal-const`||s===`setup-const`)return n.onError(gF(45,r.loc)),XL();if(!o.trim()||!TF(r))return n.onError(gF(42,r.loc)),XL();let c=i||Z(`modelValue`,!0),l=i?_F(i)?`onUpdate:${zN(i.content)}`:JP([`"onUpdate:" + `,i]):`onUpdate:modelValue`,u;u=JP([`${n.isTS?`($event: any)`:`$event`} => ((`,r,`) = $event)`]);let d=[qP(c,e.exp),qP(l,u)];if(e.modifiers.length&&t.tagType===1){let t=e.modifiers.map(e=>e.content).map(e=>(bF(e)?e:JSON.stringify(e))+`: true`).join(`, `),n=i?_F(i)?`${i.content}Modifiers`:JP([i,` + "Modifiers"`]):`modelModifiers`;d.push(qP(n,Z(`{ ${t} }`,!1,e.loc,2)))}return XL(d)};function XL(e=[]){return{props:e}}var ZL=/[\w).+\-_$\]]/,QL=(e,t)=>{fF(`COMPILER_FILTERS`,t)&&(e.type===5?$L(e.content,t):e.type===1&&e.props.forEach(e=>{e.type===7&&e.name!==`for`&&e.exp&&$L(e.exp,t)}))};function $L(e,t){if(e.type===4)eR(e,t);else for(let n=0;n<e.children.length;n++){let r=e.children[n];typeof r==`object`&&(r.type===4?eR(r,t):r.type===8?$L(e,t):r.type===5&&$L(r.content,t))}}function eR(e,t){let n=e.content,r=!1,i=!1,a=!1,o=!1,s=0,c=0,l=0,u=0,d,f,p,m,h=[];for(p=0;p<n.length;p++)if(f=d,d=n.charCodeAt(p),r)d===39&&f!==92&&(r=!1);else if(i)d===34&&f!==92&&(i=!1);else if(a)d===96&&f!==92&&(a=!1);else if(o)d===47&&f!==92&&(o=!1);else if(d===124&&n.charCodeAt(p+1)!==124&&n.charCodeAt(p-1)!==124&&!s&&!c&&!l)m===void 0?(u=p+1,m=n.slice(0,p).trim()):g();else{switch(d){case 34:i=!0;break;case 39:r=!0;break;case 96:a=!0;break;case 40:l++;break;case 41:l--;break;case 91:c++;break;case 93:c--;break;case 123:s++;break;case 125:s--;break}if(d===47){let e=p-1,t;for(;e>=0&&(t=n.charAt(e),t===` `);e--);(!t||!ZL.test(t))&&(o=!0)}}m===void 0?m=n.slice(0,p).trim():u!==0&&g();function g(){h.push(n.slice(u,p).trim()),u=p+1}if(h.length){for(p=0;p<h.length;p++)m=tR(m,h[p],t);e.content=m,e.ast=void 0}}function tR(e,t,n){n.helper(_P);let r=t.indexOf(`(`);if(r<0)return n.filters.add(t),`${VF(t,`filter`)}(${e})`;{let i=t.slice(0,r),a=t.slice(r+1);return n.filters.add(i),`${VF(i,`filter`)}(${e}${a===`)`?a:`,`+a}`}}var nR=new WeakSet,rR=(e,t)=>{if(e.type===1){let n=OF(e,`memo`);return!n||nR.has(e)||t.inSSR?void 0:(nR.add(e),()=>{let r=e.codegenNode||t.currentNode.codegenNode;r&&r.type===13&&(e.tagType!==1&&nF(r,t),e.codegenNode=YP(t.helper(RP),[n.exp,XP(void 0,r),`_cache`,String(t.cached.length)]),t.cached.push(null))})}},iR=(e,t)=>{if(e.type===1){for(let n of e.props)if(n.type===7&&n.name===`bind`&&(!n.exp||n.exp.type===4&&!n.exp.content.trim())&&n.arg){let e=n.arg;if(e.type!==4||!e.isStatic)t.onError(gF(53,e.loc)),n.exp=Z(``,!0,e.loc);else{let t=zN(e.content);(xF.test(t[0])||t[0]===`-`)&&(n.exp=Z(t,!1,e.loc))}}}};function aR(e){return[[iR,JL,hL,rR,xL,...[QL],...[],VL,NL,DL,KL],{on:UL,bind:WL,model:YL}]}function oR(e,t={}){let n=t.onError||mF,r=t.mode===`module`;t.prefixIdentifiers===!0?n(gF(48)):r&&n(gF(49)),t.cacheHandlers&&n(gF(50)),t.scopeId&&!r&&n(gF(51));let i=AN({},t,{prefixIdentifiers:!1}),a=MN(e)?AI(e,i):e,[o,s]=aR();return BI(a,AN({},i,{nodeTransforms:[...o,...t.nodeTransforms||[]],directiveTransforms:AN({},s,t.directiveTransforms||{})})),JI(a,i)}var sR=()=>({props:[]}),cR=Symbol(``),lR=Symbol(``),uR=Symbol(``),dR=Symbol(``),fR=Symbol(``),pR=Symbol(``),mR=Symbol(``),hR=Symbol(``),gR=Symbol(``),_R=Symbol(``);VP({[cR]:`vModelRadio`,[lR]:`vModelCheckbox`,[uR]:`vModelText`,[dR]:`vModelSelect`,[fR]:`vModelDynamic`,[pR]:`withModifiers`,[mR]:`withKeys`,[hR]:`vShow`,[gR]:`Transition`,[_R]:`TransitionGroup`});var vR;function yR(e,t=!1){return vR||=document.createElement(`div`),t?(vR.innerHTML=`<div foo="${e.replace(/"/g,`&quot;`)}">`,vR.children[0].getAttribute(`foo`)):(vR.innerHTML=e,vR.textContent)}var bR={parseMode:`html`,isVoidTag:eP,isNativeTag:e=>ZN(e)||QN(e)||$N(e),isPreTag:e=>e===`pre`,isIgnoreNewlineTag:e=>e===`pre`||e===`textarea`,decodeEntities:yR,isBuiltInComponent:e=>{if(e===`Transition`||e===`transition`)return gR;if(e===`TransitionGroup`||e===`transition-group`)return _R},getNamespace(e,t,n){let r=t?t.ns:n;if(t&&r===2)if(t.tag===`annotation-xml`){if(e===`svg`)return 1;t.props.some(e=>e.type===6&&e.name===`encoding`&&e.value!=null&&(e.value.content===`text/html`||e.value.content===`application/xhtml+xml`))&&(r=0)}else /^m(?:[ions]|text)$/.test(t.tag)&&e!==`mglyph`&&e!==`malignmark`&&(r=0);else t&&r===1&&(t.tag===`foreignObject`||t.tag===`desc`||t.tag===`title`)&&(r=0);if(r===0){if(e===`svg`)return 1;if(e===`math`)return 2}return r}},xR=e=>{e.type===1&&e.props.forEach((t,n)=>{t.type===6&&t.name===`style`&&t.value&&(e.props[n]={type:7,name:`bind`,arg:Z(`style`,!0,t.loc),exp:SR(t.value.content,t.loc),modifiers:[],loc:t.loc})})},SR=(e,t)=>{let n=KN(e);return Z(JSON.stringify(n),!1,t,3)};function CR(e,t){return gF(e,t,void 0)}var wR=(e,t,n)=>{let{exp:r,loc:i}=e;return r||n.onError(CR(54,i)),t.children.length&&(n.onError(CR(55,i)),t.children.length=0),{props:[qP(Z(`innerHTML`,!0,i),r||Z(``,!0))]}},TR=(e,t,n)=>{let{exp:r,loc:i}=e;return r||n.onError(CR(56,i)),t.children.length&&(n.onError(CR(57,i)),t.children.length=0),{props:[qP(Z(`textContent`,!0),r?PI(r,n)>0?r:YP(n.helperString(SP),[r],i):Z(``,!0))]}},ER=(e,t,n)=>{let r=YL(e,t,n);if(!r.props.length||t.tagType===1)return r;e.arg&&n.onError(CR(59,e.arg.loc));let{tag:i}=t,a=n.isCustomElement(i);if(i===`input`||i===`textarea`||i===`select`||a){let o=uR,s=!1;if(i===`input`||a){let r=kF(t,`type`);if(r){if(r.type===7)o=fR;else if(r.value)switch(r.value.content){case`radio`:o=cR;break;case`checkbox`:o=lR;break;case`file`:s=!0,n.onError(CR(60,e.loc));break;default:break}}else jF(t)&&(o=fR)}else i===`select`&&(o=dR);s||(r.needRuntime=n.helper(o))}else n.onError(CR(58,e.loc));return r.props=r.props.filter(e=>!(e.key.type===4&&e.key.content===`modelValue`)),r},DR=TN(`passive,once,capture`),OR=TN(`stop,prevent,self,ctrl,shift,alt,meta,exact,middle`),kR=TN(`left,right`),AR=TN(`onkeyup,onkeydown,onkeypress`),jR=(e,t,n,r)=>{let i=[],a=[],o=[];for(let s=0;s<t.length;s++){let c=t[s].content;c===`native`&&pF(`COMPILER_V_ON_NATIVE`,n,r)||DR(c)?o.push(c):kR(c)?_F(e)?AR(e.content.toLowerCase())?i.push(c):a.push(c):(i.push(c),a.push(c)):OR(c)?a.push(c):i.push(c)}return{keyModifiers:i,nonKeyModifiers:a,eventOptionModifiers:o}},MR=(e,t)=>_F(e)&&e.content.toLowerCase()===`onclick`?Z(t,!0):e.type===4?e:JP([`(`,e,`) === "onClick" ? "${t}" : (`,e,`)`]),NR=(e,t,n)=>UL(e,t,n,t=>{let{modifiers:r}=e;if(!r.length)return t;let{key:i,value:a}=t.props[0],{keyModifiers:o,nonKeyModifiers:s,eventOptionModifiers:c}=jR(i,r,n,e.loc);if(s.includes(`right`)&&(i=MR(i,`onContextmenu`)),s.includes(`middle`)&&(i=MR(i,`onMouseup`)),s.length&&(a=YP(n.helper(pR),[a,JSON.stringify(s)])),o.length&&(!_F(i)||AR(i.content.toLowerCase()))&&(a=YP(n.helper(mR),[a,JSON.stringify(o)])),c.length){let e=c.map(BN).join(``);i=_F(i)?Z(`${i.content}${e}`,!0):JP([`(`,i,`) + "${e}"`])}return{props:[qP(i,a)]}}),PR=(e,t,n)=>{let{exp:r,loc:i}=e;return r||n.onError(CR(62,i)),{props:[],needRuntime:n.helper(hR)}},FR=(e,t)=>{e.type===1&&e.tagType===0&&(e.tag===`script`||e.tag===`style`)&&t.removeNode()},IR=[xR,...[]],LR={cloak:sR,html:wR,text:TR,model:ER,on:NR,show:PR};function RR(e,t={}){return oR(e,AN({},bR,t,{nodeTransforms:[FR,...IR,...t.nodeTransforms||[]],directiveTransforms:AN({},LR,t.directiveTransforms||{}),transformHoist:null}))}var zR=Object.create(null);function BR(e,t){if(!MN(e))if(e.nodeType)e=e.innerHTML;else return DN;let n=HN(e,t),r=zR[n];if(r)return r;if(e[0]===`#`){let t=document.querySelector(e);e=t?t.innerHTML:``}let i=AN({hoistStatic:!0,onError:void 0,onWarn:DN},t);!i.isCustomElement&&typeof customElements<`u`&&(i.isCustomElement=e=>!!customElements.get(e));let{code:a}=RR(e,i),o=Function(`Vue`,a)(vj);return o._rc=!0,zR[n]=o}bA(BR);var VR=l.create(),HR=(e,t)=>`${e.method}:${e.baseURL??t.defaults.baseURL??``}${e.url}`,UR=e=>e.status===204&&e.headers[`precognition-success`]===`true`,WR={},GR={get:(e,t={},n={})=>qR(KR(`get`,e,t,n)),post:(e,t={},n={})=>qR(KR(`post`,e,t,n)),patch:(e,t={},n={})=>qR(KR(`patch`,e,t,n)),put:(e,t={},n={})=>qR(KR(`put`,e,t,n)),delete:(e,t={},n={})=>qR(KR(`delete`,e,t,n)),use(e){return VR=e,GR},axios(){return VR},fingerprintRequestsUsing(e){return HR=e===null?()=>null:e,GR},determineSuccessUsing(e){return UR=e,GR}},KR=(e,t,n,r)=>({url:t,method:e,...r,...[`get`,`delete`].includes(e)?{params:Nv({},n,r?.params)}:{data:Nv({},n,r?.data)}}),qR=(e={})=>{let t=[JR,XR,ZR].reduce((e,t)=>t(e),e);return(t.onBefore??(()=>!0))()===!1?Promise.resolve(null):((t.onStart??(()=>null))(),VR.request(t).then(async e=>{t.precognitive&&QR(e);let n=e.status,r=e;return t.precognitive&&t.onPrecognitionSuccess&&UR(r)&&(r=await Promise.resolve(t.onPrecognitionSuccess(r)??r)),t.onSuccess&&YR(n)&&(r=await Promise.resolve(t.onSuccess(r)??r)),(ez(t,n)??(e=>e))(r)??r},e=>$R(e)?Promise.reject(e):(t.precognitive&&QR(e.response),(ez(t,e.response.status)??((e,t)=>Promise.reject(t)))(e.response,e))).finally(t.onFinish??(()=>null)))},JR=e=>{let t=e.only??e.validate;return{...e,timeout:e.timeout??VR.defaults.timeout??3e4,precognitive:e.precognitive!==!1,fingerprint:e.fingerprint===void 0?HR(e,VR):e.fingerprint,headers:{...e.headers,"Content-Type":tz(e),...e.precognitive===!1?{}:{Precognition:!0},...t?{"Precognition-Validate-Only":Array.from(t).join()}:{}}}},YR=e=>e>=200&&e<300,XR=e=>typeof e.fingerprint==`string`?(WR[e.fingerprint]?.abort(),delete WR[e.fingerprint],e):e,ZR=e=>typeof e.fingerprint!=`string`||e.signal||e.cancelToken||!e.precognitive?e:(WR[e.fingerprint]=new AbortController,{...e,signal:WR[e.fingerprint].signal}),QR=e=>{if(e.headers?.precognition!==`true`)throw Error(`Did not receive a Precognition response. Ensure you have the Precognition middleware in place for the route.`)},$R=e=>!d(e)||typeof e.response?.status!=`number`||o(e),ez=(e,t)=>({401:e.onUnauthorized,403:e.onForbidden,404:e.onNotFound,409:e.onConflict,422:e.onValidationError,423:e.onLocked})[t],tz=e=>e.headers?.[`Content-Type`]??e.headers?.[`Content-type`]??e.headers?.[`content-type`]??(nz(e.data)?`multipart/form-data`:`application/json`),nz=e=>rz(e)||typeof e==`object`&&!!e&&Object.values(e).some(e=>nz(e)),rz=e=>typeof File<`u`&&e instanceof File||e instanceof Blob||typeof FileList<`u`&&e instanceof FileList&&e.length>0,iz=(t,n={})=>{let r={errorsChanged:[],touchedChanged:[],validatingChanged:[],validatedChanged:[]},i=!1,a=!1,s=e=>e===a?[]:(a=e,r.validatingChanged),c=[],l=e=>{let t=[...new Set(e)];return c.length!==t.length||!t.every(e=>c.includes(e))?(c=t,r.validatedChanged):[]},u=()=>c.filter(e=>m[e]===void 0),f=[],p=e=>{let t=[...new Set(e)];return f.length!==t.length||!t.every(e=>f.includes(e))?(f=t,r.touchedChanged):[]},m={},h=e=>{let t=oz(e);return Mv(m,t)?[]:(m=t,r.errorsChanged)},g=e=>{let t={...m};return delete t[sz(e)],h(t)},_=()=>Object.keys(m).length>0,v=1500,y=e=>{v=e,T.cancel(),T=w()},b=n,x=null,S=[],C=null,w=()=>gv(e=>{t({get:(t,n={},r={})=>GR.get(t,O(n),E(r,e,n)),post:(t,n={},r={})=>GR.post(t,O(n),E(r,e,n)),patch:(t,n={},r={})=>GR.patch(t,O(n),E(r,e,n)),put:(t,n={},r={})=>GR.put(t,O(n),E(r,e,n)),delete:(t,n={},r={})=>GR.delete(t,O(n),E(r,e,n))}).catch(e=>o(e)||d(e)&&e.response?.status===422?null:Promise.reject(e))},v,{leading:!0,trailing:!0}),T=w(),E=(t,n,r={})=>{let i={...t,...n},a=Array.from(i.only??i.validate??f);return{...n,...e(t,n),only:a,timeout:i.timeout??5e3,onValidationError:(e,t)=>([...l([...c,...a]),...h(Nv(Bv({...m},a),e.data.errors))].forEach(e=>e()),i.onValidationError?i.onValidationError(e,t):Promise.reject(t)),onSuccess:e=>(l([...c,...a]).forEach(e=>e()),i.onSuccess?i.onSuccess(e):e),onPrecognitionSuccess:e=>([...l([...c,...a]),...h(Bv({...m},a))].forEach(e=>e()),i.onPrecognitionSuccess?i.onPrecognitionSuccess(e):e),onBefore:()=>i.onBeforeValidation&&i.onBeforeValidation({data:r,touched:f},{data:b,touched:S})===!1||(i.onBefore||(()=>!0))()===!1?!1:(C=f,x=r,!0),onStart:()=>{s(!0).forEach(e=>e()),(i.onStart??(()=>null))()},onFinish:()=>{s(!1).forEach(e=>e()),S=C,b=x,C=x=null,(i.onFinish??(()=>null))()}}},D=(e,t,n)=>{if(e===void 0){let e=Array.from(n?.only??n?.validate??[]);p([...f,...e]).forEach(e=>e()),T(n??{});return}if(rz(t)&&!i){console.warn(`Precognition file validation is not active. Call the "validateFiles" function on your form to enable it.`);return}e=sz(e),rh(b,e)!==t&&(p([e,...f]).forEach(e=>e()),T(n??{}))},O=e=>i===!1?cz(e):e,k={touched:()=>f,validate(e,t,n){return typeof e==`object`&&!(`target`in e)&&(n=e,e=t=void 0),D(e,t,n),k},touch(e){let t=Array.isArray(e)?e:[sz(e)];return p([...f,...t]).forEach(e=>e()),k},validating:()=>a,valid:u,errors:()=>m,hasErrors:_,setErrors(e){return h(e).forEach(e=>e()),k},forgetError(e){return g(e).forEach(e=>e()),k},defaults(e){return n=e,b=e,k},reset(...e){if(e.length===0)p([]).forEach(e=>e());else{let t=[...f];e.forEach(e=>{t.includes(e)&&t.splice(t.indexOf(e),1),Hv(b,e,rh(n,e))}),p(t).forEach(e=>e())}return k},setTimeout(e){return y(e),k},on(e,t){return r[e].push(t),k},validateFiles(){return i=!0,k},withoutFileValidation(){return i=!1,k}};return k},az=e=>Object.keys(e).reduce((t,n)=>({...t,[n]:Array.isArray(e[n])?e[n][0]:e[n]}),{}),oz=e=>Object.keys(e).reduce((t,n)=>({...t,[n]:typeof e[n]==`string`?[e[n]]:e[n]}),{}),sz=e=>typeof e==`string`?e:e.target.name,cz=e=>{let t={...e};return Object.keys(t).forEach(e=>{let n=t[e];if(n!==null){if(rz(n)){delete t[e];return}if(Array.isArray(n)){t[e]=Object.values(cz({...n}));return}if(typeof n==`object`){t[e]=cz(t[e]);return}}}),t},lz={created(){if(!this.$options.remember)return;Array.isArray(this.$options.remember)&&(this.$options.remember={data:this.$options.remember}),typeof this.$options.remember==`string`&&(this.$options.remember={data:[this.$options.remember]}),typeof this.$options.remember.data==`string`&&(this.$options.remember={data:[this.$options.remember.data]});let e=this.$options.remember.key instanceof Function?this.$options.remember.key.call(this):this.$options.remember.key,t=Hx.restore(e),n=this.$options.remember.data.filter(e=>!(this[e]!==null&&typeof this[e]==`object`&&this[e].__rememberable===!1)),r=e=>this[e]!==null&&typeof this[e]==`object`&&typeof this[e].__remember==`function`&&typeof this[e].__restore==`function`;n.forEach(i=>{this[i]!==void 0&&t!==void 0&&t[i]!==void 0&&(r(i)?this[i].__restore(t[i]):this[i]=t[i]),this.$watch(i,()=>{Hx.remember(n.reduce((e,t)=>({...e,[t]:T_(r(t)?this[t].__remember():this[t])}),{}),e)},{immediate:!0,deep:!0})})}},uz=null,dz=!1;function fz(e){if(dz)return;uz===null&&(dz=!0,uz=new Set(Object.keys(pz({}))),dz=!1);let t=Object.keys(e).filter(e=>uz.has(e));t.length>0&&console.error(`[Inertia] useForm() data contains field(s) that conflict with form properties: ${t.map(e=>`"${e}"`).join(`, `)}. These fields will be overwritten by form methods/properties. Please rename these fields.`)}function pz(...e){let{rememberKey:t,data:n,precognitionEndpoint:r}=Zb.parseUseFormArguments(...e),i=t?Hx.restore(t):null,a=T_(typeof n==`function`?n():n);fz(a);let o=null,s,c=e=>e,l=null,u=[],d=!1,f=kC({...i?i.data:T_(a),isDirty:!1,errors:i?i.errors:{},hasErrors:!1,processing:!1,progress:null,wasSuccessful:!1,recentlySuccessful:!1,withPrecognition(...e){r=Zb.createWayfinderCallback(...e);let t=this,n=!1,i=iz(e=>{let{method:t,url:n}=r(),i=T_(c(this.data()));return e[t](n,i)},T_(a));l=i,i.on(`validatingChanged`,()=>{t.validating=i.validating()}).on(`validatedChanged`,()=>{t.__valid=i.valid()}).on(`touchedChanged`,()=>{t.__touched=i.touched()}).on(`errorsChanged`,()=>{let e=n?i.errors():az(i.errors());this.errors={},this.setError(e),t.__valid=i.valid()});let o=(e,t)=>(t(e),e);return Object.assign(t,{__touched:[],__valid:[],validating:!1,validator:()=>i,withAllErrors:()=>o(t,()=>n=!0),valid:e=>t.__valid.includes(e),invalid:e=>e in this.errors,setValidationTimeout:e=>o(t,()=>i.setTimeout(e)),validateFiles:()=>o(t,()=>i.validateFiles()),withoutFileValidation:()=>o(t,()=>i.withoutFileValidation()),touch:(e,...n)=>(Array.isArray(e)?i.touch(e):typeof e==`string`?i.touch([e,...n]):i.touch(e),t),touched:e=>typeof e==`string`?t.__touched.includes(e):t.__touched.length>0,validate:(e,n)=>{if(typeof e==`object`&&!(`target`in e)&&(n=e,e=void 0),e===void 0)i.validate(n);else{let t=sz(e),r=c(this.data());i.validate(t,rh(r,t),n)}return t},setErrors:e=>o(t,()=>this.setError(e)),forgetError:e=>o(t,()=>this.clearErrors(sz(e)))}),t},data(){return Object.keys(a).reduce((e,t)=>Hv(e,t,rh(this,t)),{})},transform(e){return c=e,this},defaults(e,t){if(typeof n==`function`)throw Error("You cannot call `defaults()` when using a function to define your form data.");return d=!0,e===void 0?(a=T_(this.data()),this.isDirty=!1):a=typeof e==`string`?Hv(T_(a),e,t):Object.assign({},T_(a),e),l?.defaults(a),this},reset(...e){let t=T_(typeof n==`function`?n():a),r=T_(t);return e.length===0?(a=r,Object.assign(this,t)):e.filter(e=>Av(r,e)).forEach(e=>{Hv(a,e,rh(r,e)),Hv(this,e,rh(t,e))}),l?.reset(...e),this},setError(e,t){let n=typeof e==`string`?{[e]:t}:e;return Object.assign(this.errors,n),this.hasErrors=Object.keys(this.errors).length>0,l?.setErrors(n),this},clearErrors(...e){return this.errors=Object.keys(this.errors).reduce((t,n)=>({...t,...e.length>0&&!e.includes(n)?{[n]:this.errors[n]}:{}}),{}),this.hasErrors=Object.keys(this.errors).length>0,l&&(e.length===0?l.setErrors({}):e.forEach(l.forgetError)),this},resetAndClearErrors(...e){return this.reset(...e),this.clearErrors(...e),this},submit(...e){let{method:t,url:n,options:i}=Zb.parseSubmitArguments(e,r);d=!1;let l={...i,onCancelToken:e=>{if(o=e,i.onCancelToken)return i.onCancelToken(e)},onBefore:e=>{if(this.wasSuccessful=!1,this.recentlySuccessful=!1,clearTimeout(s),i.onBefore)return i.onBefore(e)},onStart:e=>{if(this.processing=!0,i.onStart)return i.onStart(e)},onProgress:e=>{if(this.progress=e??null,i.onProgress)return i.onProgress(e)},onSuccess:async e=>{this.processing=!1,this.progress=null,this.clearErrors(),this.wasSuccessful=!0,this.recentlySuccessful=!0,s=setTimeout(()=>this.recentlySuccessful=!1,Az.get(`form.recentlySuccessfulDuration`));let t=i.onSuccess?await i.onSuccess(e):null;return d||(a=T_(this.data()),this.isDirty=!1),t},onError:e=>{if(this.processing=!1,this.progress=null,this.clearErrors().setError(e),i.onError)return i.onError(e)},onCancel:()=>{if(this.processing=!1,this.progress=null,i.onCancel)return i.onCancel()},onFinish:e=>{if(this.processing=!1,this.progress=null,o=null,i.onFinish)return i.onFinish(e)}},u=c(this.data());t===`delete`?Hx.delete(n,{...l,data:u}):Hx[t](n,u,l)},get(e,t){this.submit(`get`,e,t)},post(e,t){this.submit(`post`,e,t)},put(e,t){this.submit(`put`,e,t)},patch(e,t){this.submit(`patch`,e,t)},delete(e,t){this.submit(`delete`,e,t)},cancel(){o&&o.cancel()},dontRemember(...e){return u=e,this},__rememberable:t===null,__remember(){let e=this.data();if(u.length>0){let t={...e};return u.forEach(e=>delete t[e]),{data:t,errors:this.errors}}return{data:e,errors:this.errors}},__restore(e){Object.assign(this,e.data),this.setError(e.errors)}});return eE(f,e=>{f.isDirty=!Mv(f.data(),a);let n=Hx.restore(t),r=T_(e.__remember());t&&!Mv(n,r)&&Hx.remember(r,t)},{immediate:!0,deep:!0}),r?f.withPrecognition(r):f}var mz=HC(void 0),hz=HC(),gz=UC(null),_z=HC(void 0),vz,yz=ME({name:`Inertia`,props:{initialPage:{type:Object,required:!0},initialComponent:{type:Object,required:!1},resolveComponent:{type:Function,required:!1},titleCallback:{type:Function,required:!1,default:e=>e},onHeadUpdate:{type:Function,required:!1,default:()=>()=>{}}},setup({initialPage:e,initialComponent:t,resolveComponent:n,titleCallback:r,onHeadUpdate:i}){mz.value=t?RC(t):void 0,hz.value={...e,flash:e.flash??{}},_z.value=void 0;let a=typeof window>`u`;return vz=ax(a,r||(e=>e),i||(()=>{})),a||(Hx.init({initialPage:e,resolveComponent:n,swapComponent:async e=>{mz.value=RC(e.component),hz.value=e.page,_z.value=e.preserveState?_z.value:Date.now()},onFlash:e=>{hz.value={...hz.value,flash:e}}}),Hx.on(`navigate`,()=>vz.forceUpdate())),()=>{if(mz.value){mz.value.inheritAttrs=!!mz.value.inheritAttrs;let e=OA(mz.value,{...hz.value.props,key:_z.value});return gz.value&&=(mz.value.layout=gz.value,null),mz.value.layout?typeof mz.value.layout==`function`?mz.value.layout(OA,e):(Array.isArray(mz.value.layout)?mz.value.layout:[mz.value.layout]).concat(e).reverse().reduce((e,t)=>(t.inheritAttrs=!!t.inheritAttrs,OA(t,{...hz.value.props},()=>e))):e}}}}),bz={install(e){Hx.form=pz,Object.defineProperty(e.config.globalProperties,`$inertia`,{get:()=>Hx}),Object.defineProperty(e.config.globalProperties,`$page`,{get:()=>hz.value}),Object.defineProperty(e.config.globalProperties,`$headManager`,{get:()=>vz}),e.mixin(lz)}};function xz(){return kC({props:X(()=>hz.value?.props),url:X(()=>hz.value?.url),component:X(()=>hz.value?.component),version:X(()=>hz.value?.version),clearHistory:X(()=>hz.value?.clearHistory),deferredProps:X(()=>hz.value?.deferredProps),mergeProps:X(()=>hz.value?.mergeProps),prependProps:X(()=>hz.value?.prependProps),deepMergeProps:X(()=>hz.value?.deepMergeProps),matchPropsOn:X(()=>hz.value?.matchPropsOn),rememberedState:X(()=>hz.value?.rememberedState),encryptHistory:X(()=>hz.value?.encryptHistory),flash:X(()=>hz.value?.flash)})}async function Sz({id:e=`app`,resolve:t,setup:n,title:r,progress:i={},page:a,render:o,defaults:s={}}){Az.replace(s);let c=typeof window>`u`,l=Az.get(`future.useScriptElementForInitialPage`),u=a||mb(e,l),d=e=>Promise.resolve(t(e)).then(e=>e.default||e),f=[],p=await Promise.all([d(u.component),Hx.decryptHistory().catch(()=>{})]).then(([t])=>{let i={initialPage:u,initialComponent:t,resolveComponent:d,titleCallback:r};return n(c?{el:null,App:yz,props:{...i,onHeadUpdate:e=>f=e},plugin:bz}:{el:document.getElementById(e),App:yz,props:i,plugin:bz})});if(!c&&i&&Px(i),c&&o){let t=()=>l?[OA(`script`,{"data-page":e,type:`application/json`,innerHTML:JSON.stringify(u).replace(/\//g,`\\/`)}),OA(`div`,{id:e,innerHTML:p?o(p):``})]:OA(`div`,{id:e,"data-page":JSON.stringify(u),innerHTML:p?o(p):``}),n=await o(bN({render:()=>t()}));return{head:f,body:n}}}var Cz=ME({name:`Deferred`,props:{data:{type:[String,Array],required:!0}},render(){let e=Array.isArray(this.$props.data)?this.$props.data:[this.$props.data];if(!this.$slots.fallback)throw Error("`<Deferred>` requires a `<template #fallback>` slot");return e.every(e=>this.$page.props[e]!==void 0)?this.$slots.default?.():this.$slots.fallback()}}),wz=()=>void 0,Tz=Symbol(`InertiaFormContext`),Ez=ME({name:`Form`,slots:Object,props:{action:{type:[String,Object],default:``},method:{type:String,default:`get`},headers:{type:Object,default:()=>({})},queryStringArrayFormat:{type:String,default:`brackets`},errorBag:{type:[String,null],default:null},showProgress:{type:Boolean,default:!0},transform:{type:Function,default:e=>e},options:{type:Object,default:()=>({})},resetOnError:{type:[Boolean,Array],default:!1},resetOnSuccess:{type:[Boolean,Array],default:!1},setDefaultsOnSuccess:{type:Boolean,default:!1},onCancelToken:{type:Function,default:wz},onBefore:{type:Function,default:wz},onStart:{type:Function,default:wz},onProgress:{type:Function,default:wz},onFinish:{type:Function,default:wz},onCancel:{type:Function,default:wz},onSuccess:{type:Function,default:wz},onError:{type:Function,default:wz},onSubmitComplete:{type:Function,default:wz},disableWhileProcessing:{type:Boolean,default:!1},invalidateCacheTags:{type:[String,Array],default:()=>[]},validateFiles:{type:Boolean,default:!1},validationTimeout:{type:Number,default:1500},withAllErrors:{type:Boolean,default:!1}},setup(e,{slots:t,attrs:n,expose:r}){let i=()=>{let[t,n]=m();return e.transform(n)},a=pz({}).withPrecognition(()=>s.value,()=>m()[0]).transform(i).setValidationTimeout(e.validationTimeout);e.validateFiles&&a.validateFiles(),e.withAllErrors&&a.withAllErrors();let o=HC(),s=X(()=>jb(e.action)?e.action.method:e.method.toLowerCase()),c=HC(!1),l=HC(new FormData),u=e=>{e.type===`reset`&&e.detail?.[Fx]&&e.preventDefault(),c.value=e.type===`reset`?!1:!Mv(p(),rx(l.value))},d=[`input`,`change`,`reset`];vD(()=>{l.value=f(),a.defaults(p()),d.forEach(e=>o.value.addEventListener(e,u))}),eE(()=>e.validateFiles,e=>e?a.validateFiles():a.withoutFileValidation()),eE(()=>e.validationTimeout,e=>a.setValidationTimeout(e)),xD(()=>d.forEach(e=>o.value?.removeEventListener(e,u)));let f=e=>new FormData(o.value,e),p=e=>rx(f(e)),m=t=>Eb(s.value,jb(e.action)?e.action.url:e.action,p(t),e.queryStringArrayFormat),h=t=>{let[n,r]=m(t);if(t?.getAttribute(`formtarget`)===`_blank`&&s.value===`get`){window.open(n,`_blank`);return}let o=e=>{e&&(e===!0?g():e.length>0&&g(...e))},c={headers:e.headers,queryStringArrayFormat:e.queryStringArrayFormat,errorBag:e.errorBag,showProgress:e.showProgress,invalidateCacheTags:e.invalidateCacheTags,onCancelToken:e.onCancelToken,onBefore:e.onBefore,onStart:e.onStart,onProgress:e.onProgress,onFinish:e.onFinish,onCancel:e.onCancel,onSuccess:(...t)=>{e.onSuccess?.(...t),e.onSubmitComplete?.(b),o(e.resetOnSuccess),e.setDefaultsOnSuccess===!0&&y()},onError:(...t)=>{e.onError?.(...t),o(e.resetOnError)},...e.options};a.transform(()=>e.transform(r)).submit(s.value,n,c),a.transform(i)},g=(...e)=>{Vx(o.value,l.value,e),a.reset(...e)},_=(...e)=>{a.clearErrors(...e)},v=(...e)=>{_(...e),g(...e)},y=()=>{l.value=f(),c.value=!1},b={get errors(){return a.errors},get hasErrors(){return a.hasErrors},get processing(){return a.processing},get progress(){return a.progress},get wasSuccessful(){return a.wasSuccessful},get recentlySuccessful(){return a.recentlySuccessful},get validating(){return a.validating},clearErrors:_,resetAndClearErrors:v,setError:(e,t)=>a.setError(typeof e==`string`?{[e]:t}:e),get isDirty(){return c.value},reset:g,submit:h,defaults:y,getData:p,getFormData:f,touch:a.touch,valid:a.valid,invalid:a.invalid,touched:a.touched,validate:(t,n)=>a.validate(...Zb.mergeHeadersForValidation(t,n,e.headers)),validator:()=>a.validator()};return r(b),KT(Tz,b),()=>OA(`form`,{...n,ref:o,action:jb(e.action)?e.action.url:e.action,method:s.value,onSubmit:e=>{e.preventDefault(),h(e.submitter)},inert:e.disableWhileProcessing&&a.processing},t.default?t.default(b):[])}}),Dz=ME({props:{title:{type:String,required:!1}},data(){return{provider:this.$headManager.createProvider()}},beforeUnmount(){this.provider.disconnect()},methods:{isUnaryTag(e){return typeof e.type==`string`&&[`area`,`base`,`br`,`col`,`embed`,`hr`,`img`,`input`,`keygen`,`link`,`meta`,`param`,`source`,`track`,`wbr`].indexOf(e.type)>-1},renderTagStart(e){e.props=e.props||{},e.props[this.provider.preferredAttribute()]=e.props[`head-key`]===void 0?``:e.props[`head-key`];let t=Object.keys(e.props).reduce((t,n)=>{let r=String(e.props[n]);return[`key`,`head-key`].includes(n)?t:r===``?t+` ${n}`:t+` ${n}="${Dv(r)}"`},``);return`<${String(e.type)}${t}>`},renderTagChildren(e){let{children:t}=e;return typeof t==`string`?t:Array.isArray(t)?t.reduce((e,t)=>e+this.renderTag(t),``):``},isFunctionNode(e){return typeof e.type==`function`},isComponentNode(e){return typeof e.type==`object`},isCommentNode(e){return/(comment|cmt)/i.test(e.type.toString())},isFragmentNode(e){return/(fragment|fgt|symbol\(\))/i.test(e.type.toString())},isTextNode(e){return/(text|txt)/i.test(e.type.toString())},renderTag(e){if(this.isTextNode(e))return String(e.children);if(this.isFragmentNode(e)||this.isCommentNode(e))return``;let t=this.renderTagStart(e);return e.children&&(t+=this.renderTagChildren(e)),this.isUnaryTag(e)||(t+=`</${String(e.type)}>`),t},addTitleElement(e){return this.title&&!e.find(e=>e.startsWith(`<title`))&&e.push(`<title ${this.provider.preferredAttribute()}>${this.title}</title>`),e},renderNodes(e){let t=e.flatMap(e=>this.resolveNode(e)).map(e=>this.renderTag(e)).filter(e=>e);return this.addTitleElement(t)},resolveNode(e){return this.isFunctionNode(e)?this.resolveNode(e.type()):this.isComponentNode(e)?(console.warn(`Using components in the <Head> component is not supported.`),[]):this.isTextNode(e)&&e.children?e:this.isFragmentNode(e)&&e.children?e.children.flatMap(e=>this.resolveNode(e)):this.isCommentNode(e)?[]:e}},render(){this.provider.update(this.renderNodes(this.$slots.default?this.$slots.default():[]))}}),Oz=()=>{},kz=ME({name:`Link`,props:{as:{type:[String,Object],default:`a`},data:{type:Object,default:()=>({})},href:{type:[String,Object],default:``},method:{type:String,default:`get`},replace:{type:Boolean,default:!1},preserveScroll:{type:[Boolean,String,Function],default:!1},preserveState:{type:[Boolean,String,Function],default:null},preserveUrl:{type:Boolean,default:!1},only:{type:Array,default:()=>[]},except:{type:Array,default:()=>[]},headers:{type:Object,default:()=>({})},queryStringArrayFormat:{type:String,default:`brackets`},async:{type:Boolean,default:!1},prefetch:{type:[Boolean,String,Array],default:!1},cacheFor:{type:[Number,String,Array],default:0},onStart:{type:Function,default:Oz},onProgress:{type:Function,default:Oz},onFinish:{type:Function,default:Oz},onBefore:{type:Function,default:Oz},onCancel:{type:Function,default:Oz},onSuccess:{type:Function,default:Oz},onError:{type:Function,default:Oz},onCancelToken:{type:Function,default:Oz},onPrefetching:{type:Function,default:Oz},onPrefetched:{type:Function,default:Oz},cacheTags:{type:[String,Array],default:()=>[]},viewTransition:{type:[Boolean,Object],default:!1}},setup(e,{slots:t,attrs:n}){let r=HC(0),i=HC(),a=X(()=>e.prefetch===!0?[`hover`]:e.prefetch===!1?[]:Array.isArray(e.prefetch)?e.prefetch:[e.prefetch]),o=X(()=>e.cacheFor===0?a.value.length===1&&a.value[0]===`click`?0:Az.get(`prefetch.cacheFor`):e.cacheFor);vD(()=>{a.value.includes(`mount`)&&h()}),SD(()=>{clearTimeout(i.value)});let s=X(()=>jb(e.href)?e.href.method:(e.method??`get`).toLowerCase()),c=X(()=>typeof e.as!=`string`||e.as.toLowerCase()!==`a`?e.as:s.value===`get`?e.as.toLowerCase():`button`),l=X(()=>Eb(s.value,jb(e.href)?e.href.url:e.href,e.data||{},e.queryStringArrayFormat)),u=X(()=>l.value[0]),d=X(()=>l.value[1]),f=X(()=>c.value===`button`?{type:`button`}:c.value===`a`||typeof c.value!=`string`?{href:u.value}:{}),p=X(()=>({data:d.value,method:s.value,replace:e.replace,preserveScroll:e.preserveScroll,preserveState:e.preserveState??s.value!==`get`,preserveUrl:e.preserveUrl,only:e.only,except:e.except,headers:e.headers,async:e.async})),m=X(()=>({...p.value,viewTransition:e.viewTransition,onCancelToken:e.onCancelToken,onBefore:e.onBefore,onStart:t=>{r.value++,e.onStart?.(t)},onProgress:e.onProgress,onFinish:t=>{r.value--,e.onFinish?.(t)},onCancel:e.onCancel,onSuccess:e.onSuccess,onError:e.onError})),h=()=>{Hx.prefetch(u.value,{...p.value,onPrefetching:e.onPrefetching,onPrefetched:e.onPrefetched},{cacheFor:o.value,cacheTags:e.cacheTags})},g={onClick:e=>{sx(e)&&(e.preventDefault(),Hx.visit(u.value,m.value))}},_={onMouseenter:()=>{i.value=setTimeout(()=>{h()},Az.get(`prefetch.hoverDelay`))},onMouseleave:()=>{clearTimeout(i.value)},onClick:g.onClick},v={onMousedown:e=>{sx(e)&&(e.preventDefault(),h())},onKeydown:e=>{cx(e)&&(e.preventDefault(),h())},onMouseup:e=>{sx(e)&&(e.preventDefault(),Hx.visit(u.value,m.value))},onKeyup:e=>{cx(e)&&(e.preventDefault(),Hx.visit(u.value,m.value))},onClick:e=>{sx(e)&&e.preventDefault()}};return()=>OA(c.value,{...n,...f.value,"data-loading":r.value>0?``:void 0,...a.value.includes(`hover`)?_:a.value.includes(`click`)?v:g},t)}}),Az=Fy.extend({}),jz=(e,t)=>{let n=e.__vccOpts||e;for(let[e,r]of t)n[e]=r;return n};export{aT as $,JT as A,LD as B,ID as C,cA as D,ME as E,vD as F,FE as G,NE as H,SD as I,UT as J,eE as K,Nk as L,rO as M,nA as N,Jk as O,TT as P,Zw as Q,KT as R,Rk as S,Kk as T,TO as U,jD as V,QD as W,nT as X,WT as Y,rT as Z,Yk as _,Dz as a,UC as at,zk as b,xz as c,KC as ct,eM as d,yd as dt,pS as et,rN as f,ud as ft,mE as g,j as gt,Dk as h,nd as ht,Ez as i,HC as it,qT as j,OA as k,Mj as l,qC as lt,lN as m,ld as mt,Sz as n,mS as nt,kz as o,K as ot,YM as p,cd as pt,ZT as q,Cz as r,kC as rt,pz as s,JC as st,jz as t,VC as tt,yN as u,Hx as ut,X as v,Xk as w,Qk as x,Gk as y,FD as z};