const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["./bg-BG-BqN8Ofy5.js","./bg-3ZZjfJhO.js","./cs-CZ-SN-3IU_U.js","./cs-BmsyPq5Q.js","./de-DE-oKn4vR-b.js","./de-CVq70aXQ.js","./en-AU-CmzwKX8Q.js","./en-CsSts4wN.js","./en-GB-DjRN3mtz.js","./en-US-TWZ1e6QA.js","./es-ES-BvEsTaKf.js","./es-CxsbU-E3.js","./fr-FR-DB0f76VO.js","./fr-BwVS4ltX.js","./fr-BE-CbimjE5l.js","./hu-HU-pJb0gbQP.js","./hu-BETqMCB_.js","./it-IT-Dv9Syq6Q.js","./it-Dw2AvXxt.js","./nl-BE-CgXEiADS.js","./nl-8ESsQqJ_.js","./nl-NL-BGQpRlMS.js","./pl-PL-CBME1Vm2.js","./pl-BNJOnG4_.js","./ro-RO-CnXz0UKw.js","./ro-CVZKR3R4.js","./ru-RU-CIZ4zq9n.js","./ru-B8aGAE0G.js","./sk-SK-DqVU7k95.js","./sk-GonuccUi.js","./tr-TR-DSpBczmi.js","./tr-D9qhUS6G.js","./uk-UA-DsV3eA-4.js","./uk-ChYu7zY6.js","./bg-BG-BsPLW1O9.js","./bg-CM8RZOwv.js","./cs-CZ-MlqpnCaw.js","./cs-CQUHD0xt.js","./de-DE-DQp4Xmef.js","./de-Cu7ADEFF.js","./en-AU-DCRQjXcp.js","./en-BGmLPvL6.js","./en-GB-BRKc3BKy.js","./en-US-DUAtPGkW.js","./es-ES-E0fH-9yE.js","./es-CKo5g7uN.js","./fr-FR-C1CdXYYE.js","./fr-CRxVQ9kP.js","./fr-BE-BAVunqTE.js","./hu-HU-Bo4UgLra.js","./hu-INHSmlNT.js","./id-ID-B20vUlBp.js","./id-CzI_kQue.js","./it-IT-BXZcSQix.js","./it-BIZrUvhL.js","./nl-BE-nYDTTvkz.js","./nl-DK7LMiyy.js","./nl-NL-B6zhjyes.js","./pl-PL-n2UAPw-E.js","./pl-M6DBBIZW.js","./ro-RO-DXX2qg0Y.js","./ro-z1glPc5q.js","./ru-RU-p-TAxJZZ.js","./ru-eLTsJp1p.js","./sk-SK-D3Ih9zlz.js","./sk-BfoWgHvN.js","./uk-UA-N3c9Eb1b.js","./uk-DrK5PsW-.js"])))=>i.map(i=>d[i]);
import{n as e}from"./rolldown-runtime-DXc-PV0M.js";import{a as t,c as n,i as r,n as i,o as a,r as o}from"./nav-item-QIAJgajc-Di01_UBC.js";import{a as s,c,d as l,i as u,l as d,n as f,o as p,s as m,u as h}from"./progress.ts-BKFfzlcu.js";import{t as g}from"./decorate-DuuG6SzG-DZnegANy.js";import{a as _,c as v,d as y,f as b,i as x,n as S,r as C,t as w}from"./lit-DVoFBtjT.js";import{a as T,i as E,o as D,r as O,t as ee}from"./decorators-7Jm2fFRw.js";import"./nav-list.ts-DYPVfMBK.js";var te=``,ne=``,re=``;function ie(e){te=e}function ae(e=``){if(!te){let e=document.querySelector(`[data-webawesome]`);if(e?.hasAttribute(`data-webawesome`)){let t=new URL(e.getAttribute(`data-webawesome`)??``,window.location.href).pathname;ie(t)}else{let e=[...document.getElementsByTagName(`script`)].find(e=>e.src.endsWith(`webawesome.js`)||e.src.endsWith(`webawesome.loader.js`)||e.src.endsWith(`webawesome.ssr-loader.js`));e&&ie(String(e.getAttribute(`src`)).split(`/`).slice(0,-1).join(`/`))}}return te.replace(/\/$/,``)+(e?`/${e.replace(/^\//,``)}`:``)}function oe(){return ne.replace(/\/$/,``)}function se(e){re=e}function ce(){if(!re){let e=document.querySelector(`[data-fa-kit-code]`);e&&se(e.getAttribute(`data-fa-kit-code`)||``)}return re}var le=`modulepreload`,ue=function(e,t){return new URL(e,t).href},de={},k=function(e,t,n){let r=Promise.resolve();if(t&&t.length>0){let e=document.getElementsByTagName(`link`),i=document.querySelector(`meta[property=csp-nonce]`),a=i?.nonce||i?.getAttribute(`nonce`);function o(e){return Promise.all(e.map(e=>Promise.resolve(e).then(e=>({status:`fulfilled`,value:e}),e=>({status:`rejected`,reason:e}))))}r=o(t.map(t=>{if(t=ue(t,n),t in de)return;de[t]=!0;let r=t.endsWith(`.css`),i=r?`[rel="stylesheet"]`:``;if(n)for(let n=e.length-1;n>=0;n--){let i=e[n];if(i.href===t&&(!r||i.rel===`stylesheet`))return}else if(document.querySelector(`link[href="${t}"]${i}`))return;let o=document.createElement(`link`);if(o.rel=r?`stylesheet`:le,r||(o.as=`script`),o.crossOrigin=``,o.href=t,a&&o.setAttribute(`nonce`,a),document.head.appendChild(o),r)return new Promise((e,n)=>{o.addEventListener(`load`,e),o.addEventListener(`error`,()=>n(Error(`Unable to preload CSS for ${t}`)))})}))}function i(e){let t=new Event(`vite:preloadError`,{cancelable:!0});if(t.payload=e,window.dispatchEvent(t),!t.defaultPrevented)throw e}return r.then(t=>{for(let e of t||[])e.status===`rejected`&&i(e.reason);return e().catch(i)})};new MutationObserver(e=>{for(let{addedNodes:t}of e)for(let e of t)e.nodeType===Node.ELEMENT_NODE&&fe(e)});async function fe(e){let t=e instanceof Element?e.tagName.toLowerCase():``,n=t?.startsWith(`wa-`),r=[...e.querySelectorAll(`:not(:defined)`)].map(e=>e.tagName.toLowerCase()).filter(e=>e.startsWith(`wa-`));n&&!customElements.get(t)&&r.push(t);let i=e.querySelectorAll(`[data-wa-preload]`),a=e instanceof Element&&e.hasAttribute(`data-wa-preload`)?[e,...i]:i;for(let e of a)r.push(...e.getAttribute(`data-wa-preload`).split(/\s+/).filter(e=>e.startsWith(`wa-`)));let o=[...new Set(r)],s=await Promise.allSettled(o.map(e=>pe(e)));for(let e of s)e.status===`rejected`&&console.warn(e.reason);await new Promise(requestAnimationFrame),e.dispatchEvent(new CustomEvent(`wa-discovery-complete`,{bubbles:!1,cancelable:!1,composed:!0}))}function pe(e){if(customElements.get(e))return Promise.resolve();let t=e.replace(/^wa-/i,``),n=ae(`components/${t}/${t}.js`);return new Promise((t,r)=>{k(()=>import(n).then(()=>t()),[],import.meta.url).catch(()=>r(Error(`Unable to autoload <${e}> from ${n}`)))})}var me=new Set,he=new Map,ge,_e=`ltr`,ve=`en`,ye=typeof MutationObserver<`u`&&typeof document<`u`&&document.documentElement!==void 0;if(ye){let e=new MutationObserver(xe);_e=document.documentElement.dir||`ltr`,ve=document.documentElement.lang||navigator.language,e.observe(document.documentElement,{attributes:!0,attributeFilter:[`dir`,`lang`]})}function be(...e){e.map(e=>{let t=e.$code.toLowerCase();he.has(t)?he.set(t,Object.assign(Object.assign({},he.get(t)),e)):he.set(t,e),ge||=e}),xe()}function xe(){ye&&(_e=document.documentElement.dir||`ltr`,ve=document.documentElement.lang||navigator.language),[...me.keys()].map(e=>{typeof e.requestUpdate==`function`&&e.requestUpdate()})}var Se=class{constructor(e){this.host=e,this.host.addController(this)}hostConnected(){me.add(this.host)}hostDisconnected(){me.delete(this.host)}dir(){return`${this.host.dir||_e}`.toLowerCase()}lang(){return`${this.host.lang||ve}`.toLowerCase()}getTranslationData(e){let t;try{t=new Intl.Locale(e.replace(/_/g,`-`))}catch{return{locale:void 0,language:``,region:``,primary:void 0,secondary:void 0}}let n=t.language.toLowerCase(),r=t.region?.toLowerCase()??``,i=he.get(`${n}-${r}`),a=he.get(n);return{locale:t,language:n,region:r,primary:i,secondary:a}}exists(e,t){let{primary:n,secondary:r}=this.getTranslationData(t.lang??this.lang());return t=Object.assign({includeFallback:!1},t),!!(n&&n[e]||r&&r[e]||t.includeFallback&&ge&&ge[e])}term(e,...t){let{primary:n,secondary:r}=this.getTranslationData(this.lang()),i;if(n&&n[e])i=n[e];else if(r&&r[e])i=r[e];else if(ge&&ge[e])i=ge[e];else return console.error(`No translation found for: ${String(e)}`),String(e);return typeof i==`function`?i(...t):i}date(e,t){return e=new Date(e),new Intl.DateTimeFormat(this.lang(),t).format(e)}number(e,t){return e=Number(e),isNaN(e)?``:new Intl.NumberFormat(this.lang(),t).format(e)}relativeTime(e,t,n){return new Intl.RelativeTimeFormat(this.lang(),n).format(e,t)}},Ce={$code:`en`,$name:`English`,$dir:`ltr`,carousel:`Carousel`,captions:`Captions`,clearEntry:`Clear entry`,close:`Close`,createOption:e=>`Create "${e}"`,copied:`Copied`,copy:`Copy`,currentValue:`Current value`,dropFileHere:`Drop file here or click to browse`,decrement:`Decrement`,dropFilesHere:`Drop files here or click to browse`,error:`Error`,enterFullscreen:`Enter fullscreen`,exitFullscreen:`Exit fullscreen`,goToSlide:(e,t)=>`Go to slide ${e} of ${t}`,hidePassword:`Hide password`,increment:`Increment`,loading:`Loading`,moreOptions:`More Options`,mute:`Mute`,nextSlide:`Next slide`,nextVideo:`Next Video`,numCharacters:e=>e===1?`1 character`:`${e} characters`,numCharactersRemaining:e=>e===1?`1 character remaining`:`${e} characters remaining`,numOptionsSelected:e=>e===0?`No options selected`:e===1?`1 option selected`:`${e} options selected`,pause:`Pause`,pauseAnimation:`Pause animation`,pictureInPicture:`Picture in picture`,play:`Play`,playbackSpeed:`Playback speed`,playlist:`Playlist`,playAnimation:`Play animation`,previousSlide:`Previous slide`,previousVideo:`Previous video`,progress:`Progress`,remove:`Remove`,resize:`Resize`,scrollableRegion:`Scrollable region`,scrollToEnd:`Scroll to end`,scrollToStart:`Scroll to start`,selectAColorFromTheScreen:`Select a color from the screen`,showPassword:`Show password`,slideNum:e=>`Slide ${e}`,toggleColorFormat:`Toggle color format`,seek:`Seek`,seekProgress:(e,t)=>`${e} of ${t}`,currentlyPlaying:`currently playing`,unmute:`Unmute`,videoPlayer:`Video player`,volume:`Volume`,zoomIn:`Zoom in`,zoomOut:`Zoom out`};be(Ce);var we=Ce,Te=class extends Se{};be(we);var Ee=`7.2.0`;function De(e,t,n){let r=`solid`;return t===`chisel`&&(r=`chisel-regular`),t===`etch`&&(r=`etch-solid`),t===`graphite`&&(r=`graphite-thin`),t===`jelly`&&(r=`jelly-regular`,n===`duo-regular`&&(r=`jelly-duo-regular`),n===`fill-regular`&&(r=`jelly-fill-regular`)),t===`jelly-duo`&&(r=`jelly-duo-regular`),t===`jelly-fill`&&(r=`jelly-fill-regular`),t===`notdog`&&(n===`solid`&&(r=`notdog-solid`),n===`duo-solid`&&(r=`notdog-duo-solid`)),t===`notdog-duo`&&(r=`notdog-duo-solid`),t===`slab`&&((n===`solid`||n===`regular`)&&(r=`slab-regular`),n===`press-regular`&&(r=`slab-press-regular`)),t===`slab-press`&&(r=`slab-press-regular`),t===`thumbprint`&&(r=`thumbprint-light`),t===`utility`&&(r=`utility-semibold`),t===`utility-duo`&&(r=`utility-duo-semibold`),t===`utility-fill`&&(r=`utility-fill-semibold`),t===`whiteboard`&&(r=`whiteboard-semibold`),t===`classic`&&(n===`thin`&&(r=`thin`),n===`light`&&(r=`light`),n===`regular`&&(r=`regular`),n===`solid`&&(r=`solid`)),t===`duotone`&&(n===`thin`&&(r=`duotone-thin`),n===`light`&&(r=`duotone-light`),n===`regular`&&(r=`duotone-regular`),n===`solid`&&(r=`duotone`)),t===`sharp`&&(n===`thin`&&(r=`sharp-thin`),n===`light`&&(r=`sharp-light`),n===`regular`&&(r=`sharp-regular`),n===`solid`&&(r=`sharp-solid`)),t===`sharp-duotone`&&(n===`thin`&&(r=`sharp-duotone-thin`),n===`light`&&(r=`sharp-duotone-light`),n===`regular`&&(r=`sharp-duotone-regular`),n===`solid`&&(r=`sharp-duotone-solid`)),t===`brands`&&(r=`brands`),r}function Oe(e,t,n){let r=De(e,t,n),i=oe();if(i)return`${i}/${r}/${e}.svg`;let a=ce();return a.length>0?`https://ka-p.fontawesome.com/releases/v${Ee}/svgs/${r}/${e}.svg?token=${encodeURIComponent(a)}`:`https://ka-f.fontawesome.com/releases/v${Ee}/svgs/${r}/${e}.svg`}var ke={name:`default`,resolver:(e,t=`classic`,n=`solid`)=>Oe(e,t,n),mutator:(e,t)=>{if(t?.family&&!e.hasAttribute(`data-duotone-initialized`)){let{family:n,variant:r}=t;if(n===`duotone`||n===`sharp-duotone`||n===`notdog-duo`||n===`notdog`&&r===`duo-solid`||n===`jelly-duo`||n===`jelly`&&r===`duo-regular`||n===`utility-duo`||n===`thumbprint`){let n=[...e.querySelectorAll(`path`)],r=n.find(e=>!e.hasAttribute(`opacity`)),i=n.find(e=>e.hasAttribute(`opacity`));if(!r||!i)return;if(r.setAttribute(`data-duotone-primary`,``),i.setAttribute(`data-duotone-secondary`,``),t.swapOpacity&&r&&i){let e=i.getAttribute(`opacity`)||`0.4`;r.style.setProperty(`--path-opacity`,e),i.style.setProperty(`--path-opacity`,`1`)}e.setAttribute(`data-duotone-initialized`,``)}}}};function Ae(e){return`data:image/svg+xml,${encodeURIComponent(e)}`}var je={solid:{backward:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M236.3 107.1C247.9 96 265 92.9 279.7 99.2C294.4 105.5 304 120 304 136L304 272.3L476.3 107.2C487.9 96 505 92.9 519.7 99.2C534.4 105.5 544 120 544 136L544 504C544 520 534.4 534.5 519.7 540.8C505 547.1 487.9 544 476.3 532.9L304 367.7L304 504C304 520 294.4 534.5 279.7 540.8C265 547.1 247.9 544 236.3 532.9L44.3 348.9C36.5 341.3 32 330.9 32 320C32 309.1 36.5 298.7 44.3 291.1L236.3 107.1z"/></svg>`,"backward-step":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M491 100.8C478.1 93.8 462.3 94.5 450 102.6L192 272.1L192 128C192 110.3 177.7 96 160 96C142.3 96 128 110.3 128 128L128 512C128 529.7 142.3 544 160 544C177.7 544 192 529.7 192 512L192 367.9L450 537.5C462.3 545.6 478 546.3 491 539.3C504 532.3 512 518.8 512 504.1L512 136.1C512 121.4 503.9 107.9 491 100.9z"/></svg>`,check:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M434.8 70.1c14.3 10.4 17.5 30.4 7.1 44.7l-256 352c-5.5 7.6-14 12.3-23.4 13.1s-18.5-2.7-25.1-9.3l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l101.5 101.5 234-321.7c10.4-14.3 30.4-17.5 44.7-7.1z"/></svg>`,"chevron-down":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M201.4 406.6c12.5 12.5 32.8 12.5 45.3 0l192-192c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L224 338.7 54.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l192 192z"/></svg>`,"chevron-left":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l192 192c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L77.3 256 246.6 86.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-192 192z"/></svg>`,"chevron-right":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M311.1 233.4c12.5 12.5 12.5 32.8 0 45.3l-192 192c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L243.2 256 73.9 86.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l192 192z"/></svg>`,circle:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0z"/></svg>`,"closed-captioning":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M64 192C64 156.7 92.7 128 128 128L512 128C547.3 128 576 156.7 576 192L576 448C576 483.3 547.3 512 512 512L128 512C92.7 512 64 483.3 64 448L64 192zM216 272L248 272C252.4 272 256 275.6 256 280C256 293.3 266.7 304 280 304C293.3 304 304 293.3 304 280C304 249.1 278.9 224 248 224L216 224C185.1 224 160 249.1 160 280L160 360C160 390.9 185.1 416 216 416L248 416C278.9 416 304 390.9 304 360C304 346.7 293.3 336 280 336C266.7 336 256 346.7 256 360C256 364.4 252.4 368 248 368L216 368C211.6 368 208 364.4 208 360L208 280C208 275.6 211.6 272 216 272zM384 280C384 275.6 387.6 272 392 272L424 272C428.4 272 432 275.6 432 280C432 293.3 442.7 304 456 304C469.3 304 480 293.3 480 280C480 249.1 454.9 224 424 224L392 224C361.1 224 336 249.1 336 280L336 360C336 390.9 361.1 416 392 416L424 416C454.9 416 480 390.9 480 360C480 346.7 469.3 336 456 336C442.7 336 432 346.7 432 360C432 364.4 428.4 368 424 368L392 368C387.6 368 384 364.4 384 360L384 280z"/></svg>`,"closed-captioning-slash":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M39 39.1C48.4 29.7 63.6 29.7 72.9 39.1L161.8 128L512 128C547.3 128 576 156.7 576 192L576 448C576 473.5 561.1 495.4 539.6 505.8L601 567.1C610.4 576.5 610.4 591.7 601 601C591.6 610.3 576.4 610.4 567.1 601L39 73.1C29.7 63.7 29.7 48.5 39 39.1zM384 350.1L384 279.9C384 275.5 387.6 271.9 392 271.9L424 271.9C428.4 271.9 432 275.5 432 279.9C432 293.2 442.7 303.9 456 303.9C469.3 303.9 480 293.2 480 279.9C480 249 454.9 223.9 424 223.9L392 223.9C361.1 223.9 336 249 336 279.9L336 302.1L384 350.1zM445.5 411.6C465.7 403.2 480 383.2 480 359.9C480 346.6 469.3 335.9 456 335.9C442.7 335.9 432 346.6 432 359.9C432 364.3 428.4 367.9 424 367.9L401.8 367.9L445.5 411.6zM162.3 264.1C160.8 269.1 160 274.5 160 280L160 360C160 390.9 185.1 416 216 416L248 416C266.1 416 282.1 407.5 292.4 394.2L410.2 512L128 512C92.7 512 64 483.3 64 448L64 192C64 184.2 65.4 176.7 68 169.8L162.3 264.1zM256.1 357.9C256 358.6 256 359.3 256 360C256 364.4 252.4 368 248 368L216 368C211.6 368 208 364.4 208 360L208 309.8L256.1 357.9z"/></svg>`,compress:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M160 64c0-17.7-14.3-32-32-32S96 46.3 96 64l0 64-64 0c-17.7 0-32 14.3-32 32s14.3 32 32 32l96 0c17.7 0 32-14.3 32-32l0-96zM32 320c-17.7 0-32 14.3-32 32s14.3 32 32 32l64 0 0 64c0 17.7 14.3 32 32 32s32-14.3 32-32l0-96c0-17.7-14.3-32-32-32l-96 0zM352 64c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 96c0 17.7 14.3 32 32 32l96 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-64 0 0-64zM320 320c-17.7 0-32 14.3-32 32l0 96c0 17.7 14.3 32 32 32s32-14.3 32-32l0-64 64 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-96 0z"/></svg>`,"ellipsis-vertical":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M320 208C289.1 208 264 182.9 264 152C264 121.1 289.1 96 320 96C350.9 96 376 121.1 376 152C376 182.9 350.9 208 320 208zM320 432C350.9 432 376 457.1 376 488C376 518.9 350.9 544 320 544C289.1 544 264 518.9 264 488C264 457.1 289.1 432 320 432zM376 320C376 350.9 350.9 376 320 376C289.1 376 264 350.9 264 320C264 289.1 289.1 264 320 264C350.9 264 376 289.1 376 320z"/></svg>`,expand:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M128 96C110.3 96 96 110.3 96 128L96 224C96 241.7 110.3 256 128 256C145.7 256 160 241.7 160 224L160 160L224 160C241.7 160 256 145.7 256 128C256 110.3 241.7 96 224 96L128 96zM160 416C160 398.3 145.7 384 128 384C110.3 384 96 398.3 96 416L96 512C96 529.7 110.3 544 128 544L224 544C241.7 544 256 529.7 256 512C256 494.3 241.7 480 224 480L160 480L160 416zM416 96C398.3 96 384 110.3 384 128C384 145.7 398.3 160 416 160L480 160L480 224C480 241.7 494.3 256 512 256C529.7 256 544 241.7 544 224L544 128C544 110.3 529.7 96 512 96L416 96zM544 416C544 398.3 529.7 384 512 384C494.3 384 480 398.3 480 416L480 480L416 480C398.3 480 384 494.3 384 512C384 529.7 398.3 544 416 544L512 544C529.7 544 544 529.7 544 512L544 416z"/></svg>`,eyedropper:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M341.6 29.2l-101.6 101.6-9.4-9.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3l-9.4-9.4 101.6-101.6c39-39 39-102.2 0-141.1s-102.2-39-141.1 0zM55.4 323.3c-15 15-23.4 35.4-23.4 56.6l0 42.4-26.6 39.9c-8.5 12.7-6.8 29.6 4 40.4s27.7 12.5 40.4 4l39.9-26.6 42.4 0c21.2 0 41.6-8.4 56.6-23.4l109.4-109.4-45.3-45.3-109.4 109.4c-3 3-7.1 4.7-11.3 4.7l-36.1 0 0-36.1c0-4.2 1.7-8.3 4.7-11.3l109.4-109.4-45.3-45.3-109.4 109.4z"/></svg>`,forward:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M403.7 107.1C392.1 96 375 92.9 360.3 99.2C345.6 105.5 336 120 336 136L336 272.3L163.7 107.2C152.1 96 135 92.9 120.3 99.2C105.6 105.5 96 120 96 136L96 504C96 520 105.6 534.5 120.3 540.8C135 547.1 152.1 544 163.7 532.9L336 367.7L336 504C336 520 345.6 534.5 360.3 540.8C375 547.1 392.1 544 403.7 532.9L595.7 348.9C603.6 341.4 608 330.9 608 320C608 309.1 603.5 298.7 595.7 291.1L403.7 107.1z"/></svg>`,file:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free 7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M192 64C156.7 64 128 92.7 128 128L128 512C128 547.3 156.7 576 192 576L448 576C483.3 576 512 547.3 512 512L512 234.5C512 217.5 505.3 201.2 493.3 189.2L386.7 82.7C374.7 70.7 358.5 64 341.5 64L192 64zM453.5 240L360 240C346.7 240 336 229.3 336 216L336 122.5L453.5 240z"/></svg>`,"file-audio":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free 7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M128 128C128 92.7 156.7 64 192 64L341.5 64C358.5 64 374.8 70.7 386.8 82.7L493.3 189.3C505.3 201.3 512 217.6 512 234.6L512 512C512 547.3 483.3 576 448 576L192 576C156.7 576 128 547.3 128 512L128 128zM336 122.5L336 216C336 229.3 346.7 240 360 240L453.5 240L336 122.5zM389.8 307.7C380.7 301.4 368.3 303.6 362 312.7C355.7 321.8 357.9 334.2 367 340.5C390.9 357.2 406.4 384.8 406.4 416C406.4 447.2 390.8 474.9 367 491.5C357.9 497.8 355.7 510.3 362 519.3C368.3 528.3 380.8 530.6 389.8 524.3C423.9 500.5 446.4 460.8 446.4 416C446.4 371.2 424 331.5 389.8 307.7zM208 376C199.2 376 192 383.2 192 392L192 440C192 448.8 199.2 456 208 456L232 456L259.2 490C262.2 493.8 266.8 496 271.7 496L272 496C280.8 496 288 488.8 288 480L288 352C288 343.2 280.8 336 272 336L271.7 336C266.8 336 262.2 338.2 259.2 342L232 376L208 376zM336 448.2C336 458.9 346.5 466.4 354.9 459.8C367.8 449.5 376 433.7 376 416C376 398.3 367.8 382.5 354.9 372.2C346.5 365.5 336 373.1 336 383.8L336 448.3z"/></svg>`,"file-code":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free 7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M128 128C128 92.7 156.7 64 192 64L341.5 64C358.5 64 374.8 70.7 386.8 82.7L493.3 189.3C505.3 201.3 512 217.6 512 234.6L512 512C512 547.3 483.3 576 448 576L192 576C156.7 576 128 547.3 128 512L128 128zM336 122.5L336 216C336 229.3 346.7 240 360 240L453.5 240L336 122.5zM282.2 359.6C290.8 349.5 289.7 334.4 279.6 325.8C269.5 317.2 254.4 318.3 245.8 328.4L197.8 384.4C190.1 393.4 190.1 406.6 197.8 415.6L245.8 471.6C254.4 481.7 269.6 482.8 279.6 474.2C289.6 465.6 290.8 450.4 282.2 440.4L247.6 400L282.2 359.6zM394.2 328.4C385.6 318.3 370.4 317.2 360.4 325.8C350.4 334.4 349.2 349.6 357.8 359.6L392.4 400L357.8 440.4C349.2 450.5 350.3 465.6 360.4 474.2C370.5 482.8 385.6 481.7 394.2 471.6L442.2 415.6C449.9 406.6 449.9 393.4 442.2 384.4L394.2 328.4z"/></svg>`,"file-excel":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free 7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M128 128C128 92.7 156.7 64 192 64L341.5 64C358.5 64 374.8 70.7 386.8 82.7L493.3 189.3C505.3 201.3 512 217.6 512 234.6L512 512C512 547.3 483.3 576 448 576L192 576C156.7 576 128 547.3 128 512L128 128zM336 122.5L336 216C336 229.3 346.7 240 360 240L453.5 240L336 122.5zM292 330.7C284.6 319.7 269.7 316.7 258.7 324C247.7 331.3 244.7 346.3 252 357.3L291.2 416L252 474.7C244.6 485.7 247.6 500.6 258.7 508C269.8 515.4 284.6 512.4 292 501.3L320 459.3L348 501.3C355.4 512.3 370.3 515.3 381.3 508C392.3 500.7 395.3 485.7 388 474.7L348.8 416L388 357.3C395.4 346.3 392.4 331.4 381.3 324C370.2 316.6 355.4 319.6 348 330.7L320 372.7L292 330.7z"/></svg>`,"file-image":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free 7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M128 128C128 92.7 156.7 64 192 64L341.5 64C358.5 64 374.8 70.7 386.8 82.7L493.3 189.3C505.3 201.3 512 217.6 512 234.6L512 512C512 547.3 483.3 576 448 576L192 576C156.7 576 128 547.3 128 512L128 128zM336 122.5L336 216C336 229.3 346.7 240 360 240L453.5 240L336 122.5zM256 320C256 302.3 241.7 288 224 288C206.3 288 192 302.3 192 320C192 337.7 206.3 352 224 352C241.7 352 256 337.7 256 320zM220.6 512L419.4 512C435.2 512 448 499.2 448 483.4C448 476.1 445.2 469 440.1 463.7L343.3 361.9C337.3 355.6 328.9 352 320.1 352L319.8 352C311 352 302.7 355.6 296.6 361.9L199.9 463.7C194.8 469 192 476.1 192 483.4C192 499.2 204.8 512 220.6 512z"/></svg>`,"file-pdf":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free 7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M128 64C92.7 64 64 92.7 64 128L64 512C64 547.3 92.7 576 128 576L208 576L208 464C208 428.7 236.7 400 272 400L448 400L448 234.5C448 217.5 441.3 201.2 429.3 189.2L322.7 82.7C310.7 70.7 294.5 64 277.5 64L128 64zM389.5 240L296 240C282.7 240 272 229.3 272 216L272 122.5L389.5 240zM272 444C261 444 252 453 252 464L252 592C252 603 261 612 272 612C283 612 292 603 292 592L292 564L304 564C337.1 564 364 537.1 364 504C364 470.9 337.1 444 304 444L272 444zM304 524L292 524L292 484L304 484C315 484 324 493 324 504C324 515 315 524 304 524zM400 444C389 444 380 453 380 464L380 592C380 603 389 612 400 612L432 612C460.7 612 484 588.7 484 560L484 496C484 467.3 460.7 444 432 444L400 444zM420 572L420 484L432 484C438.6 484 444 489.4 444 496L444 560C444 566.6 438.6 572 432 572L420 572zM508 464L508 592C508 603 517 612 528 612C539 612 548 603 548 592L548 548L576 548C587 548 596 539 596 528C596 517 587 508 576 508L548 508L548 484L576 484C587 484 596 475 596 464C596 453 587 444 576 444L528 444C517 444 508 453 508 464z"/></svg>`,"file-powerpoint":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free 7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M128 128C128 92.7 156.7 64 192 64L341.5 64C358.5 64 374.8 70.7 386.8 82.7L493.3 189.3C505.3 201.3 512 217.6 512 234.6L512 512C512 547.3 483.3 576 448 576L192 576C156.7 576 128 547.3 128 512L128 128zM336 122.5L336 216C336 229.3 346.7 240 360 240L453.5 240L336 122.5zM280 320C266.7 320 256 330.7 256 344L256 488C256 501.3 266.7 512 280 512C293.3 512 304 501.3 304 488L304 464L328 464C367.8 464 400 431.8 400 392C400 352.2 367.8 320 328 320L280 320zM328 416L304 416L304 368L328 368C341.3 368 352 378.7 352 392C352 405.3 341.3 416 328 416z"/></svg>`,"file-video":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free 7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M128 128C128 92.7 156.7 64 192 64L341.5 64C358.5 64 374.8 70.7 386.8 82.7L493.3 189.3C505.3 201.3 512 217.6 512 234.6L512 512C512 547.3 483.3 576 448 576L192 576C156.7 576 128 547.3 128 512L128 128zM336 122.5L336 216C336 229.3 346.7 240 360 240L453.5 240L336 122.5zM208 368L208 464C208 481.7 222.3 496 240 496L336 496C353.7 496 368 481.7 368 464L368 440L403 475C406.2 478.2 410.5 480 415 480C424.4 480 432 472.4 432 463L432 368.9C432 359.5 424.4 351.9 415 351.9C410.5 351.9 406.2 353.7 403 356.9L368 391.9L368 367.9C368 350.2 353.7 335.9 336 335.9L240 335.9C222.3 335.9 208 350.2 208 367.9z"/></svg>`,"file-word":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free 7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M128 128C128 92.7 156.7 64 192 64L341.5 64C358.5 64 374.8 70.7 386.8 82.7L493.3 189.3C505.3 201.3 512 217.6 512 234.6L512 512C512 547.3 483.3 576 448 576L192 576C156.7 576 128 547.3 128 512L128 128zM336 122.5L336 216C336 229.3 346.7 240 360 240L453.5 240L336 122.5zM263.4 338.8C260.5 325.9 247.7 317.7 234.8 320.6C221.9 323.5 213.7 336.3 216.6 349.2L248.6 493.2C250.9 503.7 260 511.4 270.8 512C281.6 512.6 291.4 505.9 294.8 495.6L320 419.9L345.2 495.6C348.6 505.8 358.4 512.5 369.2 512C380 511.5 389.1 503.8 391.4 493.2L423.4 349.2C426.3 336.3 418.1 323.4 405.2 320.6C392.3 317.8 379.4 325.9 376.6 338.8L363.4 398.2L342.8 336.4C339.5 326.6 330.4 320 320 320C309.6 320 300.5 326.6 297.2 336.4L276.6 398.2L263.4 338.8z"/></svg>`,"file-zipper":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free 7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M128 128C128 92.7 156.7 64 192 64L341.5 64C358.5 64 374.8 70.7 386.8 82.7L493.3 189.3C505.3 201.3 512 217.6 512 234.6L512 512C512 547.3 483.3 576 448 576L192 576C156.7 576 128 547.3 128 512L128 128zM336 122.5L336 216C336 229.3 346.7 240 360 240L453.5 240L336 122.5zM192 136C192 149.3 202.7 160 216 160L264 160C277.3 160 288 149.3 288 136C288 122.7 277.3 112 264 112L216 112C202.7 112 192 122.7 192 136zM192 232C192 245.3 202.7 256 216 256L264 256C277.3 256 288 245.3 288 232C288 218.7 277.3 208 264 208L216 208C202.7 208 192 218.7 192 232zM256 304L224 304C206.3 304 192 318.3 192 336L192 384C192 410.5 213.5 432 240 432C266.5 432 288 410.5 288 384L288 336C288 318.3 273.7 304 256 304zM240 368C248.8 368 256 375.2 256 384C256 392.8 248.8 400 240 400C231.2 400 224 392.8 224 384C224 375.2 231.2 368 240 368z"/></svg>`,"forward-step":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M21 36.8c12.9-7 28.7-6.3 41 1.8L320 208.1 320 64c0-17.7 14.3-32 32-32s32 14.3 32 32l0 384c0 17.7-14.3 32-32 32s-32-14.3-32-32l0-144.1-258 169.6c-12.3 8.1-28 8.8-41 1.8S0 454.7 0 440L0 72C0 57.3 8.1 43.8 21 36.8z"/></svg>`,gauge:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0zm320 96c0-26.9-16.5-49.9-40-59.3L280 120c0-13.3-10.7-24-24-24s-24 10.7-24 24l0 172.7c-23.5 9.5-40 32.5-40 59.3 0 35.3 28.7 64 64 64s64-28.7 64-64zM144 176a32 32 0 1 0 0-64 32 32 0 1 0 0 64zm-16 80a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zm288 32a32 32 0 1 0 0-64 32 32 0 1 0 0 64zM400 144a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z"/></svg>`,gear:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M259.1 73.5C262.1 58.7 275.2 48 290.4 48L350.2 48C365.4 48 378.5 58.7 381.5 73.5L396 143.5C410.1 149.5 423.3 157.2 435.3 166.3L503.1 143.8C517.5 139 533.3 145 540.9 158.2L570.8 210C578.4 223.2 575.7 239.8 564.3 249.9L511 297.3C511.9 304.7 512.3 312.3 512.3 320C512.3 327.7 511.8 335.3 511 342.7L564.4 390.2C575.8 400.3 578.4 417 570.9 430.1L541 481.9C533.4 495 517.6 501.1 503.2 496.3L435.4 473.8C423.3 482.9 410.1 490.5 396.1 496.6L381.7 566.5C378.6 581.4 365.5 592 350.4 592L290.6 592C275.4 592 262.3 581.3 259.3 566.5L244.9 496.6C230.8 490.6 217.7 482.9 205.6 473.8L137.5 496.3C123.1 501.1 107.3 495.1 99.7 481.9L69.8 430.1C62.2 416.9 64.9 400.3 76.3 390.2L129.7 342.7C128.8 335.3 128.4 327.7 128.4 320C128.4 312.3 128.9 304.7 129.7 297.3L76.3 249.8C64.9 239.7 62.3 223 69.8 209.9L99.7 158.1C107.3 144.9 123.1 138.9 137.5 143.7L205.3 166.2C217.4 157.1 230.6 149.5 244.6 143.4L259.1 73.5zM320.3 400C364.5 399.8 400.2 363.9 400 319.7C399.8 275.5 363.9 239.8 319.7 240C275.5 240.2 239.8 276.1 240 320.3C240.2 364.5 276.1 400.2 320.3 400z"/></svg>`,"grip-vertical":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M128 40c0-22.1-17.9-40-40-40L40 0C17.9 0 0 17.9 0 40L0 88c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zm0 192c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zM0 424l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zM320 40c0-22.1-17.9-40-40-40L232 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zM192 232l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zM320 424c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48z"/></svg>`,indeterminate:`<svg part="indeterminate-icon" class="icon" viewBox="0 0 16 16"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" stroke-linecap="round"><g stroke="currentColor" stroke-width="2"><g transform="translate(2.285714 6.857143)"><path d="M10.2857143,1.14285714 L1.14285714,1.14285714"/></g></g></g></svg>`,minus:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M0 256c0-17.7 14.3-32 32-32l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 288c-17.7 0-32-14.3-32-32z"/></svg>`,pause:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M48 32C21.5 32 0 53.5 0 80L0 432c0 26.5 21.5 48 48 48l64 0c26.5 0 48-21.5 48-48l0-352c0-26.5-21.5-48-48-48L48 32zm224 0c-26.5 0-48 21.5-48 48l0 352c0 26.5 21.5 48 48 48l64 0c26.5 0 48-21.5 48-48l0-352c0-26.5-21.5-48-48-48l-64 0z"/></svg>`,"picture-in-picture":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M448 32c35.3 0 64 28.7 64 64l0 112-64 0 0-112-384 0 0 320 144 0 0 64-144 0-6.5-.3c-30.1-3.1-54.1-27-57.1-57.1L0 416 0 96C0 62.9 25.2 35.6 57.5 32.3L64 32 448 32zm16 224c26.5 0 48 21.5 48 48l0 128c0 26.5-21.5 48-48 48l-160 0c-26.5 0-48-21.5-48-48l0-128c0-26.5 21.5-48 48-48l160 0z"/></svg>`,play:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M91.2 36.9c-12.4-6.8-27.4-6.5-39.6 .7S32 57.9 32 72l0 368c0 14.1 7.5 27.2 19.6 34.4s27.2 7.5 39.6 .7l336-184c12.8-7 20.8-20.5 20.8-35.1s-8-28.1-20.8-35.1l-336-184z"/></svg>`,"play-circle":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0zM188.3 147.1c-7.6 4.2-12.3 12.3-12.3 20.9l0 176c0 8.7 4.7 16.7 12.3 20.9s16.8 4.1 24.3-.5l144-88c7.1-4.4 11.5-12.1 11.5-20.5s-4.4-16.1-11.5-20.5l-144-88c-7.4-4.5-16.7-4.7-24.3-.5z"/></svg>`,plus:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free 7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M352 128C352 110.3 337.7 96 320 96C302.3 96 288 110.3 288 128L288 288L128 288C110.3 288 96 302.3 96 320C96 337.7 110.3 352 128 352L288 352L288 512C288 529.7 302.3 544 320 544C337.7 544 352 529.7 352 512L352 352L512 352C529.7 352 544 337.7 544 320C544 302.3 529.7 288 512 288L352 288L352 128z"/></svg>`,star:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M309.5-18.9c-4.1-8-12.4-13.1-21.4-13.1s-17.3 5.1-21.4 13.1L193.1 125.3 33.2 150.7c-8.9 1.4-16.3 7.7-19.1 16.3s-.5 18 5.8 24.4l114.4 114.5-25.2 159.9c-1.4 8.9 2.3 17.9 9.6 23.2s16.9 6.1 25 2L288.1 417.6 432.4 491c8 4.1 17.7 3.3 25-2s11-14.2 9.6-23.2L441.7 305.9 556.1 191.4c6.4-6.4 8.6-15.8 5.8-24.4s-10.1-14.9-19.1-16.3L383 125.3 309.5-18.9z"/></svg>`,upload:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free 7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M352 173.3L352 384C352 401.7 337.7 416 320 416C302.3 416 288 401.7 288 384L288 173.3L246.6 214.7C234.1 227.2 213.8 227.2 201.3 214.7C188.8 202.2 188.8 181.9 201.3 169.4L297.3 73.4C309.8 60.9 330.1 60.9 342.6 73.4L438.6 169.4C451.1 181.9 451.1 202.2 438.6 214.7C426.1 227.2 405.8 227.2 393.3 214.7L352 173.3zM320 464C364.2 464 400 428.2 400 384L480 384C515.3 384 544 412.7 544 448L544 480C544 515.3 515.3 544 480 544L160 544C124.7 544 96 515.3 96 480L96 448C96 412.7 124.7 384 160 384L240 384C240 428.2 275.8 464 320 464zM464 488C477.3 488 488 477.3 488 464C488 450.7 477.3 440 464 440C450.7 440 440 450.7 440 464C440 477.3 450.7 488 464 488z"/></svg>`,user:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M224 248a120 120 0 1 0 0-240 120 120 0 1 0 0 240zm-29.7 56C95.8 304 16 383.8 16 482.3 16 498.7 29.3 512 45.7 512l356.6 0c16.4 0 29.7-13.3 29.7-29.7 0-98.5-79.8-178.3-178.3-178.3l-59.4 0z"/></svg>`,volume:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M48 352l48 0 134.1 119.2c6.4 5.7 14.6 8.8 23.1 8.8 19.2 0 34.8-15.6 34.8-34.8l0-378.4c0-19.2-15.6-34.8-34.8-34.8-8.5 0-16.7 3.1-23.1 8.8L96 160 48 160c-26.5 0-48 21.5-48 48l0 96c0 26.5 21.5 48 48 48zM441.1 107c-10.3-8.4-25.4-6.8-33.8 3.5s-6.8 25.4 3.5 33.8C443.3 170.7 464 210.9 464 256s-20.7 85.3-53.2 111.8c-10.3 8.4-11.8 23.5-3.5 33.8s23.5 11.8 33.8 3.5c43.2-35.2 70.9-88.9 70.9-149s-27.7-113.8-70.9-149zm-60.5 74.5c-10.3-8.4-25.4-6.8-33.8 3.5s-6.8 25.4 3.5 33.8C361.1 227.6 368 241 368 256s-6.9 28.4-17.7 37.3c-10.3 8.4-11.8 23.5-3.5 33.8s23.5 11.8 33.8 3.5C402.1 312.9 416 286.1 416 256s-13.9-56.9-35.5-74.5z"/></svg>`,"volume-low":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M48 352l48 0 134.1 119.2c6.4 5.7 14.6 8.8 23.1 8.8 19.2 0 34.8-15.6 34.8-34.8l0-378.4c0-19.2-15.6-34.8-34.8-34.8-8.5 0-16.7 3.1-23.1 8.8L96 160 48 160c-26.5 0-48 21.5-48 48l0 96c0 26.5 21.5 48 48 48zM380.6 181.5c-10.3-8.4-25.4-6.8-33.8 3.5s-6.8 25.4 3.5 33.8C361.1 227.6 368 241 368 256s-6.9 28.4-17.7 37.3c-10.3 8.4-11.8 23.5-3.5 33.8s23.5 11.8 33.8 3.5C402.1 312.9 416 286.1 416 256s-13.9-56.9-35.5-74.5z"/></svg>`,"volume-xmark":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M48 352l48 0 134.1 119.2c6.4 5.7 14.6 8.8 23.1 8.8 19.2 0 34.8-15.6 34.8-34.8l0-378.4c0-19.2-15.6-34.8-34.8-34.8-8.5 0-16.7 3.1-23.1 8.8L96 160 48 160c-26.5 0-48 21.5-48 48l0 96c0 26.5 21.5 48 48 48zM367 175c-9.4 9.4-9.4 24.6 0 33.9l47 47-47 47c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l47-47 47 47c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-47-47 47-47c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-47 47-47-47c-9.4-9.4-24.6-9.4-33.9 0z"/></svg>`,xmark:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M55.1 73.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L147.2 256 9.9 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192.5 301.3 329.9 438.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.8 256 375.1 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192.5 210.7 55.1 73.4z"/></svg>`},regular:{"circle-question":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M464 256a208 208 0 1 0 -416 0 208 208 0 1 0 416 0zM0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0zm256-80c-17.7 0-32 14.3-32 32 0 13.3-10.7 24-24 24s-24-10.7-24-24c0-44.2 35.8-80 80-80s80 35.8 80 80c0 47.2-36 67.2-56 74.5l0 3.8c0 13.3-10.7 24-24 24s-24-10.7-24-24l0-8.1c0-20.5 14.8-35.2 30.1-40.2 6.4-2.1 13.2-5.5 18.2-10.3 4.3-4.2 7.7-10 7.7-19.6 0-17.7-14.3-32-32-32zM224 368a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>`,"circle-xmark":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M256 48a208 208 0 1 1 0 416 208 208 0 1 1 0-416zm0 464a256 256 0 1 0 0-512 256 256 0 1 0 0 512zM167 167c-9.4 9.4-9.4 24.6 0 33.9l55 55-55 55c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l55-55 55 55c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-55-55 55-55c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-55 55-55-55c-9.4-9.4-24.6-9.4-33.9 0z"/></svg>`,copy:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M384 336l-192 0c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l133.5 0c4.2 0 8.3 1.7 11.3 4.7l58.5 58.5c3 3 4.7 7.1 4.7 11.3L400 320c0 8.8-7.2 16-16 16zM192 384l192 0c35.3 0 64-28.7 64-64l0-197.5c0-17-6.7-33.3-18.7-45.3L370.7 18.7C358.7 6.7 342.5 0 325.5 0L192 0c-35.3 0-64 28.7-64 64l0 256c0 35.3 28.7 64 64 64zM64 128c-35.3 0-64 28.7-64 64L0 448c0 35.3 28.7 64 64 64l192 0c35.3 0 64-28.7 64-64l0-16-48 0 0 16c0 8.8-7.2 16-16 16L64 464c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l16 0 0-48-16 0z"/></svg>`,eye:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M288 80C222.8 80 169.2 109.6 128.1 147.7 89.6 183.5 63 226 49.4 256 63 286 89.6 328.5 128.1 364.3 169.2 402.4 222.8 432 288 432s118.8-29.6 159.9-67.7C486.4 328.5 513 286 526.6 256 513 226 486.4 183.5 447.9 147.7 406.8 109.6 353.2 80 288 80zM95.4 112.6C142.5 68.8 207.2 32 288 32s145.5 36.8 192.6 80.6c46.8 43.5 78.1 95.4 93 131.1 3.3 7.9 3.3 16.7 0 24.6-14.9 35.7-46.2 87.7-93 131.1-47.1 43.7-111.8 80.6-192.6 80.6S142.5 443.2 95.4 399.4c-46.8-43.5-78.1-95.4-93-131.1-3.3-7.9-3.3-16.7 0-24.6 14.9-35.7 46.2-87.7 93-131.1zM288 336c44.2 0 80-35.8 80-80 0-29.6-16.1-55.5-40-69.3-1.4 59.7-49.6 107.9-109.3 109.3 13.8 23.9 39.7 40 69.3 40zm-79.6-88.4c2.5 .3 5 .4 7.6 .4 35.3 0 64-28.7 64-64 0-2.6-.2-5.1-.4-7.6-37.4 3.9-67.2 33.7-71.1 71.1zm45.6-115c10.8-3 22.2-4.5 33.9-4.5 8.8 0 17.5 .9 25.8 2.6 .3 .1 .5 .1 .8 .2 57.9 12.2 101.4 63.7 101.4 125.2 0 70.7-57.3 128-128 128-61.6 0-113-43.5-125.2-101.4-1.8-8.6-2.8-17.5-2.8-26.6 0-11 1.4-21.8 4-32 .2-.7 .3-1.3 .5-1.9 11.9-43.4 46.1-77.6 89.5-89.5z"/></svg>`,"eye-slash":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M41-24.9c-9.4-9.4-24.6-9.4-33.9 0S-2.3-.3 7 9.1l528 528c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-96.4-96.4c2.7-2.4 5.4-4.8 8-7.2 46.8-43.5 78.1-95.4 93-131.1 3.3-7.9 3.3-16.7 0-24.6-14.9-35.7-46.2-87.7-93-131.1-47.1-43.7-111.8-80.6-192.6-80.6-56.8 0-105.6 18.2-146 44.2L41-24.9zM176.9 111.1c32.1-18.9 69.2-31.1 111.1-31.1 65.2 0 118.8 29.6 159.9 67.7 38.5 35.7 65.1 78.3 78.6 108.3-13.6 30-40.2 72.5-78.6 108.3-3.1 2.8-6.2 5.6-9.4 8.4L393.8 328c14-20.5 22.2-45.3 22.2-72 0-70.7-57.3-128-128-128-26.7 0-51.5 8.2-72 22.2l-39.1-39.1zm182 182l-108-108c11.1-5.8 23.7-9.1 37.1-9.1 44.2 0 80 35.8 80 80 0 13.4-3.3 26-9.1 37.1zM103.4 173.2l-34-34c-32.6 36.8-55 75.8-66.9 104.5-3.3 7.9-3.3 16.7 0 24.6 14.9 35.7 46.2 87.7 93 131.1 47.1 43.7 111.8 80.6 192.6 80.6 37.3 0 71.2-7.9 101.5-20.6L352.2 422c-20 6.4-41.4 10-64.2 10-65.2 0-118.8-29.6-159.9-67.7-38.5-35.7-65.1-78.3-78.6-108.3 10.4-23.1 28.6-53.6 54-82.8z"/></svg>`,star:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M288.1-32c9 0 17.3 5.1 21.4 13.1L383 125.3 542.9 150.7c8.9 1.4 16.3 7.7 19.1 16.3s.5 18-5.8 24.4L441.7 305.9 467 465.8c1.4 8.9-2.3 17.9-9.6 23.2s-17 6.1-25 2L288.1 417.6 143.8 491c-8 4.1-17.7 3.3-25-2s-11-14.2-9.6-23.2L134.4 305.9 20 191.4c-6.4-6.4-8.6-15.8-5.8-24.4s10.1-14.9 19.1-16.3l159.9-25.4 73.6-144.2c4.1-8 12.4-13.1 21.4-13.1zm0 76.8L230.3 158c-3.5 6.8-10 11.6-17.6 12.8l-125.5 20 89.8 89.9c5.4 5.4 7.9 13.1 6.7 20.7l-19.8 125.5 113.3-57.6c6.8-3.5 14.9-3.5 21.8 0l113.3 57.6-19.8-125.5c-1.2-7.6 1.3-15.3 6.7-20.7l89.8-89.9-125.5-20c-7.6-1.2-14.1-6-17.6-12.8L288.1 44.8z"/></svg>`}},Me={name:`system`,resolver:(e,t=`classic`,n=`solid`)=>{let r=je[n][e]??je.regular[e]??je.regular[`circle-question`];return r?Ae(r):``}},Ne=`classic`,Pe=[ke,Me],Fe=[];function Ie(e){Fe.push(e)}function Le(e){Fe=Fe.filter(t=>t!==e)}function Re(e){return Pe.find(t=>t.name===e)}function ze(e,t){Be(e),Pe.push({name:e,resolver:t.resolver,mutator:t.mutator,spriteSheet:t.spriteSheet}),Fe.forEach(t=>{t.library===e&&t.setIcon()})}function Be(e){Pe=Pe.filter(t=>t.name!==e)}function Ve(){return Ne}var He=Object.defineProperty,Ue=Object.getOwnPropertyDescriptor,We=e=>{throw TypeError(e)},A=(e,t,n,r)=>{for(var i=r>1?void 0:r?Ue(t,n):t,a=e.length-1,o;a>=0;a--)(o=e[a])&&(i=(r?o(t,n,i):o(i))||i);return r&&i&&He(t,n,i),i},Ge=(e,t,n)=>t.has(e)||We(`Cannot `+n),Ke=(e,t,n)=>(Ge(e,t,`read from private field`),n?n.call(e):t.get(e)),qe=(e,t,n)=>t.has(e)?We(`Cannot add the same private member more than once`):t instanceof WeakSet?t.add(e):t.set(e,n),Je=(e,t,n,r)=>(Ge(e,t,`write to private field`),r?r.call(e,n):t.set(e,n),n),Ye={alert:`triangle-exclamation`,asc:`arrow-down-short-wide`,asset:`image`,assets:`image`,circleuarr:`circle-arrow-up`,collapse:`down-left-and-up-right-to-center`,condition:`diamond`,darr:`arrow-down`,date:`calendar`,desc:`arrow-down-wide-short`,disabled:`circle-dashed`,done:`circle-check`,downangle:`angle-down`,draft:`scribble`,edit:`pencil`,enabled:`circle`,expand:`up-right-and-down-left-from-center`,external:`arrow-up-right-from-square`,field:`pen-to-square`,help:`circle-question`,home:`house`,info:`circle-info`,insecure:`unlock`,larr:`arrow-left`,layout:`table-layout`,leftangle:`angle-left`,listrtl:`list-flip`,location:`location-dot`,mail:`envelope`,menu:`bars`,move:`grip-dots`,newstamp:`certificate`,paperplane:`paper-plane`,plugin:`plug`,rarr:`arrow-right`,refresh:`arrows-rotate`,remove:`xmark`,rightangle:`angle-right`,rotate:`rotate-left`,routes:`signs-post`,search:`magnifying-glass`,secure:`lock`,settings:`gear`,shareleft:`share-flip`,shuteye:`eye-slash`,"sidebar-left":`sidebar`,"sidebar-right":`sidebar-flip`,"sidebar-start":`sidebar`,"sidebar-end":`sidebar-flip`,structure:`list-tree`,structurertl:`list-tree-flip`,template:`file-code`,time:`clock`,tool:`wrench`,uarr:`arrow-up`,upangle:`angle-up`,view:`eye`,wand:`wand-magic-sparkles`};function Xe(e,t=`classic`,n=`regular`){let r=`solid`,i=n,a=e.endsWith(`.svg`)?e.split(`.svg`)[0]:e;if(e.includes(`/`)){let[t,...n]=e.split(`/`);i=t??i,a=n.join(`/`)}return i===`thin`?r=`thin`:i===`light`?r=`light`:i===`regular`?r=`regular`:i===`solid`&&(r=`solid`),t===`brands`&&(r=`brands`),(t===`custom-icons`||i===`custom-icons`)&&(r=`custom-icons`),a=Ye[a]??a,`/vendor/craft/icons/${r}/${a}.svg`}function Ze(){ze(`default`,{resolver:(e,t=`classic`,n=`solid`)=>Xe(e,t,n),mutator:e=>e.setAttribute(`fill`,`currentColor`)})}var Qe=class extends HTMLElement{constructor(...e){super(...e),this.cookieName=null,this.state=`collapsed`,this.expanded=!1,this.handleOpen=()=>{this.trigger?.setAttribute(`aria-expanded`,`true`),this.expanded=!0,this.dispatchEvent(new CustomEvent(`open`)),this.target&&(this.target.dataset.state=`expanded`),this.cookieName&&window.Craft?.setCookie(this.cookieName,`expanded`)},this.handleClose=()=>{this.trigger?.setAttribute(`aria-expanded`,`false`),this.expanded=!1,this.dispatchEvent(new CustomEvent(`close`)),this.target&&(this.target.dataset.state=`collapsed`),this.cookieName&&window.Craft?.setCookie(this.cookieName,`collapsed`)}}get trigger(){return this.querySelector(`button[type="button"]`)}get target(){if(!this.trigger)return console.warn(`No trigger found for disclosure.`),null;let e=this.trigger.getAttribute(`aria-controls`);return e?document.getElementById(e):(console.warn(`No target selector found for disclosure.`),null)}connectedCallback(){if(!this.trigger){console.error(`craft-disclosure elements must include a button`,this);return}if(!this.target){console.error(`No target with id ${this.trigger.getAttribute(`aria-controls`)} found for disclosure. `,this.trigger);return}this.cookieName=this.getAttribute(`cookie-name`),this.state=this.getAttribute(`state`)??`expanded`,this.trigger.setAttribute(`aria-expanded`,this.state===`expanded`?`true`:`false`),this.trigger.addEventListener(`click`,this.toggle.bind(this)),this.state===`expanded`?this.open():this.close()}disconnectedCallback(){this.open(),this.trigger?.removeEventListener(`click`,this.toggle.bind(this))}attributeChangedCallback(e,t,n){e===`state`&&(n===`expanded`?this.handleOpen():this.handleClose())}toggle(){this.expanded?this.close():this.open()}open(){this.setAttribute(`state`,`expanded`)}close(){this.setAttribute(`state`,`collapsed`)}};Qe.observedAttributes=[`state`],customElements.get(`craft-disclosure`)||customElements.define(`craft-disclosure`,Qe);var $e=class extends Event{constructor(){super(`wa-error`,{bubbles:!0,cancelable:!1,composed:!0})}},et=b`
  :host {
    --primary-color: currentColor;
    --primary-opacity: 1;
    --secondary-color: currentColor;
    --secondary-opacity: 0.4;
    --rotate-angle: 0deg;

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
    overflow: visible;
    width: auto;

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

  /* Rotation */
  :host([rotate]) {
    transform: rotate(var(--rotate-angle, 0deg));
  }

  /* Flipping */
  :host([flip='x']) {
    transform: scaleX(-1);
  }
  :host([flip='y']) {
    transform: scaleY(-1);
  }
  :host([flip='both']) {
    transform: scale(-1, -1);
  }

  /* Rotation and Flipping combined */
  :host([rotate][flip='x']) {
    transform: rotate(var(--rotate-angle, 0deg)) scaleX(-1);
  }
  :host([rotate][flip='y']) {
    transform: rotate(var(--rotate-angle, 0deg)) scaleY(-1);
  }
  :host([rotate][flip='both']) {
    transform: rotate(var(--rotate-angle, 0deg)) scale(-1, -1);
  }

  /* Animations */
  :host([animation='beat']) {
    animation-name: beat;
    animation-delay: var(--animation-delay, 0s);
    animation-direction: var(--animation-direction, normal);
    animation-duration: var(--animation-duration, 1s);
    animation-iteration-count: var(--animation-iteration-count, infinite);
    animation-timing-function: var(--animation-timing, ease-in-out);
  }

  :host([animation='fade']) {
    animation-name: fade;
    animation-delay: var(--animation-delay, 0s);
    animation-direction: var(--animation-direction, normal);
    animation-duration: var(--animation-duration, 1s);
    animation-iteration-count: var(--animation-iteration-count, infinite);
    animation-timing-function: var(--animation-timing, cubic-bezier(0.4, 0, 0.6, 1));
  }

  :host([animation='beat-fade']) {
    animation-name: beat-fade;
    animation-delay: var(--animation-delay, 0s);
    animation-direction: var(--animation-direction, normal);
    animation-duration: var(--animation-duration, 1s);
    animation-iteration-count: var(--animation-iteration-count, infinite);
    animation-timing-function: var(--animation-timing, cubic-bezier(0.4, 0, 0.6, 1));
  }

  :host([animation='bounce']) {
    animation-name: bounce;
    animation-delay: var(--animation-delay, 0s);
    animation-direction: var(--animation-direction, normal);
    animation-duration: var(--animation-duration, 1s);
    animation-iteration-count: var(--animation-iteration-count, infinite);
    animation-timing-function: var(--animation-timing, cubic-bezier(0.28, 0.84, 0.42, 1));
  }

  :host([animation='flip']) {
    animation-name: flip;
    animation-delay: var(--animation-delay, 0s);
    animation-direction: var(--animation-direction, normal);
    animation-duration: var(--animation-duration, 1s);
    animation-iteration-count: var(--animation-iteration-count, infinite);
    animation-timing-function: var(--animation-timing, ease-in-out);
  }

  :host([animation='shake']) {
    animation-name: shake;
    animation-delay: var(--animation-delay, 0s);
    animation-direction: var(--animation-direction, normal);
    animation-duration: var(--animation-duration, 1s);
    animation-iteration-count: var(--animation-iteration-count, infinite);
    animation-timing-function: var(--animation-timing, linear);
  }

  :host([animation='spin']) {
    animation-name: spin;
    animation-delay: var(--animation-delay, 0s);
    animation-direction: var(--animation-direction, normal);
    animation-duration: var(--animation-duration, 2s);
    animation-iteration-count: var(--animation-iteration-count, infinite);
    animation-timing-function: var(--animation-timing, linear);
  }

  :host([animation='spin-pulse']) {
    animation-name: spin-pulse;
    animation-direction: var(--animation-direction, normal);
    animation-duration: var(--animation-duration, 1s);
    animation-iteration-count: var(--animation-iteration-count, infinite);
    animation-timing-function: var(--animation-timing, steps(8));
  }

  :host([animation='spin-reverse']) {
    animation-name: spin;
    animation-delay: var(--animation-delay, 0s);
    animation-direction: var(--animation-direction, reverse);
    animation-duration: var(--animation-duration, 2s);
    animation-iteration-count: var(--animation-iteration-count, infinite);
    animation-timing-function: var(--animation-timing, linear);
  }

  /* Keyframes */
  @media (prefers-reduced-motion: reduce) {
    :host([animation='beat']),
    :host([animation='bounce']),
    :host([animation='fade']),
    :host([animation='beat-fade']),
    :host([animation='flip']),
    :host([animation='shake']),
    :host([animation='spin']),
    :host([animation='spin-pulse']),
    :host([animation='spin-reverse']) {
      animation: none !important;
      transition: none !important;
    }
  }
  @keyframes beat {
    0%,
    90% {
      transform: scale(1);
    }
    45% {
      transform: scale(var(--beat-scale, 1.25));
    }
  }

  @keyframes fade {
    50% {
      opacity: var(--fade-opacity, 0.4);
    }
  }

  @keyframes beat-fade {
    0%,
    100% {
      opacity: var(--beat-fade-opacity, 0.4);
      transform: scale(1);
    }
    50% {
      opacity: 1;
      transform: scale(var(--beat-fade-scale, 1.125));
    }
  }

  @keyframes bounce {
    0% {
      transform: scale(1, 1) translateY(0);
    }
    10% {
      transform: scale(var(--bounce-start-scale-x, 1.1), var(--bounce-start-scale-y, 0.9)) translateY(0);
    }
    30% {
      transform: scale(var(--bounce-jump-scale-x, 0.9), var(--bounce-jump-scale-y, 1.1))
        translateY(var(--bounce-height, -0.5em));
    }
    50% {
      transform: scale(var(--bounce-land-scale-x, 1.05), var(--bounce-land-scale-y, 0.95)) translateY(0);
    }
    57% {
      transform: scale(1, 1) translateY(var(--bounce-rebound, -0.125em));
    }
    64% {
      transform: scale(1, 1) translateY(0);
    }
    100% {
      transform: scale(1, 1) translateY(0);
    }
  }

  @keyframes flip {
    50% {
      transform: rotate3d(var(--flip-x, 0), var(--flip-y, 1), var(--flip-z, 0), var(--flip-angle, -180deg));
    }
  }

  @keyframes shake {
    0% {
      transform: rotate(-15deg);
    }
    4% {
      transform: rotate(15deg);
    }
    8%,
    24% {
      transform: rotate(-18deg);
    }
    12%,
    28% {
      transform: rotate(18deg);
    }
    16% {
      transform: rotate(-22deg);
    }
    20% {
      transform: rotate(22deg);
    }
    32% {
      transform: rotate(-12deg);
    }
    36% {
      transform: rotate(12deg);
    }
    40%,
    100% {
      transform: rotate(0deg);
    }
  }

  @keyframes spin {
    0% {
      transform: rotate(0deg);
    }
    100% {
      transform: rotate(360deg);
    }
  }

  @keyframes spin-pulse {
    0% {
      transform: rotate(0deg);
    }
    100% {
      transform: rotate(360deg);
    }
  }
`,tt=class extends Event{constructor(){super(`wa-load`,{bubbles:!0,cancelable:!1,composed:!0})}};function nt(e,t){let n={waitUntilFirstUpdate:!1,...t};return(t,r)=>{let{update:i}=t,a=Array.isArray(e)?e:[e];t.update=function(e){a.forEach(t=>{let i=t;if(e.has(i)){let t=e.get(i),a=this[i];t!==a&&(!n.waitUntilFirstUpdate||this.hasUpdated)&&this[r](t,a)}}),i.call(this,e)}}}var rt=b`
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
`,it,at=class extends w{constructor(){super(),qe(this,it,!1),this.initialReflectedProperties=new Map,this.didSSR=!!this.shadowRoot,this.customStates={set:(e,t)=>{if(this.internals?.states)try{t?this.internals.states.add(e):this.internals.states.delete(e)}catch(e){if(String(e).includes(`must start with '--'`))console.error(`Your browser implements an outdated version of CustomStateSet. Consider using a polyfill`);else throw e}},has:e=>{if(!this.internals?.states)return!1;try{return this.internals.states.has(e)}catch{return!1}}};try{this.internals=this.attachInternals()}catch{console.error(`Element internals are not supported in your browser. Consider using a polyfill`)}this.customStates.set(`wa-defined`,!0);let e=this.constructor;for(let[t,n]of e.elementProperties)n.default===`inherit`&&n.initial!==void 0&&typeof t==`string`&&this.customStates.set(`initial-${t}-${n.initial}`,!0)}static get styles(){return[rt,...Array.isArray(this.css)?this.css:this.css?[this.css]:[]]}connectedCallback(){super.connectedCallback(),this.shadowRoot?.prepend(document.createComment(` Web Awesome: https://webawesome.com/docs/components/${this.localName.replace(`wa-`,``)} `))}attributeChangedCallback(e,t,n){Ke(this,it)||(this.constructor.elementProperties.forEach((e,t)=>{e.reflect&&this[t]!=null&&this.initialReflectedProperties.set(t,this[t])}),Je(this,it,!0)),super.attributeChangedCallback(e,t,n)}willUpdate(e){super.willUpdate(e),this.initialReflectedProperties.forEach((t,n)=>{e.has(n)&&this[n]==null&&(this[n]=t)})}firstUpdated(e){super.firstUpdated(e),this.didSSR&&this.shadowRoot?.querySelectorAll(`slot`).forEach(e=>{e.dispatchEvent(new Event(`slotchange`,{bubbles:!0,composed:!1,cancelable:!1}))})}update(e){try{super.update(e)}catch(e){if(this.didSSR&&!this.hasUpdated){let t=new Event(`lit-hydration-error`,{bubbles:!0,composed:!0,cancelable:!1});t.error=e,this.dispatchEvent(t)}throw e}}relayNativeEvent(e,t){e.stopImmediatePropagation(),this.dispatchEvent(new e.constructor(e.type,{...e,...t}))}};it=new WeakMap,A([T()],at.prototype,`dir`,2),A([T()],at.prototype,`lang`,2),A([T({type:Boolean,reflect:!0,attribute:`did-ssr`})],at.prototype,`didSSR`,2);var{I:ot}=_,st=e=>e===null||typeof e!=`object`&&typeof e!=`function`,ct=(e,t)=>t===void 0?e?._$litType$!==void 0:e?._$litType$===t,lt=e=>e.strings===void 0,ut=()=>document.createComment(``),dt=(e,t,n)=>{let r=e._$AA.parentNode,i=t===void 0?e._$AB:t._$AA;if(n===void 0)n=new ot(r.insertBefore(ut(),i),r.insertBefore(ut(),i),e,e.options);else{let t=n._$AB.nextSibling,a=n._$AM,o=a!==e;if(o){let t;n._$AQ?.(e),n._$AM=e,n._$AP!==void 0&&(t=e._$AU)!==a._$AU&&n._$AP(t)}if(t!==i||o){let e=n._$AA;for(;e!==t;){let t=e.nextSibling;r.insertBefore(e,i),e=t}}}return n},ft=(e,t,n=e)=>(e._$AI(t,n),e),pt={},mt=(e,t=pt)=>e._$AH=t,ht=e=>e._$AH,gt=e=>{e._$AR(),e._$AA.remove()},_t=Symbol(),vt=Symbol(),yt,bt=new Map,xt=class extends at{constructor(){super(...arguments),this.svg=null,this.autoWidth=!1,this.swapOpacity=!1,this.label=``,this.library=`default`,this.rotate=0,this.resolveIcon=async(e,t)=>{let n;if(t?.spriteSheet){this.hasUpdated||await this.updateComplete,this.svg=v`<svg part="svg">
        <use part="use" href="${e}"></use>
      </svg>`,await this.updateComplete;let n=this.shadowRoot.querySelector(`[part='svg']`);return typeof t.mutator==`function`&&t.mutator(n,this),this.svg}try{if(n=await fetch(e,{mode:`cors`}),!n.ok)return n.status===410?_t:vt}catch{return vt}try{let e=document.createElement(`div`);e.innerHTML=await n.text();let t=e.firstElementChild;if(t?.tagName?.toLowerCase()!==`svg`)return _t;yt||=new DOMParser;let r=yt.parseFromString(t.outerHTML,`text/html`).body.querySelector(`svg`);return r?(r.part.add(`svg`),document.adoptNode(r)):_t}catch{return _t}}}connectedCallback(){super.connectedCallback(),Ie(this)}firstUpdated(e){super.firstUpdated(e),this.hasAttribute(`rotate`)&&this.style.setProperty(`--rotate-angle`,`${this.rotate}deg`),this.setIcon()}disconnectedCallback(){super.disconnectedCallback(),Le(this)}async getIconSource(){let e=Re(this.library),t=this.family||Ve();if(this.name&&e){let n;try{n=await e.resolver(this.name,t,this.variant,this.autoWidth)}catch{n=void 0}return{url:n,fromLibrary:!0}}return{url:this.src,fromLibrary:!1}}handleLabelChange(){typeof this.label==`string`&&this.label.length>0?(this.setAttribute(`role`,`img`),this.setAttribute(`aria-label`,this.label),this.removeAttribute(`aria-hidden`)):(this.removeAttribute(`role`),this.removeAttribute(`aria-label`),this.setAttribute(`aria-hidden`,`true`))}async setIcon(){let{url:e,fromLibrary:t}=await this.getIconSource(),n=t?Re(this.library):void 0;if(!e){this.svg=null;return}let r=bt.get(e);r||(r=this.resolveIcon(e,n),bt.set(e,r));let i=await r;if(i===vt&&bt.delete(e),e===(await this.getIconSource()).url){if(ct(i)){this.svg=i;return}switch(i){case vt:case _t:this.svg=null,this.dispatchEvent(new $e);break;default:this.svg=i.cloneNode(!0),n?.mutator?.(this.svg,this),this.dispatchEvent(new tt)}}}updated(e){super.updated(e);let t=Re(this.library);this.hasAttribute(`rotate`)&&this.style.setProperty(`--rotate-angle`,`${this.rotate}deg`);let n=this.shadowRoot?.querySelector(`svg`);n&&t?.mutator?.(n,this)}render(){return this.hasUpdated?this.svg:v`<svg part="svg" width="16" height="16"></svg>`}};xt.css=et,A([E()],xt.prototype,`svg`,2),A([T({reflect:!0})],xt.prototype,`name`,2),A([T({reflect:!0})],xt.prototype,`family`,2),A([T({reflect:!0})],xt.prototype,`variant`,2),A([T({attribute:`auto-width`,type:Boolean,reflect:!0})],xt.prototype,`autoWidth`,2),A([T({attribute:`swap-opacity`,type:Boolean,reflect:!0})],xt.prototype,`swapOpacity`,2),A([T()],xt.prototype,`src`,2),A([T()],xt.prototype,`label`,2),A([T({reflect:!0})],xt.prototype,`library`,2),A([T({type:Number,reflect:!0})],xt.prototype,`rotate`,2),A([T({type:String,reflect:!0})],xt.prototype,`flip`,2),A([T({type:String,reflect:!0})],xt.prototype,`animation`,2),A([nt(`label`)],xt.prototype,`handleLabelChange`,1),A([nt([`family`,`name`,`library`,`variant`,`src`,`autoWidth`,`swapOpacity`],{waitUntilFirstUpdate:!0})],xt.prototype,`setIcon`,1),xt=A([D(`wa-icon`)],xt);var St=class extends xt{constructor(...e){super(...e),this.appearance=`plain`}connectedCallback(){super.connectedCallback(),this.appearance===`badge`&&!this.getAttribute(`data-color`)&&this.setAttribute(`data-color`,`warning`)}static get styles(){return[xt.styles,b`
        :host {
          font-size: 0.8em;
        }

        :host([appearance~='badge']) {
          border: 1px solid var(--c-color-border-quiet);
          color: var(--c-color-on-quiet);
          background-color: var(--c-color-fill-quiet);
          border-radius: var(--c-radius-sm);
          width: 1.6em;
          height: 1.6em;

          svg {
            width: 0.9em;
          }
        }
      `]}};g([T({reflect:!0})],St.prototype,`appearance`,void 0),customElements.get(`craft-icon`)||customElements.define(`craft-icon`,St);var Ct=b`
  :host {
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

  .tooltip {
    --popup-border-width: var(--wa-tooltip-border-width);

    &::part(arrow) {
      border-bottom: var(--wa-tooltip-border-width) var(--wa-tooltip-border-style) var(--wa-tooltip-border-color);
      border-right: var(--wa-tooltip-border-width) var(--wa-tooltip-border-style) var(--wa-tooltip-border-color);
    }
  }
`,wt=class extends Event{constructor(){super(`wa-reposition`,{bubbles:!0,cancelable:!1,composed:!0})}},Tt=b`
  :host {
    --arrow-color: black;
    --arrow-size: var(--wa-tooltip-arrow-size);
    --popup-border-width: 0px;
    --show-duration: 100ms;
    --hide-duration: 100ms;

    /*
     * These properties are computed to account for the arrow's dimensions after being rotated 45º. The constant
     * 0.7071 is derived from sin(45) to calculate the length of the arrow after rotation.
     *
     * The diamond will be translated inward by --arrow-base-offset, the border thickness, to centralise it on
     * the inner edge of the popup border. This also means we need to increase the size of the arrow by the
     * same amount to compensate.
     *
     * A diamond shaped clipping mask is used to avoid overlap of popup content. This extends slightly inward so
     * the popup border is covered with no sub-pixel rounding artifacts. The diamond corners are mitred at 22.5º
     * to properly merge any arrow border with the popup border. The constant 1.4142 is derived from 1 + tan(22.5).
     *
     */
    --arrow-base-offset: var(--popup-border-width);
    --arrow-size-diagonal: calc((var(--arrow-size) + var(--arrow-base-offset)) * 0.7071);
    --arrow-padding-offset: calc(var(--arrow-size-diagonal) - var(--arrow-size));
    --arrow-size-div: calc(var(--arrow-size-diagonal) * 2);
    --arrow-clipping-corner: calc(var(--arrow-base-offset) * 1.4142);

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
    width: var(--arrow-size-div);
    height: var(--arrow-size-div);
    background: var(--arrow-color);
    z-index: 3;
    clip-path: polygon(
      var(--arrow-clipping-corner) 100%,
      var(--arrow-base-offset) calc(100% - var(--arrow-base-offset)),
      calc(var(--arrow-base-offset) - 2px) calc(100% - var(--arrow-base-offset)),
      calc(100% - var(--arrow-base-offset)) calc(var(--arrow-base-offset) - 2px),
      calc(100% - var(--arrow-base-offset)) var(--arrow-base-offset),
      100% var(--arrow-clipping-corner),
      100% 100%
    );
    rotate: 45deg;
  }

  :host([data-current-placement|='left']) .arrow {
    rotate: -45deg;
  }

  :host([data-current-placement|='right']) .arrow {
    rotate: 135deg;
  }

  :host([data-current-placement|='bottom']) .arrow {
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
`,Et=Math.min,Dt=Math.max,Ot=Math.round,kt=Math.floor,At=e=>({x:e,y:e}),jt={left:`right`,right:`left`,bottom:`top`,top:`bottom`};function Mt(e,t,n){return Dt(e,Et(t,n))}function Nt(e,t){return typeof e==`function`?e(t):e}function Pt(e){return e.split(`-`)[0]}function Ft(e){return e.split(`-`)[1]}function It(e){return e===`x`?`y`:`x`}function Lt(e){return e===`y`?`height`:`width`}function Rt(e){let t=e[0];return t===`t`||t===`b`?`y`:`x`}function zt(e){return It(Rt(e))}function Bt(e,t,n){n===void 0&&(n=!1);let r=Ft(e),i=zt(e),a=Lt(i),o=i===`x`?r===(n?`end`:`start`)?`right`:`left`:r===`start`?`bottom`:`top`;return t.reference[a]>t.floating[a]&&(o=Yt(o)),[o,Yt(o)]}function Vt(e){let t=Yt(e);return[Ht(e),t,Ht(t)]}function Ht(e){return e.includes(`start`)?e.replace(`start`,`end`):e.replace(`end`,`start`)}var Ut=[`left`,`right`],Wt=[`right`,`left`],Gt=[`top`,`bottom`],Kt=[`bottom`,`top`];function qt(e,t,n){switch(e){case`top`:case`bottom`:return n?t?Wt:Ut:t?Ut:Wt;case`left`:case`right`:return t?Gt:Kt;default:return[]}}function Jt(e,t,n,r){let i=Ft(e),a=qt(Pt(e),n===`start`,r);return i&&(a=a.map(e=>e+`-`+i),t&&(a=a.concat(a.map(Ht)))),a}function Yt(e){let t=Pt(e);return jt[t]+e.slice(t.length)}function Xt(e){return{top:0,right:0,bottom:0,left:0,...e}}function Zt(e){return typeof e==`number`?{top:e,right:e,bottom:e,left:e}:Xt(e)}function Qt(e){let{x:t,y:n,width:r,height:i}=e;return{width:r,height:i,top:n,left:t,right:t+r,bottom:n+i,x:t,y:n}}function $t(e,t,n){let{reference:r,floating:i}=e,a=Rt(t),o=zt(t),s=Lt(o),c=Pt(t),l=a===`y`,u=r.x+r.width/2-i.width/2,d=r.y+r.height/2-i.height/2,f=r[s]/2-i[s]/2,p;switch(c){case`top`:p={x:u,y:r.y-i.height};break;case`bottom`:p={x:u,y:r.y+r.height};break;case`right`:p={x:r.x+r.width,y:d};break;case`left`:p={x:r.x-i.width,y:d};break;default:p={x:r.x,y:r.y}}switch(Ft(t)){case`start`:p[o]-=f*(n&&l?-1:1);break;case`end`:p[o]+=f*(n&&l?-1:1);break}return p}async function en(e,t){t===void 0&&(t={});let{x:n,y:r,platform:i,rects:a,elements:o,strategy:s}=e,{boundary:c=`clippingAncestors`,rootBoundary:l=`viewport`,elementContext:u=`floating`,altBoundary:d=!1,padding:f=0}=Nt(t,e),p=Zt(f),m=o[d?u===`floating`?`reference`:`floating`:u],h=Qt(await i.getClippingRect({element:await(i.isElement==null?void 0:i.isElement(m))??!0?m:m.contextElement||await(i.getDocumentElement==null?void 0:i.getDocumentElement(o.floating)),boundary:c,rootBoundary:l,strategy:s})),g=u===`floating`?{x:n,y:r,width:a.floating.width,height:a.floating.height}:a.reference,_=await(i.getOffsetParent==null?void 0:i.getOffsetParent(o.floating)),v=await(i.isElement==null?void 0:i.isElement(_))&&await(i.getScale==null?void 0:i.getScale(_))||{x:1,y:1},y=Qt(i.convertOffsetParentRelativeRectToViewportRelativeRect?await i.convertOffsetParentRelativeRectToViewportRelativeRect({elements:o,rect:g,offsetParent:_,strategy:s}):g);return{top:(h.top-y.top+p.top)/v.y,bottom:(y.bottom-h.bottom+p.bottom)/v.y,left:(h.left-y.left+p.left)/v.x,right:(y.right-h.right+p.right)/v.x}}var tn=50,nn=async(e,t,n)=>{let{placement:r=`bottom`,strategy:i=`absolute`,middleware:a=[],platform:o}=n,s=o.detectOverflow?o:{...o,detectOverflow:en},c=await(o.isRTL==null?void 0:o.isRTL(t)),l=await o.getElementRects({reference:e,floating:t,strategy:i}),{x:u,y:d}=$t(l,r,c),f=r,p=0,m={};for(let n=0;n<a.length;n++){let h=a[n];if(!h)continue;let{name:g,fn:_}=h,{x:v,y,data:b,reset:x}=await _({x:u,y:d,initialPlacement:r,placement:f,strategy:i,middlewareData:m,rects:l,platform:s,elements:{reference:e,floating:t}});u=v??u,d=y??d,m[g]={...m[g],...b},x&&p<tn&&(p++,typeof x==`object`&&(x.placement&&(f=x.placement),x.rects&&(l=x.rects===!0?await o.getElementRects({reference:e,floating:t,strategy:i}):x.rects),{x:u,y:d}=$t(l,f,c)),n=-1)}return{x:u,y:d,placement:f,strategy:i,middlewareData:m}},rn=e=>({name:`arrow`,options:e,async fn(t){let{x:n,y:r,placement:i,rects:a,platform:o,elements:s,middlewareData:c}=t,{element:l,padding:u=0}=Nt(e,t)||{};if(l==null)return{};let d=Zt(u),f={x:n,y:r},p=zt(i),m=Lt(p),h=await o.getDimensions(l),g=p===`y`,_=g?`top`:`left`,v=g?`bottom`:`right`,y=g?`clientHeight`:`clientWidth`,b=a.reference[m]+a.reference[p]-f[p]-a.floating[m],x=f[p]-a.reference[p],S=await(o.getOffsetParent==null?void 0:o.getOffsetParent(l)),C=S?S[y]:0;(!C||!await(o.isElement==null?void 0:o.isElement(S)))&&(C=s.floating[y]||a.floating[m]);let w=b/2-x/2,T=C/2-h[m]/2-1,E=Et(d[_],T),D=Et(d[v],T),O=E,ee=C-h[m]-D,te=C/2-h[m]/2+w,ne=Mt(O,te,ee),re=!c.arrow&&Ft(i)!=null&&te!==ne&&a.reference[m]/2-(te<O?E:D)-h[m]/2<0,ie=re?te<O?te-O:te-ee:0;return{[p]:f[p]+ie,data:{[p]:ne,centerOffset:te-ne-ie,...re&&{alignmentOffset:ie}},reset:re}}}),an=function(e){return e===void 0&&(e={}),{name:`flip`,options:e,async fn(t){var n;let{placement:r,middlewareData:i,rects:a,initialPlacement:o,platform:s,elements:c}=t,{mainAxis:l=!0,crossAxis:u=!0,fallbackPlacements:d,fallbackStrategy:f=`bestFit`,fallbackAxisSideDirection:p=`none`,flipAlignment:m=!0,...h}=Nt(e,t);if((n=i.arrow)!=null&&n.alignmentOffset)return{};let g=Pt(r),_=Rt(o),v=Pt(o)===o,y=await(s.isRTL==null?void 0:s.isRTL(c.floating)),b=d||(v||!m?[Yt(o)]:Vt(o)),x=p!==`none`;!d&&x&&b.push(...Jt(o,m,p,y));let S=[o,...b],C=await s.detectOverflow(t,h),w=[],T=i.flip?.overflows||[];if(l&&w.push(C[g]),u){let e=Bt(r,a,y);w.push(C[e[0]],C[e[1]])}if(T=[...T,{placement:r,overflows:w}],!w.every(e=>e<=0)){let e=(i.flip?.index||0)+1,t=S[e];if(t&&(!(u===`alignment`&&_!==Rt(t))||T.every(e=>Rt(e.placement)===_?e.overflows[0]>0:!0)))return{data:{index:e,overflows:T},reset:{placement:t}};let n=T.filter(e=>e.overflows[0]<=0).sort((e,t)=>e.overflows[1]-t.overflows[1])[0]?.placement;if(!n)switch(f){case`bestFit`:{let e=T.filter(e=>{if(x){let t=Rt(e.placement);return t===_||t===`y`}return!0}).map(e=>[e.placement,e.overflows.filter(e=>e>0).reduce((e,t)=>e+t,0)]).sort((e,t)=>e[1]-t[1])[0]?.[0];e&&(n=e);break}case`initialPlacement`:n=o;break}if(r!==n)return{reset:{placement:n}}}return{}}}},on=new Set([`left`,`top`]);async function sn(e,t){let{placement:n,platform:r,elements:i}=e,a=await(r.isRTL==null?void 0:r.isRTL(i.floating)),o=Pt(n),s=Ft(n),c=Rt(n)===`y`,l=on.has(o)?-1:1,u=a&&c?-1:1,d=Nt(t,e),{mainAxis:f,crossAxis:p,alignmentAxis:m}=typeof d==`number`?{mainAxis:d,crossAxis:0,alignmentAxis:null}:{mainAxis:d.mainAxis||0,crossAxis:d.crossAxis||0,alignmentAxis:d.alignmentAxis};return s&&typeof m==`number`&&(p=s===`end`?m*-1:m),c?{x:p*u,y:f*l}:{x:f*l,y:p*u}}var cn=function(e){return e===void 0&&(e=0),{name:`offset`,options:e,async fn(t){var n;let{x:r,y:i,placement:a,middlewareData:o}=t,s=await sn(t,e);return a===o.offset?.placement&&(n=o.arrow)!=null&&n.alignmentOffset?{}:{x:r+s.x,y:i+s.y,data:{...s,placement:a}}}}},ln=function(e){return e===void 0&&(e={}),{name:`shift`,options:e,async fn(t){let{x:n,y:r,placement:i,platform:a}=t,{mainAxis:o=!0,crossAxis:s=!1,limiter:c={fn:e=>{let{x:t,y:n}=e;return{x:t,y:n}}},...l}=Nt(e,t),u={x:n,y:r},d=await a.detectOverflow(t,l),f=Rt(Pt(i)),p=It(f),m=u[p],h=u[f];if(o){let e=p===`y`?`top`:`left`,t=p===`y`?`bottom`:`right`,n=m+d[e],r=m-d[t];m=Mt(n,m,r)}if(s){let e=f===`y`?`top`:`left`,t=f===`y`?`bottom`:`right`,n=h+d[e],r=h-d[t];h=Mt(n,h,r)}let g=c.fn({...t,[p]:m,[f]:h});return{...g,data:{x:g.x-n,y:g.y-r,enabled:{[p]:o,[f]:s}}}}}},un=function(e){return e===void 0&&(e={}),{name:`size`,options:e,async fn(t){var n,r;let{placement:i,rects:a,platform:o,elements:s}=t,{apply:c=()=>{},...l}=Nt(e,t),u=await o.detectOverflow(t,l),d=Pt(i),f=Ft(i),p=Rt(i)===`y`,{width:m,height:h}=a.floating,g,_;d===`top`||d===`bottom`?(g=d,_=f===(await(o.isRTL==null?void 0:o.isRTL(s.floating))?`start`:`end`)?`left`:`right`):(_=d,g=f===`end`?`top`:`bottom`);let v=h-u.top-u.bottom,y=m-u.left-u.right,b=Et(h-u[g],v),x=Et(m-u[_],y),S=!t.middlewareData.shift,C=b,w=x;if((n=t.middlewareData.shift)!=null&&n.enabled.x&&(w=y),(r=t.middlewareData.shift)!=null&&r.enabled.y&&(C=v),S&&!f){let e=Dt(u.left,0),t=Dt(u.right,0),n=Dt(u.top,0),r=Dt(u.bottom,0);p?w=m-2*(e!==0||t!==0?e+t:Dt(u.left,u.right)):C=h-2*(n!==0||r!==0?n+r:Dt(u.top,u.bottom))}await c({...t,availableWidth:w,availableHeight:C});let T=await o.getDimensions(s.floating);return m!==T.width||h!==T.height?{reset:{rects:!0}}:{}}}};function dn(){return typeof window<`u`}function fn(e){return hn(e)?(e.nodeName||``).toLowerCase():`#document`}function pn(e){var t;return(e==null||(t=e.ownerDocument)==null?void 0:t.defaultView)||window}function mn(e){return((hn(e)?e.ownerDocument:e.document)||window.document)?.documentElement}function hn(e){return dn()?e instanceof Node||e instanceof pn(e).Node:!1}function gn(e){return dn()?e instanceof Element||e instanceof pn(e).Element:!1}function _n(e){return dn()?e instanceof HTMLElement||e instanceof pn(e).HTMLElement:!1}function vn(e){return!dn()||typeof ShadowRoot>`u`?!1:e instanceof ShadowRoot||e instanceof pn(e).ShadowRoot}function yn(e){let{overflow:t,overflowX:n,overflowY:r,display:i}=An(e);return/auto|scroll|overlay|hidden|clip/.test(t+r+n)&&i!==`inline`&&i!==`contents`}function bn(e){return/^(table|td|th)$/.test(fn(e))}function xn(e){try{if(e.matches(`:popover-open`))return!0}catch{}try{return e.matches(`:modal`)}catch{return!1}}var Sn=/transform|translate|scale|rotate|perspective|filter/,Cn=/paint|layout|strict|content/,wn=e=>!!e&&e!==`none`,Tn;function En(e){let t=gn(e)?An(e):e;return wn(t.transform)||wn(t.translate)||wn(t.scale)||wn(t.rotate)||wn(t.perspective)||!On()&&(wn(t.backdropFilter)||wn(t.filter))||Sn.test(t.willChange||``)||Cn.test(t.contain||``)}function Dn(e){let t=Mn(e);for(;_n(t)&&!kn(t);){if(En(t))return t;if(xn(t))return null;t=Mn(t)}return null}function On(){return Tn??=typeof CSS<`u`&&CSS.supports&&CSS.supports(`-webkit-backdrop-filter`,`none`),Tn}function kn(e){return/^(html|body|#document)$/.test(fn(e))}function An(e){return pn(e).getComputedStyle(e)}function jn(e){return gn(e)?{scrollLeft:e.scrollLeft,scrollTop:e.scrollTop}:{scrollLeft:e.scrollX,scrollTop:e.scrollY}}function Mn(e){if(fn(e)===`html`)return e;let t=e.assignedSlot||e.parentNode||vn(e)&&e.host||mn(e);return vn(t)?t.host:t}function Nn(e){let t=Mn(e);return kn(t)?e.ownerDocument?e.ownerDocument.body:e.body:_n(t)&&yn(t)?t:Nn(t)}function Pn(e,t,n){t===void 0&&(t=[]),n===void 0&&(n=!0);let r=Nn(e),i=r===e.ownerDocument?.body,a=pn(r);if(i){let e=Fn(a);return t.concat(a,a.visualViewport||[],yn(r)?r:[],e&&n?Pn(e):[])}else return t.concat(r,Pn(r,[],n))}function Fn(e){return e.parent&&Object.getPrototypeOf(e.parent)?e.frameElement:null}function In(e){let t=An(e),n=parseFloat(t.width)||0,r=parseFloat(t.height)||0,i=_n(e),a=i?e.offsetWidth:n,o=i?e.offsetHeight:r,s=Ot(n)!==a||Ot(r)!==o;return s&&(n=a,r=o),{width:n,height:r,$:s}}function Ln(e){return gn(e)?e:e.contextElement}function Rn(e){let t=Ln(e);if(!_n(t))return At(1);let n=t.getBoundingClientRect(),{width:r,height:i,$:a}=In(t),o=(a?Ot(n.width):n.width)/r,s=(a?Ot(n.height):n.height)/i;return(!o||!Number.isFinite(o))&&(o=1),(!s||!Number.isFinite(s))&&(s=1),{x:o,y:s}}var zn=At(0);function Bn(e){let t=pn(e);return!On()||!t.visualViewport?zn:{x:t.visualViewport.offsetLeft,y:t.visualViewport.offsetTop}}function Vn(e,t,n){return t===void 0&&(t=!1),!n||t&&n!==pn(e)?!1:t}function Hn(e,t,n,r){t===void 0&&(t=!1),n===void 0&&(n=!1);let i=e.getBoundingClientRect(),a=Ln(e),o=At(1);t&&(r?gn(r)&&(o=Rn(r)):o=Rn(e));let s=Vn(a,n,r)?Bn(a):At(0),c=(i.left+s.x)/o.x,l=(i.top+s.y)/o.y,u=i.width/o.x,d=i.height/o.y;if(a){let e=pn(a),t=r&&gn(r)?pn(r):r,n=e,i=Fn(n);for(;i&&r&&t!==n;){let e=Rn(i),t=i.getBoundingClientRect(),r=An(i),a=t.left+(i.clientLeft+parseFloat(r.paddingLeft))*e.x,o=t.top+(i.clientTop+parseFloat(r.paddingTop))*e.y;c*=e.x,l*=e.y,u*=e.x,d*=e.y,c+=a,l+=o,n=pn(i),i=Fn(n)}}return Qt({width:u,height:d,x:c,y:l})}function Un(e,t){let n=jn(e).scrollLeft;return t?t.left+n:Hn(mn(e)).left+n}function Wn(e,t){let n=e.getBoundingClientRect();return{x:n.left+t.scrollLeft-Un(e,n),y:n.top+t.scrollTop}}function Gn(e){let{elements:t,rect:n,offsetParent:r,strategy:i}=e,a=i===`fixed`,o=mn(r),s=t?xn(t.floating):!1;if(r===o||s&&a)return n;let c={scrollLeft:0,scrollTop:0},l=At(1),u=At(0),d=_n(r);if((d||!d&&!a)&&((fn(r)!==`body`||yn(o))&&(c=jn(r)),d)){let e=Hn(r);l=Rn(r),u.x=e.x+r.clientLeft,u.y=e.y+r.clientTop}let f=o&&!d&&!a?Wn(o,c):At(0);return{width:n.width*l.x,height:n.height*l.y,x:n.x*l.x-c.scrollLeft*l.x+u.x+f.x,y:n.y*l.y-c.scrollTop*l.y+u.y+f.y}}function Kn(e){return Array.from(e.getClientRects())}function qn(e){let t=mn(e),n=jn(e),r=e.ownerDocument.body,i=Dt(t.scrollWidth,t.clientWidth,r.scrollWidth,r.clientWidth),a=Dt(t.scrollHeight,t.clientHeight,r.scrollHeight,r.clientHeight),o=-n.scrollLeft+Un(e),s=-n.scrollTop;return An(r).direction===`rtl`&&(o+=Dt(t.clientWidth,r.clientWidth)-i),{width:i,height:a,x:o,y:s}}var Jn=25;function Yn(e,t){let n=pn(e),r=mn(e),i=n.visualViewport,a=r.clientWidth,o=r.clientHeight,s=0,c=0;if(i){a=i.width,o=i.height;let e=On();(!e||e&&t===`fixed`)&&(s=i.offsetLeft,c=i.offsetTop)}let l=Un(r);if(l<=0){let e=r.ownerDocument,t=e.body,n=getComputedStyle(t),i=e.compatMode===`CSS1Compat`&&parseFloat(n.marginLeft)+parseFloat(n.marginRight)||0,o=Math.abs(r.clientWidth-t.clientWidth-i);o<=Jn&&(a-=o)}else l<=Jn&&(a+=l);return{width:a,height:o,x:s,y:c}}function Xn(e,t){let n=Hn(e,!0,t===`fixed`),r=n.top+e.clientTop,i=n.left+e.clientLeft,a=_n(e)?Rn(e):At(1);return{width:e.clientWidth*a.x,height:e.clientHeight*a.y,x:i*a.x,y:r*a.y}}function Zn(e,t,n){let r;if(t===`viewport`)r=Yn(e,n);else if(t===`document`)r=qn(mn(e));else if(gn(t))r=Xn(t,n);else{let n=Bn(e);r={x:t.x-n.x,y:t.y-n.y,width:t.width,height:t.height}}return Qt(r)}function Qn(e,t){let n=Mn(e);return n===t||!gn(n)||kn(n)?!1:An(n).position===`fixed`||Qn(n,t)}function $n(e,t){let n=t.get(e);if(n)return n;let r=Pn(e,[],!1).filter(e=>gn(e)&&fn(e)!==`body`),i=null,a=An(e).position===`fixed`,o=a?Mn(e):e;for(;gn(o)&&!kn(o);){let t=An(o),n=En(o);!n&&t.position===`fixed`&&(i=null),(a?!n&&!i:!n&&t.position===`static`&&i&&(i.position===`absolute`||i.position===`fixed`)||yn(o)&&!n&&Qn(e,o))?r=r.filter(e=>e!==o):i=t,o=Mn(o)}return t.set(e,r),r}function er(e){let{element:t,boundary:n,rootBoundary:r,strategy:i}=e,a=[...n===`clippingAncestors`?xn(t)?[]:$n(t,this._c):[].concat(n),r],o=Zn(t,a[0],i),s=o.top,c=o.right,l=o.bottom,u=o.left;for(let e=1;e<a.length;e++){let n=Zn(t,a[e],i);s=Dt(n.top,s),c=Et(n.right,c),l=Et(n.bottom,l),u=Dt(n.left,u)}return{width:c-u,height:l-s,x:u,y:s}}function tr(e){let{width:t,height:n}=In(e);return{width:t,height:n}}function nr(e,t,n){let r=_n(t),i=mn(t),a=n===`fixed`,o=Hn(e,!0,a,t),s={scrollLeft:0,scrollTop:0},c=At(0);function l(){c.x=Un(i)}if(r||!r&&!a)if((fn(t)!==`body`||yn(i))&&(s=jn(t)),r){let e=Hn(t,!0,a,t);c.x=e.x+t.clientLeft,c.y=e.y+t.clientTop}else i&&l();a&&!r&&i&&l();let u=i&&!r&&!a?Wn(i,s):At(0);return{x:o.left+s.scrollLeft-c.x-u.x,y:o.top+s.scrollTop-c.y-u.y,width:o.width,height:o.height}}function rr(e){return An(e).position===`static`}function ir(e,t){if(!_n(e)||An(e).position===`fixed`)return null;if(t)return t(e);let n=e.offsetParent;return mn(e)===n&&(n=n.ownerDocument.body),n}function ar(e,t){let n=pn(e);if(xn(e))return n;if(!_n(e)){let t=Mn(e);for(;t&&!kn(t);){if(gn(t)&&!rr(t))return t;t=Mn(t)}return n}let r=ir(e,t);for(;r&&bn(r)&&rr(r);)r=ir(r,t);return r&&kn(r)&&rr(r)&&!En(r)?n:r||Dn(e)||n}var or=async function(e){let t=this.getOffsetParent||ar,n=this.getDimensions,r=await n(e.floating);return{reference:nr(e.reference,await t(e.floating),e.strategy),floating:{x:0,y:0,width:r.width,height:r.height}}};function sr(e){return An(e).direction===`rtl`}var cr={convertOffsetParentRelativeRectToViewportRelativeRect:Gn,getDocumentElement:mn,getClippingRect:er,getOffsetParent:ar,getElementRects:or,getClientRects:Kn,getDimensions:tr,getScale:Rn,isElement:gn,isRTL:sr};function lr(e,t){return e.x===t.x&&e.y===t.y&&e.width===t.width&&e.height===t.height}function ur(e,t){let n=null,r,i=mn(e);function a(){var e;clearTimeout(r),(e=n)==null||e.disconnect(),n=null}function o(s,c){s===void 0&&(s=!1),c===void 0&&(c=1),a();let l=e.getBoundingClientRect(),{left:u,top:d,width:f,height:p}=l;if(s||t(),!f||!p)return;let m=kt(d),h=kt(i.clientWidth-(u+f)),g=kt(i.clientHeight-(d+p)),_=kt(u),v={rootMargin:-m+`px `+-h+`px `+-g+`px `+-_+`px`,threshold:Dt(0,Et(1,c))||1},y=!0;function b(t){let n=t[0].intersectionRatio;if(n!==c){if(!y)return o();n?o(!1,n):r=setTimeout(()=>{o(!1,1e-7)},1e3)}n===1&&!lr(l,e.getBoundingClientRect())&&o(),y=!1}try{n=new IntersectionObserver(b,{...v,root:i.ownerDocument})}catch{n=new IntersectionObserver(b,v)}n.observe(e)}return o(!0),a}function dr(e,t,n,r){r===void 0&&(r={});let{ancestorScroll:i=!0,ancestorResize:a=!0,elementResize:o=typeof ResizeObserver==`function`,layoutShift:s=typeof IntersectionObserver==`function`,animationFrame:c=!1}=r,l=Ln(e),u=i||a?[...l?Pn(l):[],...t?Pn(t):[]]:[];u.forEach(e=>{i&&e.addEventListener(`scroll`,n,{passive:!0}),a&&e.addEventListener(`resize`,n)});let d=l&&s?ur(l,n):null,f=-1,p=null;o&&(p=new ResizeObserver(e=>{let[r]=e;r&&r.target===l&&p&&t&&(p.unobserve(t),cancelAnimationFrame(f),f=requestAnimationFrame(()=>{var e;(e=p)==null||e.observe(t)})),n()}),l&&!c&&p.observe(l),t&&p.observe(t));let m,h=c?Hn(e):null;c&&g();function g(){let t=Hn(e);h&&!lr(h,t)&&n(),h=t,m=requestAnimationFrame(g)}return n(),()=>{var e;u.forEach(e=>{i&&e.removeEventListener(`scroll`,n),a&&e.removeEventListener(`resize`,n)}),d?.(),(e=p)==null||e.disconnect(),p=null,c&&cancelAnimationFrame(m)}}var fr=cn,pr=ln,mr=an,hr=un,gr=rn,_r=(e,t,n)=>{let r=new Map,i={platform:cr,...n},a={...i.platform,_c:r};return nn(e,t,{...i,platform:a})};function vr(e){return br(e)}function yr(e){return e.assignedSlot?e.assignedSlot:e.parentNode instanceof ShadowRoot?e.parentNode.host:e.parentNode}function br(e){for(let t=e;t;t=yr(t))if(t instanceof Element&&getComputedStyle(t).display===`none`)return null;for(let t=yr(e);t;t=yr(t)){if(!(t instanceof Element))continue;let e=getComputedStyle(t);if(e.display!==`contents`&&(e.position!==`static`||En(e)||t.tagName===`BODY`))return t}return null}function xr(e){return typeof e==`object`&&!!e&&`getBoundingClientRect`in e&&(`contextElement`in e?e instanceof Element:!0)}var Sr=globalThis?.HTMLElement?.prototype.hasOwnProperty(`popover`),j=class extends at{constructor(){super(...arguments),this.localize=new Te(this),this.active=!1,this.placement=`top`,this.boundary=`viewport`,this.distance=0,this.skidding=0,this.arrow=!1,this.arrowPlacement=`anchor`,this.arrowPadding=10,this.flip=!1,this.flipFallbackPlacements=``,this.flipFallbackStrategy=`best-fit`,this.flipPadding=0,this.shift=!1,this.shiftPadding=0,this.autoSizePadding=0,this.hoverBridge=!1,this.updateHoverBridge=()=>{if(this.hoverBridge&&this.anchorEl&&this.popup){let e=this.anchorEl.getBoundingClientRect(),t=this.popup.getBoundingClientRect(),n=this.placement.includes(`top`)||this.placement.includes(`bottom`),r=0,i=0,a=0,o=0,s=0,c=0,l=0,u=0;n?e.top<t.top?(r=e.left,i=e.bottom,a=e.right,o=e.bottom,s=t.left,c=t.top,l=t.right,u=t.top):(r=t.left,i=t.bottom,a=t.right,o=t.bottom,s=e.left,c=e.top,l=e.right,u=e.top):e.left<t.left?(r=e.right,i=e.top,a=t.left,o=t.top,s=e.right,c=e.bottom,l=t.left,u=t.bottom):(r=t.right,i=t.top,a=e.left,o=e.top,s=t.right,c=t.bottom,l=e.left,u=e.bottom),this.style.setProperty(`--hover-bridge-top-left-x`,`${r}px`),this.style.setProperty(`--hover-bridge-top-left-y`,`${i}px`),this.style.setProperty(`--hover-bridge-top-right-x`,`${a}px`),this.style.setProperty(`--hover-bridge-top-right-y`,`${o}px`),this.style.setProperty(`--hover-bridge-bottom-left-x`,`${s}px`),this.style.setProperty(`--hover-bridge-bottom-left-y`,`${c}px`),this.style.setProperty(`--hover-bridge-bottom-right-x`,`${l}px`),this.style.setProperty(`--hover-bridge-bottom-right-y`,`${u}px`)}}}async connectedCallback(){super.connectedCallback(),await this.updateComplete,this.start()}disconnectedCallback(){super.disconnectedCallback(),this.stop()}async updated(e){super.updated(e),e.has(`active`)&&(this.active?this.start():this.stop()),e.has(`anchor`)&&this.handleAnchorChange(),this.active&&(await this.updateComplete,this.reposition())}async handleAnchorChange(){if(await this.stop(),this.anchor&&typeof this.anchor==`string`){let e=this.getRootNode();this.anchorEl=e.getElementById(this.anchor)}else this.anchor instanceof Element||xr(this.anchor)?this.anchorEl=this.anchor:this.anchorEl=this.querySelector(`[slot="anchor"]`);this.anchorEl instanceof HTMLSlotElement&&(this.anchorEl=this.anchorEl.assignedElements({flatten:!0})[0]),this.anchorEl&&this.start()}start(){!this.anchorEl||!this.active||!this.isConnected||(this.popup?.showPopover?.(),this.cleanup=dr(this.anchorEl,this.popup,()=>{this.reposition()}))}async stop(){return new Promise(e=>{this.popup?.hidePopover?.(),this.cleanup?(this.cleanup(),this.cleanup=void 0,this.removeAttribute(`data-current-placement`),this.style.removeProperty(`--auto-size-available-width`),this.style.removeProperty(`--auto-size-available-height`),requestAnimationFrame(()=>e())):e()})}reposition(){if(!this.active||!this.anchorEl||!this.popup)return;let e=[fr({mainAxis:this.distance,crossAxis:this.skidding})];this.sync?e.push(hr({apply:({rects:e})=>{let t=this.sync===`width`||this.sync===`both`,n=this.sync===`height`||this.sync===`both`;this.popup.style.width=t?`${e.reference.width}px`:``,this.popup.style.height=n?`${e.reference.height}px`:``}})):(this.popup.style.width=``,this.popup.style.height=``);let t;Sr&&!xr(this.anchor)&&this.boundary===`scroll`&&(t=Pn(this.anchorEl).filter(e=>e instanceof Element)),this.flip&&e.push(mr({boundary:this.flipBoundary||t,fallbackPlacements:this.flipFallbackPlacements,fallbackStrategy:this.flipFallbackStrategy===`best-fit`?`bestFit`:`initialPlacement`,padding:this.flipPadding})),this.shift&&e.push(pr({boundary:this.shiftBoundary||t,padding:this.shiftPadding})),this.autoSize?e.push(hr({boundary:this.autoSizeBoundary||t,padding:this.autoSizePadding,apply:({availableWidth:e,availableHeight:t})=>{this.autoSize===`vertical`||this.autoSize===`both`?this.style.setProperty(`--auto-size-available-height`,`${t}px`):this.style.removeProperty(`--auto-size-available-height`),this.autoSize===`horizontal`||this.autoSize===`both`?this.style.setProperty(`--auto-size-available-width`,`${e}px`):this.style.removeProperty(`--auto-size-available-width`)}})):(this.style.removeProperty(`--auto-size-available-width`),this.style.removeProperty(`--auto-size-available-height`)),this.arrow&&e.push(gr({element:this.arrowEl,padding:this.arrowPadding}));let n=Sr?e=>cr.getOffsetParent(e,vr):cr.getOffsetParent;_r(this.anchorEl,this.popup,{placement:this.placement,middleware:e,strategy:Sr?`absolute`:`fixed`,platform:{...cr,getOffsetParent:n}}).then(({x:e,y:t,middlewareData:n,placement:r})=>{let i=this.localize.dir()===`rtl`,a={top:`bottom`,right:`left`,bottom:`top`,left:`right`}[r.split(`-`)[0]];if(this.setAttribute(`data-current-placement`,r),Object.assign(this.popup.style,{left:`${e}px`,top:`${t}px`}),this.arrow){let e=n.arrow.x,t=n.arrow.y,r=``,o=``,s=``,c=``;if(this.arrowPlacement===`start`){let n=typeof e==`number`?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:``;r=typeof t==`number`?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:``,o=i?n:``,c=i?``:n}else if(this.arrowPlacement===`end`){let n=typeof e==`number`?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:``;o=i?``:n,c=i?n:``,s=typeof t==`number`?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:``}else this.arrowPlacement===`center`?(c=typeof e==`number`?`calc(50% - var(--arrow-size-diagonal))`:``,r=typeof t==`number`?`calc(50% - var(--arrow-size-diagonal))`:``):(c=typeof e==`number`?`${e}px`:``,r=typeof t==`number`?`${t}px`:``);Object.assign(this.arrowEl.style,{top:r,right:o,bottom:s,left:c,[a]:`calc(var(--arrow-base-offset) - var(--arrow-size-diagonal))`})}}),requestAnimationFrame(()=>this.updateHoverBridge()),this.dispatchEvent(new wt)}render(){return v`
      <slot name="anchor" @slotchange=${this.handleAnchorChange}></slot>

      <span
        part="hover-bridge"
        class=${o({"popup-hover-bridge":!0,"popup-hover-bridge-visible":this.hoverBridge&&this.active})}
      ></span>

      <div
        popover="manual"
        part="popup"
        class=${o({popup:!0,"popup-active":this.active,"popup-fixed":!Sr,"popup-has-arrow":this.arrow})}
      >
        <slot></slot>
        ${this.arrow?v`<div part="arrow" class="arrow" role="presentation"></div>`:``}
      </div>
    `}};j.css=Tt,A([O(`.popup`)],j.prototype,`popup`,2),A([O(`.arrow`)],j.prototype,`arrowEl`,2),A([T()],j.prototype,`anchor`,2),A([T({type:Boolean,reflect:!0})],j.prototype,`active`,2),A([T({reflect:!0})],j.prototype,`placement`,2),A([T()],j.prototype,`boundary`,2),A([T({type:Number})],j.prototype,`distance`,2),A([T({type:Number})],j.prototype,`skidding`,2),A([T({type:Boolean})],j.prototype,`arrow`,2),A([T({attribute:`arrow-placement`})],j.prototype,`arrowPlacement`,2),A([T({attribute:`arrow-padding`,type:Number})],j.prototype,`arrowPadding`,2),A([T({type:Boolean})],j.prototype,`flip`,2),A([T({attribute:`flip-fallback-placements`,converter:{fromAttribute:e=>e.split(` `).map(e=>e.trim()).filter(e=>e!==``),toAttribute:e=>e.join(` `)}})],j.prototype,`flipFallbackPlacements`,2),A([T({attribute:`flip-fallback-strategy`})],j.prototype,`flipFallbackStrategy`,2),A([T({type:Object})],j.prototype,`flipBoundary`,2),A([T({attribute:`flip-padding`,type:Number})],j.prototype,`flipPadding`,2),A([T({type:Boolean})],j.prototype,`shift`,2),A([T({type:Object})],j.prototype,`shiftBoundary`,2),A([T({attribute:`shift-padding`,type:Number})],j.prototype,`shiftPadding`,2),A([T({attribute:`auto-size`})],j.prototype,`autoSize`,2),A([T()],j.prototype,`sync`,2),A([T({type:Object})],j.prototype,`autoSizeBoundary`,2),A([T({attribute:`auto-size-padding`,type:Number})],j.prototype,`autoSizePadding`,2),A([T({attribute:`hover-bridge`,type:Boolean})],j.prototype,`hoverBridge`,2),j=A([D(`wa-popup`)],j);var Cr=[];function wr(e){Cr.push(e)}function Tr(e){for(let t=Cr.length-1;t>=0;t--)if(Cr[t]===e){Cr.splice(t,1);break}}function Er(e){return Cr.length>0&&Cr[Cr.length-1]===e}var Dr=class extends Event{constructor(){super(`wa-show`,{bubbles:!0,cancelable:!0,composed:!0})}},Or=class extends Event{constructor(e){super(`wa-hide`,{bubbles:!0,cancelable:!0,composed:!0}),this.detail=e}},kr=class extends Event{constructor(){super(`wa-after-hide`,{bubbles:!0,cancelable:!1,composed:!0})}},Ar=class extends Event{constructor(){super(`wa-after-show`,{bubbles:!0,cancelable:!1,composed:!0})}},jr=`useandom-26T198340PX75pxJACKVERYMINDBUSHWOLF_GQZbfghjklqvwyzrict`,Mr=(e=21)=>{let t=``,n=crypto.getRandomValues(new Uint8Array(e|=0));for(;e--;)t+=jr[n[e]&63];return t};function Nr(e=``){return`${e}${Mr()}`}function Pr(e,t){return new Promise(n=>{function r(i){i.target===e&&(e.removeEventListener(t,r),n())}e.addEventListener(t,r)})}function Fr(e,t){return new Promise(n=>{let r=new AbortController,{signal:i}=r;if(e.classList.contains(t))return;e.classList.add(t);let a=!1,o=()=>{a||(a=!0,e.classList.remove(t),n(),r.abort())};e.addEventListener(`animationend`,o,{once:!0,signal:i}),e.addEventListener(`animationcancel`,o,{once:!0,signal:i}),requestAnimationFrame(()=>{!a&&e.getAnimations().length===0&&o()})})}var Ir=class extends at{constructor(){super(...arguments),this.placement=`top`,this.disabled=!1,this.distance=8,this.open=!1,this.skidding=0,this.showDelay=150,this.hideDelay=0,this.trigger=`hover focus`,this.withoutArrow=!1,this.for=null,this.anchor=null,this.eventController=new AbortController,this.handleBlur=()=>{this.hasTrigger(`focus`)&&this.hide()},this.handleClick=()=>{this.hasTrigger(`click`)&&(this.open?this.hide():this.show())},this.handleFocus=()=>{this.hasTrigger(`focus`)&&this.show()},this.handleDocumentKeyDown=e=>{e.key===`Escape`&&this.open&&Er(this)&&(e.preventDefault(),e.stopPropagation(),this.hide())},this.handleMouseOver=()=>{this.hasTrigger(`hover`)&&(clearTimeout(this.hoverTimeout),this.hoverTimeout=window.setTimeout(()=>this.show(),this.showDelay))},this.handleMouseOut=()=>{if(this.hasTrigger(`hover`)){let e=!!this.anchor?.matches(`:hover`),t=this.matches(`:hover`);if(e||t)return;clearTimeout(this.hoverTimeout),e||t||(this.hoverTimeout=window.setTimeout(()=>{this.hide()},this.hideDelay))}}}connectedCallback(){super.connectedCallback(),this.eventController.signal.aborted&&(this.eventController=new AbortController),this.addEventListener(`mouseout`,this.handleMouseOut),this.open&&(this.open=!1,this.updateComplete.then(()=>{this.open=!0})),this.id||=Nr(`wa-tooltip-`),this.for&&this.anchor?(this.anchor=null,this.handleForChange()):this.for&&this.handleForChange()}disconnectedCallback(){super.disconnectedCallback(),document.removeEventListener(`keydown`,this.handleDocumentKeyDown),Tr(this),this.eventController.abort(),this.anchor&&this.removeFromAriaLabelledBy(this.anchor,this.id)}firstUpdated(){this.body.hidden=!this.open,this.open&&(this.popup.active=!0,this.popup.reposition())}hasTrigger(e){return this.trigger.split(` `).includes(e)}addToAriaLabelledBy(e,t){let n=(e.getAttribute(`aria-labelledby`)||``).split(/\s+/).filter(Boolean);n.includes(t)||(n.push(t),e.setAttribute(`aria-labelledby`,n.join(` `)))}removeFromAriaLabelledBy(e,t){let n=(e.getAttribute(`aria-labelledby`)||``).split(/\s+/).filter(Boolean).filter(e=>e!==t);n.length>0?e.setAttribute(`aria-labelledby`,n.join(` `)):e.removeAttribute(`aria-labelledby`)}async handleOpenChange(){if(this.open){if(this.disabled)return;let e=new Dr;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}document.addEventListener(`keydown`,this.handleDocumentKeyDown,{signal:this.eventController.signal}),wr(this),this.body.hidden=!1,this.popup.active=!0,await Fr(this.popup.popup,`show-with-scale`),this.popup.reposition(),this.dispatchEvent(new Ar)}else{let e=new Or;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}document.removeEventListener(`keydown`,this.handleDocumentKeyDown),Tr(this),await Fr(this.popup.popup,`hide-with-scale`),this.popup.active=!1,this.body.hidden=!0,this.dispatchEvent(new kr)}}handleForChange(){let e=this.getRootNode();if(!e)return;let t=this.for?e.getElementById(this.for):null,n=this.anchor;if(t===n)return;let{signal:r}=this.eventController;t&&(this.addToAriaLabelledBy(t,this.id),t.addEventListener(`blur`,this.handleBlur,{capture:!0,signal:r}),t.addEventListener(`focus`,this.handleFocus,{capture:!0,signal:r}),t.addEventListener(`click`,this.handleClick,{signal:r}),t.addEventListener(`mouseover`,this.handleMouseOver,{signal:r}),t.addEventListener(`mouseout`,this.handleMouseOut,{signal:r})),n&&(this.removeFromAriaLabelledBy(n,this.id),n.removeEventListener(`blur`,this.handleBlur,{capture:!0}),n.removeEventListener(`focus`,this.handleFocus,{capture:!0}),n.removeEventListener(`click`,this.handleClick),n.removeEventListener(`mouseover`,this.handleMouseOver),n.removeEventListener(`mouseout`,this.handleMouseOut)),this.anchor=t}async handleOptionsChange(){this.hasUpdated&&(await this.updateComplete,this.popup.reposition())}handleDisabledChange(){this.disabled&&this.open&&this.hide()}async show(){if(!this.open)return this.open=!0,Pr(this,`wa-after-show`)}async hide(){if(this.open)return this.open=!1,Pr(this,`wa-after-hide`)}render(){return v`
      <wa-popup
        part="base"
        exportparts="
          popup:base__popup,
          arrow:base__arrow
        "
        class=${o({tooltip:!0,"tooltip-open":this.open})}
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
    `}};Ir.css=Ct,Ir.dependencies={"wa-popup":j},A([O(`slot:not([name])`)],Ir.prototype,`defaultSlot`,2),A([O(`.body`)],Ir.prototype,`body`,2),A([O(`wa-popup`)],Ir.prototype,`popup`,2),A([T()],Ir.prototype,`placement`,2),A([T({type:Boolean,reflect:!0})],Ir.prototype,`disabled`,2),A([T({type:Number})],Ir.prototype,`distance`,2),A([T({type:Boolean,reflect:!0})],Ir.prototype,`open`,2),A([T({type:Number})],Ir.prototype,`skidding`,2),A([T({attribute:`show-delay`,type:Number})],Ir.prototype,`showDelay`,2),A([T({attribute:`hide-delay`,type:Number})],Ir.prototype,`hideDelay`,2),A([T()],Ir.prototype,`trigger`,2),A([T({attribute:`without-arrow`,type:Boolean,reflect:!0})],Ir.prototype,`withoutArrow`,2),A([T()],Ir.prototype,`for`,2),A([E()],Ir.prototype,`anchor`,2),A([nt(`open`,{waitUntilFirstUpdate:!0})],Ir.prototype,`handleOpenChange`,1),A([nt(`for`)],Ir.prototype,`handleForChange`,1),A([nt([`distance`,`placement`,`skidding`])],Ir.prototype,`handleOptionsChange`,1),A([nt(`disabled`)],Ir.prototype,`handleDisabledChange`,1),Ir=A([D(`wa-tooltip`)],Ir);var Lr=class extends Ir{static get styles(){return[Ir.styles,b`
        :host {
          --wa-tooltip-background-color: var(--c-color-black-fill-loud);
          --wa-tooltip-border-color: var(--c-color-black-border-loud);
          --wa-tooltip-content-color: var(--c-color-black-on-loud);
          --wa-tooltip-padding: var(
            --c-tooltip-padding,
            calc(4rem / 16) calc(8rem / 16)
          );
          --wa-tooltip-arrow-size: var(--c-tooltip-arrow-size, 5px);
          --wa-tooltip-font-family: var(--c-font-body);
          --wa-tooltip-font-size: var(--c-text-base);
          --wa-tooltip-font-weight: 400;
          --wa-tooltip-line-height: 1.3;
          --wa-tooltip-border-radius: var(--c-radius-sm);
        }

        &::part(base) {
          box-shadow: var(--c-shadow-md);
        }

        .body {
          color: var(--wa-tooltip-content-color);
          font-weight: var(--wa-tooltip-font-weight);
          font-family: var(--c-font-body);
        }
      `]}};customElements.get(`c-tooltip`)||customElements.define(`c-tooltip`,Lr);var Rr=new WeakMap;function zr(e,t){let n=t;for(;n;){if(Rr.get(n)===e)return!0;n=Object.getPrototypeOf(n)}return!1}function Br(e){return t=>{if(zr(e,t))return t;let n=e(t);return Rr.set(n,e),n}}var Vr=Br(e=>class extends e{static get properties(){return{disabled:{type:Boolean,reflect:!0}}}constructor(){super(),this._requestedToBeDisabled=!1,this.__isUserSettingDisabled=!0,this.__restoreDisabledTo=!1,this.disabled=!1}makeRequestToBeDisabled(){this._requestedToBeDisabled===!1&&(this._requestedToBeDisabled=!0,this.__restoreDisabledTo=this.disabled,this.__internalSetDisabled(!0))}retractRequestToBeDisabled(){this._requestedToBeDisabled===!0&&(this._requestedToBeDisabled=!1,this.__internalSetDisabled(this.__restoreDisabledTo))}__internalSetDisabled(e){this.__isUserSettingDisabled=!1,this.disabled=e,this.__isUserSettingDisabled=!0}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`disabled`&&(this.__isUserSettingDisabled&&(this.__restoreDisabledTo=this.disabled),this.disabled===!1&&this._requestedToBeDisabled===!0&&this.__internalSetDisabled(!0))}click(){this.disabled||super.click()}}),Hr=Br(e=>class extends Vr(e){static get properties(){return{tabIndex:{type:Number,reflect:!0,attribute:`tabindex`}}}constructor(){super(),this.__isUserSettingTabIndex=!0,this.__restoreTabIndexTo=0,this.__internalSetTabIndex(0)}makeRequestToBeDisabled(){super.makeRequestToBeDisabled(),this._requestedToBeDisabled===!1&&this.tabIndex!=null&&(this.__restoreTabIndexTo=this.tabIndex)}retractRequestToBeDisabled(){super.retractRequestToBeDisabled(),this._requestedToBeDisabled===!0&&this.__internalSetTabIndex(this.__restoreTabIndexTo)}static enabledWarnings=super.enabledWarnings?.filter(e=>e!==`change-in-update`)||[];__internalSetTabIndex(e){this.__isUserSettingTabIndex=!1,this.tabIndex=e,this.__isUserSettingTabIndex=!0}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`disabled`&&(this.disabled?this.__internalSetTabIndex(-1):this.__internalSetTabIndex(this.__restoreTabIndexTo)),e===`tabIndex`&&(this.__isUserSettingTabIndex&&this.tabIndex!=null&&(this.__restoreTabIndexTo=this.tabIndex),this.tabIndex!==-1&&this._requestedToBeDisabled===!0&&this.__internalSetTabIndex(-1))}firstUpdated(e){super.firstUpdated(e),this.disabled&&this.__internalSetTabIndex(-1)}});function Ur(e,t){let n=!1;Array.from(e.childNodes).forEach(e=>{let r=e.hasAttribute&&e.hasAttribute(`slot`);if(e.nodeType===Node.COMMENT_NODE&&!n&&(n=e.textContent.includes(`_start_slot_`)),n){e.textContent.includes(`_end_slot_`)&&(n=!1);return}r||t.appendChild(e)})}function Wr(e){return e instanceof Node?`node`:ct(e)?`template-result`:!Array.isArray(e)&&typeof e==`object`&&`template`in e?`slot-rerender-object`:null}var Gr=Br(e=>class extends e{get slots(){return{}}constructor(){super(),this.__renderMetaPerSlot=new Map,this.__slotsThatNeedRerender=new Set,this.__slotsProvidedByUserOnFirstConnected=new Set,this.__privateSlots=new Set}connectedCallback(){super.connectedCallback(),this._connectSlotMixin()}__rerenderSlot(e){let t=this.slots[e]();this.__renderTemplateInScopedContext({renderAsDirectHostChild:t.renderAsDirectHostChild,template:t.template,slotName:e}),t.afterRender?.()}update(e){super.update(e);for(let e of this.__slotsThatNeedRerender)this.__rerenderSlot(e)}__renderTemplateInScopedContext({template:e,slotName:t,renderAsDirectHostChild:n}){if(!this.__renderMetaPerSlot.has(t)){let r=!!ShadowRoot.prototype.createElement;this.shadowRoot||console.error(`[SlotMixin] No shadowRoot was found`);let i=(r?this.shadowRoot:document).createElement(`div`),a=document.createComment(`_start_slot_${t}_`),o=document.createComment(`_end_slot_${t}_`);i.appendChild(a),i.appendChild(o);let{creationScope:s,host:c}=this.renderOptions;if(S(e,i,{renderBefore:o,creationScope:s,host:c}),n){let e=Array.from(i.childNodes);this.__appendNodes({nodes:e,renderParent:this,slotName:t})}else i.slot=t,this.appendChild(i);this.__renderMetaPerSlot.set(t,{renderTargetThatRespectsShadowRootScoping:i,renderBefore:o});return}let{renderBefore:r,renderTargetThatRespectsShadowRootScoping:i}=this.__renderMetaPerSlot.get(t),a=n?this:i,{creationScope:o,host:s}=this.renderOptions;S(e,a,{creationScope:o,host:s,renderBefore:r}),n&&r.previousElementSibling&&!r.previousElementSibling.slot&&(r.previousElementSibling.slot=t)}__appendNodes({nodes:e,renderParent:t=this,slotName:n}){for(let r of e)r instanceof Element&&n&&n!==``&&r.setAttribute(`slot`,n),t.appendChild(r)}__initSlots(e){for(let t of e){if(this.__slotsProvidedByUserOnFirstConnected.has(t))continue;let e=this.slots[t]();if(e!==void 0)switch(this.__isConnectedSlotMixin||this.__privateSlots.add(t),Wr(e)){case`template-result`:this.__renderTemplateInScopedContext({template:e,renderAsDirectHostChild:!0,slotName:t});break;case`node`:this.__appendNodes({nodes:[e],renderParent:this,slotName:t});break;case`slot-rerender-object`:this.__slotsThatNeedRerender.add(t),e.firstRenderOnConnected&&this.__rerenderSlot(t);break;default:throw Error(`Slot "${t}" configured inside "get slots()" (in prototype) of ${this.constructor.name} may return these types: TemplateResult | Node | {template:TemplateResult, afterRender?:function} | undefined.
              You provided: ${e}`)}}}_connectSlotMixin(){if(this.__isConnectedSlotMixin)return;let e=Object.keys(this.slots);for(let t of e)(t===``?Array.from(this.children).find(e=>!e.hasAttribute(`slot`)):Array.from(this.children).find(e=>e.slot===t))&&this.__slotsProvidedByUserOnFirstConnected.add(t);this.__initSlots(e),this.__isConnectedSlotMixin=!0}_isPrivateSlot(e){return this.__privateSlots.has(e)}});function Kr(e=`google-chrome`){let t=globalThis.navigator,n=!!t.userAgentData&&t.userAgentData.brands.some(e=>e.brand===`Chromium`);if(e===`chromium`)return n;let r=globalThis.navigator?.vendor,i=globalThis.opr!==void 0,a=globalThis.userAgent?.indexOf(`Edge`)>-1,o=globalThis.userAgent?.match(`CriOS`);if(e===`ios`)return o;if(e===`google-chrome`)return n!=null&&r===`Google Inc.`&&i===!1&&a===!1}var qr={isIE11:/Trident/.test(globalThis.navigator?.userAgent),isChrome:Kr(),isIOSChrome:Kr(`ios`),isChromium:Kr(`chromium`),isFirefox:globalThis.navigator?.userAgent.toLowerCase().indexOf(`firefox`)>-1,isMac:globalThis.navigator?.appVersion?.indexOf(`Mac`)!==-1,isIOS:/iPhone|iPad|iPod/i.test(globalThis.navigator?.userAgent),isMacSafari:globalThis.navigator?.vendor&&globalThis.navigator?.vendor.indexOf(`Apple`)>-1&&globalThis.navigator?.userAgent&&globalThis.navigator?.userAgent.indexOf(`CriOS`)===-1&&globalThis.navigator?.userAgent.indexOf(`FxiOS`)===-1&&globalThis.navigator?.appVersion.indexOf(`Mac`)!==-1};function Jr(e=``){return`${e.length>0?`${e}-`:``}${Math.random().toString(36).substr(2,10)}`}var Yr=e=>e.key===` `||e.key===`Enter`,Xr=e=>e.key===` `,Zr=class extends Hr(w){static get properties(){return{active:{type:Boolean,reflect:!0},type:{type:String,reflect:!0}}}render(){return v` <div class="button-content"><slot></slot></div> `}static get styles(){return[b`
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
      `]}constructor(){super(),this.type=`button`,this.active=!1,this.__setupEvents()}connectedCallback(){super.connectedCallback(),this.hasAttribute(`role`)||this.setAttribute(`role`,`button`)}updated(e){super.updated(e),e.has(`disabled`)&&(this.disabled?this.setAttribute(`aria-disabled`,`true`):this.getAttribute(`aria-disabled`)!==null&&this.removeAttribute(`aria-disabled`))}__setupEvents(){this.addEventListener(`mousedown`,this.__mousedownHandler),this.addEventListener(`keydown`,this.__keydownHandler),this.addEventListener(`keyup`,this.__keyupHandler)}__mousedownHandler(){this.active=!0;let e=()=>{this.active=!1,document.removeEventListener(`mouseup`,e),this.removeEventListener(`mouseup`,e)};document.addEventListener(`mouseup`,e),this.addEventListener(`mouseup`,e)}__keydownHandler(e){if(this.active||!Yr(e)){Xr(e)&&e.preventDefault();return}Xr(e)&&e.preventDefault(),this.active=!0;let t=e=>{Yr(e)&&(this.active=!1,document.removeEventListener(`keyup`,t,!0))};document.addEventListener(`keyup`,t,!0)}__keyupHandler(e){if(Yr(e)){if(e.target&&e.target!==this)return;this.click()}}},Qr=class extends Zr{constructor(){super(),this.type=`reset`,this.__setupDelegationInConstructor(),this.__submitAndResetHelperButton=document.createElement(`button`),this.__preventEventLeakage=this.__preventEventLeakage.bind(this)}connectedCallback(){super.connectedCallback(),this.updateComplete.then(()=>{this._setupSubmitAndResetHelperOnConnected()})}disconnectedCallback(){super.disconnectedCallback(),this._teardownSubmitAndResetHelperOnDisconnected()}__preventEventLeakage(e){e.target===this.__submitAndResetHelperButton&&e.stopImmediatePropagation()}_setupSubmitAndResetHelperOnConnected(){this.appendChild(this.__submitAndResetHelperButton),this._form=this.__submitAndResetHelperButton.form,this.removeChild(this.__submitAndResetHelperButton),this._form&&this._form.addEventListener(`click`,this.__preventEventLeakage)}_teardownSubmitAndResetHelperOnDisconnected(){this._form&&this._form.removeEventListener(`click`,this.__preventEventLeakage)}async __clickDelegationHandler(e){this._form||await this.updateComplete,(this.type===`submit`||this.type===`reset`)&&e.target===this&&this._form&&(this.__submitAndResetHelperButton.type=this.type,this._form.appendChild(this.__submitAndResetHelperButton),this.__submitAndResetHelperButton.click(),this._form.removeChild(this.__submitAndResetHelperButton))}__setupDelegationInConstructor(){this.addEventListener(`click`,this.__clickDelegationHandler,!0)}},$r=new WeakMap;function ei(){let e=document.createElement(`button`);return e.tabIndex=-1,e.type=`submit`,e.setAttribute(`aria-hidden`,`true`),e.style.cssText=`
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
  `,e}var ti=class extends Qr{get _nativeButtonNode(){return $r.get(this._form)?.helper||null}constructor(){super(),this.type=`submit`,this.__implicitSubmitHelperButton=null}_setupSubmitAndResetHelperOnConnected(){if(super._setupSubmitAndResetHelperOnConnected(),!this._form||this.type!==`submit`)return;let e=this._form;if(!$r.get(this._form)){let t=ei(),n=document.createElement(`div`);n.appendChild(t),$r.set(this._form,{lionButtons:new Set,helper:t,observer:new MutationObserver(()=>{e.appendChild(n)})}),e.appendChild(n),$r.get(e)?.observer.observe(n,{childList:!0})}$r.get(e)?.lionButtons.add(this)}_teardownSubmitAndResetHelperOnDisconnected(){if(super._teardownSubmitAndResetHelperOnDisconnected(),this._form){let e=$r.get(this._form);e&&(e.lionButtons.delete(this),e.lionButtons.size||(this._form.contains(e.helper)&&e.helper.remove(),$r.get(this._form)?.observer.disconnect(),$r.delete(this._form)))}}},ni=b`
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
`,ri=b`
  .cp-visually-hidden:not(:focus-within) {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    clip: rect(0 0 0 0) !important;
    clip-path: inset(50%) !important;
    border: none !important;
    overflow: hidden !important;
    white-space: nowrap !important;
    padding: 0 !important;
  }
`,ii=b`
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
  :host([appearance~='inline']) {
    display: inline;
    appearance: none;
    background-color: transparent;
    border-color: currentColor;
    color: inherit;
    font: inherit;
    padding: 0;
    min-height: auto;
    min-width: auto;

    &::before {
      /* remove the sizer added by lion */
      display: none;
    }

    .button-content {
      padding: 0;
    }
  }

  :host([appearance='inline']:not(:disabled):not(.loading):hover) {
    background-color: color-mix(
      in oklab,
      var(--c-color-fill-quiet, var(--c-button-default-fill)),
      var(--c-color-mix-hover)
    );
    color: var(--c-color-on-quiet);
  }

  :host([appearance='inline']:not(:disabled):not(.loading):active) {
    color: var(--c-color-on-quiet, var(--c-color-neutral-on-quiet));
    background-color: color-mix(
      in oklab,
      var(--c-color-fill-quiet, var(--c-color-neutral-fill-quiet)),
      var(--c-color-mix-active)
    );
  }

  /* None */
  :host([appearance~='none']),
  :host([appearance~='none']:hover),
  :host([appearance~='none']:not(:disabled):not(.loading):active) {
    background-color: transparent;
    border-color: transparent;
    color: inherit;
    padding: 0;
    min-height: auto;
    min-width: auto;

    &:before {
      display: none;
    }
  }

  /* Plain */
  :host([appearance~='plain']) {
    background-color: transparent;
    border-color: transparent;
    color: inherit;

    &:before {
      display: none;
    }
  }

  :host([appearance='plain']:not(:disabled):not(.loading):hover) {
    background-color: color-mix(
      in oklab,
      var(--c-color-fill-quiet, var(--c-button-default-fill)),
      var(--c-color-mix-hover)
    );
    color: var(--c-color-on-quiet);
  }

  :host([appearance='plain']:not(:disabled):not(.loading):active) {
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

  :host([appearance='filled']:not(:disabled):not(.loading):hover) {
    background-color: color-mix(
      in oklab,
      var(--c-color-fill-normal, var(--c-color-neutral-fill-normal)),
      var(--c-color-mix-hover)
    );
    color: var(--c-color-on-normal, var(--c-color-neutral-on-normal));
  }

  :host([appearance='filled']:not(:disabled):not(.loading):active) {
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

  :host([appearance='dashed']:not(:disabled):not(.loading):hover) {
    background-color: color-mix(
      in oklab,
      var(--c-color-fill-quiet, var(--c-button-default-fill)),
      var(--c-color-mix-hover)
    );
    color: var(--c-color-on-quiet);
  }

  :host([appearance='dashed']:not(:disabled):not(.loading):active) {
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
`,ai=Object.prototype.toString;function oi(e){return typeof e==`function`||ai.call(e)===`[object Function]`}function si(e){var t=Number(e);return isNaN(t)?0:t===0||!isFinite(t)?t:(t>0?1:-1)*Math.floor(Math.abs(t))}var ci=2**53-1;function li(e){var t=si(e);return Math.min(Math.max(t,0),ci)}function ui(e,t){var n=Array,r=Object(e);if(e==null)throw TypeError(`Array.from requires an array-like object - not null or undefined`);if(t!==void 0&&!oi(t))throw TypeError(`Array.from: when provided, the second argument must be a function`);for(var i=li(r.length),a=oi(n)?Object(new n(i)):Array(i),o=0,s;o<i;)s=r[o],t?a[o]=t(s,o):a[o]=s,o+=1;return a.length=i,a}function di(e){"@babel/helpers - typeof";return di=typeof Symbol==`function`&&typeof Symbol.iterator==`symbol`?function(e){return typeof e}:function(e){return e&&typeof Symbol==`function`&&e.constructor===Symbol&&e!==Symbol.prototype?`symbol`:typeof e},di(e)}function fi(e,t){if(!(e instanceof t))throw TypeError(`Cannot call a class as a function`)}function pi(e,t){for(var n=0;n<t.length;n++){var r=t[n];r.enumerable=r.enumerable||!1,r.configurable=!0,`value`in r&&(r.writable=!0),Object.defineProperty(e,gi(r.key),r)}}function mi(e,t,n){return t&&pi(e.prototype,t),n&&pi(e,n),Object.defineProperty(e,`prototype`,{writable:!1}),e}function hi(e,t,n){return t=gi(t),t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function gi(e){var t=_i(e,`string`);return di(t)==`symbol`?t:t+``}function _i(e,t){if(di(e)!=`object`||!e)return e;var n=e[Symbol.toPrimitive];if(n!==void 0){var r=n.call(e,t||`default`);if(di(r)!=`object`)return r;throw TypeError(`@@toPrimitive must return a primitive value.`)}return(t===`string`?String:Number)(e)}var vi=typeof Set>`u`?Set:function(){function e(){var t=arguments.length>0&&arguments[0]!==void 0?arguments[0]:[];fi(this,e),hi(this,`items`,void 0),this.items=t}return mi(e,[{key:`add`,value:function(e){return this.has(e)===!1&&this.items.push(e),this}},{key:`clear`,value:function(){this.items=[]}},{key:`delete`,value:function(e){var t=this.items.length;return this.items=this.items.filter(function(t){return t!==e}),t!==this.items.length}},{key:`forEach`,value:function(e){var t=this;this.items.forEach(function(n){e(n,n,t)})}},{key:`has`,value:function(e){return this.items.indexOf(e)!==-1}},{key:`size`,get:function(){return this.items.length}}])}();function yi(e){return e.localName??e.tagName.toLowerCase()}var bi={article:`article`,aside:`complementary`,button:`button`,datalist:`listbox`,dd:`definition`,details:`group`,dialog:`dialog`,dt:`term`,fieldset:`group`,figure:`figure`,form:`form`,footer:`contentinfo`,h1:`heading`,h2:`heading`,h3:`heading`,h4:`heading`,h5:`heading`,h6:`heading`,header:`banner`,hr:`separator`,html:`document`,legend:`legend`,li:`listitem`,math:`math`,main:`main`,menu:`list`,nav:`navigation`,ol:`list`,optgroup:`group`,option:`option`,output:`status`,progress:`progressbar`,section:`region`,summary:`button`,table:`table`,tbody:`rowgroup`,textarea:`textbox`,tfoot:`rowgroup`,td:`cell`,th:`columnheader`,thead:`rowgroup`,tr:`row`,ul:`list`},xi={caption:new Set([`aria-label`,`aria-labelledby`]),code:new Set([`aria-label`,`aria-labelledby`]),deletion:new Set([`aria-label`,`aria-labelledby`]),emphasis:new Set([`aria-label`,`aria-labelledby`]),generic:new Set([`aria-label`,`aria-labelledby`,`aria-roledescription`]),insertion:new Set([`aria-label`,`aria-labelledby`]),none:new Set([`aria-label`,`aria-labelledby`]),paragraph:new Set([`aria-label`,`aria-labelledby`]),presentation:new Set([`aria-label`,`aria-labelledby`]),strong:new Set([`aria-label`,`aria-labelledby`]),subscript:new Set([`aria-label`,`aria-labelledby`]),superscript:new Set([`aria-label`,`aria-labelledby`])};function Si(e,t){return[`aria-atomic`,`aria-busy`,`aria-controls`,`aria-current`,`aria-description`,`aria-describedby`,`aria-details`,`aria-dropeffect`,`aria-flowto`,`aria-grabbed`,`aria-hidden`,`aria-keyshortcuts`,`aria-label`,`aria-labelledby`,`aria-live`,`aria-owns`,`aria-relevant`,`aria-roledescription`].some(function(n){var r;return e.hasAttribute(n)&&!((r=xi[t])!=null&&r.has(n))})}function Ci(e,t){return Si(e,t)}function wi(e){var t=Ei(e);if(t===null||Di.indexOf(t)!==-1){var n=Ti(e);if(Di.indexOf(t||``)===-1||Ci(e,n||``))return n}return t}function Ti(e){var t=bi[yi(e)];if(t!==void 0)return t;switch(yi(e)){case`a`:case`area`:case`link`:if(e.hasAttribute(`href`))return`link`;break;case`img`:return e.getAttribute(`alt`)===``&&!Ci(e,`img`)?`presentation`:`img`;case`input`:var n=e.type;switch(n){case`button`:case`image`:case`reset`:case`submit`:return`button`;case`checkbox`:case`radio`:return n;case`range`:return`slider`;case`email`:case`tel`:case`text`:case`url`:return e.hasAttribute(`list`)?`combobox`:`textbox`;case`search`:return e.hasAttribute(`list`)?`combobox`:`searchbox`;case`number`:return`spinbutton`;default:return null}case`select`:return e.hasAttribute(`multiple`)||e.size>1?`listbox`:`combobox`}return null}function Ei(e){var t=e.getAttribute(`role`);if(t!==null){var n=t.trim().split(` `)[0];if(n.length>0)return n}return null}var Di=[`presentation`,`none`];function Oi(e){return e!==null&&e.nodeType===e.ELEMENT_NODE}function ki(e){return Oi(e)&&yi(e)===`caption`}function Ai(e){return Oi(e)&&yi(e)===`input`}function ji(e){return Oi(e)&&yi(e)===`optgroup`}function Mi(e){return Oi(e)&&yi(e)===`select`}function Ni(e){return Oi(e)&&yi(e)===`table`}function Pi(e){return Oi(e)&&yi(e)===`textarea`}function Fi(e){var t=(e.ownerDocument===null?e:e.ownerDocument).defaultView;if(t===null)throw TypeError(`no window available`);return t}function Ii(e){return Oi(e)&&yi(e)===`fieldset`}function Li(e){return Oi(e)&&yi(e)===`legend`}function Ri(e){return Oi(e)&&yi(e)===`slot`}function zi(e){return Oi(e)&&e.ownerSVGElement!==void 0}function Bi(e){return Oi(e)&&yi(e)===`svg`}function Vi(e){return zi(e)&&yi(e)===`title`}function Hi(e,t){if(Oi(e)&&e.hasAttribute(t)){var n=e.getAttribute(t).split(` `),r=e.getRootNode?e.getRootNode():e.ownerDocument;return n.map(function(e){return r.getElementById(e)}).filter(function(e){return e!==null})}return[]}function Ui(e,t){return Oi(e)?t.indexOf(wi(e))!==-1:!1}function Wi(e){return e.trim().replace(/\s\s+/g,` `)}function Gi(e,t){if(!Oi(e))return!1;if(e.hasAttribute(`hidden`)||e.getAttribute(`aria-hidden`)===`true`)return!0;var n=t(e);return n.getPropertyValue(`display`)===`none`||n.getPropertyValue(`visibility`)===`hidden`}function Ki(e){return Ui(e,[`button`,`combobox`,`listbox`,`textbox`])||qi(e,`range`)}function qi(e,t){if(!Oi(e))return!1;switch(t){case`range`:return Ui(e,[`meter`,`progressbar`,`scrollbar`,`slider`,`spinbutton`]);default:throw TypeError(`No knowledge about abstract role '${t}'. This is likely a bug :(`)}}function Ji(e,t){var n=ui(e.querySelectorAll(t));return Hi(e,`aria-owns`).forEach(function(e){n.push.apply(n,ui(e.querySelectorAll(t)))}),n}function Yi(e){return Mi(e)?e.selectedOptions||Ji(e,`[selected]`):Ji(e,`[aria-selected="true"]`)}function Xi(e){return Ui(e,Di)}function Zi(e){return ki(e)}function Qi(e){return Ui(e,[`button`,`cell`,`checkbox`,`columnheader`,`gridcell`,`heading`,`label`,`legend`,`link`,`menuitem`,`menuitemcheckbox`,`menuitemradio`,`option`,`radio`,`row`,`rowheader`,`switch`,`tab`,`tooltip`,`treeitem`])}function $i(e){return!1}function ea(e){return Ai(e)||Pi(e)?e.value:e.textContent||``}function ta(e){var t=e.getPropertyValue(`content`);return/^["'].*["']$/.test(t)?t.slice(1,-1):``}function na(e){var t=yi(e);return t===`button`||t===`input`&&e.getAttribute(`type`)!==`hidden`||t===`meter`||t===`output`||t===`progress`||t===`select`||t===`textarea`}function ra(e){if(na(e))return e;var t=null;return e.childNodes.forEach(function(e){if(t===null&&Oi(e)){var n=ra(e);n!==null&&(t=n)}}),t}function ia(e){if(e.control!==void 0)return e.control;var t=e.getAttribute(`for`);return t===null?ra(e):e.ownerDocument.getElementById(t)}function aa(e){var t=e.labels;if(t===null)return t;if(t!==void 0)return ui(t);if(!na(e))return null;var n=e.ownerDocument;return ui(n.querySelectorAll(`label`)).filter(function(t){return ia(t)===e})}function oa(e){var t=e.assignedNodes();return t.length===0?ui(e.childNodes):t}function sa(e){var t=arguments.length>1&&arguments[1]!==void 0?arguments[1]:{},n=new vi,r=typeof Map>`u`?void 0:new Map,i=Fi(e),a=t.compute,o=a===void 0?`name`:a,s=t.computedStyleSupportsPseudoElements,c=s===void 0?t.getComputedStyle!==void 0:s,l=t.getComputedStyle,u=l===void 0?i.getComputedStyle.bind(i):l,d=t.hidden,f=d===void 0?!1:d,p=function(e,t){if(t!==void 0)throw Error(`use uncachedGetComputedStyle directly for pseudo elements`);if(r===void 0)return u(e);var n=r.get(e);if(n)return n;var i=u(e,t);return r.set(e,i),i};function m(e,t){var n=``;if(Oi(e)&&c&&(n=`${ta(u(e,`::before`))} ${n}`),(Ri(e)?oa(e):ui(e.childNodes).concat(Hi(e,`aria-owns`))).forEach(function(e){var r=v(e,{isEmbeddedInLabel:t.isEmbeddedInLabel,isReferenced:!1,recursion:!0}),i=(Oi(e)?p(e).getPropertyValue(`display`):`inline`)===`inline`?``:` `;n+=`${i}${r}${i}`}),Oi(e)&&c){var r=ta(u(e,`::after`));n=`${n} ${r}`}return n.trim()}function h(e,t){var r=e.getAttributeNode(t);return r!==null&&!n.has(r)&&r.value.trim()!==``?(n.add(r),r.value):null}function g(e){return Oi(e)?h(e,`title`):null}function _(e){if(!Oi(e))return null;if(Ii(e)){n.add(e);for(var t=ui(e.childNodes),r=0;r<t.length;r+=1){var i=t[r];if(Li(i))return v(i,{isEmbeddedInLabel:!1,isReferenced:!1,recursion:!1})}}else if(Ni(e)){n.add(e);for(var a=ui(e.childNodes),o=0;o<a.length;o+=1){var s=a[o];if(ki(s))return v(s,{isEmbeddedInLabel:!1,isReferenced:!1,recursion:!1})}}else if(Bi(e)){n.add(e);for(var c=ui(e.childNodes),l=0;l<c.length;l+=1){var u=c[l];if(Vi(u))return u.textContent}return null}else if(yi(e)===`img`||yi(e)===`area`){var d=h(e,`alt`);if(d!==null)return d}else if(ji(e)){var f=h(e,`label`);if(f!==null)return f}if(Ai(e)&&(e.type===`button`||e.type===`submit`||e.type===`reset`)){var p=h(e,`value`);if(p!==null)return p;if(e.type===`submit`)return`Submit`;if(e.type===`reset`)return`Reset`}var g=aa(e);if(g!==null&&g.length!==0)return n.add(e),ui(g).map(function(e){return v(e,{isEmbeddedInLabel:!0,isReferenced:!1,recursion:!0})}).filter(function(e){return e.length>0}).join(` `);if(Ai(e)&&e.type===`image`){var _=h(e,`alt`);if(_!==null)return _;var y=h(e,`title`);return y===null?`Submit Query`:y}if(Ui(e,[`button`])){var b=m(e,{isEmbeddedInLabel:!1,isReferenced:!1});if(b!==``)return b}return null}function v(e,t){if(n.has(e))return``;if(!f&&Gi(e,p)&&!t.isReferenced)return n.add(e),``;var r=Oi(e)?e.getAttributeNode(`aria-labelledby`):null,i=r!==null&&!n.has(r)?Hi(e,`aria-labelledby`):[];if(o===`name`&&!t.isReferenced&&i.length>0)return n.add(r),i.map(function(e){return v(e,{isEmbeddedInLabel:t.isEmbeddedInLabel,isReferenced:!0,recursion:!1})}).join(` `);var a=t.recursion&&Ki(e)&&o===`name`;if(!a){var s=(Oi(e)&&e.getAttribute(`aria-label`)||``).trim();if(s!==``&&o===`name`)return n.add(e),s;if(!Xi(e)){var c=_(e);if(c!==null)return n.add(e),c}}if(Ui(e,[`menu`]))return n.add(e),``;if(a||t.isEmbeddedInLabel||t.isReferenced){if(Ui(e,[`combobox`,`listbox`])){n.add(e);var l=Yi(e);return l.length===0?Ai(e)?e.value:``:ui(l).map(function(e){return v(e,{isEmbeddedInLabel:t.isEmbeddedInLabel,isReferenced:!1,recursion:!0})}).join(` `)}if(qi(e,`range`))return n.add(e),e.hasAttribute(`aria-valuetext`)?e.getAttribute(`aria-valuetext`):e.hasAttribute(`aria-valuenow`)?e.getAttribute(`aria-valuenow`):e.getAttribute(`value`)||``;if(Ui(e,[`textbox`]))return n.add(e),ea(e)}if(Qi(e)||Oi(e)&&t.isReferenced||Zi(e)||$i(e)){var u=m(e,{isEmbeddedInLabel:t.isEmbeddedInLabel,isReferenced:!1});if(u!==``)return n.add(e),u}if(e.nodeType===e.TEXT_NODE)return n.add(e),e.textContent||``;if(t.recursion)return n.add(e),m(e,{isEmbeddedInLabel:t.isEmbeddedInLabel,isReferenced:!1});var d=g(e);return d===null?(n.add(e),``):(n.add(e),d)}return Wi(v(e,{isEmbeddedInLabel:!1,isReferenced:o===`description`,recursion:!1}))}function ca(e){return Ui(e,[`caption`,`code`,`deletion`,`emphasis`,`generic`,`insertion`,`none`,`paragraph`,`presentation`,`strong`,`subscript`,`superscript`])}function la(e){var t=arguments.length>1&&arguments[1]!==void 0?arguments[1]:{};return ca(e)?``:sa(e,t)}var ua=class extends ti{constructor(...e){super(...e),this.appearance=`accent`,this.variant=`default`,this.size=`medium`,this.loading=!1,this.align=`center`,this.icon=null,this._hasAccessibilityError=!1}static get styles(){return[...super.styles,ii]}async firstUpdated(e){super.firstUpdated(e),await this.updateComplete;let t=this.querySelectorAll(`craft-icon, craft-spinner`);await Promise.all(Array.from(t).map(e=>e.updateComplete)),this.accessibleName||=la(this),this._hasAccessibilityError=!this.accessibleName||this.accessibleName.trim()===``}render(){return v`
      <!--@TODO need to figure this out-->
      <!--<div role="status" class="sr-only"></div>-->
      <div
        class="${o({"button-content":!0,"button-content--start":this.align===`start`,"button-content--end":this.align===`end`,"a11y-error":this._hasAccessibilityError})}"
        part="content"
      >
        <slot name="prefix" class="prefix" part="prefix">
          ${this.icon?v`<craft-icon name="${this.icon}"></craft-icon>`:C}
        </slot>
        <slot class="label" part="label"></slot>
        <slot name="suffix" class="suffix" part="suffix"></slot>
      </div>
      ${this.loading?v`<craft-spinner part="spinner"></craft-spinner>`:C}
    `}};g([T()],ua.prototype,`accessibleName`,void 0),g([T({reflect:!0})],ua.prototype,`appearance`,void 0),g([T({reflect:!0})],ua.prototype,`variant`,void 0),g([T({reflect:!0})],ua.prototype,`size`,void 0),g([T({reflect:!0,type:Boolean})],ua.prototype,`loading`,void 0),g([T()],ua.prototype,`align`,void 0),g([T()],ua.prototype,`icon`,void 0),g([E()],ua.prototype,`_hasAccessibilityError`,void 0),customElements.get(`craft-button`)||customElements.define(`craft-button`,ua);var da=new WeakMap,fa=class extends w{constructor(...e){super(...e),this.label=n(`More Info`),this.icon=`circle-info`,this.disabled=!1,this.status=``,c(this,da,new AbortController)}connectedCallback(){super.connectedCallback(),s(da,this).signal.aborted&&m(da,this,new AbortController),this.id||=`info-icon-${Math.random().toString(36).slice(2,8)}`;let{signal:e}=s(da,this);this.addEventListener(`wa-show`,()=>{pa._&&pa._!==this&&pa._.renderRoot.querySelector(`c-tooltip`)?.hide(),pa._=this},{signal:e}),this.addEventListener(`wa-after-show`,()=>{this.status=``,setTimeout(()=>{this.status=`Some new status`},200)},{signal:e}),this.addEventListener(`wa-after-hide`,()=>{pa._===this&&(pa._=null),this.status=``},{signal:e})}disconnectedCallback(){pa._===this&&(pa._=null),s(da,this).abort(),super.disconnectedCallback()}render(){return v`
      <div class="cp-info-icon">
        <craft-visually-hidden role="status">
          ${this.status}
        </craft-visually-hidden>

        <craft-button
          type="button"
          icon
          size="zero"
          appearance="plain"
          id="${this.id}"
        >
          <craft-icon name="${this.icon}" label="${this.label}"></craft-icon>
        </craft-button>

        <c-tooltip trigger="click" for="${this.id}"><slot></slot></c-tooltip>
      </div>
    `}};fa.styles=b`
    :host {
      display: inline-flex;
    }
  `;var pa={_:null};g([T()],fa.prototype,`label`,void 0),g([T()],fa.prototype,`icon`,void 0),g([T({type:Boolean,reflect:!0})],fa.prototype,`disabled`,void 0),g([T()],fa.prototype,`id`,void 0),g([E()],fa.prototype,`status`,void 0),g([O(`c-tooltip`)],fa.prototype,`tooltip`,void 0),customElements.get(`craft-info-icon`)||customElements.define(`craft-info-icon`,fa);var ma=class extends w{constructor(...e){super(...e),this.debug=!1}render(){return v`<slot></slot>`}};ma.styles=b`
    :host(:not([debug])) {
      position: absolute;
      width: 1px;
      height: 1px;
      overflow: hidden;
      clip: rect(0 0 0 0);
      clip-path: inset(50%);
      white-space: nowrap;
    }
  `,g([T({type:Boolean,reflect:!0})],ma.prototype,`debug`,void 0),customElements.get(`craft-visually-hidden`)||customElements.define(`craft-visually-hidden`,ma);var ha=class extends w{constructor(...e){super(...e),this.visible=!0}show(){this.visible=!0,this.dispatchEvent(new CustomEvent(`show`))}hide(){this.visible=!1,this.dispatchEvent(new CustomEvent(`hide`))}focus(){this.wrapper?.focus()}render(){return v`
      <div
        tabindex="-1"
        class="${o({wrapper:!0,hidden:!this.visible})}"
      >
        <div class="spinner"></div>
        <span class="cp-visually-hidden"><slot /></span>
      </div>
    `}};ha.styles=[ri,ni],g([T({reflect:!0,type:Boolean})],ha.prototype,`visible`,void 0),g([O(`.wrapper`)],ha.prototype,`wrapper`,void 0),customElements.get(`craft-spinner`)||customElements.define(`craft-spinner`,ha);var ga=b`
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
`,_a={"icon.in":{keyframes:[{scale:.25,opacity:.25},{scale:1,opacity:1}],options:{duration:100}},"icon.out":{keyframes:[{scale:1,opacity:1},{scale:.25,opacity:.25}],options:{duration:100}}},va=class extends w{constructor(...e){super(...e),this.isCopying=!1,this.status=`rest`,this.value=``,this.disabled=!1,this.feedbackDuration=1e3,this.tooltipLabel=null}async copyValue(){if(!(this.status===`copying`||this.disabled)){this.status=`copying`;try{await navigator.clipboard.writeText(this.value),this.dispatchEvent(new CustomEvent(`craft-copy`,{bubbles:!0,cancelable:!1,composed:!0,detail:{value:this.value}}))}catch{this.dispatchEvent(new CustomEvent(`craft-error`,{cancelable:!1,composed:!0,bubbles:!0}))}finally{this.isCopying=!1}}}async showStatus(e){let t=e===`success`?this.successIconEl:this.errorIconEl;this.tooltipLabel=e===`success`?`Copied`:`Copy failed`,await t.animate(_a[`icon.out`].keyframes,_a[`icon.out`].options),this.copyIconEl.hidden=!0,t.hidden=!1,await t.animate(_a[`icon.in`].keyframes,_a[`icon.in`].options),this.status=e,setTimeout(async()=>{await t.animate(_a[`icon.out`].keyframes,_a[`icon.out`].options),t.hidden=!0,this.copyIconEl.hidden=!1,await this.copyIconEl.animate(_a[`icon.in`].keyframes,_a[`icon.in`].options),this.status=`rest`,this.tooltipLabel=`Copy`},this.feedbackDuration)}connectedCallback(){super.connectedCallback(),this.tooltipLabel=this.getAttribute(`tooltip-label`)||n(`Copy`),this.id||=`copy-${Math.floor(Math.random()*1e8)}`,this.addEventListener(`craft-copy`,()=>{this.showStatus(`success`)}),this.addEventListener(`craft-error`,()=>{this.showStatus(`error`)})}render(){return v`
      <c-tooltip for="${this.id}">${this.tooltipLabel}</c-tooltip>
      <button
        type="button"
        id="${this.id}"
        @click="${this.copyValue}"
        ?disabled=${this.disabled}
        class="copy-button"
        part="button"
      >
        <slot></slot>
        <slot name="copy-icon">
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

        <craft-visually-hidden>Copy to clipboard</craft-visually-hidden>
      </button>
    `}};va.styles=[ga],g([E()],va.prototype,`isCopying`,void 0),g([E()],va.prototype,`status`,void 0),g([O(`slot[name="copy-icon"]`)],va.prototype,`copyIconEl`,void 0),g([O(`slot[name="success-icon"]`)],va.prototype,`successIconEl`,void 0),g([O(`slot[name="error-icon"]`)],va.prototype,`errorIconEl`,void 0),g([T({type:String})],va.prototype,`value`,void 0),g([T({type:Boolean})],va.prototype,`disabled`,void 0),g([T({attribute:`feedback-duration`,type:Number})],va.prototype,`feedbackDuration`,void 0),g([T()],va.prototype,`tooltipLabel`,void 0),customElements.get(`craft-copy-button`)||customElements.define(`craft-copy-button`,va);var ya=b`
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
`,ba=b`
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
`,xa=class extends w{constructor(...e){super(...e),this.value=``,this.disabled=!1}getId(){return`attribute-${this.value.replace(/([a-z])([A-Z])/g,`$1-$2`).replace(/[\s_]+/g,`-`).toLowerCase()}`}render(){return v`
      <craft-copy-button
        id="${this.getId()}"
        value="${this.value}"
        class=${o({"copy-attribute":!0})}
      >
        ${this.value}
      </craft-copy-button>
    `}};xa.styles=[ya,ba],g([O(`craft-copy-button`)],xa.prototype,`copyButtonEl`,void 0),g([T({type:String})],xa.prototype,`value`,void 0),g([T({type:Boolean,reflect:!0})],xa.prototype,`disabled`,void 0),customElements.get(`craft-copy-attribute`)||customElements.define(`craft-copy-attribute`,xa);var Sa=b`
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
`,Ca=class extends w{constructor(...e){super(...e),this.label=null,this._gradientId=null}connectedCallback(){super.connectedCallback(),this._gradientId=`avatar-gradient-${Math.random().toString(36).slice(2,8)}`}text(){return this.label?this.label.split(` `).map(e=>e.charAt(0).toUpperCase()).join(``):`?`}render(){return v`
      <span class="avatar">
        <svg
          viewBox="0 0 100 100"
          xmlns="http://www.w3.org/2000/svg"
          role="img"
        >
          ${this.label?v`<title>${this.label}</title>`:``}
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
    `}};Ca.styles=[Sa],g([T()],Ca.prototype,`label`,void 0),g([E()],Ca.prototype,`_gradientId`,void 0),customElements.get(`craft-avatar`)||customElements.define(`craft-avatar`,Ca);var wa=b`
  --_border-width: var(
    --c-input-border-width,
    var(--c-form-control-border-width)
  );
  font: inherit;
  color: var(--c-input-text, var(--c-text-default));
  position: relative;
  min-height: calc(
    var(--c-input-height, var(--c-size-control-md)) - 2 * var(--_border-width)
  );
  border-width: var(--_border-width);
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
`,Ta=b`
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

  ::slotted([slot='after']) {
    margin-block-start: var(--c-spacing-sm);
  }
`,Ea=b`
  ${Ta}

  ::slotted([slot='input']) {
    font: inherit;
    padding-block: 0;
    border: none;
    appearance: none;
    padding-inline: var(--c-input-spacing-inline);
    background-color: transparent;
  }

  .input-group__container {
    ${wa}
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

  ::slotted([slot='input']) {
    width: 100%;
  }
`,Da=window,Oa=new WeakMap;function ka(e){Da.applyFocusVisiblePolyfill&&!Oa.has(e)&&(Da.applyFocusVisiblePolyfill(e),Oa.set(e,void 0))}var Aa=Br(e=>class extends e{static get properties(){return{focused:{type:Boolean,reflect:!0},focusedVisible:{type:Boolean,reflect:!0,attribute:`focused-visible`},autofocus:{type:Boolean,reflect:!0}}}constructor(){super(),this.focused=!1,this.focusedVisible=!1,this.autofocus=!1}firstUpdated(e){super.firstUpdated(e),this.__registerEventsForFocusMixin(),this.__syncAutofocusToFocusableElement()}disconnectedCallback(){super.disconnectedCallback(),this.__teardownEventsForFocusMixin()}updated(e){super.updated(e),e.has(`autofocus`)&&this.__syncAutofocusToFocusableElement()}__syncAutofocusToFocusableElement(){this._focusableNode&&(this.hasAttribute(`autofocus`)?this._focusableNode.setAttribute(`autofocus`,``):this._focusableNode.removeAttribute(`autofocus`))}focus(){this._focusableNode?.focus()}blur(){this._focusableNode?.blur()}get _focusableNode(){return this._inputNode||document.createElement(`input`)}__onFocus(){if(this.focused=!0,typeof Da.applyFocusVisiblePolyfill==`function`)this.focusedVisible=this._focusableNode.hasAttribute(`data-focus-visible-added`);else try{this.focusedVisible=this._focusableNode.matches(`:focus-visible`)}catch{this.focusedVisible=!1}}__onBlur(){this.focused=!1,this.focusedVisible=!1}__registerEventsForFocusMixin(){ka(this.getRootNode()),this.__redispatchFocus=e=>{e.stopPropagation(),this.dispatchEvent(new Event(`focus`))},this._focusableNode.addEventListener(`focus`,this.__redispatchFocus),this.__redispatchBlur=e=>{e.stopPropagation(),this.dispatchEvent(new Event(`blur`))},this._focusableNode.addEventListener(`blur`,this.__redispatchBlur),this.__redispatchFocusin=e=>{e.stopPropagation(),this.__onFocus(),this.dispatchEvent(new Event(`focusin`,{bubbles:!0,composed:!0}))},this._focusableNode.addEventListener(`focusin`,this.__redispatchFocusin),this.__redispatchFocusout=e=>{e.stopPropagation(),this.__onBlur(),this.dispatchEvent(new Event(`focusout`,{bubbles:!0,composed:!0}))},this._focusableNode.addEventListener(`focusout`,this.__redispatchFocusout)}__teardownEventsForFocusMixin(){this._focusableNode&&(this._focusableNode?.removeEventListener(`focus`,this.__redispatchFocus),this._focusableNode?.removeEventListener(`blur`,this.__redispatchBlur),this._focusableNode?.removeEventListener(`focusin`,this.__redispatchFocusin),this._focusableNode?.removeEventListener(`focusout`,this.__redispatchFocusout))}});function ja(e,t){return t={exports:{}},e(t,t.exports),t.exports}var Ma=`long`,Na=`short`,Pa=`narrow`,M=`numeric`,Fa=`2-digit`,Ia={number:{decimal:{style:`decimal`},integer:{style:`decimal`,maximumFractionDigits:0},currency:{style:`currency`,currency:`USD`},percent:{style:`percent`},default:{style:`decimal`}},date:{short:{month:M,day:M,year:Fa},medium:{month:Na,day:M,year:M},long:{month:Ma,day:M,year:M},full:{month:Ma,day:M,year:M,weekday:Ma},default:{month:Na,day:M,year:M}},time:{short:{hour:M,minute:M},medium:{hour:M,minute:M,second:M},long:{hour:M,minute:M,second:M,timeZoneName:Na},full:{hour:M,minute:M,second:M,timeZoneName:Na},default:{hour:M,minute:M,second:M}},duration:{default:{hours:{minimumIntegerDigits:1,maximumFractionDigits:0},minutes:{minimumIntegerDigits:2,maximumFractionDigits:0},seconds:{minimumIntegerDigits:2,maximumFractionDigits:3}}},parseNumberPattern:function(e){if(e){var t={},n=e.match(/\b[A-Z]{3}\b/i),r=e.replace(/[^¤]/g,``).length;if(!r&&n&&(r=1),r?(t.style=`currency`,t.currencyDisplay=r===1?`symbol`:r===2?`code`:`name`,t.currency=n?n[0].toUpperCase():`USD`):e.indexOf(`%`)>=0&&(t.style=`percent`),!/[@#0]/.test(e))return t.style?t:void 0;if(t.useGrouping=e.indexOf(`,`)>=0,/E\+?[@#0]+/i.test(e)||e.indexOf(`@`)>=0){var i=e.replace(/E\+?[@#0]+|[^@#0]/gi,``);t.minimumSignificantDigits=Math.min(Math.max(i.replace(/[^@0]/g,``).length,1),21),t.maximumSignificantDigits=Math.min(Math.max(i.length,1),21)}else{for(var a=e.replace(/[^#0.]/g,``).split(`.`),o=a[0],s=o.length-1;o[s]===`0`;)--s;t.minimumIntegerDigits=Math.min(Math.max(o.length-1-s,1),21);var c=a[1]||``;for(s=0;c[s]===`0`;)++s;for(t.minimumFractionDigits=Math.min(Math.max(s,0),20);c[s]===`#`;)++s;t.maximumFractionDigits=Math.min(Math.max(s,0),20)}return t}},parseDatePattern:function(e){if(e){for(var t={},n=0;n<e.length;){for(var r=e[n],i=1;e[++n]===r;)++i;switch(r){case`G`:t.era=i===5?Pa:i===4?Ma:Na;break;case`y`:case`Y`:t.year=i===2?Fa:M;break;case`M`:case`L`:i=Math.min(Math.max(i-1,0),4),t.month=[M,Fa,Na,Ma,Pa][i];break;case`E`:case`e`:case`c`:t.weekday=i===5?Pa:i===4?Ma:Na;break;case`d`:case`D`:t.day=i===2?Fa:M;break;case`h`:case`K`:t.hour12=!0,t.hour=i===2?Fa:M;break;case`H`:case`k`:t.hour12=!1,t.hour=i===2?Fa:M;break;case`m`:t.minute=i===2?Fa:M;break;case`s`:case`S`:t.second=i===2?Fa:M;break;case`z`:case`Z`:case`v`:case`V`:t.timeZoneName=i===1?Na:Ma;break}}return Object.keys(t).length?t:void 0}}},La=function(e,t){if(typeof e==`string`&&t[e])return e;for(var n=[].concat(e||[]),r=0,i=n.length;r<i;++r)for(var a=n[r].split(`-`);a.length;){var o=a.join(`-`);if(t[o])return o;a.pop()}},Ra=`zero`,N=`one`,za=`two`,P=`few`,Ba=`many`,F=`other`,I=[function(e){return+e==1?N:F},function(e){var t=+e;return 0<=t&&t<=1?N:F},function(e){var t=Math.floor(Math.abs(+e)),n=+e;return t===0||n===1?N:F},function(e){var t=+e;return t===0?Ra:t===1?N:t===2?za:3<=t%100&&t%100<=10?P:11<=t%100&&t%100<=99?Ba:F},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length;return t===1&&n===0?N:F},function(e){var t=+e;return t%10==1&&t%100!=11?N:2<=t%10&&t%10<=4&&(t%100<12||14<t%100)?P:t%10==0||5<=t%10&&t%10<=9||11<=t%100&&t%100<=14?Ba:F},function(e){var t=+e;return t%10==1&&t%100!=11&&t%100!=71&&t%100!=91?N:t%10==2&&t%100!=12&&t%100!=72&&t%100!=92?za:(3<=t%10&&t%10<=4||t%10==9)&&(t%100<10||19<t%100)&&(t%100<70||79<t%100)&&(t%100<90||99<t%100)?P:t!==0&&t%1e6==0?Ba:F},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length,r=+(e+`.`).split(`.`)[1];return n===0&&t%10==1&&t%100!=11||r%10==1&&r%100!=11?N:n===0&&2<=t%10&&t%10<=4&&(t%100<12||14<t%100)||2<=r%10&&r%10<=4&&(r%100<12||14<r%100)?P:F},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length;return t===1&&n===0?N:2<=t&&t<=4&&n===0?P:n===0?F:Ba},function(e){var t=+e;return t===0?Ra:t===1?N:t===2?za:t===3?P:t===6?Ba:F},function(e){var t=Math.floor(Math.abs(+e)),n=+(``+e).replace(/^[^.]*.?|0+$/g,``);return+e==1||n!==0&&(t===0||t===1)?N:F},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length,r=+(e+`.`).split(`.`)[1];return n===0&&t%100==1||r%100==1?N:n===0&&t%100==2||r%100==2?za:n===0&&3<=t%100&&t%100<=4||3<=r%100&&r%100<=4?P:F},function(e){var t=Math.floor(Math.abs(+e));return t===0||t===1?N:F},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length,r=+(e+`.`).split(`.`)[1];return n===0&&(t===1||t===2||t===3)||n===0&&t%10!=4&&t%10!=6&&t%10!=9||n!==0&&r%10!=4&&r%10!=6&&r%10!=9?N:F},function(e){var t=+e;return t===1?N:t===2?za:3<=t&&t<=6?P:7<=t&&t<=10?Ba:F},function(e){var t=+e;return t===1||t===11?N:t===2||t===12?za:3<=t&&t<=10||13<=t&&t<=19?P:F},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length;return n===0&&t%10==1?N:n===0&&t%10==2?za:n===0&&(t%100==0||t%100==20||t%100==40||t%100==60||t%100==80)?P:n===0?F:Ba},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length,r=+e;return t===1&&n===0?N:t===2&&n===0?za:n===0&&(r<0||10<r)&&r%10==0?Ba:F},function(e){var t=Math.floor(Math.abs(+e)),n=+(``+e).replace(/^[^.]*.?|0+$/g,``);return n===0&&t%10==1&&t%100!=11||n!==0?N:F},function(e){var t=+e;return t===1?N:t===2?za:F},function(e){var t=+e;return t===0?Ra:t===1?N:F},function(e){var t=Math.floor(Math.abs(+e)),n=+e;return n===0?Ra:(t===0||t===1)&&n!==0?N:F},function(e){var t=+(e+`.`).split(`.`)[1],n=+e;return n%10==1&&(n%100<11||19<n%100)?N:2<=n%10&&n%10<=9&&(n%100<11||19<n%100)?P:t===0?F:Ba},function(e){var t=(e+`.`).split(`.`)[1].length,n=+(e+`.`).split(`.`)[1],r=+e;return r%10==0||11<=r%100&&r%100<=19||t===2&&11<=n%100&&n%100<=19?Ra:r%10==1&&r%100!=11||t===2&&n%10==1&&n%100!=11||t!==2&&n%10==1?N:F},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length,r=+(e+`.`).split(`.`)[1];return n===0&&t%10==1&&t%100!=11||r%10==1&&r%100!=11?N:F},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length,r=+e;return t===1&&n===0?N:n!==0||r===0||r!==1&&1<=r%100&&r%100<=19?P:F},function(e){var t=+e;return t===1?N:t===0||2<=t%100&&t%100<=10?P:11<=t%100&&t%100<=19?Ba:F},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length;return t===1&&n===0?N:n===0&&2<=t%10&&t%10<=4&&(t%100<12||14<t%100)?P:n===0&&t!==1&&0<=t%10&&t%10<=1||n===0&&5<=t%10&&t%10<=9||n===0&&12<=t%100&&t%100<=14?Ba:F},function(e){var t=Math.floor(Math.abs(+e));return 0<=t&&t<=1?N:F},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length;return n===0&&t%10==1&&t%100!=11?N:n===0&&2<=t%10&&t%10<=4&&(t%100<12||14<t%100)?P:n===0&&t%10==0||n===0&&5<=t%10&&t%10<=9||n===0&&11<=t%100&&t%100<=14?Ba:F},function(e){var t=Math.floor(Math.abs(+e)),n=+e;return t===0||n===1?N:2<=n&&n<=10?P:F},function(e){var t=Math.floor(Math.abs(+e)),n=+(e+`.`).split(`.`)[1],r=+e;return r===0||r===1||t===0&&n===1?N:F},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length;return n===0&&t%100==1?N:n===0&&t%100==2?za:n===0&&3<=t%100&&t%100<=4||n!==0?P:F},function(e){var t=+e;return 0<=t&&t<=1||11<=t&&t<=99?N:F},function(e){var t=+e;return t===1||t===5||t===7||t===8||t===9||t===10?N:t===2||t===3?za:t===4?P:t===6?Ba:F},function(e){var t=Math.floor(Math.abs(+e));return t%10==1||t%10==2||t%10==5||t%10==7||t%10==8||t%100==20||t%100==50||t%100==70||t%100==80?N:t%10==3||t%10==4||t%1e3==100||t%1e3==200||t%1e3==300||t%1e3==400||t%1e3==500||t%1e3==600||t%1e3==700||t%1e3==800||t%1e3==900?P:t===0||t%10==6||t%100==40||t%100==60||t%100==90?Ba:F},function(e){var t=+e;return(t%10==2||t%10==3)&&t%100!=12&&t%100!=13?P:F},function(e){var t=+e;return t===1||t===3?N:t===2?za:t===4?P:F},function(e){var t=+e;return t===0||t===7||t===8||t===9?Ra:t===1?N:t===2?za:t===3||t===4?P:t===5||t===6?Ba:F},function(e){var t=+e;return t%10==1&&t%100!=11?N:t%10==2&&t%100!=12?za:t%10==3&&t%100!=13?P:F},function(e){var t=+e;return t===1||t===11?N:t===2||t===12?za:t===3||t===13?P:F},function(e){var t=+e;return t===1?N:t===2||t===3?za:t===4?P:t===6?Ba:F},function(e){var t=+e;return t===1||t===5?N:F},function(e){var t=+e;return t===11||t===8||t===80||t===800?Ba:F},function(e){var t=Math.floor(Math.abs(+e));return t===1?N:t===0||2<=t%100&&t%100<=20||t%100==40||t%100==60||t%100==80?Ba:F},function(e){var t=+e;return t%10==6||t%10==9||t%10==0&&t!==0?Ba:F},function(e){var t=Math.floor(Math.abs(+e));return t%10==1&&t%100!=11?N:t%10==2&&t%100!=12?za:(t%10==7||t%10==8)&&t%100!=17&&t%100!=18?Ba:F},function(e){var t=+e;return t===1?N:t===2||t===3?za:t===4?P:F},function(e){var t=+e;return 1<=t&&t<=4?N:F},function(e){var t=+e;return t===1||t===5||7<=t&&t<=9?N:t===2||t===3?za:t===4?P:t===6?Ba:F},function(e){var t=+e;return t===1?N:t%10==4&&t%100!=14?Ba:F},function(e){var t=+e;return(t%10==1||t%10==2)&&t%100!=11&&t%100!=12?N:F},function(e){var t=+e;return t%10==6||t%10==9||t===10?P:F},function(e){var t=+e;return t%10==3&&t%100!=13?P:F}],Va={af:{cardinal:I[0]},ak:{cardinal:I[1]},am:{cardinal:I[2]},ar:{cardinal:I[3]},ars:{cardinal:I[3]},as:{cardinal:I[2],ordinal:I[34]},asa:{cardinal:I[0]},ast:{cardinal:I[4]},az:{cardinal:I[0],ordinal:I[35]},be:{cardinal:I[5],ordinal:I[36]},bem:{cardinal:I[0]},bez:{cardinal:I[0]},bg:{cardinal:I[0]},bh:{cardinal:I[1]},bn:{cardinal:I[2],ordinal:I[34]},br:{cardinal:I[6]},brx:{cardinal:I[0]},bs:{cardinal:I[7]},ca:{cardinal:I[4],ordinal:I[37]},ce:{cardinal:I[0]},cgg:{cardinal:I[0]},chr:{cardinal:I[0]},ckb:{cardinal:I[0]},cs:{cardinal:I[8]},cy:{cardinal:I[9],ordinal:I[38]},da:{cardinal:I[10]},de:{cardinal:I[4]},dsb:{cardinal:I[11]},dv:{cardinal:I[0]},ee:{cardinal:I[0]},el:{cardinal:I[0]},en:{cardinal:I[4],ordinal:I[39]},eo:{cardinal:I[0]},es:{cardinal:I[0]},et:{cardinal:I[4]},eu:{cardinal:I[0]},fa:{cardinal:I[2]},ff:{cardinal:I[12]},fi:{cardinal:I[4]},fil:{cardinal:I[13],ordinal:I[0]},fo:{cardinal:I[0]},fr:{cardinal:I[12],ordinal:I[0]},fur:{cardinal:I[0]},fy:{cardinal:I[4]},ga:{cardinal:I[14],ordinal:I[0]},gd:{cardinal:I[15],ordinal:I[40]},gl:{cardinal:I[4]},gsw:{cardinal:I[0]},gu:{cardinal:I[2],ordinal:I[41]},guw:{cardinal:I[1]},gv:{cardinal:I[16]},ha:{cardinal:I[0]},haw:{cardinal:I[0]},he:{cardinal:I[17]},hi:{cardinal:I[2],ordinal:I[41]},hr:{cardinal:I[7]},hsb:{cardinal:I[11]},hu:{cardinal:I[0],ordinal:I[42]},hy:{cardinal:I[12],ordinal:I[0]},ia:{cardinal:I[4]},io:{cardinal:I[4]},is:{cardinal:I[18]},it:{cardinal:I[4],ordinal:I[43]},iu:{cardinal:I[19]},iw:{cardinal:I[17]},jgo:{cardinal:I[0]},ji:{cardinal:I[4]},jmc:{cardinal:I[0]},ka:{cardinal:I[0],ordinal:I[44]},kab:{cardinal:I[12]},kaj:{cardinal:I[0]},kcg:{cardinal:I[0]},kk:{cardinal:I[0],ordinal:I[45]},kkj:{cardinal:I[0]},kl:{cardinal:I[0]},kn:{cardinal:I[2]},ks:{cardinal:I[0]},ksb:{cardinal:I[0]},ksh:{cardinal:I[20]},ku:{cardinal:I[0]},kw:{cardinal:I[19]},ky:{cardinal:I[0]},lag:{cardinal:I[21]},lb:{cardinal:I[0]},lg:{cardinal:I[0]},ln:{cardinal:I[1]},lt:{cardinal:I[22]},lv:{cardinal:I[23]},mas:{cardinal:I[0]},mg:{cardinal:I[1]},mgo:{cardinal:I[0]},mk:{cardinal:I[24],ordinal:I[46]},ml:{cardinal:I[0]},mn:{cardinal:I[0]},mo:{cardinal:I[25],ordinal:I[0]},mr:{cardinal:I[2],ordinal:I[47]},mt:{cardinal:I[26]},nah:{cardinal:I[0]},naq:{cardinal:I[19]},nb:{cardinal:I[0]},nd:{cardinal:I[0]},ne:{cardinal:I[0],ordinal:I[48]},nl:{cardinal:I[4]},nn:{cardinal:I[0]},nnh:{cardinal:I[0]},no:{cardinal:I[0]},nr:{cardinal:I[0]},nso:{cardinal:I[1]},ny:{cardinal:I[0]},nyn:{cardinal:I[0]},om:{cardinal:I[0]},or:{cardinal:I[0],ordinal:I[49]},os:{cardinal:I[0]},pa:{cardinal:I[1]},pap:{cardinal:I[0]},pl:{cardinal:I[27]},prg:{cardinal:I[23]},ps:{cardinal:I[0]},pt:{cardinal:I[28]},"pt-PT":{cardinal:I[4]},rm:{cardinal:I[0]},ro:{cardinal:I[25],ordinal:I[0]},rof:{cardinal:I[0]},ru:{cardinal:I[29]},rwk:{cardinal:I[0]},saq:{cardinal:I[0]},sc:{cardinal:I[4],ordinal:I[43]},scn:{cardinal:I[4],ordinal:I[43]},sd:{cardinal:I[0]},sdh:{cardinal:I[0]},se:{cardinal:I[19]},seh:{cardinal:I[0]},sh:{cardinal:I[7]},shi:{cardinal:I[30]},si:{cardinal:I[31]},sk:{cardinal:I[8]},sl:{cardinal:I[32]},sma:{cardinal:I[19]},smi:{cardinal:I[19]},smj:{cardinal:I[19]},smn:{cardinal:I[19]},sms:{cardinal:I[19]},sn:{cardinal:I[0]},so:{cardinal:I[0]},sq:{cardinal:I[0],ordinal:I[50]},sr:{cardinal:I[7]},ss:{cardinal:I[0]},ssy:{cardinal:I[0]},st:{cardinal:I[0]},sv:{cardinal:I[4],ordinal:I[51]},sw:{cardinal:I[4]},syr:{cardinal:I[0]},ta:{cardinal:I[0]},te:{cardinal:I[0]},teo:{cardinal:I[0]},ti:{cardinal:I[1]},tig:{cardinal:I[0]},tk:{cardinal:I[0],ordinal:I[52]},tl:{cardinal:I[13],ordinal:I[0]},tn:{cardinal:I[0]},tr:{cardinal:I[0]},ts:{cardinal:I[0]},tzm:{cardinal:I[33]},ug:{cardinal:I[0]},uk:{cardinal:I[29],ordinal:I[53]},ur:{cardinal:I[4]},uz:{cardinal:I[0]},ve:{cardinal:I[0]},vo:{cardinal:I[0]},vun:{cardinal:I[0]},wa:{cardinal:I[1]},wae:{cardinal:I[0]},xh:{cardinal:I[0]},xog:{cardinal:I[0]},yi:{cardinal:I[4]},zu:{cardinal:I[2]},lo:{ordinal:I[0]},ms:{ordinal:I[0]},vi:{ordinal:I[0]}},Ha=ja(function(e,t){t=e.exports=function(e,t,r){return n(e,null,t||`en`,r||{},!0)},t.toParts=function(e,t,r){return n(e,null,t||`en`,r||{},!1)};function n(e,t,n,i,a){var o=e.map(function(e){return r(e,t,n,i,a)});return a?o.length===1?o[0]:function(e){for(var t=``,n=0;n<o.length;++n)t+=o[n](e);return t}:function(e){return o.reduce(function(t,n){return t.concat(n(e))},[])}}function r(e,t,r,a,o){if(typeof e==`string`){var s=e;return function(){return s}}var c=e[0],l=e[1];if(t&&e[0]===`#`){c=t[0];var u=t[2],f=(a.number||d.number)([c,`number`],r);return function(e){return f(i(c,e)-u,e)}}var p;l===`plural`||l===`selectordinal`?(p={},Object.keys(e[3]).forEach(function(t){p[t]=n(e[3][t],e,r,a,o)}),e=[e[0],e[1],e[2],p]):e[2]&&typeof e[2]==`object`&&(p={},Object.keys(e[2]).forEach(function(t){p[t]=n(e[2][t],e,r,a,o)}),e=[e[0],e[1],p]);var m=l&&(a[l]||d[l]);if(m){var h=m(e,r);return function(e){return h(i(c,e),e)}}return o?function(e){return String(i(c,e))}:function(e){return i(c,e)}}function i(e,t){if(t&&e in t)return t[e];for(var n=e.split(`.`),r=t,i=0,a=n.length;r&&i<a;++i)r=r[n[i]];return r}function a(e,t){var n=e[2],r=Ia.number[n]||Ia.parseNumberPattern(n)||Ia.number.default;return new Intl.NumberFormat(t,r).format}function o(e,t){var n=e[2],r=Ia.duration[n]||Ia.duration.default,i=new Intl.NumberFormat(t,r.seconds).format,a=new Intl.NumberFormat(t,r.minutes).format,o=new Intl.NumberFormat(t,r.hours).format,s=/^fi$|^fi-|^da/.test(String(t))?`.`:`:`;return function(e,t){if(e=+e,!isFinite(e))return i(e);var n=~~(e/60/60),r=~~(e/60%60),c=(n?o(Math.abs(n))+s:``)+a(Math.abs(r))+s+i(Math.abs(e%60));return e<0?o(-1).replace(o(1),c):c}}function s(e,t){var n=e[1],r=e[2],i=Ia[n][r]||Ia.parseDatePattern(r)||Ia[n].default;return new Intl.DateTimeFormat(t,i).format}function c(e,t){var n=e[1]===`selectordinal`?`ordinal`:`cardinal`,r=e[2],i=e[3],a;if(Intl.PluralRules&&Intl.PluralRules.supportedLocalesOf(t).length>0)a=new Intl.PluralRules(t,{type:n});else{var o=La(t,Va);a={select:o&&Va[o][n]||l}}return function(e,t){return(i[`=`+ +e]||i[a.select(e-r)]||i.other)(t)}}function l(){return`other`}function u(e,t){var n=e[2];return function(e,t){return(n[e]||n.other)(t)}}var d={number:a,ordinal:a,spellout:a,duration:o,date:s,time:s,plural:c,selectordinal:c,select:u};t.types=d});Ha.toParts,Ha.types;var Ua=ja(function(e,t){var n=`{`,r=`}`,i=`,`,a=`#`,o=`<`,s=`>`,c=`</`,l=`/>`,u=`'`,d=`offset:`,f=[`number`,`date`,`time`,`ordinal`,`duration`,`spellout`],p=[`plural`,`select`,`selectordinal`];t=e.exports=function(e,t){return m({pattern:String(e),index:0,tagsType:t&&t.tagsType||null,tokens:t&&t.tokens||null},``)};function m(e,t){var n=e.pattern,i=n.length,a=[],o=e.index,s=h(e,t);for(s&&a.push(s),s&&e.tokens&&e.tokens.push([`text`,n.slice(o,e.index)]);e.index<i;){if(n[e.index]===r){if(!t)throw E(e);break}if(t&&e.tagsType&&n.slice(e.index,e.index+c.length)===c)break;a.push(v(e)),o=e.index,s=h(e,t),s&&a.push(s),s&&e.tokens&&e.tokens.push([`text`,n.slice(o,e.index)])}return a}function h(e,t){for(var i=e.pattern,s=i.length,c=t===`plural`||t===`selectordinal`,l=!!e.tagsType,d=t===`{style}`,f=``;e.index<s;){var p=i[e.index];if(p===n||p===r||c&&p===a||l&&p===o||d&&g(p.charCodeAt(0)))break;if(p===u)if(p=i[++e.index],p===u)f+=p,++e.index;else if(p===n||p===r||c&&p===a||l&&p===o||d)for(f+=p;++e.index<s;)if(p=i[e.index],p===u&&i[e.index+1]===u)f+=u,++e.index;else if(p===u){++e.index;break}else f+=p;else f+=u;else f+=p,++e.index}return f}function g(e){return e>=9&&e<=13||e===32||e===133||e===160||e===6158||e>=8192&&e<=8205||e===8232||e===8233||e===8239||e===8287||e===8288||e===12288||e===65279}function _(e){for(var t=e.pattern,n=t.length,r=e.index;e.index<n&&g(t.charCodeAt(e.index));)++e.index;r<e.index&&e.tokens&&e.tokens.push([`space`,e.pattern.slice(r,e.index)])}function v(e){var t=e.pattern;if(t[e.index]===a)return e.tokens&&e.tokens.push([`syntax`,a]),++e.index,[a];var o=y(e);if(o)return o;if(t[e.index]!==n)throw E(e,n);e.tokens&&e.tokens.push([`syntax`,n]),++e.index,_(e);var s=b(e);if(!s)throw E(e,`placeholder id`);e.tokens&&e.tokens.push([`id`,s]),_(e);var c=t[e.index];if(c===r)return e.tokens&&e.tokens.push([`syntax`,r]),++e.index,[s];if(c!==i)throw E(e,i+` or `+r);e.tokens&&e.tokens.push([`syntax`,i]),++e.index,_(e);var l=b(e);if(!l)throw E(e,`placeholder type`);if(e.tokens&&e.tokens.push([`type`,l]),_(e),c=t[e.index],c===r){if(e.tokens&&e.tokens.push([`syntax`,r]),l===`plural`||l===`selectordinal`||l===`select`)throw E(e,l+` sub-messages`);return++e.index,[s,l]}if(c!==i)throw E(e,i+` or `+r);e.tokens&&e.tokens.push([`syntax`,i]),++e.index,_(e);var u;if(l===`plural`||l===`selectordinal`){var d=S(e);_(e),u=[s,l,d,w(e,l)]}else if(l===`select`)u=[s,l,w(e,l)];else if(f.indexOf(l)>=0)u=[s,l,x(e)];else{var p=e.index,m=x(e);_(e),t[e.index]===n&&(e.index=p,m=w(e,l)),u=[s,l,m]}if(_(e),t[e.index]!==r)throw E(e,r);return e.tokens&&e.tokens.push([`syntax`,r]),++e.index,u}function y(e){var t=e.tagsType;if(!(!t||e.pattern[e.index]!==o)){if(e.pattern.slice(e.index,e.index+c.length)===c)throw E(e,null,`closing tag without matching opening tag`);e.tokens&&e.tokens.push([`syntax`,o]),++e.index;var n=b(e,!0);if(!n)throw E(e,`placeholder id`);if(e.tokens&&e.tokens.push([`id`,n]),_(e),e.pattern.slice(e.index,e.index+l.length)===l)return e.tokens&&e.tokens.push([`syntax`,l]),e.index+=l.length,[n,t];if(e.pattern[e.index]!==s)throw E(e,s);e.tokens&&e.tokens.push([`syntax`,s]),++e.index;var r=m(e,t),i=e.index;if(e.pattern.slice(e.index,e.index+c.length)!==c)throw E(e,c+n+s);e.tokens&&e.tokens.push([`syntax`,c]),e.index+=c.length;var a=b(e,!0);if(a&&e.tokens&&e.tokens.push([`id`,a]),n!==a)throw e.index=i,E(e,c+n+s,c+a+s);if(_(e),e.pattern[e.index]!==s)throw E(e,s);return e.tokens&&e.tokens.push([`syntax`,s]),++e.index,[n,t,{children:r}]}}function b(e,t){for(var c=e.pattern,l=c.length,d=``;e.index<l;){var f=c[e.index];if(f===n||f===r||f===i||f===a||f===u||g(f.charCodeAt(0))||t&&(f===o||f===s||f===`/`))break;d+=f,++e.index}return d}function x(e){var t=e.index,n=h(e,`{style}`);if(!n)throw E(e,`placeholder style name`);return e.tokens&&e.tokens.push([`style`,e.pattern.slice(t,e.index)]),n}function S(e){var t=e.pattern,n=t.length,r=0;if(t.slice(e.index,e.index+d.length)===d){e.tokens&&e.tokens.push([`offset`,`offset`],[`syntax`,`:`]),e.index+=d.length,_(e);for(var i=e.index;e.index<n&&C(t.charCodeAt(e.index));)++e.index;if(i===e.index)throw E(e,`offset number`);e.tokens&&e.tokens.push([`number`,t.slice(i,e.index)]),r=+t.slice(i,e.index)}return r}function C(e){return e>=48&&e<=57}function w(e,t){for(var n=e.pattern,i=n.length,a={};e.index<i&&n[e.index]!==r;){var o=b(e);if(!o)throw E(e,`sub-message selector`);e.tokens&&e.tokens.push([`selector`,o]),_(e),a[o]=T(e,t),_(e)}if(!a.other&&p.indexOf(t)>=0)throw E(e,null,null,`"other" sub-message must be specified in `+t);return a}function T(e,t){if(e.pattern[e.index]!==n)throw E(e,n+` to start sub-message`);e.tokens&&e.tokens.push([`syntax`,n]),++e.index;var i=m(e,t);if(e.pattern[e.index]!==r)throw E(e,r+` to end sub-message`);return e.tokens&&e.tokens.push([`syntax`,r]),++e.index,i}function E(e,t,n,r){var i=e.pattern,a=i.slice(0,e.index).split(/\r?\n/),o=e.index,s=a.length,c=a.slice(-1)[0].length;return n||=e.index>=i.length?`end of message pattern`:b(e)||i[e.index],r||=D(t,n),r+=` in `+i.replace(/\r?\n/g,`
`),new O(r,t,n,o,s,c)}function D(e,t){return e?`Expected `+e+` but found `+t:`Unexpected `+t+` found`}function O(e,t,n,r,i,a){Error.call(this,e),this.name=`SyntaxError`,this.message=e,this.expected=t,this.found=n,this.offset=r,this.line=i,this.column=a}O.prototype=Object.create(Error.prototype),t.SyntaxError=O});Ua.SyntaxError;var Wa=RegExp(`^(`+Object.keys(Va).join(`|`)+`)\\b`),Ga=new WeakMap;function Ka(e,t,n){if(!(this instanceof Ka)||Ga.has(this))throw TypeError(`calling MessageFormat constructor without new is invalid`);var r=Ua(e);Ga.set(this,{ast:r,format:Ha(r,t,n&&n.types),locale:Ka.supportedLocalesOf(t)[0]||`en`,locales:t,options:n})}var qa=Ka;Object.defineProperties(Ka.prototype,{format:{configurable:!0,get:function(){var e=Ga.get(this);if(!e)throw TypeError(`MessageFormat.prototype.format called on value that's not an object initialized as a MessageFormat`);return e.format}},formatToParts:{configurable:!0,writable:!0,value:function(e){var t=Ga.get(this);if(!t)throw TypeError(`MessageFormat.prototype.formatToParts called on value that's not an object initialized as a MessageFormat`);return(t.toParts||=Ha.toParts(t.ast,t.locales,t.options&&t.options.types))(e)}},resolvedOptions:{configurable:!0,writable:!0,value:function(){var e=Ga.get(this);if(!e)throw TypeError(`MessageFormat.prototype.resolvedOptions called on value that's not an object initialized as a MessageFormat`);return{locale:e.locale}}}}),typeof Symbol<`u`&&Object.defineProperty(Ka.prototype,Symbol.toStringTag,{value:`Object`}),Object.defineProperties(Ka,{supportedLocalesOf:{configurable:!0,writable:!0,value:function(e){return[].concat(Intl.NumberFormat.supportedLocalesOf(e),Intl.DateTimeFormat.supportedLocalesOf(e),Intl.PluralRules?Intl.PluralRules.supportedLocalesOf(e):[],[].concat(e||[]).filter(function(e){return Wa.test(e)})).filter(function(e,t,n){return n.indexOf(e)===t})}}});function Ja(e){return!!(e&&e.default&&typeof e.default==`object`&&Object.keys(e).length===1)}var Ya=globalThis.document?.documentElement,Xa=class extends EventTarget{formatNumberOptions={returnIfNaN:``,postProcessors:new Map};formatDateOptions={postProcessors:new Map};#e=!1;#t=``;#n=null;__storage={};__namespacePatternsMap=new Map;__namespaceLoadersCache={};__namespaceLoaderPromisesCache={};get locale(){return this.#e?this.#t||``:Ya.lang||``}set locale(e){if(this.#r(e),!this.#e){let t=Ya.lang;this._setHtmlLangAttribute(e),this._onLocaleChanged(e,t);return}let t=this.#t;this.#t=e,this.#n===null&&this._setHtmlLangAttribute(e),this._onLocaleChanged(e,t)}get loadingComplete(){return typeof this.__namespaceLoaderPromisesCache[this.locale]==`object`?Promise.all(Object.values(this.__namespaceLoaderPromisesCache[this.locale])):Promise.resolve()}constructor({allowOverridesForExistingNamespaces:e=!1,autoLoadOnLocaleChange:t=!1,showKeyAsFallback:n=!1,fallbackLocale:r=``}={}){super(),this.__allowOverridesForExistingNamespaces=e,this._autoLoadOnLocaleChange=!!t,this._showKeyAsFallback=n,this._fallbackLocale=r;let i=Ya.getAttribute(`data-localize-lang`);this.#e=!!i,this.#e&&(this.locale=i,this._setupTranslationToolSupport()),Ya.lang||=this.locale||`en-GB`,this._setupHtmlLangAttributeObserver()}addData(e,t,n){if(!this.__allowOverridesForExistingNamespaces&&this._isNamespaceInCache(e,t))throw Error(`Namespace "${t}" has been already added for the locale "${e}".`);this.__storage[e]=this.__storage[e]||{},this.__allowOverridesForExistingNamespaces?this.__storage[e][t]={...this.__storage[e][t],...n}:this.__storage[e][t]=n}setupNamespaceLoader(e,t){this.__namespacePatternsMap.set(e,t)}loadNamespaces(e,{locale:t}={}){return Promise.all(e.map(e=>this.loadNamespace(e,{locale:t})))}loadNamespace(e,{locale:t=this.locale}={locale:this.locale}){let n=typeof e==`object`,r=n?Object.keys(e)[0]:e;return this._isNamespaceInCache(t,r)?Promise.resolve():this._getCachedNamespaceLoaderPromise(t,r)||this._loadNamespaceData(t,e,n,r)}msg(e,t,n={}){let r=n.locale?n.locale:this.locale,i=this._getMessageForKeys(e,r);return i?new qa(i,r).format(t):``}teardown(){this._teardownHtmlLangAttributeObserver()}reset(){this.__storage={},this.__namespacePatternsMap=new Map,this.__namespaceLoadersCache={},this.__namespaceLoaderPromisesCache={}}setDatePostProcessorForLocale({locale:e,postProcessor:t}){this.formatDateOptions?.postProcessors.set(e,t)}setNumberPostProcessorForLocale({locale:e,postProcessor:t}){this.formatNumberOptions?.postProcessors.set(e,t)}_setupTranslationToolSupport(){this.#n=Ya.lang||null}_setHtmlLangAttribute(e){this._teardownHtmlLangAttributeObserver(),Ya.lang=e,this._setupHtmlLangAttributeObserver()}_setupHtmlLangAttributeObserver(){this._htmlLangAttributeObserver||=new MutationObserver(e=>{e.forEach(e=>{this.#e?Ya.lang===`auto`?(this.#n=null,this._setHtmlLangAttribute(this.locale)):this.#n=document.documentElement.lang:this._onLocaleChanged(document.documentElement.lang,e.oldValue||``)})}),this._htmlLangAttributeObserver.observe(document.documentElement,{attributes:!0,attributeFilter:[`lang`],attributeOldValue:!0})}_teardownHtmlLangAttributeObserver(){this._htmlLangAttributeObserver&&this._htmlLangAttributeObserver.disconnect()}_isNamespaceInCache(e,t){return!!(this.__storage[e]&&this.__storage[e][t])}_getCachedNamespaceLoaderPromise(e,t){return this.__namespaceLoaderPromisesCache[e]?this.__namespaceLoaderPromisesCache[e][t]:null}_loadNamespaceData(e,t,n,r){let i=this._getNamespaceLoader(t,n,r),a=this._getNamespaceLoaderPromise(i,e,r);return this._cacheNamespaceLoaderPromise(e,r,a),a.then(t=>{if(this.__namespaceLoaderPromisesCache[e]&&this.__namespaceLoaderPromisesCache[e][r]===a){let n=Ja(t)?t.default:t;this.addData(e,r,n)}})}_getNamespaceLoader(e,t,n){let r=this.__namespaceLoadersCache[n];if(r||(t?(r=e[n],this.__namespaceLoadersCache[n]=r):(r=this._lookupNamespaceLoader(n),this.__namespaceLoadersCache[n]=r)),!r)throw Error(`Namespace "${n}" was not properly setup.`);return this.__namespaceLoadersCache[n]=r,r}_getNamespaceLoaderPromise(e,t,n,r=this._fallbackLocale){return e(t,n).catch(()=>{let i=this._getLangFromLocale(t);return e(i,n).catch(()=>{if(r)return this._getNamespaceLoaderPromise(e,r,n,``).catch(()=>{let e=this._getLangFromLocale(r);throw Error(`Data for namespace "${n}" and current locale "${t}" or fallback locale "${r}" could not be loaded. Make sure you have data either for locale "${t}" (and/or generic language "${i}") or for fallback "${r}" (and/or "${e}").`)});throw Error(`Data for namespace "${n}" and locale "${t}" could not be loaded. Make sure you have data for locale "${t}" (and/or generic language "${i}").`)})})}_cacheNamespaceLoaderPromise(e,t,n){this.__namespaceLoaderPromisesCache[e]||(this.__namespaceLoaderPromisesCache[e]={}),this.__namespaceLoaderPromisesCache[e][t]=n}_lookupNamespaceLoader(e){for(let[t,n]of this.__namespacePatternsMap){let r=typeof t==`string`&&t===e,i=typeof t==`object`&&t.constructor.name===`RegExp`&&t.test(e);if(r||i)return n}return null}_getLangFromLocale(e){return e.substring(0,2)}_onLocaleChanged(e,t){this.dispatchEvent(new CustomEvent(`__localeChanging`)),e!==t&&(this._autoLoadOnLocaleChange?(this._loadAllMissing(e,t),this.loadingComplete.then(()=>{this.dispatchEvent(new CustomEvent(`localeChanged`,{detail:{newLocale:e,oldLocale:t}}))})):this.dispatchEvent(new CustomEvent(`localeChanged`,{detail:{newLocale:e,oldLocale:t}})))}_loadAllMissing(e,t){let n=this.__storage[t]||{},r=this.__storage[e]||{};Object.keys(n).forEach(t=>{r[t]||this.loadNamespace(t,{locale:e})})}_getMessageForKeys(e,t){if(typeof e==`string`)return this._getMessageForKey(e,t);let n=Array.from(e).reverse(),r,i;for(;n.length;)if(r=n.pop(),i=this._getMessageForKey(r,t),i)return i}_getMessageForKey(e,t){if(!e||e.indexOf(`:`)===-1)throw Error(`Namespace is missing in the key "${e}". The format for keys is "namespace:name".`);let[n,r]=e.split(`:`),i=this.__storage[t],a=i?i[n]:{},o=r.split(`.`).reduce((e,t)=>typeof e==`object`?e[t]:e,a);return String(o||(this._showKeyAsFallback?e:``))}#r(e){if(!e.includes(`-`))throw Error(`
      Locale was set to ${e}.
      Language only locales are not allowed, please use the full language locale e.g. 'en-GB' instead of 'en'.
      See https://github.com/ing-bank/lion/issues/187 for more information.
    `)}get _supportExternalTranslationTools(){return this.#e}set _supportExternalTranslationTools(e){this.#e=e}get _langAttrSetByTranslationTool(){return this.#t}set _langAttrSetByTranslationTool(e){this.#t=e}},Za=Symbol.for(`lion::SingletonManagerClassStorage`),Qa=globalThis||window,$a=class{constructor(){this._map=Qa[Za]?Qa[Za]:Qa[Za]=new Map}set(e,t){this.has(e)||this._map.set(e,t)}get(e){return this._map.get(e)}has(e){return this._map.has(e)}},eo=e=>{let t=null,n=()=>(t===null&&(t=e()),t);return new Proxy({},{get(e,t){let r=n();return t===`addEventListener`||t===`removeEventListener`?Reflect.get(r,t).bind(r):t===`__instance_for_testing`?r:Reflect.get(r,t,r)},set(e,t,r){return Reflect.set(n(),t,r)},getOwnPropertyDescriptor(e,t){return Reflect.getOwnPropertyDescriptor(n(),t)},getPrototypeOf(){return Reflect.getPrototypeOf(n())}})},to=new $a;function no(){if(!to.has(`@lion/ui::localize::0.x`)){let e=new Xa({autoLoadOnLocaleChange:!0,fallbackLocale:`en-GB`});to.set(`@lion/ui::localize::0.x`,e)}return to.get(`@lion/ui::localize::0.x`)}function ro(){return eo(no)}var io=(e,t)=>{let n=e._$AN;if(n===void 0)return!1;for(let e of n)e._$AO?.(t,!1),io(e,t);return!0},ao=e=>{let t,n;do{if((t=e._$AM)===void 0)break;n=t._$AN,n.delete(e),e=t}while(n?.size===0)},oo=e=>{for(let t;t=e._$AM;e=t){let n=t._$AN;if(n===void 0)t._$AN=n=new Set;else if(n.has(e))break;n.add(e),lo(t)}};function so(e){this._$AN===void 0?this._$AM=e:(ao(this),this._$AM=e,oo(this))}function co(e,t=!1,n=0){let r=this._$AH,i=this._$AN;if(i!==void 0&&i.size!==0)if(t)if(Array.isArray(r))for(let e=n;e<r.length;e++)io(r[e],!1),ao(r[e]);else r!=null&&(io(r,!1),ao(r));else io(this,e)}var lo=e=>{e.type==a.CHILD&&(e._$AP??=co,e._$AQ??=so)},uo=class extends t{constructor(){super(...arguments),this._$AN=void 0}_$AT(e,t,n){super._$AT(e,t,n),oo(this),this.isConnected=e._$AU}_$AO(e,t=!0){e!==this.isConnected&&(this.isConnected=e,e?this.reconnected?.():this.disconnected?.()),t&&(io(this,e),ao(this))}setValue(e){if(lt(this._$Ct))this._$Ct._$AI(e,this);else{let t=[...this._$Ct._$AH];t[this._$Ci]=e,this._$Ct._$AI(t,this,0)}}disconnected(){}reconnected(){}},fo=class{constructor(e){this.G=e}disconnect(){this.G=void 0}reconnect(e){this.G=e}deref(){return this.G}},po=class{constructor(){this.Y=void 0,this.Z=void 0}get(){return this.Y}pause(){this.Y??=new Promise((e=>this.Z=e))}resume(){this.Z?.(),this.Y=this.Z=void 0}},mo=e=>!st(e)&&typeof e.then==`function`,ho=1073741823,go=r(class extends uo{constructor(){super(...arguments),this._$Cwt=ho,this._$Cbt=[],this._$CK=new fo(this),this._$CX=new po}render(...e){return e.find((e=>!mo(e)))??x}update(e,t){let n=this._$Cbt,r=n.length;this._$Cbt=t;let i=this._$CK,a=this._$CX;this.isConnected||this.disconnected();for(let e=0;e<t.length&&!(e>this._$Cwt);e++){let o=t[e];if(!mo(o))return this._$Cwt=e,o;e<r&&o===n[e]||(this._$Cwt=ho,r=0,Promise.resolve(o).then((async e=>{for(;a.get();)await a.get();let t=i.deref();if(t!==void 0){let n=t._$Cbt.indexOf(o);n>-1&&n<t._$Cwt&&(t._$Cwt=n,t.setValue(e))}})))}return x}disconnected(){this._$CK.disconnect(),this._$CX.pause()}reconnected(){this._$CK.reconnect(this),this._$CX.resume()}}),_o=Br(e=>class extends e{static get localizeNamespaces(){return[]}static get waitForLocalizeNamespaces(){return!0}constructor(){super(),this._localizeManager=ro(),this.__boundLocalizeOnLocaleChanged=(...e)=>{let t=Array.from(e)[0];this.__localizeOnLocaleChanged(t)},this.__boundLocalizeOnLocaleChanging=()=>{this.__localizeOnLocaleChanging()},this.__localizeStartLoadingNamespaces(),this.localizeNamespacesLoaded&&this.localizeNamespacesLoaded.then(()=>{this.__localizeMessageSync=!0})}async scheduleUpdate(){Object.getPrototypeOf(this).constructor.waitForLocalizeNamespaces&&await this.localizeNamespacesLoaded,super.scheduleUpdate()}connectedCallback(){super.connectedCallback(),this.localizeNamespacesLoaded&&this.localizeNamespacesLoaded.then(()=>this.onLocaleReady()),this._localizeManager.addEventListener(`__localeChanging`,this.__boundLocalizeOnLocaleChanging),this._localizeManager.addEventListener(`localeChanged`,this.__boundLocalizeOnLocaleChanged)}disconnectedCallback(){super.disconnectedCallback(),this._localizeManager.removeEventListener(`__localeChanging`,this.__boundLocalizeOnLocaleChanging),this._localizeManager.removeEventListener(`localeChanged`,this.__boundLocalizeOnLocaleChanged)}msgLit(e,t,n){return this.__localizeMessageSync?this._localizeManager.msg(e,t,n):this.localizeNamespacesLoaded?go(this.localizeNamespacesLoaded.then(()=>this._localizeManager.msg(e,t,n)),C):``}__getUniqueNamespaces(){let e=[],t=new Set;return Object.getPrototypeOf(this).constructor.localizeNamespaces.forEach(t.add.bind(t)),t.forEach(t=>{e.push(t)}),e}__localizeStartLoadingNamespaces(){this.localizeNamespacesLoaded=this._localizeManager.loadNamespaces(this.__getUniqueNamespaces())}__localizeOnLocaleChanging(){this.__localizeStartLoadingNamespaces()}__localizeOnLocaleChanged(e){this.onLocaleChanged(e.detail.newLocale,e.detail.oldLocale)}onLocaleReady(){this.onLocaleUpdated()}onLocaleChanged(e,t){this.onLocaleUpdated(),this.requestUpdate()}onLocaleUpdated(){}}),vo=`3.0.0`,yo=window.scopedElementsVersions||(window.scopedElementsVersions=[]);yo.includes(vo)||yo.push(vo);var bo=Br(e=>class extends e{static scopedElements;static get scopedElementsVersion(){return vo}static __registry;get registry(){return this.constructor.__registry}set registry(e){this.constructor.__registry=e}attachShadow(e){let{scopedElements:t}=this.constructor;if(!this.registry||this.registry===this.constructor.__registry&&!Object.prototype.hasOwnProperty.call(this.constructor,`__registry`)){this.registry=new CustomElementRegistry;for(let[e,n]of Object.entries(t??{}))this.registry.define(e,n)}return super.attachShadow({...e,customElements:this.registry,registry:this.registry})}}),xo=Br(e=>class extends bo(e){createRenderRoot(){let{shadowRootOptions:e,elementStyles:t}=this.constructor,n=this.attachShadow(e);return this.renderOptions.creationScope=n,y(n,t),this.renderOptions.renderBefore??=n.firstChild,n}});function So(){return!!(globalThis.ShadowRoot?.prototype.createElement&&globalThis.ShadowRoot?.prototype.importNode)}var Co=Br(e=>class extends xo(e){constructor(){super()}createScopedElement(e){return(So()?this.shadowRoot:document).createElement(e)}defineScopedElement(e,t){let n=this.registry.get(e),r=n&&n!==t;return!So()&&r&&console.error([`You are trying to re-register the "${e}" custom element with a different class via ScopedElementsMixin.`,`This is only possible with a CustomElementRegistry.`,`Your browser does not support this feature so you will need to load a polyfill for it.`,`Load "@webcomponents/scoped-custom-element-registry" before you register ANY web component to the global customElements registry.`,`e.g. add "<script src="/node_modules/@webcomponents/scoped-custom-element-registry/scoped-custom-element-registry.min.js"><\/script>" as your first script tag.`,`For more details you can visit https://open-wc.org/docs/development/scoped-elements/`].join(`
`)),n?this.registry.get(e):this.registry.define(e,t)}attachShadow(e){let{scopedElements:t}=this.constructor;if(!this.registry||this.registry===this.constructor.__registry&&!Object.prototype.hasOwnProperty.call(this.constructor,`__registry`)){this.registry=So()?new CustomElementRegistry:customElements;for(let[e,n]of Object.entries(t??{}))this.defineScopedElement(e,n)}return Element.prototype.attachShadow.call(this,{...e,customElements:this.registry,registry:this.registry})}createRenderRoot(){let{shadowRootOptions:e,elementStyles:t}=this.constructor,n=this.attachShadow(e);return So()&&(this.renderOptions.creationScope=n),n instanceof ShadowRoot&&(y(n,t),this.renderOptions.renderBefore=this.renderOptions.renderBefore||n.firstChild),n}}),wo=[Node.DOCUMENT_POSITION_PRECEDING,Node.DOCUMENT_POSITION_CONTAINS,Node.DOCUMENT_POSITION_CONTAINS|Node.DOCUMENT_POSITION_PRECEDING];function To(e,{reverse:t}={}){let n=(e,t)=>{let n=e.compareDocumentPosition(t);return wo.includes(n)?1:-1},r=e.filter(e=>e);return r.sort(n),t&&r.reverse(),r}var Eo=class{constructor(e){this.type=`unparseable`,this.viewValue=e}toString(){return JSON.stringify({type:this.type,viewValue:this.viewValue})}},Do=Br(e=>class extends e{constructor(){super(),this.name=``,this._parentFormGroup=void 0,this.allowCrossRootRegistration=!1}get name(){return this.__name||``}set name(e){let t=this.name;this.__name=e.toString(),this.requestUpdate(`name`,t)}static get properties(){return{name:{type:String,reflect:!0},allowCrossRootRegistration:{type:Boolean,attribute:`allow-cross-root-registration`}}}connectedCallback(){super.connectedCallback(),this.dispatchEvent(new CustomEvent(`form-element-register`,{detail:{element:this},bubbles:!0,composed:!!this.allowCrossRootRegistration}))}disconnectedCallback(){super.disconnectedCallback(),this.__unregisterFormElement()}__unregisterFormElement(){this._parentFormGroup&&this._parentFormGroup.removeFormElement(this)}}),Oo=Br(e=>class extends Do(Vr(Gr(e))){static get properties(){return{readOnly:{type:Boolean,attribute:`readonly`,reflect:!0},label:String,labelSrOnly:{type:Boolean,attribute:`label-sr-only`,reflect:!0},helpText:{type:String,attribute:`help-text`},modelValue:{attribute:!1},_ariaLabelledNodes:{attribute:!1},_ariaDescribedNodes:{attribute:!1},_repropagationRole:{attribute:!1},_isRepropagationEndpoint:{attribute:!1}}}get label(){return this.__label??(this._labelNode?.textContent||``)}set label(e){let t=this.label;this.__label=e,this.requestUpdate(`label`,t)}get helpText(){return this.__helpText??(this._helpTextNode?.textContent||``)}set helpText(e){let t=this.helpText;this.__helpText=e,this.requestUpdate(`helpText`,t)}get fieldName(){return this.__fieldName||this.label||this.name||``}set fieldName(e){this.__fieldName=e}get slots(){return{...super.slots,label:()=>{let e=document.createElement(`label`);return e.textContent=this.label,e},"help-text":()=>{let e=document.createElement(`div`);return e.textContent=this.helpText,e}}}get _inputNode(){return this.__getDirectSlotChild(`input`)}get _labelNode(){return this.__getDirectSlotChild(`label`)}get _helpTextNode(){return this.__getDirectSlotChild(`help-text`)}get _feedbackNode(){return this.__getDirectSlotChild(`feedback`)}static enabledWarnings=super.enabledWarnings?.filter(e=>e!==`change-in-update`)||[];constructor(){super(),this.readOnly=!1,this.labelSrOnly=!1,this._inputId=Jr(this.localName),this._ariaLabelledNodes=[],this._ariaDescribedNodes=[],this._repropagationRole=`child`,this._isRepropagationEndpoint=!1,this.addEventListener(`model-value-changed`,this.__repropagateChildrenValues),this._onLabelClick=this._onLabelClick.bind(this)}connectedCallback(){super.connectedCallback(),this._enhanceLightDomClasses(),this._enhanceLightDomA11y(),this._triggerInitialModelValueChangedEvent(),this._labelNode&&this._labelNode.addEventListener(`click`,this._onLabelClick)}disconnectedCallback(){super.disconnectedCallback(),this._labelNode&&this._labelNode.removeEventListener(`click`,this._onLabelClick)}updated(e){super.updated(e),e.has(`disabled`)&&this._inputNode?.setAttribute(`aria-disabled`,`${!!this.disabled}`),e.has(`_ariaLabelledNodes`)&&this.__reflectAriaAttr(`aria-labelledby`,this._ariaLabelledNodes,this.__reorderAriaLabelledNodes),e.has(`_ariaDescribedNodes`)&&this.__reflectAriaAttr(`aria-describedby`,this._ariaDescribedNodes,this.__reorderAriaDescribedNodes),e.has(`label`)&&this.__label!==void 0&&this._labelNode&&(this._labelNode.textContent=this.label),e.has(`helpText`)&&this.__helpText!==void 0&&this._helpTextNode&&(this._helpTextNode.textContent=this.helpText),e.has(`name`)&&this.dispatchEvent(new CustomEvent(`form-element-name-changed`,{detail:{oldName:e.get(`name`),newName:this.name},bubbles:!0}))}_triggerInitialModelValueChangedEvent(){this._dispatchInitialModelValueChangedEvent()}_enhanceLightDomClasses(){this._inputNode&&this._inputNode.classList.add(`form-control`)}_enhanceLightDomA11y(){let{_inputNode:e,_labelNode:t,_helpTextNode:n,_feedbackNode:r}=this;e&&(e.id=e.id||this._inputId),t&&(t.setAttribute(`for`,this._inputId),this.addToAriaLabelledBy(t,{idPrefix:`label`})),n&&this.addToAriaDescribedBy(n,{idPrefix:`help-text`}),r&&(this.addEventListener(`focusin`,()=>{r.setAttribute(`aria-live`,`polite`)}),this.addEventListener(`focusout`,()=>{r.setAttribute(`aria-live`,`assertive`)}),this.addToAriaDescribedBy(r,{idPrefix:`feedback`})),this._enhanceLightDomA11yForAdditionalSlots()}_enhanceLightDomA11yForAdditionalSlots(e=[`prefix`,`suffix`,`before`,`after`]){e.forEach(e=>{let t=this.__getDirectSlotChild(e);t&&(t.hasAttribute(`data-label`)&&this.addToAriaLabelledBy(t,{idPrefix:e}),t.hasAttribute(`data-description`)&&this.addToAriaDescribedBy(t,{idPrefix:e}))})}__reflectAriaAttr(e,t,n){if(this._inputNode){if(n){let e=t.filter(e=>this.contains(e)),n=t.filter(e=>!this.contains(e)),r=[...To(e.map(e=>e.assignedSlot||e))],i=[];r.forEach(t=>{e.forEach(e=>{t.name===e.slot&&i.push(e)})}),t=[...i,...n]}let r=t.map(e=>e.id).join(` `);this._inputNode.setAttribute(e,r)}}render(){return v`
        <div class="form-field__group-one">${this._groupOneTemplate()}</div>
        <div class="form-field__group-two">${this._groupTwoTemplate()}</div>
      `}_groupOneTemplate(){return v` ${this._labelTemplate()} ${this._helpTextTemplate()} `}_groupTwoTemplate(){return v` ${this._inputGroupTemplate()} ${this._feedbackTemplate()} `}_labelTemplate(){return v`
        <div class="form-field__label">
          <slot name="label"></slot>
        </div>
      `}_helpTextTemplate(){return v`
        <small class="form-field__help-text">
          <slot name="help-text"></slot>
        </small>
      `}_inputGroupTemplate(){return v`
        <div class="input-group">
          ${this._inputGroupBeforeTemplate()}
          <div class="input-group__container">
            ${this._inputGroupPrefixTemplate()} ${this._inputGroupInputTemplate()}
            ${this._inputGroupSuffixTemplate()}
          </div>
          ${this._inputGroupAfterTemplate()}
        </div>
      `}_inputGroupBeforeTemplate(){return v`
        <div class="input-group__before">
          <slot name="before"></slot>
        </div>
      `}_inputGroupPrefixTemplate(){return Array.from(this.children).find(e=>e.slot===`prefix`)?v`
            <div class="input-group__prefix">
              <slot name="prefix"></slot>
            </div>
          `:C}_inputGroupInputTemplate(){return v`
        <div class="input-group__input">
          <slot name="input"></slot>
        </div>
      `}_inputGroupSuffixTemplate(){return Array.from(this.children).find(e=>e.slot===`suffix`)?v`
            <div class="input-group__suffix">
              <slot name="suffix"></slot>
            </div>
          `:C}_inputGroupAfterTemplate(){return v`
        <div class="input-group__after">
          <slot name="after"></slot>
        </div>
      `}_feedbackTemplate(){return v`
        <div class="form-field__feedback">
          <slot name="feedback"></slot>
        </div>
      `}_isEmpty(e=this.modelValue){let t=e;return this.modelValue instanceof Eo&&(t=this.modelValue.viewValue),typeof t==`object`&&t&&!(t instanceof Date)?!Object.keys(t).length:!t&&!(typeof t==`number`&&(t===0||Number.isNaN(t)))&&!(typeof t==`boolean`&&t===!1)}static get styles(){return[b`
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
        `]}_getAriaDescriptionElements(){return[this._helpTextNode,this._feedbackNode]}addToAriaLabelledBy(e,{idPrefix:t=``,reorder:n=!0}={}){e.id=e.id||`${t}-${this._inputId}`,this._ariaLabelledNodes.includes(e)||(this._ariaLabelledNodes=[...this._ariaLabelledNodes,e],this.__reorderAriaLabelledNodes=!!n)}removeFromAriaLabelledBy(e){this._ariaLabelledNodes.includes(e)&&(this._ariaLabelledNodes.splice(this._ariaLabelledNodes.indexOf(e),1),this._ariaLabelledNodes=[...this._ariaLabelledNodes],this.__reorderAriaLabelledNodes=!1)}addToAriaDescribedBy(e,{idPrefix:t=``,reorder:n=!0}={}){e.id=e.id||`${t}-${this._inputId}`,this._ariaDescribedNodes.includes(e)||(this._ariaDescribedNodes=[...this._ariaDescribedNodes,e],this.__reorderAriaDescribedNodes=!!n)}removeFromAriaDescribedBy(e){this._ariaDescribedNodes.includes(e)&&(this._ariaDescribedNodes.splice(this._ariaDescribedNodes.indexOf(e),1),this._ariaDescribedNodes=[...this._ariaDescribedNodes],this.__reorderAriaLabelledNodes=!1)}__getDirectSlotChild(e){return Array.from(this.children).find(t=>t.slot===e)}_dispatchInitialModelValueChangedEvent(){this._repropagationRole!==`child`&&(this.__repropagateChildrenInitialized=!0,this.dispatchEvent(new CustomEvent(`model-value-changed`,{bubbles:!0,detail:{formPath:[this],initialize:!0,isTriggeredByUser:!1}})))}_onBeforeRepropagateChildrenValues(e){}__repropagateChildrenValues(e){this._onBeforeRepropagateChildrenValues(e);let t=e.detail&&e.detail.element||e.target,n=this._isRepropagationEndpoint||this._repropagationRole===`choice-group`;if(t===this)return;e.stopImmediatePropagation();let r=this._repropagationRole!==`child`&&!this.__repropagateChildrenInitialized,i=e.detail&&e.detail.initialize;if(r||i||!this._repropagationCondition(t))return;let a=[];n||(a=e.detail&&e.detail.formPath||[t]);let o=[...a,this];this.dispatchEvent(new CustomEvent(`model-value-changed`,{bubbles:!0,detail:{formPath:o,isTriggeredByUser:!!e.detail?.isTriggeredByUser}}))}_repropagationCondition(e){return!!e}_onLabelClick(){}}),ko=class{constructor(){this.__running=!1,this.__queue=[]}add(e){this.__queue.push(e),this.__running||(this.complete=new Promise(e=>{this.__callComplete=e}),this.__run())}async __run(){this.__running=!0,await this.__queue[0](),this.__queue.shift(),this.__queue.length>0?this.__run():(this.__running=!1,this.__callComplete&&this.__callComplete())}};function Ao(e){return e.charAt(0).toUpperCase()+e.slice(1)}var jo=Br(e=>class extends e{constructor(){super(),this.__SyncUpdatableNamespace={}}firstUpdated(e){super.firstUpdated(e),this.__syncUpdatableInitialize()}connectedCallback(){super.connectedCallback(),this.__SyncUpdatableNamespace.connected=!0}disconnectedCallback(){super.disconnectedCallback(),this.__SyncUpdatableNamespace.connected=!1}static enabledWarnings=super.enabledWarnings?.filter(e=>e!==`change-in-update`)||[];static __syncUpdatableHasChanged(e,t,n){let r=this.elementProperties;return r.get(e)&&r.get(e).hasChanged?r.get(e).hasChanged(t,n):t!==n}__syncUpdatableInitialize(){let e=this.__SyncUpdatableNamespace,t=this.constructor;e.initialized=!0,e.queue&&Array.from(e.queue).forEach(e=>{t.__syncUpdatableHasChanged(e,this[e],void 0)&&this.updateSync(e,void 0)})}requestUpdate(e,t,n){if(super.requestUpdate(e,t,n),e===void 0)return;this.__SyncUpdatableNamespace=this.__SyncUpdatableNamespace||{};let r=this.__SyncUpdatableNamespace,i=this.constructor;r.initialized?i.__syncUpdatableHasChanged(e,this[e],t)&&this.updateSync(e,t):(r.queue=r.queue||new Set,r.queue.add(e))}updateSync(e,t){}}),Mo=e=>{switch(e){case`bg-BG`:return k(()=>import(`./bg-BG-BqN8Ofy5.js`),__vite__mapDeps([0,1]),import.meta.url);case`bg`:return k(()=>import(`./bg-3ZZjfJhO.js`),[],import.meta.url);case`cs-CZ`:return k(()=>import(`./cs-CZ-SN-3IU_U.js`),__vite__mapDeps([2,3]),import.meta.url);case`cs`:return k(()=>import(`./cs-BmsyPq5Q.js`),[],import.meta.url);case`de-DE`:return k(()=>import(`./de-DE-oKn4vR-b.js`),__vite__mapDeps([4,5]),import.meta.url);case`de`:return k(()=>import(`./de-CVq70aXQ.js`),[],import.meta.url);case`en-AU`:return k(()=>import(`./en-AU-CmzwKX8Q.js`),__vite__mapDeps([6,7]),import.meta.url);case`en-GB`:return k(()=>import(`./en-GB-DjRN3mtz.js`),__vite__mapDeps([8,7]),import.meta.url);case`en-US`:return k(()=>import(`./en-US-TWZ1e6QA.js`),__vite__mapDeps([9,7]),import.meta.url);case`en-PH`:case`en`:return k(()=>import(`./en-CsSts4wN.js`),[],import.meta.url);case`es-ES`:return k(()=>import(`./es-ES-BvEsTaKf.js`),__vite__mapDeps([10,11]),import.meta.url);case`es`:return k(()=>import(`./es-CxsbU-E3.js`),[],import.meta.url);case`fr-FR`:return k(()=>import(`./fr-FR-DB0f76VO.js`),__vite__mapDeps([12,13]),import.meta.url);case`fr-BE`:return k(()=>import(`./fr-BE-CbimjE5l.js`),__vite__mapDeps([14,13]),import.meta.url);case`fr`:return k(()=>import(`./fr-BwVS4ltX.js`),[],import.meta.url);case`hu-HU`:return k(()=>import(`./hu-HU-pJb0gbQP.js`),__vite__mapDeps([15,16]),import.meta.url);case`hu`:return k(()=>import(`./hu-BETqMCB_.js`),[],import.meta.url);case`it-IT`:return k(()=>import(`./it-IT-Dv9Syq6Q.js`),__vite__mapDeps([17,18]),import.meta.url);case`it`:return k(()=>import(`./it-Dw2AvXxt.js`),[],import.meta.url);case`nl-BE`:return k(()=>import(`./nl-BE-CgXEiADS.js`),__vite__mapDeps([19,20]),import.meta.url);case`nl-NL`:return k(()=>import(`./nl-NL-BGQpRlMS.js`),__vite__mapDeps([21,20]),import.meta.url);case`nl`:return k(()=>import(`./nl-8ESsQqJ_.js`),[],import.meta.url);case`pl-PL`:return k(()=>import(`./pl-PL-CBME1Vm2.js`),__vite__mapDeps([22,23]),import.meta.url);case`pl`:return k(()=>import(`./pl-BNJOnG4_.js`),[],import.meta.url);case`ro-RO`:return k(()=>import(`./ro-RO-CnXz0UKw.js`),__vite__mapDeps([24,25]),import.meta.url);case`ro`:return k(()=>import(`./ro-CVZKR3R4.js`),[],import.meta.url);case`ru-RU`:return k(()=>import(`./ru-RU-CIZ4zq9n.js`),__vite__mapDeps([26,27]),import.meta.url);case`ru`:return k(()=>import(`./ru-B8aGAE0G.js`),[],import.meta.url);case`sk-SK`:return k(()=>import(`./sk-SK-DqVU7k95.js`),__vite__mapDeps([28,29]),import.meta.url);case`sk`:return k(()=>import(`./sk-GonuccUi.js`),[],import.meta.url);case`tr-TR`:return k(()=>import(`./tr-TR-DSpBczmi.js`),__vite__mapDeps([30,31]),import.meta.url);case`tr`:return k(()=>import(`./tr-D9qhUS6G.js`),[],import.meta.url);case`uk-UA`:return k(()=>import(`./uk-UA-DsV3eA-4.js`),__vite__mapDeps([32,33]),import.meta.url);case`uk`:return k(()=>import(`./uk-ChYu7zY6.js`),[],import.meta.url);case`zh-CN`:case`zh`:return k(()=>import(`./zh-kfHaMEPE.js`),[],import.meta.url);default:return k(()=>import(`./en-CsSts4wN.js`),[],import.meta.url)}},No=e=>`${e[0].toUpperCase()}${e.slice(1)}`,Po=class extends _o(w){static get properties(){return{feedbackData:{attribute:!1}}}static localizeNamespaces=[{"lion-form-core":Mo},...super.localizeNamespaces];static get styles(){return[b`
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
      `]}constructor(){super(),this.feedbackData=void 0}_messageTemplate({message:e}){return e}updated(e){super.updated(e),this.feedbackData&&this.feedbackData[0]?(this.setAttribute(`type`,this.feedbackData[0].type),this.currentType=this.feedbackData[0].type):this.currentType!==`success`&&this.removeAttribute(`type`)}render(){return v`
      ${this.feedbackData&&this.feedbackData.map(({message:e,type:t,validator:n})=>v`
          <span class="validation-feedback__type">
            ${e&&t?this._localizeManager.msg(`lion-form-core:validation${No(t)}`):C}
          </span>
          ${this._messageTemplate({message:e,type:t,validator:n})}
        `)}
    `}},Fo=class extends EventTarget{constructor(e,t){super(),this.__param=e,this.__config=t||{},this.type=t?.type||`error`}static _$isValidator$=!0;static validatorName=``;static async=!1;execute(e,t,n){if(!this.constructor.validatorName)throw Error(`A validator needs to have a name! Please set it via "static get validatorName() { return 'IsCat'; }"`);return!0}set param(e){this.__param=e,this.dispatchEvent(new Event(`param-changed`))}get param(){return this.__param}set config(e){this.__config=e,this.dispatchEvent(new Event(`config-changed`))}get config(){return this.__config}async _getMessage(e){let t=this.constructor,n={name:t.validatorName,type:this.type,params:this.param,config:this.config,...e};if(this.config.getMessage){if(typeof this.config.getMessage==`function`)return this.config.getMessage(n);throw Error(`You must provide a value for getMessage of type 'function', you provided a value of type: ${typeof this.config.getMessage}`)}return t.getMessage(n)}static async getMessage(e){return`Please configure an error message for "${this.name}" by overriding "static async getMessage()"`}onFormControlConnect(e){}onFormControlDisconnect(e){}abortExecution(){}};function Io(e=[],t=[]){return e.filter(e=>!t.includes(e)).concat(t.filter(t=>!e.includes(t)))}function Lo(e){return e instanceof Eo?e.viewValue:e}var Ro=Br(e=>class extends Oo(jo(Vr(Gr(Co(e))))){static get scopedElements(){return{...super.scopedElements,"lion-validation-feedback":Po}}static get properties(){return{validators:{attribute:!1},hasFeedbackFor:{attribute:!1},shouldShowFeedbackFor:{attribute:!1},showsFeedbackFor:{type:Array,attribute:`shows-feedback-for`,reflect:!0,converter:{fromAttribute:e=>e.split(`,`),toAttribute:e=>e.join(`,`)}},validationStates:{attribute:!1},isPending:{type:Boolean,attribute:`is-pending`,reflect:!0},defaultValidators:{attribute:!1},_visibleMessagesAmount:{attribute:!1},__childModelValueChanged:{attribute:!1}}}static get validationTypes(){return[`error`]}get operationMode(){return`enter`}get slots(){return{...super.slots,feedback:()=>{let e=this.createScopedElement(`lion-validation-feedback`);return e.setAttribute(`data-tag-name`,`lion-validation-feedback`),e}}}get _allValidators(){return[...this.validators,...this.defaultValidators]}constructor(){super(),this.hasFeedbackFor=[],this.showsFeedbackFor=[],this.shouldShowFeedbackFor=[],this.validationStates={},this.isPending=!1,this.validators=[],this.defaultValidators=[],this._visibleMessagesAmount=1,this.__syncValidationResult=[],this.__asyncValidationResult=[],this.__validationResult=[],this.__prevValidationResult=[],this.__prevShownValidationResult=[],this.__childModelValueChanged=!1,this._onValidatorUpdated=this._onValidatorUpdated.bind(this),this._updateFeedbackComponent=this._updateFeedbackComponent.bind(this)}connectedCallback(){super.connectedCallback(),ro().addEventListener(`localeChanged`,this._updateFeedbackComponent)}disconnectedCallback(){super.disconnectedCallback(),ro().removeEventListener(`localeChanged`,this._updateFeedbackComponent)}firstUpdated(e){super.firstUpdated(e),this.__isValidateInitialized=!0,this.validate(),this._repropagationRole!==`child`&&this.addEventListener(`model-value-changed`,()=>{this.__childModelValueChanged=!0})}updateSync(e,t){if(super.updateSync(e,t),e===`validators`?(this.__setupValidators(),this.validate({clearCurrentResult:!0})):e===`modelValue`&&this.validate({clearCurrentResult:!0}),[`touched`,`dirty`,`prefilled`,`focused`,`submitted`,`hasFeedbackFor`,`filled`].includes(e)&&this._updateShouldShowFeedbackFor(),e===`showsFeedbackFor`){this._inputNode&&this._inputNode.setAttribute(`aria-invalid`,`${this._hasFeedbackVisibleFor(`error`)}`);let e=Io(this.showsFeedbackFor,t);e.length>0&&this.dispatchEvent(new Event(`showsFeedbackForChanged`,{bubbles:!0})),e.forEach(e=>{this.dispatchEvent(new Event(`showsFeedbackFor${Ao(e)}Changed`,{bubbles:!0}))})}e===`shouldShowFeedbackFor`&&Io(this.shouldShowFeedbackFor,t).length>0&&this.dispatchEvent(new Event(`shouldShowFeedbackForChanged`,{bubbles:!0}))}async validate({clearCurrentResult:e=!1}={}){if(this.validateComplete=new Promise(e=>{this.__validateCompleteResolve=e}),this.disabled||this.readOnly){this.__clearValidationResults(),this.__finishValidationPass(),this._updateFeedbackComponent();return}this.__isValidateInitialized&&(this.__prevValidationResult=this.__validationResult,e&&this.__clearValidationResults(),await this.__executeValidators())}#e(e){let t=e;for(;t;){if(t.constructor.validatorName===`Required`)return!0;t=Object.getPrototypeOf(t)}return!1}async __executeValidators(){let e=Lo(this.modelValue),t=this.__isEmpty(e);if(this.__syncValidationResult=[],t){let e=!this._isFormOrFieldset,t=this._allValidators.find(e=>e.constructor?.validatorName===`Required`);if(t&&(this.__syncValidationResult=[{validator:t,outcome:!0}]),e){this.__finishValidationPass({syncValidationResult:this.__syncValidationResult});return}}let n=[],r=[],i=[];for(let e of this._allValidators)e?.executeOnResults?n.push(e):this.#e(e)||(e.constructor.async?i.push(e):r.push(e));let a=!!i.length;this.__syncValidationResult=[...this.__syncValidationResult,...this.__executeSyncValidators(r,e)],this.__finishValidationPass({syncValidationResult:this.__syncValidationResult,metaValidators:n}),a?(this.isPending=!0,this.__asyncValidationResult=await this.__executeAsyncValidators(i,e),this.isPending=!1,this.__finishValidationPass({syncValidationResult:this.__syncValidationResult,asyncValidationResult:this.__asyncValidationResult,metaValidators:n}),this.__validateCompleteResolve?.(!0)):this.__validateCompleteResolve?.(!0)}__executeSyncValidators(e,t){return e.map(e=>({validator:e,outcome:e.execute(t,e.param,{node:this})})).filter(e=>!!e.outcome)}async __executeAsyncValidators(e,t){let n=e.map(e=>e.execute(t,e.param,{node:this})),r=await Promise.all(n);return r.map((t,n)=>({validator:e[n],outcome:r[n]})).filter(e=>!!e.outcome)}__executeMetaValidators(e,t){return t.length?this._isEmpty(this.modelValue)?(this.__prevShownValidationResult=[],[]):t.map(t=>({validator:t,outcome:t.executeOnResults({regularValidationResult:e.map(e=>e.validator),prevValidationResult:this.__prevValidationResult.map(e=>e.validator),prevShownValidationResult:this.__prevShownValidationResult.map(e=>e.validator)})})).filter(e=>!!e.outcome):[]}__finishValidationPass({syncValidationResult:e=[],asyncValidationResult:t=[],metaValidators:n=[]}={}){let r=[...e,...t],i=this.__executeMetaValidators(r,n);this.__validationResult=[...i,...r];let a=this.constructor.validationTypes.reduce((e,t)=>({...e,[t]:{}}),{});for(let{validator:e,outcome:t}of this.__validationResult){a[e.type]||(a[e.type]={});let n=e.constructor;a[e.type][n.validatorName]=t}this.validationStates=a,this.hasFeedbackFor=[...new Set(this.__validationResult.map(({validator:e})=>e.type))],this.dispatchEvent(new Event(`validate-performed`,{bubbles:!0}))}__clearValidationResults(){this.__syncValidationResult=[],this.__asyncValidationResult=[]}_onValidatorUpdated(e){(e.type===`param-changed`||e.type===`config-changed`)&&this.validate()}__setupValidators(){let e=[`param-changed`,`config-changed`];for(let t of this.__prevValidators||[]){for(let n of e)t.removeEventListener?.(n,this._onValidatorUpdated);t.onFormControlDisconnect(this)}for(let t of this._allValidators){if(t.constructor._$isValidator$===void 0){let e=`Validators array only accepts class instances of Validator. Type "${Array.isArray(t)?`array`:typeof t}" found. This may be caused by having multiple installations of "@lion/ui/form-core.js".`;throw console.error(e,this),Error(e)}let n=this.constructor,r=t.constructor;if(n.validationTypes.indexOf(t.type)===-1){let e=`This component does not support the validator type "${t.type}" used in "${r.validatorName}". You may change your validators type or add it to the components "static get validationTypes() {}".`;throw console.error(e,this),Error(e)}for(let n of e)t.addEventListener?.(n,e=>{this._onValidatorUpdated(e,{validator:t})});t.onFormControlConnect(this)}this.__prevValidators=this._allValidators}__isEmpty(e){return typeof this._isEmpty==`function`?this._isEmpty(e):this.modelValue===null||this.modelValue===void 0||this.modelValue===``}async __getFeedbackMessages(e){let t=await this.fieldName;return Promise.all(e.map(async({validator:e,outcome:n})=>(e.config.fieldName&&(t=await e.config.fieldName),{message:await e._getMessage({modelValue:this.modelValue,formControl:this,fieldName:t,outcome:n}),type:e.type,validator:e,visibilityDuration:e.config?.visibilityDuration||3e3})))}_updateFeedbackComponent(){window.clearTimeout(this.removeMessage);let{_feedbackNode:e}=this;e&&(this.__feedbackQueue||=new ko,this.showsFeedbackFor.length>0?this.__feedbackQueue.add(async()=>{let t=this._prioritizeAndFilterFeedback({validationResult:this.__validationResult.map(e=>e.validator)});this.__prioritizedResult=t.map(e=>this.__validationResult.find(t=>e===t.validator)).filter(Boolean),this.__prioritizedResult.length>0&&(this.__prevShownValidationResult=this.__prioritizedResult);let n=await this.__getFeedbackMessages(this.__prioritizedResult);e.feedbackData=n||[],n?.[0]&&n[0].type===`success`&&n[0].visibilityDuration!==1/0&&(this.removeMessage=window.setTimeout(()=>{e.removeAttribute(`type`),e.feedbackData=[]},n[0].visibilityDuration))}):this.__feedbackQueue.add(async()=>{e.feedbackData=[]}),this.feedbackComplete=this.__feedbackQueue.complete)}_showFeedbackConditionFor(e,t){return!0}get _feedbackConditionMeta(){return{modelValue:this.modelValue,el:this}}feedbackCondition(e,t=this._feedbackConditionMeta,n=this._showFeedbackConditionFor.bind(this)){return n(e,t)}_hasFeedbackVisibleFor(e){return this.hasFeedbackFor?.includes(e)&&this.shouldShowFeedbackFor?.includes(e)}updated(e){if(super.updated(e),e.has(`shouldShowFeedbackFor`)||e.has(`hasFeedbackFor`)){let e=this.constructor;this.showsFeedbackFor=e.validationTypes.map(e=>this._hasFeedbackVisibleFor(e)?e:void 0).filter(Boolean),this._updateFeedbackComponent()}if(e.has(`__childModelValueChanged`)&&this.__childModelValueChanged&&(this.validate({clearCurrentResult:!0}),this.__childModelValueChanged=!1),e.has(`validationStates`)){let t=e.get(`validationStates`);t&&Object.entries(this.validationStates).forEach(([e,n])=>{t[e]&&JSON.stringify(n)!==JSON.stringify(t[e])&&this.dispatchEvent(new CustomEvent(`${e}StateChanged`,{detail:n}))})}}_updateShouldShowFeedbackFor(){let e=this.constructor.validationTypes.map(e=>this.feedbackCondition(e,this._feedbackConditionMeta,this._showFeedbackConditionFor.bind(this))?e:void 0).filter(Boolean);JSON.stringify(this.shouldShowFeedbackFor)!==JSON.stringify(e)&&(this.shouldShowFeedbackFor=e)}_prioritizeAndFilterFeedback({validationResult:e}){let t=this.constructor.validationTypes;return e.filter(e=>this.feedbackCondition(e.type,this._feedbackConditionMeta,this._showFeedbackConditionFor.bind(this))).sort((e,n)=>t.indexOf(e.type)-t.indexOf(n.type)).slice(0,this._visibleMessagesAmount)}}),zo=Br(e=>class extends Ro(Oo(e)){static get properties(){return{formattedValue:{attribute:!1},serializedValue:{attribute:!1},formatOptions:{attribute:!1}}}#e={didFormatterOutputSyncToView:!1,didFormatterRun:!1};requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`modelValue`&&this.modelValue!==t&&this._onModelValueChanged({modelValue:this.modelValue},{modelValue:t}),e===`serializedValue`&&this.serializedValue!==t&&this._calculateValues({source:`serialized`}),e===`formattedValue`&&this.formattedValue!==t&&this._calculateValues({source:`formatted`})}get value(){return this._inputNode?.value||this.__value||``}set value(e){this._inputNode?(this._inputNode.value=e,this.__value=void 0):this.__value=e}preprocessor(e,t){}parser(e,t){return e}formatter(e,t){return e}serializer(e){return e===void 0?``:e}deserializer(e){return e===void 0?``:e}_calculateValues({source:e}={source:null}){this.__preventRecursiveTrigger||(this.__preventRecursiveTrigger=!0,e!==`model`&&(e===`serialized`?this.modelValue=this.deserializer(this.serializedValue):e===`formatted`&&(this.modelValue=this._callParser())),e!==`formatted`&&(this.formattedValue=this._callFormatter()),e!==`serialized`&&(this.serializedValue=this.serializer(this.modelValue)),this._reflectBackFormattedValueToUser(),this.__preventRecursiveTrigger=!1,this.__prevViewValue=this.value)}_callParser(e=this.formattedValue){if(e===``)return``;if(typeof e!=`string`)return;let t=this.parser(e,{...this.formatOptions,mode:this.#t(),viewValueStates:this.#n()});return t===void 0?new Eo(e):t}_callFormatter(){return this.#e.didFormatterRun=!1,this._isHandlingUserInput&&this.hasFeedbackFor?.includes(`error`)&&this._inputNode?this.value:this.modelValue instanceof Eo?this.modelValue.viewValue:(this.#e.didFormatterRun=!0,this.formatter(this.modelValue,{...this.formatOptions,mode:this.#t(),viewValueStates:this.#n()}))}_onModelValueChanged(...e){this._calculateValues({source:`model`}),this._dispatchModelValueChangedEvent(...e)}_dispatchModelValueChangedEvent(...e){this.dispatchEvent(new CustomEvent(`model-value-changed`,{bubbles:!0,detail:{formPath:[this],isTriggeredByUser:!!this._isHandlingUserInput}}))}_syncValueUpwards(){this.__isHandlingComposition||this.__handlePreprocessor();let e=this.formattedValue;this.modelValue=this._callParser(this.value),e===this.formattedValue&&this.__prevViewValue!==this.value&&this._calculateValues()}__handlePreprocessor(){let e=this.value.length;this._inputNode&&`selectionStart`in this._inputNode&&this._inputNode?.type!==`range`&&(e=this._inputNode.selectionStart);let t=this.preprocessor(this.value,{...this.formatOptions,currentCaretIndex:e,prevViewValue:this.__prevViewValue});if(t!==void 0){if(typeof t==`string`)this.value=t;else if(typeof t==`object`){let{viewValue:e,caretIndex:n}=t;this.value=e,n&&this._inputNode&&`selectionStart`in this._inputNode&&(this._inputNode.selectionStart=n,this._inputNode.selectionEnd=n)}}}_reflectBackFormattedValueToUser(){this._reflectBackOn()&&(this.value=this.formattedValue===void 0?``:this.formattedValue,this.#e.didFormatterOutputSyncToView=!!this.formattedValue&&this.#e.didFormatterRun)}_reflectBackOn(){return!this._isHandlingUserInput}_proxyInputEvent(){this.dispatchEvent(new Event(`user-input-changed`,{bubbles:!0}))}_onUserInputChanged(){this._isHandlingUserInput=!0,this._syncValueUpwards(),this._isHandlingUserInput=!1}__onCompositionEvent({type:e}){e===`compositionstart`?this.__isHandlingComposition=!0:e===`compositionend`&&(this.__isHandlingComposition=!1,this._syncValueUpwards())}constructor(){super(),this.formatOn=`change`,this.formatOptions={mode:`auto`},this.formattedValue=void 0,this.serializedValue=void 0,this._isPasting=!1,this._isHandlingUserInput=!1,this.__prevViewValue=``,this.__onCompositionEvent=this.__onCompositionEvent.bind(this),this.addEventListener(`user-input-changed`,this._onUserInputChanged),this.addEventListener(`paste`,this.__onPaste),this._reflectBackFormattedValueToUser=this._reflectBackFormattedValueToUser.bind(this),this._reflectBackFormattedValueDebounced=()=>{setTimeout(this._reflectBackFormattedValueToUser)}}__onPaste(){this._isPasting=!0,setTimeout(()=>{this._isPasting=!1})}connectedCallback(){super.connectedCallback(),this.modelValue===void 0&&this._syncValueUpwards(),this.__prevViewValue=this.value,this._reflectBackFormattedValueToUser(),this._inputNode&&(this._inputNode.addEventListener(this.formatOn,this._reflectBackFormattedValueDebounced),this._inputNode.addEventListener(`input`,this._proxyInputEvent),this._inputNode.addEventListener(`compositionstart`,this.__onCompositionEvent),this._inputNode.addEventListener(`compositionend`,this.__onCompositionEvent))}disconnectedCallback(){super.disconnectedCallback(),this._inputNode&&(this._inputNode.removeEventListener(`input`,this._proxyInputEvent),this._inputNode.removeEventListener(this.formatOn,this._reflectBackFormattedValueDebounced),this._inputNode.removeEventListener(`compositionstart`,this.__onCompositionEvent),this._inputNode.removeEventListener(`compositionend`,this.__onCompositionEvent))}#t(){return this._isPasting?`pasted`:this._isHandlingUserInput&&this.__prevViewValue?`user-edited`:`auto`}#n(){let e=[];return this.#e.didFormatterOutputSyncToView&&e.push(`formatted`),e}}),Bo=Br(e=>class extends Oo(e){static get properties(){return{touched:{type:Boolean,reflect:!0},dirty:{type:Boolean,reflect:!0},filled:{type:Boolean,reflect:!0},prefilled:{attribute:!1},submitted:{attribute:!1}}}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`touched`&&this.touched!==t&&this._onTouchedChanged(),e===`modelValue`&&(this.filled=!this._isEmpty()),e===`dirty`&&this.dirty!==t&&this._onDirtyChanged()}constructor(){super(),this.touched=!1,this.dirty=!1,this.prefilled=!1,this.filled=!1,this._leaveEvent=`blur`,this._valueChangedEvent=`model-value-changed`,this._iStateOnLeave=this._iStateOnLeave.bind(this),this._iStateOnValueChange=this._iStateOnValueChange.bind(this)}connectedCallback(){super.connectedCallback(),this.addEventListener(this._leaveEvent,this._iStateOnLeave),this.addEventListener(this._valueChangedEvent,this._iStateOnValueChange),this.initInteractionState()}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener(this._leaveEvent,this._iStateOnLeave),this.removeEventListener(this._valueChangedEvent,this._iStateOnValueChange)}initInteractionState(){this.dirty=!1,this.prefilled=!this._isEmpty()}_iStateOnLeave(){this.touched=!0,this.prefilled=!this._isEmpty()}_iStateOnValueChange(){this.dirty=!0}resetInteractionState(){this.touched=!1,this.submitted=!1,this.dirty=!1,this.prefilled=!this._isEmpty()}_onTouchedChanged(){this.dispatchEvent(new Event(`touched-changed`,{bubbles:!0,composed:!0}))}_onDirtyChanged(){this.dispatchEvent(new Event(`dirty-changed`,{bubbles:!0,composed:!0}))}_showFeedbackConditionFor(e,t){return t.touched&&t.dirty||t.prefilled||t.submitted}get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,submitted:this.submitted,touched:this.touched,dirty:this.dirty,filled:this.filled,prefilled:this.prefilled}}}),Vo=class extends Oo(Bo(Aa(zo(Ro(Gr(w)))))){firstUpdated(e){super.firstUpdated(e),this._initialModelValue=this.modelValue}connectedCallback(){super.connectedCallback(),this._onChange=this._onChange.bind(this),this._inputNode.addEventListener(`change`,this._onChange),this.classList.add(`form-field`)}disconnectedCallback(){super.disconnectedCallback(),this._inputNode?.removeEventListener(`change`,this._onChange)}resetInteractionState(){super.resetInteractionState(),this.submitted=!1}reset(){this.modelValue=this._initialModelValue,this.resetInteractionState()}clear(){this.modelValue=``}_onChange(e){this.dispatchEvent(new Event(`user-input-changed`,{bubbles:!0}))}get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,focused:this.focused}}get _focusableNode(){return this._inputNode}},Ho=class extends Array{_keys(){return Object.keys(this).filter(e=>Number.isNaN(Number(e)))}},Uo=Br(e=>class extends Do(e){static get properties(){return{_isFormOrFieldset:{type:Boolean}}}constructor(){super(),this.formElements=new Ho,this._isFormOrFieldset=!1,this._onRequestToAddFormElement=this._onRequestToAddFormElement.bind(this),this._onRequestToChangeFormElementName=this._onRequestToChangeFormElementName.bind(this),this.addEventListener(`form-element-register`,this._onRequestToAddFormElement),this.addEventListener(`form-element-name-changed`,this._onRequestToChangeFormElementName),this.initComplete=new Promise((e,t)=>{this.__resolveInitComplete=e,this.__rejectInitComplete=t}),this.registrationComplete=new Promise((e,t)=>{this.__resolveRegistrationComplete=e,this.__rejectRegistrationComplete=t}),this.registrationComplete.done=!1,this.registrationComplete.then(()=>{this.registrationComplete.done=!0,this.__resolveInitComplete(void 0)},()=>{throw this.registrationComplete.done=!0,this.__rejectInitComplete(void 0),Error(`Registration could not finish. Please use await el.registrationComplete;`)})}connectedCallback(){super.connectedCallback(),this._completeRegistration()}_completeRegistration(){Promise.resolve().then(()=>this.__resolveRegistrationComplete(void 0))}disconnectedCallback(){super.disconnectedCallback(),this.registrationComplete.done===!1&&Promise.resolve().then(()=>{Promise.resolve().then(()=>{this.__rejectRegistrationComplete()})})}isRegisteredFormElement(e){return this.formElements.some(t=>t===e)}addFormElement(e,t){if(e._parentFormGroup=this,t>=0?this.formElements.splice(t,0,e):this.formElements.push(e),this._isFormOrFieldset){let{name:n}=e;if(n===this.name)throw console.info(`Error Node:`,e),TypeError(`You can not have the same name "${n}" as your parent`);if(n.substr(-2)===`[]`)Array.isArray(this.formElements[n])||(this.formElements[n]=new Ho),t>0?this.formElements[n].splice(t,0,e):this.formElements[n].push(e);else if(!this.formElements[n])this.formElements[n]=e;else throw console.info(`Error Node:`,e),TypeError(`Name "${n}" is already registered - if you want an array add [] to the end`)}}removeFormElement(e){let t=this.formElements.indexOf(e);if(t>-1&&this.formElements.splice(t,1),this._isFormOrFieldset){let{name:t}=e;if(t.substr(-2)===`[]`&&this.formElements[t]){let n=this.formElements[t].indexOf(e);n>-1&&this.formElements[t].splice(n,1)}else this.formElements[t]&&delete this.formElements[t]}}_onRequestToAddFormElement(e){let t=e.detail.element;if(t===this||this.isRegisteredFormElement(t))return;e.stopPropagation();let n=-1;if(this.formElements&&Array.isArray(this.formElements)){for(let[e,r]of this.formElements.entries())if(!(r.compareDocumentPosition(t)&Node.DOCUMENT_POSITION_FOLLOWING)){n=e;break}}this.addFormElement(t,n)}_onRequestToChangeFormElementName(e){let t=this.formElements[e.detail.oldName];t&&(this.formElements[e.detail.newName]=t,delete this.formElements[e.detail.oldName])}_onRequestToRemoveFormElement(e){let t=e.detail.element;t!==this&&this.isRegisteredFormElement(t)&&(e.stopPropagation(),this.removeFormElement(t))}}),Wo=Br(e=>class extends e{constructor(){super(),this.registrationTarget=void 0,this.__redispatchEventForFormRegistrarPortalMixin=this.__redispatchEventForFormRegistrarPortalMixin.bind(this),this.addEventListener(`form-element-register`,this.__redispatchEventForFormRegistrarPortalMixin)}__redispatchEventForFormRegistrarPortalMixin(e){if(e.stopPropagation(),!this.registrationTarget)throw Error(`A FormRegistrarPortal element requires a .registrationTarget`);this.registrationTarget.dispatchEvent(new CustomEvent(`form-element-register`,{detail:{element:e.detail.element},bubbles:!0}))}}),Go=Br(e=>class extends zo(Aa(Oo(e))){static get properties(){return{autocomplete:{type:String,reflect:!0}}}constructor(){super(),this.autocomplete=void 0}get _inputNode(){return super._inputNode}get selectionStart(){let e=this._inputNode;return e&&e.selectionStart?e.selectionStart:0}set selectionStart(e){let t=this._inputNode;t&&t.selectionStart&&(t.selectionStart=e)}get selectionEnd(){let e=this._inputNode;return e&&e.selectionEnd?e.selectionEnd:0}set selectionEnd(e){let t=this._inputNode;t&&t.selectionEnd&&(t.selectionEnd=e)}get value(){return this._inputNode&&this._inputNode.value||this.__value||``}set value(e){this._inputNode?(this._inputNode.value!==e&&this._setValueAndPreserveCaret(e),this.__value=void 0):this.__value=e}_setValueAndPreserveCaret(e){if(this.focused)try{if(!(this._inputNode instanceof HTMLSelectElement)){let t=this._inputNode.selectionStart;this._inputNode.value=e,this._inputNode.selectionStart=t,this._inputNode.selectionEnd=t}}catch{this._inputNode.value=e}else this._inputNode.value=e}_reflectBackFormattedValueToUser(){if(super._reflectBackFormattedValueToUser(),this._reflectBackOn()&&this.focused)try{this._inputNode.selectionStart=this._inputNode.value.length}catch{}}get _focusableNode(){return this._inputNode}}),Ko=Br(e=>class extends Uo(Ro(Bo(e))){static get properties(){return{multipleChoice:{type:Boolean,attribute:`multiple-choice`}}}get modelValue(){let e=this._getCheckedElements();return this.multipleChoice?e.map(e=>e.choiceValue):e[0]?e[0].choiceValue:``}set modelValue(e){let t=(t,n)=>typeof t.choiceValue==`object`?JSON.stringify(t.choiceValue)===JSON.stringify(e):t.choiceValue===n;this.__isInitialModelValue?this.registrationComplete.then(()=>{this.__isInitialModelValue=!1,this._setCheckedElements(e,t),this.requestUpdate(`modelValue`,this._oldModelValue)}):(this._setCheckedElements(e,t),this.requestUpdate(`modelValue`,this._oldModelValue)),this._oldModelValue=this.modelValue}get serializedValue(){let e=this._getCheckedElements();return this.multipleChoice?e.map(e=>e.serializedValue.value):e[0]?e[0].serializedValue.value:``}set serializedValue(e){let t=(e,t)=>e.serializedValue.value===t;this.__isInitialSerializedValue?this.registrationComplete.then(()=>{this.__isInitialSerializedValue=!1,this._setCheckedElements(e,t),this.requestUpdate(`serializedValue`)}):(this._setCheckedElements(e,t),this.requestUpdate(`serializedValue`))}get formattedValue(){let e=this._getCheckedElements();return this.multipleChoice?e.map(e=>e.formattedValue):e[0]?e[0].formattedValue:``}set formattedValue(e){let t=(e,t)=>e.formattedValue===t;this.__isInitialFormattedValue?this.registrationComplete.then(()=>{this.__isInitialFormattedValue=!1,this._setCheckedElements(e,t)}):this._setCheckedElements(e,t)}get operationMode(){return this._repropagationRole===`choice-group`?`select`:`enter`}constructor(){super(),this.multipleChoice=!1,this._repropagationRole=`choice-group`,this.__isInitialModelValue=!0,this.__isInitialSerializedValue=!0,this.__isInitialFormattedValue=!0}connectedCallback(){super.connectedCallback(),this.registrationComplete.then(()=>{this.__isInitialModelValue=!1,this.__isInitialSerializedValue=!1,this.__isInitialFormattedValue=!1})}_completeRegistration(){Promise.resolve().then(()=>super._completeRegistration())}updated(e){super.updated(e),e.has(`name`)&&this.name!==e.get(`name`)&&this.formElements.forEach(e=>{e.name=this.name})}addFormElement(e,t){this._throwWhenInvalidChildModelValue(e),e.name=this.name,super.addFormElement(e,t)}clear(){this.multipleChoice?this.modelValue=[]:this.modelValue=``}_triggerInitialModelValueChangedEvent(){this.registrationComplete.then(()=>{this._dispatchInitialModelValueChangedEvent()})}_getFromAllFormElementsFilter(e,t){return!0}_getFromAllFormElements(e,t){let n=t||this._getFromAllFormElementsFilter;if(e===`modelValue`||e===`serializedValue`||e===`formattedValue`)return this[e];let r=this.formElements.filter(t=>n(t,e));return e===`_initialModelValue`?this.multipleChoice?r.filter(t=>t[e].checked).map(t=>t[e].value):r.find(t=>t[e].checked)?.value:r.map(t=>t[e])}_throwWhenInvalidChildModelValue(e){if(typeof e.modelValue.checked!=`boolean`||!Object.prototype.hasOwnProperty.call(e.modelValue,`value`))throw Error(`The ${this.tagName.toLowerCase()} name="${this.name}" does not allow to register ${e.tagName.toLowerCase()} with .modelValue="${e.modelValue}" - The modelValue should represent an Object { value: "foo", checked: false }`)}_isEmpty(){return this.multipleChoice?this.modelValue.length===0:typeof this.modelValue==`string`&&this.modelValue===``||this.modelValue===void 0||this.modelValue===null}_checkSingleChoiceElements(e){let{target:t}=e;if(t.checked===!1)return;let n=t.name;this.formElements.filter(e=>e.name===n).forEach(e=>{e!==t&&(e.checked=!1)})}_getCheckedElements(){return this.formElements.filter(e=>e.checked&&!e.disabled)}_setCheckedElements(e,t){if(e==null){this.formElements.forEach(e=>e.checked=!1);return}for(let n=0;n<this.formElements.length;n+=1)if(this.multipleChoice){let t=e.includes(this.formElements[n].modelValue.value);typeof this.formElements[n].modelValue.value==`object`&&(t=e.map(e=>JSON.stringify(e)).includes(JSON.stringify(this.formElements[n].modelValue.value))),this.formElements[n].checked=t}else t(this.formElements[n],e)?this.formElements[n].checked=!0:this.formElements[n].checked=!1}__setChoiceGroupTouched(){let e=this.modelValue;e!=null&&e!==this.__previousCheckedValue&&(this.touched=!0,this.__previousCheckedValue=e)}_onBeforeRepropagateChildrenValues(e){let t=e.detail&&e.detail.element||e.target;this.multipleChoice||!t.checked||(this.formElements.forEach(e=>{t.choiceValue!==e.choiceValue&&(e.checked=!1)}),this.__setChoiceGroupTouched(),this.requestUpdate(`modelValue`,this._oldModelValue),this._oldModelValue=this.modelValue)}_repropagationCondition(e){return!(this._repropagationRole===`choice-group`&&!this.multipleChoice&&!e.checked)}}),qo=(e,t={})=>e.value!==t.value||e.checked!==t.checked,Jo=Br(e=>class extends zo(e){static get properties(){return{checked:{type:Boolean,reflect:!0},disabled:{type:Boolean,reflect:!0},modelValue:{type:Object,hasChanged:qo},choiceValue:{type:Object}}}get choiceValue(){return this.modelValue.value}set choiceValue(e){this.requestUpdate(`choiceValue`,this.choiceValue),this.modelValue.value!==e&&(this.modelValue={value:e,checked:this.modelValue.checked})}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`modelValue`?this.modelValue.checked!==this.checked&&this.__syncModelCheckedToChecked(this.modelValue.checked):e===`checked`&&this.modelValue.checked!==this.checked&&this.__syncCheckedToModel(this.checked)}firstUpdated(e){super.firstUpdated(e),e.has(`checked`)&&this.__syncCheckedToInputElement()}updated(e){super.updated(e),e.has(`modelValue`)&&this.__syncCheckedToInputElement(),e.has(`name`)&&this._parentFormGroup&&this._parentFormGroup.name!==this.name&&this._syncNameToParentFormGroup()}constructor(){super(),this.modelValue={value:``,checked:!1},this.disabled=!1,this._preventDuplicateLabelClick=this._preventDuplicateLabelClick.bind(this),this._toggleChecked=this._toggleChecked.bind(this)}static get styles(){return[...super.styles||[],b`
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
        `]}render(){return v`
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
      `}_choiceGraphicTemplate(){return C}_afterTemplate(){return C}connectedCallback(){super.connectedCallback(),this._labelNode&&this._labelNode.addEventListener(`click`,this._preventDuplicateLabelClick),this.addEventListener(`user-input-changed`,this._toggleChecked)}disconnectedCallback(){super.disconnectedCallback(),this._labelNode&&this._labelNode.removeEventListener(`click`,this._preventDuplicateLabelClick),this.removeEventListener(`user-input-changed`,this._toggleChecked)}_preventDuplicateLabelClick(e){let t=e=>{e.stopImmediatePropagation(),this._inputNode.removeEventListener(`click`,t)};this._inputNode.addEventListener(`click`,t)}_toggleChecked(e){this.disabled||(this._isHandlingUserInput=!0,this.checked=!this.checked,this._isHandlingUserInput=!1)}_syncNameToParentFormGroup(){this._parentFormGroup.tagName.includes(this.tagName)&&(this.name=this._parentFormGroup?.name||``)}__syncModelCheckedToChecked(e){this.checked=e}__syncCheckedToModel(e){this.modelValue={value:this.choiceValue,checked:e}}__syncCheckedToInputElement(){this._inputNode&&(this._inputNode.checked=this.checked)}_proxyInputEvent(){}_onModelValueChanged({modelValue:e},t){let n;t&&t.modelValue&&(n=t.modelValue),this.constructor.elementProperties.get(`modelValue`).hasChanged(e,n)&&super._onModelValueChanged({modelValue:e})}parser(){return this.modelValue}formatter(e){return e&&e.value!==void 0?e.value:e}clear(){this.checked=!1}_isEmpty(){return!this.checked}_syncValueUpwards(){}}),Yo=class extends Fo{static get validatorName(){return`FormElementsHaveNoError`}execute(e,t,n){return n?.node._anyFormElementHasFeedbackFor(`error`)}static async getMessage(){return``}},Xo=Br(e=>class extends Uo(Oo(Ro(Vr(Gr(e))))){static get properties(){return{submitted:{type:Boolean,reflect:!0},focused:{type:Boolean,reflect:!0},dirty:{type:Boolean,reflect:!0},touched:{type:Boolean,reflect:!0},prefilled:{type:Boolean,reflect:!0}}}get _inputNode(){return this}get modelValue(){return this._getFromAllFormElements(`modelValue`)}set modelValue(e){this.__isInitialModelValue?(this.__isInitialModelValue=!1,this.registrationComplete.then(()=>{this._setValueMapForAllFormElements(`modelValue`,e)})):this._setValueMapForAllFormElements(`modelValue`,e)}get serializedValue(){return this._getFromAllFormElements(`serializedValue`)}set serializedValue(e){this.__isInitialSerializedValue?(this.__isInitialSerializedValue=!1,this.registrationComplete.then(()=>{this._setValueMapForAllFormElements(`serializedValue`,e)})):this._setValueMapForAllFormElements(`serializedValue`,e)}get formattedValue(){return this._getFromAllFormElements(`formattedValue`)}set formattedValue(e){this._setValueMapForAllFormElements(`formattedValue`,e)}get prefilled(){return this._everyFormElementHas(`prefilled`)}constructor(){super(),this.value=``,this.disabled=!1,this.submitted=!1,this.dirty=!1,this.touched=!1,this.focused=!1,this.__addedSubValidators=!1,this.__isInitialModelValue=!0,this.__isInitialSerializedValue=!0,this._checkForOutsideClick=this._checkForOutsideClick.bind(this),this.addEventListener(`focusin`,this._syncFocused),this.addEventListener(`focusout`,this._onFocusOut),this.addEventListener(`dirty-changed`,this._syncDirty),this.addEventListener(`validate-performed`,this.__onChildValidatePerformed),this.defaultValidators=[new Yo],this.__descriptionElementsInParentChain=new Set,this.__pendingValues={modelValue:{},serializedValue:{}}}connectedCallback(){super.connectedCallback(),this.setAttribute(`role`,`group`),this.initComplete.then(()=>{this.__isInitialModelValue=!1,this.__isInitialSerializedValue=!1,this.__initInteractionStates()})}disconnectedCallback(){super.disconnectedCallback(),this.__hasActiveOutsideClickHandling&&=(document.removeEventListener(`click`,this._checkForOutsideClick),!1),this.__descriptionElementsInParentChain.clear()}__initInteractionStates(){this.formElements.forEach(e=>{typeof e.initInteractionState==`function`&&e.initInteractionState()})}_triggerInitialModelValueChangedEvent(){this.registrationComplete.then(()=>{this._dispatchInitialModelValueChangedEvent()})}updated(e){super.updated(e),e.has(`disabled`)&&(this.disabled?this.__requestChildrenToBeDisabled():this.__retractRequestChildrenToBeDisabled()),e.has(`focused`)&&this.focused===!0&&this.__setupOutsideClickHandling()}__setupOutsideClickHandling(){this.__hasActiveOutsideClickHandling||=(document.addEventListener(`click`,this._checkForOutsideClick),!0)}_checkForOutsideClick(e){this.contains(e.target)||(this.touched=!0)}__requestChildrenToBeDisabled(){this.formElements.forEach(e=>{e.makeRequestToBeDisabled&&e.makeRequestToBeDisabled()})}__retractRequestChildrenToBeDisabled(){this.formElements.forEach(e=>{e.retractRequestToBeDisabled&&e.retractRequestToBeDisabled()})}_inputGroupTemplate(){return v`
        <div class="input-group">
          <slot></slot>
        </div>
      `}submitGroup(){this.submitted=!0,this.formElements.forEach(e=>{typeof e.submitGroup==`function`?e.submitGroup():e.submitted=!0})}resetGroup(){this.formElements.forEach(e=>{typeof e.resetGroup==`function`?e.resetGroup():typeof e.reset==`function`&&e.reset()}),this.resetInteractionState()}clearGroup(){this.formElements.forEach(e=>{typeof e.clearGroup==`function`?e.clearGroup():typeof e.clear==`function`&&e.clear()}),this.resetInteractionState()}resetInteractionState(){this.submitted=!1,this.touched=!1,this.dirty=!1,this.formElements.forEach(e=>{typeof e.resetInteractionState==`function`&&e.resetInteractionState()})}_getFromAllFormElementsFilter(e,t){return!e.disabled}_getFromAllFormElements(e,t){let n={},r=t||this._getFromAllFormElementsFilter;return this.formElements._keys().forEach(t=>{let i=this.formElements[t];i instanceof Ho?n[t]=i.filter(t=>r(t,e)).map(t=>t[e]):r(i,e)&&(typeof i._getFromAllFormElements==`function`?n[t]=i._getFromAllFormElements(e):n[t]=i[e])}),n}_setValueForAllFormElements(e,t){this.formElements.forEach(n=>{n[e]=t})}_setValueMapForAllFormElements(e,t){t&&typeof t==`object`&&Object.keys(t).forEach(n=>{Array.isArray(this.formElements[n])&&this.formElements[n].forEach((r,i)=>{r[e]=t[n][i]}),this.formElements[n]?this.formElements[n][e]=t[n]:this.__pendingValues[e][n]=t[n]})}_anyFormElementHas(e){return Object.keys(this.formElements).some(t=>Array.isArray(this.formElements[t])?this.formElements[t].some(t=>!!t[e]):!!this.formElements[t][e])}_anyFormElementHasFeedbackFor(e){return Object.keys(this.formElements).some(t=>Array.isArray(this.formElements[t])?this.formElements[t].some(t=>!!(t.hasFeedbackFor&&t.hasFeedbackFor.includes(e))):!!(this.formElements[t].hasFeedbackFor&&this.formElements[t].hasFeedbackFor.includes(e)))}_everyFormElementHas(e){return Object.keys(this.formElements).every(t=>Array.isArray(this.formElements[t])?this.formElements[t].every(t=>!!t[e]):!!this.formElements[t][e])}__onChildValidatePerformed(e){e&&this.isRegisteredFormElement(e.target)&&this.validate()}_syncFocused(){this.focused=this._anyFormElementHas(`focused`)}_onFocusOut(e){let t=this.formElements[this.formElements.length-1];e.target===t&&(this.touched=!0),this.focused=!1}_syncDirty(){this.dirty=this._anyFormElementHas(`dirty`)}__storeAllDescriptionElementsInParentChain(){let e=this;for(;e;)To(e._getAriaDescriptionElements(),{reverse:!0}).forEach(e=>{e.getAttribute(`slot`)===`feedback`&&this.__descriptionElementsInParentChain.add(e)}),e=e._parentFormGroup}__linkParentMessages(e){this.__descriptionElementsInParentChain.forEach(t=>{typeof e.addToAriaDescribedBy==`function`&&e.addToAriaDescribedBy(t,{reorder:!1})})}__unlinkParentMessages(e){this.__descriptionElementsInParentChain.forEach(t=>{typeof e.removeFromAriaDescribedBy==`function`&&e.removeFromAriaDescribedBy(t)})}addFormElement(e,t){if(super.addFormElement(e,t),this.disabled&&e.makeRequestToBeDisabled(),this.__descriptionElementsInParentChain.size||this.__storeAllDescriptionElementsInParentChain(),this.__linkParentMessages(e),this.validate({clearCurrentResult:!0}),!e.modelValue){let t=this.__pendingValues;t.modelValue&&t.modelValue[e.name]?e.modelValue=t.modelValue[e.name]:t.serializedValue&&t.serializedValue[e.name]&&(e.serializedValue=t.serializedValue[e.name])}}get _initialModelValue(){return this._getFromAllFormElements(`_initialModelValue`)}removeFormElement(e){super.removeFormElement(e),this.validate({clearCurrentResult:!0}),typeof e.removeFromAriaLabelledBy==`function`&&this._labelNode&&e.removeFromAriaLabelledBy(this._labelNode,{reorder:!1}),this.__unlinkParentMessages(e)}_isEmpty(){return this.formElements.every(e=>e._isEmpty?.())}}),Zo=class extends Go(Vo){static get properties(){return{readOnly:{type:Boolean,attribute:`readonly`,reflect:!0},type:{type:String,reflect:!0},placeholder:{type:String,reflect:!0}}}get slots(){return{...super.slots,input:()=>{let e=document.createElement(`input`),t=this.getAttribute(`value`);return t&&e.setAttribute(`value`,t),e}}}get _inputNode(){return super._inputNode}constructor(){super(),this.readOnly=!1,this.type=`text`,this.placeholder=``}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`readOnly`&&this.__delegateReadOnly()}firstUpdated(e){super.firstUpdated(e),this.__delegateReadOnly()}updated(e){super.updated(e),e.has(`type`)&&(this._inputNode.type=this.type),e.has(`placeholder`)&&(this._inputNode.placeholder=this.placeholder),e.has(`disabled`)&&(this._inputNode.disabled=this.disabled,this.validate()),e.has(`name`)&&(this._inputNode.name=this.name),e.has(`autocomplete`)&&(this._inputNode.autocomplete=this.autocomplete)}__delegateReadOnly(){this._inputNode&&(this._inputNode.readOnly=this.readOnly)}},Qo=b`
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
`,$o=class extends Zo{constructor(...e){super(...e),this.size=`medium`,this.small=!1,this.center=!1}static get styles(){return[...super.styles,Ea,Qo]}connectedCallback(){super.connectedCallback(),this._inputNode&&this.maxlength&&this.maxlength>0&&(this._inputNode.maxLength=this.maxlength)}};g([T({type:Number,reflect:!0})],$o.prototype,`maxlength`,void 0),g([T({type:String,reflect:!0})],$o.prototype,`size`,void 0),g([T({reflect:!0,type:Boolean})],$o.prototype,`small`,void 0),g([T({reflect:!0,type:Boolean})],$o.prototype,`center`,void 0),customElements.get(`craft-input`)||customElements.define(`craft-input`,$o);var es=b`
  ${Ta}

  :host {
    display: block;
  }

  .input-color {
    display: grid;
    gap: var(--c-spacing-sm);
  }

  .input-color__control {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm);
  }

  .input-color__swatch {
    position: relative;
    display: block;
    flex: 0 0 auto;
    inline-size: var(--c-input-height, var(--c-size-control-md));
    block-size: var(--c-input-height, var(--c-size-control-md));
    border-radius: 50%;
    overflow: hidden;
    background:
      linear-gradient(
        45deg,
        var(--c-color-neutral-fill-quiet) 25%,
        transparent 25%
      ),
      linear-gradient(
        -45deg,
        var(--c-color-neutral-fill-quiet) 25%,
        transparent 25%
      ),
      linear-gradient(
        45deg,
        transparent 75%,
        var(--c-color-neutral-fill-quiet) 75%
      ),
      linear-gradient(
        -45deg,
        transparent 75%,
        var(--c-color-neutral-fill-quiet) 75%
      );
    background-position:
      0 0,
      0 0.375rem,
      0.375rem -0.375rem,
      -0.375rem 0;
    background-size: 0.75rem 0.75rem;
  }

  :host(:not([disabled])) .input-color__swatch {
    cursor: pointer;
  }

  .input-color__swatch:focus-within {
    box-shadow: var(
      --focus-ring,
      0 0 0 2px var(--c-color-accent-border-normal)
    );
  }

  .input-color__preview {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    box-shadow: inset 0 0 0 1px rgb(0 0 0 / 15%);
  }

  .input-color__picker {
    position: absolute;
    inset: 0;
    inline-size: 100%;
    block-size: 100%;
    border: 0;
    margin: 0;
    padding: 0;
    opacity: 0;
  }

  .input-group__container {
    ${wa}
    flex: 0 0 7.25rem;
    inline-size: 7.25rem;
    max-inline-size: 100%;
  }

  .input-group__input {
    display: flex;
    flex: 1 1 auto;
  }

  .input-group__prefix {
    color: var(--c-text-quiet);
    user-select: none;
    font-family: var(--c-font-mono);
    padding-inline: var(--c-input-spacing-inline) 0;
    display: grid;
    place-items: center;
  }

  ::slotted([slot='input']) {
    width: 100%;
    min-inline-size: 0;
    font: inherit;
    font-family: var(--c-font-mono);
    padding-block: 0;
    padding-inline: var(--c-spacing-xs) var(--c-input-spacing-inline);
    border: 0;
    appearance: none;
    background-color: transparent;
  }
`,ts=/^[0-9a-f]{6}$/i,ns=/^[0-9a-f]{3}$/i,rs={fromAttribute(e){if(!e)return[];try{let t=JSON.parse(e);return Array.isArray(t)?t.map(e=>String(e)):[]}catch{return[]}}};function is(e){return String(e??``).trim().replace(/^#/,``)}function as(e){let t=is(e);return ns.test(t)?t.split(``).map(e=>e+e).join(``):ts.test(t)?t:null}var os=class e extends Zo{static get styles(){return[...super.styles,Ea,es]}constructor(){super(),this.presets=[],this._pickerListId=`${this._inputId}-presets`,this.type=`text`}static doesBrowserSupportColorInputs(){if(e._browserSupportsColorInputs===null){let t=document.createElement(`input`);t.setAttribute(`type`,`color`),e._browserSupportsColorInputs=t.type===`color`}return e._browserSupportsColorInputs}get slots(){return{...super.slots,input:()=>{let e=document.createElement(`input`),t=this.getAttribute(`value`);return e.type=`text`,e.inputMode=`text`,e.spellcheck=!1,e.setAttribute(`autocorrect`,`off`),e.setAttribute(`autocapitalize`,`off`),t&&e.setAttribute(`value`,is(t)),e}}}parser(e){return is(e)}formatter(e){return is(e)}serializer(e){return is(e)}deserializer(e){return is(e)}preprocessor(e){let t=is(e);if(t!==e)return t}get _expandedHexValue(){return as(this.modelValue)}get _pickerValue(){return this._expandedHexValue?`#${this._expandedHexValue}`:`#ffffff`}get _validPresets(){return this.presets.map(e=>as(e)).filter(e=>e!==null).map(e=>`#${e}`)}_handlePickerInput(e){let t=e.target,n=is(t.value),r=this._isHandlingUserInput;this._isHandlingUserInput=!0,this.modelValue=n,this.value=n,this._isHandlingUserInput=r}_pickerTemplate(){if(!e.doesBrowserSupportColorInputs())return C;let t=this._validPresets;return v`
      <input
        class="input-color__picker"
        type="color"
        aria-controls="${this._inputId}"
        aria-label="${n(`Color picker`)}"
        .value="${this._pickerValue}"
        ?disabled="${this.disabled}"
        list=${t.length?this._pickerListId:C}
        @input="${this._handlePickerInput}"
      />
      ${t.length?v`
            <datalist id="${this._pickerListId}">
              ${t.map(e=>v`<option value="${e}"></option>`)}
            </datalist>
          `:C}
    `}_swatchTemplate(){return v`
      <label class="input-color__swatch">
        <span class="input-color__preview" style="${this._expandedHexValue?`background-color: #${this._expandedHexValue}`:``}"></span>
        ${this._pickerTemplate()}
      </label>
    `}_inputGroupTemplate(){return v`
      <div class="input-color">
        ${this._inputGroupBeforeTemplate()}
        <div class="input-color__control">
          ${this._swatchTemplate()}
          <div class="input-group__container">
            <div class="input-group__prefix" aria-hidden="true">#</div>
            ${this._inputGroupInputTemplate()}
            ${this._inputGroupSuffixTemplate()}
          </div>
        </div>
        ${this._inputGroupAfterTemplate()}
      </div>
    `}};os._browserSupportsColorInputs=null,g([T({converter:rs})],os.prototype,`presets`,void 0),customElements.get(`craft-input-color`)||customElements.define(`craft-input-color`,os);var ss=e=>e??C,cs=class extends Fo{static validatorName=`IsAcceptedFile`;static checkFileSize(e,t){return e<=t}static getExtension(e){return e?.slice(e.lastIndexOf(`.`))}static isExtensionAllowed(e,t){return t?.find(t=>t.toUpperCase()===e.toUpperCase())}static isFileTypeAllowed(e,t){return t?.find(t=>t.toUpperCase()===e.toUpperCase())}execute(e,t=this.param){let n,r,i=this.constructor,{allowedFileTypes:a,allowedFileExtensions:o,maxFileSize:s}=t;return a?.length?(n=e.some(e=>!i.isFileTypeAllowed(e.type,a)),n):o?.length?(r=e.some(e=>!i.isExtensionAllowed(i.getExtension(e.name),o)),r):e.findIndex(e=>!i.checkFileSize(e.size,s))>-1}static async getMessage(){return``}},ls=class extends Fo{static validatorName=`DuplicateFileNames`;constructor(e,t){super(e,t),this.type=`info`}execute(e,t=this.param){return t.show}static async getMessage(){return ro().msg(`lion-input-file:uploadTextDuplicateFileName`)}},us=524288e3,ds={type:`FILE_TYPE`,size:`FILE_SIZE`},fs={fail:`FAIL`,pass:`SUCCESS`},ps=class{constructor(e,t){this.failedProp=[],this.systemFile=e,this._acceptCriteria=t,this.uploadFileStatus(),this.failedProp.length===0&&this.createDownloadUrl(e)}_getFileNameExtension(e){return e.slice(e.lastIndexOf(`.`))}uploadFileStatus(){if(this._acceptCriteria.allowedFileExtensions.length){let e=this._getFileNameExtension(this.systemFile.name);cs.isExtensionAllowed(e,this._acceptCriteria.allowedFileExtensions)||(this.status=fs.fail,this.failedProp.push(ds.type))}else if(this._acceptCriteria.allowedFileTypes.length){let e=this.systemFile.type;cs.isFileTypeAllowed(e,this._acceptCriteria.allowedFileTypes)||(this.status=fs.fail,this.failedProp.push(ds.type))}cs.checkFileSize(this.systemFile.size,this._acceptCriteria.maxFileSize)?this.status!==fs.fail&&(this.status=fs.pass):(this.status=fs.fail,this.failedProp.push(ds.size))}createDownloadUrl(e){this.downloadUrl=window.URL.createObjectURL(e)}},ms=(e,t,n)=>{let r=new Map;for(let i=t;i<=n;i++)r.set(e[i],i);return r},hs=r(class extends t{constructor(e){if(super(e),e.type!==a.CHILD)throw Error(`repeat() can only be used in text expressions`)}dt(e,t,n){let r;n===void 0?n=t:t!==void 0&&(r=t);let i=[],a=[],o=0;for(let t of e)i[o]=r?r(t,o):o,a[o]=n(t,o),o++;return{values:a,keys:i}}render(e,t,n){return this.dt(e,t,n).values}update(e,[t,n,r]){let i=ht(e),{values:a,keys:o}=this.dt(t,n,r);if(!Array.isArray(i))return this.ut=o,a;let s=this.ut??=[],c=[],l,u,d=0,f=i.length-1,p=0,m=a.length-1;for(;d<=f&&p<=m;)if(i[d]===null)d++;else if(i[f]===null)f--;else if(s[d]===o[p])c[p]=ft(i[d],a[p]),d++,p++;else if(s[f]===o[m])c[m]=ft(i[f],a[m]),f--,m--;else if(s[d]===o[m])c[m]=ft(i[d],a[m]),dt(e,c[m+1],i[d]),d++,m--;else if(s[f]===o[p])c[p]=ft(i[f],a[p]),dt(e,i[d],i[f]),f--,p++;else if(l===void 0&&(l=ms(o,p,m),u=ms(s,d,f)),l.has(s[d]))if(l.has(s[f])){let t=u.get(o[p]),n=t===void 0?null:i[t];if(n===null){let t=dt(e,i[d]);ft(t,a[p]),c[p]=t}else c[p]=ft(n,a[p]),dt(e,i[d],n),i[t]=null;p++}else gt(i[f]),f--;else gt(i[d]),d++;for(;p<=m;){let t=dt(e,c[m+1]);ft(t,a[p]),c[p++]=t}for(;d<=f;){let e=i[d++];e!==null&&gt(e)}return this.ut=o,mt(e,c),x}}),gs=e=>{switch(e){case`bg-BG`:return k(()=>import(`./bg-BG-BsPLW1O9.js`),__vite__mapDeps([34,35]),import.meta.url);case`bg`:return k(()=>import(`./bg-CM8RZOwv.js`),[],import.meta.url);case`cs-CZ`:return k(()=>import(`./cs-CZ-MlqpnCaw.js`),__vite__mapDeps([36,37]),import.meta.url);case`cs`:return k(()=>import(`./cs-CQUHD0xt.js`),[],import.meta.url);case`de-DE`:return k(()=>import(`./de-DE-DQp4Xmef.js`),__vite__mapDeps([38,39]),import.meta.url);case`de`:return k(()=>import(`./de-Cu7ADEFF.js`),[],import.meta.url);case`en-AU`:return k(()=>import(`./en-AU-DCRQjXcp.js`),__vite__mapDeps([40,41]),import.meta.url);case`en-GB`:return k(()=>import(`./en-GB-BRKc3BKy.js`),__vite__mapDeps([42,41]),import.meta.url);case`en-US`:return k(()=>import(`./en-US-DUAtPGkW.js`),__vite__mapDeps([43,41]),import.meta.url);case`en-PH`:case`en`:return k(()=>import(`./en-BGmLPvL6.js`),[],import.meta.url);case`es-ES`:return k(()=>import(`./es-ES-E0fH-9yE.js`),__vite__mapDeps([44,45]),import.meta.url);case`es`:return k(()=>import(`./es-CKo5g7uN.js`),[],import.meta.url);case`fr-FR`:return k(()=>import(`./fr-FR-C1CdXYYE.js`),__vite__mapDeps([46,47]),import.meta.url);case`fr-BE`:return k(()=>import(`./fr-BE-BAVunqTE.js`),__vite__mapDeps([48,47]),import.meta.url);case`fr`:return k(()=>import(`./fr-CRxVQ9kP.js`),[],import.meta.url);case`hu-HU`:return k(()=>import(`./hu-HU-Bo4UgLra.js`),__vite__mapDeps([49,50]),import.meta.url);case`hu`:return k(()=>import(`./hu-INHSmlNT.js`),[],import.meta.url);case`id-ID`:return k(()=>import(`./id-ID-B20vUlBp.js`),__vite__mapDeps([51,52]),import.meta.url);case`id`:return k(()=>import(`./id-CzI_kQue.js`),[],import.meta.url);case`it-IT`:return k(()=>import(`./it-IT-BXZcSQix.js`),__vite__mapDeps([53,54]),import.meta.url);case`it`:return k(()=>import(`./it-BIZrUvhL.js`),[],import.meta.url);case`nl-BE`:return k(()=>import(`./nl-BE-nYDTTvkz.js`),__vite__mapDeps([55,56]),import.meta.url);case`nl-NL`:return k(()=>import(`./nl-NL-B6zhjyes.js`),__vite__mapDeps([57,56]),import.meta.url);case`nl`:return k(()=>import(`./nl-DK7LMiyy.js`),[],import.meta.url);case`pl-PL`:return k(()=>import(`./pl-PL-n2UAPw-E.js`),__vite__mapDeps([58,59]),import.meta.url);case`pl`:return k(()=>import(`./pl-M6DBBIZW.js`),[],import.meta.url);case`ro-RO`:return k(()=>import(`./ro-RO-DXX2qg0Y.js`),__vite__mapDeps([60,61]),import.meta.url);case`ro`:return k(()=>import(`./ro-z1glPc5q.js`),[],import.meta.url);case`ru-RU`:return k(()=>import(`./ru-RU-p-TAxJZZ.js`),__vite__mapDeps([62,63]),import.meta.url);case`ru`:return k(()=>import(`./ru-eLTsJp1p.js`),[],import.meta.url);case`sk-SK`:return k(()=>import(`./sk-SK-D3Ih9zlz.js`),__vite__mapDeps([64,65]),import.meta.url);case`sk`:return k(()=>import(`./sk-BfoWgHvN.js`),[],import.meta.url);case`uk-UA`:return k(()=>import(`./uk-UA-N3c9Eb1b.js`),__vite__mapDeps([66,67]),import.meta.url);case`uk`:return k(()=>import(`./uk-DrK5PsW-.js`),[],import.meta.url);case`zh-CN`:case`zh`:return k(()=>import(`./zh-DABKu_Ne.js`),[],import.meta.url);default:return k(()=>import(`./en-BGmLPvL6.js`),[],import.meta.url)}},_s=class extends _o(Co(w)){static get scopedElements(){return{...super.scopedElements,"lion-validation-feedback":Po}}static get properties(){return{fileList:{type:Array},multiple:{type:Boolean}}}static localizeNamespaces=[{"lion-input-file":gs},...super.localizeNamespaces];constructor(){super(),this.fileList=[],this.multiple=!1}updated(e){super.updated(e),e.has(`fileList`)&&this._enhanceLightDomA11y()}_enhanceLightDomA11y(){let e=this.shadowRoot?.querySelectorAll(`[id^="file-feedback"]`),t=this.parentNode?.parentNode;e?.forEach(e=>{t?.addEventListener(`focusin`,()=>{e.setAttribute(`aria-live`,`polite`)}),t?.addEventListener(`focusout`,()=>{e.setAttribute(`aria-live`,`assertive`)})})}_removeFile(e){this.dispatchEvent(new CustomEvent(`file-remove-requested`,{detail:{removedFile:e,status:e.status,uploadResponse:e.response}}))}_validationFeedbackTemplate(e,t){return v`
      <lion-validation-feedback
        id="file-feedback-${t}"
        .feedbackData="${e}"
        aria-live="assertive"
      ></lion-validation-feedback>
    `}_listItemBeforeTemplate(e){return C}_listItemAfterTemplate(e,t){return v`
      <button
        class="selected__list__item__remove-button"
        aria-label="${this.msgLit(`lion-input-file:removeButtonLabel`,{fileName:e.systemFile.name})}"
        @click=${()=>this._removeFile(e)}
      >
        ${this._removeButtonContentTemplate()}
      </button>
    `}_removeButtonContentTemplate(){return v`✖️`}_selectedListItemTemplate(e){let t=Jr();return v`
      <div class="selected__list__item" status="${e.status?e.status.toLowerCase():``}">
        <div class="selected__list__item__label">
          ${this._listItemBeforeTemplate(e)}
          <span id="selected-list-item-label-${t}" class="selected__list__item__label__text">
            <span class="sr-only">${this.msgLit(`lion-input-file:fileNameDescriptionLabel`)}</span>
            ${e.downloadUrl&&e.status!==`LOADING`?v`
                  <a
                    class="selected__list__item__label__link"
                    href="${e.downloadUrl}"
                    target="${e.downloadUrl.startsWith(`blob`)?`_blank`:``}"
                    rel="${ss(e.downloadUrl.startsWith(`blob`)?`noopener noreferrer`:void 0)}"
                    >${e.systemFile?.name}</a
                  >
                `:e.systemFile?.name}
          </span>
          ${this._listItemAfterTemplate(e,t)}
        </div>
        ${e.status===`FAIL`&&e.validationFeedback?v`
              ${hs(e.validationFeedback,e=>v`
                  ${this._validationFeedbackTemplate([e],t)}
                `)}
            `:C}
      </div>
    `}render(){return this.fileList?.length?v`
          ${this.multiple?v`
                <ul class="selected__list">
                  ${this.fileList.map(e=>v` <li>${this._selectedListItemTemplate(e)}</li> `)}
                </ul>
              `:v` ${this._selectedListItemTemplate(this.fileList[0])} `}
        `:C}static get styles(){return[b`
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
      `]}};function vs(e,t=2){if(!+e)return`0 Bytes`;let n=1024,r=t<0?0:t,i=[` bytes`,`KB`,`MB`,`GB`,`TB`,`PB`,`EB`,`ZB`,`YB`],a=Math.floor(Math.log(e)/Math.log(n));return`${parseFloat((e/n**a).toFixed(r))}${i[a]}`}var ys=class extends Co(_o(Vo)){static get scopedElements(){return{...super.scopedElements,"lion-selected-file-list":_s}}static get properties(){return{accept:{type:String},multiple:{type:Boolean,reflect:!0},buttonLabel:{type:String,attribute:`button-label`},maxFileSize:{type:Number,attribute:`max-file-size`},enableDropZone:{type:Boolean,attribute:`enable-drop-zone`},uploadOnSelect:{type:Boolean,attribute:`upload-on-select`},isDragging:{type:Boolean,attribute:`is-dragging`,reflect:!0},uploadResponse:{type:Array,state:!1},_selectedFilesMetaData:{type:Array,state:!0}}}static localizeNamespaces=[{"lion-input-file":gs},...super.localizeNamespaces];static get validationTypes(){return[`error`,`info`]}get slots(){return{...super.slots,input:()=>v`<input .value="${ss(this.getAttribute(`value`))}" />`,"file-select-button":()=>v`<button
          type="button"
          id="select-button-${this._inputId}"
          @click="${this.__openDialogOnBtnClick}"
        >
          ${this.buttonLabel}
        </button>`,after:()=>v`<div data-description></div>`,"selected-file-list":()=>({template:v`
          <lion-selected-file-list
            .fileList=${this._selectedFilesMetaData}
            .multiple=${this.multiple}
          ></lion-selected-file-list>
        `,renderAsDirectHostChild:!0})}}get _inputNode(){return super._inputNode}get _buttonNode(){return this.querySelector(`#select-button-${this._inputId}`)}get buttonLabel(){return this.__buttonLabel||this._buttonNode?.textContent?.trim()||``}set buttonLabel(e){let t=this.buttonLabel;this.__buttonLabel=e,this.requestUpdate(`buttonLabel`,t)}get _focusableNode(){return this._buttonNode}get _isDragAndDropSupported(){return`draggable`in document.createElement(`div`)}constructor(){super(),this.type=`file`,this._selectedFilesMetaData=[],this.uploadResponse=[],this.__initialUploadResponse=this.uploadResponse,this.uploadOnSelect=!1,this.multiple=!1,this.enableDropZone=!1,this.maxFileSize=us,this.accept=``,this.buttonLabel=``,this._initialButtonLabel=``,this.modelValue=[],this._onRemoveFile=this._onRemoveFile.bind(this),this.__duplicateFileNamesValidator=new ls({show:!1}),this.__previouslyParsedFiles=null}get _fileListNode(){return Array.from(this.children).find(e=>e.slot===`selected-file-list`)}connectedCallback(){super.connectedCallback(),this.__initialUploadResponse=this.uploadResponse,this._initialButtonLabel=this.buttonLabel,this._inputNode.addEventListener(`change`,this._onChange),this._inputNode.addEventListener(`click`,this._onClick)}disconnectedCallback(){super.disconnectedCallback(),this._inputNode.removeEventListener(`change`,this._onChange),this._inputNode.removeEventListener(`click`,this._onClick)}onLocaleUpdated(){super.onLocaleUpdated(),this.multiple?this.buttonLabel=this._initialButtonLabel||this.msgLit(`lion-input-file:selectTextMultipleFile`):this.buttonLabel=this._initialButtonLabel||this.msgLit(`lion-input-file:selectTextSingleFile`)}get operationMode(){return`upload`}get _acceptCriteria(){let e=[],t=[];if(this.accept){let n=this.accept.replace(/\s+/g,``).split(`,`);e=n.filter(e=>e.includes(`/`)),t=n.filter(e=>!e.includes(`/`))}return{allowedFileTypes:e,allowedFileExtensions:t,maxFileSize:this.maxFileSize}}reset(){super.reset(),this._selectedFilesMetaData=[],this.uploadResponse=this.__initialUploadResponse,this.modelValue=[],this.dirty=!1}clear(){this._selectedFilesMetaData=[],this.uploadResponse=[],this.modelValue=[]}_showFeedbackConditionFor(e,t){return super._showFeedbackConditionFor(e,t)&&!(this.validationStates.error?.FileTypeAllowed||this.validationStates.error?.FileSizeAllowed)}parser(){if(this.__previouslyParsedFiles===this._inputNode.files)return this.modelValue;this.__previouslyParsedFiles=this._inputNode.files;let e=this._inputNode.files?Array.from(this._inputNode.files):[];return this.multiple?[...this.modelValue??[],...e]:e}formatter(e){return this._inputNode?.value||``}__setupDragDropEventListeners(){let e=this.shadowRoot?.querySelector(`.input-file__drop-zone`);[`dragenter`,`dragover`,`dragleave`].forEach(t=>{e?.addEventListener(t,e=>{e.preventDefault(),e.stopPropagation(),this.isDragging=t!==`dragleave`},!1)}),window.addEventListener(`drop`,e=>{e.target===this._inputNode&&e.preventDefault(),this.isDragging=!1},!1)}firstUpdated(e){super.firstUpdated(e),this.__setupFileValidators(),this._inputNode&&(this._inputNode.type=this.type,this._inputNode.setAttribute(`tabindex`,`-1`),this._inputNode.multiple=this.multiple,this.accept.length&&(this._inputNode.accept=this.accept)),this.enableDropZone&&this._isDragAndDropSupported&&(this.__setupDragDropEventListeners(),this.setAttribute(`drop-zone`,``)),this._fileListNode.addEventListener(`file-remove-requested`,this._onRemoveFile)}updated(e){super.updated(e),e.has(`disabled`)&&(this._inputNode.disabled=this.disabled,this.validate()),e.has(`buttonLabel`)&&this._buttonNode&&(this._buttonNode.textContent=this.buttonLabel),e.has(`name`)&&(this._inputNode.name=this.name),e.has(`_ariaLabelledNodes`)&&this.__syncAriaLabelledByAttributesToButton(),e.has(`_ariaDescribedNodes`)&&this.__syncAriaDescribedByAttributesToButton(),e.has(`uploadResponse`)&&(this._selectedFilesMetaData.length===0&&this.uploadResponse.forEach(e=>{let t={systemFile:{name:e.name},response:e,status:e.status,validationFeedback:[{message:e.errorMessage}]};this._selectedFilesMetaData=[...this._selectedFilesMetaData,t]}),this._selectedFilesMetaData.forEach(e=>{!this.uploadResponse.some(t=>t.name===e.systemFile.name)&&this.uploadOnSelect?this.__removeFileFromList(e):(this.uploadResponse.forEach(t=>{t.name===e.systemFile.name&&(e.response=t,e.downloadUrl=t.downloadUrl?t.downloadUrl:e.downloadUrl,e.status=t.status,e.validationFeedback=[{type:typeof t.errorMessage==`string`&&t.errorMessage?.length>0?`error`:`success`,message:t.errorMessage??``}])}),this._selectedFilesMetaData=[...this._selectedFilesMetaData])}),this._updateUploadButtonDescription())}__computeNewAddedFiles(e){let t=e.filter(e=>this._selectedFilesMetaData.findIndex(t=>t.systemFile.name===e.name)===-1);return this.__duplicateFileNamesValidator.param={show:e.length!==t.length},this.validate(),t}_processDroppedFiles(e){if(e.preventDefault(),this.isDragging=!1,!(e.dataTransfer&&e.dataTransfer.items.length>1&&!this.multiple||!e.dataTransfer?.files)){if(this._inputNode.files=e.dataTransfer.files,this.multiple){let t=this.__computeNewAddedFiles(Array.from(e.dataTransfer.files));this.modelValue=[...this.modelValue??[],...t]}else this.modelValue=Array.from(e.dataTransfer.files);this._processFiles(Array.from(e.dataTransfer.files))}}_onChange(e){this.touched=!0,this._onUserInputChanged(),this._processFiles(e?.target?.files)}_onClick(e){e.target.value=``}__syncAriaLabelledByAttributesToButton(){if(this._inputNode.hasAttribute(`aria-labelledby`)){let e=this._inputNode.getAttribute(`aria-labelledby`);this._buttonNode?.setAttribute(`aria-labelledby`,`select-button-${this._inputId} ${e}`)}}__syncAriaDescribedByAttributesToButton(){if(this._inputNode.hasAttribute(`aria-describedby`)){let e=this._inputNode.getAttribute(`aria-describedby`)||``;this._buttonNode?.setAttribute(`aria-describedby`,e)}}__setupFileValidators(){this.defaultValidators=[new cs(this._acceptCriteria),this.__duplicateFileNamesValidator]}_processFiles(e){let t=this.__computeNewAddedFiles(Array.from(e));!this.multiple&&t.length>0&&(this._selectedFilesMetaData=[],this.uploadResponse=[]);let n;for(let e of t.values())n=new ps(e,this._acceptCriteria),n.failedProp?.length?(this._handleErroredFiles(n),this.uploadResponse=[...this.uploadResponse,{name:n.systemFile.name,status:`FAIL`,errorMessage:n.validationFeedback[0].message}]):this.uploadResponse=[...this.uploadResponse,{name:n.systemFile.name,status:`SUCCESS`}],this._selectedFilesMetaData=[...this._selectedFilesMetaData,n],this._handleErrors();let r=this._selectedFilesMetaData.filter(({systemFile:e,status:n})=>t.includes(e)&&n===`SUCCESS`).map(({systemFile:e})=>e);r.length>0&&this._dispatchFileListChangeEvent(r)}_dispatchFileListChangeEvent(e){this.dispatchEvent(new CustomEvent(`file-list-changed`,{detail:{newFiles:e}}))}_handleErrors(){let e=!1;if(this._selectedFilesMetaData.forEach(t=>{t.failedProp&&t.failedProp.length>0&&(e=!0)}),e)this.hasFeedbackFor?.push(`error`),this.shouldShowFeedbackFor.push(`error`);else if(this._prevHasErrors&&this.hasFeedbackFor.includes(`error`)){let e=this.hasFeedbackFor.indexOf(`error`);this.hasFeedbackFor.slice(e,e+1);let t=this.shouldShowFeedbackFor.indexOf(`error`);this.shouldShowFeedbackFor.slice(t,t+1)}this._prevHasErrors=e}_handleErroredFiles(e){e.validationFeedback=[];let{allowedFileExtensions:t,allowedFileTypes:n}=this._acceptCriteria,r=[],i=0,a;t.length?(r=t,a=r.pop(),i=r.length):n.length&&(n.forEach(e=>{if(e.endsWith(`/*`))r.push(e.slice(0,-2));else if(e===`text/plain`)r.push(`text`);else{let t=e.indexOf(`/`),n=e.slice(t+1);if(!n.includes(`+`))r.push(`.${n}`);else{let e=n.split(`+`);r.push(`.${e[0]}`)}}}),a=r.pop(),i=r.length);let o=``;o=a?i?`${this.msgLit(`lion-input-file:allowedFileValidatorComplex`,{allowedTypesArray:r.join(`, `),allowedTypesLastItem:a,maxSize:vs(this.maxFileSize)})}`:`${this.msgLit(`lion-input-file:allowedFileValidatorSimple`,{allowedType:a,maxSize:vs(this.maxFileSize)})}`:`${this.msgLit(`lion-input-file:allowedFileSize`,{maxSize:vs(this.maxFileSize)})}`;let s={message:o,type:`error`};e.validationFeedback?.push(s)}_updateUploadButtonDescription(){let e=[],t;this._selectedFilesMetaData.forEach(n=>{n.status===`FAIL`&&(t=n.validationFeedback?n.validationFeedback[0].message.toString():``,e.push(n.systemFile.name))});let n=this.querySelector(`[slot="after"]`);if(n)if(!this._selectedFilesMetaData||this._selectedFilesMetaData.length===0)this.uploadOnSelect?n.textContent=this.msgLit(`lion-input-file:noFilesUploaded`):n.textContent=this.msgLit(`lion-input-file:noFilesSelected`);else if(this._selectedFilesMetaData.length===1){let{name:e}=this._selectedFilesMetaData[0].systemFile;this.uploadOnSelect?n.textContent=t||this.msgLit(`lion-input-file:fileUploaded`)+(e??``):n.textContent=t||this.msgLit(`lion-input-file:fileSelected`)+(e??``)}else this.uploadOnSelect?n.textContent=`${this.msgLit(`lion-input-file:filesUploaded`,{numberOfFiles:this._selectedFilesMetaData.length})} ${t?this.msgLit(`lion-input-file:generalValidatorMessage`,{validatorMessage:t,listOfErroneousFiles:e.join(`, `)}):``}`:n.textContent=`${this.msgLit(`lion-input-file:filesSelected`,{numberOfFiles:this._selectedFilesMetaData.length})} ${t?this.msgLit(`lion-input-file:generalValidatorMessage`,{validatorMessage:t,listOfErroneousFiles:e.join(`, `)}):``}`}__removeFileFromList(e){this._selectedFilesMetaData=this._selectedFilesMetaData.filter(t=>t.systemFile.name!==e.systemFile.name),this.modelValue&&=this.modelValue.filter(t=>t.name!==e.systemFile.name),this._inputNode.value=``,this._handleErrors(),this._updateUploadButtonDescription()}_onRemoveFile(e){if(this.disabled)return;let{removedFile:t}=e.detail;!this.uploadOnSelect&&t&&this.__removeFileFromList(t),this._removeFile(t)}_removeFile(e){this.dispatchEvent(new CustomEvent(`file-removed`,{detail:{removedFile:e,status:e.status,uploadResponse:e.response}}))}_reflectBackOn(){return!1}_isEmpty(){return this.modelValue?.length===0}_dropZoneTemplate(){return v`
      <div @drop="${this._processDroppedFiles}" class="input-file__drop-zone">
        <div class="input-file__drop-zone__text">
          ${this.msgLit(`lion-input-file:dragAndDropText`)}
        </div>
        <slot name="file-select-button"></slot>
      </div>
    `}_inputGroupAfterTemplate(){return v` <slot name="selected-file-list"></slot> `}_inputGroupInputTemplate(){return v`
      <slot name="input"> </slot>
      <slot name="after"> </slot>
      ${this.enableDropZone&&this._isDragAndDropSupported?this._dropZoneTemplate():v`
            <div class="input-group__file-select-button">
              <slot name="file-select-button"></slot>
            </div>
          `}
    `}static get styles(){return[super.styles,b`
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
      `]}__openDialogOnBtnClick(e){e.preventDefault(),e.stopPropagation(),this._inputNode.click()}},bs=class extends _s{static get styles(){return[...super.styles,b`
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
      `]}_listItemAfterTemplate(e,t){return v`
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
    `}_removeButtonContentTemplate(){return v`<craft-icon name="x"></craft-icon>`}_listItemBeforeTemplate(e){return v`<img src="${e.downloadUrl}" alt="" class="preview-thumb" />`}},xs=b`
  /* Add any craft-specific styles for input-file here */
  ::slotted([slot='selected-file-list']) {
    margin-block-start: var(--c-spacing-lg);
  }
`,Ss=class extends ys{static get styles(){return[...super.styles,Ea,xs]}get slots(){return{...super.slots,"file-select-button":()=>v`<craft-button
          type="button"
          id="select-button-${this._inputId}"
          @click="${this.__openDialogOnBtnClick}"
        >
          ${this.buttonLabel}
        </craft-button>`}}static get scopedElements(){return{...super.scopedElements,"lion-selected-file-list":bs}}};customElements.get(`craft-input-file`)||customElements.define(`craft-input-file`,Ss);var Cs=class extends Zo{static get styles(){return[...super.styles,Ea,b`
        .input-group__container {
          position: relative;
        }

        .input-group__suffix {
          position: absolute;
          inset-inline-end: var(--c-input-spacing-inline);
          inset-block-start: 50%;
          transform: translateY(calc(-50%));
        }
      `]}constructor(){super(),this._visible=!1,this.reveal=()=>{this._visible=!this._visible,this.type=this._visible?`text`:`password`},this.renderSuffix=()=>v`
      <craft-button
        type="button"
        icon
        size="small"
        variant="plain"
        @click="${this.reveal}"
        appearance="plain"
      >
        <span class="icon"
          >${this._visible?v`<craft-icon
                name="eye-slash"
                label="${n(`Hide`)}"
              ></craft-icon>`:v`<craft-icon name="eye" label="${n(`Show`)}"></craft-icon>`}
        </span>
      </craft-button>
    `,this.type=`password`}get slots(){return{...super.slots,suffix:()=>({template:this.renderSuffix()})}}};g([E()],Cs.prototype,`_visible`,void 0),customElements.get(`craft-input-password`)||customElements.define(`craft-input-password`,Cs);var ws=b`
  :host {
    display: contents;
  }

  .cp-chip {
    --_min-height: var(--c-chip-height, var(--c-size-control-sm));
    display: inline-flex;
    min-width: auto;
    border-radius: var(--c-chip-radius, var(--c-radius-md));
    padding-inline: var(--c-chip-spacing-inline, 0);
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

  .cp-chip__body ::slotted(a) {
    text-decoration: none;
    font-weight: bold;
    display: flex;
  }

  .cp-chip[appearance='plain'],
  .cp-chip--plain {
    padding-block: 0;
    padding-inline: 0;
    border-color: transparent;
    background-color: transparent;
    box-shadow: none;
  }

  .cp-chip[size='small'],
  .cp-chip--small {
    --_min-height: var(--c-size-control-sm);
    padding-block: calc(var(--c-spacing-xs) / 2);
  }

  .cp-chip[size='medium'],
  .cp-chip--medium {
    padding-block: 0;
    min-height: var(--c-size-control-md);
  }

  .cp-chip__prefix,
  .cp-chip__body,
  .cp-chip__suffix {
    display: inline-flex;
    flex-direction: column;
    min-height: var(--_min-height);
  }

  .cp-chip__body {
    flex: 1 1 auto;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .cp-chip__prefix {
    padding-inline: calc(var(--c-spacing-md) / 2);
  }

  .cp-chip__suffix {
    padding-inline-start: var(--c-spacing-md);
  }
`,Ts=class extends w{constructor(...e){super(...e),this.size=``,this.variant=``,this.icon=null}renderPrefix(){return v`<div class="cp-chip__prefix" part="prefix">
      <slot name="prefix">
        <slot name="icon">
          ${this.icon?v`<craft-icon name="${this.icon}"></craft-icon>`:C}
        </slot>
      </slot>
    </div>`}render(){let e=!!this.querySelector(`[slot="prefix"]`)||!!this.querySelector(`[slot="icon"]`)||this.icon,t=!!this.querySelector(`[slot="suffix"]`);return v`
      <div
        part="chip"
        class="${o({"cp-chip":!0,"cp-chip--small":this.size===`small`,"cp-chip--medium":this.size===`medium`,"cp-chip--large":this.size===`large`,"cp-chip--plain":this.variant===`plain`})}"
      >
        ${e?this.renderPrefix():C}
        <div class="cp-chip__body">
          <slot></slot>
        </div>
        ${t?v`<div class="cp-chip__suffix" part="suffix">
              <slot name="suffix"></slot>
            </div>`:C}
      </div>
    `}};Ts.styles=[ws],g([T()],Ts.prototype,`size`,void 0),g([T()],Ts.prototype,`variant`,void 0),g([T()],Ts.prototype,`icon`,void 0),customElements.get(`craft-chip`)||customElements.define(`craft-chip`,Ts);var Es=b`
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
`,Ds=class extends w{constructor(...e){super(...e),this.label=null,this.status=null}getLabel(){return!this.label&&this.status?`Status: ${this.status}`:this.label}render(){return v`
      <span
        class="${o({status:!0,"status--live":this.status===`live`,"status--enabled":this.status===`enabled`,"status--pending":this.status===`pending`,"status--expired":this.status===`expired`,"status--disabled":this.status===`disabled`})}"
        role="img"
        aria-label="${this.getLabel()}"
      ></span>
    `}};Ds.styles=[Es],g([T()],Ds.prototype,`label`,void 0),g([T()],Ds.prototype,`status`,void 0),customElements.get(`craft-status`)||customElements.define(`craft-status`,Ds);var Os=new Map;function ks(e){var t=Os.get(e);t&&t.destroy()}function As(e){var t=Os.get(e);t&&t.update()}var js=null;typeof window>`u`?((js=function(e){return e}).destroy=function(e){return e},js.update=function(e){return e}):((js=function(e,t){return e&&Array.prototype.forEach.call(e.length?e:[e],function(e){return function(e){if(e&&e.nodeName&&e.nodeName===`TEXTAREA`&&!Os.has(e)){var t,n=null,r=window.getComputedStyle(e),i=(t=e.value,function(){o({testForHeightReduction:t===``||!e.value.startsWith(t),restoreTextAlign:null}),t=e.value}),a=function(t){e.removeEventListener(`autosize:destroy`,a),e.removeEventListener(`autosize:update`,s),e.removeEventListener(`input`,i),window.removeEventListener(`resize`,s),Object.keys(t).forEach(function(n){return e.style[n]=t[n]}),Os.delete(e)}.bind(e,{height:e.style.height,resize:e.style.resize,textAlign:e.style.textAlign,overflowY:e.style.overflowY,overflowX:e.style.overflowX,wordWrap:e.style.wordWrap});e.addEventListener(`autosize:destroy`,a),e.addEventListener(`autosize:update`,s),e.addEventListener(`input`,i),window.addEventListener(`resize`,s),e.style.overflowX=`hidden`,e.style.wordWrap=`break-word`,Os.set(e,{destroy:a,update:s}),s()}function o(t){var i,a,s=t.restoreTextAlign,c=s===void 0?null:s,l=t.testForHeightReduction,u=l===void 0||l,d=r.overflowY;if(e.scrollHeight!==0&&(r.resize===`vertical`?e.style.resize=`none`:r.resize===`both`&&(e.style.resize=`horizontal`),u&&(i=function(e){for(var t=[];e&&e.parentNode&&e.parentNode instanceof Element;)e.parentNode.scrollTop&&t.push([e.parentNode,e.parentNode.scrollTop]),e=e.parentNode;return function(){return t.forEach(function(e){var t=e[0],n=e[1];t.style.scrollBehavior=`auto`,t.scrollTop=n,t.style.scrollBehavior=null})}}(e),e.style.height=``),a=r.boxSizing===`content-box`?e.scrollHeight-(parseFloat(r.paddingTop)+parseFloat(r.paddingBottom)):e.scrollHeight+parseFloat(r.borderTopWidth)+parseFloat(r.borderBottomWidth),r.maxHeight!==`none`&&a>parseFloat(r.maxHeight)?(r.overflowY===`hidden`&&(e.style.overflow=`scroll`),a=parseFloat(r.maxHeight)):r.overflowY!==`hidden`&&(e.style.overflow=`hidden`),e.style.height=a+`px`,c&&(e.style.textAlign=c),i&&i(),n!==a&&(e.dispatchEvent(new Event(`autosize:resized`,{bubbles:!0})),n=a),d!==r.overflow&&!c)){var f=r.textAlign;r.overflow===`hidden`&&(e.style.textAlign=f===`start`?`end`:`start`),o({restoreTextAlign:f,testForHeightReduction:!0})}}function s(){o({testForHeightReduction:!0,restoreTextAlign:null})}}(e)}),e}).destroy=function(e){return e&&Array.prototype.forEach.call(e.length?e:[e],ks),e},js.update=function(e){return e&&Array.prototype.forEach.call(e.length?e:[e],As),e});var Ms=js,Ns=class extends Vo{get _inputNode(){return Array.from(this.children).find(e=>e.slot===`input`)}},Ps=class extends Go(Ns){static get properties(){return{maxRows:{type:Number,attribute:`max-rows`},rows:{type:Number,reflect:!0},readOnly:{type:Boolean,attribute:`readonly`,reflect:!0},placeholder:{type:String,reflect:!0}}}get slots(){return{...super.slots,input:()=>{let e=document.createElement(`textarea`);return e.style.resize!==void 0&&(e.style.resize=`none`),e}}}constructor(){super(),this.rows=2,this.maxRows=6,this.readOnly=!1,this.placeholder=``}connectedCallback(){super.connectedCallback(),this.__initializeAutoresize(),this.__intersectionObserver=new IntersectionObserver(()=>this.resizeTextarea()),this.__intersectionObserver.observe(this)}updated(e){if(super.updated(e),e.has(`name`)&&(this._inputNode.name=this.name),e.has(`autocomplete`)&&(this._inputNode.autocomplete=this.autocomplete),e.has(`disabled`)&&(this._inputNode.disabled=this.disabled,this.validate()),e.has(`rows`)){let e=this._inputNode;e&&(e.rows=this.rows)}if(e.has(`readOnly`)){let e=this._inputNode;e&&(e.readOnly=this.readOnly)}if(e.has(`placeholder`)){let e=this._inputNode;e&&(e.placeholder=this.placeholder)}e.has(`modelValue`)&&this.resizeTextarea(),(e.has(`maxRows`)||e.has(`rows`))&&this.setTextareaMaxHeight()}disconnectedCallback(){super.disconnectedCallback(),Ms.destroy(this._inputNode)}setTextareaMaxHeight(){let{value:e}=this._inputNode;this._inputNode.value=``,this.resizeTextarea();let t=window.getComputedStyle(this._inputNode,null),n=parseFloat(t.lineHeight)||parseFloat(t.height)/this.rows,r=parseFloat(t.paddingTop)+parseFloat(t.paddingBottom),i=parseFloat(t.borderTopWidth)+parseFloat(t.borderBottomWidth),a=t.boxSizing===`border-box`?r+i:0;this._inputNode.style.maxHeight=`${n*this.maxRows+a}px`,this._inputNode.value=e,this.resizeTextarea()}static get styles(){return[...super.styles,b`
        .input-group__container > .input-group__input ::slotted(.form-control) {
          box-sizing: content-box;
          overflow-x: hidden; /* for FF adds height to the TextArea to reserve place for scroll-bars */
        }

        /* Workaround https://bugzilla.mozilla.org/show_bug.cgi?id=1739079 */
        :host([disabled]) ::slotted(textarea) {
          user-select: none;
        }
      `]}get updateComplete(){return this.__textareaUpdateComplete?Promise.all([this.__textareaUpdateComplete,super.updateComplete]):super.updateComplete}resizeTextarea(){Ms.update(this._inputNode)}__initializeAutoresize(){this.__shady_native_contains?this.__textareaUpdateComplete=this.__waitForTextareaRenderedInRealDOM().then(()=>{this.__startAutoresize(),this.__textareaUpdateComplete=null}):this.__startAutoresize()}async __waitForTextareaRenderedInRealDOM(){let e=3;for(;e!==0&&!this.__shady_native_contains(this._inputNode);)await new Promise(e=>setTimeout(e)),--e}__startAutoresize(){Ms(this._inputNode),this.setTextareaMaxHeight()}},Fs=b`
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
`,Is=class extends Ps{constructor(...e){super(...e),this.monospace=!1}static get styles(){return[...super.styles,Ea,Fs]}};g([T({type:Boolean,reflect:!0})],Is.prototype,`monospace`,void 0),customElements.get(`craft-textarea`)||customElements.define(`craft-textarea`,Is);var Ls=b`
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
`,Rs=class extends w{render(){return v`<slot></slot>`}};Rs.styles=[Ls],customElements.get(`craft-button-group`)||customElements.define(`craft-button-group`,Rs);var zs=class extends Wo(w){static get properties(){return{tabIndex:{type:Number,reflect:!0,attribute:`tabindex`}}}constructor(){super(),this.tabIndex=0}connectedCallback(){super.connectedCallback(),this.setAttribute(`role`,`listbox`)}createRenderRoot(){return this}},Bs=Br(e=>class extends Oo(Co(Ko(Gr(Uo(e))))){static get properties(){return{orientation:String,selectionFollowsFocus:{type:Boolean,attribute:`selection-follows-focus`},rotateKeyboardNavigation:{type:Boolean,attribute:`rotate-keyboard-navigation`},hasNoDefaultSelected:{type:Boolean,reflect:!0,attribute:`has-no-default-selected`},_noTypeAhead:{type:Boolean}}}static get styles(){return[...super.styles||[],b`
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
        `]}_inputGroupInputTemplate(){return v`
        <div class="input-group__input">
          <slot name="input"></slot>
          <slot id="options-outlet"></slot>
        </div>
      `}static get scopedElements(){return{...super.scopedElements,"lion-options":zs}}get slots(){return{...super.slots,input:()=>{let e=this.createScopedElement(`lion-options`);return e.setAttribute(`data-tag-name`,`lion-options`),e.registrationTarget=this,e}}}get _inputNode(){return this.querySelector(`[slot="input"]`)}get _listboxNode(){return this._inputNode}get _listboxActiveDescendantNode(){return this._listboxNode.querySelector(`#${this._listboxActiveDescendant}`)}get _listboxSlot(){return this.shadowRoot.querySelector(`slot[name=input]`)}get _scrollTargetNode(){return this._listboxNode}get _activeDescendantOwnerNode(){return this._listboxNode}get activeIndex(){return this.formElements.findIndex(e=>e.active===!0)}set activeIndex(e){if(this.formElements[e]){let t=this.formElements[e];this.__setChildActive(t)}else this.__setChildActive(null)}get checkedIndex(){let e=this.formElements;return this.multipleChoice?e.filter(e=>e.checked).map(t=>e.indexOf(t)):e.indexOf(e.find(e=>e.checked))}set checkedIndex(e){this.setCheckedIndex(e)}constructor(){super(),this.hasNoDefaultSelected=!1,this.orientation=`vertical`,this.rotateKeyboardNavigation=!1,this.selectionFollowsFocus=!1,this._noTypeAhead=!1,this._typeAheadTimeout=1e3,this._listboxActiveDescendant=null,this.__hasInitialSelectedFormElement=!1,this._repropagationRole=`choice-group`,this._listboxReceivesNoFocus=!1,this._oldModelValue=void 0,this._listboxOnKeyDown=this._listboxOnKeyDown.bind(this),this._listboxOnClick=this._listboxOnClick.bind(this),this._listboxOnKeyUp=this._listboxOnKeyUp.bind(this),this._onChildActiveChanged=this._onChildActiveChanged.bind(this),this.__proxyChildModelValueChanged=this.__proxyChildModelValueChanged.bind(this),this.__preventScrollingWithArrowKeys=this.__preventScrollingWithArrowKeys.bind(this),this.__typedChars=[]}connectedCallback(){this._listboxNode&&(this._listboxNode.registrationTarget=this),super.connectedCallback(),this._setupListboxNode(),this.__setupEventListeners(),this.registrationComplete.then(()=>{this.__initInteractionStates()})}firstUpdated(e){super.firstUpdated(e),this.__moveOptionsToListboxNode(),this.registrationComplete.then(()=>{this._initialModelValue=this.modelValue}),new MutationObserver(()=>{this._onListboxContentChanged()}).observe(this._listboxNode,{childList:!0})}updated(e){super.updated(e),e.has(`disabled`)&&(this.disabled?this.__requestOptionsToBeDisabled():this.__retractRequestOptionsToBeDisabled())}disconnectedCallback(){super.disconnectedCallback(),this._teardownListboxNode(),this.__teardownEventListeners()}setCheckedIndex(e){if(this.multipleChoice&&Array.isArray(e)){this._uncheckChildren(this.formElements.filter(t=>t===e)),e.forEach(e=>{this.formElements[e]&&(this.formElements[e].checked=!this.formElements[e].checked)});return}typeof e==`number`&&(e===-1&&this._uncheckChildren(),this.formElements[e]&&(this.formElements[e].disabled?this._uncheckChildren():this.multipleChoice?this.formElements[e].checked=!this.formElements[e].checked:this.formElements[e].checked=!0))}addFormElement(e,t){super.addFormElement(e,t),e.id=e.id||`${this.localName}-option-${Jr()}`,this.disabled&&e.makeRequestToBeDisabled(),this.__setAttributeForAllFormElements(`aria-setsize`,this.formElements.length),this.formElements.forEach((e,t)=>{e.setAttribute(`aria-posinset`,t+1)}),this.__proxyChildModelValueChanged({target:e}),this.resetInteractionState()}resetInteractionState(){super.resetInteractionState(),this.submitted=!1}reset(){this.modelValue=this._initialModelValue,this.activeIndex=-1,this.resetInteractionState()}clear(){super.clear(),this.setCheckedIndex(-1)}_handleTypeAhead(e,{setAsChecked:t}){let{key:n,code:r}=e;if(r.startsWith(`Key`)||r.startsWith(`Digit`)||r.startsWith(`Numpad`)){e.preventDefault(),this.__typedChars.push(n);let r=this.__typedChars.join(``),i=this.formElements.findIndex(e=>e.modelValue.value.toLowerCase().startsWith(r));i>=0&&(t&&this.setCheckedIndex(i),this.activeIndex=i),this.__pendingTypeAheadTimeout&&window.clearTimeout(this.__pendingTypeAheadTimeout),this.__pendingTypeAheadTimeout=setTimeout(()=>{this.__typedChars=[]},this._typeAheadTimeout)}}_getCheckedElements(){return this.formElements.filter(e=>e.checked)}_setupListboxNode(){this._listboxNode?this.__setupListboxNodeInteractions():this._listboxSlot&&this._listboxSlot.addEventListener(`slotchange`,()=>{this.__setupListboxNodeInteractions()})}_onListboxContentChanged(){}_teardownListboxNode(){this._listboxNode&&(this._listboxNode.removeEventListener(`keydown`,this._listboxOnKeyDown),this._listboxNode.removeEventListener(`click`,this._listboxOnClick),this._listboxNode.removeEventListener(`keyup`,this._listboxOnKeyUp))}_getNextEnabledOption(e,t=1){return this.__getEnabledOption(e,t)}_getPreviousEnabledOption(e,t=-1){return this.__getEnabledOption(e,t)}_onChildActiveChanged({target:e}){e.active===!0&&this.__setChildActive(e)}_listboxOnKeyDown(e){if(this.disabled)return;this._isHandlingUserInput=!0,setTimeout(()=>{this._isHandlingUserInput=!1});let{key:t}=e;switch(t){case` `:case`Enter`:if(t===` `&&this._listboxReceivesNoFocus||(t===` `&&e.preventDefault(),!this.formElements[this.activeIndex])||this.formElements[this.activeIndex].disabled)return;this.formElements[this.activeIndex].href&&this.formElements[this.activeIndex].click(),this.setCheckedIndex(this.activeIndex);break;case`ArrowUp`:e.preventDefault(),this.orientation===`vertical`&&(this.activeIndex=this._getPreviousEnabledOption(this.activeIndex));break;case`ArrowLeft`:if(this._listboxReceivesNoFocus)return;e.preventDefault(),this.orientation===`horizontal`&&(this.activeIndex=this._getPreviousEnabledOption(this.activeIndex));break;case`ArrowDown`:e.preventDefault(),this.orientation===`vertical`&&(this.activeIndex=this._getNextEnabledOption(this.activeIndex));break;case`ArrowRight`:if(this._listboxReceivesNoFocus)return;e.preventDefault(),this.orientation===`horizontal`&&(this.activeIndex=this._getNextEnabledOption(this.activeIndex));break;case`Home`:if(this._listboxReceivesNoFocus)return;e.preventDefault(),this.activeIndex=this._getNextEnabledOption(0,0);break;case`End`:if(this._listboxReceivesNoFocus)return;e.preventDefault(),this.activeIndex=this._getPreviousEnabledOption(this.formElements.length-1,0);break;default:this._noTypeAhead||this._handleTypeAhead(e,{setAsChecked:this.selectionFollowsFocus&&!this.multipleChoice})}[`ArrowUp`,`ArrowDown`,`ArrowLeft`,`ArrowRight`,`Home`,`End`].includes(t)&&this.selectionFollowsFocus&&!this.multipleChoice&&this.setCheckedIndex(this.activeIndex)}_listboxOnClick(e){}_listboxOnKeyUp(e){if(this.disabled)return;this._isHandlingUserInput=!0,setTimeout(()=>{this._isHandlingUserInput=!1});let{key:t}=e;switch(t){case`ArrowUp`:case`ArrowDown`:case`Home`:case`End`:case`Enter`:e.preventDefault()}}_onLabelClick(){this._listboxNode.focus()}_scrollIntoView(e,t){e.scrollIntoView({behavior:`smooth`,block:`nearest`})}__setupEventListeners(){this._listboxNode.addEventListener(`active-changed`,this._onChildActiveChanged),this._listboxNode.addEventListener(`model-value-changed`,this.__proxyChildModelValueChanged)}__teardownEventListeners(){this._listboxNode.removeEventListener(`active-changed`,this._onChildActiveChanged),this._listboxNode.removeEventListener(`model-value-changed`,this.__proxyChildModelValueChanged)}__setChildActive(e){if(this.formElements.forEach(t=>{t.active=e===t}),!e){this._activeDescendantOwnerNode.removeAttribute(`aria-activedescendant`);return}this._activeDescendantOwnerNode.setAttribute(`aria-activedescendant`,e.id),this._scrollIntoView(e,this._scrollTargetNode)}_uncheckChildren(e=[]){let t=Array.isArray(e)?e:[e];this.formElements.forEach(e=>{t.includes(e)||(e.checked=!1)})}__onChildCheckedChanged(e){let{target:t}=e;e.stopPropagation&&e.stopPropagation(),t.checked&&!this.multipleChoice&&this._uncheckChildren(t)}__setAttributeForAllFormElements(e,t){this.formElements.forEach(n=>{n.setAttribute(e,t)})}__proxyChildModelValueChanged(e){e.stopPropagation&&e.stopPropagation(),this.__onChildCheckedChanged(e),this.requestUpdate(`modelValue`,this._oldModelValue),e.detail&&e.detail.formPath&&this.dispatchEvent(new CustomEvent(`model-value-changed`,{detail:{formPath:e.detail.formPath,isTriggeredByUser:e.detail.isTriggeredByUser||this._isHandlingUserInput,element:e.target}})),this._oldModelValue=this.modelValue}__getEnabledOption(e,t){let n=e=>t===1?e<this.formElements.length:e>=0;for(let r=e+t;n(r);r+=t)if(this.formElements[r]&&!this.formElements[r].hasAttribute(`aria-hidden`))return r;if(this.rotateKeyboardNavigation){let e=t===-1?this.formElements.length-1:0;for(let r=e;n(r);r+=t)if(this.formElements[r]&&!this.formElements[r].hasAttribute(`aria-hidden`))return r}return e}__moveOptionsToListboxNode(){let e=this.shadowRoot.getElementById(`options-outlet`);e&&(Ur(this,this._listboxNode),e.addEventListener(`slotchange`,()=>{Ur(this,this._listboxNode)}))}__preventScrollingWithArrowKeys(e){if(this.disabled)return;let{key:t}=e;switch(t){case`ArrowUp`:case`ArrowDown`:case`Home`:case`End`:e.preventDefault()}}__setupListboxNodeInteractions(){this._listboxNode.setAttribute(`role`,`listbox`),this._listboxNode.setAttribute(`aria-orientation`,this.orientation),this._listboxNode.setAttribute(`aria-multiselectable`,`${this.multipleChoice}`),this._listboxNode.setAttribute(`tabindex`,`0`),this._listboxNode.addEventListener(`click`,this._listboxOnClick),this._listboxNode.addEventListener(`keyup`,this._listboxOnKeyUp),this._listboxNode.addEventListener(`keydown`,this._listboxOnKeyDown),this._scrollTargetNode.addEventListener(`keydown`,this.__preventScrollingWithArrowKeys)}__requestOptionsToBeDisabled(){this.formElements.forEach(e=>{e.makeRequestToBeDisabled&&e.makeRequestToBeDisabled()})}__retractRequestOptionsToBeDisabled(){this.formElements.forEach(e=>{e.retractRequestToBeDisabled&&e.retractRequestToBeDisabled()})}__initInteractionStates(){this.initInteractionState()}}),Vs=class extends Bs(Aa(Bo(Ro(w)))){get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,focused:this.focused}}get _focusableNode(){return this._inputNode}},Hs=class extends Vr(Jo(Do(Gr(w)))){static get properties(){return{active:{type:Boolean,reflect:!0}}}static get styles(){return[b`
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
      `]}get slots(){return{}}constructor(){super(),this.active=!1,this.__onClick=this.__onClick.bind(this),this.__registerEventListeners()}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`active`&&this.active!==t&&this.dispatchEvent(new Event(`active-changed`,{bubbles:!0}))}updated(e){super.updated(e),e.has(`checked`)&&this.setAttribute(`aria-selected`,`${this.checked}`),e.has(`disabled`)&&this.setAttribute(`aria-disabled`,`${this.disabled}`)}render(){return v`
      <div class="choice-field__label">
        <slot></slot>
      </div>
    `}connectedCallback(){super.connectedCallback(),this.setAttribute(`role`,`option`)}__registerEventListeners(){this.addEventListener(`click`,this.__onClick)}__unRegisterEventListeners(){this.removeEventListener(`click`,this.__onClick)}__onClick(){if(this.disabled)return;let e=this._parentFormGroup;this._isHandlingUserInput=!0,e&&e.multipleChoice?(this.checked=!this.checked,this.active=!this.active):(this.checked=!0,this.active=!0),this._isHandlingUserInput=!1}},Us=b`
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
`,Ws=new WeakMap,Gs=class extends Hs{static get styles(){return[...Hs.styles,Us]}constructor(){super(),this.hint=null,c(this,Ws,640),m(Ws,this,parseInt(getComputedStyle(this).getPropertyValue(`--c-option-wide-threshold`)||`640`,10))}connectedCallback(){super.connectedCallback();let e=this.getBoundingClientRect().width??0;this.toggleAttribute(`wide`,e>=s(Ws,this))}render(){return v`
      <div class="choice-field__label">
        <slot></slot>
        ${this.hint?v`<span class="hint">${this.hint}</span>`:C}
        <slot name="suffix"></slot>
      </div>
    `}};g([T()],Gs.prototype,`hint`,void 0),customElements.get(`craft-option`)||customElements.define(`craft-option`,Gs);var Ks=class extends Vo{static get properties(){return{autocomplete:{type:String}}}constructor(){super(),this.autocomplete=void 0}get _inputNode(){return Array.from(this.children).find(e=>e.slot===`input`)}},qs=class extends Ks{get operationMode(){return`select`}connectedCallback(){super.connectedCallback(),this._inputNode.addEventListener(`change`,this._proxyChangeEvent),this.__selectObserver=new MutationObserver(()=>{this._syncValueUpwards(),this._calculateValues({source:`model`})}),this.__selectObserver.observe(this._inputNode,{attributes:!0,childList:!0,subtree:!0})}updated(e){super.updated(e),e.has(`disabled`)&&(this._inputNode.disabled=this.disabled,this.validate()),e.has(`name`)&&(this._inputNode.name=this.name),e.has(`autocomplete`)&&(this._inputNode.autocomplete=this.autocomplete)}disconnectedCallback(){super.disconnectedCallback(),this._inputNode.removeEventListener(`change`,this._proxyChangeEvent),this.__selectObserver?.disconnect()}formatter(e){let t=Array.from(this._inputNode.options).find(t=>t.value===e);return t?t.text:``}_reflectBackFormattedValueToUser(){this._reflectBackOn()&&(this.value=this.modelValue===void 0?``:this.modelValue)}_proxyChangeEvent(){this.dispatchEvent(new CustomEvent(`user-input-changed`,{bubbles:!0,composed:!0}))}},Js=b`
  ${Ta}

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
    ${wa}
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
`,Ys=class extends qs{constructor(...e){super(...e),this.small=!1}static get styles(){return[...super.styles,Js]}_inputGroupInputTemplate(){return v`
      <div class="input-group__input">
        <slot name="input"></slot>
        <craft-icon
          class="indicator"
          name="chevron-down"
          style="font-size: 0.8em"
        ></craft-icon>
      </div>
    `}};g([T({reflect:!0,type:Boolean})],Ys.prototype,`small`,void 0),customElements.get(`craft-select`)||customElements.define(`craft-select`,Ys);var Xs=class extends Event{constructor(e){super(`wa-select`,{bubbles:!0,cancelable:!0,composed:!0}),this.detail=e}},Zs=b`
  :host {
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
      color: var(--wa-color-text-quiet);
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
`,Qs={small:`s`,medium:`m`,large:`l`},$s=new Set;function ec(e,t){t in Qs&&!$s.has(`${e}:${t}`)&&($s.add(`${e}:${t}`),console.warn(`[${e}] size="${t}" is deprecated. Use size="${Qs[t]}" instead. The long-form value will be removed in the next major version.`))}var tc=b`
  :host([size='xs']) {
    font-size: var(--wa-font-size-xs);
  }

  :host([size='s']),
  :host([size='small']) {
    font-size: var(--wa-font-size-s);
  }

  :host([size='m']),
  :host([size='medium']) {
    font-size: var(--wa-font-size-m);
  }

  :host([size='l']),
  :host([size='large']) {
    font-size: var(--wa-font-size-l);
  }

  :host([size='xl']) {
    font-size: var(--wa-font-size-xl);
  }
`;function*nc(e=document.activeElement){e!=null&&(yield e,`shadowRoot`in e&&e.shadowRoot&&e.shadowRoot.mode!==`closed`&&(yield*nc(e.shadowRoot.activeElement)))}var rc=new Set,ic=class extends at{constructor(){super(...arguments),this.submenuCleanups=new Map,this.localize=new Te(this),this.userTypedQuery=``,this.openSubmenuStack=[],this.open=!1,this.size=`m`,this.placement=`bottom-start`,this.distance=0,this.skidding=0,this.handleDocumentKeyDown=async e=>{let t=this.localize.dir()===`rtl`;if(e.key===`Escape`&&this.open&&Er(this)){let t=this.getTrigger();e.preventDefault(),e.stopPropagation(),this.open=!1,t?.focus({preventScroll:!0});return}let n=[...nc()].find(e=>e.localName===`wa-dropdown-item`),r=n?.localName===`wa-dropdown-item`,i=this.getCurrentSubmenuItem(),a=!!i,o,s,c;a?(o=this.getSubmenuItems(i),s=o.find(e=>e.active||e===n),c=s?o.indexOf(s):-1):(o=this.getItems(),s=o.find(e=>e.active||e===n),c=s?o.indexOf(s):-1);let l;if(e.key===`ArrowUp`&&(e.preventDefault(),e.stopPropagation(),l=c>0?o[c-1]:o[o.length-1]),e.key===`ArrowDown`&&(e.preventDefault(),e.stopPropagation(),l=c!==-1&&c<o.length-1?o[c+1]:o[0]),e.key===(t?`ArrowLeft`:`ArrowRight`)&&r&&s&&s.hasSubmenu){e.preventDefault(),e.stopPropagation(),s.submenuOpen=!0,this.addToSubmenuStack(s),setTimeout(()=>{let e=this.getSubmenuItems(s);e.length>0&&(e.forEach((e,t)=>e.active=t===0),e[0].focus({preventScroll:!0}))},0);return}if(e.key===(t?`ArrowRight`:`ArrowLeft`)&&a){e.preventDefault(),e.stopPropagation();let t=this.removeFromSubmenuStack();t&&(t.submenuOpen=!1,setTimeout(()=>{t.focus({preventScroll:!0}),t.active=!0,(t.slot===`submenu`?this.getSubmenuItems(t.parentElement):this.getItems()).forEach(e=>{e!==t&&(e.active=!1)})},0));return}if((e.key===`Home`||e.key===`End`)&&(e.preventDefault(),e.stopPropagation(),l=e.key===`Home`?o[0]:o[o.length-1]),e.key===`Tab`&&await this.hideMenu(),e.key.length===1&&!(e.metaKey||e.ctrlKey||e.altKey)&&!(e.key===` `&&this.userTypedQuery===``)&&(clearTimeout(this.userTypedTimeout),this.userTypedTimeout=setTimeout(()=>{this.userTypedQuery=``},1e3),this.userTypedQuery+=e.key,o.some(e=>{let t=(e.textContent||``).trim().toLowerCase(),n=this.userTypedQuery.trim().toLowerCase();return t.startsWith(n)?(l=e,!0):!1})),l){e.preventDefault(),e.stopPropagation(),o.forEach(e=>e.active=e===l),l.focus({preventScroll:!0});return}(e.key===`Enter`||e.key===` `&&this.userTypedQuery===``)&&r&&s&&(e.preventDefault(),e.stopPropagation(),s.hasSubmenu?(s.submenuOpen=!0,this.addToSubmenuStack(s),setTimeout(()=>{let e=this.getSubmenuItems(s);e.length>0&&(e.forEach((e,t)=>e.active=t===0),e[0].focus({preventScroll:!0}))},0)):this.makeSelection(s))},this.handleDocumentPointerDown=e=>{e.composedPath().some(e=>e instanceof HTMLElement?e===this||e.closest(`wa-dropdown, [part="submenu"]`):!1)||(this.open=!1)},this.handleGlobalMouseMove=e=>{let t=this.getCurrentSubmenuItem();if(!t?.submenuOpen||!t.submenuElement)return;let n=t.submenuElement.getBoundingClientRect(),r=this.localize.dir()===`rtl`,i=r?n.right:n.left,a=r?Math.max(e.clientX,i):Math.min(e.clientX,i),o=Math.max(n.top,Math.min(e.clientY,n.bottom));t.submenuElement.style.setProperty(`--safe-triangle-cursor-x`,`${a}px`),t.submenuElement.style.setProperty(`--safe-triangle-cursor-y`,`${o}px`);let s=e.composedPath(),c=t.matches(`:hover`),l=!!t.submenuElement?.matches(`:hover`),u=c||!!s.find(e=>e===t),d=l||!!s.find(e=>e instanceof HTMLElement&&e.closest(`[part="submenu"]`)===t.submenuElement);!u&&!d&&setTimeout(()=>{!c&&!l&&(t.submenuOpen=!1)},100)}}handleSizeChange(){ec(this.localName,this.size)}disconnectedCallback(){super.disconnectedCallback(),clearInterval(this.userTypedTimeout),this.closeAllSubmenus(),this.submenuCleanups.forEach(e=>e()),this.submenuCleanups.clear(),document.removeEventListener(`mousemove`,this.handleGlobalMouseMove),document.removeEventListener(`keydown`,this.handleDocumentKeyDown),document.removeEventListener(`pointerdown`,this.handleDocumentPointerDown),Tr(this)}firstUpdated(){this.syncAriaAttributes()}async updated(e){if(e.has(`open`)){let t=e.get(`open`);if(t===this.open||t===void 0&&this.open===!1)return;this.customStates.set(`open`,this.open),this.open?await this.showMenu():(this.closeAllSubmenus(),await this.hideMenu())}e.has(`size`)&&this.syncItemSizes()}getItems(e=!1){let t=(this.defaultSlot?.assignedElements({flatten:!0})??[]).filter(e=>e.localName===`wa-dropdown-item`);return e?t:t.filter(e=>!e.disabled)}getSubmenuItems(e,t=!1){let n=e.shadowRoot?.querySelector(`slot[name="submenu"]`)||e.querySelector(`slot[name="submenu"]`);if(!n)return[];let r=n.assignedElements({flatten:!0}).filter(e=>e.localName===`wa-dropdown-item`);return t?r:r.filter(e=>!e.disabled)}syncItemSizes(){(this.defaultSlot?.assignedElements({flatten:!0})??[]).filter(e=>e.localName===`wa-dropdown-item`).forEach(e=>e.size=this.size)}addToSubmenuStack(e){let t=this.openSubmenuStack.indexOf(e);t===-1?this.openSubmenuStack.push(e):this.openSubmenuStack=this.openSubmenuStack.slice(0,t+1)}removeFromSubmenuStack(){return this.openSubmenuStack.pop()}getCurrentSubmenuItem(){return this.openSubmenuStack.length>0?this.openSubmenuStack[this.openSubmenuStack.length-1]:void 0}closeAllSubmenus(){this.getItems(!0).forEach(e=>{e.submenuOpen=!1}),this.openSubmenuStack=[]}closeSiblingSubmenus(e){let t=e.closest(`wa-dropdown-item:not([slot="submenu"])`),n;n=t?this.getSubmenuItems(t,!0):this.getItems(!0),n.forEach(t=>{t!==e&&t.submenuOpen&&(t.submenuOpen=!1)}),this.openSubmenuStack.includes(e)||this.openSubmenuStack.push(e)}getTrigger(){return this.querySelector(`[slot="trigger"]`)}async showMenu(){if(!this.getTrigger()||!this.popup||!this.menu)return;let e=new Dr;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}if(this.popup.active)return;rc.forEach(e=>e.open=!1),this.popup.active=!0,this.open=!0,rc.add(this),wr(this),this.syncAriaAttributes(),document.addEventListener(`keydown`,this.handleDocumentKeyDown),document.addEventListener(`pointerdown`,this.handleDocumentPointerDown),document.addEventListener(`mousemove`,this.handleGlobalMouseMove),this.menu.classList.remove(`hide`),await Fr(this.menu,`show`);let t=this.getItems();t.length>0&&(t.forEach((e,t)=>e.active=t===0),t[0].focus({preventScroll:!0})),this.dispatchEvent(new Ar)}async hideMenu(){if(!this.popup||!this.menu)return;let e=new Or({source:this});if(this.dispatchEvent(e),e.defaultPrevented){this.open=!0;return}this.open=!1,rc.delete(this),Tr(this),this.syncAriaAttributes(),document.removeEventListener(`keydown`,this.handleDocumentKeyDown),document.removeEventListener(`pointerdown`,this.handleDocumentPointerDown),document.removeEventListener(`mousemove`,this.handleGlobalMouseMove),this.menu.classList.remove(`show`),await Fr(this.menu,`hide`),this.popup.active=this.open,this.dispatchEvent(new kr)}handleMenuClick(e){let t=e.target.closest(`wa-dropdown-item`);if(!(!t||t.disabled)){if(t.hasSubmenu){t.submenuOpen||=(this.closeSiblingSubmenus(t),this.addToSubmenuStack(t),!0),e.stopPropagation();return}this.makeSelection(t)}}async handleMenuSlotChange(){let e=this.getItems(!0);await Promise.all(e.map(e=>e.updateComplete)),this.syncItemSizes();let t=e.some(e=>e.type===`checkbox`),n=e.some(e=>e.hasSubmenu);e.forEach((e,r)=>{e.active=r===0,e.checkboxAdjacent=t,e.submenuAdjacent=n})}handleTriggerClick(){this.open=!this.open}handleSubmenuOpening(e){let t=e.detail.item;this.closeSiblingSubmenus(t),this.addToSubmenuStack(t),this.setupSubmenuPosition(t),this.processSubmenuItems(t)}setupSubmenuPosition(e){if(!e.submenuElement)return;this.cleanupSubmenuPosition(e);let t=dr(e,e.submenuElement,()=>{this.positionSubmenu(e),this.updateSafeTriangleCoordinates(e)});this.submenuCleanups.set(e,t);let n=e.submenuElement.querySelector(`slot[name="submenu"]`);n&&(n.removeEventListener(`slotchange`,ic.handleSubmenuSlotChange),n.addEventListener(`slotchange`,ic.handleSubmenuSlotChange),ic.handleSubmenuSlotChange({target:n}))}static handleSubmenuSlotChange(e){let t=e.target;if(!t)return;let n=t.assignedElements().filter(e=>e.localName===`wa-dropdown-item`);if(n.length===0)return;let r=n.some(e=>e.hasSubmenu),i=n.some(e=>e.type===`checkbox`);n.forEach(e=>{e.submenuAdjacent=r,e.checkboxAdjacent=i})}processSubmenuItems(e){if(!e.submenuElement)return;let t=this.getSubmenuItems(e,!0),n=t.some(e=>e.hasSubmenu);t.forEach(e=>{e.submenuAdjacent=n})}cleanupSubmenuPosition(e){let t=this.submenuCleanups.get(e);t&&(t(),this.submenuCleanups.delete(e))}positionSubmenu(e){if(!e.submenuElement)return;let t=this.localize.dir()===`rtl`?`left-start`:`right-start`;_r(e,e.submenuElement,{placement:t,middleware:[fr({mainAxis:0,crossAxis:-5}),mr({fallbackStrategy:`bestFit`}),pr({padding:8})]}).then(({x:t,y:n,placement:r})=>{e.submenuElement.setAttribute(`data-placement`,r),Object.assign(e.submenuElement.style,{left:`${t}px`,top:`${n}px`})})}updateSafeTriangleCoordinates(e){if(!e.submenuElement||!e.submenuOpen)return;if(document.activeElement?.matches(`:focus-visible`)){e.submenuElement.style.setProperty(`--safe-triangle-visible`,`none`);return}e.submenuElement.style.setProperty(`--safe-triangle-visible`,`block`);let t=e.submenuElement.getBoundingClientRect(),n=this.localize.dir()===`rtl`;e.submenuElement.style.setProperty(`--safe-triangle-submenu-start-x`,`${n?t.right:t.left}px`),e.submenuElement.style.setProperty(`--safe-triangle-submenu-start-y`,`${t.top}px`),e.submenuElement.style.setProperty(`--safe-triangle-submenu-end-x`,`${n?t.right:t.left}px`),e.submenuElement.style.setProperty(`--safe-triangle-submenu-end-y`,`${t.bottom}px`)}makeSelection(e){let t=this.getTrigger();if(e.disabled)return;e.type===`checkbox`&&(e.checked=!e.checked);let n=new Xs({item:e});this.dispatchEvent(n),n.defaultPrevented||(this.open=!1,t?.focus({preventScroll:!0}))}async syncAriaAttributes(){let e=this.getTrigger(),t;e&&(e.localName===`wa-button`?(await customElements.whenDefined(`wa-button`),await e.updateComplete,t=e.shadowRoot.querySelector(`[part="base"]`)):t=e,t.hasAttribute(`id`)||t.setAttribute(`id`,Nr(`wa-dropdown-trigger-`)),t.setAttribute(`aria-haspopup`,`menu`),t.setAttribute(`aria-expanded`,this.open?`true`:`false`),this.menu?.setAttribute(`aria-expanded`,`false`))}render(){let e=this.hasUpdated?this.popup?.active:this.open;return v`
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
    `}};ic.css=[tc,Zs],A([O(`slot:not([name])`)],ic.prototype,`defaultSlot`,2),A([O(`#menu`)],ic.prototype,`menu`,2),A([O(`wa-popup`)],ic.prototype,`popup`,2),A([T({type:Boolean,reflect:!0})],ic.prototype,`open`,2),A([T({reflect:!0})],ic.prototype,`size`,2),A([nt(`size`)],ic.prototype,`handleSizeChange`,1),A([T({reflect:!0})],ic.prototype,`placement`,2),A([T({type:Number})],ic.prototype,`distance`,2),A([T({type:Number})],ic.prototype,`skidding`,2),ic=A([D(`wa-dropdown`)],ic);var ac=b`
  :host {
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
      var(--wa-transition-fast) background-color var(--wa-transition-easing),
      var(--wa-transition-fast) color var(--wa-transition-easing);
  }

  @media (hover: hover) {
    :host(:hover:not(:state(disabled))) {
      background-color: var(--wa-color-neutral-fill-normal);
    }
  }

  :host(:state(submenu-open)) {
    background-color: var(--wa-color-neutral-fill-normal);
  }

  :host(:focus-visible) {
    z-index: 1;
    outline: var(--wa-focus-ring);
    background-color: var(--wa-color-neutral-fill-normal);
  }

  :host(:state(disabled)),
  :host([disabled]) {
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

  :host([variant='danger']:state(submenu-open)),
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
`,oc=class{constructor(e,...t){this.slotNames=[],this.handleSlotChange=e=>{let t=e.target;(this.slotNames.includes(`[default]`)&&!t.name||t.name&&this.slotNames.includes(t.name))&&this.host.requestUpdate()},(this.host=e).addController(this),this.slotNames=t}hasDefaultSlot(){return this.host.childNodes?[...this.host.childNodes].some(e=>{if(e.nodeType===Node.TEXT_NODE&&e.textContent.trim()!==``)return!0;if(e.nodeType===Node.ELEMENT_NODE){let t=e;if(t.tagName.toLowerCase()===`wa-visually-hidden`)return!1;if(!t.hasAttribute(`slot`))return!0}return!1}):!1}hasNamedSlot(e){return this.host.querySelector?.(`:scope > [slot="${e}"]`)!==null}test(e){return e===`[default]`?this.hasDefaultSlot():this.hasNamedSlot(e)}hostConnected(){this.host.shadowRoot?.addEventListener?.(`slotchange`,this.handleSlotChange)}hostDisconnected(){this.host.shadowRoot?.removeEventListener?.(`slotchange`,this.handleSlotChange)}},sc=class extends at{constructor(){super(...arguments),this.hasSlotController=new oc(this,`[default]`,`start`,`end`),this.active=!1,this.variant=`default`,this.size=`m`,this.checkboxAdjacent=!1,this.submenuAdjacent=!1,this.type=`normal`,this.checked=!1,this.disabled=!1,this.submenuOpen=!1,this.hasSubmenu=!1,this.handleSlotChange=()=>{this.hasSubmenu=this.hasSlotController.test(`submenu`),this.updateHasSubmenuState(),this.hasSubmenu?(this.setAttribute(`aria-haspopup`,`menu`),this.setAttribute(`aria-expanded`,this.submenuOpen?`true`:`false`)):(this.removeAttribute(`aria-haspopup`),this.removeAttribute(`aria-expanded`))},this.handleHostClick=e=>{this.disabled&&(e.preventDefault(),e.stopImmediatePropagation())},this.handleClick=e=>{this.disabled&&(e.preventDefault(),e.stopImmediatePropagation())}}handleSizeChange(){ec(this.localName,this.size)}connectedCallback(){super.connectedCallback(),this.addEventListener(`click`,this.handleHostClick),this.addEventListener(`mouseenter`,this.handleMouseEnter.bind(this)),this.shadowRoot.addEventListener(`click`,this.handleClick,{capture:!0}),this.shadowRoot.addEventListener(`slotchange`,this.handleSlotChange)}disconnectedCallback(){super.disconnectedCallback(),this.closeSubmenu(),this.removeEventListener(`click`,this.handleHostClick),this.removeEventListener(`mouseenter`,this.handleMouseEnter),this.shadowRoot.removeEventListener(`click`,this.handleClick,{capture:!0}),this.shadowRoot.removeEventListener(`slotchange`,this.handleSlotChange)}firstUpdated(){this.setAttribute(`tabindex`,`-1`),this.hasSubmenu=this.hasSlotController.test(`submenu`),this.updateHasSubmenuState()}updated(e){e.has(`active`)&&(this.setAttribute(`tabindex`,this.active?`0`:`-1`),this.customStates.set(`active`,this.active)),e.has(`checked`)&&(this.type===`checkbox`?this.setAttribute(`aria-checked`,this.checked?`true`:`false`):this.removeAttribute(`aria-checked`),this.customStates.set(`checked`,this.checked)),e.has(`disabled`)&&(this.setAttribute(`aria-disabled`,this.disabled?`true`:`false`),this.customStates.set(`disabled`,this.disabled)),e.has(`type`)&&(this.type===`checkbox`?(this.setAttribute(`role`,`menuitemcheckbox`),this.setAttribute(`aria-checked`,this.checked?`true`:`false`)):(this.setAttribute(`role`,`menuitem`),this.removeAttribute(`aria-checked`))),e.has(`submenuOpen`)&&(this.customStates.set(`submenu-open`,this.submenuOpen),this.submenuOpen?this.openSubmenu():this.closeSubmenu())}updateHasSubmenuState(){this.customStates.set(`has-submenu`,this.hasSubmenu)}async openSubmenu(){let e=this.submenuElement;!this.hasSubmenu||!e||!this.isConnected||(this.notifyParentOfOpening(),e.showPopover?.(),e.hidden=!1,e.setAttribute(`data-visible`,``),this.submenuOpen=!0,this.setAttribute(`aria-expanded`,`true`),await Fr(e,`show`),setTimeout(()=>{let e=this.getSubmenuItems();e.length>0&&(e.forEach((e,t)=>e.active=t===0),e[0].focus({preventScroll:!0}))},0))}notifyParentOfOpening(){let e=new CustomEvent(`submenu-opening`,{bubbles:!0,composed:!0,detail:{item:this}});this.dispatchEvent(e);let t=this.parentElement;t&&[...t.children].filter(e=>e!==this&&e.localName===`wa-dropdown-item`&&e.getAttribute(`slot`)===this.getAttribute(`slot`)&&e.submenuOpen).forEach(e=>{e.submenuOpen=!1})}async closeSubmenu(){let e=this.submenuElement;!this.hasSubmenu||!e||(this.submenuOpen=!1,this.setAttribute(`aria-expanded`,`false`),e.hidden||(await Fr(e,`hide`),e?.isConnected&&(e.hidden=!0,e.removeAttribute(`data-visible`),e.hidePopover?.())))}getSubmenuItems(){return[...this.children].filter(e=>e.localName===`wa-dropdown-item`&&e.getAttribute(`slot`)===`submenu`&&!e.hasAttribute(`disabled`))}handleMouseEnter(){this.hasSubmenu&&!this.disabled&&(this.notifyParentOfOpening(),this.submenuOpen=!0)}render(){return v`
      ${this.type===`checkbox`?v`
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

      ${this.hasSubmenu?v`
            <wa-icon
              id="submenu-indicator"
              part="submenu-icon"
              exportparts="svg:submenu-icon__svg"
              library="system"
              name="chevron-right"
            ></wa-icon>
          `:``}
      ${this.hasSubmenu?v`
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
    `}};sc.css=ac,A([O(`#submenu`)],sc.prototype,`submenuElement`,2),A([T({type:Boolean})],sc.prototype,`active`,2),A([T({reflect:!0})],sc.prototype,`variant`,2),A([T({reflect:!0})],sc.prototype,`size`,2),A([nt(`size`)],sc.prototype,`handleSizeChange`,1),A([T({attribute:`checkbox-adjacent`,type:Boolean,reflect:!0})],sc.prototype,`checkboxAdjacent`,2),A([T({attribute:`submenu-adjacent`,type:Boolean,reflect:!0})],sc.prototype,`submenuAdjacent`,2),A([T()],sc.prototype,`value`,2),A([T({reflect:!0})],sc.prototype,`type`,2),A([T({type:Boolean})],sc.prototype,`checked`,2),A([T({type:Boolean,reflect:!0})],sc.prototype,`disabled`,2),A([T({type:Boolean,reflect:!0})],sc.prototype,`submenuOpen`,2),A([E()],sc.prototype,`hasSubmenu`,2),sc=A([D(`wa-dropdown-item`)],sc);var cc=class extends ic{static get styles(){return[ic.styles,b`
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
      `]}},lc=class extends sc{static get styles(){return[sc.styles,b`
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
      `]}};customElements.get(`craft-dropdown`)||customElements.define(`craft-dropdown`,cc),customElements.get(`craft-dropdown-item`)||customElements.define(`craft-dropdown-item`,lc);function uc({el:e,uid:t}){e.setAttribute(`id`,`panel-${t}`),e.setAttribute(`role`,`tabpanel`),e.setAttribute(`aria-labelledby`,`button-${t}`),e.hasAttribute(`tabindex`)||e.setAttribute(`tabindex`,`0`)}function dc(e){e.setAttribute(`selected`,`true`)}function fc(e){e.removeAttribute(`selected`)}function pc({el:e,uid:t,clickHandler:n,keydownHandler:r,keyupHandler:i}){e.setAttribute(`id`,`button-${t}`),e.setAttribute(`role`,`tab`),e.setAttribute(`aria-controls`,`panel-${t}`),e.addEventListener(`click`,n),e.addEventListener(`keyup`,i),e.addEventListener(`keydown`,r)}function mc({el:e,clickHandler:t,keydownHandler:n,keyupHandler:r}){e.removeAttribute(`id`),e.removeAttribute(`role`),e.removeAttribute(`aria-controls`),e.removeEventListener(`click`,t),e.removeEventListener(`keyup`,r),e.removeEventListener(`keydown`,n)}function hc(e,t=!1){t&&e.focus(),e.setAttribute(`selected`,`true`),e.setAttribute(`aria-selected`,`true`),e.setAttribute(`tabindex`,`0`)}function gc(e){e.removeAttribute(`selected`),e.setAttribute(`aria-selected`,`false`),e.setAttribute(`tabindex`,`-1`)}function _c(e){let t=e;switch(t.key){case`ArrowDown`:case`ArrowRight`:case`ArrowUp`:case`ArrowLeft`:case`Home`:case`End`:t.preventDefault()}}var vc=class extends w{static get properties(){return{selectedIndex:{type:Number,attribute:`selected-index`,reflect:!0}}}static get styles(){return[b`
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
      `]}render(){return v`
      <div class="tabs__tab-group" role="tablist">
        <slot name="tab"></slot>
      </div>
      <div class="tabs__panels">
        <slot name="panel"></slot>
      </div>
    `}constructor(){super(),this.selectedIndex=0}firstUpdated(e){super.firstUpdated(e),this.__setupSlots(),this.tabs[0]?.disabled&&(this.selectedIndex=this.tabs.findIndex(e=>!e.disabled))}get tabs(){return Array.from(this.children).filter(e=>e.slot===`tab`)}get panels(){return Array.from(this.children).filter(e=>e.slot===`panel`)}static enabledWarnings=super.enabledWarnings?.filter(e=>e!==`change-in-update`)||[];__setupSlots(){if(this.shadowRoot){let e=this.shadowRoot.querySelector(`slot[name=tab]`);e&&e.addEventListener(`slotchange`,()=>{this.__cleanStore(),this.__setupStore(),this.__updateSelected(!1)})}}__setupStore(){this.__store=[],this.tabs.length!==this.panels.length&&console.warn(`The amount of tabs (${this.tabs.length}) doesn't match the amount of panels (${this.panels.length}).`),this.tabs.forEach((e,t)=>{let n={uid:Jr(),el:e,button:e,panel:this.panels[t],clickHandler:this.__createButtonClickHandler(t),keydownHandler:_c.bind(this),keyupHandler:this.__handleButtonKeyup.bind(this)};uc({...n,el:n.panel}),pc(n),fc(n.panel),gc(n.button),this.__store&&this.__store.push(n)})}__cleanStore(){this.__store&&=(this.__store.forEach(e=>{mc(e)}),[])}__getNextNotDisabledTab(e,t,n){let r=[],i=e.filter((e,t)=>!e.disabled&&t>this.selectedIndex),a=e.filter((e,t)=>!e.disabled&&t<this.selectedIndex);return r=n===`right`?[...i,...a]:[...a.reverse(),...i.reverse()],r[0]}__getNextAvailableIndex(e,t){let n=this.tabs[this.selectedIndex];if(this.tabs.every(e=>!e.disabled))return e;if(t===`ArrowRight`||t===`ArrowDown`){let e=this.__getNextNotDisabledTab(this.tabs,n,`right`);return this.tabs.findIndex(t=>e===t)}if(t===`ArrowLeft`||t===`ArrowUp`){let e=this.__getNextNotDisabledTab(this.tabs,n,`left`);return this.tabs.findIndex(t=>e===t)}if(t===`Home`)return this.tabs.findIndex(e=>!e.disabled);if(t===`End`){let e=this.tabs.map((e,t)=>({disabled:e.disabled,index:t})).filter(e=>!e.disabled);return e[e.length-1].index}return-1}__createButtonClickHandler(e){return()=>{this._setSelectedIndexWithFocus(e)}}__handleButtonKeyup(e){let t=e;if(typeof this.selectedIndex==`number`)switch(t.key){case`ArrowDown`:case`ArrowRight`:this.selectedIndex+1>=this._pairCount?this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(0,t.key)):this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this.selectedIndex+1,t.key));break;case`ArrowUp`:case`ArrowLeft`:this.selectedIndex<=0?this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this._pairCount-1,t.key)):this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this.selectedIndex-1,t.key));break;case`Home`:this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(0,t.key));break;case`End`:this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this._pairCount-1,t.key));break}}get selectedIndex(){return this.__selectedIndex||0}set selectedIndex(e){if(e===this.__selectedIndex)return;let t=this.__selectedIndex;this.__selectedIndex=e,this.__updateSelected(!1),this.dispatchEvent(new Event(`selected-changed`)),this.requestUpdate(`selectedIndex`,t)}_setSelectedIndexWithFocus(e){if(e===-1)return;let t=this.__selectedIndex;this.__selectedIndex=e,this.__updateSelected(!0),this.dispatchEvent(new Event(`selected-changed`)),this.requestUpdate(`selectedIndex`,t)}get _pairCount(){return this.__store&&this.__store.length||0}__updateSelected(e=!1){if(!(this.__store&&typeof this.selectedIndex==`number`&&this.__store[this.selectedIndex]))return;let t=this.tabs.find(e=>e.hasAttribute(`selected`)),n=this.panels.find(e=>e.hasAttribute(`selected`));t&&gc(t),n&&fc(n);let{button:r,panel:i}=this.__store[this.selectedIndex];r&&hc(r,e),i&&dc(i)}},yc=b`
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
`,bc=class extends vc{static get styles(){return[...super.styles,yc]}};customElements.get(`craft-tabs`)||customElements.define(`craft-tabs`,bc);var xc=b`
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
`,Sc=class extends w{constructor(...e){super(...e),this.label=``}render(){let e=!!this.label||!!this.querySelector(`[slot="header"]`)||!!this.querySelector(`[slot="label"]`)||!!this.querySelector(`[slot="actions"]`),t=!!this.querySelector(`[slot="footer"]`);return v`
      <div class="card">
        <div>
          ${e?v`<div class="card__header">
                <slot name="header">
                  <slot name="label" class="card__label" part="label"
                    >${this.label}</slot
                  >
                  <slot name="actions"></slot>
                </slot>
              </div>`:C}

          <div class="card__body">
            <slot></slot>
          </div>

          ${t?v`<div class="card__footer"><slot name="footer"></slot></div>`:C}
        </div>
      </div>
    `}};Sc.styles=[xc],g([T()],Sc.prototype,`label`,void 0),customElements.get(`craft-card`)||customElements.define(`craft-card`,Sc);var Cc=b`
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
`,wc=class extends w{render(){return v`<slot></slot> `}};wc.styles=[Cc],customElements.get(`craft-tab`)||customElements.define(`craft-tab`,wc);var Tc=class extends Hr(w){static get properties(){return{checked:{type:Boolean,reflect:!0}}}static get styles(){return[b`
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
      `]}render(){return v`
      <div class="btn">
        <div class="switch-button__track"></div>
        <div class="switch-button__thumb"></div>
      </div>
    `}constructor(){super(),this.value=``,this.checked=!1,this.__initialized=!1,this._toggleChecked=this._toggleChecked.bind(this),this.__handleKeydown=this._handleKeydown.bind(this),this.__handleKeyup=this._handleKeyup.bind(this)}connectedCallback(){super.connectedCallback(),this.setAttribute(`role`,`switch`),this.setAttribute(`aria-checked`,`${this.checked}`),this.addEventListener(`click`,this._toggleChecked),this.addEventListener(`keydown`,this.__handleKeydown),this.addEventListener(`keyup`,this.__handleKeyup)}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener(`click`,this._toggleChecked),this.removeEventListener(`keydown`,this.__handleKeydown),this.removeEventListener(`keyup`,this.__handleKeyup)}_toggleChecked(){this.disabled||(this.focus(),this.checked=!this.checked)}__checkedStateChange(){this.dispatchEvent(new Event(`checked-changed`,{bubbles:!0})),this.setAttribute(`aria-checked`,`${this.checked}`)}_handleKeydown(e){e.key===` `&&e.preventDefault()}_handleKeyup(e){[` `,`Enter`].includes(e.key)&&this._toggleChecked()}updated(e){super.updated(e),e.has(`disabled`)&&this.setAttribute(`aria-disabled`,`${this.disabled}`)}requestUpdate(e,t,n){super.requestUpdate(e,t,n),this.__initialized&&this.isConnected&&e===`checked`&&this.checked!==t&&!this.disabled&&this.__checkedStateChange()}firstUpdated(e){super.firstUpdated(e),this.__initialized=!0}},Ec=class extends Co(Jo(Vo)){static get styles(){return[...super.styles,b`
        :host([hidden]) {
          display: none;
        }

        :host([disabled]) {
          color: #adadad;
        }
      `]}static get scopedElements(){return{...super.scopedElements,"lion-switch-button":Tc}}get _inputNode(){return Array.from(this.children).find(e=>e.slot===`input`)}get slots(){return{...super.slots,input:()=>{let e=this.createScopedElement(`lion-switch-button`);return e.setAttribute(`data-tag-name`,`lion-switch-button`),e}}}render(){return v`
      <div class="form-field__group-one">${this._groupOneTemplate()}</div>
      <div class="form-field__group-two">${this._groupTwoTemplate()}</div>
    `}_groupOneTemplate(){return v`${this._labelTemplate()} ${this._helpTextTemplate()} ${this._feedbackTemplate()}`}_groupTwoTemplate(){return v`${this._inputGroupTemplate()}`}constructor(){super(),this.checked=!1,this.__handleButtonSwitchCheckedChanged=this.__handleButtonSwitchCheckedChanged.bind(this)}connectedCallback(){super.connectedCallback(),this.addEventListener(`checked-changed`,this.__handleButtonSwitchCheckedChanged),this._labelNode&&this._labelNode.addEventListener(`click`,this._toggleChecked),this._syncButtonSwitch()}disconnectedCallback(){super.disconnectedCallback(),this._inputNode&&this.removeEventListener(`checked-changed`,this.__handleButtonSwitchCheckedChanged),this._labelNode&&this._labelNode.removeEventListener(`click`,this._toggleChecked)}updated(e){super.updated(e),e.has(`disabled`)&&this._syncButtonSwitch()}_toggleChecked(e){e.preventDefault(),super._toggleChecked(e)}_isEmpty(){return!1}__handleButtonSwitchCheckedChanged(e){e.stopPropagation(),this._isHandlingUserInput=!0,this.checked=this._inputNode.checked,this._isHandlingUserInput=!1}_syncButtonSwitch(){this._inputNode.disabled=this.disabled}_onLabelClick(){this.disabled||this._inputNode.focus()}},Dc=class extends Tc{static get styles(){return[...super.styles,b`
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
      `]}};customElements.get(`craft-switch-button`)||customElements.define(`craft-switch-button`,Dc);var Oc=b`
  :host {
    display: grid;
  }

  .input-group {
    display: inline-flex;
  }

  ::slotted(label) {
    font-weight: bold;
  }
`,kc=class extends Ec{constructor(...e){super(...e),this.size=`medium`}static get styles(){return[...super.styles,Ta,Oc]}get slots(){return{...super.slots,input:()=>{let e=this.createScopedElement(`craft-switch-button`);return e.setAttribute(`size`,this.size),e.setAttribute(`data-tag-name`,`craft-switch-button`),e}}}static get scopedElements(){return{...super.scopedElements,"craft-switch-button":Dc}}};g([T({type:String,reflect:!0})],kc.prototype,`size`,void 0),customElements.get(`craft-switch`)||customElements.define(`craft-switch`,kc);var Ac=b`
  .breadcrumbs {
    display: flex;
    align-items: center;
  }
`,jc=class extends w{constructor(...e){super(...e),this.label=n(`Breadcrumbs`),this.items=[],this.visibleItems=0,this.firstRender=!0}getSeparator(){let e=this.separatorSlot.assignedElements({flatten:!0})[0].cloneNode(!0);return[e,...e.querySelectorAll(`[id]`)].forEach(e=>e.removeAttribute(`id`)),e.setAttribute(`data-default`,``),e.slot=`separator`,e}calculateBreadcrumbItemsWidth(){this.items=this.breadcrumbsElements.map((e,t)=>{let n=e.offsetWidth;return e.hasAttribute(`hidden`)&&(e.removeAttribute(`hidden`),n=e.offsetWidth,e.setAttribute(`hidden`,``)),{label:e.innerText,href:e.href,value:t.toString(),offsetWidth:n,isVisible:!0}})}async handleSlotChange(){let e=[...this.defaultSlot.assignedElements({flatten:!0})].filter(e=>e.tagName.toLowerCase()===`craft-breadcrumb-item`);if(e.forEach((t,n)=>{let r=t.querySelector(`[slot="separator"]`);r===null?t.append(this.getSeparator()):r.hasAttribute(`data-default`)&&r.replaceWith(this.getSeparator()),n===e.length-1?t.setAttribute(`aria-current`,`page`):t.removeAttribute(`aria-current`)}),this.breadcrumbsElements.length===0){this.items=[],this.visibleItems=0;return}await Promise.all(this.breadcrumbsElements.map(e=>e.updateComplete)),this.calculateBreadcrumbItemsWidth(),this.visibleItems=0,this.adjustOverflow()}connectedCallback(){super.connectedCallback(),this.resizeObserver=new ResizeObserver(()=>{if(this.firstRender){this.firstRender=!1;return}this.adjustOverflow()}),this.resizeObserver.observe(this)}adjustOverflow(){let e=this.getBoundingClientRect().width;console.log({availableSpace:e})}disconnectedCallback(){this.resizeObserver?.unobserve(this),super.disconnectedCallback()}render(){return v`
      <nav class="breadcrumbs" aria-label="${this.label}">
        <slot @slotchange="${this.handleSlotChange}"></slot>
      </nav>

      <span hidden aria-hidden="true">
        <slot name="separator"><span class="separator">/</span></slot>
      </span>
    `}};jc.styles=[Ac],g([O(`slot`)],jc.prototype,`defaultSlot`,void 0),g([O(`slot[name="separator"]`)],jc.prototype,`separatorSlot`,void 0),g([ee({selector:`craft-breadcrumb-item`})],jc.prototype,`breadcrumbsElements`,void 0),g([T()],jc.prototype,`label`,void 0),g([E()],jc.prototype,`items`,void 0),g([E()],jc.prototype,`visibleItems`,void 0),customElements.get(`craft-breadcrumbs`)||customElements.define(`craft-breadcrumbs`,jc);var Mc=b`
  :host {
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
`,Nc=class extends at{constructor(){super(...arguments),this.renderType=`button`,this.rel=`noreferrer noopener`}setRenderType(){let e=this.defaultSlot.assignedElements({flatten:!0}).filter(e=>e.tagName.toLowerCase()===`wa-dropdown`).length>0;if(this.href){this.renderType=`link`;return}if(e){this.renderType=`dropdown`;return}this.renderType=`button`}hrefChanged(){this.setRenderType()}handleSlotChange(){this.setRenderType()}render(){return v`
      <span part="start" class="start">
        <slot name="start"></slot>
      </span>

      ${this.renderType===`link`?v`
            <a
              part="label"
              class="label label-link"
              href="${this.href}"
              target="${ss(this.target?this.target:void 0)}"
              rel=${ss(this.target?this.rel:void 0)}
            >
              <slot></slot>
            </a>
          `:``}
      ${this.renderType===`button`?v`
            <button part="label" type="button" class="label label-button">
              <slot @slotchange=${this.handleSlotChange}></slot>
            </button>
          `:``}
      ${this.renderType===`dropdown`?v`
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
    `}};Nc.css=Mc,A([O(`slot:not([name])`)],Nc.prototype,`defaultSlot`,2),A([E()],Nc.prototype,`renderType`,2),A([T()],Nc.prototype,`href`,2),A([T()],Nc.prototype,`target`,2),A([T()],Nc.prototype,`rel`,2),A([nt(`href`,{waitUntilFirstUpdate:!0})],Nc.prototype,`hrefChanged`,1),Nc=A([D(`wa-breadcrumb-item`)],Nc);var Pc=class extends Nc{static get styles(){return[Nc.styles,b`
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
      `]}};customElements.get(`craft-breadcrumb-item`)||customElements.define(`craft-breadcrumb-item`,Pc);var Fc=b`
  :host {
    --arrow-size: 0.375rem;
    --max-width: 25rem;
    --show-duration: 100ms;
    --hide-duration: 100ms;

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
    --popup-border-width: var(--wa-panel-border-width);
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
`,Ic=new Set,Lc=class extends at{constructor(){super(...arguments),this.anchor=null,this.placement=`top`,this.open=!1,this.distance=8,this.skidding=0,this.for=null,this.withoutArrow=!1,this.eventController=new AbortController,this.handleAnchorClick=()=>{this.open=!this.open},this.handleBodyClick=e=>{e.target.closest(`[data-popover="close"]`)&&(e.stopPropagation(),this.open=!1)},this.handleDocumentKeyDown=e=>{e.key===`Escape`&&this.open&&Er(this)&&(e.preventDefault(),e.stopPropagation(),this.open=!1,this.anchor&&typeof this.anchor.focus==`function`&&this.anchor.focus())},this.handleDocumentClick=e=>{this.anchor&&e.composedPath().includes(this.anchor)||e.composedPath().includes(this)||(this.open=!1)}}connectedCallback(){super.connectedCallback(),this.id||=Nr(`wa-popover-`),this.eventController.signal.aborted&&(this.eventController=new AbortController),this.for&&this.anchor&&(this.anchor=null,this.handleForChange())}disconnectedCallback(){super.disconnectedCallback(),document.removeEventListener(`keydown`,this.handleDocumentKeyDown),Tr(this),this.eventController.abort()}firstUpdated(){this.open&&(this.dialog.show(),this.popup.active=!0,this.popup.reposition())}updated(e){e.has(`open`)&&this.customStates.set(`open`,this.open)}async handleOpenChange(){if(this.open){let e=new Dr;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}Ic.forEach(e=>e.open=!1),document.addEventListener(`keydown`,this.handleDocumentKeyDown,{signal:this.eventController.signal}),document.addEventListener(`click`,this.handleDocumentClick,{signal:this.eventController.signal}),this.dialog.show(),this.popup.active=!0,Ic.add(this),wr(this),requestAnimationFrame(()=>{let e=this.querySelector(`[autofocus]`);e&&typeof e.focus==`function`?e.focus():this.dialog.focus()}),await Fr(this.popup.popup,`show-with-scale`),this.popup.reposition(),this.dispatchEvent(new Ar)}else{let e=new Or;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!0;return}document.removeEventListener(`keydown`,this.handleDocumentKeyDown),document.removeEventListener(`click`,this.handleDocumentClick),Ic.delete(this),Tr(this),await Fr(this.popup.popup,`hide-with-scale`),this.popup.active=!1,this.dialog.close(),this.dispatchEvent(new kr)}}handleForChange(){let e=this.getRootNode();if(!e)return;let t=this.for?e.getElementById(this.for):null,n=this.anchor;if(t===n)return;let{signal:r}=this.eventController;t&&t.addEventListener(`click`,this.handleAnchorClick,{signal:r}),n&&n.removeEventListener(`click`,this.handleAnchorClick),this.anchor=t,this.for&&!t&&console.warn(`A popover was assigned to an element with an ID of "${this.for}" but the element could not be found.`,this)}async handleOptionsChange(){this.hasUpdated&&(await this.updateComplete,this.popup.reposition())}async show(){if(!this.open)return this.open=!0,Pr(this,`wa-after-show`)}async hide(){if(this.open)return this.open=!1,Pr(this,`wa-after-hide`)}render(){return v`
      <dialog part="dialog" class="dialog">
        <wa-popup
          part="popup"
          exportparts="
            popup:popup__popup,
            arrow:popup__arrow
          "
          class=${o({popover:!0,"popover-open":this.open})}
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
    `}};Lc.css=Fc,Lc.dependencies={"wa-popup":j},A([O(`dialog`)],Lc.prototype,`dialog`,2),A([O(`.body`)],Lc.prototype,`body`,2),A([O(`wa-popup`)],Lc.prototype,`popup`,2),A([E()],Lc.prototype,`anchor`,2),A([T()],Lc.prototype,`placement`,2),A([T({type:Boolean,reflect:!0})],Lc.prototype,`open`,2),A([T({type:Number})],Lc.prototype,`distance`,2),A([T({type:Number})],Lc.prototype,`skidding`,2),A([T()],Lc.prototype,`for`,2),A([T({attribute:`without-arrow`,type:Boolean,reflect:!0})],Lc.prototype,`withoutArrow`,2),A([nt(`open`,{waitUntilFirstUpdate:!0})],Lc.prototype,`handleOpenChange`,1),A([nt(`for`)],Lc.prototype,`handleForChange`,1),A([nt([`distance`,`placement`,`skidding`])],Lc.prototype,`handleOptionsChange`,1),Lc=A([D(`wa-popover`)],Lc);var Rc=class extends Lc{static get styles(){return[Lc.styles,b`
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
      `]}};customElements.get(`craft-popover`)||customElements.define(`craft-popover`,Rc);var zc=new Set;function Bc(){let e=document.documentElement.clientWidth;return Math.abs(window.innerWidth-e)}function Vc(){let e=Number(getComputedStyle(document.body).paddingRight.replace(/px/,``));return isNaN(e)||!e?0:e}function Hc(e){if(zc.add(e),!document.documentElement.classList.contains(`wa-scroll-lock`)){let e=Bc()+Vc(),t=getComputedStyle(document.documentElement).scrollbarGutter;(!t||t===`auto`)&&(t=`stable`),e<2&&(t=``),document.documentElement.style.setProperty(`--wa-scroll-lock-gutter`,t),document.documentElement.classList.add(`wa-scroll-lock`),document.documentElement.style.setProperty(`--wa-scroll-lock-size`,`${e}px`)}}function Uc(e){zc.delete(e),zc.size===0&&(document.documentElement.classList.remove(`wa-scroll-lock`),document.documentElement.style.removeProperty(`--wa-scroll-lock-size`))}function Wc(e){return e.split(` `).map(e=>e.trim()).filter(e=>e!==``)}var Gc=b`
  :host {
    --size: 25rem;
    --spacing: var(--wa-space-l);
    --backdrop-filter: none;
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
    color: inherit;
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
    backdrop-filter: var(--backdrop-filter);
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
`,Kc=class extends at{constructor(){super(...arguments),this.localize=new Te(this),this.hasSlotController=new oc(this,`footer`,`header-actions`,`label`),this.open=!1,this.label=``,this.placement=`end`,this.withoutHeader=!1,this.lightDismiss=!0,this.withFooter=!1,this.handleDocumentKeyDown=e=>{e.key===`Escape`&&this.open&&Er(this)&&(e.preventDefault(),e.stopPropagation(),this.requestClose(this.drawer))}}firstUpdated(){this.open&&(this.addOpenListeners(),this.drawer.showModal(),Hc(this))}disconnectedCallback(){super.disconnectedCallback(),Uc(this),this.removeOpenListeners()}async requestClose(e){let t=new Or({source:e});if(this.dispatchEvent(t),t.defaultPrevented){this.open=!0,Fr(this.drawer,`pulse`);return}this.removeOpenListeners(),await Fr(this.drawer,`hide`),this.open=!1,this.drawer.close(),Uc(this);let n=this.originalTrigger;typeof n?.focus==`function`&&setTimeout(()=>n.focus()),this.dispatchEvent(new kr)}addOpenListeners(){document.addEventListener(`keydown`,this.handleDocumentKeyDown),wr(this)}removeOpenListeners(){document.removeEventListener(`keydown`,this.handleDocumentKeyDown),Tr(this)}handleDialogCancel(e){e.preventDefault(),!this.drawer.classList.contains(`hide`)&&e.target===this.drawer&&Er(this)&&this.requestClose(this.drawer)}handleDialogClick(e){let t=e.target.closest(`[data-drawer="close"]`);t&&(e.stopPropagation(),this.requestClose(t))}async handleDialogPointerDown(e){e.target===this.drawer&&(this.lightDismiss?this.requestClose(this.drawer):await Fr(this.drawer,`pulse`))}handleOpenChange(){this.open&&!this.drawer.open?this.show():this.drawer.open&&(this.open=!0,this.requestClose(this.drawer))}async show(){let e=new Dr;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}this.addOpenListeners(),this.originalTrigger=document.activeElement,this.open=!0,this.drawer.showModal(),Hc(this),requestAnimationFrame(()=>{let e=this.querySelector(`[autofocus]`);e&&typeof e.focus==`function`?e.focus():this.drawer.focus()}),await Fr(this.drawer,`show`),this.dispatchEvent(new Ar)}render(){let e=!this.withoutHeader,t=this.hasUpdated?this.hasSlotController.test(`footer`):this.withFooter;return v`
      <dialog
        part="dialog"
        class=${o({drawer:!0,open:this.open,top:this.placement===`top`,end:this.placement===`end`,bottom:this.placement===`bottom`,start:this.placement===`start`})}
        @cancel=${this.handleDialogCancel}
        @click=${this.handleDialogClick}
        @pointerdown=${this.handleDialogPointerDown}
      >
        ${e?v`
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

        ${t?v`
              <footer part="footer" class="footer">
                <slot name="footer"></slot>
              </footer>
            `:``}
      </dialog>
    `}};Kc.css=Gc,A([O(`.drawer`)],Kc.prototype,`drawer`,2),A([T({type:Boolean,reflect:!0})],Kc.prototype,`open`,2),A([T({reflect:!0})],Kc.prototype,`label`,2),A([T({reflect:!0})],Kc.prototype,`placement`,2),A([T({attribute:`without-header`,type:Boolean,reflect:!0})],Kc.prototype,`withoutHeader`,2),A([T({attribute:`light-dismiss`,type:Boolean})],Kc.prototype,`lightDismiss`,2),A([T({attribute:`with-footer`,type:Boolean})],Kc.prototype,`withFooter`,2),A([nt(`open`,{waitUntilFirstUpdate:!0})],Kc.prototype,`handleOpenChange`,1),Kc=A([D(`wa-drawer`)],Kc),document.addEventListener(`click`,e=>{let t=e.target.closest(`[data-drawer]`);if(t instanceof Element){let[e,n]=Wc(t.getAttribute(`data-drawer`)||``);if(e===`open`&&n?.length){let e=t.getRootNode().getElementById(n);e?.localName===`wa-drawer`?e.open=!0:console.warn(`A drawer with an ID of "${n}" could not be found in this document.`)}}}),document.addEventListener(`pointerdown`,()=>{});var qc=()=>({checkValidity(e){let t=e.input,n={message:``,isValid:!0,invalidKeys:[]};if(!t)return n;let r=!0;if(`checkValidity`in t&&(r=t.checkValidity()),r)return n;if(n.isValid=!1,`validationMessage`in t&&(n.message=t.validationMessage),!(`validity`in t))return n.invalidKeys.push(`customError`),n;for(let e in t.validity){if(e===`valid`)continue;let r=e;t.validity[r]&&n.invalidKeys.push(r)}return n}}),Jc=class extends Event{constructor(){super(`wa-invalid`,{bubbles:!0,cancelable:!1,composed:!0})}},Yc=()=>({observedAttributes:[`custom-error`],checkValidity(e){let t={message:``,isValid:!0,invalidKeys:[]};return e.customError&&(t.message=e.customError,t.isValid=!1,t.invalidKeys=[`customError`]),t}}),Xc=class extends at{constructor(){super(),this.name=null,this.disabled=!1,this.required=!1,this.assumeInteractionOn=[`input`],this.validators=[],this.valueHasChanged=!1,this.hasInteracted=!1,this.customError=null,this.emittedEvents=[],this.emitInvalid=e=>{e.target===this&&(this.hasInteracted=!0,this.dispatchEvent(new Jc))},this.handleInteraction=e=>{let t=this.emittedEvents;t.includes(e.type)||t.push(e.type),t.length===this.assumeInteractionOn?.length&&(this.hasInteracted=!0)},this.addEventListener(`invalid`,this.emitInvalid)}static get validators(){return[Yc()]}static get observedAttributes(){let e=new Set(super.observedAttributes||[]);for(let t of this.validators)if(t.observedAttributes)for(let n of t.observedAttributes)e.add(n);return[...e]}connectedCallback(){super.connectedCallback(),this.updateValidity(),this.assumeInteractionOn.forEach(e=>{this.addEventListener(e,this.handleInteraction)})}firstUpdated(...e){super.firstUpdated(...e),this.updateValidity()}willUpdate(e){if(e.has(`customError`)&&(this.customError||=null,this.setCustomValidity(this.customError||``)),e.has(`value`)||e.has(`disabled`)||e.has(`defaultValue`)){let e=this.value;if(Array.isArray(e)){if(this.name){let t=new FormData;for(let n of e)t.append(this.name,n);this.setValue(t,t)}}else this.setValue(e,e)}e.has(`disabled`)&&(this.customStates.set(`disabled`,this.disabled),(this.hasAttribute(`disabled`)||!this.matches(`:disabled`))&&this.toggleAttribute(`disabled`,this.disabled)),super.willUpdate(e),this.updateValidity()}get labels(){return this.internals.labels}getForm(){return this.internals.form}set form(e){e?this.setAttribute(`form`,e):this.removeAttribute(`form`)}get form(){return this.internals.form}get validity(){return this.internals.validity}get willValidate(){return this.internals.willValidate}get validationMessage(){return this.internals.validationMessage}checkValidity(){return this.updateValidity(),this.internals.checkValidity()}reportValidity(){return this.updateValidity(),this.hasInteracted=!0,this.internals.reportValidity()}get validationTarget(){return this.input||void 0}setValidity(...e){let t=e[0],n=e[1],r=e[2];r||=this.validationTarget,this.internals.setValidity(t,n,r||void 0),this.requestUpdate(`validity`),this.setCustomStates()}setCustomStates(){let e=!!this.required,t=this.internals.validity.valid,n=this.hasInteracted;this.customStates.set(`required`,e),this.customStates.set(`optional`,!e),this.customStates.set(`invalid`,!t),this.customStates.set(`valid`,t),this.customStates.set(`user-invalid`,!t&&n),this.customStates.set(`user-valid`,t&&n)}setCustomValidity(e){if(!e){this.customError=null,this.setValidity({});return}this.customError=e,this.setValidity({customError:!0},e,this.validationTarget)}formResetCallback(){this.resetValidity(),this.hasInteracted=!1,this.valueHasChanged=!1,this.emittedEvents=[],this.updateValidity()}formDisabledCallback(e){this.disabled=e,this.updateValidity()}formStateRestoreCallback(e,t){this.value=e,t===`restore`&&this.resetValidity(),this.updateValidity()}setValue(...e){let[t,n]=e;this.internals.setFormValue(t,n)}get allValidators(){let e=this.constructor.validators||[],t=this.validators||[];return[...e,...t]}resetValidity(){this.setCustomValidity(``),this.setValidity({})}updateValidity(){if(this.disabled||this.hasAttribute(`disabled`)||!this.willValidate){this.resetValidity();return}let e=this.allValidators;if(!e?.length)return;let t={customError:!!this.customError},n=this.validationTarget||this.input||void 0,r=``;for(let n of e){let{isValid:e,message:i,invalidKeys:a}=n.checkValidity(this);e||(r||=i,a?.length>=0&&a.forEach(e=>t[e]=!0))}r||=this.validationMessage,this.setValidity(t,r,n)}};Xc.formAssociated=!0,A([T({reflect:!0})],Xc.prototype,`name`,2),A([T({type:Boolean})],Xc.prototype,`disabled`,2),A([T({state:!0,attribute:!1})],Xc.prototype,`valueHasChanged`,2),A([T({state:!0,attribute:!1})],Xc.prototype,`hasInteracted`,2),A([T({attribute:`custom-error`,reflect:!0})],Xc.prototype,`customError`,2),A([T({attribute:!1,state:!0,type:Object})],Xc.prototype,`validity`,1);var Zc=b`
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
`,Qc=b`
  @layer wa-component {
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
    transition-property: background, border, box-shadow, color, opacity;
    transition-duration: var(--wa-transition-fast);
    transition-timing-function: var(--wa-transition-easing);
    cursor: pointer;
    padding: 0 var(--wa-form-control-padding-inline);
    font-family: inherit;
    font-size: inherit;
    font-weight: var(--wa-font-weight-action);
    line-height: calc(var(--wa-form-control-height) - var(--wa-form-control-border-width) * 2);
    height: var(--wa-form-control-height);
    width: 100%;

    background-color: var(--wa-color-fill-loud, var(--wa-color-neutral-fill-loud));
    border-color: transparent;
    color: var(--wa-color-on-loud, var(--wa-color-neutral-on-loud));
    border-start-start-radius: var(--_button-start-start-radius, var(--wa-form-control-border-radius));
    border-start-end-radius: var(--_button-start-end-radius, var(--wa-form-control-border-radius));
    border-end-start-radius: var(--_button-end-start-radius, var(--wa-form-control-border-radius));
    border-end-end-radius: var(--_button-end-end-radius, var(--wa-form-control-border-radius));
    border-style: var(--wa-form-control-border-style);
    border-width: var(--wa-form-control-border-width);
  }

  /* Appearance modifiers */
  :host([appearance='plain']) {
    /* Indentation overrides for grouping */
    margin-inline-start: var(--_button-horizontal-indent);
    margin-block-start: var(--_button-vertical-indent);

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
    /* Indentation overrides for grouping outlined */
    margin-inline-start: var(--_button-horizontal-indent-outlined);
    margin-block-start: var(--_button-vertical-indent-outlined);

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
    /* Indentation overrides for grouping */
    margin-inline-start: var(--_button-horizontal-indent);
    margin-block-start: var(--_button-vertical-indent);

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
    /* Indentation overrides for grouping outlined */
    margin-inline-start: var(--_button-horizontal-indent-outlined);
    margin-block-start: var(--_button-vertical-indent-outlined);

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
    /* Indentation overrides for grouping */
    margin-inline-start: var(--_button-horizontal-indent);
    margin-block-start: var(--_button-vertical-indent);

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
  :host([disabled]) {
    opacity: 0.5;
    cursor: not-allowed;

    /* When disabled, prevent mouse events from bubbling up from children */
    .button {
      pointer-events: none;
    }
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

  /* Icon buttons with a caret need to grow to fit both the icon and the caret */
  .button.is-icon-button.caret {
    width: auto;
    aspect-ratio: auto;
    min-width: var(--wa-form-control-height);
  }

  /* Pill modifier */
  :host([pill]) .button {
    border-start-start-radius: var(--_button-start-start-radius, var(--wa-border-radius-pill));
    border-start-end-radius: var(--_button-start-end-radius, var(--wa-border-radius-pill));
    border-end-start-radius: var(--_button-end-start-radius, var(--wa-border-radius-pill));
    border-end-end-radius: var(--_button-end-end-radius, var(--wa-border-radius-pill));
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
`,$c=Symbol.for(``),el=e=>{if(e?.r===$c)return e?._$litStatic$},tl=(e,...t)=>({_$litStatic$:t.reduce(((t,n,r)=>t+(e=>{if(e._$litStatic$!==void 0)return e._$litStatic$;throw Error(`Value passed to 'literal' function must be a 'literal' result: ${e}. Use 'unsafeStatic' to pass non-literal values, but\n            take care to ensure page security.`)})(n)+e[r+1]),e[0]),r:$c}),nl=new Map,rl=(e=>(t,...n)=>{let r=n.length,i,a,o=[],s=[],c,l=0,u=!1;for(;l<r;){for(c=t[l];l<r&&(a=n[l],i=el(a))!==void 0;)c+=i+t[++l],u=!0;l!==r&&s.push(a),o.push(c),l++}if(l===r&&o.push(t[r]),u){let e=o.join(`$$lit$$`);(t=nl.get(e))===void 0&&(o.raw=o,nl.set(e,t=o)),n=s}return e(t,...n)})(v),L=class extends Xc{constructor(){super(...arguments),this.assumeInteractionOn=[`click`],this.hasSlotController=new oc(this,`[default]`,`start`,`end`),this.localize=new Te(this),this.invalid=!1,this.isIconButton=!1,this.title=``,this.variant=`neutral`,this.appearance=`accent`,this.size=`m`,this.withCaret=!1,this.withStart=!1,this.withEnd=!1,this.disabled=!1,this.loading=!1,this.pill=!1,this.type=`button`}static get validators(){return[...super.validators,qc()]}handleSizeChange(){ec(this.localName,this.size)}constructLightDOMButton(){let e=document.createElement(`button`);for(let t of this.attributes)t.name!==`style`&&e.setAttribute(t.name,t.value);return e.type=this.type,e.style.position=`absolute !important`,e.style.width=`0 !important`,e.style.height=`0 !important`,e.style.clipPath=`inset(50%) !important`,e.style.overflow=`hidden !important`,e.style.whiteSpace=`nowrap !important`,this.name&&(e.name=this.name),e.value=this.value||``,e}handleClick(e){if(this.disabled||this.loading){e.preventDefault(),e.stopImmediatePropagation();return}if(this.type!==`submit`&&this.type!==`reset`||!this.getForm())return;let t=this.constructLightDOMButton();this.parentElement?.append(t),t.click(),t.remove()}handleInvalid(){this.dispatchEvent(new Jc)}handleLabelSlotChange(){let e=this.labelSlot.assignedNodes({flatten:!0}),t=!1,n=!1,r=!1,i=!1;[...e].forEach(e=>{if(e.nodeType===Node.ELEMENT_NODE){let r=e;r.localName===`wa-icon`?(n=!0,t||=r.label!==void 0):i=!0}else e.nodeType===Node.TEXT_NODE&&(e.textContent?.trim()||``).length>0&&(r=!0)}),this.isIconButton=n&&!r&&!i,this.customStates.set(`icon-button`,this.isIconButton),this.isIconButton&&!t&&console.warn(`Icon buttons must have a label for screen readers. Add <wa-icon label="..."> to remove this warning.`,this)}isButton(){return!this.href}isLink(){return!!this.href}handleDisabledChange(){this.customStates.set(`disabled`,this.disabled),this.updateValidity()}handleHrefChange(){this.customStates.set(`link`,this.isLink())}handleLoadingChange(){this.customStates.set(`loading`,this.loading)}setValue(...e){}click(){this.button.click()}focus(e){this.button.focus(e)}blur(){this.button.blur()}render(){let e=this.isLink(),t=e?tl`a`:tl`button`;return rl`
      <${t}
        part="base"
        class=${o({button:!0,caret:this.withCaret,disabled:this.disabled,loading:this.loading,rtl:this.localize.dir()===`rtl`,"has-label":this.hasSlotController.test(`[default]`),"has-start":this.hasUpdated?this.hasSlotController.test(`start`):this.withStart,"has-end":this.hasUpdated?this.hasSlotController.test(`end`):this.withEnd,"is-icon-button":this.isIconButton})}
        ?disabled=${ss(e?void 0:this.disabled)}
        type=${ss(e?void 0:this.type)}
        title=${this.title}
        name=${ss(e?void 0:this.name)}
        value=${ss(e?void 0:this.value)}
        href=${ss(e?this.href:void 0)}
        target=${ss(e?this.target:void 0)}
        download=${ss(e?this.download:void 0)}
        rel=${ss(e&&this.rel?this.rel:void 0)}
        role=${ss(e?void 0:`button`)}
        aria-disabled=${ss(e&&this.disabled?`true`:void 0)}
        tabindex=${this.disabled?`-1`:`0`}
        @invalid=${this.isButton()?this.handleInvalid:null}
        @click=${this.handleClick}
      >
        <slot name="start" part="start" class="start"></slot>
        <slot part="label" class="label" @slotchange=${this.handleLabelSlotChange}></slot>
        <slot name="end" part="end" class="end"></slot>
        ${this.withCaret?rl`
                <wa-icon part="caret" class="caret" library="system" name="chevron-down" variant="solid"></wa-icon>
              `:``}
        ${this.loading?rl`<wa-spinner part="spinner"></wa-spinner>`:``}
      </${t}>
    `}};L.shadowRootOptions={...Xc.shadowRootOptions,delegatesFocus:!0},L.css=[Qc,Zc,tc],A([O(`.button`)],L.prototype,`button`,2),A([O(`slot:not([name])`)],L.prototype,`labelSlot`,2),A([E()],L.prototype,`invalid`,2),A([E()],L.prototype,`isIconButton`,2),A([T()],L.prototype,`title`,2),A([T({reflect:!0})],L.prototype,`variant`,2),A([T({reflect:!0})],L.prototype,`appearance`,2),A([T({reflect:!0})],L.prototype,`size`,2),A([nt(`size`)],L.prototype,`handleSizeChange`,1),A([T({attribute:`with-caret`,type:Boolean,reflect:!0})],L.prototype,`withCaret`,2),A([T({attribute:`with-start`,type:Boolean})],L.prototype,`withStart`,2),A([T({attribute:`with-end`,type:Boolean})],L.prototype,`withEnd`,2),A([T({type:Boolean})],L.prototype,`disabled`,2),A([T({type:Boolean,reflect:!0})],L.prototype,`loading`,2),A([T({type:Boolean,reflect:!0})],L.prototype,`pill`,2),A([T()],L.prototype,`type`,2),A([T({reflect:!0})],L.prototype,`name`,2),A([T({reflect:!0})],L.prototype,`value`,2),A([T({reflect:!0})],L.prototype,`href`,2),A([T()],L.prototype,`target`,2),A([T()],L.prototype,`rel`,2),A([T()],L.prototype,`download`,2),A([T({attribute:`formaction`})],L.prototype,`formAction`,2),A([T({attribute:`formenctype`})],L.prototype,`formEnctype`,2),A([T({attribute:`formmethod`})],L.prototype,`formMethod`,2),A([T({attribute:`formnovalidate`,type:Boolean})],L.prototype,`formNoValidate`,2),A([T({attribute:`formtarget`})],L.prototype,`formTarget`,2),A([nt(`disabled`,{waitUntilFirstUpdate:!0})],L.prototype,`handleDisabledChange`,1),A([nt(`href`)],L.prototype,`handleHrefChange`,1),A([nt(`loading`,{waitUntilFirstUpdate:!0})],L.prototype,`handleLoadingChange`,1),L=A([D(`wa-button`)],L),L.disableWarning?.(`change-in-update`);var il=b`
  :host {
    --track-width: 2px;
    --track-color: var(--wa-color-neutral-fill-normal);
    --indicator-color: var(--wa-color-brand-fill-loud);
    --speed: 2s;
    --size: 1em;

    /*
      Resizing a spinner element using anything but font-size will break the animation because the animation uses em
      units. Therefore, if a spinner is used in a flex container without \`flex: none\` applied, the spinner can
      grow/shrink and break the animation. The use of \`flex: none\` on the host element prevents this by always having
      the spinner sized according to its actual dimensions.
    */
    flex: none;
    display: inline-flex;
    width: var(--size);
    height: var(--size);
  }

  svg {
    width: 100%;
    height: 100%;
    aspect-ratio: 1;
    animation: spin var(--speed) linear infinite;
  }

  .track,
  .indicator {
    --radius: calc(var(--size) / 2 - var(--track-width) / 2);
    --circumference: calc(var(--radius) * 2 * 3.141592654);

    cx: calc(var(--size) / 2);
    cy: calc(var(--size) / 2);
    r: var(--radius);
    fill: none;
    stroke-width: var(--track-width);
  }

  .track {
    stroke: var(--track-color);
  }

  .indicator {
    stroke: var(--indicator-color);
    stroke-linecap: round;
    stroke-dasharray: calc(0.597 * var(--circumference)), calc(0.796 * var(--circumference));
    stroke-dashoffset: calc(-0.04 * var(--circumference));
    animation: dash 1.5s ease-in-out infinite;
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
      stroke-dasharray: calc(0.008 * var(--circumference)), calc(1.194 * var(--circumference));
      stroke-dashoffset: 0;
    }
    50% {
      stroke-dasharray: calc(0.716 * var(--circumference)), calc(1.194 * var(--circumference));
      stroke-dashoffset: calc(-0.278 * var(--circumference));
    }
    100% {
      stroke-dasharray: calc(0.716 * var(--circumference)), calc(1.194 * var(--circumference));
      stroke-dashoffset: calc(-0.987 * var(--circumference));
    }
  }
`,al=class extends at{constructor(){super(...arguments),this.localize=new Te(this)}render(){return v`
      <svg
        part="base"
        role="progressbar"
        aria-label=${this.localize.term(`loading`)}
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
      >
        <circle class="track" />
        <circle class="indicator" />
      </svg>
    `}};al.css=il,al=A([D(`wa-spinner`)],al);var ol=class extends Kc{static get styles(){return[Kc.styles,b`
        :host {
          --wa-color-surface-raised: var(--c-surface-raised);
          --spacing: var(--c-spacing-lg);
        }
      `]}};customElements.get(`craft-drawer`)||customElements.define(`craft-drawer`,ol);var sl=b`
  :host {
    --width: 31rem;
    --spacing: var(--wa-space-l);
    --backdrop-filter: none;
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
    color: inherit;
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
    backdrop-filter: var(--backdrop-filter);
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
`,cl=class extends at{constructor(){super(...arguments),this.localize=new Te(this),this.hasSlotController=new oc(this,`footer`,`header-actions`,`label`),this.open=!1,this.label=``,this.withoutHeader=!1,this.lightDismiss=!1,this.withFooter=!1,this.handleDocumentKeyDown=e=>{e.key===`Escape`&&this.open&&Er(this)&&(e.preventDefault(),e.stopPropagation(),this.requestClose(this.dialog))}}firstUpdated(){this.open&&(this.addOpenListeners(),this.dialog.showModal(),Hc(this))}disconnectedCallback(){super.disconnectedCallback(),Uc(this),this.removeOpenListeners()}async requestClose(e){let t=new Or({source:e});if(this.dispatchEvent(t),t.defaultPrevented){this.open=!0,Fr(this.dialog,`pulse`);return}this.removeOpenListeners(),await Fr(this.dialog,`hide`),this.open=!1,this.dialog.close(),Uc(this);let n=this.originalTrigger;typeof n?.focus==`function`&&setTimeout(()=>n.focus()),this.dispatchEvent(new kr)}addOpenListeners(){document.addEventListener(`keydown`,this.handleDocumentKeyDown),wr(this)}removeOpenListeners(){document.removeEventListener(`keydown`,this.handleDocumentKeyDown),Tr(this)}handleDialogCancel(e){e.preventDefault(),!this.dialog.classList.contains(`hide`)&&e.target===this.dialog&&Er(this)&&this.requestClose(this.dialog)}handleDialogClick(e){let t=e.target.closest(`[data-dialog="close"]`);t&&(e.stopPropagation(),this.requestClose(t))}async handleDialogPointerDown(e){e.target===this.dialog&&(this.lightDismiss?this.requestClose(this.dialog):await Fr(this.dialog,`pulse`))}handleOpenChange(){this.open&&!this.dialog.open?this.show():!this.open&&this.dialog.open&&(this.open=!0,this.requestClose(this.dialog))}async show(){let e=new Dr;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}this.addOpenListeners(),this.originalTrigger=document.activeElement,this.open=!0,this.dialog.showModal(),Hc(this),requestAnimationFrame(()=>{let e=this.querySelector(`[autofocus]`);e&&typeof e.focus==`function`?e.focus():this.dialog.focus()}),await Fr(this.dialog,`show`),this.dispatchEvent(new Ar)}render(){let e=!this.withoutHeader,t=this.hasUpdated?this.hasSlotController.test(`footer`):this.withFooter;return v`
      <dialog
        part="dialog"
        class=${o({dialog:!0,open:this.open})}
        @cancel=${this.handleDialogCancel}
        @click=${this.handleDialogClick}
        @pointerdown=${this.handleDialogPointerDown}
      >
        ${e?v`
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

        ${t?v`
              <footer part="footer" class="footer">
                <slot name="footer"></slot>
              </footer>
            `:``}
      </dialog>
    `}};cl.css=sl,A([O(`.dialog`)],cl.prototype,`dialog`,2),A([T({type:Boolean,reflect:!0})],cl.prototype,`open`,2),A([T({reflect:!0})],cl.prototype,`label`,2),A([T({attribute:`without-header`,type:Boolean,reflect:!0})],cl.prototype,`withoutHeader`,2),A([T({attribute:`light-dismiss`,type:Boolean})],cl.prototype,`lightDismiss`,2),A([T({attribute:`with-footer`,type:Boolean})],cl.prototype,`withFooter`,2),A([nt(`open`,{waitUntilFirstUpdate:!0})],cl.prototype,`handleOpenChange`,1),cl=A([D(`wa-dialog`)],cl),document.addEventListener(`click`,e=>{let t=e.target.closest(`[data-dialog]`);if(t instanceof Element){let[e,n]=Wc(t.getAttribute(`data-dialog`)||``);if(e===`open`&&n?.length){let e=t.getRootNode().getElementById(n);e?.localName===`wa-dialog`?e.open=!0:console.warn(`A dialog with an ID of "${n}" could not be found in this document.`)}}}),document.addEventListener(`pointerdown`,()=>{});var ll=class extends cl{static get styles(){return[cl.styles,b`
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
      `]}};customElements.get(`craft-dialog`)||customElements.define(`craft-dialog`,ll);var ul=class extends Ko(Xo(w)){constructor(){super(),this.multipleChoice=!0}},dl=class extends Jo(Zo){connectedCallback(){super.connectedCallback(),this.type=`checkbox`}},fl=class extends dl{static get styles(){return[...super.styles||[],b`
        :host .choice-field__nested-checkboxes {
          display: block;
        }
        ::slotted(*) {
          padding-left: 8px;
        }
      `]}static get properties(){return{indeterminate:{type:Boolean,reflect:!0},mixedState:{type:Boolean,reflect:!0,attribute:`mixed-state`}}}get _checkboxGroupNode(){return this._parentFormGroup}get _subCheckboxes(){return this.__subCheckboxes}_storeIndeterminateState(){this._indeterminateSubStates=this._subCheckboxes.map(e=>e.checked)}_setOldState(){this.indeterminate?this._oldState=`indeterminate`:this._oldState=this.checked?`checked`:`unchecked`}_setOwnCheckedState(){let e=this._subCheckboxes;if(!e.length)return;this.__settingOwnChecked=!0;let t=e.filter(e=>e.checked);switch(e.length-t.length){case 0:this.indeterminate=!1,this.checked=!0;break;case e.length:this.indeterminate=!1,this.checked=!1;break;default:{this.indeterminate=!0;let n=e.filter(e=>e.disabled&&e.checked===!1);this.checked=e.length-t.length-n.length===0}}this.updateComplete.then(()=>{this.__settingOwnChecked=!1})}_setBasedOnMixedState(){switch(this._oldState){case`checked`:this.checked=!1,this.indeterminate=!1;break;case`unchecked`:this.checked=!1,this.indeterminate=!0;break;case`indeterminate`:this.checked=!0,this.indeterminate=!1;break}}__onModelValueChanged(e){if(!this.disabled){if(e.detail.formPath[0]===this&&!this.__settingOwnChecked){this.mixedState&&!(e=>e.every(t=>t===e[0]))(this._indeterminateSubStates)&&this._setBasedOnMixedState(),this.__settingOwnSubs=!0;let e=this._subCheckboxes,t=e.filter(e=>e.checked),n=e.filter(e=>e.disabled),r=e.length>0&&e.length===t.length;e.length>0&&e.length===n.length&&(this.checked=r),this.indeterminate&&this.mixedState?this._subCheckboxes.forEach((e,t)=>{e.checked=this._indeterminateSubStates[t]}):this._subCheckboxes.filter(e=>!e.disabled).forEach(e=>{e.checked=this._inputNode.checked}),this.updateComplete.then(()=>{this.__settingOwnSubs=!1})}else this._setOwnCheckedState(),this.updateComplete.then(()=>{!this.__settingOwnSubs&&!this.__settingOwnChecked&&this.mixedState&&this._storeIndeterminateState()});this.mixedState&&this._setOldState()}}_afterTemplate(){return v`
      <div class="choice-field__nested-checkboxes" role="list">
        <slot></slot>
      </div>
    `}_onRequestToAddFormElement(e){e.target.hasAttribute(`role`)||e.target?.setAttribute(`role`,`listitem`),this.__addToSubCheckboxes(e.detail.element),this._setOwnCheckedState()}_onRequestToRemoveFormElement(e){e.target.getAttribute(`role`)===`listitem`&&e.target?.removeAttribute(`role`),this.__removeFromSubCheckboxes(e.detail.element)}__addToSubCheckboxes(e){e!==this&&this.contains(e)&&this.__subCheckboxes.push(e)}__removeFromSubCheckboxes(e){let t=this.__subCheckboxes.indexOf(e);t!==-1&&this.__subCheckboxes.splice(t,1)}constructor(){super(),this.indeterminate=!1,this._onRequestToAddFormElement=this._onRequestToAddFormElement.bind(this),this.__onModelValueChanged=this.__onModelValueChanged.bind(this),this.__subCheckboxes=[],this._indeterminateSubStates=[],this.mixedState=!1}connectedCallback(){super.connectedCallback(),this.addEventListener(`model-value-changed`,this.__onModelValueChanged),this.addEventListener(`form-element-register`,this._onRequestToAddFormElement)}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener(`model-value-changed`,this.__onModelValueChanged),this.removeEventListener(`form-element-register`,this._onRequestToAddFormElement)}firstUpdated(e){super.firstUpdated(e),this._setOldState(),this.indeterminate&&this._storeIndeterminateState()}updated(e){super.updated(e),(e.has(`indeterminate`)||e.has(`checked`))&&(this._inputNode.indeterminate=this.indeterminate)}},pl=class extends ul{static get styles(){return[...ul.styles,b`
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
      `]}};customElements.get(`craft-checkbox-group`)||customElements.define(`craft-checkbox-group`,pl);var ml=class extends dl{static get styles(){return[...dl.styles,b`
        /* same as radio, potentially consolidate */
        :host {
          --_gap-x: var(--gap-x, var(--c-spacing-md));
          display: grid;
          align-items: center;
          gap: 0 var(--_gap-x);
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
          width: var(--c-size-control-2xs);
          height: var(--c-size-control-2xs);
        }

        .choice-field__help-text {
          font-size: 1em;
          color: var(--c-text-quiet);
          grid-area: help-text;
        }
      `]}};customElements.get(`craft-checkbox`)||customElements.define(`craft-checkbox`,ml);var hl=class extends fl{static get styles(){return[...fl.styles,b`
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
      `]}};customElements.get(`craft-checkbox-indeterminate`)||customElements.define(`craft-checkbox-indeterminate`,hl);var gl=b`
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
`,_l=b`
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
`,vl=class extends w{constructor(...e){super(...e),this.variant=l.Default,this.appearance=d.OutlineFill,this.title=``,this.icon=null,this.rounded=`all`,this.inline=!1}getDefaultIcon(){switch(this.variant){case l.Info:return`lightbulb`;case l.Success:return`circle-check`;case l.Warning:return`circle-exclamation`;case l.Danger:return`triangle-exclamation`;default:return null}}render(){return v`
      ${this.icon||this.querySelector(`[slot="icon"]`)?v`<slot name="icon" class="callout__icon">
            <craft-icon
              name="${this.getDefaultIcon()}"
              style="font-size: 0.9em"
            ></craft-icon>
          </slot>`:C}
      <div class="callout__body">
        <slot name="title" class="callout__title">${this.title}</slot>
        <div class="callout__description">
          <slot></slot>
        </div>
      </div>
    `}};vl.styles=[gl,_l],g([T({reflect:!0})],vl.prototype,`variant`,void 0),g([T({reflect:!0})],vl.prototype,`appearance`,void 0),g([T()],vl.prototype,`title`,void 0),g([T()],vl.prototype,`icon`,void 0),g([T({reflect:!0})],vl.prototype,`rounded`,void 0),g([T({reflect:!0,type:Boolean})],vl.prototype,`inline`,void 0),customElements.get(`craft-callout`)||customElements.define(`craft-callout`,vl);var yl=class extends w{constructor(){super(),this.alt=!1,this.shift=!1,this.os=`Unknown`,this.os=this.detectOS()}connectedCallback(){super.connectedCallback(),this.os===`Unknown`&&(this.os=this.detectOS())}detectOS(){let e=navigator.platform.toLowerCase();return e.includes(`mac`)||/iphone|ipad|ipod/.test(e)?`Mac`:e.includes(`win`)?`Windows`:e.includes(`linux`)?`Linux`:`Unknown`}renderShortcutPrefix(){switch(this.os){case`Mac`:return`${this.alt?`⌥`:``}${this.shift?`⇧`:``}⌘`;case`Linux`:return`Super+${this.alt?`Alt+`:``}${this.shift?`Shift+`:``}`;default:return`Ctrl+${this.alt?`Alt+`:``}${this.shift?`Shift+`:``}`}}render(){return v`<span class="shortcut"
      >${this.renderShortcutPrefix()}<slot></slot
    ></span>`}};yl.styles=b`
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
  `,g([T({type:Boolean})],yl.prototype,`alt`,void 0),g([T({type:Boolean})],yl.prototype,`shift`,void 0),g([T()],yl.prototype,`os`,void 0),customElements.get(`craft-shortcut`)||customElements.define(`craft-shortcut`,yl);async function bl(e){switch(e.type){case`clipboard`:await navigator.clipboard.writeText(e.value);break;case`http`:if(e.confirm&&!confirm(e.confirm))return;let t=await fetch(e.url,{method:e.method||`POST`,headers:{"Content-Type":`application/json`},body:e.body?JSON.stringify(e.body):void 0});if(!t.ok){let e=await t.json().catch(()=>({}));throw Error(e.message??`Request failed`)}break;case`event`:if(e.confirm&&!confirm(e.confirm))return;window.dispatchEvent(new CustomEvent(e.name,{detail:e.detail??{}}));break;case`download`:let n=document.createElement(`a`);n.href=e.url,n.download=e.filename??``,n.click();break;default:throw Error(`Unknown action type: ${e.type}`)}}var xl=b`
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

  craft-shortcut {
    margin-inline-start: var(--c-spacing-sm);
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
`,Sl=class extends w{constructor(...e){super(...e),this.icon=null,this.href=null,this.disabled=!1,this.variant=l.Default,this.checked=!1,this.active=!1,this.type=`button`,this.action=null,this.feedback=null,this.feedbackDuration=1e3,this.confirm=null,this.state=h.Idle,this.feedbackMessage=null,this.shortcut=null}renderShortcut(){return typeof this.shortcut==`string`?v`<craft-shortcut>${this.shortcut}</craft-shortcut>`:this.shortcut===null?C:v`<craft-shortcut
        ?alt="${this.shortcut.alt??!1}"
        ?shift="${this.shortcut.shift??!1}"
        >${this.shortcut.key}</craft-shortcut
      >`}connectedCallback(){super.connectedCallback(),this.addEventListener(`click`,this)}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener(`click`,this)}setState(e,t={}){this.state=e,this.feedbackMessage=t.message??null,this.dispatchEvent(new CustomEvent(`action:change-state`,{bubbles:!0,composed:!0,detail:{state:e,actionType:this.action?.type,...t}}))}async handleEvent(e){if(this.disabled){e.preventDefault();return}if(e.type===`click`&&this.action){this.action.type===`http`&&this.setState(h.Loading);try{await bl(this.action),this.setState(h.Success,this.feedback?.success)}catch(e){this.setState(h.Error,{message:e.message,...this.feedback?.error||{}})}finally{setTimeout(()=>{this.setState(h.Idle)},this.feedbackDuration)}}}renderCheckbox(){return v`<span class="action-item__check">
      <slot name="checkmark">
        ${this.checked?v`<craft-icon name="check"></craft-icon>`:C}
      </slot>
    </span>`}renderIcon(){switch(this.state){case h.Loading:return v`<craft-spinner style="--size: 0.8em"></craft-spinner>`;case h.Success:return v`<craft-icon
          name="check"
          style="color: var(--c-color-success-on-normal)"
        ></craft-icon>`;case h.Error:return v`<craft-icon
          name="xmark"
          style="color: var(--c-color-danger-on-normal)"
        ></craft-icon>`;default:return v`
          <slot name="icon">
            ${this.icon?v`<craft-icon name="${this.icon}"></craft-icon>`:C}
          </slot>
        `}}renderPrefix(){let e=!!this.querySelector(`[slot="icon"]`)||!!this.icon;return v`
      ${this.type===`checkbox`?this.renderCheckbox():C}
      ${e?v`<div class="action-item__icon">${this.renderIcon()}</div>`:C}
    `}renderBody(){return v`
      ${this.renderPrefix()}

      <span class="action-item__label">
        ${this.feedbackMessage?this.feedbackMessage:v`<slot></slot>`}
      </span>

      <span class="action-item__suffix">
        <slot name="suffix"></slot>
        ${this.shortcut?this.renderShortcut():C}
      </span>
    `}render(){return this.href?v`
          <a
            class="${o({"action-item":!0,"action-item--checkbox":this.type===`checkbox`})}"
            href="${this.href}"
          >
            ${this.renderBody()}
          </a>
        `:v`
          <button
            type="button"
            class="${o({"action-item":!0,"action-item--checkbox":this.type===`checkbox`})}"
            ?disabled="${this.disabled}"
          >
            ${this.renderBody()}
          </button>
        `}};Sl.styles=[gl,xl],g([T()],Sl.prototype,`icon`,void 0),g([T()],Sl.prototype,`href`,void 0),g([T({type:Boolean})],Sl.prototype,`disabled`,void 0),g([T({reflect:!0})],Sl.prototype,`variant`,void 0),g([T({type:Boolean})],Sl.prototype,`checked`,void 0),g([T({type:Boolean})],Sl.prototype,`active`,void 0),g([T()],Sl.prototype,`type`,void 0),g([T({type:Object})],Sl.prototype,`action`,void 0),g([T({type:Object})],Sl.prototype,`feedback`,void 0),g([T({type:Number})],Sl.prototype,`feedbackDuration`,void 0),g([T()],Sl.prototype,`confirm`,void 0),g([E()],Sl.prototype,`state`,void 0),g([E()],Sl.prototype,`feedbackMessage`,void 0),g([T({converter:{fromAttribute(e){if(e===null)return null;try{let t=JSON.parse(e);if(typeof t==`object`&&t)return t}catch{}return e},toAttribute(e){return e===null?null:typeof e==`string`?e:JSON.stringify(e)}}})],Sl.prototype,`shortcut`,void 0),customElements.get(`craft-action-item`)||customElements.define(`craft-action-item`,Sl);var Cl=b`
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
`,wl=class e{static __createGlobalStyleNode(){let e=document.createElement(`style`);return e.setAttribute(`data-overlays`,``),e.textContent=Cl.cssText,document.head.appendChild(e),e}get list(){return this.__list}get shownList(){return this.__shownList}constructor(){this.__list=[],this.__shownList=[],this._siblingsInert=!1,this.__blockingMap=new WeakMap,e.__globalStyleNode||=e.__createGlobalStyleNode()}add(e){if(this.list.find(t=>e===t))throw Error(`controller instance is already added`);return this.list.push(e),e}remove(e){if(!this.list.find(t=>e===t))throw Error(`could not find controller to remove`);this.__list=this.list.filter(t=>t!==e),this.__shownList=this.shownList.filter(t=>t!==e)}show(e){this.list.find(t=>e===t)&&this.hide(e),this.__shownList.unshift(e),Array.from(this.__shownList).reverse().forEach((e,t)=>{e.elevation=t+1})}hide(e){if(!this.list.find(t=>e===t))throw Error(`could not find controller to hide`);this.__shownList=this.shownList.filter(t=>t!==e)}teardown(){this.list.forEach(e=>{e.teardown()}),this.__list=[],this.__shownList=[],this._siblingsInert=!1,e.__globalStyleNode&&=(document.head.removeChild(e.__globalStyleNode),void 0)}get siblingsInert(){return this._siblingsInert}requestToPreventScroll(){let{isIOS:e,isMacSafari:t}=qr;document.body.classList.add(`overlays-scroll-lock`),(e||t)&&document.body.classList.add(`overlays-scroll-lock-ios-fix`),e&&document.documentElement.classList.add(`overlays-scroll-lock-ios-fix`)}requestToEnableScroll(e){if((e?this.shownList.filter(t=>t!==e):this.shownList).some(e=>e.preventsScroll===!0))return;let{isIOS:t,isMacSafari:n}=qr;document.body.classList.remove(`overlays-scroll-lock`),(t||n)&&document.body.classList.remove(`overlays-scroll-lock-ios-fix`),t&&document.documentElement.classList.remove(`overlays-scroll-lock-ios-fix`)}requestToShowOnly(e){let t=this.shownList.filter(t=>t!==e);t.forEach(e=>e.hide()),this.__blockingMap.set(e,t)}retractRequestToShowOnly(e){this.__blockingMap.has(e)&&this.__blockingMap.get(e).forEach(e=>e.show())}};wl.__globalStyleNode=void 0;function Tl(){if(!to.has(`@lion/ui::overlays::0.x`)){let e=new wl;to.set(`@lion/ui::overlays::0.x`,e)}return to.get(`@lion/ui::overlays::0.x`)}var El=eo(Tl);function Dl(e,t,n={}){function r(e){return`getAttribute`in e}function i(e){if(!r(e))return null;let t=e.getAttribute(`slot`),i=null;if(t){let r=n[t];r&&(i=r.filter(t=>t?.element===e)[0]||null)}return i}let a=i(e);if(a)return a.deepContains;function o(t){if(!r(e))return;let i=e.getAttribute(`slot`);i&&(n[i]=n[i]||[],n[i].push({element:e,deepContains:t}))}let s=e.contains(t);if(s)return o(!0),!0;function c(e){return e.tagName===`SLOT`}function l(e){return c(e)?e.assignedElements():[]}function u(e){return e.nodeType===Node.DOCUMENT_FRAGMENT_NODE}function d(e){let i=!1;for(let a=0;a<e.length;a+=1){let o=e[a];if(o&&(r(o)||u(o))&&Dl(o,t,n)){i=!0;break}}return i}function f(e){for(let t=0;t<e.children.length;t+=1){let n=e.children[t],r=i(n);if(r){s=r.deepContains||s;break}let a=l(n);if(d([n.shadowRoot,...a])){s=!0;break}n.children.length>0&&f(n)}}return e instanceof HTMLElement&&e.shadowRoot&&(s=Dl(e.shadowRoot,t,n),s)?(o(!0),!0):(f(e),o(s),s)}var Ol=b`
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
`,kl={supportsAdoptingStyleSheets:window.ShadowRoot&&(window.ShadyCSS===void 0||window.ShadyCSS.nativeShadow)&&`adoptedStyleSheets`in Document.prototype&&`replace`in CSSStyleSheet.prototype,adoptStyle:void 0,adoptStyles:void 0},Al=new WeakMap;function jl(e){return Array.from(e.cssRules).map(e=>e.cssText).join(``)}function Ml(e,t,{teardown:n=!1}={}){let r=e===document?document.body:e,i=t.cssText||jl(t);if(n){let e=Array.from(r.querySelectorAll(`style`));for(let t of e)if(t.textContent===i){t.remove();break}}else{let e=document.createElement(`style`),t=window.litNonce;t!==void 0&&e.setAttribute(`nonce`,t),e.textContent=i,r.appendChild(e)}}function Nl(e,t,{teardown:n=!1}={}){let r=!1;e&&!Al.has(e)&&Al.set(e,[]);let i=Al.get(e)??[],a=i.find(e=>t===e);return a&&n?i.splice(i.indexOf(t),1):!a&&!n?i.push(t):(a&&!n||!a&&n)&&(r=!0),{haltFurtherExecution:r}}function Pl(e,t,{teardown:n=!1}={}){let{haltFurtherExecution:r}=Nl(e,t,{teardown:n});if(r)return;if(!kl.supportsAdoptingStyleSheets||qr.isIOS){Ml(e,t,{teardown:n});return}let i=t instanceof CSSStyleSheet?t:t.styleSheet;if(!i)throw Error(`Please provide a CSSResultOrNative style`);n?e.adoptedStyleSheets.includes(i)&&e.adoptedStyleSheets.splice(e.adoptedStyleSheets.indexOf(i),1):e.adoptedStyleSheets=[...e.adoptedStyleSheets,i]}function Fl(e,t,{teardown:n=!1}={}){for(let r of t)kl.adoptStyle(e,r,{teardown:n})}kl.adoptStyle=Pl,kl.adoptStyles=Fl;var Il=({visibility:e,display:t})=>e!==`hidden`&&t!==`none`,Ll=({display:e})=>e===`contents`;function Rl(e){if(!e||!e.isConnected||!Il(e.style))return!1;let t=window.getComputedStyle(e);return Il(t)?Ll(t)?!0:!!(e.offsetWidth||e.offsetHeight||e.getClientRects().length):!1}function zl(e,t){let n=Math.max(e.tabIndex,0),r=Math.max(t.tabIndex,0);return n===0||r===0?r>n:n>r}function Bl(e,t){let n=[];for(;e.length>0&&t.length>0;)zl(e[0],t[0])?n.push(t.shift()):n.push(e.shift());return[...n,...e,...t]}function Vl(e){let t=e.length;if(t<2)return e;let n=Math.ceil(t/2);return Bl(Vl(e.slice(0,n)),Vl(e.slice(n)))}var Hl=`matches`in Element.prototype?`matches`:`msMatchesSelector`;function Ul(e){return e[Hl](`input, select, textarea, button, object`)?e[Hl](`:not([disabled])`):e[Hl](`a[href], area[href], iframe, [tabindex], [contentEditable]`)}function Wl(e){return Ul(e)?Number(e.getAttribute(`tabindex`)||0):-1}function Gl(e){if(e.localName===`slot`)return e.assignedNodes({flatten:!0});let{children:t}=e.shadowRoot||e;return t||[]}function Kl(e){return e.nodeType===Node.ELEMENT_NODE?e.localName===`slot`?!0:Rl(e):!1}function ql(e,t){if(!Kl(e))return!1;let n=e,r=Wl(n),i=r>0;r>=0&&t.push(n);let a=Gl(n);for(let e=0;e<a.length;e+=1)i=ql(a[e],t)||i;return i}function Jl(e){let t=[];return ql(e,t)?Vl(t):t}function Yl({wrappingDialogNodeL1:e,contentWrapperNodeL2:t,contentNodeL3:n}){if(!(t.isConnected||n.isConnected))throw Error(`[OverlayController] Could not find a render target, since the provided contentNode is not connected to the DOM. Make sure that it is connected, e.g. by doing "document.body.appendChild(contentNode)", before passing it on.`);let r,i=document.createComment(`tempMarker`);t.isConnected?(r=t.parentElement||t.getRootNode(),r.insertBefore(i,t),e.appendChild(t)):n.assignedSlot?(r=n.assignedSlot.parentElement||n.assignedSlot.getRootNode(),r.insertBefore(i,n.assignedSlot),e.appendChild(t),t.appendChild(n.assignedSlot)):(r=n.parentElement||n.getRootNode(),r.insertBefore(i,n),e.appendChild(t),t.appendChild(n)),r.insertBefore(e,i),r?.removeChild(i)}async function Xl(){return k(()=>import(`./popper-qZivCu9g.js`),[],import.meta.url)}var Zl=new WeakMap,Ql=class e extends EventTarget{#e=!1;constructor(e={},t=El){super(),this.manager=t,this.__sharedConfig=e,this.__activeElementRightBeforeHide=null,this.config={},this._defaultConfig={placementMode:void 0,contentNode:e.contentNode,contentWrapperNode:e.contentWrapperNode,invokerNode:e.invokerNode,backdropNode:e.backdropNode,referenceNode:void 0,elementToFocusAfterHide:e.invokerNode,inheritsReferenceWidth:`none`,hasBackdrop:!1,isBlocking:!1,preventsScroll:!1,trapsKeyboardFocus:!1,hidesOnEsc:!1,hidesOnOutsideEsc:!1,hidesOnOutsideClick:!1,isTooltip:!1,isAlertDialog:!1,invokerRelation:`description`,visibilityTriggerFunction:void 0,handlesAccessibility:!1,popperConfig:{placement:`top`,strategy:`fixed`,modifiers:[{name:`preventOverflow`,enabled:!0,options:{boundariesElement:`viewport`,padding:8}},{name:`flip`,options:{boundariesElement:`viewport`,padding:16}},{name:`offset`,enabled:!0,options:{offset:[0,8]}},{name:`arrow`,enabled:!1}]},viewportConfig:{placement:`center`},zIndex:9999},this._contentId=`overlay-content--${Math.random().toString(36).slice(2,10)}`,this.__originalAttrs=new Map,this.__escKeyHandler=this.__escKeyHandler.bind(this),this.updateConfig(e),this.__hasActiveBackdrop=!0,this.__cancelHandler=this.__cancelHandler.bind(this),this.__escKeyHandlerCalled=!1}get invoker(){return this.invokerNode}get content(){return this.__wrappingDialogNode}get placementMode(){return this.config?.placementMode}get invokerNode(){return this.config?.invokerNode}get referenceNode(){return this.config?.referenceNode}get contentNode(){return this.config?.contentNode}get contentWrapperNode(){return this.__contentWrapperNode||this.config?.contentWrapperNode}get backdropNode(){return this.__backdropNode||this.config?.backdropNode}get elementToFocusAfterHide(){return this.__elementToFocusAfterHide||this.config?.elementToFocusAfterHide}get hasBackdrop(){return!!this.backdropNode||this.config?.hasBackdrop}get isBlocking(){return this.config?.isBlocking}get preventsScroll(){return this.config?.preventsScroll}get trapsKeyboardFocus(){return this.config?.trapsKeyboardFocus}get hidesOnEsc(){return this.config?.hidesOnEsc}get hidesOnOutsideClick(){return this.config?.hidesOnOutsideClick}get hidesOnOutsideEsc(){return this.config?.hidesOnOutsideEsc}get inheritsReferenceWidth(){return this.config?.inheritsReferenceWidth}get handlesAccessibility(){return this.config?.handlesAccessibility}get isTooltip(){return this.config?.isTooltip}get isAlertDialog(){return this.config?.isAlertDialog}get invokerRelation(){return this.config?.invokerRelation}get popperConfig(){return this.config?.popperConfig}get viewportConfig(){return this.config?.viewportConfig}get visibilityTriggerFunction(){return this.config?.visibilityTriggerFunction}get _referenceNode(){return this.referenceNode||this.invokerNode}set elevation(e){this.__wrappingDialogNode.style.zIndex=`${this.config.zIndex+e}`}get elevation(){return Number(this.contentWrapperNode?.style.zIndex)}updateConfig(e){this.teardown(),this.__prevConfig=this.config,this.config={...this._defaultConfig,...this.__sharedConfig,...e,popperConfig:{...this._defaultConfig.popperConfig||{},...this.__sharedConfig.popperConfig||{},...e.popperConfig||{},modifiers:[...this._defaultConfig.popperConfig?.modifiers||[],...this.__sharedConfig.popperConfig?.modifiers||[],...e.popperConfig?.modifiers||[]]}},this.__validateConfiguration(this.config),this._init(),this.__elementToFocusAfterHide=void 0,this.#t()||this.manager.add(this)}#t(){return!!this.manager.list.find(e=>this===e)}__validateConfiguration(e){if(!e.placementMode)throw Error(`[OverlayController] You need to provide a .placementMode ("global"|"local")`);if(![`global`,`local`].includes(e.placementMode))throw Error(`[OverlayController] "${e.placementMode}" is not a valid .placementMode, use ("global"|"local")`);if(!e.contentNode)throw Error(`[OverlayController] You need to provide a .contentNode`);if(e.isTooltip&&!e.handlesAccessibility)throw Error(`[OverlayController] .isTooltip only takes effect when .handlesAccessibility is enabled`)}_init(){this.__contentHasBeenInitialized||=(this.__initContentDomStructure(),!0),this.contentWrapperNode.removeAttribute(`style`),this.contentWrapperNode.removeAttribute(`class`),this.placementMode===`local`&&(e.popperModule||=Xl()),this.__handleOverlayStyles({phase:`init`}),this._handleFeatures({phase:`init`})}__handleOverlayStyles({phase:e}){let t=this.contentWrapperNode?.getRootNode();e===`init`?kl.adoptStyle(t,Ol):e===`teardown`&&kl.adoptStyle(t,Ol,{teardown:!0})}__initContentDomStructure(){let e=document.createElement(`dialog`);e.setAttribute(`role`,`none`),e.setAttribute(`data-overlay-outer-wrapper`,``),e.style.cssText=`display:none; z-index: ${this.config.zIndex}; padding: 0;`,this.__wrappingDialogNode=e,this.config?.contentWrapperNode||(this.__contentWrapperNode=document.createElement(`div`)),this.contentWrapperNode.setAttribute(`data-id`,`content-wrapper`),Yl({wrappingDialogNodeL1:e,contentWrapperNodeL2:this.contentWrapperNode,contentNodeL3:this.contentNode}),e.open=!0,this.isTooltip&&e.setAttribute(`tabindex`,`-1`),this.__wrappingDialogNode.style.display=`none`,this.contentWrapperNode.style.zIndex=`1`,getComputedStyle(this.contentNode).position===`absolute`&&(this.contentNode.style.position=`static`),HTMLDialogElement&&`closedBy`in HTMLDialogElement.prototype?e.closedBy=`none`:(e.addEventListener(`keydown`,e=>{e.key===`Escape`&&e.preventDefault()}),e.addEventListener(`keyup`,e=>{e.key===`Escape`&&e.preventDefault()}),e.addEventListener(`cancel`,e=>{e.stopPropagation()}),e.addEventListener(`close`,e=>{e.stopPropagation()}))}_handleZIndex({phase:e}){if(this.placementMode===`local`&&e===`setup`){let e=Number(getComputedStyle(this.contentNode).zIndex);(e<1||Number.isNaN(e))&&(this.contentNode.style.zIndex=`1`)}}__setupTeardownAccessibility({phase:e}){if(e===`init`){this.__storeOriginalAttrs(this.contentNode,[`role`,`id`]);let e=this.trapsKeyboardFocus;if(this.invokerNode){let t=[`aria-labelledby`,`aria-describedby`];e||t.push(`aria-expanded`),this.__storeOriginalAttrs(this.invokerNode,t)}this.contentNode.id||this.contentNode.setAttribute(`id`,this._contentId),this.isTooltip?(this.invokerNode&&this.invokerNode.setAttribute(this.invokerRelation===`label`?`aria-labelledby`:`aria-describedby`,this._contentId),this.contentNode.setAttribute(`role`,`tooltip`)):(this.invokerNode&&!e&&this.invokerNode.setAttribute(`aria-expanded`,`${this.isShown}`),this.isAlertDialog?this.contentNode.setAttribute(`role`,`alertdialog`):this.contentNode.getAttribute(`role`)||this.contentNode.setAttribute(`role`,`dialog`))}else e===`teardown`&&this.__restoreOriginalAttrs()}__storeOriginalAttrs(e,t){let n={};t.forEach(t=>{n[t]=e.getAttribute(t)}),this.__originalAttrs.set(e,n)}__restoreOriginalAttrs(){for(let[e,t]of this.__originalAttrs)Object.entries(t).forEach(([t,n])=>{n===null?e.removeAttribute(t):e.setAttribute(t,n)});this.__originalAttrs.clear()}get isShown(){return this.__wrappingDialogNode?.style.display!==`none`}async show(e=this.elementToFocusAfterHide){if(this._showComplete&&await this._showComplete,this._showComplete=new Promise(e=>{this._showResolve=e}),this.manager&&this.manager.show(this),this.isShown){this._showResolve();return}let t=new CustomEvent(`before-show`,{cancelable:!0});this.dispatchEvent(t),t.defaultPrevented||(`HTMLDialogElement`in window&&this.__wrappingDialogNode instanceof HTMLDialogElement&&(this.__wrappingDialogNode.open=!0),this.__wrappingDialogNode.style.display=``,this._keepBodySize({phase:`before-show`}),await this._handleFeatures({phase:`show`}),this._keepBodySize({phase:`show`}),await this._handlePosition({phase:`show`}),this.__elementToFocusAfterHide=e,this.dispatchEvent(new Event(`show`)),await this._transitionShow({backdropNode:this.backdropNode,contentNode:this.contentNode})),this._showResolve()}async _handlePosition({phase:e}){if(this.placementMode===`global`){let t=`overlays__overlay-container--${this.viewportConfig.placement}`;e===`show`?(this.contentWrapperNode.classList.add(`overlays__overlay-container`),this.contentWrapperNode.classList.add(t),this.contentNode.classList.add(`overlays__overlay`)):e===`hide`&&(this.contentWrapperNode.classList.remove(`overlays__overlay-container`),this.contentWrapperNode.classList.remove(t),this.contentNode.classList.remove(`overlays__overlay`))}else this.placementMode===`local`&&e===`show`&&(await this.__createPopperInstance(),this._popper.forceUpdate())}_keepBodySize({phase:e}){if(this.preventsScroll)switch(e){case`before-show`:this.__bodyClientWidth=document.body.clientWidth,this.__bodyClientHeight=document.body.clientHeight,this.__bodyMarginRightInline=document.body.style.marginRight,this.__bodyMarginBottomInline=document.body.style.marginBottom;break;case`show`:{if(window.getComputedStyle){let e=window.getComputedStyle(document.body);this.__bodyMarginRight=parseInt(e.getPropertyValue(`margin-right`),10),this.__bodyMarginBottom=parseInt(e.getPropertyValue(`margin-bottom`),10)}else this.__bodyMarginRight=0,this.__bodyMarginBottom=0;let e=document.body.clientWidth-this.__bodyClientWidth,t=document.body.clientHeight-this.__bodyClientHeight,n=this.__bodyMarginRight+e,r=this.__bodyMarginBottom+t;window.CSS?.number&&document.body.attributeStyleMap?.set?(document.body.attributeStyleMap.set(`margin-right`,CSS.px(n)),document.body.attributeStyleMap.set(`margin-bottom`,CSS.px(r))):(document.body.style.marginRight=`${n}px`,document.body.style.marginBottom=`${r}px`);break}case`hide`:document.body.style.marginRight=this.__bodyMarginRightInline||``,document.body.style.marginBottom=this.__bodyMarginBottomInline||``;break}}async hide(){if(this._hideComplete=new Promise(e=>{this._hideResolve=e}),this.__activeElementRightBeforeHide=this.contentNode.getRootNode().activeElement,this.manager&&this.#t()&&this.manager.hide(this),!this.isShown){this._hideResolve();return}let e=new CustomEvent(`before-hide`,{cancelable:!0});this.dispatchEvent(e),e.defaultPrevented||(await this._transitionHide({backdropNode:this.backdropNode,contentNode:this.contentNode}),`HTMLDialogElement`in window&&this.__wrappingDialogNode instanceof HTMLDialogElement&&this.__wrappingDialogNode.close(),this.__wrappingDialogNode.style.display=`none`,this._handleFeatures({phase:`hide`}),this._keepBodySize({phase:`hide`}),this.dispatchEvent(new Event(`hide`)),this._restoreFocus()),this._hideResolve()}async transitionHide(e){}async _transitionHide({backdropNode:e,contentNode:t}){await this.transitionHide({backdropNode:e,contentNode:t}),this._handlePosition({phase:`hide`}),e&&e.classList.remove(`overlays__backdrop--animation-in`)}async transitionShow(e){}async _transitionShow(e){await this.transitionShow({backdropNode:this.backdropNode,contentNode:this.contentNode}),e.backdropNode&&e.backdropNode.classList.add(`overlays__backdrop--animation-in`)}_restoreFocus(){this.__activeElementRightBeforeHide instanceof HTMLElement&&this.contentNode.contains(this.__activeElementRightBeforeHide)&&(this.elementToFocusAfterHide instanceof HTMLElement?(this.elementToFocusAfterHide.focus(),this.elementToFocusAfterHide.scrollIntoView({block:`nearest`})):this.__activeElementRightBeforeHide.blur())}async toggle(){return this.isShown?this.hide():this.show()}_handleFeatures({phase:e}){this._handleZIndex({phase:e}),this.preventsScroll&&this._handlePreventsScroll({phase:e}),this.isBlocking&&this._handleBlocking({phase:e}),this.hasBackdrop&&this._handleBackdrop({phase:e}),this.trapsKeyboardFocus&&this._handleTrapsKeyboardFocus({phase:e}),this.hidesOnEsc&&this._handleHidesOnEsc({phase:e}),this.hidesOnOutsideEsc&&this._handleHidesOnOutsideEsc({phase:e}),this.hidesOnOutsideClick&&this._handleHidesOnOutsideClick({phase:e}),this.handlesAccessibility&&this._handleAccessibility({phase:e}),this.inheritsReferenceWidth&&this._handleInheritsReferenceWidth(),this.visibilityTriggerFunction&&this._handleVisibilityTriggers({phase:e})}_handleVisibilityTriggers({phase:e}){typeof this.visibilityTriggerFunction==`function`&&(e===`init`&&(this.__visibilityTriggerHandler=this.visibilityTriggerFunction({phase:e,controller:this})),this.__visibilityTriggerHandler[e]&&this.__visibilityTriggerHandler[e]())}_handlePreventsScroll({phase:e}){switch(e){case`show`:this.manager.requestToPreventScroll();break;case`hide`:this.manager.requestToEnableScroll();break;case`teardown`:this.manager.requestToEnableScroll(this);break}}_handleBlocking({phase:e}){switch(e){case`show`:this.manager.requestToShowOnly(this);break;case`hide`:this.manager.retractRequestToShowOnly(this);break}}get hasActiveBackdrop(){return this.__hasActiveBackdrop}_handleBackdrop({phase:e}){switch(e){case`init`:this.__backdropInitialized||=(this.config?.backdropNode||(this.__backdropNode=document.createElement(`div`),this.__backdropNode.classList.add(`overlays__backdrop`)),this.__wrappingDialogNode.prepend(this.backdropNode),!0);break;case`show`:this.config.hasBackdrop&&this.backdropNode.classList.add(`overlays__backdrop--visible`),this.__hasActiveBackdrop=!0;break;case`hide`:case`teardown`:this.backdropNode.classList.remove(`overlays__backdrop--visible`),this.__hasActiveBackdrop=!1;break}}#n=e=>{e.key===`Shift`&&(this.#e=!0)};#r=e=>{e.key===`Shift`&&(this.#e=!1)};#i=()=>{window.addEventListener(`keydown`,this.#n),window.addEventListener(`keyup`,this.#r)};#a=()=>{window.removeEventListener(`keydown`,this.#n),window.removeEventListener(`keyup`,this.#r)};#o=()=>Jl(this.contentNode).find(e=>e.hasAttribute(`autofocus`))||this.contentNode;#s=()=>{this.__wrappingDialogNode?.addEventListener(`focus`,()=>{this.#e||this.#o().focus()})};_handleTrapsKeyboardFocus({phase:e}){e===`init`&&(this.contentNode.style.outline=`none`,this.contentNode.tabIndex=-1,this.contentNode.shadowRoot&&console.warn(`[overlays]: For best accessibility (compatibility with Safari + VoiceOver), provide a contentNode that is not a host for a shadow root`)),e===`show`&&(this.#i(),this.#s(),this.__wrappingDialogNode?.close(),this.__wrappingDialogNode?.showModal(),this.#o().focus()),e===`hide`&&this.#a()}__cancelHandler(e){e.preventDefault()}__escKeyHandler(e){e.key!==`Escape`||Zl.has(e)||!this.isShown&&this.__escKeyHandlerCalled||this.#c(e)&&(this.__escKeyHandlerCalled=!0,this.hide(),Zl.set(e,this))}#c=e=>e.composedPath().includes(this.__wrappingDialogNode)||this.invokerNode&&e.composedPath().includes(this.invokerNode)||Dl(this.contentNode,e.target);#l=e=>{e.key===`Escape`&&(this.#c(e)||this.hide())};_handleHidesOnEsc({phase:e}){e===`init`&&(this.contentNode.removeEventListener(`keyup`,this.__escKeyHandler),this.contentNode.addEventListener(`keyup`,this.__escKeyHandler),this.invokerNode&&this.invokerNode.addEventListener(`keyup`,this.__escKeyHandler)),e===`show`&&(this.__escKeyHandlerCalled=!1),e===`teardown`&&(this.contentNode.removeEventListener(`keyup`,this.__escKeyHandler),this.invokerNode&&this.invokerNode.removeEventListener(`keyup`,this.__escKeyHandler))}_handleHidesOnOutsideEsc({phase:e}){e===`init`?(document.removeEventListener(`keyup`,this.#l),document.addEventListener(`keyup`,this.#l)):e===`teardown`&&document.removeEventListener(`keyup`,this.#l)}_handleInheritsReferenceWidth(){if(!this._referenceNode||this.placementMode===`global`)return;let e=`${this._referenceNode.getBoundingClientRect().width}px`;switch(this.inheritsReferenceWidth){case`max`:this.contentWrapperNode.style.maxWidth=e;break;case`full`:this.contentWrapperNode.style.width=e;break;case`min`:this.contentWrapperNode.style.minWidth=e,this.contentWrapperNode.style.width=`auto`;break}}_handleHidesOnOutsideClick({phase:e}){let t=e===`show`?`addEventListener`:`removeEventListener`;if(e===`show`){let e=!1,t=!1;this.__onInsideMouseDown=()=>{e=!0},this.__onInsideMouseUp=()=>{t=!0},this.__onDocumentMouseUp=()=>{setTimeout(()=>{!e&&!t&&this.hide(),e=!1,t=!1})},this.__onWindowBlur=()=>{setTimeout(()=>{this.hide()})}}this.contentWrapperNode[t](`mousedown`,this.__onInsideMouseDown,!0),this.contentWrapperNode[t](`mouseup`,this.__onInsideMouseUp,!0),this.invokerNode&&(this.invokerNode[t](`mousedown`,this.__onInsideMouseDown,!0),this.invokerNode[t](`mouseup`,this.__onInsideMouseUp,!0)),document.documentElement[t](`mouseup`,this.__onDocumentMouseUp,!0),window[t](`blur`,this.__onWindowBlur)}_handleAccessibility({phase:e}){(e===`init`||e===`teardown`)&&this.__setupTeardownAccessibility({phase:e});let t=this.trapsKeyboardFocus;this.invokerNode&&!this.isTooltip&&!t&&this.invokerNode.setAttribute(`aria-expanded`,`${e===`show`}`)}teardown(){this.__handleOverlayStyles({phase:`teardown`}),this._handleFeatures({phase:`teardown`}),this.#t()&&this.manager.remove(this)}async __createPopperInstance(){if(this._popper&&=(this._popper.destroy(),void 0),e.popperModule!==void 0){let{createPopper:t}=await e.popperModule;this._popper=t(this._referenceNode,this.contentWrapperNode,{...this.config?.popperConfig})}}_hasDisabledInvoker(){return this.invokerNode?this.invokerNode.disabled||this.invokerNode.getAttribute(`aria-disabled`)===`true`:!1}};Ql.popperModule=void 0;function $l(e,t){if(typeof e!=`object`||typeof t!=`object`||e===null||t===null)return e===t;let n=Object.keys(e),r=Object.keys(t);return n.length===r.length?n.every(n=>$l(e[n],t[n])):!1}var eu=Br(e=>class extends e{static get properties(){return{opened:{type:Boolean,reflect:!0}}}#e=!1;constructor(){super(),this.opened=!1,this.config={},this.toggle=this.toggle.bind(this),this.open=this.open.bind(this),this.close=this.close.bind(this)}get config(){return this.__config}set config(e){let t=!$l(this.config,e);this._overlayCtrl&&t&&this._overlayCtrl.updateConfig(e),this.__config=e,this._overlayCtrl&&t&&this.__syncToOverlayController()}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`opened`&&this.opened!==t&&this.dispatchEvent(new CustomEvent(`opened-changed`,{detail:{opened:this.opened}}))}_defineOverlay({contentNode:e,invokerNode:t,referenceNode:n,backdropNode:r,contentWrapperNode:i}){let a=this._defineOverlayConfig()||{};return new Ql({contentNode:e,invokerNode:t,referenceNode:n,backdropNode:r,contentWrapperNode:i,...a,...this.config,popperConfig:{...a.popperConfig||{},...this.config?.popperConfig||{},modifiers:[...a.popperConfig?.modifiers||[],...this.config?.popperConfig?.modifiers||[]]}})}_defineOverlayConfig(){return{placementMode:`local`}}updated(e){super.updated(e),e.has(`opened`)&&this._overlayCtrl&&!this.__blockSyncToOverlayCtrl&&this.__syncToOverlayController()}_setupOpenCloseListeners(){this.__closeEventInContentNodeHandler=e=>{e.stopPropagation(),this._overlayCtrl.hide()},this._overlayContentNode&&this._overlayContentNode.addEventListener(`close-overlay`,this.__closeEventInContentNodeHandler)}_teardownOpenCloseListeners(){this._overlayContentNode&&this._overlayContentNode.removeEventListener(`close-overlay`,this.__closeEventInContentNodeHandler)}connectedCallback(){super.connectedCallback(),this.updateComplete.then(()=>{this.isConnected&&(this.#e||=(this._setupOverlayCtrl(),!0))})}async disconnectedCallback(){super.disconnectedCallback(),await this._isPermanentlyDisconnected()&&(this._teardownOverlayCtrl(),this.#e=!1)}static enabledWarnings=super.enabledWarnings?.filter(e=>e!==`change-in-update`)||[];get _overlayInvokerNode(){return Array.from(this.children).find(e=>e.slot===`invoker`)}get _overlayReferenceNode(){}get _overlayBackdropNode(){return this.__cachedOverlayBackdropNode||=Array.from(this.children).find(e=>e.slot===`backdrop`),this.__cachedOverlayBackdropNode}get _overlayContentNode(){return this._cachedOverlayContentNode||=Array.from(this.children).find(e=>e.slot===`content`)||this.config.contentNode,this._cachedOverlayContentNode}get _overlayContentWrapperNode(){return this.shadowRoot?.querySelector(`#overlay-content-node-wrapper`)}_setupOverlayCtrl(){if(this.#e)return;let e={contentNode:this._overlayContentNode,contentWrapperNode:this._overlayContentWrapperNode,invokerNode:this._overlayInvokerNode,referenceNode:this._overlayReferenceNode,backdropNode:this._overlayBackdropNode};this._overlayCtrl?this._overlayCtrl.updateConfig(e):this._overlayCtrl=this._defineOverlay(e),this.__syncToOverlayController(),this.__setupSyncFromOverlayController(),this._setupOpenCloseListeners()}_teardownOverlayCtrl(){this._overlayCtrl&&(this._teardownOpenCloseListeners(),this.__teardownSyncFromOverlayController(),this._overlayCtrl.teardown())}async _setOpenedWithoutPropertyEffects(e){this.__blockSyncToOverlayCtrl=!0,this.opened=e,await this.updateComplete,this.__blockSyncToOverlayCtrl=!1}__setupSyncFromOverlayController(){this.__onOverlayCtrlShow=()=>{this.opened=!0},this.__onOverlayCtrlHide=()=>{this.opened=!1},this.__onBeforeShow=e=>{let t=new CustomEvent(`before-opened`,{cancelable:!0});this.dispatchEvent(t),t.defaultPrevented&&(this._setOpenedWithoutPropertyEffects(this._overlayCtrl.isShown),e.preventDefault())},this.__onBeforeHide=e=>{let t=new CustomEvent(`before-closed`,{cancelable:!0});this.dispatchEvent(t),t.defaultPrevented&&(this._setOpenedWithoutPropertyEffects(this._overlayCtrl.isShown),e.preventDefault())},this._overlayCtrl.addEventListener(`show`,this.__onOverlayCtrlShow),this._overlayCtrl.addEventListener(`hide`,this.__onOverlayCtrlHide),this._overlayCtrl.addEventListener(`before-show`,this.__onBeforeShow),this._overlayCtrl.addEventListener(`before-hide`,this.__onBeforeHide)}__teardownSyncFromOverlayController(){this._overlayCtrl.removeEventListener(`show`,this.__onOverlayCtrlShow),this._overlayCtrl.removeEventListener(`hide`,this.__onOverlayCtrlHide),this._overlayCtrl.removeEventListener(`before-show`,this.__onBeforeShow),this._overlayCtrl.removeEventListener(`before-hide`,this.__onBeforeHide)}__syncToOverlayController(){this.opened?this._overlayCtrl.show():this._overlayCtrl.hide()}async toggle(){await this._overlayCtrl.toggle()}async open(){await this._overlayCtrl.show()}async close(){await this._overlayCtrl.hide()}repositionOverlay(){let e=this._overlayCtrl;e.placementMode===`local`&&e._popper&&e._popper.update()}async _isPermanentlyDisconnected(){return await this.updateComplete,!this.isConnected}});function tu(){return{visibilityTriggerFunction:({controller:e})=>{function t(){e._hasDisabledInvoker()||e.toggle()}return{init:()=>{e.invokerNode?.addEventListener(`click`,t)},teardown:()=>{e.invokerNode?.removeEventListener(`click`,t)}}}}}var nu=()=>({placementMode:`local`,inheritsReferenceWidth:`min`,hidesOnOutsideClick:!0,hidesOnEsc:!0,popperConfig:{placement:`bottom-start`,modifiers:[{name:`offset`,enabled:!1}]},handlesAccessibility:!0,...tu()}),ru=class extends eu(w){_defineOverlayConfig(){return{...nu()}}_addEventListeners(){this.actionItems.forEach(e=>{e.addEventListener(`click`,e=>{e.target?.dispatchEvent(new Event(`close-overlay`,{bubbles:!0}))})})}_setupInvoker(){let e=this.invokerNodes[0];e&&(e.setAttribute(`id`,`invoker-${this.uid}`),e.setAttribute(`aria-controls`,`content-${this.uid}`))}_setupContent(){let e=this.contentNodes[0];e&&(e.setAttribute(`id`,`content-${this.uid}`),e.setAttribute(`role`,`none`))}_setupOverlayCtrl(){super._setupOverlayCtrl(),this._setupInvoker(),this._setupContent()}firstUpdated(){this.uid=Jr(),this._addEventListeners()}render(){return v`
      <slot name="invoker"></slot>
      <slot name="backdrop"></slot>
      <slot name="content"></slot>
    `}};ru.styles=b`
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
  `,g([ee({selector:`craft-action-item`})],ru.prototype,`actionItems`,void 0),g([ee({slot:`invoker`})],ru.prototype,`invokerNodes`,void 0),g([ee({slot:`content`})],ru.prototype,`contentNodes`,void 0),customElements.get(`craft-action-menu`)||customElements.define(`craft-action-menu`,ru);var iu=new WeakMap;function au(e,t){Array.from(e.childNodes).forEach(n=>{if(n.nodeName===`#text`){let r=RegExp(`^(.*?)(${t})(.*)$`,`i`),i=n.nodeValue.match(r);if(i){let t=document.createTextNode(i[1]);e.appendChild(t);let r=document.createElement(`b`);r.textContent=i[2],e.appendChild(r);let a=document.createTextNode(i[3]);e.appendChild(a),e.removeChild(n),iu.set(e,()=>{e.appendChild(n),e.contains(t)&&t.parentNode!==null&&t.parentNode.removeChild(t),e.contains(r)&&r.parentNode!==null&&r.parentNode.removeChild(r),e.contains(a)&&a.parentNode!==null&&a.parentNode.removeChild(a)})}}else au(n,t)})}function ou(e){iu.has(e)&&iu.get(e)(),Array.from(e.childNodes).forEach(e=>{e.nodeName===`#text`?iu.has(e)&&iu.get(e)():ou(e)})}var su=class extends Fo{static get validatorName(){return`MatchesOption`}execute(e,t,n){return n?.node.modelValue instanceof Eo}};function cu(e){return Array.isArray(e)?e:[e]}var lu=Br(e=>class extends Ko(e){static get properties(){return{allowCustomChoice:{type:Boolean,attribute:`allow-custom-choice`},modelValue:{type:Object}}}get modelValue(){return this.__getChoicesFrom(super.modelValue)}set modelValue(e){if(super.modelValue=e,e==null||e===``)this._customChoices=new Set;else if(this.allowCustomChoice){let t=this.modelValue;this._customChoices=new Set(cu(e)),this.requestUpdate(`modelValue`,t)}}get formattedValue(){return this.__getChoicesFrom(super.formattedValue)}set formattedValue(e){if(super.formattedValue=e,e==null)this._customChoices=new Set;else if(this.allowCustomChoice){let t=this.modelValue;this._customChoices=new Set(cu(e).map(e=>this.formElements.find(t=>t.formattedValue===e)?.modelValue||e)),this.requestUpdate(`modelValue`,t)}}get serializedValue(){return this.__getChoicesFrom(super.serializedValue)}set serializedValue(e){if(super.serializedValue=e,e==null)this._customChoices=new Set;else if(this.allowCustomChoice){let t=this.modelValue;this._customChoices=new Set(cu(e).map(e=>this.formElements.find(t=>t.serializedValue===e)?.modelValue||e)),this.requestUpdate(`modelValue`,t)}}get customChoices(){if(!this.allowCustomChoice)return[];let e=this._getCheckedElements();return Array.from(this._customChoices).filter(t=>!e.some(e=>e.choiceValue===t))}constructor(){super(),this.allowCustomChoice=!1,this._customChoices=new Set}__getChoicesFrom(e){let t=e;return this.allowCustomChoice?this.multipleChoice?[...cu(t),...this.customChoices]:t===``?this._customChoices.values().next().value||``:t:t}_isEmpty(){return super._isEmpty()&&this._customChoices.size===0}clear(){this._customChoices=new Set,super.clear()}parser(e){return this.allowCustomChoice&&Array.isArray(e)?e.filter(e=>e.trim()!==``):e}}),uu=new WeakMap,du=class extends _o(eu(lu(Vs))){static get properties(){return{autocomplete:{type:String,reflect:!0},matchMode:{type:String,attribute:`match-mode`},showAllOnEmpty:{type:Boolean,attribute:`show-all-on-empty`},requireOptionMatch:{type:Boolean},allowCustomChoice:{type:Boolean,attribute:`allow-custom-choice`},__shouldAutocompleteNextUpdate:Boolean}}static get styles(){return[...super.styles,b`
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
      `]}static get localizeNamespaces(){return[{"lion-combobox":e=>{switch(e){case`bg-BG`:case`bg`:return k(()=>import(`./bg-ii8f6J7v.js`),[],import.meta.url);case`cs-CZ`:case`cs`:return k(()=>import(`./cs-pxSxJJBY.js`),[],import.meta.url);case`de-AT`:case`de-DE`:case`de`:return k(()=>import(`./de-DTCZZyUS.js`),[],import.meta.url);case`en-AU`:case`en-GB`:case`en-PH`:case`en-US`:case`en`:return k(()=>import(`./en-Dp2HRwt3.js`),[],import.meta.url);case`es-ES`:case`es`:return k(()=>import(`./es-DQcm-ovh.js`),[],import.meta.url);case`fr-FR`:case`fr-BE`:case`fr`:return k(()=>import(`./fr-Cb1iWJzD.js`),[],import.meta.url);case`hu-HU`:case`hu`:return k(()=>import(`./hu-CEYMgofy.js`),[],import.meta.url);case`it-IT`:case`it`:return k(()=>import(`./it-D8qndhcF.js`),[],import.meta.url);case`nl-BE`:case`nl-NL`:case`nl`:return k(()=>import(`./nl-Bvn6DzUK.js`),[],import.meta.url);case`pl-PL`:case`pl`:return k(()=>import(`./pl-CU-dL9vr.js`),[],import.meta.url);case`ro-RO`:case`ro`:return k(()=>import(`./ro-C-0uB18-.js`),[],import.meta.url);case`ru-RU`:case`ru`:return k(()=>import(`./ru-BCrU-bhw.js`),[],import.meta.url);case`sk-SK`:case`sk`:return k(()=>import(`./sk-HSpDI6YP.js`),[],import.meta.url);case`uk-UA`:case`uk`:return k(()=>import(`./uk-CupUNCSg.js`),[],import.meta.url);case`zh-CN`:case`zh`:return k(()=>import(`./zh-CWCQrcMF.js`),[],import.meta.url);default:return k(()=>import(`./en-Dp2HRwt3.js`),[],import.meta.url)}}},...super.localizeNamespaces]}get modelValue(){let e=super.modelValue;return e===``?this.parser(this.value):e}set modelValue(e){super.modelValue=e}get value(){return this._inputNode?.value||this.__value||``}set value(e){this._inputNode?(this._inputNode.value=e,this.__value=void 0):this.__value=e}reset(){super.reset(),this.multipleChoice||(this.value=this._initialModelValue),this._resetListboxOptions()}_resetListboxOptions(){this.formElements.forEach((e,t)=>{this._unhighlightMatchedOption(e),!this.showAllOnEmpty||!this.opened?e.style.display=`none`:(e.style.display=``,e.setAttribute(`aria-posinset`,`${t+1}`),e.setAttribute(`aria-setsize`,`${this.formElements.length}`),e.removeAttribute(`aria-hidden`))})}_inputGroupInputTemplate(){return v`
      <div class="input-group__input">
        <slot name="selection-display"></slot>
        <slot name="input"></slot>
      </div>
    `}_overlayListboxTemplate(){return v`
      <div
        id="overlay-content-node-wrapper"
        role="dialog"
        aria-label="${this.msgLit(`lion-combobox:optionsPopup`)}"
      >
        <slot name="listbox"></slot>
      </div>
      <slot id="options-outlet"></slot>
    `}_groupTwoTemplate(){return v` ${super._groupTwoTemplate()} ${this._overlayListboxTemplate()}`}get slots(){return{...super.slots,input:()=>{if(this._ariaVersion===`1.1`){let e=document.createElement(`div`),t=document.createElement(`input`);return t.style.cssText=`
          border: none;
          outline: none;
          width: 100%;
          height: 100%;
          font: inherit;
          background: inherit;
          color: inherit;
          border-radius: inherit;
          box-sizing: border-box;
          padding: 0;`,e.appendChild(t),e}return document.createElement(`input`)},listbox:super.slots.input}}get _comboboxNode(){return this.querySelector(`[slot="input"]`)}get _selectionDisplayNode(){return this.querySelector(`[slot="selection-display"]`)}get _inputNode(){return this._ariaVersion===`1.1`&&this._comboboxNode&&this._comboboxNode.querySelector(`input`)||this._comboboxNode}get _overlayContentNode(){return this._listboxNode}get _overlayReferenceNode(){return this.shadowRoot.querySelector(`.input-group__container`)}get _overlayInvokerNode(){return this._inputNode}get _listboxNode(){return this._overlayCtrl&&this._overlayCtrl.contentNode||Array.from(this.children).find(e=>e.slot===`listbox`)}get _activeDescendantOwnerNode(){return this._inputNode}get requireOptionMatch(){return!this.allowCustomChoice}set requireOptionMatch(e){this.allowCustomChoice=!e}constructor(){super(),this.autocomplete=`both`,this.matchMode=`all`,this.showAllOnEmpty=!1,this.requireOptionMatch=!0,this.rotateKeyboardNavigation=!0,this.selectionFollowsFocus=!0,this.defaultValidators.push(new su),this._ariaVersion=qr.isChromium?`1.1`:`1.0`,this._listboxReceivesNoFocus=!0,this._noTypeAhead=!0,this.__prevCboxValueNonSelected=``,this.__prevCboxValue=``,this.__hadUserIntendsInlineAutoFill=!1,this.__listboxContentChanged=!1,this._onKeyUp=this._onKeyUp.bind(this),this._textboxOnClick=this._textboxOnClick.bind(this),this._textboxOnInput=this._textboxOnInput.bind(this),this._textboxOnKeydown=this._textboxOnKeydown.bind(this)}connectedCallback(){super.connectedCallback(),this._selectionDisplayNode&&(this._selectionDisplayNode.comboboxElement=this),(this.disabled||this.readOnly)&&this.__setComboboxDisabledAndReadOnly()}requestUpdate(e,t,n){if(super.requestUpdate(e,t,n),(e===`disabled`||e===`readOnly`)&&this.__setComboboxDisabledAndReadOnly(),e===`modelValue`&&this.modelValue&&this.modelValue!==t&&this._syncToTextboxCondition(this.modelValue,this._oldModelValue))if(this.multipleChoice)this._syncToTextboxMultiple(this.modelValue,this._oldModelValue);else{let e=this._getTextboxValueFromOption(this.formElements[this.checkedIndex]);this._setTextboxValue(e)}}parser(e){return this.requireOptionMatch&&this.checkedIndex===-1&&e!==``&&!Array.isArray(e)?new Eo(e):super.parser(e)}__unsyncCheckedIndexOnInputChange(){let e=this._autoSelectCondition(),t=this.formElements[this.checkedIndex];if(!this.multipleChoice&&!e&&t){let e=this._getTextboxValueFromOption(t);this._inputNode.value.startsWith(e)||this.setCheckedIndex(-1)}}updated(e){super.updated(e),e.has(`__shouldAutocompleteNextUpdate`)&&this.__unsyncCheckedIndexOnInputChange(),e.has(`opened`)&&(this.opened&&(this.activeIndex=-1),!this.opened&&e.get(`opened`)!==void 0&&(this.__onOverlayClose(),this.activeIndex=-1)),e.has(`autocomplete`)&&this._inputNode.setAttribute(`aria-autocomplete`,this.autocomplete),e.has(`disabled`)&&this.setAttribute(`aria-disabled`,`${this.disabled}`),e.has(`__shouldAutocompleteNextUpdate`)&&this.__shouldAutocompleteNextUpdate&&(this._handleAutocompletion(),this.__shouldAutocompleteNextUpdate=!1,this.__listboxContentChanged=!1),typeof this._selectionDisplayNode?.onComboboxElementUpdated==`function`&&this._selectionDisplayNode.onComboboxElementUpdated(e)}matchCondition(e,t){let n=-1,r=this._getTextboxValueFromOption(e);return typeof r==`string`&&typeof t==`string`&&(n=r.toLowerCase().indexOf(t.toLowerCase())),this.matchMode===`all`?n>-1:n===0}_showOverlayCondition({lastKey:e}){return this.disabled||this.readOnly||e&&([`Tab`,`Escape`].includes(e)||!this.multipleChoice&&[`Enter`].includes(e))?!1:this.filled||this.showAllOnEmpty||!this.filled&&this.multipleChoice&&this.__prevCboxValueNonSelected?!0:this.opened}_getTextboxValueFromOption(e){return e?e.choiceValue:this.modelValue instanceof Eo?this.modelValue.viewValue:this.modelValue}_onListboxContentChanged(){super._onListboxContentChanged(),this.__shouldAutocompleteNextUpdate=!0,this.__listboxContentChanged=!0}_textboxOnInput(e){this.__shouldAutocompleteNextUpdate=!0,this.opened=this._showOverlayCondition({})}_textboxOnKeydown(e){e.key===`Tab`&&(this.opened=!1)}_listboxOnClick(e){super._listboxOnClick(e),this._inputNode.focus(),this.multipleChoice?(this._inputNode.value=``,this._resetListboxOptions()):(this.activeIndex=-1,this.opened=!1)}_setTextboxValue(e){this._inputNode&&this._inputNode.value!==e&&(this._inputNode.value=e)}__onOverlayClose(){this.multipleChoice?this._syncToTextboxMultiple(this.modelValue,this._oldModelValue):this.checkedIndex!==-1&&this._syncToTextboxCondition(this.modelValue,this._oldModelValue,{phase:`overlay-close`})&&(this._inputNode.value=this._getTextboxValueFromOption(this.formElements[this.checkedIndex]))}_repropagationCondition(e){return super._repropagationCondition(e)||this.formElements.every(e=>!e.checked)}_onFilterMatch(e,t){this._highlightMatchedOption(e,t),e.style.display=``}_highlightMatchedOption(e,t){if(au(e,t),e.textContent){let t=document.createElement(`span`);t.setAttribute(`aria-label`,e.textContent.replace(/\s+/g,` `)),Array.from(e.childNodes).forEach(e=>{t.appendChild(e)}),e.appendChild(t),uu.set(e,()=>{Array.from(t.childNodes).forEach(t=>{e.appendChild(t)}),e.contains(t)&&e.removeChild(t)})}}_onFilterUnmatch(e,t,n){this._unhighlightMatchedOption(e),e.style.display=`none`}_unhighlightMatchedOption(e){ou(e),uu.has(e)&&uu.get(e)()}__computeUserIntendsAutoFill({prevValue:e,curValue:t}){let n=e.length<t.length,r=e.length&&t.length&&e[0].toLowerCase()!==t[0].toLowerCase();return n||r||this.__listboxContentChanged&&this.__hadUserIntendsInlineAutoFill}_handleAutocompletion(){let e=this._inputNode.selectionStart!==this._inputNode.selectionEnd&&this._inputNode.value.length!==this._inputNode.selectionStart,t=this._inputNode.value,n=this._inputNode.selectionStart,r=e&&n?t.slice(0,n):t,i=e||this.__hadSelectionLastAutofill?this.__prevCboxValueNonSelected:this.__prevCboxValue,a=!r,o=[],s=!1,c=this.__computeUserIntendsAutoFill({prevValue:i,curValue:r}),l=this.autocomplete===`both`||this.autocomplete===`inline`,u=this._autoSelectCondition(),d=this.autocomplete===`inline`||this.autocomplete===`none`;this.formElements.forEach((e,t)=>{let n=this.matchCondition(e,r),f=!1;if(f=a?this.showAllOnEmpty:d||n,u&&!s&&n&&!e.disabled){let n=()=>{this.activeIndex=t,this.selectionFollowsFocus&&!this.multipleChoice&&this.setCheckedIndex(this.activeIndex),s=!0};if(c)if(l){let t=this._getTextboxValueFromOption(e);typeof t==`string`&&t!==``&&typeof r==`string`&&r!==``&&t.toLowerCase().indexOf(r.toLowerCase())===0&&(this.__textboxInlineComplete(e),n())}else n()}e.onFilterUnmatch?e.onFilterUnmatch(r,i):this._onFilterUnmatch(e,r,i),e.setAttribute(`aria-hidden`,`true`),e.removeAttribute(`aria-posinset`),e.removeAttribute(`aria-setsize`),f&&(o.push(e),e.onFilterMatch?e.onFilterMatch(r):this._onFilterMatch(e,r))});let f=o.length;o.forEach((e,t)=>{e.setAttribute(`aria-posinset`,`${t+1}`),e.setAttribute(`aria-setsize`,`${f}`),e.removeAttribute(`aria-hidden`)}),u&&!s&&!this.multipleChoice&&(this.setCheckedIndex(-1),i!==r&&(this.activeIndex=-1),this.modelValue=this.parser(t)),this.__prevCboxValueNonSelected=r,this.__prevCboxValue=this._inputNode.value,this.__hadSelectionLastAutofill=this._inputNode.value.length!==this._inputNode.selectionStart,this.__hadUserIntendsInlineAutoFill=c,this._overlayCtrl&&this._overlayCtrl._popper&&this._overlayCtrl._popper.update()}__textboxInlineComplete(e=this.formElements[this.activeIndex]){let t=this._getTextboxValueFromOption(e);if(this._inputNode.value!==t){let e=this._inputNode.value.length;this._inputNode.value=t,this._inputNode.selectionStart=e,this._inputNode.selectionEnd=this._inputNode.value.length}}_autoSelectCondition(){return this.autocomplete===`both`||this.autocomplete===`inline`}_setupListboxNode(){super._setupListboxNode(),this._listboxNode.removeAttribute(`tabindex`)}_defineOverlayConfig(){return{...nu(),elementToFocusAfterHide:void 0,invokerNode:this._comboboxNode,visibilityTriggerFunction:void 0}}_setupOverlayCtrl(){super._setupOverlayCtrl(),this.__shouldAutocompleteNextUpdate=!0,this.__setupCombobox()}_teardownOverlayCtrl(){super._teardownOverlayCtrl(),this.__teardownCombobox()}_setupOpenCloseListeners(){super._setupOpenCloseListeners(),this._inputNode.addEventListener(`keyup`,this._onKeyUp),this._inputNode.addEventListener(`click`,this._textboxOnClick)}_teardownOpenCloseListeners(){super._teardownOpenCloseListeners(),this._inputNode.removeEventListener(`keyup`,this._onKeyUp),this._inputNode.removeEventListener(`click`,this._textboxOnClick)}_listboxOnKeyDown(e){let{key:t}=e;switch(t){case`Escape`:this.opened=!1,super._listboxOnKeyDown(e),this._setTextboxValue(``);break;case`Backspace`:case`Delete`:this.requireOptionMatch?super._listboxOnKeyDown(e):this.opened=!1;break;case`Enter`:this.opened&&e.preventDefault(),!this.requireOptionMatch&&this.multipleChoice&&(!this.formElements[this.activeIndex]||this.formElements[this.activeIndex].hasAttribute(`aria-hidden`)||!this.opened)?(this.modelValue=this.parser([...this.modelValue,this._inputNode.value]),this._inputNode.value=``,this.opened=!1):(super._listboxOnKeyDown(e),this._resetListboxOptions()),this.multipleChoice?this._inputNode.value=``:this.opened=!1;break;default:super._listboxOnKeyDown(e);break}}_syncToTextboxCondition(e,t,{phase:n}={}){return this.autocomplete===`both`||this.autocomplete===`inline`||!this.focused}_syncToTextboxMultiple(e,t=[]){if(this.requireOptionMatch){let n=e.filter(e=>!t.includes(e)),r=this.formElements.filter(e=>n.includes(e.choiceValue)).map(e=>this._getTextboxValueFromOption(e)).join(` `);this._setTextboxValue(r)}}_enhanceLightDomClasses(){let e=this.querySelector(`[slot=input]`);e&&e.classList.add(`form-control`)}__setComboboxDisabledAndReadOnly(){this._comboboxNode&&(this._comboboxNode.toggleAttribute(`disabled`,this.disabled),this._comboboxNode.setAttribute(`aria-disabled`,`${this.disabled}`),this._comboboxNode.toggleAttribute(`readonly`,this.readOnly),this._comboboxNode.setAttribute(`aria-readonly`,`${this.readOnly}`)),this._inputNode&&(this._inputNode.toggleAttribute(`disabled`,this.disabled),this._inputNode.toggleAttribute(`readOnly`,this.readOnly),this._inputNode.setAttribute(`aria-readonly`,`${this.readOnly}`),this._inputNode.tabIndex=this.disabled?-1:0)}__setupCombobox(){this._comboboxNode.setAttribute(`role`,`combobox`),this._comboboxNode.setAttribute(`aria-haspopup`,`listbox`),this._inputNode.setAttribute(`aria-autocomplete`,this.autocomplete),this._comboboxNode.setAttribute(`aria-controls`,this._listboxNode.id),this._ariaVersion===`1.1`?this._comboboxNode.setAttribute(`aria-owns`,this._listboxNode.id):this._inputNode.setAttribute(`aria-owns`,this._listboxNode.id),this._listboxNode.setAttribute(`aria-labelledby`,this._labelNode.id),this._inputNode.addEventListener(`keydown`,this._listboxOnKeyDown),this._inputNode.addEventListener(`input`,this._textboxOnInput),this._inputNode.addEventListener(`keydown`,this._textboxOnKeydown)}__teardownCombobox(){this._inputNode.removeEventListener(`keydown`,this._listboxOnKeyDown),this._inputNode.removeEventListener(`input`,this._textboxOnInput),this._inputNode.removeEventListener(`keydown`,this._textboxOnKeydown)}_onKeyUp(e){let t=e&&e.key;this.opened=this._showOverlayCondition({lastKey:t,currentValue:this._inputNode.value})}_textboxOnClick(e){this.opened=this._showOverlayCondition({})}clear(){this.value=``,super.clear(),this.__shouldAutocompleteNextUpdate=!0}},fu=b`
  ${Ta}

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
    ${wa}
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
`,pu=class extends du{static get styles(){return[...super.styles,fu]}constructor(){super(),this.defaultValidators=[]}_inputGroupInputTemplate(){return v`
      <div class="input-group__input">
        <slot name="input"></slot>
        <craft-icon
          class="indicator"
          name="chevron-down"
          style="font-size: 0.8em"
        ></craft-icon>
      </div>
    `}parser(e){return e===``?super.parser(e):e}_getTextboxValueFromOption(e){return e?e.textContent?.trim()||``:super._getTextboxValueFromOption(e)}};customElements.get(`craft-combobox`)||customElements.define(`craft-combobox`,pu);var mu=class extends w{constructor(...e){super(...e),this.variant=l.Default,this.label=null}render(){return v`<span
      class="${o({indicator:!0,"indicator--success":this.variant===l.Success,"indicator--danger":this.variant===l.Danger,"indicator--warning":this.variant===l.Warning,"indicator--info":this.variant===l.Info,"indicator--empty":this.variant===`empty`})}"
    >
      <slot></slot>
    </span>`}};mu.styles=[gl,b`
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
    `],g([T({reflect:!0})],mu.prototype,`variant`,void 0),g([T()],mu.prototype,`label`,void 0),customElements.get(`craft-indicator`)||customElements.define(`craft-indicator`,mu);var hu=b`
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
`,gu=new WeakMap,_u=class extends w{constructor(...e){super(...e),this.progress=0,this.total=0,this.processed=0,this.showStatus=!1,this.pending=!1,this.smooth=!1,this.label=`Progress`,c(this,gu,0)}updated(e){if((e.has(`total`)||e.has(`processed`))&&this.total>0){let e=Math.min(100,Math.round(this.processed/this.total*100));e>=100&&s(gu,this)<100&&this.dispatchEvent(new CustomEvent(`complete`,{bubbles:!0,composed:!0})),this.progress=e}e.has(`progress`)&&(this.progress>0&&this.pending&&(this.pending=!1),m(gu,this,this.progress))}get progressPercent(){return Math.min(100,Math.max(0,this.progress))}get statusText(){return this.total>0?`${this.processed} / ${this.total}`:`${this.progressPercent}%`}reset(){this.progress=0,this.processed=0,this.pending=!0,m(gu,this,0)}show(){this.hidden=!1}hide(){this.hidden=!0}render(){let e={width:this.pending?`100%`:`${this.progressPercent}%`};return v`
      <div
        class=${o({"progress-bar":!0,"progress-bar--pending":this.pending})}
        part="track"
        role="progressbar"
        aria-valuenow=${this.pending?C:this.progressPercent}
        aria-valuemin="0"
        aria-valuemax="100"
        aria-label=${this.label}
      >
        <div
          class=${o({"progress-bar__fill":!0,"progress-bar__fill--smooth":this.smooth&&!this.pending})}
          part="fill"
          style=${i(e)}
        ></div>
      </div>
      ${this.showStatus?v`<div class="progress-bar__status" part="status">
            ${this.statusText}
          </div>`:C}
      <span class="visually-hidden">
        ${this.pending?`Loading`:`${this.progressPercent}%`}
      </span>
    `}};_u.styles=[hu],g([T({type:Number})],_u.prototype,`progress`,void 0),g([T({type:Number})],_u.prototype,`total`,void 0),g([T({type:Number})],_u.prototype,`processed`,void 0),g([T({type:Boolean,attribute:`show-status`})],_u.prototype,`showStatus`,void 0),g([T({type:Boolean,reflect:!0})],_u.prototype,`pending`,void 0),g([T({type:Boolean})],_u.prototype,`smooth`,void 0),g([T({type:String})],_u.prototype,`label`,void 0),customElements.get(`craft-progress-bar`)||customElements.define(`craft-progress-bar`,_u);var vu=class extends Ko(Xo(w)){connectedCallback(){super.connectedCallback(),this.setAttribute(`role`,`radiogroup`)}resetGroup(){let e;this.formElements.forEach(t=>{typeof t.resetGroup==`function`?t.resetGroup():typeof t.reset==`function`&&(t.reset(),t.checked&&(e=t.choiceValue))}),this.modelValue=e,this.resetInteractionState()}},yu=class extends Jo(Zo){connectedCallback(){super.connectedCallback(),this.type=`radio`}},bu=class extends vu{static get styles(){return[...super.styles,Ea,b`
        .input-group {
          display: grid;
          gap: var(--c-spacing-xs);
        }
      `]}};customElements.get(`craft-radio-group`)||customElements.define(`craft-radio-group`,bu);var xu=class extends yu{static get styles(){return[...super.styles,b`
        /* same as checkbox, potentially consolidate */
        :host {
          gap: var(--c-spacing-sm);
        }
      `]}};customElements.get(`craft-radio`)||customElements.define(`craft-radio`,xu);var Su=class extends w{render(){return v`<slot></slot>`}};Su.styles=[b`
      :host {
        display: grid;
        gap: var(--c-spacing-md);
      }
    `],customElements.get(`craft-field-group`)||customElements.define(`craft-field-group`,Su);var Cu=class extends w{constructor(...e){super(...e),this.appearance=`raised`,this.size=`lg`}render(){return v`<div
      class="${o({"cp-pane":!0,"cp-pane--raised":this.appearance===`raised`,"cp-pane--sunken":this.appearance===`sunken`,"cp-pane--lg":this.size===`lg`,"cp-pane--md":this.size===`md`,"cp-pane--sm":this.size===`sm`})}"
    >
      <slot></slot>
    </div>`}};Cu.styles=[b`
      .cp-pane {
        display: block;
        border: 1px solid var(--c-color-border-quiet);
        border-radius: var(--c-radius-md);
      }

      .cp-pane--lg {
        padding: var(--c-spacing-lg);
      }

      .cp-pane--raised {
        background-color: var(--c-surface-raised);
        box-shadow: var(--c-shadow-raised);
      }
    `],g([T()],Cu.prototype,`appearance`,void 0),g([T()],Cu.prototype,`size`,void 0),customElements.get(`craft-pane`)||customElements.define(`craft-pane`,Cu);var wu=class e{constructor(t={}){this.config={...e.defaultCookieOptions,...t}}set(e,t,n={}){let{path:r,domain:i,maxAge:a,expires:o,secure:s,sameSite:c,prefix:l}=Object.assign({},this.config,n),u=`${this.config.prefix}:${e}=${encodeURIComponent(t)}`;r&&(u+=`;path=${r}`),i&&(u+=`;domain=${i}`),a?u+=`;max-age-in-seconds=${a}`:o&&(u+=`;expires=${o.toUTCString()}`),s&&(u+=`;secure`),document.cookie=u}get(e){return document.cookie.replace(RegExp(`(?:(?:^|.*;\\s*)${this.config.prefix}:${e}\\s*\\=\\s*([^;]*).*$)|^.*$`),`$1`)}remove(e){this.set(e,``,{expires:new Date(`1970-01-01T00:00:00`)})}};wu.defaultCookieOptions={path:`/`,domain:null,secure:!1,sameSite:`strict`,prefix:`Craft`};var Tu=class{constructor(){this.refreshPromise=null,this.tokenName=null,this.tokenValue=null,this.refreshPromise=null}async getToken(){return this.tokenValue||await this.refreshToken(),this.tokenValue}async refreshToken(){return this.refreshPromise||=Ou.get(`users/session-info`).then(({data:e})=>{let{csrfTokenName:t,csrfTokenValue:n}=e;return this.tokenName=t??null,this.tokenValue=n??null,this.tokenValue}).finally(()=>{this.refreshPromise=null}),this.refreshPromise}clearToken(){this.tokenValue=null}};function Eu(e=``){return`/admin/actions/${e}`}function Du(){return{"X-Registered-Asset-Bundles":[...new Set(Cp.registeredAssetBundles)].join(`,`),"X-Registered-Js-Files":[...new Set(Cp.registeredJsFiles)].join(`,`)}}var Ou=f.create({baseURL:Eu()}),ku=new Tu;Ou.interceptors.request.use(async e=>{e.headers.set(`X-Requested-With`,`XMLHttpRequest`);let t=Du();return Object.entries(t).forEach(([t,n])=>{e.headers.set(t,n)}),e}),Ou.interceptors.response.use(e=>e,async e=>{let t=e.config;if(e.response?.status===419||e.response?.status===403&&!t._retry){t._retry=!0;try{return ku.clearToken(),t.headers[`X-CSRF-Token`]=await ku.refreshToken(),f(t)}catch(e){return console.error(`Failed to refresh CSRF token:`,e),Promise.reject(e)}}return Promise.reject(e)});var Au=!1,ju=null;async function Mu(e){if(!Au){if(ju)return ju;Au=!0;try{return(await Ou.post(`app/api-headers`,void 0,{cancelToken:e})).data}catch{}finally{Au=!1}}}var Nu=f.create({baseURL:`https://api.craftcms.com/v1/`});async function Pu(e){return ju?Object.entries(ju).forEach(([t,n])=>{e.headers.set(t,n)}):(e.params=e.params||{},e.params.processCraftHeaders=1),e}async function Fu(e,t){if(ju)return;let{data:n}=await Ou.post(`app/process-api-response-headers`,{headers:e},{cancelToken:t});return ju=n,Au=!1,ju}async function Iu(e){return await Fu(e.headers,e.config.cancelToken),e}Nu.interceptors.request.use(async e=>{let{cancelToken:t}=e,n=await Mu(t);n&&Object.entries(n).forEach(([t,n])=>{e.headers.set(t,n)});let r={...e,params:{...Cp.apiParams||{},...e.params,v:new Date().getTime()}};return n||(r.params.processCraftHeaders=1),Cp.httpProxy&&(r.proxy=Cp.httpProxy),r}),Nu.interceptors.request.use(Pu),Nu.interceptors.response.use(Iu);var Lu={START:`asset-indexes/start-indexing`,STOP:`asset-indexes/stop-indexing-session`,PROCESS:`asset-indexes/process-indexing-session`,OVERVIEW:`asset-indexes/indexing-session-overview`,FINISH:`asset-indexes/finish-indexing-session`},Ru=new WeakMap,zu=new WeakMap,Bu=new WeakMap,Vu=new WeakMap,Hu=new WeakMap,Uu=new WeakMap,Wu=new WeakMap,R=new WeakSet,Gu=class{constructor(e={}){u(this,R),c(this,Ru,new Map),c(this,zu,null),c(this,Bu,0),c(this,Vu,[]),c(this,Hu,[]),c(this,Uu,new Set),c(this,Wu,new Map);let{existingSessions:t=[],maxConcurrentConnections:n=3,autoResume:r=!0}=e;this.maxConcurrentConnections=n;for(let e of t)s(Ru,this).set(e.id,e);r&&(p(R,this,Yu).call(this),s(zu,this)!==null&&p(R,this,Xu).call(this))}getSessions(){return Array.from(s(Ru,this).values())}getCurrentSessionId(){return s(zu,this)}isProcessing(){return s(Bu,this)>0}on(e,t){return s(Wu,this).has(e)||s(Wu,this).set(e,new Set),s(Wu,this).get(e).add(t),()=>{s(Wu,this).get(e)?.delete(t)}}async startIndexing(e){let t=await Ou.post(Lu.START,e),{data:n}=t;return n.session&&(s(Ru,this).set(n.session.id,n.session),m(zu,this,n.session.id),p(R,this,qu).call(this),n.stop||p(R,this,Xu).call(this)),n.stop&&p(R,this,Ju).call(this,n.stop),t}stopSession(e){p(R,this,Zu).call(this,e),p(R,this,Qu).call(this,{sessionId:e,action:Lu.STOP,params:{sessionId:e},priority:!0})}getSessionOverview(e){p(R,this,Qu).call(this,{sessionId:e,action:Lu.OVERVIEW,params:{sessionId:e},priority:!0})}finishSession(e){p(R,this,Qu).call(this,{sessionId:e.sessionId,action:Lu.FINISH,params:e,priority:!0})}destroy(){s(Ru,this).clear(),m(Vu,this,[]),m(Hu,this,[]),s(Wu,this).clear(),m(zu,this,null),m(Bu,this,0)}};function Ku(e,t){s(Wu,this).get(e)?.forEach(e=>e(t))}function qu(e){p(R,this,Ku).call(this,`change`,{sessions:this.getSessions(),currentSessionId:s(zu,this),reviewSessionId:e})}function Ju(e){s(Ru,this).delete(e),s(zu,this)===e&&m(zu,this,null),p(R,this,qu).call(this)}function Yu(){for(let[e,t]of s(Ru,this))if(!t.actionRequired&&!s(Uu,this).has(e)){m(zu,this,e);return}m(zu,this,null)}function Xu(){if(s(zu,this)||p(R,this,Yu).call(this),!s(zu,this))return;let e=s(Ru,this).get(s(zu,this));if(!e)return;let t=e.totalEntries-e.processedEntries,n=this.maxConcurrentConnections-s(Bu,this),r=Math.min(n,t);for(let t=0;t<r;t++)p(R,this,Qu).call(this,{sessionId:e.id,action:Lu.PROCESS,params:{sessionId:s(zu,this)},priority:!1});e.processIfRootEmpty&&p(R,this,Qu).call(this,{sessionId:e.id,action:Lu.PROCESS,params:{sessionId:s(zu,this)},priority:!1})}function Zu(e){s(Uu,this).add(e),m(Vu,this,s(Vu,this).filter(t=>t.sessionId!==e))}function Qu(e){e.priority?s(Hu,this).push(e):s(Vu,this).push(e),p(R,this,$u).call(this)}function $u(){if(!(s(Vu,this).length+s(Hu,this).length===0||s(Bu,this)>=this.maxConcurrentConnections))for(;s(Vu,this).length+s(Hu,this).length>0&&s(Bu,this)<this.maxConcurrentConnections;){var e;m(Bu,this,(e=s(Bu,this),e++,e));let t=s(Hu,this).length>0?s(Hu,this).shift():s(Vu,this).shift();p(R,this,ed).call(this,t)}}async function ed(e){try{let t=await Ou.post(e.action,e.params);p(R,this,td).call(this,t.data)}catch(t){p(R,this,nd).call(this,t,e)}finally{var t;m(Bu,this,(t=s(Bu,this),t--,t)),p(R,this,$u).call(this)}}function td(e){let t;e.session&&(s(Ru,this).set(e.session.id,e.session),p(R,this,Yu).call(this),e.session.actionRequired&&!e.skipDialog?s(Uu,this).has(e.session.id)||(t=e.session.id):s(Uu,this).has(e.session.id)||p(R,this,Xu).call(this)),p(R,this,Yu).call(this),e.stop&&(s(Ru,this).delete(e.stop),s(zu,this)===e.stop&&m(zu,this,null)),p(R,this,qu).call(this,t),s(Ru,this).size===0&&p(R,this,Ku).call(this,`complete`,{})}function nd(e,t){p(R,this,Yu).call(this);let n=e?.response?.data?.message||e.message||`An error occurred during indexing.`;p(R,this,Ku).call(this,`error`,{message:n,sessionId:t.sessionId}),p(R,this,$u).call(this)}var rd=function(e,t,n,r,i){if(r===`m`)throw TypeError(`Private method is not writable`);if(r===`a`&&!i)throw TypeError(`Private accessor was defined without a setter`);if(typeof t==`function`?e!==t||!i:!t.has(e))throw TypeError(`Cannot write private member to an object whose class did not declare it`);return r===`a`?i.call(e,n):i?i.value=n:t.set(e,n),n},id=function(e,t,n,r){if(n===`a`&&!r)throw TypeError(`Private accessor was defined without a getter`);if(typeof t==`function`?e!==t||!r:!t.has(e))throw TypeError(`Cannot read private member from an object whose class did not declare it`);return n===`m`?r:n===`a`?r.call(e):r?r.value:t.get(e)},ad,od=class{formatToParts(e){let t=[];for(let n of e)t.push({type:`element`,value:n}),t.push({type:`literal`,value:`, `});return t.slice(0,-1)}},sd=typeof Intl<`u`&&Intl.ListFormat||od,cd=[[`years`,`year`],[`months`,`month`],[`weeks`,`week`],[`days`,`day`],[`hours`,`hour`],[`minutes`,`minute`],[`seconds`,`second`],[`milliseconds`,`millisecond`]],ld={minimumIntegerDigits:2},ud=class{constructor(e,t={}){ad.set(this,void 0);let n=String(t.style||`short`);n!==`long`&&n!==`short`&&n!==`narrow`&&n!==`digital`&&(n=`short`);let r=n===`digital`?`numeric`:n,i=t.hours||r;r=i===`2-digit`?`numeric`:i;let a=t.minutes||r;r=a===`2-digit`?`numeric`:a;let o=t.seconds||r;r=o===`2-digit`?`numeric`:o;let s=t.milliseconds||r;rd(this,ad,{locale:e,style:n,years:t.years||n===`digital`?`short`:n,yearsDisplay:t.yearsDisplay===`always`?`always`:`auto`,months:t.months||n===`digital`?`short`:n,monthsDisplay:t.monthsDisplay===`always`?`always`:`auto`,weeks:t.weeks||n===`digital`?`short`:n,weeksDisplay:t.weeksDisplay===`always`?`always`:`auto`,days:t.days||n===`digital`?`short`:n,daysDisplay:t.daysDisplay===`always`?`always`:`auto`,hours:i,hoursDisplay:t.hoursDisplay===`always`||n===`digital`?`always`:`auto`,minutes:a,minutesDisplay:t.minutesDisplay===`always`||n===`digital`?`always`:`auto`,seconds:o,secondsDisplay:t.secondsDisplay===`always`||n===`digital`?`always`:`auto`,milliseconds:s,millisecondsDisplay:t.millisecondsDisplay===`always`?`always`:`auto`},`f`)}resolvedOptions(){return id(this,ad,`f`)}formatToParts(e){let t=[],n=id(this,ad,`f`),r=n.style,i=n.locale;for(let[a,o]of cd){let s=e[a];if(n[`${a}Display`]===`auto`&&!s)continue;let c=n[a],l=c===`2-digit`?ld:c===`numeric`?{}:{style:`unit`,unit:o,unitDisplay:c},u=new Intl.NumberFormat(i,l).format(s);a===`months`&&(c===`narrow`||r===`narrow`&&u.endsWith(`m`))&&(u=u.replace(/(\d+)m$/,`$1mo`)),t.push(u)}return new sd(i,{type:`unit`,style:r===`digital`?`short`:r}).formatToParts(t)}format(e){return this.formatToParts(e).map(e=>e.value).join(``)}};ad=new WeakMap;var dd=/^[-+]?P(?:(\d+)Y)?(?:(\d+)M)?(?:(\d+)W)?(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?)?$/,fd=[`year`,`month`,`week`,`day`,`hour`,`minute`,`second`,`millisecond`],pd=e=>dd.test(e),md=class e{constructor(e=0,t=0,n=0,r=0,i=0,a=0,o=0,s=0){this.years=e,this.months=t,this.weeks=n,this.days=r,this.hours=i,this.minutes=a,this.seconds=o,this.milliseconds=s,this.years||=0,this.sign||=Math.sign(this.years),this.months||=0,this.sign||=Math.sign(this.months),this.weeks||=0,this.sign||=Math.sign(this.weeks),this.days||=0,this.sign||=Math.sign(this.days),this.hours||=0,this.sign||=Math.sign(this.hours),this.minutes||=0,this.sign||=Math.sign(this.minutes),this.seconds||=0,this.sign||=Math.sign(this.seconds),this.milliseconds||=0,this.sign||=Math.sign(this.milliseconds),this.blank=this.sign===0}abs(){return new e(Math.abs(this.years),Math.abs(this.months),Math.abs(this.weeks),Math.abs(this.days),Math.abs(this.hours),Math.abs(this.minutes),Math.abs(this.seconds),Math.abs(this.milliseconds))}static from(t){if(typeof t==`string`){let n=String(t).trim(),r=n.startsWith(`-`)?-1:1,i=n.match(dd)?.slice(1).map(e=>(Number(e)||0)*r);return i?new e(...i):new e}else if(typeof t==`object`){let{years:n,months:r,weeks:i,days:a,hours:o,minutes:s,seconds:c,milliseconds:l}=t;return new e(n,r,i,a,o,s,c,l)}throw RangeError(`invalid duration`)}static compare(t,n){let r=Date.now(),i=Math.abs(hd(r,e.from(t)).getTime()-r),a=Math.abs(hd(r,e.from(n)).getTime()-r);return i>a?-1:+(i<a)}toLocaleString(e,t){return new ud(e,t).format(this)}};function hd(e,t){let n=new Date(e);return t.sign<0?(n.setUTCSeconds(n.getUTCSeconds()+t.seconds),n.setUTCMinutes(n.getUTCMinutes()+t.minutes),n.setUTCHours(n.getUTCHours()+t.hours),n.setUTCDate(n.getUTCDate()+t.weeks*7+t.days),n.setUTCMonth(n.getUTCMonth()+t.months),n.setUTCFullYear(n.getUTCFullYear()+t.years)):(n.setUTCFullYear(n.getUTCFullYear()+t.years),n.setUTCMonth(n.getUTCMonth()+t.months),n.setUTCDate(n.getUTCDate()+t.weeks*7+t.days),n.setUTCHours(n.getUTCHours()+t.hours),n.setUTCMinutes(n.getUTCMinutes()+t.minutes),n.setUTCSeconds(n.getUTCSeconds()+t.seconds)),n}function gd(e,t=`second`,n=Date.now()){let r=e.getTime()-n;if(r===0)return new md;let i=Math.sign(r),a=Math.abs(r),o=Math.floor(a/1e3),s=Math.floor(o/60),c=Math.floor(s/60),l=Math.floor(c/24),u=Math.floor(l/30),d=Math.floor(u/12),f=fd.indexOf(t)||fd.length;return new md(f>=0?d*i:0,f>=1?(u-d*12)*i:0,0,f>=3?(l-u*30)*i:0,f>=4?(c-l*24)*i:0,f>=5?(s-c*60)*i:0,f>=6?(o-s*60)*i:0,f>=7?(a-o*1e3)*i:0)}function _d(e,{relativeTo:t=Date.now()}={}){if(t=new Date(t),e.blank)return e;let n=e.sign,r=Math.abs(e.years),i=Math.abs(e.months),a=Math.abs(e.weeks),o=Math.abs(e.days),s=Math.abs(e.hours),c=Math.abs(e.minutes),l=Math.abs(e.seconds),u=Math.abs(e.milliseconds);u>=900&&(l+=Math.round(u/1e3)),(l||c||s||o||a||i||r)&&(u=0),l>=55&&(c+=Math.round(l/60)),(c||s||o||a||i||r)&&(l=0),c>=55&&(s+=Math.round(c/60)),(s||o||a||i||r)&&(c=0),o&&s>=12&&(o+=Math.round(s/24)),!o&&s>=21&&(o+=Math.round(s/24)),(o||a||i||r)&&(s=0);let d=t.getFullYear(),f=t.getMonth(),p=t.getDate();if(o>=27||r+i+o){let e=new Date(t);e.setDate(1),e.setMonth(f+i*n+1),e.setDate(0);let s=Math.max(0,p-e.getDate()),c=new Date(t);c.setFullYear(d+r*n),c.setDate(p-s),c.setMonth(f+i*n),c.setDate(p-s+o*n);let l=c.getFullYear()-t.getFullYear(),u=c.getMonth()-t.getMonth(),m=Math.abs(Math.round((Number(c)-Number(t))/864e5))+s,h=Math.abs(l*12+u);m<27?(o>=6?(a+=Math.round(o/7),o=0):o=m,i=r=0):h<=11?(i=h,r=0):(i=0,r=l*n),(i||r)&&(o=0)}return r&&(i=0),a>=4&&(i+=Math.round(a/4)),(i||r)&&(a=0),o&&a&&!i&&!r&&(a+=Math.round(o/7),o=0),new md(r*n,i*n,a*n,o*n,s*n,c*n,l*n,u*n)}function vd(e,t){let n=_d(e,t);if(n.blank)return[0,`second`];for(let e of fd){if(e===`millisecond`)continue;let t=n[`${e}s`];if(t)return[t,e]}return[0,`second`]}var z=function(e,t,n,r){if(n===`a`&&!r)throw TypeError(`Private accessor was defined without a getter`);if(typeof t==`function`?e!==t||!r:!t.has(e))throw TypeError(`Cannot read private member from an object whose class did not declare it`);return n===`m`?r:n===`a`?r.call(e):r?r.value:t.get(e)},yd=function(e,t,n,r,i){if(r===`m`)throw TypeError(`Private method is not writable`);if(r===`a`&&!i)throw TypeError(`Private accessor was defined without a setter`);if(typeof t==`function`?e!==t||!i:!t.has(e))throw TypeError(`Cannot write private member to an object whose class did not declare it`);return r===`a`?i.call(e,n):i?i.value=n:t.set(e,n),n},B,bd,xd,Sd,Cd,wd,Td,Ed,Dd,Od,kd,Ad,jd,Md,Nd,Pd,Fd=globalThis.HTMLElement||null,Id=new md,Ld=new md(0,0,0,0,0,1),Rd=class extends Event{constructor(e,t,n,r){super(`relative-time-updated`,{bubbles:!0,composed:!0}),this.oldText=e,this.newText=t,this.oldTitle=n,this.newTitle=r}};function zd(e){if(!e.date)return 1/0;if(e.format===`duration`||e.format===`elapsed`){let t=e.precision;if(t===`second`)return 1e3;if(t===`minute`)return 60*1e3}let t=Math.abs(Date.now()-e.date.getTime());return t<60*1e3?1e3:t<3600*1e3?60*1e3:3600*1e3}var Bd=new class{constructor(){this.elements=new Set,this.time=1/0,this.timer=-1}observe(e){if(this.elements.has(e))return;this.elements.add(e);let t=e.date;if(t&&t.getTime()){let t=zd(e),n=Date.now()+t;n<this.time&&(clearTimeout(this.timer),this.timer=setTimeout(()=>this.update(),t),this.time=n)}}unobserve(e){this.elements.has(e)&&this.elements.delete(e)}update(){if(clearTimeout(this.timer),!this.elements.size)return;let e=1/0;for(let t of this.elements)e=Math.min(e,zd(t)),t.update();this.time=Math.min(3600*1e3,e),this.timer=setTimeout(()=>this.update(),this.time),this.time+=Date.now()}},Vd=class extends Fd{constructor(){super(...arguments),B.add(this),bd.set(this,!1),xd.set(this,!1),Cd.set(this,this.shadowRoot?this.shadowRoot:this.attachShadow?this.attachShadow({mode:`open`}):this),Pd.set(this,null)}static define(e=`relative-time`,t=customElements){return t.define(e,this),this}get timeZone(){return this.closest(`[time-zone]`)?.getAttribute(`time-zone`)||this.ownerDocument.documentElement.getAttribute(`time-zone`)||void 0}static get observedAttributes(){return[`second`,`minute`,`hour`,`weekday`,`day`,`month`,`year`,`time-zone-name`,`prefix`,`threshold`,`tense`,`precision`,`format`,`format-style`,`no-title`,`datetime`,`lang`,`title`,`aria-hidden`,`time-zone`]}get onRelativeTimeUpdated(){return z(this,Pd,`f`)}set onRelativeTimeUpdated(e){z(this,Pd,`f`)&&this.removeEventListener(`relative-time-updated`,z(this,Pd,`f`)),yd(this,Pd,typeof e==`object`||typeof e==`function`?e:null,`f`),typeof e==`function`&&this.addEventListener(`relative-time-updated`,e)}get second(){let e=this.getAttribute(`second`);if(e===`numeric`||e===`2-digit`)return e}set second(e){this.setAttribute(`second`,e||``)}get minute(){let e=this.getAttribute(`minute`);if(e===`numeric`||e===`2-digit`)return e}set minute(e){this.setAttribute(`minute`,e||``)}get hour(){let e=this.getAttribute(`hour`);if(e===`numeric`||e===`2-digit`)return e}set hour(e){this.setAttribute(`hour`,e||``)}get weekday(){let e=this.getAttribute(`weekday`);if(e===`long`||e===`short`||e===`narrow`)return e;if(this.format===`datetime`&&e!==``)return this.formatStyle}set weekday(e){this.setAttribute(`weekday`,e||``)}get day(){let e=this.getAttribute(`day`)??`numeric`;if(e===`numeric`||e===`2-digit`)return e}set day(e){this.setAttribute(`day`,e||``)}get month(){let e=this.format,t=this.getAttribute(`month`);if(t!==``&&(t??=e===`datetime`?this.formatStyle:`short`,t===`numeric`||t===`2-digit`||t===`short`||t===`long`||t===`narrow`))return t}set month(e){this.setAttribute(`month`,e||``)}get year(){let e=this.getAttribute(`year`);if(e===`numeric`||e===`2-digit`)return e;if(!this.hasAttribute(`year`)&&new Date().getUTCFullYear()!==this.date?.getUTCFullYear())return`numeric`}set year(e){this.setAttribute(`year`,e||``)}get timeZoneName(){let e=this.getAttribute(`time-zone-name`);if(e===`long`||e===`short`||e===`shortOffset`||e===`longOffset`||e===`shortGeneric`||e===`longGeneric`)return e}set timeZoneName(e){this.setAttribute(`time-zone-name`,e||``)}get prefix(){return this.getAttribute(`prefix`)??(this.format===`datetime`?``:`on`)}set prefix(e){this.setAttribute(`prefix`,e)}get threshold(){let e=this.getAttribute(`threshold`);return e&&pd(e)?e:`P30D`}set threshold(e){this.setAttribute(`threshold`,e)}get tense(){let e=this.getAttribute(`tense`);return e===`past`?`past`:e===`future`?`future`:`auto`}set tense(e){this.setAttribute(`tense`,e)}get precision(){let e=this.getAttribute(`precision`);return fd.includes(e)?e:this.format===`micro`?`minute`:`second`}set precision(e){this.setAttribute(`precision`,e)}get format(){let e=this.getAttribute(`format`);return e===`datetime`?`datetime`:e===`relative`?`relative`:e===`duration`?`duration`:e===`micro`?`micro`:e===`elapsed`?`elapsed`:`auto`}set format(e){this.setAttribute(`format`,e)}get formatStyle(){let e=this.getAttribute(`format-style`);if(e===`long`)return`long`;if(e===`short`)return`short`;if(e===`narrow`)return`narrow`;let t=this.format;return t===`elapsed`||t===`micro`?`narrow`:t===`datetime`?`short`:`long`}set formatStyle(e){this.setAttribute(`format-style`,e)}get noTitle(){return this.hasAttribute(`no-title`)}set noTitle(e){this.toggleAttribute(`no-title`,e)}get datetime(){return this.getAttribute(`datetime`)||``}set datetime(e){this.setAttribute(`datetime`,e)}get date(){let e=Date.parse(this.datetime);return Number.isNaN(e)?null:new Date(e)}set date(e){this.datetime=e?.toISOString()||``}connectedCallback(){this.update()}disconnectedCallback(){Bd.unobserve(this)}attributeChangedCallback(e,t,n){t!==n&&(e===`title`&&yd(this,bd,n!==null&&(this.date&&z(this,B,`m`,wd).call(this,this.date))!==n,`f`),!z(this,xd,`f`)&&!(e===`title`&&z(this,bd,`f`))&&yd(this,xd,(async()=>{await Promise.resolve(),this.update(),yd(this,xd,!1,`f`)})(),`f`))}update(){let e=z(this,Cd,`f`).textContent||this.textContent||``,t=this.getAttribute(`title`)||``,n=t,r=this.date;if(typeof Intl>`u`||!Intl.DateTimeFormat||!r){z(this,Cd,`f`).textContent=e;return}let i=Date.now();z(this,bd,`f`)||(n=z(this,B,`m`,wd).call(this,r)||``,n&&!this.noTitle&&this.setAttribute(`title`,n));let a=gd(r,this.precision,i),o=z(this,B,`m`,Td).call(this,a),s=e,c=z(this,B,`m`,Nd).call(this,o);s=c?z(this,B,`m`,jd).call(this,r):o===`duration`?z(this,B,`m`,Ed).call(this,a):o===`relative`?z(this,B,`m`,Dd).call(this,a):z(this,B,`m`,Od).call(this,r),s?z(this,B,`m`,Md).call(this,s):this.shadowRoot===z(this,Cd,`f`)&&this.textContent&&z(this,B,`m`,Md).call(this,this.textContent),(s!==e||n!==t)&&this.dispatchEvent(new Rd(e,s,t,n)),o===`relative`||o===`duration`||c&&(z(this,B,`m`,kd).call(this,r)||z(this,B,`m`,Ad).call(this,r))?Bd.observe(this):Bd.unobserve(this)}};bd=new WeakMap,xd=new WeakMap,Cd=new WeakMap,Pd=new WeakMap,B=new WeakSet,Sd=function(){let e=this.closest(`[lang]`)?.getAttribute(`lang`)||this.ownerDocument.documentElement.getAttribute(`lang`);try{return new Intl.Locale(e??``).toString()}catch{return`default`}},wd=function(e){return new Intl.DateTimeFormat(z(this,B,`a`,Sd),{day:`numeric`,month:`short`,year:`numeric`,hour:`numeric`,minute:`2-digit`,timeZoneName:`short`,timeZone:this.timeZone}).format(e)},Td=function(e){let t=this.format;if(t===`datetime`)return`datetime`;if(t===`duration`||t===`elapsed`||t===`micro`)return`duration`;if((t===`auto`||t===`relative`)&&typeof Intl<`u`&&Intl.RelativeTimeFormat){let t=this.tense;if(t===`past`||t===`future`||md.compare(e,this.threshold)===1)return`relative`}return`datetime`},Ed=function(e){let t=z(this,B,`a`,Sd),n=this.format,r=this.formatStyle,i=this.tense,a=Id;n===`micro`?(e=_d(e),a=Ld,e.months===0&&(this.tense===`past`&&e.sign!==-1||this.tense===`future`&&e.sign!==1)&&(e=Ld)):(i===`past`&&e.sign!==-1||i===`future`&&e.sign!==1)&&(e=a);let o=`${this.precision}sDisplay`;return e.blank?a.toLocaleString(t,{style:r,[o]:`always`}):e.abs().toLocaleString(t,{style:r})},Dd=function(e){let t=new Intl.RelativeTimeFormat(z(this,B,`a`,Sd),{numeric:`auto`,style:this.formatStyle}),n=this.tense;n===`future`&&e.sign!==1&&(e=Id),n===`past`&&e.sign!==-1&&(e=Id);let[r,i]=vd(e);return i===`second`&&r<10?t.format(0,this.precision===`millisecond`?`second`:this.precision):t.format(r,i)},Od=function(e){let t=new Intl.DateTimeFormat(z(this,B,`a`,Sd),{second:this.second,minute:this.minute,hour:this.hour,weekday:this.weekday,day:this.day,month:this.month,year:this.year,timeZoneName:this.timeZoneName,timeZone:this.timeZone});return`${this.prefix} ${t.format(e)}`.trim()},kd=function(e){let t=new Date,n=new Intl.DateTimeFormat(z(this,B,`a`,Sd),{timeZone:this.timeZone,year:`numeric`,month:`2-digit`,day:`2-digit`});return n.format(t)===n.format(e)},Ad=function(e){let t=new Date,n=new Intl.DateTimeFormat(z(this,B,`a`,Sd),{timeZone:this.timeZone,year:`numeric`});return n.format(t)===n.format(e)},jd=function(e){let t={hour:`numeric`,minute:`2-digit`,timeZoneName:`short`,timeZone:this.timeZone};if(z(this,B,`m`,kd).call(this,e)){let n=new Intl.RelativeTimeFormat(z(this,B,`a`,Sd),{numeric:`auto`}).format(0,`day`);n=n.charAt(0).toLocaleUpperCase(z(this,B,`a`,Sd))+n.slice(1);let r=new Intl.DateTimeFormat(z(this,B,`a`,Sd),t).format(e);return`${n} ${r}`}let n=Object.assign(Object.assign({},t),{day:`numeric`,month:`short`});return z(this,B,`m`,Ad).call(this,e)?new Intl.DateTimeFormat(z(this,B,`a`,Sd),n).format(e):new Intl.DateTimeFormat(z(this,B,`a`,Sd),Object.assign(Object.assign({},n),{year:`numeric`})).format(e)},Md=function(e){if(this.hasAttribute(`aria-hidden`)&&this.getAttribute(`aria-hidden`)===`true`){let t=document.createElement(`span`);t.setAttribute(`aria-hidden`,`true`),t.textContent=e,z(this,Cd,`f`).replaceChildren(t)}else z(this,Cd,`f`).textContent=e},Nd=function(e){return e===`duration`?!1:this.ownerDocument.documentElement.getAttribute(`data-prefers-absolute-time`)===`true`||this.ownerDocument.body?.getAttribute(`data-prefers-absolute-time`)===`true`};var Hd=typeof globalThis<`u`?globalThis:window;try{Hd.RelativeTimeElement=Vd.define()}catch(e){if(!(Hd.DOMException&&e instanceof DOMException&&e.name===`NotSupportedError`)&&!(e instanceof ReferenceError))throw e}var Ud=class extends $o{constructor(...e){super(...e),this.autocorrect=!1,this.autocapitalize=`off`}static get styles(){return[...super.styles,b`
        .input-group__input {
          font-family: var(--c-font-mono);
          font-size: 0.9em;
        }
      `]}firstUpdated(e){super.firstUpdated(e),this._inputNode?.setAttribute(`autocorrect`,this.autocorrect?`on`:`off`),this._inputNode?.setAttribute(`autocapitalize`,this.autocapitalize)}};g([T({reflect:!0})],Ud.prototype,`autocorrect`,void 0),g([T({reflect:!0,type:String})],Ud.prototype,`autocapitalize`,void 0),customElements.get(`craft-input-handle`)||customElements.define(`craft-input-handle`,Ud);var Wd=new Map,Gd=class extends w{constructor(...e){super(...e),this.returnUrl=``,this._state=`idle`}firstUpdated(){this._input?.focus()}async _onSubmit(e){e.preventDefault(),await this._submit(this._input?.value??``)}async _submit(e){if(this._state!==`loading`){this._state=`loading`;try{await Ou.post(this.endpoint,{code:e}),this.dispatchEvent(new CustomEvent(`login-verified`,{bubbles:!0,composed:!0,detail:{returnUrl:this.returnUrl}})),this._state=`success`,setTimeout(()=>{this._state=`idle`},2e3)}catch(e){this._state=`error`,this.dispatchEvent(new CustomEvent(`login-failed`,{bubbles:!0,composed:!0,detail:{message:e?.response?.data?.message??n(`A server error occurred.`)}}))}}}renderInput(){return v`<slot name="input">
      <div>
        ${n(`Implement the renderInput() method or provide an input slot`)}
      </div>
    </slot>`}renderFields(){return v`
      <slot name="fields">
        ${this.renderInput()}
        <slot name="submit-button">
          <craft-button
            slot="after"
            type="submit"
            variant="primary"
            ?loading="${this._state===`loading`}"
          >
            ${n(`Verify`)}
          </craft-button>
        </slot>
      </slot>
    `}render(){return v`
      <form
        class="login-form"
        accept-charset="UTF-8"
        @submit="${this._onSubmit}"
      >
        <div class="login-form__fields">${this.renderFields()}</div>
      </form>
    `}static register(e,t){customElements.get(e)||customElements.define(e,t),Wd.set(t.METHOD,t)}static isNative(e){return Wd.has(e)}};Gd.styles=[b`
      :host {
        display: block;
        width: 100%;
      }

      .spinner-overlay {
        display: grid;
        place-items: center;
      }

      .login-form__fields {
        display: flex;
        gap: var(--c-spacing-md);
        align-items: end;
      }

      .login-form__actions {
        margin-block-start: var(--c-spacing-lg);
      }

      .login-form__error {
        margin-block-start: var(--c-spacing-md);
      }

      .alternative-login-methods {
        margin-block-start: var(--c-spacing-lg);
      }

      hr {
        margin-block: var(--c-spacing-lg);
        border: none;
        border-block-end: 1px solid var(--c-color-border-quiet);
      }
    `],g([T({attribute:`return-url`})],Gd.prototype,`returnUrl`,void 0),g([E()],Gd.prototype,`_state`,void 0),g([O(`craft-input`)],Gd.prototype,`_input`,void 0),Ze();var Kd={Á:`A`,á:`a`,Ä:`A`,ä:`a`,À:`A`,à:`a`,Â:`A`,â:`a`,É:`E`,é:`e`,Ë:`E`,ë:`e`,È:`E`,è:`e`,Ê:`E`,ê:`e`,Í:`I`,í:`i`,Ï:`I`,ï:`i`,Ì:`I`,ì:`i`,Î:`I`,î:`i`,Ó:`O`,ó:`o`,Ö:`O`,ö:`o`,Ò:`O`,ò:`o`,Ô:`O`,ô:`o`,Ú:`U`,ú:`u`,Ü:`U`,ü:`u`,Ù:`U`,ù:`u`,Û:`U`,û:`u`,Ý:`Y`,ý:`y`,Ÿ:`Y`,А:`A`,Б:`B`,В:`V`,Г:`G`,Д:`D`,Ѓ:`Gj`,Е:`E`,Ж:`Z`,З:`Z`,Ѕ:`Dz`,И:`I`,Ј:`j`,К:`K`,Л:`L`,Љ:`Lj`,М:`M`,Н:`N`,Њ:`Nj`,О:`O`,П:`P`,Р:`R`,С:`S`,Т:`T`,Ќ:`Kj`,У:`U`,Ф:`F`,Х:`X`,Ц:`C`,Ч:`C`,Џ:`Dz`,Ш:`S`,а:`a`,б:`b`,в:`v`,г:`g`,д:`d`,ѓ:`gj`,е:`e`,ж:`z`,з:`z`,ѕ:`dz`,и:`i`,ј:`j`,к:`k`,л:`l`,љ:`lj`,м:`m`,н:`n`,њ:`nj`,о:`o`,п:`p`,р:`r`,с:`s`,т:`t`,ќ:`kj`,у:`u`,ф:`f`,х:`x`,ц:`c`,ч:`c`,џ:`dz`,ш:`s`,æ:`ae`,ǽ:`ae`,Ã:`A`,Å:`A`,Ǻ:`A`,Ă:`A`,Ǎ:`A`,Æ:`AE`,Ǽ:`AE`,ã:`a`,å:`a`,ǻ:`a`,ă:`a`,ǎ:`a`,ª:`a`,Ĉ:`C`,Ċ:`C`,Ç:`C`,ç:`c`,ĉ:`c`,ċ:`c`,Ð:`D`,Đ:`D`,ð:`d`,đ:`d`,Ĕ:`E`,Ė:`E`,ĕ:`e`,ė:`e`,ƒ:`f`,Ĝ:`G`,Ġ:`G`,ĝ:`g`,ġ:`g`,Ĥ:`H`,Ħ:`H`,ĥ:`h`,ħ:`h`,Ĩ:`I`,Ĭ:`I`,Ǐ:`I`,Į:`I`,Ĳ:`IJ`,ĩ:`i`,ĭ:`i`,ǐ:`i`,į:`i`,ĳ:`ij`,Ĵ:`J`,ĵ:`j`,Ĺ:`L`,Ľ:`L`,Ŀ:`L`,ĺ:`l`,ľ:`l`,ŀ:`l`,Ñ:`N`,ñ:`n`,ŉ:`n`,Õ:`O`,Ō:`O`,Ŏ:`O`,Ǒ:`O`,Ő:`O`,Ơ:`O`,Ø:`O`,Ǿ:`O`,Œ:`OE`,õ:`o`,ō:`o`,ŏ:`o`,ǒ:`o`,ő:`o`,ơ:`o`,ø:`o`,ǿ:`o`,º:`o`,œ:`oe`,Ŕ:`R`,Ŗ:`R`,ŕ:`r`,ŗ:`r`,Ŝ:`S`,Ș:`S`,ŝ:`s`,ș:`s`,ſ:`s`,Ţ:`T`,Ț:`T`,Ŧ:`T`,Þ:`TH`,ţ:`t`,ț:`t`,ŧ:`t`,þ:`th`,Ũ:`U`,Ŭ:`U`,Ű:`U`,Ų:`U`,Ư:`U`,Ǔ:`U`,Ǖ:`U`,Ǘ:`U`,Ǚ:`U`,Ǜ:`U`,ũ:`u`,ŭ:`u`,ű:`u`,ų:`u`,ư:`u`,ǔ:`u`,ǖ:`u`,ǘ:`u`,ǚ:`u`,ǜ:`u`,Ŵ:`W`,ŵ:`w`,Ŷ:`Y`,ÿ:`y`,ŷ:`y`,ΑΥ:`AU`,ΑΎ:`AU`,Αυ:`Au`,Αύ:`Au`,ΕΊ:`I`,ΕΙ:`I`,Ει:`Ei`,ΕΥ:`EF`,ΕΎ:`EU`,Εί:`I`,Ευ:`Ef`,Εύ:`Eu`,ΟΙ:`I`,ΟΊ:`I`,ΟΥ:`U`,ΟΎ:`OU`,Οι:`Oi`,Οί:`I`,Ου:`Oy`,Ού:`Ou`,ΥΙ:`I`,ΎΙ:`I`,Υι:`Yi`,Ύι:`I`,ΥΊ:`I`,Υί:`I`,αυ:`au`,αύ:`au`,εί:`i`,ει:`ei`,ευ:`ef`,εύ:`eu`,οι:`oi`,οί:`i`,ου:`oy`,ού:`ou`,υι:`yi`,ύι:`i`,υί:`i`,Α:`A`,Ά:`A`,Β:`B`,Δ:`D`,Ε:`E`,Έ:`E`,Φ:`F`,Γ:`G`,Η:`H`,Ή:`I`,Ι:`I`,Ί:`I`,Ϊ:`I`,Κ:`K`,Ξ:`Ks`,Λ:`L`,Μ:`M`,Ν:`N`,Π:`P`,Ο:`O`,Ό:`O`,Ψ:`Ps`,Ρ:`R`,Σ:`S`,Τ:`T`,Θ:`Th`,Ω:`O`,Ώ:`W`,Χ:`X`,ϒ:`Y`,Υ:`Y`,Ύ:`Y`,Ϋ:`Y`,Ζ:`Z`,α:`a`,ά:`a`,β:`v`,δ:`d`,ε:`e`,έ:`e`,φ:`f`,γ:`gh`,η:`i`,ή:`i`,ι:`i`,ί:`i`,ϊ:`i`,ΐ:`i`,κ:`k`,ξ:`ks`,λ:`l`,μ:`m`,ν:`n`,ο:`o`,ό:`o`,π:`p`,ψ:`ps`,ρ:`r`,σ:`s`,ς:`s`,τ:`t`,ϑ:`th`,θ:`th`,ϐ:`v`,ω:`o`,ώ:`w`,χ:`kh`,υ:`i`,ύ:`y`,ΰ:`y`,ϋ:`y`,ζ:`z`,अ:`a`,आ:`aa`,ए:`e`,ई:`ii`,ऍ:`ei`,ऎ:`ae`,ऐ:`ai`,इ:`i`,ओ:`o`,ऑ:`oi`,ऒ:`oii`,ऊ:`uu`,औ:`ou`,उ:`u`,ब:`B`,भ:`Bha`,च:`Ca`,छ:`Chha`,ड:`Da`,ढ:`Dha`,फ:`Fa`,फ़:`Fi`,ग:`Ga`,घ:`Gha`,ग़:`Ghi`,ह:`Ha`,ज:`Ja`,झ:`Jha`,क:`Ka`,ख:`Kha`,ख़:`Khi`,ल:`L`,ळ:`Li`,ऌ:`Li`,ऴ:`Lii`,ॡ:`Lii`,म:`Ma`,न:`Na`,ङ:`Na`,ञ:`Nia`,ण:`Nae`,ऩ:`Ni`,ॐ:`oms`,प:`Pa`,क़:`Qi`,र:`Ra`,ऋ:`Ri`,ॠ:`Ri`,ऱ:`Ri`,स:`Sa`,श:`Sha`,ष:`Shha`,ट:`Ta`,त:`Ta`,ठ:`Tha`,द:`Tha`,थ:`Tha`,ध:`Thha`,ड़:`ugDha`,ढ़:`ugDhha`,व:`Va`,य:`Ya`,य़:`Yi`,ज़:`Za`,Ա:`A`,Բ:`B`,Գ:`G`,Դ:`D`,Ե:`E`,Զ:`Z`,Է:`E`,Ը:`Y`,Թ:`Th`,Ժ:`Zh`,Ի:`I`,Լ:`L`,Խ:`Kh`,Ծ:`Ts`,Կ:`K`,Հ:`H`,Ձ:`Dz`,Ղ:`Gh`,Ճ:`Tch`,Մ:`M`,Յ:`Y`,Ն:`N`,Շ:`Sh`,Ո:`Vo`,Չ:`Ch`,Պ:`P`,Ջ:`J`,Ռ:`R`,Ս:`S`,Վ:`V`,Տ:`T`,Ր:`R`,Ց:`C`,Ւ:`u`,Փ:`Ph`,Ք:`Q`,և:`ev`,Օ:`O`,Ֆ:`F`,ա:`a`,բ:`b`,գ:`g`,դ:`d`,ե:`e`,զ:`z`,է:`e`,ը:`y`,թ:`th`,ժ:`zh`,ի:`i`,լ:`l`,խ:`kh`,ծ:`ts`,կ:`k`,հ:`h`,ձ:`dz`,ղ:`gh`,ճ:`tch`,մ:`m`,յ:`y`,ն:`n`,շ:`sh`,ո:`vo`,չ:`ch`,պ:`p`,ջ:`j`,ռ:`r`,ս:`s`,վ:`v`,տ:`t`,ր:`r`,ց:`c`,ւ:`u`,փ:`ph`,ք:`q`,օ:`o`,ֆ:`f`,Ž:`Z`,Ň:`N`,Ş:`S`,ž:`z`,ň:`n`,ş:`s`,ı:`i`,İ:`I`,ğ:`g`,Ğ:`G`,ьо:`yo`,Й:`i`,Щ:`Shh`,Ъ:`Ie`,Ь:``,Ю:`Iu`,Я:`Ia`,й:`i`,щ:`shh`,ъ:`ie`,ь:``,ю:`iu`,я:`ia`,Ē:`E`,ē:`e`,န်ုပ်:`nub`,"ောင်":`aung`,"ိုက်":`aik`,"ိုဒ်":`ok`,"ိုင်":`aing`,"ိုလ်":`ol`,"ေါင်":`aung`,သြော:`aw`,"ောက်":`auk`,"ိတ်":`eik`,"ုတ်":`ok`,"ုန်":`on`,"ေတ်":`it`,"ုဒ်":`ait`,"ာန်":`an`,"ိန်":`ein`,"ွတ်":`ut`,"ေါ်":`aw`,"ွန်":`un`,"ိပ်":`eik`,"ုပ်":`ok`,"ွပ်":`ut`,"ိမ်":`ein`,"ုမ်":`on`,"ော်":`aw`,"ွမ်":`un`,က်:`et`,"ေါ":`aw`,"ော":`aw`,"ျွ":`ywa`,"ြွ":`yw`,"ို":`o`,"ုံ":`on`,တ်:`at`,င်:`in`,ည်:`i`,ဒ်:`d`,န်:`an`,ပ်:`at`,မ်:`an`,စျ:`za`,ယ်:`e`,ဉ်:`in`,စ်:`it`,"ိံ":`ein`,"ဲ":`e`,"း":``,"ာ":`a`,"ါ":`a`,"ေ":`e`,"ံ":`an`,"ိ":`i`,"ီ":`i`,"ု":`u`,"ူ":`u`,"်":`at`,"္":``,"့":``,က:`k`,"၉":`9`,တ:`t`,ရ:`ya`,ယ:`y`,မ:`m`,ဘ:`ba`,ဗ:`b`,ဖ:`pa`,ပ:`p`,န:`n`,ဓ:`da`,ဒ:`d`,ထ:`ta`,ဏ:`na`,ဝ:`w`,ဎ:`da`,ဍ:`d`,ဌ:`ta`,ဋ:`t`,ည:`ny`,ဇ:`z`,ဆ:`sa`,စ:`s`,င:`ng`,ဃ:`ga`,ဂ:`g`,လ:`l`,သ:`th`,"၈":`8`,ဩ:`aw`,ခ:`kh`,"၆":`6`,"၅":`5`,"၄":`4`,"၃":`3`,"၂":`2`,"၁":`1`,"၀":`0`,"၌":`hnaik`,"၍":`ywae`,ဪ:`aw`,ဦ:`-u`,ဟ:`h`,ဉ:`u`,ဤ:`-i`,ဣ:`i`,"၏":`-e`,ဧ:`e`,"ှ":`h`,"ွ":`w`,"ျ":`ya`,"ြ":`y`,အ:`a`,ဠ:`la`,"၇":`7`,DŽ:`DZ`,Dž:`Dz`,dž:`dz`,Ǳ:`DZ`,ǲ:`Dz`,ǳ:`dz`,Ǉ:`LJ`,ǈ:`Lj`,ǉ:`lj`,Ǌ:`NJ`,ǋ:`Nj`,ǌ:`nj`,č:`c`,Č:`C`,ć:`c`,Ć:`C`,š:`s`,Š:`S`,ა:`a`,ბ:`b`,გ:`g`,დ:`d`,ე:`e`,ვ:`v`,ზ:`z`,თ:`t`,ი:`i`,კ:`k`,ლ:`l`,მ:`m`,ნ:`n`,ო:`o`,პ:`p`,ჟ:`zh`,რ:`r`,ს:`s`,ტ:`t`,უ:`u`,ფ:`f`,ქ:`q`,ღ:`gh`,ყ:`y`,შ:`sh`,ჩ:`ch`,ც:`ts`,ძ:`dz`,წ:`ts`,ჭ:`ch`,ხ:`kh`,ჯ:`j`,ჰ:`h`,Ё:`E`,ё:`e`,Ы:`Y`,ы:`y`,Э:`E`,э:`e`,І:`I`,і:`i`,Ѳ:`F`,ѳ:`f`,Ѣ:`E`,ѣ:`e`,Ѵ:`I`,ѵ:`i`,Є:`Je`,є:`je`,Ѥ:`Je`,ѥ:`je`,Ꙋ:`U`,ꙋ:`u`,Ѡ:`O`,ѡ:`o`,Ѿ:`Ot`,ѿ:`ot`,Ѫ:`U`,ѫ:`u`,Ѧ:`Ja`,ѧ:`ja`,Ѭ:`Ju`,ѭ:`ju`,Ѩ:`Ja`,ѩ:`Ja`,Ѯ:`Ks`,ѯ:`ks`,Ѱ:`Ps`,ѱ:`ps`,Ґ:`G`,ґ:`g`,Ї:`Yi`,ї:`yi`,Ә:`A`,Ғ:`G`,Қ:`Q`,Ң:`N`,Ө:`O`,Ұ:`U`,Ү:`U`,Һ:`H`,ә:`a`,ғ:`g`,қ:`q`,ң:`n`,ө:`o`,ұ:`u`,ү:`u`,һ:`h`,ď:`d`,Ď:`D`,ě:`e`,Ě:`E`,ř:`r`,Ř:`R`,ť:`t`,Ť:`T`,ů:`u`,Ů:`U`,ą:`a`,ę:`e`,ł:`l`,ń:`n`,ś:`s`,ź:`z`,ż:`z`,Ą:`A`,Ę:`E`,Ł:`L`,Ń:`N`,Ś:`S`,Ź:`Z`,Ż:`Z`,ā:`a`,ģ:`g`,ī:`i`,ķ:`k`,ļ:`l`,ņ:`n`,ū:`u`,Ā:`A`,Ģ:`G`,Ī:`I`,Ķ:`k`,Ļ:`L`,Ņ:`N`,Ū:`U`,Ả:`A`,Ạ:`A`,Ắ:`A`,Ằ:`A`,Ẳ:`A`,Ẵ:`A`,Ặ:`A`,Ấ:`A`,Ầ:`A`,Ẩ:`A`,Ẫ:`A`,Ậ:`A`,ả:`a`,ạ:`a`,ắ:`a`,ằ:`a`,ẳ:`a`,ẵ:`a`,ặ:`a`,ấ:`a`,ầ:`a`,ẩ:`a`,ẫ:`a`,ậ:`a`,Ẻ:`E`,Ẽ:`E`,Ẹ:`E`,Ế:`E`,Ề:`E`,Ể:`E`,Ễ:`E`,Ệ:`E`,ẻ:`e`,ẽ:`e`,ẹ:`e`,ế:`e`,ề:`e`,ể:`e`,ễ:`e`,ệ:`e`,Ỉ:`I`,Ị:`I`,ỉ:`i`,ị:`i`,Ỏ:`O`,Ọ:`O`,Ố:`O`,Ồ:`O`,Ổ:`O`,Ỗ:`O`,Ộ:`O`,Ớ:`O`,Ờ:`O`,Ở:`O`,Ỡ:`O`,Ợ:`O`,ỏ:`o`,ọ:`o`,ố:`o`,ồ:`o`,ổ:`o`,ỗ:`o`,ộ:`o`,ớ:`o`,ờ:`o`,ở:`o`,ỡ:`o`,ợ:`o`,Ủ:`U`,Ụ:`U`,Ứ:`U`,Ừ:`U`,Ử:`U`,Ữ:`U`,Ự:`U`,ủ:`u`,ụ:`u`,ứ:`u`,ừ:`u`,ử:`u`,ữ:`u`,ự:`u`,Ỳ:`Y`,Ỷ:`Y`,Ỹ:`Y`,Ỵ:`Y`,ỳ:`y`,ỷ:`y`,ỹ:`y`,ỵ:`y`,ا:`a`,ب:`b`,پ:`p`,ت:`t`,ث:`th`,ج:`g`,چ:`ch`,ح:`h`,خ:`kh`,د:`d`,ذ:`th`,ر:`r`,ز:`z`,س:`s`,ش:`sh`,ص:`s`,ض:`d`,ط:`t`,ظ:`th`,ع:`aa`,غ:`gh`,ف:`f`,ق:`k`,ک:`k`,گ:`g`,ل:`l`,ژ:`zh`,ك:`k`,م:`m`,ن:`n`,ه:`h`,و:`o`,ی:`y`,آ:`a`,"٠":`0`,"١":`1`,"٢":`2`,"٣":`3`,"٤":`4`,"٥":`5`,"٦":`6`,"٧":`7`,"٨":`8`,"٩":`9`,أ:`a`,ي:`y`,إ:`a`,ؤ:`o`,ئ:`y`,ء:`aa`,ђ:`dj`,ћ:`c`,Ђ:`Dj`,Ћ:`C`,ə:`e`,Ə:`E`,ß:`ss`,ẞ:`SS`,ভ্ল:`vl`,পশ:`psh`,ব্ধ:`bdh`,ব্জ:`bj`,ব্দ:`bd`,ব্ব:`bb`,ব্ল:`bl`,ভ:`v`,ব:`b`,চ্ঞ:`cNG`,চ্ছ:`cch`,চ্চ:`cc`,ছ:`ch`,চ:`c`,ধ্ন:`dhn`,ধ্ম:`dhm`,দ্ঘ:`dgh`,দ্ধ:`ddh`,দ্ভ:`dv`,দ্ম:`dm`,ড্ড:`DD`,ঢ:`Dh`,ধ:`dh`,দ্গ:`dg`,দ্দ:`dd`,ড:`D`,দ:`d`,"।":`.`,ঘ্ন:`Ghn`,গ্ধ:`Gdh`,গ্ণ:`GN`,গ্ন:`Gn`,গ্ম:`Gm`,গ্ল:`Gl`,জ্ঞ:`jNG`,ঘ:`Gh`,গ:`g`,হ্ণ:`hN`,হ্ন:`hn`,হ্ম:`hm`,হ্ল:`hl`,হ:`h`,জ্ঝ:`jjh`,ঝ:`jh`,জ্জ:`jj`,জ:`j`,ক্ষ্ণ:`kxN`,ক্ষ্ম:`kxm`,ক্ষ:`ksh`,কশ:`ksh`,ক্ক:`kk`,ক্ট:`kT`,ক্ত:`kt`,ক্ল:`kl`,ক্স:`ks`,খ:`kh`,ক:`k`,ল্ভ:`lv`,ল্ধ:`ldh`,লখ:`lkh`,লঘ:`lgh`,লফ:`lph`,ল্ক:`lk`,ল্গ:`lg`,ল্ট:`lT`,ল্ড:`lD`,ল্প:`lp`,ল্ম:`lm`,ল্ল:`ll`,ল্ব:`lb`,ল:`l`,ম্থ:`mth`,ম্ফ:`mf`,ম্ভ:`mv`,মপ্ল:`mpl`,ম্ন:`mn`,ম্প:`mp`,ম্ম:`mm`,ম্ল:`ml`,ম্ব:`mb`,ম:`m`,"০":`0`,"১":`1`,"২":`2`,"৩":`3`,"৪":`4`,"৫":`5`,"৬":`6`,"৭":`7`,"৮":`8`,"৯":`9`,ঙ্ক্ষ:`Ngkx`,ঞ্ছ:`nch`,ঙ্ঘ:`ngh`,ঙ্খ:`nkh`,ঞ্ঝ:`njh`,ঙ্গৌ:`ngOU`,ঙ্গৈ:`ngOI`,ঞ্চ:`nc`,ঙ্ক:`nk`,ঙ্ষ:`Ngx`,ঙ্গ:`ngo`,ঙ্ম:`Ngm`,ঞ্জ:`nj`,ন্ধ:`ndh`,ন্ঠ:`nTh`,ণ্ঠ:`NTh`,ন্থ:`nth`,ঙ্গা:`nga`,ঙ্গি:`ngi`,ঙ্গী:`ngI`,ঙ্গু:`ngu`,ঙ্গূ:`ngU`,ঙ্গে:`nge`,ঙ্গো:`ngO`,ণ্ঢ:`NDh`,নশ:`nsh`,ঙর:`Ngr`,ঞর:`NGr`,"ংর":`ngr`,ঙ:`Ng`,ঞ:`NG`,"ং":`ng`,ন্ন:`nn`,ণ্ণ:`NN`,ণ্ন:`Nn`,ন্ম:`nm`,ণ্ম:`Nm`,ন্দ:`nd`,ন্ট:`nT`,ণ্ট:`NT`,ন্ড:`nD`,ণ্ড:`ND`,ন্ত:`nt`,ন্স:`ns`,ন:`n`,ণ:`N`,"ৈ":`OI`,"ৌ":`OU`,"ো":`O`,ঐ:`OI`,ঔ:`OU`,অ:`o`,ও:`oo`,ফ্ল:`fl`,প্ট:`pT`,প্ত:`pt`,প্ন:`pn`,প্প:`pp`,প্ল:`pl`,প্স:`ps`,ফ:`f`,প:`p`,"ৃ":`rri`,ঋ:`rri`,রর‍্য:`rry`,"্র্য":`ry`,"্রর":`rr`,ড়্গ:`Rg`,ঢ়:`Rh`,ড়:`R`,র:`r`,"্র":`r`,শ্ছ:`Sch`,ষ্ঠ:`ShTh`,ষ্ফ:`Shf`,স্ক্ল:`skl`,স্খ:`skh`,স্থ:`sth`,স্ফ:`sf`,শ্চ:`Sc`,শ্ত:`St`,শ্ন:`Sn`,শ্ম:`Sm`,শ্ল:`Sl`,ষ্ক:`Shk`,ষ্ট:`ShT`,ষ্ণ:`ShN`,ষ্প:`Shp`,ষ্ম:`Shm`,স্প্ল:`spl`,স্ক:`sk`,স্ট:`sT`,স্ত:`st`,স্ন:`sn`,স্প:`sp`,স্ম:`sm`,স্ল:`sl`,শ:`S`,ষ:`Sh`,স:`s`,"ু":`u`,উ:`u`,অ্য:`oZ`,ত্থ:`tth`,ৎ:`tt`,ট্ট:`TT`,ট্ম:`Tm`,ঠ:`Th`,ত্ন:`tn`,ত্ম:`tm`,থ:`th`,ত্ত:`tt`,ট:`T`,ত:`t`,অ্যা:`AZ`,"া":`a`,আ:`a`,য়া:`ya`,য়:`y`,"ি":`i`,ই:`i`,"ী":`ee`,ঈ:`ee`,"ূ":`uu`,ঊ:`uu`,"ে":`e`,এ:`e`,য:`z`,"্য":`Z`,ইয়:`y`,ওয়:`w`,"্ব":`w`,এক্স:`x`,"ঃ":`:`,"ঁ":`nn`,"্‌":``,"˚":`0`,"¹":`1`,"²":`2`,"³":`3`,"⁴":`4`,"⁵":`5`,"⁶":`6`,"⁷":`7`,"⁸":`8`,"⁹":`9`,"₀":`0`,"₁":`1`,"₂":`2`,"₃":`3`,"₄":`4`,"₅":`5`,"₆":`6`,"₇":`7`,"₈":`8`,"₉":`9`,"௦":`0`,"௧":`1`,"௨":`2`,"௩":`3`,"௪":`4`,"௫":`5`,"௬":`6`,"௭":`7`,"௮":`8`,"௯":`9`,"௰":`10`,"௱":`100`,"௲":`1000`,Ꜳ:`AA`,ꜳ:`aa`,Ꜵ:`AO`,ꜵ:`ao`,Ꜷ:`AU`,ꜷ:`au`,Ꜹ:`AV`,ꜹ:`av`,Ꜻ:`av`,ꜻ:`av`,Ꜽ:`AY`,ꜽ:`ay`,ȸ:`db`,ʣ:`dz`,ʥ:`dz`,ʤ:`dezh`,"🙰":`et`,ﬀ:`ff`,ﬃ:`ffi`,ﬄ:`ffl`,ﬁ:`fi`,ﬂ:`fl`,ʩ:`feng`,ʪ:`ls`,ʫ:`lz`,ɮ:`lezh`,ȹ:`qp`,ʨ:`tc`,ʦ:`ts`,ʧ:`tesh`,Ꝏ:`OO`,ꝏ:`oo`,ﬆ:`st`,ﬅ:`st`,Ꜩ:`TZ`,ꜩ:`tz`,ᵫ:`ue`,Aι:`Ai`,αι:`ai`,ἀ:`a`,ἁ:`a`,ἂ:`a`,ἃ:`a`,ἄ:`a`,ἅ:`a`,ἆ:`a`,ἇ:`a`,Ἀ:`A`,Ἁ:`A`,Ἂ:`A`,Ἃ:`A`,Ἄ:`A`,Ἅ:`A`,Ἆ:`A`,Ἇ:`A`,ᾰ:`a`,ᾱ:`a`,ᾲ:`a`,ᾳ:`a`,ᾴ:`a`,ᾶ:`a`,ᾷ:`a`,Ᾰ:`A`,Ᾱ:`A`,Ὰ:`A`,Ά:`A`,ᾼ:`A`,A̧:`A`,a̧:`a`,Ⱥ:`A`,ⱥ:`a`,Ȧ:`A`,ȧ:`a`,Ɓ:`B`,C̈:`C`,c̈:`c`,C̨:`C`,c̨:`c`,Ȼ:`C`,ȼ:`c`,C̀:`C`,c̀:`c`,C̣:`C`,c̣:`c`,C̄:`C`,c̄:`c`,C̃:`C`,c̃:`c`,Ȩ:`E`,ȩ:`e`,Ɇ:`E`,ɇ:`e`,I̧:`I`,i̧:`i`,Ɨ:`I`,ɨ:`i`,i:`i`,J́́:`J`,j́:`j`,J̀̀:`J`,j̀:`j`,J̈:`J`,j̈:`j`,J̧:`J`,j̧:`j`,J̨:`J`,j̨:`j`,Ɉ:`J`,ɉ:`j`,J̌:`J`,ǰ:`j`,J̇:`J`,j:`j`,J̣:`J`,j̣:`j`,J̄:`J`,j̄:`j`,J̃:`J`,j̃:`j`,ĸ:`k`,L̀:`L`,l̀:`l`,L̂:`L`,l̂:`l`,L̈:`L`,l̈:`l`,L̨:`L`,l̨:`l`,Ƚ:`L`,ƚ:`l`,L̇:`L`,l̇:`l`,Ḷ:`L`,ḷ:`l`,L̄:`L`,l̄:`l`,L̃:`L`,l̃:`l`,Ŋ:`N`,ŋ:`n`,Ǹ:`N`,ǹ:`n`,N̂:`N`,n̂:`n`,N̈:`N`,n̈:`n`,N̨:`N`,n̨:`n`,Ꞥ:`N`,ꞥ:`n`,Ṅ:`N`,ṅ:`n`,Ṇ:`N`,ṇ:`n`,N̄:`N`,n̄:`n`,O̧:`O`,o̧:`o`,Ǫ:`O`,ǫ:`o`,Ɵ:`O`,ɵ:`o`,Ȯ:`O`,ȯ:`o`,S̀:`S`,s̀:`s`,Ŝ̀:`S`,S̈:`S`,s̈:`s`,S̨:`S`,s̨:`s`,Ꞩ:`S`,ꞩ:`s`,Ṡ:`S`,ṡ:`s`,Ṣ:`S`,ṣ:`s`,S̄:`S`,s̄:`s`,S̃:`S`,s̃:`s`,T́:`T`,t́:`t`,T̀:`T`,t̀:`t`,T̂:`T`,t̂:`t`,T̈:`T`,ẗ:`t`,T̨:`T`,t̨:`t`,Ⱦ:`T`,ⱦ:`t`,Ṫ:`T`,ṫ:`t`,Ṭ:`T`,ṭ:`t`,T̄:`T`,t̄:`t`,T̃:`T`,t̃:`t`,U̧:`U`,u̧:`u`,Ʉ:`U`,ʉ:`u`,U̇:`U`,u̇:`u`,Ʊ:`U`,ʊ:`u`,Ẁ:`W`,ẁ:`w`,Ẃ:`W`,ẃ:`w`,Ẅ:`W`,ẅ:`w`,Ꙗ:`Ja`,ꙗ:`ja`,Y̧:`Y`,y̧:`y`,Y̨:`Y`,y̨:`y`,Ɏ:`Y`,ɏ:`y`,Y̌:`Y`,y̌:`y`,Ẏ:`Y`,ẏ:`y`,Ȳ:`Y`,ȳ:`y`,Z̀:`Z`,z̀:`z`,Ẑ:`Z`,ẑ:`z`,Z̈:`Z`,z̈:`z`,Z̧:`Z`,z̧:`z`,Z̨:`Z`,z̨:`z`,Ƶ:`Z`,ƶ:`z`,Ẓ:`Z`,ẓ:`z`,Z̄:`Z`,z̄:`z`,Z̃:`Z`,z̃:`z`,"\xA0":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `,"\u2028":` `,"\u2029":` `,"​":` `," ":` `," ":` `,"　":` `,ﾠ:` `,"«":`<<`,"»":`>>`,"‘":`'`,"’":`'`,"‚":`'`,"‛":`'`,"“":`"`,"”":`"`,"„":`"`,"‟":`"`,"‹":`'`,"›":`'`,"–":`-`,"—":`-`,"…":`...`,"€":`EUR`,$:`$`,"₢":`Cr`,"₣":`Fr.`,"£":`PS`,"₤":`L.`,ℳ:`M`,"₥":`mil`,"₦":`N`,"₧":`Pts`,"₨":`Rs`,රු:`LKR`,ரூ:`LKR`,"௹":`Rs`,रू:`NPR`,"₹":`Rs`,"૱":`Rs`,"₩":`W`,"₪":`NS`,"₸":`KZT`,"₫":`D`,"֏":`AMD`,"₭":`K`,"₺":`TL`,"₼":`AZN`,"₮":`T`,"₯":`Dr`,"₲":`PYG`,"₾":`GEL`,"₳":`ARA`,"₴":`UAH`,"₽":`RUB`,"₵":`GHS`,"₡":`CL`,"¢":`c`,"¥":`YEN`,円:`JPY`,"৳":`BDT`,元:`CNY`,"﷼":`SAR`,"៛":`KR`,"₠":`ECU`,"¤":`$?`,"฿":`THB`,"؋":`AFN`};function qd(e,t=Kd){e=e.normalize(`NFC`);let n=``,r;for(let i=0;i<e.length;i++)r=e.charAt(i),n+=typeof t[r]==`string`?t[r]:r;return n}function Jd(e,t={}){let n={allowNonAlphaStart:!1,handleCasing:`camel`,...t};var r=e.replace(/<(.*?)>/g,``);r=r.replace(/['"‘’“”ʻ\[\]\(\)\{\}:]/g,``),r=r.toLowerCase(),r=qd(r),n.allowNonAlphaStart||(r=r.replace(/^[^a-z]+/,``));let i=r.split(/[^a-z0-9]+/).filter(Boolean);if(r=``,n.handleCasing===`snake`)return i.join(`_`);for(let e=0;e<i.length;e++)n.handleCasing!==`pascal`&&e===0?r+=i[e]:r+=i[e].charAt(0).toUpperCase()+i[e].substring(1);return r}function Yd(e,t={}){let n={prefix:``,suffix:``,...t},r=Jd(e,{handleCasing:`snake`}).toUpperCase();return r?`${n.prefix}${r}${n.suffix}`:``}function Xd(e){let t=e.replace(/<(.*?)>/g,``);return t=t.toLowerCase(),t=qd(t),t=t.replace(/^[^a-z]+/,``),t=t.replace(/[^a-z0-9]+$/,``),t.split(/[^a-z0-9]+/).filter(Boolean).join(`-`)}function Zd(e){return typeof e==`symbol`||e instanceof Symbol}var Qd=typeof globalThis==`object`&&globalThis||typeof window==`object`&&window||typeof self==`object`&&self||typeof global==`object`&&global||(function(){return this})()||Function(`return this`)();function $d(e,t,{signal:n,edges:r}={}){let i,a=null,o=r!=null&&r.includes(`leading`),s=r==null||r.includes(`trailing`),c=()=>{a!==null&&(e.apply(i,a),i=void 0,a=null)},l=()=>{s&&c(),p()},u=null,d=()=>{u!=null&&clearTimeout(u),u=setTimeout(()=>{u=null,l()},t)},f=()=>{u!==null&&(clearTimeout(u),u=null)},p=()=>{f(),i=void 0,a=null},m=()=>{c()},h=function(...e){if(n?.aborted)return;i=this,a=e;let t=u==null;d(),o&&t&&c()};return h.schedule=d,h.cancel=p,h.flush=m,n?.addEventListener(`abort`,p,{once:!0}),h}function ef(){}function tf(e){return e==null||typeof e!=`object`&&typeof e!=`function`}function nf(e){return ArrayBuffer.isView(e)&&!(e instanceof DataView)}function rf(e){if(tf(e))return e;if(Array.isArray(e)||nf(e)||e instanceof ArrayBuffer||typeof SharedArrayBuffer<`u`&&e instanceof SharedArrayBuffer)return e.slice(0);let t=Object.getPrototypeOf(e);if(t==null)return Object.assign(Object.create(t),e);let n=t.constructor;if(e instanceof Date||e instanceof Map||e instanceof Set)return new n(e);if(e instanceof RegExp){let t=new n(e);return t.lastIndex=e.lastIndex,t}if(e instanceof DataView)return new n(e.buffer.slice(0));if(e instanceof Error){let t;return t=e instanceof AggregateError?new n(e.errors,e.message,{cause:e.cause}):new n(e.message,{cause:e.cause}),t.stack=e.stack,Object.assign(t,e),t}return typeof File<`u`&&e instanceof File?new n([e],e.name,{type:e.type,lastModified:e.lastModified}):typeof e==`object`?Object.assign(Object.create(t),e):e}function af(e){return Object.getOwnPropertySymbols(e).filter(t=>Object.prototype.propertyIsEnumerable.call(e,t))}function of(e){return e==null?e===void 0?`[object Undefined]`:`[object Null]`:Object.prototype.toString.call(e)}var sf=`[object RegExp]`,cf=`[object String]`,lf=`[object Number]`,uf=`[object Boolean]`,df=`[object Arguments]`,ff=`[object Symbol]`,pf=`[object Date]`,mf=`[object Map]`,hf=`[object Set]`,gf=`[object Array]`,_f=`[object Function]`,vf=`[object ArrayBuffer]`,yf=`[object Object]`,bf=`[object Error]`,xf=`[object DataView]`,Sf=`[object Uint8Array]`,Cf=`[object Uint8ClampedArray]`,wf=`[object Uint16Array]`,Tf=`[object Uint32Array]`,Ef=`[object BigUint64Array]`,Df=`[object Int8Array]`,Of=`[object Int16Array]`,kf=`[object Int32Array]`,Af=`[object BigInt64Array]`,jf=`[object Float32Array]`,Mf=`[object Float64Array]`;function Nf(e){return Qd.Buffer!==void 0&&Qd.Buffer.isBuffer(e)}function Pf(e,t){return Ff(e,void 0,e,new Map,t)}function Ff(e,t,n,r=new Map,i=void 0){let a=i?.(e,t,n,r);if(a!==void 0)return a;if(tf(e))return e;if(r.has(e))return r.get(e);if(Array.isArray(e)){let t=Array(e.length);r.set(e,t);for(let a=0;a<e.length;a++)t[a]=Ff(e[a],a,n,r,i);return Object.hasOwn(e,`index`)&&(t.index=e.index),Object.hasOwn(e,`input`)&&(t.input=e.input),t}if(e instanceof Date)return new Date(e.getTime());if(e instanceof RegExp){let t=new RegExp(e.source,e.flags);return t.lastIndex=e.lastIndex,t}if(e instanceof Map){let t=new Map;r.set(e,t);for(let[a,o]of e)t.set(a,Ff(o,a,n,r,i));return t}if(e instanceof Set){let t=new Set;r.set(e,t);for(let a of e)t.add(Ff(a,void 0,n,r,i));return t}if(Nf(e))return e.subarray();if(nf(e)){let t=new(Object.getPrototypeOf(e)).constructor(e.length);r.set(e,t);for(let a=0;a<e.length;a++)t[a]=Ff(e[a],a,n,r,i);return t}if(e instanceof ArrayBuffer||typeof SharedArrayBuffer<`u`&&e instanceof SharedArrayBuffer)return e.slice(0);if(e instanceof DataView){let t=new DataView(e.buffer.slice(0),e.byteOffset,e.byteLength);return r.set(e,t),If(t,e,n,r,i),t}if(typeof File<`u`&&e instanceof File){let t=new File([e],e.name,{type:e.type});return r.set(e,t),If(t,e,n,r,i),t}if(typeof Blob<`u`&&e instanceof Blob){let t=new Blob([e],{type:e.type});return r.set(e,t),If(t,e,n,r,i),t}if(e instanceof Error){let t=structuredClone(e);return r.set(e,t),t.message=e.message,t.name=e.name,t.stack=e.stack,t.cause=e.cause,t.constructor=e.constructor,If(t,e,n,r,i),t}if(e instanceof Boolean){let t=new Boolean(e.valueOf());return r.set(e,t),If(t,e,n,r,i),t}if(e instanceof Number){let t=new Number(e.valueOf());return r.set(e,t),If(t,e,n,r,i),t}if(e instanceof String){let t=new String(e.valueOf());return r.set(e,t),If(t,e,n,r,i),t}if(typeof e==`object`&&Lf(e)){let t=Object.create(Object.getPrototypeOf(e));return r.set(e,t),If(t,e,n,r,i),t}return e}function If(e,t,n=e,r,i){let a=[...Object.keys(t),...af(t)];for(let o=0;o<a.length;o++){let s=a[o],c=Object.getOwnPropertyDescriptor(e,s);(c==null||c.writable)&&(e[s]=Ff(t[s],s,n,r,i))}}function Lf(e){switch(of(e)){case df:case gf:case vf:case xf:case uf:case pf:case jf:case Mf:case Df:case Of:case kf:case mf:case lf:case yf:case sf:case hf:case cf:case ff:case Sf:case Cf:case wf:case Tf:return!0;default:return!1}}function V(e){return Ff(e,void 0,e,new Map,void 0)}function Rf(e){if(!e||typeof e!=`object`)return!1;let t=Object.getPrototypeOf(e);return t===null||t===Object.prototype||Object.getPrototypeOf(t)===null?Object.prototype.toString.call(e)===`[object Object]`:!1}function zf(e){return e===`__proto__`}function Bf(e){if(typeof e!=`object`||!e)return!1;if(Object.getPrototypeOf(e)===null)return!0;if(Object.prototype.toString.call(e)!==`[object Object]`){let t=e[Symbol.toStringTag];return t==null||!Object.getOwnPropertyDescriptor(e,Symbol.toStringTag)?.writable?!1:e.toString()===`[object ${t}]`}let t=e;for(;Object.getPrototypeOf(t)!==null;)t=Object.getPrototypeOf(t);return Object.getPrototypeOf(e)===t}function Vf(e,t){return e===t||Number.isNaN(e)&&Number.isNaN(t)}function Hf(e,t,n){return Uf(e,t,void 0,void 0,void 0,void 0,n)}function Uf(e,t,n,r,i,a,o){let s=o(e,t,n,r,i,a);if(s!==void 0)return s;if(typeof e==typeof t)switch(typeof e){case`bigint`:case`string`:case`boolean`:case`symbol`:case`undefined`:return e===t;case`number`:return e===t||Object.is(e,t);case`function`:return e===t;case`object`:return Wf(e,t,a,o)}return Wf(e,t,a,o)}function Wf(e,t,n,r){if(Object.is(e,t))return!0;let i=of(e),a=of(t);if(i===`[object Arguments]`&&(i=yf),a===`[object Arguments]`&&(a=yf),i!==a)return!1;switch(i){case cf:return e.toString()===t.toString();case lf:return Vf(e.valueOf(),t.valueOf());case uf:case pf:case ff:return Object.is(e.valueOf(),t.valueOf());case sf:return e.source===t.source&&e.flags===t.flags;case _f:return e===t}n??=new Map;let o=n.get(e),s=n.get(t);if(o!=null&&s!=null)return o===t;n.set(e,t),n.set(t,e);try{switch(i){case mf:if(e.size!==t.size)return!1;for(let[i,a]of e.entries())if(!t.has(i)||!Uf(a,t.get(i),i,e,t,n,r))return!1;return!0;case hf:{if(e.size!==t.size)return!1;let i=Array.from(e.values()),a=Array.from(t.values());for(let o=0;o<i.length;o++){let s=i[o],c=a.findIndex(i=>Uf(s,i,void 0,e,t,n,r));if(c===-1)return!1;a.splice(c,1)}return!0}case gf:case Sf:case Cf:case wf:case Tf:case Ef:case Df:case Of:case kf:case Af:case jf:case Mf:if(Nf(e)!==Nf(t)||e.length!==t.length)return!1;for(let i=0;i<e.length;i++)if(!Uf(e[i],t[i],i,e,t,n,r))return!1;return!0;case vf:return e.byteLength===t.byteLength?Wf(new Uint8Array(e),new Uint8Array(t),n,r):!1;case xf:return e.byteLength!==t.byteLength||e.byteOffset!==t.byteOffset?!1:Wf(new Uint8Array(e),new Uint8Array(t),n,r);case bf:return e.name===t.name&&e.message===t.message;case yf:{if(!(Wf(e.constructor,t.constructor,n,r)||Rf(e)&&Rf(t)))return!1;let i=[...Object.keys(e),...af(e)],a=[...Object.keys(t),...af(t)];if(i.length!==a.length)return!1;for(let a=0;a<i.length;a++){let o=i[a],s=e[o];if(!Object.hasOwn(t,o))return!1;let c=t[o];if(!Uf(s,c,o,e,t,n,r))return!1}return!0}default:return!1}}finally{n.delete(e),n.delete(t)}}function Gf(e,t){return Hf(e,t,ef)}function Kf(e){return Number.isSafeInteger(e)&&e>=0}var qf={"&":`&amp;`,"<":`&lt;`,">":`&gt;`,'"':`&quot;`,"'":`&#39;`};function Jf(e){return e.replace(/[&<>"']/g,e=>qf[e])}function Yf(e){return e!=null&&typeof e!=`function`&&Kf(e.length)}function Xf(e){switch(typeof e){case`number`:case`symbol`:return!1;case`string`:return e.includes(`.`)||e.includes(`[`)||e.includes(`]`)}}function Zf(e){return typeof e==`string`||typeof e==`symbol`?e:Object.is(e?.valueOf?.(),-0)?`-0`:String(e)}function Qf(e){if(e==null)return``;if(typeof e==`string`)return e;if(Array.isArray(e))return e.map(Qf).join(`,`);let t=String(e);return t===`0`&&Object.is(Number(e),-0)?`-0`:t}function $f(e){if(Array.isArray(e))return e.map(Zf);if(typeof e==`symbol`)return[e];e=Qf(e);let t=[],n=e.length;if(n===0)return t;let r=0,i=``,a=``,o=!1;for(e.charCodeAt(0)===46&&(t.push(``),r++);r<n;){let s=e[r];a?s===`\\`&&r+1<n?(r++,i+=e[r]):s===a?a=``:i+=s:o?s===`"`||s===`'`?a=s:s===`]`?(o=!1,t.push(i),i=``):i+=s:s===`[`?(o=!0,i&&=(t.push(i),``)):s===`.`?i&&=(t.push(i),``):i+=s,r++}return i&&t.push(i),t}function ep(e,t,n){if(e==null)return n;switch(typeof t){case`string`:{if(zf(t))return n;let r=e[t];return r===void 0?Xf(t)?ep(e,$f(t),n):n:r}case`number`:case`symbol`:{typeof t==`number`&&(t=Zf(t));let r=e[t];return r===void 0?n:r}default:{if(Array.isArray(t))return tp(e,t,n);if(t=Object.is(t?.valueOf(),-0)?`-0`:String(t),zf(t))return n;let r=e[t];return r===void 0?n:r}}}function tp(e,t,n){if(t.length===0)return n;let r=e;for(let e=0;e<t.length;e++){if(r==null||zf(t[e]))return n;r=r[t[e]]}return r===void 0?n:r}function np(e){return e!==null&&(typeof e==`object`||typeof e==`function`)}function rp(e,t){return Pf(e,(n,r,i,a)=>{let o=t?.(n,r,i,a);if(o!==void 0)return o;if(typeof e==`object`){if(of(e)===`[object Object]`&&typeof e.constructor!=`function`){let t={};return a.set(e,t),If(t,e,i,a),t}switch(Object.prototype.toString.call(e)){case lf:case cf:case uf:{let t=new e.constructor(e?.valueOf());return If(t,e),t}case df:{let t={};return If(t,e),t.length=e.length,t[Symbol.iterator]=e[Symbol.iterator],t}default:return}}})}function ip(e){return rp(e)}var ap=/^(?:0|[1-9]\d*)$/;function op(e,t=2**53-1){switch(typeof e){case`number`:return Number.isInteger(e)&&e>=0&&e<t;case`symbol`:return!1;case`string`:return ap.test(e)}}function sp(e){return typeof e==`object`&&!!e&&of(e)===`[object Arguments]`}function cp(e,t){let n;if(n=Array.isArray(t)?t:typeof t==`string`&&Xf(t)&&e?.[t]==null?$f(t):[t],n.length===0)return!1;let r=e;for(let e=0;e<n.length;e++){let t=n[e];if((r==null||!Object.hasOwn(r,t))&&!((Array.isArray(r)||sp(r))&&op(t)&&t<r.length))return!1;r=r[t]}return!0}function lp(e){return typeof e==`object`&&!!e}function up(e){return lp(e)&&Yf(e)}var dp=/\.|\[(?:[^[\]]*|(["'])(?:(?!\1)[^\\]|\\.)*?\1)\]/,fp=/^\w*$/;function pp(e,t){return Array.isArray(e)?!1:typeof e==`number`||typeof e==`boolean`||e==null||Zd(e)?!0:typeof e==`string`&&(fp.test(e)||!dp.test(e))||t!=null&&Object.hasOwn(t,e)}var mp=(e,t,n)=>{let r=e[t];(!(Object.hasOwn(e,t)&&Vf(r,n))||n===void 0&&!(t in e))&&(e[t]=n)};function hp(e,t,n,r){if(e==null&&!np(e))return e;let i;i=pp(t,e)?[t]:Array.isArray(t)?t:$f(t);let a=n(ep(e,i)),o=e;for(let t=0;t<i.length&&o!=null;t++){let n=Zf(i[t]);if(zf(n))continue;let s;if(t===i.length-1)s=a;else{let a=o[n],c=r?.(a,n,e);s=c===void 0?np(a)?a:op(i[t+1])?[]:{}:c}mp(o,n,s),o=o[n]}return e}function gp(e,t,n){return hp(e,t,()=>n,()=>void 0)}function _p(e,t=0,n={}){typeof n!=`object`&&(n={});let{leading:r=!1,trailing:i=!0,maxWait:a}=n,o=[,,];r&&(o[0]=`leading`),i&&(o[1]=`trailing`);let s,c=null,l=$d(function(...t){s=e.apply(this,t),c=null},t,{edges:o}),u=function(...t){return a!=null&&(c===null&&(c=Date.now()),Date.now()-c>=a)?(s=e.apply(this,t),c=Date.now(),l.cancel(),l.schedule(),s):(l.apply(this,t),s)};return u.cancel=l.cancel,u.flush=()=>(l.flush(),s),u}function vp(e){return nf(e)}function yp(e,...t){let n=t.slice(0,-1),r=t[t.length-1],i=e;for(let e=0;e<n.length;e++){let t=n[e];i=bp(i,t,r,new Map)}return i}function bp(e,t,n,r){if(tf(e)&&(e=Object(e)),typeof t!=`object`||!t)return e;if(r.has(t))return rf(r.get(t));if(r.set(t,e),Array.isArray(t)){t=t.slice();for(let e=0;e<t.length;e++)t[e]=t[e]??void 0}let i=[...Object.keys(t),...af(t)];for(let a=0;a<i.length;a++){let o=i[a];if(zf(o))continue;let s=t[o],c=e[o];if(sp(s)&&(s={...s}),sp(c)&&(c={...c}),Nf(s)&&(s=ip(s)),Array.isArray(s))if(Array.isArray(c)){let e=[],t=Reflect.ownKeys(c);for(let n=0;n<t.length;n++){let r=t[n];e[r]=c[r]}c=e}else if(up(c)){let e=[];for(let t=0;t<c.length;t++)e[t]=c[t];c=e}else c=[];let l=n(c,s,o,e,t,r);l===void 0?Array.isArray(s)||lp(c)&&lp(s)&&(Bf(c)||Bf(s)||vp(c)||vp(s))?e[o]=bp(c,s,n,r):c==null&&Bf(s)?e[o]=bp({},s,n,r):c==null&&vp(s)?e[o]=ip(s):(c===void 0||s!==void 0)&&(e[o]=s):e[o]=l}return e}function xp(e,...t){return yp(e,...t,ef)}function Sp(e){return Jf(Qf(e))}var wp=e=>typeof File<`u`&&e instanceof File||e instanceof Blob||typeof FileList<`u`&&e instanceof FileList&&e.length>0,Tp=e=>e instanceof FormData?!0:wp(e)||typeof e==`object`&&!!e&&Object.values(e).some(e=>Tp(e)),Ep=class extends Error{response;constructor(e){super(`HTTP error ${e.status}`),this.name=`HttpResponseError`,this.response=e}},Dp=class extends Error{constructor(e=`Request was cancelled`){super(e),this.name=`HttpCancelledError`}},Op=class extends Error{constructor(e=`Network error`){super(e),this.name=`HttpNetworkError`}};function kp(e){let t=new URLSearchParams;return Object.entries(e).forEach(([e,n])=>{n!=null&&(Array.isArray(n)?n.forEach(n=>t.append(`${e}[]`,String(n))):typeof n==`object`?t.append(e,JSON.stringify(n)):t.append(e,String(n)))}),t.toString()}function Ap(e,t,n){if(t&&!e.startsWith(`http://`)&&!e.startsWith(`https://`)&&(e=t.replace(/\/$/,``)+`/`+e.replace(/^\//,``)),n&&Object.keys(n).length>0){let t=kp(n);t&&(e+=(e.includes(`?`)?`&`:`?`)+t)}return e}function jp(){return typeof window>`u`?null:window.axios?.defaults?.headers?.common?.[`X-Requested-With`]??null}function Mp(e,t=new FormData,n=null){for(let r in e)Object.prototype.hasOwnProperty.call(e,r)&&Np(t,n?`${n}[${r}]`:r,e[r]);return t}function Np(e,t,n){if(Array.isArray(n))return n.forEach((n,r)=>Np(e,`${t}[${r}]`,n));if(n instanceof Date)return e.append(t,n.toISOString());if(typeof File<`u`&&n instanceof File)return e.append(t,n,n.name);if(n instanceof Blob)return e.append(t,n);if(typeof n==`boolean`)return e.append(t,n?`1`:`0`);if(typeof n==`string`)return e.append(t,n);if(typeof n==`number`)return e.append(t,`${n}`);if(n==null)return e.append(t,``);Mp(n,e,t)}function Pp(e,t){if(e!=null)return e instanceof FormData?e:typeof e==`object`&&Tp(e)?Mp(e):typeof e==`object`||t[`Content-Type`]?.includes(`application/json`)?JSON.stringify(e):String(e)}function Fp(e){let t={};return e.forEach((e,n)=>{t[n.toLowerCase()]=e}),t}function Ip(e={}){let t=e.xsrfCookieName??`XSRF-TOKEN`,n=e.xsrfHeaderName??`X-XSRF-TOKEN`;function r(){if(typeof document>`u`)return null;let e=document.cookie.match(RegExp(`(^|;\\s*)`+t+`=([^;]*)`));return e?decodeURIComponent(e[2]):null}return{setXsrfCookieName(e){t=e},setXsrfHeaderName(e){n=e},async request(e){let t=Ap(e.url,e.baseURL,e.params),i=e.method.toUpperCase(),a={},o=jp();o&&(a[`X-Requested-With`]=o),e.data!==void 0&&![`GET`,`DELETE`].includes(i)&&!(e.data instanceof FormData)&&!Tp(e.data)&&(a[`Content-Type`]=`application/json`),e.headers&&Object.entries(e.headers).forEach(([e,t])=>{t!==void 0&&(a[e]=String(t))});let s=r();s&&![`GET`,`HEAD`,`OPTIONS`].includes(i)&&(a[n]=s);let c=e.signal,l,u=e.timeout??3e4;if(u>0&&!c){let e=new AbortController;c=e.signal,l=setTimeout(()=>e.abort(),u)}let d=[`GET`,`DELETE`].includes(i)?void 0:Pp(e.data,a);d instanceof FormData&&delete a[`Content-Type`];try{let n=await fetch(t,{method:i,headers:a,body:d,signal:c,credentials:e.credentials??`same-origin`});l&&clearTimeout(l);let r;r=n.headers.get(`content-type`)?.includes(`application/json`)?await n.json():await n.text();let o={status:n.status,data:r,headers:Fp(n.headers)};if(!n.ok)throw new Ep(o);return o}catch(e){throw l&&clearTimeout(l),e instanceof Ep?e:e instanceof DOMException&&e.name===`AbortError`?new Dp:e instanceof TypeError?new Op(e.message):e}}}}var Lp=Ip(),Rp=Lp,zp=void 0,Bp=void 0,Vp=`same-origin`,Hp=e=>`${e.method}:${e.baseURL??zp??``}${e.url}`,Up=e=>e.status===204&&e.headers[`precognition-success`]===`true`,Wp={},Gp={get:(e,t={},n={})=>qp(Kp(`get`,e,t,n)),post:(e,t={},n={})=>qp(Kp(`post`,e,t,n)),patch:(e,t={},n={})=>qp(Kp(`patch`,e,t,n)),put:(e,t={},n={})=>qp(Kp(`put`,e,t,n)),delete:(e,t={},n={})=>qp(Kp(`delete`,e,t,n)),useHttpClient(e){return Rp=e,Gp},withBaseURL(e){return zp=e,Gp},withTimeout(e){return Bp=e,Gp},withCredentials(e){return Vp=typeof e==`string`?e:e?`include`:`omit`,Gp},fingerprintRequestsUsing(e){return Hp=e===null?()=>null:e,Gp},determineSuccessUsing(e){return Up=e,Gp},withXsrfCookieName(e){return Lp.setXsrfCookieName(e),Gp},withXsrfHeaderName(e){return Lp.setXsrfHeaderName(e),Gp}},Kp=(e,t,n,r)=>({url:t,method:e,...r,...[`get`,`delete`].includes(e)?{params:xp({},n,r?.params)}:{data:xp({},n,r?.data)}}),qp=(e={})=>{let t=[Jp,Xp,Zp].reduce((e,t)=>t(e),e);return(t.onBefore??(()=>!0))()===!1?Promise.resolve(null):((t.onStart??(()=>null))(),Rp.request({method:t.method,url:t.url,baseURL:t.baseURL??zp,data:t.data,params:t.params,headers:t.headers,signal:t.signal,timeout:t.timeout,credentials:Vp}).then(async e=>{t.precognitive&&Qp(e);let n=e.status,r=e;return t.precognitive&&t.onPrecognitionSuccess&&Up(e)&&(r=await Promise.resolve(t.onPrecognitionSuccess(e)??r)),t.onSuccess&&Yp(n)&&(r=await Promise.resolve(t.onSuccess(r)??r)),(em(t,n)??(e=>e))(r)??r},e=>{if($p(e))return Promise.reject(e);let n=e;return t.precognitive&&Qp(n.response),(em(t,n.response.status)??((e,t)=>Promise.reject(t)))(n.response,n)}).finally(t.onFinish??(()=>null)))},Jp=e=>{let t=e.only??e.validate;return{...e,timeout:e.timeout??Bp,precognitive:e.precognitive!==!1,fingerprint:e.fingerprint===void 0?Hp(e,Rp):e.fingerprint,headers:{...e.headers,Accept:`application/json`,"Content-Type":tm(e),...e.precognitive===!1?{}:{Precognition:!0},...t?{"Precognition-Validate-Only":Array.from(t).join()}:{}}}},Yp=e=>e>=200&&e<300,Xp=e=>typeof e.fingerprint==`string`?(Wp[e.fingerprint]?.abort(),delete Wp[e.fingerprint],e):e,Zp=e=>typeof e.fingerprint!=`string`||e.signal||!e.precognitive?e:(Wp[e.fingerprint]=new AbortController,{...e,signal:Wp[e.fingerprint].signal}),Qp=e=>{if(e.headers?.precognition!==`true`)throw Error(`Did not receive a Precognition response. Ensure you have the Precognition middleware in place for the route.`)},$p=e=>!(e instanceof Ep)||typeof e.response?.status!=`number`,em=(e,t)=>({401:e.onUnauthorized,403:e.onForbidden,404:e.onNotFound,409:e.onConflict,422:e.onValidationError,423:e.onLocked})[t],tm=e=>e.headers?.[`Content-Type`]??e.headers?.[`Content-type`]??e.headers?.[`content-type`]??(Tp(e.data)?`multipart/form-data`:`application/json`),nm=(e,t)=>{if(!e.includes(`*`))return[e];let n=e.split(`.`),r=[``];for(let e of n)if(e===`*`){let e=[];for(let n of r){let r=n?ep(t,n):t;if(Array.isArray(r))for(let t=0;t<r.length;t++)e.push(n?`${n}.${t}`:String(t));else if(typeof r==`object`&&r)for(let t of Object.keys(r))e.push(n?`${n}.${t}`:t)}r=e}else r=r.map(t=>t?`${t}.${e}`:e);return r},rm=(e,t)=>t.includes(`*`)?RegExp(`^`+t.replace(/\./g,`\\.`).replace(/\*/g,`[^.]+`)+`$`).test(e):e===t,im=(e,t)=>Object.fromEntries(Object.entries(e).filter(([e])=>!t.some(t=>rm(e,t)))),am=(e,t={})=>{let n={errorsChanged:[],touchedChanged:[],validatingChanged:[],validatedChanged:[]},r=!1,i=!1,a=e=>e===i?[]:(i=e,n.validatingChanged),o=[],s=e=>{let t=[...new Set(e)];return o.length!==t.length||!t.every(e=>o.includes(e))?(o=t,n.validatedChanged):[]},c=()=>o.filter(e=>d[e]===void 0),l=[],u=e=>{let t=[...new Set(e)];return l.length!==t.length||!t.every(e=>l.includes(e))?(l=t,n.touchedChanged):[]},d={},f=e=>{let t=sm(e);return Gf(d,t)?[]:(d=t,n.errorsChanged)},p=e=>{let t={...d};return delete t[cm(e)],f(t)},m=()=>Object.keys(d).length>0,h=1500,g=e=>{h=e,S.cancel(),S=x()},_=t,v=null,y=[],b=null,x=()=>_p(t=>{e({get:(e,n={},r={})=>Gp.get(e,T(n),C(r,t,n)),post:(e,n={},r={})=>Gp.post(e,T(n),C(r,t,n)),patch:(e,n={},r={})=>Gp.patch(e,T(n),C(r,t,n)),put:(e,n={},r={})=>Gp.put(e,T(n),C(r,t,n)),delete:(e,n={},r={})=>Gp.delete(e,T(n),C(r,t,n))}).catch(e=>e instanceof Dp||e instanceof Ep&&e.response?.status===422?null:Promise.reject(e))},h,{leading:!0,trailing:!0}),S=x(),C=(e,t,n={})=>{let r={...e,...t},i=Array.from(r.only??r.validate??l);return{...t,...xp({},e,t),only:i,timeout:r.timeout??5e3,onValidationError:(e,t)=>([...s([...o,...i]),...f(xp(im({...d},i),e.data.errors))].forEach(e=>e()),r.onValidationError?r.onValidationError(e,t):Promise.reject(t)),onSuccess:e=>(s([...o,...i]).forEach(e=>e()),r.onSuccess?r.onSuccess(e):e),onPrecognitionSuccess:e=>([...s([...o,...i]),...f(im({...d},i))].forEach(e=>e()),r.onPrecognitionSuccess?r.onPrecognitionSuccess(e):e),onBefore:()=>{let e=l.some(e=>e.includes(`*`)),t=e?[...new Set(l.flatMap(e=>nm(e,n)))]:l;return r.onBeforeValidation&&r.onBeforeValidation({data:n,touched:t},{data:_,touched:y})===!1||(r.onBefore||(()=>!0))()===!1?!1:(e&&u(t).forEach(e=>e()),b=l,v=n,!0)},onStart:()=>{a(!0).forEach(e=>e()),(r.onStart??(()=>null))()},onFinish:()=>{a(!1).forEach(e=>e()),y=b,_=v,b=v=null,(r.onFinish??(()=>null))()}}},w=(e,t,n)=>{if(e===void 0){let e=Array.from(n?.only??n?.validate??[]);u([...l,...e]).forEach(e=>e()),S(n??{});return}if(wp(t)&&!r){console.warn(`Precognition file validation is not active. Call the "validateFiles" function on your form to enable it.`);return}e=cm(e),(e.includes(`*`)||ep(_,e)!==t)&&(u([e,...l]).forEach(e=>e()),S(n??{}))},T=e=>r===!1?lm(e):e,E={touched:()=>l,validate(e,t,n){return typeof e==`object`&&!(`target`in e)&&(n=e,e=t=void 0),w(e,t,n),E},touch(e){let t=Array.isArray(e)?e:[cm(e)];return u([...l,...t]).forEach(e=>e()),E},validating:()=>i,valid:c,errors:()=>d,hasErrors:m,setErrors(e){return f(e).forEach(e=>e()),E},forgetError(e){return p(e).forEach(e=>e()),E},defaults(e){return t=e,_=e,E},reset(...e){if(e.length===0)u([]).forEach(e=>e());else{let n=[...l];e.forEach(e=>{n.includes(e)&&n.splice(n.indexOf(e),1),gp(_,e,ep(t,e))}),u(n).forEach(e=>e())}return E},setTimeout(e){return g(e),E},on(e,t){return n[e].push(t),E},validateFiles(){return r=!0,E},withoutFileValidation(){return r=!1,E}};return E},om=e=>Object.keys(e).reduce((t,n)=>({...t,[n]:Array.isArray(e[n])?e[n][0]:e[n]}),{}),sm=e=>Object.keys(e).reduce((t,n)=>({...t,[n]:typeof e[n]==`string`?[e[n]]:e[n]}),{}),cm=e=>typeof e==`string`?e:e.target.name,lm=e=>{let t={...e};return Object.keys(t).forEach(e=>{let n=t[e];if(n!==null){if(wp(n)){delete t[e];return}if(Array.isArray(n)){t[e]=Object.values(lm({...n}));return}if(typeof n==`object`){t[e]=lm(t[e]);return}}}),t},um=new class{config={};defaults;constructor(e){this.defaults=e}extend(e){return e&&(this.defaults={...this.defaults,...e}),this}replace(e){this.config=e}get(e){return cp(this.config,e)?ep(this.config,e):ep(this.defaults,e)}set(e,t){typeof e==`string`?gp(this.config,e,t):Object.entries(e).forEach(([e,t])=>{gp(this.config,e,t)})}}({form:{recentlySuccessfulDuration:2e3,forceIndicesArrayFormatInFormData:!0,withAllErrors:!1},prefetch:{cacheFor:3e4,hoverDelay:75}});function dm(e,t){let n;return function(...r){clearTimeout(n),n=setTimeout(()=>e.apply(this,r),t)}}function fm(e,t){return document.dispatchEvent(new CustomEvent(`inertia:${e}`,t))}var pm=e=>fm(`before`,{cancelable:!0,detail:{visit:e}}),mm=e=>fm(`error`,{detail:{errors:e}}),hm=e=>fm(`networkError`,{cancelable:!0,detail:{error:e}}),gm=e=>fm(`finish`,{detail:{visit:e}}),_m=e=>fm(`httpException`,{cancelable:!0,detail:{response:e}}),vm=e=>fm(`beforeUpdate`,{detail:{page:e}}),ym=e=>fm(`navigate`,{detail:{page:e}}),bm=e=>fm(`progress`,{detail:{progress:e}}),xm=e=>fm(`start`,{detail:{visit:e}}),Sm=e=>fm(`success`,{detail:{page:e}}),Cm=(e,t)=>fm(`prefetched`,{detail:{fetchedAt:Date.now(),response:e,visit:t}}),wm=e=>fm(`prefetching`,{detail:{visit:e}}),Tm=e=>fm(`flash`,{detail:{flash:e}}),Em=class{static locationVisitKey=`inertiaLocationVisit`;static set(e,t){typeof window<`u`&&window.sessionStorage.setItem(e,JSON.stringify(t))}static get(e){if(typeof window<`u`)return JSON.parse(window.sessionStorage.getItem(e)||`null`)}static merge(e,t){let n=this.get(e);n===null?this.set(e,t):this.set(e,{...n,...t})}static remove(e){typeof window<`u`&&window.sessionStorage.removeItem(e)}static removeNested(e,t){let n=this.get(e);n!==null&&(delete n[t],this.set(e,n))}static exists(e){try{return this.get(e)!==null}catch{return!1}}static clear(){typeof window<`u`&&window.sessionStorage.clear()}},Dm=async e=>{if(typeof window>`u`)throw Error(`Unable to encrypt history`);let t=Mm(),n=await Fm(await Im());if(!n)throw Error(`Unable to encrypt history`);return await Am(t,n,e)},Om={key:`historyKey`,iv:`historyIv`},km=async e=>{let t=Mm(),n=await Im();if(!n)throw Error(`Unable to decrypt history`);return await jm(t,n,e)},Am=async(e,t,n)=>{if(typeof window>`u`)throw Error(`Unable to encrypt history`);if(window.crypto.subtle===void 0)return console.warn(`Encryption is not supported in this environment. SSL is required.`),Promise.resolve(n);let r=new TextEncoder,i=JSON.stringify(n),a=new Uint8Array(i.length*3),o=r.encodeInto(i,a);return window.crypto.subtle.encrypt({name:`AES-GCM`,iv:e},t,a.subarray(0,o.written))},jm=async(e,t,n)=>{if(window.crypto.subtle===void 0)return console.warn(`Decryption is not supported in this environment. SSL is required.`),Promise.resolve(n);let r=await window.crypto.subtle.decrypt({name:`AES-GCM`,iv:e},t,n);return JSON.parse(new TextDecoder().decode(r))},Mm=()=>{let e=Em.get(Om.iv);if(e)return new Uint8Array(e);let t=window.crypto.getRandomValues(new Uint8Array(12));return Em.set(Om.iv,Array.from(t)),t},Nm=async()=>window.crypto.subtle===void 0?(console.warn(`Encryption is not supported in this environment. SSL is required.`),Promise.resolve(null)):window.crypto.subtle.generateKey({name:`AES-GCM`,length:256},!0,[`encrypt`,`decrypt`]),Pm=async e=>{if(window.crypto.subtle===void 0)return console.warn(`Encryption is not supported in this environment. SSL is required.`),Promise.resolve();let t=await window.crypto.subtle.exportKey(`raw`,e);Em.set(Om.key,Array.from(new Uint8Array(t)))},Fm=async e=>{if(e)return e;let t=await Nm();return t?(await Pm(t),t):null},Im=async()=>{let e=Em.get(Om.key);return e?await window.crypto.subtle.importKey(`raw`,new Uint8Array(e),{name:`AES-GCM`,length:256},!0,[`encrypt`,`decrypt`]):null},Lm=(e,t,n)=>{if(e===t)return!0;for(let r in e)if(!n.includes(r)&&e[r]!==t[r]&&!Rm(e[r],t[r]))return!1;for(let r in t)if(!n.includes(r)&&!(r in e))return!1;return!0},Rm=(e,t)=>{switch(typeof e){case`object`:return Lm(e,t,[]);case`function`:return e.toString()===t.toString();default:return e===t}},zm={ms:1,s:1e3,m:1e3*60,h:1e3*60*60,d:1e3*60*60*24},Bm=e=>{if(typeof e==`number`)return e;for(let[t,n]of Object.entries(zm))if(e.endsWith(t))return parseFloat(e)*n;return parseInt(e)},Vm=new class{cached=[];inFlightRequests=[];removalTimers=[];currentUseId=null;add(e,t,{cacheFor:n,cacheTags:r}){if(this.findInFlight(e))return Promise.resolve();let i=this.findCached(e);if(!e.fresh&&i&&i.staleTimestamp>Date.now())return Promise.resolve();let[a,o]=this.extractStaleValues(n),s=new Promise((n,r)=>{t({...e,onCancel:()=>{this.remove(e),e.onCancel(),r()},onError:t=>{this.remove(e),e.onError(t),r()},onPrefetching(t){e.onPrefetching(t)},onPrefetched(t,n){e.onPrefetched(t,n)},onPrefetchResponse(e){n(e)},onPrefetchError(t){Vm.removeFromInFlight(e),r(t)}})}).then(t=>{this.remove(e);let n=t.getPageResponse();H.mergeOncePropsIntoResponse(n),this.cached.push({params:{...e},staleTimestamp:Date.now()+a,expiresAt:Date.now()+o,response:s,singleUse:o===0,timestamp:Date.now(),inFlight:!1,tags:Array.isArray(r)?r:[r]});let i=this.getShortestOncePropTtl(n);return this.scheduleForRemoval(e,i?Math.min(o,i):o),this.removeFromInFlight(e),t.handlePrefetch(),t});return this.inFlightRequests.push({params:{...e},response:s,staleTimestamp:null,inFlight:!0}),s}removeAll(){this.cached=[],this.removalTimers.forEach(e=>{clearTimeout(e.timer)}),this.removalTimers=[]}removeByTags(e){this.cached=this.cached.filter(t=>!t.tags.some(t=>e.includes(t)))}remove(e){this.cached=this.cached.filter(t=>!this.paramsAreEqual(t.params,e)),this.clearTimer(e)}removeFromInFlight(e){this.inFlightRequests=this.inFlightRequests.filter(t=>!this.paramsAreEqual(t.params,e))}extractStaleValues(e){let[t,n]=this.cacheForToStaleAndExpires(e);return[Bm(t),Bm(n)]}cacheForToStaleAndExpires(e){if(!Array.isArray(e))return[e,e];switch(e.length){case 0:return[0,0];case 1:return[e[0],e[0]];default:return[e[0],e[1]]}}clearTimer(e){let t=this.removalTimers.find(t=>this.paramsAreEqual(t.params,e));t&&(clearTimeout(t.timer),this.removalTimers=this.removalTimers.filter(e=>e!==t))}scheduleForRemoval(e,t){if(!(typeof window>`u`)&&(this.clearTimer(e),t>0)){let n=window.setTimeout(()=>this.remove(e),t);this.removalTimers.push({params:e,timer:n})}}get(e){return this.findCached(e)||this.findInFlight(e)}use(e,t){let n=`${t.url.pathname}-${Date.now()}-${Math.random().toString(36).substring(7)}`;return this.currentUseId=n,e.response.then(e=>{if(this.currentUseId===n)return e.mergeParams({...t,onPrefetched:()=>{}}),this.removeSingleUseItems(t),e.handle()})}removeSingleUseItems(e){this.cached=this.cached.filter(t=>this.paramsAreEqual(t.params,e)?!t.singleUse:!0)}findCached(e){return this.cached.find(t=>this.paramsAreEqual(t.params,e))||null}findInFlight(e){return this.inFlightRequests.find(t=>this.paramsAreEqual(t.params,e))||null}withoutPurposePrefetchHeader(e){let t=V(e);return t.headers.Purpose===`prefetch`&&delete t.headers.Purpose,t}paramsAreEqual(e,t){return Lm(this.withoutPurposePrefetchHeader(e),this.withoutPurposePrefetchHeader(t),[`showProgress`,`replace`,`prefetch`,`preserveScroll`,`preserveState`,`onBefore`,`onBeforeUpdate`,`onStart`,`onProgress`,`onFinish`,`onCancel`,`onSuccess`,`onError`,`onFlash`,`onPrefetched`,`onCancelToken`,`onPrefetching`,`async`,`viewTransition`,`optimistic`,`component`,`pageProps`])}updateCachedOncePropsFromCurrentPage(){this.cached.forEach(e=>{e.response.then(t=>{let n=t.getPageResponse();H.mergeOncePropsIntoResponse(n,{force:!0});for(let[e,t]of Object.entries(n.deferredProps??{})){let r=t.filter(e=>ep(n.props,e)===void 0);r.length>0?n.deferredProps[e]=r:delete n.deferredProps[e]}let r=this.getShortestOncePropTtl(n);if(r===null)return;let i=e.expiresAt-Date.now(),a=Math.min(i,r);a>0?this.scheduleForRemoval(e.params,a):this.remove(e.params)})})}getShortestOncePropTtl(e){let t=Object.values(e.onceProps??{}).map(e=>e.expiresAt).filter(e=>!!e);return t.length===0?null:Math.min(...t)-Date.now()}},Hm=(e,t=1)=>{window.requestAnimationFrame(()=>{t>1?Hm(e,t-1):e()})},Um=e=>{if(typeof window>`u`)return null;let t=document.querySelector(`script[data-page="${e}"][type="application/json"]`);return t?.textContent?JSON.parse(t.textContent):null},Wm=typeof window>`u`,Gm=!Wm&&/Firefox/i.test(window.navigator.userAgent),Km=class{static save(){U.saveScrollPositions(this.getScrollRegions())}static getScrollRegions(){return Array.from(this.regions()).map(e=>({top:e.scrollTop,left:e.scrollLeft}))}static regions(){return document.querySelectorAll(`[scroll-region]`)}static scrollToTop(){if(Gm&&getComputedStyle(document.documentElement).scrollBehavior===`smooth`)return Hm(()=>window.scrollTo(0,0),2);window.scrollTo(0,0)}static reset(){!Wm&&window.location.hash||this.scrollToTop(),this.regions().forEach(e=>{typeof e.scrollTo==`function`?e.scrollTo(0,0):(e.scrollTop=0,e.scrollLeft=0)}),this.save(),this.scrollToAnchor()}static scrollToAnchor(){let e=Wm?null:window.location.hash;e&&setTimeout(()=>{let t=document.getElementById(e.slice(1));t?t.scrollIntoView():this.scrollToTop()})}static restore(e){Wm||window.requestAnimationFrame(()=>{this.restoreDocument(),this.restoreScrollRegions(e)})}static restoreScrollRegions(e){Wm||this.regions().forEach((t,n)=>{let r=e[n];r&&(typeof t.scrollTo==`function`?t.scrollTo(r.left,r.top):(t.scrollTop=r.top,t.scrollLeft=r.left))})}static restoreDocument(){let e=U.getDocumentScrollPosition();window.scrollTo(e.left,e.top)}static onScroll(e){let t=e.target;typeof t.hasAttribute==`function`&&t.hasAttribute(`scroll-region`)&&this.save()}static onWindowScroll(){U.saveDocumentScrollPosition({top:window.scrollY,left:window.scrollX})}},qm=e=>typeof File<`u`&&e instanceof File||e instanceof Blob||typeof FileList<`u`&&e instanceof FileList&&e.length>0;function Jm(e){return qm(e)||e instanceof FormData&&Array.from(e.values()).some(e=>Jm(e))||typeof e==`object`&&!!e&&Object.values(e).some(e=>Jm(e))}var Ym=e=>e instanceof FormData;function Xm(e,t=new FormData,n=null,r=`brackets`){e||={};for(let i in e)Object.prototype.hasOwnProperty.call(e,i)&&Qm(t,Zm(n,i,`indices`),e[i],r);return t}function Zm(e,t,n){return e?n===`brackets`?`${e}[]`:`${e}[${t}]`:t}function Qm(e,t,n,r){if(Array.isArray(n))return Array.from(n.keys()).forEach(i=>Qm(e,Zm(t,i.toString(),r),n[i],r));if(n instanceof Date)return e.append(t,n.toISOString());if(n instanceof File)return e.append(t,n,n.name);if(n instanceof Blob)return e.append(t,n);if(typeof n==`boolean`)return e.append(t,n?`1`:`0`);if(typeof n==`string`)return e.append(t,n);if(typeof n==`number`)return e.append(t,`${n}`);if(n==null)return e.append(t,``);Xm(n,e,t,r)}function $m(e){return/\[\d+\]/.test(decodeURIComponent(e.search))}function eh(e){if(!e||e===`?`)return{};let t={};return e.replace(/^\?/,``).split(`&`).filter(Boolean).forEach(e=>{let[n,r]=nh(e);ih(t,rh(n),rh(r))}),t}function th(e,t){let n=[];return oh(e,``,n,t),n.length?`?`+n.join(`&`):``}function nh(e){let t=e.indexOf(`=`);return t===-1?[e,``]:[e.substring(0,t),e.substring(t+1)]}function rh(e){return decodeURIComponent(e.replace(/\+/g,` `))}function ih(e,t,n){let r=ah(t),i=e;for(;r.length>1;){let e=r.shift(),t=r[0]===``;(typeof i[e]!=`object`||i[e]===null)&&(i[e]=t?[]:{}),i=i[e]}let a=r.shift();a===``&&Array.isArray(i)?i.push(n):i[a]=n}function ah(e){let t=[],n=e.split(`[`)[0];n&&t.push(n);let r,i=/\[([^\]]*)\]/g;for(;(r=i.exec(e))!==null;)t.push(r[1]);return t}function oh(e,t,n,r){if(e!==void 0){if(e===null){n.push(`${t}=`);return}if(Array.isArray(e)){e.forEach((e,i)=>{oh(e,r===`indices`?`${t}[${i}]`:`${t}[]`,n,r)});return}if(typeof e==`object`){Object.keys(e).forEach(i=>{oh(e[i],t?`${t}[${i}]`:i,n,r)});return}n.push(`${t}=${encodeURIComponent(String(e))}`)}}function sh(e){return new URL(e.toString(),typeof window>`u`?void 0:window.location.toString())}var ch=(e,t,n,r,i)=>{let a=typeof e==`string`?sh(e):e;if((Jm(t)||r)&&!Ym(t)&&(um.get(`form.forceIndicesArrayFormatInFormData`)&&(i=`indices`),t=Xm(t,new FormData,null,i)),Ym(t))return[a,t];let[o,s]=lh(n,a,t,i);return[sh(o),s]};function lh(e,t,n,r=`brackets`){let i=e===`get`&&!Ym(n)&&Object.keys(n).length>0,a=gh(t.toString()),o=a||t.toString().startsWith(`/`)||t.toString()===``,s=!o&&!t.toString().startsWith(`#`)&&!t.toString().startsWith(`?`),c=/^[.]{1,2}([/]|$)/.test(t.toString()),l=t.toString().includes(`?`)||i,u=t.toString().includes(`#`),d=new URL(t.toString(),typeof window>`u`?`http://localhost`:window.location.toString());if(i){let e=$m(d)?`indices`:r;d.search=th({...eh(d.search),...n},e)}return[[a?`${d.protocol}//${d.host}`:``,o?d.pathname:``,s?d.pathname.substring(+!c):``,l?d.search:``,u?d.hash:``].join(``),i?{}:n]}function uh(e){return e=new URL(e.href),e.hash=``,e}var dh=(e,t)=>{e.hash&&!t.hash&&uh(e).href===t.href&&(t.hash=e.hash)},fh=(e,t)=>uh(e).href===uh(t).href,ph=(e,t)=>e.origin===t.origin&&e.pathname===t.pathname;function mh(e){return typeof e==`object`&&!!e&&e!==void 0&&`url`in e&&`method`in e}function hh(e){return e.component?typeof e.component==`string`?e.component:(console.error(`The "component" property on the URL method pair received multiple components (${Object.keys(e.component).join(`, `)}), but only a single component string is supported for instant visits. Use the withComponent() method to specify which component to use.`),null):null}function gh(e){return/^([a-z][a-z0-9+.-]*:)?\/\/[^/]/i.test(e)}var H=new class{page;swapComponent;resolveComponent;onFlashCallback;componentId={};listeners=[];isFirstPageLoad=!0;cleared=!1;pendingDeferredProps=null;historyQuotaExceeded=!1;optimisticBaseline={};pendingOptimistics=[];optimisticCounter=0;init({initialPage:e,swapComponent:t,resolveComponent:n,onFlash:r}){return this.page={...e,flash:e.flash??{}},this.swapComponent=t,this.resolveComponent=n,this.onFlashCallback=r,Sh.on(`historyQuotaExceeded`,()=>{this.historyQuotaExceeded=!0}),this}set(e,{replace:t=!1,preserveScroll:n=!1,preserveState:r=!1,viewTransition:i=!1}={}){Object.keys(e.deferredProps||{}).length&&(this.pendingDeferredProps={deferredProps:e.deferredProps,component:e.component,url:e.url},e.initialDeferredProps===void 0&&(e.initialDeferredProps=e.deferredProps)),this.componentId={};let a=this.componentId;return e.clearHistory&&U.clear(),this.resolve(e.component,e).then(o=>{if(a!==this.componentId)return;e.rememberedState??={};let s=typeof window>`u`,c=s?new URL(e.url):window.location,l=!s&&n?Km.getScrollRegions():[];t||=fh(sh(e.url),c);let u={...e,flash:{}};return new Promise(e=>t?U.replaceState(u,e):U.pushState(u,e)).then(()=>{let a=!this.isTheSame(e);if(!a&&Object.keys(e.props.errors||{}).length>0&&(i=!1),this.page=e,this.cleared=!1,this.hasOnceProps()&&Vm.updateCachedOncePropsFromCurrentPage(),a&&this.fireEventsFor(`newComponent`),this.isFirstPageLoad&&this.fireEventsFor(`firstLoad`),this.isFirstPageLoad=!1,this.historyQuotaExceeded){this.historyQuotaExceeded=!1;return}return this.swap({component:o,page:e,preserveState:r,viewTransition:i}).then(()=>{n?window.requestAnimationFrame(()=>Km.restoreScrollRegions(l)):Km.reset(),this.pendingDeferredProps&&this.pendingDeferredProps.component===e.component&&this.pendingDeferredProps.url===e.url&&Sh.fireInternalEvent(`loadDeferredProps`,this.pendingDeferredProps.deferredProps),this.pendingDeferredProps=null,t||ym(e)})})})}setQuietly(e,{preserveState:t=!1}={}){return this.resolve(e.component,e).then(n=>(this.page=e,this.cleared=!1,U.setCurrent(e),this.swap({component:n,page:e,preserveState:t,viewTransition:!1})))}clear(){this.cleared=!0}isCleared(){return this.cleared}get(){return this.page}getWithoutFlashData(){return{...this.page,flash:{}}}hasOnceProps(){return Object.keys(this.page.onceProps??{}).length>0}merge(e){this.page={...this.page,...e}}setPropsQuietly(e){return this.page={...this.page,props:e},this.resolve(this.page.component,this.page).then(e=>this.swap({component:e,page:this.page,preserveState:!0,viewTransition:!1}))}setFlash(e){this.page={...this.page,flash:e},this.onFlashCallback?.(e)}setUrlHash(e){this.page.url.includes(e)||(this.page.url+=e)}remember(e){this.page.rememberedState=e}swap({component:e,page:t,preserveState:n,viewTransition:r}){let i=()=>this.swapComponent({component:e,page:t,preserveState:n});if(!r||!document?.startViewTransition||document.visibilityState===`hidden`)return i();let a=typeof r==`boolean`?()=>null:r;return new Promise(e=>{a(document.startViewTransition(()=>i().then(e)))})}resolve(e,t){return Promise.resolve(this.resolveComponent(e,t))}nextOptimisticId(){return++this.optimisticCounter}setBaseline(e,t){e in this.optimisticBaseline||(this.optimisticBaseline[e]=t)}updateBaseline(e,t){e in this.optimisticBaseline&&(this.optimisticBaseline[e]=t)}hasBaseline(e){return e in this.optimisticBaseline}registerOptimistic(e,t){this.pendingOptimistics.push({id:e,callback:t})}unregisterOptimistic(e){this.pendingOptimistics=this.pendingOptimistics.filter(t=>t.id!==e)}replayOptimistics(){let e=Object.keys(this.optimisticBaseline);if(e.length===0)return{};let t=V(this.page.props);for(let n of e)t[n]=V(this.optimisticBaseline[n]);for(let{callback:e}of this.pendingOptimistics){let n=e(V(t));n&&Object.assign(t,n)}let n={};for(let r of e)n[r]=t[r];return n}pendingOptimisticCount(){return this.pendingOptimistics.length}clearOptimisticState(){this.optimisticBaseline={},this.pendingOptimistics=[]}isTheSame(e){return this.page.component===e.component}on(e,t){return this.listeners.push({event:e,callback:t}),()=>{this.listeners=this.listeners.filter(n=>n.event!==e&&n.callback!==t)}}fireEventsFor(e){this.listeners.filter(t=>t.event===e).forEach(e=>e.callback())}mergeOncePropsIntoResponse(e,{force:t=!1}={}){Object.entries(e.onceProps??{}).forEach(([n,r])=>{let i=this.page.onceProps?.[n];i!==void 0&&(t||ep(e.props,r.prop)===void 0)&&(gp(e.props,r.prop,ep(this.page.props,i.prop)),e.onceProps[n].expiresAt=i.expiresAt)})}},_h=class{items=[];processingPromise=null;add(e){return this.items.push(e),this.process()}process(){return this.processingPromise??=this.processNext().finally(()=>{this.processingPromise=null}),this.processingPromise}processNext(){let e=this.items.shift();return e?Promise.resolve(e()).then(()=>this.processNext()):Promise.resolve()}},vh=typeof window>`u`,yh=new _h,bh=!vh&&/CriOS/.test(window.navigator.userAgent),xh=class{rememberedState=`rememberedState`;scrollRegions=`scrollRegions`;preserveUrl=!1;current={};initialState=null;remember(e,t){this.replaceState({...H.getWithoutFlashData(),rememberedState:{...H.get()?.rememberedState??{},[t]:e}})}restore(e){if(!vh)return this.current[this.rememberedState]?.[e]===void 0?this.initialState?.[this.rememberedState]?.[e]:this.current[this.rememberedState]?.[e]}pushState(e,t=null){if(!vh){if(this.preserveUrl){t&&t();return}this.current=e,yh.add(()=>this.getPageData(e).then(n=>{let r=()=>this.doPushState({page:n},e.url).then(()=>t?.());return bh?new Promise(e=>{setTimeout(()=>r().then(e))}):r()}))}}clonePageProps(e){try{return structuredClone(e.props),e}catch{return{...e,props:V(e.props)}}}getPageData(e){let t=this.clonePageProps(e);return new Promise(n=>e.encryptHistory?Dm(t).then(n):n(t))}processQueue(){return yh.process()}decrypt(e=null){if(vh)return Promise.resolve(e??H.get());let t=e??window.history.state?.page;return this.decryptPageData(t).then(e=>{if(!e)throw Error(`Unable to decrypt history`);return this.initialState===null?this.initialState=e??void 0:this.current=e??{},e})}decryptPageData(e){return e instanceof ArrayBuffer?km(e):Promise.resolve(e)}saveScrollPositions(e){yh.add(()=>Promise.resolve().then(()=>{if(window.history.state?.page&&!Gf(this.getScrollRegions(),e))return this.doReplaceState({page:window.history.state.page,scrollRegions:e})}))}saveDocumentScrollPosition(e){yh.add(()=>Promise.resolve().then(()=>{if(window.history.state?.page&&!Gf(this.getDocumentScrollPosition(),e))return this.doReplaceState({page:window.history.state.page,documentScrollPosition:e})}))}getScrollRegions(){return window.history.state?.scrollRegions||[]}getDocumentScrollPosition(){return window.history.state?.documentScrollPosition||{top:0,left:0}}replaceState(e,t=null){if(Gf(this.current,e)){t&&t();return}let{flash:n,...r}=e;if(H.merge(r),!vh){if(this.preserveUrl){t&&t();return}this.current=e,yh.add(()=>this.getPageData(e).then(n=>{let r=()=>this.doReplaceState({page:n},e.url).then(()=>t?.());return bh?new Promise(e=>{setTimeout(()=>r().then(e))}):r()}))}}isHistoryThrottleError(e){return e instanceof Error&&e.name===`SecurityError`&&(e.message.includes(`history.pushState`)||e.message.includes(`history.replaceState`))}isQuotaExceededError(e){return e instanceof Error&&e.name===`QuotaExceededError`}withThrottleProtection(e){return Promise.resolve().then(()=>{try{return e()}catch(e){if(!this.isHistoryThrottleError(e))throw e;console.error(e.message)}})}doReplaceState(e,t){return this.withThrottleProtection(()=>{window.history.replaceState({...e,scrollRegions:e.scrollRegions??window.history.state?.scrollRegions,documentScrollPosition:e.documentScrollPosition??window.history.state?.documentScrollPosition},``,t)})}doPushState(e,t){return this.withThrottleProtection(()=>{try{window.history.pushState(e,``,t)}catch(e){if(!this.isQuotaExceededError(e))throw e;Sh.fireInternalEvent(`historyQuotaExceeded`,t)}})}getState(e,t){return this.current?.[e]??t}deleteState(e){this.current[e]!==void 0&&(delete this.current[e],this.replaceState(this.current))}clearInitialState(e){this.initialState&&this.initialState[e]!==void 0&&delete this.initialState[e]}browserHasHistoryEntry(){return!vh&&!!window.history.state?.page}clear(){Em.remove(Om.key),Em.remove(Om.iv)}setCurrent(e){this.current=e}isValidState(e){return!!e.page}getAllState(){return this.current}};typeof window<`u`&&window.history.scrollRestoration&&(window.history.scrollRestoration=`manual`);var U=new xh,Sh=new class{internalListeners=[];init(){typeof window<`u`&&(window.addEventListener(`popstate`,this.handlePopstateEvent.bind(this)),window.addEventListener(`pageshow`,this.handlePageshowEvent.bind(this)),window.addEventListener(`scroll`,dm(Km.onWindowScroll.bind(Km),100),!0)),typeof document<`u`&&document.addEventListener(`scroll`,dm(Km.onScroll.bind(Km),100),!0)}onGlobalEvent(e,t){return this.registerListener(`inertia:${e}`,(e=>{let n=t(e);e.cancelable&&!e.defaultPrevented&&n===!1&&e.preventDefault()}))}on(e,t){return this.internalListeners.push({event:e,listener:t}),()=>{this.internalListeners=this.internalListeners.filter(e=>e.listener!==t)}}onMissingHistoryItem(){H.clear(),this.fireInternalEvent(`missingHistoryItem`)}fireInternalEvent(e,...t){this.internalListeners.filter(t=>t.event===e).forEach(e=>e.listener(...t))}registerListener(e,t){return document.addEventListener(e,t),()=>document.removeEventListener(e,t)}handlePageshowEvent(e){e.persisted&&U.decrypt().catch(()=>this.onMissingHistoryItem())}handlePopstateEvent(e){let t=e.state||null;if(t===null){let e=sh(H.get().url);e.hash=window.location.hash,U.replaceState({...H.getWithoutFlashData(),url:e.href}),Km.reset();return}if(!U.isValidState(t))return this.onMissingHistoryItem();U.decrypt(t.page).then(e=>{if(H.get().version!==e.version){this.onMissingHistoryItem();return}Zg.cancelAll({prefetch:!1}),H.setQuietly(e,{preserveState:!1}).then(()=>{Km.restore(U.getScrollRegions()),ym(H.get());let t={},n=H.get().props;for(let[r,i]of Object.entries(e.initialDeferredProps??e.deferredProps??{})){let e=i.filter(e=>ep(n,e)===void 0);e.length>0&&(t[r]=e)}Object.keys(t).length>0&&this.fireInternalEvent(`loadDeferredProps`,t)})}).catch(()=>{this.onMissingHistoryItem()})}},Ch=new class{type;constructor(){this.type=this.resolveType()}resolveType(){return typeof window>`u`?`navigate`:window.performance?.getEntriesByType(`navigation`)[0]?.type??`navigate`}get(){return this.type}isBackForward(){return this.type===`back_forward`}isReload(){return this.type===`reload`}},wh=class{static handle(){this.clearRememberedStateOnReload(),[this.handleBackForward,this.handleLocation,this.handleDefault].find(e=>e.bind(this)())}static clearRememberedStateOnReload(){Ch.isReload()&&(U.deleteState(U.rememberedState),U.clearInitialState(U.rememberedState))}static handleBackForward(){if(!Ch.isBackForward()||!U.browserHasHistoryEntry())return!1;let e=U.getScrollRegions();return U.decrypt().then(t=>{H.set(t,{preserveScroll:!0,preserveState:!0}).then(()=>{Km.restore(e),ym(H.get())})}).catch(()=>{Sh.onMissingHistoryItem()}),!0}static handleLocation(){if(!Em.exists(Em.locationVisitKey))return!1;let e=Em.get(Em.locationVisitKey)||{};return Em.remove(Em.locationVisitKey),typeof window<`u`&&H.setUrlHash(window.location.hash),U.decrypt(H.get()).then(()=>{let t=U.getState(U.rememberedState,{}),n=U.getScrollRegions();H.remember(t),H.set(H.get(),{preserveScroll:e.preserveScroll,preserveState:!0}).then(()=>{e.preserveScroll&&Km.restore(n),this.fireInitialEvents()})}).catch(()=>{Sh.onMissingHistoryItem()}),!0}static handleDefault(){typeof window<`u`&&H.setUrlHash(window.location.hash),H.set(H.get(),{preserveScroll:!0,preserveState:!0}).then(()=>{Ch.isReload()?Km.restore(U.getScrollRegions()):Km.scrollToAnchor(),this.fireInitialEvents()})}static fireInitialEvents(){let e=H.get();ym(e),Object.keys(e.flash).length>0&&queueMicrotask(()=>Tm(e.flash))}},Th=class{id=null;throttle=!1;keepAlive=!1;cb;interval;cbCount=0;constructor(e,t,n){this.keepAlive=n.keepAlive??!1,this.cb=t,this.interval=e,(n.autoStart??!0)&&this.start()}stop(){this.id&&clearInterval(this.id)}start(){typeof window>`u`||(this.stop(),this.id=window.setInterval(()=>{(!this.throttle||this.cbCount%10==0)&&this.cb(),this.throttle&&this.cbCount++},this.interval))}isInBackground(e){this.throttle=this.keepAlive?!1:e,this.throttle&&(this.cbCount=0)}},Eh=new class{polls=[];constructor(){this.setupVisibilityListener()}add(e,t,n){let r=new Th(e,t,n);return this.polls.push(r),{stop:()=>r.stop(),start:()=>r.start()}}clear(){this.polls.forEach(e=>e.stop()),this.polls=[]}setupVisibilityListener(){typeof document>`u`||document.addEventListener(`visibilitychange`,()=>{this.polls.forEach(e=>e.isInBackground(document.hidden))},!1)}},Dh=new class{requestHandlers=[];responseHandlers=[];errorHandlers=[];onRequest(e){return this.requestHandlers.push(e),()=>{this.requestHandlers=this.requestHandlers.filter(t=>t!==e)}}onResponse(e){return this.responseHandlers.push(e),()=>{this.responseHandlers=this.responseHandlers.filter(t=>t!==e)}}onError(e){return this.errorHandlers.push(e),()=>{this.errorHandlers=this.errorHandlers.filter(t=>t!==e)}}async processRequest(e){let t=e;for(let e of this.requestHandlers)t=await e(t);return t}async processResponse(e){let t=e;for(let e of this.responseHandlers)t=await e(t);return t}async processError(e){for(let t of this.errorHandlers)await t(e)}},Oh=class extends Error{code;url;constructor(e,t,n){super(n?`${e} (${n})`:e),this.name=`HttpError`,this.code=t,this.url=n}},kh=class extends Oh{response;constructor(e,t,n){super(e,`ERR_HTTP_RESPONSE`,n),this.name=`HttpResponseError`,this.response=t}},Ah=class extends Oh{constructor(e=`Request was cancelled`,t){super(e,`ERR_CANCELLED`,t),this.name=`HttpCancelledError`}},jh=class extends Oh{cause;constructor(e,t,n){super(e,`ERR_NETWORK`,t),this.name=`HttpNetworkError`,this.cause=n}};function Mh(e){let t=document.cookie.match(RegExp(`(^|;\\s*)(`+e+`)=([^;]*)`));return t?decodeURIComponent(t[3]):null}function Nh(e){let t={};return e.getAllResponseHeaders().split(`\r
`).forEach(e=>{let n=e.indexOf(`:`);n>0&&(t[e.slice(0,n).toLowerCase().trim()]=e.slice(n+1).trim())}),t}function Ph(e,t){if(!t.headers)return;let n=t.data instanceof FormData;Object.entries(t.headers).forEach(([t,r])=>{(t.toLowerCase()!==`content-type`||!n)&&e.setRequestHeader(t,String(r))})}function Fh(e,t){if(!t||Object.keys(t).length===0)return e;let[n]=lh(`get`,e,t);return n}var Ih=class{xsrfCookieName;xsrfHeaderName;constructor(e={}){this.xsrfCookieName=e.xsrfCookieName??`XSRF-TOKEN`,this.xsrfHeaderName=e.xsrfHeaderName??`X-XSRF-TOKEN`}async request(e){let t=await Dh.processRequest(e);try{let e=await this.doRequest(t);return await Dh.processResponse(e)}catch(e){throw(e instanceof kh||e instanceof jh||e instanceof Ah)&&await Dh.processError(e),e}}doRequest(e){return new Promise((t,n)=>{let r=new XMLHttpRequest,i=Fh(e.url,e.params);r.open(e.method.toUpperCase(),i,!0);let a=Mh(this.xsrfCookieName);a&&r.setRequestHeader(this.xsrfHeaderName,a);let o=null;e.data!==null&&e.data!==void 0&&(e.data instanceof FormData?o=e.data:typeof e.data==`object`?(o=JSON.stringify(e.data),!e.headers?.[`Content-Type`]&&!e.headers?.[`content-type`]&&r.setRequestHeader(`Content-Type`,`application/json`)):o=String(e.data)),Ph(r,e),e.onUploadProgress&&(r.upload.onprogress=t=>{let n=t.lengthComputable?t.loaded/t.total:void 0;e.onUploadProgress({progress:n,percentage:n?Math.round(n*100):0,loaded:t.loaded,total:t.lengthComputable?t.total:void 0})}),e.signal&&e.signal.addEventListener(`abort`,()=>r.abort()),r.onabort=()=>n(new Ah(`Request was cancelled`,e.url)),r.onerror=()=>n(new jh(`Network error`,e.url)),r.onload=()=>{let i={status:r.status,data:r.responseText,headers:Nh(r)};r.status>=400?n(new kh(`Request failed with status ${r.status}`,i,e.url)):t(i)},r.send(o)})}},Lh=new Ih;function Rh(e){return!(`request`in e)}var zh={getClient(){return Lh},setClient(e){if(!Rh(e)){Lh=e;return}Lh=new Ih(e),e.xsrfCookieName&&Gp.withXsrfCookieName(e.xsrfCookieName),e.xsrfHeaderName&&Gp.withXsrfHeaderName(e.xsrfHeaderName)},onRequest:Dh.onRequest.bind(Dh),onResponse:Dh.onResponse.bind(Dh),onError:Dh.onError.bind(Dh),processRequest:Dh.processRequest.bind(Dh),processResponse:Dh.processResponse.bind(Dh),processError:Dh.processError.bind(Dh)},Bh=class e{callbacks=[];params;constructor(e){if(!e.prefetch)this.params=e;else{let t={onBefore:this.wrapCallback(e,`onBefore`),onBeforeUpdate:this.wrapCallback(e,`onBeforeUpdate`),onStart:this.wrapCallback(e,`onStart`),onProgress:this.wrapCallback(e,`onProgress`),onFinish:this.wrapCallback(e,`onFinish`),onCancel:this.wrapCallback(e,`onCancel`),onSuccess:this.wrapCallback(e,`onSuccess`),onError:this.wrapCallback(e,`onError`),onHttpException:this.wrapCallback(e,`onHttpException`),onNetworkError:this.wrapCallback(e,`onNetworkError`),onFlash:this.wrapCallback(e,`onFlash`),onCancelToken:this.wrapCallback(e,`onCancelToken`),onPrefetched:this.wrapCallback(e,`onPrefetched`),onPrefetching:this.wrapCallback(e,`onPrefetching`)};this.params={...e,...t,onPrefetchResponse:e.onPrefetchResponse||(()=>{}),onPrefetchError:e.onPrefetchError||(()=>{})}}}static create(t){return new e(t)}data(){return this.params.method===`get`?null:this.params.data}queryParams(){return this.params.method===`get`?this.params.data:{}}isPartial(){return this.params.only.length>0||this.params.except.length>0||this.params.reset.length>0}isPrefetch(){return this.params.prefetch===!0}isDeferredPropsRequest(){return this.params.deferredProps===!0}onCancelToken(e){this.params.onCancelToken({cancel:e})}markAsFinished(){this.params.completed=!0,this.params.cancelled=!1,this.params.interrupted=!1}markAsCancelled({cancelled:e=!0,interrupted:t=!1}){this.params.onCancel(),this.params.completed=!1,this.params.cancelled=e,this.params.interrupted=t}wasCancelledAtAll(){return this.params.cancelled||this.params.interrupted}onFinish(){this.params.onFinish(this.params)}onStart(){this.params.onStart(this.params)}onPrefetching(){this.params.onPrefetching(this.params)}onPrefetchResponse(e){this.params.onPrefetchResponse&&this.params.onPrefetchResponse(e)}onPrefetchError(e){this.params.onPrefetchError&&this.params.onPrefetchError(e)}all(){return this.params}headers(){let e={...this.params.headers};this.isPartial()&&(e[`X-Inertia-Partial-Component`]=H.get().component);let t=this.params.only.concat(this.params.reset);return t.length>0&&(e[`X-Inertia-Partial-Data`]=t.join(`,`)),this.params.except.length>0&&(e[`X-Inertia-Partial-Except`]=this.params.except.join(`,`)),this.params.reset.length>0&&(e[`X-Inertia-Reset`]=this.params.reset.join(`,`)),this.params.errorBag&&this.params.errorBag.length>0&&(e[`X-Inertia-Error-Bag`]=this.params.errorBag),e}setPreserveOptions(t){this.params.preserveScroll=e.resolvePreserveOption(this.params.preserveScroll,t),this.params.preserveState=e.resolvePreserveOption(this.params.preserveState,t)}runCallbacks(){this.callbacks.forEach(({name:e,args:t})=>{this.params[e](...t)})}merge(e){this.params={...this.params,...e}}wrapCallback(e,t){return(...n)=>{this.recordCallback(t,n),e[t](...n)}}recordCallback(e,t){this.callbacks.push({name:e,args:t})}static resolvePreserveOption(e,t){return typeof e==`function`?e(t):e===`errors`?Object.keys(t.props.errors||{}).length>0:e}},Vh={createIframeAndPage(e){typeof e==`object`&&(e=`All Inertia requests must receive a valid Inertia response, however a plain JSON response was received.<hr>${JSON.stringify(e)}`);let t=document.createElement(`html`);t.innerHTML=e,t.querySelectorAll(`a`).forEach(e=>e.setAttribute(`target`,`_top`));let n=document.createElement(`iframe`);return n.style.backgroundColor=`white`,n.style.borderRadius=`5px`,n.style.width=`100%`,n.style.height=`100%`,{iframe:n,page:t}},show(e){let{iframe:t,page:n}=this.createIframeAndPage(e);t.style.boxSizing=`border-box`,t.style.display=`block`;let r=document.createElement(`dialog`);r.id=`inertia-error-dialog`,Object.assign(r.style,{width:`calc(100vw - 100px)`,height:`calc(100vh - 100px)`,padding:`0`,margin:`auto`,border:`none`,backgroundColor:`transparent`});let i=document.createElement(`style`);if(i.textContent=`
      dialog#inertia-error-dialog::backdrop {
        background-color: rgba(0, 0, 0, 0.6);
      }

      dialog#inertia-error-dialog:focus {
        outline: none;
      }
    `,document.head.appendChild(i),r.addEventListener(`click`,e=>{e.target===r&&r.close()}),r.addEventListener(`close`,()=>{i.remove(),r.remove()}),r.appendChild(t),document.body.prepend(r),r.showModal(),r.focus(),!t.contentWindow)throw Error(`iframe not yet ready.`);t.contentWindow.document.open(),t.contentWindow.document.write(n.outerHTML),t.contentWindow.document.close()}},Hh=new _h,Uh=class e{constructor(e,t,n){this.requestParams=e,this.response=t,this.originatingPage=n}wasPrefetched=!1;processed=!1;static create(t,n,r){return new e(t,n,r)}isProcessed(){return this.processed}async handlePrefetch(){fh(this.requestParams.all().url,window.location)&&this.handle()}async handle(){return Hh.add(()=>this.process())}async process(){if(this.requestParams.all().prefetch)return this.wasPrefetched=!0,this.requestParams.all().prefetch=!1,this.requestParams.all().onPrefetched(this.response,this.requestParams.all()),Cm(this.response,this.requestParams.all()),Promise.resolve();if(this.requestParams.runCallbacks(),this.processed=!0,!this.isInertiaResponse())return this.handleNonInertiaResponse();if(this.isHttpException()){let e={...this.response,data:this.getDataFromResponse(this.response.data)};if(this.requestParams.all().onHttpException(e)===!1||!_m(e))return}await U.processQueue(),U.preserveUrl=this.requestParams.all().preserveUrl,await this.setPage();let{flash:e}=H.get();Object.keys(e).length>0&&!this.requestParams.isDeferredPropsRequest()&&(Tm(e),this.requestParams.all().onFlash(e));let t=H.get().props.errors||{};if(Object.keys(t).length>0){let e=this.getScopedErrors(t);return mm(e),this.requestParams.all().onError(e)}Zg.flushByCacheTags(this.requestParams.all().invalidateCacheTags||[]),this.wasPrefetched||Zg.flush(H.get().url),Sm(H.get()),await this.requestParams.all().onSuccess(H.get()),U.preserveUrl=!1}mergeParams(e){this.requestParams.merge(e)}getPageResponse(){let e=this.getDataFromResponse(this.response.data);return typeof e==`object`?this.response.data={...e,flash:e.flash??{}}:this.response.data=e}async handleNonInertiaResponse(){if(this.isInertiaRedirect()){Zg.visit(this.getHeader(`x-inertia-redirect`),{...this.requestParams.all(),method:`get`,data:{}});return}if(this.isLocationVisit()){let e=sh(this.getHeader(`x-inertia-location`));return dh(this.requestParams.all().url,e),this.locationVisit(e)}let e={...this.response,data:this.getDataFromResponse(this.response.data)};if(this.requestParams.all().onHttpException(e)!==!1&&_m(e))return Vh.show(e.data)}isInertiaResponse(){return this.hasHeader(`x-inertia`)}isHttpException(){return this.response.status>=400}hasStatus(e){return this.response.status===e}getHeader(e){return this.response.headers[e]}hasHeader(e){return this.getHeader(e)!==void 0}isInertiaRedirect(){return this.hasStatus(409)&&this.hasHeader(`x-inertia-redirect`)}isLocationVisit(){return this.hasStatus(409)&&this.hasHeader(`x-inertia-location`)}locationVisit(e){try{if(Em.set(Em.locationVisitKey,{preserveScroll:this.requestParams.all().preserveScroll===!0}),typeof window>`u`)return;fh(window.location,e)?window.location.reload():window.location.href=e.href}catch{return!1}}async setPage(){let e=this.getPageResponse();return this.shouldSetPage(e)?(this.mergeProps(e),H.mergeOncePropsIntoResponse(e),this.preserveOptimisticProps(e),this.preserveEqualProps(e),await this.setRememberedState(e),this.requestParams.setPreserveOptions(e),e.url=U.preserveUrl?H.get().url:this.pageUrl(e),this.requestParams.all().onBeforeUpdate(e),vm(e),H.set(e,{replace:this.requestParams.all().replace,preserveScroll:this.requestParams.all().preserveScroll,preserveState:this.requestParams.all().preserveState,viewTransition:this.requestParams.all().viewTransition})):Promise.resolve()}getDataFromResponse(e){if(typeof e!=`string`)return e;try{return JSON.parse(e)}catch{return e}}shouldSetPage(e){if(!this.requestParams.all().async||this.originatingPage.component!==e.component)return!0;if(this.originatingPage.component!==H.get().component)return!1;let t=sh(this.originatingPage.url),n=sh(H.get().url);return t.origin===n.origin&&t.pathname===n.pathname}pageUrl(e){let t=sh(e.url);return e.preserveFragment?t.hash=this.requestParams.all().url.hash:dh(this.requestParams.all().url,t),t.pathname+t.search+t.hash}preserveOptimisticProps(e){if(Zg.hasPendingOptimistic())for(let t of Object.keys(e.props))H.hasBaseline(t)&&(H.updateBaseline(t,e.props[t]),e.props[t]=H.get().props[t])}preserveEqualProps(e){if(e.component!==H.get().component)return;let t=H.get().props;Object.entries(e.props).forEach(([n,r])=>{Gf(r,t[n])&&(e.props[n]=t[n])})}mergeProps(e){if(!this.requestParams.isPartial()||e.component!==H.get().component)return;let t=e.mergeProps||[],n=e.prependProps||[],r=e.deepMergeProps||[],i=e.matchPropsOn||[],a=(t,n)=>{let r=ep(H.get().props,t),a=ep(e.props,t);if(Array.isArray(a)){let o=this.mergeOrMatchItems(r||[],a,t,i,n);gp(e.props,t,o)}else if(typeof a==`object`&&a){let n={...r||{},...a};gp(e.props,t,n)}};t.forEach(e=>a(e,!0)),n.forEach(e=>a(e,!1)),r.forEach(t=>{let n=ep(H.get().props,t),r=ep(e.props,t),a=(e,t,n)=>Array.isArray(t)?this.mergeOrMatchItems(e,t,n,i):typeof t==`object`&&t?Object.keys(t).reduce((r,i)=>(r[i]=a(e?e[i]:void 0,t[i],`${n}.${i}`),r),{...e}):t;gp(e.props,t,a(n,r,t))});let o=new Set([...this.requestParams.all().only,...this.requestParams.all().except].filter(e=>e.includes(`.`)).map(e=>e.split(`.`)[0]));for(let t of o){let n=H.get().props[t];this.isObject(n)&&this.isObject(e.props[t])&&(e.props[t]=this.deepMergeObjects(n,e.props[t]))}e.props={...H.get().props,...e.props},this.shouldPreserveErrors(e)&&(e.props.errors=H.get().props.errors),H.get().scrollProps&&(e.scrollProps={...H.get().scrollProps||{},...e.scrollProps||{}}),H.hasOnceProps()&&(e.onceProps={...H.get().onceProps||{},...e.onceProps||{}}),this.requestParams.isDeferredPropsRequest()&&(e.flash={...H.get().flash});let s=H.get().initialDeferredProps;s&&Object.keys(s).length>0&&(e.initialDeferredProps=s)}shouldPreserveErrors(e){if(!this.requestParams.all().preserveErrors)return!1;let t=H.get().props.errors;if(!t||Object.keys(t).length===0)return!1;let n=e.props.errors;return!(n&&Object.keys(n).length>0)}isObject(e){return e&&typeof e==`object`&&!Array.isArray(e)}deepMergeObjects(e,t){let n={...e};for(let r of Object.keys(t)){let i=e[r],a=t[r];this.isObject(i)&&this.isObject(a)?n[r]=this.deepMergeObjects(i,a):n[r]=a}return n}mergeOrMatchItems(e,t,n,r,i=!0){let a=Array.isArray(e)?e:[],o=r.find(e=>e.split(`.`).slice(0,-1).join(`.`)===n);if(!o)return i?[...a,...t]:[...t,...a];let s=o.split(`.`).pop()||``,c=new Map;return t.forEach(e=>{this.hasUniqueProperty(e,s)&&c.set(e[s],e)}),i?this.appendWithMatching(a,t,c,s):this.prependWithMatching(a,t,c,s)}appendWithMatching(e,t,n,r){let i=e.map(e=>this.hasUniqueProperty(e,r)&&n.has(e[r])?n.get(e[r]):e),a=t.filter(t=>this.hasUniqueProperty(t,r)?!e.some(e=>this.hasUniqueProperty(e,r)&&e[r]===t[r]):!0);return[...i,...a]}prependWithMatching(e,t,n,r){let i=e.filter(e=>this.hasUniqueProperty(e,r)?!n.has(e[r]):!0);return[...t,...i]}hasUniqueProperty(e,t){return e&&typeof e==`object`&&t in e}async setRememberedState(e){let t=await U.getState(U.rememberedState,{});this.requestParams.all().preserveState&&t&&e.component===H.get().component&&(e.rememberedState=t)}getScopedErrors(e){return this.requestParams.all().errorBag?e[this.requestParams.all().errorBag||``]||{}:e}},Wh=class e{constructor(e,t,{optimistic:n=!1}={}){this.page=t,this.requestParams=Bh.create(e),this.cancelToken=new AbortController,this.optimistic=n}response;cancelToken;requestParams;requestHasFinished=!1;optimistic;static create(t,n,r){return new e(t,n,r)}isPrefetch(){return this.requestParams.isPrefetch()}isOptimistic(){return this.optimistic}isPendingOptimistic(){return this.isOptimistic()&&(!this.response||!this.response.isProcessed())}async send(){this.requestParams.onCancelToken(()=>this.cancel({cancelled:!0})),xm(this.requestParams.all()),this.requestParams.onStart(),this.requestParams.all().prefetch&&(this.requestParams.onPrefetching(),wm(this.requestParams.all()));let e=this.requestParams.all().prefetch;return zh.getClient().request({method:this.requestParams.all().method,url:uh(this.requestParams.all().url).href,data:this.requestParams.data(),signal:this.cancelToken.signal,headers:this.getHeaders(),onUploadProgress:this.onProgress.bind(this)}).then(e=>(this.response=Uh.create(this.requestParams,e,this.page),this.response.handle())).catch(e=>e instanceof kh?(this.response=Uh.create(this.requestParams,e.response,this.page),this.response.handle()):Promise.reject(e)).catch(t=>{if(!(t instanceof Ah)&&this.requestParams.all().onNetworkError(t)!==!1&&hm(t))return e&&this.requestParams.onPrefetchError(t),Promise.reject(t)}).finally(()=>{this.finish(),e&&this.response&&this.requestParams.onPrefetchResponse(this.response)})}finish(){this.requestParams.wasCancelledAtAll()||(this.requestParams.markAsFinished(),this.fireFinishEvents())}fireFinishEvents(){this.requestHasFinished||(this.requestHasFinished=!0,gm(this.requestParams.all()),this.requestParams.onFinish())}cancel({cancelled:e=!1,interrupted:t=!1}){this.requestHasFinished||(this.cancelToken.abort(),this.requestParams.markAsCancelled({cancelled:e,interrupted:t}),this.fireFinishEvents())}onProgress(e){this.requestParams.data()instanceof FormData&&(bm(e),this.requestParams.all().onProgress(e))}getHeaders(){let e={...this.requestParams.headers(),Accept:`text/html, application/xhtml+xml`,"X-Requested-With":`XMLHttpRequest`,"X-Inertia":!0},t=H.get();t.version&&(e[`X-Inertia-Version`]=t.version);let n=Object.entries(t.onceProps||{}).filter(([,e])=>ep(t.props,e.prop)===void 0?!1:!e.expiresAt||e.expiresAt>Date.now()).map(([e])=>e);return n.length>0&&(e[`X-Inertia-Except-Once-Props`]=n.join(`,`)),e}},Gh=class{requests=[];maxConcurrent;interruptible;constructor({maxConcurrent:e,interruptible:t}){this.maxConcurrent=e,this.interruptible=t}send(e){this.requests.push(e),e.send().finally(()=>{this.requests=this.requests.filter(t=>t!==e)})}interruptInFlight(){this.cancel({interrupted:!0},!1)}cancelInFlight({prefetch:e=!0,optimistic:t=!0}={}){this.requests.filter(t=>e||!t.isPrefetch()).filter(e=>t||!e.isOptimistic()).forEach(e=>e.cancel({cancelled:!0}))}cancel({cancelled:e=!1,interrupted:t=!1}={},n=!1){!n&&!this.shouldCancel()||this.requests.shift()?.cancel({cancelled:e,interrupted:t})}shouldCancel(){return this.interruptible&&this.requests.length>=this.maxConcurrent}hasPendingOptimistic(){return this.requests.some(e=>e.isPendingOptimistic())}},Kh=()=>{},qh=class{syncRequestStream=new Gh({maxConcurrent:1,interruptible:!0});asyncRequestStream=new Gh({maxConcurrent:1/0,interruptible:!1});clientVisitQueue=new _h;pendingOptimisticCallback=void 0;init({initialPage:e,resolveComponent:t,swapComponent:n,onFlash:r}){H.init({initialPage:e,resolveComponent:t,swapComponent:n,onFlash:r}),wh.handle(),Sh.init(),Sh.on(`missingHistoryItem`,()=>{typeof window<`u`&&this.visit(window.location.href,{preserveState:!0,preserveScroll:!0,replace:!0})}),Sh.on(`loadDeferredProps`,e=>{this.loadDeferredProps(e)}),Sh.on(`historyQuotaExceeded`,e=>{window.location.href=e})}optimistic(e){return this.pendingOptimisticCallback=e,this}get(e,t={},n={}){return this.visit(e,{...n,method:`get`,data:t})}post(e,t={},n={}){return this.visit(e,{preserveState:!0,...n,method:`post`,data:t})}put(e,t={},n={}){return this.visit(e,{preserveState:!0,...n,method:`put`,data:t})}patch(e,t={},n={}){return this.visit(e,{preserveState:!0,...n,method:`patch`,data:t})}delete(e,t={}){return this.visit(e,{preserveState:!0,...t,method:`delete`})}reload(e={}){return this.doReload(e)}doReload(e={}){if(!(typeof window>`u`))return this.visit(window.location.href,{...e,preserveScroll:!0,preserveState:!0,async:!0,headers:{...e.headers||{},"Cache-Control":`no-cache`}})}remember(e,t=`default`){U.remember(e,t)}restore(e=`default`){return U.restore(e)}on(e,t){return typeof window>`u`?()=>{}:Sh.onGlobalEvent(e,t)}hasPendingOptimistic(){return this.asyncRequestStream.hasPendingOptimistic()}cancelAll({async:e=!0,prefetch:t=!0,sync:n=!0}={}){e&&this.asyncRequestStream.cancelInFlight({prefetch:t}),n&&this.syncRequestStream.cancelInFlight()}poll(e,t={},n={}){return Eh.add(e,()=>this.reload({preserveErrors:!0,...t}),{autoStart:n.autoStart??!0,keepAlive:n.keepAlive??!1})}visit(e,t={}){t.optimistic=t.optimistic??this.pendingOptimisticCallback,this.pendingOptimisticCallback=void 0,t.optimistic&&(t.async=t.async??!0);let n=this.getPendingVisit(e,{...t,showProgress:t.showProgress??(!t.async||!!t.optimistic)}),r=this.getVisitEvents(t);if(r.onBefore(n)===!1||!pm(n))return;let i=sh(H.get().url);(n.only.length>0||n.except.length>0||n.reset.length>0?ph(n.url,i):fh(n.url,i))||this.asyncRequestStream.cancelInFlight({prefetch:!1,optimistic:!1}),n.async||this.syncRequestStream.interruptInFlight(),t.optimistic&&this.applyOptimisticUpdate(t.optimistic,r),!H.isCleared()&&!n.preserveUrl&&Km.save();let a={...n,...r},o=()=>{let e=Vm.get(a);e?(Lg.reveal(e.inFlight),Vm.use(e,a)):(Lg.reveal(!0),(n.async?this.asyncRequestStream:this.syncRequestStream).send(Wh.create(a,H.get(),{optimistic:!!t.optimistic})))};Array.isArray(n.component)&&(console.error(`The "component" prop received an array of components (${n.component.join(`, `)}), but only a single component string is supported for instant visits. Pass an explicit component name instead.`),n.component=null),n.component?U.processQueue().then(()=>{this.performInstantSwap(n).then(()=>{a.preserveState=!0,a.replace=!0,a.viewTransition=!1,o()})}):o()}getCached(e,t={}){return Vm.findCached(this.getPrefetchParams(e,t))}flush(e,t={}){Vm.remove(this.getPrefetchParams(e,t))}flushAll(){Vm.removeAll()}flushByCacheTags(e){Vm.removeByTags(Array.isArray(e)?e:[e])}getPrefetching(e,t={}){return Vm.findInFlight(this.getPrefetchParams(e,t))}prefetch(e,t={},n={}){if((t.method??(mh(e)?e.method:`get`))!==`get`)throw Error(`Prefetch requests must use the GET method`);let r=this.getPendingVisit(e,{...t,async:!0,showProgress:!1,prefetch:!0,viewTransition:!1});if(r.url.origin+r.url.pathname+r.url.search===window.location.origin+window.location.pathname+window.location.search)return;let i=this.getVisitEvents(t);if(i.onBefore(r)===!1||!pm(r))return;Lg.hide(),this.asyncRequestStream.interruptInFlight();let a={...r,...i};new Promise(e=>{let t=()=>{H.get()?e():setTimeout(t,50)};t()}).then(()=>{Vm.add(a,e=>{this.asyncRequestStream.send(Wh.create(e,H.get()))},{cacheFor:um.get(`prefetch.cacheFor`),cacheTags:[],...n})})}clearHistory(){U.clear()}decryptHistory(){return U.decrypt()}resolveComponent(e,t){return H.resolve(e,t)}replace(e){this.clientVisit(e,{replace:!0})}replaceProp(e,t,n){this.replace({preserveScroll:!0,preserveState:!0,props(n){let r=typeof t==`function`?t(ep(n,e),n):t;return gp(V(n),e,r)},...n||{}})}appendToProp(e,t,n){this.replaceProp(e,(e,n)=>{let r=typeof t==`function`?t(e,n):t;return Array.isArray(e)||(e=e===void 0?[]:[e]),[...e,r]},n)}prependToProp(e,t,n){this.replaceProp(e,(e,n)=>{let r=typeof t==`function`?t(e,n):t;return Array.isArray(e)||(e=e===void 0?[]:[e]),[r,...e]},n)}push(e){this.clientVisit(e)}flash(e,t){let n=H.get().flash,r;if(typeof e==`function`)r=e(n);else if(typeof e==`string`)r={...n,[e]:t};else if(e&&Object.keys(e).length)r={...n,...e};else return;H.setFlash(r),Object.keys(r).length&&Tm(r)}clientVisit(e,{replace:t=!1}={}){this.clientVisitQueue.add(()=>this.performClientVisit(e,{replace:t}))}performClientVisit(e,{replace:t=!1}={}){let n=H.get(),r=typeof e.props==`function`?Object.fromEntries(Object.values(n.onceProps??{}).map(e=>[e.prop,ep(n.props,e.prop)])):{},i=typeof e.props==`function`?e.props(n.props,r):e.props??n.props,a=typeof e.flash==`function`?e.flash(n.flash):e.flash,{viewTransition:o,onError:s,onFinish:c,onFlash:l,onSuccess:u,...d}=e,f={...n,...d,flash:a??{},props:i},p=Bh.resolvePreserveOption(e.preserveScroll??!1,f),m=Bh.resolvePreserveOption(e.preserveState??!1,f);return H.set(f,{replace:t,preserveScroll:p,preserveState:m,viewTransition:o}).then(()=>{let t=H.get().flash;Object.keys(t).length>0&&(Tm(t),l?.(t));let n=H.get().props.errors||{};if(Object.keys(n).length===0){u?.(H.get());return}let r=e.errorBag?n[e.errorBag||``]||{}:n;s?.(r)}).finally(()=>c?.(e))}performInstantSwap(e){let t=H.get(),n=Object.fromEntries((t.sharedProps??[]).filter(e=>e in t.props).map(e=>[e,t.props[e]])),r=typeof e.pageProps==`function`?e.pageProps(V(t.props),V(n)):e.pageProps,i=r===null?{...n}:{...r},a={component:e.component,url:e.url.pathname+e.url.search+e.url.hash,version:t.version,props:{...i,errors:{}},flash:{},clearHistory:!1,encryptHistory:t.encryptHistory,sharedProps:t.sharedProps,rememberedState:{}};return H.set(a,{replace:e.replace,preserveScroll:Bh.resolvePreserveOption(e.preserveScroll,a),preserveState:!1,viewTransition:e.viewTransition})}getPrefetchParams(e,t){return{...this.getPendingVisit(e,{...t,async:!0,showProgress:!1,prefetch:!0,viewTransition:!1}),...this.getVisitEvents(t)}}getPendingVisit(e,t){if(mh(e)){let n=e;e=n.url,t.method=t.method??n.method}let n=um.get(`visitOptions`),r=n&&n(e.toString(),V(t))||{},i={method:`get`,data:{},replace:!1,preserveScroll:!1,preserveState:!1,only:[],except:[],headers:{},errorBag:``,forceFormData:!1,queryStringArrayFormat:`brackets`,async:!1,showProgress:!0,fresh:!1,reset:[],preserveUrl:!1,preserveErrors:!1,prefetch:!1,invalidateCacheTags:[],viewTransition:!1,component:null,pageProps:null,...t,...r},[a,o]=ch(e,i.data,i.method,i.forceFormData,i.queryStringArrayFormat),s={cancelled:!1,completed:!1,interrupted:!1,...i,url:a,data:o};return s.prefetch&&(s.headers.Purpose=`prefetch`),s}getVisitEvents(e){return{onCancelToken:e.onCancelToken||Kh,onBefore:e.onBefore||Kh,onBeforeUpdate:e.onBeforeUpdate||Kh,onStart:e.onStart||Kh,onProgress:e.onProgress||Kh,onFinish:e.onFinish||Kh,onCancel:e.onCancel||Kh,onSuccess:e.onSuccess||Kh,onError:e.onError||Kh,onHttpException:e.onHttpException||Kh,onNetworkError:e.onNetworkError||Kh,onFlash:e.onFlash||Kh,onPrefetched:e.onPrefetched||Kh,onPrefetching:e.onPrefetching||Kh}}applyOptimisticUpdate(e,t){let n=H.get().props,r=e(V(n));if(!r)return;let i=[];for(let e of Object.keys(r))Gf(n[e],r[e])||i.push(e);if(i.length===0)return;let a=H.nextOptimisticId(),o=H.get().component;for(let e of i)H.setBaseline(e,V(n[e]));H.registerOptimistic(a,e),H.setPropsQuietly({...n,...r});let s=!0,c=t.onSuccess;t.onSuccess=e=>(s=!1,c(e));let l=t.onFinish;t.onFinish=e=>{if(H.unregisterOptimistic(a),s&&H.get().component===o){let e=H.replayOptimistics();Object.keys(e).length>0&&H.setPropsQuietly({...H.get().props,...e})}return H.pendingOptimisticCount()===0&&H.clearOptimisticState(),l(e)}}loadDeferredProps(e){e&&Object.values(e).forEach(e=>{this.doReload({only:e,deferredProps:!0,preserveErrors:!0})})}},Jh=class{static createWayfinderCallback(...e){return()=>e.length===1?mh(e[0])?e[0]:e[0]():{method:typeof e[0]==`function`?e[0]():e[0],url:typeof e[1]==`function`?e[1]():e[1]}}static parseUseFormArguments(...e){return e.length===0?{rememberKey:null,data:{},precognitionEndpoint:null}:e.length===1?{rememberKey:null,data:e[0],precognitionEndpoint:null}:e.length===2?typeof e[0]==`string`?{rememberKey:e[0],data:e[1],precognitionEndpoint:null}:{rememberKey:null,data:e[1],precognitionEndpoint:this.createWayfinderCallback(e[0])}:{rememberKey:null,data:e[2],precognitionEndpoint:this.createWayfinderCallback(e[0],e[1])}}static parseSubmitArguments(e,t){return e.length===3||e.length===2&&typeof e[0]==`string`?{method:e[0],url:e[1],options:e[2]??{}}:mh(e[0])?{...e[0],options:e[1]??{}}:{...t(),options:e[0]??{}}}static mergeHeadersForValidation(e,t,n){let r=e=>(e.headers={...n??{},...e.headers??{}},e);return e&&typeof e==`object`&&!(`target`in e)?e=r(e):t&&typeof t==`object`?t=r(t):typeof e==`string`?t=r(t??{}):e=r(e??{}),[e,t]}};function Yh(e){return e.includes(`.`)?e.replace(/\\\./g,`__ESCAPED_DOT__`).split(/(\[[^\]]*\])/).filter(Boolean).map(e=>e.startsWith(`[`)&&e.endsWith(`]`)?e:e.split(`.`).reduce((e,t,n)=>n===0?t:`${e}[${t}]`)).join(``).replace(/__ESCAPED_DOT__/g,`.`):e}function Xh(e){let t=[],n=/([^\[\]]+)|\[(\d*)\]/g,r;for(;(r=n.exec(e))!==null;)r[1]===void 0?r[2]!==void 0&&t.push(r[2]===``?``:Number(r[2])):t.push(r[1]);return t}function Zh(e,t,n){let r=e;for(let e=0;e<t.length-1;e++)t[e]in r||(r[t[e]]={}),r=r[t[e]];r[t[t.length-1]]=n}function Qh(e){let t=Object.keys(e),n=t.filter(e=>/^\d+$/.test(e)).map(Number).sort((e,t)=>e-t);return t.length===n.length&&n.length>0&&n[0]===0&&n.every((e,t)=>e===t)}function $h(e){if(Array.isArray(e))return e.map($h);if(typeof e!=`object`||!e||qm(e))return e;if(Qh(e)){let t=[];for(let n=0;n<Object.keys(e).length;n++)t[n]=$h(e[n]);return t}let t={};for(let n in e)t[n]=$h(e[n]);return t}function eg(e){let t={};for(let[n,r]of e.entries()){if(r instanceof File&&r.size===0&&r.name===``)continue;let e=Xh(Yh(n));if(e[e.length-1]===``){let n=e.slice(0,-1),i=ep(t,n);if(Array.isArray(i))i.push(r);else if(i&&typeof i==`object`&&!qm(i)){let e=Object.keys(i).filter(e=>/^\d+$/.test(e)).map(Number).sort((e,t)=>e-t);gp(t,n,e.length>0?[...e.map(e=>i[e]),r]:[r])}else gp(t,n,[r]);continue}Zh(t,e.map(String),r)}return $h(t)}var tg={buildDOMElement(e){let t=document.createElement(`template`);t.innerHTML=e;let n=t.content.firstChild;if(!e.startsWith(`<script `))return n;let r=document.createElement(`script`);return r.innerHTML=n.innerHTML,n.getAttributeNames().forEach(e=>{r.setAttribute(e,n.getAttribute(e)||``)}),r},isInertiaManagedElement(e){return e.nodeType===Node.ELEMENT_NODE&&e.getAttribute(`data-inertia`)!==null},findMatchingElementIndex(e,t){let n=e.getAttribute(`data-inertia`);return n===null?-1:t.findIndex(e=>e.getAttribute(`data-inertia`)===n)},update:dm(function(e){let t=e.map(e=>this.buildDOMElement(e)),n=Array.from(document.head.childNodes).filter(e=>this.isInertiaManagedElement(e));t.some(e=>e instanceof HTMLTitleElement)&&document.head.querySelectorAll(`title:not([data-inertia])`).forEach(e=>e.remove()),n.forEach(e=>{let n=this.findMatchingElementIndex(e,t);if(n===-1){e.remove();return}let r=t.splice(n,1)[0];r&&!e.isEqualNode(r)&&e.replaceWith(r)}),t.forEach(e=>{document.head.appendChild(e)})},1)};function ng(e,t,n){let r={},i=0;function a(){let e=i+=1;return r[e]=[],e.toString()}function o(e){e===null||Object.keys(r).indexOf(e)===-1||(delete r[e],u())}function s(e){Object.keys(r).indexOf(e)===-1&&(r[e]=[])}function c(e,t=[]){e!==null&&Object.keys(r).indexOf(e)>-1&&(r[e]=t),u()}function l(){let e=t(``),n={...e?{title:`<title data-inertia="">${e}</title>`}:{}},i=Object.values(r).reduce((e,t)=>e.concat(t),[]).reduce((e,n)=>{if(n.indexOf(`<`)===-1)return e;if(n.indexOf(`<title `)===0){let r=n.match(/(<title [^>]+>)(.*?)(<\/title>)/);return e.title=r?`${r[1]}${t(r[2])}${r[3]}`:n,e}let r=n.match(/ data-inertia="[^"]+"/);return r?e[r[0]]=n:e[Object.keys(e).length]=n,e},n);return Object.values(i)}function u(){e?n(l()):tg.update(l())}return u(),{forceUpdate:u,createProvider:function(){let e=a();return{reconnect:()=>s(e),update:t=>c(e,t),disconnect:()=>o(e)}}}}new _h;function rg(){let e={},t={},n={shared:e,named:t},r=new Set,i=!1,a=()=>{n={shared:e,named:t}},o=()=>{i||(i=!0,queueMicrotask(()=>{i=!1,r.forEach(e=>e())}))};return{set(t){let n={...e,...t};Gf(e,n)||(e=n,a(),o())},setFor(e,n){let r=t[e]||{},i={...r,...n};Gf(r,i)||(t={...t,[e]:i},a(),o())},reset(){e={},t={},a(),o()},subscribe(e){return r.add(e),()=>r.delete(e)},get:()=>n}}function ig(e){return typeof e==`object`&&!!e&&!Array.isArray(e)}function ag(e){return ig(e)&&`component`in e}function og(e,t){return`component`in e&&t(e.component)}function sg(e,t){return!ig(e)||t(e)||og(e,t)?!1:Object.values(e).every(e=>t(e)||Array.isArray(e)&&t(e[0])||ag(e)&&t(e.component))}function cg(e,t){return ig(e)&&!t(e)&&!og(e,t)&&!sg(e,t)}function lg(e,t){if(cg(e,t))return!0;if(!ig(e)||t(e)||og(e,t))return!1;let n=Object.values(e);return n.length>0&&n.every(e=>typeof e==`function`)}function ug(e,t){return Array.isArray(e)&&e.length===2&&t(e[0])&&ig(e[1])&&!t(e[1])}function dg(e,t){if(Array.isArray(e)&&t(e[0]))return{component:e[0],props:e[1]??{}};if(ag(e)&&t(e.component))return{component:e.component,props:e.props??{}};if(t(e))return{component:e,props:{}};throw Error(`Invalid layout definition: received ${typeof e}`)}function fg(e,t,n){return!e||n&&n(e)?[]:sg(e,t)?Object.entries(e).map(([e,n])=>({...dg(n,t),name:e})):ug(e,t)?[{component:e[0],props:e[1]??{}}]:Array.isArray(e)?e.map(e=>dg(e,t)):ag(e)&&t(e.component)?[{component:e.component,props:e.props??{}}]:t(e)?[{component:e,props:{}}]:[]}function pg(e){return e.target instanceof HTMLElement&&e.target.isContentEditable||e.defaultPrevented}function mg(e){let t=e.currentTarget.tagName.toLowerCase()===`a`;return!(pg(e)||t&&e.altKey||t&&e.ctrlKey||t&&e.metaKey||t&&e.shiftKey||t&&`button`in e&&e.button!==0)}function hg(e){let t=e.currentTarget.tagName.toLowerCase()===`button`;return!pg(e)&&(e.key===`Enter`||t&&e.key===` `)}var gg=`nprogress`,_g,vg,yg={minimum:.08,easing:`linear`,speed:200,trickle:!0,trickleSpeed:200,showSpinner:!0,barSelector:`[role="bar"]`,spinnerSelector:`[role="spinner"]`,parent:`body`,color:`#29d`,includeCSS:!0,popover:null,template:[`<div class="bar" role="bar">`,`<div class="peg"></div>`,`</div>`,`<div class="spinner" role="spinner">`,`<div class="spinner-icon"></div>`,`</div>`].join(``)},bg=null,xg=!1,Sg=e=>{Object.assign(yg,e),_g=yg.popover??`popover`in HTMLElement.prototype,yg.includeCSS&&Fg(yg.color),vg=document.createElement(`div`),vg.id=gg,vg.innerHTML=yg.template,_g&&(vg.popover=`manual`)},Cg=e=>{let t=wg();e=Mg(e,yg.minimum,1),bg=e===1?null:e;let n=Og(!t),r=n.querySelector(yg.barSelector),i=yg.speed,a=yg.easing;n.offsetWidth,Pg(t=>{let o={transition:`all ${i}ms ${a}`,transform:`translate3d(${Ng(e)}%,0,0)`};for(let e in o)r.style[e]=o[e];if(e!==1)return setTimeout(t,i);n.style.transition=`none`,n.style.opacity=`1`,n.offsetWidth,setTimeout(()=>{n.style.transition=`all ${i}ms linear`,n.style.opacity=`0`,setTimeout(()=>{Ag(),n.style.transition=``,n.style.opacity=``,t()},i)},i)})},wg=()=>typeof bg==`number`,Tg=()=>{bg||Cg(0);let e=function(){setTimeout(function(){bg&&(Dg(),e())},yg.trickleSpeed)};yg.trickle&&e()},Eg=e=>{!e&&!bg||(Dg(.3+.5*Math.random()),Cg(1))},Dg=e=>{let t=bg;if(t===null)return Tg();if(!(t>1))return e=typeof e==`number`?e:(()=>{let e={.1:[0,.2],.04:[.2,.5],.02:[.5,.8],.005:[.8,.99]};for(let n in e)if(t>=e[n][0]&&t<e[n][1])return parseFloat(n);return 0})(),Cg(Mg(t+e,0,.994))},Og=e=>{if(jg())return document.getElementById(gg);document.documentElement.classList.add(`${gg}-busy`);let t=vg.querySelector(yg.barSelector),n=e?`-100`:Ng(bg||0);if(t.style.transition=`all 0 linear`,t.style.transform=`translate3d(${n}%,0,0)`,yg.showSpinner||vg.querySelector(yg.spinnerSelector)?.remove(),_g)document.body.appendChild(vg),xg||vg.showPopover();else{let e=kg();e!==document.body&&e.classList.add(`${gg}-custom-parent`),e.appendChild(vg),xg&&(vg.style.display=`none`)}return vg},kg=()=>document.querySelector(yg.parent),Ag=()=>{if(document.documentElement.classList.remove(`${gg}-busy`),_g&&vg?.isConnected)try{vg.hidePopover()}catch{}_g||kg().classList.remove(`${gg}-custom-parent`),vg?.remove()},jg=()=>document.getElementById(gg)!==null;function Mg(e,t,n){return e<t?t:e>n?n:e}var Ng=e=>(-1+e)*100,Pg=(()=>{let e=[],t=()=>{let n=e.shift();n&&n(t)};return n=>{e.push(n),e.length===1&&t()}})(),Fg=e=>{let t=document.createElement(`style`);t.textContent=`
    #${gg} {
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

    #${gg}::backdrop {
      display: none;
    }

    #${gg} .bar {
      background: ${e};

      position: fixed;
      z-index: 1031;
      top: 0;
      left: 0;

      width: 100%;
      height: 2px;
    }

    #${gg} .peg {
      display: block;
      position: absolute;
      right: 0px;
      width: 100px;
      height: 100%;
      box-shadow: 0 0 10px ${e}, 0 0 5px ${e};
      opacity: 1.0;

      transform: rotate(3deg) translate(0px, -4px);
    }

    #${gg} .spinner {
      display: block;
      position: fixed;
      z-index: 1031;
      top: 15px;
      right: 15px;
    }

    #${gg} .spinner-icon {
      width: 18px;
      height: 18px;
      box-sizing: border-box;

      border: solid 2px transparent;
      border-top-color: ${e};
      border-left-color: ${e};
      border-radius: 50%;

      animation: ${gg}-spinner 400ms linear infinite;
    }

    .${gg}-custom-parent {
      overflow: hidden;
      position: relative;
    }

    .${gg}-custom-parent #${gg} .spinner,
    .${gg}-custom-parent #${gg} .bar {
      position: absolute;
    }

    @keyframes ${gg}-spinner {
      0%   { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  `,document.head.appendChild(t)},Ig={configure:Sg,isStarted:wg,done:Eg,set:Cg,remove:Ag,start:Tg,status:bg,show:()=>{if(xg=!1,vg?.isConnected)if(_g)try{vg.showPopover()}catch{}else vg.style.display=``},hide:()=>{if(xg=!0,vg?.isConnected)if(_g)try{vg.hidePopover()}catch{}else vg.style.display=`none`}},Lg=new class{hideCount=0;start(){Ig.start()}reveal(e=!1){this.hideCount=Math.max(0,this.hideCount-1),(e||this.hideCount===0)&&Ig.show()}hide(){this.hideCount++,Ig.hide()}set(e){Ig.set(Math.max(0,Math.min(1,e)))}finish(){Ig.done()}reset(){Ig.set(0)}remove(){Ig.done(),Ig.remove()}isStarted(){return Ig.isStarted()}getStatus(){return Ig.status}};function Rg(e){document.addEventListener(`inertia:start`,t=>zg(t,e)),document.addEventListener(`inertia:progress`,Bg)}function zg(e,t){e.detail.visit.showProgress||Lg.hide();let n=setTimeout(()=>Lg.start(),t);document.addEventListener(`inertia:finish`,e=>Vg(e,n),{once:!0})}function Bg(e){Lg.isStarted()&&e.detail.progress?.percentage&&Lg.set(Math.max(Lg.getStatus(),e.detail.progress.percentage/100*.9))}function Vg(e,t){clearTimeout(t),Lg.isStarted()&&(e.detail.visit.completed?Lg.finish():e.detail.visit.interrupted?Lg.reset():e.detail.visit.cancelled&&Lg.remove())}function Hg({delay:e=250,color:t=`#29d`,includeCSS:n=!0,showSpinner:r=!1,popover:i=null}={}){Rg(e),Ig.configure({showSpinner:r,includeCSS:n,color:t,popover:i})}var Ug=Symbol(`FormComponentReset`);function Wg(e){return e instanceof HTMLInputElement||e instanceof HTMLSelectElement||e instanceof HTMLTextAreaElement}function Gg(e,t){let n=e.value,r=e.checked;switch(e.type.toLowerCase()){case`checkbox`:e.checked=t.includes(e.value);break;case`radio`:e.checked=t[0]===e.value;break;case`file`:e.value=``;break;case`button`:case`submit`:case`reset`:case`image`:break;default:e.value=t[0]!==null&&t[0]!==void 0?String(t[0]):``}return e.value!==n||e.checked!==r}function Kg(e,t){let n=e.value,r=Array.from(e.selectedOptions).map(e=>e.value);if(e.multiple){let n=t.map(e=>String(e));Array.from(e.options).forEach(e=>{e.selected=n.includes(e.value)})}else e.value=t[0]===void 0?``:String(t[0]);let i=Array.from(e.selectedOptions).map(e=>e.value);return e.multiple?JSON.stringify(r.sort())!==JSON.stringify(i.sort()):e.value!==n}function qg(e,t){if(e.disabled){if(e instanceof HTMLInputElement){let t=e.value,n=e.checked;switch(e.type.toLowerCase()){case`checkbox`:case`radio`:return e.checked=e.defaultChecked,e.checked!==n;case`file`:return e.value=``,t!==``;case`button`:case`submit`:case`reset`:case`image`:return!1;default:return e.value=e.defaultValue,e.value!==t}}else if(e instanceof HTMLSelectElement){let t=Array.from(e.selectedOptions).map(e=>e.value);Array.from(e.options).forEach(e=>{e.selected=e.defaultSelected});let n=Array.from(e.selectedOptions).map(e=>e.value);return JSON.stringify(t.sort())!==JSON.stringify(n.sort())}else if(e instanceof HTMLTextAreaElement){let t=e.value;return e.value=e.defaultValue,e.value!==t}return!1}if(e instanceof HTMLInputElement)return Gg(e,t);if(e instanceof HTMLSelectElement)return Kg(e,t);if(e instanceof HTMLTextAreaElement){let n=e.value;return e.value=t[0]===void 0?``:String(t[0]),e.value!==n}return!1}function Jg(e,t){let n=!1;return e instanceof RadioNodeList||e instanceof HTMLCollection?Array.from(e).forEach((e,r)=>{e instanceof Element&&Wg(e)&&(e instanceof HTMLInputElement&&[`checkbox`,`radio`].includes(e.type.toLowerCase())?qg(e,t)&&(n=!0):qg(e,t[r]===void 0?[t[0]??null].filter(Boolean):[t[r]])&&(n=!0))}):Wg(e)&&(n=qg(e,t)),n}function Yg(e,t,n){if(!e)return;let r=!n||n.length===0;if(r){let r=new FormData(e),i=Array.from(e.elements).map(e=>Wg(e)?e.name:``).filter(Boolean);n=[...new Set([...t.keys(),...r.keys(),...i])]}let i=!1;n.forEach(n=>{let r=e.elements.namedItem(n);r&&Jg(r,t.getAll(n))&&(i=!0)}),i&&r&&e.dispatchEvent(new CustomEvent(`reset`,{bubbles:!0,cancelable:!0,detail:{[Ug]:!0}}))}function Xg(e,t,n){return`<script data-page="${e}" type="application/json">${JSON.stringify(t).replace(/\//g,`\\/`)}<\/script><div data-server-rendered="true" id="${e}">${n}</div>`}var Zg=new qh;function Qg(e){let t=Object.create(null);for(let n of e.split(`,`))t[n]=1;return e=>e in t}var $g={},e_=()=>{},t_=Object.assign,n_=(e,t)=>{let n=e.indexOf(t);n>-1&&e.splice(n,1)},r_=Object.prototype.hasOwnProperty,i_=(e,t)=>r_.call(e,t),a_=Array.isArray,o_=e=>p_(e)===`[object Map]`,s_=e=>p_(e)===`[object Set]`,c_=e=>typeof e==`function`,l_=e=>typeof e==`string`,u_=e=>typeof e==`symbol`,d_=e=>typeof e==`object`&&!!e,f_=Object.prototype.toString,p_=e=>f_.call(e),m_=e=>p_(e).slice(8,-1),h_=e=>p_(e)===`[object Object]`,g_=e=>l_(e)&&e!==`NaN`&&e[0]!==`-`&&``+parseInt(e,10)===e,__=(e,t)=>!Object.is(e,t),v_=(e,t,n,r=!1)=>{Object.defineProperty(e,t,{configurable:!0,enumerable:!1,writable:r,value:n})},y_,b_=class{constructor(e=!1){this.detached=e,this._active=!0,this._on=0,this.effects=[],this.cleanups=[],this._isPaused=!1,this.__v_skip=!0,this.parent=y_,!e&&y_&&(this.index=(y_.scopes||=[]).push(this)-1)}get active(){return this._active}pause(){if(this._active){this._isPaused=!0;let e,t;if(this.scopes)for(e=0,t=this.scopes.length;e<t;e++)this.scopes[e].pause();for(e=0,t=this.effects.length;e<t;e++)this.effects[e].pause()}}resume(){if(this._active&&this._isPaused){this._isPaused=!1;let e,t;if(this.scopes)for(e=0,t=this.scopes.length;e<t;e++)this.scopes[e].resume();for(e=0,t=this.effects.length;e<t;e++)this.effects[e].resume()}}run(e){if(this._active){let t=y_;try{return y_=this,e()}finally{y_=t}}}on(){++this._on===1&&(this.prevScope=y_,y_=this)}off(){if(this._on>0&&--this._on===0){if(y_===this)y_=this.prevScope;else{let e=y_;for(;e;){if(e.prevScope===this){e.prevScope=this.prevScope;break}e=e.prevScope}}this.prevScope=void 0}}stop(e){if(this._active){this._active=!1;let t,n;for(t=0,n=this.effects.length;t<n;t++)this.effects[t].stop();for(this.effects.length=0,t=0,n=this.cleanups.length;t<n;t++)this.cleanups[t]();if(this.cleanups.length=0,this.scopes){for(t=0,n=this.scopes.length;t<n;t++)this.scopes[t].stop(!0);this.scopes.length=0}if(!this.detached&&this.parent&&!e){let e=this.parent.scopes.pop();e&&e!==this&&(this.parent.scopes[this.index]=e,e.index=this.index)}this.parent=void 0}}};function x_(e){return new b_(e)}function S_(){return y_}function C_(e,t=!1){y_&&y_.cleanups.push(e)}var W,w_=new WeakSet,T_=class{constructor(e){this.fn=e,this.deps=void 0,this.depsTail=void 0,this.flags=5,this.next=void 0,this.cleanup=void 0,this.scheduler=void 0,y_&&y_.active&&y_.effects.push(this)}pause(){this.flags|=64}resume(){this.flags&64&&(this.flags&=-65,w_.has(this)&&(w_.delete(this),this.trigger()))}notify(){this.flags&2&&!(this.flags&32)||this.flags&8||k_(this)}run(){if(!(this.flags&1))return this.fn();this.flags|=2,W_(this),M_(this);let e=W,t=B_;W=this,B_=!0;try{return this.fn()}finally{N_(this),W=e,B_=t,this.flags&=-3}}stop(){if(this.flags&1){for(let e=this.deps;e;e=e.nextDep)I_(e);this.deps=this.depsTail=void 0,W_(this),this.onStop&&this.onStop(),this.flags&=-2}}trigger(){this.flags&64?w_.add(this):this.scheduler?this.scheduler():this.runIfDirty()}runIfDirty(){P_(this)&&this.run()}get dirty(){return P_(this)}},E_=0,D_,O_;function k_(e,t=!1){if(e.flags|=8,t){e.next=O_,O_=e;return}e.next=D_,D_=e}function A_(){E_++}function j_(){if(--E_>0)return;if(O_){let e=O_;for(O_=void 0;e;){let t=e.next;e.next=void 0,e.flags&=-9,e=t}}let e;for(;D_;){let t=D_;for(D_=void 0;t;){let n=t.next;if(t.next=void 0,t.flags&=-9,t.flags&1)try{t.trigger()}catch(t){e||=t}t=n}}if(e)throw e}function M_(e){for(let t=e.deps;t;t=t.nextDep)t.version=-1,t.prevActiveLink=t.dep.activeLink,t.dep.activeLink=t}function N_(e){let t,n=e.depsTail,r=n;for(;r;){let e=r.prevDep;r.version===-1?(r===n&&(n=e),I_(r),L_(r)):t=r,r.dep.activeLink=r.prevActiveLink,r.prevActiveLink=void 0,r=e}e.deps=t,e.depsTail=n}function P_(e){for(let t=e.deps;t;t=t.nextDep)if(t.dep.version!==t.version||t.dep.computed&&(F_(t.dep.computed)||t.dep.version!==t.version))return!0;return!!e._dirty}function F_(e){if(e.flags&4&&!(e.flags&16)||(e.flags&=-17,e.globalVersion===G_)||(e.globalVersion=G_,!e.isSSR&&e.flags&128&&(!e.deps&&!e._dirty||!P_(e))))return;e.flags|=2;let t=e.dep,n=W,r=B_;W=e,B_=!0;try{M_(e);let n=e.fn(e._value);(t.version===0||__(n,e._value))&&(e.flags|=128,e._value=n,t.version++)}catch(e){throw t.version++,e}finally{W=n,B_=r,N_(e),e.flags&=-3}}function I_(e,t=!1){let{dep:n,prevSub:r,nextSub:i}=e;if(r&&(r.nextSub=i,e.prevSub=void 0),i&&(i.prevSub=r,e.nextSub=void 0),n.subs===e&&(n.subs=r,!r&&n.computed)){n.computed.flags&=-5;for(let e=n.computed.deps;e;e=e.nextDep)I_(e,!0)}!t&&!--n.sc&&n.map&&n.map.delete(n.key)}function L_(e){let{prevDep:t,nextDep:n}=e;t&&(t.nextDep=n,e.prevDep=void 0),n&&(n.prevDep=t,e.nextDep=void 0)}function R_(e,t){e.effect instanceof T_&&(e=e.effect.fn);let n=new T_(e);t&&t_(n,t);try{n.run()}catch(e){throw n.stop(),e}let r=n.run.bind(n);return r.effect=n,r}function z_(e){e.effect.stop()}var B_=!0,V_=[];function H_(){V_.push(B_),B_=!1}function U_(){let e=V_.pop();B_=e===void 0?!0:e}function W_(e){let{cleanup:t}=e;if(e.cleanup=void 0,t){let e=W;W=void 0;try{t()}finally{W=e}}}var G_=0,K_=class{constructor(e,t){this.sub=e,this.dep=t,this.version=t.version,this.nextDep=this.prevDep=this.nextSub=this.prevSub=this.prevActiveLink=void 0}},q_=class{constructor(e){this.computed=e,this.version=0,this.activeLink=void 0,this.subs=void 0,this.map=void 0,this.key=void 0,this.sc=0,this.__v_skip=!0}track(e){if(!W||!B_||W===this.computed)return;let t=this.activeLink;if(t===void 0||t.sub!==W)t=this.activeLink=new K_(W,this),W.deps?(t.prevDep=W.depsTail,W.depsTail.nextDep=t,W.depsTail=t):W.deps=W.depsTail=t,J_(t);else if(t.version===-1&&(t.version=this.version,t.nextDep)){let e=t.nextDep;e.prevDep=t.prevDep,t.prevDep&&(t.prevDep.nextDep=e),t.prevDep=W.depsTail,t.nextDep=void 0,W.depsTail.nextDep=t,W.depsTail=t,W.deps===t&&(W.deps=e)}return t}trigger(e){this.version++,G_++,this.notify(e)}notify(e){A_();try{for(let e=this.subs;e;e=e.prevSub)e.sub.notify()&&e.sub.dep.notify()}finally{j_()}}};function J_(e){if(e.dep.sc++,e.sub.flags&4){let t=e.dep.computed;if(t&&!e.dep.subs){t.flags|=20;for(let e=t.deps;e;e=e.nextDep)J_(e)}let n=e.dep.subs;n!==e&&(e.prevSub=n,n&&(n.nextSub=e)),e.dep.subs=e}}var Y_=new WeakMap,X_=Symbol(``),Z_=Symbol(``),Q_=Symbol(``);function $_(e,t,n){if(B_&&W){let t=Y_.get(e);t||Y_.set(e,t=new Map);let r=t.get(n);r||(t.set(n,r=new q_),r.map=t,r.key=n),r.track()}}function ev(e,t,n,r,i,a){let o=Y_.get(e);if(!o){G_++;return}let s=e=>{e&&e.trigger()};if(A_(),t===`clear`)o.forEach(s);else{let i=a_(e),a=i&&g_(n);if(i&&n===`length`){let e=Number(r);o.forEach((t,n)=>{(n===`length`||n===Q_||!u_(n)&&n>=e)&&s(t)})}else switch((n!==void 0||o.has(void 0))&&s(o.get(n)),a&&s(o.get(Q_)),t){case`add`:i?a&&s(o.get(`length`)):(s(o.get(X_)),o_(e)&&s(o.get(Z_)));break;case`delete`:i||(s(o.get(X_)),o_(e)&&s(o.get(Z_)));break;case`set`:o_(e)&&s(o.get(X_));break}}j_()}function tv(e,t){let n=Y_.get(e);return n&&n.get(t)}function nv(e){let t=G(e);return t===e?t:($_(t,`iterate`,Q_),Gv(e)?t:t.map(Jv))}function rv(e){return $_(e=G(e),`iterate`,Q_),e}function iv(e,t){return Wv(e)?Yv(Uv(e)?Jv(t):t):Jv(t)}var av={__proto__:null,[Symbol.iterator](){return ov(this,Symbol.iterator,e=>iv(this,e))},concat(...e){return nv(this).concat(...e.map(e=>a_(e)?nv(e):e))},entries(){return ov(this,`entries`,e=>(e[1]=iv(this,e[1]),e))},every(e,t){return cv(this,`every`,e,t,void 0,arguments)},filter(e,t){return cv(this,`filter`,e,t,e=>e.map(e=>iv(this,e)),arguments)},find(e,t){return cv(this,`find`,e,t,e=>iv(this,e),arguments)},findIndex(e,t){return cv(this,`findIndex`,e,t,void 0,arguments)},findLast(e,t){return cv(this,`findLast`,e,t,e=>iv(this,e),arguments)},findLastIndex(e,t){return cv(this,`findLastIndex`,e,t,void 0,arguments)},forEach(e,t){return cv(this,`forEach`,e,t,void 0,arguments)},includes(...e){return uv(this,`includes`,e)},indexOf(...e){return uv(this,`indexOf`,e)},join(e){return nv(this).join(e)},lastIndexOf(...e){return uv(this,`lastIndexOf`,e)},map(e,t){return cv(this,`map`,e,t,void 0,arguments)},pop(){return dv(this,`pop`)},push(...e){return dv(this,`push`,e)},reduce(e,...t){return lv(this,`reduce`,e,t)},reduceRight(e,...t){return lv(this,`reduceRight`,e,t)},shift(){return dv(this,`shift`)},some(e,t){return cv(this,`some`,e,t,void 0,arguments)},splice(...e){return dv(this,`splice`,e)},toReversed(){return nv(this).toReversed()},toSorted(e){return nv(this).toSorted(e)},toSpliced(...e){return nv(this).toSpliced(...e)},unshift(...e){return dv(this,`unshift`,e)},values(){return ov(this,`values`,e=>iv(this,e))}};function ov(e,t,n){let r=rv(e),i=r[t]();return r!==e&&!Gv(e)&&(i._next=i.next,i.next=()=>{let e=i._next();return e.done||(e.value=n(e.value)),e}),i}var sv=Array.prototype;function cv(e,t,n,r,i,a){let o=rv(e),s=o!==e&&!Gv(e),c=o[t];if(c!==sv[t]){let t=c.apply(e,a);return s?Jv(t):t}let l=n;o!==e&&(s?l=function(t,r){return n.call(this,iv(e,t),r,e)}:n.length>2&&(l=function(t,r){return n.call(this,t,r,e)}));let u=c.call(o,l,r);return s&&i?i(u):u}function lv(e,t,n,r){let i=rv(e),a=i!==e&&!Gv(e),o=n,s=!1;i!==e&&(a?(s=r.length===0,o=function(t,r,i){return s&&(s=!1,t=iv(e,t)),n.call(this,t,iv(e,r),i,e)}):n.length>3&&(o=function(t,r,i){return n.call(this,t,r,i,e)}));let c=i[t](o,...r);return s?iv(e,c):c}function uv(e,t,n){let r=G(e);$_(r,`iterate`,Q_);let i=r[t](...n);return(i===-1||i===!1)&&Kv(n[0])?(n[0]=G(n[0]),r[t](...n)):i}function dv(e,t,n=[]){H_(),A_();let r=G(e)[t].apply(e,n);return j_(),U_(),r}var fv=Qg(`__proto__,__v_isRef,__isVue`),pv=new Set(Object.getOwnPropertyNames(Symbol).filter(e=>e!==`arguments`&&e!==`caller`).map(e=>Symbol[e]).filter(u_));function mv(e){u_(e)||(e=String(e));let t=G(this);return $_(t,`has`,e),t.hasOwnProperty(e)}var hv=class{constructor(e=!1,t=!1){this._isReadonly=e,this._isShallow=t}get(e,t,n){if(t===`__v_skip`)return e.__v_skip;let r=this._isReadonly,i=this._isShallow;if(t===`__v_isReactive`)return!r;if(t===`__v_isReadonly`)return r;if(t===`__v_isShallow`)return i;if(t===`__v_raw`)return n===(r?i?Fv:Pv:i?Nv:Mv).get(e)||Object.getPrototypeOf(e)===Object.getPrototypeOf(n)?e:void 0;let a=a_(e);if(!r){let e;if(a&&(e=av[t]))return e;if(t===`hasOwnProperty`)return mv}let o=Reflect.get(e,t,Xv(e)?e:n);if((u_(t)?pv.has(t):fv(t))||(r||$_(e,`get`,t),i))return o;if(Xv(o)){let e=a&&g_(t)?o:o.value;return r&&d_(e)?Bv(e):e}return d_(o)?r?Bv(o):Rv(o):o}},gv=class extends hv{constructor(e=!1){super(!1,e)}set(e,t,n,r){let i=e[t],a=a_(e)&&g_(t);if(!this._isShallow){let e=Wv(i);if(!Gv(n)&&!Wv(n)&&(i=G(i),n=G(n)),!a&&Xv(i)&&!Xv(n))return e||(i.value=n),!0}let o=a?Number(t)<e.length:i_(e,t),s=Reflect.set(e,t,n,Xv(e)?e:r);return e===G(r)&&(o?__(n,i)&&ev(e,`set`,t,n,i):ev(e,`add`,t,n)),s}deleteProperty(e,t){let n=i_(e,t),r=e[t],i=Reflect.deleteProperty(e,t);return i&&n&&ev(e,`delete`,t,void 0,r),i}has(e,t){let n=Reflect.has(e,t);return(!u_(t)||!pv.has(t))&&$_(e,`has`,t),n}ownKeys(e){return $_(e,`iterate`,a_(e)?`length`:X_),Reflect.ownKeys(e)}},_v=class extends hv{constructor(e=!1){super(!0,e)}set(e,t){return!0}deleteProperty(e,t){return!0}},vv=new gv,yv=new _v,bv=new gv(!0),xv=new _v(!0),Sv=e=>e,Cv=e=>Reflect.getPrototypeOf(e);function wv(e,t,n){return function(...r){let i=this.__v_raw,a=G(i),o=o_(a),s=e===`entries`||e===Symbol.iterator&&o,c=e===`keys`&&o,l=i[e](...r),u=n?Sv:t?Yv:Jv;return!t&&$_(a,`iterate`,c?Z_:X_),t_(Object.create(l),{next(){let{value:e,done:t}=l.next();return t?{value:e,done:t}:{value:s?[u(e[0]),u(e[1])]:u(e),done:t}}})}}function Tv(e){return function(...t){return e===`delete`?!1:e===`clear`?void 0:this}}function Ev(e,t){let n={get(n){let r=this.__v_raw,i=G(r),a=G(n);e||(__(n,a)&&$_(i,`get`,n),$_(i,`get`,a));let{has:o}=Cv(i),s=t?Sv:e?Yv:Jv;if(o.call(i,n))return s(r.get(n));if(o.call(i,a))return s(r.get(a));r!==i&&r.get(n)},get size(){let t=this.__v_raw;return!e&&$_(G(t),`iterate`,X_),t.size},has(t){let n=this.__v_raw,r=G(n),i=G(t);return e||(__(t,i)&&$_(r,`has`,t),$_(r,`has`,i)),t===i?n.has(t):n.has(t)||n.has(i)},forEach(n,r){let i=this,a=i.__v_raw,o=G(a),s=t?Sv:e?Yv:Jv;return!e&&$_(o,`iterate`,X_),a.forEach((e,t)=>n.call(r,s(e),s(t),i))}};return t_(n,e?{add:Tv(`add`),set:Tv(`set`),delete:Tv(`delete`),clear:Tv(`clear`)}:{add(e){let n=G(this),r=Cv(n),i=G(e),a=!t&&!Gv(e)&&!Wv(e)?i:e;return r.has.call(n,a)||__(e,a)&&r.has.call(n,e)||__(i,a)&&r.has.call(n,i)||(n.add(a),ev(n,`add`,a,a)),this},set(e,n){!t&&!Gv(n)&&!Wv(n)&&(n=G(n));let r=G(this),{has:i,get:a}=Cv(r),o=i.call(r,e);o||=(e=G(e),i.call(r,e));let s=a.call(r,e);return r.set(e,n),o?__(n,s)&&ev(r,`set`,e,n,s):ev(r,`add`,e,n),this},delete(e){let t=G(this),{has:n,get:r}=Cv(t),i=n.call(t,e);i||=(e=G(e),n.call(t,e));let a=r?r.call(t,e):void 0,o=t.delete(e);return i&&ev(t,`delete`,e,void 0,a),o},clear(){let e=G(this),t=e.size!==0,n=e.clear();return t&&ev(e,`clear`,void 0,void 0,void 0),n}}),[`keys`,`values`,`entries`,Symbol.iterator].forEach(r=>{n[r]=wv(r,e,t)}),n}function Dv(e,t){let n=Ev(e,t);return(t,r,i)=>r===`__v_isReactive`?!e:r===`__v_isReadonly`?e:r===`__v_raw`?t:Reflect.get(i_(n,r)&&r in t?n:t,r,i)}var Ov={get:Dv(!1,!1)},kv={get:Dv(!1,!0)},Av={get:Dv(!0,!1)},jv={get:Dv(!0,!0)},Mv=new WeakMap,Nv=new WeakMap,Pv=new WeakMap,Fv=new WeakMap;function Iv(e){switch(e){case`Object`:case`Array`:return 1;case`Map`:case`Set`:case`WeakMap`:case`WeakSet`:return 2;default:return 0}}function Lv(e){return e.__v_skip||!Object.isExtensible(e)?0:Iv(m_(e))}function Rv(e){return Wv(e)?e:Hv(e,!1,vv,Ov,Mv)}function zv(e){return Hv(e,!1,bv,kv,Nv)}function Bv(e){return Hv(e,!0,yv,Av,Pv)}function Vv(e){return Hv(e,!0,xv,jv,Fv)}function Hv(e,t,n,r,i){if(!d_(e)||e.__v_raw&&!(t&&e.__v_isReactive))return e;let a=Lv(e);if(a===0)return e;let o=i.get(e);if(o)return o;let s=new Proxy(e,a===2?r:n);return i.set(e,s),s}function Uv(e){return Wv(e)?Uv(e.__v_raw):!!(e&&e.__v_isReactive)}function Wv(e){return!!(e&&e.__v_isReadonly)}function Gv(e){return!!(e&&e.__v_isShallow)}function Kv(e){return e?!!e.__v_raw:!1}function G(e){let t=e&&e.__v_raw;return t?G(t):e}function qv(e){return!i_(e,`__v_skip`)&&Object.isExtensible(e)&&v_(e,`__v_skip`,!0),e}var Jv=e=>d_(e)?Rv(e):e,Yv=e=>d_(e)?Bv(e):e;function Xv(e){return e?e.__v_isRef===!0:!1}function Zv(e){return $v(e,!1)}function Qv(e){return $v(e,!0)}function $v(e,t){return Xv(e)?e:new ey(e,t)}var ey=class{constructor(e,t){this.dep=new q_,this.__v_isRef=!0,this.__v_isShallow=!1,this._rawValue=t?e:G(e),this._value=t?e:Jv(e),this.__v_isShallow=t}get value(){return this.dep.track(),this._value}set value(e){let t=this._rawValue,n=this.__v_isShallow||Gv(e)||Wv(e);e=n?e:G(e),__(e,t)&&(this._rawValue=e,this._value=n?e:Jv(e),this.dep.trigger())}};function ty(e){e.dep&&e.dep.trigger()}function ny(e){return Xv(e)?e.value:e}function ry(e){return c_(e)?e():ny(e)}var iy={get:(e,t,n)=>t===`__v_raw`?e:ny(Reflect.get(e,t,n)),set:(e,t,n,r)=>{let i=e[t];return Xv(i)&&!Xv(n)?(i.value=n,!0):Reflect.set(e,t,n,r)}};function ay(e){return Uv(e)?e:new Proxy(e,iy)}var oy=class{constructor(e){this.__v_isRef=!0,this._value=void 0;let t=this.dep=new q_,{get:n,set:r}=e(t.track.bind(t),t.trigger.bind(t));this._get=n,this._set=r}get value(){return this._value=this._get()}set value(e){this._set(e)}};function sy(e){return new oy(e)}function cy(e){let t=a_(e)?Array(e.length):{};for(let n in e)t[n]=fy(e,n);return t}var ly=class{constructor(e,t,n){this._object=e,this._defaultValue=n,this.__v_isRef=!0,this._value=void 0,this._key=u_(t)?t:String(t),this._raw=G(e);let r=!0,i=e;if(!a_(e)||u_(this._key)||!g_(this._key))do r=!Kv(i)||Gv(i);while(r&&(i=i.__v_raw));this._shallow=r}get value(){let e=this._object[this._key];return this._shallow&&(e=ny(e)),this._value=e===void 0?this._defaultValue:e}set value(e){if(this._shallow&&Xv(this._raw[this._key])){let t=this._object[this._key];if(Xv(t)){t.value=e;return}}this._object[this._key]=e}get dep(){return tv(this._raw,this._key)}},uy=class{constructor(e){this._getter=e,this.__v_isRef=!0,this.__v_isReadonly=!0,this._value=void 0}get value(){return this._value=this._getter()}};function dy(e,t,n){return Xv(e)?e:c_(e)?new uy(e):d_(e)&&arguments.length>1?fy(e,t,n):Zv(e)}function fy(e,t,n){return new ly(e,t,n)}var py=class{constructor(e,t,n){this.fn=e,this.setter=t,this._value=void 0,this.dep=new q_(this),this.__v_isRef=!0,this.deps=void 0,this.depsTail=void 0,this.flags=16,this.globalVersion=G_-1,this.next=void 0,this.effect=this,this.__v_isReadonly=!t,this.isSSR=n}notify(){if(this.flags|=16,!(this.flags&8)&&W!==this)return k_(this,!0),!0}get value(){let e=this.dep.track();return F_(this),e&&(e.version=this.dep.version),this._value}set value(e){this.setter&&this.setter(e)}};function my(e,t,n=!1){let r,i;return c_(e)?r=e:(r=e.get,i=e.set),new py(r,i,n)}var hy={GET:`get`,HAS:`has`,ITERATE:`iterate`},gy={SET:`set`,ADD:`add`,DELETE:`delete`,CLEAR:`clear`},_y={},vy=new WeakMap,yy=void 0;function by(){return yy}function xy(e,t=!1,n=yy){if(n){let t=vy.get(n);t||vy.set(n,t=[]),t.push(e)}}function Sy(e,t,n=$g){let{immediate:r,deep:i,once:a,scheduler:o,augmentJob:s,call:c}=n,l=e=>i?e:Gv(e)||i===!1||i===0?Cy(e,1):Cy(e),u,d,f,p,m=!1,h=!1;if(Xv(e)?(d=()=>e.value,m=Gv(e)):Uv(e)?(d=()=>l(e),m=!0):a_(e)?(h=!0,m=e.some(e=>Uv(e)||Gv(e)),d=()=>e.map(e=>{if(Xv(e))return e.value;if(Uv(e))return l(e);if(c_(e))return c?c(e,2):e()})):d=c_(e)?t?c?()=>c(e,2):e:()=>{if(f){H_();try{f()}finally{U_()}}let t=yy;yy=u;try{return c?c(e,3,[p]):e(p)}finally{yy=t}}:e_,t&&i){let e=d,t=i===!0?1/0:i;d=()=>Cy(e(),t)}let g=S_(),_=()=>{u.stop(),g&&g.active&&n_(g.effects,u)};if(a&&t){let e=t;t=(...t)=>{e(...t),_()}}let v=h?Array(e.length).fill(_y):_y,y=e=>{if(!(!(u.flags&1)||!u.dirty&&!e))if(t){let e=u.run();if(i||m||(h?e.some((e,t)=>__(e,v[t])):__(e,v))){f&&f();let n=yy;yy=u;try{let n=[e,v===_y?void 0:h&&v[0]===_y?[]:v,p];v=e,c?c(t,3,n):t(...n)}finally{yy=n}}}else u.run()};return s&&s(y),u=new T_(d),u.scheduler=o?()=>o(y,!1):y,p=e=>xy(e,!1,u),f=u.onStop=()=>{let e=vy.get(u);if(e){if(c)c(e,4);else for(let t of e)t();vy.delete(u)}},t?r?y(!0):v=u.run():o?o(y.bind(null,!0),!0):u.run(),_.pause=u.pause.bind(u),_.resume=u.resume.bind(u),_.stop=_,_}function Cy(e,t=1/0,n){if(t<=0||!d_(e)||e.__v_skip||(n||=new Map,(n.get(e)||0)>=t))return e;if(n.set(e,t),t--,Xv(e))Cy(e.value,t,n);else if(a_(e))for(let r=0;r<e.length;r++)Cy(e[r],t,n);else if(s_(e)||o_(e))e.forEach(e=>{Cy(e,t,n)});else if(h_(e)){for(let r in e)Cy(e[r],t,n);for(let r of Object.getOwnPropertySymbols(e))Object.prototype.propertyIsEnumerable.call(e,r)&&Cy(e[r],t,n)}return e}function wy(e){let t=Object.create(null);for(let n of e.split(`,`))t[n]=1;return e=>e in t}var K={},Ty=[],Ey=()=>{},Dy=()=>!1,Oy=e=>e.charCodeAt(0)===111&&e.charCodeAt(1)===110&&(e.charCodeAt(2)>122||e.charCodeAt(2)<97),ky=e=>e.startsWith(`onUpdate:`),Ay=Object.assign,jy=(e,t)=>{let n=e.indexOf(t);n>-1&&e.splice(n,1)},My=Object.prototype.hasOwnProperty,Ny=(e,t)=>My.call(e,t),q=Array.isArray,Py=e=>Uy(e)===`[object Map]`,Fy=e=>Uy(e)===`[object Set]`,Iy=e=>Uy(e)===`[object Date]`,Ly=e=>Uy(e)===`[object RegExp]`,J=e=>typeof e==`function`,Ry=e=>typeof e==`string`,zy=e=>typeof e==`symbol`,By=e=>typeof e==`object`&&!!e,Vy=e=>(By(e)||J(e))&&J(e.then)&&J(e.catch),Hy=Object.prototype.toString,Uy=e=>Hy.call(e),Wy=e=>Uy(e)===`[object Object]`,Gy=wy(`,key,ref,ref_for,ref_key,onVnodeBeforeMount,onVnodeMounted,onVnodeBeforeUpdate,onVnodeUpdated,onVnodeBeforeUnmount,onVnodeUnmounted`),Ky=e=>{let t=Object.create(null);return(n=>t[n]||(t[n]=e(n)))},qy=/-\w/g,Jy=Ky(e=>e.replace(qy,e=>e.slice(1).toUpperCase())),Yy=/\B([A-Z])/g,Xy=Ky(e=>e.replace(Yy,`-$1`).toLowerCase()),Zy=Ky(e=>e.charAt(0).toUpperCase()+e.slice(1)),Qy=Ky(e=>e?`on${Zy(e)}`:``),$y=(e,t)=>!Object.is(e,t),eb=(e,...t)=>{for(let n=0;n<e.length;n++)e[n](...t)},tb=(e,t,n,r=!1)=>{Object.defineProperty(e,t,{configurable:!0,enumerable:!1,writable:r,value:n})},nb=e=>{let t=parseFloat(e);return isNaN(t)?e:t},rb=e=>{let t=Ry(e)?Number(e):NaN;return isNaN(t)?e:t},ib,ab=()=>ib||=typeof globalThis<`u`?globalThis:typeof self<`u`?self:typeof window<`u`?window:typeof global<`u`?global:{},ob=wy(`Infinity,undefined,NaN,isFinite,isNaN,parseFloat,parseInt,decodeURI,decodeURIComponent,encodeURI,encodeURIComponent,Math,Number,Date,Array,Object,Boolean,String,RegExp,Map,Set,JSON,Intl,BigInt,console,Error,Symbol`);function sb(e){if(q(e)){let t={};for(let n=0;n<e.length;n++){let r=e[n],i=Ry(r)?db(r):sb(r);if(i)for(let e in i)t[e]=i[e]}return t}else if(Ry(e)||By(e))return e}var cb=/;(?![^(]*\))/g,lb=/:([^]+)/,ub=/\/\*[^]*?\*\//g;function db(e){let t={};return e.replace(ub,``).split(cb).forEach(e=>{if(e){let n=e.split(lb);n.length>1&&(t[n[0].trim()]=n[1].trim())}}),t}function fb(e){let t=``;if(Ry(e))t=e;else if(q(e))for(let n=0;n<e.length;n++){let r=fb(e[n]);r&&(t+=r+` `)}else if(By(e))for(let n in e)e[n]&&(t+=n+` `);return t.trim()}function pb(e){if(!e)return null;let{class:t,style:n}=e;return t&&!Ry(t)&&(e.class=fb(t)),n&&(e.style=sb(n)),e}function mb(e,t){if(e.length!==t.length)return!1;let n=!0;for(let r=0;n&&r<e.length;r++)n=hb(e[r],t[r]);return n}function hb(e,t){if(e===t)return!0;let n=Iy(e),r=Iy(t);if(n||r)return n&&r?e.getTime()===t.getTime():!1;if(n=zy(e),r=zy(t),n||r)return e===t;if(n=q(e),r=q(t),n||r)return n&&r?mb(e,t):!1;if(n=By(e),r=By(t),n||r){if(!n||!r||Object.keys(e).length!==Object.keys(t).length)return!1;for(let n in e){let r=e.hasOwnProperty(n),i=t.hasOwnProperty(n);if(r&&!i||!r&&i||!hb(e[n],t[n]))return!1}}return String(e)===String(t)}var gb=e=>!!(e&&e.__v_isRef===!0),_b=e=>Ry(e)?e:e==null?``:q(e)||By(e)&&(e.toString===Hy||!J(e.toString))?gb(e)?_b(e.value):JSON.stringify(e,vb,2):String(e),vb=(e,t)=>gb(t)?vb(e,t.value):Py(t)?{[`Map(${t.size})`]:[...t.entries()].reduce((e,[t,n],r)=>(e[yb(t,r)+` =>`]=n,e),{})}:Fy(t)?{[`Set(${t.size})`]:[...t.values()].map(e=>yb(e))}:zy(t)?yb(t):By(t)&&!q(t)&&!Wy(t)?String(t):t,yb=(e,t=``)=>zy(e)?`Symbol(${e.description??t})`:e,bb=[];function xb(e){bb.push(e)}function Sb(){bb.pop()}function Cb(e,t){}var wb={SETUP_FUNCTION:0,0:`SETUP_FUNCTION`,RENDER_FUNCTION:1,1:`RENDER_FUNCTION`,NATIVE_EVENT_HANDLER:5,5:`NATIVE_EVENT_HANDLER`,COMPONENT_EVENT_HANDLER:6,6:`COMPONENT_EVENT_HANDLER`,VNODE_HOOK:7,7:`VNODE_HOOK`,DIRECTIVE_HOOK:8,8:`DIRECTIVE_HOOK`,TRANSITION_HOOK:9,9:`TRANSITION_HOOK`,APP_ERROR_HANDLER:10,10:`APP_ERROR_HANDLER`,APP_WARN_HANDLER:11,11:`APP_WARN_HANDLER`,FUNCTION_REF:12,12:`FUNCTION_REF`,ASYNC_COMPONENT_LOADER:13,13:`ASYNC_COMPONENT_LOADER`,SCHEDULER:14,14:`SCHEDULER`,COMPONENT_UPDATE:15,15:`COMPONENT_UPDATE`,APP_UNMOUNT_CLEANUP:16,16:`APP_UNMOUNT_CLEANUP`},Tb={sp:`serverPrefetch hook`,bc:`beforeCreate hook`,c:`created hook`,bm:`beforeMount hook`,m:`mounted hook`,bu:`beforeUpdate hook`,u:`updated`,bum:`beforeUnmount hook`,um:`unmounted hook`,a:`activated hook`,da:`deactivated hook`,ec:`errorCaptured hook`,rtc:`renderTracked hook`,rtg:`renderTriggered hook`,0:`setup function`,1:`render function`,2:`watcher getter`,3:`watcher callback`,4:`watcher cleanup function`,5:`native event handler`,6:`component event handler`,7:`vnode hook`,8:`directive hook`,9:`transition hook`,10:`app errorHandler`,11:`app warnHandler`,12:`ref function`,13:`async component loader`,14:`scheduler flush`,15:`component update`,16:`app unmount cleanup function`};function Eb(e,t,n,r){try{return r?e(...r):e()}catch(e){Ob(e,t,n)}}function Db(e,t,n,r){if(J(e)){let i=Eb(e,t,n,r);return i&&Vy(i)&&i.catch(e=>{Ob(e,t,n)}),i}if(q(e)){let i=[];for(let a=0;a<e.length;a++)i.push(Db(e[a],t,n,r));return i}}function Ob(e,t,n,r=!0){let i=t?t.vnode:null,{errorHandler:a,throwUnhandledErrorInProduction:o}=t&&t.appContext.config||K;if(t){let r=t.parent,i=t.proxy,o=`https://vuejs.org/error-reference/#runtime-${n}`;for(;r;){let t=r.ec;if(t){for(let n=0;n<t.length;n++)if(t[n](e,i,o)===!1)return}r=r.parent}if(a){H_(),Eb(a,null,10,[e,i,o]),U_();return}}kb(e,n,i,r,o)}function kb(e,t,n,r=!0,i=!1){if(i)throw e;console.error(e)}var Ab=[],jb=-1,Mb=[],Nb=null,Pb=0,Fb=Promise.resolve(),Ib=null;function Lb(e){let t=Ib||Fb;return e?t.then(this?e.bind(this):e):t}function Rb(e){let t=jb+1,n=Ab.length;for(;t<n;){let r=t+n>>>1,i=Ab[r],a=Wb(i);a<e||a===e&&i.flags&2?t=r+1:n=r}return t}function zb(e){if(!(e.flags&1)){let t=Wb(e),n=Ab[Ab.length-1];!n||!(e.flags&2)&&t>=Wb(n)?Ab.push(e):Ab.splice(Rb(t),0,e),e.flags|=1,Bb()}}function Bb(){Ib||=Fb.then(Gb)}function Vb(e){q(e)?Mb.push(...e):Nb&&e.id===-1?Nb.splice(Pb+1,0,e):e.flags&1||(Mb.push(e),e.flags|=1),Bb()}function Hb(e,t,n=jb+1){for(;n<Ab.length;n++){let t=Ab[n];if(t&&t.flags&2){if(e&&t.id!==e.uid)continue;Ab.splice(n,1),n--,t.flags&4&&(t.flags&=-2),t(),t.flags&4||(t.flags&=-2)}}}function Ub(e){if(Mb.length){let e=[...new Set(Mb)].sort((e,t)=>Wb(e)-Wb(t));if(Mb.length=0,Nb){Nb.push(...e);return}for(Nb=e,Pb=0;Pb<Nb.length;Pb++){let e=Nb[Pb];e.flags&4&&(e.flags&=-2),e.flags&8||e(),e.flags&=-2}Nb=null,Pb=0}}var Wb=e=>e.id==null?e.flags&2?-1:1/0:e.id;function Gb(e){try{for(jb=0;jb<Ab.length;jb++){let e=Ab[jb];e&&!(e.flags&8)&&(e.flags&4&&(e.flags&=-2),Eb(e,e.i,e.i?15:14),e.flags&4||(e.flags&=-2))}}finally{for(;jb<Ab.length;jb++){let e=Ab[jb];e&&(e.flags&=-2)}jb=-1,Ab.length=0,Ub(e),Ib=null,(Ab.length||Mb.length)&&Gb(e)}}var Kb,qb=[];function Jb(e,t){Kb=e,Kb?(Kb.enabled=!0,qb.forEach(({event:e,args:t})=>Kb.emit(e,...t)),qb=[]):typeof window<`u`&&window.HTMLElement&&!(window.navigator?.userAgent)?.includes(`jsdom`)?((t.__VUE_DEVTOOLS_HOOK_REPLAY__=t.__VUE_DEVTOOLS_HOOK_REPLAY__||[]).push(e=>{Jb(e,t)}),setTimeout(()=>{Kb||(t.__VUE_DEVTOOLS_HOOK_REPLAY__=null,qb=[])},3e3)):qb=[]}var Yb=null,Xb=null;function Zb(e){let t=Yb;return Yb=e,Xb=e&&e.type.__scopeId||null,t}function Qb(e){Xb=e}function $b(){Xb=null}var ex=e=>tx;function tx(e,t=Yb,n){if(!t||e._n)return e;let r=(...n)=>{r._d&&Zw(-1);let i=Zb(t),a;try{a=e(...n)}finally{Zb(i),r._d&&Zw(1)}return a};return r._n=!0,r._c=!0,r._d=!0,r}function nx(e,t){if(Yb===null)return e;let n=BT(Yb),r=e.dirs||=[];for(let e=0;e<t.length;e++){let[i,a,o,s=K]=t[e];i&&(J(i)&&(i={mounted:i,updated:i}),i.deep&&Cy(a),r.push({dir:i,instance:n,value:a,oldValue:void 0,arg:o,modifiers:s}))}return e}function rx(e,t,n,r){let i=e.dirs,a=t&&t.dirs;for(let o=0;o<i.length;o++){let s=i[o];a&&(s.oldValue=a[o].value);let c=s.dir[r];c&&(H_(),Db(c,n,8,[e.el,s,e,t]),U_())}}function ix(e,t){if(ST){let n=ST.provides,r=ST.parent&&ST.parent.provides;r===n&&(n=ST.provides=Object.create(r)),n[e]=t}}function ax(e,t,n=!1){let r=CT();if(r||RC){let i=RC?RC._context.provides:r?r.parent==null||r.ce?r.vnode.appContext&&r.vnode.appContext.provides:r.parent.provides:void 0;if(i&&e in i)return i[e];if(arguments.length>1)return n&&J(t)?t.call(r&&r.proxy):t}}function ox(){return!!(CT()||RC)}var sx=Symbol.for(`v-scx`),cx=()=>ax(sx);function lx(e,t){return px(e,null,t)}function ux(e,t){return px(e,null,{flush:`post`})}function dx(e,t){return px(e,null,{flush:`sync`})}function fx(e,t,n){return px(e,t,n)}function px(e,t,n=K){let{immediate:r,deep:i,flush:a,once:o}=n,s=Ay({},n),c=t&&r||!t&&a!==`post`,l;if(kT){if(a===`sync`){let e=cx();l=e.__watcherHandles||=[]}else if(!c){let e=()=>{};return e.stop=Ey,e.resume=Ey,e.pause=Ey,e}}let u=ST;s.call=(e,t,n)=>Db(e,u,t,n);let d=!1;a===`post`?s.scheduler=e=>{_w(e,u&&u.suspense)}:a!==`sync`&&(d=!0,s.scheduler=(e,t)=>{t?e():zb(e)}),s.augmentJob=e=>{t&&(e.flags|=4),d&&(e.flags|=2,u&&(e.id=u.uid,e.i=u))};let f=Sy(e,t,s);return kT&&(l?l.push(f):c&&f()),f}function mx(e,t,n){let r=this.proxy,i=Ry(e)?e.includes(`.`)?hx(r,e):()=>r[e]:e.bind(r,r),a;J(t)?a=t:(a=t.handler,n=t);let o=ET(this),s=px(i,a.bind(r),n);return o(),s}function hx(e,t){let n=t.split(`.`);return()=>{let t=e;for(let e=0;e<n.length&&t;e++)t=t[n[e]];return t}}var gx=new WeakMap,_x=Symbol(`_vte`),vx=e=>e.__isTeleport,yx=e=>e&&(e.disabled||e.disabled===``),bx=e=>e&&(e.defer||e.defer===``),xx=e=>typeof SVGElement<`u`&&e instanceof SVGElement,Sx=e=>typeof MathMLElement==`function`&&e instanceof MathMLElement,Cx=(e,t)=>{let n=e&&e.to;return Ry(n)?t?t(n):null:n},wx={name:`Teleport`,__isTeleport:!0,process(e,t,n,r,i,a,o,s,c,l){let{mc:u,pc:d,pbc:f,o:{insert:p,querySelector:m,createText:h,createComment:g,parentNode:_}}=l,v=yx(t.props),{dynamicChildren:y}=t,b=(e,t,n)=>{e.shapeFlag&16&&u(e.children,t,n,i,a,o,s,c)},x=(e=t)=>{let n=yx(e.props),r=e.target=Cx(e.props,m),a=kx(r,e,h,p);r&&(o!==`svg`&&xx(r)?o=`svg`:o!==`mathml`&&Sx(r)&&(o=`mathml`),i&&i.isCE&&(i.ce._teleportTargets||(i.ce._teleportTargets=new Set)).add(r),n||(b(e,r,a),Ox(e,!1)))},S=e=>{let t=()=>{gx.get(e)===t&&(gx.delete(e),yx(e.props)&&(b(e,_(e.el)||n,e.anchor),Ox(e,!0)),x(e))};gx.set(e,t),_w(t,a)};if(e==null){let e=t.el=h(``),i=t.anchor=h(``);if(p(e,n,r),p(i,n,r),bx(t.props)||a&&a.pendingBranch){S(t);return}v&&(b(t,n,i),Ox(t,!0)),x()}else{t.el=e.el;let r=t.anchor=e.anchor,u=gx.get(e);if(u){u.flags|=8,gx.delete(e),S(t);return}t.targetStart=e.targetStart;let p=t.target=e.target,h=t.targetAnchor=e.targetAnchor,g=yx(e.props),_=g?n:p,b=g?r:h;if(o===`svg`||xx(p)?o=`svg`:(o===`mathml`||Sx(p))&&(o=`mathml`),y?(f(e.dynamicChildren,y,_,i,a,o,s),ww(e,t,!0)):c||d(e,t,_,b,i,a,o,s,!1),v)g?t.props&&e.props&&t.props.to!==e.props.to&&(t.props.to=e.props.to):Tx(t,n,r,l,1);else if((t.props&&t.props.to)!==(e.props&&e.props.to)){let e=t.target=Cx(t.props,m);e&&Tx(t,e,null,l,0)}else g&&Tx(t,p,h,l,1);Ox(t,v)}},remove(e,t,n,{um:r,o:{remove:i}},a){let{shapeFlag:o,children:s,anchor:c,targetStart:l,targetAnchor:u,target:d,props:f}=e,p=a||!yx(f),m=gx.get(e);if(m&&(m.flags|=8,gx.delete(e),p=!1),d&&(i(l),i(u)),a&&i(c),o&16)for(let e=0;e<s.length;e++){let i=s[e];r(i,t,n,p,!!i.dynamicChildren)}},move:Tx,hydrate:Ex};function Tx(e,t,n,{o:{insert:r},m:i},a=2){a===0&&r(e.targetAnchor,t,n);let{el:o,anchor:s,shapeFlag:c,children:l,props:u}=e,d=a===2;if(d&&r(o,t,n),!gx.has(e)&&(!d||yx(u))&&c&16)for(let e=0;e<l.length;e++)i(l[e],t,n,2);d&&r(s,t,n)}function Ex(e,t,n,r,i,a,{o:{nextSibling:o,parentNode:s,querySelector:c,insert:l,createText:u}},d){function f(e,n){let r=n;for(;r;){if(r&&r.nodeType===8){if(r.data===`teleport start anchor`)t.targetStart=r;else if(r.data===`teleport anchor`){t.targetAnchor=r,e._lpa=t.targetAnchor&&o(t.targetAnchor);break}}r=o(r)}}function p(e,t){t.anchor=d(o(e),t,s(e),n,r,i,a)}let m=t.target=Cx(t.props,c),h=yx(t.props);if(m){let c=m._lpa||m.firstChild;t.shapeFlag&16&&(h?(p(e,t),f(m,c),t.targetAnchor||kx(m,t,u,l,s(e)===m?e:null)):(t.anchor=o(e),f(m,c),t.targetAnchor||kx(m,t,u,l),d(c&&o(c),t,m,n,r,i,a))),Ox(t,h)}else h&&t.shapeFlag&16&&(p(e,t),t.targetStart=e,t.targetAnchor=o(e));return t.anchor&&o(t.anchor)}var Dx=wx;function Ox(e,t){let n=e.ctx;if(n&&n.ut){let r,i;for(t?(r=e.el,i=e.anchor):(r=e.targetStart,i=e.targetAnchor);r&&r!==i;)r.nodeType===1&&r.setAttribute(`data-v-owner`,n.uid),r=r.nextSibling;n.ut()}}function kx(e,t,n,r,i=null){let a=t.targetStart=n(``),o=t.targetAnchor=n(``);return a[_x]=o,e&&(r(a,e,i),r(o,e,i)),o}var Ax=Symbol(`_leaveCb`),jx=Symbol(`_enterCb`);function Mx(){let e={isMounted:!1,isLeaving:!1,isUnmounting:!1,leavingVNodes:new Map};return MS(()=>{e.isMounted=!0}),FS(()=>{e.isUnmounting=!0}),e}var Nx=[Function,Array],Px={mode:String,appear:Boolean,persisted:Boolean,onBeforeEnter:Nx,onEnter:Nx,onAfterEnter:Nx,onEnterCancelled:Nx,onBeforeLeave:Nx,onLeave:Nx,onAfterLeave:Nx,onLeaveCancelled:Nx,onBeforeAppear:Nx,onAppear:Nx,onAfterAppear:Nx,onAppearCancelled:Nx},Fx=e=>{let t=e.subTree;return t.component?Fx(t.component):t},Ix={name:`BaseTransition`,props:Px,setup(e,{slots:t}){let n=CT(),r=Mx();return()=>{let i=t.default&&Wx(t.default(),!0),a=i&&i.length?Lx(i):n.subTree?pT():void 0;if(!a)return;let o=G(e),{mode:s}=o;if(r.isLeaving)return Vx(a);let c=Hx(a);if(!c)return Vx(a);let l=Bx(c,o,r,n,e=>l=e);c.type!==Ww&&Ux(c,l);let u=n.subTree&&Hx(n.subTree);if(u&&u.type!==Ww&&!nT(u,c)&&Fx(n).type!==Ww){let e=Bx(u,o,r,n);if(Ux(u,e),s===`out-in`&&c.type!==Ww)return r.isLeaving=!0,e.afterLeave=()=>{r.isLeaving=!1,n.job.flags&8||n.update(),delete e.afterLeave,u=void 0},Vx(a);s===`in-out`&&c.type!==Ww?e.delayLeave=(e,t,n)=>{let i=zx(r,u);i[String(u.key)]=u,e[Ax]=()=>{t(),e[Ax]=void 0,delete l.delayedLeave,u=void 0},l.delayedLeave=()=>{n(),delete l.delayedLeave,u=void 0}}:u=void 0}else u&&=void 0;return a}}};function Lx(e){let t=e[0];if(e.length>1){for(let n of e)if(n.type!==Ww){t=n;break}}return t}var Rx=Ix;function zx(e,t){let{leavingVNodes:n}=e,r=n.get(t.type);return r||(r=Object.create(null),n.set(t.type,r)),r}function Bx(e,t,n,r,i){let{appear:a,mode:o,persisted:s=!1,onBeforeEnter:c,onEnter:l,onAfterEnter:u,onEnterCancelled:d,onBeforeLeave:f,onLeave:p,onAfterLeave:m,onLeaveCancelled:h,onBeforeAppear:g,onAppear:_,onAfterAppear:v,onAppearCancelled:y}=t,b=String(e.key),x=zx(n,e),S=(e,t)=>{e&&Db(e,r,9,t)},C=(e,t)=>{let n=t[1];S(e,t),q(e)?e.every(e=>e.length<=1)&&n():e.length<=1&&n()},w={mode:o,persisted:s,beforeEnter(t){let r=c;if(!n.isMounted)if(a)r=g||c;else return;t[Ax]&&t[Ax](!0);let i=x[b];i&&nT(e,i)&&i.el[Ax]&&i.el[Ax](),S(r,[t])},enter(t){if(x[b]===e)return;let r=l,i=u,o=d;if(!n.isMounted)if(a)r=_||l,i=v||u,o=y||d;else return;let s=!1;t[jx]=e=>{s||(s=!0,S(e?o:i,[t]),w.delayedLeave&&w.delayedLeave(),t[jx]=void 0)};let c=t[jx].bind(null,!1);r?C(r,[t,c]):c()},leave(t,r){let i=String(e.key);if(t[jx]&&t[jx](!0),n.isUnmounting)return r();S(f,[t]);let a=!1;t[Ax]=n=>{a||(a=!0,r(),S(n?h:m,[t]),t[Ax]=void 0,x[i]===e&&delete x[i])};let o=t[Ax].bind(null,!1);x[i]=e,p?C(p,[t,o]):o()},clone(e){let a=Bx(e,t,n,r,i);return i&&i(a),a}};return w}function Vx(e){if(bS(e))return e=uT(e),e.children=null,e}function Hx(e){if(!bS(e))return vx(e.type)&&e.children?Lx(e.children):e;if(e.component)return e.component.subTree;let{shapeFlag:t,children:n}=e;if(n){if(t&16)return n[0];if(t&32&&J(n.default))return n.default()}}function Ux(e,t){e.shapeFlag&6&&e.component?(e.transition=t,Ux(e.component.subTree,t)):e.shapeFlag&128?(e.ssContent.transition=t.clone(e.ssContent),e.ssFallback.transition=t.clone(e.ssFallback)):e.transition=t}function Wx(e,t=!1,n){let r=[],i=0;for(let a=0;a<e.length;a++){let o=e[a],s=n==null?o.key:String(n)+String(o.key==null?a:o.key);o.type===Hw?(o.patchFlag&128&&i++,r=r.concat(Wx(o.children,t,s))):(t||o.type!==Ww)&&r.push(s==null?o:uT(o,{key:s}))}if(i>1)for(let e=0;e<r.length;e++)r[e].patchFlag=-2;return r}function Gx(e,t){return J(e)?Ay({name:e.name},t,{setup:e}):e}function Kx(){let e=CT();return e?(e.appContext.config.idPrefix||`v`)+`-`+e.ids[0]+ e.ids[1]++:``}function qx(e){e.ids=[e.ids[0]+ e.ids[2]+++`-`,0,0]}function Jx(e){let t=CT(),n=Qv(null);if(t){let r=t.refs===K?t.refs={}:t.refs;Object.defineProperty(r,e,{enumerable:!0,get:()=>n.value,set:e=>n.value=e})}return n}function Yx(e,t){let n;return!!((n=Object.getOwnPropertyDescriptor(e,t))&&!n.configurable)}var Xx=new WeakMap;function Zx(e,t,n,r,i=!1){if(q(e)){e.forEach((e,a)=>Zx(e,t&&(q(t)?t[a]:t),n,r,i));return}if(_S(r)&&!i){r.shapeFlag&512&&r.type.__asyncResolved&&r.component.subTree.component&&Zx(e,t,n,r.component.subTree);return}let a=r.shapeFlag&4?BT(r.component):r.el,o=i?null:a,{i:s,r:c}=e,l=t&&t.r,u=s.refs===K?s.refs={}:s.refs,d=s.setupState,f=G(d),p=d===K?Dy:e=>Yx(u,e)?!1:Ny(f,e),m=(e,t)=>!(t&&Yx(u,t));if(l!=null&&l!==c){if(Qx(t),Ry(l))u[l]=null,p(l)&&(d[l]=null);else if(Xv(l)){let e=t;m(l,e.k)&&(l.value=null),e.k&&(u[e.k]=null)}}if(J(c))Eb(c,s,12,[o,u]);else{let t=Ry(c),r=Xv(c);if(t||r){let s=()=>{if(e.f){let n=t?p(c)?d[c]:u[c]:m(c)||!e.k?c.value:u[e.k];if(i)q(n)&&jy(n,a);else if(q(n))n.includes(a)||n.push(a);else if(t)u[c]=[a],p(c)&&(d[c]=u[c]);else{let t=[a];m(c,e.k)&&(c.value=t),e.k&&(u[e.k]=t)}}else t?(u[c]=o,p(c)&&(d[c]=o)):r&&(m(c,e.k)&&(c.value=o),e.k&&(u[e.k]=o))};if(o){let t=()=>{s(),Xx.delete(e)};t.id=-1,Xx.set(e,t),_w(t,n)}else Qx(e),s()}}}function Qx(e){let t=Xx.get(e);t&&(t.flags|=8,Xx.delete(e))}var $x=!1,eS=()=>{$x||=(console.error(`Hydration completed but contains mismatches.`),!0)},tS=e=>e.namespaceURI.includes(`svg`)&&e.tagName!==`foreignObject`,nS=e=>e.namespaceURI.includes(`MathML`),rS=e=>{if(e.nodeType===1){if(tS(e))return`svg`;if(nS(e))return`mathml`}},iS=e=>e.nodeType===8;function aS(e){let{mt:t,p:n,o:{patchProp:r,createText:i,nextSibling:a,parentNode:o,remove:s,insert:c,createComment:l}}=e,u=(e,t)=>{if(!t.hasChildNodes()){n(null,e,t),Ub(),t._vnode=e;return}d(t.firstChild,e,null,null,null),Ub(),t._vnode=e},d=(n,r,s,l,u,y=!1)=>{y||=!!r.dynamicChildren;let b=iS(n)&&n.data===`[`,x=()=>h(n,r,s,l,u,b),{type:S,ref:C,shapeFlag:w,patchFlag:T}=r,E=n.nodeType;r.el=n,T===-2&&(y=!1,r.dynamicChildren=null);let D=null;switch(S){case Uw:E===3?(n.data!==r.children&&(eS(),n.data=r.children),D=a(n)):r.children===``?(c(r.el=i(``),o(n),n),D=n):D=x();break;case Ww:v(n)?(D=a(n),_(r.el=n.content.firstChild,n,s)):D=E!==8||b?x():a(n);break;case Gw:if(b&&(n=a(n),E=n.nodeType),E===1||E===3){D=n;let e=!r.children.length;for(let t=0;t<r.staticCount;t++)e&&(r.children+=D.nodeType===1?D.outerHTML:D.data),t===r.staticCount-1&&(r.anchor=D),D=a(D);return b?a(D):D}else x();break;case Hw:D=b?m(n,r,s,l,u,y):x();break;default:if(w&1)D=(E!==1||r.type.toLowerCase()!==n.tagName.toLowerCase())&&!v(n)?x():f(n,r,s,l,u,y);else if(w&6){r.slotScopeIds=u;let e=o(n);if(D=b?g(n):iS(n)&&n.data===`teleport start`?g(n,n.data,`teleport end`):a(n),t(r,e,null,s,l,rS(e),y),_S(r)&&!r.type.__asyncResolved){let t;b?(t=sT(Hw),t.anchor=D?D.previousSibling:e.lastChild):t=n.nodeType===3?dT(``):sT(`div`),t.el=n,r.component.subTree=t}}else w&64?D=E===8?r.type.hydrate(n,r,s,l,u,y,e,p):x():w&128&&(D=r.type.hydrate(n,r,s,l,rS(o(n)),u,y,e,d))}return C!=null&&Zx(C,null,l,r),D},f=(e,t,n,i,a,o)=>{o||=!!t.dynamicChildren;let{type:c,props:l,patchFlag:u,shapeFlag:d,dirs:f,transition:m}=t,h=c===`input`||c===`option`;if(h||u!==-1){f&&rx(t,null,n,`created`);let c=!1;if(v(e)){c=Cw(null,m)&&n&&n.vnode.props&&n.vnode.props.appear;let r=e.content.firstChild;if(c){let e=r.getAttribute(`class`);e&&(r.$cls=e),m.beforeEnter(r)}_(r,e,n),t.el=e=r}if(d&16&&!(l&&(l.innerHTML||l.textContent))){let r=p(e.firstChild,t,e,n,i,a,o);for(;r;){cS(e,1)||eS();let t=r;r=r.nextSibling,s(t)}}else if(d&8){let n=t.children;n[0]===`
`&&(e.tagName===`PRE`||e.tagName===`TEXTAREA`)&&(n=n.slice(1));let{textContent:r}=e;r!==n&&r!==n.replace(/\r\n|\r/g,`
`)&&(cS(e,0)||eS(),e.textContent=t.children)}if(l){if(h||!o||u&48){let t=e.tagName.includes(`-`);for(let i in l)(h&&(i.endsWith(`value`)||i===`indeterminate`)||Oy(i)&&!Gy(i)||i[0]===`.`||t&&!Gy(i))&&r(e,i,null,l[i],void 0,n)}else if(l.onClick)r(e,`onClick`,null,l.onClick,void 0,n);else if(u&4&&Uv(l.style))for(let e in l.style)l.style[e]}let g;(g=l&&l.onVnodeBeforeMount)&&vT(g,n,t),f&&rx(t,null,n,`beforeMount`),((g=l&&l.onVnodeMounted)||f||c)&&zw(()=>{g&&vT(g,n,t),c&&m.enter(e),f&&rx(t,null,n,`mounted`)},i)}return e.nextSibling},p=(e,t,r,o,s,l,u)=>{u||=!!t.dynamicChildren;let f=t.children,p=f.length;for(let t=0;t<p;t++){let m=u?f[t]:f[t]=mT(f[t]),h=m.type===Uw;e?(h&&!u&&t+1<p&&mT(f[t+1]).type===Uw&&(c(i(e.data.slice(m.children.length)),r,a(e)),e.data=m.children),e=d(e,m,o,s,l,u)):h&&!m.children?c(m.el=i(``),r):(cS(r,1)||eS(),n(null,m,r,null,o,s,rS(r),l))}return e},m=(e,t,n,r,i,s)=>{let{slotScopeIds:u}=t;u&&(i=i?i.concat(u):u);let d=o(e),f=p(a(e),t,d,n,r,i,s);return f&&iS(f)&&f.data===`]`?a(t.anchor=f):(eS(),c(t.anchor=l(`]`),d,f),f)},h=(e,t,r,i,c,l)=>{if(cS(e.parentElement,1)||eS(),t.el=null,l){let t=g(e);for(;;){let n=a(e);if(n&&n!==t)s(n);else break}}let u=a(e),d=o(e);return s(e),n(null,t,d,u,r,i,rS(d),c),r&&(r.vnode.el=t.el,QC(r,t.el)),u},g=(e,t=`[`,n=`]`)=>{let r=0;for(;e;)if(e=a(e),e&&iS(e)&&(e.data===t&&r++,e.data===n)){if(r===0)return a(e);r--}return e},_=(e,t,n)=>{let r=t.parentNode;r&&r.replaceChild(e,t);let i=n;for(;i;)i.vnode.el===t&&(i.vnode.el=i.subTree.el=e),i=i.parent},v=e=>e.nodeType===1&&e.tagName===`TEMPLATE`;return[u,d]}var oS=`data-allow-mismatch`,sS={0:`text`,1:`children`,2:`class`,3:`style`,4:`attribute`};function cS(e,t){if(t===0||t===1)for(;e&&!e.hasAttribute(oS);)e=e.parentElement;let n=e&&e.getAttribute(oS);if(n==null)return!1;if(n===``)return!0;{let e=n.split(`,`);return t===0&&e.includes(`children`)?!0:e.includes(sS[t])}}var lS=ab().requestIdleCallback||(e=>setTimeout(e,1)),uS=ab().cancelIdleCallback||(e=>clearTimeout(e)),dS=(e=1e4)=>t=>{let n=lS(t,{timeout:e});return()=>uS(n)};function fS(e){let{top:t,left:n,bottom:r,right:i}=e.getBoundingClientRect(),{innerHeight:a,innerWidth:o}=window;return(t>0&&t<a||r>0&&r<a)&&(n>0&&n<o||i>0&&i<o)}var pS=e=>(t,n)=>{let r=new IntersectionObserver(e=>{for(let n of e)if(n.isIntersecting){r.disconnect(),t();break}},e);return n(e=>{if(e instanceof Element){if(fS(e))return t(),r.disconnect(),!1;r.observe(e)}}),()=>r.disconnect()},mS=e=>t=>{if(e){let n=matchMedia(e);if(n.matches)t();else return n.addEventListener(`change`,t,{once:!0}),()=>n.removeEventListener(`change`,t)}},hS=(e=[])=>(t,n)=>{Ry(e)&&(e=[e]);let r=!1,i=e=>{r||(r=!0,a(),t(),e.target.dispatchEvent(new e.constructor(e.type,e)))},a=()=>{n(t=>{for(let n of e)t.removeEventListener(n,i)})};return n(t=>{for(let n of e)t.addEventListener(n,i,{once:!0})}),a};function gS(e,t){if(iS(e)&&e.data===`[`){let n=1,r=e.nextSibling;for(;r;){if(r.nodeType===1){if(t(r)===!1)break}else if(iS(r))if(r.data===`]`){if(--n===0)break}else r.data===`[`&&n++;r=r.nextSibling}}else t(e)}var _S=e=>!!e.type.__asyncLoader;function vS(e){J(e)&&(e={loader:e});let{loader:t,loadingComponent:n,errorComponent:r,delay:i=200,hydrate:a,timeout:o,suspensible:s=!0,onError:c}=e,l=null,u,d=0,f=()=>(d++,l=null,p()),p=()=>{let e;return l||(e=l=t().catch(e=>{if(e=e instanceof Error?e:Error(String(e)),c)return new Promise((t,n)=>{c(e,()=>t(f()),()=>n(e),d+1)});throw e}).then(t=>e!==l&&l?l:(t&&(t.__esModule||t[Symbol.toStringTag]===`Module`)&&(t=t.default),u=t,t)))};return Gx({name:`AsyncComponentWrapper`,__asyncLoader:p,__asyncHydrate(e,t,n){let r=!1;(t.bu||=[]).push(()=>r=!0);let i=()=>{r||n()},o=a?()=>{let n=a(i,t=>gS(e,t));n&&(t.bum||=[]).push(n)}:i;u?o():p().then(()=>!t.isUnmounted&&o())},get __asyncResolved(){return u},setup(){let e=ST;if(qx(e),u)return()=>yS(u,e);let t=t=>{l=null,Ob(t,e,13,!r)};if(s&&e.suspense||kT)return p().then(t=>()=>yS(t,e)).catch(e=>(t(e),()=>r?sT(r,{error:e}):null));let a=Zv(!1),c=Zv(),d=Zv(!!i);return i&&setTimeout(()=>{d.value=!1},i),o!=null&&setTimeout(()=>{if(!a.value&&!c.value){let e=Error(`Async component timed out after ${o}ms.`);t(e),c.value=e}},o),p().then(()=>{a.value=!0,e.parent&&bS(e.parent.vnode)&&e.parent.update()}).catch(e=>{t(e),c.value=e}),()=>{if(a.value&&u)return yS(u,e);if(c.value&&r)return sT(r,{error:c.value});if(n&&!d.value)return yS(n,e)}}})}function yS(e,t){let{ref:n,props:r,children:i,ce:a}=t.vnode,o=sT(e,r,i);return o.ref=n,o.ce=a,delete t.vnode.ce,o}var bS=e=>e.type.__isKeepAlive,xS={name:`KeepAlive`,__isKeepAlive:!0,props:{include:[String,RegExp,Array],exclude:[String,RegExp,Array],max:[String,Number]},setup(e,{slots:t}){let n=CT(),r=n.ctx;if(!r.renderer)return()=>{let e=t.default&&t.default();return e&&e.length===1?e[0]:e};let i=new Map,a=new Set,o=null,s=n.suspense,{renderer:{p:c,m:l,um:u,o:{createElement:d}}}=r,f=d(`div`);r.activate=(e,t,n,r,i)=>{let a=e.component;l(e,t,n,0,s),c(a.vnode,e,t,n,a,s,r,e.slotScopeIds,i),_w(()=>{a.isDeactivated=!1,a.a&&eb(a.a);let t=e.props&&e.props.onVnodeMounted;t&&vT(t,a.parent,e)},s)},r.deactivate=e=>{let t=e.component;Dw(t.m),Dw(t.a),l(e,f,null,1,s),_w(()=>{t.da&&eb(t.da);let n=e.props&&e.props.onVnodeUnmounted;n&&vT(n,t.parent,e),t.isDeactivated=!0},s)};function p(e){DS(e),u(e,n,s,!0)}function m(e){i.forEach((t,n)=>{let r=VT(_S(t)?t.type.__asyncResolved||{}:t.type);r&&!e(r)&&h(n)})}function h(e){let t=i.get(e);t&&(!o||!nT(t,o))?p(t):o&&DS(o),i.delete(e),a.delete(e)}fx(()=>[e.include,e.exclude],([e,t])=>{e&&m(t=>SS(e,t)),t&&m(e=>!SS(t,e))},{flush:`post`,deep:!0});let g=null,_=()=>{g!=null&&(kw(n.subTree.type)?_w(()=>{i.set(g,OS(n.subTree))},n.subTree.suspense):i.set(g,OS(n.subTree)))};return MS(_),PS(_),FS(()=>{i.forEach(e=>{let{subTree:t,suspense:r}=n,i=OS(t);if(e.type===i.type&&e.key===i.key){DS(i);let e=i.component.da;e&&_w(e,r);return}p(e)})}),()=>{if(g=null,!t.default)return o=null;let n=t.default(),r=n[0];if(n.length>1)return o=null,n;if(!tT(r)||!(r.shapeFlag&4)&&!(r.shapeFlag&128))return o=null,r;let s=OS(r);if(s.type===Ww)return o=null,s;let c=s.type,l=VT(_S(s)?s.type.__asyncResolved||{}:c),{include:u,exclude:d,max:f}=e;if(u&&(!l||!SS(u,l))||d&&l&&SS(d,l))return s.shapeFlag&=-257,o=s,r;let p=s.key==null?c:s.key,m=i.get(p);return s.el&&(s=uT(s),r.shapeFlag&128&&(r.ssContent=s)),g=p,m?(s.el=m.el,s.component=m.component,s.transition&&Ux(s,s.transition),s.shapeFlag|=512,a.delete(p),a.add(p)):(a.add(p),f&&a.size>parseInt(f,10)&&h(a.values().next().value)),s.shapeFlag|=256,o=s,kw(r.type)?r:s}}};function SS(e,t){return q(e)?e.some(e=>SS(e,t)):Ry(e)?e.split(`,`).includes(t):Ly(e)?(e.lastIndex=0,e.test(t)):!1}function CS(e,t){TS(e,`a`,t)}function wS(e,t){TS(e,`da`,t)}function TS(e,t,n=ST){let r=e.__wdc||=()=>{let t=n;for(;t;){if(t.isDeactivated)return;t=t.parent}return e()};if(kS(t,r,n),n){let e=n.parent;for(;e&&e.parent;)bS(e.parent.vnode)&&ES(r,t,n,e),e=e.parent}}function ES(e,t,n,r){let i=kS(t,e,r,!0);IS(()=>{jy(r[t],i)},n)}function DS(e){e.shapeFlag&=-257,e.shapeFlag&=-513}function OS(e){return e.shapeFlag&128?e.ssContent:e}function kS(e,t,n=ST,r=!1){if(n){let i=n[e]||(n[e]=[]),a=t.__weh||=(...r)=>{H_();let i=ET(n),a=Db(t,n,e,r);return i(),U_(),a};return r?i.unshift(a):i.push(a),a}}var AS=e=>(t,n=ST)=>{(!kT||e===`sp`)&&kS(e,(...e)=>t(...e),n)},jS=AS(`bm`),MS=AS(`m`),NS=AS(`bu`),PS=AS(`u`),FS=AS(`bum`),IS=AS(`um`),LS=AS(`sp`),RS=AS(`rtg`),zS=AS(`rtc`);function BS(e,t=ST){kS(`ec`,e,t)}var VS=`components`,HS=`directives`;function US(e,t){return qS(VS,e,!0,t)||e}var WS=Symbol.for(`v-ndc`);function GS(e){return Ry(e)?qS(VS,e,!1)||e:e||WS}function KS(e){return qS(HS,e)}function qS(e,t,n=!0,r=!1){let i=Yb||ST;if(i){let n=i.type;if(e===VS){let e=VT(n,!1);if(e&&(e===t||e===Jy(t)||e===Zy(Jy(t))))return n}let a=JS(i[e]||n[e],t)||JS(i.appContext[e],t);return!a&&r?n:a}}function JS(e,t){return e&&(e[t]||e[Jy(t)]||e[Zy(Jy(t))])}function YS(e,t,n,r){let i,a=n&&n[r],o=q(e);if(o||Ry(e)){let n=o&&Uv(e),r=!1,s=!1;n&&(r=!Gv(e),s=Wv(e),e=rv(e)),i=Array(e.length);for(let n=0,o=e.length;n<o;n++)i[n]=t(r?s?Yv(Jv(e[n])):Jv(e[n]):e[n],n,void 0,a&&a[n])}else if(typeof e==`number`){i=Array(e);for(let n=0;n<e;n++)i[n]=t(n+1,n,void 0,a&&a[n])}else if(By(e))if(e[Symbol.iterator])i=Array.from(e,(e,n)=>t(e,n,void 0,a&&a[n]));else{let n=Object.keys(e);i=Array(n.length);for(let r=0,o=n.length;r<o;r++){let o=n[r];i[r]=t(e[o],o,r,a&&a[r])}}else i=[];return n&&(n[r]=i),i}function XS(e,t){for(let n=0;n<t.length;n++){let r=t[n];if(q(r))for(let t=0;t<r.length;t++)e[r[t].name]=r[t].fn;else r&&(e[r.name]=r.key?(...e)=>{let t=r.fn(...e);return t&&(t.key=r.key),t}:r.fn)}return e}function ZS(e,t,n={},r,i){if(Yb.ce||Yb.parent&&_S(Yb.parent)&&Yb.parent.ce){let e=Object.keys(n).length>0;return t!==`default`&&(n.name=t),Jw(),eT(Hw,null,[sT(`slot`,n,r&&r())],e?-2:64)}let a=e[t];a&&a._c&&(a._d=!1),Jw();let o=a&&QS(a(n)),s=n.key||o&&o.key,c=eT(Hw,{key:(s&&!zy(s)?s:`_${t}`)+(!o&&r?`_fb`:``)},o||(r?r():[]),o&&e._===1?64:-2);return!i&&c.scopeId&&(c.slotScopeIds=[c.scopeId+`-s`]),a&&a._c&&(a._d=!0),c}function QS(e){return e.some(e=>tT(e)?!(e.type===Ww||e.type===Hw&&!QS(e.children)):!0)?e:null}function $S(e,t){let n={};for(let r in e)n[t&&/[A-Z]/.test(r)?`on:${r}`:Qy(r)]=e[r];return n}var eC=e=>e?OT(e)?BT(e):eC(e.parent):null,tC=Ay(Object.create(null),{$:e=>e,$el:e=>e.vnode.el,$data:e=>e.data,$props:e=>e.props,$attrs:e=>e.attrs,$slots:e=>e.slots,$refs:e=>e.refs,$parent:e=>eC(e.parent),$root:e=>eC(e.root),$host:e=>e.ce,$emit:e=>e.emit,$options:e=>TC(e),$forceUpdate:e=>e.f||=()=>{zb(e.update)},$nextTick:e=>e.n||=Lb.bind(e.proxy),$watch:e=>mx.bind(e)}),nC=(e,t)=>e!==K&&!e.__isScriptSetup&&Ny(e,t),rC={get({_:e},t){if(t===`__v_skip`)return!0;let{ctx:n,setupState:r,data:i,props:a,accessCache:o,type:s,appContext:c}=e;if(t[0]!==`$`){let e=o[t];if(e!==void 0)switch(e){case 1:return r[t];case 2:return i[t];case 4:return n[t];case 3:return a[t]}else if(nC(r,t))return o[t]=1,r[t];else if(i!==K&&Ny(i,t))return o[t]=2,i[t];else if(Ny(a,t))return o[t]=3,a[t];else if(n!==K&&Ny(n,t))return o[t]=4,n[t];else bC&&(o[t]=0)}let l=tC[t],u,d;if(l)return t===`$attrs`&&$_(e.attrs,`get`,``),l(e);if((u=s.__cssModules)&&(u=u[t]))return u;if(n!==K&&Ny(n,t))return o[t]=4,n[t];if(d=c.config.globalProperties,Ny(d,t))return d[t]},set({_:e},t,n){let{data:r,setupState:i,ctx:a}=e;return nC(i,t)?(i[t]=n,!0):r!==K&&Ny(r,t)?(r[t]=n,!0):Ny(e.props,t)||t[0]===`$`&&t.slice(1)in e?!1:(a[t]=n,!0)},has({_:{data:e,setupState:t,accessCache:n,ctx:r,appContext:i,props:a,type:o}},s){let c;return!!(n[s]||e!==K&&s[0]!==`$`&&Ny(e,s)||nC(t,s)||Ny(a,s)||Ny(r,s)||Ny(tC,s)||Ny(i.config.globalProperties,s)||(c=o.__cssModules)&&c[s])},defineProperty(e,t,n){return n.get==null?Ny(n,`value`)&&this.set(e,t,n.value,null):e._.accessCache[t]=0,Reflect.defineProperty(e,t,n)}},iC=Ay({},rC,{get(e,t){if(t!==Symbol.unscopables)return rC.get(e,t,e)},has(e,t){return t[0]!==`_`&&!ob(t)}});function aC(){return null}function oC(){return null}function sC(e){}function cC(e){}function lC(){return null}function uC(){}function dC(e,t){return null}function fC(){return mC(`useSlots`).slots}function pC(){return mC(`useAttrs`).attrs}function mC(e){let t=CT();return t.setupContext||=zT(t)}function hC(e){return q(e)?e.reduce((e,t)=>(e[t]=null,e),{}):e}function gC(e,t){let n=hC(e);for(let e in t){if(e.startsWith(`__skip`))continue;let r=n[e];r?q(r)||J(r)?r=n[e]={type:r,default:t[e]}:r.default=t[e]:r===null&&(r=n[e]={default:t[e]}),r&&t[`__skip_${e}`]&&(r.skipFactory=!0)}return n}function _C(e,t){return!e||!t?e||t:q(e)&&q(t)?e.concat(t):Ay({},hC(e),hC(t))}function vC(e,t){let n={};for(let r in e)t.includes(r)||Object.defineProperty(n,r,{enumerable:!0,get:()=>e[r]});return n}function yC(e){let t=CT(),n=kT,r=e();DT(),n&&TT(!1);let i=()=>{ET(t),n&&TT(!0)},a=()=>{CT()!==t&&t.scope.off(),DT(),n&&TT(!1)};return Vy(r)&&(r=r.catch(e=>{throw i(),Promise.resolve().then(()=>Promise.resolve().then(a)),e})),[r,()=>{i(),Promise.resolve().then(a)}]}var bC=!0;function xC(e){let t=TC(e),n=e.proxy,r=e.ctx;bC=!1,t.beforeCreate&&CC(t.beforeCreate,e,`bc`);let{data:i,computed:a,methods:o,watch:s,provide:c,inject:l,created:u,beforeMount:d,mounted:f,beforeUpdate:p,updated:m,activated:h,deactivated:g,beforeDestroy:_,beforeUnmount:v,destroyed:y,unmounted:b,render:x,renderTracked:S,renderTriggered:C,errorCaptured:w,serverPrefetch:T,expose:E,inheritAttrs:D,components:O,directives:ee,filters:te}=t;if(l&&SC(l,r,null),o)for(let e in o){let t=o[e];J(t)&&(r[e]=t.bind(n))}if(i){let t=i.call(n,n);By(t)&&(e.data=Rv(t))}if(bC=!0,a)for(let e in a){let t=a[e],i=Y({get:J(t)?t.bind(n,n):J(t.get)?t.get.bind(n,n):Ey,set:!J(t)&&J(t.set)?t.set.bind(n):Ey});Object.defineProperty(r,e,{enumerable:!0,configurable:!0,get:()=>i.value,set:e=>i.value=e})}if(s)for(let e in s)wC(s[e],r,n,e);if(c){let e=J(c)?c.call(n):c;Reflect.ownKeys(e).forEach(t=>{ix(t,e[t])})}u&&CC(u,e,`c`);function ne(e,t){q(t)?t.forEach(t=>e(t.bind(n))):t&&e(t.bind(n))}if(ne(jS,d),ne(MS,f),ne(NS,p),ne(PS,m),ne(CS,h),ne(wS,g),ne(BS,w),ne(zS,S),ne(RS,C),ne(FS,v),ne(IS,b),ne(LS,T),q(E))if(E.length){let t=e.exposed||={};E.forEach(e=>{Object.defineProperty(t,e,{get:()=>n[e],set:t=>n[e]=t,enumerable:!0})})}else e.exposed||={};x&&e.render===Ey&&(e.render=x),D!=null&&(e.inheritAttrs=D),O&&(e.components=O),ee&&(e.directives=ee),T&&qx(e)}function SC(e,t,n=Ey){q(e)&&(e=AC(e));for(let n in e){let r=e[n],i;i=By(r)?`default`in r?ax(r.from||n,r.default,!0):ax(r.from||n):ax(r),Xv(i)?Object.defineProperty(t,n,{enumerable:!0,configurable:!0,get:()=>i.value,set:e=>i.value=e}):t[n]=i}}function CC(e,t,n){Db(q(e)?e.map(e=>e.bind(t.proxy)):e.bind(t.proxy),t,n)}function wC(e,t,n,r){let i=r.includes(`.`)?hx(n,r):()=>n[r];if(Ry(e)){let n=t[e];J(n)&&fx(i,n)}else if(J(e))fx(i,e.bind(n));else if(By(e))if(q(e))e.forEach(e=>wC(e,t,n,r));else{let r=J(e.handler)?e.handler.bind(n):t[e.handler];J(r)&&fx(i,r,e)}}function TC(e){let t=e.type,{mixins:n,extends:r}=t,{mixins:i,optionsCache:a,config:{optionMergeStrategies:o}}=e.appContext,s=a.get(t),c;return s?c=s:!i.length&&!n&&!r?c=t:(c={},i.length&&i.forEach(e=>EC(c,e,o,!0)),EC(c,t,o)),By(t)&&a.set(t,c),c}function EC(e,t,n,r=!1){let{mixins:i,extends:a}=t;a&&EC(e,a,n,!0),i&&i.forEach(t=>EC(e,t,n,!0));for(let i in t)if(!(r&&i===`expose`)){let r=DC[i]||n&&n[i];e[i]=r?r(e[i],t[i]):t[i]}return e}var DC={data:OC,props:NC,emits:NC,methods:MC,computed:MC,beforeCreate:jC,created:jC,beforeMount:jC,mounted:jC,beforeUpdate:jC,updated:jC,beforeDestroy:jC,beforeUnmount:jC,destroyed:jC,unmounted:jC,activated:jC,deactivated:jC,errorCaptured:jC,serverPrefetch:jC,components:MC,directives:MC,watch:PC,provide:OC,inject:kC};function OC(e,t){return t?e?function(){return Ay(J(e)?e.call(this,this):e,J(t)?t.call(this,this):t)}:t:e}function kC(e,t){return MC(AC(e),AC(t))}function AC(e){if(q(e)){let t={};for(let n=0;n<e.length;n++)t[e[n]]=e[n];return t}return e}function jC(e,t){return e?[...new Set([].concat(e,t))]:t}function MC(e,t){return e?Ay(Object.create(null),e,t):t}function NC(e,t){return e?q(e)&&q(t)?[...new Set([...e,...t])]:Ay(Object.create(null),hC(e),hC(t??{})):t}function PC(e,t){if(!e)return t;if(!t)return e;let n=Ay(Object.create(null),e);for(let r in t)n[r]=jC(e[r],t[r]);return n}function FC(){return{app:null,config:{isNativeTag:Dy,performance:!1,globalProperties:{},optionMergeStrategies:{},errorHandler:void 0,warnHandler:void 0,compilerOptions:{}},mixins:[],components:{},directives:{},provides:Object.create(null),optionsCache:new WeakMap,propsCache:new WeakMap,emitsCache:new WeakMap}}var IC=0;function LC(e,t){return function(n,r=null){J(n)||(n=Ay({},n)),r!=null&&!By(r)&&(r=null);let i=FC(),a=new WeakSet,o=[],s=!1,c=i.app={_uid:IC++,_component:n,_props:r,_container:null,_context:i,_instance:null,version:qT,get config(){return i.config},set config(e){},use(e,...t){return a.has(e)||(e&&J(e.install)?(a.add(e),e.install(c,...t)):J(e)&&(a.add(e),e(c,...t))),c},mixin(e){return i.mixins.includes(e)||i.mixins.push(e),c},component(e,t){return t?(i.components[e]=t,c):i.components[e]},directive(e,t){return t?(i.directives[e]=t,c):i.directives[e]},mount(a,o,l){if(!s){let u=c._ceVNode||sT(n,r);return u.appContext=i,l===!0?l=`svg`:l===!1&&(l=void 0),o&&t?t(u,a):e(u,a,l),s=!0,c._container=a,a.__vue_app__=c,BT(u.component)}},onUnmount(e){o.push(e)},unmount(){s&&(Db(o,c._instance,16),e(null,c._container),delete c._container.__vue_app__)},provide(e,t){return i.provides[e]=t,c},runWithContext(e){let t=RC;RC=c;try{return e()}finally{RC=t}}};return c}}var RC=null;function zC(e,t,n=K){let r=CT(),i=Jy(t),a=Xy(t),o=BC(e,i),s=sy((o,s)=>{let c,l=K,u;return dx(()=>{let t=e[i];$y(c,t)&&(c=t,s())}),{get(){return o(),n.get?n.get(c):c},set(e){let o=n.set?n.set(e):e;if(!$y(o,c)&&!(l!==K&&$y(e,l)))return;let d=r.vnode.props;d&&(t in d||i in d||a in d)&&(`onUpdate:${t}`in d||`onUpdate:${i}`in d||`onUpdate:${a}`in d)||(c=e,s()),r.emit(`update:${t}`,o),$y(e,o)&&$y(e,l)&&!$y(o,u)&&s(),l=e,u=o}}});return s[Symbol.iterator]=()=>{let e=0;return{next(){return e<2?{value:e++?o||K:s,done:!1}:{done:!0}}}},s}var BC=(e,t)=>t===`modelValue`||t===`model-value`?e.modelModifiers:e[`${t}Modifiers`]||e[`${Jy(t)}Modifiers`]||e[`${Xy(t)}Modifiers`];function VC(e,t,...n){if(e.isUnmounted)return;let r=e.vnode.props||K,i=n,a=t.startsWith(`update:`),o=a&&BC(r,t.slice(7));o&&(o.trim&&(i=n.map(e=>Ry(e)?e.trim():e)),o.number&&(i=n.map(nb)));let s,c=r[s=Qy(t)]||r[s=Qy(Jy(t))];!c&&a&&(c=r[s=Qy(Xy(t))]),c&&Db(c,e,6,i);let l=r[s+`Once`];if(l){if(!e.emitted)e.emitted={};else if(e.emitted[s])return;e.emitted[s]=!0,Db(l,e,6,i)}}var HC=new WeakMap;function UC(e,t,n=!1){let r=n?HC:t.emitsCache,i=r.get(e);if(i!==void 0)return i;let a=e.emits,o={},s=!1;if(!J(e)){let r=e=>{let n=UC(e,t,!0);n&&(s=!0,Ay(o,n))};!n&&t.mixins.length&&t.mixins.forEach(r),e.extends&&r(e.extends),e.mixins&&e.mixins.forEach(r)}return!a&&!s?(By(e)&&r.set(e,null),null):(q(a)?a.forEach(e=>o[e]=null):Ay(o,a),By(e)&&r.set(e,o),o)}function WC(e,t){return!e||!Oy(t)?!1:(t=t.slice(2).replace(/Once$/,``),Ny(e,t[0].toLowerCase()+t.slice(1))||Ny(e,Xy(t))||Ny(e,t))}function GC(e){let{type:t,vnode:n,proxy:r,withProxy:i,propsOptions:[a],slots:o,attrs:s,emit:c,render:l,renderCache:u,props:d,data:f,setupState:p,ctx:m,inheritAttrs:h}=e,g=Zb(e),_,v;try{if(n.shapeFlag&4){let e=i||r,t=e;_=mT(l.call(t,e,u,d,p,f,m)),v=s}else{let e=t;_=mT(e.length>1?e(d,{attrs:s,slots:o,emit:c}):e(d,null)),v=t.props?s:qC(s)}}catch(t){Kw.length=0,Ob(t,e,1),_=sT(Ww)}let y=_;if(v&&h!==!1){let e=Object.keys(v),{shapeFlag:t}=y;e.length&&t&7&&(a&&e.some(ky)&&(v=JC(v,a)),y=uT(y,v,!1,!0))}return n.dirs&&(y=uT(y,null,!1,!0),y.dirs=y.dirs?y.dirs.concat(n.dirs):n.dirs),n.transition&&Ux(y,n.transition),_=y,Zb(g),_}function KC(e,t=!0){let n;for(let t=0;t<e.length;t++){let r=e[t];if(tT(r)){if(r.type!==Ww||r.children===`v-if`){if(n)return;n=r}}else return}return n}var qC=e=>{let t;for(let n in e)(n===`class`||n===`style`||Oy(n))&&((t||={})[n]=e[n]);return t},JC=(e,t)=>{let n={};for(let r in e)(!ky(r)||!(r.slice(9)in t))&&(n[r]=e[r]);return n};function YC(e,t,n){let{props:r,children:i,component:a}=e,{props:o,children:s,patchFlag:c}=t,l=a.emitsOptions;if(t.dirs||t.transition)return!0;if(n&&c>=0){if(c&1024)return!0;if(c&16)return r?XC(r,o,l):!!o;if(c&8){let e=t.dynamicProps;for(let t=0;t<e.length;t++){let n=e[t];if(ZC(o,r,n)&&!WC(l,n))return!0}}}else return(i||s)&&(!s||!s.$stable)?!0:r===o?!1:r?o?XC(r,o,l):!0:!!o;return!1}function XC(e,t,n){let r=Object.keys(t);if(r.length!==Object.keys(e).length)return!0;for(let i=0;i<r.length;i++){let a=r[i];if(ZC(t,e,a)&&!WC(n,a))return!0}return!1}function ZC(e,t,n){let r=e[n],i=t[n];return n===`style`&&By(r)&&By(i)?!hb(r,i):r!==i}function QC({vnode:e,parent:t,suspense:n},r){for(;t;){let n=t.subTree;if(n.suspense&&n.suspense.activeBranch===e&&(n.suspense.vnode.el=n.el=r,e=n),n===e)(e=t.vnode).el=r,t=t.parent;else break}n&&n.activeBranch===e&&(n.vnode.el=r)}var $C={},ew=()=>Object.create($C),tw=e=>Object.getPrototypeOf(e)===$C;function nw(e,t,n,r=!1){let i={},a=ew();e.propsDefaults=Object.create(null),iw(e,t,i,a);for(let t in e.propsOptions[0])t in i||(i[t]=void 0);n?e.props=r?i:zv(i):e.type.props?e.props=i:e.props=a,e.attrs=a}function rw(e,t,n,r){let{props:i,attrs:a,vnode:{patchFlag:o}}=e,s=G(i),[c]=e.propsOptions,l=!1;if((r||o>0)&&!(o&16)){if(o&8){let n=e.vnode.dynamicProps;for(let r=0;r<n.length;r++){let o=n[r];if(WC(e.emitsOptions,o))continue;let u=t[o];if(c)if(Ny(a,o))u!==a[o]&&(a[o]=u,l=!0);else{let t=Jy(o);i[t]=aw(c,s,t,u,e,!1)}else u!==a[o]&&(a[o]=u,l=!0)}}}else{iw(e,t,i,a)&&(l=!0);let r;for(let a in s)(!t||!Ny(t,a)&&((r=Xy(a))===a||!Ny(t,r)))&&(c?n&&(n[a]!==void 0||n[r]!==void 0)&&(i[a]=aw(c,s,a,void 0,e,!0)):delete i[a]);if(a!==s)for(let e in a)(!t||!Ny(t,e))&&(delete a[e],l=!0)}l&&ev(e.attrs,`set`,``)}function iw(e,t,n,r){let[i,a]=e.propsOptions,o=!1,s;if(t)for(let c in t){if(Gy(c))continue;let l=t[c],u;i&&Ny(i,u=Jy(c))?!a||!a.includes(u)?n[u]=l:(s||={})[u]=l:WC(e.emitsOptions,c)||(!(c in r)||l!==r[c])&&(r[c]=l,o=!0)}if(a){let t=G(n),r=s||K;for(let o=0;o<a.length;o++){let s=a[o];n[s]=aw(i,t,s,r[s],e,!Ny(r,s))}}return o}function aw(e,t,n,r,i,a){let o=e[n];if(o!=null){let e=Ny(o,`default`);if(e&&r===void 0){let e=o.default;if(o.type!==Function&&!o.skipFactory&&J(e)){let{propsDefaults:a}=i;if(n in a)r=a[n];else{let o=ET(i);r=a[n]=e.call(null,t),o()}}else r=e;i.ce&&i.ce._setProp(n,r)}o[0]&&(a&&!e?r=!1:o[1]&&(r===``||r===Xy(n))&&(r=!0))}return r}var ow=new WeakMap;function sw(e,t,n=!1){let r=n?ow:t.propsCache,i=r.get(e);if(i)return i;let a=e.props,o={},s=[],c=!1;if(!J(e)){let r=e=>{c=!0;let[n,r]=sw(e,t,!0);Ay(o,n),r&&s.push(...r)};!n&&t.mixins.length&&t.mixins.forEach(r),e.extends&&r(e.extends),e.mixins&&e.mixins.forEach(r)}if(!a&&!c)return By(e)&&r.set(e,Ty),Ty;if(q(a))for(let e=0;e<a.length;e++){let t=Jy(a[e]);cw(t)&&(o[t]=K)}else if(a)for(let e in a){let t=Jy(e);if(cw(t)){let n=a[e],r=o[t]=q(n)||J(n)?{type:n}:Ay({},n),i=r.type,c=!1,l=!0;if(q(i))for(let e=0;e<i.length;++e){let t=i[e],n=J(t)&&t.name;if(n===`Boolean`){c=!0;break}else n===`String`&&(l=!1)}else c=J(i)&&i.name===`Boolean`;r[0]=c,r[1]=l,(c||Ny(r,`default`))&&s.push(t)}}let l=[o,s];return By(e)&&r.set(e,l),l}function cw(e){return e[0]!==`$`&&!Gy(e)}var lw=e=>e===`_`||e===`_ctx`||e===`$stable`,uw=e=>q(e)?e.map(mT):[mT(e)],dw=(e,t,n)=>{if(t._n)return t;let r=tx((...e)=>uw(t(...e)),n);return r._c=!1,r},fw=(e,t,n)=>{let r=e._ctx;for(let n in e){if(lw(n))continue;let i=e[n];if(J(i))t[n]=dw(n,i,r);else if(i!=null){let e=uw(i);t[n]=()=>e}}},pw=(e,t)=>{let n=uw(t);e.slots.default=()=>n},mw=(e,t,n)=>{for(let r in t)(n||!lw(r))&&(e[r]=t[r])},hw=(e,t,n)=>{let r=e.slots=ew();if(e.vnode.shapeFlag&32){let e=t._;e?(mw(r,t,n),n&&tb(r,`_`,e,!0)):fw(t,r)}else t&&pw(e,t)},gw=(e,t,n)=>{let{vnode:r,slots:i}=e,a=!0,o=K;if(r.shapeFlag&32){let e=t._;e?n&&e===1?a=!1:mw(i,t,n):(a=!t.$stable,fw(t,i)),o=t}else t&&(pw(e,t),o={default:1});if(a)for(let e in i)!lw(e)&&o[e]==null&&delete i[e]},_w=zw;function vw(e){return bw(e)}function yw(e){return bw(e,aS)}function bw(e,t){let n=ab();n.__VUE__=!0;let{insert:r,remove:i,patchProp:a,createElement:o,createText:s,createComment:c,setText:l,setElementText:u,parentNode:d,nextSibling:f,setScopeId:p=Ey,insertStaticContent:m}=e,h=(e,t,n,r=null,i=null,a=null,o=void 0,s=null,c=!!t.dynamicChildren)=>{if(e===t)return;e&&!nT(e,t)&&(r=pe(e),le(e,i,a,!0),e=null),t.patchFlag===-2&&(c=!1,t.dynamicChildren=null);let{type:l,ref:u,shapeFlag:d}=t;switch(l){case Uw:g(e,t,n,r);break;case Ww:_(e,t,n,r);break;case Gw:e??v(t,n,r,o);break;case Hw:O(e,t,n,r,i,a,o,s,c);break;default:d&1?x(e,t,n,r,i,a,o,s,c):d&6?ee(e,t,n,r,i,a,o,s,c):(d&64||d&128)&&l.process(e,t,n,r,i,a,o,s,c,ge)}u!=null&&i?Zx(u,e&&e.ref,a,t||e,!t):u==null&&e&&e.ref!=null&&Zx(e.ref,null,a,e,!0)},g=(e,t,n,i)=>{if(e==null)r(t.el=s(t.children),n,i);else{let n=t.el=e.el;t.children!==e.children&&l(n,t.children)}},_=(e,t,n,i)=>{e==null?r(t.el=c(t.children||``),n,i):t.el=e.el},v=(e,t,n,r)=>{[e.el,e.anchor]=m(e.children,t,n,r,e.el,e.anchor)},y=({el:e,anchor:t},n,i)=>{let a;for(;e&&e!==t;)a=f(e),r(e,n,i),e=a;r(t,n,i)},b=({el:e,anchor:t})=>{let n;for(;e&&e!==t;)n=f(e),i(e),e=n;i(t)},x=(e,t,n,r,i,a,o,s,c)=>{if(t.type===`svg`?o=`svg`:t.type===`math`&&(o=`mathml`),e==null)S(t,n,r,i,a,o,s,c);else{let n=e.el&&e.el._isVueCE?e.el:null;try{n&&n._beginPatch(),T(e,t,i,a,o,s,c)}finally{n&&n._endPatch()}}},S=(e,t,n,i,s,c,l,d)=>{let f,p,{props:m,shapeFlag:h,transition:g,dirs:_}=e;if(f=e.el=o(e.type,c,m&&m.is,m),h&8?u(f,e.children):h&16&&w(e.children,f,null,i,s,xw(e,c),l,d),_&&rx(e,null,i,`created`),C(f,e,e.scopeId,l,i),m){for(let e in m)e!==`value`&&!Gy(e)&&a(f,e,null,m[e],c,i);`value`in m&&a(f,`value`,null,m.value,c),(p=m.onVnodeBeforeMount)&&vT(p,i,e)}_&&rx(e,null,i,`beforeMount`);let v=Cw(s,g);v&&g.beforeEnter(f),r(f,t,n),((p=m&&m.onVnodeMounted)||v||_)&&_w(()=>{try{p&&vT(p,i,e),v&&g.enter(f),_&&rx(e,null,i,`mounted`)}finally{}},s)},C=(e,t,n,r,i)=>{if(n&&p(e,n),r)for(let t=0;t<r.length;t++)p(e,r[t]);if(i){let n=i.subTree;if(t===n||kw(n.type)&&(n.ssContent===t||n.ssFallback===t)){let t=i.vnode;C(e,t,t.scopeId,t.slotScopeIds,i.parent)}}},w=(e,t,n,r,i,a,o,s,c=0)=>{for(let l=c;l<e.length;l++)h(null,e[l]=s?hT(e[l]):mT(e[l]),t,n,r,i,a,o,s)},T=(e,t,n,r,i,o,s)=>{let c=t.el=e.el,{patchFlag:l,dynamicChildren:d,dirs:f}=t;l|=e.patchFlag&16;let p=e.props||K,m=t.props||K,h;if(n&&Sw(n,!1),(h=m.onVnodeBeforeUpdate)&&vT(h,n,t,e),f&&rx(t,e,n,`beforeUpdate`),n&&Sw(n,!0),(p.innerHTML&&m.innerHTML==null||p.textContent&&m.textContent==null)&&u(c,``),d?E(e.dynamicChildren,d,c,n,r,xw(t,i),o):s||ae(e,t,c,null,n,r,xw(t,i),o,!1),l>0){if(l&16)D(c,p,m,n,i);else if(l&2&&p.class!==m.class&&a(c,`class`,null,m.class,i),l&4&&a(c,`style`,p.style,m.style,i),l&8){let e=t.dynamicProps;for(let t=0;t<e.length;t++){let r=e[t],o=p[r],s=m[r];(s!==o||r===`value`)&&a(c,r,o,s,i,n)}}l&1&&e.children!==t.children&&u(c,t.children)}else !s&&d==null&&D(c,p,m,n,i);((h=m.onVnodeUpdated)||f)&&_w(()=>{h&&vT(h,n,t,e),f&&rx(t,e,n,`updated`)},r)},E=(e,t,n,r,i,a,o)=>{for(let s=0;s<t.length;s++){let c=e[s],l=t[s];h(c,l,c.el&&(c.type===Hw||!nT(c,l)||c.shapeFlag&198)?d(c.el):n,null,r,i,a,o,!0)}},D=(e,t,n,r,i)=>{if(t!==n){if(t!==K)for(let o in t)!Gy(o)&&!(o in n)&&a(e,o,t[o],null,i,r);for(let o in n){if(Gy(o))continue;let s=n[o],c=t[o];s!==c&&o!==`value`&&a(e,o,c,s,i,r)}`value`in n&&a(e,`value`,t.value,n.value,i)}},O=(e,t,n,i,a,o,c,l,u)=>{let d=t.el=e?e.el:s(``),f=t.anchor=e?e.anchor:s(``),{patchFlag:p,dynamicChildren:m,slotScopeIds:h}=t;h&&(l=l?l.concat(h):h),e==null?(r(d,n,i),r(f,n,i),w(t.children||[],n,f,a,o,c,l,u)):p>0&&p&64&&m&&e.dynamicChildren&&e.dynamicChildren.length===m.length?(E(e.dynamicChildren,m,n,a,o,c,l),(t.key!=null||a&&t===a.subTree)&&ww(e,t,!0)):ae(e,t,n,f,a,o,c,l,u)},ee=(e,t,n,r,i,a,o,s,c)=>{t.slotScopeIds=s,e==null?t.shapeFlag&512?i.ctx.activate(t,n,r,o,c):te(t,n,r,i,a,o,c):ne(e,t,c)},te=(e,t,n,r,i,a,o)=>{let s=e.component=xT(e,r,i);if(bS(e)&&(s.ctx.renderer=ge),AT(s,!1,o),s.asyncDep){if(i&&i.registerDep(s,re,o),!e.el){let r=s.subTree=sT(Ww);_(null,r,t,n),e.placeholder=r.el}}else re(s,e,t,n,i,a,o)},ne=(e,t,n)=>{let r=t.component=e.component;if(YC(e,t,n))if(r.asyncDep&&!r.asyncResolved){ie(r,t,n);return}else r.next=t,r.update();else t.el=e.el,r.vnode=t},re=(e,t,n,r,i,a,o)=>{let s=()=>{if(e.isMounted){let{next:t,bu:n,u:r,parent:s,vnode:c}=e;{let n=Ew(e);if(n){t&&(t.el=c.el,ie(e,t,o)),n.asyncDep.then(()=>{_w(()=>{e.isUnmounted||l()},i)});return}}let u=t,f;Sw(e,!1),t?(t.el=c.el,ie(e,t,o)):t=c,n&&eb(n),(f=t.props&&t.props.onVnodeBeforeUpdate)&&vT(f,s,t,c),Sw(e,!0);let p=GC(e),m=e.subTree;e.subTree=p,h(m,p,d(m.el),pe(m),e,i,a),t.el=p.el,u===null&&QC(e,p.el),r&&_w(r,i),(f=t.props&&t.props.onVnodeUpdated)&&_w(()=>vT(f,s,t,c),i)}else{let o,{el:s,props:c}=t,{bm:l,m:u,parent:d,root:f,type:p}=e,m=_S(t);if(Sw(e,!1),l&&eb(l),!m&&(o=c&&c.onVnodeBeforeMount)&&vT(o,d,t),Sw(e,!0),s&&ve){let t=()=>{e.subTree=GC(e),ve(s,e.subTree,e,i,null)};m&&p.__asyncHydrate?p.__asyncHydrate(s,e,t):t()}else{f.ce&&f.ce._hasShadowRoot()&&f.ce._injectChildStyle(p,e.parent?e.parent.type:void 0);let o=e.subTree=GC(e);h(null,o,n,r,e,i,a),t.el=o.el}if(u&&_w(u,i),!m&&(o=c&&c.onVnodeMounted)){let e=t;_w(()=>vT(o,d,e),i)}(t.shapeFlag&256||d&&_S(d.vnode)&&d.vnode.shapeFlag&256)&&e.a&&_w(e.a,i),e.isMounted=!0,t=n=r=null}};e.scope.on();let c=e.effect=new T_(s);e.scope.off();let l=e.update=c.run.bind(c),u=e.job=c.runIfDirty.bind(c);u.i=e,u.id=e.uid,c.scheduler=()=>zb(u),Sw(e,!0),l()},ie=(e,t,n)=>{t.component=e;let r=e.vnode.props;e.vnode=t,e.next=null,rw(e,t.props,r,n),gw(e,t.children,n),H_(),Hb(e),U_()},ae=(e,t,n,r,i,a,o,s,c=!1)=>{let l=e&&e.children,d=e?e.shapeFlag:0,f=t.children,{patchFlag:p,shapeFlag:m}=t;if(p>0){if(p&128){se(l,f,n,r,i,a,o,s,c);return}else if(p&256){oe(l,f,n,r,i,a,o,s,c);return}}m&8?(d&16&&fe(l,i,a),f!==l&&u(n,f)):d&16?m&16?se(l,f,n,r,i,a,o,s,c):fe(l,i,a,!0):(d&8&&u(n,``),m&16&&w(f,n,r,i,a,o,s,c))},oe=(e,t,n,r,i,a,o,s,c)=>{e||=Ty,t||=Ty;let l=e.length,u=t.length,d=Math.min(l,u),f;for(f=0;f<d;f++){let r=t[f]=c?hT(t[f]):mT(t[f]);h(e[f],r,n,null,i,a,o,s,c)}l>u?fe(e,i,a,!0,!1,d):w(t,n,r,i,a,o,s,c,d)},se=(e,t,n,r,i,a,o,s,c)=>{let l=0,u=t.length,d=e.length-1,f=u-1;for(;l<=d&&l<=f;){let r=e[l],u=t[l]=c?hT(t[l]):mT(t[l]);if(nT(r,u))h(r,u,n,null,i,a,o,s,c);else break;l++}for(;l<=d&&l<=f;){let r=e[d],l=t[f]=c?hT(t[f]):mT(t[f]);if(nT(r,l))h(r,l,n,null,i,a,o,s,c);else break;d--,f--}if(l>d){if(l<=f){let e=f+1,d=e<u?t[e].el:r;for(;l<=f;)h(null,t[l]=c?hT(t[l]):mT(t[l]),n,d,i,a,o,s,c),l++}}else if(l>f)for(;l<=d;)le(e[l],i,a,!0),l++;else{let p=l,m=l,g=new Map;for(l=m;l<=f;l++){let e=t[l]=c?hT(t[l]):mT(t[l]);e.key!=null&&g.set(e.key,l)}let _,v=0,y=f-m+1,b=!1,x=0,S=Array(y);for(l=0;l<y;l++)S[l]=0;for(l=p;l<=d;l++){let r=e[l];if(v>=y){le(r,i,a,!0);continue}let u;if(r.key!=null)u=g.get(r.key);else for(_=m;_<=f;_++)if(S[_-m]===0&&nT(r,t[_])){u=_;break}u===void 0?le(r,i,a,!0):(S[u-m]=l+1,u>=x?x=u:b=!0,h(r,t[u],n,null,i,a,o,s,c),v++)}let C=b?Tw(S):Ty;for(_=C.length-1,l=y-1;l>=0;l--){let e=m+l,d=t[e],f=t[e+1],p=e+1<u?f.el||Ow(f):r;S[l]===0?h(null,d,n,p,i,a,o,s,c):b&&(_<0||l!==C[_]?ce(d,n,p,2):_--)}}},ce=(e,t,n,a,o=null)=>{let{el:s,type:c,transition:l,children:u,shapeFlag:d}=e;if(d&6){ce(e.component.subTree,t,n,a);return}if(d&128){e.suspense.move(t,n,a);return}if(d&64){c.move(e,t,n,ge);return}if(c===Hw){r(s,t,n);for(let e=0;e<u.length;e++)ce(u[e],t,n,a);r(e.anchor,t,n);return}if(c===Gw){y(e,t,n);return}if(a!==2&&d&1&&l)if(a===0)l.beforeEnter(s),r(s,t,n),_w(()=>l.enter(s),o);else{let{leave:a,delayLeave:o,afterLeave:c}=l,u=()=>{e.ctx.isUnmounted?i(s):r(s,t,n)},d=()=>{s._isLeaving&&s[Ax](!0),a(s,()=>{u(),c&&c()})};o?o(s,u,d):d()}else r(s,t,n)},le=(e,t,n,r=!1,i=!1)=>{let{type:a,props:o,ref:s,children:c,dynamicChildren:l,shapeFlag:u,patchFlag:d,dirs:f,cacheIndex:p,memo:m}=e;if(d===-2&&(i=!1),s!=null&&(H_(),Zx(s,null,n,e,!0),U_()),p!=null&&(t.renderCache[p]=void 0),u&256){t.ctx.deactivate(e);return}let h=u&1&&f,g=!_S(e),_;if(g&&(_=o&&o.onVnodeBeforeUnmount)&&vT(_,t,e),u&6)k(e.component,n,r);else{if(u&128){e.suspense.unmount(n,r);return}h&&rx(e,null,t,`beforeUnmount`),u&64?e.type.remove(e,t,n,ge,r):l&&!l.hasOnce&&(a!==Hw||d>0&&d&64)?fe(l,t,n,!1,!0):(a===Hw&&d&384||!i&&u&16)&&fe(c,t,n),r&&ue(e)}let v=m!=null&&p==null;(g&&(_=o&&o.onVnodeUnmounted)||h||v)&&_w(()=>{_&&vT(_,t,e),h&&rx(e,null,t,`unmounted`),v&&(e.el=null)},n)},ue=e=>{let{type:t,el:n,anchor:r,transition:a}=e;if(t===Hw){de(n,r);return}if(t===Gw){b(e);return}let o=()=>{i(n),a&&!a.persisted&&a.afterLeave&&a.afterLeave()};if(e.shapeFlag&1&&a&&!a.persisted){let{leave:t,delayLeave:r}=a,i=()=>t(n,o);r?r(e.el,o,i):i()}else o()},de=(e,t)=>{let n;for(;e!==t;)n=f(e),i(e),e=n;i(t)},k=(e,t,n)=>{let{bum:r,scope:i,job:a,subTree:o,um:s,m:c,a:l}=e;Dw(c),Dw(l),r&&eb(r),i.stop(),a&&(a.flags|=8,le(o,e,t,n)),s&&_w(s,t),_w(()=>{e.isUnmounted=!0},t)},fe=(e,t,n,r=!1,i=!1,a=0)=>{for(let o=a;o<e.length;o++)le(e[o],t,n,r,i)},pe=e=>{if(e.shapeFlag&6)return pe(e.component.subTree);if(e.shapeFlag&128)return e.suspense.next();let t=f(e.anchor||e.el),n=t&&t[_x];return n?f(n):t},me=!1,he=(e,t,n)=>{let r;e==null?t._vnode&&(le(t._vnode,null,null,!0),r=t._vnode.component):h(t._vnode||null,e,t,null,null,null,n),t._vnode=e,me||=(me=!0,Hb(r),Ub(),!1)},ge={p:h,um:le,m:ce,r:ue,mt:te,mc:w,pc:ae,pbc:E,n:pe,o:e},_e,ve;return t&&([_e,ve]=t(ge)),{render:he,hydrate:_e,createApp:LC(he,_e)}}function xw({type:e,props:t},n){return n===`svg`&&e===`foreignObject`||n===`mathml`&&e===`annotation-xml`&&t&&t.encoding&&t.encoding.includes(`html`)?void 0:n}function Sw({effect:e,job:t},n){n?(e.flags|=32,t.flags|=4):(e.flags&=-33,t.flags&=-5)}function Cw(e,t){return(!e||e&&!e.pendingBranch)&&t&&!t.persisted}function ww(e,t,n=!1){let r=e.children,i=t.children;if(q(r)&&q(i))for(let e=0;e<r.length;e++){let t=r[e],a=i[e];a.shapeFlag&1&&!a.dynamicChildren&&((a.patchFlag<=0||a.patchFlag===32)&&(a=i[e]=hT(i[e]),a.el=t.el),!n&&a.patchFlag!==-2&&ww(t,a)),a.type===Uw&&(a.patchFlag===-1&&(a=i[e]=hT(a)),a.el=t.el),a.type===Ww&&!a.el&&(a.el=t.el)}}function Tw(e){let t=e.slice(),n=[0],r,i,a,o,s,c=e.length;for(r=0;r<c;r++){let c=e[r];if(c!==0){if(i=n[n.length-1],e[i]<c){t[r]=i,n.push(r);continue}for(a=0,o=n.length-1;a<o;)s=a+o>>1,e[n[s]]<c?a=s+1:o=s;c<e[n[a]]&&(a>0&&(t[r]=n[a-1]),n[a]=r)}}for(a=n.length,o=n[a-1];a-- >0;)n[a]=o,o=t[o];return n}function Ew(e){let t=e.subTree.component;if(t)return t.asyncDep&&!t.asyncResolved?t:Ew(t)}function Dw(e){if(e)for(let t=0;t<e.length;t++)e[t].flags|=8}function Ow(e){if(e.placeholder)return e.placeholder;let t=e.component;return t?Ow(t.subTree):null}var kw=e=>e.__isSuspense,Aw=0,jw={name:`Suspense`,__isSuspense:!0,process(e,t,n,r,i,a,o,s,c,l){if(e==null)Nw(t,n,r,i,a,o,s,c,l);else{if(a&&a.deps>0&&!e.suspense.isInFallback){t.suspense=e.suspense,t.suspense.vnode=t,t.el=e.el;return}Pw(e,t,n,r,i,o,s,c,l)}},hydrate:Iw,normalize:Lw};function Mw(e,t){let n=e.props&&e.props[t];J(n)&&n()}function Nw(e,t,n,r,i,a,o,s,c){let{p:l,o:{createElement:u}}=c,d=u(`div`),f=e.suspense=Fw(e,i,r,t,d,n,a,o,s,c);l(null,f.pendingBranch=e.ssContent,d,null,r,f,a,o),f.deps>0?(Mw(e,`onPending`),Mw(e,`onFallback`),l(null,e.ssFallback,t,n,r,null,a,o),Bw(f,e.ssFallback)):f.resolve(!1,!0)}function Pw(e,t,n,r,i,a,o,s,{p:c,um:l,o:{createElement:u}}){let d=t.suspense=e.suspense;d.vnode=t,t.el=e.el;let f=t.ssContent,p=t.ssFallback,{activeBranch:m,pendingBranch:h,isInFallback:g,isHydrating:_}=d;if(h)d.pendingBranch=f,nT(h,f)?(c(h,f,d.hiddenContainer,null,i,d,a,o,s),d.deps<=0?d.resolve():g&&(_||(c(m,p,n,r,i,null,a,o,s),Bw(d,p)))):(d.pendingId=Aw++,_?(d.isHydrating=!1,d.activeBranch=h):l(h,i,d),d.deps=0,d.effects.length=0,d.hiddenContainer=u(`div`),g?(c(null,f,d.hiddenContainer,null,i,d,a,o,s),d.deps<=0?d.resolve():(c(m,p,n,r,i,null,a,o,s),Bw(d,p))):m&&nT(m,f)?(c(m,f,n,r,i,d,a,o,s),d.resolve(!0)):(c(null,f,d.hiddenContainer,null,i,d,a,o,s),d.deps<=0&&d.resolve()));else if(m&&nT(m,f))c(m,f,n,r,i,d,a,o,s),Bw(d,f);else if(Mw(t,`onPending`),d.pendingBranch=f,f.shapeFlag&512?d.pendingId=f.component.suspenseId:d.pendingId=Aw++,c(null,f,d.hiddenContainer,null,i,d,a,o,s),d.deps<=0)d.resolve();else{let{timeout:e,pendingId:t}=d;e>0?setTimeout(()=>{d.pendingId===t&&d.fallback(p)},e):e===0&&d.fallback(p)}}function Fw(e,t,n,r,i,a,o,s,c,l,u=!1){let{p:d,m:f,um:p,n:m,o:{parentNode:h,remove:g}}=l,_,v=Vw(e);v&&t&&t.pendingBranch&&(_=t.pendingId,t.deps++);let y=e.props?rb(e.props.timeout):void 0,b=a,x={vnode:e,parent:t,parentComponent:n,namespace:o,container:r,hiddenContainer:i,deps:0,pendingId:Aw++,timeout:typeof y==`number`?y:-1,activeBranch:null,isFallbackMountPending:!1,pendingBranch:null,isInFallback:!u,isHydrating:u,isUnmounted:!1,effects:[],resolve(e=!1,n=!1){let{vnode:r,activeBranch:i,pendingBranch:o,pendingId:s,effects:c,parentComponent:l,container:u,isInFallback:d}=x,g=!1;x.isHydrating?x.isHydrating=!1:e||(g=i&&o.transition&&o.transition.mode===`out-in`,g&&(i.transition.afterLeave=()=>{s===x.pendingId&&(f(o,u,a===b?m(i):a,0),Vb(c),d&&r.ssFallback&&(r.ssFallback.el=null))}),i&&!x.isFallbackMountPending&&(h(i.el)===u&&(a=m(i)),p(i,l,x,!0),!g&&d&&r.ssFallback&&_w(()=>r.ssFallback.el=null,x)),g||f(o,u,a,0)),x.isFallbackMountPending=!1,Bw(x,o),x.pendingBranch=null,x.isInFallback=!1;let y=x.parent,S=!1;for(;y;){if(y.pendingBranch){y.effects.push(...c),S=!0;break}y=y.parent}!S&&!g&&Vb(c),x.effects=[],v&&t&&t.pendingBranch&&_===t.pendingId&&(t.deps--,t.deps===0&&!n&&t.resolve()),Mw(r,`onResolve`)},fallback(e){if(!x.pendingBranch)return;let{vnode:t,activeBranch:n,parentComponent:r,container:i,namespace:a}=x;Mw(t,`onFallback`);let o=m(n),l=()=>{x.isFallbackMountPending=!1,x.isInFallback&&(d(null,e,i,o,r,null,a,s,c),Bw(x,e))},u=e.transition&&e.transition.mode===`out-in`;u&&(x.isFallbackMountPending=!0,n.transition.afterLeave=l),x.isInFallback=!0,p(n,r,null,!0),u||l()},move(e,t,n){x.activeBranch&&f(x.activeBranch,e,t,n),x.container=e},next(){return x.activeBranch&&m(x.activeBranch)},registerDep(e,t,n){let r=!!x.pendingBranch;r&&x.deps++;let i=e.vnode.el;e.asyncDep.catch(t=>{Ob(t,e,0)}).then(a=>{if(e.isUnmounted||x.isUnmounted||x.pendingId!==e.suspenseId)return;DT(),e.asyncResolved=!0;let{vnode:s}=e;MT(e,a,!1),i&&(s.el=i);let c=!i&&e.subTree.el;t(e,s,h(i||e.subTree.el),i?null:m(e.subTree),x,o,n),c&&(s.placeholder=null,g(c)),QC(e,s.el),r&&--x.deps===0&&x.resolve()})},unmount(e,t){x.isUnmounted=!0,x.activeBranch&&p(x.activeBranch,n,e,t),x.pendingBranch&&p(x.pendingBranch,n,e,t)}};return x}function Iw(e,t,n,r,i,a,o,s,c){let l=t.suspense=Fw(t,r,n,e.parentNode,document.createElement(`div`),null,i,a,o,s,!0),u=c(e,l.pendingBranch=t.ssContent,n,l,a,o);return l.deps===0&&l.resolve(!1,!0),u}function Lw(e){let{shapeFlag:t,children:n}=e,r=t&32;e.ssContent=Rw(r?n.default:n),e.ssFallback=r?Rw(n.fallback):sT(Ww)}function Rw(e){let t;if(J(e)){let n=Xw&&e._c;n&&(e._d=!1,Jw()),e=e(),n&&(e._d=!0,t=qw,Yw())}return q(e)&&(e=KC(e)),e=mT(e),t&&!e.dynamicChildren&&(e.dynamicChildren=t.filter(t=>t!==e)),e}function zw(e,t){t&&t.pendingBranch?q(e)?t.effects.push(...e):t.effects.push(e):Vb(e)}function Bw(e,t){e.activeBranch=t;let{vnode:n,parentComponent:r}=e,i=t.el;for(;!i&&t.component;)t=t.component.subTree,i=t.el;n.el=i,r&&r.subTree===n&&(r.vnode.el=i,QC(r,i))}function Vw(e){let t=e.props&&e.props.suspensible;return t!=null&&t!==!1}var Hw=Symbol.for(`v-fgt`),Uw=Symbol.for(`v-txt`),Ww=Symbol.for(`v-cmt`),Gw=Symbol.for(`v-stc`),Kw=[],qw=null;function Jw(e=!1){Kw.push(qw=e?null:[])}function Yw(){Kw.pop(),qw=Kw[Kw.length-1]||null}var Xw=1;function Zw(e,t=!1){Xw+=e,e<0&&qw&&t&&(qw.hasOnce=!0)}function Qw(e){return e.dynamicChildren=Xw>0?qw||Ty:null,Yw(),Xw>0&&qw&&qw.push(e),e}function $w(e,t,n,r,i,a){return Qw(oT(e,t,n,r,i,a,!0))}function eT(e,t,n,r,i){return Qw(sT(e,t,n,r,i,!0))}function tT(e){return e?e.__v_isVNode===!0:!1}function nT(e,t){return e.type===t.type&&e.key===t.key}function rT(e){}var iT=({key:e})=>e??null,aT=({ref:e,ref_key:t,ref_for:n})=>(typeof e==`number`&&(e=``+e),e==null?null:Ry(e)||Xv(e)||J(e)?{i:Yb,r:e,k:t,f:!!n}:e);function oT(e,t=null,n=null,r=0,i=null,a=e===Hw?0:1,o=!1,s=!1){let c={__v_isVNode:!0,__v_skip:!0,type:e,props:t,key:t&&iT(t),ref:t&&aT(t),scopeId:Xb,slotScopeIds:null,children:n,component:null,suspense:null,ssContent:null,ssFallback:null,dirs:null,transition:null,el:null,anchor:null,target:null,targetStart:null,targetAnchor:null,staticCount:0,shapeFlag:a,patchFlag:r,dynamicProps:i,dynamicChildren:null,appContext:null,ctx:Yb};return s?(gT(c,n),a&128&&e.normalize(c)):n&&(c.shapeFlag|=Ry(n)?8:16),Xw>0&&!o&&qw&&(c.patchFlag>0||a&6)&&c.patchFlag!==32&&qw.push(c),c}var sT=cT;function cT(e,t=null,n=null,r=0,i=null,a=!1){if((!e||e===WS)&&(e=Ww),tT(e)){let r=uT(e,t,!0);return n&&gT(r,n),Xw>0&&!a&&qw&&(r.shapeFlag&6?qw[qw.indexOf(e)]=r:qw.push(r)),r.patchFlag=-2,r}if(HT(e)&&(e=e.__vccOpts),t){t=lT(t);let{class:e,style:n}=t;e&&!Ry(e)&&(t.class=fb(e)),By(n)&&(Kv(n)&&!q(n)&&(n=Ay({},n)),t.style=sb(n))}let o=Ry(e)?1:kw(e)?128:vx(e)?64:By(e)?4:J(e)?2:0;return oT(e,t,n,r,i,o,a,!0)}function lT(e){return e?Kv(e)||tw(e)?Ay({},e):e:null}function uT(e,t,n=!1,r=!1){let{props:i,ref:a,patchFlag:o,children:s,transition:c}=e,l=t?_T(i||{},t):i,u={__v_isVNode:!0,__v_skip:!0,type:e.type,props:l,key:l&&iT(l),ref:t&&t.ref?n&&a?q(a)?a.concat(aT(t)):[a,aT(t)]:aT(t):a,scopeId:e.scopeId,slotScopeIds:e.slotScopeIds,children:s,target:e.target,targetStart:e.targetStart,targetAnchor:e.targetAnchor,staticCount:e.staticCount,shapeFlag:e.shapeFlag,patchFlag:t&&e.type!==Hw?o===-1?16:o|16:o,dynamicProps:e.dynamicProps,dynamicChildren:e.dynamicChildren,appContext:e.appContext,dirs:e.dirs,transition:c,component:e.component,suspense:e.suspense,ssContent:e.ssContent&&uT(e.ssContent),ssFallback:e.ssFallback&&uT(e.ssFallback),placeholder:e.placeholder,el:e.el,anchor:e.anchor,ctx:e.ctx,ce:e.ce};return c&&r&&Ux(u,c.clone(u)),u}function dT(e=` `,t=0){return sT(Uw,null,e,t)}function fT(e,t){let n=sT(Gw,null,e);return n.staticCount=t,n}function pT(e=``,t=!1){return t?(Jw(),eT(Ww,null,e)):sT(Ww,null,e)}function mT(e){return e==null||typeof e==`boolean`?sT(Ww):q(e)?sT(Hw,null,e.slice()):tT(e)?hT(e):sT(Uw,null,String(e))}function hT(e){return e.el===null&&e.patchFlag!==-1||e.memo?e:uT(e)}function gT(e,t){let n=0,{shapeFlag:r}=e;if(t==null)t=null;else if(q(t))n=16;else if(typeof t==`object`)if(r&65){let n=t.default;n&&(n._c&&(n._d=!1),gT(e,n()),n._c&&(n._d=!0));return}else{n=32;let r=t._;!r&&!tw(t)?t._ctx=Yb:r===3&&Yb&&(Yb.slots._===1?t._=1:(t._=2,e.patchFlag|=1024))}else J(t)?(t={default:t,_ctx:Yb},n=32):(t=String(t),r&64?(n=16,t=[dT(t)]):n=8);e.children=t,e.shapeFlag|=n}function _T(...e){let t={};for(let n=0;n<e.length;n++){let r=e[n];for(let e in r)if(e===`class`)t.class!==r.class&&(t.class=fb([t.class,r.class]));else if(e===`style`)t.style=sb([t.style,r.style]);else if(Oy(e)){let n=t[e],i=r[e];i&&n!==i&&!(q(n)&&n.includes(i))?t[e]=n?[].concat(n,i):i:i==null&&n==null&&!ky(e)&&(t[e]=i)}else e!==``&&(t[e]=r[e])}return t}function vT(e,t,n,r=null){Db(e,t,7,[n,r])}var yT=FC(),bT=0;function xT(e,t,n){let r=e.type,i=(t?t.appContext:e.appContext)||yT,a={uid:bT++,vnode:e,type:r,parent:t,appContext:i,root:null,next:null,subTree:null,effect:null,update:null,job:null,scope:new b_(!0),render:null,proxy:null,exposed:null,exposeProxy:null,withProxy:null,provides:t?t.provides:Object.create(i.provides),ids:t?t.ids:[``,0,0],accessCache:null,renderCache:[],components:null,directives:null,propsOptions:sw(r,i),emitsOptions:UC(r,i),emit:null,emitted:null,propsDefaults:K,inheritAttrs:r.inheritAttrs,ctx:K,data:K,props:K,attrs:K,slots:K,refs:K,setupState:K,setupContext:null,suspense:n,suspenseId:n?n.pendingId:0,asyncDep:null,asyncResolved:!1,isMounted:!1,isUnmounted:!1,isDeactivated:!1,bc:null,c:null,bm:null,m:null,bu:null,u:null,um:null,bum:null,da:null,a:null,rtg:null,rtc:null,ec:null,sp:null};return a.ctx={_:a},a.root=t?t.root:a,a.emit=VC.bind(null,a),e.ce&&e.ce(a),a}var ST=null,CT=()=>ST||Yb,wT,TT;{let e=ab(),t=(t,n)=>{let r;return(r=e[t])||(r=e[t]=[]),r.push(n),e=>{r.length>1?r.forEach(t=>t(e)):r[0](e)}};wT=t(`__VUE_INSTANCE_SETTERS__`,e=>ST=e),TT=t(`__VUE_SSR_SETTERS__`,e=>kT=e)}var ET=e=>{let t=ST;return wT(e),e.scope.on(),()=>{e.scope.off(),wT(t)}},DT=()=>{ST&&ST.scope.off(),wT(null)};function OT(e){return e.vnode.shapeFlag&4}var kT=!1;function AT(e,t=!1,n=!1){t&&TT(t);let{props:r,children:i}=e.vnode,a=OT(e);nw(e,r,a,t),hw(e,i,n||t);let o=a?jT(e,t):void 0;return t&&TT(!1),o}function jT(e,t){let n=e.type;e.accessCache=Object.create(null),e.proxy=new Proxy(e.ctx,rC);let{setup:r}=n;if(r){H_();let n=e.setupContext=r.length>1?zT(e):null,i=ET(e),a=Eb(r,e,0,[e.props,n]),o=Vy(a);if(U_(),i(),(o||e.sp)&&!_S(e)&&qx(e),o){if(a.then(DT,DT),t)return a.then(n=>{MT(e,n,t)}).catch(t=>{Ob(t,e,0)});e.asyncDep=a}else MT(e,a,t)}else LT(e,t)}function MT(e,t,n){J(t)?e.type.__ssrInlineRender?e.ssrRender=t:e.render=t:By(t)&&(e.setupState=ay(t)),LT(e,n)}var NT,PT;function FT(e){NT=e,PT=e=>{e.render._rc&&(e.withProxy=new Proxy(e.ctx,iC))}}var IT=()=>!NT;function LT(e,t,n){let r=e.type;if(!e.render){if(!t&&NT&&!r.render){let t=r.template||TC(e).template;if(t){let{isCustomElement:n,compilerOptions:i}=e.appContext.config,{delimiters:a,compilerOptions:o}=r,s=Ay(Ay({isCustomElement:n,delimiters:a},i),o);r.render=NT(t,s)}}e.render=r.render||Ey,PT&&PT(e)}{let t=ET(e);H_();try{xC(e)}finally{U_(),t()}}}var RT={get(e,t){return $_(e,`get`,``),e[t]}};function zT(e){return{attrs:new Proxy(e.attrs,RT),slots:e.slots,emit:e.emit,expose:t=>{e.exposed=t||{}}}}function BT(e){return e.exposed?e.exposeProxy||=new Proxy(ay(qv(e.exposed)),{get(t,n){if(n in t)return t[n];if(n in tC)return tC[n](e)},has(e,t){return t in e||t in tC}}):e.proxy}function VT(e,t=!0){return J(e)?e.displayName||e.name:e.name||t&&e.__name}function HT(e){return J(e)&&`__vccOpts`in e}var Y=(e,t)=>my(e,t,kT);function UT(e,t,n){try{Zw(-1);let r=arguments.length;return r===2?By(t)&&!q(t)?tT(t)?sT(e,null,[t]):sT(e,t):sT(e,null,t):(r>3?n=Array.prototype.slice.call(arguments,2):r===3&&tT(n)&&(n=[n]),sT(e,t,n))}finally{Zw(1)}}function WT(){return;function e(t,n,r){let i=t[r];if(q(i)&&i.includes(n)||By(i)&&n in i||t.extends&&e(t.extends,n,r)||t.mixins&&t.mixins.some(t=>e(t,n,r)))return!0}}function GT(e,t,n,r){let i=n[r];if(i&&KT(i,e))return i;let a=t();return a.memo=e.slice(),a.cacheIndex=r,n[r]=a}function KT(e,t){let n=e.memo;if(n.length!=t.length)return!1;for(let e=0;e<n.length;e++)if($y(n[e],t[e]))return!1;return Xw>0&&qw&&qw.push(e),!0}var qT=`3.5.33`,JT=Ey,YT=Tb,XT=Kb,ZT=Jb,QT={createComponentInstance:xT,setupComponent:AT,renderComponentRoot:GC,setCurrentRenderingInstance:Zb,isVNode:tT,normalizeVNode:mT,getComponentPublicInstance:BT,ensureValidVNode:QS,pushWarningContext:xb,popWarningContext:Sb};function $T(e){let t=Object.create(null);for(let n of e.split(`,`))t[n]=1;return e=>e in t}var eE={},tE=()=>{},nE=e=>e.charCodeAt(0)===111&&e.charCodeAt(1)===110&&(e.charCodeAt(2)>122||e.charCodeAt(2)<97),rE=e=>e.startsWith(`onUpdate:`),iE=Object.assign,aE=Object.prototype.hasOwnProperty,oE=(e,t)=>aE.call(e,t),sE=Array.isArray,cE=e=>hE(e)===`[object Set]`,lE=e=>hE(e)===`[object Date]`,uE=e=>typeof e==`function`,dE=e=>typeof e==`string`,fE=e=>typeof e==`symbol`,pE=e=>typeof e==`object`&&!!e,mE=Object.prototype.toString,hE=e=>mE.call(e),gE=e=>hE(e)===`[object Object]`,_E=e=>{let t=Object.create(null);return(n=>t[n]||(t[n]=e(n)))},vE=/-\w/g,yE=_E(e=>e.replace(vE,e=>e.slice(1).toUpperCase())),bE=/\B([A-Z])/g,xE=_E(e=>e.replace(bE,`-$1`).toLowerCase()),SE=_E(e=>e.charAt(0).toUpperCase()+e.slice(1)),CE=(e,...t)=>{for(let n=0;n<e.length;n++)e[n](...t)},wE=e=>{let t=parseFloat(e);return isNaN(t)?e:t},TE=e=>{let t=dE(e)?Number(e):NaN;return isNaN(t)?e:t},EE=`itemscope,allowfullscreen,formnovalidate,ismap,nomodule,novalidate,readonly`,DE=$T(EE);EE+``;function OE(e){return!!e||e===``}function kE(e,t){if(e.length!==t.length)return!1;let n=!0;for(let r=0;n&&r<e.length;r++)n=AE(e[r],t[r]);return n}function AE(e,t){if(e===t)return!0;let n=lE(e),r=lE(t);if(n||r)return n&&r?e.getTime()===t.getTime():!1;if(n=fE(e),r=fE(t),n||r)return e===t;if(n=sE(e),r=sE(t),n||r)return n&&r?kE(e,t):!1;if(n=pE(e),r=pE(t),n||r){if(!n||!r||Object.keys(e).length!==Object.keys(t).length)return!1;for(let n in e){let r=e.hasOwnProperty(n),i=t.hasOwnProperty(n);if(r&&!i||!r&&i||!AE(e[n],t[n]))return!1}}return String(e)===String(t)}function jE(e,t){return e.findIndex(e=>AE(e,t))}function ME(e){return e==null?`initial`:typeof e==`string`?e===``?` `:e:String(e)}var NE=e({BaseTransition:()=>Rx,BaseTransitionPropsValidators:()=>Px,Comment:()=>Ww,DeprecationTypes:()=>null,EffectScope:()=>b_,ErrorCodes:()=>wb,ErrorTypeStrings:()=>YT,Fragment:()=>Hw,KeepAlive:()=>xS,ReactiveEffect:()=>T_,Static:()=>Gw,Suspense:()=>jw,Teleport:()=>Dx,Text:()=>Uw,TrackOpTypes:()=>hy,Transition:()=>qE,TransitionGroup:()=>nO,TriggerOpTypes:()=>gy,VueElement:()=>JD,assertNumber:()=>Cb,callWithAsyncErrorHandling:()=>Db,callWithErrorHandling:()=>Eb,camelize:()=>Jy,capitalize:()=>Zy,cloneVNode:()=>uT,compatUtils:()=>null,computed:()=>Y,createApp:()=>LO,createBlock:()=>eT,createCommentVNode:()=>pT,createElementBlock:()=>$w,createElementVNode:()=>oT,createHydrationRenderer:()=>yw,createPropsRestProxy:()=>vC,createRenderer:()=>vw,createSSRApp:()=>RO,createSlots:()=>XS,createStaticVNode:()=>fT,createTextVNode:()=>dT,createVNode:()=>sT,customRef:()=>sy,defineAsyncComponent:()=>vS,defineComponent:()=>Gx,defineCustomElement:()=>GD,defineEmits:()=>oC,defineExpose:()=>sC,defineModel:()=>uC,defineOptions:()=>cC,defineProps:()=>aC,defineSSRCustomElement:()=>KD,defineSlots:()=>lC,devtools:()=>XT,effect:()=>R_,effectScope:()=>x_,getCurrentInstance:()=>CT,getCurrentScope:()=>S_,getCurrentWatcher:()=>by,getTransitionRawChildren:()=>Wx,guardReactiveProps:()=>lT,h:()=>UT,handleError:()=>Ob,hasInjectionContext:()=>ox,hydrate:()=>IO,hydrateOnIdle:()=>dS,hydrateOnInteraction:()=>hS,hydrateOnMediaQuery:()=>mS,hydrateOnVisible:()=>pS,initCustomFormatter:()=>WT,initDirectivesForSSR:()=>HO,inject:()=>ax,isMemoSame:()=>KT,isProxy:()=>Kv,isReactive:()=>Uv,isReadonly:()=>Wv,isRef:()=>Xv,isRuntimeOnly:()=>IT,isShallow:()=>Gv,isVNode:()=>tT,markRaw:()=>qv,mergeDefaults:()=>gC,mergeModels:()=>_C,mergeProps:()=>_T,nextTick:()=>Lb,nodeOps:()=>VE,normalizeClass:()=>fb,normalizeProps:()=>pb,normalizeStyle:()=>sb,onActivated:()=>CS,onBeforeMount:()=>jS,onBeforeUnmount:()=>FS,onBeforeUpdate:()=>NS,onDeactivated:()=>wS,onErrorCaptured:()=>BS,onMounted:()=>MS,onRenderTracked:()=>zS,onRenderTriggered:()=>RS,onScopeDispose:()=>C_,onServerPrefetch:()=>LS,onUnmounted:()=>IS,onUpdated:()=>PS,onWatcherCleanup:()=>xy,openBlock:()=>Jw,patchProp:()=>VD,popScopeId:()=>$b,provide:()=>ix,proxyRefs:()=>ay,pushScopeId:()=>Qb,queuePostFlushCb:()=>Vb,reactive:()=>Rv,readonly:()=>Bv,ref:()=>Zv,registerRuntimeCompiler:()=>FT,render:()=>FO,renderList:()=>YS,renderSlot:()=>ZS,resolveComponent:()=>US,resolveDirective:()=>KS,resolveDynamicComponent:()=>GS,resolveFilter:()=>null,resolveTransitionHooks:()=>Bx,setBlockTracking:()=>Zw,setDevtoolsHook:()=>ZT,setTransitionHooks:()=>Ux,shallowReactive:()=>zv,shallowReadonly:()=>Vv,shallowRef:()=>Qv,ssrContextKey:()=>sx,ssrUtils:()=>QT,stop:()=>z_,toDisplayString:()=>_b,toHandlerKey:()=>Qy,toHandlers:()=>$S,toRaw:()=>G,toRef:()=>dy,toRefs:()=>cy,toValue:()=>ry,transformVNodeArgs:()=>rT,triggerRef:()=>ty,unref:()=>ny,useAttrs:()=>pC,useCssModule:()=>ZD,useCssVars:()=>hD,useHost:()=>YD,useId:()=>Kx,useModel:()=>zC,useSSRContext:()=>cx,useShadowRoot:()=>XD,useSlots:()=>fC,useTemplateRef:()=>Jx,useTransitionState:()=>Mx,vModelCheckbox:()=>mO,vModelDynamic:()=>xO,vModelRadio:()=>gO,vModelSelect:()=>_O,vModelText:()=>pO,vShow:()=>dD,version:()=>qT,warn:()=>JT,watch:()=>fx,watchEffect:()=>lx,watchPostEffect:()=>ux,watchSyncEffect:()=>dx,withAsyncContext:()=>yC,withCtx:()=>tx,withDefaults:()=>dC,withDirectives:()=>nx,withKeys:()=>kO,withMemo:()=>GT,withModifiers:()=>DO,withScopeId:()=>ex}),PE=void 0,FE=typeof window<`u`&&window.trustedTypes;if(FE)try{PE=FE.createPolicy(`vue`,{createHTML:e=>e})}catch{}var IE=PE?e=>PE.createHTML(e):e=>e,LE=`http://www.w3.org/2000/svg`,RE=`http://www.w3.org/1998/Math/MathML`,zE=typeof document<`u`?document:null,BE=zE&&zE.createElement(`template`),VE={insert:(e,t,n)=>{t.insertBefore(e,n||null)},remove:e=>{let t=e.parentNode;t&&t.removeChild(e)},createElement:(e,t,n,r)=>{let i=t===`svg`?zE.createElementNS(LE,e):t===`mathml`?zE.createElementNS(RE,e):n?zE.createElement(e,{is:n}):zE.createElement(e);return e===`select`&&r&&r.multiple!=null&&i.setAttribute(`multiple`,r.multiple),i},createText:e=>zE.createTextNode(e),createComment:e=>zE.createComment(e),setText:(e,t)=>{e.nodeValue=t},setElementText:(e,t)=>{e.textContent=t},parentNode:e=>e.parentNode,nextSibling:e=>e.nextSibling,querySelector:e=>zE.querySelector(e),setScopeId(e,t){e.setAttribute(t,``)},insertStaticContent(e,t,n,r,i,a){let o=n?n.previousSibling:t.lastChild;if(i&&(i===a||i.nextSibling))for(;t.insertBefore(i.cloneNode(!0),n),!(i===a||!(i=i.nextSibling)););else{BE.innerHTML=IE(r===`svg`?`<svg>${e}</svg>`:r===`mathml`?`<math>${e}</math>`:e);let i=BE.content;if(r===`svg`||r===`mathml`){let e=i.firstChild;for(;e.firstChild;)i.appendChild(e.firstChild);i.removeChild(e)}t.insertBefore(i,n)}return[o?o.nextSibling:t.firstChild,n?n.previousSibling:t.lastChild]}},HE=`transition`,UE=`animation`,WE=Symbol(`_vtc`),GE={name:String,type:String,css:{type:Boolean,default:!0},duration:[String,Number,Object],enterFromClass:String,enterActiveClass:String,enterToClass:String,appearFromClass:String,appearActiveClass:String,appearToClass:String,leaveFromClass:String,leaveActiveClass:String,leaveToClass:String},KE=iE({},Px,GE),qE=(e=>(e.displayName=`Transition`,e.props=KE,e))((e,{slots:t})=>UT(Rx,XE(e),t)),JE=(e,t=[])=>{sE(e)?e.forEach(e=>e(...t)):e&&e(...t)},YE=e=>e?sE(e)?e.some(e=>e.length>1):e.length>1:!1;function XE(e){let t={};for(let n in e)n in GE||(t[n]=e[n]);if(e.css===!1)return t;let{name:n=`v`,type:r,duration:i,enterFromClass:a=`${n}-enter-from`,enterActiveClass:o=`${n}-enter-active`,enterToClass:s=`${n}-enter-to`,appearFromClass:c=a,appearActiveClass:l=o,appearToClass:u=s,leaveFromClass:d=`${n}-leave-from`,leaveActiveClass:f=`${n}-leave-active`,leaveToClass:p=`${n}-leave-to`}=e,m=ZE(i),h=m&&m[0],g=m&&m[1],{onBeforeEnter:_,onEnter:v,onEnterCancelled:y,onLeave:b,onLeaveCancelled:x,onBeforeAppear:S=_,onAppear:C=v,onAppearCancelled:w=y}=t,T=(e,t,n,r)=>{e._enterCancelled=r,eD(e,t?u:s),eD(e,t?l:o),n&&n()},E=(e,t)=>{e._isLeaving=!1,eD(e,d),eD(e,p),eD(e,f),t&&t()},D=e=>(t,n)=>{let i=e?C:v,o=()=>T(t,e,n);JE(i,[t,o]),tD(()=>{eD(t,e?c:a),$E(t,e?u:s),YE(i)||rD(t,r,h,o)})};return iE(t,{onBeforeEnter(e){JE(_,[e]),$E(e,a),$E(e,o)},onBeforeAppear(e){JE(S,[e]),$E(e,c),$E(e,l)},onEnter:D(!1),onAppear:D(!0),onLeave(e,t){e._isLeaving=!0;let n=()=>E(e,t);$E(e,d),e._enterCancelled?($E(e,f),sD(e)):(sD(e),$E(e,f)),tD(()=>{e._isLeaving&&(eD(e,d),$E(e,p),YE(b)||rD(e,r,g,n))}),JE(b,[e,n])},onEnterCancelled(e){T(e,!1,void 0,!0),JE(y,[e])},onAppearCancelled(e){T(e,!0,void 0,!0),JE(w,[e])},onLeaveCancelled(e){E(e),JE(x,[e])}})}function ZE(e){if(e==null)return null;if(pE(e))return[QE(e.enter),QE(e.leave)];{let t=QE(e);return[t,t]}}function QE(e){return TE(e)}function $E(e,t){t.split(/\s+/).forEach(t=>t&&e.classList.add(t)),(e[WE]||(e[WE]=new Set)).add(t)}function eD(e,t){t.split(/\s+/).forEach(t=>t&&e.classList.remove(t));let n=e[WE];n&&(n.delete(t),n.size||(e[WE]=void 0))}function tD(e){requestAnimationFrame(()=>{requestAnimationFrame(e)})}var nD=0;function rD(e,t,n,r){let i=e._endId=++nD,a=()=>{i===e._endId&&r()};if(n!=null)return setTimeout(a,n);let{type:o,timeout:s,propCount:c}=iD(e,t);if(!o)return r();let l=o+`end`,u=0,d=()=>{e.removeEventListener(l,f),a()},f=t=>{t.target===e&&++u>=c&&d()};setTimeout(()=>{u<c&&d()},s+1),e.addEventListener(l,f)}function iD(e,t){let n=window.getComputedStyle(e),r=e=>(n[e]||``).split(`, `),i=r(`${HE}Delay`),a=r(`${HE}Duration`),o=aD(i,a),s=r(`${UE}Delay`),c=r(`${UE}Duration`),l=aD(s,c),u=null,d=0,f=0;t===HE?o>0&&(u=HE,d=o,f=a.length):t===UE?l>0&&(u=UE,d=l,f=c.length):(d=Math.max(o,l),u=d>0?o>l?HE:UE:null,f=u?u===HE?a.length:c.length:0);let p=u===HE&&/\b(?:transform|all)(?:,|$)/.test(r(`${HE}Property`).toString());return{type:u,timeout:d,propCount:f,hasTransform:p}}function aD(e,t){for(;e.length<t.length;)e=e.concat(e);return Math.max(...t.map((t,n)=>oD(t)+oD(e[n])))}function oD(e){return e===`auto`?0:Number(e.slice(0,-1).replace(`,`,`.`))*1e3}function sD(e){return(e?e.ownerDocument:document).body.offsetHeight}function cD(e,t,n){let r=e[WE];r&&(t=(t?[t,...r]:[...r]).join(` `)),t==null?e.removeAttribute(`class`):n?e.setAttribute(`class`,t):e.className=t}var lD=Symbol(`_vod`),uD=Symbol(`_vsh`),dD={name:`show`,beforeMount(e,{value:t},{transition:n}){e[lD]=e.style.display===`none`?``:e.style.display,n&&t?n.beforeEnter(e):fD(e,t)},mounted(e,{value:t},{transition:n}){n&&t&&n.enter(e)},updated(e,{value:t,oldValue:n},{transition:r}){!t!=!n&&(r?t?(r.beforeEnter(e),fD(e,!0),r.enter(e)):r.leave(e,()=>{fD(e,!1)}):fD(e,t))},beforeUnmount(e,{value:t}){fD(e,t)}};function fD(e,t){e.style.display=t?e[lD]:`none`,e[uD]=!t}function pD(){dD.getSSRProps=({value:e})=>{if(!e)return{style:{display:`none`}}}}var mD=Symbol(``);function hD(e){let t=CT();if(!t)return;let n=t.ut=(n=e(t.proxy))=>{Array.from(document.querySelectorAll(`[data-v-owner="${t.uid}"]`)).forEach(e=>_D(e,n))},r=()=>{let r=e(t.proxy);t.ce?_D(t.ce,r):gD(t.subTree,r),n(r)};NS(()=>{Vb(r)}),MS(()=>{fx(r,tE,{flush:`post`});let e=new MutationObserver(r);e.observe(t.subTree.el.parentNode,{childList:!0}),IS(()=>e.disconnect())})}function gD(e,t){if(e.shapeFlag&128){let n=e.suspense;e=n.activeBranch,n.pendingBranch&&!n.isHydrating&&n.effects.push(()=>{gD(n.activeBranch,t)})}for(;e.component;)e=e.component.subTree;if(e.shapeFlag&1&&e.el)_D(e.el,t);else if(e.type===Hw)e.children.forEach(e=>gD(e,t));else if(e.type===Gw){let{el:n,anchor:r}=e;for(;n&&(_D(n,t),n!==r);)n=n.nextSibling}}function _D(e,t){if(e.nodeType===1){let n=e.style,r=``;for(let e in t){let i=ME(t[e]);n.setProperty(`--${e}`,i),r+=`--${e}: ${i};`}n[mD]=r}}var vD=/(?:^|;)\s*display\s*:/;function yD(e,t,n){let r=e.style,i=dE(n),a=!1;if(n&&!i){if(t)if(dE(t))for(let e of t.split(`;`)){let t=e.slice(0,e.indexOf(`:`)).trim();n[t]??xD(r,t,``)}else for(let e in t)n[e]??xD(r,e,``);for(let i in n){i===`display`&&(a=!0);let o=n[i];o==null?xD(r,i,``):TD(e,i,!dE(t)&&t?t[i]:void 0,o)||xD(r,i,o)}}else if(i){if(t!==n){let e=r[mD];e&&(n+=`;`+e),r.cssText=n,a=vD.test(n)}}else t&&e.removeAttribute(`style`);lD in e&&(e[lD]=a?r.display:``,e[uD]&&(r.display=`none`))}var bD=/\s*!important$/;function xD(e,t,n){if(sE(n))n.forEach(n=>xD(e,t,n));else if(n??=``,t.startsWith(`--`))e.setProperty(t,n);else{let r=wD(e,t);bD.test(n)?e.setProperty(xE(r),n.replace(bD,``),`important`):e[r]=n}}var SD=[`Webkit`,`Moz`,`ms`],CD={};function wD(e,t){let n=CD[t];if(n)return n;let r=Jy(t);if(r!==`filter`&&r in e)return CD[t]=r;r=SE(r);for(let n=0;n<SD.length;n++){let i=SD[n]+r;if(i in e)return CD[t]=i}return t}function TD(e,t,n,r){return e.tagName===`TEXTAREA`&&(t===`width`||t===`height`)&&dE(r)&&n===r}var ED=`http://www.w3.org/1999/xlink`;function DD(e,t,n,r,i,a=DE(t)){r&&t.startsWith(`xlink:`)?n==null?e.removeAttributeNS(ED,t.slice(6,t.length)):e.setAttributeNS(ED,t,n):n==null||a&&!OE(n)?e.removeAttribute(t):e.setAttribute(t,a?``:fE(n)?String(n):n)}function OD(e,t,n,r,i){if(t===`innerHTML`||t===`textContent`){n!=null&&(e[t]=t===`innerHTML`?IE(n):n);return}let a=e.tagName;if(t===`value`&&a!==`PROGRESS`&&!a.includes(`-`)){let r=a===`OPTION`?e.getAttribute(`value`)||``:e.value,i=n==null?e.type===`checkbox`?`on`:``:String(n);(r!==i||!(`_value`in e))&&(e.value=i),n??e.removeAttribute(t),e._value=n;return}let o=!1;if(n===``||n==null){let r=typeof e[t];r===`boolean`?n=OE(n):n==null&&r===`string`?(n=``,o=!0):r===`number`&&(n=0,o=!0)}try{e[t]=n}catch{}o&&e.removeAttribute(i||t)}function kD(e,t,n,r){e.addEventListener(t,n,r)}function AD(e,t,n,r){e.removeEventListener(t,n,r)}var jD=Symbol(`_vei`);function MD(e,t,n,r,i=null){let a=e[jD]||(e[jD]={}),o=a[t];if(r&&o)o.value=r;else{let[n,s]=PD(t);r?kD(e,n,a[t]=RD(r,i),s):o&&(AD(e,n,o,s),a[t]=void 0)}}var ND=/(?:Once|Passive|Capture)$/;function PD(e){let t;if(ND.test(e)){t={};let n;for(;n=e.match(ND);)e=e.slice(0,e.length-n[0].length),t[n[0].toLowerCase()]=!0}return[e[2]===`:`?e.slice(3):xE(e.slice(2)),t]}var FD=0,ID=Promise.resolve(),LD=()=>FD||=(ID.then(()=>FD=0),Date.now());function RD(e,t){let n=e=>{if(!e._vts)e._vts=Date.now();else if(e._vts<=n.attached)return;Db(zD(e,n.value),t,5,[e])};return n.value=e,n.attached=LD(),n}function zD(e,t){if(sE(t)){let n=e.stopImmediatePropagation;return e.stopImmediatePropagation=()=>{n.call(e),e._stopped=!0},t.map(e=>t=>!t._stopped&&e&&e(t))}else return t}var BD=e=>e.charCodeAt(0)===111&&e.charCodeAt(1)===110&&e.charCodeAt(2)>96&&e.charCodeAt(2)<123,VD=(e,t,n,r,i,a)=>{let o=i===`svg`;t===`class`?cD(e,r,o):t===`style`?yD(e,n,r):nE(t)?rE(t)||MD(e,t,n,r,a):(t[0]===`.`?(t=t.slice(1),!0):t[0]===`^`?(t=t.slice(1),!1):HD(e,t,r,o))?(OD(e,t,r),!e.tagName.includes(`-`)&&(t===`value`||t===`checked`||t===`selected`)&&DD(e,t,r,o,a,t!==`value`)):e._isVueCE&&(UD(e,t)||e._def.__asyncLoader&&(/[A-Z]/.test(t)||!dE(r)))?OD(e,yE(t),r,a,t):(t===`true-value`?e._trueValue=r:t===`false-value`&&(e._falseValue=r),DD(e,t,r,o))};function HD(e,t,n,r){if(r)return!!(t===`innerHTML`||t===`textContent`||t in e&&BD(t)&&uE(n));if(t===`spellcheck`||t===`draggable`||t===`translate`||t===`autocorrect`||t===`sandbox`&&e.tagName===`IFRAME`||t===`form`||t===`list`&&e.tagName===`INPUT`||t===`type`&&e.tagName===`TEXTAREA`)return!1;if(t===`width`||t===`height`){let t=e.tagName;if(t===`IMG`||t===`VIDEO`||t===`CANVAS`||t===`SOURCE`)return!1}return BD(t)&&dE(n)?!1:t in e}function UD(e,t){let n=e._def.props;if(!n)return!1;let r=yE(t);return Array.isArray(n)?n.some(e=>yE(e)===r):Object.keys(n).some(e=>yE(e)===r)}var WD={};function GD(e,t,n){let r=Gx(e,t);gE(r)&&(r=iE({},r,t));class i extends JD{constructor(e){super(r,e,n)}}return i.def=r,i}var KD=((e,t)=>GD(e,t,RO)),qD=typeof HTMLElement<`u`?HTMLElement:class{},JD=class e extends qD{constructor(e,t={},n=LO){super(),this._def=e,this._props=t,this._createApp=n,this._isVueCE=!0,this._instance=null,this._app=null,this._nonce=this._def.nonce,this._connected=!1,this._resolved=!1,this._patching=!1,this._dirty=!1,this._numberProps=null,this._styleChildren=new WeakSet,this._styleAnchors=new WeakMap,this._ob=null,this.shadowRoot&&n!==LO?this._root=this.shadowRoot:e.shadowRoot===!1?this._root=this:(this.attachShadow(iE({},e.shadowRootOptions,{mode:`open`})),this._root=this.shadowRoot)}connectedCallback(){if(!this.isConnected)return;!this.shadowRoot&&!this._resolved&&this._parseSlots(),this._connected=!0;let t=this;for(;t&&=t.assignedSlot||t.parentNode||t.host;)if(t instanceof e){this._parent=t;break}this._instance||(this._resolved?this._mount(this._def):t&&t._pendingResolve?this._pendingResolve=t._pendingResolve.then(()=>{this._pendingResolve=void 0,this._resolveDef()}):this._resolveDef())}_setParent(e=this._parent){e&&(this._instance.parent=e._instance,this._inheritParentContext(e))}_inheritParentContext(e=this._parent){e&&this._app&&Object.setPrototypeOf(this._app._context.provides,e._instance.provides)}disconnectedCallback(){this._connected=!1,Lb(()=>{this._connected||(this._ob&&=(this._ob.disconnect(),null),this._app&&this._app.unmount(),this._instance&&(this._instance.ce=void 0),this._app=this._instance=null,this._teleportTargets&&=(this._teleportTargets.clear(),void 0))})}_processMutations(e){for(let t of e)this._setAttr(t.attributeName)}_resolveDef(){if(this._pendingResolve)return;for(let e=0;e<this.attributes.length;e++)this._setAttr(this.attributes[e].name);this._ob=new MutationObserver(this._processMutations.bind(this)),this._ob.observe(this,{attributes:!0});let e=(e,t=!1)=>{this._resolved=!0,this._pendingResolve=void 0;let{props:n,styles:r}=e,i;if(n&&!sE(n))for(let e in n){let t=n[e];(t===Number||t&&t.type===Number)&&(e in this._props&&(this._props[e]=TE(this._props[e])),(i||=Object.create(null))[yE(e)]=!0)}this._numberProps=i,this._resolveProps(e),this.shadowRoot&&this._applyStyles(r),this._mount(e)},t=this._def.__asyncLoader;t?this._pendingResolve=t().then(t=>{t.configureApp=this._def.configureApp,e(this._def=t,!0)}):e(this._def)}_mount(e){this._app=this._createApp(e),this._inheritParentContext(),e.configureApp&&e.configureApp(this._app),this._app._ceVNode=this._createVNode(),this._app.mount(this._root);let t=this._instance&&this._instance.exposed;if(t)for(let e in t)oE(this,e)||Object.defineProperty(this,e,{get:()=>ny(t[e])})}_resolveProps(e){let{props:t}=e,n=sE(t)?t:Object.keys(t||{});for(let e of Object.keys(this))e[0]!==`_`&&n.includes(e)&&this._setProp(e,this[e]);for(let e of n.map(yE))Object.defineProperty(this,e,{get(){return this._getProp(e)},set(t){this._setProp(e,t,!0,!this._patching)}})}_setAttr(e){if(e.startsWith(`data-v-`))return;let t=this.hasAttribute(e),n=t?this.getAttribute(e):WD,r=yE(e);t&&this._numberProps&&this._numberProps[r]&&(n=TE(n)),this._setProp(r,n,!1,!0)}_getProp(e){return this._props[e]}_setProp(e,t,n=!0,r=!1){if(t!==this._props[e]&&(this._dirty=!0,t===WD?delete this._props[e]:(this._props[e]=t,e===`key`&&this._app&&(this._app._ceVNode.key=t)),r&&this._instance&&this._update(),n)){let n=this._ob;n&&(this._processMutations(n.takeRecords()),n.disconnect()),t===!0?this.setAttribute(xE(e),``):typeof t==`string`||typeof t==`number`?this.setAttribute(xE(e),t+``):t||this.removeAttribute(xE(e)),n&&n.observe(this,{attributes:!0})}}_update(){let e=this._createVNode();this._app&&(e.appContext=this._app._context),FO(e,this._root)}_createVNode(){let e={};this.shadowRoot||(e.onVnodeMounted=e.onVnodeUpdated=this._renderSlots.bind(this));let t=sT(this._def,iE(e,this._props));return this._instance||(t.ce=e=>{this._instance=e,e.ce=this,e.isCE=!0;let t=(e,t)=>{this.dispatchEvent(new CustomEvent(e,gE(t[0])?iE({detail:t},t[0]):{detail:t}))};e.emit=(e,...n)=>{t(e,n),xE(e)!==e&&t(xE(e),n)},this._setParent()}),t}_applyStyles(e,t,n){if(!e)return;if(t){if(t===this._def||this._styleChildren.has(t))return;this._styleChildren.add(t)}let r=this._nonce,i=this.shadowRoot,a=n?this._getStyleAnchor(n)||this._getStyleAnchor(this._def):this._getRootStyleInsertionAnchor(i),o=null;for(let s=e.length-1;s>=0;s--){let c=document.createElement(`style`);r&&c.setAttribute(`nonce`,r),c.textContent=e[s],i.insertBefore(c,o||a),o=c,s===0&&(n||this._styleAnchors.set(this._def,c),t&&this._styleAnchors.set(t,c))}}_getStyleAnchor(e){if(!e)return null;let t=this._styleAnchors.get(e);return t&&t.parentNode===this.shadowRoot?t:(t&&this._styleAnchors.delete(e),null)}_getRootStyleInsertionAnchor(e){for(let t=0;t<e.childNodes.length;t++){let n=e.childNodes[t];if(!(n instanceof HTMLStyleElement))return n}return null}_parseSlots(){let e=this._slots={},t;for(;t=this.firstChild;){let n=t.nodeType===1&&t.getAttribute(`slot`)||`default`;(e[n]||(e[n]=[])).push(t),this.removeChild(t)}}_renderSlots(){let e=this._getSlots(),t=this._instance.type.__scopeId;for(let n=0;n<e.length;n++){let r=e[n],i=r.getAttribute(`name`)||`default`,a=this._slots[i],o=r.parentNode;if(a)for(let e of a){if(t&&e.nodeType===1){let n=t+`-s`,r=document.createTreeWalker(e,1);e.setAttribute(n,``);let i;for(;i=r.nextNode();)i.setAttribute(n,``)}o.insertBefore(e,r)}else for(;r.firstChild;)o.insertBefore(r.firstChild,r);o.removeChild(r)}}_getSlots(){let e=[this];this._teleportTargets&&e.push(...this._teleportTargets);let t=new Set;for(let n of e){let e=n.querySelectorAll(`slot`);for(let n=0;n<e.length;n++)t.add(e[n])}return Array.from(t)}_injectChildStyle(e,t){this._applyStyles(e.styles,e,t)}_beginPatch(){this._patching=!0,this._dirty=!1}_endPatch(){this._patching=!1,this._dirty&&this._instance&&this._update()}_hasShadowRoot(){return this._def.shadowRoot!==!1}_removeChildStyle(e){}};function YD(e){let t=CT();return t&&t.ce||null}function XD(){let e=YD();return e&&e.shadowRoot}function ZD(e=`$style`){{let t=CT();if(!t)return eE;let n=t.type.__cssModules;return n&&n[e]||eE}}var QD=new WeakMap,$D=new WeakMap,eO=Symbol(`_moveCb`),tO=Symbol(`_enterCb`),nO=(e=>(delete e.props.mode,e))({name:`TransitionGroup`,props:iE({},KE,{tag:String,moveClass:String}),setup(e,{slots:t}){let n=CT(),r=Mx(),i,a;return PS(()=>{if(!i.length)return;let t=e.moveClass||`${e.name||`v`}-move`;if(!sO(i[0].el,n.vnode.el,t)){i=[];return}i.forEach(rO),i.forEach(iO);let r=i.filter(aO);sD(n.vnode.el),r.forEach(e=>{let n=e.el,r=n.style;$E(n,t),r.transform=r.webkitTransform=r.transitionDuration=``;let i=n[eO]=e=>{e&&e.target!==n||(!e||e.propertyName.endsWith(`transform`))&&(n.removeEventListener(`transitionend`,i),n[eO]=null,eD(n,t))};n.addEventListener(`transitionend`,i)}),i=[]}),()=>{let o=G(e),s=XE(o),c=o.tag||Hw;if(i=[],a)for(let e=0;e<a.length;e++){let t=a[e];t.el&&t.el instanceof Element&&(i.push(t),Ux(t,Bx(t,s,r,n)),QD.set(t,oO(t.el)))}a=t.default?Wx(t.default()):[];for(let e=0;e<a.length;e++){let t=a[e];t.key!=null&&Ux(t,Bx(t,s,r,n))}return sT(c,null,a)}}});function rO(e){let t=e.el;t[eO]&&t[eO](),t[tO]&&t[tO]()}function iO(e){$D.set(e,oO(e.el))}function aO(e){let t=QD.get(e),n=$D.get(e),r=t.left-n.left,i=t.top-n.top;if(r||i){let t=e.el,n=t.style,a=t.getBoundingClientRect(),o=1,s=1;return t.offsetWidth&&(o=a.width/t.offsetWidth),t.offsetHeight&&(s=a.height/t.offsetHeight),(!Number.isFinite(o)||o===0)&&(o=1),(!Number.isFinite(s)||s===0)&&(s=1),Math.abs(o-1)<.01&&(o=1),Math.abs(s-1)<.01&&(s=1),n.transform=n.webkitTransform=`translate(${r/o}px,${i/s}px)`,n.transitionDuration=`0s`,e}}function oO(e){let t=e.getBoundingClientRect();return{left:t.left,top:t.top}}function sO(e,t,n){let r=e.cloneNode(),i=e[WE];i&&i.forEach(e=>{e.split(/\s+/).forEach(e=>e&&r.classList.remove(e))}),n.split(/\s+/).forEach(e=>e&&r.classList.add(e)),r.style.display=`none`;let a=t.nodeType===1?t:t.parentNode;a.appendChild(r);let{hasTransform:o}=iD(r);return a.removeChild(r),o}var cO=e=>{let t=e.props[`onUpdate:modelValue`]||!1;return sE(t)?e=>CE(t,e):t};function lO(e){e.target.composing=!0}function uO(e){let t=e.target;t.composing&&(t.composing=!1,t.dispatchEvent(new Event(`input`)))}var dO=Symbol(`_assign`);function fO(e,t,n){return t&&(e=e.trim()),n&&(e=wE(e)),e}var pO={created(e,{modifiers:{lazy:t,trim:n,number:r}},i){e[dO]=cO(i);let a=r||i.props&&i.props.type===`number`;kD(e,t?`change`:`input`,t=>{t.target.composing||e[dO](fO(e.value,n,a))}),(n||a)&&kD(e,`change`,()=>{e.value=fO(e.value,n,a)}),t||(kD(e,`compositionstart`,lO),kD(e,`compositionend`,uO),kD(e,`change`,uO))},mounted(e,{value:t}){e.value=t??``},beforeUpdate(e,{value:t,oldValue:n,modifiers:{lazy:r,trim:i,number:a}},o){if(e[dO]=cO(o),e.composing)return;let s=(a||e.type===`number`)&&!/^0\d/.test(e.value)?wE(e.value):e.value,c=t??``;if(s===c)return;let l=e.getRootNode();(l instanceof Document||l instanceof ShadowRoot)&&l.activeElement===e&&e.type!==`range`&&(r&&t===n||i&&e.value.trim()===c)||(e.value=c)}},mO={deep:!0,created(e,t,n){e[dO]=cO(n),kD(e,`change`,()=>{let t=e._modelValue,n=yO(e),r=e.checked,i=e[dO];if(sE(t)){let e=jE(t,n),a=e!==-1;if(r&&!a)i(t.concat(n));else if(!r&&a){let n=[...t];n.splice(e,1),i(n)}}else if(cE(t)){let e=new Set(t);r?e.add(n):e.delete(n),i(e)}else i(bO(e,r))})},mounted:hO,beforeUpdate(e,t,n){e[dO]=cO(n),hO(e,t,n)}};function hO(e,{value:t,oldValue:n},r){e._modelValue=t;let i;if(sE(t))i=jE(t,r.props.value)>-1;else if(cE(t))i=t.has(r.props.value);else{if(t===n)return;i=AE(t,bO(e,!0))}e.checked!==i&&(e.checked=i)}var gO={created(e,{value:t},n){e.checked=AE(t,n.props.value),e[dO]=cO(n),kD(e,`change`,()=>{e[dO](yO(e))})},beforeUpdate(e,{value:t,oldValue:n},r){e[dO]=cO(r),t!==n&&(e.checked=AE(t,r.props.value))}},_O={deep:!0,created(e,{value:t,modifiers:{number:n}},r){let i=cE(t);kD(e,`change`,()=>{let t=Array.prototype.filter.call(e.options,e=>e.selected).map(e=>n?wE(yO(e)):yO(e));e[dO](e.multiple?i?new Set(t):t:t[0]),e._assigning=!0,Lb(()=>{e._assigning=!1})}),e[dO]=cO(r)},mounted(e,{value:t}){vO(e,t)},beforeUpdate(e,t,n){e[dO]=cO(n)},updated(e,{value:t}){e._assigning||vO(e,t)}};function vO(e,t){let n=e.multiple,r=sE(t);if(!(n&&!r&&!cE(t))){for(let i=0,a=e.options.length;i<a;i++){let a=e.options[i],o=yO(a);if(n)if(r){let e=typeof o;e===`string`||e===`number`?a.selected=t.some(e=>String(e)===String(o)):a.selected=jE(t,o)>-1}else a.selected=t.has(o);else if(AE(yO(a),t)){e.selectedIndex!==i&&(e.selectedIndex=i);return}}!n&&e.selectedIndex!==-1&&(e.selectedIndex=-1)}}function yO(e){return`_value`in e?e._value:e.value}function bO(e,t){let n=t?`_trueValue`:`_falseValue`;return n in e?e[n]:t}var xO={created(e,t,n){CO(e,t,n,null,`created`)},mounted(e,t,n){CO(e,t,n,null,`mounted`)},beforeUpdate(e,t,n,r){CO(e,t,n,r,`beforeUpdate`)},updated(e,t,n,r){CO(e,t,n,r,`updated`)}};function SO(e,t){switch(e){case`SELECT`:return _O;case`TEXTAREA`:return pO;default:switch(t){case`checkbox`:return mO;case`radio`:return gO;default:return pO}}}function CO(e,t,n,r,i){let a=SO(e.tagName,n.props&&n.props.type)[i];a&&a(e,t,n,r)}function wO(){pO.getSSRProps=({value:e})=>({value:e}),gO.getSSRProps=({value:e},t)=>{if(t.props&&AE(t.props.value,e))return{checked:!0}},mO.getSSRProps=({value:e},t)=>{if(sE(e)){if(t.props&&jE(e,t.props.value)>-1)return{checked:!0}}else if(cE(e)){if(t.props&&e.has(t.props.value))return{checked:!0}}else if(e)return{checked:!0}},xO.getSSRProps=(e,t)=>{if(typeof t.type!=`string`)return;let n=SO(t.type.toUpperCase(),t.props&&t.props.type);if(n.getSSRProps)return n.getSSRProps(e,t)}}var TO=[`ctrl`,`shift`,`alt`,`meta`],EO={stop:e=>e.stopPropagation(),prevent:e=>e.preventDefault(),self:e=>e.target!==e.currentTarget,ctrl:e=>!e.ctrlKey,shift:e=>!e.shiftKey,alt:e=>!e.altKey,meta:e=>!e.metaKey,left:e=>`button`in e&&e.button!==0,middle:e=>`button`in e&&e.button!==1,right:e=>`button`in e&&e.button!==2,exact:(e,t)=>TO.some(n=>e[`${n}Key`]&&!t.includes(n))},DO=(e,t)=>{if(!e)return e;let n=e._withMods||={},r=t.join(`.`);return n[r]||(n[r]=((n,...r)=>{for(let e=0;e<t.length;e++){let r=EO[t[e]];if(r&&r(n,t))return}return e(n,...r)}))},OO={esc:`escape`,space:` `,up:`arrow-up`,left:`arrow-left`,right:`arrow-right`,down:`arrow-down`,delete:`backspace`},kO=(e,t)=>{let n=e._withKeys||={},r=t.join(`.`);return n[r]||(n[r]=(n=>{if(!(`key`in n))return;let r=xE(n.key);if(t.some(e=>e===r||OO[e]===r))return e(n)}))},AO=iE({patchProp:VD},VE),jO,MO=!1;function NO(){return jO||=vw(AO)}function PO(){return jO=MO?jO:yw(AO),MO=!0,jO}var FO=((...e)=>{NO().render(...e)}),IO=((...e)=>{PO().hydrate(...e)}),LO=((...e)=>{let t=NO().createApp(...e),{mount:n}=t;return t.mount=e=>{let r=BO(e);if(!r)return;let i=t._component;!uE(i)&&!i.render&&!i.template&&(i.template=r.innerHTML),r.nodeType===1&&(r.textContent=``);let a=n(r,!1,zO(r));return r instanceof Element&&(r.removeAttribute(`v-cloak`),r.setAttribute(`data-v-app`,``)),a},t}),RO=((...e)=>{let t=PO().createApp(...e),{mount:n}=t;return t.mount=e=>{let t=BO(e);if(t)return n(t,!0,zO(t))},t});function zO(e){if(e instanceof SVGElement)return`svg`;if(typeof MathMLElement==`function`&&e instanceof MathMLElement)return`mathml`}function BO(e){return dE(e)?document.querySelector(e):e}var VO=!1,HO=()=>{VO||(VO=!0,wO(),pD())};function UO(e){let t=Object.create(null);for(let n of e.split(`,`))t[n]=1;return e=>e in t}var WO={},GO=()=>{},KO=()=>!1,qO=e=>e.charCodeAt(0)===111&&e.charCodeAt(1)===110&&(e.charCodeAt(2)>122||e.charCodeAt(2)<97),JO=Object.assign,YO=Array.isArray,XO=e=>typeof e==`string`,ZO=e=>typeof e==`symbol`,QO=e=>typeof e==`object`&&!!e,$O=UO(`,key,ref,ref_for,ref_key,onVnodeBeforeMount,onVnodeMounted,onVnodeBeforeUpdate,onVnodeUpdated,onVnodeBeforeUnmount,onVnodeUnmounted`),ek=UO(`bind,cloak,else-if,else,for,html,if,model,on,once,pre,show,slot,text,memo`),tk=e=>{let t=Object.create(null);return(n=>t[n]||(t[n]=e(n)))},nk=/-\w/g,rk=tk(e=>e.replace(nk,e=>e.slice(1).toUpperCase())),ik=tk(e=>e.charAt(0).toUpperCase()+e.slice(1)),ak=tk(e=>e?`on${ik(e)}`:``);function ok(e,t){return e+JSON.stringify(t,(e,t)=>typeof t==`function`?t.toString():t)}var sk=/;(?![^(]*\))/g,ck=/:([^]+)/,lk=/\/\*[^]*?\*\//g;function uk(e){let t={};return e.replace(lk,``).split(sk).forEach(e=>{if(e){let n=e.split(ck);n.length>1&&(t[n[0].trim()]=n[1].trim())}}),t}var dk=`html,body,base,head,link,meta,style,title,address,article,aside,footer,header,hgroup,h1,h2,h3,h4,h5,h6,nav,section,div,dd,dl,dt,figcaption,figure,picture,hr,img,li,main,ol,p,pre,ul,a,b,abbr,bdi,bdo,br,cite,code,data,dfn,em,i,kbd,mark,q,rp,rt,ruby,s,samp,small,span,strong,sub,sup,time,u,var,wbr,area,audio,map,track,video,embed,object,param,source,canvas,script,noscript,del,ins,caption,col,colgroup,table,thead,tbody,td,th,tr,button,datalist,fieldset,form,input,label,legend,meter,optgroup,option,output,progress,select,textarea,details,dialog,menu,summary,template,blockquote,iframe,tfoot`,fk=`svg,animate,animateMotion,animateTransform,circle,clipPath,color-profile,defs,desc,discard,ellipse,feBlend,feColorMatrix,feComponentTransfer,feComposite,feConvolveMatrix,feDiffuseLighting,feDisplacementMap,feDistantLight,feDropShadow,feFlood,feFuncA,feFuncB,feFuncG,feFuncR,feGaussianBlur,feImage,feMerge,feMergeNode,feMorphology,feOffset,fePointLight,feSpecularLighting,feSpotLight,feTile,feTurbulence,filter,foreignObject,g,hatch,hatchpath,image,line,linearGradient,marker,mask,mesh,meshgradient,meshpatch,meshrow,metadata,mpath,path,pattern,polygon,polyline,radialGradient,rect,set,solidcolor,stop,switch,symbol,text,textPath,title,tspan,unknown,use,view`,pk=`annotation,annotation-xml,maction,maligngroup,malignmark,math,menclose,merror,mfenced,mfrac,mfraction,mglyph,mi,mlabeledtr,mlongdiv,mmultiscripts,mn,mo,mover,mpadded,mphantom,mprescripts,mroot,mrow,ms,mscarries,mscarry,msgroup,msline,mspace,msqrt,msrow,mstack,mstyle,msub,msubsup,msup,mtable,mtd,mtext,mtr,munder,munderover,none,semantics`,mk=`area,base,br,col,embed,hr,img,input,link,meta,param,source,track,wbr`,hk=UO(dk),gk=UO(fk),_k=UO(pk),vk=UO(mk),yk=Symbol(``),bk=Symbol(``),xk=Symbol(``),Sk=Symbol(``),Ck=Symbol(``),wk=Symbol(``),Tk=Symbol(``),Ek=Symbol(``),Dk=Symbol(``),Ok=Symbol(``),kk=Symbol(``),Ak=Symbol(``),jk=Symbol(``),Mk=Symbol(``),Nk=Symbol(``),Pk=Symbol(``),Fk=Symbol(``),Ik=Symbol(``),Lk=Symbol(``),Rk=Symbol(``),zk=Symbol(``),Bk=Symbol(``),Vk=Symbol(``),Hk=Symbol(``),Uk=Symbol(``),Wk=Symbol(``),Gk=Symbol(``),Kk=Symbol(``),qk=Symbol(``),Jk=Symbol(``),Yk=Symbol(``),Xk=Symbol(``),Zk=Symbol(``),Qk=Symbol(``),$k=Symbol(``),eA=Symbol(``),tA=Symbol(``),nA=Symbol(``),rA=Symbol(``),iA={[yk]:`Fragment`,[bk]:`Teleport`,[xk]:`Suspense`,[Sk]:`KeepAlive`,[Ck]:`BaseTransition`,[wk]:`openBlock`,[Tk]:`createBlock`,[Ek]:`createElementBlock`,[Dk]:`createVNode`,[Ok]:`createElementVNode`,[kk]:`createCommentVNode`,[Ak]:`createTextVNode`,[jk]:`createStaticVNode`,[Mk]:`resolveComponent`,[Nk]:`resolveDynamicComponent`,[Pk]:`resolveDirective`,[Fk]:`resolveFilter`,[Ik]:`withDirectives`,[Lk]:`renderList`,[Rk]:`renderSlot`,[zk]:`createSlots`,[Bk]:`toDisplayString`,[Vk]:`mergeProps`,[Hk]:`normalizeClass`,[Uk]:`normalizeStyle`,[Wk]:`normalizeProps`,[Gk]:`guardReactiveProps`,[Kk]:`toHandlers`,[qk]:`camelize`,[Jk]:`capitalize`,[Yk]:`toHandlerKey`,[Xk]:`setBlockTracking`,[Zk]:`pushScopeId`,[Qk]:`popScopeId`,[$k]:`withCtx`,[eA]:`unref`,[tA]:`isRef`,[nA]:`withMemo`,[rA]:`isMemoSame`};function aA(e){Object.getOwnPropertySymbols(e).forEach(t=>{iA[t]=e[t]})}var oA={start:{line:1,column:1,offset:0},end:{line:1,column:1,offset:0},source:``};function sA(e,t=``){return{type:0,source:t,children:e,helpers:new Set,components:[],directives:[],hoists:[],imports:[],cached:[],temps:0,codegenNode:void 0,loc:oA}}function cA(e,t,n,r,i,a,o,s=!1,c=!1,l=!1,u=oA){return e&&(s?(e.helper(wk),e.helper(yA(e.inSSR,l))):e.helper(vA(e.inSSR,l)),o&&e.helper(Ik)),{type:13,tag:t,props:n,children:r,patchFlag:i,dynamicProps:a,directives:o,isBlock:s,disableTracking:c,isComponent:l,loc:u}}function lA(e,t=oA){return{type:17,loc:t,elements:e}}function uA(e,t=oA){return{type:15,loc:t,properties:e}}function dA(e,t){return{type:16,loc:oA,key:XO(e)?X(e,!0):e,value:t}}function X(e,t=!1,n=oA,r=0){return{type:4,loc:n,content:e,isStatic:t,constType:t?3:r}}function fA(e,t=oA){return{type:8,loc:t,children:e}}function pA(e,t=[],n=oA){return{type:14,loc:n,callee:e,arguments:t}}function mA(e,t=void 0,n=!1,r=!1,i=oA){return{type:18,params:e,returns:t,newline:n,isSlot:r,loc:i}}function hA(e,t,n,r=!0){return{type:19,test:e,consequent:t,alternate:n,newline:r,loc:oA}}function gA(e,t,n=!1,r=!1){return{type:20,index:e,value:t,needPauseTracking:n,inVOnce:r,needArraySpread:!1,loc:oA}}function _A(e){return{type:21,body:e,loc:oA}}function vA(e,t){return e||t?Dk:Ok}function yA(e,t){return e||t?Tk:Ek}function bA(e,{helper:t,removeHelper:n,inSSR:r}){e.isBlock||(e.isBlock=!0,n(vA(r,e.isComponent)),t(wk),t(yA(r,e.isComponent)))}var xA=new Uint8Array([123,123]),SA=new Uint8Array([125,125]);function CA(e){return e>=97&&e<=122||e>=65&&e<=90}function wA(e){return e===32||e===10||e===9||e===12||e===13}function TA(e){return e===47||e===62||wA(e)}function EA(e){let t=new Uint8Array(e.length);for(let n=0;n<e.length;n++)t[n]=e.charCodeAt(n);return t}var DA={Cdata:new Uint8Array([67,68,65,84,65,91]),CdataEnd:new Uint8Array([93,93,62]),CommentEnd:new Uint8Array([45,45,62]),ScriptEnd:new Uint8Array([60,47,115,99,114,105,112,116]),StyleEnd:new Uint8Array([60,47,115,116,121,108,101]),TitleEnd:new Uint8Array([60,47,116,105,116,108,101]),TextareaEnd:new Uint8Array([60,47,116,101,120,116,97,114,101,97])},OA=class{constructor(e,t){this.stack=e,this.cbs=t,this.state=1,this.buffer=``,this.sectionStart=0,this.index=0,this.entityStart=0,this.baseState=1,this.inRCDATA=!1,this.inXML=!1,this.inVPre=!1,this.newlines=[],this.mode=0,this.delimiterOpen=xA,this.delimiterClose=SA,this.delimiterIndex=-1,this.currentSequence=void 0,this.sequenceIndex=0}get inSFCRoot(){return this.mode===2&&this.stack.length===0}reset(){this.state=1,this.mode=0,this.buffer=``,this.sectionStart=0,this.index=0,this.baseState=1,this.inRCDATA=!1,this.currentSequence=void 0,this.newlines.length=0,this.delimiterOpen=xA,this.delimiterClose=SA}getPos(e){let t=1,n=e+1,r=this.newlines.length,i=-1;if(r>100){let t=-1,n=r;for(;t+1<n;){let r=t+n>>>1;this.newlines[r]<e?t=r:n=r}i=t}else for(let t=r-1;t>=0;t--)if(e>this.newlines[t]){i=t;break}return i>=0&&(t=i+2,n=e-this.newlines[i]),{column:n,line:t,offset:e}}peek(){return this.buffer.charCodeAt(this.index+1)}stateText(e){e===60?(this.index>this.sectionStart&&this.cbs.ontext(this.sectionStart,this.index),this.state=5,this.sectionStart=this.index):!this.inVPre&&e===this.delimiterOpen[0]&&(this.state=2,this.delimiterIndex=0,this.stateInterpolationOpen(e))}stateInterpolationOpen(e){if(e===this.delimiterOpen[this.delimiterIndex])if(this.delimiterIndex===this.delimiterOpen.length-1){let e=this.index+1-this.delimiterOpen.length;e>this.sectionStart&&this.cbs.ontext(this.sectionStart,e),this.state=3,this.sectionStart=e}else this.delimiterIndex++;else this.inRCDATA?(this.state=32,this.stateInRCDATA(e)):(this.state=1,this.stateText(e))}stateInterpolation(e){e===this.delimiterClose[0]&&(this.state=4,this.delimiterIndex=0,this.stateInterpolationClose(e))}stateInterpolationClose(e){e===this.delimiterClose[this.delimiterIndex]?this.delimiterIndex===this.delimiterClose.length-1?(this.cbs.oninterpolation(this.sectionStart,this.index+1),this.inRCDATA?this.state=32:this.state=1,this.sectionStart=this.index+1):this.delimiterIndex++:(this.state=3,this.stateInterpolation(e))}stateSpecialStartSequence(e){let t=this.sequenceIndex===this.currentSequence.length;if(!(t?TA(e):(e|32)===this.currentSequence[this.sequenceIndex]))this.inRCDATA=!1;else if(!t){this.sequenceIndex++;return}this.sequenceIndex=0,this.state=6,this.stateInTagName(e)}stateInRCDATA(e){if(this.sequenceIndex===this.currentSequence.length){if(e===62||wA(e)){let t=this.index-this.currentSequence.length;if(this.sectionStart<t){let e=this.index;this.index=t,this.cbs.ontext(this.sectionStart,t),this.index=e}this.sectionStart=t+2,this.stateInClosingTagName(e),this.inRCDATA=!1;return}this.sequenceIndex=0}(e|32)===this.currentSequence[this.sequenceIndex]?this.sequenceIndex+=1:this.sequenceIndex===0?this.currentSequence===DA.TitleEnd||this.currentSequence===DA.TextareaEnd&&!this.inSFCRoot?!this.inVPre&&e===this.delimiterOpen[0]&&(this.state=2,this.delimiterIndex=0,this.stateInterpolationOpen(e)):this.fastForwardTo(60)&&(this.sequenceIndex=1):this.sequenceIndex=Number(e===60)}stateCDATASequence(e){e===DA.Cdata[this.sequenceIndex]?++this.sequenceIndex===DA.Cdata.length&&(this.state=28,this.currentSequence=DA.CdataEnd,this.sequenceIndex=0,this.sectionStart=this.index+1):(this.sequenceIndex=0,this.state=23,this.stateInDeclaration(e))}fastForwardTo(e){for(;++this.index<this.buffer.length;){let t=this.buffer.charCodeAt(this.index);if(t===10&&this.newlines.push(this.index),t===e)return!0}return this.index=this.buffer.length-1,!1}stateInCommentLike(e){e===this.currentSequence[this.sequenceIndex]?++this.sequenceIndex===this.currentSequence.length&&(this.currentSequence===DA.CdataEnd?this.cbs.oncdata(this.sectionStart,this.index-2):this.cbs.oncomment(this.sectionStart,this.index-2),this.sequenceIndex=0,this.sectionStart=this.index+1,this.state=1):this.sequenceIndex===0?this.fastForwardTo(this.currentSequence[0])&&(this.sequenceIndex=1):e!==this.currentSequence[this.sequenceIndex-1]&&(this.sequenceIndex=0)}startSpecial(e,t){this.enterRCDATA(e,t),this.state=31}enterRCDATA(e,t){this.inRCDATA=!0,this.currentSequence=e,this.sequenceIndex=t}stateBeforeTagName(e){e===33?(this.state=22,this.sectionStart=this.index+1):e===63?(this.state=24,this.sectionStart=this.index+1):CA(e)?(this.sectionStart=this.index,this.mode===0?this.state=6:this.inSFCRoot?this.state=34:this.inXML?this.state=6:e===116?this.state=30:this.state=e===115?29:6):e===47?this.state=8:(this.state=1,this.stateText(e))}stateInTagName(e){TA(e)&&this.handleTagName(e)}stateInSFCRootTagName(e){if(TA(e)){let t=this.buffer.slice(this.sectionStart,this.index);t!==`template`&&this.enterRCDATA(EA(`</`+t),0),this.handleTagName(e)}}handleTagName(e){this.cbs.onopentagname(this.sectionStart,this.index),this.sectionStart=-1,this.state=11,this.stateBeforeAttrName(e)}stateBeforeClosingTagName(e){wA(e)||(e===62?(this.state=1,this.sectionStart=this.index+1):(this.state=CA(e)?9:27,this.sectionStart=this.index))}stateInClosingTagName(e){(e===62||wA(e))&&(this.cbs.onclosetag(this.sectionStart,this.index),this.sectionStart=-1,this.state=10,this.stateAfterClosingTagName(e))}stateAfterClosingTagName(e){e===62&&(this.state=1,this.sectionStart=this.index+1)}stateBeforeAttrName(e){e===62?(this.cbs.onopentagend(this.index),this.inRCDATA?this.state=32:this.state=1,this.sectionStart=this.index+1):e===47?this.state=7:e===60&&this.peek()===47?(this.cbs.onopentagend(this.index),this.state=5,this.sectionStart=this.index):wA(e)||this.handleAttrStart(e)}handleAttrStart(e){e===118&&this.peek()===45?(this.state=13,this.sectionStart=this.index):e===46||e===58||e===64||e===35?(this.cbs.ondirname(this.index,this.index+1),this.state=14,this.sectionStart=this.index+1):(this.state=12,this.sectionStart=this.index)}stateInSelfClosingTag(e){e===62?(this.cbs.onselfclosingtag(this.index),this.state=1,this.sectionStart=this.index+1,this.inRCDATA=!1):wA(e)||(this.state=11,this.stateBeforeAttrName(e))}stateInAttrName(e){(e===61||TA(e))&&(this.cbs.onattribname(this.sectionStart,this.index),this.handleAttrNameEnd(e))}stateInDirName(e){e===61||TA(e)?(this.cbs.ondirname(this.sectionStart,this.index),this.handleAttrNameEnd(e)):e===58?(this.cbs.ondirname(this.sectionStart,this.index),this.state=14,this.sectionStart=this.index+1):e===46&&(this.cbs.ondirname(this.sectionStart,this.index),this.state=16,this.sectionStart=this.index+1)}stateInDirArg(e){e===61||TA(e)?(this.cbs.ondirarg(this.sectionStart,this.index),this.handleAttrNameEnd(e)):e===91?this.state=15:e===46&&(this.cbs.ondirarg(this.sectionStart,this.index),this.state=16,this.sectionStart=this.index+1)}stateInDynamicDirArg(e){e===93?this.state=14:(e===61||TA(e))&&(this.cbs.ondirarg(this.sectionStart,this.index+1),this.handleAttrNameEnd(e))}stateInDirModifier(e){e===61||TA(e)?(this.cbs.ondirmodifier(this.sectionStart,this.index),this.handleAttrNameEnd(e)):e===46&&(this.cbs.ondirmodifier(this.sectionStart,this.index),this.sectionStart=this.index+1)}handleAttrNameEnd(e){this.sectionStart=this.index,this.state=17,this.cbs.onattribnameend(this.index),this.stateAfterAttrName(e)}stateAfterAttrName(e){e===61?this.state=18:e===47||e===62?(this.cbs.onattribend(0,this.sectionStart),this.sectionStart=-1,this.state=11,this.stateBeforeAttrName(e)):wA(e)||(this.cbs.onattribend(0,this.sectionStart),this.handleAttrStart(e))}stateBeforeAttrValue(e){e===34?(this.state=19,this.sectionStart=this.index+1):e===39?(this.state=20,this.sectionStart=this.index+1):wA(e)||(this.sectionStart=this.index,this.state=21,this.stateInAttrValueNoQuotes(e))}handleInAttrValue(e,t){(e===t||this.fastForwardTo(t))&&(this.cbs.onattribdata(this.sectionStart,this.index),this.sectionStart=-1,this.cbs.onattribend(t===34?3:2,this.index+1),this.state=11)}stateInAttrValueDoubleQuotes(e){this.handleInAttrValue(e,34)}stateInAttrValueSingleQuotes(e){this.handleInAttrValue(e,39)}stateInAttrValueNoQuotes(e){wA(e)||e===62?(this.cbs.onattribdata(this.sectionStart,this.index),this.sectionStart=-1,this.cbs.onattribend(1,this.index),this.state=11,this.stateBeforeAttrName(e)):(e===39||e===60||e===61||e===96)&&this.cbs.onerr(18,this.index)}stateBeforeDeclaration(e){e===91?(this.state=26,this.sequenceIndex=0):this.state=e===45?25:23}stateInDeclaration(e){(e===62||this.fastForwardTo(62))&&(this.state=1,this.sectionStart=this.index+1)}stateInProcessingInstruction(e){(e===62||this.fastForwardTo(62))&&(this.cbs.onprocessinginstruction(this.sectionStart,this.index),this.state=1,this.sectionStart=this.index+1)}stateBeforeComment(e){e===45?(this.state=28,this.currentSequence=DA.CommentEnd,this.sequenceIndex=2,this.sectionStart=this.index+1):this.state=23}stateInSpecialComment(e){(e===62||this.fastForwardTo(62))&&(this.cbs.oncomment(this.sectionStart,this.index),this.state=1,this.sectionStart=this.index+1)}stateBeforeSpecialS(e){e===DA.ScriptEnd[3]?this.startSpecial(DA.ScriptEnd,4):e===DA.StyleEnd[3]?this.startSpecial(DA.StyleEnd,4):(this.state=6,this.stateInTagName(e))}stateBeforeSpecialT(e){e===DA.TitleEnd[3]?this.startSpecial(DA.TitleEnd,4):e===DA.TextareaEnd[3]?this.startSpecial(DA.TextareaEnd,4):(this.state=6,this.stateInTagName(e))}startEntity(){}stateInEntity(){}parse(e){for(this.buffer=e;this.index<this.buffer.length;){let e=this.buffer.charCodeAt(this.index);switch(e===10&&this.state!==33&&this.newlines.push(this.index),this.state){case 1:this.stateText(e);break;case 2:this.stateInterpolationOpen(e);break;case 3:this.stateInterpolation(e);break;case 4:this.stateInterpolationClose(e);break;case 31:this.stateSpecialStartSequence(e);break;case 32:this.stateInRCDATA(e);break;case 26:this.stateCDATASequence(e);break;case 19:this.stateInAttrValueDoubleQuotes(e);break;case 12:this.stateInAttrName(e);break;case 13:this.stateInDirName(e);break;case 14:this.stateInDirArg(e);break;case 15:this.stateInDynamicDirArg(e);break;case 16:this.stateInDirModifier(e);break;case 28:this.stateInCommentLike(e);break;case 27:this.stateInSpecialComment(e);break;case 11:this.stateBeforeAttrName(e);break;case 6:this.stateInTagName(e);break;case 34:this.stateInSFCRootTagName(e);break;case 9:this.stateInClosingTagName(e);break;case 5:this.stateBeforeTagName(e);break;case 17:this.stateAfterAttrName(e);break;case 20:this.stateInAttrValueSingleQuotes(e);break;case 18:this.stateBeforeAttrValue(e);break;case 8:this.stateBeforeClosingTagName(e);break;case 10:this.stateAfterClosingTagName(e);break;case 29:this.stateBeforeSpecialS(e);break;case 30:this.stateBeforeSpecialT(e);break;case 21:this.stateInAttrValueNoQuotes(e);break;case 7:this.stateInSelfClosingTag(e);break;case 23:this.stateInDeclaration(e);break;case 22:this.stateBeforeDeclaration(e);break;case 25:this.stateBeforeComment(e);break;case 24:this.stateInProcessingInstruction(e);break;case 33:this.stateInEntity();break}this.index++}this.cleanup(),this.finish()}cleanup(){this.sectionStart!==this.index&&(this.state===1||this.state===32&&this.sequenceIndex===0?(this.cbs.ontext(this.sectionStart,this.index),this.sectionStart=this.index):(this.state===19||this.state===20||this.state===21)&&(this.cbs.onattribdata(this.sectionStart,this.index),this.sectionStart=this.index))}finish(){this.handleTrailingData(),this.cbs.onend()}handleTrailingData(){let e=this.buffer.length;this.sectionStart>=e||(this.state===28?this.currentSequence===DA.CdataEnd?this.cbs.oncdata(this.sectionStart,e):this.cbs.oncomment(this.sectionStart,e):this.state===6||this.state===11||this.state===18||this.state===17||this.state===12||this.state===13||this.state===14||this.state===15||this.state===16||this.state===20||this.state===19||this.state===21||this.state===9||this.cbs.ontext(this.sectionStart,e))}emitCodePoint(e,t){}};function kA(e,{compatConfig:t}){let n=t&&t[e];return e===`MODE`?n||3:n}function AA(e,t){let n=kA(`MODE`,t),r=kA(e,t);return n===3?r===!0:r!==!1}function jA(e,t,n,...r){return AA(e,t)}function MA(e){throw e}function NA(e){}function PA(e,t,n,r){let i=`https://vuejs.org/error-reference/#compiler-${e}`,a=SyntaxError(String(i));return a.code=e,a.loc=t,a}var FA=e=>e.type===4&&e.isStatic;function IA(e){switch(e){case`Teleport`:case`teleport`:return bk;case`Suspense`:case`suspense`:return xk;case`KeepAlive`:case`keep-alive`:return Sk;case`BaseTransition`:case`base-transition`:return Ck}}var LA=/^$|^\d|[^\$\w\xA0-\uFFFF]/,RA=e=>!LA.test(e),zA=/[A-Za-z_$\xA0-\uFFFF]/,BA=/[\.\?\w$\xA0-\uFFFF]/,VA=/\s+[.[]\s*|\s*[.[]\s+/g,HA=e=>e.type===4?e.content:e.loc.source,UA=e=>{let t=HA(e).trim().replace(VA,e=>e.trim()),n=0,r=[],i=0,a=0,o=null;for(let e=0;e<t.length;e++){let s=t.charAt(e);switch(n){case 0:if(s===`[`)r.push(n),n=1,i++;else if(s===`(`)r.push(n),n=2,a++;else if(!(e===0?zA:BA).test(s))return!1;break;case 1:s===`'`||s===`"`||s==="`"?(r.push(n),n=3,o=s):s===`[`?i++:s===`]`&&(--i||(n=r.pop()));break;case 2:if(s===`'`||s===`"`||s==="`")r.push(n),n=3,o=s;else if(s===`(`)a++;else if(s===`)`){if(e===t.length-1)return!1;--a||(n=r.pop())}break;case 3:s===o&&(n=r.pop(),o=null);break}}return!i&&!a},WA=/^\s*(?:async\s*)?(?:\([^)]*?\)|[\w$_]+)\s*(?::[^=]+)?=>|^\s*(?:async\s+)?function(?:\s+[\w$]+)?\s*\(/,GA=e=>WA.test(HA(e));function KA(e,t,n=!1){for(let r=0;r<e.props.length;r++){let i=e.props[r];if(i.type===7&&(n||i.exp)&&(XO(t)?i.name===t:t.test(i.name)))return i}}function qA(e,t,n=!1,r=!1){for(let i=0;i<e.props.length;i++){let a=e.props[i];if(a.type===6){if(n)continue;if(a.name===t&&(a.value||r))return a}else if(a.name===`bind`&&(a.exp||r)&&JA(a.arg,t))return a}}function JA(e,t){return!!(e&&FA(e)&&e.content===t)}function YA(e){return e.props.some(e=>e.type===7&&e.name===`bind`&&(!e.arg||e.arg.type!==4||!e.arg.isStatic))}function XA(e){return e.type===5||e.type===2}function ZA(e){return e.type===7&&e.name===`pre`}function QA(e){return e.type===7&&e.name===`slot`}function $A(e){return e.type===1&&e.tagType===3}function ej(e){return e.type===1&&e.tagType===2}var tj=new Set([Wk,Gk]);function nj(e,t=[]){if(e&&!XO(e)&&e.type===14){let n=e.callee;if(!XO(n)&&tj.has(n))return nj(e.arguments[0],t.concat(e))}return[e,t]}function rj(e,t,n){let r,i=e.type===13?e.props:e.arguments[2],a=[],o;if(i&&!XO(i)&&i.type===14){let e=nj(i);i=e[0],a=e[1],o=a[a.length-1]}if(i==null||XO(i))r=uA([t]);else if(i.type===14){let e=i.arguments[0];!XO(e)&&e.type===15?ij(t,e)||e.properties.unshift(t):i.callee===Kk?r=pA(n.helper(Vk),[uA([t]),i]):i.arguments.unshift(uA([t])),!r&&(r=i)}else i.type===15?(ij(t,i)||i.properties.unshift(t),r=i):(r=pA(n.helper(Vk),[uA([t]),i]),o&&o.callee===Gk&&(o=a[a.length-2]));e.type===13?o?o.arguments[0]=r:e.props=r:o?o.arguments[0]=r:e.arguments[2]=r}function ij(e,t){let n=!1;if(e.key.type===4){let r=e.key.content;n=t.properties.some(e=>e.key.type===4&&e.key.content===r)}return n}function aj(e,t){return`_${t}_${e.replace(/[^\w]/g,(t,n)=>t===`-`?`_`:e.charCodeAt(n).toString())}`}function oj(e){return e.type===14&&e.callee===nA?e.arguments[1].returns:e}var sj=/([\s\S]*?)\s+(?:in|of)\s+(\S[\s\S]*)/;function cj(e){for(let t=0;t<e.length;t++)if(!wA(e.charCodeAt(t)))return!1;return!0}function lj(e){return e.type===2&&cj(e.content)||e.type===12&&lj(e.content)}function uj(e){return e.type===3||lj(e)}var dj={parseMode:`base`,ns:0,delimiters:[`{{`,`}}`],getNamespace:()=>0,isVoidTag:KO,isPreTag:KO,isIgnoreNewlineTag:KO,isCustomElement:KO,onError:MA,onWarn:NA,comments:!1,prefixIdentifiers:!1},Z=dj,fj=null,pj=``,mj=null,Q=null,hj=``,gj=-1,_j=-1,vj=0,yj=!1,bj=null,xj=[],Sj=new OA(xj,{onerr:Kj,ontext(e,t){Oj(Ej(e,t),e,t)},ontextentity(e,t,n){Oj(e,t,n)},oninterpolation(e,t){if(yj)return Oj(Ej(e,t),e,t);let n=e+Sj.delimiterOpen.length,r=t-Sj.delimiterClose.length;for(;wA(pj.charCodeAt(n));)n++;for(;wA(pj.charCodeAt(r-1));)r--;let i=Ej(n,r);i.includes(`&`)&&(i=Z.decodeEntities(i,!1)),Bj({type:5,content:Gj(i,!1,Vj(n,r)),loc:Vj(e,t)})},onopentagname(e,t){let n=Ej(e,t);mj={type:1,tag:n,ns:Z.getNamespace(n,xj[0],Z.ns),tagType:0,props:[],children:[],loc:Vj(e-1,t),codegenNode:void 0}},onopentagend(e){Dj(e)},onclosetag(e,t){let n=Ej(e,t);if(!Z.isVoidTag(n)){let r=!1;for(let e=0;e<xj.length;e++)if(xj[e].tag.toLowerCase()===n.toLowerCase()){r=!0,e>0&&Kj(24,xj[0].loc.start.offset);for(let n=0;n<=e;n++)kj(xj.shift(),t,n<e);break}r||Kj(23,jj(e,60))}},onselfclosingtag(e){let t=mj.tag;mj.isSelfClosing=!0,Dj(e),xj[0]&&xj[0].tag===t&&kj(xj.shift(),e)},onattribname(e,t){Q={type:6,name:Ej(e,t),nameLoc:Vj(e,t),value:void 0,loc:Vj(e)}},ondirname(e,t){let n=Ej(e,t),r=n===`.`||n===`:`?`bind`:n===`@`?`on`:n===`#`?`slot`:n.slice(2);if(!yj&&r===``&&Kj(26,e),yj||r===``)Q={type:6,name:n,nameLoc:Vj(e,t),value:void 0,loc:Vj(e)};else if(Q={type:7,name:r,rawName:n,exp:void 0,arg:void 0,modifiers:n===`.`?[X(`prop`)]:[],loc:Vj(e)},r===`pre`){yj=Sj.inVPre=!0,bj=mj;let e=mj.props;for(let t=0;t<e.length;t++)e[t].type===7&&(e[t]=Wj(e[t]))}},ondirarg(e,t){if(e===t)return;let n=Ej(e,t);if(yj&&!ZA(Q))Q.name+=n,Uj(Q.nameLoc,t);else{let r=n[0]!==`[`;Q.arg=Gj(r?n:n.slice(1,-1),r,Vj(e,t),r?3:0)}},ondirmodifier(e,t){let n=Ej(e,t);if(yj&&!ZA(Q))Q.name+=`.`+n,Uj(Q.nameLoc,t);else if(Q.name===`slot`){let e=Q.arg;e&&(e.content+=`.`+n,Uj(e.loc,t))}else{let r=X(n,!0,Vj(e,t));Q.modifiers.push(r)}},onattribdata(e,t){hj+=Ej(e,t),gj<0&&(gj=e),_j=t},onattribentity(e,t,n){hj+=e,gj<0&&(gj=t),_j=n},onattribnameend(e){let t=Q.loc.start.offset,n=Ej(t,e);Q.type===7&&(Q.rawName=n),mj.props.some(e=>(e.type===7?e.rawName:e.name)===n)&&Kj(2,t)},onattribend(e,t){if(mj&&Q){if(Uj(Q.loc,t),e!==0)if(hj.includes(`&`)&&(hj=Z.decodeEntities(hj,!0)),Q.type===6)Q.name===`class`&&(hj=zj(hj).trim()),e===1&&!hj&&Kj(13,t),Q.value={type:2,content:hj,loc:e===1?Vj(gj,_j):Vj(gj-1,_j+1)},Sj.inSFCRoot&&mj.tag===`template`&&Q.name===`lang`&&hj&&hj!==`html`&&Sj.enterRCDATA(EA(`</template`),0);else{Q.exp=Gj(hj,!1,Vj(gj,_j),0,0),Q.name===`for`&&(Q.forParseResult=Tj(Q.exp));let e=-1;Q.name===`bind`&&(e=Q.modifiers.findIndex(e=>e.content===`sync`))>-1&&jA(`COMPILER_V_BIND_SYNC`,Z,Q.loc,Q.arg.loc.source)&&(Q.name=`model`,Q.modifiers.splice(e,1))}(Q.type!==7||Q.name!==`pre`)&&mj.props.push(Q)}hj=``,gj=_j=-1},oncomment(e,t){Z.comments&&Bj({type:3,content:Ej(e,t),loc:Vj(e-4,t+3)})},onend(){let e=pj.length;for(let t=0;t<xj.length;t++)kj(xj[t],e-1),Kj(24,xj[t].loc.start.offset)},oncdata(e,t){xj[0].ns===0?Kj(1,e-9):Oj(Ej(e,t),e,t)},onprocessinginstruction(e){(xj[0]?xj[0].ns:Z.ns)===0&&Kj(21,e-1)}}),Cj=/,([^,\}\]]*)(?:,([^,\}\]]*))?$/,wj=/^\(|\)$/g;function Tj(e){let t=e.loc,n=e.content,r=n.match(sj);if(!r)return;let[,i,a]=r,o=(e,n,r=!1)=>{let i=t.start.offset+n;return Gj(e,!1,Vj(i,i+e.length),0,+!!r)},s={source:o(a.trim(),n.indexOf(a,i.length)),value:void 0,key:void 0,index:void 0,finalized:!1},c=i.trim().replace(wj,``).trim(),l=i.indexOf(c),u=c.match(Cj);if(u){c=c.replace(Cj,``).trim();let e=u[1].trim(),t;if(e&&(t=n.indexOf(e,l+c.length),s.key=o(e,t,!0)),u[2]){let r=u[2].trim();r&&(s.index=o(r,n.indexOf(r,s.key?t+e.length:l+c.length),!0))}}return c&&(s.value=o(c,l,!0)),s}function Ej(e,t){return pj.slice(e,t)}function Dj(e){Sj.inSFCRoot&&(mj.innerLoc=Vj(e+1,e+1)),Bj(mj);let{tag:t,ns:n}=mj;n===0&&Z.isPreTag(t)&&vj++,Z.isVoidTag(t)?kj(mj,e):(xj.unshift(mj),(n===1||n===2)&&(Sj.inXML=!0)),mj=null}function Oj(e,t,n){{let t=xj[0]&&xj[0].tag;t!==`script`&&t!==`style`&&e.includes(`&`)&&(e=Z.decodeEntities(e,!1))}let r=xj[0]||fj,i=r.children[r.children.length-1];i&&i.type===2?(i.content+=e,Uj(i.loc,n)):r.children.push({type:2,content:e,loc:Vj(t,n)})}function kj(e,t,n=!1){n?Uj(e.loc,jj(t,60)):Uj(e.loc,Aj(t,62)+1),Sj.inSFCRoot&&(e.children.length?e.innerLoc.end=JO({},e.children[e.children.length-1].loc.end):e.innerLoc.end=JO({},e.innerLoc.start),e.innerLoc.source=Ej(e.innerLoc.start.offset,e.innerLoc.end.offset));let{tag:r,ns:i,children:a}=e;if(yj||(r===`slot`?e.tagType=2:Nj(e)?e.tagType=3:Pj(e)&&(e.tagType=1)),Sj.inRCDATA||(e.children=Lj(a)),i===0&&Z.isIgnoreNewlineTag(r)){let e=a[0];e&&e.type===2&&(e.content=e.content.replace(/^\r?\n/,``))}i===0&&Z.isPreTag(r)&&vj--,bj===e&&(yj=Sj.inVPre=!1,bj=null),Sj.inXML&&(xj[0]?xj[0].ns:Z.ns)===0&&(Sj.inXML=!1);{let t=e.props;if(!Sj.inSFCRoot&&AA(`COMPILER_NATIVE_TEMPLATE`,Z)&&e.tag===`template`&&!Nj(e)){let t=xj[0]||fj,n=t.children.indexOf(e);t.children.splice(n,1,...e.children)}let n=t.find(e=>e.type===6&&e.name===`inline-template`);n&&jA(`COMPILER_INLINE_TEMPLATE`,Z,n.loc)&&e.children.length&&(n.value={type:2,content:Ej(e.children[0].loc.start.offset,e.children[e.children.length-1].loc.end.offset),loc:n.loc})}}function Aj(e,t){let n=e;for(;pj.charCodeAt(n)!==t&&n<pj.length-1;)n++;return n}function jj(e,t){let n=e;for(;pj.charCodeAt(n)!==t&&n>=0;)n--;return n}var Mj=new Set([`if`,`else`,`else-if`,`for`,`slot`]);function Nj({tag:e,props:t}){if(e===`template`){for(let e=0;e<t.length;e++)if(t[e].type===7&&Mj.has(t[e].name))return!0}return!1}function Pj({tag:e,props:t}){if(Z.isCustomElement(e))return!1;if(e===`component`||Fj(e.charCodeAt(0))||IA(e)||Z.isBuiltInComponent&&Z.isBuiltInComponent(e)||Z.isNativeTag&&!Z.isNativeTag(e))return!0;for(let e=0;e<t.length;e++){let n=t[e];if(n.type===6){if(n.name===`is`&&n.value&&(n.value.content.startsWith(`vue:`)||jA(`COMPILER_IS_ON_ELEMENT`,Z,n.loc)))return!0}else if(n.name===`bind`&&JA(n.arg,`is`)&&jA(`COMPILER_IS_ON_ELEMENT`,Z,n.loc))return!0}return!1}function Fj(e){return e>64&&e<91}var Ij=/\r\n/g;function Lj(e){let t=Z.whitespace!==`preserve`,n=!1;for(let r=0;r<e.length;r++){let i=e[r];if(i.type===2)if(vj)i.content=i.content.replace(Ij,`
`);else if(cj(i.content)){let a=e[r-1]&&e[r-1].type,o=e[r+1]&&e[r+1].type;!a||!o||t&&(a===3&&(o===3||o===1)||a===1&&(o===3||o===1&&Rj(i.content)))?(n=!0,e[r]=null):i.content=` `}else t&&(i.content=zj(i.content))}return n?e.filter(Boolean):e}function Rj(e){for(let t=0;t<e.length;t++){let n=e.charCodeAt(t);if(n===10||n===13)return!0}return!1}function zj(e){let t=``,n=!1;for(let r=0;r<e.length;r++)wA(e.charCodeAt(r))?n||=(t+=` `,!0):(t+=e[r],n=!1);return t}function Bj(e){(xj[0]||fj).children.push(e)}function Vj(e,t){return{start:Sj.getPos(e),end:t==null?t:Sj.getPos(t),source:t==null?t:Ej(e,t)}}function Hj(e){return Vj(e.start.offset,e.end.offset)}function Uj(e,t){e.end=Sj.getPos(t),e.source=Ej(e.start.offset,t)}function Wj(e){let t={type:6,name:e.rawName,nameLoc:Vj(e.loc.start.offset,e.loc.start.offset+e.rawName.length),value:void 0,loc:e.loc};if(e.exp){let n=e.exp.loc;n.end.offset<e.loc.end.offset&&(n.start.offset--,n.start.column--,n.end.offset++,n.end.column++),t.value={type:2,content:e.exp.content,loc:n}}return t}function Gj(e,t=!1,n,r=0,i=0){return X(e,t,n,r)}function Kj(e,t,n){Z.onError(PA(e,Vj(t,t),void 0,n))}function qj(){Sj.reset(),mj=null,Q=null,hj=``,gj=-1,_j=-1,xj.length=0}function Jj(e,t){if(qj(),pj=e,Z=JO({},dj),t){let e;for(e in t)t[e]!=null&&(Z[e]=t[e])}Sj.mode=Z.parseMode===`html`?1:Z.parseMode===`sfc`?2:0,Sj.inXML=Z.ns===1||Z.ns===2;let n=t&&t.delimiters;n&&(Sj.delimiterOpen=EA(n[0]),Sj.delimiterClose=EA(n[1]));let r=fj=sA([],e);return Sj.parse(pj),r.loc=Vj(0,e.length),r.children=Lj(r.children),fj=null,r}function Yj(e,t){Zj(e,void 0,t,!!Xj(e))}function Xj(e){let t=e.children.filter(e=>e.type!==3);return t.length===1&&t[0].type===1&&!ej(t[0])?t[0]:null}function Zj(e,t,n,r=!1,i=!1){let{children:a}=e,o=[];for(let t=0;t<a.length;t++){let s=a[t];if(s.type===1&&s.tagType===0){let e=r?0:Qj(s,n);if(e>0){if(e>=2){s.codegenNode.patchFlag=-1,o.push(s);continue}}else{let e=s.codegenNode;if(e.type===13){let t=e.patchFlag;if((t===void 0||t===512||t===1)&&tM(s,n)>=2){let t=nM(s);t&&(e.props=n.hoist(t))}e.dynamicProps&&=n.hoist(e.dynamicProps)}}}else if(s.type===12&&(r?0:Qj(s,n))>=2){s.codegenNode.type===14&&s.codegenNode.arguments.length>0&&s.codegenNode.arguments.push(`-1`),o.push(s);continue}if(s.type===1){let t=s.tagType===1;t&&n.scopes.vSlot++,Zj(s,e,n,!1,i),t&&n.scopes.vSlot--}else if(s.type===11)Zj(s,e,n,s.children.length===1,!0);else if(s.type===9)for(let t=0;t<s.branches.length;t++)Zj(s.branches[t],e,n,s.branches[t].children.length===1,i)}let s=!1;if(o.length===a.length&&e.type===1){if(e.tagType===0&&e.codegenNode&&e.codegenNode.type===13&&YO(e.codegenNode.children))e.codegenNode.children=c(lA(e.codegenNode.children)),s=!0;else if(e.tagType===1&&e.codegenNode&&e.codegenNode.type===13&&e.codegenNode.children&&!YO(e.codegenNode.children)&&e.codegenNode.children.type===15){let t=l(e.codegenNode,`default`);t&&(t.returns=c(lA(t.returns)),s=!0)}else if(e.tagType===3&&t&&t.type===1&&t.tagType===1&&t.codegenNode&&t.codegenNode.type===13&&t.codegenNode.children&&!YO(t.codegenNode.children)&&t.codegenNode.children.type===15){let n=KA(e,`slot`,!0),r=n&&n.arg&&l(t.codegenNode,n.arg);r&&(r.returns=c(lA(r.returns)),s=!0)}}if(!s)for(let e of o)e.codegenNode=n.cache(e.codegenNode);function c(e){let t=n.cache(e);return t.needArraySpread=!0,t}function l(e,t){if(e.children&&!YO(e.children)&&e.children.type===15){let n=e.children.properties.find(e=>e.key===t||e.key.content===t);return n&&n.value}}o.length&&n.transformHoist&&n.transformHoist(a,n,e)}function Qj(e,t){let{constantCache:n}=t;switch(e.type){case 1:if(e.tagType!==0)return 0;let r=n.get(e);if(r!==void 0)return r;let i=e.codegenNode;if(i.type!==13||i.isBlock&&e.tag!==`svg`&&e.tag!==`foreignObject`&&e.tag!==`math`)return 0;if(i.patchFlag===void 0){let r=3,a=tM(e,t);if(a===0)return n.set(e,0),0;a<r&&(r=a);for(let i=0;i<e.children.length;i++){let a=Qj(e.children[i],t);if(a===0)return n.set(e,0),0;a<r&&(r=a)}if(r>1)for(let i=0;i<e.props.length;i++){let a=e.props[i];if(a.type===7&&a.name===`bind`&&a.exp){let i=Qj(a.exp,t);if(i===0)return n.set(e,0),0;i<r&&(r=i)}}if(i.isBlock){for(let t=0;t<e.props.length;t++)if(e.props[t].type===7)return n.set(e,0),0;t.removeHelper(wk),t.removeHelper(yA(t.inSSR,i.isComponent)),i.isBlock=!1,t.helper(vA(t.inSSR,i.isComponent))}return n.set(e,r),r}else return n.set(e,0),0;case 2:case 3:return 3;case 9:case 11:case 10:return 0;case 5:case 12:return Qj(e.content,t);case 4:return e.constType;case 8:let a=3;for(let n=0;n<e.children.length;n++){let r=e.children[n];if(XO(r)||ZO(r))continue;let i=Qj(r,t);if(i===0)return 0;i<a&&(a=i)}return a;case 20:return 2;default:return 0}}var $j=new Set([Hk,Uk,Wk,Gk]);function eM(e,t){if(e.type===14&&!XO(e.callee)&&$j.has(e.callee)){let n=e.arguments[0];if(n.type===4)return Qj(n,t);if(n.type===14)return eM(n,t)}return 0}function tM(e,t){let n=3,r=nM(e);if(r&&r.type===15){let{properties:e}=r;for(let r=0;r<e.length;r++){let{key:i,value:a}=e[r],o=Qj(i,t);if(o===0)return o;o<n&&(n=o);let s;if(s=a.type===4?Qj(a,t):a.type===14?eM(a,t):0,s===0)return s;s<n&&(n=s)}}return n}function nM(e){let t=e.codegenNode;if(t.type===13)return t.props}function rM(e,{filename:t=``,prefixIdentifiers:n=!1,hoistStatic:r=!1,hmr:i=!1,cacheHandlers:a=!1,nodeTransforms:o=[],directiveTransforms:s={},transformHoist:c=null,isBuiltInComponent:l=GO,isCustomElement:u=GO,expressionPlugins:d=[],scopeId:f=null,slotted:p=!0,ssr:m=!1,inSSR:h=!1,ssrCssVars:g=``,bindingMetadata:_=WO,inline:v=!1,isTS:y=!1,onError:b=MA,onWarn:x=NA,compatConfig:S}){let C=t.replace(/\?.*$/,``).match(/([^/\\]+)\.\w+$/),w={filename:t,selfName:C&&ik(rk(C[1])),prefixIdentifiers:n,hoistStatic:r,hmr:i,cacheHandlers:a,nodeTransforms:o,directiveTransforms:s,transformHoist:c,isBuiltInComponent:l,isCustomElement:u,expressionPlugins:d,scopeId:f,slotted:p,ssr:m,inSSR:h,ssrCssVars:g,bindingMetadata:_,inline:v,isTS:y,onError:b,onWarn:x,compatConfig:S,root:e,helpers:new Map,components:new Set,directives:new Set,hoists:[],imports:[],cached:[],constantCache:new WeakMap,temps:0,identifiers:Object.create(null),scopes:{vFor:0,vSlot:0,vPre:0,vOnce:0},parent:null,grandParent:null,currentNode:e,childIndex:0,inVOnce:!1,helper(e){let t=w.helpers.get(e)||0;return w.helpers.set(e,t+1),e},removeHelper(e){let t=w.helpers.get(e);if(t){let n=t-1;n?w.helpers.set(e,n):w.helpers.delete(e)}},helperString(e){return`_${iA[w.helper(e)]}`},replaceNode(e){w.parent.children[w.childIndex]=w.currentNode=e},removeNode(e){let t=w.parent.children,n=e?t.indexOf(e):w.currentNode?w.childIndex:-1;!e||e===w.currentNode?(w.currentNode=null,w.onNodeRemoved()):w.childIndex>n&&(w.childIndex--,w.onNodeRemoved()),w.parent.children.splice(n,1)},onNodeRemoved:GO,addIdentifiers(e){},removeIdentifiers(e){},hoist(e){XO(e)&&(e=X(e)),w.hoists.push(e);let t=X(`_hoisted_${w.hoists.length}`,!1,e.loc,2);return t.hoisted=e,t},cache(e,t=!1,n=!1){let r=gA(w.cached.length,e,t,n);return w.cached.push(r),r}};return w.filters=new Set,w}function iM(e,t){let n=rM(e,t);sM(e,n),t.hoistStatic&&Yj(e,n),t.ssr||aM(e,n),e.helpers=new Set([...n.helpers.keys()]),e.components=[...n.components],e.directives=[...n.directives],e.imports=n.imports,e.hoists=n.hoists,e.temps=n.temps,e.cached=n.cached,e.transformed=!0,e.filters=[...n.filters]}function aM(e,t){let{helper:n}=t,{children:r}=e;if(r.length===1){let n=Xj(e);if(n&&n.codegenNode){let r=n.codegenNode;r.type===13&&bA(r,t),e.codegenNode=r}else e.codegenNode=r[0]}else r.length>1&&(e.codegenNode=cA(t,n(yk),void 0,e.children,64,void 0,void 0,!0,void 0,!1))}function oM(e,t){let n=0,r=()=>{n--};for(;n<e.children.length;n++){let i=e.children[n];XO(i)||(t.grandParent=t.parent,t.parent=e,t.childIndex=n,t.onNodeRemoved=r,sM(i,t))}}function sM(e,t){t.currentNode=e;let{nodeTransforms:n}=t,r=[];for(let i=0;i<n.length;i++){let a=n[i](e,t);if(a&&(YO(a)?r.push(...a):r.push(a)),t.currentNode)e=t.currentNode;else return}switch(e.type){case 3:t.ssr||t.helper(kk);break;case 5:t.ssr||t.helper(Bk);break;case 9:for(let n=0;n<e.branches.length;n++)sM(e.branches[n],t);break;case 10:case 11:case 1:case 0:oM(e,t);break}t.currentNode=e;let i=r.length;for(;i--;)r[i]()}function cM(e,t){let n=XO(e)?t=>t===e:t=>e.test(t);return(e,r)=>{if(e.type===1){let{props:i}=e;if(e.tagType===3&&i.some(QA))return;let a=[];for(let o=0;o<i.length;o++){let s=i[o];if(s.type===7&&n(s.name)){i.splice(o,1),o--;let n=t(e,s,r);n&&a.push(n)}}return a}}}var lM=`/*@__PURE__*/`,uM=e=>`${iA[e]}: _${iA[e]}`;function dM(e,{mode:t=`function`,prefixIdentifiers:n=t===`module`,sourceMap:r=!1,filename:i=`template.vue.html`,scopeId:a=null,optimizeImports:o=!1,runtimeGlobalName:s=`Vue`,runtimeModuleName:c=`vue`,ssrRuntimeModuleName:l=`vue/server-renderer`,ssr:u=!1,isTS:d=!1,inSSR:f=!1}){let p={mode:t,prefixIdentifiers:n,sourceMap:r,filename:i,scopeId:a,optimizeImports:o,runtimeGlobalName:s,runtimeModuleName:c,ssrRuntimeModuleName:l,ssr:u,isTS:d,inSSR:f,source:e.source,code:``,column:1,line:1,offset:0,indentLevel:0,pure:!1,map:void 0,helper(e){return`_${iA[e]}`},push(e,t=-2,n){p.code+=e},indent(){m(++p.indentLevel)},deindent(e=!1){e?--p.indentLevel:m(--p.indentLevel)},newline(){m(p.indentLevel)}};function m(e){p.push(`
`+`  `.repeat(e),0)}return p}function fM(e,t={}){let n=dM(e,t);t.onContextCreated&&t.onContextCreated(n);let{mode:r,push:i,prefixIdentifiers:a,indent:o,deindent:s,newline:c,scopeId:l,ssr:u}=n,d=Array.from(e.helpers),f=d.length>0,p=!a&&r!==`module`;if(pM(e,n),i(`function ${u?`ssrRender`:`render`}(${(u?[`_ctx`,`_push`,`_parent`,`_attrs`]:[`_ctx`,`_cache`]).join(`, `)}) {`),o(),p&&(i(`with (_ctx) {`),o(),f&&(i(`const { ${d.map(uM).join(`, `)} } = _Vue
`,-1),c())),e.components.length&&(mM(e.components,`component`,n),(e.directives.length||e.temps>0)&&c()),e.directives.length&&(mM(e.directives,`directive`,n),e.temps>0&&c()),e.filters&&e.filters.length&&(c(),mM(e.filters,`filter`,n),c()),e.temps>0){i(`let `);for(let t=0;t<e.temps;t++)i(`${t>0?`, `:``}_temp${t}`)}return(e.components.length||e.directives.length||e.temps)&&(i(`
`,0),c()),u||i(`return `),e.codegenNode?vM(e.codegenNode,n):i(`null`),p&&(s(),i(`}`)),s(),i(`}`),{ast:e,code:n.code,preamble:``,map:n.map?n.map.toJSON():void 0}}function pM(e,t){let{ssr:n,prefixIdentifiers:r,push:i,newline:a,runtimeModuleName:o,runtimeGlobalName:s,ssrRuntimeModuleName:c}=t,l=s,u=Array.from(e.helpers);u.length>0&&(i(`const _Vue = ${l}
`,-1),e.hoists.length&&i(`const { ${[Dk,Ok,kk,Ak,jk].filter(e=>u.includes(e)).map(uM).join(`, `)} } = _Vue
`,-1)),hM(e.hoists,t),a(),i(`return `)}function mM(e,t,{helper:n,push:r,newline:i,isTS:a}){let o=n(t===`filter`?Fk:t===`component`?Mk:Pk);for(let n=0;n<e.length;n++){let s=e[n],c=s.endsWith(`__self`);c&&(s=s.slice(0,-6)),r(`const ${aj(s,t)} = ${o}(${JSON.stringify(s)}${c?`, true`:``})${a?`!`:``}`),n<e.length-1&&i()}}function hM(e,t){if(!e.length)return;t.pure=!0;let{push:n,newline:r}=t;r();for(let i=0;i<e.length;i++){let a=e[i];a&&(n(`const _hoisted_${i+1} = `),vM(a,t),r())}t.pure=!1}function gM(e,t){let n=e.length>3||!1;t.push(`[`),n&&t.indent(),_M(e,t,n),n&&t.deindent(),t.push(`]`)}function _M(e,t,n=!1,r=!0){let{push:i,newline:a}=t;for(let o=0;o<e.length;o++){let s=e[o];XO(s)?i(s,-3):YO(s)?gM(s,t):vM(s,t),o<e.length-1&&(n?(r&&i(`,`),a()):r&&i(`, `))}}function vM(e,t){if(XO(e)){t.push(e,-3);return}if(ZO(e)){t.push(t.helper(e));return}switch(e.type){case 1:case 9:case 11:vM(e.codegenNode,t);break;case 2:yM(e,t);break;case 4:bM(e,t);break;case 5:xM(e,t);break;case 12:vM(e.codegenNode,t);break;case 8:SM(e,t);break;case 3:wM(e,t);break;case 13:TM(e,t);break;case 14:DM(e,t);break;case 15:OM(e,t);break;case 17:kM(e,t);break;case 18:AM(e,t);break;case 19:jM(e,t);break;case 20:MM(e,t);break;case 21:_M(e.body,t,!0,!1);break;case 22:break;case 23:break;case 24:break;case 25:break;case 26:break;case 10:break;default:}}function yM(e,t){t.push(JSON.stringify(e.content),-3,e)}function bM(e,t){let{content:n,isStatic:r}=e;t.push(r?JSON.stringify(n):n,-3,e)}function xM(e,t){let{push:n,helper:r,pure:i}=t;i&&n(lM),n(`${r(Bk)}(`),vM(e.content,t),n(`)`)}function SM(e,t){for(let n=0;n<e.children.length;n++){let r=e.children[n];XO(r)?t.push(r,-3):vM(r,t)}}function CM(e,t){let{push:n}=t;e.type===8?(n(`[`),SM(e,t),n(`]`)):e.isStatic?n(RA(e.content)?e.content:JSON.stringify(e.content),-2,e):n(`[${e.content}]`,-3,e)}function wM(e,t){let{push:n,helper:r,pure:i}=t;i&&n(lM),n(`${r(kk)}(${JSON.stringify(e.content)})`,-3,e)}function TM(e,t){let{push:n,helper:r,pure:i}=t,{tag:a,props:o,children:s,patchFlag:c,dynamicProps:l,directives:u,isBlock:d,disableTracking:f,isComponent:p}=e,m;c&&(m=String(c)),u&&n(r(Ik)+`(`),d&&n(`(${r(wk)}(${f?`true`:``}), `),i&&n(lM),n(r(d?yA(t.inSSR,p):vA(t.inSSR,p))+`(`,-2,e),_M(EM([a,o,s,m,l]),t),n(`)`),d&&n(`)`),u&&(n(`, `),vM(u,t),n(`)`))}function EM(e){let t=e.length;for(;t--&&e[t]==null;);return e.slice(0,t+1).map(e=>e||`null`)}function DM(e,t){let{push:n,helper:r,pure:i}=t,a=XO(e.callee)?e.callee:r(e.callee);i&&n(lM),n(a+`(`,-2,e),_M(e.arguments,t),n(`)`)}function OM(e,t){let{push:n,indent:r,deindent:i,newline:a}=t,{properties:o}=e;if(!o.length){n(`{}`,-2,e);return}let s=o.length>1||!1;n(s?`{`:`{ `),s&&r();for(let e=0;e<o.length;e++){let{key:r,value:i}=o[e];CM(r,t),n(`: `),vM(i,t),e<o.length-1&&(n(`,`),a())}s&&i(),n(s?`}`:` }`)}function kM(e,t){gM(e.elements,t)}function AM(e,t){let{push:n,indent:r,deindent:i}=t,{params:a,returns:o,body:s,newline:c,isSlot:l}=e;l&&n(`_${iA[$k]}(`),n(`(`,-2,e),YO(a)?_M(a,t):a&&vM(a,t),n(`) => `),(c||s)&&(n(`{`),r()),o?(c&&n(`return `),YO(o)?gM(o,t):vM(o,t)):s&&vM(s,t),(c||s)&&(i(),n(`}`)),l&&(e.isNonScopedSlot&&n(`, undefined, true`),n(`)`))}function jM(e,t){let{test:n,consequent:r,alternate:i,newline:a}=e,{push:o,indent:s,deindent:c,newline:l}=t;if(n.type===4){let e=!RA(n.content);e&&o(`(`),bM(n,t),e&&o(`)`)}else o(`(`),vM(n,t),o(`)`);a&&s(),t.indentLevel++,a||o(` `),o(`? `),vM(r,t),t.indentLevel--,a&&l(),a||o(` `),o(`: `);let u=i.type===19;u||t.indentLevel++,vM(i,t),u||t.indentLevel--,a&&c(!0)}function MM(e,t){let{push:n,helper:r,indent:i,deindent:a,newline:o}=t,{needPauseTracking:s,needArraySpread:c}=e;c&&n(`[...(`),n(`_cache[${e.index}] || (`),s&&(i(),n(`${r(Xk)}(-1`),e.inVOnce&&n(`, true`),n(`),`),o(),n(`(`)),n(`_cache[${e.index}] = `),vM(e.value,t),s&&(n(`).cacheIndex = ${e.index},`),o(),n(`${r(Xk)}(1),`),o(),n(`_cache[${e.index}]`),a()),n(`)`),c&&n(`)]`)}RegExp(`\\b`+`arguments,await,break,case,catch,class,const,continue,debugger,default,delete,do,else,export,extends,finally,for,function,if,import,let,new,return,super,switch,throw,try,var,void,while,with,yield`.split(`,`).join(`\\b|\\b`)+`\\b`);var NM=cM(/^(?:if|else|else-if)$/,(e,t,n)=>PM(e,t,n,(e,t,r)=>{let i=n.parent.children,a=i.indexOf(e),o=0;for(;a-->=0;){let e=i[a];e&&e.type===9&&(o+=e.branches.length)}return()=>{if(r)e.codegenNode=IM(t,o,n);else{let r=RM(e.codegenNode);r.alternate=IM(t,o+e.branches.length-1,n)}}}));function PM(e,t,n,r){if(t.name!==`else`&&(!t.exp||!t.exp.content.trim())){let r=t.exp?t.exp.loc:e.loc;n.onError(PA(28,t.loc)),t.exp=X(`true`,!1,r)}if(t.name===`if`){let i=FM(e,t),a={type:9,loc:Hj(e.loc),branches:[i]};if(n.replaceNode(a),r)return r(a,i,!0)}else{let i=n.parent.children,a=i.indexOf(e);for(;a-->=-1;){let o=i[a];if(o&&uj(o)){n.removeNode(o);continue}if(o&&o.type===9){(t.name===`else-if`||t.name===`else`)&&o.branches[o.branches.length-1].condition===void 0&&n.onError(PA(30,e.loc)),n.removeNode();let i=FM(e,t);o.branches.push(i);let a=r&&r(o,i,!1);sM(i,n),a&&a(),n.currentNode=null}else n.onError(PA(30,e.loc));break}}}function FM(e,t){let n=e.tagType===3;return{type:10,loc:e.loc,condition:t.name===`else`?void 0:t.exp,children:n&&!KA(e,`for`)?e.children:[e],userKey:qA(e,`key`),isTemplateIf:n}}function IM(e,t,n){return e.condition?hA(e.condition,LM(e,t,n),pA(n.helper(kk),[`""`,`true`])):LM(e,t,n)}function LM(e,t,n){let{helper:r}=n,i=dA(`key`,X(`${t}`,!1,oA,2)),{children:a}=e,o=a[0];if(a.length!==1||o.type!==1)if(a.length===1&&o.type===11){let e=o.codegenNode;return rj(e,i,n),e}else return cA(n,r(yk),uA([i]),a,64,void 0,void 0,!0,!1,!1,e.loc);else{let e=o.codegenNode,t=oj(e);return t.type===13&&bA(t,n),rj(t,i,n),e}}function RM(e){for(;;)if(e.type===19)if(e.alternate.type===19)e=e.alternate;else return e;else e.type===20&&(e=e.value)}var zM=cM(`for`,(e,t,n)=>{let{helper:r,removeHelper:i}=n;return BM(e,t,n,t=>{let a=pA(r(Lk),[t.source]),o=$A(e),s=KA(e,`memo`),c=qA(e,`key`,!1,!0);c&&c.type;let l=c&&(c.type===6?c.value?X(c.value.content,!0):void 0:c.exp),u=c&&l?dA(`key`,l):null,d=t.source.type===4&&t.source.constType>0,f=d?64:c?128:256;return t.codegenNode=cA(n,r(yk),void 0,a,f,void 0,void 0,!0,!d,!1,e.loc),()=>{let c,{children:f}=t,p=f.length!==1||f[0].type!==1,m=ej(e)?e:o&&e.children.length===1&&ej(e.children[0])?e.children[0]:null;if(m?(c=m.codegenNode,o&&u&&rj(c,u,n)):p?c=cA(n,r(yk),u?uA([u]):void 0,e.children,64,void 0,void 0,!0,void 0,!1):(c=f[0].codegenNode,o&&u&&rj(c,u,n),c.isBlock!==!d&&(c.isBlock?(i(wk),i(yA(n.inSSR,c.isComponent))):i(vA(n.inSSR,c.isComponent))),c.isBlock=!d,c.isBlock?(r(wk),r(yA(n.inSSR,c.isComponent))):r(vA(n.inSSR,c.isComponent))),s){let e=mA(HM(t.parseResult,[X(`_cached`)]));e.body=_A([fA([`const _memo = (`,s.exp,`)`]),fA([`if (_cached && _cached.el`,...l?[` && _cached.key === `,l]:[],` && ${n.helperString(rA)}(_cached, _memo)) return _cached`]),fA([`const _item = `,c]),X(`_item.memo = _memo`),X(`return _item`)]),a.arguments.push(e,X(`_cache`),X(String(n.cached.length))),n.cached.push(null)}else a.arguments.push(mA(HM(t.parseResult),c,!0))}})});function BM(e,t,n,r){if(!t.exp){n.onError(PA(31,t.loc));return}let i=t.forParseResult;if(!i){n.onError(PA(32,t.loc));return}VM(i,n);let{addIdentifiers:a,removeIdentifiers:o,scopes:s}=n,{source:c,value:l,key:u,index:d}=i,f={type:11,loc:t.loc,source:c,valueAlias:l,keyAlias:u,objectIndexAlias:d,parseResult:i,children:$A(e)?e.children:[e]};n.replaceNode(f),s.vFor++;let p=r&&r(f);return()=>{s.vFor--,p&&p()}}function VM(e,t){e.finalized||=!0}function HM({value:e,key:t,index:n},r=[]){return UM([e,t,n,...r])}function UM(e){let t=e.length;for(;t--&&!e[t];);return e.slice(0,t+1).map((e,t)=>e||X(`_`.repeat(t+1),!1))}var WM=X(`undefined`,!1),GM=(e,t)=>{if(e.type===1&&(e.tagType===1||e.tagType===3)){let n=KA(e,`slot`);if(n)return n.exp,t.scopes.vSlot++,()=>{t.scopes.vSlot--}}},KM=(e,t,n,r)=>mA(e,n,!1,!0,n.length?n[0].loc:r);function qM(e,t,n=KM){t.helper($k);let{children:r,loc:i}=e,a=[],o=[],s=t.scopes.vSlot>0||t.scopes.vFor>0,c=KA(e,`slot`,!0);if(c){let{arg:e,exp:t}=c;e&&!FA(e)&&(s=!0),a.push(dA(e||X(`default`,!0),n(t,void 0,r,i)))}let l=!1,u=!1,d=[],f=new Set,p=0;for(let e=0;e<r.length;e++){let i=r[e],m;if(!$A(i)||!(m=KA(i,`slot`,!0))){i.type!==3&&d.push(i);continue}if(c){t.onError(PA(37,m.loc));break}l=!0;let{children:h,loc:g}=i,{arg:_=X(`default`,!0),exp:v,loc:y}=m,b;FA(_)?b=_?_.content:`default`:s=!0;let x=KA(i,`for`),S=n(v,x,h,g),C,w;if(C=KA(i,`if`))s=!0,o.push(hA(C.exp,JM(_,S,p++),WM));else if(w=KA(i,/^else(?:-if)?$/,!0)){let n=e,i;for(;n--&&(i=r[n],uj(i)););if(i&&$A(i)&&KA(i,/^(?:else-)?if$/)){let e=o[o.length-1];for(;e.alternate.type===19;)e=e.alternate;e.alternate=w.exp?hA(w.exp,JM(_,S,p++),WM):JM(_,S,p++)}else t.onError(PA(30,w.loc))}else if(x){s=!0;let e=x.forParseResult;e?(VM(e,t),o.push(pA(t.helper(Lk),[e.source,mA(HM(e),JM(_,S),!0)]))):t.onError(PA(32,x.loc))}else{if(b){if(f.has(b)){t.onError(PA(38,y));continue}f.add(b),b===`default`&&(u=!0)}a.push(dA(_,S))}}if(!c){let e=(e,r)=>{let a=n(e,void 0,r,i);return t.compatConfig&&(a.isNonScopedSlot=!0),dA(`default`,a)};l?d.length&&!d.every(lj)&&(u?t.onError(PA(39,d[0].loc)):a.push(e(void 0,d))):a.push(e(void 0,r))}let m=s?2:YM(e.children)?3:1,h=uA(a.concat(dA(`_`,X(m+``,!1))),i);return o.length&&(h=pA(t.helper(zk),[h,lA(o)])),{slots:h,hasDynamicSlots:s}}function JM(e,t,n){let r=[dA(`name`,e),dA(`fn`,t)];return n!=null&&r.push(dA(`key`,X(String(n),!0))),uA(r)}function YM(e){for(let t=0;t<e.length;t++){let n=e[t];switch(n.type){case 1:if(n.tagType===2||YM(n.children))return!0;break;case 9:if(YM(n.branches))return!0;break;case 10:case 11:if(YM(n.children))return!0;break}}return!1}var XM=new WeakMap,ZM=(e,t)=>function(){if(e=t.currentNode,!(e.type===1&&(e.tagType===0||e.tagType===1)))return;let{tag:n,props:r}=e,i=e.tagType===1,a=i?QM(e,t):`"${n}"`,o=QO(a)&&a.callee===Nk,s,c,l=0,u,d,f,p=o||a===bk||a===xk||!i&&(n===`svg`||n===`foreignObject`||n===`math`);if(r.length>0){let n=$M(e,t,void 0,i,o);s=n.props,l=n.patchFlag,d=n.dynamicPropNames;let r=n.directives;f=r&&r.length?lA(r.map(e=>nN(e,t))):void 0,n.shouldUseBlock&&(p=!0)}if(e.children.length>0)if(a===Sk&&(p=!0,l|=1024),i&&a!==bk&&a!==Sk){let{slots:n,hasDynamicSlots:r}=qM(e,t);c=n,r&&(l|=1024)}else if(e.children.length===1&&a!==bk){let n=e.children[0],r=n.type,i=r===5||r===8;i&&Qj(n,t)===0&&(l|=1),c=i||r===2?n:e.children}else c=e.children;d&&d.length&&(u=rN(d)),e.codegenNode=cA(t,a,s,c,l===0?void 0:l,u,f,!!p,!1,i,e.loc)};function QM(e,t,n=!1){let{tag:r}=e,i=iN(r),a=qA(e,`is`,!1,!0);if(a)if(i||AA(`COMPILER_IS_ON_ELEMENT`,t)){let e;if(a.type===6?e=a.value&&X(a.value.content,!0):(e=a.exp,e||=X(`is`,!1,a.arg.loc)),e)return pA(t.helper(Nk),[e])}else a.type===6&&a.value.content.startsWith(`vue:`)&&(r=a.value.content.slice(4));let o=IA(r)||t.isBuiltInComponent(r);return o?(n||t.helper(o),o):(t.helper(Mk),t.components.add(r),aj(r,`component`))}function $M(e,t,n=e.props,r,i,a=!1){let{tag:o,loc:s,children:c}=e,l=[],u=[],d=[],f=c.length>0,p=!1,m=0,h=!1,g=!1,_=!1,v=!1,y=!1,b=!1,x=[],S=e=>{l.length&&(u.push(uA(eN(l),s)),l=[]),e&&u.push(e)},C=()=>{t.scopes.vFor>0&&l.push(dA(X(`ref_for`,!0),X(`true`)))},w=({key:e,value:n})=>{if(FA(e)){let a=e.content,o=qO(a);if(o&&(!r||i)&&a.toLowerCase()!==`onclick`&&a!==`onUpdate:modelValue`&&!$O(a)&&(v=!0),o&&$O(a)&&(b=!0),o&&n.type===14&&(n=n.arguments[0]),n.type===20||(n.type===4||n.type===8)&&Qj(n,t)>0)return;a===`ref`?h=!0:a===`class`?g=!0:a===`style`?_=!0:a!==`key`&&!x.includes(a)&&x.push(a),r&&(a===`class`||a===`style`)&&!x.includes(a)&&x.push(a)}else y=!0};for(let i=0;i<n.length;i++){let c=n[i];if(c.type===6){let{loc:e,name:n,nameLoc:r,value:i}=c;if(n===`ref`&&(h=!0,C()),n===`is`&&(iN(o)||i&&i.content.startsWith(`vue:`)||AA(`COMPILER_IS_ON_ELEMENT`,t)))continue;l.push(dA(X(n,!0,r),X(i?i.content:``,!0,i?i.loc:e)))}else{let{name:n,arg:i,exp:h,loc:g,modifiers:_}=c,v=n===`bind`,b=n===`on`;if(n===`slot`){r||t.onError(PA(40,g));continue}if(n===`once`||n===`memo`||n===`is`||v&&JA(i,`is`)&&(iN(o)||AA(`COMPILER_IS_ON_ELEMENT`,t))||b&&a)continue;if((v&&JA(i,`key`)||b&&f&&JA(i,`vue:before-update`))&&(p=!0),v&&JA(i,`ref`)&&C(),!i&&(v||b)){if(y=!0,h)if(v){if(S(),AA(`COMPILER_V_BIND_OBJECT_ORDER`,t)){u.unshift(h);continue}C(),S(),u.push(h)}else S({type:14,loc:g,callee:t.helper(Kk),arguments:r?[h]:[h,`true`]});else t.onError(PA(v?34:35,g));continue}v&&_.some(e=>e.content===`prop`)&&(m|=32);let x=t.directiveTransforms[n];if(x){let{props:n,needRuntime:r}=x(c,e,t);!a&&n.forEach(w),b&&i&&!FA(i)?S(uA(n,s)):l.push(...n),r&&(d.push(c),ZO(r)&&XM.set(c,r))}else ek(n)||(d.push(c),f&&(p=!0))}}let T;if(u.length?(S(),T=u.length>1?pA(t.helper(Vk),u,s):u[0]):l.length&&(T=uA(eN(l),s)),y?m|=16:(g&&!r&&(m|=2),_&&!r&&(m|=4),x.length&&(m|=8),v&&(m|=32)),!p&&(m===0||m===32)&&(h||b||d.length>0)&&(m|=512),!t.inSSR&&T)switch(T.type){case 15:let e=-1,n=-1,r=!1;for(let t=0;t<T.properties.length;t++){let i=T.properties[t].key;FA(i)?i.content===`class`?e=t:i.content===`style`&&(n=t):i.isHandlerKey||(r=!0)}let i=T.properties[e],a=T.properties[n];r?T=pA(t.helper(Wk),[T]):(i&&!FA(i.value)&&(i.value=pA(t.helper(Hk),[i.value])),a&&(_||a.value.type===4&&a.value.content.trim()[0]===`[`||a.value.type===17)&&(a.value=pA(t.helper(Uk),[a.value])));break;case 14:break;default:T=pA(t.helper(Wk),[pA(t.helper(Gk),[T])]);break}return{props:T,directives:d,patchFlag:m,dynamicPropNames:x,shouldUseBlock:p}}function eN(e){let t=new Map,n=[];for(let r=0;r<e.length;r++){let i=e[r];if(i.key.type===8||!i.key.isStatic){n.push(i);continue}let a=i.key.content,o=t.get(a);o?(a===`style`||a===`class`||qO(a))&&tN(o,i):(t.set(a,i),n.push(i))}return n}function tN(e,t){e.value.type===17?e.value.elements.push(t.value):e.value=lA([e.value,t.value],e.loc)}function nN(e,t){let n=[],r=XM.get(e);r?n.push(t.helperString(r)):(t.helper(Pk),t.directives.add(e.name),n.push(aj(e.name,`directive`)));let{loc:i}=e;if(e.exp&&n.push(e.exp),e.arg&&(e.exp||n.push(`void 0`),n.push(e.arg)),Object.keys(e.modifiers).length){e.arg||(e.exp||n.push(`void 0`),n.push(`void 0`));let t=X(`true`,!1,i);n.push(uA(e.modifiers.map(e=>dA(e,t)),i))}return lA(n,e.loc)}function rN(e){let t=`[`;for(let n=0,r=e.length;n<r;n++)t+=JSON.stringify(e[n]),n<r-1&&(t+=`, `);return t+`]`}function iN(e){return e===`component`||e===`Component`}var aN=(e,t)=>{if(ej(e)){let{children:n,loc:r}=e,{slotName:i,slotProps:a}=oN(e,t),o=[t.prefixIdentifiers?`_ctx.$slots`:`$slots`,i,`{}`,`undefined`,`true`],s=2;a&&(o[2]=a,s=3),n.length&&(o[3]=mA([],n,!1,!1,r),s=4),t.scopeId&&!t.slotted&&(s=5),o.splice(s),e.codegenNode=pA(t.helper(Rk),o,r)}};function oN(e,t){let n=`"default"`,r,i=[];for(let t=0;t<e.props.length;t++){let r=e.props[t];r.type===6?r.value&&(r.name===`name`?n=JSON.stringify(r.value.content):(r.name=rk(r.name),i.push(r))):r.name===`bind`&&JA(r.arg,`name`)?r.exp?n=r.exp:r.arg&&r.arg.type===4&&(n=r.exp=X(rk(r.arg.content),!1,r.arg.loc)):(r.name===`bind`&&r.arg&&FA(r.arg)&&(r.arg.content=rk(r.arg.content)),i.push(r))}if(i.length>0){let{props:n,directives:a}=$M(e,t,i,!1,!1);r=n,a.length&&t.onError(PA(36,a[0].loc))}return{slotName:n,slotProps:r}}var sN=(e,t,n,r)=>{let{loc:i,modifiers:a,arg:o}=e;!e.exp&&!a.length&&n.onError(PA(35,i));let s;if(o.type===4)if(o.isStatic){let e=o.content;e.startsWith(`vue:`)&&(e=`vnode-${e.slice(4)}`),s=X(t.tagType!==0||e.startsWith(`vnode`)||!/[A-Z]/.test(e)?ak(rk(e)):`on:${e}`,!0,o.loc)}else s=fA([`${n.helperString(Yk)}(`,o,`)`]);else s=o,s.children.unshift(`${n.helperString(Yk)}(`),s.children.push(`)`);let c=e.exp;c&&!c.content.trim()&&(c=void 0);let l=n.cacheHandlers&&!c&&!n.inVOnce;if(c){let e=UA(c),t=!(e||GA(c)),n=c.content.includes(`;`);(t||l&&e)&&(c=fA([`${t?`$event`:`(...args)`} => ${n?`{`:`(`}`,c,n?`}`:`)`]))}let u={props:[dA(s,c||X(`() => {}`,!1,i))]};return r&&(u=r(u)),l&&(u.props[0].value=n.cache(u.props[0].value)),u.props.forEach(e=>e.key.isHandlerKey=!0),u},cN=(e,t,n)=>{let{modifiers:r,loc:i}=e,a=e.arg,{exp:o}=e;return o&&o.type===4&&!o.content.trim()&&(o=void 0),a.type===4?a.isStatic||(a.content=a.content?`${a.content} || ""`:`""`):(a.children.unshift(`(`),a.children.push(`) || ""`)),r.some(e=>e.content===`camel`)&&(a.type===4?a.isStatic?a.content=rk(a.content):a.content=`${n.helperString(qk)}(${a.content})`:(a.children.unshift(`${n.helperString(qk)}(`),a.children.push(`)`))),n.inSSR||(r.some(e=>e.content===`prop`)&&lN(a,`.`),r.some(e=>e.content===`attr`)&&lN(a,`^`)),{props:[dA(a,o)]}},lN=(e,t)=>{e.type===4?e.isStatic?e.content=t+e.content:e.content=`\`${t}\${${e.content}}\``:(e.children.unshift(`'${t}' + (`),e.children.push(`)`))},uN=(e,t)=>{if(e.type===0||e.type===1||e.type===11||e.type===10)return()=>{let n=e.children,r,i=!1;for(let e=0;e<n.length;e++){let t=n[e];if(XA(t)){i=!0;for(let i=e+1;i<n.length;i++){let a=n[i];if(XA(a))r||=n[e]=fA([t],t.loc),r.children.push(` + `,a),n.splice(i,1),i--;else{r=void 0;break}}}}if(!(!i||n.length===1&&(e.type===0||e.type===1&&e.tagType===0&&!e.props.find(e=>e.type===7&&!t.directiveTransforms[e.name])&&e.tag!==`template`)))for(let e=0;e<n.length;e++){let r=n[e];if(XA(r)||r.type===8){let i=[];(r.type!==2||r.content!==` `)&&i.push(r),!t.ssr&&Qj(r,t)===0&&i.push(`1`),n[e]={type:12,content:r,loc:r.loc,codegenNode:pA(t.helper(Ak),i)}}}}},dN=new WeakSet,fN=(e,t)=>{if(e.type===1&&KA(e,`once`,!0))return dN.has(e)||t.inVOnce||t.inSSR?void 0:(dN.add(e),t.inVOnce=!0,t.helper(Xk),()=>{t.inVOnce=!1;let e=t.currentNode;e.codegenNode&&=t.cache(e.codegenNode,!0,!0)})},pN=(e,t,n)=>{let{exp:r,arg:i}=e;if(!r)return n.onError(PA(41,e.loc)),mN();let a=r.loc.source.trim(),o=r.type===4?r.content:a,s=n.bindingMetadata[a];if(s===`props`||s===`props-aliased`)return n.onError(PA(44,r.loc)),mN();if(s===`literal-const`||s===`setup-const`)return n.onError(PA(45,r.loc)),mN();if(!o.trim()||!UA(r))return n.onError(PA(42,r.loc)),mN();let c=i||X(`modelValue`,!0),l=i?FA(i)?`onUpdate:${rk(i.content)}`:fA([`"onUpdate:" + `,i]):`onUpdate:modelValue`,u;u=fA([`${n.isTS?`($event: any)`:`$event`} => ((`,r,`) = $event)`]);let d=[dA(c,e.exp),dA(l,u)];if(e.modifiers.length&&t.tagType===1){let t=e.modifiers.map(e=>e.content).map(e=>(RA(e)?e:JSON.stringify(e))+`: true`).join(`, `),n=i?FA(i)?`${i.content}Modifiers`:fA([i,` + "Modifiers"`]):`modelModifiers`;d.push(dA(n,X(`{ ${t} }`,!1,e.loc,2)))}return mN(d)};function mN(e=[]){return{props:e}}var hN=/[\w).+\-_$\]]/,gN=(e,t)=>{AA(`COMPILER_FILTERS`,t)&&(e.type===5?_N(e.content,t):e.type===1&&e.props.forEach(e=>{e.type===7&&e.name!==`for`&&e.exp&&_N(e.exp,t)}))};function _N(e,t){if(e.type===4)vN(e,t);else for(let n=0;n<e.children.length;n++){let r=e.children[n];typeof r==`object`&&(r.type===4?vN(r,t):r.type===8?_N(e,t):r.type===5&&_N(r.content,t))}}function vN(e,t){let n=e.content,r=!1,i=!1,a=!1,o=!1,s=0,c=0,l=0,u=0,d,f,p,m,h=[];for(p=0;p<n.length;p++)if(f=d,d=n.charCodeAt(p),r)d===39&&f!==92&&(r=!1);else if(i)d===34&&f!==92&&(i=!1);else if(a)d===96&&f!==92&&(a=!1);else if(o)d===47&&f!==92&&(o=!1);else if(d===124&&n.charCodeAt(p+1)!==124&&n.charCodeAt(p-1)!==124&&!s&&!c&&!l)m===void 0?(u=p+1,m=n.slice(0,p).trim()):g();else{switch(d){case 34:i=!0;break;case 39:r=!0;break;case 96:a=!0;break;case 40:l++;break;case 41:l--;break;case 91:c++;break;case 93:c--;break;case 123:s++;break;case 125:s--;break}if(d===47){let e=p-1,t;for(;e>=0&&(t=n.charAt(e),t===` `);e--);(!t||!hN.test(t))&&(o=!0)}}m===void 0?m=n.slice(0,p).trim():u!==0&&g();function g(){h.push(n.slice(u,p).trim()),u=p+1}if(h.length){for(p=0;p<h.length;p++)m=yN(m,h[p],t);e.content=m,e.ast=void 0}}function yN(e,t,n){n.helper(Fk);let r=t.indexOf(`(`);if(r<0)return n.filters.add(t),`${aj(t,`filter`)}(${e})`;{let i=t.slice(0,r),a=t.slice(r+1);return n.filters.add(i),`${aj(i,`filter`)}(${e}${a===`)`?a:`,`+a}`}}var bN=new WeakSet,xN=(e,t)=>{if(e.type===1){let n=KA(e,`memo`);return!n||bN.has(e)||t.inSSR?void 0:(bN.add(e),()=>{let r=e.codegenNode||t.currentNode.codegenNode;r&&r.type===13&&(e.tagType!==1&&bA(r,t),e.codegenNode=pA(t.helper(nA),[n.exp,mA(void 0,r),`_cache`,String(t.cached.length)]),t.cached.push(null))})}},SN=(e,t)=>{if(e.type===1){for(let n of e.props)if(n.type===7&&n.name===`bind`&&(!n.exp||n.exp.type===4&&!n.exp.content.trim())&&n.arg){let e=n.arg;if(e.type!==4||!e.isStatic)t.onError(PA(53,e.loc)),n.exp=X(``,!0,e.loc);else{let t=rk(e.content);(zA.test(t[0])||t[0]===`-`)&&(n.exp=X(t,!1,e.loc))}}}};function CN(e){return[[SN,fN,NM,xN,zM,...[gN],...[],aN,ZM,GM,uN],{on:sN,bind:cN,model:pN}]}function wN(e,t={}){let n=t.onError||MA,r=t.mode===`module`;t.prefixIdentifiers===!0?n(PA(48)):r&&n(PA(49)),t.cacheHandlers&&n(PA(50)),t.scopeId&&!r&&n(PA(51));let i=JO({},t,{prefixIdentifiers:!1}),a=XO(e)?Jj(e,i):e,[o,s]=CN();return iM(a,JO({},i,{nodeTransforms:[...o,...t.nodeTransforms||[]],directiveTransforms:JO({},s,t.directiveTransforms||{})})),fM(a,i)}var TN=()=>({props:[]}),EN=Symbol(``),DN=Symbol(``),ON=Symbol(``),kN=Symbol(``),AN=Symbol(``),jN=Symbol(``),MN=Symbol(``),NN=Symbol(``),PN=Symbol(``),FN=Symbol(``);aA({[EN]:`vModelRadio`,[DN]:`vModelCheckbox`,[ON]:`vModelText`,[kN]:`vModelSelect`,[AN]:`vModelDynamic`,[jN]:`withModifiers`,[MN]:`withKeys`,[NN]:`vShow`,[PN]:`Transition`,[FN]:`TransitionGroup`});var IN;function LN(e,t=!1){return IN||=document.createElement(`div`),t?(IN.innerHTML=`<div foo="${e.replace(/"/g,`&quot;`)}">`,IN.children[0].getAttribute(`foo`)):(IN.innerHTML=e,IN.textContent)}var RN={parseMode:`html`,isVoidTag:vk,isNativeTag:e=>hk(e)||gk(e)||_k(e),isPreTag:e=>e===`pre`,isIgnoreNewlineTag:e=>e===`pre`||e===`textarea`,decodeEntities:LN,isBuiltInComponent:e=>{if(e===`Transition`||e===`transition`)return PN;if(e===`TransitionGroup`||e===`transition-group`)return FN},getNamespace(e,t,n){let r=t?t.ns:n;if(t&&r===2)if(t.tag===`annotation-xml`){if(e===`svg`)return 1;t.props.some(e=>e.type===6&&e.name===`encoding`&&e.value!=null&&(e.value.content===`text/html`||e.value.content===`application/xhtml+xml`))&&(r=0)}else /^m(?:[ions]|text)$/.test(t.tag)&&e!==`mglyph`&&e!==`malignmark`&&(r=0);else t&&r===1&&(t.tag===`foreignObject`||t.tag===`desc`||t.tag===`title`)&&(r=0);if(r===0){if(e===`svg`)return 1;if(e===`math`)return 2}return r}},zN=e=>{e.type===1&&e.props.forEach((t,n)=>{t.type===6&&t.name===`style`&&t.value&&(e.props[n]={type:7,name:`bind`,arg:X(`style`,!0,t.loc),exp:BN(t.value.content,t.loc),modifiers:[],loc:t.loc})})},BN=(e,t)=>{let n=uk(e);return X(JSON.stringify(n),!1,t,3)};function VN(e,t){return PA(e,t,void 0)}var HN=(e,t,n)=>{let{exp:r,loc:i}=e;return r||n.onError(VN(54,i)),t.children.length&&(n.onError(VN(55,i)),t.children.length=0),{props:[dA(X(`innerHTML`,!0,i),r||X(``,!0))]}},UN=(e,t,n)=>{let{exp:r,loc:i}=e;return r||n.onError(VN(56,i)),t.children.length&&(n.onError(VN(57,i)),t.children.length=0),{props:[dA(X(`textContent`,!0),r?Qj(r,n)>0?r:pA(n.helperString(Bk),[r],i):X(``,!0))]}},WN=(e,t,n)=>{let r=pN(e,t,n);if(!r.props.length||t.tagType===1)return r;e.arg&&n.onError(VN(59,e.arg.loc));let{tag:i}=t,a=n.isCustomElement(i);if(i===`input`||i===`textarea`||i===`select`||a){let o=ON,s=!1;if(i===`input`||a){let r=qA(t,`type`);if(r){if(r.type===7)o=AN;else if(r.value)switch(r.value.content){case`radio`:o=EN;break;case`checkbox`:o=DN;break;case`file`:s=!0,n.onError(VN(60,e.loc));break;default:break}}else YA(t)&&(o=AN)}else i===`select`&&(o=kN);s||(r.needRuntime=n.helper(o))}else n.onError(VN(58,e.loc));return r.props=r.props.filter(e=>!(e.key.type===4&&e.key.content===`modelValue`)),r},GN=UO(`passive,once,capture`),KN=UO(`stop,prevent,self,ctrl,shift,alt,meta,exact,middle`),qN=UO(`left,right`),JN=UO(`onkeyup,onkeydown,onkeypress`),YN=(e,t,n,r)=>{let i=[],a=[],o=[];for(let s=0;s<t.length;s++){let c=t[s].content;c===`native`&&jA(`COMPILER_V_ON_NATIVE`,n,r)||GN(c)?o.push(c):qN(c)?FA(e)?JN(e.content.toLowerCase())?i.push(c):a.push(c):(i.push(c),a.push(c)):KN(c)?a.push(c):i.push(c)}return{keyModifiers:i,nonKeyModifiers:a,eventOptionModifiers:o}},XN=(e,t)=>FA(e)&&e.content.toLowerCase()===`onclick`?X(t,!0):e.type===4?e:fA([`(`,e,`) === "onClick" ? "${t}" : (`,e,`)`]),ZN=(e,t,n)=>sN(e,t,n,t=>{let{modifiers:r}=e;if(!r.length)return t;let{key:i,value:a}=t.props[0],{keyModifiers:o,nonKeyModifiers:s,eventOptionModifiers:c}=YN(i,r,n,e.loc);if(s.includes(`right`)&&(i=XN(i,`onContextmenu`)),s.includes(`middle`)&&(i=XN(i,`onMouseup`)),s.length&&(a=pA(n.helper(jN),[a,JSON.stringify(s)])),o.length&&(!FA(i)||JN(i.content.toLowerCase()))&&(a=pA(n.helper(MN),[a,JSON.stringify(o)])),c.length){let e=c.map(ik).join(``);i=FA(i)?X(`${i.content}${e}`,!0):fA([`(`,i,`) + "${e}"`])}return{props:[dA(i,a)]}}),QN=(e,t,n)=>{let{exp:r,loc:i}=e;return r||n.onError(VN(62,i)),{props:[],needRuntime:n.helper(NN)}},$N=(e,t)=>{e.type===1&&e.tagType===0&&(e.tag===`script`||e.tag===`style`)&&t.removeNode()},eP=[zN,...[]],tP={cloak:TN,html:HN,text:UN,model:WN,on:ZN,show:QN};function nP(e,t={}){return wN(e,JO({},RN,t,{nodeTransforms:[$N,...eP,...t.nodeTransforms||[]],directiveTransforms:JO({},tP,t.directiveTransforms||{}),transformHoist:null}))}var rP=Object.create(null);function iP(e,t){if(!XO(e))if(e.nodeType)e=e.innerHTML;else return GO;let n=ok(e,t),r=rP[n];if(r)return r;if(e[0]===`#`){let t=document.querySelector(e);e=t?t.innerHTML:``}let i=JO({hoistStatic:!0,onError:void 0,onWarn:GO},t);!i.isCustomElement&&typeof customElements<`u`&&(i.isCustomElement=e=>!!customElements.get(e));let{code:a}=nP(e,i),o=Function(`Vue`,a)(NE);return o._rc=!0,rP[n]=o}FT(iP);var aP=rg(),oP=Zv(aP.get());aP.subscribe(()=>{oP.value=aP.get()});function sP(){aP.reset(),oP.value=aP.get()}var cP={created(){if(!this.$options.remember)return;Array.isArray(this.$options.remember)&&(this.$options.remember={data:this.$options.remember}),typeof this.$options.remember==`string`&&(this.$options.remember={data:[this.$options.remember]}),typeof this.$options.remember.data==`string`&&(this.$options.remember={data:[this.$options.remember.data]});let e=this.$options.remember.key instanceof Function?this.$options.remember.key.call(this):this.$options.remember.key,t=Zg.restore(e),n=this.$options.remember.data.filter(e=>!(this[e]!==null&&typeof this[e]==`object`&&this[e].__rememberable===!1)),r=e=>this[e]!==null&&typeof this[e]==`object`&&typeof this[e].__remember==`function`&&typeof this[e].__restore==`function`;n.forEach(i=>{this[i]!==void 0&&t!==void 0&&t[i]!==void 0&&(r(i)?this[i].__restore(t[i]):this[i]=t[i]),this.$watch(i,()=>{Zg.remember(n.reduce((e,t)=>({...e,[t]:V(r(t)?this[t].__remember():this[t])}),{}),e)},{immediate:!0,deep:!0})})}};function lP(e){let{data:t,rememberKey:n}=e,{precognitionEndpoint:r}=e,i=typeof t==`function`,a=()=>i?t():t,o=n?Zg.restore(n):null,s=V(o?.data??V(a())),c=e=>e,l=null,u=null,d=()=>u??KP.get(`form.withAllErrors`),f,p=!1,m=[],h=Rv({...V(s),isDirty:!1,errors:{},hasErrors:!1,processing:!1,progress:null,wasSuccessful:!1,recentlySuccessful:!1,withPrecognition(...e){r=Jh.createWayfinderCallback(...e);let t=this,n=am(e=>{let{method:t,url:n}=r(),i=V(c(this.data()));return e[t](n,i)},V(s));l=n,n.on(`validatingChanged`,()=>{t.validating=n.validating()}).on(`validatedChanged`,()=>{t.__valid=n.valid()}).on(`touchedChanged`,()=>{t.__touched=n.touched()}).on(`errorsChanged`,()=>{let e=d()?n.errors():om(n.errors());this.errors={},this.setError(e),t.__valid=n.valid()});let i=(e,t)=>(t(e),e);return Object.assign(t,{__touched:[],__valid:[],validating:!1,validator:()=>n,withAllErrors:()=>i(t,()=>u=!0),valid:e=>t.__valid.includes(e),invalid:e=>e in this.errors,setValidationTimeout:e=>i(t,()=>n.setTimeout(e)),validateFiles:()=>i(t,()=>n.validateFiles()),withoutFileValidation:()=>i(t,()=>n.withoutFileValidation()),touch:(e,...r)=>(Array.isArray(e)?n.touch(e):typeof e==`string`?n.touch([e,...r]):n.touch(e),t),touched:e=>typeof e==`string`?t.__touched.includes(e):t.__touched.length>0,validate:(e,r)=>{if(typeof e==`object`&&!(`target`in e)&&(r=e,e=void 0),e===void 0)n.validate(r);else{let t=cm(e),i=c(this.data());n.validate(t,ep(i,t),r)}return t},setErrors:e=>i(t,()=>this.setError(e)),forgetError:e=>i(t,()=>this.clearErrors(cm(e)))}),t},data(){return Object.keys(s).reduce((e,t)=>gp(e,t,ep(this,t)),{})},transform(e){return c=e,this},defaults(e,t){if(i)throw Error("You cannot call `defaults()` when using a function to define your form data.");return p=!0,e===void 0?(s=V(this.data()),this.isDirty=!1):s=typeof e==`string`?gp(V(s),e,t):Object.assign({},V(s),e),l?.defaults(s),this},reset(...e){let t=V(i?V(a()):s);return e.length===0?(i&&(s=t),Object.assign(this,t)):e.filter(e=>cp(t,e)).forEach(e=>{i&&gp(s,e,ep(t,e)),gp(this,e,ep(t,e))}),l?.reset(...e),this},setError(e,t){let n=typeof e==`string`?{[e]:t}:e;return Object.assign(this.errors,n),this.hasErrors=Object.keys(this.errors).length>0,l?.setErrors(n),this},clearErrors(...e){return this.errors=Object.keys(this.errors).reduce((t,n)=>({...t,...e.length>0&&!e.includes(n)?{[n]:this.errors[n]}:{}}),{}),this.hasErrors=Object.keys(this.errors).length>0,l&&(e.length===0?l.setErrors({}):e.forEach(l.forgetError)),this},resetAndClearErrors(...e){return this.reset(...e),this.clearErrors(...e),this},__rememberable:n===null,__remember(){let e=this.data();if(m.length>0){let t={...e};return m.forEach(e=>delete t[e]),{data:t,errors:this.errors}}return{data:e,errors:this.errors}},__restore(e){Object.assign(this,e.data),this.setError(e.errors)}});return o?.errors&&h.setError(o.errors),fx(h,()=>{h.isDirty=!Gf(h.data(),s)},{immediate:!0,deep:!0}),fx(h,e=>{if(!n)return;let t=Zg.restore(n),r=V(e.__remember());Gf(t,r)||Zg.remember(r,n)},{immediate:!0,deep:!0}),r&&h.withPrecognition(r),{form:h,setDefaults:e=>{s=e},getTransform:()=>c,getPrecognitionEndpoint:()=>r??null,markAsSuccessful:()=>{h.clearErrors(),h.wasSuccessful=!0,h.recentlySuccessful=!0,f=setTimeout(()=>h.recentlySuccessful=!1,KP.get(`form.recentlySuccessfulDuration`))},wasDefaultsCalledInOnSuccess:()=>p,resetDefaultsCalledInOnSuccess:()=>{p=!1},setRememberExcludeKeys:e=>{m=e},resetBeforeSubmit:()=>{h.wasSuccessful=!1,h.recentlySuccessful=!1,clearTimeout(f)},finishProcessing:()=>{h.processing=!1,h.progress=null},withAllErrors:{enabled:d,enable:()=>{u=!0}}}}var uP=null,dP=!1;function fP(e){if(dP)return;uP===null&&(dP=!0,uP=new Set(Object.keys(pP({}))),dP=!1);let t=Object.keys(e).filter(e=>uP.has(e));t.length>0&&console.error(`[Inertia] useForm() data contains field(s) that conflict with form properties: ${t.map(e=>`"${e}"`).join(`, `)}. These fields will be overwritten by form methods/properties. Please rename these fields.`)}function pP(...e){let{rememberKey:t,data:n,precognitionEndpoint:r}=Jh.parseUseFormArguments(...e);fP(V(typeof n==`function`?n():n));let i=null,a=null,{form:o,setDefaults:s,getTransform:c,getPrecognitionEndpoint:l,markAsSuccessful:u,wasDefaultsCalledInOnSuccess:d,resetDefaultsCalledInOnSuccess:f,setRememberExcludeKeys:p,resetBeforeSubmit:m,finishProcessing:h}=lP({data:n,rememberKey:t,precognitionEndpoint:r}),g=o,_=e=>(t,n={})=>{g.submit(e,t,n)};return Object.assign(g,{submit(...e){let{method:t,url:n,options:r}=Jh.parseSubmitArguments(e,l());f();let o={...r,onCancelToken:e=>(i=e,r.onCancelToken?.(e)),onBefore:e=>(m(),r.onBefore?.(e)),onStart:e=>(g.processing=!0,r.onStart?.(e)),onProgress:e=>(g.progress=e??null,r.onProgress?.(e)),onSuccess:async e=>{u();let t=r.onSuccess?await r.onSuccess(e):null;return d()||(s(V(g.data())),g.isDirty=!1),t},onError:e=>(g.clearErrors().setError(e),r.onError?.(e)),onCancel:()=>r.onCancel?.(),onFinish:e=>(h(),i=null,r.onFinish?.(e))};o.optimistic=o.optimistic??a??void 0,a=null;let p=c()(g.data());t===`delete`?Zg.delete(n,{...o,data:p}):Zg[t](n,p,o)},get:_(`get`),post:_(`post`),put:_(`put`),patch:_(`patch`),delete:_(`delete`),cancel(){i&&i.cancel()},dontRemember(...e){return p(e),g},optimistic(e){return a=e,g}}),l(),g}function mP(e){if(!e)return!1;if(typeof e==`function`)return!0;if(typeof e==`object`){let t=e;return typeof t.render==`function`||typeof t.setup==`function`||typeof t.template==`string`||`__file`in t||`__name`in t}return!1}function hP(e){if(typeof e!=`function`)return!1;let t=e;return t.length===2&&t.prototype===void 0}var gP=Zv(void 0),$=Zv(),_P=null,vP=Qv(null),yP=Zv(void 0),bP,xP=Gx({name:`Inertia`,props:{initialPage:{type:Object,required:!0},initialComponent:{type:Object,required:!1},resolveComponent:{type:Function,required:!1},titleCallback:{type:Function,required:!1,default:e=>e},onHeadUpdate:{type:Function,required:!1,default:()=>()=>{}},defaultLayout:{type:Function,required:!1}},setup({initialPage:e,initialComponent:t,resolveComponent:n,titleCallback:r,onHeadUpdate:i,defaultLayout:a}){gP.value=t?qv(t):void 0,$.value={...e,flash:e.flash??{}},yP.value=void 0;let o=typeof window>`u`;return bP=ng(o,r||(e=>e),i||(()=>{})),o||(Zg.init({initialPage:e,resolveComponent:n,swapComponent:async e=>{e.preserveState||sP(),gP.value=qv(e.component),$.value=e.page,yP.value=e.preserveState?yP.value:Date.now()},onFlash:e=>{$.value={...$.value,flash:e}}}),Zg.on(`navigate`,()=>bP.forceUpdate())),()=>{if(gP.value){gP.value.inheritAttrs=!!gP.value.inheritAttrs;let e=UT(gP.value,{...$.value.props,key:yP.value});if(vP.value&&=(gP.value.layout=vP.value,null),gP.value.layout&&hP(gP.value.layout))return gP.value.layout(UT,e);let t,n=null,r=gP.value.layout;if(typeof r==`function`&&r.length<=1&&r.prototype===void 0){let e=r($.value.props);lg(e,mP)?(t=a?.($.value.component,$.value),n=e):t=e}else cg(r,mP)?(t=a?.($.value.component,$.value),n=r):t=r??a?.($.value.component,$.value);if(t){let r=fg(t,mP,gP.value.layout&&!n?hP:void 0);if(n&&(r=r.map(e=>({...e,props:{...e.props,...n}}))),r.length>0){let t=o?{shared:{},named:{}}:oP.value;return r.reduceRight((e,n)=>{let r=n.component;return r.inheritAttrs=!!r.inheritAttrs,UT(r,{...$.value.props,...n.props,...t.shared,...n.name&&t.named[n.name]||{}},()=>e)},e)}}return e}}}}),SP={install(e){Zg.form=pP,Object.defineProperty(e.config.globalProperties,`$inertia`,{get:()=>Zg}),Object.defineProperty(e.config.globalProperties,`$page`,{get:()=>$.value}),Object.defineProperty(e.config.globalProperties,`$headManager`,{get:()=>bP}),e.mixin(cP)}};function CP(){return _P||=Rv({props:Y(()=>$.value?.props),url:Y(()=>$.value?.url),component:Y(()=>$.value?.component),version:Y(()=>$.value?.version),clearHistory:Y(()=>$.value?.clearHistory),deferredProps:Y(()=>$.value?.deferredProps),mergeProps:Y(()=>$.value?.mergeProps),prependProps:Y(()=>$.value?.prependProps),deepMergeProps:Y(()=>$.value?.deepMergeProps),matchPropsOn:Y(()=>$.value?.matchPropsOn),rememberedState:Y(()=>$.value?.rememberedState),encryptHistory:Y(()=>$.value?.encryptHistory),scrollProps:Y(()=>$.value?.scrollProps),flash:Y(()=>$.value?.flash)}),_P}async function wP({id:e=`app`,resolve:t,setup:n,title:r,progress:i={},page:a,render:o,defaults:s={},http:c,layout:l,withApp:u}={}){KP.replace(s),c&&zh.setClient(c);let d=typeof window>`u`,f=(e,n)=>Promise.resolve(t(e,n)).then(e=>e.default||e);if(d&&!a&&!o)return async(t,i)=>{let a=[],o={initialPage:t,initialComponent:await f(t.component,t),resolveComponent:f,titleCallback:r,onHeadUpdate:e=>a=e,defaultLayout:l},s;n?s=n({el:null,App:xP,props:o,plugin:SP}):(s=RO({render:()=>UT(xP,o)}),s.use(SP),u&&u(s,{ssr:!0}));let c=Xg(e,t,await i(s));return{head:a,body:c}};let p=a||Um(e),m=[],h=await Promise.all([f(p.component,p),Zg.decryptHistory().catch(()=>{})]).then(([t])=>{let i={initialPage:p,initialComponent:t,resolveComponent:f,titleCallback:r,onHeadUpdate:d?e=>m=e:void 0,defaultLayout:l};if(d)return n({el:null,App:xP,props:i,plugin:SP});let a=document.getElementById(e);if(n)return n({el:a,App:xP,props:i,plugin:SP});if(a.hasAttribute(`data-server-rendered`)){let e=RO({render:()=>UT(xP,i)});e.use(SP),u&&u(e,{ssr:!1}),e.mount(a)}else{let e=LO({render:()=>UT(xP,i)});e.use(SP),u&&u(e,{ssr:!1}),e.mount(a)}});if(!d&&i&&Hg(i),d&&o&&h){let t=Xg(e,p,await o(h));return{head:m,body:t}}}var TP=(e,t,n)=>e.length===0&&t.length===0?!0:e.length>0?n.some(t=>e.includes(t)):n.some(e=>!t.includes(e)),EP=Gx({name:`Deferred`,props:{data:{type:[String,Array],required:!0}},slots:Object,setup(e,{slots:t}){let n=Zv(!1),r=new Set,i=CP(),a=null,o=null;return MS(()=>{let t=Array.isArray(e.data)?e.data:[e.data];a=Zg.on(`start`,e=>{let i=e.detail.visit;i.preserveState===!0&&ph(i.url,window.location)&&TP(i.only,i.except,t)&&(r.add(i),n.value=!0)}),o=Zg.on(`finish`,e=>{let t=e.detail.visit;r.has(t)&&(r.delete(t),n.value=r.size>0)})}),IS(()=>{a?.(),o?.(),r.clear()}),()=>{let r=Array.isArray(e.data)?e.data:[e.data];if(!t.fallback)throw Error("`<Deferred>` requires a `<template #fallback>` slot");return r.every(e=>ep(i.props,e)!==void 0)?t.default?.({reloading:n.value}):t.fallback({})}}}),DP=()=>void 0,OP=Symbol(`InertiaFormContext`),kP=Gx({name:`Form`,slots:Object,props:{action:{type:[String,Object],default:``},method:{type:String,default:`get`},headers:{type:Object,default:()=>({})},queryStringArrayFormat:{type:String,default:`brackets`},errorBag:{type:[String,null],default:null},showProgress:{type:Boolean,default:!0},transform:{type:Function,default:e=>e},options:{type:Object,default:()=>({})},resetOnError:{type:[Boolean,Array],default:!1},resetOnSuccess:{type:[Boolean,Array],default:!1},setDefaultsOnSuccess:{type:Boolean,default:!1},onCancelToken:{type:Function,default:DP},onBefore:{type:Function,default:DP},onStart:{type:Function,default:DP},onProgress:{type:Function,default:DP},onFinish:{type:Function,default:DP},onCancel:{type:Function,default:DP},onSuccess:{type:Function,default:DP},onError:{type:Function,default:DP},onSubmitComplete:{type:Function,default:DP},disableWhileProcessing:{type:Boolean,default:!1},invalidateCacheTags:{type:[String,Array],default:()=>[]},validateFiles:{type:Boolean,default:!1},validationTimeout:{type:Number,default:1500},optimistic:{type:Function,default:void 0},withAllErrors:{type:Boolean,default:null},component:{type:String,default:null},instant:{type:Boolean,default:!1}},setup(e,{slots:t,attrs:n,expose:r}){let i=()=>{let[t,n]=h();return e.transform(n)},a=pP({}).withPrecognition(()=>s.value,()=>h()[0]).transform(i).setValidationTimeout(e.validationTimeout);e.validateFiles&&a.validateFiles(),(e.withAllErrors??um.get(`form.withAllErrors`))&&a.withAllErrors();let o=Zv(),s=Y(()=>mh(e.action)?e.action.method:e.method.toLowerCase()),c=Y(()=>e.component?e.component:e.instant&&mh(e.action)?hh(e.action):null),l=Zv(!1),u=Zv(new FormData),d=e=>{e.type===`reset`&&e.detail?.[Ug]&&e.preventDefault(),l.value=e.type===`reset`?!1:!Gf(m(),eg(u.value))},f=[`input`,`change`,`reset`];MS(()=>{u.value=p(),a.defaults(m()),f.forEach(e=>o.value.addEventListener(e,d))}),fx(()=>e.validateFiles,e=>e?a.validateFiles():a.withoutFileValidation()),fx(()=>e.validationTimeout,e=>a.setValidationTimeout(e)),FS(()=>f.forEach(e=>o.value?.removeEventListener(e,d)));let p=e=>new FormData(o.value,e),m=e=>eg(p(e)),h=t=>lh(s.value,mh(e.action)?e.action.url:e.action,m(t),e.queryStringArrayFormat),g=t=>{let[n,r]=h(t);if(t?.getAttribute(`formtarget`)===`_blank`&&s.value===`get`){window.open(n,`_blank`);return}let o=e=>{e&&(e===!0?_():e.length>0&&_(...e))},l={headers:e.headers,queryStringArrayFormat:e.queryStringArrayFormat,errorBag:e.errorBag,showProgress:e.showProgress,invalidateCacheTags:e.invalidateCacheTags,component:c.value,optimistic:e.optimistic?t=>e.optimistic(t,r):void 0,onCancelToken:e.onCancelToken,onBefore:e.onBefore,onStart:e.onStart,onProgress:e.onProgress,onFinish:e.onFinish,onCancel:e.onCancel,onSuccess:(...t)=>{e.onSuccess?.(...t),e.onSubmitComplete?.(x),o(e.resetOnSuccess),e.setDefaultsOnSuccess===!0&&b()},onError:(...t)=>{e.onError?.(...t),o(e.resetOnError)},...e.options};a.transform(()=>e.transform(r)).submit(s.value,n,l),a.transform(i)},_=(...e)=>{Yg(o.value,u.value,e),a.reset(...e)},v=(...e)=>{a.clearErrors(...e)},y=(...e)=>{v(...e),_(...e)},b=()=>{u.value=p(),l.value=!1},x={get errors(){return a.errors},get hasErrors(){return a.hasErrors},get processing(){return a.processing},get progress(){return a.progress},get wasSuccessful(){return a.wasSuccessful},get recentlySuccessful(){return a.recentlySuccessful},get validating(){return a.validating},clearErrors:v,resetAndClearErrors:y,setError:(e,t)=>a.setError(typeof e==`string`?{[e]:t}:e),get isDirty(){return l.value},reset:_,submit:g,defaults:b,getData:m,getFormData:p,touch:a.touch,valid:a.valid,invalid:a.invalid,touched:a.touched,validate:(t,n)=>a.validate(...Jh.mergeHeadersForValidation(t,n,e.headers)),validator:()=>a.validator()};return r(x),ix(OP,x),()=>UT(`form`,{...n,ref:o,action:mh(e.action)?e.action.url:e.action,method:s.value,onSubmit:e=>{e.preventDefault(),g(e.submitter)},inert:e.disableWhileProcessing&&a.processing},t.default?t.default(x):[])}});function AP(e){return typeof e.type==`string`&&[`area`,`base`,`br`,`col`,`embed`,`hr`,`img`,`input`,`keygen`,`link`,`meta`,`param`,`source`,`track`,`wbr`].indexOf(e.type)>-1}function jP(e){e.props=e.props||{},e.props[`data-inertia`]=e.props[`head-key`]===void 0?``:e.props[`head-key`];let t=Object.keys(e.props).reduce((t,n)=>{let r=String(e.props[n]);return[`key`,`head-key`].includes(n)?t:r===``?t+` ${n}`:t+` ${n}="${Sp(r)}"`},``);return`<${String(e.type)}${t}>`}function MP(e){let{children:t}=e;return typeof t==`string`?t:Array.isArray(t)?t.reduce((e,t)=>e+RP(t),``):``}function NP(e){return typeof e.type==`function`}function PP(e){return typeof e.type==`object`}function FP(e){return/(comment|cmt)/i.test(e.type.toString())}function IP(e){return/(fragment|fgt|symbol\(\))/i.test(e.type.toString())}function LP(e){return/(text|txt)/i.test(e.type.toString())}function RP(e){if(LP(e))return String(e.children);if(IP(e)||FP(e))return``;let t=jP(e);return e.children&&(t+=MP(e)),AP(e)||(t+=`</${String(e.type)}>`),t}function zP(e,t){return t&&!e.find(e=>e.startsWith(`<title`))&&e.push(`<title data-inertia="">${t}</title>`),e}function BP(e,t){return zP(e.flatMap(e=>VP(e)).map(e=>RP(e)).filter(e=>e),t)}function VP(e){return NP(e)?VP(e.type()):PP(e)?(console.warn(`Using components in the <Head> component is not supported.`),[]):LP(e)&&e.children?e:IP(e)&&e.children?e.children.flatMap(e=>VP(e)):FP(e)?[]:e}var HP=Gx({props:{title:{type:String,required:!1}},setup(e,{slots:t}){let n=bP.createProvider();return FS(()=>{n.disconnect()}),()=>{n.update(BP(t.default?t.default():[],e.title))}}}),UP=()=>{},WP=Gx({name:`Link`,props:{as:{type:[String,Object],default:`a`},data:{type:Object,default:()=>({})},href:{type:[String,Object],default:``},method:{type:String,default:`get`},replace:{type:Boolean,default:!1},preserveScroll:{type:[Boolean,String,Function],default:!1},preserveState:{type:[Boolean,String,Function],default:null},preserveUrl:{type:Boolean,default:!1},only:{type:Array,default:()=>[]},except:{type:Array,default:()=>[]},headers:{type:Object,default:()=>({})},queryStringArrayFormat:{type:String,default:`brackets`},async:{type:Boolean,default:!1},prefetch:{type:[Boolean,String,Array],default:!1},cacheFor:{type:[Number,String,Array],default:0},onStart:{type:Function,default:UP},onProgress:{type:Function,default:UP},onFinish:{type:Function,default:UP},onBefore:{type:Function,default:UP},onCancel:{type:Function,default:UP},onSuccess:{type:Function,default:UP},onError:{type:Function,default:UP},onCancelToken:{type:Function,default:UP},onPrefetching:{type:Function,default:UP},onPrefetched:{type:Function,default:UP},cacheTags:{type:[String,Array],default:()=>[]},viewTransition:{type:[Boolean,Object],default:!1},component:{type:String,default:null},instant:{type:Boolean,default:!1},pageProps:{type:[Object,Function],default:null}},setup(e,{slots:t,attrs:n}){let r=Zv(0),i=Zv(),a=Y(()=>e.prefetch===!0?[`hover`]:e.prefetch===!1?[]:Array.isArray(e.prefetch)?e.prefetch:[e.prefetch]),o=Y(()=>e.cacheFor===0?a.value.length===1&&a.value[0]===`click`?0:KP.get(`prefetch.cacheFor`):e.cacheFor);MS(()=>{a.value.includes(`mount`)&&g()}),IS(()=>{clearTimeout(i.value)});let s=Y(()=>mh(e.href)?e.href.method:(e.method??`get`).toLowerCase()),c=Y(()=>typeof e.as!=`string`||e.as.toLowerCase()!==`a`?e.as:s.value===`get`?e.as.toLowerCase():`button`),l=Y(()=>lh(s.value,mh(e.href)?e.href.url:e.href,e.data||{},e.queryStringArrayFormat)),u=Y(()=>l.value[0]),d=Y(()=>l.value[1]),f=Y(()=>e.component?e.component:e.instant&&mh(e.href)?hh(e.href):null),p=Y(()=>c.value===`button`?{type:`button`}:c.value===`a`||typeof c.value!=`string`?{href:u.value}:{}),m=Y(()=>({data:d.value,method:s.value,replace:e.replace,preserveScroll:e.preserveScroll,preserveState:e.preserveState??s.value!==`get`,preserveUrl:e.preserveUrl,only:e.only,except:e.except,headers:e.headers,async:e.async,component:f.value,pageProps:e.pageProps})),h=Y(()=>({...m.value,viewTransition:e.viewTransition,onCancelToken:e.onCancelToken,onBefore:e.onBefore,onStart:t=>{r.value++,e.onStart?.(t)},onProgress:e.onProgress,onFinish:t=>{r.value--,e.onFinish?.(t)},onCancel:e.onCancel,onSuccess:e.onSuccess,onError:e.onError})),g=()=>{Zg.prefetch(u.value,{...m.value,onPrefetching:e.onPrefetching,onPrefetched:e.onPrefetched},{cacheFor:o.value,cacheTags:e.cacheTags})},_={onClick:e=>{mg(e)&&(e.preventDefault(),Zg.visit(u.value,h.value))}},v={onMouseenter:()=>{i.value=setTimeout(()=>{g()},KP.get(`prefetch.hoverDelay`))},onMouseleave:()=>{clearTimeout(i.value)},onClick:_.onClick},y={onMousedown:e=>{mg(e)&&(e.preventDefault(),g())},onKeydown:e=>{hg(e)&&(e.preventDefault(),g())},onMouseup:e=>{mg(e)&&(e.preventDefault(),Zg.visit(u.value,h.value))},onKeyup:e=>{hg(e)&&(e.preventDefault(),Zg.visit(u.value,h.value))},onClick:e=>{mg(e)&&e.preventDefault()}};return()=>UT(c.value,{...n,...p.value,"data-loading":r.value>0?``:void 0,...a.value.includes(`hover`)?v:a.value.includes(`click`)?y:_},t)}});function GP(...e){let{rememberKey:t,data:n,precognitionEndpoint:r}=Jh.parseUseFormArguments(...e),i=null,a=null,{form:o,setDefaults:s,getTransform:c,getPrecognitionEndpoint:l,markAsSuccessful:u,wasDefaultsCalledInOnSuccess:d,resetDefaultsCalledInOnSuccess:f,setRememberExcludeKeys:p,resetBeforeSubmit:m,finishProcessing:h,withAllErrors:g}=lP({data:n,rememberKey:t,precognitionEndpoint:r}),_=o;_.response=null;let v=async(e,t,n)=>{if(n.onBefore?.()===!1)return Promise.reject(Error(`Request cancelled by onBefore`));f(),m(),i=new AbortController,n.onCancelToken?.({cancel:()=>i?.abort()}),n.optimistic=n.optimistic??a??void 0,a=null;let r;if(n.optimistic){r=V(_.data());let e=n.optimistic(V(r));e&&Object.keys(e).forEach(t=>{_[t]=e[t]})}_.processing=!0,n.onStart?.();let o=c()(_.data()),l=Jm(o),p=t,v,y;if(e===`get`){let[n]=lh(e,t,o);p=n}else l?v=Xm(o):(v=JSON.stringify(o),y=`application/json`);try{let r=await zh.getClient().request({method:e,url:p,data:v,headers:{Accept:`application/json`,...y?{"Content-Type":y}:{},...n.headers},signal:i.signal,onUploadProgress:e=>{_.progress=e,n.onProgress?.(e)}}),a=r.data?JSON.parse(r.data):null;if(r.status>=200&&r.status<300)return u(),_.response=a,n.onSuccess?.(a,r),d()||s(V(_.data())),_.isDirty=!1,a;throw new kh(`Request failed with status ${r.status}`,r,t)}catch(e){if(r&&Object.keys(r).forEach(e=>{_[e]=r[e]}),e instanceof kh){if(e.response.status===422){let t=JSON.parse(e.response.data).errors||{},r=g.enabled()?t:om(t);_.clearErrors().setError(r),n.onError?.(r)}else n.onHttpException?.(e.response);throw e}throw e instanceof Ah||e instanceof Error&&e.name===`AbortError`?(n.onCancel?.(),new Ah(`Request was cancelled`,t)):(n.onNetworkError?.(e instanceof Error?e:Error(`Unknown error`)),e)}finally{h(),i=null,n.onFinish?.()}},y=e=>async(t,n={})=>v(e,t,n);Object.assign(_,{submit(...e){let t=Jh.parseSubmitArguments(e,l());return v(t.method,t.url,t.options)},get:y(`get`),post:y(`post`),put:y(`put`),patch:y(`patch`),delete:y(`delete`),cancel(){i&&i.abort()},dontRemember(...e){return p(e),_},optimistic(e){return a=e,_},withAllErrors(){return g.enable(),_}});let b=_.withPrecognition;return _.withPrecognition=(...e)=>(b.call(_,...e),_),l(),_}var KP=um.extend({});export{S_ as $,ax as A,GS as B,dT as C,lT as D,CT as E,IS as F,fx as G,zC as H,Jw as I,nx as J,lx as K,ix as L,_T as M,Lb as N,UT as O,MS as P,_b as Q,YS as R,XS as S,Gx as T,fC as U,Kx as V,Jx as W,pb as X,fb as Y,sb as Z,Y as _,ri as _t,WP as a,G as at,pT as b,CP as c,ny as ct,xO as d,Jd as dt,Xv as et,pO as f,Yd as ft,uT as g,Ou as gt,Dx as h,Nu as ht,HP as i,Qv as it,_C as j,ox as k,qE as l,Zg as lt,Hw as m,Gu as mt,EP as n,Rv as nt,pP as o,ry as ot,DO as p,Gd as pt,tx as q,kP as r,Zv as rt,GP as s,ty as st,wP as t,C_ as tt,hD as u,Xd as ut,oT as v,k as vt,sT as w,$w as x,eT as y,ZS as z};