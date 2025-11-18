const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["bg-BG-BqLSXSgK.js","bg-Ch91FBqZ.js","cs-CZ-BOieS6Re.js","cs-Bco-9vYd.js","de-DE-NiEdSbeI.js","de--MUj2jPW.js","en-AU-5SYH9YrO.js","en-QBEFuq4A.js","en-GB-5SYH9YrO.js","en-US-5SYH9YrO.js","es-ES-BzB2G1H7.js","es-QUDKKOEt.js","fr-FR-D8x_WpSN.js","fr-Crw_WS9R.js","fr-BE-D8x_WpSN.js","hu-HU-DzuJRq2x.js","hu-BzLNk3Oy.js","it-IT-BVziFtOr.js","it-Dk-tLV60.js","nl-BE-Cv6cOJ-k.js","nl-ukLmcyhE.js","nl-NL-Cv6cOJ-k.js","pl-PL-C3QXGAg0.js","pl-BsbBHKbu.js","ro-RO-BHOQwu0O.js","ro-BWWeoMIS.js","ru-RU-DCvtZjBo.js","ru-D87QXJFw.js","sk-SK-DaLB_sM8.js","sk-DCOU_ZI_.js","tr-TR-Dhk7tqKh.js","tr-92apvQxK.js","uk-UA-BP_5Rplg.js","uk-CGlal3kJ.js"])))=>i.map(i=>d[i]);
const xn=class extends HTMLElement{constructor(){super(...arguments),this.cookieName=null,this.state="collapsed",this.expanded=!1,this.handleOpen=()=>{this.trigger?.setAttribute("aria-expanded","true"),this.expanded=!0,this.dispatchEvent(new CustomEvent("open")),this.target&&(this.target.dataset.state="expanded"),this.cookieName&&window.Craft?.setCookie(this.cookieName,"expanded")},this.handleClose=()=>{this.trigger?.setAttribute("aria-expanded","false"),this.expanded=!1,this.dispatchEvent(new CustomEvent("close")),this.target&&(this.target.dataset.state="collapsed"),this.cookieName&&window.Craft?.setCookie(this.cookieName,"collapsed")}}get trigger(){return this.querySelector('button[type="button"]')}get target(){if(!this.trigger)return console.warn("No trigger found for disclosure."),null;const e=this.trigger.getAttribute("aria-controls");return e?document.getElementById(e):(console.warn("No target selector found for disclosure."),null)}connectedCallback(){if(!this.trigger){console.error("craft-disclosure elements must include a button",this);return}if(!this.target){console.error(`No target with id ${this.trigger.getAttribute("aria-controls")} found for disclosure. `,this.trigger);return}this.cookieName=this.getAttribute("cookie-name"),this.state=this.getAttribute("state")??"expanded",this.trigger.setAttribute("aria-expanded",this.state==="expanded"?"true":"false"),this.trigger.addEventListener("click",this.toggle.bind(this)),this.state==="expanded"?this.open():this.close()}disconnectedCallback(){this.open(),this.trigger?.removeEventListener("click",this.toggle.bind(this))}attributeChangedCallback(e,t,i){e==="state"&&(i==="expanded"?this.handleOpen():this.handleClose())}toggle(){this.expanded?this.close():this.open()}open(){this.setAttribute("state","expanded")}close(){this.setAttribute("state","collapsed")}};xn.observedAttributes=["state"];let Fl=xn;customElements.get("craft-disclosure")||customElements.define("craft-disclosure",Fl);/**
 * @license
 * Copyright 2019 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const Rs=globalThis,_o=Rs.ShadowRoot&&(Rs.ShadyCSS===void 0||Rs.ShadyCSS.nativeShadow)&&"adoptedStyleSheets"in Document.prototype&&"replace"in CSSStyleSheet.prototype,yo=Symbol(),dr=new WeakMap;let Cn=class{constructor(e,t,i){if(this._$cssResult$=!0,i!==yo)throw Error("CSSResult is not constructable. Use `unsafeCSS` or `css` instead.");this.cssText=e,this.t=t}get styleSheet(){let e=this.o;const t=this.t;if(_o&&e===void 0){const i=t!==void 0&&t.length===1;i&&(e=dr.get(t)),e===void 0&&((this.o=e=new CSSStyleSheet).replaceSync(this.cssText),i&&dr.set(t,e))}return e}toString(){return this.cssText}};const $n=s=>new Cn(typeof s=="string"?s:s+"",void 0,yo),R=(s,...e)=>{const t=s.length===1?s[0]:e.reduce(((i,o,r)=>i+(n=>{if(n._$cssResult$===!0)return n.cssText;if(typeof n=="number")return n;throw Error("Value passed to 'css' function must be a 'css' function result: "+n+". Use 'unsafeCSS' to pass non-literal values, but take care to ensure page security.")})(o)+s[r+1]),s[0]);return new Cn(t,s,yo)},wo=(s,e)=>{if(_o)s.adoptedStyleSheets=e.map((t=>t instanceof CSSStyleSheet?t:t.styleSheet));else for(const t of e){const i=document.createElement("style"),o=Rs.litNonce;o!==void 0&&i.setAttribute("nonce",o),i.textContent=t.cssText,s.appendChild(i)}},hr=_o?s=>s:s=>s instanceof CSSStyleSheet?(e=>{let t="";for(const i of e.cssRules)t+=i.cssText;return $n(t)})(s):s;/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const{is:Pl,defineProperty:Ml,getOwnPropertyDescriptor:Il,getOwnPropertyNames:Dl,getOwnPropertySymbols:Vl,getPrototypeOf:zl}=Object,oi=globalThis,ur=oi.trustedTypes,Bl=ur?ur.emptyScript:"",Ul=oi.reactiveElementPolyfillSupport,Xt=(s,e)=>s,Hs={toAttribute(s,e){switch(e){case Boolean:s=s?Bl:null;break;case Object:case Array:s=s==null?s:JSON.stringify(s)}return s},fromAttribute(s,e){let t=s;switch(e){case Boolean:t=s!==null;break;case Number:t=s===null?null:Number(s);break;case Object:case Array:try{t=JSON.parse(s)}catch{t=null}}return t}},Eo=(s,e)=>!Pl(s,e),pr={attribute:!0,type:String,converter:Hs,reflect:!1,useDefault:!1,hasChanged:Eo};Symbol.metadata??=Symbol("metadata"),oi.litPropertyMetadata??=new WeakMap;let wt=class extends HTMLElement{static addInitializer(e){this._$Ei(),(this.l??=[]).push(e)}static get observedAttributes(){return this.finalize(),this._$Eh&&[...this._$Eh.keys()]}static createProperty(e,t=pr){if(t.state&&(t.attribute=!1),this._$Ei(),this.prototype.hasOwnProperty(e)&&((t=Object.create(t)).wrapped=!0),this.elementProperties.set(e,t),!t.noAccessor){const i=Symbol(),o=this.getPropertyDescriptor(e,i,t);o!==void 0&&Ml(this.prototype,e,o)}}static getPropertyDescriptor(e,t,i){const{get:o,set:r}=Il(this.prototype,e)??{get(){return this[t]},set(n){this[t]=n}};return{get:o,set(n){const a=o?.call(this);r?.call(this,n),this.requestUpdate(e,a,i)},configurable:!0,enumerable:!0}}static getPropertyOptions(e){return this.elementProperties.get(e)??pr}static _$Ei(){if(this.hasOwnProperty(Xt("elementProperties")))return;const e=zl(this);e.finalize(),e.l!==void 0&&(this.l=[...e.l]),this.elementProperties=new Map(e.elementProperties)}static finalize(){if(this.hasOwnProperty(Xt("finalized")))return;if(this.finalized=!0,this._$Ei(),this.hasOwnProperty(Xt("properties"))){const t=this.properties,i=[...Dl(t),...Vl(t)];for(const o of i)this.createProperty(o,t[o])}const e=this[Symbol.metadata];if(e!==null){const t=litPropertyMetadata.get(e);if(t!==void 0)for(const[i,o]of t)this.elementProperties.set(i,o)}this._$Eh=new Map;for(const[t,i]of this.elementProperties){const o=this._$Eu(t,i);o!==void 0&&this._$Eh.set(o,t)}this.elementStyles=this.finalizeStyles(this.styles)}static finalizeStyles(e){const t=[];if(Array.isArray(e)){const i=new Set(e.flat(1/0).reverse());for(const o of i)t.unshift(hr(o))}else e!==void 0&&t.push(hr(e));return t}static _$Eu(e,t){const i=t.attribute;return i===!1?void 0:typeof i=="string"?i:typeof e=="string"?e.toLowerCase():void 0}constructor(){super(),this._$Ep=void 0,this.isUpdatePending=!1,this.hasUpdated=!1,this._$Em=null,this._$Ev()}_$Ev(){this._$ES=new Promise((e=>this.enableUpdating=e)),this._$AL=new Map,this._$E_(),this.requestUpdate(),this.constructor.l?.forEach((e=>e(this)))}addController(e){(this._$EO??=new Set).add(e),this.renderRoot!==void 0&&this.isConnected&&e.hostConnected?.()}removeController(e){this._$EO?.delete(e)}_$E_(){const e=new Map,t=this.constructor.elementProperties;for(const i of t.keys())this.hasOwnProperty(i)&&(e.set(i,this[i]),delete this[i]);e.size>0&&(this._$Ep=e)}createRenderRoot(){const e=this.shadowRoot??this.attachShadow(this.constructor.shadowRootOptions);return wo(e,this.constructor.elementStyles),e}connectedCallback(){this.renderRoot??=this.createRenderRoot(),this.enableUpdating(!0),this._$EO?.forEach((e=>e.hostConnected?.()))}enableUpdating(e){}disconnectedCallback(){this._$EO?.forEach((e=>e.hostDisconnected?.()))}attributeChangedCallback(e,t,i){this._$AK(e,i)}_$ET(e,t){const i=this.constructor.elementProperties.get(e),o=this.constructor._$Eu(e,i);if(o!==void 0&&i.reflect===!0){const r=(i.converter?.toAttribute!==void 0?i.converter:Hs).toAttribute(t,i.type);this._$Em=e,r==null?this.removeAttribute(o):this.setAttribute(o,r),this._$Em=null}}_$AK(e,t){const i=this.constructor,o=i._$Eh.get(e);if(o!==void 0&&this._$Em!==o){const r=i.getPropertyOptions(o),n=typeof r.converter=="function"?{fromAttribute:r.converter}:r.converter?.fromAttribute!==void 0?r.converter:Hs;this._$Em=o;const a=n.fromAttribute(t,r.type);this[o]=a??this._$Ej?.get(o)??a,this._$Em=null}}requestUpdate(e,t,i){if(e!==void 0){const o=this.constructor,r=this[e];if(i??=o.getPropertyOptions(e),!((i.hasChanged??Eo)(r,t)||i.useDefault&&i.reflect&&r===this._$Ej?.get(e)&&!this.hasAttribute(o._$Eu(e,i))))return;this.C(e,t,i)}this.isUpdatePending===!1&&(this._$ES=this._$EP())}C(e,t,{useDefault:i,reflect:o,wrapped:r},n){i&&!(this._$Ej??=new Map).has(e)&&(this._$Ej.set(e,n??t??this[e]),r!==!0||n!==void 0)||(this._$AL.has(e)||(this.hasUpdated||i||(t=void 0),this._$AL.set(e,t)),o===!0&&this._$Em!==e&&(this._$Eq??=new Set).add(e))}async _$EP(){this.isUpdatePending=!0;try{await this._$ES}catch(t){Promise.reject(t)}const e=this.scheduleUpdate();return e!=null&&await e,!this.isUpdatePending}scheduleUpdate(){return this.performUpdate()}performUpdate(){if(!this.isUpdatePending)return;if(!this.hasUpdated){if(this.renderRoot??=this.createRenderRoot(),this._$Ep){for(const[o,r]of this._$Ep)this[o]=r;this._$Ep=void 0}const i=this.constructor.elementProperties;if(i.size>0)for(const[o,r]of i){const{wrapped:n}=r,a=this[o];n!==!0||this._$AL.has(o)||a===void 0||this.C(o,void 0,r,a)}}let e=!1;const t=this._$AL;try{e=this.shouldUpdate(t),e?(this.willUpdate(t),this._$EO?.forEach((i=>i.hostUpdate?.())),this.update(t)):this._$EM()}catch(i){throw e=!1,this._$EM(),i}e&&this._$AE(t)}willUpdate(e){}_$AE(e){this._$EO?.forEach((t=>t.hostUpdated?.())),this.hasUpdated||(this.hasUpdated=!0,this.firstUpdated(e)),this.updated(e)}_$EM(){this._$AL=new Map,this.isUpdatePending=!1}get updateComplete(){return this.getUpdateComplete()}getUpdateComplete(){return this._$ES}shouldUpdate(e){return!0}update(e){this._$Eq&&=this._$Eq.forEach((t=>this._$ET(t,this[t]))),this._$EM()}updated(e){}firstUpdated(e){}};wt.elementStyles=[],wt.shadowRootOptions={mode:"open"},wt[Xt("elementProperties")]=new Map,wt[Xt("finalized")]=new Map,Ul?.({ReactiveElement:wt}),(oi.reactiveElementVersions??=[]).push("2.1.1");/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const ko=globalThis,js=ko.trustedTypes,fr=js?js.createPolicy("lit-html",{createHTML:s=>s}):void 0,An="$lit$",Ge=`lit$${Math.random().toFixed(9).slice(2)}$`,Sn="?"+Ge,Hl=`<${Sn}>`,pt=document,os=()=>pt.createComment(""),rs=s=>s===null||typeof s!="object"&&typeof s!="function",xo=Array.isArray,jl=s=>xo(s)||typeof s?.[Symbol.iterator]=="function",xi=`[ 	
\f\r]`,Ht=/<(?:(!--|\/[^a-zA-Z])|(\/?[a-zA-Z][^>\s]*)|(\/?$))/g,mr=/-->/g,br=/>/g,tt=RegExp(`>|${xi}(?:([^\\s"'>=/]+)(${xi}*=${xi}*(?:[^ 	
\f\r"'\`<>=]|("|')|))|$)`,"g"),gr=/'/g,vr=/"/g,Nn=/^(?:script|style|textarea|title)$/i,ql=s=>(e,...t)=>({_$litType$:s,strings:e,values:t}),k=ql(1),Le=Symbol.for("lit-noChange"),V=Symbol.for("lit-nothing"),_r=new WeakMap,at=pt.createTreeWalker(pt,129);function Tn(s,e){if(!xo(s)||!s.hasOwnProperty("raw"))throw Error("invalid template strings array");return fr!==void 0?fr.createHTML(e):e}const Wl=(s,e)=>{const t=s.length-1,i=[];let o,r=e===2?"<svg>":e===3?"<math>":"",n=Ht;for(let a=0;a<t;a++){const l=s[a];let c,d,u=-1,b=0;for(;b<l.length&&(n.lastIndex=b,d=n.exec(l),d!==null);)b=n.lastIndex,n===Ht?d[1]==="!--"?n=mr:d[1]!==void 0?n=br:d[2]!==void 0?(Nn.test(d[2])&&(o=RegExp("</"+d[2],"g")),n=tt):d[3]!==void 0&&(n=tt):n===tt?d[0]===">"?(n=o??Ht,u=-1):d[1]===void 0?u=-2:(u=n.lastIndex-d[2].length,c=d[1],n=d[3]===void 0?tt:d[3]==='"'?vr:gr):n===vr||n===gr?n=tt:n===mr||n===br?n=Ht:(n=tt,o=void 0);const p=n===tt&&s[a+1].startsWith("/>")?" ":"";r+=n===Ht?l+Hl:u>=0?(i.push(c),l.slice(0,u)+An+l.slice(u)+Ge+p):l+Ge+(u===-2?a:p)}return[Tn(s,r+(s[t]||"<?>")+(e===2?"</svg>":e===3?"</math>":"")),i]};let Gi=class On{constructor({strings:e,_$litType$:t},i){let o;this.parts=[];let r=0,n=0;const a=e.length-1,l=this.parts,[c,d]=Wl(e,t);if(this.el=On.createElement(c,i),at.currentNode=this.el.content,t===2||t===3){const u=this.el.content.firstChild;u.replaceWith(...u.childNodes)}for(;(o=at.nextNode())!==null&&l.length<a;){if(o.nodeType===1){if(o.hasAttributes())for(const u of o.getAttributeNames())if(u.endsWith(An)){const b=d[n++],p=o.getAttribute(u).split(Ge),m=/([.?@])?(.*)/.exec(b);l.push({type:1,index:r,name:m[2],strings:p,ctor:m[1]==="."?Gl:m[1]==="?"?Zl:m[1]==="@"?Yl:ri}),o.removeAttribute(u)}else u.startsWith(Ge)&&(l.push({type:6,index:r}),o.removeAttribute(u));if(Nn.test(o.tagName)){const u=o.textContent.split(Ge),b=u.length-1;if(b>0){o.textContent=js?js.emptyScript:"";for(let p=0;p<b;p++)o.append(u[p],os()),at.nextNode(),l.push({type:2,index:++r});o.append(u[b],os())}}}else if(o.nodeType===8)if(o.data===Sn)l.push({type:2,index:r});else{let u=-1;for(;(u=o.data.indexOf(Ge,u+1))!==-1;)l.push({type:7,index:r}),u+=Ge.length-1}r++}}static createElement(e,t){const i=pt.createElement("template");return i.innerHTML=e,i}};function $t(s,e,t=s,i){if(e===Le)return e;let o=i!==void 0?t._$Co?.[i]:t._$Cl;const r=rs(e)?void 0:e._$litDirective$;return o?.constructor!==r&&(o?._$AO?.(!1),r===void 0?o=void 0:(o=new r(s),o._$AT(s,t,i)),i!==void 0?(t._$Co??=[])[i]=o:t._$Cl=o),o!==void 0&&(e=$t(s,o._$AS(s,e.values),o,i)),e}let Kl=class{constructor(e,t){this._$AV=[],this._$AN=void 0,this._$AD=e,this._$AM=t}get parentNode(){return this._$AM.parentNode}get _$AU(){return this._$AM._$AU}u(e){const{el:{content:t},parts:i}=this._$AD,o=(e?.creationScope??pt).importNode(t,!0);at.currentNode=o;let r=at.nextNode(),n=0,a=0,l=i[0];for(;l!==void 0;){if(n===l.index){let c;l.type===2?c=new Co(r,r.nextSibling,this,e):l.type===1?c=new l.ctor(r,l.name,l.strings,this,e):l.type===6&&(c=new Jl(r,this,e)),this._$AV.push(c),l=i[++a]}n!==l?.index&&(r=at.nextNode(),n++)}return at.currentNode=pt,o}p(e){let t=0;for(const i of this._$AV)i!==void 0&&(i.strings!==void 0?(i._$AI(e,i,t),t+=i.strings.length-2):i._$AI(e[t])),t++}},Co=class Ln{get _$AU(){return this._$AM?._$AU??this._$Cv}constructor(e,t,i,o){this.type=2,this._$AH=V,this._$AN=void 0,this._$AA=e,this._$AB=t,this._$AM=i,this.options=o,this._$Cv=o?.isConnected??!0}get parentNode(){let e=this._$AA.parentNode;const t=this._$AM;return t!==void 0&&e?.nodeType===11&&(e=t.parentNode),e}get startNode(){return this._$AA}get endNode(){return this._$AB}_$AI(e,t=this){e=$t(this,e,t),rs(e)?e===V||e==null||e===""?(this._$AH!==V&&this._$AR(),this._$AH=V):e!==this._$AH&&e!==Le&&this._(e):e._$litType$!==void 0?this.$(e):e.nodeType!==void 0?this.T(e):jl(e)?this.k(e):this._(e)}O(e){return this._$AA.parentNode.insertBefore(e,this._$AB)}T(e){this._$AH!==e&&(this._$AR(),this._$AH=this.O(e))}_(e){this._$AH!==V&&rs(this._$AH)?this._$AA.nextSibling.data=e:this.T(pt.createTextNode(e)),this._$AH=e}$(e){const{values:t,_$litType$:i}=e,o=typeof i=="number"?this._$AC(e):(i.el===void 0&&(i.el=Gi.createElement(Tn(i.h,i.h[0]),this.options)),i);if(this._$AH?._$AD===o)this._$AH.p(t);else{const r=new Kl(o,this),n=r.u(this.options);r.p(t),this.T(n),this._$AH=r}}_$AC(e){let t=_r.get(e.strings);return t===void 0&&_r.set(e.strings,t=new Gi(e)),t}k(e){xo(this._$AH)||(this._$AH=[],this._$AR());const t=this._$AH;let i,o=0;for(const r of e)o===t.length?t.push(i=new Ln(this.O(os()),this.O(os()),this,this.options)):i=t[o],i._$AI(r),o++;o<t.length&&(this._$AR(i&&i._$AB.nextSibling,o),t.length=o)}_$AR(e=this._$AA.nextSibling,t){for(this._$AP?.(!1,!0,t);e!==this._$AB;){const i=e.nextSibling;e.remove(),e=i}}setConnected(e){this._$AM===void 0&&(this._$Cv=e,this._$AP?.(e))}},ri=class{get tagName(){return this.element.tagName}get _$AU(){return this._$AM._$AU}constructor(e,t,i,o,r){this.type=1,this._$AH=V,this._$AN=void 0,this.element=e,this.name=t,this._$AM=o,this.options=r,i.length>2||i[0]!==""||i[1]!==""?(this._$AH=Array(i.length-1).fill(new String),this.strings=i):this._$AH=V}_$AI(e,t=this,i,o){const r=this.strings;let n=!1;if(r===void 0)e=$t(this,e,t,0),n=!rs(e)||e!==this._$AH&&e!==Le,n&&(this._$AH=e);else{const a=e;let l,c;for(e=r[0],l=0;l<r.length-1;l++)c=$t(this,a[i+l],t,l),c===Le&&(c=this._$AH[l]),n||=!rs(c)||c!==this._$AH[l],c===V?e=V:e!==V&&(e+=(c??"")+r[l+1]),this._$AH[l]=c}n&&!o&&this.j(e)}j(e){e===V?this.element.removeAttribute(this.name):this.element.setAttribute(this.name,e??"")}},Gl=class extends ri{constructor(){super(...arguments),this.type=3}j(e){this.element[this.name]=e===V?void 0:e}},Zl=class extends ri{constructor(){super(...arguments),this.type=4}j(e){this.element.toggleAttribute(this.name,!!e&&e!==V)}},Yl=class extends ri{constructor(e,t,i,o,r){super(e,t,i,o,r),this.type=5}_$AI(e,t=this){if((e=$t(this,e,t,0)??V)===Le)return;const i=this._$AH,o=e===V&&i!==V||e.capture!==i.capture||e.once!==i.once||e.passive!==i.passive,r=e!==V&&(i===V||o);o&&this.element.removeEventListener(this.name,this,i),r&&this.element.addEventListener(this.name,this,e),this._$AH=e}handleEvent(e){typeof this._$AH=="function"?this._$AH.call(this.options?.host??this.element,e):this._$AH.handleEvent(e)}},Jl=class{constructor(e,t,i){this.element=e,this.type=6,this._$AN=void 0,this._$AM=t,this.options=i}get _$AU(){return this._$AM._$AU}_$AI(e){$t(this,e)}};const Xl=ko.litHtmlPolyfillSupport;Xl?.(Gi,Co),(ko.litHtmlVersions??=[]).push("3.3.1");const Zi=(s,e,t)=>{const i=t?.renderBefore??e;let o=i._$litPart$;if(o===void 0){const r=t?.renderBefore??null;i._$litPart$=o=new Co(e.insertBefore(os(),r),r,void 0,t??{})}return o._$AI(s),o};/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const $o=globalThis;let G=class extends wt{constructor(){super(...arguments),this.renderOptions={host:this},this._$Do=void 0}createRenderRoot(){const e=super.createRenderRoot();return this.renderOptions.renderBefore??=e.firstChild,e}update(e){const t=this.render();this.hasUpdated||(this.renderOptions.isConnected=this.isConnected),super.update(e),this._$Do=Zi(t,this.renderRoot,this.renderOptions)}connectedCallback(){super.connectedCallback(),this._$Do?.setConnected(!0)}disconnectedCallback(){super.disconnectedCallback(),this._$Do?.setConnected(!1)}render(){return Le}};G._$litElement$=!0,G.finalized=!0,$o.litElementHydrateSupport?.({LitElement:G});const Ql=$o.litElementPolyfillSupport;Ql?.({LitElement:G});($o.litElementVersions??=[]).push("4.2.1");const ec=R`
  :host {
    display: inline-flex;
    justify-content: center;
    --_size: var(--size, 24px);
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
`,Rn=class extends G{render(){return k`
      <div tabindex="-1">
        <div class="spinner"></div>
        <span class="message visually-hidden"><slot /></span>
      </div>
    `}};Rn.styles=[ec];let tc=Rn;customElements.get("craft-spinner")||customElements.define("craft-spinner",tc);const Yi=new Set,xt=new Map;let nt,Ao="ltr",So="en";const Fn=typeof MutationObserver<"u"&&typeof document<"u"&&typeof document.documentElement<"u";if(Fn){const s=new MutationObserver(Mn);Ao=document.documentElement.dir||"ltr",So=document.documentElement.lang||navigator.language,s.observe(document.documentElement,{attributes:!0,attributeFilter:["dir","lang"]})}function Pn(...s){s.map(e=>{const t=e.$code.toLowerCase();xt.has(t)?xt.set(t,Object.assign(Object.assign({},xt.get(t)),e)):xt.set(t,e),nt||(nt=e)}),Mn()}function Mn(){Fn&&(Ao=document.documentElement.dir||"ltr",So=document.documentElement.lang||navigator.language),[...Yi.keys()].map(s=>{typeof s.requestUpdate=="function"&&s.requestUpdate()})}let sc=class{constructor(e){this.host=e,this.host.addController(this)}hostConnected(){Yi.add(this.host)}hostDisconnected(){Yi.delete(this.host)}dir(){return`${this.host.dir||Ao}`.toLowerCase()}lang(){return`${this.host.lang||So}`.toLowerCase()}getTranslationData(e){var t,i;const o=new Intl.Locale(e.replace(/_/g,"-")),r=o?.language.toLowerCase(),n=(i=(t=o?.region)===null||t===void 0?void 0:t.toLowerCase())!==null&&i!==void 0?i:"",a=xt.get(`${r}-${n}`),l=xt.get(r);return{locale:o,language:r,region:n,primary:a,secondary:l}}exists(e,t){var i;const{primary:o,secondary:r}=this.getTranslationData((i=t.lang)!==null&&i!==void 0?i:this.lang());return t=Object.assign({includeFallback:!1},t),!!(o&&o[e]||r&&r[e]||t.includeFallback&&nt&&nt[e])}term(e,...t){const{primary:i,secondary:o}=this.getTranslationData(this.lang());let r;if(i&&i[e])r=i[e];else if(o&&o[e])r=o[e];else if(nt&&nt[e])r=nt[e];else return console.error(`No translation found for: ${String(e)}`),String(e);return typeof r=="function"?r(...t):r}date(e,t){return e=new Date(e),new Intl.DateTimeFormat(this.lang(),t).format(e)}number(e,t){return e=Number(e),isNaN(e)?"":new Intl.NumberFormat(this.lang(),t).format(e)}relativeTime(e,t,i){return new Intl.RelativeTimeFormat(this.lang(),i).format(e,t)}};/*! Copyright 2025 Fonticons, Inc. - https://webawesome.com/license */var In={$code:"en",$name:"English",$dir:"ltr",carousel:"Carousel",clearEntry:"Clear entry",close:"Close",copied:"Copied",copy:"Copy",currentValue:"Current value",error:"Error",goToSlide:(s,e)=>`Go to slide ${s} of ${e}`,hidePassword:"Hide password",loading:"Loading",nextSlide:"Next slide",numOptionsSelected:s=>s===0?"No options selected":s===1?"1 option selected":`${s} options selected`,pauseAnimation:"Pause animation",playAnimation:"Play animation",previousSlide:"Previous slide",progress:"Progress",remove:"Remove",resize:"Resize",scrollableRegion:"Scrollable region",scrollToEnd:"Scroll to end",scrollToStart:"Scroll to start",selectAColorFromTheScreen:"Select a color from the screen",showPassword:"Show password",slideNum:s=>`Slide ${s}`,toggleColorFormat:"Toggle color format",zoomIn:"Zoom in",zoomOut:"Zoom out"};Pn(In);var ic=In;/*! Copyright 2025 Fonticons, Inc. - https://webawesome.com/license */var Mt=class extends sc{};Pn(ic);/*! Copyright 2025 Fonticons, Inc. - https://webawesome.com/license */var ds=class extends Event{constructor(){super("wa-after-hide",{bubbles:!0,cancelable:!1,composed:!0})}},hs=class extends Event{constructor(){super("wa-after-show",{bubbles:!0,cancelable:!1,composed:!0})}},us=class extends Event{constructor(e){super("wa-hide",{bubbles:!0,cancelable:!0,composed:!0}),this.detail=e}},ps=class extends Event{constructor(){super("wa-show",{bubbles:!0,cancelable:!0,composed:!0})}};/*! Copyright 2025 Fonticons, Inc. - https://webawesome.com/license */function de(s,e){return new Promise(t=>{const i=new AbortController,{signal:o}=i;if(s.classList.contains(e))return;s.classList.remove(e),s.classList.add(e);let r=()=>{s.classList.remove(e),t(),i.abort()};s.addEventListener("animationend",r,{once:!0,signal:o}),s.addEventListener("animationcancel",r,{once:!0,signal:o})})}/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const oc={attribute:!0,type:String,converter:Hs,reflect:!1,hasChanged:Eo},rc=(s=oc,e,t)=>{const{kind:i,metadata:o}=t;let r=globalThis.litPropertyMetadata.get(o);if(r===void 0&&globalThis.litPropertyMetadata.set(o,r=new Map),i==="setter"&&((s=Object.create(s)).wrapped=!0),r.set(t.name,s),i==="accessor"){const{name:n}=t;return{set(a){const l=e.get.call(this);e.set.call(this,a),this.requestUpdate(n,l,s)},init(a){return a!==void 0&&this.C(n,void 0,s,a),a}}}if(i==="setter"){const{name:n}=t;return function(a){const l=this[n];e.call(this,a),this.requestUpdate(n,l,s)}}throw Error("Unsupported decorator location: "+i)};function y(s){return(e,t)=>typeof t=="object"?rc(s,e,t):((i,o,r)=>{const n=o.hasOwnProperty(r);return o.constructor.createProperty(r,i),n?Object.getOwnPropertyDescriptor(o,r):void 0})(s,e,t)}/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const Ae=s=>(e,t)=>{t!==void 0?t.addInitializer((()=>{customElements.define(s,e)})):customElements.define(s,e)};/*! Copyright 2025 Fonticons, Inc. - https://webawesome.com/license */var nc=Object.defineProperty,ac=Object.getOwnPropertyDescriptor,Dn=s=>{throw TypeError(s)},v=(s,e,t,i)=>{for(var o=i>1?void 0:i?ac(e,t):e,r=s.length-1,n;r>=0;r--)(n=s[r])&&(o=(i?n(e,t,o):n(o))||o);return i&&o&&nc(e,t,o),o},Vn=(s,e,t)=>e.has(s)||Dn("Cannot "+t),lc=(s,e,t)=>(Vn(s,e,"read from private field"),e.get(s)),cc=(s,e,t)=>e.has(s)?Dn("Cannot add the same private member more than once"):e instanceof WeakSet?e.add(s):e.set(s,t),dc=(s,e,t,i)=>(Vn(s,e,"write to private field"),e.set(s,t),t);/*! Copyright 2025 Fonticons, Inc. - https://webawesome.com/license */var hc=`:host {
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
`,Fs,me=class extends G{constructor(){super(),cc(this,Fs,!1),this.initialReflectedProperties=new Map,this.didSSR=!!this.shadowRoot,this.customStates={set:(t,i)=>{if(this.internals?.states)try{i?this.internals.states.add(t):this.internals.states.delete(t)}catch(o){if(String(o).includes("must start with '--'"))console.error("Your browser implements an outdated version of CustomStateSet. Consider using a polyfill");else throw o}},has:t=>{if(!this.internals?.states)return!1;try{return this.internals.states.has(t)}catch{return!1}}};try{this.internals=this.attachInternals()}catch{console.error("Element internals are not supported in your browser. Consider using a polyfill")}this.customStates.set("wa-defined",!0);let e=this.constructor;for(let[t,i]of e.elementProperties)i.default==="inherit"&&i.initial!==void 0&&typeof t=="string"&&this.customStates.set(`initial-${t}-${i.initial}`,!0)}static get styles(){const e=Array.isArray(this.css)?this.css:this.css?[this.css]:[];return[hc,...e].map(t=>typeof t=="string"?$n(t):t)}attributeChangedCallback(e,t,i){lc(this,Fs)||(this.constructor.elementProperties.forEach((o,r)=>{o.reflect&&this[r]!=null&&this.initialReflectedProperties.set(r,this[r])}),dc(this,Fs,!0)),super.attributeChangedCallback(e,t,i)}willUpdate(e){super.willUpdate(e),this.initialReflectedProperties.forEach((t,i)=>{e.has(i)&&this[i]==null&&(this[i]=t)})}firstUpdated(e){super.firstUpdated(e),this.didSSR&&this.shadowRoot?.querySelectorAll("slot").forEach(t=>{t.dispatchEvent(new Event("slotchange",{bubbles:!0,composed:!1,cancelable:!1}))})}update(e){try{super.update(e)}catch(t){if(this.didSSR&&!this.hasUpdated){const i=new Event("lit-hydration-error",{bubbles:!0,composed:!0,cancelable:!1});i.error=t,this.dispatchEvent(i)}throw t}}relayNativeEvent(e,t){e.stopImmediatePropagation(),this.dispatchEvent(new e.constructor(e.type,{...e,...t}))}};Fs=new WeakMap;v([y()],me.prototype,"dir",2);v([y()],me.prototype,"lang",2);v([y({type:Boolean,reflect:!0,attribute:"did-ssr"})],me.prototype,"didSSR",2);/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const zn=(s,e,t)=>(t.configurable=!0,t.enumerable=!0,Reflect.decorate&&typeof e!="object"&&Object.defineProperty(s,e,t),t);/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */function Q(s,e){return(t,i,o)=>{const r=n=>n.renderRoot?.querySelector(s)??null;return zn(t,i,{get(){return r(this)}})}}/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const No={ATTRIBUTE:1,CHILD:2},To=s=>(...e)=>({_$litDirective$:s,values:e});let Oo=class{constructor(e){}get _$AU(){return this._$AM._$AU}_$AT(e,t,i){this._$Ct=e,this._$AM=t,this._$Ci=i}_$AS(e,t){return this.update(e,t)}update(e,t){return this.render(...t)}};/**
 * @license
 * Copyright 2018 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const Re=To(class extends Oo{constructor(s){if(super(s),s.type!==No.ATTRIBUTE||s.name!=="class"||s.strings?.length>2)throw Error("`classMap()` can only be used in the `class` attribute and must be the only part in the attribute.")}render(s){return" "+Object.keys(s).filter((e=>s[e])).join(" ")+" "}update(s,[e]){if(this.st===void 0){this.st=new Set,s.strings!==void 0&&(this.nt=new Set(s.strings.join(" ").split(/\s/).filter((i=>i!==""))));for(const i in e)e[i]&&!this.nt?.has(i)&&this.st.add(i);return this.render(e)}const t=s.element.classList;for(const i of this.st)i in e||(t.remove(i),this.st.delete(i));for(const i in e){const o=!!e[i];o===this.st.has(i)||this.nt?.has(i)||(o?(t.add(i),this.st.add(i)):(t.remove(i),this.st.delete(i)))}return Le}});/*! Copyright 2025 Fonticons, Inc. - https://webawesome.com/license */var uc=class extends Event{constructor(){super("wa-reposition",{bubbles:!0,cancelable:!1,composed:!0})}};const Ye=Math.min,ve=Math.max,qs=Math.round,As=Math.floor,Oe=s=>({x:s,y:s}),pc={left:"right",right:"left",bottom:"top",top:"bottom"},fc={start:"end",end:"start"};function Ji(s,e,t){return ve(s,Ye(e,t))}function It(s,e){return typeof s=="function"?s(e):s}function Je(s){return s.split("-")[0]}function Dt(s){return s.split("-")[1]}function Bn(s){return s==="x"?"y":"x"}function Lo(s){return s==="y"?"height":"width"}function fs(s){return["top","bottom"].includes(Je(s))?"y":"x"}function Ro(s){return Bn(fs(s))}function mc(s,e,t){t===void 0&&(t=!1);const i=Dt(s),o=Ro(s),r=Lo(o);let n=o==="x"?i===(t?"end":"start")?"right":"left":i==="start"?"bottom":"top";return e.reference[r]>e.floating[r]&&(n=Ws(n)),[n,Ws(n)]}function bc(s){const e=Ws(s);return[Xi(s),e,Xi(e)]}function Xi(s){return s.replace(/start|end/g,e=>fc[e])}function gc(s,e,t){const i=["left","right"],o=["right","left"],r=["top","bottom"],n=["bottom","top"];switch(s){case"top":case"bottom":return t?e?o:i:e?i:o;case"left":case"right":return e?r:n;default:return[]}}function vc(s,e,t,i){const o=Dt(s);let r=gc(Je(s),t==="start",i);return o&&(r=r.map(n=>n+"-"+o),e&&(r=r.concat(r.map(Xi)))),r}function Ws(s){return s.replace(/left|right|bottom|top/g,e=>pc[e])}function _c(s){return{top:0,right:0,bottom:0,left:0,...s}}function Un(s){return typeof s!="number"?_c(s):{top:s,right:s,bottom:s,left:s}}function Ks(s){const{x:e,y:t,width:i,height:o}=s;return{width:i,height:o,top:t,left:e,right:e+i,bottom:t+o,x:e,y:t}}function yr(s,e,t){let{reference:i,floating:o}=s;const r=fs(e),n=Ro(e),a=Lo(n),l=Je(e),c=r==="y",d=i.x+i.width/2-o.width/2,u=i.y+i.height/2-o.height/2,b=i[a]/2-o[a]/2;let p;switch(l){case"top":p={x:d,y:i.y-o.height};break;case"bottom":p={x:d,y:i.y+i.height};break;case"right":p={x:i.x+i.width,y:u};break;case"left":p={x:i.x-o.width,y:u};break;default:p={x:i.x,y:i.y}}switch(Dt(e)){case"start":p[n]-=b*(t&&c?-1:1);break;case"end":p[n]+=b*(t&&c?-1:1);break}return p}const yc=async(s,e,t)=>{const{placement:i="bottom",strategy:o="absolute",middleware:r=[],platform:n}=t,a=r.filter(Boolean),l=await(n.isRTL==null?void 0:n.isRTL(e));let c=await n.getElementRects({reference:s,floating:e,strategy:o}),{x:d,y:u}=yr(c,i,l),b=i,p={},m=0;for(let g=0;g<a.length;g++){const{name:w,fn:E}=a[g],{x,y:A,data:$,reset:z}=await E({x:d,y:u,initialPlacement:i,placement:b,strategy:o,middlewareData:p,rects:c,platform:n,elements:{reference:s,floating:e}});d=x??d,u=A??u,p={...p,[w]:{...p[w],...$}},z&&m<=50&&(m++,typeof z=="object"&&(z.placement&&(b=z.placement),z.rects&&(c=z.rects===!0?await n.getElementRects({reference:s,floating:e,strategy:o}):z.rects),{x:d,y:u}=yr(c,b,l)),g=-1)}return{x:d,y:u,placement:b,strategy:o,middlewareData:p}};async function Fo(s,e){var t;e===void 0&&(e={});const{x:i,y:o,platform:r,rects:n,elements:a,strategy:l}=s,{boundary:c="clippingAncestors",rootBoundary:d="viewport",elementContext:u="floating",altBoundary:b=!1,padding:p=0}=It(e,s),m=Un(p),g=a[b?u==="floating"?"reference":"floating":u],w=Ks(await r.getClippingRect({element:(t=await(r.isElement==null?void 0:r.isElement(g)))==null||t?g:g.contextElement||await(r.getDocumentElement==null?void 0:r.getDocumentElement(a.floating)),boundary:c,rootBoundary:d,strategy:l})),E=u==="floating"?{...n.floating,x:i,y:o}:n.reference,x=await(r.getOffsetParent==null?void 0:r.getOffsetParent(a.floating)),A=await(r.isElement==null?void 0:r.isElement(x))?await(r.getScale==null?void 0:r.getScale(x))||{x:1,y:1}:{x:1,y:1},$=Ks(r.convertOffsetParentRelativeRectToViewportRelativeRect?await r.convertOffsetParentRelativeRectToViewportRelativeRect({elements:a,rect:E,offsetParent:x,strategy:l}):E);return{top:(w.top-$.top+m.top)/A.y,bottom:($.bottom-w.bottom+m.bottom)/A.y,left:(w.left-$.left+m.left)/A.x,right:($.right-w.right+m.right)/A.x}}const wc=s=>({name:"arrow",options:s,async fn(e){const{x:t,y:i,placement:o,rects:r,platform:n,elements:a,middlewareData:l}=e,{element:c,padding:d=0}=It(s,e)||{};if(c==null)return{};const u=Un(d),b={x:t,y:i},p=Ro(o),m=Lo(p),g=await n.getDimensions(c),w=p==="y",E=w?"top":"left",x=w?"bottom":"right",A=w?"clientHeight":"clientWidth",$=r.reference[m]+r.reference[p]-b[p]-r.floating[m],z=b[p]-r.reference[p],M=await(n.getOffsetParent==null?void 0:n.getOffsetParent(c));let D=M?M[A]:0;(!D||!await(n.isElement==null?void 0:n.isElement(M)))&&(D=a.floating[A]||r.floating[m]);const Y=$/2-z/2,U=D/2-g[m]/2-1,q=Ye(u[E],U),Ne=Ye(u[x],U),se=q,h=D-g[m]-Ne,C=D/2-g[m]/2+Y,N=Ji(se,C,h),T=!l.arrow&&Dt(o)!=null&&C!==N&&r.reference[m]/2-(C<se?q:Ne)-g[m]/2<0,F=T?C<se?C-se:C-h:0;return{[p]:b[p]+F,data:{[p]:N,centerOffset:C-N-F,...T&&{alignmentOffset:F}},reset:T}}}),Ec=function(s){return s===void 0&&(s={}),{name:"flip",options:s,async fn(e){var t,i;const{placement:o,middlewareData:r,rects:n,initialPlacement:a,platform:l,elements:c}=e,{mainAxis:d=!0,crossAxis:u=!0,fallbackPlacements:b,fallbackStrategy:p="bestFit",fallbackAxisSideDirection:m="none",flipAlignment:g=!0,...w}=It(s,e);if((t=r.arrow)!=null&&t.alignmentOffset)return{};const E=Je(o),x=Je(a)===a,A=await(l.isRTL==null?void 0:l.isRTL(c.floating)),$=b||(x||!g?[Ws(a)]:bc(a));!b&&m!=="none"&&$.push(...vc(a,g,m,A));const z=[a,...$],M=await Fo(e,w),D=[];let Y=((i=r.flip)==null?void 0:i.overflows)||[];if(d&&D.push(M[E]),u){const se=mc(o,n,A);D.push(M[se[0]],M[se[1]])}if(Y=[...Y,{placement:o,overflows:D}],!D.every(se=>se<=0)){var U,q;const se=(((U=r.flip)==null?void 0:U.index)||0)+1,h=z[se];if(h)return{data:{index:se,overflows:Y},reset:{placement:h}};let C=(q=Y.filter(N=>N.overflows[0]<=0).sort((N,T)=>N.overflows[1]-T.overflows[1])[0])==null?void 0:q.placement;if(!C)switch(p){case"bestFit":{var Ne;const N=(Ne=Y.map(T=>[T.placement,T.overflows.filter(F=>F>0).reduce((F,S)=>F+S,0)]).sort((T,F)=>T[1]-F[1])[0])==null?void 0:Ne[0];N&&(C=N);break}case"initialPlacement":C=a;break}if(o!==C)return{reset:{placement:C}}}return{}}}};async function kc(s,e){const{placement:t,platform:i,elements:o}=s,r=await(i.isRTL==null?void 0:i.isRTL(o.floating)),n=Je(t),a=Dt(t),l=fs(t)==="y",c=["left","top"].includes(n)?-1:1,d=r&&l?-1:1,u=It(e,s);let{mainAxis:b,crossAxis:p,alignmentAxis:m}=typeof u=="number"?{mainAxis:u,crossAxis:0,alignmentAxis:null}:{mainAxis:0,crossAxis:0,alignmentAxis:null,...u};return a&&typeof m=="number"&&(p=a==="end"?m*-1:m),l?{x:p*d,y:b*c}:{x:b*c,y:p*d}}const xc=function(s){return s===void 0&&(s=0),{name:"offset",options:s,async fn(e){var t,i;const{x:o,y:r,placement:n,middlewareData:a}=e,l=await kc(e,s);return n===((t=a.offset)==null?void 0:t.placement)&&(i=a.arrow)!=null&&i.alignmentOffset?{}:{x:o+l.x,y:r+l.y,data:{...l,placement:n}}}}},Cc=function(s){return s===void 0&&(s={}),{name:"shift",options:s,async fn(e){const{x:t,y:i,placement:o}=e,{mainAxis:r=!0,crossAxis:n=!1,limiter:a={fn:w=>{let{x:E,y:x}=w;return{x:E,y:x}}},...l}=It(s,e),c={x:t,y:i},d=await Fo(e,l),u=fs(Je(o)),b=Bn(u);let p=c[b],m=c[u];if(r){const w=b==="y"?"top":"left",E=b==="y"?"bottom":"right",x=p+d[w],A=p-d[E];p=Ji(x,p,A)}if(n){const w=u==="y"?"top":"left",E=u==="y"?"bottom":"right",x=m+d[w],A=m-d[E];m=Ji(x,m,A)}const g=a.fn({...e,[b]:p,[u]:m});return{...g,data:{x:g.x-t,y:g.y-i}}}}},$c=function(s){return s===void 0&&(s={}),{name:"size",options:s,async fn(e){const{placement:t,rects:i,platform:o,elements:r}=e,{apply:n=()=>{},...a}=It(s,e),l=await Fo(e,a),c=Je(t),d=Dt(t),u=fs(t)==="y",{width:b,height:p}=i.floating;let m,g;c==="top"||c==="bottom"?(m=c,g=d===(await(o.isRTL==null?void 0:o.isRTL(r.floating))?"start":"end")?"left":"right"):(g=c,m=d==="end"?"top":"bottom");const w=p-l[m],E=b-l[g],x=!e.middlewareData.shift;let A=w,$=E;if(u){const M=b-l.left-l.right;$=d||x?Ye(E,M):M}else{const M=p-l.top-l.bottom;A=d||x?Ye(w,M):M}if(x&&!d){const M=ve(l.left,0),D=ve(l.right,0),Y=ve(l.top,0),U=ve(l.bottom,0);u?$=b-2*(M!==0||D!==0?M+D:ve(l.left,l.right)):A=p-2*(Y!==0||U!==0?Y+U:ve(l.top,l.bottom))}await n({...e,availableWidth:$,availableHeight:A});const z=await o.getDimensions(r.floating);return b!==z.width||p!==z.height?{reset:{rects:!0}}:{}}}};function ni(){return typeof window<"u"}function Vt(s){return Hn(s)?(s.nodeName||"").toLowerCase():"#document"}function _e(s){var e;return(s==null||(e=s.ownerDocument)==null?void 0:e.defaultView)||window}function Pe(s){var e;return(e=(Hn(s)?s.ownerDocument:s.document)||window.document)==null?void 0:e.documentElement}function Hn(s){return ni()?s instanceof Node||s instanceof _e(s).Node:!1}function ke(s){return ni()?s instanceof Element||s instanceof _e(s).Element:!1}function Fe(s){return ni()?s instanceof HTMLElement||s instanceof _e(s).HTMLElement:!1}function wr(s){return!ni()||typeof ShadowRoot>"u"?!1:s instanceof ShadowRoot||s instanceof _e(s).ShadowRoot}function ms(s){const{overflow:e,overflowX:t,overflowY:i,display:o}=xe(s);return/auto|scroll|overlay|hidden|clip/.test(e+i+t)&&!["inline","contents"].includes(o)}function Ac(s){return["table","td","th"].includes(Vt(s))}function ai(s){return[":popover-open",":modal"].some(e=>{try{return s.matches(e)}catch{return!1}})}function li(s){const e=Po(),t=ke(s)?xe(s):s;return["transform","translate","scale","rotate","perspective"].some(i=>t[i]?t[i]!=="none":!1)||(t.containerType?t.containerType!=="normal":!1)||!e&&(t.backdropFilter?t.backdropFilter!=="none":!1)||!e&&(t.filter?t.filter!=="none":!1)||["transform","translate","scale","rotate","perspective","filter"].some(i=>(t.willChange||"").includes(i))||["paint","layout","strict","content"].some(i=>(t.contain||"").includes(i))}function Sc(s){let e=Xe(s);for(;Fe(e)&&!At(e);){if(li(e))return e;if(ai(e))return null;e=Xe(e)}return null}function Po(){return typeof CSS>"u"||!CSS.supports?!1:CSS.supports("-webkit-backdrop-filter","none")}function At(s){return["html","body","#document"].includes(Vt(s))}function xe(s){return _e(s).getComputedStyle(s)}function ci(s){return ke(s)?{scrollLeft:s.scrollLeft,scrollTop:s.scrollTop}:{scrollLeft:s.scrollX,scrollTop:s.scrollY}}function Xe(s){if(Vt(s)==="html")return s;const e=s.assignedSlot||s.parentNode||wr(s)&&s.host||Pe(s);return wr(e)?e.host:e}function jn(s){const e=Xe(s);return At(e)?s.ownerDocument?s.ownerDocument.body:s.body:Fe(e)&&ms(e)?e:jn(e)}function St(s,e,t){var i;e===void 0&&(e=[]),t===void 0&&(t=!0);const o=jn(s),r=o===((i=s.ownerDocument)==null?void 0:i.body),n=_e(o);if(r){const a=Qi(n);return e.concat(n,n.visualViewport||[],ms(o)?o:[],a&&t?St(a):[])}return e.concat(o,St(o,[],t))}function Qi(s){return s.parent&&Object.getPrototypeOf(s.parent)?s.frameElement:null}function qn(s){const e=xe(s);let t=parseFloat(e.width)||0,i=parseFloat(e.height)||0;const o=Fe(s),r=o?s.offsetWidth:t,n=o?s.offsetHeight:i,a=qs(t)!==r||qs(i)!==n;return a&&(t=r,i=n),{width:t,height:i,$:a}}function Mo(s){return ke(s)?s:s.contextElement}function Ct(s){const e=Mo(s);if(!Fe(e))return Oe(1);const t=e.getBoundingClientRect(),{width:i,height:o,$:r}=qn(e);let n=(r?qs(t.width):t.width)/i,a=(r?qs(t.height):t.height)/o;return(!n||!Number.isFinite(n))&&(n=1),(!a||!Number.isFinite(a))&&(a=1),{x:n,y:a}}const Nc=Oe(0);function Wn(s){const e=_e(s);return!Po()||!e.visualViewport?Nc:{x:e.visualViewport.offsetLeft,y:e.visualViewport.offsetTop}}function Tc(s,e,t){return e===void 0&&(e=!1),!t||e&&t!==_e(s)?!1:e}function ft(s,e,t,i){e===void 0&&(e=!1),t===void 0&&(t=!1);const o=s.getBoundingClientRect(),r=Mo(s);let n=Oe(1);e&&(i?ke(i)&&(n=Ct(i)):n=Ct(s));const a=Tc(r,t,i)?Wn(r):Oe(0);let l=(o.left+a.x)/n.x,c=(o.top+a.y)/n.y,d=o.width/n.x,u=o.height/n.y;if(r){const b=_e(r),p=i&&ke(i)?_e(i):i;let m=b,g=Qi(m);for(;g&&i&&p!==m;){const w=Ct(g),E=g.getBoundingClientRect(),x=xe(g),A=E.left+(g.clientLeft+parseFloat(x.paddingLeft))*w.x,$=E.top+(g.clientTop+parseFloat(x.paddingTop))*w.y;l*=w.x,c*=w.y,d*=w.x,u*=w.y,l+=A,c+=$,m=_e(g),g=Qi(m)}}return Ks({width:d,height:u,x:l,y:c})}function Io(s,e){const t=ci(s).scrollLeft;return e?e.left+t:ft(Pe(s)).left+t}function Kn(s,e,t){t===void 0&&(t=!1);const i=s.getBoundingClientRect(),o=i.left+e.scrollLeft-(t?0:Io(s,i)),r=i.top+e.scrollTop;return{x:o,y:r}}function Oc(s){let{elements:e,rect:t,offsetParent:i,strategy:o}=s;const r=o==="fixed",n=Pe(i),a=e?ai(e.floating):!1;if(i===n||a&&r)return t;let l={scrollLeft:0,scrollTop:0},c=Oe(1);const d=Oe(0),u=Fe(i);if((u||!u&&!r)&&((Vt(i)!=="body"||ms(n))&&(l=ci(i)),Fe(i))){const p=ft(i);c=Ct(i),d.x=p.x+i.clientLeft,d.y=p.y+i.clientTop}const b=n&&!u&&!r?Kn(n,l,!0):Oe(0);return{width:t.width*c.x,height:t.height*c.y,x:t.x*c.x-l.scrollLeft*c.x+d.x+b.x,y:t.y*c.y-l.scrollTop*c.y+d.y+b.y}}function Lc(s){return Array.from(s.getClientRects())}function Rc(s){const e=Pe(s),t=ci(s),i=s.ownerDocument.body,o=ve(e.scrollWidth,e.clientWidth,i.scrollWidth,i.clientWidth),r=ve(e.scrollHeight,e.clientHeight,i.scrollHeight,i.clientHeight);let n=-t.scrollLeft+Io(s);const a=-t.scrollTop;return xe(i).direction==="rtl"&&(n+=ve(e.clientWidth,i.clientWidth)-o),{width:o,height:r,x:n,y:a}}function Fc(s,e){const t=_e(s),i=Pe(s),o=t.visualViewport;let r=i.clientWidth,n=i.clientHeight,a=0,l=0;if(o){r=o.width,n=o.height;const c=Po();(!c||c&&e==="fixed")&&(a=o.offsetLeft,l=o.offsetTop)}return{width:r,height:n,x:a,y:l}}function Pc(s,e){const t=ft(s,!0,e==="fixed"),i=t.top+s.clientTop,o=t.left+s.clientLeft,r=Fe(s)?Ct(s):Oe(1),n=s.clientWidth*r.x,a=s.clientHeight*r.y,l=o*r.x,c=i*r.y;return{width:n,height:a,x:l,y:c}}function Er(s,e,t){let i;if(e==="viewport")i=Fc(s,t);else if(e==="document")i=Rc(Pe(s));else if(ke(e))i=Pc(e,t);else{const o=Wn(s);i={x:e.x-o.x,y:e.y-o.y,width:e.width,height:e.height}}return Ks(i)}function Gn(s,e){const t=Xe(s);return t===e||!ke(t)||At(t)?!1:xe(t).position==="fixed"||Gn(t,e)}function Mc(s,e){const t=e.get(s);if(t)return t;let i=St(s,[],!1).filter(a=>ke(a)&&Vt(a)!=="body"),o=null;const r=xe(s).position==="fixed";let n=r?Xe(s):s;for(;ke(n)&&!At(n);){const a=xe(n),l=li(n);!l&&a.position==="fixed"&&(o=null),(r?!l&&!o:!l&&a.position==="static"&&o&&["absolute","fixed"].includes(o.position)||ms(n)&&!l&&Gn(s,n))?i=i.filter(c=>c!==n):o=a,n=Xe(n)}return e.set(s,i),i}function Ic(s){let{element:e,boundary:t,rootBoundary:i,strategy:o}=s;const r=[...t==="clippingAncestors"?ai(e)?[]:Mc(e,this._c):[].concat(t),i],n=r[0],a=r.reduce((l,c)=>{const d=Er(e,c,o);return l.top=ve(d.top,l.top),l.right=Ye(d.right,l.right),l.bottom=Ye(d.bottom,l.bottom),l.left=ve(d.left,l.left),l},Er(e,n,o));return{width:a.right-a.left,height:a.bottom-a.top,x:a.left,y:a.top}}function Dc(s){const{width:e,height:t}=qn(s);return{width:e,height:t}}function Vc(s,e,t){const i=Fe(e),o=Pe(e),r=t==="fixed",n=ft(s,!0,r,e);let a={scrollLeft:0,scrollTop:0};const l=Oe(0);if(i||!i&&!r)if((Vt(e)!=="body"||ms(o))&&(a=ci(e)),i){const b=ft(e,!0,r,e);l.x=b.x+e.clientLeft,l.y=b.y+e.clientTop}else o&&(l.x=Io(o));const c=o&&!i&&!r?Kn(o,a):Oe(0),d=n.left+a.scrollLeft-l.x-c.x,u=n.top+a.scrollTop-l.y-c.y;return{x:d,y:u,width:n.width,height:n.height}}function Ci(s){return xe(s).position==="static"}function kr(s,e){if(!Fe(s)||xe(s).position==="fixed")return null;if(e)return e(s);let t=s.offsetParent;return Pe(s)===t&&(t=t.ownerDocument.body),t}function Zn(s,e){const t=_e(s);if(ai(s))return t;if(!Fe(s)){let o=Xe(s);for(;o&&!At(o);){if(ke(o)&&!Ci(o))return o;o=Xe(o)}return t}let i=kr(s,e);for(;i&&Ac(i)&&Ci(i);)i=kr(i,e);return i&&At(i)&&Ci(i)&&!li(i)?t:i||Sc(s)||t}const zc=async function(s){const e=this.getOffsetParent||Zn,t=this.getDimensions,i=await t(s.floating);return{reference:Vc(s.reference,await e(s.floating),s.strategy),floating:{x:0,y:0,width:i.width,height:i.height}}};function Bc(s){return xe(s).direction==="rtl"}const Ps={convertOffsetParentRelativeRectToViewportRelativeRect:Oc,getDocumentElement:Pe,getClippingRect:Ic,getOffsetParent:Zn,getElementRects:zc,getClientRects:Lc,getDimensions:Dc,getScale:Ct,isElement:ke,isRTL:Bc};function Yn(s,e){return s.x===e.x&&s.y===e.y&&s.width===e.width&&s.height===e.height}function Uc(s,e){let t=null,i;const o=Pe(s);function r(){var a;clearTimeout(i),(a=t)==null||a.disconnect(),t=null}function n(a,l){a===void 0&&(a=!1),l===void 0&&(l=1),r();const c=s.getBoundingClientRect(),{left:d,top:u,width:b,height:p}=c;if(a||e(),!b||!p)return;const m=As(u),g=As(o.clientWidth-(d+b)),w=As(o.clientHeight-(u+p)),E=As(d),x={rootMargin:-m+"px "+-g+"px "+-w+"px "+-E+"px",threshold:ve(0,Ye(1,l))||1};let A=!0;function $(z){const M=z[0].intersectionRatio;if(M!==l){if(!A)return n();M?n(!1,M):i=setTimeout(()=>{n(!1,1e-7)},1e3)}M===1&&!Yn(c,s.getBoundingClientRect())&&n(),A=!1}try{t=new IntersectionObserver($,{...x,root:o.ownerDocument})}catch{t=new IntersectionObserver($,x)}t.observe(s)}return n(!0),r}function Jn(s,e,t,i){i===void 0&&(i={});const{ancestorScroll:o=!0,ancestorResize:r=!0,elementResize:n=typeof ResizeObserver=="function",layoutShift:a=typeof IntersectionObserver=="function",animationFrame:l=!1}=i,c=Mo(s),d=o||r?[...c?St(c):[],...St(e)]:[];d.forEach(E=>{o&&E.addEventListener("scroll",t,{passive:!0}),r&&E.addEventListener("resize",t)});const u=c&&a?Uc(c,t):null;let b=-1,p=null;n&&(p=new ResizeObserver(E=>{let[x]=E;x&&x.target===c&&p&&(p.unobserve(e),cancelAnimationFrame(b),b=requestAnimationFrame(()=>{var A;(A=p)==null||A.observe(e)})),t()}),c&&!l&&p.observe(c),p.observe(e));let m,g=l?ft(s):null;l&&w();function w(){const E=ft(s);g&&!Yn(g,E)&&t(),g=E,m=requestAnimationFrame(w)}return t(),()=>{var E;d.forEach(x=>{o&&x.removeEventListener("scroll",t),r&&x.removeEventListener("resize",t)}),u?.(),(E=p)==null||E.disconnect(),p=null,l&&cancelAnimationFrame(m)}}const Xn=xc,Qn=Cc,ea=Ec,xr=$c,Hc=wc,ta=(s,e,t)=>{const i=new Map,o={platform:Ps,...t},r={...o.platform,_c:i};return yc(s,e,{...o,platform:r})};function jc(s){return qc(s)}function $i(s){return s.assignedSlot?s.assignedSlot:s.parentNode instanceof ShadowRoot?s.parentNode.host:s.parentNode}function qc(s){for(let e=s;e;e=$i(e))if(e instanceof Element&&getComputedStyle(e).display==="none")return null;for(let e=$i(s);e;e=$i(e)){if(!(e instanceof Element))continue;const t=getComputedStyle(e);if(t.display!=="contents"&&(t.position!=="static"||li(t)||e.tagName==="BODY"))return e}return null}/*! Copyright 2025 Fonticons, Inc. - https://webawesome.com/license */var Wc=`:host {
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
`;function Cr(s){return s!==null&&typeof s=="object"&&"getBoundingClientRect"in s&&("contextElement"in s?s instanceof Element:!0)}var Ss=globalThis?.HTMLElement?.prototype.hasOwnProperty("popover"),K=class extends me{constructor(){super(...arguments),this.localize=new Mt(this),this.active=!1,this.placement="top",this.boundary="viewport",this.distance=0,this.skidding=0,this.arrow=!1,this.arrowPlacement="anchor",this.arrowPadding=10,this.flip=!1,this.flipFallbackPlacements="",this.flipFallbackStrategy="best-fit",this.flipPadding=0,this.shift=!1,this.shiftPadding=0,this.autoSizePadding=0,this.hoverBridge=!1,this.updateHoverBridge=()=>{if(this.hoverBridge&&this.anchorEl){const e=this.anchorEl.getBoundingClientRect(),t=this.popup.getBoundingClientRect(),i=this.placement.includes("top")||this.placement.includes("bottom");let o=0,r=0,n=0,a=0,l=0,c=0,d=0,u=0;i?e.top<t.top?(o=e.left,r=e.bottom,n=e.right,a=e.bottom,l=t.left,c=t.top,d=t.right,u=t.top):(o=t.left,r=t.bottom,n=t.right,a=t.bottom,l=e.left,c=e.top,d=e.right,u=e.top):e.left<t.left?(o=e.right,r=e.top,n=t.left,a=t.top,l=e.right,c=e.bottom,d=t.left,u=t.bottom):(o=t.right,r=t.top,n=e.left,a=e.top,l=t.right,c=t.bottom,d=e.left,u=e.bottom),this.style.setProperty("--hover-bridge-top-left-x",`${o}px`),this.style.setProperty("--hover-bridge-top-left-y",`${r}px`),this.style.setProperty("--hover-bridge-top-right-x",`${n}px`),this.style.setProperty("--hover-bridge-top-right-y",`${a}px`),this.style.setProperty("--hover-bridge-bottom-left-x",`${l}px`),this.style.setProperty("--hover-bridge-bottom-left-y",`${c}px`),this.style.setProperty("--hover-bridge-bottom-right-x",`${d}px`),this.style.setProperty("--hover-bridge-bottom-right-y",`${u}px`)}}}async connectedCallback(){super.connectedCallback(),await this.updateComplete,this.start()}disconnectedCallback(){super.disconnectedCallback(),this.stop()}async updated(e){super.updated(e),e.has("active")&&(this.active?this.start():this.stop()),e.has("anchor")&&this.handleAnchorChange(),this.active&&(await this.updateComplete,this.reposition())}async handleAnchorChange(){if(await this.stop(),this.anchor&&typeof this.anchor=="string"){const e=this.getRootNode();this.anchorEl=e.getElementById(this.anchor)}else this.anchor instanceof Element||Cr(this.anchor)?this.anchorEl=this.anchor:this.anchorEl=this.querySelector('[slot="anchor"]');this.anchorEl instanceof HTMLSlotElement&&(this.anchorEl=this.anchorEl.assignedElements({flatten:!0})[0]),this.anchorEl&&this.start()}start(){!this.anchorEl||!this.active||(this.popup.showPopover?.(),this.cleanup=Jn(this.anchorEl,this.popup,()=>{this.reposition()}))}async stop(){return new Promise(e=>{this.popup.hidePopover?.(),this.cleanup?(this.cleanup(),this.cleanup=void 0,this.removeAttribute("data-current-placement"),this.style.removeProperty("--auto-size-available-width"),this.style.removeProperty("--auto-size-available-height"),requestAnimationFrame(()=>e())):e()})}reposition(){if(!this.active||!this.anchorEl)return;const e=[Xn({mainAxis:this.distance,crossAxis:this.skidding})];this.sync?e.push(xr({apply:({rects:o})=>{const r=this.sync==="width"||this.sync==="both",n=this.sync==="height"||this.sync==="both";this.popup.style.width=r?`${o.reference.width}px`:"",this.popup.style.height=n?`${o.reference.height}px`:""}})):(this.popup.style.width="",this.popup.style.height="");let t;Ss&&!Cr(this.anchor)&&this.boundary==="scroll"&&(t=St(this.anchorEl).filter(o=>o instanceof Element)),this.flip&&e.push(ea({boundary:this.flipBoundary||t,fallbackPlacements:this.flipFallbackPlacements,fallbackStrategy:this.flipFallbackStrategy==="best-fit"?"bestFit":"initialPlacement",padding:this.flipPadding})),this.shift&&e.push(Qn({boundary:this.shiftBoundary||t,padding:this.shiftPadding})),this.autoSize?e.push(xr({boundary:this.autoSizeBoundary||t,padding:this.autoSizePadding,apply:({availableWidth:o,availableHeight:r})=>{this.autoSize==="vertical"||this.autoSize==="both"?this.style.setProperty("--auto-size-available-height",`${r}px`):this.style.removeProperty("--auto-size-available-height"),this.autoSize==="horizontal"||this.autoSize==="both"?this.style.setProperty("--auto-size-available-width",`${o}px`):this.style.removeProperty("--auto-size-available-width")}})):(this.style.removeProperty("--auto-size-available-width"),this.style.removeProperty("--auto-size-available-height")),this.arrow&&e.push(Hc({element:this.arrowEl,padding:this.arrowPadding}));const i=Ss?o=>Ps.getOffsetParent(o,jc):Ps.getOffsetParent;ta(this.anchorEl,this.popup,{placement:this.placement,middleware:e,strategy:Ss?"absolute":"fixed",platform:{...Ps,getOffsetParent:i}}).then(({x:o,y:r,middlewareData:n,placement:a})=>{const l=this.localize.dir()==="rtl",c={top:"bottom",right:"left",bottom:"top",left:"right"}[a.split("-")[0]];if(this.setAttribute("data-current-placement",a),Object.assign(this.popup.style,{left:`${o}px`,top:`${r}px`}),this.arrow){const d=n.arrow.x,u=n.arrow.y;let b="",p="",m="",g="";if(this.arrowPlacement==="start"){const w=typeof d=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"";b=typeof u=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"",p=l?w:"",g=l?"":w}else if(this.arrowPlacement==="end"){const w=typeof d=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"";p=l?"":w,g=l?w:"",m=typeof u=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:""}else this.arrowPlacement==="center"?(g=typeof d=="number"?"calc(50% - var(--arrow-size-diagonal))":"",b=typeof u=="number"?"calc(50% - var(--arrow-size-diagonal))":""):(g=typeof d=="number"?`${d}px`:"",b=typeof u=="number"?`${u}px`:"");Object.assign(this.arrowEl.style,{top:b,right:p,bottom:m,left:g,[c]:"calc(var(--arrow-size-diagonal) * -1)"})}}),requestAnimationFrame(()=>this.updateHoverBridge()),this.dispatchEvent(new uc)}render(){return k`
      <slot name="anchor" @slotchange=${this.handleAnchorChange}></slot>

      <span
        part="hover-bridge"
        class=${Re({"popup-hover-bridge":!0,"popup-hover-bridge-visible":this.hoverBridge&&this.active})}
      ></span>

      <div
        popover="manual"
        part="popup"
        class=${Re({popup:!0,"popup-active":this.active,"popup-fixed":!Ss,"popup-has-arrow":this.arrow})}
      >
        <slot></slot>
        ${this.arrow?k`<div part="arrow" class="arrow" role="presentation"></div>`:""}
      </div>
    `}};K.css=Wc;v([Q(".popup")],K.prototype,"popup",2);v([Q(".arrow")],K.prototype,"arrowEl",2);v([y()],K.prototype,"anchor",2);v([y({type:Boolean,reflect:!0})],K.prototype,"active",2);v([y({reflect:!0})],K.prototype,"placement",2);v([y()],K.prototype,"boundary",2);v([y({type:Number})],K.prototype,"distance",2);v([y({type:Number})],K.prototype,"skidding",2);v([y({type:Boolean})],K.prototype,"arrow",2);v([y({attribute:"arrow-placement"})],K.prototype,"arrowPlacement",2);v([y({attribute:"arrow-padding",type:Number})],K.prototype,"arrowPadding",2);v([y({type:Boolean})],K.prototype,"flip",2);v([y({attribute:"flip-fallback-placements",converter:{fromAttribute:s=>s.split(" ").map(e=>e.trim()).filter(e=>e!==""),toAttribute:s=>s.join(" ")}})],K.prototype,"flipFallbackPlacements",2);v([y({attribute:"flip-fallback-strategy"})],K.prototype,"flipFallbackStrategy",2);v([y({type:Object})],K.prototype,"flipBoundary",2);v([y({attribute:"flip-padding",type:Number})],K.prototype,"flipPadding",2);v([y({type:Boolean})],K.prototype,"shift",2);v([y({type:Object})],K.prototype,"shiftBoundary",2);v([y({attribute:"shift-padding",type:Number})],K.prototype,"shiftPadding",2);v([y({attribute:"auto-size"})],K.prototype,"autoSize",2);v([y()],K.prototype,"sync",2);v([y({type:Object})],K.prototype,"autoSizeBoundary",2);v([y({attribute:"auto-size-padding",type:Number})],K.prototype,"autoSizePadding",2);v([y({attribute:"hover-bridge",type:Boolean})],K.prototype,"hoverBridge",2);K=v([Ae("wa-popup")],K);const Kc="useandom-26T198340PX75pxJACKVERYMINDBUSHWOLF_GQZbfghjklqvwyzrict";let Gc=(s=21)=>{let e="",t=crypto.getRandomValues(new Uint8Array(s|=0));for(;s--;)e+=Kc[t[s]&63];return e};/*! Copyright 2025 Fonticons, Inc. - https://webawesome.com/license */function Do(s=""){return`${s}${Gc()}`}/*! Copyright 2025 Fonticons, Inc. - https://webawesome.com/license */function Gs(s,e){return new Promise(t=>{function i(o){o.target===s&&(s.removeEventListener(e,i),t())}s.addEventListener(e,i)})}/*! Copyright 2025 Fonticons, Inc. - https://webawesome.com/license */function ye(s,e){const t={waitUntilFirstUpdate:!1,...e};return(i,o)=>{const{update:r}=i,n=Array.isArray(s)?s:[s];i.update=function(a){n.forEach(l=>{const c=l;if(a.has(c)){const d=a.get(c),u=this[c];d!==u&&(!t.waitUntilFirstUpdate||this.hasUpdated)&&this[o](d,u)}}),r.call(this,a)}}}/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */function be(s){return y({...s,state:!0,attribute:!1})}/*! Copyright 2025 Fonticons, Inc. - https://webawesome.com/license */var Zc=`:host {
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
`,J=class extends me{constructor(){super(...arguments),this.placement="top",this.disabled=!1,this.distance=8,this.open=!1,this.skidding=0,this.showDelay=150,this.hideDelay=0,this.trigger="hover focus",this.withoutArrow=!1,this.for=null,this.anchor=null,this.eventController=new AbortController,this.handleBlur=()=>{this.hasTrigger("focus")&&this.hide()},this.handleClick=()=>{this.hasTrigger("click")&&(this.open?this.hide():this.show())},this.handleFocus=()=>{this.hasTrigger("focus")&&this.show()},this.handleDocumentKeyDown=e=>{e.key==="Escape"&&(e.stopPropagation(),this.hide())},this.handleMouseOver=()=>{this.hasTrigger("hover")&&(clearTimeout(this.hoverTimeout),this.hoverTimeout=window.setTimeout(()=>this.show(),this.showDelay))},this.handleMouseOut=()=>{this.hasTrigger("hover")&&(clearTimeout(this.hoverTimeout),this.hoverTimeout=window.setTimeout(()=>this.hide(),this.hideDelay))}}connectedCallback(){super.connectedCallback(),this.eventController.signal.aborted&&(this.eventController=new AbortController),this.open&&(this.open=!1,this.updateComplete.then(()=>{this.open=!0})),this.id||(this.id=Do("wa-tooltip-")),this.for&&this.anchor?(this.anchor=null,this.handleForChange()):this.for&&this.handleForChange()}disconnectedCallback(){super.disconnectedCallback(),document.removeEventListener("keydown",this.handleDocumentKeyDown),this.eventController.abort(),this.anchor&&this.removeFromAriaLabelledBy(this.anchor,this.id)}firstUpdated(){this.body.hidden=!this.open,this.open&&(this.popup.active=!0,this.popup.reposition())}hasTrigger(e){return this.trigger.split(" ").includes(e)}addToAriaLabelledBy(e,t){const i=(e.getAttribute("aria-labelledby")||"").split(/\s+/).filter(Boolean);i.includes(t)||(i.push(t),e.setAttribute("aria-labelledby",i.join(" ")))}removeFromAriaLabelledBy(e,t){const i=(e.getAttribute("aria-labelledby")||"").split(/\s+/).filter(Boolean).filter(o=>o!==t);i.length>0?e.setAttribute("aria-labelledby",i.join(" ")):e.removeAttribute("aria-labelledby")}async handleOpenChange(){if(this.open){if(this.disabled)return;const e=new ps;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}document.addEventListener("keydown",this.handleDocumentKeyDown,{signal:this.eventController.signal}),this.body.hidden=!1,this.popup.active=!0,await de(this.popup.popup,"show-with-scale"),this.popup.reposition(),this.dispatchEvent(new hs)}else{const e=new us;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}document.removeEventListener("keydown",this.handleDocumentKeyDown),await de(this.popup.popup,"hide-with-scale"),this.popup.active=!1,this.body.hidden=!0,this.dispatchEvent(new ds)}}handleForChange(){const e=this.getRootNode();if(!e)return;const t=this.for?e.getElementById(this.for):null,i=this.anchor;if(t===i)return;const{signal:o}=this.eventController;t&&(this.addToAriaLabelledBy(t,this.id),t.addEventListener("blur",this.handleBlur,{capture:!0,signal:o}),t.addEventListener("focus",this.handleFocus,{capture:!0,signal:o}),t.addEventListener("click",this.handleClick,{signal:o}),t.addEventListener("mouseover",this.handleMouseOver,{signal:o}),t.addEventListener("mouseout",this.handleMouseOut,{signal:o})),i&&(this.removeFromAriaLabelledBy(i,this.id),i.removeEventListener("blur",this.handleBlur,{capture:!0}),i.removeEventListener("focus",this.handleFocus,{capture:!0}),i.removeEventListener("click",this.handleClick),i.removeEventListener("mouseover",this.handleMouseOver),i.removeEventListener("mouseout",this.handleMouseOut)),this.anchor=t}async handleOptionsChange(){this.hasUpdated&&(await this.updateComplete,this.popup.reposition())}handleDisabledChange(){this.disabled&&this.open&&this.hide()}async show(){if(!this.open)return this.open=!0,Gs(this,"wa-after-show")}async hide(){if(this.open)return this.open=!1,Gs(this,"wa-after-hide")}render(){return k`
      <wa-popup
        part="base"
        exportparts="
          popup:base__popup,
          arrow:base__arrow
        "
        class=${Re({tooltip:!0,"tooltip-open":this.open})}
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
    `}};J.css=Zc;J.dependencies={"wa-popup":K};v([Q("slot:not([name])")],J.prototype,"defaultSlot",2);v([Q(".body")],J.prototype,"body",2);v([Q("wa-popup")],J.prototype,"popup",2);v([y()],J.prototype,"placement",2);v([y({type:Boolean,reflect:!0})],J.prototype,"disabled",2);v([y({type:Number})],J.prototype,"distance",2);v([y({type:Boolean,reflect:!0})],J.prototype,"open",2);v([y({type:Number})],J.prototype,"skidding",2);v([y({attribute:"show-delay",type:Number})],J.prototype,"showDelay",2);v([y({attribute:"hide-delay",type:Number})],J.prototype,"hideDelay",2);v([y()],J.prototype,"trigger",2);v([y({attribute:"without-arrow",type:Boolean,reflect:!0})],J.prototype,"withoutArrow",2);v([y()],J.prototype,"for",2);v([be()],J.prototype,"anchor",2);v([ye("open",{waitUntilFirstUpdate:!0})],J.prototype,"handleOpenChange",1);v([ye("for")],J.prototype,"handleForChange",1);v([ye(["distance","placement","skidding"])],J.prototype,"handleOptionsChange",1);v([ye("disabled")],J.prototype,"handleDisabledChange",1);J=v([Ae("wa-tooltip")],J);let Yc=class extends J{static get styles(){return[J.styles,R`
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
      `]}};customElements.get("c-tooltip")||customElements.define("c-tooltip",Yc);const Jc=R`
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
`;var Xc=R`
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
`,Qc=R`
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
`,sa=Object.defineProperty,$r=Object.getOwnPropertySymbols,ed=Object.prototype.hasOwnProperty,td=Object.prototype.propertyIsEnumerable,ia=s=>{throw TypeError(s)},Ar=(s,e,t)=>e in s?sa(s,e,{enumerable:!0,configurable:!0,writable:!0,value:t}):s[e]=t,sd=(s,e)=>{for(var t in e||(e={}))ed.call(e,t)&&Ar(s,t,e[t]);if($r)for(var t of $r(e))td.call(e,t)&&Ar(s,t,e[t]);return s},oa=(s,e,t,i)=>{for(var o=void 0,r=s.length-1,n;r>=0;r--)(n=s[r])&&(o=n(e,t,o)||o);return o&&sa(e,t,o),o},ra=(s,e,t)=>e.has(s)||ia("Cannot "+t),id=(s,e,t)=>(ra(s,e,"read from private field"),e.get(s)),od=(s,e,t)=>e.has(s)?ia("Cannot add the same private member more than once"):e instanceof WeakSet?e.add(s):e.set(s,t),rd=(s,e,t,i)=>(ra(s,e,"write to private field"),e.set(s,t),t),Ms,bs=class extends G{constructor(){super(),od(this,Ms,!1),this.initialReflectedProperties=new Map,Object.entries(this.constructor.dependencies).forEach(([e,t])=>{this.constructor.define(e,t)})}emit(e,t){const i=new CustomEvent(e,sd({bubbles:!0,cancelable:!1,composed:!0,detail:{}},t));return this.dispatchEvent(i),i}static define(e,t=this,i={}){const o=customElements.get(e);if(!o){try{customElements.define(e,t,i)}catch{customElements.define(e,class extends t{},i)}return}let r=" (unknown version)",n=r;"version"in t&&t.version&&(r=" v"+t.version),"version"in o&&o.version&&(n=" v"+o.version),!(r&&n&&r===n)&&console.warn(`Attempted to register <${e}>${r}, but <${e}>${n} has already been registered.`)}attributeChangedCallback(e,t,i){id(this,Ms)||(this.constructor.elementProperties.forEach((o,r)=>{o.reflect&&this[r]!=null&&this.initialReflectedProperties.set(r,this[r])}),rd(this,Ms,!0)),super.attributeChangedCallback(e,t,i)}willUpdate(e){super.willUpdate(e),this.initialReflectedProperties.forEach((t,i)=>{e.has(i)&&this[i]==null&&(this[i]=t)})}};Ms=new WeakMap;bs.version="2.20.1";bs.dependencies={};oa([y()],bs.prototype,"dir");oa([y()],bs.prototype,"lang");var na=class extends bs{render(){return k` <slot></slot> `}};na.styles=[Qc,Xc];na.define("sl-visually-hidden");var nd=Object.defineProperty,Vo=(s,e,t,i)=>{for(var o=void 0,r=s.length-1,n;r>=0;r--)(n=s[r])&&(o=n(e,t,o)||o);return o&&nd(e,t,o),o};const aa=class extends G{constructor(){super(...arguments),this.isCopying=!1,this.value="",this.disabled=!1}async copyValue(){if(!(this.isCopying||this.disabled)){this.isCopying=!0;try{await navigator.clipboard.writeText(this.value),this.dispatchEvent(new CustomEvent("craft-copy",{bubbles:!0,cancelable:!1,composed:!0,detail:{value:this.value}}))}catch{this.dispatchEvent(new CustomEvent("craft-error",{cancelable:!1,composed:!0,bubbles:!0}))}finally{this.isCopying=!1}}}render(){return k`
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
    `}};aa.styles=[Jc];let di=aa;Vo([be()],di.prototype,"isCopying");Vo([y({type:String})],di.prototype,"value");Vo([y({type:Boolean})],di.prototype,"disabled");customElements.get("craft-copy-button")||customElements.define("craft-copy-button",di);const ad=R`
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
`,ld=R`
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
`;var cd=Object.defineProperty,Ve=(s,e,t,i)=>{for(var o=void 0,r=s.length-1,n;r>=0;r--)(n=s[r])&&(o=n(e,t,o)||o);return o&&cd(e,t,o),o};const Ue={"icon.in":{keyframes:[{scale:.25,opacity:.25},{scale:1,opacity:1}],options:{duration:100}},"icon.out":{keyframes:[{scale:1,opacity:1},{scale:.25,opacity:.25}],options:{duration:100}}},la=class extends G{constructor(){super(),this.status="rest",this.value="",this.disabled=!1,this.feedbackDuration=1e3,this.tooltipLabel="Copy",this.addEventListener("craft-copy",()=>{this.showStatus("success")}),this.addEventListener("craft-error",()=>{this.showStatus("error")})}getId(){return`attribute-${this.value.replace(/([a-z])([A-Z])/g,"$1-$2").replace(/[\s_]+/g,"-").toLowerCase()}`}async showStatus(e){const t=e==="success"?this.successIconEl:this.errorIconEl;this.tooltipLabel=e==="success"?"Copied":"Copy failed",await t.animate(Ue["icon.out"].keyframes,Ue["icon.out"].options),this.copyIconEl.hidden=!0,t.hidden=!1,await t.animate(Ue["icon.in"].keyframes,Ue["icon.in"].options),this.status=e,setTimeout(async()=>{await t.animate(Ue["icon.out"].keyframes,Ue["icon.out"].options),t.hidden=!0,this.copyIconEl.hidden=!1,await this.copyIconEl.animate(Ue["icon.in"].keyframes,Ue["icon.in"].options),this.status="rest",this.tooltipLabel="Copy"},this.feedbackDuration)}render(){return k`
      <c-tooltip for="${this.getId()}">${this.tooltipLabel}</c-tooltip>
      <craft-copy-button
        value="${this.value}"
        id="${this.getId()}"
        class=${Re({"copy-attribute":!0,"copy-attribute--success":this.status==="success","copy-attribute--error":this.status==="error"})}
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
    `}};la.styles=[ld,ad];let Me=la;Ve([be()],Me.prototype,"status");Ve([Q('slot[name="copy-icon"]')],Me.prototype,"copyIconEl");Ve([Q('slot[name="success-icon"]')],Me.prototype,"successIconEl");Ve([Q('slot[name="error-icon"]')],Me.prototype,"errorIconEl");Ve([Q("craft-copy-button")],Me.prototype,"copyButtonEl");Ve([y({type:String})],Me.prototype,"value");Ve([y({type:Boolean,reflect:!0})],Me.prototype,"disabled");Ve([y({attribute:"feedback-duration",type:Number})],Me.prototype,"feedbackDuration");Ve([y({reflect:!1})],Me.prototype,"tooltipLabel");customElements.get("craft-copy-attribute")||customElements.define("craft-copy-attribute",Me);const dd=R`
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

    craft-icon {
      font-size: 0.8em;
    }
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
    gap: 0.25em;
    width: 100%;
  }

  craft-button-group craft-button {
    border-radius: 0;
  }

  craft-button-reset,
  craft-button-submit {
    /* Temporarily make it very obvious when these are used */
    outline: 10px solid red;
  }
`,ca=new WeakMap;function hd(s,e){let t=e;for(;t;){if(ca.get(t)===s)return!0;t=Object.getPrototypeOf(t)}return!1}function te(s){return e=>{if(hd(s,e))return e;const t=s(e);return ca.set(t,s),t}}const ud=s=>class extends s{static get properties(){return{disabled:{type:Boolean,reflect:!0}}}constructor(){super(),this._requestedToBeDisabled=!1,this.__isUserSettingDisabled=!0,this.__restoreDisabledTo=!1,this.disabled=!1}makeRequestToBeDisabled(){this._requestedToBeDisabled===!1&&(this._requestedToBeDisabled=!0,this.__restoreDisabledTo=this.disabled,this.__internalSetDisabled(!0))}retractRequestToBeDisabled(){this._requestedToBeDisabled===!0&&(this._requestedToBeDisabled=!1,this.__internalSetDisabled(this.__restoreDisabledTo))}__internalSetDisabled(e){this.__isUserSettingDisabled=!1,this.disabled=e,this.__isUserSettingDisabled=!0}requestUpdate(e,t,i){super.requestUpdate(e,t,i),e==="disabled"&&(this.__isUserSettingDisabled&&(this.__restoreDisabledTo=this.disabled),this.disabled===!1&&this._requestedToBeDisabled===!0&&this.__internalSetDisabled(!0))}click(){this.disabled||super.click()}},gs=te(ud),pd=s=>class extends gs(s){static get properties(){return{tabIndex:{type:Number,reflect:!0,attribute:"tabindex"}}}constructor(){super(),this.__isUserSettingTabIndex=!0,this.__restoreTabIndexTo=0,this.__internalSetTabIndex(0)}makeRequestToBeDisabled(){super.makeRequestToBeDisabled(),this._requestedToBeDisabled===!1&&this.tabIndex!=null&&(this.__restoreTabIndexTo=this.tabIndex)}retractRequestToBeDisabled(){super.retractRequestToBeDisabled(),this._requestedToBeDisabled===!0&&this.__internalSetTabIndex(this.__restoreTabIndexTo)}static enabledWarnings=super.enabledWarnings?.filter(e=>e!=="change-in-update")||[];__internalSetTabIndex(e){this.__isUserSettingTabIndex=!1,this.tabIndex=e,this.__isUserSettingTabIndex=!0}requestUpdate(e,t,i){super.requestUpdate(e,t,i),e==="disabled"&&(this.disabled?this.__internalSetTabIndex(-1):this.__internalSetTabIndex(this.__restoreTabIndexTo)),e==="tabIndex"&&(this.__isUserSettingTabIndex&&this.tabIndex!=null&&(this.__restoreTabIndexTo=this.tabIndex),this.tabIndex!==-1&&this._requestedToBeDisabled===!0&&this.__internalSetTabIndex(-1))}firstUpdated(e){super.firstUpdated(e),this.disabled&&this.__internalSetTabIndex(-1)}},da=te(pd),Ai=s=>s.key===" "||s.key==="Enter",Sr=s=>s.key===" ";let ha=class extends da(G){static get properties(){return{active:{type:Boolean,reflect:!0},type:{type:String,reflect:!0}}}render(){return k` <div class="button-content"><slot></slot></div> `}static get styles(){return[R`
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
      `]}constructor(){super(),this.type="button",this.active=!1,this.__setupEvents()}connectedCallback(){super.connectedCallback(),this.hasAttribute("role")||this.setAttribute("role","button")}updated(e){super.updated(e),e.has("disabled")&&(this.disabled?this.setAttribute("aria-disabled","true"):this.getAttribute("aria-disabled")!==null&&this.removeAttribute("aria-disabled"))}__setupEvents(){this.addEventListener("mousedown",this.__mousedownHandler),this.addEventListener("keydown",this.__keydownHandler),this.addEventListener("keyup",this.__keyupHandler)}__mousedownHandler(){this.active=!0;const e=()=>{this.active=!1,document.removeEventListener("mouseup",e),this.removeEventListener("mouseup",e)};document.addEventListener("mouseup",e),this.addEventListener("mouseup",e)}__keydownHandler(e){if(this.active||!Ai(e)){Sr(e)&&e.preventDefault();return}Sr(e)&&e.preventDefault(),this.active=!0;const t=i=>{Ai(i)&&(this.active=!1,document.removeEventListener("keyup",t,!0))};document.addEventListener("keyup",t,!0)}__keyupHandler(e){if(Ai(e)){if(e.target&&e.target!==this)return;this.click()}}},fd=class extends ha{constructor(){super(),this.type="reset",this.__setupDelegationInConstructor(),this.__submitAndResetHelperButton=document.createElement("button"),this.__preventEventLeakage=this.__preventEventLeakage.bind(this)}connectedCallback(){super.connectedCallback(),this.updateComplete.then(()=>{this._setupSubmitAndResetHelperOnConnected()})}disconnectedCallback(){super.disconnectedCallback(),this._teardownSubmitAndResetHelperOnDisconnected()}__preventEventLeakage(e){e.target===this.__submitAndResetHelperButton&&e.stopImmediatePropagation()}_setupSubmitAndResetHelperOnConnected(){this.appendChild(this.__submitAndResetHelperButton),this._form=this.__submitAndResetHelperButton.form,this.removeChild(this.__submitAndResetHelperButton),this._form&&this._form.addEventListener("click",this.__preventEventLeakage)}_teardownSubmitAndResetHelperOnDisconnected(){this._form&&this._form.removeEventListener("click",this.__preventEventLeakage)}async __clickDelegationHandler(e){this._form||await this.updateComplete,(this.type==="submit"||this.type==="reset")&&e.target===this&&this._form&&(this.__submitAndResetHelperButton.type=this.type,this._form.appendChild(this.__submitAndResetHelperButton),this.__submitAndResetHelperButton.click(),this._form.removeChild(this.__submitAndResetHelperButton))}__setupDelegationInConstructor(){this.addEventListener("click",this.__clickDelegationHandler,!0)}};const He=new WeakMap;function md(){const s=document.createElement("button");return s.tabIndex=-1,s.type="submit",s.setAttribute("aria-hidden","true"),s.style.cssText=`
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
  `,s}let bd=class extends fd{get _nativeButtonNode(){return He.get(this._form)?.helper||null}constructor(){super(),this.type="submit",this.__implicitSubmitHelperButton=null}_setupSubmitAndResetHelperOnConnected(){if(super._setupSubmitAndResetHelperOnConnected(),!this._form||this.type!=="submit")return;const e=this._form;if(!He.get(this._form)){const t=md(),i=document.createElement("div");i.appendChild(t),He.set(this._form,{lionButtons:new Set,helper:t,observer:new MutationObserver(()=>{e.appendChild(i)})}),e.appendChild(i),He.get(e)?.observer.observe(i,{childList:!0})}He.get(e)?.lionButtons.add(this)}_teardownSubmitAndResetHelperOnDisconnected(){if(super._teardownSubmitAndResetHelperOnDisconnected(),this._form){const e=He.get(this._form);e&&(e.lionButtons.delete(this),e.lionButtons.size||(this._form.contains(e.helper)&&e.helper.remove(),He.get(this._form)?.observer.disconnect(),He.delete(this._form)))}}};var gd=Object.defineProperty,hi=(s,e,t,i)=>{for(var o=void 0,r=s.length-1,n;r>=0;r--)(n=s[r])&&(o=n(e,t,o)||o);return o&&gd(e,t,o),o};let vs=class extends bd{constructor(){super(...arguments),this.appearance="accent",this.variant="default",this.size="medium",this.loading=!1}static get styles(){return[...super.styles,dd]}render(){return k`
      <div class="button-content" part="content">
        <slot name="prefix" class="prefix" part="prefix"></slot>
        <slot class="label" part="label"></slot>
        <slot name="suffix" class="suffix" part="suffix"></slot>
      </div>
      ${this.loading?k`<craft-spinner part="spinner"></craft-spinner>`:V}
    `}};hi([y({reflect:!0})],vs.prototype,"appearance");hi([y({reflect:!0})],vs.prototype,"variant");hi([y({reflect:!0})],vs.prototype,"size");hi([y({reflect:!0,type:Boolean})],vs.prototype,"loading");customElements.get("craft-button")||customElements.define("craft-button",vs);const vd=R`
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
`;var _d=Object.defineProperty,ua=(s,e,t,i)=>{for(var o=void 0,r=s.length-1,n;r>=0;r--)(n=s[r])&&(o=n(e,t,o)||o);return o&&_d(e,t,o),o};const pa=class extends G{constructor(){super(...arguments),this.label=null,this._gradientId=null}connectedCallback(){super.connectedCallback(),this._gradientId=`avatar-gradient-${Math.random().toString(36).slice(2,8)}`}text(){return this.label?this.label.split(" ").map(e=>e.charAt(0).toUpperCase()).join(""):"?"}render(){return k`
      <span class="avatar">
        <svg
          viewBox="0 0 100 100"
          xmlns="http://www.w3.org/2000/svg"
          role="img"
        >
          ${this.label?k`<title>${this.label}</title>`:""}
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
    `}};pa.styles=[vd];let zo=pa;ua([y()],zo.prototype,"label");ua([be()],zo.prototype,"_gradientId");customElements.get("craft-avatar")||customElements.define("craft-avatar",zo);const fa=R`
  font: inherit;
  color: var(--c-input-fg, var(--c-fg-text));
  position: relative;
  min-height: var(--c-input-height, var(--c-size-control-md));
  border: var(--c-input-border, 1px solid var(--c-form-control-border));
  border-radius: var(--c-input-radius, var(--c-radius-sm));
  padding-inline: var(--c-input-spacing-inline, var(--c-spacing-md));
  background-color: var(--c-input-bg, var(--c-form-control-bg));
  box-shadow: var(--c-input-shadow);
`,Bo=R`
  :host(:not([label-sr-only])) .form-field__group-one {
    margin-bottom: var(--c-spacing-sm);
  }

  ::slotted(label) {
    line-height: 1;
    font-weight: bold;
    font-size: var(--text-sm);
  }

  ::slotted([slot='input']) {
    ${fa}
  }

  /* Detect mobile devices and up the font size of inputs to avoid zoom on focus */
  @media (pointer: none), (pointer: coarse) {
    ::slotted([slot='input']) {
      font-size: 1rem;
    }
  }

  .form-field__help-text {
    font-size: 1em;
    color: var(--c-fg-muted);
  }

  .form-field__group-one {
    margin-bottom: var(--c-spacing-sm);
  }
`,yd=R`
  /* If an input has a "size" attribute, it should not grow */
  //:host([size]) ::slotted(.form-control) {
  //  flex: 0 0 auto;
  //}

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
`,wd="modulepreload",Ed=function(s){return"/build/"+s},Nr={},I=function(e,t,i){let o=Promise.resolve();if(t&&t.length>0){let c=function(d){return Promise.all(d.map(u=>Promise.resolve(u).then(b=>({status:"fulfilled",value:b}),b=>({status:"rejected",reason:b}))))};var n=c;document.getElementsByTagName("link");const a=document.querySelector("meta[property=csp-nonce]"),l=a?.nonce||a?.getAttribute("nonce");o=c(t.map(d=>{if(d=Ed(d),d in Nr)return;Nr[d]=!0;const u=d.endsWith(".css"),b=u?'[rel="stylesheet"]':"";if(document.querySelector(`link[href="${d}"]${b}`))return;const p=document.createElement("link");if(p.rel=u?"stylesheet":wd,u||(p.as="script"),p.crossOrigin="",p.href=d,l&&p.setAttribute("nonce",l),document.head.appendChild(p),u)return new Promise((m,g)=>{p.addEventListener("load",m),p.addEventListener("error",()=>g(new Error(`Unable to preload CSS for ${d}`)))})}))}function r(a){const l=new Event("vite:preloadError",{cancelable:!0});if(l.payload=a,window.dispatchEvent(l),!l.defaultPrevented)throw a}return o.then(a=>{for(const l of a||[])l.status==="rejected"&&r(l.reason);return e().catch(r)})};/**
 * @license
 * Copyright 2020 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const kd=s=>s===null||typeof s!="object"&&typeof s!="function",ma=(s,e)=>s?._$litType$!==void 0,xd=s=>s.strings===void 0;function Uo(s=""){return`${s.length>0?`${s}-`:""}${Math.random().toString(36).substr(2,10)}`}function Cd(s){return s instanceof Node?"node":ma(s)?"template-result":!Array.isArray(s)&&typeof s=="object"&&"template"in s?"slot-rerender-object":null}const $d=s=>class extends s{get slots(){return{}}constructor(){super(),this.__renderMetaPerSlot=new Map,this.__slotsThatNeedRerender=new Set,this.__slotsProvidedByUserOnFirstConnected=new Set,this.__privateSlots=new Set}connectedCallback(){super.connectedCallback(),this._connectSlotMixin()}__rerenderSlot(e){const t=this.slots[e]();this.__renderTemplateInScopedContext({renderAsDirectHostChild:t.renderAsDirectHostChild,template:t.template,slotName:e}),t.afterRender?.()}update(e){super.update(e);for(const t of this.__slotsThatNeedRerender)this.__rerenderSlot(t)}__renderTemplateInScopedContext({template:e,slotName:t,renderAsDirectHostChild:i}){if(!this.__renderMetaPerSlot.has(t)){const c=!!ShadowRoot.prototype.createElement;this.shadowRoot||console.error("[SlotMixin] No shadowRoot was found");const d=(c?this.shadowRoot:document).createElement("div"),u=document.createComment(`_start_slot_${t}_`),b=document.createComment(`_end_slot_${t}_`);d.appendChild(u),d.appendChild(b);const{creationScope:p,host:m}=this.renderOptions;if(Zi(e,d,{renderBefore:b,creationScope:p,host:m}),i){const g=Array.from(d.childNodes);this.__appendNodes({nodes:g,renderParent:this,slotName:t})}else d.slot=t,this.appendChild(d);this.__renderMetaPerSlot.set(t,{renderTargetThatRespectsShadowRootScoping:d,renderBefore:b});return}const{renderBefore:o,renderTargetThatRespectsShadowRootScoping:r}=this.__renderMetaPerSlot.get(t),n=i?this:r,{creationScope:a,host:l}=this.renderOptions;Zi(e,n,{creationScope:a,host:l,renderBefore:o}),i&&o.previousElementSibling&&!o.previousElementSibling.slot&&(o.previousElementSibling.slot=t)}__appendNodes({nodes:e,renderParent:t=this,slotName:i}){for(const o of e)o instanceof Element&&i&&i!==""&&o.setAttribute("slot",i),t.appendChild(o)}__initSlots(e){for(const t of e){if(this.__slotsProvidedByUserOnFirstConnected.has(t))continue;const i=this.slots[t]();if(i!==void 0)switch(this.__isConnectedSlotMixin||this.__privateSlots.add(t),Cd(i)){case"template-result":this.__renderTemplateInScopedContext({template:i,renderAsDirectHostChild:!0,slotName:t});break;case"node":this.__appendNodes({nodes:[i],renderParent:this,slotName:t});break;case"slot-rerender-object":this.__slotsThatNeedRerender.add(t),i.firstRenderOnConnected&&this.__rerenderSlot(t);break;default:throw new Error(`Slot "${t}" configured inside "get slots()" (in prototype) of ${this.constructor.name} may return these types: TemplateResult | Node | {template:TemplateResult, afterRender?:function} | undefined.
              You provided: ${i}`)}}}_connectSlotMixin(){if(this.__isConnectedSlotMixin)return;const e=Object.keys(this.slots);for(const t of e)(t===""?Array.from(this.children).find(i=>!i.hasAttribute("slot")):Array.from(this.children).find(i=>i.slot===t))&&this.__slotsProvidedByUserOnFirstConnected.add(t);this.__initSlots(e),this.__isConnectedSlotMixin=!0}_isPrivateSlot(e){return this.__privateSlots.has(e)}},Qe=te($d);function ba(s,e){return e={exports:{}},s(e,e.exports),e.exports}var st="long",je="short",Si="narrow",H="numeric",qe="2-digit",We={number:{decimal:{style:"decimal"},integer:{style:"decimal",maximumFractionDigits:0},currency:{style:"currency",currency:"USD"},percent:{style:"percent"},default:{style:"decimal"}},date:{short:{month:H,day:H,year:qe},medium:{month:je,day:H,year:H},long:{month:st,day:H,year:H},full:{month:st,day:H,year:H,weekday:st},default:{month:je,day:H,year:H}},time:{short:{hour:H,minute:H},medium:{hour:H,minute:H,second:H},long:{hour:H,minute:H,second:H,timeZoneName:je},full:{hour:H,minute:H,second:H,timeZoneName:je},default:{hour:H,minute:H,second:H}},duration:{default:{hours:{minimumIntegerDigits:1,maximumFractionDigits:0},minutes:{minimumIntegerDigits:2,maximumFractionDigits:0},seconds:{minimumIntegerDigits:2,maximumFractionDigits:3}}},parseNumberPattern:function(s){if(s){var e={},t=s.match(/\b[A-Z]{3}\b/i),i=s.replace(/[^¤]/g,"").length;if(!i&&t&&(i=1),i?(e.style="currency",e.currencyDisplay=i===1?"symbol":i===2?"code":"name",e.currency=t?t[0].toUpperCase():"USD"):s.indexOf("%")>=0&&(e.style="percent"),!/[@#0]/.test(s))return e.style?e:void 0;if(e.useGrouping=s.indexOf(",")>=0,/E\+?[@#0]+/i.test(s)||s.indexOf("@")>=0){var o=s.replace(/E\+?[@#0]+|[^@#0]/gi,"");e.minimumSignificantDigits=Math.min(Math.max(o.replace(/[^@0]/g,"").length,1),21),e.maximumSignificantDigits=Math.min(Math.max(o.length,1),21)}else{for(var r=s.replace(/[^#0.]/g,"").split("."),n=r[0],a=n.length-1;n[a]==="0";)--a;e.minimumIntegerDigits=Math.min(Math.max(n.length-1-a,1),21);var l=r[1]||"";for(a=0;l[a]==="0";)++a;for(e.minimumFractionDigits=Math.min(Math.max(a,0),20);l[a]==="#";)++a;e.maximumFractionDigits=Math.min(Math.max(a,0),20)}return e}},parseDatePattern:function(s){if(s){for(var e={},t=0;t<s.length;){for(var i=s[t],o=1;s[++t]===i;)++o;switch(i){case"G":e.era=o===5?Si:o===4?st:je;break;case"y":case"Y":e.year=o===2?qe:H;break;case"M":case"L":o=Math.min(Math.max(o-1,0),4),e.month=[H,qe,je,st,Si][o];break;case"E":case"e":case"c":e.weekday=o===5?Si:o===4?st:je;break;case"d":case"D":e.day=o===2?qe:H;break;case"h":case"K":e.hour12=!0,e.hour=o===2?qe:H;break;case"H":case"k":e.hour12=!1,e.hour=o===2?qe:H;break;case"m":e.minute=o===2?qe:H;break;case"s":case"S":e.second=o===2?qe:H;break;case"z":case"Z":case"v":case"V":e.timeZoneName=o===1?je:st;break}}return Object.keys(e).length?e:void 0}}},Ad=function(s,e){if(typeof s=="string"&&e[s])return s;for(var t=[].concat(s||[]),i=0,o=t.length;i<o;++i)for(var r=t[i].split("-");r.length;){var n=r.join("-");if(e[n])return n;r.pop()}},_t="zero",L="one",oe="two",W="few",ee="many",O="other",f=[function(s){var e=+s;return e===1?L:O},function(s){var e=+s;return 0<=e&&e<=1?L:O},function(s){var e=Math.floor(Math.abs(+s)),t=+s;return e===0||t===1?L:O},function(s){var e=+s;return e===0?_t:e===1?L:e===2?oe:3<=e%100&&e%100<=10?W:11<=e%100&&e%100<=99?ee:O},function(s){var e=Math.floor(Math.abs(+s)),t=(s+".").split(".")[1].length;return e===1&&t===0?L:O},function(s){var e=+s;return e%10===1&&e%100!==11?L:2<=e%10&&e%10<=4&&(e%100<12||14<e%100)?W:e%10===0||5<=e%10&&e%10<=9||11<=e%100&&e%100<=14?ee:O},function(s){var e=+s;return e%10===1&&e%100!==11&&e%100!==71&&e%100!==91?L:e%10===2&&e%100!==12&&e%100!==72&&e%100!==92?oe:(3<=e%10&&e%10<=4||e%10===9)&&(e%100<10||19<e%100)&&(e%100<70||79<e%100)&&(e%100<90||99<e%100)?W:e!==0&&e%1e6===0?ee:O},function(s){var e=Math.floor(Math.abs(+s)),t=(s+".").split(".")[1].length,i=+(s+".").split(".")[1];return t===0&&e%10===1&&e%100!==11||i%10===1&&i%100!==11?L:t===0&&2<=e%10&&e%10<=4&&(e%100<12||14<e%100)||2<=i%10&&i%10<=4&&(i%100<12||14<i%100)?W:O},function(s){var e=Math.floor(Math.abs(+s)),t=(s+".").split(".")[1].length;return e===1&&t===0?L:2<=e&&e<=4&&t===0?W:t!==0?ee:O},function(s){var e=+s;return e===0?_t:e===1?L:e===2?oe:e===3?W:e===6?ee:O},function(s){var e=Math.floor(Math.abs(+s)),t=+(""+s).replace(/^[^.]*.?|0+$/g,""),i=+s;return i===1||t!==0&&(e===0||e===1)?L:O},function(s){var e=Math.floor(Math.abs(+s)),t=(s+".").split(".")[1].length,i=+(s+".").split(".")[1];return t===0&&e%100===1||i%100===1?L:t===0&&e%100===2||i%100===2?oe:t===0&&3<=e%100&&e%100<=4||3<=i%100&&i%100<=4?W:O},function(s){var e=Math.floor(Math.abs(+s));return e===0||e===1?L:O},function(s){var e=Math.floor(Math.abs(+s)),t=(s+".").split(".")[1].length,i=+(s+".").split(".")[1];return t===0&&(e===1||e===2||e===3)||t===0&&e%10!==4&&e%10!==6&&e%10!==9||t!==0&&i%10!==4&&i%10!==6&&i%10!==9?L:O},function(s){var e=+s;return e===1?L:e===2?oe:3<=e&&e<=6?W:7<=e&&e<=10?ee:O},function(s){var e=+s;return e===1||e===11?L:e===2||e===12?oe:3<=e&&e<=10||13<=e&&e<=19?W:O},function(s){var e=Math.floor(Math.abs(+s)),t=(s+".").split(".")[1].length;return t===0&&e%10===1?L:t===0&&e%10===2?oe:t===0&&(e%100===0||e%100===20||e%100===40||e%100===60||e%100===80)?W:t!==0?ee:O},function(s){var e=Math.floor(Math.abs(+s)),t=(s+".").split(".")[1].length,i=+s;return e===1&&t===0?L:e===2&&t===0?oe:t===0&&(i<0||10<i)&&i%10===0?ee:O},function(s){var e=Math.floor(Math.abs(+s)),t=+(""+s).replace(/^[^.]*.?|0+$/g,"");return t===0&&e%10===1&&e%100!==11||t!==0?L:O},function(s){var e=+s;return e===1?L:e===2?oe:O},function(s){var e=+s;return e===0?_t:e===1?L:O},function(s){var e=Math.floor(Math.abs(+s)),t=+s;return t===0?_t:(e===0||e===1)&&t!==0?L:O},function(s){var e=+(s+".").split(".")[1],t=+s;return t%10===1&&(t%100<11||19<t%100)?L:2<=t%10&&t%10<=9&&(t%100<11||19<t%100)?W:e!==0?ee:O},function(s){var e=(s+".").split(".")[1].length,t=+(s+".").split(".")[1],i=+s;return i%10===0||11<=i%100&&i%100<=19||e===2&&11<=t%100&&t%100<=19?_t:i%10===1&&i%100!==11||e===2&&t%10===1&&t%100!==11||e!==2&&t%10===1?L:O},function(s){var e=Math.floor(Math.abs(+s)),t=(s+".").split(".")[1].length,i=+(s+".").split(".")[1];return t===0&&e%10===1&&e%100!==11||i%10===1&&i%100!==11?L:O},function(s){var e=Math.floor(Math.abs(+s)),t=(s+".").split(".")[1].length,i=+s;return e===1&&t===0?L:t!==0||i===0||i!==1&&1<=i%100&&i%100<=19?W:O},function(s){var e=+s;return e===1?L:e===0||2<=e%100&&e%100<=10?W:11<=e%100&&e%100<=19?ee:O},function(s){var e=Math.floor(Math.abs(+s)),t=(s+".").split(".")[1].length;return e===1&&t===0?L:t===0&&2<=e%10&&e%10<=4&&(e%100<12||14<e%100)?W:t===0&&e!==1&&0<=e%10&&e%10<=1||t===0&&5<=e%10&&e%10<=9||t===0&&12<=e%100&&e%100<=14?ee:O},function(s){var e=Math.floor(Math.abs(+s));return 0<=e&&e<=1?L:O},function(s){var e=Math.floor(Math.abs(+s)),t=(s+".").split(".")[1].length;return t===0&&e%10===1&&e%100!==11?L:t===0&&2<=e%10&&e%10<=4&&(e%100<12||14<e%100)?W:t===0&&e%10===0||t===0&&5<=e%10&&e%10<=9||t===0&&11<=e%100&&e%100<=14?ee:O},function(s){var e=Math.floor(Math.abs(+s)),t=+s;return e===0||t===1?L:2<=t&&t<=10?W:O},function(s){var e=Math.floor(Math.abs(+s)),t=+(s+".").split(".")[1],i=+s;return i===0||i===1||e===0&&t===1?L:O},function(s){var e=Math.floor(Math.abs(+s)),t=(s+".").split(".")[1].length;return t===0&&e%100===1?L:t===0&&e%100===2?oe:t===0&&3<=e%100&&e%100<=4||t!==0?W:O},function(s){var e=+s;return 0<=e&&e<=1||11<=e&&e<=99?L:O},function(s){var e=+s;return e===1||e===5||e===7||e===8||e===9||e===10?L:e===2||e===3?oe:e===4?W:e===6?ee:O},function(s){var e=Math.floor(Math.abs(+s));return e%10===1||e%10===2||e%10===5||e%10===7||e%10===8||e%100===20||e%100===50||e%100===70||e%100===80?L:e%10===3||e%10===4||e%1e3===100||e%1e3===200||e%1e3===300||e%1e3===400||e%1e3===500||e%1e3===600||e%1e3===700||e%1e3===800||e%1e3===900?W:e===0||e%10===6||e%100===40||e%100===60||e%100===90?ee:O},function(s){var e=+s;return(e%10===2||e%10===3)&&e%100!==12&&e%100!==13?W:O},function(s){var e=+s;return e===1||e===3?L:e===2?oe:e===4?W:O},function(s){var e=+s;return e===0||e===7||e===8||e===9?_t:e===1?L:e===2?oe:e===3||e===4?W:e===5||e===6?ee:O},function(s){var e=+s;return e%10===1&&e%100!==11?L:e%10===2&&e%100!==12?oe:e%10===3&&e%100!==13?W:O},function(s){var e=+s;return e===1||e===11?L:e===2||e===12?oe:e===3||e===13?W:O},function(s){var e=+s;return e===1?L:e===2||e===3?oe:e===4?W:e===6?ee:O},function(s){var e=+s;return e===1||e===5?L:O},function(s){var e=+s;return e===11||e===8||e===80||e===800?ee:O},function(s){var e=Math.floor(Math.abs(+s));return e===1?L:e===0||2<=e%100&&e%100<=20||e%100===40||e%100===60||e%100===80?ee:O},function(s){var e=+s;return e%10===6||e%10===9||e%10===0&&e!==0?ee:O},function(s){var e=Math.floor(Math.abs(+s));return e%10===1&&e%100!==11?L:e%10===2&&e%100!==12?oe:(e%10===7||e%10===8)&&e%100!==17&&e%100!==18?ee:O},function(s){var e=+s;return e===1?L:e===2||e===3?oe:e===4?W:O},function(s){var e=+s;return 1<=e&&e<=4?L:O},function(s){var e=+s;return e===1||e===5||7<=e&&e<=9?L:e===2||e===3?oe:e===4?W:e===6?ee:O},function(s){var e=+s;return e===1?L:e%10===4&&e%100!==14?ee:O},function(s){var e=+s;return(e%10===1||e%10===2)&&e%100!==11&&e%100!==12?L:O},function(s){var e=+s;return e%10===6||e%10===9||e===10?W:O},function(s){var e=+s;return e%10===3&&e%100!==13?W:O}],eo={af:{cardinal:f[0]},ak:{cardinal:f[1]},am:{cardinal:f[2]},ar:{cardinal:f[3]},ars:{cardinal:f[3]},as:{cardinal:f[2],ordinal:f[34]},asa:{cardinal:f[0]},ast:{cardinal:f[4]},az:{cardinal:f[0],ordinal:f[35]},be:{cardinal:f[5],ordinal:f[36]},bem:{cardinal:f[0]},bez:{cardinal:f[0]},bg:{cardinal:f[0]},bh:{cardinal:f[1]},bn:{cardinal:f[2],ordinal:f[34]},br:{cardinal:f[6]},brx:{cardinal:f[0]},bs:{cardinal:f[7]},ca:{cardinal:f[4],ordinal:f[37]},ce:{cardinal:f[0]},cgg:{cardinal:f[0]},chr:{cardinal:f[0]},ckb:{cardinal:f[0]},cs:{cardinal:f[8]},cy:{cardinal:f[9],ordinal:f[38]},da:{cardinal:f[10]},de:{cardinal:f[4]},dsb:{cardinal:f[11]},dv:{cardinal:f[0]},ee:{cardinal:f[0]},el:{cardinal:f[0]},en:{cardinal:f[4],ordinal:f[39]},eo:{cardinal:f[0]},es:{cardinal:f[0]},et:{cardinal:f[4]},eu:{cardinal:f[0]},fa:{cardinal:f[2]},ff:{cardinal:f[12]},fi:{cardinal:f[4]},fil:{cardinal:f[13],ordinal:f[0]},fo:{cardinal:f[0]},fr:{cardinal:f[12],ordinal:f[0]},fur:{cardinal:f[0]},fy:{cardinal:f[4]},ga:{cardinal:f[14],ordinal:f[0]},gd:{cardinal:f[15],ordinal:f[40]},gl:{cardinal:f[4]},gsw:{cardinal:f[0]},gu:{cardinal:f[2],ordinal:f[41]},guw:{cardinal:f[1]},gv:{cardinal:f[16]},ha:{cardinal:f[0]},haw:{cardinal:f[0]},he:{cardinal:f[17]},hi:{cardinal:f[2],ordinal:f[41]},hr:{cardinal:f[7]},hsb:{cardinal:f[11]},hu:{cardinal:f[0],ordinal:f[42]},hy:{cardinal:f[12],ordinal:f[0]},ia:{cardinal:f[4]},io:{cardinal:f[4]},is:{cardinal:f[18]},it:{cardinal:f[4],ordinal:f[43]},iu:{cardinal:f[19]},iw:{cardinal:f[17]},jgo:{cardinal:f[0]},ji:{cardinal:f[4]},jmc:{cardinal:f[0]},ka:{cardinal:f[0],ordinal:f[44]},kab:{cardinal:f[12]},kaj:{cardinal:f[0]},kcg:{cardinal:f[0]},kk:{cardinal:f[0],ordinal:f[45]},kkj:{cardinal:f[0]},kl:{cardinal:f[0]},kn:{cardinal:f[2]},ks:{cardinal:f[0]},ksb:{cardinal:f[0]},ksh:{cardinal:f[20]},ku:{cardinal:f[0]},kw:{cardinal:f[19]},ky:{cardinal:f[0]},lag:{cardinal:f[21]},lb:{cardinal:f[0]},lg:{cardinal:f[0]},ln:{cardinal:f[1]},lt:{cardinal:f[22]},lv:{cardinal:f[23]},mas:{cardinal:f[0]},mg:{cardinal:f[1]},mgo:{cardinal:f[0]},mk:{cardinal:f[24],ordinal:f[46]},ml:{cardinal:f[0]},mn:{cardinal:f[0]},mo:{cardinal:f[25],ordinal:f[0]},mr:{cardinal:f[2],ordinal:f[47]},mt:{cardinal:f[26]},nah:{cardinal:f[0]},naq:{cardinal:f[19]},nb:{cardinal:f[0]},nd:{cardinal:f[0]},ne:{cardinal:f[0],ordinal:f[48]},nl:{cardinal:f[4]},nn:{cardinal:f[0]},nnh:{cardinal:f[0]},no:{cardinal:f[0]},nr:{cardinal:f[0]},nso:{cardinal:f[1]},ny:{cardinal:f[0]},nyn:{cardinal:f[0]},om:{cardinal:f[0]},or:{cardinal:f[0],ordinal:f[49]},os:{cardinal:f[0]},pa:{cardinal:f[1]},pap:{cardinal:f[0]},pl:{cardinal:f[27]},prg:{cardinal:f[23]},ps:{cardinal:f[0]},pt:{cardinal:f[28]},"pt-PT":{cardinal:f[4]},rm:{cardinal:f[0]},ro:{cardinal:f[25],ordinal:f[0]},rof:{cardinal:f[0]},ru:{cardinal:f[29]},rwk:{cardinal:f[0]},saq:{cardinal:f[0]},sc:{cardinal:f[4],ordinal:f[43]},scn:{cardinal:f[4],ordinal:f[43]},sd:{cardinal:f[0]},sdh:{cardinal:f[0]},se:{cardinal:f[19]},seh:{cardinal:f[0]},sh:{cardinal:f[7]},shi:{cardinal:f[30]},si:{cardinal:f[31]},sk:{cardinal:f[8]},sl:{cardinal:f[32]},sma:{cardinal:f[19]},smi:{cardinal:f[19]},smj:{cardinal:f[19]},smn:{cardinal:f[19]},sms:{cardinal:f[19]},sn:{cardinal:f[0]},so:{cardinal:f[0]},sq:{cardinal:f[0],ordinal:f[50]},sr:{cardinal:f[7]},ss:{cardinal:f[0]},ssy:{cardinal:f[0]},st:{cardinal:f[0]},sv:{cardinal:f[4],ordinal:f[51]},sw:{cardinal:f[4]},syr:{cardinal:f[0]},ta:{cardinal:f[0]},te:{cardinal:f[0]},teo:{cardinal:f[0]},ti:{cardinal:f[1]},tig:{cardinal:f[0]},tk:{cardinal:f[0],ordinal:f[52]},tl:{cardinal:f[13],ordinal:f[0]},tn:{cardinal:f[0]},tr:{cardinal:f[0]},ts:{cardinal:f[0]},tzm:{cardinal:f[33]},ug:{cardinal:f[0]},uk:{cardinal:f[29],ordinal:f[53]},ur:{cardinal:f[4]},uz:{cardinal:f[0]},ve:{cardinal:f[0]},vo:{cardinal:f[0]},vun:{cardinal:f[0]},wa:{cardinal:f[1]},wae:{cardinal:f[0]},xh:{cardinal:f[0]},xog:{cardinal:f[0]},yi:{cardinal:f[4]},zu:{cardinal:f[2]},lo:{ordinal:f[0]},ms:{ordinal:f[0]},vi:{ordinal:f[0]}},ui=ba(function(s,e){e=s.exports=function(b,p,m){return t(b,null,p||"en",m||{},!0)},e.toParts=function(b,p,m){return t(b,null,p||"en",m||{},!1)};function t(b,p,m,g,w){var E=b.map(function(x){return i(x,p,m,g,w)});return w?E.length===1?E[0]:function(x){for(var A="",$=0;$<E.length;++$)A+=E[$](x);return A}:function(x){return E.reduce(function(A,$){return A.concat($(x))},[])}}function i(b,p,m,g,w){if(typeof b=="string"){var E=b;return function(){return E}}var x=b[0],A=b[1];if(p&&b[0]==="#"){x=p[0];var $=p[2],z=(g.number||u.number)([x,"number"],m);return function(U){return z(o(x,U)-$,U)}}var M;A==="plural"||A==="selectordinal"?(M={},Object.keys(b[3]).forEach(function(U){M[U]=t(b[3][U],b,m,g,w)}),b=[b[0],b[1],b[2],M]):b[2]&&typeof b[2]=="object"&&(M={},Object.keys(b[2]).forEach(function(U){M[U]=t(b[2][U],b,m,g,w)}),b=[b[0],b[1],M]);var D=A&&(g[A]||u[A]);if(D){var Y=D(b,m);return function(U){return Y(o(x,U),U)}}return w?function(U){return String(o(x,U))}:function(U){return o(x,U)}}function o(b,p){if(p&&b in p)return p[b];for(var m=b.split("."),g=p,w=0,E=m.length;g&&w<E;++w)g=g[m[w]];return g}function r(b,p){var m=b[2],g=We.number[m]||We.parseNumberPattern(m)||We.number.default;return new Intl.NumberFormat(p,g).format}function n(b,p){var m=b[2],g=We.duration[m]||We.duration.default,w=new Intl.NumberFormat(p,g.seconds).format,E=new Intl.NumberFormat(p,g.minutes).format,x=new Intl.NumberFormat(p,g.hours).format,A=/^fi$|^fi-|^da/.test(String(p))?".":":";return function($,z){if($=+$,!isFinite($))return w($);var M=~~($/60/60),D=~~($/60%60),Y=(M?x(Math.abs(M))+A:"")+E(Math.abs(D))+A+w(Math.abs($%60));return $<0?x(-1).replace(x(1),Y):Y}}function a(b,p){var m=b[1],g=b[2],w=We[m][g]||We.parseDatePattern(g)||We[m].default;return new Intl.DateTimeFormat(p,w).format}function l(b,p){var m=b[1],g=m==="selectordinal"?"ordinal":"cardinal",w=b[2],E=b[3],x;if(Intl.PluralRules&&Intl.PluralRules.supportedLocalesOf(p).length>0)x=new Intl.PluralRules(p,{type:g});else{var A=Ad(p,eo),$=A&&eo[A][g]||c;x={select:$}}return function(z,M){var D=E["="+ +z]||E[x.select(z-w)]||E.other;return D(M)}}function c(){return"other"}function d(b,p){var m=b[2];return function(g,w){var E=m[g]||m.other;return E(w)}}var u={number:r,ordinal:r,spellout:r,duration:n,date:a,time:a,plural:l,selectordinal:l,select:d};e.types=u});ui.toParts;ui.types;var ga=ba(function(s,e){var t="{",i="}",o=",",r="#",n="<",a=">",l="</",c="/>",d="'",u="offset:",b=["number","date","time","ordinal","duration","spellout"],p=["plural","select","selectordinal"];e=s.exports=function(h,C){return m({pattern:String(h),index:0,tagsType:C&&C.tagsType||null,tokens:C&&C.tokens||null},"")};function m(h,C){var N=h.pattern,T=N.length,F=[],S=h.index,ie=g(h,C);for(ie&&F.push(ie),ie&&h.tokens&&h.tokens.push(["text",N.slice(S,h.index)]);h.index<T;){if(N[h.index]===i){if(!C)throw q(h);break}if(C&&h.tagsType&&N.slice(h.index,h.index+l.length)===l)break;F.push(x(h)),S=h.index,ie=g(h,C),ie&&F.push(ie),ie&&h.tokens&&h.tokens.push(["text",N.slice(S,h.index)])}return F}function g(h,C){for(var N=h.pattern,T=N.length,F=C==="plural"||C==="selectordinal",S=!!h.tagsType,ie=C==="{style}",ge="";h.index<T;){var B=N[h.index];if(B===t||B===i||F&&B===r||S&&B===n||ie&&w(B.charCodeAt(0)))break;if(B===d)if(B=N[++h.index],B===d)ge+=B,++h.index;else if(B===t||B===i||F&&B===r||S&&B===n||ie)for(ge+=B;++h.index<T;)if(B=N[h.index],B===d&&N[h.index+1]===d)ge+=d,++h.index;else if(B===d){++h.index;break}else ge+=B;else ge+=d;else ge+=B,++h.index}return ge}function w(h){return h>=9&&h<=13||h===32||h===133||h===160||h===6158||h>=8192&&h<=8205||h===8232||h===8233||h===8239||h===8287||h===8288||h===12288||h===65279}function E(h){for(var C=h.pattern,N=C.length,T=h.index;h.index<N&&w(C.charCodeAt(h.index));)++h.index;T<h.index&&h.tokens&&h.tokens.push(["space",h.pattern.slice(T,h.index)])}function x(h){var C=h.pattern;if(C[h.index]===r)return h.tokens&&h.tokens.push(["syntax",r]),++h.index,[r];var N=A(h);if(N)return N;if(C[h.index]!==t)throw q(h,t);h.tokens&&h.tokens.push(["syntax",t]),++h.index,E(h);var T=$(h);if(!T)throw q(h,"placeholder id");h.tokens&&h.tokens.push(["id",T]),E(h);var F=C[h.index];if(F===i)return h.tokens&&h.tokens.push(["syntax",i]),++h.index,[T];if(F!==o)throw q(h,o+" or "+i);h.tokens&&h.tokens.push(["syntax",o]),++h.index,E(h);var S=$(h);if(!S)throw q(h,"placeholder type");if(h.tokens&&h.tokens.push(["type",S]),E(h),F=C[h.index],F===i){if(h.tokens&&h.tokens.push(["syntax",i]),S==="plural"||S==="selectordinal"||S==="select")throw q(h,S+" sub-messages");return++h.index,[T,S]}if(F!==o)throw q(h,o+" or "+i);h.tokens&&h.tokens.push(["syntax",o]),++h.index,E(h);var ie;if(S==="plural"||S==="selectordinal"){var ge=M(h);E(h),ie=[T,S,ge,Y(h,S)]}else if(S==="select")ie=[T,S,Y(h,S)];else if(b.indexOf(S)>=0)ie=[T,S,z(h)];else{var B=h.index,Ie=z(h);E(h),C[h.index]===t&&(h.index=B,Ie=Y(h,S)),ie=[T,S,Ie]}if(E(h),C[h.index]!==i)throw q(h,i);return h.tokens&&h.tokens.push(["syntax",i]),++h.index,ie}function A(h){var C=h.tagsType;if(!(!C||h.pattern[h.index]!==n)){if(h.pattern.slice(h.index,h.index+l.length)===l)throw q(h,null,"closing tag without matching opening tag");h.tokens&&h.tokens.push(["syntax",n]),++h.index;var N=$(h,!0);if(!N)throw q(h,"placeholder id");if(h.tokens&&h.tokens.push(["id",N]),E(h),h.pattern.slice(h.index,h.index+c.length)===c)return h.tokens&&h.tokens.push(["syntax",c]),h.index+=c.length,[N,C];if(h.pattern[h.index]!==a)throw q(h,a);h.tokens&&h.tokens.push(["syntax",a]),++h.index;var T=m(h,C),F=h.index;if(h.pattern.slice(h.index,h.index+l.length)!==l)throw q(h,l+N+a);h.tokens&&h.tokens.push(["syntax",l]),h.index+=l.length;var S=$(h,!0);if(S&&h.tokens&&h.tokens.push(["id",S]),N!==S)throw h.index=F,q(h,l+N+a,l+S+a);if(E(h),h.pattern[h.index]!==a)throw q(h,a);return h.tokens&&h.tokens.push(["syntax",a]),++h.index,[N,C,{children:T}]}}function $(h,C){for(var N=h.pattern,T=N.length,F="";h.index<T;){var S=N[h.index];if(S===t||S===i||S===o||S===r||S===d||w(S.charCodeAt(0))||C&&(S===n||S===a||S==="/"))break;F+=S,++h.index}return F}function z(h){var C=h.index,N=g(h,"{style}");if(!N)throw q(h,"placeholder style name");return h.tokens&&h.tokens.push(["style",h.pattern.slice(C,h.index)]),N}function M(h){var C=h.pattern,N=C.length,T=0;if(C.slice(h.index,h.index+u.length)===u){h.tokens&&h.tokens.push(["offset","offset"],["syntax",":"]),h.index+=u.length,E(h);for(var F=h.index;h.index<N&&D(C.charCodeAt(h.index));)++h.index;if(F===h.index)throw q(h,"offset number");h.tokens&&h.tokens.push(["number",C.slice(F,h.index)]),T=+C.slice(F,h.index)}return T}function D(h){return h>=48&&h<=57}function Y(h,C){for(var N=h.pattern,T=N.length,F={};h.index<T&&N[h.index]!==i;){var S=$(h);if(!S)throw q(h,"sub-message selector");h.tokens&&h.tokens.push(["selector",S]),E(h),F[S]=U(h,C),E(h)}if(!F.other&&p.indexOf(C)>=0)throw q(h,null,null,'"other" sub-message must be specified in '+C);return F}function U(h,C){if(h.pattern[h.index]!==t)throw q(h,t+" to start sub-message");h.tokens&&h.tokens.push(["syntax",t]),++h.index;var N=m(h,C);if(h.pattern[h.index]!==i)throw q(h,i+" to end sub-message");return h.tokens&&h.tokens.push(["syntax",i]),++h.index,N}function q(h,C,N,T){var F=h.pattern,S=F.slice(0,h.index).split(/\r?\n/),ie=h.index,ge=S.length,B=S.slice(-1)[0].length;return N=N||(h.index>=F.length?"end of message pattern":$(h)||F[h.index]),T||(T=Ne(C,N)),T+=" in "+F.replace(/\r?\n/g,`
`),new se(T,C,N,ie,ge,B)}function Ne(h,C){return h?"Expected "+h+" but found "+C:"Unexpected "+C+" found"}function se(h,C,N,T,F,S){Error.call(this,h),this.name="SyntaxError",this.message=h,this.expected=C,this.found=N,this.offset=T,this.line=F,this.column=S}se.prototype=Object.create(Error.prototype),e.SyntaxError=se});ga.SyntaxError;var Sd=new RegExp("^("+Object.keys(eo).join("|")+")\\b"),Qt=new WeakMap;/*!
 * Intl.MessageFormat prollyfill
 * Copyright(c) 2015 Andy VanWagoner
 * MIT licensed
 **/function Nt(s,e,t){if(!(this instanceof Nt)||Qt.has(this))throw new TypeError("calling MessageFormat constructor without new is invalid");var i=ga(s);Qt.set(this,{ast:i,format:ui(i,e,t&&t.types),locale:Nt.supportedLocalesOf(e)[0]||"en",locales:e,options:t})}var Nd=Nt;Object.defineProperties(Nt.prototype,{format:{configurable:!0,get:function(){var s=Qt.get(this);if(!s)throw new TypeError("MessageFormat.prototype.format called on value that's not an object initialized as a MessageFormat");return s.format}},formatToParts:{configurable:!0,writable:!0,value:function(s){var e=Qt.get(this);if(!e)throw new TypeError("MessageFormat.prototype.formatToParts called on value that's not an object initialized as a MessageFormat");var t=e.toParts||(e.toParts=ui.toParts(e.ast,e.locales,e.options&&e.options.types));return t(s)}},resolvedOptions:{configurable:!0,writable:!0,value:function(){var s=Qt.get(this);if(!s)throw new TypeError("MessageFormat.prototype.resolvedOptions called on value that's not an object initialized as a MessageFormat");return{locale:s.locale}}}});typeof Symbol<"u"&&Object.defineProperty(Nt.prototype,Symbol.toStringTag,{value:"Object"});Object.defineProperties(Nt,{supportedLocalesOf:{configurable:!0,writable:!0,value:function(s){return[].concat(Intl.NumberFormat.supportedLocalesOf(s),Intl.DateTimeFormat.supportedLocalesOf(s),Intl.PluralRules?Intl.PluralRules.supportedLocalesOf(s):[],[].concat(s||[]).filter(function(e){return Sd.test(e)})).filter(function(e,t,i){return i.indexOf(e)===t})}}});function Td(s){return!!(s&&s.default&&typeof s.default=="object"&&Object.keys(s).length===1)}const Ke=globalThis.document?.documentElement;let Od=class extends EventTarget{formatNumberOptions={returnIfNaN:"",postProcessors:new Map};formatDateOptions={postProcessors:new Map};#e=!1;#t="";#s=null;__storage={};__namespacePatternsMap=new Map;__namespaceLoadersCache={};__namespaceLoaderPromisesCache={};get locale(){return this.#e?this.#t||"":Ke.lang||""}set locale(e){if(this.#i(e),!this.#e){const i=Ke.lang;this._setHtmlLangAttribute(e),this._onLocaleChanged(e,i);return}const t=this.#t;this.#t=e,this.#s===null&&this._setHtmlLangAttribute(e),this._onLocaleChanged(e,t)}get loadingComplete(){return typeof this.__namespaceLoaderPromisesCache[this.locale]=="object"?Promise.all(Object.values(this.__namespaceLoaderPromisesCache[this.locale])):Promise.resolve()}constructor({allowOverridesForExistingNamespaces:e=!1,autoLoadOnLocaleChange:t=!1,showKeyAsFallback:i=!1,fallbackLocale:o=""}={}){super(),this.__allowOverridesForExistingNamespaces=e,this._autoLoadOnLocaleChange=!!t,this._showKeyAsFallback=i,this._fallbackLocale=o;const r=Ke.getAttribute("data-localize-lang");this.#e=!!r,this.#e&&(this.locale=r,this._setupTranslationToolSupport()),Ke.lang||(Ke.lang=this.locale||"en-GB"),this._setupHtmlLangAttributeObserver()}addData(e,t,i){if(!this.__allowOverridesForExistingNamespaces&&this._isNamespaceInCache(e,t))throw new Error(`Namespace "${t}" has been already added for the locale "${e}".`);this.__storage[e]=this.__storage[e]||{},this.__allowOverridesForExistingNamespaces?this.__storage[e][t]={...this.__storage[e][t],...i}:this.__storage[e][t]=i}setupNamespaceLoader(e,t){this.__namespacePatternsMap.set(e,t)}loadNamespaces(e,{locale:t}={}){return Promise.all(e.map(i=>this.loadNamespace(i,{locale:t})))}loadNamespace(e,{locale:t=this.locale}={locale:this.locale}){const i=typeof e=="object",o=i?Object.keys(e)[0]:e;return this._isNamespaceInCache(t,o)?Promise.resolve():this._getCachedNamespaceLoaderPromise(t,o)||this._loadNamespaceData(t,e,i,o)}msg(e,t,i={}){const o=i.locale?i.locale:this.locale,r=this._getMessageForKeys(e,o);return r?new Nd(r,o).format(t):""}teardown(){this._teardownHtmlLangAttributeObserver()}reset(){this.__storage={},this.__namespacePatternsMap=new Map,this.__namespaceLoadersCache={},this.__namespaceLoaderPromisesCache={}}setDatePostProcessorForLocale({locale:e,postProcessor:t}){this.formatDateOptions?.postProcessors.set(e,t)}setNumberPostProcessorForLocale({locale:e,postProcessor:t}){this.formatNumberOptions?.postProcessors.set(e,t)}_setupTranslationToolSupport(){this.#s=Ke.lang||null}_setHtmlLangAttribute(e){this._teardownHtmlLangAttributeObserver(),Ke.lang=e,this._setupHtmlLangAttributeObserver()}_setupHtmlLangAttributeObserver(){this._htmlLangAttributeObserver||(this._htmlLangAttributeObserver=new MutationObserver(e=>{e.forEach(t=>{this.#e?Ke.lang==="auto"?(this.#s=null,this._setHtmlLangAttribute(this.locale)):this.#s=document.documentElement.lang:this._onLocaleChanged(document.documentElement.lang,t.oldValue||"")})})),this._htmlLangAttributeObserver.observe(document.documentElement,{attributes:!0,attributeFilter:["lang"],attributeOldValue:!0})}_teardownHtmlLangAttributeObserver(){this._htmlLangAttributeObserver&&this._htmlLangAttributeObserver.disconnect()}_isNamespaceInCache(e,t){return!!(this.__storage[e]&&this.__storage[e][t])}_getCachedNamespaceLoaderPromise(e,t){return this.__namespaceLoaderPromisesCache[e]?this.__namespaceLoaderPromisesCache[e][t]:null}_loadNamespaceData(e,t,i,o){const r=this._getNamespaceLoader(t,i,o),n=this._getNamespaceLoaderPromise(r,e,o);return this._cacheNamespaceLoaderPromise(e,o,n),n.then(a=>{if(this.__namespaceLoaderPromisesCache[e]&&this.__namespaceLoaderPromisesCache[e][o]===n){const l=Td(a)?a.default:a;this.addData(e,o,l)}})}_getNamespaceLoader(e,t,i){let o=this.__namespaceLoadersCache[i];if(o||(t?(o=e[i],this.__namespaceLoadersCache[i]=o):(o=this._lookupNamespaceLoader(i),this.__namespaceLoadersCache[i]=o)),!o)throw new Error(`Namespace "${i}" was not properly setup.`);return this.__namespaceLoadersCache[i]=o,o}_getNamespaceLoaderPromise(e,t,i,o=this._fallbackLocale){return e(t,i).catch(()=>{const r=this._getLangFromLocale(t);return e(r,i).catch(()=>{if(o)return this._getNamespaceLoaderPromise(e,o,i,"").catch(()=>{const n=this._getLangFromLocale(o);throw new Error(`Data for namespace "${i}" and current locale "${t}" or fallback locale "${o}" could not be loaded. Make sure you have data either for locale "${t}" (and/or generic language "${r}") or for fallback "${o}" (and/or "${n}").`)});throw new Error(`Data for namespace "${i}" and locale "${t}" could not be loaded. Make sure you have data for locale "${t}" (and/or generic language "${r}").`)})})}_cacheNamespaceLoaderPromise(e,t,i){this.__namespaceLoaderPromisesCache[e]||(this.__namespaceLoaderPromisesCache[e]={}),this.__namespaceLoaderPromisesCache[e][t]=i}_lookupNamespaceLoader(e){for(const[t,i]of this.__namespacePatternsMap){const o=typeof t=="string"&&t===e,r=typeof t=="object"&&t.constructor.name==="RegExp"&&t.test(e);if(o||r)return i}return null}_getLangFromLocale(e){return e.substring(0,2)}_onLocaleChanged(e,t){this.dispatchEvent(new CustomEvent("__localeChanging")),e!==t&&(this._autoLoadOnLocaleChange?(this._loadAllMissing(e,t),this.loadingComplete.then(()=>{this.dispatchEvent(new CustomEvent("localeChanged",{detail:{newLocale:e,oldLocale:t}}))})):this.dispatchEvent(new CustomEvent("localeChanged",{detail:{newLocale:e,oldLocale:t}})))}_loadAllMissing(e,t){const i=this.__storage[t]||{},o=this.__storage[e]||{};Object.keys(i).forEach(r=>{o[r]||this.loadNamespace(r,{locale:e})})}_getMessageForKeys(e,t){if(typeof e=="string")return this._getMessageForKey(e,t);const i=Array.from(e).reverse();let o,r;for(;i.length;)if(o=i.pop(),r=this._getMessageForKey(o,t),r)return r}_getMessageForKey(e,t){if(!e||e.indexOf(":")===-1)throw new Error(`Namespace is missing in the key "${e}". The format for keys is "namespace:name".`);const[i,o]=e.split(":"),r=this.__storage[t],n=r?r[i]:{},a=o.split(".").reduce((l,c)=>typeof l=="object"?l[c]:l,n);return String(a||(this._showKeyAsFallback?e:""))}#i(e){if(!e.includes("-"))throw new Error(`
      Locale was set to ${e}.
      Language only locales are not allowed, please use the full language locale e.g. 'en-GB' instead of 'en'.
      See https://github.com/ing-bank/lion/issues/187 for more information.
    `)}get _supportExternalTranslationTools(){return this.#e}set _supportExternalTranslationTools(e){this.#e=e}get _langAttrSetByTranslationTool(){return this.#t}set _langAttrSetByTranslationTool(e){this.#t=e}};const Ni=Symbol.for("lion::SingletonManagerClassStorage"),Ti=globalThis||window;let Ld=class{constructor(){this._map=Ti[Ni]?Ti[Ni]:Ti[Ni]=new Map}set(e,t){this.has(e)||this._map.set(e,t)}get(e){return this._map.get(e)}has(e){return this._map.has(e)}};const Is=new Ld;function to(){if(Is.has("@lion/ui::localize::0.x"))return Is.get("@lion/ui::localize::0.x");const s=new Od({autoLoadOnLocaleChange:!0,fallbackLocale:"en-GB"});return Is.set("@lion/ui::localize::0.x",s),s}/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const es=(s,e)=>{const t=s._$AN;if(t===void 0)return!1;for(const i of t)i._$AO?.(e,!1),es(i,e);return!0},Zs=s=>{let e,t;do{if((e=s._$AM)===void 0)break;t=e._$AN,t.delete(s),s=e}while(t?.size===0)},va=s=>{for(let e;e=s._$AM;s=e){let t=e._$AN;if(t===void 0)e._$AN=t=new Set;else if(t.has(s))break;t.add(s),Pd(e)}};function Rd(s){this._$AN!==void 0?(Zs(this),this._$AM=s,va(this)):this._$AM=s}function Fd(s,e=!1,t=0){const i=this._$AH,o=this._$AN;if(o!==void 0&&o.size!==0)if(e)if(Array.isArray(i))for(let r=t;r<i.length;r++)es(i[r],!1),Zs(i[r]);else i!=null&&(es(i,!1),Zs(i));else es(this,s)}const Pd=s=>{s.type==No.CHILD&&(s._$AP??=Fd,s._$AQ??=Rd)};let Md=class extends Oo{constructor(){super(...arguments),this._$AN=void 0}_$AT(e,t,i){super._$AT(e,t,i),va(this),this.isConnected=e._$AU}_$AO(e,t=!0){e!==this.isConnected&&(this.isConnected=e,e?this.reconnected?.():this.disconnected?.()),t&&(es(this,e),Zs(this))}setValue(e){if(xd(this._$Ct))this._$Ct._$AI(e,this);else{const t=[...this._$Ct._$AH];t[this._$Ci]=e,this._$Ct._$AI(t,this,0)}}disconnected(){}reconnected(){}};/**
 * @license
 * Copyright 2021 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */let Id=class{constructor(e){this.G=e}disconnect(){this.G=void 0}reconnect(e){this.G=e}deref(){return this.G}},Dd=class{constructor(){this.Y=void 0,this.Z=void 0}get(){return this.Y}pause(){this.Y??=new Promise((e=>this.Z=e))}resume(){this.Z?.(),this.Y=this.Z=void 0}};/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const Tr=s=>!kd(s)&&typeof s.then=="function",Or=1073741823;let Vd=class extends Md{constructor(){super(...arguments),this._$Cwt=Or,this._$Cbt=[],this._$CK=new Id(this),this._$CX=new Dd}render(...e){return e.find((t=>!Tr(t)))??Le}update(e,t){const i=this._$Cbt;let o=i.length;this._$Cbt=t;const r=this._$CK,n=this._$CX;this.isConnected||this.disconnected();for(let a=0;a<t.length&&!(a>this._$Cwt);a++){const l=t[a];if(!Tr(l))return this._$Cwt=a,l;a<o&&l===i[a]||(this._$Cwt=Or,o=0,Promise.resolve(l).then((async c=>{for(;n.get();)await n.get();const d=r.deref();if(d!==void 0){const u=d._$Cbt.indexOf(l);u>-1&&u<d._$Cwt&&(d._$Cwt=u,d.setValue(c))}})))}return Le}disconnected(){this._$CK.disconnect(),this._$CX.pause()}reconnected(){this._$CK.reconnect(this),this._$CX.resume()}};const zd=To(Vd),Bd=s=>class extends s{static get localizeNamespaces(){return[]}static get waitForLocalizeNamespaces(){return!0}constructor(){super(),this._localizeManager=to(),this.__boundLocalizeOnLocaleChanged=(...e)=>{const t=Array.from(e)[0];this.__localizeOnLocaleChanged(t)},this.__boundLocalizeOnLocaleChanging=()=>{this.__localizeOnLocaleChanging()},this.__localizeStartLoadingNamespaces(),this.localizeNamespacesLoaded&&this.localizeNamespacesLoaded.then(()=>{this.__localizeMessageSync=!0})}async scheduleUpdate(){Object.getPrototypeOf(this).constructor.waitForLocalizeNamespaces&&await this.localizeNamespacesLoaded,super.scheduleUpdate()}connectedCallback(){super.connectedCallback(),this.localizeNamespacesLoaded&&this.localizeNamespacesLoaded.then(()=>this.onLocaleReady()),this._localizeManager.addEventListener("__localeChanging",this.__boundLocalizeOnLocaleChanging),this._localizeManager.addEventListener("localeChanged",this.__boundLocalizeOnLocaleChanged)}disconnectedCallback(){super.disconnectedCallback(),this._localizeManager.removeEventListener("__localeChanging",this.__boundLocalizeOnLocaleChanging),this._localizeManager.removeEventListener("localeChanged",this.__boundLocalizeOnLocaleChanged)}msgLit(e,t,i){return this.__localizeMessageSync?this._localizeManager.msg(e,t,i):this.localizeNamespacesLoaded?zd(this.localizeNamespacesLoaded.then(()=>this._localizeManager.msg(e,t,i)),V):""}__getUniqueNamespaces(){const e=[],t=new Set;return Object.getPrototypeOf(this).constructor.localizeNamespaces.forEach(t.add.bind(t)),t.forEach(i=>{e.push(i)}),e}__localizeStartLoadingNamespaces(){this.localizeNamespacesLoaded=this._localizeManager.loadNamespaces(this.__getUniqueNamespaces())}__localizeOnLocaleChanging(){this.__localizeStartLoadingNamespaces()}__localizeOnLocaleChanged(e){this.onLocaleChanged(e.detail.newLocale,e.detail.oldLocale)}onLocaleReady(){this.onLocaleUpdated()}onLocaleChanged(e,t){this.onLocaleUpdated(),this.requestUpdate()}onLocaleUpdated(){}},Ud=te(Bd),so="3.0.0",Lr=window.scopedElementsVersions||(window.scopedElementsVersions=[]);Lr.includes(so)||Lr.push(so);const Hd=s=>class extends s{static scopedElements;static get scopedElementsVersion(){return so}static __registry;get registry(){return this.constructor.__registry}set registry(e){this.constructor.__registry=e}attachShadow(e){const{scopedElements:t}=this.constructor;if(!this.registry||this.registry===this.constructor.__registry&&!Object.prototype.hasOwnProperty.call(this.constructor,"__registry")){this.registry=new CustomElementRegistry;for(const[i,o]of Object.entries(t??{}))this.registry.define(i,o)}return super.attachShadow({...e,customElements:this.registry,registry:this.registry})}},jd=te(Hd),qd=s=>class extends jd(s){createRenderRoot(){const{shadowRootOptions:e,elementStyles:t}=this.constructor,i=this.attachShadow(e);return this.renderOptions.creationScope=i,wo(i,t),this.renderOptions.renderBefore??=i.firstChild,i}},Wd=te(qd);function Ns(){return!!(globalThis.ShadowRoot?.prototype.createElement&&globalThis.ShadowRoot?.prototype.importNode)}const Kd=s=>class extends Wd(s){constructor(){super()}createScopedElement(e){return(Ns()?this.shadowRoot:document).createElement(e)}defineScopedElement(e,t){const i=this.registry.get(e),o=i&&i!==t;return!Ns()&&o&&console.error([`You are trying to re-register the "${e}" custom element with a different class via ScopedElementsMixin.`,"This is only possible with a CustomElementRegistry.","Your browser does not support this feature so you will need to load a polyfill for it.",'Load "@webcomponents/scoped-custom-element-registry" before you register ANY web component to the global customElements registry.','e.g. add "<script src="/node_modules/@webcomponents/scoped-custom-element-registry/scoped-custom-element-registry.min.js"><\/script>" as your first script tag.',"For more details you can visit https://open-wc.org/docs/development/scoped-elements/"].join(`
`)),i?this.registry.get(e):this.registry.define(e,t)}attachShadow(e){const{scopedElements:t}=this.constructor;if(!this.registry||this.registry===this.constructor.__registry&&!Object.prototype.hasOwnProperty.call(this.constructor,"__registry")){this.registry=Ns()?new CustomElementRegistry:customElements;for(const[i,o]of Object.entries(t??{}))this.defineScopedElement(i,o)}return Element.prototype.attachShadow.call(this,{...e,customElements:this.registry,registry:this.registry})}createRenderRoot(){const{shadowRootOptions:e,elementStyles:t}=this.constructor,i=this.attachShadow(e);return Ns()&&(this.renderOptions.creationScope=i),i instanceof ShadowRoot&&(wo(i,t),this.renderOptions.renderBefore=this.renderOptions.renderBefore||i.firstChild),i}},pi=te(Kd);let Gd=class{constructor(){this.__running=!1,this.__queue=[]}add(e){this.__queue.push(e),this.__running||(this.complete=new Promise(t=>{this.__callComplete=t}),this.__run())}async __run(){this.__running=!0,await this.__queue[0](),this.__queue.shift(),this.__queue.length>0?this.__run():(this.__running=!1,this.__callComplete&&this.__callComplete())}};function Zd(s){return s.charAt(0).toUpperCase()+s.slice(1)}const Yd=s=>class extends s{constructor(){super(),this.__SyncUpdatableNamespace={}}firstUpdated(e){super.firstUpdated(e),this.__syncUpdatableInitialize()}connectedCallback(){super.connectedCallback(),this.__SyncUpdatableNamespace.connected=!0}disconnectedCallback(){super.disconnectedCallback(),this.__SyncUpdatableNamespace.connected=!1}static enabledWarnings=super.enabledWarnings?.filter(e=>e!=="change-in-update")||[];static __syncUpdatableHasChanged(e,t,i){const o=this.elementProperties;return o.get(e)&&o.get(e).hasChanged?o.get(e).hasChanged(t,i):t!==i}__syncUpdatableInitialize(){const e=this.__SyncUpdatableNamespace,t=this.constructor;e.initialized=!0,e.queue&&Array.from(e.queue).forEach(i=>{t.__syncUpdatableHasChanged(i,this[i],void 0)&&this.updateSync(i,void 0)})}requestUpdate(e,t,i){if(super.requestUpdate(e,t,i),e===void 0)return;this.__SyncUpdatableNamespace=this.__SyncUpdatableNamespace||{};const o=this.__SyncUpdatableNamespace,r=this.constructor;o.initialized?r.__syncUpdatableHasChanged(e,this[e],t)&&this.updateSync(e,t):(o.queue=o.queue||new Set,o.queue.add(e))}updateSync(e,t){}},Jd=te(Yd),Xd=(s=>{switch(s){case"bg-BG":return I(()=>import("./bg-BG-BqLSXSgK.js"),__vite__mapDeps([0,1]));case"bg":return I(()=>import("./bg-Ch91FBqZ.js"),[]);case"cs-CZ":return I(()=>import("./cs-CZ-BOieS6Re.js"),__vite__mapDeps([2,3]));case"cs":return I(()=>import("./cs-Bco-9vYd.js"),[]);case"de-DE":return I(()=>import("./de-DE-NiEdSbeI.js"),__vite__mapDeps([4,5]));case"de":return I(()=>import("./de--MUj2jPW.js"),[]);case"en-AU":return I(()=>import("./en-AU-5SYH9YrO.js"),__vite__mapDeps([6,7]));case"en-GB":return I(()=>import("./en-GB-5SYH9YrO.js"),__vite__mapDeps([8,7]));case"en-US":return I(()=>import("./en-US-5SYH9YrO.js"),__vite__mapDeps([9,7]));case"en-PH":case"en":return I(()=>import("./en-QBEFuq4A.js"),[]);case"es-ES":return I(()=>import("./es-ES-BzB2G1H7.js"),__vite__mapDeps([10,11]));case"es":return I(()=>import("./es-QUDKKOEt.js"),[]);case"fr-FR":return I(()=>import("./fr-FR-D8x_WpSN.js"),__vite__mapDeps([12,13]));case"fr-BE":return I(()=>import("./fr-BE-D8x_WpSN.js"),__vite__mapDeps([14,13]));case"fr":return I(()=>import("./fr-Crw_WS9R.js"),[]);case"hu-HU":return I(()=>import("./hu-HU-DzuJRq2x.js"),__vite__mapDeps([15,16]));case"hu":return I(()=>import("./hu-BzLNk3Oy.js"),[]);case"it-IT":return I(()=>import("./it-IT-BVziFtOr.js"),__vite__mapDeps([17,18]));case"it":return I(()=>import("./it-Dk-tLV60.js"),[]);case"nl-BE":return I(()=>import("./nl-BE-Cv6cOJ-k.js"),__vite__mapDeps([19,20]));case"nl-NL":return I(()=>import("./nl-NL-Cv6cOJ-k.js"),__vite__mapDeps([21,20]));case"nl":return I(()=>import("./nl-ukLmcyhE.js"),[]);case"pl-PL":return I(()=>import("./pl-PL-C3QXGAg0.js"),__vite__mapDeps([22,23]));case"pl":return I(()=>import("./pl-BsbBHKbu.js"),[]);case"ro-RO":return I(()=>import("./ro-RO-BHOQwu0O.js"),__vite__mapDeps([24,25]));case"ro":return I(()=>import("./ro-BWWeoMIS.js"),[]);case"ru-RU":return I(()=>import("./ru-RU-DCvtZjBo.js"),__vite__mapDeps([26,27]));case"ru":return I(()=>import("./ru-D87QXJFw.js"),[]);case"sk-SK":return I(()=>import("./sk-SK-DaLB_sM8.js"),__vite__mapDeps([28,29]));case"sk":return I(()=>import("./sk-DCOU_ZI_.js"),[]);case"tr-TR":return I(()=>import("./tr-TR-Dhk7tqKh.js"),__vite__mapDeps([30,31]));case"tr":return I(()=>import("./tr-92apvQxK.js"),[]);case"uk-UA":return I(()=>import("./uk-UA-BP_5Rplg.js"),__vite__mapDeps([32,33]));case"uk":return I(()=>import("./uk-CGlal3kJ.js"),[]);case"zh-CN":case"zh":return I(()=>import("./zh-CZafHN1K.js"),[]);default:return I(()=>import("./en-QBEFuq4A.js"),[])}}),Qd=s=>`${s[0].toUpperCase()}${s.slice(1)}`;let eh=class extends Ud(G){static get properties(){return{feedbackData:{attribute:!1}}}static localizeNamespaces=[{"lion-form-core":Xd},...super.localizeNamespaces];static get styles(){return[R`
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
      `]}constructor(){super(),this.feedbackData=void 0}_messageTemplate({message:e}){return e}updated(e){super.updated(e),this.feedbackData&&this.feedbackData[0]?(this.setAttribute("type",this.feedbackData[0].type),this.currentType=this.feedbackData[0].type):this.currentType!=="success"&&this.removeAttribute("type")}render(){return k`
      ${this.feedbackData&&this.feedbackData.map(({message:e,type:t,validator:i})=>k`
          <div class="validation-feedback__type">
            ${e&&t?this._localizeManager.msg(`lion-form-core:validation${Qd(t)}`):V}
          </div>
          ${this._messageTemplate({message:e,type:t,validator:i})}
        `)}
    `}},Ys=class{constructor(e){this.type="unparseable",this.viewValue=e}toString(){return JSON.stringify({type:this.type,viewValue:this.viewValue})}};const th=[Node.DOCUMENT_POSITION_PRECEDING,Node.DOCUMENT_POSITION_CONTAINS,Node.DOCUMENT_POSITION_CONTAINS|Node.DOCUMENT_POSITION_PRECEDING];function _a(s,{reverse:e}={}){const t=(o,r)=>{const n=o.compareDocumentPosition(r);return th.includes(n)?1:-1},i=s.filter(o=>o);return i.sort(t),e&&i.reverse(),i}const sh=s=>class extends s{constructor(){super(),this.name="",this._parentFormGroup=void 0,this.allowCrossRootRegistration=!1}get name(){return this.__name||""}set name(e){const t=this.name;this.__name=e.toString(),this.requestUpdate("name",t)}static get properties(){return{name:{type:String,reflect:!0},allowCrossRootRegistration:{type:Boolean,attribute:"allow-cross-root-registration"}}}connectedCallback(){super.connectedCallback(),this.dispatchEvent(new CustomEvent("form-element-register",{detail:{element:this},bubbles:!0,composed:!!this.allowCrossRootRegistration}))}disconnectedCallback(){super.disconnectedCallback(),this.__unregisterFormElement()}__unregisterFormElement(){this._parentFormGroup&&this._parentFormGroup.removeFormElement(this)}},Ho=te(sh),ih=s=>class extends Ho(gs(Qe(s))){static get properties(){return{readOnly:{type:Boolean,attribute:"readonly",reflect:!0},label:String,labelSrOnly:{type:Boolean,attribute:"label-sr-only",reflect:!0},helpText:{type:String,attribute:"help-text"},modelValue:{attribute:!1},_ariaLabelledNodes:{attribute:!1},_ariaDescribedNodes:{attribute:!1},_repropagationRole:{attribute:!1},_isRepropagationEndpoint:{attribute:!1}}}get label(){return this.__label??(this._labelNode?.textContent||"")}set label(e){const t=this.label;this.__label=e,this.requestUpdate("label",t)}get helpText(){return this.__helpText??(this._helpTextNode?.textContent||"")}set helpText(e){const t=this.helpText;this.__helpText=e,this.requestUpdate("helpText",t)}get fieldName(){return this.__fieldName||this.label||this.name||""}set fieldName(e){this.__fieldName=e}get slots(){return{...super.slots,label:()=>{const e=document.createElement("label");return e.textContent=this.label,e},"help-text":()=>{const e=document.createElement("div");return e.textContent=this.helpText,e}}}get _inputNode(){return this.__getDirectSlotChild("input")}get _labelNode(){return this.__getDirectSlotChild("label")}get _helpTextNode(){return this.__getDirectSlotChild("help-text")}get _feedbackNode(){return this.__getDirectSlotChild("feedback")}static enabledWarnings=super.enabledWarnings?.filter(e=>e!=="change-in-update")||[];constructor(){super(),this.readOnly=!1,this.labelSrOnly=!1,this._inputId=Uo(this.localName),this._ariaLabelledNodes=[],this._ariaDescribedNodes=[],this._repropagationRole="child",this._isRepropagationEndpoint=!1,this.addEventListener("model-value-changed",this.__repropagateChildrenValues),this._onLabelClick=this._onLabelClick.bind(this)}connectedCallback(){super.connectedCallback(),this._enhanceLightDomClasses(),this._enhanceLightDomA11y(),this._triggerInitialModelValueChangedEvent(),this._labelNode&&this._labelNode.addEventListener("click",this._onLabelClick)}disconnectedCallback(){super.disconnectedCallback(),this._labelNode&&this._labelNode.removeEventListener("click",this._onLabelClick)}updated(e){super.updated(e),e.has("disabled")&&this._inputNode?.setAttribute("aria-disabled",`${!!this.disabled}`),e.has("_ariaLabelledNodes")&&this.__reflectAriaAttr("aria-labelledby",this._ariaLabelledNodes,this.__reorderAriaLabelledNodes),e.has("_ariaDescribedNodes")&&this.__reflectAriaAttr("aria-describedby",this._ariaDescribedNodes,this.__reorderAriaDescribedNodes),e.has("label")&&this.__label!==void 0&&this._labelNode&&(this._labelNode.textContent=this.label),e.has("helpText")&&this.__helpText!==void 0&&this._helpTextNode&&(this._helpTextNode.textContent=this.helpText),e.has("name")&&this.dispatchEvent(new CustomEvent("form-element-name-changed",{detail:{oldName:e.get("name"),newName:this.name},bubbles:!0}))}_triggerInitialModelValueChangedEvent(){this._dispatchInitialModelValueChangedEvent()}_enhanceLightDomClasses(){this._inputNode&&this._inputNode.classList.add("form-control")}_enhanceLightDomA11y(){const{_inputNode:e,_labelNode:t,_helpTextNode:i,_feedbackNode:o}=this;e&&(e.id=e.id||this._inputId),t&&(t.setAttribute("for",this._inputId),this.addToAriaLabelledBy(t,{idPrefix:"label"})),i&&this.addToAriaDescribedBy(i,{idPrefix:"help-text"}),o&&(this.addEventListener("focusin",()=>{o.setAttribute("aria-live","polite")}),this.addEventListener("focusout",()=>{o.setAttribute("aria-live","assertive")}),this.addToAriaDescribedBy(o,{idPrefix:"feedback"})),this._enhanceLightDomA11yForAdditionalSlots()}_enhanceLightDomA11yForAdditionalSlots(e=["prefix","suffix","before","after"]){e.forEach(t=>{const i=this.__getDirectSlotChild(t);i&&(i.hasAttribute("data-label")&&this.addToAriaLabelledBy(i,{idPrefix:t}),i.hasAttribute("data-description")&&this.addToAriaDescribedBy(i,{idPrefix:t}))})}__reflectAriaAttr(e,t,i){if(this._inputNode){if(i){const r=t.filter(d=>this.contains(d)),n=t.filter(d=>!this.contains(d)),a=r.map(d=>d.assignedSlot||d),l=[..._a(a)],c=[];l.forEach(d=>{r.forEach(u=>{d.name===u.slot&&c.push(u)})}),t=[...c,...n]}const o=t.map(r=>r.id).join(" ");this._inputNode.setAttribute(e,o)}}render(){return k`
        <div class="form-field__group-one">${this._groupOneTemplate()}</div>
        <div class="form-field__group-two">${this._groupTwoTemplate()}</div>
      `}_groupOneTemplate(){return k` ${this._labelTemplate()} ${this._helpTextTemplate()} `}_groupTwoTemplate(){return k` ${this._inputGroupTemplate()} ${this._feedbackTemplate()} `}_labelTemplate(){return k`
        <div class="form-field__label">
          <slot name="label"></slot>
        </div>
      `}_helpTextTemplate(){return k`
        <small class="form-field__help-text">
          <slot name="help-text"></slot>
        </small>
      `}_inputGroupTemplate(){return k`
        <div class="input-group">
          ${this._inputGroupBeforeTemplate()}
          <div class="input-group__container">
            ${this._inputGroupPrefixTemplate()} ${this._inputGroupInputTemplate()}
            ${this._inputGroupSuffixTemplate()}
          </div>
          ${this._inputGroupAfterTemplate()}
        </div>
      `}_inputGroupBeforeTemplate(){return k`
        <div class="input-group__before">
          <slot name="before"></slot>
        </div>
      `}_inputGroupPrefixTemplate(){return Array.from(this.children).find(e=>e.slot==="prefix")?k`
            <div class="input-group__prefix">
              <slot name="prefix"></slot>
            </div>
          `:V}_inputGroupInputTemplate(){return k`
        <div class="input-group__input">
          <slot name="input"></slot>
        </div>
      `}_inputGroupSuffixTemplate(){return Array.from(this.children).find(e=>e.slot==="suffix")?k`
            <div class="input-group__suffix">
              <slot name="suffix"></slot>
            </div>
          `:V}_inputGroupAfterTemplate(){return k`
        <div class="input-group__after">
          <slot name="after"></slot>
        </div>
      `}_feedbackTemplate(){return k`
        <div class="form-field__feedback">
          <slot name="feedback"></slot>
        </div>
      `}_isEmpty(e=this.modelValue){let t=e;if(this.modelValue instanceof Ys&&(t=this.modelValue.viewValue),typeof t=="object"&&t!==null&&!(t instanceof Date))return!Object.keys(t).length;const i=typeof t=="number"&&(t===0||Number.isNaN(t));return!t&&!i&&!(typeof t=="boolean"&&t===!1)}static get styles(){return[R`
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
        `]}_getAriaDescriptionElements(){return[this._helpTextNode,this._feedbackNode]}addToAriaLabelledBy(e,{idPrefix:t="",reorder:i=!0}={}){e.id=e.id||`${t}-${this._inputId}`,this._ariaLabelledNodes.includes(e)||(this._ariaLabelledNodes=[...this._ariaLabelledNodes,e],this.__reorderAriaLabelledNodes=!!i)}removeFromAriaLabelledBy(e){this._ariaLabelledNodes.includes(e)&&(this._ariaLabelledNodes.splice(this._ariaLabelledNodes.indexOf(e),1),this._ariaLabelledNodes=[...this._ariaLabelledNodes],this.__reorderAriaLabelledNodes=!1)}addToAriaDescribedBy(e,{idPrefix:t="",reorder:i=!0}={}){e.id=e.id||`${t}-${this._inputId}`,this._ariaDescribedNodes.includes(e)||(this._ariaDescribedNodes=[...this._ariaDescribedNodes,e],this.__reorderAriaDescribedNodes=!!i)}removeFromAriaDescribedBy(e){this._ariaDescribedNodes.includes(e)&&(this._ariaDescribedNodes.splice(this._ariaDescribedNodes.indexOf(e),1),this._ariaDescribedNodes=[...this._ariaDescribedNodes],this.__reorderAriaLabelledNodes=!1)}__getDirectSlotChild(e){return Array.from(this.children).find(t=>t.slot===e)}_dispatchInitialModelValueChangedEvent(){this._repropagationRole!=="child"&&(this.__repropagateChildrenInitialized=!0,this.dispatchEvent(new CustomEvent("model-value-changed",{bubbles:!0,detail:{formPath:[this],initialize:!0,isTriggeredByUser:!1}})))}_onBeforeRepropagateChildrenValues(e){}__repropagateChildrenValues(e){this._onBeforeRepropagateChildrenValues(e);const t=e.detail&&e.detail.element||e.target,i=this._isRepropagationEndpoint||this._repropagationRole==="choice-group";if(t===this)return;e.stopImmediatePropagation();const o=this._repropagationRole!=="child"&&!this.__repropagateChildrenInitialized,r=e.detail&&e.detail.initialize;if(o||r||!this._repropagationCondition(t))return;let n=[];i||(n=e.detail&&e.detail.formPath||[t]);const a=[...n,this];this.dispatchEvent(new CustomEvent("model-value-changed",{bubbles:!0,detail:{formPath:a,isTriggeredByUser:!!e.detail?.isTriggeredByUser}}))}_repropagationCondition(e){return!!e}_onLabelClick(){}},gt=te(ih);function Rr(s=[],e=[]){return s.filter(t=>!e.includes(t)).concat(e.filter(t=>!s.includes(t)))}function oh(s){return s instanceof Ys?s.viewValue:s}const rh=s=>class extends gt(Jd(gs(Qe(pi(s))))){static get scopedElements(){return{...super.scopedElements,"lion-validation-feedback":eh}}static get properties(){return{validators:{attribute:!1},hasFeedbackFor:{attribute:!1},shouldShowFeedbackFor:{attribute:!1},showsFeedbackFor:{type:Array,attribute:"shows-feedback-for",reflect:!0,converter:{fromAttribute:(e=>e.split(",")),toAttribute:(e=>e.join(","))}},validationStates:{attribute:!1},isPending:{type:Boolean,attribute:"is-pending",reflect:!0},defaultValidators:{attribute:!1},_visibleMessagesAmount:{attribute:!1},__childModelValueChanged:{attribute:!1}}}static get validationTypes(){return["error"]}get operationMode(){return"enter"}get slots(){return{...super.slots,feedback:()=>{const e=this.createScopedElement("lion-validation-feedback");return e.setAttribute("data-tag-name","lion-validation-feedback"),e}}}get _allValidators(){return[...this.validators,...this.defaultValidators]}constructor(){super(),this.hasFeedbackFor=[],this.showsFeedbackFor=[],this.shouldShowFeedbackFor=[],this.validationStates={},this.isPending=!1,this.validators=[],this.defaultValidators=[],this._visibleMessagesAmount=1,this.__syncValidationResult=[],this.__asyncValidationResult=[],this.__validationResult=[],this.__prevValidationResult=[],this.__prevShownValidationResult=[],this.__childModelValueChanged=!1,this._onValidatorUpdated=this._onValidatorUpdated.bind(this),this._updateFeedbackComponent=this._updateFeedbackComponent.bind(this)}connectedCallback(){super.connectedCallback(),to().addEventListener("localeChanged",this._updateFeedbackComponent)}disconnectedCallback(){super.disconnectedCallback(),to().removeEventListener("localeChanged",this._updateFeedbackComponent)}firstUpdated(e){super.firstUpdated(e),this.__isValidateInitialized=!0,this.validate(),this._repropagationRole!=="child"&&this.addEventListener("model-value-changed",()=>{this.__childModelValueChanged=!0})}updateSync(e,t){if(super.updateSync(e,t),e==="validators"?(this.__setupValidators(),this.validate({clearCurrentResult:!0})):e==="modelValue"&&this.validate({clearCurrentResult:!0}),["touched","dirty","prefilled","focused","submitted","hasFeedbackFor","filled"].includes(e)&&this._updateShouldShowFeedbackFor(),e==="showsFeedbackFor"){this._inputNode&&this._inputNode.setAttribute("aria-invalid",`${this._hasFeedbackVisibleFor("error")}`);const i=Rr(this.showsFeedbackFor,t);i.length>0&&this.dispatchEvent(new Event("showsFeedbackForChanged",{bubbles:!0})),i.forEach(o=>{this.dispatchEvent(new Event(`showsFeedbackFor${Zd(o)}Changed`,{bubbles:!0}))})}e==="shouldShowFeedbackFor"&&Rr(this.shouldShowFeedbackFor,t).length>0&&this.dispatchEvent(new Event("shouldShowFeedbackForChanged",{bubbles:!0}))}async validate({clearCurrentResult:e=!1}={}){if(this.validateComplete=new Promise(t=>{this.__validateCompleteResolve=t}),this.disabled){this.__clearValidationResults(),this.__finishValidationPass(),this._updateFeedbackComponent();return}this.__isValidateInitialized&&(this.__prevValidationResult=this.__validationResult,e&&this.__clearValidationResults(),await this.__executeValidators())}#e(e){let t=e;for(;t;){if(t.constructor.validatorName==="Required")return!0;t=Object.getPrototypeOf(t)}return!1}async __executeValidators(){const e=oh(this.modelValue),t=this.__isEmpty(e);if(this.__syncValidationResult=[],t){const a=!this._isFormOrFieldset,l=this._allValidators.find(c=>c.constructor?.validatorName==="Required");if(l&&(this.__syncValidationResult=[{validator:l,outcome:!0}]),a){this.__finishValidationPass({syncValidationResult:this.__syncValidationResult});return}}const i=[],o=[],r=[];for(const a of this._allValidators)a?.executeOnResults?i.push(a):this.#e(a)||(a.constructor.async?r.push(a):o.push(a));const n=!!r.length;this.__syncValidationResult=[...this.__syncValidationResult,...this.__executeSyncValidators(o,e)],this.__finishValidationPass({syncValidationResult:this.__syncValidationResult,metaValidators:i}),n?(this.isPending=!0,this.__asyncValidationResult=await this.__executeAsyncValidators(r,e),this.isPending=!1,this.__finishValidationPass({syncValidationResult:this.__syncValidationResult,asyncValidationResult:this.__asyncValidationResult,metaValidators:i}),this.__validateCompleteResolve?.(!0)):this.__validateCompleteResolve?.(!0)}__executeSyncValidators(e,t){return e.map(i=>({validator:i,outcome:i.execute(t,i.param,{node:this})})).filter(i=>!!i.outcome)}async __executeAsyncValidators(e,t){const i=e.map(r=>r.execute(t,r.param,{node:this})),o=await Promise.all(i);return o.map((r,n)=>({validator:e[n],outcome:o[n]})).filter(r=>!!r.outcome)}__executeMetaValidators(e,t){return t.length?this._isEmpty(this.modelValue)?(this.__prevShownValidationResult=[],[]):t.map(i=>({validator:i,outcome:i.executeOnResults({regularValidationResult:e.map(o=>o.validator),prevValidationResult:this.__prevValidationResult.map(o=>o.validator),prevShownValidationResult:this.__prevShownValidationResult.map(o=>o.validator)})})).filter(i=>!!i.outcome):[]}__finishValidationPass({syncValidationResult:e=[],asyncValidationResult:t=[],metaValidators:i=[]}={}){const o=[...e,...t],r=this.__executeMetaValidators(o,i);this.__validationResult=[...r,...o];const n=this.constructor.validationTypes.reduce((a,l)=>({...a,[l]:{}}),{});for(const{validator:a,outcome:l}of this.__validationResult){n[a.type]||(n[a.type]={});const c=a.constructor;n[a.type][c.validatorName]=l}this.validationStates=n,this.hasFeedbackFor=[...new Set(this.__validationResult.map(({validator:a})=>a.type))],this.dispatchEvent(new Event("validate-performed",{bubbles:!0}))}__clearValidationResults(){this.__syncValidationResult=[],this.__asyncValidationResult=[]}_onValidatorUpdated(e){(e.type==="param-changed"||e.type==="config-changed")&&this.validate()}__setupValidators(){const e=["param-changed","config-changed"];for(const t of this.__prevValidators||[]){for(const i of e)t.removeEventListener?.(i,this._onValidatorUpdated);t.onFormControlDisconnect(this)}for(const t of this._allValidators){if(t.constructor._$isValidator$===void 0){const r=`Validators array only accepts class instances of Validator. Type "${Array.isArray(t)?"array":typeof t}" found. This may be caused by having multiple installations of "@lion/ui/form-core.js".`;throw console.error(r,this),new Error(r)}const i=this.constructor,o=t.constructor;if(i.validationTypes.indexOf(t.type)===-1){const r=`This component does not support the validator type "${t.type}" used in "${o.validatorName}". You may change your validators type or add it to the components "static get validationTypes() {}".`;throw console.error(r,this),new Error(r)}for(const r of e)t.addEventListener?.(r,n=>{this._onValidatorUpdated(n,{validator:t})});t.onFormControlConnect(this)}this.__prevValidators=this._allValidators}__isEmpty(e){return typeof this._isEmpty=="function"?this._isEmpty(e):this.modelValue===null||typeof this.modelValue>"u"||this.modelValue===""}async __getFeedbackMessages(e){let t=await this.fieldName;return Promise.all(e.map(async({validator:i,outcome:o})=>(i.config.fieldName&&(t=await i.config.fieldName),{message:await i._getMessage({modelValue:this.modelValue,formControl:this,fieldName:t,outcome:o}),type:i.type,validator:i,visibilityDuration:i.config?.visibilityDuration||3e3})))}_updateFeedbackComponent(){window.clearTimeout(this.removeMessage);const{_feedbackNode:e}=this;e&&(this.__feedbackQueue||(this.__feedbackQueue=new Gd),this.showsFeedbackFor.length>0?this.__feedbackQueue.add(async()=>{const t=this._prioritizeAndFilterFeedback({validationResult:this.__validationResult.map(o=>o.validator)});this.__prioritizedResult=t.map(o=>this.__validationResult.find(r=>o===r.validator)).filter(Boolean),this.__prioritizedResult.length>0&&(this.__prevShownValidationResult=this.__prioritizedResult);const i=await this.__getFeedbackMessages(this.__prioritizedResult);e.feedbackData=i||[],i?.[0]&&i[0].type==="success"&&i[0].visibilityDuration!==1/0&&(this.removeMessage=window.setTimeout(()=>{e.removeAttribute("type"),e.feedbackData=[]},i[0].visibilityDuration))}):this.__feedbackQueue.add(async()=>{e.feedbackData=[]}),this.feedbackComplete=this.__feedbackQueue.complete)}_showFeedbackConditionFor(e,t){return!0}get _feedbackConditionMeta(){return{modelValue:this.modelValue,el:this}}feedbackCondition(e,t=this._feedbackConditionMeta,i=this._showFeedbackConditionFor.bind(this)){return i(e,t)}_hasFeedbackVisibleFor(e){return this.hasFeedbackFor?.includes(e)&&this.shouldShowFeedbackFor?.includes(e)}updated(e){if(super.updated(e),e.has("shouldShowFeedbackFor")||e.has("hasFeedbackFor")){const t=this.constructor;this.showsFeedbackFor=t.validationTypes.map(i=>this._hasFeedbackVisibleFor(i)?i:void 0).filter(Boolean),this._updateFeedbackComponent()}if(e.has("__childModelValueChanged")&&this.__childModelValueChanged&&(this.validate({clearCurrentResult:!0}),this.__childModelValueChanged=!1),e.has("validationStates")){const t=e.get("validationStates");t&&Object.entries(this.validationStates).forEach(([i,o])=>{t[i]&&JSON.stringify(o)!==JSON.stringify(t[i])&&this.dispatchEvent(new CustomEvent(`${i}StateChanged`,{detail:o}))})}}_updateShouldShowFeedbackFor(){const e=this.constructor.validationTypes.map(t=>this.feedbackCondition(t,this._feedbackConditionMeta,this._showFeedbackConditionFor.bind(this))?t:void 0).filter(Boolean);JSON.stringify(this.shouldShowFeedbackFor)!==JSON.stringify(e)&&(this.shouldShowFeedbackFor=e)}_prioritizeAndFilterFeedback({validationResult:e}){const t=this.constructor.validationTypes;return e.filter(i=>this.feedbackCondition(i.type,this._feedbackConditionMeta,this._showFeedbackConditionFor.bind(this))).sort((i,o)=>t.indexOf(i.type)-t.indexOf(o.type)).slice(0,this._visibleMessagesAmount)}},_s=te(rh),io=window,Fr=new WeakMap;function nh(s){io.applyFocusVisiblePolyfill&&!Fr.has(s)&&(io.applyFocusVisiblePolyfill(s),Fr.set(s,void 0))}const ah=s=>class extends s{static get properties(){return{focused:{type:Boolean,reflect:!0},focusedVisible:{type:Boolean,reflect:!0,attribute:"focused-visible"},autofocus:{type:Boolean,reflect:!0}}}constructor(){super(),this.focused=!1,this.focusedVisible=!1,this.autofocus=!1}firstUpdated(e){super.firstUpdated(e),this.__registerEventsForFocusMixin(),this.__syncAutofocusToFocusableElement()}disconnectedCallback(){super.disconnectedCallback(),this.__teardownEventsForFocusMixin()}updated(e){super.updated(e),e.has("autofocus")&&this.__syncAutofocusToFocusableElement()}__syncAutofocusToFocusableElement(){this._focusableNode&&(this.hasAttribute("autofocus")?this._focusableNode.setAttribute("autofocus",""):this._focusableNode.removeAttribute("autofocus"))}focus(){this._focusableNode?.focus()}blur(){this._focusableNode?.blur()}get _focusableNode(){return this._inputNode||document.createElement("input")}__onFocus(){if(this.focused=!0,typeof io.applyFocusVisiblePolyfill=="function")this.focusedVisible=this._focusableNode.hasAttribute("data-focus-visible-added");else try{this.focusedVisible=this._focusableNode.matches(":focus-visible")}catch{this.focusedVisible=!1}}__onBlur(){this.focused=!1,this.focusedVisible=!1}__registerEventsForFocusMixin(){nh(this.getRootNode()),this.__redispatchFocus=e=>{e.stopPropagation(),this.dispatchEvent(new Event("focus"))},this._focusableNode.addEventListener("focus",this.__redispatchFocus),this.__redispatchBlur=e=>{e.stopPropagation(),this.dispatchEvent(new Event("blur"))},this._focusableNode.addEventListener("blur",this.__redispatchBlur),this.__redispatchFocusin=e=>{e.stopPropagation(),this.__onFocus(),this.dispatchEvent(new Event("focusin",{bubbles:!0,composed:!0}))},this._focusableNode.addEventListener("focusin",this.__redispatchFocusin),this.__redispatchFocusout=e=>{e.stopPropagation(),this.__onBlur(),this.dispatchEvent(new Event("focusout",{bubbles:!0,composed:!0}))},this._focusableNode.addEventListener("focusout",this.__redispatchFocusout)}__teardownEventsForFocusMixin(){this._focusableNode&&(this._focusableNode?.removeEventListener("focus",this.__redispatchFocus),this._focusableNode?.removeEventListener("blur",this.__redispatchBlur),this._focusableNode?.removeEventListener("focusin",this.__redispatchFocusin),this._focusableNode?.removeEventListener("focusout",this.__redispatchFocusout))}},jo=te(ah),lh=s=>class extends _s(gt(s)){static get properties(){return{formattedValue:{attribute:!1},serializedValue:{attribute:!1},formatOptions:{attribute:!1}}}#e={didFormatterOutputSyncToView:!1,didFormatterRun:!1};requestUpdate(e,t,i){super.requestUpdate(e,t,i),e==="modelValue"&&this.modelValue!==t&&this._onModelValueChanged({modelValue:this.modelValue},{modelValue:t}),e==="serializedValue"&&this.serializedValue!==t&&this._calculateValues({source:"serialized"}),e==="formattedValue"&&this.formattedValue!==t&&this._calculateValues({source:"formatted"})}get value(){return this._inputNode?.value||this.__value||""}set value(e){this._inputNode?(this._inputNode.value=e,this.__value=void 0):this.__value=e}preprocessor(e,t){}parser(e,t){return e}formatter(e,t){return e}serializer(e){return e!==void 0?e:""}deserializer(e){return e===void 0?"":e}_calculateValues({source:e}={source:null}){this.__preventRecursiveTrigger||(this.__preventRecursiveTrigger=!0,e!=="model"&&(e==="serialized"?this.modelValue=this.deserializer(this.serializedValue):e==="formatted"&&(this.modelValue=this._callParser())),e!=="formatted"&&(this.formattedValue=this._callFormatter()),e!=="serialized"&&(this.serializedValue=this.serializer(this.modelValue)),this._reflectBackFormattedValueToUser(),this.__preventRecursiveTrigger=!1,this.__prevViewValue=this.value)}_callParser(e=this.formattedValue){if(e==="")return"";if(typeof e!="string")return;const t=this.parser(e,{...this.formatOptions,mode:this.#t(),viewValueStates:this.#s()});return t!==void 0?t:new Ys(e)}_callFormatter(){return this.#e.didFormatterRun=!1,this._isHandlingUserInput&&this.hasFeedbackFor?.includes("error")&&this._inputNode?this.value:this.modelValue instanceof Ys?this.modelValue.viewValue:(this.#e.didFormatterRun=!0,this.formatter(this.modelValue,{...this.formatOptions,mode:this.#t(),viewValueStates:this.#s()}))}_onModelValueChanged(...e){this._calculateValues({source:"model"}),this._dispatchModelValueChangedEvent(...e)}_dispatchModelValueChangedEvent(...e){this.dispatchEvent(new CustomEvent("model-value-changed",{bubbles:!0,detail:{formPath:[this],isTriggeredByUser:!!this._isHandlingUserInput}}))}_syncValueUpwards(){this.__isHandlingComposition||this.__handlePreprocessor();const e=this.formattedValue;this.modelValue=this._callParser(this.value),e===this.formattedValue&&this.__prevViewValue!==this.value&&this._calculateValues()}__handlePreprocessor(){let e=this.value.length;this._inputNode&&"selectionStart"in this._inputNode&&this._inputNode?.type!=="range"&&(e=this._inputNode.selectionStart);const t=this.preprocessor(this.value,{...this.formatOptions,currentCaretIndex:e,prevViewValue:this.__prevViewValue});if(t!==void 0){if(typeof t=="string")this.value=t;else if(typeof t=="object"){const{viewValue:i,caretIndex:o}=t;this.value=i,o&&this._inputNode&&"selectionStart"in this._inputNode&&(this._inputNode.selectionStart=o,this._inputNode.selectionEnd=o)}}}_reflectBackFormattedValueToUser(){this._reflectBackOn()&&(this.value=typeof this.formattedValue<"u"?this.formattedValue:"",this.#e.didFormatterOutputSyncToView=!!this.formattedValue&&this.#e.didFormatterRun)}_reflectBackOn(){return!this._isHandlingUserInput}_proxyInputEvent(){this.dispatchEvent(new Event("user-input-changed",{bubbles:!0}))}_onUserInputChanged(){this._isHandlingUserInput=!0,this._syncValueUpwards(),this._isHandlingUserInput=!1}__onCompositionEvent({type:e}){e==="compositionstart"?this.__isHandlingComposition=!0:e==="compositionend"&&(this.__isHandlingComposition=!1,this._syncValueUpwards())}constructor(){super(),this.formatOn="change",this.formatOptions={mode:"auto"},this.formattedValue=void 0,this.serializedValue=void 0,this._isPasting=!1,this._isHandlingUserInput=!1,this.__prevViewValue="",this.__onCompositionEvent=this.__onCompositionEvent.bind(this),this.addEventListener("user-input-changed",this._onUserInputChanged),this.addEventListener("paste",this.__onPaste),this._reflectBackFormattedValueToUser=this._reflectBackFormattedValueToUser.bind(this),this._reflectBackFormattedValueDebounced=()=>{setTimeout(this._reflectBackFormattedValueToUser)}}__onPaste(){this._isPasting=!0,setTimeout(()=>{this._isPasting=!1})}connectedCallback(){super.connectedCallback(),typeof this.modelValue>"u"&&this._syncValueUpwards(),this.__prevViewValue=this.value,this._reflectBackFormattedValueToUser(),this._inputNode&&(this._inputNode.addEventListener(this.formatOn,this._reflectBackFormattedValueDebounced),this._inputNode.addEventListener("input",this._proxyInputEvent),this._inputNode.addEventListener("compositionstart",this.__onCompositionEvent),this._inputNode.addEventListener("compositionend",this.__onCompositionEvent))}disconnectedCallback(){super.disconnectedCallback(),this._inputNode&&(this._inputNode.removeEventListener("input",this._proxyInputEvent),this._inputNode.removeEventListener(this.formatOn,this._reflectBackFormattedValueDebounced),this._inputNode.removeEventListener("compositionstart",this.__onCompositionEvent),this._inputNode.removeEventListener("compositionend",this.__onCompositionEvent))}#t(){return this._isPasting?"pasted":this._isHandlingUserInput&&this.__prevViewValue?"user-edited":"auto"}#s(){const e=[];return this.#e.didFormatterOutputSyncToView&&e.push("formatted"),e}},qo=te(lh),ch=s=>class extends qo(jo(gt(s))){static get properties(){return{autocomplete:{type:String,reflect:!0}}}constructor(){super(),this.autocomplete=void 0}get _inputNode(){return super._inputNode}get selectionStart(){const e=this._inputNode;return e&&e.selectionStart?e.selectionStart:0}set selectionStart(e){const t=this._inputNode;t&&t.selectionStart&&(t.selectionStart=e)}get selectionEnd(){const e=this._inputNode;return e&&e.selectionEnd?e.selectionEnd:0}set selectionEnd(e){const t=this._inputNode;t&&t.selectionEnd&&(t.selectionEnd=e)}get value(){return this._inputNode&&this._inputNode.value||this.__value||""}set value(e){this._inputNode?(this._inputNode.value!==e&&this._setValueAndPreserveCaret(e),this.__value=void 0):this.__value=e}_setValueAndPreserveCaret(e){if(this.focused)try{if(!(this._inputNode instanceof HTMLSelectElement)){const t=this._inputNode.selectionStart;this._inputNode.value=e,this._inputNode.selectionStart=t,this._inputNode.selectionEnd=t}}catch{this._inputNode.value=e}else this._inputNode.value=e}_reflectBackFormattedValueToUser(){if(super._reflectBackFormattedValueToUser(),this._reflectBackOn()&&this.focused)try{this._inputNode.selectionStart=this._inputNode.value.length}catch{}}get _focusableNode(){return this._inputNode}},ya=te(ch),dh=s=>class extends gt(s){static get properties(){return{touched:{type:Boolean,reflect:!0},dirty:{type:Boolean,reflect:!0},filled:{type:Boolean,reflect:!0},prefilled:{attribute:!1},submitted:{attribute:!1}}}requestUpdate(e,t,i){super.requestUpdate(e,t,i),e==="touched"&&this.touched!==t&&this._onTouchedChanged(),e==="modelValue"&&(this.filled=!this._isEmpty()),e==="dirty"&&this.dirty!==t&&this._onDirtyChanged()}constructor(){super(),this.touched=!1,this.dirty=!1,this.prefilled=!1,this.filled=!1,this._leaveEvent="blur",this._valueChangedEvent="model-value-changed",this._iStateOnLeave=this._iStateOnLeave.bind(this),this._iStateOnValueChange=this._iStateOnValueChange.bind(this)}connectedCallback(){super.connectedCallback(),this.addEventListener(this._leaveEvent,this._iStateOnLeave),this.addEventListener(this._valueChangedEvent,this._iStateOnValueChange),this.initInteractionState()}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener(this._leaveEvent,this._iStateOnLeave),this.removeEventListener(this._valueChangedEvent,this._iStateOnValueChange)}initInteractionState(){this.dirty=!1,this.prefilled=!this._isEmpty()}_iStateOnLeave(){this.touched=!0,this.prefilled=!this._isEmpty()}_iStateOnValueChange(){this.dirty=!0}resetInteractionState(){this.touched=!1,this.submitted=!1,this.dirty=!1,this.prefilled=!this._isEmpty()}_onTouchedChanged(){this.dispatchEvent(new Event("touched-changed",{bubbles:!0,composed:!0}))}_onDirtyChanged(){this.dispatchEvent(new Event("dirty-changed",{bubbles:!0,composed:!0}))}_showFeedbackConditionFor(e,t){return t.touched&&t.dirty||t.prefilled||t.submitted}get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,submitted:this.submitted,touched:this.touched,dirty:this.dirty,filled:this.filled,prefilled:this.prefilled}}},Wo=te(dh);let Ko=class extends gt(Wo(jo(qo(_s(Qe(G)))))){firstUpdated(e){super.firstUpdated(e),this._initialModelValue=this.modelValue}connectedCallback(){super.connectedCallback(),this._onChange=this._onChange.bind(this),this._inputNode.addEventListener("change",this._onChange),this.classList.add("form-field")}disconnectedCallback(){super.disconnectedCallback(),this._inputNode?.removeEventListener("change",this._onChange)}resetInteractionState(){super.resetInteractionState(),this.submitted=!1}reset(){this.modelValue=this._initialModelValue,this.resetInteractionState()}clear(){this.modelValue=""}_onChange(e){this.dispatchEvent(new Event("user-input-changed",{bubbles:!0}))}get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,focused:this.focused}}get _focusableNode(){return this._inputNode}},Go=class extends ya(Ko){static get properties(){return{readOnly:{type:Boolean,attribute:"readonly",reflect:!0},type:{type:String,reflect:!0},placeholder:{type:String,reflect:!0}}}get slots(){return{...super.slots,input:()=>{const e=document.createElement("input"),t=this.getAttribute("value");return t&&e.setAttribute("value",t),e}}}get _inputNode(){return super._inputNode}constructor(){super(),this.readOnly=!1,this.type="text",this.placeholder=""}requestUpdate(e,t,i){super.requestUpdate(e,t,i),e==="readOnly"&&this.__delegateReadOnly()}firstUpdated(e){super.firstUpdated(e),this.__delegateReadOnly()}updated(e){super.updated(e),e.has("type")&&(this._inputNode.type=this.type),e.has("placeholder")&&(this._inputNode.placeholder=this.placeholder),e.has("disabled")&&(this._inputNode.disabled=this.disabled,this.validate()),e.has("name")&&(this._inputNode.name=this.name),e.has("autocomplete")&&(this._inputNode.autocomplete=this.autocomplete)}__delegateReadOnly(){this._inputNode&&(this._inputNode.readOnly=this.readOnly)}};var hh=Object.defineProperty,uh=(s,e,t,i)=>{for(var o=void 0,r=s.length-1,n;r>=0;r--)(n=s[r])&&(o=n(e,t,o)||o);return o&&hh(e,t,o),o};let wa=class extends Go{constructor(){super(...arguments),this.size=""}static get styles(){return[...super.styles,Bo,yd]}connectedCallback(){if(super.connectedCallback(),this._inputNode){const e=parseInt(this.size,10);e>0&&(this._inputNode.size=e)}}};uh([y({type:Number,reflect:!0})],wa.prototype,"size");customElements.get("craft-input")||customElements.define("craft-input",wa);/*! Copyright 2025 Fonticons, Inc. - https://webawesome.com/license */var ph=class extends Event{constructor(){super("wa-load",{bubbles:!0,cancelable:!1,composed:!0})}};/*! Copyright 2025 Fonticons, Inc. - https://webawesome.com/license */var oo="";function fh(s){oo=s}function mh(){if(!oo){const s=document.querySelector("[data-fa-kit-code]");s&&fh(s.getAttribute("data-fa-kit-code")||"")}return oo}var De="7.0.1";function bh(s,e,t){const i=mh(),o=i.length>0;let r="solid";return e==="notdog"?(t==="solid"&&(r="solid"),t==="duo-solid"&&(r="duo-solid"),`https://ka-p.fontawesome.com/releases/v${De}/svgs/notdog-${r}/${s}.svg?token=${encodeURIComponent(i)}`):e==="chisel"?`https://ka-p.fontawesome.com/releases/v${De}/svgs/chisel-regular/${s}.svg?token=${encodeURIComponent(i)}`:e==="etch"?`https://ka-p.fontawesome.com/releases/v${De}/svgs/etch-solid/${s}.svg?token=${encodeURIComponent(i)}`:e==="jelly"?(t==="regular"&&(r="regular"),t==="duo-regular"&&(r="duo-regular"),t==="fill-regular"&&(r="fill-regular"),`https://ka-p.fontawesome.com/releases/v${De}/svgs/jelly-${r}/${s}.svg?token=${encodeURIComponent(i)}`):e==="slab"?((t==="solid"||t==="regular")&&(r="regular"),t==="press-regular"&&(r="press-regular"),`https://ka-p.fontawesome.com/releases/v${De}/svgs/slab-${r}/${s}.svg?token=${encodeURIComponent(i)}`):e==="thumbprint"?`https://ka-p.fontawesome.com/releases/v${De}/svgs/thumbprint-light/${s}.svg?token=${encodeURIComponent(i)}`:e==="whiteboard"?`https://ka-p.fontawesome.com/releases/v${De}/svgs/whiteboard-semibold/${s}.svg?token=${encodeURIComponent(i)}`:(e==="classic"&&(t==="thin"&&(r="thin"),t==="light"&&(r="light"),t==="regular"&&(r="regular"),t==="solid"&&(r="solid")),e==="sharp"&&(t==="thin"&&(r="sharp-thin"),t==="light"&&(r="sharp-light"),t==="regular"&&(r="sharp-regular"),t==="solid"&&(r="sharp-solid")),e==="duotone"&&(t==="thin"&&(r="duotone-thin"),t==="light"&&(r="duotone-light"),t==="regular"&&(r="duotone-regular"),t==="solid"&&(r="duotone")),e==="sharp-duotone"&&(t==="thin"&&(r="sharp-duotone-thin"),t==="light"&&(r="sharp-duotone-light"),t==="regular"&&(r="sharp-duotone-regular"),t==="solid"&&(r="sharp-duotone-solid")),e==="brands"&&(r="brands"),o?`https://ka-p.fontawesome.com/releases/v${De}/svgs/${r}/${s}.svg?token=${encodeURIComponent(i)}`:`https://ka-f.fontawesome.com/releases/v${De}/svgs/${r}/${s}.svg`)}var gh={name:"default",resolver:(s,e="classic",t="solid")=>bh(s,e,t),mutator:(s,e)=>{if(e?.family&&!s.hasAttribute("data-duotone-initialized")){const{family:t,variant:i}=e;if(t==="duotone"||t==="sharp-duotone"||t==="notdog"&&i==="duo-solid"||t==="jelly"&&i==="duo-regular"||t==="thumbprint"){const o=[...s.querySelectorAll("path")],r=o.find(a=>!a.hasAttribute("opacity")),n=o.find(a=>a.hasAttribute("opacity"));if(!r||!n)return;if(r.setAttribute("data-duotone-primary",""),n.setAttribute("data-duotone-secondary",""),e.swapOpacity&&r&&n){const a=n.getAttribute("opacity")||"0.4";r.style.setProperty("--path-opacity",a),n.style.setProperty("--path-opacity","1")}s.setAttribute("data-duotone-initialized","")}}}},vh=gh;/*! Copyright 2025 Fonticons, Inc. - https://webawesome.com/license */function _h(s){return`data:image/svg+xml,${encodeURIComponent(s)}`}var Oi={solid:{check:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M434.8 70.1c14.3 10.4 17.5 30.4 7.1 44.7l-256 352c-5.5 7.6-14 12.3-23.4 13.1s-18.5-2.7-25.1-9.3l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l101.5 101.5 234-321.7c10.4-14.3 30.4-17.5 44.7-7.1z"/></svg>',"chevron-down":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M201.4 406.6c12.5 12.5 32.8 12.5 45.3 0l192-192c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L224 338.7 54.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l192 192z"/></svg>',"chevron-left":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l192 192c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L77.3 256 246.6 86.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-192 192z"/></svg>',"chevron-right":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M311.1 233.4c12.5 12.5 12.5 32.8 0 45.3l-192 192c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L243.2 256 73.9 86.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l192 192z"/></svg>',circle:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0z"/></svg>',eyedropper:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M341.6 29.2l-101.6 101.6-9.4-9.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3l-9.4-9.4 101.6-101.6c39-39 39-102.2 0-141.1s-102.2-39-141.1 0zM55.4 323.3c-15 15-23.4 35.4-23.4 56.6l0 42.4-26.6 39.9c-8.5 12.7-6.8 29.6 4 40.4s27.7 12.5 40.4 4l39.9-26.6 42.4 0c21.2 0 41.6-8.4 56.6-23.4l109.4-109.4-45.3-45.3-109.4 109.4c-3 3-7.1 4.7-11.3 4.7l-36.1 0 0-36.1c0-4.2 1.7-8.3 4.7-11.3l109.4-109.4-45.3-45.3-109.4 109.4z"/></svg>',"grip-vertical":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M128 40c0-22.1-17.9-40-40-40L40 0C17.9 0 0 17.9 0 40L0 88c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zm0 192c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zM0 424l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zM320 40c0-22.1-17.9-40-40-40L232 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zM192 232l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zM320 424c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48z"/></svg>',indeterminate:'<svg part="indeterminate-icon" class="icon" viewBox="0 0 16 16"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" stroke-linecap="round"><g stroke="currentColor" stroke-width="2"><g transform="translate(2.285714 6.857143)"><path d="M10.2857143,1.14285714 L1.14285714,1.14285714"/></g></g></g></svg>',minus:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M0 256c0-17.7 14.3-32 32-32l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 288c-17.7 0-32-14.3-32-32z"/></svg>',pause:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M48 32C21.5 32 0 53.5 0 80L0 432c0 26.5 21.5 48 48 48l64 0c26.5 0 48-21.5 48-48l0-352c0-26.5-21.5-48-48-48L48 32zm224 0c-26.5 0-48 21.5-48 48l0 352c0 26.5 21.5 48 48 48l64 0c26.5 0 48-21.5 48-48l0-352c0-26.5-21.5-48-48-48l-64 0z"/></svg>',play:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M91.2 36.9c-12.4-6.8-27.4-6.5-39.6 .7S32 57.9 32 72l0 368c0 14.1 7.5 27.2 19.6 34.4s27.2 7.5 39.6 .7l336-184c12.8-7 20.8-20.5 20.8-35.1s-8-28.1-20.8-35.1l-336-184z"/></svg>',star:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M309.5-18.9c-4.1-8-12.4-13.1-21.4-13.1s-17.3 5.1-21.4 13.1L193.1 125.3 33.2 150.7c-8.9 1.4-16.3 7.7-19.1 16.3s-.5 18 5.8 24.4l114.4 114.5-25.2 159.9c-1.4 8.9 2.3 17.9 9.6 23.2s16.9 6.1 25 2L288.1 417.6 432.4 491c8 4.1 17.7 3.3 25-2s11-14.2 9.6-23.2L441.7 305.9 556.1 191.4c6.4-6.4 8.6-15.8 5.8-24.4s-10.1-14.9-19.1-16.3L383 125.3 309.5-18.9z"/></svg>',user:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M224 248a120 120 0 1 0 0-240 120 120 0 1 0 0 240zm-29.7 56C95.8 304 16 383.8 16 482.3 16 498.7 29.3 512 45.7 512l356.6 0c16.4 0 29.7-13.3 29.7-29.7 0-98.5-79.8-178.3-178.3-178.3l-59.4 0z"/></svg>',xmark:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M55.1 73.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L147.2 256 9.9 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192.5 301.3 329.9 438.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.8 256 375.1 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192.5 210.7 55.1 73.4z"/></svg>'},regular:{"circle-question":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M464 256a208 208 0 1 0 -416 0 208 208 0 1 0 416 0zM0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0zm256-80c-17.7 0-32 14.3-32 32 0 13.3-10.7 24-24 24s-24-10.7-24-24c0-44.2 35.8-80 80-80s80 35.8 80 80c0 47.2-36 67.2-56 74.5l0 3.8c0 13.3-10.7 24-24 24s-24-10.7-24-24l0-8.1c0-20.5 14.8-35.2 30.1-40.2 6.4-2.1 13.2-5.5 18.2-10.3 4.3-4.2 7.7-10 7.7-19.6 0-17.7-14.3-32-32-32zM224 368a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>',"circle-xmark":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M256 48a208 208 0 1 1 0 416 208 208 0 1 1 0-416zm0 464a256 256 0 1 0 0-512 256 256 0 1 0 0 512zM167 167c-9.4 9.4-9.4 24.6 0 33.9l55 55-55 55c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l55-55 55 55c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-55-55 55-55c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-55 55-55-55c-9.4-9.4-24.6-9.4-33.9 0z"/></svg>',copy:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M384 336l-192 0c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l133.5 0c4.2 0 8.3 1.7 11.3 4.7l58.5 58.5c3 3 4.7 7.1 4.7 11.3L400 320c0 8.8-7.2 16-16 16zM192 384l192 0c35.3 0 64-28.7 64-64l0-197.5c0-17-6.7-33.3-18.7-45.3L370.7 18.7C358.7 6.7 342.5 0 325.5 0L192 0c-35.3 0-64 28.7-64 64l0 256c0 35.3 28.7 64 64 64zM64 128c-35.3 0-64 28.7-64 64L0 448c0 35.3 28.7 64 64 64l192 0c35.3 0 64-28.7 64-64l0-16-48 0 0 16c0 8.8-7.2 16-16 16L64 464c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l16 0 0-48-16 0z"/></svg>',eye:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M288 80C222.8 80 169.2 109.6 128.1 147.7 89.6 183.5 63 226 49.4 256 63 286 89.6 328.5 128.1 364.3 169.2 402.4 222.8 432 288 432s118.8-29.6 159.9-67.7C486.4 328.5 513 286 526.6 256 513 226 486.4 183.5 447.9 147.7 406.8 109.6 353.2 80 288 80zM95.4 112.6C142.5 68.8 207.2 32 288 32s145.5 36.8 192.6 80.6c46.8 43.5 78.1 95.4 93 131.1 3.3 7.9 3.3 16.7 0 24.6-14.9 35.7-46.2 87.7-93 131.1-47.1 43.7-111.8 80.6-192.6 80.6S142.5 443.2 95.4 399.4c-46.8-43.5-78.1-95.4-93-131.1-3.3-7.9-3.3-16.7 0-24.6 14.9-35.7 46.2-87.7 93-131.1zM288 336c44.2 0 80-35.8 80-80 0-29.6-16.1-55.5-40-69.3-1.4 59.7-49.6 107.9-109.3 109.3 13.8 23.9 39.7 40 69.3 40zm-79.6-88.4c2.5 .3 5 .4 7.6 .4 35.3 0 64-28.7 64-64 0-2.6-.2-5.1-.4-7.6-37.4 3.9-67.2 33.7-71.1 71.1zm45.6-115c10.8-3 22.2-4.5 33.9-4.5 8.8 0 17.5 .9 25.8 2.6 .3 .1 .5 .1 .8 .2 57.9 12.2 101.4 63.7 101.4 125.2 0 70.7-57.3 128-128 128-61.6 0-113-43.5-125.2-101.4-1.8-8.6-2.8-17.5-2.8-26.6 0-11 1.4-21.8 4-32 .2-.7 .3-1.3 .5-1.9 11.9-43.4 46.1-77.6 89.5-89.5z"/></svg>',"eye-slash":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M41-24.9c-9.4-9.4-24.6-9.4-33.9 0S-2.3-.3 7 9.1l528 528c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-96.4-96.4c2.7-2.4 5.4-4.8 8-7.2 46.8-43.5 78.1-95.4 93-131.1 3.3-7.9 3.3-16.7 0-24.6-14.9-35.7-46.2-87.7-93-131.1-47.1-43.7-111.8-80.6-192.6-80.6-56.8 0-105.6 18.2-146 44.2L41-24.9zM176.9 111.1c32.1-18.9 69.2-31.1 111.1-31.1 65.2 0 118.8 29.6 159.9 67.7 38.5 35.7 65.1 78.3 78.6 108.3-13.6 30-40.2 72.5-78.6 108.3-3.1 2.8-6.2 5.6-9.4 8.4L393.8 328c14-20.5 22.2-45.3 22.2-72 0-70.7-57.3-128-128-128-26.7 0-51.5 8.2-72 22.2l-39.1-39.1zm182 182l-108-108c11.1-5.8 23.7-9.1 37.1-9.1 44.2 0 80 35.8 80 80 0 13.4-3.3 26-9.1 37.1zM103.4 173.2l-34-34c-32.6 36.8-55 75.8-66.9 104.5-3.3 7.9-3.3 16.7 0 24.6 14.9 35.7 46.2 87.7 93 131.1 47.1 43.7 111.8 80.6 192.6 80.6 37.3 0 71.2-7.9 101.5-20.6L352.2 422c-20 6.4-41.4 10-64.2 10-65.2 0-118.8-29.6-159.9-67.7-38.5-35.7-65.1-78.3-78.6-108.3 10.4-23.1 28.6-53.6 54-82.8z"/></svg>',star:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M288.1-32c9 0 17.3 5.1 21.4 13.1L383 125.3 542.9 150.7c8.9 1.4 16.3 7.7 19.1 16.3s.5 18-5.8 24.4L441.7 305.9 467 465.8c1.4 8.9-2.3 17.9-9.6 23.2s-17 6.1-25 2L288.1 417.6 143.8 491c-8 4.1-17.7 3.3-25-2s-11-14.2-9.6-23.2L134.4 305.9 20 191.4c-6.4-6.4-8.6-15.8-5.8-24.4s10.1-14.9 19.1-16.3l159.9-25.4 73.6-144.2c4.1-8 12.4-13.1 21.4-13.1zm0 76.8L230.3 158c-3.5 6.8-10 11.6-17.6 12.8l-125.5 20 89.8 89.9c5.4 5.4 7.9 13.1 6.7 20.7l-19.8 125.5 113.3-57.6c6.8-3.5 14.9-3.5 21.8 0l113.3 57.6-19.8-125.5c-1.2-7.6 1.3-15.3 6.7-20.7l89.8-89.9-125.5-20c-7.6-1.2-14.1-6-17.6-12.8L288.1 44.8z"/></svg>'}},yh={name:"system",resolver:(s,e="classic",t="solid")=>{let i=Oi[t][s]??Oi.regular[s]??Oi.regular["circle-question"];return i?_h(i):""}},wh=yh;/*! Copyright 2025 Fonticons, Inc. - https://webawesome.com/license */var Eh="classic",kh=[vh,wh],ro=[];function xh(s){ro.push(s)}function Ch(s){ro=ro.filter(e=>e!==s)}function Li(s){return kh.find(e=>e.name===s)}function $h(){return Eh}/*! Copyright 2025 Fonticons, Inc. - https://webawesome.com/license */var Ah=class extends Event{constructor(){super("wa-error",{bubbles:!0,cancelable:!1,composed:!0})}},Sh=`:host {
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
`,jt=Symbol(),Ts=Symbol(),Ri,Fi=new Map,ue=class extends me{constructor(){super(...arguments),this.svg=null,this.autoWidth=!1,this.swapOpacity=!1,this.label="",this.library="default",this.resolveIcon=async(e,t)=>{let i;if(t?.spriteSheet){this.hasUpdated||await this.updateComplete,this.svg=k`<svg part="svg">
        <use part="use" href="${e}"></use>
      </svg>`,await this.updateComplete;const o=this.shadowRoot.querySelector("[part='svg']");return typeof t.mutator=="function"&&t.mutator(o,this),this.svg}try{if(i=await fetch(e,{mode:"cors"}),!i.ok)return i.status===410?jt:Ts}catch{return Ts}try{const o=document.createElement("div");o.innerHTML=await i.text();const r=o.firstElementChild;if(r?.tagName?.toLowerCase()!=="svg")return jt;Ri||(Ri=new DOMParser);const n=Ri.parseFromString(r.outerHTML,"text/html").body.querySelector("svg");return n?(n.part.add("svg"),document.adoptNode(n)):jt}catch{return jt}}}connectedCallback(){super.connectedCallback(),xh(this)}firstUpdated(e){super.firstUpdated(e),this.setIcon()}disconnectedCallback(){super.disconnectedCallback(),Ch(this)}getIconSource(){const e=Li(this.library),t=this.family||$h();return this.name&&e?{url:e.resolver(this.name,t,this.variant,this.autoWidth),fromLibrary:!0}:{url:this.src,fromLibrary:!1}}handleLabelChange(){typeof this.label=="string"&&this.label.length>0?(this.setAttribute("role","img"),this.setAttribute("aria-label",this.label),this.removeAttribute("aria-hidden")):(this.removeAttribute("role"),this.removeAttribute("aria-label"),this.setAttribute("aria-hidden","true"))}async setIcon(){const{url:e,fromLibrary:t}=this.getIconSource(),i=t?Li(this.library):void 0;if(!e){this.svg=null;return}let o=Fi.get(e);o||(o=this.resolveIcon(e,i),Fi.set(e,o));const r=await o;if(r===Ts&&Fi.delete(e),e===this.getIconSource().url){if(ma(r)){this.svg=r;return}switch(r){case Ts:case jt:this.svg=null,this.dispatchEvent(new Ah);break;default:this.svg=r.cloneNode(!0),i?.mutator?.(this.svg,this),this.dispatchEvent(new ph)}}}updated(e){super.updated(e);const t=Li(this.library),i=this.shadowRoot?.querySelector("svg");i&&t?.mutator?.(i,this)}render(){return this.hasUpdated?this.svg:k`<svg part="svg" fill="currentColor" width="16" height="16"></svg>`}};ue.css=Sh;v([be()],ue.prototype,"svg",2);v([y({reflect:!0})],ue.prototype,"name",2);v([y({reflect:!0})],ue.prototype,"family",2);v([y({reflect:!0})],ue.prototype,"variant",2);v([y({attribute:"auto-width",type:Boolean,reflect:!0})],ue.prototype,"autoWidth",2);v([y({attribute:"swap-opacity",type:Boolean,reflect:!0})],ue.prototype,"swapOpacity",2);v([y()],ue.prototype,"src",2);v([y()],ue.prototype,"label",2);v([y({reflect:!0})],ue.prototype,"library",2);v([ye("label")],ue.prototype,"handleLabelChange",1);v([ye(["family","name","library","variant","src","autoWidth","swapOpacity"])],ue.prototype,"setIcon",1);ue=v([Ae("wa-icon")],ue);const Nh=R``;let Th=class extends ue{static get styles(){return[ue.styles,Nh]}};customElements.get("craft-icon")||customElements.define("craft-icon",Th);var Oh=Object.defineProperty,Lh=(s,e,t,i)=>{for(var o=void 0,r=s.length-1,n;r>=0;r--)(n=s[r])&&(o=n(e,t,o)||o);return o&&Oh(e,t,o),o};let Ea=class extends Go{constructor(){super(),this._visible=!1,this.reveal=()=>{this._visible=!this._visible,this.type=this._visible?"text":"password"},this.renderSuffix=()=>k`
      <craft-button
        icon
        size="small"
        variant="plain"
        @click="${this.reveal}"
        appearance="plain"
      >
        <span class="icon"
          >${this._visible?k`<craft-icon name="eye-slash"></craft-icon>`:k`<craft-icon name="eye"></craft-icon>`}
        </span>
      </craft-button>
    `,this.type="password"}static get styles(){return[...super.styles,Bo,R`
        .input-group__container {
          position: relative;
        }

        .input-group__suffix {
          position: absolute;
          inset-inline-end: var(--c-input-spacing-inline);
          inset-block-start: 50%;
          transform: translateY(calc(-50%));
        }
      `]}get slots(){return{...super.slots,suffix:()=>({template:this.renderSuffix()})}}};Lh([be()],Ea.prototype,"_visible");customElements.get("craft-input-password")||customElements.define("craft-input-password",Ea);const Rh=R`
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
`;var Fh=Object.defineProperty,ka=(s,e,t,i)=>{for(var o=void 0,r=s.length-1,n;r>=0;r--)(n=s[r])&&(o=n(e,t,o)||o);return o&&Fh(e,t,o),o};const xa=class extends G{constructor(){super(...arguments),this.size="",this.variant=""}render(){const e=!!this.querySelector('[slot="prefix"]'),t=!!this.querySelector('[slot="suffix"]');return k`
      <div
        class="${Re({chip:!0,"chip--small":this.size==="small","chip--medium":this.size==="medium","chip--large":this.size==="large","chip--plain":this.variant==="plain"})}"
      >
        ${e?k`<div class="chip__prefix"><slot name="prefix"></slot></div>`:V}
        <div class="chip__body">
          <slot></slot>
        </div>
        ${t?k`<div class="chip__suffix"><slot name="suffix"></slot></div>`:V}
      </div>
    `}};xa.styles=[Rh];let Zo=xa;ka([y()],Zo.prototype,"size");ka([y()],Zo.prototype,"variant");customElements.get("craft-chip")||customElements.define("craft-chip",Zo);const Ph=R`
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
`;var Mh=Object.defineProperty,Ca=(s,e,t,i)=>{for(var o=void 0,r=s.length-1,n;r>=0;r--)(n=s[r])&&(o=n(e,t,o)||o);return o&&Mh(e,t,o),o};const $a=class extends G{constructor(){super(...arguments),this.label=null,this.status=null}getLabel(){return!this.label&&this.status?`Status: ${this.status}`:this.label}render(){return k`
      <span
        class="${Re({status:!0,"status--live":this.status==="live","status--enabled":this.status==="enabled","status--pending":this.status==="pending","status--expired":this.status==="expired","status--disabled":this.status==="disabled"})}"
        role="img"
        aria-label="${this.getLabel()}"
      ></span>
    `}};$a.styles=[Ph];let Yo=$a;Ca([y()],Yo.prototype,"label");Ca([y()],Yo.prototype,"status");customElements.get("craft-status")||customElements.define("craft-status",Yo);const Ih=R`
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
`;var ts=new Map;function Dh(s){var e=ts.get(s);e&&e.destroy()}function Vh(s){var e=ts.get(s);e&&e.update()}var Kt=null;typeof window>"u"?((Kt=function(s){return s}).destroy=function(s){return s},Kt.update=function(s){return s}):((Kt=function(s,e){return s&&Array.prototype.forEach.call(s.length?s:[s],function(t){return(function(i){if(i&&i.nodeName&&i.nodeName==="TEXTAREA"&&!ts.has(i)){var o,r=null,n=window.getComputedStyle(i),a=(o=i.value,function(){c({testForHeightReduction:o===""||!i.value.startsWith(o),restoreTextAlign:null}),o=i.value}),l=(function(u){i.removeEventListener("autosize:destroy",l),i.removeEventListener("autosize:update",d),i.removeEventListener("input",a),window.removeEventListener("resize",d),Object.keys(u).forEach(function(b){return i.style[b]=u[b]}),ts.delete(i)}).bind(i,{height:i.style.height,resize:i.style.resize,textAlign:i.style.textAlign,overflowY:i.style.overflowY,overflowX:i.style.overflowX,wordWrap:i.style.wordWrap});i.addEventListener("autosize:destroy",l),i.addEventListener("autosize:update",d),i.addEventListener("input",a),window.addEventListener("resize",d),i.style.overflowX="hidden",i.style.wordWrap="break-word",ts.set(i,{destroy:l,update:d}),d()}function c(u){var b,p,m=u.restoreTextAlign,g=m===void 0?null:m,w=u.testForHeightReduction,E=w===void 0||w,x=n.overflowY;if(i.scrollHeight!==0&&(n.resize==="vertical"?i.style.resize="none":n.resize==="both"&&(i.style.resize="horizontal"),E&&(b=(function($){for(var z=[];$&&$.parentNode&&$.parentNode instanceof Element;)$.parentNode.scrollTop&&z.push([$.parentNode,$.parentNode.scrollTop]),$=$.parentNode;return function(){return z.forEach(function(M){var D=M[0],Y=M[1];D.style.scrollBehavior="auto",D.scrollTop=Y,D.style.scrollBehavior=null})}})(i),i.style.height=""),p=n.boxSizing==="content-box"?i.scrollHeight-(parseFloat(n.paddingTop)+parseFloat(n.paddingBottom)):i.scrollHeight+parseFloat(n.borderTopWidth)+parseFloat(n.borderBottomWidth),n.maxHeight!=="none"&&p>parseFloat(n.maxHeight)?(n.overflowY==="hidden"&&(i.style.overflow="scroll"),p=parseFloat(n.maxHeight)):n.overflowY!=="hidden"&&(i.style.overflow="hidden"),i.style.height=p+"px",g&&(i.style.textAlign=g),b&&b(),r!==p&&(i.dispatchEvent(new Event("autosize:resized",{bubbles:!0})),r=p),x!==n.overflow&&!g)){var A=n.textAlign;n.overflow==="hidden"&&(i.style.textAlign=A==="start"?"end":"start"),c({restoreTextAlign:A,testForHeightReduction:!0})}}function d(){c({testForHeightReduction:!0,restoreTextAlign:null})}})(t)}),s}).destroy=function(s){return s&&Array.prototype.forEach.call(s.length?s:[s],Dh),s},Kt.update=function(s){return s&&Array.prototype.forEach.call(s.length?s:[s],Vh),s});var Pi=Kt;let zh=class extends Ko{get _inputNode(){return Array.from(this.children).find(e=>e.slot==="input")}},Bh=class extends ya(zh){static get properties(){return{maxRows:{type:Number,attribute:"max-rows"},rows:{type:Number,reflect:!0},readOnly:{type:Boolean,attribute:"readonly",reflect:!0},placeholder:{type:String,reflect:!0}}}get slots(){return{...super.slots,input:()=>{const e=document.createElement("textarea");return e.style.resize!==void 0&&(e.style.resize="none"),e}}}constructor(){super(),this.rows=2,this.maxRows=6,this.readOnly=!1,this.placeholder=""}connectedCallback(){super.connectedCallback(),this.__initializeAutoresize(),this.__intersectionObserver=new IntersectionObserver(()=>this.resizeTextarea()),this.__intersectionObserver.observe(this)}updated(e){if(super.updated(e),e.has("name")&&(this._inputNode.name=this.name),e.has("autocomplete")&&(this._inputNode.autocomplete=this.autocomplete),e.has("disabled")&&(this._inputNode.disabled=this.disabled,this.validate()),e.has("rows")){const t=this._inputNode;t&&(t.rows=this.rows)}if(e.has("readOnly")){const t=this._inputNode;t&&(t.readOnly=this.readOnly)}if(e.has("placeholder")){const t=this._inputNode;t&&(t.placeholder=this.placeholder)}e.has("modelValue")&&this.resizeTextarea(),(e.has("maxRows")||e.has("rows"))&&this.setTextareaMaxHeight()}disconnectedCallback(){super.disconnectedCallback(),Pi.destroy(this._inputNode)}setTextareaMaxHeight(){const{value:e}=this._inputNode;this._inputNode.value="",this.resizeTextarea();const t=window.getComputedStyle(this._inputNode,null),i=parseFloat(t.lineHeight)||parseFloat(t.height)/this.rows,o=parseFloat(t.paddingTop)+parseFloat(t.paddingBottom),r=parseFloat(t.borderTopWidth)+parseFloat(t.borderBottomWidth),n=t.boxSizing==="border-box"?o+r:0;this._inputNode.style.maxHeight=`${i*this.maxRows+n}px`,this._inputNode.value=e,this.resizeTextarea()}static get styles(){return[...super.styles,R`
        .input-group__container > .input-group__input ::slotted(.form-control) {
          box-sizing: content-box;
          overflow-x: hidden; /* for FF adds height to the TextArea to reserve place for scroll-bars */
        }

        /* Workaround https://bugzilla.mozilla.org/show_bug.cgi?id=1739079 */
        :host([disabled]) ::slotted(textarea) {
          user-select: none;
        }
      `]}get updateComplete(){return this.__textareaUpdateComplete?Promise.all([this.__textareaUpdateComplete,super.updateComplete]):super.updateComplete}resizeTextarea(){Pi.update(this._inputNode)}__initializeAutoresize(){this.__shady_native_contains?this.__textareaUpdateComplete=this.__waitForTextareaRenderedInRealDOM().then(()=>{this.__startAutoresize(),this.__textareaUpdateComplete=null}):this.__startAutoresize()}async __waitForTextareaRenderedInRealDOM(){let e=3;for(;e!==0&&!this.__shady_native_contains(this._inputNode);)await new Promise(t=>setTimeout(t)),e-=1}__startAutoresize(){Pi(this._inputNode),this.setTextareaMaxHeight()}},Uh=class extends Bh{static get styles(){return[...super.styles,Bo,Ih]}};customElements.get("craft-textarea")||customElements.define("craft-textarea",Uh);const Hh=R`
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
`,Aa=class extends G{render(){return k`<slot></slot>`}};Aa.styles=[Hh];let jh=Aa;customElements.get("craft-button-group")||customElements.define("craft-button-group",jh);const qh=R`
  :host {
    width: 100%;
  }

  ::slotted(label) {
    font-weight: bold;
  }

  .form-field__group-one {
    margin-bottom: var(--c-spacing-sm);
  }

  .form-field__help-text {
    font-size: var(--text-sm);
    color: var(--color-slate-600);
  }

  #overlay-content-node-wrapper {
    background-color: canvas;
    padding: var(--c-spacing-sm);
    border: 1px solid var(--c-color-neutral-border-subtle);
    border-radius: var(--c-radius-md);
  }
`,Wh=R`
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
`,Kh=(s,e={})=>s.value!==e.value||s.checked!==e.checked,Gh=s=>class extends qo(s){static get properties(){return{checked:{type:Boolean,reflect:!0},disabled:{type:Boolean,reflect:!0},modelValue:{type:Object,hasChanged:Kh},choiceValue:{type:Object}}}get choiceValue(){return this.modelValue.value}set choiceValue(e){this.requestUpdate("choiceValue",this.choiceValue),this.modelValue.value!==e&&(this.modelValue={value:e,checked:this.modelValue.checked})}requestUpdate(e,t,i){super.requestUpdate(e,t,i),e==="modelValue"?this.modelValue.checked!==this.checked&&this.__syncModelCheckedToChecked(this.modelValue.checked):e==="checked"&&this.modelValue.checked!==this.checked&&this.__syncCheckedToModel(this.checked)}firstUpdated(e){super.firstUpdated(e),e.has("checked")&&this.__syncCheckedToInputElement()}updated(e){super.updated(e),e.has("modelValue")&&this.__syncCheckedToInputElement(),e.has("name")&&this._parentFormGroup&&this._parentFormGroup.name!==this.name&&this._syncNameToParentFormGroup()}constructor(){super(),this.modelValue={value:"",checked:!1},this.disabled=!1,this._preventDuplicateLabelClick=this._preventDuplicateLabelClick.bind(this),this._toggleChecked=this._toggleChecked.bind(this)}static get styles(){return[...super.styles||[],R`
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
        `]}render(){return k`
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
      `}_choiceGraphicTemplate(){return V}_afterTemplate(){return V}connectedCallback(){super.connectedCallback(),this._labelNode&&this._labelNode.addEventListener("click",this._preventDuplicateLabelClick),this.addEventListener("user-input-changed",this._toggleChecked)}disconnectedCallback(){super.disconnectedCallback(),this._labelNode&&this._labelNode.removeEventListener("click",this._preventDuplicateLabelClick),this.removeEventListener("user-input-changed",this._toggleChecked)}_preventDuplicateLabelClick(e){const t=(i=>{i.stopImmediatePropagation(),this._inputNode.removeEventListener("click",t)});this._inputNode.addEventListener("click",t)}_toggleChecked(e){this.disabled||(this._isHandlingUserInput=!0,this.checked=!this.checked,this._isHandlingUserInput=!1)}_syncNameToParentFormGroup(){this._parentFormGroup.tagName.includes(this.tagName)&&(this.name=this._parentFormGroup?.name||"")}__syncModelCheckedToChecked(e){this.checked=e}__syncCheckedToModel(e){this.modelValue={value:this.choiceValue,checked:e}}__syncCheckedToInputElement(){this._inputNode&&(this._inputNode.checked=this.checked)}_proxyInputEvent(){}_onModelValueChanged({modelValue:e},t){let i;t&&t.modelValue&&(i=t.modelValue),this.constructor.elementProperties.get("modelValue").hasChanged(e,i)&&super._onModelValueChanged({modelValue:e})}parser(){return this.modelValue}formatter(e){return e&&e.value!==void 0?e.value:e}clear(){this.checked=!1}_isEmpty(){return!this.checked}_syncValueUpwards(){}},Jo=te(Gh);let Pr=class extends gs(Jo(Ho(Qe(G)))){static get properties(){return{active:{type:Boolean,reflect:!0}}}static get styles(){return[R`
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
      `]}get slots(){return{}}constructor(){super(),this.active=!1,this.__onClick=this.__onClick.bind(this),this.__registerEventListeners()}requestUpdate(e,t,i){super.requestUpdate(e,t,i),e==="active"&&this.active!==t&&this.dispatchEvent(new Event("active-changed",{bubbles:!0}))}updated(e){super.updated(e),e.has("checked")&&this.setAttribute("aria-selected",`${this.checked}`),e.has("disabled")&&this.setAttribute("aria-disabled",`${this.disabled}`)}render(){return k`
      <div class="choice-field__label">
        <slot></slot>
      </div>
    `}connectedCallback(){super.connectedCallback(),this.setAttribute("role","option")}__registerEventListeners(){this.addEventListener("click",this.__onClick)}__unRegisterEventListeners(){this.removeEventListener("click",this.__onClick)}__onClick(){if(this.disabled)return;const e=this._parentFormGroup;this._isHandlingUserInput=!0,e&&e.multipleChoice?(this.checked=!this.checked,this.active=!this.active):(this.checked=!0,this.active=!0),this._isHandlingUserInput=!1}},Zh=class extends Pr{static get styles(){return[...Pr.styles,Wh]}};customElements.get("craft-option")||customElements.define("craft-option",Zh);let no=class extends Array{_keys(){return Object.keys(this).filter(e=>Number.isNaN(Number(e)))}};const Yh=s=>class extends Ho(s){static get properties(){return{_isFormOrFieldset:{type:Boolean}}}constructor(){super(),this.formElements=new no,this._isFormOrFieldset=!1,this._onRequestToAddFormElement=this._onRequestToAddFormElement.bind(this),this._onRequestToChangeFormElementName=this._onRequestToChangeFormElementName.bind(this),this.addEventListener("form-element-register",this._onRequestToAddFormElement),this.addEventListener("form-element-name-changed",this._onRequestToChangeFormElementName),this.initComplete=new Promise((e,t)=>{this.__resolveInitComplete=e,this.__rejectInitComplete=t}),this.registrationComplete=new Promise((e,t)=>{this.__resolveRegistrationComplete=e,this.__rejectRegistrationComplete=t}),this.registrationComplete.done=!1,this.registrationComplete.then(()=>{this.registrationComplete.done=!0,this.__resolveInitComplete(void 0)},()=>{throw this.registrationComplete.done=!0,this.__rejectInitComplete(void 0),new Error("Registration could not finish. Please use await el.registrationComplete;")})}connectedCallback(){super.connectedCallback(),this._completeRegistration()}_completeRegistration(){Promise.resolve().then(()=>this.__resolveRegistrationComplete(void 0))}disconnectedCallback(){super.disconnectedCallback(),this.registrationComplete.done===!1&&Promise.resolve().then(()=>{Promise.resolve().then(()=>{this.__rejectRegistrationComplete()})})}isRegisteredFormElement(e){return this.formElements.some(t=>t===e)}addFormElement(e,t){if(e._parentFormGroup=this,t>=0?this.formElements.splice(t,0,e):this.formElements.push(e),this._isFormOrFieldset){const{name:i}=e;if(i===this.name)throw console.info("Error Node:",e),new TypeError(`You can not have the same name "${i}" as your parent`);if(i.substr(-2)==="[]")Array.isArray(this.formElements[i])||(this.formElements[i]=new no),t>0?this.formElements[i].splice(t,0,e):this.formElements[i].push(e);else if(!this.formElements[i])this.formElements[i]=e;else throw console.info("Error Node:",e),new TypeError(`Name "${i}" is already registered - if you want an array add [] to the end`)}}removeFormElement(e){const t=this.formElements.indexOf(e);if(t>-1&&this.formElements.splice(t,1),this._isFormOrFieldset){const{name:i}=e;if(i.substr(-2)==="[]"&&this.formElements[i]){const o=this.formElements[i].indexOf(e);o>-1&&this.formElements[i].splice(o,1)}else this.formElements[i]&&delete this.formElements[i]}}_onRequestToAddFormElement(e){const t=e.detail.element;if(t===this||this.isRegisteredFormElement(t))return;e.stopPropagation();let i=-1;if(this.formElements&&Array.isArray(this.formElements)){for(const[o,r]of this.formElements.entries())if(!(r.compareDocumentPosition(t)&Node.DOCUMENT_POSITION_FOLLOWING)){i=o;break}}this.addFormElement(t,i)}_onRequestToChangeFormElementName(e){const t=this.formElements[e.detail.oldName];t&&(this.formElements[e.detail.newName]=t,delete this.formElements[e.detail.oldName])}_onRequestToRemoveFormElement(e){const t=e.detail.element;t!==this&&this.isRegisteredFormElement(t)&&(e.stopPropagation(),this.removeFormElement(t))}},Xo=te(Yh),Jh=s=>class extends Xo(_s(Wo(s))){static get properties(){return{multipleChoice:{type:Boolean,attribute:"multiple-choice"}}}get modelValue(){const e=this._getCheckedElements();return this.multipleChoice?e.map(t=>t.choiceValue):e[0]?e[0].choiceValue:""}set modelValue(e){const t=(i,o)=>typeof i.choiceValue=="object"?JSON.stringify(i.choiceValue)===JSON.stringify(e):i.choiceValue===o;this.__isInitialModelValue?this.registrationComplete.then(()=>{this.__isInitialModelValue=!1,this._setCheckedElements(e,t),this.requestUpdate("modelValue",this._oldModelValue)}):(this._setCheckedElements(e,t),this.requestUpdate("modelValue",this._oldModelValue)),this._oldModelValue=this.modelValue}get serializedValue(){const e=this._getCheckedElements();return this.multipleChoice?e.map(t=>t.serializedValue.value):e[0]?e[0].serializedValue.value:""}set serializedValue(e){const t=(i,o)=>i.serializedValue.value===o;this.__isInitialSerializedValue?this.registrationComplete.then(()=>{this.__isInitialSerializedValue=!1,this._setCheckedElements(e,t),this.requestUpdate("serializedValue")}):(this._setCheckedElements(e,t),this.requestUpdate("serializedValue"))}get formattedValue(){const e=this._getCheckedElements();return this.multipleChoice?e.map(t=>t.formattedValue):e[0]?e[0].formattedValue:""}set formattedValue(e){const t=(i,o)=>i.formattedValue===o;this.__isInitialFormattedValue?this.registrationComplete.then(()=>{this.__isInitialFormattedValue=!1,this._setCheckedElements(e,t)}):this._setCheckedElements(e,t)}get operationMode(){return this._repropagationRole==="choice-group"?"select":"enter"}constructor(){super(),this.multipleChoice=!1,this._repropagationRole="choice-group",this.__isInitialModelValue=!0,this.__isInitialSerializedValue=!0,this.__isInitialFormattedValue=!0}connectedCallback(){super.connectedCallback(),this.registrationComplete.then(()=>{this.__isInitialModelValue=!1,this.__isInitialSerializedValue=!1,this.__isInitialFormattedValue=!1})}_completeRegistration(){Promise.resolve().then(()=>super._completeRegistration())}updated(e){super.updated(e),e.has("name")&&this.name!==e.get("name")&&this.formElements.forEach(t=>{t.name=this.name})}addFormElement(e,t){this._throwWhenInvalidChildModelValue(e),e.name=this.name,super.addFormElement(e,t)}clear(){this.multipleChoice?this.modelValue=[]:this.modelValue=""}_triggerInitialModelValueChangedEvent(){this.registrationComplete.then(()=>{this._dispatchInitialModelValueChangedEvent()})}_getFromAllFormElementsFilter(e,t){return!0}_getFromAllFormElements(e,t){const i=t||this._getFromAllFormElementsFilter;return e==="modelValue"||e==="serializedValue"||e==="formattedValue"?this[e]:this.formElements.filter(o=>i(o,e)).map(o=>o.property)}_throwWhenInvalidChildModelValue(e){if(typeof e.modelValue.checked!="boolean"||!Object.prototype.hasOwnProperty.call(e.modelValue,"value"))throw new Error(`The ${this.tagName.toLowerCase()} name="${this.name}" does not allow to register ${e.tagName.toLowerCase()} with .modelValue="${e.modelValue}" - The modelValue should represent an Object { value: "foo", checked: false }`)}_isEmpty(){return this.multipleChoice?this.modelValue.length===0:typeof this.modelValue=="string"&&this.modelValue===""||this.modelValue===void 0||this.modelValue===null}_checkSingleChoiceElements(e){const{target:t}=e;if(t.checked===!1)return;const i=t.name;this.formElements.filter(o=>o.name===i).forEach(o=>{o!==t&&(o.checked=!1)})}_getCheckedElements(){return this.formElements.filter(e=>e.checked&&!e.disabled)}_setCheckedElements(e,t){if(e==null){this.formElements.forEach(i=>i.checked=!1);return}for(let i=0;i<this.formElements.length;i+=1)if(this.multipleChoice){let o=e.includes(this.formElements[i].modelValue.value);typeof this.formElements[i].modelValue.value=="object"&&(o=e.map(r=>JSON.stringify(r)).includes(JSON.stringify(this.formElements[i].modelValue.value))),this.formElements[i].checked=o}else t(this.formElements[i],e)?this.formElements[i].checked=!0:this.formElements[i].checked=!1}__setChoiceGroupTouched(){const e=this.modelValue;e!=null&&e!==this.__previousCheckedValue&&(this.touched=!0,this.__previousCheckedValue=e)}_onBeforeRepropagateChildrenValues(e){const t=e.detail&&e.detail.element||e.target;this.multipleChoice||!t.checked||(this.formElements.forEach(i=>{t.choiceValue!==i.choiceValue&&(i.checked=!1)}),this.__setChoiceGroupTouched(),this.requestUpdate("modelValue",this._oldModelValue),this._oldModelValue=this.modelValue)}_repropagationCondition(e){return!(this._repropagationRole==="choice-group"&&!this.multipleChoice&&!e.checked)}},Sa=te(Jh);function Mi(s="google-chrome"){const e=globalThis.navigator,t=!!e.userAgentData&&e.userAgentData.brands.some(a=>a.brand==="Chromium");if(s==="chromium")return t;const i=globalThis.navigator?.vendor,o=typeof globalThis.opr<"u",r=globalThis.userAgent?.indexOf("Edge")>-1,n=globalThis.userAgent?.match("CriOS");if(s==="ios")return n;if(s==="google-chrome")return t!==null&&typeof t<"u"&&i==="Google Inc."&&o===!1&&r===!1}const Js={isChrome:Mi(),isIOSChrome:Mi("ios"),isChromium:Mi("chromium"),isFirefox:globalThis.navigator?.userAgent.toLowerCase().indexOf("firefox")>-1,isMac:globalThis.navigator?.appVersion?.indexOf("Mac")!==-1,isIOS:/iPhone|iPad|iPod/i.test(globalThis.navigator?.userAgent),isMacSafari:globalThis.navigator?.vendor&&globalThis.navigator?.vendor.indexOf("Apple")>-1&&globalThis.navigator?.userAgent&&globalThis.navigator?.userAgent.indexOf("CriOS")===-1&&globalThis.navigator?.userAgent.indexOf("FxiOS")===-1&&globalThis.navigator?.appVersion.indexOf("Mac")!==-1},Xh=s=>class extends s{constructor(){super(),this.registrationTarget=void 0,this.__redispatchEventForFormRegistrarPortalMixin=this.__redispatchEventForFormRegistrarPortalMixin.bind(this),this.addEventListener("form-element-register",this.__redispatchEventForFormRegistrarPortalMixin)}__redispatchEventForFormRegistrarPortalMixin(e){if(e.stopPropagation(),!this.registrationTarget)throw new Error("A FormRegistrarPortal element requires a .registrationTarget");this.registrationTarget.dispatchEvent(new CustomEvent("form-element-register",{detail:{element:e.detail.element},bubbles:!0}))}},Qh=te(Xh);let eu=class extends Qh(G){static get properties(){return{tabIndex:{type:Number,reflect:!0,attribute:"tabindex"}}}constructor(){super(),this.tabIndex=0}connectedCallback(){super.connectedCallback(),this.setAttribute("role","listbox")}createRenderRoot(){return this}};function Mr(s,e){Array.from(s.childNodes).forEach(t=>{t.hasAttribute&&t.hasAttribute("slot")||e.appendChild(t)})}const tu=s=>class extends gt(pi(Sa(Qe(Xo(s))))){static get properties(){return{orientation:String,selectionFollowsFocus:{type:Boolean,attribute:"selection-follows-focus"},rotateKeyboardNavigation:{type:Boolean,attribute:"rotate-keyboard-navigation"},hasNoDefaultSelected:{type:Boolean,reflect:!0,attribute:"has-no-default-selected"},_noTypeAhead:{type:Boolean}}}static get styles(){return[...super.styles||[],R`
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
        `]}_inputGroupInputTemplate(){return k`
        <div class="input-group__input">
          <slot name="input"></slot>
          <slot id="options-outlet"></slot>
        </div>
      `}static get scopedElements(){return{...super.scopedElements,"lion-options":eu}}get slots(){return{...super.slots,input:()=>{const e=this.createScopedElement("lion-options");return e.setAttribute("data-tag-name","lion-options"),e.registrationTarget=this,e}}}get _inputNode(){return this.querySelector('[slot="input"]')}get _listboxNode(){return this._inputNode}get _listboxActiveDescendantNode(){return this._listboxNode.querySelector(`#${this._listboxActiveDescendant}`)}get _listboxSlot(){return this.shadowRoot.querySelector("slot[name=input]")}get _scrollTargetNode(){return this._listboxNode}get _activeDescendantOwnerNode(){return this._listboxNode}get activeIndex(){return this.formElements.findIndex(e=>e.active===!0)}set activeIndex(e){if(this.formElements[e]){const t=this.formElements[e];this.__setChildActive(t)}else this.__setChildActive(null)}get checkedIndex(){const e=this.formElements;return this.multipleChoice?e.filter(t=>t.checked).map(t=>e.indexOf(t)):e.indexOf(e.find(t=>t.checked))}set checkedIndex(e){this.setCheckedIndex(e)}constructor(){super(),this.hasNoDefaultSelected=!1,this.orientation="vertical",this.rotateKeyboardNavigation=!1,this.selectionFollowsFocus=!1,this._noTypeAhead=!1,this._typeAheadTimeout=1e3,this._listboxActiveDescendant=null,this.__hasInitialSelectedFormElement=!1,this._repropagationRole="choice-group",this._listboxReceivesNoFocus=!1,this._oldModelValue=void 0,this._listboxOnKeyDown=this._listboxOnKeyDown.bind(this),this._listboxOnClick=this._listboxOnClick.bind(this),this._listboxOnKeyUp=this._listboxOnKeyUp.bind(this),this._onChildActiveChanged=this._onChildActiveChanged.bind(this),this.__proxyChildModelValueChanged=this.__proxyChildModelValueChanged.bind(this),this.__preventScrollingWithArrowKeys=this.__preventScrollingWithArrowKeys.bind(this),this.__typedChars=[]}connectedCallback(){this._listboxNode&&(this._listboxNode.registrationTarget=this),super.connectedCallback(),this._setupListboxNode(),this.__setupEventListeners(),this.registrationComplete.then(()=>{this.__initInteractionStates()})}firstUpdated(e){super.firstUpdated(e),this.__moveOptionsToListboxNode(),this.registrationComplete.then(()=>{this._initialModelValue=this.modelValue}),new MutationObserver(()=>{this._onListboxContentChanged()}).observe(this._listboxNode,{childList:!0})}updated(e){super.updated(e),e.has("disabled")&&(this.disabled?this.__requestOptionsToBeDisabled():this.__retractRequestOptionsToBeDisabled())}disconnectedCallback(){super.disconnectedCallback(),this._teardownListboxNode(),this.__teardownEventListeners()}setCheckedIndex(e){if(this.multipleChoice&&Array.isArray(e)){this._uncheckChildren(this.formElements.filter(t=>t===e)),e.forEach(t=>{this.formElements[t]&&(this.formElements[t].checked=!this.formElements[t].checked)});return}typeof e=="number"&&(e===-1&&this._uncheckChildren(),this.formElements[e]&&(this.formElements[e].disabled?this._uncheckChildren():this.multipleChoice?this.formElements[e].checked=!this.formElements[e].checked:this.formElements[e].checked=!0))}addFormElement(e,t){super.addFormElement(e,t),e.id=e.id||`${this.localName}-option-${Uo()}`,this.disabled&&e.makeRequestToBeDisabled(),this.__setAttributeForAllFormElements("aria-setsize",this.formElements.length),this.formElements.forEach((i,o)=>{i.setAttribute("aria-posinset",o+1)}),this.__proxyChildModelValueChanged({target:e}),this.resetInteractionState()}resetInteractionState(){super.resetInteractionState(),this.submitted=!1}reset(){this.modelValue=this._initialModelValue,this.activeIndex=-1,this.resetInteractionState()}clear(){super.clear(),this.setCheckedIndex(-1),this.resetInteractionState()}_handleTypeAhead(e,{setAsChecked:t}){const{key:i,code:o}=e;if(o.startsWith("Key")||o.startsWith("Digit")||o.startsWith("Numpad")){e.preventDefault(),this.__typedChars.push(i);const r=this.__typedChars.join(""),n=this.formElements.findIndex(a=>a.modelValue.value.toLowerCase().startsWith(r));n>=0&&(t&&this.setCheckedIndex(n),this.activeIndex=n),this.__pendingTypeAheadTimeout&&window.clearTimeout(this.__pendingTypeAheadTimeout),this.__pendingTypeAheadTimeout=setTimeout(()=>{this.__typedChars=[]},this._typeAheadTimeout)}}_getCheckedElements(){return this.formElements.filter(e=>e.checked)}_setupListboxNode(){this._listboxNode?this.__setupListboxNodeInteractions():this._listboxSlot&&this._listboxSlot.addEventListener("slotchange",()=>{this.__setupListboxNodeInteractions()})}_onListboxContentChanged(){}_teardownListboxNode(){this._listboxNode&&(this._listboxNode.removeEventListener("keydown",this._listboxOnKeyDown),this._listboxNode.removeEventListener("click",this._listboxOnClick),this._listboxNode.removeEventListener("keyup",this._listboxOnKeyUp))}_getNextEnabledOption(e,t=1){return this.__getEnabledOption(e,t)}_getPreviousEnabledOption(e,t=-1){return this.__getEnabledOption(e,t)}_onChildActiveChanged({target:e}){e.active===!0&&this.__setChildActive(e)}_listboxOnKeyDown(e){if(this.disabled)return;this._isHandlingUserInput=!0,setTimeout(()=>{this._isHandlingUserInput=!1});const{key:t}=e;switch(t){case" ":case"Enter":{if(t===" "&&this._listboxReceivesNoFocus||(t===" "&&e.preventDefault(),!this.formElements[this.activeIndex])||this.formElements[this.activeIndex].disabled)return;this.formElements[this.activeIndex].href&&this.formElements[this.activeIndex].click(),this.setCheckedIndex(this.activeIndex);break}case"ArrowUp":e.preventDefault(),this.orientation==="vertical"&&(this.activeIndex=this._getPreviousEnabledOption(this.activeIndex));break;case"ArrowLeft":if(this._listboxReceivesNoFocus)return;e.preventDefault(),this.orientation==="horizontal"&&(this.activeIndex=this._getPreviousEnabledOption(this.activeIndex));break;case"ArrowDown":e.preventDefault(),this.orientation==="vertical"&&(this.activeIndex=this._getNextEnabledOption(this.activeIndex));break;case"ArrowRight":if(this._listboxReceivesNoFocus)return;e.preventDefault(),this.orientation==="horizontal"&&(this.activeIndex=this._getNextEnabledOption(this.activeIndex));break;case"Home":if(this._listboxReceivesNoFocus)return;e.preventDefault(),this.activeIndex=this._getNextEnabledOption(0,0);break;case"End":if(this._listboxReceivesNoFocus)return;e.preventDefault(),this.activeIndex=this._getPreviousEnabledOption(this.formElements.length-1,0);break;default:this._noTypeAhead||this._handleTypeAhead(e,{setAsChecked:this.selectionFollowsFocus&&!this.multipleChoice})}["ArrowUp","ArrowDown","ArrowLeft","ArrowRight","Home","End"].includes(t)&&this.selectionFollowsFocus&&!this.multipleChoice&&this.setCheckedIndex(this.activeIndex)}_listboxOnClick(e){}_listboxOnKeyUp(e){if(this.disabled)return;this._isHandlingUserInput=!0,setTimeout(()=>{this._isHandlingUserInput=!1});const{key:t}=e;switch(t){case"ArrowUp":case"ArrowDown":case"Home":case"End":case"Enter":e.preventDefault()}}_onLabelClick(){this._listboxNode.focus()}_scrollIntoView(e,t){e.scrollIntoView({behavior:"smooth",block:"nearest"})}__setupEventListeners(){this._listboxNode.addEventListener("active-changed",this._onChildActiveChanged),this._listboxNode.addEventListener("model-value-changed",this.__proxyChildModelValueChanged)}__teardownEventListeners(){this._listboxNode.removeEventListener("active-changed",this._onChildActiveChanged),this._listboxNode.removeEventListener("model-value-changed",this.__proxyChildModelValueChanged)}__setChildActive(e){if(this.formElements.forEach(t=>{t.active=e===t}),!e){this._activeDescendantOwnerNode.removeAttribute("aria-activedescendant");return}this._activeDescendantOwnerNode.setAttribute("aria-activedescendant",e.id),this._scrollIntoView(e,this._scrollTargetNode)}_uncheckChildren(e=[]){const t=Array.isArray(e)?e:[e];this.formElements.forEach(i=>{t.includes(i)||(i.checked=!1)})}__onChildCheckedChanged(e){const{target:t}=e;e.stopPropagation&&e.stopPropagation(),t.checked&&!this.multipleChoice&&this._uncheckChildren(t)}__setAttributeForAllFormElements(e,t){this.formElements.forEach(i=>{i.setAttribute(e,t)})}__proxyChildModelValueChanged(e){e.stopPropagation&&e.stopPropagation(),this.__onChildCheckedChanged(e),this.requestUpdate("modelValue",this._oldModelValue),e.detail&&e.detail.formPath&&this.dispatchEvent(new CustomEvent("model-value-changed",{detail:{formPath:e.detail.formPath,isTriggeredByUser:e.detail.isTriggeredByUser||this._isHandlingUserInput,element:e.target}})),this._oldModelValue=this.modelValue}__getEnabledOption(e,t){const i=o=>t===1?o<this.formElements.length:o>=0;for(let o=e+t;i(o);o+=t)if(this.formElements[o]&&!this.formElements[o].hasAttribute("aria-hidden"))return o;if(this.rotateKeyboardNavigation){const o=t===-1?this.formElements.length-1:0;for(let r=o;i(r);r+=t)if(this.formElements[r]&&!this.formElements[r].hasAttribute("aria-hidden"))return r}return e}__moveOptionsToListboxNode(){const e=this.shadowRoot.getElementById("options-outlet");e&&(Mr(this,this._listboxNode),e.addEventListener("slotchange",()=>{Mr(this,this._listboxNode)}))}__preventScrollingWithArrowKeys(e){if(this.disabled)return;const{key:t}=e;switch(t){case"ArrowUp":case"ArrowDown":case"Home":case"End":e.preventDefault()}}__setupListboxNodeInteractions(){this._listboxNode.setAttribute("role","listbox"),this._listboxNode.setAttribute("aria-orientation",this.orientation),this._listboxNode.setAttribute("aria-multiselectable",`${this.multipleChoice}`),this._listboxNode.setAttribute("tabindex","0"),this._listboxNode.addEventListener("click",this._listboxOnClick),this._listboxNode.addEventListener("keyup",this._listboxOnKeyUp),this._listboxNode.addEventListener("keydown",this._listboxOnKeyDown),this._scrollTargetNode.addEventListener("keydown",this.__preventScrollingWithArrowKeys)}__requestOptionsToBeDisabled(){this.formElements.forEach(e=>{e.makeRequestToBeDisabled&&e.makeRequestToBeDisabled()})}__retractRequestOptionsToBeDisabled(){this.formElements.forEach(e=>{e.retractRequestToBeDisabled&&e.retractRequestToBeDisabled()})}__initInteractionStates(){this.initInteractionState()}},su=te(tu);let iu=class extends su(jo(Wo(_s(G)))){get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,focused:this.focused}}get _focusableNode(){return this._inputNode}};const ou=R`
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
`;let Na=class ot{static __createGlobalStyleNode(){const e=document.createElement("style");return e.setAttribute("data-overlays",""),e.textContent=ou.cssText,document.head.appendChild(e),e}get list(){return this.__list}get shownList(){return this.__shownList}constructor(){this.__list=[],this.__shownList=[],this.__siblingsInert=!1,this.__blockingMap=new WeakMap,ot.__globalStyleNode||(ot.__globalStyleNode=ot.__createGlobalStyleNode())}add(e){if(this.list.find(t=>e===t))throw new Error("controller instance is already added");return this.list.push(e),e}remove(e){if(!this.list.find(t=>e===t))throw new Error("could not find controller to remove");this.__list=this.list.filter(t=>t!==e),this.__shownList=this.shownList.filter(t=>t!==e)}show(e){this.list.find(t=>e===t)&&this.hide(e),this.__shownList.unshift(e),Array.from(this.__shownList).reverse().forEach((t,i)=>{t.elevation=i+1})}hide(e){if(!this.list.find(t=>e===t))throw new Error("could not find controller to hide");this.__shownList=this.shownList.filter(t=>t!==e)}teardown(){this.list.forEach(e=>{e.teardown()}),this.__list=[],this.__shownList=[],this.__siblingsInert=!1,ot.__globalStyleNode&&(document.head.removeChild(ot.__globalStyleNode),ot.__globalStyleNode=void 0)}get siblingsInert(){return this.__siblingsInert}disableTrapsKeyboardFocusForAll(){this.shownList.forEach(e=>{e.trapsKeyboardFocus===!0&&e.disableTrapsKeyboardFocus&&e.disableTrapsKeyboardFocus({findNewTrap:!1})})}informTrapsKeyboardFocusGotEnabled(e){this.siblingsInert===!1&&e==="global"&&(this.__siblingsInert=!0)}informTrapsKeyboardFocusGotDisabled({disabledCtrl:e,findNewTrap:t=!0}={}){const i=this.shownList.find(o=>o!==e&&o.trapsKeyboardFocus===!0);i?t&&i.enableTrapsKeyboardFocus():this.siblingsInert===!0&&(this.__siblingsInert=!1)}requestToPreventScroll(){const{isIOS:e,isMacSafari:t}=Js;document.body.classList.add("overlays-scroll-lock"),(e||t)&&document.body.classList.add("overlays-scroll-lock-ios-fix"),e&&document.documentElement.classList.add("overlays-scroll-lock-ios-fix")}requestToEnableScroll(){if(this.shownList.some(i=>i.preventsScroll===!0))return;const{isIOS:e,isMacSafari:t}=Js;document.body.classList.remove("overlays-scroll-lock"),(e||t)&&document.body.classList.remove("overlays-scroll-lock-ios-fix"),e&&document.documentElement.classList.remove("overlays-scroll-lock-ios-fix")}requestToShowOnly(e){const t=this.shownList.filter(i=>i!==e);t.forEach(i=>i.hide()),this.__blockingMap.set(e,t)}retractRequestToShowOnly(e){this.__blockingMap.has(e)&&this.__blockingMap.get(e).forEach(t=>t.show())}};Na.__globalStyleNode=void 0;const ru=Is.get("@lion/ui::overlays::0.x")||new Na;function ao(){let s=document.activeElement||document.body;for(;s&&s.shadowRoot&&s.shadowRoot.activeElement;)s=s.shadowRoot.activeElement;return s}const Ir=({visibility:s,display:e})=>s!=="hidden"&&e!=="none",nu=({display:s})=>s==="contents";function au(s){if(!s||!s.isConnected||!Ir(s.style))return!1;const e=window.getComputedStyle(s);return Ir(e)?nu(e)?!0:!!(s.offsetWidth||s.offsetHeight||s.getClientRects().length):!1}function lu(s,e){const t=Math.max(s.tabIndex,0),i=Math.max(e.tabIndex,0);return t===0||i===0?i>t:t>i}function cu(s,e){const t=[];for(;s.length>0&&e.length>0;)lu(s[0],e[0])?t.push(e.shift()):t.push(s.shift());return[...t,...s,...e]}function lo(s){const e=s.length;if(e<2)return s;const t=Math.ceil(e/2),i=lo(s.slice(0,t)),o=lo(s.slice(t));return cu(i,o)}const Ii="matches"in Element.prototype?"matches":"msMatchesSelector";function du(s){return s[Ii]("input, select, textarea, button, object")?s[Ii](":not([disabled])"):s[Ii]("a[href], area[href], iframe, [tabindex], [contentEditable]")}function hu(s){return du(s)?Number(s.getAttribute("tabindex")||0):-1}function uu(s){if(s.localName==="slot")return s.assignedNodes({flatten:!0});const{children:e}=s.shadowRoot||s;return e||[]}function pu(s){return s.nodeType!==Node.ELEMENT_NODE?!1:s.localName==="slot"?!0:au(s)}function Ta(s,e){if(!pu(s))return!1;const t=s,i=hu(t);let o=i>0;i>=0&&e.push(t);const r=uu(t);for(let n=0;n<r.length;n+=1)o=Ta(r[n],e)||o;return o}function Oa(s){const e=[];return Ta(s,e)?lo(e):e}function Tt(s,e,t={}){function i(p){return"getAttribute"in p}function o(p){if(!i(p))return null;const m=p.getAttribute("slot");let g=null;if(m){const w=t[m];w&&(g=w.filter(E=>E?.element===p)[0]||null)}return g}const r=o(s);if(r)return r.deepContains;function n(p){if(!i(s))return;const m=s.getAttribute("slot");m&&(t[m]=t[m]||[],t[m].push({element:s,deepContains:p}))}let a=s.contains(e);if(a)return n(!0),!0;function l(p){return p.tagName==="SLOT"}function c(p){return l(p)?p.assignedElements():[]}function d(p){return p.nodeType===Node.DOCUMENT_FRAGMENT_NODE}function u(p){let m=!1;for(let g=0;g<p.length;g+=1){const w=p[g];if(w&&(i(w)||d(w))&&Tt(w,e,t)){m=!0;break}}return m}function b(p){for(let m=0;m<p.children.length;m+=1){const g=p.children[m],w=o(g);if(w){a=w.deepContains||a;break}const E=c(g),x=[g.shadowRoot,...E];if(u(x)){a=!0;break}g.children.length>0&&b(g)}}return s instanceof HTMLElement&&s.shadowRoot&&(a=Tt(s.shadowRoot,e,t),a)?(n(!0),!0):(b(s),n(a),a)}const fu={tab:9};function mu(s,e){const t=Oa(s);let i;t.length>=2?i=[t[0],t[t.length-1]]:t.length===1?i=[t[0],t[0]]:i=[s,s],e.shiftKey&&i.reverse();const[o,r]=i,n=ao();n===s||t.includes(n)&&r!==n||(e.preventDefault(),o.focus())}function bu(s){const e=Oa(s),t=e.find(b=>b.hasAttribute("autofocus"))||s;let i,o;t===s&&(s.tabIndex=-1,s.style.setProperty("outline","none")),t.focus();function r(b){b.keyCode===fu.tab&&mu(s,b)}function n(){i=document.createElement("div"),i.style.display="none",i.setAttribute("data-is-tab-detection-element",""),s.insertBefore(i,s.children[0]),o=new MutationObserver(b=>{for(const p of b)if(p.type==="childList"){const m=!Array.from(s.children).find(w=>w.hasAttribute("data-is-tab-detection-element")),g=Array.from(p.addedNodes).find(w=>w instanceof HTMLElement&&w.hasAttribute("data-is-tab-detection-element"));m&&!g&&(o.disconnect(),n())}}),o.observe(s,{childList:!0})}function a(){return i.compareDocumentPosition(document.activeElement)===Node.DOCUMENT_POSITION_PRECEDING}function l({resetToRoot:b=!1}={}){if(Tt(s,ao()))return;let p;b?p=s:p=e[a()?0:e.length-1],p&&p.focus()}function c(){window.removeEventListener("focusin",c),l()}function d(){setTimeout(()=>{Tt(s,ao())||l({resetToRoot:!0})}),window.addEventListener("focusin",c)}function u(){window.removeEventListener("keydown",r),window.removeEventListener("focusin",c),window.removeEventListener("focusout",d),o.disconnect(),Array.from(s.children).includes(i)&&s.removeChild(i),s.style.removeProperty("outline")}return window.addEventListener("keydown",r),window.addEventListener("focusout",d),n(),{disconnect:u}}const Dr=R`
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
`,Ot={supportsAdoptingStyleSheets:window.ShadowRoot&&(window.ShadyCSS===void 0||window.ShadyCSS.nativeShadow)&&"adoptedStyleSheets"in Document.prototype&&"replace"in CSSStyleSheet.prototype,adoptStyle:void 0,adoptStyles:void 0},Di=new WeakMap;function gu(s){return Array.from(s.cssRules).map(e=>e.cssText).join("")}function vu(s,e,{teardown:t=!1}={}){const i=s===document?document.body:s,o=e.cssText||gu(e);if(t){const r=Array.from(i.querySelectorAll("style"));for(const n of r)if(n.textContent===o){n.remove();break}}else{const r=document.createElement("style"),n=window.litNonce;n!==void 0&&r.setAttribute("nonce",n),r.textContent=o,i.appendChild(r)}}function _u(s,e,{teardown:t=!1}={}){let i=!1;s&&!Di.has(s)&&Di.set(s,[]);const o=Di.get(s)??[],r=o.find(n=>e===n);return r&&t?o.splice(o.indexOf(e),1):!r&&!t?o.push(e):(r&&!t||!r&&t)&&(i=!0),{haltFurtherExecution:i}}function yu(s,e,{teardown:t=!1}={}){const{haltFurtherExecution:i}=_u(s,e,{teardown:t});if(i)return;if(!Ot.supportsAdoptingStyleSheets||Js.isIOS){vu(s,e,{teardown:t});return}const o=e instanceof CSSStyleSheet?e:e.styleSheet;if(!o)throw new Error("Please provide a CSSResultOrNative style");t?s.adoptedStyleSheets.includes(o)&&s.adoptedStyleSheets.splice(s.adoptedStyleSheets.indexOf(o),1):s.adoptedStyleSheets=[...s.adoptedStyleSheets,o]}function wu(s,e,{teardown:t=!1}={}){for(const i of e)Ot.adoptStyle(s,i,{teardown:t})}Ot.adoptStyle=yu;Ot.adoptStyles=wu;function Eu({wrappingDialogNodeL1:s,contentWrapperNodeL2:e,contentNodeL3:t}){if(!(e.isConnected||t.isConnected))throw new Error('[OverlayController] Could not find a render target, since the provided contentNode is not connected to the DOM. Make sure that it is connected, e.g. by doing "document.body.appendChild(contentNode)", before passing it on.');let i;const o=document.createComment("tempMarker");e.isConnected?(i=e.parentElement||e.getRootNode(),i.insertBefore(o,e),s.appendChild(e)):t.assignedSlot?(i=t.assignedSlot.parentElement||t.assignedSlot.getRootNode(),i.insertBefore(o,t.assignedSlot),s.appendChild(e),e.appendChild(t.assignedSlot)):(i=t.parentElement||t.getRootNode(),i.insertBefore(o,t),s.appendChild(e),e.appendChild(t)),i.insertBefore(s,o),i?.removeChild(o)}async function ku(){return I(()=>import("./popper-DBeoc0HL.js"),[])}const Vr=new WeakMap;let La=class Gt extends EventTarget{constructor(e={},t=ru){super(),this.manager=t,this.__sharedConfig=e,this.__activeElementRightBeforeHide=null,this.config={},this._defaultConfig={placementMode:void 0,contentNode:e.contentNode,contentWrapperNode:e.contentWrapperNode,invokerNode:e.invokerNode,backdropNode:e.backdropNode,referenceNode:void 0,elementToFocusAfterHide:e.invokerNode,inheritsReferenceWidth:"none",hasBackdrop:!1,isBlocking:!1,preventsScroll:!1,trapsKeyboardFocus:!1,hidesOnEsc:!1,hidesOnOutsideEsc:!1,hidesOnOutsideClick:!1,isTooltip:!1,isAlertDialog:!1,invokerRelation:"description",visibilityTriggerFunction:void 0,handlesAccessibility:!1,popperConfig:{placement:"top",strategy:"fixed",modifiers:[{name:"preventOverflow",enabled:!0,options:{boundariesElement:"viewport",padding:8}},{name:"flip",options:{boundariesElement:"viewport",padding:16}},{name:"offset",enabled:!0,options:{offset:[0,8]}},{name:"arrow",enabled:!1}]},viewportConfig:{placement:"center"},zIndex:9999},this._contentId=`overlay-content--${Math.random().toString(36).slice(2,10)}`,this.__originalAttrs=new Map,this.updateConfig(e),this.__hasActiveTrapsKeyboardFocus=!1,this.__hasActiveBackdrop=!0,this.__escKeyHandler=this.__escKeyHandler.bind(this),this.__cancelHandler=this.__cancelHandler.bind(this)}get invoker(){return this.invokerNode}get content(){return this.__wrappingDialogNode}get placementMode(){return this.config?.placementMode}get invokerNode(){return this.config?.invokerNode}get referenceNode(){return this.config?.referenceNode}get contentNode(){return this.config?.contentNode}get contentWrapperNode(){return this.__contentWrapperNode||this.config?.contentWrapperNode}get backdropNode(){return this.__backdropNode||this.config?.backdropNode}get elementToFocusAfterHide(){return this.__elementToFocusAfterHide||this.config?.elementToFocusAfterHide}get hasBackdrop(){return!!this.backdropNode||this.config?.hasBackdrop}get isBlocking(){return this.config?.isBlocking}get preventsScroll(){return this.config?.preventsScroll}get trapsKeyboardFocus(){return this.config?.trapsKeyboardFocus}get hidesOnEsc(){return this.config?.hidesOnEsc}get hidesOnOutsideClick(){return this.config?.hidesOnOutsideClick}get hidesOnOutsideEsc(){return this.config?.hidesOnOutsideEsc}get inheritsReferenceWidth(){return this.config?.inheritsReferenceWidth}get handlesAccessibility(){return this.config?.handlesAccessibility}get isTooltip(){return this.config?.isTooltip}get isAlertDialog(){return this.config?.isAlertDialog}get invokerRelation(){return this.config?.invokerRelation}get popperConfig(){return this.config?.popperConfig}get viewportConfig(){return this.config?.viewportConfig}get visibilityTriggerFunction(){return this.config?.visibilityTriggerFunction}get _referenceNode(){return this.referenceNode||this.invokerNode}set elevation(e){this.__wrappingDialogNode.style.zIndex=`${this.config.zIndex+e}`}get elevation(){return Number(this.contentWrapperNode?.style.zIndex)}updateConfig(e){this.teardown(),this.__prevConfig=this.config,this.config={...this._defaultConfig,...this.__sharedConfig,...e,popperConfig:{...this._defaultConfig.popperConfig||{},...this.__sharedConfig.popperConfig||{},...e.popperConfig||{},modifiers:[...this._defaultConfig.popperConfig?.modifiers||[],...this.__sharedConfig.popperConfig?.modifiers||[],...e.popperConfig?.modifiers||[]]}},this.__validateConfiguration(this.config),this._init(),this.__elementToFocusAfterHide=void 0,this.#e()||this.manager.add(this)}#e(){return!!this.manager.list.find(e=>this===e)}__validateConfiguration(e){if(!e.placementMode)throw new Error('[OverlayController] You need to provide a .placementMode ("global"|"local")');if(!["global","local"].includes(e.placementMode))throw new Error(`[OverlayController] "${e.placementMode}" is not a valid .placementMode, use ("global"|"local")`);if(!e.contentNode)throw new Error("[OverlayController] You need to provide a .contentNode");if(e.isTooltip&&!e.handlesAccessibility)throw new Error("[OverlayController] .isTooltip only takes effect when .handlesAccessibility is enabled")}_init(){this.__contentHasBeenInitialized||(this.__initContentDomStructure(),this.__contentHasBeenInitialized=!0),this.contentWrapperNode.removeAttribute("style"),this.contentWrapperNode.removeAttribute("class"),this.placementMode==="local"&&(Gt.popperModule||(Gt.popperModule=ku())),this.__handleOverlayStyles({phase:"init"}),this._handleFeatures({phase:"init"})}__handleOverlayStyles({phase:e}){const t=this.contentWrapperNode?.getRootNode();e==="init"?Ot.adoptStyle(t,Dr):e==="teardown"&&Ot.adoptStyle(t,Dr,{teardown:!0})}__initContentDomStructure(){const e=document.createElement(this.config?._noDialogEl?"div":"dialog");e.setAttribute("role","none"),e.setAttribute("data-overlay-outer-wrapper",""),e.style.cssText=`display:none; z-index: ${this.config.zIndex}; padding: 0;`,this.__wrappingDialogNode=e,this.config?.contentWrapperNode||(this.__contentWrapperNode=document.createElement("div")),this.contentWrapperNode.setAttribute("data-id","content-wrapper"),Eu({wrappingDialogNodeL1:e,contentWrapperNodeL2:this.contentWrapperNode,contentNodeL3:this.contentNode}),e.open=!0,this.isTooltip&&e.setAttribute("tabindex","-1"),this.__wrappingDialogNode.style.display="none",this.contentWrapperNode.style.zIndex="1",getComputedStyle(this.contentNode).position==="absolute"&&(this.contentNode.style.position="static"),HTMLDialogElement&&"closedBy"in HTMLDialogElement.prototype?e.closedBy="none":(e.addEventListener("keydown",t=>{t.key==="Escape"&&t.preventDefault()}),e.addEventListener("keyup",t=>{t.key==="Escape"&&t.preventDefault()}),e.addEventListener("cancel",t=>{t.stopPropagation()}),e.addEventListener("close",t=>{t.stopPropagation()}))}_handleZIndex({phase:e}){if(this.placementMode==="local"&&e==="setup"){const t=Number(getComputedStyle(this.contentNode).zIndex);(t<1||Number.isNaN(t))&&(this.contentNode.style.zIndex="1")}}__setupTeardownAccessibility({phase:e}){if(e==="init"){this.__storeOriginalAttrs(this.contentNode,["role","id"]);const t=this.trapsKeyboardFocus;if(this.invokerNode){const i=["aria-labelledby","aria-describedby"];t||i.push("aria-expanded"),this.__storeOriginalAttrs(this.invokerNode,i)}this.contentNode.id||this.contentNode.setAttribute("id",this._contentId),this.isTooltip?(this.invokerNode&&this.invokerNode.setAttribute(this.invokerRelation==="label"?"aria-labelledby":"aria-describedby",this._contentId),this.contentNode.setAttribute("role","tooltip")):(this.invokerNode&&!t&&this.invokerNode.setAttribute("aria-expanded",`${this.isShown}`),this.isAlertDialog?this.contentNode.setAttribute("role","alertdialog"):this.contentNode.getAttribute("role")||this.contentNode.setAttribute("role","dialog"))}else e==="teardown"&&this.__restoreOriginalAttrs()}__storeOriginalAttrs(e,t){const i={};t.forEach(o=>{i[o]=e.getAttribute(o)}),this.__originalAttrs.set(e,i)}__restoreOriginalAttrs(){for(const[e,t]of this.__originalAttrs)Object.entries(t).forEach(([i,o])=>{o!==null?e.setAttribute(i,o):e.removeAttribute(i)});this.__originalAttrs.clear()}get isShown(){return this.__wrappingDialogNode?.style.display!=="none"}async show(e=this.elementToFocusAfterHide){if(this._showComplete&&await this._showComplete,this._showComplete=new Promise(i=>{this._showResolve=i}),this.manager&&this.manager.show(this),this.isShown){this._showResolve();return}const t=new CustomEvent("before-show",{cancelable:!0});this.dispatchEvent(t),t.defaultPrevented||("HTMLDialogElement"in window&&this.__wrappingDialogNode instanceof HTMLDialogElement&&(this.__wrappingDialogNode.open=!0),this.__wrappingDialogNode.style.display="",this._keepBodySize({phase:"before-show"}),await this._handleFeatures({phase:"show"}),this._keepBodySize({phase:"show"}),await this._handlePosition({phase:"show"}),this.__elementToFocusAfterHide=e,this.dispatchEvent(new Event("show")),await this._transitionShow({backdropNode:this.backdropNode,contentNode:this.contentNode})),this._showResolve()}async _handlePosition({phase:e}){if(this.placementMode==="global"){const t=`overlays__overlay-container--${this.viewportConfig.placement}`;e==="show"?(this.contentWrapperNode.classList.add("overlays__overlay-container"),this.contentWrapperNode.classList.add(t),this.contentNode.classList.add("overlays__overlay")):e==="hide"&&(this.contentWrapperNode.classList.remove("overlays__overlay-container"),this.contentWrapperNode.classList.remove(t),this.contentNode.classList.remove("overlays__overlay"))}else this.placementMode==="local"&&e==="show"&&(await this.__createPopperInstance(),this._popper.forceUpdate())}_keepBodySize({phase:e}){if(this.preventsScroll)switch(e){case"before-show":this.__bodyClientWidth=document.body.clientWidth,this.__bodyClientHeight=document.body.clientHeight,this.__bodyMarginRightInline=document.body.style.marginRight,this.__bodyMarginBottomInline=document.body.style.marginBottom;break;case"show":{if(window.getComputedStyle){const n=window.getComputedStyle(document.body);this.__bodyMarginRight=parseInt(n.getPropertyValue("margin-right"),10),this.__bodyMarginBottom=parseInt(n.getPropertyValue("margin-bottom"),10)}else this.__bodyMarginRight=0,this.__bodyMarginBottom=0;const t=document.body.clientWidth-this.__bodyClientWidth,i=document.body.clientHeight-this.__bodyClientHeight,o=this.__bodyMarginRight+t,r=this.__bodyMarginBottom+i;window.CSS?.number&&document.body.attributeStyleMap?.set?(document.body.attributeStyleMap.set("margin-right",CSS.px(o)),document.body.attributeStyleMap.set("margin-bottom",CSS.px(r))):(document.body.style.marginRight=`${o}px`,document.body.style.marginBottom=`${r}px`);break}case"hide":document.body.style.marginRight=this.__bodyMarginRightInline||"",document.body.style.marginBottom=this.__bodyMarginBottomInline||"";break}}async hide(){if(this._hideComplete=new Promise(t=>{this._hideResolve=t}),this.__activeElementRightBeforeHide=this.contentNode.getRootNode().activeElement,this.manager&&this.manager.hide(this),!this.isShown){this._hideResolve();return}const e=new CustomEvent("before-hide",{cancelable:!0});this.dispatchEvent(e),e.defaultPrevented||(await this._transitionHide({backdropNode:this.backdropNode,contentNode:this.contentNode}),"HTMLDialogElement"in window&&this.__wrappingDialogNode instanceof HTMLDialogElement&&this.__wrappingDialogNode.close(),this.__wrappingDialogNode.style.display="none",this._handleFeatures({phase:"hide"}),this._keepBodySize({phase:"hide"}),this.dispatchEvent(new Event("hide")),this._restoreFocus()),this._hideResolve()}async transitionHide(e){}async _transitionHide({backdropNode:e,contentNode:t}){await this.transitionHide({backdropNode:e,contentNode:t}),this._handlePosition({phase:"hide"}),e&&e.classList.remove("overlays__backdrop--animation-in")}async transitionShow(e){}async _transitionShow(e){await this.transitionShow({backdropNode:this.backdropNode,contentNode:this.contentNode}),e.backdropNode&&e.backdropNode.classList.add("overlays__backdrop--animation-in")}_restoreFocus(){this.__activeElementRightBeforeHide instanceof HTMLElement&&this.contentNode.contains(this.__activeElementRightBeforeHide)&&(this.elementToFocusAfterHide instanceof HTMLElement?(this.elementToFocusAfterHide.focus(),this.elementToFocusAfterHide.scrollIntoView({block:"nearest"})):this.__activeElementRightBeforeHide.blur())}async toggle(){return this.isShown?this.hide():this.show()}_handleFeatures({phase:e}){this._handleZIndex({phase:e}),this.preventsScroll&&this._handlePreventsScroll({phase:e}),this.isBlocking&&this._handleBlocking({phase:e}),this.hasBackdrop&&this._handleBackdrop({phase:e}),this.trapsKeyboardFocus&&this._handleTrapsKeyboardFocus({phase:e}),this.hidesOnEsc&&this._handleHidesOnEsc({phase:e}),this.hidesOnOutsideEsc&&this._handleHidesOnOutsideEsc({phase:e}),this.hidesOnOutsideClick&&this._handleHidesOnOutsideClick({phase:e}),this.handlesAccessibility&&this._handleAccessibility({phase:e}),this.inheritsReferenceWidth&&this._handleInheritsReferenceWidth(),this.visibilityTriggerFunction&&this._handleVisibilityTriggers({phase:e})}_handleVisibilityTriggers({phase:e}){typeof this.visibilityTriggerFunction=="function"&&(e==="init"&&(this.__visibilityTriggerHandler=this.visibilityTriggerFunction({phase:e,controller:this})),this.__visibilityTriggerHandler[e]&&this.__visibilityTriggerHandler[e]())}_handlePreventsScroll({phase:e}){switch(e){case"show":this.manager.requestToPreventScroll();break;case"hide":this.manager.requestToEnableScroll();break}}_handleBlocking({phase:e}){switch(e){case"show":this.manager.requestToShowOnly(this);break;case"hide":this.manager.retractRequestToShowOnly(this);break}}get hasActiveBackdrop(){return this.__hasActiveBackdrop}_handleBackdrop({phase:e}){switch(e){case"init":{this.__backdropInitialized||(this.config?.backdropNode||(this.__backdropNode=document.createElement("div"),this.__backdropNode.classList.add("overlays__backdrop")),this.__wrappingDialogNode.prepend(this.backdropNode),this.__backdropInitialized=!0);break}case"show":this.config.hasBackdrop&&this.backdropNode.classList.add("overlays__backdrop--visible"),this.__hasActiveBackdrop=!0;break;case"hide":case"teardown":this.backdropNode.classList.remove("overlays__backdrop--visible"),this.__hasActiveBackdrop=!1;break}}get hasActiveTrapsKeyboardFocus(){return this.__hasActiveTrapsKeyboardFocus}_handleTrapsKeyboardFocus({phase:e}){e==="show"?("showModal"in this.__wrappingDialogNode&&(this.__wrappingDialogNode.close(),this.__wrappingDialogNode.showModal()),this.enableTrapsKeyboardFocus()):(e==="hide"||e==="teardown")&&this.disableTrapsKeyboardFocus()}enableTrapsKeyboardFocus(){this.__hasActiveTrapsKeyboardFocus||(this.manager&&this.manager.disableTrapsKeyboardFocusForAll(),this.contentNode.shadowRoot&&console.warn("[overlays]: For best accessibility (compatibility with Safari + VoiceOver), provide a contentNode that is not a host for a shadow root"),this._containFocusHandler=bu(this.contentNode),this.__hasActiveTrapsKeyboardFocus=!0,this.manager&&this.manager.informTrapsKeyboardFocusGotEnabled(this.placementMode))}disableTrapsKeyboardFocus({findNewTrap:e=!0}={}){this.__hasActiveTrapsKeyboardFocus&&(this._containFocusHandler&&(this._containFocusHandler.disconnect(),this._containFocusHandler=void 0),this.__hasActiveTrapsKeyboardFocus=!1,this.manager&&this.manager.informTrapsKeyboardFocusGotDisabled({disabledCtrl:this,findNewTrap:e}))}__cancelHandler(e){e.preventDefault()}__escKeyHandler(e){e.key!=="Escape"||Vr.has(e)||(e.composedPath().includes(this.contentNode)||Tt(this.contentNode,e.target))&&(this.hide(),Vr.set(e,this))}#t=e=>{e.key!=="Escape"||e.composedPath().includes(this.contentNode)||Tt(this.contentNode,e.target)||this.hide()};_handleHidesOnEsc({phase:e}){e==="show"?(this.contentNode.addEventListener("keyup",this.__escKeyHandler),this.invokerNode&&this.invokerNode.addEventListener("keyup",this.__escKeyHandler)):(e==="hide"||e==="teardown")&&(this.contentNode.removeEventListener("keyup",this.__escKeyHandler),this.invokerNode&&this.invokerNode.removeEventListener("keyup",this.__escKeyHandler))}_handleHidesOnOutsideEsc({phase:e}){e==="show"?document.addEventListener("keyup",this.#t):(e==="hide"||e==="teardown")&&document.removeEventListener("keyup",this.#t)}_handleInheritsReferenceWidth(){if(!this._referenceNode||this.placementMode==="global")return;const e=`${this._referenceNode.getBoundingClientRect().width}px`;switch(this.inheritsReferenceWidth){case"max":this.contentWrapperNode.style.maxWidth=e;break;case"full":this.contentWrapperNode.style.width=e;break;case"min":this.contentWrapperNode.style.minWidth=e,this.contentWrapperNode.style.width="auto";break}}_handleHidesOnOutsideClick({phase:e}){const t=e==="show"?"addEventListener":"removeEventListener";if(e==="show"){let i=!1,o=!1;this.__onInsideMouseDown=()=>{i=!0},this.__onInsideMouseUp=()=>{o=!0},this.__onDocumentMouseUp=()=>{setTimeout(()=>{!i&&!o&&this.hide(),i=!1,o=!1})},this.__onWindowBlur=()=>{setTimeout(()=>{this.hide()})}}this.contentWrapperNode[t]("mousedown",this.__onInsideMouseDown,!0),this.contentWrapperNode[t]("mouseup",this.__onInsideMouseUp,!0),this.invokerNode&&(this.invokerNode[t]("mousedown",this.__onInsideMouseDown,!0),this.invokerNode[t]("mouseup",this.__onInsideMouseUp,!0)),document.documentElement[t]("mouseup",this.__onDocumentMouseUp,!0),window[t]("blur",this.__onWindowBlur)}_handleAccessibility({phase:e}){(e==="init"||e==="teardown")&&this.__setupTeardownAccessibility({phase:e});const t=this.trapsKeyboardFocus;this.invokerNode&&!this.isTooltip&&!t&&this.invokerNode.setAttribute("aria-expanded",`${e==="show"}`)}teardown(){this.__handleOverlayStyles({phase:"teardown"}),this._handleFeatures({phase:"teardown"}),this.#e()&&this.manager.remove(this)}async __createPopperInstance(){if(this._popper&&(this._popper.destroy(),this._popper=void 0),Gt.popperModule!==void 0){const{createPopper:e}=await Gt.popperModule;this._popper=e(this._referenceNode,this.contentWrapperNode,{...this.config?.popperConfig})}}_hasDisabledInvoker(){return this.invokerNode?this.invokerNode.disabled||this.invokerNode.getAttribute("aria-disabled")==="true":!1}};La.popperModule=void 0;function Ra(s,e){if(typeof s!="object"||typeof e!="object"||s===null||e===null)return s===e;const t=Object.keys(s),i=Object.keys(e);if(t.length!==i.length)return!1;const o=(r=>Ra(s[r],e[r]));return t.every(o)}const xu=s=>class extends s{static get properties(){return{opened:{type:Boolean,reflect:!0}}}#e=!1;constructor(){super(),this.opened=!1,this.config={},this.toggle=this.toggle.bind(this),this.open=this.open.bind(this),this.close=this.close.bind(this)}get config(){return this.__config}set config(e){const t=!Ra(this.config,e);this._overlayCtrl&&t&&this._overlayCtrl.updateConfig(e),this.__config=e,this._overlayCtrl&&t&&this.__syncToOverlayController()}requestUpdate(e,t,i){super.requestUpdate(e,t,i),e==="opened"&&this.opened!==t&&this.dispatchEvent(new CustomEvent("opened-changed",{detail:{opened:this.opened}}))}_defineOverlay({contentNode:e,invokerNode:t,referenceNode:i,backdropNode:o,contentWrapperNode:r}){const n=this._defineOverlayConfig()||{};return new La({contentNode:e,invokerNode:t,referenceNode:i,backdropNode:o,contentWrapperNode:r,...n,...this.config,popperConfig:{...n.popperConfig||{},...this.config?.popperConfig||{},modifiers:[...n.popperConfig?.modifiers||[],...this.config?.popperConfig?.modifiers||[]]}})}_defineOverlayConfig(){return{placementMode:"local"}}updated(e){super.updated(e),e.has("opened")&&this._overlayCtrl&&!this.__blockSyncToOverlayCtrl&&this.__syncToOverlayController()}_setupOpenCloseListeners(){this.__closeEventInContentNodeHandler=e=>{e.stopPropagation(),this._overlayCtrl.hide()},this._overlayContentNode&&this._overlayContentNode.addEventListener("close-overlay",this.__closeEventInContentNodeHandler)}_teardownOpenCloseListeners(){this._overlayContentNode&&this._overlayContentNode.removeEventListener("close-overlay",this.__closeEventInContentNodeHandler)}connectedCallback(){super.connectedCallback(),this.updateComplete.then(()=>{this.isConnected&&(this.#e||(this._setupOverlayCtrl(),this.#e=!0))})}async disconnectedCallback(){super.disconnectedCallback(),await this._isPermanentlyDisconnected()&&(this._teardownOverlayCtrl(),this.#e=!1)}static enabledWarnings=super.enabledWarnings?.filter(e=>e!=="change-in-update")||[];get _overlayInvokerNode(){return Array.from(this.children).find(e=>e.slot==="invoker")}get _overlayReferenceNode(){}get _overlayBackdropNode(){return this.__cachedOverlayBackdropNode||(this.__cachedOverlayBackdropNode=Array.from(this.children).find(e=>e.slot==="backdrop")),this.__cachedOverlayBackdropNode}get _overlayContentNode(){return this._cachedOverlayContentNode||(this._cachedOverlayContentNode=Array.from(this.children).find(e=>e.slot==="content")||this.config.contentNode),this._cachedOverlayContentNode}get _overlayContentWrapperNode(){return this.shadowRoot?.querySelector("#overlay-content-node-wrapper")}_setupOverlayCtrl(){if(this.#e)return;const e={contentNode:this._overlayContentNode,contentWrapperNode:this._overlayContentWrapperNode,invokerNode:this._overlayInvokerNode,referenceNode:this._overlayReferenceNode,backdropNode:this._overlayBackdropNode};this._overlayCtrl?this._overlayCtrl.updateConfig(e):this._overlayCtrl=this._defineOverlay(e),this.__syncToOverlayController(),this.__setupSyncFromOverlayController(),this._setupOpenCloseListeners()}_teardownOverlayCtrl(){this._overlayCtrl&&(this._teardownOpenCloseListeners(),this.__teardownSyncFromOverlayController(),this._overlayCtrl.teardown())}async _setOpenedWithoutPropertyEffects(e){this.__blockSyncToOverlayCtrl=!0,this.opened=e,await this.updateComplete,this.__blockSyncToOverlayCtrl=!1}__setupSyncFromOverlayController(){this.__onOverlayCtrlShow=()=>{this.opened=!0},this.__onOverlayCtrlHide=()=>{this.opened=!1},this.__onBeforeShow=e=>{const t=new CustomEvent("before-opened",{cancelable:!0});this.dispatchEvent(t),t.defaultPrevented&&(this._setOpenedWithoutPropertyEffects(this._overlayCtrl.isShown),e.preventDefault())},this.__onBeforeHide=e=>{const t=new CustomEvent("before-closed",{cancelable:!0});this.dispatchEvent(t),t.defaultPrevented&&(this._setOpenedWithoutPropertyEffects(this._overlayCtrl.isShown),e.preventDefault())},this._overlayCtrl.addEventListener("show",this.__onOverlayCtrlShow),this._overlayCtrl.addEventListener("hide",this.__onOverlayCtrlHide),this._overlayCtrl.addEventListener("before-show",this.__onBeforeShow),this._overlayCtrl.addEventListener("before-hide",this.__onBeforeHide)}__teardownSyncFromOverlayController(){this._overlayCtrl.removeEventListener("show",this.__onOverlayCtrlShow),this._overlayCtrl.removeEventListener("hide",this.__onOverlayCtrlHide),this._overlayCtrl.removeEventListener("before-show",this.__onBeforeShow),this._overlayCtrl.removeEventListener("before-hide",this.__onBeforeHide)}__syncToOverlayController(){this.opened?this._overlayCtrl.show():this._overlayCtrl.hide()}async toggle(){await this._overlayCtrl.toggle()}async open(){await this._overlayCtrl.show()}async close(){await this._overlayCtrl.hide()}repositionOverlay(){const e=this._overlayCtrl;e.placementMode==="local"&&e._popper&&e._popper.update()}async _isPermanentlyDisconnected(){return await this.updateComplete,!this.isConnected}},Cu=te(xu);function $u(){return{visibilityTriggerFunction:({controller:s})=>{function e(){s._hasDisabledInvoker()||s.toggle()}return{init:()=>{s.invokerNode?.addEventListener("click",e)},teardown:()=>{s.invokerNode?.removeEventListener("click",e)}}}}}const Au=()=>({placementMode:"local",inheritsReferenceWidth:"min",hidesOnOutsideClick:!0,hidesOnEsc:!0,popperConfig:{placement:"bottom-start",modifiers:[{name:"offset",enabled:!1}]},handlesAccessibility:!0,...$u()});let Fa=class extends Qe(ha){static get styles(){return[...super.styles,R`
        :host {
          justify-content: space-between;
          align-items: center;
        }

        #content-wrapper {
          position: relative;
          pointer-events: none;
        }
      `]}static get properties(){return{selectedElement:{type:Object},hostElement:{type:Object},readOnly:{type:Boolean,reflect:!0,attribute:"readonly"},singleOption:{type:Boolean,reflect:!0,attribute:"single-option"}}}get slots(){return{...super.slots,after:()=>{const e=document.createElement("span");return e.textContent="▼",e.setAttribute("role","img"),e.setAttribute("aria-hidden","true"),e}}}get _contentWrapperNode(){return this.shadowRoot.getElementById("content-wrapper")}constructor(){super(),this.readOnly=!1,this.selectedElement=null,this.hostElement=null,this.singleOption=!1,this.type="button"}__handleKeydown(e){switch(e.key){case"ArrowDown":case"ArrowUp":e.preventDefault()}}connectedCallback(){super.connectedCallback(),this.addEventListener("keydown",this.__handleKeydown)}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener("keydown",this.__handleKeydown)}_contentTemplate(){if(this.selectedElement){const e=Array.from(this.selectedElement.childNodes);return e.length>0?e.map(t=>t.cloneNode(!0)):this.selectedElement.textContent}return this._noSelectionTemplate()}render(){return k` ${this._beforeTemplate()} ${super.render()} ${this._afterTemplate()} `}_noSelectionTemplate(){return k``}_beforeTemplate(){return k` <div id="content-wrapper">${this._contentTemplate()}</div> `}_afterTemplate(){return k`${this.singleOption?"":k`<slot name="after"></slot>`}`}};function Su(){return Js.isMac?"mac":"windows/linux"}class Nu extends Qe(pi(Cu(iu))){static get scopedElements(){return{...super.scopedElements,"lion-select-invoker":Fa}}static get properties(){return{navigateWithinInvoker:{type:Boolean,attribute:"navigate-within-invoker"},interactionMode:{type:String,attribute:"interaction-mode"},singleOption:{type:Boolean,reflect:!0,attribute:"single-option"}}}_inputGroupInputTemplate(){return k`
      <div class="input-group__input">
        <slot name="invoker"></slot>
        <div id="overlay-content-node-wrapper">
          <slot name="input"></slot>
          <slot id="options-outlet"></slot>
        </div>
      </div>
    `}get slots(){return{...super.slots,invoker:()=>k`<lion-select-invoker></lion-select-invoker>`}}get _invokerNode(){return Array.from(this.children).find(e=>e.slot==="invoker")}get _focusableNode(){return this._invokerNode}get _scrollTargetNode(){return this._overlayContentNode._scrollTargetNode||this._overlayContentNode}constructor(){super(),this.navigateWithinInvoker=!1,this.interactionMode="auto",this.singleOption=!1,this._arrowWidth=28,this.__onKeyUp=this.__onKeyUp.bind(this),this.__invokerOnBlur=this.__invokerOnBlur.bind(this),this.__overlayOnHide=this.__overlayOnHide.bind(this),this.__overlayOnShow=this.__overlayOnShow.bind(this),this.__invokerOnClick=this.__invokerOnClick.bind(this),this.__overlayBeforeShow=this.__overlayBeforeShow.bind(this),this._listboxOnClick=this._listboxOnClick.bind(this)}connectedCallback(){super.connectedCallback(),this.registrationComplete.then(()=>{this._invokerNode.selectedElement=this.formElements[this.checkedIndex]}),this._invokerNode.hostElement=this,this.__setupInvokerNode(),this.__toggleInvokerDisabled(),this.addEventListener("keyup",this.__onKeyUp)}disconnectedCallback(){super.disconnectedCallback(),this.__teardownInvokerNode(),this.removeEventListener("keyup",this.__onKeyUp)}requestUpdate(e,t,i){super.requestUpdate(e,t,i),e==="interactionMode"&&(this.interactionMode==="auto"?this.interactionMode=Su():(this.selectionFollowsFocus=this.interactionMode==="windows/linux",this.navigateWithinInvoker=this.interactionMode==="windows/linux")),(e==="disabled"||e==="readOnly")&&this.__toggleInvokerDisabled()}updated(e){super.updated(e),e.has("disabled")&&(this.disabled?this._invokerNode.makeRequestToBeDisabled():this._invokerNode.retractRequestToBeDisabled()),e.has("singleOption")&&(this.singleOption?(this._invokerNode.removeAttribute("role"),this._invokerNode.removeAttribute("aria-haspopup"),this._invokerNode.removeAttribute("aria-expanded")):(this._invokerNode.setAttribute("role","button"),this._invokerNode.setAttribute("aria-haspopup","listbox"),this._invokerNode.setAttribute("aria-expanded",`${this.opened}`))),this._inputNode&&this._invokerNode&&(e.has("_ariaLabelledNodes")&&this._invokerNode.setAttribute("aria-labelledby",`${this._inputNode.getAttribute("aria-labelledby")} ${this._invokerNode.id}`),e.has("_ariaDescribedNodes")&&this._invokerNode.setAttribute("aria-describedby",this._inputNode.getAttribute("aria-describedby")),e.has("showsFeedbackFor")&&this._invokerNode.setAttribute("aria-invalid",`${this._hasFeedbackVisibleFor("error")}`)),e.has("modelValue")&&this.__syncInvokerElement()}addFormElement(e,t){super.addFormElement(e,t),!this.hasNoDefaultSelected&&!this.__hasInitialSelectedFormElement&&(!e.disabled||this.disabled)&&(e.active=!0,e.checked=!0,this.__hasInitialSelectedFormElement=!0),this._alignInvokerWidth(),this._onFormElementsChanged()}removeFormElement(e){super.removeFormElement(e),this._alignInvokerWidth(),this._onFormElementsChanged()}_getCheckedElements(){return this.formElements.filter(e=>e.checked)}_onFormElementsChanged(){this.singleOption=this.formElements.length===1&&!this.hasNoDefaultSelected,this._invokerNode.singleOption=this.singleOption}__initInteractionStates(){this.initInteractionState()}__toggleInvokerDisabled(){this._invokerNode&&(this._invokerNode.disabled=this.disabled,this._invokerNode.readOnly=this.readOnly)}__syncInvokerElement(){this._invokerNode&&(this._invokerNode.selectedElement=this.formElements[this.checkedIndex],this._invokerNode.requestUpdate("selectedElement"))}__setupInvokerNode(){this._invokerNode.id=`invoker-${this._inputId}`,this._invokerNode.setAttribute("aria-haspopup","listbox"),this.__setupInvokerNodeEventListener()}__invokerOnClick(){!this.disabled&&!this.readOnly&&!this.singleOption&&!this.__blockListShow&&this._overlayCtrl.toggle()}__invokerOnBlur(){this.dispatchEvent(new Event("blur"))}__setupInvokerNodeEventListener(){this._invokerNode.addEventListener("click",this.__invokerOnClick),this._invokerNode.addEventListener("blur",this.__invokerOnBlur)}__teardownInvokerNode(){this._invokerNode.removeEventListener("click",this.__invokerOnClick),this._invokerNode.removeEventListener("blur",this.__invokerOnBlur)}_defineOverlayConfig(){return{...Au(),visibilityTriggerFunction:void 0}}_noDefaultSelectedInheritsWidth(){this.checkedIndex===-1?this._overlayCtrl.updateConfig({inheritsReferenceWidth:"min"}):this._overlayCtrl.updateConfig({inheritsReferenceWidth:this._initialInheritsReferenceWidth})}__overlayBeforeShow(){this.hasNoDefaultSelected&&this._noDefaultSelectedInheritsWidth(),this._listboxNode.setAttribute("autofocus","")}__overlayOnShow(){this.checkedIndex!=null&&(this.activeIndex=this.checkedIndex),this._listboxNode.focus()}__overlayOnHide(){this._invokerNode.focus(),this._listboxNode.removeAttribute("autofocus")}_setupOverlayCtrl(){super._setupOverlayCtrl(),this._initialInheritsReferenceWidth=this._overlayCtrl.inheritsReferenceWidth,this._alignInvokerWidth(),this._overlayCtrl.addEventListener("before-show",this.__overlayBeforeShow),this._overlayCtrl.addEventListener("show",this.__overlayOnShow),this._overlayCtrl.addEventListener("hide",this.__overlayOnHide)}_teardownOverlayCtrl(){super._teardownOverlayCtrl(),this._overlayCtrl.removeEventListener("show",this.__overlayOnShow),this._overlayCtrl.removeEventListener("before-show",this.__overlayBeforeShow),this._overlayCtrl.removeEventListener("hide",this.__overlayOnHide)}async _alignInvokerWidth(){if(await this.updateComplete,!this._overlayCtrl?.content)return;const e=this._overlayCtrl.content.style.display,t=this._overlayCtrl.contentWrapperNode.style.minWidth,i=this._overlayCtrl.contentWrapperNode.style.width;this._overlayCtrl.content.style.display="",this._overlayCtrl.contentWrapperNode.style.minWidth="auto",this._overlayCtrl.contentWrapperNode.style.width="auto";const o=this._overlayCtrl.contentWrapperNode.getBoundingClientRect().width;o>0&&(this._invokerNode.style.width=`${o+this._arrowWidth}px`),this._overlayCtrl.content.style.display=e,this._overlayCtrl.contentWrapperNode.style.minWidth=t,this._overlayCtrl.contentWrapperNode.style.width=i}_onLabelClick(){this._invokerNode.focus()}get _overlayInvokerNode(){return this._invokerNode}get _overlayContentNode(){return this._listboxNode}__onKeyUp(e){if(this.disabled||this.readOnly||this.singleOption||this.opened)return;this._isHandlingUserInput=!0,setTimeout(()=>{this._isHandlingUserInput=!1});const{key:t}=e;switch(t){case"ArrowUp":e.preventDefault(),this.navigateWithinInvoker?this.setCheckedIndex(this._getPreviousEnabledOption(this.checkedIndex)):this.opened=!0;break;case"ArrowDown":e.preventDefault(),this.navigateWithinInvoker?this.setCheckedIndex(this._getNextEnabledOption(this.checkedIndex)):this.opened=!0;break;case"ArrowLeft":e.preventDefault(),this.navigateWithinInvoker&&this.setCheckedIndex(this._getPreviousEnabledOption(this.checkedIndex));break;case"ArrowRight":e.preventDefault(),this.navigateWithinInvoker&&this.setCheckedIndex(this._getNextEnabledOption(this.checkedIndex));break;default:this._noTypeAhead||this._handleTypeAhead(e,{setAsChecked:!0})}}_listboxOnKeyDown(e){if(super._listboxOnKeyDown(e),this.disabled)return;const{key:t}=e;switch(t){case"Tab":if(this._overlayCtrl.config.trapsKeyboardFocus===!0)return;this.opened=!1;break;case"Escape":this.opened=!1,this.__blockListShowDuringTransition();break;case"Enter":case" ":this.opened=!1,this.__blockListShowDuringTransition();break}}_listboxOnClick(){this.opened=!1}_setupListboxNode(){super._setupListboxNode(),this._listboxNode.addEventListener("click",this._listboxOnClick)}_teardownListboxNode(){super._teardownListboxNode(),this._listboxNode&&this._listboxNode.removeEventListener("click",this._listboxOnClick)}__blockListShowDuringTransition(){this.__blockListShow=!0,setTimeout(()=>{this.__blockListShow=!1},200)}}let Tu=class extends Nu{static get styles(){return[...super.styles,qh]}get slots(){return{...super.slots,invoker:()=>k`<craft-select-invoker></craft-select-invoker>`}}},Ou=class extends Fa{static get styles(){return[...super.styles,R`
        :host {
          ${fa}
          box-shadow: var(--c-select-shadow);
        }
      `]}get slots(){return{...super.slots,after:()=>{const e=document.createElement("craft-icon");return e.style.fontSize="0.8em",e.name="chevron-down",e}}}};customElements.get("craft-select")||customElements.define("craft-select",Tu);customElements.get("craft-select-invoker")||customElements.define("craft-select-invoker",Ou);/*! Copyright 2025 Fonticons, Inc. - https://webawesome.com/license */var Pa=`@layer wa-utilities {
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
`;/*! Copyright 2025 Fonticons, Inc. - https://webawesome.com/license */var fi=class{constructor(e,...t){this.slotNames=[],this.handleSlotChange=i=>{const o=i.target;(this.slotNames.includes("[default]")&&!o.name||o.name&&this.slotNames.includes(o.name))&&this.host.requestUpdate()},(this.host=e).addController(this),this.slotNames=t}hasDefaultSlot(){return[...this.host.childNodes].some(e=>{if(e.nodeType===Node.TEXT_NODE&&e.textContent.trim()!=="")return!0;if(e.nodeType===Node.ELEMENT_NODE){const t=e;if(t.tagName.toLowerCase()==="wa-visually-hidden")return!1;if(!t.hasAttribute("slot"))return!0}return!1})}hasNamedSlot(e){return this.host.querySelector(`:scope > [slot="${e}"]`)!==null}test(e){return e==="[default]"?this.hasDefaultSlot():this.hasNamedSlot(e)}hostConnected(){this.host.shadowRoot.addEventListener("slotchange",this.handleSlotChange)}hostDisconnected(){this.host.shadowRoot.removeEventListener("slotchange",this.handleSlotChange)}};/*! Copyright 2025 Fonticons, Inc. - https://webawesome.com/license */var Lu=class extends Event{constructor(e){super("wa-select",{bubbles:!0,cancelable:!0,composed:!0}),this.detail=e}};function*Ma(s=document.activeElement){s!=null&&(yield s,"shadowRoot"in s&&s.shadowRoot&&s.shadowRoot.mode!=="closed"&&(yield*Ma(s.shadowRoot.activeElement)))}var Ru=`:host {
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
`,Vi=new Set,he=class extends me{constructor(){super(...arguments),this.submenuCleanups=new Map,this.localize=new Mt(this),this.userTypedQuery="",this.openSubmenuStack=[],this.open=!1,this.size="medium",this.placement="bottom-start",this.distance=0,this.skidding=0,this.handleDocumentKeyDown=async e=>{const t=this.localize.dir()==="rtl";if(e.key==="Escape"){const u=this.getTrigger();e.preventDefault(),e.stopPropagation(),this.open=!1,u?.focus();return}const i=[...Ma()].find(u=>u.localName==="wa-dropdown-item"),o=i?.localName==="wa-dropdown-item",r=this.getCurrentSubmenuItem(),n=!!r;let a,l,c;n?(a=this.getSubmenuItems(r),l=a.find(u=>u.active||u===i),c=l?a.indexOf(l):-1):(a=this.getItems(),l=a.find(u=>u.active||u===i),c=l?a.indexOf(l):-1);let d;if(e.key==="ArrowUp"&&(e.preventDefault(),e.stopPropagation(),c>0?d=a[c-1]:d=a[a.length-1]),e.key==="ArrowDown"&&(e.preventDefault(),e.stopPropagation(),c!==-1&&c<a.length-1?d=a[c+1]:d=a[0]),e.key===(t?"ArrowLeft":"ArrowRight")&&o&&l&&l.hasSubmenu){e.preventDefault(),e.stopPropagation(),l.submenuOpen=!0,this.addToSubmenuStack(l),setTimeout(()=>{const u=this.getSubmenuItems(l);u.length>0&&(u.forEach((b,p)=>b.active=p===0),u[0].focus())},0);return}if(e.key===(t?"ArrowRight":"ArrowLeft")&&n){e.preventDefault(),e.stopPropagation();const u=this.removeFromSubmenuStack();u&&(u.submenuOpen=!1,setTimeout(()=>{u.focus(),u.active=!0,(u.slot==="submenu"?this.getSubmenuItems(u.parentElement):this.getItems()).forEach(b=>{b!==u&&(b.active=!1)})},0));return}if((e.key==="Home"||e.key==="End")&&(e.preventDefault(),e.stopPropagation(),d=e.key==="Home"?a[0]:a[a.length-1]),e.key==="Tab"&&await this.hideMenu(),e.key.length===1&&!(e.metaKey||e.ctrlKey||e.altKey)&&!(e.key===" "&&this.userTypedQuery==="")&&(clearTimeout(this.userTypedTimeout),this.userTypedTimeout=setTimeout(()=>{this.userTypedQuery=""},1e3),this.userTypedQuery+=e.key,a.some(u=>{const b=(u.textContent||"").trim().toLowerCase(),p=this.userTypedQuery.trim().toLowerCase();return b.startsWith(p)?(d=u,!0):!1})),d){e.preventDefault(),e.stopPropagation(),a.forEach(u=>u.active=u===d),d.focus();return}(e.key==="Enter"||e.key===" "&&this.userTypedQuery==="")&&o&&l&&(e.preventDefault(),e.stopPropagation(),l.hasSubmenu?(l.submenuOpen=!0,this.addToSubmenuStack(l),setTimeout(()=>{const u=this.getSubmenuItems(l);u.length>0&&(u.forEach((b,p)=>b.active=p===0),u[0].focus())},0)):this.makeSelection(l))},this.handleDocumentPointerDown=e=>{e.composedPath().some(t=>t instanceof HTMLElement?t===this||t.closest('wa-dropdown, [part="submenu"]'):!1)||(this.open=!1)},this.handleGlobalMouseMove=e=>{const t=this.getCurrentSubmenuItem();if(!t?.submenuOpen||!t.submenuElement)return;const i=t.submenuElement.getBoundingClientRect(),o=this.localize.dir()==="rtl",r=o?i.right:i.left,n=o?Math.max(e.clientX,r):Math.min(e.clientX,r),a=Math.max(i.top,Math.min(e.clientY,i.bottom));t.submenuElement.style.setProperty("--safe-triangle-cursor-x",`${n}px`),t.submenuElement.style.setProperty("--safe-triangle-cursor-y",`${a}px`);const l=t.matches(":hover"),c=t.submenuElement?.matches(":hover")||!!e.composedPath().find(d=>d instanceof HTMLElement&&d.closest('[part="submenu"]')===t.submenuElement);!l&&!c&&setTimeout(()=>{!t.matches(":hover")&&!t.submenuElement?.matches(":hover")&&(t.submenuOpen=!1)},100)}}disconnectedCallback(){super.disconnectedCallback(),clearInterval(this.userTypedTimeout),this.closeAllSubmenus(),this.submenuCleanups.forEach(e=>e()),this.submenuCleanups.clear(),document.removeEventListener("mousemove",this.handleGlobalMouseMove)}firstUpdated(){this.syncAriaAttributes()}async updated(e){e.has("open")&&(this.customStates.set("open",this.open),this.open?await this.showMenu():(this.closeAllSubmenus(),await this.hideMenu())),e.has("size")&&this.syncItemSizes()}getItems(e=!1){const t=this.defaultSlot.assignedElements({flatten:!0}).filter(i=>i.localName==="wa-dropdown-item");return e?t:t.filter(i=>!i.disabled)}getSubmenuItems(e,t=!1){const i=e.shadowRoot?.querySelector('slot[name="submenu"]')||e.querySelector('slot[name="submenu"]');if(!i)return[];const o=i.assignedElements({flatten:!0}).filter(r=>r.localName==="wa-dropdown-item");return t?o:o.filter(r=>!r.disabled)}syncItemSizes(){this.defaultSlot.assignedElements({flatten:!0}).filter(e=>e.localName==="wa-dropdown-item").forEach(e=>e.size=this.size)}addToSubmenuStack(e){const t=this.openSubmenuStack.indexOf(e);t!==-1?this.openSubmenuStack=this.openSubmenuStack.slice(0,t+1):this.openSubmenuStack.push(e)}removeFromSubmenuStack(){return this.openSubmenuStack.pop()}getCurrentSubmenuItem(){return this.openSubmenuStack.length>0?this.openSubmenuStack[this.openSubmenuStack.length-1]:void 0}closeAllSubmenus(){this.getItems(!0).forEach(e=>{e.submenuOpen=!1}),this.openSubmenuStack=[]}closeSiblingSubmenus(e){const t=e.closest('wa-dropdown-item:not([slot="submenu"])');let i;t?i=this.getSubmenuItems(t,!0):i=this.getItems(!0),i.forEach(o=>{o!==e&&o.submenuOpen&&(o.submenuOpen=!1)}),this.openSubmenuStack.includes(e)||this.openSubmenuStack.push(e)}getTrigger(){return this.querySelector('[slot="trigger"]')}async showMenu(){if(!this.getTrigger())return;const e=new ps;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}Vi.forEach(i=>i.open=!1),this.popup.active=!0,this.open=!0,Vi.add(this),this.syncAriaAttributes(),document.addEventListener("keydown",this.handleDocumentKeyDown),document.addEventListener("pointerdown",this.handleDocumentPointerDown),document.addEventListener("mousemove",this.handleGlobalMouseMove),this.menu.classList.remove("hide"),await de(this.menu,"show");const t=this.getItems();t.length>0&&(t.forEach((i,o)=>i.active=o===0),t[0].focus()),this.dispatchEvent(new hs)}async hideMenu(){const e=new us({source:this});if(this.dispatchEvent(e),e.defaultPrevented){this.open=!0;return}this.open=!1,Vi.delete(this),this.syncAriaAttributes(),document.removeEventListener("keydown",this.handleDocumentKeyDown),document.removeEventListener("pointerdown",this.handleDocumentPointerDown),document.removeEventListener("mousemove",this.handleGlobalMouseMove),this.menu.classList.remove("show"),await de(this.menu,"hide"),this.popup.active=this.open,this.dispatchEvent(new ds)}handleMenuClick(e){const t=e.target.closest("wa-dropdown-item");if(!(!t||t.disabled)){if(t.hasSubmenu){t.submenuOpen||(this.closeSiblingSubmenus(t),this.addToSubmenuStack(t),t.submenuOpen=!0),e.stopPropagation();return}this.makeSelection(t)}}async handleMenuSlotChange(){const e=this.getItems(!0);await Promise.all(e.map(o=>o.updateComplete)),this.syncItemSizes();const t=e.some(o=>o.type==="checkbox"),i=e.some(o=>o.hasSubmenu);e.forEach((o,r)=>{o.active=r===0,o.checkboxAdjacent=t,o.submenuAdjacent=i})}handleTriggerClick(){this.open=!this.open}handleSubmenuOpening(e){const t=e.detail.item;this.closeSiblingSubmenus(t),this.addToSubmenuStack(t),this.setupSubmenuPosition(t),this.processSubmenuItems(t)}setupSubmenuPosition(e){if(!e.submenuElement)return;this.cleanupSubmenuPosition(e);const t=Jn(e,e.submenuElement,()=>{this.positionSubmenu(e),this.updateSafeTriangleCoordinates(e)});this.submenuCleanups.set(e,t);const i=e.submenuElement.querySelector('slot[name="submenu"]');i&&(i.removeEventListener("slotchange",he.handleSubmenuSlotChange),i.addEventListener("slotchange",he.handleSubmenuSlotChange),he.handleSubmenuSlotChange({target:i}))}static handleSubmenuSlotChange(e){const t=e.target;if(!t)return;const i=t.assignedElements().filter(n=>n.localName==="wa-dropdown-item");if(i.length===0)return;const o=i.some(n=>n.hasSubmenu),r=i.some(n=>n.type==="checkbox");i.forEach(n=>{n.submenuAdjacent=o,n.checkboxAdjacent=r})}processSubmenuItems(e){if(!e.submenuElement)return;const t=this.getSubmenuItems(e,!0),i=t.some(o=>o.hasSubmenu);t.forEach(o=>{o.submenuAdjacent=i})}cleanupSubmenuPosition(e){const t=this.submenuCleanups.get(e);t&&(t(),this.submenuCleanups.delete(e))}positionSubmenu(e){if(!e.submenuElement)return;const t=this.localize.dir()==="rtl"?"left-start":"right-start";ta(e,e.submenuElement,{placement:t,middleware:[Xn({mainAxis:0,crossAxis:-5}),ea({fallbackStrategy:"bestFit"}),Qn({padding:8})]}).then(({x:i,y:o,placement:r})=>{e.submenuElement.setAttribute("data-placement",r),Object.assign(e.submenuElement.style,{left:`${i}px`,top:`${o}px`})})}updateSafeTriangleCoordinates(e){if(!e.submenuElement||!e.submenuOpen)return;if(document.activeElement?.matches(":focus-visible")){e.submenuElement.style.setProperty("--safe-triangle-visible","none");return}e.submenuElement.style.setProperty("--safe-triangle-visible","block");const t=e.submenuElement.getBoundingClientRect(),i=this.localize.dir()==="rtl";e.submenuElement.style.setProperty("--safe-triangle-submenu-start-x",`${i?t.right:t.left}px`),e.submenuElement.style.setProperty("--safe-triangle-submenu-start-y",`${t.top}px`),e.submenuElement.style.setProperty("--safe-triangle-submenu-end-x",`${i?t.right:t.left}px`),e.submenuElement.style.setProperty("--safe-triangle-submenu-end-y",`${t.bottom}px`)}makeSelection(e){const t=this.getTrigger();if(e.disabled)return;e.type==="checkbox"&&(e.checked=!e.checked);const i=new Lu({item:e});this.dispatchEvent(i),i.defaultPrevented||(this.open=!1,t?.focus())}async syncAriaAttributes(){const e=this.getTrigger();let t;e&&(e.localName==="wa-button"?(await customElements.whenDefined("wa-button"),await e.updateComplete,t=e.shadowRoot.querySelector('[part="base"]')):t=e,t.hasAttribute("id")||t.setAttribute("id",Do("wa-dropdown-trigger-")),t.setAttribute("aria-haspopup","menu"),t.setAttribute("aria-expanded",this.open?"true":"false"),this.menu.setAttribute("aria-expanded","false"))}render(){let e=this.hasUpdated?this.popup.active:this.open;return k`
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
    `}};he.css=[Pa,Ru];v([Q("slot:not([name])")],he.prototype,"defaultSlot",2);v([Q("#menu")],he.prototype,"menu",2);v([Q("wa-popup")],he.prototype,"popup",2);v([y({type:Boolean,reflect:!0})],he.prototype,"open",2);v([y({reflect:!0})],he.prototype,"size",2);v([y({reflect:!0})],he.prototype,"placement",2);v([y({type:Number})],he.prototype,"distance",2);v([y({type:Number})],he.prototype,"skidding",2);he=v([Ae("wa-dropdown")],he);/*! Copyright 2025 Fonticons, Inc. - https://webawesome.com/license */var Fu=`:host {
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
`,le=class extends me{constructor(){super(...arguments),this.hasSlotController=new fi(this,"[default]","start","end"),this.active=!1,this.variant="default",this.size="medium",this.checkboxAdjacent=!1,this.submenuAdjacent=!1,this.type="normal",this.checked=!1,this.disabled=!1,this.submenuOpen=!1,this.hasSubmenu=!1,this.handleSlotChange=()=>{this.hasSubmenu=this.hasSlotController.test("submenu"),this.updateHasSubmenuState(),this.hasSubmenu?(this.setAttribute("aria-haspopup","menu"),this.setAttribute("aria-expanded",this.submenuOpen?"true":"false")):(this.removeAttribute("aria-haspopup"),this.removeAttribute("aria-expanded"))}}connectedCallback(){super.connectedCallback(),this.addEventListener("mouseenter",this.handleMouseEnter.bind(this)),this.shadowRoot.addEventListener("slotchange",this.handleSlotChange)}disconnectedCallback(){super.disconnectedCallback(),this.closeSubmenu(),this.removeEventListener("mouseenter",this.handleMouseEnter),this.shadowRoot.removeEventListener("slotchange",this.handleSlotChange)}firstUpdated(){this.setAttribute("tabindex","-1"),this.hasSubmenu=this.hasSlotController.test("submenu"),this.updateHasSubmenuState()}updated(e){e.has("active")&&(this.setAttribute("tabindex",this.active?"0":"-1"),this.customStates.set("active",this.active)),e.has("checked")&&(this.setAttribute("aria-checked",this.checked?"true":"false"),this.customStates.set("checked",this.checked)),e.has("disabled")&&(this.setAttribute("aria-disabled",this.disabled?"true":"false"),this.customStates.set("disabled",this.disabled)),e.has("type")&&(this.type==="checkbox"?this.setAttribute("role","menuitemcheckbox"):this.setAttribute("role","menuitem")),e.has("submenuOpen")&&(this.customStates.set("submenu-open",this.submenuOpen),this.submenuOpen?this.openSubmenu():this.closeSubmenu())}updateHasSubmenuState(){this.customStates.set("has-submenu",this.hasSubmenu)}async openSubmenu(){!this.hasSubmenu||!this.submenuElement||(this.notifyParentOfOpening(),this.submenuElement.showPopover(),this.submenuElement.hidden=!1,this.submenuElement.setAttribute("data-visible",""),this.submenuOpen=!0,this.setAttribute("aria-expanded","true"),await de(this.submenuElement,"show"),setTimeout(()=>{const e=this.getSubmenuItems();e.length>0&&(e.forEach((t,i)=>t.active=i===0),e[0].focus())},0))}notifyParentOfOpening(){const e=new CustomEvent("submenu-opening",{bubbles:!0,composed:!0,detail:{item:this}});this.dispatchEvent(e);const t=this.parentElement;t&&[...t.children].filter(i=>i!==this&&i.localName==="wa-dropdown-item"&&i.getAttribute("slot")===this.getAttribute("slot")&&i.submenuOpen).forEach(i=>{i.submenuOpen=!1})}async closeSubmenu(){!this.hasSubmenu||!this.submenuElement||(this.submenuOpen=!1,this.setAttribute("aria-expanded","false"),this.submenuElement.hidden||(await de(this.submenuElement,"hide"),this.submenuElement.hidden=!0,this.submenuElement.removeAttribute("data-visible"),this.submenuElement.hidePopover()))}getSubmenuItems(){return[...this.children].filter(e=>e.localName==="wa-dropdown-item"&&e.getAttribute("slot")==="submenu"&&!e.hasAttribute("disabled"))}handleMouseEnter(){this.hasSubmenu&&!this.disabled&&(this.notifyParentOfOpening(),this.submenuOpen=!0)}render(){return k`
      ${this.type==="checkbox"?k`
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

      ${this.hasSubmenu?k`
            <wa-icon
              id="submenu-indicator"
              part="submenu-icon"
              exportparts="svg:submenu-icon__svg"
              library="system"
              name="chevron-right"
            ></wa-icon>
          `:""}
      ${this.hasSubmenu?k`
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
    `}};le.css=Fu;v([Q("#submenu")],le.prototype,"submenuElement",2);v([y({type:Boolean})],le.prototype,"active",2);v([y({reflect:!0})],le.prototype,"variant",2);v([y({reflect:!0})],le.prototype,"size",2);v([y({attribute:"checkbox-adjacent",type:Boolean,reflect:!0})],le.prototype,"checkboxAdjacent",2);v([y({attribute:"submenu-adjacent",type:Boolean,reflect:!0})],le.prototype,"submenuAdjacent",2);v([y()],le.prototype,"value",2);v([y({reflect:!0})],le.prototype,"type",2);v([y({type:Boolean})],le.prototype,"checked",2);v([y({type:Boolean,reflect:!0})],le.prototype,"disabled",2);v([y({type:Boolean,reflect:!0})],le.prototype,"submenuOpen",2);v([be()],le.prototype,"hasSubmenu",2);le=v([Ae("wa-dropdown-item")],le);let Pu=class extends he{static get styles(){return[he.styles,R`
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
      `]}},Mu=class extends le{static get styles(){return[le.styles,R`
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
      `]}};customElements.get("craft-dropdown")||customElements.define("craft-dropdown",Pu);customElements.get("craft-dropdown-item")||customElements.define("craft-dropdown-item",Mu);const Iu=R`
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
`;function Du({el:s,uid:e}){s.setAttribute("id",`panel-${e}`),s.setAttribute("role","tabpanel"),s.setAttribute("aria-labelledby",`button-${e}`),s.hasAttribute("tabindex")||s.setAttribute("tabindex","0")}function Vu(s){s.setAttribute("selected","true")}function zr(s){s.removeAttribute("selected")}function zu({el:s,uid:e,clickHandler:t,keydownHandler:i,keyupHandler:o}){s.setAttribute("id",`button-${e}`),s.setAttribute("role","tab"),s.setAttribute("aria-controls",`panel-${e}`),s.addEventListener("click",t),s.addEventListener("keyup",o),s.addEventListener("keydown",i)}function Bu({el:s,clickHandler:e,keydownHandler:t,keyupHandler:i}){s.removeAttribute("id"),s.removeAttribute("role"),s.removeAttribute("aria-controls"),s.removeEventListener("click",e),s.removeEventListener("keyup",i),s.removeEventListener("keydown",t)}function Uu(s,e=!1){e&&s.focus(),s.setAttribute("selected","true"),s.setAttribute("aria-selected","true"),s.setAttribute("tabindex","0")}function Br(s){s.removeAttribute("selected"),s.setAttribute("aria-selected","false"),s.setAttribute("tabindex","-1")}function Hu(s){const e=s;switch(e.key){case"ArrowDown":case"ArrowRight":case"ArrowUp":case"ArrowLeft":case"Home":case"End":e.preventDefault()}}let ju=class extends G{static get properties(){return{selectedIndex:{type:Number,attribute:"selected-index",reflect:!0}}}static get styles(){return[R`
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
      `]}render(){return k`
      <div class="tabs__tab-group" role="tablist">
        <slot name="tab"></slot>
      </div>
      <div class="tabs__panels">
        <slot name="panel"></slot>
      </div>
    `}constructor(){super(),this.selectedIndex=0}firstUpdated(e){super.firstUpdated(e),this.__setupSlots(),this.tabs[0]?.disabled&&(this.selectedIndex=this.tabs.findIndex(t=>!t.disabled))}get tabs(){return Array.from(this.children).filter(e=>e.slot==="tab")}get panels(){return Array.from(this.children).filter(e=>e.slot==="panel")}static enabledWarnings=super.enabledWarnings?.filter(e=>e!=="change-in-update")||[];__setupSlots(){if(this.shadowRoot){const e=this.shadowRoot.querySelector("slot[name=tab]"),t=()=>{this.__cleanStore(),this.__setupStore(),this.__updateSelected(!1)};e&&e.addEventListener("slotchange",t)}}__setupStore(){this.__store=[],this.tabs.length!==this.panels.length&&console.warn(`The amount of tabs (${this.tabs.length}) doesn't match the amount of panels (${this.panels.length}).`),this.tabs.forEach((e,t)=>{const i=Uo(),o=this.panels[t],r={uid:i,el:e,button:e,panel:o,clickHandler:this.__createButtonClickHandler(t),keydownHandler:Hu.bind(this),keyupHandler:this.__handleButtonKeyup.bind(this)};Du({...r,el:r.panel}),zu(r),zr(r.panel),Br(r.button),this.__store&&this.__store.push(r)})}__cleanStore(){this.__store&&(this.__store.forEach(e=>{Bu(e)}),this.__store=[])}__getNextNotDisabledTab(e,t,i){let o=[];const r=e.filter((a,l)=>!a.disabled&&l>this.selectedIndex),n=e.filter((a,l)=>!a.disabled&&l<this.selectedIndex);return i==="right"?o=[...r,...n]:o=[...n.reverse(),...r.reverse()],o[0]}__getNextAvailableIndex(e,t){const i=this.tabs[this.selectedIndex];if(this.tabs.every(o=>!o.disabled))return e;if(t==="ArrowRight"||t==="ArrowDown"){const o=this.__getNextNotDisabledTab(this.tabs,i,"right");return this.tabs.findIndex(r=>o===r)}if(t==="ArrowLeft"||t==="ArrowUp"){const o=this.__getNextNotDisabledTab(this.tabs,i,"left");return this.tabs.findIndex(r=>o===r)}if(t==="Home")return this.tabs.findIndex(o=>!o.disabled);if(t==="End"){const o=this.tabs.map((r,n)=>({disabled:r.disabled,index:n})).filter(r=>!r.disabled);return o[o.length-1].index}return-1}__createButtonClickHandler(e){return()=>{this._setSelectedIndexWithFocus(e)}}__handleButtonKeyup(e){const t=e;if(typeof this.selectedIndex=="number")switch(t.key){case"ArrowDown":case"ArrowRight":this.selectedIndex+1>=this._pairCount?this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(0,t.key)):this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this.selectedIndex+1,t.key));break;case"ArrowUp":case"ArrowLeft":this.selectedIndex<=0?this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this._pairCount-1,t.key)):this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this.selectedIndex-1,t.key));break;case"Home":this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(0,t.key));break;case"End":this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this._pairCount-1,t.key));break}}get selectedIndex(){return this.__selectedIndex||0}set selectedIndex(e){if(e===this.__selectedIndex)return;const t=this.__selectedIndex;this.__selectedIndex=e,this.__updateSelected(!1),this.dispatchEvent(new Event("selected-changed")),this.requestUpdate("selectedIndex",t)}_setSelectedIndexWithFocus(e){if(e===-1)return;const t=this.__selectedIndex;this.__selectedIndex=e,this.__updateSelected(!0),this.dispatchEvent(new Event("selected-changed")),this.requestUpdate("selectedIndex",t)}get _pairCount(){return this.__store&&this.__store.length||0}__updateSelected(e=!1){if(!(this.__store&&typeof this.selectedIndex=="number"&&this.__store[this.selectedIndex]))return;const t=this.tabs.find(n=>n.hasAttribute("selected")),i=this.panels.find(n=>n.hasAttribute("selected"));t&&Br(t),i&&zr(i);const{button:o,panel:r}=this.__store[this.selectedIndex];o&&Uu(o,e),r&&Vu(r)}},qu=class extends ju{static get styles(){return[...super.styles,Iu]}};customElements.get("craft-tabs")||customElements.define("craft-tabs",qu);const Wu=R`
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
`;var Ku=Object.defineProperty,Gu=(s,e,t,i)=>{for(var o=void 0,r=s.length-1,n;r>=0;r--)(n=s[r])&&(o=n(e,t,o)||o);return o&&Ku(e,t,o),o};const Ia=class extends G{constructor(){super(...arguments),this.label=""}render(){const e=!!this.label||!!this.querySelector('[slot="header"]')||!!this.querySelector('[slot="label"]')||!!this.querySelector('[slot="actions"]'),t=!!this.querySelector('[slot="footer"]');return k`
      <div class="card">
        <div>
          ${e?k`<div class="card__header">
                <slot name="header">
                  <slot name="label" class="card__label" part="label"
                    >${this.label}</slot
                  >
                  <slot name="actions"></slot>
                </slot>
              </div>`:V}

          <div class="card__body">
            <slot></slot>
          </div>

          ${t?k`<div class="card__footer"><slot name="footer"></slot></div>`:V}
        </div>
      </div>
    `}};Ia.styles=[Wu];let Da=Ia;Gu([y()],Da.prototype,"label");customElements.get("craft-card")||customElements.define("craft-card",Da);const Zu=R`
  :host {
    display: inline-flex;
  }
`,Va=class extends G{render(){return k`<slot></slot> `}};Va.styles=[Zu];let Yu=Va;customElements.get("craft-tab")||customElements.define("craft-tab",Yu);let za=class extends da(G){static get properties(){return{checked:{type:Boolean,reflect:!0}}}static get styles(){return[R`
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
      `]}render(){return k`
      <div class="btn">
        <div class="switch-button__track"></div>
        <div class="switch-button__thumb"></div>
      </div>
    `}constructor(){super(),this.value="",this.checked=!1,this.__initialized=!1,this._toggleChecked=this._toggleChecked.bind(this),this.__handleKeydown=this._handleKeydown.bind(this),this.__handleKeyup=this._handleKeyup.bind(this)}connectedCallback(){super.connectedCallback(),this.setAttribute("role","switch"),this.setAttribute("aria-checked",`${this.checked}`),this.addEventListener("click",this._toggleChecked),this.addEventListener("keydown",this.__handleKeydown),this.addEventListener("keyup",this.__handleKeyup)}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener("click",this._toggleChecked),this.removeEventListener("keydown",this.__handleKeydown),this.removeEventListener("keyup",this.__handleKeyup)}_toggleChecked(){this.disabled||(this.focus(),this.checked=!this.checked)}__checkedStateChange(){this.dispatchEvent(new Event("checked-changed",{bubbles:!0})),this.setAttribute("aria-checked",`${this.checked}`)}_handleKeydown(e){e.key===" "&&e.preventDefault()}_handleKeyup(e){[" ","Enter"].includes(e.key)&&this._toggleChecked()}updated(e){super.updated(e),e.has("disabled")&&this.setAttribute("aria-disabled",`${this.disabled}`)}requestUpdate(e,t,i){super.requestUpdate(e,t,i),this.__initialized&&this.isConnected&&e==="checked"&&this.checked!==t&&!this.disabled&&this.__checkedStateChange()}firstUpdated(e){super.firstUpdated(e),this.__initialized=!0}},Ba=class extends za{static get styles(){return[...super.styles,R`
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
      `]}};customElements.get("craft-switch-button")||customElements.define("craft-switch-button",Ba);const Ju=R`
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
`;let Xu=class extends pi(Jo(Ko)){static get styles(){return[...super.styles,R`
        :host([hidden]) {
          display: none;
        }

        :host([disabled]) {
          color: #adadad;
        }
      `]}static get scopedElements(){return{...super.scopedElements,"lion-switch-button":za}}get _inputNode(){return Array.from(this.children).find(e=>e.slot==="input")}get slots(){return{...super.slots,input:()=>{const e=this.createScopedElement("lion-switch-button");return e.setAttribute("data-tag-name","lion-switch-button"),e}}}render(){return k`
      <div class="form-field__group-one">${this._groupOneTemplate()}</div>
      <div class="form-field__group-two">${this._groupTwoTemplate()}</div>
    `}_groupOneTemplate(){return k`${this._labelTemplate()} ${this._helpTextTemplate()} ${this._feedbackTemplate()}`}_groupTwoTemplate(){return k`${this._inputGroupTemplate()}`}constructor(){super(),this.checked=!1,this.__handleButtonSwitchCheckedChanged=this.__handleButtonSwitchCheckedChanged.bind(this)}connectedCallback(){super.connectedCallback(),this.addEventListener("checked-changed",this.__handleButtonSwitchCheckedChanged),this._labelNode&&this._labelNode.addEventListener("click",this._toggleChecked),this._syncButtonSwitch()}disconnectedCallback(){super.disconnectedCallback(),this._inputNode&&this.removeEventListener("checked-changed",this.__handleButtonSwitchCheckedChanged),this._labelNode&&this._labelNode.removeEventListener("click",this._toggleChecked)}updated(e){super.updated(e),e.has("disabled")&&this._syncButtonSwitch()}_toggleChecked(e){e.preventDefault(),super._toggleChecked(e)}_isEmpty(){return!1}__handleButtonSwitchCheckedChanged(e){e.stopPropagation(),this._isHandlingUserInput=!0,this.checked=this._inputNode.checked,this._isHandlingUserInput=!1}_syncButtonSwitch(){this._inputNode.disabled=this.disabled}_onLabelClick(){this.disabled||this._inputNode.focus()}},Qu=class extends Xu{static get styles(){return[...super.styles,Ju]}get slots(){return{...super.slots,input:()=>{const e=this.createScopedElement("craft-switch-button");return e.setAttribute("data-tag-name","craft-switch-button"),e}}}static get scopedElements(){return{...super.scopedElements,"craft-switch-button":Ba}}};customElements.get("craft-switch")||customElements.define("craft-switch",Qu);/**
 * @license
 * Copyright 2021 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */function ep(s){return(e,t)=>{const{slot:i,selector:o}=s??{},r="slot"+(i?`[name=${i}]`:":not([name])");return zn(e,t,{get(){const n=this.renderRoot?.querySelector(r),a=n?.assignedElements(s)??[];return o===void 0?a:a.filter((l=>l.matches(o)))}})}}const tp=".breadcrumbs{display:flex;align-items:center}";var sp=Object.defineProperty,zt=(s,e,t,i)=>{for(var o=void 0,r=s.length-1,n;r>=0;r--)(n=s[r])&&(o=n(e,t,o)||o);return o&&sp(e,t,o),o};const Ua=new CSSStyleSheet;Ua.replaceSync(tp);const Ha=class extends G{constructor(){super(...arguments),this.label="",this.items=[],this.visibleItems=0,this.firstRender=!0}getSeparator(){const e=this.separatorSlot.assignedElements({flatten:!0})[0].cloneNode(!0);return[e,...e.querySelectorAll("[id]")].forEach(t=>t.removeAttribute("id")),e.setAttribute("data-default",""),e.slot="separator",e}calculateBreadcrumbItemsWidth(){this.items=this.breadcrumbsElements.map((e,t)=>{let i=e.offsetWidth;return e.hasAttribute("hidden")&&(e.removeAttribute("hidden"),i=e.offsetWidth,e.setAttribute("hidden","")),{label:e.innerText,href:e.href,value:t.toString(),offsetWidth:i,isVisible:!0}})}async handleSlotChange(){const e=[...this.defaultSlot.assignedElements({flatten:!0})].filter(t=>t.tagName.toLowerCase()==="craft-breadcrumb-item");if(e.forEach((t,i)=>{const o=t.querySelector('[slot="separator"]');o===null?t.append(this.getSeparator()):o.hasAttribute("data-default")&&o.replaceWith(this.getSeparator()),i===e.length-1?t.setAttribute("aria-current","page"):t.removeAttribute("aria-current")}),this.breadcrumbsElements.length===0){this.items=[],this.visibleItems=0;return}await Promise.all(this.breadcrumbsElements.map(t=>t.updateComplete)),this.calculateBreadcrumbItemsWidth(),this.visibleItems=0,this.adjustOverflow()}connectedCallback(){super.connectedCallback(),this.hasAttribute("role")||this.setAttribute("role","navigation"),this.resizeObserver=new ResizeObserver(()=>{if(this.firstRender){this.firstRender=!1;return}this.adjustOverflow()}),this.resizeObserver.observe(this)}adjustOverflow(){const e=this.getBoundingClientRect().width;console.log({availableSpace:e})}disconnectedCallback(){this.resizeObserver?.unobserve(this),super.disconnectedCallback()}render(){return k`
      <nav class="breadcrumbs" aria-label="${this.label}">
        <slot @slotchange="${this.handleSlotChange}"></slot>
      </nav>

      <span hidden aria-hidden="true">
        <slot name="separator"><span class="separator">/</span></slot>
      </span>
    `}};Ha.styles=[Ua];let vt=Ha;zt([Q("slot")],vt.prototype,"defaultSlot");zt([Q('slot[name="separator"]')],vt.prototype,"separatorSlot");zt([ep({selector:"craft-breadcrumb-item"})],vt.prototype,"breadcrumbsElements");zt([y()],vt.prototype,"label");zt([be()],vt.prototype,"items");zt([be()],vt.prototype,"visibleItems");customElements.get("craft-breadcrumbs")||customElements.define("craft-breadcrumbs",vt);/**
 * @license
 * Copyright 2018 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const Ee=s=>s??V;/*! Copyright 2025 Fonticons, Inc. - https://webawesome.com/license */var ip=`:host {
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
`,Ce=class extends me{constructor(){super(...arguments),this.renderType="button",this.rel="noreferrer noopener"}setRenderType(){const e=this.defaultSlot.assignedElements({flatten:!0}).filter(t=>t.tagName.toLowerCase()==="wa-dropdown").length>0;if(this.href){this.renderType="link";return}if(e){this.renderType="dropdown";return}this.renderType="button"}hrefChanged(){this.setRenderType()}handleSlotChange(){this.setRenderType()}render(){return k`
      <span part="start" class="start">
        <slot name="start"></slot>
      </span>

      ${this.renderType==="link"?k`
            <a
              part="label"
              class="label label-link"
              href="${this.href}"
              target="${Ee(this.target?this.target:void 0)}"
              rel=${Ee(this.target?this.rel:void 0)}
            >
              <slot></slot>
            </a>
          `:""}
      ${this.renderType==="button"?k`
            <button part="label" type="button" class="label label-button">
              <slot @slotchange=${this.handleSlotChange}></slot>
            </button>
          `:""}
      ${this.renderType==="dropdown"?k`
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
    `}};Ce.css=ip;v([Q("slot:not([name])")],Ce.prototype,"defaultSlot",2);v([be()],Ce.prototype,"renderType",2);v([y()],Ce.prototype,"href",2);v([y()],Ce.prototype,"target",2);v([y()],Ce.prototype,"rel",2);v([ye("href",{waitUntilFirstUpdate:!0})],Ce.prototype,"hrefChanged",1);Ce=v([Ae("wa-breadcrumb-item")],Ce);let op=class extends Ce{static get styles(){return[Ce.styles,R`
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
      `]}};customElements.get("craft-breadcrumb-item")||customElements.define("craft-breadcrumb-item",op);/*! Copyright 2025 Fonticons, Inc. - https://webawesome.com/license */var rp=`:host {
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
`,zi=new Set,re=class extends me{constructor(){super(...arguments),this.anchor=null,this.placement="top",this.open=!1,this.distance=8,this.skidding=0,this.for=null,this.withoutArrow=!1,this.eventController=new AbortController,this.handleAnchorClick=()=>{this.open=!this.open},this.handleBodyClick=e=>{e.target.closest('[data-popover="close"]')&&(e.stopPropagation(),this.open=!1)},this.handleDocumentKeyDown=e=>{e.key==="Escape"&&(e.preventDefault(),this.open=!1,this.anchor&&typeof this.anchor.focus=="function"&&this.anchor.focus())},this.handleDocumentClick=e=>{const t=e.target;this.anchor&&e.composedPath().includes(this.anchor)||t.closest("wa-popover")!==this&&(this.open=!1)}}connectedCallback(){super.connectedCallback(),this.id||(this.id=Do("wa-popover-"))}disconnectedCallback(){super.disconnectedCallback(),document.removeEventListener("keydown",this.handleDocumentKeyDown),this.eventController.abort()}firstUpdated(){this.open&&(this.dialog.show(),this.popup.active=!0,this.popup.reposition())}updated(e){e.has("open")&&this.customStates.set("open",this.open)}async handleOpenChange(){if(this.open){const e=new ps;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}zi.forEach(t=>t.open=!1),document.addEventListener("keydown",this.handleDocumentKeyDown,{signal:this.eventController.signal}),document.addEventListener("click",this.handleDocumentClick,{signal:this.eventController.signal}),this.dialog.show(),this.popup.active=!0,zi.add(this),requestAnimationFrame(()=>{const t=this.querySelector("[autofocus]");t&&typeof t.focus=="function"?t.focus():this.dialog.focus()}),await de(this.popup.popup,"show-with-scale"),this.popup.reposition(),this.dispatchEvent(new hs)}else{const e=new us;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!0;return}document.removeEventListener("keydown",this.handleDocumentKeyDown),document.removeEventListener("click",this.handleDocumentClick),zi.delete(this),await de(this.popup.popup,"hide-with-scale"),this.popup.active=!1,this.dialog.close(),this.dispatchEvent(new ds)}}handleForChange(){const e=this.getRootNode();if(!e)return;const t=this.for?e.getElementById(this.for):null,i=this.anchor;if(t===i)return;const{signal:o}=this.eventController;t&&t.addEventListener("click",this.handleAnchorClick,{signal:o}),i&&i.removeEventListener("click",this.handleAnchorClick),this.anchor=t,this.for&&!t&&console.warn(`A popover was assigned to an element with an ID of "${this.for}" but the element could not be found.`,this)}async handleOptionsChange(){this.hasUpdated&&(await this.updateComplete,this.popup.reposition())}async show(){if(!this.open)return this.open=!0,Gs(this,"wa-after-show")}async hide(){if(this.open)return this.open=!1,Gs(this,"wa-after-hide")}render(){return k`
      <dialog part="dialog" class="dialog">
        <wa-popup
          part="popup"
          exportparts="
            popup:popup__popup,
            arrow:popup__arrow
          "
          class=${Re({popover:!0,"popover-open":this.open})}
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
    `}};re.css=rp;re.dependencies={"wa-popup":K};v([Q("dialog")],re.prototype,"dialog",2);v([Q(".body")],re.prototype,"body",2);v([Q("wa-popup")],re.prototype,"popup",2);v([be()],re.prototype,"anchor",2);v([y()],re.prototype,"placement",2);v([y({type:Boolean,reflect:!0})],re.prototype,"open",2);v([y({type:Number})],re.prototype,"distance",2);v([y({type:Number})],re.prototype,"skidding",2);v([y()],re.prototype,"for",2);v([y({attribute:"without-arrow",type:Boolean,reflect:!0})],re.prototype,"withoutArrow",2);v([ye("open",{waitUntilFirstUpdate:!0})],re.prototype,"handleOpenChange",1);v([ye("for")],re.prototype,"handleForChange",1);v([ye(["distance","placement","skidding"])],re.prototype,"handleOptionsChange",1);re=v([Ae("wa-popover")],re);let np=class extends re{static get styles(){return[re.styles,R`
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
      `]}};customElements.get("craft-popover")||customElements.define("craft-popover",np);const ja=class extends G{render(){return k`
      <nav>
        <slot></slot>
      </nav>
    `}};ja.styles=R`
    :host {
      display: block;
    }

    nav {
      display: grid;
    }
  `;let ap=ja;customElements.get("craft-navigation")||customElements.define("craft-navigation",ap);const lp=R`
  .nav-item {
    display: grid;
    gap: var(--c-spacing-md);
    grid-template-columns: calc(24rem / 16) 1fr auto;
    align-items: center;
    text-decoration: none;
    color: inherit;
    padding-inline: var(--c-spacing-sm);
    padding-block: var(--c-spacing-sm);
    border-radius: var(--c-radius-md);
    position: relative;
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
      transform: translateX(-200%);
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

  .indicator {
    display: inline-block;
    aspect-ratio: 1;
    width: calc(6rem / 16);
    border-radius: var(--c-radius-full);
    background-color: var(--c-color-accent-bg-emphasis);
    border: 1px solid var(--c-color-accent-border-emphasis);
    outline: 2px solid Canvas;
    position: absolute;
    inset-inline-end: 0;
    inset-block-end: 0;
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
`;/**
 * @license
 * Copyright 2018 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const qa="important",cp=" !"+qa,dp=To(class extends Oo{constructor(s){if(super(s),s.type!==No.ATTRIBUTE||s.name!=="style"||s.strings?.length>2)throw Error("The `styleMap` directive must be used in the `style` attribute and must be the only part in the attribute.")}render(s){return Object.keys(s).reduce(((e,t)=>{const i=s[t];return i==null?e:e+`${t=t.includes("-")?t:t.replace(/(?:^(webkit|moz|ms|o)|)(?=[A-Z])/g,"-$&").toLowerCase()}:${i};`}),"")}update(s,[e]){const{style:t}=s.element;if(this.ft===void 0)return this.ft=new Set(Object.keys(e)),this.render(e);for(const i of this.ft)e[i]==null&&(this.ft.delete(i),i.includes("-")?t.removeProperty(i):t[i]=null);for(const i in e){const o=e[i];if(o!=null){this.ft.add(i);const r=typeof o=="string"&&o.endsWith(cp);i.includes("-")||r?t.setProperty(i,r?o.slice(0,-11):o,r?qa:""):t[i]=o}}return Le}});var hp=Object.defineProperty,et=(s,e,t,i)=>{for(var o=void 0,r=s.length-1,n;r>=0;r--)(n=s[r])&&(o=n(e,t,o)||o);return o&&hp(e,t,o),o};const Wa=class extends G{constructor(){super(),this.active=!1,this.external=!1,this.indicator=!1,this.iconOnly=!1,this.subnavState="closed",this.id=this.id||Math.random().toString(36).substring(2,6)}connectedCallback(){super.connectedCallback(),this.subnavState=this.active?"open":"closed"}toggleSubnav(e){e.preventDefault(),e.stopPropagation(),this.subnavState=this.subnavState==="open"?"closed":"open"}renderIconItem(e){const t=`item-${this.id}`;return k`
      <a
        class="nav-item"
        id="${t}"
        href="${this.url}"
        aria-current="${this.active?"page":!1}"
      >
        <span class="nav-item__prefix">
          <slot name="prefix">
            <slot name="icon">
              ${this.icon?k` <craft-icon
                    name="${this.icon}"
                    class="nav-icon"
                  ></craft-icon>`:k` <span class="active-indicator"></span> `}
            </slot>
            ${this.indicator?k`<span class="indicator"></span>`:V}
          </slot>
        </span>

        <div class="nav-item__suffix">
          <slot name="suffix">
            ${e?k`
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
                `:V}
          </slot>
        </div>
      </a>
      <c-tooltip for="${t}" placement="right-start"
        ><slot></slot
      ></c-tooltip>
    `}renderItem(e){return k`
      <a
        class="nav-item"
        href="${this.url}"
        aria-current="${this.active?"page":!1}"
      >
        <span class="nav-item__prefix">
          <slot name="prefix">
            <slot name="icon">
              ${this.icon?k` <craft-icon
                    name="${this.icon}"
                    class="nav-icon"
                  ></craft-icon>`:k` <span class="active-indicator"></span> `}
            </slot>
            ${this.indicator?k`<span class="indicator"></span>`:V}
          </slot>
        </span>
        <slot></slot>

        <div class="nav-item__suffix">
          <slot name="suffix">
            ${e?k`
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
                `:V}
          </slot>
        </div>
      </a>
    `}render(){const e=!!this.querySelector('[slot="subnav"]');return k`
      ${this.iconOnly?this.renderIconItem(e):this.renderItem(e)}
      ${e?k`
            <div
              class="subnav"
              id="${this.id}-subnav"
              style="${dp({display:this.subnavState==="open"?"block":"none"})}"
            >
              <slot name="subnav"></slot>
            </div>
          `:V}
    `}};Wa.styles=lp;let ze=Wa;et([y()],ze.prototype,"icon");et([y()],ze.prototype,"url");et([y({type:Boolean,reflect:!0})],ze.prototype,"active");et([y({type:Boolean})],ze.prototype,"external");et([y({type:Boolean})],ze.prototype,"indicator");et([y()],ze.prototype,"id");et([y({reflect:!0,type:Boolean,attribute:"icon-only"})],ze.prototype,"iconOnly");et([be()],ze.prototype,"subnavState");customElements.get("craft-nav-item")||customElements.define("craft-nav-item",ze);/*! Copyright 2025 Fonticons, Inc. - https://webawesome.com/license */var co=new Set;function up(){const s=document.documentElement.clientWidth;return Math.abs(window.innerWidth-s)}function pp(){const s=Number(getComputedStyle(document.body).paddingRight.replace(/px/,""));return isNaN(s)||!s?0:s}function Xs(s){if(co.add(s),!document.documentElement.classList.contains("wa-scroll-lock")){const e=up()+pp();let t=getComputedStyle(document.documentElement).scrollbarGutter;(!t||t==="auto")&&(t="stable"),e<2&&(t=""),document.documentElement.style.setProperty("--wa-scroll-lock-gutter",t),document.documentElement.classList.add("wa-scroll-lock"),document.documentElement.style.setProperty("--wa-scroll-lock-size",`${e}px`)}}function Qs(s){co.delete(s),co.size===0&&(document.documentElement.classList.remove("wa-scroll-lock"),document.documentElement.style.removeProperty("--wa-scroll-lock-size"))}/*! Copyright 2025 Fonticons, Inc. - https://webawesome.com/license */function Ka(s){return s.split(" ").map(e=>e.trim()).filter(e=>e!=="")}/*! Copyright 2025 Fonticons, Inc. - https://webawesome.com/license */var fp=()=>({checkValidity(s){const e=s.input,t={message:"",isValid:!0,invalidKeys:[]};if(!e)return t;let i=!0;if("checkValidity"in e&&(i=e.checkValidity()),i)return t;if(t.isValid=!1,"validationMessage"in e&&(t.message=e.validationMessage),!("validity"in e))return t.invalidKeys.push("customError"),t;for(const o in e.validity){if(o==="valid")continue;const r=o;e.validity[r]&&t.invalidKeys.push(r)}return t}});/*! Copyright 2025 Fonticons, Inc. - https://webawesome.com/license */var Ga=class extends Event{constructor(){super("wa-invalid",{bubbles:!0,cancelable:!1,composed:!0})}},mp=()=>({observedAttributes:["custom-error"],checkValidity(s){const e={message:"",isValid:!0,invalidKeys:[]};return s.customError&&(e.message=s.customError,e.isValid=!1,e.invalidKeys=["customError"]),e}}),Be=class extends me{constructor(){super(),this.name=null,this.disabled=!1,this.required=!1,this.assumeInteractionOn=["input"],this.validators=[],this.valueHasChanged=!1,this.hasInteracted=!1,this.customError=null,this.emittedEvents=[],this.emitInvalid=e=>{e.target===this&&(this.hasInteracted=!0,this.dispatchEvent(new Ga))},this.handleInteraction=e=>{const t=this.emittedEvents;t.includes(e.type)||t.push(e.type),t.length===this.assumeInteractionOn?.length&&(this.hasInteracted=!0)},this.addEventListener("invalid",this.emitInvalid)}static get validators(){return[mp()]}static get observedAttributes(){const e=new Set(super.observedAttributes||[]);for(const t of this.validators)if(t.observedAttributes)for(const i of t.observedAttributes)e.add(i);return[...e]}connectedCallback(){super.connectedCallback(),this.updateValidity(),this.assumeInteractionOn.forEach(e=>{this.addEventListener(e,this.handleInteraction)})}firstUpdated(...e){super.firstUpdated(...e),this.updateValidity()}willUpdate(e){if(e.has("customError")&&(this.customError||(this.customError=null),this.setCustomValidity(this.customError||"")),e.has("value")||e.has("disabled")){const t=this.value;if(Array.isArray(t)){if(this.name){const i=new FormData;for(const o of t)i.append(this.name,o);this.setValue(i,i)}}else this.setValue(t,t)}e.has("disabled")&&(this.customStates.set("disabled",this.disabled),(this.hasAttribute("disabled")||!this.matches(":disabled"))&&this.toggleAttribute("disabled",this.disabled)),this.updateValidity(),super.willUpdate(e)}get labels(){return this.internals.labels}getForm(){return this.internals.form}get validity(){return this.internals.validity}get willValidate(){return this.internals.willValidate}get validationMessage(){return this.internals.validationMessage}checkValidity(){return this.updateValidity(),this.internals.checkValidity()}reportValidity(){return this.updateValidity(),this.hasInteracted=!0,this.internals.reportValidity()}get validationTarget(){return this.input||void 0}setValidity(...e){const t=e[0],i=e[1];let o=e[2];o||(o=this.validationTarget),this.internals.setValidity(t,i,o||void 0),this.requestUpdate("validity"),this.setCustomStates()}setCustomStates(){const e=!!this.required,t=this.internals.validity.valid,i=this.hasInteracted;this.customStates.set("required",e),this.customStates.set("optional",!e),this.customStates.set("invalid",!t),this.customStates.set("valid",t),this.customStates.set("user-invalid",!t&&i),this.customStates.set("user-valid",t&&i)}setCustomValidity(e){if(!e){this.customError=null,this.setValidity({});return}this.customError=e,this.setValidity({customError:!0},e,this.validationTarget)}formResetCallback(){this.resetValidity(),this.hasInteracted=!1,this.valueHasChanged=!1,this.emittedEvents=[],this.updateValidity()}formDisabledCallback(e){this.disabled=e,this.updateValidity()}formStateRestoreCallback(e,t){this.value=e,t==="restore"&&this.resetValidity(),this.updateValidity()}setValue(...e){const[t,i]=e;this.internals.setFormValue(t,i)}get allValidators(){const e=this.constructor.validators||[],t=this.validators||[];return[...e,...t]}resetValidity(){this.setCustomValidity(""),this.setValidity({})}updateValidity(){if(this.disabled||this.hasAttribute("disabled")||!this.willValidate){this.resetValidity();return}const e=this.allValidators;if(!e?.length)return;const t={customError:!!this.customError},i=this.validationTarget||this.input||void 0;let o="";for(const r of e){const{isValid:n,message:a,invalidKeys:l}=r.checkValidity(this);n||(o||(o=a),l?.length>=0&&l.forEach(c=>t[c]=!0))}o||(o=this.validationMessage),this.setValidity(t,o,i)}};Be.formAssociated=!0;v([y({reflect:!0})],Be.prototype,"name",2);v([y({type:Boolean})],Be.prototype,"disabled",2);v([y({state:!0,attribute:!1})],Be.prototype,"valueHasChanged",2);v([y({state:!0,attribute:!1})],Be.prototype,"hasInteracted",2);v([y({attribute:"custom-error",reflect:!0})],Be.prototype,"customError",2);v([y({attribute:!1,state:!0,type:Object})],Be.prototype,"validity",1);/*! Copyright 2025 Fonticons, Inc. - https://webawesome.com/license */var bp=`@layer wa-utilities {
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
`;/**
 * @license
 * Copyright 2020 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const Za=Symbol.for(""),gp=s=>{if(s?.r===Za)return s?._$litStatic$},Ur=(s,...e)=>({_$litStatic$:e.reduce(((t,i,o)=>t+(r=>{if(r._$litStatic$!==void 0)return r._$litStatic$;throw Error(`Value passed to 'literal' function must be a 'literal' result: ${r}. Use 'unsafeStatic' to pass non-literal values, but
            take care to ensure page security.`)})(i)+s[o+1]),s[0]),r:Za}),Hr=new Map,vp=s=>(e,...t)=>{const i=t.length;let o,r;const n=[],a=[];let l,c=0,d=!1;for(;c<i;){for(l=e[c];c<i&&(r=t[c],(o=gp(r))!==void 0);)l+=o+e[++c],d=!0;c!==i&&a.push(r),n.push(l),c++}if(c===i&&n.push(e[i]),d){const u=n.join("$$lit$$");(e=Hr.get(u))===void 0&&(n.raw=n,Hr.set(u,e=n)),t=a}return s(e,...t)},Bi=vp(k);/*! Copyright 2025 Fonticons, Inc. - https://webawesome.com/license */var _p=`@layer wa-component {
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
`,j=class extends Be{constructor(){super(...arguments),this.assumeInteractionOn=["click"],this.hasSlotController=new fi(this,"[default]","start","end"),this.localize=new Mt(this),this.invalid=!1,this.isIconButton=!1,this.title="",this.variant="neutral",this.appearance="accent",this.size="medium",this.withCaret=!1,this.disabled=!1,this.loading=!1,this.pill=!1,this.type="button",this.form=null}static get validators(){return[...super.validators,fp()]}constructLightDOMButton(){const e=document.createElement("button");return e.type=this.type,e.style.position="absolute",e.style.width="0",e.style.height="0",e.style.clipPath="inset(50%)",e.style.overflow="hidden",e.style.whiteSpace="nowrap",this.name&&(e.name=this.name),e.value=this.value||"",["form","formaction","formenctype","formmethod","formnovalidate","formtarget"].forEach(t=>{this.hasAttribute(t)&&e.setAttribute(t,this.getAttribute(t))}),e}handleClick(){if(!this.getForm())return;const e=this.constructLightDOMButton();this.parentElement?.append(e),e.click(),e.remove()}handleInvalid(){this.dispatchEvent(new Ga)}handleLabelSlotChange(){const e=this.labelSlot.assignedNodes({flatten:!0});let t=!1,i=!1,o=!1,r=!1;[...e].forEach(n=>{if(n.nodeType===Node.ELEMENT_NODE){const a=n;a.localName==="wa-icon"?(i=!0,t||(t=a.label!==void 0)):r=!0}else n.nodeType===Node.TEXT_NODE&&(n.textContent?.trim()||"").length>0&&(o=!0)}),this.isIconButton=i&&!o&&!r,this.isIconButton&&!t&&console.warn('Icon buttons must have a label for screen readers. Add <wa-icon label="..."> to remove this warning.',this)}isButton(){return!this.href}isLink(){return!!this.href}handleDisabledChange(){this.updateValidity()}setValue(...e){}click(){this.button.click()}focus(e){this.button.focus(e)}blur(){this.button.blur()}render(){const e=this.isLink(),t=e?Ur`a`:Ur`button`;return Bi`
      <${t}
        part="base"
        class=${Re({button:!0,caret:this.withCaret,disabled:this.disabled,loading:this.loading,rtl:this.localize.dir()==="rtl","has-label":this.hasSlotController.test("[default]"),"has-start":this.hasSlotController.test("start"),"has-end":this.hasSlotController.test("end"),"is-icon-button":this.isIconButton})}
        ?disabled=${Ee(e?void 0:this.disabled)}
        type=${Ee(e?void 0:this.type)}
        title=${this.title}
        name=${Ee(e?void 0:this.name)}
        value=${Ee(e?void 0:this.value)}
        href=${Ee(e?this.href:void 0)}
        target=${Ee(e?this.target:void 0)}
        download=${Ee(e?this.download:void 0)}
        rel=${Ee(e&&this.rel?this.rel:void 0)}
        role=${Ee(e?void 0:"button")}
        aria-disabled=${this.disabled?"true":"false"}
        tabindex=${this.disabled?"-1":"0"}
        @invalid=${this.isButton()?this.handleInvalid:null}
        @click=${this.handleClick}
      >
        <slot name="start" part="start" class="start"></slot>
        <slot part="label" class="label" @slotchange=${this.handleLabelSlotChange}></slot>
        <slot name="end" part="end" class="end"></slot>
        ${this.withCaret?Bi`
                <wa-icon part="caret" class="caret" library="system" name="chevron-down" variant="solid"></wa-icon>
              `:""}
        ${this.loading?Bi`<wa-spinner part="spinner"></wa-spinner>`:""}
      </${t}>
    `}};j.shadowRootOptions={...Be.shadowRootOptions,delegatesFocus:!0};j.css=[_p,bp,Pa];v([Q(".button")],j.prototype,"button",2);v([Q("slot:not([name])")],j.prototype,"labelSlot",2);v([be()],j.prototype,"invalid",2);v([be()],j.prototype,"isIconButton",2);v([y()],j.prototype,"title",2);v([y({reflect:!0})],j.prototype,"variant",2);v([y({reflect:!0})],j.prototype,"appearance",2);v([y({reflect:!0})],j.prototype,"size",2);v([y({attribute:"with-caret",type:Boolean,reflect:!0})],j.prototype,"withCaret",2);v([y({type:Boolean})],j.prototype,"disabled",2);v([y({type:Boolean,reflect:!0})],j.prototype,"loading",2);v([y({type:Boolean,reflect:!0})],j.prototype,"pill",2);v([y()],j.prototype,"type",2);v([y({reflect:!0})],j.prototype,"name",2);v([y({reflect:!0})],j.prototype,"value",2);v([y({reflect:!0})],j.prototype,"href",2);v([y()],j.prototype,"target",2);v([y()],j.prototype,"rel",2);v([y()],j.prototype,"download",2);v([y({reflect:!0})],j.prototype,"form",2);v([y({attribute:"formaction"})],j.prototype,"formAction",2);v([y({attribute:"formenctype"})],j.prototype,"formEnctype",2);v([y({attribute:"formmethod"})],j.prototype,"formMethod",2);v([y({attribute:"formnovalidate",type:Boolean})],j.prototype,"formNoValidate",2);v([y({attribute:"formtarget"})],j.prototype,"formTarget",2);v([ye("disabled",{waitUntilFirstUpdate:!0})],j.prototype,"handleDisabledChange",1);j=v([Ae("wa-button")],j);/*! Copyright 2025 Fonticons, Inc. - https://webawesome.com/license */var yp=`:host {
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
`,ho=class extends me{constructor(){super(...arguments),this.localize=new Mt(this)}render(){return k`
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
    `}};ho.css=yp;ho=v([Ae("wa-spinner")],ho);/*! Copyright 2025 Fonticons, Inc. - https://webawesome.com/license */var wp=`:host {
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
`,we=class extends me{constructor(){super(...arguments),this.localize=new Mt(this),this.hasSlotController=new fi(this,"footer","header-actions","label"),this.open=!1,this.label="",this.placement="end",this.withoutHeader=!1,this.lightDismiss=!0,this.handleDocumentKeyDown=e=>{e.key==="Escape"&&this.open&&(e.preventDefault(),e.stopPropagation(),this.requestClose(this.drawer))}}firstUpdated(){this.open&&(this.addOpenListeners(),this.drawer.showModal(),Xs(this))}disconnectedCallback(){super.disconnectedCallback(),Qs(this),this.removeOpenListeners()}async requestClose(e){const t=new us({source:e});if(this.dispatchEvent(t),t.defaultPrevented){this.open=!0,de(this.drawer,"pulse");return}this.removeOpenListeners(),await de(this.drawer,"hide"),this.open=!1,this.drawer.close(),Qs(this);const i=this.originalTrigger;typeof i?.focus=="function"&&setTimeout(()=>i.focus()),this.dispatchEvent(new ds)}addOpenListeners(){document.addEventListener("keydown",this.handleDocumentKeyDown)}removeOpenListeners(){document.removeEventListener("keydown",this.handleDocumentKeyDown)}handleDialogCancel(e){e.preventDefault(),!this.drawer.classList.contains("hide")&&e.target===this.drawer&&this.requestClose(this.drawer)}handleDialogClick(e){const t=e.target.closest('[data-drawer="close"]');t&&(e.stopPropagation(),this.requestClose(t))}async handleDialogPointerDown(e){e.target===this.drawer&&(this.lightDismiss?this.requestClose(this.drawer):await de(this.drawer,"pulse"))}handleOpenChange(){this.open&&!this.drawer.open?this.show():this.drawer.open&&(this.open=!0,this.requestClose(this.drawer))}async show(){const e=new ps;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}this.addOpenListeners(),this.originalTrigger=document.activeElement,this.open=!0,this.drawer.showModal(),Xs(this),requestAnimationFrame(()=>{const t=this.querySelector("[autofocus]");t&&typeof t.focus=="function"?t.focus():this.drawer.focus()}),await de(this.drawer,"show"),this.dispatchEvent(new hs)}render(){const e=!this.withoutHeader,t=this.hasSlotController.test("footer");return k`
      <dialog
        part="dialog"
        class=${Re({drawer:!0,open:this.open,top:this.placement==="top",end:this.placement==="end",bottom:this.placement==="bottom",start:this.placement==="start"})}
        @cancel=${this.handleDialogCancel}
        @click=${this.handleDialogClick}
        @pointerdown=${this.handleDialogPointerDown}
      >
        ${e?k`
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
                    @click="${i=>this.requestClose(i.target)}"
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

        ${t?k`
              <footer part="footer" class="footer">
                <slot name="footer"></slot>
              </footer>
            `:""}
      </dialog>
    `}};we.css=wp;v([Q(".drawer")],we.prototype,"drawer",2);v([y({type:Boolean,reflect:!0})],we.prototype,"open",2);v([y({reflect:!0})],we.prototype,"label",2);v([y({reflect:!0})],we.prototype,"placement",2);v([y({attribute:"without-header",type:Boolean,reflect:!0})],we.prototype,"withoutHeader",2);v([y({attribute:"light-dismiss",type:Boolean})],we.prototype,"lightDismiss",2);v([ye("open",{waitUntilFirstUpdate:!0})],we.prototype,"handleOpenChange",1);we=v([Ae("wa-drawer")],we);document.addEventListener("click",s=>{const e=s.target.closest("[data-drawer]");if(e instanceof Element){const[t,i]=Ka(e.getAttribute("data-drawer")||"");if(t==="open"&&i?.length){const o=e.getRootNode().getElementById(i);o?.localName==="wa-drawer"?o.open=!0:console.warn(`A drawer with an ID of "${i}" could not be found in this document.`)}}});document.body.addEventListener("pointerdown",()=>{});let Ep=class extends we{static get styles(){return[we.styles,R`
        :host {
          --wa-color-surface-raised: var(--c-bg-raised);
          --spacing: var(--c-spacing-lg);
          background-color: red;
        }
      `]}};customElements.get("craft-drawer")||customElements.define("craft-drawer",Ep);/*! Copyright 2025 Fonticons, Inc. - https://webawesome.com/license */var kp=`:host {
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
`,$e=class extends me{constructor(){super(...arguments),this.localize=new Mt(this),this.hasSlotController=new fi(this,"footer","header-actions","label"),this.open=!1,this.label="",this.withoutHeader=!1,this.lightDismiss=!1,this.handleDocumentKeyDown=e=>{e.key==="Escape"&&this.open&&(e.preventDefault(),e.stopPropagation(),this.requestClose(this.dialog))}}firstUpdated(){this.open&&(this.addOpenListeners(),this.dialog.showModal(),Xs(this))}disconnectedCallback(){super.disconnectedCallback(),Qs(this),this.removeOpenListeners()}async requestClose(e){const t=new us({source:e});if(this.dispatchEvent(t),t.defaultPrevented){this.open=!0,de(this.dialog,"pulse");return}this.removeOpenListeners(),await de(this.dialog,"hide"),this.open=!1,this.dialog.close(),Qs(this);const i=this.originalTrigger;typeof i?.focus=="function"&&setTimeout(()=>i.focus()),this.dispatchEvent(new ds)}addOpenListeners(){document.addEventListener("keydown",this.handleDocumentKeyDown)}removeOpenListeners(){document.removeEventListener("keydown",this.handleDocumentKeyDown)}handleDialogCancel(e){e.preventDefault(),!this.dialog.classList.contains("hide")&&e.target===this.dialog&&this.requestClose(this.dialog)}handleDialogClick(e){const t=e.target.closest('[data-dialog="close"]');t&&(e.stopPropagation(),this.requestClose(t))}async handleDialogPointerDown(e){e.target===this.dialog&&(this.lightDismiss?this.requestClose(this.dialog):await de(this.dialog,"pulse"))}handleOpenChange(){this.open&&!this.dialog.open?this.show():!this.open&&this.dialog.open&&(this.open=!0,this.requestClose(this.dialog))}async show(){const e=new ps;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}this.addOpenListeners(),this.originalTrigger=document.activeElement,this.open=!0,this.dialog.showModal(),Xs(this),requestAnimationFrame(()=>{const t=this.querySelector("[autofocus]");t&&typeof t.focus=="function"?t.focus():this.dialog.focus()}),await de(this.dialog,"show"),this.dispatchEvent(new hs)}render(){const e=!this.withoutHeader,t=this.hasSlotController.test("footer");return k`
      <dialog
        part="dialog"
        class=${Re({dialog:!0,open:this.open})}
        @cancel=${this.handleDialogCancel}
        @click=${this.handleDialogClick}
        @pointerdown=${this.handleDialogPointerDown}
      >
        ${e?k`
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
                    @click="${i=>this.requestClose(i.target)}"
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

        ${t?k`
              <footer part="footer" class="footer">
                <slot name="footer"></slot>
              </footer>
            `:""}
      </dialog>
    `}};$e.css=kp;v([Q(".dialog")],$e.prototype,"dialog",2);v([y({type:Boolean,reflect:!0})],$e.prototype,"open",2);v([y({reflect:!0})],$e.prototype,"label",2);v([y({attribute:"without-header",type:Boolean,reflect:!0})],$e.prototype,"withoutHeader",2);v([y({attribute:"light-dismiss",type:Boolean})],$e.prototype,"lightDismiss",2);v([ye("open",{waitUntilFirstUpdate:!0})],$e.prototype,"handleOpenChange",1);$e=v([Ae("wa-dialog")],$e);document.addEventListener("click",s=>{const e=s.target.closest("[data-dialog]");if(e instanceof Element){const[t,i]=Ka(e.getAttribute("data-dialog")||"");if(t==="open"&&i?.length){const o=e.getRootNode().getElementById(i);o?.localName==="wa-dialog"?o.open=!0:console.warn(`A dialog with an ID of "${i}" could not be found in this document.`)}}});document.addEventListener("pointerdown",()=>{});let xp=class extends $e{static get styles(){return[$e.styles,R`
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
      `]}};customElements.get("craft-dialog")||customElements.define("craft-dialog",xp);let Cp=class extends EventTarget{constructor(e,t){super(),this.__param=e,this.__config=t||{},this.type=t?.type||"error"}static _$isValidator$=!0;static validatorName="";static async=!1;execute(e,t,i){if(!this.constructor.validatorName)throw new Error(`A validator needs to have a name! Please set it via "static get validatorName() { return 'IsCat'; }"`);return!0}set param(e){this.__param=e,this.dispatchEvent(new Event("param-changed"))}get param(){return this.__param}set config(e){this.__config=e,this.dispatchEvent(new Event("config-changed"))}get config(){return this.__config}async _getMessage(e){const t=this.constructor,i={name:t.validatorName,type:this.type,params:this.param,config:this.config,...e};if(this.config.getMessage){if(typeof this.config.getMessage=="function")return this.config.getMessage(i);throw new Error(`You must provide a value for getMessage of type 'function', you provided a value of type: ${typeof this.config.getMessage}`)}return t.getMessage(i)}static async getMessage(e){return`Please configure an error message for "${this.name}" by overriding "static async getMessage()"`}onFormControlConnect(e){}onFormControlDisconnect(e){}abortExecution(){}},$p=class extends Cp{static get validatorName(){return"FormElementsHaveNoError"}execute(e,t,i){return i?.node._anyFormElementHasFeedbackFor("error")}static async getMessage(){return""}};const Ap=s=>class extends Xo(gt(_s(gs(Qe(s))))){static get properties(){return{submitted:{type:Boolean,reflect:!0},focused:{type:Boolean,reflect:!0},dirty:{type:Boolean,reflect:!0},touched:{type:Boolean,reflect:!0},prefilled:{type:Boolean,reflect:!0}}}get _inputNode(){return this}get modelValue(){return this._getFromAllFormElements("modelValue")}set modelValue(e){this.__isInitialModelValue?(this.__isInitialModelValue=!1,this.registrationComplete.then(()=>{this._setValueMapForAllFormElements("modelValue",e)})):this._setValueMapForAllFormElements("modelValue",e)}get serializedValue(){return this._getFromAllFormElements("serializedValue")}set serializedValue(e){this.__isInitialSerializedValue?(this.__isInitialSerializedValue=!1,this.registrationComplete.then(()=>{this._setValueMapForAllFormElements("serializedValue",e)})):this._setValueMapForAllFormElements("serializedValue",e)}get formattedValue(){return this._getFromAllFormElements("formattedValue")}set formattedValue(e){this._setValueMapForAllFormElements("formattedValue",e)}get prefilled(){return this._everyFormElementHas("prefilled")}constructor(){super(),this.value="",this.disabled=!1,this.submitted=!1,this.dirty=!1,this.touched=!1,this.focused=!1,this.__addedSubValidators=!1,this.__isInitialModelValue=!0,this.__isInitialSerializedValue=!0,this._checkForOutsideClick=this._checkForOutsideClick.bind(this),this.addEventListener("focusin",this._syncFocused),this.addEventListener("focusout",this._onFocusOut),this.addEventListener("dirty-changed",this._syncDirty),this.addEventListener("validate-performed",this.__onChildValidatePerformed),this.defaultValidators=[new $p],this.__descriptionElementsInParentChain=new Set,this.__pendingValues={modelValue:{},serializedValue:{}}}connectedCallback(){super.connectedCallback(),this.setAttribute("role","group"),this.initComplete.then(()=>{this.__isInitialModelValue=!1,this.__isInitialSerializedValue=!1,this.__initInteractionStates()})}disconnectedCallback(){super.disconnectedCallback(),this.__hasActiveOutsideClickHandling&&(document.removeEventListener("click",this._checkForOutsideClick),this.__hasActiveOutsideClickHandling=!1),this.__descriptionElementsInParentChain.clear()}__initInteractionStates(){this.formElements.forEach(e=>{typeof e.initInteractionState=="function"&&e.initInteractionState()})}_triggerInitialModelValueChangedEvent(){this.registrationComplete.then(()=>{this._dispatchInitialModelValueChangedEvent()})}updated(e){super.updated(e),e.has("disabled")&&(this.disabled?this.__requestChildrenToBeDisabled():this.__retractRequestChildrenToBeDisabled()),e.has("focused")&&this.focused===!0&&this.__setupOutsideClickHandling()}__setupOutsideClickHandling(){this.__hasActiveOutsideClickHandling||(document.addEventListener("click",this._checkForOutsideClick),this.__hasActiveOutsideClickHandling=!0)}_checkForOutsideClick(e){!this.contains(e.target)&&(this.touched=!0)}__requestChildrenToBeDisabled(){this.formElements.forEach(e=>{e.makeRequestToBeDisabled&&e.makeRequestToBeDisabled()})}__retractRequestChildrenToBeDisabled(){this.formElements.forEach(e=>{e.retractRequestToBeDisabled&&e.retractRequestToBeDisabled()})}_inputGroupTemplate(){return k`
        <div class="input-group">
          <slot></slot>
        </div>
      `}submitGroup(){this.submitted=!0,this.formElements.forEach(e=>{typeof e.submitGroup=="function"?e.submitGroup():e.submitted=!0})}resetGroup(){this.formElements.forEach(e=>{typeof e.resetGroup=="function"?e.resetGroup():typeof e.reset=="function"&&e.reset()}),this.resetInteractionState()}clearGroup(){this.formElements.forEach(e=>{typeof e.clearGroup=="function"?e.clearGroup():typeof e.clear=="function"&&e.clear()}),this.resetInteractionState()}resetInteractionState(){this.submitted=!1,this.touched=!1,this.dirty=!1,this.formElements.forEach(e=>{typeof e.resetInteractionState=="function"&&e.resetInteractionState()})}_getFromAllFormElementsFilter(e,t){return!e.disabled}_getFromAllFormElements(e,t){const i={},o=t||this._getFromAllFormElementsFilter;return this.formElements._keys().forEach(r=>{const n=this.formElements[r];n instanceof no?i[r]=n.filter(a=>o(a,e)).map(a=>a[e]):o(n,e)&&(typeof n._getFromAllFormElements=="function"?i[r]=n._getFromAllFormElements(e):i[r]=n[e])}),i}_setValueForAllFormElements(e,t){this.formElements.forEach(i=>{i[e]=t})}_setValueMapForAllFormElements(e,t){t&&typeof t=="object"&&Object.keys(t).forEach(i=>{Array.isArray(this.formElements[i])&&this.formElements[i].forEach((o,r)=>{o[e]=t[i][r]}),this.formElements[i]?this.formElements[i][e]=t[i]:this.__pendingValues[e][i]=t[i]})}_anyFormElementHas(e){return Object.keys(this.formElements).some(t=>Array.isArray(this.formElements[t])?this.formElements[t].some(i=>!!i[e]):!!this.formElements[t][e])}_anyFormElementHasFeedbackFor(e){return Object.keys(this.formElements).some(t=>Array.isArray(this.formElements[t])?this.formElements[t].some(i=>!!(i.hasFeedbackFor&&i.hasFeedbackFor.includes(e))):!!(this.formElements[t].hasFeedbackFor&&this.formElements[t].hasFeedbackFor.includes(e)))}_everyFormElementHas(e){return Object.keys(this.formElements).every(t=>Array.isArray(this.formElements[t])?this.formElements[t].every(i=>!!i[e]):!!this.formElements[t][e])}__onChildValidatePerformed(e){e&&this.isRegisteredFormElement(e.target)&&this.validate()}_syncFocused(){this.focused=this._anyFormElementHas("focused")}_onFocusOut(e){const t=this.formElements[this.formElements.length-1];e.target===t&&(this.touched=!0),this.focused=!1}_syncDirty(){this.dirty=this._anyFormElementHas("dirty")}__storeAllDescriptionElementsInParentChain(){let e=this;for(;e;){const t=e._getAriaDescriptionElements();_a(t,{reverse:!0}).forEach(i=>{i.getAttribute("slot")==="feedback"&&this.__descriptionElementsInParentChain.add(i)}),e=e._parentFormGroup}}__linkParentMessages(e){this.__descriptionElementsInParentChain.forEach(t=>{typeof e.addToAriaDescribedBy=="function"&&e.addToAriaDescribedBy(t,{reorder:!1})})}__unlinkParentMessages(e){this.__descriptionElementsInParentChain.forEach(t=>{typeof e.removeFromAriaDescribedBy=="function"&&e.removeFromAriaDescribedBy(t)})}addFormElement(e,t){if(super.addFormElement(e,t),this.disabled&&e.makeRequestToBeDisabled(),this.__descriptionElementsInParentChain.size||this.__storeAllDescriptionElementsInParentChain(),this.__linkParentMessages(e),this.validate({clearCurrentResult:!0}),!e.modelValue){const i=this.__pendingValues;i.modelValue&&i.modelValue[e.name]?e.modelValue=i.modelValue[e.name]:i.serializedValue&&i.serializedValue[e.name]&&(e.serializedValue=i.serializedValue[e.name])}}get _initialModelValue(){return this._getFromAllFormElements("_initialModelValue")}removeFormElement(e){super.removeFormElement(e),this.validate({clearCurrentResult:!0}),typeof e.removeFromAriaLabelledBy=="function"&&this._labelNode&&e.removeFromAriaLabelledBy(this._labelNode,{reorder:!1}),this.__unlinkParentMessages(e)}_isEmpty(){return this.formElements.every(e=>e._isEmpty?.())}},Sp=te(Ap);let jr=class extends Sa(Sp(G)){constructor(){super(),this.multipleChoice=!0}},Np=class extends jr{static get styles(){return[...jr.styles,R`
        .form-field__group-two {
          margin-top: var(--c-spacing-sm);
        }

        ::slotted(label) {
          font-weight: bold;
        }
      `]}};customElements.get("craft-checkbox-group")||customElements.define("craft-checkbox-group",Np);let qr=class extends Jo(Go){connectedCallback(){super.connectedCallback(),this.type="checkbox"}},Tp=class extends qr{static get styles(){return[...qr.styles,R`
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
      `]}};customElements.get("craft-checkbox")||customElements.define("craft-checkbox",Tp);function Ya(s,e){return function(){return s.apply(e,arguments)}}const{toString:Op}=Object.prototype,{getPrototypeOf:Qo}=Object,{iterator:mi,toStringTag:Ja}=Symbol,bi=(s=>e=>{const t=Op.call(e);return s[t]||(s[t]=t.slice(8,-1).toLowerCase())})(Object.create(null)),Se=s=>(s=s.toLowerCase(),e=>bi(e)===s),gi=s=>e=>typeof e===s,{isArray:Bt}=Array,Lt=gi("undefined");function ys(s){return s!==null&&!Lt(s)&&s.constructor!==null&&!Lt(s.constructor)&&pe(s.constructor.isBuffer)&&s.constructor.isBuffer(s)}const Xa=Se("ArrayBuffer");function Lp(s){let e;return typeof ArrayBuffer<"u"&&ArrayBuffer.isView?e=ArrayBuffer.isView(s):e=s&&s.buffer&&Xa(s.buffer),e}const Rp=gi("string"),pe=gi("function"),Qa=gi("number"),ws=s=>s!==null&&typeof s=="object",Fp=s=>s===!0||s===!1,Ds=s=>{if(bi(s)!=="object")return!1;const e=Qo(s);return(e===null||e===Object.prototype||Object.getPrototypeOf(e)===null)&&!(Ja in s)&&!(mi in s)},Pp=s=>{if(!ws(s)||ys(s))return!1;try{return Object.keys(s).length===0&&Object.getPrototypeOf(s)===Object.prototype}catch{return!1}},Mp=Se("Date"),Ip=Se("File"),Dp=Se("Blob"),Vp=Se("FileList"),zp=s=>ws(s)&&pe(s.pipe),Bp=s=>{let e;return s&&(typeof FormData=="function"&&s instanceof FormData||pe(s.append)&&((e=bi(s))==="formdata"||e==="object"&&pe(s.toString)&&s.toString()==="[object FormData]"))},Up=Se("URLSearchParams"),[Hp,jp,qp,Wp]=["ReadableStream","Request","Response","Headers"].map(Se),Kp=s=>s.trim?s.trim():s.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g,"");function Es(s,e,{allOwnKeys:t=!1}={}){if(s===null||typeof s>"u")return;let i,o;if(typeof s!="object"&&(s=[s]),Bt(s))for(i=0,o=s.length;i<o;i++)e.call(null,s[i],i,s);else{if(ys(s))return;const r=t?Object.getOwnPropertyNames(s):Object.keys(s),n=r.length;let a;for(i=0;i<n;i++)a=r[i],e.call(null,s[a],a,s)}}function el(s,e){if(ys(s))return null;e=e.toLowerCase();const t=Object.keys(s);let i=t.length,o;for(;i-- >0;)if(o=t[i],e===o.toLowerCase())return o;return null}const lt=typeof globalThis<"u"?globalThis:typeof self<"u"?self:typeof window<"u"?window:global,tl=s=>!Lt(s)&&s!==lt;function uo(){const{caseless:s,skipUndefined:e}=tl(this)&&this||{},t={},i=(o,r)=>{const n=s&&el(t,r)||r;Ds(t[n])&&Ds(o)?t[n]=uo(t[n],o):Ds(o)?t[n]=uo({},o):Bt(o)?t[n]=o.slice():(!e||!Lt(o))&&(t[n]=o)};for(let o=0,r=arguments.length;o<r;o++)arguments[o]&&Es(arguments[o],i);return t}const Gp=(s,e,t,{allOwnKeys:i}={})=>(Es(e,(o,r)=>{t&&pe(o)?s[r]=Ya(o,t):s[r]=o},{allOwnKeys:i}),s),Zp=s=>(s.charCodeAt(0)===65279&&(s=s.slice(1)),s),Yp=(s,e,t,i)=>{s.prototype=Object.create(e.prototype,i),s.prototype.constructor=s,Object.defineProperty(s,"super",{value:e.prototype}),t&&Object.assign(s.prototype,t)},Jp=(s,e,t,i)=>{let o,r,n;const a={};if(e=e||{},s==null)return e;do{for(o=Object.getOwnPropertyNames(s),r=o.length;r-- >0;)n=o[r],(!i||i(n,s,e))&&!a[n]&&(e[n]=s[n],a[n]=!0);s=t!==!1&&Qo(s)}while(s&&(!t||t(s,e))&&s!==Object.prototype);return e},Xp=(s,e,t)=>{s=String(s),(t===void 0||t>s.length)&&(t=s.length),t-=e.length;const i=s.indexOf(e,t);return i!==-1&&i===t},Qp=s=>{if(!s)return null;if(Bt(s))return s;let e=s.length;if(!Qa(e))return null;const t=new Array(e);for(;e-- >0;)t[e]=s[e];return t},ef=(s=>e=>s&&e instanceof s)(typeof Uint8Array<"u"&&Qo(Uint8Array)),tf=(s,e)=>{const t=(s&&s[mi]).call(s);let i;for(;(i=t.next())&&!i.done;){const o=i.value;e.call(s,o[0],o[1])}},sf=(s,e)=>{let t;const i=[];for(;(t=s.exec(e))!==null;)i.push(t);return i},of=Se("HTMLFormElement"),rf=s=>s.toLowerCase().replace(/[-_\s]([a-z\d])(\w*)/g,function(e,t,i){return t.toUpperCase()+i}),Wr=(({hasOwnProperty:s})=>(e,t)=>s.call(e,t))(Object.prototype),nf=Se("RegExp"),sl=(s,e)=>{const t=Object.getOwnPropertyDescriptors(s),i={};Es(t,(o,r)=>{let n;(n=e(o,r,s))!==!1&&(i[r]=n||o)}),Object.defineProperties(s,i)},af=s=>{sl(s,(e,t)=>{if(pe(s)&&["arguments","caller","callee"].indexOf(t)!==-1)return!1;const i=s[t];if(pe(i)){if(e.enumerable=!1,"writable"in e){e.writable=!1;return}e.set||(e.set=()=>{throw Error("Can not rewrite read-only method '"+t+"'")})}})},lf=(s,e)=>{const t={},i=o=>{o.forEach(r=>{t[r]=!0})};return Bt(s)?i(s):i(String(s).split(e)),t},cf=()=>{},df=(s,e)=>s!=null&&Number.isFinite(s=+s)?s:e;function hf(s){return!!(s&&pe(s.append)&&s[Ja]==="FormData"&&s[mi])}const uf=s=>{const e=new Array(10),t=(i,o)=>{if(ws(i)){if(e.indexOf(i)>=0)return;if(ys(i))return i;if(!("toJSON"in i)){e[o]=i;const r=Bt(i)?[]:{};return Es(i,(n,a)=>{const l=t(n,o+1);!Lt(l)&&(r[a]=l)}),e[o]=void 0,r}}return i};return t(s,0)},pf=Se("AsyncFunction"),ff=s=>s&&(ws(s)||pe(s))&&pe(s.then)&&pe(s.catch),il=((s,e)=>s?setImmediate:e?((t,i)=>(lt.addEventListener("message",({source:o,data:r})=>{o===lt&&r===t&&i.length&&i.shift()()},!1),o=>{i.push(o),lt.postMessage(t,"*")}))(`axios@${Math.random()}`,[]):t=>setTimeout(t))(typeof setImmediate=="function",pe(lt.postMessage)),mf=typeof queueMicrotask<"u"?queueMicrotask.bind(lt):typeof process<"u"&&process.nextTick||il,bf=s=>s!=null&&pe(s[mi]),_={isArray:Bt,isArrayBuffer:Xa,isBuffer:ys,isFormData:Bp,isArrayBufferView:Lp,isString:Rp,isNumber:Qa,isBoolean:Fp,isObject:ws,isPlainObject:Ds,isEmptyObject:Pp,isReadableStream:Hp,isRequest:jp,isResponse:qp,isHeaders:Wp,isUndefined:Lt,isDate:Mp,isFile:Ip,isBlob:Dp,isRegExp:nf,isFunction:pe,isStream:zp,isURLSearchParams:Up,isTypedArray:ef,isFileList:Vp,forEach:Es,merge:uo,extend:Gp,trim:Kp,stripBOM:Zp,inherits:Yp,toFlatObject:Jp,kindOf:bi,kindOfTest:Se,endsWith:Xp,toArray:Qp,forEachEntry:tf,matchAll:sf,isHTMLForm:of,hasOwnProperty:Wr,hasOwnProp:Wr,reduceDescriptors:sl,freezeMethods:af,toObjectSet:lf,toCamelCase:rf,noop:cf,toFiniteNumber:df,findKey:el,global:lt,isContextDefined:tl,isSpecCompliantForm:hf,toJSONObject:uf,isAsyncFn:pf,isThenable:ff,setImmediate:il,asap:mf,isIterable:bf};function P(s,e,t,i,o){Error.call(this),Error.captureStackTrace?Error.captureStackTrace(this,this.constructor):this.stack=new Error().stack,this.message=s,this.name="AxiosError",e&&(this.code=e),t&&(this.config=t),i&&(this.request=i),o&&(this.response=o,this.status=o.status?o.status:null)}_.inherits(P,Error,{toJSON:function(){return{message:this.message,name:this.name,description:this.description,number:this.number,fileName:this.fileName,lineNumber:this.lineNumber,columnNumber:this.columnNumber,stack:this.stack,config:_.toJSONObject(this.config),code:this.code,status:this.status}}});const ol=P.prototype,rl={};["ERR_BAD_OPTION_VALUE","ERR_BAD_OPTION","ECONNABORTED","ETIMEDOUT","ERR_NETWORK","ERR_FR_TOO_MANY_REDIRECTS","ERR_DEPRECATED","ERR_BAD_RESPONSE","ERR_BAD_REQUEST","ERR_CANCELED","ERR_NOT_SUPPORT","ERR_INVALID_URL"].forEach(s=>{rl[s]={value:s}});Object.defineProperties(P,rl);Object.defineProperty(ol,"isAxiosError",{value:!0});P.from=(s,e,t,i,o,r)=>{const n=Object.create(ol);_.toFlatObject(s,n,function(c){return c!==Error.prototype},c=>c!=="isAxiosError");const a=s&&s.message?s.message:"Error",l=e==null&&s?s.code:e;return P.call(n,a,l,t,i,o),s&&n.cause==null&&Object.defineProperty(n,"cause",{value:s,configurable:!0}),n.name=s&&s.name||"Error",r&&Object.assign(n,r),n};const gf=null;function po(s){return _.isPlainObject(s)||_.isArray(s)}function nl(s){return _.endsWith(s,"[]")?s.slice(0,-2):s}function Kr(s,e,t){return s?s.concat(e).map(function(i,o){return i=nl(i),!t&&o?"["+i+"]":i}).join(t?".":""):e}function vf(s){return _.isArray(s)&&!s.some(po)}const _f=_.toFlatObject(_,{},null,function(s){return/^is[A-Z]/.test(s)});function vi(s,e,t){if(!_.isObject(s))throw new TypeError("target must be an object");e=e||new FormData,t=_.toFlatObject(t,{metaTokens:!0,dots:!1,indexes:!1},!1,function(p,m){return!_.isUndefined(m[p])});const i=t.metaTokens,o=t.visitor||c,r=t.dots,n=t.indexes,a=(t.Blob||typeof Blob<"u"&&Blob)&&_.isSpecCompliantForm(e);if(!_.isFunction(o))throw new TypeError("visitor must be a function");function l(p){if(p===null)return"";if(_.isDate(p))return p.toISOString();if(_.isBoolean(p))return p.toString();if(!a&&_.isBlob(p))throw new P("Blob is not supported. Use a Buffer instead.");return _.isArrayBuffer(p)||_.isTypedArray(p)?a&&typeof Blob=="function"?new Blob([p]):Buffer.from(p):p}function c(p,m,g){let w=p;if(p&&!g&&typeof p=="object"){if(_.endsWith(m,"{}"))m=i?m:m.slice(0,-2),p=JSON.stringify(p);else if(_.isArray(p)&&vf(p)||(_.isFileList(p)||_.endsWith(m,"[]"))&&(w=_.toArray(p)))return m=nl(m),w.forEach(function(E,x){!(_.isUndefined(E)||E===null)&&e.append(n===!0?Kr([m],x,r):n===null?m:m+"[]",l(E))}),!1}return po(p)?!0:(e.append(Kr(g,m,r),l(p)),!1)}const d=[],u=Object.assign(_f,{defaultVisitor:c,convertValue:l,isVisitable:po});function b(p,m){if(!_.isUndefined(p)){if(d.indexOf(p)!==-1)throw Error("Circular reference detected in "+m.join("."));d.push(p),_.forEach(p,function(g,w){(!(_.isUndefined(g)||g===null)&&o.call(e,g,_.isString(w)?w.trim():w,m,u))===!0&&b(g,m?m.concat(w):[w])}),d.pop()}}if(!_.isObject(s))throw new TypeError("data must be an object");return b(s),e}function Gr(s){const e={"!":"%21","'":"%27","(":"%28",")":"%29","~":"%7E","%20":"+","%00":"\0"};return encodeURIComponent(s).replace(/[!'()~]|%20|%00/g,function(t){return e[t]})}function er(s,e){this._pairs=[],s&&vi(s,this,e)}const al=er.prototype;al.append=function(s,e){this._pairs.push([s,e])};al.toString=function(s){const e=s?function(t){return s.call(this,t,Gr)}:Gr;return this._pairs.map(function(t){return e(t[0])+"="+e(t[1])},"").join("&")};function yf(s){return encodeURIComponent(s).replace(/%3A/gi,":").replace(/%24/g,"$").replace(/%2C/gi,",").replace(/%20/g,"+")}function ll(s,e,t){if(!e)return s;const i=t&&t.encode||yf;_.isFunction(t)&&(t={serialize:t});const o=t&&t.serialize;let r;if(o?r=o(e,t):r=_.isURLSearchParams(e)?e.toString():new er(e,t).toString(i),r){const n=s.indexOf("#");n!==-1&&(s=s.slice(0,n)),s+=(s.indexOf("?")===-1?"?":"&")+r}return s}class Zr{constructor(){this.handlers=[]}use(e,t,i){return this.handlers.push({fulfilled:e,rejected:t,synchronous:i?i.synchronous:!1,runWhen:i?i.runWhen:null}),this.handlers.length-1}eject(e){this.handlers[e]&&(this.handlers[e]=null)}clear(){this.handlers&&(this.handlers=[])}forEach(e){_.forEach(this.handlers,function(t){t!==null&&e(t)})}}const cl={silentJSONParsing:!0,forcedJSONParsing:!0,clarifyTimeoutError:!1},wf=typeof URLSearchParams<"u"?URLSearchParams:er,Ef=typeof FormData<"u"?FormData:null,kf=typeof Blob<"u"?Blob:null,xf={isBrowser:!0,classes:{URLSearchParams:wf,FormData:Ef,Blob:kf},protocols:["http","https","file","blob","url","data"]},tr=typeof window<"u"&&typeof document<"u",fo=typeof navigator=="object"&&navigator||void 0,Cf=tr&&(!fo||["ReactNative","NativeScript","NS"].indexOf(fo.product)<0),$f=typeof WorkerGlobalScope<"u"&&self instanceof WorkerGlobalScope&&typeof self.importScripts=="function",Af=tr&&window.location.href||"http://localhost",Sf=Object.freeze(Object.defineProperty({__proto__:null,hasBrowserEnv:tr,hasStandardBrowserEnv:Cf,hasStandardBrowserWebWorkerEnv:$f,navigator:fo,origin:Af},Symbol.toStringTag,{value:"Module"})),ce={...Sf,...xf};function Nf(s,e){return vi(s,new ce.classes.URLSearchParams,{visitor:function(t,i,o,r){return ce.isNode&&_.isBuffer(t)?(this.append(i,t.toString("base64")),!1):r.defaultVisitor.apply(this,arguments)},...e})}function Tf(s){return _.matchAll(/\w+|\[(\w*)]/g,s).map(e=>e[0]==="[]"?"":e[1]||e[0])}function Of(s){const e={},t=Object.keys(s);let i;const o=t.length;let r;for(i=0;i<o;i++)r=t[i],e[r]=s[r];return e}function dl(s){function e(t,i,o,r){let n=t[r++];if(n==="__proto__")return!0;const a=Number.isFinite(+n),l=r>=t.length;return n=!n&&_.isArray(o)?o.length:n,l?(_.hasOwnProp(o,n)?o[n]=[o[n],i]:o[n]=i,!a):((!o[n]||!_.isObject(o[n]))&&(o[n]=[]),e(t,i,o[n],r)&&_.isArray(o[n])&&(o[n]=Of(o[n])),!a)}if(_.isFormData(s)&&_.isFunction(s.entries)){const t={};return _.forEachEntry(s,(i,o)=>{e(Tf(i),o,t,0)}),t}return null}function Lf(s,e,t){if(_.isString(s))try{return(e||JSON.parse)(s),_.trim(s)}catch(i){if(i.name!=="SyntaxError")throw i}return(t||JSON.stringify)(s)}const ks={transitional:cl,adapter:["xhr","http","fetch"],transformRequest:[function(s,e){const t=e.getContentType()||"",i=t.indexOf("application/json")>-1,o=_.isObject(s);if(o&&_.isHTMLForm(s)&&(s=new FormData(s)),_.isFormData(s))return i?JSON.stringify(dl(s)):s;if(_.isArrayBuffer(s)||_.isBuffer(s)||_.isStream(s)||_.isFile(s)||_.isBlob(s)||_.isReadableStream(s))return s;if(_.isArrayBufferView(s))return s.buffer;if(_.isURLSearchParams(s))return e.setContentType("application/x-www-form-urlencoded;charset=utf-8",!1),s.toString();let r;if(o){if(t.indexOf("application/x-www-form-urlencoded")>-1)return Nf(s,this.formSerializer).toString();if((r=_.isFileList(s))||t.indexOf("multipart/form-data")>-1){const n=this.env&&this.env.FormData;return vi(r?{"files[]":s}:s,n&&new n,this.formSerializer)}}return o||i?(e.setContentType("application/json",!1),Lf(s)):s}],transformResponse:[function(s){const e=this.transitional||ks.transitional,t=e&&e.forcedJSONParsing,i=this.responseType==="json";if(_.isResponse(s)||_.isReadableStream(s))return s;if(s&&_.isString(s)&&(t&&!this.responseType||i)){const o=!(e&&e.silentJSONParsing)&&i;try{return JSON.parse(s,this.parseReviver)}catch(r){if(o)throw r.name==="SyntaxError"?P.from(r,P.ERR_BAD_RESPONSE,this,null,this.response):r}}return s}],timeout:0,xsrfCookieName:"XSRF-TOKEN",xsrfHeaderName:"X-XSRF-TOKEN",maxContentLength:-1,maxBodyLength:-1,env:{FormData:ce.classes.FormData,Blob:ce.classes.Blob},validateStatus:function(s){return s>=200&&s<300},headers:{common:{Accept:"application/json, text/plain, */*","Content-Type":void 0}}};_.forEach(["delete","get","head","post","put","patch"],s=>{ks.headers[s]={}});const Rf=_.toObjectSet(["age","authorization","content-length","content-type","etag","expires","from","host","if-modified-since","if-unmodified-since","last-modified","location","max-forwards","proxy-authorization","referer","retry-after","user-agent"]),Ff=s=>{const e={};let t,i,o;return s&&s.split(`
`).forEach(function(r){o=r.indexOf(":"),t=r.substring(0,o).trim().toLowerCase(),i=r.substring(o+1).trim(),!(!t||e[t]&&Rf[t])&&(t==="set-cookie"?e[t]?e[t].push(i):e[t]=[i]:e[t]=e[t]?e[t]+", "+i:i)}),e},Yr=Symbol("internals");function qt(s){return s&&String(s).trim().toLowerCase()}function Vs(s){return s===!1||s==null?s:_.isArray(s)?s.map(Vs):String(s)}function Pf(s){const e=Object.create(null),t=/([^\s,;=]+)\s*(?:=\s*([^,;]+))?/g;let i;for(;i=t.exec(s);)e[i[1]]=i[2];return e}const Mf=s=>/^[-_a-zA-Z0-9^`|~,!#$%&'*+.]+$/.test(s.trim());function Ui(s,e,t,i,o){if(_.isFunction(i))return i.call(this,e,t);if(o&&(e=t),!!_.isString(e)){if(_.isString(i))return e.indexOf(i)!==-1;if(_.isRegExp(i))return i.test(e)}}function If(s){return s.trim().toLowerCase().replace(/([a-z\d])(\w*)/g,(e,t,i)=>t.toUpperCase()+i)}function Df(s,e){const t=_.toCamelCase(" "+e);["get","set","has"].forEach(i=>{Object.defineProperty(s,i+t,{value:function(o,r,n){return this[i].call(this,e,o,r,n)},configurable:!0})})}let fe=class{constructor(e){e&&this.set(e)}set(e,t,i){const o=this;function r(a,l,c){const d=qt(l);if(!d)throw new Error("header name must be a non-empty string");const u=_.findKey(o,d);(!u||o[u]===void 0||c===!0||c===void 0&&o[u]!==!1)&&(o[u||l]=Vs(a))}const n=(a,l)=>_.forEach(a,(c,d)=>r(c,d,l));if(_.isPlainObject(e)||e instanceof this.constructor)n(e,t);else if(_.isString(e)&&(e=e.trim())&&!Mf(e))n(Ff(e),t);else if(_.isObject(e)&&_.isIterable(e)){let a={},l,c;for(const d of e){if(!_.isArray(d))throw TypeError("Object iterator must return a key-value pair");a[c=d[0]]=(l=a[c])?_.isArray(l)?[...l,d[1]]:[l,d[1]]:d[1]}n(a,t)}else e!=null&&r(t,e,i);return this}get(e,t){if(e=qt(e),e){const i=_.findKey(this,e);if(i){const o=this[i];if(!t)return o;if(t===!0)return Pf(o);if(_.isFunction(t))return t.call(this,o,i);if(_.isRegExp(t))return t.exec(o);throw new TypeError("parser must be boolean|regexp|function")}}}has(e,t){if(e=qt(e),e){const i=_.findKey(this,e);return!!(i&&this[i]!==void 0&&(!t||Ui(this,this[i],i,t)))}return!1}delete(e,t){const i=this;let o=!1;function r(n){if(n=qt(n),n){const a=_.findKey(i,n);a&&(!t||Ui(i,i[a],a,t))&&(delete i[a],o=!0)}}return _.isArray(e)?e.forEach(r):r(e),o}clear(e){const t=Object.keys(this);let i=t.length,o=!1;for(;i--;){const r=t[i];(!e||Ui(this,this[r],r,e,!0))&&(delete this[r],o=!0)}return o}normalize(e){const t=this,i={};return _.forEach(this,(o,r)=>{const n=_.findKey(i,r);if(n){t[n]=Vs(o),delete t[r];return}const a=e?If(r):String(r).trim();a!==r&&delete t[r],t[a]=Vs(o),i[a]=!0}),this}concat(...e){return this.constructor.concat(this,...e)}toJSON(e){const t=Object.create(null);return _.forEach(this,(i,o)=>{i!=null&&i!==!1&&(t[o]=e&&_.isArray(i)?i.join(", "):i)}),t}[Symbol.iterator](){return Object.entries(this.toJSON())[Symbol.iterator]()}toString(){return Object.entries(this.toJSON()).map(([e,t])=>e+": "+t).join(`
`)}getSetCookie(){return this.get("set-cookie")||[]}get[Symbol.toStringTag](){return"AxiosHeaders"}static from(e){return e instanceof this?e:new this(e)}static concat(e,...t){const i=new this(e);return t.forEach(o=>i.set(o)),i}static accessor(e){const t=(this[Yr]=this[Yr]={accessors:{}}).accessors,i=this.prototype;function o(r){const n=qt(r);t[n]||(Df(i,r),t[n]=!0)}return _.isArray(e)?e.forEach(o):o(e),this}};fe.accessor(["Content-Type","Content-Length","Accept","Accept-Encoding","User-Agent","Authorization"]);_.reduceDescriptors(fe.prototype,({value:s},e)=>{let t=e[0].toUpperCase()+e.slice(1);return{get:()=>s,set(i){this[t]=i}}});_.freezeMethods(fe);function Hi(s,e){const t=this||ks,i=e||t,o=fe.from(i.headers);let r=i.data;return _.forEach(s,function(n){r=n.call(t,r,o.normalize(),e?e.status:void 0)}),o.normalize(),r}function hl(s){return!!(s&&s.__CANCEL__)}function Ut(s,e,t){P.call(this,s??"canceled",P.ERR_CANCELED,e,t),this.name="CanceledError"}_.inherits(Ut,P,{__CANCEL__:!0});function ul(s,e,t){const i=t.config.validateStatus;!t.status||!i||i(t.status)?s(t):e(new P("Request failed with status code "+t.status,[P.ERR_BAD_REQUEST,P.ERR_BAD_RESPONSE][Math.floor(t.status/100)-4],t.config,t.request,t))}function Vf(s){const e=/^([-+\w]{1,25})(:?\/\/|:)/.exec(s);return e&&e[1]||""}function zf(s,e){s=s||10;const t=new Array(s),i=new Array(s);let o=0,r=0,n;return e=e!==void 0?e:1e3,function(a){const l=Date.now(),c=i[r];n||(n=l),t[o]=a,i[o]=l;let d=r,u=0;for(;d!==o;)u+=t[d++],d=d%s;if(o=(o+1)%s,o===r&&(r=(r+1)%s),l-n<e)return;const b=c&&l-c;return b?Math.round(u*1e3/b):void 0}}function Bf(s,e){let t=0,i=1e3/e,o,r;const n=(a,l=Date.now())=>{t=l,o=null,r&&(clearTimeout(r),r=null),s(...a)};return[(...a)=>{const l=Date.now(),c=l-t;c>=i?n(a,l):(o=a,r||(r=setTimeout(()=>{r=null,n(o)},i-c)))},()=>o&&n(o)]}const ei=(s,e,t=3)=>{let i=0;const o=zf(50,250);return Bf(r=>{const n=r.loaded,a=r.lengthComputable?r.total:void 0,l=n-i,c=o(l),d=n<=a;i=n;const u={loaded:n,total:a,progress:a?n/a:void 0,bytes:l,rate:c||void 0,estimated:c&&a&&d?(a-n)/c:void 0,event:r,lengthComputable:a!=null,[e?"download":"upload"]:!0};s(u)},t)},Jr=(s,e)=>{const t=s!=null;return[i=>e[0]({lengthComputable:t,total:s,loaded:i}),e[1]]},Xr=s=>(...e)=>_.asap(()=>s(...e)),Uf=ce.hasStandardBrowserEnv?((s,e)=>t=>(t=new URL(t,ce.origin),s.protocol===t.protocol&&s.host===t.host&&(e||s.port===t.port)))(new URL(ce.origin),ce.navigator&&/(msie|trident)/i.test(ce.navigator.userAgent)):()=>!0,Hf=ce.hasStandardBrowserEnv?{write(s,e,t,i,o,r,n){if(typeof document>"u")return;const a=[`${s}=${encodeURIComponent(e)}`];_.isNumber(t)&&a.push(`expires=${new Date(t).toUTCString()}`),_.isString(i)&&a.push(`path=${i}`),_.isString(o)&&a.push(`domain=${o}`),r===!0&&a.push("secure"),_.isString(n)&&a.push(`SameSite=${n}`),document.cookie=a.join("; ")},read(s){if(typeof document>"u")return null;const e=document.cookie.match(new RegExp("(?:^|; )"+s+"=([^;]*)"));return e?decodeURIComponent(e[1]):null},remove(s){this.write(s,"",Date.now()-864e5,"/")}}:{write(){},read(){return null},remove(){}};function jf(s){return/^([a-z][a-z\d+\-.]*:)?\/\//i.test(s)}function qf(s,e){return e?s.replace(/\/?\/$/,"")+"/"+e.replace(/^\/+/,""):s}function pl(s,e,t){let i=!jf(e);return s&&(i||t==!1)?qf(s,e):e}const Qr=s=>s instanceof fe?{...s}:s;function mt(s,e){e=e||{};const t={};function i(c,d,u,b){return _.isPlainObject(c)&&_.isPlainObject(d)?_.merge.call({caseless:b},c,d):_.isPlainObject(d)?_.merge({},d):_.isArray(d)?d.slice():d}function o(c,d,u,b){if(_.isUndefined(d)){if(!_.isUndefined(c))return i(void 0,c,u,b)}else return i(c,d,u,b)}function r(c,d){if(!_.isUndefined(d))return i(void 0,d)}function n(c,d){if(_.isUndefined(d)){if(!_.isUndefined(c))return i(void 0,c)}else return i(void 0,d)}function a(c,d,u){if(u in e)return i(c,d);if(u in s)return i(void 0,c)}const l={url:r,method:r,data:r,baseURL:n,transformRequest:n,transformResponse:n,paramsSerializer:n,timeout:n,timeoutMessage:n,withCredentials:n,withXSRFToken:n,adapter:n,responseType:n,xsrfCookieName:n,xsrfHeaderName:n,onUploadProgress:n,onDownloadProgress:n,decompress:n,maxContentLength:n,maxBodyLength:n,beforeRedirect:n,transport:n,httpAgent:n,httpsAgent:n,cancelToken:n,socketPath:n,responseEncoding:n,validateStatus:a,headers:(c,d,u)=>o(Qr(c),Qr(d),u,!0)};return _.forEach(Object.keys({...s,...e}),function(c){const d=l[c]||o,u=d(s[c],e[c],c);_.isUndefined(u)&&d!==a||(t[c]=u)}),t}const fl=s=>{const e=mt({},s);let{data:t,withXSRFToken:i,xsrfHeaderName:o,xsrfCookieName:r,headers:n,auth:a}=e;if(e.headers=n=fe.from(n),e.url=ll(pl(e.baseURL,e.url,e.allowAbsoluteUrls),s.params,s.paramsSerializer),a&&n.set("Authorization","Basic "+btoa((a.username||"")+":"+(a.password?unescape(encodeURIComponent(a.password)):""))),_.isFormData(t)){if(ce.hasStandardBrowserEnv||ce.hasStandardBrowserWebWorkerEnv)n.setContentType(void 0);else if(_.isFunction(t.getHeaders)){const l=t.getHeaders(),c=["content-type","content-length"];Object.entries(l).forEach(([d,u])=>{c.includes(d.toLowerCase())&&n.set(d,u)})}}if(ce.hasStandardBrowserEnv&&(i&&_.isFunction(i)&&(i=i(e)),i||i!==!1&&Uf(e.url))){const l=o&&r&&Hf.read(r);l&&n.set(o,l)}return e},Wf=typeof XMLHttpRequest<"u",Kf=Wf&&function(s){return new Promise(function(e,t){const i=fl(s);let o=i.data;const r=fe.from(i.headers).normalize();let{responseType:n,onUploadProgress:a,onDownloadProgress:l}=i,c,d,u,b,p;function m(){b&&b(),p&&p(),i.cancelToken&&i.cancelToken.unsubscribe(c),i.signal&&i.signal.removeEventListener("abort",c)}let g=new XMLHttpRequest;g.open(i.method.toUpperCase(),i.url,!0),g.timeout=i.timeout;function w(){if(!g)return;const x=fe.from("getAllResponseHeaders"in g&&g.getAllResponseHeaders()),A={data:!n||n==="text"||n==="json"?g.responseText:g.response,status:g.status,statusText:g.statusText,headers:x,config:s,request:g};ul(function($){e($),m()},function($){t($),m()},A),g=null}"onloadend"in g?g.onloadend=w:g.onreadystatechange=function(){!g||g.readyState!==4||g.status===0&&!(g.responseURL&&g.responseURL.indexOf("file:")===0)||setTimeout(w)},g.onabort=function(){g&&(t(new P("Request aborted",P.ECONNABORTED,s,g)),g=null)},g.onerror=function(x){const A=x&&x.message?x.message:"Network Error",$=new P(A,P.ERR_NETWORK,s,g);$.event=x||null,t($),g=null},g.ontimeout=function(){let x=i.timeout?"timeout of "+i.timeout+"ms exceeded":"timeout exceeded";const A=i.transitional||cl;i.timeoutErrorMessage&&(x=i.timeoutErrorMessage),t(new P(x,A.clarifyTimeoutError?P.ETIMEDOUT:P.ECONNABORTED,s,g)),g=null},o===void 0&&r.setContentType(null),"setRequestHeader"in g&&_.forEach(r.toJSON(),function(x,A){g.setRequestHeader(A,x)}),_.isUndefined(i.withCredentials)||(g.withCredentials=!!i.withCredentials),n&&n!=="json"&&(g.responseType=i.responseType),l&&([u,p]=ei(l,!0),g.addEventListener("progress",u)),a&&g.upload&&([d,b]=ei(a),g.upload.addEventListener("progress",d),g.upload.addEventListener("loadend",b)),(i.cancelToken||i.signal)&&(c=x=>{g&&(t(!x||x.type?new Ut(null,s,g):x),g.abort(),g=null)},i.cancelToken&&i.cancelToken.subscribe(c),i.signal&&(i.signal.aborted?c():i.signal.addEventListener("abort",c)));const E=Vf(i.url);if(E&&ce.protocols.indexOf(E)===-1){t(new P("Unsupported protocol "+E+":",P.ERR_BAD_REQUEST,s));return}g.send(o||null)})},Gf=(s,e)=>{const{length:t}=s=s?s.filter(Boolean):[];if(e||t){let i=new AbortController,o;const r=function(c){if(!o){o=!0,a();const d=c instanceof Error?c:this.reason;i.abort(d instanceof P?d:new Ut(d instanceof Error?d.message:d))}};let n=e&&setTimeout(()=>{n=null,r(new P(`timeout ${e} of ms exceeded`,P.ETIMEDOUT))},e);const a=()=>{s&&(n&&clearTimeout(n),n=null,s.forEach(c=>{c.unsubscribe?c.unsubscribe(r):c.removeEventListener("abort",r)}),s=null)};s.forEach(c=>c.addEventListener("abort",r));const{signal:l}=i;return l.unsubscribe=()=>_.asap(a),l}},Zf=function*(s,e){let t=s.byteLength;if(t<e){yield s;return}let i=0,o;for(;i<t;)o=i+e,yield s.slice(i,o),i=o},Yf=async function*(s,e){for await(const t of Jf(s))yield*Zf(t,e)},Jf=async function*(s){if(s[Symbol.asyncIterator]){yield*s;return}const e=s.getReader();try{for(;;){const{done:t,value:i}=await e.read();if(t)break;yield i}}finally{await e.cancel()}},en=(s,e,t,i)=>{const o=Yf(s,e);let r=0,n,a=l=>{n||(n=!0,i&&i(l))};return new ReadableStream({async pull(l){try{const{done:c,value:d}=await o.next();if(c){a(),l.close();return}let u=d.byteLength;if(t){let b=r+=u;t(b)}l.enqueue(new Uint8Array(d))}catch(c){throw a(c),c}},cancel(l){return a(l),o.return()}},{highWaterMark:2})},tn=64*1024,{isFunction:Os}=_,Xf=(({Request:s,Response:e})=>({Request:s,Response:e}))(_.global),{ReadableStream:sn,TextEncoder:on}=_.global,rn=(s,...e)=>{try{return!!s(...e)}catch{return!1}},Qf=s=>{s=_.merge.call({skipUndefined:!0},Xf,s);const{fetch:e,Request:t,Response:i}=s,o=e?Os(e):typeof fetch=="function",r=Os(t),n=Os(i);if(!o)return!1;const a=o&&Os(sn),l=o&&(typeof on=="function"?(m=>g=>m.encode(g))(new on):async m=>new Uint8Array(await new t(m).arrayBuffer())),c=r&&a&&rn(()=>{let m=!1;const g=new t(ce.origin,{body:new sn,method:"POST",get duplex(){return m=!0,"half"}}).headers.has("Content-Type");return m&&!g}),d=n&&a&&rn(()=>_.isReadableStream(new i("").body)),u={stream:d&&(m=>m.body)};o&&["text","arrayBuffer","blob","formData","stream"].forEach(m=>{!u[m]&&(u[m]=(g,w)=>{let E=g&&g[m];if(E)return E.call(g);throw new P(`Response type '${m}' is not supported`,P.ERR_NOT_SUPPORT,w)})});const b=async m=>{if(m==null)return 0;if(_.isBlob(m))return m.size;if(_.isSpecCompliantForm(m))return(await new t(ce.origin,{method:"POST",body:m}).arrayBuffer()).byteLength;if(_.isArrayBufferView(m)||_.isArrayBuffer(m))return m.byteLength;if(_.isURLSearchParams(m)&&(m=m+""),_.isString(m))return(await l(m)).byteLength},p=async(m,g)=>_.toFiniteNumber(m.getContentLength())??b(g);return async m=>{let{url:g,method:w,data:E,signal:x,cancelToken:A,timeout:$,onDownloadProgress:z,onUploadProgress:M,responseType:D,headers:Y,withCredentials:U="same-origin",fetchOptions:q}=fl(m),Ne=e||fetch;D=D?(D+"").toLowerCase():"text";let se=Gf([x,A&&A.toAbortSignal()],$),h=null;const C=se&&se.unsubscribe&&(()=>{se.unsubscribe()});let N;try{if(M&&c&&w!=="get"&&w!=="head"&&(N=await p(Y,E))!==0){let B=new t(g,{method:"POST",body:E,duplex:"half"}),Ie;if(_.isFormData(E)&&(Ie=B.headers.get("content-type"))&&Y.setContentType(Ie),B.body){const[ki,$s]=Jr(N,ei(Xr(M)));E=en(B.body,tn,ki,$s)}}_.isString(U)||(U=U?"include":"omit");const T=r&&"credentials"in t.prototype,F={...q,signal:se,method:w.toUpperCase(),headers:Y.normalize().toJSON(),body:E,duplex:"half",credentials:T?U:void 0};h=r&&new t(g,F);let S=await(r?Ne(h,q):Ne(g,F));const ie=d&&(D==="stream"||D==="response");if(d&&(z||ie&&C)){const B={};["status","statusText","headers"].forEach(cr=>{B[cr]=S[cr]});const Ie=_.toFiniteNumber(S.headers.get("content-length")),[ki,$s]=z&&Jr(Ie,ei(Xr(z),!0))||[];S=new i(en(S.body,tn,ki,()=>{$s&&$s(),C&&C()}),B)}D=D||"text";let ge=await u[_.findKey(u,D)||"text"](S,m);return!ie&&C&&C(),await new Promise((B,Ie)=>{ul(B,Ie,{data:ge,headers:fe.from(S.headers),status:S.status,statusText:S.statusText,config:m,request:h})})}catch(T){throw C&&C(),T&&T.name==="TypeError"&&/Load failed|fetch/i.test(T.message)?Object.assign(new P("Network Error",P.ERR_NETWORK,m,h),{cause:T.cause||T}):P.from(T,T&&T.code,m,h)}}},em=new Map,ml=s=>{let e=s&&s.env||{};const{fetch:t,Request:i,Response:o}=e,r=[i,o,t];let n=r.length,a=n,l,c,d=em;for(;a--;)l=r[a],c=d.get(l),c===void 0&&d.set(l,c=a?new Map:Qf(e)),d=c;return c};ml();const sr={http:gf,xhr:Kf,fetch:{get:ml}};_.forEach(sr,(s,e)=>{if(s){try{Object.defineProperty(s,"name",{value:e})}catch{}Object.defineProperty(s,"adapterName",{value:e})}});const nn=s=>`- ${s}`,tm=s=>_.isFunction(s)||s===null||s===!1;function sm(s,e){s=_.isArray(s)?s:[s];const{length:t}=s;let i,o;const r={};for(let n=0;n<t;n++){i=s[n];let a;if(o=i,!tm(i)&&(o=sr[(a=String(i)).toLowerCase()],o===void 0))throw new P(`Unknown adapter '${a}'`);if(o&&(_.isFunction(o)||(o=o.get(e))))break;r[a||"#"+n]=o}if(!o){const n=Object.entries(r).map(([l,c])=>`adapter ${l} `+(c===!1?"is not supported by the environment":"is not available in the build"));let a=t?n.length>1?`since :
`+n.map(nn).join(`
`):" "+nn(n[0]):"as no adapter specified";throw new P("There is no suitable adapter to dispatch the request "+a,"ERR_NOT_SUPPORT")}return o}const bl={getAdapter:sm,adapters:sr};function ji(s){if(s.cancelToken&&s.cancelToken.throwIfRequested(),s.signal&&s.signal.aborted)throw new Ut(null,s)}function an(s){return ji(s),s.headers=fe.from(s.headers),s.data=Hi.call(s,s.transformRequest),["post","put","patch"].indexOf(s.method)!==-1&&s.headers.setContentType("application/x-www-form-urlencoded",!1),bl.getAdapter(s.adapter||ks.adapter,s)(s).then(function(e){return ji(s),e.data=Hi.call(s,s.transformResponse,e),e.headers=fe.from(e.headers),e},function(e){return hl(e)||(ji(s),e&&e.response&&(e.response.data=Hi.call(s,s.transformResponse,e.response),e.response.headers=fe.from(e.response.headers))),Promise.reject(e)})}const gl="1.13.2",_i={};["object","boolean","number","function","string","symbol"].forEach((s,e)=>{_i[s]=function(t){return typeof t===s||"a"+(e<1?"n ":" ")+s}});const ln={};_i.transitional=function(s,e,t){function i(o,r){return"[Axios v"+gl+"] Transitional option '"+o+"'"+r+(t?". "+t:"")}return(o,r,n)=>{if(s===!1)throw new P(i(r," has been removed"+(e?" in "+e:"")),P.ERR_DEPRECATED);return e&&!ln[r]&&(ln[r]=!0,console.warn(i(r," has been deprecated since v"+e+" and will be removed in the near future"))),s?s(o,r,n):!0}};_i.spelling=function(s){return(e,t)=>(console.warn(`${t} is likely a misspelling of ${s}`),!0)};function im(s,e,t){if(typeof s!="object")throw new P("options must be an object",P.ERR_BAD_OPTION_VALUE);const i=Object.keys(s);let o=i.length;for(;o-- >0;){const r=i[o],n=e[r];if(n){const a=s[r],l=a===void 0||n(a,r,s);if(l!==!0)throw new P("option "+r+" must be "+l,P.ERR_BAD_OPTION_VALUE);continue}if(t!==!0)throw new P("Unknown option "+r,P.ERR_BAD_OPTION)}}const zs={assertOptions:im,validators:_i},Te=zs.validators;let ht=class{constructor(e){this.defaults=e||{},this.interceptors={request:new Zr,response:new Zr}}async request(e,t){try{return await this._request(e,t)}catch(i){if(i instanceof Error){let o={};Error.captureStackTrace?Error.captureStackTrace(o):o=new Error;const r=o.stack?o.stack.replace(/^.+\n/,""):"";try{i.stack?r&&!String(i.stack).endsWith(r.replace(/^.+\n.+\n/,""))&&(i.stack+=`
`+r):i.stack=r}catch{}}throw i}}_request(e,t){typeof e=="string"?(t=t||{},t.url=e):t=e||{},t=mt(this.defaults,t);const{transitional:i,paramsSerializer:o,headers:r}=t;i!==void 0&&zs.assertOptions(i,{silentJSONParsing:Te.transitional(Te.boolean),forcedJSONParsing:Te.transitional(Te.boolean),clarifyTimeoutError:Te.transitional(Te.boolean)},!1),o!=null&&(_.isFunction(o)?t.paramsSerializer={serialize:o}:zs.assertOptions(o,{encode:Te.function,serialize:Te.function},!0)),t.allowAbsoluteUrls!==void 0||(this.defaults.allowAbsoluteUrls!==void 0?t.allowAbsoluteUrls=this.defaults.allowAbsoluteUrls:t.allowAbsoluteUrls=!0),zs.assertOptions(t,{baseUrl:Te.spelling("baseURL"),withXsrfToken:Te.spelling("withXSRFToken")},!0),t.method=(t.method||this.defaults.method||"get").toLowerCase();let n=r&&_.merge(r.common,r[t.method]);r&&_.forEach(["delete","get","head","post","put","patch","common"],m=>{delete r[m]}),t.headers=fe.concat(n,r);const a=[];let l=!0;this.interceptors.request.forEach(function(m){typeof m.runWhen=="function"&&m.runWhen(t)===!1||(l=l&&m.synchronous,a.unshift(m.fulfilled,m.rejected))});const c=[];this.interceptors.response.forEach(function(m){c.push(m.fulfilled,m.rejected)});let d,u=0,b;if(!l){const m=[an.bind(this),void 0];for(m.unshift(...a),m.push(...c),b=m.length,d=Promise.resolve(t);u<b;)d=d.then(m[u++],m[u++]);return d}b=a.length;let p=t;for(;u<b;){const m=a[u++],g=a[u++];try{p=m(p)}catch(w){g.call(this,w);break}}try{d=an.call(this,p)}catch(m){return Promise.reject(m)}for(u=0,b=c.length;u<b;)d=d.then(c[u++],c[u++]);return d}getUri(e){e=mt(this.defaults,e);const t=pl(e.baseURL,e.url,e.allowAbsoluteUrls);return ll(t,e.params,e.paramsSerializer)}};_.forEach(["delete","get","head","options"],function(s){ht.prototype[s]=function(e,t){return this.request(mt(t||{},{method:s,url:e,data:(t||{}).data}))}});_.forEach(["post","put","patch"],function(s){function e(t){return function(i,o,r){return this.request(mt(r||{},{method:s,headers:t?{"Content-Type":"multipart/form-data"}:{},url:i,data:o}))}}ht.prototype[s]=e(),ht.prototype[s+"Form"]=e(!0)});let om=class vl{constructor(e){if(typeof e!="function")throw new TypeError("executor must be a function.");let t;this.promise=new Promise(function(o){t=o});const i=this;this.promise.then(o=>{if(!i._listeners)return;let r=i._listeners.length;for(;r-- >0;)i._listeners[r](o);i._listeners=null}),this.promise.then=o=>{let r;const n=new Promise(a=>{i.subscribe(a),r=a}).then(o);return n.cancel=function(){i.unsubscribe(r)},n},e(function(o,r,n){i.reason||(i.reason=new Ut(o,r,n),t(i.reason))})}throwIfRequested(){if(this.reason)throw this.reason}subscribe(e){if(this.reason){e(this.reason);return}this._listeners?this._listeners.push(e):this._listeners=[e]}unsubscribe(e){if(!this._listeners)return;const t=this._listeners.indexOf(e);t!==-1&&this._listeners.splice(t,1)}toAbortSignal(){const e=new AbortController,t=i=>{e.abort(i)};return this.subscribe(t),e.signal.unsubscribe=()=>this.unsubscribe(t),e.signal}static source(){let e;return{token:new vl(function(t){e=t}),cancel:e}}};function rm(s){return function(e){return s.apply(null,e)}}function nm(s){return _.isObject(s)&&s.isAxiosError===!0}const mo={Continue:100,SwitchingProtocols:101,Processing:102,EarlyHints:103,Ok:200,Created:201,Accepted:202,NonAuthoritativeInformation:203,NoContent:204,ResetContent:205,PartialContent:206,MultiStatus:207,AlreadyReported:208,ImUsed:226,MultipleChoices:300,MovedPermanently:301,Found:302,SeeOther:303,NotModified:304,UseProxy:305,Unused:306,TemporaryRedirect:307,PermanentRedirect:308,BadRequest:400,Unauthorized:401,PaymentRequired:402,Forbidden:403,NotFound:404,MethodNotAllowed:405,NotAcceptable:406,ProxyAuthenticationRequired:407,RequestTimeout:408,Conflict:409,Gone:410,LengthRequired:411,PreconditionFailed:412,PayloadTooLarge:413,UriTooLong:414,UnsupportedMediaType:415,RangeNotSatisfiable:416,ExpectationFailed:417,ImATeapot:418,MisdirectedRequest:421,UnprocessableEntity:422,Locked:423,FailedDependency:424,TooEarly:425,UpgradeRequired:426,PreconditionRequired:428,TooManyRequests:429,RequestHeaderFieldsTooLarge:431,UnavailableForLegalReasons:451,InternalServerError:500,NotImplemented:501,BadGateway:502,ServiceUnavailable:503,GatewayTimeout:504,HttpVersionNotSupported:505,VariantAlsoNegotiates:506,InsufficientStorage:507,LoopDetected:508,NotExtended:510,NetworkAuthenticationRequired:511,WebServerIsDown:521,ConnectionTimedOut:522,OriginIsUnreachable:523,TimeoutOccurred:524,SslHandshakeFailed:525,InvalidSslCertificate:526};Object.entries(mo).forEach(([s,e])=>{mo[e]=s});function _l(s){const e=new ht(s),t=Ya(ht.prototype.request,e);return _.extend(t,ht.prototype,e,{allOwnKeys:!0}),_.extend(t,e,null,{allOwnKeys:!0}),t.create=function(i){return _l(mt(s,i))},t}const X=_l(ks);X.Axios=ht;X.CanceledError=Ut;X.CancelToken=om;X.isCancel=hl;X.VERSION=gl;X.toFormData=vi;X.AxiosError=P;X.Cancel=X.CanceledError;X.all=function(s){return Promise.all(s)};X.spread=rm;X.isAxiosError=nm;X.mergeConfig=mt;X.AxiosHeaders=fe;X.formToJSON=s=>dl(_.isHTMLForm(s)?new FormData(s):s);X.getAdapter=bl.getAdapter;X.HttpStatusCode=mo;X.default=X;const{Axios:Zg,AxiosError:Yg,CanceledError:Jg,isCancel:Xg,CancelToken:Qg,VERSION:ev,all:tv,Cancel:sv,isAxiosError:iv,spread:ov,toFormData:rv,AxiosHeaders:nv,HttpStatusCode:av,formToJSON:lv,getAdapter:cv,mergeConfig:dv}=X;class am{constructor(){this.refreshPromise=null,this.tokenName=null,this.tokenValue=null,this.refreshPromise=null}async getToken(){return this.tokenValue||await this.refreshToken(),this.tokenValue}async refreshToken(){return this.refreshPromise?this.refreshPromise:(this.refreshPromise=xs.get("users/session-info").then(({data:e})=>{const{csrfTokenName:t,csrfTokenValue:i}=e;return this.tokenName=t??null,this.tokenValue=i??null,this.tokenValue}).finally(()=>{this.refreshPromise=null}),this.refreshPromise)}clearToken(){this.tokenValue=null}}function lm(s=""){return`https://craft6-dev.ddev.site/admin/actions/${s}`}function cm(){let s={"X-Registered-Asset-Bundles":[...new Set(Craft.registeredAssetBundles)].join(","),"X-Registered-Js-Files":[...new Set(Craft.registeredJsFiles)].join(",")};return Craft.csrfTokenValue&&(s["X-CSRF-Token"]=Craft.csrfTokenValue),s}const xs=X.create({baseURL:lm()}),bo=new am;xs.interceptors.request.use(async s=>{s.headers.set("X-Requested-With","XMLHttpRequest");const e=cm();if(Object.entries(e).forEach(([t,i])=>{s.headers.set(t,i)}),["post","put","patch","delete"].includes(s.method?.toLowerCase()||"")&&!s.url?.includes("users/session-info")){const t=await bo.getToken();t&&s.headers.set("X-CSRF-Token",t)}return s});xs.interceptors.response.use(s=>s,async s=>{const e=s.config;if(s.response?.status===419||s.response?.status===403&&!e._retry){e._retry=!0;try{return bo.clearToken(),e.headers["X-CSRF-Token"]=await bo.refreshToken(),X(e)}catch(t){return console.error("Failed to refresh CSRF token:",t),Promise.reject(t)}}return Promise.reject(s)});let Bs=!1,ut=null;async function dm(s){if(!Bs){if(ut)return ut;Bs=!0;try{return(await xs.post("app/api-headers",void 0,{cancelToken:s})).data}catch{}finally{Bs=!1}}}const ir=X.create({baseURL:"https://api.craftcms.com/v1/"});async function hm(s){return ut?Object.entries(ut).forEach(([e,t])=>{s.headers.set(e,t)}):(s.params=s.params||{},s.params.processCraftHeaders=1),s}async function um(s,e){if(ut)return;const{data:t}=await xs.post("app/process-api-response-headers",{headers:s},{cancelToken:e});return ut=t,Bs=!1,ut}async function pm(s){return await um(s.headers,s.config.cancelToken),s}ir.interceptors.request.use(async s=>{const{cancelToken:e}=s,t=await dm(e);t&&Object.entries(t).forEach(([o,r])=>{s.headers.set(o,r)});const i={...s,params:{...Craft.apiParams||{},...s.params,v:new Date().getTime()}};return t||(i.params.processCraftHeaders=1),Craft.httpProxy&&(i.proxy=Craft.httpProxy),i});ir.interceptors.request.use(hm);ir.interceptors.response.use(pm);var fm=function(s,e,t,i,o){if(typeof e=="function"?s!==e||!0:!e.has(s))throw new TypeError("Cannot write private member to an object whose class did not declare it");return e.set(s,t),t},cn=function(s,e,t,i){if(typeof e=="function"?s!==e||!i:!e.has(s))throw new TypeError("Cannot read private member from an object whose class did not declare it");return t==="m"?i:t==="a"?i.call(s):i?i.value:e.get(s)},Zt;class mm{formatToParts(e){const t=[];for(const i of e)t.push({type:"element",value:i}),t.push({type:"literal",value:", "});return t.slice(0,-1)}}const bm=typeof Intl<"u"&&Intl.ListFormat||mm,gm=[["years","year"],["months","month"],["weeks","week"],["days","day"],["hours","hour"],["minutes","minute"],["seconds","second"],["milliseconds","millisecond"]],vm={minimumIntegerDigits:2};class _m{constructor(e,t={}){Zt.set(this,void 0);let i=String(t.style||"short");i!=="long"&&i!=="short"&&i!=="narrow"&&i!=="digital"&&(i="short");let o=i==="digital"?"numeric":i;const r=t.hours||o;o=r==="2-digit"?"numeric":r;const n=t.minutes||o;o=n==="2-digit"?"numeric":n;const a=t.seconds||o;o=a==="2-digit"?"numeric":a;const l=t.milliseconds||o;fm(this,Zt,{locale:e,style:i,years:t.years||i==="digital"?"short":i,yearsDisplay:t.yearsDisplay==="always"?"always":"auto",months:t.months||i==="digital"?"short":i,monthsDisplay:t.monthsDisplay==="always"?"always":"auto",weeks:t.weeks||i==="digital"?"short":i,weeksDisplay:t.weeksDisplay==="always"?"always":"auto",days:t.days||i==="digital"?"short":i,daysDisplay:t.daysDisplay==="always"?"always":"auto",hours:r,hoursDisplay:t.hoursDisplay==="always"||i==="digital"?"always":"auto",minutes:n,minutesDisplay:t.minutesDisplay==="always"||i==="digital"?"always":"auto",seconds:a,secondsDisplay:t.secondsDisplay==="always"||i==="digital"?"always":"auto",milliseconds:l,millisecondsDisplay:t.millisecondsDisplay==="always"?"always":"auto"})}resolvedOptions(){return cn(this,Zt,"f")}formatToParts(e){const t=[],i=cn(this,Zt,"f"),o=i.style,r=i.locale;for(const[n,a]of gm){const l=e[n];if(i[`${n}Display`]==="auto"&&!l)continue;const c=i[n],d=c==="2-digit"?vm:c==="numeric"?{}:{style:"unit",unit:a,unitDisplay:c};let u=new Intl.NumberFormat(r,d).format(l);n==="months"&&(c==="narrow"||o==="narrow"&&u.endsWith("m"))&&(u=u.replace(/(\d+)m$/,"$1mo")),t.push(u)}return new bm(r,{type:"unit",style:o==="digital"?"short":o}).formatToParts(t)}format(e){return this.formatToParts(e).map(t=>t.value).join("")}}Zt=new WeakMap;const yl=/^[-+]?P(?:(\d+)Y)?(?:(\d+)M)?(?:(\d+)W)?(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?)?$/,ti=["year","month","week","day","hour","minute","second","millisecond"],ym=s=>yl.test(s);let Rt=class rt{constructor(e=0,t=0,i=0,o=0,r=0,n=0,a=0,l=0){this.years=e,this.months=t,this.weeks=i,this.days=o,this.hours=r,this.minutes=n,this.seconds=a,this.milliseconds=l,this.years||(this.years=0),this.sign||(this.sign=Math.sign(this.years)),this.months||(this.months=0),this.sign||(this.sign=Math.sign(this.months)),this.weeks||(this.weeks=0),this.sign||(this.sign=Math.sign(this.weeks)),this.days||(this.days=0),this.sign||(this.sign=Math.sign(this.days)),this.hours||(this.hours=0),this.sign||(this.sign=Math.sign(this.hours)),this.minutes||(this.minutes=0),this.sign||(this.sign=Math.sign(this.minutes)),this.seconds||(this.seconds=0),this.sign||(this.sign=Math.sign(this.seconds)),this.milliseconds||(this.milliseconds=0),this.sign||(this.sign=Math.sign(this.milliseconds)),this.blank=this.sign===0}abs(){return new rt(Math.abs(this.years),Math.abs(this.months),Math.abs(this.weeks),Math.abs(this.days),Math.abs(this.hours),Math.abs(this.minutes),Math.abs(this.seconds),Math.abs(this.milliseconds))}static from(e){var t;if(typeof e=="string"){const i=String(e).trim(),o=i.startsWith("-")?-1:1,r=(t=i.match(yl))===null||t===void 0?void 0:t.slice(1).map(n=>(Number(n)||0)*o);return r?new rt(...r):new rt}else if(typeof e=="object"){const{years:i,months:o,weeks:r,days:n,hours:a,minutes:l,seconds:c,milliseconds:d}=e;return new rt(i,o,r,n,a,l,c,d)}throw new RangeError("invalid duration")}static compare(e,t){const i=Date.now(),o=Math.abs(dn(i,rt.from(e)).getTime()-i),r=Math.abs(dn(i,rt.from(t)).getTime()-i);return o>r?-1:o<r?1:0}toLocaleString(e,t){return new _m(e,t).format(this)}};function dn(s,e){const t=new Date(s);return e.sign<0?(t.setUTCSeconds(t.getUTCSeconds()+e.seconds),t.setUTCMinutes(t.getUTCMinutes()+e.minutes),t.setUTCHours(t.getUTCHours()+e.hours),t.setUTCDate(t.getUTCDate()+e.weeks*7+e.days),t.setUTCMonth(t.getUTCMonth()+e.months),t.setUTCFullYear(t.getUTCFullYear()+e.years)):(t.setUTCFullYear(t.getUTCFullYear()+e.years),t.setUTCMonth(t.getUTCMonth()+e.months),t.setUTCDate(t.getUTCDate()+e.weeks*7+e.days),t.setUTCHours(t.getUTCHours()+e.hours),t.setUTCMinutes(t.getUTCMinutes()+e.minutes),t.setUTCSeconds(t.getUTCSeconds()+e.seconds)),t}function wm(s,e="second",t=Date.now()){const i=s.getTime()-t;if(i===0)return new Rt;const o=Math.sign(i),r=Math.abs(i),n=Math.floor(r/1e3),a=Math.floor(n/60),l=Math.floor(a/60),c=Math.floor(l/24),d=Math.floor(c/30),u=Math.floor(d/12),b=ti.indexOf(e)||ti.length;return new Rt(b>=0?u*o:0,b>=1?(d-u*12)*o:0,0,b>=3?(c-d*30)*o:0,b>=4?(l-c*24)*o:0,b>=5?(a-l*60)*o:0,b>=6?(n-a*60)*o:0,b>=7?(r-n*1e3)*o:0)}function wl(s,{relativeTo:e=Date.now()}={}){if(e=new Date(e),s.blank)return s;const t=s.sign;let i=Math.abs(s.years),o=Math.abs(s.months),r=Math.abs(s.weeks),n=Math.abs(s.days),a=Math.abs(s.hours),l=Math.abs(s.minutes),c=Math.abs(s.seconds),d=Math.abs(s.milliseconds);d>=900&&(c+=Math.round(d/1e3)),(c||l||a||n||r||o||i)&&(d=0),c>=55&&(l+=Math.round(c/60)),(l||a||n||r||o||i)&&(c=0),l>=55&&(a+=Math.round(l/60)),(a||n||r||o||i)&&(l=0),n&&a>=12&&(n+=Math.round(a/24)),!n&&a>=21&&(n+=Math.round(a/24)),(n||r||o||i)&&(a=0);const u=e.getFullYear(),b=e.getMonth(),p=e.getDate();if(n>=27||i+o+n){const m=new Date(e);m.setDate(1),m.setMonth(b+o*t+1),m.setDate(0);const g=Math.max(0,p-m.getDate()),w=new Date(e);w.setFullYear(u+i*t),w.setDate(p-g),w.setMonth(b+o*t),w.setDate(p-g+n*t);const E=w.getFullYear()-e.getFullYear(),x=w.getMonth()-e.getMonth(),A=Math.abs(Math.round((Number(w)-Number(e))/864e5))+g,$=Math.abs(E*12+x);A<27?(n>=6?(r+=Math.round(n/7),n=0):n=A,o=i=0):$<=11?(o=$,i=0):(o=0,i=E*t),(o||i)&&(n=0)}return i&&(o=0),r>=4&&(o+=Math.round(r/4)),(o||i)&&(r=0),n&&r&&!o&&!i&&(r+=Math.round(n/7),n=0),new Rt(i*t,o*t,r*t,n*t,a*t,l*t,c*t,d*t)}function Em(s,e){const t=wl(s,e);if(t.blank)return[0,"second"];for(const i of ti){if(i==="millisecond")continue;const o=t[`${i}s`];if(o)return[o,i]}return[0,"second"]}var Z=function(s,e,t,i){if(t==="a"&&!i)throw new TypeError("Private accessor was defined without a getter");if(typeof e=="function"?s!==e||!i:!e.has(s))throw new TypeError("Cannot read private member from an object whose class did not declare it");return t==="m"?i:t==="a"?i.call(s):i?i.value:e.get(s)},Ls=function(s,e,t,i,o){if(typeof e=="function"?s!==e||!0:!e.has(s))throw new TypeError("Cannot write private member to an object whose class did not declare it");return e.set(s,t),t},ae,Yt,Jt,yt,ct,go,El,kl,xl,Cl,$l,vo,Al,Et;const km=globalThis.HTMLElement||null,qi=new Rt,hn=new Rt(0,0,0,0,0,1);class xm extends Event{constructor(e,t,i,o){super("relative-time-updated",{bubbles:!0,composed:!0}),this.oldText=e,this.newText=t,this.oldTitle=i,this.newTitle=o}}function un(s){if(!s.date)return 1/0;if(s.format==="duration"||s.format==="elapsed"){const t=s.precision;if(t==="second")return 1e3;if(t==="minute")return 60*1e3}const e=Math.abs(Date.now()-s.date.getTime());return e<60*1e3?1e3:e<3600*1e3?60*1e3:3600*1e3}const Wi=new class{constructor(){this.elements=new Set,this.time=1/0,this.timer=-1}observe(s){if(this.elements.has(s))return;this.elements.add(s);const e=s.date;if(e&&e.getTime()){const t=un(s),i=Date.now()+t;i<this.time&&(clearTimeout(this.timer),this.timer=setTimeout(()=>this.update(),t),this.time=i)}}unobserve(s){this.elements.has(s)&&this.elements.delete(s)}update(){if(clearTimeout(this.timer),!this.elements.size)return;let s=1/0;for(const e of this.elements)s=Math.min(s,un(e)),e.update();this.time=Math.min(3600*1e3,s),this.timer=setTimeout(()=>this.update(),this.time),this.time+=Date.now()}};class Cm extends km{constructor(){super(...arguments),ae.add(this),Yt.set(this,!1),Jt.set(this,!1),ct.set(this,this.shadowRoot?this.shadowRoot:this.attachShadow?this.attachShadow({mode:"open"}):this),Et.set(this,null)}static define(e="relative-time",t=customElements){return t.define(e,this),this}get timeZone(){var e;return((e=this.closest("[time-zone]"))===null||e===void 0?void 0:e.getAttribute("time-zone"))||this.ownerDocument.documentElement.getAttribute("time-zone")||void 0}static get observedAttributes(){return["second","minute","hour","weekday","day","month","year","time-zone-name","prefix","threshold","tense","precision","format","format-style","no-title","datetime","lang","title","aria-hidden","time-zone"]}get onRelativeTimeUpdated(){return Z(this,Et,"f")}set onRelativeTimeUpdated(e){Z(this,Et,"f")&&this.removeEventListener("relative-time-updated",Z(this,Et,"f")),Ls(this,Et,typeof e=="object"||typeof e=="function"?e:null),typeof e=="function"&&this.addEventListener("relative-time-updated",e)}get second(){const e=this.getAttribute("second");if(e==="numeric"||e==="2-digit")return e}set second(e){this.setAttribute("second",e||"")}get minute(){const e=this.getAttribute("minute");if(e==="numeric"||e==="2-digit")return e}set minute(e){this.setAttribute("minute",e||"")}get hour(){const e=this.getAttribute("hour");if(e==="numeric"||e==="2-digit")return e}set hour(e){this.setAttribute("hour",e||"")}get weekday(){const e=this.getAttribute("weekday");if(e==="long"||e==="short"||e==="narrow")return e;if(this.format==="datetime"&&e!=="")return this.formatStyle}set weekday(e){this.setAttribute("weekday",e||"")}get day(){var e;const t=(e=this.getAttribute("day"))!==null&&e!==void 0?e:"numeric";if(t==="numeric"||t==="2-digit")return t}set day(e){this.setAttribute("day",e||"")}get month(){const e=this.format;let t=this.getAttribute("month");if(t!==""&&(t??(t=e==="datetime"?this.formatStyle:"short"),t==="numeric"||t==="2-digit"||t==="short"||t==="long"||t==="narrow"))return t}set month(e){this.setAttribute("month",e||"")}get year(){var e;const t=this.getAttribute("year");if(t==="numeric"||t==="2-digit")return t;if(!this.hasAttribute("year")&&new Date().getUTCFullYear()!==((e=this.date)===null||e===void 0?void 0:e.getUTCFullYear()))return"numeric"}set year(e){this.setAttribute("year",e||"")}get timeZoneName(){const e=this.getAttribute("time-zone-name");if(e==="long"||e==="short"||e==="shortOffset"||e==="longOffset"||e==="shortGeneric"||e==="longGeneric")return e}set timeZoneName(e){this.setAttribute("time-zone-name",e||"")}get prefix(){var e;return(e=this.getAttribute("prefix"))!==null&&e!==void 0?e:this.format==="datetime"?"":"on"}set prefix(e){this.setAttribute("prefix",e)}get threshold(){const e=this.getAttribute("threshold");return e&&ym(e)?e:"P30D"}set threshold(e){this.setAttribute("threshold",e)}get tense(){const e=this.getAttribute("tense");return e==="past"?"past":e==="future"?"future":"auto"}set tense(e){this.setAttribute("tense",e)}get precision(){const e=this.getAttribute("precision");return ti.includes(e)?e:this.format==="micro"?"minute":"second"}set precision(e){this.setAttribute("precision",e)}get format(){const e=this.getAttribute("format");return e==="datetime"?"datetime":e==="relative"?"relative":e==="duration"?"duration":e==="micro"?"micro":e==="elapsed"?"elapsed":"auto"}set format(e){this.setAttribute("format",e)}get formatStyle(){const e=this.getAttribute("format-style");if(e==="long")return"long";if(e==="short")return"short";if(e==="narrow")return"narrow";const t=this.format;return t==="elapsed"||t==="micro"?"narrow":t==="datetime"?"short":"long"}set formatStyle(e){this.setAttribute("format-style",e)}get noTitle(){return this.hasAttribute("no-title")}set noTitle(e){this.toggleAttribute("no-title",e)}get datetime(){return this.getAttribute("datetime")||""}set datetime(e){this.setAttribute("datetime",e)}get date(){const e=Date.parse(this.datetime);return Number.isNaN(e)?null:new Date(e)}set date(e){this.datetime=e?.toISOString()||""}connectedCallback(){this.update()}disconnectedCallback(){Wi.unobserve(this)}attributeChangedCallback(e,t,i){t!==i&&(e==="title"&&Ls(this,Yt,i!==null&&(this.date&&Z(this,ae,"m",go).call(this,this.date))!==i),!Z(this,Jt,"f")&&!(e==="title"&&Z(this,Yt,"f"))&&Ls(this,Jt,(async()=>{await Promise.resolve(),this.update(),Ls(this,Jt,!1,"f")})()))}update(){const e=Z(this,ct,"f").textContent||this.textContent||"",t=this.getAttribute("title")||"";let i=t;const o=this.date;if(typeof Intl>"u"||!Intl.DateTimeFormat||!o){Z(this,ct,"f").textContent=e;return}const r=Date.now();Z(this,Yt,"f")||(i=Z(this,ae,"m",go).call(this,o)||"",i&&!this.noTitle&&this.setAttribute("title",i));const n=wm(o,this.precision,r),a=Z(this,ae,"m",El).call(this,n);let l=e;const c=Z(this,ae,"m",Al).call(this,a);c?l=Z(this,ae,"m",$l).call(this,o):a==="duration"?l=Z(this,ae,"m",kl).call(this,n):a==="relative"?l=Z(this,ae,"m",xl).call(this,n):l=Z(this,ae,"m",Cl).call(this,o),l?Z(this,ae,"m",vo).call(this,l):this.shadowRoot===Z(this,ct,"f")&&this.textContent&&Z(this,ae,"m",vo).call(this,this.textContent),(l!==e||i!==t)&&this.dispatchEvent(new xm(e,l,t,i)),(a==="relative"||a==="duration")&&!c?Wi.observe(this):Wi.unobserve(this)}}Yt=new WeakMap,Jt=new WeakMap,ct=new WeakMap,Et=new WeakMap,ae=new WeakSet,yt=function(){var s;const e=((s=this.closest("[lang]"))===null||s===void 0?void 0:s.getAttribute("lang"))||this.ownerDocument.documentElement.getAttribute("lang");try{return new Intl.Locale(e??"").toString()}catch{return"default"}},go=function(s){return new Intl.DateTimeFormat(Z(this,ae,"a",yt),{day:"numeric",month:"short",year:"numeric",hour:"numeric",minute:"2-digit",timeZoneName:"short",timeZone:this.timeZone}).format(s)},El=function(s){const e=this.format;if(e==="datetime")return"datetime";if(e==="duration"||e==="elapsed"||e==="micro")return"duration";if((e==="auto"||e==="relative")&&typeof Intl<"u"&&Intl.RelativeTimeFormat){const t=this.tense;if(t==="past"||t==="future"||Rt.compare(s,this.threshold)===1)return"relative"}return"datetime"},kl=function(s){const e=Z(this,ae,"a",yt),t=this.format,i=this.formatStyle,o=this.tense;let r=qi;t==="micro"?(s=wl(s),r=hn,s.months===0&&(this.tense==="past"&&s.sign!==-1||this.tense==="future"&&s.sign!==1)&&(s=hn)):(o==="past"&&s.sign!==-1||o==="future"&&s.sign!==1)&&(s=r);const n=`${this.precision}sDisplay`;return s.blank?r.toLocaleString(e,{style:i,[n]:"always"}):s.abs().toLocaleString(e,{style:i})},xl=function(s){const e=new Intl.RelativeTimeFormat(Z(this,ae,"a",yt),{numeric:"auto",style:this.formatStyle}),t=this.tense;t==="future"&&s.sign!==1&&(s=qi),t==="past"&&s.sign!==-1&&(s=qi);const[i,o]=Em(s);return o==="second"&&i<10?e.format(0,this.precision==="millisecond"?"second":this.precision):e.format(i,o)},Cl=function(s){const e=new Intl.DateTimeFormat(Z(this,ae,"a",yt),{second:this.second,minute:this.minute,hour:this.hour,weekday:this.weekday,day:this.day,month:this.month,year:this.year,timeZoneName:this.timeZoneName,timeZone:this.timeZone});return`${this.prefix} ${e.format(s)}`.trim()},$l=function(s){return new Intl.DateTimeFormat(Z(this,ae,"a",yt),{day:"numeric",month:"short",year:"numeric",hour:"numeric",minute:"2-digit",timeZoneName:"short",timeZone:this.timeZone}).format(s)},vo=function(s){if(this.hasAttribute("aria-hidden")&&this.getAttribute("aria-hidden")==="true"){const e=document.createElement("span");e.setAttribute("aria-hidden","true"),e.textContent=s,Z(this,ct,"f").replaceChildren(e)}else Z(this,ct,"f").textContent=s},Al=function(s){var e;return s==="duration"?!1:this.ownerDocument.documentElement.getAttribute("data-prefers-absolute-time")==="true"||((e=this.ownerDocument.body)===null||e===void 0?void 0:e.getAttribute("data-prefers-absolute-time"))==="true"};const pn=typeof globalThis<"u"?globalThis:window;try{pn.RelativeTimeElement=Cm.define()}catch(s){if(!(pn.DOMException&&s instanceof DOMException&&s.name==="NotSupportedError")&&!(s instanceof ReferenceError))throw s}/**
 * @license
 * Copyright 2019 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const Us=globalThis,or=Us.ShadowRoot&&(Us.ShadyCSS===void 0||Us.ShadyCSS.nativeShadow)&&"adoptedStyleSheets"in Document.prototype&&"replace"in CSSStyleSheet.prototype,Sl=Symbol(),fn=new WeakMap;let $m=class{constructor(e,t,i){if(this._$cssResult$=!0,i!==Sl)throw Error("CSSResult is not constructable. Use `unsafeCSS` or `css` instead.");this.cssText=e,this.t=t}get styleSheet(){let e=this.o;const t=this.t;if(or&&e===void 0){const i=t!==void 0&&t.length===1;i&&(e=fn.get(t)),e===void 0&&((this.o=e=new CSSStyleSheet).replaceSync(this.cssText),i&&fn.set(t,e))}return e}toString(){return this.cssText}};const Am=s=>new $m(typeof s=="string"?s:s+"",void 0,Sl),Sm=(s,e)=>{if(or)s.adoptedStyleSheets=e.map((t=>t instanceof CSSStyleSheet?t:t.styleSheet));else for(const t of e){const i=document.createElement("style"),o=Us.litNonce;o!==void 0&&i.setAttribute("nonce",o),i.textContent=t.cssText,s.appendChild(i)}},mn=or?s=>s:s=>s instanceof CSSStyleSheet?(e=>{let t="";for(const i of e.cssRules)t+=i.cssText;return Am(t)})(s):s;/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const{is:Nm,defineProperty:Tm,getOwnPropertyDescriptor:Om,getOwnPropertyNames:Lm,getOwnPropertySymbols:Rm,getPrototypeOf:Fm}=Object,yi=globalThis,bn=yi.trustedTypes,Pm=bn?bn.emptyScript:"",Mm=yi.reactiveElementPolyfillSupport,ss=(s,e)=>s,si={toAttribute(s,e){switch(e){case Boolean:s=s?Pm:null;break;case Object:case Array:s=s==null?s:JSON.stringify(s)}return s},fromAttribute(s,e){let t=s;switch(e){case Boolean:t=s!==null;break;case Number:t=s===null?null:Number(s);break;case Object:case Array:try{t=JSON.parse(s)}catch{t=null}}return t}},rr=(s,e)=>!Nm(s,e),gn={attribute:!0,type:String,converter:si,reflect:!1,useDefault:!1,hasChanged:rr};Symbol.metadata??=Symbol("metadata"),yi.litPropertyMetadata??=new WeakMap;class kt extends HTMLElement{static addInitializer(e){this._$Ei(),(this.l??=[]).push(e)}static get observedAttributes(){return this.finalize(),this._$Eh&&[...this._$Eh.keys()]}static createProperty(e,t=gn){if(t.state&&(t.attribute=!1),this._$Ei(),this.prototype.hasOwnProperty(e)&&((t=Object.create(t)).wrapped=!0),this.elementProperties.set(e,t),!t.noAccessor){const i=Symbol(),o=this.getPropertyDescriptor(e,i,t);o!==void 0&&Tm(this.prototype,e,o)}}static getPropertyDescriptor(e,t,i){const{get:o,set:r}=Om(this.prototype,e)??{get(){return this[t]},set(n){this[t]=n}};return{get:o,set(n){const a=o?.call(this);r?.call(this,n),this.requestUpdate(e,a,i)},configurable:!0,enumerable:!0}}static getPropertyOptions(e){return this.elementProperties.get(e)??gn}static _$Ei(){if(this.hasOwnProperty(ss("elementProperties")))return;const e=Fm(this);e.finalize(),e.l!==void 0&&(this.l=[...e.l]),this.elementProperties=new Map(e.elementProperties)}static finalize(){if(this.hasOwnProperty(ss("finalized")))return;if(this.finalized=!0,this._$Ei(),this.hasOwnProperty(ss("properties"))){const t=this.properties,i=[...Lm(t),...Rm(t)];for(const o of i)this.createProperty(o,t[o])}const e=this[Symbol.metadata];if(e!==null){const t=litPropertyMetadata.get(e);if(t!==void 0)for(const[i,o]of t)this.elementProperties.set(i,o)}this._$Eh=new Map;for(const[t,i]of this.elementProperties){const o=this._$Eu(t,i);o!==void 0&&this._$Eh.set(o,t)}this.elementStyles=this.finalizeStyles(this.styles)}static finalizeStyles(e){const t=[];if(Array.isArray(e)){const i=new Set(e.flat(1/0).reverse());for(const o of i)t.unshift(mn(o))}else e!==void 0&&t.push(mn(e));return t}static _$Eu(e,t){const i=t.attribute;return i===!1?void 0:typeof i=="string"?i:typeof e=="string"?e.toLowerCase():void 0}constructor(){super(),this._$Ep=void 0,this.isUpdatePending=!1,this.hasUpdated=!1,this._$Em=null,this._$Ev()}_$Ev(){this._$ES=new Promise((e=>this.enableUpdating=e)),this._$AL=new Map,this._$E_(),this.requestUpdate(),this.constructor.l?.forEach((e=>e(this)))}addController(e){(this._$EO??=new Set).add(e),this.renderRoot!==void 0&&this.isConnected&&e.hostConnected?.()}removeController(e){this._$EO?.delete(e)}_$E_(){const e=new Map,t=this.constructor.elementProperties;for(const i of t.keys())this.hasOwnProperty(i)&&(e.set(i,this[i]),delete this[i]);e.size>0&&(this._$Ep=e)}createRenderRoot(){const e=this.shadowRoot??this.attachShadow(this.constructor.shadowRootOptions);return Sm(e,this.constructor.elementStyles),e}connectedCallback(){this.renderRoot??=this.createRenderRoot(),this.enableUpdating(!0),this._$EO?.forEach((e=>e.hostConnected?.()))}enableUpdating(e){}disconnectedCallback(){this._$EO?.forEach((e=>e.hostDisconnected?.()))}attributeChangedCallback(e,t,i){this._$AK(e,i)}_$ET(e,t){const i=this.constructor.elementProperties.get(e),o=this.constructor._$Eu(e,i);if(o!==void 0&&i.reflect===!0){const r=(i.converter?.toAttribute!==void 0?i.converter:si).toAttribute(t,i.type);this._$Em=e,r==null?this.removeAttribute(o):this.setAttribute(o,r),this._$Em=null}}_$AK(e,t){const i=this.constructor,o=i._$Eh.get(e);if(o!==void 0&&this._$Em!==o){const r=i.getPropertyOptions(o),n=typeof r.converter=="function"?{fromAttribute:r.converter}:r.converter?.fromAttribute!==void 0?r.converter:si;this._$Em=o;const a=n.fromAttribute(t,r.type);this[o]=a??this._$Ej?.get(o)??a,this._$Em=null}}requestUpdate(e,t,i){if(e!==void 0){const o=this.constructor,r=this[e];if(i??=o.getPropertyOptions(e),!((i.hasChanged??rr)(r,t)||i.useDefault&&i.reflect&&r===this._$Ej?.get(e)&&!this.hasAttribute(o._$Eu(e,i))))return;this.C(e,t,i)}this.isUpdatePending===!1&&(this._$ES=this._$EP())}C(e,t,{useDefault:i,reflect:o,wrapped:r},n){i&&!(this._$Ej??=new Map).has(e)&&(this._$Ej.set(e,n??t??this[e]),r!==!0||n!==void 0)||(this._$AL.has(e)||(this.hasUpdated||i||(t=void 0),this._$AL.set(e,t)),o===!0&&this._$Em!==e&&(this._$Eq??=new Set).add(e))}async _$EP(){this.isUpdatePending=!0;try{await this._$ES}catch(t){Promise.reject(t)}const e=this.scheduleUpdate();return e!=null&&await e,!this.isUpdatePending}scheduleUpdate(){return this.performUpdate()}performUpdate(){if(!this.isUpdatePending)return;if(!this.hasUpdated){if(this.renderRoot??=this.createRenderRoot(),this._$Ep){for(const[o,r]of this._$Ep)this[o]=r;this._$Ep=void 0}const i=this.constructor.elementProperties;if(i.size>0)for(const[o,r]of i){const{wrapped:n}=r,a=this[o];n!==!0||this._$AL.has(o)||a===void 0||this.C(o,void 0,r,a)}}let e=!1;const t=this._$AL;try{e=this.shouldUpdate(t),e?(this.willUpdate(t),this._$EO?.forEach((i=>i.hostUpdate?.())),this.update(t)):this._$EM()}catch(i){throw e=!1,this._$EM(),i}e&&this._$AE(t)}willUpdate(e){}_$AE(e){this._$EO?.forEach((t=>t.hostUpdated?.())),this.hasUpdated||(this.hasUpdated=!0,this.firstUpdated(e)),this.updated(e)}_$EM(){this._$AL=new Map,this.isUpdatePending=!1}get updateComplete(){return this.getUpdateComplete()}getUpdateComplete(){return this._$ES}shouldUpdate(e){return!0}update(e){this._$Eq&&=this._$Eq.forEach((t=>this._$ET(t,this[t]))),this._$EM()}updated(e){}firstUpdated(e){}}kt.elementStyles=[],kt.shadowRootOptions={mode:"open"},kt[ss("elementProperties")]=new Map,kt[ss("finalized")]=new Map,Mm?.({ReactiveElement:kt}),(yi.reactiveElementVersions??=[]).push("2.1.1");/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const nr=globalThis,ii=nr.trustedTypes,vn=ii?ii.createPolicy("lit-html",{createHTML:s=>s}):void 0,Nl="$lit$",Ze=`lit$${Math.random().toFixed(9).slice(2)}$`,Tl="?"+Ze,Im=`<${Tl}>`,bt=document,ns=()=>bt.createComment(""),as=s=>s===null||typeof s!="object"&&typeof s!="function",ar=Array.isArray,Dm=s=>ar(s)||typeof s?.[Symbol.iterator]=="function",Ki=`[ 	
\f\r]`,Wt=/<(?:(!--|\/[^a-zA-Z])|(\/?[a-zA-Z][^>\s]*)|(\/?$))/g,_n=/-->/g,yn=/>/g,it=RegExp(`>|${Ki}(?:([^\\s"'>=/]+)(${Ki}*=${Ki}*(?:[^ 	
\f\r"'\`<>=]|("|')|))|$)`,"g"),wn=/'/g,En=/"/g,Ol=/^(?:script|style|textarea|title)$/i,Ft=Symbol.for("lit-noChange"),ne=Symbol.for("lit-nothing"),kn=new WeakMap,dt=bt.createTreeWalker(bt,129);function Ll(s,e){if(!ar(s)||!s.hasOwnProperty("raw"))throw Error("invalid template strings array");return vn!==void 0?vn.createHTML(e):e}const Vm=(s,e)=>{const t=s.length-1,i=[];let o,r=e===2?"<svg>":e===3?"<math>":"",n=Wt;for(let a=0;a<t;a++){const l=s[a];let c,d,u=-1,b=0;for(;b<l.length&&(n.lastIndex=b,d=n.exec(l),d!==null);)b=n.lastIndex,n===Wt?d[1]==="!--"?n=_n:d[1]!==void 0?n=yn:d[2]!==void 0?(Ol.test(d[2])&&(o=RegExp("</"+d[2],"g")),n=it):d[3]!==void 0&&(n=it):n===it?d[0]===">"?(n=o??Wt,u=-1):d[1]===void 0?u=-2:(u=n.lastIndex-d[2].length,c=d[1],n=d[3]===void 0?it:d[3]==='"'?En:wn):n===En||n===wn?n=it:n===_n||n===yn?n=Wt:(n=it,o=void 0);const p=n===it&&s[a+1].startsWith("/>")?" ":"";r+=n===Wt?l+Im:u>=0?(i.push(c),l.slice(0,u)+Nl+l.slice(u)+Ze+p):l+Ze+(u===-2?a:p)}return[Ll(s,r+(s[t]||"<?>")+(e===2?"</svg>":e===3?"</math>":"")),i]};class ls{constructor({strings:e,_$litType$:t},i){let o;this.parts=[];let r=0,n=0;const a=e.length-1,l=this.parts,[c,d]=Vm(e,t);if(this.el=ls.createElement(c,i),dt.currentNode=this.el.content,t===2||t===3){const u=this.el.content.firstChild;u.replaceWith(...u.childNodes)}for(;(o=dt.nextNode())!==null&&l.length<a;){if(o.nodeType===1){if(o.hasAttributes())for(const u of o.getAttributeNames())if(u.endsWith(Nl)){const b=d[n++],p=o.getAttribute(u).split(Ze),m=/([.?@])?(.*)/.exec(b);l.push({type:1,index:r,name:m[2],strings:p,ctor:m[1]==="."?Bm:m[1]==="?"?Um:m[1]==="@"?Hm:wi}),o.removeAttribute(u)}else u.startsWith(Ze)&&(l.push({type:6,index:r}),o.removeAttribute(u));if(Ol.test(o.tagName)){const u=o.textContent.split(Ze),b=u.length-1;if(b>0){o.textContent=ii?ii.emptyScript:"";for(let p=0;p<b;p++)o.append(u[p],ns()),dt.nextNode(),l.push({type:2,index:++r});o.append(u[b],ns())}}}else if(o.nodeType===8)if(o.data===Tl)l.push({type:2,index:r});else{let u=-1;for(;(u=o.data.indexOf(Ze,u+1))!==-1;)l.push({type:7,index:r}),u+=Ze.length-1}r++}}static createElement(e,t){const i=bt.createElement("template");return i.innerHTML=e,i}}function Pt(s,e,t=s,i){if(e===Ft)return e;let o=i!==void 0?t._$Co?.[i]:t._$Cl;const r=as(e)?void 0:e._$litDirective$;return o?.constructor!==r&&(o?._$AO?.(!1),r===void 0?o=void 0:(o=new r(s),o._$AT(s,t,i)),i!==void 0?(t._$Co??=[])[i]=o:t._$Cl=o),o!==void 0&&(e=Pt(s,o._$AS(s,e.values),o,i)),e}class zm{constructor(e,t){this._$AV=[],this._$AN=void 0,this._$AD=e,this._$AM=t}get parentNode(){return this._$AM.parentNode}get _$AU(){return this._$AM._$AU}u(e){const{el:{content:t},parts:i}=this._$AD,o=(e?.creationScope??bt).importNode(t,!0);dt.currentNode=o;let r=dt.nextNode(),n=0,a=0,l=i[0];for(;l!==void 0;){if(n===l.index){let c;l.type===2?c=new Cs(r,r.nextSibling,this,e):l.type===1?c=new l.ctor(r,l.name,l.strings,this,e):l.type===6&&(c=new jm(r,this,e)),this._$AV.push(c),l=i[++a]}n!==l?.index&&(r=dt.nextNode(),n++)}return dt.currentNode=bt,o}p(e){let t=0;for(const i of this._$AV)i!==void 0&&(i.strings!==void 0?(i._$AI(e,i,t),t+=i.strings.length-2):i._$AI(e[t])),t++}}class Cs{get _$AU(){return this._$AM?._$AU??this._$Cv}constructor(e,t,i,o){this.type=2,this._$AH=ne,this._$AN=void 0,this._$AA=e,this._$AB=t,this._$AM=i,this.options=o,this._$Cv=o?.isConnected??!0}get parentNode(){let e=this._$AA.parentNode;const t=this._$AM;return t!==void 0&&e?.nodeType===11&&(e=t.parentNode),e}get startNode(){return this._$AA}get endNode(){return this._$AB}_$AI(e,t=this){e=Pt(this,e,t),as(e)?e===ne||e==null||e===""?(this._$AH!==ne&&this._$AR(),this._$AH=ne):e!==this._$AH&&e!==Ft&&this._(e):e._$litType$!==void 0?this.$(e):e.nodeType!==void 0?this.T(e):Dm(e)?this.k(e):this._(e)}O(e){return this._$AA.parentNode.insertBefore(e,this._$AB)}T(e){this._$AH!==e&&(this._$AR(),this._$AH=this.O(e))}_(e){this._$AH!==ne&&as(this._$AH)?this._$AA.nextSibling.data=e:this.T(bt.createTextNode(e)),this._$AH=e}$(e){const{values:t,_$litType$:i}=e,o=typeof i=="number"?this._$AC(e):(i.el===void 0&&(i.el=ls.createElement(Ll(i.h,i.h[0]),this.options)),i);if(this._$AH?._$AD===o)this._$AH.p(t);else{const r=new zm(o,this),n=r.u(this.options);r.p(t),this.T(n),this._$AH=r}}_$AC(e){let t=kn.get(e.strings);return t===void 0&&kn.set(e.strings,t=new ls(e)),t}k(e){ar(this._$AH)||(this._$AH=[],this._$AR());const t=this._$AH;let i,o=0;for(const r of e)o===t.length?t.push(i=new Cs(this.O(ns()),this.O(ns()),this,this.options)):i=t[o],i._$AI(r),o++;o<t.length&&(this._$AR(i&&i._$AB.nextSibling,o),t.length=o)}_$AR(e=this._$AA.nextSibling,t){for(this._$AP?.(!1,!0,t);e!==this._$AB;){const i=e.nextSibling;e.remove(),e=i}}setConnected(e){this._$AM===void 0&&(this._$Cv=e,this._$AP?.(e))}}class wi{get tagName(){return this.element.tagName}get _$AU(){return this._$AM._$AU}constructor(e,t,i,o,r){this.type=1,this._$AH=ne,this._$AN=void 0,this.element=e,this.name=t,this._$AM=o,this.options=r,i.length>2||i[0]!==""||i[1]!==""?(this._$AH=Array(i.length-1).fill(new String),this.strings=i):this._$AH=ne}_$AI(e,t=this,i,o){const r=this.strings;let n=!1;if(r===void 0)e=Pt(this,e,t,0),n=!as(e)||e!==this._$AH&&e!==Ft,n&&(this._$AH=e);else{const a=e;let l,c;for(e=r[0],l=0;l<r.length-1;l++)c=Pt(this,a[i+l],t,l),c===Ft&&(c=this._$AH[l]),n||=!as(c)||c!==this._$AH[l],c===ne?e=ne:e!==ne&&(e+=(c??"")+r[l+1]),this._$AH[l]=c}n&&!o&&this.j(e)}j(e){e===ne?this.element.removeAttribute(this.name):this.element.setAttribute(this.name,e??"")}}class Bm extends wi{constructor(){super(...arguments),this.type=3}j(e){this.element[this.name]=e===ne?void 0:e}}class Um extends wi{constructor(){super(...arguments),this.type=4}j(e){this.element.toggleAttribute(this.name,!!e&&e!==ne)}}class Hm extends wi{constructor(e,t,i,o,r){super(e,t,i,o,r),this.type=5}_$AI(e,t=this){if((e=Pt(this,e,t,0)??ne)===Ft)return;const i=this._$AH,o=e===ne&&i!==ne||e.capture!==i.capture||e.once!==i.once||e.passive!==i.passive,r=e!==ne&&(i===ne||o);o&&this.element.removeEventListener(this.name,this,i),r&&this.element.addEventListener(this.name,this,e),this._$AH=e}handleEvent(e){typeof this._$AH=="function"?this._$AH.call(this.options?.host??this.element,e):this._$AH.handleEvent(e)}}class jm{constructor(e,t,i){this.element=e,this.type=6,this._$AN=void 0,this._$AM=t,this.options=i}get _$AU(){return this._$AM._$AU}_$AI(e){Pt(this,e)}}const qm=nr.litHtmlPolyfillSupport;qm?.(ls,Cs),(nr.litHtmlVersions??=[]).push("3.3.1");const Wm=(s,e,t)=>{const i=t?.renderBefore??e;let o=i._$litPart$;if(o===void 0){const r=t?.renderBefore??null;i._$litPart$=o=new Cs(e.insertBefore(ns(),r),r,void 0,t??{})}return o._$AI(s),o};/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const lr=globalThis;class is extends kt{constructor(){super(...arguments),this.renderOptions={host:this},this._$Do=void 0}createRenderRoot(){const e=super.createRenderRoot();return this.renderOptions.renderBefore??=e.firstChild,e}update(e){const t=this.render();this.hasUpdated||(this.renderOptions.isConnected=this.isConnected),super.update(e),this._$Do=Wm(t,this.renderRoot,this.renderOptions)}connectedCallback(){super.connectedCallback(),this._$Do?.setConnected(!0)}disconnectedCallback(){super.disconnectedCallback(),this._$Do?.setConnected(!1)}render(){return Ft}}is._$litElement$=!0,is.finalized=!0,lr.litElementHydrateSupport?.({LitElement:is});const Km=lr.litElementPolyfillSupport;Km?.({LitElement:is});(lr.litElementVersions??=[]).push("4.2.1");/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const Gm=s=>(e,t)=>{t!==void 0?t.addInitializer((()=>{customElements.define(s,e)})):customElements.define(s,e)};/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const Zm={attribute:!0,type:String,converter:si,reflect:!1,hasChanged:rr},Ym=(s=Zm,e,t)=>{const{kind:i,metadata:o}=t;let r=globalThis.litPropertyMetadata.get(o);if(r===void 0&&globalThis.litPropertyMetadata.set(o,r=new Map),i==="setter"&&((s=Object.create(s)).wrapped=!0),r.set(t.name,s),i==="accessor"){const{name:n}=t;return{set(a){const l=e.get.call(this);e.set.call(this,a),this.requestUpdate(n,l,s)},init(a){return a!==void 0&&this.C(n,void 0,s,a),a}}}if(i==="setter"){const{name:n}=t;return function(a){const l=this[n];e.call(this,a),this.requestUpdate(n,l,s)}}throw Error("Unsupported decorator location: "+i)};function Jm(s){return(e,t)=>typeof t=="object"?Ym(s,e,t):((i,o,r)=>{const n=o.hasOwnProperty(r);return o.constructor.createProperty(r,i),n?Object.getOwnPropertyDescriptor(o,r):void 0})(s,e,t)}/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const Rl=(s,e,t)=>(t.configurable=!0,t.enumerable=!0,Reflect.decorate&&typeof e!="object"&&Object.defineProperty(s,e,t),t);/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */function Xm(s,e){return(t,i,o)=>{const r=n=>n.renderRoot?.querySelector(s)??null;return Rl(t,i,{get(){return r(this)}})}}/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */let Qm;function eb(s){return(e,t)=>Rl(e,t,{get(){return(this.renderRoot??(Qm??=document.createDocumentFragment())).querySelectorAll(s)}})}var tb=Object.defineProperty,sb=Object.getOwnPropertyDescriptor,Ei=(s,e,t,i)=>{for(var o=i>1?void 0:i?sb(e,t):e,r=s.length-1,n;r>=0;r--)(n=s[r])&&(o=(i?n(e,t,o):n(o))||o);return i&&o&&tb(e,t,o),o};let cs=class extends is{constructor(){super(...arguments),this.state=Craft.getCookie("sidebar")??"expanded"}connectedCallback(){super.connectedCallback(),this.trigger&&(this.trigger.addEventListener("open",this.expand.bind(this)),this.trigger.addEventListener("close",this.collapse.bind(this))),this.state==="expanded"?this.expand():this.collapse()}disconnectedCallback(){super.disconnectedCallback(),this.trigger&&(this.trigger.removeEventListener("open",this.expand.bind(this)),this.trigger.removeEventListener("close",this.collapse.bind(this))),this.state="expanded"}itemHasTooltip(s){return s.querySelector("craft-tooltip")}createTooltips(){this.items?.forEach(s=>s.setAttribute("icon-only",!0))}destroyTooltips(){this.items?.forEach(s=>s.removeAttribute("icon-only"))}expand(){document.body.setAttribute("data-sidebar","expanded"),Craft.setCookie("sidebar","expanded"),this.destroyTooltips()}collapse(){document.body.setAttribute("data-sidebar","collapsed"),Craft.setCookie("sidebar","collapsed"),this.createTooltips()}createRenderRoot(){return this}};Ei([eb("craft-nav-item")],cs.prototype,"items",2);Ei([Xm("#sidebar-trigger")],cs.prototype,"trigger",2);Ei([Jm({reflect:!0})],cs.prototype,"state",2);cs=Ei([Gm("cp-global-sidebar")],cs);
