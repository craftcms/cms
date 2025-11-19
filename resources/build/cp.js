const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["./bg-BG-BqLSXSgK.js","./bg-Ch91FBqZ.js","./cs-CZ-BOieS6Re.js","./cs-Bco-9vYd.js","./de-DE-NiEdSbeI.js","./de--MUj2jPW.js","./en-AU-5SYH9YrO.js","./en-QBEFuq4A.js","./en-GB-5SYH9YrO.js","./en-US-5SYH9YrO.js","./es-ES-BzB2G1H7.js","./es-QUDKKOEt.js","./fr-FR-D8x_WpSN.js","./fr-Crw_WS9R.js","./fr-BE-D8x_WpSN.js","./hu-HU-DzuJRq2x.js","./hu-BzLNk3Oy.js","./it-IT-BVziFtOr.js","./it-Dk-tLV60.js","./nl-BE-Cv6cOJ-k.js","./nl-ukLmcyhE.js","./nl-NL-Cv6cOJ-k.js","./pl-PL-C3QXGAg0.js","./pl-BsbBHKbu.js","./ro-RO-BHOQwu0O.js","./ro-BWWeoMIS.js","./ru-RU-DCvtZjBo.js","./ru-D87QXJFw.js","./sk-SK-DaLB_sM8.js","./sk-DCOU_ZI_.js","./tr-TR-Dhk7tqKh.js","./tr-92apvQxK.js","./uk-UA-BP_5Rplg.js","./uk-CGlal3kJ.js","./Install.js","./Install.css"])))=>i.map(i=>d[i]);
const ZS="modulepreload",ex=function(t,e){return new URL(t,e).href},km={},ve=function(e,r,n){let s=Promise.resolve();if(r&&r.length>0){let u=function(c){return Promise.all(c.map(d=>Promise.resolve(d).then(p=>({status:"fulfilled",value:p}),p=>({status:"rejected",reason:p}))))};const o=document.getElementsByTagName("link"),a=document.querySelector("meta[property=csp-nonce]"),l=a?.nonce||a?.getAttribute("nonce");s=u(r.map(c=>{if(c=ex(c,n),c in km)return;km[c]=!0;const d=c.endsWith(".css"),p=d?'[rel="stylesheet"]':"";if(n)for(let h=o.length-1;h>=0;h--){const m=o[h];if(m.href===c&&(!d||m.rel==="stylesheet"))return}else if(document.querySelector(`link[href="${c}"]${p}`))return;const f=document.createElement("link");if(f.rel=d?"stylesheet":ZS,d||(f.as="script"),f.crossOrigin="",f.href=c,l&&f.setAttribute("nonce",l),document.head.appendChild(f),d)return new Promise((h,m)=>{f.addEventListener("load",h),f.addEventListener("error",()=>m(new Error(`Unable to preload CSS for ${c}`)))})}))}function i(o){const a=new Event("vite:preloadError",{cancelable:!0});if(a.payload=o,window.dispatchEvent(a),!a.defaultPrevented)throw o}return s.then(o=>{for(const a of o||[])a.status==="rejected"&&i(a.reason);return e().catch(i)})};function br(t){const e=Object.create(null);for(const r of t.split(","))e[r]=1;return r=>r in e}const Se={},gi=[],Tt=()=>{},fi=()=>!1,Gs=t=>t.charCodeAt(0)===111&&t.charCodeAt(1)===110&&(t.charCodeAt(2)>122||t.charCodeAt(2)<97),Af=t=>t.startsWith("onUpdate:"),we=Object.assign,$f=(t,e)=>{const r=t.indexOf(e);r>-1&&t.splice(r,1)},tx=Object.prototype.hasOwnProperty,Pe=(t,e)=>tx.call(t,e),te=Array.isArray,yi=t=>zi(t)==="[object Map]",Js=t=>zi(t)==="[object Set]",Pm=t=>zi(t)==="[object Date]",rx=t=>zi(t)==="[object RegExp]",ae=t=>typeof t=="function",de=t=>typeof t=="string",ir=t=>typeof t=="symbol",Ne=t=>t!==null&&typeof t=="object",Tf=t=>(Ne(t)||ae(t))&&ae(t.then)&&ae(t.catch),Dv=Object.prototype.toString,zi=t=>Dv.call(t),nx=t=>zi(t).slice(8,-1),Ac=t=>zi(t)==="[object Object]",kf=t=>de(t)&&t!=="NaN"&&t[0]!=="-"&&""+parseInt(t,10)===t,Xn=br(",key,ref,ref_for,ref_key,onVnodeBeforeMount,onVnodeMounted,onVnodeBeforeUpdate,onVnodeUpdated,onVnodeBeforeUnmount,onVnodeUnmounted"),sx=br("bind,cloak,else-if,else,for,html,if,model,on,once,pre,show,slot,text,memo"),$c=t=>{const e=Object.create(null);return(r=>e[r]||(e[r]=t(r)))},ix=/-\w/g,We=$c(t=>t.replace(ix,e=>e.slice(1).toUpperCase())),ox=/\B([A-Z])/g,Yt=$c(t=>t.replace(ox,"-$1").toLowerCase()),Xs=$c(t=>t.charAt(0).toUpperCase()+t.slice(1)),bi=$c(t=>t?`on${Xs(t)}`:""),qt=(t,e)=>!Object.is(t,e),vi=(t,...e)=>{for(let r=0;r<t.length;r++)t[r](...e)},Vv=(t,e,r,n=!1)=>{Object.defineProperty(t,e,{configurable:!0,enumerable:!1,writable:n,value:r})},Tc=t=>{const e=parseFloat(t);return isNaN(e)?t:e},Nl=t=>{const e=de(t)?Number(t):NaN;return isNaN(e)?t:e};let Om;const kc=()=>Om||(Om=typeof globalThis<"u"?globalThis:typeof self<"u"?self:typeof window<"u"?window:typeof global<"u"?global:{});function ax(t,e){return t+JSON.stringify(e,(r,n)=>typeof n=="function"?n.toString():n)}const lx="Infinity,undefined,NaN,isFinite,isNaN,parseFloat,parseInt,decodeURI,decodeURIComponent,encodeURI,encodeURIComponent,Math,Number,Date,Array,Object,Boolean,String,RegExp,Map,Set,JSON,Intl,BigInt,console,Error,Symbol",cx=br(lx);function ha(t){if(te(t)){const e={};for(let r=0;r<t.length;r++){const n=t[r],s=de(n)?Bv(n):ha(n);if(s)for(const i in s)e[i]=s[i]}return e}else if(de(t)||Ne(t))return t}const ux=/;(?![^(]*\))/g,dx=/:([^]+)/,hx=/\/\*[^]*?\*\//g;function Bv(t){const e={};return t.replace(hx,"").split(ux).forEach(r=>{if(r){const n=r.split(dx);n.length>1&&(e[n[0].trim()]=n[1].trim())}}),e}function fa(t){let e="";if(de(t))e=t;else if(te(t))for(let r=0;r<t.length;r++){const n=fa(t[r]);n&&(e+=n+" ")}else if(Ne(t))for(const r in t)t[r]&&(e+=r+" ");return e.trim()}function fx(t){if(!t)return null;let{class:e,style:r}=t;return e&&!de(e)&&(t.class=fa(e)),r&&(t.style=ha(r)),t}const px="html,body,base,head,link,meta,style,title,address,article,aside,footer,header,hgroup,h1,h2,h3,h4,h5,h6,nav,section,div,dd,dl,dt,figcaption,figure,picture,hr,img,li,main,ol,p,pre,ul,a,b,abbr,bdi,bdo,br,cite,code,data,dfn,em,i,kbd,mark,q,rp,rt,ruby,s,samp,small,span,strong,sub,sup,time,u,var,wbr,area,audio,map,track,video,embed,object,param,source,canvas,script,noscript,del,ins,caption,col,colgroup,table,thead,tbody,td,th,tr,button,datalist,fieldset,form,input,label,legend,meter,optgroup,option,output,progress,select,textarea,details,dialog,menu,summary,template,blockquote,iframe,tfoot",mx="svg,animate,animateMotion,animateTransform,circle,clipPath,color-profile,defs,desc,discard,ellipse,feBlend,feColorMatrix,feComponentTransfer,feComposite,feConvolveMatrix,feDiffuseLighting,feDisplacementMap,feDistantLight,feDropShadow,feFlood,feFuncA,feFuncB,feFuncG,feFuncR,feGaussianBlur,feImage,feMerge,feMergeNode,feMorphology,feOffset,fePointLight,feSpecularLighting,feSpotLight,feTile,feTurbulence,filter,foreignObject,g,hatch,hatchpath,image,line,linearGradient,marker,mask,mesh,meshgradient,meshpatch,meshrow,metadata,mpath,path,pattern,polygon,polyline,radialGradient,rect,set,solidcolor,stop,switch,symbol,text,textPath,title,tspan,unknown,use,view",gx="annotation,annotation-xml,maction,maligngroup,malignmark,math,menclose,merror,mfenced,mfrac,mfraction,mglyph,mi,mlabeledtr,mlongdiv,mmultiscripts,mn,mo,mover,mpadded,mphantom,mprescripts,mroot,mrow,ms,mscarries,mscarry,msgroup,msline,mspace,msqrt,msrow,mstack,mstyle,msub,msubsup,msup,mtable,mtd,mtext,mtr,munder,munderover,none,semantics",yx="area,base,br,col,embed,hr,img,input,link,meta,param,source,track,wbr",bx=br(px),vx=br(mx),wx=br(gx),_x=br(yx),Ex="itemscope,allowfullscreen,formnovalidate,ismap,nomodule,novalidate,readonly",Sx=br(Ex);function Uv(t){return!!t||t===""}function xx(t,e){if(t.length!==e.length)return!1;let r=!0;for(let n=0;r&&n<t.length;n++)r=rs(t[n],e[n]);return r}function rs(t,e){if(t===e)return!0;let r=Pm(t),n=Pm(e);if(r||n)return r&&n?t.getTime()===e.getTime():!1;if(r=ir(t),n=ir(e),r||n)return t===e;if(r=te(t),n=te(e),r||n)return r&&n?xx(t,e):!1;if(r=Ne(t),n=Ne(e),r||n){if(!r||!n)return!1;const s=Object.keys(t).length,i=Object.keys(e).length;if(s!==i)return!1;for(const o in t){const a=t.hasOwnProperty(o),l=e.hasOwnProperty(o);if(a&&!l||!a&&l||!rs(t[o],e[o]))return!1}}return String(t)===String(e)}function Pc(t,e){return t.findIndex(r=>rs(r,e))}const jv=t=>!!(t&&t.__v_isRef===!0),qv=t=>de(t)?t:t==null?"":te(t)||Ne(t)&&(t.toString===Dv||!ae(t.toString))?jv(t)?qv(t.value):JSON.stringify(t,Hv,2):String(t),Hv=(t,e)=>jv(e)?Hv(t,e.value):yi(e)?{[`Map(${e.size})`]:[...e.entries()].reduce((r,[n,s],i)=>(r[Lu(n,i)+" =>"]=s,r),{})}:Js(e)?{[`Set(${e.size})`]:[...e.values()].map(r=>Lu(r))}:ir(e)?Lu(e):Ne(e)&&!te(e)&&!Ac(e)?String(e):e,Lu=(t,e="")=>{var r;return ir(t)?`Symbol(${(r=t.description)!=null?r:e})`:t};function Cx(t){return t==null?"initial":typeof t=="string"?t===""?" ":t:String(t)}let Ot;class Pf{constructor(e=!1){this.detached=e,this._active=!0,this._on=0,this.effects=[],this.cleanups=[],this._isPaused=!1,this.parent=Ot,!e&&Ot&&(this.index=(Ot.scopes||(Ot.scopes=[])).push(this)-1)}get active(){return this._active}pause(){if(this._active){this._isPaused=!0;let e,r;if(this.scopes)for(e=0,r=this.scopes.length;e<r;e++)this.scopes[e].pause();for(e=0,r=this.effects.length;e<r;e++)this.effects[e].pause()}}resume(){if(this._active&&this._isPaused){this._isPaused=!1;let e,r;if(this.scopes)for(e=0,r=this.scopes.length;e<r;e++)this.scopes[e].resume();for(e=0,r=this.effects.length;e<r;e++)this.effects[e].resume()}}run(e){if(this._active){const r=Ot;try{return Ot=this,e()}finally{Ot=r}}}on(){++this._on===1&&(this.prevScope=Ot,Ot=this)}off(){this._on>0&&--this._on===0&&(Ot=this.prevScope,this.prevScope=void 0)}stop(e){if(this._active){this._active=!1;let r,n;for(r=0,n=this.effects.length;r<n;r++)this.effects[r].stop();for(this.effects.length=0,r=0,n=this.cleanups.length;r<n;r++)this.cleanups[r]();if(this.cleanups.length=0,this.scopes){for(r=0,n=this.scopes.length;r<n;r++)this.scopes[r].stop(!0);this.scopes.length=0}if(!this.detached&&this.parent&&!e){const s=this.parent.scopes.pop();s&&s!==this&&(this.parent.scopes[this.index]=s,s.index=this.index)}this.parent=void 0}}}function Ax(t){return new Pf(t)}function zv(){return Ot}function $x(t,e=!1){Ot&&Ot.cleanups.push(t)}let Ue;const Fu=new WeakSet;class jo{constructor(e){this.fn=e,this.deps=void 0,this.depsTail=void 0,this.flags=5,this.next=void 0,this.cleanup=void 0,this.scheduler=void 0,Ot&&Ot.active&&Ot.effects.push(this)}pause(){this.flags|=64}resume(){this.flags&64&&(this.flags&=-65,Fu.has(this)&&(Fu.delete(this),this.trigger()))}notify(){this.flags&2&&!(this.flags&32)||this.flags&8||Kv(this)}run(){if(!(this.flags&1))return this.fn();this.flags|=2,Rm(this),Gv(this);const e=Ue,r=Fr;Ue=this,Fr=!0;try{return this.fn()}finally{Jv(this),Ue=e,Fr=r,this.flags&=-3}}stop(){if(this.flags&1){for(let e=this.deps;e;e=e.nextDep)Nf(e);this.deps=this.depsTail=void 0,Rm(this),this.onStop&&this.onStop(),this.flags&=-2}}trigger(){this.flags&64?Fu.add(this):this.scheduler?this.scheduler():this.runIfDirty()}runIfDirty(){mh(this)&&this.run()}get dirty(){return mh(this)}}let Wv=0,Ao,$o;function Kv(t,e=!1){if(t.flags|=8,e){t.next=$o,$o=t;return}t.next=Ao,Ao=t}function Of(){Wv++}function Rf(){if(--Wv>0)return;if($o){let e=$o;for($o=void 0;e;){const r=e.next;e.next=void 0,e.flags&=-9,e=r}}let t;for(;Ao;){let e=Ao;for(Ao=void 0;e;){const r=e.next;if(e.next=void 0,e.flags&=-9,e.flags&1)try{e.trigger()}catch(n){t||(t=n)}e=r}}if(t)throw t}function Gv(t){for(let e=t.deps;e;e=e.nextDep)e.version=-1,e.prevActiveLink=e.dep.activeLink,e.dep.activeLink=e}function Jv(t){let e,r=t.depsTail,n=r;for(;n;){const s=n.prevDep;n.version===-1?(n===r&&(r=s),Nf(n),Tx(n)):e=n,n.dep.activeLink=n.prevActiveLink,n.prevActiveLink=void 0,n=s}t.deps=e,t.depsTail=r}function mh(t){for(let e=t.deps;e;e=e.nextDep)if(e.dep.version!==e.version||e.dep.computed&&(Xv(e.dep.computed)||e.dep.version!==e.version))return!0;return!!t._dirty}function Xv(t){if(t.flags&4&&!(t.flags&16)||(t.flags&=-17,t.globalVersion===qo)||(t.globalVersion=qo,!t.isSSR&&t.flags&128&&(!t.deps&&!t._dirty||!mh(t))))return;t.flags|=2;const e=t.dep,r=Ue,n=Fr;Ue=t,Fr=!0;try{Gv(t);const s=t.fn(t._value);(e.version===0||qt(s,t._value))&&(t.flags|=128,t._value=s,e.version++)}catch(s){throw e.version++,s}finally{Ue=r,Fr=n,Jv(t),t.flags&=-3}}function Nf(t,e=!1){const{dep:r,prevSub:n,nextSub:s}=t;if(n&&(n.nextSub=s,t.prevSub=void 0),s&&(s.prevSub=n,t.nextSub=void 0),r.subs===t&&(r.subs=n,!n&&r.computed)){r.computed.flags&=-5;for(let i=r.computed.deps;i;i=i.nextDep)Nf(i,!0)}!e&&!--r.sc&&r.map&&r.map.delete(r.key)}function Tx(t){const{prevDep:e,nextDep:r}=t;e&&(e.nextDep=r,t.prevDep=void 0),r&&(r.prevDep=e,t.nextDep=void 0)}function kx(t,e){t.effect instanceof jo&&(t=t.effect.fn);const r=new jo(t);e&&we(r,e);try{r.run()}catch(s){throw r.stop(),s}const n=r.run.bind(r);return n.effect=r,n}function Px(t){t.effect.stop()}let Fr=!0;const Yv=[];function wn(){Yv.push(Fr),Fr=!1}function _n(){const t=Yv.pop();Fr=t===void 0?!0:t}function Rm(t){const{cleanup:e}=t;if(t.cleanup=void 0,e){const r=Ue;Ue=void 0;try{e()}finally{Ue=r}}}let qo=0;class Ox{constructor(e,r){this.sub=e,this.dep=r,this.version=r.version,this.nextDep=this.prevDep=this.nextSub=this.prevSub=this.prevActiveLink=void 0}}class Oc{constructor(e){this.computed=e,this.version=0,this.activeLink=void 0,this.subs=void 0,this.map=void 0,this.key=void 0,this.sc=0,this.__v_skip=!0}track(e){if(!Ue||!Fr||Ue===this.computed)return;let r=this.activeLink;if(r===void 0||r.sub!==Ue)r=this.activeLink=new Ox(Ue,this),Ue.deps?(r.prevDep=Ue.depsTail,Ue.depsTail.nextDep=r,Ue.depsTail=r):Ue.deps=Ue.depsTail=r,Qv(r);else if(r.version===-1&&(r.version=this.version,r.nextDep)){const n=r.nextDep;n.prevDep=r.prevDep,r.prevDep&&(r.prevDep.nextDep=n),r.prevDep=Ue.depsTail,r.nextDep=void 0,Ue.depsTail.nextDep=r,Ue.depsTail=r,Ue.deps===r&&(Ue.deps=n)}return r}trigger(e){this.version++,qo++,this.notify(e)}notify(e){Of();try{for(let r=this.subs;r;r=r.prevSub)r.sub.notify()&&r.sub.dep.notify()}finally{Rf()}}}function Qv(t){if(t.dep.sc++,t.sub.flags&4){const e=t.dep.computed;if(e&&!t.dep.subs){e.flags|=20;for(let n=e.deps;n;n=n.nextDep)Qv(n)}const r=t.dep.subs;r!==t&&(t.prevSub=r,r&&(r.nextSub=t)),t.dep.subs=t}}const Il=new WeakMap,ks=Symbol(""),gh=Symbol(""),Ho=Symbol("");function Nt(t,e,r){if(Fr&&Ue){let n=Il.get(t);n||Il.set(t,n=new Map);let s=n.get(r);s||(n.set(r,s=new Oc),s.map=n,s.key=r),s.track()}}function pn(t,e,r,n,s,i){const o=Il.get(t);if(!o){qo++;return}const a=l=>{l&&l.trigger()};if(Of(),e==="clear")o.forEach(a);else{const l=te(t),u=l&&kf(r);if(l&&r==="length"){const c=Number(n);o.forEach((d,p)=>{(p==="length"||p===Ho||!ir(p)&&p>=c)&&a(d)})}else switch((r!==void 0||o.has(void 0))&&a(o.get(r)),u&&a(o.get(Ho)),e){case"add":l?u&&a(o.get("length")):(a(o.get(ks)),yi(t)&&a(o.get(gh)));break;case"delete":l||(a(o.get(ks)),yi(t)&&a(o.get(gh)));break;case"set":yi(t)&&a(o.get(ks));break}}Rf()}function Rx(t,e){const r=Il.get(t);return r&&r.get(e)}function ni(t){const e=$e(t);return e===t?e:(Nt(e,"iterate",Ho),gr(t)?e:e.map(Ct))}function Rc(t){return Nt(t=$e(t),"iterate",Ho),t}const Nx={__proto__:null,[Symbol.iterator](){return Mu(this,Symbol.iterator,Ct)},concat(...t){return ni(this).concat(...t.map(e=>te(e)?ni(e):e))},entries(){return Mu(this,"entries",t=>(t[1]=Ct(t[1]),t))},every(t,e){return an(this,"every",t,e,void 0,arguments)},filter(t,e){return an(this,"filter",t,e,r=>r.map(Ct),arguments)},find(t,e){return an(this,"find",t,e,Ct,arguments)},findIndex(t,e){return an(this,"findIndex",t,e,void 0,arguments)},findLast(t,e){return an(this,"findLast",t,e,Ct,arguments)},findLastIndex(t,e){return an(this,"findLastIndex",t,e,void 0,arguments)},forEach(t,e){return an(this,"forEach",t,e,void 0,arguments)},includes(...t){return Du(this,"includes",t)},indexOf(...t){return Du(this,"indexOf",t)},join(t){return ni(this).join(t)},lastIndexOf(...t){return Du(this,"lastIndexOf",t)},map(t,e){return an(this,"map",t,e,void 0,arguments)},pop(){return lo(this,"pop")},push(...t){return lo(this,"push",t)},reduce(t,...e){return Nm(this,"reduce",t,e)},reduceRight(t,...e){return Nm(this,"reduceRight",t,e)},shift(){return lo(this,"shift")},some(t,e){return an(this,"some",t,e,void 0,arguments)},splice(...t){return lo(this,"splice",t)},toReversed(){return ni(this).toReversed()},toSorted(t){return ni(this).toSorted(t)},toSpliced(...t){return ni(this).toSpliced(...t)},unshift(...t){return lo(this,"unshift",t)},values(){return Mu(this,"values",Ct)}};function Mu(t,e,r){const n=Rc(t),s=n[e]();return n!==t&&!gr(t)&&(s._next=s.next,s.next=()=>{const i=s._next();return i.done||(i.value=r(i.value)),i}),s}const Ix=Array.prototype;function an(t,e,r,n,s,i){const o=Rc(t),a=o!==t&&!gr(t),l=o[e];if(l!==Ix[e]){const d=l.apply(t,i);return a?Ct(d):d}let u=r;o!==t&&(a?u=function(d,p){return r.call(this,Ct(d),p,t)}:r.length>2&&(u=function(d,p){return r.call(this,d,p,t)}));const c=l.call(o,u,n);return a&&s?s(c):c}function Nm(t,e,r,n){const s=Rc(t);let i=r;return s!==t&&(gr(t)?r.length>3&&(i=function(o,a,l){return r.call(this,o,a,l,t)}):i=function(o,a,l){return r.call(this,o,Ct(a),l,t)}),s[e](i,...n)}function Du(t,e,r){const n=$e(t);Nt(n,"iterate",Ho);const s=n[e](...r);return(s===-1||s===!1)&&Lc(r[0])?(r[0]=$e(r[0]),n[e](...r)):s}function lo(t,e,r=[]){wn(),Of();const n=$e(t)[e].apply(t,r);return Rf(),_n(),n}const Lx=br("__proto__,__v_isRef,__isVue"),Zv=new Set(Object.getOwnPropertyNames(Symbol).filter(t=>t!=="arguments"&&t!=="caller").map(t=>Symbol[t]).filter(ir));function Fx(t){ir(t)||(t=String(t));const e=$e(this);return Nt(e,"has",t),e.hasOwnProperty(t)}class ew{constructor(e=!1,r=!1){this._isReadonly=e,this._isShallow=r}get(e,r,n){if(r==="__v_skip")return e.__v_skip;const s=this._isReadonly,i=this._isShallow;if(r==="__v_isReactive")return!s;if(r==="__v_isReadonly")return s;if(r==="__v_isShallow")return i;if(r==="__v_raw")return n===(s?i?ow:iw:i?sw:nw).get(e)||Object.getPrototypeOf(e)===Object.getPrototypeOf(n)?e:void 0;const o=te(e);if(!s){let l;if(o&&(l=Nx[r]))return l;if(r==="hasOwnProperty")return Fx}const a=Reflect.get(e,r,ft(e)?e:n);if((ir(r)?Zv.has(r):Lx(r))||(s||Nt(e,"get",r),i))return a;if(ft(a)){const l=o&&kf(r)?a:a.value;return s&&Ne(l)?Ll(l):l}return Ne(a)?s?Ll(a):Wi(a):a}}class tw extends ew{constructor(e=!1){super(!1,e)}set(e,r,n,s){let i=e[r];if(!this._isShallow){const l=En(i);if(!gr(n)&&!En(n)&&(i=$e(i),n=$e(n)),!te(e)&&ft(i)&&!ft(n))return l||(i.value=n),!0}const o=te(e)&&kf(r)?Number(r)<e.length:Pe(e,r),a=Reflect.set(e,r,n,ft(e)?e:s);return e===$e(s)&&(o?qt(n,i)&&pn(e,"set",r,n):pn(e,"add",r,n)),a}deleteProperty(e,r){const n=Pe(e,r);e[r];const s=Reflect.deleteProperty(e,r);return s&&n&&pn(e,"delete",r,void 0),s}has(e,r){const n=Reflect.has(e,r);return(!ir(r)||!Zv.has(r))&&Nt(e,"has",r),n}ownKeys(e){return Nt(e,"iterate",te(e)?"length":ks),Reflect.ownKeys(e)}}class rw extends ew{constructor(e=!1){super(!0,e)}set(e,r){return!0}deleteProperty(e,r){return!0}}const Mx=new tw,Dx=new rw,Vx=new tw(!0),Bx=new rw(!0),yh=t=>t,ja=t=>Reflect.getPrototypeOf(t);function Ux(t,e,r){return function(...n){const s=this.__v_raw,i=$e(s),o=yi(i),a=t==="entries"||t===Symbol.iterator&&o,l=t==="keys"&&o,u=s[t](...n),c=r?yh:e?Ml:Ct;return!e&&Nt(i,"iterate",l?gh:ks),{next(){const{value:d,done:p}=u.next();return p?{value:d,done:p}:{value:a?[c(d[0]),c(d[1])]:c(d),done:p}},[Symbol.iterator](){return this}}}}function qa(t){return function(...e){return t==="delete"?!1:t==="clear"?void 0:this}}function jx(t,e){const r={get(s){const i=this.__v_raw,o=$e(i),a=$e(s);t||(qt(s,a)&&Nt(o,"get",s),Nt(o,"get",a));const{has:l}=ja(o),u=e?yh:t?Ml:Ct;if(l.call(o,s))return u(i.get(s));if(l.call(o,a))return u(i.get(a));i!==o&&i.get(s)},get size(){const s=this.__v_raw;return!t&&Nt($e(s),"iterate",ks),s.size},has(s){const i=this.__v_raw,o=$e(i),a=$e(s);return t||(qt(s,a)&&Nt(o,"has",s),Nt(o,"has",a)),s===a?i.has(s):i.has(s)||i.has(a)},forEach(s,i){const o=this,a=o.__v_raw,l=$e(a),u=e?yh:t?Ml:Ct;return!t&&Nt(l,"iterate",ks),a.forEach((c,d)=>s.call(i,u(c),u(d),o))}};return we(r,t?{add:qa("add"),set:qa("set"),delete:qa("delete"),clear:qa("clear")}:{add(s){!e&&!gr(s)&&!En(s)&&(s=$e(s));const i=$e(this);return ja(i).has.call(i,s)||(i.add(s),pn(i,"add",s,s)),this},set(s,i){!e&&!gr(i)&&!En(i)&&(i=$e(i));const o=$e(this),{has:a,get:l}=ja(o);let u=a.call(o,s);u||(s=$e(s),u=a.call(o,s));const c=l.call(o,s);return o.set(s,i),u?qt(i,c)&&pn(o,"set",s,i):pn(o,"add",s,i),this},delete(s){const i=$e(this),{has:o,get:a}=ja(i);let l=o.call(i,s);l||(s=$e(s),l=o.call(i,s)),a&&a.call(i,s);const u=i.delete(s);return l&&pn(i,"delete",s,void 0),u},clear(){const s=$e(this),i=s.size!==0,o=s.clear();return i&&pn(s,"clear",void 0,void 0),o}}),["keys","values","entries",Symbol.iterator].forEach(s=>{r[s]=Ux(s,t,e)}),r}function Nc(t,e){const r=jx(t,e);return(n,s,i)=>s==="__v_isReactive"?!t:s==="__v_isReadonly"?t:s==="__v_raw"?n:Reflect.get(Pe(r,s)&&s in n?r:n,s,i)}const qx={get:Nc(!1,!1)},Hx={get:Nc(!1,!0)},zx={get:Nc(!0,!1)},Wx={get:Nc(!0,!0)},nw=new WeakMap,sw=new WeakMap,iw=new WeakMap,ow=new WeakMap;function Kx(t){switch(t){case"Object":case"Array":return 1;case"Map":case"Set":case"WeakMap":case"WeakSet":return 2;default:return 0}}function Gx(t){return t.__v_skip||!Object.isExtensible(t)?0:Kx(nx(t))}function Wi(t){return En(t)?t:Ic(t,!1,Mx,qx,nw)}function aw(t){return Ic(t,!1,Vx,Hx,sw)}function Ll(t){return Ic(t,!0,Dx,zx,iw)}function Jx(t){return Ic(t,!0,Bx,Wx,ow)}function Ic(t,e,r,n,s){if(!Ne(t)||t.__v_raw&&!(e&&t.__v_isReactive))return t;const i=Gx(t);if(i===0)return t;const o=s.get(t);if(o)return o;const a=new Proxy(t,i===2?n:r);return s.set(t,a),a}function Yn(t){return En(t)?Yn(t.__v_raw):!!(t&&t.__v_isReactive)}function En(t){return!!(t&&t.__v_isReadonly)}function gr(t){return!!(t&&t.__v_isShallow)}function Lc(t){return t?!!t.__v_raw:!1}function $e(t){const e=t&&t.__v_raw;return e?$e(e):t}function Fl(t){return!Pe(t,"__v_skip")&&Object.isExtensible(t)&&Vv(t,"__v_skip",!0),t}const Ct=t=>Ne(t)?Wi(t):t,Ml=t=>Ne(t)?Ll(t):t;function ft(t){return t?t.__v_isRef===!0:!1}function Qn(t){return lw(t,!1)}function If(t){return lw(t,!0)}function lw(t,e){return ft(t)?t:new Xx(t,e)}class Xx{constructor(e,r){this.dep=new Oc,this.__v_isRef=!0,this.__v_isShallow=!1,this._rawValue=r?e:$e(e),this._value=r?e:Ct(e),this.__v_isShallow=r}get value(){return this.dep.track(),this._value}set value(e){const r=this._rawValue,n=this.__v_isShallow||gr(e)||En(e);e=n?e:$e(e),qt(e,r)&&(this._rawValue=e,this._value=n?e:Ct(e),this.dep.trigger())}}function Yx(t){t.dep&&t.dep.trigger()}function Fc(t){return ft(t)?t.value:t}function Qx(t){return ae(t)?t():Fc(t)}const Zx={get:(t,e,r)=>e==="__v_raw"?t:Fc(Reflect.get(t,e,r)),set:(t,e,r,n)=>{const s=t[e];return ft(s)&&!ft(r)?(s.value=r,!0):Reflect.set(t,e,r,n)}};function Lf(t){return Yn(t)?t:new Proxy(t,Zx)}class eC{constructor(e){this.__v_isRef=!0,this._value=void 0;const r=this.dep=new Oc,{get:n,set:s}=e(r.track.bind(r),r.trigger.bind(r));this._get=n,this._set=s}get value(){return this._value=this._get()}set value(e){this._set(e)}}function cw(t){return new eC(t)}function tC(t){const e=te(t)?new Array(t.length):{};for(const r in t)e[r]=uw(t,r);return e}class rC{constructor(e,r,n){this._object=e,this._key=r,this._defaultValue=n,this.__v_isRef=!0,this._value=void 0}get value(){const e=this._object[this._key];return this._value=e===void 0?this._defaultValue:e}set value(e){this._object[this._key]=e}get dep(){return Rx($e(this._object),this._key)}}class nC{constructor(e){this._getter=e,this.__v_isRef=!0,this.__v_isReadonly=!0,this._value=void 0}get value(){return this._value=this._getter()}}function sC(t,e,r){return ft(t)?t:ae(t)?new nC(t):Ne(t)&&arguments.length>1?uw(t,e,r):Qn(t)}function uw(t,e,r){const n=t[e];return ft(n)?n:new rC(t,e,r)}class iC{constructor(e,r,n){this.fn=e,this.setter=r,this._value=void 0,this.dep=new Oc(this),this.__v_isRef=!0,this.deps=void 0,this.depsTail=void 0,this.flags=16,this.globalVersion=qo-1,this.next=void 0,this.effect=this,this.__v_isReadonly=!r,this.isSSR=n}notify(){if(this.flags|=16,!(this.flags&8)&&Ue!==this)return Kv(this,!0),!0}get value(){const e=this.dep.track();return Xv(this),e&&(e.version=this.dep.version),this._value}set value(e){this.setter&&this.setter(e)}}function oC(t,e,r=!1){let n,s;return ae(t)?n=t:(n=t.get,s=t.set),new iC(n,s,r)}const aC={GET:"get",HAS:"has",ITERATE:"iterate"},lC={SET:"set",ADD:"add",DELETE:"delete",CLEAR:"clear"},Ha={},Dl=new WeakMap;let qn;function cC(){return qn}function dw(t,e=!1,r=qn){if(r){let n=Dl.get(r);n||Dl.set(r,n=[]),n.push(t)}}function uC(t,e,r=Se){const{immediate:n,deep:s,once:i,scheduler:o,augmentJob:a,call:l}=r,u=_=>s?_:gr(_)||s===!1||s===0?mn(_,1):mn(_);let c,d,p,f,h=!1,m=!1;if(ft(t)?(d=()=>t.value,h=gr(t)):Yn(t)?(d=()=>u(t),h=!0):te(t)?(m=!0,h=t.some(_=>Yn(_)||gr(_)),d=()=>t.map(_=>{if(ft(_))return _.value;if(Yn(_))return u(_);if(ae(_))return l?l(_,2):_()})):ae(t)?e?d=l?()=>l(t,2):t:d=()=>{if(p){wn();try{p()}finally{_n()}}const _=qn;qn=c;try{return l?l(t,3,[f]):t(f)}finally{qn=_}}:d=Tt,e&&s){const _=d,S=s===!0?1/0:s;d=()=>mn(_(),S)}const g=zv(),v=()=>{c.stop(),g&&g.active&&$f(g.effects,c)};if(i&&e){const _=e;e=(...S)=>{_(...S),v()}}let b=m?new Array(t.length).fill(Ha):Ha;const w=_=>{if(!(!(c.flags&1)||!c.dirty&&!_))if(e){const S=c.run();if(s||h||(m?S.some((k,O)=>qt(k,b[O])):qt(S,b))){p&&p();const k=qn;qn=c;try{const O=[S,b===Ha?void 0:m&&b[0]===Ha?[]:b,f];b=S,l?l(e,3,O):e(...O)}finally{qn=k}}}else c.run()};return a&&a(w),c=new jo(d),c.scheduler=o?()=>o(w,!1):w,f=_=>dw(_,!1,c),p=c.onStop=()=>{const _=Dl.get(c);if(_){if(l)l(_,4);else for(const S of _)S();Dl.delete(c)}},e?n?w(!0):b=c.run():o?o(w.bind(null,!0),!0):c.run(),v.pause=c.pause.bind(c),v.resume=c.resume.bind(c),v.stop=v,v}function mn(t,e=1/0,r){if(e<=0||!Ne(t)||t.__v_skip||(r=r||new Map,(r.get(t)||0)>=e))return t;if(r.set(t,e),e--,ft(t))mn(t.value,e,r);else if(te(t))for(let n=0;n<t.length;n++)mn(t[n],e,r);else if(Js(t)||yi(t))t.forEach(n=>{mn(n,e,r)});else if(Ac(t)){for(const n in t)mn(t[n],e,r);for(const n of Object.getOwnPropertySymbols(t))Object.prototype.propertyIsEnumerable.call(t,n)&&mn(t[n],e,r)}return t}const hw=[];function dC(t){hw.push(t)}function hC(){hw.pop()}function fC(t,e){}const pC={SETUP_FUNCTION:0,0:"SETUP_FUNCTION",RENDER_FUNCTION:1,1:"RENDER_FUNCTION",NATIVE_EVENT_HANDLER:5,5:"NATIVE_EVENT_HANDLER",COMPONENT_EVENT_HANDLER:6,6:"COMPONENT_EVENT_HANDLER",VNODE_HOOK:7,7:"VNODE_HOOK",DIRECTIVE_HOOK:8,8:"DIRECTIVE_HOOK",TRANSITION_HOOK:9,9:"TRANSITION_HOOK",APP_ERROR_HANDLER:10,10:"APP_ERROR_HANDLER",APP_WARN_HANDLER:11,11:"APP_WARN_HANDLER",FUNCTION_REF:12,12:"FUNCTION_REF",ASYNC_COMPONENT_LOADER:13,13:"ASYNC_COMPONENT_LOADER",SCHEDULER:14,14:"SCHEDULER",COMPONENT_UPDATE:15,15:"COMPONENT_UPDATE",APP_UNMOUNT_CLEANUP:16,16:"APP_UNMOUNT_CLEANUP"},mC={sp:"serverPrefetch hook",bc:"beforeCreate hook",c:"created hook",bm:"beforeMount hook",m:"mounted hook",bu:"beforeUpdate hook",u:"updated",bum:"beforeUnmount hook",um:"unmounted hook",a:"activated hook",da:"deactivated hook",ec:"errorCaptured hook",rtc:"renderTracked hook",rtg:"renderTriggered hook",0:"setup function",1:"render function",2:"watcher getter",3:"watcher callback",4:"watcher cleanup function",5:"native event handler",6:"component event handler",7:"vnode hook",8:"directive hook",9:"transition hook",10:"app errorHandler",11:"app warnHandler",12:"ref function",13:"async component loader",14:"scheduler flush",15:"component update",16:"app unmount cleanup function"};function Ki(t,e,r,n){try{return n?t(...n):t()}catch(s){Ys(s,e,r)}}function $r(t,e,r,n){if(ae(t)){const s=Ki(t,e,r,n);return s&&Tf(s)&&s.catch(i=>{Ys(i,e,r)}),s}if(te(t)){const s=[];for(let i=0;i<t.length;i++)s.push($r(t[i],e,r,n));return s}}function Ys(t,e,r,n=!0){const s=e?e.vnode:null,{errorHandler:i,throwUnhandledErrorInProduction:o}=e&&e.appContext.config||Se;if(e){let a=e.parent;const l=e.proxy,u=`https://vuejs.org/error-reference/#runtime-${r}`;for(;a;){const c=a.ec;if(c){for(let d=0;d<c.length;d++)if(c[d](t,l,u)===!1)return}a=a.parent}if(i){wn(),Ki(i,null,10,[t,l,u]),_n();return}}gC(t,r,s,n,o)}function gC(t,e,r,n=!0,s=!1){if(s)throw t;console.error(t)}const Ht=[];let Gr=-1;const wi=[];let Hn=null,li=0;const fw=Promise.resolve();let Vl=null;function Mc(t){const e=Vl||fw;return t?e.then(this?t.bind(this):t):e}function yC(t){let e=Gr+1,r=Ht.length;for(;e<r;){const n=e+r>>>1,s=Ht[n],i=Wo(s);i<t||i===t&&s.flags&2?e=n+1:r=n}return e}function Ff(t){if(!(t.flags&1)){const e=Wo(t),r=Ht[Ht.length-1];!r||!(t.flags&2)&&e>=Wo(r)?Ht.push(t):Ht.splice(yC(e),0,t),t.flags|=1,pw()}}function pw(){Vl||(Vl=fw.then(mw))}function zo(t){te(t)?wi.push(...t):Hn&&t.id===-1?Hn.splice(li+1,0,t):t.flags&1||(wi.push(t),t.flags|=1),pw()}function Im(t,e,r=Gr+1){for(;r<Ht.length;r++){const n=Ht[r];if(n&&n.flags&2){if(t&&n.id!==t.uid)continue;Ht.splice(r,1),r--,n.flags&4&&(n.flags&=-2),n(),n.flags&4||(n.flags&=-2)}}}function Bl(t){if(wi.length){const e=[...new Set(wi)].sort((r,n)=>Wo(r)-Wo(n));if(wi.length=0,Hn){Hn.push(...e);return}for(Hn=e,li=0;li<Hn.length;li++){const r=Hn[li];r.flags&4&&(r.flags&=-2),r.flags&8||r(),r.flags&=-2}Hn=null,li=0}}const Wo=t=>t.id==null?t.flags&2?-1:1/0:t.id;function mw(t){try{for(Gr=0;Gr<Ht.length;Gr++){const e=Ht[Gr];e&&!(e.flags&8)&&(e.flags&4&&(e.flags&=-2),Ki(e,e.i,e.i?15:14),e.flags&4||(e.flags&=-2))}}finally{for(;Gr<Ht.length;Gr++){const e=Ht[Gr];e&&(e.flags&=-2)}Gr=-1,Ht.length=0,Bl(),Vl=null,(Ht.length||wi.length)&&mw()}}let ci,za=[];function gw(t,e){var r,n;ci=t,ci?(ci.enabled=!0,za.forEach(({event:s,args:i})=>ci.emit(s,...i)),za=[]):typeof window<"u"&&window.HTMLElement&&!((n=(r=window.navigator)==null?void 0:r.userAgent)!=null&&n.includes("jsdom"))?((e.__VUE_DEVTOOLS_HOOK_REPLAY__=e.__VUE_DEVTOOLS_HOOK_REPLAY__||[]).push(i=>{gw(i,e)}),setTimeout(()=>{ci||(e.__VUE_DEVTOOLS_HOOK_REPLAY__=null,za=[])},3e3)):za=[]}let $t=null,Dc=null;function Ko(t){const e=$t;return $t=t,Dc=t&&t.type.__scopeId||null,e}function bC(t){Dc=t}function vC(){Dc=null}const wC=t=>Mf;function Mf(t,e=$t,r){if(!e||t._n)return t;const n=(...s)=>{n._d&&Yo(-1);const i=Ko(e);let o;try{o=t(...s)}finally{Ko(i),n._d&&Yo(1)}return o};return n._n=!0,n._c=!0,n._d=!0,n}function _C(t,e){if($t===null)return t;const r=ya($t),n=t.dirs||(t.dirs=[]);for(let s=0;s<e.length;s++){let[i,o,a,l=Se]=e[s];i&&(ae(i)&&(i={mounted:i,updated:i}),i.deep&&mn(o),n.push({dir:i,instance:r,value:o,oldValue:void 0,arg:a,modifiers:l}))}return t}function Jr(t,e,r,n){const s=t.dirs,i=e&&e.dirs;for(let o=0;o<s.length;o++){const a=s[o];i&&(a.oldValue=i[o].value);let l=a.dir[n];l&&(wn(),$r(l,r,8,[t.el,a,t,e]),_n())}}const yw=Symbol("_vte"),bw=t=>t.__isTeleport,To=t=>t&&(t.disabled||t.disabled===""),Lm=t=>t&&(t.defer||t.defer===""),Fm=t=>typeof SVGElement<"u"&&t instanceof SVGElement,Mm=t=>typeof MathMLElement=="function"&&t instanceof MathMLElement,bh=(t,e)=>{const r=t&&t.to;return de(r)?e?e(r):null:r},vw={name:"Teleport",__isTeleport:!0,process(t,e,r,n,s,i,o,a,l,u){const{mc:c,pc:d,pbc:p,o:{insert:f,querySelector:h,createText:m,createComment:g}}=u,v=To(e.props);let{shapeFlag:b,children:w,dynamicChildren:_}=e;if(t==null){const S=e.el=m(""),k=e.anchor=m("");f(S,r,n),f(k,r,n);const O=(C,A)=>{b&16&&c(w,C,A,s,i,o,a,l)},R=()=>{const C=e.target=bh(e.props,h),A=ww(C,e,m,f);C&&(o!=="svg"&&Fm(C)?o="svg":o!=="mathml"&&Mm(C)&&(o="mathml"),s&&s.isCE&&(s.ce._teleportTargets||(s.ce._teleportTargets=new Set)).add(C),v||(O(C,A),dl(e,!1)))};v&&(O(r,k),dl(e,!0)),Lm(e.props)?(e.el.__isMounted=!1,dt(()=>{R(),delete e.el.__isMounted},i)):R()}else{if(Lm(e.props)&&t.el.__isMounted===!1){dt(()=>{vw.process(t,e,r,n,s,i,o,a,l,u)},i);return}e.el=t.el,e.targetStart=t.targetStart;const S=e.anchor=t.anchor,k=e.target=t.target,O=e.targetAnchor=t.targetAnchor,R=To(t.props),C=R?r:k,A=R?S:O;if(o==="svg"||Fm(k)?o="svg":(o==="mathml"||Mm(k))&&(o="mathml"),_?(p(t.dynamicChildren,_,C,s,i,o,a),Gf(t,e,!0)):l||d(t,e,C,A,s,i,o,a,!1),v)R?e.props&&t.props&&e.props.to!==t.props.to&&(e.props.to=t.props.to):Wa(e,r,S,u,1);else if((e.props&&e.props.to)!==(t.props&&t.props.to)){const j=e.target=bh(e.props,h);j&&Wa(e,j,null,u,0)}else R&&Wa(e,k,O,u,1);dl(e,v)}},remove(t,e,r,{um:n,o:{remove:s}},i){const{shapeFlag:o,children:a,anchor:l,targetStart:u,targetAnchor:c,target:d,props:p}=t;if(d&&(s(u),s(c)),i&&s(l),o&16){const f=i||!To(p);for(let h=0;h<a.length;h++){const m=a[h];n(m,e,r,f,!!m.dynamicChildren)}}},move:Wa,hydrate:EC};function Wa(t,e,r,{o:{insert:n},m:s},i=2){i===0&&n(t.targetAnchor,e,r);const{el:o,anchor:a,shapeFlag:l,children:u,props:c}=t,d=i===2;if(d&&n(o,e,r),(!d||To(c))&&l&16)for(let p=0;p<u.length;p++)s(u[p],e,r,2);d&&n(a,e,r)}function EC(t,e,r,n,s,i,{o:{nextSibling:o,parentNode:a,querySelector:l,insert:u,createText:c}},d){function p(m,g,v,b){g.anchor=d(o(m),g,a(m),r,n,s,i),g.targetStart=v,g.targetAnchor=b}const f=e.target=bh(e.props,l),h=To(e.props);if(f){const m=f._lpa||f.firstChild;if(e.shapeFlag&16)if(h)p(t,e,m,m&&o(m));else{e.anchor=o(t);let g=m;for(;g;){if(g&&g.nodeType===8){if(g.data==="teleport start anchor")e.targetStart=g;else if(g.data==="teleport anchor"){e.targetAnchor=g,f._lpa=e.targetAnchor&&o(e.targetAnchor);break}}g=o(g)}e.targetAnchor||ww(f,e,c,u),d(m&&o(m),e,f,r,n,s,i)}dl(e,h)}else h&&e.shapeFlag&16&&p(t,e,t,o(t));return e.anchor&&o(e.anchor)}const SC=vw;function dl(t,e){const r=t.ctx;if(r&&r.ut){let n,s;for(e?(n=t.el,s=t.anchor):(n=t.targetStart,s=t.targetAnchor);n&&n!==s;)n.nodeType===1&&n.setAttribute("data-v-owner",r.uid),n=n.nextSibling;r.ut()}}function ww(t,e,r,n){const s=e.targetStart=r(""),i=e.targetAnchor=r("");return s[yw]=i,t&&(n(s,t),n(i,t)),i}const fn=Symbol("_leaveCb"),Ka=Symbol("_enterCb");function Df(){const t={isMounted:!1,isLeaving:!1,isUnmounting:!1,leavingVNodes:new Map};return ma(()=>{t.isMounted=!0}),jc(()=>{t.isUnmounting=!0}),t}const Er=[Function,Array],Vf={mode:String,appear:Boolean,persisted:Boolean,onBeforeEnter:Er,onEnter:Er,onAfterEnter:Er,onEnterCancelled:Er,onBeforeLeave:Er,onLeave:Er,onAfterLeave:Er,onLeaveCancelled:Er,onBeforeAppear:Er,onAppear:Er,onAfterAppear:Er,onAppearCancelled:Er},_w=t=>{const e=t.subTree;return e.component?_w(e.component):e},xC={name:"BaseTransition",props:Vf,setup(t,{slots:e}){const r=or(),n=Df();return()=>{const s=e.default&&Vc(e.default(),!0);if(!s||!s.length)return;const i=Ew(s),o=$e(t),{mode:a}=o;if(n.isLeaving)return Vu(i);const l=Dm(i);if(!l)return Vu(i);let u=Ci(l,o,n,r,d=>u=d);l.type!==nt&&Sn(l,u);let c=r.subTree&&Dm(r.subTree);if(c&&c.type!==nt&&!Lr(c,l)&&_w(r).type!==nt){let d=Ci(c,o,n,r);if(Sn(c,d),a==="out-in"&&l.type!==nt)return n.isLeaving=!0,d.afterLeave=()=>{n.isLeaving=!1,r.job.flags&8||r.update(),delete d.afterLeave,c=void 0},Vu(i);a==="in-out"&&l.type!==nt?d.delayLeave=(p,f,h)=>{const m=xw(n,c);m[String(c.key)]=c,p[fn]=()=>{f(),p[fn]=void 0,delete u.delayedLeave,c=void 0},u.delayedLeave=()=>{h(),delete u.delayedLeave,c=void 0}}:c=void 0}else c&&(c=void 0);return i}}};function Ew(t){let e=t[0];if(t.length>1){for(const r of t)if(r.type!==nt){e=r;break}}return e}const Sw=xC;function xw(t,e){const{leavingVNodes:r}=t;let n=r.get(e.type);return n||(n=Object.create(null),r.set(e.type,n)),n}function Ci(t,e,r,n,s){const{appear:i,mode:o,persisted:a=!1,onBeforeEnter:l,onEnter:u,onAfterEnter:c,onEnterCancelled:d,onBeforeLeave:p,onLeave:f,onAfterLeave:h,onLeaveCancelled:m,onBeforeAppear:g,onAppear:v,onAfterAppear:b,onAppearCancelled:w}=e,_=String(t.key),S=xw(r,t),k=(C,A)=>{C&&$r(C,n,9,A)},O=(C,A)=>{const j=A[1];k(C,A),te(C)?C.every($=>$.length<=1)&&j():C.length<=1&&j()},R={mode:o,persisted:a,beforeEnter(C){let A=l;if(!r.isMounted)if(i)A=g||l;else return;C[fn]&&C[fn](!0);const j=S[_];j&&Lr(t,j)&&j.el[fn]&&j.el[fn](),k(A,[C])},enter(C){let A=u,j=c,$=d;if(!r.isMounted)if(i)A=v||u,j=b||c,$=w||d;else return;let y=!1;const I=C[Ka]=q=>{y||(y=!0,q?k($,[C]):k(j,[C]),R.delayedLeave&&R.delayedLeave(),C[Ka]=void 0)};A?O(A,[C,I]):I()},leave(C,A){const j=String(t.key);if(C[Ka]&&C[Ka](!0),r.isUnmounting)return A();k(p,[C]);let $=!1;const y=C[fn]=I=>{$||($=!0,A(),I?k(m,[C]):k(h,[C]),C[fn]=void 0,S[j]===t&&delete S[j])};S[j]=t,f?O(f,[C,y]):y()},clone(C){const A=Ci(C,e,r,n,s);return s&&s(A),A}};return R}function Vu(t){if(pa(t))return t=Yr(t),t.children=null,t}function Dm(t){if(!pa(t))return bw(t.type)&&t.children?Ew(t.children):t;if(t.component)return t.component.subTree;const{shapeFlag:e,children:r}=t;if(r){if(e&16)return r[0];if(e&32&&ae(r.default))return r.default()}}function Sn(t,e){t.shapeFlag&6&&t.component?(t.transition=e,Sn(t.component.subTree,e)):t.shapeFlag&128?(t.ssContent.transition=e.clone(t.ssContent),t.ssFallback.transition=e.clone(t.ssFallback)):t.transition=e}function Vc(t,e=!1,r){let n=[],s=0;for(let i=0;i<t.length;i++){let o=t[i];const a=r==null?o.key:String(r)+String(o.key!=null?o.key:i);o.type===bt?(o.patchFlag&128&&s++,n=n.concat(Vc(o.children,e,a))):(e||o.type!==nt)&&n.push(a!=null?Yr(o,{key:a}):o)}if(s>1)for(let i=0;i<n.length;i++)n[i].patchFlag=-2;return n}function Gi(t,e){return ae(t)?we({name:t.name},e,{setup:t}):t}function CC(){const t=or();return t?(t.appContext.config.idPrefix||"v")+"-"+t.ids[0]+t.ids[1]++:""}function Bf(t){t.ids=[t.ids[0]+t.ids[2]+++"-",0,0]}function AC(t){const e=or(),r=If(null);if(e){const s=e.refs===Se?e.refs={}:e.refs;Object.defineProperty(s,t,{enumerable:!0,get:()=>r.value,set:i=>r.value=i})}return r}const Ul=new WeakMap;function _i(t,e,r,n,s=!1){if(te(t)){t.forEach((h,m)=>_i(h,e&&(te(e)?e[m]:e),r,n,s));return}if(Zn(n)&&!s){n.shapeFlag&512&&n.type.__asyncResolved&&n.component.subTree.component&&_i(t,e,r,n.component.subTree);return}const i=n.shapeFlag&4?ya(n.component):n.el,o=s?null:i,{i:a,r:l}=t,u=e&&e.r,c=a.refs===Se?a.refs={}:a.refs,d=a.setupState,p=$e(d),f=d===Se?fi:h=>Pe(p,h);if(u!=null&&u!==l){if(Vm(e),de(u))c[u]=null,f(u)&&(d[u]=null);else if(ft(u)){u.value=null;const h=e;h.k&&(c[h.k]=null)}}if(ae(l))Ki(l,a,12,[o,c]);else{const h=de(l),m=ft(l);if(h||m){const g=()=>{if(t.f){const v=h?f(l)?d[l]:c[l]:l.value;if(s)te(v)&&$f(v,i);else if(te(v))v.includes(i)||v.push(i);else if(h)c[l]=[i],f(l)&&(d[l]=c[l]);else{const b=[i];l.value=b,t.k&&(c[t.k]=b)}}else h?(c[l]=o,f(l)&&(d[l]=o)):m&&(l.value=o,t.k&&(c[t.k]=o))};if(o){const v=()=>{g(),Ul.delete(t)};v.id=-1,Ul.set(t,v),dt(v,r)}else Vm(t),g()}}}function Vm(t){const e=Ul.get(t);e&&(e.flags|=8,Ul.delete(t))}let Bm=!1;const si=()=>{Bm||(console.error("Hydration completed but contains mismatches."),Bm=!0)},$C=t=>t.namespaceURI.includes("svg")&&t.tagName!=="foreignObject",TC=t=>t.namespaceURI.includes("MathML"),Ga=t=>{if(t.nodeType===1){if($C(t))return"svg";if(TC(t))return"mathml"}},pi=t=>t.nodeType===8;function kC(t){const{mt:e,p:r,o:{patchProp:n,createText:s,nextSibling:i,parentNode:o,remove:a,insert:l,createComment:u}}=t,c=(w,_)=>{if(!_.hasChildNodes()){r(null,w,_),Bl(),_._vnode=w;return}d(_.firstChild,w,null,null,null),Bl(),_._vnode=w},d=(w,_,S,k,O,R=!1)=>{R=R||!!_.dynamicChildren;const C=pi(w)&&w.data==="[",A=()=>m(w,_,S,k,O,C),{type:j,ref:$,shapeFlag:y,patchFlag:I}=_;let q=w.nodeType;_.el=w,I===-2&&(R=!1,_.dynamicChildren=null);let D=null;switch(j){case es:q!==3?_.children===""?(l(_.el=s(""),o(w),w),D=w):D=A():(w.data!==_.children&&(si(),w.data=_.children),D=i(w));break;case nt:b(w)?(D=i(w),v(_.el=w.content.firstChild,w,S)):q!==8||C?D=A():D=i(w);break;case Rs:if(C&&(w=i(w),q=w.nodeType),q===1||q===3){D=w;const z=!_.children.length;for(let P=0;P<_.staticCount;P++)z&&(_.children+=D.nodeType===1?D.outerHTML:D.data),P===_.staticCount-1&&(_.anchor=D),D=i(D);return C?i(D):D}else A();break;case bt:C?D=h(w,_,S,k,O,R):D=A();break;default:if(y&1)(q!==1||_.type.toLowerCase()!==w.tagName.toLowerCase())&&!b(w)?D=A():D=p(w,_,S,k,O,R);else if(y&6){_.slotScopeIds=O;const z=o(w);if(C?D=g(w):pi(w)&&w.data==="teleport start"?D=g(w,w.data,"teleport end"):D=i(w),e(_,z,null,S,k,Ga(z),R),Zn(_)&&!_.type.__asyncResolved){let P;C?(P=Je(bt),P.anchor=D?D.previousSibling:z.lastChild):P=w.nodeType===3?Xf(""):Je("div"),P.el=w,_.component.subTree=P}}else y&64?q!==8?D=A():D=_.type.hydrate(w,_,S,k,O,R,t,f):y&128&&(D=_.type.hydrate(w,_,S,k,Ga(o(w)),O,R,t,d))}return $!=null&&_i($,null,k,_),D},p=(w,_,S,k,O,R)=>{R=R||!!_.dynamicChildren;const{type:C,props:A,patchFlag:j,shapeFlag:$,dirs:y,transition:I}=_,q=C==="input"||C==="option";if(q||j!==-1){y&&Jr(_,null,S,"created");let D=!1;if(b(w)){D=Jw(null,I)&&S&&S.vnode.props&&S.vnode.props.appear;const P=w.content.firstChild;if(D){const ee=P.getAttribute("class");ee&&(P.$cls=ee),I.beforeEnter(P)}v(P,w,S),_.el=w=P}if($&16&&!(A&&(A.innerHTML||A.textContent))){let P=f(w.firstChild,_,w,S,k,O,R);for(;P;){Ja(w,1)||si();const ee=P;P=P.nextSibling,a(ee)}}else if($&8){let P=_.children;P[0]===`
`&&(w.tagName==="PRE"||w.tagName==="TEXTAREA")&&(P=P.slice(1));const{textContent:ee}=w;ee!==P&&ee!==P.replace(/\r\n|\r/g,`
`)&&(Ja(w,0)||si(),w.textContent=_.children)}if(A){if(q||!R||j&48){const P=w.tagName.includes("-");for(const ee in A)(q&&(ee.endsWith("value")||ee==="indeterminate")||Gs(ee)&&!Xn(ee)||ee[0]==="."||P)&&n(w,ee,null,A[ee],void 0,S)}else if(A.onClick)n(w,"onClick",null,A.onClick,void 0,S);else if(j&4&&Yn(A.style))for(const P in A.style)A.style[P]}let z;(z=A&&A.onVnodeBeforeMount)&&Jt(z,S,_),y&&Jr(_,null,S,"beforeMount"),((z=A&&A.onVnodeMounted)||y||D)&&s_(()=>{z&&Jt(z,S,_),D&&I.enter(w),y&&Jr(_,null,S,"mounted")},k)}return w.nextSibling},f=(w,_,S,k,O,R,C)=>{C=C||!!_.dynamicChildren;const A=_.children,j=A.length;for(let $=0;$<j;$++){const y=C?A[$]:A[$]=Xt(A[$]),I=y.type===es;w?(I&&!C&&$+1<j&&Xt(A[$+1]).type===es&&(l(s(w.data.slice(y.children.length)),S,i(w)),w.data=y.children),w=d(w,y,k,O,R,C)):I&&!y.children?l(y.el=s(""),S):(Ja(S,1)||si(),r(null,y,S,null,k,O,Ga(S),R))}return w},h=(w,_,S,k,O,R)=>{const{slotScopeIds:C}=_;C&&(O=O?O.concat(C):C);const A=o(w),j=f(i(w),_,A,S,k,O,R);return j&&pi(j)&&j.data==="]"?i(_.anchor=j):(si(),l(_.anchor=u("]"),A,j),j)},m=(w,_,S,k,O,R)=>{if(Ja(w.parentElement,1)||si(),_.el=null,R){const j=g(w);for(;;){const $=i(w);if($&&$!==j)a($);else break}}const C=i(w),A=o(w);return a(w),r(null,_,A,C,S,k,Ga(A),O),S&&(S.vnode.el=_.el,zc(S,_.el)),C},g=(w,_="[",S="]")=>{let k=0;for(;w;)if(w=i(w),w&&pi(w)&&(w.data===_&&k++,w.data===S)){if(k===0)return i(w);k--}return w},v=(w,_,S)=>{const k=_.parentNode;k&&k.replaceChild(w,_);let O=S;for(;O;)O.vnode.el===_&&(O.vnode.el=O.subTree.el=w),O=O.parent},b=w=>w.nodeType===1&&w.tagName==="TEMPLATE";return[c,d]}const Um="data-allow-mismatch",PC={0:"text",1:"children",2:"class",3:"style",4:"attribute"};function Ja(t,e){if(e===0||e===1)for(;t&&!t.hasAttribute(Um);)t=t.parentElement;const r=t&&t.getAttribute(Um);if(r==null)return!1;if(r==="")return!0;{const n=r.split(",");return e===0&&n.includes("children")?!0:n.includes(PC[e])}}const OC=kc().requestIdleCallback||(t=>setTimeout(t,1)),RC=kc().cancelIdleCallback||(t=>clearTimeout(t)),NC=(t=1e4)=>e=>{const r=OC(e,{timeout:t});return()=>RC(r)};function IC(t){const{top:e,left:r,bottom:n,right:s}=t.getBoundingClientRect(),{innerHeight:i,innerWidth:o}=window;return(e>0&&e<i||n>0&&n<i)&&(r>0&&r<o||s>0&&s<o)}const LC=t=>(e,r)=>{const n=new IntersectionObserver(s=>{for(const i of s)if(i.isIntersecting){n.disconnect(),e();break}},t);return r(s=>{if(s instanceof Element){if(IC(s))return e(),n.disconnect(),!1;n.observe(s)}}),()=>n.disconnect()},FC=t=>e=>{if(t){const r=matchMedia(t);if(r.matches)e();else return r.addEventListener("change",e,{once:!0}),()=>r.removeEventListener("change",e)}},MC=(t=[])=>(e,r)=>{de(t)&&(t=[t]);let n=!1;const s=o=>{n||(n=!0,i(),e(),o.target.dispatchEvent(new o.constructor(o.type,o)))},i=()=>{r(o=>{for(const a of t)o.removeEventListener(a,s)})};return r(o=>{for(const a of t)o.addEventListener(a,s,{once:!0})}),i};function DC(t,e){if(pi(t)&&t.data==="["){let r=1,n=t.nextSibling;for(;n;){if(n.nodeType===1){if(e(n)===!1)break}else if(pi(n))if(n.data==="]"){if(--r===0)break}else n.data==="["&&r++;n=n.nextSibling}}else e(t)}const Zn=t=>!!t.type.__asyncLoader;function VC(t){ae(t)&&(t={loader:t});const{loader:e,loadingComponent:r,errorComponent:n,delay:s=200,hydrate:i,timeout:o,suspensible:a=!0,onError:l}=t;let u=null,c,d=0;const p=()=>(d++,u=null,f()),f=()=>{let h;return u||(h=u=e().catch(m=>{if(m=m instanceof Error?m:new Error(String(m)),l)return new Promise((g,v)=>{l(m,()=>g(p()),()=>v(m),d+1)});throw m}).then(m=>h!==u&&u?u:(m&&(m.__esModule||m[Symbol.toStringTag]==="Module")&&(m=m.default),c=m,m)))};return Gi({name:"AsyncComponentWrapper",__asyncLoader:f,__asyncHydrate(h,m,g){let v=!1;(m.bu||(m.bu=[])).push(()=>v=!0);const b=()=>{v||g()},w=i?()=>{const _=i(b,S=>DC(h,S));_&&(m.bum||(m.bum=[])).push(_)}:b;c?w():f().then(()=>!m.isUnmounted&&w())},get __asyncResolved(){return c},setup(){const h=At;if(Bf(h),c)return()=>Xa(c,h);const m=w=>{u=null,Ys(w,h,13,!n)};if(a&&h.suspense||Ai)return f().then(w=>()=>Xa(w,h)).catch(w=>(m(w),()=>n?Je(n,{error:w}):null));const g=Qn(!1),v=Qn(),b=Qn(!!s);return s&&setTimeout(()=>{b.value=!1},s),o!=null&&setTimeout(()=>{if(!g.value&&!v.value){const w=new Error(`Async component timed out after ${o}ms.`);m(w),v.value=w}},o),f().then(()=>{g.value=!0,h.parent&&pa(h.parent.vnode)&&h.parent.update()}).catch(w=>{m(w),v.value=w}),()=>{if(g.value&&c)return Xa(c,h);if(v.value&&n)return Je(n,{error:v.value});if(r&&!b.value)return Xa(r,h)}}})}function Xa(t,e){const{ref:r,props:n,children:s,ce:i}=e.vnode,o=Je(t,n,s);return o.ref=r,o.ce=i,delete e.vnode.ce,o}const pa=t=>t.type.__isKeepAlive,BC={name:"KeepAlive",__isKeepAlive:!0,props:{include:[String,RegExp,Array],exclude:[String,RegExp,Array],max:[String,Number]},setup(t,{slots:e}){const r=or(),n=r.ctx;if(!n.renderer)return()=>{const b=e.default&&e.default();return b&&b.length===1?b[0]:b};const s=new Map,i=new Set;let o=null;const a=r.suspense,{renderer:{p:l,m:u,um:c,o:{createElement:d}}}=n,p=d("div");n.activate=(b,w,_,S,k)=>{const O=b.component;u(b,w,_,0,a),l(O.vnode,b,w,_,O,a,S,b.slotScopeIds,k),dt(()=>{O.isDeactivated=!1,O.a&&vi(O.a);const R=b.props&&b.props.onVnodeMounted;R&&Jt(R,O.parent,b)},a)},n.deactivate=b=>{const w=b.component;ql(w.m),ql(w.a),u(b,p,null,1,a),dt(()=>{w.da&&vi(w.da);const _=b.props&&b.props.onVnodeUnmounted;_&&Jt(_,w.parent,b),w.isDeactivated=!0},a)};function f(b){Bu(b),c(b,r,a,!0)}function h(b){s.forEach((w,_)=>{const S=kh(w.type);S&&!b(S)&&m(_)})}function m(b){const w=s.get(b);w&&(!o||!Lr(w,o))?f(w):o&&Bu(o),s.delete(b),i.delete(b)}Os(()=>[t.include,t.exclude],([b,w])=>{b&&h(_=>vo(b,_)),w&&h(_=>!vo(w,_))},{flush:"post",deep:!0});let g=null;const v=()=>{g!=null&&(Hl(r.subTree.type)?dt(()=>{s.set(g,Ya(r.subTree))},r.subTree.suspense):s.set(g,Ya(r.subTree)))};return ma(v),Uc(v),jc(()=>{s.forEach(b=>{const{subTree:w,suspense:_}=r,S=Ya(w);if(b.type===S.type&&b.key===S.key){Bu(S);const k=S.component.da;k&&dt(k,_);return}f(b)})}),()=>{if(g=null,!e.default)return o=null;const b=e.default(),w=b[0];if(b.length>1)return o=null,b;if(!xn(w)||!(w.shapeFlag&4)&&!(w.shapeFlag&128))return o=null,w;let _=Ya(w);if(_.type===nt)return o=null,_;const S=_.type,k=kh(Zn(_)?_.type.__asyncResolved||{}:S),{include:O,exclude:R,max:C}=t;if(O&&(!k||!vo(O,k))||R&&k&&vo(R,k))return _.shapeFlag&=-257,o=_,w;const A=_.key==null?S:_.key,j=s.get(A);return _.el&&(_=Yr(_),w.shapeFlag&128&&(w.ssContent=_)),g=A,j?(_.el=j.el,_.component=j.component,_.transition&&Sn(_,_.transition),_.shapeFlag|=512,i.delete(A),i.add(A)):(i.add(A),C&&i.size>parseInt(C,10)&&m(i.values().next().value)),_.shapeFlag|=256,o=_,Hl(w.type)?w:_}}},UC=BC;function vo(t,e){return te(t)?t.some(r=>vo(r,e)):de(t)?t.split(",").includes(e):rx(t)?(t.lastIndex=0,t.test(e)):!1}function Cw(t,e){$w(t,"a",e)}function Aw(t,e){$w(t,"da",e)}function $w(t,e,r=At){const n=t.__wdc||(t.__wdc=()=>{let s=r;for(;s;){if(s.isDeactivated)return;s=s.parent}return t()});if(Bc(e,n,r),r){let s=r.parent;for(;s&&s.parent;)pa(s.parent.vnode)&&jC(n,e,r,s),s=s.parent}}function jC(t,e,r,n){const s=Bc(e,t,n,!0);qc(()=>{$f(n[e],s)},r)}function Bu(t){t.shapeFlag&=-257,t.shapeFlag&=-513}function Ya(t){return t.shapeFlag&128?t.ssContent:t}function Bc(t,e,r=At,n=!1){if(r){const s=r[t]||(r[t]=[]),i=e.__weh||(e.__weh=(...o)=>{wn();const a=Bs(r),l=$r(e,r,t,o);return a(),_n(),l});return n?s.unshift(i):s.push(i),i}}const An=t=>(e,r=At)=>{(!Ai||t==="sp")&&Bc(t,(...n)=>e(...n),r)},Tw=An("bm"),ma=An("m"),Uf=An("bu"),Uc=An("u"),jc=An("bum"),qc=An("um"),kw=An("sp"),Pw=An("rtg"),Ow=An("rtc");function Rw(t,e=At){Bc("ec",t,e)}const jf="components",qC="directives";function HC(t,e){return qf(jf,t,!0,e)||t}const Nw=Symbol.for("v-ndc");function zC(t){return de(t)?qf(jf,t,!1)||t:t||Nw}function WC(t){return qf(qC,t)}function qf(t,e,r=!0,n=!1){const s=$t||At;if(s){const i=s.type;if(t===jf){const a=kh(i,!1);if(a&&(a===e||a===We(e)||a===Xs(We(e))))return i}const o=jm(s[t]||i[t],e)||jm(s.appContext[t],e);return!o&&n?i:o}}function jm(t,e){return t&&(t[e]||t[We(e)]||t[Xs(We(e))])}function KC(t,e,r,n){let s;const i=r&&r[n],o=te(t);if(o||de(t)){const a=o&&Yn(t);let l=!1,u=!1;a&&(l=!gr(t),u=En(t),t=Rc(t)),s=new Array(t.length);for(let c=0,d=t.length;c<d;c++)s[c]=e(l?u?Ml(Ct(t[c])):Ct(t[c]):t[c],c,void 0,i&&i[c])}else if(typeof t=="number"){s=new Array(t);for(let a=0;a<t;a++)s[a]=e(a+1,a,void 0,i&&i[a])}else if(Ne(t))if(t[Symbol.iterator])s=Array.from(t,(a,l)=>e(a,l,void 0,i&&i[l]));else{const a=Object.keys(t);s=new Array(a.length);for(let l=0,u=a.length;l<u;l++){const c=a[l];s[l]=e(t[c],c,l,i&&i[l])}}else s=[];return r&&(r[n]=s),s}function GC(t,e){for(let r=0;r<e.length;r++){const n=e[r];if(te(n))for(let s=0;s<n.length;s++)t[n[s].name]=n[s].fn;else n&&(t[n.name]=n.key?(...s)=>{const i=n.fn(...s);return i&&(i.key=n.key),i}:n.fn)}return t}function JC(t,e,r={},n,s){if($t.ce||$t.parent&&Zn($t.parent)&&$t.parent.ce){const u=Object.keys(r).length>0;return e!=="default"&&(r.name=e),Xo(),zl(bt,null,[Je("slot",r,n&&n())],u?-2:64)}let i=t[e];i&&i._c&&(i._d=!1),Xo();const o=i&&Hf(i(r)),a=r.key||o&&o.key,l=zl(bt,{key:(a&&!ir(a)?a:`_${e}`)+(!o&&n?"_fb":"")},o||(n?n():[]),o&&t._===1?64:-2);return!s&&l.scopeId&&(l.slotScopeIds=[l.scopeId+"-s"]),i&&i._c&&(i._d=!0),l}function Hf(t){return t.some(e=>xn(e)?!(e.type===nt||e.type===bt&&!Hf(e.children)):!0)?t:null}function XC(t,e){const r={};for(const n in t)r[e&&/[A-Z]/.test(n)?`on:${n}`:bi(n)]=t[n];return r}const vh=t=>t?d_(t)?ya(t):vh(t.parent):null,ko=we(Object.create(null),{$:t=>t,$el:t=>t.vnode.el,$data:t=>t.data,$props:t=>t.props,$attrs:t=>t.attrs,$slots:t=>t.slots,$refs:t=>t.refs,$parent:t=>vh(t.parent),$root:t=>vh(t.root),$host:t=>t.ce,$emit:t=>t.emit,$options:t=>zf(t),$forceUpdate:t=>t.f||(t.f=()=>{Ff(t.update)}),$nextTick:t=>t.n||(t.n=Mc.bind(t.proxy)),$watch:t=>TA.bind(t)}),Uu=(t,e)=>t!==Se&&!t.__isScriptSetup&&Pe(t,e),wh={get({_:t},e){if(e==="__v_skip")return!0;const{ctx:r,setupState:n,data:s,props:i,accessCache:o,type:a,appContext:l}=t;let u;if(e[0]!=="$"){const f=o[e];if(f!==void 0)switch(f){case 1:return n[e];case 2:return s[e];case 4:return r[e];case 3:return i[e]}else{if(Uu(n,e))return o[e]=1,n[e];if(s!==Se&&Pe(s,e))return o[e]=2,s[e];if((u=t.propsOptions[0])&&Pe(u,e))return o[e]=3,i[e];if(r!==Se&&Pe(r,e))return o[e]=4,r[e];_h&&(o[e]=0)}}const c=ko[e];let d,p;if(c)return e==="$attrs"&&Nt(t.attrs,"get",""),c(t);if((d=a.__cssModules)&&(d=d[e]))return d;if(r!==Se&&Pe(r,e))return o[e]=4,r[e];if(p=l.config.globalProperties,Pe(p,e))return p[e]},set({_:t},e,r){const{data:n,setupState:s,ctx:i}=t;return Uu(s,e)?(s[e]=r,!0):n!==Se&&Pe(n,e)?(n[e]=r,!0):Pe(t.props,e)||e[0]==="$"&&e.slice(1)in t?!1:(i[e]=r,!0)},has({_:{data:t,setupState:e,accessCache:r,ctx:n,appContext:s,propsOptions:i,type:o}},a){let l,u;return!!(r[a]||t!==Se&&a[0]!=="$"&&Pe(t,a)||Uu(e,a)||(l=i[0])&&Pe(l,a)||Pe(n,a)||Pe(ko,a)||Pe(s.config.globalProperties,a)||(u=o.__cssModules)&&u[a])},defineProperty(t,e,r){return r.get!=null?t._.accessCache[e]=0:Pe(r,"value")&&this.set(t,e,r.value,null),Reflect.defineProperty(t,e,r)}},YC=we({},wh,{get(t,e){if(e!==Symbol.unscopables)return wh.get(t,e,t)},has(t,e){return e[0]!=="_"&&!cx(e)}});function QC(){return null}function ZC(){return null}function eA(t){}function tA(t){}function rA(){return null}function nA(){}function sA(t,e){return null}function iA(){return Iw().slots}function oA(){return Iw().attrs}function Iw(t){const e=or();return e.setupContext||(e.setupContext=m_(e))}function Go(t){return te(t)?t.reduce((e,r)=>(e[r]=null,e),{}):t}function aA(t,e){const r=Go(t);for(const n in e){if(n.startsWith("__skip"))continue;let s=r[n];s?te(s)||ae(s)?s=r[n]={type:s,default:e[n]}:s.default=e[n]:s===null&&(s=r[n]={default:e[n]}),s&&e[`__skip_${n}`]&&(s.skipFactory=!0)}return r}function lA(t,e){return!t||!e?t||e:te(t)&&te(e)?t.concat(e):we({},Go(t),Go(e))}function cA(t,e){const r={};for(const n in t)e.includes(n)||Object.defineProperty(r,n,{enumerable:!0,get:()=>t[n]});return r}function uA(t){const e=or();let r=t();return Ah(),Tf(r)&&(r=r.catch(n=>{throw Bs(e),n})),[r,()=>Bs(e)]}let _h=!0;function dA(t){const e=zf(t),r=t.proxy,n=t.ctx;_h=!1,e.beforeCreate&&qm(e.beforeCreate,t,"bc");const{data:s,computed:i,methods:o,watch:a,provide:l,inject:u,created:c,beforeMount:d,mounted:p,beforeUpdate:f,updated:h,activated:m,deactivated:g,beforeDestroy:v,beforeUnmount:b,destroyed:w,unmounted:_,render:S,renderTracked:k,renderTriggered:O,errorCaptured:R,serverPrefetch:C,expose:A,inheritAttrs:j,components:$,directives:y,filters:I}=e;if(u&&hA(u,n,null),o)for(const z in o){const P=o[z];ae(P)&&(n[z]=P.bind(r))}if(s){const z=s.call(r,r);Ne(z)&&(t.data=Wi(z))}if(_h=!0,i)for(const z in i){const P=i[z],ee=ae(P)?P.bind(r,r):ae(P.get)?P.get.bind(r,r):Tt,ye=!ae(P)&&ae(P.set)?P.set.bind(r):Tt,re=Kt({get:ee,set:ye});Object.defineProperty(n,z,{enumerable:!0,configurable:!0,get:()=>re.value,set:_e=>re.value=_e})}if(a)for(const z in a)Lw(a[z],n,r,z);if(l){const z=ae(l)?l.call(r):l;Reflect.ownKeys(z).forEach(P=>{Mw(P,z[P])})}c&&qm(c,t,"c");function D(z,P){te(P)?P.forEach(ee=>z(ee.bind(r))):P&&z(P.bind(r))}if(D(Tw,d),D(ma,p),D(Uf,f),D(Uc,h),D(Cw,m),D(Aw,g),D(Rw,R),D(Ow,k),D(Pw,O),D(jc,b),D(qc,_),D(kw,C),te(A))if(A.length){const z=t.exposed||(t.exposed={});A.forEach(P=>{Object.defineProperty(z,P,{get:()=>r[P],set:ee=>r[P]=ee,enumerable:!0})})}else t.exposed||(t.exposed={});S&&t.render===Tt&&(t.render=S),j!=null&&(t.inheritAttrs=j),$&&(t.components=$),y&&(t.directives=y),C&&Bf(t)}function hA(t,e,r=Tt){te(t)&&(t=Eh(t));for(const n in t){const s=t[n];let i;Ne(s)?"default"in s?i=Po(s.from||n,s.default,!0):i=Po(s.from||n):i=Po(s),ft(i)?Object.defineProperty(e,n,{enumerable:!0,configurable:!0,get:()=>i.value,set:o=>i.value=o}):e[n]=i}}function qm(t,e,r){$r(te(t)?t.map(n=>n.bind(e.proxy)):t.bind(e.proxy),e,r)}function Lw(t,e,r,n){let s=n.includes(".")?e_(r,n):()=>r[n];if(de(t)){const i=e[t];ae(i)&&Os(s,i)}else if(ae(t))Os(s,t.bind(r));else if(Ne(t))if(te(t))t.forEach(i=>Lw(i,e,r,n));else{const i=ae(t.handler)?t.handler.bind(r):e[t.handler];ae(i)&&Os(s,i,t)}}function zf(t){const e=t.type,{mixins:r,extends:n}=e,{mixins:s,optionsCache:i,config:{optionMergeStrategies:o}}=t.appContext,a=i.get(e);let l;return a?l=a:!s.length&&!r&&!n?l=e:(l={},s.length&&s.forEach(u=>jl(l,u,o,!0)),jl(l,e,o)),Ne(e)&&i.set(e,l),l}function jl(t,e,r,n=!1){const{mixins:s,extends:i}=e;i&&jl(t,i,r,!0),s&&s.forEach(o=>jl(t,o,r,!0));for(const o in e)if(!(n&&o==="expose")){const a=fA[o]||r&&r[o];t[o]=a?a(t[o],e[o]):e[o]}return t}const fA={data:Hm,props:zm,emits:zm,methods:wo,computed:wo,beforeCreate:jt,created:jt,beforeMount:jt,mounted:jt,beforeUpdate:jt,updated:jt,beforeDestroy:jt,beforeUnmount:jt,destroyed:jt,unmounted:jt,activated:jt,deactivated:jt,errorCaptured:jt,serverPrefetch:jt,components:wo,directives:wo,watch:mA,provide:Hm,inject:pA};function Hm(t,e){return e?t?function(){return we(ae(t)?t.call(this,this):t,ae(e)?e.call(this,this):e)}:e:t}function pA(t,e){return wo(Eh(t),Eh(e))}function Eh(t){if(te(t)){const e={};for(let r=0;r<t.length;r++)e[t[r]]=t[r];return e}return t}function jt(t,e){return t?[...new Set([].concat(t,e))]:e}function wo(t,e){return t?we(Object.create(null),t,e):e}function zm(t,e){return t?te(t)&&te(e)?[...new Set([...t,...e])]:we(Object.create(null),Go(t),Go(e??{})):e}function mA(t,e){if(!t)return e;if(!e)return t;const r=we(Object.create(null),t);for(const n in e)r[n]=jt(t[n],e[n]);return r}function Fw(){return{app:null,config:{isNativeTag:fi,performance:!1,globalProperties:{},optionMergeStrategies:{},errorHandler:void 0,warnHandler:void 0,compilerOptions:{}},mixins:[],components:{},directives:{},provides:Object.create(null),optionsCache:new WeakMap,propsCache:new WeakMap,emitsCache:new WeakMap}}let gA=0;function yA(t,e){return function(n,s=null){ae(n)||(n=we({},n)),s!=null&&!Ne(s)&&(s=null);const i=Fw(),o=new WeakSet,a=[];let l=!1;const u=i.app={_uid:gA++,_component:n,_props:s,_container:null,_context:i,_instance:null,version:y_,get config(){return i.config},set config(c){},use(c,...d){return o.has(c)||(c&&ae(c.install)?(o.add(c),c.install(u,...d)):ae(c)&&(o.add(c),c(u,...d))),u},mixin(c){return i.mixins.includes(c)||i.mixins.push(c),u},component(c,d){return d?(i.components[c]=d,u):i.components[c]},directive(c,d){return d?(i.directives[c]=d,u):i.directives[c]},mount(c,d,p){if(!l){const f=u._ceVNode||Je(n,s);return f.appContext=i,p===!0?p="svg":p===!1&&(p=void 0),d&&e?e(f,c):t(f,c,p),l=!0,u._container=c,c.__vue_app__=u,ya(f.component)}},onUnmount(c){a.push(c)},unmount(){l&&($r(a,u._instance,16),t(null,u._container),delete u._container.__vue_app__)},provide(c,d){return i.provides[c]=d,u},runWithContext(c){const d=Ps;Ps=u;try{return c()}finally{Ps=d}}};return u}}let Ps=null;function Mw(t,e){if(At){let r=At.provides;const n=At.parent&&At.parent.provides;n===r&&(r=At.provides=Object.create(n)),r[t]=e}}function Po(t,e,r=!1){const n=or();if(n||Ps){let s=Ps?Ps._context.provides:n?n.parent==null||n.ce?n.vnode.appContext&&n.vnode.appContext.provides:n.parent.provides:void 0;if(s&&t in s)return s[t];if(arguments.length>1)return r&&ae(e)?e.call(n&&n.proxy):e}}function bA(){return!!(or()||Ps)}const Dw={},Vw=()=>Object.create(Dw),Bw=t=>Object.getPrototypeOf(t)===Dw;function vA(t,e,r,n=!1){const s={},i=Vw();t.propsDefaults=Object.create(null),Uw(t,e,s,i);for(const o in t.propsOptions[0])o in s||(s[o]=void 0);r?t.props=n?s:aw(s):t.type.props?t.props=s:t.props=i,t.attrs=i}function wA(t,e,r,n){const{props:s,attrs:i,vnode:{patchFlag:o}}=t,a=$e(s),[l]=t.propsOptions;let u=!1;if((n||o>0)&&!(o&16)){if(o&8){const c=t.vnode.dynamicProps;for(let d=0;d<c.length;d++){let p=c[d];if(Hc(t.emitsOptions,p))continue;const f=e[p];if(l)if(Pe(i,p))f!==i[p]&&(i[p]=f,u=!0);else{const h=We(p);s[h]=Sh(l,a,h,f,t,!1)}else f!==i[p]&&(i[p]=f,u=!0)}}}else{Uw(t,e,s,i)&&(u=!0);let c;for(const d in a)(!e||!Pe(e,d)&&((c=Yt(d))===d||!Pe(e,c)))&&(l?r&&(r[d]!==void 0||r[c]!==void 0)&&(s[d]=Sh(l,a,d,void 0,t,!0)):delete s[d]);if(i!==a)for(const d in i)(!e||!Pe(e,d))&&(delete i[d],u=!0)}u&&pn(t.attrs,"set","")}function Uw(t,e,r,n){const[s,i]=t.propsOptions;let o=!1,a;if(e)for(let l in e){if(Xn(l))continue;const u=e[l];let c;s&&Pe(s,c=We(l))?!i||!i.includes(c)?r[c]=u:(a||(a={}))[c]=u:Hc(t.emitsOptions,l)||(!(l in n)||u!==n[l])&&(n[l]=u,o=!0)}if(i){const l=$e(r),u=a||Se;for(let c=0;c<i.length;c++){const d=i[c];r[d]=Sh(s,l,d,u[d],t,!Pe(u,d))}}return o}function Sh(t,e,r,n,s,i){const o=t[r];if(o!=null){const a=Pe(o,"default");if(a&&n===void 0){const l=o.default;if(o.type!==Function&&!o.skipFactory&&ae(l)){const{propsDefaults:u}=s;if(r in u)n=u[r];else{const c=Bs(s);n=u[r]=l.call(null,e),c()}}else n=l;s.ce&&s.ce._setProp(r,n)}o[0]&&(i&&!a?n=!1:o[1]&&(n===""||n===Yt(r))&&(n=!0))}return n}const _A=new WeakMap;function jw(t,e,r=!1){const n=r?_A:e.propsCache,s=n.get(t);if(s)return s;const i=t.props,o={},a=[];let l=!1;if(!ae(t)){const c=d=>{l=!0;const[p,f]=jw(d,e,!0);we(o,p),f&&a.push(...f)};!r&&e.mixins.length&&e.mixins.forEach(c),t.extends&&c(t.extends),t.mixins&&t.mixins.forEach(c)}if(!i&&!l)return Ne(t)&&n.set(t,gi),gi;if(te(i))for(let c=0;c<i.length;c++){const d=We(i[c]);Wm(d)&&(o[d]=Se)}else if(i)for(const c in i){const d=We(c);if(Wm(d)){const p=i[c],f=o[d]=te(p)||ae(p)?{type:p}:we({},p),h=f.type;let m=!1,g=!0;if(te(h))for(let v=0;v<h.length;++v){const b=h[v],w=ae(b)&&b.name;if(w==="Boolean"){m=!0;break}else w==="String"&&(g=!1)}else m=ae(h)&&h.name==="Boolean";f[0]=m,f[1]=g,(m||Pe(f,"default"))&&a.push(d)}}const u=[o,a];return Ne(t)&&n.set(t,u),u}function Wm(t){return t[0]!=="$"&&!Xn(t)}const Wf=t=>t==="_"||t==="_ctx"||t==="$stable",Kf=t=>te(t)?t.map(Xt):[Xt(t)],EA=(t,e,r)=>{if(e._n)return e;const n=Mf((...s)=>Kf(e(...s)),r);return n._c=!1,n},qw=(t,e,r)=>{const n=t._ctx;for(const s in t){if(Wf(s))continue;const i=t[s];if(ae(i))e[s]=EA(s,i,n);else if(i!=null){const o=Kf(i);e[s]=()=>o}}},Hw=(t,e)=>{const r=Kf(e);t.slots.default=()=>r},zw=(t,e,r)=>{for(const n in e)(r||!Wf(n))&&(t[n]=e[n])},SA=(t,e,r)=>{const n=t.slots=Vw();if(t.vnode.shapeFlag&32){const s=e._;s?(zw(n,e,r),r&&Vv(n,"_",s,!0)):qw(e,n)}else e&&Hw(t,e)},xA=(t,e,r)=>{const{vnode:n,slots:s}=t;let i=!0,o=Se;if(n.shapeFlag&32){const a=e._;a?r&&a===1?i=!1:zw(s,e,r):(i=!e.$stable,qw(e,s)),o=e}else e&&(Hw(t,e),o={default:1});if(i)for(const a in s)!Wf(a)&&o[a]==null&&delete s[a]},dt=s_;function Ww(t){return Gw(t)}function Kw(t){return Gw(t,kC)}function Gw(t,e){const r=kc();r.__VUE__=!0;const{insert:n,remove:s,patchProp:i,createElement:o,createText:a,createComment:l,setText:u,setElementText:c,parentNode:d,nextSibling:p,setScopeId:f=Tt,insertStaticContent:h}=t,m=(E,T,B,K=null,H=null,W=null,Q=void 0,X=null,J=!!T.dynamicChildren)=>{if(E===T)return;E&&!Lr(E,T)&&(K=cr(E),_e(E,H,W,!0),E=null),T.patchFlag===-2&&(J=!1,T.dynamicChildren=null);const{type:G,ref:ne,shapeFlag:Z}=T;switch(G){case es:g(E,T,B,K);break;case nt:v(E,T,B,K);break;case Rs:E==null&&b(T,B,K,Q);break;case bt:$(E,T,B,K,H,W,Q,X,J);break;default:Z&1?S(E,T,B,K,H,W,Q,X,J):Z&6?y(E,T,B,K,H,W,Q,X,J):(Z&64||Z&128)&&G.process(E,T,B,K,H,W,Q,X,J,me)}ne!=null&&H?_i(ne,E&&E.ref,W,T||E,!T):ne==null&&E&&E.ref!=null&&_i(E.ref,null,W,E,!0)},g=(E,T,B,K)=>{if(E==null)n(T.el=a(T.children),B,K);else{const H=T.el=E.el;T.children!==E.children&&u(H,T.children)}},v=(E,T,B,K)=>{E==null?n(T.el=l(T.children||""),B,K):T.el=E.el},b=(E,T,B,K)=>{[E.el,E.anchor]=h(E.children,T,B,K,E.el,E.anchor)},w=({el:E,anchor:T},B,K)=>{let H;for(;E&&E!==T;)H=p(E),n(E,B,K),E=H;n(T,B,K)},_=({el:E,anchor:T})=>{let B;for(;E&&E!==T;)B=p(E),s(E),E=B;s(T)},S=(E,T,B,K,H,W,Q,X,J)=>{if(T.type==="svg"?Q="svg":T.type==="math"&&(Q="mathml"),E==null)k(T,B,K,H,W,Q,X,J);else{const G=E.el&&E.el._isVueCE?E.el:null;try{G&&G._beginPatch(),C(E,T,H,W,Q,X,J)}finally{G&&G._endPatch()}}},k=(E,T,B,K,H,W,Q,X)=>{let J,G;const{props:ne,shapeFlag:Z,transition:se,dirs:ce}=E;if(J=E.el=o(E.type,W,ne&&ne.is,ne),Z&8?c(J,E.children):Z&16&&R(E.children,J,null,K,H,ju(E,W),Q,X),ce&&Jr(E,null,K,"created"),O(J,E,E.scopeId,Q,K),ne){for(const De in ne)De!=="value"&&!Xn(De)&&i(J,De,null,ne[De],W,K);"value"in ne&&i(J,"value",null,ne.value,W),(G=ne.onVnodeBeforeMount)&&Jt(G,K,E)}ce&&Jr(E,null,K,"beforeMount");const be=Jw(H,se);be&&se.beforeEnter(J),n(J,T,B),((G=ne&&ne.onVnodeMounted)||be||ce)&&dt(()=>{G&&Jt(G,K,E),be&&se.enter(J),ce&&Jr(E,null,K,"mounted")},H)},O=(E,T,B,K,H)=>{if(B&&f(E,B),K)for(let W=0;W<K.length;W++)f(E,K[W]);if(H){let W=H.subTree;if(T===W||Hl(W.type)&&(W.ssContent===T||W.ssFallback===T)){const Q=H.vnode;O(E,Q,Q.scopeId,Q.slotScopeIds,H.parent)}}},R=(E,T,B,K,H,W,Q,X,J=0)=>{for(let G=J;G<E.length;G++){const ne=E[G]=X?zn(E[G]):Xt(E[G]);m(null,ne,T,B,K,H,W,Q,X)}},C=(E,T,B,K,H,W,Q)=>{const X=T.el=E.el;let{patchFlag:J,dynamicChildren:G,dirs:ne}=T;J|=E.patchFlag&16;const Z=E.props||Se,se=T.props||Se;let ce;if(B&&hs(B,!1),(ce=se.onVnodeBeforeUpdate)&&Jt(ce,B,T,E),ne&&Jr(T,E,B,"beforeUpdate"),B&&hs(B,!0),(Z.innerHTML&&se.innerHTML==null||Z.textContent&&se.textContent==null)&&c(X,""),G?A(E.dynamicChildren,G,X,B,K,ju(T,H),W):Q||P(E,T,X,null,B,K,ju(T,H),W,!1),J>0){if(J&16)j(X,Z,se,B,H);else if(J&2&&Z.class!==se.class&&i(X,"class",null,se.class,H),J&4&&i(X,"style",Z.style,se.style,H),J&8){const be=T.dynamicProps;for(let De=0;De<be.length;De++){const xe=be[De],yt=Z[xe],lt=se[xe];(lt!==yt||xe==="value")&&i(X,xe,yt,lt,H,B)}}J&1&&E.children!==T.children&&c(X,T.children)}else!Q&&G==null&&j(X,Z,se,B,H);((ce=se.onVnodeUpdated)||ne)&&dt(()=>{ce&&Jt(ce,B,T,E),ne&&Jr(T,E,B,"updated")},K)},A=(E,T,B,K,H,W,Q)=>{for(let X=0;X<T.length;X++){const J=E[X],G=T[X],ne=J.el&&(J.type===bt||!Lr(J,G)||J.shapeFlag&198)?d(J.el):B;m(J,G,ne,null,K,H,W,Q,!0)}},j=(E,T,B,K,H)=>{if(T!==B){if(T!==Se)for(const W in T)!Xn(W)&&!(W in B)&&i(E,W,T[W],null,H,K);for(const W in B){if(Xn(W))continue;const Q=B[W],X=T[W];Q!==X&&W!=="value"&&i(E,W,X,Q,H,K)}"value"in B&&i(E,"value",T.value,B.value,H)}},$=(E,T,B,K,H,W,Q,X,J)=>{const G=T.el=E?E.el:a(""),ne=T.anchor=E?E.anchor:a("");let{patchFlag:Z,dynamicChildren:se,slotScopeIds:ce}=T;ce&&(X=X?X.concat(ce):ce),E==null?(n(G,B,K),n(ne,B,K),R(T.children||[],B,ne,H,W,Q,X,J)):Z>0&&Z&64&&se&&E.dynamicChildren?(A(E.dynamicChildren,se,B,H,W,Q,X),(T.key!=null||H&&T===H.subTree)&&Gf(E,T,!0)):P(E,T,B,ne,H,W,Q,X,J)},y=(E,T,B,K,H,W,Q,X,J)=>{T.slotScopeIds=X,E==null?T.shapeFlag&512?H.ctx.activate(T,B,K,Q,J):I(T,B,K,H,W,Q,J):q(E,T,J)},I=(E,T,B,K,H,W,Q)=>{const X=E.component=u_(E,K,H);if(pa(E)&&(X.ctx.renderer=me),h_(X,!1,Q),X.asyncDep){if(H&&H.registerDep(X,D,Q),!E.el){const J=X.subTree=Je(nt);v(null,J,T,B),E.placeholder=J.el}}else D(X,E,T,B,H,W,Q)},q=(E,T,B)=>{const K=T.component=E.component;if(LA(E,T,B))if(K.asyncDep&&!K.asyncResolved){z(K,T,B);return}else K.next=T,K.update();else T.el=E.el,K.vnode=T},D=(E,T,B,K,H,W,Q)=>{const X=()=>{if(E.isMounted){let{next:Z,bu:se,u:ce,parent:be,vnode:De}=E;{const Ut=Xw(E);if(Ut){Z&&(Z.el=De.el,z(E,Z,Q)),Ut.asyncDep.then(()=>{E.isUnmounted||X()});return}}let xe=Z,yt;hs(E,!1),Z?(Z.el=De.el,z(E,Z,Q)):Z=De,se&&vi(se),(yt=Z.props&&Z.props.onVnodeBeforeUpdate)&&Jt(yt,be,Z,De),hs(E,!0);const lt=hl(E),ur=E.subTree;E.subTree=lt,m(ur,lt,d(ur.el),cr(ur),E,H,W),Z.el=lt.el,xe===null&&zc(E,lt.el),ce&&dt(ce,H),(yt=Z.props&&Z.props.onVnodeUpdated)&&dt(()=>Jt(yt,be,Z,De),H)}else{let Z;const{el:se,props:ce}=T,{bm:be,m:De,parent:xe,root:yt,type:lt}=E,ur=Zn(T);if(hs(E,!1),be&&vi(be),!ur&&(Z=ce&&ce.onVnodeBeforeMount)&&Jt(Z,xe,T),hs(E,!0),se&&Re){const Ut=()=>{E.subTree=hl(E),Re(se,E.subTree,E,H,null)};ur&&lt.__asyncHydrate?lt.__asyncHydrate(se,E,Ut):Ut()}else{yt.ce&&yt.ce._def.shadowRoot!==!1&&yt.ce._injectChildStyle(lt);const Ut=E.subTree=hl(E);m(null,Ut,B,K,E,H,W),T.el=Ut.el}if(De&&dt(De,H),!ur&&(Z=ce&&ce.onVnodeMounted)){const Ut=T;dt(()=>Jt(Z,xe,Ut),H)}(T.shapeFlag&256||xe&&Zn(xe.vnode)&&xe.vnode.shapeFlag&256)&&E.a&&dt(E.a,H),E.isMounted=!0,T=B=K=null}};E.scope.on();const J=E.effect=new jo(X);E.scope.off();const G=E.update=J.run.bind(J),ne=E.job=J.runIfDirty.bind(J);ne.i=E,ne.id=E.uid,J.scheduler=()=>Ff(ne),hs(E,!0),G()},z=(E,T,B)=>{T.component=E;const K=E.vnode.props;E.vnode=T,E.next=null,wA(E,T.props,K,B),xA(E,T.children,B),wn(),Im(E),_n()},P=(E,T,B,K,H,W,Q,X,J=!1)=>{const G=E&&E.children,ne=E?E.shapeFlag:0,Z=T.children,{patchFlag:se,shapeFlag:ce}=T;if(se>0){if(se&128){ye(G,Z,B,K,H,W,Q,X,J);return}else if(se&256){ee(G,Z,B,K,H,W,Q,X,J);return}}ce&8?(ne&16&&gt(G,H,W),Z!==G&&c(B,Z)):ne&16?ce&16?ye(G,Z,B,K,H,W,Q,X,J):gt(G,H,W,!0):(ne&8&&c(B,""),ce&16&&R(Z,B,K,H,W,Q,X,J))},ee=(E,T,B,K,H,W,Q,X,J)=>{E=E||gi,T=T||gi;const G=E.length,ne=T.length,Z=Math.min(G,ne);let se;for(se=0;se<Z;se++){const ce=T[se]=J?zn(T[se]):Xt(T[se]);m(E[se],ce,B,null,H,W,Q,X,J)}G>ne?gt(E,H,W,!0,!1,Z):R(T,B,K,H,W,Q,X,J,Z)},ye=(E,T,B,K,H,W,Q,X,J)=>{let G=0;const ne=T.length;let Z=E.length-1,se=ne-1;for(;G<=Z&&G<=se;){const ce=E[G],be=T[G]=J?zn(T[G]):Xt(T[G]);if(Lr(ce,be))m(ce,be,B,null,H,W,Q,X,J);else break;G++}for(;G<=Z&&G<=se;){const ce=E[Z],be=T[se]=J?zn(T[se]):Xt(T[se]);if(Lr(ce,be))m(ce,be,B,null,H,W,Q,X,J);else break;Z--,se--}if(G>Z){if(G<=se){const ce=se+1,be=ce<ne?T[ce].el:K;for(;G<=se;)m(null,T[G]=J?zn(T[G]):Xt(T[G]),B,be,H,W,Q,X,J),G++}}else if(G>se)for(;G<=Z;)_e(E[G],H,W,!0),G++;else{const ce=G,be=G,De=new Map;for(G=be;G<=se;G++){const N=T[G]=J?zn(T[G]):Xt(T[G]);N.key!=null&&De.set(N.key,G)}let xe,yt=0;const lt=se-be+1;let ur=!1,Ut=0;const on=new Array(lt);for(G=0;G<lt;G++)on[G]=0;for(G=ce;G<=Z;G++){const N=E[G];if(yt>=lt){_e(N,H,W,!0);continue}let L;if(N.key!=null)L=De.get(N.key);else for(xe=be;xe<=se;xe++)if(on[xe-be]===0&&Lr(N,T[xe])){L=xe;break}L===void 0?_e(N,H,W,!0):(on[L-be]=G+1,L>=Ut?Ut=L:ur=!0,m(N,T[L],B,null,H,W,Q,X,J),yt++)}const us=ur?CA(on):gi;for(xe=us.length-1,G=lt-1;G>=0;G--){const N=be+G,L=T[N],Ae=T[N+1],Le=N+1<ne?Ae.el||Ae.placeholder:K;on[G]===0?m(null,L,B,Le,H,W,Q,X,J):ur&&(xe<0||G!==us[xe]?re(L,B,Le,2):xe--)}}},re=(E,T,B,K,H=null)=>{const{el:W,type:Q,transition:X,children:J,shapeFlag:G}=E;if(G&6){re(E.component.subTree,T,B,K);return}if(G&128){E.suspense.move(T,B,K);return}if(G&64){Q.move(E,T,B,me);return}if(Q===bt){n(W,T,B);for(let Z=0;Z<J.length;Z++)re(J[Z],T,B,K);n(E.anchor,T,B);return}if(Q===Rs){w(E,T,B);return}if(K!==2&&G&1&&X)if(K===0)X.beforeEnter(W),n(W,T,B),dt(()=>X.enter(W),H);else{const{leave:Z,delayLeave:se,afterLeave:ce}=X,be=()=>{E.ctx.isUnmounted?s(W):n(W,T,B)},De=()=>{W._isLeaving&&W[fn](!0),Z(W,()=>{be(),ce&&ce()})};se?se(W,be,De):De()}else n(W,T,B)},_e=(E,T,B,K=!1,H=!1)=>{const{type:W,props:Q,ref:X,children:J,dynamicChildren:G,shapeFlag:ne,patchFlag:Z,dirs:se,cacheIndex:ce}=E;if(Z===-2&&(H=!1),X!=null&&(wn(),_i(X,null,B,E,!0),_n()),ce!=null&&(T.renderCache[ce]=void 0),ne&256){T.ctx.deactivate(E);return}const be=ne&1&&se,De=!Zn(E);let xe;if(De&&(xe=Q&&Q.onVnodeBeforeUnmount)&&Jt(xe,T,E),ne&6)_t(E.component,B,K);else{if(ne&128){E.suspense.unmount(B,K);return}be&&Jr(E,null,T,"beforeUnmount"),ne&64?E.type.remove(E,T,B,me,K):G&&!G.hasOnce&&(W!==bt||Z>0&&Z&64)?gt(G,T,B,!1,!0):(W===bt&&Z&384||!H&&ne&16)&&gt(J,T,B),K&&at(E)}(De&&(xe=Q&&Q.onVnodeUnmounted)||be)&&dt(()=>{xe&&Jt(xe,T,E),be&&Jr(E,null,T,"unmounted")},B)},at=E=>{const{type:T,el:B,anchor:K,transition:H}=E;if(T===bt){st(B,K);return}if(T===Rs){_(E);return}const W=()=>{s(B),H&&!H.persisted&&H.afterLeave&&H.afterLeave()};if(E.shapeFlag&1&&H&&!H.persisted){const{leave:Q,delayLeave:X}=H,J=()=>Q(B,W);X?X(E.el,W,J):J()}else W()},st=(E,T)=>{let B;for(;E!==T;)B=p(E),s(E),E=B;s(T)},_t=(E,T,B)=>{const{bum:K,scope:H,job:W,subTree:Q,um:X,m:J,a:G}=E;ql(J),ql(G),K&&vi(K),H.stop(),W&&(W.flags|=8,_e(Q,E,T,B)),X&&dt(X,T),dt(()=>{E.isUnmounted=!0},T)},gt=(E,T,B,K=!1,H=!1,W=0)=>{for(let Q=W;Q<E.length;Q++)_e(E[Q],T,B,K,H)},cr=E=>{if(E.shapeFlag&6)return cr(E.component.subTree);if(E.shapeFlag&128)return E.suspense.next();const T=p(E.anchor||E.el),B=T&&T[yw];return B?p(B):T};let _r=!1;const it=(E,T,B)=>{E==null?T._vnode&&_e(T._vnode,null,null,!0):m(T._vnode||null,E,T,null,null,null,B),T._vnode=E,_r||(_r=!0,Im(),Bl(),_r=!1)},me={p:m,um:_e,m:re,r:at,mt:I,mc:R,pc:P,pbc:A,n:cr,o:t};let qe,Re;return e&&([qe,Re]=e(me)),{render:it,hydrate:qe,createApp:yA(it,qe)}}function ju({type:t,props:e},r){return r==="svg"&&t==="foreignObject"||r==="mathml"&&t==="annotation-xml"&&e&&e.encoding&&e.encoding.includes("html")?void 0:r}function hs({effect:t,job:e},r){r?(t.flags|=32,e.flags|=4):(t.flags&=-33,e.flags&=-5)}function Jw(t,e){return(!t||t&&!t.pendingBranch)&&e&&!e.persisted}function Gf(t,e,r=!1){const n=t.children,s=e.children;if(te(n)&&te(s))for(let i=0;i<n.length;i++){const o=n[i];let a=s[i];a.shapeFlag&1&&!a.dynamicChildren&&((a.patchFlag<=0||a.patchFlag===32)&&(a=s[i]=zn(s[i]),a.el=o.el),!r&&a.patchFlag!==-2&&Gf(o,a)),a.type===es&&a.patchFlag!==-1&&(a.el=o.el),a.type===nt&&!a.el&&(a.el=o.el)}}function CA(t){const e=t.slice(),r=[0];let n,s,i,o,a;const l=t.length;for(n=0;n<l;n++){const u=t[n];if(u!==0){if(s=r[r.length-1],t[s]<u){e[n]=s,r.push(n);continue}for(i=0,o=r.length-1;i<o;)a=i+o>>1,t[r[a]]<u?i=a+1:o=a;u<t[r[i]]&&(i>0&&(e[n]=r[i-1]),r[i]=n)}}for(i=r.length,o=r[i-1];i-- >0;)r[i]=o,o=e[o];return r}function Xw(t){const e=t.subTree.component;if(e)return e.asyncDep&&!e.asyncResolved?e:Xw(e)}function ql(t){if(t)for(let e=0;e<t.length;e++)t[e].flags|=8}const Yw=Symbol.for("v-scx"),Qw=()=>Po(Yw);function AA(t,e){return ga(t,null,e)}function $A(t,e){return ga(t,null,{flush:"post"})}function Zw(t,e){return ga(t,null,{flush:"sync"})}function Os(t,e,r){return ga(t,e,r)}function ga(t,e,r=Se){const{immediate:n,deep:s,flush:i,once:o}=r,a=we({},r),l=e&&n||!e&&i!=="post";let u;if(Ai){if(i==="sync"){const f=Qw();u=f.__watcherHandles||(f.__watcherHandles=[])}else if(!l){const f=()=>{};return f.stop=Tt,f.resume=Tt,f.pause=Tt,f}}const c=At;a.call=(f,h,m)=>$r(f,c,h,m);let d=!1;i==="post"?a.scheduler=f=>{dt(f,c&&c.suspense)}:i!=="sync"&&(d=!0,a.scheduler=(f,h)=>{h?f():Ff(f)}),a.augmentJob=f=>{e&&(f.flags|=4),d&&(f.flags|=2,c&&(f.id=c.uid,f.i=c))};const p=uC(t,e,a);return Ai&&(u?u.push(p):l&&p()),p}function TA(t,e,r){const n=this.proxy,s=de(t)?t.includes(".")?e_(n,t):()=>n[t]:t.bind(n,n);let i;ae(e)?i=e:(i=e.handler,r=e);const o=Bs(this),a=ga(s,i.bind(n),r);return o(),a}function e_(t,e){const r=e.split(".");return()=>{let n=t;for(let s=0;s<r.length&&n;s++)n=n[r[s]];return n}}function kA(t,e,r=Se){const n=or(),s=We(e),i=Yt(e),o=t_(t,s),a=cw((l,u)=>{let c,d=Se,p;return Zw(()=>{const f=t[s];qt(c,f)&&(c=f,u())}),{get(){return l(),r.get?r.get(c):c},set(f){const h=r.set?r.set(f):f;if(!qt(h,c)&&!(d!==Se&&qt(f,d)))return;const m=n.vnode.props;m&&(e in m||s in m||i in m)&&(`onUpdate:${e}`in m||`onUpdate:${s}`in m||`onUpdate:${i}`in m)||(c=f,u()),n.emit(`update:${e}`,h),qt(f,h)&&qt(f,d)&&!qt(h,p)&&u(),d=f,p=h}}});return a[Symbol.iterator]=()=>{let l=0;return{next(){return l<2?{value:l++?o||Se:a,done:!1}:{done:!0}}}},a}const t_=(t,e)=>e==="modelValue"||e==="model-value"?t.modelModifiers:t[`${e}Modifiers`]||t[`${We(e)}Modifiers`]||t[`${Yt(e)}Modifiers`];function PA(t,e,...r){if(t.isUnmounted)return;const n=t.vnode.props||Se;let s=r;const i=e.startsWith("update:"),o=i&&t_(n,e.slice(7));o&&(o.trim&&(s=r.map(c=>de(c)?c.trim():c)),o.number&&(s=r.map(Tc)));let a,l=n[a=bi(e)]||n[a=bi(We(e))];!l&&i&&(l=n[a=bi(Yt(e))]),l&&$r(l,t,6,s);const u=n[a+"Once"];if(u){if(!t.emitted)t.emitted={};else if(t.emitted[a])return;t.emitted[a]=!0,$r(u,t,6,s)}}const OA=new WeakMap;function r_(t,e,r=!1){const n=r?OA:e.emitsCache,s=n.get(t);if(s!==void 0)return s;const i=t.emits;let o={},a=!1;if(!ae(t)){const l=u=>{const c=r_(u,e,!0);c&&(a=!0,we(o,c))};!r&&e.mixins.length&&e.mixins.forEach(l),t.extends&&l(t.extends),t.mixins&&t.mixins.forEach(l)}return!i&&!a?(Ne(t)&&n.set(t,null),null):(te(i)?i.forEach(l=>o[l]=null):we(o,i),Ne(t)&&n.set(t,o),o)}function Hc(t,e){return!t||!Gs(e)?!1:(e=e.slice(2).replace(/Once$/,""),Pe(t,e[0].toLowerCase()+e.slice(1))||Pe(t,Yt(e))||Pe(t,e))}function hl(t){const{type:e,vnode:r,proxy:n,withProxy:s,propsOptions:[i],slots:o,attrs:a,emit:l,render:u,renderCache:c,props:d,data:p,setupState:f,ctx:h,inheritAttrs:m}=t,g=Ko(t);let v,b;try{if(r.shapeFlag&4){const _=s||n,S=_;v=Xt(u.call(S,_,c,d,f,p,h)),b=a}else{const _=e;v=Xt(_.length>1?_(d,{attrs:a,slots:o,emit:l}):_(d,null)),b=e.props?a:NA(a)}}catch(_){Oo.length=0,Ys(_,t,1),v=Je(nt)}let w=v;if(b&&m!==!1){const _=Object.keys(b),{shapeFlag:S}=w;_.length&&S&7&&(i&&_.some(Af)&&(b=IA(b,i)),w=Yr(w,b,!1,!0))}return r.dirs&&(w=Yr(w,null,!1,!0),w.dirs=w.dirs?w.dirs.concat(r.dirs):r.dirs),r.transition&&Sn(w,r.transition),v=w,Ko(g),v}function RA(t,e=!0){let r;for(let n=0;n<t.length;n++){const s=t[n];if(xn(s)){if(s.type!==nt||s.children==="v-if"){if(r)return;r=s}}else return}return r}const NA=t=>{let e;for(const r in t)(r==="class"||r==="style"||Gs(r))&&((e||(e={}))[r]=t[r]);return e},IA=(t,e)=>{const r={};for(const n in t)(!Af(n)||!(n.slice(9)in e))&&(r[n]=t[n]);return r};function LA(t,e,r){const{props:n,children:s,component:i}=t,{props:o,children:a,patchFlag:l}=e,u=i.emitsOptions;if(e.dirs||e.transition)return!0;if(r&&l>=0){if(l&1024)return!0;if(l&16)return n?Km(n,o,u):!!o;if(l&8){const c=e.dynamicProps;for(let d=0;d<c.length;d++){const p=c[d];if(o[p]!==n[p]&&!Hc(u,p))return!0}}}else return(s||a)&&(!a||!a.$stable)?!0:n===o?!1:n?o?Km(n,o,u):!0:!!o;return!1}function Km(t,e,r){const n=Object.keys(e);if(n.length!==Object.keys(t).length)return!0;for(let s=0;s<n.length;s++){const i=n[s];if(e[i]!==t[i]&&!Hc(r,i))return!0}return!1}function zc({vnode:t,parent:e},r){for(;e;){const n=e.subTree;if(n.suspense&&n.suspense.activeBranch===t&&(n.el=t.el),n===t)(t=e.vnode).el=r,e=e.parent;else break}}const Hl=t=>t.__isSuspense;let xh=0;const FA={name:"Suspense",__isSuspense:!0,process(t,e,r,n,s,i,o,a,l,u){if(t==null)DA(e,r,n,s,i,o,a,l,u);else{if(i&&i.deps>0&&!t.suspense.isInFallback){e.suspense=t.suspense,e.suspense.vnode=e,e.el=t.el;return}VA(t,e,r,n,s,o,a,l,u)}},hydrate:BA,normalize:UA},MA=FA;function Jo(t,e){const r=t.props&&t.props[e];ae(r)&&r()}function DA(t,e,r,n,s,i,o,a,l){const{p:u,o:{createElement:c}}=l,d=c("div"),p=t.suspense=n_(t,s,n,e,d,r,i,o,a,l);u(null,p.pendingBranch=t.ssContent,d,null,n,p,i,o),p.deps>0?(Jo(t,"onPending"),Jo(t,"onFallback"),u(null,t.ssFallback,e,r,n,null,i,o),Ei(p,t.ssFallback)):p.resolve(!1,!0)}function VA(t,e,r,n,s,i,o,a,{p:l,um:u,o:{createElement:c}}){const d=e.suspense=t.suspense;d.vnode=e,e.el=t.el;const p=e.ssContent,f=e.ssFallback,{activeBranch:h,pendingBranch:m,isInFallback:g,isHydrating:v}=d;if(m)d.pendingBranch=p,Lr(m,p)?(l(m,p,d.hiddenContainer,null,s,d,i,o,a),d.deps<=0?d.resolve():g&&(v||(l(h,f,r,n,s,null,i,o,a),Ei(d,f)))):(d.pendingId=xh++,v?(d.isHydrating=!1,d.activeBranch=m):u(m,s,d),d.deps=0,d.effects.length=0,d.hiddenContainer=c("div"),g?(l(null,p,d.hiddenContainer,null,s,d,i,o,a),d.deps<=0?d.resolve():(l(h,f,r,n,s,null,i,o,a),Ei(d,f))):h&&Lr(h,p)?(l(h,p,r,n,s,d,i,o,a),d.resolve(!0)):(l(null,p,d.hiddenContainer,null,s,d,i,o,a),d.deps<=0&&d.resolve()));else if(h&&Lr(h,p))l(h,p,r,n,s,d,i,o,a),Ei(d,p);else if(Jo(e,"onPending"),d.pendingBranch=p,p.shapeFlag&512?d.pendingId=p.component.suspenseId:d.pendingId=xh++,l(null,p,d.hiddenContainer,null,s,d,i,o,a),d.deps<=0)d.resolve();else{const{timeout:b,pendingId:w}=d;b>0?setTimeout(()=>{d.pendingId===w&&d.fallback(f)},b):b===0&&d.fallback(f)}}function n_(t,e,r,n,s,i,o,a,l,u,c=!1){const{p:d,m:p,um:f,n:h,o:{parentNode:m,remove:g}}=u;let v;const b=jA(t);b&&e&&e.pendingBranch&&(v=e.pendingId,e.deps++);const w=t.props?Nl(t.props.timeout):void 0,_=i,S={vnode:t,parent:e,parentComponent:r,namespace:o,container:n,hiddenContainer:s,deps:0,pendingId:xh++,timeout:typeof w=="number"?w:-1,activeBranch:null,pendingBranch:null,isInFallback:!c,isHydrating:c,isUnmounted:!1,effects:[],resolve(k=!1,O=!1){const{vnode:R,activeBranch:C,pendingBranch:A,pendingId:j,effects:$,parentComponent:y,container:I,isInFallback:q}=S;let D=!1;S.isHydrating?S.isHydrating=!1:k||(D=C&&A.transition&&A.transition.mode==="out-in",D&&(C.transition.afterLeave=()=>{j===S.pendingId&&(p(A,I,i===_?h(C):i,0),zo($),q&&R.ssFallback&&(R.ssFallback.el=null))}),C&&(m(C.el)===I&&(i=h(C)),f(C,y,S,!0),!D&&q&&R.ssFallback&&(R.ssFallback.el=null)),D||p(A,I,i,0)),Ei(S,A),S.pendingBranch=null,S.isInFallback=!1;let z=S.parent,P=!1;for(;z;){if(z.pendingBranch){z.effects.push(...$),P=!0;break}z=z.parent}!P&&!D&&zo($),S.effects=[],b&&e&&e.pendingBranch&&v===e.pendingId&&(e.deps--,e.deps===0&&!O&&e.resolve()),Jo(R,"onResolve")},fallback(k){if(!S.pendingBranch)return;const{vnode:O,activeBranch:R,parentComponent:C,container:A,namespace:j}=S;Jo(O,"onFallback");const $=h(R),y=()=>{S.isInFallback&&(d(null,k,A,$,C,null,j,a,l),Ei(S,k))},I=k.transition&&k.transition.mode==="out-in";I&&(R.transition.afterLeave=y),S.isInFallback=!0,f(R,C,null,!0),I||y()},move(k,O,R){S.activeBranch&&p(S.activeBranch,k,O,R),S.container=k},next(){return S.activeBranch&&h(S.activeBranch)},registerDep(k,O,R){const C=!!S.pendingBranch;C&&S.deps++;const A=k.vnode.el;k.asyncDep.catch(j=>{Ys(j,k,0)}).then(j=>{if(k.isUnmounted||S.isUnmounted||S.pendingId!==k.suspenseId)return;k.asyncResolved=!0;const{vnode:$}=k;$h(k,j,!1),A&&($.el=A);const y=!A&&k.subTree.el;O(k,$,m(A||k.subTree.el),A?null:h(k.subTree),S,o,R),y&&($.placeholder=null,g(y)),zc(k,$.el),C&&--S.deps===0&&S.resolve()})},unmount(k,O){S.isUnmounted=!0,S.activeBranch&&f(S.activeBranch,r,k,O),S.pendingBranch&&f(S.pendingBranch,r,k,O)}};return S}function BA(t,e,r,n,s,i,o,a,l){const u=e.suspense=n_(e,n,r,t.parentNode,document.createElement("div"),null,s,i,o,a,!0),c=l(t,u.pendingBranch=e.ssContent,r,u,i,o);return u.deps===0&&u.resolve(!1,!0),c}function UA(t){const{shapeFlag:e,children:r}=t,n=e&32;t.ssContent=Gm(n?r.default:r),t.ssFallback=n?Gm(r.fallback):Je(nt)}function Gm(t){let e;if(ae(t)){const r=Vs&&t._c;r&&(t._d=!1,Xo()),t=t(),r&&(t._d=!0,e=Ft,i_())}return te(t)&&(t=RA(t)),t=Xt(t),e&&!t.dynamicChildren&&(t.dynamicChildren=e.filter(r=>r!==t)),t}function s_(t,e){e&&e.pendingBranch?te(t)?e.effects.push(...t):e.effects.push(t):zo(t)}function Ei(t,e){t.activeBranch=e;const{vnode:r,parentComponent:n}=t;let s=e.el;for(;!s&&e.component;)e=e.component.subTree,s=e.el;r.el=s,n&&n.subTree===r&&(n.vnode.el=s,zc(n,s))}function jA(t){const e=t.props&&t.props.suspensible;return e!=null&&e!==!1}const bt=Symbol.for("v-fgt"),es=Symbol.for("v-txt"),nt=Symbol.for("v-cmt"),Rs=Symbol.for("v-stc"),Oo=[];let Ft=null;function Xo(t=!1){Oo.push(Ft=t?null:[])}function i_(){Oo.pop(),Ft=Oo[Oo.length-1]||null}let Vs=1;function Yo(t,e=!1){Vs+=t,t<0&&Ft&&e&&(Ft.hasOnce=!0)}function o_(t){return t.dynamicChildren=Vs>0?Ft||gi:null,i_(),Vs>0&&Ft&&Ft.push(t),t}function qA(t,e,r,n,s,i){return o_(Jf(t,e,r,n,s,i,!0))}function zl(t,e,r,n,s){return o_(Je(t,e,r,n,s,!0))}function xn(t){return t?t.__v_isVNode===!0:!1}function Lr(t,e){return t.type===e.type&&t.key===e.key}function HA(t){}const a_=({key:t})=>t??null,fl=({ref:t,ref_key:e,ref_for:r})=>(typeof t=="number"&&(t=""+t),t!=null?de(t)||ft(t)||ae(t)?{i:$t,r:t,k:e,f:!!r}:t:null);function Jf(t,e=null,r=null,n=0,s=null,i=t===bt?0:1,o=!1,a=!1){const l={__v_isVNode:!0,__v_skip:!0,type:t,props:e,key:e&&a_(e),ref:e&&fl(e),scopeId:Dc,slotScopeIds:null,children:r,component:null,suspense:null,ssContent:null,ssFallback:null,dirs:null,transition:null,el:null,anchor:null,target:null,targetStart:null,targetAnchor:null,staticCount:0,shapeFlag:i,patchFlag:n,dynamicProps:s,dynamicChildren:null,appContext:null,ctx:$t};return a?(Yf(l,r),i&128&&t.normalize(l)):r&&(l.shapeFlag|=de(r)?8:16),Vs>0&&!o&&Ft&&(l.patchFlag>0||i&6)&&l.patchFlag!==32&&Ft.push(l),l}const Je=zA;function zA(t,e=null,r=null,n=0,s=null,i=!1){if((!t||t===Nw)&&(t=nt),xn(t)){const a=Yr(t,e,!0);return r&&Yf(a,r),Vs>0&&!i&&Ft&&(a.shapeFlag&6?Ft[Ft.indexOf(t)]=a:Ft.push(a)),a.patchFlag=-2,a}if(ZA(t)&&(t=t.__vccOpts),e){e=l_(e);let{class:a,style:l}=e;a&&!de(a)&&(e.class=fa(a)),Ne(l)&&(Lc(l)&&!te(l)&&(l=we({},l)),e.style=ha(l))}const o=de(t)?1:Hl(t)?128:bw(t)?64:Ne(t)?4:ae(t)?2:0;return Jf(t,e,r,n,s,o,i,!0)}function l_(t){return t?Lc(t)||Bw(t)?we({},t):t:null}function Yr(t,e,r=!1,n=!1){const{props:s,ref:i,patchFlag:o,children:a,transition:l}=t,u=e?c_(s||{},e):s,c={__v_isVNode:!0,__v_skip:!0,type:t.type,props:u,key:u&&a_(u),ref:e&&e.ref?r&&i?te(i)?i.concat(fl(e)):[i,fl(e)]:fl(e):i,scopeId:t.scopeId,slotScopeIds:t.slotScopeIds,children:a,target:t.target,targetStart:t.targetStart,targetAnchor:t.targetAnchor,staticCount:t.staticCount,shapeFlag:t.shapeFlag,patchFlag:e&&t.type!==bt?o===-1?16:o|16:o,dynamicProps:t.dynamicProps,dynamicChildren:t.dynamicChildren,appContext:t.appContext,dirs:t.dirs,transition:l,component:t.component,suspense:t.suspense,ssContent:t.ssContent&&Yr(t.ssContent),ssFallback:t.ssFallback&&Yr(t.ssFallback),placeholder:t.placeholder,el:t.el,anchor:t.anchor,ctx:t.ctx,ce:t.ce};return l&&n&&Sn(c,l.clone(c)),c}function Xf(t=" ",e=0){return Je(es,null,t,e)}function WA(t,e){const r=Je(Rs,null,t);return r.staticCount=e,r}function KA(t="",e=!1){return e?(Xo(),zl(nt,null,t)):Je(nt,null,t)}function Xt(t){return t==null||typeof t=="boolean"?Je(nt):te(t)?Je(bt,null,t.slice()):xn(t)?zn(t):Je(es,null,String(t))}function zn(t){return t.el===null&&t.patchFlag!==-1||t.memo?t:Yr(t)}function Yf(t,e){let r=0;const{shapeFlag:n}=t;if(e==null)e=null;else if(te(e))r=16;else if(typeof e=="object")if(n&65){const s=e.default;s&&(s._c&&(s._d=!1),Yf(t,s()),s._c&&(s._d=!0));return}else{r=32;const s=e._;!s&&!Bw(e)?e._ctx=$t:s===3&&$t&&($t.slots._===1?e._=1:(e._=2,t.patchFlag|=1024))}else ae(e)?(e={default:e,_ctx:$t},r=32):(e=String(e),n&64?(r=16,e=[Xf(e)]):r=8);t.children=e,t.shapeFlag|=r}function c_(...t){const e={};for(let r=0;r<t.length;r++){const n=t[r];for(const s in n)if(s==="class")e.class!==n.class&&(e.class=fa([e.class,n.class]));else if(s==="style")e.style=ha([e.style,n.style]);else if(Gs(s)){const i=e[s],o=n[s];o&&i!==o&&!(te(i)&&i.includes(o))&&(e[s]=i?[].concat(i,o):o)}else s!==""&&(e[s]=n[s])}return e}function Jt(t,e,r,n=null){$r(t,e,7,[r,n])}const GA=Fw();let JA=0;function u_(t,e,r){const n=t.type,s=(e?e.appContext:t.appContext)||GA,i={uid:JA++,vnode:t,type:n,parent:e,appContext:s,root:null,next:null,subTree:null,effect:null,update:null,job:null,scope:new Pf(!0),render:null,proxy:null,exposed:null,exposeProxy:null,withProxy:null,provides:e?e.provides:Object.create(s.provides),ids:e?e.ids:["",0,0],accessCache:null,renderCache:[],components:null,directives:null,propsOptions:jw(n,s),emitsOptions:r_(n,s),emit:null,emitted:null,propsDefaults:Se,inheritAttrs:n.inheritAttrs,ctx:Se,data:Se,props:Se,attrs:Se,slots:Se,refs:Se,setupState:Se,setupContext:null,suspense:r,suspenseId:r?r.pendingId:0,asyncDep:null,asyncResolved:!1,isMounted:!1,isUnmounted:!1,isDeactivated:!1,bc:null,c:null,bm:null,m:null,bu:null,u:null,um:null,bum:null,da:null,a:null,rtg:null,rtc:null,ec:null,sp:null};return i.ctx={_:i},i.root=e?e.root:i,i.emit=PA.bind(null,i),t.ce&&t.ce(i),i}let At=null;const or=()=>At||$t;let Wl,Ch;{const t=kc(),e=(r,n)=>{let s;return(s=t[r])||(s=t[r]=[]),s.push(n),i=>{s.length>1?s.forEach(o=>o(i)):s[0](i)}};Wl=e("__VUE_INSTANCE_SETTERS__",r=>At=r),Ch=e("__VUE_SSR_SETTERS__",r=>Ai=r)}const Bs=t=>{const e=At;return Wl(t),t.scope.on(),()=>{t.scope.off(),Wl(e)}},Ah=()=>{At&&At.scope.off(),Wl(null)};function d_(t){return t.vnode.shapeFlag&4}let Ai=!1;function h_(t,e=!1,r=!1){e&&Ch(e);const{props:n,children:s}=t.vnode,i=d_(t);vA(t,n,i,e),SA(t,s,r||e);const o=i?XA(t,e):void 0;return e&&Ch(!1),o}function XA(t,e){const r=t.type;t.accessCache=Object.create(null),t.proxy=new Proxy(t.ctx,wh);const{setup:n}=r;if(n){wn();const s=t.setupContext=n.length>1?m_(t):null,i=Bs(t),o=Ki(n,t,0,[t.props,s]),a=Tf(o);if(_n(),i(),(a||t.sp)&&!Zn(t)&&Bf(t),a){if(o.then(Ah,Ah),e)return o.then(l=>{$h(t,l,e)}).catch(l=>{Ys(l,t,0)});t.asyncDep=o}else $h(t,o,e)}else p_(t,e)}function $h(t,e,r){ae(e)?t.type.__ssrInlineRender?t.ssrRender=e:t.render=e:Ne(e)&&(t.setupState=Lf(e)),p_(t,r)}let Kl,Th;function f_(t){Kl=t,Th=e=>{e.render._rc&&(e.withProxy=new Proxy(e.ctx,YC))}}const YA=()=>!Kl;function p_(t,e,r){const n=t.type;if(!t.render){if(!e&&Kl&&!n.render){const s=n.template||zf(t).template;if(s){const{isCustomElement:i,compilerOptions:o}=t.appContext.config,{delimiters:a,compilerOptions:l}=n,u=we(we({isCustomElement:i,delimiters:a},o),l);n.render=Kl(s,u)}}t.render=n.render||Tt,Th&&Th(t)}{const s=Bs(t);wn();try{dA(t)}finally{_n(),s()}}}const QA={get(t,e){return Nt(t,"get",""),t[e]}};function m_(t){const e=r=>{t.exposed=r||{}};return{attrs:new Proxy(t.attrs,QA),slots:t.slots,emit:t.emit,expose:e}}function ya(t){return t.exposed?t.exposeProxy||(t.exposeProxy=new Proxy(Lf(Fl(t.exposed)),{get(e,r){if(r in e)return e[r];if(r in ko)return ko[r](t)},has(e,r){return r in e||r in ko}})):t.proxy}function kh(t,e=!0){return ae(t)?t.displayName||t.name:t.name||e&&t.__name}function ZA(t){return ae(t)&&"__vccOpts"in t}const Kt=(t,e)=>oC(t,e,Ai);function Ns(t,e,r){try{Yo(-1);const n=arguments.length;return n===2?Ne(e)&&!te(e)?xn(e)?Je(t,null,[e]):Je(t,e):Je(t,null,e):(n>3?r=Array.prototype.slice.call(arguments,2):n===3&&xn(r)&&(r=[r]),Je(t,e,r))}finally{Yo(1)}}function e$(){}function t$(t,e,r,n){const s=r[n];if(s&&g_(s,t))return s;const i=e();return i.memo=t.slice(),i.cacheIndex=n,r[n]=i}function g_(t,e){const r=t.memo;if(r.length!=e.length)return!1;for(let n=0;n<r.length;n++)if(qt(r[n],e[n]))return!1;return Vs>0&&Ft&&Ft.push(t),!0}const y_="3.5.24",r$=Tt,n$=mC,s$=ci,i$=gw,o$={createComponentInstance:u_,setupComponent:h_,renderComponentRoot:hl,setCurrentRenderingInstance:Ko,isVNode:xn,normalizeVNode:Xt,getComponentPublicInstance:ya,ensureValidVNode:Hf,pushWarningContext:dC,popWarningContext:hC},a$=o$,l$=null,c$=null,u$=null;let Ph;const Jm=typeof window<"u"&&window.trustedTypes;if(Jm)try{Ph=Jm.createPolicy("vue",{createHTML:t=>t})}catch{}const b_=Ph?t=>Ph.createHTML(t):t=>t,d$="http://www.w3.org/2000/svg",h$="http://www.w3.org/1998/Math/MathML",hn=typeof document<"u"?document:null,Xm=hn&&hn.createElement("template"),f$={insert:(t,e,r)=>{e.insertBefore(t,r||null)},remove:t=>{const e=t.parentNode;e&&e.removeChild(t)},createElement:(t,e,r,n)=>{const s=e==="svg"?hn.createElementNS(d$,t):e==="mathml"?hn.createElementNS(h$,t):r?hn.createElement(t,{is:r}):hn.createElement(t);return t==="select"&&n&&n.multiple!=null&&s.setAttribute("multiple",n.multiple),s},createText:t=>hn.createTextNode(t),createComment:t=>hn.createComment(t),setText:(t,e)=>{t.nodeValue=e},setElementText:(t,e)=>{t.textContent=e},parentNode:t=>t.parentNode,nextSibling:t=>t.nextSibling,querySelector:t=>hn.querySelector(t),setScopeId(t,e){t.setAttribute(e,"")},insertStaticContent(t,e,r,n,s,i){const o=r?r.previousSibling:e.lastChild;if(s&&(s===i||s.nextSibling))for(;e.insertBefore(s.cloneNode(!0),r),!(s===i||!(s=s.nextSibling)););else{Xm.innerHTML=b_(n==="svg"?`<svg>${t}</svg>`:n==="mathml"?`<math>${t}</math>`:t);const a=Xm.content;if(n==="svg"||n==="mathml"){const l=a.firstChild;for(;l.firstChild;)a.appendChild(l.firstChild);a.removeChild(l)}e.insertBefore(a,r)}return[o?o.nextSibling:e.firstChild,r?r.previousSibling:e.lastChild]}},In="transition",co="animation",$i=Symbol("_vtc"),v_={name:String,type:String,css:{type:Boolean,default:!0},duration:[String,Number,Object],enterFromClass:String,enterActiveClass:String,enterToClass:String,appearFromClass:String,appearActiveClass:String,appearToClass:String,leaveFromClass:String,leaveActiveClass:String,leaveToClass:String},w_=we({},Vf,v_),p$=t=>(t.displayName="Transition",t.props=w_,t),m$=p$((t,{slots:e})=>Ns(Sw,__(t),e)),fs=(t,e=[])=>{te(t)?t.forEach(r=>r(...e)):t&&t(...e)},Ym=t=>t?te(t)?t.some(e=>e.length>1):t.length>1:!1;function __(t){const e={};for(const $ in t)$ in v_||(e[$]=t[$]);if(t.css===!1)return e;const{name:r="v",type:n,duration:s,enterFromClass:i=`${r}-enter-from`,enterActiveClass:o=`${r}-enter-active`,enterToClass:a=`${r}-enter-to`,appearFromClass:l=i,appearActiveClass:u=o,appearToClass:c=a,leaveFromClass:d=`${r}-leave-from`,leaveActiveClass:p=`${r}-leave-active`,leaveToClass:f=`${r}-leave-to`}=t,h=g$(s),m=h&&h[0],g=h&&h[1],{onBeforeEnter:v,onEnter:b,onEnterCancelled:w,onLeave:_,onLeaveCancelled:S,onBeforeAppear:k=v,onAppear:O=b,onAppearCancelled:R=w}=e,C=($,y,I,q)=>{$._enterCancelled=q,jn($,y?c:a),jn($,y?u:o),I&&I()},A=($,y)=>{$._isLeaving=!1,jn($,d),jn($,f),jn($,p),y&&y()},j=$=>(y,I)=>{const q=$?O:b,D=()=>C(y,$,I);fs(q,[y,D]),Qm(()=>{jn(y,$?l:i),Kr(y,$?c:a),Ym(q)||Zm(y,n,m,D)})};return we(e,{onBeforeEnter($){fs(v,[$]),Kr($,i),Kr($,o)},onBeforeAppear($){fs(k,[$]),Kr($,l),Kr($,u)},onEnter:j(!1),onAppear:j(!0),onLeave($,y){$._isLeaving=!0;const I=()=>A($,y);Kr($,d),$._enterCancelled?(Kr($,p),Oh($)):(Oh($),Kr($,p)),Qm(()=>{$._isLeaving&&(jn($,d),Kr($,f),Ym(_)||Zm($,n,g,I))}),fs(_,[$,I])},onEnterCancelled($){C($,!1,void 0,!0),fs(w,[$])},onAppearCancelled($){C($,!0,void 0,!0),fs(R,[$])},onLeaveCancelled($){A($),fs(S,[$])}})}function g$(t){if(t==null)return null;if(Ne(t))return[qu(t.enter),qu(t.leave)];{const e=qu(t);return[e,e]}}function qu(t){return Nl(t)}function Kr(t,e){e.split(/\s+/).forEach(r=>r&&t.classList.add(r)),(t[$i]||(t[$i]=new Set)).add(e)}function jn(t,e){e.split(/\s+/).forEach(n=>n&&t.classList.remove(n));const r=t[$i];r&&(r.delete(e),r.size||(t[$i]=void 0))}function Qm(t){requestAnimationFrame(()=>{requestAnimationFrame(t)})}let y$=0;function Zm(t,e,r,n){const s=t._endId=++y$,i=()=>{s===t._endId&&n()};if(r!=null)return setTimeout(i,r);const{type:o,timeout:a,propCount:l}=E_(t,e);if(!o)return n();const u=o+"end";let c=0;const d=()=>{t.removeEventListener(u,p),i()},p=f=>{f.target===t&&++c>=l&&d()};setTimeout(()=>{c<l&&d()},a+1),t.addEventListener(u,p)}function E_(t,e){const r=window.getComputedStyle(t),n=h=>(r[h]||"").split(", "),s=n(`${In}Delay`),i=n(`${In}Duration`),o=eg(s,i),a=n(`${co}Delay`),l=n(`${co}Duration`),u=eg(a,l);let c=null,d=0,p=0;e===In?o>0&&(c=In,d=o,p=i.length):e===co?u>0&&(c=co,d=u,p=l.length):(d=Math.max(o,u),c=d>0?o>u?In:co:null,p=c?c===In?i.length:l.length:0);const f=c===In&&/\b(?:transform|all)(?:,|$)/.test(n(`${In}Property`).toString());return{type:c,timeout:d,propCount:p,hasTransform:f}}function eg(t,e){for(;t.length<e.length;)t=t.concat(t);return Math.max(...e.map((r,n)=>tg(r)+tg(t[n])))}function tg(t){return t==="auto"?0:Number(t.slice(0,-1).replace(",","."))*1e3}function Oh(t){return(t?t.ownerDocument:document).body.offsetHeight}function b$(t,e,r){const n=t[$i];n&&(e=(e?[e,...n]:[...n]).join(" ")),e==null?t.removeAttribute("class"):r?t.setAttribute("class",e):t.className=e}const Gl=Symbol("_vod"),S_=Symbol("_vsh"),x_={name:"show",beforeMount(t,{value:e},{transition:r}){t[Gl]=t.style.display==="none"?"":t.style.display,r&&e?r.beforeEnter(t):uo(t,e)},mounted(t,{value:e},{transition:r}){r&&e&&r.enter(t)},updated(t,{value:e,oldValue:r},{transition:n}){!e!=!r&&(n?e?(n.beforeEnter(t),uo(t,!0),n.enter(t)):n.leave(t,()=>{uo(t,!1)}):uo(t,e))},beforeUnmount(t,{value:e}){uo(t,e)}};function uo(t,e){t.style.display=e?t[Gl]:"none",t[S_]=!e}function v$(){x_.getSSRProps=({value:t})=>{if(!t)return{style:{display:"none"}}}}const C_=Symbol("");function w$(t){const e=or();if(!e)return;const r=e.ut=(s=t(e.proxy))=>{Array.from(document.querySelectorAll(`[data-v-owner="${e.uid}"]`)).forEach(i=>Jl(i,s))},n=()=>{const s=t(e.proxy);e.ce?Jl(e.ce,s):Rh(e.subTree,s),r(s)};Uf(()=>{zo(n)}),ma(()=>{Os(n,Tt,{flush:"post"});const s=new MutationObserver(n);s.observe(e.subTree.el.parentNode,{childList:!0}),qc(()=>s.disconnect())})}function Rh(t,e){if(t.shapeFlag&128){const r=t.suspense;t=r.activeBranch,r.pendingBranch&&!r.isHydrating&&r.effects.push(()=>{Rh(r.activeBranch,e)})}for(;t.component;)t=t.component.subTree;if(t.shapeFlag&1&&t.el)Jl(t.el,e);else if(t.type===bt)t.children.forEach(r=>Rh(r,e));else if(t.type===Rs){let{el:r,anchor:n}=t;for(;r&&(Jl(r,e),r!==n);)r=r.nextSibling}}function Jl(t,e){if(t.nodeType===1){const r=t.style;let n="";for(const s in e){const i=Cx(e[s]);r.setProperty(`--${s}`,i),n+=`--${s}: ${i};`}r[C_]=n}}const _$=/(?:^|;)\s*display\s*:/;function E$(t,e,r){const n=t.style,s=de(r);let i=!1;if(r&&!s){if(e)if(de(e))for(const o of e.split(";")){const a=o.slice(0,o.indexOf(":")).trim();r[a]==null&&pl(n,a,"")}else for(const o in e)r[o]==null&&pl(n,o,"");for(const o in r)o==="display"&&(i=!0),pl(n,o,r[o])}else if(s){if(e!==r){const o=n[C_];o&&(r+=";"+o),n.cssText=r,i=_$.test(r)}}else e&&t.removeAttribute("style");Gl in t&&(t[Gl]=i?n.display:"",t[S_]&&(n.display="none"))}const rg=/\s*!important$/;function pl(t,e,r){if(te(r))r.forEach(n=>pl(t,e,n));else if(r==null&&(r=""),e.startsWith("--"))t.setProperty(e,r);else{const n=S$(t,e);rg.test(r)?t.setProperty(Yt(n),r.replace(rg,""),"important"):t[n]=r}}const ng=["Webkit","Moz","ms"],Hu={};function S$(t,e){const r=Hu[e];if(r)return r;let n=We(e);if(n!=="filter"&&n in t)return Hu[e]=n;n=Xs(n);for(let s=0;s<ng.length;s++){const i=ng[s]+n;if(i in t)return Hu[e]=i}return e}const sg="http://www.w3.org/1999/xlink";function ig(t,e,r,n,s,i=Sx(e)){n&&e.startsWith("xlink:")?r==null?t.removeAttributeNS(sg,e.slice(6,e.length)):t.setAttributeNS(sg,e,r):r==null||i&&!Uv(r)?t.removeAttribute(e):t.setAttribute(e,i?"":ir(r)?String(r):r)}function og(t,e,r,n,s){if(e==="innerHTML"||e==="textContent"){r!=null&&(t[e]=e==="innerHTML"?b_(r):r);return}const i=t.tagName;if(e==="value"&&i!=="PROGRESS"&&!i.includes("-")){const a=i==="OPTION"?t.getAttribute("value")||"":t.value,l=r==null?t.type==="checkbox"?"on":"":String(r);(a!==l||!("_value"in t))&&(t.value=l),r==null&&t.removeAttribute(e),t._value=r;return}let o=!1;if(r===""||r==null){const a=typeof t[e];a==="boolean"?r=Uv(r):r==null&&a==="string"?(r="",o=!0):a==="number"&&(r=0,o=!0)}try{t[e]=r}catch{}o&&t.removeAttribute(s||e)}function gn(t,e,r,n){t.addEventListener(e,r,n)}function x$(t,e,r,n){t.removeEventListener(e,r,n)}const ag=Symbol("_vei");function C$(t,e,r,n,s=null){const i=t[ag]||(t[ag]={}),o=i[e];if(n&&o)o.value=n;else{const[a,l]=A$(e);if(n){const u=i[e]=k$(n,s);gn(t,a,u,l)}else o&&(x$(t,a,o,l),i[e]=void 0)}}const lg=/(?:Once|Passive|Capture)$/;function A$(t){let e;if(lg.test(t)){e={};let n;for(;n=t.match(lg);)t=t.slice(0,t.length-n[0].length),e[n[0].toLowerCase()]=!0}return[t[2]===":"?t.slice(3):Yt(t.slice(2)),e]}let zu=0;const $$=Promise.resolve(),T$=()=>zu||($$.then(()=>zu=0),zu=Date.now());function k$(t,e){const r=n=>{if(!n._vts)n._vts=Date.now();else if(n._vts<=r.attached)return;$r(P$(n,r.value),e,5,[n])};return r.value=t,r.attached=T$(),r}function P$(t,e){if(te(e)){const r=t.stopImmediatePropagation;return t.stopImmediatePropagation=()=>{r.call(t),t._stopped=!0},e.map(n=>s=>!s._stopped&&n&&n(s))}else return e}const cg=t=>t.charCodeAt(0)===111&&t.charCodeAt(1)===110&&t.charCodeAt(2)>96&&t.charCodeAt(2)<123,O$=(t,e,r,n,s,i)=>{const o=s==="svg";e==="class"?b$(t,n,o):e==="style"?E$(t,r,n):Gs(e)?Af(e)||C$(t,e,r,n,i):(e[0]==="."?(e=e.slice(1),!0):e[0]==="^"?(e=e.slice(1),!1):R$(t,e,n,o))?(og(t,e,n),!t.tagName.includes("-")&&(e==="value"||e==="checked"||e==="selected")&&ig(t,e,n,o,i,e!=="value")):t._isVueCE&&(/[A-Z]/.test(e)||!de(n))?og(t,We(e),n,i,e):(e==="true-value"?t._trueValue=n:e==="false-value"&&(t._falseValue=n),ig(t,e,n,o))};function R$(t,e,r,n){if(n)return!!(e==="innerHTML"||e==="textContent"||e in t&&cg(e)&&ae(r));if(e==="spellcheck"||e==="draggable"||e==="translate"||e==="autocorrect"||e==="sandbox"&&t.tagName==="IFRAME"||e==="form"||e==="list"&&t.tagName==="INPUT"||e==="type"&&t.tagName==="TEXTAREA")return!1;if(e==="width"||e==="height"){const s=t.tagName;if(s==="IMG"||s==="VIDEO"||s==="CANVAS"||s==="SOURCE")return!1}return cg(e)&&de(r)?!1:e in t}const ug={};function A_(t,e,r){let n=Gi(t,e);Ac(n)&&(n=we({},n,e));class s extends Wc{constructor(o){super(n,o,r)}}return s.def=n,s}const N$=((t,e)=>A_(t,e,ep)),I$=typeof HTMLElement<"u"?HTMLElement:class{};class Wc extends I${constructor(e,r={},n=Ql){super(),this._def=e,this._props=r,this._createApp=n,this._isVueCE=!0,this._instance=null,this._app=null,this._nonce=this._def.nonce,this._connected=!1,this._resolved=!1,this._patching=!1,this._dirty=!1,this._numberProps=null,this._styleChildren=new WeakSet,this._ob=null,this.shadowRoot&&n!==Ql?this._root=this.shadowRoot:e.shadowRoot!==!1?(this.attachShadow(we({},e.shadowRootOptions,{mode:"open"})),this._root=this.shadowRoot):this._root=this}connectedCallback(){if(!this.isConnected)return;!this.shadowRoot&&!this._resolved&&this._parseSlots(),this._connected=!0;let e=this;for(;e=e&&(e.parentNode||e.host);)if(e instanceof Wc){this._parent=e;break}this._instance||(this._resolved?this._mount(this._def):e&&e._pendingResolve?this._pendingResolve=e._pendingResolve.then(()=>{this._pendingResolve=void 0,this._resolveDef()}):this._resolveDef())}_setParent(e=this._parent){e&&(this._instance.parent=e._instance,this._inheritParentContext(e))}_inheritParentContext(e=this._parent){e&&this._app&&Object.setPrototypeOf(this._app._context.provides,e._instance.provides)}disconnectedCallback(){this._connected=!1,Mc(()=>{this._connected||(this._ob&&(this._ob.disconnect(),this._ob=null),this._app&&this._app.unmount(),this._instance&&(this._instance.ce=void 0),this._app=this._instance=null,this._teleportTargets&&(this._teleportTargets.clear(),this._teleportTargets=void 0))})}_processMutations(e){for(const r of e)this._setAttr(r.attributeName)}_resolveDef(){if(this._pendingResolve)return;for(let n=0;n<this.attributes.length;n++)this._setAttr(this.attributes[n].name);this._ob=new MutationObserver(this._processMutations.bind(this)),this._ob.observe(this,{attributes:!0});const e=(n,s=!1)=>{this._resolved=!0,this._pendingResolve=void 0;const{props:i,styles:o}=n;let a;if(i&&!te(i))for(const l in i){const u=i[l];(u===Number||u&&u.type===Number)&&(l in this._props&&(this._props[l]=Nl(this._props[l])),(a||(a=Object.create(null)))[We(l)]=!0)}this._numberProps=a,this._resolveProps(n),this.shadowRoot&&this._applyStyles(o),this._mount(n)},r=this._def.__asyncLoader;r?this._pendingResolve=r().then(n=>{n.configureApp=this._def.configureApp,e(this._def=n,!0)}):e(this._def)}_mount(e){this._app=this._createApp(e),this._inheritParentContext(),e.configureApp&&e.configureApp(this._app),this._app._ceVNode=this._createVNode(),this._app.mount(this._root);const r=this._instance&&this._instance.exposed;if(r)for(const n in r)Pe(this,n)||Object.defineProperty(this,n,{get:()=>Fc(r[n])})}_resolveProps(e){const{props:r}=e,n=te(r)?r:Object.keys(r||{});for(const s of Object.keys(this))s[0]!=="_"&&n.includes(s)&&this._setProp(s,this[s]);for(const s of n.map(We))Object.defineProperty(this,s,{get(){return this._getProp(s)},set(i){this._setProp(s,i,!0,!this._patching)}})}_setAttr(e){if(e.startsWith("data-v-"))return;const r=this.hasAttribute(e);let n=r?this.getAttribute(e):ug;const s=We(e);r&&this._numberProps&&this._numberProps[s]&&(n=Nl(n)),this._setProp(s,n,!1,!0)}_getProp(e){return this._props[e]}_setProp(e,r,n=!0,s=!1){if(r!==this._props[e]&&(this._dirty=!0,r===ug?delete this._props[e]:(this._props[e]=r,e==="key"&&this._app&&(this._app._ceVNode.key=r)),s&&this._instance&&this._update(),n)){const i=this._ob;i&&(this._processMutations(i.takeRecords()),i.disconnect()),r===!0?this.setAttribute(Yt(e),""):typeof r=="string"||typeof r=="number"?this.setAttribute(Yt(e),r+""):r||this.removeAttribute(Yt(e)),i&&i.observe(this,{attributes:!0})}}_update(){const e=this._createVNode();this._app&&(e.appContext=this._app._context),M_(e,this._root)}_createVNode(){const e={};this.shadowRoot||(e.onVnodeMounted=e.onVnodeUpdated=this._renderSlots.bind(this));const r=Je(this._def,we(e,this._props));return this._instance||(r.ce=n=>{this._instance=n,n.ce=this,n.isCE=!0;const s=(i,o)=>{this.dispatchEvent(new CustomEvent(i,Ac(o[0])?we({detail:o},o[0]):{detail:o}))};n.emit=(i,...o)=>{s(i,o),Yt(i)!==i&&s(Yt(i),o)},this._setParent()}),r}_applyStyles(e,r){if(!e)return;if(r){if(r===this._def||this._styleChildren.has(r))return;this._styleChildren.add(r)}const n=this._nonce;for(let s=e.length-1;s>=0;s--){const i=document.createElement("style");n&&i.setAttribute("nonce",n),i.textContent=e[s],this.shadowRoot.prepend(i)}}_parseSlots(){const e=this._slots={};let r;for(;r=this.firstChild;){const n=r.nodeType===1&&r.getAttribute("slot")||"default";(e[n]||(e[n]=[])).push(r),this.removeChild(r)}}_renderSlots(){const e=this._getSlots(),r=this._instance.type.__scopeId;for(let n=0;n<e.length;n++){const s=e[n],i=s.getAttribute("name")||"default",o=this._slots[i],a=s.parentNode;if(o)for(const l of o){if(r&&l.nodeType===1){const u=r+"-s",c=document.createTreeWalker(l,1);l.setAttribute(u,"");let d;for(;d=c.nextNode();)d.setAttribute(u,"")}a.insertBefore(l,s)}else for(;s.firstChild;)a.insertBefore(s.firstChild,s);a.removeChild(s)}}_getSlots(){const e=[this];this._teleportTargets&&e.push(...this._teleportTargets);const r=new Set;for(const n of e){const s=n.querySelectorAll("slot");for(let i=0;i<s.length;i++)r.add(s[i])}return Array.from(r)}_injectChildStyle(e){this._applyStyles(e.styles,e)}_beginPatch(){this._patching=!0,this._dirty=!1}_endPatch(){this._patching=!1,this._dirty&&this._instance&&this._update()}_removeChildStyle(e){}}function $_(t){const e=or(),r=e&&e.ce;return r||null}function L$(){const t=$_();return t&&t.shadowRoot}function F$(t="$style"){{const e=or();if(!e)return Se;const r=e.type.__cssModules;if(!r)return Se;const n=r[t];return n||Se}}const T_=new WeakMap,k_=new WeakMap,Xl=Symbol("_moveCb"),dg=Symbol("_enterCb"),M$=t=>(delete t.props.mode,t),D$=M$({name:"TransitionGroup",props:we({},w_,{tag:String,moveClass:String}),setup(t,{slots:e}){const r=or(),n=Df();let s,i;return Uc(()=>{if(!s.length)return;const o=t.moveClass||`${t.name||"v"}-move`;if(!q$(s[0].el,r.vnode.el,o)){s=[];return}s.forEach(B$),s.forEach(U$);const a=s.filter(j$);Oh(r.vnode.el),a.forEach(l=>{const u=l.el,c=u.style;Kr(u,o),c.transform=c.webkitTransform=c.transitionDuration="";const d=u[Xl]=p=>{p&&p.target!==u||(!p||p.propertyName.endsWith("transform"))&&(u.removeEventListener("transitionend",d),u[Xl]=null,jn(u,o))};u.addEventListener("transitionend",d)}),s=[]}),()=>{const o=$e(t),a=__(o);let l=o.tag||bt;if(s=[],i)for(let u=0;u<i.length;u++){const c=i[u];c.el&&c.el instanceof Element&&(s.push(c),Sn(c,Ci(c,a,n,r)),T_.set(c,{left:c.el.offsetLeft,top:c.el.offsetTop}))}i=e.default?Vc(e.default()):[];for(let u=0;u<i.length;u++){const c=i[u];c.key!=null&&Sn(c,Ci(c,a,n,r))}return Je(l,null,i)}}}),V$=D$;function B$(t){const e=t.el;e[Xl]&&e[Xl](),e[dg]&&e[dg]()}function U$(t){k_.set(t,{left:t.el.offsetLeft,top:t.el.offsetTop})}function j$(t){const e=T_.get(t),r=k_.get(t),n=e.left-r.left,s=e.top-r.top;if(n||s){const i=t.el.style;return i.transform=i.webkitTransform=`translate(${n}px,${s}px)`,i.transitionDuration="0s",t}}function q$(t,e,r){const n=t.cloneNode(),s=t[$i];s&&s.forEach(a=>{a.split(/\s+/).forEach(l=>l&&n.classList.remove(l))}),r.split(/\s+/).forEach(a=>a&&n.classList.add(a)),n.style.display="none";const i=e.nodeType===1?e:e.parentNode;i.appendChild(n);const{hasTransform:o}=E_(n);return i.removeChild(n),o}const ns=t=>{const e=t.props["onUpdate:modelValue"]||!1;return te(e)?r=>vi(e,r):e};function H$(t){t.target.composing=!0}function hg(t){const e=t.target;e.composing&&(e.composing=!1,e.dispatchEvent(new Event("input")))}const Ar=Symbol("_assign");function fg(t,e,r){return e&&(t=t.trim()),r&&(t=Tc(t)),t}const Yl={created(t,{modifiers:{lazy:e,trim:r,number:n}},s){t[Ar]=ns(s);const i=n||s.props&&s.props.type==="number";gn(t,e?"change":"input",o=>{o.target.composing||t[Ar](fg(t.value,r,i))}),(r||i)&&gn(t,"change",()=>{t.value=fg(t.value,r,i)}),e||(gn(t,"compositionstart",H$),gn(t,"compositionend",hg),gn(t,"change",hg))},mounted(t,{value:e}){t.value=e??""},beforeUpdate(t,{value:e,oldValue:r,modifiers:{lazy:n,trim:s,number:i}},o){if(t[Ar]=ns(o),t.composing)return;const a=(i||t.type==="number")&&!/^0\d/.test(t.value)?Tc(t.value):t.value,l=e??"";a!==l&&(document.activeElement===t&&t.type!=="range"&&(n&&e===r||s&&t.value.trim()===l)||(t.value=l))}},Qf={deep:!0,created(t,e,r){t[Ar]=ns(r),gn(t,"change",()=>{const n=t._modelValue,s=Ti(t),i=t.checked,o=t[Ar];if(te(n)){const a=Pc(n,s),l=a!==-1;if(i&&!l)o(n.concat(s));else if(!i&&l){const u=[...n];u.splice(a,1),o(u)}}else if(Js(n)){const a=new Set(n);i?a.add(s):a.delete(s),o(a)}else o(O_(t,i))})},mounted:pg,beforeUpdate(t,e,r){t[Ar]=ns(r),pg(t,e,r)}};function pg(t,{value:e,oldValue:r},n){t._modelValue=e;let s;if(te(e))s=Pc(e,n.props.value)>-1;else if(Js(e))s=e.has(n.props.value);else{if(e===r)return;s=rs(e,O_(t,!0))}t.checked!==s&&(t.checked=s)}const Zf={created(t,{value:e},r){t.checked=rs(e,r.props.value),t[Ar]=ns(r),gn(t,"change",()=>{t[Ar](Ti(t))})},beforeUpdate(t,{value:e,oldValue:r},n){t[Ar]=ns(n),e!==r&&(t.checked=rs(e,n.props.value))}},P_={deep:!0,created(t,{value:e,modifiers:{number:r}},n){const s=Js(e);gn(t,"change",()=>{const i=Array.prototype.filter.call(t.options,o=>o.selected).map(o=>r?Tc(Ti(o)):Ti(o));t[Ar](t.multiple?s?new Set(i):i:i[0]),t._assigning=!0,Mc(()=>{t._assigning=!1})}),t[Ar]=ns(n)},mounted(t,{value:e}){mg(t,e)},beforeUpdate(t,e,r){t[Ar]=ns(r)},updated(t,{value:e}){t._assigning||mg(t,e)}};function mg(t,e){const r=t.multiple,n=te(e);if(!(r&&!n&&!Js(e))){for(let s=0,i=t.options.length;s<i;s++){const o=t.options[s],a=Ti(o);if(r)if(n){const l=typeof a;l==="string"||l==="number"?o.selected=e.some(u=>String(u)===String(a)):o.selected=Pc(e,a)>-1}else o.selected=e.has(a);else if(rs(Ti(o),e)){t.selectedIndex!==s&&(t.selectedIndex=s);return}}!r&&t.selectedIndex!==-1&&(t.selectedIndex=-1)}}function Ti(t){return"_value"in t?t._value:t.value}function O_(t,e){const r=e?"_trueValue":"_falseValue";return r in t?t[r]:e}const R_={created(t,e,r){Qa(t,e,r,null,"created")},mounted(t,e,r){Qa(t,e,r,null,"mounted")},beforeUpdate(t,e,r,n){Qa(t,e,r,n,"beforeUpdate")},updated(t,e,r,n){Qa(t,e,r,n,"updated")}};function N_(t,e){switch(t){case"SELECT":return P_;case"TEXTAREA":return Yl;default:switch(e){case"checkbox":return Qf;case"radio":return Zf;default:return Yl}}}function Qa(t,e,r,n,s){const o=N_(t.tagName,r.props&&r.props.type)[s];o&&o(t,e,r,n)}function z$(){Yl.getSSRProps=({value:t})=>({value:t}),Zf.getSSRProps=({value:t},e)=>{if(e.props&&rs(e.props.value,t))return{checked:!0}},Qf.getSSRProps=({value:t},e)=>{if(te(t)){if(e.props&&Pc(t,e.props.value)>-1)return{checked:!0}}else if(Js(t)){if(e.props&&t.has(e.props.value))return{checked:!0}}else if(t)return{checked:!0}},R_.getSSRProps=(t,e)=>{if(typeof e.type!="string")return;const r=N_(e.type.toUpperCase(),e.props&&e.props.type);if(r.getSSRProps)return r.getSSRProps(t,e)}}const W$=["ctrl","shift","alt","meta"],K$={stop:t=>t.stopPropagation(),prevent:t=>t.preventDefault(),self:t=>t.target!==t.currentTarget,ctrl:t=>!t.ctrlKey,shift:t=>!t.shiftKey,alt:t=>!t.altKey,meta:t=>!t.metaKey,left:t=>"button"in t&&t.button!==0,middle:t=>"button"in t&&t.button!==1,right:t=>"button"in t&&t.button!==2,exact:(t,e)=>W$.some(r=>t[`${r}Key`]&&!e.includes(r))},G$=(t,e)=>{const r=t._withMods||(t._withMods={}),n=e.join(".");return r[n]||(r[n]=((s,...i)=>{for(let o=0;o<e.length;o++){const a=K$[e[o]];if(a&&a(s,e))return}return t(s,...i)}))},J$={esc:"escape",space:" ",up:"arrow-up",left:"arrow-left",right:"arrow-right",down:"arrow-down",delete:"backspace"},X$=(t,e)=>{const r=t._withKeys||(t._withKeys={}),n=e.join(".");return r[n]||(r[n]=(s=>{if(!("key"in s))return;const i=Yt(s.key);if(e.some(o=>o===i||J$[o]===i))return t(s)}))},I_=we({patchProp:O$},f$);let Ro,gg=!1;function L_(){return Ro||(Ro=Ww(I_))}function F_(){return Ro=gg?Ro:Kw(I_),gg=!0,Ro}const M_=((...t)=>{L_().render(...t)}),Y$=((...t)=>{F_().hydrate(...t)}),Ql=((...t)=>{const e=L_().createApp(...t),{mount:r}=e;return e.mount=n=>{const s=V_(n);if(!s)return;const i=e._component;!ae(i)&&!i.render&&!i.template&&(i.template=s.innerHTML),s.nodeType===1&&(s.textContent="");const o=r(s,!1,D_(s));return s instanceof Element&&(s.removeAttribute("v-cloak"),s.setAttribute("data-v-app","")),o},e}),ep=((...t)=>{const e=F_().createApp(...t),{mount:r}=e;return e.mount=n=>{const s=V_(n);if(s)return r(s,!0,D_(s))},e});function D_(t){if(t instanceof SVGElement)return"svg";if(typeof MathMLElement=="function"&&t instanceof MathMLElement)return"mathml"}function V_(t){return de(t)?document.querySelector(t):t}let yg=!1;const Q$=()=>{yg||(yg=!0,z$(),v$())},Z$=Object.freeze(Object.defineProperty({__proto__:null,BaseTransition:Sw,BaseTransitionPropsValidators:Vf,Comment:nt,DeprecationTypes:u$,EffectScope:Pf,ErrorCodes:pC,ErrorTypeStrings:n$,Fragment:bt,KeepAlive:UC,ReactiveEffect:jo,Static:Rs,Suspense:MA,Teleport:SC,Text:es,TrackOpTypes:aC,Transition:m$,TransitionGroup:V$,TriggerOpTypes:lC,VueElement:Wc,assertNumber:fC,callWithAsyncErrorHandling:$r,callWithErrorHandling:Ki,camelize:We,capitalize:Xs,cloneVNode:Yr,compatUtils:c$,computed:Kt,createApp:Ql,createBlock:zl,createCommentVNode:KA,createElementBlock:qA,createElementVNode:Jf,createHydrationRenderer:Kw,createPropsRestProxy:cA,createRenderer:Ww,createSSRApp:ep,createSlots:GC,createStaticVNode:WA,createTextVNode:Xf,createVNode:Je,customRef:cw,defineAsyncComponent:VC,defineComponent:Gi,defineCustomElement:A_,defineEmits:ZC,defineExpose:eA,defineModel:nA,defineOptions:tA,defineProps:QC,defineSSRCustomElement:N$,defineSlots:rA,devtools:s$,effect:kx,effectScope:Ax,getCurrentInstance:or,getCurrentScope:zv,getCurrentWatcher:cC,getTransitionRawChildren:Vc,guardReactiveProps:l_,h:Ns,handleError:Ys,hasInjectionContext:bA,hydrate:Y$,hydrateOnIdle:NC,hydrateOnInteraction:MC,hydrateOnMediaQuery:FC,hydrateOnVisible:LC,initCustomFormatter:e$,initDirectivesForSSR:Q$,inject:Po,isMemoSame:g_,isProxy:Lc,isReactive:Yn,isReadonly:En,isRef:ft,isRuntimeOnly:YA,isShallow:gr,isVNode:xn,markRaw:Fl,mergeDefaults:aA,mergeModels:lA,mergeProps:c_,nextTick:Mc,normalizeClass:fa,normalizeProps:fx,normalizeStyle:ha,onActivated:Cw,onBeforeMount:Tw,onBeforeUnmount:jc,onBeforeUpdate:Uf,onDeactivated:Aw,onErrorCaptured:Rw,onMounted:ma,onRenderTracked:Ow,onRenderTriggered:Pw,onScopeDispose:$x,onServerPrefetch:kw,onUnmounted:qc,onUpdated:Uc,onWatcherCleanup:dw,openBlock:Xo,popScopeId:vC,provide:Mw,proxyRefs:Lf,pushScopeId:bC,queuePostFlushCb:zo,reactive:Wi,readonly:Ll,ref:Qn,registerRuntimeCompiler:f_,render:M_,renderList:KC,renderSlot:JC,resolveComponent:HC,resolveDirective:WC,resolveDynamicComponent:zC,resolveFilter:l$,resolveTransitionHooks:Ci,setBlockTracking:Yo,setDevtoolsHook:i$,setTransitionHooks:Sn,shallowReactive:aw,shallowReadonly:Jx,shallowRef:If,ssrContextKey:Yw,ssrUtils:a$,stop:Px,toDisplayString:qv,toHandlerKey:bi,toHandlers:XC,toRaw:$e,toRef:sC,toRefs:tC,toValue:Qx,transformVNodeArgs:HA,triggerRef:Yx,unref:Fc,useAttrs:oA,useCssModule:F$,useCssVars:w$,useHost:$_,useId:CC,useModel:kA,useSSRContext:Qw,useShadowRoot:L$,useSlots:iA,useTemplateRef:AC,useTransitionState:Df,vModelCheckbox:Qf,vModelDynamic:R_,vModelRadio:Zf,vModelSelect:P_,vModelText:Yl,vShow:x_,version:y_,warn:r$,watch:Os,watchEffect:AA,watchPostEffect:$A,watchSyncEffect:Zw,withAsyncContext:uA,withCtx:Mf,withDefaults:sA,withDirectives:_C,withKeys:X$,withMemo:t$,withModifiers:G$,withScopeId:wC},Symbol.toStringTag,{value:"Module"}));const Qo=Symbol(""),No=Symbol(""),tp=Symbol(""),Zl=Symbol(""),B_=Symbol(""),Us=Symbol(""),U_=Symbol(""),j_=Symbol(""),rp=Symbol(""),np=Symbol(""),ba=Symbol(""),sp=Symbol(""),q_=Symbol(""),ip=Symbol(""),op=Symbol(""),ap=Symbol(""),lp=Symbol(""),cp=Symbol(""),up=Symbol(""),H_=Symbol(""),z_=Symbol(""),Kc=Symbol(""),ec=Symbol(""),dp=Symbol(""),hp=Symbol(""),Zo=Symbol(""),va=Symbol(""),fp=Symbol(""),Nh=Symbol(""),eT=Symbol(""),Ih=Symbol(""),tc=Symbol(""),tT=Symbol(""),rT=Symbol(""),pp=Symbol(""),nT=Symbol(""),sT=Symbol(""),mp=Symbol(""),W_=Symbol(""),ki={[Qo]:"Fragment",[No]:"Teleport",[tp]:"Suspense",[Zl]:"KeepAlive",[B_]:"BaseTransition",[Us]:"openBlock",[U_]:"createBlock",[j_]:"createElementBlock",[rp]:"createVNode",[np]:"createElementVNode",[ba]:"createCommentVNode",[sp]:"createTextVNode",[q_]:"createStaticVNode",[ip]:"resolveComponent",[op]:"resolveDynamicComponent",[ap]:"resolveDirective",[lp]:"resolveFilter",[cp]:"withDirectives",[up]:"renderList",[H_]:"renderSlot",[z_]:"createSlots",[Kc]:"toDisplayString",[ec]:"mergeProps",[dp]:"normalizeClass",[hp]:"normalizeStyle",[Zo]:"normalizeProps",[va]:"guardReactiveProps",[fp]:"toHandlers",[Nh]:"camelize",[eT]:"capitalize",[Ih]:"toHandlerKey",[tc]:"setBlockTracking",[tT]:"pushScopeId",[rT]:"popScopeId",[pp]:"withCtx",[nT]:"unref",[sT]:"isRef",[mp]:"withMemo",[W_]:"isMemoSame"};function iT(t){Object.getOwnPropertySymbols(t).forEach(e=>{ki[e]=t[e]})}const vr={start:{line:1,column:1,offset:0},end:{line:1,column:1,offset:0},source:""};function oT(t,e=""){return{type:0,source:e,children:t,helpers:new Set,components:[],directives:[],hoists:[],imports:[],cached:[],temps:0,codegenNode:void 0,loc:vr}}function ea(t,e,r,n,s,i,o,a=!1,l=!1,u=!1,c=vr){return t&&(a?(t.helper(Us),t.helper(Ri(t.inSSR,u))):t.helper(Oi(t.inSSR,u)),o&&t.helper(cp)),{type:13,tag:e,props:r,children:n,patchFlag:s,dynamicProps:i,directives:o,isBlock:a,disableTracking:l,isComponent:u,loc:c}}function Is(t,e=vr){return{type:17,loc:e,elements:t}}function xr(t,e=vr){return{type:15,loc:e,properties:t}}function ot(t,e){return{type:16,loc:vr,key:de(t)?pe(t,!0):t,value:e}}function pe(t,e=!1,r=vr,n=0){return{type:4,loc:r,content:t,isStatic:e,constType:e?3:n}}function Mr(t,e=vr){return{type:8,loc:e,children:t}}function ht(t,e=[],r=vr){return{type:14,loc:r,callee:t,arguments:e}}function Pi(t,e=void 0,r=!1,n=!1,s=vr){return{type:18,params:t,returns:e,newline:r,isSlot:n,loc:s}}function Lh(t,e,r,n=!0){return{type:19,test:t,consequent:e,alternate:r,newline:n,loc:vr}}function aT(t,e,r=!1,n=!1){return{type:20,index:t,value:e,needPauseTracking:r,inVOnce:n,needArraySpread:!1,loc:vr}}function lT(t){return{type:21,body:t,loc:vr}}function Oi(t,e){return t||e?rp:np}function Ri(t,e){return t||e?U_:j_}function gp(t,{helper:e,removeHelper:r,inSSR:n}){t.isBlock||(t.isBlock=!0,r(Oi(n,t.isComponent)),e(Us),e(Ri(n,t.isComponent)))}const bg=new Uint8Array([123,123]),vg=new Uint8Array([125,125]);function wg(t){return t>=97&&t<=122||t>=65&&t<=90}function fr(t){return t===32||t===10||t===9||t===12||t===13}function Ln(t){return t===47||t===62||fr(t)}function rc(t){const e=new Uint8Array(t.length);for(let r=0;r<t.length;r++)e[r]=t.charCodeAt(r);return e}const Pt={Cdata:new Uint8Array([67,68,65,84,65,91]),CdataEnd:new Uint8Array([93,93,62]),CommentEnd:new Uint8Array([45,45,62]),ScriptEnd:new Uint8Array([60,47,115,99,114,105,112,116]),StyleEnd:new Uint8Array([60,47,115,116,121,108,101]),TitleEnd:new Uint8Array([60,47,116,105,116,108,101]),TextareaEnd:new Uint8Array([60,47,116,101,120,116,97,114,101,97])};class cT{constructor(e,r){this.stack=e,this.cbs=r,this.state=1,this.buffer="",this.sectionStart=0,this.index=0,this.entityStart=0,this.baseState=1,this.inRCDATA=!1,this.inXML=!1,this.inVPre=!1,this.newlines=[],this.mode=0,this.delimiterOpen=bg,this.delimiterClose=vg,this.delimiterIndex=-1,this.currentSequence=void 0,this.sequenceIndex=0}get inSFCRoot(){return this.mode===2&&this.stack.length===0}reset(){this.state=1,this.mode=0,this.buffer="",this.sectionStart=0,this.index=0,this.baseState=1,this.inRCDATA=!1,this.currentSequence=void 0,this.newlines.length=0,this.delimiterOpen=bg,this.delimiterClose=vg}getPos(e){let r=1,n=e+1;for(let s=this.newlines.length-1;s>=0;s--){const i=this.newlines[s];if(e>i){r=s+2,n=e-i;break}}return{column:n,line:r,offset:e}}peek(){return this.buffer.charCodeAt(this.index+1)}stateText(e){e===60?(this.index>this.sectionStart&&this.cbs.ontext(this.sectionStart,this.index),this.state=5,this.sectionStart=this.index):!this.inVPre&&e===this.delimiterOpen[0]&&(this.state=2,this.delimiterIndex=0,this.stateInterpolationOpen(e))}stateInterpolationOpen(e){if(e===this.delimiterOpen[this.delimiterIndex])if(this.delimiterIndex===this.delimiterOpen.length-1){const r=this.index+1-this.delimiterOpen.length;r>this.sectionStart&&this.cbs.ontext(this.sectionStart,r),this.state=3,this.sectionStart=r}else this.delimiterIndex++;else this.inRCDATA?(this.state=32,this.stateInRCDATA(e)):(this.state=1,this.stateText(e))}stateInterpolation(e){e===this.delimiterClose[0]&&(this.state=4,this.delimiterIndex=0,this.stateInterpolationClose(e))}stateInterpolationClose(e){e===this.delimiterClose[this.delimiterIndex]?this.delimiterIndex===this.delimiterClose.length-1?(this.cbs.oninterpolation(this.sectionStart,this.index+1),this.inRCDATA?this.state=32:this.state=1,this.sectionStart=this.index+1):this.delimiterIndex++:(this.state=3,this.stateInterpolation(e))}stateSpecialStartSequence(e){const r=this.sequenceIndex===this.currentSequence.length;if(!(r?Ln(e):(e|32)===this.currentSequence[this.sequenceIndex]))this.inRCDATA=!1;else if(!r){this.sequenceIndex++;return}this.sequenceIndex=0,this.state=6,this.stateInTagName(e)}stateInRCDATA(e){if(this.sequenceIndex===this.currentSequence.length){if(e===62||fr(e)){const r=this.index-this.currentSequence.length;if(this.sectionStart<r){const n=this.index;this.index=r,this.cbs.ontext(this.sectionStart,r),this.index=n}this.sectionStart=r+2,this.stateInClosingTagName(e),this.inRCDATA=!1;return}this.sequenceIndex=0}(e|32)===this.currentSequence[this.sequenceIndex]?this.sequenceIndex+=1:this.sequenceIndex===0?this.currentSequence===Pt.TitleEnd||this.currentSequence===Pt.TextareaEnd&&!this.inSFCRoot?!this.inVPre&&e===this.delimiterOpen[0]&&(this.state=2,this.delimiterIndex=0,this.stateInterpolationOpen(e)):this.fastForwardTo(60)&&(this.sequenceIndex=1):this.sequenceIndex=+(e===60)}stateCDATASequence(e){e===Pt.Cdata[this.sequenceIndex]?++this.sequenceIndex===Pt.Cdata.length&&(this.state=28,this.currentSequence=Pt.CdataEnd,this.sequenceIndex=0,this.sectionStart=this.index+1):(this.sequenceIndex=0,this.state=23,this.stateInDeclaration(e))}fastForwardTo(e){for(;++this.index<this.buffer.length;){const r=this.buffer.charCodeAt(this.index);if(r===10&&this.newlines.push(this.index),r===e)return!0}return this.index=this.buffer.length-1,!1}stateInCommentLike(e){e===this.currentSequence[this.sequenceIndex]?++this.sequenceIndex===this.currentSequence.length&&(this.currentSequence===Pt.CdataEnd?this.cbs.oncdata(this.sectionStart,this.index-2):this.cbs.oncomment(this.sectionStart,this.index-2),this.sequenceIndex=0,this.sectionStart=this.index+1,this.state=1):this.sequenceIndex===0?this.fastForwardTo(this.currentSequence[0])&&(this.sequenceIndex=1):e!==this.currentSequence[this.sequenceIndex-1]&&(this.sequenceIndex=0)}startSpecial(e,r){this.enterRCDATA(e,r),this.state=31}enterRCDATA(e,r){this.inRCDATA=!0,this.currentSequence=e,this.sequenceIndex=r}stateBeforeTagName(e){e===33?(this.state=22,this.sectionStart=this.index+1):e===63?(this.state=24,this.sectionStart=this.index+1):wg(e)?(this.sectionStart=this.index,this.mode===0?this.state=6:this.inSFCRoot?this.state=34:this.inXML?this.state=6:e===116?this.state=30:this.state=e===115?29:6):e===47?this.state=8:(this.state=1,this.stateText(e))}stateInTagName(e){Ln(e)&&this.handleTagName(e)}stateInSFCRootTagName(e){if(Ln(e)){const r=this.buffer.slice(this.sectionStart,this.index);r!=="template"&&this.enterRCDATA(rc("</"+r),0),this.handleTagName(e)}}handleTagName(e){this.cbs.onopentagname(this.sectionStart,this.index),this.sectionStart=-1,this.state=11,this.stateBeforeAttrName(e)}stateBeforeClosingTagName(e){fr(e)||(e===62?(this.state=1,this.sectionStart=this.index+1):(this.state=wg(e)?9:27,this.sectionStart=this.index))}stateInClosingTagName(e){(e===62||fr(e))&&(this.cbs.onclosetag(this.sectionStart,this.index),this.sectionStart=-1,this.state=10,this.stateAfterClosingTagName(e))}stateAfterClosingTagName(e){e===62&&(this.state=1,this.sectionStart=this.index+1)}stateBeforeAttrName(e){e===62?(this.cbs.onopentagend(this.index),this.inRCDATA?this.state=32:this.state=1,this.sectionStart=this.index+1):e===47?this.state=7:e===60&&this.peek()===47?(this.cbs.onopentagend(this.index),this.state=5,this.sectionStart=this.index):fr(e)||this.handleAttrStart(e)}handleAttrStart(e){e===118&&this.peek()===45?(this.state=13,this.sectionStart=this.index):e===46||e===58||e===64||e===35?(this.cbs.ondirname(this.index,this.index+1),this.state=14,this.sectionStart=this.index+1):(this.state=12,this.sectionStart=this.index)}stateInSelfClosingTag(e){e===62?(this.cbs.onselfclosingtag(this.index),this.state=1,this.sectionStart=this.index+1,this.inRCDATA=!1):fr(e)||(this.state=11,this.stateBeforeAttrName(e))}stateInAttrName(e){(e===61||Ln(e))&&(this.cbs.onattribname(this.sectionStart,this.index),this.handleAttrNameEnd(e))}stateInDirName(e){e===61||Ln(e)?(this.cbs.ondirname(this.sectionStart,this.index),this.handleAttrNameEnd(e)):e===58?(this.cbs.ondirname(this.sectionStart,this.index),this.state=14,this.sectionStart=this.index+1):e===46&&(this.cbs.ondirname(this.sectionStart,this.index),this.state=16,this.sectionStart=this.index+1)}stateInDirArg(e){e===61||Ln(e)?(this.cbs.ondirarg(this.sectionStart,this.index),this.handleAttrNameEnd(e)):e===91?this.state=15:e===46&&(this.cbs.ondirarg(this.sectionStart,this.index),this.state=16,this.sectionStart=this.index+1)}stateInDynamicDirArg(e){e===93?this.state=14:(e===61||Ln(e))&&(this.cbs.ondirarg(this.sectionStart,this.index+1),this.handleAttrNameEnd(e))}stateInDirModifier(e){e===61||Ln(e)?(this.cbs.ondirmodifier(this.sectionStart,this.index),this.handleAttrNameEnd(e)):e===46&&(this.cbs.ondirmodifier(this.sectionStart,this.index),this.sectionStart=this.index+1)}handleAttrNameEnd(e){this.sectionStart=this.index,this.state=17,this.cbs.onattribnameend(this.index),this.stateAfterAttrName(e)}stateAfterAttrName(e){e===61?this.state=18:e===47||e===62?(this.cbs.onattribend(0,this.sectionStart),this.sectionStart=-1,this.state=11,this.stateBeforeAttrName(e)):fr(e)||(this.cbs.onattribend(0,this.sectionStart),this.handleAttrStart(e))}stateBeforeAttrValue(e){e===34?(this.state=19,this.sectionStart=this.index+1):e===39?(this.state=20,this.sectionStart=this.index+1):fr(e)||(this.sectionStart=this.index,this.state=21,this.stateInAttrValueNoQuotes(e))}handleInAttrValue(e,r){(e===r||this.fastForwardTo(r))&&(this.cbs.onattribdata(this.sectionStart,this.index),this.sectionStart=-1,this.cbs.onattribend(r===34?3:2,this.index+1),this.state=11)}stateInAttrValueDoubleQuotes(e){this.handleInAttrValue(e,34)}stateInAttrValueSingleQuotes(e){this.handleInAttrValue(e,39)}stateInAttrValueNoQuotes(e){fr(e)||e===62?(this.cbs.onattribdata(this.sectionStart,this.index),this.sectionStart=-1,this.cbs.onattribend(1,this.index),this.state=11,this.stateBeforeAttrName(e)):(e===39||e===60||e===61||e===96)&&this.cbs.onerr(18,this.index)}stateBeforeDeclaration(e){e===91?(this.state=26,this.sequenceIndex=0):this.state=e===45?25:23}stateInDeclaration(e){(e===62||this.fastForwardTo(62))&&(this.state=1,this.sectionStart=this.index+1)}stateInProcessingInstruction(e){(e===62||this.fastForwardTo(62))&&(this.cbs.onprocessinginstruction(this.sectionStart,this.index),this.state=1,this.sectionStart=this.index+1)}stateBeforeComment(e){e===45?(this.state=28,this.currentSequence=Pt.CommentEnd,this.sequenceIndex=2,this.sectionStart=this.index+1):this.state=23}stateInSpecialComment(e){(e===62||this.fastForwardTo(62))&&(this.cbs.oncomment(this.sectionStart,this.index),this.state=1,this.sectionStart=this.index+1)}stateBeforeSpecialS(e){e===Pt.ScriptEnd[3]?this.startSpecial(Pt.ScriptEnd,4):e===Pt.StyleEnd[3]?this.startSpecial(Pt.StyleEnd,4):(this.state=6,this.stateInTagName(e))}stateBeforeSpecialT(e){e===Pt.TitleEnd[3]?this.startSpecial(Pt.TitleEnd,4):e===Pt.TextareaEnd[3]?this.startSpecial(Pt.TextareaEnd,4):(this.state=6,this.stateInTagName(e))}startEntity(){}stateInEntity(){}parse(e){for(this.buffer=e;this.index<this.buffer.length;){const r=this.buffer.charCodeAt(this.index);switch(r===10&&this.state!==33&&this.newlines.push(this.index),this.state){case 1:{this.stateText(r);break}case 2:{this.stateInterpolationOpen(r);break}case 3:{this.stateInterpolation(r);break}case 4:{this.stateInterpolationClose(r);break}case 31:{this.stateSpecialStartSequence(r);break}case 32:{this.stateInRCDATA(r);break}case 26:{this.stateCDATASequence(r);break}case 19:{this.stateInAttrValueDoubleQuotes(r);break}case 12:{this.stateInAttrName(r);break}case 13:{this.stateInDirName(r);break}case 14:{this.stateInDirArg(r);break}case 15:{this.stateInDynamicDirArg(r);break}case 16:{this.stateInDirModifier(r);break}case 28:{this.stateInCommentLike(r);break}case 27:{this.stateInSpecialComment(r);break}case 11:{this.stateBeforeAttrName(r);break}case 6:{this.stateInTagName(r);break}case 34:{this.stateInSFCRootTagName(r);break}case 9:{this.stateInClosingTagName(r);break}case 5:{this.stateBeforeTagName(r);break}case 17:{this.stateAfterAttrName(r);break}case 20:{this.stateInAttrValueSingleQuotes(r);break}case 18:{this.stateBeforeAttrValue(r);break}case 8:{this.stateBeforeClosingTagName(r);break}case 10:{this.stateAfterClosingTagName(r);break}case 29:{this.stateBeforeSpecialS(r);break}case 30:{this.stateBeforeSpecialT(r);break}case 21:{this.stateInAttrValueNoQuotes(r);break}case 7:{this.stateInSelfClosingTag(r);break}case 23:{this.stateInDeclaration(r);break}case 22:{this.stateBeforeDeclaration(r);break}case 25:{this.stateBeforeComment(r);break}case 24:{this.stateInProcessingInstruction(r);break}case 33:{this.stateInEntity();break}}this.index++}this.cleanup(),this.finish()}cleanup(){this.sectionStart!==this.index&&(this.state===1||this.state===32&&this.sequenceIndex===0?(this.cbs.ontext(this.sectionStart,this.index),this.sectionStart=this.index):(this.state===19||this.state===20||this.state===21)&&(this.cbs.onattribdata(this.sectionStart,this.index),this.sectionStart=this.index))}finish(){this.handleTrailingData(),this.cbs.onend()}handleTrailingData(){const e=this.buffer.length;this.sectionStart>=e||(this.state===28?this.currentSequence===Pt.CdataEnd?this.cbs.oncdata(this.sectionStart,e):this.cbs.oncomment(this.sectionStart,e):this.state===6||this.state===11||this.state===18||this.state===17||this.state===12||this.state===13||this.state===14||this.state===15||this.state===16||this.state===20||this.state===19||this.state===21||this.state===9||this.cbs.ontext(this.sectionStart,e))}emitCodePoint(e,r){}}function _g(t,{compatConfig:e}){const r=e&&e[t];return t==="MODE"?r||3:r}function Ls(t,e){const r=_g("MODE",e),n=_g(t,e);return r===3?n===!0:n!==!1}function ta(t,e,r,...n){return Ls(t,e)}function yp(t){throw t}function K_(t){}function Ge(t,e,r,n){const s=`https://vuejs.org/error-reference/#compiler-${t}`,i=new SyntaxError(String(s));return i.code=t,i.loc=e,i}const Qt=t=>t.type===4&&t.isStatic;function G_(t){switch(t){case"Teleport":case"teleport":return No;case"Suspense":case"suspense":return tp;case"KeepAlive":case"keep-alive":return Zl;case"BaseTransition":case"base-transition":return B_}}const uT=/^$|^\d|[^\$\w\xA0-\uFFFF]/,bp=t=>!uT.test(t),J_=/[A-Za-z_$\xA0-\uFFFF]/,dT=/[\.\?\w$\xA0-\uFFFF]/,hT=/\s+[.[]\s*|\s*[.[]\s+/g,X_=t=>t.type===4?t.content:t.loc.source,fT=t=>{const e=X_(t).trim().replace(hT,a=>a.trim());let r=0,n=[],s=0,i=0,o=null;for(let a=0;a<e.length;a++){const l=e.charAt(a);switch(r){case 0:if(l==="[")n.push(r),r=1,s++;else if(l==="(")n.push(r),r=2,i++;else if(!(a===0?J_:dT).test(l))return!1;break;case 1:l==="'"||l==='"'||l==="`"?(n.push(r),r=3,o=l):l==="["?s++:l==="]"&&(--s||(r=n.pop()));break;case 2:if(l==="'"||l==='"'||l==="`")n.push(r),r=3,o=l;else if(l==="(")i++;else if(l===")"){if(a===e.length-1)return!1;--i||(r=n.pop())}break;case 3:l===o&&(r=n.pop(),o=null);break}}return!s&&!i},Y_=fT,pT=/^\s*(?:async\s*)?(?:\([^)]*?\)|[\w$_]+)\s*(?::[^=]+)?=>|^\s*(?:async\s+)?function(?:\s+[\w$]+)?\s*\(/,mT=t=>pT.test(X_(t)),gT=mT;function Sr(t,e,r=!1){for(let n=0;n<t.props.length;n++){const s=t.props[n];if(s.type===7&&(r||s.exp)&&(de(e)?s.name===e:e.test(s.name)))return s}}function Gc(t,e,r=!1,n=!1){for(let s=0;s<t.props.length;s++){const i=t.props[s];if(i.type===6){if(r)continue;if(i.name===e&&(i.value||n))return i}else if(i.name==="bind"&&(i.exp||n)&&ws(i.arg,e))return i}}function ws(t,e){return!!(t&&Qt(t)&&t.content===e)}function yT(t){return t.props.some(e=>e.type===7&&e.name==="bind"&&(!e.arg||e.arg.type!==4||!e.arg.isStatic))}function Wu(t){return t.type===5||t.type===2}function Eg(t){return t.type===7&&t.name==="pre"}function bT(t){return t.type===7&&t.name==="slot"}function nc(t){return t.type===1&&t.tagType===3}function sc(t){return t.type===1&&t.tagType===2}const vT=new Set([Zo,va]);function Q_(t,e=[]){if(t&&!de(t)&&t.type===14){const r=t.callee;if(!de(r)&&vT.has(r))return Q_(t.arguments[0],e.concat(t))}return[t,e]}function ic(t,e,r){let n,s=t.type===13?t.props:t.arguments[2],i=[],o;if(s&&!de(s)&&s.type===14){const a=Q_(s);s=a[0],i=a[1],o=i[i.length-1]}if(s==null||de(s))n=xr([e]);else if(s.type===14){const a=s.arguments[0];!de(a)&&a.type===15?Sg(e,a)||a.properties.unshift(e):s.callee===fp?n=ht(r.helper(ec),[xr([e]),s]):s.arguments.unshift(xr([e])),!n&&(n=s)}else s.type===15?(Sg(e,s)||s.properties.unshift(e),n=s):(n=ht(r.helper(ec),[xr([e]),s]),o&&o.callee===va&&(o=i[i.length-2]));t.type===13?o?o.arguments[0]=n:t.props=n:o?o.arguments[0]=n:t.arguments[2]=n}function Sg(t,e){let r=!1;if(t.key.type===4){const n=t.key.content;r=e.properties.some(s=>s.key.type===4&&s.key.content===n)}return r}function ra(t,e){return`_${e}_${t.replace(/[^\w]/g,(r,n)=>r==="-"?"_":t.charCodeAt(n).toString())}`}function wT(t){return t.type===14&&t.callee===mp?t.arguments[1].returns:t}const _T=/([\s\S]*?)\s+(?:in|of)\s+(\S[\s\S]*)/,Z_={parseMode:"base",ns:0,delimiters:["{{","}}"],getNamespace:()=>0,isVoidTag:fi,isPreTag:fi,isIgnoreNewlineTag:fi,isCustomElement:fi,onError:yp,onWarn:K_,comments:!1,prefixIdentifiers:!1};let Oe=Z_,na=null,bn="",Rt=null,Ee=null,Wt="",un=-1,ys=-1,vp=0,Wn=!1,Fh=null;const Ke=[],et=new cT(Ke,{onerr:ln,ontext(t,e){Za(Et(t,e),t,e)},ontextentity(t,e,r){Za(t,e,r)},oninterpolation(t,e){if(Wn)return Za(Et(t,e),t,e);let r=t+et.delimiterOpen.length,n=e-et.delimiterClose.length;for(;fr(bn.charCodeAt(r));)r++;for(;fr(bn.charCodeAt(n-1));)n--;let s=Et(r,n);s.includes("&")&&(s=Oe.decodeEntities(s,!1)),Mh({type:5,content:gl(s,!1,rt(r,n)),loc:rt(t,e)})},onopentagname(t,e){const r=Et(t,e);Rt={type:1,tag:r,ns:Oe.getNamespace(r,Ke[0],Oe.ns),tagType:0,props:[],children:[],loc:rt(t-1,e),codegenNode:void 0}},onopentagend(t){Cg(t)},onclosetag(t,e){const r=Et(t,e);if(!Oe.isVoidTag(r)){let n=!1;for(let s=0;s<Ke.length;s++)if(Ke[s].tag.toLowerCase()===r.toLowerCase()){n=!0,s>0&&ln(24,Ke[0].loc.start.offset);for(let o=0;o<=s;o++){const a=Ke.shift();ml(a,e,o<s)}break}n||ln(23,e0(t,60))}},onselfclosingtag(t){const e=Rt.tag;Rt.isSelfClosing=!0,Cg(t),Ke[0]&&Ke[0].tag===e&&ml(Ke.shift(),t)},onattribname(t,e){Ee={type:6,name:Et(t,e),nameLoc:rt(t,e),value:void 0,loc:rt(t)}},ondirname(t,e){const r=Et(t,e),n=r==="."||r===":"?"bind":r==="@"?"on":r==="#"?"slot":r.slice(2);if(!Wn&&n===""&&ln(26,t),Wn||n==="")Ee={type:6,name:r,nameLoc:rt(t,e),value:void 0,loc:rt(t)};else if(Ee={type:7,name:n,rawName:r,exp:void 0,arg:void 0,modifiers:r==="."?[pe("prop")]:[],loc:rt(t)},n==="pre"){Wn=et.inVPre=!0,Fh=Rt;const s=Rt.props;for(let i=0;i<s.length;i++)s[i].type===7&&(s[i]=RT(s[i]))}},ondirarg(t,e){if(t===e)return;const r=Et(t,e);if(Wn&&!Eg(Ee))Ee.name+=r,_s(Ee.nameLoc,e);else{const n=r[0]!=="[";Ee.arg=gl(n?r:r.slice(1,-1),n,rt(t,e),n?3:0)}},ondirmodifier(t,e){const r=Et(t,e);if(Wn&&!Eg(Ee))Ee.name+="."+r,_s(Ee.nameLoc,e);else if(Ee.name==="slot"){const n=Ee.arg;n&&(n.content+="."+r,_s(n.loc,e))}else{const n=pe(r,!0,rt(t,e));Ee.modifiers.push(n)}},onattribdata(t,e){Wt+=Et(t,e),un<0&&(un=t),ys=e},onattribentity(t,e,r){Wt+=t,un<0&&(un=e),ys=r},onattribnameend(t){const e=Ee.loc.start.offset,r=Et(e,t);Ee.type===7&&(Ee.rawName=r),Rt.props.some(n=>(n.type===7?n.rawName:n.name)===r)&&ln(2,e)},onattribend(t,e){if(Rt&&Ee){if(_s(Ee.loc,e),t!==0)if(Wt.includes("&")&&(Wt=Oe.decodeEntities(Wt,!0)),Ee.type===6)Ee.name==="class"&&(Wt=r0(Wt).trim()),t===1&&!Wt&&ln(13,e),Ee.value={type:2,content:Wt,loc:t===1?rt(un,ys):rt(un-1,ys+1)},et.inSFCRoot&&Rt.tag==="template"&&Ee.name==="lang"&&Wt&&Wt!=="html"&&et.enterRCDATA(rc("</template"),0);else{let r=0;Ee.exp=gl(Wt,!1,rt(un,ys),0,r),Ee.name==="for"&&(Ee.forParseResult=ST(Ee.exp));let n=-1;Ee.name==="bind"&&(n=Ee.modifiers.findIndex(s=>s.content==="sync"))>-1&&ta("COMPILER_V_BIND_SYNC",Oe,Ee.loc,Ee.arg.loc.source)&&(Ee.name="model",Ee.modifiers.splice(n,1))}(Ee.type!==7||Ee.name!=="pre")&&Rt.props.push(Ee)}Wt="",un=ys=-1},oncomment(t,e){Oe.comments&&Mh({type:3,content:Et(t,e),loc:rt(t-4,e+3)})},onend(){const t=bn.length;for(let e=0;e<Ke.length;e++)ml(Ke[e],t-1),ln(24,Ke[e].loc.start.offset)},oncdata(t,e){Ke[0].ns!==0?Za(Et(t,e),t,e):ln(1,t-9)},onprocessinginstruction(t){(Ke[0]?Ke[0].ns:Oe.ns)===0&&ln(21,t-1)}}),xg=/,([^,\}\]]*)(?:,([^,\}\]]*))?$/,ET=/^\(|\)$/g;function ST(t){const e=t.loc,r=t.content,n=r.match(_T);if(!n)return;const[,s,i]=n,o=(d,p,f=!1)=>{const h=e.start.offset+p,m=h+d.length;return gl(d,!1,rt(h,m),0,f?1:0)},a={source:o(i.trim(),r.indexOf(i,s.length)),value:void 0,key:void 0,index:void 0,finalized:!1};let l=s.trim().replace(ET,"").trim();const u=s.indexOf(l),c=l.match(xg);if(c){l=l.replace(xg,"").trim();const d=c[1].trim();let p;if(d&&(p=r.indexOf(d,u+l.length),a.key=o(d,p,!0)),c[2]){const f=c[2].trim();f&&(a.index=o(f,r.indexOf(f,a.key?p+d.length:u+l.length),!0))}}return l&&(a.value=o(l,u,!0)),a}function Et(t,e){return bn.slice(t,e)}function Cg(t){et.inSFCRoot&&(Rt.innerLoc=rt(t+1,t+1)),Mh(Rt);const{tag:e,ns:r}=Rt;r===0&&Oe.isPreTag(e)&&vp++,Oe.isVoidTag(e)?ml(Rt,t):(Ke.unshift(Rt),(r===1||r===2)&&(et.inXML=!0)),Rt=null}function Za(t,e,r){{const i=Ke[0]&&Ke[0].tag;i!=="script"&&i!=="style"&&t.includes("&")&&(t=Oe.decodeEntities(t,!1))}const n=Ke[0]||na,s=n.children[n.children.length-1];s&&s.type===2?(s.content+=t,_s(s.loc,r)):n.children.push({type:2,content:t,loc:rt(e,r)})}function ml(t,e,r=!1){r?_s(t.loc,e0(e,60)):_s(t.loc,xT(e,62)+1),et.inSFCRoot&&(t.children.length?t.innerLoc.end=we({},t.children[t.children.length-1].loc.end):t.innerLoc.end=we({},t.innerLoc.start),t.innerLoc.source=Et(t.innerLoc.start.offset,t.innerLoc.end.offset));const{tag:n,ns:s,children:i}=t;if(Wn||(n==="slot"?t.tagType=2:Ag(t)?t.tagType=3:AT(t)&&(t.tagType=1)),et.inRCDATA||(t.children=t0(i)),s===0&&Oe.isIgnoreNewlineTag(n)){const o=i[0];o&&o.type===2&&(o.content=o.content.replace(/^\r?\n/,""))}s===0&&Oe.isPreTag(n)&&vp--,Fh===t&&(Wn=et.inVPre=!1,Fh=null),et.inXML&&(Ke[0]?Ke[0].ns:Oe.ns)===0&&(et.inXML=!1);{const o=t.props;if(!et.inSFCRoot&&Ls("COMPILER_NATIVE_TEMPLATE",Oe)&&t.tag==="template"&&!Ag(t)){const l=Ke[0]||na,u=l.children.indexOf(t);l.children.splice(u,1,...t.children)}const a=o.find(l=>l.type===6&&l.name==="inline-template");a&&ta("COMPILER_INLINE_TEMPLATE",Oe,a.loc)&&t.children.length&&(a.value={type:2,content:Et(t.children[0].loc.start.offset,t.children[t.children.length-1].loc.end.offset),loc:a.loc})}}function xT(t,e){let r=t;for(;bn.charCodeAt(r)!==e&&r<bn.length-1;)r++;return r}function e0(t,e){let r=t;for(;bn.charCodeAt(r)!==e&&r>=0;)r--;return r}const CT=new Set(["if","else","else-if","for","slot"]);function Ag({tag:t,props:e}){if(t==="template"){for(let r=0;r<e.length;r++)if(e[r].type===7&&CT.has(e[r].name))return!0}return!1}function AT({tag:t,props:e}){if(Oe.isCustomElement(t))return!1;if(t==="component"||$T(t.charCodeAt(0))||G_(t)||Oe.isBuiltInComponent&&Oe.isBuiltInComponent(t)||Oe.isNativeTag&&!Oe.isNativeTag(t))return!0;for(let r=0;r<e.length;r++){const n=e[r];if(n.type===6){if(n.name==="is"&&n.value){if(n.value.content.startsWith("vue:"))return!0;if(ta("COMPILER_IS_ON_ELEMENT",Oe,n.loc))return!0}}else if(n.name==="bind"&&ws(n.arg,"is")&&ta("COMPILER_IS_ON_ELEMENT",Oe,n.loc))return!0}return!1}function $T(t){return t>64&&t<91}const TT=/\r\n/g;function t0(t){const e=Oe.whitespace!=="preserve";let r=!1;for(let n=0;n<t.length;n++){const s=t[n];if(s.type===2)if(vp)s.content=s.content.replace(TT,`
`);else if(kT(s.content)){const i=t[n-1]&&t[n-1].type,o=t[n+1]&&t[n+1].type;!i||!o||e&&(i===3&&(o===3||o===1)||i===1&&(o===3||o===1&&PT(s.content)))?(r=!0,t[n]=null):s.content=" "}else e&&(s.content=r0(s.content))}return r?t.filter(Boolean):t}function kT(t){for(let e=0;e<t.length;e++)if(!fr(t.charCodeAt(e)))return!1;return!0}function PT(t){for(let e=0;e<t.length;e++){const r=t.charCodeAt(e);if(r===10||r===13)return!0}return!1}function r0(t){let e="",r=!1;for(let n=0;n<t.length;n++)fr(t.charCodeAt(n))?r||(e+=" ",r=!0):(e+=t[n],r=!1);return e}function Mh(t){(Ke[0]||na).children.push(t)}function rt(t,e){return{start:et.getPos(t),end:e==null?e:et.getPos(e),source:e==null?e:Et(t,e)}}function OT(t){return rt(t.start.offset,t.end.offset)}function _s(t,e){t.end=et.getPos(e),t.source=Et(t.start.offset,e)}function RT(t){const e={type:6,name:t.rawName,nameLoc:rt(t.loc.start.offset,t.loc.start.offset+t.rawName.length),value:void 0,loc:t.loc};if(t.exp){const r=t.exp.loc;r.end.offset<t.loc.end.offset&&(r.start.offset--,r.start.column--,r.end.offset++,r.end.column++),e.value={type:2,content:t.exp.content,loc:r}}return e}function gl(t,e=!1,r,n=0,s=0){return pe(t,e,r,n)}function ln(t,e,r){Oe.onError(Ge(t,rt(e,e)))}function NT(){et.reset(),Rt=null,Ee=null,Wt="",un=-1,ys=-1,Ke.length=0}function IT(t,e){if(NT(),bn=t,Oe=we({},Z_),e){let s;for(s in e)e[s]!=null&&(Oe[s]=e[s])}et.mode=Oe.parseMode==="html"?1:Oe.parseMode==="sfc"?2:0,et.inXML=Oe.ns===1||Oe.ns===2;const r=e&&e.delimiters;r&&(et.delimiterOpen=rc(r[0]),et.delimiterClose=rc(r[1]));const n=na=oT([],t);return et.parse(bn),n.loc=rt(0,t.length),n.children=t0(n.children),na=null,n}function LT(t,e){yl(t,void 0,e,!!n0(t))}function n0(t){const e=t.children.filter(r=>r.type!==3);return e.length===1&&e[0].type===1&&!sc(e[0])?e[0]:null}function yl(t,e,r,n=!1,s=!1){const{children:i}=t,o=[];for(let c=0;c<i.length;c++){const d=i[c];if(d.type===1&&d.tagType===0){const p=n?0:mr(d,r);if(p>0){if(p>=2){d.codegenNode.patchFlag=-1,o.push(d);continue}}else{const f=d.codegenNode;if(f.type===13){const h=f.patchFlag;if((h===void 0||h===512||h===1)&&i0(d,r)>=2){const m=o0(d);m&&(f.props=r.hoist(m))}f.dynamicProps&&(f.dynamicProps=r.hoist(f.dynamicProps))}}}else if(d.type===12&&(n?0:mr(d,r))>=2){d.codegenNode.type===14&&d.codegenNode.arguments.length>0&&d.codegenNode.arguments.push("-1"),o.push(d);continue}if(d.type===1){const p=d.tagType===1;p&&r.scopes.vSlot++,yl(d,t,r,!1,s),p&&r.scopes.vSlot--}else if(d.type===11)yl(d,t,r,d.children.length===1,!0);else if(d.type===9)for(let p=0;p<d.branches.length;p++)yl(d.branches[p],t,r,d.branches[p].children.length===1,s)}let a=!1;if(o.length===i.length&&t.type===1){if(t.tagType===0&&t.codegenNode&&t.codegenNode.type===13&&te(t.codegenNode.children))t.codegenNode.children=l(Is(t.codegenNode.children)),a=!0;else if(t.tagType===1&&t.codegenNode&&t.codegenNode.type===13&&t.codegenNode.children&&!te(t.codegenNode.children)&&t.codegenNode.children.type===15){const c=u(t.codegenNode,"default");c&&(c.returns=l(Is(c.returns)),a=!0)}else if(t.tagType===3&&e&&e.type===1&&e.tagType===1&&e.codegenNode&&e.codegenNode.type===13&&e.codegenNode.children&&!te(e.codegenNode.children)&&e.codegenNode.children.type===15){const c=Sr(t,"slot",!0),d=c&&c.arg&&u(e.codegenNode,c.arg);d&&(d.returns=l(Is(d.returns)),a=!0)}}if(!a)for(const c of o)c.codegenNode=r.cache(c.codegenNode);function l(c){const d=r.cache(c);return d.needArraySpread=!0,d}function u(c,d){if(c.children&&!te(c.children)&&c.children.type===15){const p=c.children.properties.find(f=>f.key===d||f.key.content===d);return p&&p.value}}o.length&&r.transformHoist&&r.transformHoist(i,r,t)}function mr(t,e){const{constantCache:r}=e;switch(t.type){case 1:if(t.tagType!==0)return 0;const n=r.get(t);if(n!==void 0)return n;const s=t.codegenNode;if(s.type!==13||s.isBlock&&t.tag!=="svg"&&t.tag!=="foreignObject"&&t.tag!=="math")return 0;if(s.patchFlag===void 0){let o=3;const a=i0(t,e);if(a===0)return r.set(t,0),0;a<o&&(o=a);for(let l=0;l<t.children.length;l++){const u=mr(t.children[l],e);if(u===0)return r.set(t,0),0;u<o&&(o=u)}if(o>1)for(let l=0;l<t.props.length;l++){const u=t.props[l];if(u.type===7&&u.name==="bind"&&u.exp){const c=mr(u.exp,e);if(c===0)return r.set(t,0),0;c<o&&(o=c)}}if(s.isBlock){for(let l=0;l<t.props.length;l++)if(t.props[l].type===7)return r.set(t,0),0;e.removeHelper(Us),e.removeHelper(Ri(e.inSSR,s.isComponent)),s.isBlock=!1,e.helper(Oi(e.inSSR,s.isComponent))}return r.set(t,o),o}else return r.set(t,0),0;case 2:case 3:return 3;case 9:case 11:case 10:return 0;case 5:case 12:return mr(t.content,e);case 4:return t.constType;case 8:let i=3;for(let o=0;o<t.children.length;o++){const a=t.children[o];if(de(a)||ir(a))continue;const l=mr(a,e);if(l===0)return 0;l<i&&(i=l)}return i;case 20:return 2;default:return 0}}const FT=new Set([dp,hp,Zo,va]);function s0(t,e){if(t.type===14&&!de(t.callee)&&FT.has(t.callee)){const r=t.arguments[0];if(r.type===4)return mr(r,e);if(r.type===14)return s0(r,e)}return 0}function i0(t,e){let r=3;const n=o0(t);if(n&&n.type===15){const{properties:s}=n;for(let i=0;i<s.length;i++){const{key:o,value:a}=s[i],l=mr(o,e);if(l===0)return l;l<r&&(r=l);let u;if(a.type===4?u=mr(a,e):a.type===14?u=s0(a,e):u=0,u===0)return u;u<r&&(r=u)}}return r}function o0(t){const e=t.codegenNode;if(e.type===13)return e.props}function MT(t,{filename:e="",prefixIdentifiers:r=!1,hoistStatic:n=!1,hmr:s=!1,cacheHandlers:i=!1,nodeTransforms:o=[],directiveTransforms:a={},transformHoist:l=null,isBuiltInComponent:u=Tt,isCustomElement:c=Tt,expressionPlugins:d=[],scopeId:p=null,slotted:f=!0,ssr:h=!1,inSSR:m=!1,ssrCssVars:g="",bindingMetadata:v=Se,inline:b=!1,isTS:w=!1,onError:_=yp,onWarn:S=K_,compatConfig:k}){const O=e.replace(/\?.*$/,"").match(/([^/\\]+)\.\w+$/),R={filename:e,selfName:O&&Xs(We(O[1])),prefixIdentifiers:r,hoistStatic:n,hmr:s,cacheHandlers:i,nodeTransforms:o,directiveTransforms:a,transformHoist:l,isBuiltInComponent:u,isCustomElement:c,expressionPlugins:d,scopeId:p,slotted:f,ssr:h,inSSR:m,ssrCssVars:g,bindingMetadata:v,inline:b,isTS:w,onError:_,onWarn:S,compatConfig:k,root:t,helpers:new Map,components:new Set,directives:new Set,hoists:[],imports:[],cached:[],constantCache:new WeakMap,temps:0,identifiers:Object.create(null),scopes:{vFor:0,vSlot:0,vPre:0,vOnce:0},parent:null,grandParent:null,currentNode:t,childIndex:0,inVOnce:!1,helper(C){const A=R.helpers.get(C)||0;return R.helpers.set(C,A+1),C},removeHelper(C){const A=R.helpers.get(C);if(A){const j=A-1;j?R.helpers.set(C,j):R.helpers.delete(C)}},helperString(C){return`_${ki[R.helper(C)]}`},replaceNode(C){R.parent.children[R.childIndex]=R.currentNode=C},removeNode(C){const A=R.parent.children,j=C?A.indexOf(C):R.currentNode?R.childIndex:-1;!C||C===R.currentNode?(R.currentNode=null,R.onNodeRemoved()):R.childIndex>j&&(R.childIndex--,R.onNodeRemoved()),R.parent.children.splice(j,1)},onNodeRemoved:Tt,addIdentifiers(C){},removeIdentifiers(C){},hoist(C){de(C)&&(C=pe(C)),R.hoists.push(C);const A=pe(`_hoisted_${R.hoists.length}`,!1,C.loc,2);return A.hoisted=C,A},cache(C,A=!1,j=!1){const $=aT(R.cached.length,C,A,j);return R.cached.push($),$}};return R.filters=new Set,R}function DT(t,e){const r=MT(t,e);Jc(t,r),e.hoistStatic&&LT(t,r),e.ssr||VT(t,r),t.helpers=new Set([...r.helpers.keys()]),t.components=[...r.components],t.directives=[...r.directives],t.imports=r.imports,t.hoists=r.hoists,t.temps=r.temps,t.cached=r.cached,t.transformed=!0,t.filters=[...r.filters]}function VT(t,e){const{helper:r}=e,{children:n}=t;if(n.length===1){const s=n0(t);if(s&&s.codegenNode){const i=s.codegenNode;i.type===13&&gp(i,e),t.codegenNode=i}else t.codegenNode=n[0]}else if(n.length>1){let s=64;t.codegenNode=ea(e,r(Qo),void 0,t.children,s,void 0,void 0,!0,void 0,!1)}}function BT(t,e){let r=0;const n=()=>{r--};for(;r<t.children.length;r++){const s=t.children[r];de(s)||(e.grandParent=e.parent,e.parent=t,e.childIndex=r,e.onNodeRemoved=n,Jc(s,e))}}function Jc(t,e){e.currentNode=t;const{nodeTransforms:r}=e,n=[];for(let i=0;i<r.length;i++){const o=r[i](t,e);if(o&&(te(o)?n.push(...o):n.push(o)),e.currentNode)t=e.currentNode;else return}switch(t.type){case 3:e.ssr||e.helper(ba);break;case 5:e.ssr||e.helper(Kc);break;case 9:for(let i=0;i<t.branches.length;i++)Jc(t.branches[i],e);break;case 10:case 11:case 1:case 0:BT(t,e);break}e.currentNode=t;let s=n.length;for(;s--;)n[s]()}function a0(t,e){const r=de(t)?n=>n===t:n=>t.test(n);return(n,s)=>{if(n.type===1){const{props:i}=n;if(n.tagType===3&&i.some(bT))return;const o=[];for(let a=0;a<i.length;a++){const l=i[a];if(l.type===7&&r(l.name)){i.splice(a,1),a--;const u=e(n,l,s);u&&o.push(u)}}return o}}}const Xc="/*@__PURE__*/",l0=t=>`${ki[t]}: _${ki[t]}`;function UT(t,{mode:e="function",prefixIdentifiers:r=e==="module",sourceMap:n=!1,filename:s="template.vue.html",scopeId:i=null,optimizeImports:o=!1,runtimeGlobalName:a="Vue",runtimeModuleName:l="vue",ssrRuntimeModuleName:u="vue/server-renderer",ssr:c=!1,isTS:d=!1,inSSR:p=!1}){const f={mode:e,prefixIdentifiers:r,sourceMap:n,filename:s,scopeId:i,optimizeImports:o,runtimeGlobalName:a,runtimeModuleName:l,ssrRuntimeModuleName:u,ssr:c,isTS:d,inSSR:p,source:t.source,code:"",column:1,line:1,offset:0,indentLevel:0,pure:!1,map:void 0,helper(m){return`_${ki[m]}`},push(m,g=-2,v){f.code+=m},indent(){h(++f.indentLevel)},deindent(m=!1){m?--f.indentLevel:h(--f.indentLevel)},newline(){h(f.indentLevel)}};function h(m){f.push(`
`+"  ".repeat(m),0)}return f}function jT(t,e={}){const r=UT(t,e);e.onContextCreated&&e.onContextCreated(r);const{mode:n,push:s,prefixIdentifiers:i,indent:o,deindent:a,newline:l,scopeId:u,ssr:c}=r,d=Array.from(t.helpers),p=d.length>0,f=!i&&n!=="module";qT(t,r);const m=c?"ssrRender":"render",v=(c?["_ctx","_push","_parent","_attrs"]:["_ctx","_cache"]).join(", ");if(s(`function ${m}(${v}) {`),o(),f&&(s("with (_ctx) {"),o(),p&&(s(`const { ${d.map(l0).join(", ")} } = _Vue
`,-1),l())),t.components.length&&(Ku(t.components,"component",r),(t.directives.length||t.temps>0)&&l()),t.directives.length&&(Ku(t.directives,"directive",r),t.temps>0&&l()),t.filters&&t.filters.length&&(l(),Ku(t.filters,"filter",r),l()),t.temps>0){s("let ");for(let b=0;b<t.temps;b++)s(`${b>0?", ":""}_temp${b}`)}return(t.components.length||t.directives.length||t.temps)&&(s(`
`,0),l()),c||s("return "),t.codegenNode?Dt(t.codegenNode,r):s("null"),f&&(a(),s("}")),a(),s("}"),{ast:t,code:r.code,preamble:"",map:r.map?r.map.toJSON():void 0}}function qT(t,e){const{ssr:r,prefixIdentifiers:n,push:s,newline:i,runtimeModuleName:o,runtimeGlobalName:a,ssrRuntimeModuleName:l}=e,u=a,c=Array.from(t.helpers);if(c.length>0&&(s(`const _Vue = ${u}
`,-1),t.hoists.length)){const d=[rp,np,ba,sp,q_].filter(p=>c.includes(p)).map(l0).join(", ");s(`const { ${d} } = _Vue
`,-1)}HT(t.hoists,e),i(),s("return ")}function Ku(t,e,{helper:r,push:n,newline:s,isTS:i}){const o=r(e==="filter"?lp:e==="component"?ip:ap);for(let a=0;a<t.length;a++){let l=t[a];const u=l.endsWith("__self");u&&(l=l.slice(0,-6)),n(`const ${ra(l,e)} = ${o}(${JSON.stringify(l)}${u?", true":""})${i?"!":""}`),a<t.length-1&&s()}}function HT(t,e){if(!t.length)return;e.pure=!0;const{push:r,newline:n}=e;n();for(let s=0;s<t.length;s++){const i=t[s];i&&(r(`const _hoisted_${s+1} = `),Dt(i,e),n())}e.pure=!1}function wp(t,e){const r=t.length>3||!1;e.push("["),r&&e.indent(),wa(t,e,r),r&&e.deindent(),e.push("]")}function wa(t,e,r=!1,n=!0){const{push:s,newline:i}=e;for(let o=0;o<t.length;o++){const a=t[o];de(a)?s(a,-3):te(a)?wp(a,e):Dt(a,e),o<t.length-1&&(r?(n&&s(","),i()):n&&s(", "))}}function Dt(t,e){if(de(t)){e.push(t,-3);return}if(ir(t)){e.push(e.helper(t));return}switch(t.type){case 1:case 9:case 11:Dt(t.codegenNode,e);break;case 2:zT(t,e);break;case 4:c0(t,e);break;case 5:WT(t,e);break;case 12:Dt(t.codegenNode,e);break;case 8:u0(t,e);break;case 3:GT(t,e);break;case 13:JT(t,e);break;case 14:YT(t,e);break;case 15:QT(t,e);break;case 17:ZT(t,e);break;case 18:ek(t,e);break;case 19:tk(t,e);break;case 20:rk(t,e);break;case 21:wa(t.body,e,!0,!1);break}}function zT(t,e){e.push(JSON.stringify(t.content),-3,t)}function c0(t,e){const{content:r,isStatic:n}=t;e.push(n?JSON.stringify(r):r,-3,t)}function WT(t,e){const{push:r,helper:n,pure:s}=e;s&&r(Xc),r(`${n(Kc)}(`),Dt(t.content,e),r(")")}function u0(t,e){for(let r=0;r<t.children.length;r++){const n=t.children[r];de(n)?e.push(n,-3):Dt(n,e)}}function KT(t,e){const{push:r}=e;if(t.type===8)r("["),u0(t,e),r("]");else if(t.isStatic){const n=bp(t.content)?t.content:JSON.stringify(t.content);r(n,-2,t)}else r(`[${t.content}]`,-3,t)}function GT(t,e){const{push:r,helper:n,pure:s}=e;s&&r(Xc),r(`${n(ba)}(${JSON.stringify(t.content)})`,-3,t)}function JT(t,e){const{push:r,helper:n,pure:s}=e,{tag:i,props:o,children:a,patchFlag:l,dynamicProps:u,directives:c,isBlock:d,disableTracking:p,isComponent:f}=t;let h;l&&(h=String(l)),c&&r(n(cp)+"("),d&&r(`(${n(Us)}(${p?"true":""}), `),s&&r(Xc);const m=d?Ri(e.inSSR,f):Oi(e.inSSR,f);r(n(m)+"(",-2,t),wa(XT([i,o,a,h,u]),e),r(")"),d&&r(")"),c&&(r(", "),Dt(c,e),r(")"))}function XT(t){let e=t.length;for(;e--&&t[e]==null;);return t.slice(0,e+1).map(r=>r||"null")}function YT(t,e){const{push:r,helper:n,pure:s}=e,i=de(t.callee)?t.callee:n(t.callee);s&&r(Xc),r(i+"(",-2,t),wa(t.arguments,e),r(")")}function QT(t,e){const{push:r,indent:n,deindent:s,newline:i}=e,{properties:o}=t;if(!o.length){r("{}",-2,t);return}const a=o.length>1||!1;r(a?"{":"{ "),a&&n();for(let l=0;l<o.length;l++){const{key:u,value:c}=o[l];KT(u,e),r(": "),Dt(c,e),l<o.length-1&&(r(","),i())}a&&s(),r(a?"}":" }")}function ZT(t,e){wp(t.elements,e)}function ek(t,e){const{push:r,indent:n,deindent:s}=e,{params:i,returns:o,body:a,newline:l,isSlot:u}=t;u&&r(`_${ki[pp]}(`),r("(",-2,t),te(i)?wa(i,e):i&&Dt(i,e),r(") => "),(l||a)&&(r("{"),n()),o?(l&&r("return "),te(o)?wp(o,e):Dt(o,e)):a&&Dt(a,e),(l||a)&&(s(),r("}")),u&&(t.isNonScopedSlot&&r(", undefined, true"),r(")"))}function tk(t,e){const{test:r,consequent:n,alternate:s,newline:i}=t,{push:o,indent:a,deindent:l,newline:u}=e;if(r.type===4){const d=!bp(r.content);d&&o("("),c0(r,e),d&&o(")")}else o("("),Dt(r,e),o(")");i&&a(),e.indentLevel++,i||o(" "),o("? "),Dt(n,e),e.indentLevel--,i&&u(),i||o(" "),o(": ");const c=s.type===19;c||e.indentLevel++,Dt(s,e),c||e.indentLevel--,i&&l(!0)}function rk(t,e){const{push:r,helper:n,indent:s,deindent:i,newline:o}=e,{needPauseTracking:a,needArraySpread:l}=t;l&&r("[...("),r(`_cache[${t.index}] || (`),a&&(s(),r(`${n(tc)}(-1`),t.inVOnce&&r(", true"),r("),"),o(),r("(")),r(`_cache[${t.index}] = `),Dt(t.value,e),a&&(r(`).cacheIndex = ${t.index},`),o(),r(`${n(tc)}(1),`),o(),r(`_cache[${t.index}]`),i()),r(")"),l&&r(")]")}new RegExp("\\b"+"arguments,await,break,case,catch,class,const,continue,debugger,default,delete,do,else,export,extends,finally,for,function,if,import,let,new,return,super,switch,throw,try,var,void,while,with,yield".split(",").join("\\b|\\b")+"\\b");const nk=a0(/^(?:if|else|else-if)$/,(t,e,r)=>sk(t,e,r,(n,s,i)=>{const o=r.parent.children;let a=o.indexOf(n),l=0;for(;a-->=0;){const u=o[a];u&&u.type===9&&(l+=u.branches.length)}return()=>{if(i)n.codegenNode=Tg(s,l,r);else{const u=ik(n.codegenNode);u.alternate=Tg(s,l+n.branches.length-1,r)}}}));function sk(t,e,r,n){if(e.name!=="else"&&(!e.exp||!e.exp.content.trim())){const s=e.exp?e.exp.loc:t.loc;r.onError(Ge(28,e.loc)),e.exp=pe("true",!1,s)}if(e.name==="if"){const s=$g(t,e),i={type:9,loc:OT(t.loc),branches:[s]};if(r.replaceNode(i),n)return n(i,s,!0)}else{const s=r.parent.children;let i=s.indexOf(t);for(;i-->=-1;){const o=s[i];if(o&&o.type===3){r.removeNode(o);continue}if(o&&o.type===2&&!o.content.trim().length){r.removeNode(o);continue}if(o&&o.type===9){(e.name==="else-if"||e.name==="else")&&o.branches[o.branches.length-1].condition===void 0&&r.onError(Ge(30,t.loc)),r.removeNode();const a=$g(t,e);o.branches.push(a);const l=n&&n(o,a,!1);Jc(a,r),l&&l(),r.currentNode=null}else r.onError(Ge(30,t.loc));break}}}function $g(t,e){const r=t.tagType===3;return{type:10,loc:t.loc,condition:e.name==="else"?void 0:e.exp,children:r&&!Sr(t,"for")?t.children:[t],userKey:Gc(t,"key"),isTemplateIf:r}}function Tg(t,e,r){return t.condition?Lh(t.condition,kg(t,e,r),ht(r.helper(ba),['""',"true"])):kg(t,e,r)}function kg(t,e,r){const{helper:n}=r,s=ot("key",pe(`${e}`,!1,vr,2)),{children:i}=t,o=i[0];if(i.length!==1||o.type!==1)if(i.length===1&&o.type===11){const l=o.codegenNode;return ic(l,s,r),l}else return ea(r,n(Qo),xr([s]),i,64,void 0,void 0,!0,!1,!1,t.loc);else{const l=o.codegenNode,u=wT(l);return u.type===13&&gp(u,r),ic(u,s,r),l}}function ik(t){for(;;)if(t.type===19)if(t.alternate.type===19)t=t.alternate;else return t;else t.type===20&&(t=t.value)}const ok=a0("for",(t,e,r)=>{const{helper:n,removeHelper:s}=r;return ak(t,e,r,i=>{const o=ht(n(up),[i.source]),a=nc(t),l=Sr(t,"memo"),u=Gc(t,"key",!1,!0);u&&u.type;let c=u&&(u.type===6?u.value?pe(u.value.content,!0):void 0:u.exp);const d=u&&c?ot("key",c):null,p=i.source.type===4&&i.source.constType>0,f=p?64:u?128:256;return i.codegenNode=ea(r,n(Qo),void 0,o,f,void 0,void 0,!0,!p,!1,t.loc),()=>{let h;const{children:m}=i,g=m.length!==1||m[0].type!==1,v=sc(t)?t:a&&t.children.length===1&&sc(t.children[0])?t.children[0]:null;if(v?(h=v.codegenNode,a&&d&&ic(h,d,r)):g?h=ea(r,n(Qo),d?xr([d]):void 0,t.children,64,void 0,void 0,!0,void 0,!1):(h=m[0].codegenNode,a&&d&&ic(h,d,r),h.isBlock!==!p&&(h.isBlock?(s(Us),s(Ri(r.inSSR,h.isComponent))):s(Oi(r.inSSR,h.isComponent))),h.isBlock=!p,h.isBlock?(n(Us),n(Ri(r.inSSR,h.isComponent))):n(Oi(r.inSSR,h.isComponent))),l){const b=Pi(Dh(i.parseResult,[pe("_cached")]));b.body=lT([Mr(["const _memo = (",l.exp,")"]),Mr(["if (_cached",...c?[" && _cached.key === ",c]:[],` && ${r.helperString(W_)}(_cached, _memo)) return _cached`]),Mr(["const _item = ",h]),pe("_item.memo = _memo"),pe("return _item")]),o.arguments.push(b,pe("_cache"),pe(String(r.cached.length))),r.cached.push(null)}else o.arguments.push(Pi(Dh(i.parseResult),h,!0))}})});function ak(t,e,r,n){if(!e.exp){r.onError(Ge(31,e.loc));return}const s=e.forParseResult;if(!s){r.onError(Ge(32,e.loc));return}d0(s);const{addIdentifiers:i,removeIdentifiers:o,scopes:a}=r,{source:l,value:u,key:c,index:d}=s,p={type:11,loc:e.loc,source:l,valueAlias:u,keyAlias:c,objectIndexAlias:d,parseResult:s,children:nc(t)?t.children:[t]};r.replaceNode(p),a.vFor++;const f=n&&n(p);return()=>{a.vFor--,f&&f()}}function d0(t,e){t.finalized||(t.finalized=!0)}function Dh({value:t,key:e,index:r},n=[]){return lk([t,e,r,...n])}function lk(t){let e=t.length;for(;e--&&!t[e];);return t.slice(0,e+1).map((r,n)=>r||pe("_".repeat(n+1),!1))}const Pg=pe("undefined",!1),ck=(t,e)=>{if(t.type===1&&(t.tagType===1||t.tagType===3)){const r=Sr(t,"slot");if(r)return r.exp,e.scopes.vSlot++,()=>{e.scopes.vSlot--}}},uk=(t,e,r,n)=>Pi(t,r,!1,!0,r.length?r[0].loc:n);function dk(t,e,r=uk){e.helper(pp);const{children:n,loc:s}=t,i=[],o=[];let a=e.scopes.vSlot>0||e.scopes.vFor>0;const l=Sr(t,"slot",!0);if(l){const{arg:g,exp:v}=l;g&&!Qt(g)&&(a=!0),i.push(ot(g||pe("default",!0),r(v,void 0,n,s)))}let u=!1,c=!1;const d=[],p=new Set;let f=0;for(let g=0;g<n.length;g++){const v=n[g];let b;if(!nc(v)||!(b=Sr(v,"slot",!0))){v.type!==3&&d.push(v);continue}if(l){e.onError(Ge(37,b.loc));break}u=!0;const{children:w,loc:_}=v,{arg:S=pe("default",!0),exp:k,loc:O}=b;let R;Qt(S)?R=S?S.content:"default":a=!0;const C=Sr(v,"for"),A=r(k,C,w,_);let j,$;if(j=Sr(v,"if"))a=!0,o.push(Lh(j.exp,el(S,A,f++),Pg));else if($=Sr(v,/^else(?:-if)?$/,!0)){let y=g,I;for(;y--&&(I=n[y],!(I.type!==3&&Vh(I))););if(I&&nc(I)&&Sr(I,/^(?:else-)?if$/)){let q=o[o.length-1];for(;q.alternate.type===19;)q=q.alternate;q.alternate=$.exp?Lh($.exp,el(S,A,f++),Pg):el(S,A,f++)}else e.onError(Ge(30,$.loc))}else if(C){a=!0;const y=C.forParseResult;y?(d0(y),o.push(ht(e.helper(up),[y.source,Pi(Dh(y),el(S,A),!0)]))):e.onError(Ge(32,C.loc))}else{if(R){if(p.has(R)){e.onError(Ge(38,O));continue}p.add(R),R==="default"&&(c=!0)}i.push(ot(S,A))}}if(!l){const g=(v,b)=>{const w=r(v,void 0,b,s);return e.compatConfig&&(w.isNonScopedSlot=!0),ot("default",w)};u?d.length&&d.some(v=>Vh(v))&&(c?e.onError(Ge(39,d[0].loc)):i.push(g(void 0,d))):i.push(g(void 0,n))}const h=a?2:bl(t.children)?3:1;let m=xr(i.concat(ot("_",pe(h+"",!1))),s);return o.length&&(m=ht(e.helper(z_),[m,Is(o)])),{slots:m,hasDynamicSlots:a}}function el(t,e,r){const n=[ot("name",t),ot("fn",e)];return r!=null&&n.push(ot("key",pe(String(r),!0))),xr(n)}function bl(t){for(let e=0;e<t.length;e++){const r=t[e];switch(r.type){case 1:if(r.tagType===2||bl(r.children))return!0;break;case 9:if(bl(r.branches))return!0;break;case 10:case 11:if(bl(r.children))return!0;break}}return!1}function Vh(t){return t.type!==2&&t.type!==12?!0:t.type===2?!!t.content.trim():Vh(t.content)}const h0=new WeakMap,hk=(t,e)=>function(){if(t=e.currentNode,!(t.type===1&&(t.tagType===0||t.tagType===1)))return;const{tag:n,props:s}=t,i=t.tagType===1;let o=i?fk(t,e):`"${n}"`;const a=Ne(o)&&o.callee===op;let l,u,c=0,d,p,f,h=a||o===No||o===tp||!i&&(n==="svg"||n==="foreignObject"||n==="math");if(s.length>0){const m=f0(t,e,void 0,i,a);l=m.props,c=m.patchFlag,p=m.dynamicPropNames;const g=m.directives;f=g&&g.length?Is(g.map(v=>mk(v,e))):void 0,m.shouldUseBlock&&(h=!0)}if(t.children.length>0)if(o===Zl&&(h=!0,c|=1024),i&&o!==No&&o!==Zl){const{slots:g,hasDynamicSlots:v}=dk(t,e);u=g,v&&(c|=1024)}else if(t.children.length===1&&o!==No){const g=t.children[0],v=g.type,b=v===5||v===8;b&&mr(g,e)===0&&(c|=1),b||v===2?u=g:u=t.children}else u=t.children;p&&p.length&&(d=gk(p)),t.codegenNode=ea(e,o,l,u,c===0?void 0:c,d,f,!!h,!1,i,t.loc)};function fk(t,e,r=!1){let{tag:n}=t;const s=Bh(n),i=Gc(t,"is",!1,!0);if(i)if(s||Ls("COMPILER_IS_ON_ELEMENT",e)){let a;if(i.type===6?a=i.value&&pe(i.value.content,!0):(a=i.exp,a||(a=pe("is",!1,i.arg.loc))),a)return ht(e.helper(op),[a])}else i.type===6&&i.value.content.startsWith("vue:")&&(n=i.value.content.slice(4));const o=G_(n)||e.isBuiltInComponent(n);return o?(r||e.helper(o),o):(e.helper(ip),e.components.add(n),ra(n,"component"))}function f0(t,e,r=t.props,n,s,i=!1){const{tag:o,loc:a,children:l}=t;let u=[];const c=[],d=[],p=l.length>0;let f=!1,h=0,m=!1,g=!1,v=!1,b=!1,w=!1,_=!1;const S=[],k=A=>{u.length&&(c.push(xr(Og(u),a)),u=[]),A&&c.push(A)},O=()=>{e.scopes.vFor>0&&u.push(ot(pe("ref_for",!0),pe("true")))},R=({key:A,value:j})=>{if(Qt(A)){const $=A.content,y=Gs($);if(y&&(!n||s)&&$.toLowerCase()!=="onclick"&&$!=="onUpdate:modelValue"&&!Xn($)&&(b=!0),y&&Xn($)&&(_=!0),y&&j.type===14&&(j=j.arguments[0]),j.type===20||(j.type===4||j.type===8)&&mr(j,e)>0)return;$==="ref"?m=!0:$==="class"?g=!0:$==="style"?v=!0:$!=="key"&&!S.includes($)&&S.push($),n&&($==="class"||$==="style")&&!S.includes($)&&S.push($)}else w=!0};for(let A=0;A<r.length;A++){const j=r[A];if(j.type===6){const{loc:$,name:y,nameLoc:I,value:q}=j;let D=!0;if(y==="ref"&&(m=!0,O()),y==="is"&&(Bh(o)||q&&q.content.startsWith("vue:")||Ls("COMPILER_IS_ON_ELEMENT",e)))continue;u.push(ot(pe(y,!0,I),pe(q?q.content:"",D,q?q.loc:$)))}else{const{name:$,arg:y,exp:I,loc:q,modifiers:D}=j,z=$==="bind",P=$==="on";if($==="slot"){n||e.onError(Ge(40,q));continue}if($==="once"||$==="memo"||$==="is"||z&&ws(y,"is")&&(Bh(o)||Ls("COMPILER_IS_ON_ELEMENT",e))||P&&i)continue;if((z&&ws(y,"key")||P&&p&&ws(y,"vue:before-update"))&&(f=!0),z&&ws(y,"ref")&&O(),!y&&(z||P)){if(w=!0,I)if(z){if(k(),Ls("COMPILER_V_BIND_OBJECT_ORDER",e)){c.unshift(I);continue}O(),k(),c.push(I)}else k({type:14,loc:q,callee:e.helper(fp),arguments:n?[I]:[I,"true"]});else e.onError(Ge(z?34:35,q));continue}z&&D.some(ye=>ye.content==="prop")&&(h|=32);const ee=e.directiveTransforms[$];if(ee){const{props:ye,needRuntime:re}=ee(j,t,e);!i&&ye.forEach(R),P&&y&&!Qt(y)?k(xr(ye,a)):u.push(...ye),re&&(d.push(j),ir(re)&&h0.set(j,re))}else sx($)||(d.push(j),p&&(f=!0))}}let C;if(c.length?(k(),c.length>1?C=ht(e.helper(ec),c,a):C=c[0]):u.length&&(C=xr(Og(u),a)),w?h|=16:(g&&!n&&(h|=2),v&&!n&&(h|=4),S.length&&(h|=8),b&&(h|=32)),!f&&(h===0||h===32)&&(m||_||d.length>0)&&(h|=512),!e.inSSR&&C)switch(C.type){case 15:let A=-1,j=-1,$=!1;for(let q=0;q<C.properties.length;q++){const D=C.properties[q].key;Qt(D)?D.content==="class"?A=q:D.content==="style"&&(j=q):D.isHandlerKey||($=!0)}const y=C.properties[A],I=C.properties[j];$?C=ht(e.helper(Zo),[C]):(y&&!Qt(y.value)&&(y.value=ht(e.helper(dp),[y.value])),I&&(v||I.value.type===4&&I.value.content.trim()[0]==="["||I.value.type===17)&&(I.value=ht(e.helper(hp),[I.value])));break;case 14:break;default:C=ht(e.helper(Zo),[ht(e.helper(va),[C])]);break}return{props:C,directives:d,patchFlag:h,dynamicPropNames:S,shouldUseBlock:f}}function Og(t){const e=new Map,r=[];for(let n=0;n<t.length;n++){const s=t[n];if(s.key.type===8||!s.key.isStatic){r.push(s);continue}const i=s.key.content,o=e.get(i);o?(i==="style"||i==="class"||Gs(i))&&pk(o,s):(e.set(i,s),r.push(s))}return r}function pk(t,e){t.value.type===17?t.value.elements.push(e.value):t.value=Is([t.value,e.value],t.loc)}function mk(t,e){const r=[],n=h0.get(t);n?r.push(e.helperString(n)):(e.helper(ap),e.directives.add(t.name),r.push(ra(t.name,"directive")));const{loc:s}=t;if(t.exp&&r.push(t.exp),t.arg&&(t.exp||r.push("void 0"),r.push(t.arg)),Object.keys(t.modifiers).length){t.arg||(t.exp||r.push("void 0"),r.push("void 0"));const i=pe("true",!1,s);r.push(xr(t.modifiers.map(o=>ot(o,i)),s))}return Is(r,t.loc)}function gk(t){let e="[";for(let r=0,n=t.length;r<n;r++)e+=JSON.stringify(t[r]),r<n-1&&(e+=", ");return e+"]"}function Bh(t){return t==="component"||t==="Component"}const yk=(t,e)=>{if(sc(t)){const{children:r,loc:n}=t,{slotName:s,slotProps:i}=bk(t,e),o=[e.prefixIdentifiers?"_ctx.$slots":"$slots",s,"{}","undefined","true"];let a=2;i&&(o[2]=i,a=3),r.length&&(o[3]=Pi([],r,!1,!1,n),a=4),e.scopeId&&!e.slotted&&(a=5),o.splice(a),t.codegenNode=ht(e.helper(H_),o,n)}};function bk(t,e){let r='"default"',n;const s=[];for(let i=0;i<t.props.length;i++){const o=t.props[i];if(o.type===6)o.value&&(o.name==="name"?r=JSON.stringify(o.value.content):(o.name=We(o.name),s.push(o)));else if(o.name==="bind"&&ws(o.arg,"name")){if(o.exp)r=o.exp;else if(o.arg&&o.arg.type===4){const a=We(o.arg.content);r=o.exp=pe(a,!1,o.arg.loc)}}else o.name==="bind"&&o.arg&&Qt(o.arg)&&(o.arg.content=We(o.arg.content)),s.push(o)}if(s.length>0){const{props:i,directives:o}=f0(t,e,s,!1,!1);n=i,o.length&&e.onError(Ge(36,o[0].loc))}return{slotName:r,slotProps:n}}const p0=(t,e,r,n)=>{const{loc:s,modifiers:i,arg:o}=t;!t.exp&&!i.length&&r.onError(Ge(35,s));let a;if(o.type===4)if(o.isStatic){let d=o.content;d.startsWith("vue:")&&(d=`vnode-${d.slice(4)}`);const p=e.tagType!==0||d.startsWith("vnode")||!/[A-Z]/.test(d)?bi(We(d)):`on:${d}`;a=pe(p,!0,o.loc)}else a=Mr([`${r.helperString(Ih)}(`,o,")"]);else a=o,a.children.unshift(`${r.helperString(Ih)}(`),a.children.push(")");let l=t.exp;l&&!l.content.trim()&&(l=void 0);let u=r.cacheHandlers&&!l&&!r.inVOnce;if(l){const d=Y_(l),p=!(d||gT(l)),f=l.content.includes(";");(p||u&&d)&&(l=Mr([`${p?"$event":"(...args)"} => ${f?"{":"("}`,l,f?"}":")"]))}let c={props:[ot(a,l||pe("() => {}",!1,s))]};return n&&(c=n(c)),u&&(c.props[0].value=r.cache(c.props[0].value)),c.props.forEach(d=>d.key.isHandlerKey=!0),c},vk=(t,e,r)=>{const{modifiers:n,loc:s}=t,i=t.arg;let{exp:o}=t;return o&&o.type===4&&!o.content.trim()&&(o=void 0),i.type!==4?(i.children.unshift("("),i.children.push(') || ""')):i.isStatic||(i.content=i.content?`${i.content} || ""`:'""'),n.some(a=>a.content==="camel")&&(i.type===4?i.isStatic?i.content=We(i.content):i.content=`${r.helperString(Nh)}(${i.content})`:(i.children.unshift(`${r.helperString(Nh)}(`),i.children.push(")"))),r.inSSR||(n.some(a=>a.content==="prop")&&Rg(i,"."),n.some(a=>a.content==="attr")&&Rg(i,"^")),{props:[ot(i,o)]}},Rg=(t,e)=>{t.type===4?t.isStatic?t.content=e+t.content:t.content=`\`${e}\${${t.content}}\``:(t.children.unshift(`'${e}' + (`),t.children.push(")"))},wk=(t,e)=>{if(t.type===0||t.type===1||t.type===11||t.type===10)return()=>{const r=t.children;let n,s=!1;for(let i=0;i<r.length;i++){const o=r[i];if(Wu(o)){s=!0;for(let a=i+1;a<r.length;a++){const l=r[a];if(Wu(l))n||(n=r[i]=Mr([o],o.loc)),n.children.push(" + ",l),r.splice(a,1),a--;else{n=void 0;break}}}}if(!(!s||r.length===1&&(t.type===0||t.type===1&&t.tagType===0&&!t.props.find(i=>i.type===7&&!e.directiveTransforms[i.name])&&t.tag!=="template")))for(let i=0;i<r.length;i++){const o=r[i];if(Wu(o)||o.type===8){const a=[];(o.type!==2||o.content!==" ")&&a.push(o),!e.ssr&&mr(o,e)===0&&a.push("1"),r[i]={type:12,content:o,loc:o.loc,codegenNode:ht(e.helper(sp),a)}}}}},Ng=new WeakSet,_k=(t,e)=>{if(t.type===1&&Sr(t,"once",!0))return Ng.has(t)||e.inVOnce||e.inSSR?void 0:(Ng.add(t),e.inVOnce=!0,e.helper(tc),()=>{e.inVOnce=!1;const r=e.currentNode;r.codegenNode&&(r.codegenNode=e.cache(r.codegenNode,!0,!0))})},m0=(t,e,r)=>{const{exp:n,arg:s}=t;if(!n)return r.onError(Ge(41,t.loc)),tl();const i=n.loc.source.trim(),o=n.type===4?n.content:i,a=r.bindingMetadata[i];if(a==="props"||a==="props-aliased")return r.onError(Ge(44,n.loc)),tl();if(!o.trim()||!Y_(n))return r.onError(Ge(42,n.loc)),tl();const l=s||pe("modelValue",!0),u=s?Qt(s)?`onUpdate:${We(s.content)}`:Mr(['"onUpdate:" + ',s]):"onUpdate:modelValue";let c;const d=r.isTS?"($event: any)":"$event";c=Mr([`${d} => ((`,n,") = $event)"]);const p=[ot(l,t.exp),ot(u,c)];if(t.modifiers.length&&e.tagType===1){const f=t.modifiers.map(m=>m.content).map(m=>(bp(m)?m:JSON.stringify(m))+": true").join(", "),h=s?Qt(s)?`${s.content}Modifiers`:Mr([s,' + "Modifiers"']):"modelModifiers";p.push(ot(h,pe(`{ ${f} }`,!1,t.loc,2)))}return tl(p)};function tl(t=[]){return{props:t}}const Ek=/[\w).+\-_$\]]/,Sk=(t,e)=>{Ls("COMPILER_FILTERS",e)&&(t.type===5?oc(t.content,e):t.type===1&&t.props.forEach(r=>{r.type===7&&r.name!=="for"&&r.exp&&oc(r.exp,e)}))};function oc(t,e){if(t.type===4)Ig(t,e);else for(let r=0;r<t.children.length;r++){const n=t.children[r];typeof n=="object"&&(n.type===4?Ig(n,e):n.type===8?oc(t,e):n.type===5&&oc(n.content,e))}}function Ig(t,e){const r=t.content;let n=!1,s=!1,i=!1,o=!1,a=0,l=0,u=0,c=0,d,p,f,h,m=[];for(f=0;f<r.length;f++)if(p=d,d=r.charCodeAt(f),n)d===39&&p!==92&&(n=!1);else if(s)d===34&&p!==92&&(s=!1);else if(i)d===96&&p!==92&&(i=!1);else if(o)d===47&&p!==92&&(o=!1);else if(d===124&&r.charCodeAt(f+1)!==124&&r.charCodeAt(f-1)!==124&&!a&&!l&&!u)h===void 0?(c=f+1,h=r.slice(0,f).trim()):g();else{switch(d){case 34:s=!0;break;case 39:n=!0;break;case 96:i=!0;break;case 40:u++;break;case 41:u--;break;case 91:l++;break;case 93:l--;break;case 123:a++;break;case 125:a--;break}if(d===47){let v=f-1,b;for(;v>=0&&(b=r.charAt(v),b===" ");v--);(!b||!Ek.test(b))&&(o=!0)}}h===void 0?h=r.slice(0,f).trim():c!==0&&g();function g(){m.push(r.slice(c,f).trim()),c=f+1}if(m.length){for(f=0;f<m.length;f++)h=xk(h,m[f],e);t.content=h,t.ast=void 0}}function xk(t,e,r){r.helper(lp);const n=e.indexOf("(");if(n<0)return r.filters.add(e),`${ra(e,"filter")}(${t})`;{const s=e.slice(0,n),i=e.slice(n+1);return r.filters.add(s),`${ra(s,"filter")}(${t}${i!==")"?","+i:i}`}}const Lg=new WeakSet,Ck=(t,e)=>{if(t.type===1){const r=Sr(t,"memo");return!r||Lg.has(t)||e.inSSR?void 0:(Lg.add(t),()=>{const n=t.codegenNode||e.currentNode.codegenNode;n&&n.type===13&&(t.tagType!==1&&gp(n,e),t.codegenNode=ht(e.helper(mp),[r.exp,Pi(void 0,n),"_cache",String(e.cached.length)]),e.cached.push(null))})}},Ak=(t,e)=>{if(t.type===1){for(const r of t.props)if(r.type===7&&r.name==="bind"&&(!r.exp||r.exp.type===4&&!r.exp.content.trim())&&r.arg){const n=r.arg;if(n.type!==4||!n.isStatic)e.onError(Ge(52,n.loc)),r.exp=pe("",!0,n.loc);else{const s=We(n.content);(J_.test(s[0])||s[0]==="-")&&(r.exp=pe(s,!1,n.loc))}}}};function $k(t){return[[Ak,_k,nk,Ck,ok,Sk,yk,hk,ck,wk],{on:p0,bind:vk,model:m0}]}function Tk(t,e={}){const r=e.onError||yp,n=e.mode==="module";e.prefixIdentifiers===!0?r(Ge(47)):n&&r(Ge(48));const s=!1;e.cacheHandlers&&r(Ge(49)),e.scopeId&&!n&&r(Ge(50));const i=we({},e,{prefixIdentifiers:s}),o=de(t)?IT(t,i):t,[a,l]=$k();return DT(o,we({},i,{nodeTransforms:[...a,...e.nodeTransforms||[]],directiveTransforms:we({},l,e.directiveTransforms||{})})),jT(o,i)}const kk=()=>({props:[]});const g0=Symbol(""),y0=Symbol(""),b0=Symbol(""),v0=Symbol(""),Uh=Symbol(""),w0=Symbol(""),_0=Symbol(""),E0=Symbol(""),S0=Symbol(""),x0=Symbol("");iT({[g0]:"vModelRadio",[y0]:"vModelCheckbox",[b0]:"vModelText",[v0]:"vModelSelect",[Uh]:"vModelDynamic",[w0]:"withModifiers",[_0]:"withKeys",[E0]:"vShow",[S0]:"Transition",[x0]:"TransitionGroup"});let ii;function Pk(t,e=!1){return ii||(ii=document.createElement("div")),e?(ii.innerHTML=`<div foo="${t.replace(/"/g,"&quot;")}">`,ii.children[0].getAttribute("foo")):(ii.innerHTML=t,ii.textContent)}const Ok={parseMode:"html",isVoidTag:_x,isNativeTag:t=>bx(t)||vx(t)||wx(t),isPreTag:t=>t==="pre",isIgnoreNewlineTag:t=>t==="pre"||t==="textarea",decodeEntities:Pk,isBuiltInComponent:t=>{if(t==="Transition"||t==="transition")return S0;if(t==="TransitionGroup"||t==="transition-group")return x0},getNamespace(t,e,r){let n=e?e.ns:r;if(e&&n===2)if(e.tag==="annotation-xml"){if(t==="svg")return 1;e.props.some(s=>s.type===6&&s.name==="encoding"&&s.value!=null&&(s.value.content==="text/html"||s.value.content==="application/xhtml+xml"))&&(n=0)}else/^m(?:[ions]|text)$/.test(e.tag)&&t!=="mglyph"&&t!=="malignmark"&&(n=0);else e&&n===1&&(e.tag==="foreignObject"||e.tag==="desc"||e.tag==="title")&&(n=0);if(n===0){if(t==="svg")return 1;if(t==="math")return 2}return n}},Rk=t=>{t.type===1&&t.props.forEach((e,r)=>{e.type===6&&e.name==="style"&&e.value&&(t.props[r]={type:7,name:"bind",arg:pe("style",!0,e.loc),exp:Nk(e.value.content,e.loc),modifiers:[],loc:e.loc})})},Nk=(t,e)=>{const r=Bv(t);return pe(JSON.stringify(r),!1,e,3)};function ts(t,e){return Ge(t,e)}const Ik=(t,e,r)=>{const{exp:n,loc:s}=t;return n||r.onError(ts(53,s)),e.children.length&&(r.onError(ts(54,s)),e.children.length=0),{props:[ot(pe("innerHTML",!0,s),n||pe("",!0))]}},Lk=(t,e,r)=>{const{exp:n,loc:s}=t;return n||r.onError(ts(55,s)),e.children.length&&(r.onError(ts(56,s)),e.children.length=0),{props:[ot(pe("textContent",!0),n?mr(n,r)>0?n:ht(r.helperString(Kc),[n],s):pe("",!0))]}},Fk=(t,e,r)=>{const n=m0(t,e,r);if(!n.props.length||e.tagType===1)return n;t.arg&&r.onError(ts(58,t.arg.loc));const{tag:s}=e,i=r.isCustomElement(s);if(s==="input"||s==="textarea"||s==="select"||i){let o=b0,a=!1;if(s==="input"||i){const l=Gc(e,"type");if(l){if(l.type===7)o=Uh;else if(l.value)switch(l.value.content){case"radio":o=g0;break;case"checkbox":o=y0;break;case"file":a=!0,r.onError(ts(59,t.loc));break}}else yT(e)&&(o=Uh)}else s==="select"&&(o=v0);a||(n.needRuntime=r.helper(o))}else r.onError(ts(57,t.loc));return n.props=n.props.filter(o=>!(o.key.type===4&&o.key.content==="modelValue")),n},Mk=br("passive,once,capture"),Dk=br("stop,prevent,self,ctrl,shift,alt,meta,exact,middle"),Vk=br("left,right"),C0=br("onkeyup,onkeydown,onkeypress"),Bk=(t,e,r,n)=>{const s=[],i=[],o=[];for(let a=0;a<e.length;a++){const l=e[a].content;l==="native"&&ta("COMPILER_V_ON_NATIVE",r)||Mk(l)?o.push(l):Vk(l)?Qt(t)?C0(t.content.toLowerCase())?s.push(l):i.push(l):(s.push(l),i.push(l)):Dk(l)?i.push(l):s.push(l)}return{keyModifiers:s,nonKeyModifiers:i,eventOptionModifiers:o}},Fg=(t,e)=>Qt(t)&&t.content.toLowerCase()==="onclick"?pe(e,!0):t.type!==4?Mr(["(",t,`) === "onClick" ? "${e}" : (`,t,")"]):t,Uk=(t,e,r)=>p0(t,e,r,n=>{const{modifiers:s}=t;if(!s.length)return n;let{key:i,value:o}=n.props[0];const{keyModifiers:a,nonKeyModifiers:l,eventOptionModifiers:u}=Bk(i,s,r,t.loc);if(l.includes("right")&&(i=Fg(i,"onContextmenu")),l.includes("middle")&&(i=Fg(i,"onMouseup")),l.length&&(o=ht(r.helper(w0),[o,JSON.stringify(l)])),a.length&&(!Qt(i)||C0(i.content.toLowerCase()))&&(o=ht(r.helper(_0),[o,JSON.stringify(a)])),u.length){const c=u.map(Xs).join("");i=Qt(i)?pe(`${i.content}${c}`,!0):Mr(["(",i,`) + "${c}"`])}return{props:[ot(i,o)]}}),jk=(t,e,r)=>{const{exp:n,loc:s}=t;return n||r.onError(ts(61,s)),{props:[],needRuntime:r.helper(E0)}},qk=(t,e)=>{t.type===1&&t.tagType===0&&(t.tag==="script"||t.tag==="style")&&e.removeNode()},Hk=[Rk],zk={cloak:kk,html:Ik,text:Lk,model:Fk,on:Uk,show:jk};function Wk(t,e={}){return Tk(t,we({},Ok,e,{nodeTransforms:[qk,...Hk,...e.nodeTransforms||[]],directiveTransforms:we({},zk,e.directiveTransforms||{}),transformHoist:null}))}const Mg=Object.create(null);function Kk(t,e){if(!de(t))if(t.nodeType)t=t.innerHTML;else return Tt;const r=ax(t,e),n=Mg[r];if(n)return n;if(t[0]==="#"){const a=document.querySelector(t);t=a?a.innerHTML:""}const s=we({hoistStatic:!0,onError:void 0,onWarn:Tt},e);!s.isCustomElement&&typeof customElements<"u"&&(s.isCustomElement=a=>!!customElements.get(a));const{code:i}=Wk(t,s),o=new Function("Vue",i)(Z$);return o._rc=!0,Mg[r]=o}f_(Kk);async function Gk(t,e){for(const r of Array.isArray(t)?t:[t]){const n=e[r];if(!(typeof n>"u"))return typeof n=="function"?n():n}throw new Error(`Page not found: ${t}`)}var A0=typeof global=="object"&&global&&global.Object===Object&&global,Jk=typeof self=="object"&&self&&self.Object===Object&&self,rn=A0||Jk||Function("return this")(),Qr=rn.Symbol,$0=Object.prototype,Xk=$0.hasOwnProperty,Yk=$0.toString,ho=Qr?Qr.toStringTag:void 0;function Qk(t){var e=Xk.call(t,ho),r=t[ho];try{t[ho]=void 0;var n=!0}catch{}var s=Yk.call(t);return n&&(e?t[ho]=r:delete t[ho]),s}var Zk=Object.prototype,e2=Zk.toString;function t2(t){return e2.call(t)}var r2="[object Null]",n2="[object Undefined]",Dg=Qr?Qr.toStringTag:void 0;function Ji(t){return t==null?t===void 0?n2:r2:Dg&&Dg in Object(t)?Qk(t):t2(t)}function ss(t){return t!=null&&typeof t=="object"}var s2="[object Symbol]";function _p(t){return typeof t=="symbol"||ss(t)&&Ji(t)==s2}function i2(t,e){for(var r=-1,n=t==null?0:t.length,s=Array(n);++r<n;)s[r]=e(t[r],r,t);return s}var Cn=Array.isArray,Vg=Qr?Qr.prototype:void 0,Bg=Vg?Vg.toString:void 0;function T0(t){if(typeof t=="string")return t;if(Cn(t))return i2(t,T0)+"";if(_p(t))return Bg?Bg.call(t):"";var e=t+"";return e=="0"&&1/t==-1/0?"-0":e}function Ni(t){var e=typeof t;return t!=null&&(e=="object"||e=="function")}var o2="[object AsyncFunction]",a2="[object Function]",l2="[object GeneratorFunction]",c2="[object Proxy]";function k0(t){if(!Ni(t))return!1;var e=Ji(t);return e==a2||e==l2||e==o2||e==c2}var Gu=rn["__core-js_shared__"],Ug=(function(){var t=/[^.]+$/.exec(Gu&&Gu.keys&&Gu.keys.IE_PROTO||"");return t?"Symbol(src)_1."+t:""})();function u2(t){return!!Ug&&Ug in t}var d2=Function.prototype,h2=d2.toString;function Qs(t){if(t!=null){try{return h2.call(t)}catch{}try{return t+""}catch{}}return""}var f2=/[\\^$.*+?()[\]{}|]/g,p2=/^\[object .+?Constructor\]$/,m2=Function.prototype,g2=Object.prototype,y2=m2.toString,b2=g2.hasOwnProperty,v2=RegExp("^"+y2.call(b2).replace(f2,"\\$&").replace(/hasOwnProperty|(function).*?(?=\\\()| for .+?(?=\\\])/g,"$1.*?")+"$");function w2(t){if(!Ni(t)||u2(t))return!1;var e=k0(t)?v2:p2;return e.test(Qs(t))}function _2(t,e){return t?.[e]}function Zs(t,e){var r=_2(t,e);return w2(r)?r:void 0}var jh=Zs(rn,"WeakMap"),jg=Object.create,E2=(function(){function t(){}return function(e){if(!Ni(e))return{};if(jg)return jg(e);t.prototype=e;var r=new t;return t.prototype=void 0,r}})(),qg=(function(){try{var t=Zs(Object,"defineProperty");return t({},"",{}),t}catch{}})();function S2(t,e){for(var r=-1,n=t==null?0:t.length;++r<n&&e(t[r],r,t)!==!1;);return t}var x2=9007199254740991,C2=/^(?:0|[1-9]\d*)$/;function Ep(t,e){var r=typeof t;return e=e??x2,!!e&&(r=="number"||r!="symbol"&&C2.test(t))&&t>-1&&t%1==0&&t<e}function A2(t,e,r){e=="__proto__"&&qg?qg(t,e,{configurable:!0,enumerable:!0,value:r,writable:!0}):t[e]=r}function Sp(t,e){return t===e||t!==t&&e!==e}var $2=Object.prototype,T2=$2.hasOwnProperty;function P0(t,e,r){var n=t[e];(!(T2.call(t,e)&&Sp(n,r))||r===void 0&&!(e in t))&&A2(t,e,r)}var k2=9007199254740991;function xp(t){return typeof t=="number"&&t>-1&&t%1==0&&t<=k2}function P2(t){return t!=null&&xp(t.length)&&!k0(t)}var O2=Object.prototype;function O0(t){var e=t&&t.constructor,r=typeof e=="function"&&e.prototype||O2;return t===r}function R2(t,e){for(var r=-1,n=Array(t);++r<t;)n[r]=e(r);return n}var N2="[object Arguments]";function Hg(t){return ss(t)&&Ji(t)==N2}var R0=Object.prototype,I2=R0.hasOwnProperty,L2=R0.propertyIsEnumerable,N0=Hg((function(){return arguments})())?Hg:function(t){return ss(t)&&I2.call(t,"callee")&&!L2.call(t,"callee")};function F2(){return!1}var I0=typeof exports=="object"&&exports&&!exports.nodeType&&exports,zg=I0&&typeof module=="object"&&module&&!module.nodeType&&module,M2=zg&&zg.exports===I0,Wg=M2?rn.Buffer:void 0,D2=Wg?Wg.isBuffer:void 0,ac=D2||F2,V2="[object Arguments]",B2="[object Array]",U2="[object Boolean]",j2="[object Date]",q2="[object Error]",H2="[object Function]",z2="[object Map]",W2="[object Number]",K2="[object Object]",G2="[object RegExp]",J2="[object Set]",X2="[object String]",Y2="[object WeakMap]",Q2="[object ArrayBuffer]",Z2="[object DataView]",eP="[object Float32Array]",tP="[object Float64Array]",rP="[object Int8Array]",nP="[object Int16Array]",sP="[object Int32Array]",iP="[object Uint8Array]",oP="[object Uint8ClampedArray]",aP="[object Uint16Array]",lP="[object Uint32Array]",ze={};ze[eP]=ze[tP]=ze[rP]=ze[nP]=ze[sP]=ze[iP]=ze[oP]=ze[aP]=ze[lP]=!0;ze[V2]=ze[B2]=ze[Q2]=ze[U2]=ze[Z2]=ze[j2]=ze[q2]=ze[H2]=ze[z2]=ze[W2]=ze[K2]=ze[G2]=ze[J2]=ze[X2]=ze[Y2]=!1;function cP(t){return ss(t)&&xp(t.length)&&!!ze[Ji(t)]}function Cp(t){return function(e){return t(e)}}var L0=typeof exports=="object"&&exports&&!exports.nodeType&&exports,Io=L0&&typeof module=="object"&&module&&!module.nodeType&&module,uP=Io&&Io.exports===L0,Ju=uP&&A0.process,Ii=(function(){try{var t=Io&&Io.require&&Io.require("util").types;return t||Ju&&Ju.binding&&Ju.binding("util")}catch{}})(),Kg=Ii&&Ii.isTypedArray,F0=Kg?Cp(Kg):cP,dP=Object.prototype,hP=dP.hasOwnProperty;function fP(t,e){var r=Cn(t),n=!r&&N0(t),s=!r&&!n&&ac(t),i=!r&&!n&&!s&&F0(t),o=r||n||s||i,a=o?R2(t.length,String):[],l=a.length;for(var u in t)hP.call(t,u)&&!(o&&(u=="length"||s&&(u=="offset"||u=="parent")||i&&(u=="buffer"||u=="byteLength"||u=="byteOffset")||Ep(u,l)))&&a.push(u);return a}function M0(t,e){return function(r){return t(e(r))}}var pP=M0(Object.keys,Object),mP=Object.prototype,gP=mP.hasOwnProperty;function yP(t){if(!O0(t))return pP(t);var e=[];for(var r in Object(t))gP.call(t,r)&&r!="constructor"&&e.push(r);return e}function bP(t){return P2(t)?fP(t):yP(t)}var vP=/\.|\[(?:[^[\]]*|(["'])(?:(?!\1)[^\\]|\\.)*?\1)\]/,wP=/^\w*$/;function _P(t,e){if(Cn(t))return!1;var r=typeof t;return r=="number"||r=="symbol"||r=="boolean"||t==null||_p(t)?!0:wP.test(t)||!vP.test(t)||e!=null&&t in Object(e)}var sa=Zs(Object,"create");function EP(){this.__data__=sa?sa(null):{},this.size=0}function SP(t){var e=this.has(t)&&delete this.__data__[t];return this.size-=e?1:0,e}var xP="__lodash_hash_undefined__",CP=Object.prototype,AP=CP.hasOwnProperty;function $P(t){var e=this.__data__;if(sa){var r=e[t];return r===xP?void 0:r}return AP.call(e,t)?e[t]:void 0}var TP=Object.prototype,kP=TP.hasOwnProperty;function PP(t){var e=this.__data__;return sa?e[t]!==void 0:kP.call(e,t)}var OP="__lodash_hash_undefined__";function RP(t,e){var r=this.__data__;return this.size+=this.has(t)?0:1,r[t]=sa&&e===void 0?OP:e,this}function js(t){var e=-1,r=t==null?0:t.length;for(this.clear();++e<r;){var n=t[e];this.set(n[0],n[1])}}js.prototype.clear=EP;js.prototype.delete=SP;js.prototype.get=$P;js.prototype.has=PP;js.prototype.set=RP;function NP(){this.__data__=[],this.size=0}function Yc(t,e){for(var r=t.length;r--;)if(Sp(t[r][0],e))return r;return-1}var IP=Array.prototype,LP=IP.splice;function FP(t){var e=this.__data__,r=Yc(e,t);if(r<0)return!1;var n=e.length-1;return r==n?e.pop():LP.call(e,r,1),--this.size,!0}function MP(t){var e=this.__data__,r=Yc(e,t);return r<0?void 0:e[r][1]}function DP(t){return Yc(this.__data__,t)>-1}function VP(t,e){var r=this.__data__,n=Yc(r,t);return n<0?(++this.size,r.push([t,e])):r[n][1]=e,this}function $n(t){var e=-1,r=t==null?0:t.length;for(this.clear();++e<r;){var n=t[e];this.set(n[0],n[1])}}$n.prototype.clear=NP;$n.prototype.delete=FP;$n.prototype.get=MP;$n.prototype.has=DP;$n.prototype.set=VP;var ia=Zs(rn,"Map");function BP(){this.size=0,this.__data__={hash:new js,map:new(ia||$n),string:new js}}function UP(t){var e=typeof t;return e=="string"||e=="number"||e=="symbol"||e=="boolean"?t!=="__proto__":t===null}function Qc(t,e){var r=t.__data__;return UP(e)?r[typeof e=="string"?"string":"hash"]:r.map}function jP(t){var e=Qc(this,t).delete(t);return this.size-=e?1:0,e}function qP(t){return Qc(this,t).get(t)}function HP(t){return Qc(this,t).has(t)}function zP(t,e){var r=Qc(this,t),n=r.size;return r.set(t,e),this.size+=r.size==n?0:1,this}function Tn(t){var e=-1,r=t==null?0:t.length;for(this.clear();++e<r;){var n=t[e];this.set(n[0],n[1])}}Tn.prototype.clear=BP;Tn.prototype.delete=jP;Tn.prototype.get=qP;Tn.prototype.has=HP;Tn.prototype.set=zP;var WP="Expected a function";function Ap(t,e){if(typeof t!="function"||e!=null&&typeof e!="function")throw new TypeError(WP);var r=function(){var n=arguments,s=e?e.apply(this,n):n[0],i=r.cache;if(i.has(s))return i.get(s);var o=t.apply(this,n);return r.cache=i.set(s,o)||i,o};return r.cache=new(Ap.Cache||Tn),r}Ap.Cache=Tn;var KP=500;function GP(t){var e=Ap(t,function(n){return r.size===KP&&r.clear(),n}),r=e.cache;return e}var JP=/[^.[\]]+|\[(?:(-?\d+(?:\.\d+)?)|(["'])((?:(?!\2)[^\\]|\\.)*?)\2)\]|(?=(?:\.|\[\])(?:\.|\[\]|$))/g,XP=/\\(\\)?/g,YP=GP(function(t){var e=[];return t.charCodeAt(0)===46&&e.push(""),t.replace(JP,function(r,n,s,i){e.push(s?i.replace(XP,"$1"):n||r)}),e});function D0(t){return t==null?"":T0(t)}function $p(t,e){return Cn(t)?t:_P(t,e)?[t]:YP(D0(t))}function Tp(t){if(typeof t=="string"||_p(t))return t;var e=t+"";return e=="0"&&1/t==-1/0?"-0":e}function QP(t,e){e=$p(e,t);for(var r=0,n=e.length;t!=null&&r<n;)t=t[Tp(e[r++])];return r&&r==n?t:void 0}function Si(t,e,r){var n=t==null?void 0:QP(t,e);return n===void 0?r:n}function ZP(t,e){for(var r=-1,n=e.length,s=t.length;++r<n;)t[s+r]=e[r];return t}var eO=M0(Object.getPrototypeOf,Object);function tO(t){return function(e){return t?.[e]}}function rO(){this.__data__=new $n,this.size=0}function nO(t){var e=this.__data__,r=e.delete(t);return this.size=e.size,r}function sO(t){return this.__data__.get(t)}function iO(t){return this.__data__.has(t)}var oO=200;function aO(t,e){var r=this.__data__;if(r instanceof $n){var n=r.__data__;if(!ia||n.length<oO-1)return n.push([t,e]),this.size=++r.size,this;r=this.__data__=new Tn(n)}return r.set(t,e),this.size=r.size,this}function vn(t){var e=this.__data__=new $n(t);this.size=e.size}vn.prototype.clear=rO;vn.prototype.delete=nO;vn.prototype.get=sO;vn.prototype.has=iO;vn.prototype.set=aO;var V0=typeof exports=="object"&&exports&&!exports.nodeType&&exports,Gg=V0&&typeof module=="object"&&module&&!module.nodeType&&module,lO=Gg&&Gg.exports===V0,Jg=lO?rn.Buffer:void 0;Jg&&Jg.allocUnsafe;function cO(t,e){return t.slice()}function uO(t,e){for(var r=-1,n=t==null?0:t.length,s=0,i=[];++r<n;){var o=t[r];e(o,r,t)&&(i[s++]=o)}return i}function dO(){return[]}var hO=Object.prototype,fO=hO.propertyIsEnumerable,Xg=Object.getOwnPropertySymbols,pO=Xg?function(t){return t==null?[]:(t=Object(t),uO(Xg(t),function(e){return fO.call(t,e)}))}:dO;function mO(t,e,r){var n=e(t);return Cn(t)?n:ZP(n,r(t))}function qh(t){return mO(t,bP,pO)}var Hh=Zs(rn,"DataView"),zh=Zs(rn,"Promise"),Wh=Zs(rn,"Set"),Yg="[object Map]",gO="[object Object]",Qg="[object Promise]",Zg="[object Set]",ey="[object WeakMap]",ty="[object DataView]",yO=Qs(Hh),bO=Qs(ia),vO=Qs(zh),wO=Qs(Wh),_O=Qs(jh),Nr=Ji;(Hh&&Nr(new Hh(new ArrayBuffer(1)))!=ty||ia&&Nr(new ia)!=Yg||zh&&Nr(zh.resolve())!=Qg||Wh&&Nr(new Wh)!=Zg||jh&&Nr(new jh)!=ey)&&(Nr=function(t){var e=Ji(t),r=e==gO?t.constructor:void 0,n=r?Qs(r):"";if(n)switch(n){case yO:return ty;case bO:return Yg;case vO:return Qg;case wO:return Zg;case _O:return ey}return e});var EO=Object.prototype,SO=EO.hasOwnProperty;function xO(t){var e=t.length,r=new t.constructor(e);return e&&typeof t[0]=="string"&&SO.call(t,"index")&&(r.index=t.index,r.input=t.input),r}var lc=rn.Uint8Array;function kp(t){var e=new t.constructor(t.byteLength);return new lc(e).set(new lc(t)),e}function CO(t,e){var r=kp(t.buffer);return new t.constructor(r,t.byteOffset,t.byteLength)}var AO=/\w*$/;function $O(t){var e=new t.constructor(t.source,AO.exec(t));return e.lastIndex=t.lastIndex,e}var ry=Qr?Qr.prototype:void 0,ny=ry?ry.valueOf:void 0;function TO(t){return ny?Object(ny.call(t)):{}}function kO(t,e){var r=kp(t.buffer);return new t.constructor(r,t.byteOffset,t.length)}var PO="[object Boolean]",OO="[object Date]",RO="[object Map]",NO="[object Number]",IO="[object RegExp]",LO="[object Set]",FO="[object String]",MO="[object Symbol]",DO="[object ArrayBuffer]",VO="[object DataView]",BO="[object Float32Array]",UO="[object Float64Array]",jO="[object Int8Array]",qO="[object Int16Array]",HO="[object Int32Array]",zO="[object Uint8Array]",WO="[object Uint8ClampedArray]",KO="[object Uint16Array]",GO="[object Uint32Array]";function JO(t,e,r){var n=t.constructor;switch(e){case DO:return kp(t);case PO:case OO:return new n(+t);case VO:return CO(t);case BO:case UO:case jO:case qO:case HO:case zO:case WO:case KO:case GO:return kO(t);case RO:return new n;case NO:case FO:return new n(t);case IO:return $O(t);case LO:return new n;case MO:return TO(t)}}function XO(t){return typeof t.constructor=="function"&&!O0(t)?E2(eO(t)):{}}var YO="[object Map]";function QO(t){return ss(t)&&Nr(t)==YO}var sy=Ii&&Ii.isMap,ZO=sy?Cp(sy):QO,eR="[object Set]";function tR(t){return ss(t)&&Nr(t)==eR}var iy=Ii&&Ii.isSet,rR=iy?Cp(iy):tR,B0="[object Arguments]",nR="[object Array]",sR="[object Boolean]",iR="[object Date]",oR="[object Error]",U0="[object Function]",aR="[object GeneratorFunction]",lR="[object Map]",cR="[object Number]",j0="[object Object]",uR="[object RegExp]",dR="[object Set]",hR="[object String]",fR="[object Symbol]",pR="[object WeakMap]",mR="[object ArrayBuffer]",gR="[object DataView]",yR="[object Float32Array]",bR="[object Float64Array]",vR="[object Int8Array]",wR="[object Int16Array]",_R="[object Int32Array]",ER="[object Uint8Array]",SR="[object Uint8ClampedArray]",xR="[object Uint16Array]",CR="[object Uint32Array]",Ve={};Ve[B0]=Ve[nR]=Ve[mR]=Ve[gR]=Ve[sR]=Ve[iR]=Ve[yR]=Ve[bR]=Ve[vR]=Ve[wR]=Ve[_R]=Ve[lR]=Ve[cR]=Ve[j0]=Ve[uR]=Ve[dR]=Ve[hR]=Ve[fR]=Ve[ER]=Ve[SR]=Ve[xR]=Ve[CR]=!0;Ve[oR]=Ve[U0]=Ve[pR]=!1;function vl(t,e,r,n,s,i){var o;if(o!==void 0)return o;if(!Ni(t))return t;var a=Cn(t);if(a)o=xO(t);else{var l=Nr(t),u=l==U0||l==aR;if(ac(t))return cO(t);if(l==j0||l==B0||u&&!s)o=u?{}:XO(t);else{if(!Ve[l])return s?t:{};o=JO(t,l)}}i||(i=new vn);var c=i.get(t);if(c)return c;i.set(t,o),rR(t)?t.forEach(function(f){o.add(vl(f,e,r,f,t,i))}):ZO(t)&&t.forEach(function(f,h){o.set(h,vl(f,e,r,h,t,i))});var d=qh,p=a?void 0:d(t);return S2(p||t,function(f,h){p&&(h=f,f=t[h]),P0(o,h,vl(f,e,r,h,t,i))}),o}var AR=1,$R=4;function Gt(t){return vl(t,AR|$R)}var TR="__lodash_hash_undefined__";function kR(t){return this.__data__.set(t,TR),this}function PR(t){return this.__data__.has(t)}function cc(t){var e=-1,r=t==null?0:t.length;for(this.__data__=new Tn;++e<r;)this.add(t[e])}cc.prototype.add=cc.prototype.push=kR;cc.prototype.has=PR;function OR(t,e){for(var r=-1,n=t==null?0:t.length;++r<n;)if(e(t[r],r,t))return!0;return!1}function RR(t,e){return t.has(e)}var NR=1,IR=2;function q0(t,e,r,n,s,i){var o=r&NR,a=t.length,l=e.length;if(a!=l&&!(o&&l>a))return!1;var u=i.get(t),c=i.get(e);if(u&&c)return u==e&&c==t;var d=-1,p=!0,f=r&IR?new cc:void 0;for(i.set(t,e),i.set(e,t);++d<a;){var h=t[d],m=e[d];if(n)var g=o?n(m,h,d,e,t,i):n(h,m,d,t,e,i);if(g!==void 0){if(g)continue;p=!1;break}if(f){if(!OR(e,function(v,b){if(!RR(f,b)&&(h===v||s(h,v,r,n,i)))return f.push(b)})){p=!1;break}}else if(!(h===m||s(h,m,r,n,i))){p=!1;break}}return i.delete(t),i.delete(e),p}function LR(t){var e=-1,r=Array(t.size);return t.forEach(function(n,s){r[++e]=[s,n]}),r}function FR(t){var e=-1,r=Array(t.size);return t.forEach(function(n){r[++e]=n}),r}var MR=1,DR=2,VR="[object Boolean]",BR="[object Date]",UR="[object Error]",jR="[object Map]",qR="[object Number]",HR="[object RegExp]",zR="[object Set]",WR="[object String]",KR="[object Symbol]",GR="[object ArrayBuffer]",JR="[object DataView]",oy=Qr?Qr.prototype:void 0,Xu=oy?oy.valueOf:void 0;function XR(t,e,r,n,s,i,o){switch(r){case JR:if(t.byteLength!=e.byteLength||t.byteOffset!=e.byteOffset)return!1;t=t.buffer,e=e.buffer;case GR:return!(t.byteLength!=e.byteLength||!i(new lc(t),new lc(e)));case VR:case BR:case qR:return Sp(+t,+e);case UR:return t.name==e.name&&t.message==e.message;case HR:case WR:return t==e+"";case jR:var a=LR;case zR:var l=n&MR;if(a||(a=FR),t.size!=e.size&&!l)return!1;var u=o.get(t);if(u)return u==e;n|=DR,o.set(t,e);var c=q0(a(t),a(e),n,s,i,o);return o.delete(t),c;case KR:if(Xu)return Xu.call(t)==Xu.call(e)}return!1}var YR=1,QR=Object.prototype,ZR=QR.hasOwnProperty;function eN(t,e,r,n,s,i){var o=r&YR,a=qh(t),l=a.length,u=qh(e),c=u.length;if(l!=c&&!o)return!1;for(var d=l;d--;){var p=a[d];if(!(o?p in e:ZR.call(e,p)))return!1}var f=i.get(t),h=i.get(e);if(f&&h)return f==e&&h==t;var m=!0;i.set(t,e),i.set(e,t);for(var g=o;++d<l;){p=a[d];var v=t[p],b=e[p];if(n)var w=o?n(b,v,p,e,t,i):n(v,b,p,t,e,i);if(!(w===void 0?v===b||s(v,b,r,n,i):w)){m=!1;break}g||(g=p=="constructor")}if(m&&!g){var _=t.constructor,S=e.constructor;_!=S&&"constructor"in t&&"constructor"in e&&!(typeof _=="function"&&_ instanceof _&&typeof S=="function"&&S instanceof S)&&(m=!1)}return i.delete(t),i.delete(e),m}var tN=1,ay="[object Arguments]",ly="[object Array]",rl="[object Object]",rN=Object.prototype,cy=rN.hasOwnProperty;function nN(t,e,r,n,s,i){var o=Cn(t),a=Cn(e),l=o?ly:Nr(t),u=a?ly:Nr(e);l=l==ay?rl:l,u=u==ay?rl:u;var c=l==rl,d=u==rl,p=l==u;if(p&&ac(t)){if(!ac(e))return!1;o=!0,c=!1}if(p&&!c)return i||(i=new vn),o||F0(t)?q0(t,e,r,n,s,i):XR(t,e,l,r,n,s,i);if(!(r&tN)){var f=c&&cy.call(t,"__wrapped__"),h=d&&cy.call(e,"__wrapped__");if(f||h){var m=f?t.value():t,g=h?e.value():e;return i||(i=new vn),s(m,g,r,n,i)}}return p?(i||(i=new vn),eN(t,e,r,n,s,i)):!1}function H0(t,e,r,n,s){return t===e?!0:t==null||e==null||!ss(t)&&!ss(e)?t!==t&&e!==e:nN(t,e,r,n,H0,s)}function sN(t,e,r){e=$p(e,t);for(var n=-1,s=e.length,i=!1;++n<s;){var o=Tp(e[n]);if(!(i=t!=null&&r(t,o)))break;t=t[o]}return i||++n!=s?i:(s=t==null?0:t.length,!!s&&xp(s)&&Ep(o,s)&&(Cn(t)||N0(t)))}var iN={"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"},oN=tO(iN),z0=/[&<>"']/g,aN=RegExp(z0.source);function lN(t){return t=D0(t),t&&aN.test(t)?t.replace(z0,oN):t}var cN=Object.prototype,uN=cN.hasOwnProperty;function dN(t,e){return t!=null&&uN.call(t,e)}function hN(t,e){return t!=null&&sN(t,e,dN)}function fN(t,e){return H0(t,e)}function pN(t,e,r,n){if(!Ni(t))return t;e=$p(e,t);for(var s=-1,i=e.length,o=i-1,a=t;a!=null&&++s<i;){var l=Tp(e[s]),u=r;if(l==="__proto__"||l==="constructor"||l==="prototype")return t;if(s!=o){var c=a[l];u=void 0,u===void 0&&(u=Ni(c)?c:Ep(e[s+1])?[]:{})}P0(a,l,u),a=a[l]}return t}function Es(t,e,r){return t==null?t:pN(t,e,r)}var uy=typeof globalThis<"u"?globalThis:typeof window<"u"?window:typeof global<"u"?global:typeof self<"u"?self:{};function mN(t){if(Object.prototype.hasOwnProperty.call(t,"__esModule"))return t;var e=t.default;if(typeof e=="function"){var r=function n(){var s=!1;try{s=this instanceof n}catch{}return s?Reflect.construct(e,arguments,this.constructor):e.apply(this,arguments)};r.prototype=e.prototype}else r={};return Object.defineProperty(r,"__esModule",{value:!0}),Object.keys(t).forEach(function(n){var s=Object.getOwnPropertyDescriptor(t,n);Object.defineProperty(r,n,s.get?s:{enumerable:!0,get:function(){return t[n]}})}),r}var Yu,dy;function Xi(){return dy||(dy=1,Yu=TypeError),Yu}const gN={},yN=Object.freeze(Object.defineProperty({__proto__:null,default:gN},Symbol.toStringTag,{value:"Module"})),bN=mN(yN);var Qu,hy;function Zc(){if(hy)return Qu;hy=1;var t=typeof Map=="function"&&Map.prototype,e=Object.getOwnPropertyDescriptor&&t?Object.getOwnPropertyDescriptor(Map.prototype,"size"):null,r=t&&e&&typeof e.get=="function"?e.get:null,n=t&&Map.prototype.forEach,s=typeof Set=="function"&&Set.prototype,i=Object.getOwnPropertyDescriptor&&s?Object.getOwnPropertyDescriptor(Set.prototype,"size"):null,o=s&&i&&typeof i.get=="function"?i.get:null,a=s&&Set.prototype.forEach,l=typeof WeakMap=="function"&&WeakMap.prototype,u=l?WeakMap.prototype.has:null,c=typeof WeakSet=="function"&&WeakSet.prototype,d=c?WeakSet.prototype.has:null,p=typeof WeakRef=="function"&&WeakRef.prototype,f=p?WeakRef.prototype.deref:null,h=Boolean.prototype.valueOf,m=Object.prototype.toString,g=Function.prototype.toString,v=String.prototype.match,b=String.prototype.slice,w=String.prototype.replace,_=String.prototype.toUpperCase,S=String.prototype.toLowerCase,k=RegExp.prototype.test,O=Array.prototype.concat,R=Array.prototype.join,C=Array.prototype.slice,A=Math.floor,j=typeof BigInt=="function"?BigInt.prototype.valueOf:null,$=Object.getOwnPropertySymbols,y=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?Symbol.prototype.toString:null,I=typeof Symbol=="function"&&typeof Symbol.iterator=="object",q=typeof Symbol=="function"&&Symbol.toStringTag&&(typeof Symbol.toStringTag===I||!0)?Symbol.toStringTag:null,D=Object.prototype.propertyIsEnumerable,z=(typeof Reflect=="function"?Reflect.getPrototypeOf:Object.getPrototypeOf)||([].__proto__===Array.prototype?function(N){return N.__proto__}:null);function P(N,L){if(N===1/0||N===-1/0||N!==N||N&&N>-1e3&&N<1e3||k.call(/e/,L))return L;var Ae=/[0-9](?=(?:[0-9]{3})+(?![0-9]))/g;if(typeof N=="number"){var Le=N<0?-A(-N):A(N);if(Le!==N){var He=String(Le),ge=b.call(L,He.length+1);return w.call(He,Ae,"$&_")+"."+w.call(w.call(ge,/([0-9]{3})/g,"$&_"),/_$/,"")}}return w.call(L,Ae,"$&_")}var ee=bN,ye=ee.custom,re=T(ye)?ye:null,_e={__proto__:null,double:'"',single:"'"},at={__proto__:null,double:/(["\\])/g,single:/(['\\])/g};Qu=function N(L,Ae,Le,He){var ge=Ae||{};if(H(ge,"quoteStyle")&&!H(_e,ge.quoteStyle))throw new TypeError('option "quoteStyle" must be "single" or "double"');if(H(ge,"maxStringLength")&&(typeof ge.maxStringLength=="number"?ge.maxStringLength<0&&ge.maxStringLength!==1/0:ge.maxStringLength!==null))throw new TypeError('option "maxStringLength", if provided, must be a positive integer, Infinity, or `null`');var Rn=H(ge,"customInspect")?ge.customInspect:!0;if(typeof Rn!="boolean"&&Rn!=="symbol")throw new TypeError("option \"customInspect\", if provided, must be `true`, `false`, or `'symbol'`");if(H(ge,"indent")&&ge.indent!==null&&ge.indent!=="	"&&!(parseInt(ge.indent,10)===ge.indent&&ge.indent>0))throw new TypeError('option "indent" must be "\\t", an integer > 0, or `null`');if(H(ge,"numericSeparator")&&typeof ge.numericSeparator!="boolean")throw new TypeError('option "numericSeparator", if provided, must be `true` or `false`');var ds=ge.numericSeparator;if(typeof L>"u")return"undefined";if(L===null)return"null";if(typeof L=="boolean")return L?"true":"false";if(typeof L=="string")return be(L,ge);if(typeof L=="number"){if(L===0)return 1/0/L>0?"0":"-0";var dr=String(L);return ds?P(L,dr):dr}if(typeof L=="bigint"){var Nn=String(L)+"n";return ds?P(L,Nn):Nn}var Tu=typeof ge.depth>"u"?5:ge.depth;if(typeof Le>"u"&&(Le=0),Le>=Tu&&Tu>0&&typeof L=="object")return cr(L)?"[Array]":"[Object]";var ti=Ut(ge,Le);if(typeof He>"u")He=[];else if(X(He,L)>=0)return"[Circular]";function Pr(ri,Ua,QS){if(Ua&&(He=C.call(He),He.push(Ua)),QS){var Tm={depth:ge.depth};return H(ge,"quoteStyle")&&(Tm.quoteStyle=ge.quoteStyle),N(ri,Tm,Le+1,He)}return N(ri,ge,Le+1,He)}if(typeof L=="function"&&!it(L)){var _m=Q(L),Em=us(L,Pr);return"[Function"+(_m?": "+_m:" (anonymous)")+"]"+(Em.length>0?" { "+R.call(Em,", ")+" }":"")}if(T(L)){var Sm=I?w.call(String(L),/^(Symbol\(.*\))_[^)]*$/,"$1"):y.call(L);return typeof L=="object"&&!I?xe(Sm):Sm}if(ce(L)){for(var ao="<"+S.call(String(L.nodeName)),ku=L.attributes||[],Ba=0;Ba<ku.length;Ba++)ao+=" "+ku[Ba].name+"="+st(_t(ku[Ba].value),"double",ge);return ao+=">",L.childNodes&&L.childNodes.length&&(ao+="..."),ao+="</"+S.call(String(L.nodeName))+">",ao}if(cr(L)){if(L.length===0)return"[]";var Pu=us(L,Pr);return ti&&!ur(Pu)?"["+on(Pu,ti)+"]":"[ "+R.call(Pu,", ")+" ]"}if(me(L)){var Ou=us(L,Pr);return!("cause"in Error.prototype)&&"cause"in L&&!D.call(L,"cause")?"{ ["+String(L)+"] "+R.call(O.call("[cause]: "+Pr(L.cause),Ou),", ")+" }":Ou.length===0?"["+String(L)+"]":"{ ["+String(L)+"] "+R.call(Ou,", ")+" }"}if(typeof L=="object"&&Rn){if(re&&typeof L[re]=="function"&&ee)return ee(L,{depth:Tu-Le});if(Rn!=="symbol"&&typeof L.inspect=="function")return L.inspect()}if(J(L)){var xm=[];return n&&n.call(L,function(ri,Ua){xm.push(Pr(Ua,L,!0)+" => "+Pr(ri,L))}),lt("Map",r.call(L),xm,ti)}if(Z(L)){var Cm=[];return a&&a.call(L,function(ri){Cm.push(Pr(ri,L))}),lt("Set",o.call(L),Cm,ti)}if(G(L))return yt("WeakMap");if(se(L))return yt("WeakSet");if(ne(L))return yt("WeakRef");if(Re(L))return xe(Pr(Number(L)));if(B(L))return xe(Pr(j.call(L)));if(E(L))return xe(h.call(L));if(qe(L))return xe(Pr(String(L)));if(typeof window<"u"&&L===window)return"{ [object Window] }";if(typeof globalThis<"u"&&L===globalThis||typeof uy<"u"&&L===uy)return"{ [object globalThis] }";if(!_r(L)&&!it(L)){var Ru=us(L,Pr),Am=z?z(L)===Object.prototype:L instanceof Object||L.constructor===Object,Nu=L instanceof Object?"":"null prototype",$m=!Am&&q&&Object(L)===L&&q in L?b.call(W(L),8,-1):Nu?"Object":"",YS=Am||typeof L.constructor!="function"?"":L.constructor.name?L.constructor.name+" ":"",Iu=YS+($m||Nu?"["+R.call(O.call([],$m||[],Nu||[]),": ")+"] ":"");return Ru.length===0?Iu+"{}":ti?Iu+"{"+on(Ru,ti)+"}":Iu+"{ "+R.call(Ru,", ")+" }"}return String(L)};function st(N,L,Ae){var Le=Ae.quoteStyle||L,He=_e[Le];return He+N+He}function _t(N){return w.call(String(N),/"/g,"&quot;")}function gt(N){return!q||!(typeof N=="object"&&(q in N||typeof N[q]<"u"))}function cr(N){return W(N)==="[object Array]"&&gt(N)}function _r(N){return W(N)==="[object Date]"&&gt(N)}function it(N){return W(N)==="[object RegExp]"&&gt(N)}function me(N){return W(N)==="[object Error]"&&gt(N)}function qe(N){return W(N)==="[object String]"&&gt(N)}function Re(N){return W(N)==="[object Number]"&&gt(N)}function E(N){return W(N)==="[object Boolean]"&&gt(N)}function T(N){if(I)return N&&typeof N=="object"&&N instanceof Symbol;if(typeof N=="symbol")return!0;if(!N||typeof N!="object"||!y)return!1;try{return y.call(N),!0}catch{}return!1}function B(N){if(!N||typeof N!="object"||!j)return!1;try{return j.call(N),!0}catch{}return!1}var K=Object.prototype.hasOwnProperty||function(N){return N in this};function H(N,L){return K.call(N,L)}function W(N){return m.call(N)}function Q(N){if(N.name)return N.name;var L=v.call(g.call(N),/^function\s*([\w$]+)/);return L?L[1]:null}function X(N,L){if(N.indexOf)return N.indexOf(L);for(var Ae=0,Le=N.length;Ae<Le;Ae++)if(N[Ae]===L)return Ae;return-1}function J(N){if(!r||!N||typeof N!="object")return!1;try{r.call(N);try{o.call(N)}catch{return!0}return N instanceof Map}catch{}return!1}function G(N){if(!u||!N||typeof N!="object")return!1;try{u.call(N,u);try{d.call(N,d)}catch{return!0}return N instanceof WeakMap}catch{}return!1}function ne(N){if(!f||!N||typeof N!="object")return!1;try{return f.call(N),!0}catch{}return!1}function Z(N){if(!o||!N||typeof N!="object")return!1;try{o.call(N);try{r.call(N)}catch{return!0}return N instanceof Set}catch{}return!1}function se(N){if(!d||!N||typeof N!="object")return!1;try{d.call(N,d);try{u.call(N,u)}catch{return!0}return N instanceof WeakSet}catch{}return!1}function ce(N){return!N||typeof N!="object"?!1:typeof HTMLElement<"u"&&N instanceof HTMLElement?!0:typeof N.nodeName=="string"&&typeof N.getAttribute=="function"}function be(N,L){if(N.length>L.maxStringLength){var Ae=N.length-L.maxStringLength,Le="... "+Ae+" more character"+(Ae>1?"s":"");return be(b.call(N,0,L.maxStringLength),L)+Le}var He=at[L.quoteStyle||"single"];He.lastIndex=0;var ge=w.call(w.call(N,He,"\\$1"),/[\x00-\x1f]/g,De);return st(ge,"single",L)}function De(N){var L=N.charCodeAt(0),Ae={8:"b",9:"t",10:"n",12:"f",13:"r"}[L];return Ae?"\\"+Ae:"\\x"+(L<16?"0":"")+_.call(L.toString(16))}function xe(N){return"Object("+N+")"}function yt(N){return N+" { ? }"}function lt(N,L,Ae,Le){var He=Le?on(Ae,Le):R.call(Ae,", ");return N+" ("+L+") {"+He+"}"}function ur(N){for(var L=0;L<N.length;L++)if(X(N[L],`
`)>=0)return!1;return!0}function Ut(N,L){var Ae;if(N.indent==="	")Ae="	";else if(typeof N.indent=="number"&&N.indent>0)Ae=R.call(Array(N.indent+1)," ");else return null;return{base:Ae,prev:R.call(Array(L+1),Ae)}}function on(N,L){if(N.length===0)return"";var Ae=`
`+L.prev+L.base;return Ae+R.call(N,","+Ae)+`
`+L.prev}function us(N,L){var Ae=cr(N),Le=[];if(Ae){Le.length=N.length;for(var He=0;He<N.length;He++)Le[He]=H(N,He)?L(N[He],N):""}var ge=typeof $=="function"?$(N):[],Rn;if(I){Rn={};for(var ds=0;ds<ge.length;ds++)Rn["$"+ge[ds]]=ge[ds]}for(var dr in N)H(N,dr)&&(Ae&&String(Number(dr))===dr&&dr<N.length||I&&Rn["$"+dr]instanceof Symbol||(k.call(/[^\w$]/,dr)?Le.push(L(dr,N)+": "+L(N[dr],N)):Le.push(dr+": "+L(N[dr],N))));if(typeof $=="function")for(var Nn=0;Nn<ge.length;Nn++)D.call(N,ge[Nn])&&Le.push("["+L(ge[Nn])+"]: "+L(N[ge[Nn]],N));return Le}return Qu}var Zu,fy;function vN(){if(fy)return Zu;fy=1;var t=Zc(),e=Xi(),r=function(a,l,u){for(var c=a,d;(d=c.next)!=null;c=d)if(d.key===l)return c.next=d.next,u||(d.next=a.next,a.next=d),d},n=function(a,l){if(a){var u=r(a,l);return u&&u.value}},s=function(a,l,u){var c=r(a,l);c?c.value=u:a.next={key:l,next:a.next,value:u}},i=function(a,l){return a?!!r(a,l):!1},o=function(a,l){if(a)return r(a,l,!0)};return Zu=function(){var l,u={assert:function(c){if(!u.has(c))throw new e("Side channel does not contain "+t(c))},delete:function(c){var d=l&&l.next,p=o(l,c);return p&&d&&d===p&&(l=void 0),!!p},get:function(c){return n(l,c)},has:function(c){return i(l,c)},set:function(c,d){l||(l={next:void 0}),s(l,c,d)}};return u},Zu}var ed,py;function W0(){return py||(py=1,ed=Object),ed}var td,my;function wN(){return my||(my=1,td=Error),td}var rd,gy;function _N(){return gy||(gy=1,rd=EvalError),rd}var nd,yy;function EN(){return yy||(yy=1,nd=RangeError),nd}var sd,by;function SN(){return by||(by=1,sd=ReferenceError),sd}var id,vy;function xN(){return vy||(vy=1,id=SyntaxError),id}var od,wy;function CN(){return wy||(wy=1,od=URIError),od}var ad,_y;function AN(){return _y||(_y=1,ad=Math.abs),ad}var ld,Ey;function $N(){return Ey||(Ey=1,ld=Math.floor),ld}var cd,Sy;function TN(){return Sy||(Sy=1,cd=Math.max),cd}var ud,xy;function kN(){return xy||(xy=1,ud=Math.min),ud}var dd,Cy;function PN(){return Cy||(Cy=1,dd=Math.pow),dd}var hd,Ay;function ON(){return Ay||(Ay=1,hd=Math.round),hd}var fd,$y;function RN(){return $y||($y=1,fd=Number.isNaN||function(e){return e!==e}),fd}var pd,Ty;function NN(){if(Ty)return pd;Ty=1;var t=RN();return pd=function(r){return t(r)||r===0?r:r<0?-1:1},pd}var md,ky;function IN(){return ky||(ky=1,md=Object.getOwnPropertyDescriptor),md}var gd,Py;function K0(){if(Py)return gd;Py=1;var t=IN();if(t)try{t([],"length")}catch{t=null}return gd=t,gd}var yd,Oy;function LN(){if(Oy)return yd;Oy=1;var t=Object.defineProperty||!1;if(t)try{t({},"a",{value:1})}catch{t=!1}return yd=t,yd}var bd,Ry;function FN(){return Ry||(Ry=1,bd=function(){if(typeof Symbol!="function"||typeof Object.getOwnPropertySymbols!="function")return!1;if(typeof Symbol.iterator=="symbol")return!0;var e={},r=Symbol("test"),n=Object(r);if(typeof r=="string"||Object.prototype.toString.call(r)!=="[object Symbol]"||Object.prototype.toString.call(n)!=="[object Symbol]")return!1;var s=42;e[r]=s;for(var i in e)return!1;if(typeof Object.keys=="function"&&Object.keys(e).length!==0||typeof Object.getOwnPropertyNames=="function"&&Object.getOwnPropertyNames(e).length!==0)return!1;var o=Object.getOwnPropertySymbols(e);if(o.length!==1||o[0]!==r||!Object.prototype.propertyIsEnumerable.call(e,r))return!1;if(typeof Object.getOwnPropertyDescriptor=="function"){var a=Object.getOwnPropertyDescriptor(e,r);if(a.value!==s||a.enumerable!==!0)return!1}return!0}),bd}var vd,Ny;function MN(){if(Ny)return vd;Ny=1;var t=typeof Symbol<"u"&&Symbol,e=FN();return vd=function(){return typeof t!="function"||typeof Symbol!="function"||typeof t("foo")!="symbol"||typeof Symbol("bar")!="symbol"?!1:e()},vd}var wd,Iy;function G0(){return Iy||(Iy=1,wd=typeof Reflect<"u"&&Reflect.getPrototypeOf||null),wd}var _d,Ly;function J0(){if(Ly)return _d;Ly=1;var t=W0();return _d=t.getPrototypeOf||null,_d}var Ed,Fy;function DN(){if(Fy)return Ed;Fy=1;var t="Function.prototype.bind called on incompatible ",e=Object.prototype.toString,r=Math.max,n="[object Function]",s=function(l,u){for(var c=[],d=0;d<l.length;d+=1)c[d]=l[d];for(var p=0;p<u.length;p+=1)c[p+l.length]=u[p];return c},i=function(l,u){for(var c=[],d=u,p=0;d<l.length;d+=1,p+=1)c[p]=l[d];return c},o=function(a,l){for(var u="",c=0;c<a.length;c+=1)u+=a[c],c+1<a.length&&(u+=l);return u};return Ed=function(l){var u=this;if(typeof u!="function"||e.apply(u)!==n)throw new TypeError(t+u);for(var c=i(arguments,1),d,p=function(){if(this instanceof d){var v=u.apply(this,s(c,arguments));return Object(v)===v?v:this}return u.apply(l,s(c,arguments))},f=r(0,u.length-c.length),h=[],m=0;m<f;m++)h[m]="$"+m;if(d=Function("binder","return function ("+o(h,",")+"){ return binder.apply(this,arguments); }")(p),u.prototype){var g=function(){};g.prototype=u.prototype,d.prototype=new g,g.prototype=null}return d},Ed}var Sd,My;function eu(){if(My)return Sd;My=1;var t=DN();return Sd=Function.prototype.bind||t,Sd}var xd,Dy;function Pp(){return Dy||(Dy=1,xd=Function.prototype.call),xd}var Cd,Vy;function X0(){return Vy||(Vy=1,Cd=Function.prototype.apply),Cd}var Ad,By;function VN(){return By||(By=1,Ad=typeof Reflect<"u"&&Reflect&&Reflect.apply),Ad}var $d,Uy;function BN(){if(Uy)return $d;Uy=1;var t=eu(),e=X0(),r=Pp(),n=VN();return $d=n||t.call(r,e),$d}var Td,jy;function Y0(){if(jy)return Td;jy=1;var t=eu(),e=Xi(),r=Pp(),n=BN();return Td=function(i){if(i.length<1||typeof i[0]!="function")throw new e("a function is required");return n(t,r,i)},Td}var kd,qy;function UN(){if(qy)return kd;qy=1;var t=Y0(),e=K0(),r;try{r=[].__proto__===Array.prototype}catch(o){if(!o||typeof o!="object"||!("code"in o)||o.code!=="ERR_PROTO_ACCESS")throw o}var n=!!r&&e&&e(Object.prototype,"__proto__"),s=Object,i=s.getPrototypeOf;return kd=n&&typeof n.get=="function"?t([n.get]):typeof i=="function"?function(a){return i(a==null?a:s(a))}:!1,kd}var Pd,Hy;function jN(){if(Hy)return Pd;Hy=1;var t=G0(),e=J0(),r=UN();return Pd=t?function(s){return t(s)}:e?function(s){if(!s||typeof s!="object"&&typeof s!="function")throw new TypeError("getProto: not an object");return e(s)}:r?function(s){return r(s)}:null,Pd}var Od,zy;function qN(){if(zy)return Od;zy=1;var t=Function.prototype.call,e=Object.prototype.hasOwnProperty,r=eu();return Od=r.call(t,e),Od}var Rd,Wy;function Op(){if(Wy)return Rd;Wy=1;var t,e=W0(),r=wN(),n=_N(),s=EN(),i=SN(),o=xN(),a=Xi(),l=CN(),u=AN(),c=$N(),d=TN(),p=kN(),f=PN(),h=ON(),m=NN(),g=Function,v=function(it){try{return g('"use strict"; return ('+it+").constructor;")()}catch{}},b=K0(),w=LN(),_=function(){throw new a},S=b?(function(){try{return arguments.callee,_}catch{try{return b(arguments,"callee").get}catch{return _}}})():_,k=MN()(),O=jN(),R=J0(),C=G0(),A=X0(),j=Pp(),$={},y=typeof Uint8Array>"u"||!O?t:O(Uint8Array),I={__proto__:null,"%AggregateError%":typeof AggregateError>"u"?t:AggregateError,"%Array%":Array,"%ArrayBuffer%":typeof ArrayBuffer>"u"?t:ArrayBuffer,"%ArrayIteratorPrototype%":k&&O?O([][Symbol.iterator]()):t,"%AsyncFromSyncIteratorPrototype%":t,"%AsyncFunction%":$,"%AsyncGenerator%":$,"%AsyncGeneratorFunction%":$,"%AsyncIteratorPrototype%":$,"%Atomics%":typeof Atomics>"u"?t:Atomics,"%BigInt%":typeof BigInt>"u"?t:BigInt,"%BigInt64Array%":typeof BigInt64Array>"u"?t:BigInt64Array,"%BigUint64Array%":typeof BigUint64Array>"u"?t:BigUint64Array,"%Boolean%":Boolean,"%DataView%":typeof DataView>"u"?t:DataView,"%Date%":Date,"%decodeURI%":decodeURI,"%decodeURIComponent%":decodeURIComponent,"%encodeURI%":encodeURI,"%encodeURIComponent%":encodeURIComponent,"%Error%":r,"%eval%":eval,"%EvalError%":n,"%Float16Array%":typeof Float16Array>"u"?t:Float16Array,"%Float32Array%":typeof Float32Array>"u"?t:Float32Array,"%Float64Array%":typeof Float64Array>"u"?t:Float64Array,"%FinalizationRegistry%":typeof FinalizationRegistry>"u"?t:FinalizationRegistry,"%Function%":g,"%GeneratorFunction%":$,"%Int8Array%":typeof Int8Array>"u"?t:Int8Array,"%Int16Array%":typeof Int16Array>"u"?t:Int16Array,"%Int32Array%":typeof Int32Array>"u"?t:Int32Array,"%isFinite%":isFinite,"%isNaN%":isNaN,"%IteratorPrototype%":k&&O?O(O([][Symbol.iterator]())):t,"%JSON%":typeof JSON=="object"?JSON:t,"%Map%":typeof Map>"u"?t:Map,"%MapIteratorPrototype%":typeof Map>"u"||!k||!O?t:O(new Map()[Symbol.iterator]()),"%Math%":Math,"%Number%":Number,"%Object%":e,"%Object.getOwnPropertyDescriptor%":b,"%parseFloat%":parseFloat,"%parseInt%":parseInt,"%Promise%":typeof Promise>"u"?t:Promise,"%Proxy%":typeof Proxy>"u"?t:Proxy,"%RangeError%":s,"%ReferenceError%":i,"%Reflect%":typeof Reflect>"u"?t:Reflect,"%RegExp%":RegExp,"%Set%":typeof Set>"u"?t:Set,"%SetIteratorPrototype%":typeof Set>"u"||!k||!O?t:O(new Set()[Symbol.iterator]()),"%SharedArrayBuffer%":typeof SharedArrayBuffer>"u"?t:SharedArrayBuffer,"%String%":String,"%StringIteratorPrototype%":k&&O?O(""[Symbol.iterator]()):t,"%Symbol%":k?Symbol:t,"%SyntaxError%":o,"%ThrowTypeError%":S,"%TypedArray%":y,"%TypeError%":a,"%Uint8Array%":typeof Uint8Array>"u"?t:Uint8Array,"%Uint8ClampedArray%":typeof Uint8ClampedArray>"u"?t:Uint8ClampedArray,"%Uint16Array%":typeof Uint16Array>"u"?t:Uint16Array,"%Uint32Array%":typeof Uint32Array>"u"?t:Uint32Array,"%URIError%":l,"%WeakMap%":typeof WeakMap>"u"?t:WeakMap,"%WeakRef%":typeof WeakRef>"u"?t:WeakRef,"%WeakSet%":typeof WeakSet>"u"?t:WeakSet,"%Function.prototype.call%":j,"%Function.prototype.apply%":A,"%Object.defineProperty%":w,"%Object.getPrototypeOf%":R,"%Math.abs%":u,"%Math.floor%":c,"%Math.max%":d,"%Math.min%":p,"%Math.pow%":f,"%Math.round%":h,"%Math.sign%":m,"%Reflect.getPrototypeOf%":C};if(O)try{null.error}catch(it){var q=O(O(it));I["%Error.prototype%"]=q}var D=function it(me){var qe;if(me==="%AsyncFunction%")qe=v("async function () {}");else if(me==="%GeneratorFunction%")qe=v("function* () {}");else if(me==="%AsyncGeneratorFunction%")qe=v("async function* () {}");else if(me==="%AsyncGenerator%"){var Re=it("%AsyncGeneratorFunction%");Re&&(qe=Re.prototype)}else if(me==="%AsyncIteratorPrototype%"){var E=it("%AsyncGenerator%");E&&O&&(qe=O(E.prototype))}return I[me]=qe,qe},z={__proto__:null,"%ArrayBufferPrototype%":["ArrayBuffer","prototype"],"%ArrayPrototype%":["Array","prototype"],"%ArrayProto_entries%":["Array","prototype","entries"],"%ArrayProto_forEach%":["Array","prototype","forEach"],"%ArrayProto_keys%":["Array","prototype","keys"],"%ArrayProto_values%":["Array","prototype","values"],"%AsyncFunctionPrototype%":["AsyncFunction","prototype"],"%AsyncGenerator%":["AsyncGeneratorFunction","prototype"],"%AsyncGeneratorPrototype%":["AsyncGeneratorFunction","prototype","prototype"],"%BooleanPrototype%":["Boolean","prototype"],"%DataViewPrototype%":["DataView","prototype"],"%DatePrototype%":["Date","prototype"],"%ErrorPrototype%":["Error","prototype"],"%EvalErrorPrototype%":["EvalError","prototype"],"%Float32ArrayPrototype%":["Float32Array","prototype"],"%Float64ArrayPrototype%":["Float64Array","prototype"],"%FunctionPrototype%":["Function","prototype"],"%Generator%":["GeneratorFunction","prototype"],"%GeneratorPrototype%":["GeneratorFunction","prototype","prototype"],"%Int8ArrayPrototype%":["Int8Array","prototype"],"%Int16ArrayPrototype%":["Int16Array","prototype"],"%Int32ArrayPrototype%":["Int32Array","prototype"],"%JSONParse%":["JSON","parse"],"%JSONStringify%":["JSON","stringify"],"%MapPrototype%":["Map","prototype"],"%NumberPrototype%":["Number","prototype"],"%ObjectPrototype%":["Object","prototype"],"%ObjProto_toString%":["Object","prototype","toString"],"%ObjProto_valueOf%":["Object","prototype","valueOf"],"%PromisePrototype%":["Promise","prototype"],"%PromiseProto_then%":["Promise","prototype","then"],"%Promise_all%":["Promise","all"],"%Promise_reject%":["Promise","reject"],"%Promise_resolve%":["Promise","resolve"],"%RangeErrorPrototype%":["RangeError","prototype"],"%ReferenceErrorPrototype%":["ReferenceError","prototype"],"%RegExpPrototype%":["RegExp","prototype"],"%SetPrototype%":["Set","prototype"],"%SharedArrayBufferPrototype%":["SharedArrayBuffer","prototype"],"%StringPrototype%":["String","prototype"],"%SymbolPrototype%":["Symbol","prototype"],"%SyntaxErrorPrototype%":["SyntaxError","prototype"],"%TypedArrayPrototype%":["TypedArray","prototype"],"%TypeErrorPrototype%":["TypeError","prototype"],"%Uint8ArrayPrototype%":["Uint8Array","prototype"],"%Uint8ClampedArrayPrototype%":["Uint8ClampedArray","prototype"],"%Uint16ArrayPrototype%":["Uint16Array","prototype"],"%Uint32ArrayPrototype%":["Uint32Array","prototype"],"%URIErrorPrototype%":["URIError","prototype"],"%WeakMapPrototype%":["WeakMap","prototype"],"%WeakSetPrototype%":["WeakSet","prototype"]},P=eu(),ee=qN(),ye=P.call(j,Array.prototype.concat),re=P.call(A,Array.prototype.splice),_e=P.call(j,String.prototype.replace),at=P.call(j,String.prototype.slice),st=P.call(j,RegExp.prototype.exec),_t=/[^%.[\]]+|\[(?:(-?\d+(?:\.\d+)?)|(["'])((?:(?!\2)[^\\]|\\.)*?)\2)\]|(?=(?:\.|\[\])(?:\.|\[\]|%$))/g,gt=/\\(\\)?/g,cr=function(me){var qe=at(me,0,1),Re=at(me,-1);if(qe==="%"&&Re!=="%")throw new o("invalid intrinsic syntax, expected closing `%`");if(Re==="%"&&qe!=="%")throw new o("invalid intrinsic syntax, expected opening `%`");var E=[];return _e(me,_t,function(T,B,K,H){E[E.length]=K?_e(H,gt,"$1"):B||T}),E},_r=function(me,qe){var Re=me,E;if(ee(z,Re)&&(E=z[Re],Re="%"+E[0]+"%"),ee(I,Re)){var T=I[Re];if(T===$&&(T=D(Re)),typeof T>"u"&&!qe)throw new a("intrinsic "+me+" exists, but is not available. Please file an issue!");return{alias:E,name:Re,value:T}}throw new o("intrinsic "+me+" does not exist!")};return Rd=function(me,qe){if(typeof me!="string"||me.length===0)throw new a("intrinsic name must be a non-empty string");if(arguments.length>1&&typeof qe!="boolean")throw new a('"allowMissing" argument must be a boolean');if(st(/^%?[^%]*%?$/,me)===null)throw new o("`%` may not be present anywhere but at the beginning and end of the intrinsic name");var Re=cr(me),E=Re.length>0?Re[0]:"",T=_r("%"+E+"%",qe),B=T.name,K=T.value,H=!1,W=T.alias;W&&(E=W[0],re(Re,ye([0,1],W)));for(var Q=1,X=!0;Q<Re.length;Q+=1){var J=Re[Q],G=at(J,0,1),ne=at(J,-1);if((G==='"'||G==="'"||G==="`"||ne==='"'||ne==="'"||ne==="`")&&G!==ne)throw new o("property names with quotes must have matching quotes");if((J==="constructor"||!X)&&(H=!0),E+="."+J,B="%"+E+"%",ee(I,B))K=I[B];else if(K!=null){if(!(J in K)){if(!qe)throw new a("base intrinsic for "+me+" exists, but the property is not available.");return}if(b&&Q+1>=Re.length){var Z=b(K,J);X=!!Z,X&&"get"in Z&&!("originalValue"in Z.get)?K=Z.get:K=K[J]}else X=ee(K,J),K=K[J];X&&!H&&(I[B]=K)}}return K},Rd}var Nd,Ky;function Q0(){if(Ky)return Nd;Ky=1;var t=Op(),e=Y0(),r=e([t("%String.prototype.indexOf%")]);return Nd=function(s,i){var o=t(s,!!i);return typeof o=="function"&&r(s,".prototype.")>-1?e([o]):o},Nd}var Id,Gy;function Z0(){if(Gy)return Id;Gy=1;var t=Op(),e=Q0(),r=Zc(),n=Xi(),s=t("%Map%",!0),i=e("Map.prototype.get",!0),o=e("Map.prototype.set",!0),a=e("Map.prototype.has",!0),l=e("Map.prototype.delete",!0),u=e("Map.prototype.size",!0);return Id=!!s&&function(){var d,p={assert:function(f){if(!p.has(f))throw new n("Side channel does not contain "+r(f))},delete:function(f){if(d){var h=l(d,f);return u(d)===0&&(d=void 0),h}return!1},get:function(f){if(d)return i(d,f)},has:function(f){return d?a(d,f):!1},set:function(f,h){d||(d=new s),o(d,f,h)}};return p},Id}var Ld,Jy;function HN(){if(Jy)return Ld;Jy=1;var t=Op(),e=Q0(),r=Zc(),n=Z0(),s=Xi(),i=t("%WeakMap%",!0),o=e("WeakMap.prototype.get",!0),a=e("WeakMap.prototype.set",!0),l=e("WeakMap.prototype.has",!0),u=e("WeakMap.prototype.delete",!0);return Ld=i?function(){var d,p,f={assert:function(h){if(!f.has(h))throw new s("Side channel does not contain "+r(h))},delete:function(h){if(i&&h&&(typeof h=="object"||typeof h=="function")){if(d)return u(d,h)}else if(n&&p)return p.delete(h);return!1},get:function(h){return i&&h&&(typeof h=="object"||typeof h=="function")&&d?o(d,h):p&&p.get(h)},has:function(h){return i&&h&&(typeof h=="object"||typeof h=="function")&&d?l(d,h):!!p&&p.has(h)},set:function(h,m){i&&h&&(typeof h=="object"||typeof h=="function")?(d||(d=new i),a(d,h,m)):n&&(p||(p=n()),p.set(h,m))}};return f}:n,Ld}var Fd,Xy;function zN(){if(Xy)return Fd;Xy=1;var t=Xi(),e=Zc(),r=vN(),n=Z0(),s=HN(),i=s||n||r;return Fd=function(){var a,l={assert:function(u){if(!l.has(u))throw new t("Side channel does not contain "+e(u))},delete:function(u){return!!a&&a.delete(u)},get:function(u){return a&&a.get(u)},has:function(u){return!!a&&a.has(u)},set:function(u,c){a||(a=i()),a.set(u,c)}};return l},Fd}var Md,Yy;function Rp(){if(Yy)return Md;Yy=1;var t=String.prototype.replace,e=/%20/g,r={RFC1738:"RFC1738",RFC3986:"RFC3986"};return Md={default:r.RFC3986,formatters:{RFC1738:function(n){return t.call(n,e,"+")},RFC3986:function(n){return String(n)}},RFC1738:r.RFC1738,RFC3986:r.RFC3986},Md}var Dd,Qy;function e1(){if(Qy)return Dd;Qy=1;var t=Rp(),e=Object.prototype.hasOwnProperty,r=Array.isArray,n=(function(){for(var g=[],v=0;v<256;++v)g.push("%"+((v<16?"0":"")+v.toString(16)).toUpperCase());return g})(),s=function(v){for(;v.length>1;){var b=v.pop(),w=b.obj[b.prop];if(r(w)){for(var _=[],S=0;S<w.length;++S)typeof w[S]<"u"&&_.push(w[S]);b.obj[b.prop]=_}}},i=function(v,b){for(var w=b&&b.plainObjects?{__proto__:null}:{},_=0;_<v.length;++_)typeof v[_]<"u"&&(w[_]=v[_]);return w},o=function g(v,b,w){if(!b)return v;if(typeof b!="object"&&typeof b!="function"){if(r(v))v.push(b);else if(v&&typeof v=="object")(w&&(w.plainObjects||w.allowPrototypes)||!e.call(Object.prototype,b))&&(v[b]=!0);else return[v,b];return v}if(!v||typeof v!="object")return[v].concat(b);var _=v;return r(v)&&!r(b)&&(_=i(v,w)),r(v)&&r(b)?(b.forEach(function(S,k){if(e.call(v,k)){var O=v[k];O&&typeof O=="object"&&S&&typeof S=="object"?v[k]=g(O,S,w):v.push(S)}else v[k]=S}),v):Object.keys(b).reduce(function(S,k){var O=b[k];return e.call(S,k)?S[k]=g(S[k],O,w):S[k]=O,S},_)},a=function(v,b){return Object.keys(b).reduce(function(w,_){return w[_]=b[_],w},v)},l=function(g,v,b){var w=g.replace(/\+/g," ");if(b==="iso-8859-1")return w.replace(/%[0-9a-f]{2}/gi,unescape);try{return decodeURIComponent(w)}catch{return w}},u=1024,c=function(v,b,w,_,S){if(v.length===0)return v;var k=v;if(typeof v=="symbol"?k=Symbol.prototype.toString.call(v):typeof v!="string"&&(k=String(v)),w==="iso-8859-1")return escape(k).replace(/%u[0-9a-f]{4}/gi,function(y){return"%26%23"+parseInt(y.slice(2),16)+"%3B"});for(var O="",R=0;R<k.length;R+=u){for(var C=k.length>=u?k.slice(R,R+u):k,A=[],j=0;j<C.length;++j){var $=C.charCodeAt(j);if($===45||$===46||$===95||$===126||$>=48&&$<=57||$>=65&&$<=90||$>=97&&$<=122||S===t.RFC1738&&($===40||$===41)){A[A.length]=C.charAt(j);continue}if($<128){A[A.length]=n[$];continue}if($<2048){A[A.length]=n[192|$>>6]+n[128|$&63];continue}if($<55296||$>=57344){A[A.length]=n[224|$>>12]+n[128|$>>6&63]+n[128|$&63];continue}j+=1,$=65536+(($&1023)<<10|C.charCodeAt(j)&1023),A[A.length]=n[240|$>>18]+n[128|$>>12&63]+n[128|$>>6&63]+n[128|$&63]}O+=A.join("")}return O},d=function(v){for(var b=[{obj:{o:v},prop:"o"}],w=[],_=0;_<b.length;++_)for(var S=b[_],k=S.obj[S.prop],O=Object.keys(k),R=0;R<O.length;++R){var C=O[R],A=k[C];typeof A=="object"&&A!==null&&w.indexOf(A)===-1&&(b.push({obj:k,prop:C}),w.push(A))}return s(b),v},p=function(v){return Object.prototype.toString.call(v)==="[object RegExp]"},f=function(v){return!v||typeof v!="object"?!1:!!(v.constructor&&v.constructor.isBuffer&&v.constructor.isBuffer(v))},h=function(v,b){return[].concat(v,b)},m=function(v,b){if(r(v)){for(var w=[],_=0;_<v.length;_+=1)w.push(b(v[_]));return w}return b(v)};return Dd={arrayToObject:i,assign:a,combine:h,compact:d,decode:l,encode:c,isBuffer:f,isRegExp:p,maybeMap:m,merge:o},Dd}var Vd,Zy;function WN(){if(Zy)return Vd;Zy=1;var t=zN(),e=e1(),r=Rp(),n=Object.prototype.hasOwnProperty,s={brackets:function(g){return g+"[]"},comma:"comma",indices:function(g,v){return g+"["+v+"]"},repeat:function(g){return g}},i=Array.isArray,o=Array.prototype.push,a=function(m,g){o.apply(m,i(g)?g:[g])},l=Date.prototype.toISOString,u=r.default,c={addQueryPrefix:!1,allowDots:!1,allowEmptyArrays:!1,arrayFormat:"indices",charset:"utf-8",charsetSentinel:!1,commaRoundTrip:!1,delimiter:"&",encode:!0,encodeDotInKeys:!1,encoder:e.encode,encodeValuesOnly:!1,filter:void 0,format:u,formatter:r.formatters[u],indices:!1,serializeDate:function(g){return l.call(g)},skipNulls:!1,strictNullHandling:!1},d=function(g){return typeof g=="string"||typeof g=="number"||typeof g=="boolean"||typeof g=="symbol"||typeof g=="bigint"},p={},f=function m(g,v,b,w,_,S,k,O,R,C,A,j,$,y,I,q,D,z){for(var P=g,ee=z,ye=0,re=!1;(ee=ee.get(p))!==void 0&&!re;){var _e=ee.get(g);if(ye+=1,typeof _e<"u"){if(_e===ye)throw new RangeError("Cyclic object value");re=!0}typeof ee.get(p)>"u"&&(ye=0)}if(typeof C=="function"?P=C(v,P):P instanceof Date?P=$(P):b==="comma"&&i(P)&&(P=e.maybeMap(P,function(B){return B instanceof Date?$(B):B})),P===null){if(S)return R&&!q?R(v,c.encoder,D,"key",y):v;P=""}if(d(P)||e.isBuffer(P)){if(R){var at=q?v:R(v,c.encoder,D,"key",y);return[I(at)+"="+I(R(P,c.encoder,D,"value",y))]}return[I(v)+"="+I(String(P))]}var st=[];if(typeof P>"u")return st;var _t;if(b==="comma"&&i(P))q&&R&&(P=e.maybeMap(P,R)),_t=[{value:P.length>0?P.join(",")||null:void 0}];else if(i(C))_t=C;else{var gt=Object.keys(P);_t=A?gt.sort(A):gt}var cr=O?String(v).replace(/\./g,"%2E"):String(v),_r=w&&i(P)&&P.length===1?cr+"[]":cr;if(_&&i(P)&&P.length===0)return _r+"[]";for(var it=0;it<_t.length;++it){var me=_t[it],qe=typeof me=="object"&&me&&typeof me.value<"u"?me.value:P[me];if(!(k&&qe===null)){var Re=j&&O?String(me).replace(/\./g,"%2E"):String(me),E=i(P)?typeof b=="function"?b(_r,Re):_r:_r+(j?"."+Re:"["+Re+"]");z.set(g,ye);var T=t();T.set(p,z),a(st,m(qe,E,b,w,_,S,k,O,b==="comma"&&q&&i(P)?null:R,C,A,j,$,y,I,q,D,T))}}return st},h=function(g){if(!g)return c;if(typeof g.allowEmptyArrays<"u"&&typeof g.allowEmptyArrays!="boolean")throw new TypeError("`allowEmptyArrays` option can only be `true` or `false`, when provided");if(typeof g.encodeDotInKeys<"u"&&typeof g.encodeDotInKeys!="boolean")throw new TypeError("`encodeDotInKeys` option can only be `true` or `false`, when provided");if(g.encoder!==null&&typeof g.encoder<"u"&&typeof g.encoder!="function")throw new TypeError("Encoder has to be a function.");var v=g.charset||c.charset;if(typeof g.charset<"u"&&g.charset!=="utf-8"&&g.charset!=="iso-8859-1")throw new TypeError("The charset option must be either utf-8, iso-8859-1, or undefined");var b=r.default;if(typeof g.format<"u"){if(!n.call(r.formatters,g.format))throw new TypeError("Unknown format option provided.");b=g.format}var w=r.formatters[b],_=c.filter;(typeof g.filter=="function"||i(g.filter))&&(_=g.filter);var S;if(g.arrayFormat in s?S=g.arrayFormat:"indices"in g?S=g.indices?"indices":"repeat":S=c.arrayFormat,"commaRoundTrip"in g&&typeof g.commaRoundTrip!="boolean")throw new TypeError("`commaRoundTrip` must be a boolean, or absent");var k=typeof g.allowDots>"u"?g.encodeDotInKeys===!0?!0:c.allowDots:!!g.allowDots;return{addQueryPrefix:typeof g.addQueryPrefix=="boolean"?g.addQueryPrefix:c.addQueryPrefix,allowDots:k,allowEmptyArrays:typeof g.allowEmptyArrays=="boolean"?!!g.allowEmptyArrays:c.allowEmptyArrays,arrayFormat:S,charset:v,charsetSentinel:typeof g.charsetSentinel=="boolean"?g.charsetSentinel:c.charsetSentinel,commaRoundTrip:!!g.commaRoundTrip,delimiter:typeof g.delimiter>"u"?c.delimiter:g.delimiter,encode:typeof g.encode=="boolean"?g.encode:c.encode,encodeDotInKeys:typeof g.encodeDotInKeys=="boolean"?g.encodeDotInKeys:c.encodeDotInKeys,encoder:typeof g.encoder=="function"?g.encoder:c.encoder,encodeValuesOnly:typeof g.encodeValuesOnly=="boolean"?g.encodeValuesOnly:c.encodeValuesOnly,filter:_,format:b,formatter:w,serializeDate:typeof g.serializeDate=="function"?g.serializeDate:c.serializeDate,skipNulls:typeof g.skipNulls=="boolean"?g.skipNulls:c.skipNulls,sort:typeof g.sort=="function"?g.sort:null,strictNullHandling:typeof g.strictNullHandling=="boolean"?g.strictNullHandling:c.strictNullHandling}};return Vd=function(m,g){var v=m,b=h(g),w,_;typeof b.filter=="function"?(_=b.filter,v=_("",v)):i(b.filter)&&(_=b.filter,w=_);var S=[];if(typeof v!="object"||v===null)return"";var k=s[b.arrayFormat],O=k==="comma"&&b.commaRoundTrip;w||(w=Object.keys(v)),b.sort&&w.sort(b.sort);for(var R=t(),C=0;C<w.length;++C){var A=w[C],j=v[A];b.skipNulls&&j===null||a(S,f(j,A,k,O,b.allowEmptyArrays,b.strictNullHandling,b.skipNulls,b.encodeDotInKeys,b.encode?b.encoder:null,b.filter,b.sort,b.allowDots,b.serializeDate,b.format,b.formatter,b.encodeValuesOnly,b.charset,R))}var $=S.join(b.delimiter),y=b.addQueryPrefix===!0?"?":"";return b.charsetSentinel&&(b.charset==="iso-8859-1"?y+="utf8=%26%2310003%3B&":y+="utf8=%E2%9C%93&"),$.length>0?y+$:""},Vd}var Bd,eb;function KN(){if(eb)return Bd;eb=1;var t=e1(),e=Object.prototype.hasOwnProperty,r=Array.isArray,n={allowDots:!1,allowEmptyArrays:!1,allowPrototypes:!1,allowSparse:!1,arrayLimit:20,charset:"utf-8",charsetSentinel:!1,comma:!1,decodeDotInKeys:!1,decoder:t.decode,delimiter:"&",depth:5,duplicates:"combine",ignoreQueryPrefix:!1,interpretNumericEntities:!1,parameterLimit:1e3,parseArrays:!0,plainObjects:!1,strictDepth:!1,strictNullHandling:!1,throwOnLimitExceeded:!1},s=function(p){return p.replace(/&#(\d+);/g,function(f,h){return String.fromCharCode(parseInt(h,10))})},i=function(p,f,h){if(p&&typeof p=="string"&&f.comma&&p.indexOf(",")>-1)return p.split(",");if(f.throwOnLimitExceeded&&h>=f.arrayLimit)throw new RangeError("Array limit exceeded. Only "+f.arrayLimit+" element"+(f.arrayLimit===1?"":"s")+" allowed in an array.");return p},o="utf8=%26%2310003%3B",a="utf8=%E2%9C%93",l=function(f,h){var m={__proto__:null},g=h.ignoreQueryPrefix?f.replace(/^\?/,""):f;g=g.replace(/%5B/gi,"[").replace(/%5D/gi,"]");var v=h.parameterLimit===1/0?void 0:h.parameterLimit,b=g.split(h.delimiter,h.throwOnLimitExceeded?v+1:v);if(h.throwOnLimitExceeded&&b.length>v)throw new RangeError("Parameter limit exceeded. Only "+v+" parameter"+(v===1?"":"s")+" allowed.");var w=-1,_,S=h.charset;if(h.charsetSentinel)for(_=0;_<b.length;++_)b[_].indexOf("utf8=")===0&&(b[_]===a?S="utf-8":b[_]===o&&(S="iso-8859-1"),w=_,_=b.length);for(_=0;_<b.length;++_)if(_!==w){var k=b[_],O=k.indexOf("]="),R=O===-1?k.indexOf("="):O+1,C,A;R===-1?(C=h.decoder(k,n.decoder,S,"key"),A=h.strictNullHandling?null:""):(C=h.decoder(k.slice(0,R),n.decoder,S,"key"),A=t.maybeMap(i(k.slice(R+1),h,r(m[C])?m[C].length:0),function($){return h.decoder($,n.decoder,S,"value")})),A&&h.interpretNumericEntities&&S==="iso-8859-1"&&(A=s(String(A))),k.indexOf("[]=")>-1&&(A=r(A)?[A]:A);var j=e.call(m,C);j&&h.duplicates==="combine"?m[C]=t.combine(m[C],A):(!j||h.duplicates==="last")&&(m[C]=A)}return m},u=function(p,f,h,m){var g=0;if(p.length>0&&p[p.length-1]==="[]"){var v=p.slice(0,-1).join("");g=Array.isArray(f)&&f[v]?f[v].length:0}for(var b=m?f:i(f,h,g),w=p.length-1;w>=0;--w){var _,S=p[w];if(S==="[]"&&h.parseArrays)_=h.allowEmptyArrays&&(b===""||h.strictNullHandling&&b===null)?[]:t.combine([],b);else{_=h.plainObjects?{__proto__:null}:{};var k=S.charAt(0)==="["&&S.charAt(S.length-1)==="]"?S.slice(1,-1):S,O=h.decodeDotInKeys?k.replace(/%2E/g,"."):k,R=parseInt(O,10);!h.parseArrays&&O===""?_={0:b}:!isNaN(R)&&S!==O&&String(R)===O&&R>=0&&h.parseArrays&&R<=h.arrayLimit?(_=[],_[R]=b):O!=="__proto__"&&(_[O]=b)}b=_}return b},c=function(f,h,m,g){if(f){var v=m.allowDots?f.replace(/\.([^.[]+)/g,"[$1]"):f,b=/(\[[^[\]]*])/,w=/(\[[^[\]]*])/g,_=m.depth>0&&b.exec(v),S=_?v.slice(0,_.index):v,k=[];if(S){if(!m.plainObjects&&e.call(Object.prototype,S)&&!m.allowPrototypes)return;k.push(S)}for(var O=0;m.depth>0&&(_=w.exec(v))!==null&&O<m.depth;){if(O+=1,!m.plainObjects&&e.call(Object.prototype,_[1].slice(1,-1))&&!m.allowPrototypes)return;k.push(_[1])}if(_){if(m.strictDepth===!0)throw new RangeError("Input depth exceeded depth option of "+m.depth+" and strictDepth is true");k.push("["+v.slice(_.index)+"]")}return u(k,h,m,g)}},d=function(f){if(!f)return n;if(typeof f.allowEmptyArrays<"u"&&typeof f.allowEmptyArrays!="boolean")throw new TypeError("`allowEmptyArrays` option can only be `true` or `false`, when provided");if(typeof f.decodeDotInKeys<"u"&&typeof f.decodeDotInKeys!="boolean")throw new TypeError("`decodeDotInKeys` option can only be `true` or `false`, when provided");if(f.decoder!==null&&typeof f.decoder<"u"&&typeof f.decoder!="function")throw new TypeError("Decoder has to be a function.");if(typeof f.charset<"u"&&f.charset!=="utf-8"&&f.charset!=="iso-8859-1")throw new TypeError("The charset option must be either utf-8, iso-8859-1, or undefined");if(typeof f.throwOnLimitExceeded<"u"&&typeof f.throwOnLimitExceeded!="boolean")throw new TypeError("`throwOnLimitExceeded` option must be a boolean");var h=typeof f.charset>"u"?n.charset:f.charset,m=typeof f.duplicates>"u"?n.duplicates:f.duplicates;if(m!=="combine"&&m!=="first"&&m!=="last")throw new TypeError("The duplicates option must be either combine, first, or last");var g=typeof f.allowDots>"u"?f.decodeDotInKeys===!0?!0:n.allowDots:!!f.allowDots;return{allowDots:g,allowEmptyArrays:typeof f.allowEmptyArrays=="boolean"?!!f.allowEmptyArrays:n.allowEmptyArrays,allowPrototypes:typeof f.allowPrototypes=="boolean"?f.allowPrototypes:n.allowPrototypes,allowSparse:typeof f.allowSparse=="boolean"?f.allowSparse:n.allowSparse,arrayLimit:typeof f.arrayLimit=="number"?f.arrayLimit:n.arrayLimit,charset:h,charsetSentinel:typeof f.charsetSentinel=="boolean"?f.charsetSentinel:n.charsetSentinel,comma:typeof f.comma=="boolean"?f.comma:n.comma,decodeDotInKeys:typeof f.decodeDotInKeys=="boolean"?f.decodeDotInKeys:n.decodeDotInKeys,decoder:typeof f.decoder=="function"?f.decoder:n.decoder,delimiter:typeof f.delimiter=="string"||t.isRegExp(f.delimiter)?f.delimiter:n.delimiter,depth:typeof f.depth=="number"||f.depth===!1?+f.depth:n.depth,duplicates:m,ignoreQueryPrefix:f.ignoreQueryPrefix===!0,interpretNumericEntities:typeof f.interpretNumericEntities=="boolean"?f.interpretNumericEntities:n.interpretNumericEntities,parameterLimit:typeof f.parameterLimit=="number"?f.parameterLimit:n.parameterLimit,parseArrays:f.parseArrays!==!1,plainObjects:typeof f.plainObjects=="boolean"?f.plainObjects:n.plainObjects,strictDepth:typeof f.strictDepth=="boolean"?!!f.strictDepth:n.strictDepth,strictNullHandling:typeof f.strictNullHandling=="boolean"?f.strictNullHandling:n.strictNullHandling,throwOnLimitExceeded:typeof f.throwOnLimitExceeded=="boolean"?f.throwOnLimitExceeded:!1}};return Bd=function(p,f){var h=d(f);if(p===""||p===null||typeof p>"u")return h.plainObjects?{__proto__:null}:{};for(var m=typeof p=="string"?l(p,h):p,g=h.plainObjects?{__proto__:null}:{},v=Object.keys(m),b=0;b<v.length;++b){var w=v[b],_=c(w,m[w],h,typeof p=="string");g=t.merge(g,_,h)}return h.allowSparse===!0?g:t.compact(g)},Bd}var Ud,tb;function GN(){if(tb)return Ud;tb=1;var t=WN(),e=KN(),r=Rp();return Ud={formats:r,parse:e,stringify:t},Ud}var rb=GN();function t1(t,e){return function(){return t.apply(e,arguments)}}const{toString:JN}=Object.prototype,{getPrototypeOf:Np}=Object,{iterator:tu,toStringTag:r1}=Symbol,ru=(t=>e=>{const r=JN.call(e);return t[r]||(t[r]=r.slice(8,-1).toLowerCase())})(Object.create(null)),jr=t=>(t=t.toLowerCase(),e=>ru(e)===t),nu=t=>e=>typeof e===t,{isArray:Yi}=Array,Li=nu("undefined");function _a(t){return t!==null&&!Li(t)&&t.constructor!==null&&!Li(t.constructor)&&tr(t.constructor.isBuffer)&&t.constructor.isBuffer(t)}const n1=jr("ArrayBuffer");function XN(t){let e;return typeof ArrayBuffer<"u"&&ArrayBuffer.isView?e=ArrayBuffer.isView(t):e=t&&t.buffer&&n1(t.buffer),e}const YN=nu("string"),tr=nu("function"),s1=nu("number"),Ea=t=>t!==null&&typeof t=="object",QN=t=>t===!0||t===!1,wl=t=>{if(ru(t)!=="object")return!1;const e=Np(t);return(e===null||e===Object.prototype||Object.getPrototypeOf(e)===null)&&!(r1 in t)&&!(tu in t)},ZN=t=>{if(!Ea(t)||_a(t))return!1;try{return Object.keys(t).length===0&&Object.getPrototypeOf(t)===Object.prototype}catch{return!1}},eI=jr("Date"),tI=jr("File"),rI=jr("Blob"),nI=jr("FileList"),sI=t=>Ea(t)&&tr(t.pipe),iI=t=>{let e;return t&&(typeof FormData=="function"&&t instanceof FormData||tr(t.append)&&((e=ru(t))==="formdata"||e==="object"&&tr(t.toString)&&t.toString()==="[object FormData]"))},oI=jr("URLSearchParams"),[aI,lI,cI,uI]=["ReadableStream","Request","Response","Headers"].map(jr),dI=t=>t.trim?t.trim():t.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g,"");function Sa(t,e,{allOwnKeys:r=!1}={}){if(t===null||typeof t>"u")return;let n,s;if(typeof t!="object"&&(t=[t]),Yi(t))for(n=0,s=t.length;n<s;n++)e.call(null,t[n],n,t);else{if(_a(t))return;const i=r?Object.getOwnPropertyNames(t):Object.keys(t),o=i.length;let a;for(n=0;n<o;n++)a=i[n],e.call(null,t[a],a,t)}}function i1(t,e){if(_a(t))return null;e=e.toLowerCase();const r=Object.keys(t);let n=r.length,s;for(;n-- >0;)if(s=r[n],e===s.toLowerCase())return s;return null}const Ss=typeof globalThis<"u"?globalThis:typeof self<"u"?self:typeof window<"u"?window:global,o1=t=>!Li(t)&&t!==Ss;function Kh(){const{caseless:t,skipUndefined:e}=o1(this)&&this||{},r={},n=(s,i)=>{const o=t&&i1(r,i)||i;wl(r[o])&&wl(s)?r[o]=Kh(r[o],s):wl(s)?r[o]=Kh({},s):Yi(s)?r[o]=s.slice():(!e||!Li(s))&&(r[o]=s)};for(let s=0,i=arguments.length;s<i;s++)arguments[s]&&Sa(arguments[s],n);return r}const hI=(t,e,r,{allOwnKeys:n}={})=>(Sa(e,(s,i)=>{r&&tr(s)?t[i]=t1(s,r):t[i]=s},{allOwnKeys:n}),t),fI=t=>(t.charCodeAt(0)===65279&&(t=t.slice(1)),t),pI=(t,e,r,n)=>{t.prototype=Object.create(e.prototype,n),t.prototype.constructor=t,Object.defineProperty(t,"super",{value:e.prototype}),r&&Object.assign(t.prototype,r)},mI=(t,e,r,n)=>{let s,i,o;const a={};if(e=e||{},t==null)return e;do{for(s=Object.getOwnPropertyNames(t),i=s.length;i-- >0;)o=s[i],(!n||n(o,t,e))&&!a[o]&&(e[o]=t[o],a[o]=!0);t=r!==!1&&Np(t)}while(t&&(!r||r(t,e))&&t!==Object.prototype);return e},gI=(t,e,r)=>{t=String(t),(r===void 0||r>t.length)&&(r=t.length),r-=e.length;const n=t.indexOf(e,r);return n!==-1&&n===r},yI=t=>{if(!t)return null;if(Yi(t))return t;let e=t.length;if(!s1(e))return null;const r=new Array(e);for(;e-- >0;)r[e]=t[e];return r},bI=(t=>e=>t&&e instanceof t)(typeof Uint8Array<"u"&&Np(Uint8Array)),vI=(t,e)=>{const n=(t&&t[tu]).call(t);let s;for(;(s=n.next())&&!s.done;){const i=s.value;e.call(t,i[0],i[1])}},wI=(t,e)=>{let r;const n=[];for(;(r=t.exec(e))!==null;)n.push(r);return n},_I=jr("HTMLFormElement"),EI=t=>t.toLowerCase().replace(/[-_\s]([a-z\d])(\w*)/g,function(r,n,s){return n.toUpperCase()+s}),nb=(({hasOwnProperty:t})=>(e,r)=>t.call(e,r))(Object.prototype),SI=jr("RegExp"),a1=(t,e)=>{const r=Object.getOwnPropertyDescriptors(t),n={};Sa(r,(s,i)=>{let o;(o=e(s,i,t))!==!1&&(n[i]=o||s)}),Object.defineProperties(t,n)},xI=t=>{a1(t,(e,r)=>{if(tr(t)&&["arguments","caller","callee"].indexOf(r)!==-1)return!1;const n=t[r];if(tr(n)){if(e.enumerable=!1,"writable"in e){e.writable=!1;return}e.set||(e.set=()=>{throw Error("Can not rewrite read-only method '"+r+"'")})}})},CI=(t,e)=>{const r={},n=s=>{s.forEach(i=>{r[i]=!0})};return Yi(t)?n(t):n(String(t).split(e)),r},AI=()=>{},$I=(t,e)=>t!=null&&Number.isFinite(t=+t)?t:e;function TI(t){return!!(t&&tr(t.append)&&t[r1]==="FormData"&&t[tu])}const kI=t=>{const e=new Array(10),r=(n,s)=>{if(Ea(n)){if(e.indexOf(n)>=0)return;if(_a(n))return n;if(!("toJSON"in n)){e[s]=n;const i=Yi(n)?[]:{};return Sa(n,(o,a)=>{const l=r(o,s+1);!Li(l)&&(i[a]=l)}),e[s]=void 0,i}}return n};return r(t,0)},PI=jr("AsyncFunction"),OI=t=>t&&(Ea(t)||tr(t))&&tr(t.then)&&tr(t.catch),l1=((t,e)=>t?setImmediate:e?((r,n)=>(Ss.addEventListener("message",({source:s,data:i})=>{s===Ss&&i===r&&n.length&&n.shift()()},!1),s=>{n.push(s),Ss.postMessage(r,"*")}))(`axios@${Math.random()}`,[]):r=>setTimeout(r))(typeof setImmediate=="function",tr(Ss.postMessage)),RI=typeof queueMicrotask<"u"?queueMicrotask.bind(Ss):typeof process<"u"&&process.nextTick||l1,NI=t=>t!=null&&tr(t[tu]),V={isArray:Yi,isArrayBuffer:n1,isBuffer:_a,isFormData:iI,isArrayBufferView:XN,isString:YN,isNumber:s1,isBoolean:QN,isObject:Ea,isPlainObject:wl,isEmptyObject:ZN,isReadableStream:aI,isRequest:lI,isResponse:cI,isHeaders:uI,isUndefined:Li,isDate:eI,isFile:tI,isBlob:rI,isRegExp:SI,isFunction:tr,isStream:sI,isURLSearchParams:oI,isTypedArray:bI,isFileList:nI,forEach:Sa,merge:Kh,extend:hI,trim:dI,stripBOM:fI,inherits:pI,toFlatObject:mI,kindOf:ru,kindOfTest:jr,endsWith:gI,toArray:yI,forEachEntry:vI,matchAll:wI,isHTMLForm:_I,hasOwnProperty:nb,hasOwnProp:nb,reduceDescriptors:a1,freezeMethods:xI,toObjectSet:CI,toCamelCase:EI,noop:AI,toFiniteNumber:$I,findKey:i1,global:Ss,isContextDefined:o1,isSpecCompliantForm:TI,toJSONObject:kI,isAsyncFn:PI,isThenable:OI,setImmediate:l1,asap:RI,isIterable:NI};function he(t,e,r,n,s){Error.call(this),Error.captureStackTrace?Error.captureStackTrace(this,this.constructor):this.stack=new Error().stack,this.message=t,this.name="AxiosError",e&&(this.code=e),r&&(this.config=r),n&&(this.request=n),s&&(this.response=s,this.status=s.status?s.status:null)}V.inherits(he,Error,{toJSON:function(){return{message:this.message,name:this.name,description:this.description,number:this.number,fileName:this.fileName,lineNumber:this.lineNumber,columnNumber:this.columnNumber,stack:this.stack,config:V.toJSONObject(this.config),code:this.code,status:this.status}}});const c1=he.prototype,u1={};["ERR_BAD_OPTION_VALUE","ERR_BAD_OPTION","ECONNABORTED","ETIMEDOUT","ERR_NETWORK","ERR_FR_TOO_MANY_REDIRECTS","ERR_DEPRECATED","ERR_BAD_RESPONSE","ERR_BAD_REQUEST","ERR_CANCELED","ERR_NOT_SUPPORT","ERR_INVALID_URL"].forEach(t=>{u1[t]={value:t}});Object.defineProperties(he,u1);Object.defineProperty(c1,"isAxiosError",{value:!0});he.from=(t,e,r,n,s,i)=>{const o=Object.create(c1);V.toFlatObject(t,o,function(c){return c!==Error.prototype},u=>u!=="isAxiosError");const a=t&&t.message?t.message:"Error",l=e==null&&t?t.code:e;return he.call(o,a,l,r,n,s),t&&o.cause==null&&Object.defineProperty(o,"cause",{value:t,configurable:!0}),o.name=t&&t.name||"Error",i&&Object.assign(o,i),o};const II=null;function Gh(t){return V.isPlainObject(t)||V.isArray(t)}function d1(t){return V.endsWith(t,"[]")?t.slice(0,-2):t}function sb(t,e,r){return t?t.concat(e).map(function(s,i){return s=d1(s),!r&&i?"["+s+"]":s}).join(r?".":""):e}function LI(t){return V.isArray(t)&&!t.some(Gh)}const FI=V.toFlatObject(V,{},null,function(e){return/^is[A-Z]/.test(e)});function su(t,e,r){if(!V.isObject(t))throw new TypeError("target must be an object");e=e||new FormData,r=V.toFlatObject(r,{metaTokens:!0,dots:!1,indexes:!1},!1,function(m,g){return!V.isUndefined(g[m])});const n=r.metaTokens,s=r.visitor||c,i=r.dots,o=r.indexes,l=(r.Blob||typeof Blob<"u"&&Blob)&&V.isSpecCompliantForm(e);if(!V.isFunction(s))throw new TypeError("visitor must be a function");function u(h){if(h===null)return"";if(V.isDate(h))return h.toISOString();if(V.isBoolean(h))return h.toString();if(!l&&V.isBlob(h))throw new he("Blob is not supported. Use a Buffer instead.");return V.isArrayBuffer(h)||V.isTypedArray(h)?l&&typeof Blob=="function"?new Blob([h]):Buffer.from(h):h}function c(h,m,g){let v=h;if(h&&!g&&typeof h=="object"){if(V.endsWith(m,"{}"))m=n?m:m.slice(0,-2),h=JSON.stringify(h);else if(V.isArray(h)&&LI(h)||(V.isFileList(h)||V.endsWith(m,"[]"))&&(v=V.toArray(h)))return m=d1(m),v.forEach(function(w,_){!(V.isUndefined(w)||w===null)&&e.append(o===!0?sb([m],_,i):o===null?m:m+"[]",u(w))}),!1}return Gh(h)?!0:(e.append(sb(g,m,i),u(h)),!1)}const d=[],p=Object.assign(FI,{defaultVisitor:c,convertValue:u,isVisitable:Gh});function f(h,m){if(!V.isUndefined(h)){if(d.indexOf(h)!==-1)throw Error("Circular reference detected in "+m.join("."));d.push(h),V.forEach(h,function(v,b){(!(V.isUndefined(v)||v===null)&&s.call(e,v,V.isString(b)?b.trim():b,m,p))===!0&&f(v,m?m.concat(b):[b])}),d.pop()}}if(!V.isObject(t))throw new TypeError("data must be an object");return f(t),e}function ib(t){const e={"!":"%21","'":"%27","(":"%28",")":"%29","~":"%7E","%20":"+","%00":"\0"};return encodeURIComponent(t).replace(/[!'()~]|%20|%00/g,function(n){return e[n]})}function Ip(t,e){this._pairs=[],t&&su(t,this,e)}const h1=Ip.prototype;h1.append=function(e,r){this._pairs.push([e,r])};h1.toString=function(e){const r=e?function(n){return e.call(this,n,ib)}:ib;return this._pairs.map(function(s){return r(s[0])+"="+r(s[1])},"").join("&")};function MI(t){return encodeURIComponent(t).replace(/%3A/gi,":").replace(/%24/g,"$").replace(/%2C/gi,",").replace(/%20/g,"+")}function f1(t,e,r){if(!e)return t;const n=r&&r.encode||MI;V.isFunction(r)&&(r={serialize:r});const s=r&&r.serialize;let i;if(s?i=s(e,r):i=V.isURLSearchParams(e)?e.toString():new Ip(e,r).toString(n),i){const o=t.indexOf("#");o!==-1&&(t=t.slice(0,o)),t+=(t.indexOf("?")===-1?"?":"&")+i}return t}class ob{constructor(){this.handlers=[]}use(e,r,n){return this.handlers.push({fulfilled:e,rejected:r,synchronous:n?n.synchronous:!1,runWhen:n?n.runWhen:null}),this.handlers.length-1}eject(e){this.handlers[e]&&(this.handlers[e]=null)}clear(){this.handlers&&(this.handlers=[])}forEach(e){V.forEach(this.handlers,function(n){n!==null&&e(n)})}}const p1={silentJSONParsing:!0,forcedJSONParsing:!0,clarifyTimeoutError:!1},DI=typeof URLSearchParams<"u"?URLSearchParams:Ip,VI=typeof FormData<"u"?FormData:null,BI=typeof Blob<"u"?Blob:null,UI={isBrowser:!0,classes:{URLSearchParams:DI,FormData:VI,Blob:BI},protocols:["http","https","file","blob","url","data"]},Lp=typeof window<"u"&&typeof document<"u",Jh=typeof navigator=="object"&&navigator||void 0,jI=Lp&&(!Jh||["ReactNative","NativeScript","NS"].indexOf(Jh.product)<0),qI=typeof WorkerGlobalScope<"u"&&self instanceof WorkerGlobalScope&&typeof self.importScripts=="function",HI=Lp&&window.location.href||"http://localhost",zI=Object.freeze(Object.defineProperty({__proto__:null,hasBrowserEnv:Lp,hasStandardBrowserEnv:jI,hasStandardBrowserWebWorkerEnv:qI,navigator:Jh,origin:HI},Symbol.toStringTag,{value:"Module"})),It={...zI,...UI};function WI(t,e){return su(t,new It.classes.URLSearchParams,{visitor:function(r,n,s,i){return It.isNode&&V.isBuffer(r)?(this.append(n,r.toString("base64")),!1):i.defaultVisitor.apply(this,arguments)},...e})}function KI(t){return V.matchAll(/\w+|\[(\w*)]/g,t).map(e=>e[0]==="[]"?"":e[1]||e[0])}function GI(t){const e={},r=Object.keys(t);let n;const s=r.length;let i;for(n=0;n<s;n++)i=r[n],e[i]=t[i];return e}function m1(t){function e(r,n,s,i){let o=r[i++];if(o==="__proto__")return!0;const a=Number.isFinite(+o),l=i>=r.length;return o=!o&&V.isArray(s)?s.length:o,l?(V.hasOwnProp(s,o)?s[o]=[s[o],n]:s[o]=n,!a):((!s[o]||!V.isObject(s[o]))&&(s[o]=[]),e(r,n,s[o],i)&&V.isArray(s[o])&&(s[o]=GI(s[o])),!a)}if(V.isFormData(t)&&V.isFunction(t.entries)){const r={};return V.forEachEntry(t,(n,s)=>{e(KI(n),s,r,0)}),r}return null}function JI(t,e,r){if(V.isString(t))try{return(e||JSON.parse)(t),V.trim(t)}catch(n){if(n.name!=="SyntaxError")throw n}return(r||JSON.stringify)(t)}const xa={transitional:p1,adapter:["xhr","http","fetch"],transformRequest:[function(e,r){const n=r.getContentType()||"",s=n.indexOf("application/json")>-1,i=V.isObject(e);if(i&&V.isHTMLForm(e)&&(e=new FormData(e)),V.isFormData(e))return s?JSON.stringify(m1(e)):e;if(V.isArrayBuffer(e)||V.isBuffer(e)||V.isStream(e)||V.isFile(e)||V.isBlob(e)||V.isReadableStream(e))return e;if(V.isArrayBufferView(e))return e.buffer;if(V.isURLSearchParams(e))return r.setContentType("application/x-www-form-urlencoded;charset=utf-8",!1),e.toString();let a;if(i){if(n.indexOf("application/x-www-form-urlencoded")>-1)return WI(e,this.formSerializer).toString();if((a=V.isFileList(e))||n.indexOf("multipart/form-data")>-1){const l=this.env&&this.env.FormData;return su(a?{"files[]":e}:e,l&&new l,this.formSerializer)}}return i||s?(r.setContentType("application/json",!1),JI(e)):e}],transformResponse:[function(e){const r=this.transitional||xa.transitional,n=r&&r.forcedJSONParsing,s=this.responseType==="json";if(V.isResponse(e)||V.isReadableStream(e))return e;if(e&&V.isString(e)&&(n&&!this.responseType||s)){const o=!(r&&r.silentJSONParsing)&&s;try{return JSON.parse(e,this.parseReviver)}catch(a){if(o)throw a.name==="SyntaxError"?he.from(a,he.ERR_BAD_RESPONSE,this,null,this.response):a}}return e}],timeout:0,xsrfCookieName:"XSRF-TOKEN",xsrfHeaderName:"X-XSRF-TOKEN",maxContentLength:-1,maxBodyLength:-1,env:{FormData:It.classes.FormData,Blob:It.classes.Blob},validateStatus:function(e){return e>=200&&e<300},headers:{common:{Accept:"application/json, text/plain, */*","Content-Type":void 0}}};V.forEach(["delete","get","head","post","put","patch"],t=>{xa.headers[t]={}});const XI=V.toObjectSet(["age","authorization","content-length","content-type","etag","expires","from","host","if-modified-since","if-unmodified-since","last-modified","location","max-forwards","proxy-authorization","referer","retry-after","user-agent"]),YI=t=>{const e={};let r,n,s;return t&&t.split(`
`).forEach(function(o){s=o.indexOf(":"),r=o.substring(0,s).trim().toLowerCase(),n=o.substring(s+1).trim(),!(!r||e[r]&&XI[r])&&(r==="set-cookie"?e[r]?e[r].push(n):e[r]=[n]:e[r]=e[r]?e[r]+", "+n:n)}),e},ab=Symbol("internals");function fo(t){return t&&String(t).trim().toLowerCase()}function _l(t){return t===!1||t==null?t:V.isArray(t)?t.map(_l):String(t)}function QI(t){const e=Object.create(null),r=/([^\s,;=]+)\s*(?:=\s*([^,;]+))?/g;let n;for(;n=r.exec(t);)e[n[1]]=n[2];return e}const ZI=t=>/^[-_a-zA-Z0-9^`|~,!#$%&'*+.]+$/.test(t.trim());function jd(t,e,r,n,s){if(V.isFunction(n))return n.call(this,e,r);if(s&&(e=r),!!V.isString(e)){if(V.isString(n))return e.indexOf(n)!==-1;if(V.isRegExp(n))return n.test(e)}}function eL(t){return t.trim().toLowerCase().replace(/([a-z\d])(\w*)/g,(e,r,n)=>r.toUpperCase()+n)}function tL(t,e){const r=V.toCamelCase(" "+e);["get","set","has"].forEach(n=>{Object.defineProperty(t,n+r,{value:function(s,i,o){return this[n].call(this,e,s,i,o)},configurable:!0})})}let rr=class{constructor(e){e&&this.set(e)}set(e,r,n){const s=this;function i(a,l,u){const c=fo(l);if(!c)throw new Error("header name must be a non-empty string");const d=V.findKey(s,c);(!d||s[d]===void 0||u===!0||u===void 0&&s[d]!==!1)&&(s[d||l]=_l(a))}const o=(a,l)=>V.forEach(a,(u,c)=>i(u,c,l));if(V.isPlainObject(e)||e instanceof this.constructor)o(e,r);else if(V.isString(e)&&(e=e.trim())&&!ZI(e))o(YI(e),r);else if(V.isObject(e)&&V.isIterable(e)){let a={},l,u;for(const c of e){if(!V.isArray(c))throw TypeError("Object iterator must return a key-value pair");a[u=c[0]]=(l=a[u])?V.isArray(l)?[...l,c[1]]:[l,c[1]]:c[1]}o(a,r)}else e!=null&&i(r,e,n);return this}get(e,r){if(e=fo(e),e){const n=V.findKey(this,e);if(n){const s=this[n];if(!r)return s;if(r===!0)return QI(s);if(V.isFunction(r))return r.call(this,s,n);if(V.isRegExp(r))return r.exec(s);throw new TypeError("parser must be boolean|regexp|function")}}}has(e,r){if(e=fo(e),e){const n=V.findKey(this,e);return!!(n&&this[n]!==void 0&&(!r||jd(this,this[n],n,r)))}return!1}delete(e,r){const n=this;let s=!1;function i(o){if(o=fo(o),o){const a=V.findKey(n,o);a&&(!r||jd(n,n[a],a,r))&&(delete n[a],s=!0)}}return V.isArray(e)?e.forEach(i):i(e),s}clear(e){const r=Object.keys(this);let n=r.length,s=!1;for(;n--;){const i=r[n];(!e||jd(this,this[i],i,e,!0))&&(delete this[i],s=!0)}return s}normalize(e){const r=this,n={};return V.forEach(this,(s,i)=>{const o=V.findKey(n,i);if(o){r[o]=_l(s),delete r[i];return}const a=e?eL(i):String(i).trim();a!==i&&delete r[i],r[a]=_l(s),n[a]=!0}),this}concat(...e){return this.constructor.concat(this,...e)}toJSON(e){const r=Object.create(null);return V.forEach(this,(n,s)=>{n!=null&&n!==!1&&(r[s]=e&&V.isArray(n)?n.join(", "):n)}),r}[Symbol.iterator](){return Object.entries(this.toJSON())[Symbol.iterator]()}toString(){return Object.entries(this.toJSON()).map(([e,r])=>e+": "+r).join(`
`)}getSetCookie(){return this.get("set-cookie")||[]}get[Symbol.toStringTag](){return"AxiosHeaders"}static from(e){return e instanceof this?e:new this(e)}static concat(e,...r){const n=new this(e);return r.forEach(s=>n.set(s)),n}static accessor(e){const n=(this[ab]=this[ab]={accessors:{}}).accessors,s=this.prototype;function i(o){const a=fo(o);n[a]||(tL(s,o),n[a]=!0)}return V.isArray(e)?e.forEach(i):i(e),this}};rr.accessor(["Content-Type","Content-Length","Accept","Accept-Encoding","User-Agent","Authorization"]);V.reduceDescriptors(rr.prototype,({value:t},e)=>{let r=e[0].toUpperCase()+e.slice(1);return{get:()=>t,set(n){this[r]=n}}});V.freezeMethods(rr);function qd(t,e){const r=this||xa,n=e||r,s=rr.from(n.headers);let i=n.data;return V.forEach(t,function(a){i=a.call(r,i,s.normalize(),e?e.status:void 0)}),s.normalize(),i}function g1(t){return!!(t&&t.__CANCEL__)}function Qi(t,e,r){he.call(this,t??"canceled",he.ERR_CANCELED,e,r),this.name="CanceledError"}V.inherits(Qi,he,{__CANCEL__:!0});function y1(t,e,r){const n=r.config.validateStatus;!r.status||!n||n(r.status)?t(r):e(new he("Request failed with status code "+r.status,[he.ERR_BAD_REQUEST,he.ERR_BAD_RESPONSE][Math.floor(r.status/100)-4],r.config,r.request,r))}function rL(t){const e=/^([-+\w]{1,25})(:?\/\/|:)/.exec(t);return e&&e[1]||""}function nL(t,e){t=t||10;const r=new Array(t),n=new Array(t);let s=0,i=0,o;return e=e!==void 0?e:1e3,function(l){const u=Date.now(),c=n[i];o||(o=u),r[s]=l,n[s]=u;let d=i,p=0;for(;d!==s;)p+=r[d++],d=d%t;if(s=(s+1)%t,s===i&&(i=(i+1)%t),u-o<e)return;const f=c&&u-c;return f?Math.round(p*1e3/f):void 0}}function sL(t,e){let r=0,n=1e3/e,s,i;const o=(u,c=Date.now())=>{r=c,s=null,i&&(clearTimeout(i),i=null),t(...u)};return[(...u)=>{const c=Date.now(),d=c-r;d>=n?o(u,c):(s=u,i||(i=setTimeout(()=>{i=null,o(s)},n-d)))},()=>s&&o(s)]}const uc=(t,e,r=3)=>{let n=0;const s=nL(50,250);return sL(i=>{const o=i.loaded,a=i.lengthComputable?i.total:void 0,l=o-n,u=s(l),c=o<=a;n=o;const d={loaded:o,total:a,progress:a?o/a:void 0,bytes:l,rate:u||void 0,estimated:u&&a&&c?(a-o)/u:void 0,event:i,lengthComputable:a!=null,[e?"download":"upload"]:!0};t(d)},r)},lb=(t,e)=>{const r=t!=null;return[n=>e[0]({lengthComputable:r,total:t,loaded:n}),e[1]]},cb=t=>(...e)=>V.asap(()=>t(...e)),iL=It.hasStandardBrowserEnv?((t,e)=>r=>(r=new URL(r,It.origin),t.protocol===r.protocol&&t.host===r.host&&(e||t.port===r.port)))(new URL(It.origin),It.navigator&&/(msie|trident)/i.test(It.navigator.userAgent)):()=>!0,oL=It.hasStandardBrowserEnv?{write(t,e,r,n,s,i){const o=[t+"="+encodeURIComponent(e)];V.isNumber(r)&&o.push("expires="+new Date(r).toGMTString()),V.isString(n)&&o.push("path="+n),V.isString(s)&&o.push("domain="+s),i===!0&&o.push("secure"),document.cookie=o.join("; ")},read(t){const e=document.cookie.match(new RegExp("(^|;\\s*)("+t+")=([^;]*)"));return e?decodeURIComponent(e[3]):null},remove(t){this.write(t,"",Date.now()-864e5)}}:{write(){},read(){return null},remove(){}};function aL(t){return/^([a-z][a-z\d+\-.]*:)?\/\//i.test(t)}function lL(t,e){return e?t.replace(/\/?\/$/,"")+"/"+e.replace(/^\/+/,""):t}function b1(t,e,r){let n=!aL(e);return t&&(n||r==!1)?lL(t,e):e}const ub=t=>t instanceof rr?{...t}:t;function qs(t,e){e=e||{};const r={};function n(u,c,d,p){return V.isPlainObject(u)&&V.isPlainObject(c)?V.merge.call({caseless:p},u,c):V.isPlainObject(c)?V.merge({},c):V.isArray(c)?c.slice():c}function s(u,c,d,p){if(V.isUndefined(c)){if(!V.isUndefined(u))return n(void 0,u,d,p)}else return n(u,c,d,p)}function i(u,c){if(!V.isUndefined(c))return n(void 0,c)}function o(u,c){if(V.isUndefined(c)){if(!V.isUndefined(u))return n(void 0,u)}else return n(void 0,c)}function a(u,c,d){if(d in e)return n(u,c);if(d in t)return n(void 0,u)}const l={url:i,method:i,data:i,baseURL:o,transformRequest:o,transformResponse:o,paramsSerializer:o,timeout:o,timeoutMessage:o,withCredentials:o,withXSRFToken:o,adapter:o,responseType:o,xsrfCookieName:o,xsrfHeaderName:o,onUploadProgress:o,onDownloadProgress:o,decompress:o,maxContentLength:o,maxBodyLength:o,beforeRedirect:o,transport:o,httpAgent:o,httpsAgent:o,cancelToken:o,socketPath:o,responseEncoding:o,validateStatus:a,headers:(u,c,d)=>s(ub(u),ub(c),d,!0)};return V.forEach(Object.keys({...t,...e}),function(c){const d=l[c]||s,p=d(t[c],e[c],c);V.isUndefined(p)&&d!==a||(r[c]=p)}),r}const v1=t=>{const e=qs({},t);let{data:r,withXSRFToken:n,xsrfHeaderName:s,xsrfCookieName:i,headers:o,auth:a}=e;if(e.headers=o=rr.from(o),e.url=f1(b1(e.baseURL,e.url,e.allowAbsoluteUrls),t.params,t.paramsSerializer),a&&o.set("Authorization","Basic "+btoa((a.username||"")+":"+(a.password?unescape(encodeURIComponent(a.password)):""))),V.isFormData(r)){if(It.hasStandardBrowserEnv||It.hasStandardBrowserWebWorkerEnv)o.setContentType(void 0);else if(V.isFunction(r.getHeaders)){const l=r.getHeaders(),u=["content-type","content-length"];Object.entries(l).forEach(([c,d])=>{u.includes(c.toLowerCase())&&o.set(c,d)})}}if(It.hasStandardBrowserEnv&&(n&&V.isFunction(n)&&(n=n(e)),n||n!==!1&&iL(e.url))){const l=s&&i&&oL.read(i);l&&o.set(s,l)}return e},cL=typeof XMLHttpRequest<"u",uL=cL&&function(t){return new Promise(function(r,n){const s=v1(t);let i=s.data;const o=rr.from(s.headers).normalize();let{responseType:a,onUploadProgress:l,onDownloadProgress:u}=s,c,d,p,f,h;function m(){f&&f(),h&&h(),s.cancelToken&&s.cancelToken.unsubscribe(c),s.signal&&s.signal.removeEventListener("abort",c)}let g=new XMLHttpRequest;g.open(s.method.toUpperCase(),s.url,!0),g.timeout=s.timeout;function v(){if(!g)return;const w=rr.from("getAllResponseHeaders"in g&&g.getAllResponseHeaders()),S={data:!a||a==="text"||a==="json"?g.responseText:g.response,status:g.status,statusText:g.statusText,headers:w,config:t,request:g};y1(function(O){r(O),m()},function(O){n(O),m()},S),g=null}"onloadend"in g?g.onloadend=v:g.onreadystatechange=function(){!g||g.readyState!==4||g.status===0&&!(g.responseURL&&g.responseURL.indexOf("file:")===0)||setTimeout(v)},g.onabort=function(){g&&(n(new he("Request aborted",he.ECONNABORTED,t,g)),g=null)},g.onerror=function(_){const S=_&&_.message?_.message:"Network Error",k=new he(S,he.ERR_NETWORK,t,g);k.event=_||null,n(k),g=null},g.ontimeout=function(){let _=s.timeout?"timeout of "+s.timeout+"ms exceeded":"timeout exceeded";const S=s.transitional||p1;s.timeoutErrorMessage&&(_=s.timeoutErrorMessage),n(new he(_,S.clarifyTimeoutError?he.ETIMEDOUT:he.ECONNABORTED,t,g)),g=null},i===void 0&&o.setContentType(null),"setRequestHeader"in g&&V.forEach(o.toJSON(),function(_,S){g.setRequestHeader(S,_)}),V.isUndefined(s.withCredentials)||(g.withCredentials=!!s.withCredentials),a&&a!=="json"&&(g.responseType=s.responseType),u&&([p,h]=uc(u,!0),g.addEventListener("progress",p)),l&&g.upload&&([d,f]=uc(l),g.upload.addEventListener("progress",d),g.upload.addEventListener("loadend",f)),(s.cancelToken||s.signal)&&(c=w=>{g&&(n(!w||w.type?new Qi(null,t,g):w),g.abort(),g=null)},s.cancelToken&&s.cancelToken.subscribe(c),s.signal&&(s.signal.aborted?c():s.signal.addEventListener("abort",c)));const b=rL(s.url);if(b&&It.protocols.indexOf(b)===-1){n(new he("Unsupported protocol "+b+":",he.ERR_BAD_REQUEST,t));return}g.send(i||null)})},dL=(t,e)=>{const{length:r}=t=t?t.filter(Boolean):[];if(e||r){let n=new AbortController,s;const i=function(u){if(!s){s=!0,a();const c=u instanceof Error?u:this.reason;n.abort(c instanceof he?c:new Qi(c instanceof Error?c.message:c))}};let o=e&&setTimeout(()=>{o=null,i(new he(`timeout ${e} of ms exceeded`,he.ETIMEDOUT))},e);const a=()=>{t&&(o&&clearTimeout(o),o=null,t.forEach(u=>{u.unsubscribe?u.unsubscribe(i):u.removeEventListener("abort",i)}),t=null)};t.forEach(u=>u.addEventListener("abort",i));const{signal:l}=n;return l.unsubscribe=()=>V.asap(a),l}},hL=function*(t,e){let r=t.byteLength;if(r<e){yield t;return}let n=0,s;for(;n<r;)s=n+e,yield t.slice(n,s),n=s},fL=async function*(t,e){for await(const r of pL(t))yield*hL(r,e)},pL=async function*(t){if(t[Symbol.asyncIterator]){yield*t;return}const e=t.getReader();try{for(;;){const{done:r,value:n}=await e.read();if(r)break;yield n}}finally{await e.cancel()}},db=(t,e,r,n)=>{const s=fL(t,e);let i=0,o,a=l=>{o||(o=!0,n&&n(l))};return new ReadableStream({async pull(l){try{const{done:u,value:c}=await s.next();if(u){a(),l.close();return}let d=c.byteLength;if(r){let p=i+=d;r(p)}l.enqueue(new Uint8Array(c))}catch(u){throw a(u),u}},cancel(l){return a(l),s.return()}},{highWaterMark:2})},hb=64*1024,{isFunction:nl}=V,mL=(({Request:t,Response:e})=>({Request:t,Response:e}))(V.global),{ReadableStream:fb,TextEncoder:pb}=V.global,mb=(t,...e)=>{try{return!!t(...e)}catch{return!1}},gL=t=>{t=V.merge.call({skipUndefined:!0},mL,t);const{fetch:e,Request:r,Response:n}=t,s=e?nl(e):typeof fetch=="function",i=nl(r),o=nl(n);if(!s)return!1;const a=s&&nl(fb),l=s&&(typeof pb=="function"?(h=>m=>h.encode(m))(new pb):async h=>new Uint8Array(await new r(h).arrayBuffer())),u=i&&a&&mb(()=>{let h=!1;const m=new r(It.origin,{body:new fb,method:"POST",get duplex(){return h=!0,"half"}}).headers.has("Content-Type");return h&&!m}),c=o&&a&&mb(()=>V.isReadableStream(new n("").body)),d={stream:c&&(h=>h.body)};s&&["text","arrayBuffer","blob","formData","stream"].forEach(h=>{!d[h]&&(d[h]=(m,g)=>{let v=m&&m[h];if(v)return v.call(m);throw new he(`Response type '${h}' is not supported`,he.ERR_NOT_SUPPORT,g)})});const p=async h=>{if(h==null)return 0;if(V.isBlob(h))return h.size;if(V.isSpecCompliantForm(h))return(await new r(It.origin,{method:"POST",body:h}).arrayBuffer()).byteLength;if(V.isArrayBufferView(h)||V.isArrayBuffer(h))return h.byteLength;if(V.isURLSearchParams(h)&&(h=h+""),V.isString(h))return(await l(h)).byteLength},f=async(h,m)=>{const g=V.toFiniteNumber(h.getContentLength());return g??p(m)};return async h=>{let{url:m,method:g,data:v,signal:b,cancelToken:w,timeout:_,onDownloadProgress:S,onUploadProgress:k,responseType:O,headers:R,withCredentials:C="same-origin",fetchOptions:A}=v1(h),j=e||fetch;O=O?(O+"").toLowerCase():"text";let $=dL([b,w&&w.toAbortSignal()],_),y=null;const I=$&&$.unsubscribe&&(()=>{$.unsubscribe()});let q;try{if(k&&u&&g!=="get"&&g!=="head"&&(q=await f(R,v))!==0){let re=new r(m,{method:"POST",body:v,duplex:"half"}),_e;if(V.isFormData(v)&&(_e=re.headers.get("content-type"))&&R.setContentType(_e),re.body){const[at,st]=lb(q,uc(cb(k)));v=db(re.body,hb,at,st)}}V.isString(C)||(C=C?"include":"omit");const D=i&&"credentials"in r.prototype,z={...A,signal:$,method:g.toUpperCase(),headers:R.normalize().toJSON(),body:v,duplex:"half",credentials:D?C:void 0};y=i&&new r(m,z);let P=await(i?j(y,A):j(m,z));const ee=c&&(O==="stream"||O==="response");if(c&&(S||ee&&I)){const re={};["status","statusText","headers"].forEach(_t=>{re[_t]=P[_t]});const _e=V.toFiniteNumber(P.headers.get("content-length")),[at,st]=S&&lb(_e,uc(cb(S),!0))||[];P=new n(db(P.body,hb,at,()=>{st&&st(),I&&I()}),re)}O=O||"text";let ye=await d[V.findKey(d,O)||"text"](P,h);return!ee&&I&&I(),await new Promise((re,_e)=>{y1(re,_e,{data:ye,headers:rr.from(P.headers),status:P.status,statusText:P.statusText,config:h,request:y})})}catch(D){throw I&&I(),D&&D.name==="TypeError"&&/Load failed|fetch/i.test(D.message)?Object.assign(new he("Network Error",he.ERR_NETWORK,h,y),{cause:D.cause||D}):he.from(D,D&&D.code,h,y)}}},yL=new Map,w1=t=>{let e=t?t.env:{};const{fetch:r,Request:n,Response:s}=e,i=[n,s,r];let o=i.length,a=o,l,u,c=yL;for(;a--;)l=i[a],u=c.get(l),u===void 0&&c.set(l,u=a?new Map:gL(e)),c=u;return u};w1();const Xh={http:II,xhr:uL,fetch:{get:w1}};V.forEach(Xh,(t,e)=>{if(t){try{Object.defineProperty(t,"name",{value:e})}catch{}Object.defineProperty(t,"adapterName",{value:e})}});const gb=t=>`- ${t}`,bL=t=>V.isFunction(t)||t===null||t===!1,_1={getAdapter:(t,e)=>{t=V.isArray(t)?t:[t];const{length:r}=t;let n,s;const i={};for(let o=0;o<r;o++){n=t[o];let a;if(s=n,!bL(n)&&(s=Xh[(a=String(n)).toLowerCase()],s===void 0))throw new he(`Unknown adapter '${a}'`);if(s&&(V.isFunction(s)||(s=s.get(e))))break;i[a||"#"+o]=s}if(!s){const o=Object.entries(i).map(([l,u])=>`adapter ${l} `+(u===!1?"is not supported by the environment":"is not available in the build"));let a=r?o.length>1?`since :
`+o.map(gb).join(`
`):" "+gb(o[0]):"as no adapter specified";throw new he("There is no suitable adapter to dispatch the request "+a,"ERR_NOT_SUPPORT")}return s},adapters:Xh};function Hd(t){if(t.cancelToken&&t.cancelToken.throwIfRequested(),t.signal&&t.signal.aborted)throw new Qi(null,t)}function yb(t){return Hd(t),t.headers=rr.from(t.headers),t.data=qd.call(t,t.transformRequest),["post","put","patch"].indexOf(t.method)!==-1&&t.headers.setContentType("application/x-www-form-urlencoded",!1),_1.getAdapter(t.adapter||xa.adapter,t)(t).then(function(n){return Hd(t),n.data=qd.call(t,t.transformResponse,n),n.headers=rr.from(n.headers),n},function(n){return g1(n)||(Hd(t),n&&n.response&&(n.response.data=qd.call(t,t.transformResponse,n.response),n.response.headers=rr.from(n.response.headers))),Promise.reject(n)})}const E1="1.12.2",iu={};["object","boolean","number","function","string","symbol"].forEach((t,e)=>{iu[t]=function(n){return typeof n===t||"a"+(e<1?"n ":" ")+t}});const bb={};iu.transitional=function(e,r,n){function s(i,o){return"[Axios v"+E1+"] Transitional option '"+i+"'"+o+(n?". "+n:"")}return(i,o,a)=>{if(e===!1)throw new he(s(o," has been removed"+(r?" in "+r:"")),he.ERR_DEPRECATED);return r&&!bb[o]&&(bb[o]=!0,console.warn(s(o," has been deprecated since v"+r+" and will be removed in the near future"))),e?e(i,o,a):!0}};iu.spelling=function(e){return(r,n)=>(console.warn(`${n} is likely a misspelling of ${e}`),!0)};function vL(t,e,r){if(typeof t!="object")throw new he("options must be an object",he.ERR_BAD_OPTION_VALUE);const n=Object.keys(t);let s=n.length;for(;s-- >0;){const i=n[s],o=e[i];if(o){const a=t[i],l=a===void 0||o(a,i,t);if(l!==!0)throw new he("option "+i+" must be "+l,he.ERR_BAD_OPTION_VALUE);continue}if(r!==!0)throw new he("Unknown option "+i,he.ERR_BAD_OPTION)}}const El={assertOptions:vL,validators:iu},zr=El.validators;let Fs=class{constructor(e){this.defaults=e||{},this.interceptors={request:new ob,response:new ob}}async request(e,r){try{return await this._request(e,r)}catch(n){if(n instanceof Error){let s={};Error.captureStackTrace?Error.captureStackTrace(s):s=new Error;const i=s.stack?s.stack.replace(/^.+\n/,""):"";try{n.stack?i&&!String(n.stack).endsWith(i.replace(/^.+\n.+\n/,""))&&(n.stack+=`
`+i):n.stack=i}catch{}}throw n}}_request(e,r){typeof e=="string"?(r=r||{},r.url=e):r=e||{},r=qs(this.defaults,r);const{transitional:n,paramsSerializer:s,headers:i}=r;n!==void 0&&El.assertOptions(n,{silentJSONParsing:zr.transitional(zr.boolean),forcedJSONParsing:zr.transitional(zr.boolean),clarifyTimeoutError:zr.transitional(zr.boolean)},!1),s!=null&&(V.isFunction(s)?r.paramsSerializer={serialize:s}:El.assertOptions(s,{encode:zr.function,serialize:zr.function},!0)),r.allowAbsoluteUrls!==void 0||(this.defaults.allowAbsoluteUrls!==void 0?r.allowAbsoluteUrls=this.defaults.allowAbsoluteUrls:r.allowAbsoluteUrls=!0),El.assertOptions(r,{baseUrl:zr.spelling("baseURL"),withXsrfToken:zr.spelling("withXSRFToken")},!0),r.method=(r.method||this.defaults.method||"get").toLowerCase();let o=i&&V.merge(i.common,i[r.method]);i&&V.forEach(["delete","get","head","post","put","patch","common"],h=>{delete i[h]}),r.headers=rr.concat(o,i);const a=[];let l=!0;this.interceptors.request.forEach(function(m){typeof m.runWhen=="function"&&m.runWhen(r)===!1||(l=l&&m.synchronous,a.unshift(m.fulfilled,m.rejected))});const u=[];this.interceptors.response.forEach(function(m){u.push(m.fulfilled,m.rejected)});let c,d=0,p;if(!l){const h=[yb.bind(this),void 0];for(h.unshift(...a),h.push(...u),p=h.length,c=Promise.resolve(r);d<p;)c=c.then(h[d++],h[d++]);return c}p=a.length;let f=r;for(;d<p;){const h=a[d++],m=a[d++];try{f=h(f)}catch(g){m.call(this,g);break}}try{c=yb.call(this,f)}catch(h){return Promise.reject(h)}for(d=0,p=u.length;d<p;)c=c.then(u[d++],u[d++]);return c}getUri(e){e=qs(this.defaults,e);const r=b1(e.baseURL,e.url,e.allowAbsoluteUrls);return f1(r,e.params,e.paramsSerializer)}};V.forEach(["delete","get","head","options"],function(e){Fs.prototype[e]=function(r,n){return this.request(qs(n||{},{method:e,url:r,data:(n||{}).data}))}});V.forEach(["post","put","patch"],function(e){function r(n){return function(i,o,a){return this.request(qs(a||{},{method:e,headers:n?{"Content-Type":"multipart/form-data"}:{},url:i,data:o}))}}Fs.prototype[e]=r(),Fs.prototype[e+"Form"]=r(!0)});let wL=class S1{constructor(e){if(typeof e!="function")throw new TypeError("executor must be a function.");let r;this.promise=new Promise(function(i){r=i});const n=this;this.promise.then(s=>{if(!n._listeners)return;let i=n._listeners.length;for(;i-- >0;)n._listeners[i](s);n._listeners=null}),this.promise.then=s=>{let i;const o=new Promise(a=>{n.subscribe(a),i=a}).then(s);return o.cancel=function(){n.unsubscribe(i)},o},e(function(i,o,a){n.reason||(n.reason=new Qi(i,o,a),r(n.reason))})}throwIfRequested(){if(this.reason)throw this.reason}subscribe(e){if(this.reason){e(this.reason);return}this._listeners?this._listeners.push(e):this._listeners=[e]}unsubscribe(e){if(!this._listeners)return;const r=this._listeners.indexOf(e);r!==-1&&this._listeners.splice(r,1)}toAbortSignal(){const e=new AbortController,r=n=>{e.abort(n)};return this.subscribe(r),e.signal.unsubscribe=()=>this.unsubscribe(r),e.signal}static source(){let e;return{token:new S1(function(s){e=s}),cancel:e}}};function _L(t){return function(r){return t.apply(null,r)}}function EL(t){return V.isObject(t)&&t.isAxiosError===!0}const Yh={Continue:100,SwitchingProtocols:101,Processing:102,EarlyHints:103,Ok:200,Created:201,Accepted:202,NonAuthoritativeInformation:203,NoContent:204,ResetContent:205,PartialContent:206,MultiStatus:207,AlreadyReported:208,ImUsed:226,MultipleChoices:300,MovedPermanently:301,Found:302,SeeOther:303,NotModified:304,UseProxy:305,Unused:306,TemporaryRedirect:307,PermanentRedirect:308,BadRequest:400,Unauthorized:401,PaymentRequired:402,Forbidden:403,NotFound:404,MethodNotAllowed:405,NotAcceptable:406,ProxyAuthenticationRequired:407,RequestTimeout:408,Conflict:409,Gone:410,LengthRequired:411,PreconditionFailed:412,PayloadTooLarge:413,UriTooLong:414,UnsupportedMediaType:415,RangeNotSatisfiable:416,ExpectationFailed:417,ImATeapot:418,MisdirectedRequest:421,UnprocessableEntity:422,Locked:423,FailedDependency:424,TooEarly:425,UpgradeRequired:426,PreconditionRequired:428,TooManyRequests:429,RequestHeaderFieldsTooLarge:431,UnavailableForLegalReasons:451,InternalServerError:500,NotImplemented:501,BadGateway:502,ServiceUnavailable:503,GatewayTimeout:504,HttpVersionNotSupported:505,VariantAlsoNegotiates:506,InsufficientStorage:507,LoopDetected:508,NotExtended:510,NetworkAuthenticationRequired:511};Object.entries(Yh).forEach(([t,e])=>{Yh[e]=t});function x1(t){const e=new Fs(t),r=t1(Fs.prototype.request,e);return V.extend(r,Fs.prototype,e,{allOwnKeys:!0}),V.extend(r,e,null,{allOwnKeys:!0}),r.create=function(s){return x1(qs(t,s))},r}const tt=x1(xa);tt.Axios=Fs;tt.CanceledError=Qi;tt.CancelToken=wL;tt.isCancel=g1;tt.VERSION=E1;tt.toFormData=su;tt.AxiosError=he;tt.Cancel=tt.CanceledError;tt.all=function(e){return Promise.all(e)};tt.spread=_L;tt.isAxiosError=EL;tt.mergeConfig=qs;tt.AxiosHeaders=rr;tt.formToJSON=t=>m1(V.isHTMLForm(t)?new FormData(t):t);tt.getAdapter=_1.getAdapter;tt.HttpStatusCode=Yh;tt.default=tt;const{Axios:FB,AxiosError:MB,CanceledError:DB,isCancel:VB,CancelToken:BB,VERSION:UB,all:jB,Cancel:qB,isAxiosError:HB,spread:zB,toFormData:WB,AxiosHeaders:KB,HttpStatusCode:GB,formToJSON:JB,getAdapter:XB,mergeConfig:YB}=tt;function Qh(t,e){let r;return function(...n){clearTimeout(r),r=setTimeout(()=>t.apply(this,n),e)}}function kr(t,e){return document.dispatchEvent(new CustomEvent(`inertia:${t}`,e))}var vb=t=>kr("before",{cancelable:!0,detail:{visit:t}}),SL=t=>kr("error",{detail:{errors:t}}),xL=t=>kr("exception",{cancelable:!0,detail:{exception:t}}),CL=t=>kr("finish",{detail:{visit:t}}),AL=t=>kr("invalid",{cancelable:!0,detail:{response:t}}),$L=t=>kr("beforeUpdate",{detail:{page:t}}),Lo=t=>kr("navigate",{detail:{page:t}}),TL=t=>kr("progress",{detail:{progress:t}}),kL=t=>kr("start",{detail:{visit:t}}),PL=t=>kr("success",{detail:{page:t}}),OL=(t,e)=>kr("prefetched",{detail:{fetchedAt:Date.now(),response:t.data,visit:e}}),RL=t=>kr("prefetching",{detail:{visit:t}}),zt=class{static set(t,e){typeof window<"u"&&window.sessionStorage.setItem(t,JSON.stringify(e))}static get(t){if(typeof window<"u")return JSON.parse(window.sessionStorage.getItem(t)||"null")}static merge(t,e){const r=this.get(t);r===null?this.set(t,e):this.set(t,{...r,...e})}static remove(t){typeof window<"u"&&window.sessionStorage.removeItem(t)}static removeNested(t,e){const r=this.get(t);r!==null&&(delete r[e],this.set(t,r))}static exists(t){try{return this.get(t)!==null}catch{return!1}}static clear(){typeof window<"u"&&window.sessionStorage.clear()}};zt.locationVisitKey="inertiaLocationVisit";var NL=async t=>{if(typeof window>"u")throw new Error("Unable to encrypt history");const e=C1(),r=await A1(),n=await VL(r);if(!n)throw new Error("Unable to encrypt history");return await LL(e,n,t)},Fi={key:"historyKey",iv:"historyIv"},IL=async t=>{const e=C1(),r=await A1();if(!r)throw new Error("Unable to decrypt history");return await FL(e,r,t)},LL=async(t,e,r)=>{if(typeof window>"u")throw new Error("Unable to encrypt history");if(typeof window.crypto.subtle>"u")return console.warn("Encryption is not supported in this environment. SSL is required."),Promise.resolve(r);const n=new TextEncoder,s=JSON.stringify(r),i=new Uint8Array(s.length*3),o=n.encodeInto(s,i);return window.crypto.subtle.encrypt({name:"AES-GCM",iv:t},e,i.subarray(0,o.written))},FL=async(t,e,r)=>{if(typeof window.crypto.subtle>"u")return console.warn("Decryption is not supported in this environment. SSL is required."),Promise.resolve(r);const n=await window.crypto.subtle.decrypt({name:"AES-GCM",iv:t},e,r);return JSON.parse(new TextDecoder().decode(n))},C1=()=>{const t=zt.get(Fi.iv);if(t)return new Uint8Array(t);const e=window.crypto.getRandomValues(new Uint8Array(12));return zt.set(Fi.iv,Array.from(e)),e},ML=async()=>typeof window.crypto.subtle>"u"?(console.warn("Encryption is not supported in this environment. SSL is required."),Promise.resolve(null)):window.crypto.subtle.generateKey({name:"AES-GCM",length:256},!0,["encrypt","decrypt"]),DL=async t=>{if(typeof window.crypto.subtle>"u")return console.warn("Encryption is not supported in this environment. SSL is required."),Promise.resolve();const e=await window.crypto.subtle.exportKey("raw",t);zt.set(Fi.key,Array.from(new Uint8Array(e)))},VL=async t=>{if(t)return t;const e=await ML();return e?(await DL(e),e):null},A1=async()=>{const t=zt.get(Fi.key);return t?await window.crypto.subtle.importKey("raw",new Uint8Array(t),{name:"AES-GCM",length:256},!0,["encrypt","decrypt"]):null},Ir=class{static save(){Te.saveScrollPositions(Array.from(this.regions()).map(t=>({top:t.scrollTop,left:t.scrollLeft})))}static regions(){return document.querySelectorAll("[scroll-region]")}static reset(){const t=typeof window<"u"?window.location.hash:null;t||window.scrollTo(0,0),this.regions().forEach(e=>{typeof e.scrollTo=="function"?e.scrollTo(0,0):(e.scrollTop=0,e.scrollLeft=0)}),this.save(),t&&setTimeout(()=>{const e=document.getElementById(t.slice(1));e?e.scrollIntoView():window.scrollTo(0,0)})}static restore(t){typeof window>"u"||window.requestAnimationFrame(()=>{this.restoreDocument(),this.regions().forEach((e,r)=>{const n=t[r];n&&(typeof e.scrollTo=="function"?e.scrollTo(n.left,n.top):(e.scrollTop=n.top,e.scrollLeft=n.left))})})}static restoreDocument(){const t=Te.getDocumentScrollPosition();window.scrollTo(t.left,t.top)}static onScroll(t){const e=t.target;typeof e.hasAttribute=="function"&&e.hasAttribute("scroll-region")&&this.save()}static onWindowScroll(){Te.saveDocumentScrollPosition({top:window.scrollY,left:window.scrollX})}};function Zh(t){return t instanceof File||t instanceof Blob||t instanceof FileList&&t.length>0||t instanceof FormData&&Array.from(t.values()).some(e=>Zh(e))||typeof t=="object"&&t!==null&&Object.values(t).some(e=>Zh(e))}var ef=t=>t instanceof FormData;function $1(t,e=new FormData,r=null){t=t||{};for(const n in t)Object.prototype.hasOwnProperty.call(t,n)&&k1(e,T1(r,n),t[n]);return e}function T1(t,e){return t?t+"["+e+"]":e}function k1(t,e,r){if(Array.isArray(r))return Array.from(r.keys()).forEach(n=>k1(t,T1(e,n.toString()),r[n]));if(r instanceof Date)return t.append(e,r.toISOString());if(r instanceof File)return t.append(e,r,r.name);if(r instanceof Blob)return t.append(e,r);if(typeof r=="boolean")return t.append(e,r?"1":"0");if(typeof r=="string")return t.append(e,r);if(typeof r=="number")return t.append(e,`${r}`);if(r==null)return t.append(e,"");$1(r,t,e)}function Jn(t){return new URL(t.toString(),typeof window>"u"?void 0:window.location.toString())}var BL=(t,e,r,n,s)=>{let i=typeof t=="string"?Jn(t):t;if((Zh(e)||n)&&!ef(e)&&(e=$1(e)),ef(e))return[i,e];const[o,a]=UL(r,i,e,s);return[Jn(o),a]};function UL(t,e,r,n="brackets"){const s=t==="get"&&!ef(r)&&Object.keys(r).length>0,i=jL(e.toString()),o=i||e.toString().startsWith("/")||e.toString()==="",a=!o&&!e.toString().startsWith("#")&&!e.toString().startsWith("?"),l=/^[.]{1,2}([/]|$)/.test(e.toString()),u=e.toString().includes("?")||s,c=e.toString().includes("#"),d=new URL(e.toString(),typeof window>"u"?"http://localhost":window.location.toString());if(s){const p={ignoreQueryPrefix:!0,parseArrays:!1};d.search=rb.stringify({...rb.parse(d.search,p),...r},{encodeValuesOnly:!0,arrayFormat:n})}return[[i?`${d.protocol}//${d.host}`:"",o?d.pathname:"",a?d.pathname.substring(l?0:1):"",u?d.search:"",c?d.hash:""].join(""),s?{}:r]}function dc(t){return t=new URL(t.href),t.hash="",t}var wb=(t,e)=>{t.hash&&!e.hash&&dc(t).href===e.href&&(e.hash=t.hash)},tf=(t,e)=>dc(t).href===dc(e).href;function _b(t){return t!==null&&typeof t=="object"&&t!==void 0&&"url"in t&&"method"in t}function jL(t){return/^[a-z][a-z0-9+.-]*:\/\//i.test(t)}var qL=class{constructor(){this.componentId={},this.listeners=[],this.isFirstPageLoad=!0,this.cleared=!1,this.pendingDeferredProps=null}init({initialPage:t,swapComponent:e,resolveComponent:r}){return this.page=t,this.swapComponent=e,this.resolveComponent=r,this}set(t,{replace:e=!1,preserveScroll:r=!1,preserveState:n=!1}={}){Object.keys(t.deferredProps||{}).length&&(this.pendingDeferredProps={deferredProps:t.deferredProps,component:t.component,url:t.url}),this.componentId={};const s=this.componentId;return t.clearHistory&&Te.clear(),this.resolve(t.component).then(i=>{if(s!==this.componentId)return;t.rememberedState??(t.rememberedState={});const o=typeof window<"u"?window.location:new URL(t.url);return e=e||tf(Jn(t.url),o),new Promise(a=>{e?Te.replaceState(t,()=>a(null)):Te.pushState(t,()=>a(null))}).then(()=>{const a=!this.isTheSame(t);return this.page=t,this.cleared=!1,a&&this.fireEventsFor("newComponent"),this.isFirstPageLoad&&this.fireEventsFor("firstLoad"),this.isFirstPageLoad=!1,this.swap({component:i,page:t,preserveState:n}).then(()=>{r||Ir.reset(),this.pendingDeferredProps&&this.pendingDeferredProps.component===t.component&&this.pendingDeferredProps.url===t.url&&xs.fireInternalEvent("loadDeferredProps",this.pendingDeferredProps.deferredProps),this.pendingDeferredProps=null,e||Lo(t)})})})}setQuietly(t,{preserveState:e=!1}={}){return this.resolve(t.component).then(r=>(this.page=t,this.cleared=!1,Te.setCurrent(t),this.swap({component:r,page:t,preserveState:e})))}clear(){this.cleared=!0}isCleared(){return this.cleared}get(){return this.page}merge(t){this.page={...this.page,...t}}setUrlHash(t){this.page.url.includes(t)||(this.page.url+=t)}remember(t){this.page.rememberedState=t}swap({component:t,page:e,preserveState:r}){return this.swapComponent({component:t,page:e,preserveState:r})}resolve(t){return Promise.resolve(this.resolveComponent(t))}isTheSame(t){return this.page.component===t.component}on(t,e){return this.listeners.push({event:t,callback:e}),()=>{this.listeners=this.listeners.filter(r=>r.event!==t&&r.callback!==e)}}fireEventsFor(t){this.listeners.filter(e=>e.event===t).forEach(e=>e.callback())}},oe=new qL,P1=class{constructor(){this.items=[],this.processingPromise=null}add(t){return this.items.push(t),this.process()}process(){return this.processingPromise??(this.processingPromise=this.processNext().finally(()=>{this.processingPromise=null})),this.processingPromise}processNext(){const t=this.items.shift();return t?Promise.resolve(t()).then(()=>this.processNext()):Promise.resolve()}},_o=typeof window>"u",po=new P1,Eb=!_o&&/CriOS/.test(window.navigator.userAgent),HL=class{constructor(){this.rememberedState="rememberedState",this.scrollRegions="scrollRegions",this.preserveUrl=!1,this.current={},this.initialState=null}remember(t,e){this.replaceState({...oe.get(),rememberedState:{...oe.get()?.rememberedState??{},[e]:t}})}restore(t){if(!_o)return this.current[this.rememberedState]?this.current[this.rememberedState]?.[t]:this.initialState?.[this.rememberedState]?.[t]}pushState(t,e=null){if(!_o){if(this.preserveUrl){e&&e();return}this.current=t,po.add(()=>this.getPageData(t).then(r=>{const n=()=>this.doPushState({page:r},t.url).then(()=>e?.());return Eb?new Promise(s=>{setTimeout(()=>n().then(s))}):n()}))}}getPageData(t){return new Promise(e=>t.encryptHistory?NL(t).then(e):e(t))}processQueue(){return po.process()}decrypt(t=null){if(_o)return Promise.resolve(t??oe.get());const e=t??window.history.state?.page;return this.decryptPageData(e).then(r=>{if(!r)throw new Error("Unable to decrypt history");return this.initialState===null?this.initialState=r??void 0:this.current=r??{},r})}decryptPageData(t){return t instanceof ArrayBuffer?IL(t):Promise.resolve(t)}saveScrollPositions(t){po.add(()=>Promise.resolve().then(()=>{if(window.history.state?.page)return this.doReplaceState({page:window.history.state.page,scrollRegions:t})}))}saveDocumentScrollPosition(t){po.add(()=>Promise.resolve().then(()=>{if(window.history.state?.page)return this.doReplaceState({page:window.history.state.page,documentScrollPosition:t})}))}getScrollRegions(){return window.history.state?.scrollRegions||[]}getDocumentScrollPosition(){return window.history.state?.documentScrollPosition||{top:0,left:0}}replaceState(t,e=null){if(oe.merge(t),!_o){if(this.preserveUrl){e&&e();return}this.current=t,po.add(()=>this.getPageData(t).then(r=>{const n=()=>this.doReplaceState({page:r},t.url).then(()=>e?.());return Eb?new Promise(s=>{setTimeout(()=>n().then(s))}):n()}))}}doReplaceState(t,e){return Promise.resolve().then(()=>window.history.replaceState({...t,scrollRegions:t.scrollRegions??window.history.state?.scrollRegions,documentScrollPosition:t.documentScrollPosition??window.history.state?.documentScrollPosition},"",e))}doPushState(t,e){return Promise.resolve().then(()=>window.history.pushState(t,"",e))}getState(t,e){return this.current?.[t]??e}deleteState(t){this.current[t]!==void 0&&(delete this.current[t],this.replaceState(this.current))}clearInitialState(t){this.initialState&&this.initialState[t]!==void 0&&delete this.initialState[t]}hasAnyState(){return!!this.getAllState()}clear(){zt.remove(Fi.key),zt.remove(Fi.iv)}setCurrent(t){this.current=t}isValidState(t){return!!t.page}getAllState(){return this.current}};typeof window<"u"&&window.history.scrollRestoration&&(window.history.scrollRestoration="manual");var Te=new HL,zL=class{constructor(){this.internalListeners=[]}init(){typeof window<"u"&&(window.addEventListener("popstate",this.handlePopstateEvent.bind(this)),window.addEventListener("scroll",Qh(Ir.onWindowScroll.bind(Ir),100),!0)),typeof document<"u"&&document.addEventListener("scroll",Qh(Ir.onScroll.bind(Ir),100),!0)}onGlobalEvent(t,e){const r=(n=>{const s=e(n);n.cancelable&&!n.defaultPrevented&&s===!1&&n.preventDefault()});return this.registerListener(`inertia:${t}`,r)}on(t,e){return this.internalListeners.push({event:t,listener:e}),()=>{this.internalListeners=this.internalListeners.filter(r=>r.listener!==e)}}onMissingHistoryItem(){oe.clear(),this.fireInternalEvent("missingHistoryItem")}fireInternalEvent(t,...e){this.internalListeners.filter(r=>r.event===t).forEach(r=>r.listener(...e))}registerListener(t,e){return document.addEventListener(t,e),()=>document.removeEventListener(t,e)}handlePopstateEvent(t){const e=t.state||null;if(e===null){const r=Jn(oe.get().url);r.hash=window.location.hash,Te.replaceState({...oe.get(),url:r.href}),Ir.reset();return}if(!Te.isValidState(e))return this.onMissingHistoryItem();Te.decrypt(e.page).then(r=>{if(oe.get().version!==r.version){this.onMissingHistoryItem();return}er.cancelAll(),oe.setQuietly(r,{preserveState:!1}).then(()=>{Ir.restore(Te.getScrollRegions()),Lo(oe.get())})}).catch(()=>{this.onMissingHistoryItem()})}},xs=new zL,WL=class{constructor(){this.type=this.resolveType()}resolveType(){return typeof window>"u"?"navigate":window.performance&&window.performance.getEntriesByType&&window.performance.getEntriesByType("navigation").length>0?window.performance.getEntriesByType("navigation")[0].type:"navigate"}get(){return this.type}isBackForward(){return this.type==="back_forward"}isReload(){return this.type==="reload"}},zd=new WL,KL=class{static handle(){this.clearRememberedStateOnReload(),[this.handleBackForward,this.handleLocation,this.handleDefault].find(e=>e.bind(this)())}static clearRememberedStateOnReload(){zd.isReload()&&(Te.deleteState(Te.rememberedState),Te.clearInitialState(Te.rememberedState))}static handleBackForward(){if(!zd.isBackForward()||!Te.hasAnyState())return!1;const t=Te.getScrollRegions();return Te.decrypt().then(e=>{oe.set(e,{preserveScroll:!0,preserveState:!0}).then(()=>{Ir.restore(t),Lo(oe.get())})}).catch(()=>{xs.onMissingHistoryItem()}),!0}static handleLocation(){if(!zt.exists(zt.locationVisitKey))return!1;const t=zt.get(zt.locationVisitKey)||{};return zt.remove(zt.locationVisitKey),typeof window<"u"&&oe.setUrlHash(window.location.hash),Te.decrypt(oe.get()).then(()=>{const e=Te.getState(Te.rememberedState,{}),r=Te.getScrollRegions();oe.remember(e),oe.set(oe.get(),{preserveScroll:t.preserveScroll,preserveState:!0}).then(()=>{t.preserveScroll&&Ir.restore(r),Lo(oe.get())})}).catch(()=>{xs.onMissingHistoryItem()}),!0}static handleDefault(){typeof window<"u"&&oe.setUrlHash(window.location.hash),oe.set(oe.get(),{preserveScroll:!0,preserveState:!0}).then(()=>{zd.isReload()&&Ir.restore(Te.getScrollRegions()),Lo(oe.get())})}},GL=class{constructor(t,e,r){this.id=null,this.throttle=!1,this.keepAlive=!1,this.cbCount=0,this.keepAlive=r.keepAlive??!1,this.cb=e,this.interval=t,(r.autoStart??!0)&&this.start()}stop(){this.id&&clearInterval(this.id)}start(){typeof window>"u"||(this.stop(),this.id=window.setInterval(()=>{(!this.throttle||this.cbCount%10===0)&&this.cb(),this.throttle&&this.cbCount++},this.interval))}isInBackground(t){this.throttle=this.keepAlive?!1:t,this.throttle&&(this.cbCount=0)}},JL=class{constructor(){this.polls=[],this.setupVisibilityListener()}add(t,e,r){const n=new GL(t,e,r);return this.polls.push(n),{stop:()=>n.stop(),start:()=>n.start()}}clear(){this.polls.forEach(t=>t.stop()),this.polls=[]}setupVisibilityListener(){typeof document>"u"||document.addEventListener("visibilitychange",()=>{this.polls.forEach(t=>t.isInBackground(document.hidden))},!1)}},XL=new JL,O1=(t,e,r)=>{if(t===e)return!0;for(const n in t)if(!r.includes(n)&&t[n]!==e[n]&&!YL(t[n],e[n]))return!1;return!0},YL=(t,e)=>{switch(typeof t){case"object":return O1(t,e,[]);case"function":return t.toString()===e.toString();default:return t===e}},QL={ms:1,s:1e3,m:1e3*60,h:1e3*60*60,d:1e3*60*60*24},Sb=t=>{if(typeof t=="number")return t;for(const[e,r]of Object.entries(QL))if(t.endsWith(e))return parseFloat(t)*r;return parseInt(t)},ZL=class{constructor(){this.cached=[],this.inFlightRequests=[],this.removalTimers=[],this.currentUseId=null}add(t,e,{cacheFor:r,cacheTags:n}){if(this.findInFlight(t))return Promise.resolve();const i=this.findCached(t);if(!t.fresh&&i&&i.staleTimestamp>Date.now())return Promise.resolve();const[o,a]=this.extractStaleValues(r),l=new Promise((u,c)=>{e({...t,onCancel:()=>{this.remove(t),t.onCancel(),c()},onError:d=>{this.remove(t),t.onError(d),c()},onPrefetching(d){t.onPrefetching(d)},onPrefetched(d,p){t.onPrefetched(d,p)},onPrefetchResponse(d){u(d)},onPrefetchError(d){dn.removeFromInFlight(t),c(d)}})}).then(u=>(this.remove(t),this.cached.push({params:{...t},staleTimestamp:Date.now()+o,response:l,singleUse:a===0,timestamp:Date.now(),inFlight:!1,tags:Array.isArray(n)?n:[n]}),this.scheduleForRemoval(t,a),this.removeFromInFlight(t),u.handlePrefetch(),u));return this.inFlightRequests.push({params:{...t},response:l,staleTimestamp:null,inFlight:!0}),l}removeAll(){this.cached=[],this.removalTimers.forEach(t=>{clearTimeout(t.timer)}),this.removalTimers=[]}removeByTags(t){this.cached=this.cached.filter(e=>!e.tags.some(r=>t.includes(r)))}remove(t){this.cached=this.cached.filter(e=>!this.paramsAreEqual(e.params,t)),this.clearTimer(t)}removeFromInFlight(t){this.inFlightRequests=this.inFlightRequests.filter(e=>!this.paramsAreEqual(e.params,t))}extractStaleValues(t){const[e,r]=this.cacheForToStaleAndExpires(t);return[Sb(e),Sb(r)]}cacheForToStaleAndExpires(t){if(!Array.isArray(t))return[t,t];switch(t.length){case 0:return[0,0];case 1:return[t[0],t[0]];default:return[t[0],t[1]]}}clearTimer(t){const e=this.removalTimers.find(r=>this.paramsAreEqual(r.params,t));e&&(clearTimeout(e.timer),this.removalTimers=this.removalTimers.filter(r=>r!==e))}scheduleForRemoval(t,e){if(!(typeof window>"u")&&(this.clearTimer(t),e>0)){const r=window.setTimeout(()=>this.remove(t),e);this.removalTimers.push({params:t,timer:r})}}get(t){return this.findCached(t)||this.findInFlight(t)}use(t,e){const r=`${e.url.pathname}-${Date.now()}-${Math.random().toString(36).substring(7)}`;return this.currentUseId=r,t.response.then(n=>{if(this.currentUseId===r)return n.mergeParams({...e,onPrefetched:()=>{}}),this.removeSingleUseItems(e),n.handle()})}removeSingleUseItems(t){this.cached=this.cached.filter(e=>this.paramsAreEqual(e.params,t)?!e.singleUse:!0)}findCached(t){return this.cached.find(e=>this.paramsAreEqual(e.params,t))||null}findInFlight(t){return this.inFlightRequests.find(e=>this.paramsAreEqual(e.params,t))||null}withoutPurposePrefetchHeader(t){const e=Gt(t);return e.headers.Purpose==="prefetch"&&delete e.headers.Purpose,e}paramsAreEqual(t,e){return O1(this.withoutPurposePrefetchHeader(t),this.withoutPurposePrefetchHeader(e),["showProgress","replace","prefetch","onBefore","onBeforeUpdate","onStart","onProgress","onFinish","onCancel","onSuccess","onError","onPrefetched","onCancelToken","onPrefetching","async"])}},dn=new ZL,eF=class R1{constructor(e){if(this.callbacks=[],!e.prefetch)this.params=e;else{const r={onBefore:this.wrapCallback(e,"onBefore"),onBeforeUpdate:this.wrapCallback(e,"onBeforeUpdate"),onStart:this.wrapCallback(e,"onStart"),onProgress:this.wrapCallback(e,"onProgress"),onFinish:this.wrapCallback(e,"onFinish"),onCancel:this.wrapCallback(e,"onCancel"),onSuccess:this.wrapCallback(e,"onSuccess"),onError:this.wrapCallback(e,"onError"),onCancelToken:this.wrapCallback(e,"onCancelToken"),onPrefetched:this.wrapCallback(e,"onPrefetched"),onPrefetching:this.wrapCallback(e,"onPrefetching")};this.params={...e,...r,onPrefetchResponse:e.onPrefetchResponse||(()=>{}),onPrefetchError:e.onPrefetchError||(()=>{})}}}static create(e){return new R1(e)}data(){return this.params.method==="get"?null:this.params.data}queryParams(){return this.params.method==="get"?this.params.data:{}}isPartial(){return this.params.only.length>0||this.params.except.length>0||this.params.reset.length>0}onCancelToken(e){this.params.onCancelToken({cancel:e})}markAsFinished(){this.params.completed=!0,this.params.cancelled=!1,this.params.interrupted=!1}markAsCancelled({cancelled:e=!0,interrupted:r=!1}){this.params.onCancel(),this.params.completed=!1,this.params.cancelled=e,this.params.interrupted=r}wasCancelledAtAll(){return this.params.cancelled||this.params.interrupted}onFinish(){this.params.onFinish(this.params)}onStart(){this.params.onStart(this.params)}onPrefetching(){this.params.onPrefetching(this.params)}onPrefetchResponse(e){this.params.onPrefetchResponse&&this.params.onPrefetchResponse(e)}onPrefetchError(e){this.params.onPrefetchError&&this.params.onPrefetchError(e)}all(){return this.params}headers(){const e={...this.params.headers};this.isPartial()&&(e["X-Inertia-Partial-Component"]=oe.get().component);const r=this.params.only.concat(this.params.reset);return r.length>0&&(e["X-Inertia-Partial-Data"]=r.join(",")),this.params.except.length>0&&(e["X-Inertia-Partial-Except"]=this.params.except.join(",")),this.params.reset.length>0&&(e["X-Inertia-Reset"]=this.params.reset.join(",")),this.params.errorBag&&this.params.errorBag.length>0&&(e["X-Inertia-Error-Bag"]=this.params.errorBag),e}setPreserveOptions(e){this.params.preserveScroll=this.resolvePreserveOption(this.params.preserveScroll,e),this.params.preserveState=this.resolvePreserveOption(this.params.preserveState,e)}runCallbacks(){this.callbacks.forEach(({name:e,args:r})=>{this.params[e](...r)})}merge(e){this.params={...this.params,...e}}wrapCallback(e,r){return(...n)=>{this.recordCallback(r,n),e[r](...n)}}recordCallback(e,r){this.callbacks.push({name:e,args:r})}resolvePreserveOption(e,r){return typeof e=="function"?e(r):e==="errors"?Object.keys(r.props.errors||{}).length>0:e}},tF={modal:null,listener:null,show(t){typeof t=="object"&&(t=`All Inertia requests must receive a valid Inertia response, however a plain JSON response was received.<hr>${JSON.stringify(t)}`);const e=document.createElement("html");e.innerHTML=t,e.querySelectorAll("a").forEach(n=>n.setAttribute("target","_top")),this.modal=document.createElement("div"),this.modal.style.position="fixed",this.modal.style.width="100vw",this.modal.style.height="100vh",this.modal.style.padding="50px",this.modal.style.boxSizing="border-box",this.modal.style.backgroundColor="rgba(0, 0, 0, .6)",this.modal.style.zIndex=2e5,this.modal.addEventListener("click",()=>this.hide());const r=document.createElement("iframe");if(r.style.backgroundColor="white",r.style.borderRadius="5px",r.style.width="100%",r.style.height="100%",this.modal.appendChild(r),document.body.prepend(this.modal),document.body.style.overflow="hidden",!r.contentWindow)throw new Error("iframe not yet ready.");r.contentWindow.document.open(),r.contentWindow.document.write(e.outerHTML),r.contentWindow.document.close(),this.listener=this.hideOnEscape.bind(this),document.addEventListener("keydown",this.listener)},hide(){this.modal.outerHTML="",this.modal=null,document.body.style.overflow="visible",document.removeEventListener("keydown",this.listener)},hideOnEscape(t){t.keyCode===27&&this.hide()}},rF=new P1,xb=class N1{constructor(e,r,n){this.requestParams=e,this.response=r,this.originatingPage=n,this.wasPrefetched=!1}static create(e,r,n){return new N1(e,r,n)}async handlePrefetch(){tf(this.requestParams.all().url,window.location)&&this.handle()}async handle(){return rF.add(()=>this.process())}async process(){if(this.requestParams.all().prefetch)return this.wasPrefetched=!0,this.requestParams.all().prefetch=!1,this.requestParams.all().onPrefetched(this.response,this.requestParams.all()),OL(this.response,this.requestParams.all()),Promise.resolve();if(this.requestParams.runCallbacks(),!this.isInertiaResponse())return this.handleNonInertiaResponse();await Te.processQueue(),Te.preserveUrl=this.requestParams.all().preserveUrl,await this.setPage();const e=oe.get().props.errors||{};if(Object.keys(e).length>0){const r=this.getScopedErrors(e);return SL(r),this.requestParams.all().onError(r)}er.flushByCacheTags(this.requestParams.all().invalidateCacheTags||[]),this.wasPrefetched||er.flush(oe.get().url),PL(oe.get()),await this.requestParams.all().onSuccess(oe.get()),Te.preserveUrl=!1}mergeParams(e){this.requestParams.merge(e)}async handleNonInertiaResponse(){if(this.isLocationVisit()){const r=Jn(this.getHeader("x-inertia-location"));return wb(this.requestParams.all().url,r),this.locationVisit(r)}const e={...this.response,data:this.getDataFromResponse(this.response.data)};if(AL(e))return tF.show(e.data)}isInertiaResponse(){return this.hasHeader("x-inertia")}hasStatus(e){return this.response.status===e}getHeader(e){return this.response.headers[e]}hasHeader(e){return this.getHeader(e)!==void 0}isLocationVisit(){return this.hasStatus(409)&&this.hasHeader("x-inertia-location")}locationVisit(e){try{if(zt.set(zt.locationVisitKey,{preserveScroll:this.requestParams.all().preserveScroll===!0}),typeof window>"u")return;tf(window.location,e)?window.location.reload():window.location.href=e.href}catch{return!1}}async setPage(){const e=this.getDataFromResponse(this.response.data);return this.shouldSetPage(e)?(this.mergeProps(e),await this.setRememberedState(e),this.requestParams.setPreserveOptions(e),e.url=Te.preserveUrl?oe.get().url:this.pageUrl(e),this.requestParams.all().onBeforeUpdate(e),$L(e),oe.set(e,{replace:this.requestParams.all().replace,preserveScroll:this.requestParams.all().preserveScroll,preserveState:this.requestParams.all().preserveState})):Promise.resolve()}getDataFromResponse(e){if(typeof e!="string")return e;try{return JSON.parse(e)}catch{return e}}shouldSetPage(e){if(!this.requestParams.all().async||this.originatingPage.component!==e.component)return!0;if(this.originatingPage.component!==oe.get().component)return!1;const r=Jn(this.originatingPage.url),n=Jn(oe.get().url);return r.origin===n.origin&&r.pathname===n.pathname}pageUrl(e){const r=Jn(e.url);return wb(this.requestParams.all().url,r),r.pathname+r.search+r.hash}mergeProps(e){if(!this.requestParams.isPartial()||e.component!==oe.get().component)return;const r=e.mergeProps||[],n=e.prependProps||[],s=e.deepMergeProps||[],i=e.matchPropsOn||[],o=(a,l)=>{const u=Si(oe.get().props,a),c=Si(e.props,a);if(Array.isArray(c)){const d=this.mergeOrMatchItems(u||[],c,a,i,l);Es(e.props,a,d)}else if(typeof c=="object"&&c!==null){const d={...u||{},...c};Es(e.props,a,d)}};r.forEach(a=>o(a,!0)),n.forEach(a=>o(a,!1)),s.forEach(a=>{const l=oe.get().props[a],u=e.props[a],c=(d,p,f)=>Array.isArray(p)?this.mergeOrMatchItems(d,p,f,i):typeof p=="object"&&p!==null?Object.keys(p).reduce((h,m)=>(h[m]=c(d?d[m]:void 0,p[m],`${f}.${m}`),h),{...d}):p;e.props[a]=c(l,u,a)}),e.props={...oe.get().props,...e.props},oe.get().scrollProps&&(e.scrollProps={...oe.get().scrollProps||{},...e.scrollProps||{}})}mergeOrMatchItems(e,r,n,s,i=!0){const o=Array.isArray(e)?e:[],a=s.find(c=>c.split(".").slice(0,-1).join(".")===n);if(!a)return i?[...o,...r]:[...r,...o];const l=a.split(".").pop()||"",u=new Map;return r.forEach(c=>{this.hasUniqueProperty(c,l)&&u.set(c[l],c)}),i?this.appendWithMatching(o,r,u,l):this.prependWithMatching(o,r,u,l)}appendWithMatching(e,r,n,s){const i=e.map(a=>this.hasUniqueProperty(a,s)&&n.has(a[s])?n.get(a[s]):a),o=r.filter(a=>this.hasUniqueProperty(a,s)?!e.some(l=>this.hasUniqueProperty(l,s)&&l[s]===a[s]):!0);return[...i,...o]}prependWithMatching(e,r,n,s){const i=e.filter(o=>this.hasUniqueProperty(o,s)?!n.has(o[s]):!0);return[...r,...i]}hasUniqueProperty(e,r){return e&&typeof e=="object"&&r in e}async setRememberedState(e){const r=await Te.getState(Te.rememberedState,{});this.requestParams.all().preserveState&&r&&e.component===oe.get().component&&(e.rememberedState=r)}getScopedErrors(e){return this.requestParams.all().errorBag?e[this.requestParams.all().errorBag||""]||{}:e}},Cb=class I1{constructor(e,r){this.page=r,this.requestHasFinished=!1,this.requestParams=eF.create(e),this.cancelToken=new AbortController}static create(e,r){return new I1(e,r)}async send(){this.requestParams.onCancelToken(()=>this.cancel({cancelled:!0})),kL(this.requestParams.all()),this.requestParams.onStart(),this.requestParams.all().prefetch&&(this.requestParams.onPrefetching(),RL(this.requestParams.all()));const e=this.requestParams.all().prefetch;return tt({method:this.requestParams.all().method,url:dc(this.requestParams.all().url).href,data:this.requestParams.data(),params:this.requestParams.queryParams(),signal:this.cancelToken.signal,headers:this.getHeaders(),onUploadProgress:this.onProgress.bind(this),responseType:"text"}).then(r=>(this.response=xb.create(this.requestParams,r,this.page),this.response.handle())).catch(r=>r?.response?(this.response=xb.create(this.requestParams,r.response,this.page),this.response.handle()):Promise.reject(r)).catch(r=>{if(!tt.isCancel(r)&&xL(r))return e&&this.requestParams.onPrefetchError(r),Promise.reject(r)}).finally(()=>{this.finish(),e&&this.response&&this.requestParams.onPrefetchResponse(this.response)})}finish(){this.requestParams.wasCancelledAtAll()||(this.requestParams.markAsFinished(),this.fireFinishEvents())}fireFinishEvents(){this.requestHasFinished||(this.requestHasFinished=!0,CL(this.requestParams.all()),this.requestParams.onFinish())}cancel({cancelled:e=!1,interrupted:r=!1}){this.requestHasFinished||(this.cancelToken.abort(),this.requestParams.markAsCancelled({cancelled:e,interrupted:r}),this.fireFinishEvents())}onProgress(e){this.requestParams.data()instanceof FormData&&(e.percentage=e.progress?Math.round(e.progress*100):0,TL(e),this.requestParams.all().onProgress(e))}getHeaders(){const e={...this.requestParams.headers(),Accept:"text/html, application/xhtml+xml","X-Requested-With":"XMLHttpRequest","X-Inertia":!0};return oe.get().version&&(e["X-Inertia-Version"]=oe.get().version),e}},Ab=class{constructor({maxConcurrent:t,interruptible:e}){this.requests=[],this.maxConcurrent=t,this.interruptible=e}send(t){this.requests.push(t),t.send().then(()=>{this.requests=this.requests.filter(e=>e!==t)})}interruptInFlight(){this.cancel({interrupted:!0},!1)}cancelInFlight(){this.cancel({cancelled:!0},!0)}cancel({cancelled:t=!1,interrupted:e=!1}={},r){if(!this.shouldCancel(r))return;this.requests.shift()?.cancel({interrupted:e,cancelled:t})}shouldCancel(t){return t?!0:this.interruptible&&this.requests.length>=this.maxConcurrent}},nF=class{constructor(){this.syncRequestStream=new Ab({maxConcurrent:1,interruptible:!0}),this.asyncRequestStream=new Ab({maxConcurrent:1/0,interruptible:!1})}init({initialPage:t,resolveComponent:e,swapComponent:r}){oe.init({initialPage:t,resolveComponent:e,swapComponent:r}),KL.handle(),xs.init(),xs.on("missingHistoryItem",()=>{typeof window<"u"&&this.visit(window.location.href,{preserveState:!0,preserveScroll:!0,replace:!0})}),xs.on("loadDeferredProps",n=>{this.loadDeferredProps(n)})}get(t,e={},r={}){return this.visit(t,{...r,method:"get",data:e})}post(t,e={},r={}){return this.visit(t,{preserveState:!0,...r,method:"post",data:e})}put(t,e={},r={}){return this.visit(t,{preserveState:!0,...r,method:"put",data:e})}patch(t,e={},r={}){return this.visit(t,{preserveState:!0,...r,method:"patch",data:e})}delete(t,e={}){return this.visit(t,{preserveState:!0,...e,method:"delete"})}reload(t={}){if(!(typeof window>"u"))return this.visit(window.location.href,{...t,preserveScroll:!0,preserveState:!0,async:!0,headers:{...t.headers||{},"Cache-Control":"no-cache"}})}remember(t,e="default"){Te.remember(t,e)}restore(t="default"){return Te.restore(t)}on(t,e){return typeof window>"u"?()=>{}:xs.onGlobalEvent(t,e)}cancel(){this.syncRequestStream.cancelInFlight()}cancelAll(){this.asyncRequestStream.cancelInFlight(),this.syncRequestStream.cancelInFlight()}poll(t,e={},r={}){return XL.add(t,()=>this.reload(e),{autoStart:r.autoStart??!0,keepAlive:r.keepAlive??!1})}visit(t,e={}){const r=this.getPendingVisit(t,{...e,showProgress:e.showProgress??!e.async}),n=this.getVisitEvents(e);if(n.onBefore(r)===!1||!vb(r))return;const s=r.async?this.asyncRequestStream:this.syncRequestStream;s.interruptInFlight(),!oe.isCleared()&&!r.preserveUrl&&Ir.save();const i={...r,...n},o=dn.get(i);o?(Zt.reveal(o.inFlight),dn.use(o,i)):(Zt.reveal(!0),s.send(Cb.create(i,oe.get())))}getCached(t,e={}){return dn.findCached(this.getPrefetchParams(t,e))}flush(t,e={}){dn.remove(this.getPrefetchParams(t,e))}flushAll(){dn.removeAll()}flushByCacheTags(t){dn.removeByTags(Array.isArray(t)?t:[t])}getPrefetching(t,e={}){return dn.findInFlight(this.getPrefetchParams(t,e))}prefetch(t,e={},r={}){if((e.method??(_b(t)?t.method:"get"))!=="get")throw new Error("Prefetch requests must use the GET method");const s=this.getPendingVisit(t,{...e,async:!0,showProgress:!1,prefetch:!0}),i=s.url.origin+s.url.pathname+s.url.search,o=window.location.origin+window.location.pathname+window.location.search;if(i===o)return;const a=this.getVisitEvents(e);if(a.onBefore(s)===!1||!vb(s))return;Zt.hide(),this.asyncRequestStream.interruptInFlight();const l={...s,...a};new Promise(c=>{const d=()=>{oe.get()?c():setTimeout(d,50)};d()}).then(()=>{dn.add(l,c=>{this.asyncRequestStream.send(Cb.create(c,oe.get()))},{cacheFor:3e4,cacheTags:[],...r})})}clearHistory(){Te.clear()}decryptHistory(){return Te.decrypt()}resolveComponent(t){return oe.resolve(t)}replace(t){this.clientVisit(t,{replace:!0})}replaceProp(t,e,r){this.replace({preserveScroll:!0,preserveState:!0,props(n){const s=typeof e=="function"?e(Si(n,t),n):e;return Es(Gt(n),t,s)},...r||{}})}appendToProp(t,e,r){this.replaceProp(t,(n,s)=>{const i=typeof e=="function"?e(n,s):e;return Array.isArray(n)||(n=n!==void 0?[n]:[]),[...n,i]},r)}prependToProp(t,e,r){this.replaceProp(t,(n,s)=>{const i=typeof e=="function"?e(n,s):e;return Array.isArray(n)||(n=n!==void 0?[n]:[]),[i,...n]},r)}push(t){this.clientVisit(t)}clientVisit(t,{replace:e=!1}={}){const r=oe.get(),n=typeof t.props=="function"?t.props(r.props):t.props??r.props,{onError:s,onFinish:i,onSuccess:o,...a}=t;oe.set({...r,...a,props:n},{replace:e,preserveScroll:t.preserveScroll,preserveState:t.preserveState}).then(()=>{const l=oe.get().props.errors||{};if(Object.keys(l).length===0)return o?.(oe.get());const u=t.errorBag?l[t.errorBag||""]||{}:l;return s?.(u)}).finally(()=>i?.(t))}getPrefetchParams(t,e){return{...this.getPendingVisit(t,{...e,async:!0,showProgress:!1,prefetch:!0}),...this.getVisitEvents(e)}}getPendingVisit(t,e,r={}){if(_b(t)){const a=t;t=a.url,e.method=e.method??a.method}const n={method:"get",data:{},replace:!1,preserveScroll:!1,preserveState:!1,only:[],except:[],headers:{},errorBag:"",forceFormData:!1,queryStringArrayFormat:"brackets",async:!1,showProgress:!0,fresh:!1,reset:[],preserveUrl:!1,prefetch:!1,invalidateCacheTags:[],...e},[s,i]=BL(t,n.data,n.method,n.forceFormData,n.queryStringArrayFormat),o={cancelled:!1,completed:!1,interrupted:!1,...n,...r,url:s,data:i};return o.prefetch&&(o.headers.Purpose="prefetch"),o}getVisitEvents(t){return{onCancelToken:t.onCancelToken||(()=>{}),onBefore:t.onBefore||(()=>{}),onBeforeUpdate:t.onBeforeUpdate||(()=>{}),onStart:t.onStart||(()=>{}),onProgress:t.onProgress||(()=>{}),onFinish:t.onFinish||(()=>{}),onCancel:t.onCancel||(()=>{}),onSuccess:t.onSuccess||(()=>{}),onError:t.onError||(()=>{}),onPrefetched:t.onPrefetched||(()=>{}),onPrefetching:t.onPrefetching||(()=>{})}}loadDeferredProps(t){t&&Object.entries(t).forEach(([e,r])=>{this.reload({only:r})})}},sF={buildDOMElement(t){const e=document.createElement("template");e.innerHTML=t;const r=e.content.firstChild;if(!t.startsWith("<script "))return r;const n=document.createElement("script");return n.innerHTML=r.innerHTML,r.getAttributeNames().forEach(s=>{n.setAttribute(s,r.getAttribute(s)||"")}),n},isInertiaManagedElement(t){return t.nodeType===Node.ELEMENT_NODE&&t.getAttribute("inertia")!==null},findMatchingElementIndex(t,e){const r=t.getAttribute("inertia");return r!==null?e.findIndex(n=>n.getAttribute("inertia")===r):-1},update:Qh(function(t){const e=t.map(n=>this.buildDOMElement(n));Array.from(document.head.childNodes).filter(n=>this.isInertiaManagedElement(n)).forEach(n=>{const s=this.findMatchingElementIndex(n,e);if(s===-1){n?.parentNode?.removeChild(n);return}const i=e.splice(s,1)[0];i&&!n.isEqualNode(i)&&n?.parentNode?.replaceChild(i,n)}),e.forEach(n=>document.head.appendChild(n))},1)};function iF(t,e,r){const n={};let s=0;function i(){const d=s+=1;return n[d]=[],d.toString()}function o(d){d===null||Object.keys(n).indexOf(d)===-1||(delete n[d],c())}function a(d){Object.keys(n).indexOf(d)===-1&&(n[d]=[])}function l(d,p=[]){d!==null&&Object.keys(n).indexOf(d)>-1&&(n[d]=p),c()}function u(){const d=e(""),p={...d?{title:`<title inertia="">${d}</title>`}:{}},f=Object.values(n).reduce((h,m)=>h.concat(m),[]).reduce((h,m)=>{if(m.indexOf("<")===-1)return h;if(m.indexOf("<title ")===0){const v=m.match(/(<title [^>]+>)(.*?)(<\/title>)/);return h.title=v?`${v[1]}${e(v[2])}${v[3]}`:m,h}const g=m.match(/ inertia="[^"]+"/);return g?h[g[0]]=m:h[Object.keys(h).length]=m,h},p);return Object.values(f)}function c(){t?r(u()):sF.update(u())}return c(),{forceUpdate:c,createProvider:function(){const d=i();return{reconnect:()=>a(d),update:p=>l(d,p),disconnect:()=>o(d)}}}}var ut="nprogress",Cr,vt={minimum:.08,easing:"linear",positionUsing:"translate3d",speed:200,trickle:!0,trickleSpeed:200,showSpinner:!0,barSelector:'[role="bar"]',spinnerSelector:'[role="spinner"]',parent:"body",color:"#29d",includeCSS:!0,template:['<div class="bar" role="bar">','<div class="peg"></div>',"</div>",'<div class="spinner" role="spinner">','<div class="spinner-icon"></div>',"</div>"].join("")},is=null,oF=t=>{Object.assign(vt,t),vt.includeCSS&&hF(vt.color),Cr=document.createElement("div"),Cr.id=ut,Cr.innerHTML=vt.template},ou=t=>{const e=L1();t=B1(t,vt.minimum,1),is=t===1?null:t;const r=lF(!e),n=r.querySelector(vt.barSelector),s=vt.speed,i=vt.easing;r.offsetWidth,dF(o=>{const a=vt.positionUsing==="translate3d"?{transition:`all ${s}ms ${i}`,transform:`translate3d(${Sl(t)}%,0,0)`}:vt.positionUsing==="translate"?{transition:`all ${s}ms ${i}`,transform:`translate(${Sl(t)}%,0)`}:{marginLeft:`${Sl(t)}%`};for(const l in a)n.style[l]=a[l];if(t!==1)return setTimeout(o,s);r.style.transition="none",r.style.opacity="1",r.offsetWidth,setTimeout(()=>{r.style.transition=`all ${s}ms linear`,r.style.opacity="0",setTimeout(()=>{V1(),r.style.transition="",r.style.opacity="",o()},s)},s)})},L1=()=>typeof is=="number",F1=()=>{is||ou(0);const t=function(){setTimeout(function(){is&&(M1(),t())},vt.trickleSpeed)};vt.trickle&&t()},aF=t=>{!t&&!is||(M1(.3+.5*Math.random()),ou(1))},M1=t=>{const e=is;if(e===null)return F1();if(!(e>1))return t=typeof t=="number"?t:(()=>{const r={.1:[0,.2],.04:[.2,.5],.02:[.5,.8],.005:[.8,.99]};for(const n in r)if(e>=r[n][0]&&e<r[n][1])return parseFloat(n);return 0})(),ou(B1(e+t,0,.994))},lF=t=>{if(cF())return document.getElementById(ut);document.documentElement.classList.add(`${ut}-busy`);const e=Cr.querySelector(vt.barSelector),r=t?"-100":Sl(is||0),n=D1();return e.style.transition="all 0 linear",e.style.transform=`translate3d(${r}%,0,0)`,vt.showSpinner||Cr.querySelector(vt.spinnerSelector)?.remove(),n!==document.body&&n.classList.add(`${ut}-custom-parent`),n.appendChild(Cr),Cr},D1=()=>uF(vt.parent)?vt.parent:document.querySelector(vt.parent),V1=()=>{document.documentElement.classList.remove(`${ut}-busy`),D1().classList.remove(`${ut}-custom-parent`),Cr?.remove()},cF=()=>document.getElementById(ut)!==null,uF=t=>typeof HTMLElement=="object"?t instanceof HTMLElement:t&&typeof t=="object"&&t.nodeType===1&&typeof t.nodeName=="string";function B1(t,e,r){return t<e?e:t>r?r:t}var Sl=t=>(-1+t)*100,dF=(()=>{const t=[],e=()=>{const r=t.shift();r&&r(e)};return r=>{t.push(r),t.length===1&&e()}})(),hF=t=>{const e=document.createElement("style");e.textContent=`
    #${ut} {
      pointer-events: none;
    }

    #${ut} .bar {
      background: ${t};

      position: fixed;
      z-index: 1031;
      top: 0;
      left: 0;

      width: 100%;
      height: 2px;
    }

    #${ut} .peg {
      display: block;
      position: absolute;
      right: 0px;
      width: 100px;
      height: 100%;
      box-shadow: 0 0 10px ${t}, 0 0 5px ${t};
      opacity: 1.0;

      transform: rotate(3deg) translate(0px, -4px);
    }

    #${ut} .spinner {
      display: block;
      position: fixed;
      z-index: 1031;
      top: 15px;
      right: 15px;
    }

    #${ut} .spinner-icon {
      width: 18px;
      height: 18px;
      box-sizing: border-box;

      border: solid 2px transparent;
      border-top-color: ${t};
      border-left-color: ${t};
      border-radius: 50%;

      animation: ${ut}-spinner 400ms linear infinite;
    }

    .${ut}-custom-parent {
      overflow: hidden;
      position: relative;
    }

    .${ut}-custom-parent #${ut} .spinner,
    .${ut}-custom-parent #${ut} .bar {
      position: absolute;
    }

    @keyframes ${ut}-spinner {
      0%   { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  `,document.head.appendChild(e)},fF=()=>{Cr&&(Cr.style.display="")},pF=()=>{Cr&&(Cr.style.display="none")},Or={configure:oF,isStarted:L1,done:aF,set:ou,remove:V1,start:F1,status:is,show:fF,hide:pF},mF=class{constructor(){this.hideCount=0}start(){Or.start()}reveal(t=!1){this.hideCount=Math.max(0,this.hideCount-1),(t||this.hideCount===0)&&Or.show()}hide(){this.hideCount++,Or.hide()}set(t){Or.set(Math.max(0,Math.min(1,t)))}finish(){Or.done()}reset(){Or.set(0)}remove(){Or.done(),Or.remove()}isStarted(){return Or.isStarted()}getStatus(){return Or.status}},Zt=new mF;Zt.reveal;Zt.hide;function gF(t){document.addEventListener("inertia:start",e=>yF(e,t)),document.addEventListener("inertia:progress",bF)}function yF(t,e){t.detail.visit.showProgress||Zt.hide();const r=setTimeout(()=>Zt.start(),e);document.addEventListener("inertia:finish",n=>vF(n,r),{once:!0})}function bF(t){Zt.isStarted()&&t.detail.progress?.percentage&&Zt.set(Math.max(Zt.getStatus(),t.detail.progress.percentage/100*.9))}function vF(t,e){clearTimeout(e),Zt.isStarted()&&(t.detail.visit.completed?Zt.finish():t.detail.visit.interrupted?Zt.reset():t.detail.visit.cancelled&&Zt.remove())}function wF({delay:t=250,color:e="#29d",includeCSS:r=!0,showSpinner:n=!1}={}){gF(t),Or.configure({showSpinner:n,includeCSS:r,color:e})}var er=new nF;var _F={created(){if(!this.$options.remember)return;Array.isArray(this.$options.remember)&&(this.$options.remember={data:this.$options.remember}),typeof this.$options.remember=="string"&&(this.$options.remember={data:[this.$options.remember]}),typeof this.$options.remember.data=="string"&&(this.$options.remember={data:[this.$options.remember.data]});const t=this.$options.remember.key instanceof Function?this.$options.remember.key.call(this):this.$options.remember.key,e=er.restore(t),r=this.$options.remember.data.filter(s=>!(this[s]!==null&&typeof this[s]=="object"&&this[s].__rememberable===!1)),n=s=>this[s]!==null&&typeof this[s]=="object"&&typeof this[s].__remember=="function"&&typeof this[s].__restore=="function";r.forEach(s=>{this[s]!==void 0&&e!==void 0&&e[s]!==void 0&&(n(s)?this[s].__restore(e[s]):this[s]=e[s]),this.$watch(s,()=>{er.remember(r.reduce((i,o)=>({...i,[o]:Gt(n(o)?this[o].__remember():this[o])}),{}),t)},{immediate:!0,deep:!0})})}},EF=_F;function SF(t,e){const r=typeof t=="string"?t:null,n=(typeof t=="string"?e:t)??{},s=r?er.restore(r):null;let i=Gt(typeof n=="function"?n():n),o=null,a=null,l=d=>d,u=!1;const c=Wi({...s?s.data:Gt(i),isDirty:!1,errors:s?s.errors:{},hasErrors:!1,processing:!1,progress:null,wasSuccessful:!1,recentlySuccessful:!1,data(){return Object.keys(i).reduce((d,p)=>Es(d,p,Si(this,p)),{})},transform(d){return l=d,this},defaults(d,p){if(typeof n=="function")throw new Error("You cannot call `defaults()` when using a function to define your form data.");return u=!0,typeof d>"u"?(i=Gt(this.data()),this.isDirty=!1):i=typeof d=="string"?Es(Gt(i),d,p):Object.assign({},Gt(i),d),this},reset(...d){const p=Gt(typeof n=="function"?n():i),f=Gt(p);return d.length===0?(i=f,Object.assign(this,p)):d.filter(h=>hN(f,h)).forEach(h=>{Es(i,h,Si(f,h)),Es(this,h,Si(p,h))}),this},setError(d,p){return Object.assign(this.errors,typeof d=="string"?{[d]:p}:d),this.hasErrors=Object.keys(this.errors).length>0,this},clearErrors(...d){return this.errors=Object.keys(this.errors).reduce((p,f)=>({...p,...d.length>0&&!d.includes(f)?{[f]:this.errors[f]}:{}}),{}),this.hasErrors=Object.keys(this.errors).length>0,this},resetAndClearErrors(...d){return this.reset(...d),this.clearErrors(...d),this},submit(...d){const p=d[0]!==null&&typeof d[0]=="object",f=p?d[0].method:d[0],h=p?d[0].url:d[1],m=(p?d[1]:d[2])??{};u=!1;const g=l(this.data()),v={...m,onCancelToken:b=>{if(o=b,m.onCancelToken)return m.onCancelToken(b)},onBefore:b=>{if(this.wasSuccessful=!1,this.recentlySuccessful=!1,clearTimeout(a),m.onBefore)return m.onBefore(b)},onStart:b=>{if(this.processing=!0,m.onStart)return m.onStart(b)},onProgress:b=>{if(this.progress=b,m.onProgress)return m.onProgress(b)},onSuccess:async b=>{this.processing=!1,this.progress=null,this.clearErrors(),this.wasSuccessful=!0,this.recentlySuccessful=!0,a=setTimeout(()=>this.recentlySuccessful=!1,2e3);const w=m.onSuccess?await m.onSuccess(b):null;return u||(i=Gt(this.data()),this.isDirty=!1),w},onError:b=>{if(this.processing=!1,this.progress=null,this.clearErrors().setError(b),m.onError)return m.onError(b)},onCancel:()=>{if(this.processing=!1,this.progress=null,m.onCancel)return m.onCancel()},onFinish:b=>{if(this.processing=!1,this.progress=null,o=null,m.onFinish)return m.onFinish(b)}};f==="delete"?er.delete(h,{...v,data:g}):er[f](h,g,v)},get(d,p){this.submit("get",d,p)},post(d,p){this.submit("post",d,p)},put(d,p){this.submit("put",d,p)},patch(d,p){this.submit("patch",d,p)},delete(d,p){this.submit("delete",d,p)},cancel(){o&&o.cancel()},__rememberable:r===null,__remember(){return{data:this.data(),errors:this.errors}},__restore(d){Object.assign(this,d.data),this.setError(d.errors)}});return Os(c,d=>{c.isDirty=!fN(c.data(),i),r&&er.remember(Gt(d.__remember()),r)},{immediate:!0,deep:!0}),c}var hr=Qn(null),St=Qn(null),Wd=If(null),sl=Qn(null),rf=null,xF=Gi({name:"Inertia",props:{initialPage:{type:Object,required:!0},initialComponent:{type:Object,required:!1},resolveComponent:{type:Function,required:!1},titleCallback:{type:Function,required:!1,default:t=>t},onHeadUpdate:{type:Function,required:!1,default:()=>()=>{}}},setup({initialPage:t,initialComponent:e,resolveComponent:r,titleCallback:n,onHeadUpdate:s}){hr.value=e?Fl(e):null,St.value=t,sl.value=null;const i=typeof window>"u";return rf=iF(i,n,s),i||(er.init({initialPage:t,resolveComponent:r,swapComponent:async o=>{hr.value=Fl(o.component),St.value=o.page,sl.value=o.preserveState?sl.value:Date.now()}}),er.on("navigate",()=>rf.forceUpdate())),()=>{if(hr.value){hr.value.inheritAttrs=!!hr.value.inheritAttrs;const o=Ns(hr.value,{...St.value.props,key:sl.value});return Wd.value&&(hr.value.layout=Wd.value,Wd.value=null),hr.value.layout?typeof hr.value.layout=="function"?hr.value.layout(Ns,o):(Array.isArray(hr.value.layout)?hr.value.layout:[hr.value.layout]).concat(o).reverse().reduce((a,l)=>(l.inheritAttrs=!!l.inheritAttrs,Ns(l,{...St.value.props},()=>a))):o}}}}),CF=xF,AF={install(t){er.form=SF,Object.defineProperty(t.config.globalProperties,"$inertia",{get:()=>er}),Object.defineProperty(t.config.globalProperties,"$page",{get:()=>St.value}),Object.defineProperty(t.config.globalProperties,"$headManager",{get:()=>rf}),t.mixin(EF)}};function QB(){return Wi({props:Kt(()=>St.value?.props),url:Kt(()=>St.value?.url),component:Kt(()=>St.value?.component),version:Kt(()=>St.value?.version),clearHistory:Kt(()=>St.value?.clearHistory),deferredProps:Kt(()=>St.value?.deferredProps),mergeProps:Kt(()=>St.value?.mergeProps),prependProps:Kt(()=>St.value?.prependProps),deepMergeProps:Kt(()=>St.value?.deepMergeProps),matchPropsOn:Kt(()=>St.value?.matchPropsOn),rememberedState:Kt(()=>St.value?.rememberedState),encryptHistory:Kt(()=>St.value?.encryptHistory)})}async function $F({id:t="app",resolve:e,setup:r,title:n,progress:s={},page:i,render:o}){const a=typeof window>"u",l=a?null:document.getElementById(t),u=i||JSON.parse(l.dataset.page),c=f=>Promise.resolve(e(f)).then(h=>h.default||h);let d=[];const p=await Promise.all([c(u.component),er.decryptHistory().catch(()=>{})]).then(([f])=>r({el:l,App:CF,props:{initialPage:u,initialComponent:f,resolveComponent:c,titleCallback:n,onHeadUpdate:a?h=>d=h:null},plugin:AF}));if(!a&&s&&wF(s),a){const f=await o(ep({render:()=>Ns("div",{id:t,"data-page":JSON.stringify(u),innerHTML:p?o(p):""})}));return{head:d,body:f}}}var ZB=Gi({name:"Deferred",props:{data:{type:[String,Array],required:!0}},render(){const t=Array.isArray(this.$props.data)?this.$props.data:[this.$props.data];if(!this.$slots.fallback)throw new Error("`<Deferred>` requires a `<template #fallback>` slot");return t.every(e=>this.$page.props[e]!==void 0)?this.$slots.default():this.$slots.fallback()}}),TF=Gi({props:{title:{type:String,required:!1}},data(){return{provider:this.$headManager.createProvider()}},beforeUnmount(){this.provider.disconnect()},methods:{isUnaryTag(t){return["area","base","br","col","embed","hr","img","input","keygen","link","meta","param","source","track","wbr"].indexOf(t.type)>-1},renderTagStart(t){t.props=t.props||{},t.props.inertia=t.props["head-key"]!==void 0?t.props["head-key"]:"";const e=Object.keys(t.props).reduce((r,n)=>{const s=String(t.props[n]);return["key","head-key"].includes(n)?r:s===""?r+` ${n}`:r+` ${n}="${lN(s)}"`},"");return`<${t.type}${e}>`},renderTagChildren(t){return typeof t.children=="string"?t.children:t.children.reduce((e,r)=>e+this.renderTag(r),"")},isFunctionNode(t){return typeof t.type=="function"},isComponentNode(t){return typeof t.type=="object"},isCommentNode(t){return/(comment|cmt)/i.test(t.type.toString())},isFragmentNode(t){return/(fragment|fgt|symbol\(\))/i.test(t.type.toString())},isTextNode(t){return/(text|txt)/i.test(t.type.toString())},renderTag(t){if(this.isTextNode(t))return t.children;if(this.isFragmentNode(t))return"";if(this.isCommentNode(t))return"";let e=this.renderTagStart(t);return t.children&&(e+=this.renderTagChildren(t)),this.isUnaryTag(t)||(e+=`</${t.type}>`),e},addTitleElement(t){return this.title&&!t.find(e=>e.startsWith("<title"))&&t.push(`<title inertia>${this.title}</title>`),t},renderNodes(t){return this.addTitleElement(t.flatMap(e=>this.resolveNode(e)).map(e=>this.renderTag(e)).filter(e=>e))},resolveNode(t){return this.isFunctionNode(t)?this.resolveNode(t.type()):this.isComponentNode(t)?(console.warn("Using components in the <Head> component is not supported."),[]):this.isTextNode(t)&&t.children?t:this.isFragmentNode(t)&&t.children?t.children.flatMap(e=>this.resolveNode(e)):this.isCommentNode(t)?[]:t}},render(){this.provider.update(this.renderNodes(this.$slots.default?this.$slots.default():[]))}}),eU=TF;const U1=class extends HTMLElement{constructor(){super(...arguments),this.cookieName=null,this.state="collapsed",this.expanded=!1,this.handleOpen=()=>{this.trigger?.setAttribute("aria-expanded","true"),this.expanded=!0,this.dispatchEvent(new CustomEvent("open")),this.target&&(this.target.dataset.state="expanded"),this.cookieName&&window.Craft?.setCookie(this.cookieName,"expanded")},this.handleClose=()=>{this.trigger?.setAttribute("aria-expanded","false"),this.expanded=!1,this.dispatchEvent(new CustomEvent("close")),this.target&&(this.target.dataset.state="collapsed"),this.cookieName&&window.Craft?.setCookie(this.cookieName,"collapsed")}}get trigger(){return this.querySelector('button[type="button"]')}get target(){if(!this.trigger)return console.warn("No trigger found for disclosure."),null;const e=this.trigger.getAttribute("aria-controls");return e?document.getElementById(e):(console.warn("No target selector found for disclosure."),null)}connectedCallback(){if(!this.trigger){console.error("craft-disclosure elements must include a button",this);return}if(!this.target){console.error(`No target with id ${this.trigger.getAttribute("aria-controls")} found for disclosure. `,this.trigger);return}this.cookieName=this.getAttribute("cookie-name"),this.state=this.getAttribute("state")??"expanded",this.trigger.setAttribute("aria-expanded",this.state==="expanded"?"true":"false"),this.trigger.addEventListener("click",this.toggle.bind(this)),this.state==="expanded"?this.open():this.close()}disconnectedCallback(){this.open(),this.trigger?.removeEventListener("click",this.toggle.bind(this))}attributeChangedCallback(e,r,n){e==="state"&&(n==="expanded"?this.handleOpen():this.handleClose())}toggle(){this.expanded?this.close():this.open()}open(){this.setAttribute("state","expanded")}close(){this.setAttribute("state","collapsed")}};U1.observedAttributes=["state"];let kF=U1;customElements.get("craft-disclosure")||customElements.define("craft-disclosure",kF);const xl=globalThis,Fp=xl.ShadowRoot&&(xl.ShadyCSS===void 0||xl.ShadyCSS.nativeShadow)&&"adoptedStyleSheets"in Document.prototype&&"replace"in CSSStyleSheet.prototype,Mp=Symbol(),$b=new WeakMap;let j1=class{constructor(e,r,n){if(this._$cssResult$=!0,n!==Mp)throw Error("CSSResult is not constructable. Use `unsafeCSS` or `css` instead.");this.cssText=e,this.t=r}get styleSheet(){let e=this.o;const r=this.t;if(Fp&&e===void 0){const n=r!==void 0&&r.length===1;n&&(e=$b.get(r)),e===void 0&&((this.o=e=new CSSStyleSheet).replaceSync(this.cssText),n&&$b.set(r,e))}return e}toString(){return this.cssText}};const q1=t=>new j1(typeof t=="string"?t:t+"",void 0,Mp),ue=(t,...e)=>{const r=t.length===1?t[0]:e.reduce(((n,s,i)=>n+(o=>{if(o._$cssResult$===!0)return o.cssText;if(typeof o=="number")return o;throw Error("Value passed to 'css' function must be a 'css' function result: "+o+". Use 'unsafeCSS' to pass non-literal values, but take care to ensure page security.")})(s)+t[i+1]),t[0]);return new j1(r,t,Mp)},Dp=(t,e)=>{if(Fp)t.adoptedStyleSheets=e.map((r=>r instanceof CSSStyleSheet?r:r.styleSheet));else for(const r of e){const n=document.createElement("style"),s=xl.litNonce;s!==void 0&&n.setAttribute("nonce",s),n.textContent=r.cssText,t.appendChild(n)}},Tb=Fp?t=>t:t=>t instanceof CSSStyleSheet?(e=>{let r="";for(const n of e.cssRules)r+=n.cssText;return q1(r)})(t):t,{is:PF,defineProperty:OF,getOwnPropertyDescriptor:RF,getOwnPropertyNames:NF,getOwnPropertySymbols:IF,getPrototypeOf:LF}=Object,au=globalThis,kb=au.trustedTypes,FF=kb?kb.emptyScript:"",MF=au.reactiveElementPolyfillSupport,Fo=(t,e)=>t,hc={toAttribute(t,e){switch(e){case Boolean:t=t?FF:null;break;case Object:case Array:t=t==null?t:JSON.stringify(t)}return t},fromAttribute(t,e){let r=t;switch(e){case Boolean:r=t!==null;break;case Number:r=t===null?null:Number(t);break;case Object:case Array:try{r=JSON.parse(t)}catch{r=null}}return r}},Vp=(t,e)=>!PF(t,e),Pb={attribute:!0,type:String,converter:hc,reflect:!1,useDefault:!1,hasChanged:Vp};Symbol.metadata??=Symbol("metadata"),au.litPropertyMetadata??=new WeakMap;let ui=class extends HTMLElement{static addInitializer(e){this._$Ei(),(this.l??=[]).push(e)}static get observedAttributes(){return this.finalize(),this._$Eh&&[...this._$Eh.keys()]}static createProperty(e,r=Pb){if(r.state&&(r.attribute=!1),this._$Ei(),this.prototype.hasOwnProperty(e)&&((r=Object.create(r)).wrapped=!0),this.elementProperties.set(e,r),!r.noAccessor){const n=Symbol(),s=this.getPropertyDescriptor(e,n,r);s!==void 0&&OF(this.prototype,e,s)}}static getPropertyDescriptor(e,r,n){const{get:s,set:i}=RF(this.prototype,e)??{get(){return this[r]},set(o){this[r]=o}};return{get:s,set(o){const a=s?.call(this);i?.call(this,o),this.requestUpdate(e,a,n)},configurable:!0,enumerable:!0}}static getPropertyOptions(e){return this.elementProperties.get(e)??Pb}static _$Ei(){if(this.hasOwnProperty(Fo("elementProperties")))return;const e=LF(this);e.finalize(),e.l!==void 0&&(this.l=[...e.l]),this.elementProperties=new Map(e.elementProperties)}static finalize(){if(this.hasOwnProperty(Fo("finalized")))return;if(this.finalized=!0,this._$Ei(),this.hasOwnProperty(Fo("properties"))){const r=this.properties,n=[...NF(r),...IF(r)];for(const s of n)this.createProperty(s,r[s])}const e=this[Symbol.metadata];if(e!==null){const r=litPropertyMetadata.get(e);if(r!==void 0)for(const[n,s]of r)this.elementProperties.set(n,s)}this._$Eh=new Map;for(const[r,n]of this.elementProperties){const s=this._$Eu(r,n);s!==void 0&&this._$Eh.set(s,r)}this.elementStyles=this.finalizeStyles(this.styles)}static finalizeStyles(e){const r=[];if(Array.isArray(e)){const n=new Set(e.flat(1/0).reverse());for(const s of n)r.unshift(Tb(s))}else e!==void 0&&r.push(Tb(e));return r}static _$Eu(e,r){const n=r.attribute;return n===!1?void 0:typeof n=="string"?n:typeof e=="string"?e.toLowerCase():void 0}constructor(){super(),this._$Ep=void 0,this.isUpdatePending=!1,this.hasUpdated=!1,this._$Em=null,this._$Ev()}_$Ev(){this._$ES=new Promise((e=>this.enableUpdating=e)),this._$AL=new Map,this._$E_(),this.requestUpdate(),this.constructor.l?.forEach((e=>e(this)))}addController(e){(this._$EO??=new Set).add(e),this.renderRoot!==void 0&&this.isConnected&&e.hostConnected?.()}removeController(e){this._$EO?.delete(e)}_$E_(){const e=new Map,r=this.constructor.elementProperties;for(const n of r.keys())this.hasOwnProperty(n)&&(e.set(n,this[n]),delete this[n]);e.size>0&&(this._$Ep=e)}createRenderRoot(){const e=this.shadowRoot??this.attachShadow(this.constructor.shadowRootOptions);return Dp(e,this.constructor.elementStyles),e}connectedCallback(){this.renderRoot??=this.createRenderRoot(),this.enableUpdating(!0),this._$EO?.forEach((e=>e.hostConnected?.()))}enableUpdating(e){}disconnectedCallback(){this._$EO?.forEach((e=>e.hostDisconnected?.()))}attributeChangedCallback(e,r,n){this._$AK(e,n)}_$ET(e,r){const n=this.constructor.elementProperties.get(e),s=this.constructor._$Eu(e,n);if(s!==void 0&&n.reflect===!0){const i=(n.converter?.toAttribute!==void 0?n.converter:hc).toAttribute(r,n.type);this._$Em=e,i==null?this.removeAttribute(s):this.setAttribute(s,i),this._$Em=null}}_$AK(e,r){const n=this.constructor,s=n._$Eh.get(e);if(s!==void 0&&this._$Em!==s){const i=n.getPropertyOptions(s),o=typeof i.converter=="function"?{fromAttribute:i.converter}:i.converter?.fromAttribute!==void 0?i.converter:hc;this._$Em=s;const a=o.fromAttribute(r,i.type);this[s]=a??this._$Ej?.get(s)??a,this._$Em=null}}requestUpdate(e,r,n){if(e!==void 0){const s=this.constructor,i=this[e];if(n??=s.getPropertyOptions(e),!((n.hasChanged??Vp)(i,r)||n.useDefault&&n.reflect&&i===this._$Ej?.get(e)&&!this.hasAttribute(s._$Eu(e,n))))return;this.C(e,r,n)}this.isUpdatePending===!1&&(this._$ES=this._$EP())}C(e,r,{useDefault:n,reflect:s,wrapped:i},o){n&&!(this._$Ej??=new Map).has(e)&&(this._$Ej.set(e,o??r??this[e]),i!==!0||o!==void 0)||(this._$AL.has(e)||(this.hasUpdated||n||(r=void 0),this._$AL.set(e,r)),s===!0&&this._$Em!==e&&(this._$Eq??=new Set).add(e))}async _$EP(){this.isUpdatePending=!0;try{await this._$ES}catch(r){Promise.reject(r)}const e=this.scheduleUpdate();return e!=null&&await e,!this.isUpdatePending}scheduleUpdate(){return this.performUpdate()}performUpdate(){if(!this.isUpdatePending)return;if(!this.hasUpdated){if(this.renderRoot??=this.createRenderRoot(),this._$Ep){for(const[s,i]of this._$Ep)this[s]=i;this._$Ep=void 0}const n=this.constructor.elementProperties;if(n.size>0)for(const[s,i]of n){const{wrapped:o}=i,a=this[s];o!==!0||this._$AL.has(s)||a===void 0||this.C(s,void 0,i,a)}}let e=!1;const r=this._$AL;try{e=this.shouldUpdate(r),e?(this.willUpdate(r),this._$EO?.forEach((n=>n.hostUpdate?.())),this.update(r)):this._$EM()}catch(n){throw e=!1,this._$EM(),n}e&&this._$AE(r)}willUpdate(e){}_$AE(e){this._$EO?.forEach((r=>r.hostUpdated?.())),this.hasUpdated||(this.hasUpdated=!0,this.firstUpdated(e)),this.updated(e)}_$EM(){this._$AL=new Map,this.isUpdatePending=!1}get updateComplete(){return this.getUpdateComplete()}getUpdateComplete(){return this._$ES}shouldUpdate(e){return!0}update(e){this._$Eq&&=this._$Eq.forEach((r=>this._$ET(r,this[r]))),this._$EM()}updated(e){}firstUpdated(e){}};ui.elementStyles=[],ui.shadowRootOptions={mode:"open"},ui[Fo("elementProperties")]=new Map,ui[Fo("finalized")]=new Map,MF?.({ReactiveElement:ui}),(au.reactiveElementVersions??=[]).push("2.1.1");const Bp=globalThis,fc=Bp.trustedTypes,Ob=fc?fc.createPolicy("lit-html",{createHTML:t=>t}):void 0,H1="$lit$",Kn=`lit$${Math.random().toFixed(9).slice(2)}$`,z1="?"+Kn,DF=`<${z1}>`,Hs=document,oa=()=>Hs.createComment(""),aa=t=>t===null||typeof t!="object"&&typeof t!="function",Up=Array.isArray,VF=t=>Up(t)||typeof t?.[Symbol.iterator]=="function",Kd=`[ 	
\f\r]`,mo=/<(?:(!--|\/[^a-zA-Z])|(\/?[a-zA-Z][^>\s]*)|(\/?$))/g,Rb=/-->/g,Nb=/>/g,ps=RegExp(`>|${Kd}(?:([^\\s"'>=/]+)(${Kd}*=${Kd}*(?:[^ 	
\f\r"'\`<>=]|("|')|))|$)`,"g"),Ib=/'/g,Lb=/"/g,W1=/^(?:script|style|textarea|title)$/i,BF=t=>(e,...r)=>({_$litType$:t,strings:e,values:r}),Y=BF(1),Zr=Symbol.for("lit-noChange"),Ce=Symbol.for("lit-nothing"),Fb=new WeakMap,Cs=Hs.createTreeWalker(Hs,129);function K1(t,e){if(!Up(t)||!t.hasOwnProperty("raw"))throw Error("invalid template strings array");return Ob!==void 0?Ob.createHTML(e):e}const UF=(t,e)=>{const r=t.length-1,n=[];let s,i=e===2?"<svg>":e===3?"<math>":"",o=mo;for(let a=0;a<r;a++){const l=t[a];let u,c,d=-1,p=0;for(;p<l.length&&(o.lastIndex=p,c=o.exec(l),c!==null);)p=o.lastIndex,o===mo?c[1]==="!--"?o=Rb:c[1]!==void 0?o=Nb:c[2]!==void 0?(W1.test(c[2])&&(s=RegExp("</"+c[2],"g")),o=ps):c[3]!==void 0&&(o=ps):o===ps?c[0]===">"?(o=s??mo,d=-1):c[1]===void 0?d=-2:(d=o.lastIndex-c[2].length,u=c[1],o=c[3]===void 0?ps:c[3]==='"'?Lb:Ib):o===Lb||o===Ib?o=ps:o===Rb||o===Nb?o=mo:(o=ps,s=void 0);const f=o===ps&&t[a+1].startsWith("/>")?" ":"";i+=o===mo?l+DF:d>=0?(n.push(u),l.slice(0,d)+H1+l.slice(d)+Kn+f):l+Kn+(d===-2?a:f)}return[K1(t,i+(t[r]||"<?>")+(e===2?"</svg>":e===3?"</math>":"")),n]};let nf=class G1{constructor({strings:e,_$litType$:r},n){let s;this.parts=[];let i=0,o=0;const a=e.length-1,l=this.parts,[u,c]=UF(e,r);if(this.el=G1.createElement(u,n),Cs.currentNode=this.el.content,r===2||r===3){const d=this.el.content.firstChild;d.replaceWith(...d.childNodes)}for(;(s=Cs.nextNode())!==null&&l.length<a;){if(s.nodeType===1){if(s.hasAttributes())for(const d of s.getAttributeNames())if(d.endsWith(H1)){const p=c[o++],f=s.getAttribute(d).split(Kn),h=/([.?@])?(.*)/.exec(p);l.push({type:1,index:i,name:h[2],strings:f,ctor:h[1]==="."?qF:h[1]==="?"?HF:h[1]==="@"?zF:lu}),s.removeAttribute(d)}else d.startsWith(Kn)&&(l.push({type:6,index:i}),s.removeAttribute(d));if(W1.test(s.tagName)){const d=s.textContent.split(Kn),p=d.length-1;if(p>0){s.textContent=fc?fc.emptyScript:"";for(let f=0;f<p;f++)s.append(d[f],oa()),Cs.nextNode(),l.push({type:2,index:++i});s.append(d[p],oa())}}}else if(s.nodeType===8)if(s.data===z1)l.push({type:2,index:i});else{let d=-1;for(;(d=s.data.indexOf(Kn,d+1))!==-1;)l.push({type:7,index:i}),d+=Kn.length-1}i++}}static createElement(e,r){const n=Hs.createElement("template");return n.innerHTML=e,n}};function Mi(t,e,r=t,n){if(e===Zr)return e;let s=n!==void 0?r._$Co?.[n]:r._$Cl;const i=aa(e)?void 0:e._$litDirective$;return s?.constructor!==i&&(s?._$AO?.(!1),i===void 0?s=void 0:(s=new i(t),s._$AT(t,r,n)),n!==void 0?(r._$Co??=[])[n]=s:r._$Cl=s),s!==void 0&&(e=Mi(t,s._$AS(t,e.values),s,n)),e}let jF=class{constructor(e,r){this._$AV=[],this._$AN=void 0,this._$AD=e,this._$AM=r}get parentNode(){return this._$AM.parentNode}get _$AU(){return this._$AM._$AU}u(e){const{el:{content:r},parts:n}=this._$AD,s=(e?.creationScope??Hs).importNode(r,!0);Cs.currentNode=s;let i=Cs.nextNode(),o=0,a=0,l=n[0];for(;l!==void 0;){if(o===l.index){let u;l.type===2?u=new jp(i,i.nextSibling,this,e):l.type===1?u=new l.ctor(i,l.name,l.strings,this,e):l.type===6&&(u=new WF(i,this,e)),this._$AV.push(u),l=n[++a]}o!==l?.index&&(i=Cs.nextNode(),o++)}return Cs.currentNode=Hs,s}p(e){let r=0;for(const n of this._$AV)n!==void 0&&(n.strings!==void 0?(n._$AI(e,n,r),r+=n.strings.length-2):n._$AI(e[r])),r++}},jp=class J1{get _$AU(){return this._$AM?._$AU??this._$Cv}constructor(e,r,n,s){this.type=2,this._$AH=Ce,this._$AN=void 0,this._$AA=e,this._$AB=r,this._$AM=n,this.options=s,this._$Cv=s?.isConnected??!0}get parentNode(){let e=this._$AA.parentNode;const r=this._$AM;return r!==void 0&&e?.nodeType===11&&(e=r.parentNode),e}get startNode(){return this._$AA}get endNode(){return this._$AB}_$AI(e,r=this){e=Mi(this,e,r),aa(e)?e===Ce||e==null||e===""?(this._$AH!==Ce&&this._$AR(),this._$AH=Ce):e!==this._$AH&&e!==Zr&&this._(e):e._$litType$!==void 0?this.$(e):e.nodeType!==void 0?this.T(e):VF(e)?this.k(e):this._(e)}O(e){return this._$AA.parentNode.insertBefore(e,this._$AB)}T(e){this._$AH!==e&&(this._$AR(),this._$AH=this.O(e))}_(e){this._$AH!==Ce&&aa(this._$AH)?this._$AA.nextSibling.data=e:this.T(Hs.createTextNode(e)),this._$AH=e}$(e){const{values:r,_$litType$:n}=e,s=typeof n=="number"?this._$AC(e):(n.el===void 0&&(n.el=nf.createElement(K1(n.h,n.h[0]),this.options)),n);if(this._$AH?._$AD===s)this._$AH.p(r);else{const i=new jF(s,this),o=i.u(this.options);i.p(r),this.T(o),this._$AH=i}}_$AC(e){let r=Fb.get(e.strings);return r===void 0&&Fb.set(e.strings,r=new nf(e)),r}k(e){Up(this._$AH)||(this._$AH=[],this._$AR());const r=this._$AH;let n,s=0;for(const i of e)s===r.length?r.push(n=new J1(this.O(oa()),this.O(oa()),this,this.options)):n=r[s],n._$AI(i),s++;s<r.length&&(this._$AR(n&&n._$AB.nextSibling,s),r.length=s)}_$AR(e=this._$AA.nextSibling,r){for(this._$AP?.(!1,!0,r);e!==this._$AB;){const n=e.nextSibling;e.remove(),e=n}}setConnected(e){this._$AM===void 0&&(this._$Cv=e,this._$AP?.(e))}},lu=class{get tagName(){return this.element.tagName}get _$AU(){return this._$AM._$AU}constructor(e,r,n,s,i){this.type=1,this._$AH=Ce,this._$AN=void 0,this.element=e,this.name=r,this._$AM=s,this.options=i,n.length>2||n[0]!==""||n[1]!==""?(this._$AH=Array(n.length-1).fill(new String),this.strings=n):this._$AH=Ce}_$AI(e,r=this,n,s){const i=this.strings;let o=!1;if(i===void 0)e=Mi(this,e,r,0),o=!aa(e)||e!==this._$AH&&e!==Zr,o&&(this._$AH=e);else{const a=e;let l,u;for(e=i[0],l=0;l<i.length-1;l++)u=Mi(this,a[n+l],r,l),u===Zr&&(u=this._$AH[l]),o||=!aa(u)||u!==this._$AH[l],u===Ce?e=Ce:e!==Ce&&(e+=(u??"")+i[l+1]),this._$AH[l]=u}o&&!s&&this.j(e)}j(e){e===Ce?this.element.removeAttribute(this.name):this.element.setAttribute(this.name,e??"")}},qF=class extends lu{constructor(){super(...arguments),this.type=3}j(e){this.element[this.name]=e===Ce?void 0:e}},HF=class extends lu{constructor(){super(...arguments),this.type=4}j(e){this.element.toggleAttribute(this.name,!!e&&e!==Ce)}},zF=class extends lu{constructor(e,r,n,s,i){super(e,r,n,s,i),this.type=5}_$AI(e,r=this){if((e=Mi(this,e,r,0)??Ce)===Zr)return;const n=this._$AH,s=e===Ce&&n!==Ce||e.capture!==n.capture||e.once!==n.once||e.passive!==n.passive,i=e!==Ce&&(n===Ce||s);s&&this.element.removeEventListener(this.name,this,n),i&&this.element.addEventListener(this.name,this,e),this._$AH=e}handleEvent(e){typeof this._$AH=="function"?this._$AH.call(this.options?.host??this.element,e):this._$AH.handleEvent(e)}},WF=class{constructor(e,r,n){this.element=e,this.type=6,this._$AN=void 0,this._$AM=r,this.options=n}get _$AU(){return this._$AM._$AU}_$AI(e){Mi(this,e)}};const KF=Bp.litHtmlPolyfillSupport;KF?.(nf,jp),(Bp.litHtmlVersions??=[]).push("3.3.1");const sf=(t,e,r)=>{const n=r?.renderBefore??e;let s=n._$litPart$;if(s===void 0){const i=r?.renderBefore??null;n._$litPart$=s=new jp(e.insertBefore(oa(),i),i,void 0,r??{})}return s._$AI(t),s},qp=globalThis;let je=class extends ui{constructor(){super(...arguments),this.renderOptions={host:this},this._$Do=void 0}createRenderRoot(){const e=super.createRenderRoot();return this.renderOptions.renderBefore??=e.firstChild,e}update(e){const r=this.render();this.hasUpdated||(this.renderOptions.isConnected=this.isConnected),super.update(e),this._$Do=sf(r,this.renderRoot,this.renderOptions)}connectedCallback(){super.connectedCallback(),this._$Do?.setConnected(!0)}disconnectedCallback(){super.disconnectedCallback(),this._$Do?.setConnected(!1)}render(){return Zr}};je._$litElement$=!0,je.finalized=!0,qp.litElementHydrateSupport?.({LitElement:je});const GF=qp.litElementPolyfillSupport;GF?.({LitElement:je});(qp.litElementVersions??=[]).push("4.2.1");const JF=ue`
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
`,X1=class extends je{render(){return Y`
      <div tabindex="-1">
        <div class="spinner"></div>
        <span class="message visually-hidden"><slot /></span>
      </div>
    `}};X1.styles=[JF];let XF=X1;customElements.get("craft-spinner")||customElements.define("craft-spinner",XF);const of=new Set,mi=new Map;let vs,Hp="ltr",zp="en";const Y1=typeof MutationObserver<"u"&&typeof document<"u"&&typeof document.documentElement<"u";if(Y1){const t=new MutationObserver(Z1);Hp=document.documentElement.dir||"ltr",zp=document.documentElement.lang||navigator.language,t.observe(document.documentElement,{attributes:!0,attributeFilter:["dir","lang"]})}function Q1(...t){t.map(e=>{const r=e.$code.toLowerCase();mi.has(r)?mi.set(r,Object.assign(Object.assign({},mi.get(r)),e)):mi.set(r,e),vs||(vs=e)}),Z1()}function Z1(){Y1&&(Hp=document.documentElement.dir||"ltr",zp=document.documentElement.lang||navigator.language),[...of.keys()].map(t=>{typeof t.requestUpdate=="function"&&t.requestUpdate()})}let YF=class{constructor(e){this.host=e,this.host.addController(this)}hostConnected(){of.add(this.host)}hostDisconnected(){of.delete(this.host)}dir(){return`${this.host.dir||Hp}`.toLowerCase()}lang(){return`${this.host.lang||zp}`.toLowerCase()}getTranslationData(e){var r,n;const s=new Intl.Locale(e.replace(/_/g,"-")),i=s?.language.toLowerCase(),o=(n=(r=s?.region)===null||r===void 0?void 0:r.toLowerCase())!==null&&n!==void 0?n:"",a=mi.get(`${i}-${o}`),l=mi.get(i);return{locale:s,language:i,region:o,primary:a,secondary:l}}exists(e,r){var n;const{primary:s,secondary:i}=this.getTranslationData((n=r.lang)!==null&&n!==void 0?n:this.lang());return r=Object.assign({includeFallback:!1},r),!!(s&&s[e]||i&&i[e]||r.includeFallback&&vs&&vs[e])}term(e,...r){const{primary:n,secondary:s}=this.getTranslationData(this.lang());let i;if(n&&n[e])i=n[e];else if(s&&s[e])i=s[e];else if(vs&&vs[e])i=vs[e];else return console.error(`No translation found for: ${String(e)}`),String(e);return typeof i=="function"?i(...r):i}date(e,r){return e=new Date(e),new Intl.DateTimeFormat(this.lang(),r).format(e)}number(e,r){return e=Number(e),isNaN(e)?"":new Intl.NumberFormat(this.lang(),r).format(e)}relativeTime(e,r,n){return new Intl.RelativeTimeFormat(this.lang(),n).format(e,r)}};var eE={$code:"en",$name:"English",$dir:"ltr",carousel:"Carousel",clearEntry:"Clear entry",close:"Close",copied:"Copied",copy:"Copy",currentValue:"Current value",error:"Error",goToSlide:(t,e)=>`Go to slide ${t} of ${e}`,hidePassword:"Hide password",loading:"Loading",nextSlide:"Next slide",numOptionsSelected:t=>t===0?"No options selected":t===1?"1 option selected":`${t} options selected`,pauseAnimation:"Pause animation",playAnimation:"Play animation",previousSlide:"Previous slide",progress:"Progress",remove:"Remove",resize:"Resize",scrollableRegion:"Scrollable region",scrollToEnd:"Scroll to end",scrollToStart:"Scroll to start",selectAColorFromTheScreen:"Select a color from the screen",showPassword:"Show password",slideNum:t=>`Slide ${t}`,toggleColorFormat:"Toggle color format",zoomIn:"Zoom in",zoomOut:"Zoom out"};Q1(eE);var QF=eE,Zi=class extends YF{};Q1(QF);var Ca=class extends Event{constructor(){super("wa-after-hide",{bubbles:!0,cancelable:!1,composed:!0})}},Aa=class extends Event{constructor(){super("wa-after-show",{bubbles:!0,cancelable:!1,composed:!0})}},$a=class extends Event{constructor(e){super("wa-hide",{bubbles:!0,cancelable:!0,composed:!0}),this.detail=e}},Ta=class extends Event{constructor(){super("wa-show",{bubbles:!0,cancelable:!0,composed:!0})}};function Mt(t,e){return new Promise(r=>{const n=new AbortController,{signal:s}=n;if(t.classList.contains(e))return;t.classList.remove(e),t.classList.add(e);let i=()=>{t.classList.remove(e),r(),n.abort()};t.addEventListener("animationend",i,{once:!0,signal:s}),t.addEventListener("animationcancel",i,{once:!0,signal:s})})}const ZF={attribute:!0,type:String,converter:hc,reflect:!1,hasChanged:Vp},eM=(t=ZF,e,r)=>{const{kind:n,metadata:s}=r;let i=globalThis.litPropertyMetadata.get(s);if(i===void 0&&globalThis.litPropertyMetadata.set(s,i=new Map),n==="setter"&&((t=Object.create(t)).wrapped=!0),i.set(r.name,t),n==="accessor"){const{name:o}=r;return{set(a){const l=e.get.call(this);e.set.call(this,a),this.requestUpdate(o,l,t)},init(a){return a!==void 0&&this.C(o,void 0,t,a),a}}}if(n==="setter"){const{name:o}=r;return function(a){const l=this[o];e.call(this,a),this.requestUpdate(o,l,t)}}throw Error("Unsupported decorator location: "+n)};function U(t){return(e,r)=>typeof r=="object"?eM(t,e,r):((n,s,i)=>{const o=s.hasOwnProperty(i);return s.constructor.createProperty(i,n),o?Object.getOwnPropertyDescriptor(s,i):void 0})(t,e,r)}const qr=t=>(e,r)=>{r!==void 0?r.addInitializer((()=>{customElements.define(t,e)})):customElements.define(t,e)};var tM=Object.defineProperty,rM=Object.getOwnPropertyDescriptor,tE=t=>{throw TypeError(t)},F=(t,e,r,n)=>{for(var s=n>1?void 0:n?rM(e,r):e,i=t.length-1,o;i>=0;i--)(o=t[i])&&(s=(n?o(e,r,s):o(s))||s);return n&&s&&tM(e,r,s),s},rE=(t,e,r)=>e.has(t)||tE("Cannot "+r),nM=(t,e,r)=>(rE(t,e,"read from private field"),e.get(t)),sM=(t,e,r)=>e.has(t)?tE("Cannot add the same private member more than once"):e instanceof WeakSet?e.add(t):e.set(t,r),iM=(t,e,r,n)=>(rE(t,e,"write to private field"),e.set(t,r),r),oM=`:host {
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
`,Cl,ar=class extends je{constructor(){super(),sM(this,Cl,!1),this.initialReflectedProperties=new Map,this.didSSR=!!this.shadowRoot,this.customStates={set:(r,n)=>{if(this.internals?.states)try{n?this.internals.states.add(r):this.internals.states.delete(r)}catch(s){if(String(s).includes("must start with '--'"))console.error("Your browser implements an outdated version of CustomStateSet. Consider using a polyfill");else throw s}},has:r=>{if(!this.internals?.states)return!1;try{return this.internals.states.has(r)}catch{return!1}}};try{this.internals=this.attachInternals()}catch{console.error("Element internals are not supported in your browser. Consider using a polyfill")}this.customStates.set("wa-defined",!0);let e=this.constructor;for(let[r,n]of e.elementProperties)n.default==="inherit"&&n.initial!==void 0&&typeof r=="string"&&this.customStates.set(`initial-${r}-${n.initial}`,!0)}static get styles(){const e=Array.isArray(this.css)?this.css:this.css?[this.css]:[];return[oM,...e].map(r=>typeof r=="string"?q1(r):r)}attributeChangedCallback(e,r,n){nM(this,Cl)||(this.constructor.elementProperties.forEach((s,i)=>{s.reflect&&this[i]!=null&&this.initialReflectedProperties.set(i,this[i])}),iM(this,Cl,!0)),super.attributeChangedCallback(e,r,n)}willUpdate(e){super.willUpdate(e),this.initialReflectedProperties.forEach((r,n)=>{e.has(n)&&this[n]==null&&(this[n]=r)})}firstUpdated(e){super.firstUpdated(e),this.didSSR&&this.shadowRoot?.querySelectorAll("slot").forEach(r=>{r.dispatchEvent(new Event("slotchange",{bubbles:!0,composed:!1,cancelable:!1}))})}update(e){try{super.update(e)}catch(r){if(this.didSSR&&!this.hasUpdated){const n=new Event("lit-hydration-error",{bubbles:!0,composed:!0,cancelable:!1});n.error=r,this.dispatchEvent(n)}throw r}}relayNativeEvent(e,r){e.stopImmediatePropagation(),this.dispatchEvent(new e.constructor(e.type,{...e,...r}))}};Cl=new WeakMap;F([U()],ar.prototype,"dir",2);F([U()],ar.prototype,"lang",2);F([U({type:Boolean,reflect:!0,attribute:"did-ssr"})],ar.prototype,"didSSR",2);const nE=(t,e,r)=>(r.configurable=!0,r.enumerable=!0,Reflect.decorate&&typeof e!="object"&&Object.defineProperty(t,e,r),r);function Qe(t,e){return(r,n,s)=>{const i=o=>o.renderRoot?.querySelector(t)??null;return nE(r,n,{get(){return i(this)}})}}const Wp={ATTRIBUTE:1,CHILD:2},Kp=t=>(...e)=>({_$litDirective$:t,values:e});let Gp=class{constructor(e){}get _$AU(){return this._$AM._$AU}_$AT(e,r,n){this._$Ct=e,this._$AM=r,this._$Ci=n}_$AS(e,r){return this.update(e,r)}update(e,r){return this.render(...r)}};const en=Kp(class extends Gp{constructor(t){if(super(t),t.type!==Wp.ATTRIBUTE||t.name!=="class"||t.strings?.length>2)throw Error("`classMap()` can only be used in the `class` attribute and must be the only part in the attribute.")}render(t){return" "+Object.keys(t).filter((e=>t[e])).join(" ")+" "}update(t,[e]){if(this.st===void 0){this.st=new Set,t.strings!==void 0&&(this.nt=new Set(t.strings.join(" ").split(/\s/).filter((n=>n!==""))));for(const n in e)e[n]&&!this.nt?.has(n)&&this.st.add(n);return this.render(e)}const r=t.element.classList;for(const n of this.st)n in e||(r.remove(n),this.st.delete(n));for(const n in e){const s=!!e[n];s===this.st.has(n)||this.nt?.has(n)||(s?(r.add(n),this.st.add(n)):(r.remove(n),this.st.delete(n)))}return Zr}});var aM=class extends Event{constructor(){super("wa-reposition",{bubbles:!0,cancelable:!1,composed:!0})}};const os=Math.min,pr=Math.max,pc=Math.round,il=Math.floor,Xr=t=>({x:t,y:t}),lM={left:"right",right:"left",bottom:"top",top:"bottom"},cM={start:"end",end:"start"};function af(t,e,r){return pr(t,os(e,r))}function eo(t,e){return typeof t=="function"?t(e):t}function as(t){return t.split("-")[0]}function to(t){return t.split("-")[1]}function sE(t){return t==="x"?"y":"x"}function Jp(t){return t==="y"?"height":"width"}const uM=new Set(["top","bottom"]);function yn(t){return uM.has(as(t))?"y":"x"}function Xp(t){return sE(yn(t))}function dM(t,e,r){r===void 0&&(r=!1);const n=to(t),s=Xp(t),i=Jp(s);let o=s==="x"?n===(r?"end":"start")?"right":"left":n==="start"?"bottom":"top";return e.reference[i]>e.floating[i]&&(o=mc(o)),[o,mc(o)]}function hM(t){const e=mc(t);return[lf(t),e,lf(e)]}function lf(t){return t.replace(/start|end/g,e=>cM[e])}const Mb=["left","right"],Db=["right","left"],fM=["top","bottom"],pM=["bottom","top"];function mM(t,e,r){switch(t){case"top":case"bottom":return r?e?Db:Mb:e?Mb:Db;case"left":case"right":return e?fM:pM;default:return[]}}function gM(t,e,r,n){const s=to(t);let i=mM(as(t),r==="start",n);return s&&(i=i.map(o=>o+"-"+s),e&&(i=i.concat(i.map(lf)))),i}function mc(t){return t.replace(/left|right|bottom|top/g,e=>lM[e])}function yM(t){return{top:0,right:0,bottom:0,left:0,...t}}function iE(t){return typeof t!="number"?yM(t):{top:t,right:t,bottom:t,left:t}}function gc(t){const{x:e,y:r,width:n,height:s}=t;return{width:n,height:s,top:r,left:e,right:e+n,bottom:r+s,x:e,y:r}}function Vb(t,e,r){let{reference:n,floating:s}=t;const i=yn(e),o=Xp(e),a=Jp(o),l=as(e),u=i==="y",c=n.x+n.width/2-s.width/2,d=n.y+n.height/2-s.height/2,p=n[a]/2-s[a]/2;let f;switch(l){case"top":f={x:c,y:n.y-s.height};break;case"bottom":f={x:c,y:n.y+n.height};break;case"right":f={x:n.x+n.width,y:d};break;case"left":f={x:n.x-s.width,y:d};break;default:f={x:n.x,y:n.y}}switch(to(e)){case"start":f[o]-=p*(r&&u?-1:1);break;case"end":f[o]+=p*(r&&u?-1:1);break}return f}const bM=async(t,e,r)=>{const{placement:n="bottom",strategy:s="absolute",middleware:i=[],platform:o}=r,a=i.filter(Boolean),l=await(o.isRTL==null?void 0:o.isRTL(e));let u=await o.getElementRects({reference:t,floating:e,strategy:s}),{x:c,y:d}=Vb(u,n,l),p=n,f={},h=0;for(let m=0;m<a.length;m++){const{name:g,fn:v}=a[m],{x:b,y:w,data:_,reset:S}=await v({x:c,y:d,initialPlacement:n,placement:p,strategy:s,middlewareData:f,rects:u,platform:o,elements:{reference:t,floating:e}});c=b??c,d=w??d,f={...f,[g]:{...f[g],..._}},S&&h<=50&&(h++,typeof S=="object"&&(S.placement&&(p=S.placement),S.rects&&(u=S.rects===!0?await o.getElementRects({reference:t,floating:e,strategy:s}):S.rects),{x:c,y:d}=Vb(u,p,l)),m=-1)}return{x:c,y:d,placement:p,strategy:s,middlewareData:f}};async function Yp(t,e){var r;e===void 0&&(e={});const{x:n,y:s,platform:i,rects:o,elements:a,strategy:l}=t,{boundary:u="clippingAncestors",rootBoundary:c="viewport",elementContext:d="floating",altBoundary:p=!1,padding:f=0}=eo(e,t),h=iE(f),m=a[p?d==="floating"?"reference":"floating":d],g=gc(await i.getClippingRect({element:(r=await(i.isElement==null?void 0:i.isElement(m)))==null||r?m:m.contextElement||await(i.getDocumentElement==null?void 0:i.getDocumentElement(a.floating)),boundary:u,rootBoundary:c,strategy:l})),v=d==="floating"?{x:n,y:s,width:o.floating.width,height:o.floating.height}:o.reference,b=await(i.getOffsetParent==null?void 0:i.getOffsetParent(a.floating)),w=await(i.isElement==null?void 0:i.isElement(b))?await(i.getScale==null?void 0:i.getScale(b))||{x:1,y:1}:{x:1,y:1},_=gc(i.convertOffsetParentRelativeRectToViewportRelativeRect?await i.convertOffsetParentRelativeRectToViewportRelativeRect({elements:a,rect:v,offsetParent:b,strategy:l}):v);return{top:(g.top-_.top+h.top)/w.y,bottom:(_.bottom-g.bottom+h.bottom)/w.y,left:(g.left-_.left+h.left)/w.x,right:(_.right-g.right+h.right)/w.x}}const vM=t=>({name:"arrow",options:t,async fn(e){const{x:r,y:n,placement:s,rects:i,platform:o,elements:a,middlewareData:l}=e,{element:u,padding:c=0}=eo(t,e)||{};if(u==null)return{};const d=iE(c),p={x:r,y:n},f=Xp(s),h=Jp(f),m=await o.getDimensions(u),g=f==="y",v=g?"top":"left",b=g?"bottom":"right",w=g?"clientHeight":"clientWidth",_=i.reference[h]+i.reference[f]-p[f]-i.floating[h],S=p[f]-i.reference[f],k=await(o.getOffsetParent==null?void 0:o.getOffsetParent(u));let O=k?k[w]:0;(!O||!await(o.isElement==null?void 0:o.isElement(k)))&&(O=a.floating[w]||i.floating[h]);const R=_/2-S/2,C=O/2-m[h]/2-1,A=os(d[v],C),j=os(d[b],C),$=A,y=O-m[h]-j,I=O/2-m[h]/2+R,q=af($,I,y),D=!l.arrow&&to(s)!=null&&I!==q&&i.reference[h]/2-(I<$?A:j)-m[h]/2<0,z=D?I<$?I-$:I-y:0;return{[f]:p[f]+z,data:{[f]:q,centerOffset:I-q-z,...D&&{alignmentOffset:z}},reset:D}}}),wM=function(t){return t===void 0&&(t={}),{name:"flip",options:t,async fn(e){var r,n;const{placement:s,middlewareData:i,rects:o,initialPlacement:a,platform:l,elements:u}=e,{mainAxis:c=!0,crossAxis:d=!0,fallbackPlacements:p,fallbackStrategy:f="bestFit",fallbackAxisSideDirection:h="none",flipAlignment:m=!0,...g}=eo(t,e);if((r=i.arrow)!=null&&r.alignmentOffset)return{};const v=as(s),b=yn(a),w=as(a)===a,_=await(l.isRTL==null?void 0:l.isRTL(u.floating)),S=p||(w||!m?[mc(a)]:hM(a)),k=h!=="none";!p&&k&&S.push(...gM(a,m,h,_));const O=[a,...S],R=await Yp(e,g),C=[];let A=((n=i.flip)==null?void 0:n.overflows)||[];if(c&&C.push(R[v]),d){const I=dM(s,o,_);C.push(R[I[0]],R[I[1]])}if(A=[...A,{placement:s,overflows:C}],!C.every(I=>I<=0)){var j,$;const I=(((j=i.flip)==null?void 0:j.index)||0)+1,q=O[I];if(q&&(!(d==="alignment"&&b!==yn(q))||A.every(z=>yn(z.placement)===b?z.overflows[0]>0:!0)))return{data:{index:I,overflows:A},reset:{placement:q}};let D=($=A.filter(z=>z.overflows[0]<=0).sort((z,P)=>z.overflows[1]-P.overflows[1])[0])==null?void 0:$.placement;if(!D)switch(f){case"bestFit":{var y;const z=(y=A.filter(P=>{if(k){const ee=yn(P.placement);return ee===b||ee==="y"}return!0}).map(P=>[P.placement,P.overflows.filter(ee=>ee>0).reduce((ee,ye)=>ee+ye,0)]).sort((P,ee)=>P[1]-ee[1])[0])==null?void 0:y[0];z&&(D=z);break}case"initialPlacement":D=a;break}if(s!==D)return{reset:{placement:D}}}return{}}}},_M=new Set(["left","top"]);async function EM(t,e){const{placement:r,platform:n,elements:s}=t,i=await(n.isRTL==null?void 0:n.isRTL(s.floating)),o=as(r),a=to(r),l=yn(r)==="y",u=_M.has(o)?-1:1,c=i&&l?-1:1,d=eo(e,t);let{mainAxis:p,crossAxis:f,alignmentAxis:h}=typeof d=="number"?{mainAxis:d,crossAxis:0,alignmentAxis:null}:{mainAxis:d.mainAxis||0,crossAxis:d.crossAxis||0,alignmentAxis:d.alignmentAxis};return a&&typeof h=="number"&&(f=a==="end"?h*-1:h),l?{x:f*c,y:p*u}:{x:p*u,y:f*c}}const SM=function(t){return t===void 0&&(t=0),{name:"offset",options:t,async fn(e){var r,n;const{x:s,y:i,placement:o,middlewareData:a}=e,l=await EM(e,t);return o===((r=a.offset)==null?void 0:r.placement)&&(n=a.arrow)!=null&&n.alignmentOffset?{}:{x:s+l.x,y:i+l.y,data:{...l,placement:o}}}}},xM=function(t){return t===void 0&&(t={}),{name:"shift",options:t,async fn(e){const{x:r,y:n,placement:s}=e,{mainAxis:i=!0,crossAxis:o=!1,limiter:a={fn:g=>{let{x:v,y:b}=g;return{x:v,y:b}}},...l}=eo(t,e),u={x:r,y:n},c=await Yp(e,l),d=yn(as(s)),p=sE(d);let f=u[p],h=u[d];if(i){const g=p==="y"?"top":"left",v=p==="y"?"bottom":"right",b=f+c[g],w=f-c[v];f=af(b,f,w)}if(o){const g=d==="y"?"top":"left",v=d==="y"?"bottom":"right",b=h+c[g],w=h-c[v];h=af(b,h,w)}const m=a.fn({...e,[p]:f,[d]:h});return{...m,data:{x:m.x-r,y:m.y-n,enabled:{[p]:i,[d]:o}}}}}},CM=function(t){return t===void 0&&(t={}),{name:"size",options:t,async fn(e){var r,n;const{placement:s,rects:i,platform:o,elements:a}=e,{apply:l=()=>{},...u}=eo(t,e),c=await Yp(e,u),d=as(s),p=to(s),f=yn(s)==="y",{width:h,height:m}=i.floating;let g,v;d==="top"||d==="bottom"?(g=d,v=p===(await(o.isRTL==null?void 0:o.isRTL(a.floating))?"start":"end")?"left":"right"):(v=d,g=p==="end"?"top":"bottom");const b=m-c.top-c.bottom,w=h-c.left-c.right,_=os(m-c[g],b),S=os(h-c[v],w),k=!e.middlewareData.shift;let O=_,R=S;if((r=e.middlewareData.shift)!=null&&r.enabled.x&&(R=w),(n=e.middlewareData.shift)!=null&&n.enabled.y&&(O=b),k&&!p){const A=pr(c.left,0),j=pr(c.right,0),$=pr(c.top,0),y=pr(c.bottom,0);f?R=h-2*(A!==0||j!==0?A+j:pr(c.left,c.right)):O=m-2*($!==0||y!==0?$+y:pr(c.top,c.bottom))}await l({...e,availableWidth:R,availableHeight:O});const C=await o.getDimensions(a.floating);return h!==C.width||m!==C.height?{reset:{rects:!0}}:{}}}};function cu(){return typeof window<"u"}function ro(t){return oE(t)?(t.nodeName||"").toLowerCase():"#document"}function yr(t){var e;return(t==null||(e=t.ownerDocument)==null?void 0:e.defaultView)||window}function nn(t){var e;return(e=(oE(t)?t.ownerDocument:t.document)||window.document)==null?void 0:e.documentElement}function oE(t){return cu()?t instanceof Node||t instanceof yr(t).Node:!1}function Dr(t){return cu()?t instanceof Element||t instanceof yr(t).Element:!1}function tn(t){return cu()?t instanceof HTMLElement||t instanceof yr(t).HTMLElement:!1}function Bb(t){return!cu()||typeof ShadowRoot>"u"?!1:t instanceof ShadowRoot||t instanceof yr(t).ShadowRoot}const AM=new Set(["inline","contents"]);function ka(t){const{overflow:e,overflowX:r,overflowY:n,display:s}=Vr(t);return/auto|scroll|overlay|hidden|clip/.test(e+n+r)&&!AM.has(s)}const $M=new Set(["table","td","th"]);function TM(t){return $M.has(ro(t))}const kM=[":popover-open",":modal"];function uu(t){return kM.some(e=>{try{return t.matches(e)}catch{return!1}})}const PM=["transform","translate","scale","rotate","perspective"],OM=["transform","translate","scale","rotate","perspective","filter"],RM=["paint","layout","strict","content"];function du(t){const e=Qp(),r=Dr(t)?Vr(t):t;return PM.some(n=>r[n]?r[n]!=="none":!1)||(r.containerType?r.containerType!=="normal":!1)||!e&&(r.backdropFilter?r.backdropFilter!=="none":!1)||!e&&(r.filter?r.filter!=="none":!1)||OM.some(n=>(r.willChange||"").includes(n))||RM.some(n=>(r.contain||"").includes(n))}function NM(t){let e=ls(t);for(;tn(e)&&!Di(e);){if(du(e))return e;if(uu(e))return null;e=ls(e)}return null}function Qp(){return typeof CSS>"u"||!CSS.supports?!1:CSS.supports("-webkit-backdrop-filter","none")}const IM=new Set(["html","body","#document"]);function Di(t){return IM.has(ro(t))}function Vr(t){return yr(t).getComputedStyle(t)}function hu(t){return Dr(t)?{scrollLeft:t.scrollLeft,scrollTop:t.scrollTop}:{scrollLeft:t.scrollX,scrollTop:t.scrollY}}function ls(t){if(ro(t)==="html")return t;const e=t.assignedSlot||t.parentNode||Bb(t)&&t.host||nn(t);return Bb(e)?e.host:e}function aE(t){const e=ls(t);return Di(e)?t.ownerDocument?t.ownerDocument.body:t.body:tn(e)&&ka(e)?e:aE(e)}function Vi(t,e,r){var n;e===void 0&&(e=[]),r===void 0&&(r=!0);const s=aE(t),i=s===((n=t.ownerDocument)==null?void 0:n.body),o=yr(s);if(i){const a=cf(o);return e.concat(o,o.visualViewport||[],ka(s)?s:[],a&&r?Vi(a):[])}return e.concat(s,Vi(s,[],r))}function cf(t){return t.parent&&Object.getPrototypeOf(t.parent)?t.frameElement:null}function lE(t){const e=Vr(t);let r=parseFloat(e.width)||0,n=parseFloat(e.height)||0;const s=tn(t),i=s?t.offsetWidth:r,o=s?t.offsetHeight:n,a=pc(r)!==i||pc(n)!==o;return a&&(r=i,n=o),{width:r,height:n,$:a}}function Zp(t){return Dr(t)?t:t.contextElement}function xi(t){const e=Zp(t);if(!tn(e))return Xr(1);const r=e.getBoundingClientRect(),{width:n,height:s,$:i}=lE(e);let o=(i?pc(r.width):r.width)/n,a=(i?pc(r.height):r.height)/s;return(!o||!Number.isFinite(o))&&(o=1),(!a||!Number.isFinite(a))&&(a=1),{x:o,y:a}}const LM=Xr(0);function cE(t){const e=yr(t);return!Qp()||!e.visualViewport?LM:{x:e.visualViewport.offsetLeft,y:e.visualViewport.offsetTop}}function FM(t,e,r){return e===void 0&&(e=!1),!r||e&&r!==yr(t)?!1:e}function zs(t,e,r,n){e===void 0&&(e=!1),r===void 0&&(r=!1);const s=t.getBoundingClientRect(),i=Zp(t);let o=Xr(1);e&&(n?Dr(n)&&(o=xi(n)):o=xi(t));const a=FM(i,r,n)?cE(i):Xr(0);let l=(s.left+a.x)/o.x,u=(s.top+a.y)/o.y,c=s.width/o.x,d=s.height/o.y;if(i){const p=yr(i),f=n&&Dr(n)?yr(n):n;let h=p,m=cf(h);for(;m&&n&&f!==h;){const g=xi(m),v=m.getBoundingClientRect(),b=Vr(m),w=v.left+(m.clientLeft+parseFloat(b.paddingLeft))*g.x,_=v.top+(m.clientTop+parseFloat(b.paddingTop))*g.y;l*=g.x,u*=g.y,c*=g.x,d*=g.y,l+=w,u+=_,h=yr(m),m=cf(h)}}return gc({width:c,height:d,x:l,y:u})}function fu(t,e){const r=hu(t).scrollLeft;return e?e.left+r:zs(nn(t)).left+r}function uE(t,e){const r=t.getBoundingClientRect(),n=r.left+e.scrollLeft-fu(t,r),s=r.top+e.scrollTop;return{x:n,y:s}}function MM(t){let{elements:e,rect:r,offsetParent:n,strategy:s}=t;const i=s==="fixed",o=nn(n),a=e?uu(e.floating):!1;if(n===o||a&&i)return r;let l={scrollLeft:0,scrollTop:0},u=Xr(1);const c=Xr(0),d=tn(n);if((d||!d&&!i)&&((ro(n)!=="body"||ka(o))&&(l=hu(n)),tn(n))){const f=zs(n);u=xi(n),c.x=f.x+n.clientLeft,c.y=f.y+n.clientTop}const p=o&&!d&&!i?uE(o,l):Xr(0);return{width:r.width*u.x,height:r.height*u.y,x:r.x*u.x-l.scrollLeft*u.x+c.x+p.x,y:r.y*u.y-l.scrollTop*u.y+c.y+p.y}}function DM(t){return Array.from(t.getClientRects())}function VM(t){const e=nn(t),r=hu(t),n=t.ownerDocument.body,s=pr(e.scrollWidth,e.clientWidth,n.scrollWidth,n.clientWidth),i=pr(e.scrollHeight,e.clientHeight,n.scrollHeight,n.clientHeight);let o=-r.scrollLeft+fu(t);const a=-r.scrollTop;return Vr(n).direction==="rtl"&&(o+=pr(e.clientWidth,n.clientWidth)-s),{width:s,height:i,x:o,y:a}}const Ub=25;function BM(t,e){const r=yr(t),n=nn(t),s=r.visualViewport;let i=n.clientWidth,o=n.clientHeight,a=0,l=0;if(s){i=s.width,o=s.height;const c=Qp();(!c||c&&e==="fixed")&&(a=s.offsetLeft,l=s.offsetTop)}const u=fu(n);if(u<=0){const c=n.ownerDocument,d=c.body,p=getComputedStyle(d),f=c.compatMode==="CSS1Compat"&&parseFloat(p.marginLeft)+parseFloat(p.marginRight)||0,h=Math.abs(n.clientWidth-d.clientWidth-f);h<=Ub&&(i-=h)}else u<=Ub&&(i+=u);return{width:i,height:o,x:a,y:l}}const UM=new Set(["absolute","fixed"]);function jM(t,e){const r=zs(t,!0,e==="fixed"),n=r.top+t.clientTop,s=r.left+t.clientLeft,i=tn(t)?xi(t):Xr(1),o=t.clientWidth*i.x,a=t.clientHeight*i.y,l=s*i.x,u=n*i.y;return{width:o,height:a,x:l,y:u}}function jb(t,e,r){let n;if(e==="viewport")n=BM(t,r);else if(e==="document")n=VM(nn(t));else if(Dr(e))n=jM(e,r);else{const s=cE(t);n={x:e.x-s.x,y:e.y-s.y,width:e.width,height:e.height}}return gc(n)}function dE(t,e){const r=ls(t);return r===e||!Dr(r)||Di(r)?!1:Vr(r).position==="fixed"||dE(r,e)}function qM(t,e){const r=e.get(t);if(r)return r;let n=Vi(t,[],!1).filter(a=>Dr(a)&&ro(a)!=="body"),s=null;const i=Vr(t).position==="fixed";let o=i?ls(t):t;for(;Dr(o)&&!Di(o);){const a=Vr(o),l=du(o);!l&&a.position==="fixed"&&(s=null),(i?!l&&!s:!l&&a.position==="static"&&s&&UM.has(s.position)||ka(o)&&!l&&dE(t,o))?n=n.filter(u=>u!==o):s=a,o=ls(o)}return e.set(t,n),n}function HM(t){let{element:e,boundary:r,rootBoundary:n,strategy:s}=t;const i=[...r==="clippingAncestors"?uu(e)?[]:qM(e,this._c):[].concat(r),n],o=i[0],a=i.reduce((l,u)=>{const c=jb(e,u,s);return l.top=pr(c.top,l.top),l.right=os(c.right,l.right),l.bottom=os(c.bottom,l.bottom),l.left=pr(c.left,l.left),l},jb(e,o,s));return{width:a.right-a.left,height:a.bottom-a.top,x:a.left,y:a.top}}function zM(t){const{width:e,height:r}=lE(t);return{width:e,height:r}}function WM(t,e,r){const n=tn(e),s=nn(e),i=r==="fixed",o=zs(t,!0,i,e);let a={scrollLeft:0,scrollTop:0};const l=Xr(0);function u(){l.x=fu(s)}if(n||!n&&!i)if((ro(e)!=="body"||ka(s))&&(a=hu(e)),n){const f=zs(e,!0,i,e);l.x=f.x+e.clientLeft,l.y=f.y+e.clientTop}else s&&u();i&&!n&&s&&u();const c=s&&!n&&!i?uE(s,a):Xr(0),d=o.left+a.scrollLeft-l.x-c.x,p=o.top+a.scrollTop-l.y-c.y;return{x:d,y:p,width:o.width,height:o.height}}function Gd(t){return Vr(t).position==="static"}function qb(t,e){if(!tn(t)||Vr(t).position==="fixed")return null;if(e)return e(t);let r=t.offsetParent;return nn(t)===r&&(r=r.ownerDocument.body),r}function hE(t,e){const r=yr(t);if(uu(t))return r;if(!tn(t)){let s=ls(t);for(;s&&!Di(s);){if(Dr(s)&&!Gd(s))return s;s=ls(s)}return r}let n=qb(t,e);for(;n&&TM(n)&&Gd(n);)n=qb(n,e);return n&&Di(n)&&Gd(n)&&!du(n)?r:n||NM(t)||r}const KM=async function(t){const e=this.getOffsetParent||hE,r=this.getDimensions,n=await r(t.floating);return{reference:WM(t.reference,await e(t.floating),t.strategy),floating:{x:0,y:0,width:n.width,height:n.height}}};function GM(t){return Vr(t).direction==="rtl"}const Al={convertOffsetParentRelativeRectToViewportRelativeRect:MM,getDocumentElement:nn,getClippingRect:HM,getOffsetParent:hE,getElementRects:KM,getClientRects:DM,getDimensions:zM,getScale:xi,isElement:Dr,isRTL:GM};function fE(t,e){return t.x===e.x&&t.y===e.y&&t.width===e.width&&t.height===e.height}function JM(t,e){let r=null,n;const s=nn(t);function i(){var a;clearTimeout(n),(a=r)==null||a.disconnect(),r=null}function o(a,l){a===void 0&&(a=!1),l===void 0&&(l=1),i();const u=t.getBoundingClientRect(),{left:c,top:d,width:p,height:f}=u;if(a||e(),!p||!f)return;const h=il(d),m=il(s.clientWidth-(c+p)),g=il(s.clientHeight-(d+f)),v=il(c),b={rootMargin:-h+"px "+-m+"px "+-g+"px "+-v+"px",threshold:pr(0,os(1,l))||1};let w=!0;function _(S){const k=S[0].intersectionRatio;if(k!==l){if(!w)return o();k?o(!1,k):n=setTimeout(()=>{o(!1,1e-7)},1e3)}k===1&&!fE(u,t.getBoundingClientRect())&&o(),w=!1}try{r=new IntersectionObserver(_,{...b,root:s.ownerDocument})}catch{r=new IntersectionObserver(_,b)}r.observe(t)}return o(!0),i}function pE(t,e,r,n){n===void 0&&(n={});const{ancestorScroll:s=!0,ancestorResize:i=!0,elementResize:o=typeof ResizeObserver=="function",layoutShift:a=typeof IntersectionObserver=="function",animationFrame:l=!1}=n,u=Zp(t),c=s||i?[...u?Vi(u):[],...Vi(e)]:[];c.forEach(v=>{s&&v.addEventListener("scroll",r,{passive:!0}),i&&v.addEventListener("resize",r)});const d=u&&a?JM(u,r):null;let p=-1,f=null;o&&(f=new ResizeObserver(v=>{let[b]=v;b&&b.target===u&&f&&(f.unobserve(e),cancelAnimationFrame(p),p=requestAnimationFrame(()=>{var w;(w=f)==null||w.observe(e)})),r()}),u&&!l&&f.observe(u),f.observe(e));let h,m=l?zs(t):null;l&&g();function g(){const v=zs(t);m&&!fE(m,v)&&r(),m=v,h=requestAnimationFrame(g)}return r(),()=>{var v;c.forEach(b=>{s&&b.removeEventListener("scroll",r),i&&b.removeEventListener("resize",r)}),d?.(),(v=f)==null||v.disconnect(),f=null,l&&cancelAnimationFrame(h)}}const mE=SM,gE=xM,yE=wM,Hb=CM,XM=vM,bE=(t,e,r)=>{const n=new Map,s={platform:Al,...r},i={...s.platform,_c:n};return bM(t,e,{...s,platform:i})};function YM(t){return QM(t)}function Jd(t){return t.assignedSlot?t.assignedSlot:t.parentNode instanceof ShadowRoot?t.parentNode.host:t.parentNode}function QM(t){for(let e=t;e;e=Jd(e))if(e instanceof Element&&getComputedStyle(e).display==="none")return null;for(let e=Jd(t);e;e=Jd(e)){if(!(e instanceof Element))continue;const r=getComputedStyle(e);if(r.display!=="contents"&&(r.position!=="static"||du(r)||e.tagName==="BODY"))return e}return null}var ZM=`:host {
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
`;function zb(t){return t!==null&&typeof t=="object"&&"getBoundingClientRect"in t&&("contextElement"in t?t instanceof Element:!0)}var ol=globalThis?.HTMLElement?.prototype.hasOwnProperty("popover"),Me=class extends ar{constructor(){super(...arguments),this.localize=new Zi(this),this.active=!1,this.placement="top",this.boundary="viewport",this.distance=0,this.skidding=0,this.arrow=!1,this.arrowPlacement="anchor",this.arrowPadding=10,this.flip=!1,this.flipFallbackPlacements="",this.flipFallbackStrategy="best-fit",this.flipPadding=0,this.shift=!1,this.shiftPadding=0,this.autoSizePadding=0,this.hoverBridge=!1,this.updateHoverBridge=()=>{if(this.hoverBridge&&this.anchorEl){const e=this.anchorEl.getBoundingClientRect(),r=this.popup.getBoundingClientRect(),n=this.placement.includes("top")||this.placement.includes("bottom");let s=0,i=0,o=0,a=0,l=0,u=0,c=0,d=0;n?e.top<r.top?(s=e.left,i=e.bottom,o=e.right,a=e.bottom,l=r.left,u=r.top,c=r.right,d=r.top):(s=r.left,i=r.bottom,o=r.right,a=r.bottom,l=e.left,u=e.top,c=e.right,d=e.top):e.left<r.left?(s=e.right,i=e.top,o=r.left,a=r.top,l=e.right,u=e.bottom,c=r.left,d=r.bottom):(s=r.right,i=r.top,o=e.left,a=e.top,l=r.right,u=r.bottom,c=e.left,d=e.bottom),this.style.setProperty("--hover-bridge-top-left-x",`${s}px`),this.style.setProperty("--hover-bridge-top-left-y",`${i}px`),this.style.setProperty("--hover-bridge-top-right-x",`${o}px`),this.style.setProperty("--hover-bridge-top-right-y",`${a}px`),this.style.setProperty("--hover-bridge-bottom-left-x",`${l}px`),this.style.setProperty("--hover-bridge-bottom-left-y",`${u}px`),this.style.setProperty("--hover-bridge-bottom-right-x",`${c}px`),this.style.setProperty("--hover-bridge-bottom-right-y",`${d}px`)}}}async connectedCallback(){super.connectedCallback(),await this.updateComplete,this.start()}disconnectedCallback(){super.disconnectedCallback(),this.stop()}async updated(e){super.updated(e),e.has("active")&&(this.active?this.start():this.stop()),e.has("anchor")&&this.handleAnchorChange(),this.active&&(await this.updateComplete,this.reposition())}async handleAnchorChange(){if(await this.stop(),this.anchor&&typeof this.anchor=="string"){const e=this.getRootNode();this.anchorEl=e.getElementById(this.anchor)}else this.anchor instanceof Element||zb(this.anchor)?this.anchorEl=this.anchor:this.anchorEl=this.querySelector('[slot="anchor"]');this.anchorEl instanceof HTMLSlotElement&&(this.anchorEl=this.anchorEl.assignedElements({flatten:!0})[0]),this.anchorEl&&this.start()}start(){!this.anchorEl||!this.active||(this.popup.showPopover?.(),this.cleanup=pE(this.anchorEl,this.popup,()=>{this.reposition()}))}async stop(){return new Promise(e=>{this.popup.hidePopover?.(),this.cleanup?(this.cleanup(),this.cleanup=void 0,this.removeAttribute("data-current-placement"),this.style.removeProperty("--auto-size-available-width"),this.style.removeProperty("--auto-size-available-height"),requestAnimationFrame(()=>e())):e()})}reposition(){if(!this.active||!this.anchorEl)return;const e=[mE({mainAxis:this.distance,crossAxis:this.skidding})];this.sync?e.push(Hb({apply:({rects:s})=>{const i=this.sync==="width"||this.sync==="both",o=this.sync==="height"||this.sync==="both";this.popup.style.width=i?`${s.reference.width}px`:"",this.popup.style.height=o?`${s.reference.height}px`:""}})):(this.popup.style.width="",this.popup.style.height="");let r;ol&&!zb(this.anchor)&&this.boundary==="scroll"&&(r=Vi(this.anchorEl).filter(s=>s instanceof Element)),this.flip&&e.push(yE({boundary:this.flipBoundary||r,fallbackPlacements:this.flipFallbackPlacements,fallbackStrategy:this.flipFallbackStrategy==="best-fit"?"bestFit":"initialPlacement",padding:this.flipPadding})),this.shift&&e.push(gE({boundary:this.shiftBoundary||r,padding:this.shiftPadding})),this.autoSize?e.push(Hb({boundary:this.autoSizeBoundary||r,padding:this.autoSizePadding,apply:({availableWidth:s,availableHeight:i})=>{this.autoSize==="vertical"||this.autoSize==="both"?this.style.setProperty("--auto-size-available-height",`${i}px`):this.style.removeProperty("--auto-size-available-height"),this.autoSize==="horizontal"||this.autoSize==="both"?this.style.setProperty("--auto-size-available-width",`${s}px`):this.style.removeProperty("--auto-size-available-width")}})):(this.style.removeProperty("--auto-size-available-width"),this.style.removeProperty("--auto-size-available-height")),this.arrow&&e.push(XM({element:this.arrowEl,padding:this.arrowPadding}));const n=ol?s=>Al.getOffsetParent(s,YM):Al.getOffsetParent;bE(this.anchorEl,this.popup,{placement:this.placement,middleware:e,strategy:ol?"absolute":"fixed",platform:{...Al,getOffsetParent:n}}).then(({x:s,y:i,middlewareData:o,placement:a})=>{const l=this.localize.dir()==="rtl",u={top:"bottom",right:"left",bottom:"top",left:"right"}[a.split("-")[0]];if(this.setAttribute("data-current-placement",a),Object.assign(this.popup.style,{left:`${s}px`,top:`${i}px`}),this.arrow){const c=o.arrow.x,d=o.arrow.y;let p="",f="",h="",m="";if(this.arrowPlacement==="start"){const g=typeof c=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"";p=typeof d=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"",f=l?g:"",m=l?"":g}else if(this.arrowPlacement==="end"){const g=typeof c=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"";f=l?"":g,m=l?g:"",h=typeof d=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:""}else this.arrowPlacement==="center"?(m=typeof c=="number"?"calc(50% - var(--arrow-size-diagonal))":"",p=typeof d=="number"?"calc(50% - var(--arrow-size-diagonal))":""):(m=typeof c=="number"?`${c}px`:"",p=typeof d=="number"?`${d}px`:"");Object.assign(this.arrowEl.style,{top:p,right:f,bottom:h,left:m,[u]:"calc(var(--arrow-size-diagonal) * -1)"})}}),requestAnimationFrame(()=>this.updateHoverBridge()),this.dispatchEvent(new aM)}render(){return Y`
      <slot name="anchor" @slotchange=${this.handleAnchorChange}></slot>

      <span
        part="hover-bridge"
        class=${en({"popup-hover-bridge":!0,"popup-hover-bridge-visible":this.hoverBridge&&this.active})}
      ></span>

      <div
        popover="manual"
        part="popup"
        class=${en({popup:!0,"popup-active":this.active,"popup-fixed":!ol,"popup-has-arrow":this.arrow})}
      >
        <slot></slot>
        ${this.arrow?Y`<div part="arrow" class="arrow" role="presentation"></div>`:""}
      </div>
    `}};Me.css=ZM;F([Qe(".popup")],Me.prototype,"popup",2);F([Qe(".arrow")],Me.prototype,"arrowEl",2);F([U()],Me.prototype,"anchor",2);F([U({type:Boolean,reflect:!0})],Me.prototype,"active",2);F([U({reflect:!0})],Me.prototype,"placement",2);F([U()],Me.prototype,"boundary",2);F([U({type:Number})],Me.prototype,"distance",2);F([U({type:Number})],Me.prototype,"skidding",2);F([U({type:Boolean})],Me.prototype,"arrow",2);F([U({attribute:"arrow-placement"})],Me.prototype,"arrowPlacement",2);F([U({attribute:"arrow-padding",type:Number})],Me.prototype,"arrowPadding",2);F([U({type:Boolean})],Me.prototype,"flip",2);F([U({attribute:"flip-fallback-placements",converter:{fromAttribute:t=>t.split(" ").map(e=>e.trim()).filter(e=>e!==""),toAttribute:t=>t.join(" ")}})],Me.prototype,"flipFallbackPlacements",2);F([U({attribute:"flip-fallback-strategy"})],Me.prototype,"flipFallbackStrategy",2);F([U({type:Object})],Me.prototype,"flipBoundary",2);F([U({attribute:"flip-padding",type:Number})],Me.prototype,"flipPadding",2);F([U({type:Boolean})],Me.prototype,"shift",2);F([U({type:Object})],Me.prototype,"shiftBoundary",2);F([U({attribute:"shift-padding",type:Number})],Me.prototype,"shiftPadding",2);F([U({attribute:"auto-size"})],Me.prototype,"autoSize",2);F([U()],Me.prototype,"sync",2);F([U({type:Object})],Me.prototype,"autoSizeBoundary",2);F([U({attribute:"auto-size-padding",type:Number})],Me.prototype,"autoSizePadding",2);F([U({attribute:"hover-bridge",type:Boolean})],Me.prototype,"hoverBridge",2);Me=F([qr("wa-popup")],Me);const e4="useandom-26T198340PX75pxJACKVERYMINDBUSHWOLF_GQZbfghjklqvwyzrict";let t4=(t=21)=>{let e="",r=crypto.getRandomValues(new Uint8Array(t|=0));for(;t--;)e+=e4[r[t]&63];return e};function em(t=""){return`${t}${t4()}`}function yc(t,e){return new Promise(r=>{function n(s){s.target===t&&(t.removeEventListener(e,n),r())}t.addEventListener(e,n)})}function wr(t,e){const r={waitUntilFirstUpdate:!1,...e};return(n,s)=>{const{update:i}=n,o=Array.isArray(t)?t:[t];n.update=function(a){o.forEach(l=>{const u=l;if(a.has(u)){const c=a.get(u),d=this[u];c!==d&&(!r.waitUntilFirstUpdate||this.hasUpdated)&&this[s](c,d)}}),i.call(this,a)}}}function lr(t){return U({...t,state:!0,attribute:!1})}var r4=`:host {
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
`,Xe=class extends ar{constructor(){super(...arguments),this.placement="top",this.disabled=!1,this.distance=8,this.open=!1,this.skidding=0,this.showDelay=150,this.hideDelay=0,this.trigger="hover focus",this.withoutArrow=!1,this.for=null,this.anchor=null,this.eventController=new AbortController,this.handleBlur=()=>{this.hasTrigger("focus")&&this.hide()},this.handleClick=()=>{this.hasTrigger("click")&&(this.open?this.hide():this.show())},this.handleFocus=()=>{this.hasTrigger("focus")&&this.show()},this.handleDocumentKeyDown=e=>{e.key==="Escape"&&(e.stopPropagation(),this.hide())},this.handleMouseOver=()=>{this.hasTrigger("hover")&&(clearTimeout(this.hoverTimeout),this.hoverTimeout=window.setTimeout(()=>this.show(),this.showDelay))},this.handleMouseOut=()=>{this.hasTrigger("hover")&&(clearTimeout(this.hoverTimeout),this.hoverTimeout=window.setTimeout(()=>this.hide(),this.hideDelay))}}connectedCallback(){super.connectedCallback(),this.eventController.signal.aborted&&(this.eventController=new AbortController),this.open&&(this.open=!1,this.updateComplete.then(()=>{this.open=!0})),this.id||(this.id=em("wa-tooltip-")),this.for&&this.anchor?(this.anchor=null,this.handleForChange()):this.for&&this.handleForChange()}disconnectedCallback(){super.disconnectedCallback(),document.removeEventListener("keydown",this.handleDocumentKeyDown),this.eventController.abort(),this.anchor&&this.removeFromAriaLabelledBy(this.anchor,this.id)}firstUpdated(){this.body.hidden=!this.open,this.open&&(this.popup.active=!0,this.popup.reposition())}hasTrigger(e){return this.trigger.split(" ").includes(e)}addToAriaLabelledBy(e,r){const n=(e.getAttribute("aria-labelledby")||"").split(/\s+/).filter(Boolean);n.includes(r)||(n.push(r),e.setAttribute("aria-labelledby",n.join(" ")))}removeFromAriaLabelledBy(e,r){const n=(e.getAttribute("aria-labelledby")||"").split(/\s+/).filter(Boolean).filter(s=>s!==r);n.length>0?e.setAttribute("aria-labelledby",n.join(" ")):e.removeAttribute("aria-labelledby")}async handleOpenChange(){if(this.open){if(this.disabled)return;const e=new Ta;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}document.addEventListener("keydown",this.handleDocumentKeyDown,{signal:this.eventController.signal}),this.body.hidden=!1,this.popup.active=!0,await Mt(this.popup.popup,"show-with-scale"),this.popup.reposition(),this.dispatchEvent(new Aa)}else{const e=new $a;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}document.removeEventListener("keydown",this.handleDocumentKeyDown),await Mt(this.popup.popup,"hide-with-scale"),this.popup.active=!1,this.body.hidden=!0,this.dispatchEvent(new Ca)}}handleForChange(){const e=this.getRootNode();if(!e)return;const r=this.for?e.getElementById(this.for):null,n=this.anchor;if(r===n)return;const{signal:s}=this.eventController;r&&(this.addToAriaLabelledBy(r,this.id),r.addEventListener("blur",this.handleBlur,{capture:!0,signal:s}),r.addEventListener("focus",this.handleFocus,{capture:!0,signal:s}),r.addEventListener("click",this.handleClick,{signal:s}),r.addEventListener("mouseover",this.handleMouseOver,{signal:s}),r.addEventListener("mouseout",this.handleMouseOut,{signal:s})),n&&(this.removeFromAriaLabelledBy(n,this.id),n.removeEventListener("blur",this.handleBlur,{capture:!0}),n.removeEventListener("focus",this.handleFocus,{capture:!0}),n.removeEventListener("click",this.handleClick),n.removeEventListener("mouseover",this.handleMouseOver),n.removeEventListener("mouseout",this.handleMouseOut)),this.anchor=r}async handleOptionsChange(){this.hasUpdated&&(await this.updateComplete,this.popup.reposition())}handleDisabledChange(){this.disabled&&this.open&&this.hide()}async show(){if(!this.open)return this.open=!0,yc(this,"wa-after-show")}async hide(){if(this.open)return this.open=!1,yc(this,"wa-after-hide")}render(){return Y`
      <wa-popup
        part="base"
        exportparts="
          popup:base__popup,
          arrow:base__arrow
        "
        class=${en({tooltip:!0,"tooltip-open":this.open})}
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
    `}};Xe.css=r4;Xe.dependencies={"wa-popup":Me};F([Qe("slot:not([name])")],Xe.prototype,"defaultSlot",2);F([Qe(".body")],Xe.prototype,"body",2);F([Qe("wa-popup")],Xe.prototype,"popup",2);F([U()],Xe.prototype,"placement",2);F([U({type:Boolean,reflect:!0})],Xe.prototype,"disabled",2);F([U({type:Number})],Xe.prototype,"distance",2);F([U({type:Boolean,reflect:!0})],Xe.prototype,"open",2);F([U({type:Number})],Xe.prototype,"skidding",2);F([U({attribute:"show-delay",type:Number})],Xe.prototype,"showDelay",2);F([U({attribute:"hide-delay",type:Number})],Xe.prototype,"hideDelay",2);F([U()],Xe.prototype,"trigger",2);F([U({attribute:"without-arrow",type:Boolean,reflect:!0})],Xe.prototype,"withoutArrow",2);F([U()],Xe.prototype,"for",2);F([lr()],Xe.prototype,"anchor",2);F([wr("open",{waitUntilFirstUpdate:!0})],Xe.prototype,"handleOpenChange",1);F([wr("for")],Xe.prototype,"handleForChange",1);F([wr(["distance","placement","skidding"])],Xe.prototype,"handleOptionsChange",1);F([wr("disabled")],Xe.prototype,"handleDisabledChange",1);Xe=F([qr("wa-tooltip")],Xe);let n4=class extends Xe{static get styles(){return[Xe.styles,ue`
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
      `]}};customElements.get("c-tooltip")||customElements.define("c-tooltip",n4);const s4=ue`
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
`;var i4=ue`
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
`,o4=ue`
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
`,vE=Object.defineProperty,Wb=Object.getOwnPropertySymbols,a4=Object.prototype.hasOwnProperty,l4=Object.prototype.propertyIsEnumerable,wE=t=>{throw TypeError(t)},Kb=(t,e,r)=>e in t?vE(t,e,{enumerable:!0,configurable:!0,writable:!0,value:r}):t[e]=r,c4=(t,e)=>{for(var r in e||(e={}))a4.call(e,r)&&Kb(t,r,e[r]);if(Wb)for(var r of Wb(e))l4.call(e,r)&&Kb(t,r,e[r]);return t},_E=(t,e,r,n)=>{for(var s=void 0,i=t.length-1,o;i>=0;i--)(o=t[i])&&(s=o(e,r,s)||s);return s&&vE(e,r,s),s},EE=(t,e,r)=>e.has(t)||wE("Cannot "+r),u4=(t,e,r)=>(EE(t,e,"read from private field"),e.get(t)),d4=(t,e,r)=>e.has(t)?wE("Cannot add the same private member more than once"):e instanceof WeakSet?e.add(t):e.set(t,r),h4=(t,e,r,n)=>(EE(t,e,"write to private field"),e.set(t,r),r),$l,Pa=class extends je{constructor(){super(),d4(this,$l,!1),this.initialReflectedProperties=new Map,Object.entries(this.constructor.dependencies).forEach(([e,r])=>{this.constructor.define(e,r)})}emit(e,r){const n=new CustomEvent(e,c4({bubbles:!0,cancelable:!1,composed:!0,detail:{}},r));return this.dispatchEvent(n),n}static define(e,r=this,n={}){const s=customElements.get(e);if(!s){try{customElements.define(e,r,n)}catch{customElements.define(e,class extends r{},n)}return}let i=" (unknown version)",o=i;"version"in r&&r.version&&(i=" v"+r.version),"version"in s&&s.version&&(o=" v"+s.version),!(i&&o&&i===o)&&console.warn(`Attempted to register <${e}>${i}, but <${e}>${o} has already been registered.`)}attributeChangedCallback(e,r,n){u4(this,$l)||(this.constructor.elementProperties.forEach((s,i)=>{s.reflect&&this[i]!=null&&this.initialReflectedProperties.set(i,this[i])}),h4(this,$l,!0)),super.attributeChangedCallback(e,r,n)}willUpdate(e){super.willUpdate(e),this.initialReflectedProperties.forEach((r,n)=>{e.has(n)&&this[n]==null&&(this[n]=r)})}};$l=new WeakMap;Pa.version="2.20.1";Pa.dependencies={};_E([U()],Pa.prototype,"dir");_E([U()],Pa.prototype,"lang");var SE=class extends Pa{render(){return Y` <slot></slot> `}};SE.styles=[o4,i4];SE.define("sl-visually-hidden");var f4=Object.defineProperty,tm=(t,e,r,n)=>{for(var s=void 0,i=t.length-1,o;i>=0;i--)(o=t[i])&&(s=o(e,r,s)||s);return s&&f4(e,r,s),s};const xE=class extends je{constructor(){super(...arguments),this.isCopying=!1,this.value="",this.disabled=!1}async copyValue(){if(!(this.isCopying||this.disabled)){this.isCopying=!0;try{await navigator.clipboard.writeText(this.value),this.dispatchEvent(new CustomEvent("craft-copy",{bubbles:!0,cancelable:!1,composed:!0,detail:{value:this.value}}))}catch{this.dispatchEvent(new CustomEvent("craft-error",{cancelable:!1,composed:!0,bubbles:!0}))}finally{this.isCopying=!1}}}render(){return Y`
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
    `}};xE.styles=[s4];let pu=xE;tm([lr()],pu.prototype,"isCopying");tm([U({type:String})],pu.prototype,"value");tm([U({type:Boolean})],pu.prototype,"disabled");customElements.get("craft-copy-button")||customElements.define("craft-copy-button",pu);const p4=ue`
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
`,m4=ue`
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
`;var g4=Object.defineProperty,kn=(t,e,r,n)=>{for(var s=void 0,i=t.length-1,o;i>=0;i--)(o=t[i])&&(s=o(e,r,s)||s);return s&&g4(e,r,s),s};const Fn={"icon.in":{keyframes:[{scale:.25,opacity:.25},{scale:1,opacity:1}],options:{duration:100}},"icon.out":{keyframes:[{scale:1,opacity:1},{scale:.25,opacity:.25}],options:{duration:100}}},CE=class extends je{constructor(){super(),this.status="rest",this.value="",this.disabled=!1,this.feedbackDuration=1e3,this.tooltipLabel="Copy",this.addEventListener("craft-copy",()=>{this.showStatus("success")}),this.addEventListener("craft-error",()=>{this.showStatus("error")})}getId(){return`attribute-${this.value.replace(/([a-z])([A-Z])/g,"$1-$2").replace(/[\s_]+/g,"-").toLowerCase()}`}async showStatus(e){const r=e==="success"?this.successIconEl:this.errorIconEl;this.tooltipLabel=e==="success"?"Copied":"Copy failed",await r.animate(Fn["icon.out"].keyframes,Fn["icon.out"].options),this.copyIconEl.hidden=!0,r.hidden=!1,await r.animate(Fn["icon.in"].keyframes,Fn["icon.in"].options),this.status=e,setTimeout(async()=>{await r.animate(Fn["icon.out"].keyframes,Fn["icon.out"].options),r.hidden=!0,this.copyIconEl.hidden=!1,await this.copyIconEl.animate(Fn["icon.in"].keyframes,Fn["icon.in"].options),this.status="rest",this.tooltipLabel="Copy"},this.feedbackDuration)}render(){return Y`
      <c-tooltip for="${this.getId()}">${this.tooltipLabel}</c-tooltip>
      <craft-copy-button
        value="${this.value}"
        id="${this.getId()}"
        class=${en({"copy-attribute":!0,"copy-attribute--success":this.status==="success","copy-attribute--error":this.status==="error"})}
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
    `}};CE.styles=[m4,p4];let sn=CE;kn([lr()],sn.prototype,"status");kn([Qe('slot[name="copy-icon"]')],sn.prototype,"copyIconEl");kn([Qe('slot[name="success-icon"]')],sn.prototype,"successIconEl");kn([Qe('slot[name="error-icon"]')],sn.prototype,"errorIconEl");kn([Qe("craft-copy-button")],sn.prototype,"copyButtonEl");kn([U({type:String})],sn.prototype,"value");kn([U({type:Boolean,reflect:!0})],sn.prototype,"disabled");kn([U({attribute:"feedback-duration",type:Number})],sn.prototype,"feedbackDuration");kn([U({reflect:!1})],sn.prototype,"tooltipLabel");customElements.get("craft-copy-attribute")||customElements.define("craft-copy-attribute",sn);const y4=ue`
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
`,AE=new WeakMap;function b4(t,e){let r=e;for(;r;){if(AE.get(r)===t)return!0;r=Object.getPrototypeOf(r)}return!1}function mt(t){return e=>{if(b4(t,e))return e;const r=t(e);return AE.set(r,t),r}}const v4=t=>class extends t{static get properties(){return{disabled:{type:Boolean,reflect:!0}}}constructor(){super(),this._requestedToBeDisabled=!1,this.__isUserSettingDisabled=!0,this.__restoreDisabledTo=!1,this.disabled=!1}makeRequestToBeDisabled(){this._requestedToBeDisabled===!1&&(this._requestedToBeDisabled=!0,this.__restoreDisabledTo=this.disabled,this.__internalSetDisabled(!0))}retractRequestToBeDisabled(){this._requestedToBeDisabled===!0&&(this._requestedToBeDisabled=!1,this.__internalSetDisabled(this.__restoreDisabledTo))}__internalSetDisabled(e){this.__isUserSettingDisabled=!1,this.disabled=e,this.__isUserSettingDisabled=!0}requestUpdate(e,r,n){super.requestUpdate(e,r,n),e==="disabled"&&(this.__isUserSettingDisabled&&(this.__restoreDisabledTo=this.disabled),this.disabled===!1&&this._requestedToBeDisabled===!0&&this.__internalSetDisabled(!0))}click(){this.disabled||super.click()}},Oa=mt(v4),w4=t=>class extends Oa(t){static get properties(){return{tabIndex:{type:Number,reflect:!0,attribute:"tabindex"}}}constructor(){super(),this.__isUserSettingTabIndex=!0,this.__restoreTabIndexTo=0,this.__internalSetTabIndex(0)}makeRequestToBeDisabled(){super.makeRequestToBeDisabled(),this._requestedToBeDisabled===!1&&this.tabIndex!=null&&(this.__restoreTabIndexTo=this.tabIndex)}retractRequestToBeDisabled(){super.retractRequestToBeDisabled(),this._requestedToBeDisabled===!0&&this.__internalSetTabIndex(this.__restoreTabIndexTo)}static enabledWarnings=super.enabledWarnings?.filter(e=>e!=="change-in-update")||[];__internalSetTabIndex(e){this.__isUserSettingTabIndex=!1,this.tabIndex=e,this.__isUserSettingTabIndex=!0}requestUpdate(e,r,n){super.requestUpdate(e,r,n),e==="disabled"&&(this.disabled?this.__internalSetTabIndex(-1):this.__internalSetTabIndex(this.__restoreTabIndexTo)),e==="tabIndex"&&(this.__isUserSettingTabIndex&&this.tabIndex!=null&&(this.__restoreTabIndexTo=this.tabIndex),this.tabIndex!==-1&&this._requestedToBeDisabled===!0&&this.__internalSetTabIndex(-1))}firstUpdated(e){super.firstUpdated(e),this.disabled&&this.__internalSetTabIndex(-1)}},$E=mt(w4),Xd=t=>t.key===" "||t.key==="Enter",Gb=t=>t.key===" ";let _4=class extends $E(je){static get properties(){return{active:{type:Boolean,reflect:!0},type:{type:String,reflect:!0}}}render(){return Y` <div class="button-content"><slot></slot></div> `}static get styles(){return[ue`
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
      `]}constructor(){super(),this.type="button",this.active=!1,this.__setupEvents()}connectedCallback(){super.connectedCallback(),this.hasAttribute("role")||this.setAttribute("role","button")}updated(e){super.updated(e),e.has("disabled")&&(this.disabled?this.setAttribute("aria-disabled","true"):this.getAttribute("aria-disabled")!==null&&this.removeAttribute("aria-disabled"))}__setupEvents(){this.addEventListener("mousedown",this.__mousedownHandler),this.addEventListener("keydown",this.__keydownHandler),this.addEventListener("keyup",this.__keyupHandler)}__mousedownHandler(){this.active=!0;const e=()=>{this.active=!1,document.removeEventListener("mouseup",e),this.removeEventListener("mouseup",e)};document.addEventListener("mouseup",e),this.addEventListener("mouseup",e)}__keydownHandler(e){if(this.active||!Xd(e)){Gb(e)&&e.preventDefault();return}Gb(e)&&e.preventDefault(),this.active=!0;const r=n=>{Xd(n)&&(this.active=!1,document.removeEventListener("keyup",r,!0))};document.addEventListener("keyup",r,!0)}__keyupHandler(e){if(Xd(e)){if(e.target&&e.target!==this)return;this.click()}}},E4=class extends _4{constructor(){super(),this.type="reset",this.__setupDelegationInConstructor(),this.__submitAndResetHelperButton=document.createElement("button"),this.__preventEventLeakage=this.__preventEventLeakage.bind(this)}connectedCallback(){super.connectedCallback(),this.updateComplete.then(()=>{this._setupSubmitAndResetHelperOnConnected()})}disconnectedCallback(){super.disconnectedCallback(),this._teardownSubmitAndResetHelperOnDisconnected()}__preventEventLeakage(e){e.target===this.__submitAndResetHelperButton&&e.stopImmediatePropagation()}_setupSubmitAndResetHelperOnConnected(){this.appendChild(this.__submitAndResetHelperButton),this._form=this.__submitAndResetHelperButton.form,this.removeChild(this.__submitAndResetHelperButton),this._form&&this._form.addEventListener("click",this.__preventEventLeakage)}_teardownSubmitAndResetHelperOnDisconnected(){this._form&&this._form.removeEventListener("click",this.__preventEventLeakage)}async __clickDelegationHandler(e){this._form||await this.updateComplete,(this.type==="submit"||this.type==="reset")&&e.target===this&&this._form&&(this.__submitAndResetHelperButton.type=this.type,this._form.appendChild(this.__submitAndResetHelperButton),this.__submitAndResetHelperButton.click(),this._form.removeChild(this.__submitAndResetHelperButton))}__setupDelegationInConstructor(){this.addEventListener("click",this.__clickDelegationHandler,!0)}};const Mn=new WeakMap;function S4(){const t=document.createElement("button");return t.tabIndex=-1,t.type="submit",t.setAttribute("aria-hidden","true"),t.style.cssText=`
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
  `,t}let x4=class extends E4{get _nativeButtonNode(){return Mn.get(this._form)?.helper||null}constructor(){super(),this.type="submit",this.__implicitSubmitHelperButton=null}_setupSubmitAndResetHelperOnConnected(){if(super._setupSubmitAndResetHelperOnConnected(),!this._form||this.type!=="submit")return;const e=this._form;if(!Mn.get(this._form)){const r=S4(),n=document.createElement("div");n.appendChild(r),Mn.set(this._form,{lionButtons:new Set,helper:r,observer:new MutationObserver(()=>{e.appendChild(n)})}),e.appendChild(n),Mn.get(e)?.observer.observe(n,{childList:!0})}Mn.get(e)?.lionButtons.add(this)}_teardownSubmitAndResetHelperOnDisconnected(){if(super._teardownSubmitAndResetHelperOnDisconnected(),this._form){const e=Mn.get(this._form);e&&(e.lionButtons.delete(this),e.lionButtons.size||(this._form.contains(e.helper)&&e.helper.remove(),Mn.get(this._form)?.observer.disconnect(),Mn.delete(this._form)))}}};var C4=Object.defineProperty,mu=(t,e,r,n)=>{for(var s=void 0,i=t.length-1,o;i>=0;i--)(o=t[i])&&(s=o(e,r,s)||s);return s&&C4(e,r,s),s};let Ra=class extends x4{constructor(){super(...arguments),this.appearance="accent",this.variant="default",this.size="medium",this.loading=!1}static get styles(){return[...super.styles,y4]}render(){return Y`
      <div class="button-content" part="content">
        <slot name="prefix" class="prefix" part="prefix"></slot>
        <slot class="label" part="label"></slot>
        <slot name="suffix" class="suffix" part="suffix"></slot>
      </div>
      ${this.loading?Y`<craft-spinner part="spinner"></craft-spinner>`:Ce}
    `}};mu([U({reflect:!0})],Ra.prototype,"appearance");mu([U({reflect:!0})],Ra.prototype,"variant");mu([U({reflect:!0})],Ra.prototype,"size");mu([U({reflect:!0,type:Boolean})],Ra.prototype,"loading");customElements.get("craft-button")||customElements.define("craft-button",Ra);const A4=ue`
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
`;var $4=Object.defineProperty,TE=(t,e,r,n)=>{for(var s=void 0,i=t.length-1,o;i>=0;i--)(o=t[i])&&(s=o(e,r,s)||s);return s&&$4(e,r,s),s};const kE=class extends je{constructor(){super(...arguments),this.label=null,this._gradientId=null}connectedCallback(){super.connectedCallback(),this._gradientId=`avatar-gradient-${Math.random().toString(36).slice(2,8)}`}text(){return this.label?this.label.split(" ").map(e=>e.charAt(0).toUpperCase()).join(""):"?"}render(){return Y`
      <span class="avatar">
        <svg
          viewBox="0 0 100 100"
          xmlns="http://www.w3.org/2000/svg"
          role="img"
        >
          ${this.label?Y`<title>${this.label}</title>`:""}
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
    `}};kE.styles=[A4];let rm=kE;TE([U()],rm.prototype,"label");TE([lr()],rm.prototype,"_gradientId");customElements.get("craft-avatar")||customElements.define("craft-avatar",rm);const PE=ue`
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
`,OE=ue`
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
`,nm=ue`
  ${OE}

  ::slotted([slot='input']) {
    ${PE}
  }
`,T4=ue`
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
`,k4=t=>t===null||typeof t!="object"&&typeof t!="function",RE=(t,e)=>t?._$litType$!==void 0,P4=t=>t.strings===void 0;function NE(t=""){return`${t.length>0?`${t}-`:""}${Math.random().toString(36).substr(2,10)}`}function O4(t){return t instanceof Node?"node":RE(t)?"template-result":!Array.isArray(t)&&typeof t=="object"&&"template"in t?"slot-rerender-object":null}const R4=t=>class extends t{get slots(){return{}}constructor(){super(),this.__renderMetaPerSlot=new Map,this.__slotsThatNeedRerender=new Set,this.__slotsProvidedByUserOnFirstConnected=new Set,this.__privateSlots=new Set}connectedCallback(){super.connectedCallback(),this._connectSlotMixin()}__rerenderSlot(e){const r=this.slots[e]();this.__renderTemplateInScopedContext({renderAsDirectHostChild:r.renderAsDirectHostChild,template:r.template,slotName:e}),r.afterRender?.()}update(e){super.update(e);for(const r of this.__slotsThatNeedRerender)this.__rerenderSlot(r)}__renderTemplateInScopedContext({template:e,slotName:r,renderAsDirectHostChild:n}){if(!this.__renderMetaPerSlot.has(r)){const u=!!ShadowRoot.prototype.createElement;this.shadowRoot||console.error("[SlotMixin] No shadowRoot was found");const c=(u?this.shadowRoot:document).createElement("div"),d=document.createComment(`_start_slot_${r}_`),p=document.createComment(`_end_slot_${r}_`);c.appendChild(d),c.appendChild(p);const{creationScope:f,host:h}=this.renderOptions;if(sf(e,c,{renderBefore:p,creationScope:f,host:h}),n){const m=Array.from(c.childNodes);this.__appendNodes({nodes:m,renderParent:this,slotName:r})}else c.slot=r,this.appendChild(c);this.__renderMetaPerSlot.set(r,{renderTargetThatRespectsShadowRootScoping:c,renderBefore:p});return}const{renderBefore:s,renderTargetThatRespectsShadowRootScoping:i}=this.__renderMetaPerSlot.get(r),o=n?this:i,{creationScope:a,host:l}=this.renderOptions;sf(e,o,{creationScope:a,host:l,renderBefore:s}),n&&s.previousElementSibling&&!s.previousElementSibling.slot&&(s.previousElementSibling.slot=r)}__appendNodes({nodes:e,renderParent:r=this,slotName:n}){for(const s of e)s instanceof Element&&n&&n!==""&&s.setAttribute("slot",n),r.appendChild(s)}__initSlots(e){for(const r of e){if(this.__slotsProvidedByUserOnFirstConnected.has(r))continue;const n=this.slots[r]();if(n!==void 0)switch(this.__isConnectedSlotMixin||this.__privateSlots.add(r),O4(n)){case"template-result":this.__renderTemplateInScopedContext({template:n,renderAsDirectHostChild:!0,slotName:r});break;case"node":this.__appendNodes({nodes:[n],renderParent:this,slotName:r});break;case"slot-rerender-object":this.__slotsThatNeedRerender.add(r),n.firstRenderOnConnected&&this.__rerenderSlot(r);break;default:throw new Error(`Slot "${r}" configured inside "get slots()" (in prototype) of ${this.constructor.name} may return these types: TemplateResult | Node | {template:TemplateResult, afterRender?:function} | undefined.
              You provided: ${n}`)}}}_connectSlotMixin(){if(this.__isConnectedSlotMixin)return;const e=Object.keys(this.slots);for(const r of e)(r===""?Array.from(this.children).find(n=>!n.hasAttribute("slot")):Array.from(this.children).find(n=>n.slot===r))&&this.__slotsProvidedByUserOnFirstConnected.add(r);this.__initSlots(e),this.__isConnectedSlotMixin=!0}_isPrivateSlot(e){return this.__privateSlots.has(e)}},Na=mt(R4);function IE(t,e){return e={exports:{}},t(e,e.exports),e.exports}var ms="long",Dn="short",Yd="narrow",ke="numeric",Vn="2-digit",Bn={number:{decimal:{style:"decimal"},integer:{style:"decimal",maximumFractionDigits:0},currency:{style:"currency",currency:"USD"},percent:{style:"percent"},default:{style:"decimal"}},date:{short:{month:ke,day:ke,year:Vn},medium:{month:Dn,day:ke,year:ke},long:{month:ms,day:ke,year:ke},full:{month:ms,day:ke,year:ke,weekday:ms},default:{month:Dn,day:ke,year:ke}},time:{short:{hour:ke,minute:ke},medium:{hour:ke,minute:ke,second:ke},long:{hour:ke,minute:ke,second:ke,timeZoneName:Dn},full:{hour:ke,minute:ke,second:ke,timeZoneName:Dn},default:{hour:ke,minute:ke,second:ke}},duration:{default:{hours:{minimumIntegerDigits:1,maximumFractionDigits:0},minutes:{minimumIntegerDigits:2,maximumFractionDigits:0},seconds:{minimumIntegerDigits:2,maximumFractionDigits:3}}},parseNumberPattern:function(t){if(t){var e={},r=t.match(/\b[A-Z]{3}\b/i),n=t.replace(/[^¤]/g,"").length;if(!n&&r&&(n=1),n?(e.style="currency",e.currencyDisplay=n===1?"symbol":n===2?"code":"name",e.currency=r?r[0].toUpperCase():"USD"):t.indexOf("%")>=0&&(e.style="percent"),!/[@#0]/.test(t))return e.style?e:void 0;if(e.useGrouping=t.indexOf(",")>=0,/E\+?[@#0]+/i.test(t)||t.indexOf("@")>=0){var s=t.replace(/E\+?[@#0]+|[^@#0]/gi,"");e.minimumSignificantDigits=Math.min(Math.max(s.replace(/[^@0]/g,"").length,1),21),e.maximumSignificantDigits=Math.min(Math.max(s.length,1),21)}else{for(var i=t.replace(/[^#0.]/g,"").split("."),o=i[0],a=o.length-1;o[a]==="0";)--a;e.minimumIntegerDigits=Math.min(Math.max(o.length-1-a,1),21);var l=i[1]||"";for(a=0;l[a]==="0";)++a;for(e.minimumFractionDigits=Math.min(Math.max(a,0),20);l[a]==="#";)++a;e.maximumFractionDigits=Math.min(Math.max(a,0),20)}return e}},parseDatePattern:function(t){if(t){for(var e={},r=0;r<t.length;){for(var n=t[r],s=1;t[++r]===n;)++s;switch(n){case"G":e.era=s===5?Yd:s===4?ms:Dn;break;case"y":case"Y":e.year=s===2?Vn:ke;break;case"M":case"L":s=Math.min(Math.max(s-1,0),4),e.month=[ke,Vn,Dn,ms,Yd][s];break;case"E":case"e":case"c":e.weekday=s===5?Yd:s===4?ms:Dn;break;case"d":case"D":e.day=s===2?Vn:ke;break;case"h":case"K":e.hour12=!0,e.hour=s===2?Vn:ke;break;case"H":case"k":e.hour12=!1,e.hour=s===2?Vn:ke;break;case"m":e.minute=s===2?Vn:ke;break;case"s":case"S":e.second=s===2?Vn:ke;break;case"z":case"Z":case"v":case"V":e.timeZoneName=s===1?Dn:ms;break}}return Object.keys(e).length?e:void 0}}},N4=function(t,e){if(typeof t=="string"&&e[t])return t;for(var r=[].concat(t||[]),n=0,s=r.length;n<s;++n)for(var i=r[n].split("-");i.length;){var o=i.join("-");if(e[o])return o;i.pop()}},oi="zero",le="one",ct="two",Fe="few",Ze="many",ie="other",x=[function(t){var e=+t;return e===1?le:ie},function(t){var e=+t;return 0<=e&&e<=1?le:ie},function(t){var e=Math.floor(Math.abs(+t)),r=+t;return e===0||r===1?le:ie},function(t){var e=+t;return e===0?oi:e===1?le:e===2?ct:3<=e%100&&e%100<=10?Fe:11<=e%100&&e%100<=99?Ze:ie},function(t){var e=Math.floor(Math.abs(+t)),r=(t+".").split(".")[1].length;return e===1&&r===0?le:ie},function(t){var e=+t;return e%10===1&&e%100!==11?le:2<=e%10&&e%10<=4&&(e%100<12||14<e%100)?Fe:e%10===0||5<=e%10&&e%10<=9||11<=e%100&&e%100<=14?Ze:ie},function(t){var e=+t;return e%10===1&&e%100!==11&&e%100!==71&&e%100!==91?le:e%10===2&&e%100!==12&&e%100!==72&&e%100!==92?ct:(3<=e%10&&e%10<=4||e%10===9)&&(e%100<10||19<e%100)&&(e%100<70||79<e%100)&&(e%100<90||99<e%100)?Fe:e!==0&&e%1e6===0?Ze:ie},function(t){var e=Math.floor(Math.abs(+t)),r=(t+".").split(".")[1].length,n=+(t+".").split(".")[1];return r===0&&e%10===1&&e%100!==11||n%10===1&&n%100!==11?le:r===0&&2<=e%10&&e%10<=4&&(e%100<12||14<e%100)||2<=n%10&&n%10<=4&&(n%100<12||14<n%100)?Fe:ie},function(t){var e=Math.floor(Math.abs(+t)),r=(t+".").split(".")[1].length;return e===1&&r===0?le:2<=e&&e<=4&&r===0?Fe:r!==0?Ze:ie},function(t){var e=+t;return e===0?oi:e===1?le:e===2?ct:e===3?Fe:e===6?Ze:ie},function(t){var e=Math.floor(Math.abs(+t)),r=+(""+t).replace(/^[^.]*.?|0+$/g,""),n=+t;return n===1||r!==0&&(e===0||e===1)?le:ie},function(t){var e=Math.floor(Math.abs(+t)),r=(t+".").split(".")[1].length,n=+(t+".").split(".")[1];return r===0&&e%100===1||n%100===1?le:r===0&&e%100===2||n%100===2?ct:r===0&&3<=e%100&&e%100<=4||3<=n%100&&n%100<=4?Fe:ie},function(t){var e=Math.floor(Math.abs(+t));return e===0||e===1?le:ie},function(t){var e=Math.floor(Math.abs(+t)),r=(t+".").split(".")[1].length,n=+(t+".").split(".")[1];return r===0&&(e===1||e===2||e===3)||r===0&&e%10!==4&&e%10!==6&&e%10!==9||r!==0&&n%10!==4&&n%10!==6&&n%10!==9?le:ie},function(t){var e=+t;return e===1?le:e===2?ct:3<=e&&e<=6?Fe:7<=e&&e<=10?Ze:ie},function(t){var e=+t;return e===1||e===11?le:e===2||e===12?ct:3<=e&&e<=10||13<=e&&e<=19?Fe:ie},function(t){var e=Math.floor(Math.abs(+t)),r=(t+".").split(".")[1].length;return r===0&&e%10===1?le:r===0&&e%10===2?ct:r===0&&(e%100===0||e%100===20||e%100===40||e%100===60||e%100===80)?Fe:r!==0?Ze:ie},function(t){var e=Math.floor(Math.abs(+t)),r=(t+".").split(".")[1].length,n=+t;return e===1&&r===0?le:e===2&&r===0?ct:r===0&&(n<0||10<n)&&n%10===0?Ze:ie},function(t){var e=Math.floor(Math.abs(+t)),r=+(""+t).replace(/^[^.]*.?|0+$/g,"");return r===0&&e%10===1&&e%100!==11||r!==0?le:ie},function(t){var e=+t;return e===1?le:e===2?ct:ie},function(t){var e=+t;return e===0?oi:e===1?le:ie},function(t){var e=Math.floor(Math.abs(+t)),r=+t;return r===0?oi:(e===0||e===1)&&r!==0?le:ie},function(t){var e=+(t+".").split(".")[1],r=+t;return r%10===1&&(r%100<11||19<r%100)?le:2<=r%10&&r%10<=9&&(r%100<11||19<r%100)?Fe:e!==0?Ze:ie},function(t){var e=(t+".").split(".")[1].length,r=+(t+".").split(".")[1],n=+t;return n%10===0||11<=n%100&&n%100<=19||e===2&&11<=r%100&&r%100<=19?oi:n%10===1&&n%100!==11||e===2&&r%10===1&&r%100!==11||e!==2&&r%10===1?le:ie},function(t){var e=Math.floor(Math.abs(+t)),r=(t+".").split(".")[1].length,n=+(t+".").split(".")[1];return r===0&&e%10===1&&e%100!==11||n%10===1&&n%100!==11?le:ie},function(t){var e=Math.floor(Math.abs(+t)),r=(t+".").split(".")[1].length,n=+t;return e===1&&r===0?le:r!==0||n===0||n!==1&&1<=n%100&&n%100<=19?Fe:ie},function(t){var e=+t;return e===1?le:e===0||2<=e%100&&e%100<=10?Fe:11<=e%100&&e%100<=19?Ze:ie},function(t){var e=Math.floor(Math.abs(+t)),r=(t+".").split(".")[1].length;return e===1&&r===0?le:r===0&&2<=e%10&&e%10<=4&&(e%100<12||14<e%100)?Fe:r===0&&e!==1&&0<=e%10&&e%10<=1||r===0&&5<=e%10&&e%10<=9||r===0&&12<=e%100&&e%100<=14?Ze:ie},function(t){var e=Math.floor(Math.abs(+t));return 0<=e&&e<=1?le:ie},function(t){var e=Math.floor(Math.abs(+t)),r=(t+".").split(".")[1].length;return r===0&&e%10===1&&e%100!==11?le:r===0&&2<=e%10&&e%10<=4&&(e%100<12||14<e%100)?Fe:r===0&&e%10===0||r===0&&5<=e%10&&e%10<=9||r===0&&11<=e%100&&e%100<=14?Ze:ie},function(t){var e=Math.floor(Math.abs(+t)),r=+t;return e===0||r===1?le:2<=r&&r<=10?Fe:ie},function(t){var e=Math.floor(Math.abs(+t)),r=+(t+".").split(".")[1],n=+t;return n===0||n===1||e===0&&r===1?le:ie},function(t){var e=Math.floor(Math.abs(+t)),r=(t+".").split(".")[1].length;return r===0&&e%100===1?le:r===0&&e%100===2?ct:r===0&&3<=e%100&&e%100<=4||r!==0?Fe:ie},function(t){var e=+t;return 0<=e&&e<=1||11<=e&&e<=99?le:ie},function(t){var e=+t;return e===1||e===5||e===7||e===8||e===9||e===10?le:e===2||e===3?ct:e===4?Fe:e===6?Ze:ie},function(t){var e=Math.floor(Math.abs(+t));return e%10===1||e%10===2||e%10===5||e%10===7||e%10===8||e%100===20||e%100===50||e%100===70||e%100===80?le:e%10===3||e%10===4||e%1e3===100||e%1e3===200||e%1e3===300||e%1e3===400||e%1e3===500||e%1e3===600||e%1e3===700||e%1e3===800||e%1e3===900?Fe:e===0||e%10===6||e%100===40||e%100===60||e%100===90?Ze:ie},function(t){var e=+t;return(e%10===2||e%10===3)&&e%100!==12&&e%100!==13?Fe:ie},function(t){var e=+t;return e===1||e===3?le:e===2?ct:e===4?Fe:ie},function(t){var e=+t;return e===0||e===7||e===8||e===9?oi:e===1?le:e===2?ct:e===3||e===4?Fe:e===5||e===6?Ze:ie},function(t){var e=+t;return e%10===1&&e%100!==11?le:e%10===2&&e%100!==12?ct:e%10===3&&e%100!==13?Fe:ie},function(t){var e=+t;return e===1||e===11?le:e===2||e===12?ct:e===3||e===13?Fe:ie},function(t){var e=+t;return e===1?le:e===2||e===3?ct:e===4?Fe:e===6?Ze:ie},function(t){var e=+t;return e===1||e===5?le:ie},function(t){var e=+t;return e===11||e===8||e===80||e===800?Ze:ie},function(t){var e=Math.floor(Math.abs(+t));return e===1?le:e===0||2<=e%100&&e%100<=20||e%100===40||e%100===60||e%100===80?Ze:ie},function(t){var e=+t;return e%10===6||e%10===9||e%10===0&&e!==0?Ze:ie},function(t){var e=Math.floor(Math.abs(+t));return e%10===1&&e%100!==11?le:e%10===2&&e%100!==12?ct:(e%10===7||e%10===8)&&e%100!==17&&e%100!==18?Ze:ie},function(t){var e=+t;return e===1?le:e===2||e===3?ct:e===4?Fe:ie},function(t){var e=+t;return 1<=e&&e<=4?le:ie},function(t){var e=+t;return e===1||e===5||7<=e&&e<=9?le:e===2||e===3?ct:e===4?Fe:e===6?Ze:ie},function(t){var e=+t;return e===1?le:e%10===4&&e%100!==14?Ze:ie},function(t){var e=+t;return(e%10===1||e%10===2)&&e%100!==11&&e%100!==12?le:ie},function(t){var e=+t;return e%10===6||e%10===9||e===10?Fe:ie},function(t){var e=+t;return e%10===3&&e%100!==13?Fe:ie}],uf={af:{cardinal:x[0]},ak:{cardinal:x[1]},am:{cardinal:x[2]},ar:{cardinal:x[3]},ars:{cardinal:x[3]},as:{cardinal:x[2],ordinal:x[34]},asa:{cardinal:x[0]},ast:{cardinal:x[4]},az:{cardinal:x[0],ordinal:x[35]},be:{cardinal:x[5],ordinal:x[36]},bem:{cardinal:x[0]},bez:{cardinal:x[0]},bg:{cardinal:x[0]},bh:{cardinal:x[1]},bn:{cardinal:x[2],ordinal:x[34]},br:{cardinal:x[6]},brx:{cardinal:x[0]},bs:{cardinal:x[7]},ca:{cardinal:x[4],ordinal:x[37]},ce:{cardinal:x[0]},cgg:{cardinal:x[0]},chr:{cardinal:x[0]},ckb:{cardinal:x[0]},cs:{cardinal:x[8]},cy:{cardinal:x[9],ordinal:x[38]},da:{cardinal:x[10]},de:{cardinal:x[4]},dsb:{cardinal:x[11]},dv:{cardinal:x[0]},ee:{cardinal:x[0]},el:{cardinal:x[0]},en:{cardinal:x[4],ordinal:x[39]},eo:{cardinal:x[0]},es:{cardinal:x[0]},et:{cardinal:x[4]},eu:{cardinal:x[0]},fa:{cardinal:x[2]},ff:{cardinal:x[12]},fi:{cardinal:x[4]},fil:{cardinal:x[13],ordinal:x[0]},fo:{cardinal:x[0]},fr:{cardinal:x[12],ordinal:x[0]},fur:{cardinal:x[0]},fy:{cardinal:x[4]},ga:{cardinal:x[14],ordinal:x[0]},gd:{cardinal:x[15],ordinal:x[40]},gl:{cardinal:x[4]},gsw:{cardinal:x[0]},gu:{cardinal:x[2],ordinal:x[41]},guw:{cardinal:x[1]},gv:{cardinal:x[16]},ha:{cardinal:x[0]},haw:{cardinal:x[0]},he:{cardinal:x[17]},hi:{cardinal:x[2],ordinal:x[41]},hr:{cardinal:x[7]},hsb:{cardinal:x[11]},hu:{cardinal:x[0],ordinal:x[42]},hy:{cardinal:x[12],ordinal:x[0]},ia:{cardinal:x[4]},io:{cardinal:x[4]},is:{cardinal:x[18]},it:{cardinal:x[4],ordinal:x[43]},iu:{cardinal:x[19]},iw:{cardinal:x[17]},jgo:{cardinal:x[0]},ji:{cardinal:x[4]},jmc:{cardinal:x[0]},ka:{cardinal:x[0],ordinal:x[44]},kab:{cardinal:x[12]},kaj:{cardinal:x[0]},kcg:{cardinal:x[0]},kk:{cardinal:x[0],ordinal:x[45]},kkj:{cardinal:x[0]},kl:{cardinal:x[0]},kn:{cardinal:x[2]},ks:{cardinal:x[0]},ksb:{cardinal:x[0]},ksh:{cardinal:x[20]},ku:{cardinal:x[0]},kw:{cardinal:x[19]},ky:{cardinal:x[0]},lag:{cardinal:x[21]},lb:{cardinal:x[0]},lg:{cardinal:x[0]},ln:{cardinal:x[1]},lt:{cardinal:x[22]},lv:{cardinal:x[23]},mas:{cardinal:x[0]},mg:{cardinal:x[1]},mgo:{cardinal:x[0]},mk:{cardinal:x[24],ordinal:x[46]},ml:{cardinal:x[0]},mn:{cardinal:x[0]},mo:{cardinal:x[25],ordinal:x[0]},mr:{cardinal:x[2],ordinal:x[47]},mt:{cardinal:x[26]},nah:{cardinal:x[0]},naq:{cardinal:x[19]},nb:{cardinal:x[0]},nd:{cardinal:x[0]},ne:{cardinal:x[0],ordinal:x[48]},nl:{cardinal:x[4]},nn:{cardinal:x[0]},nnh:{cardinal:x[0]},no:{cardinal:x[0]},nr:{cardinal:x[0]},nso:{cardinal:x[1]},ny:{cardinal:x[0]},nyn:{cardinal:x[0]},om:{cardinal:x[0]},or:{cardinal:x[0],ordinal:x[49]},os:{cardinal:x[0]},pa:{cardinal:x[1]},pap:{cardinal:x[0]},pl:{cardinal:x[27]},prg:{cardinal:x[23]},ps:{cardinal:x[0]},pt:{cardinal:x[28]},"pt-PT":{cardinal:x[4]},rm:{cardinal:x[0]},ro:{cardinal:x[25],ordinal:x[0]},rof:{cardinal:x[0]},ru:{cardinal:x[29]},rwk:{cardinal:x[0]},saq:{cardinal:x[0]},sc:{cardinal:x[4],ordinal:x[43]},scn:{cardinal:x[4],ordinal:x[43]},sd:{cardinal:x[0]},sdh:{cardinal:x[0]},se:{cardinal:x[19]},seh:{cardinal:x[0]},sh:{cardinal:x[7]},shi:{cardinal:x[30]},si:{cardinal:x[31]},sk:{cardinal:x[8]},sl:{cardinal:x[32]},sma:{cardinal:x[19]},smi:{cardinal:x[19]},smj:{cardinal:x[19]},smn:{cardinal:x[19]},sms:{cardinal:x[19]},sn:{cardinal:x[0]},so:{cardinal:x[0]},sq:{cardinal:x[0],ordinal:x[50]},sr:{cardinal:x[7]},ss:{cardinal:x[0]},ssy:{cardinal:x[0]},st:{cardinal:x[0]},sv:{cardinal:x[4],ordinal:x[51]},sw:{cardinal:x[4]},syr:{cardinal:x[0]},ta:{cardinal:x[0]},te:{cardinal:x[0]},teo:{cardinal:x[0]},ti:{cardinal:x[1]},tig:{cardinal:x[0]},tk:{cardinal:x[0],ordinal:x[52]},tl:{cardinal:x[13],ordinal:x[0]},tn:{cardinal:x[0]},tr:{cardinal:x[0]},ts:{cardinal:x[0]},tzm:{cardinal:x[33]},ug:{cardinal:x[0]},uk:{cardinal:x[29],ordinal:x[53]},ur:{cardinal:x[4]},uz:{cardinal:x[0]},ve:{cardinal:x[0]},vo:{cardinal:x[0]},vun:{cardinal:x[0]},wa:{cardinal:x[1]},wae:{cardinal:x[0]},xh:{cardinal:x[0]},xog:{cardinal:x[0]},yi:{cardinal:x[4]},zu:{cardinal:x[2]},lo:{ordinal:x[0]},ms:{ordinal:x[0]},vi:{ordinal:x[0]}},gu=IE(function(t,e){e=t.exports=function(p,f,h){return r(p,null,f||"en",h||{},!0)},e.toParts=function(p,f,h){return r(p,null,f||"en",h||{},!1)};function r(p,f,h,m,g){var v=p.map(function(b){return n(b,f,h,m,g)});return g?v.length===1?v[0]:function(b){for(var w="",_=0;_<v.length;++_)w+=v[_](b);return w}:function(b){return v.reduce(function(w,_){return w.concat(_(b))},[])}}function n(p,f,h,m,g){if(typeof p=="string"){var v=p;return function(){return v}}var b=p[0],w=p[1];if(f&&p[0]==="#"){b=f[0];var _=f[2],S=(m.number||d.number)([b,"number"],h);return function(C){return S(s(b,C)-_,C)}}var k;w==="plural"||w==="selectordinal"?(k={},Object.keys(p[3]).forEach(function(C){k[C]=r(p[3][C],p,h,m,g)}),p=[p[0],p[1],p[2],k]):p[2]&&typeof p[2]=="object"&&(k={},Object.keys(p[2]).forEach(function(C){k[C]=r(p[2][C],p,h,m,g)}),p=[p[0],p[1],k]);var O=w&&(m[w]||d[w]);if(O){var R=O(p,h);return function(C){return R(s(b,C),C)}}return g?function(C){return String(s(b,C))}:function(C){return s(b,C)}}function s(p,f){if(f&&p in f)return f[p];for(var h=p.split("."),m=f,g=0,v=h.length;m&&g<v;++g)m=m[h[g]];return m}function i(p,f){var h=p[2],m=Bn.number[h]||Bn.parseNumberPattern(h)||Bn.number.default;return new Intl.NumberFormat(f,m).format}function o(p,f){var h=p[2],m=Bn.duration[h]||Bn.duration.default,g=new Intl.NumberFormat(f,m.seconds).format,v=new Intl.NumberFormat(f,m.minutes).format,b=new Intl.NumberFormat(f,m.hours).format,w=/^fi$|^fi-|^da/.test(String(f))?".":":";return function(_,S){if(_=+_,!isFinite(_))return g(_);var k=~~(_/60/60),O=~~(_/60%60),R=(k?b(Math.abs(k))+w:"")+v(Math.abs(O))+w+g(Math.abs(_%60));return _<0?b(-1).replace(b(1),R):R}}function a(p,f){var h=p[1],m=p[2],g=Bn[h][m]||Bn.parseDatePattern(m)||Bn[h].default;return new Intl.DateTimeFormat(f,g).format}function l(p,f){var h=p[1],m=h==="selectordinal"?"ordinal":"cardinal",g=p[2],v=p[3],b;if(Intl.PluralRules&&Intl.PluralRules.supportedLocalesOf(f).length>0)b=new Intl.PluralRules(f,{type:m});else{var w=N4(f,uf),_=w&&uf[w][m]||u;b={select:_}}return function(S,k){var O=v["="+ +S]||v[b.select(S-g)]||v.other;return O(k)}}function u(){return"other"}function c(p,f){var h=p[2];return function(m,g){var v=h[m]||h.other;return v(g)}}var d={number:i,ordinal:i,spellout:i,duration:o,date:a,time:a,plural:l,selectordinal:l,select:c};e.types=d});gu.toParts;gu.types;var LE=IE(function(t,e){var r="{",n="}",s=",",i="#",o="<",a=">",l="</",u="/>",c="'",d="offset:",p=["number","date","time","ordinal","duration","spellout"],f=["plural","select","selectordinal"];e=t.exports=function(y,I){return h({pattern:String(y),index:0,tagsType:I&&I.tagsType||null,tokens:I&&I.tokens||null},"")};function h(y,I){var q=y.pattern,D=q.length,z=[],P=y.index,ee=m(y,I);for(ee&&z.push(ee),ee&&y.tokens&&y.tokens.push(["text",q.slice(P,y.index)]);y.index<D;){if(q[y.index]===n){if(!I)throw A(y);break}if(I&&y.tagsType&&q.slice(y.index,y.index+l.length)===l)break;z.push(b(y)),P=y.index,ee=m(y,I),ee&&z.push(ee),ee&&y.tokens&&y.tokens.push(["text",q.slice(P,y.index)])}return z}function m(y,I){for(var q=y.pattern,D=q.length,z=I==="plural"||I==="selectordinal",P=!!y.tagsType,ee=I==="{style}",ye="";y.index<D;){var re=q[y.index];if(re===r||re===n||z&&re===i||P&&re===o||ee&&g(re.charCodeAt(0)))break;if(re===c)if(re=q[++y.index],re===c)ye+=re,++y.index;else if(re===r||re===n||z&&re===i||P&&re===o||ee)for(ye+=re;++y.index<D;)if(re=q[y.index],re===c&&q[y.index+1]===c)ye+=c,++y.index;else if(re===c){++y.index;break}else ye+=re;else ye+=c;else ye+=re,++y.index}return ye}function g(y){return y>=9&&y<=13||y===32||y===133||y===160||y===6158||y>=8192&&y<=8205||y===8232||y===8233||y===8239||y===8287||y===8288||y===12288||y===65279}function v(y){for(var I=y.pattern,q=I.length,D=y.index;y.index<q&&g(I.charCodeAt(y.index));)++y.index;D<y.index&&y.tokens&&y.tokens.push(["space",y.pattern.slice(D,y.index)])}function b(y){var I=y.pattern;if(I[y.index]===i)return y.tokens&&y.tokens.push(["syntax",i]),++y.index,[i];var q=w(y);if(q)return q;if(I[y.index]!==r)throw A(y,r);y.tokens&&y.tokens.push(["syntax",r]),++y.index,v(y);var D=_(y);if(!D)throw A(y,"placeholder id");y.tokens&&y.tokens.push(["id",D]),v(y);var z=I[y.index];if(z===n)return y.tokens&&y.tokens.push(["syntax",n]),++y.index,[D];if(z!==s)throw A(y,s+" or "+n);y.tokens&&y.tokens.push(["syntax",s]),++y.index,v(y);var P=_(y);if(!P)throw A(y,"placeholder type");if(y.tokens&&y.tokens.push(["type",P]),v(y),z=I[y.index],z===n){if(y.tokens&&y.tokens.push(["syntax",n]),P==="plural"||P==="selectordinal"||P==="select")throw A(y,P+" sub-messages");return++y.index,[D,P]}if(z!==s)throw A(y,s+" or "+n);y.tokens&&y.tokens.push(["syntax",s]),++y.index,v(y);var ee;if(P==="plural"||P==="selectordinal"){var ye=k(y);v(y),ee=[D,P,ye,R(y,P)]}else if(P==="select")ee=[D,P,R(y,P)];else if(p.indexOf(P)>=0)ee=[D,P,S(y)];else{var re=y.index,_e=S(y);v(y),I[y.index]===r&&(y.index=re,_e=R(y,P)),ee=[D,P,_e]}if(v(y),I[y.index]!==n)throw A(y,n);return y.tokens&&y.tokens.push(["syntax",n]),++y.index,ee}function w(y){var I=y.tagsType;if(!(!I||y.pattern[y.index]!==o)){if(y.pattern.slice(y.index,y.index+l.length)===l)throw A(y,null,"closing tag without matching opening tag");y.tokens&&y.tokens.push(["syntax",o]),++y.index;var q=_(y,!0);if(!q)throw A(y,"placeholder id");if(y.tokens&&y.tokens.push(["id",q]),v(y),y.pattern.slice(y.index,y.index+u.length)===u)return y.tokens&&y.tokens.push(["syntax",u]),y.index+=u.length,[q,I];if(y.pattern[y.index]!==a)throw A(y,a);y.tokens&&y.tokens.push(["syntax",a]),++y.index;var D=h(y,I),z=y.index;if(y.pattern.slice(y.index,y.index+l.length)!==l)throw A(y,l+q+a);y.tokens&&y.tokens.push(["syntax",l]),y.index+=l.length;var P=_(y,!0);if(P&&y.tokens&&y.tokens.push(["id",P]),q!==P)throw y.index=z,A(y,l+q+a,l+P+a);if(v(y),y.pattern[y.index]!==a)throw A(y,a);return y.tokens&&y.tokens.push(["syntax",a]),++y.index,[q,I,{children:D}]}}function _(y,I){for(var q=y.pattern,D=q.length,z="";y.index<D;){var P=q[y.index];if(P===r||P===n||P===s||P===i||P===c||g(P.charCodeAt(0))||I&&(P===o||P===a||P==="/"))break;z+=P,++y.index}return z}function S(y){var I=y.index,q=m(y,"{style}");if(!q)throw A(y,"placeholder style name");return y.tokens&&y.tokens.push(["style",y.pattern.slice(I,y.index)]),q}function k(y){var I=y.pattern,q=I.length,D=0;if(I.slice(y.index,y.index+d.length)===d){y.tokens&&y.tokens.push(["offset","offset"],["syntax",":"]),y.index+=d.length,v(y);for(var z=y.index;y.index<q&&O(I.charCodeAt(y.index));)++y.index;if(z===y.index)throw A(y,"offset number");y.tokens&&y.tokens.push(["number",I.slice(z,y.index)]),D=+I.slice(z,y.index)}return D}function O(y){return y>=48&&y<=57}function R(y,I){for(var q=y.pattern,D=q.length,z={};y.index<D&&q[y.index]!==n;){var P=_(y);if(!P)throw A(y,"sub-message selector");y.tokens&&y.tokens.push(["selector",P]),v(y),z[P]=C(y,I),v(y)}if(!z.other&&f.indexOf(I)>=0)throw A(y,null,null,'"other" sub-message must be specified in '+I);return z}function C(y,I){if(y.pattern[y.index]!==r)throw A(y,r+" to start sub-message");y.tokens&&y.tokens.push(["syntax",r]),++y.index;var q=h(y,I);if(y.pattern[y.index]!==n)throw A(y,n+" to end sub-message");return y.tokens&&y.tokens.push(["syntax",n]),++y.index,q}function A(y,I,q,D){var z=y.pattern,P=z.slice(0,y.index).split(/\r?\n/),ee=y.index,ye=P.length,re=P.slice(-1)[0].length;return q=q||(y.index>=z.length?"end of message pattern":_(y)||z[y.index]),D||(D=j(I,q)),D+=" in "+z.replace(/\r?\n/g,`
`),new $(D,I,q,ee,ye,re)}function j(y,I){return y?"Expected "+y+" but found "+I:"Unexpected "+I+" found"}function $(y,I,q,D,z,P){Error.call(this,y),this.name="SyntaxError",this.message=y,this.expected=I,this.found=q,this.offset=D,this.line=z,this.column=P}$.prototype=Object.create(Error.prototype),e.SyntaxError=$});LE.SyntaxError;var I4=new RegExp("^("+Object.keys(uf).join("|")+")\\b"),Mo=new WeakMap;function Bi(t,e,r){if(!(this instanceof Bi)||Mo.has(this))throw new TypeError("calling MessageFormat constructor without new is invalid");var n=LE(t);Mo.set(this,{ast:n,format:gu(n,e,r&&r.types),locale:Bi.supportedLocalesOf(e)[0]||"en",locales:e,options:r})}var L4=Bi;Object.defineProperties(Bi.prototype,{format:{configurable:!0,get:function(){var t=Mo.get(this);if(!t)throw new TypeError("MessageFormat.prototype.format called on value that's not an object initialized as a MessageFormat");return t.format}},formatToParts:{configurable:!0,writable:!0,value:function(t){var e=Mo.get(this);if(!e)throw new TypeError("MessageFormat.prototype.formatToParts called on value that's not an object initialized as a MessageFormat");var r=e.toParts||(e.toParts=gu.toParts(e.ast,e.locales,e.options&&e.options.types));return r(t)}},resolvedOptions:{configurable:!0,writable:!0,value:function(){var t=Mo.get(this);if(!t)throw new TypeError("MessageFormat.prototype.resolvedOptions called on value that's not an object initialized as a MessageFormat");return{locale:t.locale}}}});typeof Symbol<"u"&&Object.defineProperty(Bi.prototype,Symbol.toStringTag,{value:"Object"});Object.defineProperties(Bi,{supportedLocalesOf:{configurable:!0,writable:!0,value:function(t){return[].concat(Intl.NumberFormat.supportedLocalesOf(t),Intl.DateTimeFormat.supportedLocalesOf(t),Intl.PluralRules?Intl.PluralRules.supportedLocalesOf(t):[],[].concat(t||[]).filter(function(e){return I4.test(e)})).filter(function(e,r,n){return n.indexOf(e)===r})}}});function F4(t){return!!(t&&t.default&&typeof t.default=="object"&&Object.keys(t).length===1)}const Un=globalThis.document?.documentElement;let M4=class extends EventTarget{formatNumberOptions={returnIfNaN:"",postProcessors:new Map};formatDateOptions={postProcessors:new Map};#e=!1;#t="";#r=null;__storage={};__namespacePatternsMap=new Map;__namespaceLoadersCache={};__namespaceLoaderPromisesCache={};get locale(){return this.#e?this.#t||"":Un.lang||""}set locale(e){if(this.#n(e),!this.#e){const n=Un.lang;this._setHtmlLangAttribute(e),this._onLocaleChanged(e,n);return}const r=this.#t;this.#t=e,this.#r===null&&this._setHtmlLangAttribute(e),this._onLocaleChanged(e,r)}get loadingComplete(){return typeof this.__namespaceLoaderPromisesCache[this.locale]=="object"?Promise.all(Object.values(this.__namespaceLoaderPromisesCache[this.locale])):Promise.resolve()}constructor({allowOverridesForExistingNamespaces:e=!1,autoLoadOnLocaleChange:r=!1,showKeyAsFallback:n=!1,fallbackLocale:s=""}={}){super(),this.__allowOverridesForExistingNamespaces=e,this._autoLoadOnLocaleChange=!!r,this._showKeyAsFallback=n,this._fallbackLocale=s;const i=Un.getAttribute("data-localize-lang");this.#e=!!i,this.#e&&(this.locale=i,this._setupTranslationToolSupport()),Un.lang||(Un.lang=this.locale||"en-GB"),this._setupHtmlLangAttributeObserver()}addData(e,r,n){if(!this.__allowOverridesForExistingNamespaces&&this._isNamespaceInCache(e,r))throw new Error(`Namespace "${r}" has been already added for the locale "${e}".`);this.__storage[e]=this.__storage[e]||{},this.__allowOverridesForExistingNamespaces?this.__storage[e][r]={...this.__storage[e][r],...n}:this.__storage[e][r]=n}setupNamespaceLoader(e,r){this.__namespacePatternsMap.set(e,r)}loadNamespaces(e,{locale:r}={}){return Promise.all(e.map(n=>this.loadNamespace(n,{locale:r})))}loadNamespace(e,{locale:r=this.locale}={locale:this.locale}){const n=typeof e=="object",s=n?Object.keys(e)[0]:e;return this._isNamespaceInCache(r,s)?Promise.resolve():this._getCachedNamespaceLoaderPromise(r,s)||this._loadNamespaceData(r,e,n,s)}msg(e,r,n={}){const s=n.locale?n.locale:this.locale,i=this._getMessageForKeys(e,s);return i?new L4(i,s).format(r):""}teardown(){this._teardownHtmlLangAttributeObserver()}reset(){this.__storage={},this.__namespacePatternsMap=new Map,this.__namespaceLoadersCache={},this.__namespaceLoaderPromisesCache={}}setDatePostProcessorForLocale({locale:e,postProcessor:r}){this.formatDateOptions?.postProcessors.set(e,r)}setNumberPostProcessorForLocale({locale:e,postProcessor:r}){this.formatNumberOptions?.postProcessors.set(e,r)}_setupTranslationToolSupport(){this.#r=Un.lang||null}_setHtmlLangAttribute(e){this._teardownHtmlLangAttributeObserver(),Un.lang=e,this._setupHtmlLangAttributeObserver()}_setupHtmlLangAttributeObserver(){this._htmlLangAttributeObserver||(this._htmlLangAttributeObserver=new MutationObserver(e=>{e.forEach(r=>{this.#e?Un.lang==="auto"?(this.#r=null,this._setHtmlLangAttribute(this.locale)):this.#r=document.documentElement.lang:this._onLocaleChanged(document.documentElement.lang,r.oldValue||"")})})),this._htmlLangAttributeObserver.observe(document.documentElement,{attributes:!0,attributeFilter:["lang"],attributeOldValue:!0})}_teardownHtmlLangAttributeObserver(){this._htmlLangAttributeObserver&&this._htmlLangAttributeObserver.disconnect()}_isNamespaceInCache(e,r){return!!(this.__storage[e]&&this.__storage[e][r])}_getCachedNamespaceLoaderPromise(e,r){return this.__namespaceLoaderPromisesCache[e]?this.__namespaceLoaderPromisesCache[e][r]:null}_loadNamespaceData(e,r,n,s){const i=this._getNamespaceLoader(r,n,s),o=this._getNamespaceLoaderPromise(i,e,s);return this._cacheNamespaceLoaderPromise(e,s,o),o.then(a=>{if(this.__namespaceLoaderPromisesCache[e]&&this.__namespaceLoaderPromisesCache[e][s]===o){const l=F4(a)?a.default:a;this.addData(e,s,l)}})}_getNamespaceLoader(e,r,n){let s=this.__namespaceLoadersCache[n];if(s||(r?(s=e[n],this.__namespaceLoadersCache[n]=s):(s=this._lookupNamespaceLoader(n),this.__namespaceLoadersCache[n]=s)),!s)throw new Error(`Namespace "${n}" was not properly setup.`);return this.__namespaceLoadersCache[n]=s,s}_getNamespaceLoaderPromise(e,r,n,s=this._fallbackLocale){return e(r,n).catch(()=>{const i=this._getLangFromLocale(r);return e(i,n).catch(()=>{if(s)return this._getNamespaceLoaderPromise(e,s,n,"").catch(()=>{const o=this._getLangFromLocale(s);throw new Error(`Data for namespace "${n}" and current locale "${r}" or fallback locale "${s}" could not be loaded. Make sure you have data either for locale "${r}" (and/or generic language "${i}") or for fallback "${s}" (and/or "${o}").`)});throw new Error(`Data for namespace "${n}" and locale "${r}" could not be loaded. Make sure you have data for locale "${r}" (and/or generic language "${i}").`)})})}_cacheNamespaceLoaderPromise(e,r,n){this.__namespaceLoaderPromisesCache[e]||(this.__namespaceLoaderPromisesCache[e]={}),this.__namespaceLoaderPromisesCache[e][r]=n}_lookupNamespaceLoader(e){for(const[r,n]of this.__namespacePatternsMap){const s=typeof r=="string"&&r===e,i=typeof r=="object"&&r.constructor.name==="RegExp"&&r.test(e);if(s||i)return n}return null}_getLangFromLocale(e){return e.substring(0,2)}_onLocaleChanged(e,r){this.dispatchEvent(new CustomEvent("__localeChanging")),e!==r&&(this._autoLoadOnLocaleChange?(this._loadAllMissing(e,r),this.loadingComplete.then(()=>{this.dispatchEvent(new CustomEvent("localeChanged",{detail:{newLocale:e,oldLocale:r}}))})):this.dispatchEvent(new CustomEvent("localeChanged",{detail:{newLocale:e,oldLocale:r}})))}_loadAllMissing(e,r){const n=this.__storage[r]||{},s=this.__storage[e]||{};Object.keys(n).forEach(i=>{s[i]||this.loadNamespace(i,{locale:e})})}_getMessageForKeys(e,r){if(typeof e=="string")return this._getMessageForKey(e,r);const n=Array.from(e).reverse();let s,i;for(;n.length;)if(s=n.pop(),i=this._getMessageForKey(s,r),i)return i}_getMessageForKey(e,r){if(!e||e.indexOf(":")===-1)throw new Error(`Namespace is missing in the key "${e}". The format for keys is "namespace:name".`);const[n,s]=e.split(":"),i=this.__storage[r],o=i?i[n]:{},a=s.split(".").reduce((l,u)=>typeof l=="object"?l[u]:l,o);return String(a||(this._showKeyAsFallback?e:""))}#n(e){if(!e.includes("-"))throw new Error(`
      Locale was set to ${e}.
      Language only locales are not allowed, please use the full language locale e.g. 'en-GB' instead of 'en'.
      See https://github.com/ing-bank/lion/issues/187 for more information.
    `)}get _supportExternalTranslationTools(){return this.#e}set _supportExternalTranslationTools(e){this.#e=e}get _langAttrSetByTranslationTool(){return this.#t}set _langAttrSetByTranslationTool(e){this.#t=e}};const Qd=Symbol.for("lion::SingletonManagerClassStorage"),Zd=globalThis||window;let D4=class{constructor(){this._map=Zd[Qd]?Zd[Qd]:Zd[Qd]=new Map}set(e,r){this.has(e)||this._map.set(e,r)}get(e){return this._map.get(e)}has(e){return this._map.has(e)}};const eh=new D4;function df(){if(eh.has("@lion/ui::localize::0.x"))return eh.get("@lion/ui::localize::0.x");const t=new M4({autoLoadOnLocaleChange:!0,fallbackLocale:"en-GB"});return eh.set("@lion/ui::localize::0.x",t),t}const Do=(t,e)=>{const r=t._$AN;if(r===void 0)return!1;for(const n of r)n._$AO?.(e,!1),Do(n,e);return!0},bc=t=>{let e,r;do{if((e=t._$AM)===void 0)break;r=e._$AN,r.delete(t),t=e}while(r?.size===0)},FE=t=>{for(let e;e=t._$AM;t=e){let r=e._$AN;if(r===void 0)e._$AN=r=new Set;else if(r.has(t))break;r.add(t),U4(e)}};function V4(t){this._$AN!==void 0?(bc(this),this._$AM=t,FE(this)):this._$AM=t}function B4(t,e=!1,r=0){const n=this._$AH,s=this._$AN;if(s!==void 0&&s.size!==0)if(e)if(Array.isArray(n))for(let i=r;i<n.length;i++)Do(n[i],!1),bc(n[i]);else n!=null&&(Do(n,!1),bc(n));else Do(this,t)}const U4=t=>{t.type==Wp.CHILD&&(t._$AP??=B4,t._$AQ??=V4)};let j4=class extends Gp{constructor(){super(...arguments),this._$AN=void 0}_$AT(e,r,n){super._$AT(e,r,n),FE(this),this.isConnected=e._$AU}_$AO(e,r=!0){e!==this.isConnected&&(this.isConnected=e,e?this.reconnected?.():this.disconnected?.()),r&&(Do(this,e),bc(this))}setValue(e){if(P4(this._$Ct))this._$Ct._$AI(e,this);else{const r=[...this._$Ct._$AH];r[this._$Ci]=e,this._$Ct._$AI(r,this,0)}}disconnected(){}reconnected(){}},q4=class{constructor(e){this.G=e}disconnect(){this.G=void 0}reconnect(e){this.G=e}deref(){return this.G}},H4=class{constructor(){this.Y=void 0,this.Z=void 0}get(){return this.Y}pause(){this.Y??=new Promise((e=>this.Z=e))}resume(){this.Z?.(),this.Y=this.Z=void 0}};const Jb=t=>!k4(t)&&typeof t.then=="function",Xb=1073741823;let z4=class extends j4{constructor(){super(...arguments),this._$Cwt=Xb,this._$Cbt=[],this._$CK=new q4(this),this._$CX=new H4}render(...e){return e.find((r=>!Jb(r)))??Zr}update(e,r){const n=this._$Cbt;let s=n.length;this._$Cbt=r;const i=this._$CK,o=this._$CX;this.isConnected||this.disconnected();for(let a=0;a<r.length&&!(a>this._$Cwt);a++){const l=r[a];if(!Jb(l))return this._$Cwt=a,l;a<s&&l===n[a]||(this._$Cwt=Xb,s=0,Promise.resolve(l).then((async u=>{for(;o.get();)await o.get();const c=i.deref();if(c!==void 0){const d=c._$Cbt.indexOf(l);d>-1&&d<c._$Cwt&&(c._$Cwt=d,c.setValue(u))}})))}return Zr}disconnected(){this._$CK.disconnect(),this._$CX.pause()}reconnected(){this._$CK.reconnect(this),this._$CX.resume()}};const W4=Kp(z4),K4=t=>class extends t{static get localizeNamespaces(){return[]}static get waitForLocalizeNamespaces(){return!0}constructor(){super(),this._localizeManager=df(),this.__boundLocalizeOnLocaleChanged=(...e)=>{const r=Array.from(e)[0];this.__localizeOnLocaleChanged(r)},this.__boundLocalizeOnLocaleChanging=()=>{this.__localizeOnLocaleChanging()},this.__localizeStartLoadingNamespaces(),this.localizeNamespacesLoaded&&this.localizeNamespacesLoaded.then(()=>{this.__localizeMessageSync=!0})}async scheduleUpdate(){Object.getPrototypeOf(this).constructor.waitForLocalizeNamespaces&&await this.localizeNamespacesLoaded,super.scheduleUpdate()}connectedCallback(){super.connectedCallback(),this.localizeNamespacesLoaded&&this.localizeNamespacesLoaded.then(()=>this.onLocaleReady()),this._localizeManager.addEventListener("__localeChanging",this.__boundLocalizeOnLocaleChanging),this._localizeManager.addEventListener("localeChanged",this.__boundLocalizeOnLocaleChanged)}disconnectedCallback(){super.disconnectedCallback(),this._localizeManager.removeEventListener("__localeChanging",this.__boundLocalizeOnLocaleChanging),this._localizeManager.removeEventListener("localeChanged",this.__boundLocalizeOnLocaleChanged)}msgLit(e,r,n){return this.__localizeMessageSync?this._localizeManager.msg(e,r,n):this.localizeNamespacesLoaded?W4(this.localizeNamespacesLoaded.then(()=>this._localizeManager.msg(e,r,n)),Ce):""}__getUniqueNamespaces(){const e=[],r=new Set;return Object.getPrototypeOf(this).constructor.localizeNamespaces.forEach(r.add.bind(r)),r.forEach(n=>{e.push(n)}),e}__localizeStartLoadingNamespaces(){this.localizeNamespacesLoaded=this._localizeManager.loadNamespaces(this.__getUniqueNamespaces())}__localizeOnLocaleChanging(){this.__localizeStartLoadingNamespaces()}__localizeOnLocaleChanged(e){this.onLocaleChanged(e.detail.newLocale,e.detail.oldLocale)}onLocaleReady(){this.onLocaleUpdated()}onLocaleChanged(e,r){this.onLocaleUpdated(),this.requestUpdate()}onLocaleUpdated(){}},G4=mt(K4),hf="3.0.0",Yb=window.scopedElementsVersions||(window.scopedElementsVersions=[]);Yb.includes(hf)||Yb.push(hf);const J4=t=>class extends t{static scopedElements;static get scopedElementsVersion(){return hf}static __registry;get registry(){return this.constructor.__registry}set registry(e){this.constructor.__registry=e}attachShadow(e){const{scopedElements:r}=this.constructor;if(!this.registry||this.registry===this.constructor.__registry&&!Object.prototype.hasOwnProperty.call(this.constructor,"__registry")){this.registry=new CustomElementRegistry;for(const[n,s]of Object.entries(r??{}))this.registry.define(n,s)}return super.attachShadow({...e,customElements:this.registry,registry:this.registry})}},X4=mt(J4),Y4=t=>class extends X4(t){createRenderRoot(){const{shadowRootOptions:e,elementStyles:r}=this.constructor,n=this.attachShadow(e);return this.renderOptions.creationScope=n,Dp(n,r),this.renderOptions.renderBefore??=n.firstChild,n}},Q4=mt(Y4);function al(){return!!(globalThis.ShadowRoot?.prototype.createElement&&globalThis.ShadowRoot?.prototype.importNode)}const Z4=t=>class extends Q4(t){constructor(){super()}createScopedElement(e){return(al()?this.shadowRoot:document).createElement(e)}defineScopedElement(e,r){const n=this.registry.get(e),s=n&&n!==r;return!al()&&s&&console.error([`You are trying to re-register the "${e}" custom element with a different class via ScopedElementsMixin.`,"This is only possible with a CustomElementRegistry.","Your browser does not support this feature so you will need to load a polyfill for it.",'Load "@webcomponents/scoped-custom-element-registry" before you register ANY web component to the global customElements registry.','e.g. add "<script src="/node_modules/@webcomponents/scoped-custom-element-registry/scoped-custom-element-registry.min.js"><\/script>" as your first script tag.',"For more details you can visit https://open-wc.org/docs/development/scoped-elements/"].join(`
`)),n?this.registry.get(e):this.registry.define(e,r)}attachShadow(e){const{scopedElements:r}=this.constructor;if(!this.registry||this.registry===this.constructor.__registry&&!Object.prototype.hasOwnProperty.call(this.constructor,"__registry")){this.registry=al()?new CustomElementRegistry:customElements;for(const[n,s]of Object.entries(r??{}))this.defineScopedElement(n,s)}return Element.prototype.attachShadow.call(this,{...e,customElements:this.registry,registry:this.registry})}createRenderRoot(){const{shadowRootOptions:e,elementStyles:r}=this.constructor,n=this.attachShadow(e);return al()&&(this.renderOptions.creationScope=n),n instanceof ShadowRoot&&(Dp(n,r),this.renderOptions.renderBefore=this.renderOptions.renderBefore||n.firstChild),n}},ME=mt(Z4);let eD=class{constructor(){this.__running=!1,this.__queue=[]}add(e){this.__queue.push(e),this.__running||(this.complete=new Promise(r=>{this.__callComplete=r}),this.__run())}async __run(){this.__running=!0,await this.__queue[0](),this.__queue.shift(),this.__queue.length>0?this.__run():(this.__running=!1,this.__callComplete&&this.__callComplete())}};function tD(t){return t.charAt(0).toUpperCase()+t.slice(1)}const rD=t=>class extends t{constructor(){super(),this.__SyncUpdatableNamespace={}}firstUpdated(e){super.firstUpdated(e),this.__syncUpdatableInitialize()}connectedCallback(){super.connectedCallback(),this.__SyncUpdatableNamespace.connected=!0}disconnectedCallback(){super.disconnectedCallback(),this.__SyncUpdatableNamespace.connected=!1}static enabledWarnings=super.enabledWarnings?.filter(e=>e!=="change-in-update")||[];static __syncUpdatableHasChanged(e,r,n){const s=this.elementProperties;return s.get(e)&&s.get(e).hasChanged?s.get(e).hasChanged(r,n):r!==n}__syncUpdatableInitialize(){const e=this.__SyncUpdatableNamespace,r=this.constructor;e.initialized=!0,e.queue&&Array.from(e.queue).forEach(n=>{r.__syncUpdatableHasChanged(n,this[n],void 0)&&this.updateSync(n,void 0)})}requestUpdate(e,r,n){if(super.requestUpdate(e,r,n),e===void 0)return;this.__SyncUpdatableNamespace=this.__SyncUpdatableNamespace||{};const s=this.__SyncUpdatableNamespace,i=this.constructor;s.initialized?i.__syncUpdatableHasChanged(e,this[e],r)&&this.updateSync(e,r):(s.queue=s.queue||new Set,s.queue.add(e))}updateSync(e,r){}},nD=mt(rD),sD=(t=>{switch(t){case"bg-BG":return ve(()=>import("./bg-BG-BqLSXSgK.js"),__vite__mapDeps([0,1]),import.meta.url);case"bg":return ve(()=>import("./bg-Ch91FBqZ.js"),[],import.meta.url);case"cs-CZ":return ve(()=>import("./cs-CZ-BOieS6Re.js"),__vite__mapDeps([2,3]),import.meta.url);case"cs":return ve(()=>import("./cs-Bco-9vYd.js"),[],import.meta.url);case"de-DE":return ve(()=>import("./de-DE-NiEdSbeI.js"),__vite__mapDeps([4,5]),import.meta.url);case"de":return ve(()=>import("./de--MUj2jPW.js"),[],import.meta.url);case"en-AU":return ve(()=>import("./en-AU-5SYH9YrO.js"),__vite__mapDeps([6,7]),import.meta.url);case"en-GB":return ve(()=>import("./en-GB-5SYH9YrO.js"),__vite__mapDeps([8,7]),import.meta.url);case"en-US":return ve(()=>import("./en-US-5SYH9YrO.js"),__vite__mapDeps([9,7]),import.meta.url);case"en-PH":case"en":return ve(()=>import("./en-QBEFuq4A.js"),[],import.meta.url);case"es-ES":return ve(()=>import("./es-ES-BzB2G1H7.js"),__vite__mapDeps([10,11]),import.meta.url);case"es":return ve(()=>import("./es-QUDKKOEt.js"),[],import.meta.url);case"fr-FR":return ve(()=>import("./fr-FR-D8x_WpSN.js"),__vite__mapDeps([12,13]),import.meta.url);case"fr-BE":return ve(()=>import("./fr-BE-D8x_WpSN.js"),__vite__mapDeps([14,13]),import.meta.url);case"fr":return ve(()=>import("./fr-Crw_WS9R.js"),[],import.meta.url);case"hu-HU":return ve(()=>import("./hu-HU-DzuJRq2x.js"),__vite__mapDeps([15,16]),import.meta.url);case"hu":return ve(()=>import("./hu-BzLNk3Oy.js"),[],import.meta.url);case"it-IT":return ve(()=>import("./it-IT-BVziFtOr.js"),__vite__mapDeps([17,18]),import.meta.url);case"it":return ve(()=>import("./it-Dk-tLV60.js"),[],import.meta.url);case"nl-BE":return ve(()=>import("./nl-BE-Cv6cOJ-k.js"),__vite__mapDeps([19,20]),import.meta.url);case"nl-NL":return ve(()=>import("./nl-NL-Cv6cOJ-k.js"),__vite__mapDeps([21,20]),import.meta.url);case"nl":return ve(()=>import("./nl-ukLmcyhE.js"),[],import.meta.url);case"pl-PL":return ve(()=>import("./pl-PL-C3QXGAg0.js"),__vite__mapDeps([22,23]),import.meta.url);case"pl":return ve(()=>import("./pl-BsbBHKbu.js"),[],import.meta.url);case"ro-RO":return ve(()=>import("./ro-RO-BHOQwu0O.js"),__vite__mapDeps([24,25]),import.meta.url);case"ro":return ve(()=>import("./ro-BWWeoMIS.js"),[],import.meta.url);case"ru-RU":return ve(()=>import("./ru-RU-DCvtZjBo.js"),__vite__mapDeps([26,27]),import.meta.url);case"ru":return ve(()=>import("./ru-D87QXJFw.js"),[],import.meta.url);case"sk-SK":return ve(()=>import("./sk-SK-DaLB_sM8.js"),__vite__mapDeps([28,29]),import.meta.url);case"sk":return ve(()=>import("./sk-DCOU_ZI_.js"),[],import.meta.url);case"tr-TR":return ve(()=>import("./tr-TR-Dhk7tqKh.js"),__vite__mapDeps([30,31]),import.meta.url);case"tr":return ve(()=>import("./tr-92apvQxK.js"),[],import.meta.url);case"uk-UA":return ve(()=>import("./uk-UA-BP_5Rplg.js"),__vite__mapDeps([32,33]),import.meta.url);case"uk":return ve(()=>import("./uk-CGlal3kJ.js"),[],import.meta.url);case"zh-CN":case"zh":return ve(()=>import("./zh-CZafHN1K.js"),[],import.meta.url);default:return ve(()=>import("./en-QBEFuq4A.js"),[],import.meta.url)}}),iD=t=>`${t[0].toUpperCase()}${t.slice(1)}`;let oD=class extends G4(je){static get properties(){return{feedbackData:{attribute:!1}}}static localizeNamespaces=[{"lion-form-core":sD},...super.localizeNamespaces];static get styles(){return[ue`
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
      `]}constructor(){super(),this.feedbackData=void 0}_messageTemplate({message:e}){return e}updated(e){super.updated(e),this.feedbackData&&this.feedbackData[0]?(this.setAttribute("type",this.feedbackData[0].type),this.currentType=this.feedbackData[0].type):this.currentType!=="success"&&this.removeAttribute("type")}render(){return Y`
      ${this.feedbackData&&this.feedbackData.map(({message:e,type:r,validator:n})=>Y`
          <div class="validation-feedback__type">
            ${e&&r?this._localizeManager.msg(`lion-form-core:validation${iD(r)}`):Ce}
          </div>
          ${this._messageTemplate({message:e,type:r,validator:n})}
        `)}
    `}},vc=class{constructor(e){this.type="unparseable",this.viewValue=e}toString(){return JSON.stringify({type:this.type,viewValue:this.viewValue})}};const aD=[Node.DOCUMENT_POSITION_PRECEDING,Node.DOCUMENT_POSITION_CONTAINS,Node.DOCUMENT_POSITION_CONTAINS|Node.DOCUMENT_POSITION_PRECEDING];function DE(t,{reverse:e}={}){const r=(s,i)=>{const o=s.compareDocumentPosition(i);return aD.includes(o)?1:-1},n=t.filter(s=>s);return n.sort(r),e&&n.reverse(),n}const lD=t=>class extends t{constructor(){super(),this.name="",this._parentFormGroup=void 0,this.allowCrossRootRegistration=!1}get name(){return this.__name||""}set name(e){const r=this.name;this.__name=e.toString(),this.requestUpdate("name",r)}static get properties(){return{name:{type:String,reflect:!0},allowCrossRootRegistration:{type:Boolean,attribute:"allow-cross-root-registration"}}}connectedCallback(){super.connectedCallback(),this.dispatchEvent(new CustomEvent("form-element-register",{detail:{element:this},bubbles:!0,composed:!!this.allowCrossRootRegistration}))}disconnectedCallback(){super.disconnectedCallback(),this.__unregisterFormElement()}__unregisterFormElement(){this._parentFormGroup&&this._parentFormGroup.removeFormElement(this)}},sm=mt(lD),cD=t=>class extends sm(Oa(Na(t))){static get properties(){return{readOnly:{type:Boolean,attribute:"readonly",reflect:!0},label:String,labelSrOnly:{type:Boolean,attribute:"label-sr-only",reflect:!0},helpText:{type:String,attribute:"help-text"},modelValue:{attribute:!1},_ariaLabelledNodes:{attribute:!1},_ariaDescribedNodes:{attribute:!1},_repropagationRole:{attribute:!1},_isRepropagationEndpoint:{attribute:!1}}}get label(){return this.__label??(this._labelNode?.textContent||"")}set label(e){const r=this.label;this.__label=e,this.requestUpdate("label",r)}get helpText(){return this.__helpText??(this._helpTextNode?.textContent||"")}set helpText(e){const r=this.helpText;this.__helpText=e,this.requestUpdate("helpText",r)}get fieldName(){return this.__fieldName||this.label||this.name||""}set fieldName(e){this.__fieldName=e}get slots(){return{...super.slots,label:()=>{const e=document.createElement("label");return e.textContent=this.label,e},"help-text":()=>{const e=document.createElement("div");return e.textContent=this.helpText,e}}}get _inputNode(){return this.__getDirectSlotChild("input")}get _labelNode(){return this.__getDirectSlotChild("label")}get _helpTextNode(){return this.__getDirectSlotChild("help-text")}get _feedbackNode(){return this.__getDirectSlotChild("feedback")}static enabledWarnings=super.enabledWarnings?.filter(e=>e!=="change-in-update")||[];constructor(){super(),this.readOnly=!1,this.labelSrOnly=!1,this._inputId=NE(this.localName),this._ariaLabelledNodes=[],this._ariaDescribedNodes=[],this._repropagationRole="child",this._isRepropagationEndpoint=!1,this.addEventListener("model-value-changed",this.__repropagateChildrenValues),this._onLabelClick=this._onLabelClick.bind(this)}connectedCallback(){super.connectedCallback(),this._enhanceLightDomClasses(),this._enhanceLightDomA11y(),this._triggerInitialModelValueChangedEvent(),this._labelNode&&this._labelNode.addEventListener("click",this._onLabelClick)}disconnectedCallback(){super.disconnectedCallback(),this._labelNode&&this._labelNode.removeEventListener("click",this._onLabelClick)}updated(e){super.updated(e),e.has("disabled")&&this._inputNode?.setAttribute("aria-disabled",`${!!this.disabled}`),e.has("_ariaLabelledNodes")&&this.__reflectAriaAttr("aria-labelledby",this._ariaLabelledNodes,this.__reorderAriaLabelledNodes),e.has("_ariaDescribedNodes")&&this.__reflectAriaAttr("aria-describedby",this._ariaDescribedNodes,this.__reorderAriaDescribedNodes),e.has("label")&&this.__label!==void 0&&this._labelNode&&(this._labelNode.textContent=this.label),e.has("helpText")&&this.__helpText!==void 0&&this._helpTextNode&&(this._helpTextNode.textContent=this.helpText),e.has("name")&&this.dispatchEvent(new CustomEvent("form-element-name-changed",{detail:{oldName:e.get("name"),newName:this.name},bubbles:!0}))}_triggerInitialModelValueChangedEvent(){this._dispatchInitialModelValueChangedEvent()}_enhanceLightDomClasses(){this._inputNode&&this._inputNode.classList.add("form-control")}_enhanceLightDomA11y(){const{_inputNode:e,_labelNode:r,_helpTextNode:n,_feedbackNode:s}=this;e&&(e.id=e.id||this._inputId),r&&(r.setAttribute("for",this._inputId),this.addToAriaLabelledBy(r,{idPrefix:"label"})),n&&this.addToAriaDescribedBy(n,{idPrefix:"help-text"}),s&&(this.addEventListener("focusin",()=>{s.setAttribute("aria-live","polite")}),this.addEventListener("focusout",()=>{s.setAttribute("aria-live","assertive")}),this.addToAriaDescribedBy(s,{idPrefix:"feedback"})),this._enhanceLightDomA11yForAdditionalSlots()}_enhanceLightDomA11yForAdditionalSlots(e=["prefix","suffix","before","after"]){e.forEach(r=>{const n=this.__getDirectSlotChild(r);n&&(n.hasAttribute("data-label")&&this.addToAriaLabelledBy(n,{idPrefix:r}),n.hasAttribute("data-description")&&this.addToAriaDescribedBy(n,{idPrefix:r}))})}__reflectAriaAttr(e,r,n){if(this._inputNode){if(n){const i=r.filter(c=>this.contains(c)),o=r.filter(c=>!this.contains(c)),a=i.map(c=>c.assignedSlot||c),l=[...DE(a)],u=[];l.forEach(c=>{i.forEach(d=>{c.name===d.slot&&u.push(d)})}),r=[...u,...o]}const s=r.map(i=>i.id).join(" ");this._inputNode.setAttribute(e,s)}}render(){return Y`
        <div class="form-field__group-one">${this._groupOneTemplate()}</div>
        <div class="form-field__group-two">${this._groupTwoTemplate()}</div>
      `}_groupOneTemplate(){return Y` ${this._labelTemplate()} ${this._helpTextTemplate()} `}_groupTwoTemplate(){return Y` ${this._inputGroupTemplate()} ${this._feedbackTemplate()} `}_labelTemplate(){return Y`
        <div class="form-field__label">
          <slot name="label"></slot>
        </div>
      `}_helpTextTemplate(){return Y`
        <small class="form-field__help-text">
          <slot name="help-text"></slot>
        </small>
      `}_inputGroupTemplate(){return Y`
        <div class="input-group">
          ${this._inputGroupBeforeTemplate()}
          <div class="input-group__container">
            ${this._inputGroupPrefixTemplate()} ${this._inputGroupInputTemplate()}
            ${this._inputGroupSuffixTemplate()}
          </div>
          ${this._inputGroupAfterTemplate()}
        </div>
      `}_inputGroupBeforeTemplate(){return Y`
        <div class="input-group__before">
          <slot name="before"></slot>
        </div>
      `}_inputGroupPrefixTemplate(){return Array.from(this.children).find(e=>e.slot==="prefix")?Y`
            <div class="input-group__prefix">
              <slot name="prefix"></slot>
            </div>
          `:Ce}_inputGroupInputTemplate(){return Y`
        <div class="input-group__input">
          <slot name="input"></slot>
        </div>
      `}_inputGroupSuffixTemplate(){return Array.from(this.children).find(e=>e.slot==="suffix")?Y`
            <div class="input-group__suffix">
              <slot name="suffix"></slot>
            </div>
          `:Ce}_inputGroupAfterTemplate(){return Y`
        <div class="input-group__after">
          <slot name="after"></slot>
        </div>
      `}_feedbackTemplate(){return Y`
        <div class="form-field__feedback">
          <slot name="feedback"></slot>
        </div>
      `}_isEmpty(e=this.modelValue){let r=e;if(this.modelValue instanceof vc&&(r=this.modelValue.viewValue),typeof r=="object"&&r!==null&&!(r instanceof Date))return!Object.keys(r).length;const n=typeof r=="number"&&(r===0||Number.isNaN(r));return!r&&!n&&!(typeof r=="boolean"&&r===!1)}static get styles(){return[ue`
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
        `]}_getAriaDescriptionElements(){return[this._helpTextNode,this._feedbackNode]}addToAriaLabelledBy(e,{idPrefix:r="",reorder:n=!0}={}){e.id=e.id||`${r}-${this._inputId}`,this._ariaLabelledNodes.includes(e)||(this._ariaLabelledNodes=[...this._ariaLabelledNodes,e],this.__reorderAriaLabelledNodes=!!n)}removeFromAriaLabelledBy(e){this._ariaLabelledNodes.includes(e)&&(this._ariaLabelledNodes.splice(this._ariaLabelledNodes.indexOf(e),1),this._ariaLabelledNodes=[...this._ariaLabelledNodes],this.__reorderAriaLabelledNodes=!1)}addToAriaDescribedBy(e,{idPrefix:r="",reorder:n=!0}={}){e.id=e.id||`${r}-${this._inputId}`,this._ariaDescribedNodes.includes(e)||(this._ariaDescribedNodes=[...this._ariaDescribedNodes,e],this.__reorderAriaDescribedNodes=!!n)}removeFromAriaDescribedBy(e){this._ariaDescribedNodes.includes(e)&&(this._ariaDescribedNodes.splice(this._ariaDescribedNodes.indexOf(e),1),this._ariaDescribedNodes=[...this._ariaDescribedNodes],this.__reorderAriaLabelledNodes=!1)}__getDirectSlotChild(e){return Array.from(this.children).find(r=>r.slot===e)}_dispatchInitialModelValueChangedEvent(){this._repropagationRole!=="child"&&(this.__repropagateChildrenInitialized=!0,this.dispatchEvent(new CustomEvent("model-value-changed",{bubbles:!0,detail:{formPath:[this],initialize:!0,isTriggeredByUser:!1}})))}_onBeforeRepropagateChildrenValues(e){}__repropagateChildrenValues(e){this._onBeforeRepropagateChildrenValues(e);const r=e.detail&&e.detail.element||e.target,n=this._isRepropagationEndpoint||this._repropagationRole==="choice-group";if(r===this)return;e.stopImmediatePropagation();const s=this._repropagationRole!=="child"&&!this.__repropagateChildrenInitialized,i=e.detail&&e.detail.initialize;if(s||i||!this._repropagationCondition(r))return;let o=[];n||(o=e.detail&&e.detail.formPath||[r]);const a=[...o,this];this.dispatchEvent(new CustomEvent("model-value-changed",{bubbles:!0,detail:{formPath:a,isTriggeredByUser:!!e.detail?.isTriggeredByUser}}))}_repropagationCondition(e){return!!e}_onLabelClick(){}},no=mt(cD);function Qb(t=[],e=[]){return t.filter(r=>!e.includes(r)).concat(e.filter(r=>!t.includes(r)))}function uD(t){return t instanceof vc?t.viewValue:t}const dD=t=>class extends no(nD(Oa(Na(ME(t))))){static get scopedElements(){return{...super.scopedElements,"lion-validation-feedback":oD}}static get properties(){return{validators:{attribute:!1},hasFeedbackFor:{attribute:!1},shouldShowFeedbackFor:{attribute:!1},showsFeedbackFor:{type:Array,attribute:"shows-feedback-for",reflect:!0,converter:{fromAttribute:(e=>e.split(",")),toAttribute:(e=>e.join(","))}},validationStates:{attribute:!1},isPending:{type:Boolean,attribute:"is-pending",reflect:!0},defaultValidators:{attribute:!1},_visibleMessagesAmount:{attribute:!1},__childModelValueChanged:{attribute:!1}}}static get validationTypes(){return["error"]}get operationMode(){return"enter"}get slots(){return{...super.slots,feedback:()=>{const e=this.createScopedElement("lion-validation-feedback");return e.setAttribute("data-tag-name","lion-validation-feedback"),e}}}get _allValidators(){return[...this.validators,...this.defaultValidators]}constructor(){super(),this.hasFeedbackFor=[],this.showsFeedbackFor=[],this.shouldShowFeedbackFor=[],this.validationStates={},this.isPending=!1,this.validators=[],this.defaultValidators=[],this._visibleMessagesAmount=1,this.__syncValidationResult=[],this.__asyncValidationResult=[],this.__validationResult=[],this.__prevValidationResult=[],this.__prevShownValidationResult=[],this.__childModelValueChanged=!1,this._onValidatorUpdated=this._onValidatorUpdated.bind(this),this._updateFeedbackComponent=this._updateFeedbackComponent.bind(this)}connectedCallback(){super.connectedCallback(),df().addEventListener("localeChanged",this._updateFeedbackComponent)}disconnectedCallback(){super.disconnectedCallback(),df().removeEventListener("localeChanged",this._updateFeedbackComponent)}firstUpdated(e){super.firstUpdated(e),this.__isValidateInitialized=!0,this.validate(),this._repropagationRole!=="child"&&this.addEventListener("model-value-changed",()=>{this.__childModelValueChanged=!0})}updateSync(e,r){if(super.updateSync(e,r),e==="validators"?(this.__setupValidators(),this.validate({clearCurrentResult:!0})):e==="modelValue"&&this.validate({clearCurrentResult:!0}),["touched","dirty","prefilled","focused","submitted","hasFeedbackFor","filled"].includes(e)&&this._updateShouldShowFeedbackFor(),e==="showsFeedbackFor"){this._inputNode&&this._inputNode.setAttribute("aria-invalid",`${this._hasFeedbackVisibleFor("error")}`);const n=Qb(this.showsFeedbackFor,r);n.length>0&&this.dispatchEvent(new Event("showsFeedbackForChanged",{bubbles:!0})),n.forEach(s=>{this.dispatchEvent(new Event(`showsFeedbackFor${tD(s)}Changed`,{bubbles:!0}))})}e==="shouldShowFeedbackFor"&&Qb(this.shouldShowFeedbackFor,r).length>0&&this.dispatchEvent(new Event("shouldShowFeedbackForChanged",{bubbles:!0}))}async validate({clearCurrentResult:e=!1}={}){if(this.validateComplete=new Promise(r=>{this.__validateCompleteResolve=r}),this.disabled){this.__clearValidationResults(),this.__finishValidationPass(),this._updateFeedbackComponent();return}this.__isValidateInitialized&&(this.__prevValidationResult=this.__validationResult,e&&this.__clearValidationResults(),await this.__executeValidators())}#e(e){let r=e;for(;r;){if(r.constructor.validatorName==="Required")return!0;r=Object.getPrototypeOf(r)}return!1}async __executeValidators(){const e=uD(this.modelValue),r=this.__isEmpty(e);if(this.__syncValidationResult=[],r){const a=!this._isFormOrFieldset,l=this._allValidators.find(u=>u.constructor?.validatorName==="Required");if(l&&(this.__syncValidationResult=[{validator:l,outcome:!0}]),a){this.__finishValidationPass({syncValidationResult:this.__syncValidationResult});return}}const n=[],s=[],i=[];for(const a of this._allValidators)a?.executeOnResults?n.push(a):this.#e(a)||(a.constructor.async?i.push(a):s.push(a));const o=!!i.length;this.__syncValidationResult=[...this.__syncValidationResult,...this.__executeSyncValidators(s,e)],this.__finishValidationPass({syncValidationResult:this.__syncValidationResult,metaValidators:n}),o?(this.isPending=!0,this.__asyncValidationResult=await this.__executeAsyncValidators(i,e),this.isPending=!1,this.__finishValidationPass({syncValidationResult:this.__syncValidationResult,asyncValidationResult:this.__asyncValidationResult,metaValidators:n}),this.__validateCompleteResolve?.(!0)):this.__validateCompleteResolve?.(!0)}__executeSyncValidators(e,r){return e.map(n=>({validator:n,outcome:n.execute(r,n.param,{node:this})})).filter(n=>!!n.outcome)}async __executeAsyncValidators(e,r){const n=e.map(i=>i.execute(r,i.param,{node:this})),s=await Promise.all(n);return s.map((i,o)=>({validator:e[o],outcome:s[o]})).filter(i=>!!i.outcome)}__executeMetaValidators(e,r){return r.length?this._isEmpty(this.modelValue)?(this.__prevShownValidationResult=[],[]):r.map(n=>({validator:n,outcome:n.executeOnResults({regularValidationResult:e.map(s=>s.validator),prevValidationResult:this.__prevValidationResult.map(s=>s.validator),prevShownValidationResult:this.__prevShownValidationResult.map(s=>s.validator)})})).filter(n=>!!n.outcome):[]}__finishValidationPass({syncValidationResult:e=[],asyncValidationResult:r=[],metaValidators:n=[]}={}){const s=[...e,...r],i=this.__executeMetaValidators(s,n);this.__validationResult=[...i,...s];const o=this.constructor.validationTypes.reduce((a,l)=>({...a,[l]:{}}),{});for(const{validator:a,outcome:l}of this.__validationResult){o[a.type]||(o[a.type]={});const u=a.constructor;o[a.type][u.validatorName]=l}this.validationStates=o,this.hasFeedbackFor=[...new Set(this.__validationResult.map(({validator:a})=>a.type))],this.dispatchEvent(new Event("validate-performed",{bubbles:!0}))}__clearValidationResults(){this.__syncValidationResult=[],this.__asyncValidationResult=[]}_onValidatorUpdated(e){(e.type==="param-changed"||e.type==="config-changed")&&this.validate()}__setupValidators(){const e=["param-changed","config-changed"];for(const r of this.__prevValidators||[]){for(const n of e)r.removeEventListener?.(n,this._onValidatorUpdated);r.onFormControlDisconnect(this)}for(const r of this._allValidators){if(r.constructor._$isValidator$===void 0){const i=`Validators array only accepts class instances of Validator. Type "${Array.isArray(r)?"array":typeof r}" found. This may be caused by having multiple installations of "@lion/ui/form-core.js".`;throw console.error(i,this),new Error(i)}const n=this.constructor,s=r.constructor;if(n.validationTypes.indexOf(r.type)===-1){const i=`This component does not support the validator type "${r.type}" used in "${s.validatorName}". You may change your validators type or add it to the components "static get validationTypes() {}".`;throw console.error(i,this),new Error(i)}for(const i of e)r.addEventListener?.(i,o=>{this._onValidatorUpdated(o,{validator:r})});r.onFormControlConnect(this)}this.__prevValidators=this._allValidators}__isEmpty(e){return typeof this._isEmpty=="function"?this._isEmpty(e):this.modelValue===null||typeof this.modelValue>"u"||this.modelValue===""}async __getFeedbackMessages(e){let r=await this.fieldName;return Promise.all(e.map(async({validator:n,outcome:s})=>(n.config.fieldName&&(r=await n.config.fieldName),{message:await n._getMessage({modelValue:this.modelValue,formControl:this,fieldName:r,outcome:s}),type:n.type,validator:n,visibilityDuration:n.config?.visibilityDuration||3e3})))}_updateFeedbackComponent(){window.clearTimeout(this.removeMessage);const{_feedbackNode:e}=this;e&&(this.__feedbackQueue||(this.__feedbackQueue=new eD),this.showsFeedbackFor.length>0?this.__feedbackQueue.add(async()=>{const r=this._prioritizeAndFilterFeedback({validationResult:this.__validationResult.map(s=>s.validator)});this.__prioritizedResult=r.map(s=>this.__validationResult.find(i=>s===i.validator)).filter(Boolean),this.__prioritizedResult.length>0&&(this.__prevShownValidationResult=this.__prioritizedResult);const n=await this.__getFeedbackMessages(this.__prioritizedResult);e.feedbackData=n||[],n?.[0]&&n[0].type==="success"&&n[0].visibilityDuration!==1/0&&(this.removeMessage=window.setTimeout(()=>{e.removeAttribute("type"),e.feedbackData=[]},n[0].visibilityDuration))}):this.__feedbackQueue.add(async()=>{e.feedbackData=[]}),this.feedbackComplete=this.__feedbackQueue.complete)}_showFeedbackConditionFor(e,r){return!0}get _feedbackConditionMeta(){return{modelValue:this.modelValue,el:this}}feedbackCondition(e,r=this._feedbackConditionMeta,n=this._showFeedbackConditionFor.bind(this)){return n(e,r)}_hasFeedbackVisibleFor(e){return this.hasFeedbackFor?.includes(e)&&this.shouldShowFeedbackFor?.includes(e)}updated(e){if(super.updated(e),e.has("shouldShowFeedbackFor")||e.has("hasFeedbackFor")){const r=this.constructor;this.showsFeedbackFor=r.validationTypes.map(n=>this._hasFeedbackVisibleFor(n)?n:void 0).filter(Boolean),this._updateFeedbackComponent()}if(e.has("__childModelValueChanged")&&this.__childModelValueChanged&&(this.validate({clearCurrentResult:!0}),this.__childModelValueChanged=!1),e.has("validationStates")){const r=e.get("validationStates");r&&Object.entries(this.validationStates).forEach(([n,s])=>{r[n]&&JSON.stringify(s)!==JSON.stringify(r[n])&&this.dispatchEvent(new CustomEvent(`${n}StateChanged`,{detail:s}))})}}_updateShouldShowFeedbackFor(){const e=this.constructor.validationTypes.map(r=>this.feedbackCondition(r,this._feedbackConditionMeta,this._showFeedbackConditionFor.bind(this))?r:void 0).filter(Boolean);JSON.stringify(this.shouldShowFeedbackFor)!==JSON.stringify(e)&&(this.shouldShowFeedbackFor=e)}_prioritizeAndFilterFeedback({validationResult:e}){const r=this.constructor.validationTypes;return e.filter(n=>this.feedbackCondition(n.type,this._feedbackConditionMeta,this._showFeedbackConditionFor.bind(this))).sort((n,s)=>r.indexOf(n.type)-r.indexOf(s.type)).slice(0,this._visibleMessagesAmount)}},yu=mt(dD),hD=t=>class extends yu(no(t)){static get properties(){return{formattedValue:{attribute:!1},serializedValue:{attribute:!1},formatOptions:{attribute:!1}}}#e={didFormatterOutputSyncToView:!1,didFormatterRun:!1};requestUpdate(e,r,n){super.requestUpdate(e,r,n),e==="modelValue"&&this.modelValue!==r&&this._onModelValueChanged({modelValue:this.modelValue},{modelValue:r}),e==="serializedValue"&&this.serializedValue!==r&&this._calculateValues({source:"serialized"}),e==="formattedValue"&&this.formattedValue!==r&&this._calculateValues({source:"formatted"})}get value(){return this._inputNode?.value||this.__value||""}set value(e){this._inputNode?(this._inputNode.value=e,this.__value=void 0):this.__value=e}preprocessor(e,r){}parser(e,r){return e}formatter(e,r){return e}serializer(e){return e!==void 0?e:""}deserializer(e){return e===void 0?"":e}_calculateValues({source:e}={source:null}){this.__preventRecursiveTrigger||(this.__preventRecursiveTrigger=!0,e!=="model"&&(e==="serialized"?this.modelValue=this.deserializer(this.serializedValue):e==="formatted"&&(this.modelValue=this._callParser())),e!=="formatted"&&(this.formattedValue=this._callFormatter()),e!=="serialized"&&(this.serializedValue=this.serializer(this.modelValue)),this._reflectBackFormattedValueToUser(),this.__preventRecursiveTrigger=!1,this.__prevViewValue=this.value)}_callParser(e=this.formattedValue){if(e==="")return"";if(typeof e!="string")return;const r=this.parser(e,{...this.formatOptions,mode:this.#t(),viewValueStates:this.#r()});return r!==void 0?r:new vc(e)}_callFormatter(){return this.#e.didFormatterRun=!1,this._isHandlingUserInput&&this.hasFeedbackFor?.includes("error")&&this._inputNode?this.value:this.modelValue instanceof vc?this.modelValue.viewValue:(this.#e.didFormatterRun=!0,this.formatter(this.modelValue,{...this.formatOptions,mode:this.#t(),viewValueStates:this.#r()}))}_onModelValueChanged(...e){this._calculateValues({source:"model"}),this._dispatchModelValueChangedEvent(...e)}_dispatchModelValueChangedEvent(...e){this.dispatchEvent(new CustomEvent("model-value-changed",{bubbles:!0,detail:{formPath:[this],isTriggeredByUser:!!this._isHandlingUserInput}}))}_syncValueUpwards(){this.__isHandlingComposition||this.__handlePreprocessor();const e=this.formattedValue;this.modelValue=this._callParser(this.value),e===this.formattedValue&&this.__prevViewValue!==this.value&&this._calculateValues()}__handlePreprocessor(){let e=this.value.length;this._inputNode&&"selectionStart"in this._inputNode&&this._inputNode?.type!=="range"&&(e=this._inputNode.selectionStart);const r=this.preprocessor(this.value,{...this.formatOptions,currentCaretIndex:e,prevViewValue:this.__prevViewValue});if(r!==void 0){if(typeof r=="string")this.value=r;else if(typeof r=="object"){const{viewValue:n,caretIndex:s}=r;this.value=n,s&&this._inputNode&&"selectionStart"in this._inputNode&&(this._inputNode.selectionStart=s,this._inputNode.selectionEnd=s)}}}_reflectBackFormattedValueToUser(){this._reflectBackOn()&&(this.value=typeof this.formattedValue<"u"?this.formattedValue:"",this.#e.didFormatterOutputSyncToView=!!this.formattedValue&&this.#e.didFormatterRun)}_reflectBackOn(){return!this._isHandlingUserInput}_proxyInputEvent(){this.dispatchEvent(new Event("user-input-changed",{bubbles:!0}))}_onUserInputChanged(){this._isHandlingUserInput=!0,this._syncValueUpwards(),this._isHandlingUserInput=!1}__onCompositionEvent({type:e}){e==="compositionstart"?this.__isHandlingComposition=!0:e==="compositionend"&&(this.__isHandlingComposition=!1,this._syncValueUpwards())}constructor(){super(),this.formatOn="change",this.formatOptions={mode:"auto"},this.formattedValue=void 0,this.serializedValue=void 0,this._isPasting=!1,this._isHandlingUserInput=!1,this.__prevViewValue="",this.__onCompositionEvent=this.__onCompositionEvent.bind(this),this.addEventListener("user-input-changed",this._onUserInputChanged),this.addEventListener("paste",this.__onPaste),this._reflectBackFormattedValueToUser=this._reflectBackFormattedValueToUser.bind(this),this._reflectBackFormattedValueDebounced=()=>{setTimeout(this._reflectBackFormattedValueToUser)}}__onPaste(){this._isPasting=!0,setTimeout(()=>{this._isPasting=!1})}connectedCallback(){super.connectedCallback(),typeof this.modelValue>"u"&&this._syncValueUpwards(),this.__prevViewValue=this.value,this._reflectBackFormattedValueToUser(),this._inputNode&&(this._inputNode.addEventListener(this.formatOn,this._reflectBackFormattedValueDebounced),this._inputNode.addEventListener("input",this._proxyInputEvent),this._inputNode.addEventListener("compositionstart",this.__onCompositionEvent),this._inputNode.addEventListener("compositionend",this.__onCompositionEvent))}disconnectedCallback(){super.disconnectedCallback(),this._inputNode&&(this._inputNode.removeEventListener("input",this._proxyInputEvent),this._inputNode.removeEventListener(this.formatOn,this._reflectBackFormattedValueDebounced),this._inputNode.removeEventListener("compositionstart",this.__onCompositionEvent),this._inputNode.removeEventListener("compositionend",this.__onCompositionEvent))}#t(){return this._isPasting?"pasted":this._isHandlingUserInput&&this.__prevViewValue?"user-edited":"auto"}#r(){const e=[];return this.#e.didFormatterOutputSyncToView&&e.push("formatted"),e}},im=mt(hD),fD=t=>class extends no(t){static get properties(){return{touched:{type:Boolean,reflect:!0},dirty:{type:Boolean,reflect:!0},filled:{type:Boolean,reflect:!0},prefilled:{attribute:!1},submitted:{attribute:!1}}}requestUpdate(e,r,n){super.requestUpdate(e,r,n),e==="touched"&&this.touched!==r&&this._onTouchedChanged(),e==="modelValue"&&(this.filled=!this._isEmpty()),e==="dirty"&&this.dirty!==r&&this._onDirtyChanged()}constructor(){super(),this.touched=!1,this.dirty=!1,this.prefilled=!1,this.filled=!1,this._leaveEvent="blur",this._valueChangedEvent="model-value-changed",this._iStateOnLeave=this._iStateOnLeave.bind(this),this._iStateOnValueChange=this._iStateOnValueChange.bind(this)}connectedCallback(){super.connectedCallback(),this.addEventListener(this._leaveEvent,this._iStateOnLeave),this.addEventListener(this._valueChangedEvent,this._iStateOnValueChange),this.initInteractionState()}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener(this._leaveEvent,this._iStateOnLeave),this.removeEventListener(this._valueChangedEvent,this._iStateOnValueChange)}initInteractionState(){this.dirty=!1,this.prefilled=!this._isEmpty()}_iStateOnLeave(){this.touched=!0,this.prefilled=!this._isEmpty()}_iStateOnValueChange(){this.dirty=!0}resetInteractionState(){this.touched=!1,this.submitted=!1,this.dirty=!1,this.prefilled=!this._isEmpty()}_onTouchedChanged(){this.dispatchEvent(new Event("touched-changed",{bubbles:!0,composed:!0}))}_onDirtyChanged(){this.dispatchEvent(new Event("dirty-changed",{bubbles:!0,composed:!0}))}_showFeedbackConditionFor(e,r){return r.touched&&r.dirty||r.prefilled||r.submitted}get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,submitted:this.submitted,touched:this.touched,dirty:this.dirty,filled:this.filled,prefilled:this.prefilled}}},VE=mt(fD),ff=window,Zb=new WeakMap;function pD(t){ff.applyFocusVisiblePolyfill&&!Zb.has(t)&&(ff.applyFocusVisiblePolyfill(t),Zb.set(t,void 0))}const mD=t=>class extends t{static get properties(){return{focused:{type:Boolean,reflect:!0},focusedVisible:{type:Boolean,reflect:!0,attribute:"focused-visible"},autofocus:{type:Boolean,reflect:!0}}}constructor(){super(),this.focused=!1,this.focusedVisible=!1,this.autofocus=!1}firstUpdated(e){super.firstUpdated(e),this.__registerEventsForFocusMixin(),this.__syncAutofocusToFocusableElement()}disconnectedCallback(){super.disconnectedCallback(),this.__teardownEventsForFocusMixin()}updated(e){super.updated(e),e.has("autofocus")&&this.__syncAutofocusToFocusableElement()}__syncAutofocusToFocusableElement(){this._focusableNode&&(this.hasAttribute("autofocus")?this._focusableNode.setAttribute("autofocus",""):this._focusableNode.removeAttribute("autofocus"))}focus(){this._focusableNode?.focus()}blur(){this._focusableNode?.blur()}get _focusableNode(){return this._inputNode||document.createElement("input")}__onFocus(){if(this.focused=!0,typeof ff.applyFocusVisiblePolyfill=="function")this.focusedVisible=this._focusableNode.hasAttribute("data-focus-visible-added");else try{this.focusedVisible=this._focusableNode.matches(":focus-visible")}catch{this.focusedVisible=!1}}__onBlur(){this.focused=!1,this.focusedVisible=!1}__registerEventsForFocusMixin(){pD(this.getRootNode()),this.__redispatchFocus=e=>{e.stopPropagation(),this.dispatchEvent(new Event("focus"))},this._focusableNode.addEventListener("focus",this.__redispatchFocus),this.__redispatchBlur=e=>{e.stopPropagation(),this.dispatchEvent(new Event("blur"))},this._focusableNode.addEventListener("blur",this.__redispatchBlur),this.__redispatchFocusin=e=>{e.stopPropagation(),this.__onFocus(),this.dispatchEvent(new Event("focusin",{bubbles:!0,composed:!0}))},this._focusableNode.addEventListener("focusin",this.__redispatchFocusin),this.__redispatchFocusout=e=>{e.stopPropagation(),this.__onBlur(),this.dispatchEvent(new Event("focusout",{bubbles:!0,composed:!0}))},this._focusableNode.addEventListener("focusout",this.__redispatchFocusout)}__teardownEventsForFocusMixin(){this._focusableNode&&(this._focusableNode?.removeEventListener("focus",this.__redispatchFocus),this._focusableNode?.removeEventListener("blur",this.__redispatchBlur),this._focusableNode?.removeEventListener("focusin",this.__redispatchFocusin),this._focusableNode?.removeEventListener("focusout",this.__redispatchFocusout))}},BE=mt(mD);let bu=class extends no(VE(BE(im(yu(Na(je)))))){firstUpdated(e){super.firstUpdated(e),this._initialModelValue=this.modelValue}connectedCallback(){super.connectedCallback(),this._onChange=this._onChange.bind(this),this._inputNode.addEventListener("change",this._onChange),this.classList.add("form-field")}disconnectedCallback(){super.disconnectedCallback(),this._inputNode?.removeEventListener("change",this._onChange)}resetInteractionState(){super.resetInteractionState(),this.submitted=!1}reset(){this.modelValue=this._initialModelValue,this.resetInteractionState()}clear(){this.modelValue=""}_onChange(e){this.dispatchEvent(new Event("user-input-changed",{bubbles:!0}))}get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,focused:this.focused}}get _focusableNode(){return this._inputNode}};const gD=t=>class extends im(BE(no(t))){static get properties(){return{autocomplete:{type:String,reflect:!0}}}constructor(){super(),this.autocomplete=void 0}get _inputNode(){return super._inputNode}get selectionStart(){const e=this._inputNode;return e&&e.selectionStart?e.selectionStart:0}set selectionStart(e){const r=this._inputNode;r&&r.selectionStart&&(r.selectionStart=e)}get selectionEnd(){const e=this._inputNode;return e&&e.selectionEnd?e.selectionEnd:0}set selectionEnd(e){const r=this._inputNode;r&&r.selectionEnd&&(r.selectionEnd=e)}get value(){return this._inputNode&&this._inputNode.value||this.__value||""}set value(e){this._inputNode?(this._inputNode.value!==e&&this._setValueAndPreserveCaret(e),this.__value=void 0):this.__value=e}_setValueAndPreserveCaret(e){if(this.focused)try{if(!(this._inputNode instanceof HTMLSelectElement)){const r=this._inputNode.selectionStart;this._inputNode.value=e,this._inputNode.selectionStart=r,this._inputNode.selectionEnd=r}}catch{this._inputNode.value=e}else this._inputNode.value=e}_reflectBackFormattedValueToUser(){if(super._reflectBackFormattedValueToUser(),this._reflectBackOn()&&this.focused)try{this._inputNode.selectionStart=this._inputNode.value.length}catch{}}get _focusableNode(){return this._inputNode}},UE=mt(gD);let om=class extends UE(bu){static get properties(){return{readOnly:{type:Boolean,attribute:"readonly",reflect:!0},type:{type:String,reflect:!0},placeholder:{type:String,reflect:!0}}}get slots(){return{...super.slots,input:()=>{const e=document.createElement("input"),r=this.getAttribute("value");return r&&e.setAttribute("value",r),e}}}get _inputNode(){return super._inputNode}constructor(){super(),this.readOnly=!1,this.type="text",this.placeholder=""}requestUpdate(e,r,n){super.requestUpdate(e,r,n),e==="readOnly"&&this.__delegateReadOnly()}firstUpdated(e){super.firstUpdated(e),this.__delegateReadOnly()}updated(e){super.updated(e),e.has("type")&&(this._inputNode.type=this.type),e.has("placeholder")&&(this._inputNode.placeholder=this.placeholder),e.has("disabled")&&(this._inputNode.disabled=this.disabled,this.validate()),e.has("name")&&(this._inputNode.name=this.name),e.has("autocomplete")&&(this._inputNode.autocomplete=this.autocomplete)}__delegateReadOnly(){this._inputNode&&(this._inputNode.readOnly=this.readOnly)}};var yD=Object.defineProperty,bD=(t,e,r,n)=>{for(var s=void 0,i=t.length-1,o;i>=0;i--)(o=t[i])&&(s=o(e,r,s)||s);return s&&yD(e,r,s),s};let jE=class extends om{constructor(){super(...arguments),this.size=""}static get styles(){return[...super.styles,nm,T4]}connectedCallback(){if(super.connectedCallback(),this._inputNode){const e=parseInt(this.size,10);e>0&&(this._inputNode.size=e)}}};bD([U({type:Number,reflect:!0})],jE.prototype,"size");customElements.get("craft-input")||customElements.define("craft-input",jE);var vD=class extends Event{constructor(){super("wa-load",{bubbles:!0,cancelable:!1,composed:!0})}},pf="";function wD(t){pf=t}function _D(){if(!pf){const t=document.querySelector("[data-fa-kit-code]");t&&wD(t.getAttribute("data-fa-kit-code")||"")}return pf}var cn="7.0.1";function ED(t,e,r){const n=_D(),s=n.length>0;let i="solid";return e==="notdog"?(r==="solid"&&(i="solid"),r==="duo-solid"&&(i="duo-solid"),`https://ka-p.fontawesome.com/releases/v${cn}/svgs/notdog-${i}/${t}.svg?token=${encodeURIComponent(n)}`):e==="chisel"?`https://ka-p.fontawesome.com/releases/v${cn}/svgs/chisel-regular/${t}.svg?token=${encodeURIComponent(n)}`:e==="etch"?`https://ka-p.fontawesome.com/releases/v${cn}/svgs/etch-solid/${t}.svg?token=${encodeURIComponent(n)}`:e==="jelly"?(r==="regular"&&(i="regular"),r==="duo-regular"&&(i="duo-regular"),r==="fill-regular"&&(i="fill-regular"),`https://ka-p.fontawesome.com/releases/v${cn}/svgs/jelly-${i}/${t}.svg?token=${encodeURIComponent(n)}`):e==="slab"?((r==="solid"||r==="regular")&&(i="regular"),r==="press-regular"&&(i="press-regular"),`https://ka-p.fontawesome.com/releases/v${cn}/svgs/slab-${i}/${t}.svg?token=${encodeURIComponent(n)}`):e==="thumbprint"?`https://ka-p.fontawesome.com/releases/v${cn}/svgs/thumbprint-light/${t}.svg?token=${encodeURIComponent(n)}`:e==="whiteboard"?`https://ka-p.fontawesome.com/releases/v${cn}/svgs/whiteboard-semibold/${t}.svg?token=${encodeURIComponent(n)}`:(e==="classic"&&(r==="thin"&&(i="thin"),r==="light"&&(i="light"),r==="regular"&&(i="regular"),r==="solid"&&(i="solid")),e==="sharp"&&(r==="thin"&&(i="sharp-thin"),r==="light"&&(i="sharp-light"),r==="regular"&&(i="sharp-regular"),r==="solid"&&(i="sharp-solid")),e==="duotone"&&(r==="thin"&&(i="duotone-thin"),r==="light"&&(i="duotone-light"),r==="regular"&&(i="duotone-regular"),r==="solid"&&(i="duotone")),e==="sharp-duotone"&&(r==="thin"&&(i="sharp-duotone-thin"),r==="light"&&(i="sharp-duotone-light"),r==="regular"&&(i="sharp-duotone-regular"),r==="solid"&&(i="sharp-duotone-solid")),e==="brands"&&(i="brands"),s?`https://ka-p.fontawesome.com/releases/v${cn}/svgs/${i}/${t}.svg?token=${encodeURIComponent(n)}`:`https://ka-f.fontawesome.com/releases/v${cn}/svgs/${i}/${t}.svg`)}var SD={name:"default",resolver:(t,e="classic",r="solid")=>ED(t,e,r),mutator:(t,e)=>{if(e?.family&&!t.hasAttribute("data-duotone-initialized")){const{family:r,variant:n}=e;if(r==="duotone"||r==="sharp-duotone"||r==="notdog"&&n==="duo-solid"||r==="jelly"&&n==="duo-regular"||r==="thumbprint"){const s=[...t.querySelectorAll("path")],i=s.find(a=>!a.hasAttribute("opacity")),o=s.find(a=>a.hasAttribute("opacity"));if(!i||!o)return;if(i.setAttribute("data-duotone-primary",""),o.setAttribute("data-duotone-secondary",""),e.swapOpacity&&i&&o){const a=o.getAttribute("opacity")||"0.4";i.style.setProperty("--path-opacity",a),o.style.setProperty("--path-opacity","1")}t.setAttribute("data-duotone-initialized","")}}}},xD=SD;function CD(t){return`data:image/svg+xml,${encodeURIComponent(t)}`}var th={solid:{check:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M434.8 70.1c14.3 10.4 17.5 30.4 7.1 44.7l-256 352c-5.5 7.6-14 12.3-23.4 13.1s-18.5-2.7-25.1-9.3l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l101.5 101.5 234-321.7c10.4-14.3 30.4-17.5 44.7-7.1z"/></svg>',"chevron-down":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M201.4 406.6c12.5 12.5 32.8 12.5 45.3 0l192-192c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L224 338.7 54.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l192 192z"/></svg>',"chevron-left":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l192 192c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L77.3 256 246.6 86.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-192 192z"/></svg>',"chevron-right":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M311.1 233.4c12.5 12.5 12.5 32.8 0 45.3l-192 192c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L243.2 256 73.9 86.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l192 192z"/></svg>',circle:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0z"/></svg>',eyedropper:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M341.6 29.2l-101.6 101.6-9.4-9.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3l-9.4-9.4 101.6-101.6c39-39 39-102.2 0-141.1s-102.2-39-141.1 0zM55.4 323.3c-15 15-23.4 35.4-23.4 56.6l0 42.4-26.6 39.9c-8.5 12.7-6.8 29.6 4 40.4s27.7 12.5 40.4 4l39.9-26.6 42.4 0c21.2 0 41.6-8.4 56.6-23.4l109.4-109.4-45.3-45.3-109.4 109.4c-3 3-7.1 4.7-11.3 4.7l-36.1 0 0-36.1c0-4.2 1.7-8.3 4.7-11.3l109.4-109.4-45.3-45.3-109.4 109.4z"/></svg>',"grip-vertical":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M128 40c0-22.1-17.9-40-40-40L40 0C17.9 0 0 17.9 0 40L0 88c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zm0 192c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zM0 424l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zM320 40c0-22.1-17.9-40-40-40L232 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zM192 232l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zM320 424c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48z"/></svg>',indeterminate:'<svg part="indeterminate-icon" class="icon" viewBox="0 0 16 16"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" stroke-linecap="round"><g stroke="currentColor" stroke-width="2"><g transform="translate(2.285714 6.857143)"><path d="M10.2857143,1.14285714 L1.14285714,1.14285714"/></g></g></g></svg>',minus:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M0 256c0-17.7 14.3-32 32-32l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 288c-17.7 0-32-14.3-32-32z"/></svg>',pause:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M48 32C21.5 32 0 53.5 0 80L0 432c0 26.5 21.5 48 48 48l64 0c26.5 0 48-21.5 48-48l0-352c0-26.5-21.5-48-48-48L48 32zm224 0c-26.5 0-48 21.5-48 48l0 352c0 26.5 21.5 48 48 48l64 0c26.5 0 48-21.5 48-48l0-352c0-26.5-21.5-48-48-48l-64 0z"/></svg>',play:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M91.2 36.9c-12.4-6.8-27.4-6.5-39.6 .7S32 57.9 32 72l0 368c0 14.1 7.5 27.2 19.6 34.4s27.2 7.5 39.6 .7l336-184c12.8-7 20.8-20.5 20.8-35.1s-8-28.1-20.8-35.1l-336-184z"/></svg>',star:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M309.5-18.9c-4.1-8-12.4-13.1-21.4-13.1s-17.3 5.1-21.4 13.1L193.1 125.3 33.2 150.7c-8.9 1.4-16.3 7.7-19.1 16.3s-.5 18 5.8 24.4l114.4 114.5-25.2 159.9c-1.4 8.9 2.3 17.9 9.6 23.2s16.9 6.1 25 2L288.1 417.6 432.4 491c8 4.1 17.7 3.3 25-2s11-14.2 9.6-23.2L441.7 305.9 556.1 191.4c6.4-6.4 8.6-15.8 5.8-24.4s-10.1-14.9-19.1-16.3L383 125.3 309.5-18.9z"/></svg>',user:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M224 248a120 120 0 1 0 0-240 120 120 0 1 0 0 240zm-29.7 56C95.8 304 16 383.8 16 482.3 16 498.7 29.3 512 45.7 512l356.6 0c16.4 0 29.7-13.3 29.7-29.7 0-98.5-79.8-178.3-178.3-178.3l-59.4 0z"/></svg>',xmark:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M55.1 73.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L147.2 256 9.9 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192.5 301.3 329.9 438.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.8 256 375.1 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192.5 210.7 55.1 73.4z"/></svg>'},regular:{"circle-question":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M464 256a208 208 0 1 0 -416 0 208 208 0 1 0 416 0zM0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0zm256-80c-17.7 0-32 14.3-32 32 0 13.3-10.7 24-24 24s-24-10.7-24-24c0-44.2 35.8-80 80-80s80 35.8 80 80c0 47.2-36 67.2-56 74.5l0 3.8c0 13.3-10.7 24-24 24s-24-10.7-24-24l0-8.1c0-20.5 14.8-35.2 30.1-40.2 6.4-2.1 13.2-5.5 18.2-10.3 4.3-4.2 7.7-10 7.7-19.6 0-17.7-14.3-32-32-32zM224 368a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>',"circle-xmark":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M256 48a208 208 0 1 1 0 416 208 208 0 1 1 0-416zm0 464a256 256 0 1 0 0-512 256 256 0 1 0 0 512zM167 167c-9.4 9.4-9.4 24.6 0 33.9l55 55-55 55c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l55-55 55 55c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-55-55 55-55c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-55 55-55-55c-9.4-9.4-24.6-9.4-33.9 0z"/></svg>',copy:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M384 336l-192 0c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l133.5 0c4.2 0 8.3 1.7 11.3 4.7l58.5 58.5c3 3 4.7 7.1 4.7 11.3L400 320c0 8.8-7.2 16-16 16zM192 384l192 0c35.3 0 64-28.7 64-64l0-197.5c0-17-6.7-33.3-18.7-45.3L370.7 18.7C358.7 6.7 342.5 0 325.5 0L192 0c-35.3 0-64 28.7-64 64l0 256c0 35.3 28.7 64 64 64zM64 128c-35.3 0-64 28.7-64 64L0 448c0 35.3 28.7 64 64 64l192 0c35.3 0 64-28.7 64-64l0-16-48 0 0 16c0 8.8-7.2 16-16 16L64 464c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l16 0 0-48-16 0z"/></svg>',eye:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M288 80C222.8 80 169.2 109.6 128.1 147.7 89.6 183.5 63 226 49.4 256 63 286 89.6 328.5 128.1 364.3 169.2 402.4 222.8 432 288 432s118.8-29.6 159.9-67.7C486.4 328.5 513 286 526.6 256 513 226 486.4 183.5 447.9 147.7 406.8 109.6 353.2 80 288 80zM95.4 112.6C142.5 68.8 207.2 32 288 32s145.5 36.8 192.6 80.6c46.8 43.5 78.1 95.4 93 131.1 3.3 7.9 3.3 16.7 0 24.6-14.9 35.7-46.2 87.7-93 131.1-47.1 43.7-111.8 80.6-192.6 80.6S142.5 443.2 95.4 399.4c-46.8-43.5-78.1-95.4-93-131.1-3.3-7.9-3.3-16.7 0-24.6 14.9-35.7 46.2-87.7 93-131.1zM288 336c44.2 0 80-35.8 80-80 0-29.6-16.1-55.5-40-69.3-1.4 59.7-49.6 107.9-109.3 109.3 13.8 23.9 39.7 40 69.3 40zm-79.6-88.4c2.5 .3 5 .4 7.6 .4 35.3 0 64-28.7 64-64 0-2.6-.2-5.1-.4-7.6-37.4 3.9-67.2 33.7-71.1 71.1zm45.6-115c10.8-3 22.2-4.5 33.9-4.5 8.8 0 17.5 .9 25.8 2.6 .3 .1 .5 .1 .8 .2 57.9 12.2 101.4 63.7 101.4 125.2 0 70.7-57.3 128-128 128-61.6 0-113-43.5-125.2-101.4-1.8-8.6-2.8-17.5-2.8-26.6 0-11 1.4-21.8 4-32 .2-.7 .3-1.3 .5-1.9 11.9-43.4 46.1-77.6 89.5-89.5z"/></svg>',"eye-slash":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M41-24.9c-9.4-9.4-24.6-9.4-33.9 0S-2.3-.3 7 9.1l528 528c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-96.4-96.4c2.7-2.4 5.4-4.8 8-7.2 46.8-43.5 78.1-95.4 93-131.1 3.3-7.9 3.3-16.7 0-24.6-14.9-35.7-46.2-87.7-93-131.1-47.1-43.7-111.8-80.6-192.6-80.6-56.8 0-105.6 18.2-146 44.2L41-24.9zM176.9 111.1c32.1-18.9 69.2-31.1 111.1-31.1 65.2 0 118.8 29.6 159.9 67.7 38.5 35.7 65.1 78.3 78.6 108.3-13.6 30-40.2 72.5-78.6 108.3-3.1 2.8-6.2 5.6-9.4 8.4L393.8 328c14-20.5 22.2-45.3 22.2-72 0-70.7-57.3-128-128-128-26.7 0-51.5 8.2-72 22.2l-39.1-39.1zm182 182l-108-108c11.1-5.8 23.7-9.1 37.1-9.1 44.2 0 80 35.8 80 80 0 13.4-3.3 26-9.1 37.1zM103.4 173.2l-34-34c-32.6 36.8-55 75.8-66.9 104.5-3.3 7.9-3.3 16.7 0 24.6 14.9 35.7 46.2 87.7 93 131.1 47.1 43.7 111.8 80.6 192.6 80.6 37.3 0 71.2-7.9 101.5-20.6L352.2 422c-20 6.4-41.4 10-64.2 10-65.2 0-118.8-29.6-159.9-67.7-38.5-35.7-65.1-78.3-78.6-108.3 10.4-23.1 28.6-53.6 54-82.8z"/></svg>',star:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M288.1-32c9 0 17.3 5.1 21.4 13.1L383 125.3 542.9 150.7c8.9 1.4 16.3 7.7 19.1 16.3s.5 18-5.8 24.4L441.7 305.9 467 465.8c1.4 8.9-2.3 17.9-9.6 23.2s-17 6.1-25 2L288.1 417.6 143.8 491c-8 4.1-17.7 3.3-25-2s-11-14.2-9.6-23.2L134.4 305.9 20 191.4c-6.4-6.4-8.6-15.8-5.8-24.4s10.1-14.9 19.1-16.3l159.9-25.4 73.6-144.2c4.1-8 12.4-13.1 21.4-13.1zm0 76.8L230.3 158c-3.5 6.8-10 11.6-17.6 12.8l-125.5 20 89.8 89.9c5.4 5.4 7.9 13.1 6.7 20.7l-19.8 125.5 113.3-57.6c6.8-3.5 14.9-3.5 21.8 0l113.3 57.6-19.8-125.5c-1.2-7.6 1.3-15.3 6.7-20.7l89.8-89.9-125.5-20c-7.6-1.2-14.1-6-17.6-12.8L288.1 44.8z"/></svg>'}},AD={name:"system",resolver:(t,e="classic",r="solid")=>{let n=th[r][t]??th.regular[t]??th.regular["circle-question"];return n?CD(n):""}},$D=AD,TD="classic",kD=[xD,$D],mf=[];function PD(t){mf.push(t)}function OD(t){mf=mf.filter(e=>e!==t)}function rh(t){return kD.find(e=>e.name===t)}function RD(){return TD}var ND=class extends Event{constructor(){super("wa-error",{bubbles:!0,cancelable:!1,composed:!0})}},ID=`:host {
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
`,go=Symbol(),ll=Symbol(),nh,sh=new Map,Bt=class extends ar{constructor(){super(...arguments),this.svg=null,this.autoWidth=!1,this.swapOpacity=!1,this.label="",this.library="default",this.resolveIcon=async(e,r)=>{let n;if(r?.spriteSheet){this.hasUpdated||await this.updateComplete,this.svg=Y`<svg part="svg">
        <use part="use" href="${e}"></use>
      </svg>`,await this.updateComplete;const s=this.shadowRoot.querySelector("[part='svg']");return typeof r.mutator=="function"&&r.mutator(s,this),this.svg}try{if(n=await fetch(e,{mode:"cors"}),!n.ok)return n.status===410?go:ll}catch{return ll}try{const s=document.createElement("div");s.innerHTML=await n.text();const i=s.firstElementChild;if(i?.tagName?.toLowerCase()!=="svg")return go;nh||(nh=new DOMParser);const o=nh.parseFromString(i.outerHTML,"text/html").body.querySelector("svg");return o?(o.part.add("svg"),document.adoptNode(o)):go}catch{return go}}}connectedCallback(){super.connectedCallback(),PD(this)}firstUpdated(e){super.firstUpdated(e),this.setIcon()}disconnectedCallback(){super.disconnectedCallback(),OD(this)}getIconSource(){const e=rh(this.library),r=this.family||RD();return this.name&&e?{url:e.resolver(this.name,r,this.variant,this.autoWidth),fromLibrary:!0}:{url:this.src,fromLibrary:!1}}handleLabelChange(){typeof this.label=="string"&&this.label.length>0?(this.setAttribute("role","img"),this.setAttribute("aria-label",this.label),this.removeAttribute("aria-hidden")):(this.removeAttribute("role"),this.removeAttribute("aria-label"),this.setAttribute("aria-hidden","true"))}async setIcon(){const{url:e,fromLibrary:r}=this.getIconSource(),n=r?rh(this.library):void 0;if(!e){this.svg=null;return}let s=sh.get(e);s||(s=this.resolveIcon(e,n),sh.set(e,s));const i=await s;if(i===ll&&sh.delete(e),e===this.getIconSource().url){if(RE(i)){this.svg=i;return}switch(i){case ll:case go:this.svg=null,this.dispatchEvent(new ND);break;default:this.svg=i.cloneNode(!0),n?.mutator?.(this.svg,this),this.dispatchEvent(new vD)}}}updated(e){super.updated(e);const r=rh(this.library),n=this.shadowRoot?.querySelector("svg");n&&r?.mutator?.(n,this)}render(){return this.hasUpdated?this.svg:Y`<svg part="svg" fill="currentColor" width="16" height="16"></svg>`}};Bt.css=ID;F([lr()],Bt.prototype,"svg",2);F([U({reflect:!0})],Bt.prototype,"name",2);F([U({reflect:!0})],Bt.prototype,"family",2);F([U({reflect:!0})],Bt.prototype,"variant",2);F([U({attribute:"auto-width",type:Boolean,reflect:!0})],Bt.prototype,"autoWidth",2);F([U({attribute:"swap-opacity",type:Boolean,reflect:!0})],Bt.prototype,"swapOpacity",2);F([U()],Bt.prototype,"src",2);F([U()],Bt.prototype,"label",2);F([U({reflect:!0})],Bt.prototype,"library",2);F([wr("label")],Bt.prototype,"handleLabelChange",1);F([wr(["family","name","library","variant","src","autoWidth","swapOpacity"])],Bt.prototype,"setIcon",1);Bt=F([qr("wa-icon")],Bt);const LD=ue``;let FD=class extends Bt{static get styles(){return[Bt.styles,LD]}};customElements.get("craft-icon")||customElements.define("craft-icon",FD);var MD=Object.defineProperty,DD=(t,e,r,n)=>{for(var s=void 0,i=t.length-1,o;i>=0;i--)(o=t[i])&&(s=o(e,r,s)||s);return s&&MD(e,r,s),s};let qE=class extends om{constructor(){super(),this._visible=!1,this.reveal=()=>{this._visible=!this._visible,this.type=this._visible?"text":"password"},this.renderSuffix=()=>Y`
      <craft-button
        type="button"
        icon
        size="small"
        variant="plain"
        @click="${this.reveal}"
        appearance="plain"
      >
        <span class="icon"
          >${this._visible?Y`<craft-icon name="eye-slash"></craft-icon>`:Y`<craft-icon name="eye"></craft-icon>`}
        </span>
      </craft-button>
    `,this.type="password"}static get styles(){return[...super.styles,nm,ue`
        .input-group__container {
          position: relative;
        }

        .input-group__suffix {
          position: absolute;
          inset-inline-end: var(--c-input-spacing-inline);
          inset-block-start: 50%;
          transform: translateY(calc(-50%));
        }
      `]}get slots(){return{...super.slots,suffix:()=>({template:this.renderSuffix()})}}};DD([lr()],qE.prototype,"_visible");customElements.get("craft-input-password")||customElements.define("craft-input-password",qE);const VD=ue`
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
`;var BD=Object.defineProperty,HE=(t,e,r,n)=>{for(var s=void 0,i=t.length-1,o;i>=0;i--)(o=t[i])&&(s=o(e,r,s)||s);return s&&BD(e,r,s),s};const zE=class extends je{constructor(){super(...arguments),this.size="",this.variant=""}render(){const e=!!this.querySelector('[slot="prefix"]'),r=!!this.querySelector('[slot="suffix"]');return Y`
      <div
        class="${en({chip:!0,"chip--small":this.size==="small","chip--medium":this.size==="medium","chip--large":this.size==="large","chip--plain":this.variant==="plain"})}"
      >
        ${e?Y`<div class="chip__prefix"><slot name="prefix"></slot></div>`:Ce}
        <div class="chip__body">
          <slot></slot>
        </div>
        ${r?Y`<div class="chip__suffix"><slot name="suffix"></slot></div>`:Ce}
      </div>
    `}};zE.styles=[VD];let am=zE;HE([U()],am.prototype,"size");HE([U()],am.prototype,"variant");customElements.get("craft-chip")||customElements.define("craft-chip",am);const UD=ue`
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
`;var jD=Object.defineProperty,WE=(t,e,r,n)=>{for(var s=void 0,i=t.length-1,o;i>=0;i--)(o=t[i])&&(s=o(e,r,s)||s);return s&&jD(e,r,s),s};const KE=class extends je{constructor(){super(...arguments),this.label=null,this.status=null}getLabel(){return!this.label&&this.status?`Status: ${this.status}`:this.label}render(){return Y`
      <span
        class="${en({status:!0,"status--live":this.status==="live","status--enabled":this.status==="enabled","status--pending":this.status==="pending","status--expired":this.status==="expired","status--disabled":this.status==="disabled"})}"
        role="img"
        aria-label="${this.getLabel()}"
      ></span>
    `}};KE.styles=[UD];let lm=KE;WE([U()],lm.prototype,"label");WE([U()],lm.prototype,"status");customElements.get("craft-status")||customElements.define("craft-status",lm);const qD=ue`
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
`;var Vo=new Map;function HD(t){var e=Vo.get(t);e&&e.destroy()}function zD(t){var e=Vo.get(t);e&&e.update()}var Eo=null;typeof window>"u"?((Eo=function(t){return t}).destroy=function(t){return t},Eo.update=function(t){return t}):((Eo=function(t,e){return t&&Array.prototype.forEach.call(t.length?t:[t],function(r){return(function(n){if(n&&n.nodeName&&n.nodeName==="TEXTAREA"&&!Vo.has(n)){var s,i=null,o=window.getComputedStyle(n),a=(s=n.value,function(){u({testForHeightReduction:s===""||!n.value.startsWith(s),restoreTextAlign:null}),s=n.value}),l=(function(d){n.removeEventListener("autosize:destroy",l),n.removeEventListener("autosize:update",c),n.removeEventListener("input",a),window.removeEventListener("resize",c),Object.keys(d).forEach(function(p){return n.style[p]=d[p]}),Vo.delete(n)}).bind(n,{height:n.style.height,resize:n.style.resize,textAlign:n.style.textAlign,overflowY:n.style.overflowY,overflowX:n.style.overflowX,wordWrap:n.style.wordWrap});n.addEventListener("autosize:destroy",l),n.addEventListener("autosize:update",c),n.addEventListener("input",a),window.addEventListener("resize",c),n.style.overflowX="hidden",n.style.wordWrap="break-word",Vo.set(n,{destroy:l,update:c}),c()}function u(d){var p,f,h=d.restoreTextAlign,m=h===void 0?null:h,g=d.testForHeightReduction,v=g===void 0||g,b=o.overflowY;if(n.scrollHeight!==0&&(o.resize==="vertical"?n.style.resize="none":o.resize==="both"&&(n.style.resize="horizontal"),v&&(p=(function(_){for(var S=[];_&&_.parentNode&&_.parentNode instanceof Element;)_.parentNode.scrollTop&&S.push([_.parentNode,_.parentNode.scrollTop]),_=_.parentNode;return function(){return S.forEach(function(k){var O=k[0],R=k[1];O.style.scrollBehavior="auto",O.scrollTop=R,O.style.scrollBehavior=null})}})(n),n.style.height=""),f=o.boxSizing==="content-box"?n.scrollHeight-(parseFloat(o.paddingTop)+parseFloat(o.paddingBottom)):n.scrollHeight+parseFloat(o.borderTopWidth)+parseFloat(o.borderBottomWidth),o.maxHeight!=="none"&&f>parseFloat(o.maxHeight)?(o.overflowY==="hidden"&&(n.style.overflow="scroll"),f=parseFloat(o.maxHeight)):o.overflowY!=="hidden"&&(n.style.overflow="hidden"),n.style.height=f+"px",m&&(n.style.textAlign=m),p&&p(),i!==f&&(n.dispatchEvent(new Event("autosize:resized",{bubbles:!0})),i=f),b!==o.overflow&&!m)){var w=o.textAlign;o.overflow==="hidden"&&(n.style.textAlign=w==="start"?"end":"start"),u({restoreTextAlign:w,testForHeightReduction:!0})}}function c(){u({testForHeightReduction:!0,restoreTextAlign:null})}})(r)}),t}).destroy=function(t){return t&&Array.prototype.forEach.call(t.length?t:[t],HD),t},Eo.update=function(t){return t&&Array.prototype.forEach.call(t.length?t:[t],zD),t});var ih=Eo;let WD=class extends bu{get _inputNode(){return Array.from(this.children).find(e=>e.slot==="input")}},KD=class extends UE(WD){static get properties(){return{maxRows:{type:Number,attribute:"max-rows"},rows:{type:Number,reflect:!0},readOnly:{type:Boolean,attribute:"readonly",reflect:!0},placeholder:{type:String,reflect:!0}}}get slots(){return{...super.slots,input:()=>{const e=document.createElement("textarea");return e.style.resize!==void 0&&(e.style.resize="none"),e}}}constructor(){super(),this.rows=2,this.maxRows=6,this.readOnly=!1,this.placeholder=""}connectedCallback(){super.connectedCallback(),this.__initializeAutoresize(),this.__intersectionObserver=new IntersectionObserver(()=>this.resizeTextarea()),this.__intersectionObserver.observe(this)}updated(e){if(super.updated(e),e.has("name")&&(this._inputNode.name=this.name),e.has("autocomplete")&&(this._inputNode.autocomplete=this.autocomplete),e.has("disabled")&&(this._inputNode.disabled=this.disabled,this.validate()),e.has("rows")){const r=this._inputNode;r&&(r.rows=this.rows)}if(e.has("readOnly")){const r=this._inputNode;r&&(r.readOnly=this.readOnly)}if(e.has("placeholder")){const r=this._inputNode;r&&(r.placeholder=this.placeholder)}e.has("modelValue")&&this.resizeTextarea(),(e.has("maxRows")||e.has("rows"))&&this.setTextareaMaxHeight()}disconnectedCallback(){super.disconnectedCallback(),ih.destroy(this._inputNode)}setTextareaMaxHeight(){const{value:e}=this._inputNode;this._inputNode.value="",this.resizeTextarea();const r=window.getComputedStyle(this._inputNode,null),n=parseFloat(r.lineHeight)||parseFloat(r.height)/this.rows,s=parseFloat(r.paddingTop)+parseFloat(r.paddingBottom),i=parseFloat(r.borderTopWidth)+parseFloat(r.borderBottomWidth),o=r.boxSizing==="border-box"?s+i:0;this._inputNode.style.maxHeight=`${n*this.maxRows+o}px`,this._inputNode.value=e,this.resizeTextarea()}static get styles(){return[...super.styles,ue`
        .input-group__container > .input-group__input ::slotted(.form-control) {
          box-sizing: content-box;
          overflow-x: hidden; /* for FF adds height to the TextArea to reserve place for scroll-bars */
        }

        /* Workaround https://bugzilla.mozilla.org/show_bug.cgi?id=1739079 */
        :host([disabled]) ::slotted(textarea) {
          user-select: none;
        }
      `]}get updateComplete(){return this.__textareaUpdateComplete?Promise.all([this.__textareaUpdateComplete,super.updateComplete]):super.updateComplete}resizeTextarea(){ih.update(this._inputNode)}__initializeAutoresize(){this.__shady_native_contains?this.__textareaUpdateComplete=this.__waitForTextareaRenderedInRealDOM().then(()=>{this.__startAutoresize(),this.__textareaUpdateComplete=null}):this.__startAutoresize()}async __waitForTextareaRenderedInRealDOM(){let e=3;for(;e!==0&&!this.__shady_native_contains(this._inputNode);)await new Promise(r=>setTimeout(r)),e-=1}__startAutoresize(){ih(this._inputNode),this.setTextareaMaxHeight()}},GD=class extends KD{static get styles(){return[...super.styles,nm,qD]}};customElements.get("craft-textarea")||customElements.define("craft-textarea",GD);const JD=ue`
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
`,GE=class extends je{render(){return Y`<slot></slot>`}};GE.styles=[JD];let XD=GE;customElements.get("craft-button-group")||customElements.define("craft-button-group",XD);const YD=ue`
  ${OE}

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
    ${PE}
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
`,QD=ue`
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
`,ZD=(t,e={})=>t.value!==e.value||t.checked!==e.checked,e3=t=>class extends im(t){static get properties(){return{checked:{type:Boolean,reflect:!0},disabled:{type:Boolean,reflect:!0},modelValue:{type:Object,hasChanged:ZD},choiceValue:{type:Object}}}get choiceValue(){return this.modelValue.value}set choiceValue(e){this.requestUpdate("choiceValue",this.choiceValue),this.modelValue.value!==e&&(this.modelValue={value:e,checked:this.modelValue.checked})}requestUpdate(e,r,n){super.requestUpdate(e,r,n),e==="modelValue"?this.modelValue.checked!==this.checked&&this.__syncModelCheckedToChecked(this.modelValue.checked):e==="checked"&&this.modelValue.checked!==this.checked&&this.__syncCheckedToModel(this.checked)}firstUpdated(e){super.firstUpdated(e),e.has("checked")&&this.__syncCheckedToInputElement()}updated(e){super.updated(e),e.has("modelValue")&&this.__syncCheckedToInputElement(),e.has("name")&&this._parentFormGroup&&this._parentFormGroup.name!==this.name&&this._syncNameToParentFormGroup()}constructor(){super(),this.modelValue={value:"",checked:!1},this.disabled=!1,this._preventDuplicateLabelClick=this._preventDuplicateLabelClick.bind(this),this._toggleChecked=this._toggleChecked.bind(this)}static get styles(){return[...super.styles||[],ue`
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
        `]}render(){return Y`
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
      `}_choiceGraphicTemplate(){return Ce}_afterTemplate(){return Ce}connectedCallback(){super.connectedCallback(),this._labelNode&&this._labelNode.addEventListener("click",this._preventDuplicateLabelClick),this.addEventListener("user-input-changed",this._toggleChecked)}disconnectedCallback(){super.disconnectedCallback(),this._labelNode&&this._labelNode.removeEventListener("click",this._preventDuplicateLabelClick),this.removeEventListener("user-input-changed",this._toggleChecked)}_preventDuplicateLabelClick(e){const r=(n=>{n.stopImmediatePropagation(),this._inputNode.removeEventListener("click",r)});this._inputNode.addEventListener("click",r)}_toggleChecked(e){this.disabled||(this._isHandlingUserInput=!0,this.checked=!this.checked,this._isHandlingUserInput=!1)}_syncNameToParentFormGroup(){this._parentFormGroup.tagName.includes(this.tagName)&&(this.name=this._parentFormGroup?.name||"")}__syncModelCheckedToChecked(e){this.checked=e}__syncCheckedToModel(e){this.modelValue={value:this.choiceValue,checked:e}}__syncCheckedToInputElement(){this._inputNode&&(this._inputNode.checked=this.checked)}_proxyInputEvent(){}_onModelValueChanged({modelValue:e},r){let n;r&&r.modelValue&&(n=r.modelValue),this.constructor.elementProperties.get("modelValue").hasChanged(e,n)&&super._onModelValueChanged({modelValue:e})}parser(){return this.modelValue}formatter(e){return e&&e.value!==void 0?e.value:e}clear(){this.checked=!1}_isEmpty(){return!this.checked}_syncValueUpwards(){}},cm=mt(e3);let ev=class extends Oa(cm(sm(Na(je)))){static get properties(){return{active:{type:Boolean,reflect:!0}}}static get styles(){return[ue`
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
      `]}get slots(){return{}}constructor(){super(),this.active=!1,this.__onClick=this.__onClick.bind(this),this.__registerEventListeners()}requestUpdate(e,r,n){super.requestUpdate(e,r,n),e==="active"&&this.active!==r&&this.dispatchEvent(new Event("active-changed",{bubbles:!0}))}updated(e){super.updated(e),e.has("checked")&&this.setAttribute("aria-selected",`${this.checked}`),e.has("disabled")&&this.setAttribute("aria-disabled",`${this.disabled}`)}render(){return Y`
      <div class="choice-field__label">
        <slot></slot>
      </div>
    `}connectedCallback(){super.connectedCallback(),this.setAttribute("role","option")}__registerEventListeners(){this.addEventListener("click",this.__onClick)}__unRegisterEventListeners(){this.removeEventListener("click",this.__onClick)}__onClick(){if(this.disabled)return;const e=this._parentFormGroup;this._isHandlingUserInput=!0,e&&e.multipleChoice?(this.checked=!this.checked,this.active=!this.active):(this.checked=!0,this.active=!0),this._isHandlingUserInput=!1}},t3=class extends ev{static get styles(){return[...ev.styles,QD]}};customElements.get("craft-option")||customElements.define("craft-option",t3);let r3=class extends bu{static get properties(){return{autocomplete:{type:String}}}constructor(){super(),this.autocomplete=void 0}get _inputNode(){return Array.from(this.children).find(e=>e.slot==="input")}},n3=class extends r3{get operationMode(){return"select"}connectedCallback(){super.connectedCallback(),this._inputNode.addEventListener("change",this._proxyChangeEvent),this.__selectObserver=new MutationObserver(()=>{this._syncValueUpwards(),this._calculateValues({source:"model"})}),this.__selectObserver.observe(this._inputNode,{attributes:!0,childList:!0,subtree:!0})}updated(e){super.updated(e),e.has("disabled")&&(this._inputNode.disabled=this.disabled,this.validate()),e.has("name")&&(this._inputNode.name=this.name),e.has("autocomplete")&&(this._inputNode.autocomplete=this.autocomplete)}disconnectedCallback(){super.disconnectedCallback(),this._inputNode.removeEventListener("change",this._proxyChangeEvent),this.__selectObserver?.disconnect()}formatter(e){const r=Array.from(this._inputNode.options).find(n=>n.value===e);return r?r.text:""}_reflectBackFormattedValueToUser(){this._reflectBackOn()&&(this.value=typeof this.modelValue<"u"?this.modelValue:"")}_proxyChangeEvent(){this.dispatchEvent(new CustomEvent("user-input-changed",{bubbles:!0,composed:!0}))}},s3=class extends n3{static get styles(){return[...super.styles,YD]}_inputGroupInputTemplate(){return Y`
      <div class="input-group__input">
        <slot name="input"></slot>
        <craft-icon
          class="indicator"
          name="chevron-down"
          style="font-size: 0.8em"
        ></craft-icon>
      </div>
    `}};customElements.get("craft-select")||customElements.define("craft-select",s3);var JE=`@layer wa-utilities {
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
`,vu=class{constructor(e,...r){this.slotNames=[],this.handleSlotChange=n=>{const s=n.target;(this.slotNames.includes("[default]")&&!s.name||s.name&&this.slotNames.includes(s.name))&&this.host.requestUpdate()},(this.host=e).addController(this),this.slotNames=r}hasDefaultSlot(){return[...this.host.childNodes].some(e=>{if(e.nodeType===Node.TEXT_NODE&&e.textContent.trim()!=="")return!0;if(e.nodeType===Node.ELEMENT_NODE){const r=e;if(r.tagName.toLowerCase()==="wa-visually-hidden")return!1;if(!r.hasAttribute("slot"))return!0}return!1})}hasNamedSlot(e){return this.host.querySelector(`:scope > [slot="${e}"]`)!==null}test(e){return e==="[default]"?this.hasDefaultSlot():this.hasNamedSlot(e)}hostConnected(){this.host.shadowRoot.addEventListener("slotchange",this.handleSlotChange)}hostDisconnected(){this.host.shadowRoot.removeEventListener("slotchange",this.handleSlotChange)}},i3=class extends Event{constructor(e){super("wa-select",{bubbles:!0,cancelable:!0,composed:!0}),this.detail=e}};function*XE(t=document.activeElement){t!=null&&(yield t,"shadowRoot"in t&&t.shadowRoot&&t.shadowRoot.mode!=="closed"&&(yield*XE(t.shadowRoot.activeElement)))}var o3=`:host {
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
`,oh=new Set,Vt=class extends ar{constructor(){super(...arguments),this.submenuCleanups=new Map,this.localize=new Zi(this),this.userTypedQuery="",this.openSubmenuStack=[],this.open=!1,this.size="medium",this.placement="bottom-start",this.distance=0,this.skidding=0,this.handleDocumentKeyDown=async e=>{const r=this.localize.dir()==="rtl";if(e.key==="Escape"){const d=this.getTrigger();e.preventDefault(),e.stopPropagation(),this.open=!1,d?.focus();return}const n=[...XE()].find(d=>d.localName==="wa-dropdown-item"),s=n?.localName==="wa-dropdown-item",i=this.getCurrentSubmenuItem(),o=!!i;let a,l,u;o?(a=this.getSubmenuItems(i),l=a.find(d=>d.active||d===n),u=l?a.indexOf(l):-1):(a=this.getItems(),l=a.find(d=>d.active||d===n),u=l?a.indexOf(l):-1);let c;if(e.key==="ArrowUp"&&(e.preventDefault(),e.stopPropagation(),u>0?c=a[u-1]:c=a[a.length-1]),e.key==="ArrowDown"&&(e.preventDefault(),e.stopPropagation(),u!==-1&&u<a.length-1?c=a[u+1]:c=a[0]),e.key===(r?"ArrowLeft":"ArrowRight")&&s&&l&&l.hasSubmenu){e.preventDefault(),e.stopPropagation(),l.submenuOpen=!0,this.addToSubmenuStack(l),setTimeout(()=>{const d=this.getSubmenuItems(l);d.length>0&&(d.forEach((p,f)=>p.active=f===0),d[0].focus())},0);return}if(e.key===(r?"ArrowRight":"ArrowLeft")&&o){e.preventDefault(),e.stopPropagation();const d=this.removeFromSubmenuStack();d&&(d.submenuOpen=!1,setTimeout(()=>{d.focus(),d.active=!0,(d.slot==="submenu"?this.getSubmenuItems(d.parentElement):this.getItems()).forEach(p=>{p!==d&&(p.active=!1)})},0));return}if((e.key==="Home"||e.key==="End")&&(e.preventDefault(),e.stopPropagation(),c=e.key==="Home"?a[0]:a[a.length-1]),e.key==="Tab"&&await this.hideMenu(),e.key.length===1&&!(e.metaKey||e.ctrlKey||e.altKey)&&!(e.key===" "&&this.userTypedQuery==="")&&(clearTimeout(this.userTypedTimeout),this.userTypedTimeout=setTimeout(()=>{this.userTypedQuery=""},1e3),this.userTypedQuery+=e.key,a.some(d=>{const p=(d.textContent||"").trim().toLowerCase(),f=this.userTypedQuery.trim().toLowerCase();return p.startsWith(f)?(c=d,!0):!1})),c){e.preventDefault(),e.stopPropagation(),a.forEach(d=>d.active=d===c),c.focus();return}(e.key==="Enter"||e.key===" "&&this.userTypedQuery==="")&&s&&l&&(e.preventDefault(),e.stopPropagation(),l.hasSubmenu?(l.submenuOpen=!0,this.addToSubmenuStack(l),setTimeout(()=>{const d=this.getSubmenuItems(l);d.length>0&&(d.forEach((p,f)=>p.active=f===0),d[0].focus())},0)):this.makeSelection(l))},this.handleDocumentPointerDown=e=>{e.composedPath().some(r=>r instanceof HTMLElement?r===this||r.closest('wa-dropdown, [part="submenu"]'):!1)||(this.open=!1)},this.handleGlobalMouseMove=e=>{const r=this.getCurrentSubmenuItem();if(!r?.submenuOpen||!r.submenuElement)return;const n=r.submenuElement.getBoundingClientRect(),s=this.localize.dir()==="rtl",i=s?n.right:n.left,o=s?Math.max(e.clientX,i):Math.min(e.clientX,i),a=Math.max(n.top,Math.min(e.clientY,n.bottom));r.submenuElement.style.setProperty("--safe-triangle-cursor-x",`${o}px`),r.submenuElement.style.setProperty("--safe-triangle-cursor-y",`${a}px`);const l=r.matches(":hover"),u=r.submenuElement?.matches(":hover")||!!e.composedPath().find(c=>c instanceof HTMLElement&&c.closest('[part="submenu"]')===r.submenuElement);!l&&!u&&setTimeout(()=>{!r.matches(":hover")&&!r.submenuElement?.matches(":hover")&&(r.submenuOpen=!1)},100)}}disconnectedCallback(){super.disconnectedCallback(),clearInterval(this.userTypedTimeout),this.closeAllSubmenus(),this.submenuCleanups.forEach(e=>e()),this.submenuCleanups.clear(),document.removeEventListener("mousemove",this.handleGlobalMouseMove)}firstUpdated(){this.syncAriaAttributes()}async updated(e){e.has("open")&&(this.customStates.set("open",this.open),this.open?await this.showMenu():(this.closeAllSubmenus(),await this.hideMenu())),e.has("size")&&this.syncItemSizes()}getItems(e=!1){const r=this.defaultSlot.assignedElements({flatten:!0}).filter(n=>n.localName==="wa-dropdown-item");return e?r:r.filter(n=>!n.disabled)}getSubmenuItems(e,r=!1){const n=e.shadowRoot?.querySelector('slot[name="submenu"]')||e.querySelector('slot[name="submenu"]');if(!n)return[];const s=n.assignedElements({flatten:!0}).filter(i=>i.localName==="wa-dropdown-item");return r?s:s.filter(i=>!i.disabled)}syncItemSizes(){this.defaultSlot.assignedElements({flatten:!0}).filter(e=>e.localName==="wa-dropdown-item").forEach(e=>e.size=this.size)}addToSubmenuStack(e){const r=this.openSubmenuStack.indexOf(e);r!==-1?this.openSubmenuStack=this.openSubmenuStack.slice(0,r+1):this.openSubmenuStack.push(e)}removeFromSubmenuStack(){return this.openSubmenuStack.pop()}getCurrentSubmenuItem(){return this.openSubmenuStack.length>0?this.openSubmenuStack[this.openSubmenuStack.length-1]:void 0}closeAllSubmenus(){this.getItems(!0).forEach(e=>{e.submenuOpen=!1}),this.openSubmenuStack=[]}closeSiblingSubmenus(e){const r=e.closest('wa-dropdown-item:not([slot="submenu"])');let n;r?n=this.getSubmenuItems(r,!0):n=this.getItems(!0),n.forEach(s=>{s!==e&&s.submenuOpen&&(s.submenuOpen=!1)}),this.openSubmenuStack.includes(e)||this.openSubmenuStack.push(e)}getTrigger(){return this.querySelector('[slot="trigger"]')}async showMenu(){if(!this.getTrigger())return;const e=new Ta;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}oh.forEach(n=>n.open=!1),this.popup.active=!0,this.open=!0,oh.add(this),this.syncAriaAttributes(),document.addEventListener("keydown",this.handleDocumentKeyDown),document.addEventListener("pointerdown",this.handleDocumentPointerDown),document.addEventListener("mousemove",this.handleGlobalMouseMove),this.menu.classList.remove("hide"),await Mt(this.menu,"show");const r=this.getItems();r.length>0&&(r.forEach((n,s)=>n.active=s===0),r[0].focus()),this.dispatchEvent(new Aa)}async hideMenu(){const e=new $a({source:this});if(this.dispatchEvent(e),e.defaultPrevented){this.open=!0;return}this.open=!1,oh.delete(this),this.syncAriaAttributes(),document.removeEventListener("keydown",this.handleDocumentKeyDown),document.removeEventListener("pointerdown",this.handleDocumentPointerDown),document.removeEventListener("mousemove",this.handleGlobalMouseMove),this.menu.classList.remove("show"),await Mt(this.menu,"hide"),this.popup.active=this.open,this.dispatchEvent(new Ca)}handleMenuClick(e){const r=e.target.closest("wa-dropdown-item");if(!(!r||r.disabled)){if(r.hasSubmenu){r.submenuOpen||(this.closeSiblingSubmenus(r),this.addToSubmenuStack(r),r.submenuOpen=!0),e.stopPropagation();return}this.makeSelection(r)}}async handleMenuSlotChange(){const e=this.getItems(!0);await Promise.all(e.map(s=>s.updateComplete)),this.syncItemSizes();const r=e.some(s=>s.type==="checkbox"),n=e.some(s=>s.hasSubmenu);e.forEach((s,i)=>{s.active=i===0,s.checkboxAdjacent=r,s.submenuAdjacent=n})}handleTriggerClick(){this.open=!this.open}handleSubmenuOpening(e){const r=e.detail.item;this.closeSiblingSubmenus(r),this.addToSubmenuStack(r),this.setupSubmenuPosition(r),this.processSubmenuItems(r)}setupSubmenuPosition(e){if(!e.submenuElement)return;this.cleanupSubmenuPosition(e);const r=pE(e,e.submenuElement,()=>{this.positionSubmenu(e),this.updateSafeTriangleCoordinates(e)});this.submenuCleanups.set(e,r);const n=e.submenuElement.querySelector('slot[name="submenu"]');n&&(n.removeEventListener("slotchange",Vt.handleSubmenuSlotChange),n.addEventListener("slotchange",Vt.handleSubmenuSlotChange),Vt.handleSubmenuSlotChange({target:n}))}static handleSubmenuSlotChange(e){const r=e.target;if(!r)return;const n=r.assignedElements().filter(o=>o.localName==="wa-dropdown-item");if(n.length===0)return;const s=n.some(o=>o.hasSubmenu),i=n.some(o=>o.type==="checkbox");n.forEach(o=>{o.submenuAdjacent=s,o.checkboxAdjacent=i})}processSubmenuItems(e){if(!e.submenuElement)return;const r=this.getSubmenuItems(e,!0),n=r.some(s=>s.hasSubmenu);r.forEach(s=>{s.submenuAdjacent=n})}cleanupSubmenuPosition(e){const r=this.submenuCleanups.get(e);r&&(r(),this.submenuCleanups.delete(e))}positionSubmenu(e){if(!e.submenuElement)return;const r=this.localize.dir()==="rtl"?"left-start":"right-start";bE(e,e.submenuElement,{placement:r,middleware:[mE({mainAxis:0,crossAxis:-5}),yE({fallbackStrategy:"bestFit"}),gE({padding:8})]}).then(({x:n,y:s,placement:i})=>{e.submenuElement.setAttribute("data-placement",i),Object.assign(e.submenuElement.style,{left:`${n}px`,top:`${s}px`})})}updateSafeTriangleCoordinates(e){if(!e.submenuElement||!e.submenuOpen)return;if(document.activeElement?.matches(":focus-visible")){e.submenuElement.style.setProperty("--safe-triangle-visible","none");return}e.submenuElement.style.setProperty("--safe-triangle-visible","block");const r=e.submenuElement.getBoundingClientRect(),n=this.localize.dir()==="rtl";e.submenuElement.style.setProperty("--safe-triangle-submenu-start-x",`${n?r.right:r.left}px`),e.submenuElement.style.setProperty("--safe-triangle-submenu-start-y",`${r.top}px`),e.submenuElement.style.setProperty("--safe-triangle-submenu-end-x",`${n?r.right:r.left}px`),e.submenuElement.style.setProperty("--safe-triangle-submenu-end-y",`${r.bottom}px`)}makeSelection(e){const r=this.getTrigger();if(e.disabled)return;e.type==="checkbox"&&(e.checked=!e.checked);const n=new i3({item:e});this.dispatchEvent(n),n.defaultPrevented||(this.open=!1,r?.focus())}async syncAriaAttributes(){const e=this.getTrigger();let r;e&&(e.localName==="wa-button"?(await customElements.whenDefined("wa-button"),await e.updateComplete,r=e.shadowRoot.querySelector('[part="base"]')):r=e,r.hasAttribute("id")||r.setAttribute("id",em("wa-dropdown-trigger-")),r.setAttribute("aria-haspopup","menu"),r.setAttribute("aria-expanded",this.open?"true":"false"),this.menu.setAttribute("aria-expanded","false"))}render(){let e=this.hasUpdated?this.popup.active:this.open;return Y`
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
    `}};Vt.css=[JE,o3];F([Qe("slot:not([name])")],Vt.prototype,"defaultSlot",2);F([Qe("#menu")],Vt.prototype,"menu",2);F([Qe("wa-popup")],Vt.prototype,"popup",2);F([U({type:Boolean,reflect:!0})],Vt.prototype,"open",2);F([U({reflect:!0})],Vt.prototype,"size",2);F([U({reflect:!0})],Vt.prototype,"placement",2);F([U({type:Number})],Vt.prototype,"distance",2);F([U({type:Number})],Vt.prototype,"skidding",2);Vt=F([qr("wa-dropdown")],Vt);var a3=`:host {
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
`,kt=class extends ar{constructor(){super(...arguments),this.hasSlotController=new vu(this,"[default]","start","end"),this.active=!1,this.variant="default",this.size="medium",this.checkboxAdjacent=!1,this.submenuAdjacent=!1,this.type="normal",this.checked=!1,this.disabled=!1,this.submenuOpen=!1,this.hasSubmenu=!1,this.handleSlotChange=()=>{this.hasSubmenu=this.hasSlotController.test("submenu"),this.updateHasSubmenuState(),this.hasSubmenu?(this.setAttribute("aria-haspopup","menu"),this.setAttribute("aria-expanded",this.submenuOpen?"true":"false")):(this.removeAttribute("aria-haspopup"),this.removeAttribute("aria-expanded"))}}connectedCallback(){super.connectedCallback(),this.addEventListener("mouseenter",this.handleMouseEnter.bind(this)),this.shadowRoot.addEventListener("slotchange",this.handleSlotChange)}disconnectedCallback(){super.disconnectedCallback(),this.closeSubmenu(),this.removeEventListener("mouseenter",this.handleMouseEnter),this.shadowRoot.removeEventListener("slotchange",this.handleSlotChange)}firstUpdated(){this.setAttribute("tabindex","-1"),this.hasSubmenu=this.hasSlotController.test("submenu"),this.updateHasSubmenuState()}updated(e){e.has("active")&&(this.setAttribute("tabindex",this.active?"0":"-1"),this.customStates.set("active",this.active)),e.has("checked")&&(this.setAttribute("aria-checked",this.checked?"true":"false"),this.customStates.set("checked",this.checked)),e.has("disabled")&&(this.setAttribute("aria-disabled",this.disabled?"true":"false"),this.customStates.set("disabled",this.disabled)),e.has("type")&&(this.type==="checkbox"?this.setAttribute("role","menuitemcheckbox"):this.setAttribute("role","menuitem")),e.has("submenuOpen")&&(this.customStates.set("submenu-open",this.submenuOpen),this.submenuOpen?this.openSubmenu():this.closeSubmenu())}updateHasSubmenuState(){this.customStates.set("has-submenu",this.hasSubmenu)}async openSubmenu(){!this.hasSubmenu||!this.submenuElement||(this.notifyParentOfOpening(),this.submenuElement.showPopover(),this.submenuElement.hidden=!1,this.submenuElement.setAttribute("data-visible",""),this.submenuOpen=!0,this.setAttribute("aria-expanded","true"),await Mt(this.submenuElement,"show"),setTimeout(()=>{const e=this.getSubmenuItems();e.length>0&&(e.forEach((r,n)=>r.active=n===0),e[0].focus())},0))}notifyParentOfOpening(){const e=new CustomEvent("submenu-opening",{bubbles:!0,composed:!0,detail:{item:this}});this.dispatchEvent(e);const r=this.parentElement;r&&[...r.children].filter(n=>n!==this&&n.localName==="wa-dropdown-item"&&n.getAttribute("slot")===this.getAttribute("slot")&&n.submenuOpen).forEach(n=>{n.submenuOpen=!1})}async closeSubmenu(){!this.hasSubmenu||!this.submenuElement||(this.submenuOpen=!1,this.setAttribute("aria-expanded","false"),this.submenuElement.hidden||(await Mt(this.submenuElement,"hide"),this.submenuElement.hidden=!0,this.submenuElement.removeAttribute("data-visible"),this.submenuElement.hidePopover()))}getSubmenuItems(){return[...this.children].filter(e=>e.localName==="wa-dropdown-item"&&e.getAttribute("slot")==="submenu"&&!e.hasAttribute("disabled"))}handleMouseEnter(){this.hasSubmenu&&!this.disabled&&(this.notifyParentOfOpening(),this.submenuOpen=!0)}render(){return Y`
      ${this.type==="checkbox"?Y`
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

      ${this.hasSubmenu?Y`
            <wa-icon
              id="submenu-indicator"
              part="submenu-icon"
              exportparts="svg:submenu-icon__svg"
              library="system"
              name="chevron-right"
            ></wa-icon>
          `:""}
      ${this.hasSubmenu?Y`
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
    `}};kt.css=a3;F([Qe("#submenu")],kt.prototype,"submenuElement",2);F([U({type:Boolean})],kt.prototype,"active",2);F([U({reflect:!0})],kt.prototype,"variant",2);F([U({reflect:!0})],kt.prototype,"size",2);F([U({attribute:"checkbox-adjacent",type:Boolean,reflect:!0})],kt.prototype,"checkboxAdjacent",2);F([U({attribute:"submenu-adjacent",type:Boolean,reflect:!0})],kt.prototype,"submenuAdjacent",2);F([U()],kt.prototype,"value",2);F([U({reflect:!0})],kt.prototype,"type",2);F([U({type:Boolean})],kt.prototype,"checked",2);F([U({type:Boolean,reflect:!0})],kt.prototype,"disabled",2);F([U({type:Boolean,reflect:!0})],kt.prototype,"submenuOpen",2);F([lr()],kt.prototype,"hasSubmenu",2);kt=F([qr("wa-dropdown-item")],kt);let l3=class extends Vt{static get styles(){return[Vt.styles,ue`
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
      `]}},c3=class extends kt{static get styles(){return[kt.styles,ue`
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
      `]}};customElements.get("craft-dropdown")||customElements.define("craft-dropdown",l3);customElements.get("craft-dropdown-item")||customElements.define("craft-dropdown-item",c3);const u3=ue`
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
`;function d3({el:t,uid:e}){t.setAttribute("id",`panel-${e}`),t.setAttribute("role","tabpanel"),t.setAttribute("aria-labelledby",`button-${e}`),t.hasAttribute("tabindex")||t.setAttribute("tabindex","0")}function h3(t){t.setAttribute("selected","true")}function tv(t){t.removeAttribute("selected")}function f3({el:t,uid:e,clickHandler:r,keydownHandler:n,keyupHandler:s}){t.setAttribute("id",`button-${e}`),t.setAttribute("role","tab"),t.setAttribute("aria-controls",`panel-${e}`),t.addEventListener("click",r),t.addEventListener("keyup",s),t.addEventListener("keydown",n)}function p3({el:t,clickHandler:e,keydownHandler:r,keyupHandler:n}){t.removeAttribute("id"),t.removeAttribute("role"),t.removeAttribute("aria-controls"),t.removeEventListener("click",e),t.removeEventListener("keyup",n),t.removeEventListener("keydown",r)}function m3(t,e=!1){e&&t.focus(),t.setAttribute("selected","true"),t.setAttribute("aria-selected","true"),t.setAttribute("tabindex","0")}function rv(t){t.removeAttribute("selected"),t.setAttribute("aria-selected","false"),t.setAttribute("tabindex","-1")}function g3(t){const e=t;switch(e.key){case"ArrowDown":case"ArrowRight":case"ArrowUp":case"ArrowLeft":case"Home":case"End":e.preventDefault()}}let y3=class extends je{static get properties(){return{selectedIndex:{type:Number,attribute:"selected-index",reflect:!0}}}static get styles(){return[ue`
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
      `]}render(){return Y`
      <div class="tabs__tab-group" role="tablist">
        <slot name="tab"></slot>
      </div>
      <div class="tabs__panels">
        <slot name="panel"></slot>
      </div>
    `}constructor(){super(),this.selectedIndex=0}firstUpdated(e){super.firstUpdated(e),this.__setupSlots(),this.tabs[0]?.disabled&&(this.selectedIndex=this.tabs.findIndex(r=>!r.disabled))}get tabs(){return Array.from(this.children).filter(e=>e.slot==="tab")}get panels(){return Array.from(this.children).filter(e=>e.slot==="panel")}static enabledWarnings=super.enabledWarnings?.filter(e=>e!=="change-in-update")||[];__setupSlots(){if(this.shadowRoot){const e=this.shadowRoot.querySelector("slot[name=tab]"),r=()=>{this.__cleanStore(),this.__setupStore(),this.__updateSelected(!1)};e&&e.addEventListener("slotchange",r)}}__setupStore(){this.__store=[],this.tabs.length!==this.panels.length&&console.warn(`The amount of tabs (${this.tabs.length}) doesn't match the amount of panels (${this.panels.length}).`),this.tabs.forEach((e,r)=>{const n=NE(),s=this.panels[r],i={uid:n,el:e,button:e,panel:s,clickHandler:this.__createButtonClickHandler(r),keydownHandler:g3.bind(this),keyupHandler:this.__handleButtonKeyup.bind(this)};d3({...i,el:i.panel}),f3(i),tv(i.panel),rv(i.button),this.__store&&this.__store.push(i)})}__cleanStore(){this.__store&&(this.__store.forEach(e=>{p3(e)}),this.__store=[])}__getNextNotDisabledTab(e,r,n){let s=[];const i=e.filter((a,l)=>!a.disabled&&l>this.selectedIndex),o=e.filter((a,l)=>!a.disabled&&l<this.selectedIndex);return n==="right"?s=[...i,...o]:s=[...o.reverse(),...i.reverse()],s[0]}__getNextAvailableIndex(e,r){const n=this.tabs[this.selectedIndex];if(this.tabs.every(s=>!s.disabled))return e;if(r==="ArrowRight"||r==="ArrowDown"){const s=this.__getNextNotDisabledTab(this.tabs,n,"right");return this.tabs.findIndex(i=>s===i)}if(r==="ArrowLeft"||r==="ArrowUp"){const s=this.__getNextNotDisabledTab(this.tabs,n,"left");return this.tabs.findIndex(i=>s===i)}if(r==="Home")return this.tabs.findIndex(s=>!s.disabled);if(r==="End"){const s=this.tabs.map((i,o)=>({disabled:i.disabled,index:o})).filter(i=>!i.disabled);return s[s.length-1].index}return-1}__createButtonClickHandler(e){return()=>{this._setSelectedIndexWithFocus(e)}}__handleButtonKeyup(e){const r=e;if(typeof this.selectedIndex=="number")switch(r.key){case"ArrowDown":case"ArrowRight":this.selectedIndex+1>=this._pairCount?this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(0,r.key)):this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this.selectedIndex+1,r.key));break;case"ArrowUp":case"ArrowLeft":this.selectedIndex<=0?this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this._pairCount-1,r.key)):this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this.selectedIndex-1,r.key));break;case"Home":this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(0,r.key));break;case"End":this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this._pairCount-1,r.key));break}}get selectedIndex(){return this.__selectedIndex||0}set selectedIndex(e){if(e===this.__selectedIndex)return;const r=this.__selectedIndex;this.__selectedIndex=e,this.__updateSelected(!1),this.dispatchEvent(new Event("selected-changed")),this.requestUpdate("selectedIndex",r)}_setSelectedIndexWithFocus(e){if(e===-1)return;const r=this.__selectedIndex;this.__selectedIndex=e,this.__updateSelected(!0),this.dispatchEvent(new Event("selected-changed")),this.requestUpdate("selectedIndex",r)}get _pairCount(){return this.__store&&this.__store.length||0}__updateSelected(e=!1){if(!(this.__store&&typeof this.selectedIndex=="number"&&this.__store[this.selectedIndex]))return;const r=this.tabs.find(o=>o.hasAttribute("selected")),n=this.panels.find(o=>o.hasAttribute("selected"));r&&rv(r),n&&tv(n);const{button:s,panel:i}=this.__store[this.selectedIndex];s&&m3(s,e),i&&h3(i)}},b3=class extends y3{static get styles(){return[...super.styles,u3]}};customElements.get("craft-tabs")||customElements.define("craft-tabs",b3);const v3=ue`
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
`;var w3=Object.defineProperty,_3=(t,e,r,n)=>{for(var s=void 0,i=t.length-1,o;i>=0;i--)(o=t[i])&&(s=o(e,r,s)||s);return s&&w3(e,r,s),s};const YE=class extends je{constructor(){super(...arguments),this.label=""}render(){const e=!!this.label||!!this.querySelector('[slot="header"]')||!!this.querySelector('[slot="label"]')||!!this.querySelector('[slot="actions"]'),r=!!this.querySelector('[slot="footer"]');return Y`
      <div class="card">
        <div>
          ${e?Y`<div class="card__header">
                <slot name="header">
                  <slot name="label" class="card__label" part="label"
                    >${this.label}</slot
                  >
                  <slot name="actions"></slot>
                </slot>
              </div>`:Ce}

          <div class="card__body">
            <slot></slot>
          </div>

          ${r?Y`<div class="card__footer"><slot name="footer"></slot></div>`:Ce}
        </div>
      </div>
    `}};YE.styles=[v3];let QE=YE;_3([U()],QE.prototype,"label");customElements.get("craft-card")||customElements.define("craft-card",QE);const E3=ue`
  :host {
    display: inline-flex;
  }
`,ZE=class extends je{render(){return Y`<slot></slot> `}};ZE.styles=[E3];let S3=ZE;customElements.get("craft-tab")||customElements.define("craft-tab",S3);let eS=class extends $E(je){static get properties(){return{checked:{type:Boolean,reflect:!0}}}static get styles(){return[ue`
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
      `]}render(){return Y`
      <div class="btn">
        <div class="switch-button__track"></div>
        <div class="switch-button__thumb"></div>
      </div>
    `}constructor(){super(),this.value="",this.checked=!1,this.__initialized=!1,this._toggleChecked=this._toggleChecked.bind(this),this.__handleKeydown=this._handleKeydown.bind(this),this.__handleKeyup=this._handleKeyup.bind(this)}connectedCallback(){super.connectedCallback(),this.setAttribute("role","switch"),this.setAttribute("aria-checked",`${this.checked}`),this.addEventListener("click",this._toggleChecked),this.addEventListener("keydown",this.__handleKeydown),this.addEventListener("keyup",this.__handleKeyup)}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener("click",this._toggleChecked),this.removeEventListener("keydown",this.__handleKeydown),this.removeEventListener("keyup",this.__handleKeyup)}_toggleChecked(){this.disabled||(this.focus(),this.checked=!this.checked)}__checkedStateChange(){this.dispatchEvent(new Event("checked-changed",{bubbles:!0})),this.setAttribute("aria-checked",`${this.checked}`)}_handleKeydown(e){e.key===" "&&e.preventDefault()}_handleKeyup(e){[" ","Enter"].includes(e.key)&&this._toggleChecked()}updated(e){super.updated(e),e.has("disabled")&&this.setAttribute("aria-disabled",`${this.disabled}`)}requestUpdate(e,r,n){super.requestUpdate(e,r,n),this.__initialized&&this.isConnected&&e==="checked"&&this.checked!==r&&!this.disabled&&this.__checkedStateChange()}firstUpdated(e){super.firstUpdated(e),this.__initialized=!0}},tS=class extends eS{static get styles(){return[...super.styles,ue`
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
      `]}};customElements.get("craft-switch-button")||customElements.define("craft-switch-button",tS);const x3=ue`
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
`;let C3=class extends ME(cm(bu)){static get styles(){return[...super.styles,ue`
        :host([hidden]) {
          display: none;
        }

        :host([disabled]) {
          color: #adadad;
        }
      `]}static get scopedElements(){return{...super.scopedElements,"lion-switch-button":eS}}get _inputNode(){return Array.from(this.children).find(e=>e.slot==="input")}get slots(){return{...super.slots,input:()=>{const e=this.createScopedElement("lion-switch-button");return e.setAttribute("data-tag-name","lion-switch-button"),e}}}render(){return Y`
      <div class="form-field__group-one">${this._groupOneTemplate()}</div>
      <div class="form-field__group-two">${this._groupTwoTemplate()}</div>
    `}_groupOneTemplate(){return Y`${this._labelTemplate()} ${this._helpTextTemplate()} ${this._feedbackTemplate()}`}_groupTwoTemplate(){return Y`${this._inputGroupTemplate()}`}constructor(){super(),this.checked=!1,this.__handleButtonSwitchCheckedChanged=this.__handleButtonSwitchCheckedChanged.bind(this)}connectedCallback(){super.connectedCallback(),this.addEventListener("checked-changed",this.__handleButtonSwitchCheckedChanged),this._labelNode&&this._labelNode.addEventListener("click",this._toggleChecked),this._syncButtonSwitch()}disconnectedCallback(){super.disconnectedCallback(),this._inputNode&&this.removeEventListener("checked-changed",this.__handleButtonSwitchCheckedChanged),this._labelNode&&this._labelNode.removeEventListener("click",this._toggleChecked)}updated(e){super.updated(e),e.has("disabled")&&this._syncButtonSwitch()}_toggleChecked(e){e.preventDefault(),super._toggleChecked(e)}_isEmpty(){return!1}__handleButtonSwitchCheckedChanged(e){e.stopPropagation(),this._isHandlingUserInput=!0,this.checked=this._inputNode.checked,this._isHandlingUserInput=!1}_syncButtonSwitch(){this._inputNode.disabled=this.disabled}_onLabelClick(){this.disabled||this._inputNode.focus()}},A3=class extends C3{static get styles(){return[...super.styles,x3]}get slots(){return{...super.slots,input:()=>{const e=this.createScopedElement("craft-switch-button");return e.setAttribute("data-tag-name","craft-switch-button"),e}}}static get scopedElements(){return{...super.scopedElements,"craft-switch-button":tS}}};customElements.get("craft-switch")||customElements.define("craft-switch",A3);function $3(t){return(e,r)=>{const{slot:n,selector:s}=t??{},i="slot"+(n?`[name=${n}]`:":not([name])");return nE(e,r,{get(){const o=this.renderRoot?.querySelector(i),a=o?.assignedElements(t)??[];return s===void 0?a:a.filter((l=>l.matches(s)))}})}}const T3=".breadcrumbs{display:flex;align-items:center}";var k3=Object.defineProperty,so=(t,e,r,n)=>{for(var s=void 0,i=t.length-1,o;i>=0;i--)(o=t[i])&&(s=o(e,r,s)||s);return s&&k3(e,r,s),s};const rS=new CSSStyleSheet;rS.replaceSync(T3);const nS=class extends je{constructor(){super(...arguments),this.label="",this.items=[],this.visibleItems=0,this.firstRender=!0}getSeparator(){const e=this.separatorSlot.assignedElements({flatten:!0})[0].cloneNode(!0);return[e,...e.querySelectorAll("[id]")].forEach(r=>r.removeAttribute("id")),e.setAttribute("data-default",""),e.slot="separator",e}calculateBreadcrumbItemsWidth(){this.items=this.breadcrumbsElements.map((e,r)=>{let n=e.offsetWidth;return e.hasAttribute("hidden")&&(e.removeAttribute("hidden"),n=e.offsetWidth,e.setAttribute("hidden","")),{label:e.innerText,href:e.href,value:r.toString(),offsetWidth:n,isVisible:!0}})}async handleSlotChange(){const e=[...this.defaultSlot.assignedElements({flatten:!0})].filter(r=>r.tagName.toLowerCase()==="craft-breadcrumb-item");if(e.forEach((r,n)=>{const s=r.querySelector('[slot="separator"]');s===null?r.append(this.getSeparator()):s.hasAttribute("data-default")&&s.replaceWith(this.getSeparator()),n===e.length-1?r.setAttribute("aria-current","page"):r.removeAttribute("aria-current")}),this.breadcrumbsElements.length===0){this.items=[],this.visibleItems=0;return}await Promise.all(this.breadcrumbsElements.map(r=>r.updateComplete)),this.calculateBreadcrumbItemsWidth(),this.visibleItems=0,this.adjustOverflow()}connectedCallback(){super.connectedCallback(),this.hasAttribute("role")||this.setAttribute("role","navigation"),this.resizeObserver=new ResizeObserver(()=>{if(this.firstRender){this.firstRender=!1;return}this.adjustOverflow()}),this.resizeObserver.observe(this)}adjustOverflow(){const e=this.getBoundingClientRect().width;console.log({availableSpace:e})}disconnectedCallback(){this.resizeObserver?.unobserve(this),super.disconnectedCallback()}render(){return Y`
      <nav class="breadcrumbs" aria-label="${this.label}">
        <slot @slotchange="${this.handleSlotChange}"></slot>
      </nav>

      <span hidden aria-hidden="true">
        <slot name="separator"><span class="separator">/</span></slot>
      </span>
    `}};nS.styles=[rS];let ei=nS;so([Qe("slot")],ei.prototype,"defaultSlot");so([Qe('slot[name="separator"]')],ei.prototype,"separatorSlot");so([$3({selector:"craft-breadcrumb-item"})],ei.prototype,"breadcrumbsElements");so([U()],ei.prototype,"label");so([lr()],ei.prototype,"items");so([lr()],ei.prototype,"visibleItems");customElements.get("craft-breadcrumbs")||customElements.define("craft-breadcrumbs",ei);const Rr=t=>t??Ce;var P3=`:host {
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
`,Br=class extends ar{constructor(){super(...arguments),this.renderType="button",this.rel="noreferrer noopener"}setRenderType(){const e=this.defaultSlot.assignedElements({flatten:!0}).filter(r=>r.tagName.toLowerCase()==="wa-dropdown").length>0;if(this.href){this.renderType="link";return}if(e){this.renderType="dropdown";return}this.renderType="button"}hrefChanged(){this.setRenderType()}handleSlotChange(){this.setRenderType()}render(){return Y`
      <span part="start" class="start">
        <slot name="start"></slot>
      </span>

      ${this.renderType==="link"?Y`
            <a
              part="label"
              class="label label-link"
              href="${this.href}"
              target="${Rr(this.target?this.target:void 0)}"
              rel=${Rr(this.target?this.rel:void 0)}
            >
              <slot></slot>
            </a>
          `:""}
      ${this.renderType==="button"?Y`
            <button part="label" type="button" class="label label-button">
              <slot @slotchange=${this.handleSlotChange}></slot>
            </button>
          `:""}
      ${this.renderType==="dropdown"?Y`
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
    `}};Br.css=P3;F([Qe("slot:not([name])")],Br.prototype,"defaultSlot",2);F([lr()],Br.prototype,"renderType",2);F([U()],Br.prototype,"href",2);F([U()],Br.prototype,"target",2);F([U()],Br.prototype,"rel",2);F([wr("href",{waitUntilFirstUpdate:!0})],Br.prototype,"hrefChanged",1);Br=F([qr("wa-breadcrumb-item")],Br);let O3=class extends Br{static get styles(){return[Br.styles,ue`
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
      `]}};customElements.get("craft-breadcrumb-item")||customElements.define("craft-breadcrumb-item",O3);var R3=`:host {
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
`,ah=new Set,pt=class extends ar{constructor(){super(...arguments),this.anchor=null,this.placement="top",this.open=!1,this.distance=8,this.skidding=0,this.for=null,this.withoutArrow=!1,this.eventController=new AbortController,this.handleAnchorClick=()=>{this.open=!this.open},this.handleBodyClick=e=>{e.target.closest('[data-popover="close"]')&&(e.stopPropagation(),this.open=!1)},this.handleDocumentKeyDown=e=>{e.key==="Escape"&&(e.preventDefault(),this.open=!1,this.anchor&&typeof this.anchor.focus=="function"&&this.anchor.focus())},this.handleDocumentClick=e=>{const r=e.target;this.anchor&&e.composedPath().includes(this.anchor)||r.closest("wa-popover")!==this&&(this.open=!1)}}connectedCallback(){super.connectedCallback(),this.id||(this.id=em("wa-popover-"))}disconnectedCallback(){super.disconnectedCallback(),document.removeEventListener("keydown",this.handleDocumentKeyDown),this.eventController.abort()}firstUpdated(){this.open&&(this.dialog.show(),this.popup.active=!0,this.popup.reposition())}updated(e){e.has("open")&&this.customStates.set("open",this.open)}async handleOpenChange(){if(this.open){const e=new Ta;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}ah.forEach(r=>r.open=!1),document.addEventListener("keydown",this.handleDocumentKeyDown,{signal:this.eventController.signal}),document.addEventListener("click",this.handleDocumentClick,{signal:this.eventController.signal}),this.dialog.show(),this.popup.active=!0,ah.add(this),requestAnimationFrame(()=>{const r=this.querySelector("[autofocus]");r&&typeof r.focus=="function"?r.focus():this.dialog.focus()}),await Mt(this.popup.popup,"show-with-scale"),this.popup.reposition(),this.dispatchEvent(new Aa)}else{const e=new $a;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!0;return}document.removeEventListener("keydown",this.handleDocumentKeyDown),document.removeEventListener("click",this.handleDocumentClick),ah.delete(this),await Mt(this.popup.popup,"hide-with-scale"),this.popup.active=!1,this.dialog.close(),this.dispatchEvent(new Ca)}}handleForChange(){const e=this.getRootNode();if(!e)return;const r=this.for?e.getElementById(this.for):null,n=this.anchor;if(r===n)return;const{signal:s}=this.eventController;r&&r.addEventListener("click",this.handleAnchorClick,{signal:s}),n&&n.removeEventListener("click",this.handleAnchorClick),this.anchor=r,this.for&&!r&&console.warn(`A popover was assigned to an element with an ID of "${this.for}" but the element could not be found.`,this)}async handleOptionsChange(){this.hasUpdated&&(await this.updateComplete,this.popup.reposition())}async show(){if(!this.open)return this.open=!0,yc(this,"wa-after-show")}async hide(){if(this.open)return this.open=!1,yc(this,"wa-after-hide")}render(){return Y`
      <dialog part="dialog" class="dialog">
        <wa-popup
          part="popup"
          exportparts="
            popup:popup__popup,
            arrow:popup__arrow
          "
          class=${en({popover:!0,"popover-open":this.open})}
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
    `}};pt.css=R3;pt.dependencies={"wa-popup":Me};F([Qe("dialog")],pt.prototype,"dialog",2);F([Qe(".body")],pt.prototype,"body",2);F([Qe("wa-popup")],pt.prototype,"popup",2);F([lr()],pt.prototype,"anchor",2);F([U()],pt.prototype,"placement",2);F([U({type:Boolean,reflect:!0})],pt.prototype,"open",2);F([U({type:Number})],pt.prototype,"distance",2);F([U({type:Number})],pt.prototype,"skidding",2);F([U()],pt.prototype,"for",2);F([U({attribute:"without-arrow",type:Boolean,reflect:!0})],pt.prototype,"withoutArrow",2);F([wr("open",{waitUntilFirstUpdate:!0})],pt.prototype,"handleOpenChange",1);F([wr("for")],pt.prototype,"handleForChange",1);F([wr(["distance","placement","skidding"])],pt.prototype,"handleOptionsChange",1);pt=F([qr("wa-popover")],pt);let N3=class extends pt{static get styles(){return[pt.styles,ue`
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
      `]}};customElements.get("craft-popover")||customElements.define("craft-popover",N3);const sS=class extends je{render(){return Y`
      <nav>
        <slot></slot>
      </nav>
    `}};sS.styles=ue`
    :host {
      display: block;
    }

    nav {
      display: grid;
    }
  `;let I3=sS;customElements.get("craft-navigation")||customElements.define("craft-navigation",I3);const L3=ue`
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
`,iS="important",F3=" !"+iS,M3=Kp(class extends Gp{constructor(t){if(super(t),t.type!==Wp.ATTRIBUTE||t.name!=="style"||t.strings?.length>2)throw Error("The `styleMap` directive must be used in the `style` attribute and must be the only part in the attribute.")}render(t){return Object.keys(t).reduce(((e,r)=>{const n=t[r];return n==null?e:e+`${r=r.includes("-")?r:r.replace(/(?:^(webkit|moz|ms|o)|)(?=[A-Z])/g,"-$&").toLowerCase()}:${n};`}),"")}update(t,[e]){const{style:r}=t.element;if(this.ft===void 0)return this.ft=new Set(Object.keys(e)),this.render(e);for(const n of this.ft)e[n]==null&&(this.ft.delete(n),n.includes("-")?r.removeProperty(n):r[n]=null);for(const n in e){const s=e[n];if(s!=null){this.ft.add(n);const i=typeof s=="string"&&s.endsWith(F3);n.includes("-")||i?r.setProperty(n,i?s.slice(0,-11):s,i?iS:""):r[n]=s}}return Zr}});var D3=Object.defineProperty,cs=(t,e,r,n)=>{for(var s=void 0,i=t.length-1,o;i>=0;i--)(o=t[i])&&(s=o(e,r,s)||s);return s&&D3(e,r,s),s};const oS=class extends je{constructor(){super(),this.active=!1,this.external=!1,this.indicator=!1,this.iconOnly=!1,this.subnavState="closed",this.id=this.id||Math.random().toString(36).substring(2,6)}connectedCallback(){super.connectedCallback(),this.subnavState=this.active?"open":"closed"}toggleSubnav(e){e.preventDefault(),e.stopPropagation(),this.subnavState=this.subnavState==="open"?"closed":"open"}renderIconItem(e){const r=`item-${this.id}`;return Y`
      <a
        class="nav-item"
        id="${r}"
        href="${this.url}"
        aria-current="${this.active?"page":!1}"
      >
        <span class="nav-item__prefix">
          <slot name="prefix">
            <slot name="icon">
              ${this.icon?Y` <craft-icon
                    name="${this.icon}"
                    class="nav-icon"
                  ></craft-icon>`:Y` <span class="active-indicator"></span> `}
            </slot>
            ${this.indicator?Y`<span class="indicator"></span>`:Ce}
          </slot>
        </span>

        <div class="nav-item__suffix">
          <slot name="suffix">
            ${e?Y`
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
                `:Ce}
          </slot>
        </div>
      </a>
      <c-tooltip for="${r}" placement="right-start"
        ><slot></slot
      ></c-tooltip>
    `}renderItem(e){return Y`
      <a
        class="nav-item"
        href="${this.url}"
        aria-current="${this.active?"page":!1}"
      >
        <span class="nav-item__prefix">
          <slot name="prefix">
            <slot name="icon">
              ${this.icon?Y` <craft-icon
                    name="${this.icon}"
                    class="nav-icon"
                  ></craft-icon>`:Y` <span class="active-indicator"></span> `}
            </slot>
            ${this.indicator?Y`<span class="indicator"></span>`:Ce}
          </slot>
        </span>
        <slot></slot>

        <div class="nav-item__suffix">
          <slot name="suffix">
            ${e?Y`
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
                `:Ce}
          </slot>
        </div>
      </a>
    `}render(){const e=!!this.querySelector('[slot="subnav"]');return Y`
      ${this.iconOnly?this.renderIconItem(e):this.renderItem(e)}
      ${e?Y`
            <div
              class="subnav"
              id="${this.id}-subnav"
              style="${M3({display:this.subnavState==="open"?"block":"none"})}"
            >
              <slot name="subnav"></slot>
            </div>
          `:Ce}
    `}};oS.styles=L3;let Pn=oS;cs([U()],Pn.prototype,"icon");cs([U()],Pn.prototype,"url");cs([U({type:Boolean,reflect:!0})],Pn.prototype,"active");cs([U({type:Boolean})],Pn.prototype,"external");cs([U({type:Boolean})],Pn.prototype,"indicator");cs([U()],Pn.prototype,"id");cs([U({reflect:!0,type:Boolean,attribute:"icon-only"})],Pn.prototype,"iconOnly");cs([lr()],Pn.prototype,"subnavState");customElements.get("craft-nav-item")||customElements.define("craft-nav-item",Pn);var gf=new Set;function V3(){const t=document.documentElement.clientWidth;return Math.abs(window.innerWidth-t)}function B3(){const t=Number(getComputedStyle(document.body).paddingRight.replace(/px/,""));return isNaN(t)||!t?0:t}function wc(t){if(gf.add(t),!document.documentElement.classList.contains("wa-scroll-lock")){const e=V3()+B3();let r=getComputedStyle(document.documentElement).scrollbarGutter;(!r||r==="auto")&&(r="stable"),e<2&&(r=""),document.documentElement.style.setProperty("--wa-scroll-lock-gutter",r),document.documentElement.classList.add("wa-scroll-lock"),document.documentElement.style.setProperty("--wa-scroll-lock-size",`${e}px`)}}function _c(t){gf.delete(t),gf.size===0&&(document.documentElement.classList.remove("wa-scroll-lock"),document.documentElement.style.removeProperty("--wa-scroll-lock-size"))}function aS(t){return t.split(" ").map(e=>e.trim()).filter(e=>e!=="")}var U3=()=>({checkValidity(t){const e=t.input,r={message:"",isValid:!0,invalidKeys:[]};if(!e)return r;let n=!0;if("checkValidity"in e&&(n=e.checkValidity()),n)return r;if(r.isValid=!1,"validationMessage"in e&&(r.message=e.validationMessage),!("validity"in e))return r.invalidKeys.push("customError"),r;for(const s in e.validity){if(s==="valid")continue;const i=s;e.validity[i]&&r.invalidKeys.push(i)}return r}}),lS=class extends Event{constructor(){super("wa-invalid",{bubbles:!0,cancelable:!1,composed:!0})}},j3=()=>({observedAttributes:["custom-error"],checkValidity(t){const e={message:"",isValid:!0,invalidKeys:[]};return t.customError&&(e.message=t.customError,e.isValid=!1,e.invalidKeys=["customError"]),e}}),On=class extends ar{constructor(){super(),this.name=null,this.disabled=!1,this.required=!1,this.assumeInteractionOn=["input"],this.validators=[],this.valueHasChanged=!1,this.hasInteracted=!1,this.customError=null,this.emittedEvents=[],this.emitInvalid=e=>{e.target===this&&(this.hasInteracted=!0,this.dispatchEvent(new lS))},this.handleInteraction=e=>{const r=this.emittedEvents;r.includes(e.type)||r.push(e.type),r.length===this.assumeInteractionOn?.length&&(this.hasInteracted=!0)},this.addEventListener("invalid",this.emitInvalid)}static get validators(){return[j3()]}static get observedAttributes(){const e=new Set(super.observedAttributes||[]);for(const r of this.validators)if(r.observedAttributes)for(const n of r.observedAttributes)e.add(n);return[...e]}connectedCallback(){super.connectedCallback(),this.updateValidity(),this.assumeInteractionOn.forEach(e=>{this.addEventListener(e,this.handleInteraction)})}firstUpdated(...e){super.firstUpdated(...e),this.updateValidity()}willUpdate(e){if(e.has("customError")&&(this.customError||(this.customError=null),this.setCustomValidity(this.customError||"")),e.has("value")||e.has("disabled")){const r=this.value;if(Array.isArray(r)){if(this.name){const n=new FormData;for(const s of r)n.append(this.name,s);this.setValue(n,n)}}else this.setValue(r,r)}e.has("disabled")&&(this.customStates.set("disabled",this.disabled),(this.hasAttribute("disabled")||!this.matches(":disabled"))&&this.toggleAttribute("disabled",this.disabled)),this.updateValidity(),super.willUpdate(e)}get labels(){return this.internals.labels}getForm(){return this.internals.form}get validity(){return this.internals.validity}get willValidate(){return this.internals.willValidate}get validationMessage(){return this.internals.validationMessage}checkValidity(){return this.updateValidity(),this.internals.checkValidity()}reportValidity(){return this.updateValidity(),this.hasInteracted=!0,this.internals.reportValidity()}get validationTarget(){return this.input||void 0}setValidity(...e){const r=e[0],n=e[1];let s=e[2];s||(s=this.validationTarget),this.internals.setValidity(r,n,s||void 0),this.requestUpdate("validity"),this.setCustomStates()}setCustomStates(){const e=!!this.required,r=this.internals.validity.valid,n=this.hasInteracted;this.customStates.set("required",e),this.customStates.set("optional",!e),this.customStates.set("invalid",!r),this.customStates.set("valid",r),this.customStates.set("user-invalid",!r&&n),this.customStates.set("user-valid",r&&n)}setCustomValidity(e){if(!e){this.customError=null,this.setValidity({});return}this.customError=e,this.setValidity({customError:!0},e,this.validationTarget)}formResetCallback(){this.resetValidity(),this.hasInteracted=!1,this.valueHasChanged=!1,this.emittedEvents=[],this.updateValidity()}formDisabledCallback(e){this.disabled=e,this.updateValidity()}formStateRestoreCallback(e,r){this.value=e,r==="restore"&&this.resetValidity(),this.updateValidity()}setValue(...e){const[r,n]=e;this.internals.setFormValue(r,n)}get allValidators(){const e=this.constructor.validators||[],r=this.validators||[];return[...e,...r]}resetValidity(){this.setCustomValidity(""),this.setValidity({})}updateValidity(){if(this.disabled||this.hasAttribute("disabled")||!this.willValidate){this.resetValidity();return}const e=this.allValidators;if(!e?.length)return;const r={customError:!!this.customError},n=this.validationTarget||this.input||void 0;let s="";for(const i of e){const{isValid:o,message:a,invalidKeys:l}=i.checkValidity(this);o||(s||(s=a),l?.length>=0&&l.forEach(u=>r[u]=!0))}s||(s=this.validationMessage),this.setValidity(r,s,n)}};On.formAssociated=!0;F([U({reflect:!0})],On.prototype,"name",2);F([U({type:Boolean})],On.prototype,"disabled",2);F([U({state:!0,attribute:!1})],On.prototype,"valueHasChanged",2);F([U({state:!0,attribute:!1})],On.prototype,"hasInteracted",2);F([U({attribute:"custom-error",reflect:!0})],On.prototype,"customError",2);F([U({attribute:!1,state:!0,type:Object})],On.prototype,"validity",1);var q3=`@layer wa-utilities {
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
`;const cS=Symbol.for(""),H3=t=>{if(t?.r===cS)return t?._$litStatic$},nv=(t,...e)=>({_$litStatic$:e.reduce(((r,n,s)=>r+(i=>{if(i._$litStatic$!==void 0)return i._$litStatic$;throw Error(`Value passed to 'literal' function must be a 'literal' result: ${i}. Use 'unsafeStatic' to pass non-literal values, but
            take care to ensure page security.`)})(n)+t[s+1]),t[0]),r:cS}),sv=new Map,z3=t=>(e,...r)=>{const n=r.length;let s,i;const o=[],a=[];let l,u=0,c=!1;for(;u<n;){for(l=e[u];u<n&&(i=r[u],(s=H3(i))!==void 0);)l+=s+e[++u],c=!0;u!==n&&a.push(i),o.push(l),u++}if(u===n&&o.push(e[n]),c){const d=o.join("$$lit$$");(e=sv.get(d))===void 0&&(o.raw=o,sv.set(d,e=o)),r=a}return t(e,...r)},lh=z3(Y);var W3=`@layer wa-component {
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
`,Ie=class extends On{constructor(){super(...arguments),this.assumeInteractionOn=["click"],this.hasSlotController=new vu(this,"[default]","start","end"),this.localize=new Zi(this),this.invalid=!1,this.isIconButton=!1,this.title="",this.variant="neutral",this.appearance="accent",this.size="medium",this.withCaret=!1,this.disabled=!1,this.loading=!1,this.pill=!1,this.type="button",this.form=null}static get validators(){return[...super.validators,U3()]}constructLightDOMButton(){const e=document.createElement("button");return e.type=this.type,e.style.position="absolute",e.style.width="0",e.style.height="0",e.style.clipPath="inset(50%)",e.style.overflow="hidden",e.style.whiteSpace="nowrap",this.name&&(e.name=this.name),e.value=this.value||"",["form","formaction","formenctype","formmethod","formnovalidate","formtarget"].forEach(r=>{this.hasAttribute(r)&&e.setAttribute(r,this.getAttribute(r))}),e}handleClick(){if(!this.getForm())return;const e=this.constructLightDOMButton();this.parentElement?.append(e),e.click(),e.remove()}handleInvalid(){this.dispatchEvent(new lS)}handleLabelSlotChange(){const e=this.labelSlot.assignedNodes({flatten:!0});let r=!1,n=!1,s=!1,i=!1;[...e].forEach(o=>{if(o.nodeType===Node.ELEMENT_NODE){const a=o;a.localName==="wa-icon"?(n=!0,r||(r=a.label!==void 0)):i=!0}else o.nodeType===Node.TEXT_NODE&&(o.textContent?.trim()||"").length>0&&(s=!0)}),this.isIconButton=n&&!s&&!i,this.isIconButton&&!r&&console.warn('Icon buttons must have a label for screen readers. Add <wa-icon label="..."> to remove this warning.',this)}isButton(){return!this.href}isLink(){return!!this.href}handleDisabledChange(){this.updateValidity()}setValue(...e){}click(){this.button.click()}focus(e){this.button.focus(e)}blur(){this.button.blur()}render(){const e=this.isLink(),r=e?nv`a`:nv`button`;return lh`
      <${r}
        part="base"
        class=${en({button:!0,caret:this.withCaret,disabled:this.disabled,loading:this.loading,rtl:this.localize.dir()==="rtl","has-label":this.hasSlotController.test("[default]"),"has-start":this.hasSlotController.test("start"),"has-end":this.hasSlotController.test("end"),"is-icon-button":this.isIconButton})}
        ?disabled=${Rr(e?void 0:this.disabled)}
        type=${Rr(e?void 0:this.type)}
        title=${this.title}
        name=${Rr(e?void 0:this.name)}
        value=${Rr(e?void 0:this.value)}
        href=${Rr(e?this.href:void 0)}
        target=${Rr(e?this.target:void 0)}
        download=${Rr(e?this.download:void 0)}
        rel=${Rr(e&&this.rel?this.rel:void 0)}
        role=${Rr(e?void 0:"button")}
        aria-disabled=${this.disabled?"true":"false"}
        tabindex=${this.disabled?"-1":"0"}
        @invalid=${this.isButton()?this.handleInvalid:null}
        @click=${this.handleClick}
      >
        <slot name="start" part="start" class="start"></slot>
        <slot part="label" class="label" @slotchange=${this.handleLabelSlotChange}></slot>
        <slot name="end" part="end" class="end"></slot>
        ${this.withCaret?lh`
                <wa-icon part="caret" class="caret" library="system" name="chevron-down" variant="solid"></wa-icon>
              `:""}
        ${this.loading?lh`<wa-spinner part="spinner"></wa-spinner>`:""}
      </${r}>
    `}};Ie.shadowRootOptions={...On.shadowRootOptions,delegatesFocus:!0};Ie.css=[W3,q3,JE];F([Qe(".button")],Ie.prototype,"button",2);F([Qe("slot:not([name])")],Ie.prototype,"labelSlot",2);F([lr()],Ie.prototype,"invalid",2);F([lr()],Ie.prototype,"isIconButton",2);F([U()],Ie.prototype,"title",2);F([U({reflect:!0})],Ie.prototype,"variant",2);F([U({reflect:!0})],Ie.prototype,"appearance",2);F([U({reflect:!0})],Ie.prototype,"size",2);F([U({attribute:"with-caret",type:Boolean,reflect:!0})],Ie.prototype,"withCaret",2);F([U({type:Boolean})],Ie.prototype,"disabled",2);F([U({type:Boolean,reflect:!0})],Ie.prototype,"loading",2);F([U({type:Boolean,reflect:!0})],Ie.prototype,"pill",2);F([U()],Ie.prototype,"type",2);F([U({reflect:!0})],Ie.prototype,"name",2);F([U({reflect:!0})],Ie.prototype,"value",2);F([U({reflect:!0})],Ie.prototype,"href",2);F([U()],Ie.prototype,"target",2);F([U()],Ie.prototype,"rel",2);F([U()],Ie.prototype,"download",2);F([U({reflect:!0})],Ie.prototype,"form",2);F([U({attribute:"formaction"})],Ie.prototype,"formAction",2);F([U({attribute:"formenctype"})],Ie.prototype,"formEnctype",2);F([U({attribute:"formmethod"})],Ie.prototype,"formMethod",2);F([U({attribute:"formnovalidate",type:Boolean})],Ie.prototype,"formNoValidate",2);F([U({attribute:"formtarget"})],Ie.prototype,"formTarget",2);F([wr("disabled",{waitUntilFirstUpdate:!0})],Ie.prototype,"handleDisabledChange",1);Ie=F([qr("wa-button")],Ie);var K3=`:host {
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
`,yf=class extends ar{constructor(){super(...arguments),this.localize=new Zi(this)}render(){return Y`
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
    `}};yf.css=K3;yf=F([qr("wa-spinner")],yf);var G3=`:host {
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
`,Tr=class extends ar{constructor(){super(...arguments),this.localize=new Zi(this),this.hasSlotController=new vu(this,"footer","header-actions","label"),this.open=!1,this.label="",this.placement="end",this.withoutHeader=!1,this.lightDismiss=!0,this.handleDocumentKeyDown=e=>{e.key==="Escape"&&this.open&&(e.preventDefault(),e.stopPropagation(),this.requestClose(this.drawer))}}firstUpdated(){this.open&&(this.addOpenListeners(),this.drawer.showModal(),wc(this))}disconnectedCallback(){super.disconnectedCallback(),_c(this),this.removeOpenListeners()}async requestClose(e){const r=new $a({source:e});if(this.dispatchEvent(r),r.defaultPrevented){this.open=!0,Mt(this.drawer,"pulse");return}this.removeOpenListeners(),await Mt(this.drawer,"hide"),this.open=!1,this.drawer.close(),_c(this);const n=this.originalTrigger;typeof n?.focus=="function"&&setTimeout(()=>n.focus()),this.dispatchEvent(new Ca)}addOpenListeners(){document.addEventListener("keydown",this.handleDocumentKeyDown)}removeOpenListeners(){document.removeEventListener("keydown",this.handleDocumentKeyDown)}handleDialogCancel(e){e.preventDefault(),!this.drawer.classList.contains("hide")&&e.target===this.drawer&&this.requestClose(this.drawer)}handleDialogClick(e){const r=e.target.closest('[data-drawer="close"]');r&&(e.stopPropagation(),this.requestClose(r))}async handleDialogPointerDown(e){e.target===this.drawer&&(this.lightDismiss?this.requestClose(this.drawer):await Mt(this.drawer,"pulse"))}handleOpenChange(){this.open&&!this.drawer.open?this.show():this.drawer.open&&(this.open=!0,this.requestClose(this.drawer))}async show(){const e=new Ta;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}this.addOpenListeners(),this.originalTrigger=document.activeElement,this.open=!0,this.drawer.showModal(),wc(this),requestAnimationFrame(()=>{const r=this.querySelector("[autofocus]");r&&typeof r.focus=="function"?r.focus():this.drawer.focus()}),await Mt(this.drawer,"show"),this.dispatchEvent(new Aa)}render(){const e=!this.withoutHeader,r=this.hasSlotController.test("footer");return Y`
      <dialog
        part="dialog"
        class=${en({drawer:!0,open:this.open,top:this.placement==="top",end:this.placement==="end",bottom:this.placement==="bottom",start:this.placement==="start"})}
        @cancel=${this.handleDialogCancel}
        @click=${this.handleDialogClick}
        @pointerdown=${this.handleDialogPointerDown}
      >
        ${e?Y`
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
                    @click="${n=>this.requestClose(n.target)}"
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

        ${r?Y`
              <footer part="footer" class="footer">
                <slot name="footer"></slot>
              </footer>
            `:""}
      </dialog>
    `}};Tr.css=G3;F([Qe(".drawer")],Tr.prototype,"drawer",2);F([U({type:Boolean,reflect:!0})],Tr.prototype,"open",2);F([U({reflect:!0})],Tr.prototype,"label",2);F([U({reflect:!0})],Tr.prototype,"placement",2);F([U({attribute:"without-header",type:Boolean,reflect:!0})],Tr.prototype,"withoutHeader",2);F([U({attribute:"light-dismiss",type:Boolean})],Tr.prototype,"lightDismiss",2);F([wr("open",{waitUntilFirstUpdate:!0})],Tr.prototype,"handleOpenChange",1);Tr=F([qr("wa-drawer")],Tr);document.addEventListener("click",t=>{const e=t.target.closest("[data-drawer]");if(e instanceof Element){const[r,n]=aS(e.getAttribute("data-drawer")||"");if(r==="open"&&n?.length){const s=e.getRootNode().getElementById(n);s?.localName==="wa-drawer"?s.open=!0:console.warn(`A drawer with an ID of "${n}" could not be found in this document.`)}}});document.body.addEventListener("pointerdown",()=>{});let J3=class extends Tr{static get styles(){return[Tr.styles,ue`
        :host {
          --wa-color-surface-raised: var(--c-bg-raised);
          --spacing: var(--c-spacing-lg);
          background-color: red;
        }
      `]}};customElements.get("craft-drawer")||customElements.define("craft-drawer",J3);var X3=`:host {
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
`,Ur=class extends ar{constructor(){super(...arguments),this.localize=new Zi(this),this.hasSlotController=new vu(this,"footer","header-actions","label"),this.open=!1,this.label="",this.withoutHeader=!1,this.lightDismiss=!1,this.handleDocumentKeyDown=e=>{e.key==="Escape"&&this.open&&(e.preventDefault(),e.stopPropagation(),this.requestClose(this.dialog))}}firstUpdated(){this.open&&(this.addOpenListeners(),this.dialog.showModal(),wc(this))}disconnectedCallback(){super.disconnectedCallback(),_c(this),this.removeOpenListeners()}async requestClose(e){const r=new $a({source:e});if(this.dispatchEvent(r),r.defaultPrevented){this.open=!0,Mt(this.dialog,"pulse");return}this.removeOpenListeners(),await Mt(this.dialog,"hide"),this.open=!1,this.dialog.close(),_c(this);const n=this.originalTrigger;typeof n?.focus=="function"&&setTimeout(()=>n.focus()),this.dispatchEvent(new Ca)}addOpenListeners(){document.addEventListener("keydown",this.handleDocumentKeyDown)}removeOpenListeners(){document.removeEventListener("keydown",this.handleDocumentKeyDown)}handleDialogCancel(e){e.preventDefault(),!this.dialog.classList.contains("hide")&&e.target===this.dialog&&this.requestClose(this.dialog)}handleDialogClick(e){const r=e.target.closest('[data-dialog="close"]');r&&(e.stopPropagation(),this.requestClose(r))}async handleDialogPointerDown(e){e.target===this.dialog&&(this.lightDismiss?this.requestClose(this.dialog):await Mt(this.dialog,"pulse"))}handleOpenChange(){this.open&&!this.dialog.open?this.show():!this.open&&this.dialog.open&&(this.open=!0,this.requestClose(this.dialog))}async show(){const e=new Ta;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}this.addOpenListeners(),this.originalTrigger=document.activeElement,this.open=!0,this.dialog.showModal(),wc(this),requestAnimationFrame(()=>{const r=this.querySelector("[autofocus]");r&&typeof r.focus=="function"?r.focus():this.dialog.focus()}),await Mt(this.dialog,"show"),this.dispatchEvent(new Aa)}render(){const e=!this.withoutHeader,r=this.hasSlotController.test("footer");return Y`
      <dialog
        part="dialog"
        class=${en({dialog:!0,open:this.open})}
        @cancel=${this.handleDialogCancel}
        @click=${this.handleDialogClick}
        @pointerdown=${this.handleDialogPointerDown}
      >
        ${e?Y`
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
                    @click="${n=>this.requestClose(n.target)}"
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

        ${r?Y`
              <footer part="footer" class="footer">
                <slot name="footer"></slot>
              </footer>
            `:""}
      </dialog>
    `}};Ur.css=X3;F([Qe(".dialog")],Ur.prototype,"dialog",2);F([U({type:Boolean,reflect:!0})],Ur.prototype,"open",2);F([U({reflect:!0})],Ur.prototype,"label",2);F([U({attribute:"without-header",type:Boolean,reflect:!0})],Ur.prototype,"withoutHeader",2);F([U({attribute:"light-dismiss",type:Boolean})],Ur.prototype,"lightDismiss",2);F([wr("open",{waitUntilFirstUpdate:!0})],Ur.prototype,"handleOpenChange",1);Ur=F([qr("wa-dialog")],Ur);document.addEventListener("click",t=>{const e=t.target.closest("[data-dialog]");if(e instanceof Element){const[r,n]=aS(e.getAttribute("data-dialog")||"");if(r==="open"&&n?.length){const s=e.getRootNode().getElementById(n);s?.localName==="wa-dialog"?s.open=!0:console.warn(`A dialog with an ID of "${n}" could not be found in this document.`)}}});document.addEventListener("pointerdown",()=>{});let Y3=class extends Ur{static get styles(){return[Ur.styles,ue`
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
      `]}};customElements.get("craft-dialog")||customElements.define("craft-dialog",Y3);let Q3=class extends EventTarget{constructor(e,r){super(),this.__param=e,this.__config=r||{},this.type=r?.type||"error"}static _$isValidator$=!0;static validatorName="";static async=!1;execute(e,r,n){if(!this.constructor.validatorName)throw new Error(`A validator needs to have a name! Please set it via "static get validatorName() { return 'IsCat'; }"`);return!0}set param(e){this.__param=e,this.dispatchEvent(new Event("param-changed"))}get param(){return this.__param}set config(e){this.__config=e,this.dispatchEvent(new Event("config-changed"))}get config(){return this.__config}async _getMessage(e){const r=this.constructor,n={name:r.validatorName,type:this.type,params:this.param,config:this.config,...e};if(this.config.getMessage){if(typeof this.config.getMessage=="function")return this.config.getMessage(n);throw new Error(`You must provide a value for getMessage of type 'function', you provided a value of type: ${typeof this.config.getMessage}`)}return r.getMessage(n)}static async getMessage(e){return`Please configure an error message for "${this.name}" by overriding "static async getMessage()"`}onFormControlConnect(e){}onFormControlDisconnect(e){}abortExecution(){}},bf=class extends Array{_keys(){return Object.keys(this).filter(e=>Number.isNaN(Number(e)))}};const Z3=t=>class extends sm(t){static get properties(){return{_isFormOrFieldset:{type:Boolean}}}constructor(){super(),this.formElements=new bf,this._isFormOrFieldset=!1,this._onRequestToAddFormElement=this._onRequestToAddFormElement.bind(this),this._onRequestToChangeFormElementName=this._onRequestToChangeFormElementName.bind(this),this.addEventListener("form-element-register",this._onRequestToAddFormElement),this.addEventListener("form-element-name-changed",this._onRequestToChangeFormElementName),this.initComplete=new Promise((e,r)=>{this.__resolveInitComplete=e,this.__rejectInitComplete=r}),this.registrationComplete=new Promise((e,r)=>{this.__resolveRegistrationComplete=e,this.__rejectRegistrationComplete=r}),this.registrationComplete.done=!1,this.registrationComplete.then(()=>{this.registrationComplete.done=!0,this.__resolveInitComplete(void 0)},()=>{throw this.registrationComplete.done=!0,this.__rejectInitComplete(void 0),new Error("Registration could not finish. Please use await el.registrationComplete;")})}connectedCallback(){super.connectedCallback(),this._completeRegistration()}_completeRegistration(){Promise.resolve().then(()=>this.__resolveRegistrationComplete(void 0))}disconnectedCallback(){super.disconnectedCallback(),this.registrationComplete.done===!1&&Promise.resolve().then(()=>{Promise.resolve().then(()=>{this.__rejectRegistrationComplete()})})}isRegisteredFormElement(e){return this.formElements.some(r=>r===e)}addFormElement(e,r){if(e._parentFormGroup=this,r>=0?this.formElements.splice(r,0,e):this.formElements.push(e),this._isFormOrFieldset){const{name:n}=e;if(n===this.name)throw console.info("Error Node:",e),new TypeError(`You can not have the same name "${n}" as your parent`);if(n.substr(-2)==="[]")Array.isArray(this.formElements[n])||(this.formElements[n]=new bf),r>0?this.formElements[n].splice(r,0,e):this.formElements[n].push(e);else if(!this.formElements[n])this.formElements[n]=e;else throw console.info("Error Node:",e),new TypeError(`Name "${n}" is already registered - if you want an array add [] to the end`)}}removeFormElement(e){const r=this.formElements.indexOf(e);if(r>-1&&this.formElements.splice(r,1),this._isFormOrFieldset){const{name:n}=e;if(n.substr(-2)==="[]"&&this.formElements[n]){const s=this.formElements[n].indexOf(e);s>-1&&this.formElements[n].splice(s,1)}else this.formElements[n]&&delete this.formElements[n]}}_onRequestToAddFormElement(e){const r=e.detail.element;if(r===this||this.isRegisteredFormElement(r))return;e.stopPropagation();let n=-1;if(this.formElements&&Array.isArray(this.formElements)){for(const[s,i]of this.formElements.entries())if(!(i.compareDocumentPosition(r)&Node.DOCUMENT_POSITION_FOLLOWING)){n=s;break}}this.addFormElement(r,n)}_onRequestToChangeFormElementName(e){const r=this.formElements[e.detail.oldName];r&&(this.formElements[e.detail.newName]=r,delete this.formElements[e.detail.oldName])}_onRequestToRemoveFormElement(e){const r=e.detail.element;r!==this&&this.isRegisteredFormElement(r)&&(e.stopPropagation(),this.removeFormElement(r))}},uS=mt(Z3),e5=t=>class extends uS(yu(VE(t))){static get properties(){return{multipleChoice:{type:Boolean,attribute:"multiple-choice"}}}get modelValue(){const e=this._getCheckedElements();return this.multipleChoice?e.map(r=>r.choiceValue):e[0]?e[0].choiceValue:""}set modelValue(e){const r=(n,s)=>typeof n.choiceValue=="object"?JSON.stringify(n.choiceValue)===JSON.stringify(e):n.choiceValue===s;this.__isInitialModelValue?this.registrationComplete.then(()=>{this.__isInitialModelValue=!1,this._setCheckedElements(e,r),this.requestUpdate("modelValue",this._oldModelValue)}):(this._setCheckedElements(e,r),this.requestUpdate("modelValue",this._oldModelValue)),this._oldModelValue=this.modelValue}get serializedValue(){const e=this._getCheckedElements();return this.multipleChoice?e.map(r=>r.serializedValue.value):e[0]?e[0].serializedValue.value:""}set serializedValue(e){const r=(n,s)=>n.serializedValue.value===s;this.__isInitialSerializedValue?this.registrationComplete.then(()=>{this.__isInitialSerializedValue=!1,this._setCheckedElements(e,r),this.requestUpdate("serializedValue")}):(this._setCheckedElements(e,r),this.requestUpdate("serializedValue"))}get formattedValue(){const e=this._getCheckedElements();return this.multipleChoice?e.map(r=>r.formattedValue):e[0]?e[0].formattedValue:""}set formattedValue(e){const r=(n,s)=>n.formattedValue===s;this.__isInitialFormattedValue?this.registrationComplete.then(()=>{this.__isInitialFormattedValue=!1,this._setCheckedElements(e,r)}):this._setCheckedElements(e,r)}get operationMode(){return this._repropagationRole==="choice-group"?"select":"enter"}constructor(){super(),this.multipleChoice=!1,this._repropagationRole="choice-group",this.__isInitialModelValue=!0,this.__isInitialSerializedValue=!0,this.__isInitialFormattedValue=!0}connectedCallback(){super.connectedCallback(),this.registrationComplete.then(()=>{this.__isInitialModelValue=!1,this.__isInitialSerializedValue=!1,this.__isInitialFormattedValue=!1})}_completeRegistration(){Promise.resolve().then(()=>super._completeRegistration())}updated(e){super.updated(e),e.has("name")&&this.name!==e.get("name")&&this.formElements.forEach(r=>{r.name=this.name})}addFormElement(e,r){this._throwWhenInvalidChildModelValue(e),e.name=this.name,super.addFormElement(e,r)}clear(){this.multipleChoice?this.modelValue=[]:this.modelValue=""}_triggerInitialModelValueChangedEvent(){this.registrationComplete.then(()=>{this._dispatchInitialModelValueChangedEvent()})}_getFromAllFormElementsFilter(e,r){return!0}_getFromAllFormElements(e,r){const n=r||this._getFromAllFormElementsFilter;return e==="modelValue"||e==="serializedValue"||e==="formattedValue"?this[e]:this.formElements.filter(s=>n(s,e)).map(s=>s.property)}_throwWhenInvalidChildModelValue(e){if(typeof e.modelValue.checked!="boolean"||!Object.prototype.hasOwnProperty.call(e.modelValue,"value"))throw new Error(`The ${this.tagName.toLowerCase()} name="${this.name}" does not allow to register ${e.tagName.toLowerCase()} with .modelValue="${e.modelValue}" - The modelValue should represent an Object { value: "foo", checked: false }`)}_isEmpty(){return this.multipleChoice?this.modelValue.length===0:typeof this.modelValue=="string"&&this.modelValue===""||this.modelValue===void 0||this.modelValue===null}_checkSingleChoiceElements(e){const{target:r}=e;if(r.checked===!1)return;const n=r.name;this.formElements.filter(s=>s.name===n).forEach(s=>{s!==r&&(s.checked=!1)})}_getCheckedElements(){return this.formElements.filter(e=>e.checked&&!e.disabled)}_setCheckedElements(e,r){if(e==null){this.formElements.forEach(n=>n.checked=!1);return}for(let n=0;n<this.formElements.length;n+=1)if(this.multipleChoice){let s=e.includes(this.formElements[n].modelValue.value);typeof this.formElements[n].modelValue.value=="object"&&(s=e.map(i=>JSON.stringify(i)).includes(JSON.stringify(this.formElements[n].modelValue.value))),this.formElements[n].checked=s}else r(this.formElements[n],e)?this.formElements[n].checked=!0:this.formElements[n].checked=!1}__setChoiceGroupTouched(){const e=this.modelValue;e!=null&&e!==this.__previousCheckedValue&&(this.touched=!0,this.__previousCheckedValue=e)}_onBeforeRepropagateChildrenValues(e){const r=e.detail&&e.detail.element||e.target;this.multipleChoice||!r.checked||(this.formElements.forEach(n=>{r.choiceValue!==n.choiceValue&&(n.checked=!1)}),this.__setChoiceGroupTouched(),this.requestUpdate("modelValue",this._oldModelValue),this._oldModelValue=this.modelValue)}_repropagationCondition(e){return!(this._repropagationRole==="choice-group"&&!this.multipleChoice&&!e.checked)}},t5=mt(e5);let r5=class extends Q3{static get validatorName(){return"FormElementsHaveNoError"}execute(e,r,n){return n?.node._anyFormElementHasFeedbackFor("error")}static async getMessage(){return""}};const n5=t=>class extends uS(no(yu(Oa(Na(t))))){static get properties(){return{submitted:{type:Boolean,reflect:!0},focused:{type:Boolean,reflect:!0},dirty:{type:Boolean,reflect:!0},touched:{type:Boolean,reflect:!0},prefilled:{type:Boolean,reflect:!0}}}get _inputNode(){return this}get modelValue(){return this._getFromAllFormElements("modelValue")}set modelValue(e){this.__isInitialModelValue?(this.__isInitialModelValue=!1,this.registrationComplete.then(()=>{this._setValueMapForAllFormElements("modelValue",e)})):this._setValueMapForAllFormElements("modelValue",e)}get serializedValue(){return this._getFromAllFormElements("serializedValue")}set serializedValue(e){this.__isInitialSerializedValue?(this.__isInitialSerializedValue=!1,this.registrationComplete.then(()=>{this._setValueMapForAllFormElements("serializedValue",e)})):this._setValueMapForAllFormElements("serializedValue",e)}get formattedValue(){return this._getFromAllFormElements("formattedValue")}set formattedValue(e){this._setValueMapForAllFormElements("formattedValue",e)}get prefilled(){return this._everyFormElementHas("prefilled")}constructor(){super(),this.value="",this.disabled=!1,this.submitted=!1,this.dirty=!1,this.touched=!1,this.focused=!1,this.__addedSubValidators=!1,this.__isInitialModelValue=!0,this.__isInitialSerializedValue=!0,this._checkForOutsideClick=this._checkForOutsideClick.bind(this),this.addEventListener("focusin",this._syncFocused),this.addEventListener("focusout",this._onFocusOut),this.addEventListener("dirty-changed",this._syncDirty),this.addEventListener("validate-performed",this.__onChildValidatePerformed),this.defaultValidators=[new r5],this.__descriptionElementsInParentChain=new Set,this.__pendingValues={modelValue:{},serializedValue:{}}}connectedCallback(){super.connectedCallback(),this.setAttribute("role","group"),this.initComplete.then(()=>{this.__isInitialModelValue=!1,this.__isInitialSerializedValue=!1,this.__initInteractionStates()})}disconnectedCallback(){super.disconnectedCallback(),this.__hasActiveOutsideClickHandling&&(document.removeEventListener("click",this._checkForOutsideClick),this.__hasActiveOutsideClickHandling=!1),this.__descriptionElementsInParentChain.clear()}__initInteractionStates(){this.formElements.forEach(e=>{typeof e.initInteractionState=="function"&&e.initInteractionState()})}_triggerInitialModelValueChangedEvent(){this.registrationComplete.then(()=>{this._dispatchInitialModelValueChangedEvent()})}updated(e){super.updated(e),e.has("disabled")&&(this.disabled?this.__requestChildrenToBeDisabled():this.__retractRequestChildrenToBeDisabled()),e.has("focused")&&this.focused===!0&&this.__setupOutsideClickHandling()}__setupOutsideClickHandling(){this.__hasActiveOutsideClickHandling||(document.addEventListener("click",this._checkForOutsideClick),this.__hasActiveOutsideClickHandling=!0)}_checkForOutsideClick(e){!this.contains(e.target)&&(this.touched=!0)}__requestChildrenToBeDisabled(){this.formElements.forEach(e=>{e.makeRequestToBeDisabled&&e.makeRequestToBeDisabled()})}__retractRequestChildrenToBeDisabled(){this.formElements.forEach(e=>{e.retractRequestToBeDisabled&&e.retractRequestToBeDisabled()})}_inputGroupTemplate(){return Y`
        <div class="input-group">
          <slot></slot>
        </div>
      `}submitGroup(){this.submitted=!0,this.formElements.forEach(e=>{typeof e.submitGroup=="function"?e.submitGroup():e.submitted=!0})}resetGroup(){this.formElements.forEach(e=>{typeof e.resetGroup=="function"?e.resetGroup():typeof e.reset=="function"&&e.reset()}),this.resetInteractionState()}clearGroup(){this.formElements.forEach(e=>{typeof e.clearGroup=="function"?e.clearGroup():typeof e.clear=="function"&&e.clear()}),this.resetInteractionState()}resetInteractionState(){this.submitted=!1,this.touched=!1,this.dirty=!1,this.formElements.forEach(e=>{typeof e.resetInteractionState=="function"&&e.resetInteractionState()})}_getFromAllFormElementsFilter(e,r){return!e.disabled}_getFromAllFormElements(e,r){const n={},s=r||this._getFromAllFormElementsFilter;return this.formElements._keys().forEach(i=>{const o=this.formElements[i];o instanceof bf?n[i]=o.filter(a=>s(a,e)).map(a=>a[e]):s(o,e)&&(typeof o._getFromAllFormElements=="function"?n[i]=o._getFromAllFormElements(e):n[i]=o[e])}),n}_setValueForAllFormElements(e,r){this.formElements.forEach(n=>{n[e]=r})}_setValueMapForAllFormElements(e,r){r&&typeof r=="object"&&Object.keys(r).forEach(n=>{Array.isArray(this.formElements[n])&&this.formElements[n].forEach((s,i)=>{s[e]=r[n][i]}),this.formElements[n]?this.formElements[n][e]=r[n]:this.__pendingValues[e][n]=r[n]})}_anyFormElementHas(e){return Object.keys(this.formElements).some(r=>Array.isArray(this.formElements[r])?this.formElements[r].some(n=>!!n[e]):!!this.formElements[r][e])}_anyFormElementHasFeedbackFor(e){return Object.keys(this.formElements).some(r=>Array.isArray(this.formElements[r])?this.formElements[r].some(n=>!!(n.hasFeedbackFor&&n.hasFeedbackFor.includes(e))):!!(this.formElements[r].hasFeedbackFor&&this.formElements[r].hasFeedbackFor.includes(e)))}_everyFormElementHas(e){return Object.keys(this.formElements).every(r=>Array.isArray(this.formElements[r])?this.formElements[r].every(n=>!!n[e]):!!this.formElements[r][e])}__onChildValidatePerformed(e){e&&this.isRegisteredFormElement(e.target)&&this.validate()}_syncFocused(){this.focused=this._anyFormElementHas("focused")}_onFocusOut(e){const r=this.formElements[this.formElements.length-1];e.target===r&&(this.touched=!0),this.focused=!1}_syncDirty(){this.dirty=this._anyFormElementHas("dirty")}__storeAllDescriptionElementsInParentChain(){let e=this;for(;e;){const r=e._getAriaDescriptionElements();DE(r,{reverse:!0}).forEach(n=>{n.getAttribute("slot")==="feedback"&&this.__descriptionElementsInParentChain.add(n)}),e=e._parentFormGroup}}__linkParentMessages(e){this.__descriptionElementsInParentChain.forEach(r=>{typeof e.addToAriaDescribedBy=="function"&&e.addToAriaDescribedBy(r,{reorder:!1})})}__unlinkParentMessages(e){this.__descriptionElementsInParentChain.forEach(r=>{typeof e.removeFromAriaDescribedBy=="function"&&e.removeFromAriaDescribedBy(r)})}addFormElement(e,r){if(super.addFormElement(e,r),this.disabled&&e.makeRequestToBeDisabled(),this.__descriptionElementsInParentChain.size||this.__storeAllDescriptionElementsInParentChain(),this.__linkParentMessages(e),this.validate({clearCurrentResult:!0}),!e.modelValue){const n=this.__pendingValues;n.modelValue&&n.modelValue[e.name]?e.modelValue=n.modelValue[e.name]:n.serializedValue&&n.serializedValue[e.name]&&(e.serializedValue=n.serializedValue[e.name])}}get _initialModelValue(){return this._getFromAllFormElements("_initialModelValue")}removeFormElement(e){super.removeFormElement(e),this.validate({clearCurrentResult:!0}),typeof e.removeFromAriaLabelledBy=="function"&&this._labelNode&&e.removeFromAriaLabelledBy(this._labelNode,{reorder:!1}),this.__unlinkParentMessages(e)}_isEmpty(){return this.formElements.every(e=>e._isEmpty?.())}},s5=mt(n5);let iv=class extends t5(s5(je)){constructor(){super(),this.multipleChoice=!0}},i5=class extends iv{static get styles(){return[...iv.styles,ue`
        .form-field__group-two {
          margin-top: var(--c-spacing-sm);
        }

        ::slotted(label) {
          font-weight: bold;
        }
      `]}};customElements.get("craft-checkbox-group")||customElements.define("craft-checkbox-group",i5);let ov=class extends cm(om){connectedCallback(){super.connectedCallback(),this.type="checkbox"}},o5=class extends ov{static get styles(){return[...ov.styles,ue`
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
      `]}};customElements.get("craft-checkbox")||customElements.define("craft-checkbox",o5);const a5=ue`
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
`;var l5=Object.defineProperty,dS=(t,e,r,n)=>{for(var s=void 0,i=t.length-1,o;i>=0;i--)(o=t[i])&&(s=o(e,r,s)||s);return s&&l5(e,r,s),s};const hS=class extends je{constructor(){super(...arguments),this.variant="default",this.appearance="outline-filled"}render(){return Y`<slot></slot>`}};hS.styles=[a5];let um=hS;dS([U({reflect:!0})],um.prototype,"variant");dS([U()],um.prototype,"appearance");customElements.get("craft-callout")||customElements.define("craft-callout",um);function fS(t,e){return function(){return t.apply(e,arguments)}}const{toString:c5}=Object.prototype,{getPrototypeOf:dm}=Object,{iterator:wu,toStringTag:pS}=Symbol,_u=(t=>e=>{const r=c5.call(e);return t[r]||(t[r]=r.slice(8,-1).toLowerCase())})(Object.create(null)),Hr=t=>(t=t.toLowerCase(),e=>_u(e)===t),Eu=t=>e=>typeof e===t,{isArray:io}=Array,Ui=Eu("undefined");function Ia(t){return t!==null&&!Ui(t)&&t.constructor!==null&&!Ui(t.constructor)&&nr(t.constructor.isBuffer)&&t.constructor.isBuffer(t)}const mS=Hr("ArrayBuffer");function u5(t){let e;return typeof ArrayBuffer<"u"&&ArrayBuffer.isView?e=ArrayBuffer.isView(t):e=t&&t.buffer&&mS(t.buffer),e}const d5=Eu("string"),nr=Eu("function"),gS=Eu("number"),La=t=>t!==null&&typeof t=="object",h5=t=>t===!0||t===!1,Tl=t=>{if(_u(t)!=="object")return!1;const e=dm(t);return(e===null||e===Object.prototype||Object.getPrototypeOf(e)===null)&&!(pS in t)&&!(wu in t)},f5=t=>{if(!La(t)||Ia(t))return!1;try{return Object.keys(t).length===0&&Object.getPrototypeOf(t)===Object.prototype}catch{return!1}},p5=Hr("Date"),m5=Hr("File"),g5=Hr("Blob"),y5=Hr("FileList"),b5=t=>La(t)&&nr(t.pipe),v5=t=>{let e;return t&&(typeof FormData=="function"&&t instanceof FormData||nr(t.append)&&((e=_u(t))==="formdata"||e==="object"&&nr(t.toString)&&t.toString()==="[object FormData]"))},w5=Hr("URLSearchParams"),[_5,E5,S5,x5]=["ReadableStream","Request","Response","Headers"].map(Hr),C5=t=>t.trim?t.trim():t.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g,"");function Fa(t,e,{allOwnKeys:r=!1}={}){if(t===null||typeof t>"u")return;let n,s;if(typeof t!="object"&&(t=[t]),io(t))for(n=0,s=t.length;n<s;n++)e.call(null,t[n],n,t);else{if(Ia(t))return;const i=r?Object.getOwnPropertyNames(t):Object.keys(t),o=i.length;let a;for(n=0;n<o;n++)a=i[n],e.call(null,t[a],a,t)}}function yS(t,e){if(Ia(t))return null;e=e.toLowerCase();const r=Object.keys(t);let n=r.length,s;for(;n-- >0;)if(s=r[n],e===s.toLowerCase())return s;return null}const As=typeof globalThis<"u"?globalThis:typeof self<"u"?self:typeof window<"u"?window:global,bS=t=>!Ui(t)&&t!==As;function vf(){const{caseless:t,skipUndefined:e}=bS(this)&&this||{},r={},n=(s,i)=>{const o=t&&yS(r,i)||i;Tl(r[o])&&Tl(s)?r[o]=vf(r[o],s):Tl(s)?r[o]=vf({},s):io(s)?r[o]=s.slice():(!e||!Ui(s))&&(r[o]=s)};for(let s=0,i=arguments.length;s<i;s++)arguments[s]&&Fa(arguments[s],n);return r}const A5=(t,e,r,{allOwnKeys:n}={})=>(Fa(e,(s,i)=>{r&&nr(s)?t[i]=fS(s,r):t[i]=s},{allOwnKeys:n}),t),$5=t=>(t.charCodeAt(0)===65279&&(t=t.slice(1)),t),T5=(t,e,r,n)=>{t.prototype=Object.create(e.prototype,n),t.prototype.constructor=t,Object.defineProperty(t,"super",{value:e.prototype}),r&&Object.assign(t.prototype,r)},k5=(t,e,r,n)=>{let s,i,o;const a={};if(e=e||{},t==null)return e;do{for(s=Object.getOwnPropertyNames(t),i=s.length;i-- >0;)o=s[i],(!n||n(o,t,e))&&!a[o]&&(e[o]=t[o],a[o]=!0);t=r!==!1&&dm(t)}while(t&&(!r||r(t,e))&&t!==Object.prototype);return e},P5=(t,e,r)=>{t=String(t),(r===void 0||r>t.length)&&(r=t.length),r-=e.length;const n=t.indexOf(e,r);return n!==-1&&n===r},O5=t=>{if(!t)return null;if(io(t))return t;let e=t.length;if(!gS(e))return null;const r=new Array(e);for(;e-- >0;)r[e]=t[e];return r},R5=(t=>e=>t&&e instanceof t)(typeof Uint8Array<"u"&&dm(Uint8Array)),N5=(t,e)=>{const r=(t&&t[wu]).call(t);let n;for(;(n=r.next())&&!n.done;){const s=n.value;e.call(t,s[0],s[1])}},I5=(t,e)=>{let r;const n=[];for(;(r=t.exec(e))!==null;)n.push(r);return n},L5=Hr("HTMLFormElement"),F5=t=>t.toLowerCase().replace(/[-_\s]([a-z\d])(\w*)/g,function(e,r,n){return r.toUpperCase()+n}),av=(({hasOwnProperty:t})=>(e,r)=>t.call(e,r))(Object.prototype),M5=Hr("RegExp"),vS=(t,e)=>{const r=Object.getOwnPropertyDescriptors(t),n={};Fa(r,(s,i)=>{let o;(o=e(s,i,t))!==!1&&(n[i]=o||s)}),Object.defineProperties(t,n)},D5=t=>{vS(t,(e,r)=>{if(nr(t)&&["arguments","caller","callee"].indexOf(r)!==-1)return!1;const n=t[r];if(nr(n)){if(e.enumerable=!1,"writable"in e){e.writable=!1;return}e.set||(e.set=()=>{throw Error("Can not rewrite read-only method '"+r+"'")})}})},V5=(t,e)=>{const r={},n=s=>{s.forEach(i=>{r[i]=!0})};return io(t)?n(t):n(String(t).split(e)),r},B5=()=>{},U5=(t,e)=>t!=null&&Number.isFinite(t=+t)?t:e;function j5(t){return!!(t&&nr(t.append)&&t[pS]==="FormData"&&t[wu])}const q5=t=>{const e=new Array(10),r=(n,s)=>{if(La(n)){if(e.indexOf(n)>=0)return;if(Ia(n))return n;if(!("toJSON"in n)){e[s]=n;const i=io(n)?[]:{};return Fa(n,(o,a)=>{const l=r(o,s+1);!Ui(l)&&(i[a]=l)}),e[s]=void 0,i}}return n};return r(t,0)},H5=Hr("AsyncFunction"),z5=t=>t&&(La(t)||nr(t))&&nr(t.then)&&nr(t.catch),wS=((t,e)=>t?setImmediate:e?((r,n)=>(As.addEventListener("message",({source:s,data:i})=>{s===As&&i===r&&n.length&&n.shift()()},!1),s=>{n.push(s),As.postMessage(r,"*")}))(`axios@${Math.random()}`,[]):r=>setTimeout(r))(typeof setImmediate=="function",nr(As.postMessage)),W5=typeof queueMicrotask<"u"?queueMicrotask.bind(As):typeof process<"u"&&process.nextTick||wS,K5=t=>t!=null&&nr(t[wu]),M={isArray:io,isArrayBuffer:mS,isBuffer:Ia,isFormData:v5,isArrayBufferView:u5,isString:d5,isNumber:gS,isBoolean:h5,isObject:La,isPlainObject:Tl,isEmptyObject:f5,isReadableStream:_5,isRequest:E5,isResponse:S5,isHeaders:x5,isUndefined:Ui,isDate:p5,isFile:m5,isBlob:g5,isRegExp:M5,isFunction:nr,isStream:b5,isURLSearchParams:w5,isTypedArray:R5,isFileList:y5,forEach:Fa,merge:vf,extend:A5,trim:C5,stripBOM:$5,inherits:T5,toFlatObject:k5,kindOf:_u,kindOfTest:Hr,endsWith:P5,toArray:O5,forEachEntry:N5,matchAll:I5,isHTMLForm:L5,hasOwnProperty:av,hasOwnProp:av,reduceDescriptors:vS,freezeMethods:D5,toObjectSet:V5,toCamelCase:F5,noop:B5,toFiniteNumber:U5,findKey:yS,global:As,isContextDefined:bS,isSpecCompliantForm:j5,toJSONObject:q5,isAsyncFn:H5,isThenable:z5,setImmediate:wS,asap:W5,isIterable:K5};function fe(t,e,r,n,s){Error.call(this),Error.captureStackTrace?Error.captureStackTrace(this,this.constructor):this.stack=new Error().stack,this.message=t,this.name="AxiosError",e&&(this.code=e),r&&(this.config=r),n&&(this.request=n),s&&(this.response=s,this.status=s.status?s.status:null)}M.inherits(fe,Error,{toJSON:function(){return{message:this.message,name:this.name,description:this.description,number:this.number,fileName:this.fileName,lineNumber:this.lineNumber,columnNumber:this.columnNumber,stack:this.stack,config:M.toJSONObject(this.config),code:this.code,status:this.status}}});const _S=fe.prototype,ES={};["ERR_BAD_OPTION_VALUE","ERR_BAD_OPTION","ECONNABORTED","ETIMEDOUT","ERR_NETWORK","ERR_FR_TOO_MANY_REDIRECTS","ERR_DEPRECATED","ERR_BAD_RESPONSE","ERR_BAD_REQUEST","ERR_CANCELED","ERR_NOT_SUPPORT","ERR_INVALID_URL"].forEach(t=>{ES[t]={value:t}});Object.defineProperties(fe,ES);Object.defineProperty(_S,"isAxiosError",{value:!0});fe.from=(t,e,r,n,s,i)=>{const o=Object.create(_S);M.toFlatObject(t,o,function(u){return u!==Error.prototype},u=>u!=="isAxiosError");const a=t&&t.message?t.message:"Error",l=e==null&&t?t.code:e;return fe.call(o,a,l,r,n,s),t&&o.cause==null&&Object.defineProperty(o,"cause",{value:t,configurable:!0}),o.name=t&&t.name||"Error",i&&Object.assign(o,i),o};const G5=null;function wf(t){return M.isPlainObject(t)||M.isArray(t)}function SS(t){return M.endsWith(t,"[]")?t.slice(0,-2):t}function lv(t,e,r){return t?t.concat(e).map(function(n,s){return n=SS(n),!r&&s?"["+n+"]":n}).join(r?".":""):e}function J5(t){return M.isArray(t)&&!t.some(wf)}const X5=M.toFlatObject(M,{},null,function(t){return/^is[A-Z]/.test(t)});function Su(t,e,r){if(!M.isObject(t))throw new TypeError("target must be an object");e=e||new FormData,r=M.toFlatObject(r,{metaTokens:!0,dots:!1,indexes:!1},!1,function(f,h){return!M.isUndefined(h[f])});const n=r.metaTokens,s=r.visitor||u,i=r.dots,o=r.indexes,a=(r.Blob||typeof Blob<"u"&&Blob)&&M.isSpecCompliantForm(e);if(!M.isFunction(s))throw new TypeError("visitor must be a function");function l(f){if(f===null)return"";if(M.isDate(f))return f.toISOString();if(M.isBoolean(f))return f.toString();if(!a&&M.isBlob(f))throw new fe("Blob is not supported. Use a Buffer instead.");return M.isArrayBuffer(f)||M.isTypedArray(f)?a&&typeof Blob=="function"?new Blob([f]):Buffer.from(f):f}function u(f,h,m){let g=f;if(f&&!m&&typeof f=="object"){if(M.endsWith(h,"{}"))h=n?h:h.slice(0,-2),f=JSON.stringify(f);else if(M.isArray(f)&&J5(f)||(M.isFileList(f)||M.endsWith(h,"[]"))&&(g=M.toArray(f)))return h=SS(h),g.forEach(function(v,b){!(M.isUndefined(v)||v===null)&&e.append(o===!0?lv([h],b,i):o===null?h:h+"[]",l(v))}),!1}return wf(f)?!0:(e.append(lv(m,h,i),l(f)),!1)}const c=[],d=Object.assign(X5,{defaultVisitor:u,convertValue:l,isVisitable:wf});function p(f,h){if(!M.isUndefined(f)){if(c.indexOf(f)!==-1)throw Error("Circular reference detected in "+h.join("."));c.push(f),M.forEach(f,function(m,g){(!(M.isUndefined(m)||m===null)&&s.call(e,m,M.isString(g)?g.trim():g,h,d))===!0&&p(m,h?h.concat(g):[g])}),c.pop()}}if(!M.isObject(t))throw new TypeError("data must be an object");return p(t),e}function cv(t){const e={"!":"%21","'":"%27","(":"%28",")":"%29","~":"%7E","%20":"+","%00":"\0"};return encodeURIComponent(t).replace(/[!'()~]|%20|%00/g,function(r){return e[r]})}function hm(t,e){this._pairs=[],t&&Su(t,this,e)}const xS=hm.prototype;xS.append=function(t,e){this._pairs.push([t,e])};xS.toString=function(t){const e=t?function(r){return t.call(this,r,cv)}:cv;return this._pairs.map(function(r){return e(r[0])+"="+e(r[1])},"").join("&")};function Y5(t){return encodeURIComponent(t).replace(/%3A/gi,":").replace(/%24/g,"$").replace(/%2C/gi,",").replace(/%20/g,"+")}function CS(t,e,r){if(!e)return t;const n=r&&r.encode||Y5;M.isFunction(r)&&(r={serialize:r});const s=r&&r.serialize;let i;if(s?i=s(e,r):i=M.isURLSearchParams(e)?e.toString():new hm(e,r).toString(n),i){const o=t.indexOf("#");o!==-1&&(t=t.slice(0,o)),t+=(t.indexOf("?")===-1?"?":"&")+i}return t}class uv{constructor(){this.handlers=[]}use(e,r,n){return this.handlers.push({fulfilled:e,rejected:r,synchronous:n?n.synchronous:!1,runWhen:n?n.runWhen:null}),this.handlers.length-1}eject(e){this.handlers[e]&&(this.handlers[e]=null)}clear(){this.handlers&&(this.handlers=[])}forEach(e){M.forEach(this.handlers,function(r){r!==null&&e(r)})}}const AS={silentJSONParsing:!0,forcedJSONParsing:!0,clarifyTimeoutError:!1},Q5=typeof URLSearchParams<"u"?URLSearchParams:hm,Z5=typeof FormData<"u"?FormData:null,eV=typeof Blob<"u"?Blob:null,tV={isBrowser:!0,classes:{URLSearchParams:Q5,FormData:Z5,Blob:eV},protocols:["http","https","file","blob","url","data"]},fm=typeof window<"u"&&typeof document<"u",_f=typeof navigator=="object"&&navigator||void 0,rV=fm&&(!_f||["ReactNative","NativeScript","NS"].indexOf(_f.product)<0),nV=typeof WorkerGlobalScope<"u"&&self instanceof WorkerGlobalScope&&typeof self.importScripts=="function",sV=fm&&window.location.href||"http://localhost",iV=Object.freeze(Object.defineProperty({__proto__:null,hasBrowserEnv:fm,hasStandardBrowserEnv:rV,hasStandardBrowserWebWorkerEnv:nV,navigator:_f,origin:sV},Symbol.toStringTag,{value:"Module"})),Lt={...iV,...tV};function oV(t,e){return Su(t,new Lt.classes.URLSearchParams,{visitor:function(r,n,s,i){return Lt.isNode&&M.isBuffer(r)?(this.append(n,r.toString("base64")),!1):i.defaultVisitor.apply(this,arguments)},...e})}function aV(t){return M.matchAll(/\w+|\[(\w*)]/g,t).map(e=>e[0]==="[]"?"":e[1]||e[0])}function lV(t){const e={},r=Object.keys(t);let n;const s=r.length;let i;for(n=0;n<s;n++)i=r[n],e[i]=t[i];return e}function $S(t){function e(r,n,s,i){let o=r[i++];if(o==="__proto__")return!0;const a=Number.isFinite(+o),l=i>=r.length;return o=!o&&M.isArray(s)?s.length:o,l?(M.hasOwnProp(s,o)?s[o]=[s[o],n]:s[o]=n,!a):((!s[o]||!M.isObject(s[o]))&&(s[o]=[]),e(r,n,s[o],i)&&M.isArray(s[o])&&(s[o]=lV(s[o])),!a)}if(M.isFormData(t)&&M.isFunction(t.entries)){const r={};return M.forEachEntry(t,(n,s)=>{e(aV(n),s,r,0)}),r}return null}function cV(t,e,r){if(M.isString(t))try{return(e||JSON.parse)(t),M.trim(t)}catch(n){if(n.name!=="SyntaxError")throw n}return(r||JSON.stringify)(t)}const Ma={transitional:AS,adapter:["xhr","http","fetch"],transformRequest:[function(t,e){const r=e.getContentType()||"",n=r.indexOf("application/json")>-1,s=M.isObject(t);if(s&&M.isHTMLForm(t)&&(t=new FormData(t)),M.isFormData(t))return n?JSON.stringify($S(t)):t;if(M.isArrayBuffer(t)||M.isBuffer(t)||M.isStream(t)||M.isFile(t)||M.isBlob(t)||M.isReadableStream(t))return t;if(M.isArrayBufferView(t))return t.buffer;if(M.isURLSearchParams(t))return e.setContentType("application/x-www-form-urlencoded;charset=utf-8",!1),t.toString();let i;if(s){if(r.indexOf("application/x-www-form-urlencoded")>-1)return oV(t,this.formSerializer).toString();if((i=M.isFileList(t))||r.indexOf("multipart/form-data")>-1){const o=this.env&&this.env.FormData;return Su(i?{"files[]":t}:t,o&&new o,this.formSerializer)}}return s||n?(e.setContentType("application/json",!1),cV(t)):t}],transformResponse:[function(t){const e=this.transitional||Ma.transitional,r=e&&e.forcedJSONParsing,n=this.responseType==="json";if(M.isResponse(t)||M.isReadableStream(t))return t;if(t&&M.isString(t)&&(r&&!this.responseType||n)){const s=!(e&&e.silentJSONParsing)&&n;try{return JSON.parse(t,this.parseReviver)}catch(i){if(s)throw i.name==="SyntaxError"?fe.from(i,fe.ERR_BAD_RESPONSE,this,null,this.response):i}}return t}],timeout:0,xsrfCookieName:"XSRF-TOKEN",xsrfHeaderName:"X-XSRF-TOKEN",maxContentLength:-1,maxBodyLength:-1,env:{FormData:Lt.classes.FormData,Blob:Lt.classes.Blob},validateStatus:function(t){return t>=200&&t<300},headers:{common:{Accept:"application/json, text/plain, */*","Content-Type":void 0}}};M.forEach(["delete","get","head","post","put","patch"],t=>{Ma.headers[t]={}});const uV=M.toObjectSet(["age","authorization","content-length","content-type","etag","expires","from","host","if-modified-since","if-unmodified-since","last-modified","location","max-forwards","proxy-authorization","referer","retry-after","user-agent"]),dV=t=>{const e={};let r,n,s;return t&&t.split(`
`).forEach(function(i){s=i.indexOf(":"),r=i.substring(0,s).trim().toLowerCase(),n=i.substring(s+1).trim(),!(!r||e[r]&&uV[r])&&(r==="set-cookie"?e[r]?e[r].push(n):e[r]=[n]:e[r]=e[r]?e[r]+", "+n:n)}),e},dv=Symbol("internals");function yo(t){return t&&String(t).trim().toLowerCase()}function kl(t){return t===!1||t==null?t:M.isArray(t)?t.map(kl):String(t)}function hV(t){const e=Object.create(null),r=/([^\s,;=]+)\s*(?:=\s*([^,;]+))?/g;let n;for(;n=r.exec(t);)e[n[1]]=n[2];return e}const fV=t=>/^[-_a-zA-Z0-9^`|~,!#$%&'*+.]+$/.test(t.trim());function ch(t,e,r,n,s){if(M.isFunction(n))return n.call(this,e,r);if(s&&(e=r),!!M.isString(e)){if(M.isString(n))return e.indexOf(n)!==-1;if(M.isRegExp(n))return n.test(e)}}function pV(t){return t.trim().toLowerCase().replace(/([a-z\d])(\w*)/g,(e,r,n)=>r.toUpperCase()+n)}function mV(t,e){const r=M.toCamelCase(" "+e);["get","set","has"].forEach(n=>{Object.defineProperty(t,n+r,{value:function(s,i,o){return this[n].call(this,e,s,i,o)},configurable:!0})})}let sr=class{constructor(e){e&&this.set(e)}set(e,r,n){const s=this;function i(a,l,u){const c=yo(l);if(!c)throw new Error("header name must be a non-empty string");const d=M.findKey(s,c);(!d||s[d]===void 0||u===!0||u===void 0&&s[d]!==!1)&&(s[d||l]=kl(a))}const o=(a,l)=>M.forEach(a,(u,c)=>i(u,c,l));if(M.isPlainObject(e)||e instanceof this.constructor)o(e,r);else if(M.isString(e)&&(e=e.trim())&&!fV(e))o(dV(e),r);else if(M.isObject(e)&&M.isIterable(e)){let a={},l,u;for(const c of e){if(!M.isArray(c))throw TypeError("Object iterator must return a key-value pair");a[u=c[0]]=(l=a[u])?M.isArray(l)?[...l,c[1]]:[l,c[1]]:c[1]}o(a,r)}else e!=null&&i(r,e,n);return this}get(e,r){if(e=yo(e),e){const n=M.findKey(this,e);if(n){const s=this[n];if(!r)return s;if(r===!0)return hV(s);if(M.isFunction(r))return r.call(this,s,n);if(M.isRegExp(r))return r.exec(s);throw new TypeError("parser must be boolean|regexp|function")}}}has(e,r){if(e=yo(e),e){const n=M.findKey(this,e);return!!(n&&this[n]!==void 0&&(!r||ch(this,this[n],n,r)))}return!1}delete(e,r){const n=this;let s=!1;function i(o){if(o=yo(o),o){const a=M.findKey(n,o);a&&(!r||ch(n,n[a],a,r))&&(delete n[a],s=!0)}}return M.isArray(e)?e.forEach(i):i(e),s}clear(e){const r=Object.keys(this);let n=r.length,s=!1;for(;n--;){const i=r[n];(!e||ch(this,this[i],i,e,!0))&&(delete this[i],s=!0)}return s}normalize(e){const r=this,n={};return M.forEach(this,(s,i)=>{const o=M.findKey(n,i);if(o){r[o]=kl(s),delete r[i];return}const a=e?pV(i):String(i).trim();a!==i&&delete r[i],r[a]=kl(s),n[a]=!0}),this}concat(...e){return this.constructor.concat(this,...e)}toJSON(e){const r=Object.create(null);return M.forEach(this,(n,s)=>{n!=null&&n!==!1&&(r[s]=e&&M.isArray(n)?n.join(", "):n)}),r}[Symbol.iterator](){return Object.entries(this.toJSON())[Symbol.iterator]()}toString(){return Object.entries(this.toJSON()).map(([e,r])=>e+": "+r).join(`
`)}getSetCookie(){return this.get("set-cookie")||[]}get[Symbol.toStringTag](){return"AxiosHeaders"}static from(e){return e instanceof this?e:new this(e)}static concat(e,...r){const n=new this(e);return r.forEach(s=>n.set(s)),n}static accessor(e){const r=(this[dv]=this[dv]={accessors:{}}).accessors,n=this.prototype;function s(i){const o=yo(i);r[o]||(mV(n,i),r[o]=!0)}return M.isArray(e)?e.forEach(s):s(e),this}};sr.accessor(["Content-Type","Content-Length","Accept","Accept-Encoding","User-Agent","Authorization"]);M.reduceDescriptors(sr.prototype,({value:t},e)=>{let r=e[0].toUpperCase()+e.slice(1);return{get:()=>t,set(n){this[r]=n}}});M.freezeMethods(sr);function uh(t,e){const r=this||Ma,n=e||r,s=sr.from(n.headers);let i=n.data;return M.forEach(t,function(o){i=o.call(r,i,s.normalize(),e?e.status:void 0)}),s.normalize(),i}function TS(t){return!!(t&&t.__CANCEL__)}function oo(t,e,r){fe.call(this,t??"canceled",fe.ERR_CANCELED,e,r),this.name="CanceledError"}M.inherits(oo,fe,{__CANCEL__:!0});function kS(t,e,r){const n=r.config.validateStatus;!r.status||!n||n(r.status)?t(r):e(new fe("Request failed with status code "+r.status,[fe.ERR_BAD_REQUEST,fe.ERR_BAD_RESPONSE][Math.floor(r.status/100)-4],r.config,r.request,r))}function gV(t){const e=/^([-+\w]{1,25})(:?\/\/|:)/.exec(t);return e&&e[1]||""}function yV(t,e){t=t||10;const r=new Array(t),n=new Array(t);let s=0,i=0,o;return e=e!==void 0?e:1e3,function(a){const l=Date.now(),u=n[i];o||(o=l),r[s]=a,n[s]=l;let c=i,d=0;for(;c!==s;)d+=r[c++],c=c%t;if(s=(s+1)%t,s===i&&(i=(i+1)%t),l-o<e)return;const p=u&&l-u;return p?Math.round(d*1e3/p):void 0}}function bV(t,e){let r=0,n=1e3/e,s,i;const o=(a,l=Date.now())=>{r=l,s=null,i&&(clearTimeout(i),i=null),t(...a)};return[(...a)=>{const l=Date.now(),u=l-r;u>=n?o(a,l):(s=a,i||(i=setTimeout(()=>{i=null,o(s)},n-u)))},()=>s&&o(s)]}const Ec=(t,e,r=3)=>{let n=0;const s=yV(50,250);return bV(i=>{const o=i.loaded,a=i.lengthComputable?i.total:void 0,l=o-n,u=s(l),c=o<=a;n=o;const d={loaded:o,total:a,progress:a?o/a:void 0,bytes:l,rate:u||void 0,estimated:u&&a&&c?(a-o)/u:void 0,event:i,lengthComputable:a!=null,[e?"download":"upload"]:!0};t(d)},r)},hv=(t,e)=>{const r=t!=null;return[n=>e[0]({lengthComputable:r,total:t,loaded:n}),e[1]]},fv=t=>(...e)=>M.asap(()=>t(...e)),vV=Lt.hasStandardBrowserEnv?((t,e)=>r=>(r=new URL(r,Lt.origin),t.protocol===r.protocol&&t.host===r.host&&(e||t.port===r.port)))(new URL(Lt.origin),Lt.navigator&&/(msie|trident)/i.test(Lt.navigator.userAgent)):()=>!0,wV=Lt.hasStandardBrowserEnv?{write(t,e,r,n,s,i,o){if(typeof document>"u")return;const a=[`${t}=${encodeURIComponent(e)}`];M.isNumber(r)&&a.push(`expires=${new Date(r).toUTCString()}`),M.isString(n)&&a.push(`path=${n}`),M.isString(s)&&a.push(`domain=${s}`),i===!0&&a.push("secure"),M.isString(o)&&a.push(`SameSite=${o}`),document.cookie=a.join("; ")},read(t){if(typeof document>"u")return null;const e=document.cookie.match(new RegExp("(?:^|; )"+t+"=([^;]*)"));return e?decodeURIComponent(e[1]):null},remove(t){this.write(t,"",Date.now()-864e5,"/")}}:{write(){},read(){return null},remove(){}};function _V(t){return/^([a-z][a-z\d+\-.]*:)?\/\//i.test(t)}function EV(t,e){return e?t.replace(/\/?\/$/,"")+"/"+e.replace(/^\/+/,""):t}function PS(t,e,r){let n=!_V(e);return t&&(n||r==!1)?EV(t,e):e}const pv=t=>t instanceof sr?{...t}:t;function Ws(t,e){e=e||{};const r={};function n(u,c,d,p){return M.isPlainObject(u)&&M.isPlainObject(c)?M.merge.call({caseless:p},u,c):M.isPlainObject(c)?M.merge({},c):M.isArray(c)?c.slice():c}function s(u,c,d,p){if(M.isUndefined(c)){if(!M.isUndefined(u))return n(void 0,u,d,p)}else return n(u,c,d,p)}function i(u,c){if(!M.isUndefined(c))return n(void 0,c)}function o(u,c){if(M.isUndefined(c)){if(!M.isUndefined(u))return n(void 0,u)}else return n(void 0,c)}function a(u,c,d){if(d in e)return n(u,c);if(d in t)return n(void 0,u)}const l={url:i,method:i,data:i,baseURL:o,transformRequest:o,transformResponse:o,paramsSerializer:o,timeout:o,timeoutMessage:o,withCredentials:o,withXSRFToken:o,adapter:o,responseType:o,xsrfCookieName:o,xsrfHeaderName:o,onUploadProgress:o,onDownloadProgress:o,decompress:o,maxContentLength:o,maxBodyLength:o,beforeRedirect:o,transport:o,httpAgent:o,httpsAgent:o,cancelToken:o,socketPath:o,responseEncoding:o,validateStatus:a,headers:(u,c,d)=>s(pv(u),pv(c),d,!0)};return M.forEach(Object.keys({...t,...e}),function(u){const c=l[u]||s,d=c(t[u],e[u],u);M.isUndefined(d)&&c!==a||(r[u]=d)}),r}const OS=t=>{const e=Ws({},t);let{data:r,withXSRFToken:n,xsrfHeaderName:s,xsrfCookieName:i,headers:o,auth:a}=e;if(e.headers=o=sr.from(o),e.url=CS(PS(e.baseURL,e.url,e.allowAbsoluteUrls),t.params,t.paramsSerializer),a&&o.set("Authorization","Basic "+btoa((a.username||"")+":"+(a.password?unescape(encodeURIComponent(a.password)):""))),M.isFormData(r)){if(Lt.hasStandardBrowserEnv||Lt.hasStandardBrowserWebWorkerEnv)o.setContentType(void 0);else if(M.isFunction(r.getHeaders)){const l=r.getHeaders(),u=["content-type","content-length"];Object.entries(l).forEach(([c,d])=>{u.includes(c.toLowerCase())&&o.set(c,d)})}}if(Lt.hasStandardBrowserEnv&&(n&&M.isFunction(n)&&(n=n(e)),n||n!==!1&&vV(e.url))){const l=s&&i&&wV.read(i);l&&o.set(s,l)}return e},SV=typeof XMLHttpRequest<"u",xV=SV&&function(t){return new Promise(function(e,r){const n=OS(t);let s=n.data;const i=sr.from(n.headers).normalize();let{responseType:o,onUploadProgress:a,onDownloadProgress:l}=n,u,c,d,p,f;function h(){p&&p(),f&&f(),n.cancelToken&&n.cancelToken.unsubscribe(u),n.signal&&n.signal.removeEventListener("abort",u)}let m=new XMLHttpRequest;m.open(n.method.toUpperCase(),n.url,!0),m.timeout=n.timeout;function g(){if(!m)return;const b=sr.from("getAllResponseHeaders"in m&&m.getAllResponseHeaders()),w={data:!o||o==="text"||o==="json"?m.responseText:m.response,status:m.status,statusText:m.statusText,headers:b,config:t,request:m};kS(function(_){e(_),h()},function(_){r(_),h()},w),m=null}"onloadend"in m?m.onloadend=g:m.onreadystatechange=function(){!m||m.readyState!==4||m.status===0&&!(m.responseURL&&m.responseURL.indexOf("file:")===0)||setTimeout(g)},m.onabort=function(){m&&(r(new fe("Request aborted",fe.ECONNABORTED,t,m)),m=null)},m.onerror=function(b){const w=b&&b.message?b.message:"Network Error",_=new fe(w,fe.ERR_NETWORK,t,m);_.event=b||null,r(_),m=null},m.ontimeout=function(){let b=n.timeout?"timeout of "+n.timeout+"ms exceeded":"timeout exceeded";const w=n.transitional||AS;n.timeoutErrorMessage&&(b=n.timeoutErrorMessage),r(new fe(b,w.clarifyTimeoutError?fe.ETIMEDOUT:fe.ECONNABORTED,t,m)),m=null},s===void 0&&i.setContentType(null),"setRequestHeader"in m&&M.forEach(i.toJSON(),function(b,w){m.setRequestHeader(w,b)}),M.isUndefined(n.withCredentials)||(m.withCredentials=!!n.withCredentials),o&&o!=="json"&&(m.responseType=n.responseType),l&&([d,f]=Ec(l,!0),m.addEventListener("progress",d)),a&&m.upload&&([c,p]=Ec(a),m.upload.addEventListener("progress",c),m.upload.addEventListener("loadend",p)),(n.cancelToken||n.signal)&&(u=b=>{m&&(r(!b||b.type?new oo(null,t,m):b),m.abort(),m=null)},n.cancelToken&&n.cancelToken.subscribe(u),n.signal&&(n.signal.aborted?u():n.signal.addEventListener("abort",u)));const v=gV(n.url);if(v&&Lt.protocols.indexOf(v)===-1){r(new fe("Unsupported protocol "+v+":",fe.ERR_BAD_REQUEST,t));return}m.send(s||null)})},CV=(t,e)=>{const{length:r}=t=t?t.filter(Boolean):[];if(e||r){let n=new AbortController,s;const i=function(u){if(!s){s=!0,a();const c=u instanceof Error?u:this.reason;n.abort(c instanceof fe?c:new oo(c instanceof Error?c.message:c))}};let o=e&&setTimeout(()=>{o=null,i(new fe(`timeout ${e} of ms exceeded`,fe.ETIMEDOUT))},e);const a=()=>{t&&(o&&clearTimeout(o),o=null,t.forEach(u=>{u.unsubscribe?u.unsubscribe(i):u.removeEventListener("abort",i)}),t=null)};t.forEach(u=>u.addEventListener("abort",i));const{signal:l}=n;return l.unsubscribe=()=>M.asap(a),l}},AV=function*(t,e){let r=t.byteLength;if(r<e){yield t;return}let n=0,s;for(;n<r;)s=n+e,yield t.slice(n,s),n=s},$V=async function*(t,e){for await(const r of TV(t))yield*AV(r,e)},TV=async function*(t){if(t[Symbol.asyncIterator]){yield*t;return}const e=t.getReader();try{for(;;){const{done:r,value:n}=await e.read();if(r)break;yield n}}finally{await e.cancel()}},mv=(t,e,r,n)=>{const s=$V(t,e);let i=0,o,a=l=>{o||(o=!0,n&&n(l))};return new ReadableStream({async pull(l){try{const{done:u,value:c}=await s.next();if(u){a(),l.close();return}let d=c.byteLength;if(r){let p=i+=d;r(p)}l.enqueue(new Uint8Array(c))}catch(u){throw a(u),u}},cancel(l){return a(l),s.return()}},{highWaterMark:2})},gv=64*1024,{isFunction:cl}=M,kV=(({Request:t,Response:e})=>({Request:t,Response:e}))(M.global),{ReadableStream:yv,TextEncoder:bv}=M.global,vv=(t,...e)=>{try{return!!t(...e)}catch{return!1}},PV=t=>{t=M.merge.call({skipUndefined:!0},kV,t);const{fetch:e,Request:r,Response:n}=t,s=e?cl(e):typeof fetch=="function",i=cl(r),o=cl(n);if(!s)return!1;const a=s&&cl(yv),l=s&&(typeof bv=="function"?(h=>m=>h.encode(m))(new bv):async h=>new Uint8Array(await new r(h).arrayBuffer())),u=i&&a&&vv(()=>{let h=!1;const m=new r(Lt.origin,{body:new yv,method:"POST",get duplex(){return h=!0,"half"}}).headers.has("Content-Type");return h&&!m}),c=o&&a&&vv(()=>M.isReadableStream(new n("").body)),d={stream:c&&(h=>h.body)};s&&["text","arrayBuffer","blob","formData","stream"].forEach(h=>{!d[h]&&(d[h]=(m,g)=>{let v=m&&m[h];if(v)return v.call(m);throw new fe(`Response type '${h}' is not supported`,fe.ERR_NOT_SUPPORT,g)})});const p=async h=>{if(h==null)return 0;if(M.isBlob(h))return h.size;if(M.isSpecCompliantForm(h))return(await new r(Lt.origin,{method:"POST",body:h}).arrayBuffer()).byteLength;if(M.isArrayBufferView(h)||M.isArrayBuffer(h))return h.byteLength;if(M.isURLSearchParams(h)&&(h=h+""),M.isString(h))return(await l(h)).byteLength},f=async(h,m)=>M.toFiniteNumber(h.getContentLength())??p(m);return async h=>{let{url:m,method:g,data:v,signal:b,cancelToken:w,timeout:_,onDownloadProgress:S,onUploadProgress:k,responseType:O,headers:R,withCredentials:C="same-origin",fetchOptions:A}=OS(h),j=e||fetch;O=O?(O+"").toLowerCase():"text";let $=CV([b,w&&w.toAbortSignal()],_),y=null;const I=$&&$.unsubscribe&&(()=>{$.unsubscribe()});let q;try{if(k&&u&&g!=="get"&&g!=="head"&&(q=await f(R,v))!==0){let re=new r(m,{method:"POST",body:v,duplex:"half"}),_e;if(M.isFormData(v)&&(_e=re.headers.get("content-type"))&&R.setContentType(_e),re.body){const[at,st]=hv(q,Ec(fv(k)));v=mv(re.body,gv,at,st)}}M.isString(C)||(C=C?"include":"omit");const D=i&&"credentials"in r.prototype,z={...A,signal:$,method:g.toUpperCase(),headers:R.normalize().toJSON(),body:v,duplex:"half",credentials:D?C:void 0};y=i&&new r(m,z);let P=await(i?j(y,A):j(m,z));const ee=c&&(O==="stream"||O==="response");if(c&&(S||ee&&I)){const re={};["status","statusText","headers"].forEach(_t=>{re[_t]=P[_t]});const _e=M.toFiniteNumber(P.headers.get("content-length")),[at,st]=S&&hv(_e,Ec(fv(S),!0))||[];P=new n(mv(P.body,gv,at,()=>{st&&st(),I&&I()}),re)}O=O||"text";let ye=await d[M.findKey(d,O)||"text"](P,h);return!ee&&I&&I(),await new Promise((re,_e)=>{kS(re,_e,{data:ye,headers:sr.from(P.headers),status:P.status,statusText:P.statusText,config:h,request:y})})}catch(D){throw I&&I(),D&&D.name==="TypeError"&&/Load failed|fetch/i.test(D.message)?Object.assign(new fe("Network Error",fe.ERR_NETWORK,h,y),{cause:D.cause||D}):fe.from(D,D&&D.code,h,y)}}},OV=new Map,RS=t=>{let e=t&&t.env||{};const{fetch:r,Request:n,Response:s}=e,i=[n,s,r];let o=i.length,a=o,l,u,c=OV;for(;a--;)l=i[a],u=c.get(l),u===void 0&&c.set(l,u=a?new Map:PV(e)),c=u;return u};RS();const pm={http:G5,xhr:xV,fetch:{get:RS}};M.forEach(pm,(t,e)=>{if(t){try{Object.defineProperty(t,"name",{value:e})}catch{}Object.defineProperty(t,"adapterName",{value:e})}});const wv=t=>`- ${t}`,RV=t=>M.isFunction(t)||t===null||t===!1;function NV(t,e){t=M.isArray(t)?t:[t];const{length:r}=t;let n,s;const i={};for(let o=0;o<r;o++){n=t[o];let a;if(s=n,!RV(n)&&(s=pm[(a=String(n)).toLowerCase()],s===void 0))throw new fe(`Unknown adapter '${a}'`);if(s&&(M.isFunction(s)||(s=s.get(e))))break;i[a||"#"+o]=s}if(!s){const o=Object.entries(i).map(([l,u])=>`adapter ${l} `+(u===!1?"is not supported by the environment":"is not available in the build"));let a=r?o.length>1?`since :
`+o.map(wv).join(`
`):" "+wv(o[0]):"as no adapter specified";throw new fe("There is no suitable adapter to dispatch the request "+a,"ERR_NOT_SUPPORT")}return s}const NS={getAdapter:NV,adapters:pm};function dh(t){if(t.cancelToken&&t.cancelToken.throwIfRequested(),t.signal&&t.signal.aborted)throw new oo(null,t)}function _v(t){return dh(t),t.headers=sr.from(t.headers),t.data=uh.call(t,t.transformRequest),["post","put","patch"].indexOf(t.method)!==-1&&t.headers.setContentType("application/x-www-form-urlencoded",!1),NS.getAdapter(t.adapter||Ma.adapter,t)(t).then(function(e){return dh(t),e.data=uh.call(t,t.transformResponse,e),e.headers=sr.from(e.headers),e},function(e){return TS(e)||(dh(t),e&&e.response&&(e.response.data=uh.call(t,t.transformResponse,e.response),e.response.headers=sr.from(e.response.headers))),Promise.reject(e)})}const IS="1.13.2",xu={};["object","boolean","number","function","string","symbol"].forEach((t,e)=>{xu[t]=function(r){return typeof r===t||"a"+(e<1?"n ":" ")+t}});const Ev={};xu.transitional=function(t,e,r){function n(s,i){return"[Axios v"+IS+"] Transitional option '"+s+"'"+i+(r?". "+r:"")}return(s,i,o)=>{if(t===!1)throw new fe(n(i," has been removed"+(e?" in "+e:"")),fe.ERR_DEPRECATED);return e&&!Ev[i]&&(Ev[i]=!0,console.warn(n(i," has been deprecated since v"+e+" and will be removed in the near future"))),t?t(s,i,o):!0}};xu.spelling=function(t){return(e,r)=>(console.warn(`${r} is likely a misspelling of ${t}`),!0)};function IV(t,e,r){if(typeof t!="object")throw new fe("options must be an object",fe.ERR_BAD_OPTION_VALUE);const n=Object.keys(t);let s=n.length;for(;s-- >0;){const i=n[s],o=e[i];if(o){const a=t[i],l=a===void 0||o(a,i,t);if(l!==!0)throw new fe("option "+i+" must be "+l,fe.ERR_BAD_OPTION_VALUE);continue}if(r!==!0)throw new fe("Unknown option "+i,fe.ERR_BAD_OPTION)}}const Pl={assertOptions:IV,validators:xu},Wr=Pl.validators;let Ms=class{constructor(e){this.defaults=e||{},this.interceptors={request:new uv,response:new uv}}async request(e,r){try{return await this._request(e,r)}catch(n){if(n instanceof Error){let s={};Error.captureStackTrace?Error.captureStackTrace(s):s=new Error;const i=s.stack?s.stack.replace(/^.+\n/,""):"";try{n.stack?i&&!String(n.stack).endsWith(i.replace(/^.+\n.+\n/,""))&&(n.stack+=`
`+i):n.stack=i}catch{}}throw n}}_request(e,r){typeof e=="string"?(r=r||{},r.url=e):r=e||{},r=Ws(this.defaults,r);const{transitional:n,paramsSerializer:s,headers:i}=r;n!==void 0&&Pl.assertOptions(n,{silentJSONParsing:Wr.transitional(Wr.boolean),forcedJSONParsing:Wr.transitional(Wr.boolean),clarifyTimeoutError:Wr.transitional(Wr.boolean)},!1),s!=null&&(M.isFunction(s)?r.paramsSerializer={serialize:s}:Pl.assertOptions(s,{encode:Wr.function,serialize:Wr.function},!0)),r.allowAbsoluteUrls!==void 0||(this.defaults.allowAbsoluteUrls!==void 0?r.allowAbsoluteUrls=this.defaults.allowAbsoluteUrls:r.allowAbsoluteUrls=!0),Pl.assertOptions(r,{baseUrl:Wr.spelling("baseURL"),withXsrfToken:Wr.spelling("withXSRFToken")},!0),r.method=(r.method||this.defaults.method||"get").toLowerCase();let o=i&&M.merge(i.common,i[r.method]);i&&M.forEach(["delete","get","head","post","put","patch","common"],h=>{delete i[h]}),r.headers=sr.concat(o,i);const a=[];let l=!0;this.interceptors.request.forEach(function(h){typeof h.runWhen=="function"&&h.runWhen(r)===!1||(l=l&&h.synchronous,a.unshift(h.fulfilled,h.rejected))});const u=[];this.interceptors.response.forEach(function(h){u.push(h.fulfilled,h.rejected)});let c,d=0,p;if(!l){const h=[_v.bind(this),void 0];for(h.unshift(...a),h.push(...u),p=h.length,c=Promise.resolve(r);d<p;)c=c.then(h[d++],h[d++]);return c}p=a.length;let f=r;for(;d<p;){const h=a[d++],m=a[d++];try{f=h(f)}catch(g){m.call(this,g);break}}try{c=_v.call(this,f)}catch(h){return Promise.reject(h)}for(d=0,p=u.length;d<p;)c=c.then(u[d++],u[d++]);return c}getUri(e){e=Ws(this.defaults,e);const r=PS(e.baseURL,e.url,e.allowAbsoluteUrls);return CS(r,e.params,e.paramsSerializer)}};M.forEach(["delete","get","head","options"],function(t){Ms.prototype[t]=function(e,r){return this.request(Ws(r||{},{method:t,url:e,data:(r||{}).data}))}});M.forEach(["post","put","patch"],function(t){function e(r){return function(n,s,i){return this.request(Ws(i||{},{method:t,headers:r?{"Content-Type":"multipart/form-data"}:{},url:n,data:s}))}}Ms.prototype[t]=e(),Ms.prototype[t+"Form"]=e(!0)});let LV=class LS{constructor(e){if(typeof e!="function")throw new TypeError("executor must be a function.");let r;this.promise=new Promise(function(s){r=s});const n=this;this.promise.then(s=>{if(!n._listeners)return;let i=n._listeners.length;for(;i-- >0;)n._listeners[i](s);n._listeners=null}),this.promise.then=s=>{let i;const o=new Promise(a=>{n.subscribe(a),i=a}).then(s);return o.cancel=function(){n.unsubscribe(i)},o},e(function(s,i,o){n.reason||(n.reason=new oo(s,i,o),r(n.reason))})}throwIfRequested(){if(this.reason)throw this.reason}subscribe(e){if(this.reason){e(this.reason);return}this._listeners?this._listeners.push(e):this._listeners=[e]}unsubscribe(e){if(!this._listeners)return;const r=this._listeners.indexOf(e);r!==-1&&this._listeners.splice(r,1)}toAbortSignal(){const e=new AbortController,r=n=>{e.abort(n)};return this.subscribe(r),e.signal.unsubscribe=()=>this.unsubscribe(r),e.signal}static source(){let e;return{token:new LS(function(r){e=r}),cancel:e}}};function FV(t){return function(e){return t.apply(null,e)}}function MV(t){return M.isObject(t)&&t.isAxiosError===!0}const Ef={Continue:100,SwitchingProtocols:101,Processing:102,EarlyHints:103,Ok:200,Created:201,Accepted:202,NonAuthoritativeInformation:203,NoContent:204,ResetContent:205,PartialContent:206,MultiStatus:207,AlreadyReported:208,ImUsed:226,MultipleChoices:300,MovedPermanently:301,Found:302,SeeOther:303,NotModified:304,UseProxy:305,Unused:306,TemporaryRedirect:307,PermanentRedirect:308,BadRequest:400,Unauthorized:401,PaymentRequired:402,Forbidden:403,NotFound:404,MethodNotAllowed:405,NotAcceptable:406,ProxyAuthenticationRequired:407,RequestTimeout:408,Conflict:409,Gone:410,LengthRequired:411,PreconditionFailed:412,PayloadTooLarge:413,UriTooLong:414,UnsupportedMediaType:415,RangeNotSatisfiable:416,ExpectationFailed:417,ImATeapot:418,MisdirectedRequest:421,UnprocessableEntity:422,Locked:423,FailedDependency:424,TooEarly:425,UpgradeRequired:426,PreconditionRequired:428,TooManyRequests:429,RequestHeaderFieldsTooLarge:431,UnavailableForLegalReasons:451,InternalServerError:500,NotImplemented:501,BadGateway:502,ServiceUnavailable:503,GatewayTimeout:504,HttpVersionNotSupported:505,VariantAlsoNegotiates:506,InsufficientStorage:507,LoopDetected:508,NotExtended:510,NetworkAuthenticationRequired:511,WebServerIsDown:521,ConnectionTimedOut:522,OriginIsUnreachable:523,TimeoutOccurred:524,SslHandshakeFailed:525,InvalidSslCertificate:526};Object.entries(Ef).forEach(([t,e])=>{Ef[e]=t});function FS(t){const e=new Ms(t),r=fS(Ms.prototype.request,e);return M.extend(r,Ms.prototype,e,{allOwnKeys:!0}),M.extend(r,e,null,{allOwnKeys:!0}),r.create=function(n){return FS(Ws(t,n))},r}const Ye=FS(Ma);Ye.Axios=Ms;Ye.CanceledError=oo;Ye.CancelToken=LV;Ye.isCancel=TS;Ye.VERSION=IS;Ye.toFormData=Su;Ye.AxiosError=fe;Ye.Cancel=Ye.CanceledError;Ye.all=function(t){return Promise.all(t)};Ye.spread=FV;Ye.isAxiosError=MV;Ye.mergeConfig=Ws;Ye.AxiosHeaders=sr;Ye.formToJSON=t=>$S(M.isHTMLForm(t)?new FormData(t):t);Ye.getAdapter=NS.getAdapter;Ye.HttpStatusCode=Ef;Ye.default=Ye;const{Axios:W6,AxiosError:K6,CanceledError:G6,isCancel:J6,CancelToken:X6,VERSION:Y6,all:Q6,Cancel:Z6,isAxiosError:e8,spread:t8,toFormData:r8,AxiosHeaders:n8,HttpStatusCode:s8,formToJSON:i8,getAdapter:o8,mergeConfig:a8}=Ye;class DV{constructor(){this.refreshPromise=null,this.tokenName=null,this.tokenValue=null,this.refreshPromise=null}async getToken(){return this.tokenValue||await this.refreshToken(),this.tokenValue}async refreshToken(){return this.refreshPromise?this.refreshPromise:(this.refreshPromise=Da.get("users/session-info").then(({data:e})=>{const{csrfTokenName:r,csrfTokenValue:n}=e;return this.tokenName=r??null,this.tokenValue=n??null,this.tokenValue}).finally(()=>{this.refreshPromise=null}),this.refreshPromise)}clearToken(){this.tokenValue=null}}function VV(t=""){return`https://craft6-dev.ddev.site/admin/actions/${t}`}function BV(){let t={"X-Registered-Asset-Bundles":[...new Set(Craft.registeredAssetBundles)].join(","),"X-Registered-Js-Files":[...new Set(Craft.registeredJsFiles)].join(",")};return Craft.csrfTokenValue&&(t["X-CSRF-Token"]=Craft.csrfTokenValue),t}const Da=Ye.create({baseURL:VV()}),Sf=new DV;Da.interceptors.request.use(async t=>{t.headers.set("X-Requested-With","XMLHttpRequest");const e=BV();if(Object.entries(e).forEach(([r,n])=>{t.headers.set(r,n)}),["post","put","patch","delete"].includes(t.method?.toLowerCase()||"")&&!t.url?.includes("users/session-info")){const r=await Sf.getToken();r&&t.headers.set("X-CSRF-Token",r)}return t});Da.interceptors.response.use(t=>t,async t=>{const e=t.config;if(t.response?.status===419||t.response?.status===403&&!e._retry){e._retry=!0;try{return Sf.clearToken(),e.headers["X-CSRF-Token"]=await Sf.refreshToken(),Ye(e)}catch(r){return console.error("Failed to refresh CSRF token:",r),Promise.reject(r)}}return Promise.reject(t)});let Ol=!1,Ds=null;async function UV(t){if(!Ol){if(Ds)return Ds;Ol=!0;try{return(await Da.post("app/api-headers",void 0,{cancelToken:t})).data}catch{}finally{Ol=!1}}}const mm=Ye.create({baseURL:"https://api.craftcms.com/v1/"});async function jV(t){return Ds?Object.entries(Ds).forEach(([e,r])=>{t.headers.set(e,r)}):(t.params=t.params||{},t.params.processCraftHeaders=1),t}async function qV(t,e){if(Ds)return;const{data:r}=await Da.post("app/process-api-response-headers",{headers:t},{cancelToken:e});return Ds=r,Ol=!1,Ds}async function HV(t){return await qV(t.headers,t.config.cancelToken),t}mm.interceptors.request.use(async t=>{const{cancelToken:e}=t,r=await UV(e);r&&Object.entries(r).forEach(([s,i])=>{t.headers.set(s,i)});const n={...t,params:{...Craft.apiParams||{},...t.params,v:new Date().getTime()}};return r||(n.params.processCraftHeaders=1),Craft.httpProxy&&(n.proxy=Craft.httpProxy),n});mm.interceptors.request.use(jV);mm.interceptors.response.use(HV);var zV=function(t,e,r,n,s){if(typeof e=="function"?t!==e||!0:!e.has(t))throw new TypeError("Cannot write private member to an object whose class did not declare it");return e.set(t,r),r},Sv=function(t,e,r,n){if(typeof e=="function"?t!==e||!n:!e.has(t))throw new TypeError("Cannot read private member from an object whose class did not declare it");return r==="m"?n:r==="a"?n.call(t):n?n.value:e.get(t)},So;class WV{formatToParts(e){const r=[];for(const n of e)r.push({type:"element",value:n}),r.push({type:"literal",value:", "});return r.slice(0,-1)}}const KV=typeof Intl<"u"&&Intl.ListFormat||WV,GV=[["years","year"],["months","month"],["weeks","week"],["days","day"],["hours","hour"],["minutes","minute"],["seconds","second"],["milliseconds","millisecond"]],JV={minimumIntegerDigits:2};class XV{constructor(e,r={}){So.set(this,void 0);let n=String(r.style||"short");n!=="long"&&n!=="short"&&n!=="narrow"&&n!=="digital"&&(n="short");let s=n==="digital"?"numeric":n;const i=r.hours||s;s=i==="2-digit"?"numeric":i;const o=r.minutes||s;s=o==="2-digit"?"numeric":o;const a=r.seconds||s;s=a==="2-digit"?"numeric":a;const l=r.milliseconds||s;zV(this,So,{locale:e,style:n,years:r.years||n==="digital"?"short":n,yearsDisplay:r.yearsDisplay==="always"?"always":"auto",months:r.months||n==="digital"?"short":n,monthsDisplay:r.monthsDisplay==="always"?"always":"auto",weeks:r.weeks||n==="digital"?"short":n,weeksDisplay:r.weeksDisplay==="always"?"always":"auto",days:r.days||n==="digital"?"short":n,daysDisplay:r.daysDisplay==="always"?"always":"auto",hours:i,hoursDisplay:r.hoursDisplay==="always"||n==="digital"?"always":"auto",minutes:o,minutesDisplay:r.minutesDisplay==="always"||n==="digital"?"always":"auto",seconds:a,secondsDisplay:r.secondsDisplay==="always"||n==="digital"?"always":"auto",milliseconds:l,millisecondsDisplay:r.millisecondsDisplay==="always"?"always":"auto"})}resolvedOptions(){return Sv(this,So,"f")}formatToParts(e){const r=[],n=Sv(this,So,"f"),s=n.style,i=n.locale;for(const[o,a]of GV){const l=e[o];if(n[`${o}Display`]==="auto"&&!l)continue;const u=n[o],c=u==="2-digit"?JV:u==="numeric"?{}:{style:"unit",unit:a,unitDisplay:u};let d=new Intl.NumberFormat(i,c).format(l);o==="months"&&(u==="narrow"||s==="narrow"&&d.endsWith("m"))&&(d=d.replace(/(\d+)m$/,"$1mo")),r.push(d)}return new KV(i,{type:"unit",style:s==="digital"?"short":s}).formatToParts(r)}format(e){return this.formatToParts(e).map(r=>r.value).join("")}}So=new WeakMap;const MS=/^[-+]?P(?:(\d+)Y)?(?:(\d+)M)?(?:(\d+)W)?(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?)?$/,Sc=["year","month","week","day","hour","minute","second","millisecond"],YV=t=>MS.test(t);let ji=class bs{constructor(e=0,r=0,n=0,s=0,i=0,o=0,a=0,l=0){this.years=e,this.months=r,this.weeks=n,this.days=s,this.hours=i,this.minutes=o,this.seconds=a,this.milliseconds=l,this.years||(this.years=0),this.sign||(this.sign=Math.sign(this.years)),this.months||(this.months=0),this.sign||(this.sign=Math.sign(this.months)),this.weeks||(this.weeks=0),this.sign||(this.sign=Math.sign(this.weeks)),this.days||(this.days=0),this.sign||(this.sign=Math.sign(this.days)),this.hours||(this.hours=0),this.sign||(this.sign=Math.sign(this.hours)),this.minutes||(this.minutes=0),this.sign||(this.sign=Math.sign(this.minutes)),this.seconds||(this.seconds=0),this.sign||(this.sign=Math.sign(this.seconds)),this.milliseconds||(this.milliseconds=0),this.sign||(this.sign=Math.sign(this.milliseconds)),this.blank=this.sign===0}abs(){return new bs(Math.abs(this.years),Math.abs(this.months),Math.abs(this.weeks),Math.abs(this.days),Math.abs(this.hours),Math.abs(this.minutes),Math.abs(this.seconds),Math.abs(this.milliseconds))}static from(e){var r;if(typeof e=="string"){const n=String(e).trim(),s=n.startsWith("-")?-1:1,i=(r=n.match(MS))===null||r===void 0?void 0:r.slice(1).map(o=>(Number(o)||0)*s);return i?new bs(...i):new bs}else if(typeof e=="object"){const{years:n,months:s,weeks:i,days:o,hours:a,minutes:l,seconds:u,milliseconds:c}=e;return new bs(n,s,i,o,a,l,u,c)}throw new RangeError("invalid duration")}static compare(e,r){const n=Date.now(),s=Math.abs(xv(n,bs.from(e)).getTime()-n),i=Math.abs(xv(n,bs.from(r)).getTime()-n);return s>i?-1:s<i?1:0}toLocaleString(e,r){return new XV(e,r).format(this)}};function xv(t,e){const r=new Date(t);return e.sign<0?(r.setUTCSeconds(r.getUTCSeconds()+e.seconds),r.setUTCMinutes(r.getUTCMinutes()+e.minutes),r.setUTCHours(r.getUTCHours()+e.hours),r.setUTCDate(r.getUTCDate()+e.weeks*7+e.days),r.setUTCMonth(r.getUTCMonth()+e.months),r.setUTCFullYear(r.getUTCFullYear()+e.years)):(r.setUTCFullYear(r.getUTCFullYear()+e.years),r.setUTCMonth(r.getUTCMonth()+e.months),r.setUTCDate(r.getUTCDate()+e.weeks*7+e.days),r.setUTCHours(r.getUTCHours()+e.hours),r.setUTCMinutes(r.getUTCMinutes()+e.minutes),r.setUTCSeconds(r.getUTCSeconds()+e.seconds)),r}function QV(t,e="second",r=Date.now()){const n=t.getTime()-r;if(n===0)return new ji;const s=Math.sign(n),i=Math.abs(n),o=Math.floor(i/1e3),a=Math.floor(o/60),l=Math.floor(a/60),u=Math.floor(l/24),c=Math.floor(u/30),d=Math.floor(c/12),p=Sc.indexOf(e)||Sc.length;return new ji(p>=0?d*s:0,p>=1?(c-d*12)*s:0,0,p>=3?(u-c*30)*s:0,p>=4?(l-u*24)*s:0,p>=5?(a-l*60)*s:0,p>=6?(o-a*60)*s:0,p>=7?(i-o*1e3)*s:0)}function DS(t,{relativeTo:e=Date.now()}={}){if(e=new Date(e),t.blank)return t;const r=t.sign;let n=Math.abs(t.years),s=Math.abs(t.months),i=Math.abs(t.weeks),o=Math.abs(t.days),a=Math.abs(t.hours),l=Math.abs(t.minutes),u=Math.abs(t.seconds),c=Math.abs(t.milliseconds);c>=900&&(u+=Math.round(c/1e3)),(u||l||a||o||i||s||n)&&(c=0),u>=55&&(l+=Math.round(u/60)),(l||a||o||i||s||n)&&(u=0),l>=55&&(a+=Math.round(l/60)),(a||o||i||s||n)&&(l=0),o&&a>=12&&(o+=Math.round(a/24)),!o&&a>=21&&(o+=Math.round(a/24)),(o||i||s||n)&&(a=0);const d=e.getFullYear(),p=e.getMonth(),f=e.getDate();if(o>=27||n+s+o){const h=new Date(e);h.setDate(1),h.setMonth(p+s*r+1),h.setDate(0);const m=Math.max(0,f-h.getDate()),g=new Date(e);g.setFullYear(d+n*r),g.setDate(f-m),g.setMonth(p+s*r),g.setDate(f-m+o*r);const v=g.getFullYear()-e.getFullYear(),b=g.getMonth()-e.getMonth(),w=Math.abs(Math.round((Number(g)-Number(e))/864e5))+m,_=Math.abs(v*12+b);w<27?(o>=6?(i+=Math.round(o/7),o=0):o=w,s=n=0):_<=11?(s=_,n=0):(s=0,n=v*r),(s||n)&&(o=0)}return n&&(s=0),i>=4&&(s+=Math.round(i/4)),(s||n)&&(i=0),o&&i&&!s&&!n&&(i+=Math.round(o/7),o=0),new ji(n*r,s*r,i*r,o*r,a*r,l*r,u*r,c*r)}function ZV(t,e){const r=DS(t,e);if(r.blank)return[0,"second"];for(const n of Sc){if(n==="millisecond")continue;const s=r[`${n}s`];if(s)return[s,n]}return[0,"second"]}var Be=function(t,e,r,n){if(r==="a"&&!n)throw new TypeError("Private accessor was defined without a getter");if(typeof e=="function"?t!==e||!n:!e.has(t))throw new TypeError("Cannot read private member from an object whose class did not declare it");return r==="m"?n:r==="a"?n.call(t):n?n.value:e.get(t)},ul=function(t,e,r,n,s){if(typeof e=="function"?t!==e||!0:!e.has(t))throw new TypeError("Cannot write private member to an object whose class did not declare it");return e.set(t,r),r},xt,xo,Co,ai,$s,xf,VS,BS,US,jS,qS,Cf,HS,di;const eB=globalThis.HTMLElement||null,hh=new ji,Cv=new ji(0,0,0,0,0,1);class tB extends Event{constructor(e,r,n,s){super("relative-time-updated",{bubbles:!0,composed:!0}),this.oldText=e,this.newText=r,this.oldTitle=n,this.newTitle=s}}function Av(t){if(!t.date)return 1/0;if(t.format==="duration"||t.format==="elapsed"){const r=t.precision;if(r==="second")return 1e3;if(r==="minute")return 60*1e3}const e=Math.abs(Date.now()-t.date.getTime());return e<60*1e3?1e3:e<3600*1e3?60*1e3:3600*1e3}const fh=new class{constructor(){this.elements=new Set,this.time=1/0,this.timer=-1}observe(t){if(this.elements.has(t))return;this.elements.add(t);const e=t.date;if(e&&e.getTime()){const r=Av(t),n=Date.now()+r;n<this.time&&(clearTimeout(this.timer),this.timer=setTimeout(()=>this.update(),r),this.time=n)}}unobserve(t){this.elements.has(t)&&this.elements.delete(t)}update(){if(clearTimeout(this.timer),!this.elements.size)return;let t=1/0;for(const e of this.elements)t=Math.min(t,Av(e)),e.update();this.time=Math.min(3600*1e3,t),this.timer=setTimeout(()=>this.update(),this.time),this.time+=Date.now()}};class rB extends eB{constructor(){super(...arguments),xt.add(this),xo.set(this,!1),Co.set(this,!1),$s.set(this,this.shadowRoot?this.shadowRoot:this.attachShadow?this.attachShadow({mode:"open"}):this),di.set(this,null)}static define(e="relative-time",r=customElements){return r.define(e,this),this}get timeZone(){var e;return((e=this.closest("[time-zone]"))===null||e===void 0?void 0:e.getAttribute("time-zone"))||this.ownerDocument.documentElement.getAttribute("time-zone")||void 0}static get observedAttributes(){return["second","minute","hour","weekday","day","month","year","time-zone-name","prefix","threshold","tense","precision","format","format-style","no-title","datetime","lang","title","aria-hidden","time-zone"]}get onRelativeTimeUpdated(){return Be(this,di,"f")}set onRelativeTimeUpdated(e){Be(this,di,"f")&&this.removeEventListener("relative-time-updated",Be(this,di,"f")),ul(this,di,typeof e=="object"||typeof e=="function"?e:null),typeof e=="function"&&this.addEventListener("relative-time-updated",e)}get second(){const e=this.getAttribute("second");if(e==="numeric"||e==="2-digit")return e}set second(e){this.setAttribute("second",e||"")}get minute(){const e=this.getAttribute("minute");if(e==="numeric"||e==="2-digit")return e}set minute(e){this.setAttribute("minute",e||"")}get hour(){const e=this.getAttribute("hour");if(e==="numeric"||e==="2-digit")return e}set hour(e){this.setAttribute("hour",e||"")}get weekday(){const e=this.getAttribute("weekday");if(e==="long"||e==="short"||e==="narrow")return e;if(this.format==="datetime"&&e!=="")return this.formatStyle}set weekday(e){this.setAttribute("weekday",e||"")}get day(){var e;const r=(e=this.getAttribute("day"))!==null&&e!==void 0?e:"numeric";if(r==="numeric"||r==="2-digit")return r}set day(e){this.setAttribute("day",e||"")}get month(){const e=this.format;let r=this.getAttribute("month");if(r!==""&&(r??(r=e==="datetime"?this.formatStyle:"short"),r==="numeric"||r==="2-digit"||r==="short"||r==="long"||r==="narrow"))return r}set month(e){this.setAttribute("month",e||"")}get year(){var e;const r=this.getAttribute("year");if(r==="numeric"||r==="2-digit")return r;if(!this.hasAttribute("year")&&new Date().getUTCFullYear()!==((e=this.date)===null||e===void 0?void 0:e.getUTCFullYear()))return"numeric"}set year(e){this.setAttribute("year",e||"")}get timeZoneName(){const e=this.getAttribute("time-zone-name");if(e==="long"||e==="short"||e==="shortOffset"||e==="longOffset"||e==="shortGeneric"||e==="longGeneric")return e}set timeZoneName(e){this.setAttribute("time-zone-name",e||"")}get prefix(){var e;return(e=this.getAttribute("prefix"))!==null&&e!==void 0?e:this.format==="datetime"?"":"on"}set prefix(e){this.setAttribute("prefix",e)}get threshold(){const e=this.getAttribute("threshold");return e&&YV(e)?e:"P30D"}set threshold(e){this.setAttribute("threshold",e)}get tense(){const e=this.getAttribute("tense");return e==="past"?"past":e==="future"?"future":"auto"}set tense(e){this.setAttribute("tense",e)}get precision(){const e=this.getAttribute("precision");return Sc.includes(e)?e:this.format==="micro"?"minute":"second"}set precision(e){this.setAttribute("precision",e)}get format(){const e=this.getAttribute("format");return e==="datetime"?"datetime":e==="relative"?"relative":e==="duration"?"duration":e==="micro"?"micro":e==="elapsed"?"elapsed":"auto"}set format(e){this.setAttribute("format",e)}get formatStyle(){const e=this.getAttribute("format-style");if(e==="long")return"long";if(e==="short")return"short";if(e==="narrow")return"narrow";const r=this.format;return r==="elapsed"||r==="micro"?"narrow":r==="datetime"?"short":"long"}set formatStyle(e){this.setAttribute("format-style",e)}get noTitle(){return this.hasAttribute("no-title")}set noTitle(e){this.toggleAttribute("no-title",e)}get datetime(){return this.getAttribute("datetime")||""}set datetime(e){this.setAttribute("datetime",e)}get date(){const e=Date.parse(this.datetime);return Number.isNaN(e)?null:new Date(e)}set date(e){this.datetime=e?.toISOString()||""}connectedCallback(){this.update()}disconnectedCallback(){fh.unobserve(this)}attributeChangedCallback(e,r,n){r!==n&&(e==="title"&&ul(this,xo,n!==null&&(this.date&&Be(this,xt,"m",xf).call(this,this.date))!==n),!Be(this,Co,"f")&&!(e==="title"&&Be(this,xo,"f"))&&ul(this,Co,(async()=>{await Promise.resolve(),this.update(),ul(this,Co,!1,"f")})()))}update(){const e=Be(this,$s,"f").textContent||this.textContent||"",r=this.getAttribute("title")||"";let n=r;const s=this.date;if(typeof Intl>"u"||!Intl.DateTimeFormat||!s){Be(this,$s,"f").textContent=e;return}const i=Date.now();Be(this,xo,"f")||(n=Be(this,xt,"m",xf).call(this,s)||"",n&&!this.noTitle&&this.setAttribute("title",n));const o=QV(s,this.precision,i),a=Be(this,xt,"m",VS).call(this,o);let l=e;const u=Be(this,xt,"m",HS).call(this,a);u?l=Be(this,xt,"m",qS).call(this,s):a==="duration"?l=Be(this,xt,"m",BS).call(this,o):a==="relative"?l=Be(this,xt,"m",US).call(this,o):l=Be(this,xt,"m",jS).call(this,s),l?Be(this,xt,"m",Cf).call(this,l):this.shadowRoot===Be(this,$s,"f")&&this.textContent&&Be(this,xt,"m",Cf).call(this,this.textContent),(l!==e||n!==r)&&this.dispatchEvent(new tB(e,l,r,n)),(a==="relative"||a==="duration")&&!u?fh.observe(this):fh.unobserve(this)}}xo=new WeakMap,Co=new WeakMap,$s=new WeakMap,di=new WeakMap,xt=new WeakSet,ai=function(){var t;const e=((t=this.closest("[lang]"))===null||t===void 0?void 0:t.getAttribute("lang"))||this.ownerDocument.documentElement.getAttribute("lang");try{return new Intl.Locale(e??"").toString()}catch{return"default"}},xf=function(t){return new Intl.DateTimeFormat(Be(this,xt,"a",ai),{day:"numeric",month:"short",year:"numeric",hour:"numeric",minute:"2-digit",timeZoneName:"short",timeZone:this.timeZone}).format(t)},VS=function(t){const e=this.format;if(e==="datetime")return"datetime";if(e==="duration"||e==="elapsed"||e==="micro")return"duration";if((e==="auto"||e==="relative")&&typeof Intl<"u"&&Intl.RelativeTimeFormat){const r=this.tense;if(r==="past"||r==="future"||ji.compare(t,this.threshold)===1)return"relative"}return"datetime"},BS=function(t){const e=Be(this,xt,"a",ai),r=this.format,n=this.formatStyle,s=this.tense;let i=hh;r==="micro"?(t=DS(t),i=Cv,t.months===0&&(this.tense==="past"&&t.sign!==-1||this.tense==="future"&&t.sign!==1)&&(t=Cv)):(s==="past"&&t.sign!==-1||s==="future"&&t.sign!==1)&&(t=i);const o=`${this.precision}sDisplay`;return t.blank?i.toLocaleString(e,{style:n,[o]:"always"}):t.abs().toLocaleString(e,{style:n})},US=function(t){const e=new Intl.RelativeTimeFormat(Be(this,xt,"a",ai),{numeric:"auto",style:this.formatStyle}),r=this.tense;r==="future"&&t.sign!==1&&(t=hh),r==="past"&&t.sign!==-1&&(t=hh);const[n,s]=ZV(t);return s==="second"&&n<10?e.format(0,this.precision==="millisecond"?"second":this.precision):e.format(n,s)},jS=function(t){const e=new Intl.DateTimeFormat(Be(this,xt,"a",ai),{second:this.second,minute:this.minute,hour:this.hour,weekday:this.weekday,day:this.day,month:this.month,year:this.year,timeZoneName:this.timeZoneName,timeZone:this.timeZone});return`${this.prefix} ${e.format(t)}`.trim()},qS=function(t){return new Intl.DateTimeFormat(Be(this,xt,"a",ai),{day:"numeric",month:"short",year:"numeric",hour:"numeric",minute:"2-digit",timeZoneName:"short",timeZone:this.timeZone}).format(t)},Cf=function(t){if(this.hasAttribute("aria-hidden")&&this.getAttribute("aria-hidden")==="true"){const e=document.createElement("span");e.setAttribute("aria-hidden","true"),e.textContent=t,Be(this,$s,"f").replaceChildren(e)}else Be(this,$s,"f").textContent=t},HS=function(t){var e;return t==="duration"?!1:this.ownerDocument.documentElement.getAttribute("data-prefers-absolute-time")==="true"||((e=this.ownerDocument.body)===null||e===void 0?void 0:e.getAttribute("data-prefers-absolute-time"))==="true"};const $v=typeof globalThis<"u"?globalThis:window;try{$v.RelativeTimeElement=rB.define()}catch(t){if(!($v.DOMException&&t instanceof DOMException&&t.name==="NotSupportedError")&&!(t instanceof ReferenceError))throw t}const Rl=globalThis,gm=Rl.ShadowRoot&&(Rl.ShadyCSS===void 0||Rl.ShadyCSS.nativeShadow)&&"adoptedStyleSheets"in Document.prototype&&"replace"in CSSStyleSheet.prototype,zS=Symbol(),Tv=new WeakMap;let nB=class{constructor(e,r,n){if(this._$cssResult$=!0,n!==zS)throw Error("CSSResult is not constructable. Use `unsafeCSS` or `css` instead.");this.cssText=e,this.t=r}get styleSheet(){let e=this.o;const r=this.t;if(gm&&e===void 0){const n=r!==void 0&&r.length===1;n&&(e=Tv.get(r)),e===void 0&&((this.o=e=new CSSStyleSheet).replaceSync(this.cssText),n&&Tv.set(r,e))}return e}toString(){return this.cssText}};const sB=t=>new nB(typeof t=="string"?t:t+"",void 0,zS),iB=(t,e)=>{if(gm)t.adoptedStyleSheets=e.map((r=>r instanceof CSSStyleSheet?r:r.styleSheet));else for(const r of e){const n=document.createElement("style"),s=Rl.litNonce;s!==void 0&&n.setAttribute("nonce",s),n.textContent=r.cssText,t.appendChild(n)}},kv=gm?t=>t:t=>t instanceof CSSStyleSheet?(e=>{let r="";for(const n of e.cssRules)r+=n.cssText;return sB(r)})(t):t;const{is:oB,defineProperty:aB,getOwnPropertyDescriptor:lB,getOwnPropertyNames:cB,getOwnPropertySymbols:uB,getPrototypeOf:dB}=Object,Cu=globalThis,Pv=Cu.trustedTypes,hB=Pv?Pv.emptyScript:"",fB=Cu.reactiveElementPolyfillSupport,Bo=(t,e)=>t,xc={toAttribute(t,e){switch(e){case Boolean:t=t?hB:null;break;case Object:case Array:t=t==null?t:JSON.stringify(t)}return t},fromAttribute(t,e){let r=t;switch(e){case Boolean:r=t!==null;break;case Number:r=t===null?null:Number(t);break;case Object:case Array:try{r=JSON.parse(t)}catch{r=null}}return r}},ym=(t,e)=>!oB(t,e),Ov={attribute:!0,type:String,converter:xc,reflect:!1,useDefault:!1,hasChanged:ym};Symbol.metadata??=Symbol("metadata"),Cu.litPropertyMetadata??=new WeakMap;class hi extends HTMLElement{static addInitializer(e){this._$Ei(),(this.l??=[]).push(e)}static get observedAttributes(){return this.finalize(),this._$Eh&&[...this._$Eh.keys()]}static createProperty(e,r=Ov){if(r.state&&(r.attribute=!1),this._$Ei(),this.prototype.hasOwnProperty(e)&&((r=Object.create(r)).wrapped=!0),this.elementProperties.set(e,r),!r.noAccessor){const n=Symbol(),s=this.getPropertyDescriptor(e,n,r);s!==void 0&&aB(this.prototype,e,s)}}static getPropertyDescriptor(e,r,n){const{get:s,set:i}=lB(this.prototype,e)??{get(){return this[r]},set(o){this[r]=o}};return{get:s,set(o){const a=s?.call(this);i?.call(this,o),this.requestUpdate(e,a,n)},configurable:!0,enumerable:!0}}static getPropertyOptions(e){return this.elementProperties.get(e)??Ov}static _$Ei(){if(this.hasOwnProperty(Bo("elementProperties")))return;const e=dB(this);e.finalize(),e.l!==void 0&&(this.l=[...e.l]),this.elementProperties=new Map(e.elementProperties)}static finalize(){if(this.hasOwnProperty(Bo("finalized")))return;if(this.finalized=!0,this._$Ei(),this.hasOwnProperty(Bo("properties"))){const r=this.properties,n=[...cB(r),...uB(r)];for(const s of n)this.createProperty(s,r[s])}const e=this[Symbol.metadata];if(e!==null){const r=litPropertyMetadata.get(e);if(r!==void 0)for(const[n,s]of r)this.elementProperties.set(n,s)}this._$Eh=new Map;for(const[r,n]of this.elementProperties){const s=this._$Eu(r,n);s!==void 0&&this._$Eh.set(s,r)}this.elementStyles=this.finalizeStyles(this.styles)}static finalizeStyles(e){const r=[];if(Array.isArray(e)){const n=new Set(e.flat(1/0).reverse());for(const s of n)r.unshift(kv(s))}else e!==void 0&&r.push(kv(e));return r}static _$Eu(e,r){const n=r.attribute;return n===!1?void 0:typeof n=="string"?n:typeof e=="string"?e.toLowerCase():void 0}constructor(){super(),this._$Ep=void 0,this.isUpdatePending=!1,this.hasUpdated=!1,this._$Em=null,this._$Ev()}_$Ev(){this._$ES=new Promise((e=>this.enableUpdating=e)),this._$AL=new Map,this._$E_(),this.requestUpdate(),this.constructor.l?.forEach((e=>e(this)))}addController(e){(this._$EO??=new Set).add(e),this.renderRoot!==void 0&&this.isConnected&&e.hostConnected?.()}removeController(e){this._$EO?.delete(e)}_$E_(){const e=new Map,r=this.constructor.elementProperties;for(const n of r.keys())this.hasOwnProperty(n)&&(e.set(n,this[n]),delete this[n]);e.size>0&&(this._$Ep=e)}createRenderRoot(){const e=this.shadowRoot??this.attachShadow(this.constructor.shadowRootOptions);return iB(e,this.constructor.elementStyles),e}connectedCallback(){this.renderRoot??=this.createRenderRoot(),this.enableUpdating(!0),this._$EO?.forEach((e=>e.hostConnected?.()))}enableUpdating(e){}disconnectedCallback(){this._$EO?.forEach((e=>e.hostDisconnected?.()))}attributeChangedCallback(e,r,n){this._$AK(e,n)}_$ET(e,r){const n=this.constructor.elementProperties.get(e),s=this.constructor._$Eu(e,n);if(s!==void 0&&n.reflect===!0){const i=(n.converter?.toAttribute!==void 0?n.converter:xc).toAttribute(r,n.type);this._$Em=e,i==null?this.removeAttribute(s):this.setAttribute(s,i),this._$Em=null}}_$AK(e,r){const n=this.constructor,s=n._$Eh.get(e);if(s!==void 0&&this._$Em!==s){const i=n.getPropertyOptions(s),o=typeof i.converter=="function"?{fromAttribute:i.converter}:i.converter?.fromAttribute!==void 0?i.converter:xc;this._$Em=s;const a=o.fromAttribute(r,i.type);this[s]=a??this._$Ej?.get(s)??a,this._$Em=null}}requestUpdate(e,r,n){if(e!==void 0){const s=this.constructor,i=this[e];if(n??=s.getPropertyOptions(e),!((n.hasChanged??ym)(i,r)||n.useDefault&&n.reflect&&i===this._$Ej?.get(e)&&!this.hasAttribute(s._$Eu(e,n))))return;this.C(e,r,n)}this.isUpdatePending===!1&&(this._$ES=this._$EP())}C(e,r,{useDefault:n,reflect:s,wrapped:i},o){n&&!(this._$Ej??=new Map).has(e)&&(this._$Ej.set(e,o??r??this[e]),i!==!0||o!==void 0)||(this._$AL.has(e)||(this.hasUpdated||n||(r=void 0),this._$AL.set(e,r)),s===!0&&this._$Em!==e&&(this._$Eq??=new Set).add(e))}async _$EP(){this.isUpdatePending=!0;try{await this._$ES}catch(r){Promise.reject(r)}const e=this.scheduleUpdate();return e!=null&&await e,!this.isUpdatePending}scheduleUpdate(){return this.performUpdate()}performUpdate(){if(!this.isUpdatePending)return;if(!this.hasUpdated){if(this.renderRoot??=this.createRenderRoot(),this._$Ep){for(const[s,i]of this._$Ep)this[s]=i;this._$Ep=void 0}const n=this.constructor.elementProperties;if(n.size>0)for(const[s,i]of n){const{wrapped:o}=i,a=this[s];o!==!0||this._$AL.has(s)||a===void 0||this.C(s,void 0,i,a)}}let e=!1;const r=this._$AL;try{e=this.shouldUpdate(r),e?(this.willUpdate(r),this._$EO?.forEach((n=>n.hostUpdate?.())),this.update(r)):this._$EM()}catch(n){throw e=!1,this._$EM(),n}e&&this._$AE(r)}willUpdate(e){}_$AE(e){this._$EO?.forEach((r=>r.hostUpdated?.())),this.hasUpdated||(this.hasUpdated=!0,this.firstUpdated(e)),this.updated(e)}_$EM(){this._$AL=new Map,this.isUpdatePending=!1}get updateComplete(){return this.getUpdateComplete()}getUpdateComplete(){return this._$ES}shouldUpdate(e){return!0}update(e){this._$Eq&&=this._$Eq.forEach((r=>this._$ET(r,this[r]))),this._$EM()}updated(e){}firstUpdated(e){}}hi.elementStyles=[],hi.shadowRootOptions={mode:"open"},hi[Bo("elementProperties")]=new Map,hi[Bo("finalized")]=new Map,fB?.({ReactiveElement:hi}),(Cu.reactiveElementVersions??=[]).push("2.1.1");const bm=globalThis,Cc=bm.trustedTypes,Rv=Cc?Cc.createPolicy("lit-html",{createHTML:t=>t}):void 0,WS="$lit$",Gn=`lit$${Math.random().toFixed(9).slice(2)}$`,KS="?"+Gn,pB=`<${KS}>`,Ks=document,la=()=>Ks.createComment(""),ca=t=>t===null||typeof t!="object"&&typeof t!="function",vm=Array.isArray,mB=t=>vm(t)||typeof t?.[Symbol.iterator]=="function",ph=`[ 	
\f\r]`,bo=/<(?:(!--|\/[^a-zA-Z])|(\/?[a-zA-Z][^>\s]*)|(\/?$))/g,Nv=/-->/g,Iv=/>/g,gs=RegExp(`>|${ph}(?:([^\\s"'>=/]+)(${ph}*=${ph}*(?:[^ 	
\f\r"'\`<>=]|("|')|))|$)`,"g"),Lv=/'/g,Fv=/"/g,GS=/^(?:script|style|textarea|title)$/i,qi=Symbol.for("lit-noChange"),wt=Symbol.for("lit-nothing"),Mv=new WeakMap,Ts=Ks.createTreeWalker(Ks,129);function JS(t,e){if(!vm(t)||!t.hasOwnProperty("raw"))throw Error("invalid template strings array");return Rv!==void 0?Rv.createHTML(e):e}const gB=(t,e)=>{const r=t.length-1,n=[];let s,i=e===2?"<svg>":e===3?"<math>":"",o=bo;for(let a=0;a<r;a++){const l=t[a];let u,c,d=-1,p=0;for(;p<l.length&&(o.lastIndex=p,c=o.exec(l),c!==null);)p=o.lastIndex,o===bo?c[1]==="!--"?o=Nv:c[1]!==void 0?o=Iv:c[2]!==void 0?(GS.test(c[2])&&(s=RegExp("</"+c[2],"g")),o=gs):c[3]!==void 0&&(o=gs):o===gs?c[0]===">"?(o=s??bo,d=-1):c[1]===void 0?d=-2:(d=o.lastIndex-c[2].length,u=c[1],o=c[3]===void 0?gs:c[3]==='"'?Fv:Lv):o===Fv||o===Lv?o=gs:o===Nv||o===Iv?o=bo:(o=gs,s=void 0);const f=o===gs&&t[a+1].startsWith("/>")?" ":"";i+=o===bo?l+pB:d>=0?(n.push(u),l.slice(0,d)+WS+l.slice(d)+Gn+f):l+Gn+(d===-2?a:f)}return[JS(t,i+(t[r]||"<?>")+(e===2?"</svg>":e===3?"</math>":"")),n]};class ua{constructor({strings:e,_$litType$:r},n){let s;this.parts=[];let i=0,o=0;const a=e.length-1,l=this.parts,[u,c]=gB(e,r);if(this.el=ua.createElement(u,n),Ts.currentNode=this.el.content,r===2||r===3){const d=this.el.content.firstChild;d.replaceWith(...d.childNodes)}for(;(s=Ts.nextNode())!==null&&l.length<a;){if(s.nodeType===1){if(s.hasAttributes())for(const d of s.getAttributeNames())if(d.endsWith(WS)){const p=c[o++],f=s.getAttribute(d).split(Gn),h=/([.?@])?(.*)/.exec(p);l.push({type:1,index:i,name:h[2],strings:f,ctor:h[1]==="."?bB:h[1]==="?"?vB:h[1]==="@"?wB:Au}),s.removeAttribute(d)}else d.startsWith(Gn)&&(l.push({type:6,index:i}),s.removeAttribute(d));if(GS.test(s.tagName)){const d=s.textContent.split(Gn),p=d.length-1;if(p>0){s.textContent=Cc?Cc.emptyScript:"";for(let f=0;f<p;f++)s.append(d[f],la()),Ts.nextNode(),l.push({type:2,index:++i});s.append(d[p],la())}}}else if(s.nodeType===8)if(s.data===KS)l.push({type:2,index:i});else{let d=-1;for(;(d=s.data.indexOf(Gn,d+1))!==-1;)l.push({type:7,index:i}),d+=Gn.length-1}i++}}static createElement(e,r){const n=Ks.createElement("template");return n.innerHTML=e,n}}function Hi(t,e,r=t,n){if(e===qi)return e;let s=n!==void 0?r._$Co?.[n]:r._$Cl;const i=ca(e)?void 0:e._$litDirective$;return s?.constructor!==i&&(s?._$AO?.(!1),i===void 0?s=void 0:(s=new i(t),s._$AT(t,r,n)),n!==void 0?(r._$Co??=[])[n]=s:r._$Cl=s),s!==void 0&&(e=Hi(t,s._$AS(t,e.values),s,n)),e}class yB{constructor(e,r){this._$AV=[],this._$AN=void 0,this._$AD=e,this._$AM=r}get parentNode(){return this._$AM.parentNode}get _$AU(){return this._$AM._$AU}u(e){const{el:{content:r},parts:n}=this._$AD,s=(e?.creationScope??Ks).importNode(r,!0);Ts.currentNode=s;let i=Ts.nextNode(),o=0,a=0,l=n[0];for(;l!==void 0;){if(o===l.index){let u;l.type===2?u=new Va(i,i.nextSibling,this,e):l.type===1?u=new l.ctor(i,l.name,l.strings,this,e):l.type===6&&(u=new _B(i,this,e)),this._$AV.push(u),l=n[++a]}o!==l?.index&&(i=Ts.nextNode(),o++)}return Ts.currentNode=Ks,s}p(e){let r=0;for(const n of this._$AV)n!==void 0&&(n.strings!==void 0?(n._$AI(e,n,r),r+=n.strings.length-2):n._$AI(e[r])),r++}}class Va{get _$AU(){return this._$AM?._$AU??this._$Cv}constructor(e,r,n,s){this.type=2,this._$AH=wt,this._$AN=void 0,this._$AA=e,this._$AB=r,this._$AM=n,this.options=s,this._$Cv=s?.isConnected??!0}get parentNode(){let e=this._$AA.parentNode;const r=this._$AM;return r!==void 0&&e?.nodeType===11&&(e=r.parentNode),e}get startNode(){return this._$AA}get endNode(){return this._$AB}_$AI(e,r=this){e=Hi(this,e,r),ca(e)?e===wt||e==null||e===""?(this._$AH!==wt&&this._$AR(),this._$AH=wt):e!==this._$AH&&e!==qi&&this._(e):e._$litType$!==void 0?this.$(e):e.nodeType!==void 0?this.T(e):mB(e)?this.k(e):this._(e)}O(e){return this._$AA.parentNode.insertBefore(e,this._$AB)}T(e){this._$AH!==e&&(this._$AR(),this._$AH=this.O(e))}_(e){this._$AH!==wt&&ca(this._$AH)?this._$AA.nextSibling.data=e:this.T(Ks.createTextNode(e)),this._$AH=e}$(e){const{values:r,_$litType$:n}=e,s=typeof n=="number"?this._$AC(e):(n.el===void 0&&(n.el=ua.createElement(JS(n.h,n.h[0]),this.options)),n);if(this._$AH?._$AD===s)this._$AH.p(r);else{const i=new yB(s,this),o=i.u(this.options);i.p(r),this.T(o),this._$AH=i}}_$AC(e){let r=Mv.get(e.strings);return r===void 0&&Mv.set(e.strings,r=new ua(e)),r}k(e){vm(this._$AH)||(this._$AH=[],this._$AR());const r=this._$AH;let n,s=0;for(const i of e)s===r.length?r.push(n=new Va(this.O(la()),this.O(la()),this,this.options)):n=r[s],n._$AI(i),s++;s<r.length&&(this._$AR(n&&n._$AB.nextSibling,s),r.length=s)}_$AR(e=this._$AA.nextSibling,r){for(this._$AP?.(!1,!0,r);e!==this._$AB;){const n=e.nextSibling;e.remove(),e=n}}setConnected(e){this._$AM===void 0&&(this._$Cv=e,this._$AP?.(e))}}class Au{get tagName(){return this.element.tagName}get _$AU(){return this._$AM._$AU}constructor(e,r,n,s,i){this.type=1,this._$AH=wt,this._$AN=void 0,this.element=e,this.name=r,this._$AM=s,this.options=i,n.length>2||n[0]!==""||n[1]!==""?(this._$AH=Array(n.length-1).fill(new String),this.strings=n):this._$AH=wt}_$AI(e,r=this,n,s){const i=this.strings;let o=!1;if(i===void 0)e=Hi(this,e,r,0),o=!ca(e)||e!==this._$AH&&e!==qi,o&&(this._$AH=e);else{const a=e;let l,u;for(e=i[0],l=0;l<i.length-1;l++)u=Hi(this,a[n+l],r,l),u===qi&&(u=this._$AH[l]),o||=!ca(u)||u!==this._$AH[l],u===wt?e=wt:e!==wt&&(e+=(u??"")+i[l+1]),this._$AH[l]=u}o&&!s&&this.j(e)}j(e){e===wt?this.element.removeAttribute(this.name):this.element.setAttribute(this.name,e??"")}}class bB extends Au{constructor(){super(...arguments),this.type=3}j(e){this.element[this.name]=e===wt?void 0:e}}class vB extends Au{constructor(){super(...arguments),this.type=4}j(e){this.element.toggleAttribute(this.name,!!e&&e!==wt)}}class wB extends Au{constructor(e,r,n,s,i){super(e,r,n,s,i),this.type=5}_$AI(e,r=this){if((e=Hi(this,e,r,0)??wt)===qi)return;const n=this._$AH,s=e===wt&&n!==wt||e.capture!==n.capture||e.once!==n.once||e.passive!==n.passive,i=e!==wt&&(n===wt||s);s&&this.element.removeEventListener(this.name,this,n),i&&this.element.addEventListener(this.name,this,e),this._$AH=e}handleEvent(e){typeof this._$AH=="function"?this._$AH.call(this.options?.host??this.element,e):this._$AH.handleEvent(e)}}class _B{constructor(e,r,n){this.element=e,this.type=6,this._$AN=void 0,this._$AM=r,this.options=n}get _$AU(){return this._$AM._$AU}_$AI(e){Hi(this,e)}}const EB=bm.litHtmlPolyfillSupport;EB?.(ua,Va),(bm.litHtmlVersions??=[]).push("3.3.1");const SB=(t,e,r)=>{const n=r?.renderBefore??e;let s=n._$litPart$;if(s===void 0){const i=r?.renderBefore??null;n._$litPart$=s=new Va(e.insertBefore(la(),i),i,void 0,r??{})}return s._$AI(t),s};const wm=globalThis;class Uo extends hi{constructor(){super(...arguments),this.renderOptions={host:this},this._$Do=void 0}createRenderRoot(){const e=super.createRenderRoot();return this.renderOptions.renderBefore??=e.firstChild,e}update(e){const r=this.render();this.hasUpdated||(this.renderOptions.isConnected=this.isConnected),super.update(e),this._$Do=SB(r,this.renderRoot,this.renderOptions)}connectedCallback(){super.connectedCallback(),this._$Do?.setConnected(!0)}disconnectedCallback(){super.disconnectedCallback(),this._$Do?.setConnected(!1)}render(){return qi}}Uo._$litElement$=!0,Uo.finalized=!0,wm.litElementHydrateSupport?.({LitElement:Uo});const xB=wm.litElementPolyfillSupport;xB?.({LitElement:Uo});(wm.litElementVersions??=[]).push("4.2.1");const CB=t=>(e,r)=>{r!==void 0?r.addInitializer((()=>{customElements.define(t,e)})):customElements.define(t,e)};const AB={attribute:!0,type:String,converter:xc,reflect:!1,hasChanged:ym},$B=(t=AB,e,r)=>{const{kind:n,metadata:s}=r;let i=globalThis.litPropertyMetadata.get(s);if(i===void 0&&globalThis.litPropertyMetadata.set(s,i=new Map),n==="setter"&&((t=Object.create(t)).wrapped=!0),i.set(r.name,t),n==="accessor"){const{name:o}=r;return{set(a){const l=e.get.call(this);e.set.call(this,a),this.requestUpdate(o,l,t)},init(a){return a!==void 0&&this.C(o,void 0,t,a),a}}}if(n==="setter"){const{name:o}=r;return function(a){const l=this[o];e.call(this,a),this.requestUpdate(o,l,t)}}throw Error("Unsupported decorator location: "+n)};function TB(t){return(e,r)=>typeof r=="object"?$B(t,e,r):((n,s,i)=>{const o=s.hasOwnProperty(i);return s.constructor.createProperty(i,n),o?Object.getOwnPropertyDescriptor(s,i):void 0})(t,e,r)}const XS=(t,e,r)=>(r.configurable=!0,r.enumerable=!0,Reflect.decorate&&typeof e!="object"&&Object.defineProperty(t,e,r),r);function kB(t,e){return(r,n,s)=>{const i=o=>o.renderRoot?.querySelector(t)??null;return XS(r,n,{get(){return i(this)}})}}let PB;function OB(t){return(e,r)=>XS(e,r,{get(){return(this.renderRoot??(PB??=document.createDocumentFragment())).querySelectorAll(t)}})}var RB=Object.defineProperty,NB=Object.getOwnPropertyDescriptor,$u=(t,e,r,n)=>{for(var s=n>1?void 0:n?NB(e,r):e,i=t.length-1,o;i>=0;i--)(o=t[i])&&(s=(n?o(e,r,s):o(s))||s);return n&&s&&RB(e,r,s),s};let da=class extends Uo{constructor(){super(...arguments),this.state=Craft.getCookie("sidebar")??"expanded"}connectedCallback(){super.connectedCallback(),this.trigger&&(this.trigger.addEventListener("open",this.expand.bind(this)),this.trigger.addEventListener("close",this.collapse.bind(this))),this.state==="expanded"?this.expand():this.collapse()}disconnectedCallback(){super.disconnectedCallback(),this.trigger&&(this.trigger.removeEventListener("open",this.expand.bind(this)),this.trigger.removeEventListener("close",this.collapse.bind(this))),this.state="expanded"}itemHasTooltip(t){return t.querySelector("craft-tooltip")}createTooltips(){this.items?.forEach(t=>t.setAttribute("icon-only",!0))}destroyTooltips(){this.items?.forEach(t=>t.removeAttribute("icon-only"))}expand(){document.body.setAttribute("data-sidebar","expanded"),Craft.setCookie("sidebar","expanded"),this.destroyTooltips()}collapse(){document.body.setAttribute("data-sidebar","collapsed"),Craft.setCookie("sidebar","collapsed"),this.createTooltips()}createRenderRoot(){return this}};$u([OB("craft-nav-item")],da.prototype,"items",2);$u([kB("#sidebar-trigger")],da.prototype,"trigger",2);$u([TB({reflect:!0})],da.prototype,"state",2);da=$u([CB("cp-global-sidebar")],da);$F({resolve:t=>Gk(`./pages/${t}.vue`,Object.assign({"./pages/Install.vue":()=>ve(()=>import("./Install.js"),__vite__mapDeps([34,35]),import.meta.url)})),setup({el:t,App:e,props:r,plugin:n}){Ql({render:()=>Ns(e,r)}).use(n).mount(t)}});export{AA as A,Wi as B,eU as C,Xf as D,ZB as E,bt as F,G$ as G,m$ as T,qA as a,_C as b,Kt as c,Gi as d,KA as e,Fc as f,Jf as g,Qn as h,JC as i,zl as j,Mf as k,tt as l,iA as m,fa as n,Xo as o,c_ as p,zC as q,KC as r,QB as s,qv as t,AC as u,Yl as v,Os as w,ma as x,Je as y,w$ as z};
