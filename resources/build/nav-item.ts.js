const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["./bg-BG2.js","./bg3.js","./cs-CZ2.js","./cs3.js","./de-DE2.js","./de3.js","./en-AU2.js","./en3.js","./en-GB2.js","./en-US2.js","./es-ES2.js","./es3.js","./fr-FR2.js","./fr3.js","./fr-BE2.js","./hu-HU2.js","./hu3.js","./it-IT2.js","./it3.js","./nl-BE2.js","./nl3.js","./nl-NL2.js","./pl-PL2.js","./pl3.js","./ro-RO2.js","./ro3.js","./ru-RU2.js","./ru3.js","./sk-SK2.js","./sk3.js","./tr-TR.js","./tr.js","./uk-UA2.js","./uk3.js","./bg-BG.js","./bg2.js","./cs-CZ.js","./cs2.js","./de-DE.js","./de2.js","./en-AU.js","./en2.js","./en-GB.js","./en-US.js","./es-ES.js","./es2.js","./fr-FR.js","./fr2.js","./fr-BE.js","./hu-HU.js","./hu2.js","./it-IT.js","./it2.js","./nl-BE.js","./nl2.js","./nl-NL.js","./pl-PL.js","./pl2.js","./ro-RO.js","./ro2.js","./ru-RU.js","./ru2.js","./sk-SK.js","./sk2.js","./uk-UA.js","./uk2.js"])))=>i.map(i=>d[i]);
import{T as Dt,a as S,i as P,x as y,r as Wn,Z as jn,B as Ks,E as j,S as Ro}from"./lit-element.js";import{n as m,t as Se}from"./property.js";import{e as C,r as de}from"./progress-DOMF4PIT.js";import{e as Kn,a as G}from"./query.js";import{_ as E,a as Fs}from"./index2.js";import"./nav-list.ts.js";var us="",hs="";function Gs(i){us=i}function Gn(i=""){if(!us){const e=document.querySelector("[data-webawesome]");if(e?.hasAttribute("data-webawesome")){const t=new URL(e.getAttribute("data-webawesome")??"",window.location.href).pathname;Gs(t)}else{const s=[...document.getElementsByTagName("script")].find(o=>o.src.endsWith("webawesome.js")||o.src.endsWith("webawesome.loader.js")||o.src.endsWith("webawesome.ssr-loader.js"));if(s){const o=String(s.getAttribute("src"));Gs(o.split("/").slice(0,-1).join("/"))}}}return us.replace(/\/$/,"")+(i?`/${i.replace(/^\//,"")}`:"")}function Yn(i){hs=i}function Zn(){if(!hs){const i=document.querySelector("[data-fa-kit-code]");i&&Yn(i.getAttribute("data-fa-kit-code")||"")}return hs}var Le="7.0.1";function Xn(i,e,t){const s=Zn(),o=s.length>0;let n="solid";return e==="notdog"?(t==="solid"&&(n="solid"),t==="duo-solid"&&(n="duo-solid"),`https://ka-p.fontawesome.com/releases/v${Le}/svgs/notdog-${n}/${i}.svg?token=${encodeURIComponent(s)}`):e==="chisel"?`https://ka-p.fontawesome.com/releases/v${Le}/svgs/chisel-regular/${i}.svg?token=${encodeURIComponent(s)}`:e==="etch"?`https://ka-p.fontawesome.com/releases/v${Le}/svgs/etch-solid/${i}.svg?token=${encodeURIComponent(s)}`:e==="jelly"?(t==="regular"&&(n="regular"),t==="duo-regular"&&(n="duo-regular"),t==="fill-regular"&&(n="fill-regular"),`https://ka-p.fontawesome.com/releases/v${Le}/svgs/jelly-${n}/${i}.svg?token=${encodeURIComponent(s)}`):e==="slab"?((t==="solid"||t==="regular")&&(n="regular"),t==="press-regular"&&(n="press-regular"),`https://ka-p.fontawesome.com/releases/v${Le}/svgs/slab-${n}/${i}.svg?token=${encodeURIComponent(s)}`):e==="thumbprint"?`https://ka-p.fontawesome.com/releases/v${Le}/svgs/thumbprint-light/${i}.svg?token=${encodeURIComponent(s)}`:e==="whiteboard"?`https://ka-p.fontawesome.com/releases/v${Le}/svgs/whiteboard-semibold/${i}.svg?token=${encodeURIComponent(s)}`:(e==="classic"&&(t==="thin"&&(n="thin"),t==="light"&&(n="light"),t==="regular"&&(n="regular"),t==="solid"&&(n="solid")),e==="sharp"&&(t==="thin"&&(n="sharp-thin"),t==="light"&&(n="sharp-light"),t==="regular"&&(n="sharp-regular"),t==="solid"&&(n="sharp-solid")),e==="duotone"&&(t==="thin"&&(n="duotone-thin"),t==="light"&&(n="duotone-light"),t==="regular"&&(n="duotone-regular"),t==="solid"&&(n="duotone")),e==="sharp-duotone"&&(t==="thin"&&(n="sharp-duotone-thin"),t==="light"&&(n="sharp-duotone-light"),t==="regular"&&(n="sharp-duotone-regular"),t==="solid"&&(n="sharp-duotone-solid")),e==="brands"&&(n="brands"),o?`https://ka-p.fontawesome.com/releases/v${Le}/svgs/${n}/${i}.svg?token=${encodeURIComponent(s)}`:`https://ka-f.fontawesome.com/releases/v${Le}/svgs/${n}/${i}.svg`)}var Qn={name:"default",resolver:(i,e="classic",t="solid")=>Xn(i,e,t),mutator:(i,e)=>{if(e?.family&&!i.hasAttribute("data-duotone-initialized")){const{family:t,variant:s}=e;if(t==="duotone"||t==="sharp-duotone"||t==="notdog"&&s==="duo-solid"||t==="jelly"&&s==="duo-regular"||t==="thumbprint"){const o=[...i.querySelectorAll("path")],n=o.find(a=>!a.hasAttribute("opacity")),r=o.find(a=>a.hasAttribute("opacity"));if(!n||!r)return;if(n.setAttribute("data-duotone-primary",""),r.setAttribute("data-duotone-secondary",""),e.swapOpacity&&n&&r){const a=r.getAttribute("opacity")||"0.4";n.style.setProperty("--path-opacity",a),r.style.setProperty("--path-opacity","1")}i.setAttribute("data-duotone-initialized","")}}}},Jn=Qn;new MutationObserver(i=>{for(const{addedNodes:e}of i)for(const t of e)t.nodeType===Node.ELEMENT_NODE&&er(t)});async function er(i){const e=i instanceof Element?i.tagName.toLowerCase():"",t=e?.startsWith("wa-"),s=[...i.querySelectorAll(":not(:defined)")].map(r=>r.tagName.toLowerCase()).filter(r=>r.startsWith("wa-"));t&&!customElements.get(e)&&s.push(e);const o=[...new Set(s)],n=await Promise.allSettled(o.map(r=>tr(r)));for(const r of n)r.status==="rejected"&&console.warn(r.reason);await new Promise(requestAnimationFrame),i.dispatchEvent(new CustomEvent("wa-discovery-complete",{bubbles:!1,cancelable:!1,composed:!0}))}function tr(i){if(customElements.get(i))return Promise.resolve();const e=i.replace(/^wa-/i,""),t=Gn(`components/${e}/${e}.js`);return new Promise((s,o)=>{import(t).then(()=>s()).catch(()=>o(new Error(`Unable to autoload <${i}> from ${t}`)))})}const ps=new Set,at=new Map;let Ye,Ls="ltr",Os="en";const zo=typeof MutationObserver<"u"&&typeof document<"u"&&typeof document.documentElement<"u";if(zo){const i=new MutationObserver(Bo);Ls=document.documentElement.dir||"ltr",Os=document.documentElement.lang||navigator.language,i.observe(document.documentElement,{attributes:!0,attributeFilter:["dir","lang"]})}function Po(...i){i.map(e=>{const t=e.$code.toLowerCase();at.has(t)?at.set(t,Object.assign(Object.assign({},at.get(t)),e)):at.set(t,e),Ye||(Ye=e)}),Bo()}function Bo(){zo&&(Ls=document.documentElement.dir||"ltr",Os=document.documentElement.lang||navigator.language),[...ps.keys()].map(i=>{typeof i.requestUpdate=="function"&&i.requestUpdate()})}let ir=class{constructor(e){this.host=e,this.host.addController(this)}hostConnected(){ps.add(this.host)}hostDisconnected(){ps.delete(this.host)}dir(){return`${this.host.dir||Ls}`.toLowerCase()}lang(){return`${this.host.lang||Os}`.toLowerCase()}getTranslationData(e){var t,s;const o=new Intl.Locale(e.replace(/_/g,"-")),n=o?.language.toLowerCase(),r=(s=(t=o?.region)===null||t===void 0?void 0:t.toLowerCase())!==null&&s!==void 0?s:"",a=at.get(`${n}-${r}`),c=at.get(n);return{locale:o,language:n,region:r,primary:a,secondary:c}}exists(e,t){var s;const{primary:o,secondary:n}=this.getTranslationData((s=t.lang)!==null&&s!==void 0?s:this.lang());return t=Object.assign({includeFallback:!1},t),!!(o&&o[e]||n&&n[e]||t.includeFallback&&Ye&&Ye[e])}term(e,...t){const{primary:s,secondary:o}=this.getTranslationData(this.lang());let n;if(s&&s[e])n=s[e];else if(o&&o[e])n=o[e];else if(Ye&&Ye[e])n=Ye[e];else return console.error(`No translation found for: ${String(e)}`),String(e);return typeof n=="function"?n(...t):n}date(e,t){return e=new Date(e),new Intl.DateTimeFormat(this.lang(),t).format(e)}number(e,t){return e=Number(e),isNaN(e)?"":new Intl.NumberFormat(this.lang(),t).format(e)}relativeTime(e,t,s){return new Intl.RelativeTimeFormat(this.lang(),s).format(e,t)}};var Uo={$code:"en",$name:"English",$dir:"ltr",carousel:"Carousel",clearEntry:"Clear entry",close:"Close",copied:"Copied",copy:"Copy",currentValue:"Current value",error:"Error",goToSlide:(i,e)=>`Go to slide ${i} of ${e}`,hidePassword:"Hide password",loading:"Loading",nextSlide:"Next slide",numOptionsSelected:i=>i===0?"No options selected":i===1?"1 option selected":`${i} options selected`,pauseAnimation:"Pause animation",playAnimation:"Play animation",previousSlide:"Previous slide",progress:"Progress",remove:"Remove",resize:"Resize",scrollableRegion:"Scrollable region",scrollToEnd:"Scroll to end",scrollToStart:"Scroll to start",selectAColorFromTheScreen:"Select a color from the screen",showPassword:"Show password",slideNum:i=>`Slide ${i}`,toggleColorFormat:"Toggle color format",zoomIn:"Zoom in",zoomOut:"Zoom out"};Po(Uo);var sr=Uo;var ft=class extends ir{};Po(sr);function or(i){return`data:image/svg+xml,${encodeURIComponent(i)}`}var zi={solid:{check:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M434.8 70.1c14.3 10.4 17.5 30.4 7.1 44.7l-256 352c-5.5 7.6-14 12.3-23.4 13.1s-18.5-2.7-25.1-9.3l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l101.5 101.5 234-321.7c10.4-14.3 30.4-17.5 44.7-7.1z"/></svg>',"chevron-down":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M201.4 406.6c12.5 12.5 32.8 12.5 45.3 0l192-192c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L224 338.7 54.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l192 192z"/></svg>',"chevron-left":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l192 192c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L77.3 256 246.6 86.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-192 192z"/></svg>',"chevron-right":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M311.1 233.4c12.5 12.5 12.5 32.8 0 45.3l-192 192c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L243.2 256 73.9 86.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l192 192z"/></svg>',circle:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0z"/></svg>',eyedropper:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M341.6 29.2l-101.6 101.6-9.4-9.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3l-9.4-9.4 101.6-101.6c39-39 39-102.2 0-141.1s-102.2-39-141.1 0zM55.4 323.3c-15 15-23.4 35.4-23.4 56.6l0 42.4-26.6 39.9c-8.5 12.7-6.8 29.6 4 40.4s27.7 12.5 40.4 4l39.9-26.6 42.4 0c21.2 0 41.6-8.4 56.6-23.4l109.4-109.4-45.3-45.3-109.4 109.4c-3 3-7.1 4.7-11.3 4.7l-36.1 0 0-36.1c0-4.2 1.7-8.3 4.7-11.3l109.4-109.4-45.3-45.3-109.4 109.4z"/></svg>',"grip-vertical":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M128 40c0-22.1-17.9-40-40-40L40 0C17.9 0 0 17.9 0 40L0 88c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zm0 192c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zM0 424l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zM320 40c0-22.1-17.9-40-40-40L232 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zM192 232l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zM320 424c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48z"/></svg>',indeterminate:'<svg part="indeterminate-icon" class="icon" viewBox="0 0 16 16"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" stroke-linecap="round"><g stroke="currentColor" stroke-width="2"><g transform="translate(2.285714 6.857143)"><path d="M10.2857143,1.14285714 L1.14285714,1.14285714"/></g></g></g></svg>',minus:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M0 256c0-17.7 14.3-32 32-32l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 288c-17.7 0-32-14.3-32-32z"/></svg>',pause:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M48 32C21.5 32 0 53.5 0 80L0 432c0 26.5 21.5 48 48 48l64 0c26.5 0 48-21.5 48-48l0-352c0-26.5-21.5-48-48-48L48 32zm224 0c-26.5 0-48 21.5-48 48l0 352c0 26.5 21.5 48 48 48l64 0c26.5 0 48-21.5 48-48l0-352c0-26.5-21.5-48-48-48l-64 0z"/></svg>',play:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M91.2 36.9c-12.4-6.8-27.4-6.5-39.6 .7S32 57.9 32 72l0 368c0 14.1 7.5 27.2 19.6 34.4s27.2 7.5 39.6 .7l336-184c12.8-7 20.8-20.5 20.8-35.1s-8-28.1-20.8-35.1l-336-184z"/></svg>',star:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M309.5-18.9c-4.1-8-12.4-13.1-21.4-13.1s-17.3 5.1-21.4 13.1L193.1 125.3 33.2 150.7c-8.9 1.4-16.3 7.7-19.1 16.3s-.5 18 5.8 24.4l114.4 114.5-25.2 159.9c-1.4 8.9 2.3 17.9 9.6 23.2s16.9 6.1 25 2L288.1 417.6 432.4 491c8 4.1 17.7 3.3 25-2s11-14.2 9.6-23.2L441.7 305.9 556.1 191.4c6.4-6.4 8.6-15.8 5.8-24.4s-10.1-14.9-19.1-16.3L383 125.3 309.5-18.9z"/></svg>',user:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M224 248a120 120 0 1 0 0-240 120 120 0 1 0 0 240zm-29.7 56C95.8 304 16 383.8 16 482.3 16 498.7 29.3 512 45.7 512l356.6 0c16.4 0 29.7-13.3 29.7-29.7 0-98.5-79.8-178.3-178.3-178.3l-59.4 0z"/></svg>',xmark:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M55.1 73.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L147.2 256 9.9 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192.5 301.3 329.9 438.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.8 256 375.1 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192.5 210.7 55.1 73.4z"/></svg>'},regular:{"circle-question":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M464 256a208 208 0 1 0 -416 0 208 208 0 1 0 416 0zM0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0zm256-80c-17.7 0-32 14.3-32 32 0 13.3-10.7 24-24 24s-24-10.7-24-24c0-44.2 35.8-80 80-80s80 35.8 80 80c0 47.2-36 67.2-56 74.5l0 3.8c0 13.3-10.7 24-24 24s-24-10.7-24-24l0-8.1c0-20.5 14.8-35.2 30.1-40.2 6.4-2.1 13.2-5.5 18.2-10.3 4.3-4.2 7.7-10 7.7-19.6 0-17.7-14.3-32-32-32zM224 368a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>',"circle-xmark":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M256 48a208 208 0 1 1 0 416 208 208 0 1 1 0-416zm0 464a256 256 0 1 0 0-512 256 256 0 1 0 0 512zM167 167c-9.4 9.4-9.4 24.6 0 33.9l55 55-55 55c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l55-55 55 55c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-55-55 55-55c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-55 55-55-55c-9.4-9.4-24.6-9.4-33.9 0z"/></svg>',copy:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M384 336l-192 0c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l133.5 0c4.2 0 8.3 1.7 11.3 4.7l58.5 58.5c3 3 4.7 7.1 4.7 11.3L400 320c0 8.8-7.2 16-16 16zM192 384l192 0c35.3 0 64-28.7 64-64l0-197.5c0-17-6.7-33.3-18.7-45.3L370.7 18.7C358.7 6.7 342.5 0 325.5 0L192 0c-35.3 0-64 28.7-64 64l0 256c0 35.3 28.7 64 64 64zM64 128c-35.3 0-64 28.7-64 64L0 448c0 35.3 28.7 64 64 64l192 0c35.3 0 64-28.7 64-64l0-16-48 0 0 16c0 8.8-7.2 16-16 16L64 464c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l16 0 0-48-16 0z"/></svg>',eye:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M288 80C222.8 80 169.2 109.6 128.1 147.7 89.6 183.5 63 226 49.4 256 63 286 89.6 328.5 128.1 364.3 169.2 402.4 222.8 432 288 432s118.8-29.6 159.9-67.7C486.4 328.5 513 286 526.6 256 513 226 486.4 183.5 447.9 147.7 406.8 109.6 353.2 80 288 80zM95.4 112.6C142.5 68.8 207.2 32 288 32s145.5 36.8 192.6 80.6c46.8 43.5 78.1 95.4 93 131.1 3.3 7.9 3.3 16.7 0 24.6-14.9 35.7-46.2 87.7-93 131.1-47.1 43.7-111.8 80.6-192.6 80.6S142.5 443.2 95.4 399.4c-46.8-43.5-78.1-95.4-93-131.1-3.3-7.9-3.3-16.7 0-24.6 14.9-35.7 46.2-87.7 93-131.1zM288 336c44.2 0 80-35.8 80-80 0-29.6-16.1-55.5-40-69.3-1.4 59.7-49.6 107.9-109.3 109.3 13.8 23.9 39.7 40 69.3 40zm-79.6-88.4c2.5 .3 5 .4 7.6 .4 35.3 0 64-28.7 64-64 0-2.6-.2-5.1-.4-7.6-37.4 3.9-67.2 33.7-71.1 71.1zm45.6-115c10.8-3 22.2-4.5 33.9-4.5 8.8 0 17.5 .9 25.8 2.6 .3 .1 .5 .1 .8 .2 57.9 12.2 101.4 63.7 101.4 125.2 0 70.7-57.3 128-128 128-61.6 0-113-43.5-125.2-101.4-1.8-8.6-2.8-17.5-2.8-26.6 0-11 1.4-21.8 4-32 .2-.7 .3-1.3 .5-1.9 11.9-43.4 46.1-77.6 89.5-89.5z"/></svg>',"eye-slash":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M41-24.9c-9.4-9.4-24.6-9.4-33.9 0S-2.3-.3 7 9.1l528 528c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-96.4-96.4c2.7-2.4 5.4-4.8 8-7.2 46.8-43.5 78.1-95.4 93-131.1 3.3-7.9 3.3-16.7 0-24.6-14.9-35.7-46.2-87.7-93-131.1-47.1-43.7-111.8-80.6-192.6-80.6-56.8 0-105.6 18.2-146 44.2L41-24.9zM176.9 111.1c32.1-18.9 69.2-31.1 111.1-31.1 65.2 0 118.8 29.6 159.9 67.7 38.5 35.7 65.1 78.3 78.6 108.3-13.6 30-40.2 72.5-78.6 108.3-3.1 2.8-6.2 5.6-9.4 8.4L393.8 328c14-20.5 22.2-45.3 22.2-72 0-70.7-57.3-128-128-128-26.7 0-51.5 8.2-72 22.2l-39.1-39.1zm182 182l-108-108c11.1-5.8 23.7-9.1 37.1-9.1 44.2 0 80 35.8 80 80 0 13.4-3.3 26-9.1 37.1zM103.4 173.2l-34-34c-32.6 36.8-55 75.8-66.9 104.5-3.3 7.9-3.3 16.7 0 24.6 14.9 35.7 46.2 87.7 93 131.1 47.1 43.7 111.8 80.6 192.6 80.6 37.3 0 71.2-7.9 101.5-20.6L352.2 422c-20 6.4-41.4 10-64.2 10-65.2 0-118.8-29.6-159.9-67.7-38.5-35.7-65.1-78.3-78.6-108.3 10.4-23.1 28.6-53.6 54-82.8z"/></svg>',star:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M288.1-32c9 0 17.3 5.1 21.4 13.1L383 125.3 542.9 150.7c8.9 1.4 16.3 7.7 19.1 16.3s.5 18-5.8 24.4L441.7 305.9 467 465.8c1.4 8.9-2.3 17.9-9.6 23.2s-17 6.1-25 2L288.1 417.6 143.8 491c-8 4.1-17.7 3.3-25-2s-11-14.2-9.6-23.2L134.4 305.9 20 191.4c-6.4-6.4-8.6-15.8-5.8-24.4s10.1-14.9 19.1-16.3l159.9-25.4 73.6-144.2c4.1-8 12.4-13.1 21.4-13.1zm0 76.8L230.3 158c-3.5 6.8-10 11.6-17.6 12.8l-125.5 20 89.8 89.9c5.4 5.4 7.9 13.1 6.7 20.7l-19.8 125.5 113.3-57.6c6.8-3.5 14.9-3.5 21.8 0l113.3 57.6-19.8-125.5c-1.2-7.6 1.3-15.3 6.7-20.7l89.8-89.9-125.5-20c-7.6-1.2-14.1-6-17.6-12.8L288.1 44.8z"/></svg>'}},nr={name:"system",resolver:(i,e="classic",t="solid")=>{let o=zi[t][i]??zi.regular[i]??zi.regular["circle-question"];return o?or(o):""}},rr=nr;var ar="classic",ui=[Jn,rr],hi=[];function lr(i){hi.push(i)}function cr(i){hi=hi.filter(e=>e!==i)}function Pi(i){return ui.find(e=>e.name===i)}function dr(i,e){ur(i),ui.push({name:i,resolver:e.resolver,mutator:e.mutator,spriteSheet:e.spriteSheet}),hi.forEach(t=>{t.library===i&&t.setIcon()})}function ur(i){ui=ui.filter(e=>e.name!==i)}function hr(){return ar}var pr=Object.defineProperty,fr=Object.getOwnPropertyDescriptor,Ho=i=>{throw TypeError(i)},g=(i,e,t,s)=>{for(var o=s>1?void 0:s?fr(e,t):e,n=i.length-1,r;n>=0;n--)(r=i[n])&&(o=(s?r(e,t,o):r(o))||o);return s&&o&&pr(e,t,o),o},qo=(i,e,t)=>e.has(i)||Ho("Cannot "+t),mr=(i,e,t)=>(qo(i,e,"read from private field"),e.get(i)),br=(i,e,t)=>e.has(i)?Ho("Cannot add the same private member more than once"):e instanceof WeakSet?e.add(i):e.set(i,t),gr=(i,e,t,s)=>(qo(i,e,"write to private field"),e.set(i,t),t);const vr={alert:"triangle-exclamation",asc:"arrow-down-short-wide",asset:"image",assets:"image",circleuarr:"circle-arrow-up",collapse:"down-left-and-up-right-to-center",condition:"diamond",darr:"arrow-down",date:"calendar",desc:"arrow-down-wide-short",disabled:"circle-dashed",done:"circle-check",downangle:"angle-down",draft:"scribble",edit:"pencil",enabled:"circle",expand:"up-right-and-down-left-from-center",external:"arrow-up-right-from-square",field:"pen-to-square",help:"circle-question",home:"house",info:"circle-info",insecure:"unlock",larr:"arrow-left",layout:"table-layout",leftangle:"angle-left",listrtl:"list-flip",location:"location-dot",mail:"envelope",menu:"bars",move:"grip-dots",newstamp:"certificate",paperplane:"paper-plane",plugin:"plug",rarr:"arrow-right",refresh:"arrows-rotate",remove:"xmark",rightangle:"angle-right",rotate:"rotate-left",routes:"signs-post",search:"magnifying-glass",secure:"lock",settings:"gear",shareleft:"share-flip",shuteye:"eye-slash","sidebar-left":"sidebar","sidebar-right":"sidebar-flip","sidebar-start":"sidebar","sidebar-end":"sidebar-flip",structure:"list-tree",structurertl:"list-tree-flip",template:"file-code",time:"clock",tool:"wrench",uarr:"arrow-up",upangle:"angle-up",view:"eye",wand:"wand-magic-sparkles"};function _r(i,e="classic",t="regular"){let s="solid",o=t,n=i.endsWith(".svg")?i.split(".svg")[0]:i;if(i.includes("/")){let[r,...a]=i.split("/");o=r??o,n=a.join("/")}return o==="thin"?s="thin":o==="light"?s="light":o==="regular"?s="regular":o==="solid"&&(s="solid"),e==="brands"&&(s="brands"),o==="custom-icons"&&(s="custom-icons"),n=vr[n]??n,`/vendor/craft/icons/${s}/${n}.svg`}function yr(){dr("default",{resolver:(i,e="classic",t="solid")=>_r(i,e,t),mutator:i=>i.setAttribute("fill","currentColor")})}var Ys=class extends HTMLElement{constructor(...e){super(...e),this.cookieName=null,this.state="collapsed",this.expanded=!1,this.handleOpen=()=>{this.trigger?.setAttribute("aria-expanded","true"),this.expanded=!0,this.dispatchEvent(new CustomEvent("open")),this.target&&(this.target.dataset.state="expanded"),this.cookieName&&window.Craft?.setCookie(this.cookieName,"expanded")},this.handleClose=()=>{this.trigger?.setAttribute("aria-expanded","false"),this.expanded=!1,this.dispatchEvent(new CustomEvent("close")),this.target&&(this.target.dataset.state="collapsed"),this.cookieName&&window.Craft?.setCookie(this.cookieName,"collapsed")}}get trigger(){return this.querySelector('button[type="button"]')}get target(){if(!this.trigger)return console.warn("No trigger found for disclosure."),null;let e=this.trigger.getAttribute("aria-controls");return e?document.getElementById(e):(console.warn("No target selector found for disclosure."),null)}connectedCallback(){if(!this.trigger){console.error("craft-disclosure elements must include a button",this);return}if(!this.target){console.error(`No target with id ${this.trigger.getAttribute("aria-controls")} found for disclosure. `,this.trigger);return}this.cookieName=this.getAttribute("cookie-name"),this.state=this.getAttribute("state")??"expanded",this.trigger.setAttribute("aria-expanded",this.state==="expanded"?"true":"false"),this.trigger.addEventListener("click",this.toggle.bind(this)),this.state==="expanded"?this.open():this.close()}disconnectedCallback(){this.open(),this.trigger?.removeEventListener("click",this.toggle.bind(this))}attributeChangedCallback(e,t,s){e==="state"&&(s==="expanded"?this.handleOpen():this.handleClose())}toggle(){this.expanded?this.close():this.open()}open(){this.setAttribute("state","expanded")}close(){this.setAttribute("state","collapsed")}};Ys.observedAttributes=["state"],customElements.get("craft-disclosure")||customElements.define("craft-disclosure",Ys);function oi(i){return(e,t)=>{const{slot:s,selector:o}=i??{},n="slot"+(s?`[name=${s}]`:":not([name])");return Kn(e,t,{get(){const r=this.renderRoot?.querySelector(n),a=r?.assignedElements(i)??[];return o===void 0?a:a.filter((c=>c.matches(o)))}})}}const Ei={ATTRIBUTE:1,CHILD:2},Ci=i=>(...e)=>({_$litDirective$:i,values:e});let ki=class{constructor(e){}get _$AU(){return this._$AM._$AU}_$AT(e,t,s){this._$Ct=e,this._$AM=t,this._$Ci=s}_$AS(e,t){return this.update(e,t)}update(e,t){return this.render(...t)}};const le=Ci(class extends ki{constructor(i){if(super(i),i.type!==Ei.ATTRIBUTE||i.name!=="class"||i.strings?.length>2)throw Error("`classMap()` can only be used in the `class` attribute and must be the only part in the attribute.")}render(i){return" "+Object.keys(i).filter((e=>i[e])).join(" ")+" "}update(i,[e]){if(this.st===void 0){this.st=new Set,i.strings!==void 0&&(this.nt=new Set(i.strings.join(" ").split(/\s/).filter((s=>s!==""))));for(const s in e)e[s]&&!this.nt?.has(s)&&this.st.add(s);return this.render(e)}const t=i.element.classList;for(const s of this.st)s in e||(t.remove(s),this.st.delete(s));for(const s in e){const o=!!e[s];o===this.st.has(s)||this.nt?.has(s)||(o?(t.add(s),this.st.add(s)):(t.remove(s),this.st.delete(s)))}return Dt}});var wr=S`
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
`,Kt=class extends P{constructor(...e){super(...e),this.visible=!1,this.wrapper=null}show(){this.visible=!0,this.dispatchEvent(new CustomEvent("show"))}hide(){this.visible=!1,this.dispatchEvent(new CustomEvent("hide"))}focus(){this.wrapper?.focus()}render(){return y`
      <div
        tabindex="-1"
        class="${le({wrapper:!0,hidden:!this.visible})}"
      >
        <div class="spinner"></div>
        <span class="message visually-hidden"><slot /></span>
      </div>
    `}};Kt.styles=[wr],C([m({reflect:!0})],Kt.prototype,"visible",void 0),C([G(".wrapper")],Kt.prototype,"wrapper",void 0),customElements.get("craft-spinner")||customElements.define("craft-spinner",Kt);var xr=class extends Event{constructor(){super("wa-reposition",{bubbles:!0,cancelable:!1,composed:!0})}};var Er=`:host {
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
`,ni,ue=class extends P{constructor(){super(),br(this,ni,!1),this.initialReflectedProperties=new Map,this.didSSR=!!this.shadowRoot,this.customStates={set:(e,t)=>{if(this.internals?.states)try{t?this.internals.states.add(e):this.internals.states.delete(e)}catch(s){if(String(s).includes("must start with '--'"))console.error("Your browser implements an outdated version of CustomStateSet. Consider using a polyfill");else throw s}},has:e=>{if(!this.internals?.states)return!1;try{return this.internals.states.has(e)}catch{return!1}}};try{this.internals=this.attachInternals()}catch{console.error("Element internals are not supported in your browser. Consider using a polyfill")}this.customStates.set("wa-defined",!0);let i=this.constructor;for(let[e,t]of i.elementProperties)t.default==="inherit"&&t.initial!==void 0&&typeof e=="string"&&this.customStates.set(`initial-${e}-${t.initial}`,!0)}static get styles(){const i=Array.isArray(this.css)?this.css:this.css?[this.css]:[];return[Er,...i].map(e=>typeof e=="string"?Wn(e):e)}attributeChangedCallback(i,e,t){mr(this,ni)||(this.constructor.elementProperties.forEach((s,o)=>{s.reflect&&this[o]!=null&&this.initialReflectedProperties.set(o,this[o])}),gr(this,ni,!0)),super.attributeChangedCallback(i,e,t)}willUpdate(i){super.willUpdate(i),this.initialReflectedProperties.forEach((e,t)=>{i.has(t)&&this[t]==null&&(this[t]=e)})}firstUpdated(i){super.firstUpdated(i),this.didSSR&&this.shadowRoot?.querySelectorAll("slot").forEach(e=>{e.dispatchEvent(new Event("slotchange",{bubbles:!0,composed:!1,cancelable:!1}))})}update(i){try{super.update(i)}catch(e){if(this.didSSR&&!this.hasUpdated){const t=new Event("lit-hydration-error",{bubbles:!0,composed:!0,cancelable:!1});t.error=e,this.dispatchEvent(t)}throw e}}relayNativeEvent(i,e){i.stopImmediatePropagation(),this.dispatchEvent(new i.constructor(i.type,{...i,...e}))}};ni=new WeakMap;g([m()],ue.prototype,"dir",2);g([m()],ue.prototype,"lang",2);g([m({type:Boolean,reflect:!0,attribute:"did-ssr"})],ue.prototype,"didSSR",2);const He=Math.min,fe=Math.max,pi=Math.round,Gt=Math.floor,Te=i=>({x:i,y:i}),Cr={left:"right",right:"left",bottom:"top",top:"bottom"},kr={start:"end",end:"start"};function fs(i,e,t){return fe(i,He(e,t))}function mt(i,e){return typeof i=="function"?i(e):i}function qe(i){return i.split("-")[0]}function bt(i){return i.split("-")[1]}function Wo(i){return i==="x"?"y":"x"}function Is(i){return i==="y"?"height":"width"}const Sr=new Set(["top","bottom"]);function Ie(i){return Sr.has(qe(i))?"y":"x"}function Ds(i){return Wo(Ie(i))}function Ar(i,e,t){t===void 0&&(t=!1);const s=bt(i),o=Ds(i),n=Is(o);let r=o==="x"?s===(t?"end":"start")?"right":"left":s==="start"?"bottom":"top";return e.reference[n]>e.floating[n]&&(r=fi(r)),[r,fi(r)]}function Tr(i){const e=fi(i);return[ms(i),e,ms(e)]}function ms(i){return i.replace(/start|end/g,e=>kr[e])}const Zs=["left","right"],Xs=["right","left"],Nr=["top","bottom"],Fr=["bottom","top"];function Lr(i,e,t){switch(i){case"top":case"bottom":return t?e?Xs:Zs:e?Zs:Xs;case"left":case"right":return e?Nr:Fr;default:return[]}}function Or(i,e,t,s){const o=bt(i);let n=Lr(qe(i),t==="start",s);return o&&(n=n.map(r=>r+"-"+o),e&&(n=n.concat(n.map(ms)))),n}function fi(i){return i.replace(/left|right|bottom|top/g,e=>Cr[e])}function Ir(i){return{top:0,right:0,bottom:0,left:0,...i}}function jo(i){return typeof i!="number"?Ir(i):{top:i,right:i,bottom:i,left:i}}function mi(i){const{x:e,y:t,width:s,height:o}=i;return{width:s,height:o,top:t,left:e,right:e+s,bottom:t+o,x:e,y:t}}function Qs(i,e,t){let{reference:s,floating:o}=i;const n=Ie(e),r=Ds(e),a=Is(r),c=qe(e),u=n==="y",h=s.x+s.width/2-o.width/2,b=s.y+s.height/2-o.height/2,p=s[a]/2-o[a]/2;let f;switch(c){case"top":f={x:h,y:s.y-o.height};break;case"bottom":f={x:h,y:s.y+s.height};break;case"right":f={x:s.x+s.width,y:b};break;case"left":f={x:s.x-o.width,y:b};break;default:f={x:s.x,y:s.y}}switch(bt(e)){case"start":f[r]-=p*(t&&u?-1:1);break;case"end":f[r]+=p*(t&&u?-1:1);break}return f}const Dr=async(i,e,t)=>{const{placement:s="bottom",strategy:o="absolute",middleware:n=[],platform:r}=t,a=n.filter(Boolean),c=await(r.isRTL==null?void 0:r.isRTL(e));let u=await r.getElementRects({reference:i,floating:e,strategy:o}),{x:h,y:b}=Qs(u,s,c),p=s,f={},_=0;for(let v=0;v<a.length;v++){const{name:w,fn:x}=a[v],{x:A,y:L,data:N,reset:V}=await x({x:h,y:b,initialPlacement:s,placement:p,strategy:o,middlewareData:f,rects:u,platform:r,elements:{reference:i,floating:e}});h=A??h,b=L??b,f={...f,[w]:{...f[w],...N}},V&&_<=50&&(_++,typeof V=="object"&&(V.placement&&(p=V.placement),V.rects&&(u=V.rects===!0?await r.getElementRects({reference:i,floating:e,strategy:o}):V.rects),{x:h,y:b}=Qs(u,p,c)),v=-1)}return{x:h,y:b,placement:p,strategy:o,middlewareData:f}};async function Ms(i,e){var t;e===void 0&&(e={});const{x:s,y:o,platform:n,rects:r,elements:a,strategy:c}=i,{boundary:u="clippingAncestors",rootBoundary:h="viewport",elementContext:b="floating",altBoundary:p=!1,padding:f=0}=mt(e,i),_=jo(f),w=a[p?b==="floating"?"reference":"floating":b],x=mi(await n.getClippingRect({element:(t=await(n.isElement==null?void 0:n.isElement(w)))==null||t?w:w.contextElement||await(n.getDocumentElement==null?void 0:n.getDocumentElement(a.floating)),boundary:u,rootBoundary:h,strategy:c})),A=b==="floating"?{x:s,y:o,width:r.floating.width,height:r.floating.height}:r.reference,L=await(n.getOffsetParent==null?void 0:n.getOffsetParent(a.floating)),N=await(n.isElement==null?void 0:n.isElement(L))?await(n.getScale==null?void 0:n.getScale(L))||{x:1,y:1}:{x:1,y:1},V=mi(n.convertOffsetParentRelativeRectToViewportRelativeRect?await n.convertOffsetParentRelativeRectToViewportRelativeRect({elements:a,rect:A,offsetParent:L,strategy:c}):A);return{top:(x.top-V.top+_.top)/N.y,bottom:(V.bottom-x.bottom+_.bottom)/N.y,left:(x.left-V.left+_.left)/N.x,right:(V.right-x.right+_.right)/N.x}}const Mr=i=>({name:"arrow",options:i,async fn(e){const{x:t,y:s,placement:o,rects:n,platform:r,elements:a,middlewareData:c}=e,{element:u,padding:h=0}=mt(i,e)||{};if(u==null)return{};const b=jo(h),p={x:t,y:s},f=Ds(o),_=Is(f),v=await r.getDimensions(u),w=f==="y",x=w?"top":"left",A=w?"bottom":"right",L=w?"clientHeight":"clientWidth",N=n.reference[_]+n.reference[f]-p[f]-n.floating[_],V=p[f]-n.reference[f],B=await(r.getOffsetParent==null?void 0:r.getOffsetParent(u));let U=B?B[L]:0;(!U||!await(r.isElement==null?void 0:r.isElement(B)))&&(U=a.floating[L]||n.floating[_]);const ee=N/2-V/2,Z=U/2-v[_]/2-1,M=He(b[x],Z),Ae=He(b[A],Z),ce=M,l=U-v[_]-Ae,k=U/2-v[_]/2+ee,F=fs(ce,k,l),$=!c.arrow&&bt(o)!=null&&k!==F&&n.reference[_]/2-(k<ce?M:Ae)-v[_]/2<0,D=$?k<ce?k-ce:k-l:0;return{[f]:p[f]+D,data:{[f]:F,centerOffset:k-F-D,...$&&{alignmentOffset:D}},reset:$}}}),$r=function(i){return i===void 0&&(i={}),{name:"flip",options:i,async fn(e){var t,s;const{placement:o,middlewareData:n,rects:r,initialPlacement:a,platform:c,elements:u}=e,{mainAxis:h=!0,crossAxis:b=!0,fallbackPlacements:p,fallbackStrategy:f="bestFit",fallbackAxisSideDirection:_="none",flipAlignment:v=!0,...w}=mt(i,e);if((t=n.arrow)!=null&&t.alignmentOffset)return{};const x=qe(o),A=Ie(a),L=qe(a)===a,N=await(c.isRTL==null?void 0:c.isRTL(u.floating)),V=p||(L||!v?[fi(a)]:Tr(a)),B=_!=="none";!p&&B&&V.push(...Or(a,v,_,N));const U=[a,...V],ee=await Ms(e,w),Z=[];let M=((s=n.flip)==null?void 0:s.overflows)||[];if(h&&Z.push(ee[x]),b){const k=Ar(o,r,N);Z.push(ee[k[0]],ee[k[1]])}if(M=[...M,{placement:o,overflows:Z}],!Z.every(k=>k<=0)){var Ae,ce;const k=(((Ae=n.flip)==null?void 0:Ae.index)||0)+1,F=U[k];if(F&&(!(b==="alignment"?A!==Ie(F):!1)||M.every(T=>Ie(T.placement)===A?T.overflows[0]>0:!0)))return{data:{index:k,overflows:M},reset:{placement:F}};let $=(ce=M.filter(D=>D.overflows[0]<=0).sort((D,T)=>D.overflows[1]-T.overflows[1])[0])==null?void 0:ce.placement;if(!$)switch(f){case"bestFit":{var l;const D=(l=M.filter(T=>{if(B){const W=Ie(T.placement);return W===A||W==="y"}return!0}).map(T=>[T.placement,T.overflows.filter(W=>W>0).reduce((W,he)=>W+he,0)]).sort((T,W)=>T[1]-W[1])[0])==null?void 0:l[0];D&&($=D);break}case"initialPlacement":$=a;break}if(o!==$)return{reset:{placement:$}}}return{}}}},Vr=new Set(["left","top"]);async function Rr(i,e){const{placement:t,platform:s,elements:o}=i,n=await(s.isRTL==null?void 0:s.isRTL(o.floating)),r=qe(t),a=bt(t),c=Ie(t)==="y",u=Vr.has(r)?-1:1,h=n&&c?-1:1,b=mt(e,i);let{mainAxis:p,crossAxis:f,alignmentAxis:_}=typeof b=="number"?{mainAxis:b,crossAxis:0,alignmentAxis:null}:{mainAxis:b.mainAxis||0,crossAxis:b.crossAxis||0,alignmentAxis:b.alignmentAxis};return a&&typeof _=="number"&&(f=a==="end"?_*-1:_),c?{x:f*h,y:p*u}:{x:p*u,y:f*h}}const zr=function(i){return i===void 0&&(i=0),{name:"offset",options:i,async fn(e){var t,s;const{x:o,y:n,placement:r,middlewareData:a}=e,c=await Rr(e,i);return r===((t=a.offset)==null?void 0:t.placement)&&(s=a.arrow)!=null&&s.alignmentOffset?{}:{x:o+c.x,y:n+c.y,data:{...c,placement:r}}}}},Pr=function(i){return i===void 0&&(i={}),{name:"shift",options:i,async fn(e){const{x:t,y:s,placement:o}=e,{mainAxis:n=!0,crossAxis:r=!1,limiter:a={fn:w=>{let{x,y:A}=w;return{x,y:A}}},...c}=mt(i,e),u={x:t,y:s},h=await Ms(e,c),b=Ie(qe(o)),p=Wo(b);let f=u[p],_=u[b];if(n){const w=p==="y"?"top":"left",x=p==="y"?"bottom":"right",A=f+h[w],L=f-h[x];f=fs(A,f,L)}if(r){const w=b==="y"?"top":"left",x=b==="y"?"bottom":"right",A=_+h[w],L=_-h[x];_=fs(A,_,L)}const v=a.fn({...e,[p]:f,[b]:_});return{...v,data:{x:v.x-t,y:v.y-s,enabled:{[p]:n,[b]:r}}}}}},Br=function(i){return i===void 0&&(i={}),{name:"size",options:i,async fn(e){var t,s;const{placement:o,rects:n,platform:r,elements:a}=e,{apply:c=()=>{},...u}=mt(i,e),h=await Ms(e,u),b=qe(o),p=bt(o),f=Ie(o)==="y",{width:_,height:v}=n.floating;let w,x;b==="top"||b==="bottom"?(w=b,x=p===(await(r.isRTL==null?void 0:r.isRTL(a.floating))?"start":"end")?"left":"right"):(x=b,w=p==="end"?"top":"bottom");const A=v-h.top-h.bottom,L=_-h.left-h.right,N=He(v-h[w],A),V=He(_-h[x],L),B=!e.middlewareData.shift;let U=N,ee=V;if((t=e.middlewareData.shift)!=null&&t.enabled.x&&(ee=L),(s=e.middlewareData.shift)!=null&&s.enabled.y&&(U=A),B&&!p){const M=fe(h.left,0),Ae=fe(h.right,0),ce=fe(h.top,0),l=fe(h.bottom,0);f?ee=_-2*(M!==0||Ae!==0?M+Ae:fe(h.left,h.right)):U=v-2*(ce!==0||l!==0?ce+l:fe(h.top,h.bottom))}await c({...e,availableWidth:ee,availableHeight:U});const Z=await r.getDimensions(a.floating);return _!==Z.width||v!==Z.height?{reset:{rects:!0}}:{}}}};function Si(){return typeof window<"u"}function gt(i){return Ko(i)?(i.nodeName||"").toLowerCase():"#document"}function be(i){var e;return(i==null||(e=i.ownerDocument)==null?void 0:e.defaultView)||window}function Fe(i){var e;return(e=(Ko(i)?i.ownerDocument:i.document)||window.document)==null?void 0:e.documentElement}function Ko(i){return Si()?i instanceof Node||i instanceof be(i).Node:!1}function xe(i){return Si()?i instanceof Element||i instanceof be(i).Element:!1}function Ne(i){return Si()?i instanceof HTMLElement||i instanceof be(i).HTMLElement:!1}function Js(i){return!Si()||typeof ShadowRoot>"u"?!1:i instanceof ShadowRoot||i instanceof be(i).ShadowRoot}const Ur=new Set(["inline","contents"]);function $t(i){const{overflow:e,overflowX:t,overflowY:s,display:o}=Ee(i);return/auto|scroll|overlay|hidden|clip/.test(e+s+t)&&!Ur.has(o)}const Hr=new Set(["table","td","th"]);function qr(i){return Hr.has(gt(i))}const Wr=[":popover-open",":modal"];function Ai(i){return Wr.some(e=>{try{return i.matches(e)}catch{return!1}})}const jr=["transform","translate","scale","rotate","perspective"],Kr=["transform","translate","scale","rotate","perspective","filter"],Gr=["paint","layout","strict","content"];function Ti(i){const e=$s(),t=xe(i)?Ee(i):i;return jr.some(s=>t[s]?t[s]!=="none":!1)||(t.containerType?t.containerType!=="normal":!1)||!e&&(t.backdropFilter?t.backdropFilter!=="none":!1)||!e&&(t.filter?t.filter!=="none":!1)||Kr.some(s=>(t.willChange||"").includes(s))||Gr.some(s=>(t.contain||"").includes(s))}function Yr(i){let e=We(i);for(;Ne(e)&&!ct(e);){if(Ti(e))return e;if(Ai(e))return null;e=We(e)}return null}function $s(){return typeof CSS>"u"||!CSS.supports?!1:CSS.supports("-webkit-backdrop-filter","none")}const Zr=new Set(["html","body","#document"]);function ct(i){return Zr.has(gt(i))}function Ee(i){return be(i).getComputedStyle(i)}function Ni(i){return xe(i)?{scrollLeft:i.scrollLeft,scrollTop:i.scrollTop}:{scrollLeft:i.scrollX,scrollTop:i.scrollY}}function We(i){if(gt(i)==="html")return i;const e=i.assignedSlot||i.parentNode||Js(i)&&i.host||Fe(i);return Js(e)?e.host:e}function Go(i){const e=We(i);return ct(e)?i.ownerDocument?i.ownerDocument.body:i.body:Ne(e)&&$t(e)?e:Go(e)}function dt(i,e,t){var s;e===void 0&&(e=[]),t===void 0&&(t=!0);const o=Go(i),n=o===((s=i.ownerDocument)==null?void 0:s.body),r=be(o);if(n){const a=bs(r);return e.concat(r,r.visualViewport||[],$t(o)?o:[],a&&t?dt(a):[])}return e.concat(o,dt(o,[],t))}function bs(i){return i.parent&&Object.getPrototypeOf(i.parent)?i.frameElement:null}function Yo(i){const e=Ee(i);let t=parseFloat(e.width)||0,s=parseFloat(e.height)||0;const o=Ne(i),n=o?i.offsetWidth:t,r=o?i.offsetHeight:s,a=pi(t)!==n||pi(s)!==r;return a&&(t=n,s=r),{width:t,height:s,$:a}}function Vs(i){return xe(i)?i:i.contextElement}function lt(i){const e=Vs(i);if(!Ne(e))return Te(1);const t=e.getBoundingClientRect(),{width:s,height:o,$:n}=Yo(e);let r=(n?pi(t.width):t.width)/s,a=(n?pi(t.height):t.height)/o;return(!r||!Number.isFinite(r))&&(r=1),(!a||!Number.isFinite(a))&&(a=1),{x:r,y:a}}const Xr=Te(0);function Zo(i){const e=be(i);return!$s()||!e.visualViewport?Xr:{x:e.visualViewport.offsetLeft,y:e.visualViewport.offsetTop}}function Qr(i,e,t){return e===void 0&&(e=!1),!t||e&&t!==be(i)?!1:e}function Je(i,e,t,s){e===void 0&&(e=!1),t===void 0&&(t=!1);const o=i.getBoundingClientRect(),n=Vs(i);let r=Te(1);e&&(s?xe(s)&&(r=lt(s)):r=lt(i));const a=Qr(n,t,s)?Zo(n):Te(0);let c=(o.left+a.x)/r.x,u=(o.top+a.y)/r.y,h=o.width/r.x,b=o.height/r.y;if(n){const p=be(n),f=s&&xe(s)?be(s):s;let _=p,v=bs(_);for(;v&&s&&f!==_;){const w=lt(v),x=v.getBoundingClientRect(),A=Ee(v),L=x.left+(v.clientLeft+parseFloat(A.paddingLeft))*w.x,N=x.top+(v.clientTop+parseFloat(A.paddingTop))*w.y;c*=w.x,u*=w.y,h*=w.x,b*=w.y,c+=L,u+=N,_=be(v),v=bs(_)}}return mi({width:h,height:b,x:c,y:u})}function Fi(i,e){const t=Ni(i).scrollLeft;return e?e.left+t:Je(Fe(i)).left+t}function Xo(i,e){const t=i.getBoundingClientRect(),s=t.left+e.scrollLeft-Fi(i,t),o=t.top+e.scrollTop;return{x:s,y:o}}function Jr(i){let{elements:e,rect:t,offsetParent:s,strategy:o}=i;const n=o==="fixed",r=Fe(s),a=e?Ai(e.floating):!1;if(s===r||a&&n)return t;let c={scrollLeft:0,scrollTop:0},u=Te(1);const h=Te(0),b=Ne(s);if((b||!b&&!n)&&((gt(s)!=="body"||$t(r))&&(c=Ni(s)),Ne(s))){const f=Je(s);u=lt(s),h.x=f.x+s.clientLeft,h.y=f.y+s.clientTop}const p=r&&!b&&!n?Xo(r,c):Te(0);return{width:t.width*u.x,height:t.height*u.y,x:t.x*u.x-c.scrollLeft*u.x+h.x+p.x,y:t.y*u.y-c.scrollTop*u.y+h.y+p.y}}function ea(i){return Array.from(i.getClientRects())}function ta(i){const e=Fe(i),t=Ni(i),s=i.ownerDocument.body,o=fe(e.scrollWidth,e.clientWidth,s.scrollWidth,s.clientWidth),n=fe(e.scrollHeight,e.clientHeight,s.scrollHeight,s.clientHeight);let r=-t.scrollLeft+Fi(i);const a=-t.scrollTop;return Ee(s).direction==="rtl"&&(r+=fe(e.clientWidth,s.clientWidth)-o),{width:o,height:n,x:r,y:a}}const eo=25;function ia(i,e){const t=be(i),s=Fe(i),o=t.visualViewport;let n=s.clientWidth,r=s.clientHeight,a=0,c=0;if(o){n=o.width,r=o.height;const h=$s();(!h||h&&e==="fixed")&&(a=o.offsetLeft,c=o.offsetTop)}const u=Fi(s);if(u<=0){const h=s.ownerDocument,b=h.body,p=getComputedStyle(b),f=h.compatMode==="CSS1Compat"&&parseFloat(p.marginLeft)+parseFloat(p.marginRight)||0,_=Math.abs(s.clientWidth-b.clientWidth-f);_<=eo&&(n-=_)}else u<=eo&&(n+=u);return{width:n,height:r,x:a,y:c}}const sa=new Set(["absolute","fixed"]);function oa(i,e){const t=Je(i,!0,e==="fixed"),s=t.top+i.clientTop,o=t.left+i.clientLeft,n=Ne(i)?lt(i):Te(1),r=i.clientWidth*n.x,a=i.clientHeight*n.y,c=o*n.x,u=s*n.y;return{width:r,height:a,x:c,y:u}}function to(i,e,t){let s;if(e==="viewport")s=ia(i,t);else if(e==="document")s=ta(Fe(i));else if(xe(e))s=oa(e,t);else{const o=Zo(i);s={x:e.x-o.x,y:e.y-o.y,width:e.width,height:e.height}}return mi(s)}function Qo(i,e){const t=We(i);return t===e||!xe(t)||ct(t)?!1:Ee(t).position==="fixed"||Qo(t,e)}function na(i,e){const t=e.get(i);if(t)return t;let s=dt(i,[],!1).filter(a=>xe(a)&&gt(a)!=="body"),o=null;const n=Ee(i).position==="fixed";let r=n?We(i):i;for(;xe(r)&&!ct(r);){const a=Ee(r),c=Ti(r);!c&&a.position==="fixed"&&(o=null),(n?!c&&!o:!c&&a.position==="static"&&!!o&&sa.has(o.position)||$t(r)&&!c&&Qo(i,r))?s=s.filter(h=>h!==r):o=a,r=We(r)}return e.set(i,s),s}function ra(i){let{element:e,boundary:t,rootBoundary:s,strategy:o}=i;const r=[...t==="clippingAncestors"?Ai(e)?[]:na(e,this._c):[].concat(t),s],a=r[0],c=r.reduce((u,h)=>{const b=to(e,h,o);return u.top=fe(b.top,u.top),u.right=He(b.right,u.right),u.bottom=He(b.bottom,u.bottom),u.left=fe(b.left,u.left),u},to(e,a,o));return{width:c.right-c.left,height:c.bottom-c.top,x:c.left,y:c.top}}function aa(i){const{width:e,height:t}=Yo(i);return{width:e,height:t}}function la(i,e,t){const s=Ne(e),o=Fe(e),n=t==="fixed",r=Je(i,!0,n,e);let a={scrollLeft:0,scrollTop:0};const c=Te(0);function u(){c.x=Fi(o)}if(s||!s&&!n)if((gt(e)!=="body"||$t(o))&&(a=Ni(e)),s){const f=Je(e,!0,n,e);c.x=f.x+e.clientLeft,c.y=f.y+e.clientTop}else o&&u();n&&!s&&o&&u();const h=o&&!s&&!n?Xo(o,a):Te(0),b=r.left+a.scrollLeft-c.x-h.x,p=r.top+a.scrollTop-c.y-h.y;return{x:b,y:p,width:r.width,height:r.height}}function Bi(i){return Ee(i).position==="static"}function io(i,e){if(!Ne(i)||Ee(i).position==="fixed")return null;if(e)return e(i);let t=i.offsetParent;return Fe(i)===t&&(t=t.ownerDocument.body),t}function Jo(i,e){const t=be(i);if(Ai(i))return t;if(!Ne(i)){let o=We(i);for(;o&&!ct(o);){if(xe(o)&&!Bi(o))return o;o=We(o)}return t}let s=io(i,e);for(;s&&qr(s)&&Bi(s);)s=io(s,e);return s&&ct(s)&&Bi(s)&&!Ti(s)?t:s||Yr(i)||t}const ca=async function(i){const e=this.getOffsetParent||Jo,t=this.getDimensions,s=await t(i.floating);return{reference:la(i.reference,await e(i.floating),i.strategy),floating:{x:0,y:0,width:s.width,height:s.height}}};function da(i){return Ee(i).direction==="rtl"}const ri={convertOffsetParentRelativeRectToViewportRelativeRect:Jr,getDocumentElement:Fe,getClippingRect:ra,getOffsetParent:Jo,getElementRects:ca,getClientRects:ea,getDimensions:aa,getScale:lt,isElement:xe,isRTL:da};function en(i,e){return i.x===e.x&&i.y===e.y&&i.width===e.width&&i.height===e.height}function ua(i,e){let t=null,s;const o=Fe(i);function n(){var a;clearTimeout(s),(a=t)==null||a.disconnect(),t=null}function r(a,c){a===void 0&&(a=!1),c===void 0&&(c=1),n();const u=i.getBoundingClientRect(),{left:h,top:b,width:p,height:f}=u;if(a||e(),!p||!f)return;const _=Gt(b),v=Gt(o.clientWidth-(h+p)),w=Gt(o.clientHeight-(b+f)),x=Gt(h),L={rootMargin:-_+"px "+-v+"px "+-w+"px "+-x+"px",threshold:fe(0,He(1,c))||1};let N=!0;function V(B){const U=B[0].intersectionRatio;if(U!==c){if(!N)return r();U?r(!1,U):s=setTimeout(()=>{r(!1,1e-7)},1e3)}U===1&&!en(u,i.getBoundingClientRect())&&r(),N=!1}try{t=new IntersectionObserver(V,{...L,root:o.ownerDocument})}catch{t=new IntersectionObserver(V,L)}t.observe(i)}return r(!0),n}function tn(i,e,t,s){s===void 0&&(s={});const{ancestorScroll:o=!0,ancestorResize:n=!0,elementResize:r=typeof ResizeObserver=="function",layoutShift:a=typeof IntersectionObserver=="function",animationFrame:c=!1}=s,u=Vs(i),h=o||n?[...u?dt(u):[],...dt(e)]:[];h.forEach(x=>{o&&x.addEventListener("scroll",t,{passive:!0}),n&&x.addEventListener("resize",t)});const b=u&&a?ua(u,t):null;let p=-1,f=null;r&&(f=new ResizeObserver(x=>{let[A]=x;A&&A.target===u&&f&&(f.unobserve(e),cancelAnimationFrame(p),p=requestAnimationFrame(()=>{var L;(L=f)==null||L.observe(e)})),t()}),u&&!c&&f.observe(u),f.observe(e));let _,v=c?Je(i):null;c&&w();function w(){const x=Je(i);v&&!en(v,x)&&t(),v=x,_=requestAnimationFrame(w)}return t(),()=>{var x;h.forEach(A=>{o&&A.removeEventListener("scroll",t),n&&A.removeEventListener("resize",t)}),b?.(),(x=f)==null||x.disconnect(),f=null,c&&cancelAnimationFrame(_)}}const sn=zr,on=Pr,nn=$r,so=Br,ha=Mr,rn=(i,e,t)=>{const s=new Map,o={platform:ri,...t},n={...o.platform,_c:s};return Dr(i,e,{...o,platform:n})};function pa(i){return fa(i)}function Ui(i){return i.assignedSlot?i.assignedSlot:i.parentNode instanceof ShadowRoot?i.parentNode.host:i.parentNode}function fa(i){for(let e=i;e;e=Ui(e))if(e instanceof Element&&getComputedStyle(e).display==="none")return null;for(let e=Ui(i);e;e=Ui(e)){if(!(e instanceof Element))continue;const t=getComputedStyle(e);if(t.display!=="contents"&&(t.position!=="static"||Ti(t)||e.tagName==="BODY"))return e}return null}var ma=`:host {
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
`;function oo(i){return i!==null&&typeof i=="object"&&"getBoundingClientRect"in i&&("contextElement"in i?i instanceof Element:!0)}var Yt=globalThis?.HTMLElement?.prototype.hasOwnProperty("popover"),q=class extends ue{constructor(){super(...arguments),this.localize=new ft(this),this.active=!1,this.placement="top",this.boundary="viewport",this.distance=0,this.skidding=0,this.arrow=!1,this.arrowPlacement="anchor",this.arrowPadding=10,this.flip=!1,this.flipFallbackPlacements="",this.flipFallbackStrategy="best-fit",this.flipPadding=0,this.shift=!1,this.shiftPadding=0,this.autoSizePadding=0,this.hoverBridge=!1,this.updateHoverBridge=()=>{if(this.hoverBridge&&this.anchorEl){const i=this.anchorEl.getBoundingClientRect(),e=this.popup.getBoundingClientRect(),t=this.placement.includes("top")||this.placement.includes("bottom");let s=0,o=0,n=0,r=0,a=0,c=0,u=0,h=0;t?i.top<e.top?(s=i.left,o=i.bottom,n=i.right,r=i.bottom,a=e.left,c=e.top,u=e.right,h=e.top):(s=e.left,o=e.bottom,n=e.right,r=e.bottom,a=i.left,c=i.top,u=i.right,h=i.top):i.left<e.left?(s=i.right,o=i.top,n=e.left,r=e.top,a=i.right,c=i.bottom,u=e.left,h=e.bottom):(s=e.right,o=e.top,n=i.left,r=i.top,a=e.right,c=e.bottom,u=i.left,h=i.bottom),this.style.setProperty("--hover-bridge-top-left-x",`${s}px`),this.style.setProperty("--hover-bridge-top-left-y",`${o}px`),this.style.setProperty("--hover-bridge-top-right-x",`${n}px`),this.style.setProperty("--hover-bridge-top-right-y",`${r}px`),this.style.setProperty("--hover-bridge-bottom-left-x",`${a}px`),this.style.setProperty("--hover-bridge-bottom-left-y",`${c}px`),this.style.setProperty("--hover-bridge-bottom-right-x",`${u}px`),this.style.setProperty("--hover-bridge-bottom-right-y",`${h}px`)}}}async connectedCallback(){super.connectedCallback(),await this.updateComplete,this.start()}disconnectedCallback(){super.disconnectedCallback(),this.stop()}async updated(i){super.updated(i),i.has("active")&&(this.active?this.start():this.stop()),i.has("anchor")&&this.handleAnchorChange(),this.active&&(await this.updateComplete,this.reposition())}async handleAnchorChange(){if(await this.stop(),this.anchor&&typeof this.anchor=="string"){const i=this.getRootNode();this.anchorEl=i.getElementById(this.anchor)}else this.anchor instanceof Element||oo(this.anchor)?this.anchorEl=this.anchor:this.anchorEl=this.querySelector('[slot="anchor"]');this.anchorEl instanceof HTMLSlotElement&&(this.anchorEl=this.anchorEl.assignedElements({flatten:!0})[0]),this.anchorEl&&this.start()}start(){!this.anchorEl||!this.active||(this.popup.showPopover?.(),this.cleanup=tn(this.anchorEl,this.popup,()=>{this.reposition()}))}async stop(){return new Promise(i=>{this.popup.hidePopover?.(),this.cleanup?(this.cleanup(),this.cleanup=void 0,this.removeAttribute("data-current-placement"),this.style.removeProperty("--auto-size-available-width"),this.style.removeProperty("--auto-size-available-height"),requestAnimationFrame(()=>i())):i()})}reposition(){if(!this.active||!this.anchorEl)return;const i=[sn({mainAxis:this.distance,crossAxis:this.skidding})];this.sync?i.push(so({apply:({rects:s})=>{const o=this.sync==="width"||this.sync==="both",n=this.sync==="height"||this.sync==="both";this.popup.style.width=o?`${s.reference.width}px`:"",this.popup.style.height=n?`${s.reference.height}px`:""}})):(this.popup.style.width="",this.popup.style.height="");let e;Yt&&!oo(this.anchor)&&this.boundary==="scroll"&&(e=dt(this.anchorEl).filter(s=>s instanceof Element)),this.flip&&i.push(nn({boundary:this.flipBoundary||e,fallbackPlacements:this.flipFallbackPlacements,fallbackStrategy:this.flipFallbackStrategy==="best-fit"?"bestFit":"initialPlacement",padding:this.flipPadding})),this.shift&&i.push(on({boundary:this.shiftBoundary||e,padding:this.shiftPadding})),this.autoSize?i.push(so({boundary:this.autoSizeBoundary||e,padding:this.autoSizePadding,apply:({availableWidth:s,availableHeight:o})=>{this.autoSize==="vertical"||this.autoSize==="both"?this.style.setProperty("--auto-size-available-height",`${o}px`):this.style.removeProperty("--auto-size-available-height"),this.autoSize==="horizontal"||this.autoSize==="both"?this.style.setProperty("--auto-size-available-width",`${s}px`):this.style.removeProperty("--auto-size-available-width")}})):(this.style.removeProperty("--auto-size-available-width"),this.style.removeProperty("--auto-size-available-height")),this.arrow&&i.push(ha({element:this.arrowEl,padding:this.arrowPadding}));const t=Yt?s=>ri.getOffsetParent(s,pa):ri.getOffsetParent;rn(this.anchorEl,this.popup,{placement:this.placement,middleware:i,strategy:Yt?"absolute":"fixed",platform:{...ri,getOffsetParent:t}}).then(({x:s,y:o,middlewareData:n,placement:r})=>{const a=this.localize.dir()==="rtl",c={top:"bottom",right:"left",bottom:"top",left:"right"}[r.split("-")[0]];if(this.setAttribute("data-current-placement",r),Object.assign(this.popup.style,{left:`${s}px`,top:`${o}px`}),this.arrow){const u=n.arrow.x,h=n.arrow.y;let b="",p="",f="",_="";if(this.arrowPlacement==="start"){const v=typeof u=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"";b=typeof h=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"",p=a?v:"",_=a?"":v}else if(this.arrowPlacement==="end"){const v=typeof u=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"";p=a?"":v,_=a?v:"",f=typeof h=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:""}else this.arrowPlacement==="center"?(_=typeof u=="number"?"calc(50% - var(--arrow-size-diagonal))":"",b=typeof h=="number"?"calc(50% - var(--arrow-size-diagonal))":""):(_=typeof u=="number"?`${u}px`:"",b=typeof h=="number"?`${h}px`:"");Object.assign(this.arrowEl.style,{top:b,right:p,bottom:f,left:_,[c]:"calc(var(--arrow-size-diagonal) * -1)"})}}),requestAnimationFrame(()=>this.updateHoverBridge()),this.dispatchEvent(new xr)}render(){return y`
      <slot name="anchor" @slotchange=${this.handleAnchorChange}></slot>

      <span
        part="hover-bridge"
        class=${le({"popup-hover-bridge":!0,"popup-hover-bridge-visible":this.hoverBridge&&this.active})}
      ></span>

      <div
        popover="manual"
        part="popup"
        class=${le({popup:!0,"popup-active":this.active,"popup-fixed":!Yt,"popup-has-arrow":this.arrow})}
      >
        <slot></slot>
        ${this.arrow?y`<div part="arrow" class="arrow" role="presentation"></div>`:""}
      </div>
    `}};q.css=ma;g([G(".popup")],q.prototype,"popup",2);g([G(".arrow")],q.prototype,"arrowEl",2);g([m()],q.prototype,"anchor",2);g([m({type:Boolean,reflect:!0})],q.prototype,"active",2);g([m({reflect:!0})],q.prototype,"placement",2);g([m()],q.prototype,"boundary",2);g([m({type:Number})],q.prototype,"distance",2);g([m({type:Number})],q.prototype,"skidding",2);g([m({type:Boolean})],q.prototype,"arrow",2);g([m({attribute:"arrow-placement"})],q.prototype,"arrowPlacement",2);g([m({attribute:"arrow-padding",type:Number})],q.prototype,"arrowPadding",2);g([m({type:Boolean})],q.prototype,"flip",2);g([m({attribute:"flip-fallback-placements",converter:{fromAttribute:i=>i.split(" ").map(e=>e.trim()).filter(e=>e!==""),toAttribute:i=>i.join(" ")}})],q.prototype,"flipFallbackPlacements",2);g([m({attribute:"flip-fallback-strategy"})],q.prototype,"flipFallbackStrategy",2);g([m({type:Object})],q.prototype,"flipBoundary",2);g([m({attribute:"flip-padding",type:Number})],q.prototype,"flipPadding",2);g([m({type:Boolean})],q.prototype,"shift",2);g([m({type:Object})],q.prototype,"shiftBoundary",2);g([m({attribute:"shift-padding",type:Number})],q.prototype,"shiftPadding",2);g([m({attribute:"auto-size"})],q.prototype,"autoSize",2);g([m()],q.prototype,"sync",2);g([m({type:Object})],q.prototype,"autoSizeBoundary",2);g([m({attribute:"auto-size-padding",type:Number})],q.prototype,"autoSizePadding",2);g([m({attribute:"hover-bridge",type:Boolean})],q.prototype,"hoverBridge",2);q=g([Se("wa-popup")],q);var Vt=class extends Event{constructor(){super("wa-after-hide",{bubbles:!0,cancelable:!1,composed:!0})}},Rt=class extends Event{constructor(){super("wa-after-show",{bubbles:!0,cancelable:!1,composed:!0})}},zt=class extends Event{constructor(i){super("wa-hide",{bubbles:!0,cancelable:!0,composed:!0}),this.detail=i}},Pt=class extends Event{constructor(){super("wa-show",{bubbles:!0,cancelable:!0,composed:!0})}};const ba="useandom-26T198340PX75pxJACKVERYMINDBUSHWOLF_GQZbfghjklqvwyzrict";let ga=(i=21)=>{let e="",t=crypto.getRandomValues(new Uint8Array(i|=0));for(;i--;)e+=ba[t[i]&63];return e};function Rs(i=""){return`${i}${ga()}`}function bi(i,e){return new Promise(t=>{function s(o){o.target===i&&(i.removeEventListener(e,s),t())}i.addEventListener(e,s)})}function ne(i,e){return new Promise(t=>{const s=new AbortController,{signal:o}=s;if(i.classList.contains(e))return;i.classList.remove(e),i.classList.add(e);let n=()=>{i.classList.remove(e),t(),s.abort()};i.addEventListener("animationend",n,{once:!0,signal:o}),i.addEventListener("animationcancel",n,{once:!0,signal:o})})}function ge(i,e){const t={waitUntilFirstUpdate:!1,...e};return(s,o)=>{const{update:n}=s,r=Array.isArray(i)?i:[i];s.update=function(a){r.forEach(c=>{const u=c;if(a.has(u)){const h=a.get(u),b=this[u];h!==b&&(!t.waitUntilFirstUpdate||this.hasUpdated)&&this[o](h,b)}}),n.call(this,a)}}}var va=`:host {
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
`,Y=class extends ue{constructor(){super(...arguments),this.placement="top",this.disabled=!1,this.distance=8,this.open=!1,this.skidding=0,this.showDelay=150,this.hideDelay=0,this.trigger="hover focus",this.withoutArrow=!1,this.for=null,this.anchor=null,this.eventController=new AbortController,this.handleBlur=()=>{this.hasTrigger("focus")&&this.hide()},this.handleClick=()=>{this.hasTrigger("click")&&(this.open?this.hide():this.show())},this.handleFocus=()=>{this.hasTrigger("focus")&&this.show()},this.handleDocumentKeyDown=i=>{i.key==="Escape"&&(i.stopPropagation(),this.hide())},this.handleMouseOver=()=>{this.hasTrigger("hover")&&(clearTimeout(this.hoverTimeout),this.hoverTimeout=window.setTimeout(()=>this.show(),this.showDelay))},this.handleMouseOut=()=>{this.hasTrigger("hover")&&(clearTimeout(this.hoverTimeout),this.hoverTimeout=window.setTimeout(()=>this.hide(),this.hideDelay))}}connectedCallback(){super.connectedCallback(),this.eventController.signal.aborted&&(this.eventController=new AbortController),this.open&&(this.open=!1,this.updateComplete.then(()=>{this.open=!0})),this.id||(this.id=Rs("wa-tooltip-")),this.for&&this.anchor?(this.anchor=null,this.handleForChange()):this.for&&this.handleForChange()}disconnectedCallback(){super.disconnectedCallback(),document.removeEventListener("keydown",this.handleDocumentKeyDown),this.eventController.abort(),this.anchor&&this.removeFromAriaLabelledBy(this.anchor,this.id)}firstUpdated(){this.body.hidden=!this.open,this.open&&(this.popup.active=!0,this.popup.reposition())}hasTrigger(i){return this.trigger.split(" ").includes(i)}addToAriaLabelledBy(i,e){const s=(i.getAttribute("aria-labelledby")||"").split(/\s+/).filter(Boolean);s.includes(e)||(s.push(e),i.setAttribute("aria-labelledby",s.join(" ")))}removeFromAriaLabelledBy(i,e){const o=(i.getAttribute("aria-labelledby")||"").split(/\s+/).filter(Boolean).filter(n=>n!==e);o.length>0?i.setAttribute("aria-labelledby",o.join(" ")):i.removeAttribute("aria-labelledby")}async handleOpenChange(){if(this.open){if(this.disabled)return;const i=new Pt;if(this.dispatchEvent(i),i.defaultPrevented){this.open=!1;return}document.addEventListener("keydown",this.handleDocumentKeyDown,{signal:this.eventController.signal}),this.body.hidden=!1,this.popup.active=!0,await ne(this.popup.popup,"show-with-scale"),this.popup.reposition(),this.dispatchEvent(new Rt)}else{const i=new zt;if(this.dispatchEvent(i),i.defaultPrevented){this.open=!1;return}document.removeEventListener("keydown",this.handleDocumentKeyDown),await ne(this.popup.popup,"hide-with-scale"),this.popup.active=!1,this.body.hidden=!0,this.dispatchEvent(new Vt)}}handleForChange(){const i=this.getRootNode();if(!i)return;const e=this.for?i.getElementById(this.for):null,t=this.anchor;if(e===t)return;const{signal:s}=this.eventController;e&&(this.addToAriaLabelledBy(e,this.id),e.addEventListener("blur",this.handleBlur,{capture:!0,signal:s}),e.addEventListener("focus",this.handleFocus,{capture:!0,signal:s}),e.addEventListener("click",this.handleClick,{signal:s}),e.addEventListener("mouseover",this.handleMouseOver,{signal:s}),e.addEventListener("mouseout",this.handleMouseOut,{signal:s})),t&&(this.removeFromAriaLabelledBy(t,this.id),t.removeEventListener("blur",this.handleBlur,{capture:!0}),t.removeEventListener("focus",this.handleFocus,{capture:!0}),t.removeEventListener("click",this.handleClick),t.removeEventListener("mouseover",this.handleMouseOver),t.removeEventListener("mouseout",this.handleMouseOut)),this.anchor=e}async handleOptionsChange(){this.hasUpdated&&(await this.updateComplete,this.popup.reposition())}handleDisabledChange(){this.disabled&&this.open&&this.hide()}async show(){if(!this.open)return this.open=!0,bi(this,"wa-after-show")}async hide(){if(this.open)return this.open=!1,bi(this,"wa-after-hide")}render(){return y`
      <wa-popup
        part="base"
        exportparts="
          popup:base__popup,
          arrow:base__arrow
        "
        class=${le({tooltip:!0,"tooltip-open":this.open})}
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
    `}};Y.css=va;Y.dependencies={"wa-popup":q};g([G("slot:not([name])")],Y.prototype,"defaultSlot",2);g([G(".body")],Y.prototype,"body",2);g([G("wa-popup")],Y.prototype,"popup",2);g([m()],Y.prototype,"placement",2);g([m({type:Boolean,reflect:!0})],Y.prototype,"disabled",2);g([m({type:Number})],Y.prototype,"distance",2);g([m({type:Boolean,reflect:!0})],Y.prototype,"open",2);g([m({type:Number})],Y.prototype,"skidding",2);g([m({attribute:"show-delay",type:Number})],Y.prototype,"showDelay",2);g([m({attribute:"hide-delay",type:Number})],Y.prototype,"hideDelay",2);g([m()],Y.prototype,"trigger",2);g([m({attribute:"without-arrow",type:Boolean,reflect:!0})],Y.prototype,"withoutArrow",2);g([m()],Y.prototype,"for",2);g([de()],Y.prototype,"anchor",2);g([ge("open",{waitUntilFirstUpdate:!0})],Y.prototype,"handleOpenChange",1);g([ge("for")],Y.prototype,"handleForChange",1);g([ge(["distance","placement","skidding"])],Y.prototype,"handleOptionsChange",1);g([ge("disabled")],Y.prototype,"handleDisabledChange",1);Y=g([Se("wa-tooltip")],Y);var _a=class extends Y{static get styles(){return[Y.styles,S`
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
      `]}};customElements.get("c-tooltip")||customElements.define("c-tooltip",_a);var ya=S`
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
`,wa=S`
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
`,an=Object.defineProperty,no=Object.getOwnPropertySymbols,xa=Object.prototype.hasOwnProperty,Ea=Object.prototype.propertyIsEnumerable,ln=i=>{throw TypeError(i)},ro=(i,e,t)=>e in i?an(i,e,{enumerable:!0,configurable:!0,writable:!0,value:t}):i[e]=t,Ca=(i,e)=>{for(var t in e||(e={}))xa.call(e,t)&&ro(i,t,e[t]);if(no)for(var t of no(e))Ea.call(e,t)&&ro(i,t,e[t]);return i},ao=(i,e,t,s)=>{for(var o=void 0,n=i.length-1,r;n>=0;n--)(r=i[n])&&(o=r(e,t,o)||o);return o&&an(e,t,o),o},cn=(i,e,t)=>e.has(i)||ln("Cannot "+t),ka=(i,e,t)=>(cn(i,e,"read from private field"),e.get(i)),Sa=(i,e,t)=>e.has(i)?ln("Cannot add the same private member more than once"):e instanceof WeakSet?e.add(i):e.set(i,t),Aa=(i,e,t,s)=>(cn(i,e,"write to private field"),e.set(i,t),t),ai,kt=class extends P{constructor(){super(),Sa(this,ai,!1),this.initialReflectedProperties=new Map,Object.entries(this.constructor.dependencies).forEach(([i,e])=>{this.constructor.define(i,e)})}emit(i,e){let t=new CustomEvent(i,Ca({bubbles:!0,cancelable:!1,composed:!0,detail:{}},e));return this.dispatchEvent(t),t}static define(i,e=this,t={}){let s=customElements.get(i);if(!s){try{customElements.define(i,e,t)}catch{customElements.define(i,class extends e{},t)}return}let o=" (unknown version)",n=o;"version"in e&&e.version&&(o=" v"+e.version),"version"in s&&s.version&&(n=" v"+s.version),!(o&&n&&o===n)&&console.warn(`Attempted to register <${i}>${o}, but <${i}>${n} has already been registered.`)}attributeChangedCallback(i,e,t){ka(this,ai)||(this.constructor.elementProperties.forEach((s,o)=>{s.reflect&&this[o]!=null&&this.initialReflectedProperties.set(o,this[o])}),Aa(this,ai,!0)),super.attributeChangedCallback(i,e,t)}willUpdate(i){super.willUpdate(i),this.initialReflectedProperties.forEach((e,t)=>{i.has(t)&&this[t]==null&&(this[t]=e)})}};ai=new WeakMap,kt.version="2.20.1",kt.dependencies={},ao([m()],kt.prototype,"dir"),ao([m()],kt.prototype,"lang");var lo=class extends kt{render(){return y` <slot></slot> `}};lo.styles=[wa,ya],lo.define("sl-visually-hidden");var Ta=S`
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
`,_t=class extends P{constructor(...e){super(...e),this.isCopying=!1,this.value="",this.disabled=!1}async copyValue(){if(!(this.isCopying||this.disabled)){this.isCopying=!0;try{await navigator.clipboard.writeText(this.value),this.dispatchEvent(new CustomEvent("craft-copy",{bubbles:!0,cancelable:!1,composed:!0,detail:{value:this.value}}))}catch{this.dispatchEvent(new CustomEvent("craft-error",{cancelable:!1,composed:!0,bubbles:!0}))}finally{this.isCopying=!1}}}render(){return y`
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
    `}};_t.styles=[Ta],C([de()],_t.prototype,"isCopying",void 0),C([m({type:String})],_t.prototype,"value",void 0),C([m({type:Boolean})],_t.prototype,"disabled",void 0),customElements.get("craft-copy-button")||customElements.define("craft-copy-button",_t);var Na=S`
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
`,Fa=S`
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
`;const Me={"icon.in":{keyframes:[{scale:.25,opacity:.25},{scale:1,opacity:1}],options:{duration:100}},"icon.out":{keyframes:[{scale:1,opacity:1},{scale:.25,opacity:.25}],options:{duration:100}}};var _e=class extends P{constructor(){super(),this.status="rest",this.value="",this.disabled=!1,this.feedbackDuration=1e3,this.tooltipLabel="Copy",this.addEventListener("craft-copy",()=>{this.showStatus("success")}),this.addEventListener("craft-error",()=>{this.showStatus("error")})}getId(){return`attribute-${this.value.replace(/([a-z])([A-Z])/g,"$1-$2").replace(/[\s_]+/g,"-").toLowerCase()}`}async showStatus(e){let t=e==="success"?this.successIconEl:this.errorIconEl;this.tooltipLabel=e==="success"?"Copied":"Copy failed",await t.animate(Me["icon.out"].keyframes,Me["icon.out"].options),this.copyIconEl.hidden=!0,t.hidden=!1,await t.animate(Me["icon.in"].keyframes,Me["icon.in"].options),this.status=e,setTimeout(async()=>{await t.animate(Me["icon.out"].keyframes,Me["icon.out"].options),t.hidden=!0,this.copyIconEl.hidden=!1,await this.copyIconEl.animate(Me["icon.in"].keyframes,Me["icon.in"].options),this.status="rest",this.tooltipLabel="Copy"},this.feedbackDuration)}render(){return y`
      <c-tooltip for="${this.getId()}">${this.tooltipLabel}</c-tooltip>
      <craft-copy-button
        value="${this.value}"
        id="${this.getId()}"
        class=${le({"copy-attribute":!0,"copy-attribute--success":this.status==="success","copy-attribute--error":this.status==="error"})}
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
    `}};_e.styles=[Na,Fa],C([de()],_e.prototype,"status",void 0),C([G('slot[name="copy-icon"]')],_e.prototype,"copyIconEl",void 0),C([G('slot[name="success-icon"]')],_e.prototype,"successIconEl",void 0),C([G('slot[name="error-icon"]')],_e.prototype,"errorIconEl",void 0),C([G("craft-copy-button")],_e.prototype,"copyButtonEl",void 0),C([m({type:String})],_e.prototype,"value",void 0),C([m({type:Boolean,reflect:!0})],_e.prototype,"disabled",void 0),C([m({attribute:"feedback-duration",type:Number})],_e.prototype,"feedbackDuration",void 0),C([m({reflect:!1})],_e.prototype,"tooltipLabel",void 0),customElements.get("craft-copy-attribute")||customElements.define("craft-copy-attribute",_e);const dn=new WeakMap;function La(i,e){let t=e;for(;t;){if(dn.get(t)===i)return!0;t=Object.getPrototypeOf(t)}return!1}function Q(i){return e=>{if(La(i,e))return e;const t=i(e);return dn.set(t,i),t}}const Oa=i=>class extends i{static get properties(){return{disabled:{type:Boolean,reflect:!0}}}constructor(){super(),this._requestedToBeDisabled=!1,this.__isUserSettingDisabled=!0,this.__restoreDisabledTo=!1,this.disabled=!1}makeRequestToBeDisabled(){this._requestedToBeDisabled===!1&&(this._requestedToBeDisabled=!0,this.__restoreDisabledTo=this.disabled,this.__internalSetDisabled(!0))}retractRequestToBeDisabled(){this._requestedToBeDisabled===!0&&(this._requestedToBeDisabled=!1,this.__internalSetDisabled(this.__restoreDisabledTo))}__internalSetDisabled(e){this.__isUserSettingDisabled=!1,this.disabled=e,this.__isUserSettingDisabled=!0}requestUpdate(e,t,s){super.requestUpdate(e,t,s),e==="disabled"&&(this.__isUserSettingDisabled&&(this.__restoreDisabledTo=this.disabled),this.disabled===!1&&this._requestedToBeDisabled===!0&&this.__internalSetDisabled(!0))}click(){this.disabled||super.click()}},Bt=Q(Oa),Ia=i=>class extends Bt(i){static get properties(){return{tabIndex:{type:Number,reflect:!0,attribute:"tabindex"}}}constructor(){super(),this.__isUserSettingTabIndex=!0,this.__restoreTabIndexTo=0,this.__internalSetTabIndex(0)}makeRequestToBeDisabled(){super.makeRequestToBeDisabled(),this._requestedToBeDisabled===!1&&this.tabIndex!=null&&(this.__restoreTabIndexTo=this.tabIndex)}retractRequestToBeDisabled(){super.retractRequestToBeDisabled(),this._requestedToBeDisabled===!0&&this.__internalSetTabIndex(this.__restoreTabIndexTo)}static enabledWarnings=super.enabledWarnings?.filter(e=>e!=="change-in-update")||[];__internalSetTabIndex(e){this.__isUserSettingTabIndex=!1,this.tabIndex=e,this.__isUserSettingTabIndex=!0}requestUpdate(e,t,s){super.requestUpdate(e,t,s),e==="disabled"&&(this.disabled?this.__internalSetTabIndex(-1):this.__internalSetTabIndex(this.__restoreTabIndexTo)),e==="tabIndex"&&(this.__isUserSettingTabIndex&&this.tabIndex!=null&&(this.__restoreTabIndexTo=this.tabIndex),this.tabIndex!==-1&&this._requestedToBeDisabled===!0&&this.__internalSetTabIndex(-1))}firstUpdated(e){super.firstUpdated(e),this.disabled&&this.__internalSetTabIndex(-1)}},un=Q(Ia);const{I:Da}=jn,Ma=i=>i===null||typeof i!="object"&&typeof i!="function",hn=(i,e)=>i?._$litType$!==void 0,$a=i=>i.strings===void 0,co=()=>document.createComment(""),yt=(i,e,t)=>{const s=i._$AA.parentNode,o=e===void 0?i._$AB:e._$AA;if(t===void 0){const n=s.insertBefore(co(),o),r=s.insertBefore(co(),o);t=new Da(n,r,i,i.options)}else{const n=t._$AB.nextSibling,r=t._$AM,a=r!==i;if(a){let c;t._$AQ?.(i),t._$AM=i,t._$AP!==void 0&&(c=i._$AU)!==r._$AU&&t._$AP(c)}if(n!==o||a){let c=t._$AA;for(;c!==n;){const u=c.nextSibling;s.insertBefore(c,o),c=u}}}return t},je=(i,e,t=i)=>(i._$AI(e,t),i),Va={},Ra=(i,e=Va)=>i._$AH=e,za=i=>i._$AH,Hi=i=>{i._$AR(),i._$AA.remove()};function Pa(i){return i instanceof Node?"node":hn(i)?"template-result":!Array.isArray(i)&&typeof i=="object"&&"template"in i?"slot-rerender-object":null}const Ba=i=>class extends i{get slots(){return{}}constructor(){super(),this.__renderMetaPerSlot=new Map,this.__slotsThatNeedRerender=new Set,this.__slotsProvidedByUserOnFirstConnected=new Set,this.__privateSlots=new Set}connectedCallback(){super.connectedCallback(),this._connectSlotMixin()}__rerenderSlot(t){const s=this.slots[t]();this.__renderTemplateInScopedContext({renderAsDirectHostChild:s.renderAsDirectHostChild,template:s.template,slotName:t}),s.afterRender?.()}update(t){super.update(t);for(const s of this.__slotsThatNeedRerender)this.__rerenderSlot(s)}__renderTemplateInScopedContext({template:t,slotName:s,renderAsDirectHostChild:o}){if(!this.__renderMetaPerSlot.has(s)){const p=!!ShadowRoot.prototype.createElement;this.shadowRoot||console.error("[SlotMixin] No shadowRoot was found");const v=(p?this.shadowRoot:document).createElement("div"),w=document.createComment(`_start_slot_${s}_`),x=document.createComment(`_end_slot_${s}_`);v.appendChild(w),v.appendChild(x);const{creationScope:A,host:L}=this.renderOptions;if(Ks(t,v,{renderBefore:x,creationScope:A,host:L}),o){const N=Array.from(v.childNodes);this.__appendNodes({nodes:N,renderParent:this,slotName:s})}else v.slot=s,this.appendChild(v);this.__renderMetaPerSlot.set(s,{renderTargetThatRespectsShadowRootScoping:v,renderBefore:x});return}const{renderBefore:r,renderTargetThatRespectsShadowRootScoping:a}=this.__renderMetaPerSlot.get(s),c=o?this:a,{creationScope:u,host:h}=this.renderOptions;Ks(t,c,{creationScope:u,host:h,renderBefore:r}),o&&r.previousElementSibling&&!r.previousElementSibling.slot&&(r.previousElementSibling.slot=s)}__appendNodes({nodes:t,renderParent:s=this,slotName:o}){for(const n of t)n instanceof Element&&o&&o!==""&&n.setAttribute("slot",o),s.appendChild(n)}__initSlots(t){for(const s of t){if(this.__slotsProvidedByUserOnFirstConnected.has(s))continue;const o=this.slots[s]();if(o===void 0)continue;switch(this.__isConnectedSlotMixin||this.__privateSlots.add(s),Pa(o)){case"template-result":this.__renderTemplateInScopedContext({template:o,renderAsDirectHostChild:!0,slotName:s});break;case"node":this.__appendNodes({nodes:[o],renderParent:this,slotName:s});break;case"slot-rerender-object":this.__slotsThatNeedRerender.add(s),o.firstRenderOnConnected&&this.__rerenderSlot(s);break;default:throw new Error(`Slot "${s}" configured inside "get slots()" (in prototype) of ${this.constructor.name} may return these types: TemplateResult | Node | {template:TemplateResult, afterRender?:function} | undefined.
              You provided: ${o}`)}}}_connectSlotMixin(){if(this.__isConnectedSlotMixin)return;const t=Object.keys(this.slots);for(const s of t)(s===""?Array.from(this.children).find(n=>!n.hasAttribute("slot")):Array.from(this.children).find(n=>n.slot===s))&&this.__slotsProvidedByUserOnFirstConnected.add(s);this.__initSlots(t),this.__isConnectedSlotMixin=!0}_isPrivateSlot(t){return this.__privateSlots.has(t)}},vt=Q(Ba);function qi(i="google-chrome"){const e=globalThis.navigator,t=!!e.userAgentData&&e.userAgentData.brands.some(c=>c.brand==="Chromium");if(i==="chromium")return t;const o=globalThis.navigator?.vendor,n=typeof globalThis.opr<"u",r=globalThis.userAgent?.indexOf("Edge")>-1,a=globalThis.userAgent?.match("CriOS");if(i==="ios")return a;if(i==="google-chrome")return t!==null&&typeof t<"u"&&o==="Google Inc."&&n===!1&&r===!1}const gi={isChrome:qi(),isIOSChrome:qi("ios"),isChromium:qi("chromium"),isFirefox:globalThis.navigator?.userAgent.toLowerCase().indexOf("firefox")>-1,isMac:globalThis.navigator?.appVersion?.indexOf("Mac")!==-1,isIOS:/iPhone|iPad|iPod/i.test(globalThis.navigator?.userAgent),isMacSafari:globalThis.navigator?.vendor&&globalThis.navigator?.vendor.indexOf("Apple")>-1&&globalThis.navigator?.userAgent&&globalThis.navigator?.userAgent.indexOf("CriOS")===-1&&globalThis.navigator?.userAgent.indexOf("FxiOS")===-1&&globalThis.navigator?.appVersion.indexOf("Mac")!==-1};function Ut(i=""){return`${i.length>0?`${i}-`:""}${Math.random().toString(36).substr(2,10)}`}const Wi=i=>i.key===" "||i.key==="Enter",uo=i=>i.key===" ";class Ua extends un(P){static get properties(){return{active:{type:Boolean,reflect:!0},type:{type:String,reflect:!0}}}render(){return y` <div class="button-content"><slot></slot></div> `}static get styles(){return[S`
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
      `]}constructor(){super(),this.type="button",this.active=!1,this.__setupEvents()}connectedCallback(){super.connectedCallback(),this.hasAttribute("role")||this.setAttribute("role","button")}updated(e){super.updated(e),e.has("disabled")&&(this.disabled?this.setAttribute("aria-disabled","true"):this.getAttribute("aria-disabled")!==null&&this.removeAttribute("aria-disabled"))}__setupEvents(){this.addEventListener("mousedown",this.__mousedownHandler),this.addEventListener("keydown",this.__keydownHandler),this.addEventListener("keyup",this.__keyupHandler)}__mousedownHandler(){this.active=!0;const e=()=>{this.active=!1,document.removeEventListener("mouseup",e),this.removeEventListener("mouseup",e)};document.addEventListener("mouseup",e),this.addEventListener("mouseup",e)}__keydownHandler(e){if(this.active||!Wi(e)){uo(e)&&e.preventDefault();return}uo(e)&&e.preventDefault(),this.active=!0;const t=s=>{Wi(s)&&(this.active=!1,document.removeEventListener("keyup",t,!0))};document.addEventListener("keyup",t,!0)}__keyupHandler(e){if(Wi(e)){if(e.target&&e.target!==this)return;this.click()}}}class Ha extends Ua{constructor(){super(),this.type="reset",this.__setupDelegationInConstructor(),this.__submitAndResetHelperButton=document.createElement("button"),this.__preventEventLeakage=this.__preventEventLeakage.bind(this)}connectedCallback(){super.connectedCallback(),this.updateComplete.then(()=>{this._setupSubmitAndResetHelperOnConnected()})}disconnectedCallback(){super.disconnectedCallback(),this._teardownSubmitAndResetHelperOnDisconnected()}__preventEventLeakage(e){e.target===this.__submitAndResetHelperButton&&e.stopImmediatePropagation()}_setupSubmitAndResetHelperOnConnected(){this.appendChild(this.__submitAndResetHelperButton),this._form=this.__submitAndResetHelperButton.form,this.removeChild(this.__submitAndResetHelperButton),this._form&&this._form.addEventListener("click",this.__preventEventLeakage)}_teardownSubmitAndResetHelperOnDisconnected(){this._form&&this._form.removeEventListener("click",this.__preventEventLeakage)}async __clickDelegationHandler(e){this._form||await this.updateComplete,(this.type==="submit"||this.type==="reset")&&e.target===this&&this._form&&(this.__submitAndResetHelperButton.type=this.type,this._form.appendChild(this.__submitAndResetHelperButton),this.__submitAndResetHelperButton.click(),this._form.removeChild(this.__submitAndResetHelperButton))}__setupDelegationInConstructor(){this.addEventListener("click",this.__clickDelegationHandler,!0)}}const $e=new WeakMap;function qa(){const i=document.createElement("button");return i.tabIndex=-1,i.type="submit",i.setAttribute("aria-hidden","true"),i.style.cssText=`
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
  `,i}class Wa extends Ha{get _nativeButtonNode(){return $e.get(this._form)?.helper||null}constructor(){super(),this.type="submit",this.__implicitSubmitHelperButton=null}_setupSubmitAndResetHelperOnConnected(){if(super._setupSubmitAndResetHelperOnConnected(),!this._form||this.type!=="submit")return;const e=this._form;if(!$e.get(this._form)){const s=qa(),o=document.createElement("div");o.appendChild(s),$e.set(this._form,{lionButtons:new Set,helper:s,observer:new MutationObserver(()=>{e.appendChild(o)})}),e.appendChild(o),$e.get(e)?.observer.observe(o,{childList:!0})}$e.get(e)?.lionButtons.add(this)}_teardownSubmitAndResetHelperOnDisconnected(){if(super._teardownSubmitAndResetHelperOnDisconnected(),this._form){const e=$e.get(this._form);e&&(e.lionButtons.delete(this),e.lionButtons.size||(this._form.contains(e.helper)&&e.helper.remove(),$e.get(this._form)?.observer.disconnect(),$e.delete(this._form)))}}}var ja=S`
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
    outline: 10px solid red;
  }
`,it=class extends Wa{constructor(...e){super(...e),this.appearance="accent",this.variant="default",this.size="medium",this.loading=!1,this.align="center"}static get styles(){return[...super.styles,ja]}render(){return y`
      <div
        class="${le({"button-content":!0,"button-content--start":this.align==="start","button-content--end":this.align==="end"})}"
        part="content"
      >
        <slot name="prefix" class="prefix" part="prefix"></slot>
        <slot class="label" part="label"></slot>
        <slot name="suffix" class="suffix" part="suffix"></slot>
      </div>
      ${this.loading?y`<craft-spinner part="spinner"></craft-spinner>`:j}
    `}};C([m({reflect:!0})],it.prototype,"appearance",void 0),C([m({reflect:!0})],it.prototype,"variant",void 0),C([m({reflect:!0})],it.prototype,"size",void 0),C([m({reflect:!0,type:Boolean})],it.prototype,"loading",void 0),C([m()],it.prototype,"align",void 0),customElements.get("craft-button")||customElements.define("craft-button",it);var Ka=S`
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
`,Zt=class extends P{constructor(...e){super(...e),this.label=null,this._gradientId=null}connectedCallback(){super.connectedCallback(),this._gradientId=`avatar-gradient-${Math.random().toString(36).slice(2,8)}`}text(){return this.label?this.label.split(" ").map(e=>e.charAt(0).toUpperCase()).join(""):"?"}render(){return y`
      <span class="avatar">
        <svg
          viewBox="0 0 100 100"
          xmlns="http://www.w3.org/2000/svg"
          role="img"
        >
          ${this.label?y`<title>${this.label}</title>`:""}
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
    `}};Zt.styles=[Ka],C([m()],Zt.prototype,"label",void 0),C([de()],Zt.prototype,"_gradientId",void 0),customElements.get("craft-avatar")||customElements.define("craft-avatar",Zt);const zs=S`
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
`,Li=S`
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
`,Ht=S`
  ${Li}

  ::slotted([slot='input']) {
    ${zs}
  }
`;var Ga=S`
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
`;const gs=window,ho=new WeakMap;function Ya(i){gs.applyFocusVisiblePolyfill&&!ho.has(i)&&(gs.applyFocusVisiblePolyfill(i),ho.set(i,void 0))}const Za=i=>class extends i{static get properties(){return{focused:{type:Boolean,reflect:!0},focusedVisible:{type:Boolean,reflect:!0,attribute:"focused-visible"},autofocus:{type:Boolean,reflect:!0}}}constructor(){super(),this.focused=!1,this.focusedVisible=!1,this.autofocus=!1}firstUpdated(t){super.firstUpdated(t),this.__registerEventsForFocusMixin(),this.__syncAutofocusToFocusableElement()}disconnectedCallback(){super.disconnectedCallback(),this.__teardownEventsForFocusMixin()}updated(t){super.updated(t),t.has("autofocus")&&this.__syncAutofocusToFocusableElement()}__syncAutofocusToFocusableElement(){this._focusableNode&&(this.hasAttribute("autofocus")?this._focusableNode.setAttribute("autofocus",""):this._focusableNode.removeAttribute("autofocus"))}focus(){this._focusableNode?.focus()}blur(){this._focusableNode?.blur()}get _focusableNode(){return this._inputNode||document.createElement("input")}__onFocus(){if(this.focused=!0,typeof gs.applyFocusVisiblePolyfill=="function")this.focusedVisible=this._focusableNode.hasAttribute("data-focus-visible-added");else try{this.focusedVisible=this._focusableNode.matches(":focus-visible")}catch{this.focusedVisible=!1}}__onBlur(){this.focused=!1,this.focusedVisible=!1}__registerEventsForFocusMixin(){Ya(this.getRootNode()),this.__redispatchFocus=t=>{t.stopPropagation(),this.dispatchEvent(new Event("focus"))},this._focusableNode.addEventListener("focus",this.__redispatchFocus),this.__redispatchBlur=t=>{t.stopPropagation(),this.dispatchEvent(new Event("blur"))},this._focusableNode.addEventListener("blur",this.__redispatchBlur),this.__redispatchFocusin=t=>{t.stopPropagation(),this.__onFocus(),this.dispatchEvent(new Event("focusin",{bubbles:!0,composed:!0}))},this._focusableNode.addEventListener("focusin",this.__redispatchFocusin),this.__redispatchFocusout=t=>{t.stopPropagation(),this.__onBlur(),this.dispatchEvent(new Event("focusout",{bubbles:!0,composed:!0}))},this._focusableNode.addEventListener("focusout",this.__redispatchFocusout)}__teardownEventsForFocusMixin(){this._focusableNode&&(this._focusableNode?.removeEventListener("focus",this.__redispatchFocus),this._focusableNode?.removeEventListener("blur",this.__redispatchBlur),this._focusableNode?.removeEventListener("focusin",this.__redispatchFocusin),this._focusableNode?.removeEventListener("focusout",this.__redispatchFocusout))}},Ps=Q(Za);function pn(i,e){return e={exports:{}},i(e,e.exports),e.exports}var Ke="long",Ve="short",ji="narrow",R="numeric",Re="2-digit",ze={number:{decimal:{style:"decimal"},integer:{style:"decimal",maximumFractionDigits:0},currency:{style:"currency",currency:"USD"},percent:{style:"percent"},default:{style:"decimal"}},date:{short:{month:R,day:R,year:Re},medium:{month:Ve,day:R,year:R},long:{month:Ke,day:R,year:R},full:{month:Ke,day:R,year:R,weekday:Ke},default:{month:Ve,day:R,year:R}},time:{short:{hour:R,minute:R},medium:{hour:R,minute:R,second:R},long:{hour:R,minute:R,second:R,timeZoneName:Ve},full:{hour:R,minute:R,second:R,timeZoneName:Ve},default:{hour:R,minute:R,second:R}},duration:{default:{hours:{minimumIntegerDigits:1,maximumFractionDigits:0},minutes:{minimumIntegerDigits:2,maximumFractionDigits:0},seconds:{minimumIntegerDigits:2,maximumFractionDigits:3}}},parseNumberPattern:function(i){if(i){var e={},t=i.match(/\b[A-Z]{3}\b/i),s=i.replace(/[^¤]/g,"").length;if(!s&&t&&(s=1),s?(e.style="currency",e.currencyDisplay=s===1?"symbol":s===2?"code":"name",e.currency=t?t[0].toUpperCase():"USD"):i.indexOf("%")>=0&&(e.style="percent"),!/[@#0]/.test(i))return e.style?e:void 0;if(e.useGrouping=i.indexOf(",")>=0,/E\+?[@#0]+/i.test(i)||i.indexOf("@")>=0){var o=i.replace(/E\+?[@#0]+|[^@#0]/gi,"");e.minimumSignificantDigits=Math.min(Math.max(o.replace(/[^@0]/g,"").length,1),21),e.maximumSignificantDigits=Math.min(Math.max(o.length,1),21)}else{for(var n=i.replace(/[^#0.]/g,"").split("."),r=n[0],a=r.length-1;r[a]==="0";)--a;e.minimumIntegerDigits=Math.min(Math.max(r.length-1-a,1),21);var c=n[1]||"";for(a=0;c[a]==="0";)++a;for(e.minimumFractionDigits=Math.min(Math.max(a,0),20);c[a]==="#";)++a;e.maximumFractionDigits=Math.min(Math.max(a,0),20)}return e}},parseDatePattern:function(i){if(i){for(var e={},t=0;t<i.length;){for(var s=i[t],o=1;i[++t]===s;)++o;switch(s){case"G":e.era=o===5?ji:o===4?Ke:Ve;break;case"y":case"Y":e.year=o===2?Re:R;break;case"M":case"L":o=Math.min(Math.max(o-1,0),4),e.month=[R,Re,Ve,Ke,ji][o];break;case"E":case"e":case"c":e.weekday=o===5?ji:o===4?Ke:Ve;break;case"d":case"D":e.day=o===2?Re:R;break;case"h":case"K":e.hour12=!0,e.hour=o===2?Re:R;break;case"H":case"k":e.hour12=!1,e.hour=o===2?Re:R;break;case"m":e.minute=o===2?Re:R;break;case"s":case"S":e.second=o===2?Re:R;break;case"z":case"Z":case"v":case"V":e.timeZoneName=o===1?Ve:Ke;break}}return Object.keys(e).length?e:void 0}}},Xa=function(e,t){if(typeof e=="string"&&t[e])return e;for(var s=[].concat(e||[]),o=0,n=s.length;o<n;++o)for(var r=s[o].split("-");r.length;){var a=r.join("-");if(t[a])return a;r.pop()}},st="zero",I="one",te="two",H="few",J="many",O="other",d=[function(i){var e=+i;return e===1?I:O},function(i){var e=+i;return 0<=e&&e<=1?I:O},function(i){var e=Math.floor(Math.abs(+i)),t=+i;return e===0||t===1?I:O},function(i){var e=+i;return e===0?st:e===1?I:e===2?te:3<=e%100&&e%100<=10?H:11<=e%100&&e%100<=99?J:O},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length;return e===1&&t===0?I:O},function(i){var e=+i;return e%10===1&&e%100!==11?I:2<=e%10&&e%10<=4&&(e%100<12||14<e%100)?H:e%10===0||5<=e%10&&e%10<=9||11<=e%100&&e%100<=14?J:O},function(i){var e=+i;return e%10===1&&e%100!==11&&e%100!==71&&e%100!==91?I:e%10===2&&e%100!==12&&e%100!==72&&e%100!==92?te:(3<=e%10&&e%10<=4||e%10===9)&&(e%100<10||19<e%100)&&(e%100<70||79<e%100)&&(e%100<90||99<e%100)?H:e!==0&&e%1e6===0?J:O},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length,s=+(i+".").split(".")[1];return t===0&&e%10===1&&e%100!==11||s%10===1&&s%100!==11?I:t===0&&2<=e%10&&e%10<=4&&(e%100<12||14<e%100)||2<=s%10&&s%10<=4&&(s%100<12||14<s%100)?H:O},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length;return e===1&&t===0?I:2<=e&&e<=4&&t===0?H:t!==0?J:O},function(i){var e=+i;return e===0?st:e===1?I:e===2?te:e===3?H:e===6?J:O},function(i){var e=Math.floor(Math.abs(+i)),t=+(""+i).replace(/^[^.]*.?|0+$/g,""),s=+i;return s===1||t!==0&&(e===0||e===1)?I:O},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length,s=+(i+".").split(".")[1];return t===0&&e%100===1||s%100===1?I:t===0&&e%100===2||s%100===2?te:t===0&&3<=e%100&&e%100<=4||3<=s%100&&s%100<=4?H:O},function(i){var e=Math.floor(Math.abs(+i));return e===0||e===1?I:O},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length,s=+(i+".").split(".")[1];return t===0&&(e===1||e===2||e===3)||t===0&&e%10!==4&&e%10!==6&&e%10!==9||t!==0&&s%10!==4&&s%10!==6&&s%10!==9?I:O},function(i){var e=+i;return e===1?I:e===2?te:3<=e&&e<=6?H:7<=e&&e<=10?J:O},function(i){var e=+i;return e===1||e===11?I:e===2||e===12?te:3<=e&&e<=10||13<=e&&e<=19?H:O},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length;return t===0&&e%10===1?I:t===0&&e%10===2?te:t===0&&(e%100===0||e%100===20||e%100===40||e%100===60||e%100===80)?H:t!==0?J:O},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length,s=+i;return e===1&&t===0?I:e===2&&t===0?te:t===0&&(s<0||10<s)&&s%10===0?J:O},function(i){var e=Math.floor(Math.abs(+i)),t=+(""+i).replace(/^[^.]*.?|0+$/g,"");return t===0&&e%10===1&&e%100!==11||t!==0?I:O},function(i){var e=+i;return e===1?I:e===2?te:O},function(i){var e=+i;return e===0?st:e===1?I:O},function(i){var e=Math.floor(Math.abs(+i)),t=+i;return t===0?st:(e===0||e===1)&&t!==0?I:O},function(i){var e=+(i+".").split(".")[1],t=+i;return t%10===1&&(t%100<11||19<t%100)?I:2<=t%10&&t%10<=9&&(t%100<11||19<t%100)?H:e!==0?J:O},function(i){var e=(i+".").split(".")[1].length,t=+(i+".").split(".")[1],s=+i;return s%10===0||11<=s%100&&s%100<=19||e===2&&11<=t%100&&t%100<=19?st:s%10===1&&s%100!==11||e===2&&t%10===1&&t%100!==11||e!==2&&t%10===1?I:O},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length,s=+(i+".").split(".")[1];return t===0&&e%10===1&&e%100!==11||s%10===1&&s%100!==11?I:O},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length,s=+i;return e===1&&t===0?I:t!==0||s===0||s!==1&&1<=s%100&&s%100<=19?H:O},function(i){var e=+i;return e===1?I:e===0||2<=e%100&&e%100<=10?H:11<=e%100&&e%100<=19?J:O},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length;return e===1&&t===0?I:t===0&&2<=e%10&&e%10<=4&&(e%100<12||14<e%100)?H:t===0&&e!==1&&0<=e%10&&e%10<=1||t===0&&5<=e%10&&e%10<=9||t===0&&12<=e%100&&e%100<=14?J:O},function(i){var e=Math.floor(Math.abs(+i));return 0<=e&&e<=1?I:O},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length;return t===0&&e%10===1&&e%100!==11?I:t===0&&2<=e%10&&e%10<=4&&(e%100<12||14<e%100)?H:t===0&&e%10===0||t===0&&5<=e%10&&e%10<=9||t===0&&11<=e%100&&e%100<=14?J:O},function(i){var e=Math.floor(Math.abs(+i)),t=+i;return e===0||t===1?I:2<=t&&t<=10?H:O},function(i){var e=Math.floor(Math.abs(+i)),t=+(i+".").split(".")[1],s=+i;return s===0||s===1||e===0&&t===1?I:O},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length;return t===0&&e%100===1?I:t===0&&e%100===2?te:t===0&&3<=e%100&&e%100<=4||t!==0?H:O},function(i){var e=+i;return 0<=e&&e<=1||11<=e&&e<=99?I:O},function(i){var e=+i;return e===1||e===5||e===7||e===8||e===9||e===10?I:e===2||e===3?te:e===4?H:e===6?J:O},function(i){var e=Math.floor(Math.abs(+i));return e%10===1||e%10===2||e%10===5||e%10===7||e%10===8||e%100===20||e%100===50||e%100===70||e%100===80?I:e%10===3||e%10===4||e%1e3===100||e%1e3===200||e%1e3===300||e%1e3===400||e%1e3===500||e%1e3===600||e%1e3===700||e%1e3===800||e%1e3===900?H:e===0||e%10===6||e%100===40||e%100===60||e%100===90?J:O},function(i){var e=+i;return(e%10===2||e%10===3)&&e%100!==12&&e%100!==13?H:O},function(i){var e=+i;return e===1||e===3?I:e===2?te:e===4?H:O},function(i){var e=+i;return e===0||e===7||e===8||e===9?st:e===1?I:e===2?te:e===3||e===4?H:e===5||e===6?J:O},function(i){var e=+i;return e%10===1&&e%100!==11?I:e%10===2&&e%100!==12?te:e%10===3&&e%100!==13?H:O},function(i){var e=+i;return e===1||e===11?I:e===2||e===12?te:e===3||e===13?H:O},function(i){var e=+i;return e===1?I:e===2||e===3?te:e===4?H:e===6?J:O},function(i){var e=+i;return e===1||e===5?I:O},function(i){var e=+i;return e===11||e===8||e===80||e===800?J:O},function(i){var e=Math.floor(Math.abs(+i));return e===1?I:e===0||2<=e%100&&e%100<=20||e%100===40||e%100===60||e%100===80?J:O},function(i){var e=+i;return e%10===6||e%10===9||e%10===0&&e!==0?J:O},function(i){var e=Math.floor(Math.abs(+i));return e%10===1&&e%100!==11?I:e%10===2&&e%100!==12?te:(e%10===7||e%10===8)&&e%100!==17&&e%100!==18?J:O},function(i){var e=+i;return e===1?I:e===2||e===3?te:e===4?H:O},function(i){var e=+i;return 1<=e&&e<=4?I:O},function(i){var e=+i;return e===1||e===5||7<=e&&e<=9?I:e===2||e===3?te:e===4?H:e===6?J:O},function(i){var e=+i;return e===1?I:e%10===4&&e%100!==14?J:O},function(i){var e=+i;return(e%10===1||e%10===2)&&e%100!==11&&e%100!==12?I:O},function(i){var e=+i;return e%10===6||e%10===9||e===10?H:O},function(i){var e=+i;return e%10===3&&e%100!==13?H:O}],vs={af:{cardinal:d[0]},ak:{cardinal:d[1]},am:{cardinal:d[2]},ar:{cardinal:d[3]},ars:{cardinal:d[3]},as:{cardinal:d[2],ordinal:d[34]},asa:{cardinal:d[0]},ast:{cardinal:d[4]},az:{cardinal:d[0],ordinal:d[35]},be:{cardinal:d[5],ordinal:d[36]},bem:{cardinal:d[0]},bez:{cardinal:d[0]},bg:{cardinal:d[0]},bh:{cardinal:d[1]},bn:{cardinal:d[2],ordinal:d[34]},br:{cardinal:d[6]},brx:{cardinal:d[0]},bs:{cardinal:d[7]},ca:{cardinal:d[4],ordinal:d[37]},ce:{cardinal:d[0]},cgg:{cardinal:d[0]},chr:{cardinal:d[0]},ckb:{cardinal:d[0]},cs:{cardinal:d[8]},cy:{cardinal:d[9],ordinal:d[38]},da:{cardinal:d[10]},de:{cardinal:d[4]},dsb:{cardinal:d[11]},dv:{cardinal:d[0]},ee:{cardinal:d[0]},el:{cardinal:d[0]},en:{cardinal:d[4],ordinal:d[39]},eo:{cardinal:d[0]},es:{cardinal:d[0]},et:{cardinal:d[4]},eu:{cardinal:d[0]},fa:{cardinal:d[2]},ff:{cardinal:d[12]},fi:{cardinal:d[4]},fil:{cardinal:d[13],ordinal:d[0]},fo:{cardinal:d[0]},fr:{cardinal:d[12],ordinal:d[0]},fur:{cardinal:d[0]},fy:{cardinal:d[4]},ga:{cardinal:d[14],ordinal:d[0]},gd:{cardinal:d[15],ordinal:d[40]},gl:{cardinal:d[4]},gsw:{cardinal:d[0]},gu:{cardinal:d[2],ordinal:d[41]},guw:{cardinal:d[1]},gv:{cardinal:d[16]},ha:{cardinal:d[0]},haw:{cardinal:d[0]},he:{cardinal:d[17]},hi:{cardinal:d[2],ordinal:d[41]},hr:{cardinal:d[7]},hsb:{cardinal:d[11]},hu:{cardinal:d[0],ordinal:d[42]},hy:{cardinal:d[12],ordinal:d[0]},ia:{cardinal:d[4]},io:{cardinal:d[4]},is:{cardinal:d[18]},it:{cardinal:d[4],ordinal:d[43]},iu:{cardinal:d[19]},iw:{cardinal:d[17]},jgo:{cardinal:d[0]},ji:{cardinal:d[4]},jmc:{cardinal:d[0]},ka:{cardinal:d[0],ordinal:d[44]},kab:{cardinal:d[12]},kaj:{cardinal:d[0]},kcg:{cardinal:d[0]},kk:{cardinal:d[0],ordinal:d[45]},kkj:{cardinal:d[0]},kl:{cardinal:d[0]},kn:{cardinal:d[2]},ks:{cardinal:d[0]},ksb:{cardinal:d[0]},ksh:{cardinal:d[20]},ku:{cardinal:d[0]},kw:{cardinal:d[19]},ky:{cardinal:d[0]},lag:{cardinal:d[21]},lb:{cardinal:d[0]},lg:{cardinal:d[0]},ln:{cardinal:d[1]},lt:{cardinal:d[22]},lv:{cardinal:d[23]},mas:{cardinal:d[0]},mg:{cardinal:d[1]},mgo:{cardinal:d[0]},mk:{cardinal:d[24],ordinal:d[46]},ml:{cardinal:d[0]},mn:{cardinal:d[0]},mo:{cardinal:d[25],ordinal:d[0]},mr:{cardinal:d[2],ordinal:d[47]},mt:{cardinal:d[26]},nah:{cardinal:d[0]},naq:{cardinal:d[19]},nb:{cardinal:d[0]},nd:{cardinal:d[0]},ne:{cardinal:d[0],ordinal:d[48]},nl:{cardinal:d[4]},nn:{cardinal:d[0]},nnh:{cardinal:d[0]},no:{cardinal:d[0]},nr:{cardinal:d[0]},nso:{cardinal:d[1]},ny:{cardinal:d[0]},nyn:{cardinal:d[0]},om:{cardinal:d[0]},or:{cardinal:d[0],ordinal:d[49]},os:{cardinal:d[0]},pa:{cardinal:d[1]},pap:{cardinal:d[0]},pl:{cardinal:d[27]},prg:{cardinal:d[23]},ps:{cardinal:d[0]},pt:{cardinal:d[28]},"pt-PT":{cardinal:d[4]},rm:{cardinal:d[0]},ro:{cardinal:d[25],ordinal:d[0]},rof:{cardinal:d[0]},ru:{cardinal:d[29]},rwk:{cardinal:d[0]},saq:{cardinal:d[0]},sc:{cardinal:d[4],ordinal:d[43]},scn:{cardinal:d[4],ordinal:d[43]},sd:{cardinal:d[0]},sdh:{cardinal:d[0]},se:{cardinal:d[19]},seh:{cardinal:d[0]},sh:{cardinal:d[7]},shi:{cardinal:d[30]},si:{cardinal:d[31]},sk:{cardinal:d[8]},sl:{cardinal:d[32]},sma:{cardinal:d[19]},smi:{cardinal:d[19]},smj:{cardinal:d[19]},smn:{cardinal:d[19]},sms:{cardinal:d[19]},sn:{cardinal:d[0]},so:{cardinal:d[0]},sq:{cardinal:d[0],ordinal:d[50]},sr:{cardinal:d[7]},ss:{cardinal:d[0]},ssy:{cardinal:d[0]},st:{cardinal:d[0]},sv:{cardinal:d[4],ordinal:d[51]},sw:{cardinal:d[4]},syr:{cardinal:d[0]},ta:{cardinal:d[0]},te:{cardinal:d[0]},teo:{cardinal:d[0]},ti:{cardinal:d[1]},tig:{cardinal:d[0]},tk:{cardinal:d[0],ordinal:d[52]},tl:{cardinal:d[13],ordinal:d[0]},tn:{cardinal:d[0]},tr:{cardinal:d[0]},ts:{cardinal:d[0]},tzm:{cardinal:d[33]},ug:{cardinal:d[0]},uk:{cardinal:d[29],ordinal:d[53]},ur:{cardinal:d[4]},uz:{cardinal:d[0]},ve:{cardinal:d[0]},vo:{cardinal:d[0]},vun:{cardinal:d[0]},wa:{cardinal:d[1]},wae:{cardinal:d[0]},xh:{cardinal:d[0]},xog:{cardinal:d[0]},yi:{cardinal:d[4]},zu:{cardinal:d[2]},lo:{ordinal:d[0]},ms:{ordinal:d[0]},vi:{ordinal:d[0]}},Oi=pn(function(i,e){e=i.exports=function(f,_,v){return t(f,null,_||"en",v||{},!0)},e.toParts=function(f,_,v){return t(f,null,_||"en",v||{},!1)};function t(p,f,_,v,w){var x=p.map(function(A){return s(A,f,_,v,w)});return w?x.length===1?x[0]:function(L){for(var N="",V=0;V<x.length;++V)N+=x[V](L);return N}:function(L){return x.reduce(function(N,V){return N.concat(V(L))},[])}}function s(p,f,_,v,w){if(typeof p=="string"){var x=p;return function(){return x}}var A=p[0],L=p[1];if(f&&p[0]==="#"){A=f[0];var N=f[2],V=(v.number||b.number)([A,"number"],_);return function(M){return V(o(A,M)-N,M)}}var B;L==="plural"||L==="selectordinal"?(B={},Object.keys(p[3]).forEach(function(Z){B[Z]=t(p[3][Z],p,_,v,w)}),p=[p[0],p[1],p[2],B]):p[2]&&typeof p[2]=="object"&&(B={},Object.keys(p[2]).forEach(function(Z){B[Z]=t(p[2][Z],p,_,v,w)}),p=[p[0],p[1],B]);var U=L&&(v[L]||b[L]);if(U){var ee=U(p,_);return function(M){return ee(o(A,M),M)}}return w?function(M){return String(o(A,M))}:function(M){return o(A,M)}}function o(p,f){if(f&&p in f)return f[p];for(var _=p.split("."),v=f,w=0,x=_.length;v&&w<x;++w)v=v[_[w]];return v}function n(p,f){var _=p[2],v=ze.number[_]||ze.parseNumberPattern(_)||ze.number.default;return new Intl.NumberFormat(f,v).format}function r(p,f){var _=p[2],v=ze.duration[_]||ze.duration.default,w=new Intl.NumberFormat(f,v.seconds).format,x=new Intl.NumberFormat(f,v.minutes).format,A=new Intl.NumberFormat(f,v.hours).format,L=/^fi$|^fi-|^da/.test(String(f))?".":":";return function(N,V){if(N=+N,!isFinite(N))return w(N);var B=~~(N/60/60),U=~~(N/60%60),ee=(B?A(Math.abs(B))+L:"")+x(Math.abs(U))+L+w(Math.abs(N%60));return N<0?A(-1).replace(A(1),ee):ee}}function a(p,f){var _=p[1],v=p[2],w=ze[_][v]||ze.parseDatePattern(v)||ze[_].default;return new Intl.DateTimeFormat(f,w).format}function c(p,f){var _=p[1],v=_==="selectordinal"?"ordinal":"cardinal",w=p[2],x=p[3],A;if(Intl.PluralRules&&Intl.PluralRules.supportedLocalesOf(f).length>0)A=new Intl.PluralRules(f,{type:v});else{var L=Xa(f,vs),N=L&&vs[L][v]||u;A={select:N}}return function(V,B){var U=x["="+ +V]||x[A.select(V-w)]||x.other;return U(B)}}function u(){return"other"}function h(p,f){var _=p[2];return function(v,w){var x=_[v]||_.other;return x(w)}}var b={number:n,ordinal:n,spellout:n,duration:r,date:a,time:a,plural:c,selectordinal:c,select:h};e.types=b});Oi.toParts;Oi.types;var fn=pn(function(i,e){var t="{",s="}",o=",",n="#",r="<",a=">",c="</",u="/>",h="'",b="offset:",p=["number","date","time","ordinal","duration","spellout"],f=["plural","select","selectordinal"];e=i.exports=function(k,F){return _({pattern:String(k),index:0,tagsType:F&&F.tagsType||null,tokens:F&&F.tokens||null},"")};function _(l,k){var F=l.pattern,$=F.length,D=[],T=l.index,W=v(l,k);for(W&&D.push(W),W&&l.tokens&&l.tokens.push(["text",F.slice(T,l.index)]);l.index<$;){if(F[l.index]===s){if(!k)throw M(l);break}if(k&&l.tagsType&&F.slice(l.index,l.index+c.length)===c)break;D.push(A(l)),T=l.index,W=v(l,k),W&&D.push(W),W&&l.tokens&&l.tokens.push(["text",F.slice(T,l.index)])}return D}function v(l,k){for(var F=l.pattern,$=F.length,D=k==="plural"||k==="selectordinal",T=!!l.tagsType,W=k==="{style}",he="";l.index<$;){var X=F[l.index];if(X===t||X===s||D&&X===n||T&&X===r||W&&w(X.charCodeAt(0)))break;if(X===h)if(X=F[++l.index],X===h)he+=X,++l.index;else if(X===t||X===s||D&&X===n||T&&X===r||W)for(he+=X;++l.index<$;)if(X=F[l.index],X===h&&F[l.index+1]===h)he+=h,++l.index;else if(X===h){++l.index;break}else he+=X;else he+=h;else he+=X,++l.index}return he}function w(l){return l>=9&&l<=13||l===32||l===133||l===160||l===6158||l>=8192&&l<=8205||l===8232||l===8233||l===8239||l===8287||l===8288||l===12288||l===65279}function x(l){for(var k=l.pattern,F=k.length,$=l.index;l.index<F&&w(k.charCodeAt(l.index));)++l.index;$<l.index&&l.tokens&&l.tokens.push(["space",l.pattern.slice($,l.index)])}function A(l){var k=l.pattern;if(k[l.index]===n)return l.tokens&&l.tokens.push(["syntax",n]),++l.index,[n];var F=L(l);if(F)return F;if(k[l.index]!==t)throw M(l,t);l.tokens&&l.tokens.push(["syntax",t]),++l.index,x(l);var $=N(l);if(!$)throw M(l,"placeholder id");l.tokens&&l.tokens.push(["id",$]),x(l);var D=k[l.index];if(D===s)return l.tokens&&l.tokens.push(["syntax",s]),++l.index,[$];if(D!==o)throw M(l,o+" or "+s);l.tokens&&l.tokens.push(["syntax",o]),++l.index,x(l);var T=N(l);if(!T)throw M(l,"placeholder type");if(l.tokens&&l.tokens.push(["type",T]),x(l),D=k[l.index],D===s){if(l.tokens&&l.tokens.push(["syntax",s]),T==="plural"||T==="selectordinal"||T==="select")throw M(l,T+" sub-messages");return++l.index,[$,T]}if(D!==o)throw M(l,o+" or "+s);l.tokens&&l.tokens.push(["syntax",o]),++l.index,x(l);var W;if(T==="plural"||T==="selectordinal"){var he=B(l);x(l),W=[$,T,he,ee(l,T)]}else if(T==="select")W=[$,T,ee(l,T)];else if(p.indexOf(T)>=0)W=[$,T,V(l)];else{var X=l.index,js=V(l);x(l),k[l.index]===t&&(l.index=X,js=ee(l,T)),W=[$,T,js]}if(x(l),k[l.index]!==s)throw M(l,s);return l.tokens&&l.tokens.push(["syntax",s]),++l.index,W}function L(l){var k=l.tagsType;if(!(!k||l.pattern[l.index]!==r)){if(l.pattern.slice(l.index,l.index+c.length)===c)throw M(l,null,"closing tag without matching opening tag");l.tokens&&l.tokens.push(["syntax",r]),++l.index;var F=N(l,!0);if(!F)throw M(l,"placeholder id");if(l.tokens&&l.tokens.push(["id",F]),x(l),l.pattern.slice(l.index,l.index+u.length)===u)return l.tokens&&l.tokens.push(["syntax",u]),l.index+=u.length,[F,k];if(l.pattern[l.index]!==a)throw M(l,a);l.tokens&&l.tokens.push(["syntax",a]),++l.index;var $=_(l,k),D=l.index;if(l.pattern.slice(l.index,l.index+c.length)!==c)throw M(l,c+F+a);l.tokens&&l.tokens.push(["syntax",c]),l.index+=c.length;var T=N(l,!0);if(T&&l.tokens&&l.tokens.push(["id",T]),F!==T)throw l.index=D,M(l,c+F+a,c+T+a);if(x(l),l.pattern[l.index]!==a)throw M(l,a);return l.tokens&&l.tokens.push(["syntax",a]),++l.index,[F,k,{children:$}]}}function N(l,k){for(var F=l.pattern,$=F.length,D="";l.index<$;){var T=F[l.index];if(T===t||T===s||T===o||T===n||T===h||w(T.charCodeAt(0))||k&&(T===r||T===a||T==="/"))break;D+=T,++l.index}return D}function V(l){var k=l.index,F=v(l,"{style}");if(!F)throw M(l,"placeholder style name");return l.tokens&&l.tokens.push(["style",l.pattern.slice(k,l.index)]),F}function B(l){var k=l.pattern,F=k.length,$=0;if(k.slice(l.index,l.index+b.length)===b){l.tokens&&l.tokens.push(["offset","offset"],["syntax",":"]),l.index+=b.length,x(l);for(var D=l.index;l.index<F&&U(k.charCodeAt(l.index));)++l.index;if(D===l.index)throw M(l,"offset number");l.tokens&&l.tokens.push(["number",k.slice(D,l.index)]),$=+k.slice(D,l.index)}return $}function U(l){return l>=48&&l<=57}function ee(l,k){for(var F=l.pattern,$=F.length,D={};l.index<$&&F[l.index]!==s;){var T=N(l);if(!T)throw M(l,"sub-message selector");l.tokens&&l.tokens.push(["selector",T]),x(l),D[T]=Z(l,k),x(l)}if(!D.other&&f.indexOf(k)>=0)throw M(l,null,null,'"other" sub-message must be specified in '+k);return D}function Z(l,k){if(l.pattern[l.index]!==t)throw M(l,t+" to start sub-message");l.tokens&&l.tokens.push(["syntax",t]),++l.index;var F=_(l,k);if(l.pattern[l.index]!==s)throw M(l,s+" to end sub-message");return l.tokens&&l.tokens.push(["syntax",s]),++l.index,F}function M(l,k,F,$){var D=l.pattern,T=D.slice(0,l.index).split(/\r?\n/),W=l.index,he=T.length,X=T.slice(-1)[0].length;return F=F||(l.index>=D.length?"end of message pattern":N(l)||D[l.index]),$||($=Ae(k,F)),$+=" in "+D.replace(/\r?\n/g,`
`),new ce($,k,F,W,he,X)}function Ae(l,k){return l?"Expected "+l+" but found "+k:"Unexpected "+k+" found"}function ce(l,k,F,$,D,T){Error.call(this,l),this.name="SyntaxError",this.message=l,this.expected=k,this.found=F,this.offset=$,this.line=D,this.column=T}ce.prototype=Object.create(Error.prototype),e.SyntaxError=ce});fn.SyntaxError;var Qa=new RegExp("^("+Object.keys(vs).join("|")+")\\b"),Lt=new WeakMap;function ut(i,e,t){if(!(this instanceof ut)||Lt.has(this))throw new TypeError("calling MessageFormat constructor without new is invalid");var s=fn(i);Lt.set(this,{ast:s,format:Oi(s,e,t&&t.types),locale:ut.supportedLocalesOf(e)[0]||"en",locales:e,options:t})}var Ja=ut;Object.defineProperties(ut.prototype,{format:{configurable:!0,get:function(){var e=Lt.get(this);if(!e)throw new TypeError("MessageFormat.prototype.format called on value that's not an object initialized as a MessageFormat");return e.format}},formatToParts:{configurable:!0,writable:!0,value:function(e){var t=Lt.get(this);if(!t)throw new TypeError("MessageFormat.prototype.formatToParts called on value that's not an object initialized as a MessageFormat");var s=t.toParts||(t.toParts=Oi.toParts(t.ast,t.locales,t.options&&t.options.types));return s(e)}},resolvedOptions:{configurable:!0,writable:!0,value:function(){var e=Lt.get(this);if(!e)throw new TypeError("MessageFormat.prototype.resolvedOptions called on value that's not an object initialized as a MessageFormat");return{locale:e.locale}}}});typeof Symbol<"u"&&Object.defineProperty(ut.prototype,Symbol.toStringTag,{value:"Object"});Object.defineProperties(ut,{supportedLocalesOf:{configurable:!0,writable:!0,value:function(e){return[].concat(Intl.NumberFormat.supportedLocalesOf(e),Intl.DateTimeFormat.supportedLocalesOf(e),Intl.PluralRules?Intl.PluralRules.supportedLocalesOf(e):[],[].concat(e||[]).filter(function(t){return Qa.test(t)})).filter(function(t,s,o){return o.indexOf(t)===s})}}});function el(i){return!!(i&&i.default&&typeof i.default=="object"&&Object.keys(i).length===1)}const Pe=globalThis.document?.documentElement;class tl extends EventTarget{formatNumberOptions={returnIfNaN:"",postProcessors:new Map};formatDateOptions={postProcessors:new Map};#e=!1;#t="";#i=null;__storage={};__namespacePatternsMap=new Map;__namespaceLoadersCache={};__namespaceLoaderPromisesCache={};get locale(){return this.#e?this.#t||"":Pe.lang||""}set locale(e){if(this.#s(e),!this.#e){const o=Pe.lang;this._setHtmlLangAttribute(e),this._onLocaleChanged(e,o);return}const t=this.#t;this.#t=e,this.#i===null&&this._setHtmlLangAttribute(e),this._onLocaleChanged(e,t)}get loadingComplete(){return typeof this.__namespaceLoaderPromisesCache[this.locale]=="object"?Promise.all(Object.values(this.__namespaceLoaderPromisesCache[this.locale])):Promise.resolve()}constructor({allowOverridesForExistingNamespaces:e=!1,autoLoadOnLocaleChange:t=!1,showKeyAsFallback:s=!1,fallbackLocale:o=""}={}){super(),this.__allowOverridesForExistingNamespaces=e,this._autoLoadOnLocaleChange=!!t,this._showKeyAsFallback=s,this._fallbackLocale=o;const n=Pe.getAttribute("data-localize-lang");this.#e=!!n,this.#e&&(this.locale=n,this._setupTranslationToolSupport()),Pe.lang||(Pe.lang=this.locale||"en-GB"),this._setupHtmlLangAttributeObserver()}addData(e,t,s){if(!this.__allowOverridesForExistingNamespaces&&this._isNamespaceInCache(e,t))throw new Error(`Namespace "${t}" has been already added for the locale "${e}".`);this.__storage[e]=this.__storage[e]||{},this.__allowOverridesForExistingNamespaces?this.__storage[e][t]={...this.__storage[e][t],...s}:this.__storage[e][t]=s}setupNamespaceLoader(e,t){this.__namespacePatternsMap.set(e,t)}loadNamespaces(e,{locale:t}={}){return Promise.all(e.map(s=>this.loadNamespace(s,{locale:t})))}loadNamespace(e,{locale:t=this.locale}={locale:this.locale}){const s=typeof e=="object",o=s?Object.keys(e)[0]:e;if(this._isNamespaceInCache(t,o))return Promise.resolve();const n=this._getCachedNamespaceLoaderPromise(t,o);return n||this._loadNamespaceData(t,e,s,o)}msg(e,t,s={}){const o=s.locale?s.locale:this.locale,n=this._getMessageForKeys(e,o);return n?new Ja(n,o).format(t):""}teardown(){this._teardownHtmlLangAttributeObserver()}reset(){this.__storage={},this.__namespacePatternsMap=new Map,this.__namespaceLoadersCache={},this.__namespaceLoaderPromisesCache={}}setDatePostProcessorForLocale({locale:e,postProcessor:t}){this.formatDateOptions?.postProcessors.set(e,t)}setNumberPostProcessorForLocale({locale:e,postProcessor:t}){this.formatNumberOptions?.postProcessors.set(e,t)}_setupTranslationToolSupport(){this.#i=Pe.lang||null}_setHtmlLangAttribute(e){this._teardownHtmlLangAttributeObserver(),Pe.lang=e,this._setupHtmlLangAttributeObserver()}_setupHtmlLangAttributeObserver(){this._htmlLangAttributeObserver||(this._htmlLangAttributeObserver=new MutationObserver(e=>{e.forEach(t=>{this.#e?Pe.lang==="auto"?(this.#i=null,this._setHtmlLangAttribute(this.locale)):this.#i=document.documentElement.lang:this._onLocaleChanged(document.documentElement.lang,t.oldValue||"")})})),this._htmlLangAttributeObserver.observe(document.documentElement,{attributes:!0,attributeFilter:["lang"],attributeOldValue:!0})}_teardownHtmlLangAttributeObserver(){this._htmlLangAttributeObserver&&this._htmlLangAttributeObserver.disconnect()}_isNamespaceInCache(e,t){return!!(this.__storage[e]&&this.__storage[e][t])}_getCachedNamespaceLoaderPromise(e,t){return this.__namespaceLoaderPromisesCache[e]?this.__namespaceLoaderPromisesCache[e][t]:null}_loadNamespaceData(e,t,s,o){const n=this._getNamespaceLoader(t,s,o),r=this._getNamespaceLoaderPromise(n,e,o);return this._cacheNamespaceLoaderPromise(e,o,r),r.then(a=>{if(this.__namespaceLoaderPromisesCache[e]&&this.__namespaceLoaderPromisesCache[e][o]===r){const c=el(a)?a.default:a;this.addData(e,o,c)}})}_getNamespaceLoader(e,t,s){let o=this.__namespaceLoadersCache[s];if(o||(t?(o=e[s],this.__namespaceLoadersCache[s]=o):(o=this._lookupNamespaceLoader(s),this.__namespaceLoadersCache[s]=o)),!o)throw new Error(`Namespace "${s}" was not properly setup.`);return this.__namespaceLoadersCache[s]=o,o}_getNamespaceLoaderPromise(e,t,s,o=this._fallbackLocale){return e(t,s).catch(()=>{const n=this._getLangFromLocale(t);return e(n,s).catch(()=>{if(o)return this._getNamespaceLoaderPromise(e,o,s,"").catch(()=>{const r=this._getLangFromLocale(o);throw new Error(`Data for namespace "${s}" and current locale "${t}" or fallback locale "${o}" could not be loaded. Make sure you have data either for locale "${t}" (and/or generic language "${n}") or for fallback "${o}" (and/or "${r}").`)});throw new Error(`Data for namespace "${s}" and locale "${t}" could not be loaded. Make sure you have data for locale "${t}" (and/or generic language "${n}").`)})})}_cacheNamespaceLoaderPromise(e,t,s){this.__namespaceLoaderPromisesCache[e]||(this.__namespaceLoaderPromisesCache[e]={}),this.__namespaceLoaderPromisesCache[e][t]=s}_lookupNamespaceLoader(e){for(const[t,s]of this.__namespacePatternsMap){const o=typeof t=="string"&&t===e,n=typeof t=="object"&&t.constructor.name==="RegExp"&&t.test(e);if(o||n)return s}return null}_getLangFromLocale(e){return e.substring(0,2)}_onLocaleChanged(e,t){this.dispatchEvent(new CustomEvent("__localeChanging")),e!==t&&(this._autoLoadOnLocaleChange?(this._loadAllMissing(e,t),this.loadingComplete.then(()=>{this.dispatchEvent(new CustomEvent("localeChanged",{detail:{newLocale:e,oldLocale:t}}))})):this.dispatchEvent(new CustomEvent("localeChanged",{detail:{newLocale:e,oldLocale:t}})))}_loadAllMissing(e,t){const s=this.__storage[t]||{},o=this.__storage[e]||{};Object.keys(s).forEach(n=>{o[n]||this.loadNamespace(n,{locale:e})})}_getMessageForKeys(e,t){if(typeof e=="string")return this._getMessageForKey(e,t);const s=Array.from(e).reverse();let o,n;for(;s.length;)if(o=s.pop(),n=this._getMessageForKey(o,t),n)return n}_getMessageForKey(e,t){if(!e||e.indexOf(":")===-1)throw new Error(`Namespace is missing in the key "${e}". The format for keys is "namespace:name".`);const[s,o]=e.split(":"),n=this.__storage[t],r=n?n[s]:{},c=o.split(".").reduce((u,h)=>typeof u=="object"?u[h]:u,r);return String(c||(this._showKeyAsFallback?e:""))}#s(e){if(!e.includes("-"))throw new Error(`
      Locale was set to ${e}.
      Language only locales are not allowed, please use the full language locale e.g. 'en-GB' instead of 'en'.
      See https://github.com/ing-bank/lion/issues/187 for more information.
    `)}get _supportExternalTranslationTools(){return this.#e}set _supportExternalTranslationTools(e){this.#e=e}get _langAttrSetByTranslationTool(){return this.#t}set _langAttrSetByTranslationTool(e){this.#t=e}}const Ki=Symbol.for("lion::SingletonManagerClassStorage"),Gi=globalThis||window;class il{constructor(){this._map=Gi[Ki]?Gi[Ki]:Gi[Ki]=new Map}set(e,t){this.has(e)||this._map.set(e,t)}get(e){return this._map.get(e)}has(e){return this._map.has(e)}}const li=new il;function vi(){if(li.has("@lion/ui::localize::0.x"))return li.get("@lion/ui::localize::0.x");const i=new tl({autoLoadOnLocaleChange:!0,fallbackLocale:"en-GB"});return li.set("@lion/ui::localize::0.x",i),i}const Ot=(i,e)=>{const t=i._$AN;if(t===void 0)return!1;for(const s of t)s._$AO?.(e,!1),Ot(s,e);return!0},_i=i=>{let e,t;do{if((e=i._$AM)===void 0)break;t=e._$AN,t.delete(i),i=e}while(t?.size===0)},mn=i=>{for(let e;e=i._$AM;i=e){let t=e._$AN;if(t===void 0)e._$AN=t=new Set;else if(t.has(i))break;t.add(i),nl(e)}};function sl(i){this._$AN!==void 0?(_i(this),this._$AM=i,mn(this)):this._$AM=i}function ol(i,e=!1,t=0){const s=this._$AH,o=this._$AN;if(o!==void 0&&o.size!==0)if(e)if(Array.isArray(s))for(let n=t;n<s.length;n++)Ot(s[n],!1),_i(s[n]);else s!=null&&(Ot(s,!1),_i(s));else Ot(this,i)}const nl=i=>{i.type==Ei.CHILD&&(i._$AP??=ol,i._$AQ??=sl)};class rl extends ki{constructor(){super(...arguments),this._$AN=void 0}_$AT(e,t,s){super._$AT(e,t,s),mn(this),this.isConnected=e._$AU}_$AO(e,t=!0){e!==this.isConnected&&(this.isConnected=e,e?this.reconnected?.():this.disconnected?.()),t&&(Ot(this,e),_i(this))}setValue(e){if($a(this._$Ct))this._$Ct._$AI(e,this);else{const t=[...this._$Ct._$AH];t[this._$Ci]=e,this._$Ct._$AI(t,this,0)}}disconnected(){}reconnected(){}}let al=class{constructor(e){this.G=e}disconnect(){this.G=void 0}reconnect(e){this.G=e}deref(){return this.G}},ll=class{constructor(){this.Y=void 0,this.Z=void 0}get(){return this.Y}pause(){this.Y??=new Promise((e=>this.Z=e))}resume(){this.Z?.(),this.Y=this.Z=void 0}};const po=i=>!Ma(i)&&typeof i.then=="function",fo=1073741823;let cl=class extends rl{constructor(){super(...arguments),this._$Cwt=fo,this._$Cbt=[],this._$CK=new al(this),this._$CX=new ll}render(...e){return e.find((t=>!po(t)))??Dt}update(e,t){const s=this._$Cbt;let o=s.length;this._$Cbt=t;const n=this._$CK,r=this._$CX;this.isConnected||this.disconnected();for(let a=0;a<t.length&&!(a>this._$Cwt);a++){const c=t[a];if(!po(c))return this._$Cwt=a,c;a<o&&c===s[a]||(this._$Cwt=fo,o=0,Promise.resolve(c).then((async u=>{for(;r.get();)await r.get();const h=n.deref();if(h!==void 0){const b=h._$Cbt.indexOf(c);b>-1&&b<h._$Cwt&&(h._$Cwt=b,h.setValue(u))}})))}return Dt}disconnected(){this._$CK.disconnect(),this._$CX.pause()}reconnected(){this._$CK.reconnect(this),this._$CX.resume()}};const dl=Ci(cl),ul=i=>class extends i{static get localizeNamespaces(){return[]}static get waitForLocalizeNamespaces(){return!0}constructor(){super(),this._localizeManager=vi(),this.__boundLocalizeOnLocaleChanged=(...t)=>{const s=Array.from(t)[0];this.__localizeOnLocaleChanged(s)},this.__boundLocalizeOnLocaleChanging=()=>{this.__localizeOnLocaleChanging()},this.__localizeStartLoadingNamespaces(),this.localizeNamespacesLoaded&&this.localizeNamespacesLoaded.then(()=>{this.__localizeMessageSync=!0})}async scheduleUpdate(){Object.getPrototypeOf(this).constructor.waitForLocalizeNamespaces&&await this.localizeNamespacesLoaded,super.scheduleUpdate()}connectedCallback(){super.connectedCallback(),this.localizeNamespacesLoaded&&this.localizeNamespacesLoaded.then(()=>this.onLocaleReady()),this._localizeManager.addEventListener("__localeChanging",this.__boundLocalizeOnLocaleChanging),this._localizeManager.addEventListener("localeChanged",this.__boundLocalizeOnLocaleChanged)}disconnectedCallback(){super.disconnectedCallback(),this._localizeManager.removeEventListener("__localeChanging",this.__boundLocalizeOnLocaleChanging),this._localizeManager.removeEventListener("localeChanged",this.__boundLocalizeOnLocaleChanged)}msgLit(t,s,o){return this.__localizeMessageSync?this._localizeManager.msg(t,s,o):this.localizeNamespacesLoaded?dl(this.localizeNamespacesLoaded.then(()=>this._localizeManager.msg(t,s,o)),j):""}__getUniqueNamespaces(){const t=[],s=new Set;return Object.getPrototypeOf(this).constructor.localizeNamespaces.forEach(s.add.bind(s)),s.forEach(o=>{t.push(o)}),t}__localizeStartLoadingNamespaces(){this.localizeNamespacesLoaded=this._localizeManager.loadNamespaces(this.__getUniqueNamespaces())}__localizeOnLocaleChanging(){this.__localizeStartLoadingNamespaces()}__localizeOnLocaleChanged(t){this.onLocaleChanged(t.detail.newLocale,t.detail.oldLocale)}onLocaleReady(){this.onLocaleUpdated()}onLocaleChanged(t,s){this.onLocaleUpdated(),this.requestUpdate()}onLocaleUpdated(){}},Ii=Q(ul),_s="3.0.0",mo=window.scopedElementsVersions||(window.scopedElementsVersions=[]);mo.includes(_s)||mo.push(_s);const hl=i=>class extends i{static scopedElements;static get scopedElementsVersion(){return _s}static __registry;get registry(){return this.constructor.__registry}set registry(t){this.constructor.__registry=t}attachShadow(t){const{scopedElements:s}=this.constructor;if(!this.registry||this.registry===this.constructor.__registry&&!Object.prototype.hasOwnProperty.call(this.constructor,"__registry")){this.registry=new CustomElementRegistry;for(const[n,r]of Object.entries(s??{}))this.registry.define(n,r)}return super.attachShadow({...t,customElements:this.registry,registry:this.registry})}},pl=Q(hl),fl=i=>class extends pl(i){createRenderRoot(){const{shadowRootOptions:t,elementStyles:s}=this.constructor,o=this.attachShadow(t);return this.renderOptions.creationScope=o,Ro(o,s),this.renderOptions.renderBefore??=o.firstChild,o}},ml=Q(fl);function Xt(){return!!(globalThis.ShadowRoot?.prototype.createElement&&globalThis.ShadowRoot?.prototype.importNode)}const bl=i=>class extends ml(i){constructor(){super()}createScopedElement(t){return(Xt()?this.shadowRoot:document).createElement(t)}defineScopedElement(t,s){const o=this.registry.get(t),n=o&&o!==s;return!Xt()&&n&&console.error([`You are trying to re-register the "${t}" custom element with a different class via ScopedElementsMixin.`,"This is only possible with a CustomElementRegistry.","Your browser does not support this feature so you will need to load a polyfill for it.",'Load "@webcomponents/scoped-custom-element-registry" before you register ANY web component to the global customElements registry.','e.g. add "<script src="/node_modules/@webcomponents/scoped-custom-element-registry/scoped-custom-element-registry.min.js"><\/script>" as your first script tag.',"For more details you can visit https://open-wc.org/docs/development/scoped-elements/"].join(`
`)),o?this.registry.get(t):this.registry.define(t,s)}attachShadow(t){const{scopedElements:s}=this.constructor;if(!this.registry||this.registry===this.constructor.__registry&&!Object.prototype.hasOwnProperty.call(this.constructor,"__registry")){this.registry=Xt()?new CustomElementRegistry:customElements;for(const[n,r]of Object.entries(s??{}))this.defineScopedElement(n,r)}return Element.prototype.attachShadow.call(this,{...t,customElements:this.registry,registry:this.registry})}createRenderRoot(){const{shadowRootOptions:t,elementStyles:s}=this.constructor,o=this.attachShadow(t);return Xt()&&(this.renderOptions.creationScope=o),o instanceof ShadowRoot&&(Ro(o,s),this.renderOptions.renderBefore=this.renderOptions.renderBefore||o.firstChild),o}},qt=Q(bl);class gl{constructor(){this.__running=!1,this.__queue=[]}add(e){this.__queue.push(e),this.__running||(this.complete=new Promise(t=>{this.__callComplete=t}),this.__run())}async __run(){this.__running=!0,await this.__queue[0](),this.__queue.shift(),this.__queue.length>0?this.__run():(this.__running=!1,this.__callComplete&&this.__callComplete())}}function vl(i){return i.charAt(0).toUpperCase()+i.slice(1)}const _l=i=>class extends i{constructor(){super(),this.__SyncUpdatableNamespace={}}firstUpdated(e){super.firstUpdated(e),this.__syncUpdatableInitialize()}connectedCallback(){super.connectedCallback(),this.__SyncUpdatableNamespace.connected=!0}disconnectedCallback(){super.disconnectedCallback(),this.__SyncUpdatableNamespace.connected=!1}static enabledWarnings=super.enabledWarnings?.filter(e=>e!=="change-in-update")||[];static __syncUpdatableHasChanged(e,t,s){const o=this.elementProperties;return o.get(e)&&o.get(e).hasChanged?o.get(e).hasChanged(t,s):t!==s}__syncUpdatableInitialize(){const e=this.__SyncUpdatableNamespace,t=this.constructor;e.initialized=!0,e.queue&&Array.from(e.queue).forEach(s=>{t.__syncUpdatableHasChanged(s,this[s],void 0)&&this.updateSync(s,void 0)})}requestUpdate(e,t,s){if(super.requestUpdate(e,t,s),e===void 0)return;this.__SyncUpdatableNamespace=this.__SyncUpdatableNamespace||{};const o=this.__SyncUpdatableNamespace,n=this.constructor;o.initialized?n.__syncUpdatableHasChanged(e,this[e],t)&&this.updateSync(e,t):(o.queue=o.queue||new Set,o.queue.add(e))}updateSync(e,t){}},yl=Q(_l),wl=i=>{switch(i){case"bg-BG":return E(()=>import("./bg-BG2.js"),__vite__mapDeps([0,1]),import.meta.url);case"bg":return E(()=>import("./bg3.js"),[],import.meta.url);case"cs-CZ":return E(()=>import("./cs-CZ2.js"),__vite__mapDeps([2,3]),import.meta.url);case"cs":return E(()=>import("./cs3.js"),[],import.meta.url);case"de-DE":return E(()=>import("./de-DE2.js"),__vite__mapDeps([4,5]),import.meta.url);case"de":return E(()=>import("./de3.js"),[],import.meta.url);case"en-AU":return E(()=>import("./en-AU2.js"),__vite__mapDeps([6,7]),import.meta.url);case"en-GB":return E(()=>import("./en-GB2.js"),__vite__mapDeps([8,7]),import.meta.url);case"en-US":return E(()=>import("./en-US2.js"),__vite__mapDeps([9,7]),import.meta.url);case"en-PH":case"en":return E(()=>import("./en3.js"),[],import.meta.url);case"es-ES":return E(()=>import("./es-ES2.js"),__vite__mapDeps([10,11]),import.meta.url);case"es":return E(()=>import("./es3.js"),[],import.meta.url);case"fr-FR":return E(()=>import("./fr-FR2.js"),__vite__mapDeps([12,13]),import.meta.url);case"fr-BE":return E(()=>import("./fr-BE2.js"),__vite__mapDeps([14,13]),import.meta.url);case"fr":return E(()=>import("./fr3.js"),[],import.meta.url);case"hu-HU":return E(()=>import("./hu-HU2.js"),__vite__mapDeps([15,16]),import.meta.url);case"hu":return E(()=>import("./hu3.js"),[],import.meta.url);case"it-IT":return E(()=>import("./it-IT2.js"),__vite__mapDeps([17,18]),import.meta.url);case"it":return E(()=>import("./it3.js"),[],import.meta.url);case"nl-BE":return E(()=>import("./nl-BE2.js"),__vite__mapDeps([19,20]),import.meta.url);case"nl-NL":return E(()=>import("./nl-NL2.js"),__vite__mapDeps([21,20]),import.meta.url);case"nl":return E(()=>import("./nl3.js"),[],import.meta.url);case"pl-PL":return E(()=>import("./pl-PL2.js"),__vite__mapDeps([22,23]),import.meta.url);case"pl":return E(()=>import("./pl3.js"),[],import.meta.url);case"ro-RO":return E(()=>import("./ro-RO2.js"),__vite__mapDeps([24,25]),import.meta.url);case"ro":return E(()=>import("./ro3.js"),[],import.meta.url);case"ru-RU":return E(()=>import("./ru-RU2.js"),__vite__mapDeps([26,27]),import.meta.url);case"ru":return E(()=>import("./ru3.js"),[],import.meta.url);case"sk-SK":return E(()=>import("./sk-SK2.js"),__vite__mapDeps([28,29]),import.meta.url);case"sk":return E(()=>import("./sk3.js"),[],import.meta.url);case"tr-TR":return E(()=>import("./tr-TR.js"),__vite__mapDeps([30,31]),import.meta.url);case"tr":return E(()=>import("./tr.js"),[],import.meta.url);case"uk-UA":return E(()=>import("./uk-UA2.js"),__vite__mapDeps([32,33]),import.meta.url);case"uk":return E(()=>import("./uk3.js"),[],import.meta.url);case"zh-CN":case"zh":return E(()=>import("./zh3.js"),[],import.meta.url);default:return E(()=>import("./en3.js"),[],import.meta.url)}},xl=i=>`${i[0].toUpperCase()}${i.slice(1)}`;class bn extends Ii(P){static get properties(){return{feedbackData:{attribute:!1}}}static localizeNamespaces=[{"lion-form-core":wl},...super.localizeNamespaces];static get styles(){return[S`
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
      `]}constructor(){super(),this.feedbackData=void 0}_messageTemplate({message:e}){return e}updated(e){super.updated(e),this.feedbackData&&this.feedbackData[0]?(this.setAttribute("type",this.feedbackData[0].type),this.currentType=this.feedbackData[0].type):this.currentType!=="success"&&this.removeAttribute("type")}render(){return y`
      ${this.feedbackData&&this.feedbackData.map(({message:e,type:t,validator:s})=>y`
          <div class="validation-feedback__type">
            ${e&&t?this._localizeManager.msg(`lion-form-core:validation${xl(t)}`):j}
          </div>
          ${this._messageTemplate({message:e,type:t,validator:s})}
        `)}
    `}}class et{constructor(e){this.type="unparseable",this.viewValue=e}toString(){return JSON.stringify({type:this.type,viewValue:this.viewValue})}}const El=[Node.DOCUMENT_POSITION_PRECEDING,Node.DOCUMENT_POSITION_CONTAINS,Node.DOCUMENT_POSITION_CONTAINS|Node.DOCUMENT_POSITION_PRECEDING];function gn(i,{reverse:e}={}){const t=(o,n)=>{const r=o.compareDocumentPosition(n);return El.includes(r)?1:-1},s=i.filter(o=>o);return s.sort(t),e&&s.reverse(),s}const Cl=i=>class extends i{constructor(){super(),this.name="",this._parentFormGroup=void 0,this.allowCrossRootRegistration=!1}get name(){return this.__name||""}set name(e){const t=this.name;this.__name=e.toString(),this.requestUpdate("name",t)}static get properties(){return{name:{type:String,reflect:!0},allowCrossRootRegistration:{type:Boolean,attribute:"allow-cross-root-registration"}}}connectedCallback(){super.connectedCallback(),this.dispatchEvent(new CustomEvent("form-element-register",{detail:{element:this},bubbles:!0,composed:!!this.allowCrossRootRegistration}))}disconnectedCallback(){super.disconnectedCallback(),this.__unregisterFormElement()}__unregisterFormElement(){this._parentFormGroup&&this._parentFormGroup.removeFormElement(this)}},Bs=Q(Cl),kl=i=>class extends Bs(Bt(vt(i))){static get properties(){return{readOnly:{type:Boolean,attribute:"readonly",reflect:!0},label:String,labelSrOnly:{type:Boolean,attribute:"label-sr-only",reflect:!0},helpText:{type:String,attribute:"help-text"},modelValue:{attribute:!1},_ariaLabelledNodes:{attribute:!1},_ariaDescribedNodes:{attribute:!1},_repropagationRole:{attribute:!1},_isRepropagationEndpoint:{attribute:!1}}}get label(){return this.__label??(this._labelNode?.textContent||"")}set label(t){const s=this.label;this.__label=t,this.requestUpdate("label",s)}get helpText(){return this.__helpText??(this._helpTextNode?.textContent||"")}set helpText(t){const s=this.helpText;this.__helpText=t,this.requestUpdate("helpText",s)}get fieldName(){return this.__fieldName||this.label||this.name||""}set fieldName(t){this.__fieldName=t}get slots(){return{...super.slots,label:()=>{const t=document.createElement("label");return t.textContent=this.label,t},"help-text":()=>{const t=document.createElement("div");return t.textContent=this.helpText,t}}}get _inputNode(){return this.__getDirectSlotChild("input")}get _labelNode(){return this.__getDirectSlotChild("label")}get _helpTextNode(){return this.__getDirectSlotChild("help-text")}get _feedbackNode(){return this.__getDirectSlotChild("feedback")}static enabledWarnings=super.enabledWarnings?.filter(t=>t!=="change-in-update")||[];constructor(){super(),this.readOnly=!1,this.labelSrOnly=!1,this._inputId=Ut(this.localName),this._ariaLabelledNodes=[],this._ariaDescribedNodes=[],this._repropagationRole="child",this._isRepropagationEndpoint=!1,this.addEventListener("model-value-changed",this.__repropagateChildrenValues),this._onLabelClick=this._onLabelClick.bind(this)}connectedCallback(){super.connectedCallback(),this._enhanceLightDomClasses(),this._enhanceLightDomA11y(),this._triggerInitialModelValueChangedEvent(),this._labelNode&&this._labelNode.addEventListener("click",this._onLabelClick)}disconnectedCallback(){super.disconnectedCallback(),this._labelNode&&this._labelNode.removeEventListener("click",this._onLabelClick)}updated(t){super.updated(t),t.has("disabled")&&this._inputNode?.setAttribute("aria-disabled",`${!!this.disabled}`),t.has("_ariaLabelledNodes")&&this.__reflectAriaAttr("aria-labelledby",this._ariaLabelledNodes,this.__reorderAriaLabelledNodes),t.has("_ariaDescribedNodes")&&this.__reflectAriaAttr("aria-describedby",this._ariaDescribedNodes,this.__reorderAriaDescribedNodes),t.has("label")&&this.__label!==void 0&&this._labelNode&&(this._labelNode.textContent=this.label),t.has("helpText")&&this.__helpText!==void 0&&this._helpTextNode&&(this._helpTextNode.textContent=this.helpText),t.has("name")&&this.dispatchEvent(new CustomEvent("form-element-name-changed",{detail:{oldName:t.get("name"),newName:this.name},bubbles:!0}))}_triggerInitialModelValueChangedEvent(){this._dispatchInitialModelValueChangedEvent()}_enhanceLightDomClasses(){this._inputNode&&this._inputNode.classList.add("form-control")}_enhanceLightDomA11y(){const{_inputNode:t,_labelNode:s,_helpTextNode:o,_feedbackNode:n}=this;t&&(t.id=t.id||this._inputId),s&&(s.setAttribute("for",this._inputId),this.addToAriaLabelledBy(s,{idPrefix:"label"})),o&&this.addToAriaDescribedBy(o,{idPrefix:"help-text"}),n&&(this.addEventListener("focusin",()=>{n.setAttribute("aria-live","polite")}),this.addEventListener("focusout",()=>{n.setAttribute("aria-live","assertive")}),this.addToAriaDescribedBy(n,{idPrefix:"feedback"})),this._enhanceLightDomA11yForAdditionalSlots()}_enhanceLightDomA11yForAdditionalSlots(t=["prefix","suffix","before","after"]){t.forEach(s=>{const o=this.__getDirectSlotChild(s);o&&(o.hasAttribute("data-label")&&this.addToAriaLabelledBy(o,{idPrefix:s}),o.hasAttribute("data-description")&&this.addToAriaDescribedBy(o,{idPrefix:s}))})}__reflectAriaAttr(t,s,o){if(this._inputNode){if(o){const r=s.filter(b=>this.contains(b)),a=s.filter(b=>!this.contains(b)),c=r.map(b=>b.assignedSlot||b),u=[...gn(c)],h=[];u.forEach(b=>{r.forEach(p=>{b.name===p.slot&&h.push(p)})}),s=[...h,...a]}const n=s.map(r=>r.id).join(" ");this._inputNode.setAttribute(t,n)}}render(){return y`
        <div class="form-field__group-one">${this._groupOneTemplate()}</div>
        <div class="form-field__group-two">${this._groupTwoTemplate()}</div>
      `}_groupOneTemplate(){return y` ${this._labelTemplate()} ${this._helpTextTemplate()} `}_groupTwoTemplate(){return y` ${this._inputGroupTemplate()} ${this._feedbackTemplate()} `}_labelTemplate(){return y`
        <div class="form-field__label">
          <slot name="label"></slot>
        </div>
      `}_helpTextTemplate(){return y`
        <small class="form-field__help-text">
          <slot name="help-text"></slot>
        </small>
      `}_inputGroupTemplate(){return y`
        <div class="input-group">
          ${this._inputGroupBeforeTemplate()}
          <div class="input-group__container">
            ${this._inputGroupPrefixTemplate()} ${this._inputGroupInputTemplate()}
            ${this._inputGroupSuffixTemplate()}
          </div>
          ${this._inputGroupAfterTemplate()}
        </div>
      `}_inputGroupBeforeTemplate(){return y`
        <div class="input-group__before">
          <slot name="before"></slot>
        </div>
      `}_inputGroupPrefixTemplate(){return Array.from(this.children).find(t=>t.slot==="prefix")?y`
            <div class="input-group__prefix">
              <slot name="prefix"></slot>
            </div>
          `:j}_inputGroupInputTemplate(){return y`
        <div class="input-group__input">
          <slot name="input"></slot>
        </div>
      `}_inputGroupSuffixTemplate(){return Array.from(this.children).find(t=>t.slot==="suffix")?y`
            <div class="input-group__suffix">
              <slot name="suffix"></slot>
            </div>
          `:j}_inputGroupAfterTemplate(){return y`
        <div class="input-group__after">
          <slot name="after"></slot>
        </div>
      `}_feedbackTemplate(){return y`
        <div class="form-field__feedback">
          <slot name="feedback"></slot>
        </div>
      `}_isEmpty(t=this.modelValue){let s=t;if(this.modelValue instanceof et&&(s=this.modelValue.viewValue),typeof s=="object"&&s!==null&&!(s instanceof Date))return!Object.keys(s).length;const o=typeof s=="number"&&(s===0||Number.isNaN(s));return!s&&!o&&!(typeof s=="boolean"&&s===!1)}static get styles(){return[S`
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
        `]}_getAriaDescriptionElements(){return[this._helpTextNode,this._feedbackNode]}addToAriaLabelledBy(t,{idPrefix:s="",reorder:o=!0}={}){t.id=t.id||`${s}-${this._inputId}`,this._ariaLabelledNodes.includes(t)||(this._ariaLabelledNodes=[...this._ariaLabelledNodes,t],this.__reorderAriaLabelledNodes=!!o)}removeFromAriaLabelledBy(t){this._ariaLabelledNodes.includes(t)&&(this._ariaLabelledNodes.splice(this._ariaLabelledNodes.indexOf(t),1),this._ariaLabelledNodes=[...this._ariaLabelledNodes],this.__reorderAriaLabelledNodes=!1)}addToAriaDescribedBy(t,{idPrefix:s="",reorder:o=!0}={}){t.id=t.id||`${s}-${this._inputId}`,this._ariaDescribedNodes.includes(t)||(this._ariaDescribedNodes=[...this._ariaDescribedNodes,t],this.__reorderAriaDescribedNodes=!!o)}removeFromAriaDescribedBy(t){this._ariaDescribedNodes.includes(t)&&(this._ariaDescribedNodes.splice(this._ariaDescribedNodes.indexOf(t),1),this._ariaDescribedNodes=[...this._ariaDescribedNodes],this.__reorderAriaLabelledNodes=!1)}__getDirectSlotChild(t){return Array.from(this.children).find(s=>s.slot===t)}_dispatchInitialModelValueChangedEvent(){this._repropagationRole!=="child"&&(this.__repropagateChildrenInitialized=!0,this.dispatchEvent(new CustomEvent("model-value-changed",{bubbles:!0,detail:{formPath:[this],initialize:!0,isTriggeredByUser:!1}})))}_onBeforeRepropagateChildrenValues(t){}__repropagateChildrenValues(t){this._onBeforeRepropagateChildrenValues(t);const s=t.detail&&t.detail.element||t.target,o=this._isRepropagationEndpoint||this._repropagationRole==="choice-group";if(s===this)return;t.stopImmediatePropagation();const r=this._repropagationRole!=="child"&&!this.__repropagateChildrenInitialized,a=t.detail&&t.detail.initialize;if(r||a||!this._repropagationCondition(s))return;let c=[];o||(c=t.detail&&t.detail.formPath||[s]);const u=[...c,this];this.dispatchEvent(new CustomEvent("model-value-changed",{bubbles:!0,detail:{formPath:u,isTriggeredByUser:!!t.detail?.isTriggeredByUser}}))}_repropagationCondition(t){return!!t}_onLabelClick(){}},tt=Q(kl);class Di extends EventTarget{constructor(e,t){super(),this.__param=e,this.__config=t||{},this.type=t?.type||"error"}static _$isValidator$=!0;static validatorName="";static async=!1;execute(e,t,s){if(!this.constructor.validatorName)throw new Error(`A validator needs to have a name! Please set it via "static get validatorName() { return 'IsCat'; }"`);return!0}set param(e){this.__param=e,this.dispatchEvent(new Event("param-changed"))}get param(){return this.__param}set config(e){this.__config=e,this.dispatchEvent(new Event("config-changed"))}get config(){return this.__config}async _getMessage(e){const t=this.constructor,s={name:t.validatorName,type:this.type,params:this.param,config:this.config,...e};if(this.config.getMessage){if(typeof this.config.getMessage=="function")return this.config.getMessage(s);throw new Error(`You must provide a value for getMessage of type 'function', you provided a value of type: ${typeof this.config.getMessage}`)}return t.getMessage(s)}static async getMessage(e){return`Please configure an error message for "${this.name}" by overriding "static async getMessage()"`}onFormControlConnect(e){}onFormControlDisconnect(e){}abortExecution(){}}function bo(i=[],e=[]){return i.filter(t=>!e.includes(t)).concat(e.filter(t=>!i.includes(t)))}function Sl(i){return i instanceof et?i.viewValue:i}const Al=i=>class extends tt(yl(Bt(vt(qt(i))))){static get scopedElements(){return{...super.scopedElements,"lion-validation-feedback":bn}}static get properties(){return{validators:{attribute:!1},hasFeedbackFor:{attribute:!1},shouldShowFeedbackFor:{attribute:!1},showsFeedbackFor:{type:Array,attribute:"shows-feedback-for",reflect:!0,converter:{fromAttribute:e=>e.split(","),toAttribute:e=>e.join(",")}},validationStates:{attribute:!1},isPending:{type:Boolean,attribute:"is-pending",reflect:!0},defaultValidators:{attribute:!1},_visibleMessagesAmount:{attribute:!1},__childModelValueChanged:{attribute:!1}}}static get validationTypes(){return["error"]}get operationMode(){return"enter"}get slots(){return{...super.slots,feedback:()=>{const e=this.createScopedElement("lion-validation-feedback");return e.setAttribute("data-tag-name","lion-validation-feedback"),e}}}get _allValidators(){return[...this.validators,...this.defaultValidators]}constructor(){super(),this.hasFeedbackFor=[],this.showsFeedbackFor=[],this.shouldShowFeedbackFor=[],this.validationStates={},this.isPending=!1,this.validators=[],this.defaultValidators=[],this._visibleMessagesAmount=1,this.__syncValidationResult=[],this.__asyncValidationResult=[],this.__validationResult=[],this.__prevValidationResult=[],this.__prevShownValidationResult=[],this.__childModelValueChanged=!1,this._onValidatorUpdated=this._onValidatorUpdated.bind(this),this._updateFeedbackComponent=this._updateFeedbackComponent.bind(this)}connectedCallback(){super.connectedCallback(),vi().addEventListener("localeChanged",this._updateFeedbackComponent)}disconnectedCallback(){super.disconnectedCallback(),vi().removeEventListener("localeChanged",this._updateFeedbackComponent)}firstUpdated(e){super.firstUpdated(e),this.__isValidateInitialized=!0,this.validate(),this._repropagationRole!=="child"&&this.addEventListener("model-value-changed",()=>{this.__childModelValueChanged=!0})}updateSync(e,t){if(super.updateSync(e,t),e==="validators"?(this.__setupValidators(),this.validate({clearCurrentResult:!0})):e==="modelValue"&&this.validate({clearCurrentResult:!0}),["touched","dirty","prefilled","focused","submitted","hasFeedbackFor","filled"].includes(e)&&this._updateShouldShowFeedbackFor(),e==="showsFeedbackFor"){this._inputNode&&this._inputNode.setAttribute("aria-invalid",`${this._hasFeedbackVisibleFor("error")}`);const s=bo(this.showsFeedbackFor,t);s.length>0&&this.dispatchEvent(new Event("showsFeedbackForChanged",{bubbles:!0})),s.forEach(o=>{this.dispatchEvent(new Event(`showsFeedbackFor${vl(o)}Changed`,{bubbles:!0}))})}e==="shouldShowFeedbackFor"&&bo(this.shouldShowFeedbackFor,t).length>0&&this.dispatchEvent(new Event("shouldShowFeedbackForChanged",{bubbles:!0}))}async validate({clearCurrentResult:e=!1}={}){if(this.validateComplete=new Promise(t=>{this.__validateCompleteResolve=t}),this.disabled){this.__clearValidationResults(),this.__finishValidationPass(),this._updateFeedbackComponent();return}this.__isValidateInitialized&&(this.__prevValidationResult=this.__validationResult,e&&this.__clearValidationResults(),await this.__executeValidators())}#e(e){let t=e;for(;t;){if(t.constructor.validatorName==="Required")return!0;t=Object.getPrototypeOf(t)}return!1}async __executeValidators(){const e=Sl(this.modelValue),t=this.__isEmpty(e);if(this.__syncValidationResult=[],t){const a=!this._isFormOrFieldset,c=this._allValidators.find(u=>u.constructor?.validatorName==="Required");if(c&&(this.__syncValidationResult=[{validator:c,outcome:!0}]),a){this.__finishValidationPass({syncValidationResult:this.__syncValidationResult});return}}const s=[],o=[],n=[];for(const a of this._allValidators)a?.executeOnResults?s.push(a):this.#e(a)||(a.constructor.async?n.push(a):o.push(a));const r=!!n.length;this.__syncValidationResult=[...this.__syncValidationResult,...this.__executeSyncValidators(o,e)],this.__finishValidationPass({syncValidationResult:this.__syncValidationResult,metaValidators:s}),r?(this.isPending=!0,this.__asyncValidationResult=await this.__executeAsyncValidators(n,e),this.isPending=!1,this.__finishValidationPass({syncValidationResult:this.__syncValidationResult,asyncValidationResult:this.__asyncValidationResult,metaValidators:s}),this.__validateCompleteResolve?.(!0)):this.__validateCompleteResolve?.(!0)}__executeSyncValidators(e,t){return e.map(s=>({validator:s,outcome:s.execute(t,s.param,{node:this})})).filter(s=>!!s.outcome)}async __executeAsyncValidators(e,t){const s=e.map(n=>n.execute(t,n.param,{node:this})),o=await Promise.all(s);return o.map((n,r)=>({validator:e[r],outcome:o[r]})).filter(n=>!!n.outcome)}__executeMetaValidators(e,t){return t.length?this._isEmpty(this.modelValue)?(this.__prevShownValidationResult=[],[]):t.map(s=>({validator:s,outcome:s.executeOnResults({regularValidationResult:e.map(o=>o.validator),prevValidationResult:this.__prevValidationResult.map(o=>o.validator),prevShownValidationResult:this.__prevShownValidationResult.map(o=>o.validator)})})).filter(s=>!!s.outcome):[]}__finishValidationPass({syncValidationResult:e=[],asyncValidationResult:t=[],metaValidators:s=[]}={}){const o=[...e,...t],n=this.__executeMetaValidators(o,s);this.__validationResult=[...n,...o];const a=this.constructor.validationTypes.reduce((c,u)=>({...c,[u]:{}}),{});for(const{validator:c,outcome:u}of this.__validationResult){a[c.type]||(a[c.type]={});const h=c.constructor;a[c.type][h.validatorName]=u}this.validationStates=a,this.hasFeedbackFor=[...new Set(this.__validationResult.map(({validator:c})=>c.type))],this.dispatchEvent(new Event("validate-performed",{bubbles:!0}))}__clearValidationResults(){this.__syncValidationResult=[],this.__asyncValidationResult=[]}_onValidatorUpdated(e){(e.type==="param-changed"||e.type==="config-changed")&&this.validate()}__setupValidators(){const e=["param-changed","config-changed"];for(const t of this.__prevValidators||[]){for(const s of e)t.removeEventListener?.(s,this._onValidatorUpdated);t.onFormControlDisconnect(this)}for(const t of this._allValidators){if(t.constructor._$isValidator$===void 0){const a=`Validators array only accepts class instances of Validator. Type "${Array.isArray(t)?"array":typeof t}" found. This may be caused by having multiple installations of "@lion/ui/form-core.js".`;throw console.error(a,this),new Error(a)}const o=this.constructor,n=t.constructor;if(o.validationTypes.indexOf(t.type)===-1){const r=`This component does not support the validator type "${t.type}" used in "${n.validatorName}". You may change your validators type or add it to the components "static get validationTypes() {}".`;throw console.error(r,this),new Error(r)}for(const r of e)t.addEventListener?.(r,a=>{this._onValidatorUpdated(a,{validator:t})});t.onFormControlConnect(this)}this.__prevValidators=this._allValidators}__isEmpty(e){return typeof this._isEmpty=="function"?this._isEmpty(e):this.modelValue===null||typeof this.modelValue>"u"||this.modelValue===""}async __getFeedbackMessages(e){let t=await this.fieldName;return Promise.all(e.map(async({validator:s,outcome:o})=>(s.config.fieldName&&(t=await s.config.fieldName),{message:await s._getMessage({modelValue:this.modelValue,formControl:this,fieldName:t,outcome:o}),type:s.type,validator:s,visibilityDuration:s.config?.visibilityDuration||3e3})))}_updateFeedbackComponent(){window.clearTimeout(this.removeMessage);const{_feedbackNode:e}=this;e&&(this.__feedbackQueue||(this.__feedbackQueue=new gl),this.showsFeedbackFor.length>0?this.__feedbackQueue.add(async()=>{const t=this._prioritizeAndFilterFeedback({validationResult:this.__validationResult.map(o=>o.validator)});this.__prioritizedResult=t.map(o=>this.__validationResult.find(r=>o===r.validator)).filter(Boolean),this.__prioritizedResult.length>0&&(this.__prevShownValidationResult=this.__prioritizedResult);const s=await this.__getFeedbackMessages(this.__prioritizedResult);e.feedbackData=s||[],s?.[0]&&s[0].type==="success"&&s[0].visibilityDuration!==1/0&&(this.removeMessage=window.setTimeout(()=>{e.removeAttribute("type"),e.feedbackData=[]},s[0].visibilityDuration))}):this.__feedbackQueue.add(async()=>{e.feedbackData=[]}),this.feedbackComplete=this.__feedbackQueue.complete)}_showFeedbackConditionFor(e,t){return!0}get _feedbackConditionMeta(){return{modelValue:this.modelValue,el:this}}feedbackCondition(e,t=this._feedbackConditionMeta,s=this._showFeedbackConditionFor.bind(this)){return s(e,t)}_hasFeedbackVisibleFor(e){return this.hasFeedbackFor?.includes(e)&&this.shouldShowFeedbackFor?.includes(e)}updated(e){if(super.updated(e),e.has("shouldShowFeedbackFor")||e.has("hasFeedbackFor")){const t=this.constructor;this.showsFeedbackFor=t.validationTypes.map(s=>this._hasFeedbackVisibleFor(s)?s:void 0).filter(Boolean),this._updateFeedbackComponent()}if(e.has("__childModelValueChanged")&&this.__childModelValueChanged&&(this.validate({clearCurrentResult:!0}),this.__childModelValueChanged=!1),e.has("validationStates")){const t=e.get("validationStates");t&&Object.entries(this.validationStates).forEach(([s,o])=>{t[s]&&JSON.stringify(o)!==JSON.stringify(t[s])&&this.dispatchEvent(new CustomEvent(`${s}StateChanged`,{detail:o}))})}}_updateShouldShowFeedbackFor(){const t=this.constructor.validationTypes.map(s=>this.feedbackCondition(s,this._feedbackConditionMeta,this._showFeedbackConditionFor.bind(this))?s:void 0).filter(Boolean);JSON.stringify(this.shouldShowFeedbackFor)!==JSON.stringify(t)&&(this.shouldShowFeedbackFor=t)}_prioritizeAndFilterFeedback({validationResult:e}){const s=this.constructor.validationTypes;return e.filter(n=>this.feedbackCondition(n.type,this._feedbackConditionMeta,this._showFeedbackConditionFor.bind(this))).sort((n,r)=>s.indexOf(n.type)-s.indexOf(r.type)).slice(0,this._visibleMessagesAmount)}},Wt=Q(Al),Tl=i=>class extends Wt(tt(i)){static get properties(){return{formattedValue:{attribute:!1},serializedValue:{attribute:!1},formatOptions:{attribute:!1}}}#e={didFormatterOutputSyncToView:!1,didFormatterRun:!1};requestUpdate(t,s,o){super.requestUpdate(t,s,o),t==="modelValue"&&this.modelValue!==s&&this._onModelValueChanged({modelValue:this.modelValue},{modelValue:s}),t==="serializedValue"&&this.serializedValue!==s&&this._calculateValues({source:"serialized"}),t==="formattedValue"&&this.formattedValue!==s&&this._calculateValues({source:"formatted"})}get value(){return this._inputNode?.value||this.__value||""}set value(t){this._inputNode?(this._inputNode.value=t,this.__value=void 0):this.__value=t}preprocessor(t,s){}parser(t,s){return t}formatter(t,s){return t}serializer(t){return t!==void 0?t:""}deserializer(t){return t===void 0?"":t}_calculateValues({source:t}={source:null}){this.__preventRecursiveTrigger||(this.__preventRecursiveTrigger=!0,t!=="model"&&(t==="serialized"?this.modelValue=this.deserializer(this.serializedValue):t==="formatted"&&(this.modelValue=this._callParser())),t!=="formatted"&&(this.formattedValue=this._callFormatter()),t!=="serialized"&&(this.serializedValue=this.serializer(this.modelValue)),this._reflectBackFormattedValueToUser(),this.__preventRecursiveTrigger=!1,this.__prevViewValue=this.value)}_callParser(t=this.formattedValue){if(t==="")return"";if(typeof t!="string")return;const s=this.parser(t,{...this.formatOptions,mode:this.#t(),viewValueStates:this.#i()});return s!==void 0?s:new et(t)}_callFormatter(){return this.#e.didFormatterRun=!1,this._isHandlingUserInput&&this.hasFeedbackFor?.includes("error")&&this._inputNode?this.value:this.modelValue instanceof et?this.modelValue.viewValue:(this.#e.didFormatterRun=!0,this.formatter(this.modelValue,{...this.formatOptions,mode:this.#t(),viewValueStates:this.#i()}))}_onModelValueChanged(...t){this._calculateValues({source:"model"}),this._dispatchModelValueChangedEvent(...t)}_dispatchModelValueChangedEvent(...t){this.dispatchEvent(new CustomEvent("model-value-changed",{bubbles:!0,detail:{formPath:[this],isTriggeredByUser:!!this._isHandlingUserInput}}))}_syncValueUpwards(){this.__isHandlingComposition||this.__handlePreprocessor();const t=this.formattedValue;this.modelValue=this._callParser(this.value),t===this.formattedValue&&this.__prevViewValue!==this.value&&this._calculateValues()}__handlePreprocessor(){let t=this.value.length;this._inputNode&&"selectionStart"in this._inputNode&&this._inputNode?.type!=="range"&&(t=this._inputNode.selectionStart);const s=this.preprocessor(this.value,{...this.formatOptions,currentCaretIndex:t,prevViewValue:this.__prevViewValue});if(s!==void 0){if(typeof s=="string")this.value=s;else if(typeof s=="object"){const{viewValue:o,caretIndex:n}=s;this.value=o,n&&this._inputNode&&"selectionStart"in this._inputNode&&(this._inputNode.selectionStart=n,this._inputNode.selectionEnd=n)}}}_reflectBackFormattedValueToUser(){this._reflectBackOn()&&(this.value=typeof this.formattedValue<"u"?this.formattedValue:"",this.#e.didFormatterOutputSyncToView=!!this.formattedValue&&this.#e.didFormatterRun)}_reflectBackOn(){return!this._isHandlingUserInput}_proxyInputEvent(){this.dispatchEvent(new Event("user-input-changed",{bubbles:!0}))}_onUserInputChanged(){this._isHandlingUserInput=!0,this._syncValueUpwards(),this._isHandlingUserInput=!1}__onCompositionEvent({type:t}){t==="compositionstart"?this.__isHandlingComposition=!0:t==="compositionend"&&(this.__isHandlingComposition=!1,this._syncValueUpwards())}constructor(){super(),this.formatOn="change",this.formatOptions={mode:"auto"},this.formattedValue=void 0,this.serializedValue=void 0,this._isPasting=!1,this._isHandlingUserInput=!1,this.__prevViewValue="",this.__onCompositionEvent=this.__onCompositionEvent.bind(this),this.addEventListener("user-input-changed",this._onUserInputChanged),this.addEventListener("paste",this.__onPaste),this._reflectBackFormattedValueToUser=this._reflectBackFormattedValueToUser.bind(this),this._reflectBackFormattedValueDebounced=()=>{setTimeout(this._reflectBackFormattedValueToUser)}}__onPaste(){this._isPasting=!0,setTimeout(()=>{this._isPasting=!1})}connectedCallback(){super.connectedCallback(),typeof this.modelValue>"u"&&this._syncValueUpwards(),this.__prevViewValue=this.value,this._reflectBackFormattedValueToUser(),this._inputNode&&(this._inputNode.addEventListener(this.formatOn,this._reflectBackFormattedValueDebounced),this._inputNode.addEventListener("input",this._proxyInputEvent),this._inputNode.addEventListener("compositionstart",this.__onCompositionEvent),this._inputNode.addEventListener("compositionend",this.__onCompositionEvent))}disconnectedCallback(){super.disconnectedCallback(),this._inputNode&&(this._inputNode.removeEventListener("input",this._proxyInputEvent),this._inputNode.removeEventListener(this.formatOn,this._reflectBackFormattedValueDebounced),this._inputNode.removeEventListener("compositionstart",this.__onCompositionEvent),this._inputNode.removeEventListener("compositionend",this.__onCompositionEvent))}#t(){return this._isPasting?"pasted":this._isHandlingUserInput&&this.__prevViewValue?"user-edited":"auto"}#i(){const t=[];return this.#e.didFormatterOutputSyncToView&&t.push("formatted"),t}},Us=Q(Tl),Nl=i=>class extends tt(i){static get properties(){return{touched:{type:Boolean,reflect:!0},dirty:{type:Boolean,reflect:!0},filled:{type:Boolean,reflect:!0},prefilled:{attribute:!1},submitted:{attribute:!1}}}requestUpdate(t,s,o){super.requestUpdate(t,s,o),t==="touched"&&this.touched!==s&&this._onTouchedChanged(),t==="modelValue"&&(this.filled=!this._isEmpty()),t==="dirty"&&this.dirty!==s&&this._onDirtyChanged()}constructor(){super(),this.touched=!1,this.dirty=!1,this.prefilled=!1,this.filled=!1,this._leaveEvent="blur",this._valueChangedEvent="model-value-changed",this._iStateOnLeave=this._iStateOnLeave.bind(this),this._iStateOnValueChange=this._iStateOnValueChange.bind(this)}connectedCallback(){super.connectedCallback(),this.addEventListener(this._leaveEvent,this._iStateOnLeave),this.addEventListener(this._valueChangedEvent,this._iStateOnValueChange),this.initInteractionState()}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener(this._leaveEvent,this._iStateOnLeave),this.removeEventListener(this._valueChangedEvent,this._iStateOnValueChange)}initInteractionState(){this.dirty=!1,this.prefilled=!this._isEmpty()}_iStateOnLeave(){this.touched=!0,this.prefilled=!this._isEmpty()}_iStateOnValueChange(){this.dirty=!0}resetInteractionState(){this.touched=!1,this.submitted=!1,this.dirty=!1,this.prefilled=!this._isEmpty()}_onTouchedChanged(){this.dispatchEvent(new Event("touched-changed",{bubbles:!0,composed:!0}))}_onDirtyChanged(){this.dispatchEvent(new Event("dirty-changed",{bubbles:!0,composed:!0}))}_showFeedbackConditionFor(t,s){return s.touched&&s.dirty||s.prefilled||s.submitted}get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,submitted:this.submitted,touched:this.touched,dirty:this.dirty,filled:this.filled,prefilled:this.prefilled}}},Hs=Q(Nl);class jt extends tt(Hs(Ps(Us(Wt(vt(P)))))){firstUpdated(e){super.firstUpdated(e),this._initialModelValue=this.modelValue}connectedCallback(){super.connectedCallback(),this._onChange=this._onChange.bind(this),this._inputNode.addEventListener("change",this._onChange),this.classList.add("form-field")}disconnectedCallback(){super.disconnectedCallback(),this._inputNode?.removeEventListener("change",this._onChange)}resetInteractionState(){super.resetInteractionState(),this.submitted=!1}reset(){this.modelValue=this._initialModelValue,this.resetInteractionState()}clear(){this.modelValue=""}_onChange(e){this.dispatchEvent(new Event("user-input-changed",{bubbles:!0}))}get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,focused:this.focused}}get _focusableNode(){return this._inputNode}}class ys extends Array{_keys(){return Object.keys(this).filter(e=>Number.isNaN(Number(e)))}}const Fl=i=>class extends Bs(i){static get properties(){return{_isFormOrFieldset:{type:Boolean}}}constructor(){super(),this.formElements=new ys,this._isFormOrFieldset=!1,this._onRequestToAddFormElement=this._onRequestToAddFormElement.bind(this),this._onRequestToChangeFormElementName=this._onRequestToChangeFormElementName.bind(this),this.addEventListener("form-element-register",this._onRequestToAddFormElement),this.addEventListener("form-element-name-changed",this._onRequestToChangeFormElementName),this.initComplete=new Promise((e,t)=>{this.__resolveInitComplete=e,this.__rejectInitComplete=t}),this.registrationComplete=new Promise((e,t)=>{this.__resolveRegistrationComplete=e,this.__rejectRegistrationComplete=t}),this.registrationComplete.done=!1,this.registrationComplete.then(()=>{this.registrationComplete.done=!0,this.__resolveInitComplete(void 0)},()=>{throw this.registrationComplete.done=!0,this.__rejectInitComplete(void 0),new Error("Registration could not finish. Please use await el.registrationComplete;")})}connectedCallback(){super.connectedCallback(),this._completeRegistration()}_completeRegistration(){Promise.resolve().then(()=>this.__resolveRegistrationComplete(void 0))}disconnectedCallback(){super.disconnectedCallback(),this.registrationComplete.done===!1&&Promise.resolve().then(()=>{Promise.resolve().then(()=>{this.__rejectRegistrationComplete()})})}isRegisteredFormElement(e){return this.formElements.some(t=>t===e)}addFormElement(e,t){if(e._parentFormGroup=this,t>=0?this.formElements.splice(t,0,e):this.formElements.push(e),this._isFormOrFieldset){const{name:s}=e;if(s===this.name)throw console.info("Error Node:",e),new TypeError(`You can not have the same name "${s}" as your parent`);if(s.substr(-2)==="[]")Array.isArray(this.formElements[s])||(this.formElements[s]=new ys),t>0?this.formElements[s].splice(t,0,e):this.formElements[s].push(e);else if(!this.formElements[s])this.formElements[s]=e;else throw console.info("Error Node:",e),new TypeError(`Name "${s}" is already registered - if you want an array add [] to the end`)}}removeFormElement(e){const t=this.formElements.indexOf(e);if(t>-1&&this.formElements.splice(t,1),this._isFormOrFieldset){const{name:s}=e;if(s.substr(-2)==="[]"&&this.formElements[s]){const o=this.formElements[s].indexOf(e);o>-1&&this.formElements[s].splice(o,1)}else this.formElements[s]&&delete this.formElements[s]}}_onRequestToAddFormElement(e){const t=e.detail.element;if(t===this||this.isRegisteredFormElement(t))return;e.stopPropagation();let s=-1;if(this.formElements&&Array.isArray(this.formElements)){for(const[o,n]of this.formElements.entries())if(!(n.compareDocumentPosition(t)&Node.DOCUMENT_POSITION_FOLLOWING)){s=o;break}}this.addFormElement(t,s)}_onRequestToChangeFormElementName(e){const t=this.formElements[e.detail.oldName];t&&(this.formElements[e.detail.newName]=t,delete this.formElements[e.detail.oldName])}_onRequestToRemoveFormElement(e){const t=e.detail.element;t!==this&&this.isRegisteredFormElement(t)&&(e.stopPropagation(),this.removeFormElement(t))}},qs=Q(Fl),Ll=i=>class extends i{constructor(){super(),this.registrationTarget=void 0,this.__redispatchEventForFormRegistrarPortalMixin=this.__redispatchEventForFormRegistrarPortalMixin.bind(this),this.addEventListener("form-element-register",this.__redispatchEventForFormRegistrarPortalMixin)}__redispatchEventForFormRegistrarPortalMixin(e){if(e.stopPropagation(),!this.registrationTarget)throw new Error("A FormRegistrarPortal element requires a .registrationTarget");this.registrationTarget.dispatchEvent(new CustomEvent("form-element-register",{detail:{element:e.detail.element},bubbles:!0}))}},Ol=Q(Ll),Il=i=>class extends Us(Ps(tt(i))){static get properties(){return{autocomplete:{type:String,reflect:!0}}}constructor(){super(),this.autocomplete=void 0}get _inputNode(){return super._inputNode}get selectionStart(){const t=this._inputNode;return t&&t.selectionStart?t.selectionStart:0}set selectionStart(t){const s=this._inputNode;s&&s.selectionStart&&(s.selectionStart=t)}get selectionEnd(){const t=this._inputNode;return t&&t.selectionEnd?t.selectionEnd:0}set selectionEnd(t){const s=this._inputNode;s&&s.selectionEnd&&(s.selectionEnd=t)}get value(){return this._inputNode&&this._inputNode.value||this.__value||""}set value(t){this._inputNode?(this._inputNode.value!==t&&this._setValueAndPreserveCaret(t),this.__value=void 0):this.__value=t}_setValueAndPreserveCaret(t){if(this.focused)try{if(!(this._inputNode instanceof HTMLSelectElement)){const s=this._inputNode.selectionStart;this._inputNode.value=t,this._inputNode.selectionStart=s,this._inputNode.selectionEnd=s}}catch{this._inputNode.value=t}else this._inputNode.value=t}_reflectBackFormattedValueToUser(){if(super._reflectBackFormattedValueToUser(),this._reflectBackOn()&&this.focused)try{this._inputNode.selectionStart=this._inputNode.value.length}catch{}}get _focusableNode(){return this._inputNode}},vn=Q(Il),Dl=i=>class extends qs(Wt(Hs(i))){static get properties(){return{multipleChoice:{type:Boolean,attribute:"multiple-choice"}}}get modelValue(){const t=this._getCheckedElements();return this.multipleChoice?t.map(s=>s.choiceValue):t[0]?t[0].choiceValue:""}set modelValue(t){const s=(o,n)=>typeof o.choiceValue=="object"?JSON.stringify(o.choiceValue)===JSON.stringify(t):o.choiceValue===n;this.__isInitialModelValue?this.registrationComplete.then(()=>{this.__isInitialModelValue=!1,this._setCheckedElements(t,s),this.requestUpdate("modelValue",this._oldModelValue)}):(this._setCheckedElements(t,s),this.requestUpdate("modelValue",this._oldModelValue)),this._oldModelValue=this.modelValue}get serializedValue(){const t=this._getCheckedElements();return this.multipleChoice?t.map(s=>s.serializedValue.value):t[0]?t[0].serializedValue.value:""}set serializedValue(t){const s=(o,n)=>o.serializedValue.value===n;this.__isInitialSerializedValue?this.registrationComplete.then(()=>{this.__isInitialSerializedValue=!1,this._setCheckedElements(t,s),this.requestUpdate("serializedValue")}):(this._setCheckedElements(t,s),this.requestUpdate("serializedValue"))}get formattedValue(){const t=this._getCheckedElements();return this.multipleChoice?t.map(s=>s.formattedValue):t[0]?t[0].formattedValue:""}set formattedValue(t){const s=(o,n)=>o.formattedValue===n;this.__isInitialFormattedValue?this.registrationComplete.then(()=>{this.__isInitialFormattedValue=!1,this._setCheckedElements(t,s)}):this._setCheckedElements(t,s)}get operationMode(){return this._repropagationRole==="choice-group"?"select":"enter"}constructor(){super(),this.multipleChoice=!1,this._repropagationRole="choice-group",this.__isInitialModelValue=!0,this.__isInitialSerializedValue=!0,this.__isInitialFormattedValue=!0}connectedCallback(){super.connectedCallback(),this.registrationComplete.then(()=>{this.__isInitialModelValue=!1,this.__isInitialSerializedValue=!1,this.__isInitialFormattedValue=!1})}_completeRegistration(){Promise.resolve().then(()=>super._completeRegistration())}updated(t){super.updated(t),t.has("name")&&this.name!==t.get("name")&&this.formElements.forEach(s=>{s.name=this.name})}addFormElement(t,s){this._throwWhenInvalidChildModelValue(t),t.name=this.name,super.addFormElement(t,s)}clear(){this.multipleChoice?this.modelValue=[]:this.modelValue=""}_triggerInitialModelValueChangedEvent(){this.registrationComplete.then(()=>{this._dispatchInitialModelValueChangedEvent()})}_getFromAllFormElementsFilter(t,s){return!0}_getFromAllFormElements(t,s){const o=s||this._getFromAllFormElementsFilter;return t==="modelValue"||t==="serializedValue"||t==="formattedValue"?this[t]:this.formElements.filter(n=>o(n,t)).map(n=>n.property)}_throwWhenInvalidChildModelValue(t){if(typeof t.modelValue.checked!="boolean"||!Object.prototype.hasOwnProperty.call(t.modelValue,"value"))throw new Error(`The ${this.tagName.toLowerCase()} name="${this.name}" does not allow to register ${t.tagName.toLowerCase()} with .modelValue="${t.modelValue}" - The modelValue should represent an Object { value: "foo", checked: false }`)}_isEmpty(){return this.multipleChoice?this.modelValue.length===0:typeof this.modelValue=="string"&&this.modelValue===""||this.modelValue===void 0||this.modelValue===null}_checkSingleChoiceElements(t){const{target:s}=t;if(s.checked===!1)return;const o=s.name;this.formElements.filter(n=>n.name===o).forEach(n=>{n!==s&&(n.checked=!1)})}_getCheckedElements(){return this.formElements.filter(t=>t.checked&&!t.disabled)}_setCheckedElements(t,s){if(t==null){this.formElements.forEach(o=>o.checked=!1);return}for(let o=0;o<this.formElements.length;o+=1)if(this.multipleChoice){let n=t.includes(this.formElements[o].modelValue.value);typeof this.formElements[o].modelValue.value=="object"&&(n=t.map(r=>JSON.stringify(r)).includes(JSON.stringify(this.formElements[o].modelValue.value))),this.formElements[o].checked=n}else s(this.formElements[o],t)?this.formElements[o].checked=!0:this.formElements[o].checked=!1}__setChoiceGroupTouched(){const t=this.modelValue;t!=null&&t!==this.__previousCheckedValue&&(this.touched=!0,this.__previousCheckedValue=t)}_onBeforeRepropagateChildrenValues(t){const s=t.detail&&t.detail.element||t.target;this.multipleChoice||!s.checked||(this.formElements.forEach(o=>{s.choiceValue!==o.choiceValue&&(o.checked=!1)}),this.__setChoiceGroupTouched(),this.requestUpdate("modelValue",this._oldModelValue),this._oldModelValue=this.modelValue)}_repropagationCondition(t){return!(this._repropagationRole==="choice-group"&&!this.multipleChoice&&!t.checked)}},Mi=Q(Dl),Ml=(i,e={})=>i.value!==e.value||i.checked!==e.checked,$l=i=>class extends Us(i){static get properties(){return{checked:{type:Boolean,reflect:!0},disabled:{type:Boolean,reflect:!0},modelValue:{type:Object,hasChanged:Ml},choiceValue:{type:Object}}}get choiceValue(){return this.modelValue.value}set choiceValue(t){this.requestUpdate("choiceValue",this.choiceValue),this.modelValue.value!==t&&(this.modelValue={value:t,checked:this.modelValue.checked})}requestUpdate(t,s,o){super.requestUpdate(t,s,o),t==="modelValue"?this.modelValue.checked!==this.checked&&this.__syncModelCheckedToChecked(this.modelValue.checked):t==="checked"&&this.modelValue.checked!==this.checked&&this.__syncCheckedToModel(this.checked)}firstUpdated(t){super.firstUpdated(t),t.has("checked")&&this.__syncCheckedToInputElement()}updated(t){super.updated(t),t.has("modelValue")&&this.__syncCheckedToInputElement(),t.has("name")&&this._parentFormGroup&&this._parentFormGroup.name!==this.name&&this._syncNameToParentFormGroup()}constructor(){super(),this.modelValue={value:"",checked:!1},this.disabled=!1,this._preventDuplicateLabelClick=this._preventDuplicateLabelClick.bind(this),this._toggleChecked=this._toggleChecked.bind(this)}static get styles(){return[...super.styles||[],S`
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
        `]}render(){return y`
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
      `}_choiceGraphicTemplate(){return j}_afterTemplate(){return j}connectedCallback(){super.connectedCallback(),this._labelNode&&this._labelNode.addEventListener("click",this._preventDuplicateLabelClick),this.addEventListener("user-input-changed",this._toggleChecked)}disconnectedCallback(){super.disconnectedCallback(),this._labelNode&&this._labelNode.removeEventListener("click",this._preventDuplicateLabelClick),this.removeEventListener("user-input-changed",this._toggleChecked)}_preventDuplicateLabelClick(t){const s=o=>{o.stopImmediatePropagation(),this._inputNode.removeEventListener("click",s)};this._inputNode.addEventListener("click",s)}_toggleChecked(t){this.disabled||(this._isHandlingUserInput=!0,this.checked=!this.checked,this._isHandlingUserInput=!1)}_syncNameToParentFormGroup(){this._parentFormGroup.tagName.includes(this.tagName)&&(this.name=this._parentFormGroup?.name||"")}__syncModelCheckedToChecked(t){this.checked=t}__syncCheckedToModel(t){this.modelValue={value:this.choiceValue,checked:t}}__syncCheckedToInputElement(){this._inputNode&&(this._inputNode.checked=this.checked)}_proxyInputEvent(){}_onModelValueChanged({modelValue:t},s){let o;s&&s.modelValue&&(o=s.modelValue),this.constructor.elementProperties.get("modelValue").hasChanged(t,o)&&super._onModelValueChanged({modelValue:t})}parser(){return this.modelValue}formatter(t){return t&&t.value!==void 0?t.value:t}clear(){this.checked=!1}_isEmpty(){return!this.checked}_syncValueUpwards(){}},$i=Q($l);class Vl extends Di{static get validatorName(){return"FormElementsHaveNoError"}execute(e,t,s){return s?.node._anyFormElementHasFeedbackFor("error")}static async getMessage(){return""}}const Rl=i=>class extends qs(tt(Wt(Bt(vt(i))))){static get properties(){return{submitted:{type:Boolean,reflect:!0},focused:{type:Boolean,reflect:!0},dirty:{type:Boolean,reflect:!0},touched:{type:Boolean,reflect:!0},prefilled:{type:Boolean,reflect:!0}}}get _inputNode(){return this}get modelValue(){return this._getFromAllFormElements("modelValue")}set modelValue(t){this.__isInitialModelValue?(this.__isInitialModelValue=!1,this.registrationComplete.then(()=>{this._setValueMapForAllFormElements("modelValue",t)})):this._setValueMapForAllFormElements("modelValue",t)}get serializedValue(){return this._getFromAllFormElements("serializedValue")}set serializedValue(t){this.__isInitialSerializedValue?(this.__isInitialSerializedValue=!1,this.registrationComplete.then(()=>{this._setValueMapForAllFormElements("serializedValue",t)})):this._setValueMapForAllFormElements("serializedValue",t)}get formattedValue(){return this._getFromAllFormElements("formattedValue")}set formattedValue(t){this._setValueMapForAllFormElements("formattedValue",t)}get prefilled(){return this._everyFormElementHas("prefilled")}constructor(){super(),this.value="",this.disabled=!1,this.submitted=!1,this.dirty=!1,this.touched=!1,this.focused=!1,this.__addedSubValidators=!1,this.__isInitialModelValue=!0,this.__isInitialSerializedValue=!0,this._checkForOutsideClick=this._checkForOutsideClick.bind(this),this.addEventListener("focusin",this._syncFocused),this.addEventListener("focusout",this._onFocusOut),this.addEventListener("dirty-changed",this._syncDirty),this.addEventListener("validate-performed",this.__onChildValidatePerformed),this.defaultValidators=[new Vl],this.__descriptionElementsInParentChain=new Set,this.__pendingValues={modelValue:{},serializedValue:{}}}connectedCallback(){super.connectedCallback(),this.setAttribute("role","group"),this.initComplete.then(()=>{this.__isInitialModelValue=!1,this.__isInitialSerializedValue=!1,this.__initInteractionStates()})}disconnectedCallback(){super.disconnectedCallback(),this.__hasActiveOutsideClickHandling&&(document.removeEventListener("click",this._checkForOutsideClick),this.__hasActiveOutsideClickHandling=!1),this.__descriptionElementsInParentChain.clear()}__initInteractionStates(){this.formElements.forEach(t=>{typeof t.initInteractionState=="function"&&t.initInteractionState()})}_triggerInitialModelValueChangedEvent(){this.registrationComplete.then(()=>{this._dispatchInitialModelValueChangedEvent()})}updated(t){super.updated(t),t.has("disabled")&&(this.disabled?this.__requestChildrenToBeDisabled():this.__retractRequestChildrenToBeDisabled()),t.has("focused")&&this.focused===!0&&this.__setupOutsideClickHandling()}__setupOutsideClickHandling(){this.__hasActiveOutsideClickHandling||(document.addEventListener("click",this._checkForOutsideClick),this.__hasActiveOutsideClickHandling=!0)}_checkForOutsideClick(t){!this.contains(t.target)&&(this.touched=!0)}__requestChildrenToBeDisabled(){this.formElements.forEach(t=>{t.makeRequestToBeDisabled&&t.makeRequestToBeDisabled()})}__retractRequestChildrenToBeDisabled(){this.formElements.forEach(t=>{t.retractRequestToBeDisabled&&t.retractRequestToBeDisabled()})}_inputGroupTemplate(){return y`
        <div class="input-group">
          <slot></slot>
        </div>
      `}submitGroup(){this.submitted=!0,this.formElements.forEach(t=>{typeof t.submitGroup=="function"?t.submitGroup():t.submitted=!0})}resetGroup(){this.formElements.forEach(t=>{typeof t.resetGroup=="function"?t.resetGroup():typeof t.reset=="function"&&t.reset()}),this.resetInteractionState()}clearGroup(){this.formElements.forEach(t=>{typeof t.clearGroup=="function"?t.clearGroup():typeof t.clear=="function"&&t.clear()}),this.resetInteractionState()}resetInteractionState(){this.submitted=!1,this.touched=!1,this.dirty=!1,this.formElements.forEach(t=>{typeof t.resetInteractionState=="function"&&t.resetInteractionState()})}_getFromAllFormElementsFilter(t,s){return!t.disabled}_getFromAllFormElements(t,s){const o={},n=s||this._getFromAllFormElementsFilter;return this.formElements._keys().forEach(r=>{const a=this.formElements[r];a instanceof ys?o[r]=a.filter(c=>n(c,t)).map(c=>c[t]):n(a,t)&&(typeof a._getFromAllFormElements=="function"?o[r]=a._getFromAllFormElements(t):o[r]=a[t])}),o}_setValueForAllFormElements(t,s){this.formElements.forEach(o=>{o[t]=s})}_setValueMapForAllFormElements(t,s){s&&typeof s=="object"&&Object.keys(s).forEach(o=>{Array.isArray(this.formElements[o])&&this.formElements[o].forEach((n,r)=>{n[t]=s[o][r]}),this.formElements[o]?this.formElements[o][t]=s[o]:this.__pendingValues[t][o]=s[o]})}_anyFormElementHas(t){return Object.keys(this.formElements).some(s=>Array.isArray(this.formElements[s])?this.formElements[s].some(o=>!!o[t]):!!this.formElements[s][t])}_anyFormElementHasFeedbackFor(t){return Object.keys(this.formElements).some(s=>Array.isArray(this.formElements[s])?this.formElements[s].some(o=>!!(o.hasFeedbackFor&&o.hasFeedbackFor.includes(t))):!!(this.formElements[s].hasFeedbackFor&&this.formElements[s].hasFeedbackFor.includes(t)))}_everyFormElementHas(t){return Object.keys(this.formElements).every(s=>Array.isArray(this.formElements[s])?this.formElements[s].every(o=>!!o[t]):!!this.formElements[s][t])}__onChildValidatePerformed(t){t&&this.isRegisteredFormElement(t.target)&&this.validate()}_syncFocused(){this.focused=this._anyFormElementHas("focused")}_onFocusOut(t){const s=this.formElements[this.formElements.length-1];t.target===s&&(this.touched=!0),this.focused=!1}_syncDirty(){this.dirty=this._anyFormElementHas("dirty")}__storeAllDescriptionElementsInParentChain(){let s=this;for(;s;){const o=s._getAriaDescriptionElements();gn(o,{reverse:!0}).forEach(r=>{r.getAttribute("slot")==="feedback"&&this.__descriptionElementsInParentChain.add(r)}),s=s._parentFormGroup}}__linkParentMessages(t){this.__descriptionElementsInParentChain.forEach(s=>{typeof t.addToAriaDescribedBy=="function"&&t.addToAriaDescribedBy(s,{reorder:!1})})}__unlinkParentMessages(t){this.__descriptionElementsInParentChain.forEach(s=>{typeof t.removeFromAriaDescribedBy=="function"&&t.removeFromAriaDescribedBy(s)})}addFormElement(t,s){if(super.addFormElement(t,s),this.disabled&&t.makeRequestToBeDisabled(),this.__descriptionElementsInParentChain.size||this.__storeAllDescriptionElementsInParentChain(),this.__linkParentMessages(t),this.validate({clearCurrentResult:!0}),!t.modelValue){const o=this.__pendingValues;o.modelValue&&o.modelValue[t.name]?t.modelValue=o.modelValue[t.name]:o.serializedValue&&o.serializedValue[t.name]&&(t.serializedValue=o.serializedValue[t.name])}}get _initialModelValue(){return this._getFromAllFormElements("_initialModelValue")}removeFormElement(t){super.removeFormElement(t),this.validate({clearCurrentResult:!0}),typeof t.removeFromAriaLabelledBy=="function"&&this._labelNode&&t.removeFromAriaLabelledBy(this._labelNode,{reorder:!1}),this.__unlinkParentMessages(t)}_isEmpty(){return this.formElements.every(t=>t._isEmpty?.())}},_n=Q(Rl);class Vi extends vn(jt){static get properties(){return{readOnly:{type:Boolean,attribute:"readonly",reflect:!0},type:{type:String,reflect:!0},placeholder:{type:String,reflect:!0}}}get slots(){return{...super.slots,input:()=>{const e=document.createElement("input"),t=this.getAttribute("value");return t&&e.setAttribute("value",t),e}}}get _inputNode(){return super._inputNode}constructor(){super(),this.readOnly=!1,this.type="text",this.placeholder=""}requestUpdate(e,t,s){super.requestUpdate(e,t,s),e==="readOnly"&&this.__delegateReadOnly()}firstUpdated(e){super.firstUpdated(e),this.__delegateReadOnly()}updated(e){super.updated(e),e.has("type")&&(this._inputNode.type=this.type),e.has("placeholder")&&(this._inputNode.placeholder=this.placeholder),e.has("disabled")&&(this._inputNode.disabled=this.disabled,this.validate()),e.has("name")&&(this._inputNode.name=this.name),e.has("autocomplete")&&(this._inputNode.autocomplete=this.autocomplete)}__delegateReadOnly(){this._inputNode&&(this._inputNode.readOnly=this.readOnly)}}var ws=class extends Vi{static get styles(){return[...super.styles,Ht,Ga]}connectedCallback(){if(super.connectedCallback(),this._inputNode&&this.size){let e=parseInt(this.size,10);e>0&&(this._inputNode.size=e)}}};C([m({type:Number,reflect:!0})],ws.prototype,"size",void 0),customElements.get("craft-input")||customElements.define("craft-input",ws);const pe=i=>i??j;class ci extends Di{static validatorName="IsAcceptedFile";static checkFileSize(e,t){return e<=t}static getExtension(e){return e?.slice(e.lastIndexOf("."))}static isExtensionAllowed(e,t){return t?.find(s=>s.toUpperCase()===e.toUpperCase())}static isFileTypeAllowed(e,t){return t?.find(s=>s.toUpperCase()===e.toUpperCase())}execute(e,t=this.param){let s,o;const n=this.constructor,{allowedFileTypes:r,allowedFileExtensions:a,maxFileSize:c}=t;return r?.length?(s=e.some(h=>!n.isFileTypeAllowed(h.type,r)),s):a?.length?(o=e.some(h=>!n.isExtensionAllowed(n.getExtension(h.name),a)),o):e.findIndex(h=>!n.checkFileSize(h.size,c))>-1}static async getMessage(){return""}}class zl extends Di{static validatorName="DuplicateFileNames";constructor(e,t){super(e,t),this.type="info"}execute(e,t=this.param){return t.show}static async getMessage(){return vi().msg("lion-input-file:uploadTextDuplicateFileName")}}const Pl=524288e3,Yi={type:"FILE_TYPE",size:"FILE_SIZE"},wt={fail:"FAIL",pass:"SUCCESS"};class Bl{constructor(e,t){this.failedProp=[],this.systemFile=e,this._acceptCriteria=t,this.uploadFileStatus(),this.failedProp.length===0&&this.createDownloadUrl(e)}_getFileNameExtension(e){return e.slice(e.lastIndexOf("."))}uploadFileStatus(){if(this._acceptCriteria.allowedFileExtensions.length){const e=this._getFileNameExtension(this.systemFile.name);ci.isExtensionAllowed(e,this._acceptCriteria.allowedFileExtensions)||(this.status=wt.fail,this.failedProp.push(Yi.type))}else if(this._acceptCriteria.allowedFileTypes.length){const e=this.systemFile.type;ci.isFileTypeAllowed(e,this._acceptCriteria.allowedFileTypes)||(this.status=wt.fail,this.failedProp.push(Yi.type))}ci.checkFileSize(this.systemFile.size,this._acceptCriteria.maxFileSize)?this.status!==wt.fail&&(this.status=wt.pass):(this.status=wt.fail,this.failedProp.push(Yi.size))}createDownloadUrl(e){this.downloadUrl=window.URL.createObjectURL(e)}}const go=(i,e,t)=>{const s=new Map;for(let o=e;o<=t;o++)s.set(i[o],o);return s},Ul=Ci(class extends ki{constructor(i){if(super(i),i.type!==Ei.CHILD)throw Error("repeat() can only be used in text expressions")}dt(i,e,t){let s;t===void 0?t=e:e!==void 0&&(s=e);const o=[],n=[];let r=0;for(const a of i)o[r]=s?s(a,r):r,n[r]=t(a,r),r++;return{values:n,keys:o}}render(i,e,t){return this.dt(i,e,t).values}update(i,[e,t,s]){const o=za(i),{values:n,keys:r}=this.dt(e,t,s);if(!Array.isArray(o))return this.ut=r,n;const a=this.ut??=[],c=[];let u,h,b=0,p=o.length-1,f=0,_=n.length-1;for(;b<=p&&f<=_;)if(o[b]===null)b++;else if(o[p]===null)p--;else if(a[b]===r[f])c[f]=je(o[b],n[f]),b++,f++;else if(a[p]===r[_])c[_]=je(o[p],n[_]),p--,_--;else if(a[b]===r[_])c[_]=je(o[b],n[_]),yt(i,c[_+1],o[b]),b++,_--;else if(a[p]===r[f])c[f]=je(o[p],n[f]),yt(i,o[b],o[p]),p--,f++;else if(u===void 0&&(u=go(r,f,_),h=go(a,b,p)),u.has(a[b]))if(u.has(a[p])){const v=h.get(r[f]),w=v!==void 0?o[v]:null;if(w===null){const x=yt(i,o[b]);je(x,n[f]),c[f]=x}else c[f]=je(w,n[f]),yt(i,o[b],w),o[v]=null;f++}else Hi(o[p]),p--;else Hi(o[b]),b++;for(;f<=_;){const v=yt(i,c[_+1]);je(v,n[f]),c[f++]=v}for(;b<=p;){const v=o[b++];v!==null&&Hi(v)}return this.ut=r,Ra(i,c),Dt}}),yn=i=>{switch(i){case"bg-BG":return E(()=>import("./bg-BG.js"),__vite__mapDeps([34,35]),import.meta.url);case"bg":return E(()=>import("./bg2.js"),[],import.meta.url);case"cs-CZ":return E(()=>import("./cs-CZ.js"),__vite__mapDeps([36,37]),import.meta.url);case"cs":return E(()=>import("./cs2.js"),[],import.meta.url);case"de-DE":return E(()=>import("./de-DE.js"),__vite__mapDeps([38,39]),import.meta.url);case"de":return E(()=>import("./de2.js"),[],import.meta.url);case"en-AU":return E(()=>import("./en-AU.js"),__vite__mapDeps([40,41]),import.meta.url);case"en-GB":return E(()=>import("./en-GB.js"),__vite__mapDeps([42,41]),import.meta.url);case"en-US":return E(()=>import("./en-US.js"),__vite__mapDeps([43,41]),import.meta.url);case"en-PH":case"en":return E(()=>import("./en2.js"),[],import.meta.url);case"es-ES":return E(()=>import("./es-ES.js"),__vite__mapDeps([44,45]),import.meta.url);case"es":return E(()=>import("./es2.js"),[],import.meta.url);case"fr-FR":return E(()=>import("./fr-FR.js"),__vite__mapDeps([46,47]),import.meta.url);case"fr-BE":return E(()=>import("./fr-BE.js"),__vite__mapDeps([48,47]),import.meta.url);case"fr":return E(()=>import("./fr2.js"),[],import.meta.url);case"hu-HU":return E(()=>import("./hu-HU.js"),__vite__mapDeps([49,50]),import.meta.url);case"hu":return E(()=>import("./hu2.js"),[],import.meta.url);case"it-IT":return E(()=>import("./it-IT.js"),__vite__mapDeps([51,52]),import.meta.url);case"it":return E(()=>import("./it2.js"),[],import.meta.url);case"nl-BE":return E(()=>import("./nl-BE.js"),__vite__mapDeps([53,54]),import.meta.url);case"nl-NL":return E(()=>import("./nl-NL.js"),__vite__mapDeps([55,54]),import.meta.url);case"nl":return E(()=>import("./nl2.js"),[],import.meta.url);case"pl-PL":return E(()=>import("./pl-PL.js"),__vite__mapDeps([56,57]),import.meta.url);case"pl":return E(()=>import("./pl2.js"),[],import.meta.url);case"ro-RO":return E(()=>import("./ro-RO.js"),__vite__mapDeps([58,59]),import.meta.url);case"ro":return E(()=>import("./ro2.js"),[],import.meta.url);case"ru-RU":return E(()=>import("./ru-RU.js"),__vite__mapDeps([60,61]),import.meta.url);case"ru":return E(()=>import("./ru2.js"),[],import.meta.url);case"sk-SK":return E(()=>import("./sk-SK.js"),__vite__mapDeps([62,63]),import.meta.url);case"sk":return E(()=>import("./sk2.js"),[],import.meta.url);case"uk-UA":return E(()=>import("./uk-UA.js"),__vite__mapDeps([64,65]),import.meta.url);case"uk":return E(()=>import("./uk2.js"),[],import.meta.url);case"zh-CN":case"zh":return E(()=>import("./zh2.js"),[],import.meta.url);default:return E(()=>import("./en2.js"),[],import.meta.url)}};class wn extends Ii(qt(P)){static get scopedElements(){return{...super.scopedElements,"lion-validation-feedback":bn}}static get properties(){return{fileList:{type:Array},multiple:{type:Boolean}}}static localizeNamespaces=[{"lion-input-file":yn},...super.localizeNamespaces];constructor(){super(),this.fileList=[],this.multiple=!1}updated(e){super.updated(e),e.has("fileList")&&this._enhanceLightDomA11y()}_enhanceLightDomA11y(){const e=this.shadowRoot?.querySelectorAll('[id^="file-feedback"]'),t=this.parentNode?.parentNode;e?.forEach(s=>{t?.addEventListener("focusin",()=>{s.setAttribute("aria-live","polite")}),t?.addEventListener("focusout",()=>{s.setAttribute("aria-live","assertive")})})}_removeFile(e){this.dispatchEvent(new CustomEvent("file-remove-requested",{detail:{removedFile:e,status:e.status,uploadResponse:e.response}}))}_validationFeedbackTemplate(e,t){return y`
      <lion-validation-feedback
        id="file-feedback-${t}"
        .feedbackData="${e}"
        aria-live="assertive"
      ></lion-validation-feedback>
    `}_listItemBeforeTemplate(e){return j}_listItemAfterTemplate(e,t){return y`
      <button
        class="selected__list__item__remove-button"
        aria-label="${this.msgLit("lion-input-file:removeButtonLabel",{fileName:e.systemFile.name})}"
        @click=${()=>this._removeFile(e)}
      >
        ${this._removeButtonContentTemplate()}
      </button>
    `}_removeButtonContentTemplate(){return y`✖️`}_selectedListItemTemplate(e){const t=Ut();return y`
      <div class="selected__list__item" status="${e.status?e.status.toLowerCase():""}">
        <div class="selected__list__item__label">
          ${this._listItemBeforeTemplate(e)}
          <span id="selected-list-item-label-${t}" class="selected__list__item__label__text">
            <span class="sr-only">${this.msgLit("lion-input-file:fileNameDescriptionLabel")}</span>
            ${e.downloadUrl&&e.status!=="LOADING"?y`
                  <a
                    class="selected__list__item__label__link"
                    href="${e.downloadUrl}"
                    target="${e.downloadUrl.startsWith("blob")?"_blank":""}"
                    rel="${pe(e.downloadUrl.startsWith("blob")?"noopener noreferrer":void 0)}"
                    >${e.systemFile?.name}</a
                  >
                `:e.systemFile?.name}
          </span>
          ${this._listItemAfterTemplate(e,t)}
        </div>
        ${e.status==="FAIL"&&e.validationFeedback?y`
              ${Ul(e.validationFeedback,s=>y`
                  ${this._validationFeedbackTemplate([s],t)}
                `)}
            `:j}
      </div>
    `}render(){return this.fileList?.length?y`
          ${this.multiple?y`
                <ul class="selected__list">
                  ${this.fileList.map(e=>y` <li>${this._selectedListItemTemplate(e)}</li> `)}
                </ul>
              `:y` ${this._selectedListItemTemplate(this.fileList[0])} `}
        `:j}static get styles(){return[S`
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
      `]}}function Zi(i,e=2){if(!+i)return"0 Bytes";const t=1024,s=e<0?0:e,o=[" bytes","KB","MB","GB","TB","PB","EB","ZB","YB"],n=Math.floor(Math.log(i)/Math.log(t));return`${parseFloat((i/t**n).toFixed(s))}${o[n]}`}class Hl extends qt(Ii(jt)){static get scopedElements(){return{...super.scopedElements,"lion-selected-file-list":wn}}static get properties(){return{accept:{type:String},multiple:{type:Boolean,reflect:!0},buttonLabel:{type:String,attribute:"button-label"},maxFileSize:{type:Number,attribute:"max-file-size"},enableDropZone:{type:Boolean,attribute:"enable-drop-zone"},uploadOnSelect:{type:Boolean,attribute:"upload-on-select"},isDragging:{type:Boolean,attribute:"is-dragging",reflect:!0},uploadResponse:{type:Array,state:!1},_selectedFilesMetaData:{type:Array,state:!0}}}static localizeNamespaces=[{"lion-input-file":yn},...super.localizeNamespaces];static get validationTypes(){return["error","info"]}get slots(){return{...super.slots,input:()=>y`<input .value="${pe(this.getAttribute("value"))}" />`,"file-select-button":()=>y`<button
          type="button"
          id="select-button-${this._inputId}"
          @click="${this.__openDialogOnBtnClick}"
        >
          ${this.buttonLabel}
        </button>`,after:()=>y`<div data-description></div>`,"selected-file-list":()=>({template:y`
          <lion-selected-file-list
            .fileList=${this._selectedFilesMetaData}
            .multiple=${this.multiple}
          ></lion-selected-file-list>
        `,renderAsDirectHostChild:!0})}}get _inputNode(){return super._inputNode}get _buttonNode(){return this.querySelector(`#select-button-${this._inputId}`)}get buttonLabel(){return this.__buttonLabel||this._buttonNode?.textContent?.trim()||""}set buttonLabel(e){const t=this.buttonLabel;this.__buttonLabel=e,this.requestUpdate("buttonLabel",t)}get _focusableNode(){return this._buttonNode}get _isDragAndDropSupported(){return"draggable"in document.createElement("div")}constructor(){super(),this.type="file",this._selectedFilesMetaData=[],this.uploadResponse=[],this.__initialUploadResponse=this.uploadResponse,this.uploadOnSelect=!1,this.multiple=!1,this.enableDropZone=!1,this.maxFileSize=Pl,this.accept="",this.buttonLabel="",this._initialButtonLabel="",this.modelValue=[],this._onRemoveFile=this._onRemoveFile.bind(this),this.__duplicateFileNamesValidator=new zl({show:!1}),this.__previouslyParsedFiles=null}get _fileListNode(){return Array.from(this.children).find(e=>e.slot==="selected-file-list")}connectedCallback(){super.connectedCallback(),this.__initialUploadResponse=this.uploadResponse,this._initialButtonLabel=this.buttonLabel,this._inputNode.addEventListener("change",this._onChange),this._inputNode.addEventListener("click",this._onClick)}disconnectedCallback(){super.disconnectedCallback(),this._inputNode.removeEventListener("change",this._onChange),this._inputNode.removeEventListener("click",this._onClick)}onLocaleUpdated(){super.onLocaleUpdated(),this.multiple?this.buttonLabel=this._initialButtonLabel||this.msgLit("lion-input-file:selectTextMultipleFile"):this.buttonLabel=this._initialButtonLabel||this.msgLit("lion-input-file:selectTextSingleFile")}get operationMode(){return"upload"}get _acceptCriteria(){let e=[],t=[];if(this.accept){const s=this.accept.replace(/\s+/g,"").split(",");e=s.filter(o=>o.includes("/")),t=s.filter(o=>!o.includes("/"))}return{allowedFileTypes:e,allowedFileExtensions:t,maxFileSize:this.maxFileSize}}reset(){super.reset(),this._selectedFilesMetaData=[],this.uploadResponse=this.__initialUploadResponse,this.modelValue=[],this.dirty=!1}clear(){this._selectedFilesMetaData=[],this.uploadResponse=[],this.modelValue=[]}_showFeedbackConditionFor(e,t){return super._showFeedbackConditionFor(e,t)&&!(this.validationStates.error?.FileTypeAllowed||this.validationStates.error?.FileSizeAllowed)}parser(){if(this.__previouslyParsedFiles===this._inputNode.files)return this.modelValue;this.__previouslyParsedFiles=this._inputNode.files;const e=this._inputNode.files?Array.from(this._inputNode.files):[];return this.multiple?[...this.modelValue??[],...e]:e}formatter(e){return this._inputNode?.value||""}__setupDragDropEventListeners(){const e=this.shadowRoot?.querySelector(".input-file__drop-zone");["dragenter","dragover","dragleave"].forEach(t=>{e?.addEventListener(t,s=>{s.preventDefault(),s.stopPropagation(),this.isDragging=t!=="dragleave"},!1)}),window.addEventListener("drop",t=>{t.target===this._inputNode&&t.preventDefault(),this.isDragging=!1},!1)}firstUpdated(e){super.firstUpdated(e),this.__setupFileValidators(),this._inputNode&&(this._inputNode.type=this.type,this._inputNode.setAttribute("tabindex","-1"),this._inputNode.multiple=this.multiple,this.accept.length&&(this._inputNode.accept=this.accept)),this.enableDropZone&&this._isDragAndDropSupported&&(this.__setupDragDropEventListeners(),this.setAttribute("drop-zone","")),this._fileListNode.addEventListener("file-remove-requested",this._onRemoveFile)}updated(e){super.updated(e),e.has("disabled")&&(this._inputNode.disabled=this.disabled,this.validate()),e.has("buttonLabel")&&this._buttonNode&&(this._buttonNode.textContent=this.buttonLabel),e.has("name")&&(this._inputNode.name=this.name),e.has("_ariaLabelledNodes")&&this.__syncAriaLabelledByAttributesToButton(),e.has("_ariaDescribedNodes")&&this.__syncAriaDescribedByAttributesToButton(),e.has("uploadResponse")&&(this._selectedFilesMetaData.length===0&&this.uploadResponse.forEach(t=>{const s={systemFile:{name:t.name},response:t,status:t.status,validationFeedback:[{message:t.errorMessage}]};this._selectedFilesMetaData=[...this._selectedFilesMetaData,s]}),this._selectedFilesMetaData.forEach(t=>{!this.uploadResponse.some(s=>s.name===t.systemFile.name)&&this.uploadOnSelect?this.__removeFileFromList(t):(this.uploadResponse.forEach(s=>{s.name===t.systemFile.name&&(t.response=s,t.downloadUrl=s.downloadUrl?s.downloadUrl:t.downloadUrl,t.status=s.status,t.validationFeedback=[{type:typeof s.errorMessage=="string"&&s.errorMessage?.length>0?"error":"success",message:s.errorMessage??""}])}),this._selectedFilesMetaData=[...this._selectedFilesMetaData])}),this._updateUploadButtonDescription())}__computeNewAddedFiles(e){const t=e.filter(s=>this._selectedFilesMetaData.findIndex(o=>o.systemFile.name===s.name)===-1);return this.__duplicateFileNamesValidator.param={show:e.length!==t.length},this.validate(),t}_processDroppedFiles(e){if(e.preventDefault(),this.isDragging=!1,!(e.dataTransfer&&e.dataTransfer.items.length>1&&!this.multiple||!e.dataTransfer?.files)){if(this._inputNode.files=e.dataTransfer.files,this.multiple){const s=this.__computeNewAddedFiles(Array.from(e.dataTransfer.files));this.modelValue=[...this.modelValue??[],...s]}else this.modelValue=Array.from(e.dataTransfer.files);this._processFiles(Array.from(e.dataTransfer.files))}}_onChange(e){this.touched=!0,this._onUserInputChanged(),this._processFiles(e?.target?.files)}_onClick(e){e.target.value=""}__syncAriaLabelledByAttributesToButton(){if(this._inputNode.hasAttribute("aria-labelledby")){const e=this._inputNode.getAttribute("aria-labelledby");this._buttonNode?.setAttribute("aria-labelledby",`select-button-${this._inputId} ${e}`)}}__syncAriaDescribedByAttributesToButton(){if(this._inputNode.hasAttribute("aria-describedby")){const e=this._inputNode.getAttribute("aria-describedby")||"";this._buttonNode?.setAttribute("aria-describedby",e)}}__setupFileValidators(){this.defaultValidators=[new ci(this._acceptCriteria),this.__duplicateFileNamesValidator]}_processFiles(e){const t=this.__computeNewAddedFiles(Array.from(e));!this.multiple&&t.length>0&&(this._selectedFilesMetaData=[],this.uploadResponse=[]);let s;for(const n of t.values())s=new Bl(n,this._acceptCriteria),s.failedProp?.length?(this._handleErroredFiles(s),this.uploadResponse=[...this.uploadResponse,{name:s.systemFile.name,status:"FAIL",errorMessage:s.validationFeedback[0].message}]):this.uploadResponse=[...this.uploadResponse,{name:s.systemFile.name,status:"SUCCESS"}],this._selectedFilesMetaData=[...this._selectedFilesMetaData,s],this._handleErrors();const o=this._selectedFilesMetaData.filter(({systemFile:n,status:r})=>t.includes(n)&&r==="SUCCESS").map(({systemFile:n})=>n);o.length>0&&this._dispatchFileListChangeEvent(o)}_dispatchFileListChangeEvent(e){this.dispatchEvent(new CustomEvent("file-list-changed",{detail:{newFiles:e}}))}_handleErrors(){let e=!1;if(this._selectedFilesMetaData.forEach(t=>{t.failedProp&&t.failedProp.length>0&&(e=!0)}),e)this.hasFeedbackFor?.push("error"),this.shouldShowFeedbackFor.push("error");else if(this._prevHasErrors&&this.hasFeedbackFor.includes("error")){const t=this.hasFeedbackFor.indexOf("error");this.hasFeedbackFor.slice(t,t+1);const s=this.shouldShowFeedbackFor.indexOf("error");this.shouldShowFeedbackFor.slice(s,s+1)}this._prevHasErrors=e}_handleErroredFiles(e){e.validationFeedback=[];const{allowedFileExtensions:t,allowedFileTypes:s}=this._acceptCriteria;let o=[],n=0,r;t.length?(o=t,r=o.pop(),n=o.length):s.length&&(s.forEach(u=>{if(u.endsWith("/*"))o.push(u.slice(0,-2));else if(u==="text/plain")o.push("text");else{const h=u.indexOf("/"),b=u.slice(h+1);if(!b.includes("+"))o.push(`.${b}`);else{const p=b.split("+");o.push(`.${p[0]}`)}}}),r=o.pop(),n=o.length);let a="";r?n?a=`${this.msgLit("lion-input-file:allowedFileValidatorComplex",{allowedTypesArray:o.join(", "),allowedTypesLastItem:r,maxSize:Zi(this.maxFileSize)})}`:a=`${this.msgLit("lion-input-file:allowedFileValidatorSimple",{allowedType:r,maxSize:Zi(this.maxFileSize)})}`:a=`${this.msgLit("lion-input-file:allowedFileSize",{maxSize:Zi(this.maxFileSize)})}`;const c={message:a,type:"error"};e.validationFeedback?.push(c)}_updateUploadButtonDescription(){const e=[];let t;this._selectedFilesMetaData.forEach(o=>{o.status==="FAIL"&&(t=o.validationFeedback?o.validationFeedback[0].message.toString():"",e.push(o.systemFile.name))});const s=this.querySelector('[slot="after"]');if(s)if(!this._selectedFilesMetaData||this._selectedFilesMetaData.length===0)this.uploadOnSelect?s.textContent=this.msgLit("lion-input-file:noFilesUploaded"):s.textContent=this.msgLit("lion-input-file:noFilesSelected");else if(this._selectedFilesMetaData.length===1){const{name:o}=this._selectedFilesMetaData[0].systemFile;this.uploadOnSelect?s.textContent=t||this.msgLit("lion-input-file:fileUploaded")+(o??""):s.textContent=t||this.msgLit("lion-input-file:fileSelected")+(o??"")}else this.uploadOnSelect?s.textContent=`${this.msgLit("lion-input-file:filesUploaded",{numberOfFiles:this._selectedFilesMetaData.length})} ${t?this.msgLit("lion-input-file:generalValidatorMessage",{validatorMessage:t,listOfErroneousFiles:e.join(", ")}):""}`:s.textContent=`${this.msgLit("lion-input-file:filesSelected",{numberOfFiles:this._selectedFilesMetaData.length})} ${t?this.msgLit("lion-input-file:generalValidatorMessage",{validatorMessage:t,listOfErroneousFiles:e.join(", ")}):""}`}__removeFileFromList(e){this._selectedFilesMetaData=this._selectedFilesMetaData.filter(t=>t.systemFile.name!==e.systemFile.name),this.modelValue&&(this.modelValue=this.modelValue.filter(t=>t.name!==e.systemFile.name)),this._inputNode.value="",this._handleErrors(),this._updateUploadButtonDescription()}_onRemoveFile(e){if(this.disabled)return;const{removedFile:t}=e.detail;!this.uploadOnSelect&&t&&this.__removeFileFromList(t),this._removeFile(t)}_removeFile(e){this.dispatchEvent(new CustomEvent("file-removed",{detail:{removedFile:e,status:e.status,uploadResponse:e.response}}))}_reflectBackOn(){return!1}_isEmpty(){return this.modelValue?.length===0}_dropZoneTemplate(){return y`
      <div @drop="${this._processDroppedFiles}" class="input-file__drop-zone">
        <div class="input-file__drop-zone__text">
          ${this.msgLit("lion-input-file:dragAndDropText")}
        </div>
        <slot name="file-select-button"></slot>
      </div>
    `}_inputGroupAfterTemplate(){return y` <slot name="selected-file-list"></slot> `}_inputGroupInputTemplate(){return y`
      <slot name="input"> </slot>
      <slot name="after"> </slot>
      ${this.enableDropZone&&this._isDragAndDropSupported?this._dropZoneTemplate():y`
            <div class="input-group__file-select-button">
              <slot name="file-select-button"></slot>
            </div>
          `}
    `}static get styles(){return[super.styles,S`
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
      `]}__openDialogOnBtnClick(e){e.preventDefault(),e.stopPropagation(),this._inputNode.click()}}var ql=class extends wn{static get styles(){return[...super.styles,S`
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
      `]}_listItemAfterTemplate(e,t){return y`
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
    `}_removeButtonContentTemplate(){return y`<craft-icon name="x"></craft-icon>`}_listItemBeforeTemplate(e){return y`<img src="${e.downloadUrl}" alt="" class="preview-thumb" />`}},Wl=S`
  /* Add any craft-specific styles for input-file here */
  ::slotted([slot='selected-file-list']) {
    margin-block-start: var(--c-spacing-lg);
  }
`,jl=class extends Hl{static get styles(){return[...super.styles,Ht,Wl]}get slots(){return{...super.slots,"file-select-button":()=>y`<craft-button
          type="button"
          id="select-button-${this._inputId}"
          @click="${this.__openDialogOnBtnClick}"
        >
          ${this.buttonLabel}
        </craft-button>`}}static get scopedElements(){return{...super.scopedElements,"lion-selected-file-list":ql}}};customElements.get("craft-input-file")||customElements.define("craft-input-file",jl);var Kl=class extends Event{constructor(){super("wa-load",{bubbles:!0,cancelable:!1,composed:!0})}};var Gl=class extends Event{constructor(){super("wa-error",{bubbles:!0,cancelable:!1,composed:!0})}},Yl=`:host {
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
`,xt=Symbol(),Qt=Symbol(),Xi,Qi=new Map,ae=class extends ue{constructor(){super(...arguments),this.svg=null,this.autoWidth=!1,this.swapOpacity=!1,this.label="",this.library="default",this.resolveIcon=async(i,e)=>{let t;if(e?.spriteSheet){this.hasUpdated||await this.updateComplete,this.svg=y`<svg part="svg">
        <use part="use" href="${i}"></use>
      </svg>`,await this.updateComplete;const s=this.shadowRoot.querySelector("[part='svg']");return typeof e.mutator=="function"&&e.mutator(s,this),this.svg}try{if(t=await fetch(i,{mode:"cors"}),!t.ok)return t.status===410?xt:Qt}catch{return Qt}try{const s=document.createElement("div");s.innerHTML=await t.text();const o=s.firstElementChild;if(o?.tagName?.toLowerCase()!=="svg")return xt;Xi||(Xi=new DOMParser);const r=Xi.parseFromString(o.outerHTML,"text/html").body.querySelector("svg");return r?(r.part.add("svg"),document.adoptNode(r)):xt}catch{return xt}}}connectedCallback(){super.connectedCallback(),lr(this)}firstUpdated(i){super.firstUpdated(i),this.setIcon()}disconnectedCallback(){super.disconnectedCallback(),cr(this)}getIconSource(){const i=Pi(this.library),e=this.family||hr();return this.name&&i?{url:i.resolver(this.name,e,this.variant,this.autoWidth),fromLibrary:!0}:{url:this.src,fromLibrary:!1}}handleLabelChange(){typeof this.label=="string"&&this.label.length>0?(this.setAttribute("role","img"),this.setAttribute("aria-label",this.label),this.removeAttribute("aria-hidden")):(this.removeAttribute("role"),this.removeAttribute("aria-label"),this.setAttribute("aria-hidden","true"))}async setIcon(){const{url:i,fromLibrary:e}=this.getIconSource(),t=e?Pi(this.library):void 0;if(!i){this.svg=null;return}let s=Qi.get(i);s||(s=this.resolveIcon(i,t),Qi.set(i,s));const o=await s;if(o===Qt&&Qi.delete(i),i===this.getIconSource().url){if(hn(o)){this.svg=o;return}switch(o){case Qt:case xt:this.svg=null,this.dispatchEvent(new Gl);break;default:this.svg=o.cloneNode(!0),t?.mutator?.(this.svg,this),this.dispatchEvent(new Kl)}}}updated(i){super.updated(i);const e=Pi(this.library),t=this.shadowRoot?.querySelector("svg");t&&e?.mutator?.(t,this)}render(){return this.hasUpdated?this.svg:y`<svg part="svg" fill="currentColor" width="16" height="16"></svg>`}};ae.css=Yl;g([de()],ae.prototype,"svg",2);g([m({reflect:!0})],ae.prototype,"name",2);g([m({reflect:!0})],ae.prototype,"family",2);g([m({reflect:!0})],ae.prototype,"variant",2);g([m({attribute:"auto-width",type:Boolean,reflect:!0})],ae.prototype,"autoWidth",2);g([m({attribute:"swap-opacity",type:Boolean,reflect:!0})],ae.prototype,"swapOpacity",2);g([m()],ae.prototype,"src",2);g([m()],ae.prototype,"label",2);g([m({reflect:!0})],ae.prototype,"library",2);g([ge("label")],ae.prototype,"handleLabelChange",1);g([ge(["family","name","library","variant","src","autoWidth","swapOpacity"])],ae.prototype,"setIcon",1);ae=g([Se("wa-icon")],ae);var Zl=S``,Xl=class extends ae{static get styles(){return[ae.styles,Zl]}};customElements.get("craft-icon")||customElements.define("craft-icon",Xl);var Ql=function(i,e,t,s,o){if(s==="m")throw new TypeError("Private method is not writable");if(s==="a"&&!o)throw new TypeError("Private accessor was defined without a setter");if(typeof e=="function"?i!==e||!o:!e.has(i))throw new TypeError("Cannot write private member to an object whose class did not declare it");return s==="a"?o.call(i,t):o?o.value=t:e.set(i,t),t},vo=function(i,e,t,s){if(t==="a"&&!s)throw new TypeError("Private accessor was defined without a getter");if(typeof e=="function"?i!==e||!s:!e.has(i))throw new TypeError("Cannot read private member from an object whose class did not declare it");return t==="m"?s:t==="a"?s.call(i):s?s.value:e.get(i)},St;class Jl{formatToParts(e){const t=[];for(const s of e)t.push({type:"element",value:s}),t.push({type:"literal",value:", "});return t.slice(0,-1)}}const ec=typeof Intl<"u"&&Intl.ListFormat||Jl,tc=[["years","year"],["months","month"],["weeks","week"],["days","day"],["hours","hour"],["minutes","minute"],["seconds","second"],["milliseconds","millisecond"]],ic={minimumIntegerDigits:2};class sc{constructor(e,t={}){St.set(this,void 0);let s=String(t.style||"short");s!=="long"&&s!=="short"&&s!=="narrow"&&s!=="digital"&&(s="short");let o=s==="digital"?"numeric":s;const n=t.hours||o;o=n==="2-digit"?"numeric":n;const r=t.minutes||o;o=r==="2-digit"?"numeric":r;const a=t.seconds||o;o=a==="2-digit"?"numeric":a;const c=t.milliseconds||o;Ql(this,St,{locale:e,style:s,years:t.years||s==="digital"?"short":s,yearsDisplay:t.yearsDisplay==="always"?"always":"auto",months:t.months||s==="digital"?"short":s,monthsDisplay:t.monthsDisplay==="always"?"always":"auto",weeks:t.weeks||s==="digital"?"short":s,weeksDisplay:t.weeksDisplay==="always"?"always":"auto",days:t.days||s==="digital"?"short":s,daysDisplay:t.daysDisplay==="always"?"always":"auto",hours:n,hoursDisplay:t.hoursDisplay==="always"||s==="digital"?"always":"auto",minutes:r,minutesDisplay:t.minutesDisplay==="always"||s==="digital"?"always":"auto",seconds:a,secondsDisplay:t.secondsDisplay==="always"||s==="digital"?"always":"auto",milliseconds:c,millisecondsDisplay:t.millisecondsDisplay==="always"?"always":"auto"},"f")}resolvedOptions(){return vo(this,St,"f")}formatToParts(e){const t=[],s=vo(this,St,"f"),o=s.style,n=s.locale;for(const[r,a]of tc){const c=e[r];if(s[`${r}Display`]==="auto"&&!c)continue;const u=s[r],h=u==="2-digit"?ic:u==="numeric"?{}:{style:"unit",unit:a,unitDisplay:u};let b=new Intl.NumberFormat(n,h).format(c);r==="months"&&(u==="narrow"||o==="narrow"&&b.endsWith("m"))&&(b=b.replace(/(\d+)m$/,"$1mo")),t.push(b)}return new ec(n,{type:"unit",style:o==="digital"?"short":o}).formatToParts(t)}format(e){return this.formatToParts(e).map(t=>t.value).join("")}}St=new WeakMap;const xn=/^[-+]?P(?:(\d+)Y)?(?:(\d+)M)?(?:(\d+)W)?(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?)?$/,yi=["year","month","week","day","hour","minute","second","millisecond"],oc=i=>xn.test(i);class me{constructor(e=0,t=0,s=0,o=0,n=0,r=0,a=0,c=0){this.years=e,this.months=t,this.weeks=s,this.days=o,this.hours=n,this.minutes=r,this.seconds=a,this.milliseconds=c,this.years||(this.years=0),this.sign||(this.sign=Math.sign(this.years)),this.months||(this.months=0),this.sign||(this.sign=Math.sign(this.months)),this.weeks||(this.weeks=0),this.sign||(this.sign=Math.sign(this.weeks)),this.days||(this.days=0),this.sign||(this.sign=Math.sign(this.days)),this.hours||(this.hours=0),this.sign||(this.sign=Math.sign(this.hours)),this.minutes||(this.minutes=0),this.sign||(this.sign=Math.sign(this.minutes)),this.seconds||(this.seconds=0),this.sign||(this.sign=Math.sign(this.seconds)),this.milliseconds||(this.milliseconds=0),this.sign||(this.sign=Math.sign(this.milliseconds)),this.blank=this.sign===0}abs(){return new me(Math.abs(this.years),Math.abs(this.months),Math.abs(this.weeks),Math.abs(this.days),Math.abs(this.hours),Math.abs(this.minutes),Math.abs(this.seconds),Math.abs(this.milliseconds))}static from(e){var t;if(typeof e=="string"){const s=String(e).trim(),o=s.startsWith("-")?-1:1,n=(t=s.match(xn))===null||t===void 0?void 0:t.slice(1).map(r=>(Number(r)||0)*o);return n?new me(...n):new me}else if(typeof e=="object"){const{years:s,months:o,weeks:n,days:r,hours:a,minutes:c,seconds:u,milliseconds:h}=e;return new me(s,o,n,r,a,c,u,h)}throw new RangeError("invalid duration")}static compare(e,t){const s=Date.now(),o=Math.abs(_o(s,me.from(e)).getTime()-s),n=Math.abs(_o(s,me.from(t)).getTime()-s);return o>n?-1:o<n?1:0}toLocaleString(e,t){return new sc(e,t).format(this)}}function _o(i,e){const t=new Date(i);return e.sign<0?(t.setUTCSeconds(t.getUTCSeconds()+e.seconds),t.setUTCMinutes(t.getUTCMinutes()+e.minutes),t.setUTCHours(t.getUTCHours()+e.hours),t.setUTCDate(t.getUTCDate()+e.weeks*7+e.days),t.setUTCMonth(t.getUTCMonth()+e.months),t.setUTCFullYear(t.getUTCFullYear()+e.years)):(t.setUTCFullYear(t.getUTCFullYear()+e.years),t.setUTCMonth(t.getUTCMonth()+e.months),t.setUTCDate(t.getUTCDate()+e.weeks*7+e.days),t.setUTCHours(t.getUTCHours()+e.hours),t.setUTCMinutes(t.getUTCMinutes()+e.minutes),t.setUTCSeconds(t.getUTCSeconds()+e.seconds)),t}function nc(i,e="second",t=Date.now()){const s=i.getTime()-t;if(s===0)return new me;const o=Math.sign(s),n=Math.abs(s),r=Math.floor(n/1e3),a=Math.floor(r/60),c=Math.floor(a/60),u=Math.floor(c/24),h=Math.floor(u/30),b=Math.floor(h/12),p=yi.indexOf(e)||yi.length;return new me(p>=0?b*o:0,p>=1?(h-b*12)*o:0,0,p>=3?(u-h*30)*o:0,p>=4?(c-u*24)*o:0,p>=5?(a-c*60)*o:0,p>=6?(r-a*60)*o:0,p>=7?(n-r*1e3)*o:0)}function En(i,{relativeTo:e=Date.now()}={}){if(e=new Date(e),i.blank)return i;const t=i.sign;let s=Math.abs(i.years),o=Math.abs(i.months),n=Math.abs(i.weeks),r=Math.abs(i.days),a=Math.abs(i.hours),c=Math.abs(i.minutes),u=Math.abs(i.seconds),h=Math.abs(i.milliseconds);h>=900&&(u+=Math.round(h/1e3)),(u||c||a||r||n||o||s)&&(h=0),u>=55&&(c+=Math.round(u/60)),(c||a||r||n||o||s)&&(u=0),c>=55&&(a+=Math.round(c/60)),(a||r||n||o||s)&&(c=0),r&&a>=12&&(r+=Math.round(a/24)),!r&&a>=21&&(r+=Math.round(a/24)),(r||n||o||s)&&(a=0);const b=e.getFullYear(),p=e.getMonth(),f=e.getDate();if(r>=27||s+o+r){const _=new Date(e);_.setDate(1),_.setMonth(p+o*t+1),_.setDate(0);const v=Math.max(0,f-_.getDate()),w=new Date(e);w.setFullYear(b+s*t),w.setDate(f-v),w.setMonth(p+o*t),w.setDate(f-v+r*t);const x=w.getFullYear()-e.getFullYear(),A=w.getMonth()-e.getMonth(),L=Math.abs(Math.round((Number(w)-Number(e))/864e5))+v,N=Math.abs(x*12+A);L<27?(r>=6?(n+=Math.round(r/7),r=0):r=L,o=s=0):N<=11?(o=N,s=0):(o=0,s=x*t),(o||s)&&(r=0)}return s&&(o=0),n>=4&&(o+=Math.round(n/4)),(o||s)&&(n=0),r&&n&&!o&&!s&&(n+=Math.round(r/7),r=0),new me(s*t,o*t,n*t,r*t,a*t,c*t,u*t,h*t)}function rc(i,e){const t=En(i,e);if(t.blank)return[0,"second"];for(const s of yi){if(s==="millisecond")continue;const o=t[`${s}s`];if(o)return[o,s]}return[0,"second"]}var K=function(i,e,t,s){if(t==="a"&&!s)throw new TypeError("Private accessor was defined without a getter");if(typeof e=="function"?i!==e||!s:!e.has(i))throw new TypeError("Cannot read private member from an object whose class did not declare it");return t==="m"?s:t==="a"?s.call(i):s?s.value:e.get(i)},Jt=function(i,e,t,s,o){if(s==="m")throw new TypeError("Private method is not writable");if(s==="a"&&!o)throw new TypeError("Private accessor was defined without a setter");if(typeof e=="function"?i!==e||!o:!e.has(i))throw new TypeError("Cannot write private member to an object whose class did not declare it");return s==="a"?o.call(i,t):o?o.value=t:e.set(i,t),t},se,At,Tt,ot,Ze,xs,Cn,kn,Sn,An,Tn,Es,Nn,rt;const ac=globalThis.HTMLElement||null,Ji=new me,yo=new me(0,0,0,0,0,1);class lc extends Event{constructor(e,t,s,o){super("relative-time-updated",{bubbles:!0,composed:!0}),this.oldText=e,this.newText=t,this.oldTitle=s,this.newTitle=o}}function wo(i){if(!i.date)return 1/0;if(i.format==="duration"||i.format==="elapsed"){const t=i.precision;if(t==="second")return 1e3;if(t==="minute")return 60*1e3}const e=Math.abs(Date.now()-i.date.getTime());return e<60*1e3?1e3:e<3600*1e3?60*1e3:3600*1e3}const es=new class{constructor(){this.elements=new Set,this.time=1/0,this.timer=-1}observe(i){if(this.elements.has(i))return;this.elements.add(i);const e=i.date;if(e&&e.getTime()){const t=wo(i),s=Date.now()+t;s<this.time&&(clearTimeout(this.timer),this.timer=setTimeout(()=>this.update(),t),this.time=s)}}unobserve(i){this.elements.has(i)&&this.elements.delete(i)}update(){if(clearTimeout(this.timer),!this.elements.size)return;let i=1/0;for(const e of this.elements)i=Math.min(i,wo(e)),e.update();this.time=Math.min(3600*1e3,i),this.timer=setTimeout(()=>this.update(),this.time),this.time+=Date.now()}};class cc extends ac{constructor(){super(...arguments),se.add(this),At.set(this,!1),Tt.set(this,!1),Ze.set(this,this.shadowRoot?this.shadowRoot:this.attachShadow?this.attachShadow({mode:"open"}):this),rt.set(this,null)}static define(e="relative-time",t=customElements){return t.define(e,this),this}get timeZone(){var e;return((e=this.closest("[time-zone]"))===null||e===void 0?void 0:e.getAttribute("time-zone"))||this.ownerDocument.documentElement.getAttribute("time-zone")||void 0}static get observedAttributes(){return["second","minute","hour","weekday","day","month","year","time-zone-name","prefix","threshold","tense","precision","format","format-style","no-title","datetime","lang","title","aria-hidden","time-zone"]}get onRelativeTimeUpdated(){return K(this,rt,"f")}set onRelativeTimeUpdated(e){K(this,rt,"f")&&this.removeEventListener("relative-time-updated",K(this,rt,"f")),Jt(this,rt,typeof e=="object"||typeof e=="function"?e:null,"f"),typeof e=="function"&&this.addEventListener("relative-time-updated",e)}get second(){const e=this.getAttribute("second");if(e==="numeric"||e==="2-digit")return e}set second(e){this.setAttribute("second",e||"")}get minute(){const e=this.getAttribute("minute");if(e==="numeric"||e==="2-digit")return e}set minute(e){this.setAttribute("minute",e||"")}get hour(){const e=this.getAttribute("hour");if(e==="numeric"||e==="2-digit")return e}set hour(e){this.setAttribute("hour",e||"")}get weekday(){const e=this.getAttribute("weekday");if(e==="long"||e==="short"||e==="narrow")return e;if(this.format==="datetime"&&e!=="")return this.formatStyle}set weekday(e){this.setAttribute("weekday",e||"")}get day(){var e;const t=(e=this.getAttribute("day"))!==null&&e!==void 0?e:"numeric";if(t==="numeric"||t==="2-digit")return t}set day(e){this.setAttribute("day",e||"")}get month(){const e=this.format;let t=this.getAttribute("month");if(t!==""&&(t??(t=e==="datetime"?this.formatStyle:"short"),t==="numeric"||t==="2-digit"||t==="short"||t==="long"||t==="narrow"))return t}set month(e){this.setAttribute("month",e||"")}get year(){var e;const t=this.getAttribute("year");if(t==="numeric"||t==="2-digit")return t;if(!this.hasAttribute("year")&&new Date().getUTCFullYear()!==((e=this.date)===null||e===void 0?void 0:e.getUTCFullYear()))return"numeric"}set year(e){this.setAttribute("year",e||"")}get timeZoneName(){const e=this.getAttribute("time-zone-name");if(e==="long"||e==="short"||e==="shortOffset"||e==="longOffset"||e==="shortGeneric"||e==="longGeneric")return e}set timeZoneName(e){this.setAttribute("time-zone-name",e||"")}get prefix(){var e;return(e=this.getAttribute("prefix"))!==null&&e!==void 0?e:this.format==="datetime"?"":"on"}set prefix(e){this.setAttribute("prefix",e)}get threshold(){const e=this.getAttribute("threshold");return e&&oc(e)?e:"P30D"}set threshold(e){this.setAttribute("threshold",e)}get tense(){const e=this.getAttribute("tense");return e==="past"?"past":e==="future"?"future":"auto"}set tense(e){this.setAttribute("tense",e)}get precision(){const e=this.getAttribute("precision");return yi.includes(e)?e:this.format==="micro"?"minute":"second"}set precision(e){this.setAttribute("precision",e)}get format(){const e=this.getAttribute("format");return e==="datetime"?"datetime":e==="relative"?"relative":e==="duration"?"duration":e==="micro"?"micro":e==="elapsed"?"elapsed":"auto"}set format(e){this.setAttribute("format",e)}get formatStyle(){const e=this.getAttribute("format-style");if(e==="long")return"long";if(e==="short")return"short";if(e==="narrow")return"narrow";const t=this.format;return t==="elapsed"||t==="micro"?"narrow":t==="datetime"?"short":"long"}set formatStyle(e){this.setAttribute("format-style",e)}get noTitle(){return this.hasAttribute("no-title")}set noTitle(e){this.toggleAttribute("no-title",e)}get datetime(){return this.getAttribute("datetime")||""}set datetime(e){this.setAttribute("datetime",e)}get date(){const e=Date.parse(this.datetime);return Number.isNaN(e)?null:new Date(e)}set date(e){this.datetime=e?.toISOString()||""}connectedCallback(){this.update()}disconnectedCallback(){es.unobserve(this)}attributeChangedCallback(e,t,s){t!==s&&(e==="title"&&Jt(this,At,s!==null&&(this.date&&K(this,se,"m",xs).call(this,this.date))!==s,"f"),!K(this,Tt,"f")&&!(e==="title"&&K(this,At,"f"))&&Jt(this,Tt,(async()=>{await Promise.resolve(),this.update(),Jt(this,Tt,!1,"f")})(),"f"))}update(){const e=K(this,Ze,"f").textContent||this.textContent||"",t=this.getAttribute("title")||"";let s=t;const o=this.date;if(typeof Intl>"u"||!Intl.DateTimeFormat||!o){K(this,Ze,"f").textContent=e;return}const n=Date.now();K(this,At,"f")||(s=K(this,se,"m",xs).call(this,o)||"",s&&!this.noTitle&&this.setAttribute("title",s));const r=nc(o,this.precision,n),a=K(this,se,"m",Cn).call(this,r);let c=e;const u=K(this,se,"m",Nn).call(this,a);u?c=K(this,se,"m",Tn).call(this,o):a==="duration"?c=K(this,se,"m",kn).call(this,r):a==="relative"?c=K(this,se,"m",Sn).call(this,r):c=K(this,se,"m",An).call(this,o),c?K(this,se,"m",Es).call(this,c):this.shadowRoot===K(this,Ze,"f")&&this.textContent&&K(this,se,"m",Es).call(this,this.textContent),(c!==e||s!==t)&&this.dispatchEvent(new lc(e,c,t,s)),(a==="relative"||a==="duration")&&!u?es.observe(this):es.unobserve(this)}}At=new WeakMap,Tt=new WeakMap,Ze=new WeakMap,rt=new WeakMap,se=new WeakSet,ot=function(){var e;const t=((e=this.closest("[lang]"))===null||e===void 0?void 0:e.getAttribute("lang"))||this.ownerDocument.documentElement.getAttribute("lang");try{return new Intl.Locale(t??"").toString()}catch{return"default"}},xs=function(e){return new Intl.DateTimeFormat(K(this,se,"a",ot),{day:"numeric",month:"short",year:"numeric",hour:"numeric",minute:"2-digit",timeZoneName:"short",timeZone:this.timeZone}).format(e)},Cn=function(e){const t=this.format;if(t==="datetime")return"datetime";if(t==="duration"||t==="elapsed"||t==="micro")return"duration";if((t==="auto"||t==="relative")&&typeof Intl<"u"&&Intl.RelativeTimeFormat){const s=this.tense;if(s==="past"||s==="future"||me.compare(e,this.threshold)===1)return"relative"}return"datetime"},kn=function(e){const t=K(this,se,"a",ot),s=this.format,o=this.formatStyle,n=this.tense;let r=Ji;s==="micro"?(e=En(e),r=yo,e.months===0&&(this.tense==="past"&&e.sign!==-1||this.tense==="future"&&e.sign!==1)&&(e=yo)):(n==="past"&&e.sign!==-1||n==="future"&&e.sign!==1)&&(e=r);const a=`${this.precision}sDisplay`;return e.blank?r.toLocaleString(t,{style:o,[a]:"always"}):e.abs().toLocaleString(t,{style:o})},Sn=function(e){const t=new Intl.RelativeTimeFormat(K(this,se,"a",ot),{numeric:"auto",style:this.formatStyle}),s=this.tense;s==="future"&&e.sign!==1&&(e=Ji),s==="past"&&e.sign!==-1&&(e=Ji);const[o,n]=rc(e);return n==="second"&&o<10?t.format(0,this.precision==="millisecond"?"second":this.precision):t.format(o,n)},An=function(e){const t=new Intl.DateTimeFormat(K(this,se,"a",ot),{second:this.second,minute:this.minute,hour:this.hour,weekday:this.weekday,day:this.day,month:this.month,year:this.year,timeZoneName:this.timeZoneName,timeZone:this.timeZone});return`${this.prefix} ${t.format(e)}`.trim()},Tn=function(e){return new Intl.DateTimeFormat(K(this,se,"a",ot),{day:"numeric",month:"short",year:"numeric",hour:"numeric",minute:"2-digit",timeZoneName:"short",timeZone:this.timeZone}).format(e)},Es=function(e){if(this.hasAttribute("aria-hidden")&&this.getAttribute("aria-hidden")==="true"){const t=document.createElement("span");t.setAttribute("aria-hidden","true"),t.textContent=e,K(this,Ze,"f").replaceChildren(t)}else K(this,Ze,"f").textContent=e},Nn=function(e){var t;return e==="duration"?!1:this.ownerDocument.documentElement.getAttribute("data-prefers-absolute-time")==="true"||((t=this.ownerDocument.body)===null||t===void 0?void 0:t.getAttribute("data-prefers-absolute-time"))==="true"};const xo=typeof globalThis<"u"?globalThis:window;try{xo.RelativeTimeElement=cc.define()}catch(i){if(!(xo.DOMException&&i instanceof DOMException&&i.name==="NotSupportedError")&&!(i instanceof ReferenceError))throw i}var dc=class extends ws{static get styles(){return[...super.styles,S`
        .input-group__input {
          font-family: var(--c-font-mono);
          font-size: 0.9em;
        }
      `]}constructor(){super(),this.autocorrect=!1}firstUpdated(e){super.firstUpdated(e),this._inputNode?.setAttribute("autocapitalize","off")}};customElements.get("craft-input-handle")||customElements.define("craft-input-handle",dc),yr();var Eo=class extends Vi{static get styles(){return[...super.styles,Ht,S`
        .input-group__container {
          position: relative;
        }

        .input-group__suffix {
          position: absolute;
          inset-inline-end: var(--c-input-spacing-inline);
          inset-block-start: 50%;
          transform: translateY(calc(-50%));
        }
      `]}constructor(){super(),this._visible=!1,this.reveal=()=>{this._visible=!this._visible,this.type=this._visible?"text":"password"},this.renderSuffix=()=>y`
      <craft-button
        type="button"
        icon
        size="small"
        variant="plain"
        @click="${this.reveal}"
        appearance="plain"
      >
        <span class="icon"
          >${this._visible?y`<craft-icon name="eye-slash"></craft-icon>`:y`<craft-icon name="eye"></craft-icon>`}
        </span>
      </craft-button>
    `,this.type="password"}get slots(){return{...super.slots,suffix:()=>({template:this.renderSuffix()})}}};C([de()],Eo.prototype,"_visible",void 0),customElements.get("craft-input-password")||customElements.define("craft-input-password",Eo);var uc=S`
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
`,ei=class extends P{constructor(...e){super(...e),this.size="",this.variant=""}render(){let e=!!this.querySelector('[slot="prefix"]'),t=!!this.querySelector('[slot="suffix"]');return y`
      <div
        class="${le({chip:!0,"chip--small":this.size==="small","chip--medium":this.size==="medium","chip--large":this.size==="large","chip--plain":this.variant==="plain"})}"
      >
        ${e?y`<div class="chip__prefix"><slot name="prefix"></slot></div>`:j}
        <div class="chip__body">
          <slot></slot>
        </div>
        ${t?y`<div class="chip__suffix"><slot name="suffix"></slot></div>`:j}
      </div>
    `}};ei.styles=[uc],C([m()],ei.prototype,"size",void 0),C([m()],ei.prototype,"variant",void 0),customElements.get("craft-chip")||customElements.define("craft-chip",ei);var hc=S`
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
`,ti=class extends P{constructor(...e){super(...e),this.label=null,this.status=null}getLabel(){return!this.label&&this.status?`Status: ${this.status}`:this.label}render(){return y`
      <span
        class="${le({status:!0,"status--live":this.status==="live","status--enabled":this.status==="enabled","status--pending":this.status==="pending","status--expired":this.status==="expired","status--disabled":this.status==="disabled"})}"
        role="img"
        aria-label="${this.getLabel()}"
      ></span>
    `}};ti.styles=[hc],C([m()],ti.prototype,"label",void 0),C([m()],ti.prototype,"status",void 0),customElements.get("craft-status")||customElements.define("craft-status",ti);var It=new Map;function pc(i){var e=It.get(i);e&&e.destroy()}function fc(i){var e=It.get(i);e&&e.update()}var Nt=null;typeof window>"u"?((Nt=function(i){return i}).destroy=function(i){return i},Nt.update=function(i){return i}):((Nt=function(i,e){return i&&Array.prototype.forEach.call(i.length?i:[i],function(t){return(function(s){if(s&&s.nodeName&&s.nodeName==="TEXTAREA"&&!It.has(s)){var o,n=null,r=window.getComputedStyle(s),a=(o=s.value,function(){u({testForHeightReduction:o===""||!s.value.startsWith(o),restoreTextAlign:null}),o=s.value}),c=(function(b){s.removeEventListener("autosize:destroy",c),s.removeEventListener("autosize:update",h),s.removeEventListener("input",a),window.removeEventListener("resize",h),Object.keys(b).forEach(function(p){return s.style[p]=b[p]}),It.delete(s)}).bind(s,{height:s.style.height,resize:s.style.resize,textAlign:s.style.textAlign,overflowY:s.style.overflowY,overflowX:s.style.overflowX,wordWrap:s.style.wordWrap});s.addEventListener("autosize:destroy",c),s.addEventListener("autosize:update",h),s.addEventListener("input",a),window.addEventListener("resize",h),s.style.overflowX="hidden",s.style.wordWrap="break-word",It.set(s,{destroy:c,update:h}),h()}function u(b){var p,f,_=b.restoreTextAlign,v=_===void 0?null:_,w=b.testForHeightReduction,x=w===void 0||w,A=r.overflowY;if(s.scrollHeight!==0&&(r.resize==="vertical"?s.style.resize="none":r.resize==="both"&&(s.style.resize="horizontal"),x&&(p=(function(N){for(var V=[];N&&N.parentNode&&N.parentNode instanceof Element;)N.parentNode.scrollTop&&V.push([N.parentNode,N.parentNode.scrollTop]),N=N.parentNode;return function(){return V.forEach(function(B){var U=B[0],ee=B[1];U.style.scrollBehavior="auto",U.scrollTop=ee,U.style.scrollBehavior=null})}})(s),s.style.height=""),f=r.boxSizing==="content-box"?s.scrollHeight-(parseFloat(r.paddingTop)+parseFloat(r.paddingBottom)):s.scrollHeight+parseFloat(r.borderTopWidth)+parseFloat(r.borderBottomWidth),r.maxHeight!=="none"&&f>parseFloat(r.maxHeight)?(r.overflowY==="hidden"&&(s.style.overflow="scroll"),f=parseFloat(r.maxHeight)):r.overflowY!=="hidden"&&(s.style.overflow="hidden"),s.style.height=f+"px",v&&(s.style.textAlign=v),p&&p(),n!==f&&(s.dispatchEvent(new Event("autosize:resized",{bubbles:!0})),n=f),A!==r.overflow&&!v)){var L=r.textAlign;r.overflow==="hidden"&&(s.style.textAlign=L==="start"?"end":"start"),u({restoreTextAlign:L,testForHeightReduction:!0})}}function h(){u({testForHeightReduction:!0,restoreTextAlign:null})}})(t)}),i}).destroy=function(i){return i&&Array.prototype.forEach.call(i.length?i:[i],pc),i},Nt.update=function(i){return i&&Array.prototype.forEach.call(i.length?i:[i],fc),i});var ts=Nt;class mc extends jt{get _inputNode(){return Array.from(this.children).find(e=>e.slot==="input")}}class bc extends vn(mc){static get properties(){return{maxRows:{type:Number,attribute:"max-rows"},rows:{type:Number,reflect:!0},readOnly:{type:Boolean,attribute:"readonly",reflect:!0},placeholder:{type:String,reflect:!0}}}get slots(){return{...super.slots,input:()=>{const e=document.createElement("textarea");return e.style.resize!==void 0&&(e.style.resize="none"),e}}}constructor(){super(),this.rows=2,this.maxRows=6,this.readOnly=!1,this.placeholder=""}connectedCallback(){super.connectedCallback(),this.__initializeAutoresize(),this.__intersectionObserver=new IntersectionObserver(()=>this.resizeTextarea()),this.__intersectionObserver.observe(this)}updated(e){if(super.updated(e),e.has("name")&&(this._inputNode.name=this.name),e.has("autocomplete")&&(this._inputNode.autocomplete=this.autocomplete),e.has("disabled")&&(this._inputNode.disabled=this.disabled,this.validate()),e.has("rows")){const t=this._inputNode;t&&(t.rows=this.rows)}if(e.has("readOnly")){const t=this._inputNode;t&&(t.readOnly=this.readOnly)}if(e.has("placeholder")){const t=this._inputNode;t&&(t.placeholder=this.placeholder)}e.has("modelValue")&&this.resizeTextarea(),(e.has("maxRows")||e.has("rows"))&&this.setTextareaMaxHeight()}disconnectedCallback(){super.disconnectedCallback(),ts.destroy(this._inputNode)}setTextareaMaxHeight(){const{value:e}=this._inputNode;this._inputNode.value="",this.resizeTextarea();const t=window.getComputedStyle(this._inputNode,null),s=parseFloat(t.lineHeight)||parseFloat(t.height)/this.rows,o=parseFloat(t.paddingTop)+parseFloat(t.paddingBottom),n=parseFloat(t.borderTopWidth)+parseFloat(t.borderBottomWidth),r=t.boxSizing==="border-box"?o+n:0;this._inputNode.style.maxHeight=`${s*this.maxRows+r}px`,this._inputNode.value=e,this.resizeTextarea()}static get styles(){return[...super.styles,S`
        .input-group__container > .input-group__input ::slotted(.form-control) {
          box-sizing: content-box;
          overflow-x: hidden; /* for FF adds height to the TextArea to reserve place for scroll-bars */
        }

        /* Workaround https://bugzilla.mozilla.org/show_bug.cgi?id=1739079 */
        :host([disabled]) ::slotted(textarea) {
          user-select: none;
        }
      `]}get updateComplete(){return this.__textareaUpdateComplete?Promise.all([this.__textareaUpdateComplete,super.updateComplete]):super.updateComplete}resizeTextarea(){ts.update(this._inputNode)}__initializeAutoresize(){this.__shady_native_contains?this.__textareaUpdateComplete=this.__waitForTextareaRenderedInRealDOM().then(()=>{this.__startAutoresize(),this.__textareaUpdateComplete=null}):this.__startAutoresize()}async __waitForTextareaRenderedInRealDOM(){let e=3;for(;e!==0&&!this.__shady_native_contains(this._inputNode);)await new Promise(t=>setTimeout(t)),e-=1}__startAutoresize(){ts(this._inputNode),this.setTextareaMaxHeight()}}var gc=S`
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
`,vc=class extends bc{static get styles(){return[...super.styles,Ht,gc]}};customElements.get("craft-textarea")||customElements.define("craft-textarea",vc);var _c=S`
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
`,Co=class extends P{render(){return y`<slot></slot>`}};Co.styles=[_c],customElements.get("craft-button-group")||customElements.define("craft-button-group",Co);class yc extends jt{static get properties(){return{autocomplete:{type:String}}}constructor(){super(),this.autocomplete=void 0}get _inputNode(){return Array.from(this.children).find(e=>e.slot==="input")}}class wc extends yc{get operationMode(){return"select"}connectedCallback(){super.connectedCallback(),this._inputNode.addEventListener("change",this._proxyChangeEvent),this.__selectObserver=new MutationObserver(()=>{this._syncValueUpwards(),this._calculateValues({source:"model"})}),this.__selectObserver.observe(this._inputNode,{attributes:!0,childList:!0,subtree:!0})}updated(e){super.updated(e),e.has("disabled")&&(this._inputNode.disabled=this.disabled,this.validate()),e.has("name")&&(this._inputNode.name=this.name),e.has("autocomplete")&&(this._inputNode.autocomplete=this.autocomplete)}disconnectedCallback(){super.disconnectedCallback(),this._inputNode.removeEventListener("change",this._proxyChangeEvent),this.__selectObserver?.disconnect()}formatter(e){const t=Array.from(this._inputNode.options).find(s=>s.value===e);return t?t.text:""}_reflectBackFormattedValueToUser(){this._reflectBackOn()&&(this.value=typeof this.modelValue<"u"?this.modelValue:"")}_proxyChangeEvent(){this.dispatchEvent(new CustomEvent("user-input-changed",{bubbles:!0,composed:!0}))}}var xc=S`
  ${Li}

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
    ${zs}
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
`,Ec=class extends wc{static get styles(){return[...super.styles,xc]}_inputGroupInputTemplate(){return y`
      <div class="input-group__input">
        <slot name="input"></slot>
        <craft-icon
          class="indicator"
          name="chevron-down"
          style="font-size: 0.8em"
        ></craft-icon>
      </div>
    `}};customElements.get("craft-select")||customElements.define("craft-select",Ec);class Cc extends Ol(P){static get properties(){return{tabIndex:{type:Number,reflect:!0,attribute:"tabindex"}}}constructor(){super(),this.tabIndex=0}connectedCallback(){super.connectedCallback(),this.setAttribute("role","listbox")}createRenderRoot(){return this}}function ko(i,e){Array.from(i.childNodes).forEach(t=>{t.hasAttribute&&t.hasAttribute("slot")||e.appendChild(t)})}const kc=i=>class extends tt(qt(Mi(vt(qs(i))))){static get properties(){return{orientation:String,selectionFollowsFocus:{type:Boolean,attribute:"selection-follows-focus"},rotateKeyboardNavigation:{type:Boolean,attribute:"rotate-keyboard-navigation"},hasNoDefaultSelected:{type:Boolean,reflect:!0,attribute:"has-no-default-selected"},_noTypeAhead:{type:Boolean}}}static get styles(){return[...super.styles||[],S`
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
        `]}_inputGroupInputTemplate(){return y`
        <div class="input-group__input">
          <slot name="input"></slot>
          <slot id="options-outlet"></slot>
        </div>
      `}static get scopedElements(){return{...super.scopedElements,"lion-options":Cc}}get slots(){return{...super.slots,input:()=>{const t=this.createScopedElement("lion-options");return t.setAttribute("data-tag-name","lion-options"),t.registrationTarget=this,t}}}get _inputNode(){return this.querySelector('[slot="input"]')}get _listboxNode(){return this._inputNode}get _listboxActiveDescendantNode(){return this._listboxNode.querySelector(`#${this._listboxActiveDescendant}`)}get _listboxSlot(){return this.shadowRoot.querySelector("slot[name=input]")}get _scrollTargetNode(){return this._listboxNode}get _activeDescendantOwnerNode(){return this._listboxNode}get activeIndex(){return this.formElements.findIndex(t=>t.active===!0)}set activeIndex(t){if(this.formElements[t]){const s=this.formElements[t];this.__setChildActive(s)}else this.__setChildActive(null)}get checkedIndex(){const t=this.formElements;return this.multipleChoice?t.filter(s=>s.checked).map(s=>t.indexOf(s)):t.indexOf(t.find(s=>s.checked))}set checkedIndex(t){this.setCheckedIndex(t)}constructor(){super(),this.hasNoDefaultSelected=!1,this.orientation="vertical",this.rotateKeyboardNavigation=!1,this.selectionFollowsFocus=!1,this._noTypeAhead=!1,this._typeAheadTimeout=1e3,this._listboxActiveDescendant=null,this.__hasInitialSelectedFormElement=!1,this._repropagationRole="choice-group",this._listboxReceivesNoFocus=!1,this._oldModelValue=void 0,this._listboxOnKeyDown=this._listboxOnKeyDown.bind(this),this._listboxOnClick=this._listboxOnClick.bind(this),this._listboxOnKeyUp=this._listboxOnKeyUp.bind(this),this._onChildActiveChanged=this._onChildActiveChanged.bind(this),this.__proxyChildModelValueChanged=this.__proxyChildModelValueChanged.bind(this),this.__preventScrollingWithArrowKeys=this.__preventScrollingWithArrowKeys.bind(this),this.__typedChars=[]}connectedCallback(){this._listboxNode&&(this._listboxNode.registrationTarget=this),super.connectedCallback(),this._setupListboxNode(),this.__setupEventListeners(),this.registrationComplete.then(()=>{this.__initInteractionStates()})}firstUpdated(t){super.firstUpdated(t),this.__moveOptionsToListboxNode(),this.registrationComplete.then(()=>{this._initialModelValue=this.modelValue}),new MutationObserver(()=>{this._onListboxContentChanged()}).observe(this._listboxNode,{childList:!0})}updated(t){super.updated(t),t.has("disabled")&&(this.disabled?this.__requestOptionsToBeDisabled():this.__retractRequestOptionsToBeDisabled())}disconnectedCallback(){super.disconnectedCallback(),this._teardownListboxNode(),this.__teardownEventListeners()}setCheckedIndex(t){if(this.multipleChoice&&Array.isArray(t)){this._uncheckChildren(this.formElements.filter(s=>s===t)),t.forEach(s=>{this.formElements[s]&&(this.formElements[s].checked=!this.formElements[s].checked)});return}typeof t=="number"&&(t===-1&&this._uncheckChildren(),this.formElements[t]&&(this.formElements[t].disabled?this._uncheckChildren():this.multipleChoice?this.formElements[t].checked=!this.formElements[t].checked:this.formElements[t].checked=!0))}addFormElement(t,s){super.addFormElement(t,s),t.id=t.id||`${this.localName}-option-${Ut()}`,this.disabled&&t.makeRequestToBeDisabled(),this.__setAttributeForAllFormElements("aria-setsize",this.formElements.length),this.formElements.forEach((o,n)=>{o.setAttribute("aria-posinset",n+1)}),this.__proxyChildModelValueChanged({target:t}),this.resetInteractionState()}resetInteractionState(){super.resetInteractionState(),this.submitted=!1}reset(){this.modelValue=this._initialModelValue,this.activeIndex=-1,this.resetInteractionState()}clear(){super.clear(),this.setCheckedIndex(-1),this.resetInteractionState()}_handleTypeAhead(t,{setAsChecked:s}){const{key:o,code:n}=t;if(n.startsWith("Key")||n.startsWith("Digit")||n.startsWith("Numpad")){t.preventDefault(),this.__typedChars.push(o);const r=this.__typedChars.join(""),a=this.formElements.findIndex(c=>c.modelValue.value.toLowerCase().startsWith(r));a>=0&&(s&&this.setCheckedIndex(a),this.activeIndex=a),this.__pendingTypeAheadTimeout&&window.clearTimeout(this.__pendingTypeAheadTimeout),this.__pendingTypeAheadTimeout=setTimeout(()=>{this.__typedChars=[]},this._typeAheadTimeout)}}_getCheckedElements(){return this.formElements.filter(t=>t.checked)}_setupListboxNode(){this._listboxNode?this.__setupListboxNodeInteractions():this._listboxSlot&&this._listboxSlot.addEventListener("slotchange",()=>{this.__setupListboxNodeInteractions()})}_onListboxContentChanged(){}_teardownListboxNode(){this._listboxNode&&(this._listboxNode.removeEventListener("keydown",this._listboxOnKeyDown),this._listboxNode.removeEventListener("click",this._listboxOnClick),this._listboxNode.removeEventListener("keyup",this._listboxOnKeyUp))}_getNextEnabledOption(t,s=1){return this.__getEnabledOption(t,s)}_getPreviousEnabledOption(t,s=-1){return this.__getEnabledOption(t,s)}_onChildActiveChanged({target:t}){t.active===!0&&this.__setChildActive(t)}_listboxOnKeyDown(t){if(this.disabled)return;this._isHandlingUserInput=!0,setTimeout(()=>{this._isHandlingUserInput=!1});const{key:s}=t;switch(s){case" ":case"Enter":{if(s===" "&&this._listboxReceivesNoFocus||(s===" "&&t.preventDefault(),!this.formElements[this.activeIndex])||this.formElements[this.activeIndex].disabled)return;this.formElements[this.activeIndex].href&&this.formElements[this.activeIndex].click(),this.setCheckedIndex(this.activeIndex);break}case"ArrowUp":t.preventDefault(),this.orientation==="vertical"&&(this.activeIndex=this._getPreviousEnabledOption(this.activeIndex));break;case"ArrowLeft":if(this._listboxReceivesNoFocus)return;t.preventDefault(),this.orientation==="horizontal"&&(this.activeIndex=this._getPreviousEnabledOption(this.activeIndex));break;case"ArrowDown":t.preventDefault(),this.orientation==="vertical"&&(this.activeIndex=this._getNextEnabledOption(this.activeIndex));break;case"ArrowRight":if(this._listboxReceivesNoFocus)return;t.preventDefault(),this.orientation==="horizontal"&&(this.activeIndex=this._getNextEnabledOption(this.activeIndex));break;case"Home":if(this._listboxReceivesNoFocus)return;t.preventDefault(),this.activeIndex=this._getNextEnabledOption(0,0);break;case"End":if(this._listboxReceivesNoFocus)return;t.preventDefault(),this.activeIndex=this._getPreviousEnabledOption(this.formElements.length-1,0);break;default:this._noTypeAhead||this._handleTypeAhead(t,{setAsChecked:this.selectionFollowsFocus&&!this.multipleChoice})}["ArrowUp","ArrowDown","ArrowLeft","ArrowRight","Home","End"].includes(s)&&this.selectionFollowsFocus&&!this.multipleChoice&&this.setCheckedIndex(this.activeIndex)}_listboxOnClick(t){}_listboxOnKeyUp(t){if(this.disabled)return;this._isHandlingUserInput=!0,setTimeout(()=>{this._isHandlingUserInput=!1});const{key:s}=t;switch(s){case"ArrowUp":case"ArrowDown":case"Home":case"End":case"Enter":t.preventDefault()}}_onLabelClick(){this._listboxNode.focus()}_scrollIntoView(t,s){t.scrollIntoView({behavior:"smooth",block:"nearest"})}__setupEventListeners(){this._listboxNode.addEventListener("active-changed",this._onChildActiveChanged),this._listboxNode.addEventListener("model-value-changed",this.__proxyChildModelValueChanged)}__teardownEventListeners(){this._listboxNode.removeEventListener("active-changed",this._onChildActiveChanged),this._listboxNode.removeEventListener("model-value-changed",this.__proxyChildModelValueChanged)}__setChildActive(t){if(this.formElements.forEach(s=>{s.active=t===s}),!t){this._activeDescendantOwnerNode.removeAttribute("aria-activedescendant");return}this._activeDescendantOwnerNode.setAttribute("aria-activedescendant",t.id),this._scrollIntoView(t,this._scrollTargetNode)}_uncheckChildren(t=[]){const s=Array.isArray(t)?t:[t];this.formElements.forEach(o=>{s.includes(o)||(o.checked=!1)})}__onChildCheckedChanged(t){const{target:s}=t;t.stopPropagation&&t.stopPropagation(),s.checked&&!this.multipleChoice&&this._uncheckChildren(s)}__setAttributeForAllFormElements(t,s){this.formElements.forEach(o=>{o.setAttribute(t,s)})}__proxyChildModelValueChanged(t){t.stopPropagation&&t.stopPropagation(),this.__onChildCheckedChanged(t),this.requestUpdate("modelValue",this._oldModelValue),t.detail&&t.detail.formPath&&this.dispatchEvent(new CustomEvent("model-value-changed",{detail:{formPath:t.detail.formPath,isTriggeredByUser:t.detail.isTriggeredByUser||this._isHandlingUserInput,element:t.target}})),this._oldModelValue=this.modelValue}__getEnabledOption(t,s){const o=n=>s===1?n<this.formElements.length:n>=0;for(let n=t+s;o(n);n+=s)if(this.formElements[n]&&!this.formElements[n].hasAttribute("aria-hidden"))return n;if(this.rotateKeyboardNavigation){const n=s===-1?this.formElements.length-1:0;for(let r=n;o(r);r+=s)if(this.formElements[r]&&!this.formElements[r].hasAttribute("aria-hidden"))return r}return t}__moveOptionsToListboxNode(){const t=this.shadowRoot.getElementById("options-outlet");t&&(ko(this,this._listboxNode),t.addEventListener("slotchange",()=>{ko(this,this._listboxNode)}))}__preventScrollingWithArrowKeys(t){if(this.disabled)return;const{key:s}=t;switch(s){case"ArrowUp":case"ArrowDown":case"Home":case"End":t.preventDefault()}}__setupListboxNodeInteractions(){this._listboxNode.setAttribute("role","listbox"),this._listboxNode.setAttribute("aria-orientation",this.orientation),this._listboxNode.setAttribute("aria-multiselectable",`${this.multipleChoice}`),this._listboxNode.setAttribute("tabindex","0"),this._listboxNode.addEventListener("click",this._listboxOnClick),this._listboxNode.addEventListener("keyup",this._listboxOnKeyUp),this._listboxNode.addEventListener("keydown",this._listboxOnKeyDown),this._scrollTargetNode.addEventListener("keydown",this.__preventScrollingWithArrowKeys)}__requestOptionsToBeDisabled(){this.formElements.forEach(t=>{t.makeRequestToBeDisabled&&t.makeRequestToBeDisabled()})}__retractRequestOptionsToBeDisabled(){this.formElements.forEach(t=>{t.retractRequestToBeDisabled&&t.retractRequestToBeDisabled()})}__initInteractionStates(){this.initInteractionState()}},Sc=Q(kc);class Ac extends Sc(Ps(Hs(Wt(P)))){get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,focused:this.focused}}get _focusableNode(){return this._inputNode}}class So extends Bt($i(Bs(vt(P)))){static get properties(){return{active:{type:Boolean,reflect:!0}}}static get styles(){return[S`
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
      `]}get slots(){return{}}constructor(){super(),this.active=!1,this.__onClick=this.__onClick.bind(this),this.__registerEventListeners()}requestUpdate(e,t,s){super.requestUpdate(e,t,s),e==="active"&&this.active!==t&&this.dispatchEvent(new Event("active-changed",{bubbles:!0}))}updated(e){super.updated(e),e.has("checked")&&this.setAttribute("aria-selected",`${this.checked}`),e.has("disabled")&&this.setAttribute("aria-disabled",`${this.disabled}`)}render(){return y`
      <div class="choice-field__label">
        <slot></slot>
      </div>
    `}connectedCallback(){super.connectedCallback(),this.setAttribute("role","option")}__registerEventListeners(){this.addEventListener("click",this.__onClick)}__unRegisterEventListeners(){this.removeEventListener("click",this.__onClick)}__onClick(){if(this.disabled)return;const e=this._parentFormGroup;this._isHandlingUserInput=!0,e&&e.multipleChoice?(this.checked=!this.checked,this.active=!this.active):(this.checked=!0,this.active=!0),this._isHandlingUserInput=!1}}var Tc=S`
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
`,Ao=class extends So{constructor(...e){super(...e),this.hint=null}static get styles(){return[...So.styles,Tc]}render(){return y`
      <div class="choice-field__label">
        <slot></slot>
        ${this.hint?y`<span class="hint">${this.hint}</span>`:j}
        <slot name="suffix"></slot>
      </div>
    `}};C([m()],Ao.prototype,"hint",void 0),customElements.get("craft-option")||customElements.define("craft-option",Ao);var Fn=`@layer wa-utilities {
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
`;var Nc=class extends Event{constructor(i){super("wa-select",{bubbles:!0,cancelable:!0,composed:!0}),this.detail=i}};function*Ln(i=document.activeElement){i!=null&&(yield i,"shadowRoot"in i&&i.shadowRoot&&i.shadowRoot.mode!=="closed"&&(yield*Ln(i.shadowRoot.activeElement)))}var Fc=`:host {
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
`,is=new Set,re=class extends ue{constructor(){super(...arguments),this.submenuCleanups=new Map,this.localize=new ft(this),this.userTypedQuery="",this.openSubmenuStack=[],this.open=!1,this.size="medium",this.placement="bottom-start",this.distance=0,this.skidding=0,this.handleDocumentKeyDown=async i=>{const e=this.localize.dir()==="rtl";if(i.key==="Escape"){const h=this.getTrigger();i.preventDefault(),i.stopPropagation(),this.open=!1,h?.focus();return}const t=[...Ln()].find(h=>h.localName==="wa-dropdown-item"),s=t?.localName==="wa-dropdown-item",o=this.getCurrentSubmenuItem(),n=!!o;let r,a,c;n?(r=this.getSubmenuItems(o),a=r.find(h=>h.active||h===t),c=a?r.indexOf(a):-1):(r=this.getItems(),a=r.find(h=>h.active||h===t),c=a?r.indexOf(a):-1);let u;if(i.key==="ArrowUp"&&(i.preventDefault(),i.stopPropagation(),c>0?u=r[c-1]:u=r[r.length-1]),i.key==="ArrowDown"&&(i.preventDefault(),i.stopPropagation(),c!==-1&&c<r.length-1?u=r[c+1]:u=r[0]),i.key===(e?"ArrowLeft":"ArrowRight")&&s&&a&&a.hasSubmenu){i.preventDefault(),i.stopPropagation(),a.submenuOpen=!0,this.addToSubmenuStack(a),setTimeout(()=>{const h=this.getSubmenuItems(a);h.length>0&&(h.forEach((b,p)=>b.active=p===0),h[0].focus())},0);return}if(i.key===(e?"ArrowRight":"ArrowLeft")&&n){i.preventDefault(),i.stopPropagation();const h=this.removeFromSubmenuStack();h&&(h.submenuOpen=!1,setTimeout(()=>{h.focus(),h.active=!0,(h.slot==="submenu"?this.getSubmenuItems(h.parentElement):this.getItems()).forEach(p=>{p!==h&&(p.active=!1)})},0));return}if((i.key==="Home"||i.key==="End")&&(i.preventDefault(),i.stopPropagation(),u=i.key==="Home"?r[0]:r[r.length-1]),i.key==="Tab"&&await this.hideMenu(),i.key.length===1&&!(i.metaKey||i.ctrlKey||i.altKey)&&!(i.key===" "&&this.userTypedQuery==="")&&(clearTimeout(this.userTypedTimeout),this.userTypedTimeout=setTimeout(()=>{this.userTypedQuery=""},1e3),this.userTypedQuery+=i.key,r.some(h=>{const b=(h.textContent||"").trim().toLowerCase(),p=this.userTypedQuery.trim().toLowerCase();return b.startsWith(p)?(u=h,!0):!1})),u){i.preventDefault(),i.stopPropagation(),r.forEach(h=>h.active=h===u),u.focus();return}(i.key==="Enter"||i.key===" "&&this.userTypedQuery==="")&&s&&a&&(i.preventDefault(),i.stopPropagation(),a.hasSubmenu?(a.submenuOpen=!0,this.addToSubmenuStack(a),setTimeout(()=>{const h=this.getSubmenuItems(a);h.length>0&&(h.forEach((b,p)=>b.active=p===0),h[0].focus())},0)):this.makeSelection(a))},this.handleDocumentPointerDown=i=>{i.composedPath().some(s=>s instanceof HTMLElement?s===this||s.closest('wa-dropdown, [part="submenu"]'):!1)||(this.open=!1)},this.handleGlobalMouseMove=i=>{const e=this.getCurrentSubmenuItem();if(!e?.submenuOpen||!e.submenuElement)return;const t=e.submenuElement.getBoundingClientRect(),s=this.localize.dir()==="rtl",o=s?t.right:t.left,n=s?Math.max(i.clientX,o):Math.min(i.clientX,o),r=Math.max(t.top,Math.min(i.clientY,t.bottom));e.submenuElement.style.setProperty("--safe-triangle-cursor-x",`${n}px`),e.submenuElement.style.setProperty("--safe-triangle-cursor-y",`${r}px`);const a=e.matches(":hover"),c=e.submenuElement?.matches(":hover")||!!i.composedPath().find(u=>u instanceof HTMLElement&&u.closest('[part="submenu"]')===e.submenuElement);!a&&!c&&setTimeout(()=>{!e.matches(":hover")&&!e.submenuElement?.matches(":hover")&&(e.submenuOpen=!1)},100)}}disconnectedCallback(){super.disconnectedCallback(),clearInterval(this.userTypedTimeout),this.closeAllSubmenus(),this.submenuCleanups.forEach(i=>i()),this.submenuCleanups.clear(),document.removeEventListener("mousemove",this.handleGlobalMouseMove)}firstUpdated(){this.syncAriaAttributes()}async updated(i){i.has("open")&&(this.customStates.set("open",this.open),this.open?await this.showMenu():(this.closeAllSubmenus(),await this.hideMenu())),i.has("size")&&this.syncItemSizes()}getItems(i=!1){const e=this.defaultSlot.assignedElements({flatten:!0}).filter(t=>t.localName==="wa-dropdown-item");return i?e:e.filter(t=>!t.disabled)}getSubmenuItems(i,e=!1){const t=i.shadowRoot?.querySelector('slot[name="submenu"]')||i.querySelector('slot[name="submenu"]');if(!t)return[];const s=t.assignedElements({flatten:!0}).filter(o=>o.localName==="wa-dropdown-item");return e?s:s.filter(o=>!o.disabled)}syncItemSizes(){this.defaultSlot.assignedElements({flatten:!0}).filter(e=>e.localName==="wa-dropdown-item").forEach(e=>e.size=this.size)}addToSubmenuStack(i){const e=this.openSubmenuStack.indexOf(i);e!==-1?this.openSubmenuStack=this.openSubmenuStack.slice(0,e+1):this.openSubmenuStack.push(i)}removeFromSubmenuStack(){return this.openSubmenuStack.pop()}getCurrentSubmenuItem(){return this.openSubmenuStack.length>0?this.openSubmenuStack[this.openSubmenuStack.length-1]:void 0}closeAllSubmenus(){this.getItems(!0).forEach(e=>{e.submenuOpen=!1}),this.openSubmenuStack=[]}closeSiblingSubmenus(i){const e=i.closest('wa-dropdown-item:not([slot="submenu"])');let t;e?t=this.getSubmenuItems(e,!0):t=this.getItems(!0),t.forEach(s=>{s!==i&&s.submenuOpen&&(s.submenuOpen=!1)}),this.openSubmenuStack.includes(i)||this.openSubmenuStack.push(i)}getTrigger(){return this.querySelector('[slot="trigger"]')}async showMenu(){if(!this.getTrigger())return;const e=new Pt;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}is.forEach(s=>s.open=!1),this.popup.active=!0,this.open=!0,is.add(this),this.syncAriaAttributes(),document.addEventListener("keydown",this.handleDocumentKeyDown),document.addEventListener("pointerdown",this.handleDocumentPointerDown),document.addEventListener("mousemove",this.handleGlobalMouseMove),this.menu.classList.remove("hide"),await ne(this.menu,"show");const t=this.getItems();t.length>0&&(t.forEach((s,o)=>s.active=o===0),t[0].focus()),this.dispatchEvent(new Rt)}async hideMenu(){const i=new zt({source:this});if(this.dispatchEvent(i),i.defaultPrevented){this.open=!0;return}this.open=!1,is.delete(this),this.syncAriaAttributes(),document.removeEventListener("keydown",this.handleDocumentKeyDown),document.removeEventListener("pointerdown",this.handleDocumentPointerDown),document.removeEventListener("mousemove",this.handleGlobalMouseMove),this.menu.classList.remove("show"),await ne(this.menu,"hide"),this.popup.active=this.open,this.dispatchEvent(new Vt)}handleMenuClick(i){const e=i.target.closest("wa-dropdown-item");if(!(!e||e.disabled)){if(e.hasSubmenu){e.submenuOpen||(this.closeSiblingSubmenus(e),this.addToSubmenuStack(e),e.submenuOpen=!0),i.stopPropagation();return}this.makeSelection(e)}}async handleMenuSlotChange(){const i=this.getItems(!0);await Promise.all(i.map(s=>s.updateComplete)),this.syncItemSizes();const e=i.some(s=>s.type==="checkbox"),t=i.some(s=>s.hasSubmenu);i.forEach((s,o)=>{s.active=o===0,s.checkboxAdjacent=e,s.submenuAdjacent=t})}handleTriggerClick(){this.open=!this.open}handleSubmenuOpening(i){const e=i.detail.item;this.closeSiblingSubmenus(e),this.addToSubmenuStack(e),this.setupSubmenuPosition(e),this.processSubmenuItems(e)}setupSubmenuPosition(i){if(!i.submenuElement)return;this.cleanupSubmenuPosition(i);const e=tn(i,i.submenuElement,()=>{this.positionSubmenu(i),this.updateSafeTriangleCoordinates(i)});this.submenuCleanups.set(i,e);const t=i.submenuElement.querySelector('slot[name="submenu"]');t&&(t.removeEventListener("slotchange",re.handleSubmenuSlotChange),t.addEventListener("slotchange",re.handleSubmenuSlotChange),re.handleSubmenuSlotChange({target:t}))}static handleSubmenuSlotChange(i){const e=i.target;if(!e)return;const t=e.assignedElements().filter(n=>n.localName==="wa-dropdown-item");if(t.length===0)return;const s=t.some(n=>n.hasSubmenu),o=t.some(n=>n.type==="checkbox");t.forEach(n=>{n.submenuAdjacent=s,n.checkboxAdjacent=o})}processSubmenuItems(i){if(!i.submenuElement)return;const e=this.getSubmenuItems(i,!0),t=e.some(s=>s.hasSubmenu);e.forEach(s=>{s.submenuAdjacent=t})}cleanupSubmenuPosition(i){const e=this.submenuCleanups.get(i);e&&(e(),this.submenuCleanups.delete(i))}positionSubmenu(i){if(!i.submenuElement)return;const t=this.localize.dir()==="rtl"?"left-start":"right-start";rn(i,i.submenuElement,{placement:t,middleware:[sn({mainAxis:0,crossAxis:-5}),nn({fallbackStrategy:"bestFit"}),on({padding:8})]}).then(({x:s,y:o,placement:n})=>{i.submenuElement.setAttribute("data-placement",n),Object.assign(i.submenuElement.style,{left:`${s}px`,top:`${o}px`})})}updateSafeTriangleCoordinates(i){if(!i.submenuElement||!i.submenuOpen)return;if(document.activeElement?.matches(":focus-visible")){i.submenuElement.style.setProperty("--safe-triangle-visible","none");return}i.submenuElement.style.setProperty("--safe-triangle-visible","block");const t=i.submenuElement.getBoundingClientRect(),s=this.localize.dir()==="rtl";i.submenuElement.style.setProperty("--safe-triangle-submenu-start-x",`${s?t.right:t.left}px`),i.submenuElement.style.setProperty("--safe-triangle-submenu-start-y",`${t.top}px`),i.submenuElement.style.setProperty("--safe-triangle-submenu-end-x",`${s?t.right:t.left}px`),i.submenuElement.style.setProperty("--safe-triangle-submenu-end-y",`${t.bottom}px`)}makeSelection(i){const e=this.getTrigger();if(i.disabled)return;i.type==="checkbox"&&(i.checked=!i.checked);const t=new Nc({item:i});this.dispatchEvent(t),t.defaultPrevented||(this.open=!1,e?.focus())}async syncAriaAttributes(){const i=this.getTrigger();let e;i&&(i.localName==="wa-button"?(await customElements.whenDefined("wa-button"),await i.updateComplete,e=i.shadowRoot.querySelector('[part="base"]')):e=i,e.hasAttribute("id")||e.setAttribute("id",Rs("wa-dropdown-trigger-")),e.setAttribute("aria-haspopup","menu"),e.setAttribute("aria-expanded",this.open?"true":"false"),this.menu.setAttribute("aria-expanded","false"))}render(){let i=this.hasUpdated?this.popup.active:this.open;return y`
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
    `}};re.css=[Fn,Fc];g([G("slot:not([name])")],re.prototype,"defaultSlot",2);g([G("#menu")],re.prototype,"menu",2);g([G("wa-popup")],re.prototype,"popup",2);g([m({type:Boolean,reflect:!0})],re.prototype,"open",2);g([m({reflect:!0})],re.prototype,"size",2);g([m({reflect:!0})],re.prototype,"placement",2);g([m({type:Number})],re.prototype,"distance",2);g([m({type:Number})],re.prototype,"skidding",2);re=g([Se("wa-dropdown")],re);var Ri=class{constructor(i,...e){this.slotNames=[],this.handleSlotChange=t=>{const s=t.target;(this.slotNames.includes("[default]")&&!s.name||s.name&&this.slotNames.includes(s.name))&&this.host.requestUpdate()},(this.host=i).addController(this),this.slotNames=e}hasDefaultSlot(){return[...this.host.childNodes].some(i=>{if(i.nodeType===Node.TEXT_NODE&&i.textContent.trim()!=="")return!0;if(i.nodeType===Node.ELEMENT_NODE){const e=i;if(e.tagName.toLowerCase()==="wa-visually-hidden")return!1;if(!e.hasAttribute("slot"))return!0}return!1})}hasNamedSlot(i){return this.host.querySelector(`:scope > [slot="${i}"]`)!==null}test(i){return i==="[default]"?this.hasDefaultSlot():this.hasNamedSlot(i)}hostConnected(){this.host.shadowRoot.addEventListener("slotchange",this.handleSlotChange)}hostDisconnected(){this.host.shadowRoot.removeEventListener("slotchange",this.handleSlotChange)}};var Lc=`:host {
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
`,oe=class extends ue{constructor(){super(...arguments),this.hasSlotController=new Ri(this,"[default]","start","end"),this.active=!1,this.variant="default",this.size="medium",this.checkboxAdjacent=!1,this.submenuAdjacent=!1,this.type="normal",this.checked=!1,this.disabled=!1,this.submenuOpen=!1,this.hasSubmenu=!1,this.handleSlotChange=()=>{this.hasSubmenu=this.hasSlotController.test("submenu"),this.updateHasSubmenuState(),this.hasSubmenu?(this.setAttribute("aria-haspopup","menu"),this.setAttribute("aria-expanded",this.submenuOpen?"true":"false")):(this.removeAttribute("aria-haspopup"),this.removeAttribute("aria-expanded"))}}connectedCallback(){super.connectedCallback(),this.addEventListener("mouseenter",this.handleMouseEnter.bind(this)),this.shadowRoot.addEventListener("slotchange",this.handleSlotChange)}disconnectedCallback(){super.disconnectedCallback(),this.closeSubmenu(),this.removeEventListener("mouseenter",this.handleMouseEnter),this.shadowRoot.removeEventListener("slotchange",this.handleSlotChange)}firstUpdated(){this.setAttribute("tabindex","-1"),this.hasSubmenu=this.hasSlotController.test("submenu"),this.updateHasSubmenuState()}updated(i){i.has("active")&&(this.setAttribute("tabindex",this.active?"0":"-1"),this.customStates.set("active",this.active)),i.has("checked")&&(this.setAttribute("aria-checked",this.checked?"true":"false"),this.customStates.set("checked",this.checked)),i.has("disabled")&&(this.setAttribute("aria-disabled",this.disabled?"true":"false"),this.customStates.set("disabled",this.disabled)),i.has("type")&&(this.type==="checkbox"?this.setAttribute("role","menuitemcheckbox"):this.setAttribute("role","menuitem")),i.has("submenuOpen")&&(this.customStates.set("submenu-open",this.submenuOpen),this.submenuOpen?this.openSubmenu():this.closeSubmenu())}updateHasSubmenuState(){this.customStates.set("has-submenu",this.hasSubmenu)}async openSubmenu(){!this.hasSubmenu||!this.submenuElement||(this.notifyParentOfOpening(),this.submenuElement.showPopover(),this.submenuElement.hidden=!1,this.submenuElement.setAttribute("data-visible",""),this.submenuOpen=!0,this.setAttribute("aria-expanded","true"),await ne(this.submenuElement,"show"),setTimeout(()=>{const i=this.getSubmenuItems();i.length>0&&(i.forEach((e,t)=>e.active=t===0),i[0].focus())},0))}notifyParentOfOpening(){const i=new CustomEvent("submenu-opening",{bubbles:!0,composed:!0,detail:{item:this}});this.dispatchEvent(i);const e=this.parentElement;e&&[...e.children].filter(s=>s!==this&&s.localName==="wa-dropdown-item"&&s.getAttribute("slot")===this.getAttribute("slot")&&s.submenuOpen).forEach(s=>{s.submenuOpen=!1})}async closeSubmenu(){!this.hasSubmenu||!this.submenuElement||(this.submenuOpen=!1,this.setAttribute("aria-expanded","false"),this.submenuElement.hidden||(await ne(this.submenuElement,"hide"),this.submenuElement.hidden=!0,this.submenuElement.removeAttribute("data-visible"),this.submenuElement.hidePopover()))}getSubmenuItems(){return[...this.children].filter(i=>i.localName==="wa-dropdown-item"&&i.getAttribute("slot")==="submenu"&&!i.hasAttribute("disabled"))}handleMouseEnter(){this.hasSubmenu&&!this.disabled&&(this.notifyParentOfOpening(),this.submenuOpen=!0)}render(){return y`
      ${this.type==="checkbox"?y`
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

      ${this.hasSubmenu?y`
            <wa-icon
              id="submenu-indicator"
              part="submenu-icon"
              exportparts="svg:submenu-icon__svg"
              library="system"
              name="chevron-right"
            ></wa-icon>
          `:""}
      ${this.hasSubmenu?y`
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
    `}};oe.css=Lc;g([G("#submenu")],oe.prototype,"submenuElement",2);g([m({type:Boolean})],oe.prototype,"active",2);g([m({reflect:!0})],oe.prototype,"variant",2);g([m({reflect:!0})],oe.prototype,"size",2);g([m({attribute:"checkbox-adjacent",type:Boolean,reflect:!0})],oe.prototype,"checkboxAdjacent",2);g([m({attribute:"submenu-adjacent",type:Boolean,reflect:!0})],oe.prototype,"submenuAdjacent",2);g([m()],oe.prototype,"value",2);g([m({reflect:!0})],oe.prototype,"type",2);g([m({type:Boolean})],oe.prototype,"checked",2);g([m({type:Boolean,reflect:!0})],oe.prototype,"disabled",2);g([m({type:Boolean,reflect:!0})],oe.prototype,"submenuOpen",2);g([de()],oe.prototype,"hasSubmenu",2);oe=g([Se("wa-dropdown-item")],oe);var Oc=class extends re{static get styles(){return[re.styles,S`
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
      `]}},Ic=class extends oe{static get styles(){return[oe.styles,S`
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
      `]}};customElements.get("craft-dropdown")||customElements.define("craft-dropdown",Oc),customElements.get("craft-dropdown-item")||customElements.define("craft-dropdown-item",Ic);function Dc({el:i,uid:e}){i.setAttribute("id",`panel-${e}`),i.setAttribute("role","tabpanel"),i.setAttribute("aria-labelledby",`button-${e}`),i.hasAttribute("tabindex")||i.setAttribute("tabindex","0")}function Mc(i){i.setAttribute("selected","true")}function To(i){i.removeAttribute("selected")}function $c({el:i,uid:e,clickHandler:t,keydownHandler:s,keyupHandler:o}){i.setAttribute("id",`button-${e}`),i.setAttribute("role","tab"),i.setAttribute("aria-controls",`panel-${e}`),i.addEventListener("click",t),i.addEventListener("keyup",o),i.addEventListener("keydown",s)}function Vc({el:i,clickHandler:e,keydownHandler:t,keyupHandler:s}){i.removeAttribute("id"),i.removeAttribute("role"),i.removeAttribute("aria-controls"),i.removeEventListener("click",e),i.removeEventListener("keyup",s),i.removeEventListener("keydown",t)}function Rc(i,e=!1){e&&i.focus(),i.setAttribute("selected","true"),i.setAttribute("aria-selected","true"),i.setAttribute("tabindex","0")}function No(i){i.removeAttribute("selected"),i.setAttribute("aria-selected","false"),i.setAttribute("tabindex","-1")}function zc(i){const e=i;switch(e.key){case"ArrowDown":case"ArrowRight":case"ArrowUp":case"ArrowLeft":case"Home":case"End":e.preventDefault()}}class Pc extends P{static get properties(){return{selectedIndex:{type:Number,attribute:"selected-index",reflect:!0}}}static get styles(){return[S`
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
      `]}render(){return y`
      <div class="tabs__tab-group" role="tablist">
        <slot name="tab"></slot>
      </div>
      <div class="tabs__panels">
        <slot name="panel"></slot>
      </div>
    `}constructor(){super(),this.selectedIndex=0}firstUpdated(e){super.firstUpdated(e),this.__setupSlots(),this.tabs[0]?.disabled&&(this.selectedIndex=this.tabs.findIndex(t=>!t.disabled))}get tabs(){return Array.from(this.children).filter(e=>e.slot==="tab")}get panels(){return Array.from(this.children).filter(e=>e.slot==="panel")}static enabledWarnings=super.enabledWarnings?.filter(e=>e!=="change-in-update")||[];__setupSlots(){if(this.shadowRoot){const e=this.shadowRoot.querySelector("slot[name=tab]"),t=()=>{this.__cleanStore(),this.__setupStore(),this.__updateSelected(!1)};e&&e.addEventListener("slotchange",t)}}__setupStore(){this.__store=[],this.tabs.length!==this.panels.length&&console.warn(`The amount of tabs (${this.tabs.length}) doesn't match the amount of panels (${this.panels.length}).`),this.tabs.forEach((e,t)=>{const s=Ut(),o=this.panels[t],n={uid:s,el:e,button:e,panel:o,clickHandler:this.__createButtonClickHandler(t),keydownHandler:zc.bind(this),keyupHandler:this.__handleButtonKeyup.bind(this)};Dc({...n,el:n.panel}),$c(n),To(n.panel),No(n.button),this.__store&&this.__store.push(n)})}__cleanStore(){this.__store&&(this.__store.forEach(e=>{Vc(e)}),this.__store=[])}__getNextNotDisabledTab(e,t,s){let o=[];const n=e.filter((a,c)=>!a.disabled&&c>this.selectedIndex),r=e.filter((a,c)=>!a.disabled&&c<this.selectedIndex);return s==="right"?o=[...n,...r]:o=[...r.reverse(),...n.reverse()],o[0]}__getNextAvailableIndex(e,t){const s=this.tabs[this.selectedIndex];if(this.tabs.every(o=>!o.disabled))return e;if(t==="ArrowRight"||t==="ArrowDown"){const o=this.__getNextNotDisabledTab(this.tabs,s,"right");return this.tabs.findIndex(n=>o===n)}if(t==="ArrowLeft"||t==="ArrowUp"){const o=this.__getNextNotDisabledTab(this.tabs,s,"left");return this.tabs.findIndex(n=>o===n)}if(t==="Home")return this.tabs.findIndex(o=>!o.disabled);if(t==="End"){const o=this.tabs.map((n,r)=>({disabled:n.disabled,index:r})).filter(n=>!n.disabled);return o[o.length-1].index}return-1}__createButtonClickHandler(e){return()=>{this._setSelectedIndexWithFocus(e)}}__handleButtonKeyup(e){const t=e;if(typeof this.selectedIndex=="number")switch(t.key){case"ArrowDown":case"ArrowRight":this.selectedIndex+1>=this._pairCount?this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(0,t.key)):this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this.selectedIndex+1,t.key));break;case"ArrowUp":case"ArrowLeft":this.selectedIndex<=0?this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this._pairCount-1,t.key)):this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this.selectedIndex-1,t.key));break;case"Home":this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(0,t.key));break;case"End":this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this._pairCount-1,t.key));break}}get selectedIndex(){return this.__selectedIndex||0}set selectedIndex(e){if(e===this.__selectedIndex)return;const t=this.__selectedIndex;this.__selectedIndex=e,this.__updateSelected(!1),this.dispatchEvent(new Event("selected-changed")),this.requestUpdate("selectedIndex",t)}_setSelectedIndexWithFocus(e){if(e===-1)return;const t=this.__selectedIndex;this.__selectedIndex=e,this.__updateSelected(!0),this.dispatchEvent(new Event("selected-changed")),this.requestUpdate("selectedIndex",t)}get _pairCount(){return this.__store&&this.__store.length||0}__updateSelected(e=!1){if(!(this.__store&&typeof this.selectedIndex=="number"&&this.__store[this.selectedIndex]))return;const t=this.tabs.find(r=>r.hasAttribute("selected")),s=this.panels.find(r=>r.hasAttribute("selected"));t&&No(t),s&&To(s);const{button:o,panel:n}=this.__store[this.selectedIndex];o&&Rc(o,e),n&&Mc(n)}}var Bc=S`
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
`,Uc=class extends Pc{static get styles(){return[...super.styles,Bc]}};customElements.get("craft-tabs")||customElements.define("craft-tabs",Uc);var Hc=S`
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
`,ss=class extends P{constructor(...e){super(...e),this.label=""}render(){let e=!!this.label||!!this.querySelector('[slot="header"]')||!!this.querySelector('[slot="label"]')||!!this.querySelector('[slot="actions"]'),t=!!this.querySelector('[slot="footer"]');return y`
      <div class="card">
        <div>
          ${e?y`<div class="card__header">
                <slot name="header">
                  <slot name="label" class="card__label" part="label"
                    >${this.label}</slot
                  >
                  <slot name="actions"></slot>
                </slot>
              </div>`:j}

          <div class="card__body">
            <slot></slot>
          </div>

          ${t?y`<div class="card__footer"><slot name="footer"></slot></div>`:j}
        </div>
      </div>
    `}};ss.styles=[Hc],C([m()],ss.prototype,"label",void 0),customElements.get("craft-card")||customElements.define("craft-card",ss);var qc=S`
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
`,Fo=class extends P{render(){return y`<slot></slot> `}};Fo.styles=[qc],customElements.get("craft-tab")||customElements.define("craft-tab",Fo);class On extends un(P){static get properties(){return{checked:{type:Boolean,reflect:!0}}}static get styles(){return[S`
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
      `]}render(){return y`
      <div class="btn">
        <div class="switch-button__track"></div>
        <div class="switch-button__thumb"></div>
      </div>
    `}constructor(){super(),this.value="",this.checked=!1,this.__initialized=!1,this._toggleChecked=this._toggleChecked.bind(this),this.__handleKeydown=this._handleKeydown.bind(this),this.__handleKeyup=this._handleKeyup.bind(this)}connectedCallback(){super.connectedCallback(),this.setAttribute("role","switch"),this.setAttribute("aria-checked",`${this.checked}`),this.addEventListener("click",this._toggleChecked),this.addEventListener("keydown",this.__handleKeydown),this.addEventListener("keyup",this.__handleKeyup)}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener("click",this._toggleChecked),this.removeEventListener("keydown",this.__handleKeydown),this.removeEventListener("keyup",this.__handleKeyup)}_toggleChecked(){this.disabled||(this.focus(),this.checked=!this.checked)}__checkedStateChange(){this.dispatchEvent(new Event("checked-changed",{bubbles:!0})),this.setAttribute("aria-checked",`${this.checked}`)}_handleKeydown(e){e.key===" "&&e.preventDefault()}_handleKeyup(e){[" ","Enter"].includes(e.key)&&this._toggleChecked()}updated(e){super.updated(e),e.has("disabled")&&this.setAttribute("aria-disabled",`${this.disabled}`)}requestUpdate(e,t,s){super.requestUpdate(e,t,s),this.__initialized&&this.isConnected&&e==="checked"&&this.checked!==t&&!this.disabled&&this.__checkedStateChange()}firstUpdated(e){super.firstUpdated(e),this.__initialized=!0}}class Wc extends qt($i(jt)){static get styles(){return[...super.styles,S`
        :host([hidden]) {
          display: none;
        }

        :host([disabled]) {
          color: #adadad;
        }
      `]}static get scopedElements(){return{...super.scopedElements,"lion-switch-button":On}}get _inputNode(){return Array.from(this.children).find(e=>e.slot==="input")}get slots(){return{...super.slots,input:()=>{const e=this.createScopedElement("lion-switch-button");return e.setAttribute("data-tag-name","lion-switch-button"),e}}}render(){return y`
      <div class="form-field__group-one">${this._groupOneTemplate()}</div>
      <div class="form-field__group-two">${this._groupTwoTemplate()}</div>
    `}_groupOneTemplate(){return y`${this._labelTemplate()} ${this._helpTextTemplate()} ${this._feedbackTemplate()}`}_groupTwoTemplate(){return y`${this._inputGroupTemplate()}`}constructor(){super(),this.checked=!1,this.__handleButtonSwitchCheckedChanged=this.__handleButtonSwitchCheckedChanged.bind(this)}connectedCallback(){super.connectedCallback(),this.addEventListener("checked-changed",this.__handleButtonSwitchCheckedChanged),this._labelNode&&this._labelNode.addEventListener("click",this._toggleChecked),this._syncButtonSwitch()}disconnectedCallback(){super.disconnectedCallback(),this._inputNode&&this.removeEventListener("checked-changed",this.__handleButtonSwitchCheckedChanged),this._labelNode&&this._labelNode.removeEventListener("click",this._toggleChecked)}updated(e){super.updated(e),e.has("disabled")&&this._syncButtonSwitch()}_toggleChecked(e){e.preventDefault(),super._toggleChecked(e)}_isEmpty(){return!1}__handleButtonSwitchCheckedChanged(e){e.stopPropagation(),this._isHandlingUserInput=!0,this.checked=this._inputNode.checked,this._isHandlingUserInput=!1}_syncButtonSwitch(){this._inputNode.disabled=this.disabled}_onLabelClick(){this.disabled||this._inputNode.focus()}}var In=class extends On{static get styles(){return[...super.styles,S`
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
      `]}};customElements.get("craft-switch-button")||customElements.define("craft-switch-button",In);var jc=S`
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
`,Kc=class extends Wc{static get styles(){return[...super.styles,Li,jc]}get slots(){return{...super.slots,input:()=>{let e=this.createScopedElement("craft-switch-button");return e.setAttribute("data-tag-name","craft-switch-button"),e}}}static get scopedElements(){return{...super.scopedElements,"craft-switch-button":In}}};customElements.get("craft-switch")||customElements.define("craft-switch",Kc);var Gc=S`
  .breadcrumbs {
    display: flex;
    align-items: center;
  }
`,Be=class extends P{constructor(...e){super(...e),this.label="",this.items=[],this.visibleItems=0,this.firstRender=!0}getSeparator(){let e=this.separatorSlot.assignedElements({flatten:!0})[0].cloneNode(!0);return[e,...e.querySelectorAll("[id]")].forEach(t=>t.removeAttribute("id")),e.setAttribute("data-default",""),e.slot="separator",e}calculateBreadcrumbItemsWidth(){this.items=this.breadcrumbsElements.map((e,t)=>{let s=e.offsetWidth;return e.hasAttribute("hidden")&&(e.removeAttribute("hidden"),s=e.offsetWidth,e.setAttribute("hidden","")),{label:e.innerText,href:e.href,value:t.toString(),offsetWidth:s,isVisible:!0}})}async handleSlotChange(){let e=[...this.defaultSlot.assignedElements({flatten:!0})].filter(t=>t.tagName.toLowerCase()==="craft-breadcrumb-item");if(e.forEach((t,s)=>{let o=t.querySelector('[slot="separator"]');o===null?t.append(this.getSeparator()):o.hasAttribute("data-default")&&o.replaceWith(this.getSeparator()),s===e.length-1?t.setAttribute("aria-current","page"):t.removeAttribute("aria-current")}),this.breadcrumbsElements.length===0){this.items=[],this.visibleItems=0;return}await Promise.all(this.breadcrumbsElements.map(t=>t.updateComplete)),this.calculateBreadcrumbItemsWidth(),this.visibleItems=0,this.adjustOverflow()}connectedCallback(){super.connectedCallback(),this.hasAttribute("role")||this.setAttribute("role","navigation"),this.resizeObserver=new ResizeObserver(()=>{if(this.firstRender){this.firstRender=!1;return}this.adjustOverflow()}),this.resizeObserver.observe(this)}adjustOverflow(){let e=this.getBoundingClientRect().width;console.log({availableSpace:e})}disconnectedCallback(){this.resizeObserver?.unobserve(this),super.disconnectedCallback()}render(){return y`
      <nav class="breadcrumbs" aria-label="${this.label}">
        <slot @slotchange="${this.handleSlotChange}"></slot>
      </nav>

      <span hidden aria-hidden="true">
        <slot name="separator"><span class="separator">/</span></slot>
      </span>
    `}};Be.styles=[Gc],C([G("slot")],Be.prototype,"defaultSlot",void 0),C([G('slot[name="separator"]')],Be.prototype,"separatorSlot",void 0),C([oi({selector:"craft-breadcrumb-item"})],Be.prototype,"breadcrumbsElements",void 0),C([m()],Be.prototype,"label",void 0),C([de()],Be.prototype,"items",void 0),C([de()],Be.prototype,"visibleItems",void 0),customElements.get("craft-breadcrumbs")||customElements.define("craft-breadcrumbs",Be);var Yc=`:host {
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
`,Ce=class extends ue{constructor(){super(...arguments),this.renderType="button",this.rel="noreferrer noopener"}setRenderType(){const i=this.defaultSlot.assignedElements({flatten:!0}).filter(e=>e.tagName.toLowerCase()==="wa-dropdown").length>0;if(this.href){this.renderType="link";return}if(i){this.renderType="dropdown";return}this.renderType="button"}hrefChanged(){this.setRenderType()}handleSlotChange(){this.setRenderType()}render(){return y`
      <span part="start" class="start">
        <slot name="start"></slot>
      </span>

      ${this.renderType==="link"?y`
            <a
              part="label"
              class="label label-link"
              href="${this.href}"
              target="${pe(this.target?this.target:void 0)}"
              rel=${pe(this.target?this.rel:void 0)}
            >
              <slot></slot>
            </a>
          `:""}
      ${this.renderType==="button"?y`
            <button part="label" type="button" class="label label-button">
              <slot @slotchange=${this.handleSlotChange}></slot>
            </button>
          `:""}
      ${this.renderType==="dropdown"?y`
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
    `}};Ce.css=Yc;g([G("slot:not([name])")],Ce.prototype,"defaultSlot",2);g([de()],Ce.prototype,"renderType",2);g([m()],Ce.prototype,"href",2);g([m()],Ce.prototype,"target",2);g([m()],Ce.prototype,"rel",2);g([ge("href",{waitUntilFirstUpdate:!0})],Ce.prototype,"hrefChanged",1);Ce=g([Se("wa-breadcrumb-item")],Ce);var Zc=class extends Ce{static get styles(){return[Ce.styles,S`
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
      `]}};customElements.get("craft-breadcrumb-item")||customElements.define("craft-breadcrumb-item",Zc);var Xc=`:host {
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
`,os=new Set,ie=class extends ue{constructor(){super(...arguments),this.anchor=null,this.placement="top",this.open=!1,this.distance=8,this.skidding=0,this.for=null,this.withoutArrow=!1,this.eventController=new AbortController,this.handleAnchorClick=()=>{this.open=!this.open},this.handleBodyClick=i=>{i.target.closest('[data-popover="close"]')&&(i.stopPropagation(),this.open=!1)},this.handleDocumentKeyDown=i=>{i.key==="Escape"&&(i.preventDefault(),this.open=!1,this.anchor&&typeof this.anchor.focus=="function"&&this.anchor.focus())},this.handleDocumentClick=i=>{const e=i.target;this.anchor&&i.composedPath().includes(this.anchor)||e.closest("wa-popover")!==this&&(this.open=!1)}}connectedCallback(){super.connectedCallback(),this.id||(this.id=Rs("wa-popover-"))}disconnectedCallback(){super.disconnectedCallback(),document.removeEventListener("keydown",this.handleDocumentKeyDown),this.eventController.abort()}firstUpdated(){this.open&&(this.dialog.show(),this.popup.active=!0,this.popup.reposition())}updated(i){i.has("open")&&this.customStates.set("open",this.open)}async handleOpenChange(){if(this.open){const i=new Pt;if(this.dispatchEvent(i),i.defaultPrevented){this.open=!1;return}os.forEach(e=>e.open=!1),document.addEventListener("keydown",this.handleDocumentKeyDown,{signal:this.eventController.signal}),document.addEventListener("click",this.handleDocumentClick,{signal:this.eventController.signal}),this.dialog.show(),this.popup.active=!0,os.add(this),requestAnimationFrame(()=>{const e=this.querySelector("[autofocus]");e&&typeof e.focus=="function"?e.focus():this.dialog.focus()}),await ne(this.popup.popup,"show-with-scale"),this.popup.reposition(),this.dispatchEvent(new Rt)}else{const i=new zt;if(this.dispatchEvent(i),i.defaultPrevented){this.open=!0;return}document.removeEventListener("keydown",this.handleDocumentKeyDown),document.removeEventListener("click",this.handleDocumentClick),os.delete(this),await ne(this.popup.popup,"hide-with-scale"),this.popup.active=!1,this.dialog.close(),this.dispatchEvent(new Vt)}}handleForChange(){const i=this.getRootNode();if(!i)return;const e=this.for?i.getElementById(this.for):null,t=this.anchor;if(e===t)return;const{signal:s}=this.eventController;e&&e.addEventListener("click",this.handleAnchorClick,{signal:s}),t&&t.removeEventListener("click",this.handleAnchorClick),this.anchor=e,this.for&&!e&&console.warn(`A popover was assigned to an element with an ID of "${this.for}" but the element could not be found.`,this)}async handleOptionsChange(){this.hasUpdated&&(await this.updateComplete,this.popup.reposition())}async show(){if(!this.open)return this.open=!0,bi(this,"wa-after-show")}async hide(){if(this.open)return this.open=!1,bi(this,"wa-after-hide")}render(){return y`
      <dialog part="dialog" class="dialog">
        <wa-popup
          part="popup"
          exportparts="
            popup:popup__popup,
            arrow:popup__arrow
          "
          class=${le({popover:!0,"popover-open":this.open})}
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
    `}};ie.css=Xc;ie.dependencies={"wa-popup":q};g([G("dialog")],ie.prototype,"dialog",2);g([G(".body")],ie.prototype,"body",2);g([G("wa-popup")],ie.prototype,"popup",2);g([de()],ie.prototype,"anchor",2);g([m()],ie.prototype,"placement",2);g([m({type:Boolean,reflect:!0})],ie.prototype,"open",2);g([m({type:Number})],ie.prototype,"distance",2);g([m({type:Number})],ie.prototype,"skidding",2);g([m()],ie.prototype,"for",2);g([m({attribute:"without-arrow",type:Boolean,reflect:!0})],ie.prototype,"withoutArrow",2);g([ge("open",{waitUntilFirstUpdate:!0})],ie.prototype,"handleOpenChange",1);g([ge("for")],ie.prototype,"handleForChange",1);g([ge(["distance","placement","skidding"])],ie.prototype,"handleOptionsChange",1);ie=g([Se("wa-popover")],ie);var Qc=class extends ie{static get styles(){return[ie.styles,S`
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
      `]}};customElements.get("craft-popover")||customElements.define("craft-popover",Qc);var Jc=S`
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
`,Ge=class extends P{constructor(){super(),this.altText=null,this.badgeCount=null,this.badgeCountSuffix=null,this.variant="primary",this.id=this.id||`badge-${Math.floor(Math.random()*1e9).toString()}`}showCount(){return this.badgeCount!==null&&this.badgeCount>0}truncatedNumber(){if(this.showCount)return this.badgeCount>99?"99+":this.badgeCount.toString()}getBadgeRole(){return this.altText?"img":j}getLabelId(){return`${this.id}-label`}renderBadgeContents(){return y`
      ${this.showCount()?y`
            <span class="number">${this.truncatedNumber()}</span>
            <sl-visually-hidden>${this.badgeCountSuffix}</sl-visually-hidden>
          `:j}
      ${this.altText?y`
            <sl-visually-hidden id=${this.getLabelId()}
              >${this.altText}</sl-visually-hidden
            >
          `:j}
    `}render(){return y`
      <div
        part="badge"
        id=${this.id}
        class="${le({"badge-indicator":!0,"badge-indicator--with-number":this.showCount(),"badge-indicator--secondary":this.variant==="secondary","badge-indicator--inverse":this.variant==="inverse"})}"
        role="${this.getBadgeRole()}"
        aria-labelledby="${this.altText?this.getLabelId():j}"
      >
        ${this.renderBadgeContents()}
      </div>
    `}};Ge.styles=[Jc],C([m()],Ge.prototype,"altText",void 0),C([m()],Ge.prototype,"badgeCount",void 0),C([m()],Ge.prototype,"badgeCountSuffix",void 0),C([m()],Ge.prototype,"variant",void 0),C([m()],Ge.prototype,"id",void 0),customElements.get("craft-badge-indicator")||customElements.define("craft-badge-indicator",Ge);const Dn="important",ed=" !"+Dn,td=Ci(class extends ki{constructor(i){if(super(i),i.type!==Ei.ATTRIBUTE||i.name!=="style"||i.strings?.length>2)throw Error("The `styleMap` directive must be used in the `style` attribute and must be the only part in the attribute.")}render(i){return Object.keys(i).reduce(((e,t)=>{const s=i[t];return s==null?e:e+`${t=t.includes("-")?t:t.replace(/(?:^(webkit|moz|ms|o)|)(?=[A-Z])/g,"-$&").toLowerCase()}:${s};`}),"")}update(i,[e]){const{style:t}=i.element;if(this.ft===void 0)return this.ft=new Set(Object.keys(e)),this.render(e);for(const s of this.ft)e[s]==null&&(this.ft.delete(s),s.includes("-")?t.removeProperty(s):t[s]=null);for(const s in e){const o=e[s];if(o!=null){this.ft.add(s);const n=typeof o=="string"&&o.endsWith(ed);s.includes("-")||n?t.setProperty(s,n?o.slice(0,-11):o,n?Dn:""):t[s]=o}}return Dt}});var id=S`
  .nav-item {
    display: grid;
    gap: var(--c-spacing-md);
    grid-template-columns: 1fr auto;
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
`,ye=class extends P{constructor(){super(),this.active=!1,this.external=!1,this.indicator=!1,this.iconOnly=!1,this.subnavState="closed",this.id=this.id||Math.random().toString(36).substring(2,6)}connectedCallback(){super.connectedCallback(),this.subnavState=this.active?"open":"closed"}toggleSubnav(i){i.preventDefault(),i.stopPropagation(),this.subnavState=this.subnavState==="open"?"closed":"open"}renderIconItem(i){let e=`item-${this.id}`;return y`
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
    `}renderPrefix(){return y`
      <span class="nav-item__prefix">
        <slot name="prefix">
          <slot name="icon">
            ${this.icon?y` <craft-icon
                  name="${this.icon}"
                  class="nav-icon"
                ></craft-icon>`:j}
          </slot>
          ${this.indicator?y`<craft-badge-indicator
                altText="${tu("Has Notifications")}"
              />`:j}
        </slot>
      </span>
    `}renderSuffix(i=!1){return y`
      <div class="nav-item__suffix">
        <slot name="suffix">
          ${i?y`
                <craft-button
                  @click="${this.toggleSubnav}"
                  icon
                  size="small"
                  aria-controls="${this.id}-subnav"
                  aria-expanded="${this.subnavState==="open"?"true":"false"}"
                >
                  <craft-icon
                    name="${this.subnavState==="closed"?"chevron-down":"chevron-up"}"
                    style="font-size: calc(10rem / 16)"
                  ></craft-icon>
                </craft-button>
              `:j}
        </slot>
      </div>
    `}renderItem(i,e=!1){return y`
      <a
        class="${le({"nav-item":!0,"nav-item--prefixed":e})}"
        href="${this.href}"
        aria-current="${this.active?"page":!1}"
      >
        ${e?this.renderPrefix():j}
        <slot></slot>
        ${this.renderSuffix(i)}
      </a>
    `}render(){let i=!!this.querySelector('[slot="subnav"]'),e=!!this.icon||!!this.querySelector('[slot="prefix"]')||!!this.querySelector('[slot="icon"]');return y`
      <li>
        ${this.iconOnly?this.renderIconItem(i):this.renderItem(i,e)}
        ${i?y`
              <div
                class="subnav"
                id="${this.id}-subnav"
                style="${td({display:this.subnavState==="open"?"block":"none"})}"
              >
                <slot name="subnav"></slot>
              </div>
            `:j}
      </li>
    `}};ye.styles=id,C([m()],ye.prototype,"icon",void 0),C([m()],ye.prototype,"href",void 0),C([m({type:Boolean,reflect:!0})],ye.prototype,"active",void 0),C([m({type:Boolean})],ye.prototype,"external",void 0),C([m({type:Boolean})],ye.prototype,"indicator",void 0),C([m()],ye.prototype,"id",void 0),C([m({reflect:!0,type:Boolean,attribute:"icon-only"})],ye.prototype,"iconOnly",void 0),C([de()],ye.prototype,"subnavState",void 0),customElements.get("craft-nav-item")||customElements.define("craft-nav-item",ye);var Cs=new Set;function sd(){const i=document.documentElement.clientWidth;return Math.abs(window.innerWidth-i)}function od(){const i=Number(getComputedStyle(document.body).paddingRight.replace(/px/,""));return isNaN(i)||!i?0:i}function wi(i){if(Cs.add(i),!document.documentElement.classList.contains("wa-scroll-lock")){const e=sd()+od();let t=getComputedStyle(document.documentElement).scrollbarGutter;(!t||t==="auto")&&(t="stable"),e<2&&(t=""),document.documentElement.style.setProperty("--wa-scroll-lock-gutter",t),document.documentElement.classList.add("wa-scroll-lock"),document.documentElement.style.setProperty("--wa-scroll-lock-size",`${e}px`)}}function xi(i){Cs.delete(i),Cs.size===0&&(document.documentElement.classList.remove("wa-scroll-lock"),document.documentElement.style.removeProperty("--wa-scroll-lock-size"))}function Mn(i){return i.split(" ").map(e=>e.trim()).filter(e=>e!=="")}var nd=`:host {
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
`,ve=class extends ue{constructor(){super(...arguments),this.localize=new ft(this),this.hasSlotController=new Ri(this,"footer","header-actions","label"),this.open=!1,this.label="",this.placement="end",this.withoutHeader=!1,this.lightDismiss=!0,this.handleDocumentKeyDown=i=>{i.key==="Escape"&&this.open&&(i.preventDefault(),i.stopPropagation(),this.requestClose(this.drawer))}}firstUpdated(){this.open&&(this.addOpenListeners(),this.drawer.showModal(),wi(this))}disconnectedCallback(){super.disconnectedCallback(),xi(this),this.removeOpenListeners()}async requestClose(i){const e=new zt({source:i});if(this.dispatchEvent(e),e.defaultPrevented){this.open=!0,ne(this.drawer,"pulse");return}this.removeOpenListeners(),await ne(this.drawer,"hide"),this.open=!1,this.drawer.close(),xi(this);const t=this.originalTrigger;typeof t?.focus=="function"&&setTimeout(()=>t.focus()),this.dispatchEvent(new Vt)}addOpenListeners(){document.addEventListener("keydown",this.handleDocumentKeyDown)}removeOpenListeners(){document.removeEventListener("keydown",this.handleDocumentKeyDown)}handleDialogCancel(i){i.preventDefault(),!this.drawer.classList.contains("hide")&&i.target===this.drawer&&this.requestClose(this.drawer)}handleDialogClick(i){const t=i.target.closest('[data-drawer="close"]');t&&(i.stopPropagation(),this.requestClose(t))}async handleDialogPointerDown(i){i.target===this.drawer&&(this.lightDismiss?this.requestClose(this.drawer):await ne(this.drawer,"pulse"))}handleOpenChange(){this.open&&!this.drawer.open?this.show():this.drawer.open&&(this.open=!0,this.requestClose(this.drawer))}async show(){const i=new Pt;if(this.dispatchEvent(i),i.defaultPrevented){this.open=!1;return}this.addOpenListeners(),this.originalTrigger=document.activeElement,this.open=!0,this.drawer.showModal(),wi(this),requestAnimationFrame(()=>{const e=this.querySelector("[autofocus]");e&&typeof e.focus=="function"?e.focus():this.drawer.focus()}),await ne(this.drawer,"show"),this.dispatchEvent(new Rt)}render(){const i=!this.withoutHeader,e=this.hasSlotController.test("footer");return y`
      <dialog
        part="dialog"
        class=${le({drawer:!0,open:this.open,top:this.placement==="top",end:this.placement==="end",bottom:this.placement==="bottom",start:this.placement==="start"})}
        @cancel=${this.handleDialogCancel}
        @click=${this.handleDialogClick}
        @pointerdown=${this.handleDialogPointerDown}
      >
        ${i?y`
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

        ${e?y`
              <footer part="footer" class="footer">
                <slot name="footer"></slot>
              </footer>
            `:""}
      </dialog>
    `}};ve.css=nd;g([G(".drawer")],ve.prototype,"drawer",2);g([m({type:Boolean,reflect:!0})],ve.prototype,"open",2);g([m({reflect:!0})],ve.prototype,"label",2);g([m({reflect:!0})],ve.prototype,"placement",2);g([m({attribute:"without-header",type:Boolean,reflect:!0})],ve.prototype,"withoutHeader",2);g([m({attribute:"light-dismiss",type:Boolean})],ve.prototype,"lightDismiss",2);g([ge("open",{waitUntilFirstUpdate:!0})],ve.prototype,"handleOpenChange",1);ve=g([Se("wa-drawer")],ve);document.addEventListener("click",i=>{const e=i.target.closest("[data-drawer]");if(e instanceof Element){const[t,s]=Mn(e.getAttribute("data-drawer")||"");if(t==="open"&&s?.length){const n=e.getRootNode().getElementById(s);n?.localName==="wa-drawer"?n.open=!0:console.warn(`A drawer with an ID of "${s}" could not be found in this document.`)}}});document.body.addEventListener("pointerdown",()=>{});var rd=()=>({checkValidity(i){const e=i.input,t={message:"",isValid:!0,invalidKeys:[]};if(!e)return t;let s=!0;if("checkValidity"in e&&(s=e.checkValidity()),s)return t;if(t.isValid=!1,"validationMessage"in e&&(t.message=e.validationMessage),!("validity"in e))return t.invalidKeys.push("customError"),t;for(const o in e.validity){if(o==="valid")continue;const n=o;e.validity[n]&&t.invalidKeys.push(n)}return t}});var $n=class extends Event{constructor(){super("wa-invalid",{bubbles:!0,cancelable:!1,composed:!0})}},ad=()=>({observedAttributes:["custom-error"],checkValidity(i){const e={message:"",isValid:!0,invalidKeys:[]};return i.customError&&(e.message=i.customError,e.isValid=!1,e.invalidKeys=["customError"]),e}}),De=class extends ue{constructor(){super(),this.name=null,this.disabled=!1,this.required=!1,this.assumeInteractionOn=["input"],this.validators=[],this.valueHasChanged=!1,this.hasInteracted=!1,this.customError=null,this.emittedEvents=[],this.emitInvalid=i=>{i.target===this&&(this.hasInteracted=!0,this.dispatchEvent(new $n))},this.handleInteraction=i=>{const e=this.emittedEvents;e.includes(i.type)||e.push(i.type),e.length===this.assumeInteractionOn?.length&&(this.hasInteracted=!0)},this.addEventListener("invalid",this.emitInvalid)}static get validators(){return[ad()]}static get observedAttributes(){const i=new Set(super.observedAttributes||[]);for(const e of this.validators)if(e.observedAttributes)for(const t of e.observedAttributes)i.add(t);return[...i]}connectedCallback(){super.connectedCallback(),this.updateValidity(),this.assumeInteractionOn.forEach(i=>{this.addEventListener(i,this.handleInteraction)})}firstUpdated(...i){super.firstUpdated(...i),this.updateValidity()}willUpdate(i){if(i.has("customError")&&(this.customError||(this.customError=null),this.setCustomValidity(this.customError||"")),i.has("value")||i.has("disabled")){const e=this.value;if(Array.isArray(e)){if(this.name){const t=new FormData;for(const s of e)t.append(this.name,s);this.setValue(t,t)}}else this.setValue(e,e)}i.has("disabled")&&(this.customStates.set("disabled",this.disabled),(this.hasAttribute("disabled")||!this.matches(":disabled"))&&this.toggleAttribute("disabled",this.disabled)),this.updateValidity(),super.willUpdate(i)}get labels(){return this.internals.labels}getForm(){return this.internals.form}get validity(){return this.internals.validity}get willValidate(){return this.internals.willValidate}get validationMessage(){return this.internals.validationMessage}checkValidity(){return this.updateValidity(),this.internals.checkValidity()}reportValidity(){return this.updateValidity(),this.hasInteracted=!0,this.internals.reportValidity()}get validationTarget(){return this.input||void 0}setValidity(...i){const e=i[0],t=i[1];let s=i[2];s||(s=this.validationTarget),this.internals.setValidity(e,t,s||void 0),this.requestUpdate("validity"),this.setCustomStates()}setCustomStates(){const i=!!this.required,e=this.internals.validity.valid,t=this.hasInteracted;this.customStates.set("required",i),this.customStates.set("optional",!i),this.customStates.set("invalid",!e),this.customStates.set("valid",e),this.customStates.set("user-invalid",!e&&t),this.customStates.set("user-valid",e&&t)}setCustomValidity(i){if(!i){this.customError=null,this.setValidity({});return}this.customError=i,this.setValidity({customError:!0},i,this.validationTarget)}formResetCallback(){this.resetValidity(),this.hasInteracted=!1,this.valueHasChanged=!1,this.emittedEvents=[],this.updateValidity()}formDisabledCallback(i){this.disabled=i,this.updateValidity()}formStateRestoreCallback(i,e){this.value=i,e==="restore"&&this.resetValidity(),this.updateValidity()}setValue(...i){const[e,t]=i;this.internals.setFormValue(e,t)}get allValidators(){const i=this.constructor.validators||[],e=this.validators||[];return[...i,...e]}resetValidity(){this.setCustomValidity(""),this.setValidity({})}updateValidity(){if(this.disabled||this.hasAttribute("disabled")||!this.willValidate){this.resetValidity();return}const i=this.allValidators;if(!i?.length)return;const e={customError:!!this.customError},t=this.validationTarget||this.input||void 0;let s="";for(const o of i){const{isValid:n,message:r,invalidKeys:a}=o.checkValidity(this);n||(s||(s=r),a?.length>=0&&a.forEach(c=>e[c]=!0))}s||(s=this.validationMessage),this.setValidity(e,s,t)}};De.formAssociated=!0;g([m({reflect:!0})],De.prototype,"name",2);g([m({type:Boolean})],De.prototype,"disabled",2);g([m({state:!0,attribute:!1})],De.prototype,"valueHasChanged",2);g([m({state:!0,attribute:!1})],De.prototype,"hasInteracted",2);g([m({attribute:"custom-error",reflect:!0})],De.prototype,"customError",2);g([m({attribute:!1,state:!0,type:Object})],De.prototype,"validity",1);var ld=`@layer wa-utilities {
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
`;const Vn=Symbol.for(""),cd=i=>{if(i?.r===Vn)return i?._$litStatic$},Lo=(i,...e)=>({_$litStatic$:e.reduce(((t,s,o)=>t+(n=>{if(n._$litStatic$!==void 0)return n._$litStatic$;throw Error(`Value passed to 'literal' function must be a 'literal' result: ${n}. Use 'unsafeStatic' to pass non-literal values, but
            take care to ensure page security.`)})(s)+i[o+1]),i[0]),r:Vn}),Oo=new Map,dd=i=>(e,...t)=>{const s=t.length;let o,n;const r=[],a=[];let c,u=0,h=!1;for(;u<s;){for(c=e[u];u<s&&(n=t[u],(o=cd(n))!==void 0);)c+=o+e[++u],h=!0;u!==s&&a.push(n),r.push(c),u++}if(u===s&&r.push(e[s]),h){const b=r.join("$$lit$$");(e=Oo.get(b))===void 0&&(r.raw=r,Oo.set(b,e=r)),t=a}return i(e,...t)},ns=dd(y);var ud=`@layer wa-component {
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
`,z=class extends De{constructor(){super(...arguments),this.assumeInteractionOn=["click"],this.hasSlotController=new Ri(this,"[default]","start","end"),this.localize=new ft(this),this.invalid=!1,this.isIconButton=!1,this.title="",this.variant="neutral",this.appearance="accent",this.size="medium",this.withCaret=!1,this.disabled=!1,this.loading=!1,this.pill=!1,this.type="button",this.form=null}static get validators(){return[...super.validators,rd()]}constructLightDOMButton(){const i=document.createElement("button");return i.type=this.type,i.style.position="absolute",i.style.width="0",i.style.height="0",i.style.clipPath="inset(50%)",i.style.overflow="hidden",i.style.whiteSpace="nowrap",this.name&&(i.name=this.name),i.value=this.value||"",["form","formaction","formenctype","formmethod","formnovalidate","formtarget"].forEach(e=>{this.hasAttribute(e)&&i.setAttribute(e,this.getAttribute(e))}),i}handleClick(){if(!this.getForm())return;const e=this.constructLightDOMButton();this.parentElement?.append(e),e.click(),e.remove()}handleInvalid(){this.dispatchEvent(new $n)}handleLabelSlotChange(){const i=this.labelSlot.assignedNodes({flatten:!0});let e=!1,t=!1,s=!1,o=!1;[...i].forEach(n=>{if(n.nodeType===Node.ELEMENT_NODE){const r=n;r.localName==="wa-icon"?(t=!0,e||(e=r.label!==void 0)):o=!0}else n.nodeType===Node.TEXT_NODE&&(n.textContent?.trim()||"").length>0&&(s=!0)}),this.isIconButton=t&&!s&&!o,this.isIconButton&&!e&&console.warn('Icon buttons must have a label for screen readers. Add <wa-icon label="..."> to remove this warning.',this)}isButton(){return!this.href}isLink(){return!!this.href}handleDisabledChange(){this.updateValidity()}setValue(...i){}click(){this.button.click()}focus(i){this.button.focus(i)}blur(){this.button.blur()}render(){const i=this.isLink(),e=i?Lo`a`:Lo`button`;return ns`
      <${e}
        part="base"
        class=${le({button:!0,caret:this.withCaret,disabled:this.disabled,loading:this.loading,rtl:this.localize.dir()==="rtl","has-label":this.hasSlotController.test("[default]"),"has-start":this.hasSlotController.test("start"),"has-end":this.hasSlotController.test("end"),"is-icon-button":this.isIconButton})}
        ?disabled=${pe(i?void 0:this.disabled)}
        type=${pe(i?void 0:this.type)}
        title=${this.title}
        name=${pe(i?void 0:this.name)}
        value=${pe(i?void 0:this.value)}
        href=${pe(i?this.href:void 0)}
        target=${pe(i?this.target:void 0)}
        download=${pe(i?this.download:void 0)}
        rel=${pe(i&&this.rel?this.rel:void 0)}
        role=${pe(i?void 0:"button")}
        aria-disabled=${this.disabled?"true":"false"}
        tabindex=${this.disabled?"-1":"0"}
        @invalid=${this.isButton()?this.handleInvalid:null}
        @click=${this.handleClick}
      >
        <slot name="start" part="start" class="start"></slot>
        <slot part="label" class="label" @slotchange=${this.handleLabelSlotChange}></slot>
        <slot name="end" part="end" class="end"></slot>
        ${this.withCaret?ns`
                <wa-icon part="caret" class="caret" library="system" name="chevron-down" variant="solid"></wa-icon>
              `:""}
        ${this.loading?ns`<wa-spinner part="spinner"></wa-spinner>`:""}
      </${e}>
    `}};z.shadowRootOptions={...De.shadowRootOptions,delegatesFocus:!0};z.css=[ud,ld,Fn];g([G(".button")],z.prototype,"button",2);g([G("slot:not([name])")],z.prototype,"labelSlot",2);g([de()],z.prototype,"invalid",2);g([de()],z.prototype,"isIconButton",2);g([m()],z.prototype,"title",2);g([m({reflect:!0})],z.prototype,"variant",2);g([m({reflect:!0})],z.prototype,"appearance",2);g([m({reflect:!0})],z.prototype,"size",2);g([m({attribute:"with-caret",type:Boolean,reflect:!0})],z.prototype,"withCaret",2);g([m({type:Boolean})],z.prototype,"disabled",2);g([m({type:Boolean,reflect:!0})],z.prototype,"loading",2);g([m({type:Boolean,reflect:!0})],z.prototype,"pill",2);g([m()],z.prototype,"type",2);g([m({reflect:!0})],z.prototype,"name",2);g([m({reflect:!0})],z.prototype,"value",2);g([m({reflect:!0})],z.prototype,"href",2);g([m()],z.prototype,"target",2);g([m()],z.prototype,"rel",2);g([m()],z.prototype,"download",2);g([m({reflect:!0})],z.prototype,"form",2);g([m({attribute:"formaction"})],z.prototype,"formAction",2);g([m({attribute:"formenctype"})],z.prototype,"formEnctype",2);g([m({attribute:"formmethod"})],z.prototype,"formMethod",2);g([m({attribute:"formnovalidate",type:Boolean})],z.prototype,"formNoValidate",2);g([m({attribute:"formtarget"})],z.prototype,"formTarget",2);g([ge("disabled",{waitUntilFirstUpdate:!0})],z.prototype,"handleDisabledChange",1);z=g([Se("wa-button")],z);var hd=`:host {
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
`,ks=class extends ue{constructor(){super(...arguments),this.localize=new ft(this)}render(){return y`
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
    `}};ks.css=hd;ks=g([Se("wa-spinner")],ks);var pd=class extends ve{static get styles(){return[ve.styles,S`
        :host {
          --wa-color-surface-raised: var(--c-bg-raised);
          --spacing: var(--c-spacing-lg);
          background-color: red;
        }
      `]}};customElements.get("craft-drawer")||customElements.define("craft-drawer",pd);var fd=`:host {
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
`,ke=class extends ue{constructor(){super(...arguments),this.localize=new ft(this),this.hasSlotController=new Ri(this,"footer","header-actions","label"),this.open=!1,this.label="",this.withoutHeader=!1,this.lightDismiss=!1,this.handleDocumentKeyDown=i=>{i.key==="Escape"&&this.open&&(i.preventDefault(),i.stopPropagation(),this.requestClose(this.dialog))}}firstUpdated(){this.open&&(this.addOpenListeners(),this.dialog.showModal(),wi(this))}disconnectedCallback(){super.disconnectedCallback(),xi(this),this.removeOpenListeners()}async requestClose(i){const e=new zt({source:i});if(this.dispatchEvent(e),e.defaultPrevented){this.open=!0,ne(this.dialog,"pulse");return}this.removeOpenListeners(),await ne(this.dialog,"hide"),this.open=!1,this.dialog.close(),xi(this);const t=this.originalTrigger;typeof t?.focus=="function"&&setTimeout(()=>t.focus()),this.dispatchEvent(new Vt)}addOpenListeners(){document.addEventListener("keydown",this.handleDocumentKeyDown)}removeOpenListeners(){document.removeEventListener("keydown",this.handleDocumentKeyDown)}handleDialogCancel(i){i.preventDefault(),!this.dialog.classList.contains("hide")&&i.target===this.dialog&&this.requestClose(this.dialog)}handleDialogClick(i){const t=i.target.closest('[data-dialog="close"]');t&&(i.stopPropagation(),this.requestClose(t))}async handleDialogPointerDown(i){i.target===this.dialog&&(this.lightDismiss?this.requestClose(this.dialog):await ne(this.dialog,"pulse"))}handleOpenChange(){this.open&&!this.dialog.open?this.show():!this.open&&this.dialog.open&&(this.open=!0,this.requestClose(this.dialog))}async show(){const i=new Pt;if(this.dispatchEvent(i),i.defaultPrevented){this.open=!1;return}this.addOpenListeners(),this.originalTrigger=document.activeElement,this.open=!0,this.dialog.showModal(),wi(this),requestAnimationFrame(()=>{const e=this.querySelector("[autofocus]");e&&typeof e.focus=="function"?e.focus():this.dialog.focus()}),await ne(this.dialog,"show"),this.dispatchEvent(new Rt)}render(){const i=!this.withoutHeader,e=this.hasSlotController.test("footer");return y`
      <dialog
        part="dialog"
        class=${le({dialog:!0,open:this.open})}
        @cancel=${this.handleDialogCancel}
        @click=${this.handleDialogClick}
        @pointerdown=${this.handleDialogPointerDown}
      >
        ${i?y`
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

        ${e?y`
              <footer part="footer" class="footer">
                <slot name="footer"></slot>
              </footer>
            `:""}
      </dialog>
    `}};ke.css=fd;g([G(".dialog")],ke.prototype,"dialog",2);g([m({type:Boolean,reflect:!0})],ke.prototype,"open",2);g([m({reflect:!0})],ke.prototype,"label",2);g([m({attribute:"without-header",type:Boolean,reflect:!0})],ke.prototype,"withoutHeader",2);g([m({attribute:"light-dismiss",type:Boolean})],ke.prototype,"lightDismiss",2);g([ge("open",{waitUntilFirstUpdate:!0})],ke.prototype,"handleOpenChange",1);ke=g([Se("wa-dialog")],ke);document.addEventListener("click",i=>{const e=i.target.closest("[data-dialog]");if(e instanceof Element){const[t,s]=Mn(e.getAttribute("data-dialog")||"");if(t==="open"&&s?.length){const n=e.getRootNode().getElementById(s);n?.localName==="wa-dialog"?n.open=!0:console.warn(`A dialog with an ID of "${s}" could not be found in this document.`)}}});document.addEventListener("pointerdown",()=>{});var md=class extends ke{static get styles(){return[ke.styles,S`
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
      `]}};customElements.get("craft-dialog")||customElements.define("craft-dialog",md);class Io extends Mi(_n(P)){constructor(){super(),this.multipleChoice=!0}}class Do extends $i(Vi){connectedCallback(){super.connectedCallback(),this.type="checkbox"}}var bd=class extends Io{static get styles(){return[...Io.styles,S`
        .form-field__group-two {
          margin-top: var(--c-spacing-sm);
        }

        ::slotted(label) {
          font-weight: bold;
        }
      `]}};customElements.get("craft-checkbox-group")||customElements.define("craft-checkbox-group",bd);var gd=class extends Do{static get styles(){return[...Do.styles,S`
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
      `]}};customElements.get("craft-checkbox")||customElements.define("craft-checkbox",gd);const we={Default:"default",Success:"success",Warning:"warning",Danger:"danger",Info:"info"},vd={OutlineFill:"outline-fill"};var Ws=S`
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
`,_d=S`
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
`,Ue=class extends P{constructor(...i){super(...i),this.variant=we.Default,this.appearance=vd.OutlineFill,this.title="",this.icon=null,this.rounded="all",this.inline=!1}getDefaultIcon(){switch(this.variant){case we.Info:return"lightbulb";case we.Success:return"circle-check";case we.Warning:return"circle-exclamation";case we.Danger:return"triangle-exclamation";default:return null}}render(){return y`
      ${this.icon||this.querySelector('[slot="icon"]')?y`<slot name="icon" class="callout__icon">
            <craft-icon
              name="${this.getDefaultIcon()}"
              style="font-size: 0.9em"
            ></craft-icon>
          </slot>`:j}
      <div class="callout__body">
        <slot name="title" class="callout__title">${this.title}</slot>
        <div class="callout__description">
          <slot></slot>
        </div>
      </div>
    `}};Ue.styles=[Ws,_d],C([m({reflect:!0})],Ue.prototype,"variant",void 0),C([m({reflect:!0})],Ue.prototype,"appearance",void 0),C([m()],Ue.prototype,"title",void 0),C([m()],Ue.prototype,"icon",void 0),C([m({reflect:!0})],Ue.prototype,"rounded",void 0),C([m({reflect:!0,type:Boolean})],Ue.prototype,"inline",void 0),customElements.get("craft-callout")||customElements.define("craft-callout",Ue);var yd=S`
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
`,nt=class extends P{constructor(...e){super(...e),this.icon=null,this.href=null,this.disabled=!1,this.variant=we.Default}renderBody(){return y`
      <span class="action-item__prefix">
        <slot name="prefix">
          <slot name="icon">
            ${this.icon?y`<craft-icon name="${this.icon}"></craft-icon>`:j}
          </slot>
        </slot>
      </span>

      <slot></slot>

      <span class="action-item__suffix">
        <slot name="suffix"></slot>
      </span>
    `}render(){return this.href?y`
          <a class="action-item" href="${this.href}"> ${this.renderBody()} </a>
        `:y`
          <button
            type="button"
            class="action-item"
            ?disabled="${this.disabled}"
          >
            ${this.renderBody()}
          </button>
        `}};nt.styles=[Ws,yd],C([m()],nt.prototype,"icon",void 0),C([m()],nt.prototype,"href",void 0),C([m({type:Boolean})],nt.prototype,"disabled",void 0),C([m({reflect:!0})],nt.prototype,"variant",void 0),customElements.get("craft-action-item")||customElements.define("craft-action-item",nt);const wd=S`
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
`;class Oe{static __createGlobalStyleNode(){const e=document.createElement("style");return e.setAttribute("data-overlays",""),e.textContent=wd.cssText,document.head.appendChild(e),e}get list(){return this.__list}get shownList(){return this.__shownList}constructor(){this.__list=[],this.__shownList=[],this.__siblingsInert=!1,this.__blockingMap=new WeakMap,Oe.__globalStyleNode||(Oe.__globalStyleNode=Oe.__createGlobalStyleNode())}add(e){if(this.list.find(t=>e===t))throw new Error("controller instance is already added");return this.list.push(e),e}remove(e){if(!this.list.find(t=>e===t))throw new Error("could not find controller to remove");this.__list=this.list.filter(t=>t!==e),this.__shownList=this.shownList.filter(t=>t!==e)}show(e){this.list.find(t=>e===t)&&this.hide(e),this.__shownList.unshift(e),Array.from(this.__shownList).reverse().forEach((t,s)=>{t.elevation=s+1})}hide(e){if(!this.list.find(t=>e===t))throw new Error("could not find controller to hide");this.__shownList=this.shownList.filter(t=>t!==e)}teardown(){this.list.forEach(e=>{e.teardown()}),this.__list=[],this.__shownList=[],this.__siblingsInert=!1,Oe.__globalStyleNode&&(document.head.removeChild(Oe.__globalStyleNode),Oe.__globalStyleNode=void 0)}get siblingsInert(){return this.__siblingsInert}disableTrapsKeyboardFocusForAll(){this.shownList.forEach(e=>{e.trapsKeyboardFocus===!0&&e.disableTrapsKeyboardFocus&&e.disableTrapsKeyboardFocus({findNewTrap:!1})})}informTrapsKeyboardFocusGotEnabled(e){this.siblingsInert===!1&&e==="global"&&(this.__siblingsInert=!0)}informTrapsKeyboardFocusGotDisabled({disabledCtrl:e,findNewTrap:t=!0}={}){const s=this.shownList.find(o=>o!==e&&o.trapsKeyboardFocus===!0);s?t&&s.enableTrapsKeyboardFocus():this.siblingsInert===!0&&(this.__siblingsInert=!1)}requestToPreventScroll(){const{isIOS:e,isMacSafari:t}=gi;document.body.classList.add("overlays-scroll-lock"),(e||t)&&document.body.classList.add("overlays-scroll-lock-ios-fix"),e&&document.documentElement.classList.add("overlays-scroll-lock-ios-fix")}requestToEnableScroll(){if(this.shownList.some(o=>o.preventsScroll===!0))return;const{isIOS:t,isMacSafari:s}=gi;document.body.classList.remove("overlays-scroll-lock"),(t||s)&&document.body.classList.remove("overlays-scroll-lock-ios-fix"),t&&document.documentElement.classList.remove("overlays-scroll-lock-ios-fix")}requestToShowOnly(e){const t=this.shownList.filter(s=>s!==e);t.forEach(s=>s.hide()),this.__blockingMap.set(e,t)}retractRequestToShowOnly(e){this.__blockingMap.has(e)&&this.__blockingMap.get(e).forEach(s=>s.show())}}Oe.__globalStyleNode=void 0;const xd=li.get("@lion/ui::overlays::0.x")||new Oe;function Ss(){let i=document.activeElement||document.body;for(;i&&i.shadowRoot&&i.shadowRoot.activeElement;)i=i.shadowRoot.activeElement;return i}const Mo=({visibility:i,display:e})=>i!=="hidden"&&e!=="none",Ed=({display:i})=>i==="contents";function Cd(i){if(!i||!i.isConnected||!Mo(i.style))return!1;const e=window.getComputedStyle(i);return Mo(e)?Ed(e)?!0:!!(i.offsetWidth||i.offsetHeight||i.getClientRects().length):!1}function kd(i,e){const t=Math.max(i.tabIndex,0),s=Math.max(e.tabIndex,0);return t===0||s===0?s>t:t>s}function Sd(i,e){const t=[];for(;i.length>0&&e.length>0;)kd(i[0],e[0])?t.push(e.shift()):t.push(i.shift());return[...t,...i,...e]}function As(i){const e=i.length;if(e<2)return i;const t=Math.ceil(e/2),s=As(i.slice(0,t)),o=As(i.slice(t));return Sd(s,o)}const rs="matches"in Element.prototype?"matches":"msMatchesSelector";function Ad(i){return i[rs]("input, select, textarea, button, object")?i[rs](":not([disabled])"):i[rs]("a[href], area[href], iframe, [tabindex], [contentEditable]")}function Td(i){return Ad(i)?Number(i.getAttribute("tabindex")||0):-1}function Nd(i){if(i.localName==="slot")return i.assignedNodes({flatten:!0});const{children:e}=i.shadowRoot||i;return e||[]}function Fd(i){return i.nodeType!==Node.ELEMENT_NODE?!1:i.localName==="slot"?!0:Cd(i)}function Rn(i,e){if(!Fd(i))return!1;const t=i,s=Td(t);let o=s>0;s>=0&&e.push(t);const n=Nd(t);for(let r=0;r<n.length;r+=1)o=Rn(n[r],e)||o;return o}function zn(i){const e=[];return Rn(i,e)?As(e):e}function ht(i,e,t={}){function s(f){return"getAttribute"in f}function o(f){if(!s(f))return null;const _=f.getAttribute("slot");let v=null;if(_){const w=t[_];w&&(v=w.filter(x=>x?.element===f)[0]||null)}return v}const n=o(i);if(n)return n.deepContains;function r(f){if(!s(i))return;const _=i.getAttribute("slot");_&&(t[_]=t[_]||[],t[_].push({element:i,deepContains:f}))}let a=i.contains(e);if(a)return r(!0),!0;function c(f){return f.tagName==="SLOT"}function u(f){return c(f)?f.assignedElements():[]}function h(f){return f.nodeType===Node.DOCUMENT_FRAGMENT_NODE}function b(f){let _=!1;for(let v=0;v<f.length;v+=1){const w=f[v];if(w&&(s(w)||h(w))&&ht(w,e,t)){_=!0;break}}return _}function p(f){for(let _=0;_<f.children.length;_+=1){const v=f.children[_],w=o(v);if(w){a=w.deepContains||a;break}const x=u(v),A=[v.shadowRoot,...x];if(b(A)){a=!0;break}v.children.length>0&&p(v)}}return i instanceof HTMLElement&&i.shadowRoot&&(a=ht(i.shadowRoot,e,t),a)?(r(!0),!0):(p(i),r(a),a)}const Ld={tab:9};function Od(i,e){const t=zn(i);let s;t.length>=2?s=[t[0],t[t.length-1]]:t.length===1?s=[t[0],t[0]]:s=[i,i],e.shiftKey&&s.reverse();const[o,n]=s,r=Ss();r===i||t.includes(r)&&n!==r||(e.preventDefault(),o.focus())}function Id(i){const e=zn(i),t=e.find(p=>p.hasAttribute("autofocus"))||i;let s,o;t===i&&(i.tabIndex=-1,i.style.setProperty("outline","none")),t.focus();function n(p){p.keyCode===Ld.tab&&Od(i,p)}function r(){s=document.createElement("div"),s.style.display="none",s.setAttribute("data-is-tab-detection-element",""),i.insertBefore(s,i.children[0]),o=new MutationObserver(p=>{for(const f of p)if(f.type==="childList"){const _=!Array.from(i.children).find(w=>w.hasAttribute("data-is-tab-detection-element")),v=Array.from(f.addedNodes).find(w=>w instanceof HTMLElement&&w.hasAttribute("data-is-tab-detection-element"));_&&!v&&(o.disconnect(),r())}}),o.observe(i,{childList:!0})}function a(){return s.compareDocumentPosition(document.activeElement)===Node.DOCUMENT_POSITION_PRECEDING}function c({resetToRoot:p=!1}={}){if(ht(i,Ss()))return;let f;p?f=i:f=e[a()?0:e.length-1],f&&f.focus()}function u(){window.removeEventListener("focusin",u),c()}function h(){setTimeout(()=>{ht(i,Ss())||c({resetToRoot:!0})}),window.addEventListener("focusin",u)}function b(){window.removeEventListener("keydown",n),window.removeEventListener("focusin",u),window.removeEventListener("focusout",h),o.disconnect(),Array.from(i.children).includes(s)&&i.removeChild(s),i.style.removeProperty("outline")}return window.addEventListener("keydown",n),window.addEventListener("focusout",h),r(),{disconnect:b}}const $o=S`
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
`,pt={supportsAdoptingStyleSheets:window.ShadowRoot&&(window.ShadyCSS===void 0||window.ShadyCSS.nativeShadow)&&"adoptedStyleSheets"in Document.prototype&&"replace"in CSSStyleSheet.prototype,adoptStyle:void 0,adoptStyles:void 0},as=new WeakMap;function Dd(i){return Array.from(i.cssRules).map(e=>e.cssText).join("")}function Md(i,e,{teardown:t=!1}={}){const s=i===document?document.body:i,o=e.cssText||Dd(e);if(t){const n=Array.from(s.querySelectorAll("style"));for(const r of n)if(r.textContent===o){r.remove();break}}else{const n=document.createElement("style"),r=window.litNonce;r!==void 0&&n.setAttribute("nonce",r),n.textContent=o,s.appendChild(n)}}function $d(i,e,{teardown:t=!1}={}){let s=!1;i&&!as.has(i)&&as.set(i,[]);const o=as.get(i)??[],n=o.find(r=>e===r);return n&&t?o.splice(o.indexOf(e),1):!n&&!t?o.push(e):(n&&!t||!n&&t)&&(s=!0),{haltFurtherExecution:s}}function Vd(i,e,{teardown:t=!1}={}){const{haltFurtherExecution:s}=$d(i,e,{teardown:t});if(s)return;if(!pt.supportsAdoptingStyleSheets||gi.isIOS){Md(i,e,{teardown:t});return}const o=e instanceof CSSStyleSheet?e:e.styleSheet;if(!o)throw new Error("Please provide a CSSResultOrNative style");t?i.adoptedStyleSheets.includes(o)&&i.adoptedStyleSheets.splice(i.adoptedStyleSheets.indexOf(o),1):i.adoptedStyleSheets=[...i.adoptedStyleSheets,o]}function Rd(i,e,{teardown:t=!1}={}){for(const s of e)pt.adoptStyle(i,s,{teardown:t})}pt.adoptStyle=Vd;pt.adoptStyles=Rd;function zd({wrappingDialogNodeL1:i,contentWrapperNodeL2:e,contentNodeL3:t}){if(!(e.isConnected||t.isConnected))throw new Error('[OverlayController] Could not find a render target, since the provided contentNode is not connected to the DOM. Make sure that it is connected, e.g. by doing "document.body.appendChild(contentNode)", before passing it on.');let s;const o=document.createComment("tempMarker");e.isConnected?(s=e.parentElement||e.getRootNode(),s.insertBefore(o,e),i.appendChild(e)):t.assignedSlot?(s=t.assignedSlot.parentElement||t.assignedSlot.getRootNode(),s.insertBefore(o,t.assignedSlot),i.appendChild(e),e.appendChild(t.assignedSlot)):(s=t.parentElement||t.getRootNode(),s.insertBefore(o,t),i.appendChild(e),e.appendChild(t)),s.insertBefore(i,o),s?.removeChild(o)}async function Pd(){return E(()=>import("./popper.js"),[],import.meta.url)}const Vo=new WeakMap;class Xe extends EventTarget{constructor(e={},t=xd){super(),this.manager=t,this.__sharedConfig=e,this.__activeElementRightBeforeHide=null,this.config={},this._defaultConfig={placementMode:void 0,contentNode:e.contentNode,contentWrapperNode:e.contentWrapperNode,invokerNode:e.invokerNode,backdropNode:e.backdropNode,referenceNode:void 0,elementToFocusAfterHide:e.invokerNode,inheritsReferenceWidth:"none",hasBackdrop:!1,isBlocking:!1,preventsScroll:!1,trapsKeyboardFocus:!1,hidesOnEsc:!1,hidesOnOutsideEsc:!1,hidesOnOutsideClick:!1,isTooltip:!1,isAlertDialog:!1,invokerRelation:"description",visibilityTriggerFunction:void 0,handlesAccessibility:!1,popperConfig:{placement:"top",strategy:"fixed",modifiers:[{name:"preventOverflow",enabled:!0,options:{boundariesElement:"viewport",padding:8}},{name:"flip",options:{boundariesElement:"viewport",padding:16}},{name:"offset",enabled:!0,options:{offset:[0,8]}},{name:"arrow",enabled:!1}]},viewportConfig:{placement:"center"},zIndex:9999},this._contentId=`overlay-content--${Math.random().toString(36).slice(2,10)}`,this.__originalAttrs=new Map,this.updateConfig(e),this.__hasActiveTrapsKeyboardFocus=!1,this.__hasActiveBackdrop=!0,this.__escKeyHandler=this.__escKeyHandler.bind(this),this.__cancelHandler=this.__cancelHandler.bind(this)}get invoker(){return this.invokerNode}get content(){return this.__wrappingDialogNode}get placementMode(){return this.config?.placementMode}get invokerNode(){return this.config?.invokerNode}get referenceNode(){return this.config?.referenceNode}get contentNode(){return this.config?.contentNode}get contentWrapperNode(){return this.__contentWrapperNode||this.config?.contentWrapperNode}get backdropNode(){return this.__backdropNode||this.config?.backdropNode}get elementToFocusAfterHide(){return this.__elementToFocusAfterHide||this.config?.elementToFocusAfterHide}get hasBackdrop(){return!!this.backdropNode||this.config?.hasBackdrop}get isBlocking(){return this.config?.isBlocking}get preventsScroll(){return this.config?.preventsScroll}get trapsKeyboardFocus(){return this.config?.trapsKeyboardFocus}get hidesOnEsc(){return this.config?.hidesOnEsc}get hidesOnOutsideClick(){return this.config?.hidesOnOutsideClick}get hidesOnOutsideEsc(){return this.config?.hidesOnOutsideEsc}get inheritsReferenceWidth(){return this.config?.inheritsReferenceWidth}get handlesAccessibility(){return this.config?.handlesAccessibility}get isTooltip(){return this.config?.isTooltip}get isAlertDialog(){return this.config?.isAlertDialog}get invokerRelation(){return this.config?.invokerRelation}get popperConfig(){return this.config?.popperConfig}get viewportConfig(){return this.config?.viewportConfig}get visibilityTriggerFunction(){return this.config?.visibilityTriggerFunction}get _referenceNode(){return this.referenceNode||this.invokerNode}set elevation(e){this.__wrappingDialogNode.style.zIndex=`${this.config.zIndex+e}`}get elevation(){return Number(this.contentWrapperNode?.style.zIndex)}updateConfig(e){this.teardown(),this.__prevConfig=this.config,this.config={...this._defaultConfig,...this.__sharedConfig,...e,popperConfig:{...this._defaultConfig.popperConfig||{},...this.__sharedConfig.popperConfig||{},...e.popperConfig||{},modifiers:[...this._defaultConfig.popperConfig?.modifiers||[],...this.__sharedConfig.popperConfig?.modifiers||[],...e.popperConfig?.modifiers||[]]}},this.__validateConfiguration(this.config),this._init(),this.__elementToFocusAfterHide=void 0,this.#e()||this.manager.add(this)}#e(){return!!this.manager.list.find(e=>this===e)}__validateConfiguration(e){if(!e.placementMode)throw new Error('[OverlayController] You need to provide a .placementMode ("global"|"local")');if(!["global","local"].includes(e.placementMode))throw new Error(`[OverlayController] "${e.placementMode}" is not a valid .placementMode, use ("global"|"local")`);if(!e.contentNode)throw new Error("[OverlayController] You need to provide a .contentNode");if(e.isTooltip&&!e.handlesAccessibility)throw new Error("[OverlayController] .isTooltip only takes effect when .handlesAccessibility is enabled")}_init(){this.__contentHasBeenInitialized||(this.__initContentDomStructure(),this.__contentHasBeenInitialized=!0),this.contentWrapperNode.removeAttribute("style"),this.contentWrapperNode.removeAttribute("class"),this.placementMode==="local"&&(Xe.popperModule||(Xe.popperModule=Pd())),this.__handleOverlayStyles({phase:"init"}),this._handleFeatures({phase:"init"})}__handleOverlayStyles({phase:e}){const t=this.contentWrapperNode?.getRootNode();e==="init"?pt.adoptStyle(t,$o):e==="teardown"&&pt.adoptStyle(t,$o,{teardown:!0})}__initContentDomStructure(){const e=document.createElement(this.config?._noDialogEl?"div":"dialog");e.setAttribute("role","none"),e.setAttribute("data-overlay-outer-wrapper",""),e.style.cssText=`display:none; z-index: ${this.config.zIndex}; padding: 0;`,this.__wrappingDialogNode=e,this.config?.contentWrapperNode||(this.__contentWrapperNode=document.createElement("div")),this.contentWrapperNode.setAttribute("data-id","content-wrapper"),zd({wrappingDialogNodeL1:e,contentWrapperNodeL2:this.contentWrapperNode,contentNodeL3:this.contentNode}),e.open=!0,this.isTooltip&&e.setAttribute("tabindex","-1"),this.__wrappingDialogNode.style.display="none",this.contentWrapperNode.style.zIndex="1",getComputedStyle(this.contentNode).position==="absolute"&&(this.contentNode.style.position="static"),HTMLDialogElement&&"closedBy"in HTMLDialogElement.prototype?e.closedBy="none":(e.addEventListener("keydown",s=>{s.key==="Escape"&&s.preventDefault()}),e.addEventListener("keyup",s=>{s.key==="Escape"&&s.preventDefault()}),e.addEventListener("cancel",s=>{s.stopPropagation()}),e.addEventListener("close",s=>{s.stopPropagation()}))}_handleZIndex({phase:e}){if(this.placementMode==="local"&&e==="setup"){const t=Number(getComputedStyle(this.contentNode).zIndex);(t<1||Number.isNaN(t))&&(this.contentNode.style.zIndex="1")}}__setupTeardownAccessibility({phase:e}){if(e==="init"){this.__storeOriginalAttrs(this.contentNode,["role","id"]);const t=this.trapsKeyboardFocus;if(this.invokerNode){const s=["aria-labelledby","aria-describedby"];t||s.push("aria-expanded"),this.__storeOriginalAttrs(this.invokerNode,s)}this.contentNode.id||this.contentNode.setAttribute("id",this._contentId),this.isTooltip?(this.invokerNode&&this.invokerNode.setAttribute(this.invokerRelation==="label"?"aria-labelledby":"aria-describedby",this._contentId),this.contentNode.setAttribute("role","tooltip")):(this.invokerNode&&!t&&this.invokerNode.setAttribute("aria-expanded",`${this.isShown}`),this.isAlertDialog?this.contentNode.setAttribute("role","alertdialog"):this.contentNode.getAttribute("role")||this.contentNode.setAttribute("role","dialog"))}else e==="teardown"&&this.__restoreOriginalAttrs()}__storeOriginalAttrs(e,t){const s={};t.forEach(o=>{s[o]=e.getAttribute(o)}),this.__originalAttrs.set(e,s)}__restoreOriginalAttrs(){for(const[e,t]of this.__originalAttrs)Object.entries(t).forEach(([s,o])=>{o!==null?e.setAttribute(s,o):e.removeAttribute(s)});this.__originalAttrs.clear()}get isShown(){return this.__wrappingDialogNode?.style.display!=="none"}async show(e=this.elementToFocusAfterHide){if(this._showComplete&&await this._showComplete,this._showComplete=new Promise(s=>{this._showResolve=s}),this.manager&&this.manager.show(this),this.isShown){this._showResolve();return}const t=new CustomEvent("before-show",{cancelable:!0});this.dispatchEvent(t),t.defaultPrevented||("HTMLDialogElement"in window&&this.__wrappingDialogNode instanceof HTMLDialogElement&&(this.__wrappingDialogNode.open=!0),this.__wrappingDialogNode.style.display="",this._keepBodySize({phase:"before-show"}),await this._handleFeatures({phase:"show"}),this._keepBodySize({phase:"show"}),await this._handlePosition({phase:"show"}),this.__elementToFocusAfterHide=e,this.dispatchEvent(new Event("show")),await this._transitionShow({backdropNode:this.backdropNode,contentNode:this.contentNode})),this._showResolve()}async _handlePosition({phase:e}){if(this.placementMode==="global"){const t=`overlays__overlay-container--${this.viewportConfig.placement}`;e==="show"?(this.contentWrapperNode.classList.add("overlays__overlay-container"),this.contentWrapperNode.classList.add(t),this.contentNode.classList.add("overlays__overlay")):e==="hide"&&(this.contentWrapperNode.classList.remove("overlays__overlay-container"),this.contentWrapperNode.classList.remove(t),this.contentNode.classList.remove("overlays__overlay"))}else this.placementMode==="local"&&e==="show"&&(await this.__createPopperInstance(),this._popper.forceUpdate())}_keepBodySize({phase:e}){if(this.preventsScroll)switch(e){case"before-show":this.__bodyClientWidth=document.body.clientWidth,this.__bodyClientHeight=document.body.clientHeight,this.__bodyMarginRightInline=document.body.style.marginRight,this.__bodyMarginBottomInline=document.body.style.marginBottom;break;case"show":{if(window.getComputedStyle){const r=window.getComputedStyle(document.body);this.__bodyMarginRight=parseInt(r.getPropertyValue("margin-right"),10),this.__bodyMarginBottom=parseInt(r.getPropertyValue("margin-bottom"),10)}else this.__bodyMarginRight=0,this.__bodyMarginBottom=0;const t=document.body.clientWidth-this.__bodyClientWidth,s=document.body.clientHeight-this.__bodyClientHeight,o=this.__bodyMarginRight+t,n=this.__bodyMarginBottom+s;window.CSS?.number&&document.body.attributeStyleMap?.set?(document.body.attributeStyleMap.set("margin-right",CSS.px(o)),document.body.attributeStyleMap.set("margin-bottom",CSS.px(n))):(document.body.style.marginRight=`${o}px`,document.body.style.marginBottom=`${n}px`);break}case"hide":document.body.style.marginRight=this.__bodyMarginRightInline||"",document.body.style.marginBottom=this.__bodyMarginBottomInline||"";break}}async hide(){if(this._hideComplete=new Promise(t=>{this._hideResolve=t}),this.__activeElementRightBeforeHide=this.contentNode.getRootNode().activeElement,this.manager&&this.manager.hide(this),!this.isShown){this._hideResolve();return}const e=new CustomEvent("before-hide",{cancelable:!0});this.dispatchEvent(e),e.defaultPrevented||(await this._transitionHide({backdropNode:this.backdropNode,contentNode:this.contentNode}),"HTMLDialogElement"in window&&this.__wrappingDialogNode instanceof HTMLDialogElement&&this.__wrappingDialogNode.close(),this.__wrappingDialogNode.style.display="none",this._handleFeatures({phase:"hide"}),this._keepBodySize({phase:"hide"}),this.dispatchEvent(new Event("hide")),this._restoreFocus()),this._hideResolve()}async transitionHide(e){}async _transitionHide({backdropNode:e,contentNode:t}){await this.transitionHide({backdropNode:e,contentNode:t}),this._handlePosition({phase:"hide"}),e&&e.classList.remove("overlays__backdrop--animation-in")}async transitionShow(e){}async _transitionShow(e){await this.transitionShow({backdropNode:this.backdropNode,contentNode:this.contentNode}),e.backdropNode&&e.backdropNode.classList.add("overlays__backdrop--animation-in")}_restoreFocus(){this.__activeElementRightBeforeHide instanceof HTMLElement&&this.contentNode.contains(this.__activeElementRightBeforeHide)&&(this.elementToFocusAfterHide instanceof HTMLElement?(this.elementToFocusAfterHide.focus(),this.elementToFocusAfterHide.scrollIntoView({block:"nearest"})):this.__activeElementRightBeforeHide.blur())}async toggle(){return this.isShown?this.hide():this.show()}_handleFeatures({phase:e}){this._handleZIndex({phase:e}),this.preventsScroll&&this._handlePreventsScroll({phase:e}),this.isBlocking&&this._handleBlocking({phase:e}),this.hasBackdrop&&this._handleBackdrop({phase:e}),this.trapsKeyboardFocus&&this._handleTrapsKeyboardFocus({phase:e}),this.hidesOnEsc&&this._handleHidesOnEsc({phase:e}),this.hidesOnOutsideEsc&&this._handleHidesOnOutsideEsc({phase:e}),this.hidesOnOutsideClick&&this._handleHidesOnOutsideClick({phase:e}),this.handlesAccessibility&&this._handleAccessibility({phase:e}),this.inheritsReferenceWidth&&this._handleInheritsReferenceWidth(),this.visibilityTriggerFunction&&this._handleVisibilityTriggers({phase:e})}_handleVisibilityTriggers({phase:e}){typeof this.visibilityTriggerFunction=="function"&&(e==="init"&&(this.__visibilityTriggerHandler=this.visibilityTriggerFunction({phase:e,controller:this})),this.__visibilityTriggerHandler[e]&&this.__visibilityTriggerHandler[e]())}_handlePreventsScroll({phase:e}){switch(e){case"show":this.manager.requestToPreventScroll();break;case"hide":this.manager.requestToEnableScroll();break}}_handleBlocking({phase:e}){switch(e){case"show":this.manager.requestToShowOnly(this);break;case"hide":this.manager.retractRequestToShowOnly(this);break}}get hasActiveBackdrop(){return this.__hasActiveBackdrop}_handleBackdrop({phase:e}){switch(e){case"init":{this.__backdropInitialized||(this.config?.backdropNode||(this.__backdropNode=document.createElement("div"),this.__backdropNode.classList.add("overlays__backdrop")),this.__wrappingDialogNode.prepend(this.backdropNode),this.__backdropInitialized=!0);break}case"show":this.config.hasBackdrop&&this.backdropNode.classList.add("overlays__backdrop--visible"),this.__hasActiveBackdrop=!0;break;case"hide":case"teardown":this.backdropNode.classList.remove("overlays__backdrop--visible"),this.__hasActiveBackdrop=!1;break}}get hasActiveTrapsKeyboardFocus(){return this.__hasActiveTrapsKeyboardFocus}_handleTrapsKeyboardFocus({phase:e}){e==="show"?("showModal"in this.__wrappingDialogNode&&(this.__wrappingDialogNode.close(),this.__wrappingDialogNode.showModal()),this.enableTrapsKeyboardFocus()):(e==="hide"||e==="teardown")&&this.disableTrapsKeyboardFocus()}enableTrapsKeyboardFocus(){if(this.__hasActiveTrapsKeyboardFocus)return;this.manager&&this.manager.disableTrapsKeyboardFocusForAll(),this.contentNode.shadowRoot&&console.warn("[overlays]: For best accessibility (compatibility with Safari + VoiceOver), provide a contentNode that is not a host for a shadow root"),this._containFocusHandler=Id(this.contentNode),this.__hasActiveTrapsKeyboardFocus=!0,this.manager&&this.manager.informTrapsKeyboardFocusGotEnabled(this.placementMode)}disableTrapsKeyboardFocus({findNewTrap:e=!0}={}){this.__hasActiveTrapsKeyboardFocus&&(this._containFocusHandler&&(this._containFocusHandler.disconnect(),this._containFocusHandler=void 0),this.__hasActiveTrapsKeyboardFocus=!1,this.manager&&this.manager.informTrapsKeyboardFocusGotDisabled({disabledCtrl:this,findNewTrap:e}))}__cancelHandler(e){e.preventDefault()}__escKeyHandler(e){if(e.key!=="Escape"||Vo.has(e))return;(e.composedPath().includes(this.contentNode)||ht(this.contentNode,e.target))&&(this.hide(),Vo.set(e,this))}#t=e=>{e.key!=="Escape"||e.composedPath().includes(this.contentNode)||ht(this.contentNode,e.target)||this.hide()};_handleHidesOnEsc({phase:e}){e==="show"?(this.contentNode.addEventListener("keyup",this.__escKeyHandler),this.invokerNode&&this.invokerNode.addEventListener("keyup",this.__escKeyHandler)):(e==="hide"||e==="teardown")&&(this.contentNode.removeEventListener("keyup",this.__escKeyHandler),this.invokerNode&&this.invokerNode.removeEventListener("keyup",this.__escKeyHandler))}_handleHidesOnOutsideEsc({phase:e}){e==="show"?document.addEventListener("keyup",this.#t):(e==="hide"||e==="teardown")&&document.removeEventListener("keyup",this.#t)}_handleInheritsReferenceWidth(){if(!this._referenceNode||this.placementMode==="global")return;const e=`${this._referenceNode.getBoundingClientRect().width}px`;switch(this.inheritsReferenceWidth){case"max":this.contentWrapperNode.style.maxWidth=e;break;case"full":this.contentWrapperNode.style.width=e;break;case"min":this.contentWrapperNode.style.minWidth=e,this.contentWrapperNode.style.width="auto";break}}_handleHidesOnOutsideClick({phase:e}){const t=e==="show"?"addEventListener":"removeEventListener";if(e==="show"){let s=!1,o=!1;this.__onInsideMouseDown=()=>{s=!0},this.__onInsideMouseUp=()=>{o=!0},this.__onDocumentMouseUp=()=>{setTimeout(()=>{!s&&!o&&this.hide(),s=!1,o=!1})},this.__onWindowBlur=()=>{setTimeout(()=>{this.hide()})}}this.contentWrapperNode[t]("mousedown",this.__onInsideMouseDown,!0),this.contentWrapperNode[t]("mouseup",this.__onInsideMouseUp,!0),this.invokerNode&&(this.invokerNode[t]("mousedown",this.__onInsideMouseDown,!0),this.invokerNode[t]("mouseup",this.__onInsideMouseUp,!0)),document.documentElement[t]("mouseup",this.__onDocumentMouseUp,!0),window[t]("blur",this.__onWindowBlur)}_handleAccessibility({phase:e}){(e==="init"||e==="teardown")&&this.__setupTeardownAccessibility({phase:e});const t=this.trapsKeyboardFocus;this.invokerNode&&!this.isTooltip&&!t&&this.invokerNode.setAttribute("aria-expanded",`${e==="show"}`)}teardown(){this.__handleOverlayStyles({phase:"teardown"}),this._handleFeatures({phase:"teardown"}),this.#e()&&this.manager.remove(this)}async __createPopperInstance(){if(this._popper&&(this._popper.destroy(),this._popper=void 0),Xe.popperModule!==void 0){const{createPopper:e}=await Xe.popperModule;this._popper=e(this._referenceNode,this.contentWrapperNode,{...this.config?.popperConfig})}}_hasDisabledInvoker(){return this.invokerNode?this.invokerNode.disabled||this.invokerNode.getAttribute("aria-disabled")==="true":!1}}Xe.popperModule=void 0;function Pn(i,e){if(typeof i!="object"||typeof e!="object"||i===null||e===null)return i===e;const t=Object.keys(i),s=Object.keys(e);if(t.length!==s.length)return!1;const o=n=>Pn(i[n],e[n]);return t.every(o)}const Bd=i=>class extends i{static get properties(){return{opened:{type:Boolean,reflect:!0}}}#e=!1;constructor(){super(),this.opened=!1,this.config={},this.toggle=this.toggle.bind(this),this.open=this.open.bind(this),this.close=this.close.bind(this)}get config(){return this.__config}set config(t){const s=!Pn(this.config,t);this._overlayCtrl&&s&&this._overlayCtrl.updateConfig(t),this.__config=t,this._overlayCtrl&&s&&this.__syncToOverlayController()}requestUpdate(t,s,o){super.requestUpdate(t,s,o),t==="opened"&&this.opened!==s&&this.dispatchEvent(new CustomEvent("opened-changed",{detail:{opened:this.opened}}))}_defineOverlay({contentNode:t,invokerNode:s,referenceNode:o,backdropNode:n,contentWrapperNode:r}){const a=this._defineOverlayConfig()||{};return new Xe({contentNode:t,invokerNode:s,referenceNode:o,backdropNode:n,contentWrapperNode:r,...a,...this.config,popperConfig:{...a.popperConfig||{},...this.config?.popperConfig||{},modifiers:[...a.popperConfig?.modifiers||[],...this.config?.popperConfig?.modifiers||[]]}})}_defineOverlayConfig(){return{placementMode:"local"}}updated(t){super.updated(t),t.has("opened")&&this._overlayCtrl&&!this.__blockSyncToOverlayCtrl&&this.__syncToOverlayController()}_setupOpenCloseListeners(){this.__closeEventInContentNodeHandler=t=>{t.stopPropagation(),this._overlayCtrl.hide()},this._overlayContentNode&&this._overlayContentNode.addEventListener("close-overlay",this.__closeEventInContentNodeHandler)}_teardownOpenCloseListeners(){this._overlayContentNode&&this._overlayContentNode.removeEventListener("close-overlay",this.__closeEventInContentNodeHandler)}connectedCallback(){super.connectedCallback(),this.updateComplete.then(()=>{this.isConnected&&(this.#e||(this._setupOverlayCtrl(),this.#e=!0))})}async disconnectedCallback(){super.disconnectedCallback(),await this._isPermanentlyDisconnected()&&(this._teardownOverlayCtrl(),this.#e=!1)}static enabledWarnings=super.enabledWarnings?.filter(t=>t!=="change-in-update")||[];get _overlayInvokerNode(){return Array.from(this.children).find(t=>t.slot==="invoker")}get _overlayReferenceNode(){}get _overlayBackdropNode(){return this.__cachedOverlayBackdropNode||(this.__cachedOverlayBackdropNode=Array.from(this.children).find(t=>t.slot==="backdrop")),this.__cachedOverlayBackdropNode}get _overlayContentNode(){return this._cachedOverlayContentNode||(this._cachedOverlayContentNode=Array.from(this.children).find(t=>t.slot==="content")||this.config.contentNode),this._cachedOverlayContentNode}get _overlayContentWrapperNode(){return this.shadowRoot?.querySelector("#overlay-content-node-wrapper")}_setupOverlayCtrl(){if(this.#e)return;const t={contentNode:this._overlayContentNode,contentWrapperNode:this._overlayContentWrapperNode,invokerNode:this._overlayInvokerNode,referenceNode:this._overlayReferenceNode,backdropNode:this._overlayBackdropNode};this._overlayCtrl?this._overlayCtrl.updateConfig(t):this._overlayCtrl=this._defineOverlay(t),this.__syncToOverlayController(),this.__setupSyncFromOverlayController(),this._setupOpenCloseListeners()}_teardownOverlayCtrl(){this._overlayCtrl&&(this._teardownOpenCloseListeners(),this.__teardownSyncFromOverlayController(),this._overlayCtrl.teardown())}async _setOpenedWithoutPropertyEffects(t){this.__blockSyncToOverlayCtrl=!0,this.opened=t,await this.updateComplete,this.__blockSyncToOverlayCtrl=!1}__setupSyncFromOverlayController(){this.__onOverlayCtrlShow=()=>{this.opened=!0},this.__onOverlayCtrlHide=()=>{this.opened=!1},this.__onBeforeShow=t=>{const s=new CustomEvent("before-opened",{cancelable:!0});this.dispatchEvent(s),s.defaultPrevented&&(this._setOpenedWithoutPropertyEffects(this._overlayCtrl.isShown),t.preventDefault())},this.__onBeforeHide=t=>{const s=new CustomEvent("before-closed",{cancelable:!0});this.dispatchEvent(s),s.defaultPrevented&&(this._setOpenedWithoutPropertyEffects(this._overlayCtrl.isShown),t.preventDefault())},this._overlayCtrl.addEventListener("show",this.__onOverlayCtrlShow),this._overlayCtrl.addEventListener("hide",this.__onOverlayCtrlHide),this._overlayCtrl.addEventListener("before-show",this.__onBeforeShow),this._overlayCtrl.addEventListener("before-hide",this.__onBeforeHide)}__teardownSyncFromOverlayController(){this._overlayCtrl.removeEventListener("show",this.__onOverlayCtrlShow),this._overlayCtrl.removeEventListener("hide",this.__onOverlayCtrlHide),this._overlayCtrl.removeEventListener("before-show",this.__onBeforeShow),this._overlayCtrl.removeEventListener("before-hide",this.__onBeforeHide)}__syncToOverlayController(){this.opened?this._overlayCtrl.show():this._overlayCtrl.hide()}async toggle(){await this._overlayCtrl.toggle()}async open(){await this._overlayCtrl.show()}async close(){await this._overlayCtrl.hide()}repositionOverlay(){const t=this._overlayCtrl;t.placementMode==="local"&&t._popper&&t._popper.update()}async _isPermanentlyDisconnected(){return await this.updateComplete,!this.isConnected}},Bn=Q(Bd);function Ud(){return{visibilityTriggerFunction:({controller:i})=>{function e(){i._hasDisabledInvoker()||i.toggle()}return{init:()=>{i.invokerNode?.addEventListener("click",e)},teardown:()=>{i.invokerNode?.removeEventListener("click",e)}}}}}const Un=()=>({placementMode:"local",inheritsReferenceWidth:"min",hidesOnOutsideClick:!0,hidesOnEsc:!0,popperConfig:{placement:"bottom-start",modifiers:[{name:"offset",enabled:!1}]},handlesAccessibility:!0,...Ud()});var Et=class extends Bn(P){_defineOverlayConfig(){return{...Un()}}_addEventListeners(){this.actionItems.forEach(e=>{e.addEventListener("click",t=>{t.target?.dispatchEvent(new Event("close-overlay",{bubbles:!0}))})})}_setupInvoker(){let e=this.invokerNodes[0];e&&(e.setAttribute("id",`invoker-${this.uid}`),e.setAttribute("aria-controls",`content-${this.uid}`))}_setupContent(){let e=this.contentNodes[0];e&&(e.setAttribute("id",`content-${this.uid}`),e.setAttribute("role","none"))}_setupOverlayCtrl(){super._setupOverlayCtrl(),this._setupInvoker(),this._setupContent()}firstUpdated(){this.uid=Ut(),this._addEventListeners()}render(){return y`
      <slot name="invoker"></slot>
      <slot name="backdrop"></slot>
      <slot name="content"></slot>
    `}};Et.styles=S`
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
  `,C([oi({selector:"craft-action-item"})],Et.prototype,"actionItems",void 0),C([oi({slot:"invoker"})],Et.prototype,"invokerNodes",void 0),C([oi({slot:"content"})],Et.prototype,"contentNodes",void 0),customElements.get("craft-action-menu")||customElements.define("craft-action-menu",Et);const Ft=new WeakMap;function Hn(i,e){Array.from(i.childNodes).forEach(t=>{if(t.nodeName==="#text"){const s=new RegExp(`^(.*?)(${e})(.*)$`,"i"),o=t.nodeValue.match(s);if(o){const n=document.createTextNode(o[1]);i.appendChild(n);const r=document.createElement("b");r.textContent=o[2],i.appendChild(r);const a=document.createTextNode(o[3]);i.appendChild(a),i.removeChild(t),Ft.set(i,()=>{i.appendChild(t),i.contains(n)&&n.parentNode!==null&&n.parentNode.removeChild(n),i.contains(r)&&r.parentNode!==null&&r.parentNode.removeChild(r),i.contains(a)&&a.parentNode!==null&&a.parentNode.removeChild(a)})}}else Hn(t,e)})}function qn(i){Ft.has(i)&&Ft.get(i)(),Array.from(i.childNodes).forEach(e=>{e.nodeName==="#text"?Ft.has(e)&&Ft.get(e)():qn(e)})}class Hd extends Di{static get validatorName(){return"MatchesOption"}execute(e,t,s){return s?.node.modelValue instanceof et}}function ii(i){return Array.isArray(i)?i:[i]}const qd=i=>class extends Mi(i){static get properties(){return{allowCustomChoice:{type:Boolean,attribute:"allow-custom-choice"},modelValue:{type:Object}}}get modelValue(){return this.__getChoicesFrom(super.modelValue)}set modelValue(t){if(super.modelValue=t,t==null||t==="")this._customChoices=new Set;else if(this.allowCustomChoice){const s=this.modelValue;this._customChoices=new Set(ii(t)),this.requestUpdate("modelValue",s)}}get formattedValue(){return this.__getChoicesFrom(super.formattedValue)}set formattedValue(t){if(super.formattedValue=t,t==null)this._customChoices=new Set;else if(this.allowCustomChoice){const s=this.modelValue;this._customChoices=new Set(ii(t).map(o=>this.formElements.find(n=>n.formattedValue===o)?.modelValue||o)),this.requestUpdate("modelValue",s)}}get serializedValue(){return this.__getChoicesFrom(super.serializedValue)}set serializedValue(t){if(super.serializedValue=t,t==null)this._customChoices=new Set;else if(this.allowCustomChoice){const s=this.modelValue;this._customChoices=new Set(ii(t).map(o=>this.formElements.find(n=>n.serializedValue===o)?.modelValue||o)),this.requestUpdate("modelValue",s)}}get customChoices(){if(!this.allowCustomChoice)return[];const t=this._getCheckedElements();return Array.from(this._customChoices).filter(s=>!t.some(o=>o.choiceValue===s))}constructor(){super(),this.allowCustomChoice=!1,this._customChoices=new Set}__getChoicesFrom(t){const s=t;return this.allowCustomChoice?this.multipleChoice?[...ii(s),...this.customChoices]:s===""?this._customChoices.values().next().value||"":s:s}_isEmpty(){return super._isEmpty()&&this._customChoices.size===0}clear(){this._customChoices=new Set,super.clear()}parser(t){return this.allowCustomChoice&&Array.isArray(t)?t.filter(s=>s.trim()!==""):t}},Wd=Q(qd),ls=new WeakMap;class jd extends Ii(Bn(Wd(Ac))){static get properties(){return{autocomplete:{type:String,reflect:!0},matchMode:{type:String,attribute:"match-mode"},showAllOnEmpty:{type:Boolean,attribute:"show-all-on-empty"},requireOptionMatch:{type:Boolean},allowCustomChoice:{type:Boolean,attribute:"allow-custom-choice"},__shouldAutocompleteNextUpdate:Boolean}}static get styles(){return[...super.styles,S`
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
      `]}static get localizeNamespaces(){return[{"lion-combobox":e=>{switch(e){case"bg-BG":case"bg":return E(()=>import("./bg.js"),[],import.meta.url);case"cs-CZ":case"cs":return E(()=>import("./cs.js"),[],import.meta.url);case"de-AT":case"de-DE":case"de":return E(()=>import("./de.js"),[],import.meta.url);case"en-AU":case"en-GB":case"en-PH":case"en-US":case"en":return E(()=>import("./en.js"),[],import.meta.url);case"es-ES":case"es":return E(()=>import("./es.js"),[],import.meta.url);case"fr-FR":case"fr-BE":case"fr":return E(()=>import("./fr.js"),[],import.meta.url);case"hu-HU":case"hu":return E(()=>import("./hu.js"),[],import.meta.url);case"it-IT":case"it":return E(()=>import("./it.js"),[],import.meta.url);case"nl-BE":case"nl-NL":case"nl":return E(()=>import("./nl.js"),[],import.meta.url);case"pl-PL":case"pl":return E(()=>import("./pl.js"),[],import.meta.url);case"ro-RO":case"ro":return E(()=>import("./ro.js"),[],import.meta.url);case"ru-RU":case"ru":return E(()=>import("./ru.js"),[],import.meta.url);case"sk-SK":case"sk":return E(()=>import("./sk.js"),[],import.meta.url);case"uk-UA":case"uk":return E(()=>import("./uk.js"),[],import.meta.url);case"zh-CN":case"zh":return E(()=>import("./zh.js"),[],import.meta.url);default:return E(()=>import("./en.js"),[],import.meta.url)}}},...super.localizeNamespaces]}get modelValue(){const e=super.modelValue;return e!==""?e:this.parser(this.value)}set modelValue(e){super.modelValue=e}get value(){return this._inputNode?.value||this.__value||""}set value(e){this._inputNode?(this._inputNode.value=e,this.__value=void 0):this.__value=e}reset(){super.reset(),this.multipleChoice||(this.value=this._initialModelValue),this._resetListboxOptions()}_resetListboxOptions(){this.formElements.forEach((e,t)=>{this._unhighlightMatchedOption(e),!this.showAllOnEmpty||!this.opened?e.style.display="none":(e.style.display="",e.setAttribute("aria-posinset",`${t+1}`),e.setAttribute("aria-setsize",`${this.formElements.length}`),e.removeAttribute("aria-hidden"))})}_inputGroupInputTemplate(){return y`
      <div class="input-group__input">
        <slot name="selection-display"></slot>
        <slot name="input"></slot>
      </div>
    `}_overlayListboxTemplate(){return y`
      <div
        id="overlay-content-node-wrapper"
        role="dialog"
        aria-label="${this.msgLit("lion-combobox:optionsPopup")}"
      >
        <slot name="listbox"></slot>
      </div>
      <slot id="options-outlet"></slot>
    `}_groupTwoTemplate(){return y` ${super._groupTwoTemplate()} ${this._overlayListboxTemplate()}`}get slots(){return{...super.slots,input:()=>{if(this._ariaVersion==="1.1"){const e=document.createElement("div"),t=document.createElement("input");return t.style.cssText=`
          border: none;
          outline: none;
          width: 100%;
          height: 100%;
          font: inherit;
          background: inherit;
          color: inherit;
          border-radius: inherit;
          box-sizing: border-box;
          padding: 0;`,e.appendChild(t),e}return document.createElement("input")},listbox:super.slots.input}}get _comboboxNode(){return this.querySelector('[slot="input"]')}get _selectionDisplayNode(){return this.querySelector('[slot="selection-display"]')}get _inputNode(){return this._ariaVersion==="1.1"&&this._comboboxNode?this._comboboxNode.querySelector("input")||this._comboboxNode:this._comboboxNode}get _overlayContentNode(){return this._listboxNode}get _overlayReferenceNode(){return this.shadowRoot.querySelector(".input-group__container")}get _overlayInvokerNode(){return this._inputNode}get _listboxNode(){return this._overlayCtrl&&this._overlayCtrl.contentNode||Array.from(this.children).find(e=>e.slot==="listbox")}get _activeDescendantOwnerNode(){return this._inputNode}get requireOptionMatch(){return!this.allowCustomChoice}set requireOptionMatch(e){this.allowCustomChoice=!e}constructor(){super(),this.autocomplete="both",this.matchMode="all",this.showAllOnEmpty=!1,this.requireOptionMatch=!0,this.rotateKeyboardNavigation=!0,this.selectionFollowsFocus=!0,this.defaultValidators.push(new Hd),this._ariaVersion=gi.isChromium?"1.1":"1.0",this._listboxReceivesNoFocus=!0,this._noTypeAhead=!0,this.__prevCboxValueNonSelected="",this.__prevCboxValue="",this.__hadUserIntendsInlineAutoFill=!1,this.__listboxContentChanged=!1,this._onKeyUp=this._onKeyUp.bind(this),this._textboxOnClick=this._textboxOnClick.bind(this),this._textboxOnInput=this._textboxOnInput.bind(this),this._textboxOnKeydown=this._textboxOnKeydown.bind(this)}connectedCallback(){super.connectedCallback(),this._selectionDisplayNode&&(this._selectionDisplayNode.comboboxElement=this),(this.disabled||this.readOnly)&&this.__setComboboxDisabledAndReadOnly()}requestUpdate(e,t,s){if(super.requestUpdate(e,t,s),(e==="disabled"||e==="readOnly")&&this.__setComboboxDisabledAndReadOnly(),e==="modelValue"&&this.modelValue&&this.modelValue!==t&&this._syncToTextboxCondition(this.modelValue,this._oldModelValue))if(this.multipleChoice)this._syncToTextboxMultiple(this.modelValue,this._oldModelValue);else{const o=this._getTextboxValueFromOption(this.formElements[this.checkedIndex]);this._setTextboxValue(o)}}parser(e){return this.requireOptionMatch&&this.checkedIndex===-1&&e!==""&&!Array.isArray(e)?new et(e):super.parser(e)}__unsyncCheckedIndexOnInputChange(){const e=this._autoSelectCondition(),t=this.formElements[this.checkedIndex];if(!this.multipleChoice&&!e&&t){const s=this._getTextboxValueFromOption(t);this._inputNode.value.startsWith(s)||this.setCheckedIndex(-1)}}updated(e){super.updated(e),e.has("__shouldAutocompleteNextUpdate")&&this.__unsyncCheckedIndexOnInputChange(),e.has("opened")&&(this.opened&&(this.activeIndex=-1),!this.opened&&e.get("opened")!==void 0&&(this.__onOverlayClose(),this.activeIndex=-1)),e.has("autocomplete")&&this._inputNode.setAttribute("aria-autocomplete",this.autocomplete),e.has("disabled")&&this.setAttribute("aria-disabled",`${this.disabled}`),e.has("__shouldAutocompleteNextUpdate")&&this.__shouldAutocompleteNextUpdate&&(this._handleAutocompletion(),this.__shouldAutocompleteNextUpdate=!1,this.__listboxContentChanged=!1),typeof this._selectionDisplayNode?.onComboboxElementUpdated=="function"&&this._selectionDisplayNode.onComboboxElementUpdated(e)}matchCondition(e,t){let s=-1;const o=this._getTextboxValueFromOption(e);return typeof o=="string"&&typeof t=="string"&&(s=o.toLowerCase().indexOf(t.toLowerCase())),this.matchMode==="all"?s>-1:s===0}_showOverlayCondition({lastKey:e}){const t=["Tab","Escape"],s=["Enter"];return this.disabled||this.readOnly||e&&(t.includes(e)||!this.multipleChoice&&s.includes(e))?!1:this.filled||this.showAllOnEmpty||!this.filled&&this.multipleChoice&&this.__prevCboxValueNonSelected?!0:this.opened}_getTextboxValueFromOption(e){return e?e.choiceValue:this.modelValue instanceof et?this.modelValue.viewValue:this.modelValue}_onListboxContentChanged(){super._onListboxContentChanged(),this.__shouldAutocompleteNextUpdate=!0,this.__listboxContentChanged=!0}_textboxOnInput(e){this.__shouldAutocompleteNextUpdate=!0,this.opened=this._showOverlayCondition({})}_textboxOnKeydown(e){e.key==="Tab"&&(this.opened=!1)}_listboxOnClick(e){super._listboxOnClick(e),this._inputNode.focus(),this.multipleChoice?(this._inputNode.value="",this._resetListboxOptions()):(this.activeIndex=-1,this.opened=!1)}_setTextboxValue(e){this._inputNode&&this._inputNode.value!==e&&(this._inputNode.value=e)}__onOverlayClose(){this.multipleChoice?this._syncToTextboxMultiple(this.modelValue,this._oldModelValue):this.checkedIndex!==-1&&this._syncToTextboxCondition(this.modelValue,this._oldModelValue,{phase:"overlay-close"})&&(this._inputNode.value=this._getTextboxValueFromOption(this.formElements[this.checkedIndex]))}_repropagationCondition(e){return super._repropagationCondition(e)||this.formElements.every(t=>!t.checked)}_onFilterMatch(e,t){this._highlightMatchedOption(e,t),e.style.display=""}_highlightMatchedOption(e,t){if(Hn(e,t),e.textContent){const s=document.createElement("span");s.setAttribute("aria-label",e.textContent.replace(/\s+/g," ")),Array.from(e.childNodes).forEach(o=>{s.appendChild(o)}),e.appendChild(s),ls.set(e,()=>{Array.from(s.childNodes).forEach(o=>{e.appendChild(o)}),e.contains(s)&&e.removeChild(s)})}}_onFilterUnmatch(e,t,s){this._unhighlightMatchedOption(e),e.style.display="none"}_unhighlightMatchedOption(e){qn(e),ls.has(e)&&ls.get(e)()}__computeUserIntendsAutoFill({prevValue:e,curValue:t}){const s=e.length<t.length,o=e.length&&t.length&&e[0].toLowerCase()!==t[0].toLowerCase();return s||o||this.__listboxContentChanged&&this.__hadUserIntendsInlineAutoFill}_handleAutocompletion(){const t=!(this._inputNode.selectionStart===this._inputNode.selectionEnd)&&this._inputNode.value.length!==this._inputNode.selectionStart,s=this._inputNode.value,o=this._inputNode.selectionStart,n=t&&o?s.slice(0,o):s,r=t||this.__hadSelectionLastAutofill?this.__prevCboxValueNonSelected:this.__prevCboxValue,a=!n,c=[];let u=!1;const h=this.__computeUserIntendsAutoFill({prevValue:r,curValue:n}),b=this.autocomplete==="both"||this.autocomplete==="inline",p=this._autoSelectCondition(),f=this.autocomplete==="inline"||this.autocomplete==="none";this.formElements.forEach((v,w)=>{const x=this.matchCondition(v,n);let A=!1;if(a?A=this.showAllOnEmpty:A=f||x,p&&!u&&x&&!v.disabled){const L=()=>{this.activeIndex=w,this.selectionFollowsFocus&&!this.multipleChoice&&this.setCheckedIndex(this.activeIndex),u=!0};if(h)if(b){const N=this._getTextboxValueFromOption(v);N&&typeof N=="string"&&typeof n=="string"&&N.toLowerCase().indexOf(n.toLowerCase())===0&&(this.__textboxInlineComplete(v),L())}else L()}v.onFilterUnmatch?v.onFilterUnmatch(n,r):this._onFilterUnmatch(v,n,r),v.setAttribute("aria-hidden","true"),v.removeAttribute("aria-posinset"),v.removeAttribute("aria-setsize"),A&&(c.push(v),v.onFilterMatch?v.onFilterMatch(n):this._onFilterMatch(v,n))});const _=c.length;c.forEach((v,w)=>{v.setAttribute("aria-posinset",`${w+1}`),v.setAttribute("aria-setsize",`${_}`),v.removeAttribute("aria-hidden")}),p&&!u&&!this.multipleChoice&&(this.setCheckedIndex(-1),r!==n&&(this.activeIndex=-1),this.modelValue=this.parser(s)),this.__prevCboxValueNonSelected=n,this.__prevCboxValue=this._inputNode.value,this.__hadSelectionLastAutofill=this._inputNode.value.length!==this._inputNode.selectionStart,this.__hadUserIntendsInlineAutoFill=h,this._overlayCtrl&&this._overlayCtrl._popper&&this._overlayCtrl._popper.update()}__textboxInlineComplete(e=this.formElements[this.activeIndex]){const t=this._getTextboxValueFromOption(e);if(this._inputNode.value!==t){const s=this._inputNode.value.length;this._inputNode.value=t,this._inputNode.selectionStart=s,this._inputNode.selectionEnd=this._inputNode.value.length}}_autoSelectCondition(){return this.autocomplete==="both"||this.autocomplete==="inline"}_setupListboxNode(){super._setupListboxNode(),this._listboxNode.removeAttribute("tabindex")}_defineOverlayConfig(){return{...Un(),elementToFocusAfterHide:void 0,invokerNode:this._comboboxNode,visibilityTriggerFunction:void 0}}_setupOverlayCtrl(){super._setupOverlayCtrl(),this.__shouldAutocompleteNextUpdate=!0,this.__setupCombobox()}_teardownOverlayCtrl(){super._teardownOverlayCtrl(),this.__teardownCombobox()}_setupOpenCloseListeners(){super._setupOpenCloseListeners(),this._inputNode.addEventListener("keyup",this._onKeyUp),this._inputNode.addEventListener("click",this._textboxOnClick)}_teardownOpenCloseListeners(){super._teardownOpenCloseListeners(),this._inputNode.removeEventListener("keyup",this._onKeyUp),this._inputNode.removeEventListener("click",this._textboxOnClick)}_listboxOnKeyDown(e){const{key:t}=e;switch(t){case"Escape":this.opened=!1,super._listboxOnKeyDown(e),this._setTextboxValue("");break;case"Backspace":case"Delete":this.requireOptionMatch?super._listboxOnKeyDown(e):this.opened=!1;break;case"Enter":this.opened&&e.preventDefault(),!this.requireOptionMatch&&this.multipleChoice&&(!this.formElements[this.activeIndex]||this.formElements[this.activeIndex].hasAttribute("aria-hidden")||!this.opened)?(this.modelValue=this.parser([...this.modelValue,this._inputNode.value]),this._inputNode.value="",this.opened=!1):(super._listboxOnKeyDown(e),this._resetListboxOptions()),this.multipleChoice?this._inputNode.value="":this.opened=!1;break;default:{super._listboxOnKeyDown(e);break}}}_syncToTextboxCondition(e,t,{phase:s}={}){return this.autocomplete==="both"||this.autocomplete==="inline"||!this.focused}_syncToTextboxMultiple(e,t=[]){if(this.requireOptionMatch){const s=e.filter(n=>!t.includes(n)),o=this.formElements.filter(n=>s.includes(n.choiceValue)).map(n=>this._getTextboxValueFromOption(n)).join(" ");this._setTextboxValue(o)}}_enhanceLightDomClasses(){const e=this.querySelector("[slot=input]");e&&e.classList.add("form-control")}__setComboboxDisabledAndReadOnly(){this._comboboxNode&&(this._comboboxNode.toggleAttribute("disabled",this.disabled),this._comboboxNode.setAttribute("aria-disabled",`${this.disabled}`),this._comboboxNode.toggleAttribute("readonly",this.readOnly),this._comboboxNode.setAttribute("aria-readonly",`${this.readOnly}`)),this._inputNode&&(this._inputNode.toggleAttribute("disabled",this.disabled),this._inputNode.toggleAttribute("readOnly",this.readOnly),this._inputNode.setAttribute("aria-readonly",`${this.readOnly}`),this._inputNode.tabIndex=this.disabled?-1:0)}__setupCombobox(){this._comboboxNode.setAttribute("role","combobox"),this._comboboxNode.setAttribute("aria-haspopup","listbox"),this._inputNode.setAttribute("aria-autocomplete",this.autocomplete),this._comboboxNode.setAttribute("aria-controls",this._listboxNode.id),this._ariaVersion==="1.1"?this._comboboxNode.setAttribute("aria-owns",this._listboxNode.id):this._inputNode.setAttribute("aria-owns",this._listboxNode.id),this._listboxNode.setAttribute("aria-labelledby",this._labelNode.id),this._inputNode.addEventListener("keydown",this._listboxOnKeyDown),this._inputNode.addEventListener("input",this._textboxOnInput),this._inputNode.addEventListener("keydown",this._textboxOnKeydown)}__teardownCombobox(){this._inputNode.removeEventListener("keydown",this._listboxOnKeyDown),this._inputNode.removeEventListener("input",this._textboxOnInput),this._inputNode.removeEventListener("keydown",this._textboxOnKeydown)}_onKeyUp(e){const t=e&&e.key;this.opened=this._showOverlayCondition({lastKey:t,currentValue:this._inputNode.value})}_textboxOnClick(e){this.opened=this._showOverlayCondition({})}clear(){this.value="",super.clear(),this.__shouldAutocompleteNextUpdate=!0}}var Kd=S`
  ${Li}

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
    ${zs}
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
`,Gd=class extends jd{static get styles(){return[...super.styles,Kd]}constructor(){super(),this.defaultValidators=[]}_inputGroupInputTemplate(){return y`
      <div class="input-group__input">
        <slot name="input"></slot>
        <craft-icon
          class="indicator"
          name="chevron-down"
          style="font-size: 0.8em"
        ></craft-icon>
      </div>
    `}parser(e){return e===""?super.parser(e):e}_getTextboxValueFromOption(e){return e?e.textContent?.trim()||"":super._getTextboxValueFromOption(e)}};customElements.get("craft-combobox")||customElements.define("craft-combobox",Gd);var si=class extends P{constructor(...e){super(...e),this.variant=we.Default,this.label=null}render(){return y`<span
      class="${le({indicator:!0,"indicator--success":this.variant===we.Success,"indicator--danger":this.variant===we.Danger,"indicator--warning":this.variant===we.Warning,"indicator--info":this.variant===we.Info,"indicator--empty":this.variant==="empty"})}"
    >
      <slot></slot>
    </span>`}};si.styles=[Ws,S`
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
    `],C([m({reflect:!0})],si.prototype,"variant",void 0),C([m()],si.prototype,"label",void 0),customElements.get("craft-indicator")||customElements.define("craft-indicator",si);var Ct=class extends P{constructor(){super(),this.alt=!1,this.shift=!1,this.os="Unknown",this.os=this.detectOS()}connectedCallback(){super.connectedCallback(),this.os==="Unknown"&&(this.os=this.detectOS())}detectOS(){let e=navigator.platform.toLowerCase();return e.includes("mac")||/iphone|ipad|ipod/.test(e)?"Mac":e.includes("win")?"Windows":e.includes("linux")?"Linux":"Unknown"}renderShortcutPrefix(){switch(this.os){case"Mac":return`${this.alt?"⌥":""}${this.shift?"⇧":""}⌘`;case"Linux":return`Super+${this.alt?"Alt+":""}${this.shift?"Shift+":""}`;default:return`Ctrl+${this.alt?"Alt+":""}${this.shift?"Shift+":""}`}}render(){return y`<span class="shortcut"
      >${this.renderShortcutPrefix()}<slot></slot
    ></span>`}};Ct.styles=S`
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
  `,C([m({type:Boolean})],Ct.prototype,"alt",void 0),C([m({type:Boolean})],Ct.prototype,"shift",void 0),C([m()],Ct.prototype,"os",void 0),customElements.get("craft-shortcut")||customElements.define("craft-shortcut",Ct);class Yd extends Mi(_n(P)){connectedCallback(){super.connectedCallback(),this.setAttribute("role","radiogroup")}resetGroup(){let e;this.formElements.forEach(t=>{typeof t.resetGroup=="function"?t.resetGroup():typeof t.reset=="function"&&(t.reset(),t.checked&&(e=t.choiceValue))}),this.modelValue=e,this.resetInteractionState()}}class Zd extends $i(Vi){connectedCallback(){super.connectedCallback(),this.type="radio"}}var Xd=class extends Yd{static get styles(){return[...super.styles,Ht,S`
        .input-group {
          display: grid;
          gap: var(--c-spacing-xs);
        }
      `]}};customElements.get("craft-radio-group")||customElements.define("craft-radio-group",Xd);var Qd=class extends Zd{static get styles(){return[...super.styles,S`
        /* same as checkbox, potentially consolidate */
        :host {
          gap: var(--c-spacing-sm);
        }
      `]}};customElements.get("craft-radio")||customElements.define("craft-radio",Qd);function Jd(i,e){if(typeof d3<"u"&&typeof d3FormatLocaleDefinition<"u")return e===void 0&&(e=",.0f"),d3.formatLocale(d3FormatLocaleDefinition).format(e)(i);let t=typeof i=="string"?parseFloat(i):i;if(isNaN(t))return String(i);if(e){let s=e.includes(","),o=e.match(/\.(\d+)/),n=o?parseInt(o[1],10):0;return new Intl.NumberFormat("en-US",{useGrouping:s,minimumFractionDigits:n,maximumFractionDigits:n}).format(t)}return new Intl.NumberFormat("en-US",{useGrouping:!0,minimumFractionDigits:0,maximumFractionDigits:0}).format(t)}function Ts(i){let e=1,t,s,o=[...i];if((t=s=o.indexOf("{"))===-1)return[i];let n=[o.slice(0,s).join("")];for(;;){let r=o.indexOf("{",s+1),a=o.indexOf("}",s+1);if(r===-1&&a===-1||(r===-1&&(r=o.length),a!==-1&&a>r?(e++,s=r):a!==-1&&(e--,s=a),e===0&&(n.push(o.slice(t+1,s).join("").split(",",3)),t=s+1,n.push(o.slice(t,r===-1?o.length:r).join("")),t=r===-1?o.length:r),e!==0&&(r===-1||a===-1)))break}return e===0?n:!1}function eu(i,e={}){let t=i[0]?.trim();if(!t||e[t]===void 0)return`{${i.join(",")}}`;let s=e[t],o=i[1]===void 0?"none":i[1].trim();switch(o){case"number":return(()=>{let n=i[2]===void 0?null:i[2].trim();if(n!==null&&n!=="integer")throw"Message format 'number' is only supported for integer values.";let r=Jd(s),a;return n===null&&(a=`${s}`.indexOf("."))!==-1&&(r+=`.${s.substring(a+1)}`),r})();case"none":return s;case"select":return(()=>{if(i[2]===void 0)return!1;let n=Ts(i[2]);if(n===!1)return!1;let r=n.length,a=!1;for(let c=0;c+1<r;c++){if(Array.isArray(n[c])||!Array.isArray(n[c+1]))return!1;let u=n[c++].trim();(a===!1&&u==="other"||u==s)&&(a=n[c].join(","))}return a===!1?!1:Ns(a,e)})();case"plural":return(()=>{if(i[2]===void 0)return!1;let n=Ts(i[2]);if(n===!1)return!1;let r=n.length,a=!1,c=0;for(let u=0;u+1<r;u++){if(typeof n[u]=="object"||typeof n[u+1]!="object")return!1;let h=n[u++].trim(),b=[...h];if(u===1&&h.substring(0,7)==="offset:"){let p=[...h.replace(/[\n\r\t]/g," ")].indexOf(" ",7);if(p===-1)throw Error("Message pattern is invalid.");c=parseInt(b.slice(7,p).join("").trim()),h=b.slice(p+1,p+1+b.length).join("").trim()}if(a===!1&&h==="other"||h[0]==="="&&parseInt(b.slice(1,1+b.length).join(""))===s||h==="one"&&s-c===1){let p=n[u];a=(typeof p=="string"?[p]:p).map(f=>f.replace("#",String(s-c))).join(",")}}return a===!1?!1:Ns(a,e)})();default:throw Error(`Message format '${o}' is not supported.`)}}function Ns(i,e){let t;if((t=Ts(i))===!1)throw Error("Message pattern is invalid.");for(let s=0;s<t.length;s++){let o=t[s];if(typeof o=="object"){let n=eu(o,e);if(n===!1)throw Error("Message pattern is invalid.");t[s]=String(n)}}return t.join("")}function tu(i,e,t="app",s){return s&&s[t]!==void 0&&s[t][i]!==void 0&&(i=s[t][i]),e?Ns(i,e):i}var iu=class{constructor(){this.refreshPromise=null,this.tokenName=null,this.tokenValue=null,this.refreshPromise=null}async getToken(){return this.tokenValue||await this.refreshToken(),this.tokenValue}async refreshToken(){return this.refreshPromise||(this.refreshPromise=Mt.get("users/session-info").then(({data:i})=>{let{csrfTokenName:e,csrfTokenValue:t}=i;return this.tokenName=e??null,this.tokenValue=t??null,this.tokenValue}).finally(()=>{this.refreshPromise=null})),this.refreshPromise}clearToken(){this.tokenValue=null}};function su(i=""){return`/admin/actions/${i}`}function ou(){let i={"X-Registered-Asset-Bundles":[...new Set(Craft.registeredAssetBundles)].join(","),"X-Registered-Js-Files":[...new Set(Craft.registeredJsFiles)].join(",")};return Craft.csrfTokenValue&&(i["X-CSRF-Token"]=Craft.csrfTokenValue),i}const Mt=Fs.create({baseURL:su()}),cs=new iu;Mt.interceptors.request.use(async i=>{i.headers.set("X-Requested-With","XMLHttpRequest");let e=ou();if(Object.entries(e).forEach(([t,s])=>{i.headers.set(t,s)}),["post","put","patch","delete"].includes(i.method?.toLowerCase()||"")&&!i.url?.includes("users/session-info")){let t=await cs.getToken();t&&i.headers.set("X-CSRF-Token",t)}return i}),Mt.interceptors.response.use(i=>i,async i=>{let e=i.config;if(i.response?.status===419||i.response?.status===403&&!e._retry){e._retry=!0;try{return cs.clearToken(),e.headers["X-CSRF-Token"]=await cs.refreshToken(),Fs(e)}catch(t){return console.error("Failed to refresh CSRF token:",t),Promise.reject(t)}}return Promise.reject(i)});let di=!1,Qe=null;async function nu(i){if(!di){if(Qe)return Qe;di=!0;try{return(await Mt.post("app/api-headers",void 0,{cancelToken:i})).data}catch{}finally{di=!1}}}const ds=Fs.create({baseURL:"https://api.craftcms.com/v1/"});async function ru(i){return Qe?Object.entries(Qe).forEach(([e,t])=>{i.headers.set(e,t)}):(i.params=i.params||{},i.params.processCraftHeaders=1),i}async function au(i,e){if(Qe)return;let{data:t}=await Mt.post("app/process-api-response-headers",{headers:i},{cancelToken:e});return Qe=t,di=!1,Qe}async function lu(i){return await au(i.headers,i.config.cancelToken),i}ds.interceptors.request.use(async i=>{let{cancelToken:e}=i,t=await nu(e);t&&Object.entries(t).forEach(([o,n])=>{i.headers.set(o,n)});let s={...i,params:{...Craft.apiParams||{},...i.params,v:new Date().getTime()}};return t||(s.params.processCraftHeaders=1),Craft.httpProxy&&(s.proxy=Craft.httpProxy),s}),ds.interceptors.request.use(ru),ds.interceptors.response.use(lu);const Eh=Object.freeze(Object.defineProperty({__proto__:null,default:ye},Symbol.toStringTag,{value:"Module"}));export{tu as i,Eh as n};
