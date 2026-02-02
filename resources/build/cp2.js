const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["./bg-BG2.js","./bg3.js","./cs-CZ2.js","./cs3.js","./de-DE2.js","./de3.js","./en-AU2.js","./en3.js","./en-GB2.js","./en-US2.js","./es-ES2.js","./es3.js","./fr-FR2.js","./fr3.js","./fr-BE2.js","./hu-HU2.js","./hu3.js","./it-IT2.js","./it3.js","./nl-BE2.js","./nl3.js","./nl-NL2.js","./pl-PL2.js","./pl3.js","./ro-RO2.js","./ro3.js","./ru-RU2.js","./ru3.js","./sk-SK2.js","./sk3.js","./tr-TR.js","./tr.js","./uk-UA2.js","./uk3.js","./bg-BG.js","./bg2.js","./cs-CZ.js","./cs2.js","./de-DE.js","./de2.js","./en-AU.js","./en2.js","./en-GB.js","./en-US.js","./es-ES.js","./es2.js","./fr-FR.js","./fr2.js","./fr-BE.js","./hu-HU.js","./hu2.js","./it-IT.js","./it2.js","./nl-BE.js","./nl2.js","./nl-NL.js","./pl-PL.js","./pl2.js","./ro-RO.js","./ro2.js","./ru-RU.js","./ru2.js","./sk-SK.js","./sk2.js","./uk-UA.js","./uk2.js","./Install.js","./lit-element.js","./nav-list.ts.js","./nav-item.ts.js","./state.js","./property.js","./progress-BMk33VHZ.js","./_plugin-vue_export-helper.js","./index.js","./CpGlobalSidebar.js","./custom-element.js","./assets/Install.css","./SettingsGeneralPage.js","./CalloutReadOnly.vue_vue_type_script_setup_true_lang.js","./assets/CalloutReadOnly.css","./assets/SettingsGeneralPage.css","./SettingsIndexPage.js","./assets/SettingsIndexPage.css"])))=>i.map(i=>d[i]);
import{a as is,i as yv,b as _v,m as jE,_ as Y}from"./index.js";import{_ as se,r as ir}from"./state.js";import{a as te,i as Ne,x as W,r as WE,Z as KE,B as Wp,E as ht,T as Wd,S as wv}from"./lit-element.js";import{n as V}from"./property.js";import{e as GE,a as ze}from"./CpGlobalSidebar.js";import{e as er,i as Ev,t as xv,a as Sv}from"./nav-item.ts.js";import{t as Fr}from"./custom-element.js";import"./nav-list.ts.js";import"./progress-BMk33VHZ.js";function YE(e){const t=Object.create(null);for(const r of e.split(","))t[r]=1;return r=>r in t}const XE={},JE=()=>{},ef=Object.assign,QE=(e,t)=>{const r=e.indexOf(t);r>-1&&e.splice(r,1)},ZE=Object.prototype.hasOwnProperty,ll=(e,t)=>ZE.call(e,t),bn=Array.isArray,to=e=>tc(e)==="[object Map]",e1=e=>tc(e)==="[object Set]",wo=e=>typeof e=="function",t1=e=>typeof e=="string",Ho=e=>typeof e=="symbol",Ti=e=>e!==null&&typeof e=="object",r1=Object.prototype.toString,tc=e=>r1.call(e),n1=e=>tc(e).slice(8,-1),i1=e=>tc(e)==="[object Object]",rc=e=>t1(e)&&e!=="NaN"&&e[0]!=="-"&&""+parseInt(e,10)===e,jn=(e,t)=>!Object.is(e,t),s1=(e,t,r,n=!1)=>{Object.defineProperty(e,t,{configurable:!0,enumerable:!1,writable:n,value:r})};let wt;class tf{constructor(t=!1){this.detached=t,this._active=!0,this._on=0,this.effects=[],this.cleanups=[],this._isPaused=!1,this.parent=wt,!t&&wt&&(this.index=(wt.scopes||(wt.scopes=[])).push(this)-1)}get active(){return this._active}pause(){if(this._active){this._isPaused=!0;let t,r;if(this.scopes)for(t=0,r=this.scopes.length;t<r;t++)this.scopes[t].pause();for(t=0,r=this.effects.length;t<r;t++)this.effects[t].pause()}}resume(){if(this._active&&this._isPaused){this._isPaused=!1;let t,r;if(this.scopes)for(t=0,r=this.scopes.length;t<r;t++)this.scopes[t].resume();for(t=0,r=this.effects.length;t<r;t++)this.effects[t].resume()}}run(t){if(this._active){const r=wt;try{return wt=this,t()}finally{wt=r}}}on(){++this._on===1&&(this.prevScope=wt,wt=this)}off(){this._on>0&&--this._on===0&&(wt=this.prevScope,this.prevScope=void 0)}stop(t){if(this._active){this._active=!1;let r,n;for(r=0,n=this.effects.length;r<n;r++)this.effects[r].stop();for(this.effects.length=0,r=0,n=this.cleanups.length;r<n;r++)this.cleanups[r]();if(this.cleanups.length=0,this.scopes){for(r=0,n=this.scopes.length;r<n;r++)this.scopes[r].stop(!0);this.scopes.length=0}if(!this.detached&&this.parent&&!t){const i=this.parent.scopes.pop();i&&i!==this&&(this.parent.scopes[this.index]=i,i.index=this.index)}this.parent=void 0}}}function o1(e){return new tf(e)}function Cv(){return wt}function a1(e,t=!1){wt&&wt.cleanups.push(e)}let Fe;const lu=new WeakSet;class Eo{constructor(t){this.fn=t,this.deps=void 0,this.depsTail=void 0,this.flags=5,this.next=void 0,this.cleanup=void 0,this.scheduler=void 0,wt&&wt.active&&wt.effects.push(this)}pause(){this.flags|=64}resume(){this.flags&64&&(this.flags&=-65,lu.has(this)&&(lu.delete(this),this.trigger()))}notify(){this.flags&2&&!(this.flags&32)||this.flags&8||Tv(this)}run(){if(!(this.flags&1))return this.fn();this.flags|=2,Kp(this),kv(this);const t=Fe,r=Ar;Fe=this,Ar=!0;try{return this.fn()}finally{Ov(this),Fe=t,Ar=r,this.flags&=-3}}stop(){if(this.flags&1){for(let t=this.deps;t;t=t.nextDep)sf(t);this.deps=this.depsTail=void 0,Kp(this),this.onStop&&this.onStop(),this.flags&=-2}}trigger(){this.flags&64?lu.add(this):this.scheduler?this.scheduler():this.runIfDirty()}runIfDirty(){Kd(this)&&this.run()}get dirty(){return Kd(this)}}let Av=0,ro,no;function Tv(e,t=!1){if(e.flags|=8,t){e.next=no,no=e;return}e.next=ro,ro=e}function rf(){Av++}function nf(){if(--Av>0)return;if(no){let t=no;for(no=void 0;t;){const r=t.next;t.next=void 0,t.flags&=-9,t=r}}let e;for(;ro;){let t=ro;for(ro=void 0;t;){const r=t.next;if(t.next=void 0,t.flags&=-9,t.flags&1)try{t.trigger()}catch(n){e||(e=n)}t=r}}if(e)throw e}function kv(e){for(let t=e.deps;t;t=t.nextDep)t.version=-1,t.prevActiveLink=t.dep.activeLink,t.dep.activeLink=t}function Ov(e){let t,r=e.depsTail,n=r;for(;n;){const i=n.prevDep;n.version===-1?(n===r&&(r=i),sf(n),l1(n)):t=n,n.dep.activeLink=n.prevActiveLink,n.prevActiveLink=void 0,n=i}e.deps=t,e.depsTail=r}function Kd(e){for(let t=e.deps;t;t=t.nextDep)if(t.dep.version!==t.version||t.dep.computed&&(Nv(t.dep.computed)||t.dep.version!==t.version))return!0;return!!e._dirty}function Nv(e){if(e.flags&4&&!(e.flags&16)||(e.flags&=-17,e.globalVersion===xo)||(e.globalVersion=xo,!e.isSSR&&e.flags&128&&(!e.deps&&!e._dirty||!Kd(e))))return;e.flags|=2;const t=e.dep,r=Fe,n=Ar;Fe=e,Ar=!0;try{kv(e);const i=e.fn(e._value);(t.version===0||jn(i,e._value))&&(e.flags|=128,e._value=i,t.version++)}catch(i){throw t.version++,i}finally{Fe=r,Ar=n,Ov(e),e.flags&=-3}}function sf(e,t=!1){const{dep:r,prevSub:n,nextSub:i}=e;if(n&&(n.nextSub=i,e.prevSub=void 0),i&&(i.prevSub=n,e.nextSub=void 0),r.subs===e&&(r.subs=n,!n&&r.computed)){r.computed.flags&=-5;for(let s=r.computed.deps;s;s=s.nextDep)sf(s,!0)}!t&&!--r.sc&&r.map&&r.map.delete(r.key)}function l1(e){const{prevDep:t,nextDep:r}=e;t&&(t.nextDep=r,e.prevDep=void 0),r&&(r.prevDep=t,e.nextDep=void 0)}function c1(e,t){e.effect instanceof Eo&&(e=e.effect.fn);const r=new Eo(e);t&&ef(r,t);try{r.run()}catch(i){throw r.stop(),i}const n=r.run.bind(r);return n.effect=r,n}function u1(e){e.effect.stop()}let Ar=!0;const Pv=[];function vn(){Pv.push(Ar),Ar=!1}function yn(){const e=Pv.pop();Ar=e===void 0?!0:e}function Kp(e){const{cleanup:t}=e;if(e.cleanup=void 0,t){const r=Fe;Fe=void 0;try{t()}finally{Fe=r}}}let xo=0;class d1{constructor(t,r){this.sub=t,this.dep=r,this.version=r.version,this.nextDep=this.prevDep=this.nextSub=this.prevSub=this.prevActiveLink=void 0}}class nc{constructor(t){this.computed=t,this.version=0,this.activeLink=void 0,this.subs=void 0,this.map=void 0,this.key=void 0,this.sc=0,this.__v_skip=!0}track(t){if(!Fe||!Ar||Fe===this.computed)return;let r=this.activeLink;if(r===void 0||r.sub!==Fe)r=this.activeLink=new d1(Fe,this),Fe.deps?(r.prevDep=Fe.depsTail,Fe.depsTail.nextDep=r,Fe.depsTail=r):Fe.deps=Fe.depsTail=r,Iv(r);else if(r.version===-1&&(r.version=this.version,r.nextDep)){const n=r.nextDep;n.prevDep=r.prevDep,r.prevDep&&(r.prevDep.nextDep=n),r.prevDep=Fe.depsTail,r.nextDep=void 0,Fe.depsTail.nextDep=r,Fe.depsTail=r,Fe.deps===r&&(Fe.deps=n)}return r}trigger(t){this.version++,xo++,this.notify(t)}notify(t){rf();try{for(let r=this.subs;r;r=r.prevSub)r.sub.notify()&&r.sub.dep.notify()}finally{nf()}}}function Iv(e){if(e.dep.sc++,e.sub.flags&4){const t=e.dep.computed;if(t&&!e.dep.subs){t.flags|=20;for(let n=t.deps;n;n=n.nextDep)Iv(n)}const r=e.dep.subs;r!==e&&(e.prevSub=r,r&&(r.nextSub=e)),e.dep.subs=e}}const cl=new WeakMap,yi=Symbol(""),Gd=Symbol(""),So=Symbol("");function xt(e,t,r){if(Ar&&Fe){let n=cl.get(e);n||cl.set(e,n=new Map);let i=n.get(r);i||(n.set(r,i=new nc),i.map=n,i.key=r),i.track()}}function an(e,t,r,n,i,s){const o=cl.get(e);if(!o){xo++;return}const a=l=>{l&&l.trigger()};if(rf(),t==="clear")o.forEach(a);else{const l=bn(e),u=l&&rc(r);if(l&&r==="length"){const c=Number(n);o.forEach((d,p)=>{(p==="length"||p===So||!Ho(p)&&p>=c)&&a(d)})}else switch((r!==void 0||o.has(void 0))&&a(o.get(r)),u&&a(o.get(So)),t){case"add":l?u&&a(o.get("length")):(a(o.get(yi)),to(e)&&a(o.get(Gd)));break;case"delete":l||(a(o.get(yi)),to(e)&&a(o.get(Gd)));break;case"set":to(e)&&a(o.get(yi));break}}nf()}function h1(e,t){const r=cl.get(e);return r&&r.get(t)}function zi(e){const t=ye(e);return t===e?t:(xt(t,"iterate",So),Ut(e)?t:t.map(kr))}function ic(e){return xt(e=ye(e),"iterate",So),e}function Dn(e,t){return Wr(e)?ss(hn(e)?kr(t):t):kr(t)}const f1={__proto__:null,[Symbol.iterator](){return cu(this,Symbol.iterator,e=>Dn(this,e))},concat(...e){return zi(this).concat(...e.map(t=>bn(t)?zi(t):t))},entries(){return cu(this,"entries",e=>(e[1]=Dn(this,e[1]),e))},every(e,t){return Zr(this,"every",e,t,void 0,arguments)},filter(e,t){return Zr(this,"filter",e,t,r=>r.map(n=>Dn(this,n)),arguments)},find(e,t){return Zr(this,"find",e,t,r=>Dn(this,r),arguments)},findIndex(e,t){return Zr(this,"findIndex",e,t,void 0,arguments)},findLast(e,t){return Zr(this,"findLast",e,t,r=>Dn(this,r),arguments)},findLastIndex(e,t){return Zr(this,"findLastIndex",e,t,void 0,arguments)},forEach(e,t){return Zr(this,"forEach",e,t,void 0,arguments)},includes(...e){return uu(this,"includes",e)},indexOf(...e){return uu(this,"indexOf",e)},join(e){return zi(this).join(e)},lastIndexOf(...e){return uu(this,"lastIndexOf",e)},map(e,t){return Zr(this,"map",e,t,void 0,arguments)},pop(){return Ms(this,"pop")},push(...e){return Ms(this,"push",e)},reduce(e,...t){return Gp(this,"reduce",e,t)},reduceRight(e,...t){return Gp(this,"reduceRight",e,t)},shift(){return Ms(this,"shift")},some(e,t){return Zr(this,"some",e,t,void 0,arguments)},splice(...e){return Ms(this,"splice",e)},toReversed(){return zi(this).toReversed()},toSorted(e){return zi(this).toSorted(e)},toSpliced(...e){return zi(this).toSpliced(...e)},unshift(...e){return Ms(this,"unshift",e)},values(){return cu(this,"values",e=>Dn(this,e))}};function cu(e,t,r){const n=ic(e),i=n[t]();return n!==e&&!Ut(e)&&(i._next=i.next,i.next=()=>{const s=i._next();return s.done||(s.value=r(s.value)),s}),i}const p1=Array.prototype;function Zr(e,t,r,n,i,s){const o=ic(e),a=o!==e&&!Ut(e),l=o[t];if(l!==p1[t]){const d=l.apply(e,s);return a?kr(d):d}let u=r;o!==e&&(a?u=function(d,p){return r.call(this,Dn(e,d),p,e)}:r.length>2&&(u=function(d,p){return r.call(this,d,p,e)}));const c=l.call(o,u,n);return a&&i?i(c):c}function Gp(e,t,r,n){const i=ic(e);let s=r;return i!==e&&(Ut(e)?r.length>3&&(s=function(o,a,l){return r.call(this,o,a,l,e)}):s=function(o,a,l){return r.call(this,o,Dn(e,a),l,e)}),i[t](s,...n)}function uu(e,t,r){const n=ye(e);xt(n,"iterate",So);const i=n[t](...r);return(i===-1||i===!1)&&qo(r[0])?(r[0]=ye(r[0]),n[t](...r)):i}function Ms(e,t,r=[]){vn(),rf();const n=ye(e)[t].apply(e,r);return nf(),yn(),n}const m1=YE("__proto__,__v_isRef,__isVue"),Fv=new Set(Object.getOwnPropertyNames(Symbol).filter(e=>e!=="arguments"&&e!=="caller").map(e=>Symbol[e]).filter(Ho));function g1(e){Ho(e)||(e=String(e));const t=ye(this);return xt(t,"has",e),t.hasOwnProperty(e)}class Lv{constructor(t=!1,r=!1){this._isReadonly=t,this._isShallow=r}get(t,r,n){if(r==="__v_skip")return t.__v_skip;const i=this._isReadonly,s=this._isShallow;if(r==="__v_isReactive")return!i;if(r==="__v_isReadonly")return i;if(r==="__v_isShallow")return s;if(r==="__v_raw")return n===(i?s?Bv:Vv:s?$v:Dv).get(t)||Object.getPrototypeOf(t)===Object.getPrototypeOf(n)?t:void 0;const o=bn(t);if(!i){let l;if(o&&(l=f1[r]))return l;if(r==="hasOwnProperty")return g1}const a=Reflect.get(t,r,Ze(t)?t:n);if((Ho(r)?Fv.has(r):m1(r))||(i||xt(t,"get",r),s))return a;if(Ze(a)){const l=o&&rc(r)?a:a.value;return i&&Ti(l)?ul(l):l}return Ti(a)?i?ul(a):Es(a):a}}class Rv extends Lv{constructor(t=!1){super(!1,t)}set(t,r,n,i){let s=t[r];const o=bn(t)&&rc(r);if(!this._isShallow){const u=Wr(s);if(!Ut(n)&&!Wr(n)&&(s=ye(s),n=ye(n)),!o&&Ze(s)&&!Ze(n))return u||(s.value=n),!0}const a=o?Number(r)<t.length:ll(t,r),l=Reflect.set(t,r,n,Ze(t)?t:i);return t===ye(i)&&(a?jn(n,s)&&an(t,"set",r,n):an(t,"add",r,n)),l}deleteProperty(t,r){const n=ll(t,r);t[r];const i=Reflect.deleteProperty(t,r);return i&&n&&an(t,"delete",r,void 0),i}has(t,r){const n=Reflect.has(t,r);return(!Ho(r)||!Fv.has(r))&&xt(t,"has",r),n}ownKeys(t){return xt(t,"iterate",bn(t)?"length":yi),Reflect.ownKeys(t)}}class Mv extends Lv{constructor(t=!1){super(!0,t)}set(t,r){return!0}deleteProperty(t,r){return!0}}const b1=new Rv,v1=new Mv,y1=new Rv(!0),_1=new Mv(!0),Yd=e=>e,xa=e=>Reflect.getPrototypeOf(e);function w1(e,t,r){return function(...n){const i=this.__v_raw,s=ye(i),o=to(s),a=e==="entries"||e===Symbol.iterator&&o,l=e==="keys"&&o,u=i[e](...n),c=r?Yd:t?ss:kr;return!t&&xt(s,"iterate",l?Gd:yi),ef(Object.create(u),{next(){const{value:d,done:p}=u.next();return p?{value:d,done:p}:{value:a?[c(d[0]),c(d[1])]:c(d),done:p}}})}}function Sa(e){return function(...t){return e==="delete"?!1:e==="clear"?void 0:this}}function E1(e,t){const r={get(i){const s=this.__v_raw,o=ye(s),a=ye(i);e||(jn(i,a)&&xt(o,"get",i),xt(o,"get",a));const{has:l}=xa(o),u=t?Yd:e?ss:kr;if(l.call(o,i))return u(s.get(i));if(l.call(o,a))return u(s.get(a));s!==o&&s.get(i)},get size(){const i=this.__v_raw;return!e&&xt(ye(i),"iterate",yi),i.size},has(i){const s=this.__v_raw,o=ye(s),a=ye(i);return e||(jn(i,a)&&xt(o,"has",i),xt(o,"has",a)),i===a?s.has(i):s.has(i)||s.has(a)},forEach(i,s){const o=this,a=o.__v_raw,l=ye(a),u=t?Yd:e?ss:kr;return!e&&xt(l,"iterate",yi),a.forEach((c,d)=>i.call(s,u(c),u(d),o))}};return ef(r,e?{add:Sa("add"),set:Sa("set"),delete:Sa("delete"),clear:Sa("clear")}:{add(i){!t&&!Ut(i)&&!Wr(i)&&(i=ye(i));const s=ye(this);return xa(s).has.call(s,i)||(s.add(i),an(s,"add",i,i)),this},set(i,s){!t&&!Ut(s)&&!Wr(s)&&(s=ye(s));const o=ye(this),{has:a,get:l}=xa(o);let u=a.call(o,i);u||(i=ye(i),u=a.call(o,i));const c=l.call(o,i);return o.set(i,s),u?jn(s,c)&&an(o,"set",i,s):an(o,"add",i,s),this},delete(i){const s=ye(this),{has:o,get:a}=xa(s);let l=o.call(s,i);l||(i=ye(i),l=o.call(s,i)),a&&a.call(s,i);const u=s.delete(i);return l&&an(s,"delete",i,void 0),u},clear(){const i=ye(this),s=i.size!==0,o=i.clear();return s&&an(i,"clear",void 0,void 0),o}}),["keys","values","entries",Symbol.iterator].forEach(i=>{r[i]=w1(i,e,t)}),r}function sc(e,t){const r=E1(e,t);return(n,i,s)=>i==="__v_isReactive"?!e:i==="__v_isReadonly"?e:i==="__v_raw"?n:Reflect.get(ll(r,i)&&i in n?r:n,i,s)}const x1={get:sc(!1,!1)},S1={get:sc(!1,!0)},C1={get:sc(!0,!1)},A1={get:sc(!0,!0)},Dv=new WeakMap,$v=new WeakMap,Vv=new WeakMap,Bv=new WeakMap;function T1(e){switch(e){case"Object":case"Array":return 1;case"Map":case"Set":case"WeakMap":case"WeakSet":return 2;default:return 0}}function k1(e){return e.__v_skip||!Object.isExtensible(e)?0:T1(n1(e))}function Es(e){return Wr(e)?e:oc(e,!1,b1,x1,Dv)}function Uv(e){return oc(e,!1,y1,S1,$v)}function ul(e){return oc(e,!0,v1,C1,Vv)}function O1(e){return oc(e,!0,_1,A1,Bv)}function oc(e,t,r,n,i){if(!Ti(e)||e.__v_raw&&!(t&&e.__v_isReactive))return e;const s=k1(e);if(s===0)return e;const o=i.get(e);if(o)return o;const a=new Proxy(e,s===2?n:r);return i.set(e,a),a}function hn(e){return Wr(e)?hn(e.__v_raw):!!(e&&e.__v_isReactive)}function Wr(e){return!!(e&&e.__v_isReadonly)}function Ut(e){return!!(e&&e.__v_isShallow)}function qo(e){return e?!!e.__v_raw:!1}function ye(e){const t=e&&e.__v_raw;return t?ye(t):e}function dl(e){return!ll(e,"__v_skip")&&Object.isExtensible(e)&&s1(e,"__v_skip",!0),e}const kr=e=>Ti(e)?Es(e):e,ss=e=>Ti(e)?ul(e):e;function Ze(e){return e?e.__v_isRef===!0:!1}function Wn(e){return zv(e,!1)}function of(e){return zv(e,!0)}function zv(e,t){return Ze(e)?e:new N1(e,t)}class N1{constructor(t,r){this.dep=new nc,this.__v_isRef=!0,this.__v_isShallow=!1,this._rawValue=r?t:ye(t),this._value=r?t:kr(t),this.__v_isShallow=r}get value(){return this.dep.track(),this._value}set value(t){const r=this._rawValue,n=this.__v_isShallow||Ut(t)||Wr(t);t=n?t:ye(t),jn(t,r)&&(this._rawValue=t,this._value=n?t:kr(t),this.dep.trigger())}}function P1(e){e.dep&&e.dep.trigger()}function jo(e){return Ze(e)?e.value:e}function I1(e){return wo(e)?e():jo(e)}const F1={get:(e,t,r)=>t==="__v_raw"?e:jo(Reflect.get(e,t,r)),set:(e,t,r,n)=>{const i=e[t];return Ze(i)&&!Ze(r)?(i.value=r,!0):Reflect.set(e,t,r,n)}};function af(e){return hn(e)?e:new Proxy(e,F1)}class L1{constructor(t){this.__v_isRef=!0,this._value=void 0;const r=this.dep=new nc,{get:n,set:i}=t(r.track.bind(r),r.trigger.bind(r));this._get=n,this._set=i}get value(){return this._value=this._get()}set value(t){this._set(t)}}function Hv(e){return new L1(e)}function R1(e){const t=bn(e)?new Array(e.length):{};for(const r in e)t[r]=qv(e,r);return t}class M1{constructor(t,r,n){this._object=t,this._key=r,this._defaultValue=n,this.__v_isRef=!0,this._value=void 0,this._raw=ye(t);let i=!0,s=t;if(!bn(t)||!rc(String(r)))do i=!qo(s)||Ut(s);while(i&&(s=s.__v_raw));this._shallow=i}get value(){let t=this._object[this._key];return this._shallow&&(t=jo(t)),this._value=t===void 0?this._defaultValue:t}set value(t){if(this._shallow&&Ze(this._raw[this._key])){const r=this._object[this._key];if(Ze(r)){r.value=t;return}}this._object[this._key]=t}get dep(){return h1(this._raw,this._key)}}class D1{constructor(t){this._getter=t,this.__v_isRef=!0,this.__v_isReadonly=!0,this._value=void 0}get value(){return this._value=this._getter()}}function $1(e,t,r){return Ze(e)?e:wo(e)?new D1(e):Ti(e)&&arguments.length>1?qv(e,t,r):Wn(e)}function qv(e,t,r){return new M1(e,t,r)}class V1{constructor(t,r,n){this.fn=t,this.setter=r,this._value=void 0,this.dep=new nc(this),this.__v_isRef=!0,this.deps=void 0,this.depsTail=void 0,this.flags=16,this.globalVersion=xo-1,this.next=void 0,this.effect=this,this.__v_isReadonly=!r,this.isSSR=n}notify(){if(this.flags|=16,!(this.flags&8)&&Fe!==this)return Tv(this,!0),!0}get value(){const t=this.dep.track();return Nv(this),t&&(t.version=this.dep.version),this._value}set value(t){this.setter&&this.setter(t)}}function B1(e,t,r=!1){let n,i;return wo(e)?n=e:(n=e.get,i=e.set),new V1(n,i,r)}const U1={GET:"get",HAS:"has",ITERATE:"iterate"},z1={SET:"set",ADD:"add",DELETE:"delete",CLEAR:"clear"},Ca={},hl=new WeakMap;let $n;function H1(){return $n}function jv(e,t=!1,r=$n){if(r){let n=hl.get(r);n||hl.set(r,n=[]),n.push(e)}}function q1(e,t,r=XE){const{immediate:n,deep:i,once:s,scheduler:o,augmentJob:a,call:l}=r,u=_=>i?_:Ut(_)||i===!1||i===0?ln(_,1):ln(_);let c,d,p,m,h=!1,f=!1;if(Ze(e)?(d=()=>e.value,h=Ut(e)):hn(e)?(d=()=>u(e),h=!0):bn(e)?(f=!0,h=e.some(_=>hn(_)||Ut(_)),d=()=>e.map(_=>{if(Ze(_))return _.value;if(hn(_))return u(_);if(wo(_))return l?l(_,2):_()})):wo(e)?t?d=l?()=>l(e,2):e:d=()=>{if(p){vn();try{p()}finally{yn()}}const _=$n;$n=c;try{return l?l(e,3,[m]):e(m)}finally{$n=_}}:d=JE,t&&i){const _=d,x=i===!0?1/0:i;d=()=>ln(_(),x)}const b=Cv(),y=()=>{c.stop(),b&&b.active&&QE(b.effects,c)};if(s&&t){const _=t;t=(...x)=>{_(...x),y()}}let w=f?new Array(e.length).fill(Ca):Ca;const v=_=>{if(!(!(c.flags&1)||!c.dirty&&!_))if(t){const x=c.run();if(i||h||(f?x.some((k,S)=>jn(k,w[S])):jn(x,w))){p&&p();const k=$n;$n=c;try{const S=[x,w===Ca?void 0:f&&w[0]===Ca?[]:w,m];w=x,l?l(t,3,S):t(...S)}finally{$n=k}}}else c.run()};return a&&a(v),c=new Eo(d),c.scheduler=o?()=>o(v,!1):v,m=_=>jv(_,!1,c),p=c.onStop=()=>{const _=hl.get(c);if(_){if(l)l(_,4);else for(const x of _)x();hl.delete(c)}},t?n?v(!0):w=c.run():o?o(v.bind(null,!0),!0):c.run(),y.pause=c.pause.bind(c),y.resume=c.resume.bind(c),y.stop=y,y}function ln(e,t=1/0,r){if(t<=0||!Ti(e)||e.__v_skip||(r=r||new Map,(r.get(e)||0)>=t))return e;if(r.set(e,t),t--,Ze(e))ln(e.value,t,r);else if(bn(e))for(let n=0;n<e.length;n++)ln(e[n],t,r);else if(e1(e)||to(e))e.forEach(n=>{ln(n,t,r)});else if(i1(e)){for(const n in e)ln(e[n],t,r);for(const n of Object.getOwnPropertySymbols(e))Object.prototype.propertyIsEnumerable.call(e,n)&&ln(e[n],t,r)}return e}function Wv(e){const t=Object.create(null);for(const r of e.split(","))t[r]=1;return r=>r in t}const Ee={},Zi=[],Hr=()=>{},Kv=()=>!1,ac=e=>e.charCodeAt(0)===111&&e.charCodeAt(1)===110&&(e.charCodeAt(2)>122||e.charCodeAt(2)<97),Gv=e=>e.startsWith("onUpdate:"),pt=Object.assign,Yv=(e,t)=>{const r=e.indexOf(t);r>-1&&e.splice(r,1)},j1=Object.prototype.hasOwnProperty,Le=(e,t)=>j1.call(e,t),de=Array.isArray,W1=e=>lc(e)==="[object Map]",K1=e=>lc(e)==="[object Set]",G1=e=>lc(e)==="[object RegExp]",ce=e=>typeof e=="function",et=e=>typeof e=="string",lf=e=>typeof e=="symbol",ot=e=>e!==null&&typeof e=="object",cf=e=>(ot(e)||ce(e))&&ce(e.then)&&ce(e.catch),Xv=Object.prototype.toString,lc=e=>Xv.call(e),Y1=e=>lc(e)==="[object Object]",_i=Wv(",key,ref,ref_for,ref_key,onVnodeBeforeMount,onVnodeMounted,onVnodeBeforeUpdate,onVnodeUpdated,onVnodeBeforeUnmount,onVnodeUnmounted"),cc=e=>{const t=Object.create(null);return(r=>t[r]||(t[r]=e(r)))},X1=/-\w/g,tr=cc(e=>e.replace(X1,t=>t.slice(1).toUpperCase())),J1=/\B([A-Z])/g,xs=cc(e=>e.replace(J1,"-$1").toLowerCase()),uc=cc(e=>e.charAt(0).toUpperCase()+e.slice(1)),io=cc(e=>e?`on${uc(e)}`:""),hi=(e,t)=>!Object.is(e,t),so=(e,...t)=>{for(let r=0;r<e.length;r++)e[r](...t)},Q1=(e,t,r,n=!1)=>{Object.defineProperty(e,t,{configurable:!0,enumerable:!1,writable:n,value:r})},Z1=e=>{const t=parseFloat(e);return isNaN(t)?e:t},ex=e=>{const t=et(e)?Number(e):NaN;return isNaN(t)?e:t};let Yp;const dc=()=>Yp||(Yp=typeof globalThis<"u"?globalThis:typeof self<"u"?self:typeof window<"u"?window:typeof global<"u"?global:{}),tx="Infinity,undefined,NaN,isFinite,isNaN,parseFloat,parseInt,decodeURI,decodeURIComponent,encodeURI,encodeURIComponent,Math,Number,Date,Array,Object,Boolean,String,RegExp,Map,Set,JSON,Intl,BigInt,console,Error,Symbol",rx=Wv(tx);function Wo(e){if(de(e)){const t={};for(let r=0;r<e.length;r++){const n=e[r],i=et(n)?ox(n):Wo(n);if(i)for(const s in i)t[s]=i[s]}return t}else if(et(e)||ot(e))return e}const nx=/;(?![^(]*\))/g,ix=/:([^]+)/,sx=/\/\*[^]*?\*\//g;function ox(e){const t={};return e.replace(sx,"").split(nx).forEach(r=>{if(r){const n=r.split(ix);n.length>1&&(t[n[0].trim()]=n[1].trim())}}),t}function Ko(e){let t="";if(et(e))t=e;else if(de(e))for(let r=0;r<e.length;r++){const n=Ko(e[r]);n&&(t+=n+" ")}else if(ot(e))for(const r in e)e[r]&&(t+=r+" ");return t.trim()}function ax(e){if(!e)return null;let{class:t,style:r}=e;return t&&!et(t)&&(e.class=Ko(t)),r&&(e.style=Wo(r)),e}const Jv=e=>!!(e&&e.__v_isRef===!0),Qv=e=>et(e)?e:e==null?"":de(e)||ot(e)&&(e.toString===Xv||!ce(e.toString))?Jv(e)?Qv(e.value):JSON.stringify(e,Zv,2):String(e),Zv=(e,t)=>Jv(t)?Zv(e,t.value):W1(t)?{[`Map(${t.size})`]:[...t.entries()].reduce((r,[n,i],s)=>(r[du(n,s)+" =>"]=i,r),{})}:K1(t)?{[`Set(${t.size})`]:[...t.values()].map(r=>du(r))}:lf(t)?du(t):ot(t)&&!de(t)&&!Y1(t)?String(t):t,du=(e,t="")=>{var r;return lf(e)?`Symbol(${(r=e.description)!=null?r:t})`:e};const ey=[];function lx(e){ey.push(e)}function cx(){ey.pop()}function ux(e,t){}const dx={SETUP_FUNCTION:0,0:"SETUP_FUNCTION",RENDER_FUNCTION:1,1:"RENDER_FUNCTION",NATIVE_EVENT_HANDLER:5,5:"NATIVE_EVENT_HANDLER",COMPONENT_EVENT_HANDLER:6,6:"COMPONENT_EVENT_HANDLER",VNODE_HOOK:7,7:"VNODE_HOOK",DIRECTIVE_HOOK:8,8:"DIRECTIVE_HOOK",TRANSITION_HOOK:9,9:"TRANSITION_HOOK",APP_ERROR_HANDLER:10,10:"APP_ERROR_HANDLER",APP_WARN_HANDLER:11,11:"APP_WARN_HANDLER",FUNCTION_REF:12,12:"FUNCTION_REF",ASYNC_COMPONENT_LOADER:13,13:"ASYNC_COMPONENT_LOADER",SCHEDULER:14,14:"SCHEDULER",COMPONENT_UPDATE:15,15:"COMPONENT_UPDATE",APP_UNMOUNT_CLEANUP:16,16:"APP_UNMOUNT_CLEANUP"},hx={sp:"serverPrefetch hook",bc:"beforeCreate hook",c:"created hook",bm:"beforeMount hook",m:"mounted hook",bu:"beforeUpdate hook",u:"updated",bum:"beforeUnmount hook",um:"unmounted hook",a:"activated hook",da:"deactivated hook",ec:"errorCaptured hook",rtc:"renderTracked hook",rtg:"renderTriggered hook",0:"setup function",1:"render function",2:"watcher getter",3:"watcher callback",4:"watcher cleanup function",5:"native event handler",6:"component event handler",7:"vnode hook",8:"directive hook",9:"transition hook",10:"app errorHandler",11:"app warnHandler",12:"ref function",13:"async component loader",14:"scheduler flush",15:"component update",16:"app unmount cleanup function"};function Ss(e,t,r,n){try{return n?e(...n):e()}catch(i){Ri(i,t,r)}}function pr(e,t,r,n){if(ce(e)){const i=Ss(e,t,r,n);return i&&cf(i)&&i.catch(s=>{Ri(s,t,r)}),i}if(de(e)){const i=[];for(let s=0;s<e.length;s++)i.push(pr(e[s],t,r,n));return i}}function Ri(e,t,r,n=!0){const i=t?t.vnode:null,{errorHandler:s,throwUnhandledErrorInProduction:o}=t&&t.appContext.config||Ee;if(t){let a=t.parent;const l=t.proxy,u=`https://vuejs.org/error-reference/#runtime-${r}`;for(;a;){const c=a.ec;if(c){for(let d=0;d<c.length;d++)if(c[d](e,l,u)===!1)return}a=a.parent}if(s){vn(),Ss(s,null,10,[e,l,u]),yn();return}}fx(e,r,i,n,o)}function fx(e,t,r,n=!0,i=!1){if(i)throw e;console.error(e)}const It=[];let $r=-1;const es=[];let Vn=null,Ki=0;const ty=Promise.resolve();let fl=null;function hc(e){const t=fl||ty;return e?t.then(this?e.bind(this):e):t}function px(e){let t=$r+1,r=It.length;for(;t<r;){const n=t+r>>>1,i=It[n],s=Ao(i);s<e||s===e&&i.flags&2?t=n+1:r=n}return t}function uf(e){if(!(e.flags&1)){const t=Ao(e),r=It[It.length-1];!r||!(e.flags&2)&&t>=Ao(r)?It.push(e):It.splice(px(t),0,e),e.flags|=1,ry()}}function ry(){fl||(fl=ty.then(ny))}function Co(e){de(e)?es.push(...e):Vn&&e.id===-1?Vn.splice(Ki+1,0,e):e.flags&1||(es.push(e),e.flags|=1),ry()}function Xp(e,t,r=$r+1){for(;r<It.length;r++){const n=It[r];if(n&&n.flags&2){if(e&&n.id!==e.uid)continue;It.splice(r,1),r--,n.flags&4&&(n.flags&=-2),n(),n.flags&4||(n.flags&=-2)}}}function pl(e){if(es.length){const t=[...new Set(es)].sort((r,n)=>Ao(r)-Ao(n));if(es.length=0,Vn){Vn.push(...t);return}for(Vn=t,Ki=0;Ki<Vn.length;Ki++){const r=Vn[Ki];r.flags&4&&(r.flags&=-2),r.flags&8||r(),r.flags&=-2}Vn=null,Ki=0}}const Ao=e=>e.id==null?e.flags&2?-1:1/0:e.id;function ny(e){try{for($r=0;$r<It.length;$r++){const t=It[$r];t&&!(t.flags&8)&&(t.flags&4&&(t.flags&=-2),Ss(t,t.i,t.i?15:14),t.flags&4||(t.flags&=-2))}}finally{for(;$r<It.length;$r++){const t=It[$r];t&&(t.flags&=-2)}$r=-1,It.length=0,pl(),fl=null,(It.length||es.length)&&ny()}}let Gi,Aa=[];function iy(e,t){var r,n;Gi=e,Gi?(Gi.enabled=!0,Aa.forEach(({event:i,args:s})=>Gi.emit(i,...s)),Aa=[]):typeof window<"u"&&window.HTMLElement&&!((n=(r=window.navigator)==null?void 0:r.userAgent)!=null&&n.includes("jsdom"))?((t.__VUE_DEVTOOLS_HOOK_REPLAY__=t.__VUE_DEVTOOLS_HOOK_REPLAY__||[]).push(s=>{iy(s,t)}),setTimeout(()=>{Gi||(t.__VUE_DEVTOOLS_HOOK_REPLAY__=null,Aa=[])},3e3)):Aa=[]}let vt=null,fc=null;function To(e){const t=vt;return vt=e,fc=e&&e.type.__scopeId||null,t}function mx(e){fc=e}function gx(){fc=null}const bx=e=>df;function df(e,t=vt,r){if(!t||e._n)return e;const n=(...i)=>{n._d&&Po(-1);const s=To(t);let o;try{o=e(...i)}finally{To(s),n._d&&Po(1)}return o};return n._n=!0,n._c=!0,n._d=!0,n}function vx(e,t){if(vt===null)return e;const r=Jo(vt),n=e.dirs||(e.dirs=[]);for(let i=0;i<t.length;i++){let[s,o,a,l=Ee]=t[i];s&&(ce(s)&&(s={mounted:s,updated:s}),s.deep&&ln(o),n.push({dir:s,instance:r,value:o,oldValue:void 0,arg:a,modifiers:l}))}return e}function Br(e,t,r,n){const i=e.dirs,s=t&&t.dirs;for(let o=0;o<i.length;o++){const a=i[o];s&&(a.oldValue=s[o].value);let l=a.dir[n];l&&(vn(),pr(l,r,8,[e.el,a,e,t]),yn())}}function sy(e,t){if(bt){let r=bt.provides;const n=bt.parent&&bt.parent.provides;n===r&&(r=bt.provides=Object.create(n)),r[e]=t}}function oo(e,t,r=!1){const n=zt();if(n||Ei){let i=Ei?Ei._context.provides:n?n.parent==null||n.ce?n.vnode.appContext&&n.vnode.appContext.provides:n.parent.provides:void 0;if(i&&e in i)return i[e];if(arguments.length>1)return r&&ce(t)?t.call(n&&n.proxy):t}}function yx(){return!!(zt()||Ei)}const oy=Symbol.for("v-scx"),ay=()=>oo(oy);function _x(e,t){return Go(e,null,t)}function wx(e,t){return Go(e,null,{flush:"post"})}function ly(e,t){return Go(e,null,{flush:"sync"})}function wi(e,t,r){return Go(e,t,r)}function Go(e,t,r=Ee){const{immediate:n,deep:i,flush:s,once:o}=r,a=pt({},r),l=t&&n||!t&&s!=="post";let u;if(as){if(s==="sync"){const m=ay();u=m.__watcherHandles||(m.__watcherHandles=[])}else if(!l){const m=()=>{};return m.stop=Hr,m.resume=Hr,m.pause=Hr,m}}const c=bt;a.call=(m,h,f)=>pr(m,c,h,f);let d=!1;s==="post"?a.scheduler=m=>{Je(m,c&&c.suspense)}:s!=="sync"&&(d=!0,a.scheduler=(m,h)=>{h?m():uf(m)}),a.augmentJob=m=>{t&&(m.flags|=4),d&&(m.flags|=2,c&&(m.id=c.uid,m.i=c))};const p=q1(e,t,a);return as&&(u?u.push(p):l&&p()),p}function Ex(e,t,r){const n=this.proxy,i=et(e)?e.includes(".")?cy(n,e):()=>n[e]:e.bind(n,n);let s;ce(t)?s=t:(s=t.handler,r=t);const o=Oi(this),a=Go(i,s.bind(n),r);return o(),a}function cy(e,t){const r=t.split(".");return()=>{let n=e;for(let i=0;i<r.length&&n;i++)n=n[r[i]];return n}}const uy=Symbol("_vte"),dy=e=>e.__isTeleport,ao=e=>e&&(e.disabled||e.disabled===""),Jp=e=>e&&(e.defer||e.defer===""),Qp=e=>typeof SVGElement<"u"&&e instanceof SVGElement,Zp=e=>typeof MathMLElement=="function"&&e instanceof MathMLElement,Xd=(e,t)=>{const r=e&&e.to;return et(r)?t?t(r):null:r},hy={name:"Teleport",__isTeleport:!0,process(e,t,r,n,i,s,o,a,l,u){const{mc:c,pc:d,pbc:p,o:{insert:m,querySelector:h,createText:f,createComment:b}}=u,y=ao(t.props);let{shapeFlag:w,children:v,dynamicChildren:_}=t;if(e==null){const x=t.el=f(""),k=t.anchor=f("");m(x,r,n),m(k,r,n);const S=(A,T)=>{w&16&&c(v,A,T,i,s,o,a,l)},N=()=>{const A=t.target=Xd(t.props,h),T=fy(A,t,f,m);A&&(o!=="svg"&&Qp(A)?o="svg":o!=="mathml"&&Zp(A)&&(o="mathml"),i&&i.isCE&&(i.ce._teleportTargets||(i.ce._teleportTargets=new Set)).add(A),y||(S(A,T),ja(t,!1)))};y&&(S(r,k),ja(t,!0)),Jp(t.props)?(t.el.__isMounted=!1,Je(()=>{N(),delete t.el.__isMounted},s)):N()}else{if(Jp(t.props)&&e.el.__isMounted===!1){Je(()=>{hy.process(e,t,r,n,i,s,o,a,l,u)},s);return}t.el=e.el,t.targetStart=e.targetStart;const x=t.anchor=e.anchor,k=t.target=e.target,S=t.targetAnchor=e.targetAnchor,N=ao(e.props),A=N?r:k,T=N?x:S;if(o==="svg"||Qp(k)?o="svg":(o==="mathml"||Zp(k))&&(o="mathml"),_?(p(e.dynamicChildren,_,A,i,s,o,a),Ef(e,t,!0)):l||d(e,t,A,T,i,s,o,a,!1),y)N?t.props&&e.props&&t.props.to!==e.props.to&&(t.props.to=e.props.to):Ta(t,r,x,u,1);else if((t.props&&t.props.to)!==(e.props&&e.props.to)){const I=t.target=Xd(t.props,h);I&&Ta(t,I,null,u,0)}else N&&Ta(t,k,S,u,1);ja(t,y)}},remove(e,t,r,{um:n,o:{remove:i}},s){const{shapeFlag:o,children:a,anchor:l,targetStart:u,targetAnchor:c,target:d,props:p}=e;if(d&&(i(u),i(c)),s&&i(l),o&16){const m=s||!ao(p);for(let h=0;h<a.length;h++){const f=a[h];n(f,t,r,m,!!f.dynamicChildren)}}},move:Ta,hydrate:xx};function Ta(e,t,r,{o:{insert:n},m:i},s=2){s===0&&n(e.targetAnchor,t,r);const{el:o,anchor:a,shapeFlag:l,children:u,props:c}=e,d=s===2;if(d&&n(o,t,r),(!d||ao(c))&&l&16)for(let p=0;p<u.length;p++)i(u[p],t,r,2);d&&n(a,t,r)}function xx(e,t,r,n,i,s,{o:{nextSibling:o,parentNode:a,querySelector:l,insert:u,createText:c}},d){function p(f,b,y,w){b.anchor=d(o(f),b,a(f),r,n,i,s),b.targetStart=y,b.targetAnchor=w}const m=t.target=Xd(t.props,l),h=ao(t.props);if(m){const f=m._lpa||m.firstChild;if(t.shapeFlag&16)if(h)p(e,t,f,f&&o(f));else{t.anchor=o(e);let b=f;for(;b;){if(b&&b.nodeType===8){if(b.data==="teleport start anchor")t.targetStart=b;else if(b.data==="teleport anchor"){t.targetAnchor=b,m._lpa=t.targetAnchor&&o(t.targetAnchor);break}}b=o(b)}t.targetAnchor||fy(m,t,c,u),d(f&&o(f),t,m,r,n,i,s)}ja(t,h)}else h&&t.shapeFlag&16&&p(e,t,e,o(e));return t.anchor&&o(t.anchor)}const Sx=hy;function ja(e,t){const r=e.ctx;if(r&&r.ut){let n,i;for(t?(n=e.el,i=e.anchor):(n=e.targetStart,i=e.targetAnchor);n&&n!==i;)n.nodeType===1&&n.setAttribute("data-v-owner",r.uid),n=n.nextSibling;r.ut()}}function fy(e,t,r,n){const i=t.targetStart=r(""),s=t.targetAnchor=r("");return i[uy]=s,e&&(n(i,e),n(s,e)),s}const sn=Symbol("_leaveCb"),ka=Symbol("_enterCb");function hf(){const e={isMounted:!1,isLeaving:!1,isUnmounting:!1,leavingVNodes:new Map};return Xo(()=>{e.isMounted=!0}),bc(()=>{e.isUnmounting=!0}),e}const cr=[Function,Array],ff={mode:String,appear:Boolean,persisted:Boolean,onBeforeEnter:cr,onEnter:cr,onAfterEnter:cr,onEnterCancelled:cr,onBeforeLeave:cr,onLeave:cr,onAfterLeave:cr,onLeaveCancelled:cr,onBeforeAppear:cr,onAppear:cr,onAfterAppear:cr,onAppearCancelled:cr},py=e=>{const t=e.subTree;return t.component?py(t.component):t},Cx={name:"BaseTransition",props:ff,setup(e,{slots:t}){const r=zt(),n=hf();return()=>{const i=t.default&&pc(t.default(),!0);if(!i||!i.length)return;const s=my(i),o=ye(e),{mode:a}=o;if(n.isLeaving)return hu(s);const l=em(s);if(!l)return hu(s);let u=os(l,o,n,r,d=>u=d);l.type!==Ge&&_n(l,u);let c=r.subTree&&em(r.subTree);if(c&&c.type!==Ge&&!xr(c,l)&&py(r).type!==Ge){let d=os(c,o,n,r);if(_n(c,d),a==="out-in"&&l.type!==Ge)return n.isLeaving=!0,d.afterLeave=()=>{n.isLeaving=!1,r.job.flags&8||r.update(),delete d.afterLeave,c=void 0},hu(s);a==="in-out"&&l.type!==Ge?d.delayLeave=(p,m,h)=>{const f=by(n,c);f[String(c.key)]=c,p[sn]=()=>{m(),p[sn]=void 0,delete u.delayedLeave,c=void 0},u.delayedLeave=()=>{h(),delete u.delayedLeave,c=void 0}}:c=void 0}else c&&(c=void 0);return s}}};function my(e){let t=e[0];if(e.length>1){for(const r of e)if(r.type!==Ge){t=r;break}}return t}const gy=Cx;function by(e,t){const{leavingVNodes:r}=e;let n=r.get(t.type);return n||(n=Object.create(null),r.set(t.type,n)),n}function os(e,t,r,n,i){const{appear:s,mode:o,persisted:a=!1,onBeforeEnter:l,onEnter:u,onAfterEnter:c,onEnterCancelled:d,onBeforeLeave:p,onLeave:m,onAfterLeave:h,onLeaveCancelled:f,onBeforeAppear:b,onAppear:y,onAfterAppear:w,onAppearCancelled:v}=t,_=String(e.key),x=by(r,e),k=(A,T)=>{A&&pr(A,n,9,T)},S=(A,T)=>{const I=T[1];k(A,T),de(A)?A.every(C=>C.length<=1)&&I():A.length<=1&&I()},N={mode:o,persisted:a,beforeEnter(A){let T=l;if(!r.isMounted)if(s)T=b||l;else return;A[sn]&&A[sn](!0);const I=x[_];I&&xr(e,I)&&I.el[sn]&&I.el[sn](),k(T,[A])},enter(A){let T=u,I=c,C=d;if(!r.isMounted)if(s)T=y||u,I=w||c,C=v||d;else return;let g=!1;const L=A[ka]=B=>{g||(g=!0,B?k(C,[A]):k(I,[A]),N.delayedLeave&&N.delayedLeave(),A[ka]=void 0)};T?S(T,[A,L]):L()},leave(A,T){const I=String(e.key);if(A[ka]&&A[ka](!0),r.isUnmounting)return T();k(p,[A]);let C=!1;const g=A[sn]=L=>{C||(C=!0,T(),L?k(f,[A]):k(h,[A]),A[sn]=void 0,x[I]===e&&delete x[I])};x[I]=e,m?S(m,[A,g]):g()},clone(A){const T=os(A,t,r,n,i);return i&&i(T),T}};return N}function hu(e){if(Yo(e))return e=Kr(e),e.children=null,e}function em(e){if(!Yo(e))return dy(e.type)&&e.children?my(e.children):e;if(e.component)return e.component.subTree;const{shapeFlag:t,children:r}=e;if(r){if(t&16)return r[0];if(t&32&&ce(r.default))return r.default()}}function _n(e,t){e.shapeFlag&6&&e.component?(e.transition=t,_n(e.component.subTree,t)):e.shapeFlag&128?(e.ssContent.transition=t.clone(e.ssContent),e.ssFallback.transition=t.clone(e.ssFallback)):e.transition=t}function pc(e,t=!1,r){let n=[],i=0;for(let s=0;s<e.length;s++){let o=e[s];const a=r==null?o.key:String(r)+String(o.key!=null?o.key:s);o.type===it?(o.patchFlag&128&&i++,n=n.concat(pc(o.children,t,a))):(t||o.type!==Ge)&&n.push(a!=null?Kr(o,{key:a}):o)}if(i>1)for(let s=0;s<n.length;s++)n[s].patchFlag=-2;return n}function Cs(e,t){return ce(e)?pt({name:e.name},t,{setup:e}):e}function Ax(){const e=zt();return e?(e.appContext.config.idPrefix||"v")+"-"+e.ids[0]+e.ids[1]++:""}function pf(e){e.ids=[e.ids[0]+e.ids[2]+++"-",0,0]}function Tx(e){const t=zt(),r=of(null);if(t){const i=t.refs===Ee?t.refs={}:t.refs;Object.defineProperty(i,e,{enumerable:!0,get:()=>r.value,set:s=>r.value=s})}return r}const ml=new WeakMap;function ts(e,t,r,n,i=!1){if(de(e)){e.forEach((h,f)=>ts(h,t&&(de(t)?t[f]:t),r,n,i));return}if(fn(n)&&!i){n.shapeFlag&512&&n.type.__asyncResolved&&n.component.subTree.component&&ts(e,t,r,n.component.subTree);return}const s=n.shapeFlag&4?Jo(n.component):n.el,o=i?null:s,{i:a,r:l}=e,u=t&&t.r,c=a.refs===Ee?a.refs={}:a.refs,d=a.setupState,p=ye(d),m=d===Ee?Kv:h=>Le(p,h);if(u!=null&&u!==l){if(tm(t),et(u))c[u]=null,m(u)&&(d[u]=null);else if(Ze(u)){u.value=null;const h=t;h.k&&(c[h.k]=null)}}if(ce(l))Ss(l,a,12,[o,c]);else{const h=et(l),f=Ze(l);if(h||f){const b=()=>{if(e.f){const y=h?m(l)?d[l]:c[l]:l.value;if(i)de(y)&&Yv(y,s);else if(de(y))y.includes(s)||y.push(s);else if(h)c[l]=[s],m(l)&&(d[l]=c[l]);else{const w=[s];l.value=w,e.k&&(c[e.k]=w)}}else h?(c[l]=o,m(l)&&(d[l]=o)):f&&(l.value=o,e.k&&(c[e.k]=o))};if(o){const y=()=>{b(),ml.delete(e)};y.id=-1,ml.set(e,y),Je(y,r)}else tm(e),b()}}}function tm(e){const t=ml.get(e);t&&(t.flags|=8,ml.delete(e))}let rm=!1;const Hi=()=>{rm||(console.error("Hydration completed but contains mismatches."),rm=!0)},kx=e=>e.namespaceURI.includes("svg")&&e.tagName!=="foreignObject",Ox=e=>e.namespaceURI.includes("MathML"),Oa=e=>{if(e.nodeType===1){if(kx(e))return"svg";if(Ox(e))return"mathml"}},Ji=e=>e.nodeType===8;function Nx(e){const{mt:t,p:r,o:{patchProp:n,createText:i,nextSibling:s,parentNode:o,remove:a,insert:l,createComment:u}}=e,c=(v,_)=>{if(!_.hasChildNodes()){r(null,v,_),pl(),_._vnode=v;return}d(_.firstChild,v,null,null,null),pl(),_._vnode=v},d=(v,_,x,k,S,N=!1)=>{N=N||!!_.dynamicChildren;const A=Ji(v)&&v.data==="[",T=()=>f(v,_,x,k,S,A),{type:I,ref:C,shapeFlag:g,patchFlag:L}=_;let B=v.nodeType;_.el=v,L===-2&&(N=!1,_.dynamicChildren=null);let M=null;switch(I){case Kn:B!==3?_.children===""?(l(_.el=i(""),o(v),v),M=v):M=T():(v.data!==_.children&&(Hi(),v.data=_.children),M=s(v));break;case Ge:w(v)?(M=s(v),y(_.el=v.content.firstChild,v,x)):B!==8||A?M=T():M=s(v);break;case xi:if(A&&(v=s(v),B=v.nodeType),B===1||B===3){M=v;const z=!_.children.length;for(let P=0;P<_.staticCount;P++)z&&(_.children+=M.nodeType===1?M.outerHTML:M.data),P===_.staticCount-1&&(_.anchor=M),M=s(M);return A?s(M):M}else T();break;case it:A?M=h(v,_,x,k,S,N):M=T();break;default:if(g&1)(B!==1||_.type.toLowerCase()!==v.tagName.toLowerCase())&&!w(v)?M=T():M=p(v,_,x,k,S,N);else if(g&6){_.slotScopeIds=S;const z=o(v);if(A?M=b(v):Ji(v)&&v.data==="teleport start"?M=b(v,v.data,"teleport end"):M=s(v),t(_,z,null,x,k,Oa(z),N),fn(_)&&!_.type.__asyncResolved){let P;A?(P=Be(it),P.anchor=M?M.previousSibling:z.lastChild):P=v.nodeType===3?Sf(""):Be("div"),P.el=v,_.component.subTree=P}}else g&64?B!==8?M=T():M=_.type.hydrate(v,_,x,k,S,N,e,m):g&128&&(M=_.type.hydrate(v,_,x,k,Oa(o(v)),S,N,e,d))}return C!=null&&ts(C,null,k,_),M},p=(v,_,x,k,S,N)=>{N=N||!!_.dynamicChildren;const{type:A,props:T,patchFlag:I,shapeFlag:C,dirs:g,transition:L}=_,B=A==="input"||A==="option";if(B||I!==-1){g&&Br(_,null,x,"created");let M=!1;if(w(v)){M=Hy(null,L)&&x&&x.vnode.props&&x.vnode.props.appear;const P=v.content.firstChild;if(M){const Z=P.getAttribute("class");Z&&(P.$cls=Z),L.beforeEnter(P)}y(P,v,x),_.el=v=P}if(C&16&&!(T&&(T.innerHTML||T.textContent))){let P=m(v.firstChild,_,v,x,k,S,N);for(;P;){Na(v,1)||Hi();const Z=P;P=P.nextSibling,a(Z)}}else if(C&8){let P=_.children;P[0]===`
`&&(v.tagName==="PRE"||v.tagName==="TEXTAREA")&&(P=P.slice(1));const{textContent:Z}=v;Z!==P&&Z!==P.replace(/\r\n|\r/g,`
`)&&(Na(v,0)||Hi(),v.textContent=_.children)}if(T){if(B||!N||I&48){const P=v.tagName.includes("-");for(const Z in T)(B&&(Z.endsWith("value")||Z==="indeterminate")||ac(Z)&&!_i(Z)||Z[0]==="."||P&&!_i(Z))&&n(v,Z,null,T[Z],void 0,x)}else if(T.onClick)n(v,"onClick",null,T.onClick,void 0,x);else if(I&4&&hn(T.style))for(const P in T.style)T.style[P]}let z;(z=T&&T.onVnodeBeforeMount)&&Mt(z,x,_),g&&Br(_,null,x,"beforeMount"),((z=T&&T.onVnodeMounted)||g||M)&&Ky(()=>{z&&Mt(z,x,_),M&&L.enter(v),g&&Br(_,null,x,"mounted")},k)}return v.nextSibling},m=(v,_,x,k,S,N,A)=>{A=A||!!_.dynamicChildren;const T=_.children,I=T.length;for(let C=0;C<I;C++){const g=A?T[C]:T[C]=Dt(T[C]),L=g.type===Kn;v?(L&&!A&&C+1<I&&Dt(T[C+1]).type===Kn&&(l(i(v.data.slice(g.children.length)),x,s(v)),v.data=g.children),v=d(v,g,k,S,N,A)):L&&!g.children?l(g.el=i(""),x):(Na(x,1)||Hi(),r(null,g,x,null,k,S,Oa(x),N))}return v},h=(v,_,x,k,S,N)=>{const{slotScopeIds:A}=_;A&&(S=S?S.concat(A):A);const T=o(v),I=m(s(v),_,T,x,k,S,N);return I&&Ji(I)&&I.data==="]"?s(_.anchor=I):(Hi(),l(_.anchor=u("]"),T,I),I)},f=(v,_,x,k,S,N)=>{if(Na(v.parentElement,1)||Hi(),_.el=null,N){const I=b(v);for(;;){const C=s(v);if(C&&C!==I)a(C);else break}}const A=s(v),T=o(v);return a(v),r(null,_,T,A,x,k,Oa(T),S),x&&(x.vnode.el=_.el,_c(x,_.el)),A},b=(v,_="[",x="]")=>{let k=0;for(;v;)if(v=s(v),v&&Ji(v)&&(v.data===_&&k++,v.data===x)){if(k===0)return s(v);k--}return v},y=(v,_,x)=>{const k=_.parentNode;k&&k.replaceChild(v,_);let S=x;for(;S;)S.vnode.el===_&&(S.vnode.el=S.subTree.el=v),S=S.parent},w=v=>v.nodeType===1&&v.tagName==="TEMPLATE";return[c,d]}const nm="data-allow-mismatch",Px={0:"text",1:"children",2:"class",3:"style",4:"attribute"};function Na(e,t){if(t===0||t===1)for(;e&&!e.hasAttribute(nm);)e=e.parentElement;const r=e&&e.getAttribute(nm);if(r==null)return!1;if(r==="")return!0;{const n=r.split(",");return t===0&&n.includes("children")?!0:n.includes(Px[t])}}const Ix=dc().requestIdleCallback||(e=>setTimeout(e,1)),Fx=dc().cancelIdleCallback||(e=>clearTimeout(e)),Lx=(e=1e4)=>t=>{const r=Ix(t,{timeout:e});return()=>Fx(r)};function Rx(e){const{top:t,left:r,bottom:n,right:i}=e.getBoundingClientRect(),{innerHeight:s,innerWidth:o}=window;return(t>0&&t<s||n>0&&n<s)&&(r>0&&r<o||i>0&&i<o)}const Mx=e=>(t,r)=>{const n=new IntersectionObserver(i=>{for(const s of i)if(s.isIntersecting){n.disconnect(),t();break}},e);return r(i=>{if(i instanceof Element){if(Rx(i))return t(),n.disconnect(),!1;n.observe(i)}}),()=>n.disconnect()},Dx=e=>t=>{if(e){const r=matchMedia(e);if(r.matches)t();else return r.addEventListener("change",t,{once:!0}),()=>r.removeEventListener("change",t)}},$x=(e=[])=>(t,r)=>{et(e)&&(e=[e]);let n=!1;const i=o=>{n||(n=!0,s(),t(),o.target.dispatchEvent(new o.constructor(o.type,o)))},s=()=>{r(o=>{for(const a of e)o.removeEventListener(a,i)})};return r(o=>{for(const a of e)o.addEventListener(a,i,{once:!0})}),s};function Vx(e,t){if(Ji(e)&&e.data==="["){let r=1,n=e.nextSibling;for(;n;){if(n.nodeType===1){if(t(n)===!1)break}else if(Ji(n))if(n.data==="]"){if(--r===0)break}else n.data==="["&&r++;n=n.nextSibling}}else t(e)}const fn=e=>!!e.type.__asyncLoader;function Bx(e){ce(e)&&(e={loader:e});const{loader:t,loadingComponent:r,errorComponent:n,delay:i=200,hydrate:s,timeout:o,suspensible:a=!0,onError:l}=e;let u=null,c,d=0;const p=()=>(d++,u=null,m()),m=()=>{let h;return u||(h=u=t().catch(f=>{if(f=f instanceof Error?f:new Error(String(f)),l)return new Promise((b,y)=>{l(f,()=>b(p()),()=>y(f),d+1)});throw f}).then(f=>h!==u&&u?u:(f&&(f.__esModule||f[Symbol.toStringTag]==="Module")&&(f=f.default),c=f,f)))};return Cs({name:"AsyncComponentWrapper",__asyncLoader:m,__asyncHydrate(h,f,b){let y=!1;(f.bu||(f.bu=[])).push(()=>y=!0);const w=()=>{y||b()},v=s?()=>{const _=s(w,x=>Vx(h,x));_&&(f.bum||(f.bum=[])).push(_)}:w;c?v():m().then(()=>!f.isUnmounted&&v())},get __asyncResolved(){return c},setup(){const h=bt;if(pf(h),c)return()=>Pa(c,h);const f=v=>{u=null,Ri(v,h,13,!n)};if(a&&h.suspense||as)return m().then(v=>()=>Pa(v,h)).catch(v=>(f(v),()=>n?Be(n,{error:v}):null));const b=Wn(!1),y=Wn(),w=Wn(!!i);return i&&setTimeout(()=>{w.value=!1},i),o!=null&&setTimeout(()=>{if(!b.value&&!y.value){const v=new Error(`Async component timed out after ${o}ms.`);f(v),y.value=v}},o),m().then(()=>{b.value=!0,h.parent&&Yo(h.parent.vnode)&&h.parent.update()}).catch(v=>{f(v),y.value=v}),()=>{if(b.value&&c)return Pa(c,h);if(y.value&&n)return Be(n,{error:y.value});if(r&&!w.value)return Pa(r,h)}}})}function Pa(e,t){const{ref:r,props:n,children:i,ce:s}=t.vnode,o=Be(e,n,i);return o.ref=r,o.ce=s,delete t.vnode.ce,o}const Yo=e=>e.type.__isKeepAlive,Ux={name:"KeepAlive",__isKeepAlive:!0,props:{include:[String,RegExp,Array],exclude:[String,RegExp,Array],max:[String,Number]},setup(e,{slots:t}){const r=zt(),n=r.ctx;if(!n.renderer)return()=>{const w=t.default&&t.default();return w&&w.length===1?w[0]:w};const i=new Map,s=new Set;let o=null;const a=r.suspense,{renderer:{p:l,m:u,um:c,o:{createElement:d}}}=n,p=d("div");n.activate=(w,v,_,x,k)=>{const S=w.component;u(w,v,_,0,a),l(S.vnode,w,v,_,S,a,x,w.slotScopeIds,k),Je(()=>{S.isDeactivated=!1,S.a&&so(S.a);const N=w.props&&w.props.onVnodeMounted;N&&Mt(N,S.parent,w)},a)},n.deactivate=w=>{const v=w.component;bl(v.m),bl(v.a),u(w,p,null,1,a),Je(()=>{v.da&&so(v.da);const _=w.props&&w.props.onVnodeUnmounted;_&&Mt(_,v.parent,w),v.isDeactivated=!0},a)};function m(w){fu(w),c(w,r,a,!0)}function h(w){i.forEach((v,_)=>{const x=ah(fn(v)?v.type.__asyncResolved||{}:v.type);x&&!w(x)&&f(_)})}function f(w){const v=i.get(w);v&&(!o||!xr(v,o))?m(v):o&&fu(o),i.delete(w),s.delete(w)}wi(()=>[e.include,e.exclude],([w,v])=>{w&&h(_=>Ks(w,_)),v&&h(_=>!Ks(v,_))},{flush:"post",deep:!0});let b=null;const y=()=>{b!=null&&(vl(r.subTree.type)?Je(()=>{i.set(b,Ia(r.subTree))},r.subTree.suspense):i.set(b,Ia(r.subTree)))};return Xo(y),gc(y),bc(()=>{i.forEach(w=>{const{subTree:v,suspense:_}=r,x=Ia(v);if(w.type===x.type&&w.key===x.key){fu(x);const k=x.component.da;k&&Je(k,_);return}m(w)})}),()=>{if(b=null,!t.default)return o=null;const w=t.default(),v=w[0];if(w.length>1)return o=null,w;if(!wn(v)||!(v.shapeFlag&4)&&!(v.shapeFlag&128))return o=null,v;let _=Ia(v);if(_.type===Ge)return o=null,_;const x=_.type,k=ah(fn(_)?_.type.__asyncResolved||{}:x),{include:S,exclude:N,max:A}=e;if(S&&(!k||!Ks(S,k))||N&&k&&Ks(N,k))return _.shapeFlag&=-257,o=_,v;const T=_.key==null?x:_.key,I=i.get(T);return _.el&&(_=Kr(_),v.shapeFlag&128&&(v.ssContent=_)),b=T,I?(_.el=I.el,_.component=I.component,_.transition&&_n(_,_.transition),_.shapeFlag|=512,s.delete(T),s.add(T)):(s.add(T),A&&s.size>parseInt(A,10)&&f(s.values().next().value)),_.shapeFlag|=256,o=_,vl(v.type)?v:_}}},zx=Ux;function Ks(e,t){return de(e)?e.some(r=>Ks(r,t)):et(e)?e.split(",").includes(t):G1(e)?(e.lastIndex=0,e.test(t)):!1}function vy(e,t){_y(e,"a",t)}function yy(e,t){_y(e,"da",t)}function _y(e,t,r=bt){const n=e.__wdc||(e.__wdc=()=>{let i=r;for(;i;){if(i.isDeactivated)return;i=i.parent}return e()});if(mc(t,n,r),r){let i=r.parent;for(;i&&i.parent;)Yo(i.parent.vnode)&&Hx(n,t,r,i),i=i.parent}}function Hx(e,t,r,n){const i=mc(t,e,n,!0);vc(()=>{Yv(n[t],i)},r)}function fu(e){e.shapeFlag&=-257,e.shapeFlag&=-513}function Ia(e){return e.shapeFlag&128?e.ssContent:e}function mc(e,t,r=bt,n=!1){if(r){const i=r[e]||(r[e]=[]),s=t.__weh||(t.__weh=(...o)=>{vn();const a=Oi(r),l=pr(t,r,e,o);return a(),yn(),l});return n?i.unshift(s):i.push(s),s}}const En=e=>(t,r=bt)=>{(!as||e==="sp")&&mc(e,(...n)=>t(...n),r)},wy=En("bm"),Xo=En("m"),mf=En("bu"),gc=En("u"),bc=En("bum"),vc=En("um"),Ey=En("sp"),xy=En("rtg"),Sy=En("rtc");function Cy(e,t=bt){mc("ec",e,t)}const gf="components",qx="directives";function jx(e,t){return bf(gf,e,!0,t)||e}const Ay=Symbol.for("v-ndc");function Wx(e){return et(e)?bf(gf,e,!1)||e:e||Ay}function Kx(e){return bf(qx,e)}function bf(e,t,r=!0,n=!1){const i=vt||bt;if(i){const s=i.type;if(e===gf){const a=ah(s,!1);if(a&&(a===t||a===tr(t)||a===uc(tr(t))))return s}const o=im(i[e]||s[e],t)||im(i.appContext[e],t);return!o&&n?s:o}}function im(e,t){return e&&(e[t]||e[tr(t)]||e[uc(tr(t))])}function Gx(e,t,r,n){let i;const s=r&&r[n],o=de(e);if(o||et(e)){const a=o&&hn(e);let l=!1,u=!1;a&&(l=!Ut(e),u=Wr(e),e=ic(e)),i=new Array(e.length);for(let c=0,d=e.length;c<d;c++)i[c]=t(l?u?ss(kr(e[c])):kr(e[c]):e[c],c,void 0,s&&s[c])}else if(typeof e=="number"){i=new Array(e);for(let a=0;a<e;a++)i[a]=t(a+1,a,void 0,s&&s[a])}else if(ot(e))if(e[Symbol.iterator])i=Array.from(e,(a,l)=>t(a,l,void 0,s&&s[l]));else{const a=Object.keys(e);i=new Array(a.length);for(let l=0,u=a.length;l<u;l++){const c=a[l];i[l]=t(e[c],c,l,s&&s[l])}}else i=[];return r&&(r[n]=i),i}function Yx(e,t){for(let r=0;r<t.length;r++){const n=t[r];if(de(n))for(let i=0;i<n.length;i++)e[n[i].name]=n[i].fn;else n&&(e[n.name]=n.key?(...i)=>{const s=n.fn(...i);return s&&(s.key=n.key),s}:n.fn)}return e}function Xx(e,t,r={},n,i){if(vt.ce||vt.parent&&fn(vt.parent)&&vt.parent.ce){const u=Object.keys(r).length>0;return t!=="default"&&(r.name=t),No(),yl(it,null,[Be("slot",r,n&&n())],u?-2:64)}let s=e[t];s&&s._c&&(s._d=!1),No();const o=s&&vf(s(r)),a=r.key||o&&o.key,l=yl(it,{key:(a&&!lf(a)?a:`_${t}`)+(!o&&n?"_fb":"")},o||(n?n():[]),o&&e._===1?64:-2);return!i&&l.scopeId&&(l.slotScopeIds=[l.scopeId+"-s"]),s&&s._c&&(s._d=!0),l}function vf(e){return e.some(t=>wn(t)?!(t.type===Ge||t.type===it&&!vf(t.children)):!0)?e:null}function Jx(e,t){const r={};for(const n in e)r[t&&/[A-Z]/.test(n)?`on:${n}`:io(n)]=e[n];return r}const Jd=e=>e?e_(e)?Jo(e):Jd(e.parent):null,lo=pt(Object.create(null),{$:e=>e,$el:e=>e.vnode.el,$data:e=>e.data,$props:e=>e.props,$attrs:e=>e.attrs,$slots:e=>e.slots,$refs:e=>e.refs,$parent:e=>Jd(e.parent),$root:e=>Jd(e.root),$host:e=>e.ce,$emit:e=>e.emit,$options:e=>yf(e),$forceUpdate:e=>e.f||(e.f=()=>{uf(e.update)}),$nextTick:e=>e.n||(e.n=hc.bind(e.proxy)),$watch:e=>Ex.bind(e)}),pu=(e,t)=>e!==Ee&&!e.__isScriptSetup&&Le(e,t),Qd={get({_:e},t){if(t==="__v_skip")return!0;const{ctx:r,setupState:n,data:i,props:s,accessCache:o,type:a,appContext:l}=e;if(t[0]!=="$"){const p=o[t];if(p!==void 0)switch(p){case 1:return n[t];case 2:return i[t];case 4:return r[t];case 3:return s[t]}else{if(pu(n,t))return o[t]=1,n[t];if(i!==Ee&&Le(i,t))return o[t]=2,i[t];if(Le(s,t))return o[t]=3,s[t];if(r!==Ee&&Le(r,t))return o[t]=4,r[t];Zd&&(o[t]=0)}}const u=lo[t];let c,d;if(u)return t==="$attrs"&&xt(e.attrs,"get",""),u(e);if((c=a.__cssModules)&&(c=c[t]))return c;if(r!==Ee&&Le(r,t))return o[t]=4,r[t];if(d=l.config.globalProperties,Le(d,t))return d[t]},set({_:e},t,r){const{data:n,setupState:i,ctx:s}=e;return pu(i,t)?(i[t]=r,!0):n!==Ee&&Le(n,t)?(n[t]=r,!0):Le(e.props,t)||t[0]==="$"&&t.slice(1)in e?!1:(s[t]=r,!0)},has({_:{data:e,setupState:t,accessCache:r,ctx:n,appContext:i,props:s,type:o}},a){let l;return!!(r[a]||e!==Ee&&a[0]!=="$"&&Le(e,a)||pu(t,a)||Le(s,a)||Le(n,a)||Le(lo,a)||Le(i.config.globalProperties,a)||(l=o.__cssModules)&&l[a])},defineProperty(e,t,r){return r.get!=null?e._.accessCache[t]=0:Le(r,"value")&&this.set(e,t,r.value,null),Reflect.defineProperty(e,t,r)}},Qx=pt({},Qd,{get(e,t){if(t!==Symbol.unscopables)return Qd.get(e,t,e)},has(e,t){return t[0]!=="_"&&!rx(t)}});function Zx(){return null}function eS(){return null}function tS(e){}function rS(e){}function nS(){return null}function iS(){}function sS(e,t){return null}function oS(){return Ty().slots}function aS(){return Ty().attrs}function Ty(e){const t=zt();return t.setupContext||(t.setupContext=i_(t))}function ko(e){return de(e)?e.reduce((t,r)=>(t[r]=null,t),{}):e}function lS(e,t){const r=ko(e);for(const n in t){if(n.startsWith("__skip"))continue;let i=r[n];i?de(i)||ce(i)?i=r[n]={type:i,default:t[n]}:i.default=t[n]:i===null&&(i=r[n]={default:t[n]}),i&&t[`__skip_${n}`]&&(i.skipFactory=!0)}return r}function cS(e,t){return!e||!t?e||t:de(e)&&de(t)?e.concat(t):pt({},ko(e),ko(t))}function uS(e,t){const r={};for(const n in e)t.includes(n)||Object.defineProperty(r,n,{enumerable:!0,get:()=>e[n]});return r}function dS(e){const t=zt();let r=e();return ih(),cf(r)&&(r=r.catch(n=>{throw Oi(t),n})),[r,()=>Oi(t)]}let Zd=!0;function hS(e){const t=yf(e),r=e.proxy,n=e.ctx;Zd=!1,t.beforeCreate&&sm(t.beforeCreate,e,"bc");const{data:i,computed:s,methods:o,watch:a,provide:l,inject:u,created:c,beforeMount:d,mounted:p,beforeUpdate:m,updated:h,activated:f,deactivated:b,beforeDestroy:y,beforeUnmount:w,destroyed:v,unmounted:_,render:x,renderTracked:k,renderTriggered:S,errorCaptured:N,serverPrefetch:A,expose:T,inheritAttrs:I,components:C,directives:g,filters:L}=t;if(u&&fS(u,n,null),o)for(const z in o){const P=o[z];ce(P)&&(n[z]=P.bind(r))}if(i){const z=i.call(r,r);ot(z)&&(e.data=Es(z))}if(Zd=!0,s)for(const z in s){const P=s[z],Z=ce(P)?P.bind(r,r):ce(P.get)?P.get.bind(r,r):Hr,be=!ce(P)&&ce(P.set)?P.set.bind(r):Hr,le=Pt({get:Z,set:be});Object.defineProperty(n,z,{enumerable:!0,configurable:!0,get:()=>le.value,set:qe=>le.value=qe})}if(a)for(const z in a)ky(a[z],n,r,z);if(l){const z=ce(l)?l.call(r):l;Reflect.ownKeys(z).forEach(P=>{sy(P,z[P])})}c&&sm(c,e,"c");function M(z,P){de(P)?P.forEach(Z=>z(Z.bind(r))):P&&z(P.bind(r))}if(M(wy,d),M(Xo,p),M(mf,m),M(gc,h),M(vy,f),M(yy,b),M(Cy,N),M(Sy,k),M(xy,S),M(bc,w),M(vc,_),M(Ey,A),de(T))if(T.length){const z=e.exposed||(e.exposed={});T.forEach(P=>{Object.defineProperty(z,P,{get:()=>r[P],set:Z=>r[P]=Z,enumerable:!0})})}else e.exposed||(e.exposed={});x&&e.render===Hr&&(e.render=x),I!=null&&(e.inheritAttrs=I),C&&(e.components=C),g&&(e.directives=g),A&&pf(e)}function fS(e,t,r=Hr){de(e)&&(e=eh(e));for(const n in e){const i=e[n];let s;ot(i)?"default"in i?s=oo(i.from||n,i.default,!0):s=oo(i.from||n):s=oo(i),Ze(s)?Object.defineProperty(t,n,{enumerable:!0,configurable:!0,get:()=>s.value,set:o=>s.value=o}):t[n]=s}}function sm(e,t,r){pr(de(e)?e.map(n=>n.bind(t.proxy)):e.bind(t.proxy),t,r)}function ky(e,t,r,n){let i=n.includes(".")?cy(r,n):()=>r[n];if(et(e)){const s=t[e];ce(s)&&wi(i,s)}else if(ce(e))wi(i,e.bind(r));else if(ot(e))if(de(e))e.forEach(s=>ky(s,t,r,n));else{const s=ce(e.handler)?e.handler.bind(r):t[e.handler];ce(s)&&wi(i,s,e)}}function yf(e){const t=e.type,{mixins:r,extends:n}=t,{mixins:i,optionsCache:s,config:{optionMergeStrategies:o}}=e.appContext,a=s.get(t);let l;return a?l=a:!i.length&&!r&&!n?l=t:(l={},i.length&&i.forEach(u=>gl(l,u,o,!0)),gl(l,t,o)),ot(t)&&s.set(t,l),l}function gl(e,t,r,n=!1){const{mixins:i,extends:s}=t;s&&gl(e,s,r,!0),i&&i.forEach(o=>gl(e,o,r,!0));for(const o in t)if(!(n&&o==="expose")){const a=pS[o]||r&&r[o];e[o]=a?a(e[o],t[o]):t[o]}return e}const pS={data:om,props:am,emits:am,methods:Gs,computed:Gs,beforeCreate:Nt,created:Nt,beforeMount:Nt,mounted:Nt,beforeUpdate:Nt,updated:Nt,beforeDestroy:Nt,beforeUnmount:Nt,destroyed:Nt,unmounted:Nt,activated:Nt,deactivated:Nt,errorCaptured:Nt,serverPrefetch:Nt,components:Gs,directives:Gs,watch:gS,provide:om,inject:mS};function om(e,t){return t?e?function(){return pt(ce(e)?e.call(this,this):e,ce(t)?t.call(this,this):t)}:t:e}function mS(e,t){return Gs(eh(e),eh(t))}function eh(e){if(de(e)){const t={};for(let r=0;r<e.length;r++)t[e[r]]=e[r];return t}return e}function Nt(e,t){return e?[...new Set([].concat(e,t))]:t}function Gs(e,t){return e?pt(Object.create(null),e,t):t}function am(e,t){return e?de(e)&&de(t)?[...new Set([...e,...t])]:pt(Object.create(null),ko(e),ko(t??{})):t}function gS(e,t){if(!e)return t;if(!t)return e;const r=pt(Object.create(null),e);for(const n in t)r[n]=Nt(e[n],t[n]);return r}function Oy(){return{app:null,config:{isNativeTag:Kv,performance:!1,globalProperties:{},optionMergeStrategies:{},errorHandler:void 0,warnHandler:void 0,compilerOptions:{}},mixins:[],components:{},directives:{},provides:Object.create(null),optionsCache:new WeakMap,propsCache:new WeakMap,emitsCache:new WeakMap}}let bS=0;function vS(e,t){return function(n,i=null){ce(n)||(n=pt({},n)),i!=null&&!ot(i)&&(i=null);const s=Oy(),o=new WeakSet,a=[];let l=!1;const u=s.app={_uid:bS++,_component:n,_props:i,_container:null,_context:s,_instance:null,version:o_,get config(){return s.config},set config(c){},use(c,...d){return o.has(c)||(c&&ce(c.install)?(o.add(c),c.install(u,...d)):ce(c)&&(o.add(c),c(u,...d))),u},mixin(c){return s.mixins.includes(c)||s.mixins.push(c),u},component(c,d){return d?(s.components[c]=d,u):s.components[c]},directive(c,d){return d?(s.directives[c]=d,u):s.directives[c]},mount(c,d,p){if(!l){const m=u._ceVNode||Be(n,i);return m.appContext=s,p===!0?p="svg":p===!1&&(p=void 0),d&&t?t(m,c):e(m,c,p),l=!0,u._container=c,c.__vue_app__=u,Jo(m.component)}},onUnmount(c){a.push(c)},unmount(){l&&(pr(a,u._instance,16),e(null,u._container),delete u._container.__vue_app__)},provide(c,d){return s.provides[c]=d,u},runWithContext(c){const d=Ei;Ei=u;try{return c()}finally{Ei=d}}};return u}}let Ei=null;function yS(e,t,r=Ee){const n=zt(),i=tr(t),s=xs(t),o=Ny(e,i),a=Hv((l,u)=>{let c,d=Ee,p;return ly(()=>{const m=e[i];hi(c,m)&&(c=m,u())}),{get(){return l(),r.get?r.get(c):c},set(m){const h=r.set?r.set(m):m;if(!hi(h,c)&&!(d!==Ee&&hi(m,d)))return;const f=n.vnode.props;f&&(t in f||i in f||s in f)&&(`onUpdate:${t}`in f||`onUpdate:${i}`in f||`onUpdate:${s}`in f)||(c=m,u()),n.emit(`update:${t}`,h),hi(m,h)&&hi(m,d)&&!hi(h,p)&&u(),d=m,p=h}}});return a[Symbol.iterator]=()=>{let l=0;return{next(){return l<2?{value:l++?o||Ee:a,done:!1}:{done:!0}}}},a}const Ny=(e,t)=>t==="modelValue"||t==="model-value"?e.modelModifiers:e[`${t}Modifiers`]||e[`${tr(t)}Modifiers`]||e[`${xs(t)}Modifiers`];function _S(e,t,...r){if(e.isUnmounted)return;const n=e.vnode.props||Ee;let i=r;const s=t.startsWith("update:"),o=s&&Ny(n,t.slice(7));o&&(o.trim&&(i=r.map(c=>et(c)?c.trim():c)),o.number&&(i=r.map(Z1)));let a,l=n[a=io(t)]||n[a=io(tr(t))];!l&&s&&(l=n[a=io(xs(t))]),l&&pr(l,e,6,i);const u=n[a+"Once"];if(u){if(!e.emitted)e.emitted={};else if(e.emitted[a])return;e.emitted[a]=!0,pr(u,e,6,i)}}const wS=new WeakMap;function Py(e,t,r=!1){const n=r?wS:t.emitsCache,i=n.get(e);if(i!==void 0)return i;const s=e.emits;let o={},a=!1;if(!ce(e)){const l=u=>{const c=Py(u,t,!0);c&&(a=!0,pt(o,c))};!r&&t.mixins.length&&t.mixins.forEach(l),e.extends&&l(e.extends),e.mixins&&e.mixins.forEach(l)}return!s&&!a?(ot(e)&&n.set(e,null),null):(de(s)?s.forEach(l=>o[l]=null):pt(o,s),ot(e)&&n.set(e,o),o)}function yc(e,t){return!e||!ac(t)?!1:(t=t.slice(2).replace(/Once$/,""),Le(e,t[0].toLowerCase()+t.slice(1))||Le(e,xs(t))||Le(e,t))}function Wa(e){const{type:t,vnode:r,proxy:n,withProxy:i,propsOptions:[s],slots:o,attrs:a,emit:l,render:u,renderCache:c,props:d,data:p,setupState:m,ctx:h,inheritAttrs:f}=e,b=To(e);let y,w;try{if(r.shapeFlag&4){const _=i||n,x=_;y=Dt(u.call(x,_,c,d,m,p,h)),w=a}else{const _=t;y=Dt(_.length>1?_(d,{attrs:a,slots:o,emit:l}):_(d,null)),w=t.props?a:xS(a)}}catch(_){co.length=0,Ri(_,e,1),y=Be(Ge)}let v=y;if(w&&f!==!1){const _=Object.keys(w),{shapeFlag:x}=v;_.length&&x&7&&(s&&_.some(Gv)&&(w=SS(w,s)),v=Kr(v,w,!1,!0))}return r.dirs&&(v=Kr(v,null,!1,!0),v.dirs=v.dirs?v.dirs.concat(r.dirs):r.dirs),r.transition&&_n(v,r.transition),y=v,To(b),y}function ES(e,t=!0){let r;for(let n=0;n<e.length;n++){const i=e[n];if(wn(i)){if(i.type!==Ge||i.children==="v-if"){if(r)return;r=i}}else return}return r}const xS=e=>{let t;for(const r in e)(r==="class"||r==="style"||ac(r))&&((t||(t={}))[r]=e[r]);return t},SS=(e,t)=>{const r={};for(const n in e)(!Gv(n)||!(n.slice(9)in t))&&(r[n]=e[n]);return r};function CS(e,t,r){const{props:n,children:i,component:s}=e,{props:o,children:a,patchFlag:l}=t,u=s.emitsOptions;if(t.dirs||t.transition)return!0;if(r&&l>=0){if(l&1024)return!0;if(l&16)return n?lm(n,o,u):!!o;if(l&8){const c=t.dynamicProps;for(let d=0;d<c.length;d++){const p=c[d];if(o[p]!==n[p]&&!yc(u,p))return!0}}}else return(i||a)&&(!a||!a.$stable)?!0:n===o?!1:n?o?lm(n,o,u):!0:!!o;return!1}function lm(e,t,r){const n=Object.keys(t);if(n.length!==Object.keys(e).length)return!0;for(let i=0;i<n.length;i++){const s=n[i];if(t[s]!==e[s]&&!yc(r,s))return!0}return!1}function _c({vnode:e,parent:t},r){for(;t;){const n=t.subTree;if(n.suspense&&n.suspense.activeBranch===e&&(n.el=e.el),n===e)(e=t.vnode).el=r,t=t.parent;else break}}const Iy={},Fy=()=>Object.create(Iy),Ly=e=>Object.getPrototypeOf(e)===Iy;function AS(e,t,r,n=!1){const i={},s=Fy();e.propsDefaults=Object.create(null),Ry(e,t,i,s);for(const o in e.propsOptions[0])o in i||(i[o]=void 0);r?e.props=n?i:Uv(i):e.type.props?e.props=i:e.props=s,e.attrs=s}function TS(e,t,r,n){const{props:i,attrs:s,vnode:{patchFlag:o}}=e,a=ye(i),[l]=e.propsOptions;let u=!1;if((n||o>0)&&!(o&16)){if(o&8){const c=e.vnode.dynamicProps;for(let d=0;d<c.length;d++){let p=c[d];if(yc(e.emitsOptions,p))continue;const m=t[p];if(l)if(Le(s,p))m!==s[p]&&(s[p]=m,u=!0);else{const h=tr(p);i[h]=th(l,a,h,m,e,!1)}else m!==s[p]&&(s[p]=m,u=!0)}}}else{Ry(e,t,i,s)&&(u=!0);let c;for(const d in a)(!t||!Le(t,d)&&((c=xs(d))===d||!Le(t,c)))&&(l?r&&(r[d]!==void 0||r[c]!==void 0)&&(i[d]=th(l,a,d,void 0,e,!0)):delete i[d]);if(s!==a)for(const d in s)(!t||!Le(t,d))&&(delete s[d],u=!0)}u&&an(e.attrs,"set","")}function Ry(e,t,r,n){const[i,s]=e.propsOptions;let o=!1,a;if(t)for(let l in t){if(_i(l))continue;const u=t[l];let c;i&&Le(i,c=tr(l))?!s||!s.includes(c)?r[c]=u:(a||(a={}))[c]=u:yc(e.emitsOptions,l)||(!(l in n)||u!==n[l])&&(n[l]=u,o=!0)}if(s){const l=ye(r),u=a||Ee;for(let c=0;c<s.length;c++){const d=s[c];r[d]=th(i,l,d,u[d],e,!Le(u,d))}}return o}function th(e,t,r,n,i,s){const o=e[r];if(o!=null){const a=Le(o,"default");if(a&&n===void 0){const l=o.default;if(o.type!==Function&&!o.skipFactory&&ce(l)){const{propsDefaults:u}=i;if(r in u)n=u[r];else{const c=Oi(i);n=u[r]=l.call(null,t),c()}}else n=l;i.ce&&i.ce._setProp(r,n)}o[0]&&(s&&!a?n=!1:o[1]&&(n===""||n===xs(r))&&(n=!0))}return n}const kS=new WeakMap;function My(e,t,r=!1){const n=r?kS:t.propsCache,i=n.get(e);if(i)return i;const s=e.props,o={},a=[];let l=!1;if(!ce(e)){const c=d=>{l=!0;const[p,m]=My(d,t,!0);pt(o,p),m&&a.push(...m)};!r&&t.mixins.length&&t.mixins.forEach(c),e.extends&&c(e.extends),e.mixins&&e.mixins.forEach(c)}if(!s&&!l)return ot(e)&&n.set(e,Zi),Zi;if(de(s))for(let c=0;c<s.length;c++){const d=tr(s[c]);cm(d)&&(o[d]=Ee)}else if(s)for(const c in s){const d=tr(c);if(cm(d)){const p=s[c],m=o[d]=de(p)||ce(p)?{type:p}:pt({},p),h=m.type;let f=!1,b=!0;if(de(h))for(let y=0;y<h.length;++y){const w=h[y],v=ce(w)&&w.name;if(v==="Boolean"){f=!0;break}else v==="String"&&(b=!1)}else f=ce(h)&&h.name==="Boolean";m[0]=f,m[1]=b,(f||Le(m,"default"))&&a.push(d)}}const u=[o,a];return ot(e)&&n.set(e,u),u}function cm(e){return e[0]!=="$"&&!_i(e)}const _f=e=>e==="_"||e==="_ctx"||e==="$stable",wf=e=>de(e)?e.map(Dt):[Dt(e)],OS=(e,t,r)=>{if(t._n)return t;const n=df((...i)=>wf(t(...i)),r);return n._c=!1,n},Dy=(e,t,r)=>{const n=e._ctx;for(const i in e){if(_f(i))continue;const s=e[i];if(ce(s))t[i]=OS(i,s,n);else if(s!=null){const o=wf(s);t[i]=()=>o}}},$y=(e,t)=>{const r=wf(t);e.slots.default=()=>r},Vy=(e,t,r)=>{for(const n in t)(r||!_f(n))&&(e[n]=t[n])},NS=(e,t,r)=>{const n=e.slots=Fy();if(e.vnode.shapeFlag&32){const i=t._;i?(Vy(n,t,r),r&&Q1(n,"_",i,!0)):Dy(t,n)}else t&&$y(e,t)},PS=(e,t,r)=>{const{vnode:n,slots:i}=e;let s=!0,o=Ee;if(n.shapeFlag&32){const a=t._;a?r&&a===1?s=!1:Vy(i,t,r):(s=!t.$stable,Dy(t,i)),o=t}else t&&($y(e,t),o={default:1});if(s)for(const a in i)!_f(a)&&o[a]==null&&delete i[a]},Je=Ky;function By(e){return zy(e)}function Uy(e){return zy(e,Nx)}function zy(e,t){const r=dc();r.__VUE__=!0;const{insert:n,remove:i,patchProp:s,createElement:o,createText:a,createComment:l,setText:u,setElementText:c,parentNode:d,nextSibling:p,setScopeId:m=Hr,insertStaticContent:h}=e,f=(E,F,U,q=null,H=null,j=null,J=void 0,X=null,G=!!F.dynamicChildren)=>{if(E===F)return;E&&!xr(E,F)&&(q=qt(E),qe(E,H,j,!0),E=null),F.patchFlag===-2&&(G=!1,F.dynamicChildren=null);const{type:K,ref:ne,shapeFlag:Q}=F;switch(K){case Kn:b(E,F,U,q);break;case Ge:y(E,F,U,q);break;case xi:E==null&&w(F,U,q,J);break;case it:C(E,F,U,q,H,j,J,X,G);break;default:Q&1?x(E,F,U,q,H,j,J,X,G):Q&6?g(E,F,U,q,H,j,J,X,G):(Q&64||Q&128)&&K.process(E,F,U,q,H,j,J,X,G,he)}ne!=null&&H?ts(ne,E&&E.ref,j,F||E,!F):ne==null&&E&&E.ref!=null&&ts(E.ref,null,j,E,!0)},b=(E,F,U,q)=>{if(E==null)n(F.el=a(F.children),U,q);else{const H=F.el=E.el;F.children!==E.children&&u(H,F.children)}},y=(E,F,U,q)=>{E==null?n(F.el=l(F.children||""),U,q):F.el=E.el},w=(E,F,U,q)=>{[E.el,E.anchor]=h(E.children,F,U,q,E.el,E.anchor)},v=({el:E,anchor:F},U,q)=>{let H;for(;E&&E!==F;)H=p(E),n(E,U,q),E=H;n(F,U,q)},_=({el:E,anchor:F})=>{let U;for(;E&&E!==F;)U=p(E),i(E),E=U;i(F)},x=(E,F,U,q,H,j,J,X,G)=>{if(F.type==="svg"?J="svg":F.type==="math"&&(J="mathml"),E==null)k(F,U,q,H,j,J,X,G);else{const K=E.el&&E.el._isVueCE?E.el:null;try{K&&K._beginPatch(),A(E,F,H,j,J,X,G)}finally{K&&K._endPatch()}}},k=(E,F,U,q,H,j,J,X)=>{let G,K;const{props:ne,shapeFlag:Q,transition:re,dirs:ae}=E;if(G=E.el=o(E.type,j,ne&&ne.is,ne),Q&8?c(G,E.children):Q&16&&N(E.children,G,null,q,H,mu(E,j),J,X),ae&&Br(E,null,q,"created"),S(G,E,E.scopeId,J,q),ne){for(const Oe in ne)Oe!=="value"&&!_i(Oe)&&s(G,Oe,null,ne[Oe],j,q);"value"in ne&&s(G,"value",null,ne.value,j),(K=ne.onVnodeBeforeMount)&&Mt(K,q,E)}ae&&Br(E,null,q,"beforeMount");const pe=Hy(H,re);pe&&re.beforeEnter(G),n(G,F,U),((K=ne&&ne.onVnodeMounted)||pe||ae)&&Je(()=>{K&&Mt(K,q,E),pe&&re.enter(G),ae&&Br(E,null,q,"mounted")},H)},S=(E,F,U,q,H)=>{if(U&&m(E,U),q)for(let j=0;j<q.length;j++)m(E,q[j]);if(H){let j=H.subTree;if(F===j||vl(j.type)&&(j.ssContent===F||j.ssFallback===F)){const J=H.vnode;S(E,J,J.scopeId,J.slotScopeIds,H.parent)}}},N=(E,F,U,q,H,j,J,X,G=0)=>{for(let K=G;K<E.length;K++){const ne=E[K]=X?Bn(E[K]):Dt(E[K]);f(null,ne,F,U,q,H,j,J,X)}},A=(E,F,U,q,H,j,J)=>{const X=F.el=E.el;let{patchFlag:G,dynamicChildren:K,dirs:ne}=F;G|=E.patchFlag&16;const Q=E.props||Ee,re=F.props||Ee;let ae;if(U&&li(U,!1),(ae=re.onVnodeBeforeUpdate)&&Mt(ae,U,F,E),ne&&Br(F,E,U,"beforeUpdate"),U&&li(U,!0),(Q.innerHTML&&re.innerHTML==null||Q.textContent&&re.textContent==null)&&c(X,""),K?T(E.dynamicChildren,K,X,U,q,mu(F,H),j):J||P(E,F,X,null,U,q,mu(F,H),j,!1),G>0){if(G&16)I(X,Q,re,U,H);else if(G&2&&Q.class!==re.class&&s(X,"class",null,re.class,H),G&4&&s(X,"style",Q.style,re.style,H),G&8){const pe=F.dynamicProps;for(let Oe=0;Oe<pe.length;Oe++){const ge=pe[Oe],ct=Q[ge],tt=re[ge];(tt!==ct||ge==="value")&&s(X,ge,ct,tt,H,U)}}G&1&&E.children!==F.children&&c(X,F.children)}else!J&&K==null&&I(X,Q,re,U,H);((ae=re.onVnodeUpdated)||ne)&&Je(()=>{ae&&Mt(ae,U,F,E),ne&&Br(F,E,U,"updated")},q)},T=(E,F,U,q,H,j,J)=>{for(let X=0;X<F.length;X++){const G=E[X],K=F[X],ne=G.el&&(G.type===it||!xr(G,K)||G.shapeFlag&198)?d(G.el):U;f(G,K,ne,null,q,H,j,J,!0)}},I=(E,F,U,q,H)=>{if(F!==U){if(F!==Ee)for(const j in F)!_i(j)&&!(j in U)&&s(E,j,F[j],null,H,q);for(const j in U){if(_i(j))continue;const J=U[j],X=F[j];J!==X&&j!=="value"&&s(E,j,X,J,H,q)}"value"in U&&s(E,"value",F.value,U.value,H)}},C=(E,F,U,q,H,j,J,X,G)=>{const K=F.el=E?E.el:a(""),ne=F.anchor=E?E.anchor:a("");let{patchFlag:Q,dynamicChildren:re,slotScopeIds:ae}=F;ae&&(X=X?X.concat(ae):ae),E==null?(n(K,U,q),n(ne,U,q),N(F.children||[],U,ne,H,j,J,X,G)):Q>0&&Q&64&&re&&E.dynamicChildren&&E.dynamicChildren.length===re.length?(T(E.dynamicChildren,re,U,H,j,J,X),(F.key!=null||H&&F===H.subTree)&&Ef(E,F,!0)):P(E,F,U,ne,H,j,J,X,G)},g=(E,F,U,q,H,j,J,X,G)=>{F.slotScopeIds=X,E==null?F.shapeFlag&512?H.ctx.activate(F,U,q,J,G):L(F,U,q,H,j,J,G):B(E,F,G)},L=(E,F,U,q,H,j,J)=>{const X=E.component=Zy(E,q,H);if(Yo(E)&&(X.ctx.renderer=he),t_(X,!1,J),X.asyncDep){if(H&&H.registerDep(X,M,J),!E.el){const G=X.subTree=Be(Ge);y(null,G,F,U),E.placeholder=G.el}}else M(X,E,F,U,H,j,J)},B=(E,F,U)=>{const q=F.component=E.component;if(CS(E,F,U))if(q.asyncDep&&!q.asyncResolved){z(q,F,U);return}else q.next=F,q.update();else F.el=E.el,q.vnode=F},M=(E,F,U,q,H,j,J)=>{const X=()=>{if(E.isMounted){let{next:Q,bu:re,u:ae,parent:pe,vnode:Oe}=E;{const Ot=qy(E);if(Ot){Q&&(Q.el=Oe.el,z(E,Q,J)),Ot.asyncDep.then(()=>{E.isUnmounted||X()});return}}let ge=Q,ct;li(E,!1),Q?(Q.el=Oe.el,z(E,Q,J)):Q=Oe,re&&so(re),(ct=Q.props&&Q.props.onVnodeBeforeUpdate)&&Mt(ct,pe,Q,Oe),li(E,!0);const tt=Wa(E),jt=E.subTree;E.subTree=tt,f(jt,tt,d(jt.el),qt(jt),E,H,j),Q.el=tt.el,ge===null&&_c(E,tt.el),ae&&Je(ae,H),(ct=Q.props&&Q.props.onVnodeUpdated)&&Je(()=>Mt(ct,pe,Q,Oe),H)}else{let Q;const{el:re,props:ae}=F,{bm:pe,m:Oe,parent:ge,root:ct,type:tt}=E,jt=fn(F);if(li(E,!1),pe&&so(pe),!jt&&(Q=ae&&ae.onVnodeBeforeMount)&&Mt(Q,ge,F),li(E,!0),re&&Se){const Ot=()=>{E.subTree=Wa(E),Se(re,E.subTree,E,H,null)};jt&&tt.__asyncHydrate?tt.__asyncHydrate(re,E,Ot):Ot()}else{ct.ce&&ct.ce._def.shadowRoot!==!1&&ct.ce._injectChildStyle(tt);const Ot=E.subTree=Wa(E);f(null,Ot,U,q,E,H,j),F.el=Ot.el}if(Oe&&Je(Oe,H),!jt&&(Q=ae&&ae.onVnodeMounted)){const Ot=F;Je(()=>Mt(Q,ge,Ot),H)}(F.shapeFlag&256||ge&&fn(ge.vnode)&&ge.vnode.shapeFlag&256)&&E.a&&Je(E.a,H),E.isMounted=!0,F=U=q=null}};E.scope.on();const G=E.effect=new Eo(X);E.scope.off();const K=E.update=G.run.bind(G),ne=E.job=G.runIfDirty.bind(G);ne.i=E,ne.id=E.uid,G.scheduler=()=>uf(ne),li(E,!0),K()},z=(E,F,U)=>{F.component=E;const q=E.vnode.props;E.vnode=F,E.next=null,TS(E,F.props,q,U),PS(E,F.children,U),vn(),Xp(E),yn()},P=(E,F,U,q,H,j,J,X,G=!1)=>{const K=E&&E.children,ne=E?E.shapeFlag:0,Q=F.children,{patchFlag:re,shapeFlag:ae}=F;if(re>0){if(re&128){be(K,Q,U,q,H,j,J,X,G);return}else if(re&256){Z(K,Q,U,q,H,j,J,X,G);return}}ae&8?(ne&16&&lt(K,H,j),Q!==K&&c(U,Q)):ne&16?ae&16?be(K,Q,U,q,H,j,J,X,G):lt(K,H,j,!0):(ne&8&&c(U,""),ae&16&&N(Q,U,q,H,j,J,X,G))},Z=(E,F,U,q,H,j,J,X,G)=>{E=E||Zi,F=F||Zi;const K=E.length,ne=F.length,Q=Math.min(K,ne);let re;for(re=0;re<Q;re++){const ae=F[re]=G?Bn(F[re]):Dt(F[re]);f(E[re],ae,U,null,H,j,J,X,G)}K>ne?lt(E,H,j,!0,!1,Q):N(F,U,q,H,j,J,X,G,Q)},be=(E,F,U,q,H,j,J,X,G)=>{let K=0;const ne=F.length;let Q=E.length-1,re=ne-1;for(;K<=Q&&K<=re;){const ae=E[K],pe=F[K]=G?Bn(F[K]):Dt(F[K]);if(xr(ae,pe))f(ae,pe,U,null,H,j,J,X,G);else break;K++}for(;K<=Q&&K<=re;){const ae=E[Q],pe=F[re]=G?Bn(F[re]):Dt(F[re]);if(xr(ae,pe))f(ae,pe,U,null,H,j,J,X,G);else break;Q--,re--}if(K>Q){if(K<=re){const ae=re+1,pe=ae<ne?F[ae].el:q;for(;K<=re;)f(null,F[K]=G?Bn(F[K]):Dt(F[K]),U,pe,H,j,J,X,G),K++}}else if(K>re)for(;K<=Q;)qe(E[K],H,j,!0),K++;else{const ae=K,pe=K,Oe=new Map;for(K=pe;K<=re;K++){const R=F[K]=G?Bn(F[K]):Dt(F[K]);R.key!=null&&Oe.set(R.key,K)}let ge,ct=0;const tt=re-pe+1;let jt=!1,Ot=0;const Qr=new Array(tt);for(K=0;K<tt;K++)Qr[K]=0;for(K=ae;K<=Q;K++){const R=E[K];if(ct>=tt){qe(R,H,j,!0);continue}let D;if(R.key!=null)D=Oe.get(R.key);else for(ge=pe;ge<=re;ge++)if(Qr[ge-pe]===0&&xr(R,F[ge])){D=ge;break}D===void 0?qe(R,H,j,!0):(Qr[D-pe]=K+1,D>=Ot?Ot=D:jt=!0,f(R,F[D],U,null,H,j,J,X,G),ct++)}const oi=jt?IS(Qr):Zi;for(ge=oi.length-1,K=tt-1;K>=0;K--){const R=pe+K,D=F[R],ve=F[R+1],Ae=R+1<ne?ve.el||jy(ve):q;Qr[K]===0?f(null,D,U,Ae,H,j,J,X,G):jt&&(ge<0||K!==oi[ge]?le(D,U,Ae,2):ge--)}}},le=(E,F,U,q,H=null)=>{const{el:j,type:J,transition:X,children:G,shapeFlag:K}=E;if(K&6){le(E.component.subTree,F,U,q);return}if(K&128){E.suspense.move(F,U,q);return}if(K&64){J.move(E,F,U,he);return}if(J===it){n(j,F,U);for(let Q=0;Q<G.length;Q++)le(G[Q],F,U,q);n(E.anchor,F,U);return}if(J===xi){v(E,F,U);return}if(q!==2&&K&1&&X)if(q===0)X.beforeEnter(j),n(j,F,U),Je(()=>X.enter(j),H);else{const{leave:Q,delayLeave:re,afterLeave:ae}=X,pe=()=>{E.ctx.isUnmounted?i(j):n(j,F,U)},Oe=()=>{j._isLeaving&&j[sn](!0),Q(j,()=>{pe(),ae&&ae()})};re?re(j,pe,Oe):Oe()}else n(j,F,U)},qe=(E,F,U,q=!1,H=!1)=>{const{type:j,props:J,ref:X,children:G,dynamicChildren:K,shapeFlag:ne,patchFlag:Q,dirs:re,cacheIndex:ae}=E;if(Q===-2&&(H=!1),X!=null&&(vn(),ts(X,null,U,E,!0),yn()),ae!=null&&(F.renderCache[ae]=void 0),ne&256){F.ctx.deactivate(E);return}const pe=ne&1&&re,Oe=!fn(E);let ge;if(Oe&&(ge=J&&J.onVnodeBeforeUnmount)&&Mt(ge,F,E),ne&6)yr(E.component,U,q);else{if(ne&128){E.suspense.unmount(U,q);return}pe&&Br(E,null,F,"beforeUnmount"),ne&64?E.type.remove(E,F,U,he,q):K&&!K.hasOnce&&(j!==it||Q>0&&Q&64)?lt(K,F,U,!1,!0):(j===it&&Q&384||!H&&ne&16)&&lt(G,F,U),q&&vr(E)}(Oe&&(ge=J&&J.onVnodeUnmounted)||pe)&&Je(()=>{ge&&Mt(ge,F,E),pe&&Br(E,null,F,"unmounted")},U)},vr=E=>{const{type:F,el:U,anchor:q,transition:H}=E;if(F===it){Mr(U,q);return}if(F===xi){_(E);return}const j=()=>{i(U),H&&!H.persisted&&H.afterLeave&&H.afterLeave()};if(E.shapeFlag&1&&H&&!H.persisted){const{leave:J,delayLeave:X}=H,G=()=>J(U,j);X?X(E.el,j,G):G()}else j()},Mr=(E,F)=>{let U;for(;E!==F;)U=p(E),i(E),E=U;i(F)},yr=(E,F,U)=>{const{bum:q,scope:H,job:j,subTree:J,um:X,m:G,a:K}=E;bl(G),bl(K),q&&so(q),H.stop(),j&&(j.flags|=8,qe(J,E,F,U)),X&&Je(X,F),Je(()=>{E.isUnmounted=!0},F)},lt=(E,F,U,q=!1,H=!1,j=0)=>{for(let J=j;J<E.length;J++)qe(E[J],F,U,q,H)},qt=E=>{if(E.shapeFlag&6)return qt(E.component.subTree);if(E.shapeFlag&128)return E.suspense.next();const F=p(E.anchor||E.el),U=F&&F[uy];return U?p(U):F};let lr=!1;const Ye=(E,F,U)=>{let q;E==null?F._vnode&&(qe(F._vnode,null,null,!0),q=F._vnode.component):f(F._vnode||null,E,F,null,null,null,U),F._vnode=E,lr||(lr=!0,Xp(q),pl(),lr=!1)},he={p:f,um:qe,m:le,r:vr,mt:L,mc:N,pc:P,pbc:T,n:qt,o:e};let Re,Se;return t&&([Re,Se]=t(he)),{render:Ye,hydrate:Re,createApp:vS(Ye,Re)}}function mu({type:e,props:t},r){return r==="svg"&&e==="foreignObject"||r==="mathml"&&e==="annotation-xml"&&t&&t.encoding&&t.encoding.includes("html")?void 0:r}function li({effect:e,job:t},r){r?(e.flags|=32,t.flags|=4):(e.flags&=-33,t.flags&=-5)}function Hy(e,t){return(!e||e&&!e.pendingBranch)&&t&&!t.persisted}function Ef(e,t,r=!1){const n=e.children,i=t.children;if(de(n)&&de(i))for(let s=0;s<n.length;s++){const o=n[s];let a=i[s];a.shapeFlag&1&&!a.dynamicChildren&&((a.patchFlag<=0||a.patchFlag===32)&&(a=i[s]=Bn(i[s]),a.el=o.el),!r&&a.patchFlag!==-2&&Ef(o,a)),a.type===Kn&&(a.patchFlag!==-1?a.el=o.el:a.__elIndex=s+(e.type===it?1:0)),a.type===Ge&&!a.el&&(a.el=o.el)}}function IS(e){const t=e.slice(),r=[0];let n,i,s,o,a;const l=e.length;for(n=0;n<l;n++){const u=e[n];if(u!==0){if(i=r[r.length-1],e[i]<u){t[n]=i,r.push(n);continue}for(s=0,o=r.length-1;s<o;)a=s+o>>1,e[r[a]]<u?s=a+1:o=a;u<e[r[s]]&&(s>0&&(t[n]=r[s-1]),r[s]=n)}}for(s=r.length,o=r[s-1];s-- >0;)r[s]=o,o=t[o];return r}function qy(e){const t=e.subTree.component;if(t)return t.asyncDep&&!t.asyncResolved?t:qy(t)}function bl(e){if(e)for(let t=0;t<e.length;t++)e[t].flags|=8}function jy(e){if(e.placeholder)return e.placeholder;const t=e.component;return t?jy(t.subTree):null}const vl=e=>e.__isSuspense;let rh=0;const FS={name:"Suspense",__isSuspense:!0,process(e,t,r,n,i,s,o,a,l,u){if(e==null)RS(t,r,n,i,s,o,a,l,u);else{if(s&&s.deps>0&&!e.suspense.isInFallback){t.suspense=e.suspense,t.suspense.vnode=t,t.el=e.el;return}MS(e,t,r,n,i,o,a,l,u)}},hydrate:DS,normalize:$S},LS=FS;function Oo(e,t){const r=e.props&&e.props[t];ce(r)&&r()}function RS(e,t,r,n,i,s,o,a,l){const{p:u,o:{createElement:c}}=l,d=c("div"),p=e.suspense=Wy(e,i,n,t,d,r,s,o,a,l);u(null,p.pendingBranch=e.ssContent,d,null,n,p,s,o),p.deps>0?(Oo(e,"onPending"),Oo(e,"onFallback"),u(null,e.ssFallback,t,r,n,null,s,o),rs(p,e.ssFallback)):p.resolve(!1,!0)}function MS(e,t,r,n,i,s,o,a,{p:l,um:u,o:{createElement:c}}){const d=t.suspense=e.suspense;d.vnode=t,t.el=e.el;const p=t.ssContent,m=t.ssFallback,{activeBranch:h,pendingBranch:f,isInFallback:b,isHydrating:y}=d;if(f)d.pendingBranch=p,xr(f,p)?(l(f,p,d.hiddenContainer,null,i,d,s,o,a),d.deps<=0?d.resolve():b&&(y||(l(h,m,r,n,i,null,s,o,a),rs(d,m)))):(d.pendingId=rh++,y?(d.isHydrating=!1,d.activeBranch=f):u(f,i,d),d.deps=0,d.effects.length=0,d.hiddenContainer=c("div"),b?(l(null,p,d.hiddenContainer,null,i,d,s,o,a),d.deps<=0?d.resolve():(l(h,m,r,n,i,null,s,o,a),rs(d,m))):h&&xr(h,p)?(l(h,p,r,n,i,d,s,o,a),d.resolve(!0)):(l(null,p,d.hiddenContainer,null,i,d,s,o,a),d.deps<=0&&d.resolve()));else if(h&&xr(h,p))l(h,p,r,n,i,d,s,o,a),rs(d,p);else if(Oo(t,"onPending"),d.pendingBranch=p,p.shapeFlag&512?d.pendingId=p.component.suspenseId:d.pendingId=rh++,l(null,p,d.hiddenContainer,null,i,d,s,o,a),d.deps<=0)d.resolve();else{const{timeout:w,pendingId:v}=d;w>0?setTimeout(()=>{d.pendingId===v&&d.fallback(m)},w):w===0&&d.fallback(m)}}function Wy(e,t,r,n,i,s,o,a,l,u,c=!1){const{p:d,m:p,um:m,n:h,o:{parentNode:f,remove:b}}=u;let y;const w=VS(e);w&&t&&t.pendingBranch&&(y=t.pendingId,t.deps++);const v=e.props?ex(e.props.timeout):void 0,_=s,x={vnode:e,parent:t,parentComponent:r,namespace:o,container:n,hiddenContainer:i,deps:0,pendingId:rh++,timeout:typeof v=="number"?v:-1,activeBranch:null,pendingBranch:null,isInFallback:!c,isHydrating:c,isUnmounted:!1,effects:[],resolve(k=!1,S=!1){const{vnode:N,activeBranch:A,pendingBranch:T,pendingId:I,effects:C,parentComponent:g,container:L,isInFallback:B}=x;let M=!1;x.isHydrating?x.isHydrating=!1:k||(M=A&&T.transition&&T.transition.mode==="out-in",M&&(A.transition.afterLeave=()=>{I===x.pendingId&&(p(T,L,s===_?h(A):s,0),Co(C),B&&N.ssFallback&&(N.ssFallback.el=null))}),A&&(f(A.el)===L&&(s=h(A)),m(A,g,x,!0),!M&&B&&N.ssFallback&&Je(()=>N.ssFallback.el=null,x)),M||p(T,L,s,0)),rs(x,T),x.pendingBranch=null,x.isInFallback=!1;let z=x.parent,P=!1;for(;z;){if(z.pendingBranch){z.effects.push(...C),P=!0;break}z=z.parent}!P&&!M&&Co(C),x.effects=[],w&&t&&t.pendingBranch&&y===t.pendingId&&(t.deps--,t.deps===0&&!S&&t.resolve()),Oo(N,"onResolve")},fallback(k){if(!x.pendingBranch)return;const{vnode:S,activeBranch:N,parentComponent:A,container:T,namespace:I}=x;Oo(S,"onFallback");const C=h(N),g=()=>{x.isInFallback&&(d(null,k,T,C,A,null,I,a,l),rs(x,k))},L=k.transition&&k.transition.mode==="out-in";L&&(N.transition.afterLeave=g),x.isInFallback=!0,m(N,A,null,!0),L||g()},move(k,S,N){x.activeBranch&&p(x.activeBranch,k,S,N),x.container=k},next(){return x.activeBranch&&h(x.activeBranch)},registerDep(k,S,N){const A=!!x.pendingBranch;A&&x.deps++;const T=k.vnode.el;k.asyncDep.catch(I=>{Ri(I,k,0)}).then(I=>{if(k.isUnmounted||x.isUnmounted||x.pendingId!==k.suspenseId)return;k.asyncResolved=!0;const{vnode:C}=k;sh(k,I,!1),T&&(C.el=T);const g=!T&&k.subTree.el;S(k,C,f(T||k.subTree.el),T?null:h(k.subTree),x,o,N),g&&(C.placeholder=null,b(g)),_c(k,C.el),A&&--x.deps===0&&x.resolve()})},unmount(k,S){x.isUnmounted=!0,x.activeBranch&&m(x.activeBranch,r,k,S),x.pendingBranch&&m(x.pendingBranch,r,k,S)}};return x}function DS(e,t,r,n,i,s,o,a,l){const u=t.suspense=Wy(t,n,r,e.parentNode,document.createElement("div"),null,i,s,o,a,!0),c=l(e,u.pendingBranch=t.ssContent,r,u,s,o);return u.deps===0&&u.resolve(!1,!0),c}function $S(e){const{shapeFlag:t,children:r}=e,n=t&32;e.ssContent=um(n?r.default:r),e.ssFallback=n?um(r.fallback):Be(Ge)}function um(e){let t;if(ce(e)){const r=ki&&e._c;r&&(e._d=!1,No()),e=e(),r&&(e._d=!0,t=St,Gy())}return de(e)&&(e=ES(e)),e=Dt(e),t&&!e.dynamicChildren&&(e.dynamicChildren=t.filter(r=>r!==e)),e}function Ky(e,t){t&&t.pendingBranch?de(e)?t.effects.push(...e):t.effects.push(e):Co(e)}function rs(e,t){e.activeBranch=t;const{vnode:r,parentComponent:n}=e;let i=t.el;for(;!i&&t.component;)t=t.component.subTree,i=t.el;r.el=i,n&&n.subTree===r&&(n.vnode.el=i,_c(n,i))}function VS(e){const t=e.props&&e.props.suspensible;return t!=null&&t!==!1}const it=Symbol.for("v-fgt"),Kn=Symbol.for("v-txt"),Ge=Symbol.for("v-cmt"),xi=Symbol.for("v-stc"),co=[];let St=null;function No(e=!1){co.push(St=e?null:[])}function Gy(){co.pop(),St=co[co.length-1]||null}let ki=1;function Po(e,t=!1){ki+=e,e<0&&St&&t&&(St.hasOnce=!0)}function Yy(e){return e.dynamicChildren=ki>0?St||Zi:null,Gy(),ki>0&&St&&St.push(e),e}function BS(e,t,r,n,i,s){return Yy(xf(e,t,r,n,i,s,!0))}function yl(e,t,r,n,i){return Yy(Be(e,t,r,n,i,!0))}function wn(e){return e?e.__v_isVNode===!0:!1}function xr(e,t){return e.type===t.type&&e.key===t.key}function US(e){}const Xy=({key:e})=>e??null,Ka=({ref:e,ref_key:t,ref_for:r})=>(typeof e=="number"&&(e=""+e),e!=null?et(e)||Ze(e)||ce(e)?{i:vt,r:e,k:t,f:!!r}:e:null);function xf(e,t=null,r=null,n=0,i=null,s=e===it?0:1,o=!1,a=!1){const l={__v_isVNode:!0,__v_skip:!0,type:e,props:t,key:t&&Xy(t),ref:t&&Ka(t),scopeId:fc,slotScopeIds:null,children:r,component:null,suspense:null,ssContent:null,ssFallback:null,dirs:null,transition:null,el:null,anchor:null,target:null,targetStart:null,targetAnchor:null,staticCount:0,shapeFlag:s,patchFlag:n,dynamicProps:i,dynamicChildren:null,appContext:null,ctx:vt};return a?(Cf(l,r),s&128&&e.normalize(l)):r&&(l.shapeFlag|=et(r)?8:16),ki>0&&!o&&St&&(l.patchFlag>0||s&6)&&l.patchFlag!==32&&St.push(l),l}const Be=zS;function zS(e,t=null,r=null,n=0,i=null,s=!1){if((!e||e===Ay)&&(e=Ge),wn(e)){const a=Kr(e,t,!0);return r&&Cf(a,r),ki>0&&!s&&St&&(a.shapeFlag&6?St[St.indexOf(e)]=a:St.push(a)),a.patchFlag=-2,a}if(XS(e)&&(e=e.__vccOpts),t){t=Jy(t);let{class:a,style:l}=t;a&&!et(a)&&(t.class=Ko(a)),ot(l)&&(qo(l)&&!de(l)&&(l=pt({},l)),t.style=Wo(l))}const o=et(e)?1:vl(e)?128:dy(e)?64:ot(e)?4:ce(e)?2:0;return xf(e,t,r,n,i,o,s,!0)}function Jy(e){return e?qo(e)||Ly(e)?pt({},e):e:null}function Kr(e,t,r=!1,n=!1){const{props:i,ref:s,patchFlag:o,children:a,transition:l}=e,u=t?Qy(i||{},t):i,c={__v_isVNode:!0,__v_skip:!0,type:e.type,props:u,key:u&&Xy(u),ref:t&&t.ref?r&&s?de(s)?s.concat(Ka(t)):[s,Ka(t)]:Ka(t):s,scopeId:e.scopeId,slotScopeIds:e.slotScopeIds,children:a,target:e.target,targetStart:e.targetStart,targetAnchor:e.targetAnchor,staticCount:e.staticCount,shapeFlag:e.shapeFlag,patchFlag:t&&e.type!==it?o===-1?16:o|16:o,dynamicProps:e.dynamicProps,dynamicChildren:e.dynamicChildren,appContext:e.appContext,dirs:e.dirs,transition:l,component:e.component,suspense:e.suspense,ssContent:e.ssContent&&Kr(e.ssContent),ssFallback:e.ssFallback&&Kr(e.ssFallback),placeholder:e.placeholder,el:e.el,anchor:e.anchor,ctx:e.ctx,ce:e.ce};return l&&n&&_n(c,l.clone(c)),c}function Sf(e=" ",t=0){return Be(Kn,null,e,t)}function HS(e,t){const r=Be(xi,null,e);return r.staticCount=t,r}function qS(e="",t=!1){return t?(No(),yl(Ge,null,e)):Be(Ge,null,e)}function Dt(e){return e==null||typeof e=="boolean"?Be(Ge):de(e)?Be(it,null,e.slice()):wn(e)?Bn(e):Be(Kn,null,String(e))}function Bn(e){return e.el===null&&e.patchFlag!==-1||e.memo?e:Kr(e)}function Cf(e,t){let r=0;const{shapeFlag:n}=e;if(t==null)t=null;else if(de(t))r=16;else if(typeof t=="object")if(n&65){const i=t.default;i&&(i._c&&(i._d=!1),Cf(e,i()),i._c&&(i._d=!0));return}else{r=32;const i=t._;!i&&!Ly(t)?t._ctx=vt:i===3&&vt&&(vt.slots._===1?t._=1:(t._=2,e.patchFlag|=1024))}else ce(t)?(t={default:t,_ctx:vt},r=32):(t=String(t),n&64?(r=16,t=[Sf(t)]):r=8);e.children=t,e.shapeFlag|=r}function Qy(...e){const t={};for(let r=0;r<e.length;r++){const n=e[r];for(const i in n)if(i==="class")t.class!==n.class&&(t.class=Ko([t.class,n.class]));else if(i==="style")t.style=Wo([t.style,n.style]);else if(ac(i)){const s=t[i],o=n[i];o&&s!==o&&!(de(s)&&s.includes(o))&&(t[i]=s?[].concat(s,o):o)}else i!==""&&(t[i]=n[i])}return t}function Mt(e,t,r,n=null){pr(e,t,7,[r,n])}const jS=Oy();let WS=0;function Zy(e,t,r){const n=e.type,i=(t?t.appContext:e.appContext)||jS,s={uid:WS++,vnode:e,type:n,parent:t,appContext:i,root:null,next:null,subTree:null,effect:null,update:null,job:null,scope:new tf(!0),render:null,proxy:null,exposed:null,exposeProxy:null,withProxy:null,provides:t?t.provides:Object.create(i.provides),ids:t?t.ids:["",0,0],accessCache:null,renderCache:[],components:null,directives:null,propsOptions:My(n,i),emitsOptions:Py(n,i),emit:null,emitted:null,propsDefaults:Ee,inheritAttrs:n.inheritAttrs,ctx:Ee,data:Ee,props:Ee,attrs:Ee,slots:Ee,refs:Ee,setupState:Ee,setupContext:null,suspense:r,suspenseId:r?r.pendingId:0,asyncDep:null,asyncResolved:!1,isMounted:!1,isUnmounted:!1,isDeactivated:!1,bc:null,c:null,bm:null,m:null,bu:null,u:null,um:null,bum:null,da:null,a:null,rtg:null,rtc:null,ec:null,sp:null};return s.ctx={_:s},s.root=t?t.root:s,s.emit=_S.bind(null,s),e.ce&&e.ce(s),s}let bt=null;const zt=()=>bt||vt;let _l,nh;{const e=dc(),t=(r,n)=>{let i;return(i=e[r])||(i=e[r]=[]),i.push(n),s=>{i.length>1?i.forEach(o=>o(s)):i[0](s)}};_l=t("__VUE_INSTANCE_SETTERS__",r=>bt=r),nh=t("__VUE_SSR_SETTERS__",r=>as=r)}const Oi=e=>{const t=bt;return _l(e),e.scope.on(),()=>{e.scope.off(),_l(t)}},ih=()=>{bt&&bt.scope.off(),_l(null)};function e_(e){return e.vnode.shapeFlag&4}let as=!1;function t_(e,t=!1,r=!1){t&&nh(t);const{props:n,children:i}=e.vnode,s=e_(e);AS(e,n,s,t),NS(e,i,r||t);const o=s?KS(e,t):void 0;return t&&nh(!1),o}function KS(e,t){const r=e.type;e.accessCache=Object.create(null),e.proxy=new Proxy(e.ctx,Qd);const{setup:n}=r;if(n){vn();const i=e.setupContext=n.length>1?i_(e):null,s=Oi(e),o=Ss(n,e,0,[e.props,i]),a=cf(o);if(yn(),s(),(a||e.sp)&&!fn(e)&&pf(e),a){if(o.then(ih,ih),t)return o.then(l=>{sh(e,l,t)}).catch(l=>{Ri(l,e,0)});e.asyncDep=o}else sh(e,o,t)}else n_(e,t)}function sh(e,t,r){ce(t)?e.type.__ssrInlineRender?e.ssrRender=t:e.render=t:ot(t)&&(e.setupState=af(t)),n_(e,r)}let wl,oh;function r_(e){wl=e,oh=t=>{t.render._rc&&(t.withProxy=new Proxy(t.ctx,Qx))}}const GS=()=>!wl;function n_(e,t,r){const n=e.type;if(!e.render){if(!t&&wl&&!n.render){const i=n.template||yf(e).template;if(i){const{isCustomElement:s,compilerOptions:o}=e.appContext.config,{delimiters:a,compilerOptions:l}=n,u=pt(pt({isCustomElement:s,delimiters:a},o),l);n.render=wl(i,u)}}e.render=n.render||Hr,oh&&oh(e)}{const i=Oi(e);vn();try{hS(e)}finally{yn(),i()}}}const YS={get(e,t){return xt(e,"get",""),e[t]}};function i_(e){const t=r=>{e.exposed=r||{}};return{attrs:new Proxy(e.attrs,YS),slots:e.slots,emit:e.emit,expose:t}}function Jo(e){return e.exposed?e.exposeProxy||(e.exposeProxy=new Proxy(af(dl(e.exposed)),{get(t,r){if(r in t)return t[r];if(r in lo)return lo[r](e)},has(t,r){return r in t||r in lo}})):e.proxy}function ah(e,t=!0){return ce(e)?e.displayName||e.name:e.name||t&&e.__name}function XS(e){return ce(e)&&"__vccOpts"in e}const Pt=(e,t)=>B1(e,t,as);function pn(e,t,r){try{Po(-1);const n=arguments.length;return n===2?ot(t)&&!de(t)?wn(t)?Be(e,null,[t]):Be(e,t):Be(e,null,t):(n>3?r=Array.prototype.slice.call(arguments,2):n===3&&wn(r)&&(r=[r]),Be(e,t,r))}finally{Po(1)}}function JS(){}function QS(e,t,r,n){const i=r[n];if(i&&s_(i,e))return i;const s=t();return s.memo=e.slice(),s.cacheIndex=n,r[n]=s}function s_(e,t){const r=e.memo;if(r.length!=t.length)return!1;for(let n=0;n<r.length;n++)if(hi(r[n],t[n]))return!1;return ki>0&&St&&St.push(e),!0}const o_="3.5.27",ZS=Hr,eC=hx,tC=Gi,rC=iy,nC={createComponentInstance:Zy,setupComponent:t_,renderComponentRoot:Wa,setCurrentRenderingInstance:To,isVNode:wn,normalizeVNode:Dt,getComponentPublicInstance:Jo,ensureValidVNode:vf,pushWarningContext:lx,popWarningContext:cx},iC=nC,sC=null,oC=null,aC=null;function lC(e){const t=Object.create(null);for(const r of e.split(","))t[r]=1;return r=>r in t}const gu={},cC=()=>{},uC=e=>e.charCodeAt(0)===111&&e.charCodeAt(1)===110&&(e.charCodeAt(2)>122||e.charCodeAt(2)<97),dC=e=>e.startsWith("onUpdate:"),Gn=Object.assign,hC=Object.prototype.hasOwnProperty,fC=(e,t)=>hC.call(e,t),rr=Array.isArray,Qo=e=>Af(e)==="[object Set]",dm=e=>Af(e)==="[object Date]",a_=e=>typeof e=="function",ls=e=>typeof e=="string",lh=e=>typeof e=="symbol",ch=e=>e!==null&&typeof e=="object",pC=Object.prototype.toString,Af=e=>pC.call(e),l_=e=>Af(e)==="[object Object]",Tf=e=>{const t=Object.create(null);return(r=>t[r]||(t[r]=e(r)))},mC=/-\w/g,Ga=Tf(e=>e.replace(mC,t=>t.slice(1).toUpperCase())),gC=/\B([A-Z])/g,zn=Tf(e=>e.replace(gC,"-$1").toLowerCase()),bC=Tf(e=>e.charAt(0).toUpperCase()+e.slice(1)),vC=(e,...t)=>{for(let r=0;r<e.length;r++)e[r](...t)},kf=e=>{const t=parseFloat(e);return isNaN(t)?e:t},uh=e=>{const t=ls(e)?Number(e):NaN;return isNaN(t)?e:t},yC="itemscope,allowfullscreen,formnovalidate,ismap,nomodule,novalidate,readonly",_C=lC(yC);function c_(e){return!!e||e===""}function wC(e,t){if(e.length!==t.length)return!1;let r=!0;for(let n=0;r&&n<e.length;n++)r=Qn(e[n],t[n]);return r}function Qn(e,t){if(e===t)return!0;let r=dm(e),n=dm(t);if(r||n)return r&&n?e.getTime()===t.getTime():!1;if(r=lh(e),n=lh(t),r||n)return e===t;if(r=rr(e),n=rr(t),r||n)return r&&n?wC(e,t):!1;if(r=ch(e),n=ch(t),r||n){if(!r||!n)return!1;const i=Object.keys(e).length,s=Object.keys(t).length;if(i!==s)return!1;for(const o in e){const a=e.hasOwnProperty(o),l=t.hasOwnProperty(o);if(a&&!l||!a&&l||!Qn(e[o],t[o]))return!1}}return String(e)===String(t)}function wc(e,t){return e.findIndex(r=>Qn(r,t))}function EC(e){return e==null?"initial":typeof e=="string"?e===""?" ":e:String(e)}let dh;const hm=typeof window<"u"&&window.trustedTypes;if(hm)try{dh=hm.createPolicy("vue",{createHTML:e=>e})}catch{}const u_=dh?e=>dh.createHTML(e):e=>e,xC="http://www.w3.org/2000/svg",SC="http://www.w3.org/1998/Math/MathML",nn=typeof document<"u"?document:null,fm=nn&&nn.createElement("template"),d_={insert:(e,t,r)=>{t.insertBefore(e,r||null)},remove:e=>{const t=e.parentNode;t&&t.removeChild(e)},createElement:(e,t,r,n)=>{const i=t==="svg"?nn.createElementNS(xC,e):t==="mathml"?nn.createElementNS(SC,e):r?nn.createElement(e,{is:r}):nn.createElement(e);return e==="select"&&n&&n.multiple!=null&&i.setAttribute("multiple",n.multiple),i},createText:e=>nn.createTextNode(e),createComment:e=>nn.createComment(e),setText:(e,t)=>{e.nodeValue=t},setElementText:(e,t)=>{e.textContent=t},parentNode:e=>e.parentNode,nextSibling:e=>e.nextSibling,querySelector:e=>nn.querySelector(e),setScopeId(e,t){e.setAttribute(t,"")},insertStaticContent(e,t,r,n,i,s){const o=r?r.previousSibling:t.lastChild;if(i&&(i===s||i.nextSibling))for(;t.insertBefore(i.cloneNode(!0),r),!(i===s||!(i=i.nextSibling)););else{fm.innerHTML=u_(n==="svg"?`<svg>${e}</svg>`:n==="mathml"?`<math>${e}</math>`:e);const a=fm.content;if(n==="svg"||n==="mathml"){const l=a.firstChild;for(;l.firstChild;)a.appendChild(l.firstChild);a.removeChild(l)}t.insertBefore(a,r)}return[o?o.nextSibling:t.firstChild,r?r.previousSibling:t.lastChild]}},kn="transition",Ds="animation",cs=Symbol("_vtc"),h_={name:String,type:String,css:{type:Boolean,default:!0},duration:[String,Number,Object],enterFromClass:String,enterActiveClass:String,enterToClass:String,appearFromClass:String,appearActiveClass:String,appearToClass:String,leaveFromClass:String,leaveActiveClass:String,leaveToClass:String},f_=Gn({},ff,h_),CC=e=>(e.displayName="Transition",e.props=f_,e),AC=CC((e,{slots:t})=>pn(gy,p_(e),t)),ci=(e,t=[])=>{rr(e)?e.forEach(r=>r(...t)):e&&e(...t)},pm=e=>e?rr(e)?e.some(t=>t.length>1):e.length>1:!1;function p_(e){const t={};for(const C in e)C in h_||(t[C]=e[C]);if(e.css===!1)return t;const{name:r="v",type:n,duration:i,enterFromClass:s=`${r}-enter-from`,enterActiveClass:o=`${r}-enter-active`,enterToClass:a=`${r}-enter-to`,appearFromClass:l=s,appearActiveClass:u=o,appearToClass:c=a,leaveFromClass:d=`${r}-leave-from`,leaveActiveClass:p=`${r}-leave-active`,leaveToClass:m=`${r}-leave-to`}=e,h=TC(i),f=h&&h[0],b=h&&h[1],{onBeforeEnter:y,onEnter:w,onEnterCancelled:v,onLeave:_,onLeaveCancelled:x,onBeforeAppear:k=y,onAppear:S=w,onAppearCancelled:N=v}=t,A=(C,g,L,B)=>{C._enterCancelled=B,Mn(C,g?c:a),Mn(C,g?u:o),L&&L()},T=(C,g)=>{C._isLeaving=!1,Mn(C,d),Mn(C,m),Mn(C,p),g&&g()},I=C=>(g,L)=>{const B=C?S:w,M=()=>A(g,C,L);ci(B,[g,M]),mm(()=>{Mn(g,C?l:s),Dr(g,C?c:a),pm(B)||gm(g,n,f,M)})};return Gn(t,{onBeforeEnter(C){ci(y,[C]),Dr(C,s),Dr(C,o)},onBeforeAppear(C){ci(k,[C]),Dr(C,l),Dr(C,u)},onEnter:I(!1),onAppear:I(!0),onLeave(C,g){C._isLeaving=!0;const L=()=>T(C,g);Dr(C,d),C._enterCancelled?(Dr(C,p),hh(C)):(hh(C),Dr(C,p)),mm(()=>{C._isLeaving&&(Mn(C,d),Dr(C,m),pm(_)||gm(C,n,b,L))}),ci(_,[C,L])},onEnterCancelled(C){A(C,!1,void 0,!0),ci(v,[C])},onAppearCancelled(C){A(C,!0,void 0,!0),ci(N,[C])},onLeaveCancelled(C){T(C),ci(x,[C])}})}function TC(e){if(e==null)return null;if(ch(e))return[bu(e.enter),bu(e.leave)];{const t=bu(e);return[t,t]}}function bu(e){return uh(e)}function Dr(e,t){t.split(/\s+/).forEach(r=>r&&e.classList.add(r)),(e[cs]||(e[cs]=new Set)).add(t)}function Mn(e,t){t.split(/\s+/).forEach(n=>n&&e.classList.remove(n));const r=e[cs];r&&(r.delete(t),r.size||(e[cs]=void 0))}function mm(e){requestAnimationFrame(()=>{requestAnimationFrame(e)})}let kC=0;function gm(e,t,r,n){const i=e._endId=++kC,s=()=>{i===e._endId&&n()};if(r!=null)return setTimeout(s,r);const{type:o,timeout:a,propCount:l}=m_(e,t);if(!o)return n();const u=o+"end";let c=0;const d=()=>{e.removeEventListener(u,p),s()},p=m=>{m.target===e&&++c>=l&&d()};setTimeout(()=>{c<l&&d()},a+1),e.addEventListener(u,p)}function m_(e,t){const r=window.getComputedStyle(e),n=h=>(r[h]||"").split(", "),i=n(`${kn}Delay`),s=n(`${kn}Duration`),o=bm(i,s),a=n(`${Ds}Delay`),l=n(`${Ds}Duration`),u=bm(a,l);let c=null,d=0,p=0;t===kn?o>0&&(c=kn,d=o,p=s.length):t===Ds?u>0&&(c=Ds,d=u,p=l.length):(d=Math.max(o,u),c=d>0?o>u?kn:Ds:null,p=c?c===kn?s.length:l.length:0);const m=c===kn&&/\b(?:transform|all)(?:,|$)/.test(n(`${kn}Property`).toString());return{type:c,timeout:d,propCount:p,hasTransform:m}}function bm(e,t){for(;e.length<t.length;)e=e.concat(e);return Math.max(...t.map((r,n)=>vm(r)+vm(e[n])))}function vm(e){return e==="auto"?0:Number(e.slice(0,-1).replace(",","."))*1e3}function hh(e){return(e?e.ownerDocument:document).body.offsetHeight}function OC(e,t,r){const n=e[cs];n&&(t=(t?[t,...n]:[...n]).join(" ")),t==null?e.removeAttribute("class"):r?e.setAttribute("class",t):e.className=t}const El=Symbol("_vod"),g_=Symbol("_vsh"),b_={name:"show",beforeMount(e,{value:t},{transition:r}){e[El]=e.style.display==="none"?"":e.style.display,r&&t?r.beforeEnter(e):$s(e,t)},mounted(e,{value:t},{transition:r}){r&&t&&r.enter(e)},updated(e,{value:t,oldValue:r},{transition:n}){!t!=!r&&(n?t?(n.beforeEnter(e),$s(e,!0),n.enter(e)):n.leave(e,()=>{$s(e,!1)}):$s(e,t))},beforeUnmount(e,{value:t}){$s(e,t)}};function $s(e,t){e.style.display=t?e[El]:"none",e[g_]=!t}function NC(){b_.getSSRProps=({value:e})=>{if(!e)return{style:{display:"none"}}}}const v_=Symbol("");function PC(e){const t=zt();if(!t)return;const r=t.ut=(i=e(t.proxy))=>{Array.from(document.querySelectorAll(`[data-v-owner="${t.uid}"]`)).forEach(s=>xl(s,i))},n=()=>{const i=e(t.proxy);t.ce?xl(t.ce,i):fh(t.subTree,i),r(i)};mf(()=>{Co(n)}),Xo(()=>{wi(n,cC,{flush:"post"});const i=new MutationObserver(n);i.observe(t.subTree.el.parentNode,{childList:!0}),vc(()=>i.disconnect())})}function fh(e,t){if(e.shapeFlag&128){const r=e.suspense;e=r.activeBranch,r.pendingBranch&&!r.isHydrating&&r.effects.push(()=>{fh(r.activeBranch,t)})}for(;e.component;)e=e.component.subTree;if(e.shapeFlag&1&&e.el)xl(e.el,t);else if(e.type===it)e.children.forEach(r=>fh(r,t));else if(e.type===xi){let{el:r,anchor:n}=e;for(;r&&(xl(r,t),r!==n);)r=r.nextSibling}}function xl(e,t){if(e.nodeType===1){const r=e.style;let n="";for(const i in t){const s=EC(t[i]);r.setProperty(`--${i}`,s),n+=`--${i}: ${s};`}r[v_]=n}}const IC=/(?:^|;)\s*display\s*:/;function FC(e,t,r){const n=e.style,i=ls(r);let s=!1;if(r&&!i){if(t)if(ls(t))for(const o of t.split(";")){const a=o.slice(0,o.indexOf(":")).trim();r[a]==null&&Ya(n,a,"")}else for(const o in t)r[o]==null&&Ya(n,o,"");for(const o in r)o==="display"&&(s=!0),Ya(n,o,r[o])}else if(i){if(t!==r){const o=n[v_];o&&(r+=";"+o),n.cssText=r,s=IC.test(r)}}else t&&e.removeAttribute("style");El in e&&(e[El]=s?n.display:"",e[g_]&&(n.display="none"))}const ym=/\s*!important$/;function Ya(e,t,r){if(rr(r))r.forEach(n=>Ya(e,t,n));else if(r==null&&(r=""),t.startsWith("--"))e.setProperty(t,r);else{const n=LC(e,t);ym.test(r)?e.setProperty(zn(n),r.replace(ym,""),"important"):e[n]=r}}const _m=["Webkit","Moz","ms"],vu={};function LC(e,t){const r=vu[t];if(r)return r;let n=tr(t);if(n!=="filter"&&n in e)return vu[t]=n;n=bC(n);for(let i=0;i<_m.length;i++){const s=_m[i]+n;if(s in e)return vu[t]=s}return t}const wm="http://www.w3.org/1999/xlink";function Em(e,t,r,n,i,s=_C(t)){n&&t.startsWith("xlink:")?r==null?e.removeAttributeNS(wm,t.slice(6,t.length)):e.setAttributeNS(wm,t,r):r==null||s&&!c_(r)?e.removeAttribute(t):e.setAttribute(t,s?"":lh(r)?String(r):r)}function xm(e,t,r,n,i){if(t==="innerHTML"||t==="textContent"){r!=null&&(e[t]=t==="innerHTML"?u_(r):r);return}const s=e.tagName;if(t==="value"&&s!=="PROGRESS"&&!s.includes("-")){const a=s==="OPTION"?e.getAttribute("value")||"":e.value,l=r==null?e.type==="checkbox"?"on":"":String(r);(a!==l||!("_value"in e))&&(e.value=l),r==null&&e.removeAttribute(t),e._value=r;return}let o=!1;if(r===""||r==null){const a=typeof e[t];a==="boolean"?r=c_(r):r==null&&a==="string"?(r="",o=!0):a==="number"&&(r=0,o=!0)}try{e[t]=r}catch{}o&&e.removeAttribute(i||t)}function cn(e,t,r,n){e.addEventListener(t,r,n)}function RC(e,t,r,n){e.removeEventListener(t,r,n)}const Sm=Symbol("_vei");function MC(e,t,r,n,i=null){const s=e[Sm]||(e[Sm]={}),o=s[t];if(n&&o)o.value=n;else{const[a,l]=DC(t);if(n){const u=s[t]=BC(n,i);cn(e,a,u,l)}else o&&(RC(e,a,o,l),s[t]=void 0)}}const Cm=/(?:Once|Passive|Capture)$/;function DC(e){let t;if(Cm.test(e)){t={};let n;for(;n=e.match(Cm);)e=e.slice(0,e.length-n[0].length),t[n[0].toLowerCase()]=!0}return[e[2]===":"?e.slice(3):zn(e.slice(2)),t]}let yu=0;const $C=Promise.resolve(),VC=()=>yu||($C.then(()=>yu=0),yu=Date.now());function BC(e,t){const r=n=>{if(!n._vts)n._vts=Date.now();else if(n._vts<=r.attached)return;pr(UC(n,r.value),t,5,[n])};return r.value=e,r.attached=VC(),r}function UC(e,t){if(rr(t)){const r=e.stopImmediatePropagation;return e.stopImmediatePropagation=()=>{r.call(e),e._stopped=!0},t.map(n=>i=>!i._stopped&&n&&n(i))}else return t}const Am=e=>e.charCodeAt(0)===111&&e.charCodeAt(1)===110&&e.charCodeAt(2)>96&&e.charCodeAt(2)<123,y_=(e,t,r,n,i,s)=>{const o=i==="svg";t==="class"?OC(e,n,o):t==="style"?FC(e,r,n):uC(t)?dC(t)||MC(e,t,r,n,s):(t[0]==="."?(t=t.slice(1),!0):t[0]==="^"?(t=t.slice(1),!1):zC(e,t,n,o))?(xm(e,t,n),!e.tagName.includes("-")&&(t==="value"||t==="checked"||t==="selected")&&Em(e,t,n,o,s,t!=="value")):e._isVueCE&&(/[A-Z]/.test(t)||!ls(n))?xm(e,Ga(t),n,s,t):(t==="true-value"?e._trueValue=n:t==="false-value"&&(e._falseValue=n),Em(e,t,n,o))};function zC(e,t,r,n){if(n)return!!(t==="innerHTML"||t==="textContent"||t in e&&Am(t)&&a_(r));if(t==="spellcheck"||t==="draggable"||t==="translate"||t==="autocorrect"||t==="sandbox"&&e.tagName==="IFRAME"||t==="form"||t==="list"&&e.tagName==="INPUT"||t==="type"&&e.tagName==="TEXTAREA")return!1;if(t==="width"||t==="height"){const i=e.tagName;if(i==="IMG"||i==="VIDEO"||i==="CANVAS"||i==="SOURCE")return!1}return Am(t)&&ls(r)?!1:t in e}const Tm={};function __(e,t,r){let n=Cs(e,t);l_(n)&&(n=Gn({},n,t));class i extends Ec{constructor(o){super(n,o,r)}}return i.def=n,i}const HC=((e,t)=>__(e,t,Pf)),qC=typeof HTMLElement<"u"?HTMLElement:class{};class Ec extends qC{constructor(t,r={},n=Al){super(),this._def=t,this._props=r,this._createApp=n,this._isVueCE=!0,this._instance=null,this._app=null,this._nonce=this._def.nonce,this._connected=!1,this._resolved=!1,this._patching=!1,this._dirty=!1,this._numberProps=null,this._styleChildren=new WeakSet,this._ob=null,this.shadowRoot&&n!==Al?this._root=this.shadowRoot:t.shadowRoot!==!1?(this.attachShadow(Gn({},t.shadowRootOptions,{mode:"open"})),this._root=this.shadowRoot):this._root=this}connectedCallback(){if(!this.isConnected)return;!this.shadowRoot&&!this._resolved&&this._parseSlots(),this._connected=!0;let t=this;for(;t=t&&(t.parentNode||t.host);)if(t instanceof Ec){this._parent=t;break}this._instance||(this._resolved?this._mount(this._def):t&&t._pendingResolve?this._pendingResolve=t._pendingResolve.then(()=>{this._pendingResolve=void 0,this._resolveDef()}):this._resolveDef())}_setParent(t=this._parent){t&&(this._instance.parent=t._instance,this._inheritParentContext(t))}_inheritParentContext(t=this._parent){t&&this._app&&Object.setPrototypeOf(this._app._context.provides,t._instance.provides)}disconnectedCallback(){this._connected=!1,hc(()=>{this._connected||(this._ob&&(this._ob.disconnect(),this._ob=null),this._app&&this._app.unmount(),this._instance&&(this._instance.ce=void 0),this._app=this._instance=null,this._teleportTargets&&(this._teleportTargets.clear(),this._teleportTargets=void 0))})}_processMutations(t){for(const r of t)this._setAttr(r.attributeName)}_resolveDef(){if(this._pendingResolve)return;for(let n=0;n<this.attributes.length;n++)this._setAttr(this.attributes[n].name);this._ob=new MutationObserver(this._processMutations.bind(this)),this._ob.observe(this,{attributes:!0});const t=(n,i=!1)=>{this._resolved=!0,this._pendingResolve=void 0;const{props:s,styles:o}=n;let a;if(s&&!rr(s))for(const l in s){const u=s[l];(u===Number||u&&u.type===Number)&&(l in this._props&&(this._props[l]=uh(this._props[l])),(a||(a=Object.create(null)))[Ga(l)]=!0)}this._numberProps=a,this._resolveProps(n),this.shadowRoot&&this._applyStyles(o),this._mount(n)},r=this._def.__asyncLoader;r?this._pendingResolve=r().then(n=>{n.configureApp=this._def.configureApp,t(this._def=n,!0)}):t(this._def)}_mount(t){this._app=this._createApp(t),this._inheritParentContext(),t.configureApp&&t.configureApp(this._app),this._app._ceVNode=this._createVNode(),this._app.mount(this._root);const r=this._instance&&this._instance.exposed;if(r)for(const n in r)fC(this,n)||Object.defineProperty(this,n,{get:()=>jo(r[n])})}_resolveProps(t){const{props:r}=t,n=rr(r)?r:Object.keys(r||{});for(const i of Object.keys(this))i[0]!=="_"&&n.includes(i)&&this._setProp(i,this[i]);for(const i of n.map(Ga))Object.defineProperty(this,i,{get(){return this._getProp(i)},set(s){this._setProp(i,s,!0,!this._patching)}})}_setAttr(t){if(t.startsWith("data-v-"))return;const r=this.hasAttribute(t);let n=r?this.getAttribute(t):Tm;const i=Ga(t);r&&this._numberProps&&this._numberProps[i]&&(n=uh(n)),this._setProp(i,n,!1,!0)}_getProp(t){return this._props[t]}_setProp(t,r,n=!0,i=!1){if(r!==this._props[t]&&(this._dirty=!0,r===Tm?delete this._props[t]:(this._props[t]=r,t==="key"&&this._app&&(this._app._ceVNode.key=r)),i&&this._instance&&this._update(),n)){const s=this._ob;s&&(this._processMutations(s.takeRecords()),s.disconnect()),r===!0?this.setAttribute(zn(t),""):typeof r=="string"||typeof r=="number"?this.setAttribute(zn(t),r+""):r||this.removeAttribute(zn(t)),s&&s.observe(this,{attributes:!0})}}_update(){const t=this._createVNode();this._app&&(t.appContext=this._app._context),P_(t,this._root)}_createVNode(){const t={};this.shadowRoot||(t.onVnodeMounted=t.onVnodeUpdated=this._renderSlots.bind(this));const r=Be(this._def,Gn(t,this._props));return this._instance||(r.ce=n=>{this._instance=n,n.ce=this,n.isCE=!0;const i=(s,o)=>{this.dispatchEvent(new CustomEvent(s,l_(o[0])?Gn({detail:o},o[0]):{detail:o}))};n.emit=(s,...o)=>{i(s,o),zn(s)!==s&&i(zn(s),o)},this._setParent()}),r}_applyStyles(t,r){if(!t)return;if(r){if(r===this._def||this._styleChildren.has(r))return;this._styleChildren.add(r)}const n=this._nonce;for(let i=t.length-1;i>=0;i--){const s=document.createElement("style");n&&s.setAttribute("nonce",n),s.textContent=t[i],this.shadowRoot.prepend(s)}}_parseSlots(){const t=this._slots={};let r;for(;r=this.firstChild;){const n=r.nodeType===1&&r.getAttribute("slot")||"default";(t[n]||(t[n]=[])).push(r),this.removeChild(r)}}_renderSlots(){const t=this._getSlots(),r=this._instance.type.__scopeId;for(let n=0;n<t.length;n++){const i=t[n],s=i.getAttribute("name")||"default",o=this._slots[s],a=i.parentNode;if(o)for(const l of o){if(r&&l.nodeType===1){const u=r+"-s",c=document.createTreeWalker(l,1);l.setAttribute(u,"");let d;for(;d=c.nextNode();)d.setAttribute(u,"")}a.insertBefore(l,i)}else for(;i.firstChild;)a.insertBefore(i.firstChild,i);a.removeChild(i)}}_getSlots(){const t=[this];this._teleportTargets&&t.push(...this._teleportTargets);const r=new Set;for(const n of t){const i=n.querySelectorAll("slot");for(let s=0;s<i.length;s++)r.add(i[s])}return Array.from(r)}_injectChildStyle(t){this._applyStyles(t.styles,t)}_beginPatch(){this._patching=!0,this._dirty=!1}_endPatch(){this._patching=!1,this._dirty&&this._instance&&this._update()}_removeChildStyle(t){}}function w_(e){const t=zt(),r=t&&t.ce;return r||null}function jC(){const e=w_();return e&&e.shadowRoot}function WC(e="$style"){{const t=zt();if(!t)return gu;const r=t.type.__cssModules;if(!r)return gu;const n=r[e];return n||gu}}const E_=new WeakMap,x_=new WeakMap,Sl=Symbol("_moveCb"),km=Symbol("_enterCb"),KC=e=>(delete e.props.mode,e),GC=KC({name:"TransitionGroup",props:Gn({},f_,{tag:String,moveClass:String}),setup(e,{slots:t}){const r=zt(),n=hf();let i,s;return gc(()=>{if(!i.length)return;const o=e.moveClass||`${e.name||"v"}-move`;if(!ZC(i[0].el,r.vnode.el,o)){i=[];return}i.forEach(XC),i.forEach(JC);const a=i.filter(QC);hh(r.vnode.el),a.forEach(l=>{const u=l.el,c=u.style;Dr(u,o),c.transform=c.webkitTransform=c.transitionDuration="";const d=u[Sl]=p=>{p&&p.target!==u||(!p||p.propertyName.endsWith("transform"))&&(u.removeEventListener("transitionend",d),u[Sl]=null,Mn(u,o))};u.addEventListener("transitionend",d)}),i=[]}),()=>{const o=ye(e),a=p_(o);let l=o.tag||it;if(i=[],s)for(let u=0;u<s.length;u++){const c=s[u];c.el&&c.el instanceof Element&&(i.push(c),_n(c,os(c,a,n,r)),E_.set(c,{left:c.el.offsetLeft,top:c.el.offsetTop}))}s=t.default?pc(t.default()):[];for(let u=0;u<s.length;u++){const c=s[u];c.key!=null&&_n(c,os(c,a,n,r))}return Be(l,null,s)}}}),YC=GC;function XC(e){const t=e.el;t[Sl]&&t[Sl](),t[km]&&t[km]()}function JC(e){x_.set(e,{left:e.el.offsetLeft,top:e.el.offsetTop})}function QC(e){const t=E_.get(e),r=x_.get(e),n=t.left-r.left,i=t.top-r.top;if(n||i){const s=e.el.style;return s.transform=s.webkitTransform=`translate(${n}px,${i}px)`,s.transitionDuration="0s",e}}function ZC(e,t,r){const n=e.cloneNode(),i=e[cs];i&&i.forEach(a=>{a.split(/\s+/).forEach(l=>l&&n.classList.remove(l))}),r.split(/\s+/).forEach(a=>a&&n.classList.add(a)),n.style.display="none";const s=t.nodeType===1?t:t.parentNode;s.appendChild(n);const{hasTransform:o}=m_(n);return s.removeChild(n),o}const Zn=e=>{const t=e.props["onUpdate:modelValue"]||!1;return rr(t)?r=>vC(t,r):t};function eA(e){e.target.composing=!0}function Om(e){const t=e.target;t.composing&&(t.composing=!1,t.dispatchEvent(new Event("input")))}const fr=Symbol("_assign");function Nm(e,t,r){return t&&(e=e.trim()),r&&(e=kf(e)),e}const Cl={created(e,{modifiers:{lazy:t,trim:r,number:n}},i){e[fr]=Zn(i);const s=n||i.props&&i.props.type==="number";cn(e,t?"change":"input",o=>{o.target.composing||e[fr](Nm(e.value,r,s))}),(r||s)&&cn(e,"change",()=>{e.value=Nm(e.value,r,s)}),t||(cn(e,"compositionstart",eA),cn(e,"compositionend",Om),cn(e,"change",Om))},mounted(e,{value:t}){e.value=t??""},beforeUpdate(e,{value:t,oldValue:r,modifiers:{lazy:n,trim:i,number:s}},o){if(e[fr]=Zn(o),e.composing)return;const a=(s||e.type==="number")&&!/^0\d/.test(e.value)?kf(e.value):e.value,l=t??"";a!==l&&(document.activeElement===e&&e.type!=="range"&&(n&&t===r||i&&e.value.trim()===l)||(e.value=l))}},Of={deep:!0,created(e,t,r){e[fr]=Zn(r),cn(e,"change",()=>{const n=e._modelValue,i=us(e),s=e.checked,o=e[fr];if(rr(n)){const a=wc(n,i),l=a!==-1;if(s&&!l)o(n.concat(i));else if(!s&&l){const u=[...n];u.splice(a,1),o(u)}}else if(Qo(n)){const a=new Set(n);s?a.add(i):a.delete(i),o(a)}else o(C_(e,s))})},mounted:Pm,beforeUpdate(e,t,r){e[fr]=Zn(r),Pm(e,t,r)}};function Pm(e,{value:t,oldValue:r},n){e._modelValue=t;let i;if(rr(t))i=wc(t,n.props.value)>-1;else if(Qo(t))i=t.has(n.props.value);else{if(t===r)return;i=Qn(t,C_(e,!0))}e.checked!==i&&(e.checked=i)}const Nf={created(e,{value:t},r){e.checked=Qn(t,r.props.value),e[fr]=Zn(r),cn(e,"change",()=>{e[fr](us(e))})},beforeUpdate(e,{value:t,oldValue:r},n){e[fr]=Zn(n),t!==r&&(e.checked=Qn(t,n.props.value))}},S_={deep:!0,created(e,{value:t,modifiers:{number:r}},n){const i=Qo(t);cn(e,"change",()=>{const s=Array.prototype.filter.call(e.options,o=>o.selected).map(o=>r?kf(us(o)):us(o));e[fr](e.multiple?i?new Set(s):s:s[0]),e._assigning=!0,hc(()=>{e._assigning=!1})}),e[fr]=Zn(n)},mounted(e,{value:t}){Im(e,t)},beforeUpdate(e,t,r){e[fr]=Zn(r)},updated(e,{value:t}){e._assigning||Im(e,t)}};function Im(e,t){const r=e.multiple,n=rr(t);if(!(r&&!n&&!Qo(t))){for(let i=0,s=e.options.length;i<s;i++){const o=e.options[i],a=us(o);if(r)if(n){const l=typeof a;l==="string"||l==="number"?o.selected=t.some(u=>String(u)===String(a)):o.selected=wc(t,a)>-1}else o.selected=t.has(a);else if(Qn(us(o),t)){e.selectedIndex!==i&&(e.selectedIndex=i);return}}!r&&e.selectedIndex!==-1&&(e.selectedIndex=-1)}}function us(e){return"_value"in e?e._value:e.value}function C_(e,t){const r=t?"_trueValue":"_falseValue";return r in e?e[r]:t}const A_={created(e,t,r){Fa(e,t,r,null,"created")},mounted(e,t,r){Fa(e,t,r,null,"mounted")},beforeUpdate(e,t,r,n){Fa(e,t,r,n,"beforeUpdate")},updated(e,t,r,n){Fa(e,t,r,n,"updated")}};function T_(e,t){switch(e){case"SELECT":return S_;case"TEXTAREA":return Cl;default:switch(t){case"checkbox":return Of;case"radio":return Nf;default:return Cl}}}function Fa(e,t,r,n,i){const o=T_(e.tagName,r.props&&r.props.type)[i];o&&o(e,t,r,n)}function tA(){Cl.getSSRProps=({value:e})=>({value:e}),Nf.getSSRProps=({value:e},t)=>{if(t.props&&Qn(t.props.value,e))return{checked:!0}},Of.getSSRProps=({value:e},t)=>{if(rr(e)){if(t.props&&wc(e,t.props.value)>-1)return{checked:!0}}else if(Qo(e)){if(t.props&&e.has(t.props.value))return{checked:!0}}else if(e)return{checked:!0}},A_.getSSRProps=(e,t)=>{if(typeof t.type!="string")return;const r=T_(t.type.toUpperCase(),t.props&&t.props.type);if(r.getSSRProps)return r.getSSRProps(e,t)}}const rA=["ctrl","shift","alt","meta"],nA={stop:e=>e.stopPropagation(),prevent:e=>e.preventDefault(),self:e=>e.target!==e.currentTarget,ctrl:e=>!e.ctrlKey,shift:e=>!e.shiftKey,alt:e=>!e.altKey,meta:e=>!e.metaKey,left:e=>"button"in e&&e.button!==0,middle:e=>"button"in e&&e.button!==1,right:e=>"button"in e&&e.button!==2,exact:(e,t)=>rA.some(r=>e[`${r}Key`]&&!t.includes(r))},iA=(e,t)=>{const r=e._withMods||(e._withMods={}),n=t.join(".");return r[n]||(r[n]=((i,...s)=>{for(let o=0;o<t.length;o++){const a=nA[t[o]];if(a&&a(i,t))return}return e(i,...s)}))},sA={esc:"escape",space:" ",up:"arrow-up",left:"arrow-left",right:"arrow-right",down:"arrow-down",delete:"backspace"},oA=(e,t)=>{const r=e._withKeys||(e._withKeys={}),n=t.join(".");return r[n]||(r[n]=(i=>{if(!("key"in i))return;const s=zn(i.key);if(t.some(o=>o===s||sA[o]===s))return e(i)}))},k_=Gn({patchProp:y_},d_);let uo,Fm=!1;function O_(){return uo||(uo=By(k_))}function N_(){return uo=Fm?uo:Uy(k_),Fm=!0,uo}const P_=((...e)=>{O_().render(...e)}),aA=((...e)=>{N_().hydrate(...e)}),Al=((...e)=>{const t=O_().createApp(...e),{mount:r}=t;return t.mount=n=>{const i=F_(n);if(!i)return;const s=t._component;!a_(s)&&!s.render&&!s.template&&(s.template=i.innerHTML),i.nodeType===1&&(i.textContent="");const o=r(i,!1,I_(i));return i instanceof Element&&(i.removeAttribute("v-cloak"),i.setAttribute("data-v-app","")),o},t}),Pf=((...e)=>{const t=N_().createApp(...e),{mount:r}=t;return t.mount=n=>{const i=F_(n);if(i)return r(i,!0,I_(i))},t});function I_(e){if(e instanceof SVGElement)return"svg";if(typeof MathMLElement=="function"&&e instanceof MathMLElement)return"mathml"}function F_(e){return ls(e)?document.querySelector(e):e}let Lm=!1;const lA=()=>{Lm||(Lm=!0,tA(),NC())},cA=Object.freeze(Object.defineProperty({__proto__:null,BaseTransition:gy,BaseTransitionPropsValidators:ff,Comment:Ge,DeprecationTypes:aC,EffectScope:tf,ErrorCodes:dx,ErrorTypeStrings:eC,Fragment:it,KeepAlive:zx,ReactiveEffect:Eo,Static:xi,Suspense:LS,Teleport:Sx,Text:Kn,TrackOpTypes:U1,Transition:AC,TransitionGroup:YC,TriggerOpTypes:z1,VueElement:Ec,assertNumber:ux,callWithAsyncErrorHandling:pr,callWithErrorHandling:Ss,camelize:tr,capitalize:uc,cloneVNode:Kr,compatUtils:oC,computed:Pt,createApp:Al,createBlock:yl,createCommentVNode:qS,createElementBlock:BS,createElementVNode:xf,createHydrationRenderer:Uy,createPropsRestProxy:uS,createRenderer:By,createSSRApp:Pf,createSlots:Yx,createStaticVNode:HS,createTextVNode:Sf,createVNode:Be,customRef:Hv,defineAsyncComponent:Bx,defineComponent:Cs,defineCustomElement:__,defineEmits:eS,defineExpose:tS,defineModel:iS,defineOptions:rS,defineProps:Zx,defineSSRCustomElement:HC,defineSlots:nS,devtools:tC,effect:c1,effectScope:o1,getCurrentInstance:zt,getCurrentScope:Cv,getCurrentWatcher:H1,getTransitionRawChildren:pc,guardReactiveProps:Jy,h:pn,handleError:Ri,hasInjectionContext:yx,hydrate:aA,hydrateOnIdle:Lx,hydrateOnInteraction:$x,hydrateOnMediaQuery:Dx,hydrateOnVisible:Mx,initCustomFormatter:JS,initDirectivesForSSR:lA,inject:oo,isMemoSame:s_,isProxy:qo,isReactive:hn,isReadonly:Wr,isRef:Ze,isRuntimeOnly:GS,isShallow:Ut,isVNode:wn,markRaw:dl,mergeDefaults:lS,mergeModels:cS,mergeProps:Qy,nextTick:hc,nodeOps:d_,normalizeClass:Ko,normalizeProps:ax,normalizeStyle:Wo,onActivated:vy,onBeforeMount:wy,onBeforeUnmount:bc,onBeforeUpdate:mf,onDeactivated:yy,onErrorCaptured:Cy,onMounted:Xo,onRenderTracked:Sy,onRenderTriggered:xy,onScopeDispose:a1,onServerPrefetch:Ey,onUnmounted:vc,onUpdated:gc,onWatcherCleanup:jv,openBlock:No,patchProp:y_,popScopeId:gx,provide:sy,proxyRefs:af,pushScopeId:mx,queuePostFlushCb:Co,reactive:Es,readonly:ul,ref:Wn,registerRuntimeCompiler:r_,render:P_,renderList:Gx,renderSlot:Xx,resolveComponent:jx,resolveDirective:Kx,resolveDynamicComponent:Wx,resolveFilter:sC,resolveTransitionHooks:os,setBlockTracking:Po,setDevtoolsHook:rC,setTransitionHooks:_n,shallowReactive:Uv,shallowReadonly:O1,shallowRef:of,ssrContextKey:oy,ssrUtils:iC,stop:u1,toDisplayString:Qv,toHandlerKey:io,toHandlers:Jx,toRaw:ye,toRef:$1,toRefs:R1,toValue:I1,transformVNodeArgs:US,triggerRef:P1,unref:jo,useAttrs:aS,useCssModule:WC,useCssVars:PC,useHost:w_,useId:Ax,useModel:yS,useSSRContext:ay,useShadowRoot:jC,useSlots:oS,useTemplateRef:Tx,useTransitionState:hf,vModelCheckbox:Of,vModelDynamic:A_,vModelRadio:Nf,vModelSelect:S_,vModelText:Cl,vShow:b_,version:o_,warn:ZS,watch:wi,watchEffect:_x,watchPostEffect:wx,watchSyncEffect:ly,withAsyncContext:dS,withCtx:df,withDefaults:sS,withDirectives:vx,withKeys:oA,withMemo:QS,withModifiers:iA,withScopeId:bx},Symbol.toStringTag,{value:"Module"}));function Xr(e){const t=Object.create(null);for(const r of e.split(","))t[r]=1;return r=>r in t}const uA={},ho=()=>{},La=()=>!1,L_=e=>e.charCodeAt(0)===111&&e.charCodeAt(1)===110&&(e.charCodeAt(2)>122||e.charCodeAt(2)<97),mn=Object.assign,qn=Array.isArray,ft=e=>typeof e=="string",If=e=>typeof e=="symbol",dA=e=>e!==null&&typeof e=="object",Rm=Xr(",key,ref,ref_for,ref_key,onVnodeBeforeMount,onVnodeMounted,onVnodeBeforeUpdate,onVnodeUpdated,onVnodeBeforeUnmount,onVnodeUnmounted"),hA=Xr("bind,cloak,else-if,else,for,html,if,model,on,once,pre,show,slot,text,memo"),Ff=e=>{const t=Object.create(null);return(r=>t[r]||(t[r]=e(r)))},fA=/-\w/g,Yn=Ff(e=>e.replace(fA,t=>t.slice(1).toUpperCase())),Lf=Ff(e=>e.charAt(0).toUpperCase()+e.slice(1)),pA=Ff(e=>e?`on${Lf(e)}`:"");function mA(e,t){return e+JSON.stringify(t,(r,n)=>typeof n=="function"?n.toString():n)}const gA=/;(?![^(]*\))/g,bA=/:([^]+)/,vA=/\/\*[^]*?\*\//g;function yA(e){const t={};return e.replace(vA,"").split(gA).forEach(r=>{if(r){const n=r.split(bA);n.length>1&&(t[n[0].trim()]=n[1].trim())}}),t}const _A="html,body,base,head,link,meta,style,title,address,article,aside,footer,header,hgroup,h1,h2,h3,h4,h5,h6,nav,section,div,dd,dl,dt,figcaption,figure,picture,hr,img,li,main,ol,p,pre,ul,a,b,abbr,bdi,bdo,br,cite,code,data,dfn,em,i,kbd,mark,q,rp,rt,ruby,s,samp,small,span,strong,sub,sup,time,u,var,wbr,area,audio,map,track,video,embed,object,param,source,canvas,script,noscript,del,ins,caption,col,colgroup,table,thead,tbody,td,th,tr,button,datalist,fieldset,form,input,label,legend,meter,optgroup,option,output,progress,select,textarea,details,dialog,menu,summary,template,blockquote,iframe,tfoot",wA="svg,animate,animateMotion,animateTransform,circle,clipPath,color-profile,defs,desc,discard,ellipse,feBlend,feColorMatrix,feComponentTransfer,feComposite,feConvolveMatrix,feDiffuseLighting,feDisplacementMap,feDistantLight,feDropShadow,feFlood,feFuncA,feFuncB,feFuncG,feFuncR,feGaussianBlur,feImage,feMerge,feMergeNode,feMorphology,feOffset,fePointLight,feSpecularLighting,feSpotLight,feTile,feTurbulence,filter,foreignObject,g,hatch,hatchpath,image,line,linearGradient,marker,mask,mesh,meshgradient,meshpatch,meshrow,metadata,mpath,path,pattern,polygon,polyline,radialGradient,rect,set,solidcolor,stop,switch,symbol,text,textPath,title,tspan,unknown,use,view",EA="annotation,annotation-xml,maction,maligngroup,malignmark,math,menclose,merror,mfenced,mfrac,mfraction,mglyph,mi,mlabeledtr,mlongdiv,mmultiscripts,mn,mo,mover,mpadded,mphantom,mprescripts,mroot,mrow,ms,mscarries,mscarry,msgroup,msline,mspace,msqrt,msrow,mstack,mstyle,msub,msubsup,msup,mtable,mtd,mtext,mtr,munder,munderover,none,semantics",xA="area,base,br,col,embed,hr,img,input,link,meta,param,source,track,wbr",SA=Xr(_A),CA=Xr(wA),AA=Xr(EA),TA=Xr(xA);const Io=Symbol(""),fo=Symbol(""),Rf=Symbol(""),Tl=Symbol(""),R_=Symbol(""),Ni=Symbol(""),M_=Symbol(""),D_=Symbol(""),Mf=Symbol(""),Df=Symbol(""),Zo=Symbol(""),$f=Symbol(""),$_=Symbol(""),Vf=Symbol(""),Bf=Symbol(""),Uf=Symbol(""),zf=Symbol(""),Hf=Symbol(""),qf=Symbol(""),V_=Symbol(""),B_=Symbol(""),xc=Symbol(""),kl=Symbol(""),jf=Symbol(""),Wf=Symbol(""),Fo=Symbol(""),ea=Symbol(""),Kf=Symbol(""),ph=Symbol(""),kA=Symbol(""),mh=Symbol(""),Ol=Symbol(""),OA=Symbol(""),NA=Symbol(""),Gf=Symbol(""),PA=Symbol(""),IA=Symbol(""),Yf=Symbol(""),U_=Symbol(""),ds={[Io]:"Fragment",[fo]:"Teleport",[Rf]:"Suspense",[Tl]:"KeepAlive",[R_]:"BaseTransition",[Ni]:"openBlock",[M_]:"createBlock",[D_]:"createElementBlock",[Mf]:"createVNode",[Df]:"createElementVNode",[Zo]:"createCommentVNode",[$f]:"createTextVNode",[$_]:"createStaticVNode",[Vf]:"resolveComponent",[Bf]:"resolveDynamicComponent",[Uf]:"resolveDirective",[zf]:"resolveFilter",[Hf]:"withDirectives",[qf]:"renderList",[V_]:"renderSlot",[B_]:"createSlots",[xc]:"toDisplayString",[kl]:"mergeProps",[jf]:"normalizeClass",[Wf]:"normalizeStyle",[Fo]:"normalizeProps",[ea]:"guardReactiveProps",[Kf]:"toHandlers",[ph]:"camelize",[kA]:"capitalize",[mh]:"toHandlerKey",[Ol]:"setBlockTracking",[OA]:"pushScopeId",[NA]:"popScopeId",[Gf]:"withCtx",[PA]:"unref",[IA]:"isRef",[Yf]:"withMemo",[U_]:"isMemoSame"};function FA(e){Object.getOwnPropertySymbols(e).forEach(t=>{ds[t]=e[t]})}const sr={start:{line:1,column:1,offset:0},end:{line:1,column:1,offset:0},source:""};function LA(e,t=""){return{type:0,source:t,children:e,helpers:new Set,components:[],directives:[],hoists:[],imports:[],cached:[],temps:0,codegenNode:void 0,loc:sr}}function Lo(e,t,r,n,i,s,o,a=!1,l=!1,u=!1,c=sr){return e&&(a?(e.helper(Ni),e.helper(ps(e.inSSR,u))):e.helper(fs(e.inSSR,u)),o&&e.helper(Hf)),{type:13,tag:t,props:r,children:n,patchFlag:i,dynamicProps:s,directives:o,isBlock:a,disableTracking:l,isComponent:u,loc:c}}function Si(e,t=sr){return{type:17,loc:t,elements:e}}function dr(e,t=sr){return{type:15,loc:t,properties:e}}function Qe(e,t){return{type:16,loc:sr,key:ft(e)?ue(e,!0):e,value:t}}function ue(e,t=!1,r=sr,n=0){return{type:4,loc:r,content:e,isStatic:t,constType:t?3:n}}function Tr(e,t=sr){return{type:8,loc:t,children:e}}function st(e,t=[],r=sr){return{type:14,loc:r,callee:e,arguments:t}}function hs(e,t=void 0,r=!1,n=!1,i=sr){return{type:18,params:e,returns:t,newline:r,isSlot:n,loc:i}}function gh(e,t,r,n=!0){return{type:19,test:e,consequent:t,alternate:r,newline:n,loc:sr}}function RA(e,t,r=!1,n=!1){return{type:20,index:e,value:t,needPauseTracking:r,inVOnce:n,needArraySpread:!1,loc:sr}}function MA(e){return{type:21,body:e,loc:sr}}function fs(e,t){return e||t?Mf:Df}function ps(e,t){return e||t?M_:D_}function Xf(e,{helper:t,removeHelper:r,inSSR:n}){e.isBlock||(e.isBlock=!0,r(fs(n,e.isComponent)),t(Ni),t(ps(n,e.isComponent)))}const Mm=new Uint8Array([123,123]),Dm=new Uint8Array([125,125]);function $m(e){return e>=97&&e<=122||e>=65&&e<=90}function Yt(e){return e===32||e===10||e===9||e===12||e===13}function On(e){return e===47||e===62||Yt(e)}function Nl(e){const t=new Uint8Array(e.length);for(let r=0;r<e.length;r++)t[r]=e.charCodeAt(r);return t}const _t={Cdata:new Uint8Array([67,68,65,84,65,91]),CdataEnd:new Uint8Array([93,93,62]),CommentEnd:new Uint8Array([45,45,62]),ScriptEnd:new Uint8Array([60,47,115,99,114,105,112,116]),StyleEnd:new Uint8Array([60,47,115,116,121,108,101]),TitleEnd:new Uint8Array([60,47,116,105,116,108,101]),TextareaEnd:new Uint8Array([60,47,116,101,120,116,97,114,101,97])};class DA{constructor(t,r){this.stack=t,this.cbs=r,this.state=1,this.buffer="",this.sectionStart=0,this.index=0,this.entityStart=0,this.baseState=1,this.inRCDATA=!1,this.inXML=!1,this.inVPre=!1,this.newlines=[],this.mode=0,this.delimiterOpen=Mm,this.delimiterClose=Dm,this.delimiterIndex=-1,this.currentSequence=void 0,this.sequenceIndex=0}get inSFCRoot(){return this.mode===2&&this.stack.length===0}reset(){this.state=1,this.mode=0,this.buffer="",this.sectionStart=0,this.index=0,this.baseState=1,this.inRCDATA=!1,this.currentSequence=void 0,this.newlines.length=0,this.delimiterOpen=Mm,this.delimiterClose=Dm}getPos(t){let r=1,n=t+1;const i=this.newlines.length;let s=-1;if(i>100){let o=-1,a=i;for(;o+1<a;){const l=o+a>>>1;this.newlines[l]<t?o=l:a=l}s=o}else for(let o=i-1;o>=0;o--)if(t>this.newlines[o]){s=o;break}return s>=0&&(r=s+2,n=t-this.newlines[s]),{column:n,line:r,offset:t}}peek(){return this.buffer.charCodeAt(this.index+1)}stateText(t){t===60?(this.index>this.sectionStart&&this.cbs.ontext(this.sectionStart,this.index),this.state=5,this.sectionStart=this.index):!this.inVPre&&t===this.delimiterOpen[0]&&(this.state=2,this.delimiterIndex=0,this.stateInterpolationOpen(t))}stateInterpolationOpen(t){if(t===this.delimiterOpen[this.delimiterIndex])if(this.delimiterIndex===this.delimiterOpen.length-1){const r=this.index+1-this.delimiterOpen.length;r>this.sectionStart&&this.cbs.ontext(this.sectionStart,r),this.state=3,this.sectionStart=r}else this.delimiterIndex++;else this.inRCDATA?(this.state=32,this.stateInRCDATA(t)):(this.state=1,this.stateText(t))}stateInterpolation(t){t===this.delimiterClose[0]&&(this.state=4,this.delimiterIndex=0,this.stateInterpolationClose(t))}stateInterpolationClose(t){t===this.delimiterClose[this.delimiterIndex]?this.delimiterIndex===this.delimiterClose.length-1?(this.cbs.oninterpolation(this.sectionStart,this.index+1),this.inRCDATA?this.state=32:this.state=1,this.sectionStart=this.index+1):this.delimiterIndex++:(this.state=3,this.stateInterpolation(t))}stateSpecialStartSequence(t){const r=this.sequenceIndex===this.currentSequence.length;if(!(r?On(t):(t|32)===this.currentSequence[this.sequenceIndex]))this.inRCDATA=!1;else if(!r){this.sequenceIndex++;return}this.sequenceIndex=0,this.state=6,this.stateInTagName(t)}stateInRCDATA(t){if(this.sequenceIndex===this.currentSequence.length){if(t===62||Yt(t)){const r=this.index-this.currentSequence.length;if(this.sectionStart<r){const n=this.index;this.index=r,this.cbs.ontext(this.sectionStart,r),this.index=n}this.sectionStart=r+2,this.stateInClosingTagName(t),this.inRCDATA=!1;return}this.sequenceIndex=0}(t|32)===this.currentSequence[this.sequenceIndex]?this.sequenceIndex+=1:this.sequenceIndex===0?this.currentSequence===_t.TitleEnd||this.currentSequence===_t.TextareaEnd&&!this.inSFCRoot?!this.inVPre&&t===this.delimiterOpen[0]&&(this.state=2,this.delimiterIndex=0,this.stateInterpolationOpen(t)):this.fastForwardTo(60)&&(this.sequenceIndex=1):this.sequenceIndex=+(t===60)}stateCDATASequence(t){t===_t.Cdata[this.sequenceIndex]?++this.sequenceIndex===_t.Cdata.length&&(this.state=28,this.currentSequence=_t.CdataEnd,this.sequenceIndex=0,this.sectionStart=this.index+1):(this.sequenceIndex=0,this.state=23,this.stateInDeclaration(t))}fastForwardTo(t){for(;++this.index<this.buffer.length;){const r=this.buffer.charCodeAt(this.index);if(r===10&&this.newlines.push(this.index),r===t)return!0}return this.index=this.buffer.length-1,!1}stateInCommentLike(t){t===this.currentSequence[this.sequenceIndex]?++this.sequenceIndex===this.currentSequence.length&&(this.currentSequence===_t.CdataEnd?this.cbs.oncdata(this.sectionStart,this.index-2):this.cbs.oncomment(this.sectionStart,this.index-2),this.sequenceIndex=0,this.sectionStart=this.index+1,this.state=1):this.sequenceIndex===0?this.fastForwardTo(this.currentSequence[0])&&(this.sequenceIndex=1):t!==this.currentSequence[this.sequenceIndex-1]&&(this.sequenceIndex=0)}startSpecial(t,r){this.enterRCDATA(t,r),this.state=31}enterRCDATA(t,r){this.inRCDATA=!0,this.currentSequence=t,this.sequenceIndex=r}stateBeforeTagName(t){t===33?(this.state=22,this.sectionStart=this.index+1):t===63?(this.state=24,this.sectionStart=this.index+1):$m(t)?(this.sectionStart=this.index,this.mode===0?this.state=6:this.inSFCRoot?this.state=34:this.inXML?this.state=6:t===116?this.state=30:this.state=t===115?29:6):t===47?this.state=8:(this.state=1,this.stateText(t))}stateInTagName(t){On(t)&&this.handleTagName(t)}stateInSFCRootTagName(t){if(On(t)){const r=this.buffer.slice(this.sectionStart,this.index);r!=="template"&&this.enterRCDATA(Nl("</"+r),0),this.handleTagName(t)}}handleTagName(t){this.cbs.onopentagname(this.sectionStart,this.index),this.sectionStart=-1,this.state=11,this.stateBeforeAttrName(t)}stateBeforeClosingTagName(t){Yt(t)||(t===62?(this.state=1,this.sectionStart=this.index+1):(this.state=$m(t)?9:27,this.sectionStart=this.index))}stateInClosingTagName(t){(t===62||Yt(t))&&(this.cbs.onclosetag(this.sectionStart,this.index),this.sectionStart=-1,this.state=10,this.stateAfterClosingTagName(t))}stateAfterClosingTagName(t){t===62&&(this.state=1,this.sectionStart=this.index+1)}stateBeforeAttrName(t){t===62?(this.cbs.onopentagend(this.index),this.inRCDATA?this.state=32:this.state=1,this.sectionStart=this.index+1):t===47?this.state=7:t===60&&this.peek()===47?(this.cbs.onopentagend(this.index),this.state=5,this.sectionStart=this.index):Yt(t)||this.handleAttrStart(t)}handleAttrStart(t){t===118&&this.peek()===45?(this.state=13,this.sectionStart=this.index):t===46||t===58||t===64||t===35?(this.cbs.ondirname(this.index,this.index+1),this.state=14,this.sectionStart=this.index+1):(this.state=12,this.sectionStart=this.index)}stateInSelfClosingTag(t){t===62?(this.cbs.onselfclosingtag(this.index),this.state=1,this.sectionStart=this.index+1,this.inRCDATA=!1):Yt(t)||(this.state=11,this.stateBeforeAttrName(t))}stateInAttrName(t){(t===61||On(t))&&(this.cbs.onattribname(this.sectionStart,this.index),this.handleAttrNameEnd(t))}stateInDirName(t){t===61||On(t)?(this.cbs.ondirname(this.sectionStart,this.index),this.handleAttrNameEnd(t)):t===58?(this.cbs.ondirname(this.sectionStart,this.index),this.state=14,this.sectionStart=this.index+1):t===46&&(this.cbs.ondirname(this.sectionStart,this.index),this.state=16,this.sectionStart=this.index+1)}stateInDirArg(t){t===61||On(t)?(this.cbs.ondirarg(this.sectionStart,this.index),this.handleAttrNameEnd(t)):t===91?this.state=15:t===46&&(this.cbs.ondirarg(this.sectionStart,this.index),this.state=16,this.sectionStart=this.index+1)}stateInDynamicDirArg(t){t===93?this.state=14:(t===61||On(t))&&(this.cbs.ondirarg(this.sectionStart,this.index+1),this.handleAttrNameEnd(t))}stateInDirModifier(t){t===61||On(t)?(this.cbs.ondirmodifier(this.sectionStart,this.index),this.handleAttrNameEnd(t)):t===46&&(this.cbs.ondirmodifier(this.sectionStart,this.index),this.sectionStart=this.index+1)}handleAttrNameEnd(t){this.sectionStart=this.index,this.state=17,this.cbs.onattribnameend(this.index),this.stateAfterAttrName(t)}stateAfterAttrName(t){t===61?this.state=18:t===47||t===62?(this.cbs.onattribend(0,this.sectionStart),this.sectionStart=-1,this.state=11,this.stateBeforeAttrName(t)):Yt(t)||(this.cbs.onattribend(0,this.sectionStart),this.handleAttrStart(t))}stateBeforeAttrValue(t){t===34?(this.state=19,this.sectionStart=this.index+1):t===39?(this.state=20,this.sectionStart=this.index+1):Yt(t)||(this.sectionStart=this.index,this.state=21,this.stateInAttrValueNoQuotes(t))}handleInAttrValue(t,r){(t===r||this.fastForwardTo(r))&&(this.cbs.onattribdata(this.sectionStart,this.index),this.sectionStart=-1,this.cbs.onattribend(r===34?3:2,this.index+1),this.state=11)}stateInAttrValueDoubleQuotes(t){this.handleInAttrValue(t,34)}stateInAttrValueSingleQuotes(t){this.handleInAttrValue(t,39)}stateInAttrValueNoQuotes(t){Yt(t)||t===62?(this.cbs.onattribdata(this.sectionStart,this.index),this.sectionStart=-1,this.cbs.onattribend(1,this.index),this.state=11,this.stateBeforeAttrName(t)):(t===39||t===60||t===61||t===96)&&this.cbs.onerr(18,this.index)}stateBeforeDeclaration(t){t===91?(this.state=26,this.sequenceIndex=0):this.state=t===45?25:23}stateInDeclaration(t){(t===62||this.fastForwardTo(62))&&(this.state=1,this.sectionStart=this.index+1)}stateInProcessingInstruction(t){(t===62||this.fastForwardTo(62))&&(this.cbs.onprocessinginstruction(this.sectionStart,this.index),this.state=1,this.sectionStart=this.index+1)}stateBeforeComment(t){t===45?(this.state=28,this.currentSequence=_t.CommentEnd,this.sequenceIndex=2,this.sectionStart=this.index+1):this.state=23}stateInSpecialComment(t){(t===62||this.fastForwardTo(62))&&(this.cbs.oncomment(this.sectionStart,this.index),this.state=1,this.sectionStart=this.index+1)}stateBeforeSpecialS(t){t===_t.ScriptEnd[3]?this.startSpecial(_t.ScriptEnd,4):t===_t.StyleEnd[3]?this.startSpecial(_t.StyleEnd,4):(this.state=6,this.stateInTagName(t))}stateBeforeSpecialT(t){t===_t.TitleEnd[3]?this.startSpecial(_t.TitleEnd,4):t===_t.TextareaEnd[3]?this.startSpecial(_t.TextareaEnd,4):(this.state=6,this.stateInTagName(t))}startEntity(){}stateInEntity(){}parse(t){for(this.buffer=t;this.index<this.buffer.length;){const r=this.buffer.charCodeAt(this.index);switch(r===10&&this.state!==33&&this.newlines.push(this.index),this.state){case 1:{this.stateText(r);break}case 2:{this.stateInterpolationOpen(r);break}case 3:{this.stateInterpolation(r);break}case 4:{this.stateInterpolationClose(r);break}case 31:{this.stateSpecialStartSequence(r);break}case 32:{this.stateInRCDATA(r);break}case 26:{this.stateCDATASequence(r);break}case 19:{this.stateInAttrValueDoubleQuotes(r);break}case 12:{this.stateInAttrName(r);break}case 13:{this.stateInDirName(r);break}case 14:{this.stateInDirArg(r);break}case 15:{this.stateInDynamicDirArg(r);break}case 16:{this.stateInDirModifier(r);break}case 28:{this.stateInCommentLike(r);break}case 27:{this.stateInSpecialComment(r);break}case 11:{this.stateBeforeAttrName(r);break}case 6:{this.stateInTagName(r);break}case 34:{this.stateInSFCRootTagName(r);break}case 9:{this.stateInClosingTagName(r);break}case 5:{this.stateBeforeTagName(r);break}case 17:{this.stateAfterAttrName(r);break}case 20:{this.stateInAttrValueSingleQuotes(r);break}case 18:{this.stateBeforeAttrValue(r);break}case 8:{this.stateBeforeClosingTagName(r);break}case 10:{this.stateAfterClosingTagName(r);break}case 29:{this.stateBeforeSpecialS(r);break}case 30:{this.stateBeforeSpecialT(r);break}case 21:{this.stateInAttrValueNoQuotes(r);break}case 7:{this.stateInSelfClosingTag(r);break}case 23:{this.stateInDeclaration(r);break}case 22:{this.stateBeforeDeclaration(r);break}case 25:{this.stateBeforeComment(r);break}case 24:{this.stateInProcessingInstruction(r);break}case 33:{this.stateInEntity();break}}this.index++}this.cleanup(),this.finish()}cleanup(){this.sectionStart!==this.index&&(this.state===1||this.state===32&&this.sequenceIndex===0?(this.cbs.ontext(this.sectionStart,this.index),this.sectionStart=this.index):(this.state===19||this.state===20||this.state===21)&&(this.cbs.onattribdata(this.sectionStart,this.index),this.sectionStart=this.index))}finish(){this.handleTrailingData(),this.cbs.onend()}handleTrailingData(){const t=this.buffer.length;this.sectionStart>=t||(this.state===28?this.currentSequence===_t.CdataEnd?this.cbs.oncdata(this.sectionStart,t):this.cbs.oncomment(this.sectionStart,t):this.state===6||this.state===11||this.state===18||this.state===17||this.state===12||this.state===13||this.state===14||this.state===15||this.state===16||this.state===20||this.state===19||this.state===21||this.state===9||this.cbs.ontext(this.sectionStart,t))}emitCodePoint(t,r){}}function Vm(e,{compatConfig:t}){const r=t&&t[e];return e==="MODE"?r||3:r}function Ci(e,t){const r=Vm("MODE",t),n=Vm(e,t);return r===3?n===!0:n!==!1}function Ro(e,t,r,...n){return Ci(e,t)}function Jf(e){throw e}function z_(e){}function $e(e,t,r,n){const i=`https://vuejs.org/error-reference/#compiler-${e}`,s=new SyntaxError(String(i));return s.code=e,s.loc=t,s}const Vt=e=>e.type===4&&e.isStatic;function H_(e){switch(e){case"Teleport":case"teleport":return fo;case"Suspense":case"suspense":return Rf;case"KeepAlive":case"keep-alive":return Tl;case"BaseTransition":case"base-transition":return R_}}const $A=/^$|^\d|[^\$\w\xA0-\uFFFF]/,Qf=e=>!$A.test(e),q_=/[A-Za-z_$\xA0-\uFFFF]/,VA=/[\.\?\w$\xA0-\uFFFF]/,BA=/\s+[.[]\s*|\s*[.[]\s+/g,j_=e=>e.type===4?e.content:e.loc.source,UA=e=>{const t=j_(e).trim().replace(BA,a=>a.trim());let r=0,n=[],i=0,s=0,o=null;for(let a=0;a<t.length;a++){const l=t.charAt(a);switch(r){case 0:if(l==="[")n.push(r),r=1,i++;else if(l==="(")n.push(r),r=2,s++;else if(!(a===0?q_:VA).test(l))return!1;break;case 1:l==="'"||l==='"'||l==="`"?(n.push(r),r=3,o=l):l==="["?i++:l==="]"&&(--i||(r=n.pop()));break;case 2:if(l==="'"||l==='"'||l==="`")n.push(r),r=3,o=l;else if(l==="(")s++;else if(l===")"){if(a===t.length-1)return!1;--s||(r=n.pop())}break;case 3:l===o&&(r=n.pop(),o=null);break}}return!i&&!s},W_=UA,zA=/^\s*(?:async\s*)?(?:\([^)]*?\)|[\w$_]+)\s*(?::[^=]+)?=>|^\s*(?:async\s+)?function(?:\s+[\w$]+)?\s*\(/,HA=e=>zA.test(j_(e)),qA=HA;function ur(e,t,r=!1){for(let n=0;n<e.props.length;n++){const i=e.props[n];if(i.type===7&&(r||i.exp)&&(ft(t)?i.name===t:t.test(i.name)))return i}}function Sc(e,t,r=!1,n=!1){for(let i=0;i<e.props.length;i++){const s=e.props[i];if(s.type===6){if(r)continue;if(s.name===t&&(s.value||n))return s}else if(s.name==="bind"&&(s.exp||n)&&mi(s.arg,t))return s}}function mi(e,t){return!!(e&&Vt(e)&&e.content===t)}function jA(e){return e.props.some(t=>t.type===7&&t.name==="bind"&&(!t.arg||t.arg.type!==4||!t.arg.isStatic))}function _u(e){return e.type===5||e.type===2}function Bm(e){return e.type===7&&e.name==="pre"}function WA(e){return e.type===7&&e.name==="slot"}function Pl(e){return e.type===1&&e.tagType===3}function Il(e){return e.type===1&&e.tagType===2}const KA=new Set([Fo,ea]);function K_(e,t=[]){if(e&&!ft(e)&&e.type===14){const r=e.callee;if(!ft(r)&&KA.has(r))return K_(e.arguments[0],t.concat(e))}return[e,t]}function Fl(e,t,r){let n,i=e.type===13?e.props:e.arguments[2],s=[],o;if(i&&!ft(i)&&i.type===14){const a=K_(i);i=a[0],s=a[1],o=s[s.length-1]}if(i==null||ft(i))n=dr([t]);else if(i.type===14){const a=i.arguments[0];!ft(a)&&a.type===15?Um(t,a)||a.properties.unshift(t):i.callee===Kf?n=st(r.helper(kl),[dr([t]),i]):i.arguments.unshift(dr([t])),!n&&(n=i)}else i.type===15?(Um(t,i)||i.properties.unshift(t),n=i):(n=st(r.helper(kl),[dr([t]),i]),o&&o.callee===ea&&(o=s[s.length-2]));e.type===13?o?o.arguments[0]=n:e.props=n:o?o.arguments[0]=n:e.arguments[2]=n}function Um(e,t){let r=!1;if(e.key.type===4){const n=e.key.content;r=t.properties.some(i=>i.key.type===4&&i.key.content===n)}return r}function Mo(e,t){return`_${t}_${e.replace(/[^\w]/g,(r,n)=>r==="-"?"_":e.charCodeAt(n).toString())}`}function GA(e){return e.type===14&&e.callee===Yf?e.arguments[1].returns:e}const YA=/([\s\S]*?)\s+(?:in|of)\s+(\S[\s\S]*)/;function G_(e){for(let t=0;t<e.length;t++)if(!Yt(e.charCodeAt(t)))return!1;return!0}function Zf(e){return e.type===2&&G_(e.content)||e.type===12&&Zf(e.content)}function Y_(e){return e.type===3||Zf(e)}const X_={parseMode:"base",ns:0,delimiters:["{{","}}"],getNamespace:()=>0,isVoidTag:La,isPreTag:La,isIgnoreNewlineTag:La,isCustomElement:La,onError:Jf,onWarn:z_,comments:!1,prefixIdentifiers:!1};let xe=X_,Do=null,gn="",Et=null,me=null,Rt="",rn=-1,fi=-1,ep=0,Un=!1,bh=null;const Ve=[],We=new DA(Ve,{onerr:en,ontext(e,t){Ra(mt(e,t),e,t)},ontextentity(e,t,r){Ra(e,t,r)},oninterpolation(e,t){if(Un)return Ra(mt(e,t),e,t);let r=e+We.delimiterOpen.length,n=t-We.delimiterClose.length;for(;Yt(gn.charCodeAt(r));)r++;for(;Yt(gn.charCodeAt(n-1));)n--;let i=mt(r,n);i.includes("&")&&(i=xe.decodeEntities(i,!1)),vh({type:5,content:Ja(i,!1,Ke(r,n)),loc:Ke(e,t)})},onopentagname(e,t){const r=mt(e,t);Et={type:1,tag:r,ns:xe.getNamespace(r,Ve[0],xe.ns),tagType:0,props:[],children:[],loc:Ke(e-1,t),codegenNode:void 0}},onopentagend(e){Hm(e)},onclosetag(e,t){const r=mt(e,t);if(!xe.isVoidTag(r)){let n=!1;for(let i=0;i<Ve.length;i++)if(Ve[i].tag.toLowerCase()===r.toLowerCase()){n=!0,i>0&&en(24,Ve[0].loc.start.offset);for(let o=0;o<=i;o++){const a=Ve.shift();Xa(a,t,o<i)}break}n||en(23,J_(e,60))}},onselfclosingtag(e){const t=Et.tag;Et.isSelfClosing=!0,Hm(e),Ve[0]&&Ve[0].tag===t&&Xa(Ve.shift(),e)},onattribname(e,t){me={type:6,name:mt(e,t),nameLoc:Ke(e,t),value:void 0,loc:Ke(e)}},ondirname(e,t){const r=mt(e,t),n=r==="."||r===":"?"bind":r==="@"?"on":r==="#"?"slot":r.slice(2);if(!Un&&n===""&&en(26,e),Un||n==="")me={type:6,name:r,nameLoc:Ke(e,t),value:void 0,loc:Ke(e)};else if(me={type:7,name:n,rawName:r,exp:void 0,arg:void 0,modifiers:r==="."?[ue("prop")]:[],loc:Ke(e)},n==="pre"){Un=We.inVPre=!0,bh=Et;const i=Et.props;for(let s=0;s<i.length;s++)i[s].type===7&&(i[s]=sT(i[s]))}},ondirarg(e,t){if(e===t)return;const r=mt(e,t);if(Un&&!Bm(me))me.name+=r,gi(me.nameLoc,t);else{const n=r[0]!=="[";me.arg=Ja(n?r:r.slice(1,-1),n,Ke(e,t),n?3:0)}},ondirmodifier(e,t){const r=mt(e,t);if(Un&&!Bm(me))me.name+="."+r,gi(me.nameLoc,t);else if(me.name==="slot"){const n=me.arg;n&&(n.content+="."+r,gi(n.loc,t))}else{const n=ue(r,!0,Ke(e,t));me.modifiers.push(n)}},onattribdata(e,t){Rt+=mt(e,t),rn<0&&(rn=e),fi=t},onattribentity(e,t,r){Rt+=e,rn<0&&(rn=t),fi=r},onattribnameend(e){const t=me.loc.start.offset,r=mt(t,e);me.type===7&&(me.rawName=r),Et.props.some(n=>(n.type===7?n.rawName:n.name)===r)&&en(2,t)},onattribend(e,t){if(Et&&me){if(gi(me.loc,t),e!==0)if(Rt.includes("&")&&(Rt=xe.decodeEntities(Rt,!0)),me.type===6)me.name==="class"&&(Rt=Z_(Rt).trim()),e===1&&!Rt&&en(13,t),me.value={type:2,content:Rt,loc:e===1?Ke(rn,fi):Ke(rn-1,fi+1)},We.inSFCRoot&&Et.tag==="template"&&me.name==="lang"&&Rt&&Rt!=="html"&&We.enterRCDATA(Nl("</template"),0);else{let r=0;me.exp=Ja(Rt,!1,Ke(rn,fi),0,r),me.name==="for"&&(me.forParseResult=JA(me.exp));let n=-1;me.name==="bind"&&(n=me.modifiers.findIndex(i=>i.content==="sync"))>-1&&Ro("COMPILER_V_BIND_SYNC",xe,me.loc,me.arg.loc.source)&&(me.name="model",me.modifiers.splice(n,1))}(me.type!==7||me.name!=="pre")&&Et.props.push(me)}Rt="",rn=fi=-1},oncomment(e,t){xe.comments&&vh({type:3,content:mt(e,t),loc:Ke(e-4,t+3)})},onend(){const e=gn.length;for(let t=0;t<Ve.length;t++)Xa(Ve[t],e-1),en(24,Ve[t].loc.start.offset)},oncdata(e,t){Ve[0].ns!==0?Ra(mt(e,t),e,t):en(1,e-9)},onprocessinginstruction(e){(Ve[0]?Ve[0].ns:xe.ns)===0&&en(21,e-1)}}),zm=/,([^,\}\]]*)(?:,([^,\}\]]*))?$/,XA=/^\(|\)$/g;function JA(e){const t=e.loc,r=e.content,n=r.match(YA);if(!n)return;const[,i,s]=n,o=(d,p,m=!1)=>{const h=t.start.offset+p,f=h+d.length;return Ja(d,!1,Ke(h,f),0,m?1:0)},a={source:o(s.trim(),r.indexOf(s,i.length)),value:void 0,key:void 0,index:void 0,finalized:!1};let l=i.trim().replace(XA,"").trim();const u=i.indexOf(l),c=l.match(zm);if(c){l=l.replace(zm,"").trim();const d=c[1].trim();let p;if(d&&(p=r.indexOf(d,u+l.length),a.key=o(d,p,!0)),c[2]){const m=c[2].trim();m&&(a.index=o(m,r.indexOf(m,a.key?p+d.length:u+l.length),!0))}}return l&&(a.value=o(l,u,!0)),a}function mt(e,t){return gn.slice(e,t)}function Hm(e){We.inSFCRoot&&(Et.innerLoc=Ke(e+1,e+1)),vh(Et);const{tag:t,ns:r}=Et;r===0&&xe.isPreTag(t)&&ep++,xe.isVoidTag(t)?Xa(Et,e):(Ve.unshift(Et),(r===1||r===2)&&(We.inXML=!0)),Et=null}function Ra(e,t,r){{const s=Ve[0]&&Ve[0].tag;s!=="script"&&s!=="style"&&e.includes("&")&&(e=xe.decodeEntities(e,!1))}const n=Ve[0]||Do,i=n.children[n.children.length-1];i&&i.type===2?(i.content+=e,gi(i.loc,r)):n.children.push({type:2,content:e,loc:Ke(t,r)})}function Xa(e,t,r=!1){r?gi(e.loc,J_(t,60)):gi(e.loc,QA(t,62)+1),We.inSFCRoot&&(e.children.length?e.innerLoc.end=mn({},e.children[e.children.length-1].loc.end):e.innerLoc.end=mn({},e.innerLoc.start),e.innerLoc.source=mt(e.innerLoc.start.offset,e.innerLoc.end.offset));const{tag:n,ns:i,children:s}=e;if(Un||(n==="slot"?e.tagType=2:qm(e)?e.tagType=3:eT(e)&&(e.tagType=1)),We.inRCDATA||(e.children=Q_(s)),i===0&&xe.isIgnoreNewlineTag(n)){const o=s[0];o&&o.type===2&&(o.content=o.content.replace(/^\r?\n/,""))}i===0&&xe.isPreTag(n)&&ep--,bh===e&&(Un=We.inVPre=!1,bh=null),We.inXML&&(Ve[0]?Ve[0].ns:xe.ns)===0&&(We.inXML=!1);{const o=e.props;if(!We.inSFCRoot&&Ci("COMPILER_NATIVE_TEMPLATE",xe)&&e.tag==="template"&&!qm(e)){const l=Ve[0]||Do,u=l.children.indexOf(e);l.children.splice(u,1,...e.children)}const a=o.find(l=>l.type===6&&l.name==="inline-template");a&&Ro("COMPILER_INLINE_TEMPLATE",xe,a.loc)&&e.children.length&&(a.value={type:2,content:mt(e.children[0].loc.start.offset,e.children[e.children.length-1].loc.end.offset),loc:a.loc})}}function QA(e,t){let r=e;for(;gn.charCodeAt(r)!==t&&r<gn.length-1;)r++;return r}function J_(e,t){let r=e;for(;gn.charCodeAt(r)!==t&&r>=0;)r--;return r}const ZA=new Set(["if","else","else-if","for","slot"]);function qm({tag:e,props:t}){if(e==="template"){for(let r=0;r<t.length;r++)if(t[r].type===7&&ZA.has(t[r].name))return!0}return!1}function eT({tag:e,props:t}){if(xe.isCustomElement(e))return!1;if(e==="component"||tT(e.charCodeAt(0))||H_(e)||xe.isBuiltInComponent&&xe.isBuiltInComponent(e)||xe.isNativeTag&&!xe.isNativeTag(e))return!0;for(let r=0;r<t.length;r++){const n=t[r];if(n.type===6){if(n.name==="is"&&n.value){if(n.value.content.startsWith("vue:"))return!0;if(Ro("COMPILER_IS_ON_ELEMENT",xe,n.loc))return!0}}else if(n.name==="bind"&&mi(n.arg,"is")&&Ro("COMPILER_IS_ON_ELEMENT",xe,n.loc))return!0}return!1}function tT(e){return e>64&&e<91}const rT=/\r\n/g;function Q_(e){const t=xe.whitespace!=="preserve";let r=!1;for(let n=0;n<e.length;n++){const i=e[n];if(i.type===2)if(ep)i.content=i.content.replace(rT,`
`);else if(G_(i.content)){const s=e[n-1]&&e[n-1].type,o=e[n+1]&&e[n+1].type;!s||!o||t&&(s===3&&(o===3||o===1)||s===1&&(o===3||o===1&&nT(i.content)))?(r=!0,e[n]=null):i.content=" "}else t&&(i.content=Z_(i.content))}return r?e.filter(Boolean):e}function nT(e){for(let t=0;t<e.length;t++){const r=e.charCodeAt(t);if(r===10||r===13)return!0}return!1}function Z_(e){let t="",r=!1;for(let n=0;n<e.length;n++)Yt(e.charCodeAt(n))?r||(t+=" ",r=!0):(t+=e[n],r=!1);return t}function vh(e){(Ve[0]||Do).children.push(e)}function Ke(e,t){return{start:We.getPos(e),end:t==null?t:We.getPos(t),source:t==null?t:mt(e,t)}}function iT(e){return Ke(e.start.offset,e.end.offset)}function gi(e,t){e.end=We.getPos(t),e.source=mt(e.start.offset,t)}function sT(e){const t={type:6,name:e.rawName,nameLoc:Ke(e.loc.start.offset,e.loc.start.offset+e.rawName.length),value:void 0,loc:e.loc};if(e.exp){const r=e.exp.loc;r.end.offset<e.loc.end.offset&&(r.start.offset--,r.start.column--,r.end.offset++,r.end.column++),t.value={type:2,content:e.exp.content,loc:r}}return t}function Ja(e,t=!1,r,n=0,i=0){return ue(e,t,r,n)}function en(e,t,r){xe.onError($e(e,Ke(t,t)))}function oT(){We.reset(),Et=null,me=null,Rt="",rn=-1,fi=-1,Ve.length=0}function aT(e,t){if(oT(),gn=e,xe=mn({},X_),t){let i;for(i in t)t[i]!=null&&(xe[i]=t[i])}We.mode=xe.parseMode==="html"?1:xe.parseMode==="sfc"?2:0,We.inXML=xe.ns===1||xe.ns===2;const r=t&&t.delimiters;r&&(We.delimiterOpen=Nl(r[0]),We.delimiterClose=Nl(r[1]));const n=Do=LA([],e);return We.parse(gn),n.loc=Ke(0,e.length),n.children=Q_(n.children),Do=null,n}function lT(e,t){Qa(e,void 0,t,!!ew(e))}function ew(e){const t=e.children.filter(r=>r.type!==3);return t.length===1&&t[0].type===1&&!Il(t[0])?t[0]:null}function Qa(e,t,r,n=!1,i=!1){const{children:s}=e,o=[];for(let c=0;c<s.length;c++){const d=s[c];if(d.type===1&&d.tagType===0){const p=n?0:Qt(d,r);if(p>0){if(p>=2){d.codegenNode.patchFlag=-1,o.push(d);continue}}else{const m=d.codegenNode;if(m.type===13){const h=m.patchFlag;if((h===void 0||h===512||h===1)&&rw(d,r)>=2){const f=nw(d);f&&(m.props=r.hoist(f))}m.dynamicProps&&(m.dynamicProps=r.hoist(m.dynamicProps))}}}else if(d.type===12&&(n?0:Qt(d,r))>=2){d.codegenNode.type===14&&d.codegenNode.arguments.length>0&&d.codegenNode.arguments.push("-1"),o.push(d);continue}if(d.type===1){const p=d.tagType===1;p&&r.scopes.vSlot++,Qa(d,e,r,!1,i),p&&r.scopes.vSlot--}else if(d.type===11)Qa(d,e,r,d.children.length===1,!0);else if(d.type===9)for(let p=0;p<d.branches.length;p++)Qa(d.branches[p],e,r,d.branches[p].children.length===1,i)}let a=!1;if(o.length===s.length&&e.type===1){if(e.tagType===0&&e.codegenNode&&e.codegenNode.type===13&&qn(e.codegenNode.children))e.codegenNode.children=l(Si(e.codegenNode.children)),a=!0;else if(e.tagType===1&&e.codegenNode&&e.codegenNode.type===13&&e.codegenNode.children&&!qn(e.codegenNode.children)&&e.codegenNode.children.type===15){const c=u(e.codegenNode,"default");c&&(c.returns=l(Si(c.returns)),a=!0)}else if(e.tagType===3&&t&&t.type===1&&t.tagType===1&&t.codegenNode&&t.codegenNode.type===13&&t.codegenNode.children&&!qn(t.codegenNode.children)&&t.codegenNode.children.type===15){const c=ur(e,"slot",!0),d=c&&c.arg&&u(t.codegenNode,c.arg);d&&(d.returns=l(Si(d.returns)),a=!0)}}if(!a)for(const c of o)c.codegenNode=r.cache(c.codegenNode);function l(c){const d=r.cache(c);return d.needArraySpread=!0,d}function u(c,d){if(c.children&&!qn(c.children)&&c.children.type===15){const p=c.children.properties.find(m=>m.key===d||m.key.content===d);return p&&p.value}}o.length&&r.transformHoist&&r.transformHoist(s,r,e)}function Qt(e,t){const{constantCache:r}=t;switch(e.type){case 1:if(e.tagType!==0)return 0;const n=r.get(e);if(n!==void 0)return n;const i=e.codegenNode;if(i.type!==13||i.isBlock&&e.tag!=="svg"&&e.tag!=="foreignObject"&&e.tag!=="math")return 0;if(i.patchFlag===void 0){let o=3;const a=rw(e,t);if(a===0)return r.set(e,0),0;a<o&&(o=a);for(let l=0;l<e.children.length;l++){const u=Qt(e.children[l],t);if(u===0)return r.set(e,0),0;u<o&&(o=u)}if(o>1)for(let l=0;l<e.props.length;l++){const u=e.props[l];if(u.type===7&&u.name==="bind"&&u.exp){const c=Qt(u.exp,t);if(c===0)return r.set(e,0),0;c<o&&(o=c)}}if(i.isBlock){for(let l=0;l<e.props.length;l++)if(e.props[l].type===7)return r.set(e,0),0;t.removeHelper(Ni),t.removeHelper(ps(t.inSSR,i.isComponent)),i.isBlock=!1,t.helper(fs(t.inSSR,i.isComponent))}return r.set(e,o),o}else return r.set(e,0),0;case 2:case 3:return 3;case 9:case 11:case 10:return 0;case 5:case 12:return Qt(e.content,t);case 4:return e.constType;case 8:let s=3;for(let o=0;o<e.children.length;o++){const a=e.children[o];if(ft(a)||If(a))continue;const l=Qt(a,t);if(l===0)return 0;l<s&&(s=l)}return s;case 20:return 2;default:return 0}}const cT=new Set([jf,Wf,Fo,ea]);function tw(e,t){if(e.type===14&&!ft(e.callee)&&cT.has(e.callee)){const r=e.arguments[0];if(r.type===4)return Qt(r,t);if(r.type===14)return tw(r,t)}return 0}function rw(e,t){let r=3;const n=nw(e);if(n&&n.type===15){const{properties:i}=n;for(let s=0;s<i.length;s++){const{key:o,value:a}=i[s],l=Qt(o,t);if(l===0)return l;l<r&&(r=l);let u;if(a.type===4?u=Qt(a,t):a.type===14?u=tw(a,t):u=0,u===0)return u;u<r&&(r=u)}}return r}function nw(e){const t=e.codegenNode;if(t.type===13)return t.props}function uT(e,{filename:t="",prefixIdentifiers:r=!1,hoistStatic:n=!1,hmr:i=!1,cacheHandlers:s=!1,nodeTransforms:o=[],directiveTransforms:a={},transformHoist:l=null,isBuiltInComponent:u=ho,isCustomElement:c=ho,expressionPlugins:d=[],scopeId:p=null,slotted:m=!0,ssr:h=!1,inSSR:f=!1,ssrCssVars:b="",bindingMetadata:y=uA,inline:w=!1,isTS:v=!1,onError:_=Jf,onWarn:x=z_,compatConfig:k}){const S=t.replace(/\?.*$/,"").match(/([^/\\]+)\.\w+$/),N={filename:t,selfName:S&&Lf(Yn(S[1])),prefixIdentifiers:r,hoistStatic:n,hmr:i,cacheHandlers:s,nodeTransforms:o,directiveTransforms:a,transformHoist:l,isBuiltInComponent:u,isCustomElement:c,expressionPlugins:d,scopeId:p,slotted:m,ssr:h,inSSR:f,ssrCssVars:b,bindingMetadata:y,inline:w,isTS:v,onError:_,onWarn:x,compatConfig:k,root:e,helpers:new Map,components:new Set,directives:new Set,hoists:[],imports:[],cached:[],constantCache:new WeakMap,temps:0,identifiers:Object.create(null),scopes:{vFor:0,vSlot:0,vPre:0,vOnce:0},parent:null,grandParent:null,currentNode:e,childIndex:0,inVOnce:!1,helper(A){const T=N.helpers.get(A)||0;return N.helpers.set(A,T+1),A},removeHelper(A){const T=N.helpers.get(A);if(T){const I=T-1;I?N.helpers.set(A,I):N.helpers.delete(A)}},helperString(A){return`_${ds[N.helper(A)]}`},replaceNode(A){N.parent.children[N.childIndex]=N.currentNode=A},removeNode(A){const T=N.parent.children,I=A?T.indexOf(A):N.currentNode?N.childIndex:-1;!A||A===N.currentNode?(N.currentNode=null,N.onNodeRemoved()):N.childIndex>I&&(N.childIndex--,N.onNodeRemoved()),N.parent.children.splice(I,1)},onNodeRemoved:ho,addIdentifiers(A){},removeIdentifiers(A){},hoist(A){ft(A)&&(A=ue(A)),N.hoists.push(A);const T=ue(`_hoisted_${N.hoists.length}`,!1,A.loc,2);return T.hoisted=A,T},cache(A,T=!1,I=!1){const C=RA(N.cached.length,A,T,I);return N.cached.push(C),C}};return N.filters=new Set,N}function dT(e,t){const r=uT(e,t);Cc(e,r),t.hoistStatic&&lT(e,r),t.ssr||hT(e,r),e.helpers=new Set([...r.helpers.keys()]),e.components=[...r.components],e.directives=[...r.directives],e.imports=r.imports,e.hoists=r.hoists,e.temps=r.temps,e.cached=r.cached,e.transformed=!0,e.filters=[...r.filters]}function hT(e,t){const{helper:r}=t,{children:n}=e;if(n.length===1){const i=ew(e);if(i&&i.codegenNode){const s=i.codegenNode;s.type===13&&Xf(s,t),e.codegenNode=s}else e.codegenNode=n[0]}else if(n.length>1){let i=64;e.codegenNode=Lo(t,r(Io),void 0,e.children,i,void 0,void 0,!0,void 0,!1)}}function fT(e,t){let r=0;const n=()=>{r--};for(;r<e.children.length;r++){const i=e.children[r];ft(i)||(t.grandParent=t.parent,t.parent=e,t.childIndex=r,t.onNodeRemoved=n,Cc(i,t))}}function Cc(e,t){t.currentNode=e;const{nodeTransforms:r}=t,n=[];for(let s=0;s<r.length;s++){const o=r[s](e,t);if(o&&(qn(o)?n.push(...o):n.push(o)),t.currentNode)e=t.currentNode;else return}switch(e.type){case 3:t.ssr||t.helper(Zo);break;case 5:t.ssr||t.helper(xc);break;case 9:for(let s=0;s<e.branches.length;s++)Cc(e.branches[s],t);break;case 10:case 11:case 1:case 0:fT(e,t);break}t.currentNode=e;let i=n.length;for(;i--;)n[i]()}function iw(e,t){const r=ft(e)?n=>n===e:n=>e.test(n);return(n,i)=>{if(n.type===1){const{props:s}=n;if(n.tagType===3&&s.some(WA))return;const o=[];for(let a=0;a<s.length;a++){const l=s[a];if(l.type===7&&r(l.name)){s.splice(a,1),a--;const u=t(n,l,i);u&&o.push(u)}}return o}}}const Ac="/*@__PURE__*/",sw=e=>`${ds[e]}: _${ds[e]}`;function pT(e,{mode:t="function",prefixIdentifiers:r=t==="module",sourceMap:n=!1,filename:i="template.vue.html",scopeId:s=null,optimizeImports:o=!1,runtimeGlobalName:a="Vue",runtimeModuleName:l="vue",ssrRuntimeModuleName:u="vue/server-renderer",ssr:c=!1,isTS:d=!1,inSSR:p=!1}){const m={mode:t,prefixIdentifiers:r,sourceMap:n,filename:i,scopeId:s,optimizeImports:o,runtimeGlobalName:a,runtimeModuleName:l,ssrRuntimeModuleName:u,ssr:c,isTS:d,inSSR:p,source:e.source,code:"",column:1,line:1,offset:0,indentLevel:0,pure:!1,map:void 0,helper(f){return`_${ds[f]}`},push(f,b=-2,y){m.code+=f},indent(){h(++m.indentLevel)},deindent(f=!1){f?--m.indentLevel:h(--m.indentLevel)},newline(){h(m.indentLevel)}};function h(f){m.push(`
`+"  ".repeat(f),0)}return m}function mT(e,t={}){const r=pT(e,t);t.onContextCreated&&t.onContextCreated(r);const{mode:n,push:i,prefixIdentifiers:s,indent:o,deindent:a,newline:l,scopeId:u,ssr:c}=r,d=Array.from(e.helpers),p=d.length>0,m=!s&&n!=="module";gT(e,r);const f=c?"ssrRender":"render",y=(c?["_ctx","_push","_parent","_attrs"]:["_ctx","_cache"]).join(", ");if(i(`function ${f}(${y}) {`),o(),m&&(i("with (_ctx) {"),o(),p&&(i(`const { ${d.map(sw).join(", ")} } = _Vue
`,-1),l())),e.components.length&&(wu(e.components,"component",r),(e.directives.length||e.temps>0)&&l()),e.directives.length&&(wu(e.directives,"directive",r),e.temps>0&&l()),e.filters&&e.filters.length&&(l(),wu(e.filters,"filter",r),l()),e.temps>0){i("let ");for(let w=0;w<e.temps;w++)i(`${w>0?", ":""}_temp${w}`)}return(e.components.length||e.directives.length||e.temps)&&(i(`
`,0),l()),c||i("return "),e.codegenNode?At(e.codegenNode,r):i("null"),m&&(a(),i("}")),a(),i("}"),{ast:e,code:r.code,preamble:"",map:r.map?r.map.toJSON():void 0}}function gT(e,t){const{ssr:r,prefixIdentifiers:n,push:i,newline:s,runtimeModuleName:o,runtimeGlobalName:a,ssrRuntimeModuleName:l}=t,u=a,c=Array.from(e.helpers);if(c.length>0&&(i(`const _Vue = ${u}
`,-1),e.hoists.length)){const d=[Mf,Df,Zo,$f,$_].filter(p=>c.includes(p)).map(sw).join(", ");i(`const { ${d} } = _Vue
`,-1)}bT(e.hoists,t),s(),i("return ")}function wu(e,t,{helper:r,push:n,newline:i,isTS:s}){const o=r(t==="filter"?zf:t==="component"?Vf:Uf);for(let a=0;a<e.length;a++){let l=e[a];const u=l.endsWith("__self");u&&(l=l.slice(0,-6)),n(`const ${Mo(l,t)} = ${o}(${JSON.stringify(l)}${u?", true":""})${s?"!":""}`),a<e.length-1&&i()}}function bT(e,t){if(!e.length)return;t.pure=!0;const{push:r,newline:n}=t;n();for(let i=0;i<e.length;i++){const s=e[i];s&&(r(`const _hoisted_${i+1} = `),At(s,t),n())}t.pure=!1}function tp(e,t){const r=e.length>3||!1;t.push("["),r&&t.indent(),ta(e,t,r),r&&t.deindent(),t.push("]")}function ta(e,t,r=!1,n=!0){const{push:i,newline:s}=t;for(let o=0;o<e.length;o++){const a=e[o];ft(a)?i(a,-3):qn(a)?tp(a,t):At(a,t),o<e.length-1&&(r?(n&&i(","),s()):n&&i(", "))}}function At(e,t){if(ft(e)){t.push(e,-3);return}if(If(e)){t.push(t.helper(e));return}switch(e.type){case 1:case 9:case 11:At(e.codegenNode,t);break;case 2:vT(e,t);break;case 4:ow(e,t);break;case 5:yT(e,t);break;case 12:At(e.codegenNode,t);break;case 8:aw(e,t);break;case 3:wT(e,t);break;case 13:ET(e,t);break;case 14:ST(e,t);break;case 15:CT(e,t);break;case 17:AT(e,t);break;case 18:TT(e,t);break;case 19:kT(e,t);break;case 20:OT(e,t);break;case 21:ta(e.body,t,!0,!1);break}}function vT(e,t){t.push(JSON.stringify(e.content),-3,e)}function ow(e,t){const{content:r,isStatic:n}=e;t.push(n?JSON.stringify(r):r,-3,e)}function yT(e,t){const{push:r,helper:n,pure:i}=t;i&&r(Ac),r(`${n(xc)}(`),At(e.content,t),r(")")}function aw(e,t){for(let r=0;r<e.children.length;r++){const n=e.children[r];ft(n)?t.push(n,-3):At(n,t)}}function _T(e,t){const{push:r}=t;if(e.type===8)r("["),aw(e,t),r("]");else if(e.isStatic){const n=Qf(e.content)?e.content:JSON.stringify(e.content);r(n,-2,e)}else r(`[${e.content}]`,-3,e)}function wT(e,t){const{push:r,helper:n,pure:i}=t;i&&r(Ac),r(`${n(Zo)}(${JSON.stringify(e.content)})`,-3,e)}function ET(e,t){const{push:r,helper:n,pure:i}=t,{tag:s,props:o,children:a,patchFlag:l,dynamicProps:u,directives:c,isBlock:d,disableTracking:p,isComponent:m}=e;let h;l&&(h=String(l)),c&&r(n(Hf)+"("),d&&r(`(${n(Ni)}(${p?"true":""}), `),i&&r(Ac);const f=d?ps(t.inSSR,m):fs(t.inSSR,m);r(n(f)+"(",-2,e),ta(xT([s,o,a,h,u]),t),r(")"),d&&r(")"),c&&(r(", "),At(c,t),r(")"))}function xT(e){let t=e.length;for(;t--&&e[t]==null;);return e.slice(0,t+1).map(r=>r||"null")}function ST(e,t){const{push:r,helper:n,pure:i}=t,s=ft(e.callee)?e.callee:n(e.callee);i&&r(Ac),r(s+"(",-2,e),ta(e.arguments,t),r(")")}function CT(e,t){const{push:r,indent:n,deindent:i,newline:s}=t,{properties:o}=e;if(!o.length){r("{}",-2,e);return}const a=o.length>1||!1;r(a?"{":"{ "),a&&n();for(let l=0;l<o.length;l++){const{key:u,value:c}=o[l];_T(u,t),r(": "),At(c,t),l<o.length-1&&(r(","),s())}a&&i(),r(a?"}":" }")}function AT(e,t){tp(e.elements,t)}function TT(e,t){const{push:r,indent:n,deindent:i}=t,{params:s,returns:o,body:a,newline:l,isSlot:u}=e;u&&r(`_${ds[Gf]}(`),r("(",-2,e),qn(s)?ta(s,t):s&&At(s,t),r(") => "),(l||a)&&(r("{"),n()),o?(l&&r("return "),qn(o)?tp(o,t):At(o,t)):a&&At(a,t),(l||a)&&(i(),r("}")),u&&(e.isNonScopedSlot&&r(", undefined, true"),r(")"))}function kT(e,t){const{test:r,consequent:n,alternate:i,newline:s}=e,{push:o,indent:a,deindent:l,newline:u}=t;if(r.type===4){const d=!Qf(r.content);d&&o("("),ow(r,t),d&&o(")")}else o("("),At(r,t),o(")");s&&a(),t.indentLevel++,s||o(" "),o("? "),At(n,t),t.indentLevel--,s&&u(),s||o(" "),o(": ");const c=i.type===19;c||t.indentLevel++,At(i,t),c||t.indentLevel--,s&&l(!0)}function OT(e,t){const{push:r,helper:n,indent:i,deindent:s,newline:o}=t,{needPauseTracking:a,needArraySpread:l}=e;l&&r("[...("),r(`_cache[${e.index}] || (`),a&&(i(),r(`${n(Ol)}(-1`),e.inVOnce&&r(", true"),r("),"),o(),r("(")),r(`_cache[${e.index}] = `),At(e.value,t),a&&(r(`).cacheIndex = ${e.index},`),o(),r(`${n(Ol)}(1),`),o(),r(`_cache[${e.index}]`),s()),r(")"),l&&r(")]")}new RegExp("\\b"+"arguments,await,break,case,catch,class,const,continue,debugger,default,delete,do,else,export,extends,finally,for,function,if,import,let,new,return,super,switch,throw,try,var,void,while,with,yield".split(",").join("\\b|\\b")+"\\b");const NT=iw(/^(?:if|else|else-if)$/,(e,t,r)=>PT(e,t,r,(n,i,s)=>{const o=r.parent.children;let a=o.indexOf(n),l=0;for(;a-->=0;){const u=o[a];u&&u.type===9&&(l+=u.branches.length)}return()=>{if(s)n.codegenNode=Wm(i,l,r);else{const u=IT(n.codegenNode);u.alternate=Wm(i,l+n.branches.length-1,r)}}}));function PT(e,t,r,n){if(t.name!=="else"&&(!t.exp||!t.exp.content.trim())){const i=t.exp?t.exp.loc:e.loc;r.onError($e(28,t.loc)),t.exp=ue("true",!1,i)}if(t.name==="if"){const i=jm(e,t),s={type:9,loc:iT(e.loc),branches:[i]};if(r.replaceNode(s),n)return n(s,i,!0)}else{const i=r.parent.children;let s=i.indexOf(e);for(;s-->=-1;){const o=i[s];if(o&&Y_(o)){r.removeNode(o);continue}if(o&&o.type===9){(t.name==="else-if"||t.name==="else")&&o.branches[o.branches.length-1].condition===void 0&&r.onError($e(30,e.loc)),r.removeNode();const a=jm(e,t);o.branches.push(a);const l=n&&n(o,a,!1);Cc(a,r),l&&l(),r.currentNode=null}else r.onError($e(30,e.loc));break}}}function jm(e,t){const r=e.tagType===3;return{type:10,loc:e.loc,condition:t.name==="else"?void 0:t.exp,children:r&&!ur(e,"for")?e.children:[e],userKey:Sc(e,"key"),isTemplateIf:r}}function Wm(e,t,r){return e.condition?gh(e.condition,Km(e,t,r),st(r.helper(Zo),['""',"true"])):Km(e,t,r)}function Km(e,t,r){const{helper:n}=r,i=Qe("key",ue(`${t}`,!1,sr,2)),{children:s}=e,o=s[0];if(s.length!==1||o.type!==1)if(s.length===1&&o.type===11){const l=o.codegenNode;return Fl(l,i,r),l}else return Lo(r,n(Io),dr([i]),s,64,void 0,void 0,!0,!1,!1,e.loc);else{const l=o.codegenNode,u=GA(l);return u.type===13&&Xf(u,r),Fl(u,i,r),l}}function IT(e){for(;;)if(e.type===19)if(e.alternate.type===19)e=e.alternate;else return e;else e.type===20&&(e=e.value)}const FT=iw("for",(e,t,r)=>{const{helper:n,removeHelper:i}=r;return LT(e,t,r,s=>{const o=st(n(qf),[s.source]),a=Pl(e),l=ur(e,"memo"),u=Sc(e,"key",!1,!0);u&&u.type;let c=u&&(u.type===6?u.value?ue(u.value.content,!0):void 0:u.exp);const d=u&&c?Qe("key",c):null,p=s.source.type===4&&s.source.constType>0,m=p?64:u?128:256;return s.codegenNode=Lo(r,n(Io),void 0,o,m,void 0,void 0,!0,!p,!1,e.loc),()=>{let h;const{children:f}=s,b=f.length!==1||f[0].type!==1,y=Il(e)?e:a&&e.children.length===1&&Il(e.children[0])?e.children[0]:null;if(y?(h=y.codegenNode,a&&d&&Fl(h,d,r)):b?h=Lo(r,n(Io),d?dr([d]):void 0,e.children,64,void 0,void 0,!0,void 0,!1):(h=f[0].codegenNode,a&&d&&Fl(h,d,r),h.isBlock!==!p&&(h.isBlock?(i(Ni),i(ps(r.inSSR,h.isComponent))):i(fs(r.inSSR,h.isComponent))),h.isBlock=!p,h.isBlock?(n(Ni),n(ps(r.inSSR,h.isComponent))):n(fs(r.inSSR,h.isComponent))),l){const w=hs(yh(s.parseResult,[ue("_cached")]));w.body=MA([Tr(["const _memo = (",l.exp,")"]),Tr(["if (_cached",...c?[" && _cached.key === ",c]:[],` && ${r.helperString(U_)}(_cached, _memo)) return _cached`]),Tr(["const _item = ",h]),ue("_item.memo = _memo"),ue("return _item")]),o.arguments.push(w,ue("_cache"),ue(String(r.cached.length))),r.cached.push(null)}else o.arguments.push(hs(yh(s.parseResult),h,!0))}})});function LT(e,t,r,n){if(!t.exp){r.onError($e(31,t.loc));return}const i=t.forParseResult;if(!i){r.onError($e(32,t.loc));return}lw(i);const{addIdentifiers:s,removeIdentifiers:o,scopes:a}=r,{source:l,value:u,key:c,index:d}=i,p={type:11,loc:t.loc,source:l,valueAlias:u,keyAlias:c,objectIndexAlias:d,parseResult:i,children:Pl(e)?e.children:[e]};r.replaceNode(p),a.vFor++;const m=n&&n(p);return()=>{a.vFor--,m&&m()}}function lw(e,t){e.finalized||(e.finalized=!0)}function yh({value:e,key:t,index:r},n=[]){return RT([e,t,r,...n])}function RT(e){let t=e.length;for(;t--&&!e[t];);return e.slice(0,t+1).map((r,n)=>r||ue("_".repeat(n+1),!1))}const Gm=ue("undefined",!1),MT=(e,t)=>{if(e.type===1&&(e.tagType===1||e.tagType===3)){const r=ur(e,"slot");if(r)return r.exp,t.scopes.vSlot++,()=>{t.scopes.vSlot--}}},DT=(e,t,r,n)=>hs(e,r,!1,!0,r.length?r[0].loc:n);function $T(e,t,r=DT){t.helper(Gf);const{children:n,loc:i}=e,s=[],o=[];let a=t.scopes.vSlot>0||t.scopes.vFor>0;const l=ur(e,"slot",!0);if(l){const{arg:b,exp:y}=l;b&&!Vt(b)&&(a=!0),s.push(Qe(b||ue("default",!0),r(y,void 0,n,i)))}let u=!1,c=!1;const d=[],p=new Set;let m=0;for(let b=0;b<n.length;b++){const y=n[b];let w;if(!Pl(y)||!(w=ur(y,"slot",!0))){y.type!==3&&d.push(y);continue}if(l){t.onError($e(37,w.loc));break}u=!0;const{children:v,loc:_}=y,{arg:x=ue("default",!0),exp:k,loc:S}=w;let N;Vt(x)?N=x?x.content:"default":a=!0;const A=ur(y,"for"),T=r(k,A,v,_);let I,C;if(I=ur(y,"if"))a=!0,o.push(gh(I.exp,Ma(x,T,m++),Gm));else if(C=ur(y,/^else(?:-if)?$/,!0)){let g=b,L;for(;g--&&(L=n[g],!!Y_(L)););if(L&&Pl(L)&&ur(L,/^(?:else-)?if$/)){let B=o[o.length-1];for(;B.alternate.type===19;)B=B.alternate;B.alternate=C.exp?gh(C.exp,Ma(x,T,m++),Gm):Ma(x,T,m++)}else t.onError($e(30,C.loc))}else if(A){a=!0;const g=A.forParseResult;g?(lw(g),o.push(st(t.helper(qf),[g.source,hs(yh(g),Ma(x,T),!0)]))):t.onError($e(32,A.loc))}else{if(N){if(p.has(N)){t.onError($e(38,S));continue}p.add(N),N==="default"&&(c=!0)}s.push(Qe(x,T))}}if(!l){const b=(y,w)=>{const v=r(y,void 0,w,i);return t.compatConfig&&(v.isNonScopedSlot=!0),Qe("default",v)};u?d.length&&!d.every(Zf)&&(c?t.onError($e(39,d[0].loc)):s.push(b(void 0,d))):s.push(b(void 0,n))}const h=a?2:Za(e.children)?3:1;let f=dr(s.concat(Qe("_",ue(h+"",!1))),i);return o.length&&(f=st(t.helper(B_),[f,Si(o)])),{slots:f,hasDynamicSlots:a}}function Ma(e,t,r){const n=[Qe("name",e),Qe("fn",t)];return r!=null&&n.push(Qe("key",ue(String(r),!0))),dr(n)}function Za(e){for(let t=0;t<e.length;t++){const r=e[t];switch(r.type){case 1:if(r.tagType===2||Za(r.children))return!0;break;case 9:if(Za(r.branches))return!0;break;case 10:case 11:if(Za(r.children))return!0;break}}return!1}const cw=new WeakMap,VT=(e,t)=>function(){if(e=t.currentNode,!(e.type===1&&(e.tagType===0||e.tagType===1)))return;const{tag:n,props:i}=e,s=e.tagType===1;let o=s?BT(e,t):`"${n}"`;const a=dA(o)&&o.callee===Bf;let l,u,c=0,d,p,m,h=a||o===fo||o===Rf||!s&&(n==="svg"||n==="foreignObject"||n==="math");if(i.length>0){const f=uw(e,t,void 0,s,a);l=f.props,c=f.patchFlag,p=f.dynamicPropNames;const b=f.directives;m=b&&b.length?Si(b.map(y=>zT(y,t))):void 0,f.shouldUseBlock&&(h=!0)}if(e.children.length>0)if(o===Tl&&(h=!0,c|=1024),s&&o!==fo&&o!==Tl){const{slots:b,hasDynamicSlots:y}=$T(e,t);u=b,y&&(c|=1024)}else if(e.children.length===1&&o!==fo){const b=e.children[0],y=b.type,w=y===5||y===8;w&&Qt(b,t)===0&&(c|=1),w||y===2?u=b:u=e.children}else u=e.children;p&&p.length&&(d=HT(p)),e.codegenNode=Lo(t,o,l,u,c===0?void 0:c,d,m,!!h,!1,s,e.loc)};function BT(e,t,r=!1){let{tag:n}=e;const i=_h(n),s=Sc(e,"is",!1,!0);if(s)if(i||Ci("COMPILER_IS_ON_ELEMENT",t)){let a;if(s.type===6?a=s.value&&ue(s.value.content,!0):(a=s.exp,a||(a=ue("is",!1,s.arg.loc))),a)return st(t.helper(Bf),[a])}else s.type===6&&s.value.content.startsWith("vue:")&&(n=s.value.content.slice(4));const o=H_(n)||t.isBuiltInComponent(n);return o?(r||t.helper(o),o):(t.helper(Vf),t.components.add(n),Mo(n,"component"))}function uw(e,t,r=e.props,n,i,s=!1){const{tag:o,loc:a,children:l}=e;let u=[];const c=[],d=[],p=l.length>0;let m=!1,h=0,f=!1,b=!1,y=!1,w=!1,v=!1,_=!1;const x=[],k=T=>{u.length&&(c.push(dr(Ym(u),a)),u=[]),T&&c.push(T)},S=()=>{t.scopes.vFor>0&&u.push(Qe(ue("ref_for",!0),ue("true")))},N=({key:T,value:I})=>{if(Vt(T)){const C=T.content,g=L_(C);if(g&&(!n||i)&&C.toLowerCase()!=="onclick"&&C!=="onUpdate:modelValue"&&!Rm(C)&&(w=!0),g&&Rm(C)&&(_=!0),g&&I.type===14&&(I=I.arguments[0]),I.type===20||(I.type===4||I.type===8)&&Qt(I,t)>0)return;C==="ref"?f=!0:C==="class"?b=!0:C==="style"?y=!0:C!=="key"&&!x.includes(C)&&x.push(C),n&&(C==="class"||C==="style")&&!x.includes(C)&&x.push(C)}else v=!0};for(let T=0;T<r.length;T++){const I=r[T];if(I.type===6){const{loc:C,name:g,nameLoc:L,value:B}=I;let M=!0;if(g==="ref"&&(f=!0,S()),g==="is"&&(_h(o)||B&&B.content.startsWith("vue:")||Ci("COMPILER_IS_ON_ELEMENT",t)))continue;u.push(Qe(ue(g,!0,L),ue(B?B.content:"",M,B?B.loc:C)))}else{const{name:C,arg:g,exp:L,loc:B,modifiers:M}=I,z=C==="bind",P=C==="on";if(C==="slot"){n||t.onError($e(40,B));continue}if(C==="once"||C==="memo"||C==="is"||z&&mi(g,"is")&&(_h(o)||Ci("COMPILER_IS_ON_ELEMENT",t))||P&&s)continue;if((z&&mi(g,"key")||P&&p&&mi(g,"vue:before-update"))&&(m=!0),z&&mi(g,"ref")&&S(),!g&&(z||P)){if(v=!0,L)if(z){if(k(),Ci("COMPILER_V_BIND_OBJECT_ORDER",t)){c.unshift(L);continue}S(),k(),c.push(L)}else k({type:14,loc:B,callee:t.helper(Kf),arguments:n?[L]:[L,"true"]});else t.onError($e(z?34:35,B));continue}z&&M.some(be=>be.content==="prop")&&(h|=32);const Z=t.directiveTransforms[C];if(Z){const{props:be,needRuntime:le}=Z(I,e,t);!s&&be.forEach(N),P&&g&&!Vt(g)?k(dr(be,a)):u.push(...be),le&&(d.push(I),If(le)&&cw.set(I,le))}else hA(C)||(d.push(I),p&&(m=!0))}}let A;if(c.length?(k(),c.length>1?A=st(t.helper(kl),c,a):A=c[0]):u.length&&(A=dr(Ym(u),a)),v?h|=16:(b&&!n&&(h|=2),y&&!n&&(h|=4),x.length&&(h|=8),w&&(h|=32)),!m&&(h===0||h===32)&&(f||_||d.length>0)&&(h|=512),!t.inSSR&&A)switch(A.type){case 15:let T=-1,I=-1,C=!1;for(let B=0;B<A.properties.length;B++){const M=A.properties[B].key;Vt(M)?M.content==="class"?T=B:M.content==="style"&&(I=B):M.isHandlerKey||(C=!0)}const g=A.properties[T],L=A.properties[I];C?A=st(t.helper(Fo),[A]):(g&&!Vt(g.value)&&(g.value=st(t.helper(jf),[g.value])),L&&(y||L.value.type===4&&L.value.content.trim()[0]==="["||L.value.type===17)&&(L.value=st(t.helper(Wf),[L.value])));break;case 14:break;default:A=st(t.helper(Fo),[st(t.helper(ea),[A])]);break}return{props:A,directives:d,patchFlag:h,dynamicPropNames:x,shouldUseBlock:m}}function Ym(e){const t=new Map,r=[];for(let n=0;n<e.length;n++){const i=e[n];if(i.key.type===8||!i.key.isStatic){r.push(i);continue}const s=i.key.content,o=t.get(s);o?(s==="style"||s==="class"||L_(s))&&UT(o,i):(t.set(s,i),r.push(i))}return r}function UT(e,t){e.value.type===17?e.value.elements.push(t.value):e.value=Si([e.value,t.value],e.loc)}function zT(e,t){const r=[],n=cw.get(e);n?r.push(t.helperString(n)):(t.helper(Uf),t.directives.add(e.name),r.push(Mo(e.name,"directive")));const{loc:i}=e;if(e.exp&&r.push(e.exp),e.arg&&(e.exp||r.push("void 0"),r.push(e.arg)),Object.keys(e.modifiers).length){e.arg||(e.exp||r.push("void 0"),r.push("void 0"));const s=ue("true",!1,i);r.push(dr(e.modifiers.map(o=>Qe(o,s)),i))}return Si(r,e.loc)}function HT(e){let t="[";for(let r=0,n=e.length;r<n;r++)t+=JSON.stringify(e[r]),r<n-1&&(t+=", ");return t+"]"}function _h(e){return e==="component"||e==="Component"}const qT=(e,t)=>{if(Il(e)){const{children:r,loc:n}=e,{slotName:i,slotProps:s}=jT(e,t),o=[t.prefixIdentifiers?"_ctx.$slots":"$slots",i,"{}","undefined","true"];let a=2;s&&(o[2]=s,a=3),r.length&&(o[3]=hs([],r,!1,!1,n),a=4),t.scopeId&&!t.slotted&&(a=5),o.splice(a),e.codegenNode=st(t.helper(V_),o,n)}};function jT(e,t){let r='"default"',n;const i=[];for(let s=0;s<e.props.length;s++){const o=e.props[s];if(o.type===6)o.value&&(o.name==="name"?r=JSON.stringify(o.value.content):(o.name=Yn(o.name),i.push(o)));else if(o.name==="bind"&&mi(o.arg,"name")){if(o.exp)r=o.exp;else if(o.arg&&o.arg.type===4){const a=Yn(o.arg.content);r=o.exp=ue(a,!1,o.arg.loc)}}else o.name==="bind"&&o.arg&&Vt(o.arg)&&(o.arg.content=Yn(o.arg.content)),i.push(o)}if(i.length>0){const{props:s,directives:o}=uw(e,t,i,!1,!1);n=s,o.length&&t.onError($e(36,o[0].loc))}return{slotName:r,slotProps:n}}const dw=(e,t,r,n)=>{const{loc:i,modifiers:s,arg:o}=e;!e.exp&&!s.length&&r.onError($e(35,i));let a;if(o.type===4)if(o.isStatic){let d=o.content;d.startsWith("vue:")&&(d=`vnode-${d.slice(4)}`);const p=t.tagType!==0||d.startsWith("vnode")||!/[A-Z]/.test(d)?pA(Yn(d)):`on:${d}`;a=ue(p,!0,o.loc)}else a=Tr([`${r.helperString(mh)}(`,o,")"]);else a=o,a.children.unshift(`${r.helperString(mh)}(`),a.children.push(")");let l=e.exp;l&&!l.content.trim()&&(l=void 0);let u=r.cacheHandlers&&!l&&!r.inVOnce;if(l){const d=W_(l),p=!(d||qA(l)),m=l.content.includes(";");(p||u&&d)&&(l=Tr([`${p?"$event":"(...args)"} => ${m?"{":"("}`,l,m?"}":")"]))}let c={props:[Qe(a,l||ue("() => {}",!1,i))]};return n&&(c=n(c)),u&&(c.props[0].value=r.cache(c.props[0].value)),c.props.forEach(d=>d.key.isHandlerKey=!0),c},WT=(e,t,r)=>{const{modifiers:n,loc:i}=e,s=e.arg;let{exp:o}=e;return o&&o.type===4&&!o.content.trim()&&(o=void 0),s.type!==4?(s.children.unshift("("),s.children.push(') || ""')):s.isStatic||(s.content=s.content?`${s.content} || ""`:'""'),n.some(a=>a.content==="camel")&&(s.type===4?s.isStatic?s.content=Yn(s.content):s.content=`${r.helperString(ph)}(${s.content})`:(s.children.unshift(`${r.helperString(ph)}(`),s.children.push(")"))),r.inSSR||(n.some(a=>a.content==="prop")&&Xm(s,"."),n.some(a=>a.content==="attr")&&Xm(s,"^")),{props:[Qe(s,o)]}},Xm=(e,t)=>{e.type===4?e.isStatic?e.content=t+e.content:e.content=`\`${t}\${${e.content}}\``:(e.children.unshift(`'${t}' + (`),e.children.push(")"))},KT=(e,t)=>{if(e.type===0||e.type===1||e.type===11||e.type===10)return()=>{const r=e.children;let n,i=!1;for(let s=0;s<r.length;s++){const o=r[s];if(_u(o)){i=!0;for(let a=s+1;a<r.length;a++){const l=r[a];if(_u(l))n||(n=r[s]=Tr([o],o.loc)),n.children.push(" + ",l),r.splice(a,1),a--;else{n=void 0;break}}}}if(!(!i||r.length===1&&(e.type===0||e.type===1&&e.tagType===0&&!e.props.find(s=>s.type===7&&!t.directiveTransforms[s.name])&&e.tag!=="template")))for(let s=0;s<r.length;s++){const o=r[s];if(_u(o)||o.type===8){const a=[];(o.type!==2||o.content!==" ")&&a.push(o),!t.ssr&&Qt(o,t)===0&&a.push("1"),r[s]={type:12,content:o,loc:o.loc,codegenNode:st(t.helper($f),a)}}}}},Jm=new WeakSet,GT=(e,t)=>{if(e.type===1&&ur(e,"once",!0))return Jm.has(e)||t.inVOnce||t.inSSR?void 0:(Jm.add(e),t.inVOnce=!0,t.helper(Ol),()=>{t.inVOnce=!1;const r=t.currentNode;r.codegenNode&&(r.codegenNode=t.cache(r.codegenNode,!0,!0))})},hw=(e,t,r)=>{const{exp:n,arg:i}=e;if(!n)return r.onError($e(41,e.loc)),Vs();const s=n.loc.source.trim(),o=n.type===4?n.content:s,a=r.bindingMetadata[s];if(a==="props"||a==="props-aliased")return r.onError($e(44,n.loc)),Vs();if(a==="literal-const"||a==="setup-const")return r.onError($e(45,n.loc)),Vs();if(!o.trim()||!W_(n))return r.onError($e(42,n.loc)),Vs();const l=i||ue("modelValue",!0),u=i?Vt(i)?`onUpdate:${Yn(i.content)}`:Tr(['"onUpdate:" + ',i]):"onUpdate:modelValue";let c;const d=r.isTS?"($event: any)":"$event";c=Tr([`${d} => ((`,n,") = $event)"]);const p=[Qe(l,e.exp),Qe(u,c)];if(e.modifiers.length&&t.tagType===1){const m=e.modifiers.map(f=>f.content).map(f=>(Qf(f)?f:JSON.stringify(f))+": true").join(", "),h=i?Vt(i)?`${i.content}Modifiers`:Tr([i,' + "Modifiers"']):"modelModifiers";p.push(Qe(h,ue(`{ ${m} }`,!1,e.loc,2)))}return Vs(p)};function Vs(e=[]){return{props:e}}const YT=/[\w).+\-_$\]]/,XT=(e,t)=>{Ci("COMPILER_FILTERS",t)&&(e.type===5?Ll(e.content,t):e.type===1&&e.props.forEach(r=>{r.type===7&&r.name!=="for"&&r.exp&&Ll(r.exp,t)}))};function Ll(e,t){if(e.type===4)Qm(e,t);else for(let r=0;r<e.children.length;r++){const n=e.children[r];typeof n=="object"&&(n.type===4?Qm(n,t):n.type===8?Ll(e,t):n.type===5&&Ll(n.content,t))}}function Qm(e,t){const r=e.content;let n=!1,i=!1,s=!1,o=!1,a=0,l=0,u=0,c=0,d,p,m,h,f=[];for(m=0;m<r.length;m++)if(p=d,d=r.charCodeAt(m),n)d===39&&p!==92&&(n=!1);else if(i)d===34&&p!==92&&(i=!1);else if(s)d===96&&p!==92&&(s=!1);else if(o)d===47&&p!==92&&(o=!1);else if(d===124&&r.charCodeAt(m+1)!==124&&r.charCodeAt(m-1)!==124&&!a&&!l&&!u)h===void 0?(c=m+1,h=r.slice(0,m).trim()):b();else{switch(d){case 34:i=!0;break;case 39:n=!0;break;case 96:s=!0;break;case 40:u++;break;case 41:u--;break;case 91:l++;break;case 93:l--;break;case 123:a++;break;case 125:a--;break}if(d===47){let y=m-1,w;for(;y>=0&&(w=r.charAt(y),w===" ");y--);(!w||!YT.test(w))&&(o=!0)}}h===void 0?h=r.slice(0,m).trim():c!==0&&b();function b(){f.push(r.slice(c,m).trim()),c=m+1}if(f.length){for(m=0;m<f.length;m++)h=JT(h,f[m],t);e.content=h,e.ast=void 0}}function JT(e,t,r){r.helper(zf);const n=t.indexOf("(");if(n<0)return r.filters.add(t),`${Mo(t,"filter")}(${e})`;{const i=t.slice(0,n),s=t.slice(n+1);return r.filters.add(i),`${Mo(i,"filter")}(${e}${s!==")"?","+s:s}`}}const Zm=new WeakSet,QT=(e,t)=>{if(e.type===1){const r=ur(e,"memo");return!r||Zm.has(e)||t.inSSR?void 0:(Zm.add(e),()=>{const n=e.codegenNode||t.currentNode.codegenNode;n&&n.type===13&&(e.tagType!==1&&Xf(n,t),e.codegenNode=st(t.helper(Yf),[r.exp,hs(void 0,n),"_cache",String(t.cached.length)]),t.cached.push(null))})}},ZT=(e,t)=>{if(e.type===1){for(const r of e.props)if(r.type===7&&r.name==="bind"&&(!r.exp||r.exp.type===4&&!r.exp.content.trim())&&r.arg){const n=r.arg;if(n.type!==4||!n.isStatic)t.onError($e(53,n.loc)),r.exp=ue("",!0,n.loc);else{const i=Yn(n.content);(q_.test(i[0])||i[0]==="-")&&(r.exp=ue(i,!1,n.loc))}}}};function ek(e){return[[ZT,GT,NT,QT,FT,XT,qT,VT,MT,KT],{on:dw,bind:WT,model:hw}]}function tk(e,t={}){const r=t.onError||Jf,n=t.mode==="module";t.prefixIdentifiers===!0?r($e(48)):n&&r($e(49));const i=!1;t.cacheHandlers&&r($e(50)),t.scopeId&&!n&&r($e(51));const s=mn({},t,{prefixIdentifiers:i}),o=ft(e)?aT(e,s):e,[a,l]=ek();return dT(o,mn({},s,{nodeTransforms:[...a,...t.nodeTransforms||[]],directiveTransforms:mn({},l,t.directiveTransforms||{})})),mT(o,s)}const rk=()=>({props:[]});const fw=Symbol(""),pw=Symbol(""),mw=Symbol(""),gw=Symbol(""),wh=Symbol(""),bw=Symbol(""),vw=Symbol(""),yw=Symbol(""),_w=Symbol(""),ww=Symbol("");FA({[fw]:"vModelRadio",[pw]:"vModelCheckbox",[mw]:"vModelText",[gw]:"vModelSelect",[wh]:"vModelDynamic",[bw]:"withModifiers",[vw]:"withKeys",[yw]:"vShow",[_w]:"Transition",[ww]:"TransitionGroup"});let qi;function nk(e,t=!1){return qi||(qi=document.createElement("div")),t?(qi.innerHTML=`<div foo="${e.replace(/"/g,"&quot;")}">`,qi.children[0].getAttribute("foo")):(qi.innerHTML=e,qi.textContent)}const ik={parseMode:"html",isVoidTag:TA,isNativeTag:e=>SA(e)||CA(e)||AA(e),isPreTag:e=>e==="pre",isIgnoreNewlineTag:e=>e==="pre"||e==="textarea",decodeEntities:nk,isBuiltInComponent:e=>{if(e==="Transition"||e==="transition")return _w;if(e==="TransitionGroup"||e==="transition-group")return ww},getNamespace(e,t,r){let n=t?t.ns:r;if(t&&n===2)if(t.tag==="annotation-xml"){if(e==="svg")return 1;t.props.some(i=>i.type===6&&i.name==="encoding"&&i.value!=null&&(i.value.content==="text/html"||i.value.content==="application/xhtml+xml"))&&(n=0)}else/^m(?:[ions]|text)$/.test(t.tag)&&e!=="mglyph"&&e!=="malignmark"&&(n=0);else t&&n===1&&(t.tag==="foreignObject"||t.tag==="desc"||t.tag==="title")&&(n=0);if(n===0){if(e==="svg")return 1;if(e==="math")return 2}return n}},sk=e=>{e.type===1&&e.props.forEach((t,r)=>{t.type===6&&t.name==="style"&&t.value&&(e.props[r]={type:7,name:"bind",arg:ue("style",!0,t.loc),exp:ok(t.value.content,t.loc),modifiers:[],loc:t.loc})})},ok=(e,t)=>{const r=yA(e);return ue(JSON.stringify(r),!1,t,3)};function Xn(e,t){return $e(e,t)}const ak=(e,t,r)=>{const{exp:n,loc:i}=e;return n||r.onError(Xn(54,i)),t.children.length&&(r.onError(Xn(55,i)),t.children.length=0),{props:[Qe(ue("innerHTML",!0,i),n||ue("",!0))]}},lk=(e,t,r)=>{const{exp:n,loc:i}=e;return n||r.onError(Xn(56,i)),t.children.length&&(r.onError(Xn(57,i)),t.children.length=0),{props:[Qe(ue("textContent",!0),n?Qt(n,r)>0?n:st(r.helperString(xc),[n],i):ue("",!0))]}},ck=(e,t,r)=>{const n=hw(e,t,r);if(!n.props.length||t.tagType===1)return n;e.arg&&r.onError(Xn(59,e.arg.loc));const{tag:i}=t,s=r.isCustomElement(i);if(i==="input"||i==="textarea"||i==="select"||s){let o=mw,a=!1;if(i==="input"||s){const l=Sc(t,"type");if(l){if(l.type===7)o=wh;else if(l.value)switch(l.value.content){case"radio":o=fw;break;case"checkbox":o=pw;break;case"file":a=!0,r.onError(Xn(60,e.loc));break}}else jA(t)&&(o=wh)}else i==="select"&&(o=gw);a||(n.needRuntime=r.helper(o))}else r.onError(Xn(58,e.loc));return n.props=n.props.filter(o=>!(o.key.type===4&&o.key.content==="modelValue")),n},uk=Xr("passive,once,capture"),dk=Xr("stop,prevent,self,ctrl,shift,alt,meta,exact,middle"),hk=Xr("left,right"),Ew=Xr("onkeyup,onkeydown,onkeypress"),fk=(e,t,r,n)=>{const i=[],s=[],o=[];for(let a=0;a<t.length;a++){const l=t[a].content;l==="native"&&Ro("COMPILER_V_ON_NATIVE",r)||uk(l)?o.push(l):hk(l)?Vt(e)?Ew(e.content.toLowerCase())?i.push(l):s.push(l):(i.push(l),s.push(l)):dk(l)?s.push(l):i.push(l)}return{keyModifiers:i,nonKeyModifiers:s,eventOptionModifiers:o}},eg=(e,t)=>Vt(e)&&e.content.toLowerCase()==="onclick"?ue(t,!0):e.type!==4?Tr(["(",e,`) === "onClick" ? "${t}" : (`,e,")"]):e,pk=(e,t,r)=>dw(e,t,r,n=>{const{modifiers:i}=e;if(!i.length)return n;let{key:s,value:o}=n.props[0];const{keyModifiers:a,nonKeyModifiers:l,eventOptionModifiers:u}=fk(s,i,r,e.loc);if(l.includes("right")&&(s=eg(s,"onContextmenu")),l.includes("middle")&&(s=eg(s,"onMouseup")),l.length&&(o=st(r.helper(bw),[o,JSON.stringify(l)])),a.length&&(!Vt(s)||Ew(s.content.toLowerCase()))&&(o=st(r.helper(vw),[o,JSON.stringify(a)])),u.length){const c=u.map(Lf).join("");s=Vt(s)?ue(`${s.content}${c}`,!0):Tr(["(",s,`) + "${c}"`])}return{props:[Qe(s,o)]}}),mk=(e,t,r)=>{const{exp:n,loc:i}=e;return n||r.onError(Xn(62,i)),{props:[],needRuntime:r.helper(yw)}},gk=(e,t)=>{e.type===1&&e.tagType===0&&(e.tag==="script"||e.tag==="style")&&t.removeNode()},bk=[sk],vk={cloak:rk,html:ak,text:lk,model:ck,on:pk,show:mk};function yk(e,t={}){return tk(e,mn({},ik,t,{nodeTransforms:[gk,...bk,...t.nodeTransforms||[]],directiveTransforms:mn({},vk,t.directiveTransforms||{}),transformHoist:null}))}const tg=Object.create(null);function _k(e,t){if(!ft(e))if(e.nodeType)e=e.innerHTML;else return ho;const r=mA(e,t),n=tg[r];if(n)return n;if(e[0]==="#"){const a=document.querySelector(e);e=a?a.innerHTML:""}const i=mn({hoistStatic:!0,onError:void 0,onWarn:ho},t);!i.isCustomElement&&typeof customElements<"u"&&(i.isCustomElement=a=>!!customElements.get(a));const{code:s}=yk(e,i),o=new Function("Vue",s)(cA);return o._rc=!0,tg[r]=o}r_(_k);async function wk(e,t){for(const r of Array.isArray(e)?e:[e]){const n=t[r];if(!(typeof n>"u"))return typeof n=="function"?n():n}throw new Error(`Page not found: ${e}`)}var xw=typeof global=="object"&&global&&global.Object===Object&&global,Ek=typeof self=="object"&&self&&self.Object===Object&&self,Lr=xw||Ek||Function("return this")(),mr=Lr.Symbol,Sw=Object.prototype,xk=Sw.hasOwnProperty,Sk=Sw.toString,Bs=mr?mr.toStringTag:void 0;function Ck(e){var t=xk.call(e,Bs),r=e[Bs];try{e[Bs]=void 0;var n=!0}catch{}var i=Sk.call(e);return n&&(t?e[Bs]=r:delete e[Bs]),i}var Ak=Object.prototype,Tk=Ak.toString;function kk(e){return Tk.call(e)}var Ok="[object Null]",Nk="[object Undefined]",rg=mr?mr.toStringTag:void 0;function Mi(e){return e==null?e===void 0?Nk:Ok:rg&&rg in Object(e)?Ck(e):kk(e)}function Gr(e){return e!=null&&typeof e=="object"}var Pk="[object Symbol]";function Tc(e){return typeof e=="symbol"||Gr(e)&&Mi(e)==Pk}function Cw(e,t){for(var r=-1,n=e==null?0:e.length,i=Array(n);++r<n;)i[r]=t(e[r],r,e);return i}var gr=Array.isArray,ng=mr?mr.prototype:void 0,ig=ng?ng.toString:void 0;function Aw(e){if(typeof e=="string")return e;if(gr(e))return Cw(e,Aw)+"";if(Tc(e))return ig?ig.call(e):"";var t=e+"";return t=="0"&&1/e==-1/0?"-0":t}var Ik=/\s/;function Fk(e){for(var t=e.length;t--&&Ik.test(e.charAt(t)););return t}var Lk=/^\s+/;function Rk(e){return e&&e.slice(0,Fk(e)+1).replace(Lk,"")}function nr(e){var t=typeof e;return e!=null&&(t=="object"||t=="function")}var sg=NaN,Mk=/^[-+]0x[0-9a-f]+$/i,Dk=/^0b[01]+$/i,$k=/^0o[0-7]+$/i,Vk=parseInt;function og(e){if(typeof e=="number")return e;if(Tc(e))return sg;if(nr(e)){var t=typeof e.valueOf=="function"?e.valueOf():e;e=nr(t)?t+"":t}if(typeof e!="string")return e===0?e:+e;e=Rk(e);var r=Dk.test(e);return r||$k.test(e)?Vk(e.slice(2),r?2:8):Mk.test(e)?sg:+e}function Tw(e){return e}var Bk="[object AsyncFunction]",Uk="[object Function]",zk="[object GeneratorFunction]",Hk="[object Proxy]";function rp(e){if(!nr(e))return!1;var t=Mi(e);return t==Uk||t==zk||t==Bk||t==Hk}var Eu=Lr["__core-js_shared__"],ag=(function(){var e=/[^.]+$/.exec(Eu&&Eu.keys&&Eu.keys.IE_PROTO||"");return e?"Symbol(src)_1."+e:""})();function qk(e){return!!ag&&ag in e}var jk=Function.prototype,Wk=jk.toString;function Di(e){if(e!=null){try{return Wk.call(e)}catch{}try{return e+""}catch{}}return""}var Kk=/[\\^$.*+?()[\]{}|]/g,Gk=/^\[object .+?Constructor\]$/,Yk=Function.prototype,Xk=Object.prototype,Jk=Yk.toString,Qk=Xk.hasOwnProperty,Zk=RegExp("^"+Jk.call(Qk).replace(Kk,"\\$&").replace(/hasOwnProperty|(function).*?(?=\\\()| for .+?(?=\\\])/g,"$1.*?")+"$");function eO(e){if(!nr(e)||qk(e))return!1;var t=rp(e)?Zk:Gk;return t.test(Di(e))}function tO(e,t){return e?.[t]}function $i(e,t){var r=tO(e,t);return eO(r)?r:void 0}var Eh=$i(Lr,"WeakMap"),lg=Object.create,rO=(function(){function e(){}return function(t){if(!nr(t))return{};if(lg)return lg(t);e.prototype=t;var r=new e;return e.prototype=void 0,r}})();function nO(e,t,r){switch(r.length){case 0:return e.call(t);case 1:return e.call(t,r[0]);case 2:return e.call(t,r[0],r[1]);case 3:return e.call(t,r[0],r[1],r[2])}return e.apply(t,r)}function kw(e,t){var r=-1,n=e.length;for(t||(t=Array(n));++r<n;)t[r]=e[r];return t}var iO=800,sO=16,oO=Date.now;function aO(e){var t=0,r=0;return function(){var n=oO(),i=sO-(n-r);if(r=n,i>0){if(++t>=iO)return arguments[0]}else t=0;return e.apply(void 0,arguments)}}function lO(e){return function(){return e}}var Rl=(function(){try{var e=$i(Object,"defineProperty");return e({},"",{}),e}catch{}})(),cO=Rl?function(e,t){return Rl(e,"toString",{configurable:!0,enumerable:!1,value:lO(t),writable:!0})}:Tw,Ow=aO(cO);function uO(e,t){for(var r=-1,n=e==null?0:e.length;++r<n&&t(e[r],r,e)!==!1;);return e}var dO=9007199254740991,hO=/^(?:0|[1-9]\d*)$/;function kc(e,t){var r=typeof e;return t=t??dO,!!t&&(r=="number"||r!="symbol"&&hO.test(e))&&e>-1&&e%1==0&&e<t}function np(e,t,r){t=="__proto__"&&Rl?Rl(e,t,{configurable:!0,enumerable:!0,value:r,writable:!0}):e[t]=r}function ra(e,t){return e===t||e!==e&&t!==t}var fO=Object.prototype,pO=fO.hasOwnProperty;function ip(e,t,r){var n=e[t];(!(pO.call(e,t)&&ra(n,r))||r===void 0&&!(t in e))&&np(e,t,r)}function As(e,t,r,n){var i=!r;r||(r={});for(var s=-1,o=t.length;++s<o;){var a=t[s],l=void 0;l===void 0&&(l=e[a]),i?np(r,a,l):ip(r,a,l)}return r}var cg=Math.max;function Nw(e,t,r){return t=cg(t===void 0?e.length-1:t,0),function(){for(var n=arguments,i=-1,s=cg(n.length-t,0),o=Array(s);++i<s;)o[i]=n[t+i];i=-1;for(var a=Array(t+1);++i<t;)a[i]=n[i];return a[t]=r(o),nO(e,this,a)}}function mO(e,t){return Ow(Nw(e,t,Tw),e+"")}var gO=9007199254740991;function sp(e){return typeof e=="number"&&e>-1&&e%1==0&&e<=gO}function Oc(e){return e!=null&&sp(e.length)&&!rp(e)}function bO(e,t,r){if(!nr(r))return!1;var n=typeof t;return(n=="number"?Oc(r)&&kc(t,r.length):n=="string"&&t in r)?ra(r[t],e):!1}function vO(e){return mO(function(t,r){var n=-1,i=r.length,s=i>1?r[i-1]:void 0,o=i>2?r[2]:void 0;for(s=e.length>3&&typeof s=="function"?(i--,s):void 0,o&&bO(r[0],r[1],o)&&(s=i<3?void 0:s,i=1),t=Object(t);++n<i;){var a=r[n];a&&e(t,a,n,s)}return t})}var yO=Object.prototype;function op(e){var t=e&&e.constructor,r=typeof t=="function"&&t.prototype||yO;return e===r}function _O(e,t){for(var r=-1,n=Array(e);++r<e;)n[r]=t(r);return n}var wO="[object Arguments]";function ug(e){return Gr(e)&&Mi(e)==wO}var Pw=Object.prototype,EO=Pw.hasOwnProperty,xO=Pw.propertyIsEnumerable,$o=ug((function(){return arguments})())?ug:function(e){return Gr(e)&&EO.call(e,"callee")&&!xO.call(e,"callee")};function SO(){return!1}var Iw=typeof exports=="object"&&exports&&!exports.nodeType&&exports,dg=Iw&&typeof module=="object"&&module&&!module.nodeType&&module,CO=dg&&dg.exports===Iw,hg=CO?Lr.Buffer:void 0,AO=hg?hg.isBuffer:void 0,Vo=AO||SO,TO="[object Arguments]",kO="[object Array]",OO="[object Boolean]",NO="[object Date]",PO="[object Error]",IO="[object Function]",FO="[object Map]",LO="[object Number]",RO="[object Object]",MO="[object RegExp]",DO="[object Set]",$O="[object String]",VO="[object WeakMap]",BO="[object ArrayBuffer]",UO="[object DataView]",zO="[object Float32Array]",HO="[object Float64Array]",qO="[object Int8Array]",jO="[object Int16Array]",WO="[object Int32Array]",KO="[object Uint8Array]",GO="[object Uint8ClampedArray]",YO="[object Uint16Array]",XO="[object Uint32Array]",De={};De[zO]=De[HO]=De[qO]=De[jO]=De[WO]=De[KO]=De[GO]=De[YO]=De[XO]=!0;De[TO]=De[kO]=De[BO]=De[OO]=De[UO]=De[NO]=De[PO]=De[IO]=De[FO]=De[LO]=De[RO]=De[MO]=De[DO]=De[$O]=De[VO]=!1;function JO(e){return Gr(e)&&sp(e.length)&&!!De[Mi(e)]}function ap(e){return function(t){return e(t)}}var Fw=typeof exports=="object"&&exports&&!exports.nodeType&&exports,po=Fw&&typeof module=="object"&&module&&!module.nodeType&&module,QO=po&&po.exports===Fw,xu=QO&&xw.process,ms=(function(){try{var e=po&&po.require&&po.require("util").types;return e||xu&&xu.binding&&xu.binding("util")}catch{}})(),fg=ms&&ms.isTypedArray,lp=fg?ap(fg):JO,ZO=Object.prototype,eN=ZO.hasOwnProperty;function Lw(e,t){var r=gr(e),n=!r&&$o(e),i=!r&&!n&&Vo(e),s=!r&&!n&&!i&&lp(e),o=r||n||i||s,a=o?_O(e.length,String):[],l=a.length;for(var u in e)(t||eN.call(e,u))&&!(o&&(u=="length"||i&&(u=="offset"||u=="parent")||s&&(u=="buffer"||u=="byteLength"||u=="byteOffset")||kc(u,l)))&&a.push(u);return a}function Rw(e,t){return function(r){return e(t(r))}}var tN=Rw(Object.keys,Object),rN=Object.prototype,nN=rN.hasOwnProperty;function iN(e){if(!op(e))return tN(e);var t=[];for(var r in Object(e))nN.call(e,r)&&r!="constructor"&&t.push(r);return t}function cp(e){return Oc(e)?Lw(e):iN(e)}function sN(e){var t=[];if(e!=null)for(var r in Object(e))t.push(r);return t}var oN=Object.prototype,aN=oN.hasOwnProperty;function lN(e){if(!nr(e))return sN(e);var t=op(e),r=[];for(var n in e)n=="constructor"&&(t||!aN.call(e,n))||r.push(n);return r}function na(e){return Oc(e)?Lw(e,!0):lN(e)}var cN=/\.|\[(?:[^[\]]*|(["'])(?:(?!\1)[^\\]|\\.)*?\1)\]/,uN=/^\w*$/;function dN(e,t){if(gr(e))return!1;var r=typeof e;return r=="number"||r=="symbol"||r=="boolean"||e==null||Tc(e)?!0:uN.test(e)||!cN.test(e)||t!=null&&e in Object(t)}var Bo=$i(Object,"create");function hN(){this.__data__=Bo?Bo(null):{},this.size=0}function fN(e){var t=this.has(e)&&delete this.__data__[e];return this.size-=t?1:0,t}var pN="__lodash_hash_undefined__",mN=Object.prototype,gN=mN.hasOwnProperty;function bN(e){var t=this.__data__;if(Bo){var r=t[e];return r===pN?void 0:r}return gN.call(t,e)?t[e]:void 0}var vN=Object.prototype,yN=vN.hasOwnProperty;function _N(e){var t=this.__data__;return Bo?t[e]!==void 0:yN.call(t,e)}var wN="__lodash_hash_undefined__";function EN(e,t){var r=this.__data__;return this.size+=this.has(e)?0:1,r[e]=Bo&&t===void 0?wN:t,this}function Pi(e){var t=-1,r=e==null?0:e.length;for(this.clear();++t<r;){var n=e[t];this.set(n[0],n[1])}}Pi.prototype.clear=hN;Pi.prototype.delete=fN;Pi.prototype.get=bN;Pi.prototype.has=_N;Pi.prototype.set=EN;function xN(){this.__data__=[],this.size=0}function Nc(e,t){for(var r=e.length;r--;)if(ra(e[r][0],t))return r;return-1}var SN=Array.prototype,CN=SN.splice;function AN(e){var t=this.__data__,r=Nc(t,e);if(r<0)return!1;var n=t.length-1;return r==n?t.pop():CN.call(t,r,1),--this.size,!0}function TN(e){var t=this.__data__,r=Nc(t,e);return r<0?void 0:t[r][1]}function kN(e){return Nc(this.__data__,e)>-1}function ON(e,t){var r=this.__data__,n=Nc(r,e);return n<0?(++this.size,r.push([e,t])):r[n][1]=t,this}function xn(e){var t=-1,r=e==null?0:e.length;for(this.clear();++t<r;){var n=e[t];this.set(n[0],n[1])}}xn.prototype.clear=xN;xn.prototype.delete=AN;xn.prototype.get=TN;xn.prototype.has=kN;xn.prototype.set=ON;var Uo=$i(Lr,"Map");function NN(){this.size=0,this.__data__={hash:new Pi,map:new(Uo||xn),string:new Pi}}function PN(e){var t=typeof e;return t=="string"||t=="number"||t=="symbol"||t=="boolean"?e!=="__proto__":e===null}function Pc(e,t){var r=e.__data__;return PN(t)?r[typeof t=="string"?"string":"hash"]:r.map}function IN(e){var t=Pc(this,e).delete(e);return this.size-=t?1:0,t}function FN(e){return Pc(this,e).get(e)}function LN(e){return Pc(this,e).has(e)}function RN(e,t){var r=Pc(this,e),n=r.size;return r.set(e,t),this.size+=r.size==n?0:1,this}function Sn(e){var t=-1,r=e==null?0:e.length;for(this.clear();++t<r;){var n=e[t];this.set(n[0],n[1])}}Sn.prototype.clear=NN;Sn.prototype.delete=IN;Sn.prototype.get=FN;Sn.prototype.has=LN;Sn.prototype.set=RN;var MN="Expected a function";function up(e,t){if(typeof e!="function"||t!=null&&typeof t!="function")throw new TypeError(MN);var r=function(){var n=arguments,i=t?t.apply(this,n):n[0],s=r.cache;if(s.has(i))return s.get(i);var o=e.apply(this,n);return r.cache=s.set(i,o)||s,o};return r.cache=new(up.Cache||Sn),r}up.Cache=Sn;var DN=500;function $N(e){var t=up(e,function(n){return r.size===DN&&r.clear(),n}),r=t.cache;return t}var VN=/[^.[\]]+|\[(?:(-?\d+(?:\.\d+)?)|(["'])((?:(?!\2)[^\\]|\\.)*?)\2)\]|(?=(?:\.|\[\])(?:\.|\[\]|$))/g,BN=/\\(\\)?/g,UN=$N(function(e){var t=[];return e.charCodeAt(0)===46&&t.push(""),e.replace(VN,function(r,n,i,s){t.push(i?s.replace(BN,"$1"):n||r)}),t});function Mw(e){return e==null?"":Aw(e)}function ia(e,t){return gr(e)?e:dN(e,t)?[e]:UN(Mw(e))}function Ic(e){if(typeof e=="string"||Tc(e))return e;var t=e+"";return t=="0"&&1/e==-1/0?"-0":t}function Dw(e,t){t=ia(t,e);for(var r=0,n=t.length;e!=null&&r<n;)e=e[Ic(t[r++])];return r&&r==n?e:void 0}function Cr(e,t,r){var n=e==null?void 0:Dw(e,t);return n===void 0?r:n}function dp(e,t){for(var r=-1,n=t.length,i=e.length;++r<n;)e[i+r]=t[r];return e}var pg=mr?mr.isConcatSpreadable:void 0;function zN(e){return gr(e)||$o(e)||!!(pg&&e&&e[pg])}function HN(e,t,r,n,i){var s=-1,o=e.length;for(r||(r=zN),i||(i=[]);++s<o;){var a=e[s];r(a)?dp(i,a):i[i.length]=a}return i}function qN(e){var t=e==null?0:e.length;return t?HN(e):[]}function jN(e){return Ow(Nw(e,void 0,qN),e+"")}var hp=Rw(Object.getPrototypeOf,Object),WN="[object Object]",KN=Function.prototype,GN=Object.prototype,$w=KN.toString,YN=GN.hasOwnProperty,XN=$w.call(Object);function Vw(e){if(!Gr(e)||Mi(e)!=WN)return!1;var t=hp(e);if(t===null)return!0;var r=YN.call(t,"constructor")&&t.constructor;return typeof r=="function"&&r instanceof r&&$w.call(r)==XN}function JN(e,t,r){var n=-1,i=e.length;t<0&&(t=-t>i?0:i+t),r=r>i?i:r,r<0&&(r+=i),i=t>r?0:r-t>>>0,t>>>=0;for(var s=Array(i);++n<i;)s[n]=e[n+t];return s}function QN(e){return function(t){return e?.[t]}}function ZN(){this.__data__=new xn,this.size=0}function eP(e){var t=this.__data__,r=t.delete(e);return this.size=t.size,r}function tP(e){return this.__data__.get(e)}function rP(e){return this.__data__.has(e)}var nP=200;function iP(e,t){var r=this.__data__;if(r instanceof xn){var n=r.__data__;if(!Uo||n.length<nP-1)return n.push([e,t]),this.size=++r.size,this;r=this.__data__=new Sn(n)}return r.set(e,t),this.size=r.size,this}function qr(e){var t=this.__data__=new xn(e);this.size=t.size}qr.prototype.clear=ZN;qr.prototype.delete=eP;qr.prototype.get=tP;qr.prototype.has=rP;qr.prototype.set=iP;function sP(e,t){return e&&As(t,cp(t),e)}function oP(e,t){return e&&As(t,na(t),e)}var Bw=typeof exports=="object"&&exports&&!exports.nodeType&&exports,mg=Bw&&typeof module=="object"&&module&&!module.nodeType&&module,aP=mg&&mg.exports===Bw,gg=aP?Lr.Buffer:void 0,bg=gg?gg.allocUnsafe:void 0;function Uw(e,t){if(t)return e.slice();var r=e.length,n=bg?bg(r):new e.constructor(r);return e.copy(n),n}function lP(e,t){for(var r=-1,n=e==null?0:e.length,i=0,s=[];++r<n;){var o=e[r];t(o,r,e)&&(s[i++]=o)}return s}function zw(){return[]}var cP=Object.prototype,uP=cP.propertyIsEnumerable,vg=Object.getOwnPropertySymbols,fp=vg?function(e){return e==null?[]:(e=Object(e),lP(vg(e),function(t){return uP.call(e,t)}))}:zw;function dP(e,t){return As(e,fp(e),t)}var hP=Object.getOwnPropertySymbols,Hw=hP?function(e){for(var t=[];e;)dp(t,fp(e)),e=hp(e);return t}:zw;function fP(e,t){return As(e,Hw(e),t)}function qw(e,t,r){var n=t(e);return gr(e)?n:dp(n,r(e))}function xh(e){return qw(e,cp,fp)}function jw(e){return qw(e,na,Hw)}var Sh=$i(Lr,"DataView"),Ch=$i(Lr,"Promise"),Ah=$i(Lr,"Set"),yg="[object Map]",pP="[object Object]",_g="[object Promise]",wg="[object Set]",Eg="[object WeakMap]",xg="[object DataView]",mP=Di(Sh),gP=Di(Uo),bP=Di(Ch),vP=Di(Ah),yP=Di(Eh),Er=Mi;(Sh&&Er(new Sh(new ArrayBuffer(1)))!=xg||Uo&&Er(new Uo)!=yg||Ch&&Er(Ch.resolve())!=_g||Ah&&Er(new Ah)!=wg||Eh&&Er(new Eh)!=Eg)&&(Er=function(e){var t=Mi(e),r=t==pP?e.constructor:void 0,n=r?Di(r):"";if(n)switch(n){case mP:return xg;case gP:return yg;case bP:return _g;case vP:return wg;case yP:return Eg}return t});var _P=Object.prototype,wP=_P.hasOwnProperty;function EP(e){var t=e.length,r=new e.constructor(t);return t&&typeof e[0]=="string"&&wP.call(e,"index")&&(r.index=e.index,r.input=e.input),r}var Ml=Lr.Uint8Array;function pp(e){var t=new e.constructor(e.byteLength);return new Ml(t).set(new Ml(e)),t}function xP(e,t){var r=t?pp(e.buffer):e.buffer;return new e.constructor(r,e.byteOffset,e.byteLength)}var SP=/\w*$/;function CP(e){var t=new e.constructor(e.source,SP.exec(e));return t.lastIndex=e.lastIndex,t}var Sg=mr?mr.prototype:void 0,Cg=Sg?Sg.valueOf:void 0;function AP(e){return Cg?Object(Cg.call(e)):{}}function Ww(e,t){var r=t?pp(e.buffer):e.buffer;return new e.constructor(r,e.byteOffset,e.length)}var TP="[object Boolean]",kP="[object Date]",OP="[object Map]",NP="[object Number]",PP="[object RegExp]",IP="[object Set]",FP="[object String]",LP="[object Symbol]",RP="[object ArrayBuffer]",MP="[object DataView]",DP="[object Float32Array]",$P="[object Float64Array]",VP="[object Int8Array]",BP="[object Int16Array]",UP="[object Int32Array]",zP="[object Uint8Array]",HP="[object Uint8ClampedArray]",qP="[object Uint16Array]",jP="[object Uint32Array]";function WP(e,t,r){var n=e.constructor;switch(t){case RP:return pp(e);case TP:case kP:return new n(+e);case MP:return xP(e,r);case DP:case $P:case VP:case BP:case UP:case zP:case HP:case qP:case jP:return Ww(e,r);case OP:return new n;case NP:case FP:return new n(e);case PP:return CP(e);case IP:return new n;case LP:return AP(e)}}function Kw(e){return typeof e.constructor=="function"&&!op(e)?rO(hp(e)):{}}var KP="[object Map]";function GP(e){return Gr(e)&&Er(e)==KP}var Ag=ms&&ms.isMap,YP=Ag?ap(Ag):GP,XP="[object Set]";function JP(e){return Gr(e)&&Er(e)==XP}var Tg=ms&&ms.isSet,QP=Tg?ap(Tg):JP,ZP=1,eI=2,tI=4,Gw="[object Arguments]",rI="[object Array]",nI="[object Boolean]",iI="[object Date]",sI="[object Error]",Yw="[object Function]",oI="[object GeneratorFunction]",aI="[object Map]",lI="[object Number]",Xw="[object Object]",cI="[object RegExp]",uI="[object Set]",dI="[object String]",hI="[object Symbol]",fI="[object WeakMap]",pI="[object ArrayBuffer]",mI="[object DataView]",gI="[object Float32Array]",bI="[object Float64Array]",vI="[object Int8Array]",yI="[object Int16Array]",_I="[object Int32Array]",wI="[object Uint8Array]",EI="[object Uint8ClampedArray]",xI="[object Uint16Array]",SI="[object Uint32Array]",Pe={};Pe[Gw]=Pe[rI]=Pe[pI]=Pe[mI]=Pe[nI]=Pe[iI]=Pe[gI]=Pe[bI]=Pe[vI]=Pe[yI]=Pe[_I]=Pe[aI]=Pe[lI]=Pe[Xw]=Pe[cI]=Pe[uI]=Pe[dI]=Pe[hI]=Pe[wI]=Pe[EI]=Pe[xI]=Pe[SI]=!0;Pe[sI]=Pe[Yw]=Pe[fI]=!1;function mo(e,t,r,n,i,s){var o,a=t&ZP,l=t&eI,u=t&tI;if(r&&(o=i?r(e,n,i,s):r(e)),o!==void 0)return o;if(!nr(e))return e;var c=gr(e);if(c){if(o=EP(e),!a)return kw(e,o)}else{var d=Er(e),p=d==Yw||d==oI;if(Vo(e))return Uw(e,a);if(d==Xw||d==Gw||p&&!i){if(o=l||p?{}:Kw(e),!a)return l?fP(e,oP(o,e)):dP(e,sP(o,e))}else{if(!Pe[d])return i?e:{};o=WP(e,d,a)}}s||(s=new qr);var m=s.get(e);if(m)return m;s.set(e,o),QP(e)?e.forEach(function(b){o.add(mo(b,t,r,b,e,s))}):YP(e)&&e.forEach(function(b,y){o.set(y,mo(b,t,r,y,e,s))});var h=u?l?jw:xh:l?na:cp,f=c?void 0:h(e);return uO(f||e,function(b,y){f&&(y=b,b=e[y]),ip(o,y,mo(b,t,r,y,e,s))}),o}var CI=1,AI=4;function ut(e){return mo(e,CI|AI)}var TI="__lodash_hash_undefined__";function kI(e){return this.__data__.set(e,TI),this}function OI(e){return this.__data__.has(e)}function Dl(e){var t=-1,r=e==null?0:e.length;for(this.__data__=new Sn;++t<r;)this.add(e[t])}Dl.prototype.add=Dl.prototype.push=kI;Dl.prototype.has=OI;function NI(e,t){for(var r=-1,n=e==null?0:e.length;++r<n;)if(t(e[r],r,e))return!0;return!1}function PI(e,t){return e.has(t)}var II=1,FI=2;function Jw(e,t,r,n,i,s){var o=r&II,a=e.length,l=t.length;if(a!=l&&!(o&&l>a))return!1;var u=s.get(e),c=s.get(t);if(u&&c)return u==t&&c==e;var d=-1,p=!0,m=r&FI?new Dl:void 0;for(s.set(e,t),s.set(t,e);++d<a;){var h=e[d],f=t[d];if(n)var b=o?n(f,h,d,t,e,s):n(h,f,d,e,t,s);if(b!==void 0){if(b)continue;p=!1;break}if(m){if(!NI(t,function(y,w){if(!PI(m,w)&&(h===y||i(h,y,r,n,s)))return m.push(w)})){p=!1;break}}else if(!(h===f||i(h,f,r,n,s))){p=!1;break}}return s.delete(e),s.delete(t),p}function LI(e){var t=-1,r=Array(e.size);return e.forEach(function(n,i){r[++t]=[i,n]}),r}function RI(e){var t=-1,r=Array(e.size);return e.forEach(function(n){r[++t]=n}),r}var MI=1,DI=2,$I="[object Boolean]",VI="[object Date]",BI="[object Error]",UI="[object Map]",zI="[object Number]",HI="[object RegExp]",qI="[object Set]",jI="[object String]",WI="[object Symbol]",KI="[object ArrayBuffer]",GI="[object DataView]",kg=mr?mr.prototype:void 0,Su=kg?kg.valueOf:void 0;function YI(e,t,r,n,i,s,o){switch(r){case GI:if(e.byteLength!=t.byteLength||e.byteOffset!=t.byteOffset)return!1;e=e.buffer,t=t.buffer;case KI:return!(e.byteLength!=t.byteLength||!s(new Ml(e),new Ml(t)));case $I:case VI:case zI:return ra(+e,+t);case BI:return e.name==t.name&&e.message==t.message;case HI:case jI:return e==t+"";case UI:var a=LI;case qI:var l=n&MI;if(a||(a=RI),e.size!=t.size&&!l)return!1;var u=o.get(e);if(u)return u==t;n|=DI,o.set(e,t);var c=Jw(a(e),a(t),n,i,s,o);return o.delete(e),c;case WI:if(Su)return Su.call(e)==Su.call(t)}return!1}var XI=1,JI=Object.prototype,QI=JI.hasOwnProperty;function ZI(e,t,r,n,i,s){var o=r&XI,a=xh(e),l=a.length,u=xh(t),c=u.length;if(l!=c&&!o)return!1;for(var d=l;d--;){var p=a[d];if(!(o?p in t:QI.call(t,p)))return!1}var m=s.get(e),h=s.get(t);if(m&&h)return m==t&&h==e;var f=!0;s.set(e,t),s.set(t,e);for(var b=o;++d<l;){p=a[d];var y=e[p],w=t[p];if(n)var v=o?n(w,y,p,t,e,s):n(y,w,p,e,t,s);if(!(v===void 0?y===w||i(y,w,r,n,s):v)){f=!1;break}b||(b=p=="constructor")}if(f&&!b){var _=e.constructor,x=t.constructor;_!=x&&"constructor"in e&&"constructor"in t&&!(typeof _=="function"&&_ instanceof _&&typeof x=="function"&&x instanceof x)&&(f=!1)}return s.delete(e),s.delete(t),f}var eF=1,Og="[object Arguments]",Ng="[object Array]",Da="[object Object]",tF=Object.prototype,Pg=tF.hasOwnProperty;function rF(e,t,r,n,i,s){var o=gr(e),a=gr(t),l=o?Ng:Er(e),u=a?Ng:Er(t);l=l==Og?Da:l,u=u==Og?Da:u;var c=l==Da,d=u==Da,p=l==u;if(p&&Vo(e)){if(!Vo(t))return!1;o=!0,c=!1}if(p&&!c)return s||(s=new qr),o||lp(e)?Jw(e,t,r,n,i,s):YI(e,t,l,r,n,i,s);if(!(r&eF)){var m=c&&Pg.call(e,"__wrapped__"),h=d&&Pg.call(t,"__wrapped__");if(m||h){var f=m?e.value():e,b=h?t.value():t;return s||(s=new qr),i(f,b,r,n,s)}}return p?(s||(s=new qr),ZI(e,t,r,n,i,s)):!1}function Qw(e,t,r,n,i){return e===t?!0:e==null||t==null||!Gr(e)&&!Gr(t)?e!==e&&t!==t:rF(e,t,r,n,Qw,i)}function nF(e,t,r){t=ia(t,e);for(var n=-1,i=t.length,s=!1;++n<i;){var o=Ic(t[n]);if(!(s=e!=null&&r(e,o)))break;e=e[o]}return s||++n!=i?s:(i=e==null?0:e.length,!!i&&sp(i)&&kc(o,i)&&(gr(e)||$o(e)))}function iF(e){return function(t,r,n){for(var i=-1,s=Object(t),o=n(t),a=o.length;a--;){var l=o[++i];if(r(s[l],l,s)===!1)break}return t}}var sF=iF(),Cu=function(){return Lr.Date.now()},oF="Expected a function",aF=Math.max,lF=Math.min;function cF(e,t,r){var n,i,s,o,a,l,u=0,c=!1,d=!1,p=!0;if(typeof e!="function")throw new TypeError(oF);t=og(t)||0,nr(r)&&(c=!0,d="maxWait"in r,s=d?aF(og(r.maxWait)||0,t):s,p="trailing"in r?!0:p);function m(k){var S=n,N=i;return n=i=void 0,u=k,o=e.apply(N,S),o}function h(k){return u=k,a=setTimeout(y,t),c?m(k):o}function f(k){var S=k-l,N=k-u,A=t-S;return d?lF(A,s-N):A}function b(k){var S=k-l,N=k-u;return l===void 0||S>=t||S<0||d&&N>=s}function y(){var k=Cu();if(b(k))return w(k);a=setTimeout(y,f(k))}function w(k){return a=void 0,p&&n?m(k):(n=i=void 0,o)}function v(){a!==void 0&&clearTimeout(a),u=0,n=l=i=a=void 0}function _(){return a===void 0?o:w(Cu())}function x(){var k=Cu(),S=b(k);if(n=arguments,i=this,l=k,S){if(a===void 0)return h(l);if(d)return clearTimeout(a),a=setTimeout(y,t),m(l)}return a===void 0&&(a=setTimeout(y,t)),o}return x.cancel=v,x.flush=_,x}function Th(e,t,r){(r!==void 0&&!ra(e[t],r)||r===void 0&&!(t in e))&&np(e,t,r)}function uF(e){return Gr(e)&&Oc(e)}function kh(e,t){if(!(t==="constructor"&&typeof e[t]=="function")&&t!="__proto__")return e[t]}function dF(e){return As(e,na(e))}function hF(e,t,r,n,i,s,o){var a=kh(e,r),l=kh(t,r),u=o.get(l);if(u){Th(e,r,u);return}var c=s?s(a,l,r+"",e,t,o):void 0,d=c===void 0;if(d){var p=gr(l),m=!p&&Vo(l),h=!p&&!m&&lp(l);c=l,p||m||h?gr(a)?c=a:uF(a)?c=kw(a):m?(d=!1,c=Uw(l,!0)):h?(d=!1,c=Ww(l,!0)):c=[]:Vw(l)||$o(l)?(c=a,$o(a)?c=dF(a):(!nr(a)||rp(a))&&(c=Kw(l))):d=!1}d&&(o.set(l,c),i(c,l,n,s,o),o.delete(l)),Th(e,r,c)}function Zw(e,t,r,n,i){e!==t&&sF(t,function(s,o){if(i||(i=new qr),nr(s))hF(e,t,o,r,Zw,n,i);else{var a=n?n(kh(e,o),s,o+"",e,t,i):void 0;a===void 0&&(a=s),Th(e,o,a)}},na)}function fF(e){var t=e==null?0:e.length;return t?e[t-1]:void 0}var pF={"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"},mF=QN(pF),e0=/[&<>"']/g,gF=RegExp(e0.source);function bF(e){return e=Mw(e),e&&gF.test(e)?e.replace(e0,mF):e}var vF=Object.prototype,yF=vF.hasOwnProperty;function _F(e,t){return e!=null&&yF.call(e,t)}function t0(e,t){return e!=null&&nF(e,t,_F)}function wF(e,t){return t.length<2?e:Dw(e,JN(t,0,-1))}function Jn(e,t){return Qw(e,t)}var Oh=vO(function(e,t,r){Zw(e,t,r)}),EF=Object.prototype,xF=EF.hasOwnProperty;function SF(e,t){t=ia(t,e);var r=-1,n=t.length;if(!n)return!0;for(var i=e==null||typeof e!="object"&&typeof e!="function";++r<n;){var s=t[r];if(typeof s=="string"){if(s==="__proto__"&&!xF.call(e,"__proto__"))return!1;if(s==="constructor"&&r+1<n&&typeof t[r+1]=="string"&&t[r+1]==="prototype"){if(i&&r===0)continue;return!1}}}var o=wF(e,t);return o==null||delete o[Ic(fF(t))]}function CF(e){return Vw(e)?void 0:e}var AF=1,TF=2,kF=4,Ig=jN(function(e,t){var r={};if(e==null)return r;var n=!1;t=Cw(t,function(s){return s=ia(s,e),n||(n=s.length>1),s}),As(e,jw(e),r),n&&(r=mo(r,AF|TF|kF,CF));for(var i=t.length;i--;)SF(r,t[i]);return r});function OF(e,t,r,n){if(!nr(e))return e;t=ia(t,e);for(var i=-1,s=t.length,o=s-1,a=e;a!=null&&++i<s;){var l=Ic(t[i]),u=r;if(l==="__proto__"||l==="constructor"||l==="prototype")return e;if(i!=o){var c=a[l];u=void 0,u===void 0&&(u=nr(c)?c:kc(t[i+1])?[]:{})}ip(a,l,u),a=a[l]}return e}function zr(e,t,r){return e==null?e:OF(e,t,r)}var Fg=typeof globalThis<"u"?globalThis:typeof window<"u"?window:typeof global<"u"?global:typeof self<"u"?self:{};function NF(e){if(Object.prototype.hasOwnProperty.call(e,"__esModule"))return e;var t=e.default;if(typeof t=="function"){var r=function n(){var i=!1;try{i=this instanceof n}catch{}return i?Reflect.construct(t,arguments,this.constructor):t.apply(this,arguments)};r.prototype=t.prototype}else r={};return Object.defineProperty(r,"__esModule",{value:!0}),Object.keys(e).forEach(function(n){var i=Object.getOwnPropertyDescriptor(e,n);Object.defineProperty(r,n,i.get?i:{enumerable:!0,get:function(){return e[n]}})}),r}var Au,Lg;function Ts(){return Lg||(Lg=1,Au=TypeError),Au}const PF={},IF=Object.freeze(Object.defineProperty({__proto__:null,default:PF},Symbol.toStringTag,{value:"Module"})),FF=NF(IF);var Tu,Rg;function Fc(){if(Rg)return Tu;Rg=1;var e=typeof Map=="function"&&Map.prototype,t=Object.getOwnPropertyDescriptor&&e?Object.getOwnPropertyDescriptor(Map.prototype,"size"):null,r=e&&t&&typeof t.get=="function"?t.get:null,n=e&&Map.prototype.forEach,i=typeof Set=="function"&&Set.prototype,s=Object.getOwnPropertyDescriptor&&i?Object.getOwnPropertyDescriptor(Set.prototype,"size"):null,o=i&&s&&typeof s.get=="function"?s.get:null,a=i&&Set.prototype.forEach,l=typeof WeakMap=="function"&&WeakMap.prototype,u=l?WeakMap.prototype.has:null,c=typeof WeakSet=="function"&&WeakSet.prototype,d=c?WeakSet.prototype.has:null,p=typeof WeakRef=="function"&&WeakRef.prototype,m=p?WeakRef.prototype.deref:null,h=Boolean.prototype.valueOf,f=Object.prototype.toString,b=Function.prototype.toString,y=String.prototype.match,w=String.prototype.slice,v=String.prototype.replace,_=String.prototype.toUpperCase,x=String.prototype.toLowerCase,k=RegExp.prototype.test,S=Array.prototype.concat,N=Array.prototype.join,A=Array.prototype.slice,T=Math.floor,I=typeof BigInt=="function"?BigInt.prototype.valueOf:null,C=Object.getOwnPropertySymbols,g=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?Symbol.prototype.toString:null,L=typeof Symbol=="function"&&typeof Symbol.iterator=="object",B=typeof Symbol=="function"&&Symbol.toStringTag&&(typeof Symbol.toStringTag===L||!0)?Symbol.toStringTag:null,M=Object.prototype.propertyIsEnumerable,z=(typeof Reflect=="function"?Reflect.getPrototypeOf:Object.getPrototypeOf)||([].__proto__===Array.prototype?function(R){return R.__proto__}:null);function P(R,D){if(R===1/0||R===-1/0||R!==R||R&&R>-1e3&&R<1e3||k.call(/e/,D))return D;var ve=/[0-9](?=(?:[0-9]{3})+(?![0-9]))/g;if(typeof R=="number"){var Ae=R<0?-T(-R):T(R);if(Ae!==R){var Me=String(Ae),fe=w.call(D,Me.length+1);return v.call(Me,ve,"$&_")+"."+v.call(v.call(fe,/([0-9]{3})/g,"$&_"),/_$/,"")}}return v.call(D,ve,"$&_")}var Z=FF,be=Z.custom,le=F(be)?be:null,qe={__proto__:null,double:'"',single:"'"},vr={__proto__:null,double:/(["\\])/g,single:/(['\\])/g};Tu=function R(D,ve,Ae,Me){var fe=ve||{};if(H(fe,"quoteStyle")&&!H(qe,fe.quoteStyle))throw new TypeError('option "quoteStyle" must be "single" or "double"');if(H(fe,"maxStringLength")&&(typeof fe.maxStringLength=="number"?fe.maxStringLength<0&&fe.maxStringLength!==1/0:fe.maxStringLength!==null))throw new TypeError('option "maxStringLength", if provided, must be a positive integer, Infinity, or `null`');var An=H(fe,"customInspect")?fe.customInspect:!0;if(typeof An!="boolean"&&An!=="symbol")throw new TypeError("option \"customInspect\", if provided, must be `true`, `false`, or `'symbol'`");if(H(fe,"indent")&&fe.indent!==null&&fe.indent!=="	"&&!(parseInt(fe.indent,10)===fe.indent&&fe.indent>0))throw new TypeError('option "indent" must be "\\t", an integer > 0, or `null`');if(H(fe,"numericSeparator")&&typeof fe.numericSeparator!="boolean")throw new TypeError('option "numericSeparator", if provided, must be `true` or `false`');var ai=fe.numericSeparator;if(typeof D>"u")return"undefined";if(D===null)return"null";if(typeof D=="boolean")return D?"true":"false";if(typeof D=="string")return pe(D,fe);if(typeof D=="number"){if(D===0)return 1/0/D>0?"0":"-0";var Wt=String(D);return ai?P(D,Wt):Wt}if(typeof D=="bigint"){var Tn=String(D)+"n";return ai?P(D,Tn):Tn}var tu=typeof fe.depth>"u"?5:fe.depth;if(typeof Ae>"u"&&(Ae=0),Ae>=tu&&tu>0&&typeof D=="object")return qt(D)?"[Array]":"[Object]";var Bi=Ot(fe,Ae);if(typeof Me>"u")Me=[];else if(X(Me,D)>=0)return"[Circular]";function _r(Ui,Ea,qE){if(Ea&&(Me=A.call(Me),Me.push(Ea)),qE){var jp={depth:fe.depth};return H(fe,"quoteStyle")&&(jp.quoteStyle=fe.quoteStyle),R(Ui,jp,Ae+1,Me)}return R(Ui,fe,Ae+1,Me)}if(typeof D=="function"&&!Ye(D)){var $p=J(D),Vp=oi(D,_r);return"[Function"+($p?": "+$p:" (anonymous)")+"]"+(Vp.length>0?" { "+N.call(Vp,", ")+" }":"")}if(F(D)){var Bp=L?v.call(String(D),/^(Symbol\(.*\))_[^)]*$/,"$1"):g.call(D);return typeof D=="object"&&!L?ge(Bp):Bp}if(ae(D)){for(var Rs="<"+x.call(String(D.nodeName)),ru=D.attributes||[],wa=0;wa<ru.length;wa++)Rs+=" "+ru[wa].name+"="+Mr(yr(ru[wa].value),"double",fe);return Rs+=">",D.childNodes&&D.childNodes.length&&(Rs+="..."),Rs+="</"+x.call(String(D.nodeName))+">",Rs}if(qt(D)){if(D.length===0)return"[]";var nu=oi(D,_r);return Bi&&!jt(nu)?"["+Qr(nu,Bi)+"]":"[ "+N.call(nu,", ")+" ]"}if(he(D)){var iu=oi(D,_r);return!("cause"in Error.prototype)&&"cause"in D&&!M.call(D,"cause")?"{ ["+String(D)+"] "+N.call(S.call("[cause]: "+_r(D.cause),iu),", ")+" }":iu.length===0?"["+String(D)+"]":"{ ["+String(D)+"] "+N.call(iu,", ")+" }"}if(typeof D=="object"&&An){if(le&&typeof D[le]=="function"&&Z)return Z(D,{depth:tu-Ae});if(An!=="symbol"&&typeof D.inspect=="function")return D.inspect()}if(G(D)){var Up=[];return n&&n.call(D,function(Ui,Ea){Up.push(_r(Ea,D,!0)+" => "+_r(Ui,D))}),tt("Map",r.call(D),Up,Bi)}if(Q(D)){var zp=[];return a&&a.call(D,function(Ui){zp.push(_r(Ui,D))}),tt("Set",o.call(D),zp,Bi)}if(K(D))return ct("WeakMap");if(re(D))return ct("WeakSet");if(ne(D))return ct("WeakRef");if(Se(D))return ge(_r(Number(D)));if(U(D))return ge(_r(I.call(D)));if(E(D))return ge(h.call(D));if(Re(D))return ge(_r(String(D)));if(typeof window<"u"&&D===window)return"{ [object Window] }";if(typeof globalThis<"u"&&D===globalThis||typeof Fg<"u"&&D===Fg)return"{ [object globalThis] }";if(!lr(D)&&!Ye(D)){var su=oi(D,_r),Hp=z?z(D)===Object.prototype:D instanceof Object||D.constructor===Object,ou=D instanceof Object?"":"null prototype",qp=!Hp&&B&&Object(D)===D&&B in D?w.call(j(D),8,-1):ou?"Object":"",HE=Hp||typeof D.constructor!="function"?"":D.constructor.name?D.constructor.name+" ":"",au=HE+(qp||ou?"["+N.call(S.call([],qp||[],ou||[]),": ")+"] ":"");return su.length===0?au+"{}":Bi?au+"{"+Qr(su,Bi)+"}":au+"{ "+N.call(su,", ")+" }"}return String(D)};function Mr(R,D,ve){var Ae=ve.quoteStyle||D,Me=qe[Ae];return Me+R+Me}function yr(R){return v.call(String(R),/"/g,"&quot;")}function lt(R){return!B||!(typeof R=="object"&&(B in R||typeof R[B]<"u"))}function qt(R){return j(R)==="[object Array]"&&lt(R)}function lr(R){return j(R)==="[object Date]"&&lt(R)}function Ye(R){return j(R)==="[object RegExp]"&&lt(R)}function he(R){return j(R)==="[object Error]"&&lt(R)}function Re(R){return j(R)==="[object String]"&&lt(R)}function Se(R){return j(R)==="[object Number]"&&lt(R)}function E(R){return j(R)==="[object Boolean]"&&lt(R)}function F(R){if(L)return R&&typeof R=="object"&&R instanceof Symbol;if(typeof R=="symbol")return!0;if(!R||typeof R!="object"||!g)return!1;try{return g.call(R),!0}catch{}return!1}function U(R){if(!R||typeof R!="object"||!I)return!1;try{return I.call(R),!0}catch{}return!1}var q=Object.prototype.hasOwnProperty||function(R){return R in this};function H(R,D){return q.call(R,D)}function j(R){return f.call(R)}function J(R){if(R.name)return R.name;var D=y.call(b.call(R),/^function\s*([\w$]+)/);return D?D[1]:null}function X(R,D){if(R.indexOf)return R.indexOf(D);for(var ve=0,Ae=R.length;ve<Ae;ve++)if(R[ve]===D)return ve;return-1}function G(R){if(!r||!R||typeof R!="object")return!1;try{r.call(R);try{o.call(R)}catch{return!0}return R instanceof Map}catch{}return!1}function K(R){if(!u||!R||typeof R!="object")return!1;try{u.call(R,u);try{d.call(R,d)}catch{return!0}return R instanceof WeakMap}catch{}return!1}function ne(R){if(!m||!R||typeof R!="object")return!1;try{return m.call(R),!0}catch{}return!1}function Q(R){if(!o||!R||typeof R!="object")return!1;try{o.call(R);try{r.call(R)}catch{return!0}return R instanceof Set}catch{}return!1}function re(R){if(!d||!R||typeof R!="object")return!1;try{d.call(R,d);try{u.call(R,u)}catch{return!0}return R instanceof WeakSet}catch{}return!1}function ae(R){return!R||typeof R!="object"?!1:typeof HTMLElement<"u"&&R instanceof HTMLElement?!0:typeof R.nodeName=="string"&&typeof R.getAttribute=="function"}function pe(R,D){if(R.length>D.maxStringLength){var ve=R.length-D.maxStringLength,Ae="... "+ve+" more character"+(ve>1?"s":"");return pe(w.call(R,0,D.maxStringLength),D)+Ae}var Me=vr[D.quoteStyle||"single"];Me.lastIndex=0;var fe=v.call(v.call(R,Me,"\\$1"),/[\x00-\x1f]/g,Oe);return Mr(fe,"single",D)}function Oe(R){var D=R.charCodeAt(0),ve={8:"b",9:"t",10:"n",12:"f",13:"r"}[D];return ve?"\\"+ve:"\\x"+(D<16?"0":"")+_.call(D.toString(16))}function ge(R){return"Object("+R+")"}function ct(R){return R+" { ? }"}function tt(R,D,ve,Ae){var Me=Ae?Qr(ve,Ae):N.call(ve,", ");return R+" ("+D+") {"+Me+"}"}function jt(R){for(var D=0;D<R.length;D++)if(X(R[D],`
`)>=0)return!1;return!0}function Ot(R,D){var ve;if(R.indent==="	")ve="	";else if(typeof R.indent=="number"&&R.indent>0)ve=N.call(Array(R.indent+1)," ");else return null;return{base:ve,prev:N.call(Array(D+1),ve)}}function Qr(R,D){if(R.length===0)return"";var ve=`
`+D.prev+D.base;return ve+N.call(R,","+ve)+`
`+D.prev}function oi(R,D){var ve=qt(R),Ae=[];if(ve){Ae.length=R.length;for(var Me=0;Me<R.length;Me++)Ae[Me]=H(R,Me)?D(R[Me],R):""}var fe=typeof C=="function"?C(R):[],An;if(L){An={};for(var ai=0;ai<fe.length;ai++)An["$"+fe[ai]]=fe[ai]}for(var Wt in R)H(R,Wt)&&(ve&&String(Number(Wt))===Wt&&Wt<R.length||L&&An["$"+Wt]instanceof Symbol||(k.call(/[^\w$]/,Wt)?Ae.push(D(Wt,R)+": "+D(R[Wt],R)):Ae.push(Wt+": "+D(R[Wt],R))));if(typeof C=="function")for(var Tn=0;Tn<fe.length;Tn++)M.call(R,fe[Tn])&&Ae.push("["+D(fe[Tn])+"]: "+D(R[fe[Tn]],R));return Ae}return Tu}var ku,Mg;function LF(){if(Mg)return ku;Mg=1;var e=Fc(),t=Ts(),r=function(a,l,u){for(var c=a,d;(d=c.next)!=null;c=d)if(d.key===l)return c.next=d.next,u||(d.next=a.next,a.next=d),d},n=function(a,l){if(a){var u=r(a,l);return u&&u.value}},i=function(a,l,u){var c=r(a,l);c?c.value=u:a.next={key:l,next:a.next,value:u}},s=function(a,l){return a?!!r(a,l):!1},o=function(a,l){if(a)return r(a,l,!0)};return ku=function(){var l,u={assert:function(c){if(!u.has(c))throw new t("Side channel does not contain "+e(c))},delete:function(c){var d=l&&l.next,p=o(l,c);return p&&d&&d===p&&(l=void 0),!!p},get:function(c){return n(l,c)},has:function(c){return s(l,c)},set:function(c,d){l||(l={next:void 0}),i(l,c,d)}};return u},ku}var Ou,Dg;function r0(){return Dg||(Dg=1,Ou=Object),Ou}var Nu,$g;function RF(){return $g||($g=1,Nu=Error),Nu}var Pu,Vg;function MF(){return Vg||(Vg=1,Pu=EvalError),Pu}var Iu,Bg;function DF(){return Bg||(Bg=1,Iu=RangeError),Iu}var Fu,Ug;function $F(){return Ug||(Ug=1,Fu=ReferenceError),Fu}var Lu,zg;function VF(){return zg||(zg=1,Lu=SyntaxError),Lu}var Ru,Hg;function BF(){return Hg||(Hg=1,Ru=URIError),Ru}var Mu,qg;function UF(){return qg||(qg=1,Mu=Math.abs),Mu}var Du,jg;function zF(){return jg||(jg=1,Du=Math.floor),Du}var $u,Wg;function HF(){return Wg||(Wg=1,$u=Math.max),$u}var Vu,Kg;function qF(){return Kg||(Kg=1,Vu=Math.min),Vu}var Bu,Gg;function jF(){return Gg||(Gg=1,Bu=Math.pow),Bu}var Uu,Yg;function WF(){return Yg||(Yg=1,Uu=Math.round),Uu}var zu,Xg;function KF(){return Xg||(Xg=1,zu=Number.isNaN||function(t){return t!==t}),zu}var Hu,Jg;function GF(){if(Jg)return Hu;Jg=1;var e=KF();return Hu=function(r){return e(r)||r===0?r:r<0?-1:1},Hu}var qu,Qg;function YF(){return Qg||(Qg=1,qu=Object.getOwnPropertyDescriptor),qu}var ju,Zg;function n0(){if(Zg)return ju;Zg=1;var e=YF();if(e)try{e([],"length")}catch{e=null}return ju=e,ju}var Wu,eb;function XF(){if(eb)return Wu;eb=1;var e=Object.defineProperty||!1;if(e)try{e({},"a",{value:1})}catch{e=!1}return Wu=e,Wu}var Ku,tb;function JF(){return tb||(tb=1,Ku=function(){if(typeof Symbol!="function"||typeof Object.getOwnPropertySymbols!="function")return!1;if(typeof Symbol.iterator=="symbol")return!0;var t={},r=Symbol("test"),n=Object(r);if(typeof r=="string"||Object.prototype.toString.call(r)!=="[object Symbol]"||Object.prototype.toString.call(n)!=="[object Symbol]")return!1;var i=42;t[r]=i;for(var s in t)return!1;if(typeof Object.keys=="function"&&Object.keys(t).length!==0||typeof Object.getOwnPropertyNames=="function"&&Object.getOwnPropertyNames(t).length!==0)return!1;var o=Object.getOwnPropertySymbols(t);if(o.length!==1||o[0]!==r||!Object.prototype.propertyIsEnumerable.call(t,r))return!1;if(typeof Object.getOwnPropertyDescriptor=="function"){var a=Object.getOwnPropertyDescriptor(t,r);if(a.value!==i||a.enumerable!==!0)return!1}return!0}),Ku}var Gu,rb;function QF(){if(rb)return Gu;rb=1;var e=typeof Symbol<"u"&&Symbol,t=JF();return Gu=function(){return typeof e!="function"||typeof Symbol!="function"||typeof e("foo")!="symbol"||typeof Symbol("bar")!="symbol"?!1:t()},Gu}var Yu,nb;function i0(){return nb||(nb=1,Yu=typeof Reflect<"u"&&Reflect.getPrototypeOf||null),Yu}var Xu,ib;function s0(){if(ib)return Xu;ib=1;var e=r0();return Xu=e.getPrototypeOf||null,Xu}var Ju,sb;function ZF(){if(sb)return Ju;sb=1;var e="Function.prototype.bind called on incompatible ",t=Object.prototype.toString,r=Math.max,n="[object Function]",i=function(l,u){for(var c=[],d=0;d<l.length;d+=1)c[d]=l[d];for(var p=0;p<u.length;p+=1)c[p+l.length]=u[p];return c},s=function(l,u){for(var c=[],d=u,p=0;d<l.length;d+=1,p+=1)c[p]=l[d];return c},o=function(a,l){for(var u="",c=0;c<a.length;c+=1)u+=a[c],c+1<a.length&&(u+=l);return u};return Ju=function(l){var u=this;if(typeof u!="function"||t.apply(u)!==n)throw new TypeError(e+u);for(var c=s(arguments,1),d,p=function(){if(this instanceof d){var y=u.apply(this,i(c,arguments));return Object(y)===y?y:this}return u.apply(l,i(c,arguments))},m=r(0,u.length-c.length),h=[],f=0;f<m;f++)h[f]="$"+f;if(d=Function("binder","return function ("+o(h,",")+"){ return binder.apply(this,arguments); }")(p),u.prototype){var b=function(){};b.prototype=u.prototype,d.prototype=new b,b.prototype=null}return d},Ju}var Qu,ob;function Lc(){if(ob)return Qu;ob=1;var e=ZF();return Qu=Function.prototype.bind||e,Qu}var Zu,ab;function mp(){return ab||(ab=1,Zu=Function.prototype.call),Zu}var ed,lb;function o0(){return lb||(lb=1,ed=Function.prototype.apply),ed}var td,cb;function e2(){return cb||(cb=1,td=typeof Reflect<"u"&&Reflect&&Reflect.apply),td}var rd,ub;function t2(){if(ub)return rd;ub=1;var e=Lc(),t=o0(),r=mp(),n=e2();return rd=n||e.call(r,t),rd}var nd,db;function a0(){if(db)return nd;db=1;var e=Lc(),t=Ts(),r=mp(),n=t2();return nd=function(s){if(s.length<1||typeof s[0]!="function")throw new t("a function is required");return n(e,r,s)},nd}var id,hb;function r2(){if(hb)return id;hb=1;var e=a0(),t=n0(),r;try{r=[].__proto__===Array.prototype}catch(o){if(!o||typeof o!="object"||!("code"in o)||o.code!=="ERR_PROTO_ACCESS")throw o}var n=!!r&&t&&t(Object.prototype,"__proto__"),i=Object,s=i.getPrototypeOf;return id=n&&typeof n.get=="function"?e([n.get]):typeof s=="function"?function(a){return s(a==null?a:i(a))}:!1,id}var sd,fb;function n2(){if(fb)return sd;fb=1;var e=i0(),t=s0(),r=r2();return sd=e?function(i){return e(i)}:t?function(i){if(!i||typeof i!="object"&&typeof i!="function")throw new TypeError("getProto: not an object");return t(i)}:r?function(i){return r(i)}:null,sd}var od,pb;function i2(){if(pb)return od;pb=1;var e=Function.prototype.call,t=Object.prototype.hasOwnProperty,r=Lc();return od=r.call(e,t),od}var ad,mb;function gp(){if(mb)return ad;mb=1;var e,t=r0(),r=RF(),n=MF(),i=DF(),s=$F(),o=VF(),a=Ts(),l=BF(),u=UF(),c=zF(),d=HF(),p=qF(),m=jF(),h=WF(),f=GF(),b=Function,y=function(Ye){try{return b('"use strict"; return ('+Ye+").constructor;")()}catch{}},w=n0(),v=XF(),_=function(){throw new a},x=w?(function(){try{return arguments.callee,_}catch{try{return w(arguments,"callee").get}catch{return _}}})():_,k=QF()(),S=n2(),N=s0(),A=i0(),T=o0(),I=mp(),C={},g=typeof Uint8Array>"u"||!S?e:S(Uint8Array),L={__proto__:null,"%AggregateError%":typeof AggregateError>"u"?e:AggregateError,"%Array%":Array,"%ArrayBuffer%":typeof ArrayBuffer>"u"?e:ArrayBuffer,"%ArrayIteratorPrototype%":k&&S?S([][Symbol.iterator]()):e,"%AsyncFromSyncIteratorPrototype%":e,"%AsyncFunction%":C,"%AsyncGenerator%":C,"%AsyncGeneratorFunction%":C,"%AsyncIteratorPrototype%":C,"%Atomics%":typeof Atomics>"u"?e:Atomics,"%BigInt%":typeof BigInt>"u"?e:BigInt,"%BigInt64Array%":typeof BigInt64Array>"u"?e:BigInt64Array,"%BigUint64Array%":typeof BigUint64Array>"u"?e:BigUint64Array,"%Boolean%":Boolean,"%DataView%":typeof DataView>"u"?e:DataView,"%Date%":Date,"%decodeURI%":decodeURI,"%decodeURIComponent%":decodeURIComponent,"%encodeURI%":encodeURI,"%encodeURIComponent%":encodeURIComponent,"%Error%":r,"%eval%":eval,"%EvalError%":n,"%Float16Array%":typeof Float16Array>"u"?e:Float16Array,"%Float32Array%":typeof Float32Array>"u"?e:Float32Array,"%Float64Array%":typeof Float64Array>"u"?e:Float64Array,"%FinalizationRegistry%":typeof FinalizationRegistry>"u"?e:FinalizationRegistry,"%Function%":b,"%GeneratorFunction%":C,"%Int8Array%":typeof Int8Array>"u"?e:Int8Array,"%Int16Array%":typeof Int16Array>"u"?e:Int16Array,"%Int32Array%":typeof Int32Array>"u"?e:Int32Array,"%isFinite%":isFinite,"%isNaN%":isNaN,"%IteratorPrototype%":k&&S?S(S([][Symbol.iterator]())):e,"%JSON%":typeof JSON=="object"?JSON:e,"%Map%":typeof Map>"u"?e:Map,"%MapIteratorPrototype%":typeof Map>"u"||!k||!S?e:S(new Map()[Symbol.iterator]()),"%Math%":Math,"%Number%":Number,"%Object%":t,"%Object.getOwnPropertyDescriptor%":w,"%parseFloat%":parseFloat,"%parseInt%":parseInt,"%Promise%":typeof Promise>"u"?e:Promise,"%Proxy%":typeof Proxy>"u"?e:Proxy,"%RangeError%":i,"%ReferenceError%":s,"%Reflect%":typeof Reflect>"u"?e:Reflect,"%RegExp%":RegExp,"%Set%":typeof Set>"u"?e:Set,"%SetIteratorPrototype%":typeof Set>"u"||!k||!S?e:S(new Set()[Symbol.iterator]()),"%SharedArrayBuffer%":typeof SharedArrayBuffer>"u"?e:SharedArrayBuffer,"%String%":String,"%StringIteratorPrototype%":k&&S?S(""[Symbol.iterator]()):e,"%Symbol%":k?Symbol:e,"%SyntaxError%":o,"%ThrowTypeError%":x,"%TypedArray%":g,"%TypeError%":a,"%Uint8Array%":typeof Uint8Array>"u"?e:Uint8Array,"%Uint8ClampedArray%":typeof Uint8ClampedArray>"u"?e:Uint8ClampedArray,"%Uint16Array%":typeof Uint16Array>"u"?e:Uint16Array,"%Uint32Array%":typeof Uint32Array>"u"?e:Uint32Array,"%URIError%":l,"%WeakMap%":typeof WeakMap>"u"?e:WeakMap,"%WeakRef%":typeof WeakRef>"u"?e:WeakRef,"%WeakSet%":typeof WeakSet>"u"?e:WeakSet,"%Function.prototype.call%":I,"%Function.prototype.apply%":T,"%Object.defineProperty%":v,"%Object.getPrototypeOf%":N,"%Math.abs%":u,"%Math.floor%":c,"%Math.max%":d,"%Math.min%":p,"%Math.pow%":m,"%Math.round%":h,"%Math.sign%":f,"%Reflect.getPrototypeOf%":A};if(S)try{null.error}catch(Ye){var B=S(S(Ye));L["%Error.prototype%"]=B}var M=function Ye(he){var Re;if(he==="%AsyncFunction%")Re=y("async function () {}");else if(he==="%GeneratorFunction%")Re=y("function* () {}");else if(he==="%AsyncGeneratorFunction%")Re=y("async function* () {}");else if(he==="%AsyncGenerator%"){var Se=Ye("%AsyncGeneratorFunction%");Se&&(Re=Se.prototype)}else if(he==="%AsyncIteratorPrototype%"){var E=Ye("%AsyncGenerator%");E&&S&&(Re=S(E.prototype))}return L[he]=Re,Re},z={__proto__:null,"%ArrayBufferPrototype%":["ArrayBuffer","prototype"],"%ArrayPrototype%":["Array","prototype"],"%ArrayProto_entries%":["Array","prototype","entries"],"%ArrayProto_forEach%":["Array","prototype","forEach"],"%ArrayProto_keys%":["Array","prototype","keys"],"%ArrayProto_values%":["Array","prototype","values"],"%AsyncFunctionPrototype%":["AsyncFunction","prototype"],"%AsyncGenerator%":["AsyncGeneratorFunction","prototype"],"%AsyncGeneratorPrototype%":["AsyncGeneratorFunction","prototype","prototype"],"%BooleanPrototype%":["Boolean","prototype"],"%DataViewPrototype%":["DataView","prototype"],"%DatePrototype%":["Date","prototype"],"%ErrorPrototype%":["Error","prototype"],"%EvalErrorPrototype%":["EvalError","prototype"],"%Float32ArrayPrototype%":["Float32Array","prototype"],"%Float64ArrayPrototype%":["Float64Array","prototype"],"%FunctionPrototype%":["Function","prototype"],"%Generator%":["GeneratorFunction","prototype"],"%GeneratorPrototype%":["GeneratorFunction","prototype","prototype"],"%Int8ArrayPrototype%":["Int8Array","prototype"],"%Int16ArrayPrototype%":["Int16Array","prototype"],"%Int32ArrayPrototype%":["Int32Array","prototype"],"%JSONParse%":["JSON","parse"],"%JSONStringify%":["JSON","stringify"],"%MapPrototype%":["Map","prototype"],"%NumberPrototype%":["Number","prototype"],"%ObjectPrototype%":["Object","prototype"],"%ObjProto_toString%":["Object","prototype","toString"],"%ObjProto_valueOf%":["Object","prototype","valueOf"],"%PromisePrototype%":["Promise","prototype"],"%PromiseProto_then%":["Promise","prototype","then"],"%Promise_all%":["Promise","all"],"%Promise_reject%":["Promise","reject"],"%Promise_resolve%":["Promise","resolve"],"%RangeErrorPrototype%":["RangeError","prototype"],"%ReferenceErrorPrototype%":["ReferenceError","prototype"],"%RegExpPrototype%":["RegExp","prototype"],"%SetPrototype%":["Set","prototype"],"%SharedArrayBufferPrototype%":["SharedArrayBuffer","prototype"],"%StringPrototype%":["String","prototype"],"%SymbolPrototype%":["Symbol","prototype"],"%SyntaxErrorPrototype%":["SyntaxError","prototype"],"%TypedArrayPrototype%":["TypedArray","prototype"],"%TypeErrorPrototype%":["TypeError","prototype"],"%Uint8ArrayPrototype%":["Uint8Array","prototype"],"%Uint8ClampedArrayPrototype%":["Uint8ClampedArray","prototype"],"%Uint16ArrayPrototype%":["Uint16Array","prototype"],"%Uint32ArrayPrototype%":["Uint32Array","prototype"],"%URIErrorPrototype%":["URIError","prototype"],"%WeakMapPrototype%":["WeakMap","prototype"],"%WeakSetPrototype%":["WeakSet","prototype"]},P=Lc(),Z=i2(),be=P.call(I,Array.prototype.concat),le=P.call(T,Array.prototype.splice),qe=P.call(I,String.prototype.replace),vr=P.call(I,String.prototype.slice),Mr=P.call(I,RegExp.prototype.exec),yr=/[^%.[\]]+|\[(?:(-?\d+(?:\.\d+)?)|(["'])((?:(?!\2)[^\\]|\\.)*?)\2)\]|(?=(?:\.|\[\])(?:\.|\[\]|%$))/g,lt=/\\(\\)?/g,qt=function(he){var Re=vr(he,0,1),Se=vr(he,-1);if(Re==="%"&&Se!=="%")throw new o("invalid intrinsic syntax, expected closing `%`");if(Se==="%"&&Re!=="%")throw new o("invalid intrinsic syntax, expected opening `%`");var E=[];return qe(he,yr,function(F,U,q,H){E[E.length]=q?qe(H,lt,"$1"):U||F}),E},lr=function(he,Re){var Se=he,E;if(Z(z,Se)&&(E=z[Se],Se="%"+E[0]+"%"),Z(L,Se)){var F=L[Se];if(F===C&&(F=M(Se)),typeof F>"u"&&!Re)throw new a("intrinsic "+he+" exists, but is not available. Please file an issue!");return{alias:E,name:Se,value:F}}throw new o("intrinsic "+he+" does not exist!")};return ad=function(he,Re){if(typeof he!="string"||he.length===0)throw new a("intrinsic name must be a non-empty string");if(arguments.length>1&&typeof Re!="boolean")throw new a('"allowMissing" argument must be a boolean');if(Mr(/^%?[^%]*%?$/,he)===null)throw new o("`%` may not be present anywhere but at the beginning and end of the intrinsic name");var Se=qt(he),E=Se.length>0?Se[0]:"",F=lr("%"+E+"%",Re),U=F.name,q=F.value,H=!1,j=F.alias;j&&(E=j[0],le(Se,be([0,1],j)));for(var J=1,X=!0;J<Se.length;J+=1){var G=Se[J],K=vr(G,0,1),ne=vr(G,-1);if((K==='"'||K==="'"||K==="`"||ne==='"'||ne==="'"||ne==="`")&&K!==ne)throw new o("property names with quotes must have matching quotes");if((G==="constructor"||!X)&&(H=!0),E+="."+G,U="%"+E+"%",Z(L,U))q=L[U];else if(q!=null){if(!(G in q)){if(!Re)throw new a("base intrinsic for "+he+" exists, but the property is not available.");return}if(w&&J+1>=Se.length){var Q=w(q,G);X=!!Q,X&&"get"in Q&&!("originalValue"in Q.get)?q=Q.get:q=q[G]}else X=Z(q,G),q=q[G];X&&!H&&(L[U]=q)}}return q},ad}var ld,gb;function l0(){if(gb)return ld;gb=1;var e=gp(),t=a0(),r=t([e("%String.prototype.indexOf%")]);return ld=function(i,s){var o=e(i,!!s);return typeof o=="function"&&r(i,".prototype.")>-1?t([o]):o},ld}var cd,bb;function c0(){if(bb)return cd;bb=1;var e=gp(),t=l0(),r=Fc(),n=Ts(),i=e("%Map%",!0),s=t("Map.prototype.get",!0),o=t("Map.prototype.set",!0),a=t("Map.prototype.has",!0),l=t("Map.prototype.delete",!0),u=t("Map.prototype.size",!0);return cd=!!i&&function(){var d,p={assert:function(m){if(!p.has(m))throw new n("Side channel does not contain "+r(m))},delete:function(m){if(d){var h=l(d,m);return u(d)===0&&(d=void 0),h}return!1},get:function(m){if(d)return s(d,m)},has:function(m){return d?a(d,m):!1},set:function(m,h){d||(d=new i),o(d,m,h)}};return p},cd}var ud,vb;function s2(){if(vb)return ud;vb=1;var e=gp(),t=l0(),r=Fc(),n=c0(),i=Ts(),s=e("%WeakMap%",!0),o=t("WeakMap.prototype.get",!0),a=t("WeakMap.prototype.set",!0),l=t("WeakMap.prototype.has",!0),u=t("WeakMap.prototype.delete",!0);return ud=s?function(){var d,p,m={assert:function(h){if(!m.has(h))throw new i("Side channel does not contain "+r(h))},delete:function(h){if(s&&h&&(typeof h=="object"||typeof h=="function")){if(d)return u(d,h)}else if(n&&p)return p.delete(h);return!1},get:function(h){return s&&h&&(typeof h=="object"||typeof h=="function")&&d?o(d,h):p&&p.get(h)},has:function(h){return s&&h&&(typeof h=="object"||typeof h=="function")&&d?l(d,h):!!p&&p.has(h)},set:function(h,f){s&&h&&(typeof h=="object"||typeof h=="function")?(d||(d=new s),a(d,h,f)):n&&(p||(p=n()),p.set(h,f))}};return m}:n,ud}var dd,yb;function u0(){if(yb)return dd;yb=1;var e=Ts(),t=Fc(),r=LF(),n=c0(),i=s2(),s=i||n||r;return dd=function(){var a,l={assert:function(u){if(!l.has(u))throw new e("Side channel does not contain "+t(u))},delete:function(u){return!!a&&a.delete(u)},get:function(u){return a&&a.get(u)},has:function(u){return!!a&&a.has(u)},set:function(u,c){a||(a=s()),a.set(u,c)}};return l},dd}var hd,_b;function bp(){if(_b)return hd;_b=1;var e=String.prototype.replace,t=/%20/g,r={RFC1738:"RFC1738",RFC3986:"RFC3986"};return hd={default:r.RFC3986,formatters:{RFC1738:function(n){return e.call(n,t,"+")},RFC3986:function(n){return String(n)}},RFC1738:r.RFC1738,RFC3986:r.RFC3986},hd}var fd,wb;function d0(){if(wb)return fd;wb=1;var e=bp(),t=u0(),r=Object.prototype.hasOwnProperty,n=Array.isArray,i=t(),s=function(S,N){return i.set(S,N),S},o=function(S){return i.has(S)},a=function(S){return i.get(S)},l=function(S,N){i.set(S,N)},u=(function(){for(var k=[],S=0;S<256;++S)k.push("%"+((S<16?"0":"")+S.toString(16)).toUpperCase());return k})(),c=function(S){for(;S.length>1;){var N=S.pop(),A=N.obj[N.prop];if(n(A)){for(var T=[],I=0;I<A.length;++I)typeof A[I]<"u"&&T.push(A[I]);N.obj[N.prop]=T}}},d=function(S,N){for(var A=N&&N.plainObjects?{__proto__:null}:{},T=0;T<S.length;++T)typeof S[T]<"u"&&(A[T]=S[T]);return A},p=function k(S,N,A){if(!N)return S;if(typeof N!="object"&&typeof N!="function"){if(n(S))S.push(N);else if(S&&typeof S=="object")if(o(S)){var T=a(S)+1;S[T]=N,l(S,T)}else(A&&(A.plainObjects||A.allowPrototypes)||!r.call(Object.prototype,N))&&(S[N]=!0);else return[S,N];return S}if(!S||typeof S!="object"){if(o(N)){for(var I=Object.keys(N),C=A&&A.plainObjects?{__proto__:null,0:S}:{0:S},g=0;g<I.length;g++){var L=parseInt(I[g],10);C[L+1]=N[I[g]]}return s(C,a(N)+1)}return[S].concat(N)}var B=S;return n(S)&&!n(N)&&(B=d(S,A)),n(S)&&n(N)?(N.forEach(function(M,z){if(r.call(S,z)){var P=S[z];P&&typeof P=="object"&&M&&typeof M=="object"?S[z]=k(P,M,A):S.push(M)}else S[z]=M}),S):Object.keys(N).reduce(function(M,z){var P=N[z];return r.call(M,z)?M[z]=k(M[z],P,A):M[z]=P,M},B)},m=function(S,N){return Object.keys(N).reduce(function(A,T){return A[T]=N[T],A},S)},h=function(k,S,N){var A=k.replace(/\+/g," ");if(N==="iso-8859-1")return A.replace(/%[0-9a-f]{2}/gi,unescape);try{return decodeURIComponent(A)}catch{return A}},f=1024,b=function(S,N,A,T,I){if(S.length===0)return S;var C=S;if(typeof S=="symbol"?C=Symbol.prototype.toString.call(S):typeof S!="string"&&(C=String(S)),A==="iso-8859-1")return escape(C).replace(/%u[0-9a-f]{4}/gi,function(Z){return"%26%23"+parseInt(Z.slice(2),16)+"%3B"});for(var g="",L=0;L<C.length;L+=f){for(var B=C.length>=f?C.slice(L,L+f):C,M=[],z=0;z<B.length;++z){var P=B.charCodeAt(z);if(P===45||P===46||P===95||P===126||P>=48&&P<=57||P>=65&&P<=90||P>=97&&P<=122||I===e.RFC1738&&(P===40||P===41)){M[M.length]=B.charAt(z);continue}if(P<128){M[M.length]=u[P];continue}if(P<2048){M[M.length]=u[192|P>>6]+u[128|P&63];continue}if(P<55296||P>=57344){M[M.length]=u[224|P>>12]+u[128|P>>6&63]+u[128|P&63];continue}z+=1,P=65536+((P&1023)<<10|B.charCodeAt(z)&1023),M[M.length]=u[240|P>>18]+u[128|P>>12&63]+u[128|P>>6&63]+u[128|P&63]}g+=M.join("")}return g},y=function(S){for(var N=[{obj:{o:S},prop:"o"}],A=[],T=0;T<N.length;++T)for(var I=N[T],C=I.obj[I.prop],g=Object.keys(C),L=0;L<g.length;++L){var B=g[L],M=C[B];typeof M=="object"&&M!==null&&A.indexOf(M)===-1&&(N.push({obj:C,prop:B}),A.push(M))}return c(N),S},w=function(S){return Object.prototype.toString.call(S)==="[object RegExp]"},v=function(S){return!S||typeof S!="object"?!1:!!(S.constructor&&S.constructor.isBuffer&&S.constructor.isBuffer(S))},_=function(S,N,A,T){if(o(S)){var I=a(S)+1;return S[I]=N,l(S,I),S}var C=[].concat(S,N);return C.length>A?s(d(C,{plainObjects:T}),C.length-1):C},x=function(S,N){if(n(S)){for(var A=[],T=0;T<S.length;T+=1)A.push(N(S[T]));return A}return N(S)};return fd={arrayToObject:d,assign:m,combine:_,compact:y,decode:h,encode:b,isBuffer:v,isOverflow:o,isRegExp:w,maybeMap:x,merge:p},fd}var pd,Eb;function o2(){if(Eb)return pd;Eb=1;var e=u0(),t=d0(),r=bp(),n=Object.prototype.hasOwnProperty,i={brackets:function(b){return b+"[]"},comma:"comma",indices:function(b,y){return b+"["+y+"]"},repeat:function(b){return b}},s=Array.isArray,o=Array.prototype.push,a=function(f,b){o.apply(f,s(b)?b:[b])},l=Date.prototype.toISOString,u=r.default,c={addQueryPrefix:!1,allowDots:!1,allowEmptyArrays:!1,arrayFormat:"indices",charset:"utf-8",charsetSentinel:!1,commaRoundTrip:!1,delimiter:"&",encode:!0,encodeDotInKeys:!1,encoder:t.encode,encodeValuesOnly:!1,filter:void 0,format:u,formatter:r.formatters[u],indices:!1,serializeDate:function(b){return l.call(b)},skipNulls:!1,strictNullHandling:!1},d=function(b){return typeof b=="string"||typeof b=="number"||typeof b=="boolean"||typeof b=="symbol"||typeof b=="bigint"},p={},m=function f(b,y,w,v,_,x,k,S,N,A,T,I,C,g,L,B,M,z){for(var P=b,Z=z,be=0,le=!1;(Z=Z.get(p))!==void 0&&!le;){var qe=Z.get(b);if(be+=1,typeof qe<"u"){if(qe===be)throw new RangeError("Cyclic object value");le=!0}typeof Z.get(p)>"u"&&(be=0)}if(typeof A=="function"?P=A(y,P):P instanceof Date?P=C(P):w==="comma"&&s(P)&&(P=t.maybeMap(P,function(U){return U instanceof Date?C(U):U})),P===null){if(x)return N&&!B?N(y,c.encoder,M,"key",g):y;P=""}if(d(P)||t.isBuffer(P)){if(N){var vr=B?y:N(y,c.encoder,M,"key",g);return[L(vr)+"="+L(N(P,c.encoder,M,"value",g))]}return[L(y)+"="+L(String(P))]}var Mr=[];if(typeof P>"u")return Mr;var yr;if(w==="comma"&&s(P))B&&N&&(P=t.maybeMap(P,N)),yr=[{value:P.length>0?P.join(",")||null:void 0}];else if(s(A))yr=A;else{var lt=Object.keys(P);yr=T?lt.sort(T):lt}var qt=S?String(y).replace(/\./g,"%2E"):String(y),lr=v&&s(P)&&P.length===1?qt+"[]":qt;if(_&&s(P)&&P.length===0)return lr+"[]";for(var Ye=0;Ye<yr.length;++Ye){var he=yr[Ye],Re=typeof he=="object"&&he&&typeof he.value<"u"?he.value:P[he];if(!(k&&Re===null)){var Se=I&&S?String(he).replace(/\./g,"%2E"):String(he),E=s(P)?typeof w=="function"?w(lr,Se):lr:lr+(I?"."+Se:"["+Se+"]");z.set(b,be);var F=e();F.set(p,z),a(Mr,f(Re,E,w,v,_,x,k,S,w==="comma"&&B&&s(P)?null:N,A,T,I,C,g,L,B,M,F))}}return Mr},h=function(b){if(!b)return c;if(typeof b.allowEmptyArrays<"u"&&typeof b.allowEmptyArrays!="boolean")throw new TypeError("`allowEmptyArrays` option can only be `true` or `false`, when provided");if(typeof b.encodeDotInKeys<"u"&&typeof b.encodeDotInKeys!="boolean")throw new TypeError("`encodeDotInKeys` option can only be `true` or `false`, when provided");if(b.encoder!==null&&typeof b.encoder<"u"&&typeof b.encoder!="function")throw new TypeError("Encoder has to be a function.");var y=b.charset||c.charset;if(typeof b.charset<"u"&&b.charset!=="utf-8"&&b.charset!=="iso-8859-1")throw new TypeError("The charset option must be either utf-8, iso-8859-1, or undefined");var w=r.default;if(typeof b.format<"u"){if(!n.call(r.formatters,b.format))throw new TypeError("Unknown format option provided.");w=b.format}var v=r.formatters[w],_=c.filter;(typeof b.filter=="function"||s(b.filter))&&(_=b.filter);var x;if(b.arrayFormat in i?x=b.arrayFormat:"indices"in b?x=b.indices?"indices":"repeat":x=c.arrayFormat,"commaRoundTrip"in b&&typeof b.commaRoundTrip!="boolean")throw new TypeError("`commaRoundTrip` must be a boolean, or absent");var k=typeof b.allowDots>"u"?b.encodeDotInKeys===!0?!0:c.allowDots:!!b.allowDots;return{addQueryPrefix:typeof b.addQueryPrefix=="boolean"?b.addQueryPrefix:c.addQueryPrefix,allowDots:k,allowEmptyArrays:typeof b.allowEmptyArrays=="boolean"?!!b.allowEmptyArrays:c.allowEmptyArrays,arrayFormat:x,charset:y,charsetSentinel:typeof b.charsetSentinel=="boolean"?b.charsetSentinel:c.charsetSentinel,commaRoundTrip:!!b.commaRoundTrip,delimiter:typeof b.delimiter>"u"?c.delimiter:b.delimiter,encode:typeof b.encode=="boolean"?b.encode:c.encode,encodeDotInKeys:typeof b.encodeDotInKeys=="boolean"?b.encodeDotInKeys:c.encodeDotInKeys,encoder:typeof b.encoder=="function"?b.encoder:c.encoder,encodeValuesOnly:typeof b.encodeValuesOnly=="boolean"?b.encodeValuesOnly:c.encodeValuesOnly,filter:_,format:w,formatter:v,serializeDate:typeof b.serializeDate=="function"?b.serializeDate:c.serializeDate,skipNulls:typeof b.skipNulls=="boolean"?b.skipNulls:c.skipNulls,sort:typeof b.sort=="function"?b.sort:null,strictNullHandling:typeof b.strictNullHandling=="boolean"?b.strictNullHandling:c.strictNullHandling}};return pd=function(f,b){var y=f,w=h(b),v,_;typeof w.filter=="function"?(_=w.filter,y=_("",y)):s(w.filter)&&(_=w.filter,v=_);var x=[];if(typeof y!="object"||y===null)return"";var k=i[w.arrayFormat],S=k==="comma"&&w.commaRoundTrip;v||(v=Object.keys(y)),w.sort&&v.sort(w.sort);for(var N=e(),A=0;A<v.length;++A){var T=v[A],I=y[T];w.skipNulls&&I===null||a(x,m(I,T,k,S,w.allowEmptyArrays,w.strictNullHandling,w.skipNulls,w.encodeDotInKeys,w.encode?w.encoder:null,w.filter,w.sort,w.allowDots,w.serializeDate,w.format,w.formatter,w.encodeValuesOnly,w.charset,N))}var C=x.join(w.delimiter),g=w.addQueryPrefix===!0?"?":"";return w.charsetSentinel&&(w.charset==="iso-8859-1"?g+="utf8=%26%2310003%3B&":g+="utf8=%E2%9C%93&"),C.length>0?g+C:""},pd}var md,xb;function a2(){if(xb)return md;xb=1;var e=d0(),t=Object.prototype.hasOwnProperty,r=Array.isArray,n={allowDots:!1,allowEmptyArrays:!1,allowPrototypes:!1,allowSparse:!1,arrayLimit:20,charset:"utf-8",charsetSentinel:!1,comma:!1,decodeDotInKeys:!1,decoder:e.decode,delimiter:"&",depth:5,duplicates:"combine",ignoreQueryPrefix:!1,interpretNumericEntities:!1,parameterLimit:1e3,parseArrays:!0,plainObjects:!1,strictDepth:!1,strictNullHandling:!1,throwOnLimitExceeded:!1},i=function(m){return m.replace(/&#(\d+);/g,function(h,f){return String.fromCharCode(parseInt(f,10))})},s=function(m,h,f){if(m&&typeof m=="string"&&h.comma&&m.indexOf(",")>-1)return m.split(",");if(h.throwOnLimitExceeded&&f>=h.arrayLimit)throw new RangeError("Array limit exceeded. Only "+h.arrayLimit+" element"+(h.arrayLimit===1?"":"s")+" allowed in an array.");return m},o="utf8=%26%2310003%3B",a="utf8=%E2%9C%93",l=function(h,f){var b={__proto__:null},y=f.ignoreQueryPrefix?h.replace(/^\?/,""):h;y=y.replace(/%5B/gi,"[").replace(/%5D/gi,"]");var w=f.parameterLimit===1/0?void 0:f.parameterLimit,v=y.split(f.delimiter,f.throwOnLimitExceeded?w+1:w);if(f.throwOnLimitExceeded&&v.length>w)throw new RangeError("Parameter limit exceeded. Only "+w+" parameter"+(w===1?"":"s")+" allowed.");var _=-1,x,k=f.charset;if(f.charsetSentinel)for(x=0;x<v.length;++x)v[x].indexOf("utf8=")===0&&(v[x]===a?k="utf-8":v[x]===o&&(k="iso-8859-1"),_=x,x=v.length);for(x=0;x<v.length;++x)if(x!==_){var S=v[x],N=S.indexOf("]="),A=N===-1?S.indexOf("="):N+1,T,I;if(A===-1?(T=f.decoder(S,n.decoder,k,"key"),I=f.strictNullHandling?null:""):(T=f.decoder(S.slice(0,A),n.decoder,k,"key"),T!==null&&(I=e.maybeMap(s(S.slice(A+1),f,r(b[T])?b[T].length:0),function(g){return f.decoder(g,n.decoder,k,"value")}))),I&&f.interpretNumericEntities&&k==="iso-8859-1"&&(I=i(String(I))),S.indexOf("[]=")>-1&&(I=r(I)?[I]:I),T!==null){var C=t.call(b,T);C&&f.duplicates==="combine"?b[T]=e.combine(b[T],I,f.arrayLimit,f.plainObjects):(!C||f.duplicates==="last")&&(b[T]=I)}}return b},u=function(m,h,f,b){var y=0;if(m.length>0&&m[m.length-1]==="[]"){var w=m.slice(0,-1).join("");y=Array.isArray(h)&&h[w]?h[w].length:0}for(var v=b?h:s(h,f,y),_=m.length-1;_>=0;--_){var x,k=m[_];if(k==="[]"&&f.parseArrays)e.isOverflow(v)?x=v:x=f.allowEmptyArrays&&(v===""||f.strictNullHandling&&v===null)?[]:e.combine([],v,f.arrayLimit,f.plainObjects);else{x=f.plainObjects?{__proto__:null}:{};var S=k.charAt(0)==="["&&k.charAt(k.length-1)==="]"?k.slice(1,-1):k,N=f.decodeDotInKeys?S.replace(/%2E/g,"."):S,A=parseInt(N,10);!f.parseArrays&&N===""?x={0:v}:!isNaN(A)&&k!==N&&String(A)===N&&A>=0&&f.parseArrays&&A<=f.arrayLimit?(x=[],x[A]=v):N!=="__proto__"&&(x[N]=v)}v=x}return v},c=function(h,f){var b=f.allowDots?h.replace(/\.([^.[]+)/g,"[$1]"):h;if(f.depth<=0)return!f.plainObjects&&t.call(Object.prototype,b)&&!f.allowPrototypes?void 0:[b];var y=/(\[[^[\]]*])/,w=/(\[[^[\]]*])/g,v=y.exec(b),_=v?b.slice(0,v.index):b,x=[];if(_){if(!f.plainObjects&&t.call(Object.prototype,_)&&!f.allowPrototypes)return;x.push(_)}for(var k=0;(v=w.exec(b))!==null&&k<f.depth;){k+=1;var S=v[1].slice(1,-1);if(!f.plainObjects&&t.call(Object.prototype,S)&&!f.allowPrototypes)return;x.push(v[1])}if(v){if(f.strictDepth===!0)throw new RangeError("Input depth exceeded depth option of "+f.depth+" and strictDepth is true");x.push("["+b.slice(v.index)+"]")}return x},d=function(h,f,b,y){if(h){var w=c(h,b);if(w)return u(w,f,b,y)}},p=function(h){if(!h)return n;if(typeof h.allowEmptyArrays<"u"&&typeof h.allowEmptyArrays!="boolean")throw new TypeError("`allowEmptyArrays` option can only be `true` or `false`, when provided");if(typeof h.decodeDotInKeys<"u"&&typeof h.decodeDotInKeys!="boolean")throw new TypeError("`decodeDotInKeys` option can only be `true` or `false`, when provided");if(h.decoder!==null&&typeof h.decoder<"u"&&typeof h.decoder!="function")throw new TypeError("Decoder has to be a function.");if(typeof h.charset<"u"&&h.charset!=="utf-8"&&h.charset!=="iso-8859-1")throw new TypeError("The charset option must be either utf-8, iso-8859-1, or undefined");if(typeof h.throwOnLimitExceeded<"u"&&typeof h.throwOnLimitExceeded!="boolean")throw new TypeError("`throwOnLimitExceeded` option must be a boolean");var f=typeof h.charset>"u"?n.charset:h.charset,b=typeof h.duplicates>"u"?n.duplicates:h.duplicates;if(b!=="combine"&&b!=="first"&&b!=="last")throw new TypeError("The duplicates option must be either combine, first, or last");var y=typeof h.allowDots>"u"?h.decodeDotInKeys===!0?!0:n.allowDots:!!h.allowDots;return{allowDots:y,allowEmptyArrays:typeof h.allowEmptyArrays=="boolean"?!!h.allowEmptyArrays:n.allowEmptyArrays,allowPrototypes:typeof h.allowPrototypes=="boolean"?h.allowPrototypes:n.allowPrototypes,allowSparse:typeof h.allowSparse=="boolean"?h.allowSparse:n.allowSparse,arrayLimit:typeof h.arrayLimit=="number"?h.arrayLimit:n.arrayLimit,charset:f,charsetSentinel:typeof h.charsetSentinel=="boolean"?h.charsetSentinel:n.charsetSentinel,comma:typeof h.comma=="boolean"?h.comma:n.comma,decodeDotInKeys:typeof h.decodeDotInKeys=="boolean"?h.decodeDotInKeys:n.decodeDotInKeys,decoder:typeof h.decoder=="function"?h.decoder:n.decoder,delimiter:typeof h.delimiter=="string"||e.isRegExp(h.delimiter)?h.delimiter:n.delimiter,depth:typeof h.depth=="number"||h.depth===!1?+h.depth:n.depth,duplicates:b,ignoreQueryPrefix:h.ignoreQueryPrefix===!0,interpretNumericEntities:typeof h.interpretNumericEntities=="boolean"?h.interpretNumericEntities:n.interpretNumericEntities,parameterLimit:typeof h.parameterLimit=="number"?h.parameterLimit:n.parameterLimit,parseArrays:h.parseArrays!==!1,plainObjects:typeof h.plainObjects=="boolean"?h.plainObjects:n.plainObjects,strictDepth:typeof h.strictDepth=="boolean"?!!h.strictDepth:n.strictDepth,strictNullHandling:typeof h.strictNullHandling=="boolean"?h.strictNullHandling:n.strictNullHandling,throwOnLimitExceeded:typeof h.throwOnLimitExceeded=="boolean"?h.throwOnLimitExceeded:!1}};return md=function(m,h){var f=p(h);if(m===""||m===null||typeof m>"u")return f.plainObjects?{__proto__:null}:{};for(var b=typeof m=="string"?l(m,f):m,y=f.plainObjects?{__proto__:null}:{},w=Object.keys(b),v=0;v<w.length;++v){var _=w[v],x=d(_,b[_],f,typeof m=="string");y=e.merge(y,x,f)}return f.allowSparse===!0?y:e.compact(y)},md}var gd,Sb;function l2(){if(Sb)return gd;Sb=1;var e=o2(),t=a2(),r=bp();return gd={formats:r,parse:t,stringify:e},gd}var Cb=l2(),c2=class{constructor(e){this.config={},this.defaults=e}extend(e){return e&&(this.defaults={...this.defaults,...e}),this}replace(e){this.config=e}get(e){return t0(this.config,e)?Cr(this.config,e):Cr(this.defaults,e)}set(e,t){typeof e=="string"?zr(this.config,e,t):Object.entries(e).forEach(([r,n])=>{zr(this.config,r,n)})}},Ii=new c2({form:{recentlySuccessfulDuration:2e3,forceIndicesArrayFormatInFormData:!0},future:{preserveEqualProps:!1,useDataInertiaHeadAttribute:!1,useDialogForErrorModal:!1,useScriptElementForInitialPage:!1},prefetch:{cacheFor:3e4,hoverDelay:75}});function Nh(e,t){let r;return function(...n){clearTimeout(r),r=setTimeout(()=>e.apply(this,n),t)}}function or(e,t){return document.dispatchEvent(new CustomEvent(`inertia:${e}`,t))}var Ab=e=>or("before",{cancelable:!0,detail:{visit:e}}),u2=e=>or("error",{detail:{errors:e}}),d2=e=>or("exception",{cancelable:!0,detail:{exception:e}}),h2=e=>or("finish",{detail:{visit:e}}),f2=e=>or("invalid",{cancelable:!0,detail:{response:e}}),p2=e=>or("beforeUpdate",{detail:{page:e}}),go=e=>or("navigate",{detail:{page:e}}),m2=e=>or("progress",{detail:{progress:e}}),g2=e=>or("start",{detail:{visit:e}}),b2=e=>or("success",{detail:{page:e}}),v2=(e,t)=>or("prefetched",{detail:{fetchedAt:Date.now(),response:e.data,visit:t}}),y2=e=>or("prefetching",{detail:{visit:e}}),$l=e=>or("flash",{detail:{flash:e}}),Ft=class{static set(e,t){typeof window<"u"&&window.sessionStorage.setItem(e,JSON.stringify(t))}static get(e){if(typeof window<"u")return JSON.parse(window.sessionStorage.getItem(e)||"null")}static merge(e,t){const r=this.get(e);r===null?this.set(e,t):this.set(e,{...r,...t})}static remove(e){typeof window<"u"&&window.sessionStorage.removeItem(e)}static removeNested(e,t){const r=this.get(e);r!==null&&(delete r[t],this.set(e,r))}static exists(e){try{return this.get(e)!==null}catch{return!1}}static clear(){typeof window<"u"&&window.sessionStorage.clear()}};Ft.locationVisitKey="inertiaLocationVisit";var _2=async e=>{if(typeof window>"u")throw new Error("Unable to encrypt history");const t=h0(),r=await f0(),n=await A2(r);if(!n)throw new Error("Unable to encrypt history");return await E2(t,n,e)},gs={key:"historyKey",iv:"historyIv"},w2=async e=>{const t=h0(),r=await f0();if(!r)throw new Error("Unable to decrypt history");return await x2(t,r,e)},E2=async(e,t,r)=>{if(typeof window>"u")throw new Error("Unable to encrypt history");if(typeof window.crypto.subtle>"u")return console.warn("Encryption is not supported in this environment. SSL is required."),Promise.resolve(r);const n=new TextEncoder,i=JSON.stringify(r),s=new Uint8Array(i.length*3),o=n.encodeInto(i,s);return window.crypto.subtle.encrypt({name:"AES-GCM",iv:e},t,s.subarray(0,o.written))},x2=async(e,t,r)=>{if(typeof window.crypto.subtle>"u")return console.warn("Decryption is not supported in this environment. SSL is required."),Promise.resolve(r);const n=await window.crypto.subtle.decrypt({name:"AES-GCM",iv:e},t,r);return JSON.parse(new TextDecoder().decode(n))},h0=()=>{const e=Ft.get(gs.iv);if(e)return new Uint8Array(e);const t=window.crypto.getRandomValues(new Uint8Array(12));return Ft.set(gs.iv,Array.from(t)),t},S2=async()=>typeof window.crypto.subtle>"u"?(console.warn("Encryption is not supported in this environment. SSL is required."),Promise.resolve(null)):window.crypto.subtle.generateKey({name:"AES-GCM",length:256},!0,["encrypt","decrypt"]),C2=async e=>{if(typeof window.crypto.subtle>"u")return console.warn("Encryption is not supported in this environment. SSL is required."),Promise.resolve();const t=await window.crypto.subtle.exportKey("raw",e);Ft.set(gs.key,Array.from(new Uint8Array(t)))},A2=async e=>{if(e)return e;const t=await S2();return t?(await C2(t),t):null},f0=async()=>{const e=Ft.get(gs.key);return e?await window.crypto.subtle.importKey("raw",new Uint8Array(e),{name:"AES-GCM",length:256},!0,["encrypt","decrypt"]):null},p0=(e,t,r)=>{if(e===t)return!0;for(const n in e)if(!r.includes(n)&&e[n]!==t[n]&&!T2(e[n],t[n]))return!1;for(const n in t)if(!r.includes(n)&&!(n in e))return!1;return!0},T2=(e,t)=>{switch(typeof e){case"object":return p0(e,t,[]);case"function":return e.toString()===t.toString();default:return e===t}},k2={ms:1,s:1e3,m:1e3*60,h:1e3*60*60,d:1e3*60*60*24},Tb=e=>{if(typeof e=="number")return e;for(const[t,r]of Object.entries(k2))if(e.endsWith(t))return parseFloat(e)*r;return parseInt(e)},O2=class{constructor(){this.cached=[],this.inFlightRequests=[],this.removalTimers=[],this.currentUseId=null}add(e,t,{cacheFor:r,cacheTags:n}){if(this.findInFlight(e))return Promise.resolve();const s=this.findCached(e);if(!e.fresh&&s&&s.staleTimestamp>Date.now())return Promise.resolve();const[o,a]=this.extractStaleValues(r),l=new Promise((u,c)=>{t({...e,onCancel:()=>{this.remove(e),e.onCancel(),c()},onError:d=>{this.remove(e),e.onError(d),c()},onPrefetching(d){e.onPrefetching(d)},onPrefetched(d,p){e.onPrefetched(d,p)},onPrefetchResponse(d){u(d)},onPrefetchError(d){Vr.removeFromInFlight(e),c(d)}})}).then(u=>{this.remove(e);const c=u.getPageResponse();ee.mergeOncePropsIntoResponse(c),this.cached.push({params:{...e},staleTimestamp:Date.now()+o,expiresAt:Date.now()+a,response:l,singleUse:a===0,timestamp:Date.now(),inFlight:!1,tags:Array.isArray(n)?n:[n]});const d=this.getShortestOncePropTtl(c);return this.scheduleForRemoval(e,d?Math.min(a,d):a),this.removeFromInFlight(e),u.handlePrefetch(),u});return this.inFlightRequests.push({params:{...e},response:l,staleTimestamp:null,inFlight:!0}),l}removeAll(){this.cached=[],this.removalTimers.forEach(e=>{clearTimeout(e.timer)}),this.removalTimers=[]}removeByTags(e){this.cached=this.cached.filter(t=>!t.tags.some(r=>e.includes(r)))}remove(e){this.cached=this.cached.filter(t=>!this.paramsAreEqual(t.params,e)),this.clearTimer(e)}removeFromInFlight(e){this.inFlightRequests=this.inFlightRequests.filter(t=>!this.paramsAreEqual(t.params,e))}extractStaleValues(e){const[t,r]=this.cacheForToStaleAndExpires(e);return[Tb(t),Tb(r)]}cacheForToStaleAndExpires(e){if(!Array.isArray(e))return[e,e];switch(e.length){case 0:return[0,0];case 1:return[e[0],e[0]];default:return[e[0],e[1]]}}clearTimer(e){const t=this.removalTimers.find(r=>this.paramsAreEqual(r.params,e));t&&(clearTimeout(t.timer),this.removalTimers=this.removalTimers.filter(r=>r!==t))}scheduleForRemoval(e,t){if(!(typeof window>"u")&&(this.clearTimer(e),t>0)){const r=window.setTimeout(()=>this.remove(e),t);this.removalTimers.push({params:e,timer:r})}}get(e){return this.findCached(e)||this.findInFlight(e)}use(e,t){const r=`${t.url.pathname}-${Date.now()}-${Math.random().toString(36).substring(7)}`;return this.currentUseId=r,e.response.then(n=>{if(this.currentUseId===r)return n.mergeParams({...t,onPrefetched:()=>{}}),this.removeSingleUseItems(t),n.handle()})}removeSingleUseItems(e){this.cached=this.cached.filter(t=>this.paramsAreEqual(t.params,e)?!t.singleUse:!0)}findCached(e){return this.cached.find(t=>this.paramsAreEqual(t.params,e))||null}findInFlight(e){return this.inFlightRequests.find(t=>this.paramsAreEqual(t.params,e))||null}withoutPurposePrefetchHeader(e){const t=ut(e);return t.headers.Purpose==="prefetch"&&delete t.headers.Purpose,t}paramsAreEqual(e,t){return p0(this.withoutPurposePrefetchHeader(e),this.withoutPurposePrefetchHeader(t),["showProgress","replace","prefetch","preserveScroll","preserveState","onBefore","onBeforeUpdate","onStart","onProgress","onFinish","onCancel","onSuccess","onError","onFlash","onPrefetched","onCancelToken","onPrefetching","async","viewTransition"])}updateCachedOncePropsFromCurrentPage(){this.cached.forEach(e=>{e.response.then(t=>{const r=t.getPageResponse();ee.mergeOncePropsIntoResponse(r,{force:!0});for(const[o,a]of Object.entries(r.deferredProps??{})){const l=a.filter(u=>r.props[u]===void 0);l.length>0?r.deferredProps[o]=l:delete r.deferredProps[o]}const n=this.getShortestOncePropTtl(r);if(n===null)return;const i=e.expiresAt-Date.now(),s=Math.min(i,n);s>0?this.scheduleForRemoval(e.params,s):this.remove(e.params)})})}getShortestOncePropTtl(e){const t=Object.values(e.onceProps??{}).map(r=>r.expiresAt).filter(r=>!!r);return t.length===0?null:Math.min(...t)-Date.now()}},Vr=new O2,m0=(e,t=1)=>{window.requestAnimationFrame(()=>{t>1?m0(e,t-1):e()})},N2=(e,t=!1)=>{if(typeof window>"u")return null;if(!t){const n=document.getElementById(e);if(n?.dataset.page)return JSON.parse(n.dataset.page)}const r=document.querySelector(`script[data-page="${e}"][type="application/json"]`);return r?.textContent?JSON.parse(r.textContent):null},Ys=typeof window>"u",P2=!Ys&&/Firefox/i.test(window.navigator.userAgent),$t=class{static save(){_e.saveScrollPositions(this.getScrollRegions())}static getScrollRegions(){return Array.from(this.regions()).map(e=>({top:e.scrollTop,left:e.scrollLeft}))}static regions(){return document.querySelectorAll("[scroll-region]")}static scrollToTop(){if(P2&&getComputedStyle(document.documentElement).scrollBehavior==="smooth")return m0(()=>window.scrollTo(0,0),2);window.scrollTo(0,0)}static reset(){(Ys?null:window.location.hash)||this.scrollToTop(),this.regions().forEach(t=>{typeof t.scrollTo=="function"?t.scrollTo(0,0):(t.scrollTop=0,t.scrollLeft=0)}),this.save(),this.scrollToAnchor()}static scrollToAnchor(){const e=Ys?null:window.location.hash;e&&setTimeout(()=>{const t=document.getElementById(e.slice(1));t?t.scrollIntoView():this.scrollToTop()})}static restore(e){Ys||window.requestAnimationFrame(()=>{this.restoreDocument(),this.restoreScrollRegions(e)})}static restoreScrollRegions(e){Ys||this.regions().forEach((t,r)=>{const n=e[r];n&&(typeof t.scrollTo=="function"?t.scrollTo(n.left,n.top):(t.scrollTop=n.top,t.scrollLeft=n.left))})}static restoreDocument(){const e=_e.getDocumentScrollPosition();window.scrollTo(e.left,e.top)}static onScroll(e){const t=e.target;typeof t.hasAttribute=="function"&&t.hasAttribute("scroll-region")&&this.save()}static onWindowScroll(){_e.saveDocumentScrollPosition({top:window.scrollY,left:window.scrollX})}},I2=e=>typeof File<"u"&&e instanceof File||e instanceof Blob||typeof FileList<"u"&&e instanceof FileList&&e.length>0;function Ph(e){return I2(e)||e instanceof FormData&&Array.from(e.values()).some(t=>Ph(t))||typeof e=="object"&&e!==null&&Object.values(e).some(t=>Ph(t))}var Ih=e=>e instanceof FormData;function g0(e,t=new FormData,r=null,n="brackets"){e=e||{};for(const i in e)Object.prototype.hasOwnProperty.call(e,i)&&v0(t,b0(r,i,"indices"),e[i],n);return t}function b0(e,t,r){return e?r==="brackets"?`${e}[]`:`${e}[${t}]`:t}function v0(e,t,r,n){if(Array.isArray(r))return Array.from(r.keys()).forEach(i=>v0(e,b0(t,i.toString(),n),r[i],n));if(r instanceof Date)return e.append(t,r.toISOString());if(r instanceof File)return e.append(t,r,r.name);if(r instanceof Blob)return e.append(t,r);if(typeof r=="boolean")return e.append(t,r?"1":"0");if(typeof r=="string")return e.append(t,r);if(typeof r=="number")return e.append(t,`${r}`);if(r==null)return e.append(t,"");g0(r,e,t,n)}function un(e){return new URL(e.toString(),typeof window>"u"?void 0:window.location.toString())}var F2=(e,t,r,n,i)=>{let s=typeof e=="string"?un(e):e;if((Ph(t)||n)&&!Ih(t)&&(Ii.get("form.forceIndicesArrayFormatInFormData")&&(i="indices"),t=g0(t,new FormData,null,i)),Ih(t))return[s,t];const[o,a]=L2(r,s,t,i);return[un(o),a]};function L2(e,t,r,n="brackets"){const i=e==="get"&&!Ih(r)&&Object.keys(r).length>0,s=M2(t.toString()),o=s||t.toString().startsWith("/")||t.toString()==="",a=!o&&!t.toString().startsWith("#")&&!t.toString().startsWith("?"),l=/^[.]{1,2}([/]|$)/.test(t.toString()),u=t.toString().includes("?")||i,c=t.toString().includes("#"),d=new URL(t.toString(),typeof window>"u"?"http://localhost":window.location.toString());if(i){const p=/\[\d+\]/.test(decodeURIComponent(d.search)),m={ignoreQueryPrefix:!0,allowSparse:!0};d.search=Cb.stringify({...Cb.parse(d.search,m),...r},{encodeValuesOnly:!0,arrayFormat:p?"indices":n})}return[[s?`${d.protocol}//${d.host}`:"",o?d.pathname:"",a?d.pathname.substring(l?0:1):"",u?d.search:"",c?d.hash:""].join(""),i?{}:r]}function Vl(e){return e=new URL(e.href),e.hash="",e}var kb=(e,t)=>{e.hash&&!t.hash&&Vl(e).href===t.href&&(t.hash=e.hash)},Bl=(e,t)=>Vl(e).href===Vl(t).href,R2=(e,t)=>e.origin===t.origin&&e.pathname===t.pathname;function Ul(e){return e!==null&&typeof e=="object"&&e!==void 0&&"url"in e&&"method"in e}function M2(e){return/^([a-z][a-z0-9+.-]*:)?\/\/[^/]/i.test(e)}var D2=class{constructor(){this.componentId={},this.listeners=[],this.isFirstPageLoad=!0,this.cleared=!1,this.pendingDeferredProps=null,this.historyQuotaExceeded=!1}init({initialPage:e,swapComponent:t,resolveComponent:r,onFlash:n}){return this.page={...e,flash:e.flash??{}},this.swapComponent=t,this.resolveComponent=r,this.onFlashCallback=n,Ur.on("historyQuotaExceeded",()=>{this.historyQuotaExceeded=!0}),this}set(e,{replace:t=!1,preserveScroll:r=!1,preserveState:n=!1,viewTransition:i=!1}={}){Object.keys(e.deferredProps||{}).length&&(this.pendingDeferredProps={deferredProps:e.deferredProps,component:e.component,url:e.url},e.initialDeferredProps===void 0&&(e.initialDeferredProps=e.deferredProps)),this.componentId={};const s=this.componentId;return e.clearHistory&&_e.clear(),this.resolve(e.component).then(o=>{if(s!==this.componentId)return;e.rememberedState??(e.rememberedState={});const a=typeof window>"u",l=a?new URL(e.url):window.location,u=!a&&r?$t.getScrollRegions():[];t=t||Bl(un(e.url),l);const c={...e,flash:{}};return new Promise(d=>t?_e.replaceState(c,d):_e.pushState(c,d)).then(()=>{const d=!this.isTheSame(e);if(!d&&Object.keys(e.props.errors||{}).length>0&&(i=!1),this.page=e,this.cleared=!1,this.hasOnceProps()&&Vr.updateCachedOncePropsFromCurrentPage(),d&&this.fireEventsFor("newComponent"),this.isFirstPageLoad&&this.fireEventsFor("firstLoad"),this.isFirstPageLoad=!1,this.historyQuotaExceeded){this.historyQuotaExceeded=!1;return}return this.swap({component:o,page:e,preserveState:n,viewTransition:i}).then(()=>{r?window.requestAnimationFrame(()=>$t.restoreScrollRegions(u)):$t.reset(),this.pendingDeferredProps&&this.pendingDeferredProps.component===e.component&&this.pendingDeferredProps.url===e.url&&Ur.fireInternalEvent("loadDeferredProps",this.pendingDeferredProps.deferredProps),this.pendingDeferredProps=null,t||go(e)})})})}setQuietly(e,{preserveState:t=!1}={}){return this.resolve(e.component).then(r=>(this.page=e,this.cleared=!1,_e.setCurrent(e),this.swap({component:r,page:e,preserveState:t,viewTransition:!1})))}clear(){this.cleared=!0}isCleared(){return this.cleared}get(){return this.page}getWithoutFlashData(){return{...this.page,flash:{}}}hasOnceProps(){return Object.keys(this.page.onceProps??{}).length>0}merge(e){this.page={...this.page,...e}}setFlash(e){this.page={...this.page,flash:e},this.onFlashCallback?.(e)}setUrlHash(e){this.page.url.includes(e)||(this.page.url+=e)}remember(e){this.page.rememberedState=e}swap({component:e,page:t,preserveState:r,viewTransition:n}){const i=()=>this.swapComponent({component:e,page:t,preserveState:r});if(!n||!document?.startViewTransition)return i();const s=typeof n=="boolean"?()=>null:n;return new Promise(o=>{const a=document.startViewTransition(()=>i().then(o));s(a)})}resolve(e){return Promise.resolve(this.resolveComponent(e))}isTheSame(e){return this.page.component===e.component}on(e,t){return this.listeners.push({event:e,callback:t}),()=>{this.listeners=this.listeners.filter(r=>r.event!==e&&r.callback!==t)}}fireEventsFor(e){this.listeners.filter(t=>t.event===e).forEach(t=>t.callback())}mergeOncePropsIntoResponse(e,{force:t=!1}={}){Object.entries(e.onceProps??{}).forEach(([r,n])=>{const i=this.page.onceProps?.[r];i!==void 0&&(t||e.props[n.prop]===void 0)&&(e.props[n.prop]=this.page.props[i.prop],e.onceProps[r].expiresAt=i.expiresAt)})}},ee=new D2,vp=class{constructor(){this.items=[],this.processingPromise=null}add(e){return this.items.push(e),this.process()}process(){return this.processingPromise??(this.processingPromise=this.processNext().finally(()=>{this.processingPromise=null})),this.processingPromise}processNext(){const e=this.items.shift();return e?Promise.resolve(e()).then(()=>this.processNext()):Promise.resolve()}},Yi=typeof window>"u",Us=new vp,Ob=!Yi&&/CriOS/.test(window.navigator.userAgent),$2=class{constructor(){this.rememberedState="rememberedState",this.scrollRegions="scrollRegions",this.preserveUrl=!1,this.current={},this.initialState=null}remember(e,t){this.replaceState({...ee.getWithoutFlashData(),rememberedState:{...ee.get()?.rememberedState??{},[t]:e}})}restore(e){if(!Yi)return this.current[this.rememberedState]?.[e]!==void 0?this.current[this.rememberedState]?.[e]:this.initialState?.[this.rememberedState]?.[e]}pushState(e,t=null){if(!Yi){if(this.preserveUrl){t&&t();return}this.current=e,Us.add(()=>this.getPageData(e).then(r=>{const n=()=>this.doPushState({page:r},e.url).then(()=>t?.());return Ob?new Promise(i=>{setTimeout(()=>n().then(i))}):n()}))}}clonePageProps(e){try{return structuredClone(e.props),e}catch{return{...e,props:ut(e.props)}}}getPageData(e){const t=this.clonePageProps(e);return new Promise(r=>e.encryptHistory?_2(t).then(r):r(t))}processQueue(){return Us.process()}decrypt(e=null){if(Yi)return Promise.resolve(e??ee.get());const t=e??window.history.state?.page;return this.decryptPageData(t).then(r=>{if(!r)throw new Error("Unable to decrypt history");return this.initialState===null?this.initialState=r??void 0:this.current=r??{},r})}decryptPageData(e){return e instanceof ArrayBuffer?w2(e):Promise.resolve(e)}saveScrollPositions(e){Us.add(()=>Promise.resolve().then(()=>{if(window.history.state?.page&&!Jn(this.getScrollRegions(),e))return this.doReplaceState({page:window.history.state.page,scrollRegions:e})}))}saveDocumentScrollPosition(e){Us.add(()=>Promise.resolve().then(()=>{if(window.history.state?.page&&!Jn(this.getDocumentScrollPosition(),e))return this.doReplaceState({page:window.history.state.page,documentScrollPosition:e})}))}getScrollRegions(){return window.history.state?.scrollRegions||[]}getDocumentScrollPosition(){return window.history.state?.documentScrollPosition||{top:0,left:0}}replaceState(e,t=null){if(Jn(this.current,e)){t&&t();return}if(ee.merge(e),!Yi){if(this.preserveUrl){t&&t();return}this.current=e,Us.add(()=>this.getPageData(e).then(r=>{const n=()=>this.doReplaceState({page:r},e.url).then(()=>t?.());return Ob?new Promise(i=>{setTimeout(()=>n().then(i))}):n()}))}}isHistoryThrottleError(e){return e instanceof Error&&e.name==="SecurityError"&&(e.message.includes("history.pushState")||e.message.includes("history.replaceState"))}isQuotaExceededError(e){return e instanceof Error&&e.name==="QuotaExceededError"}withThrottleProtection(e){return Promise.resolve().then(()=>{try{return e()}catch(t){if(!this.isHistoryThrottleError(t))throw t;console.error(t.message)}})}doReplaceState(e,t){return this.withThrottleProtection(()=>{window.history.replaceState({...e,scrollRegions:e.scrollRegions??window.history.state?.scrollRegions,documentScrollPosition:e.documentScrollPosition??window.history.state?.documentScrollPosition},"",t)})}doPushState(e,t){return this.withThrottleProtection(()=>{try{window.history.pushState(e,"",t)}catch(r){if(!this.isQuotaExceededError(r))throw r;Ur.fireInternalEvent("historyQuotaExceeded",t)}})}getState(e,t){return this.current?.[e]??t}deleteState(e){this.current[e]!==void 0&&(delete this.current[e],this.replaceState(this.current))}clearInitialState(e){this.initialState&&this.initialState[e]!==void 0&&delete this.initialState[e]}browserHasHistoryEntry(){return!Yi&&!!window.history.state?.page}clear(){Ft.remove(gs.key),Ft.remove(gs.iv)}setCurrent(e){this.current=e}isValidState(e){return!!e.page}getAllState(){return this.current}};typeof window<"u"&&window.history.scrollRestoration&&(window.history.scrollRestoration="manual");var _e=new $2,V2=class{constructor(){this.internalListeners=[]}init(){typeof window<"u"&&(window.addEventListener("popstate",this.handlePopstateEvent.bind(this)),window.addEventListener("scroll",Nh($t.onWindowScroll.bind($t),100),!0)),typeof document<"u"&&document.addEventListener("scroll",Nh($t.onScroll.bind($t),100),!0)}onGlobalEvent(e,t){const r=(n=>{const i=t(n);n.cancelable&&!n.defaultPrevented&&i===!1&&n.preventDefault()});return this.registerListener(`inertia:${e}`,r)}on(e,t){return this.internalListeners.push({event:e,listener:t}),()=>{this.internalListeners=this.internalListeners.filter(r=>r.listener!==t)}}onMissingHistoryItem(){ee.clear(),this.fireInternalEvent("missingHistoryItem")}fireInternalEvent(e,...t){this.internalListeners.filter(r=>r.event===e).forEach(r=>r.listener(...t))}registerListener(e,t){return document.addEventListener(e,t),()=>document.removeEventListener(e,t)}handlePopstateEvent(e){const t=e.state||null;if(t===null){const r=un(ee.get().url);r.hash=window.location.hash,_e.replaceState({...ee.getWithoutFlashData(),url:r.href}),$t.reset();return}if(!_e.isValidState(t))return this.onMissingHistoryItem();_e.decrypt(t.page).then(r=>{if(ee.get().version!==r.version){this.onMissingHistoryItem();return}Lt.cancelAll({prefetch:!1}),ee.setQuietly(r,{preserveState:!1}).then(()=>{$t.restore(_e.getScrollRegions()),go(ee.get());const n={},i=ee.get().props;for(const[s,o]of Object.entries(r.initialDeferredProps??r.deferredProps??{})){const a=o.filter(l=>i[l]===void 0);a.length>0&&(n[s]=a)}Object.keys(n).length>0&&this.fireInternalEvent("loadDeferredProps",n)})}).catch(()=>{this.onMissingHistoryItem()})}},Ur=new V2,B2=class{constructor(){this.type=this.resolveType()}resolveType(){return typeof window>"u"?"navigate":window.performance&&window.performance.getEntriesByType&&window.performance.getEntriesByType("navigation").length>0?window.performance.getEntriesByType("navigation")[0].type:"navigate"}get(){return this.type}isBackForward(){return this.type==="back_forward"}isReload(){return this.type==="reload"}},bd=new B2,U2=class{static handle(){this.clearRememberedStateOnReload(),[this.handleBackForward,this.handleLocation,this.handleDefault].find(t=>t.bind(this)())}static clearRememberedStateOnReload(){bd.isReload()&&(_e.deleteState(_e.rememberedState),_e.clearInitialState(_e.rememberedState))}static handleBackForward(){if(!bd.isBackForward()||!_e.browserHasHistoryEntry())return!1;const e=_e.getScrollRegions();return _e.decrypt().then(t=>{ee.set(t,{preserveScroll:!0,preserveState:!0}).then(()=>{$t.restore(e),go(ee.get())})}).catch(()=>{Ur.onMissingHistoryItem()}),!0}static handleLocation(){if(!Ft.exists(Ft.locationVisitKey))return!1;const e=Ft.get(Ft.locationVisitKey)||{};return Ft.remove(Ft.locationVisitKey),typeof window<"u"&&ee.setUrlHash(window.location.hash),_e.decrypt(ee.get()).then(()=>{const t=_e.getState(_e.rememberedState,{}),r=_e.getScrollRegions();ee.remember(t),ee.set(ee.get(),{preserveScroll:e.preserveScroll,preserveState:!0}).then(()=>{e.preserveScroll&&$t.restore(r),go(ee.get())})}).catch(()=>{Ur.onMissingHistoryItem()}),!0}static handleDefault(){typeof window<"u"&&ee.setUrlHash(window.location.hash),ee.set(ee.get(),{preserveScroll:!0,preserveState:!0}).then(()=>{bd.isReload()?$t.restore(_e.getScrollRegions()):$t.scrollToAnchor();const e=ee.get();go(e);const t=e.flash;Object.keys(t).length>0&&queueMicrotask(()=>$l(t))})}},z2=class{constructor(e,t,r){this.id=null,this.throttle=!1,this.keepAlive=!1,this.cbCount=0,this.keepAlive=r.keepAlive??!1,this.cb=t,this.interval=e,(r.autoStart??!0)&&this.start()}stop(){this.id&&clearInterval(this.id)}start(){typeof window>"u"||(this.stop(),this.id=window.setInterval(()=>{(!this.throttle||this.cbCount%10===0)&&this.cb(),this.throttle&&this.cbCount++},this.interval))}isInBackground(e){this.throttle=this.keepAlive?!1:e,this.throttle&&(this.cbCount=0)}},H2=class{constructor(){this.polls=[],this.setupVisibilityListener()}add(e,t,r){const n=new z2(e,t,r);return this.polls.push(n),{stop:()=>n.stop(),start:()=>n.start()}}clear(){this.polls.forEach(e=>e.stop()),this.polls=[]}setupVisibilityListener(){typeof document>"u"||document.addEventListener("visibilitychange",()=>{this.polls.forEach(e=>e.isInBackground(document.hidden))},!1)}},q2=new H2,Fh=class el{constructor(t){if(this.callbacks=[],!t.prefetch)this.params=t;else{const r={onBefore:this.wrapCallback(t,"onBefore"),onBeforeUpdate:this.wrapCallback(t,"onBeforeUpdate"),onStart:this.wrapCallback(t,"onStart"),onProgress:this.wrapCallback(t,"onProgress"),onFinish:this.wrapCallback(t,"onFinish"),onCancel:this.wrapCallback(t,"onCancel"),onSuccess:this.wrapCallback(t,"onSuccess"),onError:this.wrapCallback(t,"onError"),onFlash:this.wrapCallback(t,"onFlash"),onCancelToken:this.wrapCallback(t,"onCancelToken"),onPrefetched:this.wrapCallback(t,"onPrefetched"),onPrefetching:this.wrapCallback(t,"onPrefetching")};this.params={...t,...r,onPrefetchResponse:t.onPrefetchResponse||(()=>{}),onPrefetchError:t.onPrefetchError||(()=>{})}}}static create(t){return new el(t)}data(){return this.params.method==="get"?null:this.params.data}queryParams(){return this.params.method==="get"?this.params.data:{}}isPartial(){return this.params.only.length>0||this.params.except.length>0||this.params.reset.length>0}isPrefetch(){return this.params.prefetch===!0}isDeferredPropsRequest(){return this.params.deferredProps===!0}onCancelToken(t){this.params.onCancelToken({cancel:t})}markAsFinished(){this.params.completed=!0,this.params.cancelled=!1,this.params.interrupted=!1}markAsCancelled({cancelled:t=!0,interrupted:r=!1}){this.params.onCancel(),this.params.completed=!1,this.params.cancelled=t,this.params.interrupted=r}wasCancelledAtAll(){return this.params.cancelled||this.params.interrupted}onFinish(){this.params.onFinish(this.params)}onStart(){this.params.onStart(this.params)}onPrefetching(){this.params.onPrefetching(this.params)}onPrefetchResponse(t){this.params.onPrefetchResponse&&this.params.onPrefetchResponse(t)}onPrefetchError(t){this.params.onPrefetchError&&this.params.onPrefetchError(t)}all(){return this.params}headers(){const t={...this.params.headers};this.isPartial()&&(t["X-Inertia-Partial-Component"]=ee.get().component);const r=this.params.only.concat(this.params.reset);return r.length>0&&(t["X-Inertia-Partial-Data"]=r.join(",")),this.params.except.length>0&&(t["X-Inertia-Partial-Except"]=this.params.except.join(",")),this.params.reset.length>0&&(t["X-Inertia-Reset"]=this.params.reset.join(",")),this.params.errorBag&&this.params.errorBag.length>0&&(t["X-Inertia-Error-Bag"]=this.params.errorBag),t}setPreserveOptions(t){this.params.preserveScroll=el.resolvePreserveOption(this.params.preserveScroll,t),this.params.preserveState=el.resolvePreserveOption(this.params.preserveState,t)}runCallbacks(){this.callbacks.forEach(({name:t,args:r})=>{this.params[t](...r)})}merge(t){this.params={...this.params,...t}}wrapCallback(t,r){return(...n)=>{this.recordCallback(r,n),t[r](...n)}}recordCallback(t,r){this.callbacks.push({name:t,args:r})}static resolvePreserveOption(t,r){return typeof t=="function"?t(r):t==="errors"?Object.keys(r.props.errors||{}).length>0:t}},y0={modal:null,listener:null,createIframeAndPage(e){typeof e=="object"&&(e=`All Inertia requests must receive a valid Inertia response, however a plain JSON response was received.<hr>${JSON.stringify(e)}`);const t=document.createElement("html");t.innerHTML=e,t.querySelectorAll("a").forEach(n=>n.setAttribute("target","_top"));const r=document.createElement("iframe");return r.style.backgroundColor="white",r.style.borderRadius="5px",r.style.width="100%",r.style.height="100%",{iframe:r,page:t}},show(e){const{iframe:t,page:r}=this.createIframeAndPage(e);if(this.modal=document.createElement("div"),this.modal.style.position="fixed",this.modal.style.width="100vw",this.modal.style.height="100vh",this.modal.style.padding="50px",this.modal.style.boxSizing="border-box",this.modal.style.backgroundColor="rgba(0, 0, 0, .6)",this.modal.style.zIndex=2e5,this.modal.addEventListener("click",()=>this.hide()),this.modal.appendChild(t),document.body.prepend(this.modal),document.body.style.overflow="hidden",!t.contentWindow)throw new Error("iframe not yet ready.");t.contentWindow.document.open(),t.contentWindow.document.write(r.outerHTML),t.contentWindow.document.close(),this.listener=this.hideOnEscape.bind(this),document.addEventListener("keydown",this.listener)},hide(){this.modal.outerHTML="",this.modal=null,document.body.style.overflow="visible",document.removeEventListener("keydown",this.listener)},hideOnEscape(e){e.keyCode===27&&this.hide()}},j2={show(e){const{iframe:t,page:r}=y0.createIframeAndPage(e);t.style.boxSizing="border-box",t.style.display="block";const n=document.createElement("dialog");n.id="inertia-error-dialog",Object.assign(n.style,{width:"calc(100vw - 100px)",height:"calc(100vh - 100px)",padding:"0",margin:"auto",border:"none",backgroundColor:"transparent"});const i=document.createElement("style");if(i.textContent=`
      dialog#inertia-error-dialog::backdrop {
        background-color: rgba(0, 0, 0, 0.6);
      }

      dialog#inertia-error-dialog:focus {
        outline: none;
      }
    `,document.head.appendChild(i),n.addEventListener("click",s=>{s.target===n&&n.close()}),n.addEventListener("close",()=>{i.remove(),n.remove()}),n.appendChild(t),document.body.prepend(n),n.showModal(),n.focus(),!t.contentWindow)throw new Error("iframe not yet ready.");t.contentWindow.document.open(),t.contentWindow.document.write(r.outerHTML),t.contentWindow.document.close()}},W2=new vp,Nb=class _0{constructor(t,r,n){this.requestParams=t,this.response=r,this.originatingPage=n,this.wasPrefetched=!1}static create(t,r,n){return new _0(t,r,n)}async handlePrefetch(){Bl(this.requestParams.all().url,window.location)&&this.handle()}async handle(){return W2.add(()=>this.process())}async process(){if(this.requestParams.all().prefetch)return this.wasPrefetched=!0,this.requestParams.all().prefetch=!1,this.requestParams.all().onPrefetched(this.response,this.requestParams.all()),v2(this.response,this.requestParams.all()),Promise.resolve();if(this.requestParams.runCallbacks(),!this.isInertiaResponse())return this.handleNonInertiaResponse();await _e.processQueue(),_e.preserveUrl=this.requestParams.all().preserveUrl;const t=ee.get().flash;await this.setPage();const r=ee.get().props.errors||{};if(Object.keys(r).length>0){const i=this.getScopedErrors(r);return u2(i),this.requestParams.all().onError(i)}Lt.flushByCacheTags(this.requestParams.all().invalidateCacheTags||[]),this.wasPrefetched||Lt.flush(ee.get().url);const{flash:n}=ee.get();Object.keys(n).length>0&&(!this.requestParams.isPartial()||!Jn(n,t))&&($l(n),this.requestParams.all().onFlash(n)),b2(ee.get()),await this.requestParams.all().onSuccess(ee.get()),_e.preserveUrl=!1}mergeParams(t){this.requestParams.merge(t)}getPageResponse(){const t=this.getDataFromResponse(this.response.data);return typeof t=="object"?this.response.data={...t,flash:t.flash??{}}:this.response.data=t}async handleNonInertiaResponse(){if(this.isLocationVisit()){const r=un(this.getHeader("x-inertia-location"));return kb(this.requestParams.all().url,r),this.locationVisit(r)}const t={...this.response,data:this.getDataFromResponse(this.response.data)};if(f2(t))return Ii.get("future.useDialogForErrorModal")?j2.show(t.data):y0.show(t.data)}isInertiaResponse(){return this.hasHeader("x-inertia")}hasStatus(t){return this.response.status===t}getHeader(t){return this.response.headers[t]}hasHeader(t){return this.getHeader(t)!==void 0}isLocationVisit(){return this.hasStatus(409)&&this.hasHeader("x-inertia-location")}locationVisit(t){try{if(Ft.set(Ft.locationVisitKey,{preserveScroll:this.requestParams.all().preserveScroll===!0}),typeof window>"u")return;Bl(window.location,t)?window.location.reload():window.location.href=t.href}catch{return!1}}async setPage(){const t=this.getPageResponse();return this.shouldSetPage(t)?(this.mergeProps(t),ee.mergeOncePropsIntoResponse(t),this.preserveEqualProps(t),await this.setRememberedState(t),this.requestParams.setPreserveOptions(t),t.url=_e.preserveUrl?ee.get().url:this.pageUrl(t),this.requestParams.all().onBeforeUpdate(t),p2(t),ee.set(t,{replace:this.requestParams.all().replace,preserveScroll:this.requestParams.all().preserveScroll,preserveState:this.requestParams.all().preserveState,viewTransition:this.requestParams.all().viewTransition})):Promise.resolve()}getDataFromResponse(t){if(typeof t!="string")return t;try{return JSON.parse(t)}catch{return t}}shouldSetPage(t){if(!this.requestParams.all().async||this.originatingPage.component!==t.component)return!0;if(this.originatingPage.component!==ee.get().component)return!1;const r=un(this.originatingPage.url),n=un(ee.get().url);return r.origin===n.origin&&r.pathname===n.pathname}pageUrl(t){const r=un(t.url);return kb(this.requestParams.all().url,r),r.pathname+r.search+r.hash}preserveEqualProps(t){if(t.component!==ee.get().component||Ii.get("future.preserveEqualProps")!==!0)return;const r=ee.get().props;Object.entries(t.props).forEach(([n,i])=>{Jn(i,r[n])&&(t.props[n]=r[n])})}mergeProps(t){if(!this.requestParams.isPartial()||t.component!==ee.get().component)return;const r=t.mergeProps||[],n=t.prependProps||[],i=t.deepMergeProps||[],s=t.matchPropsOn||[],o=(l,u)=>{const c=Cr(ee.get().props,l),d=Cr(t.props,l);if(Array.isArray(d)){const p=this.mergeOrMatchItems(c||[],d,l,s,u);zr(t.props,l,p)}else if(typeof d=="object"&&d!==null){const p={...c||{},...d};zr(t.props,l,p)}};if(r.forEach(l=>o(l,!0)),n.forEach(l=>o(l,!1)),i.forEach(l=>{const u=ee.get().props[l],c=t.props[l],d=(p,m,h)=>Array.isArray(m)?this.mergeOrMatchItems(p,m,h,s):typeof m=="object"&&m!==null?Object.keys(m).reduce((f,b)=>(f[b]=d(p?p[b]:void 0,m[b],`${h}.${b}`),f),{...p}):m;t.props[l]=d(u,c,l)}),t.props={...ee.get().props,...t.props},this.requestParams.isDeferredPropsRequest()){const l=ee.get().props.errors;l&&Object.keys(l).length>0&&(t.props.errors=l)}ee.get().scrollProps&&(t.scrollProps={...ee.get().scrollProps||{},...t.scrollProps||{}}),ee.hasOnceProps()&&(t.onceProps={...ee.get().onceProps||{},...t.onceProps||{}}),t.flash={...ee.get().flash,...this.requestParams.isDeferredPropsRequest()?{}:t.flash};const a=ee.get().initialDeferredProps;a&&Object.keys(a).length>0&&(t.initialDeferredProps=a)}mergeOrMatchItems(t,r,n,i,s=!0){const o=Array.isArray(t)?t:[],a=i.find(c=>c.split(".").slice(0,-1).join(".")===n);if(!a)return s?[...o,...r]:[...r,...o];const l=a.split(".").pop()||"",u=new Map;return r.forEach(c=>{this.hasUniqueProperty(c,l)&&u.set(c[l],c)}),s?this.appendWithMatching(o,r,u,l):this.prependWithMatching(o,r,u,l)}appendWithMatching(t,r,n,i){const s=t.map(a=>this.hasUniqueProperty(a,i)&&n.has(a[i])?n.get(a[i]):a),o=r.filter(a=>this.hasUniqueProperty(a,i)?!t.some(l=>this.hasUniqueProperty(l,i)&&l[i]===a[i]):!0);return[...s,...o]}prependWithMatching(t,r,n,i){const s=t.filter(o=>this.hasUniqueProperty(o,i)?!n.has(o[i]):!0);return[...r,...s]}hasUniqueProperty(t,r){return t&&typeof t=="object"&&r in t}async setRememberedState(t){const r=await _e.getState(_e.rememberedState,{});this.requestParams.all().preserveState&&r&&t.component===ee.get().component&&(t.rememberedState=r)}getScopedErrors(t){return this.requestParams.all().errorBag?t[this.requestParams.all().errorBag||""]||{}:t}},Pb=class w0{constructor(t,r){this.page=r,this.requestHasFinished=!1,this.requestParams=Fh.create(t),this.cancelToken=new AbortController}static create(t,r){return new w0(t,r)}isPrefetch(){return this.requestParams.isPrefetch()}async send(){this.requestParams.onCancelToken(()=>this.cancel({cancelled:!0})),g2(this.requestParams.all()),this.requestParams.onStart(),this.requestParams.all().prefetch&&(this.requestParams.onPrefetching(),y2(this.requestParams.all()));const t=this.requestParams.all().prefetch;return is({method:this.requestParams.all().method,url:Vl(this.requestParams.all().url).href,data:this.requestParams.data(),params:this.requestParams.queryParams(),signal:this.cancelToken.signal,headers:this.getHeaders(),onUploadProgress:this.onProgress.bind(this),responseType:"text"}).then(r=>(this.response=Nb.create(this.requestParams,r,this.page),this.response.handle())).catch(r=>r?.response?(this.response=Nb.create(this.requestParams,r.response,this.page),this.response.handle()):Promise.reject(r)).catch(r=>{if(!is.isCancel(r)&&d2(r))return t&&this.requestParams.onPrefetchError(r),Promise.reject(r)}).finally(()=>{this.finish(),t&&this.response&&this.requestParams.onPrefetchResponse(this.response)})}finish(){this.requestParams.wasCancelledAtAll()||(this.requestParams.markAsFinished(),this.fireFinishEvents())}fireFinishEvents(){this.requestHasFinished||(this.requestHasFinished=!0,h2(this.requestParams.all()),this.requestParams.onFinish())}cancel({cancelled:t=!1,interrupted:r=!1}){this.requestHasFinished||(this.cancelToken.abort(),this.requestParams.markAsCancelled({cancelled:t,interrupted:r}),this.fireFinishEvents())}onProgress(t){this.requestParams.data()instanceof FormData&&(t.percentage=t.progress?Math.round(t.progress*100):0,m2(t),this.requestParams.all().onProgress(t))}getHeaders(){const t={...this.requestParams.headers(),Accept:"text/html, application/xhtml+xml","X-Requested-With":"XMLHttpRequest","X-Inertia":!0},r=ee.get();r.version&&(t["X-Inertia-Version"]=r.version);const n=Object.entries(r.onceProps||{}).filter(([,i])=>r.props[i.prop]===void 0?!1:!i.expiresAt||i.expiresAt>Date.now()).map(([i])=>i);return n.length>0&&(t["X-Inertia-Except-Once-Props"]=n.join(",")),t}},Ib=class{constructor({maxConcurrent:e,interruptible:t}){this.requests=[],this.maxConcurrent=e,this.interruptible=t}send(e){this.requests.push(e),e.send().then(()=>{this.requests=this.requests.filter(t=>t!==e)})}interruptInFlight(){this.cancel({interrupted:!0},!1)}cancelInFlight({prefetch:e=!0}={}){this.requests.filter(t=>e||!t.isPrefetch()).forEach(t=>t.cancel({cancelled:!0}))}cancel({cancelled:e=!1,interrupted:t=!1}={},r=!1){if(!r&&!this.shouldCancel())return;this.requests.shift()?.cancel({cancelled:e,interrupted:t})}shouldCancel(){return this.interruptible&&this.requests.length>=this.maxConcurrent}},K2=class{constructor(){this.syncRequestStream=new Ib({maxConcurrent:1,interruptible:!0}),this.asyncRequestStream=new Ib({maxConcurrent:1/0,interruptible:!1}),this.clientVisitQueue=new vp}init({initialPage:e,resolveComponent:t,swapComponent:r,onFlash:n}){ee.init({initialPage:e,resolveComponent:t,swapComponent:r,onFlash:n}),U2.handle(),Ur.init(),Ur.on("missingHistoryItem",()=>{typeof window<"u"&&this.visit(window.location.href,{preserveState:!0,preserveScroll:!0,replace:!0})}),Ur.on("loadDeferredProps",i=>{this.loadDeferredProps(i)}),Ur.on("historyQuotaExceeded",i=>{window.location.href=i})}get(e,t={},r={}){return this.visit(e,{...r,method:"get",data:t})}post(e,t={},r={}){return this.visit(e,{preserveState:!0,...r,method:"post",data:t})}put(e,t={},r={}){return this.visit(e,{preserveState:!0,...r,method:"put",data:t})}patch(e,t={},r={}){return this.visit(e,{preserveState:!0,...r,method:"patch",data:t})}delete(e,t={}){return this.visit(e,{preserveState:!0,...t,method:"delete"})}reload(e={}){return this.doReload(e)}doReload(e={}){if(!(typeof window>"u"))return this.visit(window.location.href,{...e,preserveScroll:!0,preserveState:!0,async:!0,headers:{...e.headers||{},"Cache-Control":"no-cache"}})}remember(e,t="default"){_e.remember(e,t)}restore(e="default"){return _e.restore(e)}on(e,t){return typeof window>"u"?()=>{}:Ur.onGlobalEvent(e,t)}cancel(){this.syncRequestStream.cancelInFlight()}cancelAll({async:e=!0,prefetch:t=!0,sync:r=!0}={}){e&&this.asyncRequestStream.cancelInFlight({prefetch:t}),r&&this.syncRequestStream.cancelInFlight()}poll(e,t={},r={}){return q2.add(e,()=>this.reload(t),{autoStart:r.autoStart??!0,keepAlive:r.keepAlive??!1})}visit(e,t={}){const r=this.getPendingVisit(e,{...t,showProgress:t.showProgress??!t.async}),n=this.getVisitEvents(t);if(n.onBefore(r)===!1||!Ab(r))return;const i=un(ee.get().url);(r.only.length>0||r.except.length>0||r.reset.length>0?R2(r.url,i):Bl(r.url,i))||this.asyncRequestStream.cancelInFlight({prefetch:!1}),r.async||this.syncRequestStream.interruptInFlight(),!ee.isCleared()&&!r.preserveUrl&&$t.save();const a={...r,...n},l=Vr.get(a);l?(Bt.reveal(l.inFlight),Vr.use(l,a)):(Bt.reveal(!0),(r.async?this.asyncRequestStream:this.syncRequestStream).send(Pb.create(a,ee.get())))}getCached(e,t={}){return Vr.findCached(this.getPrefetchParams(e,t))}flush(e,t={}){Vr.remove(this.getPrefetchParams(e,t))}flushAll(){Vr.removeAll()}flushByCacheTags(e){Vr.removeByTags(Array.isArray(e)?e:[e])}getPrefetching(e,t={}){return Vr.findInFlight(this.getPrefetchParams(e,t))}prefetch(e,t={},r={}){if((t.method??(Ul(e)?e.method:"get"))!=="get")throw new Error("Prefetch requests must use the GET method");const i=this.getPendingVisit(e,{...t,async:!0,showProgress:!1,prefetch:!0,viewTransition:!1}),s=i.url.origin+i.url.pathname+i.url.search,o=window.location.origin+window.location.pathname+window.location.search;if(s===o)return;const a=this.getVisitEvents(t);if(a.onBefore(i)===!1||!Ab(i))return;Bt.hide(),this.asyncRequestStream.interruptInFlight();const l={...i,...a};new Promise(c=>{const d=()=>{ee.get()?c():setTimeout(d,50)};d()}).then(()=>{Vr.add(l,c=>{this.asyncRequestStream.send(Pb.create(c,ee.get()))},{cacheFor:Ii.get("prefetch.cacheFor"),cacheTags:[],...r})})}clearHistory(){_e.clear()}decryptHistory(){return _e.decrypt()}resolveComponent(e){return ee.resolve(e)}replace(e){this.clientVisit(e,{replace:!0})}replaceProp(e,t,r){this.replace({preserveScroll:!0,preserveState:!0,props(n){const i=typeof t=="function"?t(Cr(n,e),n):t;return zr(ut(n),e,i)},...r||{}})}appendToProp(e,t,r){this.replaceProp(e,(n,i)=>{const s=typeof t=="function"?t(n,i):t;return Array.isArray(n)||(n=n!==void 0?[n]:[]),[...n,s]},r)}prependToProp(e,t,r){this.replaceProp(e,(n,i)=>{const s=typeof t=="function"?t(n,i):t;return Array.isArray(n)||(n=n!==void 0?[n]:[]),[s,...n]},r)}push(e){this.clientVisit(e)}flash(e,t){const r=ee.get().flash;let n;if(typeof e=="function")n=e(r);else if(typeof e=="string")n={...r,[e]:t};else if(e&&Object.keys(e).length)n={...r,...e};else return;ee.setFlash(n),Object.keys(n).length&&$l(n)}clientVisit(e,{replace:t=!1}={}){this.clientVisitQueue.add(()=>this.performClientVisit(e,{replace:t}))}performClientVisit(e,{replace:t=!1}={}){const r=ee.get(),n=typeof e.props=="function"?Object.fromEntries(Object.values(r.onceProps??{}).map(f=>[f.prop,r.props[f.prop]])):{},i=typeof e.props=="function"?e.props(r.props,n):e.props??r.props,s=typeof e.flash=="function"?e.flash(r.flash):e.flash,{viewTransition:o,onError:a,onFinish:l,onFlash:u,onSuccess:c,...d}=e,p={...r,...d,flash:s??{},props:i},m=Fh.resolvePreserveOption(e.preserveScroll??!1,p),h=Fh.resolvePreserveOption(e.preserveState??!1,p);return ee.set(p,{replace:t,preserveScroll:m,preserveState:h,viewTransition:o}).then(()=>{const f=ee.get().flash;Object.keys(f).length>0&&($l(f),u?.(f));const b=ee.get().props.errors||{};if(Object.keys(b).length===0){c?.(ee.get());return}const y=e.errorBag?b[e.errorBag||""]||{}:b;a?.(y)}).finally(()=>l?.(e))}getPrefetchParams(e,t){return{...this.getPendingVisit(e,{...t,async:!0,showProgress:!1,prefetch:!0,viewTransition:!1}),...this.getVisitEvents(t)}}getPendingVisit(e,t,r={}){if(Ul(e)){const u=e;e=u.url,t.method=t.method??u.method}const n=Ii.get("visitOptions"),i=n?n(e.toString(),ut(t))||{}:{},s={method:"get",data:{},replace:!1,preserveScroll:!1,preserveState:!1,only:[],except:[],headers:{},errorBag:"",forceFormData:!1,queryStringArrayFormat:"brackets",async:!1,showProgress:!0,fresh:!1,reset:[],preserveUrl:!1,prefetch:!1,invalidateCacheTags:[],viewTransition:!1,...t,...i},[o,a]=F2(e,s.data,s.method,s.forceFormData,s.queryStringArrayFormat),l={cancelled:!1,completed:!1,interrupted:!1,...s,...r,url:o,data:a};return l.prefetch&&(l.headers.Purpose="prefetch"),l}getVisitEvents(e){return{onCancelToken:e.onCancelToken||(()=>{}),onBefore:e.onBefore||(()=>{}),onBeforeUpdate:e.onBeforeUpdate||(()=>{}),onStart:e.onStart||(()=>{}),onProgress:e.onProgress||(()=>{}),onFinish:e.onFinish||(()=>{}),onCancel:e.onCancel||(()=>{}),onSuccess:e.onSuccess||(()=>{}),onError:e.onError||(()=>{}),onFlash:e.onFlash||(()=>{}),onPrefetched:e.onPrefetched||(()=>{}),onPrefetching:e.onPrefetching||(()=>{})}}loadDeferredProps(e){e&&Object.entries(e).forEach(([t,r])=>{this.doReload({only:r,deferredProps:!0})})}},vd=class{static createWayfinderCallback(...e){return()=>e.length===1?Ul(e[0])?e[0]:e[0]():{method:typeof e[0]=="function"?e[0]():e[0],url:typeof e[1]=="function"?e[1]():e[1]}}static parseUseFormArguments(...e){return e.length===0?{rememberKey:null,data:{},precognitionEndpoint:null}:e.length===1?{rememberKey:null,data:e[0],precognitionEndpoint:null}:e.length===2?typeof e[0]=="string"?{rememberKey:e[0],data:e[1],precognitionEndpoint:null}:{rememberKey:null,data:e[1],precognitionEndpoint:this.createWayfinderCallback(e[0])}:{rememberKey:null,data:e[2],precognitionEndpoint:this.createWayfinderCallback(e[0],e[1])}}static parseSubmitArguments(e,t){return e.length===3||e.length===2&&typeof e[0]=="string"?{method:e[0],url:e[1],options:e[2]??{}}:Ul(e[0])?{...e[0],options:e[1]??{}}:{...t(),options:e[0]??{}}}static mergeHeadersForValidation(e,t,r){const n=i=>(i.headers={...r??{},...i.headers??{}},i);return e&&typeof e=="object"&&!("target"in e)?e=n(e):t&&typeof t=="object"?t=n(t):typeof e=="string"?t=n(t??{}):e=n(e??{}),[e,t]}},yd={preferredAttribute(){return Ii.get("future.useDataInertiaHeadAttribute")?"data-inertia":"inertia"},buildDOMElement(e){const t=document.createElement("template");t.innerHTML=e;const r=t.content.firstChild;if(!e.startsWith("<script "))return r;const n=document.createElement("script");return n.innerHTML=r.innerHTML,r.getAttributeNames().forEach(i=>{n.setAttribute(i,r.getAttribute(i)||"")}),n},isInertiaManagedElement(e){return e.nodeType===Node.ELEMENT_NODE&&e.getAttribute(this.preferredAttribute())!==null},findMatchingElementIndex(e,t){const r=this.preferredAttribute(),n=e.getAttribute(r);return n!==null?t.findIndex(i=>i.getAttribute(r)===n):-1},update:Nh(function(e){const t=e.map(n=>this.buildDOMElement(n));Array.from(document.head.childNodes).filter(n=>this.isInertiaManagedElement(n)).forEach(n=>{const i=this.findMatchingElementIndex(n,t);if(i===-1){n?.parentNode?.removeChild(n);return}const s=t.splice(i,1)[0];s&&!n.isEqualNode(s)&&n?.parentNode?.replaceChild(s,n)}),t.forEach(n=>document.head.appendChild(n))},1)};function G2(e,t,r){const n={};let i=0;function s(){const d=i+=1;return n[d]=[],d.toString()}function o(d){d===null||Object.keys(n).indexOf(d)===-1||(delete n[d],c())}function a(d){Object.keys(n).indexOf(d)===-1&&(n[d]=[])}function l(d,p=[]){d!==null&&Object.keys(n).indexOf(d)>-1&&(n[d]=p),c()}function u(){const d=t(""),p=yd.preferredAttribute(),m={...d?{title:`<title ${p}="">${d}</title>`}:{}},h=Object.values(n).reduce((f,b)=>f.concat(b),[]).reduce((f,b)=>{if(b.indexOf("<")===-1)return f;if(b.indexOf("<title ")===0){const w=b.match(/(<title [^>]+>)(.*?)(<\/title>)/);return f.title=w?`${w[1]}${t(w[2])}${w[3]}`:b,f}const y=b.match(p==="inertia"?/ inertia="[^"]+"/:/ data-inertia="[^"]+"/);return y?f[y[0]]=b:f[Object.keys(f).length]=b,f},m);return Object.values(h)}function c(){e?r(u()):yd.update(u())}return c(),{forceUpdate:c,createProvider:function(){const d=s();return{preferredAttribute:yd.preferredAttribute,reconnect:()=>a(d),update:p=>l(d,p),disconnect:()=>o(d)}}}}var nt="nprogress",hr,dt={minimum:.08,easing:"linear",positionUsing:"translate3d",speed:200,trickle:!0,trickleSpeed:200,showSpinner:!0,barSelector:'[role="bar"]',spinnerSelector:'[role="spinner"]',parent:"body",color:"#29d",includeCSS:!0,template:['<div class="bar" role="bar">','<div class="peg"></div>',"</div>",'<div class="spinner" role="spinner">','<div class="spinner-icon"></div>',"</div>"].join("")},ei=null,Y2=e=>{Object.assign(dt,e),dt.includeCSS&&tL(dt.color),hr=document.createElement("div"),hr.id=nt,hr.innerHTML=dt.template},Rc=e=>{const t=E0();e=T0(e,dt.minimum,1),ei=e===1?null:e;const r=J2(!t),n=r.querySelector(dt.barSelector),i=dt.speed,s=dt.easing;r.offsetWidth,eL(o=>{const a=dt.positionUsing==="translate3d"?{transition:`all ${i}ms ${s}`,transform:`translate3d(${tl(e)}%,0,0)`}:dt.positionUsing==="translate"?{transition:`all ${i}ms ${s}`,transform:`translate(${tl(e)}%,0)`}:{marginLeft:`${tl(e)}%`};for(const l in a)n.style[l]=a[l];if(e!==1)return setTimeout(o,i);r.style.transition="none",r.style.opacity="1",r.offsetWidth,setTimeout(()=>{r.style.transition=`all ${i}ms linear`,r.style.opacity="0",setTimeout(()=>{A0(),r.style.transition="",r.style.opacity="",o()},i)},i)})},E0=()=>typeof ei=="number",x0=()=>{ei||Rc(0);const e=function(){setTimeout(function(){ei&&(S0(),e())},dt.trickleSpeed)};dt.trickle&&e()},X2=e=>{!e&&!ei||(S0(.3+.5*Math.random()),Rc(1))},S0=e=>{const t=ei;if(t===null)return x0();if(!(t>1))return e=typeof e=="number"?e:(()=>{const r={.1:[0,.2],.04:[.2,.5],.02:[.5,.8],.005:[.8,.99]};for(const n in r)if(t>=r[n][0]&&t<r[n][1])return parseFloat(n);return 0})(),Rc(T0(t+e,0,.994))},J2=e=>{if(Q2())return document.getElementById(nt);document.documentElement.classList.add(`${nt}-busy`);const t=hr.querySelector(dt.barSelector),r=e?"-100":tl(ei||0),n=C0();return t.style.transition="all 0 linear",t.style.transform=`translate3d(${r}%,0,0)`,dt.showSpinner||hr.querySelector(dt.spinnerSelector)?.remove(),n!==document.body&&n.classList.add(`${nt}-custom-parent`),n.appendChild(hr),hr},C0=()=>Z2(dt.parent)?dt.parent:document.querySelector(dt.parent),A0=()=>{document.documentElement.classList.remove(`${nt}-busy`),C0().classList.remove(`${nt}-custom-parent`),hr?.remove()},Q2=()=>document.getElementById(nt)!==null,Z2=e=>typeof HTMLElement=="object"?e instanceof HTMLElement:e&&typeof e=="object"&&e.nodeType===1&&typeof e.nodeName=="string";function T0(e,t,r){return e<t?t:e>r?r:e}var tl=e=>(-1+e)*100,eL=(()=>{const e=[],t=()=>{const r=e.shift();r&&r(t)};return r=>{e.push(r),e.length===1&&t()}})(),tL=e=>{const t=document.createElement("style");t.textContent=`
    #${nt} {
      pointer-events: none;
    }

    #${nt} .bar {
      background: ${e};

      position: fixed;
      z-index: 1031;
      top: 0;
      left: 0;

      width: 100%;
      height: 2px;
    }

    #${nt} .peg {
      display: block;
      position: absolute;
      right: 0px;
      width: 100px;
      height: 100%;
      box-shadow: 0 0 10px ${e}, 0 0 5px ${e};
      opacity: 1.0;

      transform: rotate(3deg) translate(0px, -4px);
    }

    #${nt} .spinner {
      display: block;
      position: fixed;
      z-index: 1031;
      top: 15px;
      right: 15px;
    }

    #${nt} .spinner-icon {
      width: 18px;
      height: 18px;
      box-sizing: border-box;

      border: solid 2px transparent;
      border-top-color: ${e};
      border-left-color: ${e};
      border-radius: 50%;

      animation: ${nt}-spinner 400ms linear infinite;
    }

    .${nt}-custom-parent {
      overflow: hidden;
      position: relative;
    }

    .${nt}-custom-parent #${nt} .spinner,
    .${nt}-custom-parent #${nt} .bar {
      position: absolute;
    }

    @keyframes ${nt}-spinner {
      0%   { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  `,document.head.appendChild(t)},rL=()=>{hr&&(hr.style.display="")},nL=()=>{hr&&(hr.style.display="none")},wr={configure:Y2,isStarted:E0,done:X2,set:Rc,remove:A0,start:x0,status:ei,show:rL,hide:nL},iL=class{constructor(){this.hideCount=0}start(){wr.start()}reveal(e=!1){this.hideCount=Math.max(0,this.hideCount-1),(e||this.hideCount===0)&&wr.show()}hide(){this.hideCount++,wr.hide()}set(e){wr.set(Math.max(0,Math.min(1,e)))}finish(){wr.done()}reset(){wr.set(0)}remove(){wr.done(),wr.remove()}isStarted(){return wr.isStarted()}getStatus(){return wr.status}},Bt=new iL;Bt.reveal;Bt.hide;function sL(e){document.addEventListener("inertia:start",t=>oL(t,e)),document.addEventListener("inertia:progress",aL)}function oL(e,t){e.detail.visit.showProgress||Bt.hide();const r=setTimeout(()=>Bt.start(),t);document.addEventListener("inertia:finish",n=>lL(n,r),{once:!0})}function aL(e){Bt.isStarted()&&e.detail.progress?.percentage&&Bt.set(Math.max(Bt.getStatus(),e.detail.progress.percentage/100*.9))}function lL(e,t){clearTimeout(t),Bt.isStarted()&&(e.detail.visit.completed?Bt.finish():e.detail.visit.interrupted?Bt.reset():e.detail.visit.cancelled&&Bt.remove())}function cL({delay:e=250,color:t="#29d",includeCSS:r=!0,showSpinner:n=!1}={}){sL(e),wr.configure({showSpinner:n,includeCSS:r,color:t})}var Lt=new K2;let zo=is.create(),k0=(e,t)=>`${e.method}:${e.baseURL??t.defaults.baseURL??""}${e.url}`,O0=e=>e.status===204&&e.headers["precognition-success"]==="true";const zl={},Hn={get:(e,t={},r={})=>Hs(zs("get",e,t,r)),post:(e,t={},r={})=>Hs(zs("post",e,t,r)),patch:(e,t={},r={})=>Hs(zs("patch",e,t,r)),put:(e,t={},r={})=>Hs(zs("put",e,t,r)),delete:(e,t={},r={})=>Hs(zs("delete",e,t,r)),use(e){return zo=e,Hn},axios(){return zo},fingerprintRequestsUsing(e){return k0=e===null?()=>null:e,Hn},determineSuccessUsing(e){return O0=e,Hn}},zs=(e,t,r,n)=>({url:t,method:e,...n,...["get","delete"].includes(e)?{params:Oh({},r,n?.params)}:{data:Oh({},r,n?.data)}}),Hs=(e={})=>{const t=[uL,hL,fL].reduce((r,n)=>n(r),e);return(t.onBefore??(()=>!0))()===!1?Promise.resolve(null):((t.onStart??(()=>null))(),zo.request(t).then(async r=>{t.precognitive&&Fb(r);const n=r.status;let i=r;return t.precognitive&&t.onPrecognitionSuccess&&O0(i)&&(i=await Promise.resolve(t.onPrecognitionSuccess(i)??i)),t.onSuccess&&dL(n)&&(i=await Promise.resolve(t.onSuccess(i)??i)),(Lb(t,n)??(o=>o))(i)??i},r=>pL(r)?Promise.reject(r):(t.precognitive&&Fb(r.response),(Lb(t,r.response.status)??((i,s)=>Promise.reject(s)))(r.response,r))).finally(t.onFinish??(()=>null)))},uL=e=>{const t=e.only??e.validate;return{...e,timeout:e.timeout??zo.defaults.timeout??3e4,precognitive:e.precognitive!==!1,fingerprint:typeof e.fingerprint>"u"?k0(e,zo):e.fingerprint,headers:{...e.headers,"Content-Type":mL(e),...e.precognitive!==!1?{Precognition:!0}:{},...t?{"Precognition-Validate-Only":Array.from(t).join()}:{}}}},dL=e=>e>=200&&e<300,hL=e=>(typeof e.fingerprint!="string"||(zl[e.fingerprint]?.abort(),delete zl[e.fingerprint]),e),fL=e=>typeof e.fingerprint!="string"||e.signal||e.cancelToken||!e.precognitive?e:(zl[e.fingerprint]=new AbortController,{...e,signal:zl[e.fingerprint].signal}),Fb=e=>{if(e.headers?.precognition!=="true")throw Error("Did not receive a Precognition response. Ensure you have the Precognition middleware in place for the route.")},pL=e=>!_v(e)||typeof e.response?.status!="number"||yv(e),Lb=(e,t)=>({401:e.onUnauthorized,403:e.onForbidden,404:e.onNotFound,409:e.onConflict,422:e.onValidationError,423:e.onLocked})[t],mL=e=>e.headers?.["Content-Type"]??e.headers?.["Content-type"]??e.headers?.["content-type"]??(N0(e.data)?"multipart/form-data":"application/json"),N0=e=>yp(e)||typeof e=="object"&&e!==null&&Object.values(e).some(t=>N0(t)),yp=e=>typeof File<"u"&&e instanceof File||e instanceof Blob||typeof FileList<"u"&&e instanceof FileList&&e.length>0,gL=(e,t={})=>{const r={errorsChanged:[],touchedChanged:[],validatingChanged:[],validatedChanged:[]};let n=!1,i=!1;const s=I=>I!==i?(i=I,r.validatingChanged):[];let o=[];const a=I=>{const C=[...new Set(I)];return o.length!==C.length||!C.every(g=>o.includes(g))?(o=C,r.validatedChanged):[]},l=()=>o.filter(I=>typeof d[I]>"u");let u=[];const c=I=>{const C=[...new Set(I)];return u.length!==C.length||!C.every(g=>u.includes(g))?(u=C,r.touchedChanged):[]};let d={};const p=I=>{const C=vL(I);return Jn(d,C)?[]:(d=C,r.errorsChanged)},m=I=>{const C={...d};return delete C[bo(I)],p(C)},h=()=>Object.keys(d).length>0;let f=1500;const b=I=>{f=I,k.cancel(),k=x()};let y=t,w=null,v=[],_=null;const x=()=>cF(I=>{e({get:(C,g={},L={})=>Hn.get(C,A(g),S(L,I,g)),post:(C,g={},L={})=>Hn.post(C,A(g),S(L,I,g)),patch:(C,g={},L={})=>Hn.patch(C,A(g),S(L,I,g)),put:(C,g={},L={})=>Hn.put(C,A(g),S(L,I,g)),delete:(C,g={},L={})=>Hn.delete(C,A(g),S(L,I,g))}).catch(C=>yv(C)||_v(C)&&C.response?.status===422?null:Promise.reject(C))},f,{leading:!0,trailing:!0});let k=x();const S=(I,C,g={})=>{const L={...I,...C},B=Array.from(L.only??L.validate??u);return{...C,...jE(I,C),only:B,timeout:L.timeout??5e3,onValidationError:(M,z)=>([...a([...o,...B]),...p(Oh(Ig({...d},B),M.data.errors))].forEach(P=>P()),L.onValidationError?L.onValidationError(M,z):Promise.reject(z)),onSuccess:M=>(a([...o,...B]).forEach(z=>z()),L.onSuccess?L.onSuccess(M):M),onPrecognitionSuccess:M=>([...a([...o,...B]),...p(Ig({...d},B))].forEach(z=>z()),L.onPrecognitionSuccess?L.onPrecognitionSuccess(M):M),onBefore:()=>L.onBeforeValidation&&L.onBeforeValidation({data:g,touched:u},{data:y,touched:v})===!1||(L.onBefore||(()=>!0))()===!1?!1:(_=u,w=g,!0),onStart:()=>{s(!0).forEach(M=>M()),(L.onStart??(()=>null))()},onFinish:()=>{s(!1).forEach(M=>M()),v=_,y=w,_=w=null,(L.onFinish??(()=>null))()}}},N=(I,C,g)=>{if(typeof I>"u"){const L=Array.from(g?.only??g?.validate??[]);c([...u,...L]).forEach(B=>B()),k(g??{});return}if(yp(C)&&!n){console.warn('Precognition file validation is not active. Call the "validateFiles" function on your form to enable it.');return}I=bo(I),Cr(y,I)!==C&&(c([I,...u]).forEach(L=>L()),k(g??{}))},A=I=>n===!1?Lh(I):I,T={touched:()=>u,validate(I,C,g){return typeof I=="object"&&!("target"in I)&&(g=I,I=C=void 0),N(I,C,g),T},touch(I){const C=Array.isArray(I)?I:[bo(I)];return c([...u,...C]).forEach(g=>g()),T},validating:()=>i,valid:l,errors:()=>d,hasErrors:h,setErrors(I){return p(I).forEach(C=>C()),T},forgetError(I){return m(I).forEach(C=>C()),T},defaults(I){return t=I,y=I,T},reset(...I){if(I.length===0)c([]).forEach(C=>C());else{const C=[...u];I.forEach(g=>{C.includes(g)&&C.splice(C.indexOf(g),1),zr(y,g,Cr(t,g))}),c(C).forEach(g=>g())}return T},setTimeout(I){return b(I),T},on(I,C){return r[I].push(C),T},validateFiles(){return n=!0,T},withoutFileValidation(){return n=!1,T}};return T},bL=e=>Object.keys(e).reduce((t,r)=>({...t,[r]:Array.isArray(e[r])?e[r][0]:e[r]}),{}),vL=e=>Object.keys(e).reduce((t,r)=>({...t,[r]:typeof e[r]=="string"?[e[r]]:e[r]}),{}),bo=e=>typeof e!="string"?e.target.name:e,Lh=e=>{const t={...e};return Object.keys(t).forEach(r=>{const n=t[r];if(n!==null){if(yp(n)){delete t[r];return}if(Array.isArray(n)){t[r]=Object.values(Lh({...n}));return}if(typeof n=="object"){t[r]=Lh(t[r]);return}}}),t};var yL={created(){if(!this.$options.remember)return;Array.isArray(this.$options.remember)&&(this.$options.remember={data:this.$options.remember}),typeof this.$options.remember=="string"&&(this.$options.remember={data:[this.$options.remember]}),typeof this.$options.remember.data=="string"&&(this.$options.remember={data:[this.$options.remember.data]});const e=this.$options.remember.key instanceof Function?this.$options.remember.key.call(this):this.$options.remember.key,t=Lt.restore(e),r=this.$options.remember.data.filter(i=>!(this[i]!==null&&typeof this[i]=="object"&&this[i].__rememberable===!1)),n=i=>this[i]!==null&&typeof this[i]=="object"&&typeof this[i].__remember=="function"&&typeof this[i].__restore=="function";r.forEach(i=>{this[i]!==void 0&&t!==void 0&&t[i]!==void 0&&(n(i)?this[i].__restore(t[i]):this[i]=t[i]),this.$watch(i,()=>{Lt.remember(r.reduce((s,o)=>({...s,[o]:ut(n(o)?this[o].__remember():this[o])}),{}),e)},{immediate:!0,deep:!0})})}},_L=yL,_d=null,wd=!1;function wL(e){if(wd)return;_d===null&&(wd=!0,_d=new Set(Object.keys(P0({}))),wd=!1);const t=Object.keys(e).filter(r=>_d.has(r));t.length>0&&console.error(`[Inertia] useForm() data contains field(s) that conflict with form properties: ${t.map(r=>`"${r}"`).join(", ")}. These fields will be overwritten by form methods/properties. Please rename these fields.`)}function P0(...e){let{rememberKey:t,data:r,precognitionEndpoint:n}=vd.parseUseFormArguments(...e);const i=t?Lt.restore(t):null;let s=ut(typeof r=="function"?r():r);wL(s);let o=null,a,l=h=>h,u=null,c=[],d=!1;const m=Es({...i?i.data:ut(s),isDirty:!1,errors:i?i.errors:{},hasErrors:!1,processing:!1,progress:null,wasSuccessful:!1,recentlySuccessful:!1,withPrecognition(...h){n=vd.createWayfinderCallback(...h);const f=this;let b=!1;const y=gL(v=>{const{method:_,url:x}=n(),k=ut(l(this.data()));return v[_](x,k)},ut(s));u=y,y.on("validatingChanged",()=>{f.validating=y.validating()}).on("validatedChanged",()=>{f.__valid=y.valid()}).on("touchedChanged",()=>{f.__touched=y.touched()}).on("errorsChanged",()=>{const v=b?y.errors():bL(y.errors());this.errors={},this.setError(v),f.__valid=y.valid()});const w=(v,_)=>(_(v),v);return Object.assign(f,{__touched:[],__valid:[],validating:!1,validator:()=>y,withAllErrors:()=>w(f,()=>b=!0),valid:v=>f.__valid.includes(v),invalid:v=>v in this.errors,setValidationTimeout:v=>w(f,()=>y.setTimeout(v)),validateFiles:()=>w(f,()=>y.validateFiles()),withoutFileValidation:()=>w(f,()=>y.withoutFileValidation()),touch:(v,..._)=>(Array.isArray(v)?y.touch(v):typeof v=="string"?y.touch([v,..._]):y.touch(v),f),touched:v=>typeof v=="string"?f.__touched.includes(v):f.__touched.length>0,validate:(v,_)=>{if(typeof v=="object"&&!("target"in v)&&(_=v,v=void 0),v===void 0)y.validate(_);else{const x=bo(v),k=l(this.data());y.validate(x,Cr(k,x),_)}return f},setErrors:v=>w(f,()=>this.setError(v)),forgetError:v=>w(f,()=>this.clearErrors(bo(v)))}),f},data(){return Object.keys(s).reduce((h,f)=>zr(h,f,Cr(this,f)),{})},transform(h){return l=h,this},defaults(h,f){if(typeof r=="function")throw new Error("You cannot call `defaults()` when using a function to define your form data.");return d=!0,typeof h>"u"?(s=ut(this.data()),this.isDirty=!1):s=typeof h=="string"?zr(ut(s),h,f):Object.assign({},ut(s),h),u?.defaults(s),this},reset(...h){const f=ut(typeof r=="function"?r():s),b=ut(f);return h.length===0?(s=b,Object.assign(this,f)):h.filter(y=>t0(b,y)).forEach(y=>{zr(s,y,Cr(b,y)),zr(this,y,Cr(f,y))}),u?.reset(...h),this},setError(h,f){const b=typeof h=="string"?{[h]:f}:h;return Object.assign(this.errors,b),this.hasErrors=Object.keys(this.errors).length>0,u?.setErrors(b),this},clearErrors(...h){return this.errors=Object.keys(this.errors).reduce((f,b)=>({...f,...h.length>0&&!h.includes(b)?{[b]:this.errors[b]}:{}}),{}),this.hasErrors=Object.keys(this.errors).length>0,u&&(h.length===0?u.setErrors({}):h.forEach(u.forgetError)),this},resetAndClearErrors(...h){return this.reset(...h),this.clearErrors(...h),this},submit(...h){const{method:f,url:b,options:y}=vd.parseSubmitArguments(h,n);d=!1;const w={...y,onCancelToken:_=>{if(o=_,y.onCancelToken)return y.onCancelToken(_)},onBefore:_=>{if(this.wasSuccessful=!1,this.recentlySuccessful=!1,clearTimeout(a),y.onBefore)return y.onBefore(_)},onStart:_=>{if(this.processing=!0,y.onStart)return y.onStart(_)},onProgress:_=>{if(this.progress=_??null,y.onProgress)return y.onProgress(_)},onSuccess:async _=>{this.processing=!1,this.progress=null,this.clearErrors(),this.wasSuccessful=!0,this.recentlySuccessful=!0,a=setTimeout(()=>this.recentlySuccessful=!1,Mh.get("form.recentlySuccessfulDuration"));const x=y.onSuccess?await y.onSuccess(_):null;return d||(s=ut(this.data()),this.isDirty=!1),x},onError:_=>{if(this.processing=!1,this.progress=null,this.clearErrors().setError(_),y.onError)return y.onError(_)},onCancel:()=>{if(this.processing=!1,this.progress=null,y.onCancel)return y.onCancel()},onFinish:_=>{if(this.processing=!1,this.progress=null,o=null,y.onFinish)return y.onFinish(_)}},v=l(this.data());f==="delete"?Lt.delete(b,{...w,data:v}):Lt[f](b,v,w)},get(h,f){this.submit("get",h,f)},post(h,f){this.submit("post",h,f)},put(h,f){this.submit("put",h,f)},patch(h,f){this.submit("patch",h,f)},delete(h,f){this.submit("delete",h,f)},cancel(){o&&o.cancel()},dontRemember(...h){return c=h,this},__rememberable:t===null,__remember(){const h=this.data();if(c.length>0){const f={...h};return c.forEach(b=>delete f[b]),{data:f,errors:this.errors}}return{data:h,errors:this.errors}},__restore(h){Object.assign(this,h.data),this.setError(h.errors)}});return wi(m,h=>{m.isDirty=!Jn(m.data(),s);const f=Lt.restore(t),b=ut(h.__remember());t&&!Jn(f,b)&&Lt.remember(b,t)},{immediate:!0,deep:!0}),n?m.withPrecognition(n):m}var Kt=Wn(void 0),Xe=Wn(),Ed=of(null),$a=Wn(void 0),Rh,EL=Cs({name:"Inertia",props:{initialPage:{type:Object,required:!0},initialComponent:{type:Object,required:!1},resolveComponent:{type:Function,required:!1},titleCallback:{type:Function,required:!1,default:e=>e},onHeadUpdate:{type:Function,required:!1,default:()=>()=>{}}},setup({initialPage:e,initialComponent:t,resolveComponent:r,titleCallback:n,onHeadUpdate:i}){Kt.value=t?dl(t):void 0,Xe.value={...e,flash:e.flash??{}},$a.value=void 0;const s=typeof window>"u";return Rh=G2(s,n||(o=>o),i||(()=>{})),s||(Lt.init({initialPage:e,resolveComponent:r,swapComponent:async o=>{Kt.value=dl(o.component),Xe.value=o.page,$a.value=o.preserveState?$a.value:Date.now()},onFlash:o=>{Xe.value={...Xe.value,flash:o}}}),Lt.on("navigate",()=>Rh.forceUpdate())),()=>{if(Kt.value){Kt.value.inheritAttrs=!!Kt.value.inheritAttrs;const o=pn(Kt.value,{...Xe.value.props,key:$a.value});return Ed.value&&(Kt.value.layout=Ed.value,Ed.value=null),Kt.value.layout?typeof Kt.value.layout=="function"?Kt.value.layout(pn,o):(Array.isArray(Kt.value.layout)?Kt.value.layout:[Kt.value.layout]).concat(o).reverse().reduce((a,l)=>(l.inheritAttrs=!!l.inheritAttrs,pn(l,{...Xe.value.props},()=>a))):o}}}}),Rb=EL,Mb={install(e){Lt.form=P0,Object.defineProperty(e.config.globalProperties,"$inertia",{get:()=>Lt}),Object.defineProperty(e.config.globalProperties,"$page",{get:()=>Xe.value}),Object.defineProperty(e.config.globalProperties,"$headManager",{get:()=>Rh}),e.mixin(_L)}};function zV(){return Es({props:Pt(()=>Xe.value?.props),url:Pt(()=>Xe.value?.url),component:Pt(()=>Xe.value?.component),version:Pt(()=>Xe.value?.version),clearHistory:Pt(()=>Xe.value?.clearHistory),deferredProps:Pt(()=>Xe.value?.deferredProps),mergeProps:Pt(()=>Xe.value?.mergeProps),prependProps:Pt(()=>Xe.value?.prependProps),deepMergeProps:Pt(()=>Xe.value?.deepMergeProps),matchPropsOn:Pt(()=>Xe.value?.matchPropsOn),rememberedState:Pt(()=>Xe.value?.rememberedState),encryptHistory:Pt(()=>Xe.value?.encryptHistory),flash:Pt(()=>Xe.value?.flash)})}async function xL({id:e="app",resolve:t,setup:r,title:n,progress:i={},page:s,render:o,defaults:a={}}){Mh.replace(a);const l=typeof window>"u",u=Mh.get("future.useScriptElementForInitialPage"),c=s||N2(e,u),d=h=>Promise.resolve(t(h)).then(f=>f.default||f);let p=[];const m=await Promise.all([d(c.component),Lt.decryptHistory().catch(()=>{})]).then(([h])=>{const f={initialPage:c,initialComponent:h,resolveComponent:d,titleCallback:n};return r(l?{el:null,App:Rb,props:{...f,onHeadUpdate:w=>p=w},plugin:Mb}:{el:document.getElementById(e),App:Rb,props:f,plugin:Mb})});if(!l&&i&&cL(i),l&&o){const h=()=>u?[pn("script",{"data-page":e,type:"application/json",innerHTML:JSON.stringify(c).replace(/\//g,"\\/")}),pn("div",{id:e,innerHTML:m?o(m):""})]:pn("div",{id:e,"data-page":JSON.stringify(c),innerHTML:m?o(m):""}),f=await o(Pf({render:()=>h()}));return{head:p,body:f}}}var HV=Cs({name:"Deferred",props:{data:{type:[String,Array],required:!0}},render(){const e=Array.isArray(this.$props.data)?this.$props.data:[this.$props.data];if(!this.$slots.fallback)throw new Error("`<Deferred>` requires a `<template #fallback>` slot");return e.every(t=>this.$page.props[t]!==void 0)?this.$slots.default?.():this.$slots.fallback()}}),SL=Cs({props:{title:{type:String,required:!1}},data(){return{provider:this.$headManager.createProvider()}},beforeUnmount(){this.provider.disconnect()},methods:{isUnaryTag(e){return typeof e.type=="string"&&["area","base","br","col","embed","hr","img","input","keygen","link","meta","param","source","track","wbr"].indexOf(e.type)>-1},renderTagStart(e){e.props=e.props||{},e.props[this.provider.preferredAttribute()]=e.props["head-key"]!==void 0?e.props["head-key"]:"";const t=Object.keys(e.props).reduce((r,n)=>{const i=String(e.props[n]);return["key","head-key"].includes(n)?r:i===""?r+` ${n}`:r+` ${n}="${bF(i)}"`},"");return`<${String(e.type)}${t}>`},renderTagChildren(e){const{children:t}=e;return typeof t=="string"?t:Array.isArray(t)?t.reduce((r,n)=>r+this.renderTag(n),""):""},isFunctionNode(e){return typeof e.type=="function"},isComponentNode(e){return typeof e.type=="object"},isCommentNode(e){return/(comment|cmt)/i.test(e.type.toString())},isFragmentNode(e){return/(fragment|fgt|symbol\(\))/i.test(e.type.toString())},isTextNode(e){return/(text|txt)/i.test(e.type.toString())},renderTag(e){if(this.isTextNode(e))return String(e.children);if(this.isFragmentNode(e))return"";if(this.isCommentNode(e))return"";let t=this.renderTagStart(e);return e.children&&(t+=this.renderTagChildren(e)),this.isUnaryTag(e)||(t+=`</${String(e.type)}>`),t},addTitleElement(e){return this.title&&!e.find(t=>t.startsWith("<title"))&&e.push(`<title ${this.provider.preferredAttribute()}>${this.title}</title>`),e},renderNodes(e){const t=e.flatMap(r=>this.resolveNode(r)).map(r=>this.renderTag(r)).filter(r=>r);return this.addTitleElement(t)},resolveNode(e){return this.isFunctionNode(e)?this.resolveNode(e.type()):this.isComponentNode(e)?(console.warn("Using components in the <Head> component is not supported."),[]):this.isTextNode(e)&&e.children?e:this.isFragmentNode(e)&&e.children?e.children.flatMap(t=>this.resolveNode(t)):this.isCommentNode(e)?[]:e}},render(){this.provider.update(this.renderNodes(this.$slots.default?this.$slots.default():[]))}}),qV=SL,Mh=Ii.extend({});var Dh="",$h="";function Db(e){Dh=e}function CL(e=""){if(!Dh){const t=document.querySelector("[data-webawesome]");if(t?.hasAttribute("data-webawesome")){const r=new URL(t.getAttribute("data-webawesome")??"",window.location.href).pathname;Db(r)}else{const n=[...document.getElementsByTagName("script")].find(i=>i.src.endsWith("webawesome.js")||i.src.endsWith("webawesome.loader.js")||i.src.endsWith("webawesome.ssr-loader.js"));if(n){const i=String(n.getAttribute("src"));Db(i.split("/").slice(0,-1).join("/"))}}}return Dh.replace(/\/$/,"")+(e?`/${e.replace(/^\//,"")}`:"")}function AL(e){$h=e}function TL(){if(!$h){const e=document.querySelector("[data-fa-kit-code]");e&&AL(e.getAttribute("data-fa-kit-code")||"")}return $h}var tn="7.0.1";function kL(e,t,r){const n=TL(),i=n.length>0;let s="solid";return t==="notdog"?(r==="solid"&&(s="solid"),r==="duo-solid"&&(s="duo-solid"),`https://ka-p.fontawesome.com/releases/v${tn}/svgs/notdog-${s}/${e}.svg?token=${encodeURIComponent(n)}`):t==="chisel"?`https://ka-p.fontawesome.com/releases/v${tn}/svgs/chisel-regular/${e}.svg?token=${encodeURIComponent(n)}`:t==="etch"?`https://ka-p.fontawesome.com/releases/v${tn}/svgs/etch-solid/${e}.svg?token=${encodeURIComponent(n)}`:t==="jelly"?(r==="regular"&&(s="regular"),r==="duo-regular"&&(s="duo-regular"),r==="fill-regular"&&(s="fill-regular"),`https://ka-p.fontawesome.com/releases/v${tn}/svgs/jelly-${s}/${e}.svg?token=${encodeURIComponent(n)}`):t==="slab"?((r==="solid"||r==="regular")&&(s="regular"),r==="press-regular"&&(s="press-regular"),`https://ka-p.fontawesome.com/releases/v${tn}/svgs/slab-${s}/${e}.svg?token=${encodeURIComponent(n)}`):t==="thumbprint"?`https://ka-p.fontawesome.com/releases/v${tn}/svgs/thumbprint-light/${e}.svg?token=${encodeURIComponent(n)}`:t==="whiteboard"?`https://ka-p.fontawesome.com/releases/v${tn}/svgs/whiteboard-semibold/${e}.svg?token=${encodeURIComponent(n)}`:(t==="classic"&&(r==="thin"&&(s="thin"),r==="light"&&(s="light"),r==="regular"&&(s="regular"),r==="solid"&&(s="solid")),t==="sharp"&&(r==="thin"&&(s="sharp-thin"),r==="light"&&(s="sharp-light"),r==="regular"&&(s="sharp-regular"),r==="solid"&&(s="sharp-solid")),t==="duotone"&&(r==="thin"&&(s="duotone-thin"),r==="light"&&(s="duotone-light"),r==="regular"&&(s="duotone-regular"),r==="solid"&&(s="duotone")),t==="sharp-duotone"&&(r==="thin"&&(s="sharp-duotone-thin"),r==="light"&&(s="sharp-duotone-light"),r==="regular"&&(s="sharp-duotone-regular"),r==="solid"&&(s="sharp-duotone-solid")),t==="brands"&&(s="brands"),i?`https://ka-p.fontawesome.com/releases/v${tn}/svgs/${s}/${e}.svg?token=${encodeURIComponent(n)}`:`https://ka-f.fontawesome.com/releases/v${tn}/svgs/${s}/${e}.svg`)}var OL={name:"default",resolver:(e,t="classic",r="solid")=>kL(e,t,r),mutator:(e,t)=>{if(t?.family&&!e.hasAttribute("data-duotone-initialized")){const{family:r,variant:n}=t;if(r==="duotone"||r==="sharp-duotone"||r==="notdog"&&n==="duo-solid"||r==="jelly"&&n==="duo-regular"||r==="thumbprint"){const i=[...e.querySelectorAll("path")],s=i.find(a=>!a.hasAttribute("opacity")),o=i.find(a=>a.hasAttribute("opacity"));if(!s||!o)return;if(s.setAttribute("data-duotone-primary",""),o.setAttribute("data-duotone-secondary",""),t.swapOpacity&&s&&o){const a=o.getAttribute("opacity")||"0.4";s.style.setProperty("--path-opacity",a),o.style.setProperty("--path-opacity","1")}e.setAttribute("data-duotone-initialized","")}}}},NL=OL;new MutationObserver(e=>{for(const{addedNodes:t}of e)for(const r of t)r.nodeType===Node.ELEMENT_NODE&&PL(r)});async function PL(e){const t=e instanceof Element?e.tagName.toLowerCase():"",r=t?.startsWith("wa-"),n=[...e.querySelectorAll(":not(:defined)")].map(o=>o.tagName.toLowerCase()).filter(o=>o.startsWith("wa-"));r&&!customElements.get(t)&&n.push(t);const i=[...new Set(n)],s=await Promise.allSettled(i.map(o=>IL(o)));for(const o of s)o.status==="rejected"&&console.warn(o.reason);await new Promise(requestAnimationFrame),e.dispatchEvent(new CustomEvent("wa-discovery-complete",{bubbles:!1,cancelable:!1,composed:!0}))}function IL(e){if(customElements.get(e))return Promise.resolve();const t=e.replace(/^wa-/i,""),r=CL(`components/${t}/${t}.js`);return new Promise((n,i)=>{import(r).then(()=>n()).catch(()=>i(new Error(`Unable to autoload <${e}> from ${r}`)))})}const Vh=new Set,Qi=new Map;let pi,_p="ltr",wp="en";const I0=typeof MutationObserver<"u"&&typeof document<"u"&&typeof document.documentElement<"u";if(I0){const e=new MutationObserver(L0);_p=document.documentElement.dir||"ltr",wp=document.documentElement.lang||navigator.language,e.observe(document.documentElement,{attributes:!0,attributeFilter:["dir","lang"]})}function F0(...e){e.map(t=>{const r=t.$code.toLowerCase();Qi.has(r)?Qi.set(r,Object.assign(Object.assign({},Qi.get(r)),t)):Qi.set(r,t),pi||(pi=t)}),L0()}function L0(){I0&&(_p=document.documentElement.dir||"ltr",wp=document.documentElement.lang||navigator.language),[...Vh.keys()].map(e=>{typeof e.requestUpdate=="function"&&e.requestUpdate()})}let FL=class{constructor(t){this.host=t,this.host.addController(this)}hostConnected(){Vh.add(this.host)}hostDisconnected(){Vh.delete(this.host)}dir(){return`${this.host.dir||_p}`.toLowerCase()}lang(){return`${this.host.lang||wp}`.toLowerCase()}getTranslationData(t){var r,n;const i=new Intl.Locale(t.replace(/_/g,"-")),s=i?.language.toLowerCase(),o=(n=(r=i?.region)===null||r===void 0?void 0:r.toLowerCase())!==null&&n!==void 0?n:"",a=Qi.get(`${s}-${o}`),l=Qi.get(s);return{locale:i,language:s,region:o,primary:a,secondary:l}}exists(t,r){var n;const{primary:i,secondary:s}=this.getTranslationData((n=r.lang)!==null&&n!==void 0?n:this.lang());return r=Object.assign({includeFallback:!1},r),!!(i&&i[t]||s&&s[t]||r.includeFallback&&pi&&pi[t])}term(t,...r){const{primary:n,secondary:i}=this.getTranslationData(this.lang());let s;if(n&&n[t])s=n[t];else if(i&&i[t])s=i[t];else if(pi&&pi[t])s=pi[t];else return console.error(`No translation found for: ${String(t)}`),String(t);return typeof s=="function"?s(...r):s}date(t,r){return t=new Date(t),new Intl.DateTimeFormat(this.lang(),r).format(t)}number(t,r){return t=Number(t),isNaN(t)?"":new Intl.NumberFormat(this.lang(),r).format(t)}relativeTime(t,r,n){return new Intl.RelativeTimeFormat(this.lang(),n).format(t,r)}};var R0={$code:"en",$name:"English",$dir:"ltr",carousel:"Carousel",clearEntry:"Clear entry",close:"Close",copied:"Copied",copy:"Copy",currentValue:"Current value",error:"Error",goToSlide:(e,t)=>`Go to slide ${e} of ${t}`,hidePassword:"Hide password",loading:"Loading",nextSlide:"Next slide",numOptionsSelected:e=>e===0?"No options selected":e===1?"1 option selected":`${e} options selected`,pauseAnimation:"Pause animation",playAnimation:"Play animation",previousSlide:"Previous slide",progress:"Progress",remove:"Remove",resize:"Resize",scrollableRegion:"Scrollable region",scrollToEnd:"Scroll to end",scrollToStart:"Scroll to start",selectAColorFromTheScreen:"Select a color from the screen",showPassword:"Show password",slideNum:e=>`Slide ${e}`,toggleColorFormat:"Toggle color format",zoomIn:"Zoom in",zoomOut:"Zoom out"};F0(R0);var LL=R0;var ks=class extends FL{};F0(LL);function RL(e){return`data:image/svg+xml,${encodeURIComponent(e)}`}var xd={solid:{check:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M434.8 70.1c14.3 10.4 17.5 30.4 7.1 44.7l-256 352c-5.5 7.6-14 12.3-23.4 13.1s-18.5-2.7-25.1-9.3l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l101.5 101.5 234-321.7c10.4-14.3 30.4-17.5 44.7-7.1z"/></svg>',"chevron-down":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M201.4 406.6c12.5 12.5 32.8 12.5 45.3 0l192-192c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L224 338.7 54.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l192 192z"/></svg>',"chevron-left":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l192 192c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L77.3 256 246.6 86.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-192 192z"/></svg>',"chevron-right":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M311.1 233.4c12.5 12.5 12.5 32.8 0 45.3l-192 192c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L243.2 256 73.9 86.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l192 192z"/></svg>',circle:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0z"/></svg>',eyedropper:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M341.6 29.2l-101.6 101.6-9.4-9.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3l-9.4-9.4 101.6-101.6c39-39 39-102.2 0-141.1s-102.2-39-141.1 0zM55.4 323.3c-15 15-23.4 35.4-23.4 56.6l0 42.4-26.6 39.9c-8.5 12.7-6.8 29.6 4 40.4s27.7 12.5 40.4 4l39.9-26.6 42.4 0c21.2 0 41.6-8.4 56.6-23.4l109.4-109.4-45.3-45.3-109.4 109.4c-3 3-7.1 4.7-11.3 4.7l-36.1 0 0-36.1c0-4.2 1.7-8.3 4.7-11.3l109.4-109.4-45.3-45.3-109.4 109.4z"/></svg>',"grip-vertical":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M128 40c0-22.1-17.9-40-40-40L40 0C17.9 0 0 17.9 0 40L0 88c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zm0 192c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zM0 424l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zM320 40c0-22.1-17.9-40-40-40L232 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zM192 232l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zM320 424c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48z"/></svg>',indeterminate:'<svg part="indeterminate-icon" class="icon" viewBox="0 0 16 16"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" stroke-linecap="round"><g stroke="currentColor" stroke-width="2"><g transform="translate(2.285714 6.857143)"><path d="M10.2857143,1.14285714 L1.14285714,1.14285714"/></g></g></g></svg>',minus:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M0 256c0-17.7 14.3-32 32-32l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 288c-17.7 0-32-14.3-32-32z"/></svg>',pause:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M48 32C21.5 32 0 53.5 0 80L0 432c0 26.5 21.5 48 48 48l64 0c26.5 0 48-21.5 48-48l0-352c0-26.5-21.5-48-48-48L48 32zm224 0c-26.5 0-48 21.5-48 48l0 352c0 26.5 21.5 48 48 48l64 0c26.5 0 48-21.5 48-48l0-352c0-26.5-21.5-48-48-48l-64 0z"/></svg>',play:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M91.2 36.9c-12.4-6.8-27.4-6.5-39.6 .7S32 57.9 32 72l0 368c0 14.1 7.5 27.2 19.6 34.4s27.2 7.5 39.6 .7l336-184c12.8-7 20.8-20.5 20.8-35.1s-8-28.1-20.8-35.1l-336-184z"/></svg>',star:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M309.5-18.9c-4.1-8-12.4-13.1-21.4-13.1s-17.3 5.1-21.4 13.1L193.1 125.3 33.2 150.7c-8.9 1.4-16.3 7.7-19.1 16.3s-.5 18 5.8 24.4l114.4 114.5-25.2 159.9c-1.4 8.9 2.3 17.9 9.6 23.2s16.9 6.1 25 2L288.1 417.6 432.4 491c8 4.1 17.7 3.3 25-2s11-14.2 9.6-23.2L441.7 305.9 556.1 191.4c6.4-6.4 8.6-15.8 5.8-24.4s-10.1-14.9-19.1-16.3L383 125.3 309.5-18.9z"/></svg>',user:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M224 248a120 120 0 1 0 0-240 120 120 0 1 0 0 240zm-29.7 56C95.8 304 16 383.8 16 482.3 16 498.7 29.3 512 45.7 512l356.6 0c16.4 0 29.7-13.3 29.7-29.7 0-98.5-79.8-178.3-178.3-178.3l-59.4 0z"/></svg>',xmark:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M55.1 73.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L147.2 256 9.9 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192.5 301.3 329.9 438.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.8 256 375.1 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192.5 210.7 55.1 73.4z"/></svg>'},regular:{"circle-question":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M464 256a208 208 0 1 0 -416 0 208 208 0 1 0 416 0zM0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0zm256-80c-17.7 0-32 14.3-32 32 0 13.3-10.7 24-24 24s-24-10.7-24-24c0-44.2 35.8-80 80-80s80 35.8 80 80c0 47.2-36 67.2-56 74.5l0 3.8c0 13.3-10.7 24-24 24s-24-10.7-24-24l0-8.1c0-20.5 14.8-35.2 30.1-40.2 6.4-2.1 13.2-5.5 18.2-10.3 4.3-4.2 7.7-10 7.7-19.6 0-17.7-14.3-32-32-32zM224 368a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>',"circle-xmark":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M256 48a208 208 0 1 1 0 416 208 208 0 1 1 0-416zm0 464a256 256 0 1 0 0-512 256 256 0 1 0 0 512zM167 167c-9.4 9.4-9.4 24.6 0 33.9l55 55-55 55c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l55-55 55 55c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-55-55 55-55c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-55 55-55-55c-9.4-9.4-24.6-9.4-33.9 0z"/></svg>',copy:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M384 336l-192 0c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l133.5 0c4.2 0 8.3 1.7 11.3 4.7l58.5 58.5c3 3 4.7 7.1 4.7 11.3L400 320c0 8.8-7.2 16-16 16zM192 384l192 0c35.3 0 64-28.7 64-64l0-197.5c0-17-6.7-33.3-18.7-45.3L370.7 18.7C358.7 6.7 342.5 0 325.5 0L192 0c-35.3 0-64 28.7-64 64l0 256c0 35.3 28.7 64 64 64zM64 128c-35.3 0-64 28.7-64 64L0 448c0 35.3 28.7 64 64 64l192 0c35.3 0 64-28.7 64-64l0-16-48 0 0 16c0 8.8-7.2 16-16 16L64 464c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l16 0 0-48-16 0z"/></svg>',eye:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M288 80C222.8 80 169.2 109.6 128.1 147.7 89.6 183.5 63 226 49.4 256 63 286 89.6 328.5 128.1 364.3 169.2 402.4 222.8 432 288 432s118.8-29.6 159.9-67.7C486.4 328.5 513 286 526.6 256 513 226 486.4 183.5 447.9 147.7 406.8 109.6 353.2 80 288 80zM95.4 112.6C142.5 68.8 207.2 32 288 32s145.5 36.8 192.6 80.6c46.8 43.5 78.1 95.4 93 131.1 3.3 7.9 3.3 16.7 0 24.6-14.9 35.7-46.2 87.7-93 131.1-47.1 43.7-111.8 80.6-192.6 80.6S142.5 443.2 95.4 399.4c-46.8-43.5-78.1-95.4-93-131.1-3.3-7.9-3.3-16.7 0-24.6 14.9-35.7 46.2-87.7 93-131.1zM288 336c44.2 0 80-35.8 80-80 0-29.6-16.1-55.5-40-69.3-1.4 59.7-49.6 107.9-109.3 109.3 13.8 23.9 39.7 40 69.3 40zm-79.6-88.4c2.5 .3 5 .4 7.6 .4 35.3 0 64-28.7 64-64 0-2.6-.2-5.1-.4-7.6-37.4 3.9-67.2 33.7-71.1 71.1zm45.6-115c10.8-3 22.2-4.5 33.9-4.5 8.8 0 17.5 .9 25.8 2.6 .3 .1 .5 .1 .8 .2 57.9 12.2 101.4 63.7 101.4 125.2 0 70.7-57.3 128-128 128-61.6 0-113-43.5-125.2-101.4-1.8-8.6-2.8-17.5-2.8-26.6 0-11 1.4-21.8 4-32 .2-.7 .3-1.3 .5-1.9 11.9-43.4 46.1-77.6 89.5-89.5z"/></svg>',"eye-slash":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M41-24.9c-9.4-9.4-24.6-9.4-33.9 0S-2.3-.3 7 9.1l528 528c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-96.4-96.4c2.7-2.4 5.4-4.8 8-7.2 46.8-43.5 78.1-95.4 93-131.1 3.3-7.9 3.3-16.7 0-24.6-14.9-35.7-46.2-87.7-93-131.1-47.1-43.7-111.8-80.6-192.6-80.6-56.8 0-105.6 18.2-146 44.2L41-24.9zM176.9 111.1c32.1-18.9 69.2-31.1 111.1-31.1 65.2 0 118.8 29.6 159.9 67.7 38.5 35.7 65.1 78.3 78.6 108.3-13.6 30-40.2 72.5-78.6 108.3-3.1 2.8-6.2 5.6-9.4 8.4L393.8 328c14-20.5 22.2-45.3 22.2-72 0-70.7-57.3-128-128-128-26.7 0-51.5 8.2-72 22.2l-39.1-39.1zm182 182l-108-108c11.1-5.8 23.7-9.1 37.1-9.1 44.2 0 80 35.8 80 80 0 13.4-3.3 26-9.1 37.1zM103.4 173.2l-34-34c-32.6 36.8-55 75.8-66.9 104.5-3.3 7.9-3.3 16.7 0 24.6 14.9 35.7 46.2 87.7 93 131.1 47.1 43.7 111.8 80.6 192.6 80.6 37.3 0 71.2-7.9 101.5-20.6L352.2 422c-20 6.4-41.4 10-64.2 10-65.2 0-118.8-29.6-159.9-67.7-38.5-35.7-65.1-78.3-78.6-108.3 10.4-23.1 28.6-53.6 54-82.8z"/></svg>',star:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M288.1-32c9 0 17.3 5.1 21.4 13.1L383 125.3 542.9 150.7c8.9 1.4 16.3 7.7 19.1 16.3s.5 18-5.8 24.4L441.7 305.9 467 465.8c1.4 8.9-2.3 17.9-9.6 23.2s-17 6.1-25 2L288.1 417.6 143.8 491c-8 4.1-17.7 3.3-25-2s-11-14.2-9.6-23.2L134.4 305.9 20 191.4c-6.4-6.4-8.6-15.8-5.8-24.4s10.1-14.9 19.1-16.3l159.9-25.4 73.6-144.2c4.1-8 12.4-13.1 21.4-13.1zm0 76.8L230.3 158c-3.5 6.8-10 11.6-17.6 12.8l-125.5 20 89.8 89.9c5.4 5.4 7.9 13.1 6.7 20.7l-19.8 125.5 113.3-57.6c6.8-3.5 14.9-3.5 21.8 0l113.3 57.6-19.8-125.5c-1.2-7.6 1.3-15.3 6.7-20.7l89.8-89.9-125.5-20c-7.6-1.2-14.1-6-17.6-12.8L288.1 44.8z"/></svg>'}},ML={name:"system",resolver:(e,t="classic",r="solid")=>{let i=xd[r][e]??xd.regular[e]??xd.regular["circle-question"];return i?RL(i):""}},DL=ML;var $L="classic",Hl=[NL,DL],ql=[];function VL(e){ql.push(e)}function BL(e){ql=ql.filter(t=>t!==e)}function Sd(e){return Hl.find(t=>t.name===e)}function UL(e,t){zL(e),Hl.push({name:e,resolver:t.resolver,mutator:t.mutator,spriteSheet:t.spriteSheet}),ql.forEach(r=>{r.library===e&&r.setIcon()})}function zL(e){Hl=Hl.filter(t=>t.name!==e)}function HL(){return $L}var qL=Object.defineProperty,jL=Object.getOwnPropertyDescriptor,M0=e=>{throw TypeError(e)},$=(e,t,r,n)=>{for(var i=n>1?void 0:n?jL(t,r):t,s=e.length-1,o;s>=0;s--)(o=e[s])&&(i=(n?o(t,r,i):o(i))||i);return n&&i&&qL(t,r,i),i},D0=(e,t,r)=>t.has(e)||M0("Cannot "+r),WL=(e,t,r)=>(D0(e,t,"read from private field"),t.get(e)),KL=(e,t,r)=>t.has(e)?M0("Cannot add the same private member more than once"):t instanceof WeakSet?t.add(e):t.set(e,r),GL=(e,t,r,n)=>(D0(e,t,"write to private field"),t.set(e,r),r);const YL={alert:"triangle-exclamation",asc:"arrow-down-short-wide",asset:"image",assets:"image",circleuarr:"circle-arrow-up",collapse:"down-left-and-up-right-to-center",condition:"diamond",darr:"arrow-down",date:"calendar",desc:"arrow-down-wide-short",disabled:"circle-dashed",done:"circle-check",downangle:"angle-down",draft:"scribble",edit:"pencil",enabled:"circle",expand:"up-right-and-down-left-from-center",external:"arrow-up-right-from-square",field:"pen-to-square",help:"circle-question",home:"house",info:"circle-info",insecure:"unlock",larr:"arrow-left",layout:"table-layout",leftangle:"angle-left",listrtl:"list-flip",location:"location-dot",mail:"envelope",menu:"bars",move:"grip-dots",newstamp:"certificate",paperplane:"paper-plane",plugin:"plug",rarr:"arrow-right",refresh:"arrows-rotate",remove:"xmark",rightangle:"angle-right",rotate:"rotate-left",routes:"signs-post",search:"magnifying-glass",secure:"lock",settings:"gear",shareleft:"share-flip",shuteye:"eye-slash","sidebar-left":"sidebar","sidebar-right":"sidebar-flip","sidebar-start":"sidebar","sidebar-end":"sidebar-flip",structure:"list-tree",structurertl:"list-tree-flip",template:"file-code",time:"clock",tool:"wrench",uarr:"arrow-up",upangle:"angle-up",view:"eye",wand:"wand-magic-sparkles"};function XL(e,t="classic",r="regular"){let n="solid",i=r,s=e.endsWith(".svg")?e.split(".svg")[0]:e;if(e.includes("/")){const[o,...a]=e.split("/");i=o??i,s=a.join("/")}return i==="thin"?n="thin":i==="light"?n="light":i==="regular"?n="regular":i==="solid"&&(n="solid"),t==="brands"&&(n="brands"),i==="custom-icons"&&(n="custom-icons"),s=YL[s]??s,`/vendor/craft/icons/${n}/${s}.svg`}function JL(){UL("default",{resolver:(e,t="classic",r="solid")=>XL(e,t,r),mutator:e=>e.setAttribute("fill","currentColor")})}var $0=class extends HTMLElement{constructor(...e){super(...e),this.cookieName=null,this.state="collapsed",this.expanded=!1,this.handleOpen=()=>{this.trigger?.setAttribute("aria-expanded","true"),this.expanded=!0,this.dispatchEvent(new CustomEvent("open")),this.target&&(this.target.dataset.state="expanded"),this.cookieName&&window.Craft?.setCookie(this.cookieName,"expanded")},this.handleClose=()=>{this.trigger?.setAttribute("aria-expanded","false"),this.expanded=!1,this.dispatchEvent(new CustomEvent("close")),this.target&&(this.target.dataset.state="collapsed"),this.cookieName&&window.Craft?.setCookie(this.cookieName,"collapsed")}}get trigger(){return this.querySelector('button[type="button"]')}get target(){if(!this.trigger)return console.warn("No trigger found for disclosure."),null;const e=this.trigger.getAttribute("aria-controls");return e?document.getElementById(e):(console.warn("No target selector found for disclosure."),null)}connectedCallback(){if(!this.trigger){console.error("craft-disclosure elements must include a button",this);return}if(!this.target){console.error(`No target with id ${this.trigger.getAttribute("aria-controls")} found for disclosure. `,this.trigger);return}this.cookieName=this.getAttribute("cookie-name"),this.state=this.getAttribute("state")??"expanded",this.trigger.setAttribute("aria-expanded",this.state==="expanded"?"true":"false"),this.trigger.addEventListener("click",this.toggle.bind(this)),this.state==="expanded"?this.open():this.close()}disconnectedCallback(){this.open(),this.trigger?.removeEventListener("click",this.toggle.bind(this))}attributeChangedCallback(e,t,r){e==="state"&&(r==="expanded"?this.handleOpen():this.handleClose())}toggle(){this.expanded?this.close():this.open()}open(){this.setAttribute("state","expanded")}close(){this.setAttribute("state","collapsed")}};$0.observedAttributes=["state"];customElements.get("craft-disclosure")||customElements.define("craft-disclosure",$0);function Mc(e){return(t,r)=>{const{slot:n,selector:i}=e??{},s="slot"+(n?`[name=${n}]`:":not([name])");return GE(t,r,{get(){const o=this.renderRoot?.querySelector(s),a=o?.assignedElements(e)??[];return i===void 0?a:a.filter((l=>l.matches(i)))}})}}var QL=te`
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
`,Dc=class extends Ne{constructor(...e){super(...e),this.visible=!1,this.wrapper=null}show(){this.visible=!0,this.dispatchEvent(new CustomEvent("show"))}hide(){this.visible=!1,this.dispatchEvent(new CustomEvent("hide"))}focus(){this.wrapper?.focus()}render(){return W`
      <div
        tabindex="-1"
        class="${er({wrapper:!0,hidden:!this.visible})}"
      >
        <div class="spinner"></div>
        <span class="message visually-hidden"><slot /></span>
      </div>
    `}};Dc.styles=[QL];se([V({reflect:!0})],Dc.prototype,"visible",void 0);se([ze(".wrapper")],Dc.prototype,"wrapper",void 0);customElements.get("craft-spinner")||customElements.define("craft-spinner",Dc);var ZL=class extends Event{constructor(){super("wa-reposition",{bubbles:!0,cancelable:!1,composed:!0})}};var eR=`:host {
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
`,rl,Ht=class extends Ne{constructor(){super(),KL(this,rl,!1),this.initialReflectedProperties=new Map,this.didSSR=!!this.shadowRoot,this.customStates={set:(t,r)=>{if(this.internals?.states)try{r?this.internals.states.add(t):this.internals.states.delete(t)}catch(n){if(String(n).includes("must start with '--'"))console.error("Your browser implements an outdated version of CustomStateSet. Consider using a polyfill");else throw n}},has:t=>{if(!this.internals?.states)return!1;try{return this.internals.states.has(t)}catch{return!1}}};try{this.internals=this.attachInternals()}catch{console.error("Element internals are not supported in your browser. Consider using a polyfill")}this.customStates.set("wa-defined",!0);let e=this.constructor;for(let[t,r]of e.elementProperties)r.default==="inherit"&&r.initial!==void 0&&typeof t=="string"&&this.customStates.set(`initial-${t}-${r.initial}`,!0)}static get styles(){const e=Array.isArray(this.css)?this.css:this.css?[this.css]:[];return[eR,...e].map(t=>typeof t=="string"?WE(t):t)}attributeChangedCallback(e,t,r){WL(this,rl)||(this.constructor.elementProperties.forEach((n,i)=>{n.reflect&&this[i]!=null&&this.initialReflectedProperties.set(i,this[i])}),GL(this,rl,!0)),super.attributeChangedCallback(e,t,r)}willUpdate(e){super.willUpdate(e),this.initialReflectedProperties.forEach((t,r)=>{e.has(r)&&this[r]==null&&(this[r]=t)})}firstUpdated(e){super.firstUpdated(e),this.didSSR&&this.shadowRoot?.querySelectorAll("slot").forEach(t=>{t.dispatchEvent(new Event("slotchange",{bubbles:!0,composed:!1,cancelable:!1}))})}update(e){try{super.update(e)}catch(t){if(this.didSSR&&!this.hasUpdated){const r=new Event("lit-hydration-error",{bubbles:!0,composed:!0,cancelable:!1});r.error=t,this.dispatchEvent(r)}throw t}}relayNativeEvent(e,t){e.stopImmediatePropagation(),this.dispatchEvent(new e.constructor(e.type,{...e,...t}))}};rl=new WeakMap;$([V()],Ht.prototype,"dir",2);$([V()],Ht.prototype,"lang",2);$([V({type:Boolean,reflect:!0,attribute:"did-ssr"})],Ht.prototype,"didSSR",2);const ti=Math.min,Xt=Math.max,jl=Math.round,Va=Math.floor,jr=e=>({x:e,y:e}),tR={left:"right",right:"left",bottom:"top",top:"bottom"},rR={start:"end",end:"start"};function Bh(e,t,r){return Xt(e,ti(t,r))}function Os(e,t){return typeof e=="function"?e(t):e}function ri(e){return e.split("-")[0]}function Ns(e){return e.split("-")[1]}function V0(e){return e==="x"?"y":"x"}function Ep(e){return e==="y"?"height":"width"}const nR=new Set(["top","bottom"]);function dn(e){return nR.has(ri(e))?"y":"x"}function xp(e){return V0(dn(e))}function iR(e,t,r){r===void 0&&(r=!1);const n=Ns(e),i=xp(e),s=Ep(i);let o=i==="x"?n===(r?"end":"start")?"right":"left":n==="start"?"bottom":"top";return t.reference[s]>t.floating[s]&&(o=Wl(o)),[o,Wl(o)]}function sR(e){const t=Wl(e);return[Uh(e),t,Uh(t)]}function Uh(e){return e.replace(/start|end/g,t=>rR[t])}const $b=["left","right"],Vb=["right","left"],oR=["top","bottom"],aR=["bottom","top"];function lR(e,t,r){switch(e){case"top":case"bottom":return r?t?Vb:$b:t?$b:Vb;case"left":case"right":return t?oR:aR;default:return[]}}function cR(e,t,r,n){const i=Ns(e);let s=lR(ri(e),r==="start",n);return i&&(s=s.map(o=>o+"-"+i),t&&(s=s.concat(s.map(Uh)))),s}function Wl(e){return e.replace(/left|right|bottom|top/g,t=>tR[t])}function uR(e){return{top:0,right:0,bottom:0,left:0,...e}}function B0(e){return typeof e!="number"?uR(e):{top:e,right:e,bottom:e,left:e}}function Kl(e){const{x:t,y:r,width:n,height:i}=e;return{width:n,height:i,top:r,left:t,right:t+n,bottom:r+i,x:t,y:r}}function Bb(e,t,r){let{reference:n,floating:i}=e;const s=dn(t),o=xp(t),a=Ep(o),l=ri(t),u=s==="y",c=n.x+n.width/2-i.width/2,d=n.y+n.height/2-i.height/2,p=n[a]/2-i[a]/2;let m;switch(l){case"top":m={x:c,y:n.y-i.height};break;case"bottom":m={x:c,y:n.y+n.height};break;case"right":m={x:n.x+n.width,y:d};break;case"left":m={x:n.x-i.width,y:d};break;default:m={x:n.x,y:n.y}}switch(Ns(t)){case"start":m[o]-=p*(r&&u?-1:1);break;case"end":m[o]+=p*(r&&u?-1:1);break}return m}const dR=async(e,t,r)=>{const{placement:n="bottom",strategy:i="absolute",middleware:s=[],platform:o}=r,a=s.filter(Boolean),l=await(o.isRTL==null?void 0:o.isRTL(t));let u=await o.getElementRects({reference:e,floating:t,strategy:i}),{x:c,y:d}=Bb(u,n,l),p=n,m={},h=0;for(let f=0;f<a.length;f++){const{name:b,fn:y}=a[f],{x:w,y:v,data:_,reset:x}=await y({x:c,y:d,initialPlacement:n,placement:p,strategy:i,middlewareData:m,rects:u,platform:o,elements:{reference:e,floating:t}});c=w??c,d=v??d,m={...m,[b]:{...m[b],..._}},x&&h<=50&&(h++,typeof x=="object"&&(x.placement&&(p=x.placement),x.rects&&(u=x.rects===!0?await o.getElementRects({reference:e,floating:t,strategy:i}):x.rects),{x:c,y:d}=Bb(u,p,l)),f=-1)}return{x:c,y:d,placement:p,strategy:i,middlewareData:m}};async function Sp(e,t){var r;t===void 0&&(t={});const{x:n,y:i,platform:s,rects:o,elements:a,strategy:l}=e,{boundary:u="clippingAncestors",rootBoundary:c="viewport",elementContext:d="floating",altBoundary:p=!1,padding:m=0}=Os(t,e),h=B0(m),b=a[p?d==="floating"?"reference":"floating":d],y=Kl(await s.getClippingRect({element:(r=await(s.isElement==null?void 0:s.isElement(b)))==null||r?b:b.contextElement||await(s.getDocumentElement==null?void 0:s.getDocumentElement(a.floating)),boundary:u,rootBoundary:c,strategy:l})),w=d==="floating"?{x:n,y:i,width:o.floating.width,height:o.floating.height}:o.reference,v=await(s.getOffsetParent==null?void 0:s.getOffsetParent(a.floating)),_=await(s.isElement==null?void 0:s.isElement(v))?await(s.getScale==null?void 0:s.getScale(v))||{x:1,y:1}:{x:1,y:1},x=Kl(s.convertOffsetParentRelativeRectToViewportRelativeRect?await s.convertOffsetParentRelativeRectToViewportRelativeRect({elements:a,rect:w,offsetParent:v,strategy:l}):w);return{top:(y.top-x.top+h.top)/_.y,bottom:(x.bottom-y.bottom+h.bottom)/_.y,left:(y.left-x.left+h.left)/_.x,right:(x.right-y.right+h.right)/_.x}}const hR=e=>({name:"arrow",options:e,async fn(t){const{x:r,y:n,placement:i,rects:s,platform:o,elements:a,middlewareData:l}=t,{element:u,padding:c=0}=Os(e,t)||{};if(u==null)return{};const d=B0(c),p={x:r,y:n},m=xp(i),h=Ep(m),f=await o.getDimensions(u),b=m==="y",y=b?"top":"left",w=b?"bottom":"right",v=b?"clientHeight":"clientWidth",_=s.reference[h]+s.reference[m]-p[m]-s.floating[h],x=p[m]-s.reference[m],k=await(o.getOffsetParent==null?void 0:o.getOffsetParent(u));let S=k?k[v]:0;(!S||!await(o.isElement==null?void 0:o.isElement(k)))&&(S=a.floating[v]||s.floating[h]);const N=_/2-x/2,A=S/2-f[h]/2-1,T=ti(d[y],A),I=ti(d[w],A),C=T,g=S-f[h]-I,L=S/2-f[h]/2+N,B=Bh(C,L,g),M=!l.arrow&&Ns(i)!=null&&L!==B&&s.reference[h]/2-(L<C?T:I)-f[h]/2<0,z=M?L<C?L-C:L-g:0;return{[m]:p[m]+z,data:{[m]:B,centerOffset:L-B-z,...M&&{alignmentOffset:z}},reset:M}}}),fR=function(e){return e===void 0&&(e={}),{name:"flip",options:e,async fn(t){var r,n;const{placement:i,middlewareData:s,rects:o,initialPlacement:a,platform:l,elements:u}=t,{mainAxis:c=!0,crossAxis:d=!0,fallbackPlacements:p,fallbackStrategy:m="bestFit",fallbackAxisSideDirection:h="none",flipAlignment:f=!0,...b}=Os(e,t);if((r=s.arrow)!=null&&r.alignmentOffset)return{};const y=ri(i),w=dn(a),v=ri(a)===a,_=await(l.isRTL==null?void 0:l.isRTL(u.floating)),x=p||(v||!f?[Wl(a)]:sR(a)),k=h!=="none";!p&&k&&x.push(...cR(a,f,h,_));const S=[a,...x],N=await Sp(t,b),A=[];let T=((n=s.flip)==null?void 0:n.overflows)||[];if(c&&A.push(N[y]),d){const L=iR(i,o,_);A.push(N[L[0]],N[L[1]])}if(T=[...T,{placement:i,overflows:A}],!A.every(L=>L<=0)){var I,C;const L=(((I=s.flip)==null?void 0:I.index)||0)+1,B=S[L];if(B&&(!(d==="alignment"?w!==dn(B):!1)||T.every(P=>dn(P.placement)===w?P.overflows[0]>0:!0)))return{data:{index:L,overflows:T},reset:{placement:B}};let M=(C=T.filter(z=>z.overflows[0]<=0).sort((z,P)=>z.overflows[1]-P.overflows[1])[0])==null?void 0:C.placement;if(!M)switch(m){case"bestFit":{var g;const z=(g=T.filter(P=>{if(k){const Z=dn(P.placement);return Z===w||Z==="y"}return!0}).map(P=>[P.placement,P.overflows.filter(Z=>Z>0).reduce((Z,be)=>Z+be,0)]).sort((P,Z)=>P[1]-Z[1])[0])==null?void 0:g[0];z&&(M=z);break}case"initialPlacement":M=a;break}if(i!==M)return{reset:{placement:M}}}return{}}}},pR=new Set(["left","top"]);async function mR(e,t){const{placement:r,platform:n,elements:i}=e,s=await(n.isRTL==null?void 0:n.isRTL(i.floating)),o=ri(r),a=Ns(r),l=dn(r)==="y",u=pR.has(o)?-1:1,c=s&&l?-1:1,d=Os(t,e);let{mainAxis:p,crossAxis:m,alignmentAxis:h}=typeof d=="number"?{mainAxis:d,crossAxis:0,alignmentAxis:null}:{mainAxis:d.mainAxis||0,crossAxis:d.crossAxis||0,alignmentAxis:d.alignmentAxis};return a&&typeof h=="number"&&(m=a==="end"?h*-1:h),l?{x:m*c,y:p*u}:{x:p*u,y:m*c}}const gR=function(e){return e===void 0&&(e=0),{name:"offset",options:e,async fn(t){var r,n;const{x:i,y:s,placement:o,middlewareData:a}=t,l=await mR(t,e);return o===((r=a.offset)==null?void 0:r.placement)&&(n=a.arrow)!=null&&n.alignmentOffset?{}:{x:i+l.x,y:s+l.y,data:{...l,placement:o}}}}},bR=function(e){return e===void 0&&(e={}),{name:"shift",options:e,async fn(t){const{x:r,y:n,placement:i}=t,{mainAxis:s=!0,crossAxis:o=!1,limiter:a={fn:b=>{let{x:y,y:w}=b;return{x:y,y:w}}},...l}=Os(e,t),u={x:r,y:n},c=await Sp(t,l),d=dn(ri(i)),p=V0(d);let m=u[p],h=u[d];if(s){const b=p==="y"?"top":"left",y=p==="y"?"bottom":"right",w=m+c[b],v=m-c[y];m=Bh(w,m,v)}if(o){const b=d==="y"?"top":"left",y=d==="y"?"bottom":"right",w=h+c[b],v=h-c[y];h=Bh(w,h,v)}const f=a.fn({...t,[p]:m,[d]:h});return{...f,data:{x:f.x-r,y:f.y-n,enabled:{[p]:s,[d]:o}}}}}},vR=function(e){return e===void 0&&(e={}),{name:"size",options:e,async fn(t){var r,n;const{placement:i,rects:s,platform:o,elements:a}=t,{apply:l=()=>{},...u}=Os(e,t),c=await Sp(t,u),d=ri(i),p=Ns(i),m=dn(i)==="y",{width:h,height:f}=s.floating;let b,y;d==="top"||d==="bottom"?(b=d,y=p===(await(o.isRTL==null?void 0:o.isRTL(a.floating))?"start":"end")?"left":"right"):(y=d,b=p==="end"?"top":"bottom");const w=f-c.top-c.bottom,v=h-c.left-c.right,_=ti(f-c[b],w),x=ti(h-c[y],v),k=!t.middlewareData.shift;let S=_,N=x;if((r=t.middlewareData.shift)!=null&&r.enabled.x&&(N=v),(n=t.middlewareData.shift)!=null&&n.enabled.y&&(S=w),k&&!p){const T=Xt(c.left,0),I=Xt(c.right,0),C=Xt(c.top,0),g=Xt(c.bottom,0);m?N=h-2*(T!==0||I!==0?T+I:Xt(c.left,c.right)):S=f-2*(C!==0||g!==0?C+g:Xt(c.top,c.bottom))}await l({...t,availableWidth:N,availableHeight:S});const A=await o.getDimensions(a.floating);return h!==A.width||f!==A.height?{reset:{rects:!0}}:{}}}};function $c(){return typeof window<"u"}function Ps(e){return U0(e)?(e.nodeName||"").toLowerCase():"#document"}function Zt(e){var t;return(e==null||(t=e.ownerDocument)==null?void 0:t.defaultView)||window}function Jr(e){var t;return(t=(U0(e)?e.ownerDocument:e.document)||window.document)==null?void 0:t.documentElement}function U0(e){return $c()?e instanceof Node||e instanceof Zt(e).Node:!1}function Or(e){return $c()?e instanceof Element||e instanceof Zt(e).Element:!1}function Yr(e){return $c()?e instanceof HTMLElement||e instanceof Zt(e).HTMLElement:!1}function Ub(e){return!$c()||typeof ShadowRoot>"u"?!1:e instanceof ShadowRoot||e instanceof Zt(e).ShadowRoot}const yR=new Set(["inline","contents"]);function sa(e){const{overflow:t,overflowX:r,overflowY:n,display:i}=Nr(e);return/auto|scroll|overlay|hidden|clip/.test(t+n+r)&&!yR.has(i)}const _R=new Set(["table","td","th"]);function wR(e){return _R.has(Ps(e))}const ER=[":popover-open",":modal"];function Vc(e){return ER.some(t=>{try{return e.matches(t)}catch{return!1}})}const xR=["transform","translate","scale","rotate","perspective"],SR=["transform","translate","scale","rotate","perspective","filter"],CR=["paint","layout","strict","content"];function Bc(e){const t=Cp(),r=Or(e)?Nr(e):e;return xR.some(n=>r[n]?r[n]!=="none":!1)||(r.containerType?r.containerType!=="normal":!1)||!t&&(r.backdropFilter?r.backdropFilter!=="none":!1)||!t&&(r.filter?r.filter!=="none":!1)||SR.some(n=>(r.willChange||"").includes(n))||CR.some(n=>(r.contain||"").includes(n))}function AR(e){let t=ni(e);for(;Yr(t)&&!bs(t);){if(Bc(t))return t;if(Vc(t))return null;t=ni(t)}return null}function Cp(){return typeof CSS>"u"||!CSS.supports?!1:CSS.supports("-webkit-backdrop-filter","none")}const TR=new Set(["html","body","#document"]);function bs(e){return TR.has(Ps(e))}function Nr(e){return Zt(e).getComputedStyle(e)}function Uc(e){return Or(e)?{scrollLeft:e.scrollLeft,scrollTop:e.scrollTop}:{scrollLeft:e.scrollX,scrollTop:e.scrollY}}function ni(e){if(Ps(e)==="html")return e;const t=e.assignedSlot||e.parentNode||Ub(e)&&e.host||Jr(e);return Ub(t)?t.host:t}function z0(e){const t=ni(e);return bs(t)?e.ownerDocument?e.ownerDocument.body:e.body:Yr(t)&&sa(t)?t:z0(t)}function vs(e,t,r){var n;t===void 0&&(t=[]),r===void 0&&(r=!0);const i=z0(e),s=i===((n=e.ownerDocument)==null?void 0:n.body),o=Zt(i);if(s){const a=zh(o);return t.concat(o,o.visualViewport||[],sa(i)?i:[],a&&r?vs(a):[])}return t.concat(i,vs(i,[],r))}function zh(e){return e.parent&&Object.getPrototypeOf(e.parent)?e.frameElement:null}function H0(e){const t=Nr(e);let r=parseFloat(t.width)||0,n=parseFloat(t.height)||0;const i=Yr(e),s=i?e.offsetWidth:r,o=i?e.offsetHeight:n,a=jl(r)!==s||jl(n)!==o;return a&&(r=s,n=o),{width:r,height:n,$:a}}function Ap(e){return Or(e)?e:e.contextElement}function ns(e){const t=Ap(e);if(!Yr(t))return jr(1);const r=t.getBoundingClientRect(),{width:n,height:i,$:s}=H0(t);let o=(s?jl(r.width):r.width)/n,a=(s?jl(r.height):r.height)/i;return(!o||!Number.isFinite(o))&&(o=1),(!a||!Number.isFinite(a))&&(a=1),{x:o,y:a}}const kR=jr(0);function q0(e){const t=Zt(e);return!Cp()||!t.visualViewport?kR:{x:t.visualViewport.offsetLeft,y:t.visualViewport.offsetTop}}function OR(e,t,r){return t===void 0&&(t=!1),!r||t&&r!==Zt(e)?!1:t}function Fi(e,t,r,n){t===void 0&&(t=!1),r===void 0&&(r=!1);const i=e.getBoundingClientRect(),s=Ap(e);let o=jr(1);t&&(n?Or(n)&&(o=ns(n)):o=ns(e));const a=OR(s,r,n)?q0(s):jr(0);let l=(i.left+a.x)/o.x,u=(i.top+a.y)/o.y,c=i.width/o.x,d=i.height/o.y;if(s){const p=Zt(s),m=n&&Or(n)?Zt(n):n;let h=p,f=zh(h);for(;f&&n&&m!==h;){const b=ns(f),y=f.getBoundingClientRect(),w=Nr(f),v=y.left+(f.clientLeft+parseFloat(w.paddingLeft))*b.x,_=y.top+(f.clientTop+parseFloat(w.paddingTop))*b.y;l*=b.x,u*=b.y,c*=b.x,d*=b.y,l+=v,u+=_,h=Zt(f),f=zh(h)}}return Kl({width:c,height:d,x:l,y:u})}function zc(e,t){const r=Uc(e).scrollLeft;return t?t.left+r:Fi(Jr(e)).left+r}function j0(e,t){const r=e.getBoundingClientRect(),n=r.left+t.scrollLeft-zc(e,r),i=r.top+t.scrollTop;return{x:n,y:i}}function NR(e){let{elements:t,rect:r,offsetParent:n,strategy:i}=e;const s=i==="fixed",o=Jr(n),a=t?Vc(t.floating):!1;if(n===o||a&&s)return r;let l={scrollLeft:0,scrollTop:0},u=jr(1);const c=jr(0),d=Yr(n);if((d||!d&&!s)&&((Ps(n)!=="body"||sa(o))&&(l=Uc(n)),Yr(n))){const m=Fi(n);u=ns(n),c.x=m.x+n.clientLeft,c.y=m.y+n.clientTop}const p=o&&!d&&!s?j0(o,l):jr(0);return{width:r.width*u.x,height:r.height*u.y,x:r.x*u.x-l.scrollLeft*u.x+c.x+p.x,y:r.y*u.y-l.scrollTop*u.y+c.y+p.y}}function PR(e){return Array.from(e.getClientRects())}function IR(e){const t=Jr(e),r=Uc(e),n=e.ownerDocument.body,i=Xt(t.scrollWidth,t.clientWidth,n.scrollWidth,n.clientWidth),s=Xt(t.scrollHeight,t.clientHeight,n.scrollHeight,n.clientHeight);let o=-r.scrollLeft+zc(e);const a=-r.scrollTop;return Nr(n).direction==="rtl"&&(o+=Xt(t.clientWidth,n.clientWidth)-i),{width:i,height:s,x:o,y:a}}const zb=25;function FR(e,t){const r=Zt(e),n=Jr(e),i=r.visualViewport;let s=n.clientWidth,o=n.clientHeight,a=0,l=0;if(i){s=i.width,o=i.height;const c=Cp();(!c||c&&t==="fixed")&&(a=i.offsetLeft,l=i.offsetTop)}const u=zc(n);if(u<=0){const c=n.ownerDocument,d=c.body,p=getComputedStyle(d),m=c.compatMode==="CSS1Compat"&&parseFloat(p.marginLeft)+parseFloat(p.marginRight)||0,h=Math.abs(n.clientWidth-d.clientWidth-m);h<=zb&&(s-=h)}else u<=zb&&(s+=u);return{width:s,height:o,x:a,y:l}}const LR=new Set(["absolute","fixed"]);function RR(e,t){const r=Fi(e,!0,t==="fixed"),n=r.top+e.clientTop,i=r.left+e.clientLeft,s=Yr(e)?ns(e):jr(1),o=e.clientWidth*s.x,a=e.clientHeight*s.y,l=i*s.x,u=n*s.y;return{width:o,height:a,x:l,y:u}}function Hb(e,t,r){let n;if(t==="viewport")n=FR(e,r);else if(t==="document")n=IR(Jr(e));else if(Or(t))n=RR(t,r);else{const i=q0(e);n={x:t.x-i.x,y:t.y-i.y,width:t.width,height:t.height}}return Kl(n)}function W0(e,t){const r=ni(e);return r===t||!Or(r)||bs(r)?!1:Nr(r).position==="fixed"||W0(r,t)}function MR(e,t){const r=t.get(e);if(r)return r;let n=vs(e,[],!1).filter(a=>Or(a)&&Ps(a)!=="body"),i=null;const s=Nr(e).position==="fixed";let o=s?ni(e):e;for(;Or(o)&&!bs(o);){const a=Nr(o),l=Bc(o);!l&&a.position==="fixed"&&(i=null),(s?!l&&!i:!l&&a.position==="static"&&!!i&&LR.has(i.position)||sa(o)&&!l&&W0(e,o))?n=n.filter(c=>c!==o):i=a,o=ni(o)}return t.set(e,n),n}function DR(e){let{element:t,boundary:r,rootBoundary:n,strategy:i}=e;const o=[...r==="clippingAncestors"?Vc(t)?[]:MR(t,this._c):[].concat(r),n],a=o[0],l=o.reduce((u,c)=>{const d=Hb(t,c,i);return u.top=Xt(d.top,u.top),u.right=ti(d.right,u.right),u.bottom=ti(d.bottom,u.bottom),u.left=Xt(d.left,u.left),u},Hb(t,a,i));return{width:l.right-l.left,height:l.bottom-l.top,x:l.left,y:l.top}}function $R(e){const{width:t,height:r}=H0(e);return{width:t,height:r}}function VR(e,t,r){const n=Yr(t),i=Jr(t),s=r==="fixed",o=Fi(e,!0,s,t);let a={scrollLeft:0,scrollTop:0};const l=jr(0);function u(){l.x=zc(i)}if(n||!n&&!s)if((Ps(t)!=="body"||sa(i))&&(a=Uc(t)),n){const m=Fi(t,!0,s,t);l.x=m.x+t.clientLeft,l.y=m.y+t.clientTop}else i&&u();s&&!n&&i&&u();const c=i&&!n&&!s?j0(i,a):jr(0),d=o.left+a.scrollLeft-l.x-c.x,p=o.top+a.scrollTop-l.y-c.y;return{x:d,y:p,width:o.width,height:o.height}}function Cd(e){return Nr(e).position==="static"}function qb(e,t){if(!Yr(e)||Nr(e).position==="fixed")return null;if(t)return t(e);let r=e.offsetParent;return Jr(e)===r&&(r=r.ownerDocument.body),r}function K0(e,t){const r=Zt(e);if(Vc(e))return r;if(!Yr(e)){let i=ni(e);for(;i&&!bs(i);){if(Or(i)&&!Cd(i))return i;i=ni(i)}return r}let n=qb(e,t);for(;n&&wR(n)&&Cd(n);)n=qb(n,t);return n&&bs(n)&&Cd(n)&&!Bc(n)?r:n||AR(e)||r}const BR=async function(e){const t=this.getOffsetParent||K0,r=this.getDimensions,n=await r(e.floating);return{reference:VR(e.reference,await t(e.floating),e.strategy),floating:{x:0,y:0,width:n.width,height:n.height}}};function UR(e){return Nr(e).direction==="rtl"}const nl={convertOffsetParentRelativeRectToViewportRelativeRect:NR,getDocumentElement:Jr,getClippingRect:DR,getOffsetParent:K0,getElementRects:BR,getClientRects:PR,getDimensions:$R,getScale:ns,isElement:Or,isRTL:UR};function G0(e,t){return e.x===t.x&&e.y===t.y&&e.width===t.width&&e.height===t.height}function zR(e,t){let r=null,n;const i=Jr(e);function s(){var a;clearTimeout(n),(a=r)==null||a.disconnect(),r=null}function o(a,l){a===void 0&&(a=!1),l===void 0&&(l=1),s();const u=e.getBoundingClientRect(),{left:c,top:d,width:p,height:m}=u;if(a||t(),!p||!m)return;const h=Va(d),f=Va(i.clientWidth-(c+p)),b=Va(i.clientHeight-(d+m)),y=Va(c),v={rootMargin:-h+"px "+-f+"px "+-b+"px "+-y+"px",threshold:Xt(0,ti(1,l))||1};let _=!0;function x(k){const S=k[0].intersectionRatio;if(S!==l){if(!_)return o();S?o(!1,S):n=setTimeout(()=>{o(!1,1e-7)},1e3)}S===1&&!G0(u,e.getBoundingClientRect())&&o(),_=!1}try{r=new IntersectionObserver(x,{...v,root:i.ownerDocument})}catch{r=new IntersectionObserver(x,v)}r.observe(e)}return o(!0),s}function Y0(e,t,r,n){n===void 0&&(n={});const{ancestorScroll:i=!0,ancestorResize:s=!0,elementResize:o=typeof ResizeObserver=="function",layoutShift:a=typeof IntersectionObserver=="function",animationFrame:l=!1}=n,u=Ap(e),c=i||s?[...u?vs(u):[],...vs(t)]:[];c.forEach(y=>{i&&y.addEventListener("scroll",r,{passive:!0}),s&&y.addEventListener("resize",r)});const d=u&&a?zR(u,r):null;let p=-1,m=null;o&&(m=new ResizeObserver(y=>{let[w]=y;w&&w.target===u&&m&&(m.unobserve(t),cancelAnimationFrame(p),p=requestAnimationFrame(()=>{var v;(v=m)==null||v.observe(t)})),r()}),u&&!l&&m.observe(u),m.observe(t));let h,f=l?Fi(e):null;l&&b();function b(){const y=Fi(e);f&&!G0(f,y)&&r(),f=y,h=requestAnimationFrame(b)}return r(),()=>{var y;c.forEach(w=>{i&&w.removeEventListener("scroll",r),s&&w.removeEventListener("resize",r)}),d?.(),(y=m)==null||y.disconnect(),m=null,l&&cancelAnimationFrame(h)}}const X0=gR,J0=bR,Q0=fR,jb=vR,HR=hR,Z0=(e,t,r)=>{const n=new Map,i={platform:nl,...r},s={...i.platform,_c:n};return dR(e,t,{...i,platform:s})};function qR(e){return jR(e)}function Ad(e){return e.assignedSlot?e.assignedSlot:e.parentNode instanceof ShadowRoot?e.parentNode.host:e.parentNode}function jR(e){for(let t=e;t;t=Ad(t))if(t instanceof Element&&getComputedStyle(t).display==="none")return null;for(let t=Ad(e);t;t=Ad(t)){if(!(t instanceof Element))continue;const r=getComputedStyle(t);if(r.display!=="contents"&&(r.position!=="static"||Bc(r)||t.tagName==="BODY"))return t}return null}var WR=`:host {
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
`;function Wb(e){return e!==null&&typeof e=="object"&&"getBoundingClientRect"in e&&("contextElement"in e?e instanceof Element:!0)}var Ba=globalThis?.HTMLElement?.prototype.hasOwnProperty("popover"),ke=class extends Ht{constructor(){super(...arguments),this.localize=new ks(this),this.active=!1,this.placement="top",this.boundary="viewport",this.distance=0,this.skidding=0,this.arrow=!1,this.arrowPlacement="anchor",this.arrowPadding=10,this.flip=!1,this.flipFallbackPlacements="",this.flipFallbackStrategy="best-fit",this.flipPadding=0,this.shift=!1,this.shiftPadding=0,this.autoSizePadding=0,this.hoverBridge=!1,this.updateHoverBridge=()=>{if(this.hoverBridge&&this.anchorEl){const e=this.anchorEl.getBoundingClientRect(),t=this.popup.getBoundingClientRect(),r=this.placement.includes("top")||this.placement.includes("bottom");let n=0,i=0,s=0,o=0,a=0,l=0,u=0,c=0;r?e.top<t.top?(n=e.left,i=e.bottom,s=e.right,o=e.bottom,a=t.left,l=t.top,u=t.right,c=t.top):(n=t.left,i=t.bottom,s=t.right,o=t.bottom,a=e.left,l=e.top,u=e.right,c=e.top):e.left<t.left?(n=e.right,i=e.top,s=t.left,o=t.top,a=e.right,l=e.bottom,u=t.left,c=t.bottom):(n=t.right,i=t.top,s=e.left,o=e.top,a=t.right,l=t.bottom,u=e.left,c=e.bottom),this.style.setProperty("--hover-bridge-top-left-x",`${n}px`),this.style.setProperty("--hover-bridge-top-left-y",`${i}px`),this.style.setProperty("--hover-bridge-top-right-x",`${s}px`),this.style.setProperty("--hover-bridge-top-right-y",`${o}px`),this.style.setProperty("--hover-bridge-bottom-left-x",`${a}px`),this.style.setProperty("--hover-bridge-bottom-left-y",`${l}px`),this.style.setProperty("--hover-bridge-bottom-right-x",`${u}px`),this.style.setProperty("--hover-bridge-bottom-right-y",`${c}px`)}}}async connectedCallback(){super.connectedCallback(),await this.updateComplete,this.start()}disconnectedCallback(){super.disconnectedCallback(),this.stop()}async updated(e){super.updated(e),e.has("active")&&(this.active?this.start():this.stop()),e.has("anchor")&&this.handleAnchorChange(),this.active&&(await this.updateComplete,this.reposition())}async handleAnchorChange(){if(await this.stop(),this.anchor&&typeof this.anchor=="string"){const e=this.getRootNode();this.anchorEl=e.getElementById(this.anchor)}else this.anchor instanceof Element||Wb(this.anchor)?this.anchorEl=this.anchor:this.anchorEl=this.querySelector('[slot="anchor"]');this.anchorEl instanceof HTMLSlotElement&&(this.anchorEl=this.anchorEl.assignedElements({flatten:!0})[0]),this.anchorEl&&this.start()}start(){!this.anchorEl||!this.active||(this.popup.showPopover?.(),this.cleanup=Y0(this.anchorEl,this.popup,()=>{this.reposition()}))}async stop(){return new Promise(e=>{this.popup.hidePopover?.(),this.cleanup?(this.cleanup(),this.cleanup=void 0,this.removeAttribute("data-current-placement"),this.style.removeProperty("--auto-size-available-width"),this.style.removeProperty("--auto-size-available-height"),requestAnimationFrame(()=>e())):e()})}reposition(){if(!this.active||!this.anchorEl)return;const e=[X0({mainAxis:this.distance,crossAxis:this.skidding})];this.sync?e.push(jb({apply:({rects:n})=>{const i=this.sync==="width"||this.sync==="both",s=this.sync==="height"||this.sync==="both";this.popup.style.width=i?`${n.reference.width}px`:"",this.popup.style.height=s?`${n.reference.height}px`:""}})):(this.popup.style.width="",this.popup.style.height="");let t;Ba&&!Wb(this.anchor)&&this.boundary==="scroll"&&(t=vs(this.anchorEl).filter(n=>n instanceof Element)),this.flip&&e.push(Q0({boundary:this.flipBoundary||t,fallbackPlacements:this.flipFallbackPlacements,fallbackStrategy:this.flipFallbackStrategy==="best-fit"?"bestFit":"initialPlacement",padding:this.flipPadding})),this.shift&&e.push(J0({boundary:this.shiftBoundary||t,padding:this.shiftPadding})),this.autoSize?e.push(jb({boundary:this.autoSizeBoundary||t,padding:this.autoSizePadding,apply:({availableWidth:n,availableHeight:i})=>{this.autoSize==="vertical"||this.autoSize==="both"?this.style.setProperty("--auto-size-available-height",`${i}px`):this.style.removeProperty("--auto-size-available-height"),this.autoSize==="horizontal"||this.autoSize==="both"?this.style.setProperty("--auto-size-available-width",`${n}px`):this.style.removeProperty("--auto-size-available-width")}})):(this.style.removeProperty("--auto-size-available-width"),this.style.removeProperty("--auto-size-available-height")),this.arrow&&e.push(HR({element:this.arrowEl,padding:this.arrowPadding}));const r=Ba?n=>nl.getOffsetParent(n,qR):nl.getOffsetParent;Z0(this.anchorEl,this.popup,{placement:this.placement,middleware:e,strategy:Ba?"absolute":"fixed",platform:{...nl,getOffsetParent:r}}).then(({x:n,y:i,middlewareData:s,placement:o})=>{const a=this.localize.dir()==="rtl",l={top:"bottom",right:"left",bottom:"top",left:"right"}[o.split("-")[0]];if(this.setAttribute("data-current-placement",o),Object.assign(this.popup.style,{left:`${n}px`,top:`${i}px`}),this.arrow){const u=s.arrow.x,c=s.arrow.y;let d="",p="",m="",h="";if(this.arrowPlacement==="start"){const f=typeof u=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"";d=typeof c=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"",p=a?f:"",h=a?"":f}else if(this.arrowPlacement==="end"){const f=typeof u=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"";p=a?"":f,h=a?f:"",m=typeof c=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:""}else this.arrowPlacement==="center"?(h=typeof u=="number"?"calc(50% - var(--arrow-size-diagonal))":"",d=typeof c=="number"?"calc(50% - var(--arrow-size-diagonal))":""):(h=typeof u=="number"?`${u}px`:"",d=typeof c=="number"?`${c}px`:"");Object.assign(this.arrowEl.style,{top:d,right:p,bottom:m,left:h,[l]:"calc(var(--arrow-size-diagonal) * -1)"})}}),requestAnimationFrame(()=>this.updateHoverBridge()),this.dispatchEvent(new ZL)}render(){return W`
      <slot name="anchor" @slotchange=${this.handleAnchorChange}></slot>

      <span
        part="hover-bridge"
        class=${er({"popup-hover-bridge":!0,"popup-hover-bridge-visible":this.hoverBridge&&this.active})}
      ></span>

      <div
        popover="manual"
        part="popup"
        class=${er({popup:!0,"popup-active":this.active,"popup-fixed":!Ba,"popup-has-arrow":this.arrow})}
      >
        <slot></slot>
        ${this.arrow?W`<div part="arrow" class="arrow" role="presentation"></div>`:""}
      </div>
    `}};ke.css=WR;$([ze(".popup")],ke.prototype,"popup",2);$([ze(".arrow")],ke.prototype,"arrowEl",2);$([V()],ke.prototype,"anchor",2);$([V({type:Boolean,reflect:!0})],ke.prototype,"active",2);$([V({reflect:!0})],ke.prototype,"placement",2);$([V()],ke.prototype,"boundary",2);$([V({type:Number})],ke.prototype,"distance",2);$([V({type:Number})],ke.prototype,"skidding",2);$([V({type:Boolean})],ke.prototype,"arrow",2);$([V({attribute:"arrow-placement"})],ke.prototype,"arrowPlacement",2);$([V({attribute:"arrow-padding",type:Number})],ke.prototype,"arrowPadding",2);$([V({type:Boolean})],ke.prototype,"flip",2);$([V({attribute:"flip-fallback-placements",converter:{fromAttribute:e=>e.split(" ").map(t=>t.trim()).filter(t=>t!==""),toAttribute:e=>e.join(" ")}})],ke.prototype,"flipFallbackPlacements",2);$([V({attribute:"flip-fallback-strategy"})],ke.prototype,"flipFallbackStrategy",2);$([V({type:Object})],ke.prototype,"flipBoundary",2);$([V({attribute:"flip-padding",type:Number})],ke.prototype,"flipPadding",2);$([V({type:Boolean})],ke.prototype,"shift",2);$([V({type:Object})],ke.prototype,"shiftBoundary",2);$([V({attribute:"shift-padding",type:Number})],ke.prototype,"shiftPadding",2);$([V({attribute:"auto-size"})],ke.prototype,"autoSize",2);$([V()],ke.prototype,"sync",2);$([V({type:Object})],ke.prototype,"autoSizeBoundary",2);$([V({attribute:"auto-size-padding",type:Number})],ke.prototype,"autoSizePadding",2);$([V({attribute:"hover-bridge",type:Boolean})],ke.prototype,"hoverBridge",2);ke=$([Fr("wa-popup")],ke);var oa=class extends Event{constructor(){super("wa-after-hide",{bubbles:!0,cancelable:!1,composed:!0})}},aa=class extends Event{constructor(){super("wa-after-show",{bubbles:!0,cancelable:!1,composed:!0})}},la=class extends Event{constructor(e){super("wa-hide",{bubbles:!0,cancelable:!0,composed:!0}),this.detail=e}},ca=class extends Event{constructor(){super("wa-show",{bubbles:!0,cancelable:!0,composed:!0})}};const KR="useandom-26T198340PX75pxJACKVERYMINDBUSHWOLF_GQZbfghjklqvwyzrict";let GR=(e=21)=>{let t="",r=crypto.getRandomValues(new Uint8Array(e|=0));for(;e--;)t+=KR[r[e]&63];return t};function Tp(e=""){return`${e}${GR()}`}function Gl(e,t){return new Promise(r=>{function n(i){i.target===e&&(e.removeEventListener(t,n),r())}e.addEventListener(t,n)})}function Ct(e,t){return new Promise(r=>{const n=new AbortController,{signal:i}=n;if(e.classList.contains(t))return;e.classList.remove(t),e.classList.add(t);let s=()=>{e.classList.remove(t),r(),n.abort()};e.addEventListener("animationend",s,{once:!0,signal:i}),e.addEventListener("animationcancel",s,{once:!0,signal:i})})}function ar(e,t){const r={waitUntilFirstUpdate:!1,...t};return(n,i)=>{const{update:s}=n,o=Array.isArray(e)?e:[e];n.update=function(a){o.forEach(l=>{const u=l;if(a.has(u)){const c=a.get(u),d=this[u];c!==d&&(!r.waitUntilFirstUpdate||this.hasUpdated)&&this[i](c,d)}}),s.call(this,a)}}}var YR=`:host {
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
`,Ue=class extends Ht{constructor(){super(...arguments),this.placement="top",this.disabled=!1,this.distance=8,this.open=!1,this.skidding=0,this.showDelay=150,this.hideDelay=0,this.trigger="hover focus",this.withoutArrow=!1,this.for=null,this.anchor=null,this.eventController=new AbortController,this.handleBlur=()=>{this.hasTrigger("focus")&&this.hide()},this.handleClick=()=>{this.hasTrigger("click")&&(this.open?this.hide():this.show())},this.handleFocus=()=>{this.hasTrigger("focus")&&this.show()},this.handleDocumentKeyDown=e=>{e.key==="Escape"&&(e.stopPropagation(),this.hide())},this.handleMouseOver=()=>{this.hasTrigger("hover")&&(clearTimeout(this.hoverTimeout),this.hoverTimeout=window.setTimeout(()=>this.show(),this.showDelay))},this.handleMouseOut=()=>{this.hasTrigger("hover")&&(clearTimeout(this.hoverTimeout),this.hoverTimeout=window.setTimeout(()=>this.hide(),this.hideDelay))}}connectedCallback(){super.connectedCallback(),this.eventController.signal.aborted&&(this.eventController=new AbortController),this.open&&(this.open=!1,this.updateComplete.then(()=>{this.open=!0})),this.id||(this.id=Tp("wa-tooltip-")),this.for&&this.anchor?(this.anchor=null,this.handleForChange()):this.for&&this.handleForChange()}disconnectedCallback(){super.disconnectedCallback(),document.removeEventListener("keydown",this.handleDocumentKeyDown),this.eventController.abort(),this.anchor&&this.removeFromAriaLabelledBy(this.anchor,this.id)}firstUpdated(){this.body.hidden=!this.open,this.open&&(this.popup.active=!0,this.popup.reposition())}hasTrigger(e){return this.trigger.split(" ").includes(e)}addToAriaLabelledBy(e,t){const n=(e.getAttribute("aria-labelledby")||"").split(/\s+/).filter(Boolean);n.includes(t)||(n.push(t),e.setAttribute("aria-labelledby",n.join(" ")))}removeFromAriaLabelledBy(e,t){const i=(e.getAttribute("aria-labelledby")||"").split(/\s+/).filter(Boolean).filter(s=>s!==t);i.length>0?e.setAttribute("aria-labelledby",i.join(" ")):e.removeAttribute("aria-labelledby")}async handleOpenChange(){if(this.open){if(this.disabled)return;const e=new ca;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}document.addEventListener("keydown",this.handleDocumentKeyDown,{signal:this.eventController.signal}),this.body.hidden=!1,this.popup.active=!0,await Ct(this.popup.popup,"show-with-scale"),this.popup.reposition(),this.dispatchEvent(new aa)}else{const e=new la;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}document.removeEventListener("keydown",this.handleDocumentKeyDown),await Ct(this.popup.popup,"hide-with-scale"),this.popup.active=!1,this.body.hidden=!0,this.dispatchEvent(new oa)}}handleForChange(){const e=this.getRootNode();if(!e)return;const t=this.for?e.getElementById(this.for):null,r=this.anchor;if(t===r)return;const{signal:n}=this.eventController;t&&(this.addToAriaLabelledBy(t,this.id),t.addEventListener("blur",this.handleBlur,{capture:!0,signal:n}),t.addEventListener("focus",this.handleFocus,{capture:!0,signal:n}),t.addEventListener("click",this.handleClick,{signal:n}),t.addEventListener("mouseover",this.handleMouseOver,{signal:n}),t.addEventListener("mouseout",this.handleMouseOut,{signal:n})),r&&(this.removeFromAriaLabelledBy(r,this.id),r.removeEventListener("blur",this.handleBlur,{capture:!0}),r.removeEventListener("focus",this.handleFocus,{capture:!0}),r.removeEventListener("click",this.handleClick),r.removeEventListener("mouseover",this.handleMouseOver),r.removeEventListener("mouseout",this.handleMouseOut)),this.anchor=t}async handleOptionsChange(){this.hasUpdated&&(await this.updateComplete,this.popup.reposition())}handleDisabledChange(){this.disabled&&this.open&&this.hide()}async show(){if(!this.open)return this.open=!0,Gl(this,"wa-after-show")}async hide(){if(this.open)return this.open=!1,Gl(this,"wa-after-hide")}render(){return W`
      <wa-popup
        part="base"
        exportparts="
          popup:base__popup,
          arrow:base__arrow
        "
        class=${er({tooltip:!0,"tooltip-open":this.open})}
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
    `}};Ue.css=YR;Ue.dependencies={"wa-popup":ke};$([ze("slot:not([name])")],Ue.prototype,"defaultSlot",2);$([ze(".body")],Ue.prototype,"body",2);$([ze("wa-popup")],Ue.prototype,"popup",2);$([V()],Ue.prototype,"placement",2);$([V({type:Boolean,reflect:!0})],Ue.prototype,"disabled",2);$([V({type:Number})],Ue.prototype,"distance",2);$([V({type:Boolean,reflect:!0})],Ue.prototype,"open",2);$([V({type:Number})],Ue.prototype,"skidding",2);$([V({attribute:"show-delay",type:Number})],Ue.prototype,"showDelay",2);$([V({attribute:"hide-delay",type:Number})],Ue.prototype,"hideDelay",2);$([V()],Ue.prototype,"trigger",2);$([V({attribute:"without-arrow",type:Boolean,reflect:!0})],Ue.prototype,"withoutArrow",2);$([V()],Ue.prototype,"for",2);$([ir()],Ue.prototype,"anchor",2);$([ar("open",{waitUntilFirstUpdate:!0})],Ue.prototype,"handleOpenChange",1);$([ar("for")],Ue.prototype,"handleForChange",1);$([ar(["distance","placement","skidding"])],Ue.prototype,"handleOptionsChange",1);$([ar("disabled")],Ue.prototype,"handleDisabledChange",1);Ue=$([Fr("wa-tooltip")],Ue);var XR=class extends Ue{static get styles(){return[Ue.styles,te`
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
      `]}};customElements.get("c-tooltip")||customElements.define("c-tooltip",XR);var JR=te`
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
`,QR=te`
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
`,eE=Object.defineProperty,Kb=Object.getOwnPropertySymbols,ZR=Object.prototype.hasOwnProperty,eM=Object.prototype.propertyIsEnumerable,tE=e=>{throw TypeError(e)},Gb=(e,t,r)=>t in e?eE(e,t,{enumerable:!0,configurable:!0,writable:!0,value:r}):e[t]=r,tM=(e,t)=>{for(var r in t||(t={}))ZR.call(t,r)&&Gb(e,r,t[r]);if(Kb)for(var r of Kb(t))eM.call(t,r)&&Gb(e,r,t[r]);return e},rE=(e,t,r,n)=>{for(var i=void 0,s=e.length-1,o;s>=0;s--)(o=e[s])&&(i=o(t,r,i)||i);return i&&eE(t,r,i),i},nE=(e,t,r)=>t.has(e)||tE("Cannot "+r),rM=(e,t,r)=>(nE(e,t,"read from private field"),t.get(e)),nM=(e,t,r)=>t.has(e)?tE("Cannot add the same private member more than once"):t instanceof WeakSet?t.add(e):t.set(e,r),iM=(e,t,r,n)=>(nE(e,t,"write to private field"),t.set(e,r),r),il,ua=class extends Ne{constructor(){super(),nM(this,il,!1),this.initialReflectedProperties=new Map,Object.entries(this.constructor.dependencies).forEach(([e,t])=>{this.constructor.define(e,t)})}emit(e,t){const r=new CustomEvent(e,tM({bubbles:!0,cancelable:!1,composed:!0,detail:{}},t));return this.dispatchEvent(r),r}static define(e,t=this,r={}){const n=customElements.get(e);if(!n){try{customElements.define(e,t,r)}catch{customElements.define(e,class extends t{},r)}return}let i=" (unknown version)",s=i;"version"in t&&t.version&&(i=" v"+t.version),"version"in n&&n.version&&(s=" v"+n.version),!(i&&s&&i===s)&&console.warn(`Attempted to register <${e}>${i}, but <${e}>${s} has already been registered.`)}attributeChangedCallback(e,t,r){rM(this,il)||(this.constructor.elementProperties.forEach((n,i)=>{n.reflect&&this[i]!=null&&this.initialReflectedProperties.set(i,this[i])}),iM(this,il,!0)),super.attributeChangedCallback(e,t,r)}willUpdate(e){super.willUpdate(e),this.initialReflectedProperties.forEach((t,r)=>{e.has(r)&&this[r]==null&&(this[r]=t)})}};il=new WeakMap;ua.version="2.20.1";ua.dependencies={};rE([V()],ua.prototype,"dir");rE([V()],ua.prototype,"lang");var iE=class extends ua{render(){return W` <slot></slot> `}};iE.styles=[QR,JR];iE.define("sl-visually-hidden");var sM=te`
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
`,da=class extends Ne{constructor(...e){super(...e),this.isCopying=!1,this.value="",this.disabled=!1}async copyValue(){if(!(this.isCopying||this.disabled)){this.isCopying=!0;try{await navigator.clipboard.writeText(this.value),this.dispatchEvent(new CustomEvent("craft-copy",{bubbles:!0,cancelable:!1,composed:!0,detail:{value:this.value}}))}catch{this.dispatchEvent(new CustomEvent("craft-error",{cancelable:!1,composed:!0,bubbles:!0}))}finally{this.isCopying=!1}}}render(){return W`
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
    `}};da.styles=[sM];se([ir()],da.prototype,"isCopying",void 0);se([V({type:String})],da.prototype,"value",void 0);se([V({type:Boolean})],da.prototype,"disabled",void 0);customElements.get("craft-copy-button")||customElements.define("craft-copy-button",da);var oM=te`
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
`,aM=te`
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
`;const Nn={"icon.in":{keyframes:[{scale:.25,opacity:.25},{scale:1,opacity:1}],options:{duration:100}},"icon.out":{keyframes:[{scale:1,opacity:1},{scale:.25,opacity:.25}],options:{duration:100}}};var Rr=class extends Ne{constructor(){super(),this.status="rest",this.value="",this.disabled=!1,this.feedbackDuration=1e3,this.tooltipLabel="Copy",this.addEventListener("craft-copy",()=>{this.showStatus("success")}),this.addEventListener("craft-error",()=>{this.showStatus("error")})}getId(){return`attribute-${this.value.replace(/([a-z])([A-Z])/g,"$1-$2").replace(/[\s_]+/g,"-").toLowerCase()}`}async showStatus(e){const t=e==="success"?this.successIconEl:this.errorIconEl;this.tooltipLabel=e==="success"?"Copied":"Copy failed",await t.animate(Nn["icon.out"].keyframes,Nn["icon.out"].options),this.copyIconEl.hidden=!0,t.hidden=!1,await t.animate(Nn["icon.in"].keyframes,Nn["icon.in"].options),this.status=e,setTimeout(async()=>{await t.animate(Nn["icon.out"].keyframes,Nn["icon.out"].options),t.hidden=!0,this.copyIconEl.hidden=!1,await this.copyIconEl.animate(Nn["icon.in"].keyframes,Nn["icon.in"].options),this.status="rest",this.tooltipLabel="Copy"},this.feedbackDuration)}render(){return W`
      <c-tooltip for="${this.getId()}">${this.tooltipLabel}</c-tooltip>
      <craft-copy-button
        value="${this.value}"
        id="${this.getId()}"
        class=${er({"copy-attribute":!0,"copy-attribute--success":this.status==="success","copy-attribute--error":this.status==="error"})}
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
    `}};Rr.styles=[oM,aM];se([ir()],Rr.prototype,"status",void 0);se([ze('slot[name="copy-icon"]')],Rr.prototype,"copyIconEl",void 0);se([ze('slot[name="success-icon"]')],Rr.prototype,"successIconEl",void 0);se([ze('slot[name="error-icon"]')],Rr.prototype,"errorIconEl",void 0);se([ze("craft-copy-button")],Rr.prototype,"copyButtonEl",void 0);se([V({type:String})],Rr.prototype,"value",void 0);se([V({type:Boolean,reflect:!0})],Rr.prototype,"disabled",void 0);se([V({attribute:"feedback-duration",type:Number})],Rr.prototype,"feedbackDuration",void 0);se([V({reflect:!1})],Rr.prototype,"tooltipLabel",void 0);customElements.get("craft-copy-attribute")||customElements.define("craft-copy-attribute",Rr);const sE=new WeakMap;function lM(e,t){let r=t;for(;r;){if(sE.get(r)===e)return!0;r=Object.getPrototypeOf(r)}return!1}function He(e){return t=>{if(lM(e,t))return t;const r=e(t);return sE.set(r,e),r}}const cM=e=>class extends e{static get properties(){return{disabled:{type:Boolean,reflect:!0}}}constructor(){super(),this._requestedToBeDisabled=!1,this.__isUserSettingDisabled=!0,this.__restoreDisabledTo=!1,this.disabled=!1}makeRequestToBeDisabled(){this._requestedToBeDisabled===!1&&(this._requestedToBeDisabled=!0,this.__restoreDisabledTo=this.disabled,this.__internalSetDisabled(!0))}retractRequestToBeDisabled(){this._requestedToBeDisabled===!0&&(this._requestedToBeDisabled=!1,this.__internalSetDisabled(this.__restoreDisabledTo))}__internalSetDisabled(t){this.__isUserSettingDisabled=!1,this.disabled=t,this.__isUserSettingDisabled=!0}requestUpdate(t,r,n){super.requestUpdate(t,r,n),t==="disabled"&&(this.__isUserSettingDisabled&&(this.__restoreDisabledTo=this.disabled),this.disabled===!1&&this._requestedToBeDisabled===!0&&this.__internalSetDisabled(!0))}click(){this.disabled||super.click()}},ha=He(cM),uM=e=>class extends ha(e){static get properties(){return{tabIndex:{type:Number,reflect:!0,attribute:"tabindex"}}}constructor(){super(),this.__isUserSettingTabIndex=!0,this.__restoreTabIndexTo=0,this.__internalSetTabIndex(0)}makeRequestToBeDisabled(){super.makeRequestToBeDisabled(),this._requestedToBeDisabled===!1&&this.tabIndex!=null&&(this.__restoreTabIndexTo=this.tabIndex)}retractRequestToBeDisabled(){super.retractRequestToBeDisabled(),this._requestedToBeDisabled===!0&&this.__internalSetTabIndex(this.__restoreTabIndexTo)}static enabledWarnings=super.enabledWarnings?.filter(t=>t!=="change-in-update")||[];__internalSetTabIndex(t){this.__isUserSettingTabIndex=!1,this.tabIndex=t,this.__isUserSettingTabIndex=!0}requestUpdate(t,r,n){super.requestUpdate(t,r,n),t==="disabled"&&(this.disabled?this.__internalSetTabIndex(-1):this.__internalSetTabIndex(this.__restoreTabIndexTo)),t==="tabIndex"&&(this.__isUserSettingTabIndex&&this.tabIndex!=null&&(this.__restoreTabIndexTo=this.tabIndex),this.tabIndex!==-1&&this._requestedToBeDisabled===!0&&this.__internalSetTabIndex(-1))}firstUpdated(t){super.firstUpdated(t),this.disabled&&this.__internalSetTabIndex(-1)}},oE=He(uM);const{I:dM}=KE,hM=e=>e===null||typeof e!="object"&&typeof e!="function",aE=(e,t)=>e?._$litType$!==void 0,fM=e=>e.strings===void 0,Yb=()=>document.createComment(""),qs=(e,t,r)=>{const n=e._$AA.parentNode,i=t===void 0?e._$AB:t._$AA;if(r===void 0){const s=n.insertBefore(Yb(),i),o=n.insertBefore(Yb(),i);r=new dM(s,o,e,e.options)}else{const s=r._$AB.nextSibling,o=r._$AM,a=o!==e;if(a){let l;r._$AQ?.(e),r._$AM=e,r._$AP!==void 0&&(l=e._$AU)!==o._$AU&&r._$AP(l)}if(s!==i||a){let l=r._$AA;for(;l!==s;){const u=l.nextSibling;n.insertBefore(l,i),l=u}}}return r},ui=(e,t,r=e)=>(e._$AI(t,r),e),pM={},mM=(e,t=pM)=>e._$AH=t,gM=e=>e._$AH,Td=e=>{e._$AR(),e._$AA.remove()};function bM(e){return e instanceof Node?"node":aE(e)?"template-result":!Array.isArray(e)&&typeof e=="object"&&"template"in e?"slot-rerender-object":null}const vM=e=>class extends e{get slots(){return{}}constructor(){super(),this.__renderMetaPerSlot=new Map,this.__slotsThatNeedRerender=new Set,this.__slotsProvidedByUserOnFirstConnected=new Set,this.__privateSlots=new Set}connectedCallback(){super.connectedCallback(),this._connectSlotMixin()}__rerenderSlot(r){const n=this.slots[r]();this.__renderTemplateInScopedContext({renderAsDirectHostChild:n.renderAsDirectHostChild,template:n.template,slotName:r}),n.afterRender?.()}update(r){super.update(r);for(const n of this.__slotsThatNeedRerender)this.__rerenderSlot(n)}__renderTemplateInScopedContext({template:r,slotName:n,renderAsDirectHostChild:i}){if(!this.__renderMetaPerSlot.has(n)){const p=!!ShadowRoot.prototype.createElement;!!this.shadowRoot||console.error("[SlotMixin] No shadowRoot was found");const f=(p?this.shadowRoot:document).createElement("div"),b=document.createComment(`_start_slot_${n}_`),y=document.createComment(`_end_slot_${n}_`);f.appendChild(b),f.appendChild(y);const{creationScope:w,host:v}=this.renderOptions;if(Wp(r,f,{renderBefore:y,creationScope:w,host:v}),i){const _=Array.from(f.childNodes);this.__appendNodes({nodes:_,renderParent:this,slotName:n})}else f.slot=n,this.appendChild(f);this.__renderMetaPerSlot.set(n,{renderTargetThatRespectsShadowRootScoping:f,renderBefore:y});return}const{renderBefore:o,renderTargetThatRespectsShadowRootScoping:a}=this.__renderMetaPerSlot.get(n),l=i?this:a,{creationScope:u,host:c}=this.renderOptions;Wp(r,l,{creationScope:u,host:c,renderBefore:o}),i&&o.previousElementSibling&&!o.previousElementSibling.slot&&(o.previousElementSibling.slot=n)}__appendNodes({nodes:r,renderParent:n=this,slotName:i}){for(const s of r)s instanceof Element&&i&&i!==""&&s.setAttribute("slot",i),n.appendChild(s)}__initSlots(r){for(const n of r){if(this.__slotsProvidedByUserOnFirstConnected.has(n))continue;const i=this.slots[n]();if(i===void 0)continue;switch(this.__isConnectedSlotMixin||this.__privateSlots.add(n),bM(i)){case"template-result":this.__renderTemplateInScopedContext({template:i,renderAsDirectHostChild:!0,slotName:n});break;case"node":this.__appendNodes({nodes:[i],renderParent:this,slotName:n});break;case"slot-rerender-object":this.__slotsThatNeedRerender.add(n),i.firstRenderOnConnected&&this.__rerenderSlot(n);break;default:throw new Error(`Slot "${n}" configured inside "get slots()" (in prototype) of ${this.constructor.name} may return these types: TemplateResult | Node | {template:TemplateResult, afterRender?:function} | undefined.
              You provided: ${i}`)}}}_connectSlotMixin(){if(this.__isConnectedSlotMixin)return;const r=Object.keys(this.slots);for(const n of r)(n===""?Array.from(this.children).find(s=>!s.hasAttribute("slot")):Array.from(this.children).find(s=>s.slot===n))&&this.__slotsProvidedByUserOnFirstConnected.add(n);this.__initSlots(r),this.__isConnectedSlotMixin=!0}_isPrivateSlot(r){return this.__privateSlots.has(r)}},Is=He(vM);function kd(e="google-chrome"){const t=globalThis.navigator,r=!!t.userAgentData&&t.userAgentData.brands.some(l=>l.brand==="Chromium");if(e==="chromium")return r;const i=globalThis.navigator?.vendor,s=typeof globalThis.opr<"u",o=globalThis.userAgent?.indexOf("Edge")>-1,a=globalThis.userAgent?.match("CriOS");if(e==="ios")return a;if(e==="google-chrome")return r!==null&&typeof r<"u"&&i==="Google Inc."&&s===!1&&o===!1}const Yl={isChrome:kd(),isIOSChrome:kd("ios"),isChromium:kd("chromium"),isFirefox:globalThis.navigator?.userAgent.toLowerCase().indexOf("firefox")>-1,isMac:globalThis.navigator?.appVersion?.indexOf("Mac")!==-1,isIOS:/iPhone|iPad|iPod/i.test(globalThis.navigator?.userAgent),isMacSafari:globalThis.navigator?.vendor&&globalThis.navigator?.vendor.indexOf("Apple")>-1&&globalThis.navigator?.userAgent&&globalThis.navigator?.userAgent.indexOf("CriOS")===-1&&globalThis.navigator?.userAgent.indexOf("FxiOS")===-1&&globalThis.navigator?.appVersion.indexOf("Mac")!==-1};function fa(e=""){return`${e.length>0?`${e}-`:""}${Math.random().toString(36).substr(2,10)}`}const Od=e=>e.key===" "||e.key==="Enter",Xb=e=>e.key===" ";class yM extends oE(Ne){static get properties(){return{active:{type:Boolean,reflect:!0},type:{type:String,reflect:!0}}}render(){return W` <div class="button-content"><slot></slot></div> `}static get styles(){return[te`
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
      `]}constructor(){super(),this.type="button",this.active=!1,this.__setupEvents()}connectedCallback(){super.connectedCallback(),this.hasAttribute("role")||this.setAttribute("role","button")}updated(t){super.updated(t),t.has("disabled")&&(this.disabled?this.setAttribute("aria-disabled","true"):this.getAttribute("aria-disabled")!==null&&this.removeAttribute("aria-disabled"))}__setupEvents(){this.addEventListener("mousedown",this.__mousedownHandler),this.addEventListener("keydown",this.__keydownHandler),this.addEventListener("keyup",this.__keyupHandler)}__mousedownHandler(){this.active=!0;const t=()=>{this.active=!1,document.removeEventListener("mouseup",t),this.removeEventListener("mouseup",t)};document.addEventListener("mouseup",t),this.addEventListener("mouseup",t)}__keydownHandler(t){if(this.active||!Od(t)){Xb(t)&&t.preventDefault();return}Xb(t)&&t.preventDefault(),this.active=!0;const r=n=>{Od(n)&&(this.active=!1,document.removeEventListener("keyup",r,!0))};document.addEventListener("keyup",r,!0)}__keyupHandler(t){if(Od(t)){if(t.target&&t.target!==this)return;this.click()}}}class _M extends yM{constructor(){super(),this.type="reset",this.__setupDelegationInConstructor(),this.__submitAndResetHelperButton=document.createElement("button"),this.__preventEventLeakage=this.__preventEventLeakage.bind(this)}connectedCallback(){super.connectedCallback(),this.updateComplete.then(()=>{this._setupSubmitAndResetHelperOnConnected()})}disconnectedCallback(){super.disconnectedCallback(),this._teardownSubmitAndResetHelperOnDisconnected()}__preventEventLeakage(t){t.target===this.__submitAndResetHelperButton&&t.stopImmediatePropagation()}_setupSubmitAndResetHelperOnConnected(){this.appendChild(this.__submitAndResetHelperButton),this._form=this.__submitAndResetHelperButton.form,this.removeChild(this.__submitAndResetHelperButton),this._form&&this._form.addEventListener("click",this.__preventEventLeakage)}_teardownSubmitAndResetHelperOnDisconnected(){this._form&&this._form.removeEventListener("click",this.__preventEventLeakage)}async __clickDelegationHandler(t){this._form||await this.updateComplete,(this.type==="submit"||this.type==="reset")&&t.target===this&&this._form&&(this.__submitAndResetHelperButton.type=this.type,this._form.appendChild(this.__submitAndResetHelperButton),this.__submitAndResetHelperButton.click(),this._form.removeChild(this.__submitAndResetHelperButton))}__setupDelegationInConstructor(){this.addEventListener("click",this.__clickDelegationHandler,!0)}}const Pn=new WeakMap;function wM(){const e=document.createElement("button");return e.tabIndex=-1,e.type="submit",e.setAttribute("aria-hidden","true"),e.style.cssText=`
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
  `,e}class EM extends _M{get _nativeButtonNode(){return Pn.get(this._form)?.helper||null}constructor(){super(),this.type="submit",this.__implicitSubmitHelperButton=null}_setupSubmitAndResetHelperOnConnected(){if(super._setupSubmitAndResetHelperOnConnected(),!this._form||this.type!=="submit")return;const t=this._form;if(!Pn.get(this._form)){const n=wM(),i=document.createElement("div");i.appendChild(n),Pn.set(this._form,{lionButtons:new Set,helper:n,observer:new MutationObserver(()=>{t.appendChild(i)})}),t.appendChild(i),Pn.get(t)?.observer.observe(i,{childList:!0})}Pn.get(t)?.lionButtons.add(this)}_teardownSubmitAndResetHelperOnDisconnected(){if(super._teardownSubmitAndResetHelperOnDisconnected(),this._form){const t=Pn.get(this._form);t&&(t.lionButtons.delete(this),t.lionButtons.size||(this._form.contains(t.helper)&&t.helper.remove(),Pn.get(this._form)?.observer.disconnect(),Pn.delete(this._form)))}}}var xM=te`
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
`,Fs=class extends EM{constructor(...e){super(...e),this.appearance="accent",this.variant="default",this.size="medium",this.loading=!1,this.align="center"}static get styles(){return[...super.styles,xM]}render(){return W`
      <div
        class="${er({"button-content":!0,"button-content--start":this.align==="start","button-content--end":this.align==="end"})}"
        part="content"
      >
        <slot name="prefix" class="prefix" part="prefix"></slot>
        <slot class="label" part="label"></slot>
        <slot name="suffix" class="suffix" part="suffix"></slot>
      </div>
      ${this.loading?W`<craft-spinner part="spinner"></craft-spinner>`:ht}
    `}};se([V({reflect:!0})],Fs.prototype,"appearance",void 0);se([V({reflect:!0})],Fs.prototype,"variant",void 0);se([V({reflect:!0})],Fs.prototype,"size",void 0);se([V({reflect:!0,type:Boolean})],Fs.prototype,"loading",void 0);se([V()],Fs.prototype,"align",void 0);customElements.get("craft-button")||customElements.define("craft-button",Fs);var SM=te`
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
`,Hc=class extends Ne{constructor(...e){super(...e),this.label=null,this._gradientId=null}connectedCallback(){super.connectedCallback(),this._gradientId=`avatar-gradient-${Math.random().toString(36).slice(2,8)}`}text(){return this.label?this.label.split(" ").map(e=>e.charAt(0).toUpperCase()).join(""):"?"}render(){return W`
      <span class="avatar">
        <svg
          viewBox="0 0 100 100"
          xmlns="http://www.w3.org/2000/svg"
          role="img"
        >
          ${this.label?W`<title>${this.label}</title>`:""}
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
    `}};Hc.styles=[SM];se([V()],Hc.prototype,"label",void 0);se([ir()],Hc.prototype,"_gradientId",void 0);customElements.get("craft-avatar")||customElements.define("craft-avatar",Hc);const kp=te`
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
`,qc=te`
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
`,pa=te`
  ${qc}

  ::slotted([slot='input']) {
    ${kp}
  }
`;var CM=te`
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
`;const Hh=window,Jb=new WeakMap;function AM(e){Hh.applyFocusVisiblePolyfill&&!Jb.has(e)&&(Hh.applyFocusVisiblePolyfill(e),Jb.set(e,void 0))}const TM=e=>class extends e{static get properties(){return{focused:{type:Boolean,reflect:!0},focusedVisible:{type:Boolean,reflect:!0,attribute:"focused-visible"},autofocus:{type:Boolean,reflect:!0}}}constructor(){super(),this.focused=!1,this.focusedVisible=!1,this.autofocus=!1}firstUpdated(r){super.firstUpdated(r),this.__registerEventsForFocusMixin(),this.__syncAutofocusToFocusableElement()}disconnectedCallback(){super.disconnectedCallback(),this.__teardownEventsForFocusMixin()}updated(r){super.updated(r),r.has("autofocus")&&this.__syncAutofocusToFocusableElement()}__syncAutofocusToFocusableElement(){this._focusableNode&&(this.hasAttribute("autofocus")?this._focusableNode.setAttribute("autofocus",""):this._focusableNode.removeAttribute("autofocus"))}focus(){this._focusableNode?.focus()}blur(){this._focusableNode?.blur()}get _focusableNode(){return this._inputNode||document.createElement("input")}__onFocus(){if(this.focused=!0,typeof Hh.applyFocusVisiblePolyfill=="function")this.focusedVisible=this._focusableNode.hasAttribute("data-focus-visible-added");else try{this.focusedVisible=this._focusableNode.matches(":focus-visible")}catch{this.focusedVisible=!1}}__onBlur(){this.focused=!1,this.focusedVisible=!1}__registerEventsForFocusMixin(){AM(this.getRootNode()),this.__redispatchFocus=r=>{r.stopPropagation(),this.dispatchEvent(new Event("focus"))},this._focusableNode.addEventListener("focus",this.__redispatchFocus),this.__redispatchBlur=r=>{r.stopPropagation(),this.dispatchEvent(new Event("blur"))},this._focusableNode.addEventListener("blur",this.__redispatchBlur),this.__redispatchFocusin=r=>{r.stopPropagation(),this.__onFocus(),this.dispatchEvent(new Event("focusin",{bubbles:!0,composed:!0}))},this._focusableNode.addEventListener("focusin",this.__redispatchFocusin),this.__redispatchFocusout=r=>{r.stopPropagation(),this.__onBlur(),this.dispatchEvent(new Event("focusout",{bubbles:!0,composed:!0}))},this._focusableNode.addEventListener("focusout",this.__redispatchFocusout)}__teardownEventsForFocusMixin(){this._focusableNode&&(this._focusableNode?.removeEventListener("focus",this.__redispatchFocus),this._focusableNode?.removeEventListener("blur",this.__redispatchBlur),this._focusableNode?.removeEventListener("focusin",this.__redispatchFocusin),this._focusableNode?.removeEventListener("focusout",this.__redispatchFocusout))}},Op=He(TM);function lE(e,t){return t={exports:{}},e(t,t.exports),t.exports}var di="long",In="short",Nd="narrow",we="numeric",Fn="2-digit",Ln={number:{decimal:{style:"decimal"},integer:{style:"decimal",maximumFractionDigits:0},currency:{style:"currency",currency:"USD"},percent:{style:"percent"},default:{style:"decimal"}},date:{short:{month:we,day:we,year:Fn},medium:{month:In,day:we,year:we},long:{month:di,day:we,year:we},full:{month:di,day:we,year:we,weekday:di},default:{month:In,day:we,year:we}},time:{short:{hour:we,minute:we},medium:{hour:we,minute:we,second:we},long:{hour:we,minute:we,second:we,timeZoneName:In},full:{hour:we,minute:we,second:we,timeZoneName:In},default:{hour:we,minute:we,second:we}},duration:{default:{hours:{minimumIntegerDigits:1,maximumFractionDigits:0},minutes:{minimumIntegerDigits:2,maximumFractionDigits:0},seconds:{minimumIntegerDigits:2,maximumFractionDigits:3}}},parseNumberPattern:function(e){if(e){var t={},r=e.match(/\b[A-Z]{3}\b/i),n=e.replace(/[^¤]/g,"").length;if(!n&&r&&(n=1),n?(t.style="currency",t.currencyDisplay=n===1?"symbol":n===2?"code":"name",t.currency=r?r[0].toUpperCase():"USD"):e.indexOf("%")>=0&&(t.style="percent"),!/[@#0]/.test(e))return t.style?t:void 0;if(t.useGrouping=e.indexOf(",")>=0,/E\+?[@#0]+/i.test(e)||e.indexOf("@")>=0){var i=e.replace(/E\+?[@#0]+|[^@#0]/gi,"");t.minimumSignificantDigits=Math.min(Math.max(i.replace(/[^@0]/g,"").length,1),21),t.maximumSignificantDigits=Math.min(Math.max(i.length,1),21)}else{for(var s=e.replace(/[^#0.]/g,"").split("."),o=s[0],a=o.length-1;o[a]==="0";)--a;t.minimumIntegerDigits=Math.min(Math.max(o.length-1-a,1),21);var l=s[1]||"";for(a=0;l[a]==="0";)++a;for(t.minimumFractionDigits=Math.min(Math.max(a,0),20);l[a]==="#";)++a;t.maximumFractionDigits=Math.min(Math.max(a,0),20)}return t}},parseDatePattern:function(e){if(e){for(var t={},r=0;r<e.length;){for(var n=e[r],i=1;e[++r]===n;)++i;switch(n){case"G":t.era=i===5?Nd:i===4?di:In;break;case"y":case"Y":t.year=i===2?Fn:we;break;case"M":case"L":i=Math.min(Math.max(i-1,0),4),t.month=[we,Fn,In,di,Nd][i];break;case"E":case"e":case"c":t.weekday=i===5?Nd:i===4?di:In;break;case"d":case"D":t.day=i===2?Fn:we;break;case"h":case"K":t.hour12=!0,t.hour=i===2?Fn:we;break;case"H":case"k":t.hour12=!1,t.hour=i===2?Fn:we;break;case"m":t.minute=i===2?Fn:we;break;case"s":case"S":t.second=i===2?Fn:we;break;case"z":case"Z":case"v":case"V":t.timeZoneName=i===1?In:di;break}}return Object.keys(t).length?t:void 0}}},kM=function(t,r){if(typeof t=="string"&&r[t])return t;for(var n=[].concat(t||[]),i=0,s=n.length;i<s;++i)for(var o=n[i].split("-");o.length;){var a=o.join("-");if(r[a])return a;o.pop()}},ji="zero",oe="one",rt="two",Te="few",je="many",ie="other",O=[function(e){var t=+e;return t===1?oe:ie},function(e){var t=+e;return 0<=t&&t<=1?oe:ie},function(e){var t=Math.floor(Math.abs(+e)),r=+e;return t===0||r===1?oe:ie},function(e){var t=+e;return t===0?ji:t===1?oe:t===2?rt:3<=t%100&&t%100<=10?Te:11<=t%100&&t%100<=99?je:ie},function(e){var t=Math.floor(Math.abs(+e)),r=(e+".").split(".")[1].length;return t===1&&r===0?oe:ie},function(e){var t=+e;return t%10===1&&t%100!==11?oe:2<=t%10&&t%10<=4&&(t%100<12||14<t%100)?Te:t%10===0||5<=t%10&&t%10<=9||11<=t%100&&t%100<=14?je:ie},function(e){var t=+e;return t%10===1&&t%100!==11&&t%100!==71&&t%100!==91?oe:t%10===2&&t%100!==12&&t%100!==72&&t%100!==92?rt:(3<=t%10&&t%10<=4||t%10===9)&&(t%100<10||19<t%100)&&(t%100<70||79<t%100)&&(t%100<90||99<t%100)?Te:t!==0&&t%1e6===0?je:ie},function(e){var t=Math.floor(Math.abs(+e)),r=(e+".").split(".")[1].length,n=+(e+".").split(".")[1];return r===0&&t%10===1&&t%100!==11||n%10===1&&n%100!==11?oe:r===0&&2<=t%10&&t%10<=4&&(t%100<12||14<t%100)||2<=n%10&&n%10<=4&&(n%100<12||14<n%100)?Te:ie},function(e){var t=Math.floor(Math.abs(+e)),r=(e+".").split(".")[1].length;return t===1&&r===0?oe:2<=t&&t<=4&&r===0?Te:r!==0?je:ie},function(e){var t=+e;return t===0?ji:t===1?oe:t===2?rt:t===3?Te:t===6?je:ie},function(e){var t=Math.floor(Math.abs(+e)),r=+(""+e).replace(/^[^.]*.?|0+$/g,""),n=+e;return n===1||r!==0&&(t===0||t===1)?oe:ie},function(e){var t=Math.floor(Math.abs(+e)),r=(e+".").split(".")[1].length,n=+(e+".").split(".")[1];return r===0&&t%100===1||n%100===1?oe:r===0&&t%100===2||n%100===2?rt:r===0&&3<=t%100&&t%100<=4||3<=n%100&&n%100<=4?Te:ie},function(e){var t=Math.floor(Math.abs(+e));return t===0||t===1?oe:ie},function(e){var t=Math.floor(Math.abs(+e)),r=(e+".").split(".")[1].length,n=+(e+".").split(".")[1];return r===0&&(t===1||t===2||t===3)||r===0&&t%10!==4&&t%10!==6&&t%10!==9||r!==0&&n%10!==4&&n%10!==6&&n%10!==9?oe:ie},function(e){var t=+e;return t===1?oe:t===2?rt:3<=t&&t<=6?Te:7<=t&&t<=10?je:ie},function(e){var t=+e;return t===1||t===11?oe:t===2||t===12?rt:3<=t&&t<=10||13<=t&&t<=19?Te:ie},function(e){var t=Math.floor(Math.abs(+e)),r=(e+".").split(".")[1].length;return r===0&&t%10===1?oe:r===0&&t%10===2?rt:r===0&&(t%100===0||t%100===20||t%100===40||t%100===60||t%100===80)?Te:r!==0?je:ie},function(e){var t=Math.floor(Math.abs(+e)),r=(e+".").split(".")[1].length,n=+e;return t===1&&r===0?oe:t===2&&r===0?rt:r===0&&(n<0||10<n)&&n%10===0?je:ie},function(e){var t=Math.floor(Math.abs(+e)),r=+(""+e).replace(/^[^.]*.?|0+$/g,"");return r===0&&t%10===1&&t%100!==11||r!==0?oe:ie},function(e){var t=+e;return t===1?oe:t===2?rt:ie},function(e){var t=+e;return t===0?ji:t===1?oe:ie},function(e){var t=Math.floor(Math.abs(+e)),r=+e;return r===0?ji:(t===0||t===1)&&r!==0?oe:ie},function(e){var t=+(e+".").split(".")[1],r=+e;return r%10===1&&(r%100<11||19<r%100)?oe:2<=r%10&&r%10<=9&&(r%100<11||19<r%100)?Te:t!==0?je:ie},function(e){var t=(e+".").split(".")[1].length,r=+(e+".").split(".")[1],n=+e;return n%10===0||11<=n%100&&n%100<=19||t===2&&11<=r%100&&r%100<=19?ji:n%10===1&&n%100!==11||t===2&&r%10===1&&r%100!==11||t!==2&&r%10===1?oe:ie},function(e){var t=Math.floor(Math.abs(+e)),r=(e+".").split(".")[1].length,n=+(e+".").split(".")[1];return r===0&&t%10===1&&t%100!==11||n%10===1&&n%100!==11?oe:ie},function(e){var t=Math.floor(Math.abs(+e)),r=(e+".").split(".")[1].length,n=+e;return t===1&&r===0?oe:r!==0||n===0||n!==1&&1<=n%100&&n%100<=19?Te:ie},function(e){var t=+e;return t===1?oe:t===0||2<=t%100&&t%100<=10?Te:11<=t%100&&t%100<=19?je:ie},function(e){var t=Math.floor(Math.abs(+e)),r=(e+".").split(".")[1].length;return t===1&&r===0?oe:r===0&&2<=t%10&&t%10<=4&&(t%100<12||14<t%100)?Te:r===0&&t!==1&&0<=t%10&&t%10<=1||r===0&&5<=t%10&&t%10<=9||r===0&&12<=t%100&&t%100<=14?je:ie},function(e){var t=Math.floor(Math.abs(+e));return 0<=t&&t<=1?oe:ie},function(e){var t=Math.floor(Math.abs(+e)),r=(e+".").split(".")[1].length;return r===0&&t%10===1&&t%100!==11?oe:r===0&&2<=t%10&&t%10<=4&&(t%100<12||14<t%100)?Te:r===0&&t%10===0||r===0&&5<=t%10&&t%10<=9||r===0&&11<=t%100&&t%100<=14?je:ie},function(e){var t=Math.floor(Math.abs(+e)),r=+e;return t===0||r===1?oe:2<=r&&r<=10?Te:ie},function(e){var t=Math.floor(Math.abs(+e)),r=+(e+".").split(".")[1],n=+e;return n===0||n===1||t===0&&r===1?oe:ie},function(e){var t=Math.floor(Math.abs(+e)),r=(e+".").split(".")[1].length;return r===0&&t%100===1?oe:r===0&&t%100===2?rt:r===0&&3<=t%100&&t%100<=4||r!==0?Te:ie},function(e){var t=+e;return 0<=t&&t<=1||11<=t&&t<=99?oe:ie},function(e){var t=+e;return t===1||t===5||t===7||t===8||t===9||t===10?oe:t===2||t===3?rt:t===4?Te:t===6?je:ie},function(e){var t=Math.floor(Math.abs(+e));return t%10===1||t%10===2||t%10===5||t%10===7||t%10===8||t%100===20||t%100===50||t%100===70||t%100===80?oe:t%10===3||t%10===4||t%1e3===100||t%1e3===200||t%1e3===300||t%1e3===400||t%1e3===500||t%1e3===600||t%1e3===700||t%1e3===800||t%1e3===900?Te:t===0||t%10===6||t%100===40||t%100===60||t%100===90?je:ie},function(e){var t=+e;return(t%10===2||t%10===3)&&t%100!==12&&t%100!==13?Te:ie},function(e){var t=+e;return t===1||t===3?oe:t===2?rt:t===4?Te:ie},function(e){var t=+e;return t===0||t===7||t===8||t===9?ji:t===1?oe:t===2?rt:t===3||t===4?Te:t===5||t===6?je:ie},function(e){var t=+e;return t%10===1&&t%100!==11?oe:t%10===2&&t%100!==12?rt:t%10===3&&t%100!==13?Te:ie},function(e){var t=+e;return t===1||t===11?oe:t===2||t===12?rt:t===3||t===13?Te:ie},function(e){var t=+e;return t===1?oe:t===2||t===3?rt:t===4?Te:t===6?je:ie},function(e){var t=+e;return t===1||t===5?oe:ie},function(e){var t=+e;return t===11||t===8||t===80||t===800?je:ie},function(e){var t=Math.floor(Math.abs(+e));return t===1?oe:t===0||2<=t%100&&t%100<=20||t%100===40||t%100===60||t%100===80?je:ie},function(e){var t=+e;return t%10===6||t%10===9||t%10===0&&t!==0?je:ie},function(e){var t=Math.floor(Math.abs(+e));return t%10===1&&t%100!==11?oe:t%10===2&&t%100!==12?rt:(t%10===7||t%10===8)&&t%100!==17&&t%100!==18?je:ie},function(e){var t=+e;return t===1?oe:t===2||t===3?rt:t===4?Te:ie},function(e){var t=+e;return 1<=t&&t<=4?oe:ie},function(e){var t=+e;return t===1||t===5||7<=t&&t<=9?oe:t===2||t===3?rt:t===4?Te:t===6?je:ie},function(e){var t=+e;return t===1?oe:t%10===4&&t%100!==14?je:ie},function(e){var t=+e;return(t%10===1||t%10===2)&&t%100!==11&&t%100!==12?oe:ie},function(e){var t=+e;return t%10===6||t%10===9||t===10?Te:ie},function(e){var t=+e;return t%10===3&&t%100!==13?Te:ie}],qh={af:{cardinal:O[0]},ak:{cardinal:O[1]},am:{cardinal:O[2]},ar:{cardinal:O[3]},ars:{cardinal:O[3]},as:{cardinal:O[2],ordinal:O[34]},asa:{cardinal:O[0]},ast:{cardinal:O[4]},az:{cardinal:O[0],ordinal:O[35]},be:{cardinal:O[5],ordinal:O[36]},bem:{cardinal:O[0]},bez:{cardinal:O[0]},bg:{cardinal:O[0]},bh:{cardinal:O[1]},bn:{cardinal:O[2],ordinal:O[34]},br:{cardinal:O[6]},brx:{cardinal:O[0]},bs:{cardinal:O[7]},ca:{cardinal:O[4],ordinal:O[37]},ce:{cardinal:O[0]},cgg:{cardinal:O[0]},chr:{cardinal:O[0]},ckb:{cardinal:O[0]},cs:{cardinal:O[8]},cy:{cardinal:O[9],ordinal:O[38]},da:{cardinal:O[10]},de:{cardinal:O[4]},dsb:{cardinal:O[11]},dv:{cardinal:O[0]},ee:{cardinal:O[0]},el:{cardinal:O[0]},en:{cardinal:O[4],ordinal:O[39]},eo:{cardinal:O[0]},es:{cardinal:O[0]},et:{cardinal:O[4]},eu:{cardinal:O[0]},fa:{cardinal:O[2]},ff:{cardinal:O[12]},fi:{cardinal:O[4]},fil:{cardinal:O[13],ordinal:O[0]},fo:{cardinal:O[0]},fr:{cardinal:O[12],ordinal:O[0]},fur:{cardinal:O[0]},fy:{cardinal:O[4]},ga:{cardinal:O[14],ordinal:O[0]},gd:{cardinal:O[15],ordinal:O[40]},gl:{cardinal:O[4]},gsw:{cardinal:O[0]},gu:{cardinal:O[2],ordinal:O[41]},guw:{cardinal:O[1]},gv:{cardinal:O[16]},ha:{cardinal:O[0]},haw:{cardinal:O[0]},he:{cardinal:O[17]},hi:{cardinal:O[2],ordinal:O[41]},hr:{cardinal:O[7]},hsb:{cardinal:O[11]},hu:{cardinal:O[0],ordinal:O[42]},hy:{cardinal:O[12],ordinal:O[0]},ia:{cardinal:O[4]},io:{cardinal:O[4]},is:{cardinal:O[18]},it:{cardinal:O[4],ordinal:O[43]},iu:{cardinal:O[19]},iw:{cardinal:O[17]},jgo:{cardinal:O[0]},ji:{cardinal:O[4]},jmc:{cardinal:O[0]},ka:{cardinal:O[0],ordinal:O[44]},kab:{cardinal:O[12]},kaj:{cardinal:O[0]},kcg:{cardinal:O[0]},kk:{cardinal:O[0],ordinal:O[45]},kkj:{cardinal:O[0]},kl:{cardinal:O[0]},kn:{cardinal:O[2]},ks:{cardinal:O[0]},ksb:{cardinal:O[0]},ksh:{cardinal:O[20]},ku:{cardinal:O[0]},kw:{cardinal:O[19]},ky:{cardinal:O[0]},lag:{cardinal:O[21]},lb:{cardinal:O[0]},lg:{cardinal:O[0]},ln:{cardinal:O[1]},lt:{cardinal:O[22]},lv:{cardinal:O[23]},mas:{cardinal:O[0]},mg:{cardinal:O[1]},mgo:{cardinal:O[0]},mk:{cardinal:O[24],ordinal:O[46]},ml:{cardinal:O[0]},mn:{cardinal:O[0]},mo:{cardinal:O[25],ordinal:O[0]},mr:{cardinal:O[2],ordinal:O[47]},mt:{cardinal:O[26]},nah:{cardinal:O[0]},naq:{cardinal:O[19]},nb:{cardinal:O[0]},nd:{cardinal:O[0]},ne:{cardinal:O[0],ordinal:O[48]},nl:{cardinal:O[4]},nn:{cardinal:O[0]},nnh:{cardinal:O[0]},no:{cardinal:O[0]},nr:{cardinal:O[0]},nso:{cardinal:O[1]},ny:{cardinal:O[0]},nyn:{cardinal:O[0]},om:{cardinal:O[0]},or:{cardinal:O[0],ordinal:O[49]},os:{cardinal:O[0]},pa:{cardinal:O[1]},pap:{cardinal:O[0]},pl:{cardinal:O[27]},prg:{cardinal:O[23]},ps:{cardinal:O[0]},pt:{cardinal:O[28]},"pt-PT":{cardinal:O[4]},rm:{cardinal:O[0]},ro:{cardinal:O[25],ordinal:O[0]},rof:{cardinal:O[0]},ru:{cardinal:O[29]},rwk:{cardinal:O[0]},saq:{cardinal:O[0]},sc:{cardinal:O[4],ordinal:O[43]},scn:{cardinal:O[4],ordinal:O[43]},sd:{cardinal:O[0]},sdh:{cardinal:O[0]},se:{cardinal:O[19]},seh:{cardinal:O[0]},sh:{cardinal:O[7]},shi:{cardinal:O[30]},si:{cardinal:O[31]},sk:{cardinal:O[8]},sl:{cardinal:O[32]},sma:{cardinal:O[19]},smi:{cardinal:O[19]},smj:{cardinal:O[19]},smn:{cardinal:O[19]},sms:{cardinal:O[19]},sn:{cardinal:O[0]},so:{cardinal:O[0]},sq:{cardinal:O[0],ordinal:O[50]},sr:{cardinal:O[7]},ss:{cardinal:O[0]},ssy:{cardinal:O[0]},st:{cardinal:O[0]},sv:{cardinal:O[4],ordinal:O[51]},sw:{cardinal:O[4]},syr:{cardinal:O[0]},ta:{cardinal:O[0]},te:{cardinal:O[0]},teo:{cardinal:O[0]},ti:{cardinal:O[1]},tig:{cardinal:O[0]},tk:{cardinal:O[0],ordinal:O[52]},tl:{cardinal:O[13],ordinal:O[0]},tn:{cardinal:O[0]},tr:{cardinal:O[0]},ts:{cardinal:O[0]},tzm:{cardinal:O[33]},ug:{cardinal:O[0]},uk:{cardinal:O[29],ordinal:O[53]},ur:{cardinal:O[4]},uz:{cardinal:O[0]},ve:{cardinal:O[0]},vo:{cardinal:O[0]},vun:{cardinal:O[0]},wa:{cardinal:O[1]},wae:{cardinal:O[0]},xh:{cardinal:O[0]},xog:{cardinal:O[0]},yi:{cardinal:O[4]},zu:{cardinal:O[2]},lo:{ordinal:O[0]},ms:{ordinal:O[0]},vi:{ordinal:O[0]}},jc=lE(function(e,t){t=e.exports=function(m,h,f){return r(m,null,h||"en",f||{},!0)},t.toParts=function(m,h,f){return r(m,null,h||"en",f||{},!1)};function r(p,m,h,f,b){var y=p.map(function(w){return n(w,m,h,f,b)});return b?y.length===1?y[0]:function(v){for(var _="",x=0;x<y.length;++x)_+=y[x](v);return _}:function(v){return y.reduce(function(_,x){return _.concat(x(v))},[])}}function n(p,m,h,f,b){if(typeof p=="string"){var y=p;return function(){return y}}var w=p[0],v=p[1];if(m&&p[0]==="#"){w=m[0];var _=m[2],x=(f.number||d.number)([w,"number"],h);return function(T){return x(i(w,T)-_,T)}}var k;v==="plural"||v==="selectordinal"?(k={},Object.keys(p[3]).forEach(function(A){k[A]=r(p[3][A],p,h,f,b)}),p=[p[0],p[1],p[2],k]):p[2]&&typeof p[2]=="object"&&(k={},Object.keys(p[2]).forEach(function(A){k[A]=r(p[2][A],p,h,f,b)}),p=[p[0],p[1],k]);var S=v&&(f[v]||d[v]);if(S){var N=S(p,h);return function(T){return N(i(w,T),T)}}return b?function(T){return String(i(w,T))}:function(T){return i(w,T)}}function i(p,m){if(m&&p in m)return m[p];for(var h=p.split("."),f=m,b=0,y=h.length;f&&b<y;++b)f=f[h[b]];return f}function s(p,m){var h=p[2],f=Ln.number[h]||Ln.parseNumberPattern(h)||Ln.number.default;return new Intl.NumberFormat(m,f).format}function o(p,m){var h=p[2],f=Ln.duration[h]||Ln.duration.default,b=new Intl.NumberFormat(m,f.seconds).format,y=new Intl.NumberFormat(m,f.minutes).format,w=new Intl.NumberFormat(m,f.hours).format,v=/^fi$|^fi-|^da/.test(String(m))?".":":";return function(_,x){if(_=+_,!isFinite(_))return b(_);var k=~~(_/60/60),S=~~(_/60%60),N=(k?w(Math.abs(k))+v:"")+y(Math.abs(S))+v+b(Math.abs(_%60));return _<0?w(-1).replace(w(1),N):N}}function a(p,m){var h=p[1],f=p[2],b=Ln[h][f]||Ln.parseDatePattern(f)||Ln[h].default;return new Intl.DateTimeFormat(m,b).format}function l(p,m){var h=p[1],f=h==="selectordinal"?"ordinal":"cardinal",b=p[2],y=p[3],w;if(Intl.PluralRules&&Intl.PluralRules.supportedLocalesOf(m).length>0)w=new Intl.PluralRules(m,{type:f});else{var v=kM(m,qh),_=v&&qh[v][f]||u;w={select:_}}return function(x,k){var S=y["="+ +x]||y[w.select(x-b)]||y.other;return S(k)}}function u(){return"other"}function c(p,m){var h=p[2];return function(f,b){var y=h[f]||h.other;return y(b)}}var d={number:s,ordinal:s,spellout:s,duration:o,date:a,time:a,plural:l,selectordinal:l,select:c};t.types=d});jc.toParts;jc.types;var cE=lE(function(e,t){var r="{",n="}",i=",",s="#",o="<",a=">",l="</",u="/>",c="'",d="offset:",p=["number","date","time","ordinal","duration","spellout"],m=["plural","select","selectordinal"];t=e.exports=function(L,B){return h({pattern:String(L),index:0,tagsType:B&&B.tagsType||null,tokens:B&&B.tokens||null},"")};function h(g,L){var B=g.pattern,M=B.length,z=[],P=g.index,Z=f(g,L);for(Z&&z.push(Z),Z&&g.tokens&&g.tokens.push(["text",B.slice(P,g.index)]);g.index<M;){if(B[g.index]===n){if(!L)throw T(g);break}if(L&&g.tagsType&&B.slice(g.index,g.index+l.length)===l)break;z.push(w(g)),P=g.index,Z=f(g,L),Z&&z.push(Z),Z&&g.tokens&&g.tokens.push(["text",B.slice(P,g.index)])}return z}function f(g,L){for(var B=g.pattern,M=B.length,z=L==="plural"||L==="selectordinal",P=!!g.tagsType,Z=L==="{style}",be="";g.index<M;){var le=B[g.index];if(le===r||le===n||z&&le===s||P&&le===o||Z&&b(le.charCodeAt(0)))break;if(le===c)if(le=B[++g.index],le===c)be+=le,++g.index;else if(le===r||le===n||z&&le===s||P&&le===o||Z)for(be+=le;++g.index<M;)if(le=B[g.index],le===c&&B[g.index+1]===c)be+=c,++g.index;else if(le===c){++g.index;break}else be+=le;else be+=c;else be+=le,++g.index}return be}function b(g){return g>=9&&g<=13||g===32||g===133||g===160||g===6158||g>=8192&&g<=8205||g===8232||g===8233||g===8239||g===8287||g===8288||g===12288||g===65279}function y(g){for(var L=g.pattern,B=L.length,M=g.index;g.index<B&&b(L.charCodeAt(g.index));)++g.index;M<g.index&&g.tokens&&g.tokens.push(["space",g.pattern.slice(M,g.index)])}function w(g){var L=g.pattern;if(L[g.index]===s)return g.tokens&&g.tokens.push(["syntax",s]),++g.index,[s];var B=v(g);if(B)return B;if(L[g.index]!==r)throw T(g,r);g.tokens&&g.tokens.push(["syntax",r]),++g.index,y(g);var M=_(g);if(!M)throw T(g,"placeholder id");g.tokens&&g.tokens.push(["id",M]),y(g);var z=L[g.index];if(z===n)return g.tokens&&g.tokens.push(["syntax",n]),++g.index,[M];if(z!==i)throw T(g,i+" or "+n);g.tokens&&g.tokens.push(["syntax",i]),++g.index,y(g);var P=_(g);if(!P)throw T(g,"placeholder type");if(g.tokens&&g.tokens.push(["type",P]),y(g),z=L[g.index],z===n){if(g.tokens&&g.tokens.push(["syntax",n]),P==="plural"||P==="selectordinal"||P==="select")throw T(g,P+" sub-messages");return++g.index,[M,P]}if(z!==i)throw T(g,i+" or "+n);g.tokens&&g.tokens.push(["syntax",i]),++g.index,y(g);var Z;if(P==="plural"||P==="selectordinal"){var be=k(g);y(g),Z=[M,P,be,N(g,P)]}else if(P==="select")Z=[M,P,N(g,P)];else if(p.indexOf(P)>=0)Z=[M,P,x(g)];else{var le=g.index,qe=x(g);y(g),L[g.index]===r&&(g.index=le,qe=N(g,P)),Z=[M,P,qe]}if(y(g),L[g.index]!==n)throw T(g,n);return g.tokens&&g.tokens.push(["syntax",n]),++g.index,Z}function v(g){var L=g.tagsType;if(!(!L||g.pattern[g.index]!==o)){if(g.pattern.slice(g.index,g.index+l.length)===l)throw T(g,null,"closing tag without matching opening tag");g.tokens&&g.tokens.push(["syntax",o]),++g.index;var B=_(g,!0);if(!B)throw T(g,"placeholder id");if(g.tokens&&g.tokens.push(["id",B]),y(g),g.pattern.slice(g.index,g.index+u.length)===u)return g.tokens&&g.tokens.push(["syntax",u]),g.index+=u.length,[B,L];if(g.pattern[g.index]!==a)throw T(g,a);g.tokens&&g.tokens.push(["syntax",a]),++g.index;var M=h(g,L),z=g.index;if(g.pattern.slice(g.index,g.index+l.length)!==l)throw T(g,l+B+a);g.tokens&&g.tokens.push(["syntax",l]),g.index+=l.length;var P=_(g,!0);if(P&&g.tokens&&g.tokens.push(["id",P]),B!==P)throw g.index=z,T(g,l+B+a,l+P+a);if(y(g),g.pattern[g.index]!==a)throw T(g,a);return g.tokens&&g.tokens.push(["syntax",a]),++g.index,[B,L,{children:M}]}}function _(g,L){for(var B=g.pattern,M=B.length,z="";g.index<M;){var P=B[g.index];if(P===r||P===n||P===i||P===s||P===c||b(P.charCodeAt(0))||L&&(P===o||P===a||P==="/"))break;z+=P,++g.index}return z}function x(g){var L=g.index,B=f(g,"{style}");if(!B)throw T(g,"placeholder style name");return g.tokens&&g.tokens.push(["style",g.pattern.slice(L,g.index)]),B}function k(g){var L=g.pattern,B=L.length,M=0;if(L.slice(g.index,g.index+d.length)===d){g.tokens&&g.tokens.push(["offset","offset"],["syntax",":"]),g.index+=d.length,y(g);for(var z=g.index;g.index<B&&S(L.charCodeAt(g.index));)++g.index;if(z===g.index)throw T(g,"offset number");g.tokens&&g.tokens.push(["number",L.slice(z,g.index)]),M=+L.slice(z,g.index)}return M}function S(g){return g>=48&&g<=57}function N(g,L){for(var B=g.pattern,M=B.length,z={};g.index<M&&B[g.index]!==n;){var P=_(g);if(!P)throw T(g,"sub-message selector");g.tokens&&g.tokens.push(["selector",P]),y(g),z[P]=A(g,L),y(g)}if(!z.other&&m.indexOf(L)>=0)throw T(g,null,null,'"other" sub-message must be specified in '+L);return z}function A(g,L){if(g.pattern[g.index]!==r)throw T(g,r+" to start sub-message");g.tokens&&g.tokens.push(["syntax",r]),++g.index;var B=h(g,L);if(g.pattern[g.index]!==n)throw T(g,n+" to end sub-message");return g.tokens&&g.tokens.push(["syntax",n]),++g.index,B}function T(g,L,B,M){var z=g.pattern,P=z.slice(0,g.index).split(/\r?\n/),Z=g.index,be=P.length,le=P.slice(-1)[0].length;return B=B||(g.index>=z.length?"end of message pattern":_(g)||z[g.index]),M||(M=I(L,B)),M+=" in "+z.replace(/\r?\n/g,`
`),new C(M,L,B,Z,be,le)}function I(g,L){return g?"Expected "+g+" but found "+L:"Unexpected "+L+" found"}function C(g,L,B,M,z,P){Error.call(this,g),this.name="SyntaxError",this.message=g,this.expected=L,this.found=B,this.offset=M,this.line=z,this.column=P}C.prototype=Object.create(Error.prototype),t.SyntaxError=C});cE.SyntaxError;var OM=new RegExp("^("+Object.keys(qh).join("|")+")\\b"),vo=new WeakMap;function ys(e,t,r){if(!(this instanceof ys)||vo.has(this))throw new TypeError("calling MessageFormat constructor without new is invalid");var n=cE(e);vo.set(this,{ast:n,format:jc(n,t,r&&r.types),locale:ys.supportedLocalesOf(t)[0]||"en",locales:t,options:r})}var NM=ys;Object.defineProperties(ys.prototype,{format:{configurable:!0,get:function(){var t=vo.get(this);if(!t)throw new TypeError("MessageFormat.prototype.format called on value that's not an object initialized as a MessageFormat");return t.format}},formatToParts:{configurable:!0,writable:!0,value:function(t){var r=vo.get(this);if(!r)throw new TypeError("MessageFormat.prototype.formatToParts called on value that's not an object initialized as a MessageFormat");var n=r.toParts||(r.toParts=jc.toParts(r.ast,r.locales,r.options&&r.options.types));return n(t)}},resolvedOptions:{configurable:!0,writable:!0,value:function(){var t=vo.get(this);if(!t)throw new TypeError("MessageFormat.prototype.resolvedOptions called on value that's not an object initialized as a MessageFormat");return{locale:t.locale}}}});typeof Symbol<"u"&&Object.defineProperty(ys.prototype,Symbol.toStringTag,{value:"Object"});Object.defineProperties(ys,{supportedLocalesOf:{configurable:!0,writable:!0,value:function(t){return[].concat(Intl.NumberFormat.supportedLocalesOf(t),Intl.DateTimeFormat.supportedLocalesOf(t),Intl.PluralRules?Intl.PluralRules.supportedLocalesOf(t):[],[].concat(t||[]).filter(function(r){return OM.test(r)})).filter(function(r,n,i){return i.indexOf(r)===n})}}});function PM(e){return!!(e&&e.default&&typeof e.default=="object"&&Object.keys(e).length===1)}const Rn=globalThis.document?.documentElement;class IM extends EventTarget{formatNumberOptions={returnIfNaN:"",postProcessors:new Map};formatDateOptions={postProcessors:new Map};#e=!1;#t="";#r=null;__storage={};__namespacePatternsMap=new Map;__namespaceLoadersCache={};__namespaceLoaderPromisesCache={};get locale(){return this.#e?this.#t||"":Rn.lang||""}set locale(t){if(this.#n(t),!this.#e){const i=Rn.lang;this._setHtmlLangAttribute(t),this._onLocaleChanged(t,i);return}const r=this.#t;this.#t=t,this.#r===null&&this._setHtmlLangAttribute(t),this._onLocaleChanged(t,r)}get loadingComplete(){return typeof this.__namespaceLoaderPromisesCache[this.locale]=="object"?Promise.all(Object.values(this.__namespaceLoaderPromisesCache[this.locale])):Promise.resolve()}constructor({allowOverridesForExistingNamespaces:t=!1,autoLoadOnLocaleChange:r=!1,showKeyAsFallback:n=!1,fallbackLocale:i=""}={}){super(),this.__allowOverridesForExistingNamespaces=t,this._autoLoadOnLocaleChange=!!r,this._showKeyAsFallback=n,this._fallbackLocale=i;const s=Rn.getAttribute("data-localize-lang");this.#e=!!s,this.#e&&(this.locale=s,this._setupTranslationToolSupport()),Rn.lang||(Rn.lang=this.locale||"en-GB"),this._setupHtmlLangAttributeObserver()}addData(t,r,n){if(!this.__allowOverridesForExistingNamespaces&&this._isNamespaceInCache(t,r))throw new Error(`Namespace "${r}" has been already added for the locale "${t}".`);this.__storage[t]=this.__storage[t]||{},this.__allowOverridesForExistingNamespaces?this.__storage[t][r]={...this.__storage[t][r],...n}:this.__storage[t][r]=n}setupNamespaceLoader(t,r){this.__namespacePatternsMap.set(t,r)}loadNamespaces(t,{locale:r}={}){return Promise.all(t.map(n=>this.loadNamespace(n,{locale:r})))}loadNamespace(t,{locale:r=this.locale}={locale:this.locale}){const n=typeof t=="object",i=n?Object.keys(t)[0]:t;if(this._isNamespaceInCache(r,i))return Promise.resolve();const s=this._getCachedNamespaceLoaderPromise(r,i);return s||this._loadNamespaceData(r,t,n,i)}msg(t,r,n={}){const i=n.locale?n.locale:this.locale,s=this._getMessageForKeys(t,i);return s?new NM(s,i).format(r):""}teardown(){this._teardownHtmlLangAttributeObserver()}reset(){this.__storage={},this.__namespacePatternsMap=new Map,this.__namespaceLoadersCache={},this.__namespaceLoaderPromisesCache={}}setDatePostProcessorForLocale({locale:t,postProcessor:r}){this.formatDateOptions?.postProcessors.set(t,r)}setNumberPostProcessorForLocale({locale:t,postProcessor:r}){this.formatNumberOptions?.postProcessors.set(t,r)}_setupTranslationToolSupport(){this.#r=Rn.lang||null}_setHtmlLangAttribute(t){this._teardownHtmlLangAttributeObserver(),Rn.lang=t,this._setupHtmlLangAttributeObserver()}_setupHtmlLangAttributeObserver(){this._htmlLangAttributeObserver||(this._htmlLangAttributeObserver=new MutationObserver(t=>{t.forEach(r=>{this.#e?Rn.lang==="auto"?(this.#r=null,this._setHtmlLangAttribute(this.locale)):this.#r=document.documentElement.lang:this._onLocaleChanged(document.documentElement.lang,r.oldValue||"")})})),this._htmlLangAttributeObserver.observe(document.documentElement,{attributes:!0,attributeFilter:["lang"],attributeOldValue:!0})}_teardownHtmlLangAttributeObserver(){this._htmlLangAttributeObserver&&this._htmlLangAttributeObserver.disconnect()}_isNamespaceInCache(t,r){return!!(this.__storage[t]&&this.__storage[t][r])}_getCachedNamespaceLoaderPromise(t,r){return this.__namespaceLoaderPromisesCache[t]?this.__namespaceLoaderPromisesCache[t][r]:null}_loadNamespaceData(t,r,n,i){const s=this._getNamespaceLoader(r,n,i),o=this._getNamespaceLoaderPromise(s,t,i);return this._cacheNamespaceLoaderPromise(t,i,o),o.then(a=>{if(this.__namespaceLoaderPromisesCache[t]&&this.__namespaceLoaderPromisesCache[t][i]===o){const l=PM(a)?a.default:a;this.addData(t,i,l)}})}_getNamespaceLoader(t,r,n){let i=this.__namespaceLoadersCache[n];if(i||(r?(i=t[n],this.__namespaceLoadersCache[n]=i):(i=this._lookupNamespaceLoader(n),this.__namespaceLoadersCache[n]=i)),!i)throw new Error(`Namespace "${n}" was not properly setup.`);return this.__namespaceLoadersCache[n]=i,i}_getNamespaceLoaderPromise(t,r,n,i=this._fallbackLocale){return t(r,n).catch(()=>{const s=this._getLangFromLocale(r);return t(s,n).catch(()=>{if(i)return this._getNamespaceLoaderPromise(t,i,n,"").catch(()=>{const o=this._getLangFromLocale(i);throw new Error(`Data for namespace "${n}" and current locale "${r}" or fallback locale "${i}" could not be loaded. Make sure you have data either for locale "${r}" (and/or generic language "${s}") or for fallback "${i}" (and/or "${o}").`)});throw new Error(`Data for namespace "${n}" and locale "${r}" could not be loaded. Make sure you have data for locale "${r}" (and/or generic language "${s}").`)})})}_cacheNamespaceLoaderPromise(t,r,n){this.__namespaceLoaderPromisesCache[t]||(this.__namespaceLoaderPromisesCache[t]={}),this.__namespaceLoaderPromisesCache[t][r]=n}_lookupNamespaceLoader(t){for(const[r,n]of this.__namespacePatternsMap){const i=typeof r=="string"&&r===t,s=typeof r=="object"&&r.constructor.name==="RegExp"&&r.test(t);if(i||s)return n}return null}_getLangFromLocale(t){return t.substring(0,2)}_onLocaleChanged(t,r){this.dispatchEvent(new CustomEvent("__localeChanging")),t!==r&&(this._autoLoadOnLocaleChange?(this._loadAllMissing(t,r),this.loadingComplete.then(()=>{this.dispatchEvent(new CustomEvent("localeChanged",{detail:{newLocale:t,oldLocale:r}}))})):this.dispatchEvent(new CustomEvent("localeChanged",{detail:{newLocale:t,oldLocale:r}})))}_loadAllMissing(t,r){const n=this.__storage[r]||{},i=this.__storage[t]||{};Object.keys(n).forEach(s=>{i[s]||this.loadNamespace(s,{locale:t})})}_getMessageForKeys(t,r){if(typeof t=="string")return this._getMessageForKey(t,r);const n=Array.from(t).reverse();let i,s;for(;n.length;)if(i=n.pop(),s=this._getMessageForKey(i,r),s)return s}_getMessageForKey(t,r){if(!t||t.indexOf(":")===-1)throw new Error(`Namespace is missing in the key "${t}". The format for keys is "namespace:name".`);const[n,i]=t.split(":"),s=this.__storage[r],o=s?s[n]:{},l=i.split(".").reduce((u,c)=>typeof u=="object"?u[c]:u,o);return String(l||(this._showKeyAsFallback?t:""))}#n(t){if(!t.includes("-"))throw new Error(`
      Locale was set to ${t}.
      Language only locales are not allowed, please use the full language locale e.g. 'en-GB' instead of 'en'.
      See https://github.com/ing-bank/lion/issues/187 for more information.
    `)}get _supportExternalTranslationTools(){return this.#e}set _supportExternalTranslationTools(t){this.#e=t}get _langAttrSetByTranslationTool(){return this.#t}set _langAttrSetByTranslationTool(t){this.#t=t}}const Pd=Symbol.for("lion::SingletonManagerClassStorage"),Id=globalThis||window;class FM{constructor(){this._map=Id[Pd]?Id[Pd]:Id[Pd]=new Map}set(t,r){this.has(t)||this._map.set(t,r)}get(t){return this._map.get(t)}has(t){return this._map.has(t)}}const sl=new FM;function Xl(){if(sl.has("@lion/ui::localize::0.x"))return sl.get("@lion/ui::localize::0.x");const e=new IM({autoLoadOnLocaleChange:!0,fallbackLocale:"en-GB"});return sl.set("@lion/ui::localize::0.x",e),e}const yo=(e,t)=>{const r=e._$AN;if(r===void 0)return!1;for(const n of r)n._$AO?.(t,!1),yo(n,t);return!0},Jl=e=>{let t,r;do{if((t=e._$AM)===void 0)break;r=t._$AN,r.delete(e),e=t}while(r?.size===0)},uE=e=>{for(let t;t=e._$AM;e=t){let r=t._$AN;if(r===void 0)t._$AN=r=new Set;else if(r.has(e))break;r.add(e),MM(t)}};function LM(e){this._$AN!==void 0?(Jl(this),this._$AM=e,uE(this)):this._$AM=e}function RM(e,t=!1,r=0){const n=this._$AH,i=this._$AN;if(i!==void 0&&i.size!==0)if(t)if(Array.isArray(n))for(let s=r;s<n.length;s++)yo(n[s],!1),Jl(n[s]);else n!=null&&(yo(n,!1),Jl(n));else yo(this,e)}const MM=e=>{e.type==xv.CHILD&&(e._$AP??=RM,e._$AQ??=LM)};class DM extends Ev{constructor(){super(...arguments),this._$AN=void 0}_$AT(t,r,n){super._$AT(t,r,n),uE(this),this.isConnected=t._$AU}_$AO(t,r=!0){t!==this.isConnected&&(this.isConnected=t,t?this.reconnected?.():this.disconnected?.()),r&&(yo(this,t),Jl(this))}setValue(t){if(fM(this._$Ct))this._$Ct._$AI(t,this);else{const r=[...this._$Ct._$AH];r[this._$Ci]=t,this._$Ct._$AI(r,this,0)}}disconnected(){}reconnected(){}}class $M{constructor(t){this.G=t}disconnect(){this.G=void 0}reconnect(t){this.G=t}deref(){return this.G}}let VM=class{constructor(){this.Y=void 0,this.Z=void 0}get(){return this.Y}pause(){this.Y??=new Promise((t=>this.Z=t))}resume(){this.Z?.(),this.Y=this.Z=void 0}};const Qb=e=>!hM(e)&&typeof e.then=="function",Zb=1073741823;let BM=class extends DM{constructor(){super(...arguments),this._$Cwt=Zb,this._$Cbt=[],this._$CK=new $M(this),this._$CX=new VM}render(...t){return t.find((r=>!Qb(r)))??Wd}update(t,r){const n=this._$Cbt;let i=n.length;this._$Cbt=r;const s=this._$CK,o=this._$CX;this.isConnected||this.disconnected();for(let a=0;a<r.length&&!(a>this._$Cwt);a++){const l=r[a];if(!Qb(l))return this._$Cwt=a,l;a<i&&l===n[a]||(this._$Cwt=Zb,i=0,Promise.resolve(l).then((async u=>{for(;o.get();)await o.get();const c=s.deref();if(c!==void 0){const d=c._$Cbt.indexOf(l);d>-1&&d<c._$Cwt&&(c._$Cwt=d,c.setValue(u))}})))}return Wd}disconnected(){this._$CK.disconnect(),this._$CX.pause()}reconnected(){this._$CK.reconnect(this),this._$CX.resume()}};const UM=Sv(BM),zM=e=>class extends e{static get localizeNamespaces(){return[]}static get waitForLocalizeNamespaces(){return!0}constructor(){super(),this._localizeManager=Xl(),this.__boundLocalizeOnLocaleChanged=(...r)=>{const n=Array.from(r)[0];this.__localizeOnLocaleChanged(n)},this.__boundLocalizeOnLocaleChanging=()=>{this.__localizeOnLocaleChanging()},this.__localizeStartLoadingNamespaces(),this.localizeNamespacesLoaded&&this.localizeNamespacesLoaded.then(()=>{this.__localizeMessageSync=!0})}async scheduleUpdate(){Object.getPrototypeOf(this).constructor.waitForLocalizeNamespaces&&await this.localizeNamespacesLoaded,super.scheduleUpdate()}connectedCallback(){super.connectedCallback(),this.localizeNamespacesLoaded&&this.localizeNamespacesLoaded.then(()=>this.onLocaleReady()),this._localizeManager.addEventListener("__localeChanging",this.__boundLocalizeOnLocaleChanging),this._localizeManager.addEventListener("localeChanged",this.__boundLocalizeOnLocaleChanged)}disconnectedCallback(){super.disconnectedCallback(),this._localizeManager.removeEventListener("__localeChanging",this.__boundLocalizeOnLocaleChanging),this._localizeManager.removeEventListener("localeChanged",this.__boundLocalizeOnLocaleChanged)}msgLit(r,n,i){return this.__localizeMessageSync?this._localizeManager.msg(r,n,i):this.localizeNamespacesLoaded?UM(this.localizeNamespacesLoaded.then(()=>this._localizeManager.msg(r,n,i)),ht):""}__getUniqueNamespaces(){const r=[],n=new Set;return Object.getPrototypeOf(this).constructor.localizeNamespaces.forEach(n.add.bind(n)),n.forEach(i=>{r.push(i)}),r}__localizeStartLoadingNamespaces(){this.localizeNamespacesLoaded=this._localizeManager.loadNamespaces(this.__getUniqueNamespaces())}__localizeOnLocaleChanging(){this.__localizeStartLoadingNamespaces()}__localizeOnLocaleChanged(r){this.onLocaleChanged(r.detail.newLocale,r.detail.oldLocale)}onLocaleReady(){this.onLocaleUpdated()}onLocaleChanged(r,n){this.onLocaleUpdated(),this.requestUpdate()}onLocaleUpdated(){}},Wc=He(zM),jh="3.0.0",ev=window.scopedElementsVersions||(window.scopedElementsVersions=[]);ev.includes(jh)||ev.push(jh);const HM=e=>class extends e{static scopedElements;static get scopedElementsVersion(){return jh}static __registry;get registry(){return this.constructor.__registry}set registry(r){this.constructor.__registry=r}attachShadow(r){const{scopedElements:n}=this.constructor;if(!this.registry||this.registry===this.constructor.__registry&&!Object.prototype.hasOwnProperty.call(this.constructor,"__registry")){this.registry=new CustomElementRegistry;for(const[s,o]of Object.entries(n??{}))this.registry.define(s,o)}return super.attachShadow({...r,customElements:this.registry,registry:this.registry})}},qM=He(HM),jM=e=>class extends qM(e){createRenderRoot(){const{shadowRootOptions:r,elementStyles:n}=this.constructor,i=this.attachShadow(r);return this.renderOptions.creationScope=i,wv(i,n),this.renderOptions.renderBefore??=i.firstChild,i}},WM=He(jM);function Ua(){return!!(globalThis.ShadowRoot?.prototype.createElement&&globalThis.ShadowRoot?.prototype.importNode)}const KM=e=>class extends WM(e){constructor(){super()}createScopedElement(r){return(Ua()?this.shadowRoot:document).createElement(r)}defineScopedElement(r,n){const i=this.registry.get(r),s=i&&i!==n;return!Ua()&&s&&console.error([`You are trying to re-register the "${r}" custom element with a different class via ScopedElementsMixin.`,"This is only possible with a CustomElementRegistry.","Your browser does not support this feature so you will need to load a polyfill for it.",'Load "@webcomponents/scoped-custom-element-registry" before you register ANY web component to the global customElements registry.','e.g. add "<script src="/node_modules/@webcomponents/scoped-custom-element-registry/scoped-custom-element-registry.min.js"><\/script>" as your first script tag.',"For more details you can visit https://open-wc.org/docs/development/scoped-elements/"].join(`
`)),i?this.registry.get(r):this.registry.define(r,n)}attachShadow(r){const{scopedElements:n}=this.constructor;if(!this.registry||this.registry===this.constructor.__registry&&!Object.prototype.hasOwnProperty.call(this.constructor,"__registry")){this.registry=Ua()?new CustomElementRegistry:customElements;for(const[s,o]of Object.entries(n??{}))this.defineScopedElement(s,o)}return Element.prototype.attachShadow.call(this,{...r,customElements:this.registry,registry:this.registry})}createRenderRoot(){const{shadowRootOptions:r,elementStyles:n}=this.constructor,i=this.attachShadow(r);return Ua()&&(this.renderOptions.creationScope=i),i instanceof ShadowRoot&&(wv(i,n),this.renderOptions.renderBefore=this.renderOptions.renderBefore||i.firstChild),i}},ma=He(KM);class GM{constructor(){this.__running=!1,this.__queue=[]}add(t){this.__queue.push(t),this.__running||(this.complete=new Promise(r=>{this.__callComplete=r}),this.__run())}async __run(){this.__running=!0,await this.__queue[0](),this.__queue.shift(),this.__queue.length>0?this.__run():(this.__running=!1,this.__callComplete&&this.__callComplete())}}function YM(e){return e.charAt(0).toUpperCase()+e.slice(1)}const XM=e=>class extends e{constructor(){super(),this.__SyncUpdatableNamespace={}}firstUpdated(t){super.firstUpdated(t),this.__syncUpdatableInitialize()}connectedCallback(){super.connectedCallback(),this.__SyncUpdatableNamespace.connected=!0}disconnectedCallback(){super.disconnectedCallback(),this.__SyncUpdatableNamespace.connected=!1}static enabledWarnings=super.enabledWarnings?.filter(t=>t!=="change-in-update")||[];static __syncUpdatableHasChanged(t,r,n){const i=this.elementProperties;return i.get(t)&&i.get(t).hasChanged?i.get(t).hasChanged(r,n):r!==n}__syncUpdatableInitialize(){const t=this.__SyncUpdatableNamespace,r=this.constructor;t.initialized=!0,t.queue&&Array.from(t.queue).forEach(n=>{r.__syncUpdatableHasChanged(n,this[n],void 0)&&this.updateSync(n,void 0)})}requestUpdate(t,r,n){if(super.requestUpdate(t,r,n),t===void 0)return;this.__SyncUpdatableNamespace=this.__SyncUpdatableNamespace||{};const i=this.__SyncUpdatableNamespace,s=this.constructor;i.initialized?s.__syncUpdatableHasChanged(t,this[t],r)&&this.updateSync(t,r):(i.queue=i.queue||new Set,i.queue.add(t))}updateSync(t,r){}},JM=He(XM),QM=e=>{switch(e){case"bg-BG":return Y(()=>import("./bg-BG2.js"),__vite__mapDeps([0,1]),import.meta.url);case"bg":return Y(()=>import("./bg3.js"),[],import.meta.url);case"cs-CZ":return Y(()=>import("./cs-CZ2.js"),__vite__mapDeps([2,3]),import.meta.url);case"cs":return Y(()=>import("./cs3.js"),[],import.meta.url);case"de-DE":return Y(()=>import("./de-DE2.js"),__vite__mapDeps([4,5]),import.meta.url);case"de":return Y(()=>import("./de3.js"),[],import.meta.url);case"en-AU":return Y(()=>import("./en-AU2.js"),__vite__mapDeps([6,7]),import.meta.url);case"en-GB":return Y(()=>import("./en-GB2.js"),__vite__mapDeps([8,7]),import.meta.url);case"en-US":return Y(()=>import("./en-US2.js"),__vite__mapDeps([9,7]),import.meta.url);case"en-PH":case"en":return Y(()=>import("./en3.js"),[],import.meta.url);case"es-ES":return Y(()=>import("./es-ES2.js"),__vite__mapDeps([10,11]),import.meta.url);case"es":return Y(()=>import("./es3.js"),[],import.meta.url);case"fr-FR":return Y(()=>import("./fr-FR2.js"),__vite__mapDeps([12,13]),import.meta.url);case"fr-BE":return Y(()=>import("./fr-BE2.js"),__vite__mapDeps([14,13]),import.meta.url);case"fr":return Y(()=>import("./fr3.js"),[],import.meta.url);case"hu-HU":return Y(()=>import("./hu-HU2.js"),__vite__mapDeps([15,16]),import.meta.url);case"hu":return Y(()=>import("./hu3.js"),[],import.meta.url);case"it-IT":return Y(()=>import("./it-IT2.js"),__vite__mapDeps([17,18]),import.meta.url);case"it":return Y(()=>import("./it3.js"),[],import.meta.url);case"nl-BE":return Y(()=>import("./nl-BE2.js"),__vite__mapDeps([19,20]),import.meta.url);case"nl-NL":return Y(()=>import("./nl-NL2.js"),__vite__mapDeps([21,20]),import.meta.url);case"nl":return Y(()=>import("./nl3.js"),[],import.meta.url);case"pl-PL":return Y(()=>import("./pl-PL2.js"),__vite__mapDeps([22,23]),import.meta.url);case"pl":return Y(()=>import("./pl3.js"),[],import.meta.url);case"ro-RO":return Y(()=>import("./ro-RO2.js"),__vite__mapDeps([24,25]),import.meta.url);case"ro":return Y(()=>import("./ro3.js"),[],import.meta.url);case"ru-RU":return Y(()=>import("./ru-RU2.js"),__vite__mapDeps([26,27]),import.meta.url);case"ru":return Y(()=>import("./ru3.js"),[],import.meta.url);case"sk-SK":return Y(()=>import("./sk-SK2.js"),__vite__mapDeps([28,29]),import.meta.url);case"sk":return Y(()=>import("./sk3.js"),[],import.meta.url);case"tr-TR":return Y(()=>import("./tr-TR.js"),__vite__mapDeps([30,31]),import.meta.url);case"tr":return Y(()=>import("./tr.js"),[],import.meta.url);case"uk-UA":return Y(()=>import("./uk-UA2.js"),__vite__mapDeps([32,33]),import.meta.url);case"uk":return Y(()=>import("./uk3.js"),[],import.meta.url);case"zh-CN":case"zh":return Y(()=>import("./zh3.js"),[],import.meta.url);default:return Y(()=>import("./en3.js"),[],import.meta.url)}},ZM=e=>`${e[0].toUpperCase()}${e.slice(1)}`;class dE extends Wc(Ne){static get properties(){return{feedbackData:{attribute:!1}}}static localizeNamespaces=[{"lion-form-core":QM},...super.localizeNamespaces];static get styles(){return[te`
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
      `]}constructor(){super(),this.feedbackData=void 0}_messageTemplate({message:t}){return t}updated(t){super.updated(t),this.feedbackData&&this.feedbackData[0]?(this.setAttribute("type",this.feedbackData[0].type),this.currentType=this.feedbackData[0].type):this.currentType!=="success"&&this.removeAttribute("type")}render(){return W`
      ${this.feedbackData&&this.feedbackData.map(({message:t,type:r,validator:n})=>W`
          <div class="validation-feedback__type">
            ${t&&r?this._localizeManager.msg(`lion-form-core:validation${ZM(r)}`):ht}
          </div>
          ${this._messageTemplate({message:t,type:r,validator:n})}
        `)}
    `}}class Li{constructor(t){this.type="unparseable",this.viewValue=t}toString(){return JSON.stringify({type:this.type,viewValue:this.viewValue})}}const eD=[Node.DOCUMENT_POSITION_PRECEDING,Node.DOCUMENT_POSITION_CONTAINS,Node.DOCUMENT_POSITION_CONTAINS|Node.DOCUMENT_POSITION_PRECEDING];function hE(e,{reverse:t}={}){const r=(i,s)=>{const o=i.compareDocumentPosition(s);return eD.includes(o)?1:-1},n=e.filter(i=>i);return n.sort(r),t&&n.reverse(),n}const tD=e=>class extends e{constructor(){super(),this.name="",this._parentFormGroup=void 0,this.allowCrossRootRegistration=!1}get name(){return this.__name||""}set name(t){const r=this.name;this.__name=t.toString(),this.requestUpdate("name",r)}static get properties(){return{name:{type:String,reflect:!0},allowCrossRootRegistration:{type:Boolean,attribute:"allow-cross-root-registration"}}}connectedCallback(){super.connectedCallback(),this.dispatchEvent(new CustomEvent("form-element-register",{detail:{element:this},bubbles:!0,composed:!!this.allowCrossRootRegistration}))}disconnectedCallback(){super.disconnectedCallback(),this.__unregisterFormElement()}__unregisterFormElement(){this._parentFormGroup&&this._parentFormGroup.removeFormElement(this)}},Np=He(tD),rD=e=>class extends Np(ha(Is(e))){static get properties(){return{readOnly:{type:Boolean,attribute:"readonly",reflect:!0},label:String,labelSrOnly:{type:Boolean,attribute:"label-sr-only",reflect:!0},helpText:{type:String,attribute:"help-text"},modelValue:{attribute:!1},_ariaLabelledNodes:{attribute:!1},_ariaDescribedNodes:{attribute:!1},_repropagationRole:{attribute:!1},_isRepropagationEndpoint:{attribute:!1}}}get label(){return this.__label??(this._labelNode?.textContent||"")}set label(r){const n=this.label;this.__label=r,this.requestUpdate("label",n)}get helpText(){return this.__helpText??(this._helpTextNode?.textContent||"")}set helpText(r){const n=this.helpText;this.__helpText=r,this.requestUpdate("helpText",n)}get fieldName(){return this.__fieldName||this.label||this.name||""}set fieldName(r){this.__fieldName=r}get slots(){return{...super.slots,label:()=>{const r=document.createElement("label");return r.textContent=this.label,r},"help-text":()=>{const r=document.createElement("div");return r.textContent=this.helpText,r}}}get _inputNode(){return this.__getDirectSlotChild("input")}get _labelNode(){return this.__getDirectSlotChild("label")}get _helpTextNode(){return this.__getDirectSlotChild("help-text")}get _feedbackNode(){return this.__getDirectSlotChild("feedback")}static enabledWarnings=super.enabledWarnings?.filter(r=>r!=="change-in-update")||[];constructor(){super(),this.readOnly=!1,this.labelSrOnly=!1,this._inputId=fa(this.localName),this._ariaLabelledNodes=[],this._ariaDescribedNodes=[],this._repropagationRole="child",this._isRepropagationEndpoint=!1,this.addEventListener("model-value-changed",this.__repropagateChildrenValues),this._onLabelClick=this._onLabelClick.bind(this)}connectedCallback(){super.connectedCallback(),this._enhanceLightDomClasses(),this._enhanceLightDomA11y(),this._triggerInitialModelValueChangedEvent(),this._labelNode&&this._labelNode.addEventListener("click",this._onLabelClick)}disconnectedCallback(){super.disconnectedCallback(),this._labelNode&&this._labelNode.removeEventListener("click",this._onLabelClick)}updated(r){super.updated(r),r.has("disabled")&&this._inputNode?.setAttribute("aria-disabled",`${!!this.disabled}`),r.has("_ariaLabelledNodes")&&this.__reflectAriaAttr("aria-labelledby",this._ariaLabelledNodes,this.__reorderAriaLabelledNodes),r.has("_ariaDescribedNodes")&&this.__reflectAriaAttr("aria-describedby",this._ariaDescribedNodes,this.__reorderAriaDescribedNodes),r.has("label")&&this.__label!==void 0&&this._labelNode&&(this._labelNode.textContent=this.label),r.has("helpText")&&this.__helpText!==void 0&&this._helpTextNode&&(this._helpTextNode.textContent=this.helpText),r.has("name")&&this.dispatchEvent(new CustomEvent("form-element-name-changed",{detail:{oldName:r.get("name"),newName:this.name},bubbles:!0}))}_triggerInitialModelValueChangedEvent(){this._dispatchInitialModelValueChangedEvent()}_enhanceLightDomClasses(){this._inputNode&&this._inputNode.classList.add("form-control")}_enhanceLightDomA11y(){const{_inputNode:r,_labelNode:n,_helpTextNode:i,_feedbackNode:s}=this;r&&(r.id=r.id||this._inputId),n&&(n.setAttribute("for",this._inputId),this.addToAriaLabelledBy(n,{idPrefix:"label"})),i&&this.addToAriaDescribedBy(i,{idPrefix:"help-text"}),s&&(this.addEventListener("focusin",()=>{s.setAttribute("aria-live","polite")}),this.addEventListener("focusout",()=>{s.setAttribute("aria-live","assertive")}),this.addToAriaDescribedBy(s,{idPrefix:"feedback"})),this._enhanceLightDomA11yForAdditionalSlots()}_enhanceLightDomA11yForAdditionalSlots(r=["prefix","suffix","before","after"]){r.forEach(n=>{const i=this.__getDirectSlotChild(n);i&&(i.hasAttribute("data-label")&&this.addToAriaLabelledBy(i,{idPrefix:n}),i.hasAttribute("data-description")&&this.addToAriaDescribedBy(i,{idPrefix:n}))})}__reflectAriaAttr(r,n,i){if(this._inputNode){if(i){const o=n.filter(d=>this.contains(d)),a=n.filter(d=>!this.contains(d)),l=o.map(d=>d.assignedSlot||d),u=[...hE(l)],c=[];u.forEach(d=>{o.forEach(p=>{d.name===p.slot&&c.push(p)})}),n=[...c,...a]}const s=n.map(o=>o.id).join(" ");this._inputNode.setAttribute(r,s)}}render(){return W`
        <div class="form-field__group-one">${this._groupOneTemplate()}</div>
        <div class="form-field__group-two">${this._groupTwoTemplate()}</div>
      `}_groupOneTemplate(){return W` ${this._labelTemplate()} ${this._helpTextTemplate()} `}_groupTwoTemplate(){return W` ${this._inputGroupTemplate()} ${this._feedbackTemplate()} `}_labelTemplate(){return W`
        <div class="form-field__label">
          <slot name="label"></slot>
        </div>
      `}_helpTextTemplate(){return W`
        <small class="form-field__help-text">
          <slot name="help-text"></slot>
        </small>
      `}_inputGroupTemplate(){return W`
        <div class="input-group">
          ${this._inputGroupBeforeTemplate()}
          <div class="input-group__container">
            ${this._inputGroupPrefixTemplate()} ${this._inputGroupInputTemplate()}
            ${this._inputGroupSuffixTemplate()}
          </div>
          ${this._inputGroupAfterTemplate()}
        </div>
      `}_inputGroupBeforeTemplate(){return W`
        <div class="input-group__before">
          <slot name="before"></slot>
        </div>
      `}_inputGroupPrefixTemplate(){return Array.from(this.children).find(r=>r.slot==="prefix")?W`
            <div class="input-group__prefix">
              <slot name="prefix"></slot>
            </div>
          `:ht}_inputGroupInputTemplate(){return W`
        <div class="input-group__input">
          <slot name="input"></slot>
        </div>
      `}_inputGroupSuffixTemplate(){return Array.from(this.children).find(r=>r.slot==="suffix")?W`
            <div class="input-group__suffix">
              <slot name="suffix"></slot>
            </div>
          `:ht}_inputGroupAfterTemplate(){return W`
        <div class="input-group__after">
          <slot name="after"></slot>
        </div>
      `}_feedbackTemplate(){return W`
        <div class="form-field__feedback">
          <slot name="feedback"></slot>
        </div>
      `}_isEmpty(r=this.modelValue){let n=r;if(this.modelValue instanceof Li&&(n=this.modelValue.viewValue),typeof n=="object"&&n!==null&&!(n instanceof Date))return!Object.keys(n).length;const i=typeof n=="number"&&(n===0||Number.isNaN(n));return!n&&!i&&!(typeof n=="boolean"&&n===!1)}static get styles(){return[te`
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
        `]}_getAriaDescriptionElements(){return[this._helpTextNode,this._feedbackNode]}addToAriaLabelledBy(r,{idPrefix:n="",reorder:i=!0}={}){r.id=r.id||`${n}-${this._inputId}`,this._ariaLabelledNodes.includes(r)||(this._ariaLabelledNodes=[...this._ariaLabelledNodes,r],this.__reorderAriaLabelledNodes=!!i)}removeFromAriaLabelledBy(r){this._ariaLabelledNodes.includes(r)&&(this._ariaLabelledNodes.splice(this._ariaLabelledNodes.indexOf(r),1),this._ariaLabelledNodes=[...this._ariaLabelledNodes],this.__reorderAriaLabelledNodes=!1)}addToAriaDescribedBy(r,{idPrefix:n="",reorder:i=!0}={}){r.id=r.id||`${n}-${this._inputId}`,this._ariaDescribedNodes.includes(r)||(this._ariaDescribedNodes=[...this._ariaDescribedNodes,r],this.__reorderAriaDescribedNodes=!!i)}removeFromAriaDescribedBy(r){this._ariaDescribedNodes.includes(r)&&(this._ariaDescribedNodes.splice(this._ariaDescribedNodes.indexOf(r),1),this._ariaDescribedNodes=[...this._ariaDescribedNodes],this.__reorderAriaLabelledNodes=!1)}__getDirectSlotChild(r){return Array.from(this.children).find(n=>n.slot===r)}_dispatchInitialModelValueChangedEvent(){this._repropagationRole!=="child"&&(this.__repropagateChildrenInitialized=!0,this.dispatchEvent(new CustomEvent("model-value-changed",{bubbles:!0,detail:{formPath:[this],initialize:!0,isTriggeredByUser:!1}})))}_onBeforeRepropagateChildrenValues(r){}__repropagateChildrenValues(r){this._onBeforeRepropagateChildrenValues(r);const n=r.detail&&r.detail.element||r.target,i=this._isRepropagationEndpoint||this._repropagationRole==="choice-group";if(n===this)return;r.stopImmediatePropagation();const o=this._repropagationRole!=="child"&&!this.__repropagateChildrenInitialized,a=r.detail&&r.detail.initialize;if(o||a||!this._repropagationCondition(n))return;let l=[];i||(l=r.detail&&r.detail.formPath||[n]);const u=[...l,this];this.dispatchEvent(new CustomEvent("model-value-changed",{bubbles:!0,detail:{formPath:u,isTriggeredByUser:!!r.detail?.isTriggeredByUser}}))}_repropagationCondition(r){return!!r}_onLabelClick(){}},Vi=He(rD);class Kc extends EventTarget{constructor(t,r){super(),this.__param=t,this.__config=r||{},this.type=r?.type||"error"}static _$isValidator$=!0;static validatorName="";static async=!1;execute(t,r,n){if(!this.constructor.validatorName)throw new Error(`A validator needs to have a name! Please set it via "static get validatorName() { return 'IsCat'; }"`);return!0}set param(t){this.__param=t,this.dispatchEvent(new Event("param-changed"))}get param(){return this.__param}set config(t){this.__config=t,this.dispatchEvent(new Event("config-changed"))}get config(){return this.__config}async _getMessage(t){const r=this.constructor,n={name:r.validatorName,type:this.type,params:this.param,config:this.config,...t};if(this.config.getMessage){if(typeof this.config.getMessage=="function")return this.config.getMessage(n);throw new Error(`You must provide a value for getMessage of type 'function', you provided a value of type: ${typeof this.config.getMessage}`)}return r.getMessage(n)}static async getMessage(t){return`Please configure an error message for "${this.name}" by overriding "static async getMessage()"`}onFormControlConnect(t){}onFormControlDisconnect(t){}abortExecution(){}}function tv(e=[],t=[]){return e.filter(r=>!t.includes(r)).concat(t.filter(r=>!e.includes(r)))}function nD(e){return e instanceof Li?e.viewValue:e}const iD=e=>class extends Vi(JM(ha(Is(ma(e))))){static get scopedElements(){return{...super.scopedElements,"lion-validation-feedback":dE}}static get properties(){return{validators:{attribute:!1},hasFeedbackFor:{attribute:!1},shouldShowFeedbackFor:{attribute:!1},showsFeedbackFor:{type:Array,attribute:"shows-feedback-for",reflect:!0,converter:{fromAttribute:t=>t.split(","),toAttribute:t=>t.join(",")}},validationStates:{attribute:!1},isPending:{type:Boolean,attribute:"is-pending",reflect:!0},defaultValidators:{attribute:!1},_visibleMessagesAmount:{attribute:!1},__childModelValueChanged:{attribute:!1}}}static get validationTypes(){return["error"]}get operationMode(){return"enter"}get slots(){return{...super.slots,feedback:()=>{const t=this.createScopedElement("lion-validation-feedback");return t.setAttribute("data-tag-name","lion-validation-feedback"),t}}}get _allValidators(){return[...this.validators,...this.defaultValidators]}constructor(){super(),this.hasFeedbackFor=[],this.showsFeedbackFor=[],this.shouldShowFeedbackFor=[],this.validationStates={},this.isPending=!1,this.validators=[],this.defaultValidators=[],this._visibleMessagesAmount=1,this.__syncValidationResult=[],this.__asyncValidationResult=[],this.__validationResult=[],this.__prevValidationResult=[],this.__prevShownValidationResult=[],this.__childModelValueChanged=!1,this._onValidatorUpdated=this._onValidatorUpdated.bind(this),this._updateFeedbackComponent=this._updateFeedbackComponent.bind(this)}connectedCallback(){super.connectedCallback(),Xl().addEventListener("localeChanged",this._updateFeedbackComponent)}disconnectedCallback(){super.disconnectedCallback(),Xl().removeEventListener("localeChanged",this._updateFeedbackComponent)}firstUpdated(t){super.firstUpdated(t),this.__isValidateInitialized=!0,this.validate(),this._repropagationRole!=="child"&&this.addEventListener("model-value-changed",()=>{this.__childModelValueChanged=!0})}updateSync(t,r){if(super.updateSync(t,r),t==="validators"?(this.__setupValidators(),this.validate({clearCurrentResult:!0})):t==="modelValue"&&this.validate({clearCurrentResult:!0}),["touched","dirty","prefilled","focused","submitted","hasFeedbackFor","filled"].includes(t)&&this._updateShouldShowFeedbackFor(),t==="showsFeedbackFor"){this._inputNode&&this._inputNode.setAttribute("aria-invalid",`${this._hasFeedbackVisibleFor("error")}`);const n=tv(this.showsFeedbackFor,r);n.length>0&&this.dispatchEvent(new Event("showsFeedbackForChanged",{bubbles:!0})),n.forEach(i=>{this.dispatchEvent(new Event(`showsFeedbackFor${YM(i)}Changed`,{bubbles:!0}))})}t==="shouldShowFeedbackFor"&&tv(this.shouldShowFeedbackFor,r).length>0&&this.dispatchEvent(new Event("shouldShowFeedbackForChanged",{bubbles:!0}))}async validate({clearCurrentResult:t=!1}={}){if(this.validateComplete=new Promise(r=>{this.__validateCompleteResolve=r}),this.disabled){this.__clearValidationResults(),this.__finishValidationPass(),this._updateFeedbackComponent();return}this.__isValidateInitialized&&(this.__prevValidationResult=this.__validationResult,t&&this.__clearValidationResults(),await this.__executeValidators())}#e(t){let r=t;for(;r;){if(r.constructor.validatorName==="Required")return!0;r=Object.getPrototypeOf(r)}return!1}async __executeValidators(){const t=nD(this.modelValue),r=this.__isEmpty(t);if(this.__syncValidationResult=[],r){const a=!this._isFormOrFieldset,l=this._allValidators.find(u=>u.constructor?.validatorName==="Required");if(l&&(this.__syncValidationResult=[{validator:l,outcome:!0}]),a){this.__finishValidationPass({syncValidationResult:this.__syncValidationResult});return}}const n=[],i=[],s=[];for(const a of this._allValidators)a?.executeOnResults?n.push(a):this.#e(a)||(a.constructor.async?s.push(a):i.push(a));const o=!!s.length;this.__syncValidationResult=[...this.__syncValidationResult,...this.__executeSyncValidators(i,t)],this.__finishValidationPass({syncValidationResult:this.__syncValidationResult,metaValidators:n}),o?(this.isPending=!0,this.__asyncValidationResult=await this.__executeAsyncValidators(s,t),this.isPending=!1,this.__finishValidationPass({syncValidationResult:this.__syncValidationResult,asyncValidationResult:this.__asyncValidationResult,metaValidators:n}),this.__validateCompleteResolve?.(!0)):this.__validateCompleteResolve?.(!0)}__executeSyncValidators(t,r){return t.map(n=>({validator:n,outcome:n.execute(r,n.param,{node:this})})).filter(n=>!!n.outcome)}async __executeAsyncValidators(t,r){const n=t.map(s=>s.execute(r,s.param,{node:this})),i=await Promise.all(n);return i.map((s,o)=>({validator:t[o],outcome:i[o]})).filter(s=>!!s.outcome)}__executeMetaValidators(t,r){return r.length?this._isEmpty(this.modelValue)?(this.__prevShownValidationResult=[],[]):r.map(n=>({validator:n,outcome:n.executeOnResults({regularValidationResult:t.map(i=>i.validator),prevValidationResult:this.__prevValidationResult.map(i=>i.validator),prevShownValidationResult:this.__prevShownValidationResult.map(i=>i.validator)})})).filter(n=>!!n.outcome):[]}__finishValidationPass({syncValidationResult:t=[],asyncValidationResult:r=[],metaValidators:n=[]}={}){const i=[...t,...r],s=this.__executeMetaValidators(i,n);this.__validationResult=[...s,...i];const a=this.constructor.validationTypes.reduce((l,u)=>({...l,[u]:{}}),{});for(const{validator:l,outcome:u}of this.__validationResult){a[l.type]||(a[l.type]={});const c=l.constructor;a[l.type][c.validatorName]=u}this.validationStates=a,this.hasFeedbackFor=[...new Set(this.__validationResult.map(({validator:l})=>l.type))],this.dispatchEvent(new Event("validate-performed",{bubbles:!0}))}__clearValidationResults(){this.__syncValidationResult=[],this.__asyncValidationResult=[]}_onValidatorUpdated(t){(t.type==="param-changed"||t.type==="config-changed")&&this.validate()}__setupValidators(){const t=["param-changed","config-changed"];for(const r of this.__prevValidators||[]){for(const n of t)r.removeEventListener?.(n,this._onValidatorUpdated);r.onFormControlDisconnect(this)}for(const r of this._allValidators){if(r.constructor._$isValidator$===void 0){const a=`Validators array only accepts class instances of Validator. Type "${Array.isArray(r)?"array":typeof r}" found. This may be caused by having multiple installations of "@lion/ui/form-core.js".`;throw console.error(a,this),new Error(a)}const i=this.constructor,s=r.constructor;if(i.validationTypes.indexOf(r.type)===-1){const o=`This component does not support the validator type "${r.type}" used in "${s.validatorName}". You may change your validators type or add it to the components "static get validationTypes() {}".`;throw console.error(o,this),new Error(o)}for(const o of t)r.addEventListener?.(o,a=>{this._onValidatorUpdated(a,{validator:r})});r.onFormControlConnect(this)}this.__prevValidators=this._allValidators}__isEmpty(t){return typeof this._isEmpty=="function"?this._isEmpty(t):this.modelValue===null||typeof this.modelValue>"u"||this.modelValue===""}async __getFeedbackMessages(t){let r=await this.fieldName;return Promise.all(t.map(async({validator:n,outcome:i})=>(n.config.fieldName&&(r=await n.config.fieldName),{message:await n._getMessage({modelValue:this.modelValue,formControl:this,fieldName:r,outcome:i}),type:n.type,validator:n,visibilityDuration:n.config?.visibilityDuration||3e3})))}_updateFeedbackComponent(){window.clearTimeout(this.removeMessage);const{_feedbackNode:t}=this;t&&(this.__feedbackQueue||(this.__feedbackQueue=new GM),this.showsFeedbackFor.length>0?this.__feedbackQueue.add(async()=>{const r=this._prioritizeAndFilterFeedback({validationResult:this.__validationResult.map(i=>i.validator)});this.__prioritizedResult=r.map(i=>this.__validationResult.find(o=>i===o.validator)).filter(Boolean),this.__prioritizedResult.length>0&&(this.__prevShownValidationResult=this.__prioritizedResult);const n=await this.__getFeedbackMessages(this.__prioritizedResult);t.feedbackData=n||[],n?.[0]&&n[0].type==="success"&&n[0].visibilityDuration!==1/0&&(this.removeMessage=window.setTimeout(()=>{t.removeAttribute("type"),t.feedbackData=[]},n[0].visibilityDuration))}):this.__feedbackQueue.add(async()=>{t.feedbackData=[]}),this.feedbackComplete=this.__feedbackQueue.complete)}_showFeedbackConditionFor(t,r){return!0}get _feedbackConditionMeta(){return{modelValue:this.modelValue,el:this}}feedbackCondition(t,r=this._feedbackConditionMeta,n=this._showFeedbackConditionFor.bind(this)){return n(t,r)}_hasFeedbackVisibleFor(t){return this.hasFeedbackFor?.includes(t)&&this.shouldShowFeedbackFor?.includes(t)}updated(t){if(super.updated(t),t.has("shouldShowFeedbackFor")||t.has("hasFeedbackFor")){const r=this.constructor;this.showsFeedbackFor=r.validationTypes.map(n=>this._hasFeedbackVisibleFor(n)?n:void 0).filter(Boolean),this._updateFeedbackComponent()}if(t.has("__childModelValueChanged")&&this.__childModelValueChanged&&(this.validate({clearCurrentResult:!0}),this.__childModelValueChanged=!1),t.has("validationStates")){const r=t.get("validationStates");r&&Object.entries(this.validationStates).forEach(([n,i])=>{r[n]&&JSON.stringify(i)!==JSON.stringify(r[n])&&this.dispatchEvent(new CustomEvent(`${n}StateChanged`,{detail:i}))})}}_updateShouldShowFeedbackFor(){const r=this.constructor.validationTypes.map(n=>this.feedbackCondition(n,this._feedbackConditionMeta,this._showFeedbackConditionFor.bind(this))?n:void 0).filter(Boolean);JSON.stringify(this.shouldShowFeedbackFor)!==JSON.stringify(r)&&(this.shouldShowFeedbackFor=r)}_prioritizeAndFilterFeedback({validationResult:t}){const n=this.constructor.validationTypes;return t.filter(s=>this.feedbackCondition(s.type,this._feedbackConditionMeta,this._showFeedbackConditionFor.bind(this))).sort((s,o)=>n.indexOf(s.type)-n.indexOf(o.type)).slice(0,this._visibleMessagesAmount)}},ga=He(iD),sD=e=>class extends ga(Vi(e)){static get properties(){return{formattedValue:{attribute:!1},serializedValue:{attribute:!1},formatOptions:{attribute:!1}}}#e={didFormatterOutputSyncToView:!1,didFormatterRun:!1};requestUpdate(r,n,i){super.requestUpdate(r,n,i),r==="modelValue"&&this.modelValue!==n&&this._onModelValueChanged({modelValue:this.modelValue},{modelValue:n}),r==="serializedValue"&&this.serializedValue!==n&&this._calculateValues({source:"serialized"}),r==="formattedValue"&&this.formattedValue!==n&&this._calculateValues({source:"formatted"})}get value(){return this._inputNode?.value||this.__value||""}set value(r){this._inputNode?(this._inputNode.value=r,this.__value=void 0):this.__value=r}preprocessor(r,n){}parser(r,n){return r}formatter(r,n){return r}serializer(r){return r!==void 0?r:""}deserializer(r){return r===void 0?"":r}_calculateValues({source:r}={source:null}){this.__preventRecursiveTrigger||(this.__preventRecursiveTrigger=!0,r!=="model"&&(r==="serialized"?this.modelValue=this.deserializer(this.serializedValue):r==="formatted"&&(this.modelValue=this._callParser())),r!=="formatted"&&(this.formattedValue=this._callFormatter()),r!=="serialized"&&(this.serializedValue=this.serializer(this.modelValue)),this._reflectBackFormattedValueToUser(),this.__preventRecursiveTrigger=!1,this.__prevViewValue=this.value)}_callParser(r=this.formattedValue){if(r==="")return"";if(typeof r!="string")return;const n=this.parser(r,{...this.formatOptions,mode:this.#t(),viewValueStates:this.#r()});return n!==void 0?n:new Li(r)}_callFormatter(){return this.#e.didFormatterRun=!1,this._isHandlingUserInput&&this.hasFeedbackFor?.includes("error")&&this._inputNode?this.value:this.modelValue instanceof Li?this.modelValue.viewValue:(this.#e.didFormatterRun=!0,this.formatter(this.modelValue,{...this.formatOptions,mode:this.#t(),viewValueStates:this.#r()}))}_onModelValueChanged(...r){this._calculateValues({source:"model"}),this._dispatchModelValueChangedEvent(...r)}_dispatchModelValueChangedEvent(...r){this.dispatchEvent(new CustomEvent("model-value-changed",{bubbles:!0,detail:{formPath:[this],isTriggeredByUser:!!this._isHandlingUserInput}}))}_syncValueUpwards(){this.__isHandlingComposition||this.__handlePreprocessor();const r=this.formattedValue;this.modelValue=this._callParser(this.value),r===this.formattedValue&&this.__prevViewValue!==this.value&&this._calculateValues()}__handlePreprocessor(){let r=this.value.length;this._inputNode&&"selectionStart"in this._inputNode&&this._inputNode?.type!=="range"&&(r=this._inputNode.selectionStart);const n=this.preprocessor(this.value,{...this.formatOptions,currentCaretIndex:r,prevViewValue:this.__prevViewValue});if(n!==void 0){if(typeof n=="string")this.value=n;else if(typeof n=="object"){const{viewValue:i,caretIndex:s}=n;this.value=i,s&&this._inputNode&&"selectionStart"in this._inputNode&&(this._inputNode.selectionStart=s,this._inputNode.selectionEnd=s)}}}_reflectBackFormattedValueToUser(){this._reflectBackOn()&&(this.value=typeof this.formattedValue<"u"?this.formattedValue:"",this.#e.didFormatterOutputSyncToView=!!this.formattedValue&&this.#e.didFormatterRun)}_reflectBackOn(){return!this._isHandlingUserInput}_proxyInputEvent(){this.dispatchEvent(new Event("user-input-changed",{bubbles:!0}))}_onUserInputChanged(){this._isHandlingUserInput=!0,this._syncValueUpwards(),this._isHandlingUserInput=!1}__onCompositionEvent({type:r}){r==="compositionstart"?this.__isHandlingComposition=!0:r==="compositionend"&&(this.__isHandlingComposition=!1,this._syncValueUpwards())}constructor(){super(),this.formatOn="change",this.formatOptions={mode:"auto"},this.formattedValue=void 0,this.serializedValue=void 0,this._isPasting=!1,this._isHandlingUserInput=!1,this.__prevViewValue="",this.__onCompositionEvent=this.__onCompositionEvent.bind(this),this.addEventListener("user-input-changed",this._onUserInputChanged),this.addEventListener("paste",this.__onPaste),this._reflectBackFormattedValueToUser=this._reflectBackFormattedValueToUser.bind(this),this._reflectBackFormattedValueDebounced=()=>{setTimeout(this._reflectBackFormattedValueToUser)}}__onPaste(){this._isPasting=!0,setTimeout(()=>{this._isPasting=!1})}connectedCallback(){super.connectedCallback(),typeof this.modelValue>"u"&&this._syncValueUpwards(),this.__prevViewValue=this.value,this._reflectBackFormattedValueToUser(),this._inputNode&&(this._inputNode.addEventListener(this.formatOn,this._reflectBackFormattedValueDebounced),this._inputNode.addEventListener("input",this._proxyInputEvent),this._inputNode.addEventListener("compositionstart",this.__onCompositionEvent),this._inputNode.addEventListener("compositionend",this.__onCompositionEvent))}disconnectedCallback(){super.disconnectedCallback(),this._inputNode&&(this._inputNode.removeEventListener("input",this._proxyInputEvent),this._inputNode.removeEventListener(this.formatOn,this._reflectBackFormattedValueDebounced),this._inputNode.removeEventListener("compositionstart",this.__onCompositionEvent),this._inputNode.removeEventListener("compositionend",this.__onCompositionEvent))}#t(){return this._isPasting?"pasted":this._isHandlingUserInput&&this.__prevViewValue?"user-edited":"auto"}#r(){const r=[];return this.#e.didFormatterOutputSyncToView&&r.push("formatted"),r}},Pp=He(sD),oD=e=>class extends Vi(e){static get properties(){return{touched:{type:Boolean,reflect:!0},dirty:{type:Boolean,reflect:!0},filled:{type:Boolean,reflect:!0},prefilled:{attribute:!1},submitted:{attribute:!1}}}requestUpdate(r,n,i){super.requestUpdate(r,n,i),r==="touched"&&this.touched!==n&&this._onTouchedChanged(),r==="modelValue"&&(this.filled=!this._isEmpty()),r==="dirty"&&this.dirty!==n&&this._onDirtyChanged()}constructor(){super(),this.touched=!1,this.dirty=!1,this.prefilled=!1,this.filled=!1,this._leaveEvent="blur",this._valueChangedEvent="model-value-changed",this._iStateOnLeave=this._iStateOnLeave.bind(this),this._iStateOnValueChange=this._iStateOnValueChange.bind(this)}connectedCallback(){super.connectedCallback(),this.addEventListener(this._leaveEvent,this._iStateOnLeave),this.addEventListener(this._valueChangedEvent,this._iStateOnValueChange),this.initInteractionState()}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener(this._leaveEvent,this._iStateOnLeave),this.removeEventListener(this._valueChangedEvent,this._iStateOnValueChange)}initInteractionState(){this.dirty=!1,this.prefilled=!this._isEmpty()}_iStateOnLeave(){this.touched=!0,this.prefilled=!this._isEmpty()}_iStateOnValueChange(){this.dirty=!0}resetInteractionState(){this.touched=!1,this.submitted=!1,this.dirty=!1,this.prefilled=!this._isEmpty()}_onTouchedChanged(){this.dispatchEvent(new Event("touched-changed",{bubbles:!0,composed:!0}))}_onDirtyChanged(){this.dispatchEvent(new Event("dirty-changed",{bubbles:!0,composed:!0}))}_showFeedbackConditionFor(r,n){return n.touched&&n.dirty||n.prefilled||n.submitted}get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,submitted:this.submitted,touched:this.touched,dirty:this.dirty,filled:this.filled,prefilled:this.prefilled}}},Ip=He(oD);class ba extends Vi(Ip(Op(Pp(ga(Is(Ne)))))){firstUpdated(t){super.firstUpdated(t),this._initialModelValue=this.modelValue}connectedCallback(){super.connectedCallback(),this._onChange=this._onChange.bind(this),this._inputNode.addEventListener("change",this._onChange),this.classList.add("form-field")}disconnectedCallback(){super.disconnectedCallback(),this._inputNode?.removeEventListener("change",this._onChange)}resetInteractionState(){super.resetInteractionState(),this.submitted=!1}reset(){this.modelValue=this._initialModelValue,this.resetInteractionState()}clear(){this.modelValue=""}_onChange(t){this.dispatchEvent(new Event("user-input-changed",{bubbles:!0}))}get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,focused:this.focused}}get _focusableNode(){return this._inputNode}}class Wh extends Array{_keys(){return Object.keys(this).filter(t=>Number.isNaN(Number(t)))}}const aD=e=>class extends Np(e){static get properties(){return{_isFormOrFieldset:{type:Boolean}}}constructor(){super(),this.formElements=new Wh,this._isFormOrFieldset=!1,this._onRequestToAddFormElement=this._onRequestToAddFormElement.bind(this),this._onRequestToChangeFormElementName=this._onRequestToChangeFormElementName.bind(this),this.addEventListener("form-element-register",this._onRequestToAddFormElement),this.addEventListener("form-element-name-changed",this._onRequestToChangeFormElementName),this.initComplete=new Promise((t,r)=>{this.__resolveInitComplete=t,this.__rejectInitComplete=r}),this.registrationComplete=new Promise((t,r)=>{this.__resolveRegistrationComplete=t,this.__rejectRegistrationComplete=r}),this.registrationComplete.done=!1,this.registrationComplete.then(()=>{this.registrationComplete.done=!0,this.__resolveInitComplete(void 0)},()=>{throw this.registrationComplete.done=!0,this.__rejectInitComplete(void 0),new Error("Registration could not finish. Please use await el.registrationComplete;")})}connectedCallback(){super.connectedCallback(),this._completeRegistration()}_completeRegistration(){Promise.resolve().then(()=>this.__resolveRegistrationComplete(void 0))}disconnectedCallback(){super.disconnectedCallback(),this.registrationComplete.done===!1&&Promise.resolve().then(()=>{Promise.resolve().then(()=>{this.__rejectRegistrationComplete()})})}isRegisteredFormElement(t){return this.formElements.some(r=>r===t)}addFormElement(t,r){if(t._parentFormGroup=this,r>=0?this.formElements.splice(r,0,t):this.formElements.push(t),this._isFormOrFieldset){const{name:n}=t;if(n===this.name)throw console.info("Error Node:",t),new TypeError(`You can not have the same name "${n}" as your parent`);if(n.substr(-2)==="[]")Array.isArray(this.formElements[n])||(this.formElements[n]=new Wh),r>0?this.formElements[n].splice(r,0,t):this.formElements[n].push(t);else if(!this.formElements[n])this.formElements[n]=t;else throw console.info("Error Node:",t),new TypeError(`Name "${n}" is already registered - if you want an array add [] to the end`)}}removeFormElement(t){const r=this.formElements.indexOf(t);if(r>-1&&this.formElements.splice(r,1),this._isFormOrFieldset){const{name:n}=t;if(n.substr(-2)==="[]"&&this.formElements[n]){const i=this.formElements[n].indexOf(t);i>-1&&this.formElements[n].splice(i,1)}else this.formElements[n]&&delete this.formElements[n]}}_onRequestToAddFormElement(t){const r=t.detail.element;if(r===this||this.isRegisteredFormElement(r))return;t.stopPropagation();let n=-1;if(this.formElements&&Array.isArray(this.formElements)){for(const[i,s]of this.formElements.entries())if(!(s.compareDocumentPosition(r)&Node.DOCUMENT_POSITION_FOLLOWING)){n=i;break}}this.addFormElement(r,n)}_onRequestToChangeFormElementName(t){const r=this.formElements[t.detail.oldName];r&&(this.formElements[t.detail.newName]=r,delete this.formElements[t.detail.oldName])}_onRequestToRemoveFormElement(t){const r=t.detail.element;r!==this&&this.isRegisteredFormElement(r)&&(t.stopPropagation(),this.removeFormElement(r))}},Fp=He(aD),lD=e=>class extends e{constructor(){super(),this.registrationTarget=void 0,this.__redispatchEventForFormRegistrarPortalMixin=this.__redispatchEventForFormRegistrarPortalMixin.bind(this),this.addEventListener("form-element-register",this.__redispatchEventForFormRegistrarPortalMixin)}__redispatchEventForFormRegistrarPortalMixin(t){if(t.stopPropagation(),!this.registrationTarget)throw new Error("A FormRegistrarPortal element requires a .registrationTarget");this.registrationTarget.dispatchEvent(new CustomEvent("form-element-register",{detail:{element:t.detail.element},bubbles:!0}))}},cD=He(lD),uD=e=>class extends Pp(Op(Vi(e))){static get properties(){return{autocomplete:{type:String,reflect:!0}}}constructor(){super(),this.autocomplete=void 0}get _inputNode(){return super._inputNode}get selectionStart(){const r=this._inputNode;return r&&r.selectionStart?r.selectionStart:0}set selectionStart(r){const n=this._inputNode;n&&n.selectionStart&&(n.selectionStart=r)}get selectionEnd(){const r=this._inputNode;return r&&r.selectionEnd?r.selectionEnd:0}set selectionEnd(r){const n=this._inputNode;n&&n.selectionEnd&&(n.selectionEnd=r)}get value(){return this._inputNode&&this._inputNode.value||this.__value||""}set value(r){this._inputNode?(this._inputNode.value!==r&&this._setValueAndPreserveCaret(r),this.__value=void 0):this.__value=r}_setValueAndPreserveCaret(r){if(this.focused)try{if(!(this._inputNode instanceof HTMLSelectElement)){const n=this._inputNode.selectionStart;this._inputNode.value=r,this._inputNode.selectionStart=n,this._inputNode.selectionEnd=n}}catch{this._inputNode.value=r}else this._inputNode.value=r}_reflectBackFormattedValueToUser(){if(super._reflectBackFormattedValueToUser(),this._reflectBackOn()&&this.focused)try{this._inputNode.selectionStart=this._inputNode.value.length}catch{}}get _focusableNode(){return this._inputNode}},fE=He(uD),dD=e=>class extends Fp(ga(Ip(e))){static get properties(){return{multipleChoice:{type:Boolean,attribute:"multiple-choice"}}}get modelValue(){const r=this._getCheckedElements();return this.multipleChoice?r.map(n=>n.choiceValue):r[0]?r[0].choiceValue:""}set modelValue(r){const n=(i,s)=>typeof i.choiceValue=="object"?JSON.stringify(i.choiceValue)===JSON.stringify(r):i.choiceValue===s;this.__isInitialModelValue?this.registrationComplete.then(()=>{this.__isInitialModelValue=!1,this._setCheckedElements(r,n),this.requestUpdate("modelValue",this._oldModelValue)}):(this._setCheckedElements(r,n),this.requestUpdate("modelValue",this._oldModelValue)),this._oldModelValue=this.modelValue}get serializedValue(){const r=this._getCheckedElements();return this.multipleChoice?r.map(n=>n.serializedValue.value):r[0]?r[0].serializedValue.value:""}set serializedValue(r){const n=(i,s)=>i.serializedValue.value===s;this.__isInitialSerializedValue?this.registrationComplete.then(()=>{this.__isInitialSerializedValue=!1,this._setCheckedElements(r,n),this.requestUpdate("serializedValue")}):(this._setCheckedElements(r,n),this.requestUpdate("serializedValue"))}get formattedValue(){const r=this._getCheckedElements();return this.multipleChoice?r.map(n=>n.formattedValue):r[0]?r[0].formattedValue:""}set formattedValue(r){const n=(i,s)=>i.formattedValue===s;this.__isInitialFormattedValue?this.registrationComplete.then(()=>{this.__isInitialFormattedValue=!1,this._setCheckedElements(r,n)}):this._setCheckedElements(r,n)}get operationMode(){return this._repropagationRole==="choice-group"?"select":"enter"}constructor(){super(),this.multipleChoice=!1,this._repropagationRole="choice-group",this.__isInitialModelValue=!0,this.__isInitialSerializedValue=!0,this.__isInitialFormattedValue=!0}connectedCallback(){super.connectedCallback(),this.registrationComplete.then(()=>{this.__isInitialModelValue=!1,this.__isInitialSerializedValue=!1,this.__isInitialFormattedValue=!1})}_completeRegistration(){Promise.resolve().then(()=>super._completeRegistration())}updated(r){super.updated(r),r.has("name")&&this.name!==r.get("name")&&this.formElements.forEach(n=>{n.name=this.name})}addFormElement(r,n){this._throwWhenInvalidChildModelValue(r),r.name=this.name,super.addFormElement(r,n)}clear(){this.multipleChoice?this.modelValue=[]:this.modelValue=""}_triggerInitialModelValueChangedEvent(){this.registrationComplete.then(()=>{this._dispatchInitialModelValueChangedEvent()})}_getFromAllFormElementsFilter(r,n){return!0}_getFromAllFormElements(r,n){const i=n||this._getFromAllFormElementsFilter;return r==="modelValue"||r==="serializedValue"||r==="formattedValue"?this[r]:this.formElements.filter(s=>i(s,r)).map(s=>s.property)}_throwWhenInvalidChildModelValue(r){if(typeof r.modelValue.checked!="boolean"||!Object.prototype.hasOwnProperty.call(r.modelValue,"value"))throw new Error(`The ${this.tagName.toLowerCase()} name="${this.name}" does not allow to register ${r.tagName.toLowerCase()} with .modelValue="${r.modelValue}" - The modelValue should represent an Object { value: "foo", checked: false }`)}_isEmpty(){return this.multipleChoice?this.modelValue.length===0:typeof this.modelValue=="string"&&this.modelValue===""||this.modelValue===void 0||this.modelValue===null}_checkSingleChoiceElements(r){const{target:n}=r;if(n.checked===!1)return;const i=n.name;this.formElements.filter(s=>s.name===i).forEach(s=>{s!==n&&(s.checked=!1)})}_getCheckedElements(){return this.formElements.filter(r=>r.checked&&!r.disabled)}_setCheckedElements(r,n){if(r==null){this.formElements.forEach(i=>i.checked=!1);return}for(let i=0;i<this.formElements.length;i+=1)if(this.multipleChoice){let s=r.includes(this.formElements[i].modelValue.value);typeof this.formElements[i].modelValue.value=="object"&&(s=r.map(o=>JSON.stringify(o)).includes(JSON.stringify(this.formElements[i].modelValue.value))),this.formElements[i].checked=s}else n(this.formElements[i],r)?this.formElements[i].checked=!0:this.formElements[i].checked=!1}__setChoiceGroupTouched(){const r=this.modelValue;r!=null&&r!==this.__previousCheckedValue&&(this.touched=!0,this.__previousCheckedValue=r)}_onBeforeRepropagateChildrenValues(r){const n=r.detail&&r.detail.element||r.target;this.multipleChoice||!n.checked||(this.formElements.forEach(i=>{n.choiceValue!==i.choiceValue&&(i.checked=!1)}),this.__setChoiceGroupTouched(),this.requestUpdate("modelValue",this._oldModelValue),this._oldModelValue=this.modelValue)}_repropagationCondition(r){return!(this._repropagationRole==="choice-group"&&!this.multipleChoice&&!r.checked)}},Gc=He(dD),hD=(e,t={})=>e.value!==t.value||e.checked!==t.checked,fD=e=>class extends Pp(e){static get properties(){return{checked:{type:Boolean,reflect:!0},disabled:{type:Boolean,reflect:!0},modelValue:{type:Object,hasChanged:hD},choiceValue:{type:Object}}}get choiceValue(){return this.modelValue.value}set choiceValue(r){this.requestUpdate("choiceValue",this.choiceValue),this.modelValue.value!==r&&(this.modelValue={value:r,checked:this.modelValue.checked})}requestUpdate(r,n,i){super.requestUpdate(r,n,i),r==="modelValue"?this.modelValue.checked!==this.checked&&this.__syncModelCheckedToChecked(this.modelValue.checked):r==="checked"&&this.modelValue.checked!==this.checked&&this.__syncCheckedToModel(this.checked)}firstUpdated(r){super.firstUpdated(r),r.has("checked")&&this.__syncCheckedToInputElement()}updated(r){super.updated(r),r.has("modelValue")&&this.__syncCheckedToInputElement(),r.has("name")&&this._parentFormGroup&&this._parentFormGroup.name!==this.name&&this._syncNameToParentFormGroup()}constructor(){super(),this.modelValue={value:"",checked:!1},this.disabled=!1,this._preventDuplicateLabelClick=this._preventDuplicateLabelClick.bind(this),this._toggleChecked=this._toggleChecked.bind(this)}static get styles(){return[...super.styles||[],te`
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
        `]}render(){return W`
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
      `}_choiceGraphicTemplate(){return ht}_afterTemplate(){return ht}connectedCallback(){super.connectedCallback(),this._labelNode&&this._labelNode.addEventListener("click",this._preventDuplicateLabelClick),this.addEventListener("user-input-changed",this._toggleChecked)}disconnectedCallback(){super.disconnectedCallback(),this._labelNode&&this._labelNode.removeEventListener("click",this._preventDuplicateLabelClick),this.removeEventListener("user-input-changed",this._toggleChecked)}_preventDuplicateLabelClick(r){const n=i=>{i.stopImmediatePropagation(),this._inputNode.removeEventListener("click",n)};this._inputNode.addEventListener("click",n)}_toggleChecked(r){this.disabled||(this._isHandlingUserInput=!0,this.checked=!this.checked,this._isHandlingUserInput=!1)}_syncNameToParentFormGroup(){this._parentFormGroup.tagName.includes(this.tagName)&&(this.name=this._parentFormGroup?.name||"")}__syncModelCheckedToChecked(r){this.checked=r}__syncCheckedToModel(r){this.modelValue={value:this.choiceValue,checked:r}}__syncCheckedToInputElement(){this._inputNode&&(this._inputNode.checked=this.checked)}_proxyInputEvent(){}_onModelValueChanged({modelValue:r},n){let i;n&&n.modelValue&&(i=n.modelValue),this.constructor.elementProperties.get("modelValue").hasChanged(r,i)&&super._onModelValueChanged({modelValue:r})}parser(){return this.modelValue}formatter(r){return r&&r.value!==void 0?r.value:r}clear(){this.checked=!1}_isEmpty(){return!this.checked}_syncValueUpwards(){}},Yc=He(fD);class pD extends Kc{static get validatorName(){return"FormElementsHaveNoError"}execute(t,r,n){return n?.node._anyFormElementHasFeedbackFor("error")}static async getMessage(){return""}}const mD=e=>class extends Fp(Vi(ga(ha(Is(e))))){static get properties(){return{submitted:{type:Boolean,reflect:!0},focused:{type:Boolean,reflect:!0},dirty:{type:Boolean,reflect:!0},touched:{type:Boolean,reflect:!0},prefilled:{type:Boolean,reflect:!0}}}get _inputNode(){return this}get modelValue(){return this._getFromAllFormElements("modelValue")}set modelValue(r){this.__isInitialModelValue?(this.__isInitialModelValue=!1,this.registrationComplete.then(()=>{this._setValueMapForAllFormElements("modelValue",r)})):this._setValueMapForAllFormElements("modelValue",r)}get serializedValue(){return this._getFromAllFormElements("serializedValue")}set serializedValue(r){this.__isInitialSerializedValue?(this.__isInitialSerializedValue=!1,this.registrationComplete.then(()=>{this._setValueMapForAllFormElements("serializedValue",r)})):this._setValueMapForAllFormElements("serializedValue",r)}get formattedValue(){return this._getFromAllFormElements("formattedValue")}set formattedValue(r){this._setValueMapForAllFormElements("formattedValue",r)}get prefilled(){return this._everyFormElementHas("prefilled")}constructor(){super(),this.value="",this.disabled=!1,this.submitted=!1,this.dirty=!1,this.touched=!1,this.focused=!1,this.__addedSubValidators=!1,this.__isInitialModelValue=!0,this.__isInitialSerializedValue=!0,this._checkForOutsideClick=this._checkForOutsideClick.bind(this),this.addEventListener("focusin",this._syncFocused),this.addEventListener("focusout",this._onFocusOut),this.addEventListener("dirty-changed",this._syncDirty),this.addEventListener("validate-performed",this.__onChildValidatePerformed),this.defaultValidators=[new pD],this.__descriptionElementsInParentChain=new Set,this.__pendingValues={modelValue:{},serializedValue:{}}}connectedCallback(){super.connectedCallback(),this.setAttribute("role","group"),this.initComplete.then(()=>{this.__isInitialModelValue=!1,this.__isInitialSerializedValue=!1,this.__initInteractionStates()})}disconnectedCallback(){super.disconnectedCallback(),this.__hasActiveOutsideClickHandling&&(document.removeEventListener("click",this._checkForOutsideClick),this.__hasActiveOutsideClickHandling=!1),this.__descriptionElementsInParentChain.clear()}__initInteractionStates(){this.formElements.forEach(r=>{typeof r.initInteractionState=="function"&&r.initInteractionState()})}_triggerInitialModelValueChangedEvent(){this.registrationComplete.then(()=>{this._dispatchInitialModelValueChangedEvent()})}updated(r){super.updated(r),r.has("disabled")&&(this.disabled?this.__requestChildrenToBeDisabled():this.__retractRequestChildrenToBeDisabled()),r.has("focused")&&this.focused===!0&&this.__setupOutsideClickHandling()}__setupOutsideClickHandling(){this.__hasActiveOutsideClickHandling||(document.addEventListener("click",this._checkForOutsideClick),this.__hasActiveOutsideClickHandling=!0)}_checkForOutsideClick(r){!this.contains(r.target)&&(this.touched=!0)}__requestChildrenToBeDisabled(){this.formElements.forEach(r=>{r.makeRequestToBeDisabled&&r.makeRequestToBeDisabled()})}__retractRequestChildrenToBeDisabled(){this.formElements.forEach(r=>{r.retractRequestToBeDisabled&&r.retractRequestToBeDisabled()})}_inputGroupTemplate(){return W`
        <div class="input-group">
          <slot></slot>
        </div>
      `}submitGroup(){this.submitted=!0,this.formElements.forEach(r=>{typeof r.submitGroup=="function"?r.submitGroup():r.submitted=!0})}resetGroup(){this.formElements.forEach(r=>{typeof r.resetGroup=="function"?r.resetGroup():typeof r.reset=="function"&&r.reset()}),this.resetInteractionState()}clearGroup(){this.formElements.forEach(r=>{typeof r.clearGroup=="function"?r.clearGroup():typeof r.clear=="function"&&r.clear()}),this.resetInteractionState()}resetInteractionState(){this.submitted=!1,this.touched=!1,this.dirty=!1,this.formElements.forEach(r=>{typeof r.resetInteractionState=="function"&&r.resetInteractionState()})}_getFromAllFormElementsFilter(r,n){return!r.disabled}_getFromAllFormElements(r,n){const i={},s=n||this._getFromAllFormElementsFilter;return this.formElements._keys().forEach(o=>{const a=this.formElements[o];a instanceof Wh?i[o]=a.filter(l=>s(l,r)).map(l=>l[r]):s(a,r)&&(typeof a._getFromAllFormElements=="function"?i[o]=a._getFromAllFormElements(r):i[o]=a[r])}),i}_setValueForAllFormElements(r,n){this.formElements.forEach(i=>{i[r]=n})}_setValueMapForAllFormElements(r,n){n&&typeof n=="object"&&Object.keys(n).forEach(i=>{Array.isArray(this.formElements[i])&&this.formElements[i].forEach((s,o)=>{s[r]=n[i][o]}),this.formElements[i]?this.formElements[i][r]=n[i]:this.__pendingValues[r][i]=n[i]})}_anyFormElementHas(r){return Object.keys(this.formElements).some(n=>Array.isArray(this.formElements[n])?this.formElements[n].some(i=>!!i[r]):!!this.formElements[n][r])}_anyFormElementHasFeedbackFor(r){return Object.keys(this.formElements).some(n=>Array.isArray(this.formElements[n])?this.formElements[n].some(i=>!!(i.hasFeedbackFor&&i.hasFeedbackFor.includes(r))):!!(this.formElements[n].hasFeedbackFor&&this.formElements[n].hasFeedbackFor.includes(r)))}_everyFormElementHas(r){return Object.keys(this.formElements).every(n=>Array.isArray(this.formElements[n])?this.formElements[n].every(i=>!!i[r]):!!this.formElements[n][r])}__onChildValidatePerformed(r){r&&this.isRegisteredFormElement(r.target)&&this.validate()}_syncFocused(){this.focused=this._anyFormElementHas("focused")}_onFocusOut(r){const n=this.formElements[this.formElements.length-1];r.target===n&&(this.touched=!0),this.focused=!1}_syncDirty(){this.dirty=this._anyFormElementHas("dirty")}__storeAllDescriptionElementsInParentChain(){let n=this;for(;n;){const i=n._getAriaDescriptionElements();hE(i,{reverse:!0}).forEach(o=>{o.getAttribute("slot")==="feedback"&&this.__descriptionElementsInParentChain.add(o)}),n=n._parentFormGroup}}__linkParentMessages(r){this.__descriptionElementsInParentChain.forEach(n=>{typeof r.addToAriaDescribedBy=="function"&&r.addToAriaDescribedBy(n,{reorder:!1})})}__unlinkParentMessages(r){this.__descriptionElementsInParentChain.forEach(n=>{typeof r.removeFromAriaDescribedBy=="function"&&r.removeFromAriaDescribedBy(n)})}addFormElement(r,n){if(super.addFormElement(r,n),this.disabled&&r.makeRequestToBeDisabled(),this.__descriptionElementsInParentChain.size||this.__storeAllDescriptionElementsInParentChain(),this.__linkParentMessages(r),this.validate({clearCurrentResult:!0}),!r.modelValue){const i=this.__pendingValues;i.modelValue&&i.modelValue[r.name]?r.modelValue=i.modelValue[r.name]:i.serializedValue&&i.serializedValue[r.name]&&(r.serializedValue=i.serializedValue[r.name])}}get _initialModelValue(){return this._getFromAllFormElements("_initialModelValue")}removeFormElement(r){super.removeFormElement(r),this.validate({clearCurrentResult:!0}),typeof r.removeFromAriaLabelledBy=="function"&&this._labelNode&&r.removeFromAriaLabelledBy(this._labelNode,{reorder:!1}),this.__unlinkParentMessages(r)}_isEmpty(){return this.formElements.every(r=>r._isEmpty?.())}},pE=He(mD);class Xc extends fE(ba){static get properties(){return{readOnly:{type:Boolean,attribute:"readonly",reflect:!0},type:{type:String,reflect:!0},placeholder:{type:String,reflect:!0}}}get slots(){return{...super.slots,input:()=>{const t=document.createElement("input"),r=this.getAttribute("value");return r&&t.setAttribute("value",r),t}}}get _inputNode(){return super._inputNode}constructor(){super(),this.readOnly=!1,this.type="text",this.placeholder=""}requestUpdate(t,r,n){super.requestUpdate(t,r,n),t==="readOnly"&&this.__delegateReadOnly()}firstUpdated(t){super.firstUpdated(t),this.__delegateReadOnly()}updated(t){super.updated(t),t.has("type")&&(this._inputNode.type=this.type),t.has("placeholder")&&(this._inputNode.placeholder=this.placeholder),t.has("disabled")&&(this._inputNode.disabled=this.disabled,this.validate()),t.has("name")&&(this._inputNode.name=this.name),t.has("autocomplete")&&(this._inputNode.autocomplete=this.autocomplete)}__delegateReadOnly(){this._inputNode&&(this._inputNode.readOnly=this.readOnly)}}var Lp=class extends Xc{static get styles(){return[...super.styles,pa,CM]}connectedCallback(){if(super.connectedCallback(),this._inputNode&&this.size){const e=parseInt(this.size,10);e>0&&(this._inputNode.size=e)}}};se([V({type:Number,reflect:!0})],Lp.prototype,"size",void 0);customElements.get("craft-input")||customElements.define("craft-input",Lp);const Gt=e=>e??ht;class ol extends Kc{static validatorName="IsAcceptedFile";static checkFileSize(t,r){return t<=r}static getExtension(t){return t?.slice(t.lastIndexOf("."))}static isExtensionAllowed(t,r){return r?.find(n=>n.toUpperCase()===t.toUpperCase())}static isFileTypeAllowed(t,r){return r?.find(n=>n.toUpperCase()===t.toUpperCase())}execute(t,r=this.param){let n,i;const s=this.constructor,{allowedFileTypes:o,allowedFileExtensions:a,maxFileSize:l}=r;return o?.length?(n=t.some(c=>!s.isFileTypeAllowed(c.type,o)),n):a?.length?(i=t.some(c=>!s.isExtensionAllowed(s.getExtension(c.name),a)),i):t.findIndex(c=>!s.checkFileSize(c.size,l))>-1}static async getMessage(){return""}}class gD extends Kc{static validatorName="DuplicateFileNames";constructor(t,r){super(t,r),this.type="info"}execute(t,r=this.param){return r.show}static async getMessage(){return Xl().msg("lion-input-file:uploadTextDuplicateFileName")}}const bD=524288e3,Fd={type:"FILE_TYPE",size:"FILE_SIZE"},js={fail:"FAIL",pass:"SUCCESS"};class vD{constructor(t,r){this.failedProp=[],this.systemFile=t,this._acceptCriteria=r,this.uploadFileStatus(),this.failedProp.length===0&&this.createDownloadUrl(t)}_getFileNameExtension(t){return t.slice(t.lastIndexOf("."))}uploadFileStatus(){if(this._acceptCriteria.allowedFileExtensions.length){const t=this._getFileNameExtension(this.systemFile.name);ol.isExtensionAllowed(t,this._acceptCriteria.allowedFileExtensions)||(this.status=js.fail,this.failedProp.push(Fd.type))}else if(this._acceptCriteria.allowedFileTypes.length){const t=this.systemFile.type;ol.isFileTypeAllowed(t,this._acceptCriteria.allowedFileTypes)||(this.status=js.fail,this.failedProp.push(Fd.type))}ol.checkFileSize(this.systemFile.size,this._acceptCriteria.maxFileSize)?this.status!==js.fail&&(this.status=js.pass):(this.status=js.fail,this.failedProp.push(Fd.size))}createDownloadUrl(t){this.downloadUrl=window.URL.createObjectURL(t)}}const rv=(e,t,r)=>{const n=new Map;for(let i=t;i<=r;i++)n.set(e[i],i);return n},yD=Sv(class extends Ev{constructor(e){if(super(e),e.type!==xv.CHILD)throw Error("repeat() can only be used in text expressions")}dt(e,t,r){let n;r===void 0?r=t:t!==void 0&&(n=t);const i=[],s=[];let o=0;for(const a of e)i[o]=n?n(a,o):o,s[o]=r(a,o),o++;return{values:s,keys:i}}render(e,t,r){return this.dt(e,t,r).values}update(e,[t,r,n]){const i=gM(e),{values:s,keys:o}=this.dt(t,r,n);if(!Array.isArray(i))return this.ut=o,s;const a=this.ut??=[],l=[];let u,c,d=0,p=i.length-1,m=0,h=s.length-1;for(;d<=p&&m<=h;)if(i[d]===null)d++;else if(i[p]===null)p--;else if(a[d]===o[m])l[m]=ui(i[d],s[m]),d++,m++;else if(a[p]===o[h])l[h]=ui(i[p],s[h]),p--,h--;else if(a[d]===o[h])l[h]=ui(i[d],s[h]),qs(e,l[h+1],i[d]),d++,h--;else if(a[p]===o[m])l[m]=ui(i[p],s[m]),qs(e,i[d],i[p]),p--,m++;else if(u===void 0&&(u=rv(o,m,h),c=rv(a,d,p)),u.has(a[d]))if(u.has(a[p])){const f=c.get(o[m]),b=f!==void 0?i[f]:null;if(b===null){const y=qs(e,i[d]);ui(y,s[m]),l[m]=y}else l[m]=ui(b,s[m]),qs(e,i[d],b),i[f]=null;m++}else Td(i[p]),p--;else Td(i[d]),d++;for(;m<=h;){const f=qs(e,l[h+1]);ui(f,s[m]),l[m++]=f}for(;d<=p;){const f=i[d++];f!==null&&Td(f)}return this.ut=o,mM(e,l),Wd}}),mE=e=>{switch(e){case"bg-BG":return Y(()=>import("./bg-BG.js"),__vite__mapDeps([34,35]),import.meta.url);case"bg":return Y(()=>import("./bg2.js"),[],import.meta.url);case"cs-CZ":return Y(()=>import("./cs-CZ.js"),__vite__mapDeps([36,37]),import.meta.url);case"cs":return Y(()=>import("./cs2.js"),[],import.meta.url);case"de-DE":return Y(()=>import("./de-DE.js"),__vite__mapDeps([38,39]),import.meta.url);case"de":return Y(()=>import("./de2.js"),[],import.meta.url);case"en-AU":return Y(()=>import("./en-AU.js"),__vite__mapDeps([40,41]),import.meta.url);case"en-GB":return Y(()=>import("./en-GB.js"),__vite__mapDeps([42,41]),import.meta.url);case"en-US":return Y(()=>import("./en-US.js"),__vite__mapDeps([43,41]),import.meta.url);case"en-PH":case"en":return Y(()=>import("./en2.js"),[],import.meta.url);case"es-ES":return Y(()=>import("./es-ES.js"),__vite__mapDeps([44,45]),import.meta.url);case"es":return Y(()=>import("./es2.js"),[],import.meta.url);case"fr-FR":return Y(()=>import("./fr-FR.js"),__vite__mapDeps([46,47]),import.meta.url);case"fr-BE":return Y(()=>import("./fr-BE.js"),__vite__mapDeps([48,47]),import.meta.url);case"fr":return Y(()=>import("./fr2.js"),[],import.meta.url);case"hu-HU":return Y(()=>import("./hu-HU.js"),__vite__mapDeps([49,50]),import.meta.url);case"hu":return Y(()=>import("./hu2.js"),[],import.meta.url);case"it-IT":return Y(()=>import("./it-IT.js"),__vite__mapDeps([51,52]),import.meta.url);case"it":return Y(()=>import("./it2.js"),[],import.meta.url);case"nl-BE":return Y(()=>import("./nl-BE.js"),__vite__mapDeps([53,54]),import.meta.url);case"nl-NL":return Y(()=>import("./nl-NL.js"),__vite__mapDeps([55,54]),import.meta.url);case"nl":return Y(()=>import("./nl2.js"),[],import.meta.url);case"pl-PL":return Y(()=>import("./pl-PL.js"),__vite__mapDeps([56,57]),import.meta.url);case"pl":return Y(()=>import("./pl2.js"),[],import.meta.url);case"ro-RO":return Y(()=>import("./ro-RO.js"),__vite__mapDeps([58,59]),import.meta.url);case"ro":return Y(()=>import("./ro2.js"),[],import.meta.url);case"ru-RU":return Y(()=>import("./ru-RU.js"),__vite__mapDeps([60,61]),import.meta.url);case"ru":return Y(()=>import("./ru2.js"),[],import.meta.url);case"sk-SK":return Y(()=>import("./sk-SK.js"),__vite__mapDeps([62,63]),import.meta.url);case"sk":return Y(()=>import("./sk2.js"),[],import.meta.url);case"uk-UA":return Y(()=>import("./uk-UA.js"),__vite__mapDeps([64,65]),import.meta.url);case"uk":return Y(()=>import("./uk2.js"),[],import.meta.url);case"zh-CN":case"zh":return Y(()=>import("./zh2.js"),[],import.meta.url);default:return Y(()=>import("./en2.js"),[],import.meta.url)}};class gE extends Wc(ma(Ne)){static get scopedElements(){return{...super.scopedElements,"lion-validation-feedback":dE}}static get properties(){return{fileList:{type:Array},multiple:{type:Boolean}}}static localizeNamespaces=[{"lion-input-file":mE},...super.localizeNamespaces];constructor(){super(),this.fileList=[],this.multiple=!1}updated(t){super.updated(t),t.has("fileList")&&this._enhanceLightDomA11y()}_enhanceLightDomA11y(){const t=this.shadowRoot?.querySelectorAll('[id^="file-feedback"]'),r=this.parentNode?.parentNode;t?.forEach(n=>{r?.addEventListener("focusin",()=>{n.setAttribute("aria-live","polite")}),r?.addEventListener("focusout",()=>{n.setAttribute("aria-live","assertive")})})}_removeFile(t){this.dispatchEvent(new CustomEvent("file-remove-requested",{detail:{removedFile:t,status:t.status,uploadResponse:t.response}}))}_validationFeedbackTemplate(t,r){return W`
      <lion-validation-feedback
        id="file-feedback-${r}"
        .feedbackData="${t}"
        aria-live="assertive"
      ></lion-validation-feedback>
    `}_listItemBeforeTemplate(t){return ht}_listItemAfterTemplate(t,r){return W`
      <button
        class="selected__list__item__remove-button"
        aria-label="${this.msgLit("lion-input-file:removeButtonLabel",{fileName:t.systemFile.name})}"
        @click=${()=>this._removeFile(t)}
      >
        ${this._removeButtonContentTemplate()}
      </button>
    `}_removeButtonContentTemplate(){return W`✖️`}_selectedListItemTemplate(t){const r=fa();return W`
      <div class="selected__list__item" status="${t.status?t.status.toLowerCase():""}">
        <div class="selected__list__item__label">
          ${this._listItemBeforeTemplate(t)}
          <span id="selected-list-item-label-${r}" class="selected__list__item__label__text">
            <span class="sr-only">${this.msgLit("lion-input-file:fileNameDescriptionLabel")}</span>
            ${t.downloadUrl&&t.status!=="LOADING"?W`
                  <a
                    class="selected__list__item__label__link"
                    href="${t.downloadUrl}"
                    target="${t.downloadUrl.startsWith("blob")?"_blank":""}"
                    rel="${Gt(t.downloadUrl.startsWith("blob")?"noopener noreferrer":void 0)}"
                    >${t.systemFile?.name}</a
                  >
                `:t.systemFile?.name}
          </span>
          ${this._listItemAfterTemplate(t,r)}
        </div>
        ${t.status==="FAIL"&&t.validationFeedback?W`
              ${yD(t.validationFeedback,n=>W`
                  ${this._validationFeedbackTemplate([n],r)}
                `)}
            `:ht}
      </div>
    `}render(){return this.fileList?.length?W`
          ${this.multiple?W`
                <ul class="selected__list">
                  ${this.fileList.map(t=>W` <li>${this._selectedListItemTemplate(t)}</li> `)}
                </ul>
              `:W` ${this._selectedListItemTemplate(this.fileList[0])} `}
        `:ht}static get styles(){return[te`
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
      `]}}function Ld(e,t=2){if(!+e)return"0 Bytes";const r=1024,n=t<0?0:t,i=[" bytes","KB","MB","GB","TB","PB","EB","ZB","YB"],s=Math.floor(Math.log(e)/Math.log(r));return`${parseFloat((e/r**s).toFixed(n))}${i[s]}`}class _D extends ma(Wc(ba)){static get scopedElements(){return{...super.scopedElements,"lion-selected-file-list":gE}}static get properties(){return{accept:{type:String},multiple:{type:Boolean,reflect:!0},buttonLabel:{type:String,attribute:"button-label"},maxFileSize:{type:Number,attribute:"max-file-size"},enableDropZone:{type:Boolean,attribute:"enable-drop-zone"},uploadOnSelect:{type:Boolean,attribute:"upload-on-select"},isDragging:{type:Boolean,attribute:"is-dragging",reflect:!0},uploadResponse:{type:Array,state:!1},_selectedFilesMetaData:{type:Array,state:!0}}}static localizeNamespaces=[{"lion-input-file":mE},...super.localizeNamespaces];static get validationTypes(){return["error","info"]}get slots(){return{...super.slots,input:()=>W`<input .value="${Gt(this.getAttribute("value"))}" />`,"file-select-button":()=>W`<button
          type="button"
          id="select-button-${this._inputId}"
          @click="${this.__openDialogOnBtnClick}"
        >
          ${this.buttonLabel}
        </button>`,after:()=>W`<div data-description></div>`,"selected-file-list":()=>({template:W`
          <lion-selected-file-list
            .fileList=${this._selectedFilesMetaData}
            .multiple=${this.multiple}
          ></lion-selected-file-list>
        `,renderAsDirectHostChild:!0})}}get _inputNode(){return super._inputNode}get _buttonNode(){return this.querySelector(`#select-button-${this._inputId}`)}get buttonLabel(){return this.__buttonLabel||this._buttonNode?.textContent?.trim()||""}set buttonLabel(t){const r=this.buttonLabel;this.__buttonLabel=t,this.requestUpdate("buttonLabel",r)}get _focusableNode(){return this._buttonNode}get _isDragAndDropSupported(){return"draggable"in document.createElement("div")}constructor(){super(),this.type="file",this._selectedFilesMetaData=[],this.uploadResponse=[],this.__initialUploadResponse=this.uploadResponse,this.uploadOnSelect=!1,this.multiple=!1,this.enableDropZone=!1,this.maxFileSize=bD,this.accept="",this.buttonLabel="",this._initialButtonLabel="",this.modelValue=[],this._onRemoveFile=this._onRemoveFile.bind(this),this.__duplicateFileNamesValidator=new gD({show:!1}),this.__previouslyParsedFiles=null}get _fileListNode(){return Array.from(this.children).find(t=>t.slot==="selected-file-list")}connectedCallback(){super.connectedCallback(),this.__initialUploadResponse=this.uploadResponse,this._initialButtonLabel=this.buttonLabel,this._inputNode.addEventListener("change",this._onChange),this._inputNode.addEventListener("click",this._onClick)}disconnectedCallback(){super.disconnectedCallback(),this._inputNode.removeEventListener("change",this._onChange),this._inputNode.removeEventListener("click",this._onClick)}onLocaleUpdated(){super.onLocaleUpdated(),this.multiple?this.buttonLabel=this._initialButtonLabel||this.msgLit("lion-input-file:selectTextMultipleFile"):this.buttonLabel=this._initialButtonLabel||this.msgLit("lion-input-file:selectTextSingleFile")}get operationMode(){return"upload"}get _acceptCriteria(){let t=[],r=[];if(this.accept){const n=this.accept.replace(/\s+/g,"").split(",");t=n.filter(i=>i.includes("/")),r=n.filter(i=>!i.includes("/"))}return{allowedFileTypes:t,allowedFileExtensions:r,maxFileSize:this.maxFileSize}}reset(){super.reset(),this._selectedFilesMetaData=[],this.uploadResponse=this.__initialUploadResponse,this.modelValue=[],this.dirty=!1}clear(){this._selectedFilesMetaData=[],this.uploadResponse=[],this.modelValue=[]}_showFeedbackConditionFor(t,r){return super._showFeedbackConditionFor(t,r)&&!(this.validationStates.error?.FileTypeAllowed||this.validationStates.error?.FileSizeAllowed)}parser(){if(this.__previouslyParsedFiles===this._inputNode.files)return this.modelValue;this.__previouslyParsedFiles=this._inputNode.files;const t=this._inputNode.files?Array.from(this._inputNode.files):[];return this.multiple?[...this.modelValue??[],...t]:t}formatter(t){return this._inputNode?.value||""}__setupDragDropEventListeners(){const t=this.shadowRoot?.querySelector(".input-file__drop-zone");["dragenter","dragover","dragleave"].forEach(r=>{t?.addEventListener(r,n=>{n.preventDefault(),n.stopPropagation(),this.isDragging=r!=="dragleave"},!1)}),window.addEventListener("drop",r=>{r.target===this._inputNode&&r.preventDefault(),this.isDragging=!1},!1)}firstUpdated(t){super.firstUpdated(t),this.__setupFileValidators(),this._inputNode&&(this._inputNode.type=this.type,this._inputNode.setAttribute("tabindex","-1"),this._inputNode.multiple=this.multiple,this.accept.length&&(this._inputNode.accept=this.accept)),this.enableDropZone&&this._isDragAndDropSupported&&(this.__setupDragDropEventListeners(),this.setAttribute("drop-zone","")),this._fileListNode.addEventListener("file-remove-requested",this._onRemoveFile)}updated(t){super.updated(t),t.has("disabled")&&(this._inputNode.disabled=this.disabled,this.validate()),t.has("buttonLabel")&&this._buttonNode&&(this._buttonNode.textContent=this.buttonLabel),t.has("name")&&(this._inputNode.name=this.name),t.has("_ariaLabelledNodes")&&this.__syncAriaLabelledByAttributesToButton(),t.has("_ariaDescribedNodes")&&this.__syncAriaDescribedByAttributesToButton(),t.has("uploadResponse")&&(this._selectedFilesMetaData.length===0&&this.uploadResponse.forEach(r=>{const n={systemFile:{name:r.name},response:r,status:r.status,validationFeedback:[{message:r.errorMessage}]};this._selectedFilesMetaData=[...this._selectedFilesMetaData,n]}),this._selectedFilesMetaData.forEach(r=>{!this.uploadResponse.some(n=>n.name===r.systemFile.name)&&this.uploadOnSelect?this.__removeFileFromList(r):(this.uploadResponse.forEach(n=>{n.name===r.systemFile.name&&(r.response=n,r.downloadUrl=n.downloadUrl?n.downloadUrl:r.downloadUrl,r.status=n.status,r.validationFeedback=[{type:typeof n.errorMessage=="string"&&n.errorMessage?.length>0?"error":"success",message:n.errorMessage??""}])}),this._selectedFilesMetaData=[...this._selectedFilesMetaData])}),this._updateUploadButtonDescription())}__computeNewAddedFiles(t){const r=t.filter(n=>this._selectedFilesMetaData.findIndex(i=>i.systemFile.name===n.name)===-1);return this.__duplicateFileNamesValidator.param={show:t.length!==r.length},this.validate(),r}_processDroppedFiles(t){if(t.preventDefault(),this.isDragging=!1,!(t.dataTransfer&&t.dataTransfer.items.length>1&&!this.multiple||!t.dataTransfer?.files)){if(this._inputNode.files=t.dataTransfer.files,this.multiple){const n=this.__computeNewAddedFiles(Array.from(t.dataTransfer.files));this.modelValue=[...this.modelValue??[],...n]}else this.modelValue=Array.from(t.dataTransfer.files);this._processFiles(Array.from(t.dataTransfer.files))}}_onChange(t){this.touched=!0,this._onUserInputChanged(),this._processFiles(t?.target?.files)}_onClick(t){t.target.value=""}__syncAriaLabelledByAttributesToButton(){if(this._inputNode.hasAttribute("aria-labelledby")){const t=this._inputNode.getAttribute("aria-labelledby");this._buttonNode?.setAttribute("aria-labelledby",`select-button-${this._inputId} ${t}`)}}__syncAriaDescribedByAttributesToButton(){if(this._inputNode.hasAttribute("aria-describedby")){const t=this._inputNode.getAttribute("aria-describedby")||"";this._buttonNode?.setAttribute("aria-describedby",t)}}__setupFileValidators(){this.defaultValidators=[new ol(this._acceptCriteria),this.__duplicateFileNamesValidator]}_processFiles(t){const r=this.__computeNewAddedFiles(Array.from(t));!this.multiple&&r.length>0&&(this._selectedFilesMetaData=[],this.uploadResponse=[]);let n;for(const s of r.values())n=new vD(s,this._acceptCriteria),n.failedProp?.length?(this._handleErroredFiles(n),this.uploadResponse=[...this.uploadResponse,{name:n.systemFile.name,status:"FAIL",errorMessage:n.validationFeedback[0].message}]):this.uploadResponse=[...this.uploadResponse,{name:n.systemFile.name,status:"SUCCESS"}],this._selectedFilesMetaData=[...this._selectedFilesMetaData,n],this._handleErrors();const i=this._selectedFilesMetaData.filter(({systemFile:s,status:o})=>r.includes(s)&&o==="SUCCESS").map(({systemFile:s})=>s);i.length>0&&this._dispatchFileListChangeEvent(i)}_dispatchFileListChangeEvent(t){this.dispatchEvent(new CustomEvent("file-list-changed",{detail:{newFiles:t}}))}_handleErrors(){let t=!1;if(this._selectedFilesMetaData.forEach(r=>{r.failedProp&&r.failedProp.length>0&&(t=!0)}),t)this.hasFeedbackFor?.push("error"),this.shouldShowFeedbackFor.push("error");else if(this._prevHasErrors&&this.hasFeedbackFor.includes("error")){const r=this.hasFeedbackFor.indexOf("error");this.hasFeedbackFor.slice(r,r+1);const n=this.shouldShowFeedbackFor.indexOf("error");this.shouldShowFeedbackFor.slice(n,n+1)}this._prevHasErrors=t}_handleErroredFiles(t){t.validationFeedback=[];const{allowedFileExtensions:r,allowedFileTypes:n}=this._acceptCriteria;let i=[],s=0,o;r.length?(i=r,o=i.pop(),s=i.length):n.length&&(n.forEach(u=>{if(u.endsWith("/*"))i.push(u.slice(0,-2));else if(u==="text/plain")i.push("text");else{const c=u.indexOf("/"),d=u.slice(c+1);if(!d.includes("+"))i.push(`.${d}`);else{const p=d.split("+");i.push(`.${p[0]}`)}}}),o=i.pop(),s=i.length);let a="";o?s?a=`${this.msgLit("lion-input-file:allowedFileValidatorComplex",{allowedTypesArray:i.join(", "),allowedTypesLastItem:o,maxSize:Ld(this.maxFileSize)})}`:a=`${this.msgLit("lion-input-file:allowedFileValidatorSimple",{allowedType:o,maxSize:Ld(this.maxFileSize)})}`:a=`${this.msgLit("lion-input-file:allowedFileSize",{maxSize:Ld(this.maxFileSize)})}`;const l={message:a,type:"error"};t.validationFeedback?.push(l)}_updateUploadButtonDescription(){const t=[];let r;this._selectedFilesMetaData.forEach(i=>{i.status==="FAIL"&&(r=i.validationFeedback?i.validationFeedback[0].message.toString():"",t.push(i.systemFile.name))});const n=this.querySelector('[slot="after"]');if(n)if(!this._selectedFilesMetaData||this._selectedFilesMetaData.length===0)this.uploadOnSelect?n.textContent=this.msgLit("lion-input-file:noFilesUploaded"):n.textContent=this.msgLit("lion-input-file:noFilesSelected");else if(this._selectedFilesMetaData.length===1){const{name:i}=this._selectedFilesMetaData[0].systemFile;this.uploadOnSelect?n.textContent=r||this.msgLit("lion-input-file:fileUploaded")+(i??""):n.textContent=r||this.msgLit("lion-input-file:fileSelected")+(i??"")}else this.uploadOnSelect?n.textContent=`${this.msgLit("lion-input-file:filesUploaded",{numberOfFiles:this._selectedFilesMetaData.length})} ${r?this.msgLit("lion-input-file:generalValidatorMessage",{validatorMessage:r,listOfErroneousFiles:t.join(", ")}):""}`:n.textContent=`${this.msgLit("lion-input-file:filesSelected",{numberOfFiles:this._selectedFilesMetaData.length})} ${r?this.msgLit("lion-input-file:generalValidatorMessage",{validatorMessage:r,listOfErroneousFiles:t.join(", ")}):""}`}__removeFileFromList(t){this._selectedFilesMetaData=this._selectedFilesMetaData.filter(r=>r.systemFile.name!==t.systemFile.name),this.modelValue&&(this.modelValue=this.modelValue.filter(r=>r.name!==t.systemFile.name)),this._inputNode.value="",this._handleErrors(),this._updateUploadButtonDescription()}_onRemoveFile(t){if(this.disabled)return;const{removedFile:r}=t.detail;!this.uploadOnSelect&&r&&this.__removeFileFromList(r),this._removeFile(r)}_removeFile(t){this.dispatchEvent(new CustomEvent("file-removed",{detail:{removedFile:t,status:t.status,uploadResponse:t.response}}))}_reflectBackOn(){return!1}_isEmpty(){return this.modelValue?.length===0}_dropZoneTemplate(){return W`
      <div @drop="${this._processDroppedFiles}" class="input-file__drop-zone">
        <div class="input-file__drop-zone__text">
          ${this.msgLit("lion-input-file:dragAndDropText")}
        </div>
        <slot name="file-select-button"></slot>
      </div>
    `}_inputGroupAfterTemplate(){return W` <slot name="selected-file-list"></slot> `}_inputGroupInputTemplate(){return W`
      <slot name="input"> </slot>
      <slot name="after"> </slot>
      ${this.enableDropZone&&this._isDragAndDropSupported?this._dropZoneTemplate():W`
            <div class="input-group__file-select-button">
              <slot name="file-select-button"></slot>
            </div>
          `}
    `}static get styles(){return[super.styles,te`
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
      `]}__openDialogOnBtnClick(t){t.preventDefault(),t.stopPropagation(),this._inputNode.click()}}var wD=class extends gE{static get styles(){return[...super.styles,te`
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
      `]}_listItemAfterTemplate(e,t){return W`
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
    `}_removeButtonContentTemplate(){return W`<craft-icon name="x"></craft-icon>`}_listItemBeforeTemplate(e){return W`<img src="${e.downloadUrl}" alt="" class="preview-thumb" />`}},ED=te`
  /* Add any craft-specific styles for input-file here */
  ::slotted([slot='selected-file-list']) {
    margin-block-start: var(--c-spacing-lg);
  }
`,xD=class extends _D{static get styles(){return[...super.styles,pa,ED]}get slots(){return{...super.slots,"file-select-button":()=>W`<craft-button
          type="button"
          id="select-button-${this._inputId}"
          @click="${this.__openDialogOnBtnClick}"
        >
          ${this.buttonLabel}
        </craft-button>`}}static get scopedElements(){return{...super.scopedElements,"lion-selected-file-list":wD}}};customElements.get("craft-input-file")||customElements.define("craft-input-file",xD);var SD=class extends Event{constructor(){super("wa-load",{bubbles:!0,cancelable:!1,composed:!0})}};var CD=class extends Event{constructor(){super("wa-error",{bubbles:!0,cancelable:!1,composed:!0})}},AD=`:host {
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
`,Ws=Symbol(),za=Symbol(),Rd,Md=new Map,kt=class extends Ht{constructor(){super(...arguments),this.svg=null,this.autoWidth=!1,this.swapOpacity=!1,this.label="",this.library="default",this.resolveIcon=async(e,t)=>{let r;if(t?.spriteSheet){this.hasUpdated||await this.updateComplete,this.svg=W`<svg part="svg">
        <use part="use" href="${e}"></use>
      </svg>`,await this.updateComplete;const n=this.shadowRoot.querySelector("[part='svg']");return typeof t.mutator=="function"&&t.mutator(n,this),this.svg}try{if(r=await fetch(e,{mode:"cors"}),!r.ok)return r.status===410?Ws:za}catch{return za}try{const n=document.createElement("div");n.innerHTML=await r.text();const i=n.firstElementChild;if(i?.tagName?.toLowerCase()!=="svg")return Ws;Rd||(Rd=new DOMParser);const o=Rd.parseFromString(i.outerHTML,"text/html").body.querySelector("svg");return o?(o.part.add("svg"),document.adoptNode(o)):Ws}catch{return Ws}}}connectedCallback(){super.connectedCallback(),VL(this)}firstUpdated(e){super.firstUpdated(e),this.setIcon()}disconnectedCallback(){super.disconnectedCallback(),BL(this)}getIconSource(){const e=Sd(this.library),t=this.family||HL();return this.name&&e?{url:e.resolver(this.name,t,this.variant,this.autoWidth),fromLibrary:!0}:{url:this.src,fromLibrary:!1}}handleLabelChange(){typeof this.label=="string"&&this.label.length>0?(this.setAttribute("role","img"),this.setAttribute("aria-label",this.label),this.removeAttribute("aria-hidden")):(this.removeAttribute("role"),this.removeAttribute("aria-label"),this.setAttribute("aria-hidden","true"))}async setIcon(){const{url:e,fromLibrary:t}=this.getIconSource(),r=t?Sd(this.library):void 0;if(!e){this.svg=null;return}let n=Md.get(e);n||(n=this.resolveIcon(e,r),Md.set(e,n));const i=await n;if(i===za&&Md.delete(e),e===this.getIconSource().url){if(aE(i)){this.svg=i;return}switch(i){case za:case Ws:this.svg=null,this.dispatchEvent(new CD);break;default:this.svg=i.cloneNode(!0),r?.mutator?.(this.svg,this),this.dispatchEvent(new SD)}}}updated(e){super.updated(e);const t=Sd(this.library),r=this.shadowRoot?.querySelector("svg");r&&t?.mutator?.(r,this)}render(){return this.hasUpdated?this.svg:W`<svg part="svg" fill="currentColor" width="16" height="16"></svg>`}};kt.css=AD;$([ir()],kt.prototype,"svg",2);$([V({reflect:!0})],kt.prototype,"name",2);$([V({reflect:!0})],kt.prototype,"family",2);$([V({reflect:!0})],kt.prototype,"variant",2);$([V({attribute:"auto-width",type:Boolean,reflect:!0})],kt.prototype,"autoWidth",2);$([V({attribute:"swap-opacity",type:Boolean,reflect:!0})],kt.prototype,"swapOpacity",2);$([V()],kt.prototype,"src",2);$([V()],kt.prototype,"label",2);$([V({reflect:!0})],kt.prototype,"library",2);$([ar("label")],kt.prototype,"handleLabelChange",1);$([ar(["family","name","library","variant","src","autoWidth","swapOpacity"])],kt.prototype,"setIcon",1);kt=$([Fr("wa-icon")],kt);var TD=te``,kD=class extends kt{static get styles(){return[kt.styles,TD]}};customElements.get("craft-icon")||customElements.define("craft-icon",kD);var OD=function(e,t,r,n,i){if(n==="m")throw new TypeError("Private method is not writable");if(n==="a"&&!i)throw new TypeError("Private accessor was defined without a setter");if(typeof t=="function"?e!==t||!i:!t.has(e))throw new TypeError("Cannot write private member to an object whose class did not declare it");return n==="a"?i.call(e,r):i?i.value=r:t.set(e,r),r},nv=function(e,t,r,n){if(r==="a"&&!n)throw new TypeError("Private accessor was defined without a getter");if(typeof t=="function"?e!==t||!n:!t.has(e))throw new TypeError("Cannot read private member from an object whose class did not declare it");return r==="m"?n:r==="a"?n.call(e):n?n.value:t.get(e)},Xs;class ND{formatToParts(t){const r=[];for(const n of t)r.push({type:"element",value:n}),r.push({type:"literal",value:", "});return r.slice(0,-1)}}const PD=typeof Intl<"u"&&Intl.ListFormat||ND,ID=[["years","year"],["months","month"],["weeks","week"],["days","day"],["hours","hour"],["minutes","minute"],["seconds","second"],["milliseconds","millisecond"]],FD={minimumIntegerDigits:2};class LD{constructor(t,r={}){Xs.set(this,void 0);let n=String(r.style||"short");n!=="long"&&n!=="short"&&n!=="narrow"&&n!=="digital"&&(n="short");let i=n==="digital"?"numeric":n;const s=r.hours||i;i=s==="2-digit"?"numeric":s;const o=r.minutes||i;i=o==="2-digit"?"numeric":o;const a=r.seconds||i;i=a==="2-digit"?"numeric":a;const l=r.milliseconds||i;OD(this,Xs,{locale:t,style:n,years:r.years||n==="digital"?"short":n,yearsDisplay:r.yearsDisplay==="always"?"always":"auto",months:r.months||n==="digital"?"short":n,monthsDisplay:r.monthsDisplay==="always"?"always":"auto",weeks:r.weeks||n==="digital"?"short":n,weeksDisplay:r.weeksDisplay==="always"?"always":"auto",days:r.days||n==="digital"?"short":n,daysDisplay:r.daysDisplay==="always"?"always":"auto",hours:s,hoursDisplay:r.hoursDisplay==="always"||n==="digital"?"always":"auto",minutes:o,minutesDisplay:r.minutesDisplay==="always"||n==="digital"?"always":"auto",seconds:a,secondsDisplay:r.secondsDisplay==="always"||n==="digital"?"always":"auto",milliseconds:l,millisecondsDisplay:r.millisecondsDisplay==="always"?"always":"auto"},"f")}resolvedOptions(){return nv(this,Xs,"f")}formatToParts(t){const r=[],n=nv(this,Xs,"f"),i=n.style,s=n.locale;for(const[o,a]of ID){const l=t[o];if(n[`${o}Display`]==="auto"&&!l)continue;const u=n[o],c=u==="2-digit"?FD:u==="numeric"?{}:{style:"unit",unit:a,unitDisplay:u};let d=new Intl.NumberFormat(s,c).format(l);o==="months"&&(u==="narrow"||i==="narrow"&&d.endsWith("m"))&&(d=d.replace(/(\d+)m$/,"$1mo")),r.push(d)}return new PD(s,{type:"unit",style:i==="digital"?"short":i}).formatToParts(r)}format(t){return this.formatToParts(t).map(r=>r.value).join("")}}Xs=new WeakMap;const bE=/^[-+]?P(?:(\d+)Y)?(?:(\d+)M)?(?:(\d+)W)?(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?)?$/,Ql=["year","month","week","day","hour","minute","second","millisecond"],RD=e=>bE.test(e);class Jt{constructor(t=0,r=0,n=0,i=0,s=0,o=0,a=0,l=0){this.years=t,this.months=r,this.weeks=n,this.days=i,this.hours=s,this.minutes=o,this.seconds=a,this.milliseconds=l,this.years||(this.years=0),this.sign||(this.sign=Math.sign(this.years)),this.months||(this.months=0),this.sign||(this.sign=Math.sign(this.months)),this.weeks||(this.weeks=0),this.sign||(this.sign=Math.sign(this.weeks)),this.days||(this.days=0),this.sign||(this.sign=Math.sign(this.days)),this.hours||(this.hours=0),this.sign||(this.sign=Math.sign(this.hours)),this.minutes||(this.minutes=0),this.sign||(this.sign=Math.sign(this.minutes)),this.seconds||(this.seconds=0),this.sign||(this.sign=Math.sign(this.seconds)),this.milliseconds||(this.milliseconds=0),this.sign||(this.sign=Math.sign(this.milliseconds)),this.blank=this.sign===0}abs(){return new Jt(Math.abs(this.years),Math.abs(this.months),Math.abs(this.weeks),Math.abs(this.days),Math.abs(this.hours),Math.abs(this.minutes),Math.abs(this.seconds),Math.abs(this.milliseconds))}static from(t){var r;if(typeof t=="string"){const n=String(t).trim(),i=n.startsWith("-")?-1:1,s=(r=n.match(bE))===null||r===void 0?void 0:r.slice(1).map(o=>(Number(o)||0)*i);return s?new Jt(...s):new Jt}else if(typeof t=="object"){const{years:n,months:i,weeks:s,days:o,hours:a,minutes:l,seconds:u,milliseconds:c}=t;return new Jt(n,i,s,o,a,l,u,c)}throw new RangeError("invalid duration")}static compare(t,r){const n=Date.now(),i=Math.abs(iv(n,Jt.from(t)).getTime()-n),s=Math.abs(iv(n,Jt.from(r)).getTime()-n);return i>s?-1:i<s?1:0}toLocaleString(t,r){return new LD(t,r).format(this)}}function iv(e,t){const r=new Date(e);return t.sign<0?(r.setUTCSeconds(r.getUTCSeconds()+t.seconds),r.setUTCMinutes(r.getUTCMinutes()+t.minutes),r.setUTCHours(r.getUTCHours()+t.hours),r.setUTCDate(r.getUTCDate()+t.weeks*7+t.days),r.setUTCMonth(r.getUTCMonth()+t.months),r.setUTCFullYear(r.getUTCFullYear()+t.years)):(r.setUTCFullYear(r.getUTCFullYear()+t.years),r.setUTCMonth(r.getUTCMonth()+t.months),r.setUTCDate(r.getUTCDate()+t.weeks*7+t.days),r.setUTCHours(r.getUTCHours()+t.hours),r.setUTCMinutes(r.getUTCMinutes()+t.minutes),r.setUTCSeconds(r.getUTCSeconds()+t.seconds)),r}function MD(e,t="second",r=Date.now()){const n=e.getTime()-r;if(n===0)return new Jt;const i=Math.sign(n),s=Math.abs(n),o=Math.floor(s/1e3),a=Math.floor(o/60),l=Math.floor(a/60),u=Math.floor(l/24),c=Math.floor(u/30),d=Math.floor(c/12),p=Ql.indexOf(t)||Ql.length;return new Jt(p>=0?d*i:0,p>=1?(c-d*12)*i:0,0,p>=3?(u-c*30)*i:0,p>=4?(l-u*24)*i:0,p>=5?(a-l*60)*i:0,p>=6?(o-a*60)*i:0,p>=7?(s-o*1e3)*i:0)}function vE(e,{relativeTo:t=Date.now()}={}){if(t=new Date(t),e.blank)return e;const r=e.sign;let n=Math.abs(e.years),i=Math.abs(e.months),s=Math.abs(e.weeks),o=Math.abs(e.days),a=Math.abs(e.hours),l=Math.abs(e.minutes),u=Math.abs(e.seconds),c=Math.abs(e.milliseconds);c>=900&&(u+=Math.round(c/1e3)),(u||l||a||o||s||i||n)&&(c=0),u>=55&&(l+=Math.round(u/60)),(l||a||o||s||i||n)&&(u=0),l>=55&&(a+=Math.round(l/60)),(a||o||s||i||n)&&(l=0),o&&a>=12&&(o+=Math.round(a/24)),!o&&a>=21&&(o+=Math.round(a/24)),(o||s||i||n)&&(a=0);const d=t.getFullYear(),p=t.getMonth(),m=t.getDate();if(o>=27||n+i+o){const h=new Date(t);h.setDate(1),h.setMonth(p+i*r+1),h.setDate(0);const f=Math.max(0,m-h.getDate()),b=new Date(t);b.setFullYear(d+n*r),b.setDate(m-f),b.setMonth(p+i*r),b.setDate(m-f+o*r);const y=b.getFullYear()-t.getFullYear(),w=b.getMonth()-t.getMonth(),v=Math.abs(Math.round((Number(b)-Number(t))/864e5))+f,_=Math.abs(y*12+w);v<27?(o>=6?(s+=Math.round(o/7),o=0):o=v,i=n=0):_<=11?(i=_,n=0):(i=0,n=y*r),(i||n)&&(o=0)}return n&&(i=0),s>=4&&(i+=Math.round(s/4)),(i||n)&&(s=0),o&&s&&!i&&!n&&(s+=Math.round(o/7),o=0),new Jt(n*r,i*r,s*r,o*r,a*r,l*r,u*r,c*r)}function DD(e,t){const r=vE(e,t);if(r.blank)return[0,"second"];for(const n of Ql){if(n==="millisecond")continue;const i=r[`${n}s`];if(i)return[i,n]}return[0,"second"]}var Ie=function(e,t,r,n){if(r==="a"&&!n)throw new TypeError("Private accessor was defined without a getter");if(typeof t=="function"?e!==t||!n:!t.has(e))throw new TypeError("Cannot read private member from an object whose class did not declare it");return r==="m"?n:r==="a"?n.call(e):n?n.value:t.get(e)},Ha=function(e,t,r,n,i){if(n==="m")throw new TypeError("Private method is not writable");if(n==="a"&&!i)throw new TypeError("Private accessor was defined without a setter");if(typeof t=="function"?e!==t||!i:!t.has(e))throw new TypeError("Cannot write private member to an object whose class did not declare it");return n==="a"?i.call(e,r):i?i.value=r:t.set(e,r),r},gt,Js,Qs,Wi,bi,Kh,yE,_E,wE,EE,xE,Gh,SE,Xi;const $D=globalThis.HTMLElement||null,Dd=new Jt,sv=new Jt(0,0,0,0,0,1);class VD extends Event{constructor(t,r,n,i){super("relative-time-updated",{bubbles:!0,composed:!0}),this.oldText=t,this.newText=r,this.oldTitle=n,this.newTitle=i}}function ov(e){if(!e.date)return 1/0;if(e.format==="duration"||e.format==="elapsed"){const r=e.precision;if(r==="second")return 1e3;if(r==="minute")return 60*1e3}const t=Math.abs(Date.now()-e.date.getTime());return t<60*1e3?1e3:t<3600*1e3?60*1e3:3600*1e3}const $d=new class{constructor(){this.elements=new Set,this.time=1/0,this.timer=-1}observe(e){if(this.elements.has(e))return;this.elements.add(e);const t=e.date;if(t&&t.getTime()){const r=ov(e),n=Date.now()+r;n<this.time&&(clearTimeout(this.timer),this.timer=setTimeout(()=>this.update(),r),this.time=n)}}unobserve(e){this.elements.has(e)&&this.elements.delete(e)}update(){if(clearTimeout(this.timer),!this.elements.size)return;let e=1/0;for(const t of this.elements)e=Math.min(e,ov(t)),t.update();this.time=Math.min(3600*1e3,e),this.timer=setTimeout(()=>this.update(),this.time),this.time+=Date.now()}};class BD extends $D{constructor(){super(...arguments),gt.add(this),Js.set(this,!1),Qs.set(this,!1),bi.set(this,this.shadowRoot?this.shadowRoot:this.attachShadow?this.attachShadow({mode:"open"}):this),Xi.set(this,null)}static define(t="relative-time",r=customElements){return r.define(t,this),this}get timeZone(){var t;return((t=this.closest("[time-zone]"))===null||t===void 0?void 0:t.getAttribute("time-zone"))||this.ownerDocument.documentElement.getAttribute("time-zone")||void 0}static get observedAttributes(){return["second","minute","hour","weekday","day","month","year","time-zone-name","prefix","threshold","tense","precision","format","format-style","no-title","datetime","lang","title","aria-hidden","time-zone"]}get onRelativeTimeUpdated(){return Ie(this,Xi,"f")}set onRelativeTimeUpdated(t){Ie(this,Xi,"f")&&this.removeEventListener("relative-time-updated",Ie(this,Xi,"f")),Ha(this,Xi,typeof t=="object"||typeof t=="function"?t:null,"f"),typeof t=="function"&&this.addEventListener("relative-time-updated",t)}get second(){const t=this.getAttribute("second");if(t==="numeric"||t==="2-digit")return t}set second(t){this.setAttribute("second",t||"")}get minute(){const t=this.getAttribute("minute");if(t==="numeric"||t==="2-digit")return t}set minute(t){this.setAttribute("minute",t||"")}get hour(){const t=this.getAttribute("hour");if(t==="numeric"||t==="2-digit")return t}set hour(t){this.setAttribute("hour",t||"")}get weekday(){const t=this.getAttribute("weekday");if(t==="long"||t==="short"||t==="narrow")return t;if(this.format==="datetime"&&t!=="")return this.formatStyle}set weekday(t){this.setAttribute("weekday",t||"")}get day(){var t;const r=(t=this.getAttribute("day"))!==null&&t!==void 0?t:"numeric";if(r==="numeric"||r==="2-digit")return r}set day(t){this.setAttribute("day",t||"")}get month(){const t=this.format;let r=this.getAttribute("month");if(r!==""&&(r??(r=t==="datetime"?this.formatStyle:"short"),r==="numeric"||r==="2-digit"||r==="short"||r==="long"||r==="narrow"))return r}set month(t){this.setAttribute("month",t||"")}get year(){var t;const r=this.getAttribute("year");if(r==="numeric"||r==="2-digit")return r;if(!this.hasAttribute("year")&&new Date().getUTCFullYear()!==((t=this.date)===null||t===void 0?void 0:t.getUTCFullYear()))return"numeric"}set year(t){this.setAttribute("year",t||"")}get timeZoneName(){const t=this.getAttribute("time-zone-name");if(t==="long"||t==="short"||t==="shortOffset"||t==="longOffset"||t==="shortGeneric"||t==="longGeneric")return t}set timeZoneName(t){this.setAttribute("time-zone-name",t||"")}get prefix(){var t;return(t=this.getAttribute("prefix"))!==null&&t!==void 0?t:this.format==="datetime"?"":"on"}set prefix(t){this.setAttribute("prefix",t)}get threshold(){const t=this.getAttribute("threshold");return t&&RD(t)?t:"P30D"}set threshold(t){this.setAttribute("threshold",t)}get tense(){const t=this.getAttribute("tense");return t==="past"?"past":t==="future"?"future":"auto"}set tense(t){this.setAttribute("tense",t)}get precision(){const t=this.getAttribute("precision");return Ql.includes(t)?t:this.format==="micro"?"minute":"second"}set precision(t){this.setAttribute("precision",t)}get format(){const t=this.getAttribute("format");return t==="datetime"?"datetime":t==="relative"?"relative":t==="duration"?"duration":t==="micro"?"micro":t==="elapsed"?"elapsed":"auto"}set format(t){this.setAttribute("format",t)}get formatStyle(){const t=this.getAttribute("format-style");if(t==="long")return"long";if(t==="short")return"short";if(t==="narrow")return"narrow";const r=this.format;return r==="elapsed"||r==="micro"?"narrow":r==="datetime"?"short":"long"}set formatStyle(t){this.setAttribute("format-style",t)}get noTitle(){return this.hasAttribute("no-title")}set noTitle(t){this.toggleAttribute("no-title",t)}get datetime(){return this.getAttribute("datetime")||""}set datetime(t){this.setAttribute("datetime",t)}get date(){const t=Date.parse(this.datetime);return Number.isNaN(t)?null:new Date(t)}set date(t){this.datetime=t?.toISOString()||""}connectedCallback(){this.update()}disconnectedCallback(){$d.unobserve(this)}attributeChangedCallback(t,r,n){r!==n&&(t==="title"&&Ha(this,Js,n!==null&&(this.date&&Ie(this,gt,"m",Kh).call(this,this.date))!==n,"f"),!Ie(this,Qs,"f")&&!(t==="title"&&Ie(this,Js,"f"))&&Ha(this,Qs,(async()=>{await Promise.resolve(),this.update(),Ha(this,Qs,!1,"f")})(),"f"))}update(){const t=Ie(this,bi,"f").textContent||this.textContent||"",r=this.getAttribute("title")||"";let n=r;const i=this.date;if(typeof Intl>"u"||!Intl.DateTimeFormat||!i){Ie(this,bi,"f").textContent=t;return}const s=Date.now();Ie(this,Js,"f")||(n=Ie(this,gt,"m",Kh).call(this,i)||"",n&&!this.noTitle&&this.setAttribute("title",n));const o=MD(i,this.precision,s),a=Ie(this,gt,"m",yE).call(this,o);let l=t;const u=Ie(this,gt,"m",SE).call(this,a);u?l=Ie(this,gt,"m",xE).call(this,i):a==="duration"?l=Ie(this,gt,"m",_E).call(this,o):a==="relative"?l=Ie(this,gt,"m",wE).call(this,o):l=Ie(this,gt,"m",EE).call(this,i),l?Ie(this,gt,"m",Gh).call(this,l):this.shadowRoot===Ie(this,bi,"f")&&this.textContent&&Ie(this,gt,"m",Gh).call(this,this.textContent),(l!==t||n!==r)&&this.dispatchEvent(new VD(t,l,r,n)),(a==="relative"||a==="duration")&&!u?$d.observe(this):$d.unobserve(this)}}Js=new WeakMap,Qs=new WeakMap,bi=new WeakMap,Xi=new WeakMap,gt=new WeakSet,Wi=function(){var t;const r=((t=this.closest("[lang]"))===null||t===void 0?void 0:t.getAttribute("lang"))||this.ownerDocument.documentElement.getAttribute("lang");try{return new Intl.Locale(r??"").toString()}catch{return"default"}},Kh=function(t){return new Intl.DateTimeFormat(Ie(this,gt,"a",Wi),{day:"numeric",month:"short",year:"numeric",hour:"numeric",minute:"2-digit",timeZoneName:"short",timeZone:this.timeZone}).format(t)},yE=function(t){const r=this.format;if(r==="datetime")return"datetime";if(r==="duration"||r==="elapsed"||r==="micro")return"duration";if((r==="auto"||r==="relative")&&typeof Intl<"u"&&Intl.RelativeTimeFormat){const n=this.tense;if(n==="past"||n==="future"||Jt.compare(t,this.threshold)===1)return"relative"}return"datetime"},_E=function(t){const r=Ie(this,gt,"a",Wi),n=this.format,i=this.formatStyle,s=this.tense;let o=Dd;n==="micro"?(t=vE(t),o=sv,t.months===0&&(this.tense==="past"&&t.sign!==-1||this.tense==="future"&&t.sign!==1)&&(t=sv)):(s==="past"&&t.sign!==-1||s==="future"&&t.sign!==1)&&(t=o);const a=`${this.precision}sDisplay`;return t.blank?o.toLocaleString(r,{style:i,[a]:"always"}):t.abs().toLocaleString(r,{style:i})},wE=function(t){const r=new Intl.RelativeTimeFormat(Ie(this,gt,"a",Wi),{numeric:"auto",style:this.formatStyle}),n=this.tense;n==="future"&&t.sign!==1&&(t=Dd),n==="past"&&t.sign!==-1&&(t=Dd);const[i,s]=DD(t);return s==="second"&&i<10?r.format(0,this.precision==="millisecond"?"second":this.precision):r.format(i,s)},EE=function(t){const r=new Intl.DateTimeFormat(Ie(this,gt,"a",Wi),{second:this.second,minute:this.minute,hour:this.hour,weekday:this.weekday,day:this.day,month:this.month,year:this.year,timeZoneName:this.timeZoneName,timeZone:this.timeZone});return`${this.prefix} ${r.format(t)}`.trim()},xE=function(t){return new Intl.DateTimeFormat(Ie(this,gt,"a",Wi),{day:"numeric",month:"short",year:"numeric",hour:"numeric",minute:"2-digit",timeZoneName:"short",timeZone:this.timeZone}).format(t)},Gh=function(t){if(this.hasAttribute("aria-hidden")&&this.getAttribute("aria-hidden")==="true"){const r=document.createElement("span");r.setAttribute("aria-hidden","true"),r.textContent=t,Ie(this,bi,"f").replaceChildren(r)}else Ie(this,bi,"f").textContent=t},SE=function(t){var r;return t==="duration"?!1:this.ownerDocument.documentElement.getAttribute("data-prefers-absolute-time")==="true"||((r=this.ownerDocument.body)===null||r===void 0?void 0:r.getAttribute("data-prefers-absolute-time"))==="true"};const av=typeof globalThis<"u"?globalThis:window;try{av.RelativeTimeElement=BD.define()}catch(e){if(!(av.DOMException&&e instanceof DOMException&&e.name==="NotSupportedError")&&!(e instanceof ReferenceError))throw e}var UD=class extends Lp{static get styles(){return[...super.styles,te`
        .input-group__input {
          font-family: var(--c-font-mono);
          font-size: 0.9em;
        }
      `]}constructor(){super(),this.autocorrect=!1}firstUpdated(e){super.firstUpdated(e),this._inputNode?.setAttribute("autocapitalize","off")}};customElements.get("craft-input-handle")||customElements.define("craft-input-handle",UD);JL();var CE=class extends Xc{static get styles(){return[...super.styles,pa,te`
        .input-group__container {
          position: relative;
        }

        .input-group__suffix {
          position: absolute;
          inset-inline-end: var(--c-input-spacing-inline);
          inset-block-start: 50%;
          transform: translateY(calc(-50%));
        }
      `]}constructor(){super(),this._visible=!1,this.reveal=()=>{this._visible=!this._visible,this.type=this._visible?"text":"password"},this.renderSuffix=()=>W`
      <craft-button
        type="button"
        icon
        size="small"
        variant="plain"
        @click="${this.reveal}"
        appearance="plain"
      >
        <span class="icon"
          >${this._visible?W`<craft-icon name="eye-slash"></craft-icon>`:W`<craft-icon name="eye"></craft-icon>`}
        </span>
      </craft-button>
    `,this.type="password"}get slots(){return{...super.slots,suffix:()=>({template:this.renderSuffix()})}}};se([ir()],CE.prototype,"_visible",void 0);customElements.get("craft-input-password")||customElements.define("craft-input-password",CE);var zD=te`
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
`,Jc=class extends Ne{constructor(...e){super(...e),this.size="",this.variant=""}render(){const e=!!this.querySelector('[slot="prefix"]'),t=!!this.querySelector('[slot="suffix"]');return W`
      <div
        class="${er({chip:!0,"chip--small":this.size==="small","chip--medium":this.size==="medium","chip--large":this.size==="large","chip--plain":this.variant==="plain"})}"
      >
        ${e?W`<div class="chip__prefix"><slot name="prefix"></slot></div>`:ht}
        <div class="chip__body">
          <slot></slot>
        </div>
        ${t?W`<div class="chip__suffix"><slot name="suffix"></slot></div>`:ht}
      </div>
    `}};Jc.styles=[zD];se([V()],Jc.prototype,"size",void 0);se([V()],Jc.prototype,"variant",void 0);customElements.get("craft-chip")||customElements.define("craft-chip",Jc);var HD=te`
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
`,Qc=class extends Ne{constructor(...e){super(...e),this.label=null,this.status=null}getLabel(){return!this.label&&this.status?`Status: ${this.status}`:this.label}render(){return W`
      <span
        class="${er({status:!0,"status--live":this.status==="live","status--enabled":this.status==="enabled","status--pending":this.status==="pending","status--expired":this.status==="expired","status--disabled":this.status==="disabled"})}"
        role="img"
        aria-label="${this.getLabel()}"
      ></span>
    `}};Qc.styles=[HD];se([V()],Qc.prototype,"label",void 0);se([V()],Qc.prototype,"status",void 0);customElements.get("craft-status")||customElements.define("craft-status",Qc);var _o=new Map;function qD(e){var t=_o.get(e);t&&t.destroy()}function jD(e){var t=_o.get(e);t&&t.update()}var Zs=null;typeof window>"u"?((Zs=function(e){return e}).destroy=function(e){return e},Zs.update=function(e){return e}):((Zs=function(e,t){return e&&Array.prototype.forEach.call(e.length?e:[e],function(r){return(function(n){if(n&&n.nodeName&&n.nodeName==="TEXTAREA"&&!_o.has(n)){var i,s=null,o=window.getComputedStyle(n),a=(i=n.value,function(){u({testForHeightReduction:i===""||!n.value.startsWith(i),restoreTextAlign:null}),i=n.value}),l=(function(d){n.removeEventListener("autosize:destroy",l),n.removeEventListener("autosize:update",c),n.removeEventListener("input",a),window.removeEventListener("resize",c),Object.keys(d).forEach(function(p){return n.style[p]=d[p]}),_o.delete(n)}).bind(n,{height:n.style.height,resize:n.style.resize,textAlign:n.style.textAlign,overflowY:n.style.overflowY,overflowX:n.style.overflowX,wordWrap:n.style.wordWrap});n.addEventListener("autosize:destroy",l),n.addEventListener("autosize:update",c),n.addEventListener("input",a),window.addEventListener("resize",c),n.style.overflowX="hidden",n.style.wordWrap="break-word",_o.set(n,{destroy:l,update:c}),c()}function u(d){var p,m,h=d.restoreTextAlign,f=h===void 0?null:h,b=d.testForHeightReduction,y=b===void 0||b,w=o.overflowY;if(n.scrollHeight!==0&&(o.resize==="vertical"?n.style.resize="none":o.resize==="both"&&(n.style.resize="horizontal"),y&&(p=(function(_){for(var x=[];_&&_.parentNode&&_.parentNode instanceof Element;)_.parentNode.scrollTop&&x.push([_.parentNode,_.parentNode.scrollTop]),_=_.parentNode;return function(){return x.forEach(function(k){var S=k[0],N=k[1];S.style.scrollBehavior="auto",S.scrollTop=N,S.style.scrollBehavior=null})}})(n),n.style.height=""),m=o.boxSizing==="content-box"?n.scrollHeight-(parseFloat(o.paddingTop)+parseFloat(o.paddingBottom)):n.scrollHeight+parseFloat(o.borderTopWidth)+parseFloat(o.borderBottomWidth),o.maxHeight!=="none"&&m>parseFloat(o.maxHeight)?(o.overflowY==="hidden"&&(n.style.overflow="scroll"),m=parseFloat(o.maxHeight)):o.overflowY!=="hidden"&&(n.style.overflow="hidden"),n.style.height=m+"px",f&&(n.style.textAlign=f),p&&p(),s!==m&&(n.dispatchEvent(new Event("autosize:resized",{bubbles:!0})),s=m),w!==o.overflow&&!f)){var v=o.textAlign;o.overflow==="hidden"&&(n.style.textAlign=v==="start"?"end":"start"),u({restoreTextAlign:v,testForHeightReduction:!0})}}function c(){u({testForHeightReduction:!0,restoreTextAlign:null})}})(r)}),e}).destroy=function(e){return e&&Array.prototype.forEach.call(e.length?e:[e],qD),e},Zs.update=function(e){return e&&Array.prototype.forEach.call(e.length?e:[e],jD),e});var Vd=Zs;class WD extends ba{get _inputNode(){return Array.from(this.children).find(t=>t.slot==="input")}}class KD extends fE(WD){static get properties(){return{maxRows:{type:Number,attribute:"max-rows"},rows:{type:Number,reflect:!0},readOnly:{type:Boolean,attribute:"readonly",reflect:!0},placeholder:{type:String,reflect:!0}}}get slots(){return{...super.slots,input:()=>{const t=document.createElement("textarea");return t.style.resize!==void 0&&(t.style.resize="none"),t}}}constructor(){super(),this.rows=2,this.maxRows=6,this.readOnly=!1,this.placeholder=""}connectedCallback(){super.connectedCallback(),this.__initializeAutoresize(),this.__intersectionObserver=new IntersectionObserver(()=>this.resizeTextarea()),this.__intersectionObserver.observe(this)}updated(t){if(super.updated(t),t.has("name")&&(this._inputNode.name=this.name),t.has("autocomplete")&&(this._inputNode.autocomplete=this.autocomplete),t.has("disabled")&&(this._inputNode.disabled=this.disabled,this.validate()),t.has("rows")){const r=this._inputNode;r&&(r.rows=this.rows)}if(t.has("readOnly")){const r=this._inputNode;r&&(r.readOnly=this.readOnly)}if(t.has("placeholder")){const r=this._inputNode;r&&(r.placeholder=this.placeholder)}t.has("modelValue")&&this.resizeTextarea(),(t.has("maxRows")||t.has("rows"))&&this.setTextareaMaxHeight()}disconnectedCallback(){super.disconnectedCallback(),Vd.destroy(this._inputNode)}setTextareaMaxHeight(){const{value:t}=this._inputNode;this._inputNode.value="",this.resizeTextarea();const r=window.getComputedStyle(this._inputNode,null),n=parseFloat(r.lineHeight)||parseFloat(r.height)/this.rows,i=parseFloat(r.paddingTop)+parseFloat(r.paddingBottom),s=parseFloat(r.borderTopWidth)+parseFloat(r.borderBottomWidth),o=r.boxSizing==="border-box"?i+s:0;this._inputNode.style.maxHeight=`${n*this.maxRows+o}px`,this._inputNode.value=t,this.resizeTextarea()}static get styles(){return[...super.styles,te`
        .input-group__container > .input-group__input ::slotted(.form-control) {
          box-sizing: content-box;
          overflow-x: hidden; /* for FF adds height to the TextArea to reserve place for scroll-bars */
        }

        /* Workaround https://bugzilla.mozilla.org/show_bug.cgi?id=1739079 */
        :host([disabled]) ::slotted(textarea) {
          user-select: none;
        }
      `]}get updateComplete(){return this.__textareaUpdateComplete?Promise.all([this.__textareaUpdateComplete,super.updateComplete]):super.updateComplete}resizeTextarea(){Vd.update(this._inputNode)}__initializeAutoresize(){this.__shady_native_contains?this.__textareaUpdateComplete=this.__waitForTextareaRenderedInRealDOM().then(()=>{this.__startAutoresize(),this.__textareaUpdateComplete=null}):this.__startAutoresize()}async __waitForTextareaRenderedInRealDOM(){let t=3;for(;t!==0&&!this.__shady_native_contains(this._inputNode);)await new Promise(r=>setTimeout(r)),t-=1}__startAutoresize(){Vd(this._inputNode),this.setTextareaMaxHeight()}}var GD=te`
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
`,YD=class extends KD{static get styles(){return[...super.styles,pa,GD]}};customElements.get("craft-textarea")||customElements.define("craft-textarea",YD);var XD=te`
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
`,AE=class extends Ne{render(){return W`<slot></slot>`}};AE.styles=[XD];customElements.get("craft-button-group")||customElements.define("craft-button-group",AE);class JD extends ba{static get properties(){return{autocomplete:{type:String}}}constructor(){super(),this.autocomplete=void 0}get _inputNode(){return Array.from(this.children).find(t=>t.slot==="input")}}class QD extends JD{get operationMode(){return"select"}connectedCallback(){super.connectedCallback(),this._inputNode.addEventListener("change",this._proxyChangeEvent),this.__selectObserver=new MutationObserver(()=>{this._syncValueUpwards(),this._calculateValues({source:"model"})}),this.__selectObserver.observe(this._inputNode,{attributes:!0,childList:!0,subtree:!0})}updated(t){super.updated(t),t.has("disabled")&&(this._inputNode.disabled=this.disabled,this.validate()),t.has("name")&&(this._inputNode.name=this.name),t.has("autocomplete")&&(this._inputNode.autocomplete=this.autocomplete)}disconnectedCallback(){super.disconnectedCallback(),this._inputNode.removeEventListener("change",this._proxyChangeEvent),this.__selectObserver?.disconnect()}formatter(t){const r=Array.from(this._inputNode.options).find(n=>n.value===t);return r?r.text:""}_reflectBackFormattedValueToUser(){this._reflectBackOn()&&(this.value=typeof this.modelValue<"u"?this.modelValue:"")}_proxyChangeEvent(){this.dispatchEvent(new CustomEvent("user-input-changed",{bubbles:!0,composed:!0}))}}var ZD=te`
  ${qc}

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
    ${kp}
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
`,e$=class extends QD{static get styles(){return[...super.styles,ZD]}_inputGroupInputTemplate(){return W`
      <div class="input-group__input">
        <slot name="input"></slot>
        <craft-icon
          class="indicator"
          name="chevron-down"
          style="font-size: 0.8em"
        ></craft-icon>
      </div>
    `}};customElements.get("craft-select")||customElements.define("craft-select",e$);class t$ extends cD(Ne){static get properties(){return{tabIndex:{type:Number,reflect:!0,attribute:"tabindex"}}}constructor(){super(),this.tabIndex=0}connectedCallback(){super.connectedCallback(),this.setAttribute("role","listbox")}createRenderRoot(){return this}}function lv(e,t){Array.from(e.childNodes).forEach(r=>{r.hasAttribute&&r.hasAttribute("slot")||t.appendChild(r)})}const r$=e=>class extends Vi(ma(Gc(Is(Fp(e))))){static get properties(){return{orientation:String,selectionFollowsFocus:{type:Boolean,attribute:"selection-follows-focus"},rotateKeyboardNavigation:{type:Boolean,attribute:"rotate-keyboard-navigation"},hasNoDefaultSelected:{type:Boolean,reflect:!0,attribute:"has-no-default-selected"},_noTypeAhead:{type:Boolean}}}static get styles(){return[...super.styles||[],te`
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
        `]}_inputGroupInputTemplate(){return W`
        <div class="input-group__input">
          <slot name="input"></slot>
          <slot id="options-outlet"></slot>
        </div>
      `}static get scopedElements(){return{...super.scopedElements,"lion-options":t$}}get slots(){return{...super.slots,input:()=>{const r=this.createScopedElement("lion-options");return r.setAttribute("data-tag-name","lion-options"),r.registrationTarget=this,r}}}get _inputNode(){return this.querySelector('[slot="input"]')}get _listboxNode(){return this._inputNode}get _listboxActiveDescendantNode(){return this._listboxNode.querySelector(`#${this._listboxActiveDescendant}`)}get _listboxSlot(){return this.shadowRoot.querySelector("slot[name=input]")}get _scrollTargetNode(){return this._listboxNode}get _activeDescendantOwnerNode(){return this._listboxNode}get activeIndex(){return this.formElements.findIndex(r=>r.active===!0)}set activeIndex(r){if(this.formElements[r]){const n=this.formElements[r];this.__setChildActive(n)}else this.__setChildActive(null)}get checkedIndex(){const r=this.formElements;return this.multipleChoice?r.filter(n=>n.checked).map(n=>r.indexOf(n)):r.indexOf(r.find(n=>n.checked))}set checkedIndex(r){this.setCheckedIndex(r)}constructor(){super(),this.hasNoDefaultSelected=!1,this.orientation="vertical",this.rotateKeyboardNavigation=!1,this.selectionFollowsFocus=!1,this._noTypeAhead=!1,this._typeAheadTimeout=1e3,this._listboxActiveDescendant=null,this.__hasInitialSelectedFormElement=!1,this._repropagationRole="choice-group",this._listboxReceivesNoFocus=!1,this._oldModelValue=void 0,this._listboxOnKeyDown=this._listboxOnKeyDown.bind(this),this._listboxOnClick=this._listboxOnClick.bind(this),this._listboxOnKeyUp=this._listboxOnKeyUp.bind(this),this._onChildActiveChanged=this._onChildActiveChanged.bind(this),this.__proxyChildModelValueChanged=this.__proxyChildModelValueChanged.bind(this),this.__preventScrollingWithArrowKeys=this.__preventScrollingWithArrowKeys.bind(this),this.__typedChars=[]}connectedCallback(){this._listboxNode&&(this._listboxNode.registrationTarget=this),super.connectedCallback(),this._setupListboxNode(),this.__setupEventListeners(),this.registrationComplete.then(()=>{this.__initInteractionStates()})}firstUpdated(r){super.firstUpdated(r),this.__moveOptionsToListboxNode(),this.registrationComplete.then(()=>{this._initialModelValue=this.modelValue}),new MutationObserver(()=>{this._onListboxContentChanged()}).observe(this._listboxNode,{childList:!0})}updated(r){super.updated(r),r.has("disabled")&&(this.disabled?this.__requestOptionsToBeDisabled():this.__retractRequestOptionsToBeDisabled())}disconnectedCallback(){super.disconnectedCallback(),this._teardownListboxNode(),this.__teardownEventListeners()}setCheckedIndex(r){if(this.multipleChoice&&Array.isArray(r)){this._uncheckChildren(this.formElements.filter(n=>n===r)),r.forEach(n=>{this.formElements[n]&&(this.formElements[n].checked=!this.formElements[n].checked)});return}typeof r=="number"&&(r===-1&&this._uncheckChildren(),this.formElements[r]&&(this.formElements[r].disabled?this._uncheckChildren():this.multipleChoice?this.formElements[r].checked=!this.formElements[r].checked:this.formElements[r].checked=!0))}addFormElement(r,n){super.addFormElement(r,n),r.id=r.id||`${this.localName}-option-${fa()}`,this.disabled&&r.makeRequestToBeDisabled(),this.__setAttributeForAllFormElements("aria-setsize",this.formElements.length),this.formElements.forEach((i,s)=>{i.setAttribute("aria-posinset",s+1)}),this.__proxyChildModelValueChanged({target:r}),this.resetInteractionState()}resetInteractionState(){super.resetInteractionState(),this.submitted=!1}reset(){this.modelValue=this._initialModelValue,this.activeIndex=-1,this.resetInteractionState()}clear(){super.clear(),this.setCheckedIndex(-1),this.resetInteractionState()}_handleTypeAhead(r,{setAsChecked:n}){const{key:i,code:s}=r;if(s.startsWith("Key")||s.startsWith("Digit")||s.startsWith("Numpad")){r.preventDefault(),this.__typedChars.push(i);const o=this.__typedChars.join(""),a=this.formElements.findIndex(l=>l.modelValue.value.toLowerCase().startsWith(o));a>=0&&(n&&this.setCheckedIndex(a),this.activeIndex=a),this.__pendingTypeAheadTimeout&&window.clearTimeout(this.__pendingTypeAheadTimeout),this.__pendingTypeAheadTimeout=setTimeout(()=>{this.__typedChars=[]},this._typeAheadTimeout)}}_getCheckedElements(){return this.formElements.filter(r=>r.checked)}_setupListboxNode(){this._listboxNode?this.__setupListboxNodeInteractions():this._listboxSlot&&this._listboxSlot.addEventListener("slotchange",()=>{this.__setupListboxNodeInteractions()})}_onListboxContentChanged(){}_teardownListboxNode(){this._listboxNode&&(this._listboxNode.removeEventListener("keydown",this._listboxOnKeyDown),this._listboxNode.removeEventListener("click",this._listboxOnClick),this._listboxNode.removeEventListener("keyup",this._listboxOnKeyUp))}_getNextEnabledOption(r,n=1){return this.__getEnabledOption(r,n)}_getPreviousEnabledOption(r,n=-1){return this.__getEnabledOption(r,n)}_onChildActiveChanged({target:r}){r.active===!0&&this.__setChildActive(r)}_listboxOnKeyDown(r){if(this.disabled)return;this._isHandlingUserInput=!0,setTimeout(()=>{this._isHandlingUserInput=!1});const{key:n}=r;switch(n){case" ":case"Enter":{if(n===" "&&this._listboxReceivesNoFocus||(n===" "&&r.preventDefault(),!this.formElements[this.activeIndex])||this.formElements[this.activeIndex].disabled)return;this.formElements[this.activeIndex].href&&this.formElements[this.activeIndex].click(),this.setCheckedIndex(this.activeIndex);break}case"ArrowUp":r.preventDefault(),this.orientation==="vertical"&&(this.activeIndex=this._getPreviousEnabledOption(this.activeIndex));break;case"ArrowLeft":if(this._listboxReceivesNoFocus)return;r.preventDefault(),this.orientation==="horizontal"&&(this.activeIndex=this._getPreviousEnabledOption(this.activeIndex));break;case"ArrowDown":r.preventDefault(),this.orientation==="vertical"&&(this.activeIndex=this._getNextEnabledOption(this.activeIndex));break;case"ArrowRight":if(this._listboxReceivesNoFocus)return;r.preventDefault(),this.orientation==="horizontal"&&(this.activeIndex=this._getNextEnabledOption(this.activeIndex));break;case"Home":if(this._listboxReceivesNoFocus)return;r.preventDefault(),this.activeIndex=this._getNextEnabledOption(0,0);break;case"End":if(this._listboxReceivesNoFocus)return;r.preventDefault(),this.activeIndex=this._getPreviousEnabledOption(this.formElements.length-1,0);break;default:this._noTypeAhead||this._handleTypeAhead(r,{setAsChecked:this.selectionFollowsFocus&&!this.multipleChoice})}["ArrowUp","ArrowDown","ArrowLeft","ArrowRight","Home","End"].includes(n)&&this.selectionFollowsFocus&&!this.multipleChoice&&this.setCheckedIndex(this.activeIndex)}_listboxOnClick(r){}_listboxOnKeyUp(r){if(this.disabled)return;this._isHandlingUserInput=!0,setTimeout(()=>{this._isHandlingUserInput=!1});const{key:n}=r;switch(n){case"ArrowUp":case"ArrowDown":case"Home":case"End":case"Enter":r.preventDefault()}}_onLabelClick(){this._listboxNode.focus()}_scrollIntoView(r,n){r.scrollIntoView({behavior:"smooth",block:"nearest"})}__setupEventListeners(){this._listboxNode.addEventListener("active-changed",this._onChildActiveChanged),this._listboxNode.addEventListener("model-value-changed",this.__proxyChildModelValueChanged)}__teardownEventListeners(){this._listboxNode.removeEventListener("active-changed",this._onChildActiveChanged),this._listboxNode.removeEventListener("model-value-changed",this.__proxyChildModelValueChanged)}__setChildActive(r){if(this.formElements.forEach(n=>{n.active=r===n}),!r){this._activeDescendantOwnerNode.removeAttribute("aria-activedescendant");return}this._activeDescendantOwnerNode.setAttribute("aria-activedescendant",r.id),this._scrollIntoView(r,this._scrollTargetNode)}_uncheckChildren(r=[]){const n=Array.isArray(r)?r:[r];this.formElements.forEach(i=>{n.includes(i)||(i.checked=!1)})}__onChildCheckedChanged(r){const{target:n}=r;r.stopPropagation&&r.stopPropagation(),n.checked&&!this.multipleChoice&&this._uncheckChildren(n)}__setAttributeForAllFormElements(r,n){this.formElements.forEach(i=>{i.setAttribute(r,n)})}__proxyChildModelValueChanged(r){r.stopPropagation&&r.stopPropagation(),this.__onChildCheckedChanged(r),this.requestUpdate("modelValue",this._oldModelValue),r.detail&&r.detail.formPath&&this.dispatchEvent(new CustomEvent("model-value-changed",{detail:{formPath:r.detail.formPath,isTriggeredByUser:r.detail.isTriggeredByUser||this._isHandlingUserInput,element:r.target}})),this._oldModelValue=this.modelValue}__getEnabledOption(r,n){const i=s=>n===1?s<this.formElements.length:s>=0;for(let s=r+n;i(s);s+=n)if(this.formElements[s]&&!this.formElements[s].hasAttribute("aria-hidden"))return s;if(this.rotateKeyboardNavigation){const s=n===-1?this.formElements.length-1:0;for(let o=s;i(o);o+=n)if(this.formElements[o]&&!this.formElements[o].hasAttribute("aria-hidden"))return o}return r}__moveOptionsToListboxNode(){const r=this.shadowRoot.getElementById("options-outlet");r&&(lv(this,this._listboxNode),r.addEventListener("slotchange",()=>{lv(this,this._listboxNode)}))}__preventScrollingWithArrowKeys(r){if(this.disabled)return;const{key:n}=r;switch(n){case"ArrowUp":case"ArrowDown":case"Home":case"End":r.preventDefault()}}__setupListboxNodeInteractions(){this._listboxNode.setAttribute("role","listbox"),this._listboxNode.setAttribute("aria-orientation",this.orientation),this._listboxNode.setAttribute("aria-multiselectable",`${this.multipleChoice}`),this._listboxNode.setAttribute("tabindex","0"),this._listboxNode.addEventListener("click",this._listboxOnClick),this._listboxNode.addEventListener("keyup",this._listboxOnKeyUp),this._listboxNode.addEventListener("keydown",this._listboxOnKeyDown),this._scrollTargetNode.addEventListener("keydown",this.__preventScrollingWithArrowKeys)}__requestOptionsToBeDisabled(){this.formElements.forEach(r=>{r.makeRequestToBeDisabled&&r.makeRequestToBeDisabled()})}__retractRequestOptionsToBeDisabled(){this.formElements.forEach(r=>{r.retractRequestToBeDisabled&&r.retractRequestToBeDisabled()})}__initInteractionStates(){this.initInteractionState()}},n$=He(r$);class i$ extends n$(Op(Ip(ga(Ne)))){get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,focused:this.focused}}get _focusableNode(){return this._inputNode}}class cv extends ha(Yc(Np(Is(Ne)))){static get properties(){return{active:{type:Boolean,reflect:!0}}}static get styles(){return[te`
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
      `]}get slots(){return{}}constructor(){super(),this.active=!1,this.__onClick=this.__onClick.bind(this),this.__registerEventListeners()}requestUpdate(t,r,n){super.requestUpdate(t,r,n),t==="active"&&this.active!==r&&this.dispatchEvent(new Event("active-changed",{bubbles:!0}))}updated(t){super.updated(t),t.has("checked")&&this.setAttribute("aria-selected",`${this.checked}`),t.has("disabled")&&this.setAttribute("aria-disabled",`${this.disabled}`)}render(){return W`
      <div class="choice-field__label">
        <slot></slot>
      </div>
    `}connectedCallback(){super.connectedCallback(),this.setAttribute("role","option")}__registerEventListeners(){this.addEventListener("click",this.__onClick)}__unRegisterEventListeners(){this.removeEventListener("click",this.__onClick)}__onClick(){if(this.disabled)return;const t=this._parentFormGroup;this._isHandlingUserInput=!0,t&&t.multipleChoice?(this.checked=!this.checked,this.active=!this.active):(this.checked=!0,this.active=!0),this._isHandlingUserInput=!1}}var s$=te`
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
`,TE=class extends cv{constructor(...e){super(...e),this.hint=null}static get styles(){return[...cv.styles,s$]}render(){return W`
      <div class="choice-field__label">
        <slot></slot>
        ${this.hint?W`<span class="hint">${this.hint}</span>`:ht}
        <slot name="suffix"></slot>
      </div>
    `}};se([V()],TE.prototype,"hint",void 0);customElements.get("craft-option")||customElements.define("craft-option",TE);var kE=`@layer wa-utilities {
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
`;var o$=class extends Event{constructor(e){super("wa-select",{bubbles:!0,cancelable:!0,composed:!0}),this.detail=e}};function*OE(e=document.activeElement){e!=null&&(yield e,"shadowRoot"in e&&e.shadowRoot&&e.shadowRoot.mode!=="closed"&&(yield*OE(e.shadowRoot.activeElement)))}var a$=`:host {
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
`,Bd=new Set,Tt=class extends Ht{constructor(){super(...arguments),this.submenuCleanups=new Map,this.localize=new ks(this),this.userTypedQuery="",this.openSubmenuStack=[],this.open=!1,this.size="medium",this.placement="bottom-start",this.distance=0,this.skidding=0,this.handleDocumentKeyDown=async e=>{const t=this.localize.dir()==="rtl";if(e.key==="Escape"){const c=this.getTrigger();e.preventDefault(),e.stopPropagation(),this.open=!1,c?.focus();return}const r=[...OE()].find(c=>c.localName==="wa-dropdown-item"),n=r?.localName==="wa-dropdown-item",i=this.getCurrentSubmenuItem(),s=!!i;let o,a,l;s?(o=this.getSubmenuItems(i),a=o.find(c=>c.active||c===r),l=a?o.indexOf(a):-1):(o=this.getItems(),a=o.find(c=>c.active||c===r),l=a?o.indexOf(a):-1);let u;if(e.key==="ArrowUp"&&(e.preventDefault(),e.stopPropagation(),l>0?u=o[l-1]:u=o[o.length-1]),e.key==="ArrowDown"&&(e.preventDefault(),e.stopPropagation(),l!==-1&&l<o.length-1?u=o[l+1]:u=o[0]),e.key===(t?"ArrowLeft":"ArrowRight")&&n&&a&&a.hasSubmenu){e.preventDefault(),e.stopPropagation(),a.submenuOpen=!0,this.addToSubmenuStack(a),setTimeout(()=>{const c=this.getSubmenuItems(a);c.length>0&&(c.forEach((d,p)=>d.active=p===0),c[0].focus())},0);return}if(e.key===(t?"ArrowRight":"ArrowLeft")&&s){e.preventDefault(),e.stopPropagation();const c=this.removeFromSubmenuStack();c&&(c.submenuOpen=!1,setTimeout(()=>{c.focus(),c.active=!0,(c.slot==="submenu"?this.getSubmenuItems(c.parentElement):this.getItems()).forEach(p=>{p!==c&&(p.active=!1)})},0));return}if((e.key==="Home"||e.key==="End")&&(e.preventDefault(),e.stopPropagation(),u=e.key==="Home"?o[0]:o[o.length-1]),e.key==="Tab"&&await this.hideMenu(),e.key.length===1&&!(e.metaKey||e.ctrlKey||e.altKey)&&!(e.key===" "&&this.userTypedQuery==="")&&(clearTimeout(this.userTypedTimeout),this.userTypedTimeout=setTimeout(()=>{this.userTypedQuery=""},1e3),this.userTypedQuery+=e.key,o.some(c=>{const d=(c.textContent||"").trim().toLowerCase(),p=this.userTypedQuery.trim().toLowerCase();return d.startsWith(p)?(u=c,!0):!1})),u){e.preventDefault(),e.stopPropagation(),o.forEach(c=>c.active=c===u),u.focus();return}(e.key==="Enter"||e.key===" "&&this.userTypedQuery==="")&&n&&a&&(e.preventDefault(),e.stopPropagation(),a.hasSubmenu?(a.submenuOpen=!0,this.addToSubmenuStack(a),setTimeout(()=>{const c=this.getSubmenuItems(a);c.length>0&&(c.forEach((d,p)=>d.active=p===0),c[0].focus())},0)):this.makeSelection(a))},this.handleDocumentPointerDown=e=>{e.composedPath().some(n=>n instanceof HTMLElement?n===this||n.closest('wa-dropdown, [part="submenu"]'):!1)||(this.open=!1)},this.handleGlobalMouseMove=e=>{const t=this.getCurrentSubmenuItem();if(!t?.submenuOpen||!t.submenuElement)return;const r=t.submenuElement.getBoundingClientRect(),n=this.localize.dir()==="rtl",i=n?r.right:r.left,s=n?Math.max(e.clientX,i):Math.min(e.clientX,i),o=Math.max(r.top,Math.min(e.clientY,r.bottom));t.submenuElement.style.setProperty("--safe-triangle-cursor-x",`${s}px`),t.submenuElement.style.setProperty("--safe-triangle-cursor-y",`${o}px`);const a=t.matches(":hover"),l=t.submenuElement?.matches(":hover")||!!e.composedPath().find(u=>u instanceof HTMLElement&&u.closest('[part="submenu"]')===t.submenuElement);!a&&!l&&setTimeout(()=>{!t.matches(":hover")&&!t.submenuElement?.matches(":hover")&&(t.submenuOpen=!1)},100)}}disconnectedCallback(){super.disconnectedCallback(),clearInterval(this.userTypedTimeout),this.closeAllSubmenus(),this.submenuCleanups.forEach(e=>e()),this.submenuCleanups.clear(),document.removeEventListener("mousemove",this.handleGlobalMouseMove)}firstUpdated(){this.syncAriaAttributes()}async updated(e){e.has("open")&&(this.customStates.set("open",this.open),this.open?await this.showMenu():(this.closeAllSubmenus(),await this.hideMenu())),e.has("size")&&this.syncItemSizes()}getItems(e=!1){const t=this.defaultSlot.assignedElements({flatten:!0}).filter(r=>r.localName==="wa-dropdown-item");return e?t:t.filter(r=>!r.disabled)}getSubmenuItems(e,t=!1){const r=e.shadowRoot?.querySelector('slot[name="submenu"]')||e.querySelector('slot[name="submenu"]');if(!r)return[];const n=r.assignedElements({flatten:!0}).filter(i=>i.localName==="wa-dropdown-item");return t?n:n.filter(i=>!i.disabled)}syncItemSizes(){this.defaultSlot.assignedElements({flatten:!0}).filter(t=>t.localName==="wa-dropdown-item").forEach(t=>t.size=this.size)}addToSubmenuStack(e){const t=this.openSubmenuStack.indexOf(e);t!==-1?this.openSubmenuStack=this.openSubmenuStack.slice(0,t+1):this.openSubmenuStack.push(e)}removeFromSubmenuStack(){return this.openSubmenuStack.pop()}getCurrentSubmenuItem(){return this.openSubmenuStack.length>0?this.openSubmenuStack[this.openSubmenuStack.length-1]:void 0}closeAllSubmenus(){this.getItems(!0).forEach(t=>{t.submenuOpen=!1}),this.openSubmenuStack=[]}closeSiblingSubmenus(e){const t=e.closest('wa-dropdown-item:not([slot="submenu"])');let r;t?r=this.getSubmenuItems(t,!0):r=this.getItems(!0),r.forEach(n=>{n!==e&&n.submenuOpen&&(n.submenuOpen=!1)}),this.openSubmenuStack.includes(e)||this.openSubmenuStack.push(e)}getTrigger(){return this.querySelector('[slot="trigger"]')}async showMenu(){if(!this.getTrigger())return;const t=new ca;if(this.dispatchEvent(t),t.defaultPrevented){this.open=!1;return}Bd.forEach(n=>n.open=!1),this.popup.active=!0,this.open=!0,Bd.add(this),this.syncAriaAttributes(),document.addEventListener("keydown",this.handleDocumentKeyDown),document.addEventListener("pointerdown",this.handleDocumentPointerDown),document.addEventListener("mousemove",this.handleGlobalMouseMove),this.menu.classList.remove("hide"),await Ct(this.menu,"show");const r=this.getItems();r.length>0&&(r.forEach((n,i)=>n.active=i===0),r[0].focus()),this.dispatchEvent(new aa)}async hideMenu(){const e=new la({source:this});if(this.dispatchEvent(e),e.defaultPrevented){this.open=!0;return}this.open=!1,Bd.delete(this),this.syncAriaAttributes(),document.removeEventListener("keydown",this.handleDocumentKeyDown),document.removeEventListener("pointerdown",this.handleDocumentPointerDown),document.removeEventListener("mousemove",this.handleGlobalMouseMove),this.menu.classList.remove("show"),await Ct(this.menu,"hide"),this.popup.active=this.open,this.dispatchEvent(new oa)}handleMenuClick(e){const t=e.target.closest("wa-dropdown-item");if(!(!t||t.disabled)){if(t.hasSubmenu){t.submenuOpen||(this.closeSiblingSubmenus(t),this.addToSubmenuStack(t),t.submenuOpen=!0),e.stopPropagation();return}this.makeSelection(t)}}async handleMenuSlotChange(){const e=this.getItems(!0);await Promise.all(e.map(n=>n.updateComplete)),this.syncItemSizes();const t=e.some(n=>n.type==="checkbox"),r=e.some(n=>n.hasSubmenu);e.forEach((n,i)=>{n.active=i===0,n.checkboxAdjacent=t,n.submenuAdjacent=r})}handleTriggerClick(){this.open=!this.open}handleSubmenuOpening(e){const t=e.detail.item;this.closeSiblingSubmenus(t),this.addToSubmenuStack(t),this.setupSubmenuPosition(t),this.processSubmenuItems(t)}setupSubmenuPosition(e){if(!e.submenuElement)return;this.cleanupSubmenuPosition(e);const t=Y0(e,e.submenuElement,()=>{this.positionSubmenu(e),this.updateSafeTriangleCoordinates(e)});this.submenuCleanups.set(e,t);const r=e.submenuElement.querySelector('slot[name="submenu"]');r&&(r.removeEventListener("slotchange",Tt.handleSubmenuSlotChange),r.addEventListener("slotchange",Tt.handleSubmenuSlotChange),Tt.handleSubmenuSlotChange({target:r}))}static handleSubmenuSlotChange(e){const t=e.target;if(!t)return;const r=t.assignedElements().filter(s=>s.localName==="wa-dropdown-item");if(r.length===0)return;const n=r.some(s=>s.hasSubmenu),i=r.some(s=>s.type==="checkbox");r.forEach(s=>{s.submenuAdjacent=n,s.checkboxAdjacent=i})}processSubmenuItems(e){if(!e.submenuElement)return;const t=this.getSubmenuItems(e,!0),r=t.some(n=>n.hasSubmenu);t.forEach(n=>{n.submenuAdjacent=r})}cleanupSubmenuPosition(e){const t=this.submenuCleanups.get(e);t&&(t(),this.submenuCleanups.delete(e))}positionSubmenu(e){if(!e.submenuElement)return;const r=this.localize.dir()==="rtl"?"left-start":"right-start";Z0(e,e.submenuElement,{placement:r,middleware:[X0({mainAxis:0,crossAxis:-5}),Q0({fallbackStrategy:"bestFit"}),J0({padding:8})]}).then(({x:n,y:i,placement:s})=>{e.submenuElement.setAttribute("data-placement",s),Object.assign(e.submenuElement.style,{left:`${n}px`,top:`${i}px`})})}updateSafeTriangleCoordinates(e){if(!e.submenuElement||!e.submenuOpen)return;if(document.activeElement?.matches(":focus-visible")){e.submenuElement.style.setProperty("--safe-triangle-visible","none");return}e.submenuElement.style.setProperty("--safe-triangle-visible","block");const r=e.submenuElement.getBoundingClientRect(),n=this.localize.dir()==="rtl";e.submenuElement.style.setProperty("--safe-triangle-submenu-start-x",`${n?r.right:r.left}px`),e.submenuElement.style.setProperty("--safe-triangle-submenu-start-y",`${r.top}px`),e.submenuElement.style.setProperty("--safe-triangle-submenu-end-x",`${n?r.right:r.left}px`),e.submenuElement.style.setProperty("--safe-triangle-submenu-end-y",`${r.bottom}px`)}makeSelection(e){const t=this.getTrigger();if(e.disabled)return;e.type==="checkbox"&&(e.checked=!e.checked);const r=new o$({item:e});this.dispatchEvent(r),r.defaultPrevented||(this.open=!1,t?.focus())}async syncAriaAttributes(){const e=this.getTrigger();let t;e&&(e.localName==="wa-button"?(await customElements.whenDefined("wa-button"),await e.updateComplete,t=e.shadowRoot.querySelector('[part="base"]')):t=e,t.hasAttribute("id")||t.setAttribute("id",Tp("wa-dropdown-trigger-")),t.setAttribute("aria-haspopup","menu"),t.setAttribute("aria-expanded",this.open?"true":"false"),this.menu.setAttribute("aria-expanded","false"))}render(){let e=this.hasUpdated?this.popup.active:this.open;return W`
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
    `}};Tt.css=[kE,a$];$([ze("slot:not([name])")],Tt.prototype,"defaultSlot",2);$([ze("#menu")],Tt.prototype,"menu",2);$([ze("wa-popup")],Tt.prototype,"popup",2);$([V({type:Boolean,reflect:!0})],Tt.prototype,"open",2);$([V({reflect:!0})],Tt.prototype,"size",2);$([V({reflect:!0})],Tt.prototype,"placement",2);$([V({type:Number})],Tt.prototype,"distance",2);$([V({type:Number})],Tt.prototype,"skidding",2);Tt=$([Fr("wa-dropdown")],Tt);var Zc=class{constructor(e,...t){this.slotNames=[],this.handleSlotChange=r=>{const n=r.target;(this.slotNames.includes("[default]")&&!n.name||n.name&&this.slotNames.includes(n.name))&&this.host.requestUpdate()},(this.host=e).addController(this),this.slotNames=t}hasDefaultSlot(){return[...this.host.childNodes].some(e=>{if(e.nodeType===Node.TEXT_NODE&&e.textContent.trim()!=="")return!0;if(e.nodeType===Node.ELEMENT_NODE){const t=e;if(t.tagName.toLowerCase()==="wa-visually-hidden")return!1;if(!t.hasAttribute("slot"))return!0}return!1})}hasNamedSlot(e){return this.host.querySelector(`:scope > [slot="${e}"]`)!==null}test(e){return e==="[default]"?this.hasDefaultSlot():this.hasNamedSlot(e)}hostConnected(){this.host.shadowRoot.addEventListener("slotchange",this.handleSlotChange)}hostDisconnected(){this.host.shadowRoot.removeEventListener("slotchange",this.handleSlotChange)}};var l$=`:host {
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
`,yt=class extends Ht{constructor(){super(...arguments),this.hasSlotController=new Zc(this,"[default]","start","end"),this.active=!1,this.variant="default",this.size="medium",this.checkboxAdjacent=!1,this.submenuAdjacent=!1,this.type="normal",this.checked=!1,this.disabled=!1,this.submenuOpen=!1,this.hasSubmenu=!1,this.handleSlotChange=()=>{this.hasSubmenu=this.hasSlotController.test("submenu"),this.updateHasSubmenuState(),this.hasSubmenu?(this.setAttribute("aria-haspopup","menu"),this.setAttribute("aria-expanded",this.submenuOpen?"true":"false")):(this.removeAttribute("aria-haspopup"),this.removeAttribute("aria-expanded"))}}connectedCallback(){super.connectedCallback(),this.addEventListener("mouseenter",this.handleMouseEnter.bind(this)),this.shadowRoot.addEventListener("slotchange",this.handleSlotChange)}disconnectedCallback(){super.disconnectedCallback(),this.closeSubmenu(),this.removeEventListener("mouseenter",this.handleMouseEnter),this.shadowRoot.removeEventListener("slotchange",this.handleSlotChange)}firstUpdated(){this.setAttribute("tabindex","-1"),this.hasSubmenu=this.hasSlotController.test("submenu"),this.updateHasSubmenuState()}updated(e){e.has("active")&&(this.setAttribute("tabindex",this.active?"0":"-1"),this.customStates.set("active",this.active)),e.has("checked")&&(this.setAttribute("aria-checked",this.checked?"true":"false"),this.customStates.set("checked",this.checked)),e.has("disabled")&&(this.setAttribute("aria-disabled",this.disabled?"true":"false"),this.customStates.set("disabled",this.disabled)),e.has("type")&&(this.type==="checkbox"?this.setAttribute("role","menuitemcheckbox"):this.setAttribute("role","menuitem")),e.has("submenuOpen")&&(this.customStates.set("submenu-open",this.submenuOpen),this.submenuOpen?this.openSubmenu():this.closeSubmenu())}updateHasSubmenuState(){this.customStates.set("has-submenu",this.hasSubmenu)}async openSubmenu(){!this.hasSubmenu||!this.submenuElement||(this.notifyParentOfOpening(),this.submenuElement.showPopover(),this.submenuElement.hidden=!1,this.submenuElement.setAttribute("data-visible",""),this.submenuOpen=!0,this.setAttribute("aria-expanded","true"),await Ct(this.submenuElement,"show"),setTimeout(()=>{const e=this.getSubmenuItems();e.length>0&&(e.forEach((t,r)=>t.active=r===0),e[0].focus())},0))}notifyParentOfOpening(){const e=new CustomEvent("submenu-opening",{bubbles:!0,composed:!0,detail:{item:this}});this.dispatchEvent(e);const t=this.parentElement;t&&[...t.children].filter(n=>n!==this&&n.localName==="wa-dropdown-item"&&n.getAttribute("slot")===this.getAttribute("slot")&&n.submenuOpen).forEach(n=>{n.submenuOpen=!1})}async closeSubmenu(){!this.hasSubmenu||!this.submenuElement||(this.submenuOpen=!1,this.setAttribute("aria-expanded","false"),this.submenuElement.hidden||(await Ct(this.submenuElement,"hide"),this.submenuElement.hidden=!0,this.submenuElement.removeAttribute("data-visible"),this.submenuElement.hidePopover()))}getSubmenuItems(){return[...this.children].filter(e=>e.localName==="wa-dropdown-item"&&e.getAttribute("slot")==="submenu"&&!e.hasAttribute("disabled"))}handleMouseEnter(){this.hasSubmenu&&!this.disabled&&(this.notifyParentOfOpening(),this.submenuOpen=!0)}render(){return W`
      ${this.type==="checkbox"?W`
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

      ${this.hasSubmenu?W`
            <wa-icon
              id="submenu-indicator"
              part="submenu-icon"
              exportparts="svg:submenu-icon__svg"
              library="system"
              name="chevron-right"
            ></wa-icon>
          `:""}
      ${this.hasSubmenu?W`
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
    `}};yt.css=l$;$([ze("#submenu")],yt.prototype,"submenuElement",2);$([V({type:Boolean})],yt.prototype,"active",2);$([V({reflect:!0})],yt.prototype,"variant",2);$([V({reflect:!0})],yt.prototype,"size",2);$([V({attribute:"checkbox-adjacent",type:Boolean,reflect:!0})],yt.prototype,"checkboxAdjacent",2);$([V({attribute:"submenu-adjacent",type:Boolean,reflect:!0})],yt.prototype,"submenuAdjacent",2);$([V()],yt.prototype,"value",2);$([V({reflect:!0})],yt.prototype,"type",2);$([V({type:Boolean})],yt.prototype,"checked",2);$([V({type:Boolean,reflect:!0})],yt.prototype,"disabled",2);$([V({type:Boolean,reflect:!0})],yt.prototype,"submenuOpen",2);$([ir()],yt.prototype,"hasSubmenu",2);yt=$([Fr("wa-dropdown-item")],yt);var c$=class extends Tt{static get styles(){return[Tt.styles,te`
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
      `]}},u$=class extends yt{static get styles(){return[yt.styles,te`
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
      `]}};customElements.get("craft-dropdown")||customElements.define("craft-dropdown",c$);customElements.get("craft-dropdown-item")||customElements.define("craft-dropdown-item",u$);function d$({el:e,uid:t}){e.setAttribute("id",`panel-${t}`),e.setAttribute("role","tabpanel"),e.setAttribute("aria-labelledby",`button-${t}`),e.hasAttribute("tabindex")||e.setAttribute("tabindex","0")}function h$(e){e.setAttribute("selected","true")}function uv(e){e.removeAttribute("selected")}function f$({el:e,uid:t,clickHandler:r,keydownHandler:n,keyupHandler:i}){e.setAttribute("id",`button-${t}`),e.setAttribute("role","tab"),e.setAttribute("aria-controls",`panel-${t}`),e.addEventListener("click",r),e.addEventListener("keyup",i),e.addEventListener("keydown",n)}function p$({el:e,clickHandler:t,keydownHandler:r,keyupHandler:n}){e.removeAttribute("id"),e.removeAttribute("role"),e.removeAttribute("aria-controls"),e.removeEventListener("click",t),e.removeEventListener("keyup",n),e.removeEventListener("keydown",r)}function m$(e,t=!1){t&&e.focus(),e.setAttribute("selected","true"),e.setAttribute("aria-selected","true"),e.setAttribute("tabindex","0")}function dv(e){e.removeAttribute("selected"),e.setAttribute("aria-selected","false"),e.setAttribute("tabindex","-1")}function g$(e){const t=e;switch(t.key){case"ArrowDown":case"ArrowRight":case"ArrowUp":case"ArrowLeft":case"Home":case"End":t.preventDefault()}}class b$ extends Ne{static get properties(){return{selectedIndex:{type:Number,attribute:"selected-index",reflect:!0}}}static get styles(){return[te`
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
      `]}render(){return W`
      <div class="tabs__tab-group" role="tablist">
        <slot name="tab"></slot>
      </div>
      <div class="tabs__panels">
        <slot name="panel"></slot>
      </div>
    `}constructor(){super(),this.selectedIndex=0}firstUpdated(t){super.firstUpdated(t),this.__setupSlots(),this.tabs[0]?.disabled&&(this.selectedIndex=this.tabs.findIndex(r=>!r.disabled))}get tabs(){return Array.from(this.children).filter(t=>t.slot==="tab")}get panels(){return Array.from(this.children).filter(t=>t.slot==="panel")}static enabledWarnings=super.enabledWarnings?.filter(t=>t!=="change-in-update")||[];__setupSlots(){if(this.shadowRoot){const t=this.shadowRoot.querySelector("slot[name=tab]"),r=()=>{this.__cleanStore(),this.__setupStore(),this.__updateSelected(!1)};t&&t.addEventListener("slotchange",r)}}__setupStore(){this.__store=[],this.tabs.length!==this.panels.length&&console.warn(`The amount of tabs (${this.tabs.length}) doesn't match the amount of panels (${this.panels.length}).`),this.tabs.forEach((t,r)=>{const n=fa(),i=this.panels[r],s={uid:n,el:t,button:t,panel:i,clickHandler:this.__createButtonClickHandler(r),keydownHandler:g$.bind(this),keyupHandler:this.__handleButtonKeyup.bind(this)};d$({...s,el:s.panel}),f$(s),uv(s.panel),dv(s.button),this.__store&&this.__store.push(s)})}__cleanStore(){this.__store&&(this.__store.forEach(t=>{p$(t)}),this.__store=[])}__getNextNotDisabledTab(t,r,n){let i=[];const s=t.filter((a,l)=>!a.disabled&&l>this.selectedIndex),o=t.filter((a,l)=>!a.disabled&&l<this.selectedIndex);return n==="right"?i=[...s,...o]:i=[...o.reverse(),...s.reverse()],i[0]}__getNextAvailableIndex(t,r){const n=this.tabs[this.selectedIndex];if(this.tabs.every(i=>!i.disabled))return t;if(r==="ArrowRight"||r==="ArrowDown"){const i=this.__getNextNotDisabledTab(this.tabs,n,"right");return this.tabs.findIndex(s=>i===s)}if(r==="ArrowLeft"||r==="ArrowUp"){const i=this.__getNextNotDisabledTab(this.tabs,n,"left");return this.tabs.findIndex(s=>i===s)}if(r==="Home")return this.tabs.findIndex(i=>!i.disabled);if(r==="End"){const i=this.tabs.map((s,o)=>({disabled:s.disabled,index:o})).filter(s=>!s.disabled);return i[i.length-1].index}return-1}__createButtonClickHandler(t){return()=>{this._setSelectedIndexWithFocus(t)}}__handleButtonKeyup(t){const r=t;if(typeof this.selectedIndex=="number")switch(r.key){case"ArrowDown":case"ArrowRight":this.selectedIndex+1>=this._pairCount?this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(0,r.key)):this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this.selectedIndex+1,r.key));break;case"ArrowUp":case"ArrowLeft":this.selectedIndex<=0?this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this._pairCount-1,r.key)):this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this.selectedIndex-1,r.key));break;case"Home":this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(0,r.key));break;case"End":this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this._pairCount-1,r.key));break}}get selectedIndex(){return this.__selectedIndex||0}set selectedIndex(t){if(t===this.__selectedIndex)return;const r=this.__selectedIndex;this.__selectedIndex=t,this.__updateSelected(!1),this.dispatchEvent(new Event("selected-changed")),this.requestUpdate("selectedIndex",r)}_setSelectedIndexWithFocus(t){if(t===-1)return;const r=this.__selectedIndex;this.__selectedIndex=t,this.__updateSelected(!0),this.dispatchEvent(new Event("selected-changed")),this.requestUpdate("selectedIndex",r)}get _pairCount(){return this.__store&&this.__store.length||0}__updateSelected(t=!1){if(!(this.__store&&typeof this.selectedIndex=="number"&&this.__store[this.selectedIndex]))return;const r=this.tabs.find(o=>o.hasAttribute("selected")),n=this.panels.find(o=>o.hasAttribute("selected"));r&&dv(r),n&&uv(n);const{button:i,panel:s}=this.__store[this.selectedIndex];i&&m$(i,t),s&&h$(s)}}var v$=te`
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
`,y$=class extends b${static get styles(){return[...super.styles,v$]}};customElements.get("craft-tabs")||customElements.define("craft-tabs",y$);var _$=te`
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
`,Rp=class extends Ne{constructor(...e){super(...e),this.label=""}render(){const e=!!this.label||!!this.querySelector('[slot="header"]')||!!this.querySelector('[slot="label"]')||!!this.querySelector('[slot="actions"]'),t=!!this.querySelector('[slot="footer"]');return W`
      <div class="card">
        <div>
          ${e?W`<div class="card__header">
                <slot name="header">
                  <slot name="label" class="card__label" part="label"
                    >${this.label}</slot
                  >
                  <slot name="actions"></slot>
                </slot>
              </div>`:ht}

          <div class="card__body">
            <slot></slot>
          </div>

          ${t?W`<div class="card__footer"><slot name="footer"></slot></div>`:ht}
        </div>
      </div>
    `}};Rp.styles=[_$];se([V()],Rp.prototype,"label",void 0);customElements.get("craft-card")||customElements.define("craft-card",Rp);var w$=te`
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
`,NE=class extends Ne{render(){return W`<slot></slot> `}};NE.styles=[w$];customElements.get("craft-tab")||customElements.define("craft-tab",NE);class PE extends oE(Ne){static get properties(){return{checked:{type:Boolean,reflect:!0}}}static get styles(){return[te`
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
      `]}render(){return W`
      <div class="btn">
        <div class="switch-button__track"></div>
        <div class="switch-button__thumb"></div>
      </div>
    `}constructor(){super(),this.value="",this.checked=!1,this.__initialized=!1,this._toggleChecked=this._toggleChecked.bind(this),this.__handleKeydown=this._handleKeydown.bind(this),this.__handleKeyup=this._handleKeyup.bind(this)}connectedCallback(){super.connectedCallback(),this.setAttribute("role","switch"),this.setAttribute("aria-checked",`${this.checked}`),this.addEventListener("click",this._toggleChecked),this.addEventListener("keydown",this.__handleKeydown),this.addEventListener("keyup",this.__handleKeyup)}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener("click",this._toggleChecked),this.removeEventListener("keydown",this.__handleKeydown),this.removeEventListener("keyup",this.__handleKeyup)}_toggleChecked(){this.disabled||(this.focus(),this.checked=!this.checked)}__checkedStateChange(){this.dispatchEvent(new Event("checked-changed",{bubbles:!0})),this.setAttribute("aria-checked",`${this.checked}`)}_handleKeydown(t){t.key===" "&&t.preventDefault()}_handleKeyup(t){[" ","Enter"].includes(t.key)&&this._toggleChecked()}updated(t){super.updated(t),t.has("disabled")&&this.setAttribute("aria-disabled",`${this.disabled}`)}requestUpdate(t,r,n){super.requestUpdate(t,r,n),this.__initialized&&this.isConnected&&t==="checked"&&this.checked!==r&&!this.disabled&&this.__checkedStateChange()}firstUpdated(t){super.firstUpdated(t),this.__initialized=!0}}class E$ extends ma(Yc(ba)){static get styles(){return[...super.styles,te`
        :host([hidden]) {
          display: none;
        }

        :host([disabled]) {
          color: #adadad;
        }
      `]}static get scopedElements(){return{...super.scopedElements,"lion-switch-button":PE}}get _inputNode(){return Array.from(this.children).find(t=>t.slot==="input")}get slots(){return{...super.slots,input:()=>{const t=this.createScopedElement("lion-switch-button");return t.setAttribute("data-tag-name","lion-switch-button"),t}}}render(){return W`
      <div class="form-field__group-one">${this._groupOneTemplate()}</div>
      <div class="form-field__group-two">${this._groupTwoTemplate()}</div>
    `}_groupOneTemplate(){return W`${this._labelTemplate()} ${this._helpTextTemplate()} ${this._feedbackTemplate()}`}_groupTwoTemplate(){return W`${this._inputGroupTemplate()}`}constructor(){super(),this.checked=!1,this.__handleButtonSwitchCheckedChanged=this.__handleButtonSwitchCheckedChanged.bind(this)}connectedCallback(){super.connectedCallback(),this.addEventListener("checked-changed",this.__handleButtonSwitchCheckedChanged),this._labelNode&&this._labelNode.addEventListener("click",this._toggleChecked),this._syncButtonSwitch()}disconnectedCallback(){super.disconnectedCallback(),this._inputNode&&this.removeEventListener("checked-changed",this.__handleButtonSwitchCheckedChanged),this._labelNode&&this._labelNode.removeEventListener("click",this._toggleChecked)}updated(t){super.updated(t),t.has("disabled")&&this._syncButtonSwitch()}_toggleChecked(t){t.preventDefault(),super._toggleChecked(t)}_isEmpty(){return!1}__handleButtonSwitchCheckedChanged(t){t.stopPropagation(),this._isHandlingUserInput=!0,this.checked=this._inputNode.checked,this._isHandlingUserInput=!1}_syncButtonSwitch(){this._inputNode.disabled=this.disabled}_onLabelClick(){this.disabled||this._inputNode.focus()}}var IE=class extends PE{static get styles(){return[...super.styles,te`
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
      `]}};customElements.get("craft-switch-button")||customElements.define("craft-switch-button",IE);var x$=te`
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
`,S$=class extends E${static get styles(){return[...super.styles,qc,x$]}get slots(){return{...super.slots,input:()=>{const e=this.createScopedElement("craft-switch-button");return e.setAttribute("data-tag-name","craft-switch-button"),e}}}static get scopedElements(){return{...super.scopedElements,"craft-switch-button":IE}}};customElements.get("craft-switch")||customElements.define("craft-switch",S$);var C$=te`
  .breadcrumbs {
    display: flex;
    align-items: center;
  }
`,ii=class extends Ne{constructor(...e){super(...e),this.label="",this.items=[],this.visibleItems=0,this.firstRender=!0}getSeparator(){const e=this.separatorSlot.assignedElements({flatten:!0})[0].cloneNode(!0);return[e,...e.querySelectorAll("[id]")].forEach(t=>t.removeAttribute("id")),e.setAttribute("data-default",""),e.slot="separator",e}calculateBreadcrumbItemsWidth(){this.items=this.breadcrumbsElements.map((e,t)=>{let r=e.offsetWidth;return e.hasAttribute("hidden")&&(e.removeAttribute("hidden"),r=e.offsetWidth,e.setAttribute("hidden","")),{label:e.innerText,href:e.href,value:t.toString(),offsetWidth:r,isVisible:!0}})}async handleSlotChange(){const e=[...this.defaultSlot.assignedElements({flatten:!0})].filter(t=>t.tagName.toLowerCase()==="craft-breadcrumb-item");if(e.forEach((t,r)=>{const n=t.querySelector('[slot="separator"]');n===null?t.append(this.getSeparator()):n.hasAttribute("data-default")&&n.replaceWith(this.getSeparator()),r===e.length-1?t.setAttribute("aria-current","page"):t.removeAttribute("aria-current")}),this.breadcrumbsElements.length===0){this.items=[],this.visibleItems=0;return}await Promise.all(this.breadcrumbsElements.map(t=>t.updateComplete)),this.calculateBreadcrumbItemsWidth(),this.visibleItems=0,this.adjustOverflow()}connectedCallback(){super.connectedCallback(),this.hasAttribute("role")||this.setAttribute("role","navigation"),this.resizeObserver=new ResizeObserver(()=>{if(this.firstRender){this.firstRender=!1;return}this.adjustOverflow()}),this.resizeObserver.observe(this)}adjustOverflow(){const e=this.getBoundingClientRect().width;console.log({availableSpace:e})}disconnectedCallback(){this.resizeObserver?.unobserve(this),super.disconnectedCallback()}render(){return W`
      <nav class="breadcrumbs" aria-label="${this.label}">
        <slot @slotchange="${this.handleSlotChange}"></slot>
      </nav>

      <span hidden aria-hidden="true">
        <slot name="separator"><span class="separator">/</span></slot>
      </span>
    `}};ii.styles=[C$];se([ze("slot")],ii.prototype,"defaultSlot",void 0);se([ze('slot[name="separator"]')],ii.prototype,"separatorSlot",void 0);se([Mc({selector:"craft-breadcrumb-item"})],ii.prototype,"breadcrumbsElements",void 0);se([V()],ii.prototype,"label",void 0);se([ir()],ii.prototype,"items",void 0);se([ir()],ii.prototype,"visibleItems",void 0);customElements.get("craft-breadcrumbs")||customElements.define("craft-breadcrumbs",ii);var A$=`:host {
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
`,Pr=class extends Ht{constructor(){super(...arguments),this.renderType="button",this.rel="noreferrer noopener"}setRenderType(){const e=this.defaultSlot.assignedElements({flatten:!0}).filter(t=>t.tagName.toLowerCase()==="wa-dropdown").length>0;if(this.href){this.renderType="link";return}if(e){this.renderType="dropdown";return}this.renderType="button"}hrefChanged(){this.setRenderType()}handleSlotChange(){this.setRenderType()}render(){return W`
      <span part="start" class="start">
        <slot name="start"></slot>
      </span>

      ${this.renderType==="link"?W`
            <a
              part="label"
              class="label label-link"
              href="${this.href}"
              target="${Gt(this.target?this.target:void 0)}"
              rel=${Gt(this.target?this.rel:void 0)}
            >
              <slot></slot>
            </a>
          `:""}
      ${this.renderType==="button"?W`
            <button part="label" type="button" class="label label-button">
              <slot @slotchange=${this.handleSlotChange}></slot>
            </button>
          `:""}
      ${this.renderType==="dropdown"?W`
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
    `}};Pr.css=A$;$([ze("slot:not([name])")],Pr.prototype,"defaultSlot",2);$([ir()],Pr.prototype,"renderType",2);$([V()],Pr.prototype,"href",2);$([V()],Pr.prototype,"target",2);$([V()],Pr.prototype,"rel",2);$([ar("href",{waitUntilFirstUpdate:!0})],Pr.prototype,"hrefChanged",1);Pr=$([Fr("wa-breadcrumb-item")],Pr);var T$=class extends Pr{static get styles(){return[Pr.styles,te`
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
      `]}};customElements.get("craft-breadcrumb-item")||customElements.define("craft-breadcrumb-item",T$);var k$=`:host {
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
`,Ud=new Set,at=class extends Ht{constructor(){super(...arguments),this.anchor=null,this.placement="top",this.open=!1,this.distance=8,this.skidding=0,this.for=null,this.withoutArrow=!1,this.eventController=new AbortController,this.handleAnchorClick=()=>{this.open=!this.open},this.handleBodyClick=e=>{e.target.closest('[data-popover="close"]')&&(e.stopPropagation(),this.open=!1)},this.handleDocumentKeyDown=e=>{e.key==="Escape"&&(e.preventDefault(),this.open=!1,this.anchor&&typeof this.anchor.focus=="function"&&this.anchor.focus())},this.handleDocumentClick=e=>{const t=e.target;this.anchor&&e.composedPath().includes(this.anchor)||t.closest("wa-popover")!==this&&(this.open=!1)}}connectedCallback(){super.connectedCallback(),this.id||(this.id=Tp("wa-popover-"))}disconnectedCallback(){super.disconnectedCallback(),document.removeEventListener("keydown",this.handleDocumentKeyDown),this.eventController.abort()}firstUpdated(){this.open&&(this.dialog.show(),this.popup.active=!0,this.popup.reposition())}updated(e){e.has("open")&&this.customStates.set("open",this.open)}async handleOpenChange(){if(this.open){const e=new ca;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}Ud.forEach(t=>t.open=!1),document.addEventListener("keydown",this.handleDocumentKeyDown,{signal:this.eventController.signal}),document.addEventListener("click",this.handleDocumentClick,{signal:this.eventController.signal}),this.dialog.show(),this.popup.active=!0,Ud.add(this),requestAnimationFrame(()=>{const t=this.querySelector("[autofocus]");t&&typeof t.focus=="function"?t.focus():this.dialog.focus()}),await Ct(this.popup.popup,"show-with-scale"),this.popup.reposition(),this.dispatchEvent(new aa)}else{const e=new la;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!0;return}document.removeEventListener("keydown",this.handleDocumentKeyDown),document.removeEventListener("click",this.handleDocumentClick),Ud.delete(this),await Ct(this.popup.popup,"hide-with-scale"),this.popup.active=!1,this.dialog.close(),this.dispatchEvent(new oa)}}handleForChange(){const e=this.getRootNode();if(!e)return;const t=this.for?e.getElementById(this.for):null,r=this.anchor;if(t===r)return;const{signal:n}=this.eventController;t&&t.addEventListener("click",this.handleAnchorClick,{signal:n}),r&&r.removeEventListener("click",this.handleAnchorClick),this.anchor=t,this.for&&!t&&console.warn(`A popover was assigned to an element with an ID of "${this.for}" but the element could not be found.`,this)}async handleOptionsChange(){this.hasUpdated&&(await this.updateComplete,this.popup.reposition())}async show(){if(!this.open)return this.open=!0,Gl(this,"wa-after-show")}async hide(){if(this.open)return this.open=!1,Gl(this,"wa-after-hide")}render(){return W`
      <dialog part="dialog" class="dialog">
        <wa-popup
          part="popup"
          exportparts="
            popup:popup__popup,
            arrow:popup__arrow
          "
          class=${er({popover:!0,"popover-open":this.open})}
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
    `}};at.css=k$;at.dependencies={"wa-popup":ke};$([ze("dialog")],at.prototype,"dialog",2);$([ze(".body")],at.prototype,"body",2);$([ze("wa-popup")],at.prototype,"popup",2);$([ir()],at.prototype,"anchor",2);$([V()],at.prototype,"placement",2);$([V({type:Boolean,reflect:!0})],at.prototype,"open",2);$([V({type:Number})],at.prototype,"distance",2);$([V({type:Number})],at.prototype,"skidding",2);$([V()],at.prototype,"for",2);$([V({attribute:"without-arrow",type:Boolean,reflect:!0})],at.prototype,"withoutArrow",2);$([ar("open",{waitUntilFirstUpdate:!0})],at.prototype,"handleOpenChange",1);$([ar("for")],at.prototype,"handleForChange",1);$([ar(["distance","placement","skidding"])],at.prototype,"handleOptionsChange",1);at=$([Fr("wa-popover")],at);var O$=class extends at{static get styles(){return[at.styles,te`
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
      `]}};customElements.get("craft-popover")||customElements.define("craft-popover",O$);var Yh=new Set;function N$(){const e=document.documentElement.clientWidth;return Math.abs(window.innerWidth-e)}function P$(){const e=Number(getComputedStyle(document.body).paddingRight.replace(/px/,""));return isNaN(e)||!e?0:e}function Zl(e){if(Yh.add(e),!document.documentElement.classList.contains("wa-scroll-lock")){const t=N$()+P$();let r=getComputedStyle(document.documentElement).scrollbarGutter;(!r||r==="auto")&&(r="stable"),t<2&&(r=""),document.documentElement.style.setProperty("--wa-scroll-lock-gutter",r),document.documentElement.classList.add("wa-scroll-lock"),document.documentElement.style.setProperty("--wa-scroll-lock-size",`${t}px`)}}function ec(e){Yh.delete(e),Yh.size===0&&(document.documentElement.classList.remove("wa-scroll-lock"),document.documentElement.style.removeProperty("--wa-scroll-lock-size"))}function FE(e){return e.split(" ").map(t=>t.trim()).filter(t=>t!=="")}var I$=`:host {
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
`,br=class extends Ht{constructor(){super(...arguments),this.localize=new ks(this),this.hasSlotController=new Zc(this,"footer","header-actions","label"),this.open=!1,this.label="",this.placement="end",this.withoutHeader=!1,this.lightDismiss=!0,this.handleDocumentKeyDown=e=>{e.key==="Escape"&&this.open&&(e.preventDefault(),e.stopPropagation(),this.requestClose(this.drawer))}}firstUpdated(){this.open&&(this.addOpenListeners(),this.drawer.showModal(),Zl(this))}disconnectedCallback(){super.disconnectedCallback(),ec(this),this.removeOpenListeners()}async requestClose(e){const t=new la({source:e});if(this.dispatchEvent(t),t.defaultPrevented){this.open=!0,Ct(this.drawer,"pulse");return}this.removeOpenListeners(),await Ct(this.drawer,"hide"),this.open=!1,this.drawer.close(),ec(this);const r=this.originalTrigger;typeof r?.focus=="function"&&setTimeout(()=>r.focus()),this.dispatchEvent(new oa)}addOpenListeners(){document.addEventListener("keydown",this.handleDocumentKeyDown)}removeOpenListeners(){document.removeEventListener("keydown",this.handleDocumentKeyDown)}handleDialogCancel(e){e.preventDefault(),!this.drawer.classList.contains("hide")&&e.target===this.drawer&&this.requestClose(this.drawer)}handleDialogClick(e){const r=e.target.closest('[data-drawer="close"]');r&&(e.stopPropagation(),this.requestClose(r))}async handleDialogPointerDown(e){e.target===this.drawer&&(this.lightDismiss?this.requestClose(this.drawer):await Ct(this.drawer,"pulse"))}handleOpenChange(){this.open&&!this.drawer.open?this.show():this.drawer.open&&(this.open=!0,this.requestClose(this.drawer))}async show(){const e=new ca;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}this.addOpenListeners(),this.originalTrigger=document.activeElement,this.open=!0,this.drawer.showModal(),Zl(this),requestAnimationFrame(()=>{const t=this.querySelector("[autofocus]");t&&typeof t.focus=="function"?t.focus():this.drawer.focus()}),await Ct(this.drawer,"show"),this.dispatchEvent(new aa)}render(){const e=!this.withoutHeader,t=this.hasSlotController.test("footer");return W`
      <dialog
        part="dialog"
        class=${er({drawer:!0,open:this.open,top:this.placement==="top",end:this.placement==="end",bottom:this.placement==="bottom",start:this.placement==="start"})}
        @cancel=${this.handleDialogCancel}
        @click=${this.handleDialogClick}
        @pointerdown=${this.handleDialogPointerDown}
      >
        ${e?W`
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

        ${t?W`
              <footer part="footer" class="footer">
                <slot name="footer"></slot>
              </footer>
            `:""}
      </dialog>
    `}};br.css=I$;$([ze(".drawer")],br.prototype,"drawer",2);$([V({type:Boolean,reflect:!0})],br.prototype,"open",2);$([V({reflect:!0})],br.prototype,"label",2);$([V({reflect:!0})],br.prototype,"placement",2);$([V({attribute:"without-header",type:Boolean,reflect:!0})],br.prototype,"withoutHeader",2);$([V({attribute:"light-dismiss",type:Boolean})],br.prototype,"lightDismiss",2);$([ar("open",{waitUntilFirstUpdate:!0})],br.prototype,"handleOpenChange",1);br=$([Fr("wa-drawer")],br);document.addEventListener("click",e=>{const t=e.target.closest("[data-drawer]");if(t instanceof Element){const[r,n]=FE(t.getAttribute("data-drawer")||"");if(r==="open"&&n?.length){const s=t.getRootNode().getElementById(n);s?.localName==="wa-drawer"?s.open=!0:console.warn(`A drawer with an ID of "${n}" could not be found in this document.`)}}});document.body.addEventListener("pointerdown",()=>{});var F$=()=>({checkValidity(e){const t=e.input,r={message:"",isValid:!0,invalidKeys:[]};if(!t)return r;let n=!0;if("checkValidity"in t&&(n=t.checkValidity()),n)return r;if(r.isValid=!1,"validationMessage"in t&&(r.message=t.validationMessage),!("validity"in t))return r.invalidKeys.push("customError"),r;for(const i in t.validity){if(i==="valid")continue;const s=i;t.validity[s]&&r.invalidKeys.push(s)}return r}});var LE=class extends Event{constructor(){super("wa-invalid",{bubbles:!0,cancelable:!1,composed:!0})}},L$=()=>({observedAttributes:["custom-error"],checkValidity(e){const t={message:"",isValid:!0,invalidKeys:[]};return e.customError&&(t.message=e.customError,t.isValid=!1,t.invalidKeys=["customError"]),t}}),Cn=class extends Ht{constructor(){super(),this.name=null,this.disabled=!1,this.required=!1,this.assumeInteractionOn=["input"],this.validators=[],this.valueHasChanged=!1,this.hasInteracted=!1,this.customError=null,this.emittedEvents=[],this.emitInvalid=e=>{e.target===this&&(this.hasInteracted=!0,this.dispatchEvent(new LE))},this.handleInteraction=e=>{const t=this.emittedEvents;t.includes(e.type)||t.push(e.type),t.length===this.assumeInteractionOn?.length&&(this.hasInteracted=!0)},this.addEventListener("invalid",this.emitInvalid)}static get validators(){return[L$()]}static get observedAttributes(){const e=new Set(super.observedAttributes||[]);for(const t of this.validators)if(t.observedAttributes)for(const r of t.observedAttributes)e.add(r);return[...e]}connectedCallback(){super.connectedCallback(),this.updateValidity(),this.assumeInteractionOn.forEach(e=>{this.addEventListener(e,this.handleInteraction)})}firstUpdated(...e){super.firstUpdated(...e),this.updateValidity()}willUpdate(e){if(e.has("customError")&&(this.customError||(this.customError=null),this.setCustomValidity(this.customError||"")),e.has("value")||e.has("disabled")){const t=this.value;if(Array.isArray(t)){if(this.name){const r=new FormData;for(const n of t)r.append(this.name,n);this.setValue(r,r)}}else this.setValue(t,t)}e.has("disabled")&&(this.customStates.set("disabled",this.disabled),(this.hasAttribute("disabled")||!this.matches(":disabled"))&&this.toggleAttribute("disabled",this.disabled)),this.updateValidity(),super.willUpdate(e)}get labels(){return this.internals.labels}getForm(){return this.internals.form}get validity(){return this.internals.validity}get willValidate(){return this.internals.willValidate}get validationMessage(){return this.internals.validationMessage}checkValidity(){return this.updateValidity(),this.internals.checkValidity()}reportValidity(){return this.updateValidity(),this.hasInteracted=!0,this.internals.reportValidity()}get validationTarget(){return this.input||void 0}setValidity(...e){const t=e[0],r=e[1];let n=e[2];n||(n=this.validationTarget),this.internals.setValidity(t,r,n||void 0),this.requestUpdate("validity"),this.setCustomStates()}setCustomStates(){const e=!!this.required,t=this.internals.validity.valid,r=this.hasInteracted;this.customStates.set("required",e),this.customStates.set("optional",!e),this.customStates.set("invalid",!t),this.customStates.set("valid",t),this.customStates.set("user-invalid",!t&&r),this.customStates.set("user-valid",t&&r)}setCustomValidity(e){if(!e){this.customError=null,this.setValidity({});return}this.customError=e,this.setValidity({customError:!0},e,this.validationTarget)}formResetCallback(){this.resetValidity(),this.hasInteracted=!1,this.valueHasChanged=!1,this.emittedEvents=[],this.updateValidity()}formDisabledCallback(e){this.disabled=e,this.updateValidity()}formStateRestoreCallback(e,t){this.value=e,t==="restore"&&this.resetValidity(),this.updateValidity()}setValue(...e){const[t,r]=e;this.internals.setFormValue(t,r)}get allValidators(){const e=this.constructor.validators||[],t=this.validators||[];return[...e,...t]}resetValidity(){this.setCustomValidity(""),this.setValidity({})}updateValidity(){if(this.disabled||this.hasAttribute("disabled")||!this.willValidate){this.resetValidity();return}const e=this.allValidators;if(!e?.length)return;const t={customError:!!this.customError},r=this.validationTarget||this.input||void 0;let n="";for(const i of e){const{isValid:s,message:o,invalidKeys:a}=i.checkValidity(this);s||(n||(n=o),a?.length>=0&&a.forEach(l=>t[l]=!0))}n||(n=this.validationMessage),this.setValidity(t,n,r)}};Cn.formAssociated=!0;$([V({reflect:!0})],Cn.prototype,"name",2);$([V({type:Boolean})],Cn.prototype,"disabled",2);$([V({state:!0,attribute:!1})],Cn.prototype,"valueHasChanged",2);$([V({state:!0,attribute:!1})],Cn.prototype,"hasInteracted",2);$([V({attribute:"custom-error",reflect:!0})],Cn.prototype,"customError",2);$([V({attribute:!1,state:!0,type:Object})],Cn.prototype,"validity",1);var R$=`@layer wa-utilities {
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
`;const RE=Symbol.for(""),M$=e=>{if(e?.r===RE)return e?._$litStatic$},hv=(e,...t)=>({_$litStatic$:t.reduce(((r,n,i)=>r+(s=>{if(s._$litStatic$!==void 0)return s._$litStatic$;throw Error(`Value passed to 'literal' function must be a 'literal' result: ${s}. Use 'unsafeStatic' to pass non-literal values, but
            take care to ensure page security.`)})(n)+e[i+1]),e[0]),r:RE}),fv=new Map,D$=e=>(t,...r)=>{const n=r.length;let i,s;const o=[],a=[];let l,u=0,c=!1;for(;u<n;){for(l=t[u];u<n&&(s=r[u],(i=M$(s))!==void 0);)l+=i+t[++u],c=!0;u!==n&&a.push(s),o.push(l),u++}if(u===n&&o.push(t[n]),c){const d=o.join("$$lit$$");(t=fv.get(d))===void 0&&(o.raw=o,fv.set(d,t=o)),r=a}return e(t,...r)},zd=D$(W);var $$=`@layer wa-component {
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
`,Ce=class extends Cn{constructor(){super(...arguments),this.assumeInteractionOn=["click"],this.hasSlotController=new Zc(this,"[default]","start","end"),this.localize=new ks(this),this.invalid=!1,this.isIconButton=!1,this.title="",this.variant="neutral",this.appearance="accent",this.size="medium",this.withCaret=!1,this.disabled=!1,this.loading=!1,this.pill=!1,this.type="button",this.form=null}static get validators(){return[...super.validators,F$()]}constructLightDOMButton(){const e=document.createElement("button");return e.type=this.type,e.style.position="absolute",e.style.width="0",e.style.height="0",e.style.clipPath="inset(50%)",e.style.overflow="hidden",e.style.whiteSpace="nowrap",this.name&&(e.name=this.name),e.value=this.value||"",["form","formaction","formenctype","formmethod","formnovalidate","formtarget"].forEach(t=>{this.hasAttribute(t)&&e.setAttribute(t,this.getAttribute(t))}),e}handleClick(){if(!this.getForm())return;const t=this.constructLightDOMButton();this.parentElement?.append(t),t.click(),t.remove()}handleInvalid(){this.dispatchEvent(new LE)}handleLabelSlotChange(){const e=this.labelSlot.assignedNodes({flatten:!0});let t=!1,r=!1,n=!1,i=!1;[...e].forEach(s=>{if(s.nodeType===Node.ELEMENT_NODE){const o=s;o.localName==="wa-icon"?(r=!0,t||(t=o.label!==void 0)):i=!0}else s.nodeType===Node.TEXT_NODE&&(s.textContent?.trim()||"").length>0&&(n=!0)}),this.isIconButton=r&&!n&&!i,this.isIconButton&&!t&&console.warn('Icon buttons must have a label for screen readers. Add <wa-icon label="..."> to remove this warning.',this)}isButton(){return!this.href}isLink(){return!!this.href}handleDisabledChange(){this.updateValidity()}setValue(...e){}click(){this.button.click()}focus(e){this.button.focus(e)}blur(){this.button.blur()}render(){const e=this.isLink(),t=e?hv`a`:hv`button`;return zd`
      <${t}
        part="base"
        class=${er({button:!0,caret:this.withCaret,disabled:this.disabled,loading:this.loading,rtl:this.localize.dir()==="rtl","has-label":this.hasSlotController.test("[default]"),"has-start":this.hasSlotController.test("start"),"has-end":this.hasSlotController.test("end"),"is-icon-button":this.isIconButton})}
        ?disabled=${Gt(e?void 0:this.disabled)}
        type=${Gt(e?void 0:this.type)}
        title=${this.title}
        name=${Gt(e?void 0:this.name)}
        value=${Gt(e?void 0:this.value)}
        href=${Gt(e?this.href:void 0)}
        target=${Gt(e?this.target:void 0)}
        download=${Gt(e?this.download:void 0)}
        rel=${Gt(e&&this.rel?this.rel:void 0)}
        role=${Gt(e?void 0:"button")}
        aria-disabled=${this.disabled?"true":"false"}
        tabindex=${this.disabled?"-1":"0"}
        @invalid=${this.isButton()?this.handleInvalid:null}
        @click=${this.handleClick}
      >
        <slot name="start" part="start" class="start"></slot>
        <slot part="label" class="label" @slotchange=${this.handleLabelSlotChange}></slot>
        <slot name="end" part="end" class="end"></slot>
        ${this.withCaret?zd`
                <wa-icon part="caret" class="caret" library="system" name="chevron-down" variant="solid"></wa-icon>
              `:""}
        ${this.loading?zd`<wa-spinner part="spinner"></wa-spinner>`:""}
      </${t}>
    `}};Ce.shadowRootOptions={...Cn.shadowRootOptions,delegatesFocus:!0};Ce.css=[$$,R$,kE];$([ze(".button")],Ce.prototype,"button",2);$([ze("slot:not([name])")],Ce.prototype,"labelSlot",2);$([ir()],Ce.prototype,"invalid",2);$([ir()],Ce.prototype,"isIconButton",2);$([V()],Ce.prototype,"title",2);$([V({reflect:!0})],Ce.prototype,"variant",2);$([V({reflect:!0})],Ce.prototype,"appearance",2);$([V({reflect:!0})],Ce.prototype,"size",2);$([V({attribute:"with-caret",type:Boolean,reflect:!0})],Ce.prototype,"withCaret",2);$([V({type:Boolean})],Ce.prototype,"disabled",2);$([V({type:Boolean,reflect:!0})],Ce.prototype,"loading",2);$([V({type:Boolean,reflect:!0})],Ce.prototype,"pill",2);$([V()],Ce.prototype,"type",2);$([V({reflect:!0})],Ce.prototype,"name",2);$([V({reflect:!0})],Ce.prototype,"value",2);$([V({reflect:!0})],Ce.prototype,"href",2);$([V()],Ce.prototype,"target",2);$([V()],Ce.prototype,"rel",2);$([V()],Ce.prototype,"download",2);$([V({reflect:!0})],Ce.prototype,"form",2);$([V({attribute:"formaction"})],Ce.prototype,"formAction",2);$([V({attribute:"formenctype"})],Ce.prototype,"formEnctype",2);$([V({attribute:"formmethod"})],Ce.prototype,"formMethod",2);$([V({attribute:"formnovalidate",type:Boolean})],Ce.prototype,"formNoValidate",2);$([V({attribute:"formtarget"})],Ce.prototype,"formTarget",2);$([ar("disabled",{waitUntilFirstUpdate:!0})],Ce.prototype,"handleDisabledChange",1);Ce=$([Fr("wa-button")],Ce);var V$=`:host {
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
`,Xh=class extends Ht{constructor(){super(...arguments),this.localize=new ks(this)}render(){return W`
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
    `}};Xh.css=V$;Xh=$([Fr("wa-spinner")],Xh);var B$=class extends br{static get styles(){return[br.styles,te`
        :host {
          --wa-color-surface-raised: var(--c-bg-raised);
          --spacing: var(--c-spacing-lg);
          background-color: red;
        }
      `]}};customElements.get("craft-drawer")||customElements.define("craft-drawer",B$);var U$=`:host {
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
`,Ir=class extends Ht{constructor(){super(...arguments),this.localize=new ks(this),this.hasSlotController=new Zc(this,"footer","header-actions","label"),this.open=!1,this.label="",this.withoutHeader=!1,this.lightDismiss=!1,this.handleDocumentKeyDown=e=>{e.key==="Escape"&&this.open&&(e.preventDefault(),e.stopPropagation(),this.requestClose(this.dialog))}}firstUpdated(){this.open&&(this.addOpenListeners(),this.dialog.showModal(),Zl(this))}disconnectedCallback(){super.disconnectedCallback(),ec(this),this.removeOpenListeners()}async requestClose(e){const t=new la({source:e});if(this.dispatchEvent(t),t.defaultPrevented){this.open=!0,Ct(this.dialog,"pulse");return}this.removeOpenListeners(),await Ct(this.dialog,"hide"),this.open=!1,this.dialog.close(),ec(this);const r=this.originalTrigger;typeof r?.focus=="function"&&setTimeout(()=>r.focus()),this.dispatchEvent(new oa)}addOpenListeners(){document.addEventListener("keydown",this.handleDocumentKeyDown)}removeOpenListeners(){document.removeEventListener("keydown",this.handleDocumentKeyDown)}handleDialogCancel(e){e.preventDefault(),!this.dialog.classList.contains("hide")&&e.target===this.dialog&&this.requestClose(this.dialog)}handleDialogClick(e){const r=e.target.closest('[data-dialog="close"]');r&&(e.stopPropagation(),this.requestClose(r))}async handleDialogPointerDown(e){e.target===this.dialog&&(this.lightDismiss?this.requestClose(this.dialog):await Ct(this.dialog,"pulse"))}handleOpenChange(){this.open&&!this.dialog.open?this.show():!this.open&&this.dialog.open&&(this.open=!0,this.requestClose(this.dialog))}async show(){const e=new ca;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}this.addOpenListeners(),this.originalTrigger=document.activeElement,this.open=!0,this.dialog.showModal(),Zl(this),requestAnimationFrame(()=>{const t=this.querySelector("[autofocus]");t&&typeof t.focus=="function"?t.focus():this.dialog.focus()}),await Ct(this.dialog,"show"),this.dispatchEvent(new aa)}render(){const e=!this.withoutHeader,t=this.hasSlotController.test("footer");return W`
      <dialog
        part="dialog"
        class=${er({dialog:!0,open:this.open})}
        @cancel=${this.handleDialogCancel}
        @click=${this.handleDialogClick}
        @pointerdown=${this.handleDialogPointerDown}
      >
        ${e?W`
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

        ${t?W`
              <footer part="footer" class="footer">
                <slot name="footer"></slot>
              </footer>
            `:""}
      </dialog>
    `}};Ir.css=U$;$([ze(".dialog")],Ir.prototype,"dialog",2);$([V({type:Boolean,reflect:!0})],Ir.prototype,"open",2);$([V({reflect:!0})],Ir.prototype,"label",2);$([V({attribute:"without-header",type:Boolean,reflect:!0})],Ir.prototype,"withoutHeader",2);$([V({attribute:"light-dismiss",type:Boolean})],Ir.prototype,"lightDismiss",2);$([ar("open",{waitUntilFirstUpdate:!0})],Ir.prototype,"handleOpenChange",1);Ir=$([Fr("wa-dialog")],Ir);document.addEventListener("click",e=>{const t=e.target.closest("[data-dialog]");if(t instanceof Element){const[r,n]=FE(t.getAttribute("data-dialog")||"");if(r==="open"&&n?.length){const s=t.getRootNode().getElementById(n);s?.localName==="wa-dialog"?s.open=!0:console.warn(`A dialog with an ID of "${n}" could not be found in this document.`)}}});document.addEventListener("pointerdown",()=>{});var z$=class extends Ir{static get styles(){return[Ir.styles,te`
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
      `]}};customElements.get("craft-dialog")||customElements.define("craft-dialog",z$);class pv extends Gc(pE(Ne)){constructor(){super(),this.multipleChoice=!0}}class mv extends Yc(Xc){connectedCallback(){super.connectedCallback(),this.type="checkbox"}}var H$=class extends pv{static get styles(){return[...pv.styles,te`
        .form-field__group-two {
          margin-top: var(--c-spacing-sm);
        }

        ::slotted(label) {
          font-weight: bold;
        }
      `]}};customElements.get("craft-checkbox-group")||customElements.define("craft-checkbox-group",H$);var q$=class extends mv{static get styles(){return[...mv.styles,te`
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
      `]}};customElements.get("craft-checkbox")||customElements.define("craft-checkbox",q$);const Sr={Default:"default",Success:"success",Warning:"warning",Danger:"danger",Info:"info"},j$={OutlineFill:"outline-fill"};var Mp=te`
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
`,W$=te`
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
`,si=class extends Ne{constructor(...e){super(...e),this.variant=Sr.Default,this.appearance=j$.OutlineFill,this.title="",this.icon=null,this.rounded="all",this.inline=!1}getDefaultIcon(){switch(this.variant){case Sr.Info:return"lightbulb";case Sr.Success:return"circle-check";case Sr.Warning:return"circle-exclamation";case Sr.Danger:return"triangle-exclamation";default:return null}}render(){return W`
      ${this.icon||this.querySelector('[slot="icon"]')?W`<slot name="icon" class="callout__icon">
            <craft-icon
              name="${this.getDefaultIcon()}"
              style="font-size: 0.9em"
            ></craft-icon>
          </slot>`:ht}
      <div class="callout__body">
        <slot name="title" class="callout__title">${this.title}</slot>
        <div class="callout__description">
          <slot></slot>
        </div>
      </div>
    `}};si.styles=[Mp,W$];se([V({reflect:!0})],si.prototype,"variant",void 0);se([V({reflect:!0})],si.prototype,"appearance",void 0);se([V()],si.prototype,"title",void 0);se([V()],si.prototype,"icon",void 0);se([V({reflect:!0})],si.prototype,"rounded",void 0);se([V({reflect:!0,type:Boolean})],si.prototype,"inline",void 0);customElements.get("craft-callout")||customElements.define("craft-callout",si);var K$=te`
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
`,Ls=class extends Ne{constructor(...e){super(...e),this.icon=null,this.href=null,this.disabled=!1,this.variant=Sr.Default}renderBody(){return W`
      <span class="action-item__prefix">
        <slot name="prefix">
          <slot name="icon">
            ${this.icon?W`<craft-icon name="${this.icon}"></craft-icon>`:ht}
          </slot>
        </slot>
      </span>

      <slot></slot>

      <span class="action-item__suffix">
        <slot name="suffix"></slot>
      </span>
    `}render(){return this.href?W`
          <a class="action-item" href="${this.href}"> ${this.renderBody()} </a>
        `:W`
          <button
            type="button"
            class="action-item"
            ?disabled="${this.disabled}"
          >
            ${this.renderBody()}
          </button>
        `}};Ls.styles=[Mp,K$];se([V()],Ls.prototype,"icon",void 0);se([V()],Ls.prototype,"href",void 0);se([V({type:Boolean})],Ls.prototype,"disabled",void 0);se([V({reflect:!0})],Ls.prototype,"variant",void 0);customElements.get("craft-action-item")||customElements.define("craft-action-item",Ls);const G$=te`
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
`;class on{static __createGlobalStyleNode(){const t=document.createElement("style");return t.setAttribute("data-overlays",""),t.textContent=G$.cssText,document.head.appendChild(t),t}get list(){return this.__list}get shownList(){return this.__shownList}constructor(){this.__list=[],this.__shownList=[],this.__siblingsInert=!1,this.__blockingMap=new WeakMap,on.__globalStyleNode||(on.__globalStyleNode=on.__createGlobalStyleNode())}add(t){if(this.list.find(r=>t===r))throw new Error("controller instance is already added");return this.list.push(t),t}remove(t){if(!this.list.find(r=>t===r))throw new Error("could not find controller to remove");this.__list=this.list.filter(r=>r!==t),this.__shownList=this.shownList.filter(r=>r!==t)}show(t){this.list.find(r=>t===r)&&this.hide(t),this.__shownList.unshift(t),Array.from(this.__shownList).reverse().forEach((r,n)=>{r.elevation=n+1})}hide(t){if(!this.list.find(r=>t===r))throw new Error("could not find controller to hide");this.__shownList=this.shownList.filter(r=>r!==t)}teardown(){this.list.forEach(t=>{t.teardown()}),this.__list=[],this.__shownList=[],this.__siblingsInert=!1,on.__globalStyleNode&&(document.head.removeChild(on.__globalStyleNode),on.__globalStyleNode=void 0)}get siblingsInert(){return this.__siblingsInert}disableTrapsKeyboardFocusForAll(){this.shownList.forEach(t=>{t.trapsKeyboardFocus===!0&&t.disableTrapsKeyboardFocus&&t.disableTrapsKeyboardFocus({findNewTrap:!1})})}informTrapsKeyboardFocusGotEnabled(t){this.siblingsInert===!1&&t==="global"&&(this.__siblingsInert=!0)}informTrapsKeyboardFocusGotDisabled({disabledCtrl:t,findNewTrap:r=!0}={}){const n=this.shownList.find(i=>i!==t&&i.trapsKeyboardFocus===!0);n?r&&n.enableTrapsKeyboardFocus():this.siblingsInert===!0&&(this.__siblingsInert=!1)}requestToPreventScroll(){const{isIOS:t,isMacSafari:r}=Yl;document.body.classList.add("overlays-scroll-lock"),(t||r)&&document.body.classList.add("overlays-scroll-lock-ios-fix"),t&&document.documentElement.classList.add("overlays-scroll-lock-ios-fix")}requestToEnableScroll(){if(this.shownList.some(i=>i.preventsScroll===!0))return;const{isIOS:r,isMacSafari:n}=Yl;document.body.classList.remove("overlays-scroll-lock"),(r||n)&&document.body.classList.remove("overlays-scroll-lock-ios-fix"),r&&document.documentElement.classList.remove("overlays-scroll-lock-ios-fix")}requestToShowOnly(t){const r=this.shownList.filter(n=>n!==t);r.forEach(n=>n.hide()),this.__blockingMap.set(t,r)}retractRequestToShowOnly(t){this.__blockingMap.has(t)&&this.__blockingMap.get(t).forEach(n=>n.show())}}on.__globalStyleNode=void 0;const Y$=sl.get("@lion/ui::overlays::0.x")||new on;function Jh(){let e=document.activeElement||document.body;for(;e&&e.shadowRoot&&e.shadowRoot.activeElement;)e=e.shadowRoot.activeElement;return e}const gv=({visibility:e,display:t})=>e!=="hidden"&&t!=="none",X$=({display:e})=>e==="contents";function J$(e){if(!e||!e.isConnected||!gv(e.style))return!1;const t=window.getComputedStyle(e);return gv(t)?X$(t)?!0:!!(e.offsetWidth||e.offsetHeight||e.getClientRects().length):!1}function Q$(e,t){const r=Math.max(e.tabIndex,0),n=Math.max(t.tabIndex,0);return r===0||n===0?n>r:r>n}function Z$(e,t){const r=[];for(;e.length>0&&t.length>0;)Q$(e[0],t[0])?r.push(t.shift()):r.push(e.shift());return[...r,...e,...t]}function Qh(e){const t=e.length;if(t<2)return e;const r=Math.ceil(t/2),n=Qh(e.slice(0,r)),i=Qh(e.slice(r));return Z$(n,i)}const Hd="matches"in Element.prototype?"matches":"msMatchesSelector";function eV(e){return e[Hd]("input, select, textarea, button, object")?e[Hd](":not([disabled])"):e[Hd]("a[href], area[href], iframe, [tabindex], [contentEditable]")}function tV(e){return eV(e)?Number(e.getAttribute("tabindex")||0):-1}function rV(e){if(e.localName==="slot")return e.assignedNodes({flatten:!0});const{children:t}=e.shadowRoot||e;return t||[]}function nV(e){return e.nodeType!==Node.ELEMENT_NODE?!1:e.localName==="slot"?!0:J$(e)}function ME(e,t){if(!nV(e))return!1;const r=e,n=tV(r);let i=n>0;n>=0&&t.push(r);const s=rV(r);for(let o=0;o<s.length;o+=1)i=ME(s[o],t)||i;return i}function DE(e){const t=[];return ME(e,t)?Qh(t):t}function _s(e,t,r={}){function n(m){return"getAttribute"in m}function i(m){if(!n(m))return null;const h=m.getAttribute("slot");let f=null;if(h){const b=r[h];b&&(f=b.filter(y=>y?.element===m)[0]||null)}return f}const s=i(e);if(s)return s.deepContains;function o(m){if(!n(e))return;const h=e.getAttribute("slot");h&&(r[h]=r[h]||[],r[h].push({element:e,deepContains:m}))}let a=e.contains(t);if(a)return o(!0),!0;function l(m){return m.tagName==="SLOT"}function u(m){return l(m)?m.assignedElements():[]}function c(m){return m.nodeType===Node.DOCUMENT_FRAGMENT_NODE}function d(m){let h=!1;for(let f=0;f<m.length;f+=1){const b=m[f];if(b&&(n(b)||c(b))&&_s(b,t,r)){h=!0;break}}return h}function p(m){for(let h=0;h<m.children.length;h+=1){const f=m.children[h],b=i(f);if(b){a=b.deepContains||a;break}const y=u(f),w=[f.shadowRoot,...y];if(d(w)){a=!0;break}f.children.length>0&&p(f)}}return e instanceof HTMLElement&&e.shadowRoot&&(a=_s(e.shadowRoot,t,r),a)?(o(!0),!0):(p(e),o(a),a)}const iV={tab:9};function sV(e,t){const r=DE(e);let n;r.length>=2?n=[r[0],r[r.length-1]]:r.length===1?n=[r[0],r[0]]:n=[e,e],t.shiftKey&&n.reverse();const[i,s]=n,o=Jh();o===e||r.includes(o)&&s!==o||(t.preventDefault(),i.focus())}function oV(e){const t=DE(e),r=t.find(p=>p.hasAttribute("autofocus"))||e;let n,i;r===e&&(e.tabIndex=-1,e.style.setProperty("outline","none")),r.focus();function s(p){p.keyCode===iV.tab&&sV(e,p)}function o(){n=document.createElement("div"),n.style.display="none",n.setAttribute("data-is-tab-detection-element",""),e.insertBefore(n,e.children[0]),i=new MutationObserver(p=>{for(const m of p)if(m.type==="childList"){const h=!Array.from(e.children).find(b=>b.hasAttribute("data-is-tab-detection-element")),f=Array.from(m.addedNodes).find(b=>b instanceof HTMLElement&&b.hasAttribute("data-is-tab-detection-element"));h&&!f&&(i.disconnect(),o())}}),i.observe(e,{childList:!0})}function a(){return n.compareDocumentPosition(document.activeElement)===Node.DOCUMENT_POSITION_PRECEDING}function l({resetToRoot:p=!1}={}){if(_s(e,Jh()))return;let m;p?m=e:m=t[a()?0:t.length-1],m&&m.focus()}function u(){window.removeEventListener("focusin",u),l()}function c(){setTimeout(()=>{_s(e,Jh())||l({resetToRoot:!0})}),window.addEventListener("focusin",u)}function d(){window.removeEventListener("keydown",s),window.removeEventListener("focusin",u),window.removeEventListener("focusout",c),i.disconnect(),Array.from(e.children).includes(n)&&e.removeChild(n),e.style.removeProperty("outline")}return window.addEventListener("keydown",s),window.addEventListener("focusout",c),o(),{disconnect:d}}const bv=te`
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
`,ws={supportsAdoptingStyleSheets:window.ShadowRoot&&(window.ShadyCSS===void 0||window.ShadyCSS.nativeShadow)&&"adoptedStyleSheets"in Document.prototype&&"replace"in CSSStyleSheet.prototype,adoptStyle:void 0,adoptStyles:void 0},qd=new WeakMap;function aV(e){return Array.from(e.cssRules).map(t=>t.cssText).join("")}function lV(e,t,{teardown:r=!1}={}){const n=e===document?document.body:e,i=t.cssText||aV(t);if(r){const s=Array.from(n.querySelectorAll("style"));for(const o of s)if(o.textContent===i){o.remove();break}}else{const s=document.createElement("style"),o=window.litNonce;o!==void 0&&s.setAttribute("nonce",o),s.textContent=i,n.appendChild(s)}}function cV(e,t,{teardown:r=!1}={}){let n=!1;e&&!qd.has(e)&&qd.set(e,[]);const i=qd.get(e)??[],s=i.find(o=>t===o);return s&&r?i.splice(i.indexOf(t),1):!s&&!r?i.push(t):(s&&!r||!s&&r)&&(n=!0),{haltFurtherExecution:n}}function uV(e,t,{teardown:r=!1}={}){const{haltFurtherExecution:n}=cV(e,t,{teardown:r});if(n)return;if(!ws.supportsAdoptingStyleSheets||Yl.isIOS){lV(e,t,{teardown:r});return}const i=t instanceof CSSStyleSheet?t:t.styleSheet;if(!i)throw new Error("Please provide a CSSResultOrNative style");r?e.adoptedStyleSheets.includes(i)&&e.adoptedStyleSheets.splice(e.adoptedStyleSheets.indexOf(i),1):e.adoptedStyleSheets=[...e.adoptedStyleSheets,i]}function dV(e,t,{teardown:r=!1}={}){for(const n of t)ws.adoptStyle(e,n,{teardown:r})}ws.adoptStyle=uV;ws.adoptStyles=dV;function hV({wrappingDialogNodeL1:e,contentWrapperNodeL2:t,contentNodeL3:r}){if(!(t.isConnected||r.isConnected))throw new Error('[OverlayController] Could not find a render target, since the provided contentNode is not connected to the DOM. Make sure that it is connected, e.g. by doing "document.body.appendChild(contentNode)", before passing it on.');let n;const i=document.createComment("tempMarker");t.isConnected?(n=t.parentElement||t.getRootNode(),n.insertBefore(i,t),e.appendChild(t)):r.assignedSlot?(n=r.assignedSlot.parentElement||r.assignedSlot.getRootNode(),n.insertBefore(i,r.assignedSlot),e.appendChild(t),t.appendChild(r.assignedSlot)):(n=r.parentElement||r.getRootNode(),n.insertBefore(i,r),e.appendChild(t),t.appendChild(r)),n.insertBefore(e,i),n?.removeChild(i)}async function fV(){return Y(()=>import("./popper.js"),[],import.meta.url)}const vv=new WeakMap;class vi extends EventTarget{constructor(t={},r=Y$){super(),this.manager=r,this.__sharedConfig=t,this.__activeElementRightBeforeHide=null,this.config={},this._defaultConfig={placementMode:void 0,contentNode:t.contentNode,contentWrapperNode:t.contentWrapperNode,invokerNode:t.invokerNode,backdropNode:t.backdropNode,referenceNode:void 0,elementToFocusAfterHide:t.invokerNode,inheritsReferenceWidth:"none",hasBackdrop:!1,isBlocking:!1,preventsScroll:!1,trapsKeyboardFocus:!1,hidesOnEsc:!1,hidesOnOutsideEsc:!1,hidesOnOutsideClick:!1,isTooltip:!1,isAlertDialog:!1,invokerRelation:"description",visibilityTriggerFunction:void 0,handlesAccessibility:!1,popperConfig:{placement:"top",strategy:"fixed",modifiers:[{name:"preventOverflow",enabled:!0,options:{boundariesElement:"viewport",padding:8}},{name:"flip",options:{boundariesElement:"viewport",padding:16}},{name:"offset",enabled:!0,options:{offset:[0,8]}},{name:"arrow",enabled:!1}]},viewportConfig:{placement:"center"},zIndex:9999},this._contentId=`overlay-content--${Math.random().toString(36).slice(2,10)}`,this.__originalAttrs=new Map,this.updateConfig(t),this.__hasActiveTrapsKeyboardFocus=!1,this.__hasActiveBackdrop=!0,this.__escKeyHandler=this.__escKeyHandler.bind(this),this.__cancelHandler=this.__cancelHandler.bind(this)}get invoker(){return this.invokerNode}get content(){return this.__wrappingDialogNode}get placementMode(){return this.config?.placementMode}get invokerNode(){return this.config?.invokerNode}get referenceNode(){return this.config?.referenceNode}get contentNode(){return this.config?.contentNode}get contentWrapperNode(){return this.__contentWrapperNode||this.config?.contentWrapperNode}get backdropNode(){return this.__backdropNode||this.config?.backdropNode}get elementToFocusAfterHide(){return this.__elementToFocusAfterHide||this.config?.elementToFocusAfterHide}get hasBackdrop(){return!!this.backdropNode||this.config?.hasBackdrop}get isBlocking(){return this.config?.isBlocking}get preventsScroll(){return this.config?.preventsScroll}get trapsKeyboardFocus(){return this.config?.trapsKeyboardFocus}get hidesOnEsc(){return this.config?.hidesOnEsc}get hidesOnOutsideClick(){return this.config?.hidesOnOutsideClick}get hidesOnOutsideEsc(){return this.config?.hidesOnOutsideEsc}get inheritsReferenceWidth(){return this.config?.inheritsReferenceWidth}get handlesAccessibility(){return this.config?.handlesAccessibility}get isTooltip(){return this.config?.isTooltip}get isAlertDialog(){return this.config?.isAlertDialog}get invokerRelation(){return this.config?.invokerRelation}get popperConfig(){return this.config?.popperConfig}get viewportConfig(){return this.config?.viewportConfig}get visibilityTriggerFunction(){return this.config?.visibilityTriggerFunction}get _referenceNode(){return this.referenceNode||this.invokerNode}set elevation(t){this.__wrappingDialogNode.style.zIndex=`${this.config.zIndex+t}`}get elevation(){return Number(this.contentWrapperNode?.style.zIndex)}updateConfig(t){this.teardown(),this.__prevConfig=this.config,this.config={...this._defaultConfig,...this.__sharedConfig,...t,popperConfig:{...this._defaultConfig.popperConfig||{},...this.__sharedConfig.popperConfig||{},...t.popperConfig||{},modifiers:[...this._defaultConfig.popperConfig?.modifiers||[],...this.__sharedConfig.popperConfig?.modifiers||[],...t.popperConfig?.modifiers||[]]}},this.__validateConfiguration(this.config),this._init(),this.__elementToFocusAfterHide=void 0,this.#e()||this.manager.add(this)}#e(){return!!this.manager.list.find(t=>this===t)}__validateConfiguration(t){if(!t.placementMode)throw new Error('[OverlayController] You need to provide a .placementMode ("global"|"local")');if(!["global","local"].includes(t.placementMode))throw new Error(`[OverlayController] "${t.placementMode}" is not a valid .placementMode, use ("global"|"local")`);if(!t.contentNode)throw new Error("[OverlayController] You need to provide a .contentNode");if(t.isTooltip&&!t.handlesAccessibility)throw new Error("[OverlayController] .isTooltip only takes effect when .handlesAccessibility is enabled")}_init(){this.__contentHasBeenInitialized||(this.__initContentDomStructure(),this.__contentHasBeenInitialized=!0),this.contentWrapperNode.removeAttribute("style"),this.contentWrapperNode.removeAttribute("class"),this.placementMode==="local"&&(vi.popperModule||(vi.popperModule=fV())),this.__handleOverlayStyles({phase:"init"}),this._handleFeatures({phase:"init"})}__handleOverlayStyles({phase:t}){const r=this.contentWrapperNode?.getRootNode();t==="init"?ws.adoptStyle(r,bv):t==="teardown"&&ws.adoptStyle(r,bv,{teardown:!0})}__initContentDomStructure(){const t=document.createElement(this.config?._noDialogEl?"div":"dialog");t.setAttribute("role","none"),t.setAttribute("data-overlay-outer-wrapper",""),t.style.cssText=`display:none; z-index: ${this.config.zIndex}; padding: 0;`,this.__wrappingDialogNode=t,this.config?.contentWrapperNode||(this.__contentWrapperNode=document.createElement("div")),this.contentWrapperNode.setAttribute("data-id","content-wrapper"),hV({wrappingDialogNodeL1:t,contentWrapperNodeL2:this.contentWrapperNode,contentNodeL3:this.contentNode}),t.open=!0,this.isTooltip&&t.setAttribute("tabindex","-1"),this.__wrappingDialogNode.style.display="none",this.contentWrapperNode.style.zIndex="1",getComputedStyle(this.contentNode).position==="absolute"&&(this.contentNode.style.position="static"),HTMLDialogElement&&"closedBy"in HTMLDialogElement.prototype?t.closedBy="none":(t.addEventListener("keydown",n=>{n.key==="Escape"&&n.preventDefault()}),t.addEventListener("keyup",n=>{n.key==="Escape"&&n.preventDefault()}),t.addEventListener("cancel",n=>{n.stopPropagation()}),t.addEventListener("close",n=>{n.stopPropagation()}))}_handleZIndex({phase:t}){if(this.placementMode==="local"&&t==="setup"){const r=Number(getComputedStyle(this.contentNode).zIndex);(r<1||Number.isNaN(r))&&(this.contentNode.style.zIndex="1")}}__setupTeardownAccessibility({phase:t}){if(t==="init"){this.__storeOriginalAttrs(this.contentNode,["role","id"]);const r=this.trapsKeyboardFocus;if(this.invokerNode){const n=["aria-labelledby","aria-describedby"];r||n.push("aria-expanded"),this.__storeOriginalAttrs(this.invokerNode,n)}this.contentNode.id||this.contentNode.setAttribute("id",this._contentId),this.isTooltip?(this.invokerNode&&this.invokerNode.setAttribute(this.invokerRelation==="label"?"aria-labelledby":"aria-describedby",this._contentId),this.contentNode.setAttribute("role","tooltip")):(this.invokerNode&&!r&&this.invokerNode.setAttribute("aria-expanded",`${this.isShown}`),this.isAlertDialog?this.contentNode.setAttribute("role","alertdialog"):this.contentNode.getAttribute("role")||this.contentNode.setAttribute("role","dialog"))}else t==="teardown"&&this.__restoreOriginalAttrs()}__storeOriginalAttrs(t,r){const n={};r.forEach(i=>{n[i]=t.getAttribute(i)}),this.__originalAttrs.set(t,n)}__restoreOriginalAttrs(){for(const[t,r]of this.__originalAttrs)Object.entries(r).forEach(([n,i])=>{i!==null?t.setAttribute(n,i):t.removeAttribute(n)});this.__originalAttrs.clear()}get isShown(){return this.__wrappingDialogNode?.style.display!=="none"}async show(t=this.elementToFocusAfterHide){if(this._showComplete&&await this._showComplete,this._showComplete=new Promise(n=>{this._showResolve=n}),this.manager&&this.manager.show(this),this.isShown){this._showResolve();return}const r=new CustomEvent("before-show",{cancelable:!0});this.dispatchEvent(r),r.defaultPrevented||("HTMLDialogElement"in window&&this.__wrappingDialogNode instanceof HTMLDialogElement&&(this.__wrappingDialogNode.open=!0),this.__wrappingDialogNode.style.display="",this._keepBodySize({phase:"before-show"}),await this._handleFeatures({phase:"show"}),this._keepBodySize({phase:"show"}),await this._handlePosition({phase:"show"}),this.__elementToFocusAfterHide=t,this.dispatchEvent(new Event("show")),await this._transitionShow({backdropNode:this.backdropNode,contentNode:this.contentNode})),this._showResolve()}async _handlePosition({phase:t}){if(this.placementMode==="global"){const r=`overlays__overlay-container--${this.viewportConfig.placement}`;t==="show"?(this.contentWrapperNode.classList.add("overlays__overlay-container"),this.contentWrapperNode.classList.add(r),this.contentNode.classList.add("overlays__overlay")):t==="hide"&&(this.contentWrapperNode.classList.remove("overlays__overlay-container"),this.contentWrapperNode.classList.remove(r),this.contentNode.classList.remove("overlays__overlay"))}else this.placementMode==="local"&&t==="show"&&(await this.__createPopperInstance(),this._popper.forceUpdate())}_keepBodySize({phase:t}){if(this.preventsScroll)switch(t){case"before-show":this.__bodyClientWidth=document.body.clientWidth,this.__bodyClientHeight=document.body.clientHeight,this.__bodyMarginRightInline=document.body.style.marginRight,this.__bodyMarginBottomInline=document.body.style.marginBottom;break;case"show":{if(window.getComputedStyle){const o=window.getComputedStyle(document.body);this.__bodyMarginRight=parseInt(o.getPropertyValue("margin-right"),10),this.__bodyMarginBottom=parseInt(o.getPropertyValue("margin-bottom"),10)}else this.__bodyMarginRight=0,this.__bodyMarginBottom=0;const r=document.body.clientWidth-this.__bodyClientWidth,n=document.body.clientHeight-this.__bodyClientHeight,i=this.__bodyMarginRight+r,s=this.__bodyMarginBottom+n;window.CSS?.number&&document.body.attributeStyleMap?.set?(document.body.attributeStyleMap.set("margin-right",CSS.px(i)),document.body.attributeStyleMap.set("margin-bottom",CSS.px(s))):(document.body.style.marginRight=`${i}px`,document.body.style.marginBottom=`${s}px`);break}case"hide":document.body.style.marginRight=this.__bodyMarginRightInline||"",document.body.style.marginBottom=this.__bodyMarginBottomInline||"";break}}async hide(){if(this._hideComplete=new Promise(r=>{this._hideResolve=r}),this.__activeElementRightBeforeHide=this.contentNode.getRootNode().activeElement,this.manager&&this.manager.hide(this),!this.isShown){this._hideResolve();return}const t=new CustomEvent("before-hide",{cancelable:!0});this.dispatchEvent(t),t.defaultPrevented||(await this._transitionHide({backdropNode:this.backdropNode,contentNode:this.contentNode}),"HTMLDialogElement"in window&&this.__wrappingDialogNode instanceof HTMLDialogElement&&this.__wrappingDialogNode.close(),this.__wrappingDialogNode.style.display="none",this._handleFeatures({phase:"hide"}),this._keepBodySize({phase:"hide"}),this.dispatchEvent(new Event("hide")),this._restoreFocus()),this._hideResolve()}async transitionHide(t){}async _transitionHide({backdropNode:t,contentNode:r}){await this.transitionHide({backdropNode:t,contentNode:r}),this._handlePosition({phase:"hide"}),t&&t.classList.remove("overlays__backdrop--animation-in")}async transitionShow(t){}async _transitionShow(t){await this.transitionShow({backdropNode:this.backdropNode,contentNode:this.contentNode}),t.backdropNode&&t.backdropNode.classList.add("overlays__backdrop--animation-in")}_restoreFocus(){this.__activeElementRightBeforeHide instanceof HTMLElement&&this.contentNode.contains(this.__activeElementRightBeforeHide)&&(this.elementToFocusAfterHide instanceof HTMLElement?(this.elementToFocusAfterHide.focus(),this.elementToFocusAfterHide.scrollIntoView({block:"nearest"})):this.__activeElementRightBeforeHide.blur())}async toggle(){return this.isShown?this.hide():this.show()}_handleFeatures({phase:t}){this._handleZIndex({phase:t}),this.preventsScroll&&this._handlePreventsScroll({phase:t}),this.isBlocking&&this._handleBlocking({phase:t}),this.hasBackdrop&&this._handleBackdrop({phase:t}),this.trapsKeyboardFocus&&this._handleTrapsKeyboardFocus({phase:t}),this.hidesOnEsc&&this._handleHidesOnEsc({phase:t}),this.hidesOnOutsideEsc&&this._handleHidesOnOutsideEsc({phase:t}),this.hidesOnOutsideClick&&this._handleHidesOnOutsideClick({phase:t}),this.handlesAccessibility&&this._handleAccessibility({phase:t}),this.inheritsReferenceWidth&&this._handleInheritsReferenceWidth(),this.visibilityTriggerFunction&&this._handleVisibilityTriggers({phase:t})}_handleVisibilityTriggers({phase:t}){typeof this.visibilityTriggerFunction=="function"&&(t==="init"&&(this.__visibilityTriggerHandler=this.visibilityTriggerFunction({phase:t,controller:this})),this.__visibilityTriggerHandler[t]&&this.__visibilityTriggerHandler[t]())}_handlePreventsScroll({phase:t}){switch(t){case"show":this.manager.requestToPreventScroll();break;case"hide":this.manager.requestToEnableScroll();break}}_handleBlocking({phase:t}){switch(t){case"show":this.manager.requestToShowOnly(this);break;case"hide":this.manager.retractRequestToShowOnly(this);break}}get hasActiveBackdrop(){return this.__hasActiveBackdrop}_handleBackdrop({phase:t}){switch(t){case"init":{this.__backdropInitialized||(this.config?.backdropNode||(this.__backdropNode=document.createElement("div"),this.__backdropNode.classList.add("overlays__backdrop")),this.__wrappingDialogNode.prepend(this.backdropNode),this.__backdropInitialized=!0);break}case"show":this.config.hasBackdrop&&this.backdropNode.classList.add("overlays__backdrop--visible"),this.__hasActiveBackdrop=!0;break;case"hide":case"teardown":this.backdropNode.classList.remove("overlays__backdrop--visible"),this.__hasActiveBackdrop=!1;break}}get hasActiveTrapsKeyboardFocus(){return this.__hasActiveTrapsKeyboardFocus}_handleTrapsKeyboardFocus({phase:t}){t==="show"?("showModal"in this.__wrappingDialogNode&&(this.__wrappingDialogNode.close(),this.__wrappingDialogNode.showModal()),this.enableTrapsKeyboardFocus()):(t==="hide"||t==="teardown")&&this.disableTrapsKeyboardFocus()}enableTrapsKeyboardFocus(){if(this.__hasActiveTrapsKeyboardFocus)return;this.manager&&this.manager.disableTrapsKeyboardFocusForAll(),!!this.contentNode.shadowRoot&&console.warn("[overlays]: For best accessibility (compatibility with Safari + VoiceOver), provide a contentNode that is not a host for a shadow root"),this._containFocusHandler=oV(this.contentNode),this.__hasActiveTrapsKeyboardFocus=!0,this.manager&&this.manager.informTrapsKeyboardFocusGotEnabled(this.placementMode)}disableTrapsKeyboardFocus({findNewTrap:t=!0}={}){this.__hasActiveTrapsKeyboardFocus&&(this._containFocusHandler&&(this._containFocusHandler.disconnect(),this._containFocusHandler=void 0),this.__hasActiveTrapsKeyboardFocus=!1,this.manager&&this.manager.informTrapsKeyboardFocusGotDisabled({disabledCtrl:this,findNewTrap:t}))}__cancelHandler(t){t.preventDefault()}__escKeyHandler(t){if(t.key!=="Escape"||vv.has(t))return;(t.composedPath().includes(this.contentNode)||_s(this.contentNode,t.target))&&(this.hide(),vv.set(t,this))}#t=t=>{t.key!=="Escape"||t.composedPath().includes(this.contentNode)||_s(this.contentNode,t.target)||this.hide()};_handleHidesOnEsc({phase:t}){t==="show"?(this.contentNode.addEventListener("keyup",this.__escKeyHandler),this.invokerNode&&this.invokerNode.addEventListener("keyup",this.__escKeyHandler)):(t==="hide"||t==="teardown")&&(this.contentNode.removeEventListener("keyup",this.__escKeyHandler),this.invokerNode&&this.invokerNode.removeEventListener("keyup",this.__escKeyHandler))}_handleHidesOnOutsideEsc({phase:t}){t==="show"?document.addEventListener("keyup",this.#t):(t==="hide"||t==="teardown")&&document.removeEventListener("keyup",this.#t)}_handleInheritsReferenceWidth(){if(!this._referenceNode||this.placementMode==="global")return;const t=`${this._referenceNode.getBoundingClientRect().width}px`;switch(this.inheritsReferenceWidth){case"max":this.contentWrapperNode.style.maxWidth=t;break;case"full":this.contentWrapperNode.style.width=t;break;case"min":this.contentWrapperNode.style.minWidth=t,this.contentWrapperNode.style.width="auto";break}}_handleHidesOnOutsideClick({phase:t}){const r=t==="show"?"addEventListener":"removeEventListener";if(t==="show"){let n=!1,i=!1;this.__onInsideMouseDown=()=>{n=!0},this.__onInsideMouseUp=()=>{i=!0},this.__onDocumentMouseUp=()=>{setTimeout(()=>{!n&&!i&&this.hide(),n=!1,i=!1})},this.__onWindowBlur=()=>{setTimeout(()=>{this.hide()})}}this.contentWrapperNode[r]("mousedown",this.__onInsideMouseDown,!0),this.contentWrapperNode[r]("mouseup",this.__onInsideMouseUp,!0),this.invokerNode&&(this.invokerNode[r]("mousedown",this.__onInsideMouseDown,!0),this.invokerNode[r]("mouseup",this.__onInsideMouseUp,!0)),document.documentElement[r]("mouseup",this.__onDocumentMouseUp,!0),window[r]("blur",this.__onWindowBlur)}_handleAccessibility({phase:t}){(t==="init"||t==="teardown")&&this.__setupTeardownAccessibility({phase:t});const r=this.trapsKeyboardFocus;this.invokerNode&&!this.isTooltip&&!r&&this.invokerNode.setAttribute("aria-expanded",`${t==="show"}`)}teardown(){this.__handleOverlayStyles({phase:"teardown"}),this._handleFeatures({phase:"teardown"}),this.#e()&&this.manager.remove(this)}async __createPopperInstance(){if(this._popper&&(this._popper.destroy(),this._popper=void 0),vi.popperModule!==void 0){const{createPopper:t}=await vi.popperModule;this._popper=t(this._referenceNode,this.contentWrapperNode,{...this.config?.popperConfig})}}_hasDisabledInvoker(){return this.invokerNode?this.invokerNode.disabled||this.invokerNode.getAttribute("aria-disabled")==="true":!1}}vi.popperModule=void 0;function $E(e,t){if(typeof e!="object"||typeof t!="object"||e===null||t===null)return e===t;const r=Object.keys(e),n=Object.keys(t);if(r.length!==n.length)return!1;const i=s=>$E(e[s],t[s]);return r.every(i)}const pV=e=>class extends e{static get properties(){return{opened:{type:Boolean,reflect:!0}}}#e=!1;constructor(){super(),this.opened=!1,this.config={},this.toggle=this.toggle.bind(this),this.open=this.open.bind(this),this.close=this.close.bind(this)}get config(){return this.__config}set config(r){const n=!$E(this.config,r);this._overlayCtrl&&n&&this._overlayCtrl.updateConfig(r),this.__config=r,this._overlayCtrl&&n&&this.__syncToOverlayController()}requestUpdate(r,n,i){super.requestUpdate(r,n,i),r==="opened"&&this.opened!==n&&this.dispatchEvent(new CustomEvent("opened-changed",{detail:{opened:this.opened}}))}_defineOverlay({contentNode:r,invokerNode:n,referenceNode:i,backdropNode:s,contentWrapperNode:o}){const a=this._defineOverlayConfig()||{};return new vi({contentNode:r,invokerNode:n,referenceNode:i,backdropNode:s,contentWrapperNode:o,...a,...this.config,popperConfig:{...a.popperConfig||{},...this.config?.popperConfig||{},modifiers:[...a.popperConfig?.modifiers||[],...this.config?.popperConfig?.modifiers||[]]}})}_defineOverlayConfig(){return{placementMode:"local"}}updated(r){super.updated(r),r.has("opened")&&this._overlayCtrl&&!this.__blockSyncToOverlayCtrl&&this.__syncToOverlayController()}_setupOpenCloseListeners(){this.__closeEventInContentNodeHandler=r=>{r.stopPropagation(),this._overlayCtrl.hide()},this._overlayContentNode&&this._overlayContentNode.addEventListener("close-overlay",this.__closeEventInContentNodeHandler)}_teardownOpenCloseListeners(){this._overlayContentNode&&this._overlayContentNode.removeEventListener("close-overlay",this.__closeEventInContentNodeHandler)}connectedCallback(){super.connectedCallback(),this.updateComplete.then(()=>{this.isConnected&&(this.#e||(this._setupOverlayCtrl(),this.#e=!0))})}async disconnectedCallback(){super.disconnectedCallback(),await this._isPermanentlyDisconnected()&&(this._teardownOverlayCtrl(),this.#e=!1)}static enabledWarnings=super.enabledWarnings?.filter(r=>r!=="change-in-update")||[];get _overlayInvokerNode(){return Array.from(this.children).find(r=>r.slot==="invoker")}get _overlayReferenceNode(){}get _overlayBackdropNode(){return this.__cachedOverlayBackdropNode||(this.__cachedOverlayBackdropNode=Array.from(this.children).find(r=>r.slot==="backdrop")),this.__cachedOverlayBackdropNode}get _overlayContentNode(){return this._cachedOverlayContentNode||(this._cachedOverlayContentNode=Array.from(this.children).find(r=>r.slot==="content")||this.config.contentNode),this._cachedOverlayContentNode}get _overlayContentWrapperNode(){return this.shadowRoot?.querySelector("#overlay-content-node-wrapper")}_setupOverlayCtrl(){if(this.#e)return;const r={contentNode:this._overlayContentNode,contentWrapperNode:this._overlayContentWrapperNode,invokerNode:this._overlayInvokerNode,referenceNode:this._overlayReferenceNode,backdropNode:this._overlayBackdropNode};this._overlayCtrl?this._overlayCtrl.updateConfig(r):this._overlayCtrl=this._defineOverlay(r),this.__syncToOverlayController(),this.__setupSyncFromOverlayController(),this._setupOpenCloseListeners()}_teardownOverlayCtrl(){this._overlayCtrl&&(this._teardownOpenCloseListeners(),this.__teardownSyncFromOverlayController(),this._overlayCtrl.teardown())}async _setOpenedWithoutPropertyEffects(r){this.__blockSyncToOverlayCtrl=!0,this.opened=r,await this.updateComplete,this.__blockSyncToOverlayCtrl=!1}__setupSyncFromOverlayController(){this.__onOverlayCtrlShow=()=>{this.opened=!0},this.__onOverlayCtrlHide=()=>{this.opened=!1},this.__onBeforeShow=r=>{const n=new CustomEvent("before-opened",{cancelable:!0});this.dispatchEvent(n),n.defaultPrevented&&(this._setOpenedWithoutPropertyEffects(this._overlayCtrl.isShown),r.preventDefault())},this.__onBeforeHide=r=>{const n=new CustomEvent("before-closed",{cancelable:!0});this.dispatchEvent(n),n.defaultPrevented&&(this._setOpenedWithoutPropertyEffects(this._overlayCtrl.isShown),r.preventDefault())},this._overlayCtrl.addEventListener("show",this.__onOverlayCtrlShow),this._overlayCtrl.addEventListener("hide",this.__onOverlayCtrlHide),this._overlayCtrl.addEventListener("before-show",this.__onBeforeShow),this._overlayCtrl.addEventListener("before-hide",this.__onBeforeHide)}__teardownSyncFromOverlayController(){this._overlayCtrl.removeEventListener("show",this.__onOverlayCtrlShow),this._overlayCtrl.removeEventListener("hide",this.__onOverlayCtrlHide),this._overlayCtrl.removeEventListener("before-show",this.__onBeforeShow),this._overlayCtrl.removeEventListener("before-hide",this.__onBeforeHide)}__syncToOverlayController(){this.opened?this._overlayCtrl.show():this._overlayCtrl.hide()}async toggle(){await this._overlayCtrl.toggle()}async open(){await this._overlayCtrl.show()}async close(){await this._overlayCtrl.hide()}repositionOverlay(){const r=this._overlayCtrl;r.placementMode==="local"&&r._popper&&r._popper.update()}async _isPermanentlyDisconnected(){return await this.updateComplete,!this.isConnected}},VE=He(pV);function mV(){return{visibilityTriggerFunction:({controller:e})=>{function t(){e._hasDisabledInvoker()||e.toggle()}return{init:()=>{e.invokerNode?.addEventListener("click",t)},teardown:()=>{e.invokerNode?.removeEventListener("click",t)}}}}}const BE=()=>({placementMode:"local",inheritsReferenceWidth:"min",hidesOnOutsideClick:!0,hidesOnEsc:!0,popperConfig:{placement:"bottom-start",modifiers:[{name:"offset",enabled:!1}]},handlesAccessibility:!0,...mV()});var va=class extends VE(Ne){_defineOverlayConfig(){return{...BE()}}_addEventListeners(){this.actionItems.forEach(e=>{e.addEventListener("click",t=>{t.target?.dispatchEvent(new Event("close-overlay",{bubbles:!0}))})})}_setupInvoker(){const e=this.invokerNodes[0];e&&(e.setAttribute("id",`invoker-${this.uid}`),e.setAttribute("aria-controls",`content-${this.uid}`))}_setupContent(){const e=this.contentNodes[0];e&&(e.setAttribute("id",`content-${this.uid}`),e.setAttribute("role","none"))}_setupOverlayCtrl(){super._setupOverlayCtrl(),this._setupInvoker(),this._setupContent()}firstUpdated(){this.uid=fa(),this._addEventListeners()}render(){return W`
      <slot name="invoker"></slot>
      <slot name="backdrop"></slot>
      <slot name="content"></slot>
    `}};va.styles=te`
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
  `;se([Mc({selector:"craft-action-item"})],va.prototype,"actionItems",void 0);se([Mc({slot:"invoker"})],va.prototype,"invokerNodes",void 0);se([Mc({slot:"content"})],va.prototype,"contentNodes",void 0);customElements.get("craft-action-menu")||customElements.define("craft-action-menu",va);const eo=new WeakMap;function UE(e,t){Array.from(e.childNodes).forEach(r=>{if(r.nodeName==="#text"){const n=new RegExp(`^(.*?)(${t})(.*)$`,"i"),i=r.nodeValue.match(n);if(i){const s=document.createTextNode(i[1]);e.appendChild(s);const o=document.createElement("b");o.textContent=i[2],e.appendChild(o);const a=document.createTextNode(i[3]);e.appendChild(a),e.removeChild(r),eo.set(e,()=>{e.appendChild(r),e.contains(s)&&s.parentNode!==null&&s.parentNode.removeChild(s),e.contains(o)&&o.parentNode!==null&&o.parentNode.removeChild(o),e.contains(a)&&a.parentNode!==null&&a.parentNode.removeChild(a)})}}else UE(r,t)})}function zE(e){eo.has(e)&&eo.get(e)(),Array.from(e.childNodes).forEach(t=>{t.nodeName==="#text"?eo.has(t)&&eo.get(t)():zE(t)})}class gV extends Kc{static get validatorName(){return"MatchesOption"}execute(t,r,n){return n?.node.modelValue instanceof Li}}function qa(e){return Array.isArray(e)?e:[e]}const bV=e=>class extends Gc(e){static get properties(){return{allowCustomChoice:{type:Boolean,attribute:"allow-custom-choice"},modelValue:{type:Object}}}get modelValue(){return this.__getChoicesFrom(super.modelValue)}set modelValue(r){if(super.modelValue=r,r==null||r==="")this._customChoices=new Set;else if(this.allowCustomChoice){const n=this.modelValue;this._customChoices=new Set(qa(r)),this.requestUpdate("modelValue",n)}}get formattedValue(){return this.__getChoicesFrom(super.formattedValue)}set formattedValue(r){if(super.formattedValue=r,r==null)this._customChoices=new Set;else if(this.allowCustomChoice){const n=this.modelValue;this._customChoices=new Set(qa(r).map(i=>this.formElements.find(s=>s.formattedValue===i)?.modelValue||i)),this.requestUpdate("modelValue",n)}}get serializedValue(){return this.__getChoicesFrom(super.serializedValue)}set serializedValue(r){if(super.serializedValue=r,r==null)this._customChoices=new Set;else if(this.allowCustomChoice){const n=this.modelValue;this._customChoices=new Set(qa(r).map(i=>this.formElements.find(s=>s.serializedValue===i)?.modelValue||i)),this.requestUpdate("modelValue",n)}}get customChoices(){if(!this.allowCustomChoice)return[];const r=this._getCheckedElements();return Array.from(this._customChoices).filter(n=>!r.some(i=>i.choiceValue===n))}constructor(){super(),this.allowCustomChoice=!1,this._customChoices=new Set}__getChoicesFrom(r){const n=r;return this.allowCustomChoice?this.multipleChoice?[...qa(n),...this.customChoices]:n===""?this._customChoices.values().next().value||"":n:n}_isEmpty(){return super._isEmpty()&&this._customChoices.size===0}clear(){this._customChoices=new Set,super.clear()}parser(r){return this.allowCustomChoice&&Array.isArray(r)?r.filter(n=>n.trim()!==""):r}},vV=He(bV),jd=new WeakMap;class yV extends Wc(VE(vV(i$))){static get properties(){return{autocomplete:{type:String,reflect:!0},matchMode:{type:String,attribute:"match-mode"},showAllOnEmpty:{type:Boolean,attribute:"show-all-on-empty"},requireOptionMatch:{type:Boolean},allowCustomChoice:{type:Boolean,attribute:"allow-custom-choice"},__shouldAutocompleteNextUpdate:Boolean}}static get styles(){return[...super.styles,te`
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
      `]}static get localizeNamespaces(){return[{"lion-combobox":t=>{switch(t){case"bg-BG":case"bg":return Y(()=>import("./bg.js"),[],import.meta.url);case"cs-CZ":case"cs":return Y(()=>import("./cs.js"),[],import.meta.url);case"de-AT":case"de-DE":case"de":return Y(()=>import("./de.js"),[],import.meta.url);case"en-AU":case"en-GB":case"en-PH":case"en-US":case"en":return Y(()=>import("./en.js"),[],import.meta.url);case"es-ES":case"es":return Y(()=>import("./es.js"),[],import.meta.url);case"fr-FR":case"fr-BE":case"fr":return Y(()=>import("./fr.js"),[],import.meta.url);case"hu-HU":case"hu":return Y(()=>import("./hu.js"),[],import.meta.url);case"it-IT":case"it":return Y(()=>import("./it.js"),[],import.meta.url);case"nl-BE":case"nl-NL":case"nl":return Y(()=>import("./nl.js"),[],import.meta.url);case"pl-PL":case"pl":return Y(()=>import("./pl.js"),[],import.meta.url);case"ro-RO":case"ro":return Y(()=>import("./ro.js"),[],import.meta.url);case"ru-RU":case"ru":return Y(()=>import("./ru.js"),[],import.meta.url);case"sk-SK":case"sk":return Y(()=>import("./sk.js"),[],import.meta.url);case"uk-UA":case"uk":return Y(()=>import("./uk.js"),[],import.meta.url);case"zh-CN":case"zh":return Y(()=>import("./zh.js"),[],import.meta.url);default:return Y(()=>import("./en.js"),[],import.meta.url)}}},...super.localizeNamespaces]}get modelValue(){const t=super.modelValue;return t!==""?t:this.parser(this.value)}set modelValue(t){super.modelValue=t}get value(){return this._inputNode?.value||this.__value||""}set value(t){this._inputNode?(this._inputNode.value=t,this.__value=void 0):this.__value=t}reset(){super.reset(),this.multipleChoice||(this.value=this._initialModelValue),this._resetListboxOptions()}_resetListboxOptions(){this.formElements.forEach((t,r)=>{this._unhighlightMatchedOption(t),!this.showAllOnEmpty||!this.opened?t.style.display="none":(t.style.display="",t.setAttribute("aria-posinset",`${r+1}`),t.setAttribute("aria-setsize",`${this.formElements.length}`),t.removeAttribute("aria-hidden"))})}_inputGroupInputTemplate(){return W`
      <div class="input-group__input">
        <slot name="selection-display"></slot>
        <slot name="input"></slot>
      </div>
    `}_overlayListboxTemplate(){return W`
      <div
        id="overlay-content-node-wrapper"
        role="dialog"
        aria-label="${this.msgLit("lion-combobox:optionsPopup")}"
      >
        <slot name="listbox"></slot>
      </div>
      <slot id="options-outlet"></slot>
    `}_groupTwoTemplate(){return W` ${super._groupTwoTemplate()} ${this._overlayListboxTemplate()}`}get slots(){return{...super.slots,input:()=>{if(this._ariaVersion==="1.1"){const t=document.createElement("div"),r=document.createElement("input");return r.style.cssText=`
          border: none;
          outline: none;
          width: 100%;
          height: 100%;
          font: inherit;
          background: inherit;
          color: inherit;
          border-radius: inherit;
          box-sizing: border-box;
          padding: 0;`,t.appendChild(r),t}return document.createElement("input")},listbox:super.slots.input}}get _comboboxNode(){return this.querySelector('[slot="input"]')}get _selectionDisplayNode(){return this.querySelector('[slot="selection-display"]')}get _inputNode(){return this._ariaVersion==="1.1"&&this._comboboxNode?this._comboboxNode.querySelector("input")||this._comboboxNode:this._comboboxNode}get _overlayContentNode(){return this._listboxNode}get _overlayReferenceNode(){return this.shadowRoot.querySelector(".input-group__container")}get _overlayInvokerNode(){return this._inputNode}get _listboxNode(){return this._overlayCtrl&&this._overlayCtrl.contentNode||Array.from(this.children).find(t=>t.slot==="listbox")}get _activeDescendantOwnerNode(){return this._inputNode}get requireOptionMatch(){return!this.allowCustomChoice}set requireOptionMatch(t){this.allowCustomChoice=!t}constructor(){super(),this.autocomplete="both",this.matchMode="all",this.showAllOnEmpty=!1,this.requireOptionMatch=!0,this.rotateKeyboardNavigation=!0,this.selectionFollowsFocus=!0,this.defaultValidators.push(new gV),this._ariaVersion=Yl.isChromium?"1.1":"1.0",this._listboxReceivesNoFocus=!0,this._noTypeAhead=!0,this.__prevCboxValueNonSelected="",this.__prevCboxValue="",this.__hadUserIntendsInlineAutoFill=!1,this.__listboxContentChanged=!1,this._onKeyUp=this._onKeyUp.bind(this),this._textboxOnClick=this._textboxOnClick.bind(this),this._textboxOnInput=this._textboxOnInput.bind(this),this._textboxOnKeydown=this._textboxOnKeydown.bind(this)}connectedCallback(){super.connectedCallback(),this._selectionDisplayNode&&(this._selectionDisplayNode.comboboxElement=this),(this.disabled||this.readOnly)&&this.__setComboboxDisabledAndReadOnly()}requestUpdate(t,r,n){if(super.requestUpdate(t,r,n),(t==="disabled"||t==="readOnly")&&this.__setComboboxDisabledAndReadOnly(),t==="modelValue"&&this.modelValue&&this.modelValue!==r&&this._syncToTextboxCondition(this.modelValue,this._oldModelValue))if(this.multipleChoice)this._syncToTextboxMultiple(this.modelValue,this._oldModelValue);else{const i=this._getTextboxValueFromOption(this.formElements[this.checkedIndex]);this._setTextboxValue(i)}}parser(t){return this.requireOptionMatch&&this.checkedIndex===-1&&t!==""&&!Array.isArray(t)?new Li(t):super.parser(t)}__unsyncCheckedIndexOnInputChange(){const t=this._autoSelectCondition(),r=this.formElements[this.checkedIndex];if(!this.multipleChoice&&!t&&r){const n=this._getTextboxValueFromOption(r);this._inputNode.value.startsWith(n)||this.setCheckedIndex(-1)}}updated(t){super.updated(t),t.has("__shouldAutocompleteNextUpdate")&&this.__unsyncCheckedIndexOnInputChange(),t.has("opened")&&(this.opened&&(this.activeIndex=-1),!this.opened&&t.get("opened")!==void 0&&(this.__onOverlayClose(),this.activeIndex=-1)),t.has("autocomplete")&&this._inputNode.setAttribute("aria-autocomplete",this.autocomplete),t.has("disabled")&&this.setAttribute("aria-disabled",`${this.disabled}`),t.has("__shouldAutocompleteNextUpdate")&&this.__shouldAutocompleteNextUpdate&&(this._handleAutocompletion(),this.__shouldAutocompleteNextUpdate=!1,this.__listboxContentChanged=!1),typeof this._selectionDisplayNode?.onComboboxElementUpdated=="function"&&this._selectionDisplayNode.onComboboxElementUpdated(t)}matchCondition(t,r){let n=-1;const i=this._getTextboxValueFromOption(t);return typeof i=="string"&&typeof r=="string"&&(n=i.toLowerCase().indexOf(r.toLowerCase())),this.matchMode==="all"?n>-1:n===0}_showOverlayCondition({lastKey:t}){const r=["Tab","Escape"],n=["Enter"];return this.disabled||this.readOnly||t&&(r.includes(t)||!this.multipleChoice&&n.includes(t))?!1:this.filled||this.showAllOnEmpty||!this.filled&&this.multipleChoice&&this.__prevCboxValueNonSelected?!0:this.opened}_getTextboxValueFromOption(t){return t?t.choiceValue:this.modelValue instanceof Li?this.modelValue.viewValue:this.modelValue}_onListboxContentChanged(){super._onListboxContentChanged(),this.__shouldAutocompleteNextUpdate=!0,this.__listboxContentChanged=!0}_textboxOnInput(t){this.__shouldAutocompleteNextUpdate=!0,this.opened=this._showOverlayCondition({})}_textboxOnKeydown(t){t.key==="Tab"&&(this.opened=!1)}_listboxOnClick(t){super._listboxOnClick(t),this._inputNode.focus(),this.multipleChoice?(this._inputNode.value="",this._resetListboxOptions()):(this.activeIndex=-1,this.opened=!1)}_setTextboxValue(t){this._inputNode&&this._inputNode.value!==t&&(this._inputNode.value=t)}__onOverlayClose(){this.multipleChoice?this._syncToTextboxMultiple(this.modelValue,this._oldModelValue):this.checkedIndex!==-1&&this._syncToTextboxCondition(this.modelValue,this._oldModelValue,{phase:"overlay-close"})&&(this._inputNode.value=this._getTextboxValueFromOption(this.formElements[this.checkedIndex]))}_repropagationCondition(t){return super._repropagationCondition(t)||this.formElements.every(r=>!r.checked)}_onFilterMatch(t,r){this._highlightMatchedOption(t,r),t.style.display=""}_highlightMatchedOption(t,r){if(UE(t,r),t.textContent){const n=document.createElement("span");n.setAttribute("aria-label",t.textContent.replace(/\s+/g," ")),Array.from(t.childNodes).forEach(i=>{n.appendChild(i)}),t.appendChild(n),jd.set(t,()=>{Array.from(n.childNodes).forEach(i=>{t.appendChild(i)}),t.contains(n)&&t.removeChild(n)})}}_onFilterUnmatch(t,r,n){this._unhighlightMatchedOption(t),t.style.display="none"}_unhighlightMatchedOption(t){zE(t),jd.has(t)&&jd.get(t)()}__computeUserIntendsAutoFill({prevValue:t,curValue:r}){const n=t.length<r.length,i=t.length&&r.length&&t[0].toLowerCase()!==r[0].toLowerCase();return n||i||this.__listboxContentChanged&&this.__hadUserIntendsInlineAutoFill}_handleAutocompletion(){const r=!(this._inputNode.selectionStart===this._inputNode.selectionEnd)&&this._inputNode.value.length!==this._inputNode.selectionStart,n=this._inputNode.value,i=this._inputNode.selectionStart,s=r&&i?n.slice(0,i):n,o=r||this.__hadSelectionLastAutofill?this.__prevCboxValueNonSelected:this.__prevCboxValue,a=!s,l=[];let u=!1;const c=this.__computeUserIntendsAutoFill({prevValue:o,curValue:s}),d=this.autocomplete==="both"||this.autocomplete==="inline",p=this._autoSelectCondition(),m=this.autocomplete==="inline"||this.autocomplete==="none";this.formElements.forEach((f,b)=>{const y=this.matchCondition(f,s);let w=!1;if(a?w=this.showAllOnEmpty:w=m||y,p&&!u&&y&&!f.disabled){const v=()=>{this.activeIndex=b,this.selectionFollowsFocus&&!this.multipleChoice&&this.setCheckedIndex(this.activeIndex),u=!0};if(c)if(d){const _=this._getTextboxValueFromOption(f);_&&typeof _=="string"&&typeof s=="string"&&_.toLowerCase().indexOf(s.toLowerCase())===0&&(this.__textboxInlineComplete(f),v())}else v()}f.onFilterUnmatch?f.onFilterUnmatch(s,o):this._onFilterUnmatch(f,s,o),f.setAttribute("aria-hidden","true"),f.removeAttribute("aria-posinset"),f.removeAttribute("aria-setsize"),w&&(l.push(f),f.onFilterMatch?f.onFilterMatch(s):this._onFilterMatch(f,s))});const h=l.length;l.forEach((f,b)=>{f.setAttribute("aria-posinset",`${b+1}`),f.setAttribute("aria-setsize",`${h}`),f.removeAttribute("aria-hidden")}),p&&!u&&!this.multipleChoice&&(this.setCheckedIndex(-1),o!==s&&(this.activeIndex=-1),this.modelValue=this.parser(n)),this.__prevCboxValueNonSelected=s,this.__prevCboxValue=this._inputNode.value,this.__hadSelectionLastAutofill=this._inputNode.value.length!==this._inputNode.selectionStart,this.__hadUserIntendsInlineAutoFill=c,this._overlayCtrl&&this._overlayCtrl._popper&&this._overlayCtrl._popper.update()}__textboxInlineComplete(t=this.formElements[this.activeIndex]){const r=this._getTextboxValueFromOption(t);if(this._inputNode.value!==r){const n=this._inputNode.value.length;this._inputNode.value=r,this._inputNode.selectionStart=n,this._inputNode.selectionEnd=this._inputNode.value.length}}_autoSelectCondition(){return this.autocomplete==="both"||this.autocomplete==="inline"}_setupListboxNode(){super._setupListboxNode(),this._listboxNode.removeAttribute("tabindex")}_defineOverlayConfig(){return{...BE(),elementToFocusAfterHide:void 0,invokerNode:this._comboboxNode,visibilityTriggerFunction:void 0}}_setupOverlayCtrl(){super._setupOverlayCtrl(),this.__shouldAutocompleteNextUpdate=!0,this.__setupCombobox()}_teardownOverlayCtrl(){super._teardownOverlayCtrl(),this.__teardownCombobox()}_setupOpenCloseListeners(){super._setupOpenCloseListeners(),this._inputNode.addEventListener("keyup",this._onKeyUp),this._inputNode.addEventListener("click",this._textboxOnClick)}_teardownOpenCloseListeners(){super._teardownOpenCloseListeners(),this._inputNode.removeEventListener("keyup",this._onKeyUp),this._inputNode.removeEventListener("click",this._textboxOnClick)}_listboxOnKeyDown(t){const{key:r}=t;switch(r){case"Escape":this.opened=!1,super._listboxOnKeyDown(t),this._setTextboxValue("");break;case"Backspace":case"Delete":this.requireOptionMatch?super._listboxOnKeyDown(t):this.opened=!1;break;case"Enter":this.opened&&t.preventDefault(),!this.requireOptionMatch&&this.multipleChoice&&(!this.formElements[this.activeIndex]||this.formElements[this.activeIndex].hasAttribute("aria-hidden")||!this.opened)?(this.modelValue=this.parser([...this.modelValue,this._inputNode.value]),this._inputNode.value="",this.opened=!1):(super._listboxOnKeyDown(t),this._resetListboxOptions()),this.multipleChoice?this._inputNode.value="":this.opened=!1;break;default:{super._listboxOnKeyDown(t);break}}}_syncToTextboxCondition(t,r,{phase:n}={}){return this.autocomplete==="both"||this.autocomplete==="inline"||!this.focused}_syncToTextboxMultiple(t,r=[]){if(this.requireOptionMatch){const n=t.filter(s=>!r.includes(s)),i=this.formElements.filter(s=>n.includes(s.choiceValue)).map(s=>this._getTextboxValueFromOption(s)).join(" ");this._setTextboxValue(i)}}_enhanceLightDomClasses(){const t=this.querySelector("[slot=input]");t&&t.classList.add("form-control")}__setComboboxDisabledAndReadOnly(){this._comboboxNode&&(this._comboboxNode.toggleAttribute("disabled",this.disabled),this._comboboxNode.setAttribute("aria-disabled",`${this.disabled}`),this._comboboxNode.toggleAttribute("readonly",this.readOnly),this._comboboxNode.setAttribute("aria-readonly",`${this.readOnly}`)),this._inputNode&&(this._inputNode.toggleAttribute("disabled",this.disabled),this._inputNode.toggleAttribute("readOnly",this.readOnly),this._inputNode.setAttribute("aria-readonly",`${this.readOnly}`),this._inputNode.tabIndex=this.disabled?-1:0)}__setupCombobox(){this._comboboxNode.setAttribute("role","combobox"),this._comboboxNode.setAttribute("aria-haspopup","listbox"),this._inputNode.setAttribute("aria-autocomplete",this.autocomplete),this._comboboxNode.setAttribute("aria-controls",this._listboxNode.id),this._ariaVersion==="1.1"?this._comboboxNode.setAttribute("aria-owns",this._listboxNode.id):this._inputNode.setAttribute("aria-owns",this._listboxNode.id),this._listboxNode.setAttribute("aria-labelledby",this._labelNode.id),this._inputNode.addEventListener("keydown",this._listboxOnKeyDown),this._inputNode.addEventListener("input",this._textboxOnInput),this._inputNode.addEventListener("keydown",this._textboxOnKeydown)}__teardownCombobox(){this._inputNode.removeEventListener("keydown",this._listboxOnKeyDown),this._inputNode.removeEventListener("input",this._textboxOnInput),this._inputNode.removeEventListener("keydown",this._textboxOnKeydown)}_onKeyUp(t){const r=t&&t.key;this.opened=this._showOverlayCondition({lastKey:r,currentValue:this._inputNode.value})}_textboxOnClick(t){this.opened=this._showOverlayCondition({})}clear(){this.value="",super.clear(),this.__shouldAutocompleteNextUpdate=!0}}var _V=te`
  ${qc}

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
    ${kp}
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
`,wV=class extends yV{static get styles(){return[...super.styles,_V]}constructor(){super(),this.defaultValidators=[]}_inputGroupInputTemplate(){return W`
      <div class="input-group__input">
        <slot name="input"></slot>
        <craft-icon
          class="indicator"
          name="chevron-down"
          style="font-size: 0.8em"
        ></craft-icon>
      </div>
    `}parser(e){return e!==""?e:super.parser(e)}_getTextboxValueFromOption(e){return e?e.textContent?.trim()||"":super._getTextboxValueFromOption(e)}};customElements.get("craft-combobox")||customElements.define("craft-combobox",wV);var eu=class extends Ne{constructor(...e){super(...e),this.variant=Sr.Default,this.label=null}render(){return W`<span
      class="${er({indicator:!0,"indicator--success":this.variant===Sr.Success,"indicator--danger":this.variant===Sr.Danger,"indicator--warning":this.variant===Sr.Warning,"indicator--info":this.variant===Sr.Info,"indicator--empty":this.variant==="empty"})}"
    ></span>`}};eu.styles=[Mp,te`
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
    `];se([V({reflect:!0})],eu.prototype,"variant",void 0);se([V()],eu.prototype,"label",void 0);customElements.get("craft-indicator")||customElements.define("craft-indicator",eu);var ya=class extends Ne{constructor(){super(),this.alt=!1,this.shift=!1,this.os="Unknown",this.os=this.detectOS()}connectedCallback(){super.connectedCallback(),this.os==="Unknown"&&(this.os=this.detectOS())}detectOS(){const e=navigator.platform.toLowerCase();return e.includes("mac")||/iphone|ipad|ipod/.test(e)?"Mac":e.includes("win")?"Windows":e.includes("linux")?"Linux":"Unknown"}renderShortcutPrefix(){switch(this.os){case"Mac":return`${this.alt?"⌥":""}${this.shift?"⇧":""}⌘`;case"Linux":return`Super+${this.alt?"Alt+":""}${this.shift?"Shift+":""}`;default:return`Ctrl+${this.alt?"Alt+":""}${this.shift?"Shift+":""}`}}render(){return W`<span class="shortcut"
      >${this.renderShortcutPrefix()}<slot></slot
    ></span>`}};ya.styles=te`
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
  `;se([V({type:Boolean})],ya.prototype,"alt",void 0);se([V({type:Boolean})],ya.prototype,"shift",void 0);se([V()],ya.prototype,"os",void 0);customElements.get("craft-shortcut")||customElements.define("craft-shortcut",ya);class EV extends Gc(pE(Ne)){connectedCallback(){super.connectedCallback(),this.setAttribute("role","radiogroup")}resetGroup(){let t;this.formElements.forEach(r=>{typeof r.resetGroup=="function"?r.resetGroup():typeof r.reset=="function"&&(r.reset(),r.checked&&(t=r.choiceValue))}),this.modelValue=t,this.resetInteractionState()}}class xV extends Yc(Xc){connectedCallback(){super.connectedCallback(),this.type="radio"}}var SV=class extends EV{static get styles(){return[...super.styles,pa,te`
        .input-group {
          display: grid;
          gap: var(--c-spacing-xs);
        }
      `]}};customElements.get("craft-radio-group")||customElements.define("craft-radio-group",SV);var CV=class extends xV{static get styles(){return[...super.styles,te`
        /* same as checkbox, potentially consolidate */
        :host {
          gap: var(--c-spacing-sm);
        }
      `]}};customElements.get("craft-radio")||customElements.define("craft-radio",CV);var AV=class{constructor(){this.refreshPromise=null,this.tokenName=null,this.tokenValue=null,this.refreshPromise=null}async getToken(){return this.tokenValue||await this.refreshToken(),this.tokenValue}async refreshToken(){return this.refreshPromise?this.refreshPromise:(this.refreshPromise=_a.get("users/session-info").then(({data:e})=>{const{csrfTokenName:t,csrfTokenValue:r}=e;return this.tokenName=t??null,this.tokenValue=r??null,this.tokenValue}).finally(()=>{this.refreshPromise=null}),this.refreshPromise)}clearToken(){this.tokenValue=null}};function TV(e=""){return`https://craft6-dev.ddev.site/admin/actions/${e}`}function kV(){let e={"X-Registered-Asset-Bundles":[...new Set(Craft.registeredAssetBundles)].join(","),"X-Registered-Js-Files":[...new Set(Craft.registeredJsFiles)].join(",")};return Craft.csrfTokenValue&&(e["X-CSRF-Token"]=Craft.csrfTokenValue),e}const _a=is.create({baseURL:TV()}),Zh=new AV;_a.interceptors.request.use(async e=>{e.headers.set("X-Requested-With","XMLHttpRequest");const t=kV();if(Object.entries(t).forEach(([r,n])=>{e.headers.set(r,n)}),["post","put","patch","delete"].includes(e.method?.toLowerCase()||"")&&!e.url?.includes("users/session-info")){const r=await Zh.getToken();r&&e.headers.set("X-CSRF-Token",r)}return e});_a.interceptors.response.use(e=>e,async e=>{const t=e.config;if(e.response?.status===419||e.response?.status===403&&!t._retry){t._retry=!0;try{return Zh.clearToken(),t.headers["X-CSRF-Token"]=await Zh.refreshToken(),is(t)}catch(r){return console.error("Failed to refresh CSRF token:",r),Promise.reject(r)}}return Promise.reject(e)});let al=!1,Ai=null;async function OV(e){if(!al){if(Ai)return Ai;al=!0;try{return(await _a.post("app/api-headers",void 0,{cancelToken:e})).data}catch{}finally{al=!1}}}const Dp=is.create({baseURL:"https://api.craftcms.com/v1/"});async function NV(e){return Ai?Object.entries(Ai).forEach(([t,r])=>{e.headers.set(t,r)}):(e.params=e.params||{},e.params.processCraftHeaders=1),e}async function PV(e,t){if(Ai)return;const{data:r}=await _a.post("app/process-api-response-headers",{headers:e},{cancelToken:t});return Ai=r,al=!1,Ai}async function IV(e){return await PV(e.headers,e.config.cancelToken),e}Dp.interceptors.request.use(async e=>{const{cancelToken:t}=e,r=await OV(t);r&&Object.entries(r).forEach(([i,s])=>{e.headers.set(i,s)});const n={...e,params:{...Craft.apiParams||{},...e.params,v:new Date().getTime()}};return r||(n.params.processCraftHeaders=1),Craft.httpProxy&&(n.proxy=Craft.httpProxy),n});Dp.interceptors.request.use(NV);Dp.interceptors.response.use(IV);xL({resolve:e=>wk(`./pages/${e}.vue`,Object.assign({"./pages/Install.vue":()=>Y(()=>import("./Install.js"),__vite__mapDeps([66,67,68,69,70,71,72,73,74,75,76,77]),import.meta.url),"./pages/SettingsGeneralPage.vue":()=>Y(()=>import("./SettingsGeneralPage.js"),__vite__mapDeps([78,73,79,67,68,69,70,71,72,80,74,75,76,81]),import.meta.url),"./pages/SettingsIndexPage.vue":()=>Y(()=>import("./SettingsIndexPage.js"),__vite__mapDeps([82,67,68,69,70,71,72,73,79,80,74,75,76,83]),import.meta.url)})),setup({el:e,App:t,props:r,plugin:n}){Al({render:()=>pn(t,r)}).use(n).mount(e)}});export{Wo as A,P0 as B,Be as C,vx as D,Cl as E,it as F,iA as G,zV as H,Wx as I,Es as J,qV as K,Tx as L,Ko as M,oS as N,Qy as O,HV as P,AC as T,xf as a,qS as b,yl as c,Cs as d,BS as e,Sf as f,wi as g,zt as h,yx as i,Cv as j,oo as k,_x as l,I1 as m,Pt as n,No as o,Wn as p,Xo as q,Gx as r,of as s,Qv as t,jo as u,Xx as v,df as w,cS as x,PC as y,yS as z};
