const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["./bg-BG-BqLSXSgK.js","./bg-Ch91FBqZ.js","./cs-CZ-BOieS6Re.js","./cs-Bco-9vYd.js","./de-DE-NiEdSbeI.js","./de--MUj2jPW.js","./en-AU-5SYH9YrO.js","./en-QBEFuq4A.js","./en-GB-5SYH9YrO.js","./en-US-5SYH9YrO.js","./es-ES-BzB2G1H7.js","./es-QUDKKOEt.js","./fr-FR-D8x_WpSN.js","./fr-Crw_WS9R.js","./fr-BE-D8x_WpSN.js","./hu-HU-DzuJRq2x.js","./hu-BzLNk3Oy.js","./it-IT-BVziFtOr.js","./it-Dk-tLV60.js","./nl-BE-Cv6cOJ-k.js","./nl-ukLmcyhE.js","./nl-NL-Cv6cOJ-k.js","./pl-PL-C3QXGAg0.js","./pl-BsbBHKbu.js","./ro-RO-BHOQwu0O.js","./ro-BWWeoMIS.js","./ru-RU-DCvtZjBo.js","./ru-D87QXJFw.js","./sk-SK-DaLB_sM8.js","./sk-DCOU_ZI_.js","./tr-TR-Dhk7tqKh.js","./tr-92apvQxK.js","./uk-UA-BP_5Rplg.js","./uk-CGlal3kJ.js"])))=>i.map(i=>d[i]);
const yl="modulepreload",wl=function(s,e){return new URL(s,e).href},Yo={},D=function(e,t,r){let o=Promise.resolve();if(t&&t.length>0){let d=function(c){return Promise.all(c.map(h=>Promise.resolve(h).then(g=>({status:"fulfilled",value:g}),g=>({status:"rejected",reason:g}))))};const n=document.getElementsByTagName("link"),a=document.querySelector("meta[property=csp-nonce]"),l=a?.nonce||a?.getAttribute("nonce");o=d(t.map(c=>{if(c=wl(c,r),c in Yo)return;Yo[c]=!0;const h=c.endsWith(".css"),g=h?'[rel="stylesheet"]':"";if(r)for(let m=n.length-1;m>=0;m--){const b=n[m];if(b.href===c&&(!h||b.rel==="stylesheet"))return}else if(document.querySelector(`link[href="${c}"]${g}`))return;const f=document.createElement("link");if(f.rel=h?"stylesheet":yl,h||(f.as="script"),f.crossOrigin="",f.href=c,l&&f.setAttribute("nonce",l),document.head.appendChild(f),h)return new Promise((m,b)=>{f.addEventListener("load",m),f.addEventListener("error",()=>b(new Error(`Unable to preload CSS for ${c}`)))})}))}function i(n){const a=new Event("vite:preloadError",{cancelable:!0});if(a.payload=n,window.dispatchEvent(a),!a.defaultPrevented)throw n}return o.then(n=>{for(const a of n||[])a.status==="rejected"&&i(a.reason);return e().catch(i)})},cn=class extends HTMLElement{constructor(){super(...arguments),this.cookieName=null,this.state="collapsed",this.expanded=!1,this.handleOpen=()=>{this.trigger?.setAttribute("aria-expanded","true"),this.expanded=!0,this.dispatchEvent(new CustomEvent("open")),this.target&&(this.target.dataset.state="expanded"),this.cookieName&&window.Craft?.setCookie(this.cookieName,"expanded")},this.handleClose=()=>{this.trigger?.setAttribute("aria-expanded","false"),this.expanded=!1,this.dispatchEvent(new CustomEvent("close")),this.target&&(this.target.dataset.state="collapsed"),this.cookieName&&window.Craft?.setCookie(this.cookieName,"collapsed")}}get trigger(){return this.querySelector('button[type="button"]')}get target(){if(!this.trigger)return console.warn("No trigger found for disclosure."),null;const e=this.trigger.getAttribute("aria-controls");return e?document.getElementById(e):(console.warn("No target selector found for disclosure."),null)}connectedCallback(){if(!this.trigger){console.error("craft-disclosure elements must include a button",this);return}if(!this.target){console.error(`No target with id ${this.trigger.getAttribute("aria-controls")} found for disclosure. `,this.trigger);return}this.cookieName=this.getAttribute("cookie-name"),this.state=this.getAttribute("state")??"expanded",this.trigger.setAttribute("aria-expanded",this.state==="expanded"?"true":"false"),this.trigger.addEventListener("click",this.toggle.bind(this)),this.state==="expanded"?this.open():this.close()}disconnectedCallback(){this.open(),this.trigger?.removeEventListener("click",this.toggle.bind(this))}attributeChangedCallback(e,t,r){e==="state"&&(r==="expanded"?this.handleOpen():this.handleClose())}toggle(){this.expanded?this.close():this.open()}open(){this.setAttribute("state","expanded")}close(){this.setAttribute("state","collapsed")}};cn.observedAttributes=["state"];let _l=cn;customElements.get("craft-disclosure")||customElements.define("craft-disclosure",_l);const As=globalThis,co=As.ShadowRoot&&(As.ShadyCSS===void 0||As.ShadyCSS.nativeShadow)&&"adoptedStyleSheets"in Document.prototype&&"replace"in CSSStyleSheet.prototype,uo=Symbol(),Xo=new WeakMap;let dn=class{constructor(e,t,r){if(this._$cssResult$=!0,r!==uo)throw Error("CSSResult is not constructable. Use `unsafeCSS` or `css` instead.");this.cssText=e,this.t=t}get styleSheet(){let e=this.o;const t=this.t;if(co&&e===void 0){const r=t!==void 0&&t.length===1;r&&(e=Xo.get(t)),e===void 0&&((this.o=e=new CSSStyleSheet).replaceSync(this.cssText),r&&Xo.set(t,e))}return e}toString(){return this.cssText}};const un=s=>new dn(typeof s=="string"?s:s+"",void 0,uo),P=(s,...e)=>{const t=s.length===1?s[0]:e.reduce(((r,o,i)=>r+(n=>{if(n._$cssResult$===!0)return n.cssText;if(typeof n=="number")return n;throw Error("Value passed to 'css' function must be a 'css' function result: "+n+". Use 'unsafeCSS' to pass non-literal values, but take care to ensure page security.")})(o)+s[i+1]),s[0]);return new dn(t,s,uo)},ho=(s,e)=>{if(co)s.adoptedStyleSheets=e.map((t=>t instanceof CSSStyleSheet?t:t.styleSheet));else for(const t of e){const r=document.createElement("style"),o=As.litNonce;o!==void 0&&r.setAttribute("nonce",o),r.textContent=t.cssText,s.appendChild(r)}},Zo=co?s=>s:s=>s instanceof CSSStyleSheet?(e=>{let t="";for(const r of e.cssRules)t+=r.cssText;return un(t)})(s):s,{is:$l,defineProperty:El,getOwnPropertyDescriptor:xl,getOwnPropertyNames:kl,getOwnPropertySymbols:Cl,getPrototypeOf:Sl}=Object,Xs=globalThis,Qo=Xs.trustedTypes,Al=Qo?Qo.emptyScript:"",Tl=Xs.reactiveElementPolyfillSupport,Gt=(s,e)=>s,Ds={toAttribute(s,e){switch(e){case Boolean:s=s?Al:null;break;case Object:case Array:s=s==null?s:JSON.stringify(s)}return s},fromAttribute(s,e){let t=s;switch(e){case Boolean:t=s!==null;break;case Number:t=s===null?null:Number(s);break;case Object:case Array:try{t=JSON.parse(s)}catch{t=null}}return t}},po=(s,e)=>!$l(s,e),ei={attribute:!0,type:String,converter:Ds,reflect:!1,useDefault:!1,hasChanged:po};Symbol.metadata??=Symbol("metadata"),Xs.litPropertyMetadata??=new WeakMap;let yt=class extends HTMLElement{static addInitializer(e){this._$Ei(),(this.l??=[]).push(e)}static get observedAttributes(){return this.finalize(),this._$Eh&&[...this._$Eh.keys()]}static createProperty(e,t=ei){if(t.state&&(t.attribute=!1),this._$Ei(),this.prototype.hasOwnProperty(e)&&((t=Object.create(t)).wrapped=!0),this.elementProperties.set(e,t),!t.noAccessor){const r=Symbol(),o=this.getPropertyDescriptor(e,r,t);o!==void 0&&El(this.prototype,e,o)}}static getPropertyDescriptor(e,t,r){const{get:o,set:i}=xl(this.prototype,e)??{get(){return this[t]},set(n){this[t]=n}};return{get:o,set(n){const a=o?.call(this);i?.call(this,n),this.requestUpdate(e,a,r)},configurable:!0,enumerable:!0}}static getPropertyOptions(e){return this.elementProperties.get(e)??ei}static _$Ei(){if(this.hasOwnProperty(Gt("elementProperties")))return;const e=Sl(this);e.finalize(),e.l!==void 0&&(this.l=[...e.l]),this.elementProperties=new Map(e.elementProperties)}static finalize(){if(this.hasOwnProperty(Gt("finalized")))return;if(this.finalized=!0,this._$Ei(),this.hasOwnProperty(Gt("properties"))){const t=this.properties,r=[...kl(t),...Cl(t)];for(const o of r)this.createProperty(o,t[o])}const e=this[Symbol.metadata];if(e!==null){const t=litPropertyMetadata.get(e);if(t!==void 0)for(const[r,o]of t)this.elementProperties.set(r,o)}this._$Eh=new Map;for(const[t,r]of this.elementProperties){const o=this._$Eu(t,r);o!==void 0&&this._$Eh.set(o,t)}this.elementStyles=this.finalizeStyles(this.styles)}static finalizeStyles(e){const t=[];if(Array.isArray(e)){const r=new Set(e.flat(1/0).reverse());for(const o of r)t.unshift(Zo(o))}else e!==void 0&&t.push(Zo(e));return t}static _$Eu(e,t){const r=t.attribute;return r===!1?void 0:typeof r=="string"?r:typeof e=="string"?e.toLowerCase():void 0}constructor(){super(),this._$Ep=void 0,this.isUpdatePending=!1,this.hasUpdated=!1,this._$Em=null,this._$Ev()}_$Ev(){this._$ES=new Promise((e=>this.enableUpdating=e)),this._$AL=new Map,this._$E_(),this.requestUpdate(),this.constructor.l?.forEach((e=>e(this)))}addController(e){(this._$EO??=new Set).add(e),this.renderRoot!==void 0&&this.isConnected&&e.hostConnected?.()}removeController(e){this._$EO?.delete(e)}_$E_(){const e=new Map,t=this.constructor.elementProperties;for(const r of t.keys())this.hasOwnProperty(r)&&(e.set(r,this[r]),delete this[r]);e.size>0&&(this._$Ep=e)}createRenderRoot(){const e=this.shadowRoot??this.attachShadow(this.constructor.shadowRootOptions);return ho(e,this.constructor.elementStyles),e}connectedCallback(){this.renderRoot??=this.createRenderRoot(),this.enableUpdating(!0),this._$EO?.forEach((e=>e.hostConnected?.()))}enableUpdating(e){}disconnectedCallback(){this._$EO?.forEach((e=>e.hostDisconnected?.()))}attributeChangedCallback(e,t,r){this._$AK(e,r)}_$ET(e,t){const r=this.constructor.elementProperties.get(e),o=this.constructor._$Eu(e,r);if(o!==void 0&&r.reflect===!0){const i=(r.converter?.toAttribute!==void 0?r.converter:Ds).toAttribute(t,r.type);this._$Em=e,i==null?this.removeAttribute(o):this.setAttribute(o,i),this._$Em=null}}_$AK(e,t){const r=this.constructor,o=r._$Eh.get(e);if(o!==void 0&&this._$Em!==o){const i=r.getPropertyOptions(o),n=typeof i.converter=="function"?{fromAttribute:i.converter}:i.converter?.fromAttribute!==void 0?i.converter:Ds;this._$Em=o;const a=n.fromAttribute(t,i.type);this[o]=a??this._$Ej?.get(o)??a,this._$Em=null}}requestUpdate(e,t,r){if(e!==void 0){const o=this.constructor,i=this[e];if(r??=o.getPropertyOptions(e),!((r.hasChanged??po)(i,t)||r.useDefault&&r.reflect&&i===this._$Ej?.get(e)&&!this.hasAttribute(o._$Eu(e,r))))return;this.C(e,t,r)}this.isUpdatePending===!1&&(this._$ES=this._$EP())}C(e,t,{useDefault:r,reflect:o,wrapped:i},n){r&&!(this._$Ej??=new Map).has(e)&&(this._$Ej.set(e,n??t??this[e]),i!==!0||n!==void 0)||(this._$AL.has(e)||(this.hasUpdated||r||(t=void 0),this._$AL.set(e,t)),o===!0&&this._$Em!==e&&(this._$Eq??=new Set).add(e))}async _$EP(){this.isUpdatePending=!0;try{await this._$ES}catch(t){Promise.reject(t)}const e=this.scheduleUpdate();return e!=null&&await e,!this.isUpdatePending}scheduleUpdate(){return this.performUpdate()}performUpdate(){if(!this.isUpdatePending)return;if(!this.hasUpdated){if(this.renderRoot??=this.createRenderRoot(),this._$Ep){for(const[o,i]of this._$Ep)this[o]=i;this._$Ep=void 0}const r=this.constructor.elementProperties;if(r.size>0)for(const[o,i]of r){const{wrapped:n}=i,a=this[o];n!==!0||this._$AL.has(o)||a===void 0||this.C(o,void 0,i,a)}}let e=!1;const t=this._$AL;try{e=this.shouldUpdate(t),e?(this.willUpdate(t),this._$EO?.forEach((r=>r.hostUpdate?.())),this.update(t)):this._$EM()}catch(r){throw e=!1,this._$EM(),r}e&&this._$AE(t)}willUpdate(e){}_$AE(e){this._$EO?.forEach((t=>t.hostUpdated?.())),this.hasUpdated||(this.hasUpdated=!0,this.firstUpdated(e)),this.updated(e)}_$EM(){this._$AL=new Map,this.isUpdatePending=!1}get updateComplete(){return this.getUpdateComplete()}getUpdateComplete(){return this._$ES}shouldUpdate(e){return!0}update(e){this._$Eq&&=this._$Eq.forEach((t=>this._$ET(t,this[t]))),this._$EM()}updated(e){}firstUpdated(e){}};yt.elementStyles=[],yt.shadowRootOptions={mode:"open"},yt[Gt("elementProperties")]=new Map,yt[Gt("finalized")]=new Map,Tl?.({ReactiveElement:yt}),(Xs.reactiveElementVersions??=[]).push("2.1.1");const mo=globalThis,Vs=mo.trustedTypes,ti=Vs?Vs.createPolicy("lit-html",{createHTML:s=>s}):void 0,hn="$lit$",Je=`lit$${Math.random().toFixed(9).slice(2)}$`,pn="?"+Je,Ol=`<${pn}>`,ht=document,es=()=>ht.createComment(""),ts=s=>s===null||typeof s!="object"&&typeof s!="function",fo=Array.isArray,Ll=s=>fo(s)||typeof s?.[Symbol.iterator]=="function",yr=`[ 	
\f\r]`,It=/<(?:(!--|\/[^a-zA-Z])|(\/?[a-zA-Z][^>\s]*)|(\/?$))/g,si=/-->/g,ri=/>/g,tt=RegExp(`>|${yr}(?:([^\\s"'>=/]+)(${yr}*=${yr}*(?:[^ 	
\f\r"'\`<>=]|("|')|))|$)`,"g"),oi=/'/g,ii=/"/g,mn=/^(?:script|style|textarea|title)$/i,Nl=s=>(e,...t)=>({_$litType$:s,strings:e,values:t}),E=Nl(1),Ne=Symbol.for("lit-noChange"),V=Symbol.for("lit-nothing"),ni=new WeakMap,nt=ht.createTreeWalker(ht,129);function fn(s,e){if(!fo(s)||!s.hasOwnProperty("raw"))throw Error("invalid template strings array");return ti!==void 0?ti.createHTML(e):e}const Rl=(s,e)=>{const t=s.length-1,r=[];let o,i=e===2?"<svg>":e===3?"<math>":"",n=It;for(let a=0;a<t;a++){const l=s[a];let d,c,h=-1,g=0;for(;g<l.length&&(n.lastIndex=g,c=n.exec(l),c!==null);)g=n.lastIndex,n===It?c[1]==="!--"?n=si:c[1]!==void 0?n=ri:c[2]!==void 0?(mn.test(c[2])&&(o=RegExp("</"+c[2],"g")),n=tt):c[3]!==void 0&&(n=tt):n===tt?c[0]===">"?(n=o??It,h=-1):c[1]===void 0?h=-2:(h=n.lastIndex-c[2].length,d=c[1],n=c[3]===void 0?tt:c[3]==='"'?ii:oi):n===ii||n===oi?n=tt:n===si||n===ri?n=It:(n=tt,o=void 0);const f=n===tt&&s[a+1].startsWith("/>")?" ":"";i+=n===It?l+Ol:h>=0?(r.push(d),l.slice(0,h)+hn+l.slice(h)+Je+f):l+Je+(h===-2?a:f)}return[fn(s,i+(s[t]||"<?>")+(e===2?"</svg>":e===3?"</math>":"")),r]};let Ur=class gn{constructor({strings:e,_$litType$:t},r){let o;this.parts=[];let i=0,n=0;const a=e.length-1,l=this.parts,[d,c]=Rl(e,t);if(this.el=gn.createElement(d,r),nt.currentNode=this.el.content,t===2||t===3){const h=this.el.content.firstChild;h.replaceWith(...h.childNodes)}for(;(o=nt.nextNode())!==null&&l.length<a;){if(o.nodeType===1){if(o.hasAttributes())for(const h of o.getAttributeNames())if(h.endsWith(hn)){const g=c[n++],f=o.getAttribute(h).split(Je),m=/([.?@])?(.*)/.exec(g);l.push({type:1,index:i,name:m[2],strings:f,ctor:m[1]==="."?Fl:m[1]==="?"?Ml:m[1]==="@"?Dl:Zs}),o.removeAttribute(h)}else h.startsWith(Je)&&(l.push({type:6,index:i}),o.removeAttribute(h));if(mn.test(o.tagName)){const h=o.textContent.split(Je),g=h.length-1;if(g>0){o.textContent=Vs?Vs.emptyScript:"";for(let f=0;f<g;f++)o.append(h[f],es()),nt.nextNode(),l.push({type:2,index:++i});o.append(h[g],es())}}}else if(o.nodeType===8)if(o.data===pn)l.push({type:2,index:i});else{let h=-1;for(;(h=o.data.indexOf(Je,h+1))!==-1;)l.push({type:7,index:i}),h+=Je.length-1}i++}}static createElement(e,t){const r=ht.createElement("template");return r.innerHTML=e,r}};function xt(s,e,t=s,r){if(e===Ne)return e;let o=r!==void 0?t._$Co?.[r]:t._$Cl;const i=ts(e)?void 0:e._$litDirective$;return o?.constructor!==i&&(o?._$AO?.(!1),i===void 0?o=void 0:(o=new i(s),o._$AT(s,t,r)),r!==void 0?(t._$Co??=[])[r]=o:t._$Cl=o),o!==void 0&&(e=xt(s,o._$AS(s,e.values),o,r)),e}let Pl=class{constructor(e,t){this._$AV=[],this._$AN=void 0,this._$AD=e,this._$AM=t}get parentNode(){return this._$AM.parentNode}get _$AU(){return this._$AM._$AU}u(e){const{el:{content:t},parts:r}=this._$AD,o=(e?.creationScope??ht).importNode(t,!0);nt.currentNode=o;let i=nt.nextNode(),n=0,a=0,l=r[0];for(;l!==void 0;){if(n===l.index){let d;l.type===2?d=new go(i,i.nextSibling,this,e):l.type===1?d=new l.ctor(i,l.name,l.strings,this,e):l.type===6&&(d=new Vl(i,this,e)),this._$AV.push(d),l=r[++a]}n!==l?.index&&(i=nt.nextNode(),n++)}return nt.currentNode=ht,o}p(e){let t=0;for(const r of this._$AV)r!==void 0&&(r.strings!==void 0?(r._$AI(e,r,t),t+=r.strings.length-2):r._$AI(e[t])),t++}},go=class bn{get _$AU(){return this._$AM?._$AU??this._$Cv}constructor(e,t,r,o){this.type=2,this._$AH=V,this._$AN=void 0,this._$AA=e,this._$AB=t,this._$AM=r,this.options=o,this._$Cv=o?.isConnected??!0}get parentNode(){let e=this._$AA.parentNode;const t=this._$AM;return t!==void 0&&e?.nodeType===11&&(e=t.parentNode),e}get startNode(){return this._$AA}get endNode(){return this._$AB}_$AI(e,t=this){e=xt(this,e,t),ts(e)?e===V||e==null||e===""?(this._$AH!==V&&this._$AR(),this._$AH=V):e!==this._$AH&&e!==Ne&&this._(e):e._$litType$!==void 0?this.$(e):e.nodeType!==void 0?this.T(e):Ll(e)?this.k(e):this._(e)}O(e){return this._$AA.parentNode.insertBefore(e,this._$AB)}T(e){this._$AH!==e&&(this._$AR(),this._$AH=this.O(e))}_(e){this._$AH!==V&&ts(this._$AH)?this._$AA.nextSibling.data=e:this.T(ht.createTextNode(e)),this._$AH=e}$(e){const{values:t,_$litType$:r}=e,o=typeof r=="number"?this._$AC(e):(r.el===void 0&&(r.el=Ur.createElement(fn(r.h,r.h[0]),this.options)),r);if(this._$AH?._$AD===o)this._$AH.p(t);else{const i=new Pl(o,this),n=i.u(this.options);i.p(t),this.T(n),this._$AH=i}}_$AC(e){let t=ni.get(e.strings);return t===void 0&&ni.set(e.strings,t=new Ur(e)),t}k(e){fo(this._$AH)||(this._$AH=[],this._$AR());const t=this._$AH;let r,o=0;for(const i of e)o===t.length?t.push(r=new bn(this.O(es()),this.O(es()),this,this.options)):r=t[o],r._$AI(i),o++;o<t.length&&(this._$AR(r&&r._$AB.nextSibling,o),t.length=o)}_$AR(e=this._$AA.nextSibling,t){for(this._$AP?.(!1,!0,t);e!==this._$AB;){const r=e.nextSibling;e.remove(),e=r}}setConnected(e){this._$AM===void 0&&(this._$Cv=e,this._$AP?.(e))}},Zs=class{get tagName(){return this.element.tagName}get _$AU(){return this._$AM._$AU}constructor(e,t,r,o,i){this.type=1,this._$AH=V,this._$AN=void 0,this.element=e,this.name=t,this._$AM=o,this.options=i,r.length>2||r[0]!==""||r[1]!==""?(this._$AH=Array(r.length-1).fill(new String),this.strings=r):this._$AH=V}_$AI(e,t=this,r,o){const i=this.strings;let n=!1;if(i===void 0)e=xt(this,e,t,0),n=!ts(e)||e!==this._$AH&&e!==Ne,n&&(this._$AH=e);else{const a=e;let l,d;for(e=i[0],l=0;l<i.length-1;l++)d=xt(this,a[r+l],t,l),d===Ne&&(d=this._$AH[l]),n||=!ts(d)||d!==this._$AH[l],d===V?e=V:e!==V&&(e+=(d??"")+i[l+1]),this._$AH[l]=d}n&&!o&&this.j(e)}j(e){e===V?this.element.removeAttribute(this.name):this.element.setAttribute(this.name,e??"")}},Fl=class extends Zs{constructor(){super(...arguments),this.type=3}j(e){this.element[this.name]=e===V?void 0:e}},Ml=class extends Zs{constructor(){super(...arguments),this.type=4}j(e){this.element.toggleAttribute(this.name,!!e&&e!==V)}},Dl=class extends Zs{constructor(e,t,r,o,i){super(e,t,r,o,i),this.type=5}_$AI(e,t=this){if((e=xt(this,e,t,0)??V)===Ne)return;const r=this._$AH,o=e===V&&r!==V||e.capture!==r.capture||e.once!==r.once||e.passive!==r.passive,i=e!==V&&(r===V||o);o&&this.element.removeEventListener(this.name,this,r),i&&this.element.addEventListener(this.name,this,e),this._$AH=e}handleEvent(e){typeof this._$AH=="function"?this._$AH.call(this.options?.host??this.element,e):this._$AH.handleEvent(e)}},Vl=class{constructor(e,t,r){this.element=e,this.type=6,this._$AN=void 0,this._$AM=t,this.options=r}get _$AU(){return this._$AM._$AU}_$AI(e){xt(this,e)}};const zl=mo.litHtmlPolyfillSupport;zl?.(Ur,go),(mo.litHtmlVersions??=[]).push("3.3.1");const Br=(s,e,t)=>{const r=t?.renderBefore??e;let o=r._$litPart$;if(o===void 0){const i=t?.renderBefore??null;r._$litPart$=o=new go(e.insertBefore(es(),i),i,void 0,t??{})}return o._$AI(s),o},bo=globalThis;let Y=class extends yt{constructor(){super(...arguments),this.renderOptions={host:this},this._$Do=void 0}createRenderRoot(){const e=super.createRenderRoot();return this.renderOptions.renderBefore??=e.firstChild,e}update(e){const t=this.render();this.hasUpdated||(this.renderOptions.isConnected=this.isConnected),super.update(e),this._$Do=Br(t,this.renderRoot,this.renderOptions)}connectedCallback(){super.connectedCallback(),this._$Do?.setConnected(!0)}disconnectedCallback(){super.disconnectedCallback(),this._$Do?.setConnected(!1)}render(){return Ne}};Y._$litElement$=!0,Y.finalized=!0,bo.litElementHydrateSupport?.({LitElement:Y});const Il=bo.litElementPolyfillSupport;Il?.({LitElement:Y});(bo.litElementVersions??=[]).push("4.2.1");const Ul=P`
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
`,vn=class extends Y{render(){return E`
      <div tabindex="-1">
        <div class="spinner"></div>
        <span class="message visually-hidden"><slot /></span>
      </div>
    `}};vn.styles=[Ul];let Bl=vn;customElements.get("craft-spinner")||customElements.define("craft-spinner",Bl);const jr=new Set,$t=new Map;let it,vo="ltr",yo="en";const yn=typeof MutationObserver<"u"&&typeof document<"u"&&typeof document.documentElement<"u";if(yn){const s=new MutationObserver(_n);vo=document.documentElement.dir||"ltr",yo=document.documentElement.lang||navigator.language,s.observe(document.documentElement,{attributes:!0,attributeFilter:["dir","lang"]})}function wn(...s){s.map(e=>{const t=e.$code.toLowerCase();$t.has(t)?$t.set(t,Object.assign(Object.assign({},$t.get(t)),e)):$t.set(t,e),it||(it=e)}),_n()}function _n(){yn&&(vo=document.documentElement.dir||"ltr",yo=document.documentElement.lang||navigator.language),[...jr.keys()].map(s=>{typeof s.requestUpdate=="function"&&s.requestUpdate()})}let jl=class{constructor(e){this.host=e,this.host.addController(this)}hostConnected(){jr.add(this.host)}hostDisconnected(){jr.delete(this.host)}dir(){return`${this.host.dir||vo}`.toLowerCase()}lang(){return`${this.host.lang||yo}`.toLowerCase()}getTranslationData(e){var t,r;const o=new Intl.Locale(e.replace(/_/g,"-")),i=o?.language.toLowerCase(),n=(r=(t=o?.region)===null||t===void 0?void 0:t.toLowerCase())!==null&&r!==void 0?r:"",a=$t.get(`${i}-${n}`),l=$t.get(i);return{locale:o,language:i,region:n,primary:a,secondary:l}}exists(e,t){var r;const{primary:o,secondary:i}=this.getTranslationData((r=t.lang)!==null&&r!==void 0?r:this.lang());return t=Object.assign({includeFallback:!1},t),!!(o&&o[e]||i&&i[e]||t.includeFallback&&it&&it[e])}term(e,...t){const{primary:r,secondary:o}=this.getTranslationData(this.lang());let i;if(r&&r[e])i=r[e];else if(o&&o[e])i=o[e];else if(it&&it[e])i=it[e];else return console.error(`No translation found for: ${String(e)}`),String(e);return typeof i=="function"?i(...t):i}date(e,t){return e=new Date(e),new Intl.DateTimeFormat(this.lang(),t).format(e)}number(e,t){return e=Number(e),isNaN(e)?"":new Intl.NumberFormat(this.lang(),t).format(e)}relativeTime(e,t,r){return new Intl.RelativeTimeFormat(this.lang(),r).format(e,t)}};var $n={$code:"en",$name:"English",$dir:"ltr",carousel:"Carousel",clearEntry:"Clear entry",close:"Close",copied:"Copied",copy:"Copy",currentValue:"Current value",error:"Error",goToSlide:(s,e)=>`Go to slide ${s} of ${e}`,hidePassword:"Hide password",loading:"Loading",nextSlide:"Next slide",numOptionsSelected:s=>s===0?"No options selected":s===1?"1 option selected":`${s} options selected`,pauseAnimation:"Pause animation",playAnimation:"Play animation",previousSlide:"Previous slide",progress:"Progress",remove:"Remove",resize:"Resize",scrollableRegion:"Scrollable region",scrollToEnd:"Scroll to end",scrollToStart:"Scroll to start",selectAColorFromTheScreen:"Select a color from the screen",showPassword:"Show password",slideNum:s=>`Slide ${s}`,toggleColorFormat:"Toggle color format",zoomIn:"Zoom in",zoomOut:"Zoom out"};wn($n);var ql=$n,Nt=class extends jl{};wn(ql);var ns=class extends Event{constructor(){super("wa-after-hide",{bubbles:!0,cancelable:!1,composed:!0})}},as=class extends Event{constructor(){super("wa-after-show",{bubbles:!0,cancelable:!1,composed:!0})}},ls=class extends Event{constructor(e){super("wa-hide",{bubbles:!0,cancelable:!0,composed:!0}),this.detail=e}},cs=class extends Event{constructor(){super("wa-show",{bubbles:!0,cancelable:!0,composed:!0})}};function de(s,e){return new Promise(t=>{const r=new AbortController,{signal:o}=r;if(s.classList.contains(e))return;s.classList.remove(e),s.classList.add(e);let i=()=>{s.classList.remove(e),t(),r.abort()};s.addEventListener("animationend",i,{once:!0,signal:o}),s.addEventListener("animationcancel",i,{once:!0,signal:o})})}const Hl={attribute:!0,type:String,converter:Ds,reflect:!1,hasChanged:po},Wl=(s=Hl,e,t)=>{const{kind:r,metadata:o}=t;let i=globalThis.litPropertyMetadata.get(o);if(i===void 0&&globalThis.litPropertyMetadata.set(o,i=new Map),r==="setter"&&((s=Object.create(s)).wrapped=!0),i.set(t.name,s),r==="accessor"){const{name:n}=t;return{set(a){const l=e.get.call(this);e.set.call(this,a),this.requestUpdate(n,l,s)},init(a){return a!==void 0&&this.C(n,void 0,s,a),a}}}if(r==="setter"){const{name:n}=t;return function(a){const l=this[n];e.call(this,a),this.requestUpdate(n,l,s)}}throw Error("Unsupported decorator location: "+r)};function w(s){return(e,t)=>typeof t=="object"?Wl(s,e,t):((r,o,i)=>{const n=o.hasOwnProperty(i);return o.constructor.createProperty(i,r),n?Object.getOwnPropertyDescriptor(o,i):void 0})(s,e,t)}const Ae=s=>(e,t)=>{t!==void 0?t.addInitializer((()=>{customElements.define(s,e)})):customElements.define(s,e)};var Kl=Object.defineProperty,Gl=Object.getOwnPropertyDescriptor,En=s=>{throw TypeError(s)},v=(s,e,t,r)=>{for(var o=r>1?void 0:r?Gl(e,t):e,i=s.length-1,n;i>=0;i--)(n=s[i])&&(o=(r?n(e,t,o):n(o))||o);return r&&o&&Kl(e,t,o),o},xn=(s,e,t)=>e.has(s)||En("Cannot "+t),Jl=(s,e,t)=>(xn(s,e,"read from private field"),e.get(s)),Yl=(s,e,t)=>e.has(s)?En("Cannot add the same private member more than once"):e instanceof WeakSet?e.add(s):e.set(s,t),Xl=(s,e,t,r)=>(xn(s,e,"write to private field"),e.set(s,t),t),Zl=`:host {
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
`,Ts,ge=class extends Y{constructor(){super(),Yl(this,Ts,!1),this.initialReflectedProperties=new Map,this.didSSR=!!this.shadowRoot,this.customStates={set:(t,r)=>{if(this.internals?.states)try{r?this.internals.states.add(t):this.internals.states.delete(t)}catch(o){if(String(o).includes("must start with '--'"))console.error("Your browser implements an outdated version of CustomStateSet. Consider using a polyfill");else throw o}},has:t=>{if(!this.internals?.states)return!1;try{return this.internals.states.has(t)}catch{return!1}}};try{this.internals=this.attachInternals()}catch{console.error("Element internals are not supported in your browser. Consider using a polyfill")}this.customStates.set("wa-defined",!0);let e=this.constructor;for(let[t,r]of e.elementProperties)r.default==="inherit"&&r.initial!==void 0&&typeof t=="string"&&this.customStates.set(`initial-${t}-${r.initial}`,!0)}static get styles(){const e=Array.isArray(this.css)?this.css:this.css?[this.css]:[];return[Zl,...e].map(t=>typeof t=="string"?un(t):t)}attributeChangedCallback(e,t,r){Jl(this,Ts)||(this.constructor.elementProperties.forEach((o,i)=>{o.reflect&&this[i]!=null&&this.initialReflectedProperties.set(i,this[i])}),Xl(this,Ts,!0)),super.attributeChangedCallback(e,t,r)}willUpdate(e){super.willUpdate(e),this.initialReflectedProperties.forEach((t,r)=>{e.has(r)&&this[r]==null&&(this[r]=t)})}firstUpdated(e){super.firstUpdated(e),this.didSSR&&this.shadowRoot?.querySelectorAll("slot").forEach(t=>{t.dispatchEvent(new Event("slotchange",{bubbles:!0,composed:!1,cancelable:!1}))})}update(e){try{super.update(e)}catch(t){if(this.didSSR&&!this.hasUpdated){const r=new Event("lit-hydration-error",{bubbles:!0,composed:!0,cancelable:!1});r.error=t,this.dispatchEvent(r)}throw t}}relayNativeEvent(e,t){e.stopImmediatePropagation(),this.dispatchEvent(new e.constructor(e.type,{...e,...t}))}};Ts=new WeakMap;v([w()],ge.prototype,"dir",2);v([w()],ge.prototype,"lang",2);v([w({type:Boolean,reflect:!0,attribute:"did-ssr"})],ge.prototype,"didSSR",2);const kn=(s,e,t)=>(t.configurable=!0,t.enumerable=!0,Reflect.decorate&&typeof e!="object"&&Object.defineProperty(s,e,t),t);function ee(s,e){return(t,r,o)=>{const i=n=>n.renderRoot?.querySelector(s)??null;return kn(t,r,{get(){return i(this)}})}}const wo={ATTRIBUTE:1,CHILD:2},_o=s=>(...e)=>({_$litDirective$:s,values:e});let $o=class{constructor(e){}get _$AU(){return this._$AM._$AU}_$AT(e,t,r){this._$Ct=e,this._$AM=t,this._$Ci=r}_$AS(e,t){return this.update(e,t)}update(e,t){return this.render(...t)}};const Re=_o(class extends $o{constructor(s){if(super(s),s.type!==wo.ATTRIBUTE||s.name!=="class"||s.strings?.length>2)throw Error("`classMap()` can only be used in the `class` attribute and must be the only part in the attribute.")}render(s){return" "+Object.keys(s).filter((e=>s[e])).join(" ")+" "}update(s,[e]){if(this.st===void 0){this.st=new Set,s.strings!==void 0&&(this.nt=new Set(s.strings.join(" ").split(/\s/).filter((r=>r!==""))));for(const r in e)e[r]&&!this.nt?.has(r)&&this.st.add(r);return this.render(e)}const t=s.element.classList;for(const r of this.st)r in e||(t.remove(r),this.st.delete(r));for(const r in e){const o=!!e[r];o===this.st.has(r)||this.nt?.has(r)||(o?(t.add(r),this.st.add(r)):(t.remove(r),this.st.delete(r)))}return Ne}});var Ql=class extends Event{constructor(){super("wa-reposition",{bubbles:!0,cancelable:!1,composed:!0})}};const Xe=Math.min,ye=Math.max,zs=Math.round,$s=Math.floor,Le=s=>({x:s,y:s}),ec={left:"right",right:"left",bottom:"top",top:"bottom"},tc={start:"end",end:"start"};function qr(s,e,t){return ye(s,Xe(e,t))}function Rt(s,e){return typeof s=="function"?s(e):s}function Ze(s){return s.split("-")[0]}function Pt(s){return s.split("-")[1]}function Cn(s){return s==="x"?"y":"x"}function Eo(s){return s==="y"?"height":"width"}const sc=new Set(["top","bottom"]);function ze(s){return sc.has(Ze(s))?"y":"x"}function xo(s){return Cn(ze(s))}function rc(s,e,t){t===void 0&&(t=!1);const r=Pt(s),o=xo(s),i=Eo(o);let n=o==="x"?r===(t?"end":"start")?"right":"left":r==="start"?"bottom":"top";return e.reference[i]>e.floating[i]&&(n=Is(n)),[n,Is(n)]}function oc(s){const e=Is(s);return[Hr(s),e,Hr(e)]}function Hr(s){return s.replace(/start|end/g,e=>tc[e])}const ai=["left","right"],li=["right","left"],ic=["top","bottom"],nc=["bottom","top"];function ac(s,e,t){switch(s){case"top":case"bottom":return t?e?li:ai:e?ai:li;case"left":case"right":return e?ic:nc;default:return[]}}function lc(s,e,t,r){const o=Pt(s);let i=ac(Ze(s),t==="start",r);return o&&(i=i.map(n=>n+"-"+o),e&&(i=i.concat(i.map(Hr)))),i}function Is(s){return s.replace(/left|right|bottom|top/g,e=>ec[e])}function cc(s){return{top:0,right:0,bottom:0,left:0,...s}}function Sn(s){return typeof s!="number"?cc(s):{top:s,right:s,bottom:s,left:s}}function Us(s){const{x:e,y:t,width:r,height:o}=s;return{width:r,height:o,top:t,left:e,right:e+r,bottom:t+o,x:e,y:t}}function ci(s,e,t){let{reference:r,floating:o}=s;const i=ze(e),n=xo(e),a=Eo(n),l=Ze(e),d=i==="y",c=r.x+r.width/2-o.width/2,h=r.y+r.height/2-o.height/2,g=r[a]/2-o[a]/2;let f;switch(l){case"top":f={x:c,y:r.y-o.height};break;case"bottom":f={x:c,y:r.y+r.height};break;case"right":f={x:r.x+r.width,y:h};break;case"left":f={x:r.x-o.width,y:h};break;default:f={x:r.x,y:r.y}}switch(Pt(e)){case"start":f[n]-=g*(t&&d?-1:1);break;case"end":f[n]+=g*(t&&d?-1:1);break}return f}const dc=async(s,e,t)=>{const{placement:r="bottom",strategy:o="absolute",middleware:i=[],platform:n}=t,a=i.filter(Boolean),l=await(n.isRTL==null?void 0:n.isRTL(e));let d=await n.getElementRects({reference:s,floating:e,strategy:o}),{x:c,y:h}=ci(d,r,l),g=r,f={},m=0;for(let b=0;b<a.length;b++){const{name:_,fn:$}=a[b],{x,y:A,data:S,reset:B}=await $({x:c,y:h,initialPlacement:r,placement:g,strategy:o,middlewareData:f,rects:d,platform:n,elements:{reference:s,floating:e}});c=x??c,h=A??h,f={...f,[_]:{...f[_],...S}},B&&m<=50&&(m++,typeof B=="object"&&(B.placement&&(g=B.placement),B.rects&&(d=B.rects===!0?await n.getElementRects({reference:s,floating:e,strategy:o}):B.rects),{x:c,y:h}=ci(d,g,l)),b=-1)}return{x:c,y:h,placement:g,strategy:o,middlewareData:f}};async function ko(s,e){var t;e===void 0&&(e={});const{x:r,y:o,platform:i,rects:n,elements:a,strategy:l}=s,{boundary:d="clippingAncestors",rootBoundary:c="viewport",elementContext:h="floating",altBoundary:g=!1,padding:f=0}=Rt(e,s),m=Sn(f),b=a[g?h==="floating"?"reference":"floating":h],_=Us(await i.getClippingRect({element:(t=await(i.isElement==null?void 0:i.isElement(b)))==null||t?b:b.contextElement||await(i.getDocumentElement==null?void 0:i.getDocumentElement(a.floating)),boundary:d,rootBoundary:c,strategy:l})),$=h==="floating"?{x:r,y:o,width:n.floating.width,height:n.floating.height}:n.reference,x=await(i.getOffsetParent==null?void 0:i.getOffsetParent(a.floating)),A=await(i.isElement==null?void 0:i.isElement(x))?await(i.getScale==null?void 0:i.getScale(x))||{x:1,y:1}:{x:1,y:1},S=Us(i.convertOffsetParentRelativeRectToViewportRelativeRect?await i.convertOffsetParentRelativeRectToViewportRelativeRect({elements:a,rect:$,offsetParent:x,strategy:l}):$);return{top:(_.top-S.top+m.top)/A.y,bottom:(S.bottom-_.bottom+m.bottom)/A.y,left:(_.left-S.left+m.left)/A.x,right:(S.right-_.right+m.right)/A.x}}const uc=s=>({name:"arrow",options:s,async fn(e){const{x:t,y:r,placement:o,rects:i,platform:n,elements:a,middlewareData:l}=e,{element:d,padding:c=0}=Rt(s,e)||{};if(d==null)return{};const h=Sn(c),g={x:t,y:r},f=xo(o),m=Eo(f),b=await n.getDimensions(d),_=f==="y",$=_?"top":"left",x=_?"bottom":"right",A=_?"clientHeight":"clientWidth",S=i.reference[m]+i.reference[f]-g[f]-i.floating[m],B=g[f]-i.reference[f],z=await(n.getOffsetParent==null?void 0:n.getOffsetParent(d));let I=z?z[A]:0;(!I||!await(n.isElement==null?void 0:n.isElement(z)))&&(I=a.floating[A]||i.floating[m]);const X=S/2-B/2,U=I/2-b[m]/2-1,M=Xe(h[$],U),ve=Xe(h[x],U),se=M,u=I-b[m]-ve,k=I/2-b[m]/2+X,T=qr(se,k,u),L=!l.arrow&&Pt(o)!=null&&k!==T&&i.reference[m]/2-(k<se?M:ve)-b[m]/2<0,N=L?k<se?k-se:k-u:0;return{[f]:g[f]+N,data:{[f]:T,centerOffset:k-T-N,...L&&{alignmentOffset:N}},reset:L}}}),hc=function(s){return s===void 0&&(s={}),{name:"flip",options:s,async fn(e){var t,r;const{placement:o,middlewareData:i,rects:n,initialPlacement:a,platform:l,elements:d}=e,{mainAxis:c=!0,crossAxis:h=!0,fallbackPlacements:g,fallbackStrategy:f="bestFit",fallbackAxisSideDirection:m="none",flipAlignment:b=!0,..._}=Rt(s,e);if((t=i.arrow)!=null&&t.alignmentOffset)return{};const $=Ze(o),x=ze(a),A=Ze(a)===a,S=await(l.isRTL==null?void 0:l.isRTL(d.floating)),B=g||(A||!b?[Is(a)]:oc(a)),z=m!=="none";!g&&z&&B.push(...lc(a,b,m,S));const I=[a,...B],X=await ko(e,_),U=[];let M=((r=i.flip)==null?void 0:r.overflows)||[];if(c&&U.push(X[$]),h){const k=rc(o,n,S);U.push(X[k[0]],X[k[1]])}if(M=[...M,{placement:o,overflows:U}],!U.every(k=>k<=0)){var ve,se;const k=(((ve=i.flip)==null?void 0:ve.index)||0)+1,T=I[k];if(T&&(!(h==="alignment"&&x!==ze(T))||M.every(N=>ze(N.placement)===x?N.overflows[0]>0:!0)))return{data:{index:k,overflows:M},reset:{placement:T}};let L=(se=M.filter(N=>N.overflows[0]<=0).sort((N,C)=>N.overflows[1]-C.overflows[1])[0])==null?void 0:se.placement;if(!L)switch(f){case"bestFit":{var u;const N=(u=M.filter(C=>{if(z){const H=ze(C.placement);return H===x||H==="y"}return!0}).map(C=>[C.placement,C.overflows.filter(H=>H>0).reduce((H,pe)=>H+pe,0)]).sort((C,H)=>C[1]-H[1])[0])==null?void 0:u[0];N&&(L=N);break}case"initialPlacement":L=a;break}if(o!==L)return{reset:{placement:L}}}return{}}}},pc=new Set(["left","top"]);async function mc(s,e){const{placement:t,platform:r,elements:o}=s,i=await(r.isRTL==null?void 0:r.isRTL(o.floating)),n=Ze(t),a=Pt(t),l=ze(t)==="y",d=pc.has(n)?-1:1,c=i&&l?-1:1,h=Rt(e,s);let{mainAxis:g,crossAxis:f,alignmentAxis:m}=typeof h=="number"?{mainAxis:h,crossAxis:0,alignmentAxis:null}:{mainAxis:h.mainAxis||0,crossAxis:h.crossAxis||0,alignmentAxis:h.alignmentAxis};return a&&typeof m=="number"&&(f=a==="end"?m*-1:m),l?{x:f*c,y:g*d}:{x:g*d,y:f*c}}const fc=function(s){return s===void 0&&(s=0),{name:"offset",options:s,async fn(e){var t,r;const{x:o,y:i,placement:n,middlewareData:a}=e,l=await mc(e,s);return n===((t=a.offset)==null?void 0:t.placement)&&(r=a.arrow)!=null&&r.alignmentOffset?{}:{x:o+l.x,y:i+l.y,data:{...l,placement:n}}}}},gc=function(s){return s===void 0&&(s={}),{name:"shift",options:s,async fn(e){const{x:t,y:r,placement:o}=e,{mainAxis:i=!0,crossAxis:n=!1,limiter:a={fn:_=>{let{x:$,y:x}=_;return{x:$,y:x}}},...l}=Rt(s,e),d={x:t,y:r},c=await ko(e,l),h=ze(Ze(o)),g=Cn(h);let f=d[g],m=d[h];if(i){const _=g==="y"?"top":"left",$=g==="y"?"bottom":"right",x=f+c[_],A=f-c[$];f=qr(x,f,A)}if(n){const _=h==="y"?"top":"left",$=h==="y"?"bottom":"right",x=m+c[_],A=m-c[$];m=qr(x,m,A)}const b=a.fn({...e,[g]:f,[h]:m});return{...b,data:{x:b.x-t,y:b.y-r,enabled:{[g]:i,[h]:n}}}}}},bc=function(s){return s===void 0&&(s={}),{name:"size",options:s,async fn(e){var t,r;const{placement:o,rects:i,platform:n,elements:a}=e,{apply:l=()=>{},...d}=Rt(s,e),c=await ko(e,d),h=Ze(o),g=Pt(o),f=ze(o)==="y",{width:m,height:b}=i.floating;let _,$;h==="top"||h==="bottom"?(_=h,$=g===(await(n.isRTL==null?void 0:n.isRTL(a.floating))?"start":"end")?"left":"right"):($=h,_=g==="end"?"top":"bottom");const x=b-c.top-c.bottom,A=m-c.left-c.right,S=Xe(b-c[_],x),B=Xe(m-c[$],A),z=!e.middlewareData.shift;let I=S,X=B;if((t=e.middlewareData.shift)!=null&&t.enabled.x&&(X=A),(r=e.middlewareData.shift)!=null&&r.enabled.y&&(I=x),z&&!g){const M=ye(c.left,0),ve=ye(c.right,0),se=ye(c.top,0),u=ye(c.bottom,0);f?X=m-2*(M!==0||ve!==0?M+ve:ye(c.left,c.right)):I=b-2*(se!==0||u!==0?se+u:ye(c.top,c.bottom))}await l({...e,availableWidth:X,availableHeight:I});const U=await n.getDimensions(a.floating);return m!==U.width||b!==U.height?{reset:{rects:!0}}:{}}}};function Qs(){return typeof window<"u"}function Ft(s){return An(s)?(s.nodeName||"").toLowerCase():"#document"}function we(s){var e;return(s==null||(e=s.ownerDocument)==null?void 0:e.defaultView)||window}function Fe(s){var e;return(e=(An(s)?s.ownerDocument:s.document)||window.document)==null?void 0:e.documentElement}function An(s){return Qs()?s instanceof Node||s instanceof we(s).Node:!1}function xe(s){return Qs()?s instanceof Element||s instanceof we(s).Element:!1}function Pe(s){return Qs()?s instanceof HTMLElement||s instanceof we(s).HTMLElement:!1}function di(s){return!Qs()||typeof ShadowRoot>"u"?!1:s instanceof ShadowRoot||s instanceof we(s).ShadowRoot}const vc=new Set(["inline","contents"]);function ds(s){const{overflow:e,overflowX:t,overflowY:r,display:o}=ke(s);return/auto|scroll|overlay|hidden|clip/.test(e+r+t)&&!vc.has(o)}const yc=new Set(["table","td","th"]);function wc(s){return yc.has(Ft(s))}const _c=[":popover-open",":modal"];function er(s){return _c.some(e=>{try{return s.matches(e)}catch{return!1}})}const $c=["transform","translate","scale","rotate","perspective"],Ec=["transform","translate","scale","rotate","perspective","filter"],xc=["paint","layout","strict","content"];function tr(s){const e=Co(),t=xe(s)?ke(s):s;return $c.some(r=>t[r]?t[r]!=="none":!1)||(t.containerType?t.containerType!=="normal":!1)||!e&&(t.backdropFilter?t.backdropFilter!=="none":!1)||!e&&(t.filter?t.filter!=="none":!1)||Ec.some(r=>(t.willChange||"").includes(r))||xc.some(r=>(t.contain||"").includes(r))}function kc(s){let e=Qe(s);for(;Pe(e)&&!kt(e);){if(tr(e))return e;if(er(e))return null;e=Qe(e)}return null}function Co(){return typeof CSS>"u"||!CSS.supports?!1:CSS.supports("-webkit-backdrop-filter","none")}const Cc=new Set(["html","body","#document"]);function kt(s){return Cc.has(Ft(s))}function ke(s){return we(s).getComputedStyle(s)}function sr(s){return xe(s)?{scrollLeft:s.scrollLeft,scrollTop:s.scrollTop}:{scrollLeft:s.scrollX,scrollTop:s.scrollY}}function Qe(s){if(Ft(s)==="html")return s;const e=s.assignedSlot||s.parentNode||di(s)&&s.host||Fe(s);return di(e)?e.host:e}function Tn(s){const e=Qe(s);return kt(e)?s.ownerDocument?s.ownerDocument.body:s.body:Pe(e)&&ds(e)?e:Tn(e)}function Ct(s,e,t){var r;e===void 0&&(e=[]),t===void 0&&(t=!0);const o=Tn(s),i=o===((r=s.ownerDocument)==null?void 0:r.body),n=we(o);if(i){const a=Wr(n);return e.concat(n,n.visualViewport||[],ds(o)?o:[],a&&t?Ct(a):[])}return e.concat(o,Ct(o,[],t))}function Wr(s){return s.parent&&Object.getPrototypeOf(s.parent)?s.frameElement:null}function On(s){const e=ke(s);let t=parseFloat(e.width)||0,r=parseFloat(e.height)||0;const o=Pe(s),i=o?s.offsetWidth:t,n=o?s.offsetHeight:r,a=zs(t)!==i||zs(r)!==n;return a&&(t=i,r=n),{width:t,height:r,$:a}}function So(s){return xe(s)?s:s.contextElement}function Et(s){const e=So(s);if(!Pe(e))return Le(1);const t=e.getBoundingClientRect(),{width:r,height:o,$:i}=On(e);let n=(i?zs(t.width):t.width)/r,a=(i?zs(t.height):t.height)/o;return(!n||!Number.isFinite(n))&&(n=1),(!a||!Number.isFinite(a))&&(a=1),{x:n,y:a}}const Sc=Le(0);function Ln(s){const e=we(s);return!Co()||!e.visualViewport?Sc:{x:e.visualViewport.offsetLeft,y:e.visualViewport.offsetTop}}function Ac(s,e,t){return e===void 0&&(e=!1),!t||e&&t!==we(s)?!1:e}function pt(s,e,t,r){e===void 0&&(e=!1),t===void 0&&(t=!1);const o=s.getBoundingClientRect(),i=So(s);let n=Le(1);e&&(r?xe(r)&&(n=Et(r)):n=Et(s));const a=Ac(i,t,r)?Ln(i):Le(0);let l=(o.left+a.x)/n.x,d=(o.top+a.y)/n.y,c=o.width/n.x,h=o.height/n.y;if(i){const g=we(i),f=r&&xe(r)?we(r):r;let m=g,b=Wr(m);for(;b&&r&&f!==m;){const _=Et(b),$=b.getBoundingClientRect(),x=ke(b),A=$.left+(b.clientLeft+parseFloat(x.paddingLeft))*_.x,S=$.top+(b.clientTop+parseFloat(x.paddingTop))*_.y;l*=_.x,d*=_.y,c*=_.x,h*=_.y,l+=A,d+=S,m=we(b),b=Wr(m)}}return Us({width:c,height:h,x:l,y:d})}function rr(s,e){const t=sr(s).scrollLeft;return e?e.left+t:pt(Fe(s)).left+t}function Nn(s,e){const t=s.getBoundingClientRect(),r=t.left+e.scrollLeft-rr(s,t),o=t.top+e.scrollTop;return{x:r,y:o}}function Tc(s){let{elements:e,rect:t,offsetParent:r,strategy:o}=s;const i=o==="fixed",n=Fe(r),a=e?er(e.floating):!1;if(r===n||a&&i)return t;let l={scrollLeft:0,scrollTop:0},d=Le(1);const c=Le(0),h=Pe(r);if((h||!h&&!i)&&((Ft(r)!=="body"||ds(n))&&(l=sr(r)),Pe(r))){const f=pt(r);d=Et(r),c.x=f.x+r.clientLeft,c.y=f.y+r.clientTop}const g=n&&!h&&!i?Nn(n,l):Le(0);return{width:t.width*d.x,height:t.height*d.y,x:t.x*d.x-l.scrollLeft*d.x+c.x+g.x,y:t.y*d.y-l.scrollTop*d.y+c.y+g.y}}function Oc(s){return Array.from(s.getClientRects())}function Lc(s){const e=Fe(s),t=sr(s),r=s.ownerDocument.body,o=ye(e.scrollWidth,e.clientWidth,r.scrollWidth,r.clientWidth),i=ye(e.scrollHeight,e.clientHeight,r.scrollHeight,r.clientHeight);let n=-t.scrollLeft+rr(s);const a=-t.scrollTop;return ke(r).direction==="rtl"&&(n+=ye(e.clientWidth,r.clientWidth)-o),{width:o,height:i,x:n,y:a}}const ui=25;function Nc(s,e){const t=we(s),r=Fe(s),o=t.visualViewport;let i=r.clientWidth,n=r.clientHeight,a=0,l=0;if(o){i=o.width,n=o.height;const c=Co();(!c||c&&e==="fixed")&&(a=o.offsetLeft,l=o.offsetTop)}const d=rr(r);if(d<=0){const c=r.ownerDocument,h=c.body,g=getComputedStyle(h),f=c.compatMode==="CSS1Compat"&&parseFloat(g.marginLeft)+parseFloat(g.marginRight)||0,m=Math.abs(r.clientWidth-h.clientWidth-f);m<=ui&&(i-=m)}else d<=ui&&(i+=d);return{width:i,height:n,x:a,y:l}}const Rc=new Set(["absolute","fixed"]);function Pc(s,e){const t=pt(s,!0,e==="fixed"),r=t.top+s.clientTop,o=t.left+s.clientLeft,i=Pe(s)?Et(s):Le(1),n=s.clientWidth*i.x,a=s.clientHeight*i.y,l=o*i.x,d=r*i.y;return{width:n,height:a,x:l,y:d}}function hi(s,e,t){let r;if(e==="viewport")r=Nc(s,t);else if(e==="document")r=Lc(Fe(s));else if(xe(e))r=Pc(e,t);else{const o=Ln(s);r={x:e.x-o.x,y:e.y-o.y,width:e.width,height:e.height}}return Us(r)}function Rn(s,e){const t=Qe(s);return t===e||!xe(t)||kt(t)?!1:ke(t).position==="fixed"||Rn(t,e)}function Fc(s,e){const t=e.get(s);if(t)return t;let r=Ct(s,[],!1).filter(a=>xe(a)&&Ft(a)!=="body"),o=null;const i=ke(s).position==="fixed";let n=i?Qe(s):s;for(;xe(n)&&!kt(n);){const a=ke(n),l=tr(n);!l&&a.position==="fixed"&&(o=null),(i?!l&&!o:!l&&a.position==="static"&&o&&Rc.has(o.position)||ds(n)&&!l&&Rn(s,n))?r=r.filter(d=>d!==n):o=a,n=Qe(n)}return e.set(s,r),r}function Mc(s){let{element:e,boundary:t,rootBoundary:r,strategy:o}=s;const i=[...t==="clippingAncestors"?er(e)?[]:Fc(e,this._c):[].concat(t),r],n=i[0],a=i.reduce((l,d)=>{const c=hi(e,d,o);return l.top=ye(c.top,l.top),l.right=Xe(c.right,l.right),l.bottom=Xe(c.bottom,l.bottom),l.left=ye(c.left,l.left),l},hi(e,n,o));return{width:a.right-a.left,height:a.bottom-a.top,x:a.left,y:a.top}}function Dc(s){const{width:e,height:t}=On(s);return{width:e,height:t}}function Vc(s,e,t){const r=Pe(e),o=Fe(e),i=t==="fixed",n=pt(s,!0,i,e);let a={scrollLeft:0,scrollTop:0};const l=Le(0);function d(){l.x=rr(o)}if(r||!r&&!i)if((Ft(e)!=="body"||ds(o))&&(a=sr(e)),r){const f=pt(e,!0,i,e);l.x=f.x+e.clientLeft,l.y=f.y+e.clientTop}else o&&d();i&&!r&&o&&d();const c=o&&!r&&!i?Nn(o,a):Le(0),h=n.left+a.scrollLeft-l.x-c.x,g=n.top+a.scrollTop-l.y-c.y;return{x:h,y:g,width:n.width,height:n.height}}function wr(s){return ke(s).position==="static"}function pi(s,e){if(!Pe(s)||ke(s).position==="fixed")return null;if(e)return e(s);let t=s.offsetParent;return Fe(s)===t&&(t=t.ownerDocument.body),t}function Pn(s,e){const t=we(s);if(er(s))return t;if(!Pe(s)){let o=Qe(s);for(;o&&!kt(o);){if(xe(o)&&!wr(o))return o;o=Qe(o)}return t}let r=pi(s,e);for(;r&&wc(r)&&wr(r);)r=pi(r,e);return r&&kt(r)&&wr(r)&&!tr(r)?t:r||kc(s)||t}const zc=async function(s){const e=this.getOffsetParent||Pn,t=this.getDimensions,r=await t(s.floating);return{reference:Vc(s.reference,await e(s.floating),s.strategy),floating:{x:0,y:0,width:r.width,height:r.height}}};function Ic(s){return ke(s).direction==="rtl"}const Os={convertOffsetParentRelativeRectToViewportRelativeRect:Tc,getDocumentElement:Fe,getClippingRect:Mc,getOffsetParent:Pn,getElementRects:zc,getClientRects:Oc,getDimensions:Dc,getScale:Et,isElement:xe,isRTL:Ic};function Fn(s,e){return s.x===e.x&&s.y===e.y&&s.width===e.width&&s.height===e.height}function Uc(s,e){let t=null,r;const o=Fe(s);function i(){var a;clearTimeout(r),(a=t)==null||a.disconnect(),t=null}function n(a,l){a===void 0&&(a=!1),l===void 0&&(l=1),i();const d=s.getBoundingClientRect(),{left:c,top:h,width:g,height:f}=d;if(a||e(),!g||!f)return;const m=$s(h),b=$s(o.clientWidth-(c+g)),_=$s(o.clientHeight-(h+f)),$=$s(c),x={rootMargin:-m+"px "+-b+"px "+-_+"px "+-$+"px",threshold:ye(0,Xe(1,l))||1};let A=!0;function S(B){const z=B[0].intersectionRatio;if(z!==l){if(!A)return n();z?n(!1,z):r=setTimeout(()=>{n(!1,1e-7)},1e3)}z===1&&!Fn(d,s.getBoundingClientRect())&&n(),A=!1}try{t=new IntersectionObserver(S,{...x,root:o.ownerDocument})}catch{t=new IntersectionObserver(S,x)}t.observe(s)}return n(!0),i}function Mn(s,e,t,r){r===void 0&&(r={});const{ancestorScroll:o=!0,ancestorResize:i=!0,elementResize:n=typeof ResizeObserver=="function",layoutShift:a=typeof IntersectionObserver=="function",animationFrame:l=!1}=r,d=So(s),c=o||i?[...d?Ct(d):[],...Ct(e)]:[];c.forEach($=>{o&&$.addEventListener("scroll",t,{passive:!0}),i&&$.addEventListener("resize",t)});const h=d&&a?Uc(d,t):null;let g=-1,f=null;n&&(f=new ResizeObserver($=>{let[x]=$;x&&x.target===d&&f&&(f.unobserve(e),cancelAnimationFrame(g),g=requestAnimationFrame(()=>{var A;(A=f)==null||A.observe(e)})),t()}),d&&!l&&f.observe(d),f.observe(e));let m,b=l?pt(s):null;l&&_();function _(){const $=pt(s);b&&!Fn(b,$)&&t(),b=$,m=requestAnimationFrame(_)}return t(),()=>{var $;c.forEach(x=>{o&&x.removeEventListener("scroll",t),i&&x.removeEventListener("resize",t)}),h?.(),($=f)==null||$.disconnect(),f=null,l&&cancelAnimationFrame(m)}}const Dn=fc,Vn=gc,zn=hc,mi=bc,Bc=uc,In=(s,e,t)=>{const r=new Map,o={platform:Os,...t},i={...o.platform,_c:r};return dc(s,e,{...o,platform:i})};function jc(s){return qc(s)}function _r(s){return s.assignedSlot?s.assignedSlot:s.parentNode instanceof ShadowRoot?s.parentNode.host:s.parentNode}function qc(s){for(let e=s;e;e=_r(e))if(e instanceof Element&&getComputedStyle(e).display==="none")return null;for(let e=_r(s);e;e=_r(e)){if(!(e instanceof Element))continue;const t=getComputedStyle(e);if(t.display!=="contents"&&(t.position!=="static"||tr(t)||e.tagName==="BODY"))return e}return null}var Hc=`:host {
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
`;function fi(s){return s!==null&&typeof s=="object"&&"getBoundingClientRect"in s&&("contextElement"in s?s instanceof Element:!0)}var Es=globalThis?.HTMLElement?.prototype.hasOwnProperty("popover"),G=class extends ge{constructor(){super(...arguments),this.localize=new Nt(this),this.active=!1,this.placement="top",this.boundary="viewport",this.distance=0,this.skidding=0,this.arrow=!1,this.arrowPlacement="anchor",this.arrowPadding=10,this.flip=!1,this.flipFallbackPlacements="",this.flipFallbackStrategy="best-fit",this.flipPadding=0,this.shift=!1,this.shiftPadding=0,this.autoSizePadding=0,this.hoverBridge=!1,this.updateHoverBridge=()=>{if(this.hoverBridge&&this.anchorEl){const e=this.anchorEl.getBoundingClientRect(),t=this.popup.getBoundingClientRect(),r=this.placement.includes("top")||this.placement.includes("bottom");let o=0,i=0,n=0,a=0,l=0,d=0,c=0,h=0;r?e.top<t.top?(o=e.left,i=e.bottom,n=e.right,a=e.bottom,l=t.left,d=t.top,c=t.right,h=t.top):(o=t.left,i=t.bottom,n=t.right,a=t.bottom,l=e.left,d=e.top,c=e.right,h=e.top):e.left<t.left?(o=e.right,i=e.top,n=t.left,a=t.top,l=e.right,d=e.bottom,c=t.left,h=t.bottom):(o=t.right,i=t.top,n=e.left,a=e.top,l=t.right,d=t.bottom,c=e.left,h=e.bottom),this.style.setProperty("--hover-bridge-top-left-x",`${o}px`),this.style.setProperty("--hover-bridge-top-left-y",`${i}px`),this.style.setProperty("--hover-bridge-top-right-x",`${n}px`),this.style.setProperty("--hover-bridge-top-right-y",`${a}px`),this.style.setProperty("--hover-bridge-bottom-left-x",`${l}px`),this.style.setProperty("--hover-bridge-bottom-left-y",`${d}px`),this.style.setProperty("--hover-bridge-bottom-right-x",`${c}px`),this.style.setProperty("--hover-bridge-bottom-right-y",`${h}px`)}}}async connectedCallback(){super.connectedCallback(),await this.updateComplete,this.start()}disconnectedCallback(){super.disconnectedCallback(),this.stop()}async updated(e){super.updated(e),e.has("active")&&(this.active?this.start():this.stop()),e.has("anchor")&&this.handleAnchorChange(),this.active&&(await this.updateComplete,this.reposition())}async handleAnchorChange(){if(await this.stop(),this.anchor&&typeof this.anchor=="string"){const e=this.getRootNode();this.anchorEl=e.getElementById(this.anchor)}else this.anchor instanceof Element||fi(this.anchor)?this.anchorEl=this.anchor:this.anchorEl=this.querySelector('[slot="anchor"]');this.anchorEl instanceof HTMLSlotElement&&(this.anchorEl=this.anchorEl.assignedElements({flatten:!0})[0]),this.anchorEl&&this.start()}start(){!this.anchorEl||!this.active||(this.popup.showPopover?.(),this.cleanup=Mn(this.anchorEl,this.popup,()=>{this.reposition()}))}async stop(){return new Promise(e=>{this.popup.hidePopover?.(),this.cleanup?(this.cleanup(),this.cleanup=void 0,this.removeAttribute("data-current-placement"),this.style.removeProperty("--auto-size-available-width"),this.style.removeProperty("--auto-size-available-height"),requestAnimationFrame(()=>e())):e()})}reposition(){if(!this.active||!this.anchorEl)return;const e=[Dn({mainAxis:this.distance,crossAxis:this.skidding})];this.sync?e.push(mi({apply:({rects:o})=>{const i=this.sync==="width"||this.sync==="both",n=this.sync==="height"||this.sync==="both";this.popup.style.width=i?`${o.reference.width}px`:"",this.popup.style.height=n?`${o.reference.height}px`:""}})):(this.popup.style.width="",this.popup.style.height="");let t;Es&&!fi(this.anchor)&&this.boundary==="scroll"&&(t=Ct(this.anchorEl).filter(o=>o instanceof Element)),this.flip&&e.push(zn({boundary:this.flipBoundary||t,fallbackPlacements:this.flipFallbackPlacements,fallbackStrategy:this.flipFallbackStrategy==="best-fit"?"bestFit":"initialPlacement",padding:this.flipPadding})),this.shift&&e.push(Vn({boundary:this.shiftBoundary||t,padding:this.shiftPadding})),this.autoSize?e.push(mi({boundary:this.autoSizeBoundary||t,padding:this.autoSizePadding,apply:({availableWidth:o,availableHeight:i})=>{this.autoSize==="vertical"||this.autoSize==="both"?this.style.setProperty("--auto-size-available-height",`${i}px`):this.style.removeProperty("--auto-size-available-height"),this.autoSize==="horizontal"||this.autoSize==="both"?this.style.setProperty("--auto-size-available-width",`${o}px`):this.style.removeProperty("--auto-size-available-width")}})):(this.style.removeProperty("--auto-size-available-width"),this.style.removeProperty("--auto-size-available-height")),this.arrow&&e.push(Bc({element:this.arrowEl,padding:this.arrowPadding}));const r=Es?o=>Os.getOffsetParent(o,jc):Os.getOffsetParent;In(this.anchorEl,this.popup,{placement:this.placement,middleware:e,strategy:Es?"absolute":"fixed",platform:{...Os,getOffsetParent:r}}).then(({x:o,y:i,middlewareData:n,placement:a})=>{const l=this.localize.dir()==="rtl",d={top:"bottom",right:"left",bottom:"top",left:"right"}[a.split("-")[0]];if(this.setAttribute("data-current-placement",a),Object.assign(this.popup.style,{left:`${o}px`,top:`${i}px`}),this.arrow){const c=n.arrow.x,h=n.arrow.y;let g="",f="",m="",b="";if(this.arrowPlacement==="start"){const _=typeof c=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"";g=typeof h=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"",f=l?_:"",b=l?"":_}else if(this.arrowPlacement==="end"){const _=typeof c=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"";f=l?"":_,b=l?_:"",m=typeof h=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:""}else this.arrowPlacement==="center"?(b=typeof c=="number"?"calc(50% - var(--arrow-size-diagonal))":"",g=typeof h=="number"?"calc(50% - var(--arrow-size-diagonal))":""):(b=typeof c=="number"?`${c}px`:"",g=typeof h=="number"?`${h}px`:"");Object.assign(this.arrowEl.style,{top:g,right:f,bottom:m,left:b,[d]:"calc(var(--arrow-size-diagonal) * -1)"})}}),requestAnimationFrame(()=>this.updateHoverBridge()),this.dispatchEvent(new Ql)}render(){return E`
      <slot name="anchor" @slotchange=${this.handleAnchorChange}></slot>

      <span
        part="hover-bridge"
        class=${Re({"popup-hover-bridge":!0,"popup-hover-bridge-visible":this.hoverBridge&&this.active})}
      ></span>

      <div
        popover="manual"
        part="popup"
        class=${Re({popup:!0,"popup-active":this.active,"popup-fixed":!Es,"popup-has-arrow":this.arrow})}
      >
        <slot></slot>
        ${this.arrow?E`<div part="arrow" class="arrow" role="presentation"></div>`:""}
      </div>
    `}};G.css=Hc;v([ee(".popup")],G.prototype,"popup",2);v([ee(".arrow")],G.prototype,"arrowEl",2);v([w()],G.prototype,"anchor",2);v([w({type:Boolean,reflect:!0})],G.prototype,"active",2);v([w({reflect:!0})],G.prototype,"placement",2);v([w()],G.prototype,"boundary",2);v([w({type:Number})],G.prototype,"distance",2);v([w({type:Number})],G.prototype,"skidding",2);v([w({type:Boolean})],G.prototype,"arrow",2);v([w({attribute:"arrow-placement"})],G.prototype,"arrowPlacement",2);v([w({attribute:"arrow-padding",type:Number})],G.prototype,"arrowPadding",2);v([w({type:Boolean})],G.prototype,"flip",2);v([w({attribute:"flip-fallback-placements",converter:{fromAttribute:s=>s.split(" ").map(e=>e.trim()).filter(e=>e!==""),toAttribute:s=>s.join(" ")}})],G.prototype,"flipFallbackPlacements",2);v([w({attribute:"flip-fallback-strategy"})],G.prototype,"flipFallbackStrategy",2);v([w({type:Object})],G.prototype,"flipBoundary",2);v([w({attribute:"flip-padding",type:Number})],G.prototype,"flipPadding",2);v([w({type:Boolean})],G.prototype,"shift",2);v([w({type:Object})],G.prototype,"shiftBoundary",2);v([w({attribute:"shift-padding",type:Number})],G.prototype,"shiftPadding",2);v([w({attribute:"auto-size"})],G.prototype,"autoSize",2);v([w()],G.prototype,"sync",2);v([w({type:Object})],G.prototype,"autoSizeBoundary",2);v([w({attribute:"auto-size-padding",type:Number})],G.prototype,"autoSizePadding",2);v([w({attribute:"hover-bridge",type:Boolean})],G.prototype,"hoverBridge",2);G=v([Ae("wa-popup")],G);const Wc="useandom-26T198340PX75pxJACKVERYMINDBUSHWOLF_GQZbfghjklqvwyzrict";let Kc=(s=21)=>{let e="",t=crypto.getRandomValues(new Uint8Array(s|=0));for(;s--;)e+=Wc[t[s]&63];return e};function Ao(s=""){return`${s}${Kc()}`}function Bs(s,e){return new Promise(t=>{function r(o){o.target===s&&(s.removeEventListener(e,r),t())}s.addEventListener(e,r)})}function _e(s,e){const t={waitUntilFirstUpdate:!1,...e};return(r,o)=>{const{update:i}=r,n=Array.isArray(s)?s:[s];r.update=function(a){n.forEach(l=>{const d=l;if(a.has(d)){const c=a.get(d),h=this[d];c!==h&&(!t.waitUntilFirstUpdate||this.hasUpdated)&&this[o](c,h)}}),i.call(this,a)}}}function be(s){return w({...s,state:!0,attribute:!1})}var Gc=`:host {
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
`,Z=class extends ge{constructor(){super(...arguments),this.placement="top",this.disabled=!1,this.distance=8,this.open=!1,this.skidding=0,this.showDelay=150,this.hideDelay=0,this.trigger="hover focus",this.withoutArrow=!1,this.for=null,this.anchor=null,this.eventController=new AbortController,this.handleBlur=()=>{this.hasTrigger("focus")&&this.hide()},this.handleClick=()=>{this.hasTrigger("click")&&(this.open?this.hide():this.show())},this.handleFocus=()=>{this.hasTrigger("focus")&&this.show()},this.handleDocumentKeyDown=e=>{e.key==="Escape"&&(e.stopPropagation(),this.hide())},this.handleMouseOver=()=>{this.hasTrigger("hover")&&(clearTimeout(this.hoverTimeout),this.hoverTimeout=window.setTimeout(()=>this.show(),this.showDelay))},this.handleMouseOut=()=>{this.hasTrigger("hover")&&(clearTimeout(this.hoverTimeout),this.hoverTimeout=window.setTimeout(()=>this.hide(),this.hideDelay))}}connectedCallback(){super.connectedCallback(),this.eventController.signal.aborted&&(this.eventController=new AbortController),this.open&&(this.open=!1,this.updateComplete.then(()=>{this.open=!0})),this.id||(this.id=Ao("wa-tooltip-")),this.for&&this.anchor?(this.anchor=null,this.handleForChange()):this.for&&this.handleForChange()}disconnectedCallback(){super.disconnectedCallback(),document.removeEventListener("keydown",this.handleDocumentKeyDown),this.eventController.abort(),this.anchor&&this.removeFromAriaLabelledBy(this.anchor,this.id)}firstUpdated(){this.body.hidden=!this.open,this.open&&(this.popup.active=!0,this.popup.reposition())}hasTrigger(e){return this.trigger.split(" ").includes(e)}addToAriaLabelledBy(e,t){const r=(e.getAttribute("aria-labelledby")||"").split(/\s+/).filter(Boolean);r.includes(t)||(r.push(t),e.setAttribute("aria-labelledby",r.join(" ")))}removeFromAriaLabelledBy(e,t){const r=(e.getAttribute("aria-labelledby")||"").split(/\s+/).filter(Boolean).filter(o=>o!==t);r.length>0?e.setAttribute("aria-labelledby",r.join(" ")):e.removeAttribute("aria-labelledby")}async handleOpenChange(){if(this.open){if(this.disabled)return;const e=new cs;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}document.addEventListener("keydown",this.handleDocumentKeyDown,{signal:this.eventController.signal}),this.body.hidden=!1,this.popup.active=!0,await de(this.popup.popup,"show-with-scale"),this.popup.reposition(),this.dispatchEvent(new as)}else{const e=new ls;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}document.removeEventListener("keydown",this.handleDocumentKeyDown),await de(this.popup.popup,"hide-with-scale"),this.popup.active=!1,this.body.hidden=!0,this.dispatchEvent(new ns)}}handleForChange(){const e=this.getRootNode();if(!e)return;const t=this.for?e.getElementById(this.for):null,r=this.anchor;if(t===r)return;const{signal:o}=this.eventController;t&&(this.addToAriaLabelledBy(t,this.id),t.addEventListener("blur",this.handleBlur,{capture:!0,signal:o}),t.addEventListener("focus",this.handleFocus,{capture:!0,signal:o}),t.addEventListener("click",this.handleClick,{signal:o}),t.addEventListener("mouseover",this.handleMouseOver,{signal:o}),t.addEventListener("mouseout",this.handleMouseOut,{signal:o})),r&&(this.removeFromAriaLabelledBy(r,this.id),r.removeEventListener("blur",this.handleBlur,{capture:!0}),r.removeEventListener("focus",this.handleFocus,{capture:!0}),r.removeEventListener("click",this.handleClick),r.removeEventListener("mouseover",this.handleMouseOver),r.removeEventListener("mouseout",this.handleMouseOut)),this.anchor=t}async handleOptionsChange(){this.hasUpdated&&(await this.updateComplete,this.popup.reposition())}handleDisabledChange(){this.disabled&&this.open&&this.hide()}async show(){if(!this.open)return this.open=!0,Bs(this,"wa-after-show")}async hide(){if(this.open)return this.open=!1,Bs(this,"wa-after-hide")}render(){return E`
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
    `}};Z.css=Gc;Z.dependencies={"wa-popup":G};v([ee("slot:not([name])")],Z.prototype,"defaultSlot",2);v([ee(".body")],Z.prototype,"body",2);v([ee("wa-popup")],Z.prototype,"popup",2);v([w()],Z.prototype,"placement",2);v([w({type:Boolean,reflect:!0})],Z.prototype,"disabled",2);v([w({type:Number})],Z.prototype,"distance",2);v([w({type:Boolean,reflect:!0})],Z.prototype,"open",2);v([w({type:Number})],Z.prototype,"skidding",2);v([w({attribute:"show-delay",type:Number})],Z.prototype,"showDelay",2);v([w({attribute:"hide-delay",type:Number})],Z.prototype,"hideDelay",2);v([w()],Z.prototype,"trigger",2);v([w({attribute:"without-arrow",type:Boolean,reflect:!0})],Z.prototype,"withoutArrow",2);v([w()],Z.prototype,"for",2);v([be()],Z.prototype,"anchor",2);v([_e("open",{waitUntilFirstUpdate:!0})],Z.prototype,"handleOpenChange",1);v([_e("for")],Z.prototype,"handleForChange",1);v([_e(["distance","placement","skidding"])],Z.prototype,"handleOptionsChange",1);v([_e("disabled")],Z.prototype,"handleDisabledChange",1);Z=v([Ae("wa-tooltip")],Z);let Jc=class extends Z{static get styles(){return[Z.styles,P`
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
      `]}};customElements.get("c-tooltip")||customElements.define("c-tooltip",Jc);const Yc=P`
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
`;var Xc=P`
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
`,Zc=P`
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
`,Un=Object.defineProperty,gi=Object.getOwnPropertySymbols,Qc=Object.prototype.hasOwnProperty,ed=Object.prototype.propertyIsEnumerable,Bn=s=>{throw TypeError(s)},bi=(s,e,t)=>e in s?Un(s,e,{enumerable:!0,configurable:!0,writable:!0,value:t}):s[e]=t,td=(s,e)=>{for(var t in e||(e={}))Qc.call(e,t)&&bi(s,t,e[t]);if(gi)for(var t of gi(e))ed.call(e,t)&&bi(s,t,e[t]);return s},jn=(s,e,t,r)=>{for(var o=void 0,i=s.length-1,n;i>=0;i--)(n=s[i])&&(o=n(e,t,o)||o);return o&&Un(e,t,o),o},qn=(s,e,t)=>e.has(s)||Bn("Cannot "+t),sd=(s,e,t)=>(qn(s,e,"read from private field"),e.get(s)),rd=(s,e,t)=>e.has(s)?Bn("Cannot add the same private member more than once"):e instanceof WeakSet?e.add(s):e.set(s,t),od=(s,e,t,r)=>(qn(s,e,"write to private field"),e.set(s,t),t),Ls,us=class extends Y{constructor(){super(),rd(this,Ls,!1),this.initialReflectedProperties=new Map,Object.entries(this.constructor.dependencies).forEach(([e,t])=>{this.constructor.define(e,t)})}emit(e,t){const r=new CustomEvent(e,td({bubbles:!0,cancelable:!1,composed:!0,detail:{}},t));return this.dispatchEvent(r),r}static define(e,t=this,r={}){const o=customElements.get(e);if(!o){try{customElements.define(e,t,r)}catch{customElements.define(e,class extends t{},r)}return}let i=" (unknown version)",n=i;"version"in t&&t.version&&(i=" v"+t.version),"version"in o&&o.version&&(n=" v"+o.version),!(i&&n&&i===n)&&console.warn(`Attempted to register <${e}>${i}, but <${e}>${n} has already been registered.`)}attributeChangedCallback(e,t,r){sd(this,Ls)||(this.constructor.elementProperties.forEach((o,i)=>{o.reflect&&this[i]!=null&&this.initialReflectedProperties.set(i,this[i])}),od(this,Ls,!0)),super.attributeChangedCallback(e,t,r)}willUpdate(e){super.willUpdate(e),this.initialReflectedProperties.forEach((t,r)=>{e.has(r)&&this[r]==null&&(this[r]=t)})}};Ls=new WeakMap;us.version="2.20.1";us.dependencies={};jn([w()],us.prototype,"dir");jn([w()],us.prototype,"lang");var Hn=class extends us{render(){return E` <slot></slot> `}};Hn.styles=[Zc,Xc];Hn.define("sl-visually-hidden");var id=Object.defineProperty,To=(s,e,t,r)=>{for(var o=void 0,i=s.length-1,n;i>=0;i--)(n=s[i])&&(o=n(e,t,o)||o);return o&&id(e,t,o),o};const Wn=class extends Y{constructor(){super(...arguments),this.isCopying=!1,this.value="",this.disabled=!1}async copyValue(){if(!(this.isCopying||this.disabled)){this.isCopying=!0;try{await navigator.clipboard.writeText(this.value),this.dispatchEvent(new CustomEvent("craft-copy",{bubbles:!0,cancelable:!1,composed:!0,detail:{value:this.value}}))}catch{this.dispatchEvent(new CustomEvent("craft-error",{cancelable:!1,composed:!0,bubbles:!0}))}finally{this.isCopying=!1}}}render(){return E`
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
    `}};Wn.styles=[Yc];let or=Wn;To([be()],or.prototype,"isCopying");To([w({type:String})],or.prototype,"value");To([w({type:Boolean})],or.prototype,"disabled");customElements.get("craft-copy-button")||customElements.define("craft-copy-button",or);const nd=P`
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
`,ad=P`
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
`;var ld=Object.defineProperty,Ie=(s,e,t,r)=>{for(var o=void 0,i=s.length-1,n;i>=0;i--)(n=s[i])&&(o=n(e,t,o)||o);return o&&ld(e,t,o),o};const je={"icon.in":{keyframes:[{scale:.25,opacity:.25},{scale:1,opacity:1}],options:{duration:100}},"icon.out":{keyframes:[{scale:1,opacity:1},{scale:.25,opacity:.25}],options:{duration:100}}},Kn=class extends Y{constructor(){super(),this.status="rest",this.value="",this.disabled=!1,this.feedbackDuration=1e3,this.tooltipLabel="Copy",this.addEventListener("craft-copy",()=>{this.showStatus("success")}),this.addEventListener("craft-error",()=>{this.showStatus("error")})}getId(){return`attribute-${this.value.replace(/([a-z])([A-Z])/g,"$1-$2").replace(/[\s_]+/g,"-").toLowerCase()}`}async showStatus(e){const t=e==="success"?this.successIconEl:this.errorIconEl;this.tooltipLabel=e==="success"?"Copied":"Copy failed",await t.animate(je["icon.out"].keyframes,je["icon.out"].options),this.copyIconEl.hidden=!0,t.hidden=!1,await t.animate(je["icon.in"].keyframes,je["icon.in"].options),this.status=e,setTimeout(async()=>{await t.animate(je["icon.out"].keyframes,je["icon.out"].options),t.hidden=!0,this.copyIconEl.hidden=!1,await this.copyIconEl.animate(je["icon.in"].keyframes,je["icon.in"].options),this.status="rest",this.tooltipLabel="Copy"},this.feedbackDuration)}render(){return E`
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
    `}};Kn.styles=[ad,nd];let Me=Kn;Ie([be()],Me.prototype,"status");Ie([ee('slot[name="copy-icon"]')],Me.prototype,"copyIconEl");Ie([ee('slot[name="success-icon"]')],Me.prototype,"successIconEl");Ie([ee('slot[name="error-icon"]')],Me.prototype,"errorIconEl");Ie([ee("craft-copy-button")],Me.prototype,"copyButtonEl");Ie([w({type:String})],Me.prototype,"value");Ie([w({type:Boolean,reflect:!0})],Me.prototype,"disabled");Ie([w({attribute:"feedback-duration",type:Number})],Me.prototype,"feedbackDuration");Ie([w({reflect:!1})],Me.prototype,"tooltipLabel");customElements.get("craft-copy-attribute")||customElements.define("craft-copy-attribute",Me);const cd=P`
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
`,Gn=new WeakMap;function dd(s,e){let t=e;for(;t;){if(Gn.get(t)===s)return!0;t=Object.getPrototypeOf(t)}return!1}function ie(s){return e=>{if(dd(s,e))return e;const t=s(e);return Gn.set(t,s),t}}const ud=s=>class extends s{static get properties(){return{disabled:{type:Boolean,reflect:!0}}}constructor(){super(),this._requestedToBeDisabled=!1,this.__isUserSettingDisabled=!0,this.__restoreDisabledTo=!1,this.disabled=!1}makeRequestToBeDisabled(){this._requestedToBeDisabled===!1&&(this._requestedToBeDisabled=!0,this.__restoreDisabledTo=this.disabled,this.__internalSetDisabled(!0))}retractRequestToBeDisabled(){this._requestedToBeDisabled===!0&&(this._requestedToBeDisabled=!1,this.__internalSetDisabled(this.__restoreDisabledTo))}__internalSetDisabled(e){this.__isUserSettingDisabled=!1,this.disabled=e,this.__isUserSettingDisabled=!0}requestUpdate(e,t,r){super.requestUpdate(e,t,r),e==="disabled"&&(this.__isUserSettingDisabled&&(this.__restoreDisabledTo=this.disabled),this.disabled===!1&&this._requestedToBeDisabled===!0&&this.__internalSetDisabled(!0))}click(){this.disabled||super.click()}},hs=ie(ud),hd=s=>class extends hs(s){static get properties(){return{tabIndex:{type:Number,reflect:!0,attribute:"tabindex"}}}constructor(){super(),this.__isUserSettingTabIndex=!0,this.__restoreTabIndexTo=0,this.__internalSetTabIndex(0)}makeRequestToBeDisabled(){super.makeRequestToBeDisabled(),this._requestedToBeDisabled===!1&&this.tabIndex!=null&&(this.__restoreTabIndexTo=this.tabIndex)}retractRequestToBeDisabled(){super.retractRequestToBeDisabled(),this._requestedToBeDisabled===!0&&this.__internalSetTabIndex(this.__restoreTabIndexTo)}static enabledWarnings=super.enabledWarnings?.filter(e=>e!=="change-in-update")||[];__internalSetTabIndex(e){this.__isUserSettingTabIndex=!1,this.tabIndex=e,this.__isUserSettingTabIndex=!0}requestUpdate(e,t,r){super.requestUpdate(e,t,r),e==="disabled"&&(this.disabled?this.__internalSetTabIndex(-1):this.__internalSetTabIndex(this.__restoreTabIndexTo)),e==="tabIndex"&&(this.__isUserSettingTabIndex&&this.tabIndex!=null&&(this.__restoreTabIndexTo=this.tabIndex),this.tabIndex!==-1&&this._requestedToBeDisabled===!0&&this.__internalSetTabIndex(-1))}firstUpdated(e){super.firstUpdated(e),this.disabled&&this.__internalSetTabIndex(-1)}},Jn=ie(hd),$r=s=>s.key===" "||s.key==="Enter",vi=s=>s.key===" ";let pd=class extends Jn(Y){static get properties(){return{active:{type:Boolean,reflect:!0},type:{type:String,reflect:!0}}}render(){return E` <div class="button-content"><slot></slot></div> `}static get styles(){return[P`
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
      `]}constructor(){super(),this.type="button",this.active=!1,this.__setupEvents()}connectedCallback(){super.connectedCallback(),this.hasAttribute("role")||this.setAttribute("role","button")}updated(e){super.updated(e),e.has("disabled")&&(this.disabled?this.setAttribute("aria-disabled","true"):this.getAttribute("aria-disabled")!==null&&this.removeAttribute("aria-disabled"))}__setupEvents(){this.addEventListener("mousedown",this.__mousedownHandler),this.addEventListener("keydown",this.__keydownHandler),this.addEventListener("keyup",this.__keyupHandler)}__mousedownHandler(){this.active=!0;const e=()=>{this.active=!1,document.removeEventListener("mouseup",e),this.removeEventListener("mouseup",e)};document.addEventListener("mouseup",e),this.addEventListener("mouseup",e)}__keydownHandler(e){if(this.active||!$r(e)){vi(e)&&e.preventDefault();return}vi(e)&&e.preventDefault(),this.active=!0;const t=r=>{$r(r)&&(this.active=!1,document.removeEventListener("keyup",t,!0))};document.addEventListener("keyup",t,!0)}__keyupHandler(e){if($r(e)){if(e.target&&e.target!==this)return;this.click()}}},md=class extends pd{constructor(){super(),this.type="reset",this.__setupDelegationInConstructor(),this.__submitAndResetHelperButton=document.createElement("button"),this.__preventEventLeakage=this.__preventEventLeakage.bind(this)}connectedCallback(){super.connectedCallback(),this.updateComplete.then(()=>{this._setupSubmitAndResetHelperOnConnected()})}disconnectedCallback(){super.disconnectedCallback(),this._teardownSubmitAndResetHelperOnDisconnected()}__preventEventLeakage(e){e.target===this.__submitAndResetHelperButton&&e.stopImmediatePropagation()}_setupSubmitAndResetHelperOnConnected(){this.appendChild(this.__submitAndResetHelperButton),this._form=this.__submitAndResetHelperButton.form,this.removeChild(this.__submitAndResetHelperButton),this._form&&this._form.addEventListener("click",this.__preventEventLeakage)}_teardownSubmitAndResetHelperOnDisconnected(){this._form&&this._form.removeEventListener("click",this.__preventEventLeakage)}async __clickDelegationHandler(e){this._form||await this.updateComplete,(this.type==="submit"||this.type==="reset")&&e.target===this&&this._form&&(this.__submitAndResetHelperButton.type=this.type,this._form.appendChild(this.__submitAndResetHelperButton),this.__submitAndResetHelperButton.click(),this._form.removeChild(this.__submitAndResetHelperButton))}__setupDelegationInConstructor(){this.addEventListener("click",this.__clickDelegationHandler,!0)}};const qe=new WeakMap;function fd(){const s=document.createElement("button");return s.tabIndex=-1,s.type="submit",s.setAttribute("aria-hidden","true"),s.style.cssText=`
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
  `,s}let gd=class extends md{get _nativeButtonNode(){return qe.get(this._form)?.helper||null}constructor(){super(),this.type="submit",this.__implicitSubmitHelperButton=null}_setupSubmitAndResetHelperOnConnected(){if(super._setupSubmitAndResetHelperOnConnected(),!this._form||this.type!=="submit")return;const e=this._form;if(!qe.get(this._form)){const t=fd(),r=document.createElement("div");r.appendChild(t),qe.set(this._form,{lionButtons:new Set,helper:t,observer:new MutationObserver(()=>{e.appendChild(r)})}),e.appendChild(r),qe.get(e)?.observer.observe(r,{childList:!0})}qe.get(e)?.lionButtons.add(this)}_teardownSubmitAndResetHelperOnDisconnected(){if(super._teardownSubmitAndResetHelperOnDisconnected(),this._form){const e=qe.get(this._form);e&&(e.lionButtons.delete(this),e.lionButtons.size||(this._form.contains(e.helper)&&e.helper.remove(),qe.get(this._form)?.observer.disconnect(),qe.delete(this._form)))}}};var bd=Object.defineProperty,ir=(s,e,t,r)=>{for(var o=void 0,i=s.length-1,n;i>=0;i--)(n=s[i])&&(o=n(e,t,o)||o);return o&&bd(e,t,o),o};let ps=class extends gd{constructor(){super(...arguments),this.appearance="accent",this.variant="default",this.size="medium",this.loading=!1}static get styles(){return[...super.styles,cd]}render(){return E`
      <div class="button-content" part="content">
        <slot name="prefix" class="prefix" part="prefix"></slot>
        <slot class="label" part="label"></slot>
        <slot name="suffix" class="suffix" part="suffix"></slot>
      </div>
      ${this.loading?E`<craft-spinner part="spinner"></craft-spinner>`:V}
    `}};ir([w({reflect:!0})],ps.prototype,"appearance");ir([w({reflect:!0})],ps.prototype,"variant");ir([w({reflect:!0})],ps.prototype,"size");ir([w({reflect:!0,type:Boolean})],ps.prototype,"loading");customElements.get("craft-button")||customElements.define("craft-button",ps);const vd=P`
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
`;var yd=Object.defineProperty,Yn=(s,e,t,r)=>{for(var o=void 0,i=s.length-1,n;i>=0;i--)(n=s[i])&&(o=n(e,t,o)||o);return o&&yd(e,t,o),o};const Xn=class extends Y{constructor(){super(...arguments),this.label=null,this._gradientId=null}connectedCallback(){super.connectedCallback(),this._gradientId=`avatar-gradient-${Math.random().toString(36).slice(2,8)}`}text(){return this.label?this.label.split(" ").map(e=>e.charAt(0).toUpperCase()).join(""):"?"}render(){return E`
      <span class="avatar">
        <svg
          viewBox="0 0 100 100"
          xmlns="http://www.w3.org/2000/svg"
          role="img"
        >
          ${this.label?E`<title>${this.label}</title>`:""}
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
    `}};Xn.styles=[vd];let Oo=Xn;Yn([w()],Oo.prototype,"label");Yn([be()],Oo.prototype,"_gradientId");customElements.get("craft-avatar")||customElements.define("craft-avatar",Oo);const Zn=P`
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
`,Qn=P`
  :host(:not([label-sr-only])) .form-field__group-one {
    margin-bottom: var(--c-spacing-sm);
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
    margin-bottom: var(--c-spacing-sm);
  }
`,Lo=P`
  ${Qn}

  ::slotted([slot='input']) {
    ${Zn}
  }
`,wd=P`
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
`,_d=s=>s===null||typeof s!="object"&&typeof s!="function",ea=(s,e)=>s?._$litType$!==void 0,$d=s=>s.strings===void 0;function ta(s=""){return`${s.length>0?`${s}-`:""}${Math.random().toString(36).substr(2,10)}`}function Ed(s){return s instanceof Node?"node":ea(s)?"template-result":!Array.isArray(s)&&typeof s=="object"&&"template"in s?"slot-rerender-object":null}const xd=s=>class extends s{get slots(){return{}}constructor(){super(),this.__renderMetaPerSlot=new Map,this.__slotsThatNeedRerender=new Set,this.__slotsProvidedByUserOnFirstConnected=new Set,this.__privateSlots=new Set}connectedCallback(){super.connectedCallback(),this._connectSlotMixin()}__rerenderSlot(e){const t=this.slots[e]();this.__renderTemplateInScopedContext({renderAsDirectHostChild:t.renderAsDirectHostChild,template:t.template,slotName:e}),t.afterRender?.()}update(e){super.update(e);for(const t of this.__slotsThatNeedRerender)this.__rerenderSlot(t)}__renderTemplateInScopedContext({template:e,slotName:t,renderAsDirectHostChild:r}){if(!this.__renderMetaPerSlot.has(t)){const d=!!ShadowRoot.prototype.createElement;this.shadowRoot||console.error("[SlotMixin] No shadowRoot was found");const c=(d?this.shadowRoot:document).createElement("div"),h=document.createComment(`_start_slot_${t}_`),g=document.createComment(`_end_slot_${t}_`);c.appendChild(h),c.appendChild(g);const{creationScope:f,host:m}=this.renderOptions;if(Br(e,c,{renderBefore:g,creationScope:f,host:m}),r){const b=Array.from(c.childNodes);this.__appendNodes({nodes:b,renderParent:this,slotName:t})}else c.slot=t,this.appendChild(c);this.__renderMetaPerSlot.set(t,{renderTargetThatRespectsShadowRootScoping:c,renderBefore:g});return}const{renderBefore:o,renderTargetThatRespectsShadowRootScoping:i}=this.__renderMetaPerSlot.get(t),n=r?this:i,{creationScope:a,host:l}=this.renderOptions;Br(e,n,{creationScope:a,host:l,renderBefore:o}),r&&o.previousElementSibling&&!o.previousElementSibling.slot&&(o.previousElementSibling.slot=t)}__appendNodes({nodes:e,renderParent:t=this,slotName:r}){for(const o of e)o instanceof Element&&r&&r!==""&&o.setAttribute("slot",r),t.appendChild(o)}__initSlots(e){for(const t of e){if(this.__slotsProvidedByUserOnFirstConnected.has(t))continue;const r=this.slots[t]();if(r!==void 0)switch(this.__isConnectedSlotMixin||this.__privateSlots.add(t),Ed(r)){case"template-result":this.__renderTemplateInScopedContext({template:r,renderAsDirectHostChild:!0,slotName:t});break;case"node":this.__appendNodes({nodes:[r],renderParent:this,slotName:t});break;case"slot-rerender-object":this.__slotsThatNeedRerender.add(t),r.firstRenderOnConnected&&this.__rerenderSlot(t);break;default:throw new Error(`Slot "${t}" configured inside "get slots()" (in prototype) of ${this.constructor.name} may return these types: TemplateResult | Node | {template:TemplateResult, afterRender?:function} | undefined.
              You provided: ${r}`)}}}_connectSlotMixin(){if(this.__isConnectedSlotMixin)return;const e=Object.keys(this.slots);for(const t of e)(t===""?Array.from(this.children).find(r=>!r.hasAttribute("slot")):Array.from(this.children).find(r=>r.slot===t))&&this.__slotsProvidedByUserOnFirstConnected.add(t);this.__initSlots(e),this.__isConnectedSlotMixin=!0}_isPrivateSlot(e){return this.__privateSlots.has(e)}},ms=ie(xd);function sa(s,e){return e={exports:{}},s(e,e.exports),e.exports}var st="long",He="short",Er="narrow",q="numeric",We="2-digit",Ke={number:{decimal:{style:"decimal"},integer:{style:"decimal",maximumFractionDigits:0},currency:{style:"currency",currency:"USD"},percent:{style:"percent"},default:{style:"decimal"}},date:{short:{month:q,day:q,year:We},medium:{month:He,day:q,year:q},long:{month:st,day:q,year:q},full:{month:st,day:q,year:q,weekday:st},default:{month:He,day:q,year:q}},time:{short:{hour:q,minute:q},medium:{hour:q,minute:q,second:q},long:{hour:q,minute:q,second:q,timeZoneName:He},full:{hour:q,minute:q,second:q,timeZoneName:He},default:{hour:q,minute:q,second:q}},duration:{default:{hours:{minimumIntegerDigits:1,maximumFractionDigits:0},minutes:{minimumIntegerDigits:2,maximumFractionDigits:0},seconds:{minimumIntegerDigits:2,maximumFractionDigits:3}}},parseNumberPattern:function(s){if(s){var e={},t=s.match(/\b[A-Z]{3}\b/i),r=s.replace(/[^¤]/g,"").length;if(!r&&t&&(r=1),r?(e.style="currency",e.currencyDisplay=r===1?"symbol":r===2?"code":"name",e.currency=t?t[0].toUpperCase():"USD"):s.indexOf("%")>=0&&(e.style="percent"),!/[@#0]/.test(s))return e.style?e:void 0;if(e.useGrouping=s.indexOf(",")>=0,/E\+?[@#0]+/i.test(s)||s.indexOf("@")>=0){var o=s.replace(/E\+?[@#0]+|[^@#0]/gi,"");e.minimumSignificantDigits=Math.min(Math.max(o.replace(/[^@0]/g,"").length,1),21),e.maximumSignificantDigits=Math.min(Math.max(o.length,1),21)}else{for(var i=s.replace(/[^#0.]/g,"").split("."),n=i[0],a=n.length-1;n[a]==="0";)--a;e.minimumIntegerDigits=Math.min(Math.max(n.length-1-a,1),21);var l=i[1]||"";for(a=0;l[a]==="0";)++a;for(e.minimumFractionDigits=Math.min(Math.max(a,0),20);l[a]==="#";)++a;e.maximumFractionDigits=Math.min(Math.max(a,0),20)}return e}},parseDatePattern:function(s){if(s){for(var e={},t=0;t<s.length;){for(var r=s[t],o=1;s[++t]===r;)++o;switch(r){case"G":e.era=o===5?Er:o===4?st:He;break;case"y":case"Y":e.year=o===2?We:q;break;case"M":case"L":o=Math.min(Math.max(o-1,0),4),e.month=[q,We,He,st,Er][o];break;case"E":case"e":case"c":e.weekday=o===5?Er:o===4?st:He;break;case"d":case"D":e.day=o===2?We:q;break;case"h":case"K":e.hour12=!0,e.hour=o===2?We:q;break;case"H":case"k":e.hour12=!1,e.hour=o===2?We:q;break;case"m":e.minute=o===2?We:q;break;case"s":case"S":e.second=o===2?We:q;break;case"z":case"Z":case"v":case"V":e.timeZoneName=o===1?He:st;break}}return Object.keys(e).length?e:void 0}}},kd=function(s,e){if(typeof s=="string"&&e[s])return s;for(var t=[].concat(s||[]),r=0,o=t.length;r<o;++r)for(var i=t[r].split("-");i.length;){var n=i.join("-");if(e[n])return n;i.pop()}},bt="zero",R="one",re="two",K="few",te="many",O="other",p=[function(s){var e=+s;return e===1?R:O},function(s){var e=+s;return 0<=e&&e<=1?R:O},function(s){var e=Math.floor(Math.abs(+s)),t=+s;return e===0||t===1?R:O},function(s){var e=+s;return e===0?bt:e===1?R:e===2?re:3<=e%100&&e%100<=10?K:11<=e%100&&e%100<=99?te:O},function(s){var e=Math.floor(Math.abs(+s)),t=(s+".").split(".")[1].length;return e===1&&t===0?R:O},function(s){var e=+s;return e%10===1&&e%100!==11?R:2<=e%10&&e%10<=4&&(e%100<12||14<e%100)?K:e%10===0||5<=e%10&&e%10<=9||11<=e%100&&e%100<=14?te:O},function(s){var e=+s;return e%10===1&&e%100!==11&&e%100!==71&&e%100!==91?R:e%10===2&&e%100!==12&&e%100!==72&&e%100!==92?re:(3<=e%10&&e%10<=4||e%10===9)&&(e%100<10||19<e%100)&&(e%100<70||79<e%100)&&(e%100<90||99<e%100)?K:e!==0&&e%1e6===0?te:O},function(s){var e=Math.floor(Math.abs(+s)),t=(s+".").split(".")[1].length,r=+(s+".").split(".")[1];return t===0&&e%10===1&&e%100!==11||r%10===1&&r%100!==11?R:t===0&&2<=e%10&&e%10<=4&&(e%100<12||14<e%100)||2<=r%10&&r%10<=4&&(r%100<12||14<r%100)?K:O},function(s){var e=Math.floor(Math.abs(+s)),t=(s+".").split(".")[1].length;return e===1&&t===0?R:2<=e&&e<=4&&t===0?K:t!==0?te:O},function(s){var e=+s;return e===0?bt:e===1?R:e===2?re:e===3?K:e===6?te:O},function(s){var e=Math.floor(Math.abs(+s)),t=+(""+s).replace(/^[^.]*.?|0+$/g,""),r=+s;return r===1||t!==0&&(e===0||e===1)?R:O},function(s){var e=Math.floor(Math.abs(+s)),t=(s+".").split(".")[1].length,r=+(s+".").split(".")[1];return t===0&&e%100===1||r%100===1?R:t===0&&e%100===2||r%100===2?re:t===0&&3<=e%100&&e%100<=4||3<=r%100&&r%100<=4?K:O},function(s){var e=Math.floor(Math.abs(+s));return e===0||e===1?R:O},function(s){var e=Math.floor(Math.abs(+s)),t=(s+".").split(".")[1].length,r=+(s+".").split(".")[1];return t===0&&(e===1||e===2||e===3)||t===0&&e%10!==4&&e%10!==6&&e%10!==9||t!==0&&r%10!==4&&r%10!==6&&r%10!==9?R:O},function(s){var e=+s;return e===1?R:e===2?re:3<=e&&e<=6?K:7<=e&&e<=10?te:O},function(s){var e=+s;return e===1||e===11?R:e===2||e===12?re:3<=e&&e<=10||13<=e&&e<=19?K:O},function(s){var e=Math.floor(Math.abs(+s)),t=(s+".").split(".")[1].length;return t===0&&e%10===1?R:t===0&&e%10===2?re:t===0&&(e%100===0||e%100===20||e%100===40||e%100===60||e%100===80)?K:t!==0?te:O},function(s){var e=Math.floor(Math.abs(+s)),t=(s+".").split(".")[1].length,r=+s;return e===1&&t===0?R:e===2&&t===0?re:t===0&&(r<0||10<r)&&r%10===0?te:O},function(s){var e=Math.floor(Math.abs(+s)),t=+(""+s).replace(/^[^.]*.?|0+$/g,"");return t===0&&e%10===1&&e%100!==11||t!==0?R:O},function(s){var e=+s;return e===1?R:e===2?re:O},function(s){var e=+s;return e===0?bt:e===1?R:O},function(s){var e=Math.floor(Math.abs(+s)),t=+s;return t===0?bt:(e===0||e===1)&&t!==0?R:O},function(s){var e=+(s+".").split(".")[1],t=+s;return t%10===1&&(t%100<11||19<t%100)?R:2<=t%10&&t%10<=9&&(t%100<11||19<t%100)?K:e!==0?te:O},function(s){var e=(s+".").split(".")[1].length,t=+(s+".").split(".")[1],r=+s;return r%10===0||11<=r%100&&r%100<=19||e===2&&11<=t%100&&t%100<=19?bt:r%10===1&&r%100!==11||e===2&&t%10===1&&t%100!==11||e!==2&&t%10===1?R:O},function(s){var e=Math.floor(Math.abs(+s)),t=(s+".").split(".")[1].length,r=+(s+".").split(".")[1];return t===0&&e%10===1&&e%100!==11||r%10===1&&r%100!==11?R:O},function(s){var e=Math.floor(Math.abs(+s)),t=(s+".").split(".")[1].length,r=+s;return e===1&&t===0?R:t!==0||r===0||r!==1&&1<=r%100&&r%100<=19?K:O},function(s){var e=+s;return e===1?R:e===0||2<=e%100&&e%100<=10?K:11<=e%100&&e%100<=19?te:O},function(s){var e=Math.floor(Math.abs(+s)),t=(s+".").split(".")[1].length;return e===1&&t===0?R:t===0&&2<=e%10&&e%10<=4&&(e%100<12||14<e%100)?K:t===0&&e!==1&&0<=e%10&&e%10<=1||t===0&&5<=e%10&&e%10<=9||t===0&&12<=e%100&&e%100<=14?te:O},function(s){var e=Math.floor(Math.abs(+s));return 0<=e&&e<=1?R:O},function(s){var e=Math.floor(Math.abs(+s)),t=(s+".").split(".")[1].length;return t===0&&e%10===1&&e%100!==11?R:t===0&&2<=e%10&&e%10<=4&&(e%100<12||14<e%100)?K:t===0&&e%10===0||t===0&&5<=e%10&&e%10<=9||t===0&&11<=e%100&&e%100<=14?te:O},function(s){var e=Math.floor(Math.abs(+s)),t=+s;return e===0||t===1?R:2<=t&&t<=10?K:O},function(s){var e=Math.floor(Math.abs(+s)),t=+(s+".").split(".")[1],r=+s;return r===0||r===1||e===0&&t===1?R:O},function(s){var e=Math.floor(Math.abs(+s)),t=(s+".").split(".")[1].length;return t===0&&e%100===1?R:t===0&&e%100===2?re:t===0&&3<=e%100&&e%100<=4||t!==0?K:O},function(s){var e=+s;return 0<=e&&e<=1||11<=e&&e<=99?R:O},function(s){var e=+s;return e===1||e===5||e===7||e===8||e===9||e===10?R:e===2||e===3?re:e===4?K:e===6?te:O},function(s){var e=Math.floor(Math.abs(+s));return e%10===1||e%10===2||e%10===5||e%10===7||e%10===8||e%100===20||e%100===50||e%100===70||e%100===80?R:e%10===3||e%10===4||e%1e3===100||e%1e3===200||e%1e3===300||e%1e3===400||e%1e3===500||e%1e3===600||e%1e3===700||e%1e3===800||e%1e3===900?K:e===0||e%10===6||e%100===40||e%100===60||e%100===90?te:O},function(s){var e=+s;return(e%10===2||e%10===3)&&e%100!==12&&e%100!==13?K:O},function(s){var e=+s;return e===1||e===3?R:e===2?re:e===4?K:O},function(s){var e=+s;return e===0||e===7||e===8||e===9?bt:e===1?R:e===2?re:e===3||e===4?K:e===5||e===6?te:O},function(s){var e=+s;return e%10===1&&e%100!==11?R:e%10===2&&e%100!==12?re:e%10===3&&e%100!==13?K:O},function(s){var e=+s;return e===1||e===11?R:e===2||e===12?re:e===3||e===13?K:O},function(s){var e=+s;return e===1?R:e===2||e===3?re:e===4?K:e===6?te:O},function(s){var e=+s;return e===1||e===5?R:O},function(s){var e=+s;return e===11||e===8||e===80||e===800?te:O},function(s){var e=Math.floor(Math.abs(+s));return e===1?R:e===0||2<=e%100&&e%100<=20||e%100===40||e%100===60||e%100===80?te:O},function(s){var e=+s;return e%10===6||e%10===9||e%10===0&&e!==0?te:O},function(s){var e=Math.floor(Math.abs(+s));return e%10===1&&e%100!==11?R:e%10===2&&e%100!==12?re:(e%10===7||e%10===8)&&e%100!==17&&e%100!==18?te:O},function(s){var e=+s;return e===1?R:e===2||e===3?re:e===4?K:O},function(s){var e=+s;return 1<=e&&e<=4?R:O},function(s){var e=+s;return e===1||e===5||7<=e&&e<=9?R:e===2||e===3?re:e===4?K:e===6?te:O},function(s){var e=+s;return e===1?R:e%10===4&&e%100!==14?te:O},function(s){var e=+s;return(e%10===1||e%10===2)&&e%100!==11&&e%100!==12?R:O},function(s){var e=+s;return e%10===6||e%10===9||e===10?K:O},function(s){var e=+s;return e%10===3&&e%100!==13?K:O}],Kr={af:{cardinal:p[0]},ak:{cardinal:p[1]},am:{cardinal:p[2]},ar:{cardinal:p[3]},ars:{cardinal:p[3]},as:{cardinal:p[2],ordinal:p[34]},asa:{cardinal:p[0]},ast:{cardinal:p[4]},az:{cardinal:p[0],ordinal:p[35]},be:{cardinal:p[5],ordinal:p[36]},bem:{cardinal:p[0]},bez:{cardinal:p[0]},bg:{cardinal:p[0]},bh:{cardinal:p[1]},bn:{cardinal:p[2],ordinal:p[34]},br:{cardinal:p[6]},brx:{cardinal:p[0]},bs:{cardinal:p[7]},ca:{cardinal:p[4],ordinal:p[37]},ce:{cardinal:p[0]},cgg:{cardinal:p[0]},chr:{cardinal:p[0]},ckb:{cardinal:p[0]},cs:{cardinal:p[8]},cy:{cardinal:p[9],ordinal:p[38]},da:{cardinal:p[10]},de:{cardinal:p[4]},dsb:{cardinal:p[11]},dv:{cardinal:p[0]},ee:{cardinal:p[0]},el:{cardinal:p[0]},en:{cardinal:p[4],ordinal:p[39]},eo:{cardinal:p[0]},es:{cardinal:p[0]},et:{cardinal:p[4]},eu:{cardinal:p[0]},fa:{cardinal:p[2]},ff:{cardinal:p[12]},fi:{cardinal:p[4]},fil:{cardinal:p[13],ordinal:p[0]},fo:{cardinal:p[0]},fr:{cardinal:p[12],ordinal:p[0]},fur:{cardinal:p[0]},fy:{cardinal:p[4]},ga:{cardinal:p[14],ordinal:p[0]},gd:{cardinal:p[15],ordinal:p[40]},gl:{cardinal:p[4]},gsw:{cardinal:p[0]},gu:{cardinal:p[2],ordinal:p[41]},guw:{cardinal:p[1]},gv:{cardinal:p[16]},ha:{cardinal:p[0]},haw:{cardinal:p[0]},he:{cardinal:p[17]},hi:{cardinal:p[2],ordinal:p[41]},hr:{cardinal:p[7]},hsb:{cardinal:p[11]},hu:{cardinal:p[0],ordinal:p[42]},hy:{cardinal:p[12],ordinal:p[0]},ia:{cardinal:p[4]},io:{cardinal:p[4]},is:{cardinal:p[18]},it:{cardinal:p[4],ordinal:p[43]},iu:{cardinal:p[19]},iw:{cardinal:p[17]},jgo:{cardinal:p[0]},ji:{cardinal:p[4]},jmc:{cardinal:p[0]},ka:{cardinal:p[0],ordinal:p[44]},kab:{cardinal:p[12]},kaj:{cardinal:p[0]},kcg:{cardinal:p[0]},kk:{cardinal:p[0],ordinal:p[45]},kkj:{cardinal:p[0]},kl:{cardinal:p[0]},kn:{cardinal:p[2]},ks:{cardinal:p[0]},ksb:{cardinal:p[0]},ksh:{cardinal:p[20]},ku:{cardinal:p[0]},kw:{cardinal:p[19]},ky:{cardinal:p[0]},lag:{cardinal:p[21]},lb:{cardinal:p[0]},lg:{cardinal:p[0]},ln:{cardinal:p[1]},lt:{cardinal:p[22]},lv:{cardinal:p[23]},mas:{cardinal:p[0]},mg:{cardinal:p[1]},mgo:{cardinal:p[0]},mk:{cardinal:p[24],ordinal:p[46]},ml:{cardinal:p[0]},mn:{cardinal:p[0]},mo:{cardinal:p[25],ordinal:p[0]},mr:{cardinal:p[2],ordinal:p[47]},mt:{cardinal:p[26]},nah:{cardinal:p[0]},naq:{cardinal:p[19]},nb:{cardinal:p[0]},nd:{cardinal:p[0]},ne:{cardinal:p[0],ordinal:p[48]},nl:{cardinal:p[4]},nn:{cardinal:p[0]},nnh:{cardinal:p[0]},no:{cardinal:p[0]},nr:{cardinal:p[0]},nso:{cardinal:p[1]},ny:{cardinal:p[0]},nyn:{cardinal:p[0]},om:{cardinal:p[0]},or:{cardinal:p[0],ordinal:p[49]},os:{cardinal:p[0]},pa:{cardinal:p[1]},pap:{cardinal:p[0]},pl:{cardinal:p[27]},prg:{cardinal:p[23]},ps:{cardinal:p[0]},pt:{cardinal:p[28]},"pt-PT":{cardinal:p[4]},rm:{cardinal:p[0]},ro:{cardinal:p[25],ordinal:p[0]},rof:{cardinal:p[0]},ru:{cardinal:p[29]},rwk:{cardinal:p[0]},saq:{cardinal:p[0]},sc:{cardinal:p[4],ordinal:p[43]},scn:{cardinal:p[4],ordinal:p[43]},sd:{cardinal:p[0]},sdh:{cardinal:p[0]},se:{cardinal:p[19]},seh:{cardinal:p[0]},sh:{cardinal:p[7]},shi:{cardinal:p[30]},si:{cardinal:p[31]},sk:{cardinal:p[8]},sl:{cardinal:p[32]},sma:{cardinal:p[19]},smi:{cardinal:p[19]},smj:{cardinal:p[19]},smn:{cardinal:p[19]},sms:{cardinal:p[19]},sn:{cardinal:p[0]},so:{cardinal:p[0]},sq:{cardinal:p[0],ordinal:p[50]},sr:{cardinal:p[7]},ss:{cardinal:p[0]},ssy:{cardinal:p[0]},st:{cardinal:p[0]},sv:{cardinal:p[4],ordinal:p[51]},sw:{cardinal:p[4]},syr:{cardinal:p[0]},ta:{cardinal:p[0]},te:{cardinal:p[0]},teo:{cardinal:p[0]},ti:{cardinal:p[1]},tig:{cardinal:p[0]},tk:{cardinal:p[0],ordinal:p[52]},tl:{cardinal:p[13],ordinal:p[0]},tn:{cardinal:p[0]},tr:{cardinal:p[0]},ts:{cardinal:p[0]},tzm:{cardinal:p[33]},ug:{cardinal:p[0]},uk:{cardinal:p[29],ordinal:p[53]},ur:{cardinal:p[4]},uz:{cardinal:p[0]},ve:{cardinal:p[0]},vo:{cardinal:p[0]},vun:{cardinal:p[0]},wa:{cardinal:p[1]},wae:{cardinal:p[0]},xh:{cardinal:p[0]},xog:{cardinal:p[0]},yi:{cardinal:p[4]},zu:{cardinal:p[2]},lo:{ordinal:p[0]},ms:{ordinal:p[0]},vi:{ordinal:p[0]}},nr=sa(function(s,e){e=s.exports=function(g,f,m){return t(g,null,f||"en",m||{},!0)},e.toParts=function(g,f,m){return t(g,null,f||"en",m||{},!1)};function t(g,f,m,b,_){var $=g.map(function(x){return r(x,f,m,b,_)});return _?$.length===1?$[0]:function(x){for(var A="",S=0;S<$.length;++S)A+=$[S](x);return A}:function(x){return $.reduce(function(A,S){return A.concat(S(x))},[])}}function r(g,f,m,b,_){if(typeof g=="string"){var $=g;return function(){return $}}var x=g[0],A=g[1];if(f&&g[0]==="#"){x=f[0];var S=f[2],B=(b.number||h.number)([x,"number"],m);return function(U){return B(o(x,U)-S,U)}}var z;A==="plural"||A==="selectordinal"?(z={},Object.keys(g[3]).forEach(function(U){z[U]=t(g[3][U],g,m,b,_)}),g=[g[0],g[1],g[2],z]):g[2]&&typeof g[2]=="object"&&(z={},Object.keys(g[2]).forEach(function(U){z[U]=t(g[2][U],g,m,b,_)}),g=[g[0],g[1],z]);var I=A&&(b[A]||h[A]);if(I){var X=I(g,m);return function(U){return X(o(x,U),U)}}return _?function(U){return String(o(x,U))}:function(U){return o(x,U)}}function o(g,f){if(f&&g in f)return f[g];for(var m=g.split("."),b=f,_=0,$=m.length;b&&_<$;++_)b=b[m[_]];return b}function i(g,f){var m=g[2],b=Ke.number[m]||Ke.parseNumberPattern(m)||Ke.number.default;return new Intl.NumberFormat(f,b).format}function n(g,f){var m=g[2],b=Ke.duration[m]||Ke.duration.default,_=new Intl.NumberFormat(f,b.seconds).format,$=new Intl.NumberFormat(f,b.minutes).format,x=new Intl.NumberFormat(f,b.hours).format,A=/^fi$|^fi-|^da/.test(String(f))?".":":";return function(S,B){if(S=+S,!isFinite(S))return _(S);var z=~~(S/60/60),I=~~(S/60%60),X=(z?x(Math.abs(z))+A:"")+$(Math.abs(I))+A+_(Math.abs(S%60));return S<0?x(-1).replace(x(1),X):X}}function a(g,f){var m=g[1],b=g[2],_=Ke[m][b]||Ke.parseDatePattern(b)||Ke[m].default;return new Intl.DateTimeFormat(f,_).format}function l(g,f){var m=g[1],b=m==="selectordinal"?"ordinal":"cardinal",_=g[2],$=g[3],x;if(Intl.PluralRules&&Intl.PluralRules.supportedLocalesOf(f).length>0)x=new Intl.PluralRules(f,{type:b});else{var A=kd(f,Kr),S=A&&Kr[A][b]||d;x={select:S}}return function(B,z){var I=$["="+ +B]||$[x.select(B-_)]||$.other;return I(z)}}function d(){return"other"}function c(g,f){var m=g[2];return function(b,_){var $=m[b]||m.other;return $(_)}}var h={number:i,ordinal:i,spellout:i,duration:n,date:a,time:a,plural:l,selectordinal:l,select:c};e.types=h});nr.toParts;nr.types;var ra=sa(function(s,e){var t="{",r="}",o=",",i="#",n="<",a=">",l="</",d="/>",c="'",h="offset:",g=["number","date","time","ordinal","duration","spellout"],f=["plural","select","selectordinal"];e=s.exports=function(u,k){return m({pattern:String(u),index:0,tagsType:k&&k.tagsType||null,tokens:k&&k.tokens||null},"")};function m(u,k){var T=u.pattern,L=T.length,N=[],C=u.index,H=b(u,k);for(H&&N.push(H),H&&u.tokens&&u.tokens.push(["text",T.slice(C,u.index)]);u.index<L;){if(T[u.index]===r){if(!k)throw M(u);break}if(k&&u.tagsType&&T.slice(u.index,u.index+l.length)===l)break;N.push(x(u)),C=u.index,H=b(u,k),H&&N.push(H),H&&u.tokens&&u.tokens.push(["text",T.slice(C,u.index)])}return N}function b(u,k){for(var T=u.pattern,L=T.length,N=k==="plural"||k==="selectordinal",C=!!u.tagsType,H=k==="{style}",pe="";u.index<L;){var j=T[u.index];if(j===t||j===r||N&&j===i||C&&j===n||H&&_(j.charCodeAt(0)))break;if(j===c)if(j=T[++u.index],j===c)pe+=j,++u.index;else if(j===t||j===r||N&&j===i||C&&j===n||H)for(pe+=j;++u.index<L;)if(j=T[u.index],j===c&&T[u.index+1]===c)pe+=c,++u.index;else if(j===c){++u.index;break}else pe+=j;else pe+=c;else pe+=j,++u.index}return pe}function _(u){return u>=9&&u<=13||u===32||u===133||u===160||u===6158||u>=8192&&u<=8205||u===8232||u===8233||u===8239||u===8287||u===8288||u===12288||u===65279}function $(u){for(var k=u.pattern,T=k.length,L=u.index;u.index<T&&_(k.charCodeAt(u.index));)++u.index;L<u.index&&u.tokens&&u.tokens.push(["space",u.pattern.slice(L,u.index)])}function x(u){var k=u.pattern;if(k[u.index]===i)return u.tokens&&u.tokens.push(["syntax",i]),++u.index,[i];var T=A(u);if(T)return T;if(k[u.index]!==t)throw M(u,t);u.tokens&&u.tokens.push(["syntax",t]),++u.index,$(u);var L=S(u);if(!L)throw M(u,"placeholder id");u.tokens&&u.tokens.push(["id",L]),$(u);var N=k[u.index];if(N===r)return u.tokens&&u.tokens.push(["syntax",r]),++u.index,[L];if(N!==o)throw M(u,o+" or "+r);u.tokens&&u.tokens.push(["syntax",o]),++u.index,$(u);var C=S(u);if(!C)throw M(u,"placeholder type");if(u.tokens&&u.tokens.push(["type",C]),$(u),N=k[u.index],N===r){if(u.tokens&&u.tokens.push(["syntax",r]),C==="plural"||C==="selectordinal"||C==="select")throw M(u,C+" sub-messages");return++u.index,[L,C]}if(N!==o)throw M(u,o+" or "+r);u.tokens&&u.tokens.push(["syntax",o]),++u.index,$(u);var H;if(C==="plural"||C==="selectordinal"){var pe=z(u);$(u),H=[L,C,pe,X(u,C)]}else if(C==="select")H=[L,C,X(u,C)];else if(g.indexOf(C)>=0)H=[L,C,B(u)];else{var j=u.index,De=B(u);$(u),k[u.index]===t&&(u.index=j,De=X(u,C)),H=[L,C,De]}if($(u),k[u.index]!==r)throw M(u,r);return u.tokens&&u.tokens.push(["syntax",r]),++u.index,H}function A(u){var k=u.tagsType;if(!(!k||u.pattern[u.index]!==n)){if(u.pattern.slice(u.index,u.index+l.length)===l)throw M(u,null,"closing tag without matching opening tag");u.tokens&&u.tokens.push(["syntax",n]),++u.index;var T=S(u,!0);if(!T)throw M(u,"placeholder id");if(u.tokens&&u.tokens.push(["id",T]),$(u),u.pattern.slice(u.index,u.index+d.length)===d)return u.tokens&&u.tokens.push(["syntax",d]),u.index+=d.length,[T,k];if(u.pattern[u.index]!==a)throw M(u,a);u.tokens&&u.tokens.push(["syntax",a]),++u.index;var L=m(u,k),N=u.index;if(u.pattern.slice(u.index,u.index+l.length)!==l)throw M(u,l+T+a);u.tokens&&u.tokens.push(["syntax",l]),u.index+=l.length;var C=S(u,!0);if(C&&u.tokens&&u.tokens.push(["id",C]),T!==C)throw u.index=N,M(u,l+T+a,l+C+a);if($(u),u.pattern[u.index]!==a)throw M(u,a);return u.tokens&&u.tokens.push(["syntax",a]),++u.index,[T,k,{children:L}]}}function S(u,k){for(var T=u.pattern,L=T.length,N="";u.index<L;){var C=T[u.index];if(C===t||C===r||C===o||C===i||C===c||_(C.charCodeAt(0))||k&&(C===n||C===a||C==="/"))break;N+=C,++u.index}return N}function B(u){var k=u.index,T=b(u,"{style}");if(!T)throw M(u,"placeholder style name");return u.tokens&&u.tokens.push(["style",u.pattern.slice(k,u.index)]),T}function z(u){var k=u.pattern,T=k.length,L=0;if(k.slice(u.index,u.index+h.length)===h){u.tokens&&u.tokens.push(["offset","offset"],["syntax",":"]),u.index+=h.length,$(u);for(var N=u.index;u.index<T&&I(k.charCodeAt(u.index));)++u.index;if(N===u.index)throw M(u,"offset number");u.tokens&&u.tokens.push(["number",k.slice(N,u.index)]),L=+k.slice(N,u.index)}return L}function I(u){return u>=48&&u<=57}function X(u,k){for(var T=u.pattern,L=T.length,N={};u.index<L&&T[u.index]!==r;){var C=S(u);if(!C)throw M(u,"sub-message selector");u.tokens&&u.tokens.push(["selector",C]),$(u),N[C]=U(u,k),$(u)}if(!N.other&&f.indexOf(k)>=0)throw M(u,null,null,'"other" sub-message must be specified in '+k);return N}function U(u,k){if(u.pattern[u.index]!==t)throw M(u,t+" to start sub-message");u.tokens&&u.tokens.push(["syntax",t]),++u.index;var T=m(u,k);if(u.pattern[u.index]!==r)throw M(u,r+" to end sub-message");return u.tokens&&u.tokens.push(["syntax",r]),++u.index,T}function M(u,k,T,L){var N=u.pattern,C=N.slice(0,u.index).split(/\r?\n/),H=u.index,pe=C.length,j=C.slice(-1)[0].length;return T=T||(u.index>=N.length?"end of message pattern":S(u)||N[u.index]),L||(L=ve(k,T)),L+=" in "+N.replace(/\r?\n/g,`
`),new se(L,k,T,H,pe,j)}function ve(u,k){return u?"Expected "+u+" but found "+k:"Unexpected "+k+" found"}function se(u,k,T,L,N,C){Error.call(this,u),this.name="SyntaxError",this.message=u,this.expected=k,this.found=T,this.offset=L,this.line=N,this.column=C}se.prototype=Object.create(Error.prototype),e.SyntaxError=se});ra.SyntaxError;var Cd=new RegExp("^("+Object.keys(Kr).join("|")+")\\b"),Jt=new WeakMap;function St(s,e,t){if(!(this instanceof St)||Jt.has(this))throw new TypeError("calling MessageFormat constructor without new is invalid");var r=ra(s);Jt.set(this,{ast:r,format:nr(r,e,t&&t.types),locale:St.supportedLocalesOf(e)[0]||"en",locales:e,options:t})}var Sd=St;Object.defineProperties(St.prototype,{format:{configurable:!0,get:function(){var s=Jt.get(this);if(!s)throw new TypeError("MessageFormat.prototype.format called on value that's not an object initialized as a MessageFormat");return s.format}},formatToParts:{configurable:!0,writable:!0,value:function(s){var e=Jt.get(this);if(!e)throw new TypeError("MessageFormat.prototype.formatToParts called on value that's not an object initialized as a MessageFormat");var t=e.toParts||(e.toParts=nr.toParts(e.ast,e.locales,e.options&&e.options.types));return t(s)}},resolvedOptions:{configurable:!0,writable:!0,value:function(){var s=Jt.get(this);if(!s)throw new TypeError("MessageFormat.prototype.resolvedOptions called on value that's not an object initialized as a MessageFormat");return{locale:s.locale}}}});typeof Symbol<"u"&&Object.defineProperty(St.prototype,Symbol.toStringTag,{value:"Object"});Object.defineProperties(St,{supportedLocalesOf:{configurable:!0,writable:!0,value:function(s){return[].concat(Intl.NumberFormat.supportedLocalesOf(s),Intl.DateTimeFormat.supportedLocalesOf(s),Intl.PluralRules?Intl.PluralRules.supportedLocalesOf(s):[],[].concat(s||[]).filter(function(e){return Cd.test(e)})).filter(function(e,t,r){return r.indexOf(e)===t})}}});function Ad(s){return!!(s&&s.default&&typeof s.default=="object"&&Object.keys(s).length===1)}const Ge=globalThis.document?.documentElement;let Td=class extends EventTarget{formatNumberOptions={returnIfNaN:"",postProcessors:new Map};formatDateOptions={postProcessors:new Map};#e=!1;#t="";#s=null;__storage={};__namespacePatternsMap=new Map;__namespaceLoadersCache={};__namespaceLoaderPromisesCache={};get locale(){return this.#e?this.#t||"":Ge.lang||""}set locale(e){if(this.#r(e),!this.#e){const r=Ge.lang;this._setHtmlLangAttribute(e),this._onLocaleChanged(e,r);return}const t=this.#t;this.#t=e,this.#s===null&&this._setHtmlLangAttribute(e),this._onLocaleChanged(e,t)}get loadingComplete(){return typeof this.__namespaceLoaderPromisesCache[this.locale]=="object"?Promise.all(Object.values(this.__namespaceLoaderPromisesCache[this.locale])):Promise.resolve()}constructor({allowOverridesForExistingNamespaces:e=!1,autoLoadOnLocaleChange:t=!1,showKeyAsFallback:r=!1,fallbackLocale:o=""}={}){super(),this.__allowOverridesForExistingNamespaces=e,this._autoLoadOnLocaleChange=!!t,this._showKeyAsFallback=r,this._fallbackLocale=o;const i=Ge.getAttribute("data-localize-lang");this.#e=!!i,this.#e&&(this.locale=i,this._setupTranslationToolSupport()),Ge.lang||(Ge.lang=this.locale||"en-GB"),this._setupHtmlLangAttributeObserver()}addData(e,t,r){if(!this.__allowOverridesForExistingNamespaces&&this._isNamespaceInCache(e,t))throw new Error(`Namespace "${t}" has been already added for the locale "${e}".`);this.__storage[e]=this.__storage[e]||{},this.__allowOverridesForExistingNamespaces?this.__storage[e][t]={...this.__storage[e][t],...r}:this.__storage[e][t]=r}setupNamespaceLoader(e,t){this.__namespacePatternsMap.set(e,t)}loadNamespaces(e,{locale:t}={}){return Promise.all(e.map(r=>this.loadNamespace(r,{locale:t})))}loadNamespace(e,{locale:t=this.locale}={locale:this.locale}){const r=typeof e=="object",o=r?Object.keys(e)[0]:e;return this._isNamespaceInCache(t,o)?Promise.resolve():this._getCachedNamespaceLoaderPromise(t,o)||this._loadNamespaceData(t,e,r,o)}msg(e,t,r={}){const o=r.locale?r.locale:this.locale,i=this._getMessageForKeys(e,o);return i?new Sd(i,o).format(t):""}teardown(){this._teardownHtmlLangAttributeObserver()}reset(){this.__storage={},this.__namespacePatternsMap=new Map,this.__namespaceLoadersCache={},this.__namespaceLoaderPromisesCache={}}setDatePostProcessorForLocale({locale:e,postProcessor:t}){this.formatDateOptions?.postProcessors.set(e,t)}setNumberPostProcessorForLocale({locale:e,postProcessor:t}){this.formatNumberOptions?.postProcessors.set(e,t)}_setupTranslationToolSupport(){this.#s=Ge.lang||null}_setHtmlLangAttribute(e){this._teardownHtmlLangAttributeObserver(),Ge.lang=e,this._setupHtmlLangAttributeObserver()}_setupHtmlLangAttributeObserver(){this._htmlLangAttributeObserver||(this._htmlLangAttributeObserver=new MutationObserver(e=>{e.forEach(t=>{this.#e?Ge.lang==="auto"?(this.#s=null,this._setHtmlLangAttribute(this.locale)):this.#s=document.documentElement.lang:this._onLocaleChanged(document.documentElement.lang,t.oldValue||"")})})),this._htmlLangAttributeObserver.observe(document.documentElement,{attributes:!0,attributeFilter:["lang"],attributeOldValue:!0})}_teardownHtmlLangAttributeObserver(){this._htmlLangAttributeObserver&&this._htmlLangAttributeObserver.disconnect()}_isNamespaceInCache(e,t){return!!(this.__storage[e]&&this.__storage[e][t])}_getCachedNamespaceLoaderPromise(e,t){return this.__namespaceLoaderPromisesCache[e]?this.__namespaceLoaderPromisesCache[e][t]:null}_loadNamespaceData(e,t,r,o){const i=this._getNamespaceLoader(t,r,o),n=this._getNamespaceLoaderPromise(i,e,o);return this._cacheNamespaceLoaderPromise(e,o,n),n.then(a=>{if(this.__namespaceLoaderPromisesCache[e]&&this.__namespaceLoaderPromisesCache[e][o]===n){const l=Ad(a)?a.default:a;this.addData(e,o,l)}})}_getNamespaceLoader(e,t,r){let o=this.__namespaceLoadersCache[r];if(o||(t?(o=e[r],this.__namespaceLoadersCache[r]=o):(o=this._lookupNamespaceLoader(r),this.__namespaceLoadersCache[r]=o)),!o)throw new Error(`Namespace "${r}" was not properly setup.`);return this.__namespaceLoadersCache[r]=o,o}_getNamespaceLoaderPromise(e,t,r,o=this._fallbackLocale){return e(t,r).catch(()=>{const i=this._getLangFromLocale(t);return e(i,r).catch(()=>{if(o)return this._getNamespaceLoaderPromise(e,o,r,"").catch(()=>{const n=this._getLangFromLocale(o);throw new Error(`Data for namespace "${r}" and current locale "${t}" or fallback locale "${o}" could not be loaded. Make sure you have data either for locale "${t}" (and/or generic language "${i}") or for fallback "${o}" (and/or "${n}").`)});throw new Error(`Data for namespace "${r}" and locale "${t}" could not be loaded. Make sure you have data for locale "${t}" (and/or generic language "${i}").`)})})}_cacheNamespaceLoaderPromise(e,t,r){this.__namespaceLoaderPromisesCache[e]||(this.__namespaceLoaderPromisesCache[e]={}),this.__namespaceLoaderPromisesCache[e][t]=r}_lookupNamespaceLoader(e){for(const[t,r]of this.__namespacePatternsMap){const o=typeof t=="string"&&t===e,i=typeof t=="object"&&t.constructor.name==="RegExp"&&t.test(e);if(o||i)return r}return null}_getLangFromLocale(e){return e.substring(0,2)}_onLocaleChanged(e,t){this.dispatchEvent(new CustomEvent("__localeChanging")),e!==t&&(this._autoLoadOnLocaleChange?(this._loadAllMissing(e,t),this.loadingComplete.then(()=>{this.dispatchEvent(new CustomEvent("localeChanged",{detail:{newLocale:e,oldLocale:t}}))})):this.dispatchEvent(new CustomEvent("localeChanged",{detail:{newLocale:e,oldLocale:t}})))}_loadAllMissing(e,t){const r=this.__storage[t]||{},o=this.__storage[e]||{};Object.keys(r).forEach(i=>{o[i]||this.loadNamespace(i,{locale:e})})}_getMessageForKeys(e,t){if(typeof e=="string")return this._getMessageForKey(e,t);const r=Array.from(e).reverse();let o,i;for(;r.length;)if(o=r.pop(),i=this._getMessageForKey(o,t),i)return i}_getMessageForKey(e,t){if(!e||e.indexOf(":")===-1)throw new Error(`Namespace is missing in the key "${e}". The format for keys is "namespace:name".`);const[r,o]=e.split(":"),i=this.__storage[t],n=i?i[r]:{},a=o.split(".").reduce((l,d)=>typeof l=="object"?l[d]:l,n);return String(a||(this._showKeyAsFallback?e:""))}#r(e){if(!e.includes("-"))throw new Error(`
      Locale was set to ${e}.
      Language only locales are not allowed, please use the full language locale e.g. 'en-GB' instead of 'en'.
      See https://github.com/ing-bank/lion/issues/187 for more information.
    `)}get _supportExternalTranslationTools(){return this.#e}set _supportExternalTranslationTools(e){this.#e=e}get _langAttrSetByTranslationTool(){return this.#t}set _langAttrSetByTranslationTool(e){this.#t=e}};const xr=Symbol.for("lion::SingletonManagerClassStorage"),kr=globalThis||window;let Od=class{constructor(){this._map=kr[xr]?kr[xr]:kr[xr]=new Map}set(e,t){this.has(e)||this._map.set(e,t)}get(e){return this._map.get(e)}has(e){return this._map.has(e)}};const Cr=new Od;function Gr(){if(Cr.has("@lion/ui::localize::0.x"))return Cr.get("@lion/ui::localize::0.x");const s=new Td({autoLoadOnLocaleChange:!0,fallbackLocale:"en-GB"});return Cr.set("@lion/ui::localize::0.x",s),s}const Yt=(s,e)=>{const t=s._$AN;if(t===void 0)return!1;for(const r of t)r._$AO?.(e,!1),Yt(r,e);return!0},js=s=>{let e,t;do{if((e=s._$AM)===void 0)break;t=e._$AN,t.delete(s),s=e}while(t?.size===0)},oa=s=>{for(let e;e=s._$AM;s=e){let t=e._$AN;if(t===void 0)e._$AN=t=new Set;else if(t.has(s))break;t.add(s),Rd(e)}};function Ld(s){this._$AN!==void 0?(js(this),this._$AM=s,oa(this)):this._$AM=s}function Nd(s,e=!1,t=0){const r=this._$AH,o=this._$AN;if(o!==void 0&&o.size!==0)if(e)if(Array.isArray(r))for(let i=t;i<r.length;i++)Yt(r[i],!1),js(r[i]);else r!=null&&(Yt(r,!1),js(r));else Yt(this,s)}const Rd=s=>{s.type==wo.CHILD&&(s._$AP??=Nd,s._$AQ??=Ld)};let Pd=class extends $o{constructor(){super(...arguments),this._$AN=void 0}_$AT(e,t,r){super._$AT(e,t,r),oa(this),this.isConnected=e._$AU}_$AO(e,t=!0){e!==this.isConnected&&(this.isConnected=e,e?this.reconnected?.():this.disconnected?.()),t&&(Yt(this,e),js(this))}setValue(e){if($d(this._$Ct))this._$Ct._$AI(e,this);else{const t=[...this._$Ct._$AH];t[this._$Ci]=e,this._$Ct._$AI(t,this,0)}}disconnected(){}reconnected(){}},Fd=class{constructor(e){this.G=e}disconnect(){this.G=void 0}reconnect(e){this.G=e}deref(){return this.G}},Md=class{constructor(){this.Y=void 0,this.Z=void 0}get(){return this.Y}pause(){this.Y??=new Promise((e=>this.Z=e))}resume(){this.Z?.(),this.Y=this.Z=void 0}};const yi=s=>!_d(s)&&typeof s.then=="function",wi=1073741823;let Dd=class extends Pd{constructor(){super(...arguments),this._$Cwt=wi,this._$Cbt=[],this._$CK=new Fd(this),this._$CX=new Md}render(...e){return e.find((t=>!yi(t)))??Ne}update(e,t){const r=this._$Cbt;let o=r.length;this._$Cbt=t;const i=this._$CK,n=this._$CX;this.isConnected||this.disconnected();for(let a=0;a<t.length&&!(a>this._$Cwt);a++){const l=t[a];if(!yi(l))return this._$Cwt=a,l;a<o&&l===r[a]||(this._$Cwt=wi,o=0,Promise.resolve(l).then((async d=>{for(;n.get();)await n.get();const c=i.deref();if(c!==void 0){const h=c._$Cbt.indexOf(l);h>-1&&h<c._$Cwt&&(c._$Cwt=h,c.setValue(d))}})))}return Ne}disconnected(){this._$CK.disconnect(),this._$CX.pause()}reconnected(){this._$CK.reconnect(this),this._$CX.resume()}};const Vd=_o(Dd),zd=s=>class extends s{static get localizeNamespaces(){return[]}static get waitForLocalizeNamespaces(){return!0}constructor(){super(),this._localizeManager=Gr(),this.__boundLocalizeOnLocaleChanged=(...e)=>{const t=Array.from(e)[0];this.__localizeOnLocaleChanged(t)},this.__boundLocalizeOnLocaleChanging=()=>{this.__localizeOnLocaleChanging()},this.__localizeStartLoadingNamespaces(),this.localizeNamespacesLoaded&&this.localizeNamespacesLoaded.then(()=>{this.__localizeMessageSync=!0})}async scheduleUpdate(){Object.getPrototypeOf(this).constructor.waitForLocalizeNamespaces&&await this.localizeNamespacesLoaded,super.scheduleUpdate()}connectedCallback(){super.connectedCallback(),this.localizeNamespacesLoaded&&this.localizeNamespacesLoaded.then(()=>this.onLocaleReady()),this._localizeManager.addEventListener("__localeChanging",this.__boundLocalizeOnLocaleChanging),this._localizeManager.addEventListener("localeChanged",this.__boundLocalizeOnLocaleChanged)}disconnectedCallback(){super.disconnectedCallback(),this._localizeManager.removeEventListener("__localeChanging",this.__boundLocalizeOnLocaleChanging),this._localizeManager.removeEventListener("localeChanged",this.__boundLocalizeOnLocaleChanged)}msgLit(e,t,r){return this.__localizeMessageSync?this._localizeManager.msg(e,t,r):this.localizeNamespacesLoaded?Vd(this.localizeNamespacesLoaded.then(()=>this._localizeManager.msg(e,t,r)),V):""}__getUniqueNamespaces(){const e=[],t=new Set;return Object.getPrototypeOf(this).constructor.localizeNamespaces.forEach(t.add.bind(t)),t.forEach(r=>{e.push(r)}),e}__localizeStartLoadingNamespaces(){this.localizeNamespacesLoaded=this._localizeManager.loadNamespaces(this.__getUniqueNamespaces())}__localizeOnLocaleChanging(){this.__localizeStartLoadingNamespaces()}__localizeOnLocaleChanged(e){this.onLocaleChanged(e.detail.newLocale,e.detail.oldLocale)}onLocaleReady(){this.onLocaleUpdated()}onLocaleChanged(e,t){this.onLocaleUpdated(),this.requestUpdate()}onLocaleUpdated(){}},Id=ie(zd),Jr="3.0.0",_i=window.scopedElementsVersions||(window.scopedElementsVersions=[]);_i.includes(Jr)||_i.push(Jr);const Ud=s=>class extends s{static scopedElements;static get scopedElementsVersion(){return Jr}static __registry;get registry(){return this.constructor.__registry}set registry(e){this.constructor.__registry=e}attachShadow(e){const{scopedElements:t}=this.constructor;if(!this.registry||this.registry===this.constructor.__registry&&!Object.prototype.hasOwnProperty.call(this.constructor,"__registry")){this.registry=new CustomElementRegistry;for(const[r,o]of Object.entries(t??{}))this.registry.define(r,o)}return super.attachShadow({...e,customElements:this.registry,registry:this.registry})}},Bd=ie(Ud),jd=s=>class extends Bd(s){createRenderRoot(){const{shadowRootOptions:e,elementStyles:t}=this.constructor,r=this.attachShadow(e);return this.renderOptions.creationScope=r,ho(r,t),this.renderOptions.renderBefore??=r.firstChild,r}},qd=ie(jd);function xs(){return!!(globalThis.ShadowRoot?.prototype.createElement&&globalThis.ShadowRoot?.prototype.importNode)}const Hd=s=>class extends qd(s){constructor(){super()}createScopedElement(e){return(xs()?this.shadowRoot:document).createElement(e)}defineScopedElement(e,t){const r=this.registry.get(e),o=r&&r!==t;return!xs()&&o&&console.error([`You are trying to re-register the "${e}" custom element with a different class via ScopedElementsMixin.`,"This is only possible with a CustomElementRegistry.","Your browser does not support this feature so you will need to load a polyfill for it.",'Load "@webcomponents/scoped-custom-element-registry" before you register ANY web component to the global customElements registry.','e.g. add "<script src="/node_modules/@webcomponents/scoped-custom-element-registry/scoped-custom-element-registry.min.js"><\/script>" as your first script tag.',"For more details you can visit https://open-wc.org/docs/development/scoped-elements/"].join(`
`)),r?this.registry.get(e):this.registry.define(e,t)}attachShadow(e){const{scopedElements:t}=this.constructor;if(!this.registry||this.registry===this.constructor.__registry&&!Object.prototype.hasOwnProperty.call(this.constructor,"__registry")){this.registry=xs()?new CustomElementRegistry:customElements;for(const[r,o]of Object.entries(t??{}))this.defineScopedElement(r,o)}return Element.prototype.attachShadow.call(this,{...e,customElements:this.registry,registry:this.registry})}createRenderRoot(){const{shadowRootOptions:e,elementStyles:t}=this.constructor,r=this.attachShadow(e);return xs()&&(this.renderOptions.creationScope=r),r instanceof ShadowRoot&&(ho(r,t),this.renderOptions.renderBefore=this.renderOptions.renderBefore||r.firstChild),r}},ia=ie(Hd);let Wd=class{constructor(){this.__running=!1,this.__queue=[]}add(e){this.__queue.push(e),this.__running||(this.complete=new Promise(t=>{this.__callComplete=t}),this.__run())}async __run(){this.__running=!0,await this.__queue[0](),this.__queue.shift(),this.__queue.length>0?this.__run():(this.__running=!1,this.__callComplete&&this.__callComplete())}};function Kd(s){return s.charAt(0).toUpperCase()+s.slice(1)}const Gd=s=>class extends s{constructor(){super(),this.__SyncUpdatableNamespace={}}firstUpdated(e){super.firstUpdated(e),this.__syncUpdatableInitialize()}connectedCallback(){super.connectedCallback(),this.__SyncUpdatableNamespace.connected=!0}disconnectedCallback(){super.disconnectedCallback(),this.__SyncUpdatableNamespace.connected=!1}static enabledWarnings=super.enabledWarnings?.filter(e=>e!=="change-in-update")||[];static __syncUpdatableHasChanged(e,t,r){const o=this.elementProperties;return o.get(e)&&o.get(e).hasChanged?o.get(e).hasChanged(t,r):t!==r}__syncUpdatableInitialize(){const e=this.__SyncUpdatableNamespace,t=this.constructor;e.initialized=!0,e.queue&&Array.from(e.queue).forEach(r=>{t.__syncUpdatableHasChanged(r,this[r],void 0)&&this.updateSync(r,void 0)})}requestUpdate(e,t,r){if(super.requestUpdate(e,t,r),e===void 0)return;this.__SyncUpdatableNamespace=this.__SyncUpdatableNamespace||{};const o=this.__SyncUpdatableNamespace,i=this.constructor;o.initialized?i.__syncUpdatableHasChanged(e,this[e],t)&&this.updateSync(e,t):(o.queue=o.queue||new Set,o.queue.add(e))}updateSync(e,t){}},Jd=ie(Gd),Yd=(s=>{switch(s){case"bg-BG":return D(()=>import("./bg-BG-BqLSXSgK.js"),__vite__mapDeps([0,1]),import.meta.url);case"bg":return D(()=>import("./bg-Ch91FBqZ.js"),[],import.meta.url);case"cs-CZ":return D(()=>import("./cs-CZ-BOieS6Re.js"),__vite__mapDeps([2,3]),import.meta.url);case"cs":return D(()=>import("./cs-Bco-9vYd.js"),[],import.meta.url);case"de-DE":return D(()=>import("./de-DE-NiEdSbeI.js"),__vite__mapDeps([4,5]),import.meta.url);case"de":return D(()=>import("./de--MUj2jPW.js"),[],import.meta.url);case"en-AU":return D(()=>import("./en-AU-5SYH9YrO.js"),__vite__mapDeps([6,7]),import.meta.url);case"en-GB":return D(()=>import("./en-GB-5SYH9YrO.js"),__vite__mapDeps([8,7]),import.meta.url);case"en-US":return D(()=>import("./en-US-5SYH9YrO.js"),__vite__mapDeps([9,7]),import.meta.url);case"en-PH":case"en":return D(()=>import("./en-QBEFuq4A.js"),[],import.meta.url);case"es-ES":return D(()=>import("./es-ES-BzB2G1H7.js"),__vite__mapDeps([10,11]),import.meta.url);case"es":return D(()=>import("./es-QUDKKOEt.js"),[],import.meta.url);case"fr-FR":return D(()=>import("./fr-FR-D8x_WpSN.js"),__vite__mapDeps([12,13]),import.meta.url);case"fr-BE":return D(()=>import("./fr-BE-D8x_WpSN.js"),__vite__mapDeps([14,13]),import.meta.url);case"fr":return D(()=>import("./fr-Crw_WS9R.js"),[],import.meta.url);case"hu-HU":return D(()=>import("./hu-HU-DzuJRq2x.js"),__vite__mapDeps([15,16]),import.meta.url);case"hu":return D(()=>import("./hu-BzLNk3Oy.js"),[],import.meta.url);case"it-IT":return D(()=>import("./it-IT-BVziFtOr.js"),__vite__mapDeps([17,18]),import.meta.url);case"it":return D(()=>import("./it-Dk-tLV60.js"),[],import.meta.url);case"nl-BE":return D(()=>import("./nl-BE-Cv6cOJ-k.js"),__vite__mapDeps([19,20]),import.meta.url);case"nl-NL":return D(()=>import("./nl-NL-Cv6cOJ-k.js"),__vite__mapDeps([21,20]),import.meta.url);case"nl":return D(()=>import("./nl-ukLmcyhE.js"),[],import.meta.url);case"pl-PL":return D(()=>import("./pl-PL-C3QXGAg0.js"),__vite__mapDeps([22,23]),import.meta.url);case"pl":return D(()=>import("./pl-BsbBHKbu.js"),[],import.meta.url);case"ro-RO":return D(()=>import("./ro-RO-BHOQwu0O.js"),__vite__mapDeps([24,25]),import.meta.url);case"ro":return D(()=>import("./ro-BWWeoMIS.js"),[],import.meta.url);case"ru-RU":return D(()=>import("./ru-RU-DCvtZjBo.js"),__vite__mapDeps([26,27]),import.meta.url);case"ru":return D(()=>import("./ru-D87QXJFw.js"),[],import.meta.url);case"sk-SK":return D(()=>import("./sk-SK-DaLB_sM8.js"),__vite__mapDeps([28,29]),import.meta.url);case"sk":return D(()=>import("./sk-DCOU_ZI_.js"),[],import.meta.url);case"tr-TR":return D(()=>import("./tr-TR-Dhk7tqKh.js"),__vite__mapDeps([30,31]),import.meta.url);case"tr":return D(()=>import("./tr-92apvQxK.js"),[],import.meta.url);case"uk-UA":return D(()=>import("./uk-UA-BP_5Rplg.js"),__vite__mapDeps([32,33]),import.meta.url);case"uk":return D(()=>import("./uk-CGlal3kJ.js"),[],import.meta.url);case"zh-CN":case"zh":return D(()=>import("./zh-CZafHN1K.js"),[],import.meta.url);default:return D(()=>import("./en-QBEFuq4A.js"),[],import.meta.url)}}),Xd=s=>`${s[0].toUpperCase()}${s.slice(1)}`;let Zd=class extends Id(Y){static get properties(){return{feedbackData:{attribute:!1}}}static localizeNamespaces=[{"lion-form-core":Yd},...super.localizeNamespaces];static get styles(){return[P`
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
      `]}constructor(){super(),this.feedbackData=void 0}_messageTemplate({message:e}){return e}updated(e){super.updated(e),this.feedbackData&&this.feedbackData[0]?(this.setAttribute("type",this.feedbackData[0].type),this.currentType=this.feedbackData[0].type):this.currentType!=="success"&&this.removeAttribute("type")}render(){return E`
      ${this.feedbackData&&this.feedbackData.map(({message:e,type:t,validator:r})=>E`
          <div class="validation-feedback__type">
            ${e&&t?this._localizeManager.msg(`lion-form-core:validation${Xd(t)}`):V}
          </div>
          ${this._messageTemplate({message:e,type:t,validator:r})}
        `)}
    `}},qs=class{constructor(e){this.type="unparseable",this.viewValue=e}toString(){return JSON.stringify({type:this.type,viewValue:this.viewValue})}};const Qd=[Node.DOCUMENT_POSITION_PRECEDING,Node.DOCUMENT_POSITION_CONTAINS,Node.DOCUMENT_POSITION_CONTAINS|Node.DOCUMENT_POSITION_PRECEDING];function na(s,{reverse:e}={}){const t=(o,i)=>{const n=o.compareDocumentPosition(i);return Qd.includes(n)?1:-1},r=s.filter(o=>o);return r.sort(t),e&&r.reverse(),r}const eu=s=>class extends s{constructor(){super(),this.name="",this._parentFormGroup=void 0,this.allowCrossRootRegistration=!1}get name(){return this.__name||""}set name(e){const t=this.name;this.__name=e.toString(),this.requestUpdate("name",t)}static get properties(){return{name:{type:String,reflect:!0},allowCrossRootRegistration:{type:Boolean,attribute:"allow-cross-root-registration"}}}connectedCallback(){super.connectedCallback(),this.dispatchEvent(new CustomEvent("form-element-register",{detail:{element:this},bubbles:!0,composed:!!this.allowCrossRootRegistration}))}disconnectedCallback(){super.disconnectedCallback(),this.__unregisterFormElement()}__unregisterFormElement(){this._parentFormGroup&&this._parentFormGroup.removeFormElement(this)}},No=ie(eu),tu=s=>class extends No(hs(ms(s))){static get properties(){return{readOnly:{type:Boolean,attribute:"readonly",reflect:!0},label:String,labelSrOnly:{type:Boolean,attribute:"label-sr-only",reflect:!0},helpText:{type:String,attribute:"help-text"},modelValue:{attribute:!1},_ariaLabelledNodes:{attribute:!1},_ariaDescribedNodes:{attribute:!1},_repropagationRole:{attribute:!1},_isRepropagationEndpoint:{attribute:!1}}}get label(){return this.__label??(this._labelNode?.textContent||"")}set label(e){const t=this.label;this.__label=e,this.requestUpdate("label",t)}get helpText(){return this.__helpText??(this._helpTextNode?.textContent||"")}set helpText(e){const t=this.helpText;this.__helpText=e,this.requestUpdate("helpText",t)}get fieldName(){return this.__fieldName||this.label||this.name||""}set fieldName(e){this.__fieldName=e}get slots(){return{...super.slots,label:()=>{const e=document.createElement("label");return e.textContent=this.label,e},"help-text":()=>{const e=document.createElement("div");return e.textContent=this.helpText,e}}}get _inputNode(){return this.__getDirectSlotChild("input")}get _labelNode(){return this.__getDirectSlotChild("label")}get _helpTextNode(){return this.__getDirectSlotChild("help-text")}get _feedbackNode(){return this.__getDirectSlotChild("feedback")}static enabledWarnings=super.enabledWarnings?.filter(e=>e!=="change-in-update")||[];constructor(){super(),this.readOnly=!1,this.labelSrOnly=!1,this._inputId=ta(this.localName),this._ariaLabelledNodes=[],this._ariaDescribedNodes=[],this._repropagationRole="child",this._isRepropagationEndpoint=!1,this.addEventListener("model-value-changed",this.__repropagateChildrenValues),this._onLabelClick=this._onLabelClick.bind(this)}connectedCallback(){super.connectedCallback(),this._enhanceLightDomClasses(),this._enhanceLightDomA11y(),this._triggerInitialModelValueChangedEvent(),this._labelNode&&this._labelNode.addEventListener("click",this._onLabelClick)}disconnectedCallback(){super.disconnectedCallback(),this._labelNode&&this._labelNode.removeEventListener("click",this._onLabelClick)}updated(e){super.updated(e),e.has("disabled")&&this._inputNode?.setAttribute("aria-disabled",`${!!this.disabled}`),e.has("_ariaLabelledNodes")&&this.__reflectAriaAttr("aria-labelledby",this._ariaLabelledNodes,this.__reorderAriaLabelledNodes),e.has("_ariaDescribedNodes")&&this.__reflectAriaAttr("aria-describedby",this._ariaDescribedNodes,this.__reorderAriaDescribedNodes),e.has("label")&&this.__label!==void 0&&this._labelNode&&(this._labelNode.textContent=this.label),e.has("helpText")&&this.__helpText!==void 0&&this._helpTextNode&&(this._helpTextNode.textContent=this.helpText),e.has("name")&&this.dispatchEvent(new CustomEvent("form-element-name-changed",{detail:{oldName:e.get("name"),newName:this.name},bubbles:!0}))}_triggerInitialModelValueChangedEvent(){this._dispatchInitialModelValueChangedEvent()}_enhanceLightDomClasses(){this._inputNode&&this._inputNode.classList.add("form-control")}_enhanceLightDomA11y(){const{_inputNode:e,_labelNode:t,_helpTextNode:r,_feedbackNode:o}=this;e&&(e.id=e.id||this._inputId),t&&(t.setAttribute("for",this._inputId),this.addToAriaLabelledBy(t,{idPrefix:"label"})),r&&this.addToAriaDescribedBy(r,{idPrefix:"help-text"}),o&&(this.addEventListener("focusin",()=>{o.setAttribute("aria-live","polite")}),this.addEventListener("focusout",()=>{o.setAttribute("aria-live","assertive")}),this.addToAriaDescribedBy(o,{idPrefix:"feedback"})),this._enhanceLightDomA11yForAdditionalSlots()}_enhanceLightDomA11yForAdditionalSlots(e=["prefix","suffix","before","after"]){e.forEach(t=>{const r=this.__getDirectSlotChild(t);r&&(r.hasAttribute("data-label")&&this.addToAriaLabelledBy(r,{idPrefix:t}),r.hasAttribute("data-description")&&this.addToAriaDescribedBy(r,{idPrefix:t}))})}__reflectAriaAttr(e,t,r){if(this._inputNode){if(r){const i=t.filter(c=>this.contains(c)),n=t.filter(c=>!this.contains(c)),a=i.map(c=>c.assignedSlot||c),l=[...na(a)],d=[];l.forEach(c=>{i.forEach(h=>{c.name===h.slot&&d.push(h)})}),t=[...d,...n]}const o=t.map(i=>i.id).join(" ");this._inputNode.setAttribute(e,o)}}render(){return E`
        <div class="form-field__group-one">${this._groupOneTemplate()}</div>
        <div class="form-field__group-two">${this._groupTwoTemplate()}</div>
      `}_groupOneTemplate(){return E` ${this._labelTemplate()} ${this._helpTextTemplate()} `}_groupTwoTemplate(){return E` ${this._inputGroupTemplate()} ${this._feedbackTemplate()} `}_labelTemplate(){return E`
        <div class="form-field__label">
          <slot name="label"></slot>
        </div>
      `}_helpTextTemplate(){return E`
        <small class="form-field__help-text">
          <slot name="help-text"></slot>
        </small>
      `}_inputGroupTemplate(){return E`
        <div class="input-group">
          ${this._inputGroupBeforeTemplate()}
          <div class="input-group__container">
            ${this._inputGroupPrefixTemplate()} ${this._inputGroupInputTemplate()}
            ${this._inputGroupSuffixTemplate()}
          </div>
          ${this._inputGroupAfterTemplate()}
        </div>
      `}_inputGroupBeforeTemplate(){return E`
        <div class="input-group__before">
          <slot name="before"></slot>
        </div>
      `}_inputGroupPrefixTemplate(){return Array.from(this.children).find(e=>e.slot==="prefix")?E`
            <div class="input-group__prefix">
              <slot name="prefix"></slot>
            </div>
          `:V}_inputGroupInputTemplate(){return E`
        <div class="input-group__input">
          <slot name="input"></slot>
        </div>
      `}_inputGroupSuffixTemplate(){return Array.from(this.children).find(e=>e.slot==="suffix")?E`
            <div class="input-group__suffix">
              <slot name="suffix"></slot>
            </div>
          `:V}_inputGroupAfterTemplate(){return E`
        <div class="input-group__after">
          <slot name="after"></slot>
        </div>
      `}_feedbackTemplate(){return E`
        <div class="form-field__feedback">
          <slot name="feedback"></slot>
        </div>
      `}_isEmpty(e=this.modelValue){let t=e;if(this.modelValue instanceof qs&&(t=this.modelValue.viewValue),typeof t=="object"&&t!==null&&!(t instanceof Date))return!Object.keys(t).length;const r=typeof t=="number"&&(t===0||Number.isNaN(t));return!t&&!r&&!(typeof t=="boolean"&&t===!1)}static get styles(){return[P`
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
        `]}_getAriaDescriptionElements(){return[this._helpTextNode,this._feedbackNode]}addToAriaLabelledBy(e,{idPrefix:t="",reorder:r=!0}={}){e.id=e.id||`${t}-${this._inputId}`,this._ariaLabelledNodes.includes(e)||(this._ariaLabelledNodes=[...this._ariaLabelledNodes,e],this.__reorderAriaLabelledNodes=!!r)}removeFromAriaLabelledBy(e){this._ariaLabelledNodes.includes(e)&&(this._ariaLabelledNodes.splice(this._ariaLabelledNodes.indexOf(e),1),this._ariaLabelledNodes=[...this._ariaLabelledNodes],this.__reorderAriaLabelledNodes=!1)}addToAriaDescribedBy(e,{idPrefix:t="",reorder:r=!0}={}){e.id=e.id||`${t}-${this._inputId}`,this._ariaDescribedNodes.includes(e)||(this._ariaDescribedNodes=[...this._ariaDescribedNodes,e],this.__reorderAriaDescribedNodes=!!r)}removeFromAriaDescribedBy(e){this._ariaDescribedNodes.includes(e)&&(this._ariaDescribedNodes.splice(this._ariaDescribedNodes.indexOf(e),1),this._ariaDescribedNodes=[...this._ariaDescribedNodes],this.__reorderAriaLabelledNodes=!1)}__getDirectSlotChild(e){return Array.from(this.children).find(t=>t.slot===e)}_dispatchInitialModelValueChangedEvent(){this._repropagationRole!=="child"&&(this.__repropagateChildrenInitialized=!0,this.dispatchEvent(new CustomEvent("model-value-changed",{bubbles:!0,detail:{formPath:[this],initialize:!0,isTriggeredByUser:!1}})))}_onBeforeRepropagateChildrenValues(e){}__repropagateChildrenValues(e){this._onBeforeRepropagateChildrenValues(e);const t=e.detail&&e.detail.element||e.target,r=this._isRepropagationEndpoint||this._repropagationRole==="choice-group";if(t===this)return;e.stopImmediatePropagation();const o=this._repropagationRole!=="child"&&!this.__repropagateChildrenInitialized,i=e.detail&&e.detail.initialize;if(o||i||!this._repropagationCondition(t))return;let n=[];r||(n=e.detail&&e.detail.formPath||[t]);const a=[...n,this];this.dispatchEvent(new CustomEvent("model-value-changed",{bubbles:!0,detail:{formPath:a,isTriggeredByUser:!!e.detail?.isTriggeredByUser}}))}_repropagationCondition(e){return!!e}_onLabelClick(){}},Mt=ie(tu);function $i(s=[],e=[]){return s.filter(t=>!e.includes(t)).concat(e.filter(t=>!s.includes(t)))}function su(s){return s instanceof qs?s.viewValue:s}const ru=s=>class extends Mt(Jd(hs(ms(ia(s))))){static get scopedElements(){return{...super.scopedElements,"lion-validation-feedback":Zd}}static get properties(){return{validators:{attribute:!1},hasFeedbackFor:{attribute:!1},shouldShowFeedbackFor:{attribute:!1},showsFeedbackFor:{type:Array,attribute:"shows-feedback-for",reflect:!0,converter:{fromAttribute:(e=>e.split(",")),toAttribute:(e=>e.join(","))}},validationStates:{attribute:!1},isPending:{type:Boolean,attribute:"is-pending",reflect:!0},defaultValidators:{attribute:!1},_visibleMessagesAmount:{attribute:!1},__childModelValueChanged:{attribute:!1}}}static get validationTypes(){return["error"]}get operationMode(){return"enter"}get slots(){return{...super.slots,feedback:()=>{const e=this.createScopedElement("lion-validation-feedback");return e.setAttribute("data-tag-name","lion-validation-feedback"),e}}}get _allValidators(){return[...this.validators,...this.defaultValidators]}constructor(){super(),this.hasFeedbackFor=[],this.showsFeedbackFor=[],this.shouldShowFeedbackFor=[],this.validationStates={},this.isPending=!1,this.validators=[],this.defaultValidators=[],this._visibleMessagesAmount=1,this.__syncValidationResult=[],this.__asyncValidationResult=[],this.__validationResult=[],this.__prevValidationResult=[],this.__prevShownValidationResult=[],this.__childModelValueChanged=!1,this._onValidatorUpdated=this._onValidatorUpdated.bind(this),this._updateFeedbackComponent=this._updateFeedbackComponent.bind(this)}connectedCallback(){super.connectedCallback(),Gr().addEventListener("localeChanged",this._updateFeedbackComponent)}disconnectedCallback(){super.disconnectedCallback(),Gr().removeEventListener("localeChanged",this._updateFeedbackComponent)}firstUpdated(e){super.firstUpdated(e),this.__isValidateInitialized=!0,this.validate(),this._repropagationRole!=="child"&&this.addEventListener("model-value-changed",()=>{this.__childModelValueChanged=!0})}updateSync(e,t){if(super.updateSync(e,t),e==="validators"?(this.__setupValidators(),this.validate({clearCurrentResult:!0})):e==="modelValue"&&this.validate({clearCurrentResult:!0}),["touched","dirty","prefilled","focused","submitted","hasFeedbackFor","filled"].includes(e)&&this._updateShouldShowFeedbackFor(),e==="showsFeedbackFor"){this._inputNode&&this._inputNode.setAttribute("aria-invalid",`${this._hasFeedbackVisibleFor("error")}`);const r=$i(this.showsFeedbackFor,t);r.length>0&&this.dispatchEvent(new Event("showsFeedbackForChanged",{bubbles:!0})),r.forEach(o=>{this.dispatchEvent(new Event(`showsFeedbackFor${Kd(o)}Changed`,{bubbles:!0}))})}e==="shouldShowFeedbackFor"&&$i(this.shouldShowFeedbackFor,t).length>0&&this.dispatchEvent(new Event("shouldShowFeedbackForChanged",{bubbles:!0}))}async validate({clearCurrentResult:e=!1}={}){if(this.validateComplete=new Promise(t=>{this.__validateCompleteResolve=t}),this.disabled){this.__clearValidationResults(),this.__finishValidationPass(),this._updateFeedbackComponent();return}this.__isValidateInitialized&&(this.__prevValidationResult=this.__validationResult,e&&this.__clearValidationResults(),await this.__executeValidators())}#e(e){let t=e;for(;t;){if(t.constructor.validatorName==="Required")return!0;t=Object.getPrototypeOf(t)}return!1}async __executeValidators(){const e=su(this.modelValue),t=this.__isEmpty(e);if(this.__syncValidationResult=[],t){const a=!this._isFormOrFieldset,l=this._allValidators.find(d=>d.constructor?.validatorName==="Required");if(l&&(this.__syncValidationResult=[{validator:l,outcome:!0}]),a){this.__finishValidationPass({syncValidationResult:this.__syncValidationResult});return}}const r=[],o=[],i=[];for(const a of this._allValidators)a?.executeOnResults?r.push(a):this.#e(a)||(a.constructor.async?i.push(a):o.push(a));const n=!!i.length;this.__syncValidationResult=[...this.__syncValidationResult,...this.__executeSyncValidators(o,e)],this.__finishValidationPass({syncValidationResult:this.__syncValidationResult,metaValidators:r}),n?(this.isPending=!0,this.__asyncValidationResult=await this.__executeAsyncValidators(i,e),this.isPending=!1,this.__finishValidationPass({syncValidationResult:this.__syncValidationResult,asyncValidationResult:this.__asyncValidationResult,metaValidators:r}),this.__validateCompleteResolve?.(!0)):this.__validateCompleteResolve?.(!0)}__executeSyncValidators(e,t){return e.map(r=>({validator:r,outcome:r.execute(t,r.param,{node:this})})).filter(r=>!!r.outcome)}async __executeAsyncValidators(e,t){const r=e.map(i=>i.execute(t,i.param,{node:this})),o=await Promise.all(r);return o.map((i,n)=>({validator:e[n],outcome:o[n]})).filter(i=>!!i.outcome)}__executeMetaValidators(e,t){return t.length?this._isEmpty(this.modelValue)?(this.__prevShownValidationResult=[],[]):t.map(r=>({validator:r,outcome:r.executeOnResults({regularValidationResult:e.map(o=>o.validator),prevValidationResult:this.__prevValidationResult.map(o=>o.validator),prevShownValidationResult:this.__prevShownValidationResult.map(o=>o.validator)})})).filter(r=>!!r.outcome):[]}__finishValidationPass({syncValidationResult:e=[],asyncValidationResult:t=[],metaValidators:r=[]}={}){const o=[...e,...t],i=this.__executeMetaValidators(o,r);this.__validationResult=[...i,...o];const n=this.constructor.validationTypes.reduce((a,l)=>({...a,[l]:{}}),{});for(const{validator:a,outcome:l}of this.__validationResult){n[a.type]||(n[a.type]={});const d=a.constructor;n[a.type][d.validatorName]=l}this.validationStates=n,this.hasFeedbackFor=[...new Set(this.__validationResult.map(({validator:a})=>a.type))],this.dispatchEvent(new Event("validate-performed",{bubbles:!0}))}__clearValidationResults(){this.__syncValidationResult=[],this.__asyncValidationResult=[]}_onValidatorUpdated(e){(e.type==="param-changed"||e.type==="config-changed")&&this.validate()}__setupValidators(){const e=["param-changed","config-changed"];for(const t of this.__prevValidators||[]){for(const r of e)t.removeEventListener?.(r,this._onValidatorUpdated);t.onFormControlDisconnect(this)}for(const t of this._allValidators){if(t.constructor._$isValidator$===void 0){const i=`Validators array only accepts class instances of Validator. Type "${Array.isArray(t)?"array":typeof t}" found. This may be caused by having multiple installations of "@lion/ui/form-core.js".`;throw console.error(i,this),new Error(i)}const r=this.constructor,o=t.constructor;if(r.validationTypes.indexOf(t.type)===-1){const i=`This component does not support the validator type "${t.type}" used in "${o.validatorName}". You may change your validators type or add it to the components "static get validationTypes() {}".`;throw console.error(i,this),new Error(i)}for(const i of e)t.addEventListener?.(i,n=>{this._onValidatorUpdated(n,{validator:t})});t.onFormControlConnect(this)}this.__prevValidators=this._allValidators}__isEmpty(e){return typeof this._isEmpty=="function"?this._isEmpty(e):this.modelValue===null||typeof this.modelValue>"u"||this.modelValue===""}async __getFeedbackMessages(e){let t=await this.fieldName;return Promise.all(e.map(async({validator:r,outcome:o})=>(r.config.fieldName&&(t=await r.config.fieldName),{message:await r._getMessage({modelValue:this.modelValue,formControl:this,fieldName:t,outcome:o}),type:r.type,validator:r,visibilityDuration:r.config?.visibilityDuration||3e3})))}_updateFeedbackComponent(){window.clearTimeout(this.removeMessage);const{_feedbackNode:e}=this;e&&(this.__feedbackQueue||(this.__feedbackQueue=new Wd),this.showsFeedbackFor.length>0?this.__feedbackQueue.add(async()=>{const t=this._prioritizeAndFilterFeedback({validationResult:this.__validationResult.map(o=>o.validator)});this.__prioritizedResult=t.map(o=>this.__validationResult.find(i=>o===i.validator)).filter(Boolean),this.__prioritizedResult.length>0&&(this.__prevShownValidationResult=this.__prioritizedResult);const r=await this.__getFeedbackMessages(this.__prioritizedResult);e.feedbackData=r||[],r?.[0]&&r[0].type==="success"&&r[0].visibilityDuration!==1/0&&(this.removeMessage=window.setTimeout(()=>{e.removeAttribute("type"),e.feedbackData=[]},r[0].visibilityDuration))}):this.__feedbackQueue.add(async()=>{e.feedbackData=[]}),this.feedbackComplete=this.__feedbackQueue.complete)}_showFeedbackConditionFor(e,t){return!0}get _feedbackConditionMeta(){return{modelValue:this.modelValue,el:this}}feedbackCondition(e,t=this._feedbackConditionMeta,r=this._showFeedbackConditionFor.bind(this)){return r(e,t)}_hasFeedbackVisibleFor(e){return this.hasFeedbackFor?.includes(e)&&this.shouldShowFeedbackFor?.includes(e)}updated(e){if(super.updated(e),e.has("shouldShowFeedbackFor")||e.has("hasFeedbackFor")){const t=this.constructor;this.showsFeedbackFor=t.validationTypes.map(r=>this._hasFeedbackVisibleFor(r)?r:void 0).filter(Boolean),this._updateFeedbackComponent()}if(e.has("__childModelValueChanged")&&this.__childModelValueChanged&&(this.validate({clearCurrentResult:!0}),this.__childModelValueChanged=!1),e.has("validationStates")){const t=e.get("validationStates");t&&Object.entries(this.validationStates).forEach(([r,o])=>{t[r]&&JSON.stringify(o)!==JSON.stringify(t[r])&&this.dispatchEvent(new CustomEvent(`${r}StateChanged`,{detail:o}))})}}_updateShouldShowFeedbackFor(){const e=this.constructor.validationTypes.map(t=>this.feedbackCondition(t,this._feedbackConditionMeta,this._showFeedbackConditionFor.bind(this))?t:void 0).filter(Boolean);JSON.stringify(this.shouldShowFeedbackFor)!==JSON.stringify(e)&&(this.shouldShowFeedbackFor=e)}_prioritizeAndFilterFeedback({validationResult:e}){const t=this.constructor.validationTypes;return e.filter(r=>this.feedbackCondition(r.type,this._feedbackConditionMeta,this._showFeedbackConditionFor.bind(this))).sort((r,o)=>t.indexOf(r.type)-t.indexOf(o.type)).slice(0,this._visibleMessagesAmount)}},ar=ie(ru),ou=s=>class extends ar(Mt(s)){static get properties(){return{formattedValue:{attribute:!1},serializedValue:{attribute:!1},formatOptions:{attribute:!1}}}#e={didFormatterOutputSyncToView:!1,didFormatterRun:!1};requestUpdate(e,t,r){super.requestUpdate(e,t,r),e==="modelValue"&&this.modelValue!==t&&this._onModelValueChanged({modelValue:this.modelValue},{modelValue:t}),e==="serializedValue"&&this.serializedValue!==t&&this._calculateValues({source:"serialized"}),e==="formattedValue"&&this.formattedValue!==t&&this._calculateValues({source:"formatted"})}get value(){return this._inputNode?.value||this.__value||""}set value(e){this._inputNode?(this._inputNode.value=e,this.__value=void 0):this.__value=e}preprocessor(e,t){}parser(e,t){return e}formatter(e,t){return e}serializer(e){return e!==void 0?e:""}deserializer(e){return e===void 0?"":e}_calculateValues({source:e}={source:null}){this.__preventRecursiveTrigger||(this.__preventRecursiveTrigger=!0,e!=="model"&&(e==="serialized"?this.modelValue=this.deserializer(this.serializedValue):e==="formatted"&&(this.modelValue=this._callParser())),e!=="formatted"&&(this.formattedValue=this._callFormatter()),e!=="serialized"&&(this.serializedValue=this.serializer(this.modelValue)),this._reflectBackFormattedValueToUser(),this.__preventRecursiveTrigger=!1,this.__prevViewValue=this.value)}_callParser(e=this.formattedValue){if(e==="")return"";if(typeof e!="string")return;const t=this.parser(e,{...this.formatOptions,mode:this.#t(),viewValueStates:this.#s()});return t!==void 0?t:new qs(e)}_callFormatter(){return this.#e.didFormatterRun=!1,this._isHandlingUserInput&&this.hasFeedbackFor?.includes("error")&&this._inputNode?this.value:this.modelValue instanceof qs?this.modelValue.viewValue:(this.#e.didFormatterRun=!0,this.formatter(this.modelValue,{...this.formatOptions,mode:this.#t(),viewValueStates:this.#s()}))}_onModelValueChanged(...e){this._calculateValues({source:"model"}),this._dispatchModelValueChangedEvent(...e)}_dispatchModelValueChangedEvent(...e){this.dispatchEvent(new CustomEvent("model-value-changed",{bubbles:!0,detail:{formPath:[this],isTriggeredByUser:!!this._isHandlingUserInput}}))}_syncValueUpwards(){this.__isHandlingComposition||this.__handlePreprocessor();const e=this.formattedValue;this.modelValue=this._callParser(this.value),e===this.formattedValue&&this.__prevViewValue!==this.value&&this._calculateValues()}__handlePreprocessor(){let e=this.value.length;this._inputNode&&"selectionStart"in this._inputNode&&this._inputNode?.type!=="range"&&(e=this._inputNode.selectionStart);const t=this.preprocessor(this.value,{...this.formatOptions,currentCaretIndex:e,prevViewValue:this.__prevViewValue});if(t!==void 0){if(typeof t=="string")this.value=t;else if(typeof t=="object"){const{viewValue:r,caretIndex:o}=t;this.value=r,o&&this._inputNode&&"selectionStart"in this._inputNode&&(this._inputNode.selectionStart=o,this._inputNode.selectionEnd=o)}}}_reflectBackFormattedValueToUser(){this._reflectBackOn()&&(this.value=typeof this.formattedValue<"u"?this.formattedValue:"",this.#e.didFormatterOutputSyncToView=!!this.formattedValue&&this.#e.didFormatterRun)}_reflectBackOn(){return!this._isHandlingUserInput}_proxyInputEvent(){this.dispatchEvent(new Event("user-input-changed",{bubbles:!0}))}_onUserInputChanged(){this._isHandlingUserInput=!0,this._syncValueUpwards(),this._isHandlingUserInput=!1}__onCompositionEvent({type:e}){e==="compositionstart"?this.__isHandlingComposition=!0:e==="compositionend"&&(this.__isHandlingComposition=!1,this._syncValueUpwards())}constructor(){super(),this.formatOn="change",this.formatOptions={mode:"auto"},this.formattedValue=void 0,this.serializedValue=void 0,this._isPasting=!1,this._isHandlingUserInput=!1,this.__prevViewValue="",this.__onCompositionEvent=this.__onCompositionEvent.bind(this),this.addEventListener("user-input-changed",this._onUserInputChanged),this.addEventListener("paste",this.__onPaste),this._reflectBackFormattedValueToUser=this._reflectBackFormattedValueToUser.bind(this),this._reflectBackFormattedValueDebounced=()=>{setTimeout(this._reflectBackFormattedValueToUser)}}__onPaste(){this._isPasting=!0,setTimeout(()=>{this._isPasting=!1})}connectedCallback(){super.connectedCallback(),typeof this.modelValue>"u"&&this._syncValueUpwards(),this.__prevViewValue=this.value,this._reflectBackFormattedValueToUser(),this._inputNode&&(this._inputNode.addEventListener(this.formatOn,this._reflectBackFormattedValueDebounced),this._inputNode.addEventListener("input",this._proxyInputEvent),this._inputNode.addEventListener("compositionstart",this.__onCompositionEvent),this._inputNode.addEventListener("compositionend",this.__onCompositionEvent))}disconnectedCallback(){super.disconnectedCallback(),this._inputNode&&(this._inputNode.removeEventListener("input",this._proxyInputEvent),this._inputNode.removeEventListener(this.formatOn,this._reflectBackFormattedValueDebounced),this._inputNode.removeEventListener("compositionstart",this.__onCompositionEvent),this._inputNode.removeEventListener("compositionend",this.__onCompositionEvent))}#t(){return this._isPasting?"pasted":this._isHandlingUserInput&&this.__prevViewValue?"user-edited":"auto"}#s(){const e=[];return this.#e.didFormatterOutputSyncToView&&e.push("formatted"),e}},Ro=ie(ou),iu=s=>class extends Mt(s){static get properties(){return{touched:{type:Boolean,reflect:!0},dirty:{type:Boolean,reflect:!0},filled:{type:Boolean,reflect:!0},prefilled:{attribute:!1},submitted:{attribute:!1}}}requestUpdate(e,t,r){super.requestUpdate(e,t,r),e==="touched"&&this.touched!==t&&this._onTouchedChanged(),e==="modelValue"&&(this.filled=!this._isEmpty()),e==="dirty"&&this.dirty!==t&&this._onDirtyChanged()}constructor(){super(),this.touched=!1,this.dirty=!1,this.prefilled=!1,this.filled=!1,this._leaveEvent="blur",this._valueChangedEvent="model-value-changed",this._iStateOnLeave=this._iStateOnLeave.bind(this),this._iStateOnValueChange=this._iStateOnValueChange.bind(this)}connectedCallback(){super.connectedCallback(),this.addEventListener(this._leaveEvent,this._iStateOnLeave),this.addEventListener(this._valueChangedEvent,this._iStateOnValueChange),this.initInteractionState()}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener(this._leaveEvent,this._iStateOnLeave),this.removeEventListener(this._valueChangedEvent,this._iStateOnValueChange)}initInteractionState(){this.dirty=!1,this.prefilled=!this._isEmpty()}_iStateOnLeave(){this.touched=!0,this.prefilled=!this._isEmpty()}_iStateOnValueChange(){this.dirty=!0}resetInteractionState(){this.touched=!1,this.submitted=!1,this.dirty=!1,this.prefilled=!this._isEmpty()}_onTouchedChanged(){this.dispatchEvent(new Event("touched-changed",{bubbles:!0,composed:!0}))}_onDirtyChanged(){this.dispatchEvent(new Event("dirty-changed",{bubbles:!0,composed:!0}))}_showFeedbackConditionFor(e,t){return t.touched&&t.dirty||t.prefilled||t.submitted}get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,submitted:this.submitted,touched:this.touched,dirty:this.dirty,filled:this.filled,prefilled:this.prefilled}}},aa=ie(iu),Yr=window,Ei=new WeakMap;function nu(s){Yr.applyFocusVisiblePolyfill&&!Ei.has(s)&&(Yr.applyFocusVisiblePolyfill(s),Ei.set(s,void 0))}const au=s=>class extends s{static get properties(){return{focused:{type:Boolean,reflect:!0},focusedVisible:{type:Boolean,reflect:!0,attribute:"focused-visible"},autofocus:{type:Boolean,reflect:!0}}}constructor(){super(),this.focused=!1,this.focusedVisible=!1,this.autofocus=!1}firstUpdated(e){super.firstUpdated(e),this.__registerEventsForFocusMixin(),this.__syncAutofocusToFocusableElement()}disconnectedCallback(){super.disconnectedCallback(),this.__teardownEventsForFocusMixin()}updated(e){super.updated(e),e.has("autofocus")&&this.__syncAutofocusToFocusableElement()}__syncAutofocusToFocusableElement(){this._focusableNode&&(this.hasAttribute("autofocus")?this._focusableNode.setAttribute("autofocus",""):this._focusableNode.removeAttribute("autofocus"))}focus(){this._focusableNode?.focus()}blur(){this._focusableNode?.blur()}get _focusableNode(){return this._inputNode||document.createElement("input")}__onFocus(){if(this.focused=!0,typeof Yr.applyFocusVisiblePolyfill=="function")this.focusedVisible=this._focusableNode.hasAttribute("data-focus-visible-added");else try{this.focusedVisible=this._focusableNode.matches(":focus-visible")}catch{this.focusedVisible=!1}}__onBlur(){this.focused=!1,this.focusedVisible=!1}__registerEventsForFocusMixin(){nu(this.getRootNode()),this.__redispatchFocus=e=>{e.stopPropagation(),this.dispatchEvent(new Event("focus"))},this._focusableNode.addEventListener("focus",this.__redispatchFocus),this.__redispatchBlur=e=>{e.stopPropagation(),this.dispatchEvent(new Event("blur"))},this._focusableNode.addEventListener("blur",this.__redispatchBlur),this.__redispatchFocusin=e=>{e.stopPropagation(),this.__onFocus(),this.dispatchEvent(new Event("focusin",{bubbles:!0,composed:!0}))},this._focusableNode.addEventListener("focusin",this.__redispatchFocusin),this.__redispatchFocusout=e=>{e.stopPropagation(),this.__onBlur(),this.dispatchEvent(new Event("focusout",{bubbles:!0,composed:!0}))},this._focusableNode.addEventListener("focusout",this.__redispatchFocusout)}__teardownEventsForFocusMixin(){this._focusableNode&&(this._focusableNode?.removeEventListener("focus",this.__redispatchFocus),this._focusableNode?.removeEventListener("blur",this.__redispatchBlur),this._focusableNode?.removeEventListener("focusin",this.__redispatchFocusin),this._focusableNode?.removeEventListener("focusout",this.__redispatchFocusout))}},la=ie(au);let lr=class extends Mt(aa(la(Ro(ar(ms(Y)))))){firstUpdated(e){super.firstUpdated(e),this._initialModelValue=this.modelValue}connectedCallback(){super.connectedCallback(),this._onChange=this._onChange.bind(this),this._inputNode.addEventListener("change",this._onChange),this.classList.add("form-field")}disconnectedCallback(){super.disconnectedCallback(),this._inputNode?.removeEventListener("change",this._onChange)}resetInteractionState(){super.resetInteractionState(),this.submitted=!1}reset(){this.modelValue=this._initialModelValue,this.resetInteractionState()}clear(){this.modelValue=""}_onChange(e){this.dispatchEvent(new Event("user-input-changed",{bubbles:!0}))}get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,focused:this.focused}}get _focusableNode(){return this._inputNode}};const lu=s=>class extends Ro(la(Mt(s))){static get properties(){return{autocomplete:{type:String,reflect:!0}}}constructor(){super(),this.autocomplete=void 0}get _inputNode(){return super._inputNode}get selectionStart(){const e=this._inputNode;return e&&e.selectionStart?e.selectionStart:0}set selectionStart(e){const t=this._inputNode;t&&t.selectionStart&&(t.selectionStart=e)}get selectionEnd(){const e=this._inputNode;return e&&e.selectionEnd?e.selectionEnd:0}set selectionEnd(e){const t=this._inputNode;t&&t.selectionEnd&&(t.selectionEnd=e)}get value(){return this._inputNode&&this._inputNode.value||this.__value||""}set value(e){this._inputNode?(this._inputNode.value!==e&&this._setValueAndPreserveCaret(e),this.__value=void 0):this.__value=e}_setValueAndPreserveCaret(e){if(this.focused)try{if(!(this._inputNode instanceof HTMLSelectElement)){const t=this._inputNode.selectionStart;this._inputNode.value=e,this._inputNode.selectionStart=t,this._inputNode.selectionEnd=t}}catch{this._inputNode.value=e}else this._inputNode.value=e}_reflectBackFormattedValueToUser(){if(super._reflectBackFormattedValueToUser(),this._reflectBackOn()&&this.focused)try{this._inputNode.selectionStart=this._inputNode.value.length}catch{}}get _focusableNode(){return this._inputNode}},ca=ie(lu);let Po=class extends ca(lr){static get properties(){return{readOnly:{type:Boolean,attribute:"readonly",reflect:!0},type:{type:String,reflect:!0},placeholder:{type:String,reflect:!0}}}get slots(){return{...super.slots,input:()=>{const e=document.createElement("input"),t=this.getAttribute("value");return t&&e.setAttribute("value",t),e}}}get _inputNode(){return super._inputNode}constructor(){super(),this.readOnly=!1,this.type="text",this.placeholder=""}requestUpdate(e,t,r){super.requestUpdate(e,t,r),e==="readOnly"&&this.__delegateReadOnly()}firstUpdated(e){super.firstUpdated(e),this.__delegateReadOnly()}updated(e){super.updated(e),e.has("type")&&(this._inputNode.type=this.type),e.has("placeholder")&&(this._inputNode.placeholder=this.placeholder),e.has("disabled")&&(this._inputNode.disabled=this.disabled,this.validate()),e.has("name")&&(this._inputNode.name=this.name),e.has("autocomplete")&&(this._inputNode.autocomplete=this.autocomplete)}__delegateReadOnly(){this._inputNode&&(this._inputNode.readOnly=this.readOnly)}};var cu=Object.defineProperty,du=(s,e,t,r)=>{for(var o=void 0,i=s.length-1,n;i>=0;i--)(n=s[i])&&(o=n(e,t,o)||o);return o&&cu(e,t,o),o};let da=class extends Po{constructor(){super(...arguments),this.size=""}static get styles(){return[...super.styles,Lo,wd]}connectedCallback(){if(super.connectedCallback(),this._inputNode){const e=parseInt(this.size,10);e>0&&(this._inputNode.size=e)}}};du([w({type:Number,reflect:!0})],da.prototype,"size");customElements.get("craft-input")||customElements.define("craft-input",da);var uu=class extends Event{constructor(){super("wa-load",{bubbles:!0,cancelable:!1,composed:!0})}},Xr="";function hu(s){Xr=s}function pu(){if(!Xr){const s=document.querySelector("[data-fa-kit-code]");s&&hu(s.getAttribute("data-fa-kit-code")||"")}return Xr}var Ve="7.0.1";function mu(s,e,t){const r=pu(),o=r.length>0;let i="solid";return e==="notdog"?(t==="solid"&&(i="solid"),t==="duo-solid"&&(i="duo-solid"),`https://ka-p.fontawesome.com/releases/v${Ve}/svgs/notdog-${i}/${s}.svg?token=${encodeURIComponent(r)}`):e==="chisel"?`https://ka-p.fontawesome.com/releases/v${Ve}/svgs/chisel-regular/${s}.svg?token=${encodeURIComponent(r)}`:e==="etch"?`https://ka-p.fontawesome.com/releases/v${Ve}/svgs/etch-solid/${s}.svg?token=${encodeURIComponent(r)}`:e==="jelly"?(t==="regular"&&(i="regular"),t==="duo-regular"&&(i="duo-regular"),t==="fill-regular"&&(i="fill-regular"),`https://ka-p.fontawesome.com/releases/v${Ve}/svgs/jelly-${i}/${s}.svg?token=${encodeURIComponent(r)}`):e==="slab"?((t==="solid"||t==="regular")&&(i="regular"),t==="press-regular"&&(i="press-regular"),`https://ka-p.fontawesome.com/releases/v${Ve}/svgs/slab-${i}/${s}.svg?token=${encodeURIComponent(r)}`):e==="thumbprint"?`https://ka-p.fontawesome.com/releases/v${Ve}/svgs/thumbprint-light/${s}.svg?token=${encodeURIComponent(r)}`:e==="whiteboard"?`https://ka-p.fontawesome.com/releases/v${Ve}/svgs/whiteboard-semibold/${s}.svg?token=${encodeURIComponent(r)}`:(e==="classic"&&(t==="thin"&&(i="thin"),t==="light"&&(i="light"),t==="regular"&&(i="regular"),t==="solid"&&(i="solid")),e==="sharp"&&(t==="thin"&&(i="sharp-thin"),t==="light"&&(i="sharp-light"),t==="regular"&&(i="sharp-regular"),t==="solid"&&(i="sharp-solid")),e==="duotone"&&(t==="thin"&&(i="duotone-thin"),t==="light"&&(i="duotone-light"),t==="regular"&&(i="duotone-regular"),t==="solid"&&(i="duotone")),e==="sharp-duotone"&&(t==="thin"&&(i="sharp-duotone-thin"),t==="light"&&(i="sharp-duotone-light"),t==="regular"&&(i="sharp-duotone-regular"),t==="solid"&&(i="sharp-duotone-solid")),e==="brands"&&(i="brands"),o?`https://ka-p.fontawesome.com/releases/v${Ve}/svgs/${i}/${s}.svg?token=${encodeURIComponent(r)}`:`https://ka-f.fontawesome.com/releases/v${Ve}/svgs/${i}/${s}.svg`)}var fu={name:"default",resolver:(s,e="classic",t="solid")=>mu(s,e,t),mutator:(s,e)=>{if(e?.family&&!s.hasAttribute("data-duotone-initialized")){const{family:t,variant:r}=e;if(t==="duotone"||t==="sharp-duotone"||t==="notdog"&&r==="duo-solid"||t==="jelly"&&r==="duo-regular"||t==="thumbprint"){const o=[...s.querySelectorAll("path")],i=o.find(a=>!a.hasAttribute("opacity")),n=o.find(a=>a.hasAttribute("opacity"));if(!i||!n)return;if(i.setAttribute("data-duotone-primary",""),n.setAttribute("data-duotone-secondary",""),e.swapOpacity&&i&&n){const a=n.getAttribute("opacity")||"0.4";i.style.setProperty("--path-opacity",a),n.style.setProperty("--path-opacity","1")}s.setAttribute("data-duotone-initialized","")}}}},gu=fu;function bu(s){return`data:image/svg+xml,${encodeURIComponent(s)}`}var Sr={solid:{check:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M434.8 70.1c14.3 10.4 17.5 30.4 7.1 44.7l-256 352c-5.5 7.6-14 12.3-23.4 13.1s-18.5-2.7-25.1-9.3l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l101.5 101.5 234-321.7c10.4-14.3 30.4-17.5 44.7-7.1z"/></svg>',"chevron-down":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M201.4 406.6c12.5 12.5 32.8 12.5 45.3 0l192-192c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L224 338.7 54.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l192 192z"/></svg>',"chevron-left":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l192 192c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L77.3 256 246.6 86.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-192 192z"/></svg>',"chevron-right":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M311.1 233.4c12.5 12.5 12.5 32.8 0 45.3l-192 192c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L243.2 256 73.9 86.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l192 192z"/></svg>',circle:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0z"/></svg>',eyedropper:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M341.6 29.2l-101.6 101.6-9.4-9.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3l-9.4-9.4 101.6-101.6c39-39 39-102.2 0-141.1s-102.2-39-141.1 0zM55.4 323.3c-15 15-23.4 35.4-23.4 56.6l0 42.4-26.6 39.9c-8.5 12.7-6.8 29.6 4 40.4s27.7 12.5 40.4 4l39.9-26.6 42.4 0c21.2 0 41.6-8.4 56.6-23.4l109.4-109.4-45.3-45.3-109.4 109.4c-3 3-7.1 4.7-11.3 4.7l-36.1 0 0-36.1c0-4.2 1.7-8.3 4.7-11.3l109.4-109.4-45.3-45.3-109.4 109.4z"/></svg>',"grip-vertical":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M128 40c0-22.1-17.9-40-40-40L40 0C17.9 0 0 17.9 0 40L0 88c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zm0 192c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zM0 424l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zM320 40c0-22.1-17.9-40-40-40L232 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zM192 232l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zM320 424c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48z"/></svg>',indeterminate:'<svg part="indeterminate-icon" class="icon" viewBox="0 0 16 16"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" stroke-linecap="round"><g stroke="currentColor" stroke-width="2"><g transform="translate(2.285714 6.857143)"><path d="M10.2857143,1.14285714 L1.14285714,1.14285714"/></g></g></g></svg>',minus:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M0 256c0-17.7 14.3-32 32-32l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 288c-17.7 0-32-14.3-32-32z"/></svg>',pause:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M48 32C21.5 32 0 53.5 0 80L0 432c0 26.5 21.5 48 48 48l64 0c26.5 0 48-21.5 48-48l0-352c0-26.5-21.5-48-48-48L48 32zm224 0c-26.5 0-48 21.5-48 48l0 352c0 26.5 21.5 48 48 48l64 0c26.5 0 48-21.5 48-48l0-352c0-26.5-21.5-48-48-48l-64 0z"/></svg>',play:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M91.2 36.9c-12.4-6.8-27.4-6.5-39.6 .7S32 57.9 32 72l0 368c0 14.1 7.5 27.2 19.6 34.4s27.2 7.5 39.6 .7l336-184c12.8-7 20.8-20.5 20.8-35.1s-8-28.1-20.8-35.1l-336-184z"/></svg>',star:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M309.5-18.9c-4.1-8-12.4-13.1-21.4-13.1s-17.3 5.1-21.4 13.1L193.1 125.3 33.2 150.7c-8.9 1.4-16.3 7.7-19.1 16.3s-.5 18 5.8 24.4l114.4 114.5-25.2 159.9c-1.4 8.9 2.3 17.9 9.6 23.2s16.9 6.1 25 2L288.1 417.6 432.4 491c8 4.1 17.7 3.3 25-2s11-14.2 9.6-23.2L441.7 305.9 556.1 191.4c6.4-6.4 8.6-15.8 5.8-24.4s-10.1-14.9-19.1-16.3L383 125.3 309.5-18.9z"/></svg>',user:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M224 248a120 120 0 1 0 0-240 120 120 0 1 0 0 240zm-29.7 56C95.8 304 16 383.8 16 482.3 16 498.7 29.3 512 45.7 512l356.6 0c16.4 0 29.7-13.3 29.7-29.7 0-98.5-79.8-178.3-178.3-178.3l-59.4 0z"/></svg>',xmark:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M55.1 73.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L147.2 256 9.9 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192.5 301.3 329.9 438.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.8 256 375.1 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192.5 210.7 55.1 73.4z"/></svg>'},regular:{"circle-question":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M464 256a208 208 0 1 0 -416 0 208 208 0 1 0 416 0zM0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0zm256-80c-17.7 0-32 14.3-32 32 0 13.3-10.7 24-24 24s-24-10.7-24-24c0-44.2 35.8-80 80-80s80 35.8 80 80c0 47.2-36 67.2-56 74.5l0 3.8c0 13.3-10.7 24-24 24s-24-10.7-24-24l0-8.1c0-20.5 14.8-35.2 30.1-40.2 6.4-2.1 13.2-5.5 18.2-10.3 4.3-4.2 7.7-10 7.7-19.6 0-17.7-14.3-32-32-32zM224 368a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>',"circle-xmark":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M256 48a208 208 0 1 1 0 416 208 208 0 1 1 0-416zm0 464a256 256 0 1 0 0-512 256 256 0 1 0 0 512zM167 167c-9.4 9.4-9.4 24.6 0 33.9l55 55-55 55c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l55-55 55 55c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-55-55 55-55c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-55 55-55-55c-9.4-9.4-24.6-9.4-33.9 0z"/></svg>',copy:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M384 336l-192 0c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l133.5 0c4.2 0 8.3 1.7 11.3 4.7l58.5 58.5c3 3 4.7 7.1 4.7 11.3L400 320c0 8.8-7.2 16-16 16zM192 384l192 0c35.3 0 64-28.7 64-64l0-197.5c0-17-6.7-33.3-18.7-45.3L370.7 18.7C358.7 6.7 342.5 0 325.5 0L192 0c-35.3 0-64 28.7-64 64l0 256c0 35.3 28.7 64 64 64zM64 128c-35.3 0-64 28.7-64 64L0 448c0 35.3 28.7 64 64 64l192 0c35.3 0 64-28.7 64-64l0-16-48 0 0 16c0 8.8-7.2 16-16 16L64 464c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l16 0 0-48-16 0z"/></svg>',eye:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M288 80C222.8 80 169.2 109.6 128.1 147.7 89.6 183.5 63 226 49.4 256 63 286 89.6 328.5 128.1 364.3 169.2 402.4 222.8 432 288 432s118.8-29.6 159.9-67.7C486.4 328.5 513 286 526.6 256 513 226 486.4 183.5 447.9 147.7 406.8 109.6 353.2 80 288 80zM95.4 112.6C142.5 68.8 207.2 32 288 32s145.5 36.8 192.6 80.6c46.8 43.5 78.1 95.4 93 131.1 3.3 7.9 3.3 16.7 0 24.6-14.9 35.7-46.2 87.7-93 131.1-47.1 43.7-111.8 80.6-192.6 80.6S142.5 443.2 95.4 399.4c-46.8-43.5-78.1-95.4-93-131.1-3.3-7.9-3.3-16.7 0-24.6 14.9-35.7 46.2-87.7 93-131.1zM288 336c44.2 0 80-35.8 80-80 0-29.6-16.1-55.5-40-69.3-1.4 59.7-49.6 107.9-109.3 109.3 13.8 23.9 39.7 40 69.3 40zm-79.6-88.4c2.5 .3 5 .4 7.6 .4 35.3 0 64-28.7 64-64 0-2.6-.2-5.1-.4-7.6-37.4 3.9-67.2 33.7-71.1 71.1zm45.6-115c10.8-3 22.2-4.5 33.9-4.5 8.8 0 17.5 .9 25.8 2.6 .3 .1 .5 .1 .8 .2 57.9 12.2 101.4 63.7 101.4 125.2 0 70.7-57.3 128-128 128-61.6 0-113-43.5-125.2-101.4-1.8-8.6-2.8-17.5-2.8-26.6 0-11 1.4-21.8 4-32 .2-.7 .3-1.3 .5-1.9 11.9-43.4 46.1-77.6 89.5-89.5z"/></svg>',"eye-slash":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M41-24.9c-9.4-9.4-24.6-9.4-33.9 0S-2.3-.3 7 9.1l528 528c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-96.4-96.4c2.7-2.4 5.4-4.8 8-7.2 46.8-43.5 78.1-95.4 93-131.1 3.3-7.9 3.3-16.7 0-24.6-14.9-35.7-46.2-87.7-93-131.1-47.1-43.7-111.8-80.6-192.6-80.6-56.8 0-105.6 18.2-146 44.2L41-24.9zM176.9 111.1c32.1-18.9 69.2-31.1 111.1-31.1 65.2 0 118.8 29.6 159.9 67.7 38.5 35.7 65.1 78.3 78.6 108.3-13.6 30-40.2 72.5-78.6 108.3-3.1 2.8-6.2 5.6-9.4 8.4L393.8 328c14-20.5 22.2-45.3 22.2-72 0-70.7-57.3-128-128-128-26.7 0-51.5 8.2-72 22.2l-39.1-39.1zm182 182l-108-108c11.1-5.8 23.7-9.1 37.1-9.1 44.2 0 80 35.8 80 80 0 13.4-3.3 26-9.1 37.1zM103.4 173.2l-34-34c-32.6 36.8-55 75.8-66.9 104.5-3.3 7.9-3.3 16.7 0 24.6 14.9 35.7 46.2 87.7 93 131.1 47.1 43.7 111.8 80.6 192.6 80.6 37.3 0 71.2-7.9 101.5-20.6L352.2 422c-20 6.4-41.4 10-64.2 10-65.2 0-118.8-29.6-159.9-67.7-38.5-35.7-65.1-78.3-78.6-108.3 10.4-23.1 28.6-53.6 54-82.8z"/></svg>',star:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M288.1-32c9 0 17.3 5.1 21.4 13.1L383 125.3 542.9 150.7c8.9 1.4 16.3 7.7 19.1 16.3s.5 18-5.8 24.4L441.7 305.9 467 465.8c1.4 8.9-2.3 17.9-9.6 23.2s-17 6.1-25 2L288.1 417.6 143.8 491c-8 4.1-17.7 3.3-25-2s-11-14.2-9.6-23.2L134.4 305.9 20 191.4c-6.4-6.4-8.6-15.8-5.8-24.4s10.1-14.9 19.1-16.3l159.9-25.4 73.6-144.2c4.1-8 12.4-13.1 21.4-13.1zm0 76.8L230.3 158c-3.5 6.8-10 11.6-17.6 12.8l-125.5 20 89.8 89.9c5.4 5.4 7.9 13.1 6.7 20.7l-19.8 125.5 113.3-57.6c6.8-3.5 14.9-3.5 21.8 0l113.3 57.6-19.8-125.5c-1.2-7.6 1.3-15.3 6.7-20.7l89.8-89.9-125.5-20c-7.6-1.2-14.1-6-17.6-12.8L288.1 44.8z"/></svg>'}},vu={name:"system",resolver:(s,e="classic",t="solid")=>{let r=Sr[t][s]??Sr.regular[s]??Sr.regular["circle-question"];return r?bu(r):""}},yu=vu,wu="classic",_u=[gu,yu],Zr=[];function $u(s){Zr.push(s)}function Eu(s){Zr=Zr.filter(e=>e!==s)}function Ar(s){return _u.find(e=>e.name===s)}function xu(){return wu}var ku=class extends Event{constructor(){super("wa-error",{bubbles:!0,cancelable:!1,composed:!0})}},Cu=`:host {
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
`,Ut=Symbol(),ks=Symbol(),Tr,Or=new Map,he=class extends ge{constructor(){super(...arguments),this.svg=null,this.autoWidth=!1,this.swapOpacity=!1,this.label="",this.library="default",this.resolveIcon=async(e,t)=>{let r;if(t?.spriteSheet){this.hasUpdated||await this.updateComplete,this.svg=E`<svg part="svg">
        <use part="use" href="${e}"></use>
      </svg>`,await this.updateComplete;const o=this.shadowRoot.querySelector("[part='svg']");return typeof t.mutator=="function"&&t.mutator(o,this),this.svg}try{if(r=await fetch(e,{mode:"cors"}),!r.ok)return r.status===410?Ut:ks}catch{return ks}try{const o=document.createElement("div");o.innerHTML=await r.text();const i=o.firstElementChild;if(i?.tagName?.toLowerCase()!=="svg")return Ut;Tr||(Tr=new DOMParser);const n=Tr.parseFromString(i.outerHTML,"text/html").body.querySelector("svg");return n?(n.part.add("svg"),document.adoptNode(n)):Ut}catch{return Ut}}}connectedCallback(){super.connectedCallback(),$u(this)}firstUpdated(e){super.firstUpdated(e),this.setIcon()}disconnectedCallback(){super.disconnectedCallback(),Eu(this)}getIconSource(){const e=Ar(this.library),t=this.family||xu();return this.name&&e?{url:e.resolver(this.name,t,this.variant,this.autoWidth),fromLibrary:!0}:{url:this.src,fromLibrary:!1}}handleLabelChange(){typeof this.label=="string"&&this.label.length>0?(this.setAttribute("role","img"),this.setAttribute("aria-label",this.label),this.removeAttribute("aria-hidden")):(this.removeAttribute("role"),this.removeAttribute("aria-label"),this.setAttribute("aria-hidden","true"))}async setIcon(){const{url:e,fromLibrary:t}=this.getIconSource(),r=t?Ar(this.library):void 0;if(!e){this.svg=null;return}let o=Or.get(e);o||(o=this.resolveIcon(e,r),Or.set(e,o));const i=await o;if(i===ks&&Or.delete(e),e===this.getIconSource().url){if(ea(i)){this.svg=i;return}switch(i){case ks:case Ut:this.svg=null,this.dispatchEvent(new ku);break;default:this.svg=i.cloneNode(!0),r?.mutator?.(this.svg,this),this.dispatchEvent(new uu)}}}updated(e){super.updated(e);const t=Ar(this.library),r=this.shadowRoot?.querySelector("svg");r&&t?.mutator?.(r,this)}render(){return this.hasUpdated?this.svg:E`<svg part="svg" fill="currentColor" width="16" height="16"></svg>`}};he.css=Cu;v([be()],he.prototype,"svg",2);v([w({reflect:!0})],he.prototype,"name",2);v([w({reflect:!0})],he.prototype,"family",2);v([w({reflect:!0})],he.prototype,"variant",2);v([w({attribute:"auto-width",type:Boolean,reflect:!0})],he.prototype,"autoWidth",2);v([w({attribute:"swap-opacity",type:Boolean,reflect:!0})],he.prototype,"swapOpacity",2);v([w()],he.prototype,"src",2);v([w()],he.prototype,"label",2);v([w({reflect:!0})],he.prototype,"library",2);v([_e("label")],he.prototype,"handleLabelChange",1);v([_e(["family","name","library","variant","src","autoWidth","swapOpacity"])],he.prototype,"setIcon",1);he=v([Ae("wa-icon")],he);const Su=P``;let Au=class extends he{static get styles(){return[he.styles,Su]}};customElements.get("craft-icon")||customElements.define("craft-icon",Au);var Tu=Object.defineProperty,Ou=(s,e,t,r)=>{for(var o=void 0,i=s.length-1,n;i>=0;i--)(n=s[i])&&(o=n(e,t,o)||o);return o&&Tu(e,t,o),o};let ua=class extends Po{constructor(){super(),this._visible=!1,this.reveal=()=>{this._visible=!this._visible,this.type=this._visible?"text":"password"},this.renderSuffix=()=>E`
      <craft-button
        type="button"
        icon
        size="small"
        variant="plain"
        @click="${this.reveal}"
        appearance="plain"
      >
        <span class="icon"
          >${this._visible?E`<craft-icon name="eye-slash"></craft-icon>`:E`<craft-icon name="eye"></craft-icon>`}
        </span>
      </craft-button>
    `,this.type="password"}static get styles(){return[...super.styles,Lo,P`
        .input-group__container {
          position: relative;
        }

        .input-group__suffix {
          position: absolute;
          inset-inline-end: var(--c-input-spacing-inline);
          inset-block-start: 50%;
          transform: translateY(calc(-50%));
        }
      `]}get slots(){return{...super.slots,suffix:()=>({template:this.renderSuffix()})}}};Ou([be()],ua.prototype,"_visible");customElements.get("craft-input-password")||customElements.define("craft-input-password",ua);const Lu=P`
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
`;var Nu=Object.defineProperty,ha=(s,e,t,r)=>{for(var o=void 0,i=s.length-1,n;i>=0;i--)(n=s[i])&&(o=n(e,t,o)||o);return o&&Nu(e,t,o),o};const pa=class extends Y{constructor(){super(...arguments),this.size="",this.variant=""}render(){const e=!!this.querySelector('[slot="prefix"]'),t=!!this.querySelector('[slot="suffix"]');return E`
      <div
        class="${Re({chip:!0,"chip--small":this.size==="small","chip--medium":this.size==="medium","chip--large":this.size==="large","chip--plain":this.variant==="plain"})}"
      >
        ${e?E`<div class="chip__prefix"><slot name="prefix"></slot></div>`:V}
        <div class="chip__body">
          <slot></slot>
        </div>
        ${t?E`<div class="chip__suffix"><slot name="suffix"></slot></div>`:V}
      </div>
    `}};pa.styles=[Lu];let Fo=pa;ha([w()],Fo.prototype,"size");ha([w()],Fo.prototype,"variant");customElements.get("craft-chip")||customElements.define("craft-chip",Fo);const Ru=P`
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
`;var Pu=Object.defineProperty,ma=(s,e,t,r)=>{for(var o=void 0,i=s.length-1,n;i>=0;i--)(n=s[i])&&(o=n(e,t,o)||o);return o&&Pu(e,t,o),o};const fa=class extends Y{constructor(){super(...arguments),this.label=null,this.status=null}getLabel(){return!this.label&&this.status?`Status: ${this.status}`:this.label}render(){return E`
      <span
        class="${Re({status:!0,"status--live":this.status==="live","status--enabled":this.status==="enabled","status--pending":this.status==="pending","status--expired":this.status==="expired","status--disabled":this.status==="disabled"})}"
        role="img"
        aria-label="${this.getLabel()}"
      ></span>
    `}};fa.styles=[Ru];let Mo=fa;ma([w()],Mo.prototype,"label");ma([w()],Mo.prototype,"status");customElements.get("craft-status")||customElements.define("craft-status",Mo);const Fu=P`
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
`;var Xt=new Map;function Mu(s){var e=Xt.get(s);e&&e.destroy()}function Du(s){var e=Xt.get(s);e&&e.update()}var qt=null;typeof window>"u"?((qt=function(s){return s}).destroy=function(s){return s},qt.update=function(s){return s}):((qt=function(s,e){return s&&Array.prototype.forEach.call(s.length?s:[s],function(t){return(function(r){if(r&&r.nodeName&&r.nodeName==="TEXTAREA"&&!Xt.has(r)){var o,i=null,n=window.getComputedStyle(r),a=(o=r.value,function(){d({testForHeightReduction:o===""||!r.value.startsWith(o),restoreTextAlign:null}),o=r.value}),l=(function(h){r.removeEventListener("autosize:destroy",l),r.removeEventListener("autosize:update",c),r.removeEventListener("input",a),window.removeEventListener("resize",c),Object.keys(h).forEach(function(g){return r.style[g]=h[g]}),Xt.delete(r)}).bind(r,{height:r.style.height,resize:r.style.resize,textAlign:r.style.textAlign,overflowY:r.style.overflowY,overflowX:r.style.overflowX,wordWrap:r.style.wordWrap});r.addEventListener("autosize:destroy",l),r.addEventListener("autosize:update",c),r.addEventListener("input",a),window.addEventListener("resize",c),r.style.overflowX="hidden",r.style.wordWrap="break-word",Xt.set(r,{destroy:l,update:c}),c()}function d(h){var g,f,m=h.restoreTextAlign,b=m===void 0?null:m,_=h.testForHeightReduction,$=_===void 0||_,x=n.overflowY;if(r.scrollHeight!==0&&(n.resize==="vertical"?r.style.resize="none":n.resize==="both"&&(r.style.resize="horizontal"),$&&(g=(function(S){for(var B=[];S&&S.parentNode&&S.parentNode instanceof Element;)S.parentNode.scrollTop&&B.push([S.parentNode,S.parentNode.scrollTop]),S=S.parentNode;return function(){return B.forEach(function(z){var I=z[0],X=z[1];I.style.scrollBehavior="auto",I.scrollTop=X,I.style.scrollBehavior=null})}})(r),r.style.height=""),f=n.boxSizing==="content-box"?r.scrollHeight-(parseFloat(n.paddingTop)+parseFloat(n.paddingBottom)):r.scrollHeight+parseFloat(n.borderTopWidth)+parseFloat(n.borderBottomWidth),n.maxHeight!=="none"&&f>parseFloat(n.maxHeight)?(n.overflowY==="hidden"&&(r.style.overflow="scroll"),f=parseFloat(n.maxHeight)):n.overflowY!=="hidden"&&(r.style.overflow="hidden"),r.style.height=f+"px",b&&(r.style.textAlign=b),g&&g(),i!==f&&(r.dispatchEvent(new Event("autosize:resized",{bubbles:!0})),i=f),x!==n.overflow&&!b)){var A=n.textAlign;n.overflow==="hidden"&&(r.style.textAlign=A==="start"?"end":"start"),d({restoreTextAlign:A,testForHeightReduction:!0})}}function c(){d({testForHeightReduction:!0,restoreTextAlign:null})}})(t)}),s}).destroy=function(s){return s&&Array.prototype.forEach.call(s.length?s:[s],Mu),s},qt.update=function(s){return s&&Array.prototype.forEach.call(s.length?s:[s],Du),s});var Lr=qt;let Vu=class extends lr{get _inputNode(){return Array.from(this.children).find(e=>e.slot==="input")}},zu=class extends ca(Vu){static get properties(){return{maxRows:{type:Number,attribute:"max-rows"},rows:{type:Number,reflect:!0},readOnly:{type:Boolean,attribute:"readonly",reflect:!0},placeholder:{type:String,reflect:!0}}}get slots(){return{...super.slots,input:()=>{const e=document.createElement("textarea");return e.style.resize!==void 0&&(e.style.resize="none"),e}}}constructor(){super(),this.rows=2,this.maxRows=6,this.readOnly=!1,this.placeholder=""}connectedCallback(){super.connectedCallback(),this.__initializeAutoresize(),this.__intersectionObserver=new IntersectionObserver(()=>this.resizeTextarea()),this.__intersectionObserver.observe(this)}updated(e){if(super.updated(e),e.has("name")&&(this._inputNode.name=this.name),e.has("autocomplete")&&(this._inputNode.autocomplete=this.autocomplete),e.has("disabled")&&(this._inputNode.disabled=this.disabled,this.validate()),e.has("rows")){const t=this._inputNode;t&&(t.rows=this.rows)}if(e.has("readOnly")){const t=this._inputNode;t&&(t.readOnly=this.readOnly)}if(e.has("placeholder")){const t=this._inputNode;t&&(t.placeholder=this.placeholder)}e.has("modelValue")&&this.resizeTextarea(),(e.has("maxRows")||e.has("rows"))&&this.setTextareaMaxHeight()}disconnectedCallback(){super.disconnectedCallback(),Lr.destroy(this._inputNode)}setTextareaMaxHeight(){const{value:e}=this._inputNode;this._inputNode.value="",this.resizeTextarea();const t=window.getComputedStyle(this._inputNode,null),r=parseFloat(t.lineHeight)||parseFloat(t.height)/this.rows,o=parseFloat(t.paddingTop)+parseFloat(t.paddingBottom),i=parseFloat(t.borderTopWidth)+parseFloat(t.borderBottomWidth),n=t.boxSizing==="border-box"?o+i:0;this._inputNode.style.maxHeight=`${r*this.maxRows+n}px`,this._inputNode.value=e,this.resizeTextarea()}static get styles(){return[...super.styles,P`
        .input-group__container > .input-group__input ::slotted(.form-control) {
          box-sizing: content-box;
          overflow-x: hidden; /* for FF adds height to the TextArea to reserve place for scroll-bars */
        }

        /* Workaround https://bugzilla.mozilla.org/show_bug.cgi?id=1739079 */
        :host([disabled]) ::slotted(textarea) {
          user-select: none;
        }
      `]}get updateComplete(){return this.__textareaUpdateComplete?Promise.all([this.__textareaUpdateComplete,super.updateComplete]):super.updateComplete}resizeTextarea(){Lr.update(this._inputNode)}__initializeAutoresize(){this.__shady_native_contains?this.__textareaUpdateComplete=this.__waitForTextareaRenderedInRealDOM().then(()=>{this.__startAutoresize(),this.__textareaUpdateComplete=null}):this.__startAutoresize()}async __waitForTextareaRenderedInRealDOM(){let e=3;for(;e!==0&&!this.__shady_native_contains(this._inputNode);)await new Promise(t=>setTimeout(t)),e-=1}__startAutoresize(){Lr(this._inputNode),this.setTextareaMaxHeight()}},Iu=class extends zu{static get styles(){return[...super.styles,Lo,Fu]}};customElements.get("craft-textarea")||customElements.define("craft-textarea",Iu);const Uu=P`
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
`,ga=class extends Y{render(){return E`<slot></slot>`}};ga.styles=[Uu];let Bu=ga;customElements.get("craft-button-group")||customElements.define("craft-button-group",Bu);const ju=P`
  ${Qn}

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
    ${Zn}
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
`,qu=P`
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
`,Hu=(s,e={})=>s.value!==e.value||s.checked!==e.checked,Wu=s=>class extends Ro(s){static get properties(){return{checked:{type:Boolean,reflect:!0},disabled:{type:Boolean,reflect:!0},modelValue:{type:Object,hasChanged:Hu},choiceValue:{type:Object}}}get choiceValue(){return this.modelValue.value}set choiceValue(e){this.requestUpdate("choiceValue",this.choiceValue),this.modelValue.value!==e&&(this.modelValue={value:e,checked:this.modelValue.checked})}requestUpdate(e,t,r){super.requestUpdate(e,t,r),e==="modelValue"?this.modelValue.checked!==this.checked&&this.__syncModelCheckedToChecked(this.modelValue.checked):e==="checked"&&this.modelValue.checked!==this.checked&&this.__syncCheckedToModel(this.checked)}firstUpdated(e){super.firstUpdated(e),e.has("checked")&&this.__syncCheckedToInputElement()}updated(e){super.updated(e),e.has("modelValue")&&this.__syncCheckedToInputElement(),e.has("name")&&this._parentFormGroup&&this._parentFormGroup.name!==this.name&&this._syncNameToParentFormGroup()}constructor(){super(),this.modelValue={value:"",checked:!1},this.disabled=!1,this._preventDuplicateLabelClick=this._preventDuplicateLabelClick.bind(this),this._toggleChecked=this._toggleChecked.bind(this)}static get styles(){return[...super.styles||[],P`
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
        `]}render(){return E`
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
      `}_choiceGraphicTemplate(){return V}_afterTemplate(){return V}connectedCallback(){super.connectedCallback(),this._labelNode&&this._labelNode.addEventListener("click",this._preventDuplicateLabelClick),this.addEventListener("user-input-changed",this._toggleChecked)}disconnectedCallback(){super.disconnectedCallback(),this._labelNode&&this._labelNode.removeEventListener("click",this._preventDuplicateLabelClick),this.removeEventListener("user-input-changed",this._toggleChecked)}_preventDuplicateLabelClick(e){const t=(r=>{r.stopImmediatePropagation(),this._inputNode.removeEventListener("click",t)});this._inputNode.addEventListener("click",t)}_toggleChecked(e){this.disabled||(this._isHandlingUserInput=!0,this.checked=!this.checked,this._isHandlingUserInput=!1)}_syncNameToParentFormGroup(){this._parentFormGroup.tagName.includes(this.tagName)&&(this.name=this._parentFormGroup?.name||"")}__syncModelCheckedToChecked(e){this.checked=e}__syncCheckedToModel(e){this.modelValue={value:this.choiceValue,checked:e}}__syncCheckedToInputElement(){this._inputNode&&(this._inputNode.checked=this.checked)}_proxyInputEvent(){}_onModelValueChanged({modelValue:e},t){let r;t&&t.modelValue&&(r=t.modelValue),this.constructor.elementProperties.get("modelValue").hasChanged(e,r)&&super._onModelValueChanged({modelValue:e})}parser(){return this.modelValue}formatter(e){return e&&e.value!==void 0?e.value:e}clear(){this.checked=!1}_isEmpty(){return!this.checked}_syncValueUpwards(){}},Do=ie(Wu);let xi=class extends hs(Do(No(ms(Y)))){static get properties(){return{active:{type:Boolean,reflect:!0}}}static get styles(){return[P`
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
      `]}get slots(){return{}}constructor(){super(),this.active=!1,this.__onClick=this.__onClick.bind(this),this.__registerEventListeners()}requestUpdate(e,t,r){super.requestUpdate(e,t,r),e==="active"&&this.active!==t&&this.dispatchEvent(new Event("active-changed",{bubbles:!0}))}updated(e){super.updated(e),e.has("checked")&&this.setAttribute("aria-selected",`${this.checked}`),e.has("disabled")&&this.setAttribute("aria-disabled",`${this.disabled}`)}render(){return E`
      <div class="choice-field__label">
        <slot></slot>
      </div>
    `}connectedCallback(){super.connectedCallback(),this.setAttribute("role","option")}__registerEventListeners(){this.addEventListener("click",this.__onClick)}__unRegisterEventListeners(){this.removeEventListener("click",this.__onClick)}__onClick(){if(this.disabled)return;const e=this._parentFormGroup;this._isHandlingUserInput=!0,e&&e.multipleChoice?(this.checked=!this.checked,this.active=!this.active):(this.checked=!0,this.active=!0),this._isHandlingUserInput=!1}},Ku=class extends xi{static get styles(){return[...xi.styles,qu]}};customElements.get("craft-option")||customElements.define("craft-option",Ku);let Gu=class extends lr{static get properties(){return{autocomplete:{type:String}}}constructor(){super(),this.autocomplete=void 0}get _inputNode(){return Array.from(this.children).find(e=>e.slot==="input")}},Ju=class extends Gu{get operationMode(){return"select"}connectedCallback(){super.connectedCallback(),this._inputNode.addEventListener("change",this._proxyChangeEvent),this.__selectObserver=new MutationObserver(()=>{this._syncValueUpwards(),this._calculateValues({source:"model"})}),this.__selectObserver.observe(this._inputNode,{attributes:!0,childList:!0,subtree:!0})}updated(e){super.updated(e),e.has("disabled")&&(this._inputNode.disabled=this.disabled,this.validate()),e.has("name")&&(this._inputNode.name=this.name),e.has("autocomplete")&&(this._inputNode.autocomplete=this.autocomplete)}disconnectedCallback(){super.disconnectedCallback(),this._inputNode.removeEventListener("change",this._proxyChangeEvent),this.__selectObserver?.disconnect()}formatter(e){const t=Array.from(this._inputNode.options).find(r=>r.value===e);return t?t.text:""}_reflectBackFormattedValueToUser(){this._reflectBackOn()&&(this.value=typeof this.modelValue<"u"?this.modelValue:"")}_proxyChangeEvent(){this.dispatchEvent(new CustomEvent("user-input-changed",{bubbles:!0,composed:!0}))}},Yu=class extends Ju{static get styles(){return[...super.styles,ju]}_inputGroupInputTemplate(){return E`
      <div class="input-group__input">
        <slot name="input"></slot>
        <craft-icon
          class="indicator"
          name="chevron-down"
          style="font-size: 0.8em"
        ></craft-icon>
      </div>
    `}};customElements.get("craft-select")||customElements.define("craft-select",Yu);var ba=`@layer wa-utilities {
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
`,cr=class{constructor(e,...t){this.slotNames=[],this.handleSlotChange=r=>{const o=r.target;(this.slotNames.includes("[default]")&&!o.name||o.name&&this.slotNames.includes(o.name))&&this.host.requestUpdate()},(this.host=e).addController(this),this.slotNames=t}hasDefaultSlot(){return[...this.host.childNodes].some(e=>{if(e.nodeType===Node.TEXT_NODE&&e.textContent.trim()!=="")return!0;if(e.nodeType===Node.ELEMENT_NODE){const t=e;if(t.tagName.toLowerCase()==="wa-visually-hidden")return!1;if(!t.hasAttribute("slot"))return!0}return!1})}hasNamedSlot(e){return this.host.querySelector(`:scope > [slot="${e}"]`)!==null}test(e){return e==="[default]"?this.hasDefaultSlot():this.hasNamedSlot(e)}hostConnected(){this.host.shadowRoot.addEventListener("slotchange",this.handleSlotChange)}hostDisconnected(){this.host.shadowRoot.removeEventListener("slotchange",this.handleSlotChange)}},Xu=class extends Event{constructor(e){super("wa-select",{bubbles:!0,cancelable:!0,composed:!0}),this.detail=e}};function*va(s=document.activeElement){s!=null&&(yield s,"shadowRoot"in s&&s.shadowRoot&&s.shadowRoot.mode!=="closed"&&(yield*va(s.shadowRoot.activeElement)))}var Zu=`:host {
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
`,Nr=new Set,ue=class extends ge{constructor(){super(...arguments),this.submenuCleanups=new Map,this.localize=new Nt(this),this.userTypedQuery="",this.openSubmenuStack=[],this.open=!1,this.size="medium",this.placement="bottom-start",this.distance=0,this.skidding=0,this.handleDocumentKeyDown=async e=>{const t=this.localize.dir()==="rtl";if(e.key==="Escape"){const h=this.getTrigger();e.preventDefault(),e.stopPropagation(),this.open=!1,h?.focus();return}const r=[...va()].find(h=>h.localName==="wa-dropdown-item"),o=r?.localName==="wa-dropdown-item",i=this.getCurrentSubmenuItem(),n=!!i;let a,l,d;n?(a=this.getSubmenuItems(i),l=a.find(h=>h.active||h===r),d=l?a.indexOf(l):-1):(a=this.getItems(),l=a.find(h=>h.active||h===r),d=l?a.indexOf(l):-1);let c;if(e.key==="ArrowUp"&&(e.preventDefault(),e.stopPropagation(),d>0?c=a[d-1]:c=a[a.length-1]),e.key==="ArrowDown"&&(e.preventDefault(),e.stopPropagation(),d!==-1&&d<a.length-1?c=a[d+1]:c=a[0]),e.key===(t?"ArrowLeft":"ArrowRight")&&o&&l&&l.hasSubmenu){e.preventDefault(),e.stopPropagation(),l.submenuOpen=!0,this.addToSubmenuStack(l),setTimeout(()=>{const h=this.getSubmenuItems(l);h.length>0&&(h.forEach((g,f)=>g.active=f===0),h[0].focus())},0);return}if(e.key===(t?"ArrowRight":"ArrowLeft")&&n){e.preventDefault(),e.stopPropagation();const h=this.removeFromSubmenuStack();h&&(h.submenuOpen=!1,setTimeout(()=>{h.focus(),h.active=!0,(h.slot==="submenu"?this.getSubmenuItems(h.parentElement):this.getItems()).forEach(g=>{g!==h&&(g.active=!1)})},0));return}if((e.key==="Home"||e.key==="End")&&(e.preventDefault(),e.stopPropagation(),c=e.key==="Home"?a[0]:a[a.length-1]),e.key==="Tab"&&await this.hideMenu(),e.key.length===1&&!(e.metaKey||e.ctrlKey||e.altKey)&&!(e.key===" "&&this.userTypedQuery==="")&&(clearTimeout(this.userTypedTimeout),this.userTypedTimeout=setTimeout(()=>{this.userTypedQuery=""},1e3),this.userTypedQuery+=e.key,a.some(h=>{const g=(h.textContent||"").trim().toLowerCase(),f=this.userTypedQuery.trim().toLowerCase();return g.startsWith(f)?(c=h,!0):!1})),c){e.preventDefault(),e.stopPropagation(),a.forEach(h=>h.active=h===c),c.focus();return}(e.key==="Enter"||e.key===" "&&this.userTypedQuery==="")&&o&&l&&(e.preventDefault(),e.stopPropagation(),l.hasSubmenu?(l.submenuOpen=!0,this.addToSubmenuStack(l),setTimeout(()=>{const h=this.getSubmenuItems(l);h.length>0&&(h.forEach((g,f)=>g.active=f===0),h[0].focus())},0)):this.makeSelection(l))},this.handleDocumentPointerDown=e=>{e.composedPath().some(t=>t instanceof HTMLElement?t===this||t.closest('wa-dropdown, [part="submenu"]'):!1)||(this.open=!1)},this.handleGlobalMouseMove=e=>{const t=this.getCurrentSubmenuItem();if(!t?.submenuOpen||!t.submenuElement)return;const r=t.submenuElement.getBoundingClientRect(),o=this.localize.dir()==="rtl",i=o?r.right:r.left,n=o?Math.max(e.clientX,i):Math.min(e.clientX,i),a=Math.max(r.top,Math.min(e.clientY,r.bottom));t.submenuElement.style.setProperty("--safe-triangle-cursor-x",`${n}px`),t.submenuElement.style.setProperty("--safe-triangle-cursor-y",`${a}px`);const l=t.matches(":hover"),d=t.submenuElement?.matches(":hover")||!!e.composedPath().find(c=>c instanceof HTMLElement&&c.closest('[part="submenu"]')===t.submenuElement);!l&&!d&&setTimeout(()=>{!t.matches(":hover")&&!t.submenuElement?.matches(":hover")&&(t.submenuOpen=!1)},100)}}disconnectedCallback(){super.disconnectedCallback(),clearInterval(this.userTypedTimeout),this.closeAllSubmenus(),this.submenuCleanups.forEach(e=>e()),this.submenuCleanups.clear(),document.removeEventListener("mousemove",this.handleGlobalMouseMove)}firstUpdated(){this.syncAriaAttributes()}async updated(e){e.has("open")&&(this.customStates.set("open",this.open),this.open?await this.showMenu():(this.closeAllSubmenus(),await this.hideMenu())),e.has("size")&&this.syncItemSizes()}getItems(e=!1){const t=this.defaultSlot.assignedElements({flatten:!0}).filter(r=>r.localName==="wa-dropdown-item");return e?t:t.filter(r=>!r.disabled)}getSubmenuItems(e,t=!1){const r=e.shadowRoot?.querySelector('slot[name="submenu"]')||e.querySelector('slot[name="submenu"]');if(!r)return[];const o=r.assignedElements({flatten:!0}).filter(i=>i.localName==="wa-dropdown-item");return t?o:o.filter(i=>!i.disabled)}syncItemSizes(){this.defaultSlot.assignedElements({flatten:!0}).filter(e=>e.localName==="wa-dropdown-item").forEach(e=>e.size=this.size)}addToSubmenuStack(e){const t=this.openSubmenuStack.indexOf(e);t!==-1?this.openSubmenuStack=this.openSubmenuStack.slice(0,t+1):this.openSubmenuStack.push(e)}removeFromSubmenuStack(){return this.openSubmenuStack.pop()}getCurrentSubmenuItem(){return this.openSubmenuStack.length>0?this.openSubmenuStack[this.openSubmenuStack.length-1]:void 0}closeAllSubmenus(){this.getItems(!0).forEach(e=>{e.submenuOpen=!1}),this.openSubmenuStack=[]}closeSiblingSubmenus(e){const t=e.closest('wa-dropdown-item:not([slot="submenu"])');let r;t?r=this.getSubmenuItems(t,!0):r=this.getItems(!0),r.forEach(o=>{o!==e&&o.submenuOpen&&(o.submenuOpen=!1)}),this.openSubmenuStack.includes(e)||this.openSubmenuStack.push(e)}getTrigger(){return this.querySelector('[slot="trigger"]')}async showMenu(){if(!this.getTrigger())return;const e=new cs;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}Nr.forEach(r=>r.open=!1),this.popup.active=!0,this.open=!0,Nr.add(this),this.syncAriaAttributes(),document.addEventListener("keydown",this.handleDocumentKeyDown),document.addEventListener("pointerdown",this.handleDocumentPointerDown),document.addEventListener("mousemove",this.handleGlobalMouseMove),this.menu.classList.remove("hide"),await de(this.menu,"show");const t=this.getItems();t.length>0&&(t.forEach((r,o)=>r.active=o===0),t[0].focus()),this.dispatchEvent(new as)}async hideMenu(){const e=new ls({source:this});if(this.dispatchEvent(e),e.defaultPrevented){this.open=!0;return}this.open=!1,Nr.delete(this),this.syncAriaAttributes(),document.removeEventListener("keydown",this.handleDocumentKeyDown),document.removeEventListener("pointerdown",this.handleDocumentPointerDown),document.removeEventListener("mousemove",this.handleGlobalMouseMove),this.menu.classList.remove("show"),await de(this.menu,"hide"),this.popup.active=this.open,this.dispatchEvent(new ns)}handleMenuClick(e){const t=e.target.closest("wa-dropdown-item");if(!(!t||t.disabled)){if(t.hasSubmenu){t.submenuOpen||(this.closeSiblingSubmenus(t),this.addToSubmenuStack(t),t.submenuOpen=!0),e.stopPropagation();return}this.makeSelection(t)}}async handleMenuSlotChange(){const e=this.getItems(!0);await Promise.all(e.map(o=>o.updateComplete)),this.syncItemSizes();const t=e.some(o=>o.type==="checkbox"),r=e.some(o=>o.hasSubmenu);e.forEach((o,i)=>{o.active=i===0,o.checkboxAdjacent=t,o.submenuAdjacent=r})}handleTriggerClick(){this.open=!this.open}handleSubmenuOpening(e){const t=e.detail.item;this.closeSiblingSubmenus(t),this.addToSubmenuStack(t),this.setupSubmenuPosition(t),this.processSubmenuItems(t)}setupSubmenuPosition(e){if(!e.submenuElement)return;this.cleanupSubmenuPosition(e);const t=Mn(e,e.submenuElement,()=>{this.positionSubmenu(e),this.updateSafeTriangleCoordinates(e)});this.submenuCleanups.set(e,t);const r=e.submenuElement.querySelector('slot[name="submenu"]');r&&(r.removeEventListener("slotchange",ue.handleSubmenuSlotChange),r.addEventListener("slotchange",ue.handleSubmenuSlotChange),ue.handleSubmenuSlotChange({target:r}))}static handleSubmenuSlotChange(e){const t=e.target;if(!t)return;const r=t.assignedElements().filter(n=>n.localName==="wa-dropdown-item");if(r.length===0)return;const o=r.some(n=>n.hasSubmenu),i=r.some(n=>n.type==="checkbox");r.forEach(n=>{n.submenuAdjacent=o,n.checkboxAdjacent=i})}processSubmenuItems(e){if(!e.submenuElement)return;const t=this.getSubmenuItems(e,!0),r=t.some(o=>o.hasSubmenu);t.forEach(o=>{o.submenuAdjacent=r})}cleanupSubmenuPosition(e){const t=this.submenuCleanups.get(e);t&&(t(),this.submenuCleanups.delete(e))}positionSubmenu(e){if(!e.submenuElement)return;const t=this.localize.dir()==="rtl"?"left-start":"right-start";In(e,e.submenuElement,{placement:t,middleware:[Dn({mainAxis:0,crossAxis:-5}),zn({fallbackStrategy:"bestFit"}),Vn({padding:8})]}).then(({x:r,y:o,placement:i})=>{e.submenuElement.setAttribute("data-placement",i),Object.assign(e.submenuElement.style,{left:`${r}px`,top:`${o}px`})})}updateSafeTriangleCoordinates(e){if(!e.submenuElement||!e.submenuOpen)return;if(document.activeElement?.matches(":focus-visible")){e.submenuElement.style.setProperty("--safe-triangle-visible","none");return}e.submenuElement.style.setProperty("--safe-triangle-visible","block");const t=e.submenuElement.getBoundingClientRect(),r=this.localize.dir()==="rtl";e.submenuElement.style.setProperty("--safe-triangle-submenu-start-x",`${r?t.right:t.left}px`),e.submenuElement.style.setProperty("--safe-triangle-submenu-start-y",`${t.top}px`),e.submenuElement.style.setProperty("--safe-triangle-submenu-end-x",`${r?t.right:t.left}px`),e.submenuElement.style.setProperty("--safe-triangle-submenu-end-y",`${t.bottom}px`)}makeSelection(e){const t=this.getTrigger();if(e.disabled)return;e.type==="checkbox"&&(e.checked=!e.checked);const r=new Xu({item:e});this.dispatchEvent(r),r.defaultPrevented||(this.open=!1,t?.focus())}async syncAriaAttributes(){const e=this.getTrigger();let t;e&&(e.localName==="wa-button"?(await customElements.whenDefined("wa-button"),await e.updateComplete,t=e.shadowRoot.querySelector('[part="base"]')):t=e,t.hasAttribute("id")||t.setAttribute("id",Ao("wa-dropdown-trigger-")),t.setAttribute("aria-haspopup","menu"),t.setAttribute("aria-expanded",this.open?"true":"false"),this.menu.setAttribute("aria-expanded","false"))}render(){let e=this.hasUpdated?this.popup.active:this.open;return E`
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
    `}};ue.css=[ba,Zu];v([ee("slot:not([name])")],ue.prototype,"defaultSlot",2);v([ee("#menu")],ue.prototype,"menu",2);v([ee("wa-popup")],ue.prototype,"popup",2);v([w({type:Boolean,reflect:!0})],ue.prototype,"open",2);v([w({reflect:!0})],ue.prototype,"size",2);v([w({reflect:!0})],ue.prototype,"placement",2);v([w({type:Number})],ue.prototype,"distance",2);v([w({type:Number})],ue.prototype,"skidding",2);ue=v([Ae("wa-dropdown")],ue);var Qu=`:host {
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
`,le=class extends ge{constructor(){super(...arguments),this.hasSlotController=new cr(this,"[default]","start","end"),this.active=!1,this.variant="default",this.size="medium",this.checkboxAdjacent=!1,this.submenuAdjacent=!1,this.type="normal",this.checked=!1,this.disabled=!1,this.submenuOpen=!1,this.hasSubmenu=!1,this.handleSlotChange=()=>{this.hasSubmenu=this.hasSlotController.test("submenu"),this.updateHasSubmenuState(),this.hasSubmenu?(this.setAttribute("aria-haspopup","menu"),this.setAttribute("aria-expanded",this.submenuOpen?"true":"false")):(this.removeAttribute("aria-haspopup"),this.removeAttribute("aria-expanded"))}}connectedCallback(){super.connectedCallback(),this.addEventListener("mouseenter",this.handleMouseEnter.bind(this)),this.shadowRoot.addEventListener("slotchange",this.handleSlotChange)}disconnectedCallback(){super.disconnectedCallback(),this.closeSubmenu(),this.removeEventListener("mouseenter",this.handleMouseEnter),this.shadowRoot.removeEventListener("slotchange",this.handleSlotChange)}firstUpdated(){this.setAttribute("tabindex","-1"),this.hasSubmenu=this.hasSlotController.test("submenu"),this.updateHasSubmenuState()}updated(e){e.has("active")&&(this.setAttribute("tabindex",this.active?"0":"-1"),this.customStates.set("active",this.active)),e.has("checked")&&(this.setAttribute("aria-checked",this.checked?"true":"false"),this.customStates.set("checked",this.checked)),e.has("disabled")&&(this.setAttribute("aria-disabled",this.disabled?"true":"false"),this.customStates.set("disabled",this.disabled)),e.has("type")&&(this.type==="checkbox"?this.setAttribute("role","menuitemcheckbox"):this.setAttribute("role","menuitem")),e.has("submenuOpen")&&(this.customStates.set("submenu-open",this.submenuOpen),this.submenuOpen?this.openSubmenu():this.closeSubmenu())}updateHasSubmenuState(){this.customStates.set("has-submenu",this.hasSubmenu)}async openSubmenu(){!this.hasSubmenu||!this.submenuElement||(this.notifyParentOfOpening(),this.submenuElement.showPopover(),this.submenuElement.hidden=!1,this.submenuElement.setAttribute("data-visible",""),this.submenuOpen=!0,this.setAttribute("aria-expanded","true"),await de(this.submenuElement,"show"),setTimeout(()=>{const e=this.getSubmenuItems();e.length>0&&(e.forEach((t,r)=>t.active=r===0),e[0].focus())},0))}notifyParentOfOpening(){const e=new CustomEvent("submenu-opening",{bubbles:!0,composed:!0,detail:{item:this}});this.dispatchEvent(e);const t=this.parentElement;t&&[...t.children].filter(r=>r!==this&&r.localName==="wa-dropdown-item"&&r.getAttribute("slot")===this.getAttribute("slot")&&r.submenuOpen).forEach(r=>{r.submenuOpen=!1})}async closeSubmenu(){!this.hasSubmenu||!this.submenuElement||(this.submenuOpen=!1,this.setAttribute("aria-expanded","false"),this.submenuElement.hidden||(await de(this.submenuElement,"hide"),this.submenuElement.hidden=!0,this.submenuElement.removeAttribute("data-visible"),this.submenuElement.hidePopover()))}getSubmenuItems(){return[...this.children].filter(e=>e.localName==="wa-dropdown-item"&&e.getAttribute("slot")==="submenu"&&!e.hasAttribute("disabled"))}handleMouseEnter(){this.hasSubmenu&&!this.disabled&&(this.notifyParentOfOpening(),this.submenuOpen=!0)}render(){return E`
      ${this.type==="checkbox"?E`
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

      ${this.hasSubmenu?E`
            <wa-icon
              id="submenu-indicator"
              part="submenu-icon"
              exportparts="svg:submenu-icon__svg"
              library="system"
              name="chevron-right"
            ></wa-icon>
          `:""}
      ${this.hasSubmenu?E`
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
    `}};le.css=Qu;v([ee("#submenu")],le.prototype,"submenuElement",2);v([w({type:Boolean})],le.prototype,"active",2);v([w({reflect:!0})],le.prototype,"variant",2);v([w({reflect:!0})],le.prototype,"size",2);v([w({attribute:"checkbox-adjacent",type:Boolean,reflect:!0})],le.prototype,"checkboxAdjacent",2);v([w({attribute:"submenu-adjacent",type:Boolean,reflect:!0})],le.prototype,"submenuAdjacent",2);v([w()],le.prototype,"value",2);v([w({reflect:!0})],le.prototype,"type",2);v([w({type:Boolean})],le.prototype,"checked",2);v([w({type:Boolean,reflect:!0})],le.prototype,"disabled",2);v([w({type:Boolean,reflect:!0})],le.prototype,"submenuOpen",2);v([be()],le.prototype,"hasSubmenu",2);le=v([Ae("wa-dropdown-item")],le);let eh=class extends ue{static get styles(){return[ue.styles,P`
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
      `]}},th=class extends le{static get styles(){return[le.styles,P`
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
      `]}};customElements.get("craft-dropdown")||customElements.define("craft-dropdown",eh);customElements.get("craft-dropdown-item")||customElements.define("craft-dropdown-item",th);const sh=P`
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
`;function rh({el:s,uid:e}){s.setAttribute("id",`panel-${e}`),s.setAttribute("role","tabpanel"),s.setAttribute("aria-labelledby",`button-${e}`),s.hasAttribute("tabindex")||s.setAttribute("tabindex","0")}function oh(s){s.setAttribute("selected","true")}function ki(s){s.removeAttribute("selected")}function ih({el:s,uid:e,clickHandler:t,keydownHandler:r,keyupHandler:o}){s.setAttribute("id",`button-${e}`),s.setAttribute("role","tab"),s.setAttribute("aria-controls",`panel-${e}`),s.addEventListener("click",t),s.addEventListener("keyup",o),s.addEventListener("keydown",r)}function nh({el:s,clickHandler:e,keydownHandler:t,keyupHandler:r}){s.removeAttribute("id"),s.removeAttribute("role"),s.removeAttribute("aria-controls"),s.removeEventListener("click",e),s.removeEventListener("keyup",r),s.removeEventListener("keydown",t)}function ah(s,e=!1){e&&s.focus(),s.setAttribute("selected","true"),s.setAttribute("aria-selected","true"),s.setAttribute("tabindex","0")}function Ci(s){s.removeAttribute("selected"),s.setAttribute("aria-selected","false"),s.setAttribute("tabindex","-1")}function lh(s){const e=s;switch(e.key){case"ArrowDown":case"ArrowRight":case"ArrowUp":case"ArrowLeft":case"Home":case"End":e.preventDefault()}}let ch=class extends Y{static get properties(){return{selectedIndex:{type:Number,attribute:"selected-index",reflect:!0}}}static get styles(){return[P`
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
      `]}render(){return E`
      <div class="tabs__tab-group" role="tablist">
        <slot name="tab"></slot>
      </div>
      <div class="tabs__panels">
        <slot name="panel"></slot>
      </div>
    `}constructor(){super(),this.selectedIndex=0}firstUpdated(e){super.firstUpdated(e),this.__setupSlots(),this.tabs[0]?.disabled&&(this.selectedIndex=this.tabs.findIndex(t=>!t.disabled))}get tabs(){return Array.from(this.children).filter(e=>e.slot==="tab")}get panels(){return Array.from(this.children).filter(e=>e.slot==="panel")}static enabledWarnings=super.enabledWarnings?.filter(e=>e!=="change-in-update")||[];__setupSlots(){if(this.shadowRoot){const e=this.shadowRoot.querySelector("slot[name=tab]"),t=()=>{this.__cleanStore(),this.__setupStore(),this.__updateSelected(!1)};e&&e.addEventListener("slotchange",t)}}__setupStore(){this.__store=[],this.tabs.length!==this.panels.length&&console.warn(`The amount of tabs (${this.tabs.length}) doesn't match the amount of panels (${this.panels.length}).`),this.tabs.forEach((e,t)=>{const r=ta(),o=this.panels[t],i={uid:r,el:e,button:e,panel:o,clickHandler:this.__createButtonClickHandler(t),keydownHandler:lh.bind(this),keyupHandler:this.__handleButtonKeyup.bind(this)};rh({...i,el:i.panel}),ih(i),ki(i.panel),Ci(i.button),this.__store&&this.__store.push(i)})}__cleanStore(){this.__store&&(this.__store.forEach(e=>{nh(e)}),this.__store=[])}__getNextNotDisabledTab(e,t,r){let o=[];const i=e.filter((a,l)=>!a.disabled&&l>this.selectedIndex),n=e.filter((a,l)=>!a.disabled&&l<this.selectedIndex);return r==="right"?o=[...i,...n]:o=[...n.reverse(),...i.reverse()],o[0]}__getNextAvailableIndex(e,t){const r=this.tabs[this.selectedIndex];if(this.tabs.every(o=>!o.disabled))return e;if(t==="ArrowRight"||t==="ArrowDown"){const o=this.__getNextNotDisabledTab(this.tabs,r,"right");return this.tabs.findIndex(i=>o===i)}if(t==="ArrowLeft"||t==="ArrowUp"){const o=this.__getNextNotDisabledTab(this.tabs,r,"left");return this.tabs.findIndex(i=>o===i)}if(t==="Home")return this.tabs.findIndex(o=>!o.disabled);if(t==="End"){const o=this.tabs.map((i,n)=>({disabled:i.disabled,index:n})).filter(i=>!i.disabled);return o[o.length-1].index}return-1}__createButtonClickHandler(e){return()=>{this._setSelectedIndexWithFocus(e)}}__handleButtonKeyup(e){const t=e;if(typeof this.selectedIndex=="number")switch(t.key){case"ArrowDown":case"ArrowRight":this.selectedIndex+1>=this._pairCount?this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(0,t.key)):this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this.selectedIndex+1,t.key));break;case"ArrowUp":case"ArrowLeft":this.selectedIndex<=0?this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this._pairCount-1,t.key)):this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this.selectedIndex-1,t.key));break;case"Home":this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(0,t.key));break;case"End":this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this._pairCount-1,t.key));break}}get selectedIndex(){return this.__selectedIndex||0}set selectedIndex(e){if(e===this.__selectedIndex)return;const t=this.__selectedIndex;this.__selectedIndex=e,this.__updateSelected(!1),this.dispatchEvent(new Event("selected-changed")),this.requestUpdate("selectedIndex",t)}_setSelectedIndexWithFocus(e){if(e===-1)return;const t=this.__selectedIndex;this.__selectedIndex=e,this.__updateSelected(!0),this.dispatchEvent(new Event("selected-changed")),this.requestUpdate("selectedIndex",t)}get _pairCount(){return this.__store&&this.__store.length||0}__updateSelected(e=!1){if(!(this.__store&&typeof this.selectedIndex=="number"&&this.__store[this.selectedIndex]))return;const t=this.tabs.find(n=>n.hasAttribute("selected")),r=this.panels.find(n=>n.hasAttribute("selected"));t&&Ci(t),r&&ki(r);const{button:o,panel:i}=this.__store[this.selectedIndex];o&&ah(o,e),i&&oh(i)}},dh=class extends ch{static get styles(){return[...super.styles,sh]}};customElements.get("craft-tabs")||customElements.define("craft-tabs",dh);const uh=P`
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
`;var hh=Object.defineProperty,ph=(s,e,t,r)=>{for(var o=void 0,i=s.length-1,n;i>=0;i--)(n=s[i])&&(o=n(e,t,o)||o);return o&&hh(e,t,o),o};const ya=class extends Y{constructor(){super(...arguments),this.label=""}render(){const e=!!this.label||!!this.querySelector('[slot="header"]')||!!this.querySelector('[slot="label"]')||!!this.querySelector('[slot="actions"]'),t=!!this.querySelector('[slot="footer"]');return E`
      <div class="card">
        <div>
          ${e?E`<div class="card__header">
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

          ${t?E`<div class="card__footer"><slot name="footer"></slot></div>`:V}
        </div>
      </div>
    `}};ya.styles=[uh];let wa=ya;ph([w()],wa.prototype,"label");customElements.get("craft-card")||customElements.define("craft-card",wa);const mh=P`
  :host {
    display: inline-flex;
  }
`,_a=class extends Y{render(){return E`<slot></slot> `}};_a.styles=[mh];let fh=_a;customElements.get("craft-tab")||customElements.define("craft-tab",fh);let $a=class extends Jn(Y){static get properties(){return{checked:{type:Boolean,reflect:!0}}}static get styles(){return[P`
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
      `]}render(){return E`
      <div class="btn">
        <div class="switch-button__track"></div>
        <div class="switch-button__thumb"></div>
      </div>
    `}constructor(){super(),this.value="",this.checked=!1,this.__initialized=!1,this._toggleChecked=this._toggleChecked.bind(this),this.__handleKeydown=this._handleKeydown.bind(this),this.__handleKeyup=this._handleKeyup.bind(this)}connectedCallback(){super.connectedCallback(),this.setAttribute("role","switch"),this.setAttribute("aria-checked",`${this.checked}`),this.addEventListener("click",this._toggleChecked),this.addEventListener("keydown",this.__handleKeydown),this.addEventListener("keyup",this.__handleKeyup)}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener("click",this._toggleChecked),this.removeEventListener("keydown",this.__handleKeydown),this.removeEventListener("keyup",this.__handleKeyup)}_toggleChecked(){this.disabled||(this.focus(),this.checked=!this.checked)}__checkedStateChange(){this.dispatchEvent(new Event("checked-changed",{bubbles:!0})),this.setAttribute("aria-checked",`${this.checked}`)}_handleKeydown(e){e.key===" "&&e.preventDefault()}_handleKeyup(e){[" ","Enter"].includes(e.key)&&this._toggleChecked()}updated(e){super.updated(e),e.has("disabled")&&this.setAttribute("aria-disabled",`${this.disabled}`)}requestUpdate(e,t,r){super.requestUpdate(e,t,r),this.__initialized&&this.isConnected&&e==="checked"&&this.checked!==t&&!this.disabled&&this.__checkedStateChange()}firstUpdated(e){super.firstUpdated(e),this.__initialized=!0}},Ea=class extends $a{static get styles(){return[...super.styles,P`
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
      `]}};customElements.get("craft-switch-button")||customElements.define("craft-switch-button",Ea);const gh=P`
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
`;let bh=class extends ia(Do(lr)){static get styles(){return[...super.styles,P`
        :host([hidden]) {
          display: none;
        }

        :host([disabled]) {
          color: #adadad;
        }
      `]}static get scopedElements(){return{...super.scopedElements,"lion-switch-button":$a}}get _inputNode(){return Array.from(this.children).find(e=>e.slot==="input")}get slots(){return{...super.slots,input:()=>{const e=this.createScopedElement("lion-switch-button");return e.setAttribute("data-tag-name","lion-switch-button"),e}}}render(){return E`
      <div class="form-field__group-one">${this._groupOneTemplate()}</div>
      <div class="form-field__group-two">${this._groupTwoTemplate()}</div>
    `}_groupOneTemplate(){return E`${this._labelTemplate()} ${this._helpTextTemplate()} ${this._feedbackTemplate()}`}_groupTwoTemplate(){return E`${this._inputGroupTemplate()}`}constructor(){super(),this.checked=!1,this.__handleButtonSwitchCheckedChanged=this.__handleButtonSwitchCheckedChanged.bind(this)}connectedCallback(){super.connectedCallback(),this.addEventListener("checked-changed",this.__handleButtonSwitchCheckedChanged),this._labelNode&&this._labelNode.addEventListener("click",this._toggleChecked),this._syncButtonSwitch()}disconnectedCallback(){super.disconnectedCallback(),this._inputNode&&this.removeEventListener("checked-changed",this.__handleButtonSwitchCheckedChanged),this._labelNode&&this._labelNode.removeEventListener("click",this._toggleChecked)}updated(e){super.updated(e),e.has("disabled")&&this._syncButtonSwitch()}_toggleChecked(e){e.preventDefault(),super._toggleChecked(e)}_isEmpty(){return!1}__handleButtonSwitchCheckedChanged(e){e.stopPropagation(),this._isHandlingUserInput=!0,this.checked=this._inputNode.checked,this._isHandlingUserInput=!1}_syncButtonSwitch(){this._inputNode.disabled=this.disabled}_onLabelClick(){this.disabled||this._inputNode.focus()}},vh=class extends bh{static get styles(){return[...super.styles,gh]}get slots(){return{...super.slots,input:()=>{const e=this.createScopedElement("craft-switch-button");return e.setAttribute("data-tag-name","craft-switch-button"),e}}}static get scopedElements(){return{...super.scopedElements,"craft-switch-button":Ea}}};customElements.get("craft-switch")||customElements.define("craft-switch",vh);function yh(s){return(e,t)=>{const{slot:r,selector:o}=s??{},i="slot"+(r?`[name=${r}]`:":not([name])");return kn(e,t,{get(){const n=this.renderRoot?.querySelector(i),a=n?.assignedElements(s)??[];return o===void 0?a:a.filter((l=>l.matches(o)))}})}}const wh=".breadcrumbs{display:flex;align-items:center}";var _h=Object.defineProperty,Dt=(s,e,t,r)=>{for(var o=void 0,i=s.length-1,n;i>=0;i--)(n=s[i])&&(o=n(e,t,o)||o);return o&&_h(e,t,o),o};const xa=new CSSStyleSheet;xa.replaceSync(wh);const ka=class extends Y{constructor(){super(...arguments),this.label="",this.items=[],this.visibleItems=0,this.firstRender=!0}getSeparator(){const e=this.separatorSlot.assignedElements({flatten:!0})[0].cloneNode(!0);return[e,...e.querySelectorAll("[id]")].forEach(t=>t.removeAttribute("id")),e.setAttribute("data-default",""),e.slot="separator",e}calculateBreadcrumbItemsWidth(){this.items=this.breadcrumbsElements.map((e,t)=>{let r=e.offsetWidth;return e.hasAttribute("hidden")&&(e.removeAttribute("hidden"),r=e.offsetWidth,e.setAttribute("hidden","")),{label:e.innerText,href:e.href,value:t.toString(),offsetWidth:r,isVisible:!0}})}async handleSlotChange(){const e=[...this.defaultSlot.assignedElements({flatten:!0})].filter(t=>t.tagName.toLowerCase()==="craft-breadcrumb-item");if(e.forEach((t,r)=>{const o=t.querySelector('[slot="separator"]');o===null?t.append(this.getSeparator()):o.hasAttribute("data-default")&&o.replaceWith(this.getSeparator()),r===e.length-1?t.setAttribute("aria-current","page"):t.removeAttribute("aria-current")}),this.breadcrumbsElements.length===0){this.items=[],this.visibleItems=0;return}await Promise.all(this.breadcrumbsElements.map(t=>t.updateComplete)),this.calculateBreadcrumbItemsWidth(),this.visibleItems=0,this.adjustOverflow()}connectedCallback(){super.connectedCallback(),this.hasAttribute("role")||this.setAttribute("role","navigation"),this.resizeObserver=new ResizeObserver(()=>{if(this.firstRender){this.firstRender=!1;return}this.adjustOverflow()}),this.resizeObserver.observe(this)}adjustOverflow(){const e=this.getBoundingClientRect().width;console.log({availableSpace:e})}disconnectedCallback(){this.resizeObserver?.unobserve(this),super.disconnectedCallback()}render(){return E`
      <nav class="breadcrumbs" aria-label="${this.label}">
        <slot @slotchange="${this.handleSlotChange}"></slot>
      </nav>

      <span hidden aria-hidden="true">
        <slot name="separator"><span class="separator">/</span></slot>
      </span>
    `}};ka.styles=[xa];let gt=ka;Dt([ee("slot")],gt.prototype,"defaultSlot");Dt([ee('slot[name="separator"]')],gt.prototype,"separatorSlot");Dt([yh({selector:"craft-breadcrumb-item"})],gt.prototype,"breadcrumbsElements");Dt([w()],gt.prototype,"label");Dt([be()],gt.prototype,"items");Dt([be()],gt.prototype,"visibleItems");customElements.get("craft-breadcrumbs")||customElements.define("craft-breadcrumbs",gt);const Ee=s=>s??V;var $h=`:host {
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
`,Ce=class extends ge{constructor(){super(...arguments),this.renderType="button",this.rel="noreferrer noopener"}setRenderType(){const e=this.defaultSlot.assignedElements({flatten:!0}).filter(t=>t.tagName.toLowerCase()==="wa-dropdown").length>0;if(this.href){this.renderType="link";return}if(e){this.renderType="dropdown";return}this.renderType="button"}hrefChanged(){this.setRenderType()}handleSlotChange(){this.setRenderType()}render(){return E`
      <span part="start" class="start">
        <slot name="start"></slot>
      </span>

      ${this.renderType==="link"?E`
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
      ${this.renderType==="button"?E`
            <button part="label" type="button" class="label label-button">
              <slot @slotchange=${this.handleSlotChange}></slot>
            </button>
          `:""}
      ${this.renderType==="dropdown"?E`
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
    `}};Ce.css=$h;v([ee("slot:not([name])")],Ce.prototype,"defaultSlot",2);v([be()],Ce.prototype,"renderType",2);v([w()],Ce.prototype,"href",2);v([w()],Ce.prototype,"target",2);v([w()],Ce.prototype,"rel",2);v([_e("href",{waitUntilFirstUpdate:!0})],Ce.prototype,"hrefChanged",1);Ce=v([Ae("wa-breadcrumb-item")],Ce);let Eh=class extends Ce{static get styles(){return[Ce.styles,P`
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
      `]}};customElements.get("craft-breadcrumb-item")||customElements.define("craft-breadcrumb-item",Eh);var xh=`:host {
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
`,Rr=new Set,oe=class extends ge{constructor(){super(...arguments),this.anchor=null,this.placement="top",this.open=!1,this.distance=8,this.skidding=0,this.for=null,this.withoutArrow=!1,this.eventController=new AbortController,this.handleAnchorClick=()=>{this.open=!this.open},this.handleBodyClick=e=>{e.target.closest('[data-popover="close"]')&&(e.stopPropagation(),this.open=!1)},this.handleDocumentKeyDown=e=>{e.key==="Escape"&&(e.preventDefault(),this.open=!1,this.anchor&&typeof this.anchor.focus=="function"&&this.anchor.focus())},this.handleDocumentClick=e=>{const t=e.target;this.anchor&&e.composedPath().includes(this.anchor)||t.closest("wa-popover")!==this&&(this.open=!1)}}connectedCallback(){super.connectedCallback(),this.id||(this.id=Ao("wa-popover-"))}disconnectedCallback(){super.disconnectedCallback(),document.removeEventListener("keydown",this.handleDocumentKeyDown),this.eventController.abort()}firstUpdated(){this.open&&(this.dialog.show(),this.popup.active=!0,this.popup.reposition())}updated(e){e.has("open")&&this.customStates.set("open",this.open)}async handleOpenChange(){if(this.open){const e=new cs;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}Rr.forEach(t=>t.open=!1),document.addEventListener("keydown",this.handleDocumentKeyDown,{signal:this.eventController.signal}),document.addEventListener("click",this.handleDocumentClick,{signal:this.eventController.signal}),this.dialog.show(),this.popup.active=!0,Rr.add(this),requestAnimationFrame(()=>{const t=this.querySelector("[autofocus]");t&&typeof t.focus=="function"?t.focus():this.dialog.focus()}),await de(this.popup.popup,"show-with-scale"),this.popup.reposition(),this.dispatchEvent(new as)}else{const e=new ls;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!0;return}document.removeEventListener("keydown",this.handleDocumentKeyDown),document.removeEventListener("click",this.handleDocumentClick),Rr.delete(this),await de(this.popup.popup,"hide-with-scale"),this.popup.active=!1,this.dialog.close(),this.dispatchEvent(new ns)}}handleForChange(){const e=this.getRootNode();if(!e)return;const t=this.for?e.getElementById(this.for):null,r=this.anchor;if(t===r)return;const{signal:o}=this.eventController;t&&t.addEventListener("click",this.handleAnchorClick,{signal:o}),r&&r.removeEventListener("click",this.handleAnchorClick),this.anchor=t,this.for&&!t&&console.warn(`A popover was assigned to an element with an ID of "${this.for}" but the element could not be found.`,this)}async handleOptionsChange(){this.hasUpdated&&(await this.updateComplete,this.popup.reposition())}async show(){if(!this.open)return this.open=!0,Bs(this,"wa-after-show")}async hide(){if(this.open)return this.open=!1,Bs(this,"wa-after-hide")}render(){return E`
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
    `}};oe.css=xh;oe.dependencies={"wa-popup":G};v([ee("dialog")],oe.prototype,"dialog",2);v([ee(".body")],oe.prototype,"body",2);v([ee("wa-popup")],oe.prototype,"popup",2);v([be()],oe.prototype,"anchor",2);v([w()],oe.prototype,"placement",2);v([w({type:Boolean,reflect:!0})],oe.prototype,"open",2);v([w({type:Number})],oe.prototype,"distance",2);v([w({type:Number})],oe.prototype,"skidding",2);v([w()],oe.prototype,"for",2);v([w({attribute:"without-arrow",type:Boolean,reflect:!0})],oe.prototype,"withoutArrow",2);v([_e("open",{waitUntilFirstUpdate:!0})],oe.prototype,"handleOpenChange",1);v([_e("for")],oe.prototype,"handleForChange",1);v([_e(["distance","placement","skidding"])],oe.prototype,"handleOptionsChange",1);oe=v([Ae("wa-popover")],oe);let kh=class extends oe{static get styles(){return[oe.styles,P`
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
      `]}};customElements.get("craft-popover")||customElements.define("craft-popover",kh);const Ca=class extends Y{render(){return E`
      <nav>
        <slot></slot>
      </nav>
    `}};Ca.styles=P`
    :host {
      display: block;
    }

    nav {
      display: grid;
    }
  `;let Ch=Ca;customElements.get("craft-navigation")||customElements.define("craft-navigation",Ch);const Sh=P`
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
`,Sa="important",Ah=" !"+Sa,Th=_o(class extends $o{constructor(s){if(super(s),s.type!==wo.ATTRIBUTE||s.name!=="style"||s.strings?.length>2)throw Error("The `styleMap` directive must be used in the `style` attribute and must be the only part in the attribute.")}render(s){return Object.keys(s).reduce(((e,t)=>{const r=s[t];return r==null?e:e+`${t=t.includes("-")?t:t.replace(/(?:^(webkit|moz|ms|o)|)(?=[A-Z])/g,"-$&").toLowerCase()}:${r};`}),"")}update(s,[e]){const{style:t}=s.element;if(this.ft===void 0)return this.ft=new Set(Object.keys(e)),this.render(e);for(const r of this.ft)e[r]==null&&(this.ft.delete(r),r.includes("-")?t.removeProperty(r):t[r]=null);for(const r in e){const o=e[r];if(o!=null){this.ft.add(r);const i=typeof o=="string"&&o.endsWith(Ah);r.includes("-")||i?t.setProperty(r,i?o.slice(0,-11):o,i?Sa:""):t[r]=o}}return Ne}});var Oh=Object.defineProperty,et=(s,e,t,r)=>{for(var o=void 0,i=s.length-1,n;i>=0;i--)(n=s[i])&&(o=n(e,t,o)||o);return o&&Oh(e,t,o),o};const Aa=class extends Y{constructor(){super(),this.active=!1,this.external=!1,this.indicator=!1,this.iconOnly=!1,this.subnavState="closed",this.id=this.id||Math.random().toString(36).substring(2,6)}connectedCallback(){super.connectedCallback(),this.subnavState=this.active?"open":"closed"}toggleSubnav(e){e.preventDefault(),e.stopPropagation(),this.subnavState=this.subnavState==="open"?"closed":"open"}renderIconItem(e){const t=`item-${this.id}`;return E`
      <a
        class="nav-item"
        id="${t}"
        href="${this.url}"
        aria-current="${this.active?"page":!1}"
      >
        <span class="nav-item__prefix">
          <slot name="prefix">
            <slot name="icon">
              ${this.icon?E` <craft-icon
                    name="${this.icon}"
                    class="nav-icon"
                  ></craft-icon>`:E` <span class="active-indicator"></span> `}
            </slot>
            ${this.indicator?E`<span class="indicator"></span>`:V}
          </slot>
        </span>

        <div class="nav-item__suffix">
          <slot name="suffix">
            ${e?E`
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
    `}renderItem(e){return E`
      <a
        class="nav-item"
        href="${this.url}"
        aria-current="${this.active?"page":!1}"
      >
        <span class="nav-item__prefix">
          <slot name="prefix">
            <slot name="icon">
              ${this.icon?E` <craft-icon
                    name="${this.icon}"
                    class="nav-icon"
                  ></craft-icon>`:E` <span class="active-indicator"></span> `}
            </slot>
            ${this.indicator?E`<span class="indicator"></span>`:V}
          </slot>
        </span>
        <slot></slot>

        <div class="nav-item__suffix">
          <slot name="suffix">
            ${e?E`
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
    `}render(){const e=!!this.querySelector('[slot="subnav"]');return E`
      ${this.iconOnly?this.renderIconItem(e):this.renderItem(e)}
      ${e?E`
            <div
              class="subnav"
              id="${this.id}-subnav"
              style="${Th({display:this.subnavState==="open"?"block":"none"})}"
            >
              <slot name="subnav"></slot>
            </div>
          `:V}
    `}};Aa.styles=Sh;let Ue=Aa;et([w()],Ue.prototype,"icon");et([w()],Ue.prototype,"url");et([w({type:Boolean,reflect:!0})],Ue.prototype,"active");et([w({type:Boolean})],Ue.prototype,"external");et([w({type:Boolean})],Ue.prototype,"indicator");et([w()],Ue.prototype,"id");et([w({reflect:!0,type:Boolean,attribute:"icon-only"})],Ue.prototype,"iconOnly");et([be()],Ue.prototype,"subnavState");customElements.get("craft-nav-item")||customElements.define("craft-nav-item",Ue);var Qr=new Set;function Lh(){const s=document.documentElement.clientWidth;return Math.abs(window.innerWidth-s)}function Nh(){const s=Number(getComputedStyle(document.body).paddingRight.replace(/px/,""));return isNaN(s)||!s?0:s}function Hs(s){if(Qr.add(s),!document.documentElement.classList.contains("wa-scroll-lock")){const e=Lh()+Nh();let t=getComputedStyle(document.documentElement).scrollbarGutter;(!t||t==="auto")&&(t="stable"),e<2&&(t=""),document.documentElement.style.setProperty("--wa-scroll-lock-gutter",t),document.documentElement.classList.add("wa-scroll-lock"),document.documentElement.style.setProperty("--wa-scroll-lock-size",`${e}px`)}}function Ws(s){Qr.delete(s),Qr.size===0&&(document.documentElement.classList.remove("wa-scroll-lock"),document.documentElement.style.removeProperty("--wa-scroll-lock-size"))}function Ta(s){return s.split(" ").map(e=>e.trim()).filter(e=>e!=="")}var Rh=()=>({checkValidity(s){const e=s.input,t={message:"",isValid:!0,invalidKeys:[]};if(!e)return t;let r=!0;if("checkValidity"in e&&(r=e.checkValidity()),r)return t;if(t.isValid=!1,"validationMessage"in e&&(t.message=e.validationMessage),!("validity"in e))return t.invalidKeys.push("customError"),t;for(const o in e.validity){if(o==="valid")continue;const i=o;e.validity[i]&&t.invalidKeys.push(i)}return t}}),Oa=class extends Event{constructor(){super("wa-invalid",{bubbles:!0,cancelable:!1,composed:!0})}},Ph=()=>({observedAttributes:["custom-error"],checkValidity(s){const e={message:"",isValid:!0,invalidKeys:[]};return s.customError&&(e.message=s.customError,e.isValid=!1,e.invalidKeys=["customError"]),e}}),Be=class extends ge{constructor(){super(),this.name=null,this.disabled=!1,this.required=!1,this.assumeInteractionOn=["input"],this.validators=[],this.valueHasChanged=!1,this.hasInteracted=!1,this.customError=null,this.emittedEvents=[],this.emitInvalid=e=>{e.target===this&&(this.hasInteracted=!0,this.dispatchEvent(new Oa))},this.handleInteraction=e=>{const t=this.emittedEvents;t.includes(e.type)||t.push(e.type),t.length===this.assumeInteractionOn?.length&&(this.hasInteracted=!0)},this.addEventListener("invalid",this.emitInvalid)}static get validators(){return[Ph()]}static get observedAttributes(){const e=new Set(super.observedAttributes||[]);for(const t of this.validators)if(t.observedAttributes)for(const r of t.observedAttributes)e.add(r);return[...e]}connectedCallback(){super.connectedCallback(),this.updateValidity(),this.assumeInteractionOn.forEach(e=>{this.addEventListener(e,this.handleInteraction)})}firstUpdated(...e){super.firstUpdated(...e),this.updateValidity()}willUpdate(e){if(e.has("customError")&&(this.customError||(this.customError=null),this.setCustomValidity(this.customError||"")),e.has("value")||e.has("disabled")){const t=this.value;if(Array.isArray(t)){if(this.name){const r=new FormData;for(const o of t)r.append(this.name,o);this.setValue(r,r)}}else this.setValue(t,t)}e.has("disabled")&&(this.customStates.set("disabled",this.disabled),(this.hasAttribute("disabled")||!this.matches(":disabled"))&&this.toggleAttribute("disabled",this.disabled)),this.updateValidity(),super.willUpdate(e)}get labels(){return this.internals.labels}getForm(){return this.internals.form}get validity(){return this.internals.validity}get willValidate(){return this.internals.willValidate}get validationMessage(){return this.internals.validationMessage}checkValidity(){return this.updateValidity(),this.internals.checkValidity()}reportValidity(){return this.updateValidity(),this.hasInteracted=!0,this.internals.reportValidity()}get validationTarget(){return this.input||void 0}setValidity(...e){const t=e[0],r=e[1];let o=e[2];o||(o=this.validationTarget),this.internals.setValidity(t,r,o||void 0),this.requestUpdate("validity"),this.setCustomStates()}setCustomStates(){const e=!!this.required,t=this.internals.validity.valid,r=this.hasInteracted;this.customStates.set("required",e),this.customStates.set("optional",!e),this.customStates.set("invalid",!t),this.customStates.set("valid",t),this.customStates.set("user-invalid",!t&&r),this.customStates.set("user-valid",t&&r)}setCustomValidity(e){if(!e){this.customError=null,this.setValidity({});return}this.customError=e,this.setValidity({customError:!0},e,this.validationTarget)}formResetCallback(){this.resetValidity(),this.hasInteracted=!1,this.valueHasChanged=!1,this.emittedEvents=[],this.updateValidity()}formDisabledCallback(e){this.disabled=e,this.updateValidity()}formStateRestoreCallback(e,t){this.value=e,t==="restore"&&this.resetValidity(),this.updateValidity()}setValue(...e){const[t,r]=e;this.internals.setFormValue(t,r)}get allValidators(){const e=this.constructor.validators||[],t=this.validators||[];return[...e,...t]}resetValidity(){this.setCustomValidity(""),this.setValidity({})}updateValidity(){if(this.disabled||this.hasAttribute("disabled")||!this.willValidate){this.resetValidity();return}const e=this.allValidators;if(!e?.length)return;const t={customError:!!this.customError},r=this.validationTarget||this.input||void 0;let o="";for(const i of e){const{isValid:n,message:a,invalidKeys:l}=i.checkValidity(this);n||(o||(o=a),l?.length>=0&&l.forEach(d=>t[d]=!0))}o||(o=this.validationMessage),this.setValidity(t,o,r)}};Be.formAssociated=!0;v([w({reflect:!0})],Be.prototype,"name",2);v([w({type:Boolean})],Be.prototype,"disabled",2);v([w({state:!0,attribute:!1})],Be.prototype,"valueHasChanged",2);v([w({state:!0,attribute:!1})],Be.prototype,"hasInteracted",2);v([w({attribute:"custom-error",reflect:!0})],Be.prototype,"customError",2);v([w({attribute:!1,state:!0,type:Object})],Be.prototype,"validity",1);var Fh=`@layer wa-utilities {
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
`;const La=Symbol.for(""),Mh=s=>{if(s?.r===La)return s?._$litStatic$},Si=(s,...e)=>({_$litStatic$:e.reduce(((t,r,o)=>t+(i=>{if(i._$litStatic$!==void 0)return i._$litStatic$;throw Error(`Value passed to 'literal' function must be a 'literal' result: ${i}. Use 'unsafeStatic' to pass non-literal values, but
            take care to ensure page security.`)})(r)+s[o+1]),s[0]),r:La}),Ai=new Map,Dh=s=>(e,...t)=>{const r=t.length;let o,i;const n=[],a=[];let l,d=0,c=!1;for(;d<r;){for(l=e[d];d<r&&(i=t[d],(o=Mh(i))!==void 0);)l+=o+e[++d],c=!0;d!==r&&a.push(i),n.push(l),d++}if(d===r&&n.push(e[r]),c){const h=n.join("$$lit$$");(e=Ai.get(h))===void 0&&(n.raw=n,Ai.set(h,e=n)),t=a}return s(e,...t)},Pr=Dh(E);var Vh=`@layer wa-component {
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
`,W=class extends Be{constructor(){super(...arguments),this.assumeInteractionOn=["click"],this.hasSlotController=new cr(this,"[default]","start","end"),this.localize=new Nt(this),this.invalid=!1,this.isIconButton=!1,this.title="",this.variant="neutral",this.appearance="accent",this.size="medium",this.withCaret=!1,this.disabled=!1,this.loading=!1,this.pill=!1,this.type="button",this.form=null}static get validators(){return[...super.validators,Rh()]}constructLightDOMButton(){const e=document.createElement("button");return e.type=this.type,e.style.position="absolute",e.style.width="0",e.style.height="0",e.style.clipPath="inset(50%)",e.style.overflow="hidden",e.style.whiteSpace="nowrap",this.name&&(e.name=this.name),e.value=this.value||"",["form","formaction","formenctype","formmethod","formnovalidate","formtarget"].forEach(t=>{this.hasAttribute(t)&&e.setAttribute(t,this.getAttribute(t))}),e}handleClick(){if(!this.getForm())return;const e=this.constructLightDOMButton();this.parentElement?.append(e),e.click(),e.remove()}handleInvalid(){this.dispatchEvent(new Oa)}handleLabelSlotChange(){const e=this.labelSlot.assignedNodes({flatten:!0});let t=!1,r=!1,o=!1,i=!1;[...e].forEach(n=>{if(n.nodeType===Node.ELEMENT_NODE){const a=n;a.localName==="wa-icon"?(r=!0,t||(t=a.label!==void 0)):i=!0}else n.nodeType===Node.TEXT_NODE&&(n.textContent?.trim()||"").length>0&&(o=!0)}),this.isIconButton=r&&!o&&!i,this.isIconButton&&!t&&console.warn('Icon buttons must have a label for screen readers. Add <wa-icon label="..."> to remove this warning.',this)}isButton(){return!this.href}isLink(){return!!this.href}handleDisabledChange(){this.updateValidity()}setValue(...e){}click(){this.button.click()}focus(e){this.button.focus(e)}blur(){this.button.blur()}render(){const e=this.isLink(),t=e?Si`a`:Si`button`;return Pr`
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
        ${this.withCaret?Pr`
                <wa-icon part="caret" class="caret" library="system" name="chevron-down" variant="solid"></wa-icon>
              `:""}
        ${this.loading?Pr`<wa-spinner part="spinner"></wa-spinner>`:""}
      </${t}>
    `}};W.shadowRootOptions={...Be.shadowRootOptions,delegatesFocus:!0};W.css=[Vh,Fh,ba];v([ee(".button")],W.prototype,"button",2);v([ee("slot:not([name])")],W.prototype,"labelSlot",2);v([be()],W.prototype,"invalid",2);v([be()],W.prototype,"isIconButton",2);v([w()],W.prototype,"title",2);v([w({reflect:!0})],W.prototype,"variant",2);v([w({reflect:!0})],W.prototype,"appearance",2);v([w({reflect:!0})],W.prototype,"size",2);v([w({attribute:"with-caret",type:Boolean,reflect:!0})],W.prototype,"withCaret",2);v([w({type:Boolean})],W.prototype,"disabled",2);v([w({type:Boolean,reflect:!0})],W.prototype,"loading",2);v([w({type:Boolean,reflect:!0})],W.prototype,"pill",2);v([w()],W.prototype,"type",2);v([w({reflect:!0})],W.prototype,"name",2);v([w({reflect:!0})],W.prototype,"value",2);v([w({reflect:!0})],W.prototype,"href",2);v([w()],W.prototype,"target",2);v([w()],W.prototype,"rel",2);v([w()],W.prototype,"download",2);v([w({reflect:!0})],W.prototype,"form",2);v([w({attribute:"formaction"})],W.prototype,"formAction",2);v([w({attribute:"formenctype"})],W.prototype,"formEnctype",2);v([w({attribute:"formmethod"})],W.prototype,"formMethod",2);v([w({attribute:"formnovalidate",type:Boolean})],W.prototype,"formNoValidate",2);v([w({attribute:"formtarget"})],W.prototype,"formTarget",2);v([_e("disabled",{waitUntilFirstUpdate:!0})],W.prototype,"handleDisabledChange",1);W=v([Ae("wa-button")],W);var zh=`:host {
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
`,eo=class extends ge{constructor(){super(...arguments),this.localize=new Nt(this)}render(){return E`
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
    `}};eo.css=zh;eo=v([Ae("wa-spinner")],eo);var Ih=`:host {
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
`,$e=class extends ge{constructor(){super(...arguments),this.localize=new Nt(this),this.hasSlotController=new cr(this,"footer","header-actions","label"),this.open=!1,this.label="",this.placement="end",this.withoutHeader=!1,this.lightDismiss=!0,this.handleDocumentKeyDown=e=>{e.key==="Escape"&&this.open&&(e.preventDefault(),e.stopPropagation(),this.requestClose(this.drawer))}}firstUpdated(){this.open&&(this.addOpenListeners(),this.drawer.showModal(),Hs(this))}disconnectedCallback(){super.disconnectedCallback(),Ws(this),this.removeOpenListeners()}async requestClose(e){const t=new ls({source:e});if(this.dispatchEvent(t),t.defaultPrevented){this.open=!0,de(this.drawer,"pulse");return}this.removeOpenListeners(),await de(this.drawer,"hide"),this.open=!1,this.drawer.close(),Ws(this);const r=this.originalTrigger;typeof r?.focus=="function"&&setTimeout(()=>r.focus()),this.dispatchEvent(new ns)}addOpenListeners(){document.addEventListener("keydown",this.handleDocumentKeyDown)}removeOpenListeners(){document.removeEventListener("keydown",this.handleDocumentKeyDown)}handleDialogCancel(e){e.preventDefault(),!this.drawer.classList.contains("hide")&&e.target===this.drawer&&this.requestClose(this.drawer)}handleDialogClick(e){const t=e.target.closest('[data-drawer="close"]');t&&(e.stopPropagation(),this.requestClose(t))}async handleDialogPointerDown(e){e.target===this.drawer&&(this.lightDismiss?this.requestClose(this.drawer):await de(this.drawer,"pulse"))}handleOpenChange(){this.open&&!this.drawer.open?this.show():this.drawer.open&&(this.open=!0,this.requestClose(this.drawer))}async show(){const e=new cs;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}this.addOpenListeners(),this.originalTrigger=document.activeElement,this.open=!0,this.drawer.showModal(),Hs(this),requestAnimationFrame(()=>{const t=this.querySelector("[autofocus]");t&&typeof t.focus=="function"?t.focus():this.drawer.focus()}),await de(this.drawer,"show"),this.dispatchEvent(new as)}render(){const e=!this.withoutHeader,t=this.hasSlotController.test("footer");return E`
      <dialog
        part="dialog"
        class=${Re({drawer:!0,open:this.open,top:this.placement==="top",end:this.placement==="end",bottom:this.placement==="bottom",start:this.placement==="start"})}
        @cancel=${this.handleDialogCancel}
        @click=${this.handleDialogClick}
        @pointerdown=${this.handleDialogPointerDown}
      >
        ${e?E`
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
                    @click="${r=>this.requestClose(r.target)}"
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

        ${t?E`
              <footer part="footer" class="footer">
                <slot name="footer"></slot>
              </footer>
            `:""}
      </dialog>
    `}};$e.css=Ih;v([ee(".drawer")],$e.prototype,"drawer",2);v([w({type:Boolean,reflect:!0})],$e.prototype,"open",2);v([w({reflect:!0})],$e.prototype,"label",2);v([w({reflect:!0})],$e.prototype,"placement",2);v([w({attribute:"without-header",type:Boolean,reflect:!0})],$e.prototype,"withoutHeader",2);v([w({attribute:"light-dismiss",type:Boolean})],$e.prototype,"lightDismiss",2);v([_e("open",{waitUntilFirstUpdate:!0})],$e.prototype,"handleOpenChange",1);$e=v([Ae("wa-drawer")],$e);document.addEventListener("click",s=>{const e=s.target.closest("[data-drawer]");if(e instanceof Element){const[t,r]=Ta(e.getAttribute("data-drawer")||"");if(t==="open"&&r?.length){const o=e.getRootNode().getElementById(r);o?.localName==="wa-drawer"?o.open=!0:console.warn(`A drawer with an ID of "${r}" could not be found in this document.`)}}});document.body.addEventListener("pointerdown",()=>{});let Uh=class extends $e{static get styles(){return[$e.styles,P`
        :host {
          --wa-color-surface-raised: var(--c-bg-raised);
          --spacing: var(--c-spacing-lg);
          background-color: red;
        }
      `]}};customElements.get("craft-drawer")||customElements.define("craft-drawer",Uh);var Bh=`:host {
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
`,Se=class extends ge{constructor(){super(...arguments),this.localize=new Nt(this),this.hasSlotController=new cr(this,"footer","header-actions","label"),this.open=!1,this.label="",this.withoutHeader=!1,this.lightDismiss=!1,this.handleDocumentKeyDown=e=>{e.key==="Escape"&&this.open&&(e.preventDefault(),e.stopPropagation(),this.requestClose(this.dialog))}}firstUpdated(){this.open&&(this.addOpenListeners(),this.dialog.showModal(),Hs(this))}disconnectedCallback(){super.disconnectedCallback(),Ws(this),this.removeOpenListeners()}async requestClose(e){const t=new ls({source:e});if(this.dispatchEvent(t),t.defaultPrevented){this.open=!0,de(this.dialog,"pulse");return}this.removeOpenListeners(),await de(this.dialog,"hide"),this.open=!1,this.dialog.close(),Ws(this);const r=this.originalTrigger;typeof r?.focus=="function"&&setTimeout(()=>r.focus()),this.dispatchEvent(new ns)}addOpenListeners(){document.addEventListener("keydown",this.handleDocumentKeyDown)}removeOpenListeners(){document.removeEventListener("keydown",this.handleDocumentKeyDown)}handleDialogCancel(e){e.preventDefault(),!this.dialog.classList.contains("hide")&&e.target===this.dialog&&this.requestClose(this.dialog)}handleDialogClick(e){const t=e.target.closest('[data-dialog="close"]');t&&(e.stopPropagation(),this.requestClose(t))}async handleDialogPointerDown(e){e.target===this.dialog&&(this.lightDismiss?this.requestClose(this.dialog):await de(this.dialog,"pulse"))}handleOpenChange(){this.open&&!this.dialog.open?this.show():!this.open&&this.dialog.open&&(this.open=!0,this.requestClose(this.dialog))}async show(){const e=new cs;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}this.addOpenListeners(),this.originalTrigger=document.activeElement,this.open=!0,this.dialog.showModal(),Hs(this),requestAnimationFrame(()=>{const t=this.querySelector("[autofocus]");t&&typeof t.focus=="function"?t.focus():this.dialog.focus()}),await de(this.dialog,"show"),this.dispatchEvent(new as)}render(){const e=!this.withoutHeader,t=this.hasSlotController.test("footer");return E`
      <dialog
        part="dialog"
        class=${Re({dialog:!0,open:this.open})}
        @cancel=${this.handleDialogCancel}
        @click=${this.handleDialogClick}
        @pointerdown=${this.handleDialogPointerDown}
      >
        ${e?E`
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
                    @click="${r=>this.requestClose(r.target)}"
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

        ${t?E`
              <footer part="footer" class="footer">
                <slot name="footer"></slot>
              </footer>
            `:""}
      </dialog>
    `}};Se.css=Bh;v([ee(".dialog")],Se.prototype,"dialog",2);v([w({type:Boolean,reflect:!0})],Se.prototype,"open",2);v([w({reflect:!0})],Se.prototype,"label",2);v([w({attribute:"without-header",type:Boolean,reflect:!0})],Se.prototype,"withoutHeader",2);v([w({attribute:"light-dismiss",type:Boolean})],Se.prototype,"lightDismiss",2);v([_e("open",{waitUntilFirstUpdate:!0})],Se.prototype,"handleOpenChange",1);Se=v([Ae("wa-dialog")],Se);document.addEventListener("click",s=>{const e=s.target.closest("[data-dialog]");if(e instanceof Element){const[t,r]=Ta(e.getAttribute("data-dialog")||"");if(t==="open"&&r?.length){const o=e.getRootNode().getElementById(r);o?.localName==="wa-dialog"?o.open=!0:console.warn(`A dialog with an ID of "${r}" could not be found in this document.`)}}});document.addEventListener("pointerdown",()=>{});let jh=class extends Se{static get styles(){return[Se.styles,P`
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
      `]}};customElements.get("craft-dialog")||customElements.define("craft-dialog",jh);let qh=class extends EventTarget{constructor(e,t){super(),this.__param=e,this.__config=t||{},this.type=t?.type||"error"}static _$isValidator$=!0;static validatorName="";static async=!1;execute(e,t,r){if(!this.constructor.validatorName)throw new Error(`A validator needs to have a name! Please set it via "static get validatorName() { return 'IsCat'; }"`);return!0}set param(e){this.__param=e,this.dispatchEvent(new Event("param-changed"))}get param(){return this.__param}set config(e){this.__config=e,this.dispatchEvent(new Event("config-changed"))}get config(){return this.__config}async _getMessage(e){const t=this.constructor,r={name:t.validatorName,type:this.type,params:this.param,config:this.config,...e};if(this.config.getMessage){if(typeof this.config.getMessage=="function")return this.config.getMessage(r);throw new Error(`You must provide a value for getMessage of type 'function', you provided a value of type: ${typeof this.config.getMessage}`)}return t.getMessage(r)}static async getMessage(e){return`Please configure an error message for "${this.name}" by overriding "static async getMessage()"`}onFormControlConnect(e){}onFormControlDisconnect(e){}abortExecution(){}},to=class extends Array{_keys(){return Object.keys(this).filter(e=>Number.isNaN(Number(e)))}};const Hh=s=>class extends No(s){static get properties(){return{_isFormOrFieldset:{type:Boolean}}}constructor(){super(),this.formElements=new to,this._isFormOrFieldset=!1,this._onRequestToAddFormElement=this._onRequestToAddFormElement.bind(this),this._onRequestToChangeFormElementName=this._onRequestToChangeFormElementName.bind(this),this.addEventListener("form-element-register",this._onRequestToAddFormElement),this.addEventListener("form-element-name-changed",this._onRequestToChangeFormElementName),this.initComplete=new Promise((e,t)=>{this.__resolveInitComplete=e,this.__rejectInitComplete=t}),this.registrationComplete=new Promise((e,t)=>{this.__resolveRegistrationComplete=e,this.__rejectRegistrationComplete=t}),this.registrationComplete.done=!1,this.registrationComplete.then(()=>{this.registrationComplete.done=!0,this.__resolveInitComplete(void 0)},()=>{throw this.registrationComplete.done=!0,this.__rejectInitComplete(void 0),new Error("Registration could not finish. Please use await el.registrationComplete;")})}connectedCallback(){super.connectedCallback(),this._completeRegistration()}_completeRegistration(){Promise.resolve().then(()=>this.__resolveRegistrationComplete(void 0))}disconnectedCallback(){super.disconnectedCallback(),this.registrationComplete.done===!1&&Promise.resolve().then(()=>{Promise.resolve().then(()=>{this.__rejectRegistrationComplete()})})}isRegisteredFormElement(e){return this.formElements.some(t=>t===e)}addFormElement(e,t){if(e._parentFormGroup=this,t>=0?this.formElements.splice(t,0,e):this.formElements.push(e),this._isFormOrFieldset){const{name:r}=e;if(r===this.name)throw console.info("Error Node:",e),new TypeError(`You can not have the same name "${r}" as your parent`);if(r.substr(-2)==="[]")Array.isArray(this.formElements[r])||(this.formElements[r]=new to),t>0?this.formElements[r].splice(t,0,e):this.formElements[r].push(e);else if(!this.formElements[r])this.formElements[r]=e;else throw console.info("Error Node:",e),new TypeError(`Name "${r}" is already registered - if you want an array add [] to the end`)}}removeFormElement(e){const t=this.formElements.indexOf(e);if(t>-1&&this.formElements.splice(t,1),this._isFormOrFieldset){const{name:r}=e;if(r.substr(-2)==="[]"&&this.formElements[r]){const o=this.formElements[r].indexOf(e);o>-1&&this.formElements[r].splice(o,1)}else this.formElements[r]&&delete this.formElements[r]}}_onRequestToAddFormElement(e){const t=e.detail.element;if(t===this||this.isRegisteredFormElement(t))return;e.stopPropagation();let r=-1;if(this.formElements&&Array.isArray(this.formElements)){for(const[o,i]of this.formElements.entries())if(!(i.compareDocumentPosition(t)&Node.DOCUMENT_POSITION_FOLLOWING)){r=o;break}}this.addFormElement(t,r)}_onRequestToChangeFormElementName(e){const t=this.formElements[e.detail.oldName];t&&(this.formElements[e.detail.newName]=t,delete this.formElements[e.detail.oldName])}_onRequestToRemoveFormElement(e){const t=e.detail.element;t!==this&&this.isRegisteredFormElement(t)&&(e.stopPropagation(),this.removeFormElement(t))}},Na=ie(Hh),Wh=s=>class extends Na(ar(aa(s))){static get properties(){return{multipleChoice:{type:Boolean,attribute:"multiple-choice"}}}get modelValue(){const e=this._getCheckedElements();return this.multipleChoice?e.map(t=>t.choiceValue):e[0]?e[0].choiceValue:""}set modelValue(e){const t=(r,o)=>typeof r.choiceValue=="object"?JSON.stringify(r.choiceValue)===JSON.stringify(e):r.choiceValue===o;this.__isInitialModelValue?this.registrationComplete.then(()=>{this.__isInitialModelValue=!1,this._setCheckedElements(e,t),this.requestUpdate("modelValue",this._oldModelValue)}):(this._setCheckedElements(e,t),this.requestUpdate("modelValue",this._oldModelValue)),this._oldModelValue=this.modelValue}get serializedValue(){const e=this._getCheckedElements();return this.multipleChoice?e.map(t=>t.serializedValue.value):e[0]?e[0].serializedValue.value:""}set serializedValue(e){const t=(r,o)=>r.serializedValue.value===o;this.__isInitialSerializedValue?this.registrationComplete.then(()=>{this.__isInitialSerializedValue=!1,this._setCheckedElements(e,t),this.requestUpdate("serializedValue")}):(this._setCheckedElements(e,t),this.requestUpdate("serializedValue"))}get formattedValue(){const e=this._getCheckedElements();return this.multipleChoice?e.map(t=>t.formattedValue):e[0]?e[0].formattedValue:""}set formattedValue(e){const t=(r,o)=>r.formattedValue===o;this.__isInitialFormattedValue?this.registrationComplete.then(()=>{this.__isInitialFormattedValue=!1,this._setCheckedElements(e,t)}):this._setCheckedElements(e,t)}get operationMode(){return this._repropagationRole==="choice-group"?"select":"enter"}constructor(){super(),this.multipleChoice=!1,this._repropagationRole="choice-group",this.__isInitialModelValue=!0,this.__isInitialSerializedValue=!0,this.__isInitialFormattedValue=!0}connectedCallback(){super.connectedCallback(),this.registrationComplete.then(()=>{this.__isInitialModelValue=!1,this.__isInitialSerializedValue=!1,this.__isInitialFormattedValue=!1})}_completeRegistration(){Promise.resolve().then(()=>super._completeRegistration())}updated(e){super.updated(e),e.has("name")&&this.name!==e.get("name")&&this.formElements.forEach(t=>{t.name=this.name})}addFormElement(e,t){this._throwWhenInvalidChildModelValue(e),e.name=this.name,super.addFormElement(e,t)}clear(){this.multipleChoice?this.modelValue=[]:this.modelValue=""}_triggerInitialModelValueChangedEvent(){this.registrationComplete.then(()=>{this._dispatchInitialModelValueChangedEvent()})}_getFromAllFormElementsFilter(e,t){return!0}_getFromAllFormElements(e,t){const r=t||this._getFromAllFormElementsFilter;return e==="modelValue"||e==="serializedValue"||e==="formattedValue"?this[e]:this.formElements.filter(o=>r(o,e)).map(o=>o.property)}_throwWhenInvalidChildModelValue(e){if(typeof e.modelValue.checked!="boolean"||!Object.prototype.hasOwnProperty.call(e.modelValue,"value"))throw new Error(`The ${this.tagName.toLowerCase()} name="${this.name}" does not allow to register ${e.tagName.toLowerCase()} with .modelValue="${e.modelValue}" - The modelValue should represent an Object { value: "foo", checked: false }`)}_isEmpty(){return this.multipleChoice?this.modelValue.length===0:typeof this.modelValue=="string"&&this.modelValue===""||this.modelValue===void 0||this.modelValue===null}_checkSingleChoiceElements(e){const{target:t}=e;if(t.checked===!1)return;const r=t.name;this.formElements.filter(o=>o.name===r).forEach(o=>{o!==t&&(o.checked=!1)})}_getCheckedElements(){return this.formElements.filter(e=>e.checked&&!e.disabled)}_setCheckedElements(e,t){if(e==null){this.formElements.forEach(r=>r.checked=!1);return}for(let r=0;r<this.formElements.length;r+=1)if(this.multipleChoice){let o=e.includes(this.formElements[r].modelValue.value);typeof this.formElements[r].modelValue.value=="object"&&(o=e.map(i=>JSON.stringify(i)).includes(JSON.stringify(this.formElements[r].modelValue.value))),this.formElements[r].checked=o}else t(this.formElements[r],e)?this.formElements[r].checked=!0:this.formElements[r].checked=!1}__setChoiceGroupTouched(){const e=this.modelValue;e!=null&&e!==this.__previousCheckedValue&&(this.touched=!0,this.__previousCheckedValue=e)}_onBeforeRepropagateChildrenValues(e){const t=e.detail&&e.detail.element||e.target;this.multipleChoice||!t.checked||(this.formElements.forEach(r=>{t.choiceValue!==r.choiceValue&&(r.checked=!1)}),this.__setChoiceGroupTouched(),this.requestUpdate("modelValue",this._oldModelValue),this._oldModelValue=this.modelValue)}_repropagationCondition(e){return!(this._repropagationRole==="choice-group"&&!this.multipleChoice&&!e.checked)}},Kh=ie(Wh);let Gh=class extends qh{static get validatorName(){return"FormElementsHaveNoError"}execute(e,t,r){return r?.node._anyFormElementHasFeedbackFor("error")}static async getMessage(){return""}};const Jh=s=>class extends Na(Mt(ar(hs(ms(s))))){static get properties(){return{submitted:{type:Boolean,reflect:!0},focused:{type:Boolean,reflect:!0},dirty:{type:Boolean,reflect:!0},touched:{type:Boolean,reflect:!0},prefilled:{type:Boolean,reflect:!0}}}get _inputNode(){return this}get modelValue(){return this._getFromAllFormElements("modelValue")}set modelValue(e){this.__isInitialModelValue?(this.__isInitialModelValue=!1,this.registrationComplete.then(()=>{this._setValueMapForAllFormElements("modelValue",e)})):this._setValueMapForAllFormElements("modelValue",e)}get serializedValue(){return this._getFromAllFormElements("serializedValue")}set serializedValue(e){this.__isInitialSerializedValue?(this.__isInitialSerializedValue=!1,this.registrationComplete.then(()=>{this._setValueMapForAllFormElements("serializedValue",e)})):this._setValueMapForAllFormElements("serializedValue",e)}get formattedValue(){return this._getFromAllFormElements("formattedValue")}set formattedValue(e){this._setValueMapForAllFormElements("formattedValue",e)}get prefilled(){return this._everyFormElementHas("prefilled")}constructor(){super(),this.value="",this.disabled=!1,this.submitted=!1,this.dirty=!1,this.touched=!1,this.focused=!1,this.__addedSubValidators=!1,this.__isInitialModelValue=!0,this.__isInitialSerializedValue=!0,this._checkForOutsideClick=this._checkForOutsideClick.bind(this),this.addEventListener("focusin",this._syncFocused),this.addEventListener("focusout",this._onFocusOut),this.addEventListener("dirty-changed",this._syncDirty),this.addEventListener("validate-performed",this.__onChildValidatePerformed),this.defaultValidators=[new Gh],this.__descriptionElementsInParentChain=new Set,this.__pendingValues={modelValue:{},serializedValue:{}}}connectedCallback(){super.connectedCallback(),this.setAttribute("role","group"),this.initComplete.then(()=>{this.__isInitialModelValue=!1,this.__isInitialSerializedValue=!1,this.__initInteractionStates()})}disconnectedCallback(){super.disconnectedCallback(),this.__hasActiveOutsideClickHandling&&(document.removeEventListener("click",this._checkForOutsideClick),this.__hasActiveOutsideClickHandling=!1),this.__descriptionElementsInParentChain.clear()}__initInteractionStates(){this.formElements.forEach(e=>{typeof e.initInteractionState=="function"&&e.initInteractionState()})}_triggerInitialModelValueChangedEvent(){this.registrationComplete.then(()=>{this._dispatchInitialModelValueChangedEvent()})}updated(e){super.updated(e),e.has("disabled")&&(this.disabled?this.__requestChildrenToBeDisabled():this.__retractRequestChildrenToBeDisabled()),e.has("focused")&&this.focused===!0&&this.__setupOutsideClickHandling()}__setupOutsideClickHandling(){this.__hasActiveOutsideClickHandling||(document.addEventListener("click",this._checkForOutsideClick),this.__hasActiveOutsideClickHandling=!0)}_checkForOutsideClick(e){!this.contains(e.target)&&(this.touched=!0)}__requestChildrenToBeDisabled(){this.formElements.forEach(e=>{e.makeRequestToBeDisabled&&e.makeRequestToBeDisabled()})}__retractRequestChildrenToBeDisabled(){this.formElements.forEach(e=>{e.retractRequestToBeDisabled&&e.retractRequestToBeDisabled()})}_inputGroupTemplate(){return E`
        <div class="input-group">
          <slot></slot>
        </div>
      `}submitGroup(){this.submitted=!0,this.formElements.forEach(e=>{typeof e.submitGroup=="function"?e.submitGroup():e.submitted=!0})}resetGroup(){this.formElements.forEach(e=>{typeof e.resetGroup=="function"?e.resetGroup():typeof e.reset=="function"&&e.reset()}),this.resetInteractionState()}clearGroup(){this.formElements.forEach(e=>{typeof e.clearGroup=="function"?e.clearGroup():typeof e.clear=="function"&&e.clear()}),this.resetInteractionState()}resetInteractionState(){this.submitted=!1,this.touched=!1,this.dirty=!1,this.formElements.forEach(e=>{typeof e.resetInteractionState=="function"&&e.resetInteractionState()})}_getFromAllFormElementsFilter(e,t){return!e.disabled}_getFromAllFormElements(e,t){const r={},o=t||this._getFromAllFormElementsFilter;return this.formElements._keys().forEach(i=>{const n=this.formElements[i];n instanceof to?r[i]=n.filter(a=>o(a,e)).map(a=>a[e]):o(n,e)&&(typeof n._getFromAllFormElements=="function"?r[i]=n._getFromAllFormElements(e):r[i]=n[e])}),r}_setValueForAllFormElements(e,t){this.formElements.forEach(r=>{r[e]=t})}_setValueMapForAllFormElements(e,t){t&&typeof t=="object"&&Object.keys(t).forEach(r=>{Array.isArray(this.formElements[r])&&this.formElements[r].forEach((o,i)=>{o[e]=t[r][i]}),this.formElements[r]?this.formElements[r][e]=t[r]:this.__pendingValues[e][r]=t[r]})}_anyFormElementHas(e){return Object.keys(this.formElements).some(t=>Array.isArray(this.formElements[t])?this.formElements[t].some(r=>!!r[e]):!!this.formElements[t][e])}_anyFormElementHasFeedbackFor(e){return Object.keys(this.formElements).some(t=>Array.isArray(this.formElements[t])?this.formElements[t].some(r=>!!(r.hasFeedbackFor&&r.hasFeedbackFor.includes(e))):!!(this.formElements[t].hasFeedbackFor&&this.formElements[t].hasFeedbackFor.includes(e)))}_everyFormElementHas(e){return Object.keys(this.formElements).every(t=>Array.isArray(this.formElements[t])?this.formElements[t].every(r=>!!r[e]):!!this.formElements[t][e])}__onChildValidatePerformed(e){e&&this.isRegisteredFormElement(e.target)&&this.validate()}_syncFocused(){this.focused=this._anyFormElementHas("focused")}_onFocusOut(e){const t=this.formElements[this.formElements.length-1];e.target===t&&(this.touched=!0),this.focused=!1}_syncDirty(){this.dirty=this._anyFormElementHas("dirty")}__storeAllDescriptionElementsInParentChain(){let e=this;for(;e;){const t=e._getAriaDescriptionElements();na(t,{reverse:!0}).forEach(r=>{r.getAttribute("slot")==="feedback"&&this.__descriptionElementsInParentChain.add(r)}),e=e._parentFormGroup}}__linkParentMessages(e){this.__descriptionElementsInParentChain.forEach(t=>{typeof e.addToAriaDescribedBy=="function"&&e.addToAriaDescribedBy(t,{reorder:!1})})}__unlinkParentMessages(e){this.__descriptionElementsInParentChain.forEach(t=>{typeof e.removeFromAriaDescribedBy=="function"&&e.removeFromAriaDescribedBy(t)})}addFormElement(e,t){if(super.addFormElement(e,t),this.disabled&&e.makeRequestToBeDisabled(),this.__descriptionElementsInParentChain.size||this.__storeAllDescriptionElementsInParentChain(),this.__linkParentMessages(e),this.validate({clearCurrentResult:!0}),!e.modelValue){const r=this.__pendingValues;r.modelValue&&r.modelValue[e.name]?e.modelValue=r.modelValue[e.name]:r.serializedValue&&r.serializedValue[e.name]&&(e.serializedValue=r.serializedValue[e.name])}}get _initialModelValue(){return this._getFromAllFormElements("_initialModelValue")}removeFormElement(e){super.removeFormElement(e),this.validate({clearCurrentResult:!0}),typeof e.removeFromAriaLabelledBy=="function"&&this._labelNode&&e.removeFromAriaLabelledBy(this._labelNode,{reorder:!1}),this.__unlinkParentMessages(e)}_isEmpty(){return this.formElements.every(e=>e._isEmpty?.())}},Yh=ie(Jh);let Ti=class extends Kh(Yh(Y)){constructor(){super(),this.multipleChoice=!0}},Xh=class extends Ti{static get styles(){return[...Ti.styles,P`
        .form-field__group-two {
          margin-top: var(--c-spacing-sm);
        }

        ::slotted(label) {
          font-weight: bold;
        }
      `]}};customElements.get("craft-checkbox-group")||customElements.define("craft-checkbox-group",Xh);let Oi=class extends Do(Po){connectedCallback(){super.connectedCallback(),this.type="checkbox"}},Zh=class extends Oi{static get styles(){return[...Oi.styles,P`
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
      `]}};customElements.get("craft-checkbox")||customElements.define("craft-checkbox",Zh);const Qh=P`
  :host {
    display: block;
    padding: var(--c-spacing-sm) var(--c-spacing-md);
    border-radius: var(--c-callout-radius, var(--c-radius-md));
    background-color: var(--c-callout-bg);
    border: 1px solid var(--c-callout-border-color);
    color: var(--c-callout-fg);
  }

  :host([variant='danger']) {
    --c-callout-bg: var(--c-color-danger-bg-normal);
    --c-callout-border-color: var(--c-color-danger-border-normal);
    --c-callout-fg: var(--c-color-danger-on-normal);
  }
`;var ep=Object.defineProperty,Ra=(s,e,t,r)=>{for(var o=void 0,i=s.length-1,n;i>=0;i--)(n=s[i])&&(o=n(e,t,o)||o);return o&&ep(e,t,o),o};const Pa=class extends Y{constructor(){super(...arguments),this.variant="default",this.appearance="outline-filled"}render(){return E`<slot></slot>`}};Pa.styles=[Qh];let Vo=Pa;Ra([w({reflect:!0})],Vo.prototype,"variant");Ra([w()],Vo.prototype,"appearance");customElements.get("craft-callout")||customElements.define("craft-callout",Vo);function Fa(s,e){return function(){return s.apply(e,arguments)}}const{toString:tp}=Object.prototype,{getPrototypeOf:zo}=Object,{iterator:dr,toStringTag:Ma}=Symbol,ur=(s=>e=>{const t=tp.call(e);return s[t]||(s[t]=t.slice(8,-1).toLowerCase())})(Object.create(null)),Te=s=>(s=s.toLowerCase(),e=>ur(e)===s),hr=s=>e=>typeof e===s,{isArray:Vt}=Array,At=hr("undefined");function fs(s){return s!==null&&!At(s)&&s.constructor!==null&&!At(s.constructor)&&me(s.constructor.isBuffer)&&s.constructor.isBuffer(s)}const Da=Te("ArrayBuffer");function sp(s){let e;return typeof ArrayBuffer<"u"&&ArrayBuffer.isView?e=ArrayBuffer.isView(s):e=s&&s.buffer&&Da(s.buffer),e}const rp=hr("string"),me=hr("function"),Va=hr("number"),gs=s=>s!==null&&typeof s=="object",op=s=>s===!0||s===!1,Ns=s=>{if(ur(s)!=="object")return!1;const e=zo(s);return(e===null||e===Object.prototype||Object.getPrototypeOf(e)===null)&&!(Ma in s)&&!(dr in s)},ip=s=>{if(!gs(s)||fs(s))return!1;try{return Object.keys(s).length===0&&Object.getPrototypeOf(s)===Object.prototype}catch{return!1}},np=Te("Date"),ap=Te("File"),lp=Te("Blob"),cp=Te("FileList"),dp=s=>gs(s)&&me(s.pipe),up=s=>{let e;return s&&(typeof FormData=="function"&&s instanceof FormData||me(s.append)&&((e=ur(s))==="formdata"||e==="object"&&me(s.toString)&&s.toString()==="[object FormData]"))},hp=Te("URLSearchParams"),[pp,mp,fp,gp]=["ReadableStream","Request","Response","Headers"].map(Te),bp=s=>s.trim?s.trim():s.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g,"");function bs(s,e,{allOwnKeys:t=!1}={}){if(s===null||typeof s>"u")return;let r,o;if(typeof s!="object"&&(s=[s]),Vt(s))for(r=0,o=s.length;r<o;r++)e.call(null,s[r],r,s);else{if(fs(s))return;const i=t?Object.getOwnPropertyNames(s):Object.keys(s),n=i.length;let a;for(r=0;r<n;r++)a=i[r],e.call(null,s[a],a,s)}}function za(s,e){if(fs(s))return null;e=e.toLowerCase();const t=Object.keys(s);let r=t.length,o;for(;r-- >0;)if(o=t[r],e===o.toLowerCase())return o;return null}const at=typeof globalThis<"u"?globalThis:typeof self<"u"?self:typeof window<"u"?window:global,Ia=s=>!At(s)&&s!==at;function so(){const{caseless:s,skipUndefined:e}=Ia(this)&&this||{},t={},r=(o,i)=>{const n=s&&za(t,i)||i;Ns(t[n])&&Ns(o)?t[n]=so(t[n],o):Ns(o)?t[n]=so({},o):Vt(o)?t[n]=o.slice():(!e||!At(o))&&(t[n]=o)};for(let o=0,i=arguments.length;o<i;o++)arguments[o]&&bs(arguments[o],r);return t}const vp=(s,e,t,{allOwnKeys:r}={})=>(bs(e,(o,i)=>{t&&me(o)?s[i]=Fa(o,t):s[i]=o},{allOwnKeys:r}),s),yp=s=>(s.charCodeAt(0)===65279&&(s=s.slice(1)),s),wp=(s,e,t,r)=>{s.prototype=Object.create(e.prototype,r),s.prototype.constructor=s,Object.defineProperty(s,"super",{value:e.prototype}),t&&Object.assign(s.prototype,t)},_p=(s,e,t,r)=>{let o,i,n;const a={};if(e=e||{},s==null)return e;do{for(o=Object.getOwnPropertyNames(s),i=o.length;i-- >0;)n=o[i],(!r||r(n,s,e))&&!a[n]&&(e[n]=s[n],a[n]=!0);s=t!==!1&&zo(s)}while(s&&(!t||t(s,e))&&s!==Object.prototype);return e},$p=(s,e,t)=>{s=String(s),(t===void 0||t>s.length)&&(t=s.length),t-=e.length;const r=s.indexOf(e,t);return r!==-1&&r===t},Ep=s=>{if(!s)return null;if(Vt(s))return s;let e=s.length;if(!Va(e))return null;const t=new Array(e);for(;e-- >0;)t[e]=s[e];return t},xp=(s=>e=>s&&e instanceof s)(typeof Uint8Array<"u"&&zo(Uint8Array)),kp=(s,e)=>{const t=(s&&s[dr]).call(s);let r;for(;(r=t.next())&&!r.done;){const o=r.value;e.call(s,o[0],o[1])}},Cp=(s,e)=>{let t;const r=[];for(;(t=s.exec(e))!==null;)r.push(t);return r},Sp=Te("HTMLFormElement"),Ap=s=>s.toLowerCase().replace(/[-_\s]([a-z\d])(\w*)/g,function(e,t,r){return t.toUpperCase()+r}),Li=(({hasOwnProperty:s})=>(e,t)=>s.call(e,t))(Object.prototype),Tp=Te("RegExp"),Ua=(s,e)=>{const t=Object.getOwnPropertyDescriptors(s),r={};bs(t,(o,i)=>{let n;(n=e(o,i,s))!==!1&&(r[i]=n||o)}),Object.defineProperties(s,r)},Op=s=>{Ua(s,(e,t)=>{if(me(s)&&["arguments","caller","callee"].indexOf(t)!==-1)return!1;const r=s[t];if(me(r)){if(e.enumerable=!1,"writable"in e){e.writable=!1;return}e.set||(e.set=()=>{throw Error("Can not rewrite read-only method '"+t+"'")})}})},Lp=(s,e)=>{const t={},r=o=>{o.forEach(i=>{t[i]=!0})};return Vt(s)?r(s):r(String(s).split(e)),t},Np=()=>{},Rp=(s,e)=>s!=null&&Number.isFinite(s=+s)?s:e;function Pp(s){return!!(s&&me(s.append)&&s[Ma]==="FormData"&&s[dr])}const Fp=s=>{const e=new Array(10),t=(r,o)=>{if(gs(r)){if(e.indexOf(r)>=0)return;if(fs(r))return r;if(!("toJSON"in r)){e[o]=r;const i=Vt(r)?[]:{};return bs(r,(n,a)=>{const l=t(n,o+1);!At(l)&&(i[a]=l)}),e[o]=void 0,i}}return r};return t(s,0)},Mp=Te("AsyncFunction"),Dp=s=>s&&(gs(s)||me(s))&&me(s.then)&&me(s.catch),Ba=((s,e)=>s?setImmediate:e?((t,r)=>(at.addEventListener("message",({source:o,data:i})=>{o===at&&i===t&&r.length&&r.shift()()},!1),o=>{r.push(o),at.postMessage(t,"*")}))(`axios@${Math.random()}`,[]):t=>setTimeout(t))(typeof setImmediate=="function",me(at.postMessage)),Vp=typeof queueMicrotask<"u"?queueMicrotask.bind(at):typeof process<"u"&&process.nextTick||Ba,zp=s=>s!=null&&me(s[dr]),y={isArray:Vt,isArrayBuffer:Da,isBuffer:fs,isFormData:up,isArrayBufferView:sp,isString:rp,isNumber:Va,isBoolean:op,isObject:gs,isPlainObject:Ns,isEmptyObject:ip,isReadableStream:pp,isRequest:mp,isResponse:fp,isHeaders:gp,isUndefined:At,isDate:np,isFile:ap,isBlob:lp,isRegExp:Tp,isFunction:me,isStream:dp,isURLSearchParams:hp,isTypedArray:xp,isFileList:cp,forEach:bs,merge:so,extend:vp,trim:bp,stripBOM:yp,inherits:wp,toFlatObject:_p,kindOf:ur,kindOfTest:Te,endsWith:$p,toArray:Ep,forEachEntry:kp,matchAll:Cp,isHTMLForm:Sp,hasOwnProperty:Li,hasOwnProp:Li,reduceDescriptors:Ua,freezeMethods:Op,toObjectSet:Lp,toCamelCase:Ap,noop:Np,toFiniteNumber:Rp,findKey:za,global:at,isContextDefined:Ia,isSpecCompliantForm:Pp,toJSONObject:Fp,isAsyncFn:Mp,isThenable:Dp,setImmediate:Ba,asap:Vp,isIterable:zp};function F(s,e,t,r,o){Error.call(this),Error.captureStackTrace?Error.captureStackTrace(this,this.constructor):this.stack=new Error().stack,this.message=s,this.name="AxiosError",e&&(this.code=e),t&&(this.config=t),r&&(this.request=r),o&&(this.response=o,this.status=o.status?o.status:null)}y.inherits(F,Error,{toJSON:function(){return{message:this.message,name:this.name,description:this.description,number:this.number,fileName:this.fileName,lineNumber:this.lineNumber,columnNumber:this.columnNumber,stack:this.stack,config:y.toJSONObject(this.config),code:this.code,status:this.status}}});const ja=F.prototype,qa={};["ERR_BAD_OPTION_VALUE","ERR_BAD_OPTION","ECONNABORTED","ETIMEDOUT","ERR_NETWORK","ERR_FR_TOO_MANY_REDIRECTS","ERR_DEPRECATED","ERR_BAD_RESPONSE","ERR_BAD_REQUEST","ERR_CANCELED","ERR_NOT_SUPPORT","ERR_INVALID_URL"].forEach(s=>{qa[s]={value:s}});Object.defineProperties(F,qa);Object.defineProperty(ja,"isAxiosError",{value:!0});F.from=(s,e,t,r,o,i)=>{const n=Object.create(ja);y.toFlatObject(s,n,function(d){return d!==Error.prototype},d=>d!=="isAxiosError");const a=s&&s.message?s.message:"Error",l=e==null&&s?s.code:e;return F.call(n,a,l,t,r,o),s&&n.cause==null&&Object.defineProperty(n,"cause",{value:s,configurable:!0}),n.name=s&&s.name||"Error",i&&Object.assign(n,i),n};const Ip=null;function ro(s){return y.isPlainObject(s)||y.isArray(s)}function Ha(s){return y.endsWith(s,"[]")?s.slice(0,-2):s}function Ni(s,e,t){return s?s.concat(e).map(function(r,o){return r=Ha(r),!t&&o?"["+r+"]":r}).join(t?".":""):e}function Up(s){return y.isArray(s)&&!s.some(ro)}const Bp=y.toFlatObject(y,{},null,function(s){return/^is[A-Z]/.test(s)});function pr(s,e,t){if(!y.isObject(s))throw new TypeError("target must be an object");e=e||new FormData,t=y.toFlatObject(t,{metaTokens:!0,dots:!1,indexes:!1},!1,function(f,m){return!y.isUndefined(m[f])});const r=t.metaTokens,o=t.visitor||d,i=t.dots,n=t.indexes,a=(t.Blob||typeof Blob<"u"&&Blob)&&y.isSpecCompliantForm(e);if(!y.isFunction(o))throw new TypeError("visitor must be a function");function l(f){if(f===null)return"";if(y.isDate(f))return f.toISOString();if(y.isBoolean(f))return f.toString();if(!a&&y.isBlob(f))throw new F("Blob is not supported. Use a Buffer instead.");return y.isArrayBuffer(f)||y.isTypedArray(f)?a&&typeof Blob=="function"?new Blob([f]):Buffer.from(f):f}function d(f,m,b){let _=f;if(f&&!b&&typeof f=="object"){if(y.endsWith(m,"{}"))m=r?m:m.slice(0,-2),f=JSON.stringify(f);else if(y.isArray(f)&&Up(f)||(y.isFileList(f)||y.endsWith(m,"[]"))&&(_=y.toArray(f)))return m=Ha(m),_.forEach(function($,x){!(y.isUndefined($)||$===null)&&e.append(n===!0?Ni([m],x,i):n===null?m:m+"[]",l($))}),!1}return ro(f)?!0:(e.append(Ni(b,m,i),l(f)),!1)}const c=[],h=Object.assign(Bp,{defaultVisitor:d,convertValue:l,isVisitable:ro});function g(f,m){if(!y.isUndefined(f)){if(c.indexOf(f)!==-1)throw Error("Circular reference detected in "+m.join("."));c.push(f),y.forEach(f,function(b,_){(!(y.isUndefined(b)||b===null)&&o.call(e,b,y.isString(_)?_.trim():_,m,h))===!0&&g(b,m?m.concat(_):[_])}),c.pop()}}if(!y.isObject(s))throw new TypeError("data must be an object");return g(s),e}function Ri(s){const e={"!":"%21","'":"%27","(":"%28",")":"%29","~":"%7E","%20":"+","%00":"\0"};return encodeURIComponent(s).replace(/[!'()~]|%20|%00/g,function(t){return e[t]})}function Io(s,e){this._pairs=[],s&&pr(s,this,e)}const Wa=Io.prototype;Wa.append=function(s,e){this._pairs.push([s,e])};Wa.toString=function(s){const e=s?function(t){return s.call(this,t,Ri)}:Ri;return this._pairs.map(function(t){return e(t[0])+"="+e(t[1])},"").join("&")};function jp(s){return encodeURIComponent(s).replace(/%3A/gi,":").replace(/%24/g,"$").replace(/%2C/gi,",").replace(/%20/g,"+")}function Ka(s,e,t){if(!e)return s;const r=t&&t.encode||jp;y.isFunction(t)&&(t={serialize:t});const o=t&&t.serialize;let i;if(o?i=o(e,t):i=y.isURLSearchParams(e)?e.toString():new Io(e,t).toString(r),i){const n=s.indexOf("#");n!==-1&&(s=s.slice(0,n)),s+=(s.indexOf("?")===-1?"?":"&")+i}return s}class Pi{constructor(){this.handlers=[]}use(e,t,r){return this.handlers.push({fulfilled:e,rejected:t,synchronous:r?r.synchronous:!1,runWhen:r?r.runWhen:null}),this.handlers.length-1}eject(e){this.handlers[e]&&(this.handlers[e]=null)}clear(){this.handlers&&(this.handlers=[])}forEach(e){y.forEach(this.handlers,function(t){t!==null&&e(t)})}}const Ga={silentJSONParsing:!0,forcedJSONParsing:!0,clarifyTimeoutError:!1},qp=typeof URLSearchParams<"u"?URLSearchParams:Io,Hp=typeof FormData<"u"?FormData:null,Wp=typeof Blob<"u"?Blob:null,Kp={isBrowser:!0,classes:{URLSearchParams:qp,FormData:Hp,Blob:Wp},protocols:["http","https","file","blob","url","data"]},Uo=typeof window<"u"&&typeof document<"u",oo=typeof navigator=="object"&&navigator||void 0,Gp=Uo&&(!oo||["ReactNative","NativeScript","NS"].indexOf(oo.product)<0),Jp=typeof WorkerGlobalScope<"u"&&self instanceof WorkerGlobalScope&&typeof self.importScripts=="function",Yp=Uo&&window.location.href||"http://localhost",Xp=Object.freeze(Object.defineProperty({__proto__:null,hasBrowserEnv:Uo,hasStandardBrowserEnv:Gp,hasStandardBrowserWebWorkerEnv:Jp,navigator:oo,origin:Yp},Symbol.toStringTag,{value:"Module"})),ce={...Xp,...Kp};function Zp(s,e){return pr(s,new ce.classes.URLSearchParams,{visitor:function(t,r,o,i){return ce.isNode&&y.isBuffer(t)?(this.append(r,t.toString("base64")),!1):i.defaultVisitor.apply(this,arguments)},...e})}function Qp(s){return y.matchAll(/\w+|\[(\w*)]/g,s).map(e=>e[0]==="[]"?"":e[1]||e[0])}function em(s){const e={},t=Object.keys(s);let r;const o=t.length;let i;for(r=0;r<o;r++)i=t[r],e[i]=s[i];return e}function Ja(s){function e(t,r,o,i){let n=t[i++];if(n==="__proto__")return!0;const a=Number.isFinite(+n),l=i>=t.length;return n=!n&&y.isArray(o)?o.length:n,l?(y.hasOwnProp(o,n)?o[n]=[o[n],r]:o[n]=r,!a):((!o[n]||!y.isObject(o[n]))&&(o[n]=[]),e(t,r,o[n],i)&&y.isArray(o[n])&&(o[n]=em(o[n])),!a)}if(y.isFormData(s)&&y.isFunction(s.entries)){const t={};return y.forEachEntry(s,(r,o)=>{e(Qp(r),o,t,0)}),t}return null}function tm(s,e,t){if(y.isString(s))try{return(e||JSON.parse)(s),y.trim(s)}catch(r){if(r.name!=="SyntaxError")throw r}return(t||JSON.stringify)(s)}const vs={transitional:Ga,adapter:["xhr","http","fetch"],transformRequest:[function(s,e){const t=e.getContentType()||"",r=t.indexOf("application/json")>-1,o=y.isObject(s);if(o&&y.isHTMLForm(s)&&(s=new FormData(s)),y.isFormData(s))return r?JSON.stringify(Ja(s)):s;if(y.isArrayBuffer(s)||y.isBuffer(s)||y.isStream(s)||y.isFile(s)||y.isBlob(s)||y.isReadableStream(s))return s;if(y.isArrayBufferView(s))return s.buffer;if(y.isURLSearchParams(s))return e.setContentType("application/x-www-form-urlencoded;charset=utf-8",!1),s.toString();let i;if(o){if(t.indexOf("application/x-www-form-urlencoded")>-1)return Zp(s,this.formSerializer).toString();if((i=y.isFileList(s))||t.indexOf("multipart/form-data")>-1){const n=this.env&&this.env.FormData;return pr(i?{"files[]":s}:s,n&&new n,this.formSerializer)}}return o||r?(e.setContentType("application/json",!1),tm(s)):s}],transformResponse:[function(s){const e=this.transitional||vs.transitional,t=e&&e.forcedJSONParsing,r=this.responseType==="json";if(y.isResponse(s)||y.isReadableStream(s))return s;if(s&&y.isString(s)&&(t&&!this.responseType||r)){const o=!(e&&e.silentJSONParsing)&&r;try{return JSON.parse(s,this.parseReviver)}catch(i){if(o)throw i.name==="SyntaxError"?F.from(i,F.ERR_BAD_RESPONSE,this,null,this.response):i}}return s}],timeout:0,xsrfCookieName:"XSRF-TOKEN",xsrfHeaderName:"X-XSRF-TOKEN",maxContentLength:-1,maxBodyLength:-1,env:{FormData:ce.classes.FormData,Blob:ce.classes.Blob},validateStatus:function(s){return s>=200&&s<300},headers:{common:{Accept:"application/json, text/plain, */*","Content-Type":void 0}}};y.forEach(["delete","get","head","post","put","patch"],s=>{vs.headers[s]={}});const sm=y.toObjectSet(["age","authorization","content-length","content-type","etag","expires","from","host","if-modified-since","if-unmodified-since","last-modified","location","max-forwards","proxy-authorization","referer","retry-after","user-agent"]),rm=s=>{const e={};let t,r,o;return s&&s.split(`
`).forEach(function(i){o=i.indexOf(":"),t=i.substring(0,o).trim().toLowerCase(),r=i.substring(o+1).trim(),!(!t||e[t]&&sm[t])&&(t==="set-cookie"?e[t]?e[t].push(r):e[t]=[r]:e[t]=e[t]?e[t]+", "+r:r)}),e},Fi=Symbol("internals");function Bt(s){return s&&String(s).trim().toLowerCase()}function Rs(s){return s===!1||s==null?s:y.isArray(s)?s.map(Rs):String(s)}function om(s){const e=Object.create(null),t=/([^\s,;=]+)\s*(?:=\s*([^,;]+))?/g;let r;for(;r=t.exec(s);)e[r[1]]=r[2];return e}const im=s=>/^[-_a-zA-Z0-9^`|~,!#$%&'*+.]+$/.test(s.trim());function Fr(s,e,t,r,o){if(y.isFunction(r))return r.call(this,e,t);if(o&&(e=t),!!y.isString(e)){if(y.isString(r))return e.indexOf(r)!==-1;if(y.isRegExp(r))return r.test(e)}}function nm(s){return s.trim().toLowerCase().replace(/([a-z\d])(\w*)/g,(e,t,r)=>t.toUpperCase()+r)}function am(s,e){const t=y.toCamelCase(" "+e);["get","set","has"].forEach(r=>{Object.defineProperty(s,r+t,{value:function(o,i,n){return this[r].call(this,e,o,i,n)},configurable:!0})})}let fe=class{constructor(e){e&&this.set(e)}set(e,t,r){const o=this;function i(a,l,d){const c=Bt(l);if(!c)throw new Error("header name must be a non-empty string");const h=y.findKey(o,c);(!h||o[h]===void 0||d===!0||d===void 0&&o[h]!==!1)&&(o[h||l]=Rs(a))}const n=(a,l)=>y.forEach(a,(d,c)=>i(d,c,l));if(y.isPlainObject(e)||e instanceof this.constructor)n(e,t);else if(y.isString(e)&&(e=e.trim())&&!im(e))n(rm(e),t);else if(y.isObject(e)&&y.isIterable(e)){let a={},l,d;for(const c of e){if(!y.isArray(c))throw TypeError("Object iterator must return a key-value pair");a[d=c[0]]=(l=a[d])?y.isArray(l)?[...l,c[1]]:[l,c[1]]:c[1]}n(a,t)}else e!=null&&i(t,e,r);return this}get(e,t){if(e=Bt(e),e){const r=y.findKey(this,e);if(r){const o=this[r];if(!t)return o;if(t===!0)return om(o);if(y.isFunction(t))return t.call(this,o,r);if(y.isRegExp(t))return t.exec(o);throw new TypeError("parser must be boolean|regexp|function")}}}has(e,t){if(e=Bt(e),e){const r=y.findKey(this,e);return!!(r&&this[r]!==void 0&&(!t||Fr(this,this[r],r,t)))}return!1}delete(e,t){const r=this;let o=!1;function i(n){if(n=Bt(n),n){const a=y.findKey(r,n);a&&(!t||Fr(r,r[a],a,t))&&(delete r[a],o=!0)}}return y.isArray(e)?e.forEach(i):i(e),o}clear(e){const t=Object.keys(this);let r=t.length,o=!1;for(;r--;){const i=t[r];(!e||Fr(this,this[i],i,e,!0))&&(delete this[i],o=!0)}return o}normalize(e){const t=this,r={};return y.forEach(this,(o,i)=>{const n=y.findKey(r,i);if(n){t[n]=Rs(o),delete t[i];return}const a=e?nm(i):String(i).trim();a!==i&&delete t[i],t[a]=Rs(o),r[a]=!0}),this}concat(...e){return this.constructor.concat(this,...e)}toJSON(e){const t=Object.create(null);return y.forEach(this,(r,o)=>{r!=null&&r!==!1&&(t[o]=e&&y.isArray(r)?r.join(", "):r)}),t}[Symbol.iterator](){return Object.entries(this.toJSON())[Symbol.iterator]()}toString(){return Object.entries(this.toJSON()).map(([e,t])=>e+": "+t).join(`
`)}getSetCookie(){return this.get("set-cookie")||[]}get[Symbol.toStringTag](){return"AxiosHeaders"}static from(e){return e instanceof this?e:new this(e)}static concat(e,...t){const r=new this(e);return t.forEach(o=>r.set(o)),r}static accessor(e){const t=(this[Fi]=this[Fi]={accessors:{}}).accessors,r=this.prototype;function o(i){const n=Bt(i);t[n]||(am(r,i),t[n]=!0)}return y.isArray(e)?e.forEach(o):o(e),this}};fe.accessor(["Content-Type","Content-Length","Accept","Accept-Encoding","User-Agent","Authorization"]);y.reduceDescriptors(fe.prototype,({value:s},e)=>{let t=e[0].toUpperCase()+e.slice(1);return{get:()=>s,set(r){this[t]=r}}});y.freezeMethods(fe);function Mr(s,e){const t=this||vs,r=e||t,o=fe.from(r.headers);let i=r.data;return y.forEach(s,function(n){i=n.call(t,i,o.normalize(),e?e.status:void 0)}),o.normalize(),i}function Ya(s){return!!(s&&s.__CANCEL__)}function zt(s,e,t){F.call(this,s??"canceled",F.ERR_CANCELED,e,t),this.name="CanceledError"}y.inherits(zt,F,{__CANCEL__:!0});function Xa(s,e,t){const r=t.config.validateStatus;!t.status||!r||r(t.status)?s(t):e(new F("Request failed with status code "+t.status,[F.ERR_BAD_REQUEST,F.ERR_BAD_RESPONSE][Math.floor(t.status/100)-4],t.config,t.request,t))}function lm(s){const e=/^([-+\w]{1,25})(:?\/\/|:)/.exec(s);return e&&e[1]||""}function cm(s,e){s=s||10;const t=new Array(s),r=new Array(s);let o=0,i=0,n;return e=e!==void 0?e:1e3,function(a){const l=Date.now(),d=r[i];n||(n=l),t[o]=a,r[o]=l;let c=i,h=0;for(;c!==o;)h+=t[c++],c=c%s;if(o=(o+1)%s,o===i&&(i=(i+1)%s),l-n<e)return;const g=d&&l-d;return g?Math.round(h*1e3/g):void 0}}function dm(s,e){let t=0,r=1e3/e,o,i;const n=(a,l=Date.now())=>{t=l,o=null,i&&(clearTimeout(i),i=null),s(...a)};return[(...a)=>{const l=Date.now(),d=l-t;d>=r?n(a,l):(o=a,i||(i=setTimeout(()=>{i=null,n(o)},r-d)))},()=>o&&n(o)]}const Ks=(s,e,t=3)=>{let r=0;const o=cm(50,250);return dm(i=>{const n=i.loaded,a=i.lengthComputable?i.total:void 0,l=n-r,d=o(l),c=n<=a;r=n;const h={loaded:n,total:a,progress:a?n/a:void 0,bytes:l,rate:d||void 0,estimated:d&&a&&c?(a-n)/d:void 0,event:i,lengthComputable:a!=null,[e?"download":"upload"]:!0};s(h)},t)},Mi=(s,e)=>{const t=s!=null;return[r=>e[0]({lengthComputable:t,total:s,loaded:r}),e[1]]},Di=s=>(...e)=>y.asap(()=>s(...e)),um=ce.hasStandardBrowserEnv?((s,e)=>t=>(t=new URL(t,ce.origin),s.protocol===t.protocol&&s.host===t.host&&(e||s.port===t.port)))(new URL(ce.origin),ce.navigator&&/(msie|trident)/i.test(ce.navigator.userAgent)):()=>!0,hm=ce.hasStandardBrowserEnv?{write(s,e,t,r,o,i,n){if(typeof document>"u")return;const a=[`${s}=${encodeURIComponent(e)}`];y.isNumber(t)&&a.push(`expires=${new Date(t).toUTCString()}`),y.isString(r)&&a.push(`path=${r}`),y.isString(o)&&a.push(`domain=${o}`),i===!0&&a.push("secure"),y.isString(n)&&a.push(`SameSite=${n}`),document.cookie=a.join("; ")},read(s){if(typeof document>"u")return null;const e=document.cookie.match(new RegExp("(?:^|; )"+s+"=([^;]*)"));return e?decodeURIComponent(e[1]):null},remove(s){this.write(s,"",Date.now()-864e5,"/")}}:{write(){},read(){return null},remove(){}};function pm(s){return/^([a-z][a-z\d+\-.]*:)?\/\//i.test(s)}function mm(s,e){return e?s.replace(/\/?\/$/,"")+"/"+e.replace(/^\/+/,""):s}function Za(s,e,t){let r=!pm(e);return s&&(r||t==!1)?mm(s,e):e}const Vi=s=>s instanceof fe?{...s}:s;function mt(s,e){e=e||{};const t={};function r(d,c,h,g){return y.isPlainObject(d)&&y.isPlainObject(c)?y.merge.call({caseless:g},d,c):y.isPlainObject(c)?y.merge({},c):y.isArray(c)?c.slice():c}function o(d,c,h,g){if(y.isUndefined(c)){if(!y.isUndefined(d))return r(void 0,d,h,g)}else return r(d,c,h,g)}function i(d,c){if(!y.isUndefined(c))return r(void 0,c)}function n(d,c){if(y.isUndefined(c)){if(!y.isUndefined(d))return r(void 0,d)}else return r(void 0,c)}function a(d,c,h){if(h in e)return r(d,c);if(h in s)return r(void 0,d)}const l={url:i,method:i,data:i,baseURL:n,transformRequest:n,transformResponse:n,paramsSerializer:n,timeout:n,timeoutMessage:n,withCredentials:n,withXSRFToken:n,adapter:n,responseType:n,xsrfCookieName:n,xsrfHeaderName:n,onUploadProgress:n,onDownloadProgress:n,decompress:n,maxContentLength:n,maxBodyLength:n,beforeRedirect:n,transport:n,httpAgent:n,httpsAgent:n,cancelToken:n,socketPath:n,responseEncoding:n,validateStatus:a,headers:(d,c,h)=>o(Vi(d),Vi(c),h,!0)};return y.forEach(Object.keys({...s,...e}),function(d){const c=l[d]||o,h=c(s[d],e[d],d);y.isUndefined(h)&&c!==a||(t[d]=h)}),t}const Qa=s=>{const e=mt({},s);let{data:t,withXSRFToken:r,xsrfHeaderName:o,xsrfCookieName:i,headers:n,auth:a}=e;if(e.headers=n=fe.from(n),e.url=Ka(Za(e.baseURL,e.url,e.allowAbsoluteUrls),s.params,s.paramsSerializer),a&&n.set("Authorization","Basic "+btoa((a.username||"")+":"+(a.password?unescape(encodeURIComponent(a.password)):""))),y.isFormData(t)){if(ce.hasStandardBrowserEnv||ce.hasStandardBrowserWebWorkerEnv)n.setContentType(void 0);else if(y.isFunction(t.getHeaders)){const l=t.getHeaders(),d=["content-type","content-length"];Object.entries(l).forEach(([c,h])=>{d.includes(c.toLowerCase())&&n.set(c,h)})}}if(ce.hasStandardBrowserEnv&&(r&&y.isFunction(r)&&(r=r(e)),r||r!==!1&&um(e.url))){const l=o&&i&&hm.read(i);l&&n.set(o,l)}return e},fm=typeof XMLHttpRequest<"u",gm=fm&&function(s){return new Promise(function(e,t){const r=Qa(s);let o=r.data;const i=fe.from(r.headers).normalize();let{responseType:n,onUploadProgress:a,onDownloadProgress:l}=r,d,c,h,g,f;function m(){g&&g(),f&&f(),r.cancelToken&&r.cancelToken.unsubscribe(d),r.signal&&r.signal.removeEventListener("abort",d)}let b=new XMLHttpRequest;b.open(r.method.toUpperCase(),r.url,!0),b.timeout=r.timeout;function _(){if(!b)return;const x=fe.from("getAllResponseHeaders"in b&&b.getAllResponseHeaders()),A={data:!n||n==="text"||n==="json"?b.responseText:b.response,status:b.status,statusText:b.statusText,headers:x,config:s,request:b};Xa(function(S){e(S),m()},function(S){t(S),m()},A),b=null}"onloadend"in b?b.onloadend=_:b.onreadystatechange=function(){!b||b.readyState!==4||b.status===0&&!(b.responseURL&&b.responseURL.indexOf("file:")===0)||setTimeout(_)},b.onabort=function(){b&&(t(new F("Request aborted",F.ECONNABORTED,s,b)),b=null)},b.onerror=function(x){const A=x&&x.message?x.message:"Network Error",S=new F(A,F.ERR_NETWORK,s,b);S.event=x||null,t(S),b=null},b.ontimeout=function(){let x=r.timeout?"timeout of "+r.timeout+"ms exceeded":"timeout exceeded";const A=r.transitional||Ga;r.timeoutErrorMessage&&(x=r.timeoutErrorMessage),t(new F(x,A.clarifyTimeoutError?F.ETIMEDOUT:F.ECONNABORTED,s,b)),b=null},o===void 0&&i.setContentType(null),"setRequestHeader"in b&&y.forEach(i.toJSON(),function(x,A){b.setRequestHeader(A,x)}),y.isUndefined(r.withCredentials)||(b.withCredentials=!!r.withCredentials),n&&n!=="json"&&(b.responseType=r.responseType),l&&([h,f]=Ks(l,!0),b.addEventListener("progress",h)),a&&b.upload&&([c,g]=Ks(a),b.upload.addEventListener("progress",c),b.upload.addEventListener("loadend",g)),(r.cancelToken||r.signal)&&(d=x=>{b&&(t(!x||x.type?new zt(null,s,b):x),b.abort(),b=null)},r.cancelToken&&r.cancelToken.subscribe(d),r.signal&&(r.signal.aborted?d():r.signal.addEventListener("abort",d)));const $=lm(r.url);if($&&ce.protocols.indexOf($)===-1){t(new F("Unsupported protocol "+$+":",F.ERR_BAD_REQUEST,s));return}b.send(o||null)})},bm=(s,e)=>{const{length:t}=s=s?s.filter(Boolean):[];if(e||t){let r=new AbortController,o;const i=function(d){if(!o){o=!0,a();const c=d instanceof Error?d:this.reason;r.abort(c instanceof F?c:new zt(c instanceof Error?c.message:c))}};let n=e&&setTimeout(()=>{n=null,i(new F(`timeout ${e} of ms exceeded`,F.ETIMEDOUT))},e);const a=()=>{s&&(n&&clearTimeout(n),n=null,s.forEach(d=>{d.unsubscribe?d.unsubscribe(i):d.removeEventListener("abort",i)}),s=null)};s.forEach(d=>d.addEventListener("abort",i));const{signal:l}=r;return l.unsubscribe=()=>y.asap(a),l}},vm=function*(s,e){let t=s.byteLength;if(t<e){yield s;return}let r=0,o;for(;r<t;)o=r+e,yield s.slice(r,o),r=o},ym=async function*(s,e){for await(const t of wm(s))yield*vm(t,e)},wm=async function*(s){if(s[Symbol.asyncIterator]){yield*s;return}const e=s.getReader();try{for(;;){const{done:t,value:r}=await e.read();if(t)break;yield r}}finally{await e.cancel()}},zi=(s,e,t,r)=>{const o=ym(s,e);let i=0,n,a=l=>{n||(n=!0,r&&r(l))};return new ReadableStream({async pull(l){try{const{done:d,value:c}=await o.next();if(d){a(),l.close();return}let h=c.byteLength;if(t){let g=i+=h;t(g)}l.enqueue(new Uint8Array(c))}catch(d){throw a(d),d}},cancel(l){return a(l),o.return()}},{highWaterMark:2})},Ii=64*1024,{isFunction:Cs}=y,_m=(({Request:s,Response:e})=>({Request:s,Response:e}))(y.global),{ReadableStream:Ui,TextEncoder:Bi}=y.global,ji=(s,...e)=>{try{return!!s(...e)}catch{return!1}},$m=s=>{s=y.merge.call({skipUndefined:!0},_m,s);const{fetch:e,Request:t,Response:r}=s,o=e?Cs(e):typeof fetch=="function",i=Cs(t),n=Cs(r);if(!o)return!1;const a=o&&Cs(Ui),l=o&&(typeof Bi=="function"?(m=>b=>m.encode(b))(new Bi):async m=>new Uint8Array(await new t(m).arrayBuffer())),d=i&&a&&ji(()=>{let m=!1;const b=new t(ce.origin,{body:new Ui,method:"POST",get duplex(){return m=!0,"half"}}).headers.has("Content-Type");return m&&!b}),c=n&&a&&ji(()=>y.isReadableStream(new r("").body)),h={stream:c&&(m=>m.body)};o&&["text","arrayBuffer","blob","formData","stream"].forEach(m=>{!h[m]&&(h[m]=(b,_)=>{let $=b&&b[m];if($)return $.call(b);throw new F(`Response type '${m}' is not supported`,F.ERR_NOT_SUPPORT,_)})});const g=async m=>{if(m==null)return 0;if(y.isBlob(m))return m.size;if(y.isSpecCompliantForm(m))return(await new t(ce.origin,{method:"POST",body:m}).arrayBuffer()).byteLength;if(y.isArrayBufferView(m)||y.isArrayBuffer(m))return m.byteLength;if(y.isURLSearchParams(m)&&(m=m+""),y.isString(m))return(await l(m)).byteLength},f=async(m,b)=>y.toFiniteNumber(m.getContentLength())??g(b);return async m=>{let{url:b,method:_,data:$,signal:x,cancelToken:A,timeout:S,onDownloadProgress:B,onUploadProgress:z,responseType:I,headers:X,withCredentials:U="same-origin",fetchOptions:M}=Qa(m),ve=e||fetch;I=I?(I+"").toLowerCase():"text";let se=bm([x,A&&A.toAbortSignal()],S),u=null;const k=se&&se.unsubscribe&&(()=>{se.unsubscribe()});let T;try{if(z&&d&&_!=="get"&&_!=="head"&&(T=await f(X,$))!==0){let j=new t(b,{method:"POST",body:$,duplex:"half"}),De;if(y.isFormData($)&&(De=j.headers.get("content-type"))&&X.setContentType(De),j.body){const[vr,_s]=Mi(T,Ks(Di(z)));$=zi(j.body,Ii,vr,_s)}}y.isString(U)||(U=U?"include":"omit");const L=i&&"credentials"in t.prototype,N={...M,signal:se,method:_.toUpperCase(),headers:X.normalize().toJSON(),body:$,duplex:"half",credentials:L?U:void 0};u=i&&new t(b,N);let C=await(i?ve(u,M):ve(b,N));const H=c&&(I==="stream"||I==="response");if(c&&(B||H&&k)){const j={};["status","statusText","headers"].forEach(Jo=>{j[Jo]=C[Jo]});const De=y.toFiniteNumber(C.headers.get("content-length")),[vr,_s]=B&&Mi(De,Ks(Di(B),!0))||[];C=new r(zi(C.body,Ii,vr,()=>{_s&&_s(),k&&k()}),j)}I=I||"text";let pe=await h[y.findKey(h,I)||"text"](C,m);return!H&&k&&k(),await new Promise((j,De)=>{Xa(j,De,{data:pe,headers:fe.from(C.headers),status:C.status,statusText:C.statusText,config:m,request:u})})}catch(L){throw k&&k(),L&&L.name==="TypeError"&&/Load failed|fetch/i.test(L.message)?Object.assign(new F("Network Error",F.ERR_NETWORK,m,u),{cause:L.cause||L}):F.from(L,L&&L.code,m,u)}}},Em=new Map,el=s=>{let e=s&&s.env||{};const{fetch:t,Request:r,Response:o}=e,i=[r,o,t];let n=i.length,a=n,l,d,c=Em;for(;a--;)l=i[a],d=c.get(l),d===void 0&&c.set(l,d=a?new Map:$m(e)),c=d;return d};el();const Bo={http:Ip,xhr:gm,fetch:{get:el}};y.forEach(Bo,(s,e)=>{if(s){try{Object.defineProperty(s,"name",{value:e})}catch{}Object.defineProperty(s,"adapterName",{value:e})}});const qi=s=>`- ${s}`,xm=s=>y.isFunction(s)||s===null||s===!1;function km(s,e){s=y.isArray(s)?s:[s];const{length:t}=s;let r,o;const i={};for(let n=0;n<t;n++){r=s[n];let a;if(o=r,!xm(r)&&(o=Bo[(a=String(r)).toLowerCase()],o===void 0))throw new F(`Unknown adapter '${a}'`);if(o&&(y.isFunction(o)||(o=o.get(e))))break;i[a||"#"+n]=o}if(!o){const n=Object.entries(i).map(([l,d])=>`adapter ${l} `+(d===!1?"is not supported by the environment":"is not available in the build"));let a=t?n.length>1?`since :
`+n.map(qi).join(`
`):" "+qi(n[0]):"as no adapter specified";throw new F("There is no suitable adapter to dispatch the request "+a,"ERR_NOT_SUPPORT")}return o}const tl={getAdapter:km,adapters:Bo};function Dr(s){if(s.cancelToken&&s.cancelToken.throwIfRequested(),s.signal&&s.signal.aborted)throw new zt(null,s)}function Hi(s){return Dr(s),s.headers=fe.from(s.headers),s.data=Mr.call(s,s.transformRequest),["post","put","patch"].indexOf(s.method)!==-1&&s.headers.setContentType("application/x-www-form-urlencoded",!1),tl.getAdapter(s.adapter||vs.adapter,s)(s).then(function(e){return Dr(s),e.data=Mr.call(s,s.transformResponse,e),e.headers=fe.from(e.headers),e},function(e){return Ya(e)||(Dr(s),e&&e.response&&(e.response.data=Mr.call(s,s.transformResponse,e.response),e.response.headers=fe.from(e.response.headers))),Promise.reject(e)})}const sl="1.13.2",mr={};["object","boolean","number","function","string","symbol"].forEach((s,e)=>{mr[s]=function(t){return typeof t===s||"a"+(e<1?"n ":" ")+s}});const Wi={};mr.transitional=function(s,e,t){function r(o,i){return"[Axios v"+sl+"] Transitional option '"+o+"'"+i+(t?". "+t:"")}return(o,i,n)=>{if(s===!1)throw new F(r(i," has been removed"+(e?" in "+e:"")),F.ERR_DEPRECATED);return e&&!Wi[i]&&(Wi[i]=!0,console.warn(r(i," has been deprecated since v"+e+" and will be removed in the near future"))),s?s(o,i,n):!0}};mr.spelling=function(s){return(e,t)=>(console.warn(`${t} is likely a misspelling of ${s}`),!0)};function Cm(s,e,t){if(typeof s!="object")throw new F("options must be an object",F.ERR_BAD_OPTION_VALUE);const r=Object.keys(s);let o=r.length;for(;o-- >0;){const i=r[o],n=e[i];if(n){const a=s[i],l=a===void 0||n(a,i,s);if(l!==!0)throw new F("option "+i+" must be "+l,F.ERR_BAD_OPTION_VALUE);continue}if(t!==!0)throw new F("Unknown option "+i,F.ERR_BAD_OPTION)}}const Ps={assertOptions:Cm,validators:mr},Oe=Ps.validators;let dt=class{constructor(e){this.defaults=e||{},this.interceptors={request:new Pi,response:new Pi}}async request(e,t){try{return await this._request(e,t)}catch(r){if(r instanceof Error){let o={};Error.captureStackTrace?Error.captureStackTrace(o):o=new Error;const i=o.stack?o.stack.replace(/^.+\n/,""):"";try{r.stack?i&&!String(r.stack).endsWith(i.replace(/^.+\n.+\n/,""))&&(r.stack+=`
`+i):r.stack=i}catch{}}throw r}}_request(e,t){typeof e=="string"?(t=t||{},t.url=e):t=e||{},t=mt(this.defaults,t);const{transitional:r,paramsSerializer:o,headers:i}=t;r!==void 0&&Ps.assertOptions(r,{silentJSONParsing:Oe.transitional(Oe.boolean),forcedJSONParsing:Oe.transitional(Oe.boolean),clarifyTimeoutError:Oe.transitional(Oe.boolean)},!1),o!=null&&(y.isFunction(o)?t.paramsSerializer={serialize:o}:Ps.assertOptions(o,{encode:Oe.function,serialize:Oe.function},!0)),t.allowAbsoluteUrls!==void 0||(this.defaults.allowAbsoluteUrls!==void 0?t.allowAbsoluteUrls=this.defaults.allowAbsoluteUrls:t.allowAbsoluteUrls=!0),Ps.assertOptions(t,{baseUrl:Oe.spelling("baseURL"),withXsrfToken:Oe.spelling("withXSRFToken")},!0),t.method=(t.method||this.defaults.method||"get").toLowerCase();let n=i&&y.merge(i.common,i[t.method]);i&&y.forEach(["delete","get","head","post","put","patch","common"],m=>{delete i[m]}),t.headers=fe.concat(n,i);const a=[];let l=!0;this.interceptors.request.forEach(function(m){typeof m.runWhen=="function"&&m.runWhen(t)===!1||(l=l&&m.synchronous,a.unshift(m.fulfilled,m.rejected))});const d=[];this.interceptors.response.forEach(function(m){d.push(m.fulfilled,m.rejected)});let c,h=0,g;if(!l){const m=[Hi.bind(this),void 0];for(m.unshift(...a),m.push(...d),g=m.length,c=Promise.resolve(t);h<g;)c=c.then(m[h++],m[h++]);return c}g=a.length;let f=t;for(;h<g;){const m=a[h++],b=a[h++];try{f=m(f)}catch(_){b.call(this,_);break}}try{c=Hi.call(this,f)}catch(m){return Promise.reject(m)}for(h=0,g=d.length;h<g;)c=c.then(d[h++],d[h++]);return c}getUri(e){e=mt(this.defaults,e);const t=Za(e.baseURL,e.url,e.allowAbsoluteUrls);return Ka(t,e.params,e.paramsSerializer)}};y.forEach(["delete","get","head","options"],function(s){dt.prototype[s]=function(e,t){return this.request(mt(t||{},{method:s,url:e,data:(t||{}).data}))}});y.forEach(["post","put","patch"],function(s){function e(t){return function(r,o,i){return this.request(mt(i||{},{method:s,headers:t?{"Content-Type":"multipart/form-data"}:{},url:r,data:o}))}}dt.prototype[s]=e(),dt.prototype[s+"Form"]=e(!0)});let Sm=class rl{constructor(e){if(typeof e!="function")throw new TypeError("executor must be a function.");let t;this.promise=new Promise(function(o){t=o});const r=this;this.promise.then(o=>{if(!r._listeners)return;let i=r._listeners.length;for(;i-- >0;)r._listeners[i](o);r._listeners=null}),this.promise.then=o=>{let i;const n=new Promise(a=>{r.subscribe(a),i=a}).then(o);return n.cancel=function(){r.unsubscribe(i)},n},e(function(o,i,n){r.reason||(r.reason=new zt(o,i,n),t(r.reason))})}throwIfRequested(){if(this.reason)throw this.reason}subscribe(e){if(this.reason){e(this.reason);return}this._listeners?this._listeners.push(e):this._listeners=[e]}unsubscribe(e){if(!this._listeners)return;const t=this._listeners.indexOf(e);t!==-1&&this._listeners.splice(t,1)}toAbortSignal(){const e=new AbortController,t=r=>{e.abort(r)};return this.subscribe(t),e.signal.unsubscribe=()=>this.unsubscribe(t),e.signal}static source(){let e;return{token:new rl(function(t){e=t}),cancel:e}}};function Am(s){return function(e){return s.apply(null,e)}}function Tm(s){return y.isObject(s)&&s.isAxiosError===!0}const io={Continue:100,SwitchingProtocols:101,Processing:102,EarlyHints:103,Ok:200,Created:201,Accepted:202,NonAuthoritativeInformation:203,NoContent:204,ResetContent:205,PartialContent:206,MultiStatus:207,AlreadyReported:208,ImUsed:226,MultipleChoices:300,MovedPermanently:301,Found:302,SeeOther:303,NotModified:304,UseProxy:305,Unused:306,TemporaryRedirect:307,PermanentRedirect:308,BadRequest:400,Unauthorized:401,PaymentRequired:402,Forbidden:403,NotFound:404,MethodNotAllowed:405,NotAcceptable:406,ProxyAuthenticationRequired:407,RequestTimeout:408,Conflict:409,Gone:410,LengthRequired:411,PreconditionFailed:412,PayloadTooLarge:413,UriTooLong:414,UnsupportedMediaType:415,RangeNotSatisfiable:416,ExpectationFailed:417,ImATeapot:418,MisdirectedRequest:421,UnprocessableEntity:422,Locked:423,FailedDependency:424,TooEarly:425,UpgradeRequired:426,PreconditionRequired:428,TooManyRequests:429,RequestHeaderFieldsTooLarge:431,UnavailableForLegalReasons:451,InternalServerError:500,NotImplemented:501,BadGateway:502,ServiceUnavailable:503,GatewayTimeout:504,HttpVersionNotSupported:505,VariantAlsoNegotiates:506,InsufficientStorage:507,LoopDetected:508,NotExtended:510,NetworkAuthenticationRequired:511,WebServerIsDown:521,ConnectionTimedOut:522,OriginIsUnreachable:523,TimeoutOccurred:524,SslHandshakeFailed:525,InvalidSslCertificate:526};Object.entries(io).forEach(([s,e])=>{io[e]=s});function ol(s){const e=new dt(s),t=Fa(dt.prototype.request,e);return y.extend(t,dt.prototype,e,{allOwnKeys:!0}),y.extend(t,e,null,{allOwnKeys:!0}),t.create=function(r){return ol(mt(s,r))},t}const Q=ol(vs);Q.Axios=dt;Q.CanceledError=zt;Q.CancelToken=Sm;Q.isCancel=Ya;Q.VERSION=sl;Q.toFormData=pr;Q.AxiosError=F;Q.Cancel=Q.CanceledError;Q.all=function(s){return Promise.all(s)};Q.spread=Am;Q.isAxiosError=Tm;Q.mergeConfig=mt;Q.AxiosHeaders=fe;Q.formToJSON=s=>Ja(y.isHTMLForm(s)?new FormData(s):s);Q.getAdapter=tl.getAdapter;Q.HttpStatusCode=io;Q.default=Q;const{Axios:vb,AxiosError:yb,CanceledError:wb,isCancel:_b,CancelToken:$b,VERSION:Eb,all:xb,Cancel:kb,isAxiosError:Cb,spread:Sb,toFormData:Ab,AxiosHeaders:Tb,HttpStatusCode:Ob,formToJSON:Lb,getAdapter:Nb,mergeConfig:Rb}=Q;class Om{constructor(){this.refreshPromise=null,this.tokenName=null,this.tokenValue=null,this.refreshPromise=null}async getToken(){return this.tokenValue||await this.refreshToken(),this.tokenValue}async refreshToken(){return this.refreshPromise?this.refreshPromise:(this.refreshPromise=ys.get("users/session-info").then(({data:e})=>{const{csrfTokenName:t,csrfTokenValue:r}=e;return this.tokenName=t??null,this.tokenValue=r??null,this.tokenValue}).finally(()=>{this.refreshPromise=null}),this.refreshPromise)}clearToken(){this.tokenValue=null}}function Lm(s=""){return`https://craft6-dev.ddev.site/admin/actions/${s}`}function Nm(){let s={"X-Registered-Asset-Bundles":[...new Set(Craft.registeredAssetBundles)].join(","),"X-Registered-Js-Files":[...new Set(Craft.registeredJsFiles)].join(",")};return Craft.csrfTokenValue&&(s["X-CSRF-Token"]=Craft.csrfTokenValue),s}const ys=Q.create({baseURL:Lm()}),no=new Om;ys.interceptors.request.use(async s=>{s.headers.set("X-Requested-With","XMLHttpRequest");const e=Nm();if(Object.entries(e).forEach(([t,r])=>{s.headers.set(t,r)}),["post","put","patch","delete"].includes(s.method?.toLowerCase()||"")&&!s.url?.includes("users/session-info")){const t=await no.getToken();t&&s.headers.set("X-CSRF-Token",t)}return s});ys.interceptors.response.use(s=>s,async s=>{const e=s.config;if(s.response?.status===419||s.response?.status===403&&!e._retry){e._retry=!0;try{return no.clearToken(),e.headers["X-CSRF-Token"]=await no.refreshToken(),Q(e)}catch(t){return console.error("Failed to refresh CSRF token:",t),Promise.reject(t)}}return Promise.reject(s)});let Fs=!1,ut=null;async function Rm(s){if(!Fs){if(ut)return ut;Fs=!0;try{return(await ys.post("app/api-headers",void 0,{cancelToken:s})).data}catch{}finally{Fs=!1}}}const jo=Q.create({baseURL:"https://api.craftcms.com/v1/"});async function Pm(s){return ut?Object.entries(ut).forEach(([e,t])=>{s.headers.set(e,t)}):(s.params=s.params||{},s.params.processCraftHeaders=1),s}async function Fm(s,e){if(ut)return;const{data:t}=await ys.post("app/process-api-response-headers",{headers:s},{cancelToken:e});return ut=t,Fs=!1,ut}async function Mm(s){return await Fm(s.headers,s.config.cancelToken),s}jo.interceptors.request.use(async s=>{const{cancelToken:e}=s,t=await Rm(e);t&&Object.entries(t).forEach(([o,i])=>{s.headers.set(o,i)});const r={...s,params:{...Craft.apiParams||{},...s.params,v:new Date().getTime()}};return t||(r.params.processCraftHeaders=1),Craft.httpProxy&&(r.proxy=Craft.httpProxy),r});jo.interceptors.request.use(Pm);jo.interceptors.response.use(Mm);var Dm=function(s,e,t,r,o){if(typeof e=="function"?s!==e||!0:!e.has(s))throw new TypeError("Cannot write private member to an object whose class did not declare it");return e.set(s,t),t},Ki=function(s,e,t,r){if(typeof e=="function"?s!==e||!r:!e.has(s))throw new TypeError("Cannot read private member from an object whose class did not declare it");return t==="m"?r:t==="a"?r.call(s):r?r.value:e.get(s)},Ht;class Vm{formatToParts(e){const t=[];for(const r of e)t.push({type:"element",value:r}),t.push({type:"literal",value:", "});return t.slice(0,-1)}}const zm=typeof Intl<"u"&&Intl.ListFormat||Vm,Im=[["years","year"],["months","month"],["weeks","week"],["days","day"],["hours","hour"],["minutes","minute"],["seconds","second"],["milliseconds","millisecond"]],Um={minimumIntegerDigits:2};class Bm{constructor(e,t={}){Ht.set(this,void 0);let r=String(t.style||"short");r!=="long"&&r!=="short"&&r!=="narrow"&&r!=="digital"&&(r="short");let o=r==="digital"?"numeric":r;const i=t.hours||o;o=i==="2-digit"?"numeric":i;const n=t.minutes||o;o=n==="2-digit"?"numeric":n;const a=t.seconds||o;o=a==="2-digit"?"numeric":a;const l=t.milliseconds||o;Dm(this,Ht,{locale:e,style:r,years:t.years||r==="digital"?"short":r,yearsDisplay:t.yearsDisplay==="always"?"always":"auto",months:t.months||r==="digital"?"short":r,monthsDisplay:t.monthsDisplay==="always"?"always":"auto",weeks:t.weeks||r==="digital"?"short":r,weeksDisplay:t.weeksDisplay==="always"?"always":"auto",days:t.days||r==="digital"?"short":r,daysDisplay:t.daysDisplay==="always"?"always":"auto",hours:i,hoursDisplay:t.hoursDisplay==="always"||r==="digital"?"always":"auto",minutes:n,minutesDisplay:t.minutesDisplay==="always"||r==="digital"?"always":"auto",seconds:a,secondsDisplay:t.secondsDisplay==="always"||r==="digital"?"always":"auto",milliseconds:l,millisecondsDisplay:t.millisecondsDisplay==="always"?"always":"auto"})}resolvedOptions(){return Ki(this,Ht,"f")}formatToParts(e){const t=[],r=Ki(this,Ht,"f"),o=r.style,i=r.locale;for(const[n,a]of Im){const l=e[n];if(r[`${n}Display`]==="auto"&&!l)continue;const d=r[n],c=d==="2-digit"?Um:d==="numeric"?{}:{style:"unit",unit:a,unitDisplay:d};let h=new Intl.NumberFormat(i,c).format(l);n==="months"&&(d==="narrow"||o==="narrow"&&h.endsWith("m"))&&(h=h.replace(/(\d+)m$/,"$1mo")),t.push(h)}return new zm(i,{type:"unit",style:o==="digital"?"short":o}).formatToParts(t)}format(e){return this.formatToParts(e).map(t=>t.value).join("")}}Ht=new WeakMap;const il=/^[-+]?P(?:(\d+)Y)?(?:(\d+)M)?(?:(\d+)W)?(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?)?$/,Gs=["year","month","week","day","hour","minute","second","millisecond"],jm=s=>il.test(s);let Tt=class ot{constructor(e=0,t=0,r=0,o=0,i=0,n=0,a=0,l=0){this.years=e,this.months=t,this.weeks=r,this.days=o,this.hours=i,this.minutes=n,this.seconds=a,this.milliseconds=l,this.years||(this.years=0),this.sign||(this.sign=Math.sign(this.years)),this.months||(this.months=0),this.sign||(this.sign=Math.sign(this.months)),this.weeks||(this.weeks=0),this.sign||(this.sign=Math.sign(this.weeks)),this.days||(this.days=0),this.sign||(this.sign=Math.sign(this.days)),this.hours||(this.hours=0),this.sign||(this.sign=Math.sign(this.hours)),this.minutes||(this.minutes=0),this.sign||(this.sign=Math.sign(this.minutes)),this.seconds||(this.seconds=0),this.sign||(this.sign=Math.sign(this.seconds)),this.milliseconds||(this.milliseconds=0),this.sign||(this.sign=Math.sign(this.milliseconds)),this.blank=this.sign===0}abs(){return new ot(Math.abs(this.years),Math.abs(this.months),Math.abs(this.weeks),Math.abs(this.days),Math.abs(this.hours),Math.abs(this.minutes),Math.abs(this.seconds),Math.abs(this.milliseconds))}static from(e){var t;if(typeof e=="string"){const r=String(e).trim(),o=r.startsWith("-")?-1:1,i=(t=r.match(il))===null||t===void 0?void 0:t.slice(1).map(n=>(Number(n)||0)*o);return i?new ot(...i):new ot}else if(typeof e=="object"){const{years:r,months:o,weeks:i,days:n,hours:a,minutes:l,seconds:d,milliseconds:c}=e;return new ot(r,o,i,n,a,l,d,c)}throw new RangeError("invalid duration")}static compare(e,t){const r=Date.now(),o=Math.abs(Gi(r,ot.from(e)).getTime()-r),i=Math.abs(Gi(r,ot.from(t)).getTime()-r);return o>i?-1:o<i?1:0}toLocaleString(e,t){return new Bm(e,t).format(this)}};function Gi(s,e){const t=new Date(s);return e.sign<0?(t.setUTCSeconds(t.getUTCSeconds()+e.seconds),t.setUTCMinutes(t.getUTCMinutes()+e.minutes),t.setUTCHours(t.getUTCHours()+e.hours),t.setUTCDate(t.getUTCDate()+e.weeks*7+e.days),t.setUTCMonth(t.getUTCMonth()+e.months),t.setUTCFullYear(t.getUTCFullYear()+e.years)):(t.setUTCFullYear(t.getUTCFullYear()+e.years),t.setUTCMonth(t.getUTCMonth()+e.months),t.setUTCDate(t.getUTCDate()+e.weeks*7+e.days),t.setUTCHours(t.getUTCHours()+e.hours),t.setUTCMinutes(t.getUTCMinutes()+e.minutes),t.setUTCSeconds(t.getUTCSeconds()+e.seconds)),t}function qm(s,e="second",t=Date.now()){const r=s.getTime()-t;if(r===0)return new Tt;const o=Math.sign(r),i=Math.abs(r),n=Math.floor(i/1e3),a=Math.floor(n/60),l=Math.floor(a/60),d=Math.floor(l/24),c=Math.floor(d/30),h=Math.floor(c/12),g=Gs.indexOf(e)||Gs.length;return new Tt(g>=0?h*o:0,g>=1?(c-h*12)*o:0,0,g>=3?(d-c*30)*o:0,g>=4?(l-d*24)*o:0,g>=5?(a-l*60)*o:0,g>=6?(n-a*60)*o:0,g>=7?(i-n*1e3)*o:0)}function nl(s,{relativeTo:e=Date.now()}={}){if(e=new Date(e),s.blank)return s;const t=s.sign;let r=Math.abs(s.years),o=Math.abs(s.months),i=Math.abs(s.weeks),n=Math.abs(s.days),a=Math.abs(s.hours),l=Math.abs(s.minutes),d=Math.abs(s.seconds),c=Math.abs(s.milliseconds);c>=900&&(d+=Math.round(c/1e3)),(d||l||a||n||i||o||r)&&(c=0),d>=55&&(l+=Math.round(d/60)),(l||a||n||i||o||r)&&(d=0),l>=55&&(a+=Math.round(l/60)),(a||n||i||o||r)&&(l=0),n&&a>=12&&(n+=Math.round(a/24)),!n&&a>=21&&(n+=Math.round(a/24)),(n||i||o||r)&&(a=0);const h=e.getFullYear(),g=e.getMonth(),f=e.getDate();if(n>=27||r+o+n){const m=new Date(e);m.setDate(1),m.setMonth(g+o*t+1),m.setDate(0);const b=Math.max(0,f-m.getDate()),_=new Date(e);_.setFullYear(h+r*t),_.setDate(f-b),_.setMonth(g+o*t),_.setDate(f-b+n*t);const $=_.getFullYear()-e.getFullYear(),x=_.getMonth()-e.getMonth(),A=Math.abs(Math.round((Number(_)-Number(e))/864e5))+b,S=Math.abs($*12+x);A<27?(n>=6?(i+=Math.round(n/7),n=0):n=A,o=r=0):S<=11?(o=S,r=0):(o=0,r=$*t),(o||r)&&(n=0)}return r&&(o=0),i>=4&&(o+=Math.round(i/4)),(o||r)&&(i=0),n&&i&&!o&&!r&&(i+=Math.round(n/7),n=0),new Tt(r*t,o*t,i*t,n*t,a*t,l*t,d*t,c*t)}function Hm(s,e){const t=nl(s,e);if(t.blank)return[0,"second"];for(const r of Gs){if(r==="millisecond")continue;const o=t[`${r}s`];if(o)return[o,r]}return[0,"second"]}var J=function(s,e,t,r){if(t==="a"&&!r)throw new TypeError("Private accessor was defined without a getter");if(typeof e=="function"?s!==e||!r:!e.has(s))throw new TypeError("Cannot read private member from an object whose class did not declare it");return t==="m"?r:t==="a"?r.call(s):r?r.value:e.get(s)},Ss=function(s,e,t,r,o){if(typeof e=="function"?s!==e||!0:!e.has(s))throw new TypeError("Cannot write private member to an object whose class did not declare it");return e.set(s,t),t},ae,Wt,Kt,vt,lt,ao,al,ll,cl,dl,ul,lo,hl,wt;const Wm=globalThis.HTMLElement||null,Vr=new Tt,Ji=new Tt(0,0,0,0,0,1);class Km extends Event{constructor(e,t,r,o){super("relative-time-updated",{bubbles:!0,composed:!0}),this.oldText=e,this.newText=t,this.oldTitle=r,this.newTitle=o}}function Yi(s){if(!s.date)return 1/0;if(s.format==="duration"||s.format==="elapsed"){const t=s.precision;if(t==="second")return 1e3;if(t==="minute")return 60*1e3}const e=Math.abs(Date.now()-s.date.getTime());return e<60*1e3?1e3:e<3600*1e3?60*1e3:3600*1e3}const zr=new class{constructor(){this.elements=new Set,this.time=1/0,this.timer=-1}observe(s){if(this.elements.has(s))return;this.elements.add(s);const e=s.date;if(e&&e.getTime()){const t=Yi(s),r=Date.now()+t;r<this.time&&(clearTimeout(this.timer),this.timer=setTimeout(()=>this.update(),t),this.time=r)}}unobserve(s){this.elements.has(s)&&this.elements.delete(s)}update(){if(clearTimeout(this.timer),!this.elements.size)return;let s=1/0;for(const e of this.elements)s=Math.min(s,Yi(e)),e.update();this.time=Math.min(3600*1e3,s),this.timer=setTimeout(()=>this.update(),this.time),this.time+=Date.now()}};class Gm extends Wm{constructor(){super(...arguments),ae.add(this),Wt.set(this,!1),Kt.set(this,!1),lt.set(this,this.shadowRoot?this.shadowRoot:this.attachShadow?this.attachShadow({mode:"open"}):this),wt.set(this,null)}static define(e="relative-time",t=customElements){return t.define(e,this),this}get timeZone(){var e;return((e=this.closest("[time-zone]"))===null||e===void 0?void 0:e.getAttribute("time-zone"))||this.ownerDocument.documentElement.getAttribute("time-zone")||void 0}static get observedAttributes(){return["second","minute","hour","weekday","day","month","year","time-zone-name","prefix","threshold","tense","precision","format","format-style","no-title","datetime","lang","title","aria-hidden","time-zone"]}get onRelativeTimeUpdated(){return J(this,wt,"f")}set onRelativeTimeUpdated(e){J(this,wt,"f")&&this.removeEventListener("relative-time-updated",J(this,wt,"f")),Ss(this,wt,typeof e=="object"||typeof e=="function"?e:null),typeof e=="function"&&this.addEventListener("relative-time-updated",e)}get second(){const e=this.getAttribute("second");if(e==="numeric"||e==="2-digit")return e}set second(e){this.setAttribute("second",e||"")}get minute(){const e=this.getAttribute("minute");if(e==="numeric"||e==="2-digit")return e}set minute(e){this.setAttribute("minute",e||"")}get hour(){const e=this.getAttribute("hour");if(e==="numeric"||e==="2-digit")return e}set hour(e){this.setAttribute("hour",e||"")}get weekday(){const e=this.getAttribute("weekday");if(e==="long"||e==="short"||e==="narrow")return e;if(this.format==="datetime"&&e!=="")return this.formatStyle}set weekday(e){this.setAttribute("weekday",e||"")}get day(){var e;const t=(e=this.getAttribute("day"))!==null&&e!==void 0?e:"numeric";if(t==="numeric"||t==="2-digit")return t}set day(e){this.setAttribute("day",e||"")}get month(){const e=this.format;let t=this.getAttribute("month");if(t!==""&&(t??(t=e==="datetime"?this.formatStyle:"short"),t==="numeric"||t==="2-digit"||t==="short"||t==="long"||t==="narrow"))return t}set month(e){this.setAttribute("month",e||"")}get year(){var e;const t=this.getAttribute("year");if(t==="numeric"||t==="2-digit")return t;if(!this.hasAttribute("year")&&new Date().getUTCFullYear()!==((e=this.date)===null||e===void 0?void 0:e.getUTCFullYear()))return"numeric"}set year(e){this.setAttribute("year",e||"")}get timeZoneName(){const e=this.getAttribute("time-zone-name");if(e==="long"||e==="short"||e==="shortOffset"||e==="longOffset"||e==="shortGeneric"||e==="longGeneric")return e}set timeZoneName(e){this.setAttribute("time-zone-name",e||"")}get prefix(){var e;return(e=this.getAttribute("prefix"))!==null&&e!==void 0?e:this.format==="datetime"?"":"on"}set prefix(e){this.setAttribute("prefix",e)}get threshold(){const e=this.getAttribute("threshold");return e&&jm(e)?e:"P30D"}set threshold(e){this.setAttribute("threshold",e)}get tense(){const e=this.getAttribute("tense");return e==="past"?"past":e==="future"?"future":"auto"}set tense(e){this.setAttribute("tense",e)}get precision(){const e=this.getAttribute("precision");return Gs.includes(e)?e:this.format==="micro"?"minute":"second"}set precision(e){this.setAttribute("precision",e)}get format(){const e=this.getAttribute("format");return e==="datetime"?"datetime":e==="relative"?"relative":e==="duration"?"duration":e==="micro"?"micro":e==="elapsed"?"elapsed":"auto"}set format(e){this.setAttribute("format",e)}get formatStyle(){const e=this.getAttribute("format-style");if(e==="long")return"long";if(e==="short")return"short";if(e==="narrow")return"narrow";const t=this.format;return t==="elapsed"||t==="micro"?"narrow":t==="datetime"?"short":"long"}set formatStyle(e){this.setAttribute("format-style",e)}get noTitle(){return this.hasAttribute("no-title")}set noTitle(e){this.toggleAttribute("no-title",e)}get datetime(){return this.getAttribute("datetime")||""}set datetime(e){this.setAttribute("datetime",e)}get date(){const e=Date.parse(this.datetime);return Number.isNaN(e)?null:new Date(e)}set date(e){this.datetime=e?.toISOString()||""}connectedCallback(){this.update()}disconnectedCallback(){zr.unobserve(this)}attributeChangedCallback(e,t,r){t!==r&&(e==="title"&&Ss(this,Wt,r!==null&&(this.date&&J(this,ae,"m",ao).call(this,this.date))!==r),!J(this,Kt,"f")&&!(e==="title"&&J(this,Wt,"f"))&&Ss(this,Kt,(async()=>{await Promise.resolve(),this.update(),Ss(this,Kt,!1,"f")})()))}update(){const e=J(this,lt,"f").textContent||this.textContent||"",t=this.getAttribute("title")||"";let r=t;const o=this.date;if(typeof Intl>"u"||!Intl.DateTimeFormat||!o){J(this,lt,"f").textContent=e;return}const i=Date.now();J(this,Wt,"f")||(r=J(this,ae,"m",ao).call(this,o)||"",r&&!this.noTitle&&this.setAttribute("title",r));const n=qm(o,this.precision,i),a=J(this,ae,"m",al).call(this,n);let l=e;const d=J(this,ae,"m",hl).call(this,a);d?l=J(this,ae,"m",ul).call(this,o):a==="duration"?l=J(this,ae,"m",ll).call(this,n):a==="relative"?l=J(this,ae,"m",cl).call(this,n):l=J(this,ae,"m",dl).call(this,o),l?J(this,ae,"m",lo).call(this,l):this.shadowRoot===J(this,lt,"f")&&this.textContent&&J(this,ae,"m",lo).call(this,this.textContent),(l!==e||r!==t)&&this.dispatchEvent(new Km(e,l,t,r)),(a==="relative"||a==="duration")&&!d?zr.observe(this):zr.unobserve(this)}}Wt=new WeakMap,Kt=new WeakMap,lt=new WeakMap,wt=new WeakMap,ae=new WeakSet,vt=function(){var s;const e=((s=this.closest("[lang]"))===null||s===void 0?void 0:s.getAttribute("lang"))||this.ownerDocument.documentElement.getAttribute("lang");try{return new Intl.Locale(e??"").toString()}catch{return"default"}},ao=function(s){return new Intl.DateTimeFormat(J(this,ae,"a",vt),{day:"numeric",month:"short",year:"numeric",hour:"numeric",minute:"2-digit",timeZoneName:"short",timeZone:this.timeZone}).format(s)},al=function(s){const e=this.format;if(e==="datetime")return"datetime";if(e==="duration"||e==="elapsed"||e==="micro")return"duration";if((e==="auto"||e==="relative")&&typeof Intl<"u"&&Intl.RelativeTimeFormat){const t=this.tense;if(t==="past"||t==="future"||Tt.compare(s,this.threshold)===1)return"relative"}return"datetime"},ll=function(s){const e=J(this,ae,"a",vt),t=this.format,r=this.formatStyle,o=this.tense;let i=Vr;t==="micro"?(s=nl(s),i=Ji,s.months===0&&(this.tense==="past"&&s.sign!==-1||this.tense==="future"&&s.sign!==1)&&(s=Ji)):(o==="past"&&s.sign!==-1||o==="future"&&s.sign!==1)&&(s=i);const n=`${this.precision}sDisplay`;return s.blank?i.toLocaleString(e,{style:r,[n]:"always"}):s.abs().toLocaleString(e,{style:r})},cl=function(s){const e=new Intl.RelativeTimeFormat(J(this,ae,"a",vt),{numeric:"auto",style:this.formatStyle}),t=this.tense;t==="future"&&s.sign!==1&&(s=Vr),t==="past"&&s.sign!==-1&&(s=Vr);const[r,o]=Hm(s);return o==="second"&&r<10?e.format(0,this.precision==="millisecond"?"second":this.precision):e.format(r,o)},dl=function(s){const e=new Intl.DateTimeFormat(J(this,ae,"a",vt),{second:this.second,minute:this.minute,hour:this.hour,weekday:this.weekday,day:this.day,month:this.month,year:this.year,timeZoneName:this.timeZoneName,timeZone:this.timeZone});return`${this.prefix} ${e.format(s)}`.trim()},ul=function(s){return new Intl.DateTimeFormat(J(this,ae,"a",vt),{day:"numeric",month:"short",year:"numeric",hour:"numeric",minute:"2-digit",timeZoneName:"short",timeZone:this.timeZone}).format(s)},lo=function(s){if(this.hasAttribute("aria-hidden")&&this.getAttribute("aria-hidden")==="true"){const e=document.createElement("span");e.setAttribute("aria-hidden","true"),e.textContent=s,J(this,lt,"f").replaceChildren(e)}else J(this,lt,"f").textContent=s},hl=function(s){var e;return s==="duration"?!1:this.ownerDocument.documentElement.getAttribute("data-prefers-absolute-time")==="true"||((e=this.ownerDocument.body)===null||e===void 0?void 0:e.getAttribute("data-prefers-absolute-time"))==="true"};const Xi=typeof globalThis<"u"?globalThis:window;try{Xi.RelativeTimeElement=Gm.define()}catch(s){if(!(Xi.DOMException&&s instanceof DOMException&&s.name==="NotSupportedError")&&!(s instanceof ReferenceError))throw s}const Ms=globalThis,qo=Ms.ShadowRoot&&(Ms.ShadyCSS===void 0||Ms.ShadyCSS.nativeShadow)&&"adoptedStyleSheets"in Document.prototype&&"replace"in CSSStyleSheet.prototype,pl=Symbol(),Zi=new WeakMap;let Jm=class{constructor(e,t,r){if(this._$cssResult$=!0,r!==pl)throw Error("CSSResult is not constructable. Use `unsafeCSS` or `css` instead.");this.cssText=e,this.t=t}get styleSheet(){let e=this.o;const t=this.t;if(qo&&e===void 0){const r=t!==void 0&&t.length===1;r&&(e=Zi.get(t)),e===void 0&&((this.o=e=new CSSStyleSheet).replaceSync(this.cssText),r&&Zi.set(t,e))}return e}toString(){return this.cssText}};const Ym=s=>new Jm(typeof s=="string"?s:s+"",void 0,pl),Xm=(s,e)=>{if(qo)s.adoptedStyleSheets=e.map((t=>t instanceof CSSStyleSheet?t:t.styleSheet));else for(const t of e){const r=document.createElement("style"),o=Ms.litNonce;o!==void 0&&r.setAttribute("nonce",o),r.textContent=t.cssText,s.appendChild(r)}},Qi=qo?s=>s:s=>s instanceof CSSStyleSheet?(e=>{let t="";for(const r of e.cssRules)t+=r.cssText;return Ym(t)})(s):s;const{is:Zm,defineProperty:Qm,getOwnPropertyDescriptor:ef,getOwnPropertyNames:tf,getOwnPropertySymbols:sf,getPrototypeOf:rf}=Object,fr=globalThis,en=fr.trustedTypes,of=en?en.emptyScript:"",nf=fr.reactiveElementPolyfillSupport,Zt=(s,e)=>s,Js={toAttribute(s,e){switch(e){case Boolean:s=s?of:null;break;case Object:case Array:s=s==null?s:JSON.stringify(s)}return s},fromAttribute(s,e){let t=s;switch(e){case Boolean:t=s!==null;break;case Number:t=s===null?null:Number(s);break;case Object:case Array:try{t=JSON.parse(s)}catch{t=null}}return t}},Ho=(s,e)=>!Zm(s,e),tn={attribute:!0,type:String,converter:Js,reflect:!1,useDefault:!1,hasChanged:Ho};Symbol.metadata??=Symbol("metadata"),fr.litPropertyMetadata??=new WeakMap;class _t extends HTMLElement{static addInitializer(e){this._$Ei(),(this.l??=[]).push(e)}static get observedAttributes(){return this.finalize(),this._$Eh&&[...this._$Eh.keys()]}static createProperty(e,t=tn){if(t.state&&(t.attribute=!1),this._$Ei(),this.prototype.hasOwnProperty(e)&&((t=Object.create(t)).wrapped=!0),this.elementProperties.set(e,t),!t.noAccessor){const r=Symbol(),o=this.getPropertyDescriptor(e,r,t);o!==void 0&&Qm(this.prototype,e,o)}}static getPropertyDescriptor(e,t,r){const{get:o,set:i}=ef(this.prototype,e)??{get(){return this[t]},set(n){this[t]=n}};return{get:o,set(n){const a=o?.call(this);i?.call(this,n),this.requestUpdate(e,a,r)},configurable:!0,enumerable:!0}}static getPropertyOptions(e){return this.elementProperties.get(e)??tn}static _$Ei(){if(this.hasOwnProperty(Zt("elementProperties")))return;const e=rf(this);e.finalize(),e.l!==void 0&&(this.l=[...e.l]),this.elementProperties=new Map(e.elementProperties)}static finalize(){if(this.hasOwnProperty(Zt("finalized")))return;if(this.finalized=!0,this._$Ei(),this.hasOwnProperty(Zt("properties"))){const t=this.properties,r=[...tf(t),...sf(t)];for(const o of r)this.createProperty(o,t[o])}const e=this[Symbol.metadata];if(e!==null){const t=litPropertyMetadata.get(e);if(t!==void 0)for(const[r,o]of t)this.elementProperties.set(r,o)}this._$Eh=new Map;for(const[t,r]of this.elementProperties){const o=this._$Eu(t,r);o!==void 0&&this._$Eh.set(o,t)}this.elementStyles=this.finalizeStyles(this.styles)}static finalizeStyles(e){const t=[];if(Array.isArray(e)){const r=new Set(e.flat(1/0).reverse());for(const o of r)t.unshift(Qi(o))}else e!==void 0&&t.push(Qi(e));return t}static _$Eu(e,t){const r=t.attribute;return r===!1?void 0:typeof r=="string"?r:typeof e=="string"?e.toLowerCase():void 0}constructor(){super(),this._$Ep=void 0,this.isUpdatePending=!1,this.hasUpdated=!1,this._$Em=null,this._$Ev()}_$Ev(){this._$ES=new Promise((e=>this.enableUpdating=e)),this._$AL=new Map,this._$E_(),this.requestUpdate(),this.constructor.l?.forEach((e=>e(this)))}addController(e){(this._$EO??=new Set).add(e),this.renderRoot!==void 0&&this.isConnected&&e.hostConnected?.()}removeController(e){this._$EO?.delete(e)}_$E_(){const e=new Map,t=this.constructor.elementProperties;for(const r of t.keys())this.hasOwnProperty(r)&&(e.set(r,this[r]),delete this[r]);e.size>0&&(this._$Ep=e)}createRenderRoot(){const e=this.shadowRoot??this.attachShadow(this.constructor.shadowRootOptions);return Xm(e,this.constructor.elementStyles),e}connectedCallback(){this.renderRoot??=this.createRenderRoot(),this.enableUpdating(!0),this._$EO?.forEach((e=>e.hostConnected?.()))}enableUpdating(e){}disconnectedCallback(){this._$EO?.forEach((e=>e.hostDisconnected?.()))}attributeChangedCallback(e,t,r){this._$AK(e,r)}_$ET(e,t){const r=this.constructor.elementProperties.get(e),o=this.constructor._$Eu(e,r);if(o!==void 0&&r.reflect===!0){const i=(r.converter?.toAttribute!==void 0?r.converter:Js).toAttribute(t,r.type);this._$Em=e,i==null?this.removeAttribute(o):this.setAttribute(o,i),this._$Em=null}}_$AK(e,t){const r=this.constructor,o=r._$Eh.get(e);if(o!==void 0&&this._$Em!==o){const i=r.getPropertyOptions(o),n=typeof i.converter=="function"?{fromAttribute:i.converter}:i.converter?.fromAttribute!==void 0?i.converter:Js;this._$Em=o;const a=n.fromAttribute(t,i.type);this[o]=a??this._$Ej?.get(o)??a,this._$Em=null}}requestUpdate(e,t,r){if(e!==void 0){const o=this.constructor,i=this[e];if(r??=o.getPropertyOptions(e),!((r.hasChanged??Ho)(i,t)||r.useDefault&&r.reflect&&i===this._$Ej?.get(e)&&!this.hasAttribute(o._$Eu(e,r))))return;this.C(e,t,r)}this.isUpdatePending===!1&&(this._$ES=this._$EP())}C(e,t,{useDefault:r,reflect:o,wrapped:i},n){r&&!(this._$Ej??=new Map).has(e)&&(this._$Ej.set(e,n??t??this[e]),i!==!0||n!==void 0)||(this._$AL.has(e)||(this.hasUpdated||r||(t=void 0),this._$AL.set(e,t)),o===!0&&this._$Em!==e&&(this._$Eq??=new Set).add(e))}async _$EP(){this.isUpdatePending=!0;try{await this._$ES}catch(t){Promise.reject(t)}const e=this.scheduleUpdate();return e!=null&&await e,!this.isUpdatePending}scheduleUpdate(){return this.performUpdate()}performUpdate(){if(!this.isUpdatePending)return;if(!this.hasUpdated){if(this.renderRoot??=this.createRenderRoot(),this._$Ep){for(const[o,i]of this._$Ep)this[o]=i;this._$Ep=void 0}const r=this.constructor.elementProperties;if(r.size>0)for(const[o,i]of r){const{wrapped:n}=i,a=this[o];n!==!0||this._$AL.has(o)||a===void 0||this.C(o,void 0,i,a)}}let e=!1;const t=this._$AL;try{e=this.shouldUpdate(t),e?(this.willUpdate(t),this._$EO?.forEach((r=>r.hostUpdate?.())),this.update(t)):this._$EM()}catch(r){throw e=!1,this._$EM(),r}e&&this._$AE(t)}willUpdate(e){}_$AE(e){this._$EO?.forEach((t=>t.hostUpdated?.())),this.hasUpdated||(this.hasUpdated=!0,this.firstUpdated(e)),this.updated(e)}_$EM(){this._$AL=new Map,this.isUpdatePending=!1}get updateComplete(){return this.getUpdateComplete()}getUpdateComplete(){return this._$ES}shouldUpdate(e){return!0}update(e){this._$Eq&&=this._$Eq.forEach((t=>this._$ET(t,this[t]))),this._$EM()}updated(e){}firstUpdated(e){}}_t.elementStyles=[],_t.shadowRootOptions={mode:"open"},_t[Zt("elementProperties")]=new Map,_t[Zt("finalized")]=new Map,nf?.({ReactiveElement:_t}),(fr.reactiveElementVersions??=[]).push("2.1.1");const Wo=globalThis,Ys=Wo.trustedTypes,sn=Ys?Ys.createPolicy("lit-html",{createHTML:s=>s}):void 0,ml="$lit$",Ye=`lit$${Math.random().toFixed(9).slice(2)}$`,fl="?"+Ye,af=`<${fl}>`,ft=document,ss=()=>ft.createComment(""),rs=s=>s===null||typeof s!="object"&&typeof s!="function",Ko=Array.isArray,lf=s=>Ko(s)||typeof s?.[Symbol.iterator]=="function",Ir=`[ 	
\f\r]`,jt=/<(?:(!--|\/[^a-zA-Z])|(\/?[a-zA-Z][^>\s]*)|(\/?$))/g,rn=/-->/g,on=/>/g,rt=RegExp(`>|${Ir}(?:([^\\s"'>=/]+)(${Ir}*=${Ir}*(?:[^ 	
\f\r"'\`<>=]|("|')|))|$)`,"g"),nn=/'/g,an=/"/g,gl=/^(?:script|style|textarea|title)$/i,Ot=Symbol.for("lit-noChange"),ne=Symbol.for("lit-nothing"),ln=new WeakMap,ct=ft.createTreeWalker(ft,129);function bl(s,e){if(!Ko(s)||!s.hasOwnProperty("raw"))throw Error("invalid template strings array");return sn!==void 0?sn.createHTML(e):e}const cf=(s,e)=>{const t=s.length-1,r=[];let o,i=e===2?"<svg>":e===3?"<math>":"",n=jt;for(let a=0;a<t;a++){const l=s[a];let d,c,h=-1,g=0;for(;g<l.length&&(n.lastIndex=g,c=n.exec(l),c!==null);)g=n.lastIndex,n===jt?c[1]==="!--"?n=rn:c[1]!==void 0?n=on:c[2]!==void 0?(gl.test(c[2])&&(o=RegExp("</"+c[2],"g")),n=rt):c[3]!==void 0&&(n=rt):n===rt?c[0]===">"?(n=o??jt,h=-1):c[1]===void 0?h=-2:(h=n.lastIndex-c[2].length,d=c[1],n=c[3]===void 0?rt:c[3]==='"'?an:nn):n===an||n===nn?n=rt:n===rn||n===on?n=jt:(n=rt,o=void 0);const f=n===rt&&s[a+1].startsWith("/>")?" ":"";i+=n===jt?l+af:h>=0?(r.push(d),l.slice(0,h)+ml+l.slice(h)+Ye+f):l+Ye+(h===-2?a:f)}return[bl(s,i+(s[t]||"<?>")+(e===2?"</svg>":e===3?"</math>":"")),r]};class os{constructor({strings:e,_$litType$:t},r){let o;this.parts=[];let i=0,n=0;const a=e.length-1,l=this.parts,[d,c]=cf(e,t);if(this.el=os.createElement(d,r),ct.currentNode=this.el.content,t===2||t===3){const h=this.el.content.firstChild;h.replaceWith(...h.childNodes)}for(;(o=ct.nextNode())!==null&&l.length<a;){if(o.nodeType===1){if(o.hasAttributes())for(const h of o.getAttributeNames())if(h.endsWith(ml)){const g=c[n++],f=o.getAttribute(h).split(Ye),m=/([.?@])?(.*)/.exec(g);l.push({type:1,index:i,name:m[2],strings:f,ctor:m[1]==="."?uf:m[1]==="?"?hf:m[1]==="@"?pf:gr}),o.removeAttribute(h)}else h.startsWith(Ye)&&(l.push({type:6,index:i}),o.removeAttribute(h));if(gl.test(o.tagName)){const h=o.textContent.split(Ye),g=h.length-1;if(g>0){o.textContent=Ys?Ys.emptyScript:"";for(let f=0;f<g;f++)o.append(h[f],ss()),ct.nextNode(),l.push({type:2,index:++i});o.append(h[g],ss())}}}else if(o.nodeType===8)if(o.data===fl)l.push({type:2,index:i});else{let h=-1;for(;(h=o.data.indexOf(Ye,h+1))!==-1;)l.push({type:7,index:i}),h+=Ye.length-1}i++}}static createElement(e,t){const r=ft.createElement("template");return r.innerHTML=e,r}}function Lt(s,e,t=s,r){if(e===Ot)return e;let o=r!==void 0?t._$Co?.[r]:t._$Cl;const i=rs(e)?void 0:e._$litDirective$;return o?.constructor!==i&&(o?._$AO?.(!1),i===void 0?o=void 0:(o=new i(s),o._$AT(s,t,r)),r!==void 0?(t._$Co??=[])[r]=o:t._$Cl=o),o!==void 0&&(e=Lt(s,o._$AS(s,e.values),o,r)),e}class df{constructor(e,t){this._$AV=[],this._$AN=void 0,this._$AD=e,this._$AM=t}get parentNode(){return this._$AM.parentNode}get _$AU(){return this._$AM._$AU}u(e){const{el:{content:t},parts:r}=this._$AD,o=(e?.creationScope??ft).importNode(t,!0);ct.currentNode=o;let i=ct.nextNode(),n=0,a=0,l=r[0];for(;l!==void 0;){if(n===l.index){let d;l.type===2?d=new ws(i,i.nextSibling,this,e):l.type===1?d=new l.ctor(i,l.name,l.strings,this,e):l.type===6&&(d=new mf(i,this,e)),this._$AV.push(d),l=r[++a]}n!==l?.index&&(i=ct.nextNode(),n++)}return ct.currentNode=ft,o}p(e){let t=0;for(const r of this._$AV)r!==void 0&&(r.strings!==void 0?(r._$AI(e,r,t),t+=r.strings.length-2):r._$AI(e[t])),t++}}class ws{get _$AU(){return this._$AM?._$AU??this._$Cv}constructor(e,t,r,o){this.type=2,this._$AH=ne,this._$AN=void 0,this._$AA=e,this._$AB=t,this._$AM=r,this.options=o,this._$Cv=o?.isConnected??!0}get parentNode(){let e=this._$AA.parentNode;const t=this._$AM;return t!==void 0&&e?.nodeType===11&&(e=t.parentNode),e}get startNode(){return this._$AA}get endNode(){return this._$AB}_$AI(e,t=this){e=Lt(this,e,t),rs(e)?e===ne||e==null||e===""?(this._$AH!==ne&&this._$AR(),this._$AH=ne):e!==this._$AH&&e!==Ot&&this._(e):e._$litType$!==void 0?this.$(e):e.nodeType!==void 0?this.T(e):lf(e)?this.k(e):this._(e)}O(e){return this._$AA.parentNode.insertBefore(e,this._$AB)}T(e){this._$AH!==e&&(this._$AR(),this._$AH=this.O(e))}_(e){this._$AH!==ne&&rs(this._$AH)?this._$AA.nextSibling.data=e:this.T(ft.createTextNode(e)),this._$AH=e}$(e){const{values:t,_$litType$:r}=e,o=typeof r=="number"?this._$AC(e):(r.el===void 0&&(r.el=os.createElement(bl(r.h,r.h[0]),this.options)),r);if(this._$AH?._$AD===o)this._$AH.p(t);else{const i=new df(o,this),n=i.u(this.options);i.p(t),this.T(n),this._$AH=i}}_$AC(e){let t=ln.get(e.strings);return t===void 0&&ln.set(e.strings,t=new os(e)),t}k(e){Ko(this._$AH)||(this._$AH=[],this._$AR());const t=this._$AH;let r,o=0;for(const i of e)o===t.length?t.push(r=new ws(this.O(ss()),this.O(ss()),this,this.options)):r=t[o],r._$AI(i),o++;o<t.length&&(this._$AR(r&&r._$AB.nextSibling,o),t.length=o)}_$AR(e=this._$AA.nextSibling,t){for(this._$AP?.(!1,!0,t);e!==this._$AB;){const r=e.nextSibling;e.remove(),e=r}}setConnected(e){this._$AM===void 0&&(this._$Cv=e,this._$AP?.(e))}}class gr{get tagName(){return this.element.tagName}get _$AU(){return this._$AM._$AU}constructor(e,t,r,o,i){this.type=1,this._$AH=ne,this._$AN=void 0,this.element=e,this.name=t,this._$AM=o,this.options=i,r.length>2||r[0]!==""||r[1]!==""?(this._$AH=Array(r.length-1).fill(new String),this.strings=r):this._$AH=ne}_$AI(e,t=this,r,o){const i=this.strings;let n=!1;if(i===void 0)e=Lt(this,e,t,0),n=!rs(e)||e!==this._$AH&&e!==Ot,n&&(this._$AH=e);else{const a=e;let l,d;for(e=i[0],l=0;l<i.length-1;l++)d=Lt(this,a[r+l],t,l),d===Ot&&(d=this._$AH[l]),n||=!rs(d)||d!==this._$AH[l],d===ne?e=ne:e!==ne&&(e+=(d??"")+i[l+1]),this._$AH[l]=d}n&&!o&&this.j(e)}j(e){e===ne?this.element.removeAttribute(this.name):this.element.setAttribute(this.name,e??"")}}class uf extends gr{constructor(){super(...arguments),this.type=3}j(e){this.element[this.name]=e===ne?void 0:e}}class hf extends gr{constructor(){super(...arguments),this.type=4}j(e){this.element.toggleAttribute(this.name,!!e&&e!==ne)}}class pf extends gr{constructor(e,t,r,o,i){super(e,t,r,o,i),this.type=5}_$AI(e,t=this){if((e=Lt(this,e,t,0)??ne)===Ot)return;const r=this._$AH,o=e===ne&&r!==ne||e.capture!==r.capture||e.once!==r.once||e.passive!==r.passive,i=e!==ne&&(r===ne||o);o&&this.element.removeEventListener(this.name,this,r),i&&this.element.addEventListener(this.name,this,e),this._$AH=e}handleEvent(e){typeof this._$AH=="function"?this._$AH.call(this.options?.host??this.element,e):this._$AH.handleEvent(e)}}class mf{constructor(e,t,r){this.element=e,this.type=6,this._$AN=void 0,this._$AM=t,this.options=r}get _$AU(){return this._$AM._$AU}_$AI(e){Lt(this,e)}}const ff=Wo.litHtmlPolyfillSupport;ff?.(os,ws),(Wo.litHtmlVersions??=[]).push("3.3.1");const gf=(s,e,t)=>{const r=t?.renderBefore??e;let o=r._$litPart$;if(o===void 0){const i=t?.renderBefore??null;r._$litPart$=o=new ws(e.insertBefore(ss(),i),i,void 0,t??{})}return o._$AI(s),o};const Go=globalThis;class Qt extends _t{constructor(){super(...arguments),this.renderOptions={host:this},this._$Do=void 0}createRenderRoot(){const e=super.createRenderRoot();return this.renderOptions.renderBefore??=e.firstChild,e}update(e){const t=this.render();this.hasUpdated||(this.renderOptions.isConnected=this.isConnected),super.update(e),this._$Do=gf(t,this.renderRoot,this.renderOptions)}connectedCallback(){super.connectedCallback(),this._$Do?.setConnected(!0)}disconnectedCallback(){super.disconnectedCallback(),this._$Do?.setConnected(!1)}render(){return Ot}}Qt._$litElement$=!0,Qt.finalized=!0,Go.litElementHydrateSupport?.({LitElement:Qt});const bf=Go.litElementPolyfillSupport;bf?.({LitElement:Qt});(Go.litElementVersions??=[]).push("4.2.1");const vf=s=>(e,t)=>{t!==void 0?t.addInitializer((()=>{customElements.define(s,e)})):customElements.define(s,e)};const yf={attribute:!0,type:String,converter:Js,reflect:!1,hasChanged:Ho},wf=(s=yf,e,t)=>{const{kind:r,metadata:o}=t;let i=globalThis.litPropertyMetadata.get(o);if(i===void 0&&globalThis.litPropertyMetadata.set(o,i=new Map),r==="setter"&&((s=Object.create(s)).wrapped=!0),i.set(t.name,s),r==="accessor"){const{name:n}=t;return{set(a){const l=e.get.call(this);e.set.call(this,a),this.requestUpdate(n,l,s)},init(a){return a!==void 0&&this.C(n,void 0,s,a),a}}}if(r==="setter"){const{name:n}=t;return function(a){const l=this[n];e.call(this,a),this.requestUpdate(n,l,s)}}throw Error("Unsupported decorator location: "+r)};function _f(s){return(e,t)=>typeof t=="object"?wf(s,e,t):((r,o,i)=>{const n=o.hasOwnProperty(i);return o.constructor.createProperty(i,r),n?Object.getOwnPropertyDescriptor(o,i):void 0})(s,e,t)}const vl=(s,e,t)=>(t.configurable=!0,t.enumerable=!0,Reflect.decorate&&typeof e!="object"&&Object.defineProperty(s,e,t),t);function $f(s,e){return(t,r,o)=>{const i=n=>n.renderRoot?.querySelector(s)??null;return vl(t,r,{get(){return i(this)}})}}let Ef;function xf(s){return(e,t)=>vl(e,t,{get(){return(this.renderRoot??(Ef??=document.createDocumentFragment())).querySelectorAll(s)}})}var kf=Object.defineProperty,Cf=Object.getOwnPropertyDescriptor,br=(s,e,t,r)=>{for(var o=r>1?void 0:r?Cf(e,t):e,i=s.length-1,n;i>=0;i--)(n=s[i])&&(o=(r?n(e,t,o):n(o))||o);return r&&o&&kf(e,t,o),o};let is=class extends Qt{constructor(){super(...arguments),this.state=Craft.getCookie("sidebar")??"expanded"}connectedCallback(){super.connectedCallback(),this.trigger&&(this.trigger.addEventListener("open",this.expand.bind(this)),this.trigger.addEventListener("close",this.collapse.bind(this))),this.state==="expanded"?this.expand():this.collapse()}disconnectedCallback(){super.disconnectedCallback(),this.trigger&&(this.trigger.removeEventListener("open",this.expand.bind(this)),this.trigger.removeEventListener("close",this.collapse.bind(this))),this.state="expanded"}itemHasTooltip(s){return s.querySelector("craft-tooltip")}createTooltips(){this.items?.forEach(s=>s.setAttribute("icon-only",!0))}destroyTooltips(){this.items?.forEach(s=>s.removeAttribute("icon-only"))}expand(){document.body.setAttribute("data-sidebar","expanded"),Craft.setCookie("sidebar","expanded"),this.destroyTooltips()}collapse(){document.body.setAttribute("data-sidebar","collapsed"),Craft.setCookie("sidebar","collapsed"),this.createTooltips()}createRenderRoot(){return this}};br([xf("craft-nav-item")],is.prototype,"items",2);br([$f("#sidebar-trigger")],is.prototype,"trigger",2);br([_f({reflect:!0})],is.prototype,"state",2);is=br([vf("cp-global-sidebar")],is);export{D as _};
