const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["./bg-BG2.js","./bg3.js","./cs-CZ2.js","./cs3.js","./de-DE2.js","./de3.js","./en-AU2.js","./en3.js","./en-GB2.js","./en-US2.js","./es-ES2.js","./es3.js","./fr-FR2.js","./fr3.js","./fr-BE2.js","./hu-HU2.js","./hu3.js","./it-IT2.js","./it3.js","./nl-BE2.js","./nl3.js","./nl-NL2.js","./pl-PL2.js","./pl3.js","./ro-RO2.js","./ro3.js","./ru-RU2.js","./ru3.js","./sk-SK2.js","./sk3.js","./tr-TR.js","./tr.js","./uk-UA2.js","./uk3.js","./bg-BG.js","./bg2.js","./cs-CZ.js","./cs2.js","./de-DE.js","./de2.js","./en-AU.js","./en2.js","./en-GB.js","./en-US.js","./es-ES.js","./es2.js","./fr-FR.js","./fr2.js","./fr-BE.js","./hu-HU.js","./hu2.js","./it-IT.js","./it2.js","./nl-BE.js","./nl2.js","./nl-NL.js","./pl-PL.js","./pl2.js","./ro-RO.js","./ro2.js","./ru-RU.js","./ru2.js","./sk-SK.js","./sk2.js","./uk-UA.js","./uk2.js"])))=>i.map(i=>d[i]);
import{T as Rt,a as F,i as H,x as w,r as ar,Z as lr,B as en,E as K,S as Gn}from"./lit-element.js";import{n as b,t as Fe}from"./property.js";import{e as T,r as he}from"./progress-DOMF4PIT.js";import{e as cr,a as X}from"./query.js";import{_ as E,a as Vs}from"./index2.js";import"./nav-list.ts.js";var gs="",vs="";function tn(i){gs=i}function dr(i=""){if(!gs){const e=document.querySelector("[data-webawesome]");if(e?.hasAttribute("data-webawesome")){const t=new URL(e.getAttribute("data-webawesome")??"",window.location.href).pathname;tn(t)}else{const s=[...document.getElementsByTagName("script")].find(n=>n.src.endsWith("webawesome.js")||n.src.endsWith("webawesome.loader.js")||n.src.endsWith("webawesome.ssr-loader.js"));if(s){const n=String(s.getAttribute("src"));tn(n.split("/").slice(0,-1).join("/"))}}}return gs.replace(/\/$/,"")+(i?`/${i.replace(/^\//,"")}`:"")}function ur(i){vs=i}function hr(){if(!vs){const i=document.querySelector("[data-fa-kit-code]");i&&ur(i.getAttribute("data-fa-kit-code")||"")}return vs}var De="7.0.1";function pr(i,e,t){const s=hr(),n=s.length>0;let o="solid";return e==="notdog"?(t==="solid"&&(o="solid"),t==="duo-solid"&&(o="duo-solid"),`https://ka-p.fontawesome.com/releases/v${De}/svgs/notdog-${o}/${i}.svg?token=${encodeURIComponent(s)}`):e==="chisel"?`https://ka-p.fontawesome.com/releases/v${De}/svgs/chisel-regular/${i}.svg?token=${encodeURIComponent(s)}`:e==="etch"?`https://ka-p.fontawesome.com/releases/v${De}/svgs/etch-solid/${i}.svg?token=${encodeURIComponent(s)}`:e==="jelly"?(t==="regular"&&(o="regular"),t==="duo-regular"&&(o="duo-regular"),t==="fill-regular"&&(o="fill-regular"),`https://ka-p.fontawesome.com/releases/v${De}/svgs/jelly-${o}/${i}.svg?token=${encodeURIComponent(s)}`):e==="slab"?((t==="solid"||t==="regular")&&(o="regular"),t==="press-regular"&&(o="press-regular"),`https://ka-p.fontawesome.com/releases/v${De}/svgs/slab-${o}/${i}.svg?token=${encodeURIComponent(s)}`):e==="thumbprint"?`https://ka-p.fontawesome.com/releases/v${De}/svgs/thumbprint-light/${i}.svg?token=${encodeURIComponent(s)}`:e==="whiteboard"?`https://ka-p.fontawesome.com/releases/v${De}/svgs/whiteboard-semibold/${i}.svg?token=${encodeURIComponent(s)}`:(e==="classic"&&(t==="thin"&&(o="thin"),t==="light"&&(o="light"),t==="regular"&&(o="regular"),t==="solid"&&(o="solid")),e==="sharp"&&(t==="thin"&&(o="sharp-thin"),t==="light"&&(o="sharp-light"),t==="regular"&&(o="sharp-regular"),t==="solid"&&(o="sharp-solid")),e==="duotone"&&(t==="thin"&&(o="duotone-thin"),t==="light"&&(o="duotone-light"),t==="regular"&&(o="duotone-regular"),t==="solid"&&(o="duotone")),e==="sharp-duotone"&&(t==="thin"&&(o="sharp-duotone-thin"),t==="light"&&(o="sharp-duotone-light"),t==="regular"&&(o="sharp-duotone-regular"),t==="solid"&&(o="sharp-duotone-solid")),e==="brands"&&(o="brands"),n?`https://ka-p.fontawesome.com/releases/v${De}/svgs/${o}/${i}.svg?token=${encodeURIComponent(s)}`:`https://ka-f.fontawesome.com/releases/v${De}/svgs/${o}/${i}.svg`)}var fr={name:"default",resolver:(i,e="classic",t="solid")=>pr(i,e,t),mutator:(i,e)=>{if(e?.family&&!i.hasAttribute("data-duotone-initialized")){const{family:t,variant:s}=e;if(t==="duotone"||t==="sharp-duotone"||t==="notdog"&&s==="duo-solid"||t==="jelly"&&s==="duo-regular"||t==="thumbprint"){const n=[...i.querySelectorAll("path")],o=n.find(a=>!a.hasAttribute("opacity")),r=n.find(a=>a.hasAttribute("opacity"));if(!o||!r)return;if(o.setAttribute("data-duotone-primary",""),r.setAttribute("data-duotone-secondary",""),e.swapOpacity&&o&&r){const a=r.getAttribute("opacity")||"0.4";o.style.setProperty("--path-opacity",a),r.style.setProperty("--path-opacity","1")}i.setAttribute("data-duotone-initialized","")}}}},mr=fr;new MutationObserver(i=>{for(const{addedNodes:e}of i)for(const t of e)t.nodeType===Node.ELEMENT_NODE&&br(t)});async function br(i){const e=i instanceof Element?i.tagName.toLowerCase():"",t=e?.startsWith("wa-"),s=[...i.querySelectorAll(":not(:defined)")].map(r=>r.tagName.toLowerCase()).filter(r=>r.startsWith("wa-"));t&&!customElements.get(e)&&s.push(e);const n=[...new Set(s)],o=await Promise.allSettled(n.map(r=>gr(r)));for(const r of o)r.status==="rejected"&&console.warn(r.reason);await new Promise(requestAnimationFrame),i.dispatchEvent(new CustomEvent("wa-discovery-complete",{bubbles:!1,cancelable:!1,composed:!0}))}function gr(i){if(customElements.get(i))return Promise.resolve();const e=i.replace(/^wa-/i,""),t=dr(`components/${e}/${e}.js`);return new Promise((s,n)=>{import(t).then(()=>s()).catch(()=>n(new Error(`Unable to autoload <${i}> from ${t}`)))})}const _s=new Set,ut=new Map;let et,Rs="ltr",zs="en";const Yn=typeof MutationObserver<"u"&&typeof document<"u"&&typeof document.documentElement<"u";if(Yn){const i=new MutationObserver(Xn);Rs=document.documentElement.dir||"ltr",zs=document.documentElement.lang||navigator.language,i.observe(document.documentElement,{attributes:!0,attributeFilter:["dir","lang"]})}function Zn(...i){i.map(e=>{const t=e.$code.toLowerCase();ut.has(t)?ut.set(t,Object.assign(Object.assign({},ut.get(t)),e)):ut.set(t,e),et||(et=e)}),Xn()}function Xn(){Yn&&(Rs=document.documentElement.dir||"ltr",zs=document.documentElement.lang||navigator.language),[..._s.keys()].map(i=>{typeof i.requestUpdate=="function"&&i.requestUpdate()})}let vr=class{constructor(e){this.host=e,this.host.addController(this)}hostConnected(){_s.add(this.host)}hostDisconnected(){_s.delete(this.host)}dir(){return`${this.host.dir||Rs}`.toLowerCase()}lang(){return`${this.host.lang||zs}`.toLowerCase()}getTranslationData(e){var t,s;const n=new Intl.Locale(e.replace(/_/g,"-")),o=n?.language.toLowerCase(),r=(s=(t=n?.region)===null||t===void 0?void 0:t.toLowerCase())!==null&&s!==void 0?s:"",a=ut.get(`${o}-${r}`),c=ut.get(o);return{locale:n,language:o,region:r,primary:a,secondary:c}}exists(e,t){var s;const{primary:n,secondary:o}=this.getTranslationData((s=t.lang)!==null&&s!==void 0?s:this.lang());return t=Object.assign({includeFallback:!1},t),!!(n&&n[e]||o&&o[e]||t.includeFallback&&et&&et[e])}term(e,...t){const{primary:s,secondary:n}=this.getTranslationData(this.lang());let o;if(s&&s[e])o=s[e];else if(n&&n[e])o=n[e];else if(et&&et[e])o=et[e];else return console.error(`No translation found for: ${String(e)}`),String(e);return typeof o=="function"?o(...t):o}date(e,t){return e=new Date(e),new Intl.DateTimeFormat(this.lang(),t).format(e)}number(e,t){return e=Number(e),isNaN(e)?"":new Intl.NumberFormat(this.lang(),t).format(e)}relativeTime(e,t,s){return new Intl.RelativeTimeFormat(this.lang(),s).format(e,t)}};var Qn={$code:"en",$name:"English",$dir:"ltr",carousel:"Carousel",clearEntry:"Clear entry",close:"Close",copied:"Copied",copy:"Copy",currentValue:"Current value",error:"Error",goToSlide:(i,e)=>`Go to slide ${i} of ${e}`,hidePassword:"Hide password",loading:"Loading",nextSlide:"Next slide",numOptionsSelected:i=>i===0?"No options selected":i===1?"1 option selected":`${i} options selected`,pauseAnimation:"Pause animation",playAnimation:"Play animation",previousSlide:"Previous slide",progress:"Progress",remove:"Remove",resize:"Resize",scrollableRegion:"Scrollable region",scrollToEnd:"Scroll to end",scrollToStart:"Scroll to start",selectAColorFromTheScreen:"Select a color from the screen",showPassword:"Show password",slideNum:i=>`Slide ${i}`,toggleColorFormat:"Toggle color format",zoomIn:"Zoom in",zoomOut:"Zoom out"};Zn(Qn);var _r=Qn;var vt=class extends vr{};Zn(_r);function yr(i){return`data:image/svg+xml,${encodeURIComponent(i)}`}var Wi={solid:{check:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M434.8 70.1c14.3 10.4 17.5 30.4 7.1 44.7l-256 352c-5.5 7.6-14 12.3-23.4 13.1s-18.5-2.7-25.1-9.3l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l101.5 101.5 234-321.7c10.4-14.3 30.4-17.5 44.7-7.1z"/></svg>',"chevron-down":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M201.4 406.6c12.5 12.5 32.8 12.5 45.3 0l192-192c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L224 338.7 54.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l192 192z"/></svg>',"chevron-left":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l192 192c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L77.3 256 246.6 86.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-192 192z"/></svg>',"chevron-right":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M311.1 233.4c12.5 12.5 12.5 32.8 0 45.3l-192 192c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L243.2 256 73.9 86.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l192 192z"/></svg>',circle:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0z"/></svg>',eyedropper:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M341.6 29.2l-101.6 101.6-9.4-9.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3l-9.4-9.4 101.6-101.6c39-39 39-102.2 0-141.1s-102.2-39-141.1 0zM55.4 323.3c-15 15-23.4 35.4-23.4 56.6l0 42.4-26.6 39.9c-8.5 12.7-6.8 29.6 4 40.4s27.7 12.5 40.4 4l39.9-26.6 42.4 0c21.2 0 41.6-8.4 56.6-23.4l109.4-109.4-45.3-45.3-109.4 109.4c-3 3-7.1 4.7-11.3 4.7l-36.1 0 0-36.1c0-4.2 1.7-8.3 4.7-11.3l109.4-109.4-45.3-45.3-109.4 109.4z"/></svg>',"grip-vertical":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M128 40c0-22.1-17.9-40-40-40L40 0C17.9 0 0 17.9 0 40L0 88c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zm0 192c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zM0 424l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zM320 40c0-22.1-17.9-40-40-40L232 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zM192 232l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zM320 424c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48z"/></svg>',indeterminate:'<svg part="indeterminate-icon" class="icon" viewBox="0 0 16 16"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" stroke-linecap="round"><g stroke="currentColor" stroke-width="2"><g transform="translate(2.285714 6.857143)"><path d="M10.2857143,1.14285714 L1.14285714,1.14285714"/></g></g></g></svg>',minus:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M0 256c0-17.7 14.3-32 32-32l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 288c-17.7 0-32-14.3-32-32z"/></svg>',pause:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M48 32C21.5 32 0 53.5 0 80L0 432c0 26.5 21.5 48 48 48l64 0c26.5 0 48-21.5 48-48l0-352c0-26.5-21.5-48-48-48L48 32zm224 0c-26.5 0-48 21.5-48 48l0 352c0 26.5 21.5 48 48 48l64 0c26.5 0 48-21.5 48-48l0-352c0-26.5-21.5-48-48-48l-64 0z"/></svg>',play:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M91.2 36.9c-12.4-6.8-27.4-6.5-39.6 .7S32 57.9 32 72l0 368c0 14.1 7.5 27.2 19.6 34.4s27.2 7.5 39.6 .7l336-184c12.8-7 20.8-20.5 20.8-35.1s-8-28.1-20.8-35.1l-336-184z"/></svg>',star:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M309.5-18.9c-4.1-8-12.4-13.1-21.4-13.1s-17.3 5.1-21.4 13.1L193.1 125.3 33.2 150.7c-8.9 1.4-16.3 7.7-19.1 16.3s-.5 18 5.8 24.4l114.4 114.5-25.2 159.9c-1.4 8.9 2.3 17.9 9.6 23.2s16.9 6.1 25 2L288.1 417.6 432.4 491c8 4.1 17.7 3.3 25-2s11-14.2 9.6-23.2L441.7 305.9 556.1 191.4c6.4-6.4 8.6-15.8 5.8-24.4s-10.1-14.9-19.1-16.3L383 125.3 309.5-18.9z"/></svg>',user:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M224 248a120 120 0 1 0 0-240 120 120 0 1 0 0 240zm-29.7 56C95.8 304 16 383.8 16 482.3 16 498.7 29.3 512 45.7 512l356.6 0c16.4 0 29.7-13.3 29.7-29.7 0-98.5-79.8-178.3-178.3-178.3l-59.4 0z"/></svg>',xmark:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M55.1 73.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L147.2 256 9.9 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192.5 301.3 329.9 438.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.8 256 375.1 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192.5 210.7 55.1 73.4z"/></svg>'},regular:{"circle-question":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M464 256a208 208 0 1 0 -416 0 208 208 0 1 0 416 0zM0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0zm256-80c-17.7 0-32 14.3-32 32 0 13.3-10.7 24-24 24s-24-10.7-24-24c0-44.2 35.8-80 80-80s80 35.8 80 80c0 47.2-36 67.2-56 74.5l0 3.8c0 13.3-10.7 24-24 24s-24-10.7-24-24l0-8.1c0-20.5 14.8-35.2 30.1-40.2 6.4-2.1 13.2-5.5 18.2-10.3 4.3-4.2 7.7-10 7.7-19.6 0-17.7-14.3-32-32-32zM224 368a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>',"circle-xmark":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M256 48a208 208 0 1 1 0 416 208 208 0 1 1 0-416zm0 464a256 256 0 1 0 0-512 256 256 0 1 0 0 512zM167 167c-9.4 9.4-9.4 24.6 0 33.9l55 55-55 55c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l55-55 55 55c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-55-55 55-55c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-55 55-55-55c-9.4-9.4-24.6-9.4-33.9 0z"/></svg>',copy:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M384 336l-192 0c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l133.5 0c4.2 0 8.3 1.7 11.3 4.7l58.5 58.5c3 3 4.7 7.1 4.7 11.3L400 320c0 8.8-7.2 16-16 16zM192 384l192 0c35.3 0 64-28.7 64-64l0-197.5c0-17-6.7-33.3-18.7-45.3L370.7 18.7C358.7 6.7 342.5 0 325.5 0L192 0c-35.3 0-64 28.7-64 64l0 256c0 35.3 28.7 64 64 64zM64 128c-35.3 0-64 28.7-64 64L0 448c0 35.3 28.7 64 64 64l192 0c35.3 0 64-28.7 64-64l0-16-48 0 0 16c0 8.8-7.2 16-16 16L64 464c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l16 0 0-48-16 0z"/></svg>',eye:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M288 80C222.8 80 169.2 109.6 128.1 147.7 89.6 183.5 63 226 49.4 256 63 286 89.6 328.5 128.1 364.3 169.2 402.4 222.8 432 288 432s118.8-29.6 159.9-67.7C486.4 328.5 513 286 526.6 256 513 226 486.4 183.5 447.9 147.7 406.8 109.6 353.2 80 288 80zM95.4 112.6C142.5 68.8 207.2 32 288 32s145.5 36.8 192.6 80.6c46.8 43.5 78.1 95.4 93 131.1 3.3 7.9 3.3 16.7 0 24.6-14.9 35.7-46.2 87.7-93 131.1-47.1 43.7-111.8 80.6-192.6 80.6S142.5 443.2 95.4 399.4c-46.8-43.5-78.1-95.4-93-131.1-3.3-7.9-3.3-16.7 0-24.6 14.9-35.7 46.2-87.7 93-131.1zM288 336c44.2 0 80-35.8 80-80 0-29.6-16.1-55.5-40-69.3-1.4 59.7-49.6 107.9-109.3 109.3 13.8 23.9 39.7 40 69.3 40zm-79.6-88.4c2.5 .3 5 .4 7.6 .4 35.3 0 64-28.7 64-64 0-2.6-.2-5.1-.4-7.6-37.4 3.9-67.2 33.7-71.1 71.1zm45.6-115c10.8-3 22.2-4.5 33.9-4.5 8.8 0 17.5 .9 25.8 2.6 .3 .1 .5 .1 .8 .2 57.9 12.2 101.4 63.7 101.4 125.2 0 70.7-57.3 128-128 128-61.6 0-113-43.5-125.2-101.4-1.8-8.6-2.8-17.5-2.8-26.6 0-11 1.4-21.8 4-32 .2-.7 .3-1.3 .5-1.9 11.9-43.4 46.1-77.6 89.5-89.5z"/></svg>',"eye-slash":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M41-24.9c-9.4-9.4-24.6-9.4-33.9 0S-2.3-.3 7 9.1l528 528c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-96.4-96.4c2.7-2.4 5.4-4.8 8-7.2 46.8-43.5 78.1-95.4 93-131.1 3.3-7.9 3.3-16.7 0-24.6-14.9-35.7-46.2-87.7-93-131.1-47.1-43.7-111.8-80.6-192.6-80.6-56.8 0-105.6 18.2-146 44.2L41-24.9zM176.9 111.1c32.1-18.9 69.2-31.1 111.1-31.1 65.2 0 118.8 29.6 159.9 67.7 38.5 35.7 65.1 78.3 78.6 108.3-13.6 30-40.2 72.5-78.6 108.3-3.1 2.8-6.2 5.6-9.4 8.4L393.8 328c14-20.5 22.2-45.3 22.2-72 0-70.7-57.3-128-128-128-26.7 0-51.5 8.2-72 22.2l-39.1-39.1zm182 182l-108-108c11.1-5.8 23.7-9.1 37.1-9.1 44.2 0 80 35.8 80 80 0 13.4-3.3 26-9.1 37.1zM103.4 173.2l-34-34c-32.6 36.8-55 75.8-66.9 104.5-3.3 7.9-3.3 16.7 0 24.6 14.9 35.7 46.2 87.7 93 131.1 47.1 43.7 111.8 80.6 192.6 80.6 37.3 0 71.2-7.9 101.5-20.6L352.2 422c-20 6.4-41.4 10-64.2 10-65.2 0-118.8-29.6-159.9-67.7-38.5-35.7-65.1-78.3-78.6-108.3 10.4-23.1 28.6-53.6 54-82.8z"/></svg>',star:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M288.1-32c9 0 17.3 5.1 21.4 13.1L383 125.3 542.9 150.7c8.9 1.4 16.3 7.7 19.1 16.3s.5 18-5.8 24.4L441.7 305.9 467 465.8c1.4 8.9-2.3 17.9-9.6 23.2s-17 6.1-25 2L288.1 417.6 143.8 491c-8 4.1-17.7 3.3-25-2s-11-14.2-9.6-23.2L134.4 305.9 20 191.4c-6.4-6.4-8.6-15.8-5.8-24.4s10.1-14.9 19.1-16.3l159.9-25.4 73.6-144.2c4.1-8 12.4-13.1 21.4-13.1zm0 76.8L230.3 158c-3.5 6.8-10 11.6-17.6 12.8l-125.5 20 89.8 89.9c5.4 5.4 7.9 13.1 6.7 20.7l-19.8 125.5 113.3-57.6c6.8-3.5 14.9-3.5 21.8 0l113.3 57.6-19.8-125.5c-1.2-7.6 1.3-15.3 6.7-20.7l89.8-89.9-125.5-20c-7.6-1.2-14.1-6-17.6-12.8L288.1 44.8z"/></svg>'}},wr={name:"system",resolver:(i,e="classic",t="solid")=>{let n=Wi[t][i]??Wi.regular[i]??Wi.regular["circle-question"];return n?yr(n):""}},xr=wr;var Er="classic",gi=[mr,xr],vi=[];function kr(i){vi.push(i)}function Cr(i){vi=vi.filter(e=>e!==i)}function ji(i){return gi.find(e=>e.name===i)}function Sr(i,e){Ar(i),gi.push({name:i,resolver:e.resolver,mutator:e.mutator,spriteSheet:e.spriteSheet}),vi.forEach(t=>{t.library===i&&t.setIcon()})}function Ar(i){gi=gi.filter(e=>e.name!==i)}function Tr(){return Er}var Nr=Object.defineProperty,Fr=Object.getOwnPropertyDescriptor,Jn=i=>{throw TypeError(i)},v=(i,e,t,s)=>{for(var n=s>1?void 0:s?Fr(e,t):e,o=i.length-1,r;o>=0;o--)(r=i[o])&&(n=(s?r(e,t,n):r(n))||n);return s&&n&&Nr(e,t,n),n},eo=(i,e,t)=>e.has(i)||Jn("Cannot "+t),Lr=(i,e,t)=>(eo(i,e,"read from private field"),e.get(i)),Or=(i,e,t)=>e.has(i)?Jn("Cannot add the same private member more than once"):e instanceof WeakSet?e.add(i):e.set(i,t),Ir=(i,e,t,s)=>(eo(i,e,"write to private field"),e.set(i,t),t);const Dr={alert:"triangle-exclamation",asc:"arrow-down-short-wide",asset:"image",assets:"image",circleuarr:"circle-arrow-up",collapse:"down-left-and-up-right-to-center",condition:"diamond",darr:"arrow-down",date:"calendar",desc:"arrow-down-wide-short",disabled:"circle-dashed",done:"circle-check",downangle:"angle-down",draft:"scribble",edit:"pencil",enabled:"circle",expand:"up-right-and-down-left-from-center",external:"arrow-up-right-from-square",field:"pen-to-square",help:"circle-question",home:"house",info:"circle-info",insecure:"unlock",larr:"arrow-left",layout:"table-layout",leftangle:"angle-left",listrtl:"list-flip",location:"location-dot",mail:"envelope",menu:"bars",move:"grip-dots",newstamp:"certificate",paperplane:"paper-plane",plugin:"plug",rarr:"arrow-right",refresh:"arrows-rotate",remove:"xmark",rightangle:"angle-right",rotate:"rotate-left",routes:"signs-post",search:"magnifying-glass",secure:"lock",settings:"gear",shareleft:"share-flip",shuteye:"eye-slash","sidebar-left":"sidebar","sidebar-right":"sidebar-flip","sidebar-start":"sidebar","sidebar-end":"sidebar-flip",structure:"list-tree",structurertl:"list-tree-flip",template:"file-code",time:"clock",tool:"wrench",uarr:"arrow-up",upangle:"angle-up",view:"eye",wand:"wand-magic-sparkles"};function $r(i,e="classic",t="regular"){let s="solid",n=t,o=i.endsWith(".svg")?i.split(".svg")[0]:i;if(i.includes("/")){let[r,...a]=i.split("/");n=r??n,o=a.join("/")}return n==="thin"?s="thin":n==="light"?s="light":n==="regular"?s="regular":n==="solid"&&(s="solid"),e==="brands"&&(s="brands"),n==="custom-icons"&&(s="custom-icons"),o=Dr[o]??o,`/vendor/craft/icons/${s}/${o}.svg`}function Mr(){Sr("default",{resolver:(i,e="classic",t="solid")=>$r(i,e,t),mutator:i=>i.setAttribute("fill","currentColor")})}var sn=class extends HTMLElement{constructor(...e){super(...e),this.cookieName=null,this.state="collapsed",this.expanded=!1,this.handleOpen=()=>{this.trigger?.setAttribute("aria-expanded","true"),this.expanded=!0,this.dispatchEvent(new CustomEvent("open")),this.target&&(this.target.dataset.state="expanded"),this.cookieName&&window.Craft?.setCookie(this.cookieName,"expanded")},this.handleClose=()=>{this.trigger?.setAttribute("aria-expanded","false"),this.expanded=!1,this.dispatchEvent(new CustomEvent("close")),this.target&&(this.target.dataset.state="collapsed"),this.cookieName&&window.Craft?.setCookie(this.cookieName,"collapsed")}}get trigger(){return this.querySelector('button[type="button"]')}get target(){if(!this.trigger)return console.warn("No trigger found for disclosure."),null;let e=this.trigger.getAttribute("aria-controls");return e?document.getElementById(e):(console.warn("No target selector found for disclosure."),null)}connectedCallback(){if(!this.trigger){console.error("craft-disclosure elements must include a button",this);return}if(!this.target){console.error(`No target with id ${this.trigger.getAttribute("aria-controls")} found for disclosure. `,this.trigger);return}this.cookieName=this.getAttribute("cookie-name"),this.state=this.getAttribute("state")??"expanded",this.trigger.setAttribute("aria-expanded",this.state==="expanded"?"true":"false"),this.trigger.addEventListener("click",this.toggle.bind(this)),this.state==="expanded"?this.open():this.close()}disconnectedCallback(){this.open(),this.trigger?.removeEventListener("click",this.toggle.bind(this))}attributeChangedCallback(e,t,s){e==="state"&&(s==="expanded"?this.handleOpen():this.handleClose())}toggle(){this.expanded?this.close():this.open()}open(){this.setAttribute("state","expanded")}close(){this.setAttribute("state","collapsed")}};sn.observedAttributes=["state"],customElements.get("craft-disclosure")||customElements.define("craft-disclosure",sn);function ci(i){return(e,t)=>{const{slot:s,selector:n}=i??{},o="slot"+(s?`[name=${s}]`:":not([name])");return cr(e,t,{get(){const r=this.renderRoot?.querySelector(o),a=r?.assignedElements(i)??[];return n===void 0?a:a.filter((c=>c.matches(n)))}})}}const Ni={ATTRIBUTE:1,CHILD:2},Fi=i=>(...e)=>({_$litDirective$:i,values:e});let Li=class{constructor(e){}get _$AU(){return this._$AM._$AU}_$AT(e,t,s){this._$Ct=e,this._$AM=t,this._$Ci=s}_$AS(e,t){return this.update(e,t)}update(e,t){return this.render(...t)}};const pe=Fi(class extends Li{constructor(i){if(super(i),i.type!==Ni.ATTRIBUTE||i.name!=="class"||i.strings?.length>2)throw Error("`classMap()` can only be used in the `class` attribute and must be the only part in the attribute.")}render(i){return" "+Object.keys(i).filter((e=>i[e])).join(" ")+" "}update(i,[e]){if(this.st===void 0){this.st=new Set,i.strings!==void 0&&(this.nt=new Set(i.strings.join(" ").split(/\s/).filter((s=>s!==""))));for(const s in e)e[s]&&!this.nt?.has(s)&&this.st.add(s);return this.render(e)}const t=i.element.classList;for(const s of this.st)s in e||(t.remove(s),this.st.delete(s));for(const s in e){const n=!!e[s];n===this.st.has(s)||this.nt?.has(s)||(n?(t.add(s),this.st.add(s)):(t.remove(s),this.st.delete(s)))}return Rt}});var Vr=F`
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
`,Qt=class extends H{constructor(...e){super(...e),this.visible=!1,this.wrapper=null}show(){this.visible=!0,this.dispatchEvent(new CustomEvent("show"))}hide(){this.visible=!1,this.dispatchEvent(new CustomEvent("hide"))}focus(){this.wrapper?.focus()}render(){return w`
      <div
        tabindex="-1"
        class="${pe({wrapper:!0,hidden:!this.visible})}"
      >
        <div class="spinner"></div>
        <span class="message visually-hidden"><slot /></span>
      </div>
    `}};Qt.styles=[Vr],T([b({reflect:!0})],Qt.prototype,"visible",void 0),T([X(".wrapper")],Qt.prototype,"wrapper",void 0),customElements.get("craft-spinner")||customElements.define("craft-spinner",Qt);var Rr=class extends Event{constructor(){super("wa-reposition",{bubbles:!0,cancelable:!1,composed:!0})}};var zr=`:host {
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
`,di,fe=class extends H{constructor(){super(),Or(this,di,!1),this.initialReflectedProperties=new Map,this.didSSR=!!this.shadowRoot,this.customStates={set:(e,t)=>{if(this.internals?.states)try{t?this.internals.states.add(e):this.internals.states.delete(e)}catch(s){if(String(s).includes("must start with '--'"))console.error("Your browser implements an outdated version of CustomStateSet. Consider using a polyfill");else throw s}},has:e=>{if(!this.internals?.states)return!1;try{return this.internals.states.has(e)}catch{return!1}}};try{this.internals=this.attachInternals()}catch{console.error("Element internals are not supported in your browser. Consider using a polyfill")}this.customStates.set("wa-defined",!0);let i=this.constructor;for(let[e,t]of i.elementProperties)t.default==="inherit"&&t.initial!==void 0&&typeof e=="string"&&this.customStates.set(`initial-${e}-${t.initial}`,!0)}static get styles(){const i=Array.isArray(this.css)?this.css:this.css?[this.css]:[];return[zr,...i].map(e=>typeof e=="string"?ar(e):e)}attributeChangedCallback(i,e,t){Lr(this,di)||(this.constructor.elementProperties.forEach((s,n)=>{s.reflect&&this[n]!=null&&this.initialReflectedProperties.set(n,this[n])}),Ir(this,di,!0)),super.attributeChangedCallback(i,e,t)}willUpdate(i){super.willUpdate(i),this.initialReflectedProperties.forEach((e,t)=>{i.has(t)&&this[t]==null&&(this[t]=e)})}firstUpdated(i){super.firstUpdated(i),this.didSSR&&this.shadowRoot?.querySelectorAll("slot").forEach(e=>{e.dispatchEvent(new Event("slotchange",{bubbles:!0,composed:!1,cancelable:!1}))})}update(i){try{super.update(i)}catch(e){if(this.didSSR&&!this.hasUpdated){const t=new Event("lit-hydration-error",{bubbles:!0,composed:!0,cancelable:!1});t.error=e,this.dispatchEvent(t)}throw e}}relayNativeEvent(i,e){i.stopImmediatePropagation(),this.dispatchEvent(new i.constructor(i.type,{...i,...e}))}};di=new WeakMap;v([b()],fe.prototype,"dir",2);v([b()],fe.prototype,"lang",2);v([b({type:Boolean,reflect:!0,attribute:"did-ssr"})],fe.prototype,"didSSR",2);const Ge=Math.min,ge=Math.max,_i=Math.round,Jt=Math.floor,Le=i=>({x:i,y:i}),Pr={left:"right",right:"left",bottom:"top",top:"bottom"},Br={start:"end",end:"start"};function ys(i,e,t){return ge(i,Ge(e,t))}function _t(i,e){return typeof i=="function"?i(e):i}function Ye(i){return i.split("-")[0]}function yt(i){return i.split("-")[1]}function to(i){return i==="x"?"y":"x"}function Ps(i){return i==="y"?"height":"width"}const Ur=new Set(["top","bottom"]);function Me(i){return Ur.has(Ye(i))?"y":"x"}function Bs(i){return to(Me(i))}function Hr(i,e,t){t===void 0&&(t=!1);const s=yt(i),n=Bs(i),o=Ps(n);let r=n==="x"?s===(t?"end":"start")?"right":"left":s==="start"?"bottom":"top";return e.reference[o]>e.floating[o]&&(r=yi(r)),[r,yi(r)]}function qr(i){const e=yi(i);return[ws(i),e,ws(e)]}function ws(i){return i.replace(/start|end/g,e=>Br[e])}const nn=["left","right"],on=["right","left"],Wr=["top","bottom"],jr=["bottom","top"];function Kr(i,e,t){switch(i){case"top":case"bottom":return t?e?on:nn:e?nn:on;case"left":case"right":return e?Wr:jr;default:return[]}}function Gr(i,e,t,s){const n=yt(i);let o=Kr(Ye(i),t==="start",s);return n&&(o=o.map(r=>r+"-"+n),e&&(o=o.concat(o.map(ws)))),o}function yi(i){return i.replace(/left|right|bottom|top/g,e=>Pr[e])}function Yr(i){return{top:0,right:0,bottom:0,left:0,...i}}function io(i){return typeof i!="number"?Yr(i):{top:i,right:i,bottom:i,left:i}}function wi(i){const{x:e,y:t,width:s,height:n}=i;return{width:s,height:n,top:t,left:e,right:e+s,bottom:t+n,x:e,y:t}}function rn(i,e,t){let{reference:s,floating:n}=i;const o=Me(e),r=Bs(e),a=Ps(r),c=Ye(e),u=o==="y",h=s.x+s.width/2-n.width/2,g=s.y+s.height/2-n.height/2,f=s[a]/2-n[a]/2;let m;switch(c){case"top":m={x:h,y:s.y-n.height};break;case"bottom":m={x:h,y:s.y+s.height};break;case"right":m={x:s.x+s.width,y:g};break;case"left":m={x:s.x-n.width,y:g};break;default:m={x:s.x,y:s.y}}switch(yt(e)){case"start":m[r]-=f*(t&&u?-1:1);break;case"end":m[r]+=f*(t&&u?-1:1);break}return m}const Zr=async(i,e,t)=>{const{placement:s="bottom",strategy:n="absolute",middleware:o=[],platform:r}=t,a=o.filter(Boolean),c=await(r.isRTL==null?void 0:r.isRTL(e));let u=await r.getElementRects({reference:i,floating:e,strategy:n}),{x:h,y:g}=rn(u,s,c),f=s,m={},y=0;for(let _=0;_<a.length;_++){const{name:x,fn:p}=a[_],{x:k,y:C,data:S,reset:I}=await p({x:h,y:g,initialPlacement:s,placement:f,strategy:n,middlewareData:m,rects:u,platform:r,elements:{reference:i,floating:e}});h=k??h,g=C??g,m={...m,[x]:{...m[x],...S}},I&&y<=50&&(y++,typeof I=="object"&&(I.placement&&(f=I.placement),I.rects&&(u=I.rects===!0?await r.getElementRects({reference:i,floating:e,strategy:n}):I.rects),{x:h,y:g}=rn(u,f,c)),_=-1)}return{x:h,y:g,placement:f,strategy:n,middlewareData:m}};async function Us(i,e){var t;e===void 0&&(e={});const{x:s,y:n,platform:o,rects:r,elements:a,strategy:c}=i,{boundary:u="clippingAncestors",rootBoundary:h="viewport",elementContext:g="floating",altBoundary:f=!1,padding:m=0}=_t(e,i),y=io(m),x=a[f?g==="floating"?"reference":"floating":g],p=wi(await o.getClippingRect({element:(t=await(o.isElement==null?void 0:o.isElement(x)))==null||t?x:x.contextElement||await(o.getDocumentElement==null?void 0:o.getDocumentElement(a.floating)),boundary:u,rootBoundary:h,strategy:c})),k=g==="floating"?{x:s,y:n,width:r.floating.width,height:r.floating.height}:r.reference,C=await(o.getOffsetParent==null?void 0:o.getOffsetParent(a.floating)),S=await(o.isElement==null?void 0:o.isElement(C))?await(o.getScale==null?void 0:o.getScale(C))||{x:1,y:1}:{x:1,y:1},I=wi(o.convertOffsetParentRelativeRectToViewportRelativeRect?await o.convertOffsetParentRelativeRectToViewportRelativeRect({elements:a,rect:k,offsetParent:C,strategy:c}):k);return{top:(p.top-I.top+y.top)/S.y,bottom:(I.bottom-p.bottom+y.bottom)/S.y,left:(p.left-I.left+y.left)/S.x,right:(I.right-p.right+y.right)/S.x}}const Xr=i=>({name:"arrow",options:i,async fn(e){const{x:t,y:s,placement:n,rects:o,platform:r,elements:a,middlewareData:c}=e,{element:u,padding:h=0}=_t(i,e)||{};if(u==null)return{};const g=io(h),f={x:t,y:s},m=Bs(n),y=Ps(m),_=await r.getDimensions(u),x=m==="y",p=x?"top":"left",k=x?"bottom":"right",C=x?"clientHeight":"clientWidth",S=o.reference[y]+o.reference[m]-f[m]-o.floating[y],I=f[m]-o.reference[m],R=await(r.getOffsetParent==null?void 0:r.getOffsetParent(u));let z=R?R[C]:0;(!z||!await(r.isElement==null?void 0:r.isElement(R)))&&(z=a.floating[C]||o.floating[y]);const G=S/2-I/2,B=z/2-_[y]/2-1,D=Ge(g[p],B),ne=Ge(g[k],B),oe=D,l=z-_[y]-ne,A=z/2-_[y]/2+G,L=ys(oe,A,l),V=!c.arrow&&yt(n)!=null&&A!==L&&o.reference[y]/2-(A<oe?D:ne)-_[y]/2<0,M=V?A<oe?A-oe:A-l:0;return{[m]:f[m]+M,data:{[m]:L,centerOffset:A-L-M,...V&&{alignmentOffset:M}},reset:V}}}),Qr=function(i){return i===void 0&&(i={}),{name:"flip",options:i,async fn(e){var t,s;const{placement:n,middlewareData:o,rects:r,initialPlacement:a,platform:c,elements:u}=e,{mainAxis:h=!0,crossAxis:g=!0,fallbackPlacements:f,fallbackStrategy:m="bestFit",fallbackAxisSideDirection:y="none",flipAlignment:_=!0,...x}=_t(i,e);if((t=o.arrow)!=null&&t.alignmentOffset)return{};const p=Ye(n),k=Me(a),C=Ye(a)===a,S=await(c.isRTL==null?void 0:c.isRTL(u.floating)),I=f||(C||!_?[yi(a)]:qr(a)),R=y!=="none";!f&&R&&I.push(...Gr(a,_,y,S));const z=[a,...I],G=await Us(e,x),B=[];let D=((s=o.flip)==null?void 0:s.overflows)||[];if(h&&B.push(G[p]),g){const A=Hr(n,r,S);B.push(G[A[0]],G[A[1]])}if(D=[...D,{placement:n,overflows:B}],!B.every(A=>A<=0)){var ne,oe;const A=(((ne=o.flip)==null?void 0:ne.index)||0)+1,L=z[A];if(L&&(!(g==="alignment"?k!==Me(L):!1)||D.every(N=>Me(N.placement)===k?N.overflows[0]>0:!0)))return{data:{index:A,overflows:D},reset:{placement:L}};let V=(oe=D.filter(M=>M.overflows[0]<=0).sort((M,N)=>M.overflows[1]-N.overflows[1])[0])==null?void 0:oe.placement;if(!V)switch(m){case"bestFit":{var l;const M=(l=D.filter(N=>{if(R){const j=Me(N.placement);return j===k||j==="y"}return!0}).map(N=>[N.placement,N.overflows.filter(j=>j>0).reduce((j,me)=>j+me,0)]).sort((N,j)=>N[1]-j[1])[0])==null?void 0:l[0];M&&(V=M);break}case"initialPlacement":V=a;break}if(n!==V)return{reset:{placement:V}}}return{}}}},Jr=new Set(["left","top"]);async function ea(i,e){const{placement:t,platform:s,elements:n}=i,o=await(s.isRTL==null?void 0:s.isRTL(n.floating)),r=Ye(t),a=yt(t),c=Me(t)==="y",u=Jr.has(r)?-1:1,h=o&&c?-1:1,g=_t(e,i);let{mainAxis:f,crossAxis:m,alignmentAxis:y}=typeof g=="number"?{mainAxis:g,crossAxis:0,alignmentAxis:null}:{mainAxis:g.mainAxis||0,crossAxis:g.crossAxis||0,alignmentAxis:g.alignmentAxis};return a&&typeof y=="number"&&(m=a==="end"?y*-1:y),c?{x:m*h,y:f*u}:{x:f*u,y:m*h}}const ta=function(i){return i===void 0&&(i=0),{name:"offset",options:i,async fn(e){var t,s;const{x:n,y:o,placement:r,middlewareData:a}=e,c=await ea(e,i);return r===((t=a.offset)==null?void 0:t.placement)&&(s=a.arrow)!=null&&s.alignmentOffset?{}:{x:n+c.x,y:o+c.y,data:{...c,placement:r}}}}},ia=function(i){return i===void 0&&(i={}),{name:"shift",options:i,async fn(e){const{x:t,y:s,placement:n}=e,{mainAxis:o=!0,crossAxis:r=!1,limiter:a={fn:x=>{let{x:p,y:k}=x;return{x:p,y:k}}},...c}=_t(i,e),u={x:t,y:s},h=await Us(e,c),g=Me(Ye(n)),f=to(g);let m=u[f],y=u[g];if(o){const x=f==="y"?"top":"left",p=f==="y"?"bottom":"right",k=m+h[x],C=m-h[p];m=ys(k,m,C)}if(r){const x=g==="y"?"top":"left",p=g==="y"?"bottom":"right",k=y+h[x],C=y-h[p];y=ys(k,y,C)}const _=a.fn({...e,[f]:m,[g]:y});return{..._,data:{x:_.x-t,y:_.y-s,enabled:{[f]:o,[g]:r}}}}}},sa=function(i){return i===void 0&&(i={}),{name:"size",options:i,async fn(e){var t,s;const{placement:n,rects:o,platform:r,elements:a}=e,{apply:c=()=>{},...u}=_t(i,e),h=await Us(e,u),g=Ye(n),f=yt(n),m=Me(n)==="y",{width:y,height:_}=o.floating;let x,p;g==="top"||g==="bottom"?(x=g,p=f===(await(r.isRTL==null?void 0:r.isRTL(a.floating))?"start":"end")?"left":"right"):(p=g,x=f==="end"?"top":"bottom");const k=_-h.top-h.bottom,C=y-h.left-h.right,S=Ge(_-h[x],k),I=Ge(y-h[p],C),R=!e.middlewareData.shift;let z=S,G=I;if((t=e.middlewareData.shift)!=null&&t.enabled.x&&(G=C),(s=e.middlewareData.shift)!=null&&s.enabled.y&&(z=k),R&&!f){const D=ge(h.left,0),ne=ge(h.right,0),oe=ge(h.top,0),l=ge(h.bottom,0);m?G=y-2*(D!==0||ne!==0?D+ne:ge(h.left,h.right)):z=_-2*(oe!==0||l!==0?oe+l:ge(h.top,h.bottom))}await c({...e,availableWidth:G,availableHeight:z});const B=await r.getDimensions(a.floating);return y!==B.width||_!==B.height?{reset:{rects:!0}}:{}}}};function Oi(){return typeof window<"u"}function wt(i){return so(i)?(i.nodeName||"").toLowerCase():"#document"}function _e(i){var e;return(i==null||(e=i.ownerDocument)==null?void 0:e.defaultView)||window}function Ie(i){var e;return(e=(so(i)?i.ownerDocument:i.document)||window.document)==null?void 0:e.documentElement}function so(i){return Oi()?i instanceof Node||i instanceof _e(i).Node:!1}function Se(i){return Oi()?i instanceof Element||i instanceof _e(i).Element:!1}function Oe(i){return Oi()?i instanceof HTMLElement||i instanceof _e(i).HTMLElement:!1}function an(i){return!Oi()||typeof ShadowRoot>"u"?!1:i instanceof ShadowRoot||i instanceof _e(i).ShadowRoot}const na=new Set(["inline","contents"]);function Bt(i){const{overflow:e,overflowX:t,overflowY:s,display:n}=Ae(i);return/auto|scroll|overlay|hidden|clip/.test(e+s+t)&&!na.has(n)}const oa=new Set(["table","td","th"]);function ra(i){return oa.has(wt(i))}const aa=[":popover-open",":modal"];function Ii(i){return aa.some(e=>{try{return i.matches(e)}catch{return!1}})}const la=["transform","translate","scale","rotate","perspective"],ca=["transform","translate","scale","rotate","perspective","filter"],da=["paint","layout","strict","content"];function Di(i){const e=Hs(),t=Se(i)?Ae(i):i;return la.some(s=>t[s]?t[s]!=="none":!1)||(t.containerType?t.containerType!=="normal":!1)||!e&&(t.backdropFilter?t.backdropFilter!=="none":!1)||!e&&(t.filter?t.filter!=="none":!1)||ca.some(s=>(t.willChange||"").includes(s))||da.some(s=>(t.contain||"").includes(s))}function ua(i){let e=Ze(i);for(;Oe(e)&&!pt(e);){if(Di(e))return e;if(Ii(e))return null;e=Ze(e)}return null}function Hs(){return typeof CSS>"u"||!CSS.supports?!1:CSS.supports("-webkit-backdrop-filter","none")}const ha=new Set(["html","body","#document"]);function pt(i){return ha.has(wt(i))}function Ae(i){return _e(i).getComputedStyle(i)}function $i(i){return Se(i)?{scrollLeft:i.scrollLeft,scrollTop:i.scrollTop}:{scrollLeft:i.scrollX,scrollTop:i.scrollY}}function Ze(i){if(wt(i)==="html")return i;const e=i.assignedSlot||i.parentNode||an(i)&&i.host||Ie(i);return an(e)?e.host:e}function no(i){const e=Ze(i);return pt(e)?i.ownerDocument?i.ownerDocument.body:i.body:Oe(e)&&Bt(e)?e:no(e)}function ft(i,e,t){var s;e===void 0&&(e=[]),t===void 0&&(t=!0);const n=no(i),o=n===((s=i.ownerDocument)==null?void 0:s.body),r=_e(n);if(o){const a=xs(r);return e.concat(r,r.visualViewport||[],Bt(n)?n:[],a&&t?ft(a):[])}return e.concat(n,ft(n,[],t))}function xs(i){return i.parent&&Object.getPrototypeOf(i.parent)?i.frameElement:null}function oo(i){const e=Ae(i);let t=parseFloat(e.width)||0,s=parseFloat(e.height)||0;const n=Oe(i),o=n?i.offsetWidth:t,r=n?i.offsetHeight:s,a=_i(t)!==o||_i(s)!==r;return a&&(t=o,s=r),{width:t,height:s,$:a}}function qs(i){return Se(i)?i:i.contextElement}function ht(i){const e=qs(i);if(!Oe(e))return Le(1);const t=e.getBoundingClientRect(),{width:s,height:n,$:o}=oo(e);let r=(o?_i(t.width):t.width)/s,a=(o?_i(t.height):t.height)/n;return(!r||!Number.isFinite(r))&&(r=1),(!a||!Number.isFinite(a))&&(a=1),{x:r,y:a}}const pa=Le(0);function ro(i){const e=_e(i);return!Hs()||!e.visualViewport?pa:{x:e.visualViewport.offsetLeft,y:e.visualViewport.offsetTop}}function fa(i,e,t){return e===void 0&&(e=!1),!t||e&&t!==_e(i)?!1:e}function nt(i,e,t,s){e===void 0&&(e=!1),t===void 0&&(t=!1);const n=i.getBoundingClientRect(),o=qs(i);let r=Le(1);e&&(s?Se(s)&&(r=ht(s)):r=ht(i));const a=fa(o,t,s)?ro(o):Le(0);let c=(n.left+a.x)/r.x,u=(n.top+a.y)/r.y,h=n.width/r.x,g=n.height/r.y;if(o){const f=_e(o),m=s&&Se(s)?_e(s):s;let y=f,_=xs(y);for(;_&&s&&m!==y;){const x=ht(_),p=_.getBoundingClientRect(),k=Ae(_),C=p.left+(_.clientLeft+parseFloat(k.paddingLeft))*x.x,S=p.top+(_.clientTop+parseFloat(k.paddingTop))*x.y;c*=x.x,u*=x.y,h*=x.x,g*=x.y,c+=C,u+=S,y=_e(_),_=xs(y)}}return wi({width:h,height:g,x:c,y:u})}function Mi(i,e){const t=$i(i).scrollLeft;return e?e.left+t:nt(Ie(i)).left+t}function ao(i,e){const t=i.getBoundingClientRect(),s=t.left+e.scrollLeft-Mi(i,t),n=t.top+e.scrollTop;return{x:s,y:n}}function ma(i){let{elements:e,rect:t,offsetParent:s,strategy:n}=i;const o=n==="fixed",r=Ie(s),a=e?Ii(e.floating):!1;if(s===r||a&&o)return t;let c={scrollLeft:0,scrollTop:0},u=Le(1);const h=Le(0),g=Oe(s);if((g||!g&&!o)&&((wt(s)!=="body"||Bt(r))&&(c=$i(s)),Oe(s))){const m=nt(s);u=ht(s),h.x=m.x+s.clientLeft,h.y=m.y+s.clientTop}const f=r&&!g&&!o?ao(r,c):Le(0);return{width:t.width*u.x,height:t.height*u.y,x:t.x*u.x-c.scrollLeft*u.x+h.x+f.x,y:t.y*u.y-c.scrollTop*u.y+h.y+f.y}}function ba(i){return Array.from(i.getClientRects())}function ga(i){const e=Ie(i),t=$i(i),s=i.ownerDocument.body,n=ge(e.scrollWidth,e.clientWidth,s.scrollWidth,s.clientWidth),o=ge(e.scrollHeight,e.clientHeight,s.scrollHeight,s.clientHeight);let r=-t.scrollLeft+Mi(i);const a=-t.scrollTop;return Ae(s).direction==="rtl"&&(r+=ge(e.clientWidth,s.clientWidth)-n),{width:n,height:o,x:r,y:a}}const ln=25;function va(i,e){const t=_e(i),s=Ie(i),n=t.visualViewport;let o=s.clientWidth,r=s.clientHeight,a=0,c=0;if(n){o=n.width,r=n.height;const h=Hs();(!h||h&&e==="fixed")&&(a=n.offsetLeft,c=n.offsetTop)}const u=Mi(s);if(u<=0){const h=s.ownerDocument,g=h.body,f=getComputedStyle(g),m=h.compatMode==="CSS1Compat"&&parseFloat(f.marginLeft)+parseFloat(f.marginRight)||0,y=Math.abs(s.clientWidth-g.clientWidth-m);y<=ln&&(o-=y)}else u<=ln&&(o+=u);return{width:o,height:r,x:a,y:c}}const _a=new Set(["absolute","fixed"]);function ya(i,e){const t=nt(i,!0,e==="fixed"),s=t.top+i.clientTop,n=t.left+i.clientLeft,o=Oe(i)?ht(i):Le(1),r=i.clientWidth*o.x,a=i.clientHeight*o.y,c=n*o.x,u=s*o.y;return{width:r,height:a,x:c,y:u}}function cn(i,e,t){let s;if(e==="viewport")s=va(i,t);else if(e==="document")s=ga(Ie(i));else if(Se(e))s=ya(e,t);else{const n=ro(i);s={x:e.x-n.x,y:e.y-n.y,width:e.width,height:e.height}}return wi(s)}function lo(i,e){const t=Ze(i);return t===e||!Se(t)||pt(t)?!1:Ae(t).position==="fixed"||lo(t,e)}function wa(i,e){const t=e.get(i);if(t)return t;let s=ft(i,[],!1).filter(a=>Se(a)&&wt(a)!=="body"),n=null;const o=Ae(i).position==="fixed";let r=o?Ze(i):i;for(;Se(r)&&!pt(r);){const a=Ae(r),c=Di(r);!c&&a.position==="fixed"&&(n=null),(o?!c&&!n:!c&&a.position==="static"&&!!n&&_a.has(n.position)||Bt(r)&&!c&&lo(i,r))?s=s.filter(h=>h!==r):n=a,r=Ze(r)}return e.set(i,s),s}function xa(i){let{element:e,boundary:t,rootBoundary:s,strategy:n}=i;const r=[...t==="clippingAncestors"?Ii(e)?[]:wa(e,this._c):[].concat(t),s],a=r[0],c=r.reduce((u,h)=>{const g=cn(e,h,n);return u.top=ge(g.top,u.top),u.right=Ge(g.right,u.right),u.bottom=Ge(g.bottom,u.bottom),u.left=ge(g.left,u.left),u},cn(e,a,n));return{width:c.right-c.left,height:c.bottom-c.top,x:c.left,y:c.top}}function Ea(i){const{width:e,height:t}=oo(i);return{width:e,height:t}}function ka(i,e,t){const s=Oe(e),n=Ie(e),o=t==="fixed",r=nt(i,!0,o,e);let a={scrollLeft:0,scrollTop:0};const c=Le(0);function u(){c.x=Mi(n)}if(s||!s&&!o)if((wt(e)!=="body"||Bt(n))&&(a=$i(e)),s){const m=nt(e,!0,o,e);c.x=m.x+e.clientLeft,c.y=m.y+e.clientTop}else n&&u();o&&!s&&n&&u();const h=n&&!s&&!o?ao(n,a):Le(0),g=r.left+a.scrollLeft-c.x-h.x,f=r.top+a.scrollTop-c.y-h.y;return{x:g,y:f,width:r.width,height:r.height}}function Ki(i){return Ae(i).position==="static"}function dn(i,e){if(!Oe(i)||Ae(i).position==="fixed")return null;if(e)return e(i);let t=i.offsetParent;return Ie(i)===t&&(t=t.ownerDocument.body),t}function co(i,e){const t=_e(i);if(Ii(i))return t;if(!Oe(i)){let n=Ze(i);for(;n&&!pt(n);){if(Se(n)&&!Ki(n))return n;n=Ze(n)}return t}let s=dn(i,e);for(;s&&ra(s)&&Ki(s);)s=dn(s,e);return s&&pt(s)&&Ki(s)&&!Di(s)?t:s||ua(i)||t}const Ca=async function(i){const e=this.getOffsetParent||co,t=this.getDimensions,s=await t(i.floating);return{reference:ka(i.reference,await e(i.floating),i.strategy),floating:{x:0,y:0,width:s.width,height:s.height}}};function Sa(i){return Ae(i).direction==="rtl"}const ui={convertOffsetParentRelativeRectToViewportRelativeRect:ma,getDocumentElement:Ie,getClippingRect:xa,getOffsetParent:co,getElementRects:Ca,getClientRects:ba,getDimensions:Ea,getScale:ht,isElement:Se,isRTL:Sa};function uo(i,e){return i.x===e.x&&i.y===e.y&&i.width===e.width&&i.height===e.height}function Aa(i,e){let t=null,s;const n=Ie(i);function o(){var a;clearTimeout(s),(a=t)==null||a.disconnect(),t=null}function r(a,c){a===void 0&&(a=!1),c===void 0&&(c=1),o();const u=i.getBoundingClientRect(),{left:h,top:g,width:f,height:m}=u;if(a||e(),!f||!m)return;const y=Jt(g),_=Jt(n.clientWidth-(h+f)),x=Jt(n.clientHeight-(g+m)),p=Jt(h),C={rootMargin:-y+"px "+-_+"px "+-x+"px "+-p+"px",threshold:ge(0,Ge(1,c))||1};let S=!0;function I(R){const z=R[0].intersectionRatio;if(z!==c){if(!S)return r();z?r(!1,z):s=setTimeout(()=>{r(!1,1e-7)},1e3)}z===1&&!uo(u,i.getBoundingClientRect())&&r(),S=!1}try{t=new IntersectionObserver(I,{...C,root:n.ownerDocument})}catch{t=new IntersectionObserver(I,C)}t.observe(i)}return r(!0),o}function ho(i,e,t,s){s===void 0&&(s={});const{ancestorScroll:n=!0,ancestorResize:o=!0,elementResize:r=typeof ResizeObserver=="function",layoutShift:a=typeof IntersectionObserver=="function",animationFrame:c=!1}=s,u=qs(i),h=n||o?[...u?ft(u):[],...ft(e)]:[];h.forEach(p=>{n&&p.addEventListener("scroll",t,{passive:!0}),o&&p.addEventListener("resize",t)});const g=u&&a?Aa(u,t):null;let f=-1,m=null;r&&(m=new ResizeObserver(p=>{let[k]=p;k&&k.target===u&&m&&(m.unobserve(e),cancelAnimationFrame(f),f=requestAnimationFrame(()=>{var C;(C=m)==null||C.observe(e)})),t()}),u&&!c&&m.observe(u),m.observe(e));let y,_=c?nt(i):null;c&&x();function x(){const p=nt(i);_&&!uo(_,p)&&t(),_=p,y=requestAnimationFrame(x)}return t(),()=>{var p;h.forEach(k=>{n&&k.removeEventListener("scroll",t),o&&k.removeEventListener("resize",t)}),g?.(),(p=m)==null||p.disconnect(),m=null,c&&cancelAnimationFrame(y)}}const po=ta,fo=ia,mo=Qr,un=sa,Ta=Xr,bo=(i,e,t)=>{const s=new Map,n={platform:ui,...t},o={...n.platform,_c:s};return Zr(i,e,{...n,platform:o})};function Na(i){return Fa(i)}function Gi(i){return i.assignedSlot?i.assignedSlot:i.parentNode instanceof ShadowRoot?i.parentNode.host:i.parentNode}function Fa(i){for(let e=i;e;e=Gi(e))if(e instanceof Element&&getComputedStyle(e).display==="none")return null;for(let e=Gi(i);e;e=Gi(e)){if(!(e instanceof Element))continue;const t=getComputedStyle(e);if(t.display!=="contents"&&(t.position!=="static"||Di(t)||e.tagName==="BODY"))return e}return null}var La=`:host {
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
`;function hn(i){return i!==null&&typeof i=="object"&&"getBoundingClientRect"in i&&("contextElement"in i?i instanceof Element:!0)}var ei=globalThis?.HTMLElement?.prototype.hasOwnProperty("popover"),W=class extends fe{constructor(){super(...arguments),this.localize=new vt(this),this.active=!1,this.placement="top",this.boundary="viewport",this.distance=0,this.skidding=0,this.arrow=!1,this.arrowPlacement="anchor",this.arrowPadding=10,this.flip=!1,this.flipFallbackPlacements="",this.flipFallbackStrategy="best-fit",this.flipPadding=0,this.shift=!1,this.shiftPadding=0,this.autoSizePadding=0,this.hoverBridge=!1,this.updateHoverBridge=()=>{if(this.hoverBridge&&this.anchorEl){const i=this.anchorEl.getBoundingClientRect(),e=this.popup.getBoundingClientRect(),t=this.placement.includes("top")||this.placement.includes("bottom");let s=0,n=0,o=0,r=0,a=0,c=0,u=0,h=0;t?i.top<e.top?(s=i.left,n=i.bottom,o=i.right,r=i.bottom,a=e.left,c=e.top,u=e.right,h=e.top):(s=e.left,n=e.bottom,o=e.right,r=e.bottom,a=i.left,c=i.top,u=i.right,h=i.top):i.left<e.left?(s=i.right,n=i.top,o=e.left,r=e.top,a=i.right,c=i.bottom,u=e.left,h=e.bottom):(s=e.right,n=e.top,o=i.left,r=i.top,a=e.right,c=e.bottom,u=i.left,h=i.bottom),this.style.setProperty("--hover-bridge-top-left-x",`${s}px`),this.style.setProperty("--hover-bridge-top-left-y",`${n}px`),this.style.setProperty("--hover-bridge-top-right-x",`${o}px`),this.style.setProperty("--hover-bridge-top-right-y",`${r}px`),this.style.setProperty("--hover-bridge-bottom-left-x",`${a}px`),this.style.setProperty("--hover-bridge-bottom-left-y",`${c}px`),this.style.setProperty("--hover-bridge-bottom-right-x",`${u}px`),this.style.setProperty("--hover-bridge-bottom-right-y",`${h}px`)}}}async connectedCallback(){super.connectedCallback(),await this.updateComplete,this.start()}disconnectedCallback(){super.disconnectedCallback(),this.stop()}async updated(i){super.updated(i),i.has("active")&&(this.active?this.start():this.stop()),i.has("anchor")&&this.handleAnchorChange(),this.active&&(await this.updateComplete,this.reposition())}async handleAnchorChange(){if(await this.stop(),this.anchor&&typeof this.anchor=="string"){const i=this.getRootNode();this.anchorEl=i.getElementById(this.anchor)}else this.anchor instanceof Element||hn(this.anchor)?this.anchorEl=this.anchor:this.anchorEl=this.querySelector('[slot="anchor"]');this.anchorEl instanceof HTMLSlotElement&&(this.anchorEl=this.anchorEl.assignedElements({flatten:!0})[0]),this.anchorEl&&this.start()}start(){!this.anchorEl||!this.active||(this.popup.showPopover?.(),this.cleanup=ho(this.anchorEl,this.popup,()=>{this.reposition()}))}async stop(){return new Promise(i=>{this.popup.hidePopover?.(),this.cleanup?(this.cleanup(),this.cleanup=void 0,this.removeAttribute("data-current-placement"),this.style.removeProperty("--auto-size-available-width"),this.style.removeProperty("--auto-size-available-height"),requestAnimationFrame(()=>i())):i()})}reposition(){if(!this.active||!this.anchorEl)return;const i=[po({mainAxis:this.distance,crossAxis:this.skidding})];this.sync?i.push(un({apply:({rects:s})=>{const n=this.sync==="width"||this.sync==="both",o=this.sync==="height"||this.sync==="both";this.popup.style.width=n?`${s.reference.width}px`:"",this.popup.style.height=o?`${s.reference.height}px`:""}})):(this.popup.style.width="",this.popup.style.height="");let e;ei&&!hn(this.anchor)&&this.boundary==="scroll"&&(e=ft(this.anchorEl).filter(s=>s instanceof Element)),this.flip&&i.push(mo({boundary:this.flipBoundary||e,fallbackPlacements:this.flipFallbackPlacements,fallbackStrategy:this.flipFallbackStrategy==="best-fit"?"bestFit":"initialPlacement",padding:this.flipPadding})),this.shift&&i.push(fo({boundary:this.shiftBoundary||e,padding:this.shiftPadding})),this.autoSize?i.push(un({boundary:this.autoSizeBoundary||e,padding:this.autoSizePadding,apply:({availableWidth:s,availableHeight:n})=>{this.autoSize==="vertical"||this.autoSize==="both"?this.style.setProperty("--auto-size-available-height",`${n}px`):this.style.removeProperty("--auto-size-available-height"),this.autoSize==="horizontal"||this.autoSize==="both"?this.style.setProperty("--auto-size-available-width",`${s}px`):this.style.removeProperty("--auto-size-available-width")}})):(this.style.removeProperty("--auto-size-available-width"),this.style.removeProperty("--auto-size-available-height")),this.arrow&&i.push(Ta({element:this.arrowEl,padding:this.arrowPadding}));const t=ei?s=>ui.getOffsetParent(s,Na):ui.getOffsetParent;bo(this.anchorEl,this.popup,{placement:this.placement,middleware:i,strategy:ei?"absolute":"fixed",platform:{...ui,getOffsetParent:t}}).then(({x:s,y:n,middlewareData:o,placement:r})=>{const a=this.localize.dir()==="rtl",c={top:"bottom",right:"left",bottom:"top",left:"right"}[r.split("-")[0]];if(this.setAttribute("data-current-placement",r),Object.assign(this.popup.style,{left:`${s}px`,top:`${n}px`}),this.arrow){const u=o.arrow.x,h=o.arrow.y;let g="",f="",m="",y="";if(this.arrowPlacement==="start"){const _=typeof u=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"";g=typeof h=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"",f=a?_:"",y=a?"":_}else if(this.arrowPlacement==="end"){const _=typeof u=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"";f=a?"":_,y=a?_:"",m=typeof h=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:""}else this.arrowPlacement==="center"?(y=typeof u=="number"?"calc(50% - var(--arrow-size-diagonal))":"",g=typeof h=="number"?"calc(50% - var(--arrow-size-diagonal))":""):(y=typeof u=="number"?`${u}px`:"",g=typeof h=="number"?`${h}px`:"");Object.assign(this.arrowEl.style,{top:g,right:f,bottom:m,left:y,[c]:"calc(var(--arrow-size-diagonal) * -1)"})}}),requestAnimationFrame(()=>this.updateHoverBridge()),this.dispatchEvent(new Rr)}render(){return w`
      <slot name="anchor" @slotchange=${this.handleAnchorChange}></slot>

      <span
        part="hover-bridge"
        class=${pe({"popup-hover-bridge":!0,"popup-hover-bridge-visible":this.hoverBridge&&this.active})}
      ></span>

      <div
        popover="manual"
        part="popup"
        class=${pe({popup:!0,"popup-active":this.active,"popup-fixed":!ei,"popup-has-arrow":this.arrow})}
      >
        <slot></slot>
        ${this.arrow?w`<div part="arrow" class="arrow" role="presentation"></div>`:""}
      </div>
    `}};W.css=La;v([X(".popup")],W.prototype,"popup",2);v([X(".arrow")],W.prototype,"arrowEl",2);v([b()],W.prototype,"anchor",2);v([b({type:Boolean,reflect:!0})],W.prototype,"active",2);v([b({reflect:!0})],W.prototype,"placement",2);v([b()],W.prototype,"boundary",2);v([b({type:Number})],W.prototype,"distance",2);v([b({type:Number})],W.prototype,"skidding",2);v([b({type:Boolean})],W.prototype,"arrow",2);v([b({attribute:"arrow-placement"})],W.prototype,"arrowPlacement",2);v([b({attribute:"arrow-padding",type:Number})],W.prototype,"arrowPadding",2);v([b({type:Boolean})],W.prototype,"flip",2);v([b({attribute:"flip-fallback-placements",converter:{fromAttribute:i=>i.split(" ").map(e=>e.trim()).filter(e=>e!==""),toAttribute:i=>i.join(" ")}})],W.prototype,"flipFallbackPlacements",2);v([b({attribute:"flip-fallback-strategy"})],W.prototype,"flipFallbackStrategy",2);v([b({type:Object})],W.prototype,"flipBoundary",2);v([b({attribute:"flip-padding",type:Number})],W.prototype,"flipPadding",2);v([b({type:Boolean})],W.prototype,"shift",2);v([b({type:Object})],W.prototype,"shiftBoundary",2);v([b({attribute:"shift-padding",type:Number})],W.prototype,"shiftPadding",2);v([b({attribute:"auto-size"})],W.prototype,"autoSize",2);v([b()],W.prototype,"sync",2);v([b({type:Object})],W.prototype,"autoSizeBoundary",2);v([b({attribute:"auto-size-padding",type:Number})],W.prototype,"autoSizePadding",2);v([b({attribute:"hover-bridge",type:Boolean})],W.prototype,"hoverBridge",2);W=v([Fe("wa-popup")],W);var Ut=class extends Event{constructor(){super("wa-after-hide",{bubbles:!0,cancelable:!1,composed:!0})}},Ht=class extends Event{constructor(){super("wa-after-show",{bubbles:!0,cancelable:!1,composed:!0})}},qt=class extends Event{constructor(i){super("wa-hide",{bubbles:!0,cancelable:!0,composed:!0}),this.detail=i}},Wt=class extends Event{constructor(){super("wa-show",{bubbles:!0,cancelable:!0,composed:!0})}};const Oa="useandom-26T198340PX75pxJACKVERYMINDBUSHWOLF_GQZbfghjklqvwyzrict";let Ia=(i=21)=>{let e="",t=crypto.getRandomValues(new Uint8Array(i|=0));for(;i--;)e+=Oa[t[i]&63];return e};function Ws(i=""){return`${i}${Ia()}`}function xi(i,e){return new Promise(t=>{function s(n){n.target===i&&(i.removeEventListener(e,s),t())}i.addEventListener(e,s)})}function le(i,e){return new Promise(t=>{const s=new AbortController,{signal:n}=s;if(i.classList.contains(e))return;i.classList.remove(e),i.classList.add(e);let o=()=>{i.classList.remove(e),t(),s.abort()};i.addEventListener("animationend",o,{once:!0,signal:n}),i.addEventListener("animationcancel",o,{once:!0,signal:n})})}function ye(i,e){const t={waitUntilFirstUpdate:!1,...e};return(s,n)=>{const{update:o}=s,r=Array.isArray(i)?i:[i];s.update=function(a){r.forEach(c=>{const u=c;if(a.has(u)){const h=a.get(u),g=this[u];h!==g&&(!t.waitUntilFirstUpdate||this.hasUpdated)&&this[n](h,g)}}),o.call(this,a)}}}var Da=`:host {
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
`,Q=class extends fe{constructor(){super(...arguments),this.placement="top",this.disabled=!1,this.distance=8,this.open=!1,this.skidding=0,this.showDelay=150,this.hideDelay=0,this.trigger="hover focus",this.withoutArrow=!1,this.for=null,this.anchor=null,this.eventController=new AbortController,this.handleBlur=()=>{this.hasTrigger("focus")&&this.hide()},this.handleClick=()=>{this.hasTrigger("click")&&(this.open?this.hide():this.show())},this.handleFocus=()=>{this.hasTrigger("focus")&&this.show()},this.handleDocumentKeyDown=i=>{i.key==="Escape"&&(i.stopPropagation(),this.hide())},this.handleMouseOver=()=>{this.hasTrigger("hover")&&(clearTimeout(this.hoverTimeout),this.hoverTimeout=window.setTimeout(()=>this.show(),this.showDelay))},this.handleMouseOut=()=>{this.hasTrigger("hover")&&(clearTimeout(this.hoverTimeout),this.hoverTimeout=window.setTimeout(()=>this.hide(),this.hideDelay))}}connectedCallback(){super.connectedCallback(),this.eventController.signal.aborted&&(this.eventController=new AbortController),this.open&&(this.open=!1,this.updateComplete.then(()=>{this.open=!0})),this.id||(this.id=Ws("wa-tooltip-")),this.for&&this.anchor?(this.anchor=null,this.handleForChange()):this.for&&this.handleForChange()}disconnectedCallback(){super.disconnectedCallback(),document.removeEventListener("keydown",this.handleDocumentKeyDown),this.eventController.abort(),this.anchor&&this.removeFromAriaLabelledBy(this.anchor,this.id)}firstUpdated(){this.body.hidden=!this.open,this.open&&(this.popup.active=!0,this.popup.reposition())}hasTrigger(i){return this.trigger.split(" ").includes(i)}addToAriaLabelledBy(i,e){const s=(i.getAttribute("aria-labelledby")||"").split(/\s+/).filter(Boolean);s.includes(e)||(s.push(e),i.setAttribute("aria-labelledby",s.join(" ")))}removeFromAriaLabelledBy(i,e){const n=(i.getAttribute("aria-labelledby")||"").split(/\s+/).filter(Boolean).filter(o=>o!==e);n.length>0?i.setAttribute("aria-labelledby",n.join(" ")):i.removeAttribute("aria-labelledby")}async handleOpenChange(){if(this.open){if(this.disabled)return;const i=new Wt;if(this.dispatchEvent(i),i.defaultPrevented){this.open=!1;return}document.addEventListener("keydown",this.handleDocumentKeyDown,{signal:this.eventController.signal}),this.body.hidden=!1,this.popup.active=!0,await le(this.popup.popup,"show-with-scale"),this.popup.reposition(),this.dispatchEvent(new Ht)}else{const i=new qt;if(this.dispatchEvent(i),i.defaultPrevented){this.open=!1;return}document.removeEventListener("keydown",this.handleDocumentKeyDown),await le(this.popup.popup,"hide-with-scale"),this.popup.active=!1,this.body.hidden=!0,this.dispatchEvent(new Ut)}}handleForChange(){const i=this.getRootNode();if(!i)return;const e=this.for?i.getElementById(this.for):null,t=this.anchor;if(e===t)return;const{signal:s}=this.eventController;e&&(this.addToAriaLabelledBy(e,this.id),e.addEventListener("blur",this.handleBlur,{capture:!0,signal:s}),e.addEventListener("focus",this.handleFocus,{capture:!0,signal:s}),e.addEventListener("click",this.handleClick,{signal:s}),e.addEventListener("mouseover",this.handleMouseOver,{signal:s}),e.addEventListener("mouseout",this.handleMouseOut,{signal:s})),t&&(this.removeFromAriaLabelledBy(t,this.id),t.removeEventListener("blur",this.handleBlur,{capture:!0}),t.removeEventListener("focus",this.handleFocus,{capture:!0}),t.removeEventListener("click",this.handleClick),t.removeEventListener("mouseover",this.handleMouseOver),t.removeEventListener("mouseout",this.handleMouseOut)),this.anchor=e}async handleOptionsChange(){this.hasUpdated&&(await this.updateComplete,this.popup.reposition())}handleDisabledChange(){this.disabled&&this.open&&this.hide()}async show(){if(!this.open)return this.open=!0,xi(this,"wa-after-show")}async hide(){if(this.open)return this.open=!1,xi(this,"wa-after-hide")}render(){return w`
      <wa-popup
        part="base"
        exportparts="
          popup:base__popup,
          arrow:base__arrow
        "
        class=${pe({tooltip:!0,"tooltip-open":this.open})}
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
    `}};Q.css=Da;Q.dependencies={"wa-popup":W};v([X("slot:not([name])")],Q.prototype,"defaultSlot",2);v([X(".body")],Q.prototype,"body",2);v([X("wa-popup")],Q.prototype,"popup",2);v([b()],Q.prototype,"placement",2);v([b({type:Boolean,reflect:!0})],Q.prototype,"disabled",2);v([b({type:Number})],Q.prototype,"distance",2);v([b({type:Boolean,reflect:!0})],Q.prototype,"open",2);v([b({type:Number})],Q.prototype,"skidding",2);v([b({attribute:"show-delay",type:Number})],Q.prototype,"showDelay",2);v([b({attribute:"hide-delay",type:Number})],Q.prototype,"hideDelay",2);v([b()],Q.prototype,"trigger",2);v([b({attribute:"without-arrow",type:Boolean,reflect:!0})],Q.prototype,"withoutArrow",2);v([b()],Q.prototype,"for",2);v([he()],Q.prototype,"anchor",2);v([ye("open",{waitUntilFirstUpdate:!0})],Q.prototype,"handleOpenChange",1);v([ye("for")],Q.prototype,"handleForChange",1);v([ye(["distance","placement","skidding"])],Q.prototype,"handleOptionsChange",1);v([ye("disabled")],Q.prototype,"handleDisabledChange",1);Q=v([Fe("wa-tooltip")],Q);var $a=class extends Q{static get styles(){return[Q.styles,F`
        :host {
          --wa-z-index-tooltip: var(--c-tooltip-z-index, 1000);
          --wa-tooltip-background-color: var(
            --c-tooltip-bg,
            var(--c-bg-overlay)
          );
          --wa-tooltip-border-color: var(
            --c-tooltip-border,
            var(--c-border-subtle)
          );
          --wa-tooltip-content-color: var(--c-tooltip-fg, currentColor);
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
        }
      `]}};customElements.get("c-tooltip")||customElements.define("c-tooltip",$a);var Ma=F`
  :host(:not(:focus-within)) {
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
`,Va=F`
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
`,go=Object.defineProperty,pn=Object.getOwnPropertySymbols,Ra=Object.prototype.hasOwnProperty,za=Object.prototype.propertyIsEnumerable,vo=i=>{throw TypeError(i)},fn=(i,e,t)=>e in i?go(i,e,{enumerable:!0,configurable:!0,writable:!0,value:t}):i[e]=t,Pa=(i,e)=>{for(var t in e||(e={}))Ra.call(e,t)&&fn(i,t,e[t]);if(pn)for(var t of pn(e))za.call(e,t)&&fn(i,t,e[t]);return i},mn=(i,e,t,s)=>{for(var n=void 0,o=i.length-1,r;o>=0;o--)(r=i[o])&&(n=r(e,t,n)||n);return n&&go(e,t,n),n},_o=(i,e,t)=>e.has(i)||vo("Cannot "+t),Ba=(i,e,t)=>(_o(i,e,"read from private field"),e.get(i)),Ua=(i,e,t)=>e.has(i)?vo("Cannot add the same private member more than once"):e instanceof WeakSet?e.add(i):e.set(i,t),Ha=(i,e,t,s)=>(_o(i,e,"write to private field"),e.set(i,t),t),hi,Nt=class extends H{constructor(){super(),Ua(this,hi,!1),this.initialReflectedProperties=new Map,Object.entries(this.constructor.dependencies).forEach(([e,t])=>{this.constructor.define(e,t)})}emit(e,t){let s=new CustomEvent(e,Pa({bubbles:!0,cancelable:!1,composed:!0,detail:{}},t));return this.dispatchEvent(s),s}static define(e,t=this,s={}){let n=customElements.get(e);if(!n){try{customElements.define(e,t,s)}catch{customElements.define(e,class extends t{},s)}return}let o=" (unknown version)",r=o;"version"in t&&t.version&&(o=" v"+t.version),"version"in n&&n.version&&(r=" v"+n.version),!(o&&r&&o===r)&&console.warn(`Attempted to register <${e}>${o}, but <${e}>${r} has already been registered.`)}attributeChangedCallback(e,t,s){Ba(this,hi)||(this.constructor.elementProperties.forEach((n,o)=>{n.reflect&&this[o]!=null&&this.initialReflectedProperties.set(o,this[o])}),Ha(this,hi,!0)),super.attributeChangedCallback(e,t,s)}willUpdate(e){super.willUpdate(e),this.initialReflectedProperties.forEach((t,s)=>{e.has(s)&&this[s]==null&&(this[s]=t)})}};hi=new WeakMap,Nt.version="2.20.1",Nt.dependencies={},mn([b()],Nt.prototype,"dir"),mn([b()],Nt.prototype,"lang");var bn=class extends Nt{render(){return w` <slot></slot> `}};bn.styles=[Va,Ma],bn.define("sl-visually-hidden");var qa=F`
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
`,Et=class extends H{constructor(...e){super(...e),this.isCopying=!1,this.value="",this.disabled=!1}async copyValue(){if(!(this.isCopying||this.disabled)){this.isCopying=!0;try{await navigator.clipboard.writeText(this.value),this.dispatchEvent(new CustomEvent("craft-copy",{bubbles:!0,cancelable:!1,composed:!0,detail:{value:this.value}}))}catch{this.dispatchEvent(new CustomEvent("craft-error",{cancelable:!1,composed:!0,bubbles:!0}))}finally{this.isCopying=!1}}}render(){return w`
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
    `}};Et.styles=[qa],T([he()],Et.prototype,"isCopying",void 0),T([b({type:String})],Et.prototype,"value",void 0),T([b({type:Boolean})],Et.prototype,"disabled",void 0),customElements.get("craft-copy-button")||customElements.define("craft-copy-button",Et);var Wa=F`
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
`,ja=F`
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
    background-color: var(--c-copy-attribute-bg, transparent);
    color: var(--c-copy-attribute-fg, inherit);
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
    border-color: var(--c-border-subtle);
    color: var(--c-fg-text);
  }

  .copy-attribute--success::part(button) {
    background-color: var(
      --c-copy-attribute-success-bg,
      var(--c-copy-attribute-bg)
    );
    color: var(--c-copy-attribute-success-fg, var(--c-copy-attribute-fg));
    border: var(--c-copy-attribute-success-border, var(--_border));
  }

  .copy-attribute--error::part(button) {
    background-color: var(
      --c-copy-attribute-error-bg,
      var(--c-copy-attribute-bg)
    );
    color: var(--c-copy-attribute-error-fg, var(--c-copy-attribute-fg));
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
`;const ze={"icon.in":{keyframes:[{scale:.25,opacity:.25},{scale:1,opacity:1}],options:{duration:100}},"icon.out":{keyframes:[{scale:1,opacity:1},{scale:.25,opacity:.25}],options:{duration:100}}};var xe=class extends H{constructor(){super(),this.status="rest",this.value="",this.disabled=!1,this.feedbackDuration=1e3,this.tooltipLabel="Copy",this.addEventListener("craft-copy",()=>{this.showStatus("success")}),this.addEventListener("craft-error",()=>{this.showStatus("error")})}getId(){return`attribute-${this.value.replace(/([a-z])([A-Z])/g,"$1-$2").replace(/[\s_]+/g,"-").toLowerCase()}`}async showStatus(e){let t=e==="success"?this.successIconEl:this.errorIconEl;this.tooltipLabel=e==="success"?"Copied":"Copy failed",await t.animate(ze["icon.out"].keyframes,ze["icon.out"].options),this.copyIconEl.hidden=!0,t.hidden=!1,await t.animate(ze["icon.in"].keyframes,ze["icon.in"].options),this.status=e,setTimeout(async()=>{await t.animate(ze["icon.out"].keyframes,ze["icon.out"].options),t.hidden=!0,this.copyIconEl.hidden=!1,await this.copyIconEl.animate(ze["icon.in"].keyframes,ze["icon.in"].options),this.status="rest",this.tooltipLabel="Copy"},this.feedbackDuration)}render(){return w`
      <c-tooltip for="${this.getId()}">${this.tooltipLabel}</c-tooltip>
      <craft-copy-button
        value="${this.value}"
        id="${this.getId()}"
        class=${pe({"copy-attribute":!0,"copy-attribute--success":this.status==="success","copy-attribute--error":this.status==="error"})}
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
    `}};xe.styles=[Wa,ja],T([he()],xe.prototype,"status",void 0),T([X('slot[name="copy-icon"]')],xe.prototype,"copyIconEl",void 0),T([X('slot[name="success-icon"]')],xe.prototype,"successIconEl",void 0),T([X('slot[name="error-icon"]')],xe.prototype,"errorIconEl",void 0),T([X("craft-copy-button")],xe.prototype,"copyButtonEl",void 0),T([b({type:String})],xe.prototype,"value",void 0),T([b({type:Boolean,reflect:!0})],xe.prototype,"disabled",void 0),T([b({attribute:"feedback-duration",type:Number})],xe.prototype,"feedbackDuration",void 0),T([b({reflect:!1})],xe.prototype,"tooltipLabel",void 0),customElements.get("craft-copy-attribute")||customElements.define("craft-copy-attribute",xe);const yo=new WeakMap;function Ka(i,e){let t=e;for(;t;){if(yo.get(t)===i)return!0;t=Object.getPrototypeOf(t)}return!1}function ee(i){return e=>{if(Ka(i,e))return e;const t=i(e);return yo.set(t,i),t}}const Ga=i=>class extends i{static get properties(){return{disabled:{type:Boolean,reflect:!0}}}constructor(){super(),this._requestedToBeDisabled=!1,this.__isUserSettingDisabled=!0,this.__restoreDisabledTo=!1,this.disabled=!1}makeRequestToBeDisabled(){this._requestedToBeDisabled===!1&&(this._requestedToBeDisabled=!0,this.__restoreDisabledTo=this.disabled,this.__internalSetDisabled(!0))}retractRequestToBeDisabled(){this._requestedToBeDisabled===!0&&(this._requestedToBeDisabled=!1,this.__internalSetDisabled(this.__restoreDisabledTo))}__internalSetDisabled(e){this.__isUserSettingDisabled=!1,this.disabled=e,this.__isUserSettingDisabled=!0}requestUpdate(e,t,s){super.requestUpdate(e,t,s),e==="disabled"&&(this.__isUserSettingDisabled&&(this.__restoreDisabledTo=this.disabled),this.disabled===!1&&this._requestedToBeDisabled===!0&&this.__internalSetDisabled(!0))}click(){this.disabled||super.click()}},jt=ee(Ga),Ya=i=>class extends jt(i){static get properties(){return{tabIndex:{type:Number,reflect:!0,attribute:"tabindex"}}}constructor(){super(),this.__isUserSettingTabIndex=!0,this.__restoreTabIndexTo=0,this.__internalSetTabIndex(0)}makeRequestToBeDisabled(){super.makeRequestToBeDisabled(),this._requestedToBeDisabled===!1&&this.tabIndex!=null&&(this.__restoreTabIndexTo=this.tabIndex)}retractRequestToBeDisabled(){super.retractRequestToBeDisabled(),this._requestedToBeDisabled===!0&&this.__internalSetTabIndex(this.__restoreTabIndexTo)}static enabledWarnings=super.enabledWarnings?.filter(e=>e!=="change-in-update")||[];__internalSetTabIndex(e){this.__isUserSettingTabIndex=!1,this.tabIndex=e,this.__isUserSettingTabIndex=!0}requestUpdate(e,t,s){super.requestUpdate(e,t,s),e==="disabled"&&(this.disabled?this.__internalSetTabIndex(-1):this.__internalSetTabIndex(this.__restoreTabIndexTo)),e==="tabIndex"&&(this.__isUserSettingTabIndex&&this.tabIndex!=null&&(this.__restoreTabIndexTo=this.tabIndex),this.tabIndex!==-1&&this._requestedToBeDisabled===!0&&this.__internalSetTabIndex(-1))}firstUpdated(e){super.firstUpdated(e),this.disabled&&this.__internalSetTabIndex(-1)}},wo=ee(Ya);const{I:Za}=lr,Xa=i=>i===null||typeof i!="object"&&typeof i!="function",xo=(i,e)=>i?._$litType$!==void 0,Qa=i=>i.strings===void 0,gn=()=>document.createComment(""),kt=(i,e,t)=>{const s=i._$AA.parentNode,n=e===void 0?i._$AB:e._$AA;if(t===void 0){const o=s.insertBefore(gn(),n),r=s.insertBefore(gn(),n);t=new Za(o,r,i,i.options)}else{const o=t._$AB.nextSibling,r=t._$AM,a=r!==i;if(a){let c;t._$AQ?.(i),t._$AM=i,t._$AP!==void 0&&(c=i._$AU)!==r._$AU&&t._$AP(c)}if(o!==n||a){let c=t._$AA;for(;c!==o;){const u=c.nextSibling;s.insertBefore(c,n),c=u}}}return t},Xe=(i,e,t=i)=>(i._$AI(e,t),i),Ja={},el=(i,e=Ja)=>i._$AH=e,tl=i=>i._$AH,Yi=i=>{i._$AR(),i._$AA.remove()};function il(i){return i instanceof Node?"node":xo(i)?"template-result":!Array.isArray(i)&&typeof i=="object"&&"template"in i?"slot-rerender-object":null}const sl=i=>class extends i{get slots(){return{}}constructor(){super(),this.__renderMetaPerSlot=new Map,this.__slotsThatNeedRerender=new Set,this.__slotsProvidedByUserOnFirstConnected=new Set,this.__privateSlots=new Set}connectedCallback(){super.connectedCallback(),this._connectSlotMixin()}__rerenderSlot(t){const s=this.slots[t]();this.__renderTemplateInScopedContext({renderAsDirectHostChild:s.renderAsDirectHostChild,template:s.template,slotName:t}),s.afterRender?.()}update(t){super.update(t);for(const s of this.__slotsThatNeedRerender)this.__rerenderSlot(s)}__renderTemplateInScopedContext({template:t,slotName:s,renderAsDirectHostChild:n}){if(!this.__renderMetaPerSlot.has(s)){const f=!!ShadowRoot.prototype.createElement;this.shadowRoot||console.error("[SlotMixin] No shadowRoot was found");const _=(f?this.shadowRoot:document).createElement("div"),x=document.createComment(`_start_slot_${s}_`),p=document.createComment(`_end_slot_${s}_`);_.appendChild(x),_.appendChild(p);const{creationScope:k,host:C}=this.renderOptions;if(en(t,_,{renderBefore:p,creationScope:k,host:C}),n){const S=Array.from(_.childNodes);this.__appendNodes({nodes:S,renderParent:this,slotName:s})}else _.slot=s,this.appendChild(_);this.__renderMetaPerSlot.set(s,{renderTargetThatRespectsShadowRootScoping:_,renderBefore:p});return}const{renderBefore:r,renderTargetThatRespectsShadowRootScoping:a}=this.__renderMetaPerSlot.get(s),c=n?this:a,{creationScope:u,host:h}=this.renderOptions;en(t,c,{creationScope:u,host:h,renderBefore:r}),n&&r.previousElementSibling&&!r.previousElementSibling.slot&&(r.previousElementSibling.slot=s)}__appendNodes({nodes:t,renderParent:s=this,slotName:n}){for(const o of t)o instanceof Element&&n&&n!==""&&o.setAttribute("slot",n),s.appendChild(o)}__initSlots(t){for(const s of t){if(this.__slotsProvidedByUserOnFirstConnected.has(s))continue;const n=this.slots[s]();if(n===void 0)continue;switch(this.__isConnectedSlotMixin||this.__privateSlots.add(s),il(n)){case"template-result":this.__renderTemplateInScopedContext({template:n,renderAsDirectHostChild:!0,slotName:s});break;case"node":this.__appendNodes({nodes:[n],renderParent:this,slotName:s});break;case"slot-rerender-object":this.__slotsThatNeedRerender.add(s),n.firstRenderOnConnected&&this.__rerenderSlot(s);break;default:throw new Error(`Slot "${s}" configured inside "get slots()" (in prototype) of ${this.constructor.name} may return these types: TemplateResult | Node | {template:TemplateResult, afterRender?:function} | undefined.
              You provided: ${n}`)}}}_connectSlotMixin(){if(this.__isConnectedSlotMixin)return;const t=Object.keys(this.slots);for(const s of t)(s===""?Array.from(this.children).find(o=>!o.hasAttribute("slot")):Array.from(this.children).find(o=>o.slot===s))&&this.__slotsProvidedByUserOnFirstConnected.add(s);this.__initSlots(t),this.__isConnectedSlotMixin=!0}_isPrivateSlot(t){return this.__privateSlots.has(t)}},xt=ee(sl);function Zi(i="google-chrome"){const e=globalThis.navigator,t=!!e.userAgentData&&e.userAgentData.brands.some(c=>c.brand==="Chromium");if(i==="chromium")return t;const n=globalThis.navigator?.vendor,o=typeof globalThis.opr<"u",r=globalThis.userAgent?.indexOf("Edge")>-1,a=globalThis.userAgent?.match("CriOS");if(i==="ios")return a;if(i==="google-chrome")return t!==null&&typeof t<"u"&&n==="Google Inc."&&o===!1&&r===!1}const Ei={isChrome:Zi(),isIOSChrome:Zi("ios"),isChromium:Zi("chromium"),isFirefox:globalThis.navigator?.userAgent.toLowerCase().indexOf("firefox")>-1,isMac:globalThis.navigator?.appVersion?.indexOf("Mac")!==-1,isIOS:/iPhone|iPad|iPod/i.test(globalThis.navigator?.userAgent),isMacSafari:globalThis.navigator?.vendor&&globalThis.navigator?.vendor.indexOf("Apple")>-1&&globalThis.navigator?.userAgent&&globalThis.navigator?.userAgent.indexOf("CriOS")===-1&&globalThis.navigator?.userAgent.indexOf("FxiOS")===-1&&globalThis.navigator?.appVersion.indexOf("Mac")!==-1};function Kt(i=""){return`${i.length>0?`${i}-`:""}${Math.random().toString(36).substr(2,10)}`}const Xi=i=>i.key===" "||i.key==="Enter",vn=i=>i.key===" ";class nl extends wo(H){static get properties(){return{active:{type:Boolean,reflect:!0},type:{type:String,reflect:!0}}}render(){return w` <div class="button-content"><slot></slot></div> `}static get styles(){return[F`
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
      `]}constructor(){super(),this.type="button",this.active=!1,this.__setupEvents()}connectedCallback(){super.connectedCallback(),this.hasAttribute("role")||this.setAttribute("role","button")}updated(e){super.updated(e),e.has("disabled")&&(this.disabled?this.setAttribute("aria-disabled","true"):this.getAttribute("aria-disabled")!==null&&this.removeAttribute("aria-disabled"))}__setupEvents(){this.addEventListener("mousedown",this.__mousedownHandler),this.addEventListener("keydown",this.__keydownHandler),this.addEventListener("keyup",this.__keyupHandler)}__mousedownHandler(){this.active=!0;const e=()=>{this.active=!1,document.removeEventListener("mouseup",e),this.removeEventListener("mouseup",e)};document.addEventListener("mouseup",e),this.addEventListener("mouseup",e)}__keydownHandler(e){if(this.active||!Xi(e)){vn(e)&&e.preventDefault();return}vn(e)&&e.preventDefault(),this.active=!0;const t=s=>{Xi(s)&&(this.active=!1,document.removeEventListener("keyup",t,!0))};document.addEventListener("keyup",t,!0)}__keyupHandler(e){if(Xi(e)){if(e.target&&e.target!==this)return;this.click()}}}class ol extends nl{constructor(){super(),this.type="reset",this.__setupDelegationInConstructor(),this.__submitAndResetHelperButton=document.createElement("button"),this.__preventEventLeakage=this.__preventEventLeakage.bind(this)}connectedCallback(){super.connectedCallback(),this.updateComplete.then(()=>{this._setupSubmitAndResetHelperOnConnected()})}disconnectedCallback(){super.disconnectedCallback(),this._teardownSubmitAndResetHelperOnDisconnected()}__preventEventLeakage(e){e.target===this.__submitAndResetHelperButton&&e.stopImmediatePropagation()}_setupSubmitAndResetHelperOnConnected(){this.appendChild(this.__submitAndResetHelperButton),this._form=this.__submitAndResetHelperButton.form,this.removeChild(this.__submitAndResetHelperButton),this._form&&this._form.addEventListener("click",this.__preventEventLeakage)}_teardownSubmitAndResetHelperOnDisconnected(){this._form&&this._form.removeEventListener("click",this.__preventEventLeakage)}async __clickDelegationHandler(e){this._form||await this.updateComplete,(this.type==="submit"||this.type==="reset")&&e.target===this&&this._form&&(this.__submitAndResetHelperButton.type=this.type,this._form.appendChild(this.__submitAndResetHelperButton),this.__submitAndResetHelperButton.click(),this._form.removeChild(this.__submitAndResetHelperButton))}__setupDelegationInConstructor(){this.addEventListener("click",this.__clickDelegationHandler,!0)}}const Pe=new WeakMap;function rl(){const i=document.createElement("button");return i.tabIndex=-1,i.type="submit",i.setAttribute("aria-hidden","true"),i.style.cssText=`
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
  `,i}class al extends ol{get _nativeButtonNode(){return Pe.get(this._form)?.helper||null}constructor(){super(),this.type="submit",this.__implicitSubmitHelperButton=null}_setupSubmitAndResetHelperOnConnected(){if(super._setupSubmitAndResetHelperOnConnected(),!this._form||this.type!=="submit")return;const e=this._form;if(!Pe.get(this._form)){const s=rl(),n=document.createElement("div");n.appendChild(s),Pe.set(this._form,{lionButtons:new Set,helper:s,observer:new MutationObserver(()=>{e.appendChild(n)})}),e.appendChild(n),Pe.get(e)?.observer.observe(n,{childList:!0})}Pe.get(e)?.lionButtons.add(this)}_teardownSubmitAndResetHelperOnDisconnected(){if(super._teardownSubmitAndResetHelperOnDisconnected(),this._form){const e=Pe.get(this._form);e&&(e.lionButtons.delete(this),e.lionButtons.size||(this._form.contains(e.helper)&&e.helper.remove(),Pe.get(this._form)?.observer.disconnect(),Pe.delete(this._form)))}}}var ll=F`
  :host {
    cursor: pointer;
    font: inherit;
    border: 1px solid var(--c-button-border, var(--c-button-default-border));
    background-color: var(--c-button-bg, var(--c-button-default-bg));
    display: inline-flex;
    justify-content: center;
    gap: var(--c-spacing-sm);
    align-items: center;
    border-radius: var(--c-button-radius, var(--c-radius-sm));
    color: var(--c-button-fg, inherit);
    padding-inline: var(--c-button-spacing-inline, var(--c-spacing-md));
    padding-block: 0;
    width: auto;
    min-height: var(--c-button-height, var(--c-size-control-md));
    min-width: var(--c-button-width, var(--c-size-control-md));
    white-space: nowrap;
  }

  @media (hover: hover) {
    :host(:hover) {
      background-color: var(
        --c-button-bg-hover,
        var(--c-button-default-bg-hover)
      );
      border-color: var(
        --c-button-border-hover,
        var(--c-button-default-border-hover)
      );
      color: var(--c-button-fg-hover, var(--c-button-default-fg-hover));
    }
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
  :host([appearance~='plain']) {
    background-color: transparent;
    border-color: transparent;
    color: inherit;
  }

  :host([appearance~='plain']:hover) {
    background-color: rgba(from var(--c-button-bg-hover) r g b / 0.4);
    color: var(--c-button-fg-hover);
  }

  /*
  Variants
   */
  :host([variant~='default']) {
    --c-button-bg: var(--c-button-default-bg);
    --c-button-bg-hover: var(--c-button-default-bg-hover);
    --c-button-border: var(--c-button-default-border);
    --c-button-border-hover: var(--c-button-default-border-hover);
    --c-button-fg: var(--c-button-default-fg);
    --c-button-fg-hover: var(--c-button-default-fg-hover);
  }

  :host([variant~='primary']) {
    --c-button-bg: var(--c-button-primary-bg);
    --c-button-bg-hover: var(--c-button-primary-bg-hover);
    --c-button-border: var(--c-button-primary-border);
    --c-button-border-hover: var(--c-button-primary-border-hover);
    --c-button-fg: var(--c-button-primary-fg);
    --c-button-fg-hover: var(--c-button-primary-fg-hover);
  }

  :host([variant~='danger']) {
    --c-button-bg: var(--c-button-danger-bg);
    --c-button-bg-hover: var(--c-button-danger-bg-hover);
    --c-button-border: var(--c-button-danger-border);
    --c-button-border-hover: var(--c-button-danger-border-hover);
    --c-button-fg: var(--c-button-danger-fg);
    --c-button-fg-hover: var(--c-button-danger-fg-hover);
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
`,cl=Object.prototype.toString;function dl(i){return typeof i=="function"||cl.call(i)==="[object Function]"}function ul(i){var e=Number(i);return isNaN(e)?0:e===0||!isFinite(e)?e:(e>0?1:-1)*Math.floor(Math.abs(e))}var hl=2**53-1;function pl(i){var e=ul(i);return Math.min(Math.max(e,0),hl)}function ke(i,e){var t=Array,s=Object(i);if(i==null)throw TypeError("Array.from requires an array-like object - not null or undefined");for(var n=pl(s.length),o=dl(t)?Object(new t(n)):Array(n),r=0,a;r<n;)a=s[r],o[r]=a,r+=1;return o.length=n,o}function zt(i){"@babel/helpers - typeof";return zt=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(e){return typeof e}:function(e){return e&&typeof Symbol=="function"&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},zt(i)}function fl(i,e){if(!(i instanceof e))throw TypeError("Cannot call a class as a function")}function ml(i,e){for(var t=0;t<e.length;t++){var s=e[t];s.enumerable=s.enumerable||!1,s.configurable=!0,"value"in s&&(s.writable=!0),Object.defineProperty(i,Eo(s.key),s)}}function bl(i,e,t){return e&&ml(i.prototype,e),Object.defineProperty(i,"prototype",{writable:!1}),i}function gl(i,e,t){return e=Eo(e),e in i?Object.defineProperty(i,e,{value:t,enumerable:!0,configurable:!0,writable:!0}):i[e]=t,i}function Eo(i){var e=vl(i,"string");return zt(e)==="symbol"?e:String(e)}function vl(i,e){if(zt(i)!=="object"||i===null)return i;var t=i[Symbol.toPrimitive];if(t!==void 0){var s=t.call(i,e);if(zt(s)!=="object")return s;throw TypeError("@@toPrimitive must return a primitive value.")}return(e==="string"?String:Number)(i)}var _l=(function(){function i(){var e=arguments.length>0&&arguments[0]!==void 0?arguments[0]:[];fl(this,i),gl(this,"items",void 0),this.items=e}return bl(i,[{key:"add",value:function(e){return this.has(e)===!1&&this.items.push(e),this}},{key:"clear",value:function(){this.items=[]}},{key:"delete",value:function(e){var t=this.items.length;return this.items=this.items.filter(function(s){return s!==e}),t!==this.items.length}},{key:"forEach",value:function(e){var t=this;this.items.forEach(function(s){e(s,s,t)})}},{key:"has",value:function(e){return this.items.indexOf(e)!==-1}},{key:"size",get:function(){return this.items.length}}]),i})(),yl=typeof Set>"u"?Set:_l;function de(i){return i.localName??i.tagName.toLowerCase()}var wl={article:"article",aside:"complementary",button:"button",datalist:"listbox",dd:"definition",details:"group",dialog:"dialog",dt:"term",fieldset:"group",figure:"figure",form:"form",footer:"contentinfo",h1:"heading",h2:"heading",h3:"heading",h4:"heading",h5:"heading",h6:"heading",header:"banner",hr:"separator",html:"document",legend:"legend",li:"listitem",math:"math",main:"main",menu:"list",nav:"navigation",ol:"list",optgroup:"group",option:"option",output:"status",progress:"progressbar",section:"region",summary:"button",table:"table",tbody:"rowgroup",textarea:"textbox",tfoot:"rowgroup",td:"cell",th:"columnheader",thead:"rowgroup",tr:"row",ul:"list"},xl={caption:new Set(["aria-label","aria-labelledby"]),code:new Set(["aria-label","aria-labelledby"]),deletion:new Set(["aria-label","aria-labelledby"]),emphasis:new Set(["aria-label","aria-labelledby"]),generic:new Set(["aria-label","aria-labelledby","aria-roledescription"]),insertion:new Set(["aria-label","aria-labelledby"]),paragraph:new Set(["aria-label","aria-labelledby"]),presentation:new Set(["aria-label","aria-labelledby"]),strong:new Set(["aria-label","aria-labelledby"]),subscript:new Set(["aria-label","aria-labelledby"]),superscript:new Set(["aria-label","aria-labelledby"])};function El(i,e){return["aria-atomic","aria-busy","aria-controls","aria-current","aria-describedby","aria-details","aria-dropeffect","aria-flowto","aria-grabbed","aria-hidden","aria-keyshortcuts","aria-label","aria-labelledby","aria-live","aria-owns","aria-relevant","aria-roledescription"].some(function(t){var s;return i.hasAttribute(t)&&!((s=xl[e])!=null&&s.has(t))})}function ko(i,e){return El(i,e)}function kl(i){var e=Sl(i);if(e===null||e==="presentation"){var t=Cl(i);if(e!=="presentation"||ko(i,t||""))return t}return e}function Cl(i){var e=wl[de(i)];if(e!==void 0)return e;switch(de(i)){case"a":case"area":case"link":if(i.hasAttribute("href"))return"link";break;case"img":return i.getAttribute("alt")===""&&!ko(i,"img")?"presentation":"img";case"input":var t=i.type;switch(t){case"button":case"image":case"reset":case"submit":return"button";case"checkbox":case"radio":return t;case"range":return"slider";case"email":case"tel":case"text":case"url":return i.hasAttribute("list")?"combobox":"textbox";case"search":return i.hasAttribute("list")?"combobox":"searchbox";case"number":return"spinbutton";default:return null}case"select":return i.hasAttribute("multiple")||i.size>1?"listbox":"combobox"}return null}function Sl(i){var e=i.getAttribute("role");if(e!==null){var t=e.trim().split(" ")[0];if(t.length>0)return t}return null}function Z(i){return i!==null&&i.nodeType===i.ELEMENT_NODE}function Co(i){return Z(i)&&de(i)==="caption"}function pi(i){return Z(i)&&de(i)==="input"}function Al(i){return Z(i)&&de(i)==="optgroup"}function Tl(i){return Z(i)&&de(i)==="select"}function Nl(i){return Z(i)&&de(i)==="table"}function Fl(i){return Z(i)&&de(i)==="textarea"}function Ll(i){var e=(i.ownerDocument===null?i:i.ownerDocument).defaultView;if(e===null)throw TypeError("no window available");return e}function Ol(i){return Z(i)&&de(i)==="fieldset"}function Il(i){return Z(i)&&de(i)==="legend"}function Dl(i){return Z(i)&&de(i)==="slot"}function $l(i){return Z(i)&&i.ownerSVGElement!==void 0}function Ml(i){return Z(i)&&de(i)==="svg"}function Vl(i){return $l(i)&&de(i)==="title"}function Es(i,e){if(Z(i)&&i.hasAttribute(e)){var t=i.getAttribute(e).split(" "),s=i.getRootNode?i.getRootNode():i.ownerDocument;return t.map(function(n){return s.getElementById(n)}).filter(function(n){return n!==null})}return[]}function Ve(i,e){return Z(i)?e.indexOf(kl(i))!==-1:!1}function Rl(i){return i.trim().replace(/\s\s+/g," ")}function zl(i,e){if(!Z(i))return!1;if(i.hasAttribute("hidden")||i.getAttribute("aria-hidden")==="true")return!0;var t=e(i);return t.getPropertyValue("display")==="none"||t.getPropertyValue("visibility")==="hidden"}function Pl(i){return Ve(i,["button","combobox","listbox","textbox"])||So(i,"range")}function So(i,e){if(!Z(i))return!1;if(e==="range")return Ve(i,["meter","progressbar","scrollbar","slider","spinbutton"]);throw TypeError(`No knowledge about abstract role '${e}'. This is likely a bug :(`)}function _n(i,e){var t=ke(i.querySelectorAll(e));return Es(i,"aria-owns").forEach(function(s){t.push.apply(t,ke(s.querySelectorAll(e)))}),t}function Bl(i){return Tl(i)?i.selectedOptions||_n(i,"[selected]"):_n(i,'[aria-selected="true"]')}function Ul(i){return Ve(i,["none","presentation"])}function Hl(i){return Co(i)}function ql(i){return Ve(i,["button","cell","checkbox","columnheader","gridcell","heading","label","legend","link","menuitem","menuitemcheckbox","menuitemradio","option","radio","row","rowheader","switch","tab","tooltip","treeitem"])}function Wl(i){return!1}function jl(i){return pi(i)||Fl(i)?i.value:i.textContent||""}function yn(i){var e=i.getPropertyValue("content");return/^["'].*["']$/.test(e)?e.slice(1,-1):""}function Ao(i){var e=de(i);return e==="button"||e==="input"&&i.getAttribute("type")!=="hidden"||e==="meter"||e==="output"||e==="progress"||e==="select"||e==="textarea"}function To(i){if(Ao(i))return i;var e=null;return i.childNodes.forEach(function(t){if(e===null&&Z(t)){var s=To(t);s!==null&&(e=s)}}),e}function Kl(i){if(i.control!==void 0)return i.control;var e=i.getAttribute("for");return e===null?To(i):i.ownerDocument.getElementById(e)}function Gl(i){var e=i.labels;if(e===null)return e;if(e!==void 0)return ke(e);if(!Ao(i))return null;var t=i.ownerDocument;return ke(t.querySelectorAll("label")).filter(function(s){return Kl(s)===i})}function Yl(i){var e=i.assignedNodes();return e.length===0?ke(i.childNodes):e}function Zl(i){var e=arguments.length>1&&arguments[1]!==void 0?arguments[1]:{},t=new yl,s=Ll(i),n=e.compute,o=n===void 0?"name":n,r=e.computedStyleSupportsPseudoElements,a=r===void 0?e.getComputedStyle!==void 0:r,c=e.getComputedStyle,u=c===void 0?s.getComputedStyle.bind(s):c,h=e.hidden,g=h===void 0?!1:h;function f(p,k){var C="";if(Z(p)&&a&&(C=`${yn(u(p,"::before"))} ${C}`),(Dl(p)?Yl(p):ke(p.childNodes).concat(Es(p,"aria-owns"))).forEach(function(I){var R=x(I,{isEmbeddedInLabel:k.isEmbeddedInLabel,isReferenced:!1,recursion:!0}),z=(Z(I)?u(I).getPropertyValue("display"):"inline")==="inline"?"":" ";C+=`${z}${R}${z}`}),Z(p)&&a){var S=yn(u(p,"::after"));C=`${C} ${S}`}return C.trim()}function m(p,k){var C=p.getAttributeNode(k);return C!==null&&!t.has(C)&&C.value.trim()!==""?(t.add(C),C.value):null}function y(p){return Z(p)?m(p,"title"):null}function _(p){if(!Z(p))return null;if(Ol(p)){t.add(p);for(var k=ke(p.childNodes),C=0;C<k.length;C+=1){var S=k[C];if(Il(S))return x(S,{isEmbeddedInLabel:!1,isReferenced:!1,recursion:!1})}}else if(Nl(p)){t.add(p);for(var I=ke(p.childNodes),R=0;R<I.length;R+=1){var z=I[R];if(Co(z))return x(z,{isEmbeddedInLabel:!1,isReferenced:!1,recursion:!1})}}else if(Ml(p)){t.add(p);for(var G=ke(p.childNodes),B=0;B<G.length;B+=1){var D=G[B];if(Vl(D))return D.textContent}return null}else if(de(p)==="img"||de(p)==="area"){var ne=m(p,"alt");if(ne!==null)return ne}else if(Al(p)){var oe=m(p,"label");if(oe!==null)return oe}if(pi(p)&&(p.type==="button"||p.type==="submit"||p.type==="reset")){var l=m(p,"value");if(l!==null)return l;if(p.type==="submit")return"Submit";if(p.type==="reset")return"Reset"}var A=Gl(p);if(A!==null&&A.length!==0)return t.add(p),ke(A).map(function(N){return x(N,{isEmbeddedInLabel:!0,isReferenced:!1,recursion:!0})}).filter(function(N){return N.length>0}).join(" ");if(pi(p)&&p.type==="image"){var L=m(p,"alt");if(L!==null)return L;var V=m(p,"title");return V===null?"Submit Query":V}if(Ve(p,["button"])){var M=f(p,{isEmbeddedInLabel:!1});if(M!=="")return M}return null}function x(p,k){if(t.has(p))return"";if(!g&&zl(p,u)&&!k.isReferenced)return t.add(p),"";var C=Z(p)?p.getAttributeNode("aria-labelledby"):null,S=C!==null&&!t.has(C)?Es(p,"aria-labelledby"):[];if(o==="name"&&!k.isReferenced&&S.length>0)return t.add(C),S.map(function(ne){return x(ne,{isEmbeddedInLabel:k.isEmbeddedInLabel,isReferenced:!0,recursion:!1})}).join(" ");var I=k.recursion&&Pl(p)&&o==="name";if(!I){var R=(Z(p)&&p.getAttribute("aria-label")||"").trim();if(R!==""&&o==="name")return t.add(p),R;if(!Ul(p)){var z=_(p);if(z!==null)return t.add(p),z}}if(Ve(p,["menu"]))return t.add(p),"";if(I||k.isEmbeddedInLabel||k.isReferenced){if(Ve(p,["combobox","listbox"])){t.add(p);var G=Bl(p);return G.length===0?pi(p)?p.value:"":ke(G).map(function(ne){return x(ne,{isEmbeddedInLabel:k.isEmbeddedInLabel,isReferenced:!1,recursion:!0})}).join(" ")}if(So(p,"range"))return t.add(p),p.hasAttribute("aria-valuetext")?p.getAttribute("aria-valuetext"):p.hasAttribute("aria-valuenow")?p.getAttribute("aria-valuenow"):p.getAttribute("value")||"";if(Ve(p,["textbox"]))return t.add(p),jl(p)}if(ql(p)||Z(p)&&k.isReferenced||Hl(p)||Wl()){var B=f(p,{isEmbeddedInLabel:k.isEmbeddedInLabel});if(B!=="")return t.add(p),B}if(p.nodeType===p.TEXT_NODE)return t.add(p),p.textContent||"";if(k.recursion)return t.add(p),f(p,{isEmbeddedInLabel:k.isEmbeddedInLabel});var D=y(p);return D===null?(t.add(p),""):(t.add(p),D)}return Rl(x(i,{isEmbeddedInLabel:!1,isReferenced:o==="description",recursion:!1}))}function Xl(i){return Ve(i,["caption","code","deletion","emphasis","generic","insertion","paragraph","presentation","strong","subscript","superscript"])}function Ql(i){var e=arguments.length>1&&arguments[1]!==void 0?arguments[1]:{};return Xl(i)?"":Zl(i,e)}var Be=class extends al{constructor(...i){super(...i),this.appearance="accent",this.variant="default",this.size="medium",this.loading=!1,this.align="center",this._hasAccessibilityError=!1}static get styles(){return[...super.styles,ll]}async firstUpdated(i){super.firstUpdated(i),await this.updateComplete;let e=this.querySelectorAll("craft-icon, craft-spinner");await Promise.all(Array.from(e).map(t=>t.updateComplete)),this.accessibleName||(this.accessibleName=Ql(this)),this._hasAccessibilityError=!this.accessibleName||this.accessibleName.trim()===""}render(){return w`
      <div
        class="${pe({"button-content":!0,"button-content--start":this.align==="start","button-content--end":this.align==="end","a11y-error":this._hasAccessibilityError})}"
        part="content"
      >
        <slot name="prefix" class="prefix" part="prefix"></slot>
        <slot class="label" part="label"></slot>
        <slot name="suffix" class="suffix" part="suffix"></slot>
      </div>
      ${this.loading?w`<craft-spinner part="spinner"></craft-spinner>`:K}
    `}};T([b()],Be.prototype,"accessibleName",void 0),T([b({reflect:!0})],Be.prototype,"appearance",void 0),T([b({reflect:!0})],Be.prototype,"variant",void 0),T([b({reflect:!0})],Be.prototype,"size",void 0),T([b({reflect:!0,type:Boolean})],Be.prototype,"loading",void 0),T([b()],Be.prototype,"align",void 0),T([he()],Be.prototype,"_hasAccessibilityError",void 0),customElements.get("craft-button")||customElements.define("craft-button",Be);var Jl=class extends Event{constructor(){super("wa-load",{bubbles:!0,cancelable:!1,composed:!0})}};var ec=class extends Event{constructor(){super("wa-error",{bubbles:!0,cancelable:!1,composed:!0})}},tc=`:host {
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
`,Ct=Symbol(),ti=Symbol(),Qi,Ji=new Map,ue=class extends fe{constructor(){super(...arguments),this.svg=null,this.autoWidth=!1,this.swapOpacity=!1,this.label="",this.library="default",this.resolveIcon=async(i,e)=>{let t;if(e?.spriteSheet){this.hasUpdated||await this.updateComplete,this.svg=w`<svg part="svg">
        <use part="use" href="${i}"></use>
      </svg>`,await this.updateComplete;const s=this.shadowRoot.querySelector("[part='svg']");return typeof e.mutator=="function"&&e.mutator(s,this),this.svg}try{if(t=await fetch(i,{mode:"cors"}),!t.ok)return t.status===410?Ct:ti}catch{return ti}try{const s=document.createElement("div");s.innerHTML=await t.text();const n=s.firstElementChild;if(n?.tagName?.toLowerCase()!=="svg")return Ct;Qi||(Qi=new DOMParser);const r=Qi.parseFromString(n.outerHTML,"text/html").body.querySelector("svg");return r?(r.part.add("svg"),document.adoptNode(r)):Ct}catch{return Ct}}}connectedCallback(){super.connectedCallback(),kr(this)}firstUpdated(i){super.firstUpdated(i),this.setIcon()}disconnectedCallback(){super.disconnectedCallback(),Cr(this)}getIconSource(){const i=ji(this.library),e=this.family||Tr();return this.name&&i?{url:i.resolver(this.name,e,this.variant,this.autoWidth),fromLibrary:!0}:{url:this.src,fromLibrary:!1}}handleLabelChange(){typeof this.label=="string"&&this.label.length>0?(this.setAttribute("role","img"),this.setAttribute("aria-label",this.label),this.removeAttribute("aria-hidden")):(this.removeAttribute("role"),this.removeAttribute("aria-label"),this.setAttribute("aria-hidden","true"))}async setIcon(){const{url:i,fromLibrary:e}=this.getIconSource(),t=e?ji(this.library):void 0;if(!i){this.svg=null;return}let s=Ji.get(i);s||(s=this.resolveIcon(i,t),Ji.set(i,s));const n=await s;if(n===ti&&Ji.delete(i),i===this.getIconSource().url){if(xo(n)){this.svg=n;return}switch(n){case ti:case Ct:this.svg=null,this.dispatchEvent(new ec);break;default:this.svg=n.cloneNode(!0),t?.mutator?.(this.svg,this),this.dispatchEvent(new Jl)}}}updated(i){super.updated(i);const e=ji(this.library),t=this.shadowRoot?.querySelector("svg");t&&e?.mutator?.(t,this)}render(){return this.hasUpdated?this.svg:w`<svg part="svg" fill="currentColor" width="16" height="16"></svg>`}};ue.css=tc;v([he()],ue.prototype,"svg",2);v([b({reflect:!0})],ue.prototype,"name",2);v([b({reflect:!0})],ue.prototype,"family",2);v([b({reflect:!0})],ue.prototype,"variant",2);v([b({attribute:"auto-width",type:Boolean,reflect:!0})],ue.prototype,"autoWidth",2);v([b({attribute:"swap-opacity",type:Boolean,reflect:!0})],ue.prototype,"swapOpacity",2);v([b()],ue.prototype,"src",2);v([b()],ue.prototype,"label",2);v([b({reflect:!0})],ue.prototype,"library",2);v([ye("label")],ue.prototype,"handleLabelChange",1);v([ye(["family","name","library","variant","src","autoWidth","swapOpacity"])],ue.prototype,"setIcon",1);ue=v([Fe("wa-icon")],ue);var ic=F``,sc=class extends ue{static get styles(){return[ue.styles,ic]}};customElements.get("craft-icon")||customElements.define("craft-icon",sc);var nc=F`
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
`,ii=class extends H{constructor(...e){super(...e),this.label=null,this._gradientId=null}connectedCallback(){super.connectedCallback(),this._gradientId=`avatar-gradient-${Math.random().toString(36).slice(2,8)}`}text(){return this.label?this.label.split(" ").map(e=>e.charAt(0).toUpperCase()).join(""):"?"}render(){return w`
      <span class="avatar">
        <svg
          viewBox="0 0 100 100"
          xmlns="http://www.w3.org/2000/svg"
          role="img"
        >
          ${this.label?w`<title>${this.label}</title>`:""}
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
    `}};ii.styles=[nc],T([b()],ii.prototype,"label",void 0),T([he()],ii.prototype,"_gradientId",void 0),customElements.get("craft-avatar")||customElements.define("craft-avatar",ii);const js=F`
  font: inherit;
  color: var(--c-input-fg, var(--c-fg-text));
  position: relative;
  min-height: var(--c-input-height, var(--c-size-control-md));
  border: var(--c-input-border, 1px solid var(--c-form-control-border));
  border-radius: var(--c-input-radius, var(--c-radius-sm));
  padding-block: 0;
  width: 100%;
  flex: 1 1 auto;
  padding-inline: var(--c-input-spacing-inline);
  background-color: var(--c-input-bg, var(--c-form-control-bg));
  box-shadow: var(--c-input-shadow);

  /* Detect mobile devices and up the font size of inputs to avoid zoom on focus */
  @media (pointer: none), (pointer: coarse) {
    font-size: 1rem;
  }
`,Vi=F`
  :host(:not([label-sr-only])) .form-field__group-one {
    margin-block-end: var(--c-spacing-sm);
  }

  :host([has-feedback-for='error']) {
    color: var(--c-color-danger-on-normal);

    ::slotted([slot='input']) {
      border-color: var(--c-color-danger-border-emphasis);
    }
  }

  ::slotted(label) {
    line-height: 1;
    font-weight: bold;
    font-size: var(--text-sm);
  }

  .form-field__help-text {
    font-size: 1em;
    color: var(--c-fg-muted);
  }

  .form-field__group-one {
    margin-block-end: var(--c-spacing-sm);
  }

  .input-group__after {
    margin-block-start: var(--c-spacing-sm);
  }
`,Gt=F`
  ${Vi}

  ::slotted([slot='input']) {
    ${js}
  }
`;var oc=F`
  /* If an input has a "size" attribute, it should not grow */
  :host([size]) ::slotted(.form-control) {
    width: auto;
    flex: 0 0 auto;
  }

  craft-input input[type='checkbox'],
  craft-input input[type='radio'] {
    background-color: var(--c-input-bg, var(--c-form-control-bg));
    border: var(--c-input-border, 1px solid var(--c-form-control-border));
    border-radius: var(--c-input-radius, var(--c-radius-sm));
  }

  [slot='help-text'] {
    font-size: var(--c-text-base);
    color: var(--c-fg-muted);
  }
`;const ks=window,wn=new WeakMap;function rc(i){ks.applyFocusVisiblePolyfill&&!wn.has(i)&&(ks.applyFocusVisiblePolyfill(i),wn.set(i,void 0))}const ac=i=>class extends i{static get properties(){return{focused:{type:Boolean,reflect:!0},focusedVisible:{type:Boolean,reflect:!0,attribute:"focused-visible"},autofocus:{type:Boolean,reflect:!0}}}constructor(){super(),this.focused=!1,this.focusedVisible=!1,this.autofocus=!1}firstUpdated(t){super.firstUpdated(t),this.__registerEventsForFocusMixin(),this.__syncAutofocusToFocusableElement()}disconnectedCallback(){super.disconnectedCallback(),this.__teardownEventsForFocusMixin()}updated(t){super.updated(t),t.has("autofocus")&&this.__syncAutofocusToFocusableElement()}__syncAutofocusToFocusableElement(){this._focusableNode&&(this.hasAttribute("autofocus")?this._focusableNode.setAttribute("autofocus",""):this._focusableNode.removeAttribute("autofocus"))}focus(){this._focusableNode?.focus()}blur(){this._focusableNode?.blur()}get _focusableNode(){return this._inputNode||document.createElement("input")}__onFocus(){if(this.focused=!0,typeof ks.applyFocusVisiblePolyfill=="function")this.focusedVisible=this._focusableNode.hasAttribute("data-focus-visible-added");else try{this.focusedVisible=this._focusableNode.matches(":focus-visible")}catch{this.focusedVisible=!1}}__onBlur(){this.focused=!1,this.focusedVisible=!1}__registerEventsForFocusMixin(){rc(this.getRootNode()),this.__redispatchFocus=t=>{t.stopPropagation(),this.dispatchEvent(new Event("focus"))},this._focusableNode.addEventListener("focus",this.__redispatchFocus),this.__redispatchBlur=t=>{t.stopPropagation(),this.dispatchEvent(new Event("blur"))},this._focusableNode.addEventListener("blur",this.__redispatchBlur),this.__redispatchFocusin=t=>{t.stopPropagation(),this.__onFocus(),this.dispatchEvent(new Event("focusin",{bubbles:!0,composed:!0}))},this._focusableNode.addEventListener("focusin",this.__redispatchFocusin),this.__redispatchFocusout=t=>{t.stopPropagation(),this.__onBlur(),this.dispatchEvent(new Event("focusout",{bubbles:!0,composed:!0}))},this._focusableNode.addEventListener("focusout",this.__redispatchFocusout)}__teardownEventsForFocusMixin(){this._focusableNode&&(this._focusableNode?.removeEventListener("focus",this.__redispatchFocus),this._focusableNode?.removeEventListener("blur",this.__redispatchBlur),this._focusableNode?.removeEventListener("focusin",this.__redispatchFocusin),this._focusableNode?.removeEventListener("focusout",this.__redispatchFocusout))}},Ks=ee(ac);function No(i,e){return e={exports:{}},i(e,e.exports),e.exports}var Qe="long",Ue="short",es="narrow",P="numeric",He="2-digit",qe={number:{decimal:{style:"decimal"},integer:{style:"decimal",maximumFractionDigits:0},currency:{style:"currency",currency:"USD"},percent:{style:"percent"},default:{style:"decimal"}},date:{short:{month:P,day:P,year:He},medium:{month:Ue,day:P,year:P},long:{month:Qe,day:P,year:P},full:{month:Qe,day:P,year:P,weekday:Qe},default:{month:Ue,day:P,year:P}},time:{short:{hour:P,minute:P},medium:{hour:P,minute:P,second:P},long:{hour:P,minute:P,second:P,timeZoneName:Ue},full:{hour:P,minute:P,second:P,timeZoneName:Ue},default:{hour:P,minute:P,second:P}},duration:{default:{hours:{minimumIntegerDigits:1,maximumFractionDigits:0},minutes:{minimumIntegerDigits:2,maximumFractionDigits:0},seconds:{minimumIntegerDigits:2,maximumFractionDigits:3}}},parseNumberPattern:function(i){if(i){var e={},t=i.match(/\b[A-Z]{3}\b/i),s=i.replace(/[^¤]/g,"").length;if(!s&&t&&(s=1),s?(e.style="currency",e.currencyDisplay=s===1?"symbol":s===2?"code":"name",e.currency=t?t[0].toUpperCase():"USD"):i.indexOf("%")>=0&&(e.style="percent"),!/[@#0]/.test(i))return e.style?e:void 0;if(e.useGrouping=i.indexOf(",")>=0,/E\+?[@#0]+/i.test(i)||i.indexOf("@")>=0){var n=i.replace(/E\+?[@#0]+|[^@#0]/gi,"");e.minimumSignificantDigits=Math.min(Math.max(n.replace(/[^@0]/g,"").length,1),21),e.maximumSignificantDigits=Math.min(Math.max(n.length,1),21)}else{for(var o=i.replace(/[^#0.]/g,"").split("."),r=o[0],a=r.length-1;r[a]==="0";)--a;e.minimumIntegerDigits=Math.min(Math.max(r.length-1-a,1),21);var c=o[1]||"";for(a=0;c[a]==="0";)++a;for(e.minimumFractionDigits=Math.min(Math.max(a,0),20);c[a]==="#";)++a;e.maximumFractionDigits=Math.min(Math.max(a,0),20)}return e}},parseDatePattern:function(i){if(i){for(var e={},t=0;t<i.length;){for(var s=i[t],n=1;i[++t]===s;)++n;switch(s){case"G":e.era=n===5?es:n===4?Qe:Ue;break;case"y":case"Y":e.year=n===2?He:P;break;case"M":case"L":n=Math.min(Math.max(n-1,0),4),e.month=[P,He,Ue,Qe,es][n];break;case"E":case"e":case"c":e.weekday=n===5?es:n===4?Qe:Ue;break;case"d":case"D":e.day=n===2?He:P;break;case"h":case"K":e.hour12=!0,e.hour=n===2?He:P;break;case"H":case"k":e.hour12=!1,e.hour=n===2?He:P;break;case"m":e.minute=n===2?He:P;break;case"s":case"S":e.second=n===2?He:P;break;case"z":case"Z":case"v":case"V":e.timeZoneName=n===1?Ue:Qe;break}}return Object.keys(e).length?e:void 0}}},lc=function(e,t){if(typeof e=="string"&&t[e])return e;for(var s=[].concat(e||[]),n=0,o=s.length;n<o;++n)for(var r=s[n].split("-");r.length;){var a=r.join("-");if(t[a])return a;r.pop()}},at="zero",$="one",ie="two",q="few",te="many",O="other",d=[function(i){var e=+i;return e===1?$:O},function(i){var e=+i;return 0<=e&&e<=1?$:O},function(i){var e=Math.floor(Math.abs(+i)),t=+i;return e===0||t===1?$:O},function(i){var e=+i;return e===0?at:e===1?$:e===2?ie:3<=e%100&&e%100<=10?q:11<=e%100&&e%100<=99?te:O},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length;return e===1&&t===0?$:O},function(i){var e=+i;return e%10===1&&e%100!==11?$:2<=e%10&&e%10<=4&&(e%100<12||14<e%100)?q:e%10===0||5<=e%10&&e%10<=9||11<=e%100&&e%100<=14?te:O},function(i){var e=+i;return e%10===1&&e%100!==11&&e%100!==71&&e%100!==91?$:e%10===2&&e%100!==12&&e%100!==72&&e%100!==92?ie:(3<=e%10&&e%10<=4||e%10===9)&&(e%100<10||19<e%100)&&(e%100<70||79<e%100)&&(e%100<90||99<e%100)?q:e!==0&&e%1e6===0?te:O},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length,s=+(i+".").split(".")[1];return t===0&&e%10===1&&e%100!==11||s%10===1&&s%100!==11?$:t===0&&2<=e%10&&e%10<=4&&(e%100<12||14<e%100)||2<=s%10&&s%10<=4&&(s%100<12||14<s%100)?q:O},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length;return e===1&&t===0?$:2<=e&&e<=4&&t===0?q:t!==0?te:O},function(i){var e=+i;return e===0?at:e===1?$:e===2?ie:e===3?q:e===6?te:O},function(i){var e=Math.floor(Math.abs(+i)),t=+(""+i).replace(/^[^.]*.?|0+$/g,""),s=+i;return s===1||t!==0&&(e===0||e===1)?$:O},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length,s=+(i+".").split(".")[1];return t===0&&e%100===1||s%100===1?$:t===0&&e%100===2||s%100===2?ie:t===0&&3<=e%100&&e%100<=4||3<=s%100&&s%100<=4?q:O},function(i){var e=Math.floor(Math.abs(+i));return e===0||e===1?$:O},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length,s=+(i+".").split(".")[1];return t===0&&(e===1||e===2||e===3)||t===0&&e%10!==4&&e%10!==6&&e%10!==9||t!==0&&s%10!==4&&s%10!==6&&s%10!==9?$:O},function(i){var e=+i;return e===1?$:e===2?ie:3<=e&&e<=6?q:7<=e&&e<=10?te:O},function(i){var e=+i;return e===1||e===11?$:e===2||e===12?ie:3<=e&&e<=10||13<=e&&e<=19?q:O},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length;return t===0&&e%10===1?$:t===0&&e%10===2?ie:t===0&&(e%100===0||e%100===20||e%100===40||e%100===60||e%100===80)?q:t!==0?te:O},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length,s=+i;return e===1&&t===0?$:e===2&&t===0?ie:t===0&&(s<0||10<s)&&s%10===0?te:O},function(i){var e=Math.floor(Math.abs(+i)),t=+(""+i).replace(/^[^.]*.?|0+$/g,"");return t===0&&e%10===1&&e%100!==11||t!==0?$:O},function(i){var e=+i;return e===1?$:e===2?ie:O},function(i){var e=+i;return e===0?at:e===1?$:O},function(i){var e=Math.floor(Math.abs(+i)),t=+i;return t===0?at:(e===0||e===1)&&t!==0?$:O},function(i){var e=+(i+".").split(".")[1],t=+i;return t%10===1&&(t%100<11||19<t%100)?$:2<=t%10&&t%10<=9&&(t%100<11||19<t%100)?q:e!==0?te:O},function(i){var e=(i+".").split(".")[1].length,t=+(i+".").split(".")[1],s=+i;return s%10===0||11<=s%100&&s%100<=19||e===2&&11<=t%100&&t%100<=19?at:s%10===1&&s%100!==11||e===2&&t%10===1&&t%100!==11||e!==2&&t%10===1?$:O},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length,s=+(i+".").split(".")[1];return t===0&&e%10===1&&e%100!==11||s%10===1&&s%100!==11?$:O},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length,s=+i;return e===1&&t===0?$:t!==0||s===0||s!==1&&1<=s%100&&s%100<=19?q:O},function(i){var e=+i;return e===1?$:e===0||2<=e%100&&e%100<=10?q:11<=e%100&&e%100<=19?te:O},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length;return e===1&&t===0?$:t===0&&2<=e%10&&e%10<=4&&(e%100<12||14<e%100)?q:t===0&&e!==1&&0<=e%10&&e%10<=1||t===0&&5<=e%10&&e%10<=9||t===0&&12<=e%100&&e%100<=14?te:O},function(i){var e=Math.floor(Math.abs(+i));return 0<=e&&e<=1?$:O},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length;return t===0&&e%10===1&&e%100!==11?$:t===0&&2<=e%10&&e%10<=4&&(e%100<12||14<e%100)?q:t===0&&e%10===0||t===0&&5<=e%10&&e%10<=9||t===0&&11<=e%100&&e%100<=14?te:O},function(i){var e=Math.floor(Math.abs(+i)),t=+i;return e===0||t===1?$:2<=t&&t<=10?q:O},function(i){var e=Math.floor(Math.abs(+i)),t=+(i+".").split(".")[1],s=+i;return s===0||s===1||e===0&&t===1?$:O},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length;return t===0&&e%100===1?$:t===0&&e%100===2?ie:t===0&&3<=e%100&&e%100<=4||t!==0?q:O},function(i){var e=+i;return 0<=e&&e<=1||11<=e&&e<=99?$:O},function(i){var e=+i;return e===1||e===5||e===7||e===8||e===9||e===10?$:e===2||e===3?ie:e===4?q:e===6?te:O},function(i){var e=Math.floor(Math.abs(+i));return e%10===1||e%10===2||e%10===5||e%10===7||e%10===8||e%100===20||e%100===50||e%100===70||e%100===80?$:e%10===3||e%10===4||e%1e3===100||e%1e3===200||e%1e3===300||e%1e3===400||e%1e3===500||e%1e3===600||e%1e3===700||e%1e3===800||e%1e3===900?q:e===0||e%10===6||e%100===40||e%100===60||e%100===90?te:O},function(i){var e=+i;return(e%10===2||e%10===3)&&e%100!==12&&e%100!==13?q:O},function(i){var e=+i;return e===1||e===3?$:e===2?ie:e===4?q:O},function(i){var e=+i;return e===0||e===7||e===8||e===9?at:e===1?$:e===2?ie:e===3||e===4?q:e===5||e===6?te:O},function(i){var e=+i;return e%10===1&&e%100!==11?$:e%10===2&&e%100!==12?ie:e%10===3&&e%100!==13?q:O},function(i){var e=+i;return e===1||e===11?$:e===2||e===12?ie:e===3||e===13?q:O},function(i){var e=+i;return e===1?$:e===2||e===3?ie:e===4?q:e===6?te:O},function(i){var e=+i;return e===1||e===5?$:O},function(i){var e=+i;return e===11||e===8||e===80||e===800?te:O},function(i){var e=Math.floor(Math.abs(+i));return e===1?$:e===0||2<=e%100&&e%100<=20||e%100===40||e%100===60||e%100===80?te:O},function(i){var e=+i;return e%10===6||e%10===9||e%10===0&&e!==0?te:O},function(i){var e=Math.floor(Math.abs(+i));return e%10===1&&e%100!==11?$:e%10===2&&e%100!==12?ie:(e%10===7||e%10===8)&&e%100!==17&&e%100!==18?te:O},function(i){var e=+i;return e===1?$:e===2||e===3?ie:e===4?q:O},function(i){var e=+i;return 1<=e&&e<=4?$:O},function(i){var e=+i;return e===1||e===5||7<=e&&e<=9?$:e===2||e===3?ie:e===4?q:e===6?te:O},function(i){var e=+i;return e===1?$:e%10===4&&e%100!==14?te:O},function(i){var e=+i;return(e%10===1||e%10===2)&&e%100!==11&&e%100!==12?$:O},function(i){var e=+i;return e%10===6||e%10===9||e===10?q:O},function(i){var e=+i;return e%10===3&&e%100!==13?q:O}],Cs={af:{cardinal:d[0]},ak:{cardinal:d[1]},am:{cardinal:d[2]},ar:{cardinal:d[3]},ars:{cardinal:d[3]},as:{cardinal:d[2],ordinal:d[34]},asa:{cardinal:d[0]},ast:{cardinal:d[4]},az:{cardinal:d[0],ordinal:d[35]},be:{cardinal:d[5],ordinal:d[36]},bem:{cardinal:d[0]},bez:{cardinal:d[0]},bg:{cardinal:d[0]},bh:{cardinal:d[1]},bn:{cardinal:d[2],ordinal:d[34]},br:{cardinal:d[6]},brx:{cardinal:d[0]},bs:{cardinal:d[7]},ca:{cardinal:d[4],ordinal:d[37]},ce:{cardinal:d[0]},cgg:{cardinal:d[0]},chr:{cardinal:d[0]},ckb:{cardinal:d[0]},cs:{cardinal:d[8]},cy:{cardinal:d[9],ordinal:d[38]},da:{cardinal:d[10]},de:{cardinal:d[4]},dsb:{cardinal:d[11]},dv:{cardinal:d[0]},ee:{cardinal:d[0]},el:{cardinal:d[0]},en:{cardinal:d[4],ordinal:d[39]},eo:{cardinal:d[0]},es:{cardinal:d[0]},et:{cardinal:d[4]},eu:{cardinal:d[0]},fa:{cardinal:d[2]},ff:{cardinal:d[12]},fi:{cardinal:d[4]},fil:{cardinal:d[13],ordinal:d[0]},fo:{cardinal:d[0]},fr:{cardinal:d[12],ordinal:d[0]},fur:{cardinal:d[0]},fy:{cardinal:d[4]},ga:{cardinal:d[14],ordinal:d[0]},gd:{cardinal:d[15],ordinal:d[40]},gl:{cardinal:d[4]},gsw:{cardinal:d[0]},gu:{cardinal:d[2],ordinal:d[41]},guw:{cardinal:d[1]},gv:{cardinal:d[16]},ha:{cardinal:d[0]},haw:{cardinal:d[0]},he:{cardinal:d[17]},hi:{cardinal:d[2],ordinal:d[41]},hr:{cardinal:d[7]},hsb:{cardinal:d[11]},hu:{cardinal:d[0],ordinal:d[42]},hy:{cardinal:d[12],ordinal:d[0]},ia:{cardinal:d[4]},io:{cardinal:d[4]},is:{cardinal:d[18]},it:{cardinal:d[4],ordinal:d[43]},iu:{cardinal:d[19]},iw:{cardinal:d[17]},jgo:{cardinal:d[0]},ji:{cardinal:d[4]},jmc:{cardinal:d[0]},ka:{cardinal:d[0],ordinal:d[44]},kab:{cardinal:d[12]},kaj:{cardinal:d[0]},kcg:{cardinal:d[0]},kk:{cardinal:d[0],ordinal:d[45]},kkj:{cardinal:d[0]},kl:{cardinal:d[0]},kn:{cardinal:d[2]},ks:{cardinal:d[0]},ksb:{cardinal:d[0]},ksh:{cardinal:d[20]},ku:{cardinal:d[0]},kw:{cardinal:d[19]},ky:{cardinal:d[0]},lag:{cardinal:d[21]},lb:{cardinal:d[0]},lg:{cardinal:d[0]},ln:{cardinal:d[1]},lt:{cardinal:d[22]},lv:{cardinal:d[23]},mas:{cardinal:d[0]},mg:{cardinal:d[1]},mgo:{cardinal:d[0]},mk:{cardinal:d[24],ordinal:d[46]},ml:{cardinal:d[0]},mn:{cardinal:d[0]},mo:{cardinal:d[25],ordinal:d[0]},mr:{cardinal:d[2],ordinal:d[47]},mt:{cardinal:d[26]},nah:{cardinal:d[0]},naq:{cardinal:d[19]},nb:{cardinal:d[0]},nd:{cardinal:d[0]},ne:{cardinal:d[0],ordinal:d[48]},nl:{cardinal:d[4]},nn:{cardinal:d[0]},nnh:{cardinal:d[0]},no:{cardinal:d[0]},nr:{cardinal:d[0]},nso:{cardinal:d[1]},ny:{cardinal:d[0]},nyn:{cardinal:d[0]},om:{cardinal:d[0]},or:{cardinal:d[0],ordinal:d[49]},os:{cardinal:d[0]},pa:{cardinal:d[1]},pap:{cardinal:d[0]},pl:{cardinal:d[27]},prg:{cardinal:d[23]},ps:{cardinal:d[0]},pt:{cardinal:d[28]},"pt-PT":{cardinal:d[4]},rm:{cardinal:d[0]},ro:{cardinal:d[25],ordinal:d[0]},rof:{cardinal:d[0]},ru:{cardinal:d[29]},rwk:{cardinal:d[0]},saq:{cardinal:d[0]},sc:{cardinal:d[4],ordinal:d[43]},scn:{cardinal:d[4],ordinal:d[43]},sd:{cardinal:d[0]},sdh:{cardinal:d[0]},se:{cardinal:d[19]},seh:{cardinal:d[0]},sh:{cardinal:d[7]},shi:{cardinal:d[30]},si:{cardinal:d[31]},sk:{cardinal:d[8]},sl:{cardinal:d[32]},sma:{cardinal:d[19]},smi:{cardinal:d[19]},smj:{cardinal:d[19]},smn:{cardinal:d[19]},sms:{cardinal:d[19]},sn:{cardinal:d[0]},so:{cardinal:d[0]},sq:{cardinal:d[0],ordinal:d[50]},sr:{cardinal:d[7]},ss:{cardinal:d[0]},ssy:{cardinal:d[0]},st:{cardinal:d[0]},sv:{cardinal:d[4],ordinal:d[51]},sw:{cardinal:d[4]},syr:{cardinal:d[0]},ta:{cardinal:d[0]},te:{cardinal:d[0]},teo:{cardinal:d[0]},ti:{cardinal:d[1]},tig:{cardinal:d[0]},tk:{cardinal:d[0],ordinal:d[52]},tl:{cardinal:d[13],ordinal:d[0]},tn:{cardinal:d[0]},tr:{cardinal:d[0]},ts:{cardinal:d[0]},tzm:{cardinal:d[33]},ug:{cardinal:d[0]},uk:{cardinal:d[29],ordinal:d[53]},ur:{cardinal:d[4]},uz:{cardinal:d[0]},ve:{cardinal:d[0]},vo:{cardinal:d[0]},vun:{cardinal:d[0]},wa:{cardinal:d[1]},wae:{cardinal:d[0]},xh:{cardinal:d[0]},xog:{cardinal:d[0]},yi:{cardinal:d[4]},zu:{cardinal:d[2]},lo:{ordinal:d[0]},ms:{ordinal:d[0]},vi:{ordinal:d[0]}},Ri=No(function(i,e){e=i.exports=function(m,y,_){return t(m,null,y||"en",_||{},!0)},e.toParts=function(m,y,_){return t(m,null,y||"en",_||{},!1)};function t(f,m,y,_,x){var p=f.map(function(k){return s(k,m,y,_,x)});return x?p.length===1?p[0]:function(C){for(var S="",I=0;I<p.length;++I)S+=p[I](C);return S}:function(C){return p.reduce(function(S,I){return S.concat(I(C))},[])}}function s(f,m,y,_,x){if(typeof f=="string"){var p=f;return function(){return p}}var k=f[0],C=f[1];if(m&&f[0]==="#"){k=m[0];var S=m[2],I=(_.number||g.number)([k,"number"],y);return function(D){return I(n(k,D)-S,D)}}var R;C==="plural"||C==="selectordinal"?(R={},Object.keys(f[3]).forEach(function(B){R[B]=t(f[3][B],f,y,_,x)}),f=[f[0],f[1],f[2],R]):f[2]&&typeof f[2]=="object"&&(R={},Object.keys(f[2]).forEach(function(B){R[B]=t(f[2][B],f,y,_,x)}),f=[f[0],f[1],R]);var z=C&&(_[C]||g[C]);if(z){var G=z(f,y);return function(D){return G(n(k,D),D)}}return x?function(D){return String(n(k,D))}:function(D){return n(k,D)}}function n(f,m){if(m&&f in m)return m[f];for(var y=f.split("."),_=m,x=0,p=y.length;_&&x<p;++x)_=_[y[x]];return _}function o(f,m){var y=f[2],_=qe.number[y]||qe.parseNumberPattern(y)||qe.number.default;return new Intl.NumberFormat(m,_).format}function r(f,m){var y=f[2],_=qe.duration[y]||qe.duration.default,x=new Intl.NumberFormat(m,_.seconds).format,p=new Intl.NumberFormat(m,_.minutes).format,k=new Intl.NumberFormat(m,_.hours).format,C=/^fi$|^fi-|^da/.test(String(m))?".":":";return function(S,I){if(S=+S,!isFinite(S))return x(S);var R=~~(S/60/60),z=~~(S/60%60),G=(R?k(Math.abs(R))+C:"")+p(Math.abs(z))+C+x(Math.abs(S%60));return S<0?k(-1).replace(k(1),G):G}}function a(f,m){var y=f[1],_=f[2],x=qe[y][_]||qe.parseDatePattern(_)||qe[y].default;return new Intl.DateTimeFormat(m,x).format}function c(f,m){var y=f[1],_=y==="selectordinal"?"ordinal":"cardinal",x=f[2],p=f[3],k;if(Intl.PluralRules&&Intl.PluralRules.supportedLocalesOf(m).length>0)k=new Intl.PluralRules(m,{type:_});else{var C=lc(m,Cs),S=C&&Cs[C][_]||u;k={select:S}}return function(I,R){var z=p["="+ +I]||p[k.select(I-x)]||p.other;return z(R)}}function u(){return"other"}function h(f,m){var y=f[2];return function(_,x){var p=y[_]||y.other;return p(x)}}var g={number:o,ordinal:o,spellout:o,duration:r,date:a,time:a,plural:c,selectordinal:c,select:h};e.types=g});Ri.toParts;Ri.types;var Fo=No(function(i,e){var t="{",s="}",n=",",o="#",r="<",a=">",c="</",u="/>",h="'",g="offset:",f=["number","date","time","ordinal","duration","spellout"],m=["plural","select","selectordinal"];e=i.exports=function(A,L){return y({pattern:String(A),index:0,tagsType:L&&L.tagsType||null,tokens:L&&L.tokens||null},"")};function y(l,A){var L=l.pattern,V=L.length,M=[],N=l.index,j=_(l,A);for(j&&M.push(j),j&&l.tokens&&l.tokens.push(["text",L.slice(N,l.index)]);l.index<V;){if(L[l.index]===s){if(!A)throw D(l);break}if(A&&l.tagsType&&L.slice(l.index,l.index+c.length)===c)break;M.push(k(l)),N=l.index,j=_(l,A),j&&M.push(j),j&&l.tokens&&l.tokens.push(["text",L.slice(N,l.index)])}return M}function _(l,A){for(var L=l.pattern,V=L.length,M=A==="plural"||A==="selectordinal",N=!!l.tagsType,j=A==="{style}",me="";l.index<V;){var J=L[l.index];if(J===t||J===s||M&&J===o||N&&J===r||j&&x(J.charCodeAt(0)))break;if(J===h)if(J=L[++l.index],J===h)me+=J,++l.index;else if(J===t||J===s||M&&J===o||N&&J===r||j)for(me+=J;++l.index<V;)if(J=L[l.index],J===h&&L[l.index+1]===h)me+=h,++l.index;else if(J===h){++l.index;break}else me+=J;else me+=h;else me+=J,++l.index}return me}function x(l){return l>=9&&l<=13||l===32||l===133||l===160||l===6158||l>=8192&&l<=8205||l===8232||l===8233||l===8239||l===8287||l===8288||l===12288||l===65279}function p(l){for(var A=l.pattern,L=A.length,V=l.index;l.index<L&&x(A.charCodeAt(l.index));)++l.index;V<l.index&&l.tokens&&l.tokens.push(["space",l.pattern.slice(V,l.index)])}function k(l){var A=l.pattern;if(A[l.index]===o)return l.tokens&&l.tokens.push(["syntax",o]),++l.index,[o];var L=C(l);if(L)return L;if(A[l.index]!==t)throw D(l,t);l.tokens&&l.tokens.push(["syntax",t]),++l.index,p(l);var V=S(l);if(!V)throw D(l,"placeholder id");l.tokens&&l.tokens.push(["id",V]),p(l);var M=A[l.index];if(M===s)return l.tokens&&l.tokens.push(["syntax",s]),++l.index,[V];if(M!==n)throw D(l,n+" or "+s);l.tokens&&l.tokens.push(["syntax",n]),++l.index,p(l);var N=S(l);if(!N)throw D(l,"placeholder type");if(l.tokens&&l.tokens.push(["type",N]),p(l),M=A[l.index],M===s){if(l.tokens&&l.tokens.push(["syntax",s]),N==="plural"||N==="selectordinal"||N==="select")throw D(l,N+" sub-messages");return++l.index,[V,N]}if(M!==n)throw D(l,n+" or "+s);l.tokens&&l.tokens.push(["syntax",n]),++l.index,p(l);var j;if(N==="plural"||N==="selectordinal"){var me=R(l);p(l),j=[V,N,me,G(l,N)]}else if(N==="select")j=[V,N,G(l,N)];else if(f.indexOf(N)>=0)j=[V,N,I(l)];else{var J=l.index,Js=I(l);p(l),A[l.index]===t&&(l.index=J,Js=G(l,N)),j=[V,N,Js]}if(p(l),A[l.index]!==s)throw D(l,s);return l.tokens&&l.tokens.push(["syntax",s]),++l.index,j}function C(l){var A=l.tagsType;if(!(!A||l.pattern[l.index]!==r)){if(l.pattern.slice(l.index,l.index+c.length)===c)throw D(l,null,"closing tag without matching opening tag");l.tokens&&l.tokens.push(["syntax",r]),++l.index;var L=S(l,!0);if(!L)throw D(l,"placeholder id");if(l.tokens&&l.tokens.push(["id",L]),p(l),l.pattern.slice(l.index,l.index+u.length)===u)return l.tokens&&l.tokens.push(["syntax",u]),l.index+=u.length,[L,A];if(l.pattern[l.index]!==a)throw D(l,a);l.tokens&&l.tokens.push(["syntax",a]),++l.index;var V=y(l,A),M=l.index;if(l.pattern.slice(l.index,l.index+c.length)!==c)throw D(l,c+L+a);l.tokens&&l.tokens.push(["syntax",c]),l.index+=c.length;var N=S(l,!0);if(N&&l.tokens&&l.tokens.push(["id",N]),L!==N)throw l.index=M,D(l,c+L+a,c+N+a);if(p(l),l.pattern[l.index]!==a)throw D(l,a);return l.tokens&&l.tokens.push(["syntax",a]),++l.index,[L,A,{children:V}]}}function S(l,A){for(var L=l.pattern,V=L.length,M="";l.index<V;){var N=L[l.index];if(N===t||N===s||N===n||N===o||N===h||x(N.charCodeAt(0))||A&&(N===r||N===a||N==="/"))break;M+=N,++l.index}return M}function I(l){var A=l.index,L=_(l,"{style}");if(!L)throw D(l,"placeholder style name");return l.tokens&&l.tokens.push(["style",l.pattern.slice(A,l.index)]),L}function R(l){var A=l.pattern,L=A.length,V=0;if(A.slice(l.index,l.index+g.length)===g){l.tokens&&l.tokens.push(["offset","offset"],["syntax",":"]),l.index+=g.length,p(l);for(var M=l.index;l.index<L&&z(A.charCodeAt(l.index));)++l.index;if(M===l.index)throw D(l,"offset number");l.tokens&&l.tokens.push(["number",A.slice(M,l.index)]),V=+A.slice(M,l.index)}return V}function z(l){return l>=48&&l<=57}function G(l,A){for(var L=l.pattern,V=L.length,M={};l.index<V&&L[l.index]!==s;){var N=S(l);if(!N)throw D(l,"sub-message selector");l.tokens&&l.tokens.push(["selector",N]),p(l),M[N]=B(l,A),p(l)}if(!M.other&&m.indexOf(A)>=0)throw D(l,null,null,'"other" sub-message must be specified in '+A);return M}function B(l,A){if(l.pattern[l.index]!==t)throw D(l,t+" to start sub-message");l.tokens&&l.tokens.push(["syntax",t]),++l.index;var L=y(l,A);if(l.pattern[l.index]!==s)throw D(l,s+" to end sub-message");return l.tokens&&l.tokens.push(["syntax",s]),++l.index,L}function D(l,A,L,V){var M=l.pattern,N=M.slice(0,l.index).split(/\r?\n/),j=l.index,me=N.length,J=N.slice(-1)[0].length;return L=L||(l.index>=M.length?"end of message pattern":S(l)||M[l.index]),V||(V=ne(A,L)),V+=" in "+M.replace(/\r?\n/g,`
`),new oe(V,A,L,j,me,J)}function ne(l,A){return l?"Expected "+l+" but found "+A:"Unexpected "+A+" found"}function oe(l,A,L,V,M,N){Error.call(this,l),this.name="SyntaxError",this.message=l,this.expected=A,this.found=L,this.offset=V,this.line=M,this.column=N}oe.prototype=Object.create(Error.prototype),e.SyntaxError=oe});Fo.SyntaxError;var cc=new RegExp("^("+Object.keys(Cs).join("|")+")\\b"),$t=new WeakMap;function mt(i,e,t){if(!(this instanceof mt)||$t.has(this))throw new TypeError("calling MessageFormat constructor without new is invalid");var s=Fo(i);$t.set(this,{ast:s,format:Ri(s,e,t&&t.types),locale:mt.supportedLocalesOf(e)[0]||"en",locales:e,options:t})}var dc=mt;Object.defineProperties(mt.prototype,{format:{configurable:!0,get:function(){var e=$t.get(this);if(!e)throw new TypeError("MessageFormat.prototype.format called on value that's not an object initialized as a MessageFormat");return e.format}},formatToParts:{configurable:!0,writable:!0,value:function(e){var t=$t.get(this);if(!t)throw new TypeError("MessageFormat.prototype.formatToParts called on value that's not an object initialized as a MessageFormat");var s=t.toParts||(t.toParts=Ri.toParts(t.ast,t.locales,t.options&&t.options.types));return s(e)}},resolvedOptions:{configurable:!0,writable:!0,value:function(){var e=$t.get(this);if(!e)throw new TypeError("MessageFormat.prototype.resolvedOptions called on value that's not an object initialized as a MessageFormat");return{locale:e.locale}}}});typeof Symbol<"u"&&Object.defineProperty(mt.prototype,Symbol.toStringTag,{value:"Object"});Object.defineProperties(mt,{supportedLocalesOf:{configurable:!0,writable:!0,value:function(e){return[].concat(Intl.NumberFormat.supportedLocalesOf(e),Intl.DateTimeFormat.supportedLocalesOf(e),Intl.PluralRules?Intl.PluralRules.supportedLocalesOf(e):[],[].concat(e||[]).filter(function(t){return cc.test(t)})).filter(function(t,s,n){return n.indexOf(t)===s})}}});function uc(i){return!!(i&&i.default&&typeof i.default=="object"&&Object.keys(i).length===1)}const We=globalThis.document?.documentElement;class hc extends EventTarget{formatNumberOptions={returnIfNaN:"",postProcessors:new Map};formatDateOptions={postProcessors:new Map};#e=!1;#t="";#i=null;__storage={};__namespacePatternsMap=new Map;__namespaceLoadersCache={};__namespaceLoaderPromisesCache={};get locale(){return this.#e?this.#t||"":We.lang||""}set locale(e){if(this.#s(e),!this.#e){const n=We.lang;this._setHtmlLangAttribute(e),this._onLocaleChanged(e,n);return}const t=this.#t;this.#t=e,this.#i===null&&this._setHtmlLangAttribute(e),this._onLocaleChanged(e,t)}get loadingComplete(){return typeof this.__namespaceLoaderPromisesCache[this.locale]=="object"?Promise.all(Object.values(this.__namespaceLoaderPromisesCache[this.locale])):Promise.resolve()}constructor({allowOverridesForExistingNamespaces:e=!1,autoLoadOnLocaleChange:t=!1,showKeyAsFallback:s=!1,fallbackLocale:n=""}={}){super(),this.__allowOverridesForExistingNamespaces=e,this._autoLoadOnLocaleChange=!!t,this._showKeyAsFallback=s,this._fallbackLocale=n;const o=We.getAttribute("data-localize-lang");this.#e=!!o,this.#e&&(this.locale=o,this._setupTranslationToolSupport()),We.lang||(We.lang=this.locale||"en-GB"),this._setupHtmlLangAttributeObserver()}addData(e,t,s){if(!this.__allowOverridesForExistingNamespaces&&this._isNamespaceInCache(e,t))throw new Error(`Namespace "${t}" has been already added for the locale "${e}".`);this.__storage[e]=this.__storage[e]||{},this.__allowOverridesForExistingNamespaces?this.__storage[e][t]={...this.__storage[e][t],...s}:this.__storage[e][t]=s}setupNamespaceLoader(e,t){this.__namespacePatternsMap.set(e,t)}loadNamespaces(e,{locale:t}={}){return Promise.all(e.map(s=>this.loadNamespace(s,{locale:t})))}loadNamespace(e,{locale:t=this.locale}={locale:this.locale}){const s=typeof e=="object",n=s?Object.keys(e)[0]:e;if(this._isNamespaceInCache(t,n))return Promise.resolve();const o=this._getCachedNamespaceLoaderPromise(t,n);return o||this._loadNamespaceData(t,e,s,n)}msg(e,t,s={}){const n=s.locale?s.locale:this.locale,o=this._getMessageForKeys(e,n);return o?new dc(o,n).format(t):""}teardown(){this._teardownHtmlLangAttributeObserver()}reset(){this.__storage={},this.__namespacePatternsMap=new Map,this.__namespaceLoadersCache={},this.__namespaceLoaderPromisesCache={}}setDatePostProcessorForLocale({locale:e,postProcessor:t}){this.formatDateOptions?.postProcessors.set(e,t)}setNumberPostProcessorForLocale({locale:e,postProcessor:t}){this.formatNumberOptions?.postProcessors.set(e,t)}_setupTranslationToolSupport(){this.#i=We.lang||null}_setHtmlLangAttribute(e){this._teardownHtmlLangAttributeObserver(),We.lang=e,this._setupHtmlLangAttributeObserver()}_setupHtmlLangAttributeObserver(){this._htmlLangAttributeObserver||(this._htmlLangAttributeObserver=new MutationObserver(e=>{e.forEach(t=>{this.#e?We.lang==="auto"?(this.#i=null,this._setHtmlLangAttribute(this.locale)):this.#i=document.documentElement.lang:this._onLocaleChanged(document.documentElement.lang,t.oldValue||"")})})),this._htmlLangAttributeObserver.observe(document.documentElement,{attributes:!0,attributeFilter:["lang"],attributeOldValue:!0})}_teardownHtmlLangAttributeObserver(){this._htmlLangAttributeObserver&&this._htmlLangAttributeObserver.disconnect()}_isNamespaceInCache(e,t){return!!(this.__storage[e]&&this.__storage[e][t])}_getCachedNamespaceLoaderPromise(e,t){return this.__namespaceLoaderPromisesCache[e]?this.__namespaceLoaderPromisesCache[e][t]:null}_loadNamespaceData(e,t,s,n){const o=this._getNamespaceLoader(t,s,n),r=this._getNamespaceLoaderPromise(o,e,n);return this._cacheNamespaceLoaderPromise(e,n,r),r.then(a=>{if(this.__namespaceLoaderPromisesCache[e]&&this.__namespaceLoaderPromisesCache[e][n]===r){const c=uc(a)?a.default:a;this.addData(e,n,c)}})}_getNamespaceLoader(e,t,s){let n=this.__namespaceLoadersCache[s];if(n||(t?(n=e[s],this.__namespaceLoadersCache[s]=n):(n=this._lookupNamespaceLoader(s),this.__namespaceLoadersCache[s]=n)),!n)throw new Error(`Namespace "${s}" was not properly setup.`);return this.__namespaceLoadersCache[s]=n,n}_getNamespaceLoaderPromise(e,t,s,n=this._fallbackLocale){return e(t,s).catch(()=>{const o=this._getLangFromLocale(t);return e(o,s).catch(()=>{if(n)return this._getNamespaceLoaderPromise(e,n,s,"").catch(()=>{const r=this._getLangFromLocale(n);throw new Error(`Data for namespace "${s}" and current locale "${t}" or fallback locale "${n}" could not be loaded. Make sure you have data either for locale "${t}" (and/or generic language "${o}") or for fallback "${n}" (and/or "${r}").`)});throw new Error(`Data for namespace "${s}" and locale "${t}" could not be loaded. Make sure you have data for locale "${t}" (and/or generic language "${o}").`)})})}_cacheNamespaceLoaderPromise(e,t,s){this.__namespaceLoaderPromisesCache[e]||(this.__namespaceLoaderPromisesCache[e]={}),this.__namespaceLoaderPromisesCache[e][t]=s}_lookupNamespaceLoader(e){for(const[t,s]of this.__namespacePatternsMap){const n=typeof t=="string"&&t===e,o=typeof t=="object"&&t.constructor.name==="RegExp"&&t.test(e);if(n||o)return s}return null}_getLangFromLocale(e){return e.substring(0,2)}_onLocaleChanged(e,t){this.dispatchEvent(new CustomEvent("__localeChanging")),e!==t&&(this._autoLoadOnLocaleChange?(this._loadAllMissing(e,t),this.loadingComplete.then(()=>{this.dispatchEvent(new CustomEvent("localeChanged",{detail:{newLocale:e,oldLocale:t}}))})):this.dispatchEvent(new CustomEvent("localeChanged",{detail:{newLocale:e,oldLocale:t}})))}_loadAllMissing(e,t){const s=this.__storage[t]||{},n=this.__storage[e]||{};Object.keys(s).forEach(o=>{n[o]||this.loadNamespace(o,{locale:e})})}_getMessageForKeys(e,t){if(typeof e=="string")return this._getMessageForKey(e,t);const s=Array.from(e).reverse();let n,o;for(;s.length;)if(n=s.pop(),o=this._getMessageForKey(n,t),o)return o}_getMessageForKey(e,t){if(!e||e.indexOf(":")===-1)throw new Error(`Namespace is missing in the key "${e}". The format for keys is "namespace:name".`);const[s,n]=e.split(":"),o=this.__storage[t],r=o?o[s]:{},c=n.split(".").reduce((u,h)=>typeof u=="object"?u[h]:u,r);return String(c||(this._showKeyAsFallback?e:""))}#s(e){if(!e.includes("-"))throw new Error(`
      Locale was set to ${e}.
      Language only locales are not allowed, please use the full language locale e.g. 'en-GB' instead of 'en'.
      See https://github.com/ing-bank/lion/issues/187 for more information.
    `)}get _supportExternalTranslationTools(){return this.#e}set _supportExternalTranslationTools(e){this.#e=e}get _langAttrSetByTranslationTool(){return this.#t}set _langAttrSetByTranslationTool(e){this.#t=e}}const ts=Symbol.for("lion::SingletonManagerClassStorage"),is=globalThis||window;class pc{constructor(){this._map=is[ts]?is[ts]:is[ts]=new Map}set(e,t){this.has(e)||this._map.set(e,t)}get(e){return this._map.get(e)}has(e){return this._map.has(e)}}const fi=new pc;function ki(){if(fi.has("@lion/ui::localize::0.x"))return fi.get("@lion/ui::localize::0.x");const i=new hc({autoLoadOnLocaleChange:!0,fallbackLocale:"en-GB"});return fi.set("@lion/ui::localize::0.x",i),i}const Mt=(i,e)=>{const t=i._$AN;if(t===void 0)return!1;for(const s of t)s._$AO?.(e,!1),Mt(s,e);return!0},Ci=i=>{let e,t;do{if((e=i._$AM)===void 0)break;t=e._$AN,t.delete(i),i=e}while(t?.size===0)},Lo=i=>{for(let e;e=i._$AM;i=e){let t=e._$AN;if(t===void 0)e._$AN=t=new Set;else if(t.has(i))break;t.add(i),bc(e)}};function fc(i){this._$AN!==void 0?(Ci(this),this._$AM=i,Lo(this)):this._$AM=i}function mc(i,e=!1,t=0){const s=this._$AH,n=this._$AN;if(n!==void 0&&n.size!==0)if(e)if(Array.isArray(s))for(let o=t;o<s.length;o++)Mt(s[o],!1),Ci(s[o]);else s!=null&&(Mt(s,!1),Ci(s));else Mt(this,i)}const bc=i=>{i.type==Ni.CHILD&&(i._$AP??=mc,i._$AQ??=fc)};class gc extends Li{constructor(){super(...arguments),this._$AN=void 0}_$AT(e,t,s){super._$AT(e,t,s),Lo(this),this.isConnected=e._$AU}_$AO(e,t=!0){e!==this.isConnected&&(this.isConnected=e,e?this.reconnected?.():this.disconnected?.()),t&&(Mt(this,e),Ci(this))}setValue(e){if(Qa(this._$Ct))this._$Ct._$AI(e,this);else{const t=[...this._$Ct._$AH];t[this._$Ci]=e,this._$Ct._$AI(t,this,0)}}disconnected(){}reconnected(){}}let vc=class{constructor(e){this.G=e}disconnect(){this.G=void 0}reconnect(e){this.G=e}deref(){return this.G}},_c=class{constructor(){this.Y=void 0,this.Z=void 0}get(){return this.Y}pause(){this.Y??=new Promise((e=>this.Z=e))}resume(){this.Z?.(),this.Y=this.Z=void 0}};const xn=i=>!Xa(i)&&typeof i.then=="function",En=1073741823;let yc=class extends gc{constructor(){super(...arguments),this._$Cwt=En,this._$Cbt=[],this._$CK=new vc(this),this._$CX=new _c}render(...e){return e.find((t=>!xn(t)))??Rt}update(e,t){const s=this._$Cbt;let n=s.length;this._$Cbt=t;const o=this._$CK,r=this._$CX;this.isConnected||this.disconnected();for(let a=0;a<t.length&&!(a>this._$Cwt);a++){const c=t[a];if(!xn(c))return this._$Cwt=a,c;a<n&&c===s[a]||(this._$Cwt=En,n=0,Promise.resolve(c).then((async u=>{for(;r.get();)await r.get();const h=o.deref();if(h!==void 0){const g=h._$Cbt.indexOf(c);g>-1&&g<h._$Cwt&&(h._$Cwt=g,h.setValue(u))}})))}return Rt}disconnected(){this._$CK.disconnect(),this._$CX.pause()}reconnected(){this._$CK.reconnect(this),this._$CX.resume()}};const wc=Fi(yc),xc=i=>class extends i{static get localizeNamespaces(){return[]}static get waitForLocalizeNamespaces(){return!0}constructor(){super(),this._localizeManager=ki(),this.__boundLocalizeOnLocaleChanged=(...t)=>{const s=Array.from(t)[0];this.__localizeOnLocaleChanged(s)},this.__boundLocalizeOnLocaleChanging=()=>{this.__localizeOnLocaleChanging()},this.__localizeStartLoadingNamespaces(),this.localizeNamespacesLoaded&&this.localizeNamespacesLoaded.then(()=>{this.__localizeMessageSync=!0})}async scheduleUpdate(){Object.getPrototypeOf(this).constructor.waitForLocalizeNamespaces&&await this.localizeNamespacesLoaded,super.scheduleUpdate()}connectedCallback(){super.connectedCallback(),this.localizeNamespacesLoaded&&this.localizeNamespacesLoaded.then(()=>this.onLocaleReady()),this._localizeManager.addEventListener("__localeChanging",this.__boundLocalizeOnLocaleChanging),this._localizeManager.addEventListener("localeChanged",this.__boundLocalizeOnLocaleChanged)}disconnectedCallback(){super.disconnectedCallback(),this._localizeManager.removeEventListener("__localeChanging",this.__boundLocalizeOnLocaleChanging),this._localizeManager.removeEventListener("localeChanged",this.__boundLocalizeOnLocaleChanged)}msgLit(t,s,n){return this.__localizeMessageSync?this._localizeManager.msg(t,s,n):this.localizeNamespacesLoaded?wc(this.localizeNamespacesLoaded.then(()=>this._localizeManager.msg(t,s,n)),K):""}__getUniqueNamespaces(){const t=[],s=new Set;return Object.getPrototypeOf(this).constructor.localizeNamespaces.forEach(s.add.bind(s)),s.forEach(n=>{t.push(n)}),t}__localizeStartLoadingNamespaces(){this.localizeNamespacesLoaded=this._localizeManager.loadNamespaces(this.__getUniqueNamespaces())}__localizeOnLocaleChanging(){this.__localizeStartLoadingNamespaces()}__localizeOnLocaleChanged(t){this.onLocaleChanged(t.detail.newLocale,t.detail.oldLocale)}onLocaleReady(){this.onLocaleUpdated()}onLocaleChanged(t,s){this.onLocaleUpdated(),this.requestUpdate()}onLocaleUpdated(){}},zi=ee(xc),Ss="3.0.0",kn=window.scopedElementsVersions||(window.scopedElementsVersions=[]);kn.includes(Ss)||kn.push(Ss);const Ec=i=>class extends i{static scopedElements;static get scopedElementsVersion(){return Ss}static __registry;get registry(){return this.constructor.__registry}set registry(t){this.constructor.__registry=t}attachShadow(t){const{scopedElements:s}=this.constructor;if(!this.registry||this.registry===this.constructor.__registry&&!Object.prototype.hasOwnProperty.call(this.constructor,"__registry")){this.registry=new CustomElementRegistry;for(const[o,r]of Object.entries(s??{}))this.registry.define(o,r)}return super.attachShadow({...t,customElements:this.registry,registry:this.registry})}},kc=ee(Ec),Cc=i=>class extends kc(i){createRenderRoot(){const{shadowRootOptions:t,elementStyles:s}=this.constructor,n=this.attachShadow(t);return this.renderOptions.creationScope=n,Gn(n,s),this.renderOptions.renderBefore??=n.firstChild,n}},Sc=ee(Cc);function si(){return!!(globalThis.ShadowRoot?.prototype.createElement&&globalThis.ShadowRoot?.prototype.importNode)}const Ac=i=>class extends Sc(i){constructor(){super()}createScopedElement(t){return(si()?this.shadowRoot:document).createElement(t)}defineScopedElement(t,s){const n=this.registry.get(t),o=n&&n!==s;return!si()&&o&&console.error([`You are trying to re-register the "${t}" custom element with a different class via ScopedElementsMixin.`,"This is only possible with a CustomElementRegistry.","Your browser does not support this feature so you will need to load a polyfill for it.",'Load "@webcomponents/scoped-custom-element-registry" before you register ANY web component to the global customElements registry.','e.g. add "<script src="/node_modules/@webcomponents/scoped-custom-element-registry/scoped-custom-element-registry.min.js"><\/script>" as your first script tag.',"For more details you can visit https://open-wc.org/docs/development/scoped-elements/"].join(`
`)),n?this.registry.get(t):this.registry.define(t,s)}attachShadow(t){const{scopedElements:s}=this.constructor;if(!this.registry||this.registry===this.constructor.__registry&&!Object.prototype.hasOwnProperty.call(this.constructor,"__registry")){this.registry=si()?new CustomElementRegistry:customElements;for(const[o,r]of Object.entries(s??{}))this.defineScopedElement(o,r)}return Element.prototype.attachShadow.call(this,{...t,customElements:this.registry,registry:this.registry})}createRenderRoot(){const{shadowRootOptions:t,elementStyles:s}=this.constructor,n=this.attachShadow(t);return si()&&(this.renderOptions.creationScope=n),n instanceof ShadowRoot&&(Gn(n,s),this.renderOptions.renderBefore=this.renderOptions.renderBefore||n.firstChild),n}},Yt=ee(Ac);class Tc{constructor(){this.__running=!1,this.__queue=[]}add(e){this.__queue.push(e),this.__running||(this.complete=new Promise(t=>{this.__callComplete=t}),this.__run())}async __run(){this.__running=!0,await this.__queue[0](),this.__queue.shift(),this.__queue.length>0?this.__run():(this.__running=!1,this.__callComplete&&this.__callComplete())}}function Nc(i){return i.charAt(0).toUpperCase()+i.slice(1)}const Fc=i=>class extends i{constructor(){super(),this.__SyncUpdatableNamespace={}}firstUpdated(e){super.firstUpdated(e),this.__syncUpdatableInitialize()}connectedCallback(){super.connectedCallback(),this.__SyncUpdatableNamespace.connected=!0}disconnectedCallback(){super.disconnectedCallback(),this.__SyncUpdatableNamespace.connected=!1}static enabledWarnings=super.enabledWarnings?.filter(e=>e!=="change-in-update")||[];static __syncUpdatableHasChanged(e,t,s){const n=this.elementProperties;return n.get(e)&&n.get(e).hasChanged?n.get(e).hasChanged(t,s):t!==s}__syncUpdatableInitialize(){const e=this.__SyncUpdatableNamespace,t=this.constructor;e.initialized=!0,e.queue&&Array.from(e.queue).forEach(s=>{t.__syncUpdatableHasChanged(s,this[s],void 0)&&this.updateSync(s,void 0)})}requestUpdate(e,t,s){if(super.requestUpdate(e,t,s),e===void 0)return;this.__SyncUpdatableNamespace=this.__SyncUpdatableNamespace||{};const n=this.__SyncUpdatableNamespace,o=this.constructor;n.initialized?o.__syncUpdatableHasChanged(e,this[e],t)&&this.updateSync(e,t):(n.queue=n.queue||new Set,n.queue.add(e))}updateSync(e,t){}},Lc=ee(Fc),Oc=i=>{switch(i){case"bg-BG":return E(()=>import("./bg-BG2.js"),__vite__mapDeps([0,1]),import.meta.url);case"bg":return E(()=>import("./bg3.js"),[],import.meta.url);case"cs-CZ":return E(()=>import("./cs-CZ2.js"),__vite__mapDeps([2,3]),import.meta.url);case"cs":return E(()=>import("./cs3.js"),[],import.meta.url);case"de-DE":return E(()=>import("./de-DE2.js"),__vite__mapDeps([4,5]),import.meta.url);case"de":return E(()=>import("./de3.js"),[],import.meta.url);case"en-AU":return E(()=>import("./en-AU2.js"),__vite__mapDeps([6,7]),import.meta.url);case"en-GB":return E(()=>import("./en-GB2.js"),__vite__mapDeps([8,7]),import.meta.url);case"en-US":return E(()=>import("./en-US2.js"),__vite__mapDeps([9,7]),import.meta.url);case"en-PH":case"en":return E(()=>import("./en3.js"),[],import.meta.url);case"es-ES":return E(()=>import("./es-ES2.js"),__vite__mapDeps([10,11]),import.meta.url);case"es":return E(()=>import("./es3.js"),[],import.meta.url);case"fr-FR":return E(()=>import("./fr-FR2.js"),__vite__mapDeps([12,13]),import.meta.url);case"fr-BE":return E(()=>import("./fr-BE2.js"),__vite__mapDeps([14,13]),import.meta.url);case"fr":return E(()=>import("./fr3.js"),[],import.meta.url);case"hu-HU":return E(()=>import("./hu-HU2.js"),__vite__mapDeps([15,16]),import.meta.url);case"hu":return E(()=>import("./hu3.js"),[],import.meta.url);case"it-IT":return E(()=>import("./it-IT2.js"),__vite__mapDeps([17,18]),import.meta.url);case"it":return E(()=>import("./it3.js"),[],import.meta.url);case"nl-BE":return E(()=>import("./nl-BE2.js"),__vite__mapDeps([19,20]),import.meta.url);case"nl-NL":return E(()=>import("./nl-NL2.js"),__vite__mapDeps([21,20]),import.meta.url);case"nl":return E(()=>import("./nl3.js"),[],import.meta.url);case"pl-PL":return E(()=>import("./pl-PL2.js"),__vite__mapDeps([22,23]),import.meta.url);case"pl":return E(()=>import("./pl3.js"),[],import.meta.url);case"ro-RO":return E(()=>import("./ro-RO2.js"),__vite__mapDeps([24,25]),import.meta.url);case"ro":return E(()=>import("./ro3.js"),[],import.meta.url);case"ru-RU":return E(()=>import("./ru-RU2.js"),__vite__mapDeps([26,27]),import.meta.url);case"ru":return E(()=>import("./ru3.js"),[],import.meta.url);case"sk-SK":return E(()=>import("./sk-SK2.js"),__vite__mapDeps([28,29]),import.meta.url);case"sk":return E(()=>import("./sk3.js"),[],import.meta.url);case"tr-TR":return E(()=>import("./tr-TR.js"),__vite__mapDeps([30,31]),import.meta.url);case"tr":return E(()=>import("./tr.js"),[],import.meta.url);case"uk-UA":return E(()=>import("./uk-UA2.js"),__vite__mapDeps([32,33]),import.meta.url);case"uk":return E(()=>import("./uk3.js"),[],import.meta.url);case"zh-CN":case"zh":return E(()=>import("./zh3.js"),[],import.meta.url);default:return E(()=>import("./en3.js"),[],import.meta.url)}},Ic=i=>`${i[0].toUpperCase()}${i.slice(1)}`;class Oo extends zi(H){static get properties(){return{feedbackData:{attribute:!1}}}static localizeNamespaces=[{"lion-form-core":Oc},...super.localizeNamespaces];static get styles(){return[F`
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
      `]}constructor(){super(),this.feedbackData=void 0}_messageTemplate({message:e}){return e}updated(e){super.updated(e),this.feedbackData&&this.feedbackData[0]?(this.setAttribute("type",this.feedbackData[0].type),this.currentType=this.feedbackData[0].type):this.currentType!=="success"&&this.removeAttribute("type")}render(){return w`
      ${this.feedbackData&&this.feedbackData.map(({message:e,type:t,validator:s})=>w`
          <div class="validation-feedback__type">
            ${e&&t?this._localizeManager.msg(`lion-form-core:validation${Ic(t)}`):K}
          </div>
          ${this._messageTemplate({message:e,type:t,validator:s})}
        `)}
    `}}class ot{constructor(e){this.type="unparseable",this.viewValue=e}toString(){return JSON.stringify({type:this.type,viewValue:this.viewValue})}}const Dc=[Node.DOCUMENT_POSITION_PRECEDING,Node.DOCUMENT_POSITION_CONTAINS,Node.DOCUMENT_POSITION_CONTAINS|Node.DOCUMENT_POSITION_PRECEDING];function Io(i,{reverse:e}={}){const t=(n,o)=>{const r=n.compareDocumentPosition(o);return Dc.includes(r)?1:-1},s=i.filter(n=>n);return s.sort(t),e&&s.reverse(),s}const $c=i=>class extends i{constructor(){super(),this.name="",this._parentFormGroup=void 0,this.allowCrossRootRegistration=!1}get name(){return this.__name||""}set name(e){const t=this.name;this.__name=e.toString(),this.requestUpdate("name",t)}static get properties(){return{name:{type:String,reflect:!0},allowCrossRootRegistration:{type:Boolean,attribute:"allow-cross-root-registration"}}}connectedCallback(){super.connectedCallback(),this.dispatchEvent(new CustomEvent("form-element-register",{detail:{element:this},bubbles:!0,composed:!!this.allowCrossRootRegistration}))}disconnectedCallback(){super.disconnectedCallback(),this.__unregisterFormElement()}__unregisterFormElement(){this._parentFormGroup&&this._parentFormGroup.removeFormElement(this)}},Gs=ee($c),Mc=i=>class extends Gs(jt(xt(i))){static get properties(){return{readOnly:{type:Boolean,attribute:"readonly",reflect:!0},label:String,labelSrOnly:{type:Boolean,attribute:"label-sr-only",reflect:!0},helpText:{type:String,attribute:"help-text"},modelValue:{attribute:!1},_ariaLabelledNodes:{attribute:!1},_ariaDescribedNodes:{attribute:!1},_repropagationRole:{attribute:!1},_isRepropagationEndpoint:{attribute:!1}}}get label(){return this.__label??(this._labelNode?.textContent||"")}set label(t){const s=this.label;this.__label=t,this.requestUpdate("label",s)}get helpText(){return this.__helpText??(this._helpTextNode?.textContent||"")}set helpText(t){const s=this.helpText;this.__helpText=t,this.requestUpdate("helpText",s)}get fieldName(){return this.__fieldName||this.label||this.name||""}set fieldName(t){this.__fieldName=t}get slots(){return{...super.slots,label:()=>{const t=document.createElement("label");return t.textContent=this.label,t},"help-text":()=>{const t=document.createElement("div");return t.textContent=this.helpText,t}}}get _inputNode(){return this.__getDirectSlotChild("input")}get _labelNode(){return this.__getDirectSlotChild("label")}get _helpTextNode(){return this.__getDirectSlotChild("help-text")}get _feedbackNode(){return this.__getDirectSlotChild("feedback")}static enabledWarnings=super.enabledWarnings?.filter(t=>t!=="change-in-update")||[];constructor(){super(),this.readOnly=!1,this.labelSrOnly=!1,this._inputId=Kt(this.localName),this._ariaLabelledNodes=[],this._ariaDescribedNodes=[],this._repropagationRole="child",this._isRepropagationEndpoint=!1,this.addEventListener("model-value-changed",this.__repropagateChildrenValues),this._onLabelClick=this._onLabelClick.bind(this)}connectedCallback(){super.connectedCallback(),this._enhanceLightDomClasses(),this._enhanceLightDomA11y(),this._triggerInitialModelValueChangedEvent(),this._labelNode&&this._labelNode.addEventListener("click",this._onLabelClick)}disconnectedCallback(){super.disconnectedCallback(),this._labelNode&&this._labelNode.removeEventListener("click",this._onLabelClick)}updated(t){super.updated(t),t.has("disabled")&&this._inputNode?.setAttribute("aria-disabled",`${!!this.disabled}`),t.has("_ariaLabelledNodes")&&this.__reflectAriaAttr("aria-labelledby",this._ariaLabelledNodes,this.__reorderAriaLabelledNodes),t.has("_ariaDescribedNodes")&&this.__reflectAriaAttr("aria-describedby",this._ariaDescribedNodes,this.__reorderAriaDescribedNodes),t.has("label")&&this.__label!==void 0&&this._labelNode&&(this._labelNode.textContent=this.label),t.has("helpText")&&this.__helpText!==void 0&&this._helpTextNode&&(this._helpTextNode.textContent=this.helpText),t.has("name")&&this.dispatchEvent(new CustomEvent("form-element-name-changed",{detail:{oldName:t.get("name"),newName:this.name},bubbles:!0}))}_triggerInitialModelValueChangedEvent(){this._dispatchInitialModelValueChangedEvent()}_enhanceLightDomClasses(){this._inputNode&&this._inputNode.classList.add("form-control")}_enhanceLightDomA11y(){const{_inputNode:t,_labelNode:s,_helpTextNode:n,_feedbackNode:o}=this;t&&(t.id=t.id||this._inputId),s&&(s.setAttribute("for",this._inputId),this.addToAriaLabelledBy(s,{idPrefix:"label"})),n&&this.addToAriaDescribedBy(n,{idPrefix:"help-text"}),o&&(this.addEventListener("focusin",()=>{o.setAttribute("aria-live","polite")}),this.addEventListener("focusout",()=>{o.setAttribute("aria-live","assertive")}),this.addToAriaDescribedBy(o,{idPrefix:"feedback"})),this._enhanceLightDomA11yForAdditionalSlots()}_enhanceLightDomA11yForAdditionalSlots(t=["prefix","suffix","before","after"]){t.forEach(s=>{const n=this.__getDirectSlotChild(s);n&&(n.hasAttribute("data-label")&&this.addToAriaLabelledBy(n,{idPrefix:s}),n.hasAttribute("data-description")&&this.addToAriaDescribedBy(n,{idPrefix:s}))})}__reflectAriaAttr(t,s,n){if(this._inputNode){if(n){const r=s.filter(g=>this.contains(g)),a=s.filter(g=>!this.contains(g)),c=r.map(g=>g.assignedSlot||g),u=[...Io(c)],h=[];u.forEach(g=>{r.forEach(f=>{g.name===f.slot&&h.push(f)})}),s=[...h,...a]}const o=s.map(r=>r.id).join(" ");this._inputNode.setAttribute(t,o)}}render(){return w`
        <div class="form-field__group-one">${this._groupOneTemplate()}</div>
        <div class="form-field__group-two">${this._groupTwoTemplate()}</div>
      `}_groupOneTemplate(){return w` ${this._labelTemplate()} ${this._helpTextTemplate()} `}_groupTwoTemplate(){return w` ${this._inputGroupTemplate()} ${this._feedbackTemplate()} `}_labelTemplate(){return w`
        <div class="form-field__label">
          <slot name="label"></slot>
        </div>
      `}_helpTextTemplate(){return w`
        <small class="form-field__help-text">
          <slot name="help-text"></slot>
        </small>
      `}_inputGroupTemplate(){return w`
        <div class="input-group">
          ${this._inputGroupBeforeTemplate()}
          <div class="input-group__container">
            ${this._inputGroupPrefixTemplate()} ${this._inputGroupInputTemplate()}
            ${this._inputGroupSuffixTemplate()}
          </div>
          ${this._inputGroupAfterTemplate()}
        </div>
      `}_inputGroupBeforeTemplate(){return w`
        <div class="input-group__before">
          <slot name="before"></slot>
        </div>
      `}_inputGroupPrefixTemplate(){return Array.from(this.children).find(t=>t.slot==="prefix")?w`
            <div class="input-group__prefix">
              <slot name="prefix"></slot>
            </div>
          `:K}_inputGroupInputTemplate(){return w`
        <div class="input-group__input">
          <slot name="input"></slot>
        </div>
      `}_inputGroupSuffixTemplate(){return Array.from(this.children).find(t=>t.slot==="suffix")?w`
            <div class="input-group__suffix">
              <slot name="suffix"></slot>
            </div>
          `:K}_inputGroupAfterTemplate(){return w`
        <div class="input-group__after">
          <slot name="after"></slot>
        </div>
      `}_feedbackTemplate(){return w`
        <div class="form-field__feedback">
          <slot name="feedback"></slot>
        </div>
      `}_isEmpty(t=this.modelValue){let s=t;if(this.modelValue instanceof ot&&(s=this.modelValue.viewValue),typeof s=="object"&&s!==null&&!(s instanceof Date))return!Object.keys(s).length;const n=typeof s=="number"&&(s===0||Number.isNaN(s));return!s&&!n&&!(typeof s=="boolean"&&s===!1)}static get styles(){return[F`
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
        `]}_getAriaDescriptionElements(){return[this._helpTextNode,this._feedbackNode]}addToAriaLabelledBy(t,{idPrefix:s="",reorder:n=!0}={}){t.id=t.id||`${s}-${this._inputId}`,this._ariaLabelledNodes.includes(t)||(this._ariaLabelledNodes=[...this._ariaLabelledNodes,t],this.__reorderAriaLabelledNodes=!!n)}removeFromAriaLabelledBy(t){this._ariaLabelledNodes.includes(t)&&(this._ariaLabelledNodes.splice(this._ariaLabelledNodes.indexOf(t),1),this._ariaLabelledNodes=[...this._ariaLabelledNodes],this.__reorderAriaLabelledNodes=!1)}addToAriaDescribedBy(t,{idPrefix:s="",reorder:n=!0}={}){t.id=t.id||`${s}-${this._inputId}`,this._ariaDescribedNodes.includes(t)||(this._ariaDescribedNodes=[...this._ariaDescribedNodes,t],this.__reorderAriaDescribedNodes=!!n)}removeFromAriaDescribedBy(t){this._ariaDescribedNodes.includes(t)&&(this._ariaDescribedNodes.splice(this._ariaDescribedNodes.indexOf(t),1),this._ariaDescribedNodes=[...this._ariaDescribedNodes],this.__reorderAriaLabelledNodes=!1)}__getDirectSlotChild(t){return Array.from(this.children).find(s=>s.slot===t)}_dispatchInitialModelValueChangedEvent(){this._repropagationRole!=="child"&&(this.__repropagateChildrenInitialized=!0,this.dispatchEvent(new CustomEvent("model-value-changed",{bubbles:!0,detail:{formPath:[this],initialize:!0,isTriggeredByUser:!1}})))}_onBeforeRepropagateChildrenValues(t){}__repropagateChildrenValues(t){this._onBeforeRepropagateChildrenValues(t);const s=t.detail&&t.detail.element||t.target,n=this._isRepropagationEndpoint||this._repropagationRole==="choice-group";if(s===this)return;t.stopImmediatePropagation();const r=this._repropagationRole!=="child"&&!this.__repropagateChildrenInitialized,a=t.detail&&t.detail.initialize;if(r||a||!this._repropagationCondition(s))return;let c=[];n||(c=t.detail&&t.detail.formPath||[s]);const u=[...c,this];this.dispatchEvent(new CustomEvent("model-value-changed",{bubbles:!0,detail:{formPath:u,isTriggeredByUser:!!t.detail?.isTriggeredByUser}}))}_repropagationCondition(t){return!!t}_onLabelClick(){}},rt=ee(Mc);class Pi extends EventTarget{constructor(e,t){super(),this.__param=e,this.__config=t||{},this.type=t?.type||"error"}static _$isValidator$=!0;static validatorName="";static async=!1;execute(e,t,s){if(!this.constructor.validatorName)throw new Error(`A validator needs to have a name! Please set it via "static get validatorName() { return 'IsCat'; }"`);return!0}set param(e){this.__param=e,this.dispatchEvent(new Event("param-changed"))}get param(){return this.__param}set config(e){this.__config=e,this.dispatchEvent(new Event("config-changed"))}get config(){return this.__config}async _getMessage(e){const t=this.constructor,s={name:t.validatorName,type:this.type,params:this.param,config:this.config,...e};if(this.config.getMessage){if(typeof this.config.getMessage=="function")return this.config.getMessage(s);throw new Error(`You must provide a value for getMessage of type 'function', you provided a value of type: ${typeof this.config.getMessage}`)}return t.getMessage(s)}static async getMessage(e){return`Please configure an error message for "${this.name}" by overriding "static async getMessage()"`}onFormControlConnect(e){}onFormControlDisconnect(e){}abortExecution(){}}function Cn(i=[],e=[]){return i.filter(t=>!e.includes(t)).concat(e.filter(t=>!i.includes(t)))}function Vc(i){return i instanceof ot?i.viewValue:i}const Rc=i=>class extends rt(Lc(jt(xt(Yt(i))))){static get scopedElements(){return{...super.scopedElements,"lion-validation-feedback":Oo}}static get properties(){return{validators:{attribute:!1},hasFeedbackFor:{attribute:!1},shouldShowFeedbackFor:{attribute:!1},showsFeedbackFor:{type:Array,attribute:"shows-feedback-for",reflect:!0,converter:{fromAttribute:e=>e.split(","),toAttribute:e=>e.join(",")}},validationStates:{attribute:!1},isPending:{type:Boolean,attribute:"is-pending",reflect:!0},defaultValidators:{attribute:!1},_visibleMessagesAmount:{attribute:!1},__childModelValueChanged:{attribute:!1}}}static get validationTypes(){return["error"]}get operationMode(){return"enter"}get slots(){return{...super.slots,feedback:()=>{const e=this.createScopedElement("lion-validation-feedback");return e.setAttribute("data-tag-name","lion-validation-feedback"),e}}}get _allValidators(){return[...this.validators,...this.defaultValidators]}constructor(){super(),this.hasFeedbackFor=[],this.showsFeedbackFor=[],this.shouldShowFeedbackFor=[],this.validationStates={},this.isPending=!1,this.validators=[],this.defaultValidators=[],this._visibleMessagesAmount=1,this.__syncValidationResult=[],this.__asyncValidationResult=[],this.__validationResult=[],this.__prevValidationResult=[],this.__prevShownValidationResult=[],this.__childModelValueChanged=!1,this._onValidatorUpdated=this._onValidatorUpdated.bind(this),this._updateFeedbackComponent=this._updateFeedbackComponent.bind(this)}connectedCallback(){super.connectedCallback(),ki().addEventListener("localeChanged",this._updateFeedbackComponent)}disconnectedCallback(){super.disconnectedCallback(),ki().removeEventListener("localeChanged",this._updateFeedbackComponent)}firstUpdated(e){super.firstUpdated(e),this.__isValidateInitialized=!0,this.validate(),this._repropagationRole!=="child"&&this.addEventListener("model-value-changed",()=>{this.__childModelValueChanged=!0})}updateSync(e,t){if(super.updateSync(e,t),e==="validators"?(this.__setupValidators(),this.validate({clearCurrentResult:!0})):e==="modelValue"&&this.validate({clearCurrentResult:!0}),["touched","dirty","prefilled","focused","submitted","hasFeedbackFor","filled"].includes(e)&&this._updateShouldShowFeedbackFor(),e==="showsFeedbackFor"){this._inputNode&&this._inputNode.setAttribute("aria-invalid",`${this._hasFeedbackVisibleFor("error")}`);const s=Cn(this.showsFeedbackFor,t);s.length>0&&this.dispatchEvent(new Event("showsFeedbackForChanged",{bubbles:!0})),s.forEach(n=>{this.dispatchEvent(new Event(`showsFeedbackFor${Nc(n)}Changed`,{bubbles:!0}))})}e==="shouldShowFeedbackFor"&&Cn(this.shouldShowFeedbackFor,t).length>0&&this.dispatchEvent(new Event("shouldShowFeedbackForChanged",{bubbles:!0}))}async validate({clearCurrentResult:e=!1}={}){if(this.validateComplete=new Promise(t=>{this.__validateCompleteResolve=t}),this.disabled){this.__clearValidationResults(),this.__finishValidationPass(),this._updateFeedbackComponent();return}this.__isValidateInitialized&&(this.__prevValidationResult=this.__validationResult,e&&this.__clearValidationResults(),await this.__executeValidators())}#e(e){let t=e;for(;t;){if(t.constructor.validatorName==="Required")return!0;t=Object.getPrototypeOf(t)}return!1}async __executeValidators(){const e=Vc(this.modelValue),t=this.__isEmpty(e);if(this.__syncValidationResult=[],t){const a=!this._isFormOrFieldset,c=this._allValidators.find(u=>u.constructor?.validatorName==="Required");if(c&&(this.__syncValidationResult=[{validator:c,outcome:!0}]),a){this.__finishValidationPass({syncValidationResult:this.__syncValidationResult});return}}const s=[],n=[],o=[];for(const a of this._allValidators)a?.executeOnResults?s.push(a):this.#e(a)||(a.constructor.async?o.push(a):n.push(a));const r=!!o.length;this.__syncValidationResult=[...this.__syncValidationResult,...this.__executeSyncValidators(n,e)],this.__finishValidationPass({syncValidationResult:this.__syncValidationResult,metaValidators:s}),r?(this.isPending=!0,this.__asyncValidationResult=await this.__executeAsyncValidators(o,e),this.isPending=!1,this.__finishValidationPass({syncValidationResult:this.__syncValidationResult,asyncValidationResult:this.__asyncValidationResult,metaValidators:s}),this.__validateCompleteResolve?.(!0)):this.__validateCompleteResolve?.(!0)}__executeSyncValidators(e,t){return e.map(s=>({validator:s,outcome:s.execute(t,s.param,{node:this})})).filter(s=>!!s.outcome)}async __executeAsyncValidators(e,t){const s=e.map(o=>o.execute(t,o.param,{node:this})),n=await Promise.all(s);return n.map((o,r)=>({validator:e[r],outcome:n[r]})).filter(o=>!!o.outcome)}__executeMetaValidators(e,t){return t.length?this._isEmpty(this.modelValue)?(this.__prevShownValidationResult=[],[]):t.map(s=>({validator:s,outcome:s.executeOnResults({regularValidationResult:e.map(n=>n.validator),prevValidationResult:this.__prevValidationResult.map(n=>n.validator),prevShownValidationResult:this.__prevShownValidationResult.map(n=>n.validator)})})).filter(s=>!!s.outcome):[]}__finishValidationPass({syncValidationResult:e=[],asyncValidationResult:t=[],metaValidators:s=[]}={}){const n=[...e,...t],o=this.__executeMetaValidators(n,s);this.__validationResult=[...o,...n];const a=this.constructor.validationTypes.reduce((c,u)=>({...c,[u]:{}}),{});for(const{validator:c,outcome:u}of this.__validationResult){a[c.type]||(a[c.type]={});const h=c.constructor;a[c.type][h.validatorName]=u}this.validationStates=a,this.hasFeedbackFor=[...new Set(this.__validationResult.map(({validator:c})=>c.type))],this.dispatchEvent(new Event("validate-performed",{bubbles:!0}))}__clearValidationResults(){this.__syncValidationResult=[],this.__asyncValidationResult=[]}_onValidatorUpdated(e){(e.type==="param-changed"||e.type==="config-changed")&&this.validate()}__setupValidators(){const e=["param-changed","config-changed"];for(const t of this.__prevValidators||[]){for(const s of e)t.removeEventListener?.(s,this._onValidatorUpdated);t.onFormControlDisconnect(this)}for(const t of this._allValidators){if(t.constructor._$isValidator$===void 0){const a=`Validators array only accepts class instances of Validator. Type "${Array.isArray(t)?"array":typeof t}" found. This may be caused by having multiple installations of "@lion/ui/form-core.js".`;throw console.error(a,this),new Error(a)}const n=this.constructor,o=t.constructor;if(n.validationTypes.indexOf(t.type)===-1){const r=`This component does not support the validator type "${t.type}" used in "${o.validatorName}". You may change your validators type or add it to the components "static get validationTypes() {}".`;throw console.error(r,this),new Error(r)}for(const r of e)t.addEventListener?.(r,a=>{this._onValidatorUpdated(a,{validator:t})});t.onFormControlConnect(this)}this.__prevValidators=this._allValidators}__isEmpty(e){return typeof this._isEmpty=="function"?this._isEmpty(e):this.modelValue===null||typeof this.modelValue>"u"||this.modelValue===""}async __getFeedbackMessages(e){let t=await this.fieldName;return Promise.all(e.map(async({validator:s,outcome:n})=>(s.config.fieldName&&(t=await s.config.fieldName),{message:await s._getMessage({modelValue:this.modelValue,formControl:this,fieldName:t,outcome:n}),type:s.type,validator:s,visibilityDuration:s.config?.visibilityDuration||3e3})))}_updateFeedbackComponent(){window.clearTimeout(this.removeMessage);const{_feedbackNode:e}=this;e&&(this.__feedbackQueue||(this.__feedbackQueue=new Tc),this.showsFeedbackFor.length>0?this.__feedbackQueue.add(async()=>{const t=this._prioritizeAndFilterFeedback({validationResult:this.__validationResult.map(n=>n.validator)});this.__prioritizedResult=t.map(n=>this.__validationResult.find(r=>n===r.validator)).filter(Boolean),this.__prioritizedResult.length>0&&(this.__prevShownValidationResult=this.__prioritizedResult);const s=await this.__getFeedbackMessages(this.__prioritizedResult);e.feedbackData=s||[],s?.[0]&&s[0].type==="success"&&s[0].visibilityDuration!==1/0&&(this.removeMessage=window.setTimeout(()=>{e.removeAttribute("type"),e.feedbackData=[]},s[0].visibilityDuration))}):this.__feedbackQueue.add(async()=>{e.feedbackData=[]}),this.feedbackComplete=this.__feedbackQueue.complete)}_showFeedbackConditionFor(e,t){return!0}get _feedbackConditionMeta(){return{modelValue:this.modelValue,el:this}}feedbackCondition(e,t=this._feedbackConditionMeta,s=this._showFeedbackConditionFor.bind(this)){return s(e,t)}_hasFeedbackVisibleFor(e){return this.hasFeedbackFor?.includes(e)&&this.shouldShowFeedbackFor?.includes(e)}updated(e){if(super.updated(e),e.has("shouldShowFeedbackFor")||e.has("hasFeedbackFor")){const t=this.constructor;this.showsFeedbackFor=t.validationTypes.map(s=>this._hasFeedbackVisibleFor(s)?s:void 0).filter(Boolean),this._updateFeedbackComponent()}if(e.has("__childModelValueChanged")&&this.__childModelValueChanged&&(this.validate({clearCurrentResult:!0}),this.__childModelValueChanged=!1),e.has("validationStates")){const t=e.get("validationStates");t&&Object.entries(this.validationStates).forEach(([s,n])=>{t[s]&&JSON.stringify(n)!==JSON.stringify(t[s])&&this.dispatchEvent(new CustomEvent(`${s}StateChanged`,{detail:n}))})}}_updateShouldShowFeedbackFor(){const t=this.constructor.validationTypes.map(s=>this.feedbackCondition(s,this._feedbackConditionMeta,this._showFeedbackConditionFor.bind(this))?s:void 0).filter(Boolean);JSON.stringify(this.shouldShowFeedbackFor)!==JSON.stringify(t)&&(this.shouldShowFeedbackFor=t)}_prioritizeAndFilterFeedback({validationResult:e}){const s=this.constructor.validationTypes;return e.filter(o=>this.feedbackCondition(o.type,this._feedbackConditionMeta,this._showFeedbackConditionFor.bind(this))).sort((o,r)=>s.indexOf(o.type)-s.indexOf(r.type)).slice(0,this._visibleMessagesAmount)}},Zt=ee(Rc),zc=i=>class extends Zt(rt(i)){static get properties(){return{formattedValue:{attribute:!1},serializedValue:{attribute:!1},formatOptions:{attribute:!1}}}#e={didFormatterOutputSyncToView:!1,didFormatterRun:!1};requestUpdate(t,s,n){super.requestUpdate(t,s,n),t==="modelValue"&&this.modelValue!==s&&this._onModelValueChanged({modelValue:this.modelValue},{modelValue:s}),t==="serializedValue"&&this.serializedValue!==s&&this._calculateValues({source:"serialized"}),t==="formattedValue"&&this.formattedValue!==s&&this._calculateValues({source:"formatted"})}get value(){return this._inputNode?.value||this.__value||""}set value(t){this._inputNode?(this._inputNode.value=t,this.__value=void 0):this.__value=t}preprocessor(t,s){}parser(t,s){return t}formatter(t,s){return t}serializer(t){return t!==void 0?t:""}deserializer(t){return t===void 0?"":t}_calculateValues({source:t}={source:null}){this.__preventRecursiveTrigger||(this.__preventRecursiveTrigger=!0,t!=="model"&&(t==="serialized"?this.modelValue=this.deserializer(this.serializedValue):t==="formatted"&&(this.modelValue=this._callParser())),t!=="formatted"&&(this.formattedValue=this._callFormatter()),t!=="serialized"&&(this.serializedValue=this.serializer(this.modelValue)),this._reflectBackFormattedValueToUser(),this.__preventRecursiveTrigger=!1,this.__prevViewValue=this.value)}_callParser(t=this.formattedValue){if(t==="")return"";if(typeof t!="string")return;const s=this.parser(t,{...this.formatOptions,mode:this.#t(),viewValueStates:this.#i()});return s!==void 0?s:new ot(t)}_callFormatter(){return this.#e.didFormatterRun=!1,this._isHandlingUserInput&&this.hasFeedbackFor?.includes("error")&&this._inputNode?this.value:this.modelValue instanceof ot?this.modelValue.viewValue:(this.#e.didFormatterRun=!0,this.formatter(this.modelValue,{...this.formatOptions,mode:this.#t(),viewValueStates:this.#i()}))}_onModelValueChanged(...t){this._calculateValues({source:"model"}),this._dispatchModelValueChangedEvent(...t)}_dispatchModelValueChangedEvent(...t){this.dispatchEvent(new CustomEvent("model-value-changed",{bubbles:!0,detail:{formPath:[this],isTriggeredByUser:!!this._isHandlingUserInput}}))}_syncValueUpwards(){this.__isHandlingComposition||this.__handlePreprocessor();const t=this.formattedValue;this.modelValue=this._callParser(this.value),t===this.formattedValue&&this.__prevViewValue!==this.value&&this._calculateValues()}__handlePreprocessor(){let t=this.value.length;this._inputNode&&"selectionStart"in this._inputNode&&this._inputNode?.type!=="range"&&(t=this._inputNode.selectionStart);const s=this.preprocessor(this.value,{...this.formatOptions,currentCaretIndex:t,prevViewValue:this.__prevViewValue});if(s!==void 0){if(typeof s=="string")this.value=s;else if(typeof s=="object"){const{viewValue:n,caretIndex:o}=s;this.value=n,o&&this._inputNode&&"selectionStart"in this._inputNode&&(this._inputNode.selectionStart=o,this._inputNode.selectionEnd=o)}}}_reflectBackFormattedValueToUser(){this._reflectBackOn()&&(this.value=typeof this.formattedValue<"u"?this.formattedValue:"",this.#e.didFormatterOutputSyncToView=!!this.formattedValue&&this.#e.didFormatterRun)}_reflectBackOn(){return!this._isHandlingUserInput}_proxyInputEvent(){this.dispatchEvent(new Event("user-input-changed",{bubbles:!0}))}_onUserInputChanged(){this._isHandlingUserInput=!0,this._syncValueUpwards(),this._isHandlingUserInput=!1}__onCompositionEvent({type:t}){t==="compositionstart"?this.__isHandlingComposition=!0:t==="compositionend"&&(this.__isHandlingComposition=!1,this._syncValueUpwards())}constructor(){super(),this.formatOn="change",this.formatOptions={mode:"auto"},this.formattedValue=void 0,this.serializedValue=void 0,this._isPasting=!1,this._isHandlingUserInput=!1,this.__prevViewValue="",this.__onCompositionEvent=this.__onCompositionEvent.bind(this),this.addEventListener("user-input-changed",this._onUserInputChanged),this.addEventListener("paste",this.__onPaste),this._reflectBackFormattedValueToUser=this._reflectBackFormattedValueToUser.bind(this),this._reflectBackFormattedValueDebounced=()=>{setTimeout(this._reflectBackFormattedValueToUser)}}__onPaste(){this._isPasting=!0,setTimeout(()=>{this._isPasting=!1})}connectedCallback(){super.connectedCallback(),typeof this.modelValue>"u"&&this._syncValueUpwards(),this.__prevViewValue=this.value,this._reflectBackFormattedValueToUser(),this._inputNode&&(this._inputNode.addEventListener(this.formatOn,this._reflectBackFormattedValueDebounced),this._inputNode.addEventListener("input",this._proxyInputEvent),this._inputNode.addEventListener("compositionstart",this.__onCompositionEvent),this._inputNode.addEventListener("compositionend",this.__onCompositionEvent))}disconnectedCallback(){super.disconnectedCallback(),this._inputNode&&(this._inputNode.removeEventListener("input",this._proxyInputEvent),this._inputNode.removeEventListener(this.formatOn,this._reflectBackFormattedValueDebounced),this._inputNode.removeEventListener("compositionstart",this.__onCompositionEvent),this._inputNode.removeEventListener("compositionend",this.__onCompositionEvent))}#t(){return this._isPasting?"pasted":this._isHandlingUserInput&&this.__prevViewValue?"user-edited":"auto"}#i(){const t=[];return this.#e.didFormatterOutputSyncToView&&t.push("formatted"),t}},Ys=ee(zc),Pc=i=>class extends rt(i){static get properties(){return{touched:{type:Boolean,reflect:!0},dirty:{type:Boolean,reflect:!0},filled:{type:Boolean,reflect:!0},prefilled:{attribute:!1},submitted:{attribute:!1}}}requestUpdate(t,s,n){super.requestUpdate(t,s,n),t==="touched"&&this.touched!==s&&this._onTouchedChanged(),t==="modelValue"&&(this.filled=!this._isEmpty()),t==="dirty"&&this.dirty!==s&&this._onDirtyChanged()}constructor(){super(),this.touched=!1,this.dirty=!1,this.prefilled=!1,this.filled=!1,this._leaveEvent="blur",this._valueChangedEvent="model-value-changed",this._iStateOnLeave=this._iStateOnLeave.bind(this),this._iStateOnValueChange=this._iStateOnValueChange.bind(this)}connectedCallback(){super.connectedCallback(),this.addEventListener(this._leaveEvent,this._iStateOnLeave),this.addEventListener(this._valueChangedEvent,this._iStateOnValueChange),this.initInteractionState()}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener(this._leaveEvent,this._iStateOnLeave),this.removeEventListener(this._valueChangedEvent,this._iStateOnValueChange)}initInteractionState(){this.dirty=!1,this.prefilled=!this._isEmpty()}_iStateOnLeave(){this.touched=!0,this.prefilled=!this._isEmpty()}_iStateOnValueChange(){this.dirty=!0}resetInteractionState(){this.touched=!1,this.submitted=!1,this.dirty=!1,this.prefilled=!this._isEmpty()}_onTouchedChanged(){this.dispatchEvent(new Event("touched-changed",{bubbles:!0,composed:!0}))}_onDirtyChanged(){this.dispatchEvent(new Event("dirty-changed",{bubbles:!0,composed:!0}))}_showFeedbackConditionFor(t,s){return s.touched&&s.dirty||s.prefilled||s.submitted}get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,submitted:this.submitted,touched:this.touched,dirty:this.dirty,filled:this.filled,prefilled:this.prefilled}}},Zs=ee(Pc);class Xt extends rt(Zs(Ks(Ys(Zt(xt(H)))))){firstUpdated(e){super.firstUpdated(e),this._initialModelValue=this.modelValue}connectedCallback(){super.connectedCallback(),this._onChange=this._onChange.bind(this),this._inputNode.addEventListener("change",this._onChange),this.classList.add("form-field")}disconnectedCallback(){super.disconnectedCallback(),this._inputNode?.removeEventListener("change",this._onChange)}resetInteractionState(){super.resetInteractionState(),this.submitted=!1}reset(){this.modelValue=this._initialModelValue,this.resetInteractionState()}clear(){this.modelValue=""}_onChange(e){this.dispatchEvent(new Event("user-input-changed",{bubbles:!0}))}get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,focused:this.focused}}get _focusableNode(){return this._inputNode}}class As extends Array{_keys(){return Object.keys(this).filter(e=>Number.isNaN(Number(e)))}}const Bc=i=>class extends Gs(i){static get properties(){return{_isFormOrFieldset:{type:Boolean}}}constructor(){super(),this.formElements=new As,this._isFormOrFieldset=!1,this._onRequestToAddFormElement=this._onRequestToAddFormElement.bind(this),this._onRequestToChangeFormElementName=this._onRequestToChangeFormElementName.bind(this),this.addEventListener("form-element-register",this._onRequestToAddFormElement),this.addEventListener("form-element-name-changed",this._onRequestToChangeFormElementName),this.initComplete=new Promise((e,t)=>{this.__resolveInitComplete=e,this.__rejectInitComplete=t}),this.registrationComplete=new Promise((e,t)=>{this.__resolveRegistrationComplete=e,this.__rejectRegistrationComplete=t}),this.registrationComplete.done=!1,this.registrationComplete.then(()=>{this.registrationComplete.done=!0,this.__resolveInitComplete(void 0)},()=>{throw this.registrationComplete.done=!0,this.__rejectInitComplete(void 0),new Error("Registration could not finish. Please use await el.registrationComplete;")})}connectedCallback(){super.connectedCallback(),this._completeRegistration()}_completeRegistration(){Promise.resolve().then(()=>this.__resolveRegistrationComplete(void 0))}disconnectedCallback(){super.disconnectedCallback(),this.registrationComplete.done===!1&&Promise.resolve().then(()=>{Promise.resolve().then(()=>{this.__rejectRegistrationComplete()})})}isRegisteredFormElement(e){return this.formElements.some(t=>t===e)}addFormElement(e,t){if(e._parentFormGroup=this,t>=0?this.formElements.splice(t,0,e):this.formElements.push(e),this._isFormOrFieldset){const{name:s}=e;if(s===this.name)throw console.info("Error Node:",e),new TypeError(`You can not have the same name "${s}" as your parent`);if(s.substr(-2)==="[]")Array.isArray(this.formElements[s])||(this.formElements[s]=new As),t>0?this.formElements[s].splice(t,0,e):this.formElements[s].push(e);else if(!this.formElements[s])this.formElements[s]=e;else throw console.info("Error Node:",e),new TypeError(`Name "${s}" is already registered - if you want an array add [] to the end`)}}removeFormElement(e){const t=this.formElements.indexOf(e);if(t>-1&&this.formElements.splice(t,1),this._isFormOrFieldset){const{name:s}=e;if(s.substr(-2)==="[]"&&this.formElements[s]){const n=this.formElements[s].indexOf(e);n>-1&&this.formElements[s].splice(n,1)}else this.formElements[s]&&delete this.formElements[s]}}_onRequestToAddFormElement(e){const t=e.detail.element;if(t===this||this.isRegisteredFormElement(t))return;e.stopPropagation();let s=-1;if(this.formElements&&Array.isArray(this.formElements)){for(const[n,o]of this.formElements.entries())if(!(o.compareDocumentPosition(t)&Node.DOCUMENT_POSITION_FOLLOWING)){s=n;break}}this.addFormElement(t,s)}_onRequestToChangeFormElementName(e){const t=this.formElements[e.detail.oldName];t&&(this.formElements[e.detail.newName]=t,delete this.formElements[e.detail.oldName])}_onRequestToRemoveFormElement(e){const t=e.detail.element;t!==this&&this.isRegisteredFormElement(t)&&(e.stopPropagation(),this.removeFormElement(t))}},Xs=ee(Bc),Uc=i=>class extends i{constructor(){super(),this.registrationTarget=void 0,this.__redispatchEventForFormRegistrarPortalMixin=this.__redispatchEventForFormRegistrarPortalMixin.bind(this),this.addEventListener("form-element-register",this.__redispatchEventForFormRegistrarPortalMixin)}__redispatchEventForFormRegistrarPortalMixin(e){if(e.stopPropagation(),!this.registrationTarget)throw new Error("A FormRegistrarPortal element requires a .registrationTarget");this.registrationTarget.dispatchEvent(new CustomEvent("form-element-register",{detail:{element:e.detail.element},bubbles:!0}))}},Hc=ee(Uc),qc=i=>class extends Ys(Ks(rt(i))){static get properties(){return{autocomplete:{type:String,reflect:!0}}}constructor(){super(),this.autocomplete=void 0}get _inputNode(){return super._inputNode}get selectionStart(){const t=this._inputNode;return t&&t.selectionStart?t.selectionStart:0}set selectionStart(t){const s=this._inputNode;s&&s.selectionStart&&(s.selectionStart=t)}get selectionEnd(){const t=this._inputNode;return t&&t.selectionEnd?t.selectionEnd:0}set selectionEnd(t){const s=this._inputNode;s&&s.selectionEnd&&(s.selectionEnd=t)}get value(){return this._inputNode&&this._inputNode.value||this.__value||""}set value(t){this._inputNode?(this._inputNode.value!==t&&this._setValueAndPreserveCaret(t),this.__value=void 0):this.__value=t}_setValueAndPreserveCaret(t){if(this.focused)try{if(!(this._inputNode instanceof HTMLSelectElement)){const s=this._inputNode.selectionStart;this._inputNode.value=t,this._inputNode.selectionStart=s,this._inputNode.selectionEnd=s}}catch{this._inputNode.value=t}else this._inputNode.value=t}_reflectBackFormattedValueToUser(){if(super._reflectBackFormattedValueToUser(),this._reflectBackOn()&&this.focused)try{this._inputNode.selectionStart=this._inputNode.value.length}catch{}}get _focusableNode(){return this._inputNode}},Do=ee(qc),Wc=i=>class extends Xs(Zt(Zs(i))){static get properties(){return{multipleChoice:{type:Boolean,attribute:"multiple-choice"}}}get modelValue(){const t=this._getCheckedElements();return this.multipleChoice?t.map(s=>s.choiceValue):t[0]?t[0].choiceValue:""}set modelValue(t){const s=(n,o)=>typeof n.choiceValue=="object"?JSON.stringify(n.choiceValue)===JSON.stringify(t):n.choiceValue===o;this.__isInitialModelValue?this.registrationComplete.then(()=>{this.__isInitialModelValue=!1,this._setCheckedElements(t,s),this.requestUpdate("modelValue",this._oldModelValue)}):(this._setCheckedElements(t,s),this.requestUpdate("modelValue",this._oldModelValue)),this._oldModelValue=this.modelValue}get serializedValue(){const t=this._getCheckedElements();return this.multipleChoice?t.map(s=>s.serializedValue.value):t[0]?t[0].serializedValue.value:""}set serializedValue(t){const s=(n,o)=>n.serializedValue.value===o;this.__isInitialSerializedValue?this.registrationComplete.then(()=>{this.__isInitialSerializedValue=!1,this._setCheckedElements(t,s),this.requestUpdate("serializedValue")}):(this._setCheckedElements(t,s),this.requestUpdate("serializedValue"))}get formattedValue(){const t=this._getCheckedElements();return this.multipleChoice?t.map(s=>s.formattedValue):t[0]?t[0].formattedValue:""}set formattedValue(t){const s=(n,o)=>n.formattedValue===o;this.__isInitialFormattedValue?this.registrationComplete.then(()=>{this.__isInitialFormattedValue=!1,this._setCheckedElements(t,s)}):this._setCheckedElements(t,s)}get operationMode(){return this._repropagationRole==="choice-group"?"select":"enter"}constructor(){super(),this.multipleChoice=!1,this._repropagationRole="choice-group",this.__isInitialModelValue=!0,this.__isInitialSerializedValue=!0,this.__isInitialFormattedValue=!0}connectedCallback(){super.connectedCallback(),this.registrationComplete.then(()=>{this.__isInitialModelValue=!1,this.__isInitialSerializedValue=!1,this.__isInitialFormattedValue=!1})}_completeRegistration(){Promise.resolve().then(()=>super._completeRegistration())}updated(t){super.updated(t),t.has("name")&&this.name!==t.get("name")&&this.formElements.forEach(s=>{s.name=this.name})}addFormElement(t,s){this._throwWhenInvalidChildModelValue(t),t.name=this.name,super.addFormElement(t,s)}clear(){this.multipleChoice?this.modelValue=[]:this.modelValue=""}_triggerInitialModelValueChangedEvent(){this.registrationComplete.then(()=>{this._dispatchInitialModelValueChangedEvent()})}_getFromAllFormElementsFilter(t,s){return!0}_getFromAllFormElements(t,s){const n=s||this._getFromAllFormElementsFilter;return t==="modelValue"||t==="serializedValue"||t==="formattedValue"?this[t]:this.formElements.filter(o=>n(o,t)).map(o=>o.property)}_throwWhenInvalidChildModelValue(t){if(typeof t.modelValue.checked!="boolean"||!Object.prototype.hasOwnProperty.call(t.modelValue,"value"))throw new Error(`The ${this.tagName.toLowerCase()} name="${this.name}" does not allow to register ${t.tagName.toLowerCase()} with .modelValue="${t.modelValue}" - The modelValue should represent an Object { value: "foo", checked: false }`)}_isEmpty(){return this.multipleChoice?this.modelValue.length===0:typeof this.modelValue=="string"&&this.modelValue===""||this.modelValue===void 0||this.modelValue===null}_checkSingleChoiceElements(t){const{target:s}=t;if(s.checked===!1)return;const n=s.name;this.formElements.filter(o=>o.name===n).forEach(o=>{o!==s&&(o.checked=!1)})}_getCheckedElements(){return this.formElements.filter(t=>t.checked&&!t.disabled)}_setCheckedElements(t,s){if(t==null){this.formElements.forEach(n=>n.checked=!1);return}for(let n=0;n<this.formElements.length;n+=1)if(this.multipleChoice){let o=t.includes(this.formElements[n].modelValue.value);typeof this.formElements[n].modelValue.value=="object"&&(o=t.map(r=>JSON.stringify(r)).includes(JSON.stringify(this.formElements[n].modelValue.value))),this.formElements[n].checked=o}else s(this.formElements[n],t)?this.formElements[n].checked=!0:this.formElements[n].checked=!1}__setChoiceGroupTouched(){const t=this.modelValue;t!=null&&t!==this.__previousCheckedValue&&(this.touched=!0,this.__previousCheckedValue=t)}_onBeforeRepropagateChildrenValues(t){const s=t.detail&&t.detail.element||t.target;this.multipleChoice||!s.checked||(this.formElements.forEach(n=>{s.choiceValue!==n.choiceValue&&(n.checked=!1)}),this.__setChoiceGroupTouched(),this.requestUpdate("modelValue",this._oldModelValue),this._oldModelValue=this.modelValue)}_repropagationCondition(t){return!(this._repropagationRole==="choice-group"&&!this.multipleChoice&&!t.checked)}},Bi=ee(Wc),jc=(i,e={})=>i.value!==e.value||i.checked!==e.checked,Kc=i=>class extends Ys(i){static get properties(){return{checked:{type:Boolean,reflect:!0},disabled:{type:Boolean,reflect:!0},modelValue:{type:Object,hasChanged:jc},choiceValue:{type:Object}}}get choiceValue(){return this.modelValue.value}set choiceValue(t){this.requestUpdate("choiceValue",this.choiceValue),this.modelValue.value!==t&&(this.modelValue={value:t,checked:this.modelValue.checked})}requestUpdate(t,s,n){super.requestUpdate(t,s,n),t==="modelValue"?this.modelValue.checked!==this.checked&&this.__syncModelCheckedToChecked(this.modelValue.checked):t==="checked"&&this.modelValue.checked!==this.checked&&this.__syncCheckedToModel(this.checked)}firstUpdated(t){super.firstUpdated(t),t.has("checked")&&this.__syncCheckedToInputElement()}updated(t){super.updated(t),t.has("modelValue")&&this.__syncCheckedToInputElement(),t.has("name")&&this._parentFormGroup&&this._parentFormGroup.name!==this.name&&this._syncNameToParentFormGroup()}constructor(){super(),this.modelValue={value:"",checked:!1},this.disabled=!1,this._preventDuplicateLabelClick=this._preventDuplicateLabelClick.bind(this),this._toggleChecked=this._toggleChecked.bind(this)}static get styles(){return[...super.styles||[],F`
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
        `]}render(){return w`
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
      `}_choiceGraphicTemplate(){return K}_afterTemplate(){return K}connectedCallback(){super.connectedCallback(),this._labelNode&&this._labelNode.addEventListener("click",this._preventDuplicateLabelClick),this.addEventListener("user-input-changed",this._toggleChecked)}disconnectedCallback(){super.disconnectedCallback(),this._labelNode&&this._labelNode.removeEventListener("click",this._preventDuplicateLabelClick),this.removeEventListener("user-input-changed",this._toggleChecked)}_preventDuplicateLabelClick(t){const s=n=>{n.stopImmediatePropagation(),this._inputNode.removeEventListener("click",s)};this._inputNode.addEventListener("click",s)}_toggleChecked(t){this.disabled||(this._isHandlingUserInput=!0,this.checked=!this.checked,this._isHandlingUserInput=!1)}_syncNameToParentFormGroup(){this._parentFormGroup.tagName.includes(this.tagName)&&(this.name=this._parentFormGroup?.name||"")}__syncModelCheckedToChecked(t){this.checked=t}__syncCheckedToModel(t){this.modelValue={value:this.choiceValue,checked:t}}__syncCheckedToInputElement(){this._inputNode&&(this._inputNode.checked=this.checked)}_proxyInputEvent(){}_onModelValueChanged({modelValue:t},s){let n;s&&s.modelValue&&(n=s.modelValue),this.constructor.elementProperties.get("modelValue").hasChanged(t,n)&&super._onModelValueChanged({modelValue:t})}parser(){return this.modelValue}formatter(t){return t&&t.value!==void 0?t.value:t}clear(){this.checked=!1}_isEmpty(){return!this.checked}_syncValueUpwards(){}},Ui=ee(Kc);class Gc extends Pi{static get validatorName(){return"FormElementsHaveNoError"}execute(e,t,s){return s?.node._anyFormElementHasFeedbackFor("error")}static async getMessage(){return""}}const Yc=i=>class extends Xs(rt(Zt(jt(xt(i))))){static get properties(){return{submitted:{type:Boolean,reflect:!0},focused:{type:Boolean,reflect:!0},dirty:{type:Boolean,reflect:!0},touched:{type:Boolean,reflect:!0},prefilled:{type:Boolean,reflect:!0}}}get _inputNode(){return this}get modelValue(){return this._getFromAllFormElements("modelValue")}set modelValue(t){this.__isInitialModelValue?(this.__isInitialModelValue=!1,this.registrationComplete.then(()=>{this._setValueMapForAllFormElements("modelValue",t)})):this._setValueMapForAllFormElements("modelValue",t)}get serializedValue(){return this._getFromAllFormElements("serializedValue")}set serializedValue(t){this.__isInitialSerializedValue?(this.__isInitialSerializedValue=!1,this.registrationComplete.then(()=>{this._setValueMapForAllFormElements("serializedValue",t)})):this._setValueMapForAllFormElements("serializedValue",t)}get formattedValue(){return this._getFromAllFormElements("formattedValue")}set formattedValue(t){this._setValueMapForAllFormElements("formattedValue",t)}get prefilled(){return this._everyFormElementHas("prefilled")}constructor(){super(),this.value="",this.disabled=!1,this.submitted=!1,this.dirty=!1,this.touched=!1,this.focused=!1,this.__addedSubValidators=!1,this.__isInitialModelValue=!0,this.__isInitialSerializedValue=!0,this._checkForOutsideClick=this._checkForOutsideClick.bind(this),this.addEventListener("focusin",this._syncFocused),this.addEventListener("focusout",this._onFocusOut),this.addEventListener("dirty-changed",this._syncDirty),this.addEventListener("validate-performed",this.__onChildValidatePerformed),this.defaultValidators=[new Gc],this.__descriptionElementsInParentChain=new Set,this.__pendingValues={modelValue:{},serializedValue:{}}}connectedCallback(){super.connectedCallback(),this.setAttribute("role","group"),this.initComplete.then(()=>{this.__isInitialModelValue=!1,this.__isInitialSerializedValue=!1,this.__initInteractionStates()})}disconnectedCallback(){super.disconnectedCallback(),this.__hasActiveOutsideClickHandling&&(document.removeEventListener("click",this._checkForOutsideClick),this.__hasActiveOutsideClickHandling=!1),this.__descriptionElementsInParentChain.clear()}__initInteractionStates(){this.formElements.forEach(t=>{typeof t.initInteractionState=="function"&&t.initInteractionState()})}_triggerInitialModelValueChangedEvent(){this.registrationComplete.then(()=>{this._dispatchInitialModelValueChangedEvent()})}updated(t){super.updated(t),t.has("disabled")&&(this.disabled?this.__requestChildrenToBeDisabled():this.__retractRequestChildrenToBeDisabled()),t.has("focused")&&this.focused===!0&&this.__setupOutsideClickHandling()}__setupOutsideClickHandling(){this.__hasActiveOutsideClickHandling||(document.addEventListener("click",this._checkForOutsideClick),this.__hasActiveOutsideClickHandling=!0)}_checkForOutsideClick(t){!this.contains(t.target)&&(this.touched=!0)}__requestChildrenToBeDisabled(){this.formElements.forEach(t=>{t.makeRequestToBeDisabled&&t.makeRequestToBeDisabled()})}__retractRequestChildrenToBeDisabled(){this.formElements.forEach(t=>{t.retractRequestToBeDisabled&&t.retractRequestToBeDisabled()})}_inputGroupTemplate(){return w`
        <div class="input-group">
          <slot></slot>
        </div>
      `}submitGroup(){this.submitted=!0,this.formElements.forEach(t=>{typeof t.submitGroup=="function"?t.submitGroup():t.submitted=!0})}resetGroup(){this.formElements.forEach(t=>{typeof t.resetGroup=="function"?t.resetGroup():typeof t.reset=="function"&&t.reset()}),this.resetInteractionState()}clearGroup(){this.formElements.forEach(t=>{typeof t.clearGroup=="function"?t.clearGroup():typeof t.clear=="function"&&t.clear()}),this.resetInteractionState()}resetInteractionState(){this.submitted=!1,this.touched=!1,this.dirty=!1,this.formElements.forEach(t=>{typeof t.resetInteractionState=="function"&&t.resetInteractionState()})}_getFromAllFormElementsFilter(t,s){return!t.disabled}_getFromAllFormElements(t,s){const n={},o=s||this._getFromAllFormElementsFilter;return this.formElements._keys().forEach(r=>{const a=this.formElements[r];a instanceof As?n[r]=a.filter(c=>o(c,t)).map(c=>c[t]):o(a,t)&&(typeof a._getFromAllFormElements=="function"?n[r]=a._getFromAllFormElements(t):n[r]=a[t])}),n}_setValueForAllFormElements(t,s){this.formElements.forEach(n=>{n[t]=s})}_setValueMapForAllFormElements(t,s){s&&typeof s=="object"&&Object.keys(s).forEach(n=>{Array.isArray(this.formElements[n])&&this.formElements[n].forEach((o,r)=>{o[t]=s[n][r]}),this.formElements[n]?this.formElements[n][t]=s[n]:this.__pendingValues[t][n]=s[n]})}_anyFormElementHas(t){return Object.keys(this.formElements).some(s=>Array.isArray(this.formElements[s])?this.formElements[s].some(n=>!!n[t]):!!this.formElements[s][t])}_anyFormElementHasFeedbackFor(t){return Object.keys(this.formElements).some(s=>Array.isArray(this.formElements[s])?this.formElements[s].some(n=>!!(n.hasFeedbackFor&&n.hasFeedbackFor.includes(t))):!!(this.formElements[s].hasFeedbackFor&&this.formElements[s].hasFeedbackFor.includes(t)))}_everyFormElementHas(t){return Object.keys(this.formElements).every(s=>Array.isArray(this.formElements[s])?this.formElements[s].every(n=>!!n[t]):!!this.formElements[s][t])}__onChildValidatePerformed(t){t&&this.isRegisteredFormElement(t.target)&&this.validate()}_syncFocused(){this.focused=this._anyFormElementHas("focused")}_onFocusOut(t){const s=this.formElements[this.formElements.length-1];t.target===s&&(this.touched=!0),this.focused=!1}_syncDirty(){this.dirty=this._anyFormElementHas("dirty")}__storeAllDescriptionElementsInParentChain(){let s=this;for(;s;){const n=s._getAriaDescriptionElements();Io(n,{reverse:!0}).forEach(r=>{r.getAttribute("slot")==="feedback"&&this.__descriptionElementsInParentChain.add(r)}),s=s._parentFormGroup}}__linkParentMessages(t){this.__descriptionElementsInParentChain.forEach(s=>{typeof t.addToAriaDescribedBy=="function"&&t.addToAriaDescribedBy(s,{reorder:!1})})}__unlinkParentMessages(t){this.__descriptionElementsInParentChain.forEach(s=>{typeof t.removeFromAriaDescribedBy=="function"&&t.removeFromAriaDescribedBy(s)})}addFormElement(t,s){if(super.addFormElement(t,s),this.disabled&&t.makeRequestToBeDisabled(),this.__descriptionElementsInParentChain.size||this.__storeAllDescriptionElementsInParentChain(),this.__linkParentMessages(t),this.validate({clearCurrentResult:!0}),!t.modelValue){const n=this.__pendingValues;n.modelValue&&n.modelValue[t.name]?t.modelValue=n.modelValue[t.name]:n.serializedValue&&n.serializedValue[t.name]&&(t.serializedValue=n.serializedValue[t.name])}}get _initialModelValue(){return this._getFromAllFormElements("_initialModelValue")}removeFormElement(t){super.removeFormElement(t),this.validate({clearCurrentResult:!0}),typeof t.removeFromAriaLabelledBy=="function"&&this._labelNode&&t.removeFromAriaLabelledBy(this._labelNode,{reorder:!1}),this.__unlinkParentMessages(t)}_isEmpty(){return this.formElements.every(t=>t._isEmpty?.())}},$o=ee(Yc);class Hi extends Do(Xt){static get properties(){return{readOnly:{type:Boolean,attribute:"readonly",reflect:!0},type:{type:String,reflect:!0},placeholder:{type:String,reflect:!0}}}get slots(){return{...super.slots,input:()=>{const e=document.createElement("input"),t=this.getAttribute("value");return t&&e.setAttribute("value",t),e}}}get _inputNode(){return super._inputNode}constructor(){super(),this.readOnly=!1,this.type="text",this.placeholder=""}requestUpdate(e,t,s){super.requestUpdate(e,t,s),e==="readOnly"&&this.__delegateReadOnly()}firstUpdated(e){super.firstUpdated(e),this.__delegateReadOnly()}updated(e){super.updated(e),e.has("type")&&(this._inputNode.type=this.type),e.has("placeholder")&&(this._inputNode.placeholder=this.placeholder),e.has("disabled")&&(this._inputNode.disabled=this.disabled,this.validate()),e.has("name")&&(this._inputNode.name=this.name),e.has("autocomplete")&&(this._inputNode.autocomplete=this.autocomplete)}__delegateReadOnly(){this._inputNode&&(this._inputNode.readOnly=this.readOnly)}}var Ts=class extends Hi{static get styles(){return[...super.styles,Gt,oc]}connectedCallback(){if(super.connectedCallback(),this._inputNode&&this.size){let e=parseInt(this.size,10);e>0&&(this._inputNode.size=e)}}};T([b({type:Number,reflect:!0})],Ts.prototype,"size",void 0),customElements.get("craft-input")||customElements.define("craft-input",Ts);const be=i=>i??K;class mi extends Pi{static validatorName="IsAcceptedFile";static checkFileSize(e,t){return e<=t}static getExtension(e){return e?.slice(e.lastIndexOf("."))}static isExtensionAllowed(e,t){return t?.find(s=>s.toUpperCase()===e.toUpperCase())}static isFileTypeAllowed(e,t){return t?.find(s=>s.toUpperCase()===e.toUpperCase())}execute(e,t=this.param){let s,n;const o=this.constructor,{allowedFileTypes:r,allowedFileExtensions:a,maxFileSize:c}=t;return r?.length?(s=e.some(h=>!o.isFileTypeAllowed(h.type,r)),s):a?.length?(n=e.some(h=>!o.isExtensionAllowed(o.getExtension(h.name),a)),n):e.findIndex(h=>!o.checkFileSize(h.size,c))>-1}static async getMessage(){return""}}class Zc extends Pi{static validatorName="DuplicateFileNames";constructor(e,t){super(e,t),this.type="info"}execute(e,t=this.param){return t.show}static async getMessage(){return ki().msg("lion-input-file:uploadTextDuplicateFileName")}}const Xc=524288e3,ss={type:"FILE_TYPE",size:"FILE_SIZE"},St={fail:"FAIL",pass:"SUCCESS"};class Qc{constructor(e,t){this.failedProp=[],this.systemFile=e,this._acceptCriteria=t,this.uploadFileStatus(),this.failedProp.length===0&&this.createDownloadUrl(e)}_getFileNameExtension(e){return e.slice(e.lastIndexOf("."))}uploadFileStatus(){if(this._acceptCriteria.allowedFileExtensions.length){const e=this._getFileNameExtension(this.systemFile.name);mi.isExtensionAllowed(e,this._acceptCriteria.allowedFileExtensions)||(this.status=St.fail,this.failedProp.push(ss.type))}else if(this._acceptCriteria.allowedFileTypes.length){const e=this.systemFile.type;mi.isFileTypeAllowed(e,this._acceptCriteria.allowedFileTypes)||(this.status=St.fail,this.failedProp.push(ss.type))}mi.checkFileSize(this.systemFile.size,this._acceptCriteria.maxFileSize)?this.status!==St.fail&&(this.status=St.pass):(this.status=St.fail,this.failedProp.push(ss.size))}createDownloadUrl(e){this.downloadUrl=window.URL.createObjectURL(e)}}const Sn=(i,e,t)=>{const s=new Map;for(let n=e;n<=t;n++)s.set(i[n],n);return s},Jc=Fi(class extends Li{constructor(i){if(super(i),i.type!==Ni.CHILD)throw Error("repeat() can only be used in text expressions")}dt(i,e,t){let s;t===void 0?t=e:e!==void 0&&(s=e);const n=[],o=[];let r=0;for(const a of i)n[r]=s?s(a,r):r,o[r]=t(a,r),r++;return{values:o,keys:n}}render(i,e,t){return this.dt(i,e,t).values}update(i,[e,t,s]){const n=tl(i),{values:o,keys:r}=this.dt(e,t,s);if(!Array.isArray(n))return this.ut=r,o;const a=this.ut??=[],c=[];let u,h,g=0,f=n.length-1,m=0,y=o.length-1;for(;g<=f&&m<=y;)if(n[g]===null)g++;else if(n[f]===null)f--;else if(a[g]===r[m])c[m]=Xe(n[g],o[m]),g++,m++;else if(a[f]===r[y])c[y]=Xe(n[f],o[y]),f--,y--;else if(a[g]===r[y])c[y]=Xe(n[g],o[y]),kt(i,c[y+1],n[g]),g++,y--;else if(a[f]===r[m])c[m]=Xe(n[f],o[m]),kt(i,n[g],n[f]),f--,m++;else if(u===void 0&&(u=Sn(r,m,y),h=Sn(a,g,f)),u.has(a[g]))if(u.has(a[f])){const _=h.get(r[m]),x=_!==void 0?n[_]:null;if(x===null){const p=kt(i,n[g]);Xe(p,o[m]),c[m]=p}else c[m]=Xe(x,o[m]),kt(i,n[g],x),n[_]=null;m++}else Yi(n[f]),f--;else Yi(n[g]),g++;for(;m<=y;){const _=kt(i,c[y+1]);Xe(_,o[m]),c[m++]=_}for(;g<=f;){const _=n[g++];_!==null&&Yi(_)}return this.ut=r,el(i,c),Rt}}),Mo=i=>{switch(i){case"bg-BG":return E(()=>import("./bg-BG.js"),__vite__mapDeps([34,35]),import.meta.url);case"bg":return E(()=>import("./bg2.js"),[],import.meta.url);case"cs-CZ":return E(()=>import("./cs-CZ.js"),__vite__mapDeps([36,37]),import.meta.url);case"cs":return E(()=>import("./cs2.js"),[],import.meta.url);case"de-DE":return E(()=>import("./de-DE.js"),__vite__mapDeps([38,39]),import.meta.url);case"de":return E(()=>import("./de2.js"),[],import.meta.url);case"en-AU":return E(()=>import("./en-AU.js"),__vite__mapDeps([40,41]),import.meta.url);case"en-GB":return E(()=>import("./en-GB.js"),__vite__mapDeps([42,41]),import.meta.url);case"en-US":return E(()=>import("./en-US.js"),__vite__mapDeps([43,41]),import.meta.url);case"en-PH":case"en":return E(()=>import("./en2.js"),[],import.meta.url);case"es-ES":return E(()=>import("./es-ES.js"),__vite__mapDeps([44,45]),import.meta.url);case"es":return E(()=>import("./es2.js"),[],import.meta.url);case"fr-FR":return E(()=>import("./fr-FR.js"),__vite__mapDeps([46,47]),import.meta.url);case"fr-BE":return E(()=>import("./fr-BE.js"),__vite__mapDeps([48,47]),import.meta.url);case"fr":return E(()=>import("./fr2.js"),[],import.meta.url);case"hu-HU":return E(()=>import("./hu-HU.js"),__vite__mapDeps([49,50]),import.meta.url);case"hu":return E(()=>import("./hu2.js"),[],import.meta.url);case"it-IT":return E(()=>import("./it-IT.js"),__vite__mapDeps([51,52]),import.meta.url);case"it":return E(()=>import("./it2.js"),[],import.meta.url);case"nl-BE":return E(()=>import("./nl-BE.js"),__vite__mapDeps([53,54]),import.meta.url);case"nl-NL":return E(()=>import("./nl-NL.js"),__vite__mapDeps([55,54]),import.meta.url);case"nl":return E(()=>import("./nl2.js"),[],import.meta.url);case"pl-PL":return E(()=>import("./pl-PL.js"),__vite__mapDeps([56,57]),import.meta.url);case"pl":return E(()=>import("./pl2.js"),[],import.meta.url);case"ro-RO":return E(()=>import("./ro-RO.js"),__vite__mapDeps([58,59]),import.meta.url);case"ro":return E(()=>import("./ro2.js"),[],import.meta.url);case"ru-RU":return E(()=>import("./ru-RU.js"),__vite__mapDeps([60,61]),import.meta.url);case"ru":return E(()=>import("./ru2.js"),[],import.meta.url);case"sk-SK":return E(()=>import("./sk-SK.js"),__vite__mapDeps([62,63]),import.meta.url);case"sk":return E(()=>import("./sk2.js"),[],import.meta.url);case"uk-UA":return E(()=>import("./uk-UA.js"),__vite__mapDeps([64,65]),import.meta.url);case"uk":return E(()=>import("./uk2.js"),[],import.meta.url);case"zh-CN":case"zh":return E(()=>import("./zh2.js"),[],import.meta.url);default:return E(()=>import("./en2.js"),[],import.meta.url)}};class Vo extends zi(Yt(H)){static get scopedElements(){return{...super.scopedElements,"lion-validation-feedback":Oo}}static get properties(){return{fileList:{type:Array},multiple:{type:Boolean}}}static localizeNamespaces=[{"lion-input-file":Mo},...super.localizeNamespaces];constructor(){super(),this.fileList=[],this.multiple=!1}updated(e){super.updated(e),e.has("fileList")&&this._enhanceLightDomA11y()}_enhanceLightDomA11y(){const e=this.shadowRoot?.querySelectorAll('[id^="file-feedback"]'),t=this.parentNode?.parentNode;e?.forEach(s=>{t?.addEventListener("focusin",()=>{s.setAttribute("aria-live","polite")}),t?.addEventListener("focusout",()=>{s.setAttribute("aria-live","assertive")})})}_removeFile(e){this.dispatchEvent(new CustomEvent("file-remove-requested",{detail:{removedFile:e,status:e.status,uploadResponse:e.response}}))}_validationFeedbackTemplate(e,t){return w`
      <lion-validation-feedback
        id="file-feedback-${t}"
        .feedbackData="${e}"
        aria-live="assertive"
      ></lion-validation-feedback>
    `}_listItemBeforeTemplate(e){return K}_listItemAfterTemplate(e,t){return w`
      <button
        class="selected__list__item__remove-button"
        aria-label="${this.msgLit("lion-input-file:removeButtonLabel",{fileName:e.systemFile.name})}"
        @click=${()=>this._removeFile(e)}
      >
        ${this._removeButtonContentTemplate()}
      </button>
    `}_removeButtonContentTemplate(){return w`✖️`}_selectedListItemTemplate(e){const t=Kt();return w`
      <div class="selected__list__item" status="${e.status?e.status.toLowerCase():""}">
        <div class="selected__list__item__label">
          ${this._listItemBeforeTemplate(e)}
          <span id="selected-list-item-label-${t}" class="selected__list__item__label__text">
            <span class="sr-only">${this.msgLit("lion-input-file:fileNameDescriptionLabel")}</span>
            ${e.downloadUrl&&e.status!=="LOADING"?w`
                  <a
                    class="selected__list__item__label__link"
                    href="${e.downloadUrl}"
                    target="${e.downloadUrl.startsWith("blob")?"_blank":""}"
                    rel="${be(e.downloadUrl.startsWith("blob")?"noopener noreferrer":void 0)}"
                    >${e.systemFile?.name}</a
                  >
                `:e.systemFile?.name}
          </span>
          ${this._listItemAfterTemplate(e,t)}
        </div>
        ${e.status==="FAIL"&&e.validationFeedback?w`
              ${Jc(e.validationFeedback,s=>w`
                  ${this._validationFeedbackTemplate([s],t)}
                `)}
            `:K}
      </div>
    `}render(){return this.fileList?.length?w`
          ${this.multiple?w`
                <ul class="selected__list">
                  ${this.fileList.map(e=>w` <li>${this._selectedListItemTemplate(e)}</li> `)}
                </ul>
              `:w` ${this._selectedListItemTemplate(this.fileList[0])} `}
        `:K}static get styles(){return[F`
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
      `]}}function ns(i,e=2){if(!+i)return"0 Bytes";const t=1024,s=e<0?0:e,n=[" bytes","KB","MB","GB","TB","PB","EB","ZB","YB"],o=Math.floor(Math.log(i)/Math.log(t));return`${parseFloat((i/t**o).toFixed(s))}${n[o]}`}class ed extends Yt(zi(Xt)){static get scopedElements(){return{...super.scopedElements,"lion-selected-file-list":Vo}}static get properties(){return{accept:{type:String},multiple:{type:Boolean,reflect:!0},buttonLabel:{type:String,attribute:"button-label"},maxFileSize:{type:Number,attribute:"max-file-size"},enableDropZone:{type:Boolean,attribute:"enable-drop-zone"},uploadOnSelect:{type:Boolean,attribute:"upload-on-select"},isDragging:{type:Boolean,attribute:"is-dragging",reflect:!0},uploadResponse:{type:Array,state:!1},_selectedFilesMetaData:{type:Array,state:!0}}}static localizeNamespaces=[{"lion-input-file":Mo},...super.localizeNamespaces];static get validationTypes(){return["error","info"]}get slots(){return{...super.slots,input:()=>w`<input .value="${be(this.getAttribute("value"))}" />`,"file-select-button":()=>w`<button
          type="button"
          id="select-button-${this._inputId}"
          @click="${this.__openDialogOnBtnClick}"
        >
          ${this.buttonLabel}
        </button>`,after:()=>w`<div data-description></div>`,"selected-file-list":()=>({template:w`
          <lion-selected-file-list
            .fileList=${this._selectedFilesMetaData}
            .multiple=${this.multiple}
          ></lion-selected-file-list>
        `,renderAsDirectHostChild:!0})}}get _inputNode(){return super._inputNode}get _buttonNode(){return this.querySelector(`#select-button-${this._inputId}`)}get buttonLabel(){return this.__buttonLabel||this._buttonNode?.textContent?.trim()||""}set buttonLabel(e){const t=this.buttonLabel;this.__buttonLabel=e,this.requestUpdate("buttonLabel",t)}get _focusableNode(){return this._buttonNode}get _isDragAndDropSupported(){return"draggable"in document.createElement("div")}constructor(){super(),this.type="file",this._selectedFilesMetaData=[],this.uploadResponse=[],this.__initialUploadResponse=this.uploadResponse,this.uploadOnSelect=!1,this.multiple=!1,this.enableDropZone=!1,this.maxFileSize=Xc,this.accept="",this.buttonLabel="",this._initialButtonLabel="",this.modelValue=[],this._onRemoveFile=this._onRemoveFile.bind(this),this.__duplicateFileNamesValidator=new Zc({show:!1}),this.__previouslyParsedFiles=null}get _fileListNode(){return Array.from(this.children).find(e=>e.slot==="selected-file-list")}connectedCallback(){super.connectedCallback(),this.__initialUploadResponse=this.uploadResponse,this._initialButtonLabel=this.buttonLabel,this._inputNode.addEventListener("change",this._onChange),this._inputNode.addEventListener("click",this._onClick)}disconnectedCallback(){super.disconnectedCallback(),this._inputNode.removeEventListener("change",this._onChange),this._inputNode.removeEventListener("click",this._onClick)}onLocaleUpdated(){super.onLocaleUpdated(),this.multiple?this.buttonLabel=this._initialButtonLabel||this.msgLit("lion-input-file:selectTextMultipleFile"):this.buttonLabel=this._initialButtonLabel||this.msgLit("lion-input-file:selectTextSingleFile")}get operationMode(){return"upload"}get _acceptCriteria(){let e=[],t=[];if(this.accept){const s=this.accept.replace(/\s+/g,"").split(",");e=s.filter(n=>n.includes("/")),t=s.filter(n=>!n.includes("/"))}return{allowedFileTypes:e,allowedFileExtensions:t,maxFileSize:this.maxFileSize}}reset(){super.reset(),this._selectedFilesMetaData=[],this.uploadResponse=this.__initialUploadResponse,this.modelValue=[],this.dirty=!1}clear(){this._selectedFilesMetaData=[],this.uploadResponse=[],this.modelValue=[]}_showFeedbackConditionFor(e,t){return super._showFeedbackConditionFor(e,t)&&!(this.validationStates.error?.FileTypeAllowed||this.validationStates.error?.FileSizeAllowed)}parser(){if(this.__previouslyParsedFiles===this._inputNode.files)return this.modelValue;this.__previouslyParsedFiles=this._inputNode.files;const e=this._inputNode.files?Array.from(this._inputNode.files):[];return this.multiple?[...this.modelValue??[],...e]:e}formatter(e){return this._inputNode?.value||""}__setupDragDropEventListeners(){const e=this.shadowRoot?.querySelector(".input-file__drop-zone");["dragenter","dragover","dragleave"].forEach(t=>{e?.addEventListener(t,s=>{s.preventDefault(),s.stopPropagation(),this.isDragging=t!=="dragleave"},!1)}),window.addEventListener("drop",t=>{t.target===this._inputNode&&t.preventDefault(),this.isDragging=!1},!1)}firstUpdated(e){super.firstUpdated(e),this.__setupFileValidators(),this._inputNode&&(this._inputNode.type=this.type,this._inputNode.setAttribute("tabindex","-1"),this._inputNode.multiple=this.multiple,this.accept.length&&(this._inputNode.accept=this.accept)),this.enableDropZone&&this._isDragAndDropSupported&&(this.__setupDragDropEventListeners(),this.setAttribute("drop-zone","")),this._fileListNode.addEventListener("file-remove-requested",this._onRemoveFile)}updated(e){super.updated(e),e.has("disabled")&&(this._inputNode.disabled=this.disabled,this.validate()),e.has("buttonLabel")&&this._buttonNode&&(this._buttonNode.textContent=this.buttonLabel),e.has("name")&&(this._inputNode.name=this.name),e.has("_ariaLabelledNodes")&&this.__syncAriaLabelledByAttributesToButton(),e.has("_ariaDescribedNodes")&&this.__syncAriaDescribedByAttributesToButton(),e.has("uploadResponse")&&(this._selectedFilesMetaData.length===0&&this.uploadResponse.forEach(t=>{const s={systemFile:{name:t.name},response:t,status:t.status,validationFeedback:[{message:t.errorMessage}]};this._selectedFilesMetaData=[...this._selectedFilesMetaData,s]}),this._selectedFilesMetaData.forEach(t=>{!this.uploadResponse.some(s=>s.name===t.systemFile.name)&&this.uploadOnSelect?this.__removeFileFromList(t):(this.uploadResponse.forEach(s=>{s.name===t.systemFile.name&&(t.response=s,t.downloadUrl=s.downloadUrl?s.downloadUrl:t.downloadUrl,t.status=s.status,t.validationFeedback=[{type:typeof s.errorMessage=="string"&&s.errorMessage?.length>0?"error":"success",message:s.errorMessage??""}])}),this._selectedFilesMetaData=[...this._selectedFilesMetaData])}),this._updateUploadButtonDescription())}__computeNewAddedFiles(e){const t=e.filter(s=>this._selectedFilesMetaData.findIndex(n=>n.systemFile.name===s.name)===-1);return this.__duplicateFileNamesValidator.param={show:e.length!==t.length},this.validate(),t}_processDroppedFiles(e){if(e.preventDefault(),this.isDragging=!1,!(e.dataTransfer&&e.dataTransfer.items.length>1&&!this.multiple||!e.dataTransfer?.files)){if(this._inputNode.files=e.dataTransfer.files,this.multiple){const s=this.__computeNewAddedFiles(Array.from(e.dataTransfer.files));this.modelValue=[...this.modelValue??[],...s]}else this.modelValue=Array.from(e.dataTransfer.files);this._processFiles(Array.from(e.dataTransfer.files))}}_onChange(e){this.touched=!0,this._onUserInputChanged(),this._processFiles(e?.target?.files)}_onClick(e){e.target.value=""}__syncAriaLabelledByAttributesToButton(){if(this._inputNode.hasAttribute("aria-labelledby")){const e=this._inputNode.getAttribute("aria-labelledby");this._buttonNode?.setAttribute("aria-labelledby",`select-button-${this._inputId} ${e}`)}}__syncAriaDescribedByAttributesToButton(){if(this._inputNode.hasAttribute("aria-describedby")){const e=this._inputNode.getAttribute("aria-describedby")||"";this._buttonNode?.setAttribute("aria-describedby",e)}}__setupFileValidators(){this.defaultValidators=[new mi(this._acceptCriteria),this.__duplicateFileNamesValidator]}_processFiles(e){const t=this.__computeNewAddedFiles(Array.from(e));!this.multiple&&t.length>0&&(this._selectedFilesMetaData=[],this.uploadResponse=[]);let s;for(const o of t.values())s=new Qc(o,this._acceptCriteria),s.failedProp?.length?(this._handleErroredFiles(s),this.uploadResponse=[...this.uploadResponse,{name:s.systemFile.name,status:"FAIL",errorMessage:s.validationFeedback[0].message}]):this.uploadResponse=[...this.uploadResponse,{name:s.systemFile.name,status:"SUCCESS"}],this._selectedFilesMetaData=[...this._selectedFilesMetaData,s],this._handleErrors();const n=this._selectedFilesMetaData.filter(({systemFile:o,status:r})=>t.includes(o)&&r==="SUCCESS").map(({systemFile:o})=>o);n.length>0&&this._dispatchFileListChangeEvent(n)}_dispatchFileListChangeEvent(e){this.dispatchEvent(new CustomEvent("file-list-changed",{detail:{newFiles:e}}))}_handleErrors(){let e=!1;if(this._selectedFilesMetaData.forEach(t=>{t.failedProp&&t.failedProp.length>0&&(e=!0)}),e)this.hasFeedbackFor?.push("error"),this.shouldShowFeedbackFor.push("error");else if(this._prevHasErrors&&this.hasFeedbackFor.includes("error")){const t=this.hasFeedbackFor.indexOf("error");this.hasFeedbackFor.slice(t,t+1);const s=this.shouldShowFeedbackFor.indexOf("error");this.shouldShowFeedbackFor.slice(s,s+1)}this._prevHasErrors=e}_handleErroredFiles(e){e.validationFeedback=[];const{allowedFileExtensions:t,allowedFileTypes:s}=this._acceptCriteria;let n=[],o=0,r;t.length?(n=t,r=n.pop(),o=n.length):s.length&&(s.forEach(u=>{if(u.endsWith("/*"))n.push(u.slice(0,-2));else if(u==="text/plain")n.push("text");else{const h=u.indexOf("/"),g=u.slice(h+1);if(!g.includes("+"))n.push(`.${g}`);else{const f=g.split("+");n.push(`.${f[0]}`)}}}),r=n.pop(),o=n.length);let a="";r?o?a=`${this.msgLit("lion-input-file:allowedFileValidatorComplex",{allowedTypesArray:n.join(", "),allowedTypesLastItem:r,maxSize:ns(this.maxFileSize)})}`:a=`${this.msgLit("lion-input-file:allowedFileValidatorSimple",{allowedType:r,maxSize:ns(this.maxFileSize)})}`:a=`${this.msgLit("lion-input-file:allowedFileSize",{maxSize:ns(this.maxFileSize)})}`;const c={message:a,type:"error"};e.validationFeedback?.push(c)}_updateUploadButtonDescription(){const e=[];let t;this._selectedFilesMetaData.forEach(n=>{n.status==="FAIL"&&(t=n.validationFeedback?n.validationFeedback[0].message.toString():"",e.push(n.systemFile.name))});const s=this.querySelector('[slot="after"]');if(s)if(!this._selectedFilesMetaData||this._selectedFilesMetaData.length===0)this.uploadOnSelect?s.textContent=this.msgLit("lion-input-file:noFilesUploaded"):s.textContent=this.msgLit("lion-input-file:noFilesSelected");else if(this._selectedFilesMetaData.length===1){const{name:n}=this._selectedFilesMetaData[0].systemFile;this.uploadOnSelect?s.textContent=t||this.msgLit("lion-input-file:fileUploaded")+(n??""):s.textContent=t||this.msgLit("lion-input-file:fileSelected")+(n??"")}else this.uploadOnSelect?s.textContent=`${this.msgLit("lion-input-file:filesUploaded",{numberOfFiles:this._selectedFilesMetaData.length})} ${t?this.msgLit("lion-input-file:generalValidatorMessage",{validatorMessage:t,listOfErroneousFiles:e.join(", ")}):""}`:s.textContent=`${this.msgLit("lion-input-file:filesSelected",{numberOfFiles:this._selectedFilesMetaData.length})} ${t?this.msgLit("lion-input-file:generalValidatorMessage",{validatorMessage:t,listOfErroneousFiles:e.join(", ")}):""}`}__removeFileFromList(e){this._selectedFilesMetaData=this._selectedFilesMetaData.filter(t=>t.systemFile.name!==e.systemFile.name),this.modelValue&&(this.modelValue=this.modelValue.filter(t=>t.name!==e.systemFile.name)),this._inputNode.value="",this._handleErrors(),this._updateUploadButtonDescription()}_onRemoveFile(e){if(this.disabled)return;const{removedFile:t}=e.detail;!this.uploadOnSelect&&t&&this.__removeFileFromList(t),this._removeFile(t)}_removeFile(e){this.dispatchEvent(new CustomEvent("file-removed",{detail:{removedFile:e,status:e.status,uploadResponse:e.response}}))}_reflectBackOn(){return!1}_isEmpty(){return this.modelValue?.length===0}_dropZoneTemplate(){return w`
      <div @drop="${this._processDroppedFiles}" class="input-file__drop-zone">
        <div class="input-file__drop-zone__text">
          ${this.msgLit("lion-input-file:dragAndDropText")}
        </div>
        <slot name="file-select-button"></slot>
      </div>
    `}_inputGroupAfterTemplate(){return w` <slot name="selected-file-list"></slot> `}_inputGroupInputTemplate(){return w`
      <slot name="input"> </slot>
      <slot name="after"> </slot>
      ${this.enableDropZone&&this._isDragAndDropSupported?this._dropZoneTemplate():w`
            <div class="input-group__file-select-button">
              <slot name="file-select-button"></slot>
            </div>
          `}
    `}static get styles(){return[super.styles,F`
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
      `]}__openDialogOnBtnClick(e){e.preventDefault(),e.stopPropagation(),this._inputNode.click()}}var td=class extends Vo{static get styles(){return[...super.styles,F`
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
          border: 1px solid var(--c-color-neutral-border-subtle);
          border-radius: var(--c-radius-sm);
          background-color: var(--c-bg-surface);
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
      `]}_listItemAfterTemplate(e,t){return w`
      <craft-button
        icon
        size="small"
        variant="plain"
        class="selected__list__item__remove-button"
        aria-label="${this.msgLit("lion-input-file:removeButtonLabel",{fileName:e.systemFile.name})}"
        @click=${()=>this._removeFile(e)}
      >
        ${this._removeButtonContentTemplate()}
      </craft-button>
    `}_removeButtonContentTemplate(){return w`<craft-icon name="x"></craft-icon>`}_listItemBeforeTemplate(e){return w`<img src="${e.downloadUrl}" alt="" class="preview-thumb" />`}},id=F`
  /* Add any craft-specific styles for input-file here */
  ::slotted([slot='selected-file-list']) {
    margin-block-start: var(--c-spacing-lg);
  }
`,sd=class extends ed{static get styles(){return[...super.styles,Gt,id]}get slots(){return{...super.slots,"file-select-button":()=>w`<craft-button
          type="button"
          id="select-button-${this._inputId}"
          @click="${this.__openDialogOnBtnClick}"
        >
          ${this.buttonLabel}
        </craft-button>`}}static get scopedElements(){return{...super.scopedElements,"lion-selected-file-list":td}}};customElements.get("craft-input-file")||customElements.define("craft-input-file",sd);var nd=function(i,e,t,s,n){if(s==="m")throw new TypeError("Private method is not writable");if(s==="a"&&!n)throw new TypeError("Private accessor was defined without a setter");if(typeof e=="function"?i!==e||!n:!e.has(i))throw new TypeError("Cannot write private member to an object whose class did not declare it");return s==="a"?n.call(i,t):n?n.value=t:e.set(i,t),t},An=function(i,e,t,s){if(t==="a"&&!s)throw new TypeError("Private accessor was defined without a getter");if(typeof e=="function"?i!==e||!s:!e.has(i))throw new TypeError("Cannot read private member from an object whose class did not declare it");return t==="m"?s:t==="a"?s.call(i):s?s.value:e.get(i)},Ft;class od{formatToParts(e){const t=[];for(const s of e)t.push({type:"element",value:s}),t.push({type:"literal",value:", "});return t.slice(0,-1)}}const rd=typeof Intl<"u"&&Intl.ListFormat||od,ad=[["years","year"],["months","month"],["weeks","week"],["days","day"],["hours","hour"],["minutes","minute"],["seconds","second"],["milliseconds","millisecond"]],ld={minimumIntegerDigits:2};class cd{constructor(e,t={}){Ft.set(this,void 0);let s=String(t.style||"short");s!=="long"&&s!=="short"&&s!=="narrow"&&s!=="digital"&&(s="short");let n=s==="digital"?"numeric":s;const o=t.hours||n;n=o==="2-digit"?"numeric":o;const r=t.minutes||n;n=r==="2-digit"?"numeric":r;const a=t.seconds||n;n=a==="2-digit"?"numeric":a;const c=t.milliseconds||n;nd(this,Ft,{locale:e,style:s,years:t.years||s==="digital"?"short":s,yearsDisplay:t.yearsDisplay==="always"?"always":"auto",months:t.months||s==="digital"?"short":s,monthsDisplay:t.monthsDisplay==="always"?"always":"auto",weeks:t.weeks||s==="digital"?"short":s,weeksDisplay:t.weeksDisplay==="always"?"always":"auto",days:t.days||s==="digital"?"short":s,daysDisplay:t.daysDisplay==="always"?"always":"auto",hours:o,hoursDisplay:t.hoursDisplay==="always"||s==="digital"?"always":"auto",minutes:r,minutesDisplay:t.minutesDisplay==="always"||s==="digital"?"always":"auto",seconds:a,secondsDisplay:t.secondsDisplay==="always"||s==="digital"?"always":"auto",milliseconds:c,millisecondsDisplay:t.millisecondsDisplay==="always"?"always":"auto"},"f")}resolvedOptions(){return An(this,Ft,"f")}formatToParts(e){const t=[],s=An(this,Ft,"f"),n=s.style,o=s.locale;for(const[r,a]of ad){const c=e[r];if(s[`${r}Display`]==="auto"&&!c)continue;const u=s[r],h=u==="2-digit"?ld:u==="numeric"?{}:{style:"unit",unit:a,unitDisplay:u};let g=new Intl.NumberFormat(o,h).format(c);r==="months"&&(u==="narrow"||n==="narrow"&&g.endsWith("m"))&&(g=g.replace(/(\d+)m$/,"$1mo")),t.push(g)}return new rd(o,{type:"unit",style:n==="digital"?"short":n}).formatToParts(t)}format(e){return this.formatToParts(e).map(t=>t.value).join("")}}Ft=new WeakMap;const Ro=/^[-+]?P(?:(\d+)Y)?(?:(\d+)M)?(?:(\d+)W)?(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?)?$/,Si=["year","month","week","day","hour","minute","second","millisecond"],dd=i=>Ro.test(i);class ve{constructor(e=0,t=0,s=0,n=0,o=0,r=0,a=0,c=0){this.years=e,this.months=t,this.weeks=s,this.days=n,this.hours=o,this.minutes=r,this.seconds=a,this.milliseconds=c,this.years||(this.years=0),this.sign||(this.sign=Math.sign(this.years)),this.months||(this.months=0),this.sign||(this.sign=Math.sign(this.months)),this.weeks||(this.weeks=0),this.sign||(this.sign=Math.sign(this.weeks)),this.days||(this.days=0),this.sign||(this.sign=Math.sign(this.days)),this.hours||(this.hours=0),this.sign||(this.sign=Math.sign(this.hours)),this.minutes||(this.minutes=0),this.sign||(this.sign=Math.sign(this.minutes)),this.seconds||(this.seconds=0),this.sign||(this.sign=Math.sign(this.seconds)),this.milliseconds||(this.milliseconds=0),this.sign||(this.sign=Math.sign(this.milliseconds)),this.blank=this.sign===0}abs(){return new ve(Math.abs(this.years),Math.abs(this.months),Math.abs(this.weeks),Math.abs(this.days),Math.abs(this.hours),Math.abs(this.minutes),Math.abs(this.seconds),Math.abs(this.milliseconds))}static from(e){var t;if(typeof e=="string"){const s=String(e).trim(),n=s.startsWith("-")?-1:1,o=(t=s.match(Ro))===null||t===void 0?void 0:t.slice(1).map(r=>(Number(r)||0)*n);return o?new ve(...o):new ve}else if(typeof e=="object"){const{years:s,months:n,weeks:o,days:r,hours:a,minutes:c,seconds:u,milliseconds:h}=e;return new ve(s,n,o,r,a,c,u,h)}throw new RangeError("invalid duration")}static compare(e,t){const s=Date.now(),n=Math.abs(Tn(s,ve.from(e)).getTime()-s),o=Math.abs(Tn(s,ve.from(t)).getTime()-s);return n>o?-1:n<o?1:0}toLocaleString(e,t){return new cd(e,t).format(this)}}function Tn(i,e){const t=new Date(i);return e.sign<0?(t.setUTCSeconds(t.getUTCSeconds()+e.seconds),t.setUTCMinutes(t.getUTCMinutes()+e.minutes),t.setUTCHours(t.getUTCHours()+e.hours),t.setUTCDate(t.getUTCDate()+e.weeks*7+e.days),t.setUTCMonth(t.getUTCMonth()+e.months),t.setUTCFullYear(t.getUTCFullYear()+e.years)):(t.setUTCFullYear(t.getUTCFullYear()+e.years),t.setUTCMonth(t.getUTCMonth()+e.months),t.setUTCDate(t.getUTCDate()+e.weeks*7+e.days),t.setUTCHours(t.getUTCHours()+e.hours),t.setUTCMinutes(t.getUTCMinutes()+e.minutes),t.setUTCSeconds(t.getUTCSeconds()+e.seconds)),t}function ud(i,e="second",t=Date.now()){const s=i.getTime()-t;if(s===0)return new ve;const n=Math.sign(s),o=Math.abs(s),r=Math.floor(o/1e3),a=Math.floor(r/60),c=Math.floor(a/60),u=Math.floor(c/24),h=Math.floor(u/30),g=Math.floor(h/12),f=Si.indexOf(e)||Si.length;return new ve(f>=0?g*n:0,f>=1?(h-g*12)*n:0,0,f>=3?(u-h*30)*n:0,f>=4?(c-u*24)*n:0,f>=5?(a-c*60)*n:0,f>=6?(r-a*60)*n:0,f>=7?(o-r*1e3)*n:0)}function zo(i,{relativeTo:e=Date.now()}={}){if(e=new Date(e),i.blank)return i;const t=i.sign;let s=Math.abs(i.years),n=Math.abs(i.months),o=Math.abs(i.weeks),r=Math.abs(i.days),a=Math.abs(i.hours),c=Math.abs(i.minutes),u=Math.abs(i.seconds),h=Math.abs(i.milliseconds);h>=900&&(u+=Math.round(h/1e3)),(u||c||a||r||o||n||s)&&(h=0),u>=55&&(c+=Math.round(u/60)),(c||a||r||o||n||s)&&(u=0),c>=55&&(a+=Math.round(c/60)),(a||r||o||n||s)&&(c=0),r&&a>=12&&(r+=Math.round(a/24)),!r&&a>=21&&(r+=Math.round(a/24)),(r||o||n||s)&&(a=0);const g=e.getFullYear(),f=e.getMonth(),m=e.getDate();if(r>=27||s+n+r){const y=new Date(e);y.setDate(1),y.setMonth(f+n*t+1),y.setDate(0);const _=Math.max(0,m-y.getDate()),x=new Date(e);x.setFullYear(g+s*t),x.setDate(m-_),x.setMonth(f+n*t),x.setDate(m-_+r*t);const p=x.getFullYear()-e.getFullYear(),k=x.getMonth()-e.getMonth(),C=Math.abs(Math.round((Number(x)-Number(e))/864e5))+_,S=Math.abs(p*12+k);C<27?(r>=6?(o+=Math.round(r/7),r=0):r=C,n=s=0):S<=11?(n=S,s=0):(n=0,s=p*t),(n||s)&&(r=0)}return s&&(n=0),o>=4&&(n+=Math.round(o/4)),(n||s)&&(o=0),r&&o&&!n&&!s&&(o+=Math.round(r/7),r=0),new ve(s*t,n*t,o*t,r*t,a*t,c*t,u*t,h*t)}function hd(i,e){const t=zo(i,e);if(t.blank)return[0,"second"];for(const s of Si){if(s==="millisecond")continue;const n=t[`${s}s`];if(n)return[n,s]}return[0,"second"]}var Y=function(i,e,t,s){if(t==="a"&&!s)throw new TypeError("Private accessor was defined without a getter");if(typeof e=="function"?i!==e||!s:!e.has(i))throw new TypeError("Cannot read private member from an object whose class did not declare it");return t==="m"?s:t==="a"?s.call(i):s?s.value:e.get(i)},ni=function(i,e,t,s,n){if(s==="m")throw new TypeError("Private method is not writable");if(s==="a"&&!n)throw new TypeError("Private accessor was defined without a setter");if(typeof e=="function"?i!==e||!n:!e.has(i))throw new TypeError("Cannot write private member to an object whose class did not declare it");return s==="a"?n.call(i,t):n?n.value=t:e.set(i,t),t},re,Lt,Ot,lt,tt,Ns,Po,Bo,Uo,Ho,qo,Fs,Wo,dt;const pd=globalThis.HTMLElement||null,os=new ve,Nn=new ve(0,0,0,0,0,1);class fd extends Event{constructor(e,t,s,n){super("relative-time-updated",{bubbles:!0,composed:!0}),this.oldText=e,this.newText=t,this.oldTitle=s,this.newTitle=n}}function Fn(i){if(!i.date)return 1/0;if(i.format==="duration"||i.format==="elapsed"){const t=i.precision;if(t==="second")return 1e3;if(t==="minute")return 60*1e3}const e=Math.abs(Date.now()-i.date.getTime());return e<60*1e3?1e3:e<3600*1e3?60*1e3:3600*1e3}const rs=new class{constructor(){this.elements=new Set,this.time=1/0,this.timer=-1}observe(i){if(this.elements.has(i))return;this.elements.add(i);const e=i.date;if(e&&e.getTime()){const t=Fn(i),s=Date.now()+t;s<this.time&&(clearTimeout(this.timer),this.timer=setTimeout(()=>this.update(),t),this.time=s)}}unobserve(i){this.elements.has(i)&&this.elements.delete(i)}update(){if(clearTimeout(this.timer),!this.elements.size)return;let i=1/0;for(const e of this.elements)i=Math.min(i,Fn(e)),e.update();this.time=Math.min(3600*1e3,i),this.timer=setTimeout(()=>this.update(),this.time),this.time+=Date.now()}};class md extends pd{constructor(){super(...arguments),re.add(this),Lt.set(this,!1),Ot.set(this,!1),tt.set(this,this.shadowRoot?this.shadowRoot:this.attachShadow?this.attachShadow({mode:"open"}):this),dt.set(this,null)}static define(e="relative-time",t=customElements){return t.define(e,this),this}get timeZone(){var e;return((e=this.closest("[time-zone]"))===null||e===void 0?void 0:e.getAttribute("time-zone"))||this.ownerDocument.documentElement.getAttribute("time-zone")||void 0}static get observedAttributes(){return["second","minute","hour","weekday","day","month","year","time-zone-name","prefix","threshold","tense","precision","format","format-style","no-title","datetime","lang","title","aria-hidden","time-zone"]}get onRelativeTimeUpdated(){return Y(this,dt,"f")}set onRelativeTimeUpdated(e){Y(this,dt,"f")&&this.removeEventListener("relative-time-updated",Y(this,dt,"f")),ni(this,dt,typeof e=="object"||typeof e=="function"?e:null,"f"),typeof e=="function"&&this.addEventListener("relative-time-updated",e)}get second(){const e=this.getAttribute("second");if(e==="numeric"||e==="2-digit")return e}set second(e){this.setAttribute("second",e||"")}get minute(){const e=this.getAttribute("minute");if(e==="numeric"||e==="2-digit")return e}set minute(e){this.setAttribute("minute",e||"")}get hour(){const e=this.getAttribute("hour");if(e==="numeric"||e==="2-digit")return e}set hour(e){this.setAttribute("hour",e||"")}get weekday(){const e=this.getAttribute("weekday");if(e==="long"||e==="short"||e==="narrow")return e;if(this.format==="datetime"&&e!=="")return this.formatStyle}set weekday(e){this.setAttribute("weekday",e||"")}get day(){var e;const t=(e=this.getAttribute("day"))!==null&&e!==void 0?e:"numeric";if(t==="numeric"||t==="2-digit")return t}set day(e){this.setAttribute("day",e||"")}get month(){const e=this.format;let t=this.getAttribute("month");if(t!==""&&(t??(t=e==="datetime"?this.formatStyle:"short"),t==="numeric"||t==="2-digit"||t==="short"||t==="long"||t==="narrow"))return t}set month(e){this.setAttribute("month",e||"")}get year(){var e;const t=this.getAttribute("year");if(t==="numeric"||t==="2-digit")return t;if(!this.hasAttribute("year")&&new Date().getUTCFullYear()!==((e=this.date)===null||e===void 0?void 0:e.getUTCFullYear()))return"numeric"}set year(e){this.setAttribute("year",e||"")}get timeZoneName(){const e=this.getAttribute("time-zone-name");if(e==="long"||e==="short"||e==="shortOffset"||e==="longOffset"||e==="shortGeneric"||e==="longGeneric")return e}set timeZoneName(e){this.setAttribute("time-zone-name",e||"")}get prefix(){var e;return(e=this.getAttribute("prefix"))!==null&&e!==void 0?e:this.format==="datetime"?"":"on"}set prefix(e){this.setAttribute("prefix",e)}get threshold(){const e=this.getAttribute("threshold");return e&&dd(e)?e:"P30D"}set threshold(e){this.setAttribute("threshold",e)}get tense(){const e=this.getAttribute("tense");return e==="past"?"past":e==="future"?"future":"auto"}set tense(e){this.setAttribute("tense",e)}get precision(){const e=this.getAttribute("precision");return Si.includes(e)?e:this.format==="micro"?"minute":"second"}set precision(e){this.setAttribute("precision",e)}get format(){const e=this.getAttribute("format");return e==="datetime"?"datetime":e==="relative"?"relative":e==="duration"?"duration":e==="micro"?"micro":e==="elapsed"?"elapsed":"auto"}set format(e){this.setAttribute("format",e)}get formatStyle(){const e=this.getAttribute("format-style");if(e==="long")return"long";if(e==="short")return"short";if(e==="narrow")return"narrow";const t=this.format;return t==="elapsed"||t==="micro"?"narrow":t==="datetime"?"short":"long"}set formatStyle(e){this.setAttribute("format-style",e)}get noTitle(){return this.hasAttribute("no-title")}set noTitle(e){this.toggleAttribute("no-title",e)}get datetime(){return this.getAttribute("datetime")||""}set datetime(e){this.setAttribute("datetime",e)}get date(){const e=Date.parse(this.datetime);return Number.isNaN(e)?null:new Date(e)}set date(e){this.datetime=e?.toISOString()||""}connectedCallback(){this.update()}disconnectedCallback(){rs.unobserve(this)}attributeChangedCallback(e,t,s){t!==s&&(e==="title"&&ni(this,Lt,s!==null&&(this.date&&Y(this,re,"m",Ns).call(this,this.date))!==s,"f"),!Y(this,Ot,"f")&&!(e==="title"&&Y(this,Lt,"f"))&&ni(this,Ot,(async()=>{await Promise.resolve(),this.update(),ni(this,Ot,!1,"f")})(),"f"))}update(){const e=Y(this,tt,"f").textContent||this.textContent||"",t=this.getAttribute("title")||"";let s=t;const n=this.date;if(typeof Intl>"u"||!Intl.DateTimeFormat||!n){Y(this,tt,"f").textContent=e;return}const o=Date.now();Y(this,Lt,"f")||(s=Y(this,re,"m",Ns).call(this,n)||"",s&&!this.noTitle&&this.setAttribute("title",s));const r=ud(n,this.precision,o),a=Y(this,re,"m",Po).call(this,r);let c=e;const u=Y(this,re,"m",Wo).call(this,a);u?c=Y(this,re,"m",qo).call(this,n):a==="duration"?c=Y(this,re,"m",Bo).call(this,r):a==="relative"?c=Y(this,re,"m",Uo).call(this,r):c=Y(this,re,"m",Ho).call(this,n),c?Y(this,re,"m",Fs).call(this,c):this.shadowRoot===Y(this,tt,"f")&&this.textContent&&Y(this,re,"m",Fs).call(this,this.textContent),(c!==e||s!==t)&&this.dispatchEvent(new fd(e,c,t,s)),(a==="relative"||a==="duration")&&!u?rs.observe(this):rs.unobserve(this)}}Lt=new WeakMap,Ot=new WeakMap,tt=new WeakMap,dt=new WeakMap,re=new WeakSet,lt=function(){var e;const t=((e=this.closest("[lang]"))===null||e===void 0?void 0:e.getAttribute("lang"))||this.ownerDocument.documentElement.getAttribute("lang");try{return new Intl.Locale(t??"").toString()}catch{return"default"}},Ns=function(e){return new Intl.DateTimeFormat(Y(this,re,"a",lt),{day:"numeric",month:"short",year:"numeric",hour:"numeric",minute:"2-digit",timeZoneName:"short",timeZone:this.timeZone}).format(e)},Po=function(e){const t=this.format;if(t==="datetime")return"datetime";if(t==="duration"||t==="elapsed"||t==="micro")return"duration";if((t==="auto"||t==="relative")&&typeof Intl<"u"&&Intl.RelativeTimeFormat){const s=this.tense;if(s==="past"||s==="future"||ve.compare(e,this.threshold)===1)return"relative"}return"datetime"},Bo=function(e){const t=Y(this,re,"a",lt),s=this.format,n=this.formatStyle,o=this.tense;let r=os;s==="micro"?(e=zo(e),r=Nn,e.months===0&&(this.tense==="past"&&e.sign!==-1||this.tense==="future"&&e.sign!==1)&&(e=Nn)):(o==="past"&&e.sign!==-1||o==="future"&&e.sign!==1)&&(e=r);const a=`${this.precision}sDisplay`;return e.blank?r.toLocaleString(t,{style:n,[a]:"always"}):e.abs().toLocaleString(t,{style:n})},Uo=function(e){const t=new Intl.RelativeTimeFormat(Y(this,re,"a",lt),{numeric:"auto",style:this.formatStyle}),s=this.tense;s==="future"&&e.sign!==1&&(e=os),s==="past"&&e.sign!==-1&&(e=os);const[n,o]=hd(e);return o==="second"&&n<10?t.format(0,this.precision==="millisecond"?"second":this.precision):t.format(n,o)},Ho=function(e){const t=new Intl.DateTimeFormat(Y(this,re,"a",lt),{second:this.second,minute:this.minute,hour:this.hour,weekday:this.weekday,day:this.day,month:this.month,year:this.year,timeZoneName:this.timeZoneName,timeZone:this.timeZone});return`${this.prefix} ${t.format(e)}`.trim()},qo=function(e){return new Intl.DateTimeFormat(Y(this,re,"a",lt),{day:"numeric",month:"short",year:"numeric",hour:"numeric",minute:"2-digit",timeZoneName:"short",timeZone:this.timeZone}).format(e)},Fs=function(e){if(this.hasAttribute("aria-hidden")&&this.getAttribute("aria-hidden")==="true"){const t=document.createElement("span");t.setAttribute("aria-hidden","true"),t.textContent=e,Y(this,tt,"f").replaceChildren(t)}else Y(this,tt,"f").textContent=e},Wo=function(e){var t;return e==="duration"?!1:this.ownerDocument.documentElement.getAttribute("data-prefers-absolute-time")==="true"||((t=this.ownerDocument.body)===null||t===void 0?void 0:t.getAttribute("data-prefers-absolute-time"))==="true"};const Ln=typeof globalThis<"u"?globalThis:window;try{Ln.RelativeTimeElement=md.define()}catch(i){if(!(Ln.DOMException&&i instanceof DOMException&&i.name==="NotSupportedError")&&!(i instanceof ReferenceError))throw i}var bd=class extends Ts{static get styles(){return[...super.styles,F`
        .input-group__input {
          font-family: var(--c-font-mono);
          font-size: 0.9em;
        }
      `]}constructor(){super(),this.autocorrect=!1}firstUpdated(e){super.firstUpdated(e),this._inputNode?.setAttribute("autocapitalize","off")}};customElements.get("craft-input-handle")||customElements.define("craft-input-handle",bd),Mr();var On=class extends Hi{static get styles(){return[...super.styles,Gt,F`
        .input-group__container {
          position: relative;
        }

        .input-group__suffix {
          position: absolute;
          inset-inline-end: var(--c-input-spacing-inline);
          inset-block-start: 50%;
          transform: translateY(calc(-50%));
        }
      `]}constructor(){super(),this._visible=!1,this.reveal=()=>{this._visible=!this._visible,this.type=this._visible?"text":"password"},this.renderSuffix=()=>w`
      <craft-button
        type="button"
        icon
        size="small"
        variant="plain"
        @click="${this.reveal}"
        appearance="plain"
      >
        <span class="icon"
          >${this._visible?w`<craft-icon name="eye-slash"></craft-icon>`:w`<craft-icon name="eye"></craft-icon>`}
        </span>
      </craft-button>
    `,this.type="password"}get slots(){return{...super.slots,suffix:()=>({template:this.renderSuffix()})}}};T([he()],On.prototype,"_visible",void 0),customElements.get("craft-input-password")||customElements.define("craft-input-password",On);var gd=F`
  :host {
    display: contents;
  }

  .chip {
    color: var(--c-chip-fg, var(--c-color-neutral-on-faint));
    display: inline-grid;
    grid-template-columns: minmax(0, auto) 1fr minmax(0, auto);
    min-height: var(--c-chip-height, var(--c-size-control-sm));
    min-width: auto;
    border-radius: var(--c-chip-radius, var(--c-radius-md));
    border: var(
      --c-chip-border,
      1px solid var(--c-color-neutral-border-subtle)
    );
    background-color: var(--c-chip-bg, var(--c-color-neutral-bg-faint));
    padding-inline: var(--c-chip-spacing-inline, var(--c-spacing-md));
    padding-block: var(--c-chip-spacing-block, var(--c-spacing-sm));
    align-items: center;
    box-shadow: var(--c-chip-shadow, var(--c-shadow-sm));
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
    justify-content: center;
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
`,oi=class extends H{constructor(...e){super(...e),this.size="",this.variant=""}render(){let e=!!this.querySelector('[slot="prefix"]'),t=!!this.querySelector('[slot="suffix"]');return w`
      <div
        class="${pe({chip:!0,"chip--small":this.size==="small","chip--medium":this.size==="medium","chip--large":this.size==="large","chip--plain":this.variant==="plain"})}"
      >
        ${e?w`<div class="chip__prefix"><slot name="prefix"></slot></div>`:K}
        <div class="chip__body">
          <slot></slot>
        </div>
        ${t?w`<div class="chip__suffix"><slot name="suffix"></slot></div>`:K}
      </div>
    `}};oi.styles=[gd],T([b()],oi.prototype,"size",void 0),T([b()],oi.prototype,"variant",void 0),customElements.get("craft-chip")||customElements.define("craft-chip",oi);var vd=F`
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
    background-color: var(--c-status-live-bg);
    border-color: var(--c-status-live-border);
  }

  .status--enabled {
    background-color: var(--c-status-enabled-bg);
    border-color: var(--c-status-enabled-border);
  }

  .status--pending {
    background-color: var(--c-status-pending-bg);
    border-color: var(--c-status-pending-border);
  }

  .status--expired {
    background-color: var(--c-status-expired-bg);
    border-color: var(--c-status-expired-border);
  }

  .status--disabled {
    background-color: var(--c-status-disabled-bg);
    border: 1px solid var(--c-status-disabled-border);
  }
`,ri=class extends H{constructor(...e){super(...e),this.label=null,this.status=null}getLabel(){return!this.label&&this.status?`Status: ${this.status}`:this.label}render(){return w`
      <span
        class="${pe({status:!0,"status--live":this.status==="live","status--enabled":this.status==="enabled","status--pending":this.status==="pending","status--expired":this.status==="expired","status--disabled":this.status==="disabled"})}"
        role="img"
        aria-label="${this.getLabel()}"
      ></span>
    `}};ri.styles=[vd],T([b()],ri.prototype,"label",void 0),T([b()],ri.prototype,"status",void 0),customElements.get("craft-status")||customElements.define("craft-status",ri);var Vt=new Map;function _d(i){var e=Vt.get(i);e&&e.destroy()}function yd(i){var e=Vt.get(i);e&&e.update()}var It=null;typeof window>"u"?((It=function(i){return i}).destroy=function(i){return i},It.update=function(i){return i}):((It=function(i,e){return i&&Array.prototype.forEach.call(i.length?i:[i],function(t){return(function(s){if(s&&s.nodeName&&s.nodeName==="TEXTAREA"&&!Vt.has(s)){var n,o=null,r=window.getComputedStyle(s),a=(n=s.value,function(){u({testForHeightReduction:n===""||!s.value.startsWith(n),restoreTextAlign:null}),n=s.value}),c=(function(g){s.removeEventListener("autosize:destroy",c),s.removeEventListener("autosize:update",h),s.removeEventListener("input",a),window.removeEventListener("resize",h),Object.keys(g).forEach(function(f){return s.style[f]=g[f]}),Vt.delete(s)}).bind(s,{height:s.style.height,resize:s.style.resize,textAlign:s.style.textAlign,overflowY:s.style.overflowY,overflowX:s.style.overflowX,wordWrap:s.style.wordWrap});s.addEventListener("autosize:destroy",c),s.addEventListener("autosize:update",h),s.addEventListener("input",a),window.addEventListener("resize",h),s.style.overflowX="hidden",s.style.wordWrap="break-word",Vt.set(s,{destroy:c,update:h}),h()}function u(g){var f,m,y=g.restoreTextAlign,_=y===void 0?null:y,x=g.testForHeightReduction,p=x===void 0||x,k=r.overflowY;if(s.scrollHeight!==0&&(r.resize==="vertical"?s.style.resize="none":r.resize==="both"&&(s.style.resize="horizontal"),p&&(f=(function(S){for(var I=[];S&&S.parentNode&&S.parentNode instanceof Element;)S.parentNode.scrollTop&&I.push([S.parentNode,S.parentNode.scrollTop]),S=S.parentNode;return function(){return I.forEach(function(R){var z=R[0],G=R[1];z.style.scrollBehavior="auto",z.scrollTop=G,z.style.scrollBehavior=null})}})(s),s.style.height=""),m=r.boxSizing==="content-box"?s.scrollHeight-(parseFloat(r.paddingTop)+parseFloat(r.paddingBottom)):s.scrollHeight+parseFloat(r.borderTopWidth)+parseFloat(r.borderBottomWidth),r.maxHeight!=="none"&&m>parseFloat(r.maxHeight)?(r.overflowY==="hidden"&&(s.style.overflow="scroll"),m=parseFloat(r.maxHeight)):r.overflowY!=="hidden"&&(s.style.overflow="hidden"),s.style.height=m+"px",_&&(s.style.textAlign=_),f&&f(),o!==m&&(s.dispatchEvent(new Event("autosize:resized",{bubbles:!0})),o=m),k!==r.overflow&&!_)){var C=r.textAlign;r.overflow==="hidden"&&(s.style.textAlign=C==="start"?"end":"start"),u({restoreTextAlign:C,testForHeightReduction:!0})}}function h(){u({testForHeightReduction:!0,restoreTextAlign:null})}})(t)}),i}).destroy=function(i){return i&&Array.prototype.forEach.call(i.length?i:[i],_d),i},It.update=function(i){return i&&Array.prototype.forEach.call(i.length?i:[i],yd),i});var as=It;class wd extends Xt{get _inputNode(){return Array.from(this.children).find(e=>e.slot==="input")}}class xd extends Do(wd){static get properties(){return{maxRows:{type:Number,attribute:"max-rows"},rows:{type:Number,reflect:!0},readOnly:{type:Boolean,attribute:"readonly",reflect:!0},placeholder:{type:String,reflect:!0}}}get slots(){return{...super.slots,input:()=>{const e=document.createElement("textarea");return e.style.resize!==void 0&&(e.style.resize="none"),e}}}constructor(){super(),this.rows=2,this.maxRows=6,this.readOnly=!1,this.placeholder=""}connectedCallback(){super.connectedCallback(),this.__initializeAutoresize(),this.__intersectionObserver=new IntersectionObserver(()=>this.resizeTextarea()),this.__intersectionObserver.observe(this)}updated(e){if(super.updated(e),e.has("name")&&(this._inputNode.name=this.name),e.has("autocomplete")&&(this._inputNode.autocomplete=this.autocomplete),e.has("disabled")&&(this._inputNode.disabled=this.disabled,this.validate()),e.has("rows")){const t=this._inputNode;t&&(t.rows=this.rows)}if(e.has("readOnly")){const t=this._inputNode;t&&(t.readOnly=this.readOnly)}if(e.has("placeholder")){const t=this._inputNode;t&&(t.placeholder=this.placeholder)}e.has("modelValue")&&this.resizeTextarea(),(e.has("maxRows")||e.has("rows"))&&this.setTextareaMaxHeight()}disconnectedCallback(){super.disconnectedCallback(),as.destroy(this._inputNode)}setTextareaMaxHeight(){const{value:e}=this._inputNode;this._inputNode.value="",this.resizeTextarea();const t=window.getComputedStyle(this._inputNode,null),s=parseFloat(t.lineHeight)||parseFloat(t.height)/this.rows,n=parseFloat(t.paddingTop)+parseFloat(t.paddingBottom),o=parseFloat(t.borderTopWidth)+parseFloat(t.borderBottomWidth),r=t.boxSizing==="border-box"?n+o:0;this._inputNode.style.maxHeight=`${s*this.maxRows+r}px`,this._inputNode.value=e,this.resizeTextarea()}static get styles(){return[...super.styles,F`
        .input-group__container > .input-group__input ::slotted(.form-control) {
          box-sizing: content-box;
          overflow-x: hidden; /* for FF adds height to the TextArea to reserve place for scroll-bars */
        }

        /* Workaround https://bugzilla.mozilla.org/show_bug.cgi?id=1739079 */
        :host([disabled]) ::slotted(textarea) {
          user-select: none;
        }
      `]}get updateComplete(){return this.__textareaUpdateComplete?Promise.all([this.__textareaUpdateComplete,super.updateComplete]):super.updateComplete}resizeTextarea(){as.update(this._inputNode)}__initializeAutoresize(){this.__shady_native_contains?this.__textareaUpdateComplete=this.__waitForTextareaRenderedInRealDOM().then(()=>{this.__startAutoresize(),this.__textareaUpdateComplete=null}):this.__startAutoresize()}async __waitForTextareaRenderedInRealDOM(){let e=3;for(;e!==0&&!this.__shady_native_contains(this._inputNode);)await new Promise(t=>setTimeout(t)),e-=1}__startAutoresize(){as(this._inputNode),this.setTextareaMaxHeight()}}var Ed=F`
  :host(:not([label-sr-only])) .form-field__group-one {
    margin-bottom: var(--c-spacing-sm);
  }

  ::slotted(label) {
    font-weight: bold;
  }

  ::slotted([slot='input']) {
    padding-block: var(--c-spacing-md);
    line-height: var(--leading-normal);
  }
`,kd=class extends xd{static get styles(){return[...super.styles,Gt,Ed]}};customElements.get("craft-textarea")||customElements.define("craft-textarea",kd);var Cd=F`
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
`,In=class extends H{render(){return w`<slot></slot>`}};In.styles=[Cd],customElements.get("craft-button-group")||customElements.define("craft-button-group",In);class Sd extends Xt{static get properties(){return{autocomplete:{type:String}}}constructor(){super(),this.autocomplete=void 0}get _inputNode(){return Array.from(this.children).find(e=>e.slot==="input")}}class Ad extends Sd{get operationMode(){return"select"}connectedCallback(){super.connectedCallback(),this._inputNode.addEventListener("change",this._proxyChangeEvent),this.__selectObserver=new MutationObserver(()=>{this._syncValueUpwards(),this._calculateValues({source:"model"})}),this.__selectObserver.observe(this._inputNode,{attributes:!0,childList:!0,subtree:!0})}updated(e){super.updated(e),e.has("disabled")&&(this._inputNode.disabled=this.disabled,this.validate()),e.has("name")&&(this._inputNode.name=this.name),e.has("autocomplete")&&(this._inputNode.autocomplete=this.autocomplete)}disconnectedCallback(){super.disconnectedCallback(),this._inputNode.removeEventListener("change",this._proxyChangeEvent),this.__selectObserver?.disconnect()}formatter(e){const t=Array.from(this._inputNode.options).find(s=>s.value===e);return t?t.text:""}_reflectBackFormattedValueToUser(){this._reflectBackOn()&&(this.value=typeof this.modelValue<"u"?this.modelValue:"")}_proxyChangeEvent(){this.dispatchEvent(new CustomEvent("user-input-changed",{bubbles:!0,composed:!0}))}}var Td=F`
  ${Vi}

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

  .input-group__input {
    ${js}
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
`,Nd=class extends Ad{static get styles(){return[...super.styles,Td]}_inputGroupInputTemplate(){return w`
      <div class="input-group__input">
        <slot name="input"></slot>
        <craft-icon
          class="indicator"
          name="chevron-down"
          style="font-size: 0.8em"
        ></craft-icon>
      </div>
    `}};customElements.get("craft-select")||customElements.define("craft-select",Nd);class Fd extends Hc(H){static get properties(){return{tabIndex:{type:Number,reflect:!0,attribute:"tabindex"}}}constructor(){super(),this.tabIndex=0}connectedCallback(){super.connectedCallback(),this.setAttribute("role","listbox")}createRenderRoot(){return this}}function Dn(i,e){Array.from(i.childNodes).forEach(t=>{t.hasAttribute&&t.hasAttribute("slot")||e.appendChild(t)})}const Ld=i=>class extends rt(Yt(Bi(xt(Xs(i))))){static get properties(){return{orientation:String,selectionFollowsFocus:{type:Boolean,attribute:"selection-follows-focus"},rotateKeyboardNavigation:{type:Boolean,attribute:"rotate-keyboard-navigation"},hasNoDefaultSelected:{type:Boolean,reflect:!0,attribute:"has-no-default-selected"},_noTypeAhead:{type:Boolean}}}static get styles(){return[...super.styles||[],F`
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
        `]}_inputGroupInputTemplate(){return w`
        <div class="input-group__input">
          <slot name="input"></slot>
          <slot id="options-outlet"></slot>
        </div>
      `}static get scopedElements(){return{...super.scopedElements,"lion-options":Fd}}get slots(){return{...super.slots,input:()=>{const t=this.createScopedElement("lion-options");return t.setAttribute("data-tag-name","lion-options"),t.registrationTarget=this,t}}}get _inputNode(){return this.querySelector('[slot="input"]')}get _listboxNode(){return this._inputNode}get _listboxActiveDescendantNode(){return this._listboxNode.querySelector(`#${this._listboxActiveDescendant}`)}get _listboxSlot(){return this.shadowRoot.querySelector("slot[name=input]")}get _scrollTargetNode(){return this._listboxNode}get _activeDescendantOwnerNode(){return this._listboxNode}get activeIndex(){return this.formElements.findIndex(t=>t.active===!0)}set activeIndex(t){if(this.formElements[t]){const s=this.formElements[t];this.__setChildActive(s)}else this.__setChildActive(null)}get checkedIndex(){const t=this.formElements;return this.multipleChoice?t.filter(s=>s.checked).map(s=>t.indexOf(s)):t.indexOf(t.find(s=>s.checked))}set checkedIndex(t){this.setCheckedIndex(t)}constructor(){super(),this.hasNoDefaultSelected=!1,this.orientation="vertical",this.rotateKeyboardNavigation=!1,this.selectionFollowsFocus=!1,this._noTypeAhead=!1,this._typeAheadTimeout=1e3,this._listboxActiveDescendant=null,this.__hasInitialSelectedFormElement=!1,this._repropagationRole="choice-group",this._listboxReceivesNoFocus=!1,this._oldModelValue=void 0,this._listboxOnKeyDown=this._listboxOnKeyDown.bind(this),this._listboxOnClick=this._listboxOnClick.bind(this),this._listboxOnKeyUp=this._listboxOnKeyUp.bind(this),this._onChildActiveChanged=this._onChildActiveChanged.bind(this),this.__proxyChildModelValueChanged=this.__proxyChildModelValueChanged.bind(this),this.__preventScrollingWithArrowKeys=this.__preventScrollingWithArrowKeys.bind(this),this.__typedChars=[]}connectedCallback(){this._listboxNode&&(this._listboxNode.registrationTarget=this),super.connectedCallback(),this._setupListboxNode(),this.__setupEventListeners(),this.registrationComplete.then(()=>{this.__initInteractionStates()})}firstUpdated(t){super.firstUpdated(t),this.__moveOptionsToListboxNode(),this.registrationComplete.then(()=>{this._initialModelValue=this.modelValue}),new MutationObserver(()=>{this._onListboxContentChanged()}).observe(this._listboxNode,{childList:!0})}updated(t){super.updated(t),t.has("disabled")&&(this.disabled?this.__requestOptionsToBeDisabled():this.__retractRequestOptionsToBeDisabled())}disconnectedCallback(){super.disconnectedCallback(),this._teardownListboxNode(),this.__teardownEventListeners()}setCheckedIndex(t){if(this.multipleChoice&&Array.isArray(t)){this._uncheckChildren(this.formElements.filter(s=>s===t)),t.forEach(s=>{this.formElements[s]&&(this.formElements[s].checked=!this.formElements[s].checked)});return}typeof t=="number"&&(t===-1&&this._uncheckChildren(),this.formElements[t]&&(this.formElements[t].disabled?this._uncheckChildren():this.multipleChoice?this.formElements[t].checked=!this.formElements[t].checked:this.formElements[t].checked=!0))}addFormElement(t,s){super.addFormElement(t,s),t.id=t.id||`${this.localName}-option-${Kt()}`,this.disabled&&t.makeRequestToBeDisabled(),this.__setAttributeForAllFormElements("aria-setsize",this.formElements.length),this.formElements.forEach((n,o)=>{n.setAttribute("aria-posinset",o+1)}),this.__proxyChildModelValueChanged({target:t}),this.resetInteractionState()}resetInteractionState(){super.resetInteractionState(),this.submitted=!1}reset(){this.modelValue=this._initialModelValue,this.activeIndex=-1,this.resetInteractionState()}clear(){super.clear(),this.setCheckedIndex(-1),this.resetInteractionState()}_handleTypeAhead(t,{setAsChecked:s}){const{key:n,code:o}=t;if(o.startsWith("Key")||o.startsWith("Digit")||o.startsWith("Numpad")){t.preventDefault(),this.__typedChars.push(n);const r=this.__typedChars.join(""),a=this.formElements.findIndex(c=>c.modelValue.value.toLowerCase().startsWith(r));a>=0&&(s&&this.setCheckedIndex(a),this.activeIndex=a),this.__pendingTypeAheadTimeout&&window.clearTimeout(this.__pendingTypeAheadTimeout),this.__pendingTypeAheadTimeout=setTimeout(()=>{this.__typedChars=[]},this._typeAheadTimeout)}}_getCheckedElements(){return this.formElements.filter(t=>t.checked)}_setupListboxNode(){this._listboxNode?this.__setupListboxNodeInteractions():this._listboxSlot&&this._listboxSlot.addEventListener("slotchange",()=>{this.__setupListboxNodeInteractions()})}_onListboxContentChanged(){}_teardownListboxNode(){this._listboxNode&&(this._listboxNode.removeEventListener("keydown",this._listboxOnKeyDown),this._listboxNode.removeEventListener("click",this._listboxOnClick),this._listboxNode.removeEventListener("keyup",this._listboxOnKeyUp))}_getNextEnabledOption(t,s=1){return this.__getEnabledOption(t,s)}_getPreviousEnabledOption(t,s=-1){return this.__getEnabledOption(t,s)}_onChildActiveChanged({target:t}){t.active===!0&&this.__setChildActive(t)}_listboxOnKeyDown(t){if(this.disabled)return;this._isHandlingUserInput=!0,setTimeout(()=>{this._isHandlingUserInput=!1});const{key:s}=t;switch(s){case" ":case"Enter":{if(s===" "&&this._listboxReceivesNoFocus||(s===" "&&t.preventDefault(),!this.formElements[this.activeIndex])||this.formElements[this.activeIndex].disabled)return;this.formElements[this.activeIndex].href&&this.formElements[this.activeIndex].click(),this.setCheckedIndex(this.activeIndex);break}case"ArrowUp":t.preventDefault(),this.orientation==="vertical"&&(this.activeIndex=this._getPreviousEnabledOption(this.activeIndex));break;case"ArrowLeft":if(this._listboxReceivesNoFocus)return;t.preventDefault(),this.orientation==="horizontal"&&(this.activeIndex=this._getPreviousEnabledOption(this.activeIndex));break;case"ArrowDown":t.preventDefault(),this.orientation==="vertical"&&(this.activeIndex=this._getNextEnabledOption(this.activeIndex));break;case"ArrowRight":if(this._listboxReceivesNoFocus)return;t.preventDefault(),this.orientation==="horizontal"&&(this.activeIndex=this._getNextEnabledOption(this.activeIndex));break;case"Home":if(this._listboxReceivesNoFocus)return;t.preventDefault(),this.activeIndex=this._getNextEnabledOption(0,0);break;case"End":if(this._listboxReceivesNoFocus)return;t.preventDefault(),this.activeIndex=this._getPreviousEnabledOption(this.formElements.length-1,0);break;default:this._noTypeAhead||this._handleTypeAhead(t,{setAsChecked:this.selectionFollowsFocus&&!this.multipleChoice})}["ArrowUp","ArrowDown","ArrowLeft","ArrowRight","Home","End"].includes(s)&&this.selectionFollowsFocus&&!this.multipleChoice&&this.setCheckedIndex(this.activeIndex)}_listboxOnClick(t){}_listboxOnKeyUp(t){if(this.disabled)return;this._isHandlingUserInput=!0,setTimeout(()=>{this._isHandlingUserInput=!1});const{key:s}=t;switch(s){case"ArrowUp":case"ArrowDown":case"Home":case"End":case"Enter":t.preventDefault()}}_onLabelClick(){this._listboxNode.focus()}_scrollIntoView(t,s){t.scrollIntoView({behavior:"smooth",block:"nearest"})}__setupEventListeners(){this._listboxNode.addEventListener("active-changed",this._onChildActiveChanged),this._listboxNode.addEventListener("model-value-changed",this.__proxyChildModelValueChanged)}__teardownEventListeners(){this._listboxNode.removeEventListener("active-changed",this._onChildActiveChanged),this._listboxNode.removeEventListener("model-value-changed",this.__proxyChildModelValueChanged)}__setChildActive(t){if(this.formElements.forEach(s=>{s.active=t===s}),!t){this._activeDescendantOwnerNode.removeAttribute("aria-activedescendant");return}this._activeDescendantOwnerNode.setAttribute("aria-activedescendant",t.id),this._scrollIntoView(t,this._scrollTargetNode)}_uncheckChildren(t=[]){const s=Array.isArray(t)?t:[t];this.formElements.forEach(n=>{s.includes(n)||(n.checked=!1)})}__onChildCheckedChanged(t){const{target:s}=t;t.stopPropagation&&t.stopPropagation(),s.checked&&!this.multipleChoice&&this._uncheckChildren(s)}__setAttributeForAllFormElements(t,s){this.formElements.forEach(n=>{n.setAttribute(t,s)})}__proxyChildModelValueChanged(t){t.stopPropagation&&t.stopPropagation(),this.__onChildCheckedChanged(t),this.requestUpdate("modelValue",this._oldModelValue),t.detail&&t.detail.formPath&&this.dispatchEvent(new CustomEvent("model-value-changed",{detail:{formPath:t.detail.formPath,isTriggeredByUser:t.detail.isTriggeredByUser||this._isHandlingUserInput,element:t.target}})),this._oldModelValue=this.modelValue}__getEnabledOption(t,s){const n=o=>s===1?o<this.formElements.length:o>=0;for(let o=t+s;n(o);o+=s)if(this.formElements[o]&&!this.formElements[o].hasAttribute("aria-hidden"))return o;if(this.rotateKeyboardNavigation){const o=s===-1?this.formElements.length-1:0;for(let r=o;n(r);r+=s)if(this.formElements[r]&&!this.formElements[r].hasAttribute("aria-hidden"))return r}return t}__moveOptionsToListboxNode(){const t=this.shadowRoot.getElementById("options-outlet");t&&(Dn(this,this._listboxNode),t.addEventListener("slotchange",()=>{Dn(this,this._listboxNode)}))}__preventScrollingWithArrowKeys(t){if(this.disabled)return;const{key:s}=t;switch(s){case"ArrowUp":case"ArrowDown":case"Home":case"End":t.preventDefault()}}__setupListboxNodeInteractions(){this._listboxNode.setAttribute("role","listbox"),this._listboxNode.setAttribute("aria-orientation",this.orientation),this._listboxNode.setAttribute("aria-multiselectable",`${this.multipleChoice}`),this._listboxNode.setAttribute("tabindex","0"),this._listboxNode.addEventListener("click",this._listboxOnClick),this._listboxNode.addEventListener("keyup",this._listboxOnKeyUp),this._listboxNode.addEventListener("keydown",this._listboxOnKeyDown),this._scrollTargetNode.addEventListener("keydown",this.__preventScrollingWithArrowKeys)}__requestOptionsToBeDisabled(){this.formElements.forEach(t=>{t.makeRequestToBeDisabled&&t.makeRequestToBeDisabled()})}__retractRequestOptionsToBeDisabled(){this.formElements.forEach(t=>{t.retractRequestToBeDisabled&&t.retractRequestToBeDisabled()})}__initInteractionStates(){this.initInteractionState()}},Od=ee(Ld);class Id extends Od(Ks(Zs(Zt(H)))){get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,focused:this.focused}}get _focusableNode(){return this._inputNode}}class $n extends jt(Ui(Gs(xt(H)))){static get properties(){return{active:{type:Boolean,reflect:!0}}}static get styles(){return[F`
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
      `]}get slots(){return{}}constructor(){super(),this.active=!1,this.__onClick=this.__onClick.bind(this),this.__registerEventListeners()}requestUpdate(e,t,s){super.requestUpdate(e,t,s),e==="active"&&this.active!==t&&this.dispatchEvent(new Event("active-changed",{bubbles:!0}))}updated(e){super.updated(e),e.has("checked")&&this.setAttribute("aria-selected",`${this.checked}`),e.has("disabled")&&this.setAttribute("aria-disabled",`${this.disabled}`)}render(){return w`
      <div class="choice-field__label">
        <slot></slot>
      </div>
    `}connectedCallback(){super.connectedCallback(),this.setAttribute("role","option")}__registerEventListeners(){this.addEventListener("click",this.__onClick)}__unRegisterEventListeners(){this.removeEventListener("click",this.__onClick)}__onClick(){if(this.disabled)return;const e=this._parentFormGroup;this._isHandlingUserInput=!0,e&&e.multipleChoice?(this.checked=!this.checked,this.active=!this.active):(this.checked=!0,this.active=!0),this._isHandlingUserInput=!1}}var Dd=F`
  :host([checked]) {
    background-color: var(--c-color-neutral-bg-emphasis);
    color: var(--c-color-neutral-on-emphasis);
  }

  :host {
    padding-inline: var(--c-spacing-md);
    padding-block: var(--c-spacing-sm);
    font: inherit;
    border-radius: var(--c-radius-sm);
  }

  :host(:hover) {
    background-color: var(--c-color-neutral-bg-normal);
    color: var(--c-color-neutral-on-normal);
  }

  :host([active]) {
    background-color: var(--c-color-neutral-bg-emphasis);
    color: var(--c-color-neutral-on-emphasis);
  }

  :host([checked]) {
    background-color: var(--c-color-neutral-bg-emphasis);
    color: var(--c-color-neutral-on-emphasis);
  }

  :host([disabled]) {
    color: var(--c-color-neutral-on-normal);
  }

  .hint {
    color: color-mix(in srgb, currentColor, transparent 25%);
    align-self: end;
  }

  :host([active]) .hint {
    color: var(--c-color-neutral-on-emphasis);
  }

  .choice-field__label {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    max-width: 100%;
  }
`,Mn=class extends $n{constructor(...e){super(...e),this.hint=null}static get styles(){return[...$n.styles,Dd]}render(){return w`
      <div class="choice-field__label">
        <slot></slot>
        ${this.hint?w`<span class="hint">${this.hint}</span>`:K}
        <slot name="suffix"></slot>
      </div>
    `}};T([b()],Mn.prototype,"hint",void 0),customElements.get("craft-option")||customElements.define("craft-option",Mn);var jo=`@layer wa-utilities {
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
`;var $d=class extends Event{constructor(i){super("wa-select",{bubbles:!0,cancelable:!0,composed:!0}),this.detail=i}};function*Ko(i=document.activeElement){i!=null&&(yield i,"shadowRoot"in i&&i.shadowRoot&&i.shadowRoot.mode!=="closed"&&(yield*Ko(i.shadowRoot.activeElement)))}var Md=`:host {
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
`,ls=new Set,ce=class extends fe{constructor(){super(...arguments),this.submenuCleanups=new Map,this.localize=new vt(this),this.userTypedQuery="",this.openSubmenuStack=[],this.open=!1,this.size="medium",this.placement="bottom-start",this.distance=0,this.skidding=0,this.handleDocumentKeyDown=async i=>{const e=this.localize.dir()==="rtl";if(i.key==="Escape"){const h=this.getTrigger();i.preventDefault(),i.stopPropagation(),this.open=!1,h?.focus();return}const t=[...Ko()].find(h=>h.localName==="wa-dropdown-item"),s=t?.localName==="wa-dropdown-item",n=this.getCurrentSubmenuItem(),o=!!n;let r,a,c;o?(r=this.getSubmenuItems(n),a=r.find(h=>h.active||h===t),c=a?r.indexOf(a):-1):(r=this.getItems(),a=r.find(h=>h.active||h===t),c=a?r.indexOf(a):-1);let u;if(i.key==="ArrowUp"&&(i.preventDefault(),i.stopPropagation(),c>0?u=r[c-1]:u=r[r.length-1]),i.key==="ArrowDown"&&(i.preventDefault(),i.stopPropagation(),c!==-1&&c<r.length-1?u=r[c+1]:u=r[0]),i.key===(e?"ArrowLeft":"ArrowRight")&&s&&a&&a.hasSubmenu){i.preventDefault(),i.stopPropagation(),a.submenuOpen=!0,this.addToSubmenuStack(a),setTimeout(()=>{const h=this.getSubmenuItems(a);h.length>0&&(h.forEach((g,f)=>g.active=f===0),h[0].focus())},0);return}if(i.key===(e?"ArrowRight":"ArrowLeft")&&o){i.preventDefault(),i.stopPropagation();const h=this.removeFromSubmenuStack();h&&(h.submenuOpen=!1,setTimeout(()=>{h.focus(),h.active=!0,(h.slot==="submenu"?this.getSubmenuItems(h.parentElement):this.getItems()).forEach(f=>{f!==h&&(f.active=!1)})},0));return}if((i.key==="Home"||i.key==="End")&&(i.preventDefault(),i.stopPropagation(),u=i.key==="Home"?r[0]:r[r.length-1]),i.key==="Tab"&&await this.hideMenu(),i.key.length===1&&!(i.metaKey||i.ctrlKey||i.altKey)&&!(i.key===" "&&this.userTypedQuery==="")&&(clearTimeout(this.userTypedTimeout),this.userTypedTimeout=setTimeout(()=>{this.userTypedQuery=""},1e3),this.userTypedQuery+=i.key,r.some(h=>{const g=(h.textContent||"").trim().toLowerCase(),f=this.userTypedQuery.trim().toLowerCase();return g.startsWith(f)?(u=h,!0):!1})),u){i.preventDefault(),i.stopPropagation(),r.forEach(h=>h.active=h===u),u.focus();return}(i.key==="Enter"||i.key===" "&&this.userTypedQuery==="")&&s&&a&&(i.preventDefault(),i.stopPropagation(),a.hasSubmenu?(a.submenuOpen=!0,this.addToSubmenuStack(a),setTimeout(()=>{const h=this.getSubmenuItems(a);h.length>0&&(h.forEach((g,f)=>g.active=f===0),h[0].focus())},0)):this.makeSelection(a))},this.handleDocumentPointerDown=i=>{i.composedPath().some(s=>s instanceof HTMLElement?s===this||s.closest('wa-dropdown, [part="submenu"]'):!1)||(this.open=!1)},this.handleGlobalMouseMove=i=>{const e=this.getCurrentSubmenuItem();if(!e?.submenuOpen||!e.submenuElement)return;const t=e.submenuElement.getBoundingClientRect(),s=this.localize.dir()==="rtl",n=s?t.right:t.left,o=s?Math.max(i.clientX,n):Math.min(i.clientX,n),r=Math.max(t.top,Math.min(i.clientY,t.bottom));e.submenuElement.style.setProperty("--safe-triangle-cursor-x",`${o}px`),e.submenuElement.style.setProperty("--safe-triangle-cursor-y",`${r}px`);const a=e.matches(":hover"),c=e.submenuElement?.matches(":hover")||!!i.composedPath().find(u=>u instanceof HTMLElement&&u.closest('[part="submenu"]')===e.submenuElement);!a&&!c&&setTimeout(()=>{!e.matches(":hover")&&!e.submenuElement?.matches(":hover")&&(e.submenuOpen=!1)},100)}}disconnectedCallback(){super.disconnectedCallback(),clearInterval(this.userTypedTimeout),this.closeAllSubmenus(),this.submenuCleanups.forEach(i=>i()),this.submenuCleanups.clear(),document.removeEventListener("mousemove",this.handleGlobalMouseMove)}firstUpdated(){this.syncAriaAttributes()}async updated(i){i.has("open")&&(this.customStates.set("open",this.open),this.open?await this.showMenu():(this.closeAllSubmenus(),await this.hideMenu())),i.has("size")&&this.syncItemSizes()}getItems(i=!1){const e=this.defaultSlot.assignedElements({flatten:!0}).filter(t=>t.localName==="wa-dropdown-item");return i?e:e.filter(t=>!t.disabled)}getSubmenuItems(i,e=!1){const t=i.shadowRoot?.querySelector('slot[name="submenu"]')||i.querySelector('slot[name="submenu"]');if(!t)return[];const s=t.assignedElements({flatten:!0}).filter(n=>n.localName==="wa-dropdown-item");return e?s:s.filter(n=>!n.disabled)}syncItemSizes(){this.defaultSlot.assignedElements({flatten:!0}).filter(e=>e.localName==="wa-dropdown-item").forEach(e=>e.size=this.size)}addToSubmenuStack(i){const e=this.openSubmenuStack.indexOf(i);e!==-1?this.openSubmenuStack=this.openSubmenuStack.slice(0,e+1):this.openSubmenuStack.push(i)}removeFromSubmenuStack(){return this.openSubmenuStack.pop()}getCurrentSubmenuItem(){return this.openSubmenuStack.length>0?this.openSubmenuStack[this.openSubmenuStack.length-1]:void 0}closeAllSubmenus(){this.getItems(!0).forEach(e=>{e.submenuOpen=!1}),this.openSubmenuStack=[]}closeSiblingSubmenus(i){const e=i.closest('wa-dropdown-item:not([slot="submenu"])');let t;e?t=this.getSubmenuItems(e,!0):t=this.getItems(!0),t.forEach(s=>{s!==i&&s.submenuOpen&&(s.submenuOpen=!1)}),this.openSubmenuStack.includes(i)||this.openSubmenuStack.push(i)}getTrigger(){return this.querySelector('[slot="trigger"]')}async showMenu(){if(!this.getTrigger())return;const e=new Wt;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}ls.forEach(s=>s.open=!1),this.popup.active=!0,this.open=!0,ls.add(this),this.syncAriaAttributes(),document.addEventListener("keydown",this.handleDocumentKeyDown),document.addEventListener("pointerdown",this.handleDocumentPointerDown),document.addEventListener("mousemove",this.handleGlobalMouseMove),this.menu.classList.remove("hide"),await le(this.menu,"show");const t=this.getItems();t.length>0&&(t.forEach((s,n)=>s.active=n===0),t[0].focus()),this.dispatchEvent(new Ht)}async hideMenu(){const i=new qt({source:this});if(this.dispatchEvent(i),i.defaultPrevented){this.open=!0;return}this.open=!1,ls.delete(this),this.syncAriaAttributes(),document.removeEventListener("keydown",this.handleDocumentKeyDown),document.removeEventListener("pointerdown",this.handleDocumentPointerDown),document.removeEventListener("mousemove",this.handleGlobalMouseMove),this.menu.classList.remove("show"),await le(this.menu,"hide"),this.popup.active=this.open,this.dispatchEvent(new Ut)}handleMenuClick(i){const e=i.target.closest("wa-dropdown-item");if(!(!e||e.disabled)){if(e.hasSubmenu){e.submenuOpen||(this.closeSiblingSubmenus(e),this.addToSubmenuStack(e),e.submenuOpen=!0),i.stopPropagation();return}this.makeSelection(e)}}async handleMenuSlotChange(){const i=this.getItems(!0);await Promise.all(i.map(s=>s.updateComplete)),this.syncItemSizes();const e=i.some(s=>s.type==="checkbox"),t=i.some(s=>s.hasSubmenu);i.forEach((s,n)=>{s.active=n===0,s.checkboxAdjacent=e,s.submenuAdjacent=t})}handleTriggerClick(){this.open=!this.open}handleSubmenuOpening(i){const e=i.detail.item;this.closeSiblingSubmenus(e),this.addToSubmenuStack(e),this.setupSubmenuPosition(e),this.processSubmenuItems(e)}setupSubmenuPosition(i){if(!i.submenuElement)return;this.cleanupSubmenuPosition(i);const e=ho(i,i.submenuElement,()=>{this.positionSubmenu(i),this.updateSafeTriangleCoordinates(i)});this.submenuCleanups.set(i,e);const t=i.submenuElement.querySelector('slot[name="submenu"]');t&&(t.removeEventListener("slotchange",ce.handleSubmenuSlotChange),t.addEventListener("slotchange",ce.handleSubmenuSlotChange),ce.handleSubmenuSlotChange({target:t}))}static handleSubmenuSlotChange(i){const e=i.target;if(!e)return;const t=e.assignedElements().filter(o=>o.localName==="wa-dropdown-item");if(t.length===0)return;const s=t.some(o=>o.hasSubmenu),n=t.some(o=>o.type==="checkbox");t.forEach(o=>{o.submenuAdjacent=s,o.checkboxAdjacent=n})}processSubmenuItems(i){if(!i.submenuElement)return;const e=this.getSubmenuItems(i,!0),t=e.some(s=>s.hasSubmenu);e.forEach(s=>{s.submenuAdjacent=t})}cleanupSubmenuPosition(i){const e=this.submenuCleanups.get(i);e&&(e(),this.submenuCleanups.delete(i))}positionSubmenu(i){if(!i.submenuElement)return;const t=this.localize.dir()==="rtl"?"left-start":"right-start";bo(i,i.submenuElement,{placement:t,middleware:[po({mainAxis:0,crossAxis:-5}),mo({fallbackStrategy:"bestFit"}),fo({padding:8})]}).then(({x:s,y:n,placement:o})=>{i.submenuElement.setAttribute("data-placement",o),Object.assign(i.submenuElement.style,{left:`${s}px`,top:`${n}px`})})}updateSafeTriangleCoordinates(i){if(!i.submenuElement||!i.submenuOpen)return;if(document.activeElement?.matches(":focus-visible")){i.submenuElement.style.setProperty("--safe-triangle-visible","none");return}i.submenuElement.style.setProperty("--safe-triangle-visible","block");const t=i.submenuElement.getBoundingClientRect(),s=this.localize.dir()==="rtl";i.submenuElement.style.setProperty("--safe-triangle-submenu-start-x",`${s?t.right:t.left}px`),i.submenuElement.style.setProperty("--safe-triangle-submenu-start-y",`${t.top}px`),i.submenuElement.style.setProperty("--safe-triangle-submenu-end-x",`${s?t.right:t.left}px`),i.submenuElement.style.setProperty("--safe-triangle-submenu-end-y",`${t.bottom}px`)}makeSelection(i){const e=this.getTrigger();if(i.disabled)return;i.type==="checkbox"&&(i.checked=!i.checked);const t=new $d({item:i});this.dispatchEvent(t),t.defaultPrevented||(this.open=!1,e?.focus())}async syncAriaAttributes(){const i=this.getTrigger();let e;i&&(i.localName==="wa-button"?(await customElements.whenDefined("wa-button"),await i.updateComplete,e=i.shadowRoot.querySelector('[part="base"]')):e=i,e.hasAttribute("id")||e.setAttribute("id",Ws("wa-dropdown-trigger-")),e.setAttribute("aria-haspopup","menu"),e.setAttribute("aria-expanded",this.open?"true":"false"),this.menu.setAttribute("aria-expanded","false"))}render(){let i=this.hasUpdated?this.popup.active:this.open;return w`
      <wa-popup
        placement=${this.placement}
        distance=${this.distance}
        skidding=${this.skidding}
        ?active=${i}
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
    `}};ce.css=[jo,Md];v([X("slot:not([name])")],ce.prototype,"defaultSlot",2);v([X("#menu")],ce.prototype,"menu",2);v([X("wa-popup")],ce.prototype,"popup",2);v([b({type:Boolean,reflect:!0})],ce.prototype,"open",2);v([b({reflect:!0})],ce.prototype,"size",2);v([b({reflect:!0})],ce.prototype,"placement",2);v([b({type:Number})],ce.prototype,"distance",2);v([b({type:Number})],ce.prototype,"skidding",2);ce=v([Fe("wa-dropdown")],ce);var qi=class{constructor(i,...e){this.slotNames=[],this.handleSlotChange=t=>{const s=t.target;(this.slotNames.includes("[default]")&&!s.name||s.name&&this.slotNames.includes(s.name))&&this.host.requestUpdate()},(this.host=i).addController(this),this.slotNames=e}hasDefaultSlot(){return[...this.host.childNodes].some(i=>{if(i.nodeType===Node.TEXT_NODE&&i.textContent.trim()!=="")return!0;if(i.nodeType===Node.ELEMENT_NODE){const e=i;if(e.tagName.toLowerCase()==="wa-visually-hidden")return!1;if(!e.hasAttribute("slot"))return!0}return!1})}hasNamedSlot(i){return this.host.querySelector(`:scope > [slot="${i}"]`)!==null}test(i){return i==="[default]"?this.hasDefaultSlot():this.hasNamedSlot(i)}hostConnected(){this.host.shadowRoot.addEventListener("slotchange",this.handleSlotChange)}hostDisconnected(){this.host.shadowRoot.removeEventListener("slotchange",this.handleSlotChange)}};var Vd=`:host {
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
`,ae=class extends fe{constructor(){super(...arguments),this.hasSlotController=new qi(this,"[default]","start","end"),this.active=!1,this.variant="default",this.size="medium",this.checkboxAdjacent=!1,this.submenuAdjacent=!1,this.type="normal",this.checked=!1,this.disabled=!1,this.submenuOpen=!1,this.hasSubmenu=!1,this.handleSlotChange=()=>{this.hasSubmenu=this.hasSlotController.test("submenu"),this.updateHasSubmenuState(),this.hasSubmenu?(this.setAttribute("aria-haspopup","menu"),this.setAttribute("aria-expanded",this.submenuOpen?"true":"false")):(this.removeAttribute("aria-haspopup"),this.removeAttribute("aria-expanded"))}}connectedCallback(){super.connectedCallback(),this.addEventListener("mouseenter",this.handleMouseEnter.bind(this)),this.shadowRoot.addEventListener("slotchange",this.handleSlotChange)}disconnectedCallback(){super.disconnectedCallback(),this.closeSubmenu(),this.removeEventListener("mouseenter",this.handleMouseEnter),this.shadowRoot.removeEventListener("slotchange",this.handleSlotChange)}firstUpdated(){this.setAttribute("tabindex","-1"),this.hasSubmenu=this.hasSlotController.test("submenu"),this.updateHasSubmenuState()}updated(i){i.has("active")&&(this.setAttribute("tabindex",this.active?"0":"-1"),this.customStates.set("active",this.active)),i.has("checked")&&(this.setAttribute("aria-checked",this.checked?"true":"false"),this.customStates.set("checked",this.checked)),i.has("disabled")&&(this.setAttribute("aria-disabled",this.disabled?"true":"false"),this.customStates.set("disabled",this.disabled)),i.has("type")&&(this.type==="checkbox"?this.setAttribute("role","menuitemcheckbox"):this.setAttribute("role","menuitem")),i.has("submenuOpen")&&(this.customStates.set("submenu-open",this.submenuOpen),this.submenuOpen?this.openSubmenu():this.closeSubmenu())}updateHasSubmenuState(){this.customStates.set("has-submenu",this.hasSubmenu)}async openSubmenu(){!this.hasSubmenu||!this.submenuElement||(this.notifyParentOfOpening(),this.submenuElement.showPopover(),this.submenuElement.hidden=!1,this.submenuElement.setAttribute("data-visible",""),this.submenuOpen=!0,this.setAttribute("aria-expanded","true"),await le(this.submenuElement,"show"),setTimeout(()=>{const i=this.getSubmenuItems();i.length>0&&(i.forEach((e,t)=>e.active=t===0),i[0].focus())},0))}notifyParentOfOpening(){const i=new CustomEvent("submenu-opening",{bubbles:!0,composed:!0,detail:{item:this}});this.dispatchEvent(i);const e=this.parentElement;e&&[...e.children].filter(s=>s!==this&&s.localName==="wa-dropdown-item"&&s.getAttribute("slot")===this.getAttribute("slot")&&s.submenuOpen).forEach(s=>{s.submenuOpen=!1})}async closeSubmenu(){!this.hasSubmenu||!this.submenuElement||(this.submenuOpen=!1,this.setAttribute("aria-expanded","false"),this.submenuElement.hidden||(await le(this.submenuElement,"hide"),this.submenuElement.hidden=!0,this.submenuElement.removeAttribute("data-visible"),this.submenuElement.hidePopover()))}getSubmenuItems(){return[...this.children].filter(i=>i.localName==="wa-dropdown-item"&&i.getAttribute("slot")==="submenu"&&!i.hasAttribute("disabled"))}handleMouseEnter(){this.hasSubmenu&&!this.disabled&&(this.notifyParentOfOpening(),this.submenuOpen=!0)}render(){return w`
      ${this.type==="checkbox"?w`
            <wa-icon
              id="check"
              part="checkmark"
              exportparts="svg:checkmark__svg"
              library="system"
              name="check"
            ></wa-icon>
          `:""}

      <span id="icon" part="icon">
        <slot name="icon"></slot>
      </span>

      <span id="label" part="label">
        <slot></slot>
      </span>

      <span id="details" part="details">
        <slot name="details"></slot>
      </span>

      ${this.hasSubmenu?w`
            <wa-icon
              id="submenu-indicator"
              part="submenu-icon"
              exportparts="svg:submenu-icon__svg"
              library="system"
              name="chevron-right"
            ></wa-icon>
          `:""}
      ${this.hasSubmenu?w`
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
          `:""}
    `}};ae.css=Vd;v([X("#submenu")],ae.prototype,"submenuElement",2);v([b({type:Boolean})],ae.prototype,"active",2);v([b({reflect:!0})],ae.prototype,"variant",2);v([b({reflect:!0})],ae.prototype,"size",2);v([b({attribute:"checkbox-adjacent",type:Boolean,reflect:!0})],ae.prototype,"checkboxAdjacent",2);v([b({attribute:"submenu-adjacent",type:Boolean,reflect:!0})],ae.prototype,"submenuAdjacent",2);v([b()],ae.prototype,"value",2);v([b({reflect:!0})],ae.prototype,"type",2);v([b({type:Boolean})],ae.prototype,"checked",2);v([b({type:Boolean,reflect:!0})],ae.prototype,"disabled",2);v([b({type:Boolean,reflect:!0})],ae.prototype,"submenuOpen",2);v([he()],ae.prototype,"hasSubmenu",2);ae=v([Fe("wa-dropdown-item")],ae);var Rd=class extends ce{static get styles(){return[ce.styles,F`
        :host {
          --wa-border-style: solid;
          --wa-border-width-s: 1px;
          --wa-color-surface-raised: var(--c-bg-raised);
          --wa-color-surface-border: var(--c-border-subtle);
          --wa-border-radius-m: var(--c-radius-lg);
        }

        #menu {
          gap: 1px;
        }
      `]}},zd=class extends ae{static get styles(){return[ae.styles,F`
        @layer components.dropdown-item {
          :host {
            --wa-font-weight-action: 400;
            --wa-space-s: var(--c-spacing-sm);
            --wa-color-neutral-fill-normal: var(--c-color-neutral-bg-subtle);
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            border-radius: var(--c-radius-sm);
            padding-block: calc(var(--c-spacing, 0.25rem) * 1);
            padding-inline: calc(var(--c-spacing, 0.25rem) * 2.5);
          }
        }
      `]}};customElements.get("craft-dropdown")||customElements.define("craft-dropdown",Rd),customElements.get("craft-dropdown-item")||customElements.define("craft-dropdown-item",zd);function Pd({el:i,uid:e}){i.setAttribute("id",`panel-${e}`),i.setAttribute("role","tabpanel"),i.setAttribute("aria-labelledby",`button-${e}`),i.hasAttribute("tabindex")||i.setAttribute("tabindex","0")}function Bd(i){i.setAttribute("selected","true")}function Vn(i){i.removeAttribute("selected")}function Ud({el:i,uid:e,clickHandler:t,keydownHandler:s,keyupHandler:n}){i.setAttribute("id",`button-${e}`),i.setAttribute("role","tab"),i.setAttribute("aria-controls",`panel-${e}`),i.addEventListener("click",t),i.addEventListener("keyup",n),i.addEventListener("keydown",s)}function Hd({el:i,clickHandler:e,keydownHandler:t,keyupHandler:s}){i.removeAttribute("id"),i.removeAttribute("role"),i.removeAttribute("aria-controls"),i.removeEventListener("click",e),i.removeEventListener("keyup",s),i.removeEventListener("keydown",t)}function qd(i,e=!1){e&&i.focus(),i.setAttribute("selected","true"),i.setAttribute("aria-selected","true"),i.setAttribute("tabindex","0")}function Rn(i){i.removeAttribute("selected"),i.setAttribute("aria-selected","false"),i.setAttribute("tabindex","-1")}function Wd(i){const e=i;switch(e.key){case"ArrowDown":case"ArrowRight":case"ArrowUp":case"ArrowLeft":case"Home":case"End":e.preventDefault()}}class jd extends H{static get properties(){return{selectedIndex:{type:Number,attribute:"selected-index",reflect:!0}}}static get styles(){return[F`
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
      `]}render(){return w`
      <div class="tabs__tab-group" role="tablist">
        <slot name="tab"></slot>
      </div>
      <div class="tabs__panels">
        <slot name="panel"></slot>
      </div>
    `}constructor(){super(),this.selectedIndex=0}firstUpdated(e){super.firstUpdated(e),this.__setupSlots(),this.tabs[0]?.disabled&&(this.selectedIndex=this.tabs.findIndex(t=>!t.disabled))}get tabs(){return Array.from(this.children).filter(e=>e.slot==="tab")}get panels(){return Array.from(this.children).filter(e=>e.slot==="panel")}static enabledWarnings=super.enabledWarnings?.filter(e=>e!=="change-in-update")||[];__setupSlots(){if(this.shadowRoot){const e=this.shadowRoot.querySelector("slot[name=tab]"),t=()=>{this.__cleanStore(),this.__setupStore(),this.__updateSelected(!1)};e&&e.addEventListener("slotchange",t)}}__setupStore(){this.__store=[],this.tabs.length!==this.panels.length&&console.warn(`The amount of tabs (${this.tabs.length}) doesn't match the amount of panels (${this.panels.length}).`),this.tabs.forEach((e,t)=>{const s=Kt(),n=this.panels[t],o={uid:s,el:e,button:e,panel:n,clickHandler:this.__createButtonClickHandler(t),keydownHandler:Wd.bind(this),keyupHandler:this.__handleButtonKeyup.bind(this)};Pd({...o,el:o.panel}),Ud(o),Vn(o.panel),Rn(o.button),this.__store&&this.__store.push(o)})}__cleanStore(){this.__store&&(this.__store.forEach(e=>{Hd(e)}),this.__store=[])}__getNextNotDisabledTab(e,t,s){let n=[];const o=e.filter((a,c)=>!a.disabled&&c>this.selectedIndex),r=e.filter((a,c)=>!a.disabled&&c<this.selectedIndex);return s==="right"?n=[...o,...r]:n=[...r.reverse(),...o.reverse()],n[0]}__getNextAvailableIndex(e,t){const s=this.tabs[this.selectedIndex];if(this.tabs.every(n=>!n.disabled))return e;if(t==="ArrowRight"||t==="ArrowDown"){const n=this.__getNextNotDisabledTab(this.tabs,s,"right");return this.tabs.findIndex(o=>n===o)}if(t==="ArrowLeft"||t==="ArrowUp"){const n=this.__getNextNotDisabledTab(this.tabs,s,"left");return this.tabs.findIndex(o=>n===o)}if(t==="Home")return this.tabs.findIndex(n=>!n.disabled);if(t==="End"){const n=this.tabs.map((o,r)=>({disabled:o.disabled,index:r})).filter(o=>!o.disabled);return n[n.length-1].index}return-1}__createButtonClickHandler(e){return()=>{this._setSelectedIndexWithFocus(e)}}__handleButtonKeyup(e){const t=e;if(typeof this.selectedIndex=="number")switch(t.key){case"ArrowDown":case"ArrowRight":this.selectedIndex+1>=this._pairCount?this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(0,t.key)):this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this.selectedIndex+1,t.key));break;case"ArrowUp":case"ArrowLeft":this.selectedIndex<=0?this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this._pairCount-1,t.key)):this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this.selectedIndex-1,t.key));break;case"Home":this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(0,t.key));break;case"End":this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this._pairCount-1,t.key));break}}get selectedIndex(){return this.__selectedIndex||0}set selectedIndex(e){if(e===this.__selectedIndex)return;const t=this.__selectedIndex;this.__selectedIndex=e,this.__updateSelected(!1),this.dispatchEvent(new Event("selected-changed")),this.requestUpdate("selectedIndex",t)}_setSelectedIndexWithFocus(e){if(e===-1)return;const t=this.__selectedIndex;this.__selectedIndex=e,this.__updateSelected(!0),this.dispatchEvent(new Event("selected-changed")),this.requestUpdate("selectedIndex",t)}get _pairCount(){return this.__store&&this.__store.length||0}__updateSelected(e=!1){if(!(this.__store&&typeof this.selectedIndex=="number"&&this.__store[this.selectedIndex]))return;const t=this.tabs.find(r=>r.hasAttribute("selected")),s=this.panels.find(r=>r.hasAttribute("selected"));t&&Rn(t),s&&Vn(s);const{button:n,panel:o}=this.__store[this.selectedIndex];n&&qd(n,e),o&&Bd(o)}}var Kd=F`
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
    border-bottom: 1px solid var(--c-tabs-border-end, var(--c-border-subtle));
  }
`,Gd=class extends jd{static get styles(){return[...super.styles,Kd]}};customElements.get("craft-tabs")||customElements.define("craft-tabs",Gd);var Yd=F`
  :host {
    display: block;
  }

  .card {
    //--c-card-fg: var(--c-card-fg, var(--c-color-neutral-on-faint));
    //--c-card-border: var(--c-card-border, var(--c-color-neutral-border-subtle));
    //--c-card-bg: var(--c-card-bg, var(--c-color-neutral-bg-faint));
    //--c-card-bars-bg: var(--c-card-bars-bg, var(--c-color-neutral-bg-subtle));
    //--c-card-bars-fg: var(--c-card-bars-fg, var(--c-color-neutral-on-subtle));
    color: var(--c-card-color, var(--c-color-neutral-on-faint));
    background-color: var(--c-card-bg, var(--c-color-neutral-bg-faint));
    border: 1px solid var(--c-card-border, var(--c-color-neutral-border-subtle));
    border-radius: var(--c-card-radius, var(--c-radius-md));
    //overflow: hidden;
    box-shadow: var(--c-card-shadow, var(--c-shadow-sm));
    position: relative;
  }

  .card__header,
  .card__footer {
    font-size: 0.875em;
    padding-block: var(--c-card-padding-block, var(--c-spacing-sm));
    padding-inline-start: var(--c-card-padding-inline, var(--c-spacing-md));
    padding-inline-end: var(--c-card-padding-inline, var(--c-spacing-sm));
    color: var(--c-card-bars-fg, var(--c-color-neutral-on-subtle));
    background-color: var(--c-card-bars-bg, var(--c-color-neutral-bg-subtle));
    border-width: 0;
    border-color: var(--c-card-border, var(--c-color-neutral-border-subtle));
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
`,cs=class extends H{constructor(...e){super(...e),this.label=""}render(){let e=!!this.label||!!this.querySelector('[slot="header"]')||!!this.querySelector('[slot="label"]')||!!this.querySelector('[slot="actions"]'),t=!!this.querySelector('[slot="footer"]');return w`
      <div class="card">
        <div>
          ${e?w`<div class="card__header">
                <slot name="header">
                  <slot name="label" class="card__label" part="label"
                    >${this.label}</slot
                  >
                  <slot name="actions"></slot>
                </slot>
              </div>`:K}

          <div class="card__body">
            <slot></slot>
          </div>

          ${t?w`<div class="card__footer"><slot name="footer"></slot></div>`:K}
        </div>
      </div>
    `}};cs.styles=[Yd],T([b()],cs.prototype,"label",void 0),customElements.get("craft-card")||customElements.define("craft-card",cs);var Zd=F`
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
        var(--c-color-accent-border-emphasis)
      );
    }
  }
`,zn=class extends H{render(){return w`<slot></slot> `}};zn.styles=[Zd],customElements.get("craft-tab")||customElements.define("craft-tab",zn);class Go extends wo(H){static get properties(){return{checked:{type:Boolean,reflect:!0}}}static get styles(){return[F`
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
      `]}render(){return w`
      <div class="btn">
        <div class="switch-button__track"></div>
        <div class="switch-button__thumb"></div>
      </div>
    `}constructor(){super(),this.value="",this.checked=!1,this.__initialized=!1,this._toggleChecked=this._toggleChecked.bind(this),this.__handleKeydown=this._handleKeydown.bind(this),this.__handleKeyup=this._handleKeyup.bind(this)}connectedCallback(){super.connectedCallback(),this.setAttribute("role","switch"),this.setAttribute("aria-checked",`${this.checked}`),this.addEventListener("click",this._toggleChecked),this.addEventListener("keydown",this.__handleKeydown),this.addEventListener("keyup",this.__handleKeyup)}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener("click",this._toggleChecked),this.removeEventListener("keydown",this.__handleKeydown),this.removeEventListener("keyup",this.__handleKeyup)}_toggleChecked(){this.disabled||(this.focus(),this.checked=!this.checked)}__checkedStateChange(){this.dispatchEvent(new Event("checked-changed",{bubbles:!0})),this.setAttribute("aria-checked",`${this.checked}`)}_handleKeydown(e){e.key===" "&&e.preventDefault()}_handleKeyup(e){[" ","Enter"].includes(e.key)&&this._toggleChecked()}updated(e){super.updated(e),e.has("disabled")&&this.setAttribute("aria-disabled",`${this.disabled}`)}requestUpdate(e,t,s){super.requestUpdate(e,t,s),this.__initialized&&this.isConnected&&e==="checked"&&this.checked!==t&&!this.disabled&&this.__checkedStateChange()}firstUpdated(e){super.firstUpdated(e),this.__initialized=!0}}class Xd extends Yt(Ui(Xt)){static get styles(){return[...super.styles,F`
        :host([hidden]) {
          display: none;
        }

        :host([disabled]) {
          color: #adadad;
        }
      `]}static get scopedElements(){return{...super.scopedElements,"lion-switch-button":Go}}get _inputNode(){return Array.from(this.children).find(e=>e.slot==="input")}get slots(){return{...super.slots,input:()=>{const e=this.createScopedElement("lion-switch-button");return e.setAttribute("data-tag-name","lion-switch-button"),e}}}render(){return w`
      <div class="form-field__group-one">${this._groupOneTemplate()}</div>
      <div class="form-field__group-two">${this._groupTwoTemplate()}</div>
    `}_groupOneTemplate(){return w`${this._labelTemplate()} ${this._helpTextTemplate()} ${this._feedbackTemplate()}`}_groupTwoTemplate(){return w`${this._inputGroupTemplate()}`}constructor(){super(),this.checked=!1,this.__handleButtonSwitchCheckedChanged=this.__handleButtonSwitchCheckedChanged.bind(this)}connectedCallback(){super.connectedCallback(),this.addEventListener("checked-changed",this.__handleButtonSwitchCheckedChanged),this._labelNode&&this._labelNode.addEventListener("click",this._toggleChecked),this._syncButtonSwitch()}disconnectedCallback(){super.disconnectedCallback(),this._inputNode&&this.removeEventListener("checked-changed",this.__handleButtonSwitchCheckedChanged),this._labelNode&&this._labelNode.removeEventListener("click",this._toggleChecked)}updated(e){super.updated(e),e.has("disabled")&&this._syncButtonSwitch()}_toggleChecked(e){e.preventDefault(),super._toggleChecked(e)}_isEmpty(){return!1}__handleButtonSwitchCheckedChanged(e){e.stopPropagation(),this._isHandlingUserInput=!0,this.checked=this._inputNode.checked,this._isHandlingUserInput=!1}_syncButtonSwitch(){this._inputNode.disabled=this.disabled}_onLabelClick(){this.disabled||this._inputNode.focus()}}var Yo=class extends Go{static get styles(){return[...super.styles,F`
        :host {
          --c-switch-height: calc(24rem / 16);
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
          --c-switch-height: var(--c-size-control-sm);
          --c-switch-thumb-offset: 4px;
        }

        .btn {
          width: 100%;
        }

        .switch-button__track {
          --tw-inset-shadow-color: var(--color-slate-300);
          margin-inline: -1px;
          background-color: var(--c-color-neutral-bg-subtle);
          border-radius: var(--c-radius-full);
          border: 1px solid var(--c-form-control-border);
          box-shadow: var(--c-input-shadow);
        }

        .switch-button__thumb {
          height: var(--c-switch-thumb-height);
          width: auto;
          aspect-ratio: 1;
          border-radius: var(--c-radius-full);
          border: 1px solid var(--c-form-control-border);
          background-color: var(--c-switch-thumb-bg, var(--c-bg-raised));
          inset-block-start: calc(var(--c-switch-thumb-offset) / 2);
          inset-inline-start: calc(var(--c-switch-thumb-offset) / 2);
          inset-inline-end: auto;
        }

        :host([checked]) .switch-button__track {
          background-color: var(--c-color-success-bg-emphasis);
        }

        :host([checked]) .switch-button__thumb {
          border: 1px solid var(--c-color-success-border-emphasis);
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
          background-color: var(--c-color-success-border-emphasis);
        }
      `]}};customElements.get("craft-switch-button")||customElements.define("craft-switch-button",Yo);var Qd=F`
  :host {
    flex-direction: column;
    gap: var(--c-spacing-md);
  }

  .input-group {
    display: inline-flex;
  }

  ::slotted(label) {
    font-weight: bold;
  }
`,Jd=class extends Xd{static get styles(){return[...super.styles,Vi,Qd]}get slots(){return{...super.slots,input:()=>{let e=this.createScopedElement("craft-switch-button");return e.setAttribute("data-tag-name","craft-switch-button"),e}}}static get scopedElements(){return{...super.scopedElements,"craft-switch-button":Yo}}};customElements.get("craft-switch")||customElements.define("craft-switch",Jd);var eu=F`
  .breadcrumbs {
    display: flex;
    align-items: center;
  }
`,je=class extends H{constructor(...e){super(...e),this.label="",this.items=[],this.visibleItems=0,this.firstRender=!0}getSeparator(){let e=this.separatorSlot.assignedElements({flatten:!0})[0].cloneNode(!0);return[e,...e.querySelectorAll("[id]")].forEach(t=>t.removeAttribute("id")),e.setAttribute("data-default",""),e.slot="separator",e}calculateBreadcrumbItemsWidth(){this.items=this.breadcrumbsElements.map((e,t)=>{let s=e.offsetWidth;return e.hasAttribute("hidden")&&(e.removeAttribute("hidden"),s=e.offsetWidth,e.setAttribute("hidden","")),{label:e.innerText,href:e.href,value:t.toString(),offsetWidth:s,isVisible:!0}})}async handleSlotChange(){let e=[...this.defaultSlot.assignedElements({flatten:!0})].filter(t=>t.tagName.toLowerCase()==="craft-breadcrumb-item");if(e.forEach((t,s)=>{let n=t.querySelector('[slot="separator"]');n===null?t.append(this.getSeparator()):n.hasAttribute("data-default")&&n.replaceWith(this.getSeparator()),s===e.length-1?t.setAttribute("aria-current","page"):t.removeAttribute("aria-current")}),this.breadcrumbsElements.length===0){this.items=[],this.visibleItems=0;return}await Promise.all(this.breadcrumbsElements.map(t=>t.updateComplete)),this.calculateBreadcrumbItemsWidth(),this.visibleItems=0,this.adjustOverflow()}connectedCallback(){super.connectedCallback(),this.hasAttribute("role")||this.setAttribute("role","navigation"),this.resizeObserver=new ResizeObserver(()=>{if(this.firstRender){this.firstRender=!1;return}this.adjustOverflow()}),this.resizeObserver.observe(this)}adjustOverflow(){let e=this.getBoundingClientRect().width;console.log({availableSpace:e})}disconnectedCallback(){this.resizeObserver?.unobserve(this),super.disconnectedCallback()}render(){return w`
      <nav class="breadcrumbs" aria-label="${this.label}">
        <slot @slotchange="${this.handleSlotChange}"></slot>
      </nav>

      <span hidden aria-hidden="true">
        <slot name="separator"><span class="separator">/</span></slot>
      </span>
    `}};je.styles=[eu],T([X("slot")],je.prototype,"defaultSlot",void 0),T([X('slot[name="separator"]')],je.prototype,"separatorSlot",void 0),T([ci({selector:"craft-breadcrumb-item"})],je.prototype,"breadcrumbsElements",void 0),T([b()],je.prototype,"label",void 0),T([he()],je.prototype,"items",void 0),T([he()],je.prototype,"visibleItems",void 0),customElements.get("craft-breadcrumbs")||customElements.define("craft-breadcrumbs",je);var tu=`:host {
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
`,Te=class extends fe{constructor(){super(...arguments),this.renderType="button",this.rel="noreferrer noopener"}setRenderType(){const i=this.defaultSlot.assignedElements({flatten:!0}).filter(e=>e.tagName.toLowerCase()==="wa-dropdown").length>0;if(this.href){this.renderType="link";return}if(i){this.renderType="dropdown";return}this.renderType="button"}hrefChanged(){this.setRenderType()}handleSlotChange(){this.setRenderType()}render(){return w`
      <span part="start" class="start">
        <slot name="start"></slot>
      </span>

      ${this.renderType==="link"?w`
            <a
              part="label"
              class="label label-link"
              href="${this.href}"
              target="${be(this.target?this.target:void 0)}"
              rel=${be(this.target?this.rel:void 0)}
            >
              <slot></slot>
            </a>
          `:""}
      ${this.renderType==="button"?w`
            <button part="label" type="button" class="label label-button">
              <slot @slotchange=${this.handleSlotChange}></slot>
            </button>
          `:""}
      ${this.renderType==="dropdown"?w`
            <div part="label" class="label label-dropdown">
              <slot @slotchange=${this.handleSlotChange}></slot>
            </div>
          `:""}

      <span part="end" class="end">
        <slot name="end"></slot>
      </span>

      <span part="separator" class="separator" aria-hidden="true">
        <slot name="separator"></slot>
      </span>
    `}};Te.css=tu;v([X("slot:not([name])")],Te.prototype,"defaultSlot",2);v([he()],Te.prototype,"renderType",2);v([b()],Te.prototype,"href",2);v([b()],Te.prototype,"target",2);v([b()],Te.prototype,"rel",2);v([ye("href",{waitUntilFirstUpdate:!0})],Te.prototype,"hrefChanged",1);Te=v([Fe("wa-breadcrumb-item")],Te);var iu=class extends Te{static get styles(){return[Te.styles,F`
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
          color: var(--c-fg-muted);
          margin: 0 var(--c-spacing-md);
        }
      `]}};customElements.get("craft-breadcrumb-item")||customElements.define("craft-breadcrumb-item",iu);var su=`:host {
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
`,ds=new Set,se=class extends fe{constructor(){super(...arguments),this.anchor=null,this.placement="top",this.open=!1,this.distance=8,this.skidding=0,this.for=null,this.withoutArrow=!1,this.eventController=new AbortController,this.handleAnchorClick=()=>{this.open=!this.open},this.handleBodyClick=i=>{i.target.closest('[data-popover="close"]')&&(i.stopPropagation(),this.open=!1)},this.handleDocumentKeyDown=i=>{i.key==="Escape"&&(i.preventDefault(),this.open=!1,this.anchor&&typeof this.anchor.focus=="function"&&this.anchor.focus())},this.handleDocumentClick=i=>{const e=i.target;this.anchor&&i.composedPath().includes(this.anchor)||e.closest("wa-popover")!==this&&(this.open=!1)}}connectedCallback(){super.connectedCallback(),this.id||(this.id=Ws("wa-popover-"))}disconnectedCallback(){super.disconnectedCallback(),document.removeEventListener("keydown",this.handleDocumentKeyDown),this.eventController.abort()}firstUpdated(){this.open&&(this.dialog.show(),this.popup.active=!0,this.popup.reposition())}updated(i){i.has("open")&&this.customStates.set("open",this.open)}async handleOpenChange(){if(this.open){const i=new Wt;if(this.dispatchEvent(i),i.defaultPrevented){this.open=!1;return}ds.forEach(e=>e.open=!1),document.addEventListener("keydown",this.handleDocumentKeyDown,{signal:this.eventController.signal}),document.addEventListener("click",this.handleDocumentClick,{signal:this.eventController.signal}),this.dialog.show(),this.popup.active=!0,ds.add(this),requestAnimationFrame(()=>{const e=this.querySelector("[autofocus]");e&&typeof e.focus=="function"?e.focus():this.dialog.focus()}),await le(this.popup.popup,"show-with-scale"),this.popup.reposition(),this.dispatchEvent(new Ht)}else{const i=new qt;if(this.dispatchEvent(i),i.defaultPrevented){this.open=!0;return}document.removeEventListener("keydown",this.handleDocumentKeyDown),document.removeEventListener("click",this.handleDocumentClick),ds.delete(this),await le(this.popup.popup,"hide-with-scale"),this.popup.active=!1,this.dialog.close(),this.dispatchEvent(new Ut)}}handleForChange(){const i=this.getRootNode();if(!i)return;const e=this.for?i.getElementById(this.for):null,t=this.anchor;if(e===t)return;const{signal:s}=this.eventController;e&&e.addEventListener("click",this.handleAnchorClick,{signal:s}),t&&t.removeEventListener("click",this.handleAnchorClick),this.anchor=e,this.for&&!e&&console.warn(`A popover was assigned to an element with an ID of "${this.for}" but the element could not be found.`,this)}async handleOptionsChange(){this.hasUpdated&&(await this.updateComplete,this.popup.reposition())}async show(){if(!this.open)return this.open=!0,xi(this,"wa-after-show")}async hide(){if(this.open)return this.open=!1,xi(this,"wa-after-hide")}render(){return w`
      <dialog part="dialog" class="dialog">
        <wa-popup
          part="popup"
          exportparts="
            popup:popup__popup,
            arrow:popup__arrow
          "
          class=${pe({popover:!0,"popover-open":this.open})}
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
    `}};se.css=su;se.dependencies={"wa-popup":W};v([X("dialog")],se.prototype,"dialog",2);v([X(".body")],se.prototype,"body",2);v([X("wa-popup")],se.prototype,"popup",2);v([he()],se.prototype,"anchor",2);v([b()],se.prototype,"placement",2);v([b({type:Boolean,reflect:!0})],se.prototype,"open",2);v([b({type:Number})],se.prototype,"distance",2);v([b({type:Number})],se.prototype,"skidding",2);v([b()],se.prototype,"for",2);v([b({attribute:"without-arrow",type:Boolean,reflect:!0})],se.prototype,"withoutArrow",2);v([ye("open",{waitUntilFirstUpdate:!0})],se.prototype,"handleOpenChange",1);v([ye("for")],se.prototype,"handleForChange",1);v([ye(["distance","placement","skidding"])],se.prototype,"handleOptionsChange",1);se=v([Fe("wa-popover")],se);var nu=class extends se{static get styles(){return[se.styles,F`
        :host {
          --wa-border-style: solid;
          --wa-border-width-s: 1px;
          --wa-color-surface-default: var(--c-bg-raised);
          --wa-color-surface-raised: var(--c-bg-raised);
          --wa-color-surface-border: var(--c-border-subtle);
          --wa-border-radius-m: var(--c-radius-lg);
        }

        .body {
          padding: var(--c-spacing-md);
        }
      `]}};customElements.get("craft-popover")||customElements.define("craft-popover",nu);var ou=F`
  .badge-indicator {
    --badge-color: var(--c-color-accent-bg-emphasis);
    --text-color: white;
    --badge-size: calc(8rem / 16);
    display: inline-flex;
    min-width: var(--badge-size);
    min-height: var(--badge-size);
    justify-content: center;
    align-items: center;
    background-color: var(--badge-color);
    color: var(--text-color);
    border-radius: var(--c-radius-full);
    border: 2px solid var(--c-fg-white);
  }

  .badge-indicator--secondary {
    --badge-color: var(--c-color-brand-bg-emphasis);
  }

  .badge-indicator--inverse {
    --badge-color: var(--c-color-neutral-bg-normal);
    --text-color: var(--c-fg-text);
  }

  .badge-indicator--with-number {
    --badge-size: var(--c-size-icon-md);
    padding: calc(2rem / 16);
  }

  .number {
    display: inline-flex;
    font-size: var(--c-text-xs);
    font-weight: var(--font-weight-semibold);
    line-height: 1;
  }
`,Je=class extends H{constructor(){super(),this.altText=null,this.badgeCount=null,this.badgeCountSuffix=null,this.variant="primary",this.id=this.id||`badge-${Math.floor(Math.random()*1e9).toString()}`}showCount(){return this.badgeCount!==null&&this.badgeCount>0}truncatedNumber(){if(this.showCount)return this.badgeCount>99?"99+":this.badgeCount.toString()}getBadgeRole(){return this.altText?"img":K}getLabelId(){return`${this.id}-label`}renderBadgeContents(){return w`
      ${this.showCount()?w`
            <span class="number">${this.truncatedNumber()}</span>
            <sl-visually-hidden>${this.badgeCountSuffix}</sl-visually-hidden>
          `:K}
      ${this.altText?w`
            <sl-visually-hidden id=${this.getLabelId()}
              >${this.altText}</sl-visually-hidden
            >
          `:K}
    `}render(){return w`
      <div
        part="badge"
        id=${this.id}
        class="${pe({"badge-indicator":!0,"badge-indicator--with-number":this.showCount(),"badge-indicator--secondary":this.variant==="secondary","badge-indicator--inverse":this.variant==="inverse"})}"
        role="${this.getBadgeRole()}"
        aria-labelledby="${this.altText?this.getLabelId():K}"
      >
        ${this.renderBadgeContents()}
      </div>
    `}};Je.styles=[ou],T([b()],Je.prototype,"altText",void 0),T([b()],Je.prototype,"badgeCount",void 0),T([b()],Je.prototype,"badgeCountSuffix",void 0),T([b()],Je.prototype,"variant",void 0),T([b()],Je.prototype,"id",void 0),customElements.get("craft-badge-indicator")||customElements.define("craft-badge-indicator",Je);const Zo="important",ru=" !"+Zo,au=Fi(class extends Li{constructor(i){if(super(i),i.type!==Ni.ATTRIBUTE||i.name!=="style"||i.strings?.length>2)throw Error("The `styleMap` directive must be used in the `style` attribute and must be the only part in the attribute.")}render(i){return Object.keys(i).reduce(((e,t)=>{const s=i[t];return s==null?e:e+`${t=t.includes("-")?t:t.replace(/(?:^(webkit|moz|ms|o)|)(?=[A-Z])/g,"-$&").toLowerCase()}:${s};`}),"")}update(i,[e]){const{style:t}=i.element;if(this.ft===void 0)return this.ft=new Set(Object.keys(e)),this.render(e);for(const s of this.ft)e[s]==null&&(this.ft.delete(s),s.includes("-")?t.removeProperty(s):t[s]=null);for(const s in e){const n=e[s];if(n!=null){this.ft.add(s);const o=typeof n=="string"&&n.endsWith(ru);s.includes("-")||o?t.setProperty(s,o?n.slice(0,-11):n,o?Zo:""):t[s]=n}}return Rt}});var lu=F`
  .nav-item {
    display: grid;
    gap: var(--c-spacing-md);
    grid-template-columns: calc(24rem / 16) 1fr auto;
    align-items: center;
    text-decoration: none;
    color: inherit;
    padding-inline: var(--c-spacing-md);
    padding-block: var(--c-spacing-sm);
    border-radius: var(--c-radius-md);
    position: relative;
  }
  
  craft-badge-indicator::part(badge) {
      position: absolute;
      inset-inline-end: 0;
      inset-block-end: 0;
    }
  }

  .nav-item--prefixed {
    padding-inline: var(--c-spacing-sm);
    grid-template-columns: calc(24rem / 16) 1fr auto;
  }

  :host([active]) .nav-item {
    &:before {
      content: '';
      position: absolute;
      inset-inline-start: 0;
      inset-block-start: 12%;
      width: calc(3rem / 16);
      height: 76%;
      border-radius: calc(2rem / 16);
      background-color: currentColor;
      transform: translateX(-150%);
    }
  }

  .nav-item:hover:not(:has(craft-button:hover)) {
    background-color: color-mix(in srgb, currentColor, transparent 95%);
  }

  .nav-item__prefix {
    position: relative;
    display: grid;
    justify-content: center;
    align-items: center;
    aspect-ratio: 1;
    width: 100%;
  }

  .active-indicator {
    display: inline-block;
    aspect-ratio: 1;
    width: calc(4rem / 16);
    border-radius: var(--c-radius-full);
    background-color: currentColor;

    :host([active]) & {
      width: calc(6rem / 16);
    }
  }

  .subnav {
    margin-block-start: var(--c-spacing-sm);
    margin-inline-start: calc(
      (var(--c-size-icon-md) / 2) + var(--c-spacing-sm) + 1px
    );
    padding-inline: var(--c-spacing-sm);
    border-left: 2px solid color-mix(in srgb, currentColor, transparent 90%);
  }

  :host([icon-only]) {
    .nav-item {
      gap: 0;
      grid-template-columns: calc(24rem / 16);
    }

    .nav-item__suffix {
      display: grid;
      justify-content: center;
      align-items: center;
    }

    .subnav {
      margin: 0;
      border-left: none;
      padding-inline: 0;
    }
  }
`,Ee=class extends H{constructor(){super(),this.active=!1,this.external=!1,this.indicator=!1,this.iconOnly=!1,this.subnavState="closed",this.id=this.id||Math.random().toString(36).substring(2,6)}connectedCallback(){super.connectedCallback(),this.subnavState=this.active?"open":"closed"}toggleSubnav(i){i.preventDefault(),i.stopPropagation(),this.subnavState=this.subnavState==="open"?"closed":"open"}renderIconItem(i){let e=`item-${this.id}`;return w`
      <a
        class="nav-item"
        id="${e}"
        href="${this.href}"
        aria-current="${this.active?"page":!1}"
      >
        ${this.renderPrefix()} ${this.renderSuffix(i)}
      </a>
      <c-tooltip for="${e}" placement="right-start"
        ><slot></slot
      ></c-tooltip>
    `}renderPrefix(){return w`
      <span class="nav-item__prefix">
        <slot name="prefix">
          <slot name="icon">
            ${this.icon?w` <craft-icon
                  name="${this.icon}"
                  class="nav-icon"
                ></craft-icon>`:K}
          </slot>
          ${this.indicator?w`<craft-badge-indicator
                altText="${Kn("Has Notifications")}"
              />`:K}
        </slot>
      </span>
    `}renderSuffix(i=!1){return w`
      <div class="nav-item__suffix">
        <slot name="suffix">
          ${i?w`
                  <craft-button
                    @click="${this.toggleSubnav}"
                    icon
                    size="small"
                    aria-controls="${this.id}-subnav"
                    aria-expanded="${this.subnavState==="open"?"true":"false"}"
                    aria-labelledby="${this.id}-toggle-icon ${this.id}-label"
                  >
                    <craft-icon
                      id="${this.id}-toggle-icon""
                      name="${this.subnavState==="closed"?"chevron-down":"chevron-up"}"
                      style="font-size: calc(10rem / 16)"
                      label="${Kn("Toggle subnavigation")}"
                  ></craft-icon>
                </craft-button>
              `:K}
        </slot>
      </div>
    `}renderItem(i,e=!1){return w`
      <a
        class="${pe({"nav-item":!0,"nav-item--prefixed":e})}"
        href="${this.href}"
        aria-current="${this.active?"page":!1}"
      >
        ${e?this.renderPrefix():K}
        <slot id="${this.id}-label"></slot>
        ${this.renderSuffix(i)}
      </a>
    `}render(){let i=!!this.querySelector('[slot="subnav"]'),e=!!this.icon||!!this.querySelector('[slot="prefix"]')||!!this.querySelector('[slot="icon"]');return w`
      <li>
        ${this.iconOnly?this.renderIconItem(i):this.renderItem(i,e)}
        ${i?w`
              <div
                class="subnav"
                id="${this.id}-subnav"
                style="${au({display:this.subnavState==="open"?"block":"none"})}"
              >
                <slot name="subnav"></slot>
              </div>
            `:K}
      </li>
    `}};Ee.styles=lu,T([b()],Ee.prototype,"icon",void 0),T([b()],Ee.prototype,"href",void 0),T([b({type:Boolean,reflect:!0})],Ee.prototype,"active",void 0),T([b({type:Boolean})],Ee.prototype,"external",void 0),T([b({type:Boolean})],Ee.prototype,"indicator",void 0),T([b()],Ee.prototype,"id",void 0),T([b({reflect:!0,type:Boolean,attribute:"icon-only"})],Ee.prototype,"iconOnly",void 0),T([he()],Ee.prototype,"subnavState",void 0),customElements.get("craft-nav-item")||customElements.define("craft-nav-item",Ee);var Ls=new Set;function cu(){const i=document.documentElement.clientWidth;return Math.abs(window.innerWidth-i)}function du(){const i=Number(getComputedStyle(document.body).paddingRight.replace(/px/,""));return isNaN(i)||!i?0:i}function Ai(i){if(Ls.add(i),!document.documentElement.classList.contains("wa-scroll-lock")){const e=cu()+du();let t=getComputedStyle(document.documentElement).scrollbarGutter;(!t||t==="auto")&&(t="stable"),e<2&&(t=""),document.documentElement.style.setProperty("--wa-scroll-lock-gutter",t),document.documentElement.classList.add("wa-scroll-lock"),document.documentElement.style.setProperty("--wa-scroll-lock-size",`${e}px`)}}function Ti(i){Ls.delete(i),Ls.size===0&&(document.documentElement.classList.remove("wa-scroll-lock"),document.documentElement.style.removeProperty("--wa-scroll-lock-size"))}function Xo(i){return i.split(" ").map(e=>e.trim()).filter(e=>e!=="")}var uu=`:host {
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
`,we=class extends fe{constructor(){super(...arguments),this.localize=new vt(this),this.hasSlotController=new qi(this,"footer","header-actions","label"),this.open=!1,this.label="",this.placement="end",this.withoutHeader=!1,this.lightDismiss=!0,this.handleDocumentKeyDown=i=>{i.key==="Escape"&&this.open&&(i.preventDefault(),i.stopPropagation(),this.requestClose(this.drawer))}}firstUpdated(){this.open&&(this.addOpenListeners(),this.drawer.showModal(),Ai(this))}disconnectedCallback(){super.disconnectedCallback(),Ti(this),this.removeOpenListeners()}async requestClose(i){const e=new qt({source:i});if(this.dispatchEvent(e),e.defaultPrevented){this.open=!0,le(this.drawer,"pulse");return}this.removeOpenListeners(),await le(this.drawer,"hide"),this.open=!1,this.drawer.close(),Ti(this);const t=this.originalTrigger;typeof t?.focus=="function"&&setTimeout(()=>t.focus()),this.dispatchEvent(new Ut)}addOpenListeners(){document.addEventListener("keydown",this.handleDocumentKeyDown)}removeOpenListeners(){document.removeEventListener("keydown",this.handleDocumentKeyDown)}handleDialogCancel(i){i.preventDefault(),!this.drawer.classList.contains("hide")&&i.target===this.drawer&&this.requestClose(this.drawer)}handleDialogClick(i){const t=i.target.closest('[data-drawer="close"]');t&&(i.stopPropagation(),this.requestClose(t))}async handleDialogPointerDown(i){i.target===this.drawer&&(this.lightDismiss?this.requestClose(this.drawer):await le(this.drawer,"pulse"))}handleOpenChange(){this.open&&!this.drawer.open?this.show():this.drawer.open&&(this.open=!0,this.requestClose(this.drawer))}async show(){const i=new Wt;if(this.dispatchEvent(i),i.defaultPrevented){this.open=!1;return}this.addOpenListeners(),this.originalTrigger=document.activeElement,this.open=!0,this.drawer.showModal(),Ai(this),requestAnimationFrame(()=>{const e=this.querySelector("[autofocus]");e&&typeof e.focus=="function"?e.focus():this.drawer.focus()}),await le(this.drawer,"show"),this.dispatchEvent(new Ht)}render(){const i=!this.withoutHeader,e=this.hasSlotController.test("footer");return w`
      <dialog
        part="dialog"
        class=${pe({drawer:!0,open:this.open,top:this.placement==="top",end:this.placement==="end",bottom:this.placement==="bottom",start:this.placement==="start"})}
        @cancel=${this.handleDialogCancel}
        @click=${this.handleDialogClick}
        @pointerdown=${this.handleDialogPointerDown}
      >
        ${i?w`
              <header part="header" class="header">
                <h2 part="title" class="title" id="title">
                  <!-- If there's no label, use an invisible character to prevent the header from collapsing -->
                  <slot name="label"> ${this.label.length>0?this.label:"​"} </slot>
                </h2>
                <div part="header-actions" class="header-actions">
                  <slot name="header-actions"></slot>
                  <wa-button
                    part="close-button"
                    exportparts="base:close-button__base"
                    class="close"
                    appearance="plain"
                    @click="${t=>this.requestClose(t.target)}"
                  >
                    <wa-icon
                      name="xmark"
                      label=${this.localize.term("close")}
                      library="system"
                      variant="solid"
                    ></wa-icon>
                  </wa-button>
                </div>
              </header>
            `:""}

        <div part="body" class="body"><slot></slot></div>

        ${e?w`
              <footer part="footer" class="footer">
                <slot name="footer"></slot>
              </footer>
            `:""}
      </dialog>
    `}};we.css=uu;v([X(".drawer")],we.prototype,"drawer",2);v([b({type:Boolean,reflect:!0})],we.prototype,"open",2);v([b({reflect:!0})],we.prototype,"label",2);v([b({reflect:!0})],we.prototype,"placement",2);v([b({attribute:"without-header",type:Boolean,reflect:!0})],we.prototype,"withoutHeader",2);v([b({attribute:"light-dismiss",type:Boolean})],we.prototype,"lightDismiss",2);v([ye("open",{waitUntilFirstUpdate:!0})],we.prototype,"handleOpenChange",1);we=v([Fe("wa-drawer")],we);document.addEventListener("click",i=>{const e=i.target.closest("[data-drawer]");if(e instanceof Element){const[t,s]=Xo(e.getAttribute("data-drawer")||"");if(t==="open"&&s?.length){const o=e.getRootNode().getElementById(s);o?.localName==="wa-drawer"?o.open=!0:console.warn(`A drawer with an ID of "${s}" could not be found in this document.`)}}});document.body.addEventListener("pointerdown",()=>{});var hu=()=>({checkValidity(i){const e=i.input,t={message:"",isValid:!0,invalidKeys:[]};if(!e)return t;let s=!0;if("checkValidity"in e&&(s=e.checkValidity()),s)return t;if(t.isValid=!1,"validationMessage"in e&&(t.message=e.validationMessage),!("validity"in e))return t.invalidKeys.push("customError"),t;for(const n in e.validity){if(n==="valid")continue;const o=n;e.validity[o]&&t.invalidKeys.push(o)}return t}});var Qo=class extends Event{constructor(){super("wa-invalid",{bubbles:!0,cancelable:!1,composed:!0})}},pu=()=>({observedAttributes:["custom-error"],checkValidity(i){const e={message:"",isValid:!0,invalidKeys:[]};return i.customError&&(e.message=i.customError,e.isValid=!1,e.invalidKeys=["customError"]),e}}),Re=class extends fe{constructor(){super(),this.name=null,this.disabled=!1,this.required=!1,this.assumeInteractionOn=["input"],this.validators=[],this.valueHasChanged=!1,this.hasInteracted=!1,this.customError=null,this.emittedEvents=[],this.emitInvalid=i=>{i.target===this&&(this.hasInteracted=!0,this.dispatchEvent(new Qo))},this.handleInteraction=i=>{const e=this.emittedEvents;e.includes(i.type)||e.push(i.type),e.length===this.assumeInteractionOn?.length&&(this.hasInteracted=!0)},this.addEventListener("invalid",this.emitInvalid)}static get validators(){return[pu()]}static get observedAttributes(){const i=new Set(super.observedAttributes||[]);for(const e of this.validators)if(e.observedAttributes)for(const t of e.observedAttributes)i.add(t);return[...i]}connectedCallback(){super.connectedCallback(),this.updateValidity(),this.assumeInteractionOn.forEach(i=>{this.addEventListener(i,this.handleInteraction)})}firstUpdated(...i){super.firstUpdated(...i),this.updateValidity()}willUpdate(i){if(i.has("customError")&&(this.customError||(this.customError=null),this.setCustomValidity(this.customError||"")),i.has("value")||i.has("disabled")){const e=this.value;if(Array.isArray(e)){if(this.name){const t=new FormData;for(const s of e)t.append(this.name,s);this.setValue(t,t)}}else this.setValue(e,e)}i.has("disabled")&&(this.customStates.set("disabled",this.disabled),(this.hasAttribute("disabled")||!this.matches(":disabled"))&&this.toggleAttribute("disabled",this.disabled)),this.updateValidity(),super.willUpdate(i)}get labels(){return this.internals.labels}getForm(){return this.internals.form}get validity(){return this.internals.validity}get willValidate(){return this.internals.willValidate}get validationMessage(){return this.internals.validationMessage}checkValidity(){return this.updateValidity(),this.internals.checkValidity()}reportValidity(){return this.updateValidity(),this.hasInteracted=!0,this.internals.reportValidity()}get validationTarget(){return this.input||void 0}setValidity(...i){const e=i[0],t=i[1];let s=i[2];s||(s=this.validationTarget),this.internals.setValidity(e,t,s||void 0),this.requestUpdate("validity"),this.setCustomStates()}setCustomStates(){const i=!!this.required,e=this.internals.validity.valid,t=this.hasInteracted;this.customStates.set("required",i),this.customStates.set("optional",!i),this.customStates.set("invalid",!e),this.customStates.set("valid",e),this.customStates.set("user-invalid",!e&&t),this.customStates.set("user-valid",e&&t)}setCustomValidity(i){if(!i){this.customError=null,this.setValidity({});return}this.customError=i,this.setValidity({customError:!0},i,this.validationTarget)}formResetCallback(){this.resetValidity(),this.hasInteracted=!1,this.valueHasChanged=!1,this.emittedEvents=[],this.updateValidity()}formDisabledCallback(i){this.disabled=i,this.updateValidity()}formStateRestoreCallback(i,e){this.value=i,e==="restore"&&this.resetValidity(),this.updateValidity()}setValue(...i){const[e,t]=i;this.internals.setFormValue(e,t)}get allValidators(){const i=this.constructor.validators||[],e=this.validators||[];return[...i,...e]}resetValidity(){this.setCustomValidity(""),this.setValidity({})}updateValidity(){if(this.disabled||this.hasAttribute("disabled")||!this.willValidate){this.resetValidity();return}const i=this.allValidators;if(!i?.length)return;const e={customError:!!this.customError},t=this.validationTarget||this.input||void 0;let s="";for(const n of i){const{isValid:o,message:r,invalidKeys:a}=n.checkValidity(this);o||(s||(s=r),a?.length>=0&&a.forEach(c=>e[c]=!0))}s||(s=this.validationMessage),this.setValidity(e,s,t)}};Re.formAssociated=!0;v([b({reflect:!0})],Re.prototype,"name",2);v([b({type:Boolean})],Re.prototype,"disabled",2);v([b({state:!0,attribute:!1})],Re.prototype,"valueHasChanged",2);v([b({state:!0,attribute:!1})],Re.prototype,"hasInteracted",2);v([b({attribute:"custom-error",reflect:!0})],Re.prototype,"customError",2);v([b({attribute:!1,state:!0,type:Object})],Re.prototype,"validity",1);var fu=`@layer wa-utilities {
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
`;const Jo=Symbol.for(""),mu=i=>{if(i?.r===Jo)return i?._$litStatic$},Pn=(i,...e)=>({_$litStatic$:e.reduce(((t,s,n)=>t+(o=>{if(o._$litStatic$!==void 0)return o._$litStatic$;throw Error(`Value passed to 'literal' function must be a 'literal' result: ${o}. Use 'unsafeStatic' to pass non-literal values, but
            take care to ensure page security.`)})(s)+i[n+1]),i[0]),r:Jo}),Bn=new Map,bu=i=>(e,...t)=>{const s=t.length;let n,o;const r=[],a=[];let c,u=0,h=!1;for(;u<s;){for(c=e[u];u<s&&(o=t[u],(n=mu(o))!==void 0);)c+=n+e[++u],h=!0;u!==s&&a.push(o),r.push(c),u++}if(u===s&&r.push(e[s]),h){const g=r.join("$$lit$$");(e=Bn.get(g))===void 0&&(r.raw=r,Bn.set(g,e=r)),t=a}return i(e,...t)},us=bu(w);var gu=`@layer wa-component {
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
`,U=class extends Re{constructor(){super(...arguments),this.assumeInteractionOn=["click"],this.hasSlotController=new qi(this,"[default]","start","end"),this.localize=new vt(this),this.invalid=!1,this.isIconButton=!1,this.title="",this.variant="neutral",this.appearance="accent",this.size="medium",this.withCaret=!1,this.disabled=!1,this.loading=!1,this.pill=!1,this.type="button",this.form=null}static get validators(){return[...super.validators,hu()]}constructLightDOMButton(){const i=document.createElement("button");return i.type=this.type,i.style.position="absolute",i.style.width="0",i.style.height="0",i.style.clipPath="inset(50%)",i.style.overflow="hidden",i.style.whiteSpace="nowrap",this.name&&(i.name=this.name),i.value=this.value||"",["form","formaction","formenctype","formmethod","formnovalidate","formtarget"].forEach(e=>{this.hasAttribute(e)&&i.setAttribute(e,this.getAttribute(e))}),i}handleClick(){if(!this.getForm())return;const e=this.constructLightDOMButton();this.parentElement?.append(e),e.click(),e.remove()}handleInvalid(){this.dispatchEvent(new Qo)}handleLabelSlotChange(){const i=this.labelSlot.assignedNodes({flatten:!0});let e=!1,t=!1,s=!1,n=!1;[...i].forEach(o=>{if(o.nodeType===Node.ELEMENT_NODE){const r=o;r.localName==="wa-icon"?(t=!0,e||(e=r.label!==void 0)):n=!0}else o.nodeType===Node.TEXT_NODE&&(o.textContent?.trim()||"").length>0&&(s=!0)}),this.isIconButton=t&&!s&&!n,this.isIconButton&&!e&&console.warn('Icon buttons must have a label for screen readers. Add <wa-icon label="..."> to remove this warning.',this)}isButton(){return!this.href}isLink(){return!!this.href}handleDisabledChange(){this.updateValidity()}setValue(...i){}click(){this.button.click()}focus(i){this.button.focus(i)}blur(){this.button.blur()}render(){const i=this.isLink(),e=i?Pn`a`:Pn`button`;return us`
      <${e}
        part="base"
        class=${pe({button:!0,caret:this.withCaret,disabled:this.disabled,loading:this.loading,rtl:this.localize.dir()==="rtl","has-label":this.hasSlotController.test("[default]"),"has-start":this.hasSlotController.test("start"),"has-end":this.hasSlotController.test("end"),"is-icon-button":this.isIconButton})}
        ?disabled=${be(i?void 0:this.disabled)}
        type=${be(i?void 0:this.type)}
        title=${this.title}
        name=${be(i?void 0:this.name)}
        value=${be(i?void 0:this.value)}
        href=${be(i?this.href:void 0)}
        target=${be(i?this.target:void 0)}
        download=${be(i?this.download:void 0)}
        rel=${be(i&&this.rel?this.rel:void 0)}
        role=${be(i?void 0:"button")}
        aria-disabled=${this.disabled?"true":"false"}
        tabindex=${this.disabled?"-1":"0"}
        @invalid=${this.isButton()?this.handleInvalid:null}
        @click=${this.handleClick}
      >
        <slot name="start" part="start" class="start"></slot>
        <slot part="label" class="label" @slotchange=${this.handleLabelSlotChange}></slot>
        <slot name="end" part="end" class="end"></slot>
        ${this.withCaret?us`
                <wa-icon part="caret" class="caret" library="system" name="chevron-down" variant="solid"></wa-icon>
              `:""}
        ${this.loading?us`<wa-spinner part="spinner"></wa-spinner>`:""}
      </${e}>
    `}};U.shadowRootOptions={...Re.shadowRootOptions,delegatesFocus:!0};U.css=[gu,fu,jo];v([X(".button")],U.prototype,"button",2);v([X("slot:not([name])")],U.prototype,"labelSlot",2);v([he()],U.prototype,"invalid",2);v([he()],U.prototype,"isIconButton",2);v([b()],U.prototype,"title",2);v([b({reflect:!0})],U.prototype,"variant",2);v([b({reflect:!0})],U.prototype,"appearance",2);v([b({reflect:!0})],U.prototype,"size",2);v([b({attribute:"with-caret",type:Boolean,reflect:!0})],U.prototype,"withCaret",2);v([b({type:Boolean})],U.prototype,"disabled",2);v([b({type:Boolean,reflect:!0})],U.prototype,"loading",2);v([b({type:Boolean,reflect:!0})],U.prototype,"pill",2);v([b()],U.prototype,"type",2);v([b({reflect:!0})],U.prototype,"name",2);v([b({reflect:!0})],U.prototype,"value",2);v([b({reflect:!0})],U.prototype,"href",2);v([b()],U.prototype,"target",2);v([b()],U.prototype,"rel",2);v([b()],U.prototype,"download",2);v([b({reflect:!0})],U.prototype,"form",2);v([b({attribute:"formaction"})],U.prototype,"formAction",2);v([b({attribute:"formenctype"})],U.prototype,"formEnctype",2);v([b({attribute:"formmethod"})],U.prototype,"formMethod",2);v([b({attribute:"formnovalidate",type:Boolean})],U.prototype,"formNoValidate",2);v([b({attribute:"formtarget"})],U.prototype,"formTarget",2);v([ye("disabled",{waitUntilFirstUpdate:!0})],U.prototype,"handleDisabledChange",1);U=v([Fe("wa-button")],U);var vu=`:host {
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
`,Os=class extends fe{constructor(){super(...arguments),this.localize=new vt(this)}render(){return w`
      <svg
        part="base"
        role="progressbar"
        aria-label=${this.localize.term("loading")}
        fill="none"
        viewBox="0 0 50 50"
        xmlns="http://www.w3.org/2000/svg"
      >
        <circle class="track" cx="25" cy="25" r="20" fill="none" stroke-width="5" />
        <circle class="indicator" cx="25" cy="25" r="20" fill="none" stroke-width="5" />
      </svg>
    `}};Os.css=vu;Os=v([Fe("wa-spinner")],Os);var _u=class extends we{static get styles(){return[we.styles,F`
        :host {
          --wa-color-surface-raised: var(--c-bg-raised);
          --spacing: var(--c-spacing-lg);
          background-color: red;
        }
      `]}};customElements.get("craft-drawer")||customElements.define("craft-drawer",_u);var yu=`:host {
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
`,Ne=class extends fe{constructor(){super(...arguments),this.localize=new vt(this),this.hasSlotController=new qi(this,"footer","header-actions","label"),this.open=!1,this.label="",this.withoutHeader=!1,this.lightDismiss=!1,this.handleDocumentKeyDown=i=>{i.key==="Escape"&&this.open&&(i.preventDefault(),i.stopPropagation(),this.requestClose(this.dialog))}}firstUpdated(){this.open&&(this.addOpenListeners(),this.dialog.showModal(),Ai(this))}disconnectedCallback(){super.disconnectedCallback(),Ti(this),this.removeOpenListeners()}async requestClose(i){const e=new qt({source:i});if(this.dispatchEvent(e),e.defaultPrevented){this.open=!0,le(this.dialog,"pulse");return}this.removeOpenListeners(),await le(this.dialog,"hide"),this.open=!1,this.dialog.close(),Ti(this);const t=this.originalTrigger;typeof t?.focus=="function"&&setTimeout(()=>t.focus()),this.dispatchEvent(new Ut)}addOpenListeners(){document.addEventListener("keydown",this.handleDocumentKeyDown)}removeOpenListeners(){document.removeEventListener("keydown",this.handleDocumentKeyDown)}handleDialogCancel(i){i.preventDefault(),!this.dialog.classList.contains("hide")&&i.target===this.dialog&&this.requestClose(this.dialog)}handleDialogClick(i){const t=i.target.closest('[data-dialog="close"]');t&&(i.stopPropagation(),this.requestClose(t))}async handleDialogPointerDown(i){i.target===this.dialog&&(this.lightDismiss?this.requestClose(this.dialog):await le(this.dialog,"pulse"))}handleOpenChange(){this.open&&!this.dialog.open?this.show():!this.open&&this.dialog.open&&(this.open=!0,this.requestClose(this.dialog))}async show(){const i=new Wt;if(this.dispatchEvent(i),i.defaultPrevented){this.open=!1;return}this.addOpenListeners(),this.originalTrigger=document.activeElement,this.open=!0,this.dialog.showModal(),Ai(this),requestAnimationFrame(()=>{const e=this.querySelector("[autofocus]");e&&typeof e.focus=="function"?e.focus():this.dialog.focus()}),await le(this.dialog,"show"),this.dispatchEvent(new Ht)}render(){const i=!this.withoutHeader,e=this.hasSlotController.test("footer");return w`
      <dialog
        part="dialog"
        class=${pe({dialog:!0,open:this.open})}
        @cancel=${this.handleDialogCancel}
        @click=${this.handleDialogClick}
        @pointerdown=${this.handleDialogPointerDown}
      >
        ${i?w`
              <header part="header" class="header">
                <h2 part="title" class="title" id="title">
                  <!-- If there's no label, use an invisible character to prevent the header from collapsing -->
                  <slot name="label"> ${this.label.length>0?this.label:"​"} </slot>
                </h2>
                <div part="header-actions" class="header-actions">
                  <slot name="header-actions"></slot>
                  <wa-button
                    part="close-button"
                    exportparts="base:close-button__base"
                    class="close"
                    appearance="plain"
                    @click="${t=>this.requestClose(t.target)}"
                  >
                    <wa-icon
                      name="xmark"
                      label=${this.localize.term("close")}
                      library="system"
                      variant="solid"
                    ></wa-icon>
                  </wa-button>
                </div>
              </header>
            `:""}

        <div part="body" class="body"><slot></slot></div>

        ${e?w`
              <footer part="footer" class="footer">
                <slot name="footer"></slot>
              </footer>
            `:""}
      </dialog>
    `}};Ne.css=yu;v([X(".dialog")],Ne.prototype,"dialog",2);v([b({type:Boolean,reflect:!0})],Ne.prototype,"open",2);v([b({reflect:!0})],Ne.prototype,"label",2);v([b({attribute:"without-header",type:Boolean,reflect:!0})],Ne.prototype,"withoutHeader",2);v([b({attribute:"light-dismiss",type:Boolean})],Ne.prototype,"lightDismiss",2);v([ye("open",{waitUntilFirstUpdate:!0})],Ne.prototype,"handleOpenChange",1);Ne=v([Fe("wa-dialog")],Ne);document.addEventListener("click",i=>{const e=i.target.closest("[data-dialog]");if(e instanceof Element){const[t,s]=Xo(e.getAttribute("data-dialog")||"");if(t==="open"&&s?.length){const o=e.getRootNode().getElementById(s);o?.localName==="wa-dialog"?o.open=!0:console.warn(`A dialog with an ID of "${s}" could not be found in this document.`)}}});document.addEventListener("pointerdown",()=>{});var wu=class extends Ne{static get styles(){return[Ne.styles,F`
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
      `]}};customElements.get("craft-dialog")||customElements.define("craft-dialog",wu);class Un extends Bi($o(H)){constructor(){super(),this.multipleChoice=!0}}class Hn extends Ui(Hi){connectedCallback(){super.connectedCallback(),this.type="checkbox"}}var xu=class extends Un{static get styles(){return[...Un.styles,F`
        .form-field__group-two {
          margin-top: var(--c-spacing-sm);
        }

        ::slotted(label) {
          font-weight: bold;
        }
      `]}};customElements.get("craft-checkbox-group")||customElements.define("craft-checkbox-group",xu);var Eu=class extends Hn{static get styles(){return[...Hn.styles,F`
        /* same as radio, potentially consolidate */
        :host {
          display: flex;
          gap: var(--c-spacing-sm);
        }

        ::slotted(label) {
          font: inherit;
        }

        ::slotted([slot='input']) {
          background-color: var(--c-input-bg, var(--c-form-control-bg));
          border: var(--c-input-border, 1px solid var(--c-form-control-border));
          border-radius: var(--c-input-radius, var(--c-radius-sm));
        }

        .choice-field__help-text {
          font-size: 1em;
          color: var(--c-fg-muted);
        }
      `]}};customElements.get("craft-checkbox")||customElements.define("craft-checkbox",Eu);const Ce={Default:"default",Success:"success",Warning:"warning",Danger:"danger",Info:"info"},ku={OutlineFill:"outline-fill"};var Qs=F`
  :host([variant='default']) {
    --c-color-bg-emphasis: var(--c-color-neutral-bg-emphasis);
    --c-color-bg-normal: var(--c-color-neutral-bg-normal);
    --c-color-bg-subtle: var(--c-color-neutral-bg-subtle);
    --c-color-border-emphasis: var(--c-color-neutral-border-emphasis);
    --c-color-border-normal: var(--c-color-neutral-border-normal);
    --c-color-border-subtle: var(--c-color-neutral-border-subtle);
    --c-color-on-emphasis: var(--c-color-neutral-on-emphasis);
    --c-color-on-normal: var(--c-color-neutral-on-normal);
    --c-color-on-subtle: var(--c-color-neutral-on-subtle);
  }

  :host([variant='danger']) {
    --c-color-bg-emphasis: var(--c-color-danger-bg-emphasis);
    --c-color-bg-normal: var(--c-color-danger-bg-normal);
    --c-color-bg-subtle: var(--c-color-danger-bg-subtle);
    --c-color-border-emphasis: var(--c-color-danger-border-emphasis);
    --c-color-border-normal: var(--c-color-danger-border-normal);
    --c-color-border-subtle: var(--c-color-danger-border-subtle);
    --c-color-on-emphasis: var(--c-color-danger-on-emphasis);
    --c-color-on-normal: var(--c-color-danger-on-normal);
    --c-color-on-subtle: var(--c-color-danger-on-subtle);
  }

  :host([variant='info']) {
    --c-color-bg-emphasis: var(--c-color-info-bg-emphasis);
    --c-color-bg-normal: var(--c-color-info-bg-normal);
    --c-color-bg-subtle: var(--c-color-info-bg-subtle);
    --c-color-border-emphasis: var(--c-color-info-border-emphasis);
    --c-color-border-normal: var(--c-color-info-border-normal);
    --c-color-border-subtle: var(--c-color-info-border-subtle);
    --c-color-on-emphasis: var(--c-color-info-on-emphasis);
    --c-color-on-normal: var(--c-color-info-on-normal);
    --c-color-on-subtle: var(--c-color-info-on-subtle);
  }

  :host([variant='warning']) {
    --c-color-bg-emphasis: var(--c-color-warning-bg-emphasis);
    --c-color-bg-normal: var(--c-color-warning-bg-normal);
    --c-color-bg-subtle: var(--c-color-warning-bg-subtle);
    --c-color-border-emphasis: var(--c-color-warning-border-emphasis);
    --c-color-border-normal: var(--c-color-warning-border-normal);
    --c-color-border-subtle: var(--c-color-warning-border-subtle);
    --c-color-on-emphasis: var(--c-color-warning-on-emphasis);
    --c-color-on-normal: var(--c-color-warning-on-normal);
    --c-color-on-subtle: var(--c-color-warning-on-subtle);
  }

  :host([variant='success']) {
    --c-color-bg-emphasis: var(--c-color-success-bg-emphasis);
    --c-color-bg-normal: var(--c-color-success-bg-normal);
    --c-color-bg-subtle: var(--c-color-success-bg-subtle);
    --c-color-border-emphasis: var(--c-color-success-border-emphasis);
    --c-color-border-normal: var(--c-color-success-border-normal);
    --c-color-border-subtle: var(--c-color-success-border-subtle);
    --c-color-on-emphasis: var(--c-color-success-on-emphasis);
    --c-color-on-normal: var(--c-color-success-on-normal);
    --c-color-on-subtle: var(--c-color-success-on-subtle);
  }
`,Cu=F`
  :host {
    --c-color-bg-emphasis: var(--c-color-neutral-bg-emphasis);
    --c-color-bg-normal: var(--c-color-neutral-bg-normal);
    --c-color-bg-subtle: var(--c-color-neutral-bg-subtle);
    --c-color-border-emphasis: var(--c-color-neutral-border-emphasis);
    --c-color-border-normal: var(--c-color-neutral-border-normal);
    --c-color-border-subtle: var(--c-color-neutral-border-subtle);
    --c-color-on-emphasis: var(--c-color-neutral-on-emphasis);
    --c-color-on-normal: var(--c-color-neutral-on-normal);
    --c-color-on-subtle: var(--c-color-neutral-on-subtle);
    --_radius: var(--c-callout-radius, var(--c-radius-md));
    display: flex;
    gap: var(--c-spacing-sm);
    align-items: start;
    padding: var(--c-spacing-md);
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
    --c-fg-link: var(--c-color-on-emphasis);
    background-color: var(--c-color-bg-emphasis);
    color: var(--c-color-on-emphasis);
    border-color: var(--c-color-border-emphasis);
  }

  :host([appearance~='fill']) {
    --c-fg-link: var(--c-color-on-normal);
    border-color: transparent;
    background-color: var(--c-color-bg-normal);
    color: var(--c-color-on-normal);
  }

  :host([appearance~='outline-fill']) {
    --c-fg-link: var(--c-color-on-normal);
    border-color: var(--c-color-border-normal);
    background-color: var(--c-color-bg-normal);
    color: var(--c-color-on-normal);
  }

  :host([appearance~='outline']) {
    --c-fg-link: var(--c-color-on-subtle);
    border-color: var(--c-color-border-subtle);
    background-color: transparent;
    color: var(--c-color-on-subtle);
  }

  :host([appearance~='plain']) {
    --c-fg-link: var(--c-color-on-subtle);
    background-color: transparent;
    border-color: transparent;
    color: var(--c-color-on-subtle);
  }
`,Ke=class extends H{constructor(...i){super(...i),this.variant=Ce.Default,this.appearance=ku.OutlineFill,this.title="",this.icon=null,this.rounded="all",this.inline=!1}getDefaultIcon(){switch(this.variant){case Ce.Info:return"lightbulb";case Ce.Success:return"circle-check";case Ce.Warning:return"circle-exclamation";case Ce.Danger:return"triangle-exclamation";default:return null}}render(){return w`
      ${this.icon||this.querySelector('[slot="icon"]')?w`<slot name="icon" class="callout__icon">
            <craft-icon
              name="${this.getDefaultIcon()}"
              style="font-size: 0.9em"
            ></craft-icon>
          </slot>`:K}
      <div class="callout__body">
        <slot name="title" class="callout__title">${this.title}</slot>
        <div class="callout__description">
          <slot></slot>
        </div>
      </div>
    `}};Ke.styles=[Qs,Cu],T([b({reflect:!0})],Ke.prototype,"variant",void 0),T([b({reflect:!0})],Ke.prototype,"appearance",void 0),T([b()],Ke.prototype,"title",void 0),T([b()],Ke.prototype,"icon",void 0),T([b({reflect:!0})],Ke.prototype,"rounded",void 0),T([b({reflect:!0,type:Boolean})],Ke.prototype,"inline",void 0),customElements.get("craft-callout")||customElements.define("craft-callout",Ke);var Su=F`
  :host {
    display: contents;
  }

  .action-item {
    font: inherit;
    text-align: left;
    display: grid;
    gap: var(--c-spacing-md);
    grid-template-columns: auto 1fr auto;
    align-items: center;
    text-decoration: none;
    color: inherit;
    padding-inline: var(--c-spacing-sm);
    padding-block: var(--c-spacing-sm);
    border-radius: var(--c-radius-md);
    position: relative;
    background-color: transparent;
    border: 1px solid transparent;
  }

  @media (hover: hover) {
    :host(:hover) .action-item:not(:disabled) {
      background-color: var(--c-color-accent-bg-subtle);
      color: var(--c-color-accent-on-subtle);
    }
  }

  :host([active]) .action-item {
    background-color: var(--c-color-accent-bg-emphasis);
    color: var(--c-color-accent-on-emphasis);
  }

  .action-item:disabled {
    opacity: 0.5;
  }

  .action-item:not(:disabled) {
    cursor: pointer;
  }

  .action-item__prefix {
    position: relative;
    display: grid;
    justify-content: center;
    align-items: center;
    aspect-ratio: 1;
    width: 100%;
  }

  :host([variant='danger']) .action-item {
    color: var(--c-color-on-subtle);
  }

  @media (hover: hover) {
    :host(:hover[variant='danger']) .action-item:not(:disabled) {
      background-color: var(--c-color-bg-subtle);
      color: var(--c-color-on-subtle);
    }
  }
`,ct=class extends H{constructor(...e){super(...e),this.icon=null,this.href=null,this.disabled=!1,this.variant=Ce.Default}renderBody(){return w`
      <span class="action-item__prefix">
        <slot name="prefix">
          <slot name="icon">
            ${this.icon?w`<craft-icon name="${this.icon}"></craft-icon>`:K}
          </slot>
        </slot>
      </span>

      <slot></slot>

      <span class="action-item__suffix">
        <slot name="suffix"></slot>
      </span>
    `}render(){return this.href?w`
          <a class="action-item" href="${this.href}"> ${this.renderBody()} </a>
        `:w`
          <button
            type="button"
            class="action-item"
            ?disabled="${this.disabled}"
          >
            ${this.renderBody()}
          </button>
        `}};ct.styles=[Qs,Su],T([b()],ct.prototype,"icon",void 0),T([b()],ct.prototype,"href",void 0),T([b({type:Boolean})],ct.prototype,"disabled",void 0),T([b({reflect:!0})],ct.prototype,"variant",void 0),customElements.get("craft-action-item")||customElements.define("craft-action-item",ct);const Au=F`
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
`;class $e{static __createGlobalStyleNode(){const e=document.createElement("style");return e.setAttribute("data-overlays",""),e.textContent=Au.cssText,document.head.appendChild(e),e}get list(){return this.__list}get shownList(){return this.__shownList}constructor(){this.__list=[],this.__shownList=[],this.__siblingsInert=!1,this.__blockingMap=new WeakMap,$e.__globalStyleNode||($e.__globalStyleNode=$e.__createGlobalStyleNode())}add(e){if(this.list.find(t=>e===t))throw new Error("controller instance is already added");return this.list.push(e),e}remove(e){if(!this.list.find(t=>e===t))throw new Error("could not find controller to remove");this.__list=this.list.filter(t=>t!==e),this.__shownList=this.shownList.filter(t=>t!==e)}show(e){this.list.find(t=>e===t)&&this.hide(e),this.__shownList.unshift(e),Array.from(this.__shownList).reverse().forEach((t,s)=>{t.elevation=s+1})}hide(e){if(!this.list.find(t=>e===t))throw new Error("could not find controller to hide");this.__shownList=this.shownList.filter(t=>t!==e)}teardown(){this.list.forEach(e=>{e.teardown()}),this.__list=[],this.__shownList=[],this.__siblingsInert=!1,$e.__globalStyleNode&&(document.head.removeChild($e.__globalStyleNode),$e.__globalStyleNode=void 0)}get siblingsInert(){return this.__siblingsInert}disableTrapsKeyboardFocusForAll(){this.shownList.forEach(e=>{e.trapsKeyboardFocus===!0&&e.disableTrapsKeyboardFocus&&e.disableTrapsKeyboardFocus({findNewTrap:!1})})}informTrapsKeyboardFocusGotEnabled(e){this.siblingsInert===!1&&e==="global"&&(this.__siblingsInert=!0)}informTrapsKeyboardFocusGotDisabled({disabledCtrl:e,findNewTrap:t=!0}={}){const s=this.shownList.find(n=>n!==e&&n.trapsKeyboardFocus===!0);s?t&&s.enableTrapsKeyboardFocus():this.siblingsInert===!0&&(this.__siblingsInert=!1)}requestToPreventScroll(){const{isIOS:e,isMacSafari:t}=Ei;document.body.classList.add("overlays-scroll-lock"),(e||t)&&document.body.classList.add("overlays-scroll-lock-ios-fix"),e&&document.documentElement.classList.add("overlays-scroll-lock-ios-fix")}requestToEnableScroll(){if(this.shownList.some(n=>n.preventsScroll===!0))return;const{isIOS:t,isMacSafari:s}=Ei;document.body.classList.remove("overlays-scroll-lock"),(t||s)&&document.body.classList.remove("overlays-scroll-lock-ios-fix"),t&&document.documentElement.classList.remove("overlays-scroll-lock-ios-fix")}requestToShowOnly(e){const t=this.shownList.filter(s=>s!==e);t.forEach(s=>s.hide()),this.__blockingMap.set(e,t)}retractRequestToShowOnly(e){this.__blockingMap.has(e)&&this.__blockingMap.get(e).forEach(s=>s.show())}}$e.__globalStyleNode=void 0;const Tu=fi.get("@lion/ui::overlays::0.x")||new $e;function Is(){let i=document.activeElement||document.body;for(;i&&i.shadowRoot&&i.shadowRoot.activeElement;)i=i.shadowRoot.activeElement;return i}const qn=({visibility:i,display:e})=>i!=="hidden"&&e!=="none",Nu=({display:i})=>i==="contents";function Fu(i){if(!i||!i.isConnected||!qn(i.style))return!1;const e=window.getComputedStyle(i);return qn(e)?Nu(e)?!0:!!(i.offsetWidth||i.offsetHeight||i.getClientRects().length):!1}function Lu(i,e){const t=Math.max(i.tabIndex,0),s=Math.max(e.tabIndex,0);return t===0||s===0?s>t:t>s}function Ou(i,e){const t=[];for(;i.length>0&&e.length>0;)Lu(i[0],e[0])?t.push(e.shift()):t.push(i.shift());return[...t,...i,...e]}function Ds(i){const e=i.length;if(e<2)return i;const t=Math.ceil(e/2),s=Ds(i.slice(0,t)),n=Ds(i.slice(t));return Ou(s,n)}const hs="matches"in Element.prototype?"matches":"msMatchesSelector";function Iu(i){return i[hs]("input, select, textarea, button, object")?i[hs](":not([disabled])"):i[hs]("a[href], area[href], iframe, [tabindex], [contentEditable]")}function Du(i){return Iu(i)?Number(i.getAttribute("tabindex")||0):-1}function $u(i){if(i.localName==="slot")return i.assignedNodes({flatten:!0});const{children:e}=i.shadowRoot||i;return e||[]}function Mu(i){return i.nodeType!==Node.ELEMENT_NODE?!1:i.localName==="slot"?!0:Fu(i)}function er(i,e){if(!Mu(i))return!1;const t=i,s=Du(t);let n=s>0;s>=0&&e.push(t);const o=$u(t);for(let r=0;r<o.length;r+=1)n=er(o[r],e)||n;return n}function tr(i){const e=[];return er(i,e)?Ds(e):e}function bt(i,e,t={}){function s(m){return"getAttribute"in m}function n(m){if(!s(m))return null;const y=m.getAttribute("slot");let _=null;if(y){const x=t[y];x&&(_=x.filter(p=>p?.element===m)[0]||null)}return _}const o=n(i);if(o)return o.deepContains;function r(m){if(!s(i))return;const y=i.getAttribute("slot");y&&(t[y]=t[y]||[],t[y].push({element:i,deepContains:m}))}let a=i.contains(e);if(a)return r(!0),!0;function c(m){return m.tagName==="SLOT"}function u(m){return c(m)?m.assignedElements():[]}function h(m){return m.nodeType===Node.DOCUMENT_FRAGMENT_NODE}function g(m){let y=!1;for(let _=0;_<m.length;_+=1){const x=m[_];if(x&&(s(x)||h(x))&&bt(x,e,t)){y=!0;break}}return y}function f(m){for(let y=0;y<m.children.length;y+=1){const _=m.children[y],x=n(_);if(x){a=x.deepContains||a;break}const p=u(_),k=[_.shadowRoot,...p];if(g(k)){a=!0;break}_.children.length>0&&f(_)}}return i instanceof HTMLElement&&i.shadowRoot&&(a=bt(i.shadowRoot,e,t),a)?(r(!0),!0):(f(i),r(a),a)}const Vu={tab:9};function Ru(i,e){const t=tr(i);let s;t.length>=2?s=[t[0],t[t.length-1]]:t.length===1?s=[t[0],t[0]]:s=[i,i],e.shiftKey&&s.reverse();const[n,o]=s,r=Is();r===i||t.includes(r)&&o!==r||(e.preventDefault(),n.focus())}function zu(i){const e=tr(i),t=e.find(f=>f.hasAttribute("autofocus"))||i;let s,n;t===i&&(i.tabIndex=-1,i.style.setProperty("outline","none")),t.focus();function o(f){f.keyCode===Vu.tab&&Ru(i,f)}function r(){s=document.createElement("div"),s.style.display="none",s.setAttribute("data-is-tab-detection-element",""),i.insertBefore(s,i.children[0]),n=new MutationObserver(f=>{for(const m of f)if(m.type==="childList"){const y=!Array.from(i.children).find(x=>x.hasAttribute("data-is-tab-detection-element")),_=Array.from(m.addedNodes).find(x=>x instanceof HTMLElement&&x.hasAttribute("data-is-tab-detection-element"));y&&!_&&(n.disconnect(),r())}}),n.observe(i,{childList:!0})}function a(){return s.compareDocumentPosition(document.activeElement)===Node.DOCUMENT_POSITION_PRECEDING}function c({resetToRoot:f=!1}={}){if(bt(i,Is()))return;let m;f?m=i:m=e[a()?0:e.length-1],m&&m.focus()}function u(){window.removeEventListener("focusin",u),c()}function h(){setTimeout(()=>{bt(i,Is())||c({resetToRoot:!0})}),window.addEventListener("focusin",u)}function g(){window.removeEventListener("keydown",o),window.removeEventListener("focusin",u),window.removeEventListener("focusout",h),n.disconnect(),Array.from(i.children).includes(s)&&i.removeChild(s),i.style.removeProperty("outline")}return window.addEventListener("keydown",o),window.addEventListener("focusout",h),r(),{disconnect:g}}const Wn=F`
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
`,gt={supportsAdoptingStyleSheets:window.ShadowRoot&&(window.ShadyCSS===void 0||window.ShadyCSS.nativeShadow)&&"adoptedStyleSheets"in Document.prototype&&"replace"in CSSStyleSheet.prototype,adoptStyle:void 0,adoptStyles:void 0},ps=new WeakMap;function Pu(i){return Array.from(i.cssRules).map(e=>e.cssText).join("")}function Bu(i,e,{teardown:t=!1}={}){const s=i===document?document.body:i,n=e.cssText||Pu(e);if(t){const o=Array.from(s.querySelectorAll("style"));for(const r of o)if(r.textContent===n){r.remove();break}}else{const o=document.createElement("style"),r=window.litNonce;r!==void 0&&o.setAttribute("nonce",r),o.textContent=n,s.appendChild(o)}}function Uu(i,e,{teardown:t=!1}={}){let s=!1;i&&!ps.has(i)&&ps.set(i,[]);const n=ps.get(i)??[],o=n.find(r=>e===r);return o&&t?n.splice(n.indexOf(e),1):!o&&!t?n.push(e):(o&&!t||!o&&t)&&(s=!0),{haltFurtherExecution:s}}function Hu(i,e,{teardown:t=!1}={}){const{haltFurtherExecution:s}=Uu(i,e,{teardown:t});if(s)return;if(!gt.supportsAdoptingStyleSheets||Ei.isIOS){Bu(i,e,{teardown:t});return}const n=e instanceof CSSStyleSheet?e:e.styleSheet;if(!n)throw new Error("Please provide a CSSResultOrNative style");t?i.adoptedStyleSheets.includes(n)&&i.adoptedStyleSheets.splice(i.adoptedStyleSheets.indexOf(n),1):i.adoptedStyleSheets=[...i.adoptedStyleSheets,n]}function qu(i,e,{teardown:t=!1}={}){for(const s of e)gt.adoptStyle(i,s,{teardown:t})}gt.adoptStyle=Hu;gt.adoptStyles=qu;function Wu({wrappingDialogNodeL1:i,contentWrapperNodeL2:e,contentNodeL3:t}){if(!(e.isConnected||t.isConnected))throw new Error('[OverlayController] Could not find a render target, since the provided contentNode is not connected to the DOM. Make sure that it is connected, e.g. by doing "document.body.appendChild(contentNode)", before passing it on.');let s;const n=document.createComment("tempMarker");e.isConnected?(s=e.parentElement||e.getRootNode(),s.insertBefore(n,e),i.appendChild(e)):t.assignedSlot?(s=t.assignedSlot.parentElement||t.assignedSlot.getRootNode(),s.insertBefore(n,t.assignedSlot),i.appendChild(e),e.appendChild(t.assignedSlot)):(s=t.parentElement||t.getRootNode(),s.insertBefore(n,t),i.appendChild(e),e.appendChild(t)),s.insertBefore(i,n),s?.removeChild(n)}async function ju(){return E(()=>import("./popper.js"),[],import.meta.url)}const jn=new WeakMap;class it extends EventTarget{constructor(e={},t=Tu){super(),this.manager=t,this.__sharedConfig=e,this.__activeElementRightBeforeHide=null,this.config={},this._defaultConfig={placementMode:void 0,contentNode:e.contentNode,contentWrapperNode:e.contentWrapperNode,invokerNode:e.invokerNode,backdropNode:e.backdropNode,referenceNode:void 0,elementToFocusAfterHide:e.invokerNode,inheritsReferenceWidth:"none",hasBackdrop:!1,isBlocking:!1,preventsScroll:!1,trapsKeyboardFocus:!1,hidesOnEsc:!1,hidesOnOutsideEsc:!1,hidesOnOutsideClick:!1,isTooltip:!1,isAlertDialog:!1,invokerRelation:"description",visibilityTriggerFunction:void 0,handlesAccessibility:!1,popperConfig:{placement:"top",strategy:"fixed",modifiers:[{name:"preventOverflow",enabled:!0,options:{boundariesElement:"viewport",padding:8}},{name:"flip",options:{boundariesElement:"viewport",padding:16}},{name:"offset",enabled:!0,options:{offset:[0,8]}},{name:"arrow",enabled:!1}]},viewportConfig:{placement:"center"},zIndex:9999},this._contentId=`overlay-content--${Math.random().toString(36).slice(2,10)}`,this.__originalAttrs=new Map,this.updateConfig(e),this.__hasActiveTrapsKeyboardFocus=!1,this.__hasActiveBackdrop=!0,this.__escKeyHandler=this.__escKeyHandler.bind(this),this.__cancelHandler=this.__cancelHandler.bind(this)}get invoker(){return this.invokerNode}get content(){return this.__wrappingDialogNode}get placementMode(){return this.config?.placementMode}get invokerNode(){return this.config?.invokerNode}get referenceNode(){return this.config?.referenceNode}get contentNode(){return this.config?.contentNode}get contentWrapperNode(){return this.__contentWrapperNode||this.config?.contentWrapperNode}get backdropNode(){return this.__backdropNode||this.config?.backdropNode}get elementToFocusAfterHide(){return this.__elementToFocusAfterHide||this.config?.elementToFocusAfterHide}get hasBackdrop(){return!!this.backdropNode||this.config?.hasBackdrop}get isBlocking(){return this.config?.isBlocking}get preventsScroll(){return this.config?.preventsScroll}get trapsKeyboardFocus(){return this.config?.trapsKeyboardFocus}get hidesOnEsc(){return this.config?.hidesOnEsc}get hidesOnOutsideClick(){return this.config?.hidesOnOutsideClick}get hidesOnOutsideEsc(){return this.config?.hidesOnOutsideEsc}get inheritsReferenceWidth(){return this.config?.inheritsReferenceWidth}get handlesAccessibility(){return this.config?.handlesAccessibility}get isTooltip(){return this.config?.isTooltip}get isAlertDialog(){return this.config?.isAlertDialog}get invokerRelation(){return this.config?.invokerRelation}get popperConfig(){return this.config?.popperConfig}get viewportConfig(){return this.config?.viewportConfig}get visibilityTriggerFunction(){return this.config?.visibilityTriggerFunction}get _referenceNode(){return this.referenceNode||this.invokerNode}set elevation(e){this.__wrappingDialogNode.style.zIndex=`${this.config.zIndex+e}`}get elevation(){return Number(this.contentWrapperNode?.style.zIndex)}updateConfig(e){this.teardown(),this.__prevConfig=this.config,this.config={...this._defaultConfig,...this.__sharedConfig,...e,popperConfig:{...this._defaultConfig.popperConfig||{},...this.__sharedConfig.popperConfig||{},...e.popperConfig||{},modifiers:[...this._defaultConfig.popperConfig?.modifiers||[],...this.__sharedConfig.popperConfig?.modifiers||[],...e.popperConfig?.modifiers||[]]}},this.__validateConfiguration(this.config),this._init(),this.__elementToFocusAfterHide=void 0,this.#e()||this.manager.add(this)}#e(){return!!this.manager.list.find(e=>this===e)}__validateConfiguration(e){if(!e.placementMode)throw new Error('[OverlayController] You need to provide a .placementMode ("global"|"local")');if(!["global","local"].includes(e.placementMode))throw new Error(`[OverlayController] "${e.placementMode}" is not a valid .placementMode, use ("global"|"local")`);if(!e.contentNode)throw new Error("[OverlayController] You need to provide a .contentNode");if(e.isTooltip&&!e.handlesAccessibility)throw new Error("[OverlayController] .isTooltip only takes effect when .handlesAccessibility is enabled")}_init(){this.__contentHasBeenInitialized||(this.__initContentDomStructure(),this.__contentHasBeenInitialized=!0),this.contentWrapperNode.removeAttribute("style"),this.contentWrapperNode.removeAttribute("class"),this.placementMode==="local"&&(it.popperModule||(it.popperModule=ju())),this.__handleOverlayStyles({phase:"init"}),this._handleFeatures({phase:"init"})}__handleOverlayStyles({phase:e}){const t=this.contentWrapperNode?.getRootNode();e==="init"?gt.adoptStyle(t,Wn):e==="teardown"&&gt.adoptStyle(t,Wn,{teardown:!0})}__initContentDomStructure(){const e=document.createElement(this.config?._noDialogEl?"div":"dialog");e.setAttribute("role","none"),e.setAttribute("data-overlay-outer-wrapper",""),e.style.cssText=`display:none; z-index: ${this.config.zIndex}; padding: 0;`,this.__wrappingDialogNode=e,this.config?.contentWrapperNode||(this.__contentWrapperNode=document.createElement("div")),this.contentWrapperNode.setAttribute("data-id","content-wrapper"),Wu({wrappingDialogNodeL1:e,contentWrapperNodeL2:this.contentWrapperNode,contentNodeL3:this.contentNode}),e.open=!0,this.isTooltip&&e.setAttribute("tabindex","-1"),this.__wrappingDialogNode.style.display="none",this.contentWrapperNode.style.zIndex="1",getComputedStyle(this.contentNode).position==="absolute"&&(this.contentNode.style.position="static"),HTMLDialogElement&&"closedBy"in HTMLDialogElement.prototype?e.closedBy="none":(e.addEventListener("keydown",s=>{s.key==="Escape"&&s.preventDefault()}),e.addEventListener("keyup",s=>{s.key==="Escape"&&s.preventDefault()}),e.addEventListener("cancel",s=>{s.stopPropagation()}),e.addEventListener("close",s=>{s.stopPropagation()}))}_handleZIndex({phase:e}){if(this.placementMode==="local"&&e==="setup"){const t=Number(getComputedStyle(this.contentNode).zIndex);(t<1||Number.isNaN(t))&&(this.contentNode.style.zIndex="1")}}__setupTeardownAccessibility({phase:e}){if(e==="init"){this.__storeOriginalAttrs(this.contentNode,["role","id"]);const t=this.trapsKeyboardFocus;if(this.invokerNode){const s=["aria-labelledby","aria-describedby"];t||s.push("aria-expanded"),this.__storeOriginalAttrs(this.invokerNode,s)}this.contentNode.id||this.contentNode.setAttribute("id",this._contentId),this.isTooltip?(this.invokerNode&&this.invokerNode.setAttribute(this.invokerRelation==="label"?"aria-labelledby":"aria-describedby",this._contentId),this.contentNode.setAttribute("role","tooltip")):(this.invokerNode&&!t&&this.invokerNode.setAttribute("aria-expanded",`${this.isShown}`),this.isAlertDialog?this.contentNode.setAttribute("role","alertdialog"):this.contentNode.getAttribute("role")||this.contentNode.setAttribute("role","dialog"))}else e==="teardown"&&this.__restoreOriginalAttrs()}__storeOriginalAttrs(e,t){const s={};t.forEach(n=>{s[n]=e.getAttribute(n)}),this.__originalAttrs.set(e,s)}__restoreOriginalAttrs(){for(const[e,t]of this.__originalAttrs)Object.entries(t).forEach(([s,n])=>{n!==null?e.setAttribute(s,n):e.removeAttribute(s)});this.__originalAttrs.clear()}get isShown(){return this.__wrappingDialogNode?.style.display!=="none"}async show(e=this.elementToFocusAfterHide){if(this._showComplete&&await this._showComplete,this._showComplete=new Promise(s=>{this._showResolve=s}),this.manager&&this.manager.show(this),this.isShown){this._showResolve();return}const t=new CustomEvent("before-show",{cancelable:!0});this.dispatchEvent(t),t.defaultPrevented||("HTMLDialogElement"in window&&this.__wrappingDialogNode instanceof HTMLDialogElement&&(this.__wrappingDialogNode.open=!0),this.__wrappingDialogNode.style.display="",this._keepBodySize({phase:"before-show"}),await this._handleFeatures({phase:"show"}),this._keepBodySize({phase:"show"}),await this._handlePosition({phase:"show"}),this.__elementToFocusAfterHide=e,this.dispatchEvent(new Event("show")),await this._transitionShow({backdropNode:this.backdropNode,contentNode:this.contentNode})),this._showResolve()}async _handlePosition({phase:e}){if(this.placementMode==="global"){const t=`overlays__overlay-container--${this.viewportConfig.placement}`;e==="show"?(this.contentWrapperNode.classList.add("overlays__overlay-container"),this.contentWrapperNode.classList.add(t),this.contentNode.classList.add("overlays__overlay")):e==="hide"&&(this.contentWrapperNode.classList.remove("overlays__overlay-container"),this.contentWrapperNode.classList.remove(t),this.contentNode.classList.remove("overlays__overlay"))}else this.placementMode==="local"&&e==="show"&&(await this.__createPopperInstance(),this._popper.forceUpdate())}_keepBodySize({phase:e}){if(this.preventsScroll)switch(e){case"before-show":this.__bodyClientWidth=document.body.clientWidth,this.__bodyClientHeight=document.body.clientHeight,this.__bodyMarginRightInline=document.body.style.marginRight,this.__bodyMarginBottomInline=document.body.style.marginBottom;break;case"show":{if(window.getComputedStyle){const r=window.getComputedStyle(document.body);this.__bodyMarginRight=parseInt(r.getPropertyValue("margin-right"),10),this.__bodyMarginBottom=parseInt(r.getPropertyValue("margin-bottom"),10)}else this.__bodyMarginRight=0,this.__bodyMarginBottom=0;const t=document.body.clientWidth-this.__bodyClientWidth,s=document.body.clientHeight-this.__bodyClientHeight,n=this.__bodyMarginRight+t,o=this.__bodyMarginBottom+s;window.CSS?.number&&document.body.attributeStyleMap?.set?(document.body.attributeStyleMap.set("margin-right",CSS.px(n)),document.body.attributeStyleMap.set("margin-bottom",CSS.px(o))):(document.body.style.marginRight=`${n}px`,document.body.style.marginBottom=`${o}px`);break}case"hide":document.body.style.marginRight=this.__bodyMarginRightInline||"",document.body.style.marginBottom=this.__bodyMarginBottomInline||"";break}}async hide(){if(this._hideComplete=new Promise(t=>{this._hideResolve=t}),this.__activeElementRightBeforeHide=this.contentNode.getRootNode().activeElement,this.manager&&this.manager.hide(this),!this.isShown){this._hideResolve();return}const e=new CustomEvent("before-hide",{cancelable:!0});this.dispatchEvent(e),e.defaultPrevented||(await this._transitionHide({backdropNode:this.backdropNode,contentNode:this.contentNode}),"HTMLDialogElement"in window&&this.__wrappingDialogNode instanceof HTMLDialogElement&&this.__wrappingDialogNode.close(),this.__wrappingDialogNode.style.display="none",this._handleFeatures({phase:"hide"}),this._keepBodySize({phase:"hide"}),this.dispatchEvent(new Event("hide")),this._restoreFocus()),this._hideResolve()}async transitionHide(e){}async _transitionHide({backdropNode:e,contentNode:t}){await this.transitionHide({backdropNode:e,contentNode:t}),this._handlePosition({phase:"hide"}),e&&e.classList.remove("overlays__backdrop--animation-in")}async transitionShow(e){}async _transitionShow(e){await this.transitionShow({backdropNode:this.backdropNode,contentNode:this.contentNode}),e.backdropNode&&e.backdropNode.classList.add("overlays__backdrop--animation-in")}_restoreFocus(){this.__activeElementRightBeforeHide instanceof HTMLElement&&this.contentNode.contains(this.__activeElementRightBeforeHide)&&(this.elementToFocusAfterHide instanceof HTMLElement?(this.elementToFocusAfterHide.focus(),this.elementToFocusAfterHide.scrollIntoView({block:"nearest"})):this.__activeElementRightBeforeHide.blur())}async toggle(){return this.isShown?this.hide():this.show()}_handleFeatures({phase:e}){this._handleZIndex({phase:e}),this.preventsScroll&&this._handlePreventsScroll({phase:e}),this.isBlocking&&this._handleBlocking({phase:e}),this.hasBackdrop&&this._handleBackdrop({phase:e}),this.trapsKeyboardFocus&&this._handleTrapsKeyboardFocus({phase:e}),this.hidesOnEsc&&this._handleHidesOnEsc({phase:e}),this.hidesOnOutsideEsc&&this._handleHidesOnOutsideEsc({phase:e}),this.hidesOnOutsideClick&&this._handleHidesOnOutsideClick({phase:e}),this.handlesAccessibility&&this._handleAccessibility({phase:e}),this.inheritsReferenceWidth&&this._handleInheritsReferenceWidth(),this.visibilityTriggerFunction&&this._handleVisibilityTriggers({phase:e})}_handleVisibilityTriggers({phase:e}){typeof this.visibilityTriggerFunction=="function"&&(e==="init"&&(this.__visibilityTriggerHandler=this.visibilityTriggerFunction({phase:e,controller:this})),this.__visibilityTriggerHandler[e]&&this.__visibilityTriggerHandler[e]())}_handlePreventsScroll({phase:e}){switch(e){case"show":this.manager.requestToPreventScroll();break;case"hide":this.manager.requestToEnableScroll();break}}_handleBlocking({phase:e}){switch(e){case"show":this.manager.requestToShowOnly(this);break;case"hide":this.manager.retractRequestToShowOnly(this);break}}get hasActiveBackdrop(){return this.__hasActiveBackdrop}_handleBackdrop({phase:e}){switch(e){case"init":{this.__backdropInitialized||(this.config?.backdropNode||(this.__backdropNode=document.createElement("div"),this.__backdropNode.classList.add("overlays__backdrop")),this.__wrappingDialogNode.prepend(this.backdropNode),this.__backdropInitialized=!0);break}case"show":this.config.hasBackdrop&&this.backdropNode.classList.add("overlays__backdrop--visible"),this.__hasActiveBackdrop=!0;break;case"hide":case"teardown":this.backdropNode.classList.remove("overlays__backdrop--visible"),this.__hasActiveBackdrop=!1;break}}get hasActiveTrapsKeyboardFocus(){return this.__hasActiveTrapsKeyboardFocus}_handleTrapsKeyboardFocus({phase:e}){e==="show"?("showModal"in this.__wrappingDialogNode&&(this.__wrappingDialogNode.close(),this.__wrappingDialogNode.showModal()),this.enableTrapsKeyboardFocus()):(e==="hide"||e==="teardown")&&this.disableTrapsKeyboardFocus()}enableTrapsKeyboardFocus(){if(this.__hasActiveTrapsKeyboardFocus)return;this.manager&&this.manager.disableTrapsKeyboardFocusForAll(),this.contentNode.shadowRoot&&console.warn("[overlays]: For best accessibility (compatibility with Safari + VoiceOver), provide a contentNode that is not a host for a shadow root"),this._containFocusHandler=zu(this.contentNode),this.__hasActiveTrapsKeyboardFocus=!0,this.manager&&this.manager.informTrapsKeyboardFocusGotEnabled(this.placementMode)}disableTrapsKeyboardFocus({findNewTrap:e=!0}={}){this.__hasActiveTrapsKeyboardFocus&&(this._containFocusHandler&&(this._containFocusHandler.disconnect(),this._containFocusHandler=void 0),this.__hasActiveTrapsKeyboardFocus=!1,this.manager&&this.manager.informTrapsKeyboardFocusGotDisabled({disabledCtrl:this,findNewTrap:e}))}__cancelHandler(e){e.preventDefault()}__escKeyHandler(e){if(e.key!=="Escape"||jn.has(e))return;(e.composedPath().includes(this.contentNode)||bt(this.contentNode,e.target))&&(this.hide(),jn.set(e,this))}#t=e=>{e.key!=="Escape"||e.composedPath().includes(this.contentNode)||bt(this.contentNode,e.target)||this.hide()};_handleHidesOnEsc({phase:e}){e==="show"?(this.contentNode.addEventListener("keyup",this.__escKeyHandler),this.invokerNode&&this.invokerNode.addEventListener("keyup",this.__escKeyHandler)):(e==="hide"||e==="teardown")&&(this.contentNode.removeEventListener("keyup",this.__escKeyHandler),this.invokerNode&&this.invokerNode.removeEventListener("keyup",this.__escKeyHandler))}_handleHidesOnOutsideEsc({phase:e}){e==="show"?document.addEventListener("keyup",this.#t):(e==="hide"||e==="teardown")&&document.removeEventListener("keyup",this.#t)}_handleInheritsReferenceWidth(){if(!this._referenceNode||this.placementMode==="global")return;const e=`${this._referenceNode.getBoundingClientRect().width}px`;switch(this.inheritsReferenceWidth){case"max":this.contentWrapperNode.style.maxWidth=e;break;case"full":this.contentWrapperNode.style.width=e;break;case"min":this.contentWrapperNode.style.minWidth=e,this.contentWrapperNode.style.width="auto";break}}_handleHidesOnOutsideClick({phase:e}){const t=e==="show"?"addEventListener":"removeEventListener";if(e==="show"){let s=!1,n=!1;this.__onInsideMouseDown=()=>{s=!0},this.__onInsideMouseUp=()=>{n=!0},this.__onDocumentMouseUp=()=>{setTimeout(()=>{!s&&!n&&this.hide(),s=!1,n=!1})},this.__onWindowBlur=()=>{setTimeout(()=>{this.hide()})}}this.contentWrapperNode[t]("mousedown",this.__onInsideMouseDown,!0),this.contentWrapperNode[t]("mouseup",this.__onInsideMouseUp,!0),this.invokerNode&&(this.invokerNode[t]("mousedown",this.__onInsideMouseDown,!0),this.invokerNode[t]("mouseup",this.__onInsideMouseUp,!0)),document.documentElement[t]("mouseup",this.__onDocumentMouseUp,!0),window[t]("blur",this.__onWindowBlur)}_handleAccessibility({phase:e}){(e==="init"||e==="teardown")&&this.__setupTeardownAccessibility({phase:e});const t=this.trapsKeyboardFocus;this.invokerNode&&!this.isTooltip&&!t&&this.invokerNode.setAttribute("aria-expanded",`${e==="show"}`)}teardown(){this.__handleOverlayStyles({phase:"teardown"}),this._handleFeatures({phase:"teardown"}),this.#e()&&this.manager.remove(this)}async __createPopperInstance(){if(this._popper&&(this._popper.destroy(),this._popper=void 0),it.popperModule!==void 0){const{createPopper:e}=await it.popperModule;this._popper=e(this._referenceNode,this.contentWrapperNode,{...this.config?.popperConfig})}}_hasDisabledInvoker(){return this.invokerNode?this.invokerNode.disabled||this.invokerNode.getAttribute("aria-disabled")==="true":!1}}it.popperModule=void 0;function ir(i,e){if(typeof i!="object"||typeof e!="object"||i===null||e===null)return i===e;const t=Object.keys(i),s=Object.keys(e);if(t.length!==s.length)return!1;const n=o=>ir(i[o],e[o]);return t.every(n)}const Ku=i=>class extends i{static get properties(){return{opened:{type:Boolean,reflect:!0}}}#e=!1;constructor(){super(),this.opened=!1,this.config={},this.toggle=this.toggle.bind(this),this.open=this.open.bind(this),this.close=this.close.bind(this)}get config(){return this.__config}set config(t){const s=!ir(this.config,t);this._overlayCtrl&&s&&this._overlayCtrl.updateConfig(t),this.__config=t,this._overlayCtrl&&s&&this.__syncToOverlayController()}requestUpdate(t,s,n){super.requestUpdate(t,s,n),t==="opened"&&this.opened!==s&&this.dispatchEvent(new CustomEvent("opened-changed",{detail:{opened:this.opened}}))}_defineOverlay({contentNode:t,invokerNode:s,referenceNode:n,backdropNode:o,contentWrapperNode:r}){const a=this._defineOverlayConfig()||{};return new it({contentNode:t,invokerNode:s,referenceNode:n,backdropNode:o,contentWrapperNode:r,...a,...this.config,popperConfig:{...a.popperConfig||{},...this.config?.popperConfig||{},modifiers:[...a.popperConfig?.modifiers||[],...this.config?.popperConfig?.modifiers||[]]}})}_defineOverlayConfig(){return{placementMode:"local"}}updated(t){super.updated(t),t.has("opened")&&this._overlayCtrl&&!this.__blockSyncToOverlayCtrl&&this.__syncToOverlayController()}_setupOpenCloseListeners(){this.__closeEventInContentNodeHandler=t=>{t.stopPropagation(),this._overlayCtrl.hide()},this._overlayContentNode&&this._overlayContentNode.addEventListener("close-overlay",this.__closeEventInContentNodeHandler)}_teardownOpenCloseListeners(){this._overlayContentNode&&this._overlayContentNode.removeEventListener("close-overlay",this.__closeEventInContentNodeHandler)}connectedCallback(){super.connectedCallback(),this.updateComplete.then(()=>{this.isConnected&&(this.#e||(this._setupOverlayCtrl(),this.#e=!0))})}async disconnectedCallback(){super.disconnectedCallback(),await this._isPermanentlyDisconnected()&&(this._teardownOverlayCtrl(),this.#e=!1)}static enabledWarnings=super.enabledWarnings?.filter(t=>t!=="change-in-update")||[];get _overlayInvokerNode(){return Array.from(this.children).find(t=>t.slot==="invoker")}get _overlayReferenceNode(){}get _overlayBackdropNode(){return this.__cachedOverlayBackdropNode||(this.__cachedOverlayBackdropNode=Array.from(this.children).find(t=>t.slot==="backdrop")),this.__cachedOverlayBackdropNode}get _overlayContentNode(){return this._cachedOverlayContentNode||(this._cachedOverlayContentNode=Array.from(this.children).find(t=>t.slot==="content")||this.config.contentNode),this._cachedOverlayContentNode}get _overlayContentWrapperNode(){return this.shadowRoot?.querySelector("#overlay-content-node-wrapper")}_setupOverlayCtrl(){if(this.#e)return;const t={contentNode:this._overlayContentNode,contentWrapperNode:this._overlayContentWrapperNode,invokerNode:this._overlayInvokerNode,referenceNode:this._overlayReferenceNode,backdropNode:this._overlayBackdropNode};this._overlayCtrl?this._overlayCtrl.updateConfig(t):this._overlayCtrl=this._defineOverlay(t),this.__syncToOverlayController(),this.__setupSyncFromOverlayController(),this._setupOpenCloseListeners()}_teardownOverlayCtrl(){this._overlayCtrl&&(this._teardownOpenCloseListeners(),this.__teardownSyncFromOverlayController(),this._overlayCtrl.teardown())}async _setOpenedWithoutPropertyEffects(t){this.__blockSyncToOverlayCtrl=!0,this.opened=t,await this.updateComplete,this.__blockSyncToOverlayCtrl=!1}__setupSyncFromOverlayController(){this.__onOverlayCtrlShow=()=>{this.opened=!0},this.__onOverlayCtrlHide=()=>{this.opened=!1},this.__onBeforeShow=t=>{const s=new CustomEvent("before-opened",{cancelable:!0});this.dispatchEvent(s),s.defaultPrevented&&(this._setOpenedWithoutPropertyEffects(this._overlayCtrl.isShown),t.preventDefault())},this.__onBeforeHide=t=>{const s=new CustomEvent("before-closed",{cancelable:!0});this.dispatchEvent(s),s.defaultPrevented&&(this._setOpenedWithoutPropertyEffects(this._overlayCtrl.isShown),t.preventDefault())},this._overlayCtrl.addEventListener("show",this.__onOverlayCtrlShow),this._overlayCtrl.addEventListener("hide",this.__onOverlayCtrlHide),this._overlayCtrl.addEventListener("before-show",this.__onBeforeShow),this._overlayCtrl.addEventListener("before-hide",this.__onBeforeHide)}__teardownSyncFromOverlayController(){this._overlayCtrl.removeEventListener("show",this.__onOverlayCtrlShow),this._overlayCtrl.removeEventListener("hide",this.__onOverlayCtrlHide),this._overlayCtrl.removeEventListener("before-show",this.__onBeforeShow),this._overlayCtrl.removeEventListener("before-hide",this.__onBeforeHide)}__syncToOverlayController(){this.opened?this._overlayCtrl.show():this._overlayCtrl.hide()}async toggle(){await this._overlayCtrl.toggle()}async open(){await this._overlayCtrl.show()}async close(){await this._overlayCtrl.hide()}repositionOverlay(){const t=this._overlayCtrl;t.placementMode==="local"&&t._popper&&t._popper.update()}async _isPermanentlyDisconnected(){return await this.updateComplete,!this.isConnected}},sr=ee(Ku);function Gu(){return{visibilityTriggerFunction:({controller:i})=>{function e(){i._hasDisabledInvoker()||i.toggle()}return{init:()=>{i.invokerNode?.addEventListener("click",e)},teardown:()=>{i.invokerNode?.removeEventListener("click",e)}}}}}const nr=()=>({placementMode:"local",inheritsReferenceWidth:"min",hidesOnOutsideClick:!0,hidesOnEsc:!0,popperConfig:{placement:"bottom-start",modifiers:[{name:"offset",enabled:!1}]},handlesAccessibility:!0,...Gu()});var At=class extends sr(H){_defineOverlayConfig(){return{...nr()}}_addEventListeners(){this.actionItems.forEach(e=>{e.addEventListener("click",t=>{t.target?.dispatchEvent(new Event("close-overlay",{bubbles:!0}))})})}_setupInvoker(){let e=this.invokerNodes[0];e&&(e.setAttribute("id",`invoker-${this.uid}`),e.setAttribute("aria-controls",`content-${this.uid}`))}_setupContent(){let e=this.contentNodes[0];e&&(e.setAttribute("id",`content-${this.uid}`),e.setAttribute("role","none"))}_setupOverlayCtrl(){super._setupOverlayCtrl(),this._setupInvoker(),this._setupContent()}firstUpdated(){this.uid=Kt(),this._addEventListeners()}render(){return w`
      <slot name="invoker"></slot>
      <slot name="backdrop"></slot>
      <slot name="content"></slot>
    `}};At.styles=F`
    ::slotted([slot='content']) {
      font-size: var(--c-text-base);
      font-weight: 400;
      display: grid;
      gap: var(--c-spacing-xs);
      border: 1px solid var(--c-color-neutral-border-subtle);
      border-radius: var(--c-radius-md);
      background-color: var(--c-bg-overlay);
      box-shadow: var(--c-shadow-sm);
      padding: var(--c-spacing-sm);
    }

    ::slotted(hr) {
      margin: 0;
    }
  `,T([ci({selector:"craft-action-item"})],At.prototype,"actionItems",void 0),T([ci({slot:"invoker"})],At.prototype,"invokerNodes",void 0),T([ci({slot:"content"})],At.prototype,"contentNodes",void 0),customElements.get("craft-action-menu")||customElements.define("craft-action-menu",At);const Dt=new WeakMap;function or(i,e){Array.from(i.childNodes).forEach(t=>{if(t.nodeName==="#text"){const s=new RegExp(`^(.*?)(${e})(.*)$`,"i"),n=t.nodeValue.match(s);if(n){const o=document.createTextNode(n[1]);i.appendChild(o);const r=document.createElement("b");r.textContent=n[2],i.appendChild(r);const a=document.createTextNode(n[3]);i.appendChild(a),i.removeChild(t),Dt.set(i,()=>{i.appendChild(t),i.contains(o)&&o.parentNode!==null&&o.parentNode.removeChild(o),i.contains(r)&&r.parentNode!==null&&r.parentNode.removeChild(r),i.contains(a)&&a.parentNode!==null&&a.parentNode.removeChild(a)})}}else or(t,e)})}function rr(i){Dt.has(i)&&Dt.get(i)(),Array.from(i.childNodes).forEach(e=>{e.nodeName==="#text"?Dt.has(e)&&Dt.get(e)():rr(e)})}class Yu extends Pi{static get validatorName(){return"MatchesOption"}execute(e,t,s){return s?.node.modelValue instanceof ot}}function ai(i){return Array.isArray(i)?i:[i]}const Zu=i=>class extends Bi(i){static get properties(){return{allowCustomChoice:{type:Boolean,attribute:"allow-custom-choice"},modelValue:{type:Object}}}get modelValue(){return this.__getChoicesFrom(super.modelValue)}set modelValue(t){if(super.modelValue=t,t==null||t==="")this._customChoices=new Set;else if(this.allowCustomChoice){const s=this.modelValue;this._customChoices=new Set(ai(t)),this.requestUpdate("modelValue",s)}}get formattedValue(){return this.__getChoicesFrom(super.formattedValue)}set formattedValue(t){if(super.formattedValue=t,t==null)this._customChoices=new Set;else if(this.allowCustomChoice){const s=this.modelValue;this._customChoices=new Set(ai(t).map(n=>this.formElements.find(o=>o.formattedValue===n)?.modelValue||n)),this.requestUpdate("modelValue",s)}}get serializedValue(){return this.__getChoicesFrom(super.serializedValue)}set serializedValue(t){if(super.serializedValue=t,t==null)this._customChoices=new Set;else if(this.allowCustomChoice){const s=this.modelValue;this._customChoices=new Set(ai(t).map(n=>this.formElements.find(o=>o.serializedValue===n)?.modelValue||n)),this.requestUpdate("modelValue",s)}}get customChoices(){if(!this.allowCustomChoice)return[];const t=this._getCheckedElements();return Array.from(this._customChoices).filter(s=>!t.some(n=>n.choiceValue===s))}constructor(){super(),this.allowCustomChoice=!1,this._customChoices=new Set}__getChoicesFrom(t){const s=t;return this.allowCustomChoice?this.multipleChoice?[...ai(s),...this.customChoices]:s===""?this._customChoices.values().next().value||"":s:s}_isEmpty(){return super._isEmpty()&&this._customChoices.size===0}clear(){this._customChoices=new Set,super.clear()}parser(t){return this.allowCustomChoice&&Array.isArray(t)?t.filter(s=>s.trim()!==""):t}},Xu=ee(Zu),fs=new WeakMap;class Qu extends zi(sr(Xu(Id))){static get properties(){return{autocomplete:{type:String,reflect:!0},matchMode:{type:String,attribute:"match-mode"},showAllOnEmpty:{type:Boolean,attribute:"show-all-on-empty"},requireOptionMatch:{type:Boolean},allowCustomChoice:{type:Boolean,attribute:"allow-custom-choice"},__shouldAutocompleteNextUpdate:Boolean}}static get styles(){return[...super.styles,F`
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
      `]}static get localizeNamespaces(){return[{"lion-combobox":e=>{switch(e){case"bg-BG":case"bg":return E(()=>import("./bg.js"),[],import.meta.url);case"cs-CZ":case"cs":return E(()=>import("./cs.js"),[],import.meta.url);case"de-AT":case"de-DE":case"de":return E(()=>import("./de.js"),[],import.meta.url);case"en-AU":case"en-GB":case"en-PH":case"en-US":case"en":return E(()=>import("./en.js"),[],import.meta.url);case"es-ES":case"es":return E(()=>import("./es.js"),[],import.meta.url);case"fr-FR":case"fr-BE":case"fr":return E(()=>import("./fr.js"),[],import.meta.url);case"hu-HU":case"hu":return E(()=>import("./hu.js"),[],import.meta.url);case"it-IT":case"it":return E(()=>import("./it.js"),[],import.meta.url);case"nl-BE":case"nl-NL":case"nl":return E(()=>import("./nl.js"),[],import.meta.url);case"pl-PL":case"pl":return E(()=>import("./pl.js"),[],import.meta.url);case"ro-RO":case"ro":return E(()=>import("./ro.js"),[],import.meta.url);case"ru-RU":case"ru":return E(()=>import("./ru.js"),[],import.meta.url);case"sk-SK":case"sk":return E(()=>import("./sk.js"),[],import.meta.url);case"uk-UA":case"uk":return E(()=>import("./uk.js"),[],import.meta.url);case"zh-CN":case"zh":return E(()=>import("./zh.js"),[],import.meta.url);default:return E(()=>import("./en.js"),[],import.meta.url)}}},...super.localizeNamespaces]}get modelValue(){const e=super.modelValue;return e!==""?e:this.parser(this.value)}set modelValue(e){super.modelValue=e}get value(){return this._inputNode?.value||this.__value||""}set value(e){this._inputNode?(this._inputNode.value=e,this.__value=void 0):this.__value=e}reset(){super.reset(),this.multipleChoice||(this.value=this._initialModelValue),this._resetListboxOptions()}_resetListboxOptions(){this.formElements.forEach((e,t)=>{this._unhighlightMatchedOption(e),!this.showAllOnEmpty||!this.opened?e.style.display="none":(e.style.display="",e.setAttribute("aria-posinset",`${t+1}`),e.setAttribute("aria-setsize",`${this.formElements.length}`),e.removeAttribute("aria-hidden"))})}_inputGroupInputTemplate(){return w`
      <div class="input-group__input">
        <slot name="selection-display"></slot>
        <slot name="input"></slot>
      </div>
    `}_overlayListboxTemplate(){return w`
      <div
        id="overlay-content-node-wrapper"
        role="dialog"
        aria-label="${this.msgLit("lion-combobox:optionsPopup")}"
      >
        <slot name="listbox"></slot>
      </div>
      <slot id="options-outlet"></slot>
    `}_groupTwoTemplate(){return w` ${super._groupTwoTemplate()} ${this._overlayListboxTemplate()}`}get slots(){return{...super.slots,input:()=>{if(this._ariaVersion==="1.1"){const e=document.createElement("div"),t=document.createElement("input");return t.style.cssText=`
          border: none;
          outline: none;
          width: 100%;
          height: 100%;
          font: inherit;
          background: inherit;
          color: inherit;
          border-radius: inherit;
          box-sizing: border-box;
          padding: 0;`,e.appendChild(t),e}return document.createElement("input")},listbox:super.slots.input}}get _comboboxNode(){return this.querySelector('[slot="input"]')}get _selectionDisplayNode(){return this.querySelector('[slot="selection-display"]')}get _inputNode(){return this._ariaVersion==="1.1"&&this._comboboxNode?this._comboboxNode.querySelector("input")||this._comboboxNode:this._comboboxNode}get _overlayContentNode(){return this._listboxNode}get _overlayReferenceNode(){return this.shadowRoot.querySelector(".input-group__container")}get _overlayInvokerNode(){return this._inputNode}get _listboxNode(){return this._overlayCtrl&&this._overlayCtrl.contentNode||Array.from(this.children).find(e=>e.slot==="listbox")}get _activeDescendantOwnerNode(){return this._inputNode}get requireOptionMatch(){return!this.allowCustomChoice}set requireOptionMatch(e){this.allowCustomChoice=!e}constructor(){super(),this.autocomplete="both",this.matchMode="all",this.showAllOnEmpty=!1,this.requireOptionMatch=!0,this.rotateKeyboardNavigation=!0,this.selectionFollowsFocus=!0,this.defaultValidators.push(new Yu),this._ariaVersion=Ei.isChromium?"1.1":"1.0",this._listboxReceivesNoFocus=!0,this._noTypeAhead=!0,this.__prevCboxValueNonSelected="",this.__prevCboxValue="",this.__hadUserIntendsInlineAutoFill=!1,this.__listboxContentChanged=!1,this._onKeyUp=this._onKeyUp.bind(this),this._textboxOnClick=this._textboxOnClick.bind(this),this._textboxOnInput=this._textboxOnInput.bind(this),this._textboxOnKeydown=this._textboxOnKeydown.bind(this)}connectedCallback(){super.connectedCallback(),this._selectionDisplayNode&&(this._selectionDisplayNode.comboboxElement=this),(this.disabled||this.readOnly)&&this.__setComboboxDisabledAndReadOnly()}requestUpdate(e,t,s){if(super.requestUpdate(e,t,s),(e==="disabled"||e==="readOnly")&&this.__setComboboxDisabledAndReadOnly(),e==="modelValue"&&this.modelValue&&this.modelValue!==t&&this._syncToTextboxCondition(this.modelValue,this._oldModelValue))if(this.multipleChoice)this._syncToTextboxMultiple(this.modelValue,this._oldModelValue);else{const n=this._getTextboxValueFromOption(this.formElements[this.checkedIndex]);this._setTextboxValue(n)}}parser(e){return this.requireOptionMatch&&this.checkedIndex===-1&&e!==""&&!Array.isArray(e)?new ot(e):super.parser(e)}__unsyncCheckedIndexOnInputChange(){const e=this._autoSelectCondition(),t=this.formElements[this.checkedIndex];if(!this.multipleChoice&&!e&&t){const s=this._getTextboxValueFromOption(t);this._inputNode.value.startsWith(s)||this.setCheckedIndex(-1)}}updated(e){super.updated(e),e.has("__shouldAutocompleteNextUpdate")&&this.__unsyncCheckedIndexOnInputChange(),e.has("opened")&&(this.opened&&(this.activeIndex=-1),!this.opened&&e.get("opened")!==void 0&&(this.__onOverlayClose(),this.activeIndex=-1)),e.has("autocomplete")&&this._inputNode.setAttribute("aria-autocomplete",this.autocomplete),e.has("disabled")&&this.setAttribute("aria-disabled",`${this.disabled}`),e.has("__shouldAutocompleteNextUpdate")&&this.__shouldAutocompleteNextUpdate&&(this._handleAutocompletion(),this.__shouldAutocompleteNextUpdate=!1,this.__listboxContentChanged=!1),typeof this._selectionDisplayNode?.onComboboxElementUpdated=="function"&&this._selectionDisplayNode.onComboboxElementUpdated(e)}matchCondition(e,t){let s=-1;const n=this._getTextboxValueFromOption(e);return typeof n=="string"&&typeof t=="string"&&(s=n.toLowerCase().indexOf(t.toLowerCase())),this.matchMode==="all"?s>-1:s===0}_showOverlayCondition({lastKey:e}){const t=["Tab","Escape"],s=["Enter"];return this.disabled||this.readOnly||e&&(t.includes(e)||!this.multipleChoice&&s.includes(e))?!1:this.filled||this.showAllOnEmpty||!this.filled&&this.multipleChoice&&this.__prevCboxValueNonSelected?!0:this.opened}_getTextboxValueFromOption(e){return e?e.choiceValue:this.modelValue instanceof ot?this.modelValue.viewValue:this.modelValue}_onListboxContentChanged(){super._onListboxContentChanged(),this.__shouldAutocompleteNextUpdate=!0,this.__listboxContentChanged=!0}_textboxOnInput(e){this.__shouldAutocompleteNextUpdate=!0,this.opened=this._showOverlayCondition({})}_textboxOnKeydown(e){e.key==="Tab"&&(this.opened=!1)}_listboxOnClick(e){super._listboxOnClick(e),this._inputNode.focus(),this.multipleChoice?(this._inputNode.value="",this._resetListboxOptions()):(this.activeIndex=-1,this.opened=!1)}_setTextboxValue(e){this._inputNode&&this._inputNode.value!==e&&(this._inputNode.value=e)}__onOverlayClose(){this.multipleChoice?this._syncToTextboxMultiple(this.modelValue,this._oldModelValue):this.checkedIndex!==-1&&this._syncToTextboxCondition(this.modelValue,this._oldModelValue,{phase:"overlay-close"})&&(this._inputNode.value=this._getTextboxValueFromOption(this.formElements[this.checkedIndex]))}_repropagationCondition(e){return super._repropagationCondition(e)||this.formElements.every(t=>!t.checked)}_onFilterMatch(e,t){this._highlightMatchedOption(e,t),e.style.display=""}_highlightMatchedOption(e,t){if(or(e,t),e.textContent){const s=document.createElement("span");s.setAttribute("aria-label",e.textContent.replace(/\s+/g," ")),Array.from(e.childNodes).forEach(n=>{s.appendChild(n)}),e.appendChild(s),fs.set(e,()=>{Array.from(s.childNodes).forEach(n=>{e.appendChild(n)}),e.contains(s)&&e.removeChild(s)})}}_onFilterUnmatch(e,t,s){this._unhighlightMatchedOption(e),e.style.display="none"}_unhighlightMatchedOption(e){rr(e),fs.has(e)&&fs.get(e)()}__computeUserIntendsAutoFill({prevValue:e,curValue:t}){const s=e.length<t.length,n=e.length&&t.length&&e[0].toLowerCase()!==t[0].toLowerCase();return s||n||this.__listboxContentChanged&&this.__hadUserIntendsInlineAutoFill}_handleAutocompletion(){const t=!(this._inputNode.selectionStart===this._inputNode.selectionEnd)&&this._inputNode.value.length!==this._inputNode.selectionStart,s=this._inputNode.value,n=this._inputNode.selectionStart,o=t&&n?s.slice(0,n):s,r=t||this.__hadSelectionLastAutofill?this.__prevCboxValueNonSelected:this.__prevCboxValue,a=!o,c=[];let u=!1;const h=this.__computeUserIntendsAutoFill({prevValue:r,curValue:o}),g=this.autocomplete==="both"||this.autocomplete==="inline",f=this._autoSelectCondition(),m=this.autocomplete==="inline"||this.autocomplete==="none";this.formElements.forEach((_,x)=>{const p=this.matchCondition(_,o);let k=!1;if(a?k=this.showAllOnEmpty:k=m||p,f&&!u&&p&&!_.disabled){const C=()=>{this.activeIndex=x,this.selectionFollowsFocus&&!this.multipleChoice&&this.setCheckedIndex(this.activeIndex),u=!0};if(h)if(g){const S=this._getTextboxValueFromOption(_);S&&typeof S=="string"&&typeof o=="string"&&S.toLowerCase().indexOf(o.toLowerCase())===0&&(this.__textboxInlineComplete(_),C())}else C()}_.onFilterUnmatch?_.onFilterUnmatch(o,r):this._onFilterUnmatch(_,o,r),_.setAttribute("aria-hidden","true"),_.removeAttribute("aria-posinset"),_.removeAttribute("aria-setsize"),k&&(c.push(_),_.onFilterMatch?_.onFilterMatch(o):this._onFilterMatch(_,o))});const y=c.length;c.forEach((_,x)=>{_.setAttribute("aria-posinset",`${x+1}`),_.setAttribute("aria-setsize",`${y}`),_.removeAttribute("aria-hidden")}),f&&!u&&!this.multipleChoice&&(this.setCheckedIndex(-1),r!==o&&(this.activeIndex=-1),this.modelValue=this.parser(s)),this.__prevCboxValueNonSelected=o,this.__prevCboxValue=this._inputNode.value,this.__hadSelectionLastAutofill=this._inputNode.value.length!==this._inputNode.selectionStart,this.__hadUserIntendsInlineAutoFill=h,this._overlayCtrl&&this._overlayCtrl._popper&&this._overlayCtrl._popper.update()}__textboxInlineComplete(e=this.formElements[this.activeIndex]){const t=this._getTextboxValueFromOption(e);if(this._inputNode.value!==t){const s=this._inputNode.value.length;this._inputNode.value=t,this._inputNode.selectionStart=s,this._inputNode.selectionEnd=this._inputNode.value.length}}_autoSelectCondition(){return this.autocomplete==="both"||this.autocomplete==="inline"}_setupListboxNode(){super._setupListboxNode(),this._listboxNode.removeAttribute("tabindex")}_defineOverlayConfig(){return{...nr(),elementToFocusAfterHide:void 0,invokerNode:this._comboboxNode,visibilityTriggerFunction:void 0}}_setupOverlayCtrl(){super._setupOverlayCtrl(),this.__shouldAutocompleteNextUpdate=!0,this.__setupCombobox()}_teardownOverlayCtrl(){super._teardownOverlayCtrl(),this.__teardownCombobox()}_setupOpenCloseListeners(){super._setupOpenCloseListeners(),this._inputNode.addEventListener("keyup",this._onKeyUp),this._inputNode.addEventListener("click",this._textboxOnClick)}_teardownOpenCloseListeners(){super._teardownOpenCloseListeners(),this._inputNode.removeEventListener("keyup",this._onKeyUp),this._inputNode.removeEventListener("click",this._textboxOnClick)}_listboxOnKeyDown(e){const{key:t}=e;switch(t){case"Escape":this.opened=!1,super._listboxOnKeyDown(e),this._setTextboxValue("");break;case"Backspace":case"Delete":this.requireOptionMatch?super._listboxOnKeyDown(e):this.opened=!1;break;case"Enter":this.opened&&e.preventDefault(),!this.requireOptionMatch&&this.multipleChoice&&(!this.formElements[this.activeIndex]||this.formElements[this.activeIndex].hasAttribute("aria-hidden")||!this.opened)?(this.modelValue=this.parser([...this.modelValue,this._inputNode.value]),this._inputNode.value="",this.opened=!1):(super._listboxOnKeyDown(e),this._resetListboxOptions()),this.multipleChoice?this._inputNode.value="":this.opened=!1;break;default:{super._listboxOnKeyDown(e);break}}}_syncToTextboxCondition(e,t,{phase:s}={}){return this.autocomplete==="both"||this.autocomplete==="inline"||!this.focused}_syncToTextboxMultiple(e,t=[]){if(this.requireOptionMatch){const s=e.filter(o=>!t.includes(o)),n=this.formElements.filter(o=>s.includes(o.choiceValue)).map(o=>this._getTextboxValueFromOption(o)).join(" ");this._setTextboxValue(n)}}_enhanceLightDomClasses(){const e=this.querySelector("[slot=input]");e&&e.classList.add("form-control")}__setComboboxDisabledAndReadOnly(){this._comboboxNode&&(this._comboboxNode.toggleAttribute("disabled",this.disabled),this._comboboxNode.setAttribute("aria-disabled",`${this.disabled}`),this._comboboxNode.toggleAttribute("readonly",this.readOnly),this._comboboxNode.setAttribute("aria-readonly",`${this.readOnly}`)),this._inputNode&&(this._inputNode.toggleAttribute("disabled",this.disabled),this._inputNode.toggleAttribute("readOnly",this.readOnly),this._inputNode.setAttribute("aria-readonly",`${this.readOnly}`),this._inputNode.tabIndex=this.disabled?-1:0)}__setupCombobox(){this._comboboxNode.setAttribute("role","combobox"),this._comboboxNode.setAttribute("aria-haspopup","listbox"),this._inputNode.setAttribute("aria-autocomplete",this.autocomplete),this._comboboxNode.setAttribute("aria-controls",this._listboxNode.id),this._ariaVersion==="1.1"?this._comboboxNode.setAttribute("aria-owns",this._listboxNode.id):this._inputNode.setAttribute("aria-owns",this._listboxNode.id),this._listboxNode.setAttribute("aria-labelledby",this._labelNode.id),this._inputNode.addEventListener("keydown",this._listboxOnKeyDown),this._inputNode.addEventListener("input",this._textboxOnInput),this._inputNode.addEventListener("keydown",this._textboxOnKeydown)}__teardownCombobox(){this._inputNode.removeEventListener("keydown",this._listboxOnKeyDown),this._inputNode.removeEventListener("input",this._textboxOnInput),this._inputNode.removeEventListener("keydown",this._textboxOnKeydown)}_onKeyUp(e){const t=e&&e.key;this.opened=this._showOverlayCondition({lastKey:t,currentValue:this._inputNode.value})}_textboxOnClick(e){this.opened=this._showOverlayCondition({})}clear(){this.value="",super.clear(),this.__shouldAutocompleteNextUpdate=!0}}var Ju=F`
  ${Vi}

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
    border: 1px solid var(--c-color-neutral-border-subtle);
    border-radius: var(--c-radius-md);
    background-color: var(--c-bg-overlay);
    box-shadow: var(--c-shadow-sm);
    padding: var(--c-spacing-sm);
  }

  .input-group__input {
    ${js}
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
`,eh=class extends Qu{static get styles(){return[...super.styles,Ju]}constructor(){super(),this.defaultValidators=[]}_inputGroupInputTemplate(){return w`
      <div class="input-group__input">
        <slot name="input"></slot>
        <craft-icon
          class="indicator"
          name="chevron-down"
          style="font-size: 0.8em"
        ></craft-icon>
      </div>
    `}parser(e){return e===""?super.parser(e):e}_getTextboxValueFromOption(e){return e?e.textContent?.trim()||"":super._getTextboxValueFromOption(e)}};customElements.get("craft-combobox")||customElements.define("craft-combobox",eh);var li=class extends H{constructor(...e){super(...e),this.variant=Ce.Default,this.label=null}render(){return w`<span
      class="${pe({indicator:!0,"indicator--success":this.variant===Ce.Success,"indicator--danger":this.variant===Ce.Danger,"indicator--warning":this.variant===Ce.Warning,"indicator--info":this.variant===Ce.Info,"indicator--empty":this.variant==="empty"})}"
    >
      <slot></slot>
    </span>`}};li.styles=[Qs,F`
      .indicator {
        display: inline-flex;
        aspect-ratio: 1;
        width: var(--c-indicator-size, 0.5em);
        border-radius: var(--c-radius-full);
        color: var(--c-color-on-emphasis);
        background-color: var(--c-color-bg-emphasis);
        border: 1px solid var(--c-color-border-emphasis);
      }

      .indicator--empty {
        background-color: var(--c-color-neutral-bg-faint);
        border: 1px solid var(--c-color-neutral-border-normal);
      }
    `],T([b({reflect:!0})],li.prototype,"variant",void 0),T([b()],li.prototype,"label",void 0),customElements.get("craft-indicator")||customElements.define("craft-indicator",li);var Tt=class extends H{constructor(){super(),this.alt=!1,this.shift=!1,this.os="Unknown",this.os=this.detectOS()}connectedCallback(){super.connectedCallback(),this.os==="Unknown"&&(this.os=this.detectOS())}detectOS(){let e=navigator.platform.toLowerCase();return e.includes("mac")||/iphone|ipad|ipod/.test(e)?"Mac":e.includes("win")?"Windows":e.includes("linux")?"Linux":"Unknown"}renderShortcutPrefix(){switch(this.os){case"Mac":return`${this.alt?"⌥":""}${this.shift?"⇧":""}⌘`;case"Linux":return`Super+${this.alt?"Alt+":""}${this.shift?"Shift+":""}`;default:return`Ctrl+${this.alt?"Alt+":""}${this.shift?"Shift+":""}`}}render(){return w`<span class="shortcut"
      >${this.renderShortcutPrefix()}<slot></slot
    ></span>`}};Tt.styles=F`
    :host {
      display: inline-flex;
    }

    .shortcut {
      font-size: 0.9em;
      padding: 0 var(--c-spacing-sm);
      background-color: var(--c-color-neutral-bg-subtle);
      border: 1px solid var(--c-color-neutral-border-subtle);
      border-radius: var(--c-radius-sm);
      box-shadow: var(--c-shadow-sm);
    }
  `,T([b({type:Boolean})],Tt.prototype,"alt",void 0),T([b({type:Boolean})],Tt.prototype,"shift",void 0),T([b()],Tt.prototype,"os",void 0),customElements.get("craft-shortcut")||customElements.define("craft-shortcut",Tt);class th extends Bi($o(H)){connectedCallback(){super.connectedCallback(),this.setAttribute("role","radiogroup")}resetGroup(){let e;this.formElements.forEach(t=>{typeof t.resetGroup=="function"?t.resetGroup():typeof t.reset=="function"&&(t.reset(),t.checked&&(e=t.choiceValue))}),this.modelValue=e,this.resetInteractionState()}}class ih extends Ui(Hi){connectedCallback(){super.connectedCallback(),this.type="radio"}}var sh=class extends th{static get styles(){return[...super.styles,Gt,F`
        .input-group {
          display: grid;
          gap: var(--c-spacing-xs);
        }
      `]}};customElements.get("craft-radio-group")||customElements.define("craft-radio-group",sh);var nh=class extends ih{static get styles(){return[...super.styles,F`
        /* same as checkbox, potentially consolidate */
        :host {
          gap: var(--c-spacing-sm);
        }
      `]}};customElements.get("craft-radio")||customElements.define("craft-radio",nh);function oh(i,e){if(typeof d3<"u"&&typeof d3FormatLocaleDefinition<"u")return e===void 0&&(e=",.0f"),d3.formatLocale(d3FormatLocaleDefinition).format(e)(i);let t=typeof i=="string"?parseFloat(i):i;if(isNaN(t))return String(i);if(e){let s=e.includes(","),n=e.match(/\.(\d+)/),o=n?parseInt(n[1],10):0;return new Intl.NumberFormat("en-US",{useGrouping:s,minimumFractionDigits:o,maximumFractionDigits:o}).format(t)}return new Intl.NumberFormat("en-US",{useGrouping:!0,minimumFractionDigits:0,maximumFractionDigits:0}).format(t)}function $s(i){let e=1,t,s,n=[...i];if((t=s=n.indexOf("{"))===-1)return[i];let o=[n.slice(0,s).join("")];for(;;){let r=n.indexOf("{",s+1),a=n.indexOf("}",s+1);if(r===-1&&a===-1||(r===-1&&(r=n.length),a!==-1&&a>r?(e++,s=r):a!==-1&&(e--,s=a),e===0&&(o.push(n.slice(t+1,s).join("").split(",",3)),t=s+1,o.push(n.slice(t,r===-1?n.length:r).join("")),t=r===-1?n.length:r),e!==0&&(r===-1||a===-1)))break}return e===0?o:!1}function rh(i,e={}){let t=i[0]?.trim();if(!t||e[t]===void 0)return`{${i.join(",")}}`;let s=e[t],n=i[1]===void 0?"none":i[1].trim();switch(n){case"number":return(()=>{let o=i[2]===void 0?null:i[2].trim();if(o!==null&&o!=="integer")throw"Message format 'number' is only supported for integer values.";let r=oh(s),a;return o===null&&(a=`${s}`.indexOf("."))!==-1&&(r+=`.${s.substring(a+1)}`),r})();case"none":return s;case"select":return(()=>{if(i[2]===void 0)return!1;let o=$s(i[2]);if(o===!1)return!1;let r=o.length,a=!1;for(let c=0;c+1<r;c++){if(Array.isArray(o[c])||!Array.isArray(o[c+1]))return!1;let u=o[c++].trim();(a===!1&&u==="other"||u==s)&&(a=o[c].join(","))}return a===!1?!1:Ms(a,e)})();case"plural":return(()=>{if(i[2]===void 0)return!1;let o=$s(i[2]);if(o===!1)return!1;let r=o.length,a=!1,c=0;for(let u=0;u+1<r;u++){if(typeof o[u]=="object"||typeof o[u+1]!="object")return!1;let h=o[u++].trim(),g=[...h];if(u===1&&h.substring(0,7)==="offset:"){let f=[...h.replace(/[\n\r\t]/g," ")].indexOf(" ",7);if(f===-1)throw Error("Message pattern is invalid.");c=parseInt(g.slice(7,f).join("").trim()),h=g.slice(f+1,f+1+g.length).join("").trim()}if(a===!1&&h==="other"||h[0]==="="&&parseInt(g.slice(1,1+g.length).join(""))===s||h==="one"&&s-c===1){let f=o[u];a=(typeof f=="string"?[f]:f).map(m=>m.replace("#",String(s-c))).join(",")}}return a===!1?!1:Ms(a,e)})();default:throw Error(`Message format '${n}' is not supported.`)}}function Ms(i,e){let t;if((t=$s(i))===!1)throw Error("Message pattern is invalid.");for(let s=0;s<t.length;s++){let n=t[s];if(typeof n=="object"){let o=rh(n,e);if(o===!1)throw Error("Message pattern is invalid.");t[s]=String(o)}}return t.join("")}function Kn(i,e,t="app",s){return s&&s[t]!==void 0&&s[t][i]!==void 0&&(i=s[t][i]),e?Ms(i,e):i}var ah=class{constructor(){this.refreshPromise=null,this.tokenName=null,this.tokenValue=null,this.refreshPromise=null}async getToken(){return this.tokenValue||await this.refreshToken(),this.tokenValue}async refreshToken(){return this.refreshPromise||(this.refreshPromise=Pt.get("users/session-info").then(({data:i})=>{let{csrfTokenName:e,csrfTokenValue:t}=i;return this.tokenName=e??null,this.tokenValue=t??null,this.tokenValue}).finally(()=>{this.refreshPromise=null})),this.refreshPromise}clearToken(){this.tokenValue=null}};function lh(i=""){return`/admin/actions/${i}`}function ch(){let i={"X-Registered-Asset-Bundles":[...new Set(Craft.registeredAssetBundles)].join(","),"X-Registered-Js-Files":[...new Set(Craft.registeredJsFiles)].join(",")};return Craft.csrfTokenValue&&(i["X-CSRF-Token"]=Craft.csrfTokenValue),i}const Pt=Vs.create({baseURL:lh()}),ms=new ah;Pt.interceptors.request.use(async i=>{i.headers.set("X-Requested-With","XMLHttpRequest");let e=ch();if(Object.entries(e).forEach(([t,s])=>{i.headers.set(t,s)}),["post","put","patch","delete"].includes(i.method?.toLowerCase()||"")&&!i.url?.includes("users/session-info")){let t=await ms.getToken();t&&i.headers.set("X-CSRF-Token",t)}return i}),Pt.interceptors.response.use(i=>i,async i=>{let e=i.config;if(i.response?.status===419||i.response?.status===403&&!e._retry){e._retry=!0;try{return ms.clearToken(),e.headers["X-CSRF-Token"]=await ms.refreshToken(),Vs(e)}catch(t){return console.error("Failed to refresh CSRF token:",t),Promise.reject(t)}}return Promise.reject(i)});let bi=!1,st=null;async function dh(i){if(!bi){if(st)return st;bi=!0;try{return(await Pt.post("app/api-headers",void 0,{cancelToken:i})).data}catch{}finally{bi=!1}}}const bs=Vs.create({baseURL:"https://api.craftcms.com/v1/"});async function uh(i){return st?Object.entries(st).forEach(([e,t])=>{i.headers.set(e,t)}):(i.params=i.params||{},i.params.processCraftHeaders=1),i}async function hh(i,e){if(st)return;let{data:t}=await Pt.post("app/process-api-response-headers",{headers:i},{cancelToken:e});return st=t,bi=!1,st}async function ph(i){return await hh(i.headers,i.config.cancelToken),i}bs.interceptors.request.use(async i=>{let{cancelToken:e}=i,t=await dh(e);t&&Object.entries(t).forEach(([n,o])=>{i.headers.set(n,o)});let s={...i,params:{...Craft.apiParams||{},...i.params,v:new Date().getTime()}};return t||(s.params.processCraftHeaders=1),Craft.httpProxy&&(s.proxy=Craft.httpProxy),s}),bs.interceptors.request.use(uh),bs.interceptors.response.use(ph);const Np=Object.freeze(Object.defineProperty({__proto__:null,default:Ee},Symbol.toStringTag,{value:"Module"}));export{Kn as i,Np as n};
