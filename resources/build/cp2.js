const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["./bg-BG2.js","./bg3.js","./cs-CZ2.js","./cs3.js","./de-DE2.js","./de3.js","./en-AU2.js","./en3.js","./en-GB2.js","./en-US2.js","./es-ES2.js","./es3.js","./fr-FR2.js","./fr3.js","./fr-BE2.js","./hu-HU2.js","./hu3.js","./it-IT2.js","./it3.js","./nl-BE2.js","./nl3.js","./nl-NL2.js","./pl-PL2.js","./pl3.js","./ro-RO2.js","./ro3.js","./ru-RU2.js","./ru3.js","./sk-SK2.js","./sk3.js","./tr-TR.js","./tr.js","./uk-UA2.js","./uk3.js","./bg-BG.js","./bg2.js","./cs-CZ.js","./cs2.js","./de-DE.js","./de2.js","./en-AU.js","./en2.js","./en-GB.js","./en-US.js","./es-ES.js","./es2.js","./fr-FR.js","./fr2.js","./fr-BE.js","./hu-HU.js","./hu2.js","./it-IT.js","./it2.js","./nl-BE.js","./nl2.js","./nl-NL.js","./pl-PL.js","./pl2.js","./ro-RO.js","./ro2.js","./ru-RU.js","./ru2.js","./sk-SK.js","./sk2.js","./uk-UA.js","./uk2.js","./Install.js","./lit-element.js","./nav-list.ts.js","./nav-item.ts.js","./state.js","./property.js","./progress-CM21Jrvu.js","./_plugin-vue_export-helper.js","./index2.js","./Modal.js","./assets/Modal.css","./CpGlobalSidebar.js","./custom-element.js","./assets/Install.css","./SettingsGeneralPage.js","./CalloutReadOnly.vue_vue_type_script_setup_true_lang.js","./assets/CalloutReadOnly.css","./index.js","./TransitionFade.js","./assets/TransitionFade.css","./assets/SettingsGeneralPage.css","./SettingsIndexPage.js","./assets/SettingsIndexPage.css","./SettingsSitesEdit.js","./Badge.vue_vue_type_script_setup_true_lang.js","./assets/Badge.css","./SettingsSitesIndex.js","./assets/SettingsSitesIndex.css"])))=>i.map(i=>d[i]);
import{a as ls,i as Ov,b as Nv,m as XE,_ as Y}from"./index2.js";import{e as se,r as er}from"./state.js";import{a as te,i as Ne,x as W,r as JE,Z as QE,B as Gp,E as pt,T as Qd,S as Pv}from"./lit-element.js";import{n as V}from"./property.js";import{e as ZE,a as Ue}from"./CpGlobalSidebar.js";import{e as tr,i as Fv,t as Iv,a as Lv}from"./nav-item.ts.js";import{t as Lr}from"./custom-element.js";import"./nav-list.ts.js";import"./progress-CM21Jrvu.js";function e1(e){const t=Object.create(null);for(const r of e.split(","))t[r]=1;return r=>r in t}const t1={},r1=()=>{},of=Object.assign,n1=(e,t)=>{const r=e.indexOf(t);r>-1&&e.splice(r,1)},i1=Object.prototype.hasOwnProperty,yl=(e,t)=>i1.call(e,t),yn=Array.isArray,lo=e=>dc(e)==="[object Map]",s1=e=>dc(e)==="[object Set]",ko=e=>typeof e=="function",o1=e=>typeof e=="string",Qo=e=>typeof e=="symbol",ki=e=>e!==null&&typeof e=="object",a1=Object.prototype.toString,dc=e=>a1.call(e),l1=e=>dc(e).slice(8,-1),c1=e=>dc(e)==="[object Object]",hc=e=>o1(e)&&e!=="NaN"&&e[0]!=="-"&&""+parseInt(e,10)===e,Yn=(e,t)=>!Object.is(e,t),u1=(e,t,r,n=!1)=>{Object.defineProperty(e,t,{configurable:!0,enumerable:!1,writable:n,value:r})};let xt;class af{constructor(t=!1){this.detached=t,this._active=!0,this._on=0,this.effects=[],this.cleanups=[],this._isPaused=!1,this.parent=xt,!t&&xt&&(this.index=(xt.scopes||(xt.scopes=[])).push(this)-1)}get active(){return this._active}pause(){if(this._active){this._isPaused=!0;let t,r;if(this.scopes)for(t=0,r=this.scopes.length;t<r;t++)this.scopes[t].pause();for(t=0,r=this.effects.length;t<r;t++)this.effects[t].pause()}}resume(){if(this._active&&this._isPaused){this._isPaused=!1;let t,r;if(this.scopes)for(t=0,r=this.scopes.length;t<r;t++)this.scopes[t].resume();for(t=0,r=this.effects.length;t<r;t++)this.effects[t].resume()}}run(t){if(this._active){const r=xt;try{return xt=this,t()}finally{xt=r}}}on(){++this._on===1&&(this.prevScope=xt,xt=this)}off(){this._on>0&&--this._on===0&&(xt=this.prevScope,this.prevScope=void 0)}stop(t){if(this._active){this._active=!1;let r,n;for(r=0,n=this.effects.length;r<n;r++)this.effects[r].stop();for(this.effects.length=0,r=0,n=this.cleanups.length;r<n;r++)this.cleanups[r]();if(this.cleanups.length=0,this.scopes){for(r=0,n=this.scopes.length;r<n;r++)this.scopes[r].stop(!0);this.scopes.length=0}if(!this.detached&&this.parent&&!t){const i=this.parent.scopes.pop();i&&i!==this&&(this.parent.scopes[this.index]=i,i.index=this.index)}this.parent=void 0}}}function d1(e){return new af(e)}function Rv(){return xt}function h1(e,t=!1){xt&&xt.cleanups.push(e)}let Ie;const du=new WeakSet;class Oo{constructor(t){this.fn=t,this.deps=void 0,this.depsTail=void 0,this.flags=5,this.next=void 0,this.cleanup=void 0,this.scheduler=void 0,xt&&xt.active&&xt.effects.push(this)}pause(){this.flags|=64}resume(){this.flags&64&&(this.flags&=-65,du.has(this)&&(du.delete(this),this.trigger()))}notify(){this.flags&2&&!(this.flags&32)||this.flags&8||Mv(this)}run(){if(!(this.flags&1))return this.fn();this.flags|=2,Yp(this),Dv(this);const t=Ie,r=Tr;Ie=this,Tr=!0;try{return this.fn()}finally{Vv(this),Ie=t,Tr=r,this.flags&=-3}}stop(){if(this.flags&1){for(let t=this.deps;t;t=t.nextDep)uf(t);this.deps=this.depsTail=void 0,Yp(this),this.onStop&&this.onStop(),this.flags&=-2}}trigger(){this.flags&64?du.add(this):this.scheduler?this.scheduler():this.runIfDirty()}runIfDirty(){Zd(this)&&this.run()}get dirty(){return Zd(this)}}let $v=0,co,uo;function Mv(e,t=!1){if(e.flags|=8,t){e.next=uo,uo=e;return}e.next=co,co=e}function lf(){$v++}function cf(){if(--$v>0)return;if(uo){let t=uo;for(uo=void 0;t;){const r=t.next;t.next=void 0,t.flags&=-9,t=r}}let e;for(;co;){let t=co;for(co=void 0;t;){const r=t.next;if(t.next=void 0,t.flags&=-9,t.flags&1)try{t.trigger()}catch(n){e||(e=n)}t=r}}if(e)throw e}function Dv(e){for(let t=e.deps;t;t=t.nextDep)t.version=-1,t.prevActiveLink=t.dep.activeLink,t.dep.activeLink=t}function Vv(e){let t,r=e.depsTail,n=r;for(;n;){const i=n.prevDep;n.version===-1?(n===r&&(r=i),uf(n),f1(n)):t=n,n.dep.activeLink=n.prevActiveLink,n.prevActiveLink=void 0,n=i}e.deps=t,e.depsTail=r}function Zd(e){for(let t=e.deps;t;t=t.nextDep)if(t.dep.version!==t.version||t.dep.computed&&(Bv(t.dep.computed)||t.dep.version!==t.version))return!0;return!!e._dirty}function Bv(e){if(e.flags&4&&!(e.flags&16)||(e.flags&=-17,e.globalVersion===No)||(e.globalVersion=No,!e.isSSR&&e.flags&128&&(!e.deps&&!e._dirty||!Zd(e))))return;e.flags|=2;const t=e.dep,r=Ie,n=Tr;Ie=e,Tr=!0;try{Dv(e);const i=e.fn(e._value);(t.version===0||Yn(i,e._value))&&(e.flags|=128,e._value=i,t.version++)}catch(i){throw t.version++,i}finally{Ie=r,Tr=n,Vv(e),e.flags&=-3}}function uf(e,t=!1){const{dep:r,prevSub:n,nextSub:i}=e;if(n&&(n.nextSub=i,e.prevSub=void 0),i&&(i.prevSub=n,e.nextSub=void 0),r.subs===e&&(r.subs=n,!n&&r.computed)){r.computed.flags&=-5;for(let s=r.computed.deps;s;s=s.nextDep)uf(s,!0)}!t&&!--r.sc&&r.map&&r.map.delete(r.key)}function f1(e){const{prevDep:t,nextDep:r}=e;t&&(t.nextDep=r,e.prevDep=void 0),r&&(r.prevDep=t,e.nextDep=void 0)}function p1(e,t){e.effect instanceof Oo&&(e=e.effect.fn);const r=new Oo(e);t&&of(r,t);try{r.run()}catch(i){throw r.stop(),i}const n=r.run.bind(r);return n.effect=r,n}function m1(e){e.effect.stop()}let Tr=!0;const Uv=[];function _n(){Uv.push(Tr),Tr=!1}function wn(){const e=Uv.pop();Tr=e===void 0?!0:e}function Yp(e){const{cleanup:t}=e;if(e.cleanup=void 0,t){const r=Ie;Ie=void 0;try{t()}finally{Ie=r}}}let No=0,g1=class{constructor(t,r){this.sub=t,this.dep=r,this.version=r.version,this.nextDep=this.prevDep=this.nextSub=this.prevSub=this.prevActiveLink=void 0}};class fc{constructor(t){this.computed=t,this.version=0,this.activeLink=void 0,this.subs=void 0,this.map=void 0,this.key=void 0,this.sc=0,this.__v_skip=!0}track(t){if(!Ie||!Tr||Ie===this.computed)return;let r=this.activeLink;if(r===void 0||r.sub!==Ie)r=this.activeLink=new g1(Ie,this),Ie.deps?(r.prevDep=Ie.depsTail,Ie.depsTail.nextDep=r,Ie.depsTail=r):Ie.deps=Ie.depsTail=r,zv(r);else if(r.version===-1&&(r.version=this.version,r.nextDep)){const n=r.nextDep;n.prevDep=r.prevDep,r.prevDep&&(r.prevDep.nextDep=n),r.prevDep=Ie.depsTail,r.nextDep=void 0,Ie.depsTail.nextDep=r,Ie.depsTail=r,Ie.deps===r&&(Ie.deps=n)}return r}trigger(t){this.version++,No++,this.notify(t)}notify(t){lf();try{for(let r=this.subs;r;r=r.prevSub)r.sub.notify()&&r.sub.dep.notify()}finally{cf()}}}function zv(e){if(e.dep.sc++,e.sub.flags&4){const t=e.dep.computed;if(t&&!e.dep.subs){t.flags|=20;for(let n=t.deps;n;n=n.nextDep)zv(n)}const r=e.dep.subs;r!==e&&(e.prevSub=r,r&&(r.nextSub=e)),e.dep.subs=e}}const _l=new WeakMap,_i=Symbol(""),eh=Symbol(""),Po=Symbol("");function Ct(e,t,r){if(Tr&&Ie){let n=_l.get(e);n||_l.set(e,n=new Map);let i=n.get(r);i||(n.set(r,i=new fc),i.map=n,i.key=r),i.track()}}function un(e,t,r,n,i,s){const o=_l.get(e);if(!o){No++;return}const a=l=>{l&&l.trigger()};if(lf(),t==="clear")o.forEach(a);else{const l=yn(e),u=l&&hc(r);if(l&&r==="length"){const c=Number(n);o.forEach((d,p)=>{(p==="length"||p===Po||!Qo(p)&&p>=c)&&a(d)})}else switch((r!==void 0||o.has(void 0))&&a(o.get(r)),u&&a(o.get(Po)),t){case"add":l?u&&a(o.get("length")):(a(o.get(_i)),lo(e)&&a(o.get(eh)));break;case"delete":l||(a(o.get(_i)),lo(e)&&a(o.get(eh)));break;case"set":lo(e)&&a(o.get(_i));break}}cf()}function b1(e,t){const r=_l.get(e);return r&&r.get(t)}function Hi(e){const t=ye(e);return t===e?t:(Ct(t,"iterate",Po),Ut(e)?t:t.map(Or))}function pc(e){return Ct(e=ye(e),"iterate",Po),e}function Un(e,t){return Yr(e)?cs(mn(e)?Or(t):t):Or(t)}const v1={__proto__:null,[Symbol.iterator](){return hu(this,Symbol.iterator,e=>Un(this,e))},concat(...e){return Hi(this).concat(...e.map(t=>yn(t)?Hi(t):t))},entries(){return hu(this,"entries",e=>(e[1]=Un(this,e[1]),e))},every(e,t){return rn(this,"every",e,t,void 0,arguments)},filter(e,t){return rn(this,"filter",e,t,r=>r.map(n=>Un(this,n)),arguments)},find(e,t){return rn(this,"find",e,t,r=>Un(this,r),arguments)},findIndex(e,t){return rn(this,"findIndex",e,t,void 0,arguments)},findLast(e,t){return rn(this,"findLast",e,t,r=>Un(this,r),arguments)},findLastIndex(e,t){return rn(this,"findLastIndex",e,t,void 0,arguments)},forEach(e,t){return rn(this,"forEach",e,t,void 0,arguments)},includes(...e){return fu(this,"includes",e)},indexOf(...e){return fu(this,"indexOf",e)},join(e){return Hi(this).join(e)},lastIndexOf(...e){return fu(this,"lastIndexOf",e)},map(e,t){return rn(this,"map",e,t,void 0,arguments)},pop(){return Vs(this,"pop")},push(...e){return Vs(this,"push",e)},reduce(e,...t){return Xp(this,"reduce",e,t)},reduceRight(e,...t){return Xp(this,"reduceRight",e,t)},shift(){return Vs(this,"shift")},some(e,t){return rn(this,"some",e,t,void 0,arguments)},splice(...e){return Vs(this,"splice",e)},toReversed(){return Hi(this).toReversed()},toSorted(e){return Hi(this).toSorted(e)},toSpliced(...e){return Hi(this).toSpliced(...e)},unshift(...e){return Vs(this,"unshift",e)},values(){return hu(this,"values",e=>Un(this,e))}};function hu(e,t,r){const n=pc(e),i=n[t]();return n!==e&&!Ut(e)&&(i._next=i.next,i.next=()=>{const s=i._next();return s.done||(s.value=r(s.value)),s}),i}const y1=Array.prototype;function rn(e,t,r,n,i,s){const o=pc(e),a=o!==e&&!Ut(e),l=o[t];if(l!==y1[t]){const d=l.apply(e,s);return a?Or(d):d}let u=r;o!==e&&(a?u=function(d,p){return r.call(this,Un(e,d),p,e)}:r.length>2&&(u=function(d,p){return r.call(this,d,p,e)}));const c=l.call(o,u,n);return a&&i?i(c):c}function Xp(e,t,r,n){const i=pc(e);let s=r;return i!==e&&(Ut(e)?r.length>3&&(s=function(o,a,l){return r.call(this,o,a,l,e)}):s=function(o,a,l){return r.call(this,o,Un(e,a),l,e)}),i[t](s,...n)}function fu(e,t,r){const n=ye(e);Ct(n,"iterate",Po);const i=n[t](...r);return(i===-1||i===!1)&&Zo(r[0])?(r[0]=ye(r[0]),n[t](...r)):i}function Vs(e,t,r=[]){_n(),lf();const n=ye(e)[t].apply(e,r);return cf(),wn(),n}const _1=e1("__proto__,__v_isRef,__isVue"),qv=new Set(Object.getOwnPropertyNames(Symbol).filter(e=>e!=="arguments"&&e!=="caller").map(e=>Symbol[e]).filter(Qo));function w1(e){Qo(e)||(e=String(e));const t=ye(this);return Ct(t,"has",e),t.hasOwnProperty(e)}class Hv{constructor(t=!1,r=!1){this._isReadonly=t,this._isShallow=r}get(t,r,n){if(r==="__v_skip")return t.__v_skip;const i=this._isReadonly,s=this._isShallow;if(r==="__v_isReactive")return!i;if(r==="__v_isReadonly")return i;if(r==="__v_isShallow")return s;if(r==="__v_raw")return n===(i?s?Xv:Yv:s?Gv:Kv).get(t)||Object.getPrototypeOf(t)===Object.getPrototypeOf(n)?t:void 0;const o=yn(t);if(!i){let l;if(o&&(l=v1[r]))return l;if(r==="hasOwnProperty")return w1}const a=Reflect.get(t,r,et(t)?t:n);if((Qo(r)?qv.has(r):_1(r))||(i||Ct(t,"get",r),s))return a;if(et(a)){const l=o&&hc(r)?a:a.value;return i&&ki(l)?wl(l):l}return ki(a)?i?wl(a):Ts(a):a}}class jv extends Hv{constructor(t=!1){super(!1,t)}set(t,r,n,i){let s=t[r];const o=yn(t)&&hc(r);if(!this._isShallow){const u=Yr(s);if(!Ut(n)&&!Yr(n)&&(s=ye(s),n=ye(n)),!o&&et(s)&&!et(n))return u||(s.value=n),!0}const a=o?Number(r)<t.length:yl(t,r),l=Reflect.set(t,r,n,et(t)?t:i);return t===ye(i)&&(a?Yn(n,s)&&un(t,"set",r,n):un(t,"add",r,n)),l}deleteProperty(t,r){const n=yl(t,r);t[r];const i=Reflect.deleteProperty(t,r);return i&&n&&un(t,"delete",r,void 0),i}has(t,r){const n=Reflect.has(t,r);return(!Qo(r)||!qv.has(r))&&Ct(t,"has",r),n}ownKeys(t){return Ct(t,"iterate",yn(t)?"length":_i),Reflect.ownKeys(t)}}class Wv extends Hv{constructor(t=!1){super(!0,t)}set(t,r){return!0}deleteProperty(t,r){return!0}}const E1=new jv,x1=new Wv,S1=new jv(!0),C1=new Wv(!0),th=e=>e,Ta=e=>Reflect.getPrototypeOf(e);function A1(e,t,r){return function(...n){const i=this.__v_raw,s=ye(i),o=lo(s),a=e==="entries"||e===Symbol.iterator&&o,l=e==="keys"&&o,u=i[e](...n),c=r?th:t?cs:Or;return!t&&Ct(s,"iterate",l?eh:_i),of(Object.create(u),{next(){const{value:d,done:p}=u.next();return p?{value:d,done:p}:{value:a?[c(d[0]),c(d[1])]:c(d),done:p}}})}}function ka(e){return function(...t){return e==="delete"?!1:e==="clear"?void 0:this}}function T1(e,t){const r={get(i){const s=this.__v_raw,o=ye(s),a=ye(i);e||(Yn(i,a)&&Ct(o,"get",i),Ct(o,"get",a));const{has:l}=Ta(o),u=t?th:e?cs:Or;if(l.call(o,i))return u(s.get(i));if(l.call(o,a))return u(s.get(a));s!==o&&s.get(i)},get size(){const i=this.__v_raw;return!e&&Ct(ye(i),"iterate",_i),i.size},has(i){const s=this.__v_raw,o=ye(s),a=ye(i);return e||(Yn(i,a)&&Ct(o,"has",i),Ct(o,"has",a)),i===a?s.has(i):s.has(i)||s.has(a)},forEach(i,s){const o=this,a=o.__v_raw,l=ye(a),u=t?th:e?cs:Or;return!e&&Ct(l,"iterate",_i),a.forEach((c,d)=>i.call(s,u(c),u(d),o))}};return of(r,e?{add:ka("add"),set:ka("set"),delete:ka("delete"),clear:ka("clear")}:{add(i){!t&&!Ut(i)&&!Yr(i)&&(i=ye(i));const s=ye(this);return Ta(s).has.call(s,i)||(s.add(i),un(s,"add",i,i)),this},set(i,s){!t&&!Ut(s)&&!Yr(s)&&(s=ye(s));const o=ye(this),{has:a,get:l}=Ta(o);let u=a.call(o,i);u||(i=ye(i),u=a.call(o,i));const c=l.call(o,i);return o.set(i,s),u?Yn(s,c)&&un(o,"set",i,s):un(o,"add",i,s),this},delete(i){const s=ye(this),{has:o,get:a}=Ta(s);let l=o.call(s,i);l||(i=ye(i),l=o.call(s,i)),a&&a.call(s,i);const u=s.delete(i);return l&&un(s,"delete",i,void 0),u},clear(){const i=ye(this),s=i.size!==0,o=i.clear();return s&&un(i,"clear",void 0,void 0),o}}),["keys","values","entries",Symbol.iterator].forEach(i=>{r[i]=A1(i,e,t)}),r}function mc(e,t){const r=T1(e,t);return(n,i,s)=>i==="__v_isReactive"?!e:i==="__v_isReadonly"?e:i==="__v_raw"?n:Reflect.get(yl(r,i)&&i in n?r:n,i,s)}const k1={get:mc(!1,!1)},O1={get:mc(!1,!0)},N1={get:mc(!0,!1)},P1={get:mc(!0,!0)},Kv=new WeakMap,Gv=new WeakMap,Yv=new WeakMap,Xv=new WeakMap;function F1(e){switch(e){case"Object":case"Array":return 1;case"Map":case"Set":case"WeakMap":case"WeakSet":return 2;default:return 0}}function I1(e){return e.__v_skip||!Object.isExtensible(e)?0:F1(l1(e))}function Ts(e){return Yr(e)?e:gc(e,!1,E1,k1,Kv)}function Jv(e){return gc(e,!1,S1,O1,Gv)}function wl(e){return gc(e,!0,x1,N1,Yv)}function L1(e){return gc(e,!0,C1,P1,Xv)}function gc(e,t,r,n,i){if(!ki(e)||e.__v_raw&&!(t&&e.__v_isReactive))return e;const s=I1(e);if(s===0)return e;const o=i.get(e);if(o)return o;const a=new Proxy(e,s===2?n:r);return i.set(e,a),a}function mn(e){return Yr(e)?mn(e.__v_raw):!!(e&&e.__v_isReactive)}function Yr(e){return!!(e&&e.__v_isReadonly)}function Ut(e){return!!(e&&e.__v_isShallow)}function Zo(e){return e?!!e.__v_raw:!1}function ye(e){const t=e&&e.__v_raw;return t?ye(t):e}function El(e){return!yl(e,"__v_skip")&&Object.isExtensible(e)&&u1(e,"__v_skip",!0),e}const Or=e=>ki(e)?Ts(e):e,cs=e=>ki(e)?wl(e):e;function et(e){return e?e.__v_isRef===!0:!1}function Hr(e){return Qv(e,!1)}function df(e){return Qv(e,!0)}function Qv(e,t){return et(e)?e:new R1(e,t)}class R1{constructor(t,r){this.dep=new fc,this.__v_isRef=!0,this.__v_isShallow=!1,this._rawValue=r?t:ye(t),this._value=r?t:Or(t),this.__v_isShallow=r}get value(){return this.dep.track(),this._value}set value(t){const r=this._rawValue,n=this.__v_isShallow||Ut(t)||Yr(t);t=n?t:ye(t),Yn(t,r)&&(this._rawValue=t,this._value=n?t:Or(t),this.dep.trigger())}}function $1(e){e.dep&&e.dep.trigger()}function ea(e){return et(e)?e.value:e}function M1(e){return ko(e)?e():ea(e)}const D1={get:(e,t,r)=>t==="__v_raw"?e:ea(Reflect.get(e,t,r)),set:(e,t,r,n)=>{const i=e[t];return et(i)&&!et(r)?(i.value=r,!0):Reflect.set(e,t,r,n)}};function hf(e){return mn(e)?e:new Proxy(e,D1)}class V1{constructor(t){this.__v_isRef=!0,this._value=void 0;const r=this.dep=new fc,{get:n,set:i}=t(r.track.bind(r),r.trigger.bind(r));this._get=n,this._set=i}get value(){return this._value=this._get()}set value(t){this._set(t)}}function Zv(e){return new V1(e)}function B1(e){const t=yn(e)?new Array(e.length):{};for(const r in e)t[r]=ey(e,r);return t}class U1{constructor(t,r,n){this._object=t,this._key=r,this._defaultValue=n,this.__v_isRef=!0,this._value=void 0,this._raw=ye(t);let i=!0,s=t;if(!yn(t)||!hc(String(r)))do i=!Zo(s)||Ut(s);while(i&&(s=s.__v_raw));this._shallow=i}get value(){let t=this._object[this._key];return this._shallow&&(t=ea(t)),this._value=t===void 0?this._defaultValue:t}set value(t){if(this._shallow&&et(this._raw[this._key])){const r=this._object[this._key];if(et(r)){r.value=t;return}}this._object[this._key]=t}get dep(){return b1(this._raw,this._key)}}class z1{constructor(t){this._getter=t,this.__v_isRef=!0,this.__v_isReadonly=!0,this._value=void 0}get value(){return this._value=this._getter()}}function q1(e,t,r){return et(e)?e:ko(e)?new z1(e):ki(e)&&arguments.length>1?ey(e,t,r):Hr(e)}function ey(e,t,r){return new U1(e,t,r)}class H1{constructor(t,r,n){this.fn=t,this.setter=r,this._value=void 0,this.dep=new fc(this),this.__v_isRef=!0,this.deps=void 0,this.depsTail=void 0,this.flags=16,this.globalVersion=No-1,this.next=void 0,this.effect=this,this.__v_isReadonly=!r,this.isSSR=n}notify(){if(this.flags|=16,!(this.flags&8)&&Ie!==this)return Mv(this,!0),!0}get value(){const t=this.dep.track();return Bv(this),t&&(t.version=this.dep.version),this._value}set value(t){this.setter&&this.setter(t)}}function j1(e,t,r=!1){let n,i;return ko(e)?n=e:(n=e.get,i=e.set),new H1(n,i,r)}const W1={GET:"get",HAS:"has",ITERATE:"iterate"},K1={SET:"set",ADD:"add",DELETE:"delete",CLEAR:"clear"},Oa={},xl=new WeakMap;let zn;function G1(){return zn}function ty(e,t=!1,r=zn){if(r){let n=xl.get(r);n||xl.set(r,n=[]),n.push(e)}}function Y1(e,t,r=t1){const{immediate:n,deep:i,once:s,scheduler:o,augmentJob:a,call:l}=r,u=_=>i?_:Ut(_)||i===!1||i===0?dn(_,1):dn(_);let c,d,p,m,h=!1,f=!1;if(et(e)?(d=()=>e.value,h=Ut(e)):mn(e)?(d=()=>u(e),h=!0):yn(e)?(f=!0,h=e.some(_=>mn(_)||Ut(_)),d=()=>e.map(_=>{if(et(_))return _.value;if(mn(_))return u(_);if(ko(_))return l?l(_,2):_()})):ko(e)?t?d=l?()=>l(e,2):e:d=()=>{if(p){_n();try{p()}finally{wn()}}const _=zn;zn=c;try{return l?l(e,3,[m]):e(m)}finally{zn=_}}:d=r1,t&&i){const _=d,x=i===!0?1/0:i;d=()=>dn(_(),x)}const b=Rv(),y=()=>{c.stop(),b&&b.active&&n1(b.effects,c)};if(s&&t){const _=t;t=(...x)=>{_(...x),y()}}let w=f?new Array(e.length).fill(Oa):Oa;const v=_=>{if(!(!(c.flags&1)||!c.dirty&&!_))if(t){const x=c.run();if(i||h||(f?x.some((k,S)=>Yn(k,w[S])):Yn(x,w))){p&&p();const k=zn;zn=c;try{const S=[x,w===Oa?void 0:f&&w[0]===Oa?[]:w,m];w=x,l?l(t,3,S):t(...S)}finally{zn=k}}}else c.run()};return a&&a(v),c=new Oo(d),c.scheduler=o?()=>o(v,!1):v,m=_=>ty(_,!1,c),p=c.onStop=()=>{const _=xl.get(c);if(_){if(l)l(_,4);else for(const x of _)x();xl.delete(c)}},t?n?v(!0):w=c.run():o?o(v.bind(null,!0),!0):c.run(),y.pause=c.pause.bind(c),y.resume=c.resume.bind(c),y.stop=y,y}function dn(e,t=1/0,r){if(t<=0||!ki(e)||e.__v_skip||(r=r||new Map,(r.get(e)||0)>=t))return e;if(r.set(e,t),t--,et(e))dn(e.value,t,r);else if(yn(e))for(let n=0;n<e.length;n++)dn(e[n],t,r);else if(s1(e)||lo(e))e.forEach(n=>{dn(n,t,r)});else if(c1(e)){for(const n in e)dn(e[n],t,r);for(const n of Object.getOwnPropertySymbols(e))Object.prototype.propertyIsEnumerable.call(e,n)&&dn(e[n],t,r)}return e}function ry(e){const t=Object.create(null);for(const r of e.split(","))t[r]=1;return r=>r in t}const Ee={},ns=[],jr=()=>{},ny=()=>!1,bc=e=>e.charCodeAt(0)===111&&e.charCodeAt(1)===110&&(e.charCodeAt(2)>122||e.charCodeAt(2)<97),iy=e=>e.startsWith("onUpdate:"),gt=Object.assign,sy=(e,t)=>{const r=e.indexOf(t);r>-1&&e.splice(r,1)},X1=Object.prototype.hasOwnProperty,Le=(e,t)=>X1.call(e,t),de=Array.isArray,J1=e=>vc(e)==="[object Map]",Q1=e=>vc(e)==="[object Set]",Z1=e=>vc(e)==="[object RegExp]",ce=e=>typeof e=="function",tt=e=>typeof e=="string",ff=e=>typeof e=="symbol",lt=e=>e!==null&&typeof e=="object",pf=e=>(lt(e)||ce(e))&&ce(e.then)&&ce(e.catch),oy=Object.prototype.toString,vc=e=>oy.call(e),ex=e=>vc(e)==="[object Object]",wi=ry(",key,ref,ref_for,ref_key,onVnodeBeforeMount,onVnodeMounted,onVnodeBeforeUpdate,onVnodeUpdated,onVnodeBeforeUnmount,onVnodeUnmounted"),yc=e=>{const t=Object.create(null);return(r=>t[r]||(t[r]=e(r)))},tx=/-\w/g,rr=yc(e=>e.replace(tx,t=>t.slice(1).toUpperCase())),rx=/\B([A-Z])/g,ks=yc(e=>e.replace(rx,"-$1").toLowerCase()),_c=yc(e=>e.charAt(0).toUpperCase()+e.slice(1)),ho=yc(e=>e?`on${_c(e)}`:""),fi=(e,t)=>!Object.is(e,t),fo=(e,...t)=>{for(let r=0;r<e.length;r++)e[r](...t)},nx=(e,t,r,n=!1)=>{Object.defineProperty(e,t,{configurable:!0,enumerable:!1,writable:n,value:r})},ix=e=>{const t=parseFloat(e);return isNaN(t)?e:t},sx=e=>{const t=tt(e)?Number(e):NaN;return isNaN(t)?e:t};let Jp;const wc=()=>Jp||(Jp=typeof globalThis<"u"?globalThis:typeof self<"u"?self:typeof window<"u"?window:typeof global<"u"?global:{}),ox="Infinity,undefined,NaN,isFinite,isNaN,parseFloat,parseInt,decodeURI,decodeURIComponent,encodeURI,encodeURIComponent,Math,Number,Date,Array,Object,Boolean,String,RegExp,Map,Set,JSON,Intl,BigInt,console,Error,Symbol",ax=ry(ox);function ta(e){if(de(e)){const t={};for(let r=0;r<e.length;r++){const n=e[r],i=tt(n)?dx(n):ta(n);if(i)for(const s in i)t[s]=i[s]}return t}else if(tt(e)||lt(e))return e}const lx=/;(?![^(]*\))/g,cx=/:([^]+)/,ux=/\/\*[^]*?\*\//g;function dx(e){const t={};return e.replace(ux,"").split(lx).forEach(r=>{if(r){const n=r.split(cx);n.length>1&&(t[n[0].trim()]=n[1].trim())}}),t}function ra(e){let t="";if(tt(e))t=e;else if(de(e))for(let r=0;r<e.length;r++){const n=ra(e[r]);n&&(t+=n+" ")}else if(lt(e))for(const r in e)e[r]&&(t+=r+" ");return t.trim()}function hx(e){if(!e)return null;let{class:t,style:r}=e;return t&&!tt(t)&&(e.class=ra(t)),r&&(e.style=ta(r)),e}const ay=e=>!!(e&&e.__v_isRef===!0),ly=e=>tt(e)?e:e==null?"":de(e)||lt(e)&&(e.toString===oy||!ce(e.toString))?ay(e)?ly(e.value):JSON.stringify(e,cy,2):String(e),cy=(e,t)=>ay(t)?cy(e,t.value):J1(t)?{[`Map(${t.size})`]:[...t.entries()].reduce((r,[n,i],s)=>(r[pu(n,s)+" =>"]=i,r),{})}:Q1(t)?{[`Set(${t.size})`]:[...t.values()].map(r=>pu(r))}:ff(t)?pu(t):lt(t)&&!de(t)&&!ex(t)?String(t):t,pu=(e,t="")=>{var r;return ff(e)?`Symbol(${(r=e.description)!=null?r:t})`:e};const uy=[];function fx(e){uy.push(e)}function px(){uy.pop()}function mx(e,t){}const gx={SETUP_FUNCTION:0,0:"SETUP_FUNCTION",RENDER_FUNCTION:1,1:"RENDER_FUNCTION",NATIVE_EVENT_HANDLER:5,5:"NATIVE_EVENT_HANDLER",COMPONENT_EVENT_HANDLER:6,6:"COMPONENT_EVENT_HANDLER",VNODE_HOOK:7,7:"VNODE_HOOK",DIRECTIVE_HOOK:8,8:"DIRECTIVE_HOOK",TRANSITION_HOOK:9,9:"TRANSITION_HOOK",APP_ERROR_HANDLER:10,10:"APP_ERROR_HANDLER",APP_WARN_HANDLER:11,11:"APP_WARN_HANDLER",FUNCTION_REF:12,12:"FUNCTION_REF",ASYNC_COMPONENT_LOADER:13,13:"ASYNC_COMPONENT_LOADER",SCHEDULER:14,14:"SCHEDULER",COMPONENT_UPDATE:15,15:"COMPONENT_UPDATE",APP_UNMOUNT_CLEANUP:16,16:"APP_UNMOUNT_CLEANUP"},bx={sp:"serverPrefetch hook",bc:"beforeCreate hook",c:"created hook",bm:"beforeMount hook",m:"mounted hook",bu:"beforeUpdate hook",u:"updated",bum:"beforeUnmount hook",um:"unmounted hook",a:"activated hook",da:"deactivated hook",ec:"errorCaptured hook",rtc:"renderTracked hook",rtg:"renderTriggered hook",0:"setup function",1:"render function",2:"watcher getter",3:"watcher callback",4:"watcher cleanup function",5:"native event handler",6:"component event handler",7:"vnode hook",8:"directive hook",9:"transition hook",10:"app errorHandler",11:"app warnHandler",12:"ref function",13:"async component loader",14:"scheduler flush",15:"component update",16:"app unmount cleanup function"};function Os(e,t,r,n){try{return n?e(...n):e()}catch(i){$i(i,t,r)}}function pr(e,t,r,n){if(ce(e)){const i=Os(e,t,r,n);return i&&pf(i)&&i.catch(s=>{$i(s,t,r)}),i}if(de(e)){const i=[];for(let s=0;s<e.length;s++)i.push(pr(e[s],t,r,n));return i}}function $i(e,t,r,n=!0){const i=t?t.vnode:null,{errorHandler:s,throwUnhandledErrorInProduction:o}=t&&t.appContext.config||Ee;if(t){let a=t.parent;const l=t.proxy,u=`https://vuejs.org/error-reference/#runtime-${r}`;for(;a;){const c=a.ec;if(c){for(let d=0;d<c.length;d++)if(c[d](e,l,u)===!1)return}a=a.parent}if(s){_n(),Os(s,null,10,[e,l,u]),wn();return}}vx(e,r,i,n,o)}function vx(e,t,r,n=!0,i=!1){if(i)throw e;console.error(e)}const It=[];let Vr=-1;const is=[];let qn=null,Ji=0;const dy=Promise.resolve();let Sl=null;function Ec(e){const t=Sl||dy;return e?t.then(this?e.bind(this):e):t}function yx(e){let t=Vr+1,r=It.length;for(;t<r;){const n=t+r>>>1,i=It[n],s=Io(i);s<e||s===e&&i.flags&2?t=n+1:r=n}return t}function mf(e){if(!(e.flags&1)){const t=Io(e),r=It[It.length-1];!r||!(e.flags&2)&&t>=Io(r)?It.push(e):It.splice(yx(t),0,e),e.flags|=1,hy()}}function hy(){Sl||(Sl=dy.then(fy))}function Fo(e){de(e)?is.push(...e):qn&&e.id===-1?qn.splice(Ji+1,0,e):e.flags&1||(is.push(e),e.flags|=1),hy()}function Qp(e,t,r=Vr+1){for(;r<It.length;r++){const n=It[r];if(n&&n.flags&2){if(e&&n.id!==e.uid)continue;It.splice(r,1),r--,n.flags&4&&(n.flags&=-2),n(),n.flags&4||(n.flags&=-2)}}}function Cl(e){if(is.length){const t=[...new Set(is)].sort((r,n)=>Io(r)-Io(n));if(is.length=0,qn){qn.push(...t);return}for(qn=t,Ji=0;Ji<qn.length;Ji++){const r=qn[Ji];r.flags&4&&(r.flags&=-2),r.flags&8||r(),r.flags&=-2}qn=null,Ji=0}}const Io=e=>e.id==null?e.flags&2?-1:1/0:e.id;function fy(e){try{for(Vr=0;Vr<It.length;Vr++){const t=It[Vr];t&&!(t.flags&8)&&(t.flags&4&&(t.flags&=-2),Os(t,t.i,t.i?15:14),t.flags&4||(t.flags&=-2))}}finally{for(;Vr<It.length;Vr++){const t=It[Vr];t&&(t.flags&=-2)}Vr=-1,It.length=0,Cl(),Sl=null,(It.length||is.length)&&fy()}}let Qi,Na=[];function py(e,t){var r,n;Qi=e,Qi?(Qi.enabled=!0,Na.forEach(({event:i,args:s})=>Qi.emit(i,...s)),Na=[]):typeof window<"u"&&window.HTMLElement&&!((n=(r=window.navigator)==null?void 0:r.userAgent)!=null&&n.includes("jsdom"))?((t.__VUE_DEVTOOLS_HOOK_REPLAY__=t.__VUE_DEVTOOLS_HOOK_REPLAY__||[]).push(s=>{py(s,t)}),setTimeout(()=>{Qi||(t.__VUE_DEVTOOLS_HOOK_REPLAY__=null,Na=[])},3e3)):Na=[]}let _t=null,xc=null;function Lo(e){const t=_t;return _t=e,xc=e&&e.type.__scopeId||null,t}function _x(e){xc=e}function wx(){xc=null}const Ex=e=>gf;function gf(e,t=_t,r){if(!t||e._n)return e;const n=(...i)=>{n._d&&Do(-1);const s=Lo(t);let o;try{o=e(...i)}finally{Lo(s),n._d&&Do(1)}return o};return n._n=!0,n._c=!0,n._d=!0,n}function xx(e,t){if(_t===null)return e;const r=oa(_t),n=e.dirs||(e.dirs=[]);for(let i=0;i<t.length;i++){let[s,o,a,l=Ee]=t[i];s&&(ce(s)&&(s={mounted:s,updated:s}),s.deep&&dn(o),n.push({dir:s,instance:r,value:o,oldValue:void 0,arg:a,modifiers:l}))}return e}function Ur(e,t,r,n){const i=e.dirs,s=t&&t.dirs;for(let o=0;o<i.length;o++){const a=i[o];s&&(a.oldValue=s[o].value);let l=a.dir[n];l&&(_n(),pr(l,r,8,[e.el,a,e,t]),wn())}}function my(e,t){if(yt){let r=yt.provides;const n=yt.parent&&yt.parent.provides;n===r&&(r=yt.provides=Object.create(n)),r[e]=t}}function po(e,t,r=!1){const n=zt();if(n||xi){let i=xi?xi._context.provides:n?n.parent==null||n.ce?n.vnode.appContext&&n.vnode.appContext.provides:n.parent.provides:void 0;if(i&&e in i)return i[e];if(arguments.length>1)return r&&ce(t)?t.call(n&&n.proxy):t}}function Sx(){return!!(zt()||xi)}const gy=Symbol.for("v-scx"),by=()=>po(gy);function Cx(e,t){return na(e,null,t)}function Ax(e,t){return na(e,null,{flush:"post"})}function vy(e,t){return na(e,null,{flush:"sync"})}function Ei(e,t,r){return na(e,t,r)}function na(e,t,r=Ee){const{immediate:n,deep:i,flush:s,once:o}=r,a=gt({},r),l=t&&n||!t&&s!=="post";let u;if(ds){if(s==="sync"){const m=by();u=m.__watcherHandles||(m.__watcherHandles=[])}else if(!l){const m=()=>{};return m.stop=jr,m.resume=jr,m.pause=jr,m}}const c=yt;a.call=(m,h,f)=>pr(m,c,h,f);let d=!1;s==="post"?a.scheduler=m=>{Qe(m,c&&c.suspense)}:s!=="sync"&&(d=!0,a.scheduler=(m,h)=>{h?m():mf(m)}),a.augmentJob=m=>{t&&(m.flags|=4),d&&(m.flags|=2,c&&(m.id=c.uid,m.i=c))};const p=Y1(e,t,a);return ds&&(u?u.push(p):l&&p()),p}function Tx(e,t,r){const n=this.proxy,i=tt(e)?e.includes(".")?yy(n,e):()=>n[e]:e.bind(n,n);let s;ce(t)?s=t:(s=t.handler,r=t);const o=Ni(this),a=na(i,s.bind(n),r);return o(),a}function yy(e,t){const r=t.split(".");return()=>{let n=e;for(let i=0;i<r.length&&n;i++)n=n[r[i]];return n}}const _y=Symbol("_vte"),wy=e=>e.__isTeleport,mo=e=>e&&(e.disabled||e.disabled===""),Zp=e=>e&&(e.defer||e.defer===""),em=e=>typeof SVGElement<"u"&&e instanceof SVGElement,tm=e=>typeof MathMLElement=="function"&&e instanceof MathMLElement,rh=(e,t)=>{const r=e&&e.to;return tt(r)?t?t(r):null:r},Ey={name:"Teleport",__isTeleport:!0,process(e,t,r,n,i,s,o,a,l,u){const{mc:c,pc:d,pbc:p,o:{insert:m,querySelector:h,createText:f,createComment:b}}=u,y=mo(t.props);let{shapeFlag:w,children:v,dynamicChildren:_}=t;if(e==null){const x=t.el=f(""),k=t.anchor=f("");m(x,r,n),m(k,r,n);const S=(A,T)=>{w&16&&c(v,A,T,i,s,o,a,l)},N=()=>{const A=t.target=rh(t.props,h),T=xy(A,t,f,m);A&&(o!=="svg"&&em(A)?o="svg":o!=="mathml"&&tm(A)&&(o="mathml"),i&&i.isCE&&(i.ce._teleportTargets||(i.ce._teleportTargets=new Set)).add(A),y||(S(A,T),tl(t,!1)))};y&&(S(r,k),tl(t,!0)),Zp(t.props)?(t.el.__isMounted=!1,Qe(()=>{N(),delete t.el.__isMounted},s)):N()}else{if(Zp(t.props)&&e.el.__isMounted===!1){Qe(()=>{Ey.process(e,t,r,n,i,s,o,a,l,u)},s);return}t.el=e.el,t.targetStart=e.targetStart;const x=t.anchor=e.anchor,k=t.target=e.target,S=t.targetAnchor=e.targetAnchor,N=mo(e.props),A=N?r:k,T=N?x:S;if(o==="svg"||em(k)?o="svg":(o==="mathml"||tm(k))&&(o="mathml"),_?(p(e.dynamicChildren,_,A,i,s,o,a),Tf(e,t,!0)):l||d(e,t,A,T,i,s,o,a,!1),y)N?t.props&&e.props&&t.props.to!==e.props.to&&(t.props.to=e.props.to):Pa(t,r,x,u,1);else if((t.props&&t.props.to)!==(e.props&&e.props.to)){const F=t.target=rh(t.props,h);F&&Pa(t,F,null,u,0)}else N&&Pa(t,k,S,u,1);tl(t,y)}},remove(e,t,r,{um:n,o:{remove:i}},s){const{shapeFlag:o,children:a,anchor:l,targetStart:u,targetAnchor:c,target:d,props:p}=e;if(d&&(i(u),i(c)),s&&i(l),o&16){const m=s||!mo(p);for(let h=0;h<a.length;h++){const f=a[h];n(f,t,r,m,!!f.dynamicChildren)}}},move:Pa,hydrate:kx};function Pa(e,t,r,{o:{insert:n},m:i},s=2){s===0&&n(e.targetAnchor,t,r);const{el:o,anchor:a,shapeFlag:l,children:u,props:c}=e,d=s===2;if(d&&n(o,t,r),(!d||mo(c))&&l&16)for(let p=0;p<u.length;p++)i(u[p],t,r,2);d&&n(a,t,r)}function kx(e,t,r,n,i,s,{o:{nextSibling:o,parentNode:a,querySelector:l,insert:u,createText:c}},d){function p(f,b,y,w){b.anchor=d(o(f),b,a(f),r,n,i,s),b.targetStart=y,b.targetAnchor=w}const m=t.target=rh(t.props,l),h=mo(t.props);if(m){const f=m._lpa||m.firstChild;if(t.shapeFlag&16)if(h)p(e,t,f,f&&o(f));else{t.anchor=o(e);let b=f;for(;b;){if(b&&b.nodeType===8){if(b.data==="teleport start anchor")t.targetStart=b;else if(b.data==="teleport anchor"){t.targetAnchor=b,m._lpa=t.targetAnchor&&o(t.targetAnchor);break}}b=o(b)}t.targetAnchor||xy(m,t,c,u),d(f&&o(f),t,m,r,n,i,s)}tl(t,h)}else h&&t.shapeFlag&16&&p(e,t,e,o(e));return t.anchor&&o(t.anchor)}const Ox=Ey;function tl(e,t){const r=e.ctx;if(r&&r.ut){let n,i;for(t?(n=e.el,i=e.anchor):(n=e.targetStart,i=e.targetAnchor);n&&n!==i;)n.nodeType===1&&n.setAttribute("data-v-owner",r.uid),n=n.nextSibling;r.ut()}}function xy(e,t,r,n){const i=t.targetStart=r(""),s=t.targetAnchor=r("");return i[_y]=s,e&&(n(i,e),n(s,e)),s}const ln=Symbol("_leaveCb"),Fa=Symbol("_enterCb");function bf(){const e={isMounted:!1,isLeaving:!1,isUnmounting:!1,leavingVNodes:new Map};return Ns(()=>{e.isMounted=!0}),Tc(()=>{e.isUnmounting=!0}),e}const cr=[Function,Array],vf={mode:String,appear:Boolean,persisted:Boolean,onBeforeEnter:cr,onEnter:cr,onAfterEnter:cr,onEnterCancelled:cr,onBeforeLeave:cr,onLeave:cr,onAfterLeave:cr,onLeaveCancelled:cr,onBeforeAppear:cr,onAppear:cr,onAfterAppear:cr,onAppearCancelled:cr},Sy=e=>{const t=e.subTree;return t.component?Sy(t.component):t},Nx={name:"BaseTransition",props:vf,setup(e,{slots:t}){const r=zt(),n=bf();return()=>{const i=t.default&&Sc(t.default(),!0);if(!i||!i.length)return;const s=Cy(i),o=ye(e),{mode:a}=o;if(n.isLeaving)return mu(s);const l=rm(s);if(!l)return mu(s);let u=us(l,o,n,r,d=>u=d);l.type!==Ye&&En(l,u);let c=r.subTree&&rm(r.subTree);if(c&&c.type!==Ye&&!Sr(c,l)&&Sy(r).type!==Ye){let d=us(c,o,n,r);if(En(c,d),a==="out-in"&&l.type!==Ye)return n.isLeaving=!0,d.afterLeave=()=>{n.isLeaving=!1,r.job.flags&8||r.update(),delete d.afterLeave,c=void 0},mu(s);a==="in-out"&&l.type!==Ye?d.delayLeave=(p,m,h)=>{const f=Ty(n,c);f[String(c.key)]=c,p[ln]=()=>{m(),p[ln]=void 0,delete u.delayedLeave,c=void 0},u.delayedLeave=()=>{h(),delete u.delayedLeave,c=void 0}}:c=void 0}else c&&(c=void 0);return s}}};function Cy(e){let t=e[0];if(e.length>1){for(const r of e)if(r.type!==Ye){t=r;break}}return t}const Ay=Nx;function Ty(e,t){const{leavingVNodes:r}=e;let n=r.get(t.type);return n||(n=Object.create(null),r.set(t.type,n)),n}function us(e,t,r,n,i){const{appear:s,mode:o,persisted:a=!1,onBeforeEnter:l,onEnter:u,onAfterEnter:c,onEnterCancelled:d,onBeforeLeave:p,onLeave:m,onAfterLeave:h,onLeaveCancelled:f,onBeforeAppear:b,onAppear:y,onAfterAppear:w,onAppearCancelled:v}=t,_=String(e.key),x=Ty(r,e),k=(A,T)=>{A&&pr(A,n,9,T)},S=(A,T)=>{const F=T[1];k(A,T),de(A)?A.every(C=>C.length<=1)&&F():A.length<=1&&F()},N={mode:o,persisted:a,beforeEnter(A){let T=l;if(!r.isMounted)if(s)T=b||l;else return;A[ln]&&A[ln](!0);const F=x[_];F&&Sr(e,F)&&F.el[ln]&&F.el[ln](),k(T,[A])},enter(A){let T=u,F=c,C=d;if(!r.isMounted)if(s)T=y||u,F=w||c,C=v||d;else return;let g=!1;const L=A[Fa]=B=>{g||(g=!0,B?k(C,[A]):k(F,[A]),N.delayedLeave&&N.delayedLeave(),A[Fa]=void 0)};T?S(T,[A,L]):L()},leave(A,T){const F=String(e.key);if(A[Fa]&&A[Fa](!0),r.isUnmounting)return T();k(p,[A]);let C=!1;const g=A[ln]=L=>{C||(C=!0,T(),L?k(f,[A]):k(h,[A]),A[ln]=void 0,x[F]===e&&delete x[F])};x[F]=e,m?S(m,[A,g]):g()},clone(A){const T=us(A,t,r,n,i);return i&&i(T),T}};return N}function mu(e){if(ia(e))return e=Xr(e),e.children=null,e}function rm(e){if(!ia(e))return wy(e.type)&&e.children?Cy(e.children):e;if(e.component)return e.component.subTree;const{shapeFlag:t,children:r}=e;if(r){if(t&16)return r[0];if(t&32&&ce(r.default))return r.default()}}function En(e,t){e.shapeFlag&6&&e.component?(e.transition=t,En(e.component.subTree,t)):e.shapeFlag&128?(e.ssContent.transition=t.clone(e.ssContent),e.ssFallback.transition=t.clone(e.ssFallback)):e.transition=t}function Sc(e,t=!1,r){let n=[],i=0;for(let s=0;s<e.length;s++){let o=e[s];const a=r==null?o.key:String(r)+String(o.key!=null?o.key:s);o.type===st?(o.patchFlag&128&&i++,n=n.concat(Sc(o.children,t,a))):(t||o.type!==Ye)&&n.push(a!=null?Xr(o,{key:a}):o)}if(i>1)for(let s=0;s<n.length;s++)n[s].patchFlag=-2;return n}function Mi(e,t){return ce(e)?gt({name:e.name},t,{setup:e}):e}function Px(){const e=zt();return e?(e.appContext.config.idPrefix||"v")+"-"+e.ids[0]+e.ids[1]++:""}function yf(e){e.ids=[e.ids[0]+e.ids[2]+++"-",0,0]}function Fx(e){const t=zt(),r=df(null);if(t){const i=t.refs===Ee?t.refs={}:t.refs;Object.defineProperty(i,e,{enumerable:!0,get:()=>r.value,set:s=>r.value=s})}return r}const Al=new WeakMap;function ss(e,t,r,n,i=!1){if(de(e)){e.forEach((h,f)=>ss(h,t&&(de(t)?t[f]:t),r,n,i));return}if(gn(n)&&!i){n.shapeFlag&512&&n.type.__asyncResolved&&n.component.subTree.component&&ss(e,t,r,n.component.subTree);return}const s=n.shapeFlag&4?oa(n.component):n.el,o=i?null:s,{i:a,r:l}=e,u=t&&t.r,c=a.refs===Ee?a.refs={}:a.refs,d=a.setupState,p=ye(d),m=d===Ee?ny:h=>Le(p,h);if(u!=null&&u!==l){if(nm(t),tt(u))c[u]=null,m(u)&&(d[u]=null);else if(et(u)){u.value=null;const h=t;h.k&&(c[h.k]=null)}}if(ce(l))Os(l,a,12,[o,c]);else{const h=tt(l),f=et(l);if(h||f){const b=()=>{if(e.f){const y=h?m(l)?d[l]:c[l]:l.value;if(i)de(y)&&sy(y,s);else if(de(y))y.includes(s)||y.push(s);else if(h)c[l]=[s],m(l)&&(d[l]=c[l]);else{const w=[s];l.value=w,e.k&&(c[e.k]=w)}}else h?(c[l]=o,m(l)&&(d[l]=o)):f&&(l.value=o,e.k&&(c[e.k]=o))};if(o){const y=()=>{b(),Al.delete(e)};y.id=-1,Al.set(e,y),Qe(y,r)}else nm(e),b()}}}function nm(e){const t=Al.get(e);t&&(t.flags|=8,Al.delete(e))}let im=!1;const ji=()=>{im||(console.error("Hydration completed but contains mismatches."),im=!0)},Ix=e=>e.namespaceURI.includes("svg")&&e.tagName!=="foreignObject",Lx=e=>e.namespaceURI.includes("MathML"),Ia=e=>{if(e.nodeType===1){if(Ix(e))return"svg";if(Lx(e))return"mathml"}},ts=e=>e.nodeType===8;function Rx(e){const{mt:t,p:r,o:{patchProp:n,createText:i,nextSibling:s,parentNode:o,remove:a,insert:l,createComment:u}}=e,c=(v,_)=>{if(!_.hasChildNodes()){r(null,v,_),Cl(),_._vnode=v;return}d(_.firstChild,v,null,null,null),Cl(),_._vnode=v},d=(v,_,x,k,S,N=!1)=>{N=N||!!_.dynamicChildren;const A=ts(v)&&v.data==="[",T=()=>f(v,_,x,k,S,A),{type:F,ref:C,shapeFlag:g,patchFlag:L}=_;let B=v.nodeType;_.el=v,L===-2&&(N=!1,_.dynamicChildren=null);let $=null;switch(F){case Xn:B!==3?_.children===""?(l(_.el=i(""),o(v),v),$=v):$=T():(v.data!==_.children&&(ji(),v.data=_.children),$=s(v));break;case Ye:w(v)?($=s(v),y(_.el=v.content.firstChild,v,x)):B!==8||A?$=T():$=s(v);break;case Si:if(A&&(v=s(v),B=v.nodeType),B===1||B===3){$=v;const z=!_.children.length;for(let P=0;P<_.staticCount;P++)z&&(_.children+=$.nodeType===1?$.outerHTML:$.data),P===_.staticCount-1&&(_.anchor=$),$=s($);return A?s($):$}else T();break;case st:A?$=h(v,_,x,k,S,N):$=T();break;default:if(g&1)(B!==1||_.type.toLowerCase()!==v.tagName.toLowerCase())&&!w(v)?$=T():$=p(v,_,x,k,S,N);else if(g&6){_.slotScopeIds=S;const z=o(v);if(A?$=b(v):ts(v)&&v.data==="teleport start"?$=b(v,v.data,"teleport end"):$=s(v),t(_,z,null,x,k,Ia(z),N),gn(_)&&!_.type.__asyncResolved){let P;A?(P=ze(st),P.anchor=$?$.previousSibling:z.lastChild):P=v.nodeType===3?Of(""):ze("div"),P.el=v,_.component.subTree=P}}else g&64?B!==8?$=T():$=_.type.hydrate(v,_,x,k,S,N,e,m):g&128&&($=_.type.hydrate(v,_,x,k,Ia(o(v)),S,N,e,d))}return C!=null&&ss(C,null,k,_),$},p=(v,_,x,k,S,N)=>{N=N||!!_.dynamicChildren;const{type:A,props:T,patchFlag:F,shapeFlag:C,dirs:g,transition:L}=_,B=A==="input"||A==="option";if(B||F!==-1){g&&Ur(_,null,x,"created");let $=!1;if(w(v)){$=Zy(null,L)&&x&&x.vnode.props&&x.vnode.props.appear;const P=v.content.firstChild;if($){const Z=P.getAttribute("class");Z&&(P.$cls=Z),L.beforeEnter(P)}y(P,v,x),_.el=v=P}if(C&16&&!(T&&(T.innerHTML||T.textContent))){let P=m(v.firstChild,_,v,x,k,S,N);for(;P;){La(v,1)||ji();const Z=P;P=P.nextSibling,a(Z)}}else if(C&8){let P=_.children;P[0]===`
`&&(v.tagName==="PRE"||v.tagName==="TEXTAREA")&&(P=P.slice(1));const{textContent:Z}=v;Z!==P&&Z!==P.replace(/\r\n|\r/g,`
`)&&(La(v,0)||ji(),v.textContent=_.children)}if(T){if(B||!N||F&48){const P=v.tagName.includes("-");for(const Z in T)(B&&(Z.endsWith("value")||Z==="indeterminate")||bc(Z)&&!wi(Z)||Z[0]==="."||P&&!wi(Z))&&n(v,Z,null,T[Z],void 0,x)}else if(T.onClick)n(v,"onClick",null,T.onClick,void 0,x);else if(F&4&&mn(T.style))for(const P in T.style)T.style[P]}let z;(z=T&&T.onVnodeBeforeMount)&&$t(z,x,_),g&&Ur(_,null,x,"beforeMount"),((z=T&&T.onVnodeMounted)||g||$)&&n_(()=>{z&&$t(z,x,_),$&&L.enter(v),g&&Ur(_,null,x,"mounted")},k)}return v.nextSibling},m=(v,_,x,k,S,N,A)=>{A=A||!!_.dynamicChildren;const T=_.children,F=T.length;for(let C=0;C<F;C++){const g=A?T[C]:T[C]=Mt(T[C]),L=g.type===Xn;v?(L&&!A&&C+1<F&&Mt(T[C+1]).type===Xn&&(l(i(v.data.slice(g.children.length)),x,s(v)),v.data=g.children),v=d(v,g,k,S,N,A)):L&&!g.children?l(g.el=i(""),x):(La(x,1)||ji(),r(null,g,x,null,k,S,Ia(x),N))}return v},h=(v,_,x,k,S,N)=>{const{slotScopeIds:A}=_;A&&(S=S?S.concat(A):A);const T=o(v),F=m(s(v),_,T,x,k,S,N);return F&&ts(F)&&F.data==="]"?s(_.anchor=F):(ji(),l(_.anchor=u("]"),T,F),F)},f=(v,_,x,k,S,N)=>{if(La(v.parentElement,1)||ji(),_.el=null,N){const F=b(v);for(;;){const C=s(v);if(C&&C!==F)a(C);else break}}const A=s(v),T=o(v);return a(v),r(null,_,T,A,x,k,Ia(T),S),x&&(x.vnode.el=_.el,Oc(x,_.el)),A},b=(v,_="[",x="]")=>{let k=0;for(;v;)if(v=s(v),v&&ts(v)&&(v.data===_&&k++,v.data===x)){if(k===0)return s(v);k--}return v},y=(v,_,x)=>{const k=_.parentNode;k&&k.replaceChild(v,_);let S=x;for(;S;)S.vnode.el===_&&(S.vnode.el=S.subTree.el=v),S=S.parent},w=v=>v.nodeType===1&&v.tagName==="TEMPLATE";return[c,d]}const sm="data-allow-mismatch",$x={0:"text",1:"children",2:"class",3:"style",4:"attribute"};function La(e,t){if(t===0||t===1)for(;e&&!e.hasAttribute(sm);)e=e.parentElement;const r=e&&e.getAttribute(sm);if(r==null)return!1;if(r==="")return!0;{const n=r.split(",");return t===0&&n.includes("children")?!0:n.includes($x[t])}}const Mx=wc().requestIdleCallback||(e=>setTimeout(e,1)),Dx=wc().cancelIdleCallback||(e=>clearTimeout(e)),Vx=(e=1e4)=>t=>{const r=Mx(t,{timeout:e});return()=>Dx(r)};function Bx(e){const{top:t,left:r,bottom:n,right:i}=e.getBoundingClientRect(),{innerHeight:s,innerWidth:o}=window;return(t>0&&t<s||n>0&&n<s)&&(r>0&&r<o||i>0&&i<o)}const Ux=e=>(t,r)=>{const n=new IntersectionObserver(i=>{for(const s of i)if(s.isIntersecting){n.disconnect(),t();break}},e);return r(i=>{if(i instanceof Element){if(Bx(i))return t(),n.disconnect(),!1;n.observe(i)}}),()=>n.disconnect()},zx=e=>t=>{if(e){const r=matchMedia(e);if(r.matches)t();else return r.addEventListener("change",t,{once:!0}),()=>r.removeEventListener("change",t)}},qx=(e=[])=>(t,r)=>{tt(e)&&(e=[e]);let n=!1;const i=o=>{n||(n=!0,s(),t(),o.target.dispatchEvent(new o.constructor(o.type,o)))},s=()=>{r(o=>{for(const a of e)o.removeEventListener(a,i)})};return r(o=>{for(const a of e)o.addEventListener(a,i,{once:!0})}),s};function Hx(e,t){if(ts(e)&&e.data==="["){let r=1,n=e.nextSibling;for(;n;){if(n.nodeType===1){if(t(n)===!1)break}else if(ts(n))if(n.data==="]"){if(--r===0)break}else n.data==="["&&r++;n=n.nextSibling}}else t(e)}const gn=e=>!!e.type.__asyncLoader;function jx(e){ce(e)&&(e={loader:e});const{loader:t,loadingComponent:r,errorComponent:n,delay:i=200,hydrate:s,timeout:o,suspensible:a=!0,onError:l}=e;let u=null,c,d=0;const p=()=>(d++,u=null,m()),m=()=>{let h;return u||(h=u=t().catch(f=>{if(f=f instanceof Error?f:new Error(String(f)),l)return new Promise((b,y)=>{l(f,()=>b(p()),()=>y(f),d+1)});throw f}).then(f=>h!==u&&u?u:(f&&(f.__esModule||f[Symbol.toStringTag]==="Module")&&(f=f.default),c=f,f)))};return Mi({name:"AsyncComponentWrapper",__asyncLoader:m,__asyncHydrate(h,f,b){let y=!1;(f.bu||(f.bu=[])).push(()=>y=!0);const w=()=>{y||b()},v=s?()=>{const _=s(w,x=>Hx(h,x));_&&(f.bum||(f.bum=[])).push(_)}:w;c?v():m().then(()=>!f.isUnmounted&&v())},get __asyncResolved(){return c},setup(){const h=yt;if(yf(h),c)return()=>Ra(c,h);const f=v=>{u=null,$i(v,h,13,!n)};if(a&&h.suspense||ds)return m().then(v=>()=>Ra(v,h)).catch(v=>(f(v),()=>n?ze(n,{error:v}):null));const b=Hr(!1),y=Hr(),w=Hr(!!i);return i&&setTimeout(()=>{w.value=!1},i),o!=null&&setTimeout(()=>{if(!b.value&&!y.value){const v=new Error(`Async component timed out after ${o}ms.`);f(v),y.value=v}},o),m().then(()=>{b.value=!0,h.parent&&ia(h.parent.vnode)&&h.parent.update()}).catch(v=>{f(v),y.value=v}),()=>{if(b.value&&c)return Ra(c,h);if(y.value&&n)return ze(n,{error:y.value});if(r&&!w.value)return Ra(r,h)}}})}function Ra(e,t){const{ref:r,props:n,children:i,ce:s}=t.vnode,o=ze(e,n,i);return o.ref=r,o.ce=s,delete t.vnode.ce,o}const ia=e=>e.type.__isKeepAlive,Wx={name:"KeepAlive",__isKeepAlive:!0,props:{include:[String,RegExp,Array],exclude:[String,RegExp,Array],max:[String,Number]},setup(e,{slots:t}){const r=zt(),n=r.ctx;if(!n.renderer)return()=>{const w=t.default&&t.default();return w&&w.length===1?w[0]:w};const i=new Map,s=new Set;let o=null;const a=r.suspense,{renderer:{p:l,m:u,um:c,o:{createElement:d}}}=n,p=d("div");n.activate=(w,v,_,x,k)=>{const S=w.component;u(w,v,_,0,a),l(S.vnode,w,v,_,S,a,x,w.slotScopeIds,k),Qe(()=>{S.isDeactivated=!1,S.a&&fo(S.a);const N=w.props&&w.props.onVnodeMounted;N&&$t(N,S.parent,w)},a)},n.deactivate=w=>{const v=w.component;kl(v.m),kl(v.a),u(w,p,null,1,a),Qe(()=>{v.da&&fo(v.da);const _=w.props&&w.props.onVnodeUnmounted;_&&$t(_,v.parent,w),v.isDeactivated=!0},a)};function m(w){gu(w),c(w,r,a,!0)}function h(w){i.forEach((v,_)=>{const x=fh(gn(v)?v.type.__asyncResolved||{}:v.type);x&&!w(x)&&f(_)})}function f(w){const v=i.get(w);v&&(!o||!Sr(v,o))?m(v):o&&gu(o),i.delete(w),s.delete(w)}Ei(()=>[e.include,e.exclude],([w,v])=>{w&&h(_=>Zs(w,_)),v&&h(_=>!Zs(v,_))},{flush:"post",deep:!0});let b=null;const y=()=>{b!=null&&(Ol(r.subTree.type)?Qe(()=>{i.set(b,$a(r.subTree))},r.subTree.suspense):i.set(b,$a(r.subTree)))};return Ns(y),Ac(y),Tc(()=>{i.forEach(w=>{const{subTree:v,suspense:_}=r,x=$a(v);if(w.type===x.type&&w.key===x.key){gu(x);const k=x.component.da;k&&Qe(k,_);return}m(w)})}),()=>{if(b=null,!t.default)return o=null;const w=t.default(),v=w[0];if(w.length>1)return o=null,w;if(!xn(v)||!(v.shapeFlag&4)&&!(v.shapeFlag&128))return o=null,v;let _=$a(v);if(_.type===Ye)return o=null,_;const x=_.type,k=fh(gn(_)?_.type.__asyncResolved||{}:x),{include:S,exclude:N,max:A}=e;if(S&&(!k||!Zs(S,k))||N&&k&&Zs(N,k))return _.shapeFlag&=-257,o=_,v;const T=_.key==null?x:_.key,F=i.get(T);return _.el&&(_=Xr(_),v.shapeFlag&128&&(v.ssContent=_)),b=T,F?(_.el=F.el,_.component=F.component,_.transition&&En(_,_.transition),_.shapeFlag|=512,s.delete(T),s.add(T)):(s.add(T),A&&s.size>parseInt(A,10)&&f(s.values().next().value)),_.shapeFlag|=256,o=_,Ol(v.type)?v:_}}},Kx=Wx;function Zs(e,t){return de(e)?e.some(r=>Zs(r,t)):tt(e)?e.split(",").includes(t):Z1(e)?(e.lastIndex=0,e.test(t)):!1}function ky(e,t){Ny(e,"a",t)}function Oy(e,t){Ny(e,"da",t)}function Ny(e,t,r=yt){const n=e.__wdc||(e.__wdc=()=>{let i=r;for(;i;){if(i.isDeactivated)return;i=i.parent}return e()});if(Cc(t,n,r),r){let i=r.parent;for(;i&&i.parent;)ia(i.parent.vnode)&&Gx(n,t,r,i),i=i.parent}}function Gx(e,t,r,n){const i=Cc(t,e,n,!0);sa(()=>{sy(n[t],i)},r)}function gu(e){e.shapeFlag&=-257,e.shapeFlag&=-513}function $a(e){return e.shapeFlag&128?e.ssContent:e}function Cc(e,t,r=yt,n=!1){if(r){const i=r[e]||(r[e]=[]),s=t.__weh||(t.__weh=(...o)=>{_n();const a=Ni(r),l=pr(t,r,e,o);return a(),wn(),l});return n?i.unshift(s):i.push(s),s}}const Sn=e=>(t,r=yt)=>{(!ds||e==="sp")&&Cc(e,(...n)=>t(...n),r)},Py=Sn("bm"),Ns=Sn("m"),_f=Sn("bu"),Ac=Sn("u"),Tc=Sn("bum"),sa=Sn("um"),Fy=Sn("sp"),Iy=Sn("rtg"),Ly=Sn("rtc");function Ry(e,t=yt){Cc("ec",e,t)}const wf="components",Yx="directives";function Xx(e,t){return Ef(wf,e,!0,t)||e}const $y=Symbol.for("v-ndc");function Jx(e){return tt(e)?Ef(wf,e,!1)||e:e||$y}function Qx(e){return Ef(Yx,e)}function Ef(e,t,r=!0,n=!1){const i=_t||yt;if(i){const s=i.type;if(e===wf){const a=fh(s,!1);if(a&&(a===t||a===rr(t)||a===_c(rr(t))))return s}const o=om(i[e]||s[e],t)||om(i.appContext[e],t);return!o&&n?s:o}}function om(e,t){return e&&(e[t]||e[rr(t)]||e[_c(rr(t))])}function Zx(e,t,r,n){let i;const s=r&&r[n],o=de(e);if(o||tt(e)){const a=o&&mn(e);let l=!1,u=!1;a&&(l=!Ut(e),u=Yr(e),e=pc(e)),i=new Array(e.length);for(let c=0,d=e.length;c<d;c++)i[c]=t(l?u?cs(Or(e[c])):Or(e[c]):e[c],c,void 0,s&&s[c])}else if(typeof e=="number"){i=new Array(e);for(let a=0;a<e;a++)i[a]=t(a+1,a,void 0,s&&s[a])}else if(lt(e))if(e[Symbol.iterator])i=Array.from(e,(a,l)=>t(a,l,void 0,s&&s[l]));else{const a=Object.keys(e);i=new Array(a.length);for(let l=0,u=a.length;l<u;l++){const c=a[l];i[l]=t(e[c],c,l,s&&s[l])}}else i=[];return r&&(r[n]=i),i}function eS(e,t){for(let r=0;r<t.length;r++){const n=t[r];if(de(n))for(let i=0;i<n.length;i++)e[n[i].name]=n[i].fn;else n&&(e[n.name]=n.key?(...i)=>{const s=n.fn(...i);return s&&(s.key=n.key),s}:n.fn)}return e}function tS(e,t,r={},n,i){if(_t.ce||_t.parent&&gn(_t.parent)&&_t.parent.ce){const u=Object.keys(r).length>0;return t!=="default"&&(r.name=t),Mo(),Nl(st,null,[ze("slot",r,n&&n())],u?-2:64)}let s=e[t];s&&s._c&&(s._d=!1),Mo();const o=s&&xf(s(r)),a=r.key||o&&o.key,l=Nl(st,{key:(a&&!ff(a)?a:`_${t}`)+(!o&&n?"_fb":"")},o||(n?n():[]),o&&e._===1?64:-2);return!i&&l.scopeId&&(l.slotScopeIds=[l.scopeId+"-s"]),s&&s._c&&(s._d=!0),l}function xf(e){return e.some(t=>xn(t)?!(t.type===Ye||t.type===st&&!xf(t.children)):!0)?e:null}function rS(e,t){const r={};for(const n in e)r[t&&/[A-Z]/.test(n)?`on:${n}`:ho(n)]=e[n];return r}const nh=e=>e?u_(e)?oa(e):nh(e.parent):null,go=gt(Object.create(null),{$:e=>e,$el:e=>e.vnode.el,$data:e=>e.data,$props:e=>e.props,$attrs:e=>e.attrs,$slots:e=>e.slots,$refs:e=>e.refs,$parent:e=>nh(e.parent),$root:e=>nh(e.root),$host:e=>e.ce,$emit:e=>e.emit,$options:e=>Sf(e),$forceUpdate:e=>e.f||(e.f=()=>{mf(e.update)}),$nextTick:e=>e.n||(e.n=Ec.bind(e.proxy)),$watch:e=>Tx.bind(e)}),bu=(e,t)=>e!==Ee&&!e.__isScriptSetup&&Le(e,t),ih={get({_:e},t){if(t==="__v_skip")return!0;const{ctx:r,setupState:n,data:i,props:s,accessCache:o,type:a,appContext:l}=e;if(t[0]!=="$"){const p=o[t];if(p!==void 0)switch(p){case 1:return n[t];case 2:return i[t];case 4:return r[t];case 3:return s[t]}else{if(bu(n,t))return o[t]=1,n[t];if(i!==Ee&&Le(i,t))return o[t]=2,i[t];if(Le(s,t))return o[t]=3,s[t];if(r!==Ee&&Le(r,t))return o[t]=4,r[t];sh&&(o[t]=0)}}const u=go[t];let c,d;if(u)return t==="$attrs"&&Ct(e.attrs,"get",""),u(e);if((c=a.__cssModules)&&(c=c[t]))return c;if(r!==Ee&&Le(r,t))return o[t]=4,r[t];if(d=l.config.globalProperties,Le(d,t))return d[t]},set({_:e},t,r){const{data:n,setupState:i,ctx:s}=e;return bu(i,t)?(i[t]=r,!0):n!==Ee&&Le(n,t)?(n[t]=r,!0):Le(e.props,t)||t[0]==="$"&&t.slice(1)in e?!1:(s[t]=r,!0)},has({_:{data:e,setupState:t,accessCache:r,ctx:n,appContext:i,props:s,type:o}},a){let l;return!!(r[a]||e!==Ee&&a[0]!=="$"&&Le(e,a)||bu(t,a)||Le(s,a)||Le(n,a)||Le(go,a)||Le(i.config.globalProperties,a)||(l=o.__cssModules)&&l[a])},defineProperty(e,t,r){return r.get!=null?e._.accessCache[t]=0:Le(r,"value")&&this.set(e,t,r.value,null),Reflect.defineProperty(e,t,r)}},nS=gt({},ih,{get(e,t){if(t!==Symbol.unscopables)return ih.get(e,t,e)},has(e,t){return t[0]!=="_"&&!ax(t)}});function iS(){return null}function sS(){return null}function oS(e){}function aS(e){}function lS(){return null}function cS(){}function uS(e,t){return null}function dS(){return My().slots}function hS(){return My().attrs}function My(e){const t=zt();return t.setupContext||(t.setupContext=p_(t))}function Ro(e){return de(e)?e.reduce((t,r)=>(t[r]=null,t),{}):e}function fS(e,t){const r=Ro(e);for(const n in t){if(n.startsWith("__skip"))continue;let i=r[n];i?de(i)||ce(i)?i=r[n]={type:i,default:t[n]}:i.default=t[n]:i===null&&(i=r[n]={default:t[n]}),i&&t[`__skip_${n}`]&&(i.skipFactory=!0)}return r}function pS(e,t){return!e||!t?e||t:de(e)&&de(t)?e.concat(t):gt({},Ro(e),Ro(t))}function mS(e,t){const r={};for(const n in e)t.includes(n)||Object.defineProperty(r,n,{enumerable:!0,get:()=>e[n]});return r}function gS(e){const t=zt();let r=e();return uh(),pf(r)&&(r=r.catch(n=>{throw Ni(t),n})),[r,()=>Ni(t)]}let sh=!0;function bS(e){const t=Sf(e),r=e.proxy,n=e.ctx;sh=!1,t.beforeCreate&&am(t.beforeCreate,e,"bc");const{data:i,computed:s,methods:o,watch:a,provide:l,inject:u,created:c,beforeMount:d,mounted:p,beforeUpdate:m,updated:h,activated:f,deactivated:b,beforeDestroy:y,beforeUnmount:w,destroyed:v,unmounted:_,render:x,renderTracked:k,renderTriggered:S,errorCaptured:N,serverPrefetch:A,expose:T,inheritAttrs:F,components:C,directives:g,filters:L}=t;if(u&&vS(u,n,null),o)for(const z in o){const P=o[z];ce(P)&&(n[z]=P.bind(r))}if(i){const z=i.call(r,r);lt(z)&&(e.data=Ts(z))}if(sh=!0,s)for(const z in s){const P=s[z],Z=ce(P)?P.bind(r,r):ce(P.get)?P.get.bind(r,r):jr,be=!ce(P)&&ce(P.set)?P.set.bind(r):jr,le=De({get:Z,set:be});Object.defineProperty(n,z,{enumerable:!0,configurable:!0,get:()=>le.value,set:je=>le.value=je})}if(a)for(const z in a)Dy(a[z],n,r,z);if(l){const z=ce(l)?l.call(r):l;Reflect.ownKeys(z).forEach(P=>{my(P,z[P])})}c&&am(c,e,"c");function $(z,P){de(P)?P.forEach(Z=>z(Z.bind(r))):P&&z(P.bind(r))}if($(Py,d),$(Ns,p),$(_f,m),$(Ac,h),$(ky,f),$(Oy,b),$(Ry,N),$(Ly,k),$(Iy,S),$(Tc,w),$(sa,_),$(Fy,A),de(T))if(T.length){const z=e.exposed||(e.exposed={});T.forEach(P=>{Object.defineProperty(z,P,{get:()=>r[P],set:Z=>r[P]=Z,enumerable:!0})})}else e.exposed||(e.exposed={});x&&e.render===jr&&(e.render=x),F!=null&&(e.inheritAttrs=F),C&&(e.components=C),g&&(e.directives=g),A&&yf(e)}function vS(e,t,r=jr){de(e)&&(e=oh(e));for(const n in e){const i=e[n];let s;lt(i)?"default"in i?s=po(i.from||n,i.default,!0):s=po(i.from||n):s=po(i),et(s)?Object.defineProperty(t,n,{enumerable:!0,configurable:!0,get:()=>s.value,set:o=>s.value=o}):t[n]=s}}function am(e,t,r){pr(de(e)?e.map(n=>n.bind(t.proxy)):e.bind(t.proxy),t,r)}function Dy(e,t,r,n){let i=n.includes(".")?yy(r,n):()=>r[n];if(tt(e)){const s=t[e];ce(s)&&Ei(i,s)}else if(ce(e))Ei(i,e.bind(r));else if(lt(e))if(de(e))e.forEach(s=>Dy(s,t,r,n));else{const s=ce(e.handler)?e.handler.bind(r):t[e.handler];ce(s)&&Ei(i,s,e)}}function Sf(e){const t=e.type,{mixins:r,extends:n}=t,{mixins:i,optionsCache:s,config:{optionMergeStrategies:o}}=e.appContext,a=s.get(t);let l;return a?l=a:!i.length&&!r&&!n?l=t:(l={},i.length&&i.forEach(u=>Tl(l,u,o,!0)),Tl(l,t,o)),lt(t)&&s.set(t,l),l}function Tl(e,t,r,n=!1){const{mixins:i,extends:s}=t;s&&Tl(e,s,r,!0),i&&i.forEach(o=>Tl(e,o,r,!0));for(const o in t)if(!(n&&o==="expose")){const a=yS[o]||r&&r[o];e[o]=a?a(e[o],t[o]):t[o]}return e}const yS={data:lm,props:cm,emits:cm,methods:eo,computed:eo,beforeCreate:Ft,created:Ft,beforeMount:Ft,mounted:Ft,beforeUpdate:Ft,updated:Ft,beforeDestroy:Ft,beforeUnmount:Ft,destroyed:Ft,unmounted:Ft,activated:Ft,deactivated:Ft,errorCaptured:Ft,serverPrefetch:Ft,components:eo,directives:eo,watch:wS,provide:lm,inject:_S};function lm(e,t){return t?e?function(){return gt(ce(e)?e.call(this,this):e,ce(t)?t.call(this,this):t)}:t:e}function _S(e,t){return eo(oh(e),oh(t))}function oh(e){if(de(e)){const t={};for(let r=0;r<e.length;r++)t[e[r]]=e[r];return t}return e}function Ft(e,t){return e?[...new Set([].concat(e,t))]:t}function eo(e,t){return e?gt(Object.create(null),e,t):t}function cm(e,t){return e?de(e)&&de(t)?[...new Set([...e,...t])]:gt(Object.create(null),Ro(e),Ro(t??{})):t}function wS(e,t){if(!e)return t;if(!t)return e;const r=gt(Object.create(null),e);for(const n in t)r[n]=Ft(e[n],t[n]);return r}function Vy(){return{app:null,config:{isNativeTag:ny,performance:!1,globalProperties:{},optionMergeStrategies:{},errorHandler:void 0,warnHandler:void 0,compilerOptions:{}},mixins:[],components:{},directives:{},provides:Object.create(null),optionsCache:new WeakMap,propsCache:new WeakMap,emitsCache:new WeakMap}}let ES=0;function xS(e,t){return function(n,i=null){ce(n)||(n=gt({},n)),i!=null&&!lt(i)&&(i=null);const s=Vy(),o=new WeakSet,a=[];let l=!1;const u=s.app={_uid:ES++,_component:n,_props:i,_container:null,_context:s,_instance:null,version:g_,get config(){return s.config},set config(c){},use(c,...d){return o.has(c)||(c&&ce(c.install)?(o.add(c),c.install(u,...d)):ce(c)&&(o.add(c),c(u,...d))),u},mixin(c){return s.mixins.includes(c)||s.mixins.push(c),u},component(c,d){return d?(s.components[c]=d,u):s.components[c]},directive(c,d){return d?(s.directives[c]=d,u):s.directives[c]},mount(c,d,p){if(!l){const m=u._ceVNode||ze(n,i);return m.appContext=s,p===!0?p="svg":p===!1&&(p=void 0),d&&t?t(m,c):e(m,c,p),l=!0,u._container=c,c.__vue_app__=u,oa(m.component)}},onUnmount(c){a.push(c)},unmount(){l&&(pr(a,u._instance,16),e(null,u._container),delete u._container.__vue_app__)},provide(c,d){return s.provides[c]=d,u},runWithContext(c){const d=xi;xi=u;try{return c()}finally{xi=d}}};return u}}let xi=null;function SS(e,t,r=Ee){const n=zt(),i=rr(t),s=ks(t),o=By(e,i),a=Zv((l,u)=>{let c,d=Ee,p;return vy(()=>{const m=e[i];fi(c,m)&&(c=m,u())}),{get(){return l(),r.get?r.get(c):c},set(m){const h=r.set?r.set(m):m;if(!fi(h,c)&&!(d!==Ee&&fi(m,d)))return;const f=n.vnode.props;f&&(t in f||i in f||s in f)&&(`onUpdate:${t}`in f||`onUpdate:${i}`in f||`onUpdate:${s}`in f)||(c=m,u()),n.emit(`update:${t}`,h),fi(m,h)&&fi(m,d)&&!fi(h,p)&&u(),d=m,p=h}}});return a[Symbol.iterator]=()=>{let l=0;return{next(){return l<2?{value:l++?o||Ee:a,done:!1}:{done:!0}}}},a}const By=(e,t)=>t==="modelValue"||t==="model-value"?e.modelModifiers:e[`${t}Modifiers`]||e[`${rr(t)}Modifiers`]||e[`${ks(t)}Modifiers`];function CS(e,t,...r){if(e.isUnmounted)return;const n=e.vnode.props||Ee;let i=r;const s=t.startsWith("update:"),o=s&&By(n,t.slice(7));o&&(o.trim&&(i=r.map(c=>tt(c)?c.trim():c)),o.number&&(i=r.map(ix)));let a,l=n[a=ho(t)]||n[a=ho(rr(t))];!l&&s&&(l=n[a=ho(ks(t))]),l&&pr(l,e,6,i);const u=n[a+"Once"];if(u){if(!e.emitted)e.emitted={};else if(e.emitted[a])return;e.emitted[a]=!0,pr(u,e,6,i)}}const AS=new WeakMap;function Uy(e,t,r=!1){const n=r?AS:t.emitsCache,i=n.get(e);if(i!==void 0)return i;const s=e.emits;let o={},a=!1;if(!ce(e)){const l=u=>{const c=Uy(u,t,!0);c&&(a=!0,gt(o,c))};!r&&t.mixins.length&&t.mixins.forEach(l),e.extends&&l(e.extends),e.mixins&&e.mixins.forEach(l)}return!s&&!a?(lt(e)&&n.set(e,null),null):(de(s)?s.forEach(l=>o[l]=null):gt(o,s),lt(e)&&n.set(e,o),o)}function kc(e,t){return!e||!bc(t)?!1:(t=t.slice(2).replace(/Once$/,""),Le(e,t[0].toLowerCase()+t.slice(1))||Le(e,ks(t))||Le(e,t))}function rl(e){const{type:t,vnode:r,proxy:n,withProxy:i,propsOptions:[s],slots:o,attrs:a,emit:l,render:u,renderCache:c,props:d,data:p,setupState:m,ctx:h,inheritAttrs:f}=e,b=Lo(e);let y,w;try{if(r.shapeFlag&4){const _=i||n,x=_;y=Mt(u.call(x,_,c,d,m,p,h)),w=a}else{const _=t;y=Mt(_.length>1?_(d,{attrs:a,slots:o,emit:l}):_(d,null)),w=t.props?a:kS(a)}}catch(_){bo.length=0,$i(_,e,1),y=ze(Ye)}let v=y;if(w&&f!==!1){const _=Object.keys(w),{shapeFlag:x}=v;_.length&&x&7&&(s&&_.some(iy)&&(w=OS(w,s)),v=Xr(v,w,!1,!0))}return r.dirs&&(v=Xr(v,null,!1,!0),v.dirs=v.dirs?v.dirs.concat(r.dirs):r.dirs),r.transition&&En(v,r.transition),y=v,Lo(b),y}function TS(e,t=!0){let r;for(let n=0;n<e.length;n++){const i=e[n];if(xn(i)){if(i.type!==Ye||i.children==="v-if"){if(r)return;r=i}}else return}return r}const kS=e=>{let t;for(const r in e)(r==="class"||r==="style"||bc(r))&&((t||(t={}))[r]=e[r]);return t},OS=(e,t)=>{const r={};for(const n in e)(!iy(n)||!(n.slice(9)in t))&&(r[n]=e[n]);return r};function NS(e,t,r){const{props:n,children:i,component:s}=e,{props:o,children:a,patchFlag:l}=t,u=s.emitsOptions;if(t.dirs||t.transition)return!0;if(r&&l>=0){if(l&1024)return!0;if(l&16)return n?um(n,o,u):!!o;if(l&8){const c=t.dynamicProps;for(let d=0;d<c.length;d++){const p=c[d];if(o[p]!==n[p]&&!kc(u,p))return!0}}}else return(i||a)&&(!a||!a.$stable)?!0:n===o?!1:n?o?um(n,o,u):!0:!!o;return!1}function um(e,t,r){const n=Object.keys(t);if(n.length!==Object.keys(e).length)return!0;for(let i=0;i<n.length;i++){const s=n[i];if(t[s]!==e[s]&&!kc(r,s))return!0}return!1}function Oc({vnode:e,parent:t},r){for(;t;){const n=t.subTree;if(n.suspense&&n.suspense.activeBranch===e&&(n.el=e.el),n===e)(e=t.vnode).el=r,t=t.parent;else break}}const zy={},qy=()=>Object.create(zy),Hy=e=>Object.getPrototypeOf(e)===zy;function PS(e,t,r,n=!1){const i={},s=qy();e.propsDefaults=Object.create(null),jy(e,t,i,s);for(const o in e.propsOptions[0])o in i||(i[o]=void 0);r?e.props=n?i:Jv(i):e.type.props?e.props=i:e.props=s,e.attrs=s}function FS(e,t,r,n){const{props:i,attrs:s,vnode:{patchFlag:o}}=e,a=ye(i),[l]=e.propsOptions;let u=!1;if((n||o>0)&&!(o&16)){if(o&8){const c=e.vnode.dynamicProps;for(let d=0;d<c.length;d++){let p=c[d];if(kc(e.emitsOptions,p))continue;const m=t[p];if(l)if(Le(s,p))m!==s[p]&&(s[p]=m,u=!0);else{const h=rr(p);i[h]=ah(l,a,h,m,e,!1)}else m!==s[p]&&(s[p]=m,u=!0)}}}else{jy(e,t,i,s)&&(u=!0);let c;for(const d in a)(!t||!Le(t,d)&&((c=ks(d))===d||!Le(t,c)))&&(l?r&&(r[d]!==void 0||r[c]!==void 0)&&(i[d]=ah(l,a,d,void 0,e,!0)):delete i[d]);if(s!==a)for(const d in s)(!t||!Le(t,d))&&(delete s[d],u=!0)}u&&un(e.attrs,"set","")}function jy(e,t,r,n){const[i,s]=e.propsOptions;let o=!1,a;if(t)for(let l in t){if(wi(l))continue;const u=t[l];let c;i&&Le(i,c=rr(l))?!s||!s.includes(c)?r[c]=u:(a||(a={}))[c]=u:kc(e.emitsOptions,l)||(!(l in n)||u!==n[l])&&(n[l]=u,o=!0)}if(s){const l=ye(r),u=a||Ee;for(let c=0;c<s.length;c++){const d=s[c];r[d]=ah(i,l,d,u[d],e,!Le(u,d))}}return o}function ah(e,t,r,n,i,s){const o=e[r];if(o!=null){const a=Le(o,"default");if(a&&n===void 0){const l=o.default;if(o.type!==Function&&!o.skipFactory&&ce(l)){const{propsDefaults:u}=i;if(r in u)n=u[r];else{const c=Ni(i);n=u[r]=l.call(null,t),c()}}else n=l;i.ce&&i.ce._setProp(r,n)}o[0]&&(s&&!a?n=!1:o[1]&&(n===""||n===ks(r))&&(n=!0))}return n}const IS=new WeakMap;function Wy(e,t,r=!1){const n=r?IS:t.propsCache,i=n.get(e);if(i)return i;const s=e.props,o={},a=[];let l=!1;if(!ce(e)){const c=d=>{l=!0;const[p,m]=Wy(d,t,!0);gt(o,p),m&&a.push(...m)};!r&&t.mixins.length&&t.mixins.forEach(c),e.extends&&c(e.extends),e.mixins&&e.mixins.forEach(c)}if(!s&&!l)return lt(e)&&n.set(e,ns),ns;if(de(s))for(let c=0;c<s.length;c++){const d=rr(s[c]);dm(d)&&(o[d]=Ee)}else if(s)for(const c in s){const d=rr(c);if(dm(d)){const p=s[c],m=o[d]=de(p)||ce(p)?{type:p}:gt({},p),h=m.type;let f=!1,b=!0;if(de(h))for(let y=0;y<h.length;++y){const w=h[y],v=ce(w)&&w.name;if(v==="Boolean"){f=!0;break}else v==="String"&&(b=!1)}else f=ce(h)&&h.name==="Boolean";m[0]=f,m[1]=b,(f||Le(m,"default"))&&a.push(d)}}const u=[o,a];return lt(e)&&n.set(e,u),u}function dm(e){return e[0]!=="$"&&!wi(e)}const Cf=e=>e==="_"||e==="_ctx"||e==="$stable",Af=e=>de(e)?e.map(Mt):[Mt(e)],LS=(e,t,r)=>{if(t._n)return t;const n=gf((...i)=>Af(t(...i)),r);return n._c=!1,n},Ky=(e,t,r)=>{const n=e._ctx;for(const i in e){if(Cf(i))continue;const s=e[i];if(ce(s))t[i]=LS(i,s,n);else if(s!=null){const o=Af(s);t[i]=()=>o}}},Gy=(e,t)=>{const r=Af(t);e.slots.default=()=>r},Yy=(e,t,r)=>{for(const n in t)(r||!Cf(n))&&(e[n]=t[n])},RS=(e,t,r)=>{const n=e.slots=qy();if(e.vnode.shapeFlag&32){const i=t._;i?(Yy(n,t,r),r&&nx(n,"_",i,!0)):Ky(t,n)}else t&&Gy(e,t)},$S=(e,t,r)=>{const{vnode:n,slots:i}=e;let s=!0,o=Ee;if(n.shapeFlag&32){const a=t._;a?r&&a===1?s=!1:Yy(i,t,r):(s=!t.$stable,Ky(t,i)),o=t}else t&&(Gy(e,t),o={default:1});if(s)for(const a in i)!Cf(a)&&o[a]==null&&delete i[a]},Qe=n_;function Xy(e){return Qy(e)}function Jy(e){return Qy(e,Rx)}function Qy(e,t){const r=wc();r.__VUE__=!0;const{insert:n,remove:i,patchProp:s,createElement:o,createText:a,createComment:l,setText:u,setElementText:c,parentNode:d,nextSibling:p,setScopeId:m=jr,insertStaticContent:h}=e,f=(E,I,U,H=null,q=null,j=null,J=void 0,X=null,G=!!I.dynamicChildren)=>{if(E===I)return;E&&!Sr(E,I)&&(H=Ht(E),je(E,q,j,!0),E=null),I.patchFlag===-2&&(G=!1,I.dynamicChildren=null);const{type:K,ref:ne,shapeFlag:Q}=I;switch(K){case Xn:b(E,I,U,H);break;case Ye:y(E,I,U,H);break;case Si:E==null&&w(I,U,H,J);break;case st:C(E,I,U,H,q,j,J,X,G);break;default:Q&1?x(E,I,U,H,q,j,J,X,G):Q&6?g(E,I,U,H,q,j,J,X,G):(Q&64||Q&128)&&K.process(E,I,U,H,q,j,J,X,G,he)}ne!=null&&q?ss(ne,E&&E.ref,j,I||E,!I):ne==null&&E&&E.ref!=null&&ss(E.ref,null,j,E,!0)},b=(E,I,U,H)=>{if(E==null)n(I.el=a(I.children),U,H);else{const q=I.el=E.el;I.children!==E.children&&u(q,I.children)}},y=(E,I,U,H)=>{E==null?n(I.el=l(I.children||""),U,H):I.el=E.el},w=(E,I,U,H)=>{[E.el,E.anchor]=h(E.children,I,U,H,E.el,E.anchor)},v=({el:E,anchor:I},U,H)=>{let q;for(;E&&E!==I;)q=p(E),n(E,U,H),E=q;n(I,U,H)},_=({el:E,anchor:I})=>{let U;for(;E&&E!==I;)U=p(E),i(E),E=U;i(I)},x=(E,I,U,H,q,j,J,X,G)=>{if(I.type==="svg"?J="svg":I.type==="math"&&(J="mathml"),E==null)k(I,U,H,q,j,J,X,G);else{const K=E.el&&E.el._isVueCE?E.el:null;try{K&&K._beginPatch(),A(E,I,q,j,J,X,G)}finally{K&&K._endPatch()}}},k=(E,I,U,H,q,j,J,X)=>{let G,K;const{props:ne,shapeFlag:Q,transition:re,dirs:ae}=E;if(G=E.el=o(E.type,j,ne&&ne.is,ne),Q&8?c(G,E.children):Q&16&&N(E.children,G,null,H,q,vu(E,j),J,X),ae&&Ur(E,null,H,"created"),S(G,E,E.scopeId,J,H),ne){for(const Oe in ne)Oe!=="value"&&!wi(Oe)&&s(G,Oe,null,ne[Oe],j,H);"value"in ne&&s(G,"value",null,ne.value,j),(K=ne.onVnodeBeforeMount)&&$t(K,H,E)}ae&&Ur(E,null,H,"beforeMount");const pe=Zy(q,re);pe&&re.beforeEnter(G),n(G,I,U),((K=ne&&ne.onVnodeMounted)||pe||ae)&&Qe(()=>{K&&$t(K,H,E),pe&&re.enter(G),ae&&Ur(E,null,H,"mounted")},q)},S=(E,I,U,H,q)=>{if(U&&m(E,U),H)for(let j=0;j<H.length;j++)m(E,H[j]);if(q){let j=q.subTree;if(I===j||Ol(j.type)&&(j.ssContent===I||j.ssFallback===I)){const J=q.vnode;S(E,J,J.scopeId,J.slotScopeIds,q.parent)}}},N=(E,I,U,H,q,j,J,X,G=0)=>{for(let K=G;K<E.length;K++){const ne=E[K]=X?Hn(E[K]):Mt(E[K]);f(null,ne,I,U,H,q,j,J,X)}},A=(E,I,U,H,q,j,J)=>{const X=I.el=E.el;let{patchFlag:G,dynamicChildren:K,dirs:ne}=I;G|=E.patchFlag&16;const Q=E.props||Ee,re=I.props||Ee;let ae;if(U&&ci(U,!1),(ae=re.onVnodeBeforeUpdate)&&$t(ae,U,I,E),ne&&Ur(I,E,U,"beforeUpdate"),U&&ci(U,!0),(Q.innerHTML&&re.innerHTML==null||Q.textContent&&re.textContent==null)&&c(X,""),K?T(E.dynamicChildren,K,X,U,H,vu(I,q),j):J||P(E,I,X,null,U,H,vu(I,q),j,!1),G>0){if(G&16)F(X,Q,re,U,q);else if(G&2&&Q.class!==re.class&&s(X,"class",null,re.class,q),G&4&&s(X,"style",Q.style,re.style,q),G&8){const pe=I.dynamicProps;for(let Oe=0;Oe<pe.length;Oe++){const ge=pe[Oe],dt=Q[ge],rt=re[ge];(rt!==dt||ge==="value")&&s(X,ge,dt,rt,q,U)}}G&1&&E.children!==I.children&&c(X,I.children)}else!J&&K==null&&F(X,Q,re,U,q);((ae=re.onVnodeUpdated)||ne)&&Qe(()=>{ae&&$t(ae,U,I,E),ne&&Ur(I,E,U,"updated")},H)},T=(E,I,U,H,q,j,J)=>{for(let X=0;X<I.length;X++){const G=E[X],K=I[X],ne=G.el&&(G.type===st||!Sr(G,K)||G.shapeFlag&198)?d(G.el):U;f(G,K,ne,null,H,q,j,J,!0)}},F=(E,I,U,H,q)=>{if(I!==U){if(I!==Ee)for(const j in I)!wi(j)&&!(j in U)&&s(E,j,I[j],null,q,H);for(const j in U){if(wi(j))continue;const J=U[j],X=I[j];J!==X&&j!=="value"&&s(E,j,X,J,q,H)}"value"in U&&s(E,"value",I.value,U.value,q)}},C=(E,I,U,H,q,j,J,X,G)=>{const K=I.el=E?E.el:a(""),ne=I.anchor=E?E.anchor:a("");let{patchFlag:Q,dynamicChildren:re,slotScopeIds:ae}=I;ae&&(X=X?X.concat(ae):ae),E==null?(n(K,U,H),n(ne,U,H),N(I.children||[],U,ne,q,j,J,X,G)):Q>0&&Q&64&&re&&E.dynamicChildren&&E.dynamicChildren.length===re.length?(T(E.dynamicChildren,re,U,q,j,J,X),(I.key!=null||q&&I===q.subTree)&&Tf(E,I,!0)):P(E,I,U,ne,q,j,J,X,G)},g=(E,I,U,H,q,j,J,X,G)=>{I.slotScopeIds=X,E==null?I.shapeFlag&512?q.ctx.activate(I,U,H,J,G):L(I,U,H,q,j,J,G):B(E,I,G)},L=(E,I,U,H,q,j,J)=>{const X=E.component=c_(E,H,q);if(ia(E)&&(X.ctx.renderer=he),d_(X,!1,J),X.asyncDep){if(q&&q.registerDep(X,$,J),!E.el){const G=X.subTree=ze(Ye);y(null,G,I,U),E.placeholder=G.el}}else $(X,E,I,U,q,j,J)},B=(E,I,U)=>{const H=I.component=E.component;if(NS(E,I,U))if(H.asyncDep&&!H.asyncResolved){z(H,I,U);return}else H.next=I,H.update();else I.el=E.el,H.vnode=I},$=(E,I,U,H,q,j,J)=>{const X=()=>{if(E.isMounted){let{next:Q,bu:re,u:ae,parent:pe,vnode:Oe}=E;{const Pt=e_(E);if(Pt){Q&&(Q.el=Oe.el,z(E,Q,J)),Pt.asyncDep.then(()=>{E.isUnmounted||X()});return}}let ge=Q,dt;ci(E,!1),Q?(Q.el=Oe.el,z(E,Q,J)):Q=Oe,re&&fo(re),(dt=Q.props&&Q.props.onVnodeBeforeUpdate)&&$t(dt,pe,Q,Oe),ci(E,!0);const rt=rl(E),jt=E.subTree;E.subTree=rt,f(jt,rt,d(jt.el),Ht(jt),E,q,j),Q.el=rt.el,ge===null&&Oc(E,rt.el),ae&&Qe(ae,q),(dt=Q.props&&Q.props.onVnodeUpdated)&&Qe(()=>$t(dt,pe,Q,Oe),q)}else{let Q;const{el:re,props:ae}=I,{bm:pe,m:Oe,parent:ge,root:dt,type:rt}=E,jt=gn(I);if(ci(E,!1),pe&&fo(pe),!jt&&(Q=ae&&ae.onVnodeBeforeMount)&&$t(Q,ge,I),ci(E,!0),re&&Se){const Pt=()=>{E.subTree=rl(E),Se(re,E.subTree,E,q,null)};jt&&rt.__asyncHydrate?rt.__asyncHydrate(re,E,Pt):Pt()}else{dt.ce&&dt.ce._def.shadowRoot!==!1&&dt.ce._injectChildStyle(rt);const Pt=E.subTree=rl(E);f(null,Pt,U,H,E,q,j),I.el=Pt.el}if(Oe&&Qe(Oe,q),!jt&&(Q=ae&&ae.onVnodeMounted)){const Pt=I;Qe(()=>$t(Q,ge,Pt),q)}(I.shapeFlag&256||ge&&gn(ge.vnode)&&ge.vnode.shapeFlag&256)&&E.a&&Qe(E.a,q),E.isMounted=!0,I=U=H=null}};E.scope.on();const G=E.effect=new Oo(X);E.scope.off();const K=E.update=G.run.bind(G),ne=E.job=G.runIfDirty.bind(G);ne.i=E,ne.id=E.uid,G.scheduler=()=>mf(ne),ci(E,!0),K()},z=(E,I,U)=>{I.component=E;const H=E.vnode.props;E.vnode=I,E.next=null,FS(E,I.props,H,U),$S(E,I.children,U),_n(),Qp(E),wn()},P=(E,I,U,H,q,j,J,X,G=!1)=>{const K=E&&E.children,ne=E?E.shapeFlag:0,Q=I.children,{patchFlag:re,shapeFlag:ae}=I;if(re>0){if(re&128){be(K,Q,U,H,q,j,J,X,G);return}else if(re&256){Z(K,Q,U,H,q,j,J,X,G);return}}ae&8?(ne&16&&ut(K,q,j),Q!==K&&c(U,Q)):ne&16?ae&16?be(K,Q,U,H,q,j,J,X,G):ut(K,q,j,!0):(ne&8&&c(U,""),ae&16&&N(Q,U,H,q,j,J,X,G))},Z=(E,I,U,H,q,j,J,X,G)=>{E=E||ns,I=I||ns;const K=E.length,ne=I.length,Q=Math.min(K,ne);let re;for(re=0;re<Q;re++){const ae=I[re]=G?Hn(I[re]):Mt(I[re]);f(E[re],ae,U,null,q,j,J,X,G)}K>ne?ut(E,q,j,!0,!1,Q):N(I,U,H,q,j,J,X,G,Q)},be=(E,I,U,H,q,j,J,X,G)=>{let K=0;const ne=I.length;let Q=E.length-1,re=ne-1;for(;K<=Q&&K<=re;){const ae=E[K],pe=I[K]=G?Hn(I[K]):Mt(I[K]);if(Sr(ae,pe))f(ae,pe,U,null,q,j,J,X,G);else break;K++}for(;K<=Q&&K<=re;){const ae=E[Q],pe=I[re]=G?Hn(I[re]):Mt(I[re]);if(Sr(ae,pe))f(ae,pe,U,null,q,j,J,X,G);else break;Q--,re--}if(K>Q){if(K<=re){const ae=re+1,pe=ae<ne?I[ae].el:H;for(;K<=re;)f(null,I[K]=G?Hn(I[K]):Mt(I[K]),U,pe,q,j,J,X,G),K++}}else if(K>re)for(;K<=Q;)je(E[K],q,j,!0),K++;else{const ae=K,pe=K,Oe=new Map;for(K=pe;K<=re;K++){const R=I[K]=G?Hn(I[K]):Mt(I[K]);R.key!=null&&Oe.set(R.key,K)}let ge,dt=0;const rt=re-pe+1;let jt=!1,Pt=0;const tn=new Array(rt);for(K=0;K<rt;K++)tn[K]=0;for(K=ae;K<=Q;K++){const R=E[K];if(dt>=rt){je(R,q,j,!0);continue}let M;if(R.key!=null)M=Oe.get(R.key);else for(ge=pe;ge<=re;ge++)if(tn[ge-pe]===0&&Sr(R,I[ge])){M=ge;break}M===void 0?je(R,q,j,!0):(tn[M-pe]=K+1,M>=Pt?Pt=M:jt=!0,f(R,I[M],U,null,q,j,J,X,G),dt++)}const ai=jt?MS(tn):ns;for(ge=ai.length-1,K=rt-1;K>=0;K--){const R=pe+K,M=I[R],ve=I[R+1],Ae=R+1<ne?ve.el||t_(ve):H;tn[K]===0?f(null,M,U,Ae,q,j,J,X,G):jt&&(ge<0||K!==ai[ge]?le(M,U,Ae,2):ge--)}}},le=(E,I,U,H,q=null)=>{const{el:j,type:J,transition:X,children:G,shapeFlag:K}=E;if(K&6){le(E.component.subTree,I,U,H);return}if(K&128){E.suspense.move(I,U,H);return}if(K&64){J.move(E,I,U,he);return}if(J===st){n(j,I,U);for(let Q=0;Q<G.length;Q++)le(G[Q],I,U,H);n(E.anchor,I,U);return}if(J===Si){v(E,I,U);return}if(H!==2&&K&1&&X)if(H===0)X.beforeEnter(j),n(j,I,U),Qe(()=>X.enter(j),q);else{const{leave:Q,delayLeave:re,afterLeave:ae}=X,pe=()=>{E.ctx.isUnmounted?i(j):n(j,I,U)},Oe=()=>{j._isLeaving&&j[ln](!0),Q(j,()=>{pe(),ae&&ae()})};re?re(j,pe,Oe):Oe()}else n(j,I,U)},je=(E,I,U,H=!1,q=!1)=>{const{type:j,props:J,ref:X,children:G,dynamicChildren:K,shapeFlag:ne,patchFlag:Q,dirs:re,cacheIndex:ae}=E;if(Q===-2&&(q=!1),X!=null&&(_n(),ss(X,null,U,E,!0),wn()),ae!=null&&(I.renderCache[ae]=void 0),ne&256){I.ctx.deactivate(E);return}const pe=ne&1&&re,Oe=!gn(E);let ge;if(Oe&&(ge=J&&J.onVnodeBeforeUnmount)&&$t(ge,I,E),ne&6)yr(E.component,U,H);else{if(ne&128){E.suspense.unmount(U,H);return}pe&&Ur(E,null,I,"beforeUnmount"),ne&64?E.type.remove(E,I,U,he,H):K&&!K.hasOnce&&(j!==st||Q>0&&Q&64)?ut(K,I,U,!1,!0):(j===st&&Q&384||!q&&ne&16)&&ut(G,I,U),H&&vr(E)}(Oe&&(ge=J&&J.onVnodeUnmounted)||pe)&&Qe(()=>{ge&&$t(ge,I,E),pe&&Ur(E,null,I,"unmounted")},U)},vr=E=>{const{type:I,el:U,anchor:H,transition:q}=E;if(I===st){$r(U,H);return}if(I===Si){_(E);return}const j=()=>{i(U),q&&!q.persisted&&q.afterLeave&&q.afterLeave()};if(E.shapeFlag&1&&q&&!q.persisted){const{leave:J,delayLeave:X}=q,G=()=>J(U,j);X?X(E.el,j,G):G()}else j()},$r=(E,I)=>{let U;for(;E!==I;)U=p(E),i(E),E=U;i(I)},yr=(E,I,U)=>{const{bum:H,scope:q,job:j,subTree:J,um:X,m:G,a:K}=E;kl(G),kl(K),H&&fo(H),q.stop(),j&&(j.flags|=8,je(J,E,I,U)),X&&Qe(X,I),Qe(()=>{E.isUnmounted=!0},I)},ut=(E,I,U,H=!1,q=!1,j=0)=>{for(let J=j;J<E.length;J++)je(E[J],I,U,H,q)},Ht=E=>{if(E.shapeFlag&6)return Ht(E.component.subTree);if(E.shapeFlag&128)return E.suspense.next();const I=p(E.anchor||E.el),U=I&&I[_y];return U?p(U):I};let lr=!1;const Xe=(E,I,U)=>{let H;E==null?I._vnode&&(je(I._vnode,null,null,!0),H=I._vnode.component):f(I._vnode||null,E,I,null,null,null,U),I._vnode=E,lr||(lr=!0,Qp(H),Cl(),lr=!1)},he={p:f,um:je,m:le,r:vr,mt:L,mc:N,pc:P,pbc:T,n:Ht,o:e};let Re,Se;return t&&([Re,Se]=t(he)),{render:Xe,hydrate:Re,createApp:xS(Xe,Re)}}function vu({type:e,props:t},r){return r==="svg"&&e==="foreignObject"||r==="mathml"&&e==="annotation-xml"&&t&&t.encoding&&t.encoding.includes("html")?void 0:r}function ci({effect:e,job:t},r){r?(e.flags|=32,t.flags|=4):(e.flags&=-33,t.flags&=-5)}function Zy(e,t){return(!e||e&&!e.pendingBranch)&&t&&!t.persisted}function Tf(e,t,r=!1){const n=e.children,i=t.children;if(de(n)&&de(i))for(let s=0;s<n.length;s++){const o=n[s];let a=i[s];a.shapeFlag&1&&!a.dynamicChildren&&((a.patchFlag<=0||a.patchFlag===32)&&(a=i[s]=Hn(i[s]),a.el=o.el),!r&&a.patchFlag!==-2&&Tf(o,a)),a.type===Xn&&(a.patchFlag!==-1?a.el=o.el:a.__elIndex=s+(e.type===st?1:0)),a.type===Ye&&!a.el&&(a.el=o.el)}}function MS(e){const t=e.slice(),r=[0];let n,i,s,o,a;const l=e.length;for(n=0;n<l;n++){const u=e[n];if(u!==0){if(i=r[r.length-1],e[i]<u){t[n]=i,r.push(n);continue}for(s=0,o=r.length-1;s<o;)a=s+o>>1,e[r[a]]<u?s=a+1:o=a;u<e[r[s]]&&(s>0&&(t[n]=r[s-1]),r[s]=n)}}for(s=r.length,o=r[s-1];s-- >0;)r[s]=o,o=t[o];return r}function e_(e){const t=e.subTree.component;if(t)return t.asyncDep&&!t.asyncResolved?t:e_(t)}function kl(e){if(e)for(let t=0;t<e.length;t++)e[t].flags|=8}function t_(e){if(e.placeholder)return e.placeholder;const t=e.component;return t?t_(t.subTree):null}const Ol=e=>e.__isSuspense;let lh=0;const DS={name:"Suspense",__isSuspense:!0,process(e,t,r,n,i,s,o,a,l,u){if(e==null)BS(t,r,n,i,s,o,a,l,u);else{if(s&&s.deps>0&&!e.suspense.isInFallback){t.suspense=e.suspense,t.suspense.vnode=t,t.el=e.el;return}US(e,t,r,n,i,o,a,l,u)}},hydrate:zS,normalize:qS},VS=DS;function $o(e,t){const r=e.props&&e.props[t];ce(r)&&r()}function BS(e,t,r,n,i,s,o,a,l){const{p:u,o:{createElement:c}}=l,d=c("div"),p=e.suspense=r_(e,i,n,t,d,r,s,o,a,l);u(null,p.pendingBranch=e.ssContent,d,null,n,p,s,o),p.deps>0?($o(e,"onPending"),$o(e,"onFallback"),u(null,e.ssFallback,t,r,n,null,s,o),os(p,e.ssFallback)):p.resolve(!1,!0)}function US(e,t,r,n,i,s,o,a,{p:l,um:u,o:{createElement:c}}){const d=t.suspense=e.suspense;d.vnode=t,t.el=e.el;const p=t.ssContent,m=t.ssFallback,{activeBranch:h,pendingBranch:f,isInFallback:b,isHydrating:y}=d;if(f)d.pendingBranch=p,Sr(f,p)?(l(f,p,d.hiddenContainer,null,i,d,s,o,a),d.deps<=0?d.resolve():b&&(y||(l(h,m,r,n,i,null,s,o,a),os(d,m)))):(d.pendingId=lh++,y?(d.isHydrating=!1,d.activeBranch=f):u(f,i,d),d.deps=0,d.effects.length=0,d.hiddenContainer=c("div"),b?(l(null,p,d.hiddenContainer,null,i,d,s,o,a),d.deps<=0?d.resolve():(l(h,m,r,n,i,null,s,o,a),os(d,m))):h&&Sr(h,p)?(l(h,p,r,n,i,d,s,o,a),d.resolve(!0)):(l(null,p,d.hiddenContainer,null,i,d,s,o,a),d.deps<=0&&d.resolve()));else if(h&&Sr(h,p))l(h,p,r,n,i,d,s,o,a),os(d,p);else if($o(t,"onPending"),d.pendingBranch=p,p.shapeFlag&512?d.pendingId=p.component.suspenseId:d.pendingId=lh++,l(null,p,d.hiddenContainer,null,i,d,s,o,a),d.deps<=0)d.resolve();else{const{timeout:w,pendingId:v}=d;w>0?setTimeout(()=>{d.pendingId===v&&d.fallback(m)},w):w===0&&d.fallback(m)}}function r_(e,t,r,n,i,s,o,a,l,u,c=!1){const{p:d,m:p,um:m,n:h,o:{parentNode:f,remove:b}}=u;let y;const w=HS(e);w&&t&&t.pendingBranch&&(y=t.pendingId,t.deps++);const v=e.props?sx(e.props.timeout):void 0,_=s,x={vnode:e,parent:t,parentComponent:r,namespace:o,container:n,hiddenContainer:i,deps:0,pendingId:lh++,timeout:typeof v=="number"?v:-1,activeBranch:null,pendingBranch:null,isInFallback:!c,isHydrating:c,isUnmounted:!1,effects:[],resolve(k=!1,S=!1){const{vnode:N,activeBranch:A,pendingBranch:T,pendingId:F,effects:C,parentComponent:g,container:L,isInFallback:B}=x;let $=!1;x.isHydrating?x.isHydrating=!1:k||($=A&&T.transition&&T.transition.mode==="out-in",$&&(A.transition.afterLeave=()=>{F===x.pendingId&&(p(T,L,s===_?h(A):s,0),Fo(C),B&&N.ssFallback&&(N.ssFallback.el=null))}),A&&(f(A.el)===L&&(s=h(A)),m(A,g,x,!0),!$&&B&&N.ssFallback&&Qe(()=>N.ssFallback.el=null,x)),$||p(T,L,s,0)),os(x,T),x.pendingBranch=null,x.isInFallback=!1;let z=x.parent,P=!1;for(;z;){if(z.pendingBranch){z.effects.push(...C),P=!0;break}z=z.parent}!P&&!$&&Fo(C),x.effects=[],w&&t&&t.pendingBranch&&y===t.pendingId&&(t.deps--,t.deps===0&&!S&&t.resolve()),$o(N,"onResolve")},fallback(k){if(!x.pendingBranch)return;const{vnode:S,activeBranch:N,parentComponent:A,container:T,namespace:F}=x;$o(S,"onFallback");const C=h(N),g=()=>{x.isInFallback&&(d(null,k,T,C,A,null,F,a,l),os(x,k))},L=k.transition&&k.transition.mode==="out-in";L&&(N.transition.afterLeave=g),x.isInFallback=!0,m(N,A,null,!0),L||g()},move(k,S,N){x.activeBranch&&p(x.activeBranch,k,S,N),x.container=k},next(){return x.activeBranch&&h(x.activeBranch)},registerDep(k,S,N){const A=!!x.pendingBranch;A&&x.deps++;const T=k.vnode.el;k.asyncDep.catch(F=>{$i(F,k,0)}).then(F=>{if(k.isUnmounted||x.isUnmounted||x.pendingId!==k.suspenseId)return;k.asyncResolved=!0;const{vnode:C}=k;dh(k,F,!1),T&&(C.el=T);const g=!T&&k.subTree.el;S(k,C,f(T||k.subTree.el),T?null:h(k.subTree),x,o,N),g&&(C.placeholder=null,b(g)),Oc(k,C.el),A&&--x.deps===0&&x.resolve()})},unmount(k,S){x.isUnmounted=!0,x.activeBranch&&m(x.activeBranch,r,k,S),x.pendingBranch&&m(x.pendingBranch,r,k,S)}};return x}function zS(e,t,r,n,i,s,o,a,l){const u=t.suspense=r_(t,n,r,e.parentNode,document.createElement("div"),null,i,s,o,a,!0),c=l(e,u.pendingBranch=t.ssContent,r,u,s,o);return u.deps===0&&u.resolve(!1,!0),c}function qS(e){const{shapeFlag:t,children:r}=e,n=t&32;e.ssContent=hm(n?r.default:r),e.ssFallback=n?hm(r.fallback):ze(Ye)}function hm(e){let t;if(ce(e)){const r=Oi&&e._c;r&&(e._d=!1,Mo()),e=e(),r&&(e._d=!0,t=At,i_())}return de(e)&&(e=TS(e)),e=Mt(e),t&&!e.dynamicChildren&&(e.dynamicChildren=t.filter(r=>r!==e)),e}function n_(e,t){t&&t.pendingBranch?de(e)?t.effects.push(...e):t.effects.push(e):Fo(e)}function os(e,t){e.activeBranch=t;const{vnode:r,parentComponent:n}=e;let i=t.el;for(;!i&&t.component;)t=t.component.subTree,i=t.el;r.el=i,n&&n.subTree===r&&(n.vnode.el=i,Oc(n,i))}function HS(e){const t=e.props&&e.props.suspensible;return t!=null&&t!==!1}const st=Symbol.for("v-fgt"),Xn=Symbol.for("v-txt"),Ye=Symbol.for("v-cmt"),Si=Symbol.for("v-stc"),bo=[];let At=null;function Mo(e=!1){bo.push(At=e?null:[])}function i_(){bo.pop(),At=bo[bo.length-1]||null}let Oi=1;function Do(e,t=!1){Oi+=e,e<0&&At&&t&&(At.hasOnce=!0)}function s_(e){return e.dynamicChildren=Oi>0?At||ns:null,i_(),Oi>0&&At&&At.push(e),e}function jS(e,t,r,n,i,s){return s_(kf(e,t,r,n,i,s,!0))}function Nl(e,t,r,n,i){return s_(ze(e,t,r,n,i,!0))}function xn(e){return e?e.__v_isVNode===!0:!1}function Sr(e,t){return e.type===t.type&&e.key===t.key}function WS(e){}const o_=({key:e})=>e??null,nl=({ref:e,ref_key:t,ref_for:r})=>(typeof e=="number"&&(e=""+e),e!=null?tt(e)||et(e)||ce(e)?{i:_t,r:e,k:t,f:!!r}:e:null);function kf(e,t=null,r=null,n=0,i=null,s=e===st?0:1,o=!1,a=!1){const l={__v_isVNode:!0,__v_skip:!0,type:e,props:t,key:t&&o_(t),ref:t&&nl(t),scopeId:xc,slotScopeIds:null,children:r,component:null,suspense:null,ssContent:null,ssFallback:null,dirs:null,transition:null,el:null,anchor:null,target:null,targetStart:null,targetAnchor:null,staticCount:0,shapeFlag:s,patchFlag:n,dynamicProps:i,dynamicChildren:null,appContext:null,ctx:_t};return a?(Nf(l,r),s&128&&e.normalize(l)):r&&(l.shapeFlag|=tt(r)?8:16),Oi>0&&!o&&At&&(l.patchFlag>0||s&6)&&l.patchFlag!==32&&At.push(l),l}const ze=KS;function KS(e,t=null,r=null,n=0,i=null,s=!1){if((!e||e===$y)&&(e=Ye),xn(e)){const a=Xr(e,t,!0);return r&&Nf(a,r),Oi>0&&!s&&At&&(a.shapeFlag&6?At[At.indexOf(e)]=a:At.push(a)),a.patchFlag=-2,a}if(tC(e)&&(e=e.__vccOpts),t){t=a_(t);let{class:a,style:l}=t;a&&!tt(a)&&(t.class=ra(a)),lt(l)&&(Zo(l)&&!de(l)&&(l=gt({},l)),t.style=ta(l))}const o=tt(e)?1:Ol(e)?128:wy(e)?64:lt(e)?4:ce(e)?2:0;return kf(e,t,r,n,i,o,s,!0)}function a_(e){return e?Zo(e)||Hy(e)?gt({},e):e:null}function Xr(e,t,r=!1,n=!1){const{props:i,ref:s,patchFlag:o,children:a,transition:l}=e,u=t?l_(i||{},t):i,c={__v_isVNode:!0,__v_skip:!0,type:e.type,props:u,key:u&&o_(u),ref:t&&t.ref?r&&s?de(s)?s.concat(nl(t)):[s,nl(t)]:nl(t):s,scopeId:e.scopeId,slotScopeIds:e.slotScopeIds,children:a,target:e.target,targetStart:e.targetStart,targetAnchor:e.targetAnchor,staticCount:e.staticCount,shapeFlag:e.shapeFlag,patchFlag:t&&e.type!==st?o===-1?16:o|16:o,dynamicProps:e.dynamicProps,dynamicChildren:e.dynamicChildren,appContext:e.appContext,dirs:e.dirs,transition:l,component:e.component,suspense:e.suspense,ssContent:e.ssContent&&Xr(e.ssContent),ssFallback:e.ssFallback&&Xr(e.ssFallback),placeholder:e.placeholder,el:e.el,anchor:e.anchor,ctx:e.ctx,ce:e.ce};return l&&n&&En(c,l.clone(c)),c}function Of(e=" ",t=0){return ze(Xn,null,e,t)}function GS(e,t){const r=ze(Si,null,e);return r.staticCount=t,r}function YS(e="",t=!1){return t?(Mo(),Nl(Ye,null,e)):ze(Ye,null,e)}function Mt(e){return e==null||typeof e=="boolean"?ze(Ye):de(e)?ze(st,null,e.slice()):xn(e)?Hn(e):ze(Xn,null,String(e))}function Hn(e){return e.el===null&&e.patchFlag!==-1||e.memo?e:Xr(e)}function Nf(e,t){let r=0;const{shapeFlag:n}=e;if(t==null)t=null;else if(de(t))r=16;else if(typeof t=="object")if(n&65){const i=t.default;i&&(i._c&&(i._d=!1),Nf(e,i()),i._c&&(i._d=!0));return}else{r=32;const i=t._;!i&&!Hy(t)?t._ctx=_t:i===3&&_t&&(_t.slots._===1?t._=1:(t._=2,e.patchFlag|=1024))}else ce(t)?(t={default:t,_ctx:_t},r=32):(t=String(t),n&64?(r=16,t=[Of(t)]):r=8);e.children=t,e.shapeFlag|=r}function l_(...e){const t={};for(let r=0;r<e.length;r++){const n=e[r];for(const i in n)if(i==="class")t.class!==n.class&&(t.class=ra([t.class,n.class]));else if(i==="style")t.style=ta([t.style,n.style]);else if(bc(i)){const s=t[i],o=n[i];o&&s!==o&&!(de(s)&&s.includes(o))&&(t[i]=s?[].concat(s,o):o)}else i!==""&&(t[i]=n[i])}return t}function $t(e,t,r,n=null){pr(e,t,7,[r,n])}const XS=Vy();let JS=0;function c_(e,t,r){const n=e.type,i=(t?t.appContext:e.appContext)||XS,s={uid:JS++,vnode:e,type:n,parent:t,appContext:i,root:null,next:null,subTree:null,effect:null,update:null,job:null,scope:new af(!0),render:null,proxy:null,exposed:null,exposeProxy:null,withProxy:null,provides:t?t.provides:Object.create(i.provides),ids:t?t.ids:["",0,0],accessCache:null,renderCache:[],components:null,directives:null,propsOptions:Wy(n,i),emitsOptions:Uy(n,i),emit:null,emitted:null,propsDefaults:Ee,inheritAttrs:n.inheritAttrs,ctx:Ee,data:Ee,props:Ee,attrs:Ee,slots:Ee,refs:Ee,setupState:Ee,setupContext:null,suspense:r,suspenseId:r?r.pendingId:0,asyncDep:null,asyncResolved:!1,isMounted:!1,isUnmounted:!1,isDeactivated:!1,bc:null,c:null,bm:null,m:null,bu:null,u:null,um:null,bum:null,da:null,a:null,rtg:null,rtc:null,ec:null,sp:null};return s.ctx={_:s},s.root=t?t.root:s,s.emit=CS.bind(null,s),e.ce&&e.ce(s),s}let yt=null;const zt=()=>yt||_t;let Pl,ch;{const e=wc(),t=(r,n)=>{let i;return(i=e[r])||(i=e[r]=[]),i.push(n),s=>{i.length>1?i.forEach(o=>o(s)):i[0](s)}};Pl=t("__VUE_INSTANCE_SETTERS__",r=>yt=r),ch=t("__VUE_SSR_SETTERS__",r=>ds=r)}const Ni=e=>{const t=yt;return Pl(e),e.scope.on(),()=>{e.scope.off(),Pl(t)}},uh=()=>{yt&&yt.scope.off(),Pl(null)};function u_(e){return e.vnode.shapeFlag&4}let ds=!1;function d_(e,t=!1,r=!1){t&&ch(t);const{props:n,children:i}=e.vnode,s=u_(e);PS(e,n,s,t),RS(e,i,r||t);const o=s?QS(e,t):void 0;return t&&ch(!1),o}function QS(e,t){const r=e.type;e.accessCache=Object.create(null),e.proxy=new Proxy(e.ctx,ih);const{setup:n}=r;if(n){_n();const i=e.setupContext=n.length>1?p_(e):null,s=Ni(e),o=Os(n,e,0,[e.props,i]),a=pf(o);if(wn(),s(),(a||e.sp)&&!gn(e)&&yf(e),a){if(o.then(uh,uh),t)return o.then(l=>{dh(e,l,t)}).catch(l=>{$i(l,e,0)});e.asyncDep=o}else dh(e,o,t)}else f_(e,t)}function dh(e,t,r){ce(t)?e.type.__ssrInlineRender?e.ssrRender=t:e.render=t:lt(t)&&(e.setupState=hf(t)),f_(e,r)}let Fl,hh;function h_(e){Fl=e,hh=t=>{t.render._rc&&(t.withProxy=new Proxy(t.ctx,nS))}}const ZS=()=>!Fl;function f_(e,t,r){const n=e.type;if(!e.render){if(!t&&Fl&&!n.render){const i=n.template||Sf(e).template;if(i){const{isCustomElement:s,compilerOptions:o}=e.appContext.config,{delimiters:a,compilerOptions:l}=n,u=gt(gt({isCustomElement:s,delimiters:a},o),l);n.render=Fl(i,u)}}e.render=n.render||jr,hh&&hh(e)}{const i=Ni(e);_n();try{bS(e)}finally{wn(),i()}}}const eC={get(e,t){return Ct(e,"get",""),e[t]}};function p_(e){const t=r=>{e.exposed=r||{}};return{attrs:new Proxy(e.attrs,eC),slots:e.slots,emit:e.emit,expose:t}}function oa(e){return e.exposed?e.exposeProxy||(e.exposeProxy=new Proxy(hf(El(e.exposed)),{get(t,r){if(r in t)return t[r];if(r in go)return go[r](e)},has(t,r){return r in t||r in go}})):e.proxy}function fh(e,t=!0){return ce(e)?e.displayName||e.name:e.name||t&&e.__name}function tC(e){return ce(e)&&"__vccOpts"in e}const De=(e,t)=>j1(e,t,ds);function Wr(e,t,r){try{Do(-1);const n=arguments.length;return n===2?lt(t)&&!de(t)?xn(t)?ze(e,null,[t]):ze(e,t):ze(e,null,t):(n>3?r=Array.prototype.slice.call(arguments,2):n===3&&xn(r)&&(r=[r]),ze(e,t,r))}finally{Do(1)}}function rC(){}function nC(e,t,r,n){const i=r[n];if(i&&m_(i,e))return i;const s=t();return s.memo=e.slice(),s.cacheIndex=n,r[n]=s}function m_(e,t){const r=e.memo;if(r.length!=t.length)return!1;for(let n=0;n<r.length;n++)if(fi(r[n],t[n]))return!1;return Oi>0&&At&&At.push(e),!0}const g_="3.5.27",iC=jr,sC=bx,oC=Qi,aC=py,lC={createComponentInstance:c_,setupComponent:d_,renderComponentRoot:rl,setCurrentRenderingInstance:Lo,isVNode:xn,normalizeVNode:Mt,getComponentPublicInstance:oa,ensureValidVNode:xf,pushWarningContext:fx,popWarningContext:px},cC=lC,uC=null,dC=null,hC=null;function fC(e){const t=Object.create(null);for(const r of e.split(","))t[r]=1;return r=>r in t}const yu={},pC=()=>{},mC=e=>e.charCodeAt(0)===111&&e.charCodeAt(1)===110&&(e.charCodeAt(2)>122||e.charCodeAt(2)<97),gC=e=>e.startsWith("onUpdate:"),Jn=Object.assign,bC=Object.prototype.hasOwnProperty,vC=(e,t)=>bC.call(e,t),nr=Array.isArray,aa=e=>Pf(e)==="[object Set]",fm=e=>Pf(e)==="[object Date]",b_=e=>typeof e=="function",hs=e=>typeof e=="string",ph=e=>typeof e=="symbol",mh=e=>e!==null&&typeof e=="object",yC=Object.prototype.toString,Pf=e=>yC.call(e),v_=e=>Pf(e)==="[object Object]",Ff=e=>{const t=Object.create(null);return(r=>t[r]||(t[r]=e(r)))},_C=/-\w/g,il=Ff(e=>e.replace(_C,t=>t.slice(1).toUpperCase())),wC=/\B([A-Z])/g,Wn=Ff(e=>e.replace(wC,"-$1").toLowerCase()),EC=Ff(e=>e.charAt(0).toUpperCase()+e.slice(1)),xC=(e,...t)=>{for(let r=0;r<e.length;r++)e[r](...t)},If=e=>{const t=parseFloat(e);return isNaN(t)?e:t},gh=e=>{const t=hs(e)?Number(e):NaN;return isNaN(t)?e:t},SC="itemscope,allowfullscreen,formnovalidate,ismap,nomodule,novalidate,readonly",CC=fC(SC);function y_(e){return!!e||e===""}function AC(e,t){if(e.length!==t.length)return!1;let r=!0;for(let n=0;r&&n<e.length;n++)r=ti(e[n],t[n]);return r}function ti(e,t){if(e===t)return!0;let r=fm(e),n=fm(t);if(r||n)return r&&n?e.getTime()===t.getTime():!1;if(r=ph(e),n=ph(t),r||n)return e===t;if(r=nr(e),n=nr(t),r||n)return r&&n?AC(e,t):!1;if(r=mh(e),n=mh(t),r||n){if(!r||!n)return!1;const i=Object.keys(e).length,s=Object.keys(t).length;if(i!==s)return!1;for(const o in e){const a=e.hasOwnProperty(o),l=t.hasOwnProperty(o);if(a&&!l||!a&&l||!ti(e[o],t[o]))return!1}}return String(e)===String(t)}function Nc(e,t){return e.findIndex(r=>ti(r,t))}function TC(e){return e==null?"initial":typeof e=="string"?e===""?" ":e:String(e)}let bh;const pm=typeof window<"u"&&window.trustedTypes;if(pm)try{bh=pm.createPolicy("vue",{createHTML:e=>e})}catch{}const __=bh?e=>bh.createHTML(e):e=>e,kC="http://www.w3.org/2000/svg",OC="http://www.w3.org/1998/Math/MathML",an=typeof document<"u"?document:null,mm=an&&an.createElement("template"),w_={insert:(e,t,r)=>{t.insertBefore(e,r||null)},remove:e=>{const t=e.parentNode;t&&t.removeChild(e)},createElement:(e,t,r,n)=>{const i=t==="svg"?an.createElementNS(kC,e):t==="mathml"?an.createElementNS(OC,e):r?an.createElement(e,{is:r}):an.createElement(e);return e==="select"&&n&&n.multiple!=null&&i.setAttribute("multiple",n.multiple),i},createText:e=>an.createTextNode(e),createComment:e=>an.createComment(e),setText:(e,t)=>{e.nodeValue=t},setElementText:(e,t)=>{e.textContent=t},parentNode:e=>e.parentNode,nextSibling:e=>e.nextSibling,querySelector:e=>an.querySelector(e),setScopeId(e,t){e.setAttribute(t,"")},insertStaticContent(e,t,r,n,i,s){const o=r?r.previousSibling:t.lastChild;if(i&&(i===s||i.nextSibling))for(;t.insertBefore(i.cloneNode(!0),r),!(i===s||!(i=i.nextSibling)););else{mm.innerHTML=__(n==="svg"?`<svg>${e}</svg>`:n==="mathml"?`<math>${e}</math>`:e);const a=mm.content;if(n==="svg"||n==="mathml"){const l=a.firstChild;for(;l.firstChild;)a.appendChild(l.firstChild);a.removeChild(l)}t.insertBefore(a,r)}return[o?o.nextSibling:t.firstChild,r?r.previousSibling:t.lastChild]}},Nn="transition",Bs="animation",fs=Symbol("_vtc"),E_={name:String,type:String,css:{type:Boolean,default:!0},duration:[String,Number,Object],enterFromClass:String,enterActiveClass:String,enterToClass:String,appearFromClass:String,appearActiveClass:String,appearToClass:String,leaveFromClass:String,leaveActiveClass:String,leaveToClass:String},x_=Jn({},vf,E_),NC=e=>(e.displayName="Transition",e.props=x_,e),PC=NC((e,{slots:t})=>Wr(Ay,S_(e),t)),ui=(e,t=[])=>{nr(e)?e.forEach(r=>r(...t)):e&&e(...t)},gm=e=>e?nr(e)?e.some(t=>t.length>1):e.length>1:!1;function S_(e){const t={};for(const C in e)C in E_||(t[C]=e[C]);if(e.css===!1)return t;const{name:r="v",type:n,duration:i,enterFromClass:s=`${r}-enter-from`,enterActiveClass:o=`${r}-enter-active`,enterToClass:a=`${r}-enter-to`,appearFromClass:l=s,appearActiveClass:u=o,appearToClass:c=a,leaveFromClass:d=`${r}-leave-from`,leaveActiveClass:p=`${r}-leave-active`,leaveToClass:m=`${r}-leave-to`}=e,h=FC(i),f=h&&h[0],b=h&&h[1],{onBeforeEnter:y,onEnter:w,onEnterCancelled:v,onLeave:_,onLeaveCancelled:x,onBeforeAppear:k=y,onAppear:S=w,onAppearCancelled:N=v}=t,A=(C,g,L,B)=>{C._enterCancelled=B,Bn(C,g?c:a),Bn(C,g?u:o),L&&L()},T=(C,g)=>{C._isLeaving=!1,Bn(C,d),Bn(C,m),Bn(C,p),g&&g()},F=C=>(g,L)=>{const B=C?S:w,$=()=>A(g,C,L);ui(B,[g,$]),bm(()=>{Bn(g,C?l:s),Dr(g,C?c:a),gm(B)||vm(g,n,f,$)})};return Jn(t,{onBeforeEnter(C){ui(y,[C]),Dr(C,s),Dr(C,o)},onBeforeAppear(C){ui(k,[C]),Dr(C,l),Dr(C,u)},onEnter:F(!1),onAppear:F(!0),onLeave(C,g){C._isLeaving=!0;const L=()=>T(C,g);Dr(C,d),C._enterCancelled?(Dr(C,p),vh(C)):(vh(C),Dr(C,p)),bm(()=>{C._isLeaving&&(Bn(C,d),Dr(C,m),gm(_)||vm(C,n,b,L))}),ui(_,[C,L])},onEnterCancelled(C){A(C,!1,void 0,!0),ui(v,[C])},onAppearCancelled(C){A(C,!0,void 0,!0),ui(N,[C])},onLeaveCancelled(C){T(C),ui(x,[C])}})}function FC(e){if(e==null)return null;if(mh(e))return[_u(e.enter),_u(e.leave)];{const t=_u(e);return[t,t]}}function _u(e){return gh(e)}function Dr(e,t){t.split(/\s+/).forEach(r=>r&&e.classList.add(r)),(e[fs]||(e[fs]=new Set)).add(t)}function Bn(e,t){t.split(/\s+/).forEach(n=>n&&e.classList.remove(n));const r=e[fs];r&&(r.delete(t),r.size||(e[fs]=void 0))}function bm(e){requestAnimationFrame(()=>{requestAnimationFrame(e)})}let IC=0;function vm(e,t,r,n){const i=e._endId=++IC,s=()=>{i===e._endId&&n()};if(r!=null)return setTimeout(s,r);const{type:o,timeout:a,propCount:l}=C_(e,t);if(!o)return n();const u=o+"end";let c=0;const d=()=>{e.removeEventListener(u,p),s()},p=m=>{m.target===e&&++c>=l&&d()};setTimeout(()=>{c<l&&d()},a+1),e.addEventListener(u,p)}function C_(e,t){const r=window.getComputedStyle(e),n=h=>(r[h]||"").split(", "),i=n(`${Nn}Delay`),s=n(`${Nn}Duration`),o=ym(i,s),a=n(`${Bs}Delay`),l=n(`${Bs}Duration`),u=ym(a,l);let c=null,d=0,p=0;t===Nn?o>0&&(c=Nn,d=o,p=s.length):t===Bs?u>0&&(c=Bs,d=u,p=l.length):(d=Math.max(o,u),c=d>0?o>u?Nn:Bs:null,p=c?c===Nn?s.length:l.length:0);const m=c===Nn&&/\b(?:transform|all)(?:,|$)/.test(n(`${Nn}Property`).toString());return{type:c,timeout:d,propCount:p,hasTransform:m}}function ym(e,t){for(;e.length<t.length;)e=e.concat(e);return Math.max(...t.map((r,n)=>_m(r)+_m(e[n])))}function _m(e){return e==="auto"?0:Number(e.slice(0,-1).replace(",","."))*1e3}function vh(e){return(e?e.ownerDocument:document).body.offsetHeight}function LC(e,t,r){const n=e[fs];n&&(t=(t?[t,...n]:[...n]).join(" ")),t==null?e.removeAttribute("class"):r?e.setAttribute("class",t):e.className=t}const Il=Symbol("_vod"),A_=Symbol("_vsh"),T_={name:"show",beforeMount(e,{value:t},{transition:r}){e[Il]=e.style.display==="none"?"":e.style.display,r&&t?r.beforeEnter(e):Us(e,t)},mounted(e,{value:t},{transition:r}){r&&t&&r.enter(e)},updated(e,{value:t,oldValue:r},{transition:n}){!t!=!r&&(n?t?(n.beforeEnter(e),Us(e,!0),n.enter(e)):n.leave(e,()=>{Us(e,!1)}):Us(e,t))},beforeUnmount(e,{value:t}){Us(e,t)}};function Us(e,t){e.style.display=t?e[Il]:"none",e[A_]=!t}function RC(){T_.getSSRProps=({value:e})=>{if(!e)return{style:{display:"none"}}}}const k_=Symbol("");function $C(e){const t=zt();if(!t)return;const r=t.ut=(i=e(t.proxy))=>{Array.from(document.querySelectorAll(`[data-v-owner="${t.uid}"]`)).forEach(s=>Ll(s,i))},n=()=>{const i=e(t.proxy);t.ce?Ll(t.ce,i):yh(t.subTree,i),r(i)};_f(()=>{Fo(n)}),Ns(()=>{Ei(n,pC,{flush:"post"});const i=new MutationObserver(n);i.observe(t.subTree.el.parentNode,{childList:!0}),sa(()=>i.disconnect())})}function yh(e,t){if(e.shapeFlag&128){const r=e.suspense;e=r.activeBranch,r.pendingBranch&&!r.isHydrating&&r.effects.push(()=>{yh(r.activeBranch,t)})}for(;e.component;)e=e.component.subTree;if(e.shapeFlag&1&&e.el)Ll(e.el,t);else if(e.type===st)e.children.forEach(r=>yh(r,t));else if(e.type===Si){let{el:r,anchor:n}=e;for(;r&&(Ll(r,t),r!==n);)r=r.nextSibling}}function Ll(e,t){if(e.nodeType===1){const r=e.style;let n="";for(const i in t){const s=TC(t[i]);r.setProperty(`--${i}`,s),n+=`--${i}: ${s};`}r[k_]=n}}const MC=/(?:^|;)\s*display\s*:/;function DC(e,t,r){const n=e.style,i=hs(r);let s=!1;if(r&&!i){if(t)if(hs(t))for(const o of t.split(";")){const a=o.slice(0,o.indexOf(":")).trim();r[a]==null&&sl(n,a,"")}else for(const o in t)r[o]==null&&sl(n,o,"");for(const o in r)o==="display"&&(s=!0),sl(n,o,r[o])}else if(i){if(t!==r){const o=n[k_];o&&(r+=";"+o),n.cssText=r,s=MC.test(r)}}else t&&e.removeAttribute("style");Il in e&&(e[Il]=s?n.display:"",e[A_]&&(n.display="none"))}const wm=/\s*!important$/;function sl(e,t,r){if(nr(r))r.forEach(n=>sl(e,t,n));else if(r==null&&(r=""),t.startsWith("--"))e.setProperty(t,r);else{const n=VC(e,t);wm.test(r)?e.setProperty(Wn(n),r.replace(wm,""),"important"):e[n]=r}}const Em=["Webkit","Moz","ms"],wu={};function VC(e,t){const r=wu[t];if(r)return r;let n=rr(t);if(n!=="filter"&&n in e)return wu[t]=n;n=EC(n);for(let i=0;i<Em.length;i++){const s=Em[i]+n;if(s in e)return wu[t]=s}return t}const xm="http://www.w3.org/1999/xlink";function Sm(e,t,r,n,i,s=CC(t)){n&&t.startsWith("xlink:")?r==null?e.removeAttributeNS(xm,t.slice(6,t.length)):e.setAttributeNS(xm,t,r):r==null||s&&!y_(r)?e.removeAttribute(t):e.setAttribute(t,s?"":ph(r)?String(r):r)}function Cm(e,t,r,n,i){if(t==="innerHTML"||t==="textContent"){r!=null&&(e[t]=t==="innerHTML"?__(r):r);return}const s=e.tagName;if(t==="value"&&s!=="PROGRESS"&&!s.includes("-")){const a=s==="OPTION"?e.getAttribute("value")||"":e.value,l=r==null?e.type==="checkbox"?"on":"":String(r);(a!==l||!("_value"in e))&&(e.value=l),r==null&&e.removeAttribute(t),e._value=r;return}let o=!1;if(r===""||r==null){const a=typeof e[t];a==="boolean"?r=y_(r):r==null&&a==="string"?(r="",o=!0):a==="number"&&(r=0,o=!0)}try{e[t]=r}catch{}o&&e.removeAttribute(i||t)}function hn(e,t,r,n){e.addEventListener(t,r,n)}function BC(e,t,r,n){e.removeEventListener(t,r,n)}const Am=Symbol("_vei");function UC(e,t,r,n,i=null){const s=e[Am]||(e[Am]={}),o=s[t];if(n&&o)o.value=n;else{const[a,l]=zC(t);if(n){const u=s[t]=jC(n,i);hn(e,a,u,l)}else o&&(BC(e,a,o,l),s[t]=void 0)}}const Tm=/(?:Once|Passive|Capture)$/;function zC(e){let t;if(Tm.test(e)){t={};let n;for(;n=e.match(Tm);)e=e.slice(0,e.length-n[0].length),t[n[0].toLowerCase()]=!0}return[e[2]===":"?e.slice(3):Wn(e.slice(2)),t]}let Eu=0;const qC=Promise.resolve(),HC=()=>Eu||(qC.then(()=>Eu=0),Eu=Date.now());function jC(e,t){const r=n=>{if(!n._vts)n._vts=Date.now();else if(n._vts<=r.attached)return;pr(WC(n,r.value),t,5,[n])};return r.value=e,r.attached=HC(),r}function WC(e,t){if(nr(t)){const r=e.stopImmediatePropagation;return e.stopImmediatePropagation=()=>{r.call(e),e._stopped=!0},t.map(n=>i=>!i._stopped&&n&&n(i))}else return t}const km=e=>e.charCodeAt(0)===111&&e.charCodeAt(1)===110&&e.charCodeAt(2)>96&&e.charCodeAt(2)<123,O_=(e,t,r,n,i,s)=>{const o=i==="svg";t==="class"?LC(e,n,o):t==="style"?DC(e,r,n):mC(t)?gC(t)||UC(e,t,r,n,s):(t[0]==="."?(t=t.slice(1),!0):t[0]==="^"?(t=t.slice(1),!1):KC(e,t,n,o))?(Cm(e,t,n),!e.tagName.includes("-")&&(t==="value"||t==="checked"||t==="selected")&&Sm(e,t,n,o,s,t!=="value")):e._isVueCE&&(/[A-Z]/.test(t)||!hs(n))?Cm(e,il(t),n,s,t):(t==="true-value"?e._trueValue=n:t==="false-value"&&(e._falseValue=n),Sm(e,t,n,o))};function KC(e,t,r,n){if(n)return!!(t==="innerHTML"||t==="textContent"||t in e&&km(t)&&b_(r));if(t==="spellcheck"||t==="draggable"||t==="translate"||t==="autocorrect"||t==="sandbox"&&e.tagName==="IFRAME"||t==="form"||t==="list"&&e.tagName==="INPUT"||t==="type"&&e.tagName==="TEXTAREA")return!1;if(t==="width"||t==="height"){const i=e.tagName;if(i==="IMG"||i==="VIDEO"||i==="CANVAS"||i==="SOURCE")return!1}return km(t)&&hs(r)?!1:t in e}const Om={};function N_(e,t,r){let n=Mi(e,t);v_(n)&&(n=Jn({},n,t));class i extends Pc{constructor(o){super(n,o,r)}}return i.def=n,i}const GC=((e,t)=>N_(e,t,$f)),YC=typeof HTMLElement<"u"?HTMLElement:class{};class Pc extends YC{constructor(t,r={},n=Ml){super(),this._def=t,this._props=r,this._createApp=n,this._isVueCE=!0,this._instance=null,this._app=null,this._nonce=this._def.nonce,this._connected=!1,this._resolved=!1,this._patching=!1,this._dirty=!1,this._numberProps=null,this._styleChildren=new WeakSet,this._ob=null,this.shadowRoot&&n!==Ml?this._root=this.shadowRoot:t.shadowRoot!==!1?(this.attachShadow(Jn({},t.shadowRootOptions,{mode:"open"})),this._root=this.shadowRoot):this._root=this}connectedCallback(){if(!this.isConnected)return;!this.shadowRoot&&!this._resolved&&this._parseSlots(),this._connected=!0;let t=this;for(;t=t&&(t.parentNode||t.host);)if(t instanceof Pc){this._parent=t;break}this._instance||(this._resolved?this._mount(this._def):t&&t._pendingResolve?this._pendingResolve=t._pendingResolve.then(()=>{this._pendingResolve=void 0,this._resolveDef()}):this._resolveDef())}_setParent(t=this._parent){t&&(this._instance.parent=t._instance,this._inheritParentContext(t))}_inheritParentContext(t=this._parent){t&&this._app&&Object.setPrototypeOf(this._app._context.provides,t._instance.provides)}disconnectedCallback(){this._connected=!1,Ec(()=>{this._connected||(this._ob&&(this._ob.disconnect(),this._ob=null),this._app&&this._app.unmount(),this._instance&&(this._instance.ce=void 0),this._app=this._instance=null,this._teleportTargets&&(this._teleportTargets.clear(),this._teleportTargets=void 0))})}_processMutations(t){for(const r of t)this._setAttr(r.attributeName)}_resolveDef(){if(this._pendingResolve)return;for(let n=0;n<this.attributes.length;n++)this._setAttr(this.attributes[n].name);this._ob=new MutationObserver(this._processMutations.bind(this)),this._ob.observe(this,{attributes:!0});const t=(n,i=!1)=>{this._resolved=!0,this._pendingResolve=void 0;const{props:s,styles:o}=n;let a;if(s&&!nr(s))for(const l in s){const u=s[l];(u===Number||u&&u.type===Number)&&(l in this._props&&(this._props[l]=gh(this._props[l])),(a||(a=Object.create(null)))[il(l)]=!0)}this._numberProps=a,this._resolveProps(n),this.shadowRoot&&this._applyStyles(o),this._mount(n)},r=this._def.__asyncLoader;r?this._pendingResolve=r().then(n=>{n.configureApp=this._def.configureApp,t(this._def=n,!0)}):t(this._def)}_mount(t){this._app=this._createApp(t),this._inheritParentContext(),t.configureApp&&t.configureApp(this._app),this._app._ceVNode=this._createVNode(),this._app.mount(this._root);const r=this._instance&&this._instance.exposed;if(r)for(const n in r)vC(this,n)||Object.defineProperty(this,n,{get:()=>ea(r[n])})}_resolveProps(t){const{props:r}=t,n=nr(r)?r:Object.keys(r||{});for(const i of Object.keys(this))i[0]!=="_"&&n.includes(i)&&this._setProp(i,this[i]);for(const i of n.map(il))Object.defineProperty(this,i,{get(){return this._getProp(i)},set(s){this._setProp(i,s,!0,!this._patching)}})}_setAttr(t){if(t.startsWith("data-v-"))return;const r=this.hasAttribute(t);let n=r?this.getAttribute(t):Om;const i=il(t);r&&this._numberProps&&this._numberProps[i]&&(n=gh(n)),this._setProp(i,n,!1,!0)}_getProp(t){return this._props[t]}_setProp(t,r,n=!0,i=!1){if(r!==this._props[t]&&(this._dirty=!0,r===Om?delete this._props[t]:(this._props[t]=r,t==="key"&&this._app&&(this._app._ceVNode.key=r)),i&&this._instance&&this._update(),n)){const s=this._ob;s&&(this._processMutations(s.takeRecords()),s.disconnect()),r===!0?this.setAttribute(Wn(t),""):typeof r=="string"||typeof r=="number"?this.setAttribute(Wn(t),r+""):r||this.removeAttribute(Wn(t)),s&&s.observe(this,{attributes:!0})}}_update(){const t=this._createVNode();this._app&&(t.appContext=this._app._context),U_(t,this._root)}_createVNode(){const t={};this.shadowRoot||(t.onVnodeMounted=t.onVnodeUpdated=this._renderSlots.bind(this));const r=ze(this._def,Jn(t,this._props));return this._instance||(r.ce=n=>{this._instance=n,n.ce=this,n.isCE=!0;const i=(s,o)=>{this.dispatchEvent(new CustomEvent(s,v_(o[0])?Jn({detail:o},o[0]):{detail:o}))};n.emit=(s,...o)=>{i(s,o),Wn(s)!==s&&i(Wn(s),o)},this._setParent()}),r}_applyStyles(t,r){if(!t)return;if(r){if(r===this._def||this._styleChildren.has(r))return;this._styleChildren.add(r)}const n=this._nonce;for(let i=t.length-1;i>=0;i--){const s=document.createElement("style");n&&s.setAttribute("nonce",n),s.textContent=t[i],this.shadowRoot.prepend(s)}}_parseSlots(){const t=this._slots={};let r;for(;r=this.firstChild;){const n=r.nodeType===1&&r.getAttribute("slot")||"default";(t[n]||(t[n]=[])).push(r),this.removeChild(r)}}_renderSlots(){const t=this._getSlots(),r=this._instance.type.__scopeId;for(let n=0;n<t.length;n++){const i=t[n],s=i.getAttribute("name")||"default",o=this._slots[s],a=i.parentNode;if(o)for(const l of o){if(r&&l.nodeType===1){const u=r+"-s",c=document.createTreeWalker(l,1);l.setAttribute(u,"");let d;for(;d=c.nextNode();)d.setAttribute(u,"")}a.insertBefore(l,i)}else for(;i.firstChild;)a.insertBefore(i.firstChild,i);a.removeChild(i)}}_getSlots(){const t=[this];this._teleportTargets&&t.push(...this._teleportTargets);const r=new Set;for(const n of t){const i=n.querySelectorAll("slot");for(let s=0;s<i.length;s++)r.add(i[s])}return Array.from(r)}_injectChildStyle(t){this._applyStyles(t.styles,t)}_beginPatch(){this._patching=!0,this._dirty=!1}_endPatch(){this._patching=!1,this._dirty&&this._instance&&this._update()}_removeChildStyle(t){}}function P_(e){const t=zt(),r=t&&t.ce;return r||null}function XC(){const e=P_();return e&&e.shadowRoot}function JC(e="$style"){{const t=zt();if(!t)return yu;const r=t.type.__cssModules;if(!r)return yu;const n=r[e];return n||yu}}const F_=new WeakMap,I_=new WeakMap,Rl=Symbol("_moveCb"),Nm=Symbol("_enterCb"),QC=e=>(delete e.props.mode,e),ZC=QC({name:"TransitionGroup",props:Jn({},x_,{tag:String,moveClass:String}),setup(e,{slots:t}){const r=zt(),n=bf();let i,s;return Ac(()=>{if(!i.length)return;const o=e.moveClass||`${e.name||"v"}-move`;if(!iA(i[0].el,r.vnode.el,o)){i=[];return}i.forEach(tA),i.forEach(rA);const a=i.filter(nA);vh(r.vnode.el),a.forEach(l=>{const u=l.el,c=u.style;Dr(u,o),c.transform=c.webkitTransform=c.transitionDuration="";const d=u[Rl]=p=>{p&&p.target!==u||(!p||p.propertyName.endsWith("transform"))&&(u.removeEventListener("transitionend",d),u[Rl]=null,Bn(u,o))};u.addEventListener("transitionend",d)}),i=[]}),()=>{const o=ye(e),a=S_(o);let l=o.tag||st;if(i=[],s)for(let u=0;u<s.length;u++){const c=s[u];c.el&&c.el instanceof Element&&(i.push(c),En(c,us(c,a,n,r)),F_.set(c,{left:c.el.offsetLeft,top:c.el.offsetTop}))}s=t.default?Sc(t.default()):[];for(let u=0;u<s.length;u++){const c=s[u];c.key!=null&&En(c,us(c,a,n,r))}return ze(l,null,s)}}}),eA=ZC;function tA(e){const t=e.el;t[Rl]&&t[Rl](),t[Nm]&&t[Nm]()}function rA(e){I_.set(e,{left:e.el.offsetLeft,top:e.el.offsetTop})}function nA(e){const t=F_.get(e),r=I_.get(e),n=t.left-r.left,i=t.top-r.top;if(n||i){const s=e.el.style;return s.transform=s.webkitTransform=`translate(${n}px,${i}px)`,s.transitionDuration="0s",e}}function iA(e,t,r){const n=e.cloneNode(),i=e[fs];i&&i.forEach(a=>{a.split(/\s+/).forEach(l=>l&&n.classList.remove(l))}),r.split(/\s+/).forEach(a=>a&&n.classList.add(a)),n.style.display="none";const s=t.nodeType===1?t:t.parentNode;s.appendChild(n);const{hasTransform:o}=C_(n);return s.removeChild(n),o}const ri=e=>{const t=e.props["onUpdate:modelValue"]||!1;return nr(t)?r=>xC(t,r):t};function sA(e){e.target.composing=!0}function Pm(e){const t=e.target;t.composing&&(t.composing=!1,t.dispatchEvent(new Event("input")))}const fr=Symbol("_assign");function Fm(e,t,r){return t&&(e=e.trim()),r&&(e=If(e)),e}const $l={created(e,{modifiers:{lazy:t,trim:r,number:n}},i){e[fr]=ri(i);const s=n||i.props&&i.props.type==="number";hn(e,t?"change":"input",o=>{o.target.composing||e[fr](Fm(e.value,r,s))}),(r||s)&&hn(e,"change",()=>{e.value=Fm(e.value,r,s)}),t||(hn(e,"compositionstart",sA),hn(e,"compositionend",Pm),hn(e,"change",Pm))},mounted(e,{value:t}){e.value=t??""},beforeUpdate(e,{value:t,oldValue:r,modifiers:{lazy:n,trim:i,number:s}},o){if(e[fr]=ri(o),e.composing)return;const a=(s||e.type==="number")&&!/^0\d/.test(e.value)?If(e.value):e.value,l=t??"";a!==l&&(document.activeElement===e&&e.type!=="range"&&(n&&t===r||i&&e.value.trim()===l)||(e.value=l))}},Lf={deep:!0,created(e,t,r){e[fr]=ri(r),hn(e,"change",()=>{const n=e._modelValue,i=ps(e),s=e.checked,o=e[fr];if(nr(n)){const a=Nc(n,i),l=a!==-1;if(s&&!l)o(n.concat(i));else if(!s&&l){const u=[...n];u.splice(a,1),o(u)}}else if(aa(n)){const a=new Set(n);s?a.add(i):a.delete(i),o(a)}else o(R_(e,s))})},mounted:Im,beforeUpdate(e,t,r){e[fr]=ri(r),Im(e,t,r)}};function Im(e,{value:t,oldValue:r},n){e._modelValue=t;let i;if(nr(t))i=Nc(t,n.props.value)>-1;else if(aa(t))i=t.has(n.props.value);else{if(t===r)return;i=ti(t,R_(e,!0))}e.checked!==i&&(e.checked=i)}const Rf={created(e,{value:t},r){e.checked=ti(t,r.props.value),e[fr]=ri(r),hn(e,"change",()=>{e[fr](ps(e))})},beforeUpdate(e,{value:t,oldValue:r},n){e[fr]=ri(n),t!==r&&(e.checked=ti(t,n.props.value))}},L_={deep:!0,created(e,{value:t,modifiers:{number:r}},n){const i=aa(t);hn(e,"change",()=>{const s=Array.prototype.filter.call(e.options,o=>o.selected).map(o=>r?If(ps(o)):ps(o));e[fr](e.multiple?i?new Set(s):s:s[0]),e._assigning=!0,Ec(()=>{e._assigning=!1})}),e[fr]=ri(n)},mounted(e,{value:t}){Lm(e,t)},beforeUpdate(e,t,r){e[fr]=ri(r)},updated(e,{value:t}){e._assigning||Lm(e,t)}};function Lm(e,t){const r=e.multiple,n=nr(t);if(!(r&&!n&&!aa(t))){for(let i=0,s=e.options.length;i<s;i++){const o=e.options[i],a=ps(o);if(r)if(n){const l=typeof a;l==="string"||l==="number"?o.selected=t.some(u=>String(u)===String(a)):o.selected=Nc(t,a)>-1}else o.selected=t.has(a);else if(ti(ps(o),t)){e.selectedIndex!==i&&(e.selectedIndex=i);return}}!r&&e.selectedIndex!==-1&&(e.selectedIndex=-1)}}function ps(e){return"_value"in e?e._value:e.value}function R_(e,t){const r=t?"_trueValue":"_falseValue";return r in e?e[r]:t}const $_={created(e,t,r){Ma(e,t,r,null,"created")},mounted(e,t,r){Ma(e,t,r,null,"mounted")},beforeUpdate(e,t,r,n){Ma(e,t,r,n,"beforeUpdate")},updated(e,t,r,n){Ma(e,t,r,n,"updated")}};function M_(e,t){switch(e){case"SELECT":return L_;case"TEXTAREA":return $l;default:switch(t){case"checkbox":return Lf;case"radio":return Rf;default:return $l}}}function Ma(e,t,r,n,i){const o=M_(e.tagName,r.props&&r.props.type)[i];o&&o(e,t,r,n)}function oA(){$l.getSSRProps=({value:e})=>({value:e}),Rf.getSSRProps=({value:e},t)=>{if(t.props&&ti(t.props.value,e))return{checked:!0}},Lf.getSSRProps=({value:e},t)=>{if(nr(e)){if(t.props&&Nc(e,t.props.value)>-1)return{checked:!0}}else if(aa(e)){if(t.props&&e.has(t.props.value))return{checked:!0}}else if(e)return{checked:!0}},$_.getSSRProps=(e,t)=>{if(typeof t.type!="string")return;const r=M_(t.type.toUpperCase(),t.props&&t.props.type);if(r.getSSRProps)return r.getSSRProps(e,t)}}const aA=["ctrl","shift","alt","meta"],lA={stop:e=>e.stopPropagation(),prevent:e=>e.preventDefault(),self:e=>e.target!==e.currentTarget,ctrl:e=>!e.ctrlKey,shift:e=>!e.shiftKey,alt:e=>!e.altKey,meta:e=>!e.metaKey,left:e=>"button"in e&&e.button!==0,middle:e=>"button"in e&&e.button!==1,right:e=>"button"in e&&e.button!==2,exact:(e,t)=>aA.some(r=>e[`${r}Key`]&&!t.includes(r))},cA=(e,t)=>{const r=e._withMods||(e._withMods={}),n=t.join(".");return r[n]||(r[n]=((i,...s)=>{for(let o=0;o<t.length;o++){const a=lA[t[o]];if(a&&a(i,t))return}return e(i,...s)}))},uA={esc:"escape",space:" ",up:"arrow-up",left:"arrow-left",right:"arrow-right",down:"arrow-down",delete:"backspace"},dA=(e,t)=>{const r=e._withKeys||(e._withKeys={}),n=t.join(".");return r[n]||(r[n]=(i=>{if(!("key"in i))return;const s=Wn(i.key);if(t.some(o=>o===s||uA[o]===s))return e(i)}))},D_=Jn({patchProp:O_},w_);let vo,Rm=!1;function V_(){return vo||(vo=Xy(D_))}function B_(){return vo=Rm?vo:Jy(D_),Rm=!0,vo}const U_=((...e)=>{V_().render(...e)}),hA=((...e)=>{B_().hydrate(...e)}),Ml=((...e)=>{const t=V_().createApp(...e),{mount:r}=t;return t.mount=n=>{const i=q_(n);if(!i)return;const s=t._component;!b_(s)&&!s.render&&!s.template&&(s.template=i.innerHTML),i.nodeType===1&&(i.textContent="");const o=r(i,!1,z_(i));return i instanceof Element&&(i.removeAttribute("v-cloak"),i.setAttribute("data-v-app","")),o},t}),$f=((...e)=>{const t=B_().createApp(...e),{mount:r}=t;return t.mount=n=>{const i=q_(n);if(i)return r(i,!0,z_(i))},t});function z_(e){if(e instanceof SVGElement)return"svg";if(typeof MathMLElement=="function"&&e instanceof MathMLElement)return"mathml"}function q_(e){return hs(e)?document.querySelector(e):e}let $m=!1;const fA=()=>{$m||($m=!0,oA(),RC())},pA=Object.freeze(Object.defineProperty({__proto__:null,BaseTransition:Ay,BaseTransitionPropsValidators:vf,Comment:Ye,DeprecationTypes:hC,EffectScope:af,ErrorCodes:gx,ErrorTypeStrings:sC,Fragment:st,KeepAlive:Kx,ReactiveEffect:Oo,Static:Si,Suspense:VS,Teleport:Ox,Text:Xn,TrackOpTypes:W1,Transition:PC,TransitionGroup:eA,TriggerOpTypes:K1,VueElement:Pc,assertNumber:mx,callWithAsyncErrorHandling:pr,callWithErrorHandling:Os,camelize:rr,capitalize:_c,cloneVNode:Xr,compatUtils:dC,computed:De,createApp:Ml,createBlock:Nl,createCommentVNode:YS,createElementBlock:jS,createElementVNode:kf,createHydrationRenderer:Jy,createPropsRestProxy:mS,createRenderer:Xy,createSSRApp:$f,createSlots:eS,createStaticVNode:GS,createTextVNode:Of,createVNode:ze,customRef:Zv,defineAsyncComponent:jx,defineComponent:Mi,defineCustomElement:N_,defineEmits:sS,defineExpose:oS,defineModel:cS,defineOptions:aS,defineProps:iS,defineSSRCustomElement:GC,defineSlots:lS,devtools:oC,effect:p1,effectScope:d1,getCurrentInstance:zt,getCurrentScope:Rv,getCurrentWatcher:G1,getTransitionRawChildren:Sc,guardReactiveProps:a_,h:Wr,handleError:$i,hasInjectionContext:Sx,hydrate:hA,hydrateOnIdle:Vx,hydrateOnInteraction:qx,hydrateOnMediaQuery:zx,hydrateOnVisible:Ux,initCustomFormatter:rC,initDirectivesForSSR:fA,inject:po,isMemoSame:m_,isProxy:Zo,isReactive:mn,isReadonly:Yr,isRef:et,isRuntimeOnly:ZS,isShallow:Ut,isVNode:xn,markRaw:El,mergeDefaults:fS,mergeModels:pS,mergeProps:l_,nextTick:Ec,nodeOps:w_,normalizeClass:ra,normalizeProps:hx,normalizeStyle:ta,onActivated:ky,onBeforeMount:Py,onBeforeUnmount:Tc,onBeforeUpdate:_f,onDeactivated:Oy,onErrorCaptured:Ry,onMounted:Ns,onRenderTracked:Ly,onRenderTriggered:Iy,onScopeDispose:h1,onServerPrefetch:Fy,onUnmounted:sa,onUpdated:Ac,onWatcherCleanup:ty,openBlock:Mo,patchProp:O_,popScopeId:wx,provide:my,proxyRefs:hf,pushScopeId:_x,queuePostFlushCb:Fo,reactive:Ts,readonly:wl,ref:Hr,registerRuntimeCompiler:h_,render:U_,renderList:Zx,renderSlot:tS,resolveComponent:Xx,resolveDirective:Qx,resolveDynamicComponent:Jx,resolveFilter:uC,resolveTransitionHooks:us,setBlockTracking:Do,setDevtoolsHook:aC,setTransitionHooks:En,shallowReactive:Jv,shallowReadonly:L1,shallowRef:df,ssrContextKey:gy,ssrUtils:cC,stop:m1,toDisplayString:ly,toHandlerKey:ho,toHandlers:rS,toRaw:ye,toRef:q1,toRefs:B1,toValue:M1,transformVNodeArgs:WS,triggerRef:$1,unref:ea,useAttrs:hS,useCssModule:JC,useCssVars:$C,useHost:P_,useId:Px,useModel:SS,useSSRContext:by,useShadowRoot:XC,useSlots:dS,useTemplateRef:Fx,useTransitionState:bf,vModelCheckbox:Lf,vModelDynamic:$_,vModelRadio:Rf,vModelSelect:L_,vModelText:$l,vShow:T_,version:g_,warn:iC,watch:Ei,watchEffect:Cx,watchPostEffect:Ax,watchSyncEffect:vy,withAsyncContext:gS,withCtx:gf,withDefaults:uS,withDirectives:xx,withKeys:dA,withMemo:nC,withModifiers:cA,withScopeId:Ex},Symbol.toStringTag,{value:"Module"}));function Zr(e){const t=Object.create(null);for(const r of e.split(","))t[r]=1;return r=>r in t}const mA={},yo=()=>{},Da=()=>!1,H_=e=>e.charCodeAt(0)===111&&e.charCodeAt(1)===110&&(e.charCodeAt(2)>122||e.charCodeAt(2)<97),bn=Object.assign,Gn=Array.isArray,mt=e=>typeof e=="string",Mf=e=>typeof e=="symbol",gA=e=>e!==null&&typeof e=="object",Mm=Zr(",key,ref,ref_for,ref_key,onVnodeBeforeMount,onVnodeMounted,onVnodeBeforeUpdate,onVnodeUpdated,onVnodeBeforeUnmount,onVnodeUnmounted"),bA=Zr("bind,cloak,else-if,else,for,html,if,model,on,once,pre,show,slot,text,memo"),Df=e=>{const t=Object.create(null);return(r=>t[r]||(t[r]=e(r)))},vA=/-\w/g,Qn=Df(e=>e.replace(vA,t=>t.slice(1).toUpperCase())),Vf=Df(e=>e.charAt(0).toUpperCase()+e.slice(1)),yA=Df(e=>e?`on${Vf(e)}`:"");function _A(e,t){return e+JSON.stringify(t,(r,n)=>typeof n=="function"?n.toString():n)}const wA=/;(?![^(]*\))/g,EA=/:([^]+)/,xA=/\/\*[^]*?\*\//g;function SA(e){const t={};return e.replace(xA,"").split(wA).forEach(r=>{if(r){const n=r.split(EA);n.length>1&&(t[n[0].trim()]=n[1].trim())}}),t}const CA="html,body,base,head,link,meta,style,title,address,article,aside,footer,header,hgroup,h1,h2,h3,h4,h5,h6,nav,section,div,dd,dl,dt,figcaption,figure,picture,hr,img,li,main,ol,p,pre,ul,a,b,abbr,bdi,bdo,br,cite,code,data,dfn,em,i,kbd,mark,q,rp,rt,ruby,s,samp,small,span,strong,sub,sup,time,u,var,wbr,area,audio,map,track,video,embed,object,param,source,canvas,script,noscript,del,ins,caption,col,colgroup,table,thead,tbody,td,th,tr,button,datalist,fieldset,form,input,label,legend,meter,optgroup,option,output,progress,select,textarea,details,dialog,menu,summary,template,blockquote,iframe,tfoot",AA="svg,animate,animateMotion,animateTransform,circle,clipPath,color-profile,defs,desc,discard,ellipse,feBlend,feColorMatrix,feComponentTransfer,feComposite,feConvolveMatrix,feDiffuseLighting,feDisplacementMap,feDistantLight,feDropShadow,feFlood,feFuncA,feFuncB,feFuncG,feFuncR,feGaussianBlur,feImage,feMerge,feMergeNode,feMorphology,feOffset,fePointLight,feSpecularLighting,feSpotLight,feTile,feTurbulence,filter,foreignObject,g,hatch,hatchpath,image,line,linearGradient,marker,mask,mesh,meshgradient,meshpatch,meshrow,metadata,mpath,path,pattern,polygon,polyline,radialGradient,rect,set,solidcolor,stop,switch,symbol,text,textPath,title,tspan,unknown,use,view",TA="annotation,annotation-xml,maction,maligngroup,malignmark,math,menclose,merror,mfenced,mfrac,mfraction,mglyph,mi,mlabeledtr,mlongdiv,mmultiscripts,mn,mo,mover,mpadded,mphantom,mprescripts,mroot,mrow,ms,mscarries,mscarry,msgroup,msline,mspace,msqrt,msrow,mstack,mstyle,msub,msubsup,msup,mtable,mtd,mtext,mtr,munder,munderover,none,semantics",kA="area,base,br,col,embed,hr,img,input,link,meta,param,source,track,wbr",OA=Zr(CA),NA=Zr(AA),PA=Zr(TA),FA=Zr(kA);const Vo=Symbol(""),_o=Symbol(""),Bf=Symbol(""),Dl=Symbol(""),j_=Symbol(""),Pi=Symbol(""),W_=Symbol(""),K_=Symbol(""),Uf=Symbol(""),zf=Symbol(""),la=Symbol(""),qf=Symbol(""),G_=Symbol(""),Hf=Symbol(""),jf=Symbol(""),Wf=Symbol(""),Kf=Symbol(""),Gf=Symbol(""),Yf=Symbol(""),Y_=Symbol(""),X_=Symbol(""),Fc=Symbol(""),Vl=Symbol(""),Xf=Symbol(""),Jf=Symbol(""),Bo=Symbol(""),ca=Symbol(""),Qf=Symbol(""),_h=Symbol(""),IA=Symbol(""),wh=Symbol(""),Bl=Symbol(""),LA=Symbol(""),RA=Symbol(""),Zf=Symbol(""),$A=Symbol(""),MA=Symbol(""),ep=Symbol(""),J_=Symbol(""),ms={[Vo]:"Fragment",[_o]:"Teleport",[Bf]:"Suspense",[Dl]:"KeepAlive",[j_]:"BaseTransition",[Pi]:"openBlock",[W_]:"createBlock",[K_]:"createElementBlock",[Uf]:"createVNode",[zf]:"createElementVNode",[la]:"createCommentVNode",[qf]:"createTextVNode",[G_]:"createStaticVNode",[Hf]:"resolveComponent",[jf]:"resolveDynamicComponent",[Wf]:"resolveDirective",[Kf]:"resolveFilter",[Gf]:"withDirectives",[Yf]:"renderList",[Y_]:"renderSlot",[X_]:"createSlots",[Fc]:"toDisplayString",[Vl]:"mergeProps",[Xf]:"normalizeClass",[Jf]:"normalizeStyle",[Bo]:"normalizeProps",[ca]:"guardReactiveProps",[Qf]:"toHandlers",[_h]:"camelize",[IA]:"capitalize",[wh]:"toHandlerKey",[Bl]:"setBlockTracking",[LA]:"pushScopeId",[RA]:"popScopeId",[Zf]:"withCtx",[$A]:"unref",[MA]:"isRef",[ep]:"withMemo",[J_]:"isMemoSame"};function DA(e){Object.getOwnPropertySymbols(e).forEach(t=>{ms[t]=e[t]})}const sr={start:{line:1,column:1,offset:0},end:{line:1,column:1,offset:0},source:""};function VA(e,t=""){return{type:0,source:t,children:e,helpers:new Set,components:[],directives:[],hoists:[],imports:[],cached:[],temps:0,codegenNode:void 0,loc:sr}}function Uo(e,t,r,n,i,s,o,a=!1,l=!1,u=!1,c=sr){return e&&(a?(e.helper(Pi),e.helper(vs(e.inSSR,u))):e.helper(bs(e.inSSR,u)),o&&e.helper(Gf)),{type:13,tag:t,props:r,children:n,patchFlag:i,dynamicProps:s,directives:o,isBlock:a,disableTracking:l,isComponent:u,loc:c}}function Ci(e,t=sr){return{type:17,loc:t,elements:e}}function dr(e,t=sr){return{type:15,loc:t,properties:e}}function Ze(e,t){return{type:16,loc:sr,key:mt(e)?ue(e,!0):e,value:t}}function ue(e,t=!1,r=sr,n=0){return{type:4,loc:r,content:e,isStatic:t,constType:t?3:n}}function kr(e,t=sr){return{type:8,loc:t,children:e}}function ot(e,t=[],r=sr){return{type:14,loc:r,callee:e,arguments:t}}function gs(e,t=void 0,r=!1,n=!1,i=sr){return{type:18,params:e,returns:t,newline:r,isSlot:n,loc:i}}function Eh(e,t,r,n=!0){return{type:19,test:e,consequent:t,alternate:r,newline:n,loc:sr}}function BA(e,t,r=!1,n=!1){return{type:20,index:e,value:t,needPauseTracking:r,inVOnce:n,needArraySpread:!1,loc:sr}}function UA(e){return{type:21,body:e,loc:sr}}function bs(e,t){return e||t?Uf:zf}function vs(e,t){return e||t?W_:K_}function tp(e,{helper:t,removeHelper:r,inSSR:n}){e.isBlock||(e.isBlock=!0,r(bs(n,e.isComponent)),t(Pi),t(vs(n,e.isComponent)))}const Dm=new Uint8Array([123,123]),Vm=new Uint8Array([125,125]);function Bm(e){return e>=97&&e<=122||e>=65&&e<=90}function Yt(e){return e===32||e===10||e===9||e===12||e===13}function Pn(e){return e===47||e===62||Yt(e)}function Ul(e){const t=new Uint8Array(e.length);for(let r=0;r<e.length;r++)t[r]=e.charCodeAt(r);return t}const Et={Cdata:new Uint8Array([67,68,65,84,65,91]),CdataEnd:new Uint8Array([93,93,62]),CommentEnd:new Uint8Array([45,45,62]),ScriptEnd:new Uint8Array([60,47,115,99,114,105,112,116]),StyleEnd:new Uint8Array([60,47,115,116,121,108,101]),TitleEnd:new Uint8Array([60,47,116,105,116,108,101]),TextareaEnd:new Uint8Array([60,47,116,101,120,116,97,114,101,97])};class zA{constructor(t,r){this.stack=t,this.cbs=r,this.state=1,this.buffer="",this.sectionStart=0,this.index=0,this.entityStart=0,this.baseState=1,this.inRCDATA=!1,this.inXML=!1,this.inVPre=!1,this.newlines=[],this.mode=0,this.delimiterOpen=Dm,this.delimiterClose=Vm,this.delimiterIndex=-1,this.currentSequence=void 0,this.sequenceIndex=0}get inSFCRoot(){return this.mode===2&&this.stack.length===0}reset(){this.state=1,this.mode=0,this.buffer="",this.sectionStart=0,this.index=0,this.baseState=1,this.inRCDATA=!1,this.currentSequence=void 0,this.newlines.length=0,this.delimiterOpen=Dm,this.delimiterClose=Vm}getPos(t){let r=1,n=t+1;const i=this.newlines.length;let s=-1;if(i>100){let o=-1,a=i;for(;o+1<a;){const l=o+a>>>1;this.newlines[l]<t?o=l:a=l}s=o}else for(let o=i-1;o>=0;o--)if(t>this.newlines[o]){s=o;break}return s>=0&&(r=s+2,n=t-this.newlines[s]),{column:n,line:r,offset:t}}peek(){return this.buffer.charCodeAt(this.index+1)}stateText(t){t===60?(this.index>this.sectionStart&&this.cbs.ontext(this.sectionStart,this.index),this.state=5,this.sectionStart=this.index):!this.inVPre&&t===this.delimiterOpen[0]&&(this.state=2,this.delimiterIndex=0,this.stateInterpolationOpen(t))}stateInterpolationOpen(t){if(t===this.delimiterOpen[this.delimiterIndex])if(this.delimiterIndex===this.delimiterOpen.length-1){const r=this.index+1-this.delimiterOpen.length;r>this.sectionStart&&this.cbs.ontext(this.sectionStart,r),this.state=3,this.sectionStart=r}else this.delimiterIndex++;else this.inRCDATA?(this.state=32,this.stateInRCDATA(t)):(this.state=1,this.stateText(t))}stateInterpolation(t){t===this.delimiterClose[0]&&(this.state=4,this.delimiterIndex=0,this.stateInterpolationClose(t))}stateInterpolationClose(t){t===this.delimiterClose[this.delimiterIndex]?this.delimiterIndex===this.delimiterClose.length-1?(this.cbs.oninterpolation(this.sectionStart,this.index+1),this.inRCDATA?this.state=32:this.state=1,this.sectionStart=this.index+1):this.delimiterIndex++:(this.state=3,this.stateInterpolation(t))}stateSpecialStartSequence(t){const r=this.sequenceIndex===this.currentSequence.length;if(!(r?Pn(t):(t|32)===this.currentSequence[this.sequenceIndex]))this.inRCDATA=!1;else if(!r){this.sequenceIndex++;return}this.sequenceIndex=0,this.state=6,this.stateInTagName(t)}stateInRCDATA(t){if(this.sequenceIndex===this.currentSequence.length){if(t===62||Yt(t)){const r=this.index-this.currentSequence.length;if(this.sectionStart<r){const n=this.index;this.index=r,this.cbs.ontext(this.sectionStart,r),this.index=n}this.sectionStart=r+2,this.stateInClosingTagName(t),this.inRCDATA=!1;return}this.sequenceIndex=0}(t|32)===this.currentSequence[this.sequenceIndex]?this.sequenceIndex+=1:this.sequenceIndex===0?this.currentSequence===Et.TitleEnd||this.currentSequence===Et.TextareaEnd&&!this.inSFCRoot?!this.inVPre&&t===this.delimiterOpen[0]&&(this.state=2,this.delimiterIndex=0,this.stateInterpolationOpen(t)):this.fastForwardTo(60)&&(this.sequenceIndex=1):this.sequenceIndex=+(t===60)}stateCDATASequence(t){t===Et.Cdata[this.sequenceIndex]?++this.sequenceIndex===Et.Cdata.length&&(this.state=28,this.currentSequence=Et.CdataEnd,this.sequenceIndex=0,this.sectionStart=this.index+1):(this.sequenceIndex=0,this.state=23,this.stateInDeclaration(t))}fastForwardTo(t){for(;++this.index<this.buffer.length;){const r=this.buffer.charCodeAt(this.index);if(r===10&&this.newlines.push(this.index),r===t)return!0}return this.index=this.buffer.length-1,!1}stateInCommentLike(t){t===this.currentSequence[this.sequenceIndex]?++this.sequenceIndex===this.currentSequence.length&&(this.currentSequence===Et.CdataEnd?this.cbs.oncdata(this.sectionStart,this.index-2):this.cbs.oncomment(this.sectionStart,this.index-2),this.sequenceIndex=0,this.sectionStart=this.index+1,this.state=1):this.sequenceIndex===0?this.fastForwardTo(this.currentSequence[0])&&(this.sequenceIndex=1):t!==this.currentSequence[this.sequenceIndex-1]&&(this.sequenceIndex=0)}startSpecial(t,r){this.enterRCDATA(t,r),this.state=31}enterRCDATA(t,r){this.inRCDATA=!0,this.currentSequence=t,this.sequenceIndex=r}stateBeforeTagName(t){t===33?(this.state=22,this.sectionStart=this.index+1):t===63?(this.state=24,this.sectionStart=this.index+1):Bm(t)?(this.sectionStart=this.index,this.mode===0?this.state=6:this.inSFCRoot?this.state=34:this.inXML?this.state=6:t===116?this.state=30:this.state=t===115?29:6):t===47?this.state=8:(this.state=1,this.stateText(t))}stateInTagName(t){Pn(t)&&this.handleTagName(t)}stateInSFCRootTagName(t){if(Pn(t)){const r=this.buffer.slice(this.sectionStart,this.index);r!=="template"&&this.enterRCDATA(Ul("</"+r),0),this.handleTagName(t)}}handleTagName(t){this.cbs.onopentagname(this.sectionStart,this.index),this.sectionStart=-1,this.state=11,this.stateBeforeAttrName(t)}stateBeforeClosingTagName(t){Yt(t)||(t===62?(this.state=1,this.sectionStart=this.index+1):(this.state=Bm(t)?9:27,this.sectionStart=this.index))}stateInClosingTagName(t){(t===62||Yt(t))&&(this.cbs.onclosetag(this.sectionStart,this.index),this.sectionStart=-1,this.state=10,this.stateAfterClosingTagName(t))}stateAfterClosingTagName(t){t===62&&(this.state=1,this.sectionStart=this.index+1)}stateBeforeAttrName(t){t===62?(this.cbs.onopentagend(this.index),this.inRCDATA?this.state=32:this.state=1,this.sectionStart=this.index+1):t===47?this.state=7:t===60&&this.peek()===47?(this.cbs.onopentagend(this.index),this.state=5,this.sectionStart=this.index):Yt(t)||this.handleAttrStart(t)}handleAttrStart(t){t===118&&this.peek()===45?(this.state=13,this.sectionStart=this.index):t===46||t===58||t===64||t===35?(this.cbs.ondirname(this.index,this.index+1),this.state=14,this.sectionStart=this.index+1):(this.state=12,this.sectionStart=this.index)}stateInSelfClosingTag(t){t===62?(this.cbs.onselfclosingtag(this.index),this.state=1,this.sectionStart=this.index+1,this.inRCDATA=!1):Yt(t)||(this.state=11,this.stateBeforeAttrName(t))}stateInAttrName(t){(t===61||Pn(t))&&(this.cbs.onattribname(this.sectionStart,this.index),this.handleAttrNameEnd(t))}stateInDirName(t){t===61||Pn(t)?(this.cbs.ondirname(this.sectionStart,this.index),this.handleAttrNameEnd(t)):t===58?(this.cbs.ondirname(this.sectionStart,this.index),this.state=14,this.sectionStart=this.index+1):t===46&&(this.cbs.ondirname(this.sectionStart,this.index),this.state=16,this.sectionStart=this.index+1)}stateInDirArg(t){t===61||Pn(t)?(this.cbs.ondirarg(this.sectionStart,this.index),this.handleAttrNameEnd(t)):t===91?this.state=15:t===46&&(this.cbs.ondirarg(this.sectionStart,this.index),this.state=16,this.sectionStart=this.index+1)}stateInDynamicDirArg(t){t===93?this.state=14:(t===61||Pn(t))&&(this.cbs.ondirarg(this.sectionStart,this.index+1),this.handleAttrNameEnd(t))}stateInDirModifier(t){t===61||Pn(t)?(this.cbs.ondirmodifier(this.sectionStart,this.index),this.handleAttrNameEnd(t)):t===46&&(this.cbs.ondirmodifier(this.sectionStart,this.index),this.sectionStart=this.index+1)}handleAttrNameEnd(t){this.sectionStart=this.index,this.state=17,this.cbs.onattribnameend(this.index),this.stateAfterAttrName(t)}stateAfterAttrName(t){t===61?this.state=18:t===47||t===62?(this.cbs.onattribend(0,this.sectionStart),this.sectionStart=-1,this.state=11,this.stateBeforeAttrName(t)):Yt(t)||(this.cbs.onattribend(0,this.sectionStart),this.handleAttrStart(t))}stateBeforeAttrValue(t){t===34?(this.state=19,this.sectionStart=this.index+1):t===39?(this.state=20,this.sectionStart=this.index+1):Yt(t)||(this.sectionStart=this.index,this.state=21,this.stateInAttrValueNoQuotes(t))}handleInAttrValue(t,r){(t===r||this.fastForwardTo(r))&&(this.cbs.onattribdata(this.sectionStart,this.index),this.sectionStart=-1,this.cbs.onattribend(r===34?3:2,this.index+1),this.state=11)}stateInAttrValueDoubleQuotes(t){this.handleInAttrValue(t,34)}stateInAttrValueSingleQuotes(t){this.handleInAttrValue(t,39)}stateInAttrValueNoQuotes(t){Yt(t)||t===62?(this.cbs.onattribdata(this.sectionStart,this.index),this.sectionStart=-1,this.cbs.onattribend(1,this.index),this.state=11,this.stateBeforeAttrName(t)):(t===39||t===60||t===61||t===96)&&this.cbs.onerr(18,this.index)}stateBeforeDeclaration(t){t===91?(this.state=26,this.sequenceIndex=0):this.state=t===45?25:23}stateInDeclaration(t){(t===62||this.fastForwardTo(62))&&(this.state=1,this.sectionStart=this.index+1)}stateInProcessingInstruction(t){(t===62||this.fastForwardTo(62))&&(this.cbs.onprocessinginstruction(this.sectionStart,this.index),this.state=1,this.sectionStart=this.index+1)}stateBeforeComment(t){t===45?(this.state=28,this.currentSequence=Et.CommentEnd,this.sequenceIndex=2,this.sectionStart=this.index+1):this.state=23}stateInSpecialComment(t){(t===62||this.fastForwardTo(62))&&(this.cbs.oncomment(this.sectionStart,this.index),this.state=1,this.sectionStart=this.index+1)}stateBeforeSpecialS(t){t===Et.ScriptEnd[3]?this.startSpecial(Et.ScriptEnd,4):t===Et.StyleEnd[3]?this.startSpecial(Et.StyleEnd,4):(this.state=6,this.stateInTagName(t))}stateBeforeSpecialT(t){t===Et.TitleEnd[3]?this.startSpecial(Et.TitleEnd,4):t===Et.TextareaEnd[3]?this.startSpecial(Et.TextareaEnd,4):(this.state=6,this.stateInTagName(t))}startEntity(){}stateInEntity(){}parse(t){for(this.buffer=t;this.index<this.buffer.length;){const r=this.buffer.charCodeAt(this.index);switch(r===10&&this.state!==33&&this.newlines.push(this.index),this.state){case 1:{this.stateText(r);break}case 2:{this.stateInterpolationOpen(r);break}case 3:{this.stateInterpolation(r);break}case 4:{this.stateInterpolationClose(r);break}case 31:{this.stateSpecialStartSequence(r);break}case 32:{this.stateInRCDATA(r);break}case 26:{this.stateCDATASequence(r);break}case 19:{this.stateInAttrValueDoubleQuotes(r);break}case 12:{this.stateInAttrName(r);break}case 13:{this.stateInDirName(r);break}case 14:{this.stateInDirArg(r);break}case 15:{this.stateInDynamicDirArg(r);break}case 16:{this.stateInDirModifier(r);break}case 28:{this.stateInCommentLike(r);break}case 27:{this.stateInSpecialComment(r);break}case 11:{this.stateBeforeAttrName(r);break}case 6:{this.stateInTagName(r);break}case 34:{this.stateInSFCRootTagName(r);break}case 9:{this.stateInClosingTagName(r);break}case 5:{this.stateBeforeTagName(r);break}case 17:{this.stateAfterAttrName(r);break}case 20:{this.stateInAttrValueSingleQuotes(r);break}case 18:{this.stateBeforeAttrValue(r);break}case 8:{this.stateBeforeClosingTagName(r);break}case 10:{this.stateAfterClosingTagName(r);break}case 29:{this.stateBeforeSpecialS(r);break}case 30:{this.stateBeforeSpecialT(r);break}case 21:{this.stateInAttrValueNoQuotes(r);break}case 7:{this.stateInSelfClosingTag(r);break}case 23:{this.stateInDeclaration(r);break}case 22:{this.stateBeforeDeclaration(r);break}case 25:{this.stateBeforeComment(r);break}case 24:{this.stateInProcessingInstruction(r);break}case 33:{this.stateInEntity();break}}this.index++}this.cleanup(),this.finish()}cleanup(){this.sectionStart!==this.index&&(this.state===1||this.state===32&&this.sequenceIndex===0?(this.cbs.ontext(this.sectionStart,this.index),this.sectionStart=this.index):(this.state===19||this.state===20||this.state===21)&&(this.cbs.onattribdata(this.sectionStart,this.index),this.sectionStart=this.index))}finish(){this.handleTrailingData(),this.cbs.onend()}handleTrailingData(){const t=this.buffer.length;this.sectionStart>=t||(this.state===28?this.currentSequence===Et.CdataEnd?this.cbs.oncdata(this.sectionStart,t):this.cbs.oncomment(this.sectionStart,t):this.state===6||this.state===11||this.state===18||this.state===17||this.state===12||this.state===13||this.state===14||this.state===15||this.state===16||this.state===20||this.state===19||this.state===21||this.state===9||this.cbs.ontext(this.sectionStart,t))}emitCodePoint(t,r){}}function Um(e,{compatConfig:t}){const r=t&&t[e];return e==="MODE"?r||3:r}function Ai(e,t){const r=Um("MODE",t),n=Um(e,t);return r===3?n===!0:n!==!1}function zo(e,t,r,...n){return Ai(e,t)}function rp(e){throw e}function Q_(e){}function Ve(e,t,r,n){const i=`https://vuejs.org/error-reference/#compiler-${e}`,s=new SyntaxError(String(i));return s.code=e,s.loc=t,s}const Vt=e=>e.type===4&&e.isStatic;function Z_(e){switch(e){case"Teleport":case"teleport":return _o;case"Suspense":case"suspense":return Bf;case"KeepAlive":case"keep-alive":return Dl;case"BaseTransition":case"base-transition":return j_}}const qA=/^$|^\d|[^\$\w\xA0-\uFFFF]/,np=e=>!qA.test(e),ew=/[A-Za-z_$\xA0-\uFFFF]/,HA=/[\.\?\w$\xA0-\uFFFF]/,jA=/\s+[.[]\s*|\s*[.[]\s+/g,tw=e=>e.type===4?e.content:e.loc.source,WA=e=>{const t=tw(e).trim().replace(jA,a=>a.trim());let r=0,n=[],i=0,s=0,o=null;for(let a=0;a<t.length;a++){const l=t.charAt(a);switch(r){case 0:if(l==="[")n.push(r),r=1,i++;else if(l==="(")n.push(r),r=2,s++;else if(!(a===0?ew:HA).test(l))return!1;break;case 1:l==="'"||l==='"'||l==="`"?(n.push(r),r=3,o=l):l==="["?i++:l==="]"&&(--i||(r=n.pop()));break;case 2:if(l==="'"||l==='"'||l==="`")n.push(r),r=3,o=l;else if(l==="(")s++;else if(l===")"){if(a===t.length-1)return!1;--s||(r=n.pop())}break;case 3:l===o&&(r=n.pop(),o=null);break}}return!i&&!s},rw=WA,KA=/^\s*(?:async\s*)?(?:\([^)]*?\)|[\w$_]+)\s*(?::[^=]+)?=>|^\s*(?:async\s+)?function(?:\s+[\w$]+)?\s*\(/,GA=e=>KA.test(tw(e)),YA=GA;function ur(e,t,r=!1){for(let n=0;n<e.props.length;n++){const i=e.props[n];if(i.type===7&&(r||i.exp)&&(mt(t)?i.name===t:t.test(i.name)))return i}}function Ic(e,t,r=!1,n=!1){for(let i=0;i<e.props.length;i++){const s=e.props[i];if(s.type===6){if(r)continue;if(s.name===t&&(s.value||n))return s}else if(s.name==="bind"&&(s.exp||n)&&gi(s.arg,t))return s}}function gi(e,t){return!!(e&&Vt(e)&&e.content===t)}function XA(e){return e.props.some(t=>t.type===7&&t.name==="bind"&&(!t.arg||t.arg.type!==4||!t.arg.isStatic))}function xu(e){return e.type===5||e.type===2}function zm(e){return e.type===7&&e.name==="pre"}function JA(e){return e.type===7&&e.name==="slot"}function zl(e){return e.type===1&&e.tagType===3}function ql(e){return e.type===1&&e.tagType===2}const QA=new Set([Bo,ca]);function nw(e,t=[]){if(e&&!mt(e)&&e.type===14){const r=e.callee;if(!mt(r)&&QA.has(r))return nw(e.arguments[0],t.concat(e))}return[e,t]}function Hl(e,t,r){let n,i=e.type===13?e.props:e.arguments[2],s=[],o;if(i&&!mt(i)&&i.type===14){const a=nw(i);i=a[0],s=a[1],o=s[s.length-1]}if(i==null||mt(i))n=dr([t]);else if(i.type===14){const a=i.arguments[0];!mt(a)&&a.type===15?qm(t,a)||a.properties.unshift(t):i.callee===Qf?n=ot(r.helper(Vl),[dr([t]),i]):i.arguments.unshift(dr([t])),!n&&(n=i)}else i.type===15?(qm(t,i)||i.properties.unshift(t),n=i):(n=ot(r.helper(Vl),[dr([t]),i]),o&&o.callee===ca&&(o=s[s.length-2]));e.type===13?o?o.arguments[0]=n:e.props=n:o?o.arguments[0]=n:e.arguments[2]=n}function qm(e,t){let r=!1;if(e.key.type===4){const n=e.key.content;r=t.properties.some(i=>i.key.type===4&&i.key.content===n)}return r}function qo(e,t){return`_${t}_${e.replace(/[^\w]/g,(r,n)=>r==="-"?"_":e.charCodeAt(n).toString())}`}function ZA(e){return e.type===14&&e.callee===ep?e.arguments[1].returns:e}const eT=/([\s\S]*?)\s+(?:in|of)\s+(\S[\s\S]*)/;function iw(e){for(let t=0;t<e.length;t++)if(!Yt(e.charCodeAt(t)))return!1;return!0}function ip(e){return e.type===2&&iw(e.content)||e.type===12&&ip(e.content)}function sw(e){return e.type===3||ip(e)}const ow={parseMode:"base",ns:0,delimiters:["{{","}}"],getNamespace:()=>0,isVoidTag:Da,isPreTag:Da,isIgnoreNewlineTag:Da,isCustomElement:Da,onError:rp,onWarn:Q_,comments:!1,prefixIdentifiers:!1};let xe=ow,Ho=null,vn="",St=null,me=null,Rt="",on=-1,pi=-1,sp=0,jn=!1,xh=null;const Be=[],Ke=new zA(Be,{onerr:nn,ontext(e,t){Va(bt(e,t),e,t)},ontextentity(e,t,r){Va(e,t,r)},oninterpolation(e,t){if(jn)return Va(bt(e,t),e,t);let r=e+Ke.delimiterOpen.length,n=t-Ke.delimiterClose.length;for(;Yt(vn.charCodeAt(r));)r++;for(;Yt(vn.charCodeAt(n-1));)n--;let i=bt(r,n);i.includes("&")&&(i=xe.decodeEntities(i,!1)),Sh({type:5,content:al(i,!1,Ge(r,n)),loc:Ge(e,t)})},onopentagname(e,t){const r=bt(e,t);St={type:1,tag:r,ns:xe.getNamespace(r,Be[0],xe.ns),tagType:0,props:[],children:[],loc:Ge(e-1,t),codegenNode:void 0}},onopentagend(e){jm(e)},onclosetag(e,t){const r=bt(e,t);if(!xe.isVoidTag(r)){let n=!1;for(let i=0;i<Be.length;i++)if(Be[i].tag.toLowerCase()===r.toLowerCase()){n=!0,i>0&&nn(24,Be[0].loc.start.offset);for(let o=0;o<=i;o++){const a=Be.shift();ol(a,t,o<i)}break}n||nn(23,aw(e,60))}},onselfclosingtag(e){const t=St.tag;St.isSelfClosing=!0,jm(e),Be[0]&&Be[0].tag===t&&ol(Be.shift(),e)},onattribname(e,t){me={type:6,name:bt(e,t),nameLoc:Ge(e,t),value:void 0,loc:Ge(e)}},ondirname(e,t){const r=bt(e,t),n=r==="."||r===":"?"bind":r==="@"?"on":r==="#"?"slot":r.slice(2);if(!jn&&n===""&&nn(26,e),jn||n==="")me={type:6,name:r,nameLoc:Ge(e,t),value:void 0,loc:Ge(e)};else if(me={type:7,name:n,rawName:r,exp:void 0,arg:void 0,modifiers:r==="."?[ue("prop")]:[],loc:Ge(e)},n==="pre"){jn=Ke.inVPre=!0,xh=St;const i=St.props;for(let s=0;s<i.length;s++)i[s].type===7&&(i[s]=uT(i[s]))}},ondirarg(e,t){if(e===t)return;const r=bt(e,t);if(jn&&!zm(me))me.name+=r,bi(me.nameLoc,t);else{const n=r[0]!=="[";me.arg=al(n?r:r.slice(1,-1),n,Ge(e,t),n?3:0)}},ondirmodifier(e,t){const r=bt(e,t);if(jn&&!zm(me))me.name+="."+r,bi(me.nameLoc,t);else if(me.name==="slot"){const n=me.arg;n&&(n.content+="."+r,bi(n.loc,t))}else{const n=ue(r,!0,Ge(e,t));me.modifiers.push(n)}},onattribdata(e,t){Rt+=bt(e,t),on<0&&(on=e),pi=t},onattribentity(e,t,r){Rt+=e,on<0&&(on=t),pi=r},onattribnameend(e){const t=me.loc.start.offset,r=bt(t,e);me.type===7&&(me.rawName=r),St.props.some(n=>(n.type===7?n.rawName:n.name)===r)&&nn(2,t)},onattribend(e,t){if(St&&me){if(bi(me.loc,t),e!==0)if(Rt.includes("&")&&(Rt=xe.decodeEntities(Rt,!0)),me.type===6)me.name==="class"&&(Rt=cw(Rt).trim()),e===1&&!Rt&&nn(13,t),me.value={type:2,content:Rt,loc:e===1?Ge(on,pi):Ge(on-1,pi+1)},Ke.inSFCRoot&&St.tag==="template"&&me.name==="lang"&&Rt&&Rt!=="html"&&Ke.enterRCDATA(Ul("</template"),0);else{let r=0;me.exp=al(Rt,!1,Ge(on,pi),0,r),me.name==="for"&&(me.forParseResult=rT(me.exp));let n=-1;me.name==="bind"&&(n=me.modifiers.findIndex(i=>i.content==="sync"))>-1&&zo("COMPILER_V_BIND_SYNC",xe,me.loc,me.arg.loc.source)&&(me.name="model",me.modifiers.splice(n,1))}(me.type!==7||me.name!=="pre")&&St.props.push(me)}Rt="",on=pi=-1},oncomment(e,t){xe.comments&&Sh({type:3,content:bt(e,t),loc:Ge(e-4,t+3)})},onend(){const e=vn.length;for(let t=0;t<Be.length;t++)ol(Be[t],e-1),nn(24,Be[t].loc.start.offset)},oncdata(e,t){Be[0].ns!==0?Va(bt(e,t),e,t):nn(1,e-9)},onprocessinginstruction(e){(Be[0]?Be[0].ns:xe.ns)===0&&nn(21,e-1)}}),Hm=/,([^,\}\]]*)(?:,([^,\}\]]*))?$/,tT=/^\(|\)$/g;function rT(e){const t=e.loc,r=e.content,n=r.match(eT);if(!n)return;const[,i,s]=n,o=(d,p,m=!1)=>{const h=t.start.offset+p,f=h+d.length;return al(d,!1,Ge(h,f),0,m?1:0)},a={source:o(s.trim(),r.indexOf(s,i.length)),value:void 0,key:void 0,index:void 0,finalized:!1};let l=i.trim().replace(tT,"").trim();const u=i.indexOf(l),c=l.match(Hm);if(c){l=l.replace(Hm,"").trim();const d=c[1].trim();let p;if(d&&(p=r.indexOf(d,u+l.length),a.key=o(d,p,!0)),c[2]){const m=c[2].trim();m&&(a.index=o(m,r.indexOf(m,a.key?p+d.length:u+l.length),!0))}}return l&&(a.value=o(l,u,!0)),a}function bt(e,t){return vn.slice(e,t)}function jm(e){Ke.inSFCRoot&&(St.innerLoc=Ge(e+1,e+1)),Sh(St);const{tag:t,ns:r}=St;r===0&&xe.isPreTag(t)&&sp++,xe.isVoidTag(t)?ol(St,e):(Be.unshift(St),(r===1||r===2)&&(Ke.inXML=!0)),St=null}function Va(e,t,r){{const s=Be[0]&&Be[0].tag;s!=="script"&&s!=="style"&&e.includes("&")&&(e=xe.decodeEntities(e,!1))}const n=Be[0]||Ho,i=n.children[n.children.length-1];i&&i.type===2?(i.content+=e,bi(i.loc,r)):n.children.push({type:2,content:e,loc:Ge(t,r)})}function ol(e,t,r=!1){r?bi(e.loc,aw(t,60)):bi(e.loc,nT(t,62)+1),Ke.inSFCRoot&&(e.children.length?e.innerLoc.end=bn({},e.children[e.children.length-1].loc.end):e.innerLoc.end=bn({},e.innerLoc.start),e.innerLoc.source=bt(e.innerLoc.start.offset,e.innerLoc.end.offset));const{tag:n,ns:i,children:s}=e;if(jn||(n==="slot"?e.tagType=2:Wm(e)?e.tagType=3:sT(e)&&(e.tagType=1)),Ke.inRCDATA||(e.children=lw(s)),i===0&&xe.isIgnoreNewlineTag(n)){const o=s[0];o&&o.type===2&&(o.content=o.content.replace(/^\r?\n/,""))}i===0&&xe.isPreTag(n)&&sp--,xh===e&&(jn=Ke.inVPre=!1,xh=null),Ke.inXML&&(Be[0]?Be[0].ns:xe.ns)===0&&(Ke.inXML=!1);{const o=e.props;if(!Ke.inSFCRoot&&Ai("COMPILER_NATIVE_TEMPLATE",xe)&&e.tag==="template"&&!Wm(e)){const l=Be[0]||Ho,u=l.children.indexOf(e);l.children.splice(u,1,...e.children)}const a=o.find(l=>l.type===6&&l.name==="inline-template");a&&zo("COMPILER_INLINE_TEMPLATE",xe,a.loc)&&e.children.length&&(a.value={type:2,content:bt(e.children[0].loc.start.offset,e.children[e.children.length-1].loc.end.offset),loc:a.loc})}}function nT(e,t){let r=e;for(;vn.charCodeAt(r)!==t&&r<vn.length-1;)r++;return r}function aw(e,t){let r=e;for(;vn.charCodeAt(r)!==t&&r>=0;)r--;return r}const iT=new Set(["if","else","else-if","for","slot"]);function Wm({tag:e,props:t}){if(e==="template"){for(let r=0;r<t.length;r++)if(t[r].type===7&&iT.has(t[r].name))return!0}return!1}function sT({tag:e,props:t}){if(xe.isCustomElement(e))return!1;if(e==="component"||oT(e.charCodeAt(0))||Z_(e)||xe.isBuiltInComponent&&xe.isBuiltInComponent(e)||xe.isNativeTag&&!xe.isNativeTag(e))return!0;for(let r=0;r<t.length;r++){const n=t[r];if(n.type===6){if(n.name==="is"&&n.value){if(n.value.content.startsWith("vue:"))return!0;if(zo("COMPILER_IS_ON_ELEMENT",xe,n.loc))return!0}}else if(n.name==="bind"&&gi(n.arg,"is")&&zo("COMPILER_IS_ON_ELEMENT",xe,n.loc))return!0}return!1}function oT(e){return e>64&&e<91}const aT=/\r\n/g;function lw(e){const t=xe.whitespace!=="preserve";let r=!1;for(let n=0;n<e.length;n++){const i=e[n];if(i.type===2)if(sp)i.content=i.content.replace(aT,`
`);else if(iw(i.content)){const s=e[n-1]&&e[n-1].type,o=e[n+1]&&e[n+1].type;!s||!o||t&&(s===3&&(o===3||o===1)||s===1&&(o===3||o===1&&lT(i.content)))?(r=!0,e[n]=null):i.content=" "}else t&&(i.content=cw(i.content))}return r?e.filter(Boolean):e}function lT(e){for(let t=0;t<e.length;t++){const r=e.charCodeAt(t);if(r===10||r===13)return!0}return!1}function cw(e){let t="",r=!1;for(let n=0;n<e.length;n++)Yt(e.charCodeAt(n))?r||(t+=" ",r=!0):(t+=e[n],r=!1);return t}function Sh(e){(Be[0]||Ho).children.push(e)}function Ge(e,t){return{start:Ke.getPos(e),end:t==null?t:Ke.getPos(t),source:t==null?t:bt(e,t)}}function cT(e){return Ge(e.start.offset,e.end.offset)}function bi(e,t){e.end=Ke.getPos(t),e.source=bt(e.start.offset,t)}function uT(e){const t={type:6,name:e.rawName,nameLoc:Ge(e.loc.start.offset,e.loc.start.offset+e.rawName.length),value:void 0,loc:e.loc};if(e.exp){const r=e.exp.loc;r.end.offset<e.loc.end.offset&&(r.start.offset--,r.start.column--,r.end.offset++,r.end.column++),t.value={type:2,content:e.exp.content,loc:r}}return t}function al(e,t=!1,r,n=0,i=0){return ue(e,t,r,n)}function nn(e,t,r){xe.onError(Ve(e,Ge(t,t)))}function dT(){Ke.reset(),St=null,me=null,Rt="",on=-1,pi=-1,Be.length=0}function hT(e,t){if(dT(),vn=e,xe=bn({},ow),t){let i;for(i in t)t[i]!=null&&(xe[i]=t[i])}Ke.mode=xe.parseMode==="html"?1:xe.parseMode==="sfc"?2:0,Ke.inXML=xe.ns===1||xe.ns===2;const r=t&&t.delimiters;r&&(Ke.delimiterOpen=Ul(r[0]),Ke.delimiterClose=Ul(r[1]));const n=Ho=VA([],e);return Ke.parse(vn),n.loc=Ge(0,e.length),n.children=lw(n.children),Ho=null,n}function fT(e,t){ll(e,void 0,t,!!uw(e))}function uw(e){const t=e.children.filter(r=>r.type!==3);return t.length===1&&t[0].type===1&&!ql(t[0])?t[0]:null}function ll(e,t,r,n=!1,i=!1){const{children:s}=e,o=[];for(let c=0;c<s.length;c++){const d=s[c];if(d.type===1&&d.tagType===0){const p=n?0:Qt(d,r);if(p>0){if(p>=2){d.codegenNode.patchFlag=-1,o.push(d);continue}}else{const m=d.codegenNode;if(m.type===13){const h=m.patchFlag;if((h===void 0||h===512||h===1)&&hw(d,r)>=2){const f=fw(d);f&&(m.props=r.hoist(f))}m.dynamicProps&&(m.dynamicProps=r.hoist(m.dynamicProps))}}}else if(d.type===12&&(n?0:Qt(d,r))>=2){d.codegenNode.type===14&&d.codegenNode.arguments.length>0&&d.codegenNode.arguments.push("-1"),o.push(d);continue}if(d.type===1){const p=d.tagType===1;p&&r.scopes.vSlot++,ll(d,e,r,!1,i),p&&r.scopes.vSlot--}else if(d.type===11)ll(d,e,r,d.children.length===1,!0);else if(d.type===9)for(let p=0;p<d.branches.length;p++)ll(d.branches[p],e,r,d.branches[p].children.length===1,i)}let a=!1;if(o.length===s.length&&e.type===1){if(e.tagType===0&&e.codegenNode&&e.codegenNode.type===13&&Gn(e.codegenNode.children))e.codegenNode.children=l(Ci(e.codegenNode.children)),a=!0;else if(e.tagType===1&&e.codegenNode&&e.codegenNode.type===13&&e.codegenNode.children&&!Gn(e.codegenNode.children)&&e.codegenNode.children.type===15){const c=u(e.codegenNode,"default");c&&(c.returns=l(Ci(c.returns)),a=!0)}else if(e.tagType===3&&t&&t.type===1&&t.tagType===1&&t.codegenNode&&t.codegenNode.type===13&&t.codegenNode.children&&!Gn(t.codegenNode.children)&&t.codegenNode.children.type===15){const c=ur(e,"slot",!0),d=c&&c.arg&&u(t.codegenNode,c.arg);d&&(d.returns=l(Ci(d.returns)),a=!0)}}if(!a)for(const c of o)c.codegenNode=r.cache(c.codegenNode);function l(c){const d=r.cache(c);return d.needArraySpread=!0,d}function u(c,d){if(c.children&&!Gn(c.children)&&c.children.type===15){const p=c.children.properties.find(m=>m.key===d||m.key.content===d);return p&&p.value}}o.length&&r.transformHoist&&r.transformHoist(s,r,e)}function Qt(e,t){const{constantCache:r}=t;switch(e.type){case 1:if(e.tagType!==0)return 0;const n=r.get(e);if(n!==void 0)return n;const i=e.codegenNode;if(i.type!==13||i.isBlock&&e.tag!=="svg"&&e.tag!=="foreignObject"&&e.tag!=="math")return 0;if(i.patchFlag===void 0){let o=3;const a=hw(e,t);if(a===0)return r.set(e,0),0;a<o&&(o=a);for(let l=0;l<e.children.length;l++){const u=Qt(e.children[l],t);if(u===0)return r.set(e,0),0;u<o&&(o=u)}if(o>1)for(let l=0;l<e.props.length;l++){const u=e.props[l];if(u.type===7&&u.name==="bind"&&u.exp){const c=Qt(u.exp,t);if(c===0)return r.set(e,0),0;c<o&&(o=c)}}if(i.isBlock){for(let l=0;l<e.props.length;l++)if(e.props[l].type===7)return r.set(e,0),0;t.removeHelper(Pi),t.removeHelper(vs(t.inSSR,i.isComponent)),i.isBlock=!1,t.helper(bs(t.inSSR,i.isComponent))}return r.set(e,o),o}else return r.set(e,0),0;case 2:case 3:return 3;case 9:case 11:case 10:return 0;case 5:case 12:return Qt(e.content,t);case 4:return e.constType;case 8:let s=3;for(let o=0;o<e.children.length;o++){const a=e.children[o];if(mt(a)||Mf(a))continue;const l=Qt(a,t);if(l===0)return 0;l<s&&(s=l)}return s;case 20:return 2;default:return 0}}const pT=new Set([Xf,Jf,Bo,ca]);function dw(e,t){if(e.type===14&&!mt(e.callee)&&pT.has(e.callee)){const r=e.arguments[0];if(r.type===4)return Qt(r,t);if(r.type===14)return dw(r,t)}return 0}function hw(e,t){let r=3;const n=fw(e);if(n&&n.type===15){const{properties:i}=n;for(let s=0;s<i.length;s++){const{key:o,value:a}=i[s],l=Qt(o,t);if(l===0)return l;l<r&&(r=l);let u;if(a.type===4?u=Qt(a,t):a.type===14?u=dw(a,t):u=0,u===0)return u;u<r&&(r=u)}}return r}function fw(e){const t=e.codegenNode;if(t.type===13)return t.props}function mT(e,{filename:t="",prefixIdentifiers:r=!1,hoistStatic:n=!1,hmr:i=!1,cacheHandlers:s=!1,nodeTransforms:o=[],directiveTransforms:a={},transformHoist:l=null,isBuiltInComponent:u=yo,isCustomElement:c=yo,expressionPlugins:d=[],scopeId:p=null,slotted:m=!0,ssr:h=!1,inSSR:f=!1,ssrCssVars:b="",bindingMetadata:y=mA,inline:w=!1,isTS:v=!1,onError:_=rp,onWarn:x=Q_,compatConfig:k}){const S=t.replace(/\?.*$/,"").match(/([^/\\]+)\.\w+$/),N={filename:t,selfName:S&&Vf(Qn(S[1])),prefixIdentifiers:r,hoistStatic:n,hmr:i,cacheHandlers:s,nodeTransforms:o,directiveTransforms:a,transformHoist:l,isBuiltInComponent:u,isCustomElement:c,expressionPlugins:d,scopeId:p,slotted:m,ssr:h,inSSR:f,ssrCssVars:b,bindingMetadata:y,inline:w,isTS:v,onError:_,onWarn:x,compatConfig:k,root:e,helpers:new Map,components:new Set,directives:new Set,hoists:[],imports:[],cached:[],constantCache:new WeakMap,temps:0,identifiers:Object.create(null),scopes:{vFor:0,vSlot:0,vPre:0,vOnce:0},parent:null,grandParent:null,currentNode:e,childIndex:0,inVOnce:!1,helper(A){const T=N.helpers.get(A)||0;return N.helpers.set(A,T+1),A},removeHelper(A){const T=N.helpers.get(A);if(T){const F=T-1;F?N.helpers.set(A,F):N.helpers.delete(A)}},helperString(A){return`_${ms[N.helper(A)]}`},replaceNode(A){N.parent.children[N.childIndex]=N.currentNode=A},removeNode(A){const T=N.parent.children,F=A?T.indexOf(A):N.currentNode?N.childIndex:-1;!A||A===N.currentNode?(N.currentNode=null,N.onNodeRemoved()):N.childIndex>F&&(N.childIndex--,N.onNodeRemoved()),N.parent.children.splice(F,1)},onNodeRemoved:yo,addIdentifiers(A){},removeIdentifiers(A){},hoist(A){mt(A)&&(A=ue(A)),N.hoists.push(A);const T=ue(`_hoisted_${N.hoists.length}`,!1,A.loc,2);return T.hoisted=A,T},cache(A,T=!1,F=!1){const C=BA(N.cached.length,A,T,F);return N.cached.push(C),C}};return N.filters=new Set,N}function gT(e,t){const r=mT(e,t);Lc(e,r),t.hoistStatic&&fT(e,r),t.ssr||bT(e,r),e.helpers=new Set([...r.helpers.keys()]),e.components=[...r.components],e.directives=[...r.directives],e.imports=r.imports,e.hoists=r.hoists,e.temps=r.temps,e.cached=r.cached,e.transformed=!0,e.filters=[...r.filters]}function bT(e,t){const{helper:r}=t,{children:n}=e;if(n.length===1){const i=uw(e);if(i&&i.codegenNode){const s=i.codegenNode;s.type===13&&tp(s,t),e.codegenNode=s}else e.codegenNode=n[0]}else if(n.length>1){let i=64;e.codegenNode=Uo(t,r(Vo),void 0,e.children,i,void 0,void 0,!0,void 0,!1)}}function vT(e,t){let r=0;const n=()=>{r--};for(;r<e.children.length;r++){const i=e.children[r];mt(i)||(t.grandParent=t.parent,t.parent=e,t.childIndex=r,t.onNodeRemoved=n,Lc(i,t))}}function Lc(e,t){t.currentNode=e;const{nodeTransforms:r}=t,n=[];for(let s=0;s<r.length;s++){const o=r[s](e,t);if(o&&(Gn(o)?n.push(...o):n.push(o)),t.currentNode)e=t.currentNode;else return}switch(e.type){case 3:t.ssr||t.helper(la);break;case 5:t.ssr||t.helper(Fc);break;case 9:for(let s=0;s<e.branches.length;s++)Lc(e.branches[s],t);break;case 10:case 11:case 1:case 0:vT(e,t);break}t.currentNode=e;let i=n.length;for(;i--;)n[i]()}function pw(e,t){const r=mt(e)?n=>n===e:n=>e.test(n);return(n,i)=>{if(n.type===1){const{props:s}=n;if(n.tagType===3&&s.some(JA))return;const o=[];for(let a=0;a<s.length;a++){const l=s[a];if(l.type===7&&r(l.name)){s.splice(a,1),a--;const u=t(n,l,i);u&&o.push(u)}}return o}}}const Rc="/*@__PURE__*/",mw=e=>`${ms[e]}: _${ms[e]}`;function yT(e,{mode:t="function",prefixIdentifiers:r=t==="module",sourceMap:n=!1,filename:i="template.vue.html",scopeId:s=null,optimizeImports:o=!1,runtimeGlobalName:a="Vue",runtimeModuleName:l="vue",ssrRuntimeModuleName:u="vue/server-renderer",ssr:c=!1,isTS:d=!1,inSSR:p=!1}){const m={mode:t,prefixIdentifiers:r,sourceMap:n,filename:i,scopeId:s,optimizeImports:o,runtimeGlobalName:a,runtimeModuleName:l,ssrRuntimeModuleName:u,ssr:c,isTS:d,inSSR:p,source:e.source,code:"",column:1,line:1,offset:0,indentLevel:0,pure:!1,map:void 0,helper(f){return`_${ms[f]}`},push(f,b=-2,y){m.code+=f},indent(){h(++m.indentLevel)},deindent(f=!1){f?--m.indentLevel:h(--m.indentLevel)},newline(){h(m.indentLevel)}};function h(f){m.push(`
`+"  ".repeat(f),0)}return m}function _T(e,t={}){const r=yT(e,t);t.onContextCreated&&t.onContextCreated(r);const{mode:n,push:i,prefixIdentifiers:s,indent:o,deindent:a,newline:l,scopeId:u,ssr:c}=r,d=Array.from(e.helpers),p=d.length>0,m=!s&&n!=="module";wT(e,r);const f=c?"ssrRender":"render",y=(c?["_ctx","_push","_parent","_attrs"]:["_ctx","_cache"]).join(", ");if(i(`function ${f}(${y}) {`),o(),m&&(i("with (_ctx) {"),o(),p&&(i(`const { ${d.map(mw).join(", ")} } = _Vue
`,-1),l())),e.components.length&&(Su(e.components,"component",r),(e.directives.length||e.temps>0)&&l()),e.directives.length&&(Su(e.directives,"directive",r),e.temps>0&&l()),e.filters&&e.filters.length&&(l(),Su(e.filters,"filter",r),l()),e.temps>0){i("let ");for(let w=0;w<e.temps;w++)i(`${w>0?", ":""}_temp${w}`)}return(e.components.length||e.directives.length||e.temps)&&(i(`
`,0),l()),c||i("return "),e.codegenNode?kt(e.codegenNode,r):i("null"),m&&(a(),i("}")),a(),i("}"),{ast:e,code:r.code,preamble:"",map:r.map?r.map.toJSON():void 0}}function wT(e,t){const{ssr:r,prefixIdentifiers:n,push:i,newline:s,runtimeModuleName:o,runtimeGlobalName:a,ssrRuntimeModuleName:l}=t,u=a,c=Array.from(e.helpers);if(c.length>0&&(i(`const _Vue = ${u}
`,-1),e.hoists.length)){const d=[Uf,zf,la,qf,G_].filter(p=>c.includes(p)).map(mw).join(", ");i(`const { ${d} } = _Vue
`,-1)}ET(e.hoists,t),s(),i("return ")}function Su(e,t,{helper:r,push:n,newline:i,isTS:s}){const o=r(t==="filter"?Kf:t==="component"?Hf:Wf);for(let a=0;a<e.length;a++){let l=e[a];const u=l.endsWith("__self");u&&(l=l.slice(0,-6)),n(`const ${qo(l,t)} = ${o}(${JSON.stringify(l)}${u?", true":""})${s?"!":""}`),a<e.length-1&&i()}}function ET(e,t){if(!e.length)return;t.pure=!0;const{push:r,newline:n}=t;n();for(let i=0;i<e.length;i++){const s=e[i];s&&(r(`const _hoisted_${i+1} = `),kt(s,t),n())}t.pure=!1}function op(e,t){const r=e.length>3||!1;t.push("["),r&&t.indent(),ua(e,t,r),r&&t.deindent(),t.push("]")}function ua(e,t,r=!1,n=!0){const{push:i,newline:s}=t;for(let o=0;o<e.length;o++){const a=e[o];mt(a)?i(a,-3):Gn(a)?op(a,t):kt(a,t),o<e.length-1&&(r?(n&&i(","),s()):n&&i(", "))}}function kt(e,t){if(mt(e)){t.push(e,-3);return}if(Mf(e)){t.push(t.helper(e));return}switch(e.type){case 1:case 9:case 11:kt(e.codegenNode,t);break;case 2:xT(e,t);break;case 4:gw(e,t);break;case 5:ST(e,t);break;case 12:kt(e.codegenNode,t);break;case 8:bw(e,t);break;case 3:AT(e,t);break;case 13:TT(e,t);break;case 14:OT(e,t);break;case 15:NT(e,t);break;case 17:PT(e,t);break;case 18:FT(e,t);break;case 19:IT(e,t);break;case 20:LT(e,t);break;case 21:ua(e.body,t,!0,!1);break}}function xT(e,t){t.push(JSON.stringify(e.content),-3,e)}function gw(e,t){const{content:r,isStatic:n}=e;t.push(n?JSON.stringify(r):r,-3,e)}function ST(e,t){const{push:r,helper:n,pure:i}=t;i&&r(Rc),r(`${n(Fc)}(`),kt(e.content,t),r(")")}function bw(e,t){for(let r=0;r<e.children.length;r++){const n=e.children[r];mt(n)?t.push(n,-3):kt(n,t)}}function CT(e,t){const{push:r}=t;if(e.type===8)r("["),bw(e,t),r("]");else if(e.isStatic){const n=np(e.content)?e.content:JSON.stringify(e.content);r(n,-2,e)}else r(`[${e.content}]`,-3,e)}function AT(e,t){const{push:r,helper:n,pure:i}=t;i&&r(Rc),r(`${n(la)}(${JSON.stringify(e.content)})`,-3,e)}function TT(e,t){const{push:r,helper:n,pure:i}=t,{tag:s,props:o,children:a,patchFlag:l,dynamicProps:u,directives:c,isBlock:d,disableTracking:p,isComponent:m}=e;let h;l&&(h=String(l)),c&&r(n(Gf)+"("),d&&r(`(${n(Pi)}(${p?"true":""}), `),i&&r(Rc);const f=d?vs(t.inSSR,m):bs(t.inSSR,m);r(n(f)+"(",-2,e),ua(kT([s,o,a,h,u]),t),r(")"),d&&r(")"),c&&(r(", "),kt(c,t),r(")"))}function kT(e){let t=e.length;for(;t--&&e[t]==null;);return e.slice(0,t+1).map(r=>r||"null")}function OT(e,t){const{push:r,helper:n,pure:i}=t,s=mt(e.callee)?e.callee:n(e.callee);i&&r(Rc),r(s+"(",-2,e),ua(e.arguments,t),r(")")}function NT(e,t){const{push:r,indent:n,deindent:i,newline:s}=t,{properties:o}=e;if(!o.length){r("{}",-2,e);return}const a=o.length>1||!1;r(a?"{":"{ "),a&&n();for(let l=0;l<o.length;l++){const{key:u,value:c}=o[l];CT(u,t),r(": "),kt(c,t),l<o.length-1&&(r(","),s())}a&&i(),r(a?"}":" }")}function PT(e,t){op(e.elements,t)}function FT(e,t){const{push:r,indent:n,deindent:i}=t,{params:s,returns:o,body:a,newline:l,isSlot:u}=e;u&&r(`_${ms[Zf]}(`),r("(",-2,e),Gn(s)?ua(s,t):s&&kt(s,t),r(") => "),(l||a)&&(r("{"),n()),o?(l&&r("return "),Gn(o)?op(o,t):kt(o,t)):a&&kt(a,t),(l||a)&&(i(),r("}")),u&&(e.isNonScopedSlot&&r(", undefined, true"),r(")"))}function IT(e,t){const{test:r,consequent:n,alternate:i,newline:s}=e,{push:o,indent:a,deindent:l,newline:u}=t;if(r.type===4){const d=!np(r.content);d&&o("("),gw(r,t),d&&o(")")}else o("("),kt(r,t),o(")");s&&a(),t.indentLevel++,s||o(" "),o("? "),kt(n,t),t.indentLevel--,s&&u(),s||o(" "),o(": ");const c=i.type===19;c||t.indentLevel++,kt(i,t),c||t.indentLevel--,s&&l(!0)}function LT(e,t){const{push:r,helper:n,indent:i,deindent:s,newline:o}=t,{needPauseTracking:a,needArraySpread:l}=e;l&&r("[...("),r(`_cache[${e.index}] || (`),a&&(i(),r(`${n(Bl)}(-1`),e.inVOnce&&r(", true"),r("),"),o(),r("(")),r(`_cache[${e.index}] = `),kt(e.value,t),a&&(r(`).cacheIndex = ${e.index},`),o(),r(`${n(Bl)}(1),`),o(),r(`_cache[${e.index}]`),s()),r(")"),l&&r(")]")}new RegExp("\\b"+"arguments,await,break,case,catch,class,const,continue,debugger,default,delete,do,else,export,extends,finally,for,function,if,import,let,new,return,super,switch,throw,try,var,void,while,with,yield".split(",").join("\\b|\\b")+"\\b");const RT=pw(/^(?:if|else|else-if)$/,(e,t,r)=>$T(e,t,r,(n,i,s)=>{const o=r.parent.children;let a=o.indexOf(n),l=0;for(;a-->=0;){const u=o[a];u&&u.type===9&&(l+=u.branches.length)}return()=>{if(s)n.codegenNode=Gm(i,l,r);else{const u=MT(n.codegenNode);u.alternate=Gm(i,l+n.branches.length-1,r)}}}));function $T(e,t,r,n){if(t.name!=="else"&&(!t.exp||!t.exp.content.trim())){const i=t.exp?t.exp.loc:e.loc;r.onError(Ve(28,t.loc)),t.exp=ue("true",!1,i)}if(t.name==="if"){const i=Km(e,t),s={type:9,loc:cT(e.loc),branches:[i]};if(r.replaceNode(s),n)return n(s,i,!0)}else{const i=r.parent.children;let s=i.indexOf(e);for(;s-->=-1;){const o=i[s];if(o&&sw(o)){r.removeNode(o);continue}if(o&&o.type===9){(t.name==="else-if"||t.name==="else")&&o.branches[o.branches.length-1].condition===void 0&&r.onError(Ve(30,e.loc)),r.removeNode();const a=Km(e,t);o.branches.push(a);const l=n&&n(o,a,!1);Lc(a,r),l&&l(),r.currentNode=null}else r.onError(Ve(30,e.loc));break}}}function Km(e,t){const r=e.tagType===3;return{type:10,loc:e.loc,condition:t.name==="else"?void 0:t.exp,children:r&&!ur(e,"for")?e.children:[e],userKey:Ic(e,"key"),isTemplateIf:r}}function Gm(e,t,r){return e.condition?Eh(e.condition,Ym(e,t,r),ot(r.helper(la),['""',"true"])):Ym(e,t,r)}function Ym(e,t,r){const{helper:n}=r,i=Ze("key",ue(`${t}`,!1,sr,2)),{children:s}=e,o=s[0];if(s.length!==1||o.type!==1)if(s.length===1&&o.type===11){const l=o.codegenNode;return Hl(l,i,r),l}else return Uo(r,n(Vo),dr([i]),s,64,void 0,void 0,!0,!1,!1,e.loc);else{const l=o.codegenNode,u=ZA(l);return u.type===13&&tp(u,r),Hl(u,i,r),l}}function MT(e){for(;;)if(e.type===19)if(e.alternate.type===19)e=e.alternate;else return e;else e.type===20&&(e=e.value)}const DT=pw("for",(e,t,r)=>{const{helper:n,removeHelper:i}=r;return VT(e,t,r,s=>{const o=ot(n(Yf),[s.source]),a=zl(e),l=ur(e,"memo"),u=Ic(e,"key",!1,!0);u&&u.type;let c=u&&(u.type===6?u.value?ue(u.value.content,!0):void 0:u.exp);const d=u&&c?Ze("key",c):null,p=s.source.type===4&&s.source.constType>0,m=p?64:u?128:256;return s.codegenNode=Uo(r,n(Vo),void 0,o,m,void 0,void 0,!0,!p,!1,e.loc),()=>{let h;const{children:f}=s,b=f.length!==1||f[0].type!==1,y=ql(e)?e:a&&e.children.length===1&&ql(e.children[0])?e.children[0]:null;if(y?(h=y.codegenNode,a&&d&&Hl(h,d,r)):b?h=Uo(r,n(Vo),d?dr([d]):void 0,e.children,64,void 0,void 0,!0,void 0,!1):(h=f[0].codegenNode,a&&d&&Hl(h,d,r),h.isBlock!==!p&&(h.isBlock?(i(Pi),i(vs(r.inSSR,h.isComponent))):i(bs(r.inSSR,h.isComponent))),h.isBlock=!p,h.isBlock?(n(Pi),n(vs(r.inSSR,h.isComponent))):n(bs(r.inSSR,h.isComponent))),l){const w=gs(Ch(s.parseResult,[ue("_cached")]));w.body=UA([kr(["const _memo = (",l.exp,")"]),kr(["if (_cached",...c?[" && _cached.key === ",c]:[],` && ${r.helperString(J_)}(_cached, _memo)) return _cached`]),kr(["const _item = ",h]),ue("_item.memo = _memo"),ue("return _item")]),o.arguments.push(w,ue("_cache"),ue(String(r.cached.length))),r.cached.push(null)}else o.arguments.push(gs(Ch(s.parseResult),h,!0))}})});function VT(e,t,r,n){if(!t.exp){r.onError(Ve(31,t.loc));return}const i=t.forParseResult;if(!i){r.onError(Ve(32,t.loc));return}vw(i);const{addIdentifiers:s,removeIdentifiers:o,scopes:a}=r,{source:l,value:u,key:c,index:d}=i,p={type:11,loc:t.loc,source:l,valueAlias:u,keyAlias:c,objectIndexAlias:d,parseResult:i,children:zl(e)?e.children:[e]};r.replaceNode(p),a.vFor++;const m=n&&n(p);return()=>{a.vFor--,m&&m()}}function vw(e,t){e.finalized||(e.finalized=!0)}function Ch({value:e,key:t,index:r},n=[]){return BT([e,t,r,...n])}function BT(e){let t=e.length;for(;t--&&!e[t];);return e.slice(0,t+1).map((r,n)=>r||ue("_".repeat(n+1),!1))}const Xm=ue("undefined",!1),UT=(e,t)=>{if(e.type===1&&(e.tagType===1||e.tagType===3)){const r=ur(e,"slot");if(r)return r.exp,t.scopes.vSlot++,()=>{t.scopes.vSlot--}}},zT=(e,t,r,n)=>gs(e,r,!1,!0,r.length?r[0].loc:n);function qT(e,t,r=zT){t.helper(Zf);const{children:n,loc:i}=e,s=[],o=[];let a=t.scopes.vSlot>0||t.scopes.vFor>0;const l=ur(e,"slot",!0);if(l){const{arg:b,exp:y}=l;b&&!Vt(b)&&(a=!0),s.push(Ze(b||ue("default",!0),r(y,void 0,n,i)))}let u=!1,c=!1;const d=[],p=new Set;let m=0;for(let b=0;b<n.length;b++){const y=n[b];let w;if(!zl(y)||!(w=ur(y,"slot",!0))){y.type!==3&&d.push(y);continue}if(l){t.onError(Ve(37,w.loc));break}u=!0;const{children:v,loc:_}=y,{arg:x=ue("default",!0),exp:k,loc:S}=w;let N;Vt(x)?N=x?x.content:"default":a=!0;const A=ur(y,"for"),T=r(k,A,v,_);let F,C;if(F=ur(y,"if"))a=!0,o.push(Eh(F.exp,Ba(x,T,m++),Xm));else if(C=ur(y,/^else(?:-if)?$/,!0)){let g=b,L;for(;g--&&(L=n[g],!!sw(L)););if(L&&zl(L)&&ur(L,/^(?:else-)?if$/)){let B=o[o.length-1];for(;B.alternate.type===19;)B=B.alternate;B.alternate=C.exp?Eh(C.exp,Ba(x,T,m++),Xm):Ba(x,T,m++)}else t.onError(Ve(30,C.loc))}else if(A){a=!0;const g=A.forParseResult;g?(vw(g),o.push(ot(t.helper(Yf),[g.source,gs(Ch(g),Ba(x,T),!0)]))):t.onError(Ve(32,A.loc))}else{if(N){if(p.has(N)){t.onError(Ve(38,S));continue}p.add(N),N==="default"&&(c=!0)}s.push(Ze(x,T))}}if(!l){const b=(y,w)=>{const v=r(y,void 0,w,i);return t.compatConfig&&(v.isNonScopedSlot=!0),Ze("default",v)};u?d.length&&!d.every(ip)&&(c?t.onError(Ve(39,d[0].loc)):s.push(b(void 0,d))):s.push(b(void 0,n))}const h=a?2:cl(e.children)?3:1;let f=dr(s.concat(Ze("_",ue(h+"",!1))),i);return o.length&&(f=ot(t.helper(X_),[f,Ci(o)])),{slots:f,hasDynamicSlots:a}}function Ba(e,t,r){const n=[Ze("name",e),Ze("fn",t)];return r!=null&&n.push(Ze("key",ue(String(r),!0))),dr(n)}function cl(e){for(let t=0;t<e.length;t++){const r=e[t];switch(r.type){case 1:if(r.tagType===2||cl(r.children))return!0;break;case 9:if(cl(r.branches))return!0;break;case 10:case 11:if(cl(r.children))return!0;break}}return!1}const yw=new WeakMap,HT=(e,t)=>function(){if(e=t.currentNode,!(e.type===1&&(e.tagType===0||e.tagType===1)))return;const{tag:n,props:i}=e,s=e.tagType===1;let o=s?jT(e,t):`"${n}"`;const a=gA(o)&&o.callee===jf;let l,u,c=0,d,p,m,h=a||o===_o||o===Bf||!s&&(n==="svg"||n==="foreignObject"||n==="math");if(i.length>0){const f=_w(e,t,void 0,s,a);l=f.props,c=f.patchFlag,p=f.dynamicPropNames;const b=f.directives;m=b&&b.length?Ci(b.map(y=>KT(y,t))):void 0,f.shouldUseBlock&&(h=!0)}if(e.children.length>0)if(o===Dl&&(h=!0,c|=1024),s&&o!==_o&&o!==Dl){const{slots:b,hasDynamicSlots:y}=qT(e,t);u=b,y&&(c|=1024)}else if(e.children.length===1&&o!==_o){const b=e.children[0],y=b.type,w=y===5||y===8;w&&Qt(b,t)===0&&(c|=1),w||y===2?u=b:u=e.children}else u=e.children;p&&p.length&&(d=GT(p)),e.codegenNode=Uo(t,o,l,u,c===0?void 0:c,d,m,!!h,!1,s,e.loc)};function jT(e,t,r=!1){let{tag:n}=e;const i=Ah(n),s=Ic(e,"is",!1,!0);if(s)if(i||Ai("COMPILER_IS_ON_ELEMENT",t)){let a;if(s.type===6?a=s.value&&ue(s.value.content,!0):(a=s.exp,a||(a=ue("is",!1,s.arg.loc))),a)return ot(t.helper(jf),[a])}else s.type===6&&s.value.content.startsWith("vue:")&&(n=s.value.content.slice(4));const o=Z_(n)||t.isBuiltInComponent(n);return o?(r||t.helper(o),o):(t.helper(Hf),t.components.add(n),qo(n,"component"))}function _w(e,t,r=e.props,n,i,s=!1){const{tag:o,loc:a,children:l}=e;let u=[];const c=[],d=[],p=l.length>0;let m=!1,h=0,f=!1,b=!1,y=!1,w=!1,v=!1,_=!1;const x=[],k=T=>{u.length&&(c.push(dr(Jm(u),a)),u=[]),T&&c.push(T)},S=()=>{t.scopes.vFor>0&&u.push(Ze(ue("ref_for",!0),ue("true")))},N=({key:T,value:F})=>{if(Vt(T)){const C=T.content,g=H_(C);if(g&&(!n||i)&&C.toLowerCase()!=="onclick"&&C!=="onUpdate:modelValue"&&!Mm(C)&&(w=!0),g&&Mm(C)&&(_=!0),g&&F.type===14&&(F=F.arguments[0]),F.type===20||(F.type===4||F.type===8)&&Qt(F,t)>0)return;C==="ref"?f=!0:C==="class"?b=!0:C==="style"?y=!0:C!=="key"&&!x.includes(C)&&x.push(C),n&&(C==="class"||C==="style")&&!x.includes(C)&&x.push(C)}else v=!0};for(let T=0;T<r.length;T++){const F=r[T];if(F.type===6){const{loc:C,name:g,nameLoc:L,value:B}=F;let $=!0;if(g==="ref"&&(f=!0,S()),g==="is"&&(Ah(o)||B&&B.content.startsWith("vue:")||Ai("COMPILER_IS_ON_ELEMENT",t)))continue;u.push(Ze(ue(g,!0,L),ue(B?B.content:"",$,B?B.loc:C)))}else{const{name:C,arg:g,exp:L,loc:B,modifiers:$}=F,z=C==="bind",P=C==="on";if(C==="slot"){n||t.onError(Ve(40,B));continue}if(C==="once"||C==="memo"||C==="is"||z&&gi(g,"is")&&(Ah(o)||Ai("COMPILER_IS_ON_ELEMENT",t))||P&&s)continue;if((z&&gi(g,"key")||P&&p&&gi(g,"vue:before-update"))&&(m=!0),z&&gi(g,"ref")&&S(),!g&&(z||P)){if(v=!0,L)if(z){if(k(),Ai("COMPILER_V_BIND_OBJECT_ORDER",t)){c.unshift(L);continue}S(),k(),c.push(L)}else k({type:14,loc:B,callee:t.helper(Qf),arguments:n?[L]:[L,"true"]});else t.onError(Ve(z?34:35,B));continue}z&&$.some(be=>be.content==="prop")&&(h|=32);const Z=t.directiveTransforms[C];if(Z){const{props:be,needRuntime:le}=Z(F,e,t);!s&&be.forEach(N),P&&g&&!Vt(g)?k(dr(be,a)):u.push(...be),le&&(d.push(F),Mf(le)&&yw.set(F,le))}else bA(C)||(d.push(F),p&&(m=!0))}}let A;if(c.length?(k(),c.length>1?A=ot(t.helper(Vl),c,a):A=c[0]):u.length&&(A=dr(Jm(u),a)),v?h|=16:(b&&!n&&(h|=2),y&&!n&&(h|=4),x.length&&(h|=8),w&&(h|=32)),!m&&(h===0||h===32)&&(f||_||d.length>0)&&(h|=512),!t.inSSR&&A)switch(A.type){case 15:let T=-1,F=-1,C=!1;for(let B=0;B<A.properties.length;B++){const $=A.properties[B].key;Vt($)?$.content==="class"?T=B:$.content==="style"&&(F=B):$.isHandlerKey||(C=!0)}const g=A.properties[T],L=A.properties[F];C?A=ot(t.helper(Bo),[A]):(g&&!Vt(g.value)&&(g.value=ot(t.helper(Xf),[g.value])),L&&(y||L.value.type===4&&L.value.content.trim()[0]==="["||L.value.type===17)&&(L.value=ot(t.helper(Jf),[L.value])));break;case 14:break;default:A=ot(t.helper(Bo),[ot(t.helper(ca),[A])]);break}return{props:A,directives:d,patchFlag:h,dynamicPropNames:x,shouldUseBlock:m}}function Jm(e){const t=new Map,r=[];for(let n=0;n<e.length;n++){const i=e[n];if(i.key.type===8||!i.key.isStatic){r.push(i);continue}const s=i.key.content,o=t.get(s);o?(s==="style"||s==="class"||H_(s))&&WT(o,i):(t.set(s,i),r.push(i))}return r}function WT(e,t){e.value.type===17?e.value.elements.push(t.value):e.value=Ci([e.value,t.value],e.loc)}function KT(e,t){const r=[],n=yw.get(e);n?r.push(t.helperString(n)):(t.helper(Wf),t.directives.add(e.name),r.push(qo(e.name,"directive")));const{loc:i}=e;if(e.exp&&r.push(e.exp),e.arg&&(e.exp||r.push("void 0"),r.push(e.arg)),Object.keys(e.modifiers).length){e.arg||(e.exp||r.push("void 0"),r.push("void 0"));const s=ue("true",!1,i);r.push(dr(e.modifiers.map(o=>Ze(o,s)),i))}return Ci(r,e.loc)}function GT(e){let t="[";for(let r=0,n=e.length;r<n;r++)t+=JSON.stringify(e[r]),r<n-1&&(t+=", ");return t+"]"}function Ah(e){return e==="component"||e==="Component"}const YT=(e,t)=>{if(ql(e)){const{children:r,loc:n}=e,{slotName:i,slotProps:s}=XT(e,t),o=[t.prefixIdentifiers?"_ctx.$slots":"$slots",i,"{}","undefined","true"];let a=2;s&&(o[2]=s,a=3),r.length&&(o[3]=gs([],r,!1,!1,n),a=4),t.scopeId&&!t.slotted&&(a=5),o.splice(a),e.codegenNode=ot(t.helper(Y_),o,n)}};function XT(e,t){let r='"default"',n;const i=[];for(let s=0;s<e.props.length;s++){const o=e.props[s];if(o.type===6)o.value&&(o.name==="name"?r=JSON.stringify(o.value.content):(o.name=Qn(o.name),i.push(o)));else if(o.name==="bind"&&gi(o.arg,"name")){if(o.exp)r=o.exp;else if(o.arg&&o.arg.type===4){const a=Qn(o.arg.content);r=o.exp=ue(a,!1,o.arg.loc)}}else o.name==="bind"&&o.arg&&Vt(o.arg)&&(o.arg.content=Qn(o.arg.content)),i.push(o)}if(i.length>0){const{props:s,directives:o}=_w(e,t,i,!1,!1);n=s,o.length&&t.onError(Ve(36,o[0].loc))}return{slotName:r,slotProps:n}}const ww=(e,t,r,n)=>{const{loc:i,modifiers:s,arg:o}=e;!e.exp&&!s.length&&r.onError(Ve(35,i));let a;if(o.type===4)if(o.isStatic){let d=o.content;d.startsWith("vue:")&&(d=`vnode-${d.slice(4)}`);const p=t.tagType!==0||d.startsWith("vnode")||!/[A-Z]/.test(d)?yA(Qn(d)):`on:${d}`;a=ue(p,!0,o.loc)}else a=kr([`${r.helperString(wh)}(`,o,")"]);else a=o,a.children.unshift(`${r.helperString(wh)}(`),a.children.push(")");let l=e.exp;l&&!l.content.trim()&&(l=void 0);let u=r.cacheHandlers&&!l&&!r.inVOnce;if(l){const d=rw(l),p=!(d||YA(l)),m=l.content.includes(";");(p||u&&d)&&(l=kr([`${p?"$event":"(...args)"} => ${m?"{":"("}`,l,m?"}":")"]))}let c={props:[Ze(a,l||ue("() => {}",!1,i))]};return n&&(c=n(c)),u&&(c.props[0].value=r.cache(c.props[0].value)),c.props.forEach(d=>d.key.isHandlerKey=!0),c},JT=(e,t,r)=>{const{modifiers:n,loc:i}=e,s=e.arg;let{exp:o}=e;return o&&o.type===4&&!o.content.trim()&&(o=void 0),s.type!==4?(s.children.unshift("("),s.children.push(') || ""')):s.isStatic||(s.content=s.content?`${s.content} || ""`:'""'),n.some(a=>a.content==="camel")&&(s.type===4?s.isStatic?s.content=Qn(s.content):s.content=`${r.helperString(_h)}(${s.content})`:(s.children.unshift(`${r.helperString(_h)}(`),s.children.push(")"))),r.inSSR||(n.some(a=>a.content==="prop")&&Qm(s,"."),n.some(a=>a.content==="attr")&&Qm(s,"^")),{props:[Ze(s,o)]}},Qm=(e,t)=>{e.type===4?e.isStatic?e.content=t+e.content:e.content=`\`${t}\${${e.content}}\``:(e.children.unshift(`'${t}' + (`),e.children.push(")"))},QT=(e,t)=>{if(e.type===0||e.type===1||e.type===11||e.type===10)return()=>{const r=e.children;let n,i=!1;for(let s=0;s<r.length;s++){const o=r[s];if(xu(o)){i=!0;for(let a=s+1;a<r.length;a++){const l=r[a];if(xu(l))n||(n=r[s]=kr([o],o.loc)),n.children.push(" + ",l),r.splice(a,1),a--;else{n=void 0;break}}}}if(!(!i||r.length===1&&(e.type===0||e.type===1&&e.tagType===0&&!e.props.find(s=>s.type===7&&!t.directiveTransforms[s.name])&&e.tag!=="template")))for(let s=0;s<r.length;s++){const o=r[s];if(xu(o)||o.type===8){const a=[];(o.type!==2||o.content!==" ")&&a.push(o),!t.ssr&&Qt(o,t)===0&&a.push("1"),r[s]={type:12,content:o,loc:o.loc,codegenNode:ot(t.helper(qf),a)}}}}},Zm=new WeakSet,ZT=(e,t)=>{if(e.type===1&&ur(e,"once",!0))return Zm.has(e)||t.inVOnce||t.inSSR?void 0:(Zm.add(e),t.inVOnce=!0,t.helper(Bl),()=>{t.inVOnce=!1;const r=t.currentNode;r.codegenNode&&(r.codegenNode=t.cache(r.codegenNode,!0,!0))})},Ew=(e,t,r)=>{const{exp:n,arg:i}=e;if(!n)return r.onError(Ve(41,e.loc)),zs();const s=n.loc.source.trim(),o=n.type===4?n.content:s,a=r.bindingMetadata[s];if(a==="props"||a==="props-aliased")return r.onError(Ve(44,n.loc)),zs();if(a==="literal-const"||a==="setup-const")return r.onError(Ve(45,n.loc)),zs();if(!o.trim()||!rw(n))return r.onError(Ve(42,n.loc)),zs();const l=i||ue("modelValue",!0),u=i?Vt(i)?`onUpdate:${Qn(i.content)}`:kr(['"onUpdate:" + ',i]):"onUpdate:modelValue";let c;const d=r.isTS?"($event: any)":"$event";c=kr([`${d} => ((`,n,") = $event)"]);const p=[Ze(l,e.exp),Ze(u,c)];if(e.modifiers.length&&t.tagType===1){const m=e.modifiers.map(f=>f.content).map(f=>(np(f)?f:JSON.stringify(f))+": true").join(", "),h=i?Vt(i)?`${i.content}Modifiers`:kr([i,' + "Modifiers"']):"modelModifiers";p.push(Ze(h,ue(`{ ${m} }`,!1,e.loc,2)))}return zs(p)};function zs(e=[]){return{props:e}}const ek=/[\w).+\-_$\]]/,tk=(e,t)=>{Ai("COMPILER_FILTERS",t)&&(e.type===5?jl(e.content,t):e.type===1&&e.props.forEach(r=>{r.type===7&&r.name!=="for"&&r.exp&&jl(r.exp,t)}))};function jl(e,t){if(e.type===4)eg(e,t);else for(let r=0;r<e.children.length;r++){const n=e.children[r];typeof n=="object"&&(n.type===4?eg(n,t):n.type===8?jl(e,t):n.type===5&&jl(n.content,t))}}function eg(e,t){const r=e.content;let n=!1,i=!1,s=!1,o=!1,a=0,l=0,u=0,c=0,d,p,m,h,f=[];for(m=0;m<r.length;m++)if(p=d,d=r.charCodeAt(m),n)d===39&&p!==92&&(n=!1);else if(i)d===34&&p!==92&&(i=!1);else if(s)d===96&&p!==92&&(s=!1);else if(o)d===47&&p!==92&&(o=!1);else if(d===124&&r.charCodeAt(m+1)!==124&&r.charCodeAt(m-1)!==124&&!a&&!l&&!u)h===void 0?(c=m+1,h=r.slice(0,m).trim()):b();else{switch(d){case 34:i=!0;break;case 39:n=!0;break;case 96:s=!0;break;case 40:u++;break;case 41:u--;break;case 91:l++;break;case 93:l--;break;case 123:a++;break;case 125:a--;break}if(d===47){let y=m-1,w;for(;y>=0&&(w=r.charAt(y),w===" ");y--);(!w||!ek.test(w))&&(o=!0)}}h===void 0?h=r.slice(0,m).trim():c!==0&&b();function b(){f.push(r.slice(c,m).trim()),c=m+1}if(f.length){for(m=0;m<f.length;m++)h=rk(h,f[m],t);e.content=h,e.ast=void 0}}function rk(e,t,r){r.helper(Kf);const n=t.indexOf("(");if(n<0)return r.filters.add(t),`${qo(t,"filter")}(${e})`;{const i=t.slice(0,n),s=t.slice(n+1);return r.filters.add(i),`${qo(i,"filter")}(${e}${s!==")"?","+s:s}`}}const tg=new WeakSet,nk=(e,t)=>{if(e.type===1){const r=ur(e,"memo");return!r||tg.has(e)||t.inSSR?void 0:(tg.add(e),()=>{const n=e.codegenNode||t.currentNode.codegenNode;n&&n.type===13&&(e.tagType!==1&&tp(n,t),e.codegenNode=ot(t.helper(ep),[r.exp,gs(void 0,n),"_cache",String(t.cached.length)]),t.cached.push(null))})}},ik=(e,t)=>{if(e.type===1){for(const r of e.props)if(r.type===7&&r.name==="bind"&&(!r.exp||r.exp.type===4&&!r.exp.content.trim())&&r.arg){const n=r.arg;if(n.type!==4||!n.isStatic)t.onError(Ve(53,n.loc)),r.exp=ue("",!0,n.loc);else{const i=Qn(n.content);(ew.test(i[0])||i[0]==="-")&&(r.exp=ue(i,!1,n.loc))}}}};function sk(e){return[[ik,ZT,RT,nk,DT,tk,YT,HT,UT,QT],{on:ww,bind:JT,model:Ew}]}function ok(e,t={}){const r=t.onError||rp,n=t.mode==="module";t.prefixIdentifiers===!0?r(Ve(48)):n&&r(Ve(49));const i=!1;t.cacheHandlers&&r(Ve(50)),t.scopeId&&!n&&r(Ve(51));const s=bn({},t,{prefixIdentifiers:i}),o=mt(e)?hT(e,s):e,[a,l]=sk();return gT(o,bn({},s,{nodeTransforms:[...a,...t.nodeTransforms||[]],directiveTransforms:bn({},l,t.directiveTransforms||{})})),_T(o,s)}const ak=()=>({props:[]});const xw=Symbol(""),Sw=Symbol(""),Cw=Symbol(""),Aw=Symbol(""),Th=Symbol(""),Tw=Symbol(""),kw=Symbol(""),Ow=Symbol(""),Nw=Symbol(""),Pw=Symbol("");DA({[xw]:"vModelRadio",[Sw]:"vModelCheckbox",[Cw]:"vModelText",[Aw]:"vModelSelect",[Th]:"vModelDynamic",[Tw]:"withModifiers",[kw]:"withKeys",[Ow]:"vShow",[Nw]:"Transition",[Pw]:"TransitionGroup"});let Wi;function lk(e,t=!1){return Wi||(Wi=document.createElement("div")),t?(Wi.innerHTML=`<div foo="${e.replace(/"/g,"&quot;")}">`,Wi.children[0].getAttribute("foo")):(Wi.innerHTML=e,Wi.textContent)}const ck={parseMode:"html",isVoidTag:FA,isNativeTag:e=>OA(e)||NA(e)||PA(e),isPreTag:e=>e==="pre",isIgnoreNewlineTag:e=>e==="pre"||e==="textarea",decodeEntities:lk,isBuiltInComponent:e=>{if(e==="Transition"||e==="transition")return Nw;if(e==="TransitionGroup"||e==="transition-group")return Pw},getNamespace(e,t,r){let n=t?t.ns:r;if(t&&n===2)if(t.tag==="annotation-xml"){if(e==="svg")return 1;t.props.some(i=>i.type===6&&i.name==="encoding"&&i.value!=null&&(i.value.content==="text/html"||i.value.content==="application/xhtml+xml"))&&(n=0)}else/^m(?:[ions]|text)$/.test(t.tag)&&e!=="mglyph"&&e!=="malignmark"&&(n=0);else t&&n===1&&(t.tag==="foreignObject"||t.tag==="desc"||t.tag==="title")&&(n=0);if(n===0){if(e==="svg")return 1;if(e==="math")return 2}return n}},uk=e=>{e.type===1&&e.props.forEach((t,r)=>{t.type===6&&t.name==="style"&&t.value&&(e.props[r]={type:7,name:"bind",arg:ue("style",!0,t.loc),exp:dk(t.value.content,t.loc),modifiers:[],loc:t.loc})})},dk=(e,t)=>{const r=SA(e);return ue(JSON.stringify(r),!1,t,3)};function Zn(e,t){return Ve(e,t)}const hk=(e,t,r)=>{const{exp:n,loc:i}=e;return n||r.onError(Zn(54,i)),t.children.length&&(r.onError(Zn(55,i)),t.children.length=0),{props:[Ze(ue("innerHTML",!0,i),n||ue("",!0))]}},fk=(e,t,r)=>{const{exp:n,loc:i}=e;return n||r.onError(Zn(56,i)),t.children.length&&(r.onError(Zn(57,i)),t.children.length=0),{props:[Ze(ue("textContent",!0),n?Qt(n,r)>0?n:ot(r.helperString(Fc),[n],i):ue("",!0))]}},pk=(e,t,r)=>{const n=Ew(e,t,r);if(!n.props.length||t.tagType===1)return n;e.arg&&r.onError(Zn(59,e.arg.loc));const{tag:i}=t,s=r.isCustomElement(i);if(i==="input"||i==="textarea"||i==="select"||s){let o=Cw,a=!1;if(i==="input"||s){const l=Ic(t,"type");if(l){if(l.type===7)o=Th;else if(l.value)switch(l.value.content){case"radio":o=xw;break;case"checkbox":o=Sw;break;case"file":a=!0,r.onError(Zn(60,e.loc));break}}else XA(t)&&(o=Th)}else i==="select"&&(o=Aw);a||(n.needRuntime=r.helper(o))}else r.onError(Zn(58,e.loc));return n.props=n.props.filter(o=>!(o.key.type===4&&o.key.content==="modelValue")),n},mk=Zr("passive,once,capture"),gk=Zr("stop,prevent,self,ctrl,shift,alt,meta,exact,middle"),bk=Zr("left,right"),Fw=Zr("onkeyup,onkeydown,onkeypress"),vk=(e,t,r,n)=>{const i=[],s=[],o=[];for(let a=0;a<t.length;a++){const l=t[a].content;l==="native"&&zo("COMPILER_V_ON_NATIVE",r)||mk(l)?o.push(l):bk(l)?Vt(e)?Fw(e.content.toLowerCase())?i.push(l):s.push(l):(i.push(l),s.push(l)):gk(l)?s.push(l):i.push(l)}return{keyModifiers:i,nonKeyModifiers:s,eventOptionModifiers:o}},rg=(e,t)=>Vt(e)&&e.content.toLowerCase()==="onclick"?ue(t,!0):e.type!==4?kr(["(",e,`) === "onClick" ? "${t}" : (`,e,")"]):e,yk=(e,t,r)=>ww(e,t,r,n=>{const{modifiers:i}=e;if(!i.length)return n;let{key:s,value:o}=n.props[0];const{keyModifiers:a,nonKeyModifiers:l,eventOptionModifiers:u}=vk(s,i,r,e.loc);if(l.includes("right")&&(s=rg(s,"onContextmenu")),l.includes("middle")&&(s=rg(s,"onMouseup")),l.length&&(o=ot(r.helper(Tw),[o,JSON.stringify(l)])),a.length&&(!Vt(s)||Fw(s.content.toLowerCase()))&&(o=ot(r.helper(kw),[o,JSON.stringify(a)])),u.length){const c=u.map(Vf).join("");s=Vt(s)?ue(`${s.content}${c}`,!0):kr(["(",s,`) + "${c}"`])}return{props:[Ze(s,o)]}}),_k=(e,t,r)=>{const{exp:n,loc:i}=e;return n||r.onError(Zn(62,i)),{props:[],needRuntime:r.helper(Ow)}},wk=(e,t)=>{e.type===1&&e.tagType===0&&(e.tag==="script"||e.tag==="style")&&t.removeNode()},Ek=[uk],xk={cloak:ak,html:hk,text:fk,model:pk,on:yk,show:_k};function Sk(e,t={}){return ok(e,bn({},ck,t,{nodeTransforms:[wk,...Ek,...t.nodeTransforms||[]],directiveTransforms:bn({},xk,t.directiveTransforms||{}),transformHoist:null}))}const ng=Object.create(null);function Ck(e,t){if(!mt(e))if(e.nodeType)e=e.innerHTML;else return yo;const r=_A(e,t),n=ng[r];if(n)return n;if(e[0]==="#"){const a=document.querySelector(e);e=a?a.innerHTML:""}const i=bn({hoistStatic:!0,onError:void 0,onWarn:yo},t);!i.isCustomElement&&typeof customElements<"u"&&(i.isCustomElement=a=>!!customElements.get(a));const{code:s}=Sk(e,i),o=new Function("Vue",s)(pA);return o._rc=!0,ng[r]=o}h_(Ck);async function Ak(e,t){for(const r of Array.isArray(e)?e:[e]){const n=t[r];if(!(typeof n>"u"))return typeof n=="function"?n():n}throw new Error(`Page not found: ${e}`)}var Iw=typeof global=="object"&&global&&global.Object===Object&&global,Tk=typeof self=="object"&&self&&self.Object===Object&&self,Rr=Iw||Tk||Function("return this")(),mr=Rr.Symbol,Lw=Object.prototype,kk=Lw.hasOwnProperty,Ok=Lw.toString,qs=mr?mr.toStringTag:void 0;function Nk(e){var t=kk.call(e,qs),r=e[qs];try{e[qs]=void 0;var n=!0}catch{}var i=Ok.call(e);return n&&(t?e[qs]=r:delete e[qs]),i}var Pk=Object.prototype,Fk=Pk.toString;function Ik(e){return Fk.call(e)}var Lk="[object Null]",Rk="[object Undefined]",ig=mr?mr.toStringTag:void 0;function Di(e){return e==null?e===void 0?Rk:Lk:ig&&ig in Object(e)?Nk(e):Ik(e)}function Jr(e){return e!=null&&typeof e=="object"}var $k="[object Symbol]";function $c(e){return typeof e=="symbol"||Jr(e)&&Di(e)==$k}function Rw(e,t){for(var r=-1,n=e==null?0:e.length,i=Array(n);++r<n;)i[r]=t(e[r],r,e);return i}var gr=Array.isArray,sg=mr?mr.prototype:void 0,og=sg?sg.toString:void 0;function $w(e){if(typeof e=="string")return e;if(gr(e))return Rw(e,$w)+"";if($c(e))return og?og.call(e):"";var t=e+"";return t=="0"&&1/e==-1/0?"-0":t}var Mk=/\s/;function Dk(e){for(var t=e.length;t--&&Mk.test(e.charAt(t)););return t}var Vk=/^\s+/;function Bk(e){return e&&e.slice(0,Dk(e)+1).replace(Vk,"")}function ir(e){var t=typeof e;return e!=null&&(t=="object"||t=="function")}var ag=NaN,Uk=/^[-+]0x[0-9a-f]+$/i,zk=/^0b[01]+$/i,qk=/^0o[0-7]+$/i,Hk=parseInt;function lg(e){if(typeof e=="number")return e;if($c(e))return ag;if(ir(e)){var t=typeof e.valueOf=="function"?e.valueOf():e;e=ir(t)?t+"":t}if(typeof e!="string")return e===0?e:+e;e=Bk(e);var r=zk.test(e);return r||qk.test(e)?Hk(e.slice(2),r?2:8):Uk.test(e)?ag:+e}function Mw(e){return e}var jk="[object AsyncFunction]",Wk="[object Function]",Kk="[object GeneratorFunction]",Gk="[object Proxy]";function ap(e){if(!ir(e))return!1;var t=Di(e);return t==Wk||t==Kk||t==jk||t==Gk}var Cu=Rr["__core-js_shared__"],cg=(function(){var e=/[^.]+$/.exec(Cu&&Cu.keys&&Cu.keys.IE_PROTO||"");return e?"Symbol(src)_1."+e:""})();function Yk(e){return!!cg&&cg in e}var Xk=Function.prototype,Jk=Xk.toString;function Vi(e){if(e!=null){try{return Jk.call(e)}catch{}try{return e+""}catch{}}return""}var Qk=/[\\^$.*+?()[\]{}|]/g,Zk=/^\[object .+?Constructor\]$/,eO=Function.prototype,tO=Object.prototype,rO=eO.toString,nO=tO.hasOwnProperty,iO=RegExp("^"+rO.call(nO).replace(Qk,"\\$&").replace(/hasOwnProperty|(function).*?(?=\\\()| for .+?(?=\\\])/g,"$1.*?")+"$");function sO(e){if(!ir(e)||Yk(e))return!1;var t=ap(e)?iO:Zk;return t.test(Vi(e))}function oO(e,t){return e?.[t]}function Bi(e,t){var r=oO(e,t);return sO(r)?r:void 0}var kh=Bi(Rr,"WeakMap"),ug=Object.create,aO=(function(){function e(){}return function(t){if(!ir(t))return{};if(ug)return ug(t);e.prototype=t;var r=new e;return e.prototype=void 0,r}})();function lO(e,t,r){switch(r.length){case 0:return e.call(t);case 1:return e.call(t,r[0]);case 2:return e.call(t,r[0],r[1]);case 3:return e.call(t,r[0],r[1],r[2])}return e.apply(t,r)}function Dw(e,t){var r=-1,n=e.length;for(t||(t=Array(n));++r<n;)t[r]=e[r];return t}var cO=800,uO=16,dO=Date.now;function hO(e){var t=0,r=0;return function(){var n=dO(),i=uO-(n-r);if(r=n,i>0){if(++t>=cO)return arguments[0]}else t=0;return e.apply(void 0,arguments)}}function fO(e){return function(){return e}}var Wl=(function(){try{var e=Bi(Object,"defineProperty");return e({},"",{}),e}catch{}})(),pO=Wl?function(e,t){return Wl(e,"toString",{configurable:!0,enumerable:!1,value:fO(t),writable:!0})}:Mw,Vw=hO(pO);function mO(e,t){for(var r=-1,n=e==null?0:e.length;++r<n&&t(e[r],r,e)!==!1;);return e}var gO=9007199254740991,bO=/^(?:0|[1-9]\d*)$/;function Mc(e,t){var r=typeof e;return t=t??gO,!!t&&(r=="number"||r!="symbol"&&bO.test(e))&&e>-1&&e%1==0&&e<t}function lp(e,t,r){t=="__proto__"&&Wl?Wl(e,t,{configurable:!0,enumerable:!0,value:r,writable:!0}):e[t]=r}function da(e,t){return e===t||e!==e&&t!==t}var vO=Object.prototype,yO=vO.hasOwnProperty;function cp(e,t,r){var n=e[t];(!(yO.call(e,t)&&da(n,r))||r===void 0&&!(t in e))&&lp(e,t,r)}function Ps(e,t,r,n){var i=!r;r||(r={});for(var s=-1,o=t.length;++s<o;){var a=t[s],l=void 0;l===void 0&&(l=e[a]),i?lp(r,a,l):cp(r,a,l)}return r}var dg=Math.max;function Bw(e,t,r){return t=dg(t===void 0?e.length-1:t,0),function(){for(var n=arguments,i=-1,s=dg(n.length-t,0),o=Array(s);++i<s;)o[i]=n[t+i];i=-1;for(var a=Array(t+1);++i<t;)a[i]=n[i];return a[t]=r(o),lO(e,this,a)}}function _O(e,t){return Vw(Bw(e,t,Mw),e+"")}var wO=9007199254740991;function up(e){return typeof e=="number"&&e>-1&&e%1==0&&e<=wO}function Dc(e){return e!=null&&up(e.length)&&!ap(e)}function EO(e,t,r){if(!ir(r))return!1;var n=typeof t;return(n=="number"?Dc(r)&&Mc(t,r.length):n=="string"&&t in r)?da(r[t],e):!1}function xO(e){return _O(function(t,r){var n=-1,i=r.length,s=i>1?r[i-1]:void 0,o=i>2?r[2]:void 0;for(s=e.length>3&&typeof s=="function"?(i--,s):void 0,o&&EO(r[0],r[1],o)&&(s=i<3?void 0:s,i=1),t=Object(t);++n<i;){var a=r[n];a&&e(t,a,n,s)}return t})}var SO=Object.prototype;function dp(e){var t=e&&e.constructor,r=typeof t=="function"&&t.prototype||SO;return e===r}function CO(e,t){for(var r=-1,n=Array(e);++r<e;)n[r]=t(r);return n}var AO="[object Arguments]";function hg(e){return Jr(e)&&Di(e)==AO}var Uw=Object.prototype,TO=Uw.hasOwnProperty,kO=Uw.propertyIsEnumerable,jo=hg((function(){return arguments})())?hg:function(e){return Jr(e)&&TO.call(e,"callee")&&!kO.call(e,"callee")};function OO(){return!1}var zw=typeof exports=="object"&&exports&&!exports.nodeType&&exports,fg=zw&&typeof module=="object"&&module&&!module.nodeType&&module,NO=fg&&fg.exports===zw,pg=NO?Rr.Buffer:void 0,PO=pg?pg.isBuffer:void 0,Wo=PO||OO,FO="[object Arguments]",IO="[object Array]",LO="[object Boolean]",RO="[object Date]",$O="[object Error]",MO="[object Function]",DO="[object Map]",VO="[object Number]",BO="[object Object]",UO="[object RegExp]",zO="[object Set]",qO="[object String]",HO="[object WeakMap]",jO="[object ArrayBuffer]",WO="[object DataView]",KO="[object Float32Array]",GO="[object Float64Array]",YO="[object Int8Array]",XO="[object Int16Array]",JO="[object Int32Array]",QO="[object Uint8Array]",ZO="[object Uint8ClampedArray]",eN="[object Uint16Array]",tN="[object Uint32Array]",Me={};Me[KO]=Me[GO]=Me[YO]=Me[XO]=Me[JO]=Me[QO]=Me[ZO]=Me[eN]=Me[tN]=!0;Me[FO]=Me[IO]=Me[jO]=Me[LO]=Me[WO]=Me[RO]=Me[$O]=Me[MO]=Me[DO]=Me[VO]=Me[BO]=Me[UO]=Me[zO]=Me[qO]=Me[HO]=!1;function rN(e){return Jr(e)&&up(e.length)&&!!Me[Di(e)]}function hp(e){return function(t){return e(t)}}var qw=typeof exports=="object"&&exports&&!exports.nodeType&&exports,wo=qw&&typeof module=="object"&&module&&!module.nodeType&&module,nN=wo&&wo.exports===qw,Au=nN&&Iw.process,ys=(function(){try{var e=wo&&wo.require&&wo.require("util").types;return e||Au&&Au.binding&&Au.binding("util")}catch{}})(),mg=ys&&ys.isTypedArray,fp=mg?hp(mg):rN,iN=Object.prototype,sN=iN.hasOwnProperty;function Hw(e,t){var r=gr(e),n=!r&&jo(e),i=!r&&!n&&Wo(e),s=!r&&!n&&!i&&fp(e),o=r||n||i||s,a=o?CO(e.length,String):[],l=a.length;for(var u in e)(t||sN.call(e,u))&&!(o&&(u=="length"||i&&(u=="offset"||u=="parent")||s&&(u=="buffer"||u=="byteLength"||u=="byteOffset")||Mc(u,l)))&&a.push(u);return a}function jw(e,t){return function(r){return e(t(r))}}var oN=jw(Object.keys,Object),aN=Object.prototype,lN=aN.hasOwnProperty;function cN(e){if(!dp(e))return oN(e);var t=[];for(var r in Object(e))lN.call(e,r)&&r!="constructor"&&t.push(r);return t}function pp(e){return Dc(e)?Hw(e):cN(e)}function uN(e){var t=[];if(e!=null)for(var r in Object(e))t.push(r);return t}var dN=Object.prototype,hN=dN.hasOwnProperty;function fN(e){if(!ir(e))return uN(e);var t=dp(e),r=[];for(var n in e)n=="constructor"&&(t||!hN.call(e,n))||r.push(n);return r}function ha(e){return Dc(e)?Hw(e,!0):fN(e)}var pN=/\.|\[(?:[^[\]]*|(["'])(?:(?!\1)[^\\]|\\.)*?\1)\]/,mN=/^\w*$/;function gN(e,t){if(gr(e))return!1;var r=typeof e;return r=="number"||r=="symbol"||r=="boolean"||e==null||$c(e)?!0:mN.test(e)||!pN.test(e)||t!=null&&e in Object(t)}var Ko=Bi(Object,"create");function bN(){this.__data__=Ko?Ko(null):{},this.size=0}function vN(e){var t=this.has(e)&&delete this.__data__[e];return this.size-=t?1:0,t}var yN="__lodash_hash_undefined__",_N=Object.prototype,wN=_N.hasOwnProperty;function EN(e){var t=this.__data__;if(Ko){var r=t[e];return r===yN?void 0:r}return wN.call(t,e)?t[e]:void 0}var xN=Object.prototype,SN=xN.hasOwnProperty;function CN(e){var t=this.__data__;return Ko?t[e]!==void 0:SN.call(t,e)}var AN="__lodash_hash_undefined__";function TN(e,t){var r=this.__data__;return this.size+=this.has(e)?0:1,r[e]=Ko&&t===void 0?AN:t,this}function Fi(e){var t=-1,r=e==null?0:e.length;for(this.clear();++t<r;){var n=e[t];this.set(n[0],n[1])}}Fi.prototype.clear=bN;Fi.prototype.delete=vN;Fi.prototype.get=EN;Fi.prototype.has=CN;Fi.prototype.set=TN;function kN(){this.__data__=[],this.size=0}function Vc(e,t){for(var r=e.length;r--;)if(da(e[r][0],t))return r;return-1}var ON=Array.prototype,NN=ON.splice;function PN(e){var t=this.__data__,r=Vc(t,e);if(r<0)return!1;var n=t.length-1;return r==n?t.pop():NN.call(t,r,1),--this.size,!0}function FN(e){var t=this.__data__,r=Vc(t,e);return r<0?void 0:t[r][1]}function IN(e){return Vc(this.__data__,e)>-1}function LN(e,t){var r=this.__data__,n=Vc(r,e);return n<0?(++this.size,r.push([e,t])):r[n][1]=t,this}function Cn(e){var t=-1,r=e==null?0:e.length;for(this.clear();++t<r;){var n=e[t];this.set(n[0],n[1])}}Cn.prototype.clear=kN;Cn.prototype.delete=PN;Cn.prototype.get=FN;Cn.prototype.has=IN;Cn.prototype.set=LN;var Go=Bi(Rr,"Map");function RN(){this.size=0,this.__data__={hash:new Fi,map:new(Go||Cn),string:new Fi}}function $N(e){var t=typeof e;return t=="string"||t=="number"||t=="symbol"||t=="boolean"?e!=="__proto__":e===null}function Bc(e,t){var r=e.__data__;return $N(t)?r[typeof t=="string"?"string":"hash"]:r.map}function MN(e){var t=Bc(this,e).delete(e);return this.size-=t?1:0,t}function DN(e){return Bc(this,e).get(e)}function VN(e){return Bc(this,e).has(e)}function BN(e,t){var r=Bc(this,e),n=r.size;return r.set(e,t),this.size+=r.size==n?0:1,this}function An(e){var t=-1,r=e==null?0:e.length;for(this.clear();++t<r;){var n=e[t];this.set(n[0],n[1])}}An.prototype.clear=RN;An.prototype.delete=MN;An.prototype.get=DN;An.prototype.has=VN;An.prototype.set=BN;var UN="Expected a function";function mp(e,t){if(typeof e!="function"||t!=null&&typeof t!="function")throw new TypeError(UN);var r=function(){var n=arguments,i=t?t.apply(this,n):n[0],s=r.cache;if(s.has(i))return s.get(i);var o=e.apply(this,n);return r.cache=s.set(i,o)||s,o};return r.cache=new(mp.Cache||An),r}mp.Cache=An;var zN=500;function qN(e){var t=mp(e,function(n){return r.size===zN&&r.clear(),n}),r=t.cache;return t}var HN=/[^.[\]]+|\[(?:(-?\d+(?:\.\d+)?)|(["'])((?:(?!\2)[^\\]|\\.)*?)\2)\]|(?=(?:\.|\[\])(?:\.|\[\]|$))/g,jN=/\\(\\)?/g,WN=qN(function(e){var t=[];return e.charCodeAt(0)===46&&t.push(""),e.replace(HN,function(r,n,i,s){t.push(i?s.replace(jN,"$1"):n||r)}),t});function Ww(e){return e==null?"":$w(e)}function fa(e,t){return gr(e)?e:gN(e,t)?[e]:WN(Ww(e))}function Uc(e){if(typeof e=="string"||$c(e))return e;var t=e+"";return t=="0"&&1/e==-1/0?"-0":t}function Kw(e,t){t=fa(t,e);for(var r=0,n=t.length;e!=null&&r<n;)e=e[Uc(t[r++])];return r&&r==n?e:void 0}function Ar(e,t,r){var n=e==null?void 0:Kw(e,t);return n===void 0?r:n}function gp(e,t){for(var r=-1,n=t.length,i=e.length;++r<n;)e[i+r]=t[r];return e}var gg=mr?mr.isConcatSpreadable:void 0;function KN(e){return gr(e)||jo(e)||!!(gg&&e&&e[gg])}function GN(e,t,r,n,i){var s=-1,o=e.length;for(r||(r=KN),i||(i=[]);++s<o;){var a=e[s];r(a)?gp(i,a):i[i.length]=a}return i}function YN(e){var t=e==null?0:e.length;return t?GN(e):[]}function XN(e){return Vw(Bw(e,void 0,YN),e+"")}var bp=jw(Object.getPrototypeOf,Object),JN="[object Object]",QN=Function.prototype,ZN=Object.prototype,Gw=QN.toString,eP=ZN.hasOwnProperty,tP=Gw.call(Object);function Yw(e){if(!Jr(e)||Di(e)!=JN)return!1;var t=bp(e);if(t===null)return!0;var r=eP.call(t,"constructor")&&t.constructor;return typeof r=="function"&&r instanceof r&&Gw.call(r)==tP}function rP(e,t,r){var n=-1,i=e.length;t<0&&(t=-t>i?0:i+t),r=r>i?i:r,r<0&&(r+=i),i=t>r?0:r-t>>>0,t>>>=0;for(var s=Array(i);++n<i;)s[n]=e[n+t];return s}function nP(e){return function(t){return e?.[t]}}function iP(){this.__data__=new Cn,this.size=0}function sP(e){var t=this.__data__,r=t.delete(e);return this.size=t.size,r}function oP(e){return this.__data__.get(e)}function aP(e){return this.__data__.has(e)}var lP=200;function cP(e,t){var r=this.__data__;if(r instanceof Cn){var n=r.__data__;if(!Go||n.length<lP-1)return n.push([e,t]),this.size=++r.size,this;r=this.__data__=new An(n)}return r.set(e,t),this.size=r.size,this}function Kr(e){var t=this.__data__=new Cn(e);this.size=t.size}Kr.prototype.clear=iP;Kr.prototype.delete=sP;Kr.prototype.get=oP;Kr.prototype.has=aP;Kr.prototype.set=cP;function uP(e,t){return e&&Ps(t,pp(t),e)}function dP(e,t){return e&&Ps(t,ha(t),e)}var Xw=typeof exports=="object"&&exports&&!exports.nodeType&&exports,bg=Xw&&typeof module=="object"&&module&&!module.nodeType&&module,hP=bg&&bg.exports===Xw,vg=hP?Rr.Buffer:void 0,yg=vg?vg.allocUnsafe:void 0;function Jw(e,t){if(t)return e.slice();var r=e.length,n=yg?yg(r):new e.constructor(r);return e.copy(n),n}function fP(e,t){for(var r=-1,n=e==null?0:e.length,i=0,s=[];++r<n;){var o=e[r];t(o,r,e)&&(s[i++]=o)}return s}function Qw(){return[]}var pP=Object.prototype,mP=pP.propertyIsEnumerable,_g=Object.getOwnPropertySymbols,vp=_g?function(e){return e==null?[]:(e=Object(e),fP(_g(e),function(t){return mP.call(e,t)}))}:Qw;function gP(e,t){return Ps(e,vp(e),t)}var bP=Object.getOwnPropertySymbols,Zw=bP?function(e){for(var t=[];e;)gp(t,vp(e)),e=bp(e);return t}:Qw;function vP(e,t){return Ps(e,Zw(e),t)}function e0(e,t,r){var n=t(e);return gr(e)?n:gp(n,r(e))}function Oh(e){return e0(e,pp,vp)}function t0(e){return e0(e,ha,Zw)}var Nh=Bi(Rr,"DataView"),Ph=Bi(Rr,"Promise"),Fh=Bi(Rr,"Set"),wg="[object Map]",yP="[object Object]",Eg="[object Promise]",xg="[object Set]",Sg="[object WeakMap]",Cg="[object DataView]",_P=Vi(Nh),wP=Vi(Go),EP=Vi(Ph),xP=Vi(Fh),SP=Vi(kh),xr=Di;(Nh&&xr(new Nh(new ArrayBuffer(1)))!=Cg||Go&&xr(new Go)!=wg||Ph&&xr(Ph.resolve())!=Eg||Fh&&xr(new Fh)!=xg||kh&&xr(new kh)!=Sg)&&(xr=function(e){var t=Di(e),r=t==yP?e.constructor:void 0,n=r?Vi(r):"";if(n)switch(n){case _P:return Cg;case wP:return wg;case EP:return Eg;case xP:return xg;case SP:return Sg}return t});var CP=Object.prototype,AP=CP.hasOwnProperty;function TP(e){var t=e.length,r=new e.constructor(t);return t&&typeof e[0]=="string"&&AP.call(e,"index")&&(r.index=e.index,r.input=e.input),r}var Kl=Rr.Uint8Array;function yp(e){var t=new e.constructor(e.byteLength);return new Kl(t).set(new Kl(e)),t}function kP(e,t){var r=t?yp(e.buffer):e.buffer;return new e.constructor(r,e.byteOffset,e.byteLength)}var OP=/\w*$/;function NP(e){var t=new e.constructor(e.source,OP.exec(e));return t.lastIndex=e.lastIndex,t}var Ag=mr?mr.prototype:void 0,Tg=Ag?Ag.valueOf:void 0;function PP(e){return Tg?Object(Tg.call(e)):{}}function r0(e,t){var r=t?yp(e.buffer):e.buffer;return new e.constructor(r,e.byteOffset,e.length)}var FP="[object Boolean]",IP="[object Date]",LP="[object Map]",RP="[object Number]",$P="[object RegExp]",MP="[object Set]",DP="[object String]",VP="[object Symbol]",BP="[object ArrayBuffer]",UP="[object DataView]",zP="[object Float32Array]",qP="[object Float64Array]",HP="[object Int8Array]",jP="[object Int16Array]",WP="[object Int32Array]",KP="[object Uint8Array]",GP="[object Uint8ClampedArray]",YP="[object Uint16Array]",XP="[object Uint32Array]";function JP(e,t,r){var n=e.constructor;switch(t){case BP:return yp(e);case FP:case IP:return new n(+e);case UP:return kP(e,r);case zP:case qP:case HP:case jP:case WP:case KP:case GP:case YP:case XP:return r0(e,r);case LP:return new n;case RP:case DP:return new n(e);case $P:return NP(e);case MP:return new n;case VP:return PP(e)}}function n0(e){return typeof e.constructor=="function"&&!dp(e)?aO(bp(e)):{}}var QP="[object Map]";function ZP(e){return Jr(e)&&xr(e)==QP}var kg=ys&&ys.isMap,eF=kg?hp(kg):ZP,tF="[object Set]";function rF(e){return Jr(e)&&xr(e)==tF}var Og=ys&&ys.isSet,nF=Og?hp(Og):rF,iF=1,sF=2,oF=4,i0="[object Arguments]",aF="[object Array]",lF="[object Boolean]",cF="[object Date]",uF="[object Error]",s0="[object Function]",dF="[object GeneratorFunction]",hF="[object Map]",fF="[object Number]",o0="[object Object]",pF="[object RegExp]",mF="[object Set]",gF="[object String]",bF="[object Symbol]",vF="[object WeakMap]",yF="[object ArrayBuffer]",_F="[object DataView]",wF="[object Float32Array]",EF="[object Float64Array]",xF="[object Int8Array]",SF="[object Int16Array]",CF="[object Int32Array]",AF="[object Uint8Array]",TF="[object Uint8ClampedArray]",kF="[object Uint16Array]",OF="[object Uint32Array]",Pe={};Pe[i0]=Pe[aF]=Pe[yF]=Pe[_F]=Pe[lF]=Pe[cF]=Pe[wF]=Pe[EF]=Pe[xF]=Pe[SF]=Pe[CF]=Pe[hF]=Pe[fF]=Pe[o0]=Pe[pF]=Pe[mF]=Pe[gF]=Pe[bF]=Pe[AF]=Pe[TF]=Pe[kF]=Pe[OF]=!0;Pe[uF]=Pe[s0]=Pe[vF]=!1;function Eo(e,t,r,n,i,s){var o,a=t&iF,l=t&sF,u=t&oF;if(r&&(o=i?r(e,n,i,s):r(e)),o!==void 0)return o;if(!ir(e))return e;var c=gr(e);if(c){if(o=TP(e),!a)return Dw(e,o)}else{var d=xr(e),p=d==s0||d==dF;if(Wo(e))return Jw(e,a);if(d==o0||d==i0||p&&!i){if(o=l||p?{}:n0(e),!a)return l?vP(e,dP(o,e)):gP(e,uP(o,e))}else{if(!Pe[d])return i?e:{};o=JP(e,d,a)}}s||(s=new Kr);var m=s.get(e);if(m)return m;s.set(e,o),nF(e)?e.forEach(function(b){o.add(Eo(b,t,r,b,e,s))}):eF(e)&&e.forEach(function(b,y){o.set(y,Eo(b,t,r,y,e,s))});var h=u?l?t0:Oh:l?ha:pp,f=c?void 0:h(e);return mO(f||e,function(b,y){f&&(y=b,b=e[y]),cp(o,y,Eo(b,t,r,y,e,s))}),o}var NF=1,PF=4;function ht(e){return Eo(e,NF|PF)}var FF="__lodash_hash_undefined__";function IF(e){return this.__data__.set(e,FF),this}function LF(e){return this.__data__.has(e)}function Gl(e){var t=-1,r=e==null?0:e.length;for(this.__data__=new An;++t<r;)this.add(e[t])}Gl.prototype.add=Gl.prototype.push=IF;Gl.prototype.has=LF;function RF(e,t){for(var r=-1,n=e==null?0:e.length;++r<n;)if(t(e[r],r,e))return!0;return!1}function $F(e,t){return e.has(t)}var MF=1,DF=2;function a0(e,t,r,n,i,s){var o=r&MF,a=e.length,l=t.length;if(a!=l&&!(o&&l>a))return!1;var u=s.get(e),c=s.get(t);if(u&&c)return u==t&&c==e;var d=-1,p=!0,m=r&DF?new Gl:void 0;for(s.set(e,t),s.set(t,e);++d<a;){var h=e[d],f=t[d];if(n)var b=o?n(f,h,d,t,e,s):n(h,f,d,e,t,s);if(b!==void 0){if(b)continue;p=!1;break}if(m){if(!RF(t,function(y,w){if(!$F(m,w)&&(h===y||i(h,y,r,n,s)))return m.push(w)})){p=!1;break}}else if(!(h===f||i(h,f,r,n,s))){p=!1;break}}return s.delete(e),s.delete(t),p}function VF(e){var t=-1,r=Array(e.size);return e.forEach(function(n,i){r[++t]=[i,n]}),r}function BF(e){var t=-1,r=Array(e.size);return e.forEach(function(n){r[++t]=n}),r}var UF=1,zF=2,qF="[object Boolean]",HF="[object Date]",jF="[object Error]",WF="[object Map]",KF="[object Number]",GF="[object RegExp]",YF="[object Set]",XF="[object String]",JF="[object Symbol]",QF="[object ArrayBuffer]",ZF="[object DataView]",Ng=mr?mr.prototype:void 0,Tu=Ng?Ng.valueOf:void 0;function eI(e,t,r,n,i,s,o){switch(r){case ZF:if(e.byteLength!=t.byteLength||e.byteOffset!=t.byteOffset)return!1;e=e.buffer,t=t.buffer;case QF:return!(e.byteLength!=t.byteLength||!s(new Kl(e),new Kl(t)));case qF:case HF:case KF:return da(+e,+t);case jF:return e.name==t.name&&e.message==t.message;case GF:case XF:return e==t+"";case WF:var a=VF;case YF:var l=n&UF;if(a||(a=BF),e.size!=t.size&&!l)return!1;var u=o.get(e);if(u)return u==t;n|=zF,o.set(e,t);var c=a0(a(e),a(t),n,i,s,o);return o.delete(e),c;case JF:if(Tu)return Tu.call(e)==Tu.call(t)}return!1}var tI=1,rI=Object.prototype,nI=rI.hasOwnProperty;function iI(e,t,r,n,i,s){var o=r&tI,a=Oh(e),l=a.length,u=Oh(t),c=u.length;if(l!=c&&!o)return!1;for(var d=l;d--;){var p=a[d];if(!(o?p in t:nI.call(t,p)))return!1}var m=s.get(e),h=s.get(t);if(m&&h)return m==t&&h==e;var f=!0;s.set(e,t),s.set(t,e);for(var b=o;++d<l;){p=a[d];var y=e[p],w=t[p];if(n)var v=o?n(w,y,p,t,e,s):n(y,w,p,e,t,s);if(!(v===void 0?y===w||i(y,w,r,n,s):v)){f=!1;break}b||(b=p=="constructor")}if(f&&!b){var _=e.constructor,x=t.constructor;_!=x&&"constructor"in e&&"constructor"in t&&!(typeof _=="function"&&_ instanceof _&&typeof x=="function"&&x instanceof x)&&(f=!1)}return s.delete(e),s.delete(t),f}var sI=1,Pg="[object Arguments]",Fg="[object Array]",Ua="[object Object]",oI=Object.prototype,Ig=oI.hasOwnProperty;function aI(e,t,r,n,i,s){var o=gr(e),a=gr(t),l=o?Fg:xr(e),u=a?Fg:xr(t);l=l==Pg?Ua:l,u=u==Pg?Ua:u;var c=l==Ua,d=u==Ua,p=l==u;if(p&&Wo(e)){if(!Wo(t))return!1;o=!0,c=!1}if(p&&!c)return s||(s=new Kr),o||fp(e)?a0(e,t,r,n,i,s):eI(e,t,l,r,n,i,s);if(!(r&sI)){var m=c&&Ig.call(e,"__wrapped__"),h=d&&Ig.call(t,"__wrapped__");if(m||h){var f=m?e.value():e,b=h?t.value():t;return s||(s=new Kr),i(f,b,r,n,s)}}return p?(s||(s=new Kr),iI(e,t,r,n,i,s)):!1}function l0(e,t,r,n,i){return e===t?!0:e==null||t==null||!Jr(e)&&!Jr(t)?e!==e&&t!==t:aI(e,t,r,n,l0,i)}function lI(e,t,r){t=fa(t,e);for(var n=-1,i=t.length,s=!1;++n<i;){var o=Uc(t[n]);if(!(s=e!=null&&r(e,o)))break;e=e[o]}return s||++n!=i?s:(i=e==null?0:e.length,!!i&&up(i)&&Mc(o,i)&&(gr(e)||jo(e)))}function cI(e){return function(t,r,n){for(var i=-1,s=Object(t),o=n(t),a=o.length;a--;){var l=o[++i];if(r(s[l],l,s)===!1)break}return t}}var uI=cI(),ku=function(){return Rr.Date.now()},dI="Expected a function",hI=Math.max,fI=Math.min;function pI(e,t,r){var n,i,s,o,a,l,u=0,c=!1,d=!1,p=!0;if(typeof e!="function")throw new TypeError(dI);t=lg(t)||0,ir(r)&&(c=!0,d="maxWait"in r,s=d?hI(lg(r.maxWait)||0,t):s,p="trailing"in r?!0:p);function m(k){var S=n,N=i;return n=i=void 0,u=k,o=e.apply(N,S),o}function h(k){return u=k,a=setTimeout(y,t),c?m(k):o}function f(k){var S=k-l,N=k-u,A=t-S;return d?fI(A,s-N):A}function b(k){var S=k-l,N=k-u;return l===void 0||S>=t||S<0||d&&N>=s}function y(){var k=ku();if(b(k))return w(k);a=setTimeout(y,f(k))}function w(k){return a=void 0,p&&n?m(k):(n=i=void 0,o)}function v(){a!==void 0&&clearTimeout(a),u=0,n=l=i=a=void 0}function _(){return a===void 0?o:w(ku())}function x(){var k=ku(),S=b(k);if(n=arguments,i=this,l=k,S){if(a===void 0)return h(l);if(d)return clearTimeout(a),a=setTimeout(y,t),m(l)}return a===void 0&&(a=setTimeout(y,t)),o}return x.cancel=v,x.flush=_,x}function Ih(e,t,r){(r!==void 0&&!da(e[t],r)||r===void 0&&!(t in e))&&lp(e,t,r)}function mI(e){return Jr(e)&&Dc(e)}function Lh(e,t){if(!(t==="constructor"&&typeof e[t]=="function")&&t!="__proto__")return e[t]}function gI(e){return Ps(e,ha(e))}function bI(e,t,r,n,i,s,o){var a=Lh(e,r),l=Lh(t,r),u=o.get(l);if(u){Ih(e,r,u);return}var c=s?s(a,l,r+"",e,t,o):void 0,d=c===void 0;if(d){var p=gr(l),m=!p&&Wo(l),h=!p&&!m&&fp(l);c=l,p||m||h?gr(a)?c=a:mI(a)?c=Dw(a):m?(d=!1,c=Jw(l,!0)):h?(d=!1,c=r0(l,!0)):c=[]:Yw(l)||jo(l)?(c=a,jo(a)?c=gI(a):(!ir(a)||ap(a))&&(c=n0(l))):d=!1}d&&(o.set(l,c),i(c,l,n,s,o),o.delete(l)),Ih(e,r,c)}function c0(e,t,r,n,i){e!==t&&uI(t,function(s,o){if(i||(i=new Kr),ir(s))bI(e,t,o,r,c0,n,i);else{var a=n?n(Lh(e,o),s,o+"",e,t,i):void 0;a===void 0&&(a=s),Ih(e,o,a)}},ha)}function vI(e){var t=e==null?0:e.length;return t?e[t-1]:void 0}var yI={"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"},_I=nP(yI),u0=/[&<>"']/g,wI=RegExp(u0.source);function EI(e){return e=Ww(e),e&&wI.test(e)?e.replace(u0,_I):e}var xI=Object.prototype,SI=xI.hasOwnProperty;function CI(e,t){return e!=null&&SI.call(e,t)}function d0(e,t){return e!=null&&lI(e,t,CI)}function AI(e,t){return t.length<2?e:Kw(e,rP(t,0,-1))}function ei(e,t){return l0(e,t)}var Rh=xO(function(e,t,r){c0(e,t,r)}),TI=Object.prototype,kI=TI.hasOwnProperty;function OI(e,t){t=fa(t,e);var r=-1,n=t.length;if(!n)return!0;for(var i=e==null||typeof e!="object"&&typeof e!="function";++r<n;){var s=t[r];if(typeof s=="string"){if(s==="__proto__"&&!kI.call(e,"__proto__"))return!1;if(s==="constructor"&&r+1<n&&typeof t[r+1]=="string"&&t[r+1]==="prototype"){if(i&&r===0)continue;return!1}}}var o=AI(e,t);return o==null||delete o[Uc(vI(t))]}function NI(e){return Yw(e)?void 0:e}var PI=1,FI=2,II=4,Lg=XN(function(e,t){var r={};if(e==null)return r;var n=!1;t=Rw(t,function(s){return s=fa(s,e),n||(n=s.length>1),s}),Ps(e,t0(e),r),n&&(r=Eo(r,PI|FI|II,NI));for(var i=t.length;i--;)OI(r,t[i]);return r});function LI(e,t,r,n){if(!ir(e))return e;t=fa(t,e);for(var i=-1,s=t.length,o=s-1,a=e;a!=null&&++i<s;){var l=Uc(t[i]),u=r;if(l==="__proto__"||l==="constructor"||l==="prototype")return e;if(i!=o){var c=a[l];u=void 0,u===void 0&&(u=ir(c)?c:Mc(t[i+1])?[]:{})}cp(a,l,u),a=a[l]}return e}function qr(e,t,r){return e==null?e:LI(e,t,r)}var Rg=typeof globalThis<"u"?globalThis:typeof window<"u"?window:typeof global<"u"?global:typeof self<"u"?self:{};function RI(e){if(Object.prototype.hasOwnProperty.call(e,"__esModule"))return e;var t=e.default;if(typeof t=="function"){var r=function n(){var i=!1;try{i=this instanceof n}catch{}return i?Reflect.construct(t,arguments,this.constructor):t.apply(this,arguments)};r.prototype=t.prototype}else r={};return Object.defineProperty(r,"__esModule",{value:!0}),Object.keys(e).forEach(function(n){var i=Object.getOwnPropertyDescriptor(e,n);Object.defineProperty(r,n,i.get?i:{enumerable:!0,get:function(){return e[n]}})}),r}var Ou,$g;function Fs(){return $g||($g=1,Ou=TypeError),Ou}const $I={},MI=Object.freeze(Object.defineProperty({__proto__:null,default:$I},Symbol.toStringTag,{value:"Module"})),DI=RI(MI);var Nu,Mg;function zc(){if(Mg)return Nu;Mg=1;var e=typeof Map=="function"&&Map.prototype,t=Object.getOwnPropertyDescriptor&&e?Object.getOwnPropertyDescriptor(Map.prototype,"size"):null,r=e&&t&&typeof t.get=="function"?t.get:null,n=e&&Map.prototype.forEach,i=typeof Set=="function"&&Set.prototype,s=Object.getOwnPropertyDescriptor&&i?Object.getOwnPropertyDescriptor(Set.prototype,"size"):null,o=i&&s&&typeof s.get=="function"?s.get:null,a=i&&Set.prototype.forEach,l=typeof WeakMap=="function"&&WeakMap.prototype,u=l?WeakMap.prototype.has:null,c=typeof WeakSet=="function"&&WeakSet.prototype,d=c?WeakSet.prototype.has:null,p=typeof WeakRef=="function"&&WeakRef.prototype,m=p?WeakRef.prototype.deref:null,h=Boolean.prototype.valueOf,f=Object.prototype.toString,b=Function.prototype.toString,y=String.prototype.match,w=String.prototype.slice,v=String.prototype.replace,_=String.prototype.toUpperCase,x=String.prototype.toLowerCase,k=RegExp.prototype.test,S=Array.prototype.concat,N=Array.prototype.join,A=Array.prototype.slice,T=Math.floor,F=typeof BigInt=="function"?BigInt.prototype.valueOf:null,C=Object.getOwnPropertySymbols,g=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?Symbol.prototype.toString:null,L=typeof Symbol=="function"&&typeof Symbol.iterator=="object",B=typeof Symbol=="function"&&Symbol.toStringTag&&(typeof Symbol.toStringTag===L||!0)?Symbol.toStringTag:null,$=Object.prototype.propertyIsEnumerable,z=(typeof Reflect=="function"?Reflect.getPrototypeOf:Object.getPrototypeOf)||([].__proto__===Array.prototype?function(R){return R.__proto__}:null);function P(R,M){if(R===1/0||R===-1/0||R!==R||R&&R>-1e3&&R<1e3||k.call(/e/,M))return M;var ve=/[0-9](?=(?:[0-9]{3})+(?![0-9]))/g;if(typeof R=="number"){var Ae=R<0?-T(-R):T(R);if(Ae!==R){var $e=String(Ae),fe=w.call(M,$e.length+1);return v.call($e,ve,"$&_")+"."+v.call(v.call(fe,/([0-9]{3})/g,"$&_"),/_$/,"")}}return v.call(M,ve,"$&_")}var Z=DI,be=Z.custom,le=I(be)?be:null,je={__proto__:null,double:'"',single:"'"},vr={__proto__:null,double:/(["\\])/g,single:/(['\\])/g};Nu=function R(M,ve,Ae,$e){var fe=ve||{};if(q(fe,"quoteStyle")&&!q(je,fe.quoteStyle))throw new TypeError('option "quoteStyle" must be "single" or "double"');if(q(fe,"maxStringLength")&&(typeof fe.maxStringLength=="number"?fe.maxStringLength<0&&fe.maxStringLength!==1/0:fe.maxStringLength!==null))throw new TypeError('option "maxStringLength", if provided, must be a positive integer, Infinity, or `null`');var kn=q(fe,"customInspect")?fe.customInspect:!0;if(typeof kn!="boolean"&&kn!=="symbol")throw new TypeError("option \"customInspect\", if provided, must be `true`, `false`, or `'symbol'`");if(q(fe,"indent")&&fe.indent!==null&&fe.indent!=="	"&&!(parseInt(fe.indent,10)===fe.indent&&fe.indent>0))throw new TypeError('option "indent" must be "\\t", an integer > 0, or `null`');if(q(fe,"numericSeparator")&&typeof fe.numericSeparator!="boolean")throw new TypeError('option "numericSeparator", if provided, must be `true` or `false`');var li=fe.numericSeparator;if(typeof M>"u")return"undefined";if(M===null)return"null";if(typeof M=="boolean")return M?"true":"false";if(typeof M=="string")return pe(M,fe);if(typeof M=="number"){if(M===0)return 1/0/M>0?"0":"-0";var Wt=String(M);return li?P(M,Wt):Wt}if(typeof M=="bigint"){var On=String(M)+"n";return li?P(M,On):On}var iu=typeof fe.depth>"u"?5:fe.depth;if(typeof Ae>"u"&&(Ae=0),Ae>=iu&&iu>0&&typeof M=="object")return Ht(M)?"[Array]":"[Object]";var zi=Pt(fe,Ae);if(typeof $e>"u")$e=[];else if(X($e,M)>=0)return"[Circular]";function _r(qi,Aa,YE){if(Aa&&($e=A.call($e),$e.push(Aa)),YE){var Kp={depth:fe.depth};return q(fe,"quoteStyle")&&(Kp.quoteStyle=fe.quoteStyle),R(qi,Kp,Ae+1,$e)}return R(qi,fe,Ae+1,$e)}if(typeof M=="function"&&!Xe(M)){var Bp=J(M),Up=ai(M,_r);return"[Function"+(Bp?": "+Bp:" (anonymous)")+"]"+(Up.length>0?" { "+N.call(Up,", ")+" }":"")}if(I(M)){var zp=L?v.call(String(M),/^(Symbol\(.*\))_[^)]*$/,"$1"):g.call(M);return typeof M=="object"&&!L?ge(zp):zp}if(ae(M)){for(var Ds="<"+x.call(String(M.nodeName)),su=M.attributes||[],Ca=0;Ca<su.length;Ca++)Ds+=" "+su[Ca].name+"="+$r(yr(su[Ca].value),"double",fe);return Ds+=">",M.childNodes&&M.childNodes.length&&(Ds+="..."),Ds+="</"+x.call(String(M.nodeName))+">",Ds}if(Ht(M)){if(M.length===0)return"[]";var ou=ai(M,_r);return zi&&!jt(ou)?"["+tn(ou,zi)+"]":"[ "+N.call(ou,", ")+" ]"}if(he(M)){var au=ai(M,_r);return!("cause"in Error.prototype)&&"cause"in M&&!$.call(M,"cause")?"{ ["+String(M)+"] "+N.call(S.call("[cause]: "+_r(M.cause),au),", ")+" }":au.length===0?"["+String(M)+"]":"{ ["+String(M)+"] "+N.call(au,", ")+" }"}if(typeof M=="object"&&kn){if(le&&typeof M[le]=="function"&&Z)return Z(M,{depth:iu-Ae});if(kn!=="symbol"&&typeof M.inspect=="function")return M.inspect()}if(G(M)){var qp=[];return n&&n.call(M,function(qi,Aa){qp.push(_r(Aa,M,!0)+" => "+_r(qi,M))}),rt("Map",r.call(M),qp,zi)}if(Q(M)){var Hp=[];return a&&a.call(M,function(qi){Hp.push(_r(qi,M))}),rt("Set",o.call(M),Hp,zi)}if(K(M))return dt("WeakMap");if(re(M))return dt("WeakSet");if(ne(M))return dt("WeakRef");if(Se(M))return ge(_r(Number(M)));if(U(M))return ge(_r(F.call(M)));if(E(M))return ge(h.call(M));if(Re(M))return ge(_r(String(M)));if(typeof window<"u"&&M===window)return"{ [object Window] }";if(typeof globalThis<"u"&&M===globalThis||typeof Rg<"u"&&M===Rg)return"{ [object globalThis] }";if(!lr(M)&&!Xe(M)){var lu=ai(M,_r),jp=z?z(M)===Object.prototype:M instanceof Object||M.constructor===Object,cu=M instanceof Object?"":"null prototype",Wp=!jp&&B&&Object(M)===M&&B in M?w.call(j(M),8,-1):cu?"Object":"",GE=jp||typeof M.constructor!="function"?"":M.constructor.name?M.constructor.name+" ":"",uu=GE+(Wp||cu?"["+N.call(S.call([],Wp||[],cu||[]),": ")+"] ":"");return lu.length===0?uu+"{}":zi?uu+"{"+tn(lu,zi)+"}":uu+"{ "+N.call(lu,", ")+" }"}return String(M)};function $r(R,M,ve){var Ae=ve.quoteStyle||M,$e=je[Ae];return $e+R+$e}function yr(R){return v.call(String(R),/"/g,"&quot;")}function ut(R){return!B||!(typeof R=="object"&&(B in R||typeof R[B]<"u"))}function Ht(R){return j(R)==="[object Array]"&&ut(R)}function lr(R){return j(R)==="[object Date]"&&ut(R)}function Xe(R){return j(R)==="[object RegExp]"&&ut(R)}function he(R){return j(R)==="[object Error]"&&ut(R)}function Re(R){return j(R)==="[object String]"&&ut(R)}function Se(R){return j(R)==="[object Number]"&&ut(R)}function E(R){return j(R)==="[object Boolean]"&&ut(R)}function I(R){if(L)return R&&typeof R=="object"&&R instanceof Symbol;if(typeof R=="symbol")return!0;if(!R||typeof R!="object"||!g)return!1;try{return g.call(R),!0}catch{}return!1}function U(R){if(!R||typeof R!="object"||!F)return!1;try{return F.call(R),!0}catch{}return!1}var H=Object.prototype.hasOwnProperty||function(R){return R in this};function q(R,M){return H.call(R,M)}function j(R){return f.call(R)}function J(R){if(R.name)return R.name;var M=y.call(b.call(R),/^function\s*([\w$]+)/);return M?M[1]:null}function X(R,M){if(R.indexOf)return R.indexOf(M);for(var ve=0,Ae=R.length;ve<Ae;ve++)if(R[ve]===M)return ve;return-1}function G(R){if(!r||!R||typeof R!="object")return!1;try{r.call(R);try{o.call(R)}catch{return!0}return R instanceof Map}catch{}return!1}function K(R){if(!u||!R||typeof R!="object")return!1;try{u.call(R,u);try{d.call(R,d)}catch{return!0}return R instanceof WeakMap}catch{}return!1}function ne(R){if(!m||!R||typeof R!="object")return!1;try{return m.call(R),!0}catch{}return!1}function Q(R){if(!o||!R||typeof R!="object")return!1;try{o.call(R);try{r.call(R)}catch{return!0}return R instanceof Set}catch{}return!1}function re(R){if(!d||!R||typeof R!="object")return!1;try{d.call(R,d);try{u.call(R,u)}catch{return!0}return R instanceof WeakSet}catch{}return!1}function ae(R){return!R||typeof R!="object"?!1:typeof HTMLElement<"u"&&R instanceof HTMLElement?!0:typeof R.nodeName=="string"&&typeof R.getAttribute=="function"}function pe(R,M){if(R.length>M.maxStringLength){var ve=R.length-M.maxStringLength,Ae="... "+ve+" more character"+(ve>1?"s":"");return pe(w.call(R,0,M.maxStringLength),M)+Ae}var $e=vr[M.quoteStyle||"single"];$e.lastIndex=0;var fe=v.call(v.call(R,$e,"\\$1"),/[\x00-\x1f]/g,Oe);return $r(fe,"single",M)}function Oe(R){var M=R.charCodeAt(0),ve={8:"b",9:"t",10:"n",12:"f",13:"r"}[M];return ve?"\\"+ve:"\\x"+(M<16?"0":"")+_.call(M.toString(16))}function ge(R){return"Object("+R+")"}function dt(R){return R+" { ? }"}function rt(R,M,ve,Ae){var $e=Ae?tn(ve,Ae):N.call(ve,", ");return R+" ("+M+") {"+$e+"}"}function jt(R){for(var M=0;M<R.length;M++)if(X(R[M],`
`)>=0)return!1;return!0}function Pt(R,M){var ve;if(R.indent==="	")ve="	";else if(typeof R.indent=="number"&&R.indent>0)ve=N.call(Array(R.indent+1)," ");else return null;return{base:ve,prev:N.call(Array(M+1),ve)}}function tn(R,M){if(R.length===0)return"";var ve=`
`+M.prev+M.base;return ve+N.call(R,","+ve)+`
`+M.prev}function ai(R,M){var ve=Ht(R),Ae=[];if(ve){Ae.length=R.length;for(var $e=0;$e<R.length;$e++)Ae[$e]=q(R,$e)?M(R[$e],R):""}var fe=typeof C=="function"?C(R):[],kn;if(L){kn={};for(var li=0;li<fe.length;li++)kn["$"+fe[li]]=fe[li]}for(var Wt in R)q(R,Wt)&&(ve&&String(Number(Wt))===Wt&&Wt<R.length||L&&kn["$"+Wt]instanceof Symbol||(k.call(/[^\w$]/,Wt)?Ae.push(M(Wt,R)+": "+M(R[Wt],R)):Ae.push(Wt+": "+M(R[Wt],R))));if(typeof C=="function")for(var On=0;On<fe.length;On++)$.call(R,fe[On])&&Ae.push("["+M(fe[On])+"]: "+M(R[fe[On]],R));return Ae}return Nu}var Pu,Dg;function VI(){if(Dg)return Pu;Dg=1;var e=zc(),t=Fs(),r=function(a,l,u){for(var c=a,d;(d=c.next)!=null;c=d)if(d.key===l)return c.next=d.next,u||(d.next=a.next,a.next=d),d},n=function(a,l){if(a){var u=r(a,l);return u&&u.value}},i=function(a,l,u){var c=r(a,l);c?c.value=u:a.next={key:l,next:a.next,value:u}},s=function(a,l){return a?!!r(a,l):!1},o=function(a,l){if(a)return r(a,l,!0)};return Pu=function(){var l,u={assert:function(c){if(!u.has(c))throw new t("Side channel does not contain "+e(c))},delete:function(c){var d=l&&l.next,p=o(l,c);return p&&d&&d===p&&(l=void 0),!!p},get:function(c){return n(l,c)},has:function(c){return s(l,c)},set:function(c,d){l||(l={next:void 0}),i(l,c,d)}};return u},Pu}var Fu,Vg;function h0(){return Vg||(Vg=1,Fu=Object),Fu}var Iu,Bg;function BI(){return Bg||(Bg=1,Iu=Error),Iu}var Lu,Ug;function UI(){return Ug||(Ug=1,Lu=EvalError),Lu}var Ru,zg;function zI(){return zg||(zg=1,Ru=RangeError),Ru}var $u,qg;function qI(){return qg||(qg=1,$u=ReferenceError),$u}var Mu,Hg;function HI(){return Hg||(Hg=1,Mu=SyntaxError),Mu}var Du,jg;function jI(){return jg||(jg=1,Du=URIError),Du}var Vu,Wg;function WI(){return Wg||(Wg=1,Vu=Math.abs),Vu}var Bu,Kg;function KI(){return Kg||(Kg=1,Bu=Math.floor),Bu}var Uu,Gg;function GI(){return Gg||(Gg=1,Uu=Math.max),Uu}var zu,Yg;function YI(){return Yg||(Yg=1,zu=Math.min),zu}var qu,Xg;function XI(){return Xg||(Xg=1,qu=Math.pow),qu}var Hu,Jg;function JI(){return Jg||(Jg=1,Hu=Math.round),Hu}var ju,Qg;function QI(){return Qg||(Qg=1,ju=Number.isNaN||function(t){return t!==t}),ju}var Wu,Zg;function ZI(){if(Zg)return Wu;Zg=1;var e=QI();return Wu=function(r){return e(r)||r===0?r:r<0?-1:1},Wu}var Ku,eb;function e2(){return eb||(eb=1,Ku=Object.getOwnPropertyDescriptor),Ku}var Gu,tb;function f0(){if(tb)return Gu;tb=1;var e=e2();if(e)try{e([],"length")}catch{e=null}return Gu=e,Gu}var Yu,rb;function t2(){if(rb)return Yu;rb=1;var e=Object.defineProperty||!1;if(e)try{e({},"a",{value:1})}catch{e=!1}return Yu=e,Yu}var Xu,nb;function r2(){return nb||(nb=1,Xu=function(){if(typeof Symbol!="function"||typeof Object.getOwnPropertySymbols!="function")return!1;if(typeof Symbol.iterator=="symbol")return!0;var t={},r=Symbol("test"),n=Object(r);if(typeof r=="string"||Object.prototype.toString.call(r)!=="[object Symbol]"||Object.prototype.toString.call(n)!=="[object Symbol]")return!1;var i=42;t[r]=i;for(var s in t)return!1;if(typeof Object.keys=="function"&&Object.keys(t).length!==0||typeof Object.getOwnPropertyNames=="function"&&Object.getOwnPropertyNames(t).length!==0)return!1;var o=Object.getOwnPropertySymbols(t);if(o.length!==1||o[0]!==r||!Object.prototype.propertyIsEnumerable.call(t,r))return!1;if(typeof Object.getOwnPropertyDescriptor=="function"){var a=Object.getOwnPropertyDescriptor(t,r);if(a.value!==i||a.enumerable!==!0)return!1}return!0}),Xu}var Ju,ib;function n2(){if(ib)return Ju;ib=1;var e=typeof Symbol<"u"&&Symbol,t=r2();return Ju=function(){return typeof e!="function"||typeof Symbol!="function"||typeof e("foo")!="symbol"||typeof Symbol("bar")!="symbol"?!1:t()},Ju}var Qu,sb;function p0(){return sb||(sb=1,Qu=typeof Reflect<"u"&&Reflect.getPrototypeOf||null),Qu}var Zu,ob;function m0(){if(ob)return Zu;ob=1;var e=h0();return Zu=e.getPrototypeOf||null,Zu}var ed,ab;function i2(){if(ab)return ed;ab=1;var e="Function.prototype.bind called on incompatible ",t=Object.prototype.toString,r=Math.max,n="[object Function]",i=function(l,u){for(var c=[],d=0;d<l.length;d+=1)c[d]=l[d];for(var p=0;p<u.length;p+=1)c[p+l.length]=u[p];return c},s=function(l,u){for(var c=[],d=u,p=0;d<l.length;d+=1,p+=1)c[p]=l[d];return c},o=function(a,l){for(var u="",c=0;c<a.length;c+=1)u+=a[c],c+1<a.length&&(u+=l);return u};return ed=function(l){var u=this;if(typeof u!="function"||t.apply(u)!==n)throw new TypeError(e+u);for(var c=s(arguments,1),d,p=function(){if(this instanceof d){var y=u.apply(this,i(c,arguments));return Object(y)===y?y:this}return u.apply(l,i(c,arguments))},m=r(0,u.length-c.length),h=[],f=0;f<m;f++)h[f]="$"+f;if(d=Function("binder","return function ("+o(h,",")+"){ return binder.apply(this,arguments); }")(p),u.prototype){var b=function(){};b.prototype=u.prototype,d.prototype=new b,b.prototype=null}return d},ed}var td,lb;function qc(){if(lb)return td;lb=1;var e=i2();return td=Function.prototype.bind||e,td}var rd,cb;function _p(){return cb||(cb=1,rd=Function.prototype.call),rd}var nd,ub;function g0(){return ub||(ub=1,nd=Function.prototype.apply),nd}var id,db;function s2(){return db||(db=1,id=typeof Reflect<"u"&&Reflect&&Reflect.apply),id}var sd,hb;function o2(){if(hb)return sd;hb=1;var e=qc(),t=g0(),r=_p(),n=s2();return sd=n||e.call(r,t),sd}var od,fb;function b0(){if(fb)return od;fb=1;var e=qc(),t=Fs(),r=_p(),n=o2();return od=function(s){if(s.length<1||typeof s[0]!="function")throw new t("a function is required");return n(e,r,s)},od}var ad,pb;function a2(){if(pb)return ad;pb=1;var e=b0(),t=f0(),r;try{r=[].__proto__===Array.prototype}catch(o){if(!o||typeof o!="object"||!("code"in o)||o.code!=="ERR_PROTO_ACCESS")throw o}var n=!!r&&t&&t(Object.prototype,"__proto__"),i=Object,s=i.getPrototypeOf;return ad=n&&typeof n.get=="function"?e([n.get]):typeof s=="function"?function(a){return s(a==null?a:i(a))}:!1,ad}var ld,mb;function l2(){if(mb)return ld;mb=1;var e=p0(),t=m0(),r=a2();return ld=e?function(i){return e(i)}:t?function(i){if(!i||typeof i!="object"&&typeof i!="function")throw new TypeError("getProto: not an object");return t(i)}:r?function(i){return r(i)}:null,ld}var cd,gb;function c2(){if(gb)return cd;gb=1;var e=Function.prototype.call,t=Object.prototype.hasOwnProperty,r=qc();return cd=r.call(e,t),cd}var ud,bb;function wp(){if(bb)return ud;bb=1;var e,t=h0(),r=BI(),n=UI(),i=zI(),s=qI(),o=HI(),a=Fs(),l=jI(),u=WI(),c=KI(),d=GI(),p=YI(),m=XI(),h=JI(),f=ZI(),b=Function,y=function(Xe){try{return b('"use strict"; return ('+Xe+").constructor;")()}catch{}},w=f0(),v=t2(),_=function(){throw new a},x=w?(function(){try{return arguments.callee,_}catch{try{return w(arguments,"callee").get}catch{return _}}})():_,k=n2()(),S=l2(),N=m0(),A=p0(),T=g0(),F=_p(),C={},g=typeof Uint8Array>"u"||!S?e:S(Uint8Array),L={__proto__:null,"%AggregateError%":typeof AggregateError>"u"?e:AggregateError,"%Array%":Array,"%ArrayBuffer%":typeof ArrayBuffer>"u"?e:ArrayBuffer,"%ArrayIteratorPrototype%":k&&S?S([][Symbol.iterator]()):e,"%AsyncFromSyncIteratorPrototype%":e,"%AsyncFunction%":C,"%AsyncGenerator%":C,"%AsyncGeneratorFunction%":C,"%AsyncIteratorPrototype%":C,"%Atomics%":typeof Atomics>"u"?e:Atomics,"%BigInt%":typeof BigInt>"u"?e:BigInt,"%BigInt64Array%":typeof BigInt64Array>"u"?e:BigInt64Array,"%BigUint64Array%":typeof BigUint64Array>"u"?e:BigUint64Array,"%Boolean%":Boolean,"%DataView%":typeof DataView>"u"?e:DataView,"%Date%":Date,"%decodeURI%":decodeURI,"%decodeURIComponent%":decodeURIComponent,"%encodeURI%":encodeURI,"%encodeURIComponent%":encodeURIComponent,"%Error%":r,"%eval%":eval,"%EvalError%":n,"%Float16Array%":typeof Float16Array>"u"?e:Float16Array,"%Float32Array%":typeof Float32Array>"u"?e:Float32Array,"%Float64Array%":typeof Float64Array>"u"?e:Float64Array,"%FinalizationRegistry%":typeof FinalizationRegistry>"u"?e:FinalizationRegistry,"%Function%":b,"%GeneratorFunction%":C,"%Int8Array%":typeof Int8Array>"u"?e:Int8Array,"%Int16Array%":typeof Int16Array>"u"?e:Int16Array,"%Int32Array%":typeof Int32Array>"u"?e:Int32Array,"%isFinite%":isFinite,"%isNaN%":isNaN,"%IteratorPrototype%":k&&S?S(S([][Symbol.iterator]())):e,"%JSON%":typeof JSON=="object"?JSON:e,"%Map%":typeof Map>"u"?e:Map,"%MapIteratorPrototype%":typeof Map>"u"||!k||!S?e:S(new Map()[Symbol.iterator]()),"%Math%":Math,"%Number%":Number,"%Object%":t,"%Object.getOwnPropertyDescriptor%":w,"%parseFloat%":parseFloat,"%parseInt%":parseInt,"%Promise%":typeof Promise>"u"?e:Promise,"%Proxy%":typeof Proxy>"u"?e:Proxy,"%RangeError%":i,"%ReferenceError%":s,"%Reflect%":typeof Reflect>"u"?e:Reflect,"%RegExp%":RegExp,"%Set%":typeof Set>"u"?e:Set,"%SetIteratorPrototype%":typeof Set>"u"||!k||!S?e:S(new Set()[Symbol.iterator]()),"%SharedArrayBuffer%":typeof SharedArrayBuffer>"u"?e:SharedArrayBuffer,"%String%":String,"%StringIteratorPrototype%":k&&S?S(""[Symbol.iterator]()):e,"%Symbol%":k?Symbol:e,"%SyntaxError%":o,"%ThrowTypeError%":x,"%TypedArray%":g,"%TypeError%":a,"%Uint8Array%":typeof Uint8Array>"u"?e:Uint8Array,"%Uint8ClampedArray%":typeof Uint8ClampedArray>"u"?e:Uint8ClampedArray,"%Uint16Array%":typeof Uint16Array>"u"?e:Uint16Array,"%Uint32Array%":typeof Uint32Array>"u"?e:Uint32Array,"%URIError%":l,"%WeakMap%":typeof WeakMap>"u"?e:WeakMap,"%WeakRef%":typeof WeakRef>"u"?e:WeakRef,"%WeakSet%":typeof WeakSet>"u"?e:WeakSet,"%Function.prototype.call%":F,"%Function.prototype.apply%":T,"%Object.defineProperty%":v,"%Object.getPrototypeOf%":N,"%Math.abs%":u,"%Math.floor%":c,"%Math.max%":d,"%Math.min%":p,"%Math.pow%":m,"%Math.round%":h,"%Math.sign%":f,"%Reflect.getPrototypeOf%":A};if(S)try{null.error}catch(Xe){var B=S(S(Xe));L["%Error.prototype%"]=B}var $=function Xe(he){var Re;if(he==="%AsyncFunction%")Re=y("async function () {}");else if(he==="%GeneratorFunction%")Re=y("function* () {}");else if(he==="%AsyncGeneratorFunction%")Re=y("async function* () {}");else if(he==="%AsyncGenerator%"){var Se=Xe("%AsyncGeneratorFunction%");Se&&(Re=Se.prototype)}else if(he==="%AsyncIteratorPrototype%"){var E=Xe("%AsyncGenerator%");E&&S&&(Re=S(E.prototype))}return L[he]=Re,Re},z={__proto__:null,"%ArrayBufferPrototype%":["ArrayBuffer","prototype"],"%ArrayPrototype%":["Array","prototype"],"%ArrayProto_entries%":["Array","prototype","entries"],"%ArrayProto_forEach%":["Array","prototype","forEach"],"%ArrayProto_keys%":["Array","prototype","keys"],"%ArrayProto_values%":["Array","prototype","values"],"%AsyncFunctionPrototype%":["AsyncFunction","prototype"],"%AsyncGenerator%":["AsyncGeneratorFunction","prototype"],"%AsyncGeneratorPrototype%":["AsyncGeneratorFunction","prototype","prototype"],"%BooleanPrototype%":["Boolean","prototype"],"%DataViewPrototype%":["DataView","prototype"],"%DatePrototype%":["Date","prototype"],"%ErrorPrototype%":["Error","prototype"],"%EvalErrorPrototype%":["EvalError","prototype"],"%Float32ArrayPrototype%":["Float32Array","prototype"],"%Float64ArrayPrototype%":["Float64Array","prototype"],"%FunctionPrototype%":["Function","prototype"],"%Generator%":["GeneratorFunction","prototype"],"%GeneratorPrototype%":["GeneratorFunction","prototype","prototype"],"%Int8ArrayPrototype%":["Int8Array","prototype"],"%Int16ArrayPrototype%":["Int16Array","prototype"],"%Int32ArrayPrototype%":["Int32Array","prototype"],"%JSONParse%":["JSON","parse"],"%JSONStringify%":["JSON","stringify"],"%MapPrototype%":["Map","prototype"],"%NumberPrototype%":["Number","prototype"],"%ObjectPrototype%":["Object","prototype"],"%ObjProto_toString%":["Object","prototype","toString"],"%ObjProto_valueOf%":["Object","prototype","valueOf"],"%PromisePrototype%":["Promise","prototype"],"%PromiseProto_then%":["Promise","prototype","then"],"%Promise_all%":["Promise","all"],"%Promise_reject%":["Promise","reject"],"%Promise_resolve%":["Promise","resolve"],"%RangeErrorPrototype%":["RangeError","prototype"],"%ReferenceErrorPrototype%":["ReferenceError","prototype"],"%RegExpPrototype%":["RegExp","prototype"],"%SetPrototype%":["Set","prototype"],"%SharedArrayBufferPrototype%":["SharedArrayBuffer","prototype"],"%StringPrototype%":["String","prototype"],"%SymbolPrototype%":["Symbol","prototype"],"%SyntaxErrorPrototype%":["SyntaxError","prototype"],"%TypedArrayPrototype%":["TypedArray","prototype"],"%TypeErrorPrototype%":["TypeError","prototype"],"%Uint8ArrayPrototype%":["Uint8Array","prototype"],"%Uint8ClampedArrayPrototype%":["Uint8ClampedArray","prototype"],"%Uint16ArrayPrototype%":["Uint16Array","prototype"],"%Uint32ArrayPrototype%":["Uint32Array","prototype"],"%URIErrorPrototype%":["URIError","prototype"],"%WeakMapPrototype%":["WeakMap","prototype"],"%WeakSetPrototype%":["WeakSet","prototype"]},P=qc(),Z=c2(),be=P.call(F,Array.prototype.concat),le=P.call(T,Array.prototype.splice),je=P.call(F,String.prototype.replace),vr=P.call(F,String.prototype.slice),$r=P.call(F,RegExp.prototype.exec),yr=/[^%.[\]]+|\[(?:(-?\d+(?:\.\d+)?)|(["'])((?:(?!\2)[^\\]|\\.)*?)\2)\]|(?=(?:\.|\[\])(?:\.|\[\]|%$))/g,ut=/\\(\\)?/g,Ht=function(he){var Re=vr(he,0,1),Se=vr(he,-1);if(Re==="%"&&Se!=="%")throw new o("invalid intrinsic syntax, expected closing `%`");if(Se==="%"&&Re!=="%")throw new o("invalid intrinsic syntax, expected opening `%`");var E=[];return je(he,yr,function(I,U,H,q){E[E.length]=H?je(q,ut,"$1"):U||I}),E},lr=function(he,Re){var Se=he,E;if(Z(z,Se)&&(E=z[Se],Se="%"+E[0]+"%"),Z(L,Se)){var I=L[Se];if(I===C&&(I=$(Se)),typeof I>"u"&&!Re)throw new a("intrinsic "+he+" exists, but is not available. Please file an issue!");return{alias:E,name:Se,value:I}}throw new o("intrinsic "+he+" does not exist!")};return ud=function(he,Re){if(typeof he!="string"||he.length===0)throw new a("intrinsic name must be a non-empty string");if(arguments.length>1&&typeof Re!="boolean")throw new a('"allowMissing" argument must be a boolean');if($r(/^%?[^%]*%?$/,he)===null)throw new o("`%` may not be present anywhere but at the beginning and end of the intrinsic name");var Se=Ht(he),E=Se.length>0?Se[0]:"",I=lr("%"+E+"%",Re),U=I.name,H=I.value,q=!1,j=I.alias;j&&(E=j[0],le(Se,be([0,1],j)));for(var J=1,X=!0;J<Se.length;J+=1){var G=Se[J],K=vr(G,0,1),ne=vr(G,-1);if((K==='"'||K==="'"||K==="`"||ne==='"'||ne==="'"||ne==="`")&&K!==ne)throw new o("property names with quotes must have matching quotes");if((G==="constructor"||!X)&&(q=!0),E+="."+G,U="%"+E+"%",Z(L,U))H=L[U];else if(H!=null){if(!(G in H)){if(!Re)throw new a("base intrinsic for "+he+" exists, but the property is not available.");return}if(w&&J+1>=Se.length){var Q=w(H,G);X=!!Q,X&&"get"in Q&&!("originalValue"in Q.get)?H=Q.get:H=H[G]}else X=Z(H,G),H=H[G];X&&!q&&(L[U]=H)}}return H},ud}var dd,vb;function v0(){if(vb)return dd;vb=1;var e=wp(),t=b0(),r=t([e("%String.prototype.indexOf%")]);return dd=function(i,s){var o=e(i,!!s);return typeof o=="function"&&r(i,".prototype.")>-1?t([o]):o},dd}var hd,yb;function y0(){if(yb)return hd;yb=1;var e=wp(),t=v0(),r=zc(),n=Fs(),i=e("%Map%",!0),s=t("Map.prototype.get",!0),o=t("Map.prototype.set",!0),a=t("Map.prototype.has",!0),l=t("Map.prototype.delete",!0),u=t("Map.prototype.size",!0);return hd=!!i&&function(){var d,p={assert:function(m){if(!p.has(m))throw new n("Side channel does not contain "+r(m))},delete:function(m){if(d){var h=l(d,m);return u(d)===0&&(d=void 0),h}return!1},get:function(m){if(d)return s(d,m)},has:function(m){return d?a(d,m):!1},set:function(m,h){d||(d=new i),o(d,m,h)}};return p},hd}var fd,_b;function u2(){if(_b)return fd;_b=1;var e=wp(),t=v0(),r=zc(),n=y0(),i=Fs(),s=e("%WeakMap%",!0),o=t("WeakMap.prototype.get",!0),a=t("WeakMap.prototype.set",!0),l=t("WeakMap.prototype.has",!0),u=t("WeakMap.prototype.delete",!0);return fd=s?function(){var d,p,m={assert:function(h){if(!m.has(h))throw new i("Side channel does not contain "+r(h))},delete:function(h){if(s&&h&&(typeof h=="object"||typeof h=="function")){if(d)return u(d,h)}else if(n&&p)return p.delete(h);return!1},get:function(h){return s&&h&&(typeof h=="object"||typeof h=="function")&&d?o(d,h):p&&p.get(h)},has:function(h){return s&&h&&(typeof h=="object"||typeof h=="function")&&d?l(d,h):!!p&&p.has(h)},set:function(h,f){s&&h&&(typeof h=="object"||typeof h=="function")?(d||(d=new s),a(d,h,f)):n&&(p||(p=n()),p.set(h,f))}};return m}:n,fd}var pd,wb;function _0(){if(wb)return pd;wb=1;var e=Fs(),t=zc(),r=VI(),n=y0(),i=u2(),s=i||n||r;return pd=function(){var a,l={assert:function(u){if(!l.has(u))throw new e("Side channel does not contain "+t(u))},delete:function(u){return!!a&&a.delete(u)},get:function(u){return a&&a.get(u)},has:function(u){return!!a&&a.has(u)},set:function(u,c){a||(a=s()),a.set(u,c)}};return l},pd}var md,Eb;function Ep(){if(Eb)return md;Eb=1;var e=String.prototype.replace,t=/%20/g,r={RFC1738:"RFC1738",RFC3986:"RFC3986"};return md={default:r.RFC3986,formatters:{RFC1738:function(n){return e.call(n,t,"+")},RFC3986:function(n){return String(n)}},RFC1738:r.RFC1738,RFC3986:r.RFC3986},md}var gd,xb;function w0(){if(xb)return gd;xb=1;var e=Ep(),t=_0(),r=Object.prototype.hasOwnProperty,n=Array.isArray,i=t(),s=function(S,N){return i.set(S,N),S},o=function(S){return i.has(S)},a=function(S){return i.get(S)},l=function(S,N){i.set(S,N)},u=(function(){for(var k=[],S=0;S<256;++S)k.push("%"+((S<16?"0":"")+S.toString(16)).toUpperCase());return k})(),c=function(S){for(;S.length>1;){var N=S.pop(),A=N.obj[N.prop];if(n(A)){for(var T=[],F=0;F<A.length;++F)typeof A[F]<"u"&&T.push(A[F]);N.obj[N.prop]=T}}},d=function(S,N){for(var A=N&&N.plainObjects?{__proto__:null}:{},T=0;T<S.length;++T)typeof S[T]<"u"&&(A[T]=S[T]);return A},p=function k(S,N,A){if(!N)return S;if(typeof N!="object"&&typeof N!="function"){if(n(S))S.push(N);else if(S&&typeof S=="object")if(o(S)){var T=a(S)+1;S[T]=N,l(S,T)}else(A&&(A.plainObjects||A.allowPrototypes)||!r.call(Object.prototype,N))&&(S[N]=!0);else return[S,N];return S}if(!S||typeof S!="object"){if(o(N)){for(var F=Object.keys(N),C=A&&A.plainObjects?{__proto__:null,0:S}:{0:S},g=0;g<F.length;g++){var L=parseInt(F[g],10);C[L+1]=N[F[g]]}return s(C,a(N)+1)}return[S].concat(N)}var B=S;return n(S)&&!n(N)&&(B=d(S,A)),n(S)&&n(N)?(N.forEach(function($,z){if(r.call(S,z)){var P=S[z];P&&typeof P=="object"&&$&&typeof $=="object"?S[z]=k(P,$,A):S.push($)}else S[z]=$}),S):Object.keys(N).reduce(function($,z){var P=N[z];return r.call($,z)?$[z]=k($[z],P,A):$[z]=P,$},B)},m=function(S,N){return Object.keys(N).reduce(function(A,T){return A[T]=N[T],A},S)},h=function(k,S,N){var A=k.replace(/\+/g," ");if(N==="iso-8859-1")return A.replace(/%[0-9a-f]{2}/gi,unescape);try{return decodeURIComponent(A)}catch{return A}},f=1024,b=function(S,N,A,T,F){if(S.length===0)return S;var C=S;if(typeof S=="symbol"?C=Symbol.prototype.toString.call(S):typeof S!="string"&&(C=String(S)),A==="iso-8859-1")return escape(C).replace(/%u[0-9a-f]{4}/gi,function(Z){return"%26%23"+parseInt(Z.slice(2),16)+"%3B"});for(var g="",L=0;L<C.length;L+=f){for(var B=C.length>=f?C.slice(L,L+f):C,$=[],z=0;z<B.length;++z){var P=B.charCodeAt(z);if(P===45||P===46||P===95||P===126||P>=48&&P<=57||P>=65&&P<=90||P>=97&&P<=122||F===e.RFC1738&&(P===40||P===41)){$[$.length]=B.charAt(z);continue}if(P<128){$[$.length]=u[P];continue}if(P<2048){$[$.length]=u[192|P>>6]+u[128|P&63];continue}if(P<55296||P>=57344){$[$.length]=u[224|P>>12]+u[128|P>>6&63]+u[128|P&63];continue}z+=1,P=65536+((P&1023)<<10|B.charCodeAt(z)&1023),$[$.length]=u[240|P>>18]+u[128|P>>12&63]+u[128|P>>6&63]+u[128|P&63]}g+=$.join("")}return g},y=function(S){for(var N=[{obj:{o:S},prop:"o"}],A=[],T=0;T<N.length;++T)for(var F=N[T],C=F.obj[F.prop],g=Object.keys(C),L=0;L<g.length;++L){var B=g[L],$=C[B];typeof $=="object"&&$!==null&&A.indexOf($)===-1&&(N.push({obj:C,prop:B}),A.push($))}return c(N),S},w=function(S){return Object.prototype.toString.call(S)==="[object RegExp]"},v=function(S){return!S||typeof S!="object"?!1:!!(S.constructor&&S.constructor.isBuffer&&S.constructor.isBuffer(S))},_=function(S,N,A,T){if(o(S)){var F=a(S)+1;return S[F]=N,l(S,F),S}var C=[].concat(S,N);return C.length>A?s(d(C,{plainObjects:T}),C.length-1):C},x=function(S,N){if(n(S)){for(var A=[],T=0;T<S.length;T+=1)A.push(N(S[T]));return A}return N(S)};return gd={arrayToObject:d,assign:m,combine:_,compact:y,decode:h,encode:b,isBuffer:v,isOverflow:o,isRegExp:w,maybeMap:x,merge:p},gd}var bd,Sb;function d2(){if(Sb)return bd;Sb=1;var e=_0(),t=w0(),r=Ep(),n=Object.prototype.hasOwnProperty,i={brackets:function(b){return b+"[]"},comma:"comma",indices:function(b,y){return b+"["+y+"]"},repeat:function(b){return b}},s=Array.isArray,o=Array.prototype.push,a=function(f,b){o.apply(f,s(b)?b:[b])},l=Date.prototype.toISOString,u=r.default,c={addQueryPrefix:!1,allowDots:!1,allowEmptyArrays:!1,arrayFormat:"indices",charset:"utf-8",charsetSentinel:!1,commaRoundTrip:!1,delimiter:"&",encode:!0,encodeDotInKeys:!1,encoder:t.encode,encodeValuesOnly:!1,filter:void 0,format:u,formatter:r.formatters[u],indices:!1,serializeDate:function(b){return l.call(b)},skipNulls:!1,strictNullHandling:!1},d=function(b){return typeof b=="string"||typeof b=="number"||typeof b=="boolean"||typeof b=="symbol"||typeof b=="bigint"},p={},m=function f(b,y,w,v,_,x,k,S,N,A,T,F,C,g,L,B,$,z){for(var P=b,Z=z,be=0,le=!1;(Z=Z.get(p))!==void 0&&!le;){var je=Z.get(b);if(be+=1,typeof je<"u"){if(je===be)throw new RangeError("Cyclic object value");le=!0}typeof Z.get(p)>"u"&&(be=0)}if(typeof A=="function"?P=A(y,P):P instanceof Date?P=C(P):w==="comma"&&s(P)&&(P=t.maybeMap(P,function(U){return U instanceof Date?C(U):U})),P===null){if(x)return N&&!B?N(y,c.encoder,$,"key",g):y;P=""}if(d(P)||t.isBuffer(P)){if(N){var vr=B?y:N(y,c.encoder,$,"key",g);return[L(vr)+"="+L(N(P,c.encoder,$,"value",g))]}return[L(y)+"="+L(String(P))]}var $r=[];if(typeof P>"u")return $r;var yr;if(w==="comma"&&s(P))B&&N&&(P=t.maybeMap(P,N)),yr=[{value:P.length>0?P.join(",")||null:void 0}];else if(s(A))yr=A;else{var ut=Object.keys(P);yr=T?ut.sort(T):ut}var Ht=S?String(y).replace(/\./g,"%2E"):String(y),lr=v&&s(P)&&P.length===1?Ht+"[]":Ht;if(_&&s(P)&&P.length===0)return lr+"[]";for(var Xe=0;Xe<yr.length;++Xe){var he=yr[Xe],Re=typeof he=="object"&&he&&typeof he.value<"u"?he.value:P[he];if(!(k&&Re===null)){var Se=F&&S?String(he).replace(/\./g,"%2E"):String(he),E=s(P)?typeof w=="function"?w(lr,Se):lr:lr+(F?"."+Se:"["+Se+"]");z.set(b,be);var I=e();I.set(p,z),a($r,f(Re,E,w,v,_,x,k,S,w==="comma"&&B&&s(P)?null:N,A,T,F,C,g,L,B,$,I))}}return $r},h=function(b){if(!b)return c;if(typeof b.allowEmptyArrays<"u"&&typeof b.allowEmptyArrays!="boolean")throw new TypeError("`allowEmptyArrays` option can only be `true` or `false`, when provided");if(typeof b.encodeDotInKeys<"u"&&typeof b.encodeDotInKeys!="boolean")throw new TypeError("`encodeDotInKeys` option can only be `true` or `false`, when provided");if(b.encoder!==null&&typeof b.encoder<"u"&&typeof b.encoder!="function")throw new TypeError("Encoder has to be a function.");var y=b.charset||c.charset;if(typeof b.charset<"u"&&b.charset!=="utf-8"&&b.charset!=="iso-8859-1")throw new TypeError("The charset option must be either utf-8, iso-8859-1, or undefined");var w=r.default;if(typeof b.format<"u"){if(!n.call(r.formatters,b.format))throw new TypeError("Unknown format option provided.");w=b.format}var v=r.formatters[w],_=c.filter;(typeof b.filter=="function"||s(b.filter))&&(_=b.filter);var x;if(b.arrayFormat in i?x=b.arrayFormat:"indices"in b?x=b.indices?"indices":"repeat":x=c.arrayFormat,"commaRoundTrip"in b&&typeof b.commaRoundTrip!="boolean")throw new TypeError("`commaRoundTrip` must be a boolean, or absent");var k=typeof b.allowDots>"u"?b.encodeDotInKeys===!0?!0:c.allowDots:!!b.allowDots;return{addQueryPrefix:typeof b.addQueryPrefix=="boolean"?b.addQueryPrefix:c.addQueryPrefix,allowDots:k,allowEmptyArrays:typeof b.allowEmptyArrays=="boolean"?!!b.allowEmptyArrays:c.allowEmptyArrays,arrayFormat:x,charset:y,charsetSentinel:typeof b.charsetSentinel=="boolean"?b.charsetSentinel:c.charsetSentinel,commaRoundTrip:!!b.commaRoundTrip,delimiter:typeof b.delimiter>"u"?c.delimiter:b.delimiter,encode:typeof b.encode=="boolean"?b.encode:c.encode,encodeDotInKeys:typeof b.encodeDotInKeys=="boolean"?b.encodeDotInKeys:c.encodeDotInKeys,encoder:typeof b.encoder=="function"?b.encoder:c.encoder,encodeValuesOnly:typeof b.encodeValuesOnly=="boolean"?b.encodeValuesOnly:c.encodeValuesOnly,filter:_,format:w,formatter:v,serializeDate:typeof b.serializeDate=="function"?b.serializeDate:c.serializeDate,skipNulls:typeof b.skipNulls=="boolean"?b.skipNulls:c.skipNulls,sort:typeof b.sort=="function"?b.sort:null,strictNullHandling:typeof b.strictNullHandling=="boolean"?b.strictNullHandling:c.strictNullHandling}};return bd=function(f,b){var y=f,w=h(b),v,_;typeof w.filter=="function"?(_=w.filter,y=_("",y)):s(w.filter)&&(_=w.filter,v=_);var x=[];if(typeof y!="object"||y===null)return"";var k=i[w.arrayFormat],S=k==="comma"&&w.commaRoundTrip;v||(v=Object.keys(y)),w.sort&&v.sort(w.sort);for(var N=e(),A=0;A<v.length;++A){var T=v[A],F=y[T];w.skipNulls&&F===null||a(x,m(F,T,k,S,w.allowEmptyArrays,w.strictNullHandling,w.skipNulls,w.encodeDotInKeys,w.encode?w.encoder:null,w.filter,w.sort,w.allowDots,w.serializeDate,w.format,w.formatter,w.encodeValuesOnly,w.charset,N))}var C=x.join(w.delimiter),g=w.addQueryPrefix===!0?"?":"";return w.charsetSentinel&&(w.charset==="iso-8859-1"?g+="utf8=%26%2310003%3B&":g+="utf8=%E2%9C%93&"),C.length>0?g+C:""},bd}var vd,Cb;function h2(){if(Cb)return vd;Cb=1;var e=w0(),t=Object.prototype.hasOwnProperty,r=Array.isArray,n={allowDots:!1,allowEmptyArrays:!1,allowPrototypes:!1,allowSparse:!1,arrayLimit:20,charset:"utf-8",charsetSentinel:!1,comma:!1,decodeDotInKeys:!1,decoder:e.decode,delimiter:"&",depth:5,duplicates:"combine",ignoreQueryPrefix:!1,interpretNumericEntities:!1,parameterLimit:1e3,parseArrays:!0,plainObjects:!1,strictDepth:!1,strictNullHandling:!1,throwOnLimitExceeded:!1},i=function(m){return m.replace(/&#(\d+);/g,function(h,f){return String.fromCharCode(parseInt(f,10))})},s=function(m,h,f){if(m&&typeof m=="string"&&h.comma&&m.indexOf(",")>-1)return m.split(",");if(h.throwOnLimitExceeded&&f>=h.arrayLimit)throw new RangeError("Array limit exceeded. Only "+h.arrayLimit+" element"+(h.arrayLimit===1?"":"s")+" allowed in an array.");return m},o="utf8=%26%2310003%3B",a="utf8=%E2%9C%93",l=function(h,f){var b={__proto__:null},y=f.ignoreQueryPrefix?h.replace(/^\?/,""):h;y=y.replace(/%5B/gi,"[").replace(/%5D/gi,"]");var w=f.parameterLimit===1/0?void 0:f.parameterLimit,v=y.split(f.delimiter,f.throwOnLimitExceeded?w+1:w);if(f.throwOnLimitExceeded&&v.length>w)throw new RangeError("Parameter limit exceeded. Only "+w+" parameter"+(w===1?"":"s")+" allowed.");var _=-1,x,k=f.charset;if(f.charsetSentinel)for(x=0;x<v.length;++x)v[x].indexOf("utf8=")===0&&(v[x]===a?k="utf-8":v[x]===o&&(k="iso-8859-1"),_=x,x=v.length);for(x=0;x<v.length;++x)if(x!==_){var S=v[x],N=S.indexOf("]="),A=N===-1?S.indexOf("="):N+1,T,F;if(A===-1?(T=f.decoder(S,n.decoder,k,"key"),F=f.strictNullHandling?null:""):(T=f.decoder(S.slice(0,A),n.decoder,k,"key"),T!==null&&(F=e.maybeMap(s(S.slice(A+1),f,r(b[T])?b[T].length:0),function(g){return f.decoder(g,n.decoder,k,"value")}))),F&&f.interpretNumericEntities&&k==="iso-8859-1"&&(F=i(String(F))),S.indexOf("[]=")>-1&&(F=r(F)?[F]:F),T!==null){var C=t.call(b,T);C&&f.duplicates==="combine"?b[T]=e.combine(b[T],F,f.arrayLimit,f.plainObjects):(!C||f.duplicates==="last")&&(b[T]=F)}}return b},u=function(m,h,f,b){var y=0;if(m.length>0&&m[m.length-1]==="[]"){var w=m.slice(0,-1).join("");y=Array.isArray(h)&&h[w]?h[w].length:0}for(var v=b?h:s(h,f,y),_=m.length-1;_>=0;--_){var x,k=m[_];if(k==="[]"&&f.parseArrays)e.isOverflow(v)?x=v:x=f.allowEmptyArrays&&(v===""||f.strictNullHandling&&v===null)?[]:e.combine([],v,f.arrayLimit,f.plainObjects);else{x=f.plainObjects?{__proto__:null}:{};var S=k.charAt(0)==="["&&k.charAt(k.length-1)==="]"?k.slice(1,-1):k,N=f.decodeDotInKeys?S.replace(/%2E/g,"."):S,A=parseInt(N,10);!f.parseArrays&&N===""?x={0:v}:!isNaN(A)&&k!==N&&String(A)===N&&A>=0&&f.parseArrays&&A<=f.arrayLimit?(x=[],x[A]=v):N!=="__proto__"&&(x[N]=v)}v=x}return v},c=function(h,f){var b=f.allowDots?h.replace(/\.([^.[]+)/g,"[$1]"):h;if(f.depth<=0)return!f.plainObjects&&t.call(Object.prototype,b)&&!f.allowPrototypes?void 0:[b];var y=/(\[[^[\]]*])/,w=/(\[[^[\]]*])/g,v=y.exec(b),_=v?b.slice(0,v.index):b,x=[];if(_){if(!f.plainObjects&&t.call(Object.prototype,_)&&!f.allowPrototypes)return;x.push(_)}for(var k=0;(v=w.exec(b))!==null&&k<f.depth;){k+=1;var S=v[1].slice(1,-1);if(!f.plainObjects&&t.call(Object.prototype,S)&&!f.allowPrototypes)return;x.push(v[1])}if(v){if(f.strictDepth===!0)throw new RangeError("Input depth exceeded depth option of "+f.depth+" and strictDepth is true");x.push("["+b.slice(v.index)+"]")}return x},d=function(h,f,b,y){if(h){var w=c(h,b);if(w)return u(w,f,b,y)}},p=function(h){if(!h)return n;if(typeof h.allowEmptyArrays<"u"&&typeof h.allowEmptyArrays!="boolean")throw new TypeError("`allowEmptyArrays` option can only be `true` or `false`, when provided");if(typeof h.decodeDotInKeys<"u"&&typeof h.decodeDotInKeys!="boolean")throw new TypeError("`decodeDotInKeys` option can only be `true` or `false`, when provided");if(h.decoder!==null&&typeof h.decoder<"u"&&typeof h.decoder!="function")throw new TypeError("Decoder has to be a function.");if(typeof h.charset<"u"&&h.charset!=="utf-8"&&h.charset!=="iso-8859-1")throw new TypeError("The charset option must be either utf-8, iso-8859-1, or undefined");if(typeof h.throwOnLimitExceeded<"u"&&typeof h.throwOnLimitExceeded!="boolean")throw new TypeError("`throwOnLimitExceeded` option must be a boolean");var f=typeof h.charset>"u"?n.charset:h.charset,b=typeof h.duplicates>"u"?n.duplicates:h.duplicates;if(b!=="combine"&&b!=="first"&&b!=="last")throw new TypeError("The duplicates option must be either combine, first, or last");var y=typeof h.allowDots>"u"?h.decodeDotInKeys===!0?!0:n.allowDots:!!h.allowDots;return{allowDots:y,allowEmptyArrays:typeof h.allowEmptyArrays=="boolean"?!!h.allowEmptyArrays:n.allowEmptyArrays,allowPrototypes:typeof h.allowPrototypes=="boolean"?h.allowPrototypes:n.allowPrototypes,allowSparse:typeof h.allowSparse=="boolean"?h.allowSparse:n.allowSparse,arrayLimit:typeof h.arrayLimit=="number"?h.arrayLimit:n.arrayLimit,charset:f,charsetSentinel:typeof h.charsetSentinel=="boolean"?h.charsetSentinel:n.charsetSentinel,comma:typeof h.comma=="boolean"?h.comma:n.comma,decodeDotInKeys:typeof h.decodeDotInKeys=="boolean"?h.decodeDotInKeys:n.decodeDotInKeys,decoder:typeof h.decoder=="function"?h.decoder:n.decoder,delimiter:typeof h.delimiter=="string"||e.isRegExp(h.delimiter)?h.delimiter:n.delimiter,depth:typeof h.depth=="number"||h.depth===!1?+h.depth:n.depth,duplicates:b,ignoreQueryPrefix:h.ignoreQueryPrefix===!0,interpretNumericEntities:typeof h.interpretNumericEntities=="boolean"?h.interpretNumericEntities:n.interpretNumericEntities,parameterLimit:typeof h.parameterLimit=="number"?h.parameterLimit:n.parameterLimit,parseArrays:h.parseArrays!==!1,plainObjects:typeof h.plainObjects=="boolean"?h.plainObjects:n.plainObjects,strictDepth:typeof h.strictDepth=="boolean"?!!h.strictDepth:n.strictDepth,strictNullHandling:typeof h.strictNullHandling=="boolean"?h.strictNullHandling:n.strictNullHandling,throwOnLimitExceeded:typeof h.throwOnLimitExceeded=="boolean"?h.throwOnLimitExceeded:!1}};return vd=function(m,h){var f=p(h);if(m===""||m===null||typeof m>"u")return f.plainObjects?{__proto__:null}:{};for(var b=typeof m=="string"?l(m,f):m,y=f.plainObjects?{__proto__:null}:{},w=Object.keys(b),v=0;v<w.length;++v){var _=w[v],x=d(_,b[_],f,typeof m=="string");y=e.merge(y,x,f)}return f.allowSparse===!0?y:e.compact(y)},vd}var yd,Ab;function f2(){if(Ab)return yd;Ab=1;var e=d2(),t=h2(),r=Ep();return yd={formats:r,parse:t,stringify:e},yd}var Tb=f2(),p2=class{constructor(e){this.config={},this.defaults=e}extend(e){return e&&(this.defaults={...this.defaults,...e}),this}replace(e){this.config=e}get(e){return d0(this.config,e)?Ar(this.config,e):Ar(this.defaults,e)}set(e,t){typeof e=="string"?qr(this.config,e,t):Object.entries(e).forEach(([r,n])=>{qr(this.config,r,n)})}},Ii=new p2({form:{recentlySuccessfulDuration:2e3,forceIndicesArrayFormatInFormData:!0},future:{preserveEqualProps:!1,useDataInertiaHeadAttribute:!1,useDialogForErrorModal:!1,useScriptElementForInitialPage:!1},prefetch:{cacheFor:3e4,hoverDelay:75}});function $h(e,t){let r;return function(...n){clearTimeout(r),r=setTimeout(()=>e.apply(this,n),t)}}function or(e,t){return document.dispatchEvent(new CustomEvent(`inertia:${e}`,t))}var kb=e=>or("before",{cancelable:!0,detail:{visit:e}}),m2=e=>or("error",{detail:{errors:e}}),g2=e=>or("exception",{cancelable:!0,detail:{exception:e}}),b2=e=>or("finish",{detail:{visit:e}}),v2=e=>or("invalid",{cancelable:!0,detail:{response:e}}),y2=e=>or("beforeUpdate",{detail:{page:e}}),xo=e=>or("navigate",{detail:{page:e}}),_2=e=>or("progress",{detail:{progress:e}}),w2=e=>or("start",{detail:{visit:e}}),E2=e=>or("success",{detail:{page:e}}),x2=(e,t)=>or("prefetched",{detail:{fetchedAt:Date.now(),response:e.data,visit:t}}),S2=e=>or("prefetching",{detail:{visit:e}}),Yl=e=>or("flash",{detail:{flash:e}}),Lt=class{static set(e,t){typeof window<"u"&&window.sessionStorage.setItem(e,JSON.stringify(t))}static get(e){if(typeof window<"u")return JSON.parse(window.sessionStorage.getItem(e)||"null")}static merge(e,t){const r=this.get(e);r===null?this.set(e,t):this.set(e,{...r,...t})}static remove(e){typeof window<"u"&&window.sessionStorage.removeItem(e)}static removeNested(e,t){const r=this.get(e);r!==null&&(delete r[t],this.set(e,r))}static exists(e){try{return this.get(e)!==null}catch{return!1}}static clear(){typeof window<"u"&&window.sessionStorage.clear()}};Lt.locationVisitKey="inertiaLocationVisit";var C2=async e=>{if(typeof window>"u")throw new Error("Unable to encrypt history");const t=E0(),r=await x0(),n=await P2(r);if(!n)throw new Error("Unable to encrypt history");return await T2(t,n,e)},_s={key:"historyKey",iv:"historyIv"},A2=async e=>{const t=E0(),r=await x0();if(!r)throw new Error("Unable to decrypt history");return await k2(t,r,e)},T2=async(e,t,r)=>{if(typeof window>"u")throw new Error("Unable to encrypt history");if(typeof window.crypto.subtle>"u")return console.warn("Encryption is not supported in this environment. SSL is required."),Promise.resolve(r);const n=new TextEncoder,i=JSON.stringify(r),s=new Uint8Array(i.length*3),o=n.encodeInto(i,s);return window.crypto.subtle.encrypt({name:"AES-GCM",iv:e},t,s.subarray(0,o.written))},k2=async(e,t,r)=>{if(typeof window.crypto.subtle>"u")return console.warn("Decryption is not supported in this environment. SSL is required."),Promise.resolve(r);const n=await window.crypto.subtle.decrypt({name:"AES-GCM",iv:e},t,r);return JSON.parse(new TextDecoder().decode(n))},E0=()=>{const e=Lt.get(_s.iv);if(e)return new Uint8Array(e);const t=window.crypto.getRandomValues(new Uint8Array(12));return Lt.set(_s.iv,Array.from(t)),t},O2=async()=>typeof window.crypto.subtle>"u"?(console.warn("Encryption is not supported in this environment. SSL is required."),Promise.resolve(null)):window.crypto.subtle.generateKey({name:"AES-GCM",length:256},!0,["encrypt","decrypt"]),N2=async e=>{if(typeof window.crypto.subtle>"u")return console.warn("Encryption is not supported in this environment. SSL is required."),Promise.resolve();const t=await window.crypto.subtle.exportKey("raw",e);Lt.set(_s.key,Array.from(new Uint8Array(t)))},P2=async e=>{if(e)return e;const t=await O2();return t?(await N2(t),t):null},x0=async()=>{const e=Lt.get(_s.key);return e?await window.crypto.subtle.importKey("raw",new Uint8Array(e),{name:"AES-GCM",length:256},!0,["encrypt","decrypt"]):null},S0=(e,t,r)=>{if(e===t)return!0;for(const n in e)if(!r.includes(n)&&e[n]!==t[n]&&!F2(e[n],t[n]))return!1;for(const n in t)if(!r.includes(n)&&!(n in e))return!1;return!0},F2=(e,t)=>{switch(typeof e){case"object":return S0(e,t,[]);case"function":return e.toString()===t.toString();default:return e===t}},I2={ms:1,s:1e3,m:1e3*60,h:1e3*60*60,d:1e3*60*60*24},Ob=e=>{if(typeof e=="number")return e;for(const[t,r]of Object.entries(I2))if(e.endsWith(t))return parseFloat(e)*r;return parseInt(e)},L2=class{constructor(){this.cached=[],this.inFlightRequests=[],this.removalTimers=[],this.currentUseId=null}add(e,t,{cacheFor:r,cacheTags:n}){if(this.findInFlight(e))return Promise.resolve();const s=this.findCached(e);if(!e.fresh&&s&&s.staleTimestamp>Date.now())return Promise.resolve();const[o,a]=this.extractStaleValues(r),l=new Promise((u,c)=>{t({...e,onCancel:()=>{this.remove(e),e.onCancel(),c()},onError:d=>{this.remove(e),e.onError(d),c()},onPrefetching(d){e.onPrefetching(d)},onPrefetched(d,p){e.onPrefetched(d,p)},onPrefetchResponse(d){u(d)},onPrefetchError(d){Br.removeFromInFlight(e),c(d)}})}).then(u=>{this.remove(e);const c=u.getPageResponse();ee.mergeOncePropsIntoResponse(c),this.cached.push({params:{...e},staleTimestamp:Date.now()+o,expiresAt:Date.now()+a,response:l,singleUse:a===0,timestamp:Date.now(),inFlight:!1,tags:Array.isArray(n)?n:[n]});const d=this.getShortestOncePropTtl(c);return this.scheduleForRemoval(e,d?Math.min(a,d):a),this.removeFromInFlight(e),u.handlePrefetch(),u});return this.inFlightRequests.push({params:{...e},response:l,staleTimestamp:null,inFlight:!0}),l}removeAll(){this.cached=[],this.removalTimers.forEach(e=>{clearTimeout(e.timer)}),this.removalTimers=[]}removeByTags(e){this.cached=this.cached.filter(t=>!t.tags.some(r=>e.includes(r)))}remove(e){this.cached=this.cached.filter(t=>!this.paramsAreEqual(t.params,e)),this.clearTimer(e)}removeFromInFlight(e){this.inFlightRequests=this.inFlightRequests.filter(t=>!this.paramsAreEqual(t.params,e))}extractStaleValues(e){const[t,r]=this.cacheForToStaleAndExpires(e);return[Ob(t),Ob(r)]}cacheForToStaleAndExpires(e){if(!Array.isArray(e))return[e,e];switch(e.length){case 0:return[0,0];case 1:return[e[0],e[0]];default:return[e[0],e[1]]}}clearTimer(e){const t=this.removalTimers.find(r=>this.paramsAreEqual(r.params,e));t&&(clearTimeout(t.timer),this.removalTimers=this.removalTimers.filter(r=>r!==t))}scheduleForRemoval(e,t){if(!(typeof window>"u")&&(this.clearTimer(e),t>0)){const r=window.setTimeout(()=>this.remove(e),t);this.removalTimers.push({params:e,timer:r})}}get(e){return this.findCached(e)||this.findInFlight(e)}use(e,t){const r=`${t.url.pathname}-${Date.now()}-${Math.random().toString(36).substring(7)}`;return this.currentUseId=r,e.response.then(n=>{if(this.currentUseId===r)return n.mergeParams({...t,onPrefetched:()=>{}}),this.removeSingleUseItems(t),n.handle()})}removeSingleUseItems(e){this.cached=this.cached.filter(t=>this.paramsAreEqual(t.params,e)?!t.singleUse:!0)}findCached(e){return this.cached.find(t=>this.paramsAreEqual(t.params,e))||null}findInFlight(e){return this.inFlightRequests.find(t=>this.paramsAreEqual(t.params,e))||null}withoutPurposePrefetchHeader(e){const t=ht(e);return t.headers.Purpose==="prefetch"&&delete t.headers.Purpose,t}paramsAreEqual(e,t){return S0(this.withoutPurposePrefetchHeader(e),this.withoutPurposePrefetchHeader(t),["showProgress","replace","prefetch","preserveScroll","preserveState","onBefore","onBeforeUpdate","onStart","onProgress","onFinish","onCancel","onSuccess","onError","onFlash","onPrefetched","onCancelToken","onPrefetching","async","viewTransition"])}updateCachedOncePropsFromCurrentPage(){this.cached.forEach(e=>{e.response.then(t=>{const r=t.getPageResponse();ee.mergeOncePropsIntoResponse(r,{force:!0});for(const[o,a]of Object.entries(r.deferredProps??{})){const l=a.filter(u=>r.props[u]===void 0);l.length>0?r.deferredProps[o]=l:delete r.deferredProps[o]}const n=this.getShortestOncePropTtl(r);if(n===null)return;const i=e.expiresAt-Date.now(),s=Math.min(i,n);s>0?this.scheduleForRemoval(e.params,s):this.remove(e.params)})})}getShortestOncePropTtl(e){const t=Object.values(e.onceProps??{}).map(r=>r.expiresAt).filter(r=>!!r);return t.length===0?null:Math.min(...t)-Date.now()}},Br=new L2,C0=(e,t=1)=>{window.requestAnimationFrame(()=>{t>1?C0(e,t-1):e()})},R2=(e,t=!1)=>{if(typeof window>"u")return null;if(!t){const n=document.getElementById(e);if(n?.dataset.page)return JSON.parse(n.dataset.page)}const r=document.querySelector(`script[data-page="${e}"][type="application/json"]`);return r?.textContent?JSON.parse(r.textContent):null},to=typeof window>"u",$2=!to&&/Firefox/i.test(window.navigator.userAgent),Dt=class{static save(){_e.saveScrollPositions(this.getScrollRegions())}static getScrollRegions(){return Array.from(this.regions()).map(e=>({top:e.scrollTop,left:e.scrollLeft}))}static regions(){return document.querySelectorAll("[scroll-region]")}static scrollToTop(){if($2&&getComputedStyle(document.documentElement).scrollBehavior==="smooth")return C0(()=>window.scrollTo(0,0),2);window.scrollTo(0,0)}static reset(){(to?null:window.location.hash)||this.scrollToTop(),this.regions().forEach(t=>{typeof t.scrollTo=="function"?t.scrollTo(0,0):(t.scrollTop=0,t.scrollLeft=0)}),this.save(),this.scrollToAnchor()}static scrollToAnchor(){const e=to?null:window.location.hash;e&&setTimeout(()=>{const t=document.getElementById(e.slice(1));t?t.scrollIntoView():this.scrollToTop()})}static restore(e){to||window.requestAnimationFrame(()=>{this.restoreDocument(),this.restoreScrollRegions(e)})}static restoreScrollRegions(e){to||this.regions().forEach((t,r)=>{const n=e[r];n&&(typeof t.scrollTo=="function"?t.scrollTo(n.left,n.top):(t.scrollTop=n.top,t.scrollLeft=n.left))})}static restoreDocument(){const e=_e.getDocumentScrollPosition();window.scrollTo(e.left,e.top)}static onScroll(e){const t=e.target;typeof t.hasAttribute=="function"&&t.hasAttribute("scroll-region")&&this.save()}static onWindowScroll(){_e.saveDocumentScrollPosition({top:window.scrollY,left:window.scrollX})}},M2=e=>typeof File<"u"&&e instanceof File||e instanceof Blob||typeof FileList<"u"&&e instanceof FileList&&e.length>0;function Mh(e){return M2(e)||e instanceof FormData&&Array.from(e.values()).some(t=>Mh(t))||typeof e=="object"&&e!==null&&Object.values(e).some(t=>Mh(t))}var Dh=e=>e instanceof FormData;function A0(e,t=new FormData,r=null,n="brackets"){e=e||{};for(const i in e)Object.prototype.hasOwnProperty.call(e,i)&&k0(t,T0(r,i,"indices"),e[i],n);return t}function T0(e,t,r){return e?r==="brackets"?`${e}[]`:`${e}[${t}]`:t}function k0(e,t,r,n){if(Array.isArray(r))return Array.from(r.keys()).forEach(i=>k0(e,T0(t,i.toString(),n),r[i],n));if(r instanceof Date)return e.append(t,r.toISOString());if(r instanceof File)return e.append(t,r,r.name);if(r instanceof Blob)return e.append(t,r);if(typeof r=="boolean")return e.append(t,r?"1":"0");if(typeof r=="string")return e.append(t,r);if(typeof r=="number")return e.append(t,`${r}`);if(r==null)return e.append(t,"");A0(r,e,t,n)}function fn(e){return new URL(e.toString(),typeof window>"u"?void 0:window.location.toString())}var D2=(e,t,r,n,i)=>{let s=typeof e=="string"?fn(e):e;if((Mh(t)||n)&&!Dh(t)&&(Ii.get("form.forceIndicesArrayFormatInFormData")&&(i="indices"),t=A0(t,new FormData,null,i)),Dh(t))return[s,t];const[o,a]=O0(r,s,t,i);return[fn(o),a]};function O0(e,t,r,n="brackets"){const i=e==="get"&&!Dh(r)&&Object.keys(r).length>0,s=B2(t.toString()),o=s||t.toString().startsWith("/")||t.toString()==="",a=!o&&!t.toString().startsWith("#")&&!t.toString().startsWith("?"),l=/^[.]{1,2}([/]|$)/.test(t.toString()),u=t.toString().includes("?")||i,c=t.toString().includes("#"),d=new URL(t.toString(),typeof window>"u"?"http://localhost":window.location.toString());if(i){const p=/\[\d+\]/.test(decodeURIComponent(d.search)),m={ignoreQueryPrefix:!0,allowSparse:!0};d.search=Tb.stringify({...Tb.parse(d.search,m),...r},{encodeValuesOnly:!0,arrayFormat:p?"indices":n})}return[[s?`${d.protocol}//${d.host}`:"",o?d.pathname:"",a?d.pathname.substring(l?0:1):"",u?d.search:"",c?d.hash:""].join(""),i?{}:r]}function Xl(e){return e=new URL(e.href),e.hash="",e}var Nb=(e,t)=>{e.hash&&!t.hash&&Xl(e).href===t.href&&(t.hash=e.hash)},Jl=(e,t)=>Xl(e).href===Xl(t).href,V2=(e,t)=>e.origin===t.origin&&e.pathname===t.pathname;function ws(e){return e!==null&&typeof e=="object"&&e!==void 0&&"url"in e&&"method"in e}function B2(e){return/^([a-z][a-z0-9+.-]*:)?\/\/[^/]/i.test(e)}var U2=class{constructor(){this.componentId={},this.listeners=[],this.isFirstPageLoad=!0,this.cleared=!1,this.pendingDeferredProps=null,this.historyQuotaExceeded=!1}init({initialPage:e,swapComponent:t,resolveComponent:r,onFlash:n}){return this.page={...e,flash:e.flash??{}},this.swapComponent=t,this.resolveComponent=r,this.onFlashCallback=n,zr.on("historyQuotaExceeded",()=>{this.historyQuotaExceeded=!0}),this}set(e,{replace:t=!1,preserveScroll:r=!1,preserveState:n=!1,viewTransition:i=!1}={}){Object.keys(e.deferredProps||{}).length&&(this.pendingDeferredProps={deferredProps:e.deferredProps,component:e.component,url:e.url},e.initialDeferredProps===void 0&&(e.initialDeferredProps=e.deferredProps)),this.componentId={};const s=this.componentId;return e.clearHistory&&_e.clear(),this.resolve(e.component).then(o=>{if(s!==this.componentId)return;e.rememberedState??(e.rememberedState={});const a=typeof window>"u",l=a?new URL(e.url):window.location,u=!a&&r?Dt.getScrollRegions():[];t=t||Jl(fn(e.url),l);const c={...e,flash:{}};return new Promise(d=>t?_e.replaceState(c,d):_e.pushState(c,d)).then(()=>{const d=!this.isTheSame(e);if(!d&&Object.keys(e.props.errors||{}).length>0&&(i=!1),this.page=e,this.cleared=!1,this.hasOnceProps()&&Br.updateCachedOncePropsFromCurrentPage(),d&&this.fireEventsFor("newComponent"),this.isFirstPageLoad&&this.fireEventsFor("firstLoad"),this.isFirstPageLoad=!1,this.historyQuotaExceeded){this.historyQuotaExceeded=!1;return}return this.swap({component:o,page:e,preserveState:n,viewTransition:i}).then(()=>{r?window.requestAnimationFrame(()=>Dt.restoreScrollRegions(u)):Dt.reset(),this.pendingDeferredProps&&this.pendingDeferredProps.component===e.component&&this.pendingDeferredProps.url===e.url&&zr.fireInternalEvent("loadDeferredProps",this.pendingDeferredProps.deferredProps),this.pendingDeferredProps=null,t||xo(e)})})})}setQuietly(e,{preserveState:t=!1}={}){return this.resolve(e.component).then(r=>(this.page=e,this.cleared=!1,_e.setCurrent(e),this.swap({component:r,page:e,preserveState:t,viewTransition:!1})))}clear(){this.cleared=!0}isCleared(){return this.cleared}get(){return this.page}getWithoutFlashData(){return{...this.page,flash:{}}}hasOnceProps(){return Object.keys(this.page.onceProps??{}).length>0}merge(e){this.page={...this.page,...e}}setFlash(e){this.page={...this.page,flash:e},this.onFlashCallback?.(e)}setUrlHash(e){this.page.url.includes(e)||(this.page.url+=e)}remember(e){this.page.rememberedState=e}swap({component:e,page:t,preserveState:r,viewTransition:n}){const i=()=>this.swapComponent({component:e,page:t,preserveState:r});if(!n||!document?.startViewTransition)return i();const s=typeof n=="boolean"?()=>null:n;return new Promise(o=>{const a=document.startViewTransition(()=>i().then(o));s(a)})}resolve(e){return Promise.resolve(this.resolveComponent(e))}isTheSame(e){return this.page.component===e.component}on(e,t){return this.listeners.push({event:e,callback:t}),()=>{this.listeners=this.listeners.filter(r=>r.event!==e&&r.callback!==t)}}fireEventsFor(e){this.listeners.filter(t=>t.event===e).forEach(t=>t.callback())}mergeOncePropsIntoResponse(e,{force:t=!1}={}){Object.entries(e.onceProps??{}).forEach(([r,n])=>{const i=this.page.onceProps?.[r];i!==void 0&&(t||e.props[n.prop]===void 0)&&(e.props[n.prop]=this.page.props[i.prop],e.onceProps[r].expiresAt=i.expiresAt)})}},ee=new U2,xp=class{constructor(){this.items=[],this.processingPromise=null}add(e){return this.items.push(e),this.process()}process(){return this.processingPromise??(this.processingPromise=this.processNext().finally(()=>{this.processingPromise=null})),this.processingPromise}processNext(){const e=this.items.shift();return e?Promise.resolve(e()).then(()=>this.processNext()):Promise.resolve()}},Zi=typeof window>"u",Hs=new xp,Pb=!Zi&&/CriOS/.test(window.navigator.userAgent),z2=class{constructor(){this.rememberedState="rememberedState",this.scrollRegions="scrollRegions",this.preserveUrl=!1,this.current={},this.initialState=null}remember(e,t){this.replaceState({...ee.getWithoutFlashData(),rememberedState:{...ee.get()?.rememberedState??{},[t]:e}})}restore(e){if(!Zi)return this.current[this.rememberedState]?.[e]!==void 0?this.current[this.rememberedState]?.[e]:this.initialState?.[this.rememberedState]?.[e]}pushState(e,t=null){if(!Zi){if(this.preserveUrl){t&&t();return}this.current=e,Hs.add(()=>this.getPageData(e).then(r=>{const n=()=>this.doPushState({page:r},e.url).then(()=>t?.());return Pb?new Promise(i=>{setTimeout(()=>n().then(i))}):n()}))}}clonePageProps(e){try{return structuredClone(e.props),e}catch{return{...e,props:ht(e.props)}}}getPageData(e){const t=this.clonePageProps(e);return new Promise(r=>e.encryptHistory?C2(t).then(r):r(t))}processQueue(){return Hs.process()}decrypt(e=null){if(Zi)return Promise.resolve(e??ee.get());const t=e??window.history.state?.page;return this.decryptPageData(t).then(r=>{if(!r)throw new Error("Unable to decrypt history");return this.initialState===null?this.initialState=r??void 0:this.current=r??{},r})}decryptPageData(e){return e instanceof ArrayBuffer?A2(e):Promise.resolve(e)}saveScrollPositions(e){Hs.add(()=>Promise.resolve().then(()=>{if(window.history.state?.page&&!ei(this.getScrollRegions(),e))return this.doReplaceState({page:window.history.state.page,scrollRegions:e})}))}saveDocumentScrollPosition(e){Hs.add(()=>Promise.resolve().then(()=>{if(window.history.state?.page&&!ei(this.getDocumentScrollPosition(),e))return this.doReplaceState({page:window.history.state.page,documentScrollPosition:e})}))}getScrollRegions(){return window.history.state?.scrollRegions||[]}getDocumentScrollPosition(){return window.history.state?.documentScrollPosition||{top:0,left:0}}replaceState(e,t=null){if(ei(this.current,e)){t&&t();return}if(ee.merge(e),!Zi){if(this.preserveUrl){t&&t();return}this.current=e,Hs.add(()=>this.getPageData(e).then(r=>{const n=()=>this.doReplaceState({page:r},e.url).then(()=>t?.());return Pb?new Promise(i=>{setTimeout(()=>n().then(i))}):n()}))}}isHistoryThrottleError(e){return e instanceof Error&&e.name==="SecurityError"&&(e.message.includes("history.pushState")||e.message.includes("history.replaceState"))}isQuotaExceededError(e){return e instanceof Error&&e.name==="QuotaExceededError"}withThrottleProtection(e){return Promise.resolve().then(()=>{try{return e()}catch(t){if(!this.isHistoryThrottleError(t))throw t;console.error(t.message)}})}doReplaceState(e,t){return this.withThrottleProtection(()=>{window.history.replaceState({...e,scrollRegions:e.scrollRegions??window.history.state?.scrollRegions,documentScrollPosition:e.documentScrollPosition??window.history.state?.documentScrollPosition},"",t)})}doPushState(e,t){return this.withThrottleProtection(()=>{try{window.history.pushState(e,"",t)}catch(r){if(!this.isQuotaExceededError(r))throw r;zr.fireInternalEvent("historyQuotaExceeded",t)}})}getState(e,t){return this.current?.[e]??t}deleteState(e){this.current[e]!==void 0&&(delete this.current[e],this.replaceState(this.current))}clearInitialState(e){this.initialState&&this.initialState[e]!==void 0&&delete this.initialState[e]}browserHasHistoryEntry(){return!Zi&&!!window.history.state?.page}clear(){Lt.remove(_s.key),Lt.remove(_s.iv)}setCurrent(e){this.current=e}isValidState(e){return!!e.page}getAllState(){return this.current}};typeof window<"u"&&window.history.scrollRestoration&&(window.history.scrollRestoration="manual");var _e=new z2,q2=class{constructor(){this.internalListeners=[]}init(){typeof window<"u"&&(window.addEventListener("popstate",this.handlePopstateEvent.bind(this)),window.addEventListener("scroll",$h(Dt.onWindowScroll.bind(Dt),100),!0)),typeof document<"u"&&document.addEventListener("scroll",$h(Dt.onScroll.bind(Dt),100),!0)}onGlobalEvent(e,t){const r=(n=>{const i=t(n);n.cancelable&&!n.defaultPrevented&&i===!1&&n.preventDefault()});return this.registerListener(`inertia:${e}`,r)}on(e,t){return this.internalListeners.push({event:e,listener:t}),()=>{this.internalListeners=this.internalListeners.filter(r=>r.listener!==t)}}onMissingHistoryItem(){ee.clear(),this.fireInternalEvent("missingHistoryItem")}fireInternalEvent(e,...t){this.internalListeners.filter(r=>r.event===e).forEach(r=>r.listener(...t))}registerListener(e,t){return document.addEventListener(e,t),()=>document.removeEventListener(e,t)}handlePopstateEvent(e){const t=e.state||null;if(t===null){const r=fn(ee.get().url);r.hash=window.location.hash,_e.replaceState({...ee.getWithoutFlashData(),url:r.href}),Dt.reset();return}if(!_e.isValidState(t))return this.onMissingHistoryItem();_e.decrypt(t.page).then(r=>{if(ee.get().version!==r.version){this.onMissingHistoryItem();return}at.cancelAll({prefetch:!1}),ee.setQuietly(r,{preserveState:!1}).then(()=>{Dt.restore(_e.getScrollRegions()),xo(ee.get());const n={},i=ee.get().props;for(const[s,o]of Object.entries(r.initialDeferredProps??r.deferredProps??{})){const a=o.filter(l=>i[l]===void 0);a.length>0&&(n[s]=a)}Object.keys(n).length>0&&this.fireInternalEvent("loadDeferredProps",n)})}).catch(()=>{this.onMissingHistoryItem()})}},zr=new q2,H2=class{constructor(){this.type=this.resolveType()}resolveType(){return typeof window>"u"?"navigate":window.performance&&window.performance.getEntriesByType&&window.performance.getEntriesByType("navigation").length>0?window.performance.getEntriesByType("navigation")[0].type:"navigate"}get(){return this.type}isBackForward(){return this.type==="back_forward"}isReload(){return this.type==="reload"}},_d=new H2,j2=class{static handle(){this.clearRememberedStateOnReload(),[this.handleBackForward,this.handleLocation,this.handleDefault].find(t=>t.bind(this)())}static clearRememberedStateOnReload(){_d.isReload()&&(_e.deleteState(_e.rememberedState),_e.clearInitialState(_e.rememberedState))}static handleBackForward(){if(!_d.isBackForward()||!_e.browserHasHistoryEntry())return!1;const e=_e.getScrollRegions();return _e.decrypt().then(t=>{ee.set(t,{preserveScroll:!0,preserveState:!0}).then(()=>{Dt.restore(e),xo(ee.get())})}).catch(()=>{zr.onMissingHistoryItem()}),!0}static handleLocation(){if(!Lt.exists(Lt.locationVisitKey))return!1;const e=Lt.get(Lt.locationVisitKey)||{};return Lt.remove(Lt.locationVisitKey),typeof window<"u"&&ee.setUrlHash(window.location.hash),_e.decrypt(ee.get()).then(()=>{const t=_e.getState(_e.rememberedState,{}),r=_e.getScrollRegions();ee.remember(t),ee.set(ee.get(),{preserveScroll:e.preserveScroll,preserveState:!0}).then(()=>{e.preserveScroll&&Dt.restore(r),xo(ee.get())})}).catch(()=>{zr.onMissingHistoryItem()}),!0}static handleDefault(){typeof window<"u"&&ee.setUrlHash(window.location.hash),ee.set(ee.get(),{preserveScroll:!0,preserveState:!0}).then(()=>{_d.isReload()?Dt.restore(_e.getScrollRegions()):Dt.scrollToAnchor();const e=ee.get();xo(e);const t=e.flash;Object.keys(t).length>0&&queueMicrotask(()=>Yl(t))})}},W2=class{constructor(e,t,r){this.id=null,this.throttle=!1,this.keepAlive=!1,this.cbCount=0,this.keepAlive=r.keepAlive??!1,this.cb=t,this.interval=e,(r.autoStart??!0)&&this.start()}stop(){this.id&&clearInterval(this.id)}start(){typeof window>"u"||(this.stop(),this.id=window.setInterval(()=>{(!this.throttle||this.cbCount%10===0)&&this.cb(),this.throttle&&this.cbCount++},this.interval))}isInBackground(e){this.throttle=this.keepAlive?!1:e,this.throttle&&(this.cbCount=0)}},K2=class{constructor(){this.polls=[],this.setupVisibilityListener()}add(e,t,r){const n=new W2(e,t,r);return this.polls.push(n),{stop:()=>n.stop(),start:()=>n.start()}}clear(){this.polls.forEach(e=>e.stop()),this.polls=[]}setupVisibilityListener(){typeof document>"u"||document.addEventListener("visibilitychange",()=>{this.polls.forEach(e=>e.isInBackground(document.hidden))},!1)}},G2=new K2,Vh=class ul{constructor(t){if(this.callbacks=[],!t.prefetch)this.params=t;else{const r={onBefore:this.wrapCallback(t,"onBefore"),onBeforeUpdate:this.wrapCallback(t,"onBeforeUpdate"),onStart:this.wrapCallback(t,"onStart"),onProgress:this.wrapCallback(t,"onProgress"),onFinish:this.wrapCallback(t,"onFinish"),onCancel:this.wrapCallback(t,"onCancel"),onSuccess:this.wrapCallback(t,"onSuccess"),onError:this.wrapCallback(t,"onError"),onFlash:this.wrapCallback(t,"onFlash"),onCancelToken:this.wrapCallback(t,"onCancelToken"),onPrefetched:this.wrapCallback(t,"onPrefetched"),onPrefetching:this.wrapCallback(t,"onPrefetching")};this.params={...t,...r,onPrefetchResponse:t.onPrefetchResponse||(()=>{}),onPrefetchError:t.onPrefetchError||(()=>{})}}}static create(t){return new ul(t)}data(){return this.params.method==="get"?null:this.params.data}queryParams(){return this.params.method==="get"?this.params.data:{}}isPartial(){return this.params.only.length>0||this.params.except.length>0||this.params.reset.length>0}isPrefetch(){return this.params.prefetch===!0}isDeferredPropsRequest(){return this.params.deferredProps===!0}onCancelToken(t){this.params.onCancelToken({cancel:t})}markAsFinished(){this.params.completed=!0,this.params.cancelled=!1,this.params.interrupted=!1}markAsCancelled({cancelled:t=!0,interrupted:r=!1}){this.params.onCancel(),this.params.completed=!1,this.params.cancelled=t,this.params.interrupted=r}wasCancelledAtAll(){return this.params.cancelled||this.params.interrupted}onFinish(){this.params.onFinish(this.params)}onStart(){this.params.onStart(this.params)}onPrefetching(){this.params.onPrefetching(this.params)}onPrefetchResponse(t){this.params.onPrefetchResponse&&this.params.onPrefetchResponse(t)}onPrefetchError(t){this.params.onPrefetchError&&this.params.onPrefetchError(t)}all(){return this.params}headers(){const t={...this.params.headers};this.isPartial()&&(t["X-Inertia-Partial-Component"]=ee.get().component);const r=this.params.only.concat(this.params.reset);return r.length>0&&(t["X-Inertia-Partial-Data"]=r.join(",")),this.params.except.length>0&&(t["X-Inertia-Partial-Except"]=this.params.except.join(",")),this.params.reset.length>0&&(t["X-Inertia-Reset"]=this.params.reset.join(",")),this.params.errorBag&&this.params.errorBag.length>0&&(t["X-Inertia-Error-Bag"]=this.params.errorBag),t}setPreserveOptions(t){this.params.preserveScroll=ul.resolvePreserveOption(this.params.preserveScroll,t),this.params.preserveState=ul.resolvePreserveOption(this.params.preserveState,t)}runCallbacks(){this.callbacks.forEach(({name:t,args:r})=>{this.params[t](...r)})}merge(t){this.params={...this.params,...t}}wrapCallback(t,r){return(...n)=>{this.recordCallback(r,n),t[r](...n)}}recordCallback(t,r){this.callbacks.push({name:t,args:r})}static resolvePreserveOption(t,r){return typeof t=="function"?t(r):t==="errors"?Object.keys(r.props.errors||{}).length>0:t}},N0={modal:null,listener:null,createIframeAndPage(e){typeof e=="object"&&(e=`All Inertia requests must receive a valid Inertia response, however a plain JSON response was received.<hr>${JSON.stringify(e)}`);const t=document.createElement("html");t.innerHTML=e,t.querySelectorAll("a").forEach(n=>n.setAttribute("target","_top"));const r=document.createElement("iframe");return r.style.backgroundColor="white",r.style.borderRadius="5px",r.style.width="100%",r.style.height="100%",{iframe:r,page:t}},show(e){const{iframe:t,page:r}=this.createIframeAndPage(e);if(this.modal=document.createElement("div"),this.modal.style.position="fixed",this.modal.style.width="100vw",this.modal.style.height="100vh",this.modal.style.padding="50px",this.modal.style.boxSizing="border-box",this.modal.style.backgroundColor="rgba(0, 0, 0, .6)",this.modal.style.zIndex=2e5,this.modal.addEventListener("click",()=>this.hide()),this.modal.appendChild(t),document.body.prepend(this.modal),document.body.style.overflow="hidden",!t.contentWindow)throw new Error("iframe not yet ready.");t.contentWindow.document.open(),t.contentWindow.document.write(r.outerHTML),t.contentWindow.document.close(),this.listener=this.hideOnEscape.bind(this),document.addEventListener("keydown",this.listener)},hide(){this.modal.outerHTML="",this.modal=null,document.body.style.overflow="visible",document.removeEventListener("keydown",this.listener)},hideOnEscape(e){e.keyCode===27&&this.hide()}},Y2={show(e){const{iframe:t,page:r}=N0.createIframeAndPage(e);t.style.boxSizing="border-box",t.style.display="block";const n=document.createElement("dialog");n.id="inertia-error-dialog",Object.assign(n.style,{width:"calc(100vw - 100px)",height:"calc(100vh - 100px)",padding:"0",margin:"auto",border:"none",backgroundColor:"transparent"});const i=document.createElement("style");if(i.textContent=`
      dialog#inertia-error-dialog::backdrop {
        background-color: rgba(0, 0, 0, 0.6);
      }

      dialog#inertia-error-dialog:focus {
        outline: none;
      }
    `,document.head.appendChild(i),n.addEventListener("click",s=>{s.target===n&&n.close()}),n.addEventListener("close",()=>{i.remove(),n.remove()}),n.appendChild(t),document.body.prepend(n),n.showModal(),n.focus(),!t.contentWindow)throw new Error("iframe not yet ready.");t.contentWindow.document.open(),t.contentWindow.document.write(r.outerHTML),t.contentWindow.document.close()}},X2=new xp,Fb=class P0{constructor(t,r,n){this.requestParams=t,this.response=r,this.originatingPage=n,this.wasPrefetched=!1}static create(t,r,n){return new P0(t,r,n)}async handlePrefetch(){Jl(this.requestParams.all().url,window.location)&&this.handle()}async handle(){return X2.add(()=>this.process())}async process(){if(this.requestParams.all().prefetch)return this.wasPrefetched=!0,this.requestParams.all().prefetch=!1,this.requestParams.all().onPrefetched(this.response,this.requestParams.all()),x2(this.response,this.requestParams.all()),Promise.resolve();if(this.requestParams.runCallbacks(),!this.isInertiaResponse())return this.handleNonInertiaResponse();await _e.processQueue(),_e.preserveUrl=this.requestParams.all().preserveUrl;const t=ee.get().flash;await this.setPage();const r=ee.get().props.errors||{};if(Object.keys(r).length>0){const i=this.getScopedErrors(r);return m2(i),this.requestParams.all().onError(i)}at.flushByCacheTags(this.requestParams.all().invalidateCacheTags||[]),this.wasPrefetched||at.flush(ee.get().url);const{flash:n}=ee.get();Object.keys(n).length>0&&(!this.requestParams.isPartial()||!ei(n,t))&&(Yl(n),this.requestParams.all().onFlash(n)),E2(ee.get()),await this.requestParams.all().onSuccess(ee.get()),_e.preserveUrl=!1}mergeParams(t){this.requestParams.merge(t)}getPageResponse(){const t=this.getDataFromResponse(this.response.data);return typeof t=="object"?this.response.data={...t,flash:t.flash??{}}:this.response.data=t}async handleNonInertiaResponse(){if(this.isLocationVisit()){const r=fn(this.getHeader("x-inertia-location"));return Nb(this.requestParams.all().url,r),this.locationVisit(r)}const t={...this.response,data:this.getDataFromResponse(this.response.data)};if(v2(t))return Ii.get("future.useDialogForErrorModal")?Y2.show(t.data):N0.show(t.data)}isInertiaResponse(){return this.hasHeader("x-inertia")}hasStatus(t){return this.response.status===t}getHeader(t){return this.response.headers[t]}hasHeader(t){return this.getHeader(t)!==void 0}isLocationVisit(){return this.hasStatus(409)&&this.hasHeader("x-inertia-location")}locationVisit(t){try{if(Lt.set(Lt.locationVisitKey,{preserveScroll:this.requestParams.all().preserveScroll===!0}),typeof window>"u")return;Jl(window.location,t)?window.location.reload():window.location.href=t.href}catch{return!1}}async setPage(){const t=this.getPageResponse();return this.shouldSetPage(t)?(this.mergeProps(t),ee.mergeOncePropsIntoResponse(t),this.preserveEqualProps(t),await this.setRememberedState(t),this.requestParams.setPreserveOptions(t),t.url=_e.preserveUrl?ee.get().url:this.pageUrl(t),this.requestParams.all().onBeforeUpdate(t),y2(t),ee.set(t,{replace:this.requestParams.all().replace,preserveScroll:this.requestParams.all().preserveScroll,preserveState:this.requestParams.all().preserveState,viewTransition:this.requestParams.all().viewTransition})):Promise.resolve()}getDataFromResponse(t){if(typeof t!="string")return t;try{return JSON.parse(t)}catch{return t}}shouldSetPage(t){if(!this.requestParams.all().async||this.originatingPage.component!==t.component)return!0;if(this.originatingPage.component!==ee.get().component)return!1;const r=fn(this.originatingPage.url),n=fn(ee.get().url);return r.origin===n.origin&&r.pathname===n.pathname}pageUrl(t){const r=fn(t.url);return Nb(this.requestParams.all().url,r),r.pathname+r.search+r.hash}preserveEqualProps(t){if(t.component!==ee.get().component||Ii.get("future.preserveEqualProps")!==!0)return;const r=ee.get().props;Object.entries(t.props).forEach(([n,i])=>{ei(i,r[n])&&(t.props[n]=r[n])})}mergeProps(t){if(!this.requestParams.isPartial()||t.component!==ee.get().component)return;const r=t.mergeProps||[],n=t.prependProps||[],i=t.deepMergeProps||[],s=t.matchPropsOn||[],o=(l,u)=>{const c=Ar(ee.get().props,l),d=Ar(t.props,l);if(Array.isArray(d)){const p=this.mergeOrMatchItems(c||[],d,l,s,u);qr(t.props,l,p)}else if(typeof d=="object"&&d!==null){const p={...c||{},...d};qr(t.props,l,p)}};if(r.forEach(l=>o(l,!0)),n.forEach(l=>o(l,!1)),i.forEach(l=>{const u=ee.get().props[l],c=t.props[l],d=(p,m,h)=>Array.isArray(m)?this.mergeOrMatchItems(p,m,h,s):typeof m=="object"&&m!==null?Object.keys(m).reduce((f,b)=>(f[b]=d(p?p[b]:void 0,m[b],`${h}.${b}`),f),{...p}):m;t.props[l]=d(u,c,l)}),t.props={...ee.get().props,...t.props},this.requestParams.isDeferredPropsRequest()){const l=ee.get().props.errors;l&&Object.keys(l).length>0&&(t.props.errors=l)}ee.get().scrollProps&&(t.scrollProps={...ee.get().scrollProps||{},...t.scrollProps||{}}),ee.hasOnceProps()&&(t.onceProps={...ee.get().onceProps||{},...t.onceProps||{}}),t.flash={...ee.get().flash,...this.requestParams.isDeferredPropsRequest()?{}:t.flash};const a=ee.get().initialDeferredProps;a&&Object.keys(a).length>0&&(t.initialDeferredProps=a)}mergeOrMatchItems(t,r,n,i,s=!0){const o=Array.isArray(t)?t:[],a=i.find(c=>c.split(".").slice(0,-1).join(".")===n);if(!a)return s?[...o,...r]:[...r,...o];const l=a.split(".").pop()||"",u=new Map;return r.forEach(c=>{this.hasUniqueProperty(c,l)&&u.set(c[l],c)}),s?this.appendWithMatching(o,r,u,l):this.prependWithMatching(o,r,u,l)}appendWithMatching(t,r,n,i){const s=t.map(a=>this.hasUniqueProperty(a,i)&&n.has(a[i])?n.get(a[i]):a),o=r.filter(a=>this.hasUniqueProperty(a,i)?!t.some(l=>this.hasUniqueProperty(l,i)&&l[i]===a[i]):!0);return[...s,...o]}prependWithMatching(t,r,n,i){const s=t.filter(o=>this.hasUniqueProperty(o,i)?!n.has(o[i]):!0);return[...r,...s]}hasUniqueProperty(t,r){return t&&typeof t=="object"&&r in t}async setRememberedState(t){const r=await _e.getState(_e.rememberedState,{});this.requestParams.all().preserveState&&r&&t.component===ee.get().component&&(t.rememberedState=r)}getScopedErrors(t){return this.requestParams.all().errorBag?t[this.requestParams.all().errorBag||""]||{}:t}},Ib=class F0{constructor(t,r){this.page=r,this.requestHasFinished=!1,this.requestParams=Vh.create(t),this.cancelToken=new AbortController}static create(t,r){return new F0(t,r)}isPrefetch(){return this.requestParams.isPrefetch()}async send(){this.requestParams.onCancelToken(()=>this.cancel({cancelled:!0})),w2(this.requestParams.all()),this.requestParams.onStart(),this.requestParams.all().prefetch&&(this.requestParams.onPrefetching(),S2(this.requestParams.all()));const t=this.requestParams.all().prefetch;return ls({method:this.requestParams.all().method,url:Xl(this.requestParams.all().url).href,data:this.requestParams.data(),params:this.requestParams.queryParams(),signal:this.cancelToken.signal,headers:this.getHeaders(),onUploadProgress:this.onProgress.bind(this),responseType:"text"}).then(r=>(this.response=Fb.create(this.requestParams,r,this.page),this.response.handle())).catch(r=>r?.response?(this.response=Fb.create(this.requestParams,r.response,this.page),this.response.handle()):Promise.reject(r)).catch(r=>{if(!ls.isCancel(r)&&g2(r))return t&&this.requestParams.onPrefetchError(r),Promise.reject(r)}).finally(()=>{this.finish(),t&&this.response&&this.requestParams.onPrefetchResponse(this.response)})}finish(){this.requestParams.wasCancelledAtAll()||(this.requestParams.markAsFinished(),this.fireFinishEvents())}fireFinishEvents(){this.requestHasFinished||(this.requestHasFinished=!0,b2(this.requestParams.all()),this.requestParams.onFinish())}cancel({cancelled:t=!1,interrupted:r=!1}){this.requestHasFinished||(this.cancelToken.abort(),this.requestParams.markAsCancelled({cancelled:t,interrupted:r}),this.fireFinishEvents())}onProgress(t){this.requestParams.data()instanceof FormData&&(t.percentage=t.progress?Math.round(t.progress*100):0,_2(t),this.requestParams.all().onProgress(t))}getHeaders(){const t={...this.requestParams.headers(),Accept:"text/html, application/xhtml+xml","X-Requested-With":"XMLHttpRequest","X-Inertia":!0},r=ee.get();r.version&&(t["X-Inertia-Version"]=r.version);const n=Object.entries(r.onceProps||{}).filter(([,i])=>r.props[i.prop]===void 0?!1:!i.expiresAt||i.expiresAt>Date.now()).map(([i])=>i);return n.length>0&&(t["X-Inertia-Except-Once-Props"]=n.join(",")),t}},Lb=class{constructor({maxConcurrent:e,interruptible:t}){this.requests=[],this.maxConcurrent=e,this.interruptible=t}send(e){this.requests.push(e),e.send().then(()=>{this.requests=this.requests.filter(t=>t!==e)})}interruptInFlight(){this.cancel({interrupted:!0},!1)}cancelInFlight({prefetch:e=!0}={}){this.requests.filter(t=>e||!t.isPrefetch()).forEach(t=>t.cancel({cancelled:!0}))}cancel({cancelled:e=!1,interrupted:t=!1}={},r=!1){if(!r&&!this.shouldCancel())return;this.requests.shift()?.cancel({cancelled:e,interrupted:t})}shouldCancel(){return this.interruptible&&this.requests.length>=this.maxConcurrent}},J2=class{constructor(){this.syncRequestStream=new Lb({maxConcurrent:1,interruptible:!0}),this.asyncRequestStream=new Lb({maxConcurrent:1/0,interruptible:!1}),this.clientVisitQueue=new xp}init({initialPage:e,resolveComponent:t,swapComponent:r,onFlash:n}){ee.init({initialPage:e,resolveComponent:t,swapComponent:r,onFlash:n}),j2.handle(),zr.init(),zr.on("missingHistoryItem",()=>{typeof window<"u"&&this.visit(window.location.href,{preserveState:!0,preserveScroll:!0,replace:!0})}),zr.on("loadDeferredProps",i=>{this.loadDeferredProps(i)}),zr.on("historyQuotaExceeded",i=>{window.location.href=i})}get(e,t={},r={}){return this.visit(e,{...r,method:"get",data:t})}post(e,t={},r={}){return this.visit(e,{preserveState:!0,...r,method:"post",data:t})}put(e,t={},r={}){return this.visit(e,{preserveState:!0,...r,method:"put",data:t})}patch(e,t={},r={}){return this.visit(e,{preserveState:!0,...r,method:"patch",data:t})}delete(e,t={}){return this.visit(e,{preserveState:!0,...t,method:"delete"})}reload(e={}){return this.doReload(e)}doReload(e={}){if(!(typeof window>"u"))return this.visit(window.location.href,{...e,preserveScroll:!0,preserveState:!0,async:!0,headers:{...e.headers||{},"Cache-Control":"no-cache"}})}remember(e,t="default"){_e.remember(e,t)}restore(e="default"){return _e.restore(e)}on(e,t){return typeof window>"u"?()=>{}:zr.onGlobalEvent(e,t)}cancel(){this.syncRequestStream.cancelInFlight()}cancelAll({async:e=!0,prefetch:t=!0,sync:r=!0}={}){e&&this.asyncRequestStream.cancelInFlight({prefetch:t}),r&&this.syncRequestStream.cancelInFlight()}poll(e,t={},r={}){return G2.add(e,()=>this.reload(t),{autoStart:r.autoStart??!0,keepAlive:r.keepAlive??!1})}visit(e,t={}){const r=this.getPendingVisit(e,{...t,showProgress:t.showProgress??!t.async}),n=this.getVisitEvents(t);if(n.onBefore(r)===!1||!kb(r))return;const i=fn(ee.get().url);(r.only.length>0||r.except.length>0||r.reset.length>0?V2(r.url,i):Jl(r.url,i))||this.asyncRequestStream.cancelInFlight({prefetch:!1}),r.async||this.syncRequestStream.interruptInFlight(),!ee.isCleared()&&!r.preserveUrl&&Dt.save();const a={...r,...n},l=Br.get(a);l?(Bt.reveal(l.inFlight),Br.use(l,a)):(Bt.reveal(!0),(r.async?this.asyncRequestStream:this.syncRequestStream).send(Ib.create(a,ee.get())))}getCached(e,t={}){return Br.findCached(this.getPrefetchParams(e,t))}flush(e,t={}){Br.remove(this.getPrefetchParams(e,t))}flushAll(){Br.removeAll()}flushByCacheTags(e){Br.removeByTags(Array.isArray(e)?e:[e])}getPrefetching(e,t={}){return Br.findInFlight(this.getPrefetchParams(e,t))}prefetch(e,t={},r={}){if((t.method??(ws(e)?e.method:"get"))!=="get")throw new Error("Prefetch requests must use the GET method");const i=this.getPendingVisit(e,{...t,async:!0,showProgress:!1,prefetch:!0,viewTransition:!1}),s=i.url.origin+i.url.pathname+i.url.search,o=window.location.origin+window.location.pathname+window.location.search;if(s===o)return;const a=this.getVisitEvents(t);if(a.onBefore(i)===!1||!kb(i))return;Bt.hide(),this.asyncRequestStream.interruptInFlight();const l={...i,...a};new Promise(c=>{const d=()=>{ee.get()?c():setTimeout(d,50)};d()}).then(()=>{Br.add(l,c=>{this.asyncRequestStream.send(Ib.create(c,ee.get()))},{cacheFor:Ii.get("prefetch.cacheFor"),cacheTags:[],...r})})}clearHistory(){_e.clear()}decryptHistory(){return _e.decrypt()}resolveComponent(e){return ee.resolve(e)}replace(e){this.clientVisit(e,{replace:!0})}replaceProp(e,t,r){this.replace({preserveScroll:!0,preserveState:!0,props(n){const i=typeof t=="function"?t(Ar(n,e),n):t;return qr(ht(n),e,i)},...r||{}})}appendToProp(e,t,r){this.replaceProp(e,(n,i)=>{const s=typeof t=="function"?t(n,i):t;return Array.isArray(n)||(n=n!==void 0?[n]:[]),[...n,s]},r)}prependToProp(e,t,r){this.replaceProp(e,(n,i)=>{const s=typeof t=="function"?t(n,i):t;return Array.isArray(n)||(n=n!==void 0?[n]:[]),[s,...n]},r)}push(e){this.clientVisit(e)}flash(e,t){const r=ee.get().flash;let n;if(typeof e=="function")n=e(r);else if(typeof e=="string")n={...r,[e]:t};else if(e&&Object.keys(e).length)n={...r,...e};else return;ee.setFlash(n),Object.keys(n).length&&Yl(n)}clientVisit(e,{replace:t=!1}={}){this.clientVisitQueue.add(()=>this.performClientVisit(e,{replace:t}))}performClientVisit(e,{replace:t=!1}={}){const r=ee.get(),n=typeof e.props=="function"?Object.fromEntries(Object.values(r.onceProps??{}).map(f=>[f.prop,r.props[f.prop]])):{},i=typeof e.props=="function"?e.props(r.props,n):e.props??r.props,s=typeof e.flash=="function"?e.flash(r.flash):e.flash,{viewTransition:o,onError:a,onFinish:l,onFlash:u,onSuccess:c,...d}=e,p={...r,...d,flash:s??{},props:i},m=Vh.resolvePreserveOption(e.preserveScroll??!1,p),h=Vh.resolvePreserveOption(e.preserveState??!1,p);return ee.set(p,{replace:t,preserveScroll:m,preserveState:h,viewTransition:o}).then(()=>{const f=ee.get().flash;Object.keys(f).length>0&&(Yl(f),u?.(f));const b=ee.get().props.errors||{};if(Object.keys(b).length===0){c?.(ee.get());return}const y=e.errorBag?b[e.errorBag||""]||{}:b;a?.(y)}).finally(()=>l?.(e))}getPrefetchParams(e,t){return{...this.getPendingVisit(e,{...t,async:!0,showProgress:!1,prefetch:!0,viewTransition:!1}),...this.getVisitEvents(t)}}getPendingVisit(e,t,r={}){if(ws(e)){const u=e;e=u.url,t.method=t.method??u.method}const n=Ii.get("visitOptions"),i=n?n(e.toString(),ht(t))||{}:{},s={method:"get",data:{},replace:!1,preserveScroll:!1,preserveState:!1,only:[],except:[],headers:{},errorBag:"",forceFormData:!1,queryStringArrayFormat:"brackets",async:!1,showProgress:!0,fresh:!1,reset:[],preserveUrl:!1,prefetch:!1,invalidateCacheTags:[],viewTransition:!1,...t,...i},[o,a]=D2(e,s.data,s.method,s.forceFormData,s.queryStringArrayFormat),l={cancelled:!1,completed:!1,interrupted:!1,...s,...r,url:o,data:a};return l.prefetch&&(l.headers.Purpose="prefetch"),l}getVisitEvents(e){return{onCancelToken:e.onCancelToken||(()=>{}),onBefore:e.onBefore||(()=>{}),onBeforeUpdate:e.onBeforeUpdate||(()=>{}),onStart:e.onStart||(()=>{}),onProgress:e.onProgress||(()=>{}),onFinish:e.onFinish||(()=>{}),onCancel:e.onCancel||(()=>{}),onSuccess:e.onSuccess||(()=>{}),onError:e.onError||(()=>{}),onFlash:e.onFlash||(()=>{}),onPrefetched:e.onPrefetched||(()=>{}),onPrefetching:e.onPrefetching||(()=>{})}}loadDeferredProps(e){e&&Object.entries(e).forEach(([t,r])=>{this.doReload({only:r,deferredProps:!0})})}},wd=class{static createWayfinderCallback(...e){return()=>e.length===1?ws(e[0])?e[0]:e[0]():{method:typeof e[0]=="function"?e[0]():e[0],url:typeof e[1]=="function"?e[1]():e[1]}}static parseUseFormArguments(...e){return e.length===0?{rememberKey:null,data:{},precognitionEndpoint:null}:e.length===1?{rememberKey:null,data:e[0],precognitionEndpoint:null}:e.length===2?typeof e[0]=="string"?{rememberKey:e[0],data:e[1],precognitionEndpoint:null}:{rememberKey:null,data:e[1],precognitionEndpoint:this.createWayfinderCallback(e[0])}:{rememberKey:null,data:e[2],precognitionEndpoint:this.createWayfinderCallback(e[0],e[1])}}static parseSubmitArguments(e,t){return e.length===3||e.length===2&&typeof e[0]=="string"?{method:e[0],url:e[1],options:e[2]??{}}:ws(e[0])?{...e[0],options:e[1]??{}}:{...t(),options:e[0]??{}}}static mergeHeadersForValidation(e,t,r){const n=i=>(i.headers={...r??{},...i.headers??{}},i);return e&&typeof e=="object"&&!("target"in e)?e=n(e):t&&typeof t=="object"?t=n(t):typeof e=="string"?t=n(t??{}):e=n(e??{}),[e,t]}},Ed={preferredAttribute(){return Ii.get("future.useDataInertiaHeadAttribute")?"data-inertia":"inertia"},buildDOMElement(e){const t=document.createElement("template");t.innerHTML=e;const r=t.content.firstChild;if(!e.startsWith("<script "))return r;const n=document.createElement("script");return n.innerHTML=r.innerHTML,r.getAttributeNames().forEach(i=>{n.setAttribute(i,r.getAttribute(i)||"")}),n},isInertiaManagedElement(e){return e.nodeType===Node.ELEMENT_NODE&&e.getAttribute(this.preferredAttribute())!==null},findMatchingElementIndex(e,t){const r=this.preferredAttribute(),n=e.getAttribute(r);return n!==null?t.findIndex(i=>i.getAttribute(r)===n):-1},update:$h(function(e){const t=e.map(n=>this.buildDOMElement(n));Array.from(document.head.childNodes).filter(n=>this.isInertiaManagedElement(n)).forEach(n=>{const i=this.findMatchingElementIndex(n,t);if(i===-1){n?.parentNode?.removeChild(n);return}const s=t.splice(i,1)[0];s&&!n.isEqualNode(s)&&n?.parentNode?.replaceChild(s,n)}),t.forEach(n=>document.head.appendChild(n))},1)};function Q2(e,t,r){const n={};let i=0;function s(){const d=i+=1;return n[d]=[],d.toString()}function o(d){d===null||Object.keys(n).indexOf(d)===-1||(delete n[d],c())}function a(d){Object.keys(n).indexOf(d)===-1&&(n[d]=[])}function l(d,p=[]){d!==null&&Object.keys(n).indexOf(d)>-1&&(n[d]=p),c()}function u(){const d=t(""),p=Ed.preferredAttribute(),m={...d?{title:`<title ${p}="">${d}</title>`}:{}},h=Object.values(n).reduce((f,b)=>f.concat(b),[]).reduce((f,b)=>{if(b.indexOf("<")===-1)return f;if(b.indexOf("<title ")===0){const w=b.match(/(<title [^>]+>)(.*?)(<\/title>)/);return f.title=w?`${w[1]}${t(w[2])}${w[3]}`:b,f}const y=b.match(p==="inertia"?/ inertia="[^"]+"/:/ data-inertia="[^"]+"/);return y?f[y[0]]=b:f[Object.keys(f).length]=b,f},m);return Object.values(h)}function c(){e?r(u()):Ed.update(u())}return c(),{forceUpdate:c,createProvider:function(){const d=s();return{preferredAttribute:Ed.preferredAttribute,reconnect:()=>a(d),update:p=>l(d,p),disconnect:()=>o(d)}}}}function I0(e){return e.target instanceof HTMLElement&&e.target.isContentEditable||e.defaultPrevented}function za(e){const t=e.currentTarget.tagName.toLowerCase()==="a";return!(I0(e)||t&&e.altKey||t&&e.ctrlKey||t&&e.metaKey||t&&e.shiftKey||t&&"button"in e&&e.button!==0)}function Rb(e){const t=e.currentTarget.tagName.toLowerCase()==="button";return!I0(e)&&(e.key==="Enter"||t&&e.key===" ")}var it="nprogress",hr,ft={minimum:.08,easing:"linear",positionUsing:"translate3d",speed:200,trickle:!0,trickleSpeed:200,showSpinner:!0,barSelector:'[role="bar"]',spinnerSelector:'[role="spinner"]',parent:"body",color:"#29d",includeCSS:!0,template:['<div class="bar" role="bar">','<div class="peg"></div>',"</div>",'<div class="spinner" role="spinner">','<div class="spinner-icon"></div>',"</div>"].join("")},ni=null,Z2=e=>{Object.assign(ft,e),ft.includeCSS&&sL(ft.color),hr=document.createElement("div"),hr.id=it,hr.innerHTML=ft.template},Hc=e=>{const t=L0();e=V0(e,ft.minimum,1),ni=e===1?null:e;const r=tL(!t),n=r.querySelector(ft.barSelector),i=ft.speed,s=ft.easing;r.offsetWidth,iL(o=>{const a=ft.positionUsing==="translate3d"?{transition:`all ${i}ms ${s}`,transform:`translate3d(${dl(e)}%,0,0)`}:ft.positionUsing==="translate"?{transition:`all ${i}ms ${s}`,transform:`translate(${dl(e)}%,0)`}:{marginLeft:`${dl(e)}%`};for(const l in a)n.style[l]=a[l];if(e!==1)return setTimeout(o,i);r.style.transition="none",r.style.opacity="1",r.offsetWidth,setTimeout(()=>{r.style.transition=`all ${i}ms linear`,r.style.opacity="0",setTimeout(()=>{D0(),r.style.transition="",r.style.opacity="",o()},i)},i)})},L0=()=>typeof ni=="number",R0=()=>{ni||Hc(0);const e=function(){setTimeout(function(){ni&&($0(),e())},ft.trickleSpeed)};ft.trickle&&e()},eL=e=>{!e&&!ni||($0(.3+.5*Math.random()),Hc(1))},$0=e=>{const t=ni;if(t===null)return R0();if(!(t>1))return e=typeof e=="number"?e:(()=>{const r={.1:[0,.2],.04:[.2,.5],.02:[.5,.8],.005:[.8,.99]};for(const n in r)if(t>=r[n][0]&&t<r[n][1])return parseFloat(n);return 0})(),Hc(V0(t+e,0,.994))},tL=e=>{if(rL())return document.getElementById(it);document.documentElement.classList.add(`${it}-busy`);const t=hr.querySelector(ft.barSelector),r=e?"-100":dl(ni||0),n=M0();return t.style.transition="all 0 linear",t.style.transform=`translate3d(${r}%,0,0)`,ft.showSpinner||hr.querySelector(ft.spinnerSelector)?.remove(),n!==document.body&&n.classList.add(`${it}-custom-parent`),n.appendChild(hr),hr},M0=()=>nL(ft.parent)?ft.parent:document.querySelector(ft.parent),D0=()=>{document.documentElement.classList.remove(`${it}-busy`),M0().classList.remove(`${it}-custom-parent`),hr?.remove()},rL=()=>document.getElementById(it)!==null,nL=e=>typeof HTMLElement=="object"?e instanceof HTMLElement:e&&typeof e=="object"&&e.nodeType===1&&typeof e.nodeName=="string";function V0(e,t,r){return e<t?t:e>r?r:e}var dl=e=>(-1+e)*100,iL=(()=>{const e=[],t=()=>{const r=e.shift();r&&r(t)};return r=>{e.push(r),e.length===1&&t()}})(),sL=e=>{const t=document.createElement("style");t.textContent=`
    #${it} {
      pointer-events: none;
    }

    #${it} .bar {
      background: ${e};

      position: fixed;
      z-index: 1031;
      top: 0;
      left: 0;

      width: 100%;
      height: 2px;
    }

    #${it} .peg {
      display: block;
      position: absolute;
      right: 0px;
      width: 100px;
      height: 100%;
      box-shadow: 0 0 10px ${e}, 0 0 5px ${e};
      opacity: 1.0;

      transform: rotate(3deg) translate(0px, -4px);
    }

    #${it} .spinner {
      display: block;
      position: fixed;
      z-index: 1031;
      top: 15px;
      right: 15px;
    }

    #${it} .spinner-icon {
      width: 18px;
      height: 18px;
      box-sizing: border-box;

      border: solid 2px transparent;
      border-top-color: ${e};
      border-left-color: ${e};
      border-radius: 50%;

      animation: ${it}-spinner 400ms linear infinite;
    }

    .${it}-custom-parent {
      overflow: hidden;
      position: relative;
    }

    .${it}-custom-parent #${it} .spinner,
    .${it}-custom-parent #${it} .bar {
      position: absolute;
    }

    @keyframes ${it}-spinner {
      0%   { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  `,document.head.appendChild(t)},oL=()=>{hr&&(hr.style.display="")},aL=()=>{hr&&(hr.style.display="none")},Er={configure:Z2,isStarted:L0,done:eL,set:Hc,remove:D0,start:R0,status:ni,show:oL,hide:aL},lL=class{constructor(){this.hideCount=0}start(){Er.start()}reveal(e=!1){this.hideCount=Math.max(0,this.hideCount-1),(e||this.hideCount===0)&&Er.show()}hide(){this.hideCount++,Er.hide()}set(e){Er.set(Math.max(0,Math.min(1,e)))}finish(){Er.done()}reset(){Er.set(0)}remove(){Er.done(),Er.remove()}isStarted(){return Er.isStarted()}getStatus(){return Er.status}},Bt=new lL;Bt.reveal;Bt.hide;function cL(e){document.addEventListener("inertia:start",t=>uL(t,e)),document.addEventListener("inertia:progress",dL)}function uL(e,t){e.detail.visit.showProgress||Bt.hide();const r=setTimeout(()=>Bt.start(),t);document.addEventListener("inertia:finish",n=>hL(n,r),{once:!0})}function dL(e){Bt.isStarted()&&e.detail.progress?.percentage&&Bt.set(Math.max(Bt.getStatus(),e.detail.progress.percentage/100*.9))}function hL(e,t){clearTimeout(t),Bt.isStarted()&&(e.detail.visit.completed?Bt.finish():e.detail.visit.interrupted?Bt.reset():e.detail.visit.cancelled&&Bt.remove())}function fL({delay:e=250,color:t="#29d",includeCSS:r=!0,showSpinner:n=!1}={}){cL(e),Er.configure({showSpinner:n,includeCSS:r,color:t})}var at=new J2;let Yo=ls.create(),B0=(e,t)=>`${e.method}:${e.baseURL??t.defaults.baseURL??""}${e.url}`,U0=e=>e.status===204&&e.headers["precognition-success"]==="true";const Ql={},Kn={get:(e,t={},r={})=>Ws(js("get",e,t,r)),post:(e,t={},r={})=>Ws(js("post",e,t,r)),patch:(e,t={},r={})=>Ws(js("patch",e,t,r)),put:(e,t={},r={})=>Ws(js("put",e,t,r)),delete:(e,t={},r={})=>Ws(js("delete",e,t,r)),use(e){return Yo=e,Kn},axios(){return Yo},fingerprintRequestsUsing(e){return B0=e===null?()=>null:e,Kn},determineSuccessUsing(e){return U0=e,Kn}},js=(e,t,r,n)=>({url:t,method:e,...n,...["get","delete"].includes(e)?{params:Rh({},r,n?.params)}:{data:Rh({},r,n?.data)}}),Ws=(e={})=>{const t=[pL,gL,bL].reduce((r,n)=>n(r),e);return(t.onBefore??(()=>!0))()===!1?Promise.resolve(null):((t.onStart??(()=>null))(),Yo.request(t).then(async r=>{t.precognitive&&$b(r);const n=r.status;let i=r;return t.precognitive&&t.onPrecognitionSuccess&&U0(i)&&(i=await Promise.resolve(t.onPrecognitionSuccess(i)??i)),t.onSuccess&&mL(n)&&(i=await Promise.resolve(t.onSuccess(i)??i)),(Mb(t,n)??(o=>o))(i)??i},r=>vL(r)?Promise.reject(r):(t.precognitive&&$b(r.response),(Mb(t,r.response.status)??((i,s)=>Promise.reject(s)))(r.response,r))).finally(t.onFinish??(()=>null)))},pL=e=>{const t=e.only??e.validate;return{...e,timeout:e.timeout??Yo.defaults.timeout??3e4,precognitive:e.precognitive!==!1,fingerprint:typeof e.fingerprint>"u"?B0(e,Yo):e.fingerprint,headers:{...e.headers,"Content-Type":yL(e),...e.precognitive!==!1?{Precognition:!0}:{},...t?{"Precognition-Validate-Only":Array.from(t).join()}:{}}}},mL=e=>e>=200&&e<300,gL=e=>(typeof e.fingerprint!="string"||(Ql[e.fingerprint]?.abort(),delete Ql[e.fingerprint]),e),bL=e=>typeof e.fingerprint!="string"||e.signal||e.cancelToken||!e.precognitive?e:(Ql[e.fingerprint]=new AbortController,{...e,signal:Ql[e.fingerprint].signal}),$b=e=>{if(e.headers?.precognition!=="true")throw Error("Did not receive a Precognition response. Ensure you have the Precognition middleware in place for the route.")},vL=e=>!Nv(e)||typeof e.response?.status!="number"||Ov(e),Mb=(e,t)=>({401:e.onUnauthorized,403:e.onForbidden,404:e.onNotFound,409:e.onConflict,422:e.onValidationError,423:e.onLocked})[t],yL=e=>e.headers?.["Content-Type"]??e.headers?.["Content-type"]??e.headers?.["content-type"]??(z0(e.data)?"multipart/form-data":"application/json"),z0=e=>Sp(e)||typeof e=="object"&&e!==null&&Object.values(e).some(t=>z0(t)),Sp=e=>typeof File<"u"&&e instanceof File||e instanceof Blob||typeof FileList<"u"&&e instanceof FileList&&e.length>0,_L=(e,t={})=>{const r={errorsChanged:[],touchedChanged:[],validatingChanged:[],validatedChanged:[]};let n=!1,i=!1;const s=F=>F!==i?(i=F,r.validatingChanged):[];let o=[];const a=F=>{const C=[...new Set(F)];return o.length!==C.length||!C.every(g=>o.includes(g))?(o=C,r.validatedChanged):[]},l=()=>o.filter(F=>typeof d[F]>"u");let u=[];const c=F=>{const C=[...new Set(F)];return u.length!==C.length||!C.every(g=>u.includes(g))?(u=C,r.touchedChanged):[]};let d={};const p=F=>{const C=EL(F);return ei(d,C)?[]:(d=C,r.errorsChanged)},m=F=>{const C={...d};return delete C[So(F)],p(C)},h=()=>Object.keys(d).length>0;let f=1500;const b=F=>{f=F,k.cancel(),k=x()};let y=t,w=null,v=[],_=null;const x=()=>pI(F=>{e({get:(C,g={},L={})=>Kn.get(C,A(g),S(L,F,g)),post:(C,g={},L={})=>Kn.post(C,A(g),S(L,F,g)),patch:(C,g={},L={})=>Kn.patch(C,A(g),S(L,F,g)),put:(C,g={},L={})=>Kn.put(C,A(g),S(L,F,g)),delete:(C,g={},L={})=>Kn.delete(C,A(g),S(L,F,g))}).catch(C=>Ov(C)||Nv(C)&&C.response?.status===422?null:Promise.reject(C))},f,{leading:!0,trailing:!0});let k=x();const S=(F,C,g={})=>{const L={...F,...C},B=Array.from(L.only??L.validate??u);return{...C,...XE(F,C),only:B,timeout:L.timeout??5e3,onValidationError:($,z)=>([...a([...o,...B]),...p(Rh(Lg({...d},B),$.data.errors))].forEach(P=>P()),L.onValidationError?L.onValidationError($,z):Promise.reject(z)),onSuccess:$=>(a([...o,...B]).forEach(z=>z()),L.onSuccess?L.onSuccess($):$),onPrecognitionSuccess:$=>([...a([...o,...B]),...p(Lg({...d},B))].forEach(z=>z()),L.onPrecognitionSuccess?L.onPrecognitionSuccess($):$),onBefore:()=>L.onBeforeValidation&&L.onBeforeValidation({data:g,touched:u},{data:y,touched:v})===!1||(L.onBefore||(()=>!0))()===!1?!1:(_=u,w=g,!0),onStart:()=>{s(!0).forEach($=>$()),(L.onStart??(()=>null))()},onFinish:()=>{s(!1).forEach($=>$()),v=_,y=w,_=w=null,(L.onFinish??(()=>null))()}}},N=(F,C,g)=>{if(typeof F>"u"){const L=Array.from(g?.only??g?.validate??[]);c([...u,...L]).forEach(B=>B()),k(g??{});return}if(Sp(C)&&!n){console.warn('Precognition file validation is not active. Call the "validateFiles" function on your form to enable it.');return}F=So(F),Ar(y,F)!==C&&(c([F,...u]).forEach(L=>L()),k(g??{}))},A=F=>n===!1?Bh(F):F,T={touched:()=>u,validate(F,C,g){return typeof F=="object"&&!("target"in F)&&(g=F,F=C=void 0),N(F,C,g),T},touch(F){const C=Array.isArray(F)?F:[So(F)];return c([...u,...C]).forEach(g=>g()),T},validating:()=>i,valid:l,errors:()=>d,hasErrors:h,setErrors(F){return p(F).forEach(C=>C()),T},forgetError(F){return m(F).forEach(C=>C()),T},defaults(F){return t=F,y=F,T},reset(...F){if(F.length===0)c([]).forEach(C=>C());else{const C=[...u];F.forEach(g=>{C.includes(g)&&C.splice(C.indexOf(g),1),qr(y,g,Ar(t,g))}),c(C).forEach(g=>g())}return T},setTimeout(F){return b(F),T},on(F,C){return r[F].push(C),T},validateFiles(){return n=!0,T},withoutFileValidation(){return n=!1,T}};return T},wL=e=>Object.keys(e).reduce((t,r)=>({...t,[r]:Array.isArray(e[r])?e[r][0]:e[r]}),{}),EL=e=>Object.keys(e).reduce((t,r)=>({...t,[r]:typeof e[r]=="string"?[e[r]]:e[r]}),{}),So=e=>typeof e!="string"?e.target.name:e,Bh=e=>{const t={...e};return Object.keys(t).forEach(r=>{const n=t[r];if(n!==null){if(Sp(n)){delete t[r];return}if(Array.isArray(n)){t[r]=Object.values(Bh({...n}));return}if(typeof n=="object"){t[r]=Bh(t[r]);return}}}),t};var xL={created(){if(!this.$options.remember)return;Array.isArray(this.$options.remember)&&(this.$options.remember={data:this.$options.remember}),typeof this.$options.remember=="string"&&(this.$options.remember={data:[this.$options.remember]}),typeof this.$options.remember.data=="string"&&(this.$options.remember={data:[this.$options.remember.data]});const e=this.$options.remember.key instanceof Function?this.$options.remember.key.call(this):this.$options.remember.key,t=at.restore(e),r=this.$options.remember.data.filter(i=>!(this[i]!==null&&typeof this[i]=="object"&&this[i].__rememberable===!1)),n=i=>this[i]!==null&&typeof this[i]=="object"&&typeof this[i].__remember=="function"&&typeof this[i].__restore=="function";r.forEach(i=>{this[i]!==void 0&&t!==void 0&&t[i]!==void 0&&(n(i)?this[i].__restore(t[i]):this[i]=t[i]),this.$watch(i,()=>{at.remember(r.reduce((s,o)=>({...s,[o]:ht(n(o)?this[o].__remember():this[o])}),{}),e)},{immediate:!0,deep:!0})})}},SL=xL,xd=null,Sd=!1;function CL(e){if(Sd)return;xd===null&&(Sd=!0,xd=new Set(Object.keys(q0({}))),Sd=!1);const t=Object.keys(e).filter(r=>xd.has(r));t.length>0&&console.error(`[Inertia] useForm() data contains field(s) that conflict with form properties: ${t.map(r=>`"${r}"`).join(", ")}. These fields will be overwritten by form methods/properties. Please rename these fields.`)}function q0(...e){let{rememberKey:t,data:r,precognitionEndpoint:n}=wd.parseUseFormArguments(...e);const i=t?at.restore(t):null;let s=ht(typeof r=="function"?r():r);CL(s);let o=null,a,l=h=>h,u=null,c=[],d=!1;const m=Ts({...i?i.data:ht(s),isDirty:!1,errors:i?i.errors:{},hasErrors:!1,processing:!1,progress:null,wasSuccessful:!1,recentlySuccessful:!1,withPrecognition(...h){n=wd.createWayfinderCallback(...h);const f=this;let b=!1;const y=_L(v=>{const{method:_,url:x}=n(),k=ht(l(this.data()));return v[_](x,k)},ht(s));u=y,y.on("validatingChanged",()=>{f.validating=y.validating()}).on("validatedChanged",()=>{f.__valid=y.valid()}).on("touchedChanged",()=>{f.__touched=y.touched()}).on("errorsChanged",()=>{const v=b?y.errors():wL(y.errors());this.errors={},this.setError(v),f.__valid=y.valid()});const w=(v,_)=>(_(v),v);return Object.assign(f,{__touched:[],__valid:[],validating:!1,validator:()=>y,withAllErrors:()=>w(f,()=>b=!0),valid:v=>f.__valid.includes(v),invalid:v=>v in this.errors,setValidationTimeout:v=>w(f,()=>y.setTimeout(v)),validateFiles:()=>w(f,()=>y.validateFiles()),withoutFileValidation:()=>w(f,()=>y.withoutFileValidation()),touch:(v,..._)=>(Array.isArray(v)?y.touch(v):typeof v=="string"?y.touch([v,..._]):y.touch(v),f),touched:v=>typeof v=="string"?f.__touched.includes(v):f.__touched.length>0,validate:(v,_)=>{if(typeof v=="object"&&!("target"in v)&&(_=v,v=void 0),v===void 0)y.validate(_);else{const x=So(v),k=l(this.data());y.validate(x,Ar(k,x),_)}return f},setErrors:v=>w(f,()=>this.setError(v)),forgetError:v=>w(f,()=>this.clearErrors(So(v)))}),f},data(){return Object.keys(s).reduce((h,f)=>qr(h,f,Ar(this,f)),{})},transform(h){return l=h,this},defaults(h,f){if(typeof r=="function")throw new Error("You cannot call `defaults()` when using a function to define your form data.");return d=!0,typeof h>"u"?(s=ht(this.data()),this.isDirty=!1):s=typeof h=="string"?qr(ht(s),h,f):Object.assign({},ht(s),h),u?.defaults(s),this},reset(...h){const f=ht(typeof r=="function"?r():s),b=ht(f);return h.length===0?(s=b,Object.assign(this,f)):h.filter(y=>d0(b,y)).forEach(y=>{qr(s,y,Ar(b,y)),qr(this,y,Ar(f,y))}),u?.reset(...h),this},setError(h,f){const b=typeof h=="string"?{[h]:f}:h;return Object.assign(this.errors,b),this.hasErrors=Object.keys(this.errors).length>0,u?.setErrors(b),this},clearErrors(...h){return this.errors=Object.keys(this.errors).reduce((f,b)=>({...f,...h.length>0&&!h.includes(b)?{[b]:this.errors[b]}:{}}),{}),this.hasErrors=Object.keys(this.errors).length>0,u&&(h.length===0?u.setErrors({}):h.forEach(u.forgetError)),this},resetAndClearErrors(...h){return this.reset(...h),this.clearErrors(...h),this},submit(...h){const{method:f,url:b,options:y}=wd.parseSubmitArguments(h,n);d=!1;const w={...y,onCancelToken:_=>{if(o=_,y.onCancelToken)return y.onCancelToken(_)},onBefore:_=>{if(this.wasSuccessful=!1,this.recentlySuccessful=!1,clearTimeout(a),y.onBefore)return y.onBefore(_)},onStart:_=>{if(this.processing=!0,y.onStart)return y.onStart(_)},onProgress:_=>{if(this.progress=_??null,y.onProgress)return y.onProgress(_)},onSuccess:async _=>{this.processing=!1,this.progress=null,this.clearErrors(),this.wasSuccessful=!0,this.recentlySuccessful=!0,a=setTimeout(()=>this.recentlySuccessful=!1,Xo.get("form.recentlySuccessfulDuration"));const x=y.onSuccess?await y.onSuccess(_):null;return d||(s=ht(this.data()),this.isDirty=!1),x},onError:_=>{if(this.processing=!1,this.progress=null,this.clearErrors().setError(_),y.onError)return y.onError(_)},onCancel:()=>{if(this.processing=!1,this.progress=null,y.onCancel)return y.onCancel()},onFinish:_=>{if(this.processing=!1,this.progress=null,o=null,y.onFinish)return y.onFinish(_)}},v=l(this.data());f==="delete"?at.delete(b,{...w,data:v}):at[f](b,v,w)},get(h,f){this.submit("get",h,f)},post(h,f){this.submit("post",h,f)},put(h,f){this.submit("put",h,f)},patch(h,f){this.submit("patch",h,f)},delete(h,f){this.submit("delete",h,f)},cancel(){o&&o.cancel()},dontRemember(...h){return c=h,this},__rememberable:t===null,__remember(){const h=this.data();if(c.length>0){const f={...h};return c.forEach(b=>delete f[b]),{data:f,errors:this.errors}}return{data:h,errors:this.errors}},__restore(h){Object.assign(this,h.data),this.setError(h.errors)}});return Ei(m,h=>{m.isDirty=!ei(m.data(),s);const f=at.restore(t),b=ht(h.__remember());t&&!ei(f,b)&&at.remember(b,t)},{immediate:!0,deep:!0}),n?m.withPrecognition(n):m}var Kt=Hr(void 0),Je=Hr(),Cd=df(null),qa=Hr(void 0),Uh,AL=Mi({name:"Inertia",props:{initialPage:{type:Object,required:!0},initialComponent:{type:Object,required:!1},resolveComponent:{type:Function,required:!1},titleCallback:{type:Function,required:!1,default:e=>e},onHeadUpdate:{type:Function,required:!1,default:()=>()=>{}}},setup({initialPage:e,initialComponent:t,resolveComponent:r,titleCallback:n,onHeadUpdate:i}){Kt.value=t?El(t):void 0,Je.value={...e,flash:e.flash??{}},qa.value=void 0;const s=typeof window>"u";return Uh=Q2(s,n||(o=>o),i||(()=>{})),s||(at.init({initialPage:e,resolveComponent:r,swapComponent:async o=>{Kt.value=El(o.component),Je.value=o.page,qa.value=o.preserveState?qa.value:Date.now()},onFlash:o=>{Je.value={...Je.value,flash:o}}}),at.on("navigate",()=>Uh.forceUpdate())),()=>{if(Kt.value){Kt.value.inheritAttrs=!!Kt.value.inheritAttrs;const o=Wr(Kt.value,{...Je.value.props,key:qa.value});return Cd.value&&(Kt.value.layout=Cd.value,Cd.value=null),Kt.value.layout?typeof Kt.value.layout=="function"?Kt.value.layout(Wr,o):(Array.isArray(Kt.value.layout)?Kt.value.layout:[Kt.value.layout]).concat(o).reverse().reduce((a,l)=>(l.inheritAttrs=!!l.inheritAttrs,Wr(l,{...Je.value.props},()=>a))):o}}}}),Db=AL,Vb={install(e){at.form=q0,Object.defineProperty(e.config.globalProperties,"$inertia",{get:()=>at}),Object.defineProperty(e.config.globalProperties,"$page",{get:()=>Je.value}),Object.defineProperty(e.config.globalProperties,"$headManager",{get:()=>Uh}),e.mixin(SL)}};function GV(){return Ts({props:De(()=>Je.value?.props),url:De(()=>Je.value?.url),component:De(()=>Je.value?.component),version:De(()=>Je.value?.version),clearHistory:De(()=>Je.value?.clearHistory),deferredProps:De(()=>Je.value?.deferredProps),mergeProps:De(()=>Je.value?.mergeProps),prependProps:De(()=>Je.value?.prependProps),deepMergeProps:De(()=>Je.value?.deepMergeProps),matchPropsOn:De(()=>Je.value?.matchPropsOn),rememberedState:De(()=>Je.value?.rememberedState),encryptHistory:De(()=>Je.value?.encryptHistory),flash:De(()=>Je.value?.flash)})}async function TL({id:e="app",resolve:t,setup:r,title:n,progress:i={},page:s,render:o,defaults:a={}}){Xo.replace(a);const l=typeof window>"u",u=Xo.get("future.useScriptElementForInitialPage"),c=s||R2(e,u),d=h=>Promise.resolve(t(h)).then(f=>f.default||f);let p=[];const m=await Promise.all([d(c.component),at.decryptHistory().catch(()=>{})]).then(([h])=>{const f={initialPage:c,initialComponent:h,resolveComponent:d,titleCallback:n};return r(l?{el:null,App:Db,props:{...f,onHeadUpdate:w=>p=w},plugin:Vb}:{el:document.getElementById(e),App:Db,props:f,plugin:Vb})});if(!l&&i&&fL(i),l&&o){const h=()=>u?[Wr("script",{"data-page":e,type:"application/json",innerHTML:JSON.stringify(c).replace(/\//g,"\\/")}),Wr("div",{id:e,innerHTML:m?o(m):""})]:Wr("div",{id:e,"data-page":JSON.stringify(c),innerHTML:m?o(m):""}),f=await o($f({render:()=>h()}));return{head:p,body:f}}}var YV=Mi({name:"Deferred",props:{data:{type:[String,Array],required:!0}},render(){const e=Array.isArray(this.$props.data)?this.$props.data:[this.$props.data];if(!this.$slots.fallback)throw new Error("`<Deferred>` requires a `<template #fallback>` slot");return e.every(t=>this.$page.props[t]!==void 0)?this.$slots.default?.():this.$slots.fallback()}}),kL=Mi({props:{title:{type:String,required:!1}},data(){return{provider:this.$headManager.createProvider()}},beforeUnmount(){this.provider.disconnect()},methods:{isUnaryTag(e){return typeof e.type=="string"&&["area","base","br","col","embed","hr","img","input","keygen","link","meta","param","source","track","wbr"].indexOf(e.type)>-1},renderTagStart(e){e.props=e.props||{},e.props[this.provider.preferredAttribute()]=e.props["head-key"]!==void 0?e.props["head-key"]:"";const t=Object.keys(e.props).reduce((r,n)=>{const i=String(e.props[n]);return["key","head-key"].includes(n)?r:i===""?r+` ${n}`:r+` ${n}="${EI(i)}"`},"");return`<${String(e.type)}${t}>`},renderTagChildren(e){const{children:t}=e;return typeof t=="string"?t:Array.isArray(t)?t.reduce((r,n)=>r+this.renderTag(n),""):""},isFunctionNode(e){return typeof e.type=="function"},isComponentNode(e){return typeof e.type=="object"},isCommentNode(e){return/(comment|cmt)/i.test(e.type.toString())},isFragmentNode(e){return/(fragment|fgt|symbol\(\))/i.test(e.type.toString())},isTextNode(e){return/(text|txt)/i.test(e.type.toString())},renderTag(e){if(this.isTextNode(e))return String(e.children);if(this.isFragmentNode(e))return"";if(this.isCommentNode(e))return"";let t=this.renderTagStart(e);return e.children&&(t+=this.renderTagChildren(e)),this.isUnaryTag(e)||(t+=`</${String(e.type)}>`),t},addTitleElement(e){return this.title&&!e.find(t=>t.startsWith("<title"))&&e.push(`<title ${this.provider.preferredAttribute()}>${this.title}</title>`),e},renderNodes(e){const t=e.flatMap(r=>this.resolveNode(r)).map(r=>this.renderTag(r)).filter(r=>r);return this.addTitleElement(t)},resolveNode(e){return this.isFunctionNode(e)?this.resolveNode(e.type()):this.isComponentNode(e)?(console.warn("Using components in the <Head> component is not supported."),[]):this.isTextNode(e)&&e.children?e:this.isFragmentNode(e)&&e.children?e.children.flatMap(t=>this.resolveNode(t)):this.isCommentNode(e)?[]:e}},render(){this.provider.update(this.renderNodes(this.$slots.default?this.$slots.default():[]))}}),XV=kL,Mr=()=>{},OL=Mi({name:"Link",props:{as:{type:[String,Object],default:"a"},data:{type:Object,default:()=>({})},href:{type:[String,Object],default:""},method:{type:String,default:"get"},replace:{type:Boolean,default:!1},preserveScroll:{type:[Boolean,String,Function],default:!1},preserveState:{type:[Boolean,String,Function],default:null},preserveUrl:{type:Boolean,default:!1},only:{type:Array,default:()=>[]},except:{type:Array,default:()=>[]},headers:{type:Object,default:()=>({})},queryStringArrayFormat:{type:String,default:"brackets"},async:{type:Boolean,default:!1},prefetch:{type:[Boolean,String,Array],default:!1},cacheFor:{type:[Number,String,Array],default:0},onStart:{type:Function,default:Mr},onProgress:{type:Function,default:Mr},onFinish:{type:Function,default:Mr},onBefore:{type:Function,default:Mr},onCancel:{type:Function,default:Mr},onSuccess:{type:Function,default:Mr},onError:{type:Function,default:Mr},onCancelToken:{type:Function,default:Mr},onPrefetching:{type:Function,default:Mr},onPrefetched:{type:Function,default:Mr},cacheTags:{type:[String,Array],default:()=>[]},viewTransition:{type:[Boolean,Object],default:!1}},setup(e,{slots:t,attrs:r}){const n=Hr(0),i=Hr(),s=De(()=>e.prefetch===!0?["hover"]:e.prefetch===!1?[]:Array.isArray(e.prefetch)?e.prefetch:[e.prefetch]),o=De(()=>e.cacheFor!==0?e.cacheFor:s.value.length===1&&s.value[0]==="click"?0:Xo.get("prefetch.cacheFor"));Ns(()=>{s.value.includes("mount")&&f()}),sa(()=>{clearTimeout(i.value)});const a=De(()=>ws(e.href)?e.href.method:(e.method??"get").toLowerCase()),l=De(()=>typeof e.as!="string"||e.as.toLowerCase()!=="a"?e.as:a.value!=="get"?"button":e.as.toLowerCase()),u=De(()=>O0(a.value,ws(e.href)?e.href.url:e.href,e.data||{},e.queryStringArrayFormat)),c=De(()=>u.value[0]),d=De(()=>u.value[1]),p=De(()=>l.value==="button"?{type:"button"}:l.value==="a"||typeof l.value!="string"?{href:c.value}:{}),m=De(()=>({data:d.value,method:a.value,replace:e.replace,preserveScroll:e.preserveScroll,preserveState:e.preserveState??a.value!=="get",preserveUrl:e.preserveUrl,only:e.only,except:e.except,headers:e.headers,async:e.async})),h=De(()=>({...m.value,viewTransition:e.viewTransition,onCancelToken:e.onCancelToken,onBefore:e.onBefore,onStart:v=>{n.value++,e.onStart?.(v)},onProgress:e.onProgress,onFinish:v=>{n.value--,e.onFinish?.(v)},onCancel:e.onCancel,onSuccess:e.onSuccess,onError:e.onError})),f=()=>{at.prefetch(c.value,{...m.value,onPrefetching:e.onPrefetching,onPrefetched:e.onPrefetched},{cacheFor:o.value,cacheTags:e.cacheTags})},b={onClick:v=>{za(v)&&(v.preventDefault(),at.visit(c.value,h.value))}},y={onMouseenter:()=>{i.value=setTimeout(()=>{f()},Xo.get("prefetch.hoverDelay"))},onMouseleave:()=>{clearTimeout(i.value)},onClick:b.onClick},w={onMousedown:v=>{za(v)&&(v.preventDefault(),f())},onKeydown:v=>{Rb(v)&&(v.preventDefault(),f())},onMouseup:v=>{za(v)&&(v.preventDefault(),at.visit(c.value,h.value))},onKeyup:v=>{Rb(v)&&(v.preventDefault(),at.visit(c.value,h.value))},onClick:v=>{za(v)&&v.preventDefault()}};return()=>Wr(l.value,{...r,...p.value,"data-loading":n.value>0?"":void 0,...s.value.includes("hover")?y:s.value.includes("click")?w:b},t)}}),JV=OL,Xo=Ii.extend({});var zh="",qh="";function Bb(e){zh=e}function NL(e=""){if(!zh){const t=document.querySelector("[data-webawesome]");if(t?.hasAttribute("data-webawesome")){const r=new URL(t.getAttribute("data-webawesome")??"",window.location.href).pathname;Bb(r)}else{const n=[...document.getElementsByTagName("script")].find(i=>i.src.endsWith("webawesome.js")||i.src.endsWith("webawesome.loader.js")||i.src.endsWith("webawesome.ssr-loader.js"));if(n){const i=String(n.getAttribute("src"));Bb(i.split("/").slice(0,-1).join("/"))}}}return zh.replace(/\/$/,"")+(e?`/${e.replace(/^\//,"")}`:"")}function PL(e){qh=e}function FL(){if(!qh){const e=document.querySelector("[data-fa-kit-code]");e&&PL(e.getAttribute("data-fa-kit-code")||"")}return qh}var sn="7.0.1";function IL(e,t,r){const n=FL(),i=n.length>0;let s="solid";return t==="notdog"?(r==="solid"&&(s="solid"),r==="duo-solid"&&(s="duo-solid"),`https://ka-p.fontawesome.com/releases/v${sn}/svgs/notdog-${s}/${e}.svg?token=${encodeURIComponent(n)}`):t==="chisel"?`https://ka-p.fontawesome.com/releases/v${sn}/svgs/chisel-regular/${e}.svg?token=${encodeURIComponent(n)}`:t==="etch"?`https://ka-p.fontawesome.com/releases/v${sn}/svgs/etch-solid/${e}.svg?token=${encodeURIComponent(n)}`:t==="jelly"?(r==="regular"&&(s="regular"),r==="duo-regular"&&(s="duo-regular"),r==="fill-regular"&&(s="fill-regular"),`https://ka-p.fontawesome.com/releases/v${sn}/svgs/jelly-${s}/${e}.svg?token=${encodeURIComponent(n)}`):t==="slab"?((r==="solid"||r==="regular")&&(s="regular"),r==="press-regular"&&(s="press-regular"),`https://ka-p.fontawesome.com/releases/v${sn}/svgs/slab-${s}/${e}.svg?token=${encodeURIComponent(n)}`):t==="thumbprint"?`https://ka-p.fontawesome.com/releases/v${sn}/svgs/thumbprint-light/${e}.svg?token=${encodeURIComponent(n)}`:t==="whiteboard"?`https://ka-p.fontawesome.com/releases/v${sn}/svgs/whiteboard-semibold/${e}.svg?token=${encodeURIComponent(n)}`:(t==="classic"&&(r==="thin"&&(s="thin"),r==="light"&&(s="light"),r==="regular"&&(s="regular"),r==="solid"&&(s="solid")),t==="sharp"&&(r==="thin"&&(s="sharp-thin"),r==="light"&&(s="sharp-light"),r==="regular"&&(s="sharp-regular"),r==="solid"&&(s="sharp-solid")),t==="duotone"&&(r==="thin"&&(s="duotone-thin"),r==="light"&&(s="duotone-light"),r==="regular"&&(s="duotone-regular"),r==="solid"&&(s="duotone")),t==="sharp-duotone"&&(r==="thin"&&(s="sharp-duotone-thin"),r==="light"&&(s="sharp-duotone-light"),r==="regular"&&(s="sharp-duotone-regular"),r==="solid"&&(s="sharp-duotone-solid")),t==="brands"&&(s="brands"),i?`https://ka-p.fontawesome.com/releases/v${sn}/svgs/${s}/${e}.svg?token=${encodeURIComponent(n)}`:`https://ka-f.fontawesome.com/releases/v${sn}/svgs/${s}/${e}.svg`)}var LL={name:"default",resolver:(e,t="classic",r="solid")=>IL(e,t,r),mutator:(e,t)=>{if(t?.family&&!e.hasAttribute("data-duotone-initialized")){const{family:r,variant:n}=t;if(r==="duotone"||r==="sharp-duotone"||r==="notdog"&&n==="duo-solid"||r==="jelly"&&n==="duo-regular"||r==="thumbprint"){const i=[...e.querySelectorAll("path")],s=i.find(a=>!a.hasAttribute("opacity")),o=i.find(a=>a.hasAttribute("opacity"));if(!s||!o)return;if(s.setAttribute("data-duotone-primary",""),o.setAttribute("data-duotone-secondary",""),t.swapOpacity&&s&&o){const a=o.getAttribute("opacity")||"0.4";s.style.setProperty("--path-opacity",a),o.style.setProperty("--path-opacity","1")}e.setAttribute("data-duotone-initialized","")}}}},RL=LL;new MutationObserver(e=>{for(const{addedNodes:t}of e)for(const r of t)r.nodeType===Node.ELEMENT_NODE&&$L(r)});async function $L(e){const t=e instanceof Element?e.tagName.toLowerCase():"",r=t?.startsWith("wa-"),n=[...e.querySelectorAll(":not(:defined)")].map(o=>o.tagName.toLowerCase()).filter(o=>o.startsWith("wa-"));r&&!customElements.get(t)&&n.push(t);const i=[...new Set(n)],s=await Promise.allSettled(i.map(o=>ML(o)));for(const o of s)o.status==="rejected"&&console.warn(o.reason);await new Promise(requestAnimationFrame),e.dispatchEvent(new CustomEvent("wa-discovery-complete",{bubbles:!1,cancelable:!1,composed:!0}))}function ML(e){if(customElements.get(e))return Promise.resolve();const t=e.replace(/^wa-/i,""),r=NL(`components/${t}/${t}.js`);return new Promise((n,i)=>{import(r).then(()=>n()).catch(()=>i(new Error(`Unable to autoload <${e}> from ${r}`)))})}const Hh=new Set,rs=new Map;let mi,Cp="ltr",Ap="en";const H0=typeof MutationObserver<"u"&&typeof document<"u"&&typeof document.documentElement<"u";if(H0){const e=new MutationObserver(W0);Cp=document.documentElement.dir||"ltr",Ap=document.documentElement.lang||navigator.language,e.observe(document.documentElement,{attributes:!0,attributeFilter:["dir","lang"]})}function j0(...e){e.map(t=>{const r=t.$code.toLowerCase();rs.has(r)?rs.set(r,Object.assign(Object.assign({},rs.get(r)),t)):rs.set(r,t),mi||(mi=t)}),W0()}function W0(){H0&&(Cp=document.documentElement.dir||"ltr",Ap=document.documentElement.lang||navigator.language),[...Hh.keys()].map(e=>{typeof e.requestUpdate=="function"&&e.requestUpdate()})}let DL=class{constructor(t){this.host=t,this.host.addController(this)}hostConnected(){Hh.add(this.host)}hostDisconnected(){Hh.delete(this.host)}dir(){return`${this.host.dir||Cp}`.toLowerCase()}lang(){return`${this.host.lang||Ap}`.toLowerCase()}getTranslationData(t){var r,n;const i=new Intl.Locale(t.replace(/_/g,"-")),s=i?.language.toLowerCase(),o=(n=(r=i?.region)===null||r===void 0?void 0:r.toLowerCase())!==null&&n!==void 0?n:"",a=rs.get(`${s}-${o}`),l=rs.get(s);return{locale:i,language:s,region:o,primary:a,secondary:l}}exists(t,r){var n;const{primary:i,secondary:s}=this.getTranslationData((n=r.lang)!==null&&n!==void 0?n:this.lang());return r=Object.assign({includeFallback:!1},r),!!(i&&i[t]||s&&s[t]||r.includeFallback&&mi&&mi[t])}term(t,...r){const{primary:n,secondary:i}=this.getTranslationData(this.lang());let s;if(n&&n[t])s=n[t];else if(i&&i[t])s=i[t];else if(mi&&mi[t])s=mi[t];else return console.error(`No translation found for: ${String(t)}`),String(t);return typeof s=="function"?s(...r):s}date(t,r){return t=new Date(t),new Intl.DateTimeFormat(this.lang(),r).format(t)}number(t,r){return t=Number(t),isNaN(t)?"":new Intl.NumberFormat(this.lang(),r).format(t)}relativeTime(t,r,n){return new Intl.RelativeTimeFormat(this.lang(),n).format(t,r)}};var K0={$code:"en",$name:"English",$dir:"ltr",carousel:"Carousel",clearEntry:"Clear entry",close:"Close",copied:"Copied",copy:"Copy",currentValue:"Current value",error:"Error",goToSlide:(e,t)=>`Go to slide ${e} of ${t}`,hidePassword:"Hide password",loading:"Loading",nextSlide:"Next slide",numOptionsSelected:e=>e===0?"No options selected":e===1?"1 option selected":`${e} options selected`,pauseAnimation:"Pause animation",playAnimation:"Play animation",previousSlide:"Previous slide",progress:"Progress",remove:"Remove",resize:"Resize",scrollableRegion:"Scrollable region",scrollToEnd:"Scroll to end",scrollToStart:"Scroll to start",selectAColorFromTheScreen:"Select a color from the screen",showPassword:"Show password",slideNum:e=>`Slide ${e}`,toggleColorFormat:"Toggle color format",zoomIn:"Zoom in",zoomOut:"Zoom out"};j0(K0);var VL=K0;var Is=class extends DL{};j0(VL);function BL(e){return`data:image/svg+xml,${encodeURIComponent(e)}`}var Ad={solid:{check:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M434.8 70.1c14.3 10.4 17.5 30.4 7.1 44.7l-256 352c-5.5 7.6-14 12.3-23.4 13.1s-18.5-2.7-25.1-9.3l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l101.5 101.5 234-321.7c10.4-14.3 30.4-17.5 44.7-7.1z"/></svg>',"chevron-down":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M201.4 406.6c12.5 12.5 32.8 12.5 45.3 0l192-192c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L224 338.7 54.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l192 192z"/></svg>',"chevron-left":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l192 192c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L77.3 256 246.6 86.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-192 192z"/></svg>',"chevron-right":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M311.1 233.4c12.5 12.5 12.5 32.8 0 45.3l-192 192c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L243.2 256 73.9 86.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l192 192z"/></svg>',circle:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0z"/></svg>',eyedropper:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M341.6 29.2l-101.6 101.6-9.4-9.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3l-9.4-9.4 101.6-101.6c39-39 39-102.2 0-141.1s-102.2-39-141.1 0zM55.4 323.3c-15 15-23.4 35.4-23.4 56.6l0 42.4-26.6 39.9c-8.5 12.7-6.8 29.6 4 40.4s27.7 12.5 40.4 4l39.9-26.6 42.4 0c21.2 0 41.6-8.4 56.6-23.4l109.4-109.4-45.3-45.3-109.4 109.4c-3 3-7.1 4.7-11.3 4.7l-36.1 0 0-36.1c0-4.2 1.7-8.3 4.7-11.3l109.4-109.4-45.3-45.3-109.4 109.4z"/></svg>',"grip-vertical":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M128 40c0-22.1-17.9-40-40-40L40 0C17.9 0 0 17.9 0 40L0 88c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zm0 192c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zM0 424l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zM320 40c0-22.1-17.9-40-40-40L232 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zM192 232l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zM320 424c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48z"/></svg>',indeterminate:'<svg part="indeterminate-icon" class="icon" viewBox="0 0 16 16"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" stroke-linecap="round"><g stroke="currentColor" stroke-width="2"><g transform="translate(2.285714 6.857143)"><path d="M10.2857143,1.14285714 L1.14285714,1.14285714"/></g></g></g></svg>',minus:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M0 256c0-17.7 14.3-32 32-32l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 288c-17.7 0-32-14.3-32-32z"/></svg>',pause:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M48 32C21.5 32 0 53.5 0 80L0 432c0 26.5 21.5 48 48 48l64 0c26.5 0 48-21.5 48-48l0-352c0-26.5-21.5-48-48-48L48 32zm224 0c-26.5 0-48 21.5-48 48l0 352c0 26.5 21.5 48 48 48l64 0c26.5 0 48-21.5 48-48l0-352c0-26.5-21.5-48-48-48l-64 0z"/></svg>',play:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M91.2 36.9c-12.4-6.8-27.4-6.5-39.6 .7S32 57.9 32 72l0 368c0 14.1 7.5 27.2 19.6 34.4s27.2 7.5 39.6 .7l336-184c12.8-7 20.8-20.5 20.8-35.1s-8-28.1-20.8-35.1l-336-184z"/></svg>',star:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M309.5-18.9c-4.1-8-12.4-13.1-21.4-13.1s-17.3 5.1-21.4 13.1L193.1 125.3 33.2 150.7c-8.9 1.4-16.3 7.7-19.1 16.3s-.5 18 5.8 24.4l114.4 114.5-25.2 159.9c-1.4 8.9 2.3 17.9 9.6 23.2s16.9 6.1 25 2L288.1 417.6 432.4 491c8 4.1 17.7 3.3 25-2s11-14.2 9.6-23.2L441.7 305.9 556.1 191.4c6.4-6.4 8.6-15.8 5.8-24.4s-10.1-14.9-19.1-16.3L383 125.3 309.5-18.9z"/></svg>',user:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M224 248a120 120 0 1 0 0-240 120 120 0 1 0 0 240zm-29.7 56C95.8 304 16 383.8 16 482.3 16 498.7 29.3 512 45.7 512l356.6 0c16.4 0 29.7-13.3 29.7-29.7 0-98.5-79.8-178.3-178.3-178.3l-59.4 0z"/></svg>',xmark:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M55.1 73.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L147.2 256 9.9 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192.5 301.3 329.9 438.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.8 256 375.1 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192.5 210.7 55.1 73.4z"/></svg>'},regular:{"circle-question":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M464 256a208 208 0 1 0 -416 0 208 208 0 1 0 416 0zM0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0zm256-80c-17.7 0-32 14.3-32 32 0 13.3-10.7 24-24 24s-24-10.7-24-24c0-44.2 35.8-80 80-80s80 35.8 80 80c0 47.2-36 67.2-56 74.5l0 3.8c0 13.3-10.7 24-24 24s-24-10.7-24-24l0-8.1c0-20.5 14.8-35.2 30.1-40.2 6.4-2.1 13.2-5.5 18.2-10.3 4.3-4.2 7.7-10 7.7-19.6 0-17.7-14.3-32-32-32zM224 368a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>',"circle-xmark":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M256 48a208 208 0 1 1 0 416 208 208 0 1 1 0-416zm0 464a256 256 0 1 0 0-512 256 256 0 1 0 0 512zM167 167c-9.4 9.4-9.4 24.6 0 33.9l55 55-55 55c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l55-55 55 55c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-55-55 55-55c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-55 55-55-55c-9.4-9.4-24.6-9.4-33.9 0z"/></svg>',copy:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M384 336l-192 0c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l133.5 0c4.2 0 8.3 1.7 11.3 4.7l58.5 58.5c3 3 4.7 7.1 4.7 11.3L400 320c0 8.8-7.2 16-16 16zM192 384l192 0c35.3 0 64-28.7 64-64l0-197.5c0-17-6.7-33.3-18.7-45.3L370.7 18.7C358.7 6.7 342.5 0 325.5 0L192 0c-35.3 0-64 28.7-64 64l0 256c0 35.3 28.7 64 64 64zM64 128c-35.3 0-64 28.7-64 64L0 448c0 35.3 28.7 64 64 64l192 0c35.3 0 64-28.7 64-64l0-16-48 0 0 16c0 8.8-7.2 16-16 16L64 464c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l16 0 0-48-16 0z"/></svg>',eye:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M288 80C222.8 80 169.2 109.6 128.1 147.7 89.6 183.5 63 226 49.4 256 63 286 89.6 328.5 128.1 364.3 169.2 402.4 222.8 432 288 432s118.8-29.6 159.9-67.7C486.4 328.5 513 286 526.6 256 513 226 486.4 183.5 447.9 147.7 406.8 109.6 353.2 80 288 80zM95.4 112.6C142.5 68.8 207.2 32 288 32s145.5 36.8 192.6 80.6c46.8 43.5 78.1 95.4 93 131.1 3.3 7.9 3.3 16.7 0 24.6-14.9 35.7-46.2 87.7-93 131.1-47.1 43.7-111.8 80.6-192.6 80.6S142.5 443.2 95.4 399.4c-46.8-43.5-78.1-95.4-93-131.1-3.3-7.9-3.3-16.7 0-24.6 14.9-35.7 46.2-87.7 93-131.1zM288 336c44.2 0 80-35.8 80-80 0-29.6-16.1-55.5-40-69.3-1.4 59.7-49.6 107.9-109.3 109.3 13.8 23.9 39.7 40 69.3 40zm-79.6-88.4c2.5 .3 5 .4 7.6 .4 35.3 0 64-28.7 64-64 0-2.6-.2-5.1-.4-7.6-37.4 3.9-67.2 33.7-71.1 71.1zm45.6-115c10.8-3 22.2-4.5 33.9-4.5 8.8 0 17.5 .9 25.8 2.6 .3 .1 .5 .1 .8 .2 57.9 12.2 101.4 63.7 101.4 125.2 0 70.7-57.3 128-128 128-61.6 0-113-43.5-125.2-101.4-1.8-8.6-2.8-17.5-2.8-26.6 0-11 1.4-21.8 4-32 .2-.7 .3-1.3 .5-1.9 11.9-43.4 46.1-77.6 89.5-89.5z"/></svg>',"eye-slash":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M41-24.9c-9.4-9.4-24.6-9.4-33.9 0S-2.3-.3 7 9.1l528 528c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-96.4-96.4c2.7-2.4 5.4-4.8 8-7.2 46.8-43.5 78.1-95.4 93-131.1 3.3-7.9 3.3-16.7 0-24.6-14.9-35.7-46.2-87.7-93-131.1-47.1-43.7-111.8-80.6-192.6-80.6-56.8 0-105.6 18.2-146 44.2L41-24.9zM176.9 111.1c32.1-18.9 69.2-31.1 111.1-31.1 65.2 0 118.8 29.6 159.9 67.7 38.5 35.7 65.1 78.3 78.6 108.3-13.6 30-40.2 72.5-78.6 108.3-3.1 2.8-6.2 5.6-9.4 8.4L393.8 328c14-20.5 22.2-45.3 22.2-72 0-70.7-57.3-128-128-128-26.7 0-51.5 8.2-72 22.2l-39.1-39.1zm182 182l-108-108c11.1-5.8 23.7-9.1 37.1-9.1 44.2 0 80 35.8 80 80 0 13.4-3.3 26-9.1 37.1zM103.4 173.2l-34-34c-32.6 36.8-55 75.8-66.9 104.5-3.3 7.9-3.3 16.7 0 24.6 14.9 35.7 46.2 87.7 93 131.1 47.1 43.7 111.8 80.6 192.6 80.6 37.3 0 71.2-7.9 101.5-20.6L352.2 422c-20 6.4-41.4 10-64.2 10-65.2 0-118.8-29.6-159.9-67.7-38.5-35.7-65.1-78.3-78.6-108.3 10.4-23.1 28.6-53.6 54-82.8z"/></svg>',star:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M288.1-32c9 0 17.3 5.1 21.4 13.1L383 125.3 542.9 150.7c8.9 1.4 16.3 7.7 19.1 16.3s.5 18-5.8 24.4L441.7 305.9 467 465.8c1.4 8.9-2.3 17.9-9.6 23.2s-17 6.1-25 2L288.1 417.6 143.8 491c-8 4.1-17.7 3.3-25-2s-11-14.2-9.6-23.2L134.4 305.9 20 191.4c-6.4-6.4-8.6-15.8-5.8-24.4s10.1-14.9 19.1-16.3l159.9-25.4 73.6-144.2c4.1-8 12.4-13.1 21.4-13.1zm0 76.8L230.3 158c-3.5 6.8-10 11.6-17.6 12.8l-125.5 20 89.8 89.9c5.4 5.4 7.9 13.1 6.7 20.7l-19.8 125.5 113.3-57.6c6.8-3.5 14.9-3.5 21.8 0l113.3 57.6-19.8-125.5c-1.2-7.6 1.3-15.3 6.7-20.7l89.8-89.9-125.5-20c-7.6-1.2-14.1-6-17.6-12.8L288.1 44.8z"/></svg>'}},UL={name:"system",resolver:(e,t="classic",r="solid")=>{let i=Ad[r][e]??Ad.regular[e]??Ad.regular["circle-question"];return i?BL(i):""}},zL=UL;var qL="classic",Zl=[RL,zL],ec=[];function HL(e){ec.push(e)}function jL(e){ec=ec.filter(t=>t!==e)}function Td(e){return Zl.find(t=>t.name===e)}function WL(e,t){KL(e),Zl.push({name:e,resolver:t.resolver,mutator:t.mutator,spriteSheet:t.spriteSheet}),ec.forEach(r=>{r.library===e&&r.setIcon()})}function KL(e){Zl=Zl.filter(t=>t.name!==e)}function GL(){return qL}var YL=Object.defineProperty,XL=Object.getOwnPropertyDescriptor,G0=e=>{throw TypeError(e)},D=(e,t,r,n)=>{for(var i=n>1?void 0:n?XL(t,r):t,s=e.length-1,o;s>=0;s--)(o=e[s])&&(i=(n?o(t,r,i):o(i))||i);return n&&i&&YL(t,r,i),i},Y0=(e,t,r)=>t.has(e)||G0("Cannot "+r),JL=(e,t,r)=>(Y0(e,t,"read from private field"),t.get(e)),QL=(e,t,r)=>t.has(e)?G0("Cannot add the same private member more than once"):t instanceof WeakSet?t.add(e):t.set(e,r),ZL=(e,t,r,n)=>(Y0(e,t,"write to private field"),t.set(e,r),r);const eR={alert:"triangle-exclamation",asc:"arrow-down-short-wide",asset:"image",assets:"image",circleuarr:"circle-arrow-up",collapse:"down-left-and-up-right-to-center",condition:"diamond",darr:"arrow-down",date:"calendar",desc:"arrow-down-wide-short",disabled:"circle-dashed",done:"circle-check",downangle:"angle-down",draft:"scribble",edit:"pencil",enabled:"circle",expand:"up-right-and-down-left-from-center",external:"arrow-up-right-from-square",field:"pen-to-square",help:"circle-question",home:"house",info:"circle-info",insecure:"unlock",larr:"arrow-left",layout:"table-layout",leftangle:"angle-left",listrtl:"list-flip",location:"location-dot",mail:"envelope",menu:"bars",move:"grip-dots",newstamp:"certificate",paperplane:"paper-plane",plugin:"plug",rarr:"arrow-right",refresh:"arrows-rotate",remove:"xmark",rightangle:"angle-right",rotate:"rotate-left",routes:"signs-post",search:"magnifying-glass",secure:"lock",settings:"gear",shareleft:"share-flip",shuteye:"eye-slash","sidebar-left":"sidebar","sidebar-right":"sidebar-flip","sidebar-start":"sidebar","sidebar-end":"sidebar-flip",structure:"list-tree",structurertl:"list-tree-flip",template:"file-code",time:"clock",tool:"wrench",uarr:"arrow-up",upangle:"angle-up",view:"eye",wand:"wand-magic-sparkles"};function tR(e,t="classic",r="regular"){let n="solid",i=r,s=e.endsWith(".svg")?e.split(".svg")[0]:e;if(e.includes("/")){let[o,...a]=e.split("/");i=o??i,s=a.join("/")}return i==="thin"?n="thin":i==="light"?n="light":i==="regular"?n="regular":i==="solid"&&(n="solid"),t==="brands"&&(n="brands"),i==="custom-icons"&&(n="custom-icons"),s=eR[s]??s,`/vendor/craft/icons/${n}/${s}.svg`}function rR(){WL("default",{resolver:(e,t="classic",r="solid")=>tR(e,t,r),mutator:e=>e.setAttribute("fill","currentColor")})}var Ub=class extends HTMLElement{constructor(...t){super(...t),this.cookieName=null,this.state="collapsed",this.expanded=!1,this.handleOpen=()=>{this.trigger?.setAttribute("aria-expanded","true"),this.expanded=!0,this.dispatchEvent(new CustomEvent("open")),this.target&&(this.target.dataset.state="expanded"),this.cookieName&&window.Craft?.setCookie(this.cookieName,"expanded")},this.handleClose=()=>{this.trigger?.setAttribute("aria-expanded","false"),this.expanded=!1,this.dispatchEvent(new CustomEvent("close")),this.target&&(this.target.dataset.state="collapsed"),this.cookieName&&window.Craft?.setCookie(this.cookieName,"collapsed")}}get trigger(){return this.querySelector('button[type="button"]')}get target(){if(!this.trigger)return console.warn("No trigger found for disclosure."),null;let t=this.trigger.getAttribute("aria-controls");return t?document.getElementById(t):(console.warn("No target selector found for disclosure."),null)}connectedCallback(){if(!this.trigger){console.error("craft-disclosure elements must include a button",this);return}if(!this.target){console.error(`No target with id ${this.trigger.getAttribute("aria-controls")} found for disclosure. `,this.trigger);return}this.cookieName=this.getAttribute("cookie-name"),this.state=this.getAttribute("state")??"expanded",this.trigger.setAttribute("aria-expanded",this.state==="expanded"?"true":"false"),this.trigger.addEventListener("click",this.toggle.bind(this)),this.state==="expanded"?this.open():this.close()}disconnectedCallback(){this.open(),this.trigger?.removeEventListener("click",this.toggle.bind(this))}attributeChangedCallback(t,r,n){t==="state"&&(n==="expanded"?this.handleOpen():this.handleClose())}toggle(){this.expanded?this.close():this.open()}open(){this.setAttribute("state","expanded")}close(){this.setAttribute("state","collapsed")}};Ub.observedAttributes=["state"],customElements.get("craft-disclosure")||customElements.define("craft-disclosure",Ub);function hl(e){return(t,r)=>{const{slot:n,selector:i}=e??{},s="slot"+(n?`[name=${n}]`:":not([name])");return ZE(t,r,{get(){const o=this.renderRoot?.querySelector(s),a=o?.assignedElements(e)??[];return i===void 0?a:a.filter((l=>l.matches(i)))}})}}var nR=te`
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
`,Ha=class extends Ne{constructor(...t){super(...t),this.visible=!1,this.wrapper=null}show(){this.visible=!0,this.dispatchEvent(new CustomEvent("show"))}hide(){this.visible=!1,this.dispatchEvent(new CustomEvent("hide"))}focus(){this.wrapper?.focus()}render(){return W`
      <div
        tabindex="-1"
        class="${tr({wrapper:!0,hidden:!this.visible})}"
      >
        <div class="spinner"></div>
        <span class="message visually-hidden"><slot /></span>
      </div>
    `}};Ha.styles=[nR],se([V({reflect:!0})],Ha.prototype,"visible",void 0),se([Ue(".wrapper")],Ha.prototype,"wrapper",void 0),customElements.get("craft-spinner")||customElements.define("craft-spinner",Ha);var iR=class extends Event{constructor(){super("wa-reposition",{bubbles:!0,cancelable:!1,composed:!0})}};var sR=`:host {
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
`,fl,qt=class extends Ne{constructor(){super(),QL(this,fl,!1),this.initialReflectedProperties=new Map,this.didSSR=!!this.shadowRoot,this.customStates={set:(t,r)=>{if(this.internals?.states)try{r?this.internals.states.add(t):this.internals.states.delete(t)}catch(n){if(String(n).includes("must start with '--'"))console.error("Your browser implements an outdated version of CustomStateSet. Consider using a polyfill");else throw n}},has:t=>{if(!this.internals?.states)return!1;try{return this.internals.states.has(t)}catch{return!1}}};try{this.internals=this.attachInternals()}catch{console.error("Element internals are not supported in your browser. Consider using a polyfill")}this.customStates.set("wa-defined",!0);let e=this.constructor;for(let[t,r]of e.elementProperties)r.default==="inherit"&&r.initial!==void 0&&typeof t=="string"&&this.customStates.set(`initial-${t}-${r.initial}`,!0)}static get styles(){const e=Array.isArray(this.css)?this.css:this.css?[this.css]:[];return[sR,...e].map(t=>typeof t=="string"?JE(t):t)}attributeChangedCallback(e,t,r){JL(this,fl)||(this.constructor.elementProperties.forEach((n,i)=>{n.reflect&&this[i]!=null&&this.initialReflectedProperties.set(i,this[i])}),ZL(this,fl,!0)),super.attributeChangedCallback(e,t,r)}willUpdate(e){super.willUpdate(e),this.initialReflectedProperties.forEach((t,r)=>{e.has(r)&&this[r]==null&&(this[r]=t)})}firstUpdated(e){super.firstUpdated(e),this.didSSR&&this.shadowRoot?.querySelectorAll("slot").forEach(t=>{t.dispatchEvent(new Event("slotchange",{bubbles:!0,composed:!1,cancelable:!1}))})}update(e){try{super.update(e)}catch(t){if(this.didSSR&&!this.hasUpdated){const r=new Event("lit-hydration-error",{bubbles:!0,composed:!0,cancelable:!1});r.error=t,this.dispatchEvent(r)}throw t}}relayNativeEvent(e,t){e.stopImmediatePropagation(),this.dispatchEvent(new e.constructor(e.type,{...e,...t}))}};fl=new WeakMap;D([V()],qt.prototype,"dir",2);D([V()],qt.prototype,"lang",2);D([V({type:Boolean,reflect:!0,attribute:"did-ssr"})],qt.prototype,"didSSR",2);const ii=Math.min,Xt=Math.max,tc=Math.round,ja=Math.floor,Gr=e=>({x:e,y:e}),oR={left:"right",right:"left",bottom:"top",top:"bottom"},aR={start:"end",end:"start"};function jh(e,t,r){return Xt(e,ii(t,r))}function Ls(e,t){return typeof e=="function"?e(t):e}function si(e){return e.split("-")[0]}function Rs(e){return e.split("-")[1]}function X0(e){return e==="x"?"y":"x"}function Tp(e){return e==="y"?"height":"width"}const lR=new Set(["top","bottom"]);function pn(e){return lR.has(si(e))?"y":"x"}function kp(e){return X0(pn(e))}function cR(e,t,r){r===void 0&&(r=!1);const n=Rs(e),i=kp(e),s=Tp(i);let o=i==="x"?n===(r?"end":"start")?"right":"left":n==="start"?"bottom":"top";return t.reference[s]>t.floating[s]&&(o=rc(o)),[o,rc(o)]}function uR(e){const t=rc(e);return[Wh(e),t,Wh(t)]}function Wh(e){return e.replace(/start|end/g,t=>aR[t])}const zb=["left","right"],qb=["right","left"],dR=["top","bottom"],hR=["bottom","top"];function fR(e,t,r){switch(e){case"top":case"bottom":return r?t?qb:zb:t?zb:qb;case"left":case"right":return t?dR:hR;default:return[]}}function pR(e,t,r,n){const i=Rs(e);let s=fR(si(e),r==="start",n);return i&&(s=s.map(o=>o+"-"+i),t&&(s=s.concat(s.map(Wh)))),s}function rc(e){return e.replace(/left|right|bottom|top/g,t=>oR[t])}function mR(e){return{top:0,right:0,bottom:0,left:0,...e}}function J0(e){return typeof e!="number"?mR(e):{top:e,right:e,bottom:e,left:e}}function nc(e){const{x:t,y:r,width:n,height:i}=e;return{width:n,height:i,top:r,left:t,right:t+n,bottom:r+i,x:t,y:r}}function Hb(e,t,r){let{reference:n,floating:i}=e;const s=pn(t),o=kp(t),a=Tp(o),l=si(t),u=s==="y",c=n.x+n.width/2-i.width/2,d=n.y+n.height/2-i.height/2,p=n[a]/2-i[a]/2;let m;switch(l){case"top":m={x:c,y:n.y-i.height};break;case"bottom":m={x:c,y:n.y+n.height};break;case"right":m={x:n.x+n.width,y:d};break;case"left":m={x:n.x-i.width,y:d};break;default:m={x:n.x,y:n.y}}switch(Rs(t)){case"start":m[o]-=p*(r&&u?-1:1);break;case"end":m[o]+=p*(r&&u?-1:1);break}return m}const gR=async(e,t,r)=>{const{placement:n="bottom",strategy:i="absolute",middleware:s=[],platform:o}=r,a=s.filter(Boolean),l=await(o.isRTL==null?void 0:o.isRTL(t));let u=await o.getElementRects({reference:e,floating:t,strategy:i}),{x:c,y:d}=Hb(u,n,l),p=n,m={},h=0;for(let f=0;f<a.length;f++){const{name:b,fn:y}=a[f],{x:w,y:v,data:_,reset:x}=await y({x:c,y:d,initialPlacement:n,placement:p,strategy:i,middlewareData:m,rects:u,platform:o,elements:{reference:e,floating:t}});c=w??c,d=v??d,m={...m,[b]:{...m[b],..._}},x&&h<=50&&(h++,typeof x=="object"&&(x.placement&&(p=x.placement),x.rects&&(u=x.rects===!0?await o.getElementRects({reference:e,floating:t,strategy:i}):x.rects),{x:c,y:d}=Hb(u,p,l)),f=-1)}return{x:c,y:d,placement:p,strategy:i,middlewareData:m}};async function Op(e,t){var r;t===void 0&&(t={});const{x:n,y:i,platform:s,rects:o,elements:a,strategy:l}=e,{boundary:u="clippingAncestors",rootBoundary:c="viewport",elementContext:d="floating",altBoundary:p=!1,padding:m=0}=Ls(t,e),h=J0(m),b=a[p?d==="floating"?"reference":"floating":d],y=nc(await s.getClippingRect({element:(r=await(s.isElement==null?void 0:s.isElement(b)))==null||r?b:b.contextElement||await(s.getDocumentElement==null?void 0:s.getDocumentElement(a.floating)),boundary:u,rootBoundary:c,strategy:l})),w=d==="floating"?{x:n,y:i,width:o.floating.width,height:o.floating.height}:o.reference,v=await(s.getOffsetParent==null?void 0:s.getOffsetParent(a.floating)),_=await(s.isElement==null?void 0:s.isElement(v))?await(s.getScale==null?void 0:s.getScale(v))||{x:1,y:1}:{x:1,y:1},x=nc(s.convertOffsetParentRelativeRectToViewportRelativeRect?await s.convertOffsetParentRelativeRectToViewportRelativeRect({elements:a,rect:w,offsetParent:v,strategy:l}):w);return{top:(y.top-x.top+h.top)/_.y,bottom:(x.bottom-y.bottom+h.bottom)/_.y,left:(y.left-x.left+h.left)/_.x,right:(x.right-y.right+h.right)/_.x}}const bR=e=>({name:"arrow",options:e,async fn(t){const{x:r,y:n,placement:i,rects:s,platform:o,elements:a,middlewareData:l}=t,{element:u,padding:c=0}=Ls(e,t)||{};if(u==null)return{};const d=J0(c),p={x:r,y:n},m=kp(i),h=Tp(m),f=await o.getDimensions(u),b=m==="y",y=b?"top":"left",w=b?"bottom":"right",v=b?"clientHeight":"clientWidth",_=s.reference[h]+s.reference[m]-p[m]-s.floating[h],x=p[m]-s.reference[m],k=await(o.getOffsetParent==null?void 0:o.getOffsetParent(u));let S=k?k[v]:0;(!S||!await(o.isElement==null?void 0:o.isElement(k)))&&(S=a.floating[v]||s.floating[h]);const N=_/2-x/2,A=S/2-f[h]/2-1,T=ii(d[y],A),F=ii(d[w],A),C=T,g=S-f[h]-F,L=S/2-f[h]/2+N,B=jh(C,L,g),$=!l.arrow&&Rs(i)!=null&&L!==B&&s.reference[h]/2-(L<C?T:F)-f[h]/2<0,z=$?L<C?L-C:L-g:0;return{[m]:p[m]+z,data:{[m]:B,centerOffset:L-B-z,...$&&{alignmentOffset:z}},reset:$}}}),vR=function(e){return e===void 0&&(e={}),{name:"flip",options:e,async fn(t){var r,n;const{placement:i,middlewareData:s,rects:o,initialPlacement:a,platform:l,elements:u}=t,{mainAxis:c=!0,crossAxis:d=!0,fallbackPlacements:p,fallbackStrategy:m="bestFit",fallbackAxisSideDirection:h="none",flipAlignment:f=!0,...b}=Ls(e,t);if((r=s.arrow)!=null&&r.alignmentOffset)return{};const y=si(i),w=pn(a),v=si(a)===a,_=await(l.isRTL==null?void 0:l.isRTL(u.floating)),x=p||(v||!f?[rc(a)]:uR(a)),k=h!=="none";!p&&k&&x.push(...pR(a,f,h,_));const S=[a,...x],N=await Op(t,b),A=[];let T=((n=s.flip)==null?void 0:n.overflows)||[];if(c&&A.push(N[y]),d){const L=cR(i,o,_);A.push(N[L[0]],N[L[1]])}if(T=[...T,{placement:i,overflows:A}],!A.every(L=>L<=0)){var F,C;const L=(((F=s.flip)==null?void 0:F.index)||0)+1,B=S[L];if(B&&(!(d==="alignment"?w!==pn(B):!1)||T.every(P=>pn(P.placement)===w?P.overflows[0]>0:!0)))return{data:{index:L,overflows:T},reset:{placement:B}};let $=(C=T.filter(z=>z.overflows[0]<=0).sort((z,P)=>z.overflows[1]-P.overflows[1])[0])==null?void 0:C.placement;if(!$)switch(m){case"bestFit":{var g;const z=(g=T.filter(P=>{if(k){const Z=pn(P.placement);return Z===w||Z==="y"}return!0}).map(P=>[P.placement,P.overflows.filter(Z=>Z>0).reduce((Z,be)=>Z+be,0)]).sort((P,Z)=>P[1]-Z[1])[0])==null?void 0:g[0];z&&($=z);break}case"initialPlacement":$=a;break}if(i!==$)return{reset:{placement:$}}}return{}}}},yR=new Set(["left","top"]);async function _R(e,t){const{placement:r,platform:n,elements:i}=e,s=await(n.isRTL==null?void 0:n.isRTL(i.floating)),o=si(r),a=Rs(r),l=pn(r)==="y",u=yR.has(o)?-1:1,c=s&&l?-1:1,d=Ls(t,e);let{mainAxis:p,crossAxis:m,alignmentAxis:h}=typeof d=="number"?{mainAxis:d,crossAxis:0,alignmentAxis:null}:{mainAxis:d.mainAxis||0,crossAxis:d.crossAxis||0,alignmentAxis:d.alignmentAxis};return a&&typeof h=="number"&&(m=a==="end"?h*-1:h),l?{x:m*c,y:p*u}:{x:p*u,y:m*c}}const wR=function(e){return e===void 0&&(e=0),{name:"offset",options:e,async fn(t){var r,n;const{x:i,y:s,placement:o,middlewareData:a}=t,l=await _R(t,e);return o===((r=a.offset)==null?void 0:r.placement)&&(n=a.arrow)!=null&&n.alignmentOffset?{}:{x:i+l.x,y:s+l.y,data:{...l,placement:o}}}}},ER=function(e){return e===void 0&&(e={}),{name:"shift",options:e,async fn(t){const{x:r,y:n,placement:i}=t,{mainAxis:s=!0,crossAxis:o=!1,limiter:a={fn:b=>{let{x:y,y:w}=b;return{x:y,y:w}}},...l}=Ls(e,t),u={x:r,y:n},c=await Op(t,l),d=pn(si(i)),p=X0(d);let m=u[p],h=u[d];if(s){const b=p==="y"?"top":"left",y=p==="y"?"bottom":"right",w=m+c[b],v=m-c[y];m=jh(w,m,v)}if(o){const b=d==="y"?"top":"left",y=d==="y"?"bottom":"right",w=h+c[b],v=h-c[y];h=jh(w,h,v)}const f=a.fn({...t,[p]:m,[d]:h});return{...f,data:{x:f.x-r,y:f.y-n,enabled:{[p]:s,[d]:o}}}}}},xR=function(e){return e===void 0&&(e={}),{name:"size",options:e,async fn(t){var r,n;const{placement:i,rects:s,platform:o,elements:a}=t,{apply:l=()=>{},...u}=Ls(e,t),c=await Op(t,u),d=si(i),p=Rs(i),m=pn(i)==="y",{width:h,height:f}=s.floating;let b,y;d==="top"||d==="bottom"?(b=d,y=p===(await(o.isRTL==null?void 0:o.isRTL(a.floating))?"start":"end")?"left":"right"):(y=d,b=p==="end"?"top":"bottom");const w=f-c.top-c.bottom,v=h-c.left-c.right,_=ii(f-c[b],w),x=ii(h-c[y],v),k=!t.middlewareData.shift;let S=_,N=x;if((r=t.middlewareData.shift)!=null&&r.enabled.x&&(N=v),(n=t.middlewareData.shift)!=null&&n.enabled.y&&(S=w),k&&!p){const T=Xt(c.left,0),F=Xt(c.right,0),C=Xt(c.top,0),g=Xt(c.bottom,0);m?N=h-2*(T!==0||F!==0?T+F:Xt(c.left,c.right)):S=f-2*(C!==0||g!==0?C+g:Xt(c.top,c.bottom))}await l({...t,availableWidth:N,availableHeight:S});const A=await o.getDimensions(a.floating);return h!==A.width||f!==A.height?{reset:{rects:!0}}:{}}}};function jc(){return typeof window<"u"}function $s(e){return Q0(e)?(e.nodeName||"").toLowerCase():"#document"}function Zt(e){var t;return(e==null||(t=e.ownerDocument)==null?void 0:t.defaultView)||window}function en(e){var t;return(t=(Q0(e)?e.ownerDocument:e.document)||window.document)==null?void 0:t.documentElement}function Q0(e){return jc()?e instanceof Node||e instanceof Zt(e).Node:!1}function Nr(e){return jc()?e instanceof Element||e instanceof Zt(e).Element:!1}function Qr(e){return jc()?e instanceof HTMLElement||e instanceof Zt(e).HTMLElement:!1}function jb(e){return!jc()||typeof ShadowRoot>"u"?!1:e instanceof ShadowRoot||e instanceof Zt(e).ShadowRoot}const SR=new Set(["inline","contents"]);function pa(e){const{overflow:t,overflowX:r,overflowY:n,display:i}=Pr(e);return/auto|scroll|overlay|hidden|clip/.test(t+n+r)&&!SR.has(i)}const CR=new Set(["table","td","th"]);function AR(e){return CR.has($s(e))}const TR=[":popover-open",":modal"];function Wc(e){return TR.some(t=>{try{return e.matches(t)}catch{return!1}})}const kR=["transform","translate","scale","rotate","perspective"],OR=["transform","translate","scale","rotate","perspective","filter"],NR=["paint","layout","strict","content"];function Kc(e){const t=Np(),r=Nr(e)?Pr(e):e;return kR.some(n=>r[n]?r[n]!=="none":!1)||(r.containerType?r.containerType!=="normal":!1)||!t&&(r.backdropFilter?r.backdropFilter!=="none":!1)||!t&&(r.filter?r.filter!=="none":!1)||OR.some(n=>(r.willChange||"").includes(n))||NR.some(n=>(r.contain||"").includes(n))}function PR(e){let t=oi(e);for(;Qr(t)&&!Es(t);){if(Kc(t))return t;if(Wc(t))return null;t=oi(t)}return null}function Np(){return typeof CSS>"u"||!CSS.supports?!1:CSS.supports("-webkit-backdrop-filter","none")}const FR=new Set(["html","body","#document"]);function Es(e){return FR.has($s(e))}function Pr(e){return Zt(e).getComputedStyle(e)}function Gc(e){return Nr(e)?{scrollLeft:e.scrollLeft,scrollTop:e.scrollTop}:{scrollLeft:e.scrollX,scrollTop:e.scrollY}}function oi(e){if($s(e)==="html")return e;const t=e.assignedSlot||e.parentNode||jb(e)&&e.host||en(e);return jb(t)?t.host:t}function Z0(e){const t=oi(e);return Es(t)?e.ownerDocument?e.ownerDocument.body:e.body:Qr(t)&&pa(t)?t:Z0(t)}function xs(e,t,r){var n;t===void 0&&(t=[]),r===void 0&&(r=!0);const i=Z0(e),s=i===((n=e.ownerDocument)==null?void 0:n.body),o=Zt(i);if(s){const a=Kh(o);return t.concat(o,o.visualViewport||[],pa(i)?i:[],a&&r?xs(a):[])}return t.concat(i,xs(i,[],r))}function Kh(e){return e.parent&&Object.getPrototypeOf(e.parent)?e.frameElement:null}function eE(e){const t=Pr(e);let r=parseFloat(t.width)||0,n=parseFloat(t.height)||0;const i=Qr(e),s=i?e.offsetWidth:r,o=i?e.offsetHeight:n,a=tc(r)!==s||tc(n)!==o;return a&&(r=s,n=o),{width:r,height:n,$:a}}function Pp(e){return Nr(e)?e:e.contextElement}function as(e){const t=Pp(e);if(!Qr(t))return Gr(1);const r=t.getBoundingClientRect(),{width:n,height:i,$:s}=eE(t);let o=(s?tc(r.width):r.width)/n,a=(s?tc(r.height):r.height)/i;return(!o||!Number.isFinite(o))&&(o=1),(!a||!Number.isFinite(a))&&(a=1),{x:o,y:a}}const IR=Gr(0);function tE(e){const t=Zt(e);return!Np()||!t.visualViewport?IR:{x:t.visualViewport.offsetLeft,y:t.visualViewport.offsetTop}}function LR(e,t,r){return t===void 0&&(t=!1),!r||t&&r!==Zt(e)?!1:t}function Li(e,t,r,n){t===void 0&&(t=!1),r===void 0&&(r=!1);const i=e.getBoundingClientRect(),s=Pp(e);let o=Gr(1);t&&(n?Nr(n)&&(o=as(n)):o=as(e));const a=LR(s,r,n)?tE(s):Gr(0);let l=(i.left+a.x)/o.x,u=(i.top+a.y)/o.y,c=i.width/o.x,d=i.height/o.y;if(s){const p=Zt(s),m=n&&Nr(n)?Zt(n):n;let h=p,f=Kh(h);for(;f&&n&&m!==h;){const b=as(f),y=f.getBoundingClientRect(),w=Pr(f),v=y.left+(f.clientLeft+parseFloat(w.paddingLeft))*b.x,_=y.top+(f.clientTop+parseFloat(w.paddingTop))*b.y;l*=b.x,u*=b.y,c*=b.x,d*=b.y,l+=v,u+=_,h=Zt(f),f=Kh(h)}}return nc({width:c,height:d,x:l,y:u})}function Yc(e,t){const r=Gc(e).scrollLeft;return t?t.left+r:Li(en(e)).left+r}function rE(e,t){const r=e.getBoundingClientRect(),n=r.left+t.scrollLeft-Yc(e,r),i=r.top+t.scrollTop;return{x:n,y:i}}function RR(e){let{elements:t,rect:r,offsetParent:n,strategy:i}=e;const s=i==="fixed",o=en(n),a=t?Wc(t.floating):!1;if(n===o||a&&s)return r;let l={scrollLeft:0,scrollTop:0},u=Gr(1);const c=Gr(0),d=Qr(n);if((d||!d&&!s)&&(($s(n)!=="body"||pa(o))&&(l=Gc(n)),Qr(n))){const m=Li(n);u=as(n),c.x=m.x+n.clientLeft,c.y=m.y+n.clientTop}const p=o&&!d&&!s?rE(o,l):Gr(0);return{width:r.width*u.x,height:r.height*u.y,x:r.x*u.x-l.scrollLeft*u.x+c.x+p.x,y:r.y*u.y-l.scrollTop*u.y+c.y+p.y}}function $R(e){return Array.from(e.getClientRects())}function MR(e){const t=en(e),r=Gc(e),n=e.ownerDocument.body,i=Xt(t.scrollWidth,t.clientWidth,n.scrollWidth,n.clientWidth),s=Xt(t.scrollHeight,t.clientHeight,n.scrollHeight,n.clientHeight);let o=-r.scrollLeft+Yc(e);const a=-r.scrollTop;return Pr(n).direction==="rtl"&&(o+=Xt(t.clientWidth,n.clientWidth)-i),{width:i,height:s,x:o,y:a}}const Wb=25;function DR(e,t){const r=Zt(e),n=en(e),i=r.visualViewport;let s=n.clientWidth,o=n.clientHeight,a=0,l=0;if(i){s=i.width,o=i.height;const c=Np();(!c||c&&t==="fixed")&&(a=i.offsetLeft,l=i.offsetTop)}const u=Yc(n);if(u<=0){const c=n.ownerDocument,d=c.body,p=getComputedStyle(d),m=c.compatMode==="CSS1Compat"&&parseFloat(p.marginLeft)+parseFloat(p.marginRight)||0,h=Math.abs(n.clientWidth-d.clientWidth-m);h<=Wb&&(s-=h)}else u<=Wb&&(s+=u);return{width:s,height:o,x:a,y:l}}const VR=new Set(["absolute","fixed"]);function BR(e,t){const r=Li(e,!0,t==="fixed"),n=r.top+e.clientTop,i=r.left+e.clientLeft,s=Qr(e)?as(e):Gr(1),o=e.clientWidth*s.x,a=e.clientHeight*s.y,l=i*s.x,u=n*s.y;return{width:o,height:a,x:l,y:u}}function Kb(e,t,r){let n;if(t==="viewport")n=DR(e,r);else if(t==="document")n=MR(en(e));else if(Nr(t))n=BR(t,r);else{const i=tE(e);n={x:t.x-i.x,y:t.y-i.y,width:t.width,height:t.height}}return nc(n)}function nE(e,t){const r=oi(e);return r===t||!Nr(r)||Es(r)?!1:Pr(r).position==="fixed"||nE(r,t)}function UR(e,t){const r=t.get(e);if(r)return r;let n=xs(e,[],!1).filter(a=>Nr(a)&&$s(a)!=="body"),i=null;const s=Pr(e).position==="fixed";let o=s?oi(e):e;for(;Nr(o)&&!Es(o);){const a=Pr(o),l=Kc(o);!l&&a.position==="fixed"&&(i=null),(s?!l&&!i:!l&&a.position==="static"&&!!i&&VR.has(i.position)||pa(o)&&!l&&nE(e,o))?n=n.filter(c=>c!==o):i=a,o=oi(o)}return t.set(e,n),n}function zR(e){let{element:t,boundary:r,rootBoundary:n,strategy:i}=e;const o=[...r==="clippingAncestors"?Wc(t)?[]:UR(t,this._c):[].concat(r),n],a=o[0],l=o.reduce((u,c)=>{const d=Kb(t,c,i);return u.top=Xt(d.top,u.top),u.right=ii(d.right,u.right),u.bottom=ii(d.bottom,u.bottom),u.left=Xt(d.left,u.left),u},Kb(t,a,i));return{width:l.right-l.left,height:l.bottom-l.top,x:l.left,y:l.top}}function qR(e){const{width:t,height:r}=eE(e);return{width:t,height:r}}function HR(e,t,r){const n=Qr(t),i=en(t),s=r==="fixed",o=Li(e,!0,s,t);let a={scrollLeft:0,scrollTop:0};const l=Gr(0);function u(){l.x=Yc(i)}if(n||!n&&!s)if(($s(t)!=="body"||pa(i))&&(a=Gc(t)),n){const m=Li(t,!0,s,t);l.x=m.x+t.clientLeft,l.y=m.y+t.clientTop}else i&&u();s&&!n&&i&&u();const c=i&&!n&&!s?rE(i,a):Gr(0),d=o.left+a.scrollLeft-l.x-c.x,p=o.top+a.scrollTop-l.y-c.y;return{x:d,y:p,width:o.width,height:o.height}}function kd(e){return Pr(e).position==="static"}function Gb(e,t){if(!Qr(e)||Pr(e).position==="fixed")return null;if(t)return t(e);let r=e.offsetParent;return en(e)===r&&(r=r.ownerDocument.body),r}function iE(e,t){const r=Zt(e);if(Wc(e))return r;if(!Qr(e)){let i=oi(e);for(;i&&!Es(i);){if(Nr(i)&&!kd(i))return i;i=oi(i)}return r}let n=Gb(e,t);for(;n&&AR(n)&&kd(n);)n=Gb(n,t);return n&&Es(n)&&kd(n)&&!Kc(n)?r:n||PR(e)||r}const jR=async function(e){const t=this.getOffsetParent||iE,r=this.getDimensions,n=await r(e.floating);return{reference:HR(e.reference,await t(e.floating),e.strategy),floating:{x:0,y:0,width:n.width,height:n.height}}};function WR(e){return Pr(e).direction==="rtl"}const pl={convertOffsetParentRelativeRectToViewportRelativeRect:RR,getDocumentElement:en,getClippingRect:zR,getOffsetParent:iE,getElementRects:jR,getClientRects:$R,getDimensions:qR,getScale:as,isElement:Nr,isRTL:WR};function sE(e,t){return e.x===t.x&&e.y===t.y&&e.width===t.width&&e.height===t.height}function KR(e,t){let r=null,n;const i=en(e);function s(){var a;clearTimeout(n),(a=r)==null||a.disconnect(),r=null}function o(a,l){a===void 0&&(a=!1),l===void 0&&(l=1),s();const u=e.getBoundingClientRect(),{left:c,top:d,width:p,height:m}=u;if(a||t(),!p||!m)return;const h=ja(d),f=ja(i.clientWidth-(c+p)),b=ja(i.clientHeight-(d+m)),y=ja(c),v={rootMargin:-h+"px "+-f+"px "+-b+"px "+-y+"px",threshold:Xt(0,ii(1,l))||1};let _=!0;function x(k){const S=k[0].intersectionRatio;if(S!==l){if(!_)return o();S?o(!1,S):n=setTimeout(()=>{o(!1,1e-7)},1e3)}S===1&&!sE(u,e.getBoundingClientRect())&&o(),_=!1}try{r=new IntersectionObserver(x,{...v,root:i.ownerDocument})}catch{r=new IntersectionObserver(x,v)}r.observe(e)}return o(!0),s}function oE(e,t,r,n){n===void 0&&(n={});const{ancestorScroll:i=!0,ancestorResize:s=!0,elementResize:o=typeof ResizeObserver=="function",layoutShift:a=typeof IntersectionObserver=="function",animationFrame:l=!1}=n,u=Pp(e),c=i||s?[...u?xs(u):[],...xs(t)]:[];c.forEach(y=>{i&&y.addEventListener("scroll",r,{passive:!0}),s&&y.addEventListener("resize",r)});const d=u&&a?KR(u,r):null;let p=-1,m=null;o&&(m=new ResizeObserver(y=>{let[w]=y;w&&w.target===u&&m&&(m.unobserve(t),cancelAnimationFrame(p),p=requestAnimationFrame(()=>{var v;(v=m)==null||v.observe(t)})),r()}),u&&!l&&m.observe(u),m.observe(t));let h,f=l?Li(e):null;l&&b();function b(){const y=Li(e);f&&!sE(f,y)&&r(),f=y,h=requestAnimationFrame(b)}return r(),()=>{var y;c.forEach(w=>{i&&w.removeEventListener("scroll",r),s&&w.removeEventListener("resize",r)}),d?.(),(y=m)==null||y.disconnect(),m=null,l&&cancelAnimationFrame(h)}}const aE=wR,lE=ER,cE=vR,Yb=xR,GR=bR,uE=(e,t,r)=>{const n=new Map,i={platform:pl,...r},s={...i.platform,_c:n};return gR(e,t,{...i,platform:s})};function YR(e){return XR(e)}function Od(e){return e.assignedSlot?e.assignedSlot:e.parentNode instanceof ShadowRoot?e.parentNode.host:e.parentNode}function XR(e){for(let t=e;t;t=Od(t))if(t instanceof Element&&getComputedStyle(t).display==="none")return null;for(let t=Od(e);t;t=Od(t)){if(!(t instanceof Element))continue;const r=getComputedStyle(t);if(r.display!=="contents"&&(r.position!=="static"||Kc(r)||t.tagName==="BODY"))return t}return null}var JR=`:host {
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
`;function Xb(e){return e!==null&&typeof e=="object"&&"getBoundingClientRect"in e&&("contextElement"in e?e instanceof Element:!0)}var Wa=globalThis?.HTMLElement?.prototype.hasOwnProperty("popover"),ke=class extends qt{constructor(){super(...arguments),this.localize=new Is(this),this.active=!1,this.placement="top",this.boundary="viewport",this.distance=0,this.skidding=0,this.arrow=!1,this.arrowPlacement="anchor",this.arrowPadding=10,this.flip=!1,this.flipFallbackPlacements="",this.flipFallbackStrategy="best-fit",this.flipPadding=0,this.shift=!1,this.shiftPadding=0,this.autoSizePadding=0,this.hoverBridge=!1,this.updateHoverBridge=()=>{if(this.hoverBridge&&this.anchorEl){const e=this.anchorEl.getBoundingClientRect(),t=this.popup.getBoundingClientRect(),r=this.placement.includes("top")||this.placement.includes("bottom");let n=0,i=0,s=0,o=0,a=0,l=0,u=0,c=0;r?e.top<t.top?(n=e.left,i=e.bottom,s=e.right,o=e.bottom,a=t.left,l=t.top,u=t.right,c=t.top):(n=t.left,i=t.bottom,s=t.right,o=t.bottom,a=e.left,l=e.top,u=e.right,c=e.top):e.left<t.left?(n=e.right,i=e.top,s=t.left,o=t.top,a=e.right,l=e.bottom,u=t.left,c=t.bottom):(n=t.right,i=t.top,s=e.left,o=e.top,a=t.right,l=t.bottom,u=e.left,c=e.bottom),this.style.setProperty("--hover-bridge-top-left-x",`${n}px`),this.style.setProperty("--hover-bridge-top-left-y",`${i}px`),this.style.setProperty("--hover-bridge-top-right-x",`${s}px`),this.style.setProperty("--hover-bridge-top-right-y",`${o}px`),this.style.setProperty("--hover-bridge-bottom-left-x",`${a}px`),this.style.setProperty("--hover-bridge-bottom-left-y",`${l}px`),this.style.setProperty("--hover-bridge-bottom-right-x",`${u}px`),this.style.setProperty("--hover-bridge-bottom-right-y",`${c}px`)}}}async connectedCallback(){super.connectedCallback(),await this.updateComplete,this.start()}disconnectedCallback(){super.disconnectedCallback(),this.stop()}async updated(e){super.updated(e),e.has("active")&&(this.active?this.start():this.stop()),e.has("anchor")&&this.handleAnchorChange(),this.active&&(await this.updateComplete,this.reposition())}async handleAnchorChange(){if(await this.stop(),this.anchor&&typeof this.anchor=="string"){const e=this.getRootNode();this.anchorEl=e.getElementById(this.anchor)}else this.anchor instanceof Element||Xb(this.anchor)?this.anchorEl=this.anchor:this.anchorEl=this.querySelector('[slot="anchor"]');this.anchorEl instanceof HTMLSlotElement&&(this.anchorEl=this.anchorEl.assignedElements({flatten:!0})[0]),this.anchorEl&&this.start()}start(){!this.anchorEl||!this.active||(this.popup.showPopover?.(),this.cleanup=oE(this.anchorEl,this.popup,()=>{this.reposition()}))}async stop(){return new Promise(e=>{this.popup.hidePopover?.(),this.cleanup?(this.cleanup(),this.cleanup=void 0,this.removeAttribute("data-current-placement"),this.style.removeProperty("--auto-size-available-width"),this.style.removeProperty("--auto-size-available-height"),requestAnimationFrame(()=>e())):e()})}reposition(){if(!this.active||!this.anchorEl)return;const e=[aE({mainAxis:this.distance,crossAxis:this.skidding})];this.sync?e.push(Yb({apply:({rects:n})=>{const i=this.sync==="width"||this.sync==="both",s=this.sync==="height"||this.sync==="both";this.popup.style.width=i?`${n.reference.width}px`:"",this.popup.style.height=s?`${n.reference.height}px`:""}})):(this.popup.style.width="",this.popup.style.height="");let t;Wa&&!Xb(this.anchor)&&this.boundary==="scroll"&&(t=xs(this.anchorEl).filter(n=>n instanceof Element)),this.flip&&e.push(cE({boundary:this.flipBoundary||t,fallbackPlacements:this.flipFallbackPlacements,fallbackStrategy:this.flipFallbackStrategy==="best-fit"?"bestFit":"initialPlacement",padding:this.flipPadding})),this.shift&&e.push(lE({boundary:this.shiftBoundary||t,padding:this.shiftPadding})),this.autoSize?e.push(Yb({boundary:this.autoSizeBoundary||t,padding:this.autoSizePadding,apply:({availableWidth:n,availableHeight:i})=>{this.autoSize==="vertical"||this.autoSize==="both"?this.style.setProperty("--auto-size-available-height",`${i}px`):this.style.removeProperty("--auto-size-available-height"),this.autoSize==="horizontal"||this.autoSize==="both"?this.style.setProperty("--auto-size-available-width",`${n}px`):this.style.removeProperty("--auto-size-available-width")}})):(this.style.removeProperty("--auto-size-available-width"),this.style.removeProperty("--auto-size-available-height")),this.arrow&&e.push(GR({element:this.arrowEl,padding:this.arrowPadding}));const r=Wa?n=>pl.getOffsetParent(n,YR):pl.getOffsetParent;uE(this.anchorEl,this.popup,{placement:this.placement,middleware:e,strategy:Wa?"absolute":"fixed",platform:{...pl,getOffsetParent:r}}).then(({x:n,y:i,middlewareData:s,placement:o})=>{const a=this.localize.dir()==="rtl",l={top:"bottom",right:"left",bottom:"top",left:"right"}[o.split("-")[0]];if(this.setAttribute("data-current-placement",o),Object.assign(this.popup.style,{left:`${n}px`,top:`${i}px`}),this.arrow){const u=s.arrow.x,c=s.arrow.y;let d="",p="",m="",h="";if(this.arrowPlacement==="start"){const f=typeof u=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"";d=typeof c=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"",p=a?f:"",h=a?"":f}else if(this.arrowPlacement==="end"){const f=typeof u=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"";p=a?"":f,h=a?f:"",m=typeof c=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:""}else this.arrowPlacement==="center"?(h=typeof u=="number"?"calc(50% - var(--arrow-size-diagonal))":"",d=typeof c=="number"?"calc(50% - var(--arrow-size-diagonal))":""):(h=typeof u=="number"?`${u}px`:"",d=typeof c=="number"?`${c}px`:"");Object.assign(this.arrowEl.style,{top:d,right:p,bottom:m,left:h,[l]:"calc(var(--arrow-size-diagonal) * -1)"})}}),requestAnimationFrame(()=>this.updateHoverBridge()),this.dispatchEvent(new iR)}render(){return W`
      <slot name="anchor" @slotchange=${this.handleAnchorChange}></slot>

      <span
        part="hover-bridge"
        class=${tr({"popup-hover-bridge":!0,"popup-hover-bridge-visible":this.hoverBridge&&this.active})}
      ></span>

      <div
        popover="manual"
        part="popup"
        class=${tr({popup:!0,"popup-active":this.active,"popup-fixed":!Wa,"popup-has-arrow":this.arrow})}
      >
        <slot></slot>
        ${this.arrow?W`<div part="arrow" class="arrow" role="presentation"></div>`:""}
      </div>
    `}};ke.css=JR;D([Ue(".popup")],ke.prototype,"popup",2);D([Ue(".arrow")],ke.prototype,"arrowEl",2);D([V()],ke.prototype,"anchor",2);D([V({type:Boolean,reflect:!0})],ke.prototype,"active",2);D([V({reflect:!0})],ke.prototype,"placement",2);D([V()],ke.prototype,"boundary",2);D([V({type:Number})],ke.prototype,"distance",2);D([V({type:Number})],ke.prototype,"skidding",2);D([V({type:Boolean})],ke.prototype,"arrow",2);D([V({attribute:"arrow-placement"})],ke.prototype,"arrowPlacement",2);D([V({attribute:"arrow-padding",type:Number})],ke.prototype,"arrowPadding",2);D([V({type:Boolean})],ke.prototype,"flip",2);D([V({attribute:"flip-fallback-placements",converter:{fromAttribute:e=>e.split(" ").map(t=>t.trim()).filter(t=>t!==""),toAttribute:e=>e.join(" ")}})],ke.prototype,"flipFallbackPlacements",2);D([V({attribute:"flip-fallback-strategy"})],ke.prototype,"flipFallbackStrategy",2);D([V({type:Object})],ke.prototype,"flipBoundary",2);D([V({attribute:"flip-padding",type:Number})],ke.prototype,"flipPadding",2);D([V({type:Boolean})],ke.prototype,"shift",2);D([V({type:Object})],ke.prototype,"shiftBoundary",2);D([V({attribute:"shift-padding",type:Number})],ke.prototype,"shiftPadding",2);D([V({attribute:"auto-size"})],ke.prototype,"autoSize",2);D([V()],ke.prototype,"sync",2);D([V({type:Object})],ke.prototype,"autoSizeBoundary",2);D([V({attribute:"auto-size-padding",type:Number})],ke.prototype,"autoSizePadding",2);D([V({attribute:"hover-bridge",type:Boolean})],ke.prototype,"hoverBridge",2);ke=D([Lr("wa-popup")],ke);var ma=class extends Event{constructor(){super("wa-after-hide",{bubbles:!0,cancelable:!1,composed:!0})}},ga=class extends Event{constructor(){super("wa-after-show",{bubbles:!0,cancelable:!1,composed:!0})}},ba=class extends Event{constructor(e){super("wa-hide",{bubbles:!0,cancelable:!0,composed:!0}),this.detail=e}},va=class extends Event{constructor(){super("wa-show",{bubbles:!0,cancelable:!0,composed:!0})}};const QR="useandom-26T198340PX75pxJACKVERYMINDBUSHWOLF_GQZbfghjklqvwyzrict";let ZR=(e=21)=>{let t="",r=crypto.getRandomValues(new Uint8Array(e|=0));for(;e--;)t+=QR[r[e]&63];return t};function Fp(e=""){return`${e}${ZR()}`}function ic(e,t){return new Promise(r=>{function n(i){i.target===e&&(e.removeEventListener(t,n),r())}e.addEventListener(t,n)})}function Tt(e,t){return new Promise(r=>{const n=new AbortController,{signal:i}=n;if(e.classList.contains(t))return;e.classList.remove(t),e.classList.add(t);let s=()=>{e.classList.remove(t),r(),n.abort()};e.addEventListener("animationend",s,{once:!0,signal:i}),e.addEventListener("animationcancel",s,{once:!0,signal:i})})}function ar(e,t){const r={waitUntilFirstUpdate:!1,...t};return(n,i)=>{const{update:s}=n,o=Array.isArray(e)?e:[e];n.update=function(a){o.forEach(l=>{const u=l;if(a.has(u)){const c=a.get(u),d=this[u];c!==d&&(!r.waitUntilFirstUpdate||this.hasUpdated)&&this[i](c,d)}}),s.call(this,a)}}}var e$=`:host {
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
`,qe=class extends qt{constructor(){super(...arguments),this.placement="top",this.disabled=!1,this.distance=8,this.open=!1,this.skidding=0,this.showDelay=150,this.hideDelay=0,this.trigger="hover focus",this.withoutArrow=!1,this.for=null,this.anchor=null,this.eventController=new AbortController,this.handleBlur=()=>{this.hasTrigger("focus")&&this.hide()},this.handleClick=()=>{this.hasTrigger("click")&&(this.open?this.hide():this.show())},this.handleFocus=()=>{this.hasTrigger("focus")&&this.show()},this.handleDocumentKeyDown=e=>{e.key==="Escape"&&(e.stopPropagation(),this.hide())},this.handleMouseOver=()=>{this.hasTrigger("hover")&&(clearTimeout(this.hoverTimeout),this.hoverTimeout=window.setTimeout(()=>this.show(),this.showDelay))},this.handleMouseOut=()=>{this.hasTrigger("hover")&&(clearTimeout(this.hoverTimeout),this.hoverTimeout=window.setTimeout(()=>this.hide(),this.hideDelay))}}connectedCallback(){super.connectedCallback(),this.eventController.signal.aborted&&(this.eventController=new AbortController),this.open&&(this.open=!1,this.updateComplete.then(()=>{this.open=!0})),this.id||(this.id=Fp("wa-tooltip-")),this.for&&this.anchor?(this.anchor=null,this.handleForChange()):this.for&&this.handleForChange()}disconnectedCallback(){super.disconnectedCallback(),document.removeEventListener("keydown",this.handleDocumentKeyDown),this.eventController.abort(),this.anchor&&this.removeFromAriaLabelledBy(this.anchor,this.id)}firstUpdated(){this.body.hidden=!this.open,this.open&&(this.popup.active=!0,this.popup.reposition())}hasTrigger(e){return this.trigger.split(" ").includes(e)}addToAriaLabelledBy(e,t){const n=(e.getAttribute("aria-labelledby")||"").split(/\s+/).filter(Boolean);n.includes(t)||(n.push(t),e.setAttribute("aria-labelledby",n.join(" ")))}removeFromAriaLabelledBy(e,t){const i=(e.getAttribute("aria-labelledby")||"").split(/\s+/).filter(Boolean).filter(s=>s!==t);i.length>0?e.setAttribute("aria-labelledby",i.join(" ")):e.removeAttribute("aria-labelledby")}async handleOpenChange(){if(this.open){if(this.disabled)return;const e=new va;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}document.addEventListener("keydown",this.handleDocumentKeyDown,{signal:this.eventController.signal}),this.body.hidden=!1,this.popup.active=!0,await Tt(this.popup.popup,"show-with-scale"),this.popup.reposition(),this.dispatchEvent(new ga)}else{const e=new ba;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}document.removeEventListener("keydown",this.handleDocumentKeyDown),await Tt(this.popup.popup,"hide-with-scale"),this.popup.active=!1,this.body.hidden=!0,this.dispatchEvent(new ma)}}handleForChange(){const e=this.getRootNode();if(!e)return;const t=this.for?e.getElementById(this.for):null,r=this.anchor;if(t===r)return;const{signal:n}=this.eventController;t&&(this.addToAriaLabelledBy(t,this.id),t.addEventListener("blur",this.handleBlur,{capture:!0,signal:n}),t.addEventListener("focus",this.handleFocus,{capture:!0,signal:n}),t.addEventListener("click",this.handleClick,{signal:n}),t.addEventListener("mouseover",this.handleMouseOver,{signal:n}),t.addEventListener("mouseout",this.handleMouseOut,{signal:n})),r&&(this.removeFromAriaLabelledBy(r,this.id),r.removeEventListener("blur",this.handleBlur,{capture:!0}),r.removeEventListener("focus",this.handleFocus,{capture:!0}),r.removeEventListener("click",this.handleClick),r.removeEventListener("mouseover",this.handleMouseOver),r.removeEventListener("mouseout",this.handleMouseOut)),this.anchor=t}async handleOptionsChange(){this.hasUpdated&&(await this.updateComplete,this.popup.reposition())}handleDisabledChange(){this.disabled&&this.open&&this.hide()}async show(){if(!this.open)return this.open=!0,ic(this,"wa-after-show")}async hide(){if(this.open)return this.open=!1,ic(this,"wa-after-hide")}render(){return W`
      <wa-popup
        part="base"
        exportparts="
          popup:base__popup,
          arrow:base__arrow
        "
        class=${tr({tooltip:!0,"tooltip-open":this.open})}
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
    `}};qe.css=e$;qe.dependencies={"wa-popup":ke};D([Ue("slot:not([name])")],qe.prototype,"defaultSlot",2);D([Ue(".body")],qe.prototype,"body",2);D([Ue("wa-popup")],qe.prototype,"popup",2);D([V()],qe.prototype,"placement",2);D([V({type:Boolean,reflect:!0})],qe.prototype,"disabled",2);D([V({type:Number})],qe.prototype,"distance",2);D([V({type:Boolean,reflect:!0})],qe.prototype,"open",2);D([V({type:Number})],qe.prototype,"skidding",2);D([V({attribute:"show-delay",type:Number})],qe.prototype,"showDelay",2);D([V({attribute:"hide-delay",type:Number})],qe.prototype,"hideDelay",2);D([V()],qe.prototype,"trigger",2);D([V({attribute:"without-arrow",type:Boolean,reflect:!0})],qe.prototype,"withoutArrow",2);D([V()],qe.prototype,"for",2);D([er()],qe.prototype,"anchor",2);D([ar("open",{waitUntilFirstUpdate:!0})],qe.prototype,"handleOpenChange",1);D([ar("for")],qe.prototype,"handleForChange",1);D([ar(["distance","placement","skidding"])],qe.prototype,"handleOptionsChange",1);D([ar("disabled")],qe.prototype,"handleDisabledChange",1);qe=D([Lr("wa-tooltip")],qe);var t$=class extends qe{static get styles(){return[qe.styles,te`
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
      `]}};customElements.get("c-tooltip")||customElements.define("c-tooltip",t$);var r$=te`
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
`,n$=te`
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
`,dE=Object.defineProperty,Jb=Object.getOwnPropertySymbols,i$=Object.prototype.hasOwnProperty,s$=Object.prototype.propertyIsEnumerable,hE=e=>{throw TypeError(e)},Qb=(e,t,r)=>t in e?dE(e,t,{enumerable:!0,configurable:!0,writable:!0,value:r}):e[t]=r,o$=(e,t)=>{for(var r in t||(t={}))i$.call(t,r)&&Qb(e,r,t[r]);if(Jb)for(var r of Jb(t))s$.call(t,r)&&Qb(e,r,t[r]);return e},Zb=(e,t,r,n)=>{for(var i=void 0,s=e.length-1,o;s>=0;s--)(o=e[s])&&(i=o(t,r,i)||i);return i&&dE(t,r,i),i},fE=(e,t,r)=>t.has(e)||hE("Cannot "+r),a$=(e,t,r)=>(fE(e,t,"read from private field"),t.get(e)),l$=(e,t,r)=>t.has(e)?hE("Cannot add the same private member more than once"):t instanceof WeakSet?t.add(e):t.set(e,r),c$=(e,t,r,n)=>(fE(e,t,"write to private field"),t.set(e,r),r),ml,ro=class extends Ne{constructor(){super(),l$(this,ml,!1),this.initialReflectedProperties=new Map,Object.entries(this.constructor.dependencies).forEach(([e,t])=>{this.constructor.define(e,t)})}emit(e,t){let r=new CustomEvent(e,o$({bubbles:!0,cancelable:!1,composed:!0,detail:{}},t));return this.dispatchEvent(r),r}static define(e,t=this,r={}){let n=customElements.get(e);if(!n){try{customElements.define(e,t,r)}catch{customElements.define(e,class extends t{},r)}return}let i=" (unknown version)",s=i;"version"in t&&t.version&&(i=" v"+t.version),"version"in n&&n.version&&(s=" v"+n.version),!(i&&s&&i===s)&&console.warn(`Attempted to register <${e}>${i}, but <${e}>${s} has already been registered.`)}attributeChangedCallback(e,t,r){a$(this,ml)||(this.constructor.elementProperties.forEach((n,i)=>{n.reflect&&this[i]!=null&&this.initialReflectedProperties.set(i,this[i])}),c$(this,ml,!0)),super.attributeChangedCallback(e,t,r)}willUpdate(e){super.willUpdate(e),this.initialReflectedProperties.forEach((t,r)=>{e.has(r)&&this[r]==null&&(this[r]=t)})}};ml=new WeakMap,ro.version="2.20.1",ro.dependencies={},Zb([V()],ro.prototype,"dir"),Zb([V()],ro.prototype,"lang");var ev=class extends ro{render(){return W` <slot></slot> `}};ev.styles=[n$,r$],ev.define("sl-visually-hidden");var u$=te`
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
`,Ks=class extends Ne{constructor(...e){super(...e),this.isCopying=!1,this.value="",this.disabled=!1}async copyValue(){if(!(this.isCopying||this.disabled)){this.isCopying=!0;try{await navigator.clipboard.writeText(this.value),this.dispatchEvent(new CustomEvent("craft-copy",{bubbles:!0,cancelable:!1,composed:!0,detail:{value:this.value}}))}catch{this.dispatchEvent(new CustomEvent("craft-error",{cancelable:!1,composed:!0,bubbles:!0}))}finally{this.isCopying=!1}}}render(){return W`
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
    `}};Ks.styles=[u$],se([er()],Ks.prototype,"isCopying",void 0),se([V({type:String})],Ks.prototype,"value",void 0),se([V({type:Boolean})],Ks.prototype,"disabled",void 0),customElements.get("craft-copy-button")||customElements.define("craft-copy-button",Ks);var d$=te`
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
`,h$=te`
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
`;const Fn={"icon.in":{keyframes:[{scale:.25,opacity:.25},{scale:1,opacity:1}],options:{duration:100}},"icon.out":{keyframes:[{scale:1,opacity:1},{scale:.25,opacity:.25}],options:{duration:100}}};var wr=class extends Ne{constructor(){super(),this.status="rest",this.value="",this.disabled=!1,this.feedbackDuration=1e3,this.tooltipLabel="Copy",this.addEventListener("craft-copy",()=>{this.showStatus("success")}),this.addEventListener("craft-error",()=>{this.showStatus("error")})}getId(){return`attribute-${this.value.replace(/([a-z])([A-Z])/g,"$1-$2").replace(/[\s_]+/g,"-").toLowerCase()}`}async showStatus(e){let t=e==="success"?this.successIconEl:this.errorIconEl;this.tooltipLabel=e==="success"?"Copied":"Copy failed",await t.animate(Fn["icon.out"].keyframes,Fn["icon.out"].options),this.copyIconEl.hidden=!0,t.hidden=!1,await t.animate(Fn["icon.in"].keyframes,Fn["icon.in"].options),this.status=e,setTimeout(async()=>{await t.animate(Fn["icon.out"].keyframes,Fn["icon.out"].options),t.hidden=!0,this.copyIconEl.hidden=!1,await this.copyIconEl.animate(Fn["icon.in"].keyframes,Fn["icon.in"].options),this.status="rest",this.tooltipLabel="Copy"},this.feedbackDuration)}render(){return W`
      <c-tooltip for="${this.getId()}">${this.tooltipLabel}</c-tooltip>
      <craft-copy-button
        value="${this.value}"
        id="${this.getId()}"
        class=${tr({"copy-attribute":!0,"copy-attribute--success":this.status==="success","copy-attribute--error":this.status==="error"})}
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
    `}};wr.styles=[d$,h$],se([er()],wr.prototype,"status",void 0),se([Ue('slot[name="copy-icon"]')],wr.prototype,"copyIconEl",void 0),se([Ue('slot[name="success-icon"]')],wr.prototype,"successIconEl",void 0),se([Ue('slot[name="error-icon"]')],wr.prototype,"errorIconEl",void 0),se([Ue("craft-copy-button")],wr.prototype,"copyButtonEl",void 0),se([V({type:String})],wr.prototype,"value",void 0),se([V({type:Boolean,reflect:!0})],wr.prototype,"disabled",void 0),se([V({attribute:"feedback-duration",type:Number})],wr.prototype,"feedbackDuration",void 0),se([V({reflect:!1})],wr.prototype,"tooltipLabel",void 0),customElements.get("craft-copy-attribute")||customElements.define("craft-copy-attribute",wr);const pE=new WeakMap;function f$(e,t){let r=t;for(;r;){if(pE.get(r)===e)return!0;r=Object.getPrototypeOf(r)}return!1}function He(e){return t=>{if(f$(e,t))return t;const r=e(t);return pE.set(r,e),r}}const p$=e=>class extends e{static get properties(){return{disabled:{type:Boolean,reflect:!0}}}constructor(){super(),this._requestedToBeDisabled=!1,this.__isUserSettingDisabled=!0,this.__restoreDisabledTo=!1,this.disabled=!1}makeRequestToBeDisabled(){this._requestedToBeDisabled===!1&&(this._requestedToBeDisabled=!0,this.__restoreDisabledTo=this.disabled,this.__internalSetDisabled(!0))}retractRequestToBeDisabled(){this._requestedToBeDisabled===!0&&(this._requestedToBeDisabled=!1,this.__internalSetDisabled(this.__restoreDisabledTo))}__internalSetDisabled(t){this.__isUserSettingDisabled=!1,this.disabled=t,this.__isUserSettingDisabled=!0}requestUpdate(t,r,n){super.requestUpdate(t,r,n),t==="disabled"&&(this.__isUserSettingDisabled&&(this.__restoreDisabledTo=this.disabled),this.disabled===!1&&this._requestedToBeDisabled===!0&&this.__internalSetDisabled(!0))}click(){this.disabled||super.click()}},ya=He(p$),m$=e=>class extends ya(e){static get properties(){return{tabIndex:{type:Number,reflect:!0,attribute:"tabindex"}}}constructor(){super(),this.__isUserSettingTabIndex=!0,this.__restoreTabIndexTo=0,this.__internalSetTabIndex(0)}makeRequestToBeDisabled(){super.makeRequestToBeDisabled(),this._requestedToBeDisabled===!1&&this.tabIndex!=null&&(this.__restoreTabIndexTo=this.tabIndex)}retractRequestToBeDisabled(){super.retractRequestToBeDisabled(),this._requestedToBeDisabled===!0&&this.__internalSetTabIndex(this.__restoreTabIndexTo)}static enabledWarnings=super.enabledWarnings?.filter(t=>t!=="change-in-update")||[];__internalSetTabIndex(t){this.__isUserSettingTabIndex=!1,this.tabIndex=t,this.__isUserSettingTabIndex=!0}requestUpdate(t,r,n){super.requestUpdate(t,r,n),t==="disabled"&&(this.disabled?this.__internalSetTabIndex(-1):this.__internalSetTabIndex(this.__restoreTabIndexTo)),t==="tabIndex"&&(this.__isUserSettingTabIndex&&this.tabIndex!=null&&(this.__restoreTabIndexTo=this.tabIndex),this.tabIndex!==-1&&this._requestedToBeDisabled===!0&&this.__internalSetTabIndex(-1))}firstUpdated(t){super.firstUpdated(t),this.disabled&&this.__internalSetTabIndex(-1)}},mE=He(m$);const{I:g$}=QE,b$=e=>e===null||typeof e!="object"&&typeof e!="function",gE=(e,t)=>e?._$litType$!==void 0,v$=e=>e.strings===void 0,tv=()=>document.createComment(""),Gs=(e,t,r)=>{const n=e._$AA.parentNode,i=t===void 0?e._$AB:t._$AA;if(r===void 0){const s=n.insertBefore(tv(),i),o=n.insertBefore(tv(),i);r=new g$(s,o,e,e.options)}else{const s=r._$AB.nextSibling,o=r._$AM,a=o!==e;if(a){let l;r._$AQ?.(e),r._$AM=e,r._$AP!==void 0&&(l=e._$AU)!==o._$AU&&r._$AP(l)}if(s!==i||a){let l=r._$AA;for(;l!==s;){const u=l.nextSibling;n.insertBefore(l,i),l=u}}}return r},di=(e,t,r=e)=>(e._$AI(t,r),e),y$={},_$=(e,t=y$)=>e._$AH=t,w$=e=>e._$AH,Nd=e=>{e._$AR(),e._$AA.remove()};function E$(e){return e instanceof Node?"node":gE(e)?"template-result":!Array.isArray(e)&&typeof e=="object"&&"template"in e?"slot-rerender-object":null}const x$=e=>class extends e{get slots(){return{}}constructor(){super(),this.__renderMetaPerSlot=new Map,this.__slotsThatNeedRerender=new Set,this.__slotsProvidedByUserOnFirstConnected=new Set,this.__privateSlots=new Set}connectedCallback(){super.connectedCallback(),this._connectSlotMixin()}__rerenderSlot(r){const n=this.slots[r]();this.__renderTemplateInScopedContext({renderAsDirectHostChild:n.renderAsDirectHostChild,template:n.template,slotName:r}),n.afterRender?.()}update(r){super.update(r);for(const n of this.__slotsThatNeedRerender)this.__rerenderSlot(n)}__renderTemplateInScopedContext({template:r,slotName:n,renderAsDirectHostChild:i}){if(!this.__renderMetaPerSlot.has(n)){const p=!!ShadowRoot.prototype.createElement;!!this.shadowRoot||console.error("[SlotMixin] No shadowRoot was found");const f=(p?this.shadowRoot:document).createElement("div"),b=document.createComment(`_start_slot_${n}_`),y=document.createComment(`_end_slot_${n}_`);f.appendChild(b),f.appendChild(y);const{creationScope:w,host:v}=this.renderOptions;if(Gp(r,f,{renderBefore:y,creationScope:w,host:v}),i){const _=Array.from(f.childNodes);this.__appendNodes({nodes:_,renderParent:this,slotName:n})}else f.slot=n,this.appendChild(f);this.__renderMetaPerSlot.set(n,{renderTargetThatRespectsShadowRootScoping:f,renderBefore:y});return}const{renderBefore:o,renderTargetThatRespectsShadowRootScoping:a}=this.__renderMetaPerSlot.get(n),l=i?this:a,{creationScope:u,host:c}=this.renderOptions;Gp(r,l,{creationScope:u,host:c,renderBefore:o}),i&&o.previousElementSibling&&!o.previousElementSibling.slot&&(o.previousElementSibling.slot=n)}__appendNodes({nodes:r,renderParent:n=this,slotName:i}){for(const s of r)s instanceof Element&&i&&i!==""&&s.setAttribute("slot",i),n.appendChild(s)}__initSlots(r){for(const n of r){if(this.__slotsProvidedByUserOnFirstConnected.has(n))continue;const i=this.slots[n]();if(i===void 0)continue;switch(this.__isConnectedSlotMixin||this.__privateSlots.add(n),E$(i)){case"template-result":this.__renderTemplateInScopedContext({template:i,renderAsDirectHostChild:!0,slotName:n});break;case"node":this.__appendNodes({nodes:[i],renderParent:this,slotName:n});break;case"slot-rerender-object":this.__slotsThatNeedRerender.add(n),i.firstRenderOnConnected&&this.__rerenderSlot(n);break;default:throw new Error(`Slot "${n}" configured inside "get slots()" (in prototype) of ${this.constructor.name} may return these types: TemplateResult | Node | {template:TemplateResult, afterRender?:function} | undefined.
              You provided: ${i}`)}}}_connectSlotMixin(){if(this.__isConnectedSlotMixin)return;const r=Object.keys(this.slots);for(const n of r)(n===""?Array.from(this.children).find(s=>!s.hasAttribute("slot")):Array.from(this.children).find(s=>s.slot===n))&&this.__slotsProvidedByUserOnFirstConnected.add(n);this.__initSlots(r),this.__isConnectedSlotMixin=!0}_isPrivateSlot(r){return this.__privateSlots.has(r)}},Ms=He(x$);function Pd(e="google-chrome"){const t=globalThis.navigator,r=!!t.userAgentData&&t.userAgentData.brands.some(l=>l.brand==="Chromium");if(e==="chromium")return r;const i=globalThis.navigator?.vendor,s=typeof globalThis.opr<"u",o=globalThis.userAgent?.indexOf("Edge")>-1,a=globalThis.userAgent?.match("CriOS");if(e==="ios")return a;if(e==="google-chrome")return r!==null&&typeof r<"u"&&i==="Google Inc."&&s===!1&&o===!1}const sc={isChrome:Pd(),isIOSChrome:Pd("ios"),isChromium:Pd("chromium"),isFirefox:globalThis.navigator?.userAgent.toLowerCase().indexOf("firefox")>-1,isMac:globalThis.navigator?.appVersion?.indexOf("Mac")!==-1,isIOS:/iPhone|iPad|iPod/i.test(globalThis.navigator?.userAgent),isMacSafari:globalThis.navigator?.vendor&&globalThis.navigator?.vendor.indexOf("Apple")>-1&&globalThis.navigator?.userAgent&&globalThis.navigator?.userAgent.indexOf("CriOS")===-1&&globalThis.navigator?.userAgent.indexOf("FxiOS")===-1&&globalThis.navigator?.appVersion.indexOf("Mac")!==-1};function _a(e=""){return`${e.length>0?`${e}-`:""}${Math.random().toString(36).substr(2,10)}`}const Fd=e=>e.key===" "||e.key==="Enter",rv=e=>e.key===" ";class S$ extends mE(Ne){static get properties(){return{active:{type:Boolean,reflect:!0},type:{type:String,reflect:!0}}}render(){return W` <div class="button-content"><slot></slot></div> `}static get styles(){return[te`
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
      `]}constructor(){super(),this.type="button",this.active=!1,this.__setupEvents()}connectedCallback(){super.connectedCallback(),this.hasAttribute("role")||this.setAttribute("role","button")}updated(t){super.updated(t),t.has("disabled")&&(this.disabled?this.setAttribute("aria-disabled","true"):this.getAttribute("aria-disabled")!==null&&this.removeAttribute("aria-disabled"))}__setupEvents(){this.addEventListener("mousedown",this.__mousedownHandler),this.addEventListener("keydown",this.__keydownHandler),this.addEventListener("keyup",this.__keyupHandler)}__mousedownHandler(){this.active=!0;const t=()=>{this.active=!1,document.removeEventListener("mouseup",t),this.removeEventListener("mouseup",t)};document.addEventListener("mouseup",t),this.addEventListener("mouseup",t)}__keydownHandler(t){if(this.active||!Fd(t)){rv(t)&&t.preventDefault();return}rv(t)&&t.preventDefault(),this.active=!0;const r=n=>{Fd(n)&&(this.active=!1,document.removeEventListener("keyup",r,!0))};document.addEventListener("keyup",r,!0)}__keyupHandler(t){if(Fd(t)){if(t.target&&t.target!==this)return;this.click()}}}class C$ extends S${constructor(){super(),this.type="reset",this.__setupDelegationInConstructor(),this.__submitAndResetHelperButton=document.createElement("button"),this.__preventEventLeakage=this.__preventEventLeakage.bind(this)}connectedCallback(){super.connectedCallback(),this.updateComplete.then(()=>{this._setupSubmitAndResetHelperOnConnected()})}disconnectedCallback(){super.disconnectedCallback(),this._teardownSubmitAndResetHelperOnDisconnected()}__preventEventLeakage(t){t.target===this.__submitAndResetHelperButton&&t.stopImmediatePropagation()}_setupSubmitAndResetHelperOnConnected(){this.appendChild(this.__submitAndResetHelperButton),this._form=this.__submitAndResetHelperButton.form,this.removeChild(this.__submitAndResetHelperButton),this._form&&this._form.addEventListener("click",this.__preventEventLeakage)}_teardownSubmitAndResetHelperOnDisconnected(){this._form&&this._form.removeEventListener("click",this.__preventEventLeakage)}async __clickDelegationHandler(t){this._form||await this.updateComplete,(this.type==="submit"||this.type==="reset")&&t.target===this&&this._form&&(this.__submitAndResetHelperButton.type=this.type,this._form.appendChild(this.__submitAndResetHelperButton),this.__submitAndResetHelperButton.click(),this._form.removeChild(this.__submitAndResetHelperButton))}__setupDelegationInConstructor(){this.addEventListener("click",this.__clickDelegationHandler,!0)}}const In=new WeakMap;function A$(){const e=document.createElement("button");return e.tabIndex=-1,e.type="submit",e.setAttribute("aria-hidden","true"),e.style.cssText=`
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
  `,e}class T$ extends C${get _nativeButtonNode(){return In.get(this._form)?.helper||null}constructor(){super(),this.type="submit",this.__implicitSubmitHelperButton=null}_setupSubmitAndResetHelperOnConnected(){if(super._setupSubmitAndResetHelperOnConnected(),!this._form||this.type!=="submit")return;const t=this._form;if(!In.get(this._form)){const n=A$(),i=document.createElement("div");i.appendChild(n),In.set(this._form,{lionButtons:new Set,helper:n,observer:new MutationObserver(()=>{t.appendChild(i)})}),t.appendChild(i),In.get(t)?.observer.observe(i,{childList:!0})}In.get(t)?.lionButtons.add(this)}_teardownSubmitAndResetHelperOnDisconnected(){if(super._teardownSubmitAndResetHelperOnDisconnected(),this._form){const t=In.get(this._form);t&&(t.lionButtons.delete(this),t.lionButtons.size||(this._form.contains(t.helper)&&t.helper.remove(),In.get(this._form)?.observer.disconnect(),In.delete(this._form)))}}}var k$=te`
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
`,Ki=class extends T${constructor(...t){super(...t),this.appearance="accent",this.variant="default",this.size="medium",this.loading=!1,this.align="center"}static get styles(){return[...super.styles,k$]}render(){return W`
      <div
        class="${tr({"button-content":!0,"button-content--start":this.align==="start","button-content--end":this.align==="end"})}"
        part="content"
      >
        <slot name="prefix" class="prefix" part="prefix"></slot>
        <slot class="label" part="label"></slot>
        <slot name="suffix" class="suffix" part="suffix"></slot>
      </div>
      ${this.loading?W`<craft-spinner part="spinner"></craft-spinner>`:pt}
    `}};se([V({reflect:!0})],Ki.prototype,"appearance",void 0),se([V({reflect:!0})],Ki.prototype,"variant",void 0),se([V({reflect:!0})],Ki.prototype,"size",void 0),se([V({reflect:!0,type:Boolean})],Ki.prototype,"loading",void 0),se([V()],Ki.prototype,"align",void 0),customElements.get("craft-button")||customElements.define("craft-button",Ki);var O$=te`
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
`,Ka=class extends Ne{constructor(...t){super(...t),this.label=null,this._gradientId=null}connectedCallback(){super.connectedCallback(),this._gradientId=`avatar-gradient-${Math.random().toString(36).slice(2,8)}`}text(){return this.label?this.label.split(" ").map(t=>t.charAt(0).toUpperCase()).join(""):"?"}render(){return W`
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
    `}};Ka.styles=[O$],se([V()],Ka.prototype,"label",void 0),se([er()],Ka.prototype,"_gradientId",void 0),customElements.get("craft-avatar")||customElements.define("craft-avatar",Ka);const Ip=te`
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
`,Xc=te`
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
`,wa=te`
  ${Xc}

  ::slotted([slot='input']) {
    ${Ip}
  }
`;var N$=te`
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
`;const Gh=window,nv=new WeakMap;function P$(e){Gh.applyFocusVisiblePolyfill&&!nv.has(e)&&(Gh.applyFocusVisiblePolyfill(e),nv.set(e,void 0))}const F$=e=>class extends e{static get properties(){return{focused:{type:Boolean,reflect:!0},focusedVisible:{type:Boolean,reflect:!0,attribute:"focused-visible"},autofocus:{type:Boolean,reflect:!0}}}constructor(){super(),this.focused=!1,this.focusedVisible=!1,this.autofocus=!1}firstUpdated(r){super.firstUpdated(r),this.__registerEventsForFocusMixin(),this.__syncAutofocusToFocusableElement()}disconnectedCallback(){super.disconnectedCallback(),this.__teardownEventsForFocusMixin()}updated(r){super.updated(r),r.has("autofocus")&&this.__syncAutofocusToFocusableElement()}__syncAutofocusToFocusableElement(){this._focusableNode&&(this.hasAttribute("autofocus")?this._focusableNode.setAttribute("autofocus",""):this._focusableNode.removeAttribute("autofocus"))}focus(){this._focusableNode?.focus()}blur(){this._focusableNode?.blur()}get _focusableNode(){return this._inputNode||document.createElement("input")}__onFocus(){if(this.focused=!0,typeof Gh.applyFocusVisiblePolyfill=="function")this.focusedVisible=this._focusableNode.hasAttribute("data-focus-visible-added");else try{this.focusedVisible=this._focusableNode.matches(":focus-visible")}catch{this.focusedVisible=!1}}__onBlur(){this.focused=!1,this.focusedVisible=!1}__registerEventsForFocusMixin(){P$(this.getRootNode()),this.__redispatchFocus=r=>{r.stopPropagation(),this.dispatchEvent(new Event("focus"))},this._focusableNode.addEventListener("focus",this.__redispatchFocus),this.__redispatchBlur=r=>{r.stopPropagation(),this.dispatchEvent(new Event("blur"))},this._focusableNode.addEventListener("blur",this.__redispatchBlur),this.__redispatchFocusin=r=>{r.stopPropagation(),this.__onFocus(),this.dispatchEvent(new Event("focusin",{bubbles:!0,composed:!0}))},this._focusableNode.addEventListener("focusin",this.__redispatchFocusin),this.__redispatchFocusout=r=>{r.stopPropagation(),this.__onBlur(),this.dispatchEvent(new Event("focusout",{bubbles:!0,composed:!0}))},this._focusableNode.addEventListener("focusout",this.__redispatchFocusout)}__teardownEventsForFocusMixin(){this._focusableNode&&(this._focusableNode?.removeEventListener("focus",this.__redispatchFocus),this._focusableNode?.removeEventListener("blur",this.__redispatchBlur),this._focusableNode?.removeEventListener("focusin",this.__redispatchFocusin),this._focusableNode?.removeEventListener("focusout",this.__redispatchFocusout))}},Lp=He(F$);function bE(e,t){return t={exports:{}},e(t,t.exports),t.exports}var hi="long",Ln="short",Id="narrow",we="numeric",Rn="2-digit",$n={number:{decimal:{style:"decimal"},integer:{style:"decimal",maximumFractionDigits:0},currency:{style:"currency",currency:"USD"},percent:{style:"percent"},default:{style:"decimal"}},date:{short:{month:we,day:we,year:Rn},medium:{month:Ln,day:we,year:we},long:{month:hi,day:we,year:we},full:{month:hi,day:we,year:we,weekday:hi},default:{month:Ln,day:we,year:we}},time:{short:{hour:we,minute:we},medium:{hour:we,minute:we,second:we},long:{hour:we,minute:we,second:we,timeZoneName:Ln},full:{hour:we,minute:we,second:we,timeZoneName:Ln},default:{hour:we,minute:we,second:we}},duration:{default:{hours:{minimumIntegerDigits:1,maximumFractionDigits:0},minutes:{minimumIntegerDigits:2,maximumFractionDigits:0},seconds:{minimumIntegerDigits:2,maximumFractionDigits:3}}},parseNumberPattern:function(e){if(e){var t={},r=e.match(/\b[A-Z]{3}\b/i),n=e.replace(/[^¤]/g,"").length;if(!n&&r&&(n=1),n?(t.style="currency",t.currencyDisplay=n===1?"symbol":n===2?"code":"name",t.currency=r?r[0].toUpperCase():"USD"):e.indexOf("%")>=0&&(t.style="percent"),!/[@#0]/.test(e))return t.style?t:void 0;if(t.useGrouping=e.indexOf(",")>=0,/E\+?[@#0]+/i.test(e)||e.indexOf("@")>=0){var i=e.replace(/E\+?[@#0]+|[^@#0]/gi,"");t.minimumSignificantDigits=Math.min(Math.max(i.replace(/[^@0]/g,"").length,1),21),t.maximumSignificantDigits=Math.min(Math.max(i.length,1),21)}else{for(var s=e.replace(/[^#0.]/g,"").split("."),o=s[0],a=o.length-1;o[a]==="0";)--a;t.minimumIntegerDigits=Math.min(Math.max(o.length-1-a,1),21);var l=s[1]||"";for(a=0;l[a]==="0";)++a;for(t.minimumFractionDigits=Math.min(Math.max(a,0),20);l[a]==="#";)++a;t.maximumFractionDigits=Math.min(Math.max(a,0),20)}return t}},parseDatePattern:function(e){if(e){for(var t={},r=0;r<e.length;){for(var n=e[r],i=1;e[++r]===n;)++i;switch(n){case"G":t.era=i===5?Id:i===4?hi:Ln;break;case"y":case"Y":t.year=i===2?Rn:we;break;case"M":case"L":i=Math.min(Math.max(i-1,0),4),t.month=[we,Rn,Ln,hi,Id][i];break;case"E":case"e":case"c":t.weekday=i===5?Id:i===4?hi:Ln;break;case"d":case"D":t.day=i===2?Rn:we;break;case"h":case"K":t.hour12=!0,t.hour=i===2?Rn:we;break;case"H":case"k":t.hour12=!1,t.hour=i===2?Rn:we;break;case"m":t.minute=i===2?Rn:we;break;case"s":case"S":t.second=i===2?Rn:we;break;case"z":case"Z":case"v":case"V":t.timeZoneName=i===1?Ln:hi;break}}return Object.keys(t).length?t:void 0}}},I$=function(t,r){if(typeof t=="string"&&r[t])return t;for(var n=[].concat(t||[]),i=0,s=n.length;i<s;++i)for(var o=n[i].split("-");o.length;){var a=o.join("-");if(r[a])return a;o.pop()}},Gi="zero",oe="one",nt="two",Te="few",We="many",ie="other",O=[function(e){var t=+e;return t===1?oe:ie},function(e){var t=+e;return 0<=t&&t<=1?oe:ie},function(e){var t=Math.floor(Math.abs(+e)),r=+e;return t===0||r===1?oe:ie},function(e){var t=+e;return t===0?Gi:t===1?oe:t===2?nt:3<=t%100&&t%100<=10?Te:11<=t%100&&t%100<=99?We:ie},function(e){var t=Math.floor(Math.abs(+e)),r=(e+".").split(".")[1].length;return t===1&&r===0?oe:ie},function(e){var t=+e;return t%10===1&&t%100!==11?oe:2<=t%10&&t%10<=4&&(t%100<12||14<t%100)?Te:t%10===0||5<=t%10&&t%10<=9||11<=t%100&&t%100<=14?We:ie},function(e){var t=+e;return t%10===1&&t%100!==11&&t%100!==71&&t%100!==91?oe:t%10===2&&t%100!==12&&t%100!==72&&t%100!==92?nt:(3<=t%10&&t%10<=4||t%10===9)&&(t%100<10||19<t%100)&&(t%100<70||79<t%100)&&(t%100<90||99<t%100)?Te:t!==0&&t%1e6===0?We:ie},function(e){var t=Math.floor(Math.abs(+e)),r=(e+".").split(".")[1].length,n=+(e+".").split(".")[1];return r===0&&t%10===1&&t%100!==11||n%10===1&&n%100!==11?oe:r===0&&2<=t%10&&t%10<=4&&(t%100<12||14<t%100)||2<=n%10&&n%10<=4&&(n%100<12||14<n%100)?Te:ie},function(e){var t=Math.floor(Math.abs(+e)),r=(e+".").split(".")[1].length;return t===1&&r===0?oe:2<=t&&t<=4&&r===0?Te:r!==0?We:ie},function(e){var t=+e;return t===0?Gi:t===1?oe:t===2?nt:t===3?Te:t===6?We:ie},function(e){var t=Math.floor(Math.abs(+e)),r=+(""+e).replace(/^[^.]*.?|0+$/g,""),n=+e;return n===1||r!==0&&(t===0||t===1)?oe:ie},function(e){var t=Math.floor(Math.abs(+e)),r=(e+".").split(".")[1].length,n=+(e+".").split(".")[1];return r===0&&t%100===1||n%100===1?oe:r===0&&t%100===2||n%100===2?nt:r===0&&3<=t%100&&t%100<=4||3<=n%100&&n%100<=4?Te:ie},function(e){var t=Math.floor(Math.abs(+e));return t===0||t===1?oe:ie},function(e){var t=Math.floor(Math.abs(+e)),r=(e+".").split(".")[1].length,n=+(e+".").split(".")[1];return r===0&&(t===1||t===2||t===3)||r===0&&t%10!==4&&t%10!==6&&t%10!==9||r!==0&&n%10!==4&&n%10!==6&&n%10!==9?oe:ie},function(e){var t=+e;return t===1?oe:t===2?nt:3<=t&&t<=6?Te:7<=t&&t<=10?We:ie},function(e){var t=+e;return t===1||t===11?oe:t===2||t===12?nt:3<=t&&t<=10||13<=t&&t<=19?Te:ie},function(e){var t=Math.floor(Math.abs(+e)),r=(e+".").split(".")[1].length;return r===0&&t%10===1?oe:r===0&&t%10===2?nt:r===0&&(t%100===0||t%100===20||t%100===40||t%100===60||t%100===80)?Te:r!==0?We:ie},function(e){var t=Math.floor(Math.abs(+e)),r=(e+".").split(".")[1].length,n=+e;return t===1&&r===0?oe:t===2&&r===0?nt:r===0&&(n<0||10<n)&&n%10===0?We:ie},function(e){var t=Math.floor(Math.abs(+e)),r=+(""+e).replace(/^[^.]*.?|0+$/g,"");return r===0&&t%10===1&&t%100!==11||r!==0?oe:ie},function(e){var t=+e;return t===1?oe:t===2?nt:ie},function(e){var t=+e;return t===0?Gi:t===1?oe:ie},function(e){var t=Math.floor(Math.abs(+e)),r=+e;return r===0?Gi:(t===0||t===1)&&r!==0?oe:ie},function(e){var t=+(e+".").split(".")[1],r=+e;return r%10===1&&(r%100<11||19<r%100)?oe:2<=r%10&&r%10<=9&&(r%100<11||19<r%100)?Te:t!==0?We:ie},function(e){var t=(e+".").split(".")[1].length,r=+(e+".").split(".")[1],n=+e;return n%10===0||11<=n%100&&n%100<=19||t===2&&11<=r%100&&r%100<=19?Gi:n%10===1&&n%100!==11||t===2&&r%10===1&&r%100!==11||t!==2&&r%10===1?oe:ie},function(e){var t=Math.floor(Math.abs(+e)),r=(e+".").split(".")[1].length,n=+(e+".").split(".")[1];return r===0&&t%10===1&&t%100!==11||n%10===1&&n%100!==11?oe:ie},function(e){var t=Math.floor(Math.abs(+e)),r=(e+".").split(".")[1].length,n=+e;return t===1&&r===0?oe:r!==0||n===0||n!==1&&1<=n%100&&n%100<=19?Te:ie},function(e){var t=+e;return t===1?oe:t===0||2<=t%100&&t%100<=10?Te:11<=t%100&&t%100<=19?We:ie},function(e){var t=Math.floor(Math.abs(+e)),r=(e+".").split(".")[1].length;return t===1&&r===0?oe:r===0&&2<=t%10&&t%10<=4&&(t%100<12||14<t%100)?Te:r===0&&t!==1&&0<=t%10&&t%10<=1||r===0&&5<=t%10&&t%10<=9||r===0&&12<=t%100&&t%100<=14?We:ie},function(e){var t=Math.floor(Math.abs(+e));return 0<=t&&t<=1?oe:ie},function(e){var t=Math.floor(Math.abs(+e)),r=(e+".").split(".")[1].length;return r===0&&t%10===1&&t%100!==11?oe:r===0&&2<=t%10&&t%10<=4&&(t%100<12||14<t%100)?Te:r===0&&t%10===0||r===0&&5<=t%10&&t%10<=9||r===0&&11<=t%100&&t%100<=14?We:ie},function(e){var t=Math.floor(Math.abs(+e)),r=+e;return t===0||r===1?oe:2<=r&&r<=10?Te:ie},function(e){var t=Math.floor(Math.abs(+e)),r=+(e+".").split(".")[1],n=+e;return n===0||n===1||t===0&&r===1?oe:ie},function(e){var t=Math.floor(Math.abs(+e)),r=(e+".").split(".")[1].length;return r===0&&t%100===1?oe:r===0&&t%100===2?nt:r===0&&3<=t%100&&t%100<=4||r!==0?Te:ie},function(e){var t=+e;return 0<=t&&t<=1||11<=t&&t<=99?oe:ie},function(e){var t=+e;return t===1||t===5||t===7||t===8||t===9||t===10?oe:t===2||t===3?nt:t===4?Te:t===6?We:ie},function(e){var t=Math.floor(Math.abs(+e));return t%10===1||t%10===2||t%10===5||t%10===7||t%10===8||t%100===20||t%100===50||t%100===70||t%100===80?oe:t%10===3||t%10===4||t%1e3===100||t%1e3===200||t%1e3===300||t%1e3===400||t%1e3===500||t%1e3===600||t%1e3===700||t%1e3===800||t%1e3===900?Te:t===0||t%10===6||t%100===40||t%100===60||t%100===90?We:ie},function(e){var t=+e;return(t%10===2||t%10===3)&&t%100!==12&&t%100!==13?Te:ie},function(e){var t=+e;return t===1||t===3?oe:t===2?nt:t===4?Te:ie},function(e){var t=+e;return t===0||t===7||t===8||t===9?Gi:t===1?oe:t===2?nt:t===3||t===4?Te:t===5||t===6?We:ie},function(e){var t=+e;return t%10===1&&t%100!==11?oe:t%10===2&&t%100!==12?nt:t%10===3&&t%100!==13?Te:ie},function(e){var t=+e;return t===1||t===11?oe:t===2||t===12?nt:t===3||t===13?Te:ie},function(e){var t=+e;return t===1?oe:t===2||t===3?nt:t===4?Te:t===6?We:ie},function(e){var t=+e;return t===1||t===5?oe:ie},function(e){var t=+e;return t===11||t===8||t===80||t===800?We:ie},function(e){var t=Math.floor(Math.abs(+e));return t===1?oe:t===0||2<=t%100&&t%100<=20||t%100===40||t%100===60||t%100===80?We:ie},function(e){var t=+e;return t%10===6||t%10===9||t%10===0&&t!==0?We:ie},function(e){var t=Math.floor(Math.abs(+e));return t%10===1&&t%100!==11?oe:t%10===2&&t%100!==12?nt:(t%10===7||t%10===8)&&t%100!==17&&t%100!==18?We:ie},function(e){var t=+e;return t===1?oe:t===2||t===3?nt:t===4?Te:ie},function(e){var t=+e;return 1<=t&&t<=4?oe:ie},function(e){var t=+e;return t===1||t===5||7<=t&&t<=9?oe:t===2||t===3?nt:t===4?Te:t===6?We:ie},function(e){var t=+e;return t===1?oe:t%10===4&&t%100!==14?We:ie},function(e){var t=+e;return(t%10===1||t%10===2)&&t%100!==11&&t%100!==12?oe:ie},function(e){var t=+e;return t%10===6||t%10===9||t===10?Te:ie},function(e){var t=+e;return t%10===3&&t%100!==13?Te:ie}],Yh={af:{cardinal:O[0]},ak:{cardinal:O[1]},am:{cardinal:O[2]},ar:{cardinal:O[3]},ars:{cardinal:O[3]},as:{cardinal:O[2],ordinal:O[34]},asa:{cardinal:O[0]},ast:{cardinal:O[4]},az:{cardinal:O[0],ordinal:O[35]},be:{cardinal:O[5],ordinal:O[36]},bem:{cardinal:O[0]},bez:{cardinal:O[0]},bg:{cardinal:O[0]},bh:{cardinal:O[1]},bn:{cardinal:O[2],ordinal:O[34]},br:{cardinal:O[6]},brx:{cardinal:O[0]},bs:{cardinal:O[7]},ca:{cardinal:O[4],ordinal:O[37]},ce:{cardinal:O[0]},cgg:{cardinal:O[0]},chr:{cardinal:O[0]},ckb:{cardinal:O[0]},cs:{cardinal:O[8]},cy:{cardinal:O[9],ordinal:O[38]},da:{cardinal:O[10]},de:{cardinal:O[4]},dsb:{cardinal:O[11]},dv:{cardinal:O[0]},ee:{cardinal:O[0]},el:{cardinal:O[0]},en:{cardinal:O[4],ordinal:O[39]},eo:{cardinal:O[0]},es:{cardinal:O[0]},et:{cardinal:O[4]},eu:{cardinal:O[0]},fa:{cardinal:O[2]},ff:{cardinal:O[12]},fi:{cardinal:O[4]},fil:{cardinal:O[13],ordinal:O[0]},fo:{cardinal:O[0]},fr:{cardinal:O[12],ordinal:O[0]},fur:{cardinal:O[0]},fy:{cardinal:O[4]},ga:{cardinal:O[14],ordinal:O[0]},gd:{cardinal:O[15],ordinal:O[40]},gl:{cardinal:O[4]},gsw:{cardinal:O[0]},gu:{cardinal:O[2],ordinal:O[41]},guw:{cardinal:O[1]},gv:{cardinal:O[16]},ha:{cardinal:O[0]},haw:{cardinal:O[0]},he:{cardinal:O[17]},hi:{cardinal:O[2],ordinal:O[41]},hr:{cardinal:O[7]},hsb:{cardinal:O[11]},hu:{cardinal:O[0],ordinal:O[42]},hy:{cardinal:O[12],ordinal:O[0]},ia:{cardinal:O[4]},io:{cardinal:O[4]},is:{cardinal:O[18]},it:{cardinal:O[4],ordinal:O[43]},iu:{cardinal:O[19]},iw:{cardinal:O[17]},jgo:{cardinal:O[0]},ji:{cardinal:O[4]},jmc:{cardinal:O[0]},ka:{cardinal:O[0],ordinal:O[44]},kab:{cardinal:O[12]},kaj:{cardinal:O[0]},kcg:{cardinal:O[0]},kk:{cardinal:O[0],ordinal:O[45]},kkj:{cardinal:O[0]},kl:{cardinal:O[0]},kn:{cardinal:O[2]},ks:{cardinal:O[0]},ksb:{cardinal:O[0]},ksh:{cardinal:O[20]},ku:{cardinal:O[0]},kw:{cardinal:O[19]},ky:{cardinal:O[0]},lag:{cardinal:O[21]},lb:{cardinal:O[0]},lg:{cardinal:O[0]},ln:{cardinal:O[1]},lt:{cardinal:O[22]},lv:{cardinal:O[23]},mas:{cardinal:O[0]},mg:{cardinal:O[1]},mgo:{cardinal:O[0]},mk:{cardinal:O[24],ordinal:O[46]},ml:{cardinal:O[0]},mn:{cardinal:O[0]},mo:{cardinal:O[25],ordinal:O[0]},mr:{cardinal:O[2],ordinal:O[47]},mt:{cardinal:O[26]},nah:{cardinal:O[0]},naq:{cardinal:O[19]},nb:{cardinal:O[0]},nd:{cardinal:O[0]},ne:{cardinal:O[0],ordinal:O[48]},nl:{cardinal:O[4]},nn:{cardinal:O[0]},nnh:{cardinal:O[0]},no:{cardinal:O[0]},nr:{cardinal:O[0]},nso:{cardinal:O[1]},ny:{cardinal:O[0]},nyn:{cardinal:O[0]},om:{cardinal:O[0]},or:{cardinal:O[0],ordinal:O[49]},os:{cardinal:O[0]},pa:{cardinal:O[1]},pap:{cardinal:O[0]},pl:{cardinal:O[27]},prg:{cardinal:O[23]},ps:{cardinal:O[0]},pt:{cardinal:O[28]},"pt-PT":{cardinal:O[4]},rm:{cardinal:O[0]},ro:{cardinal:O[25],ordinal:O[0]},rof:{cardinal:O[0]},ru:{cardinal:O[29]},rwk:{cardinal:O[0]},saq:{cardinal:O[0]},sc:{cardinal:O[4],ordinal:O[43]},scn:{cardinal:O[4],ordinal:O[43]},sd:{cardinal:O[0]},sdh:{cardinal:O[0]},se:{cardinal:O[19]},seh:{cardinal:O[0]},sh:{cardinal:O[7]},shi:{cardinal:O[30]},si:{cardinal:O[31]},sk:{cardinal:O[8]},sl:{cardinal:O[32]},sma:{cardinal:O[19]},smi:{cardinal:O[19]},smj:{cardinal:O[19]},smn:{cardinal:O[19]},sms:{cardinal:O[19]},sn:{cardinal:O[0]},so:{cardinal:O[0]},sq:{cardinal:O[0],ordinal:O[50]},sr:{cardinal:O[7]},ss:{cardinal:O[0]},ssy:{cardinal:O[0]},st:{cardinal:O[0]},sv:{cardinal:O[4],ordinal:O[51]},sw:{cardinal:O[4]},syr:{cardinal:O[0]},ta:{cardinal:O[0]},te:{cardinal:O[0]},teo:{cardinal:O[0]},ti:{cardinal:O[1]},tig:{cardinal:O[0]},tk:{cardinal:O[0],ordinal:O[52]},tl:{cardinal:O[13],ordinal:O[0]},tn:{cardinal:O[0]},tr:{cardinal:O[0]},ts:{cardinal:O[0]},tzm:{cardinal:O[33]},ug:{cardinal:O[0]},uk:{cardinal:O[29],ordinal:O[53]},ur:{cardinal:O[4]},uz:{cardinal:O[0]},ve:{cardinal:O[0]},vo:{cardinal:O[0]},vun:{cardinal:O[0]},wa:{cardinal:O[1]},wae:{cardinal:O[0]},xh:{cardinal:O[0]},xog:{cardinal:O[0]},yi:{cardinal:O[4]},zu:{cardinal:O[2]},lo:{ordinal:O[0]},ms:{ordinal:O[0]},vi:{ordinal:O[0]}},Jc=bE(function(e,t){t=e.exports=function(m,h,f){return r(m,null,h||"en",f||{},!0)},t.toParts=function(m,h,f){return r(m,null,h||"en",f||{},!1)};function r(p,m,h,f,b){var y=p.map(function(w){return n(w,m,h,f,b)});return b?y.length===1?y[0]:function(v){for(var _="",x=0;x<y.length;++x)_+=y[x](v);return _}:function(v){return y.reduce(function(_,x){return _.concat(x(v))},[])}}function n(p,m,h,f,b){if(typeof p=="string"){var y=p;return function(){return y}}var w=p[0],v=p[1];if(m&&p[0]==="#"){w=m[0];var _=m[2],x=(f.number||d.number)([w,"number"],h);return function(T){return x(i(w,T)-_,T)}}var k;v==="plural"||v==="selectordinal"?(k={},Object.keys(p[3]).forEach(function(A){k[A]=r(p[3][A],p,h,f,b)}),p=[p[0],p[1],p[2],k]):p[2]&&typeof p[2]=="object"&&(k={},Object.keys(p[2]).forEach(function(A){k[A]=r(p[2][A],p,h,f,b)}),p=[p[0],p[1],k]);var S=v&&(f[v]||d[v]);if(S){var N=S(p,h);return function(T){return N(i(w,T),T)}}return b?function(T){return String(i(w,T))}:function(T){return i(w,T)}}function i(p,m){if(m&&p in m)return m[p];for(var h=p.split("."),f=m,b=0,y=h.length;f&&b<y;++b)f=f[h[b]];return f}function s(p,m){var h=p[2],f=$n.number[h]||$n.parseNumberPattern(h)||$n.number.default;return new Intl.NumberFormat(m,f).format}function o(p,m){var h=p[2],f=$n.duration[h]||$n.duration.default,b=new Intl.NumberFormat(m,f.seconds).format,y=new Intl.NumberFormat(m,f.minutes).format,w=new Intl.NumberFormat(m,f.hours).format,v=/^fi$|^fi-|^da/.test(String(m))?".":":";return function(_,x){if(_=+_,!isFinite(_))return b(_);var k=~~(_/60/60),S=~~(_/60%60),N=(k?w(Math.abs(k))+v:"")+y(Math.abs(S))+v+b(Math.abs(_%60));return _<0?w(-1).replace(w(1),N):N}}function a(p,m){var h=p[1],f=p[2],b=$n[h][f]||$n.parseDatePattern(f)||$n[h].default;return new Intl.DateTimeFormat(m,b).format}function l(p,m){var h=p[1],f=h==="selectordinal"?"ordinal":"cardinal",b=p[2],y=p[3],w;if(Intl.PluralRules&&Intl.PluralRules.supportedLocalesOf(m).length>0)w=new Intl.PluralRules(m,{type:f});else{var v=I$(m,Yh),_=v&&Yh[v][f]||u;w={select:_}}return function(x,k){var S=y["="+ +x]||y[w.select(x-b)]||y.other;return S(k)}}function u(){return"other"}function c(p,m){var h=p[2];return function(f,b){var y=h[f]||h.other;return y(b)}}var d={number:s,ordinal:s,spellout:s,duration:o,date:a,time:a,plural:l,selectordinal:l,select:c};t.types=d});Jc.toParts;Jc.types;var vE=bE(function(e,t){var r="{",n="}",i=",",s="#",o="<",a=">",l="</",u="/>",c="'",d="offset:",p=["number","date","time","ordinal","duration","spellout"],m=["plural","select","selectordinal"];t=e.exports=function(L,B){return h({pattern:String(L),index:0,tagsType:B&&B.tagsType||null,tokens:B&&B.tokens||null},"")};function h(g,L){var B=g.pattern,$=B.length,z=[],P=g.index,Z=f(g,L);for(Z&&z.push(Z),Z&&g.tokens&&g.tokens.push(["text",B.slice(P,g.index)]);g.index<$;){if(B[g.index]===n){if(!L)throw T(g);break}if(L&&g.tagsType&&B.slice(g.index,g.index+l.length)===l)break;z.push(w(g)),P=g.index,Z=f(g,L),Z&&z.push(Z),Z&&g.tokens&&g.tokens.push(["text",B.slice(P,g.index)])}return z}function f(g,L){for(var B=g.pattern,$=B.length,z=L==="plural"||L==="selectordinal",P=!!g.tagsType,Z=L==="{style}",be="";g.index<$;){var le=B[g.index];if(le===r||le===n||z&&le===s||P&&le===o||Z&&b(le.charCodeAt(0)))break;if(le===c)if(le=B[++g.index],le===c)be+=le,++g.index;else if(le===r||le===n||z&&le===s||P&&le===o||Z)for(be+=le;++g.index<$;)if(le=B[g.index],le===c&&B[g.index+1]===c)be+=c,++g.index;else if(le===c){++g.index;break}else be+=le;else be+=c;else be+=le,++g.index}return be}function b(g){return g>=9&&g<=13||g===32||g===133||g===160||g===6158||g>=8192&&g<=8205||g===8232||g===8233||g===8239||g===8287||g===8288||g===12288||g===65279}function y(g){for(var L=g.pattern,B=L.length,$=g.index;g.index<B&&b(L.charCodeAt(g.index));)++g.index;$<g.index&&g.tokens&&g.tokens.push(["space",g.pattern.slice($,g.index)])}function w(g){var L=g.pattern;if(L[g.index]===s)return g.tokens&&g.tokens.push(["syntax",s]),++g.index,[s];var B=v(g);if(B)return B;if(L[g.index]!==r)throw T(g,r);g.tokens&&g.tokens.push(["syntax",r]),++g.index,y(g);var $=_(g);if(!$)throw T(g,"placeholder id");g.tokens&&g.tokens.push(["id",$]),y(g);var z=L[g.index];if(z===n)return g.tokens&&g.tokens.push(["syntax",n]),++g.index,[$];if(z!==i)throw T(g,i+" or "+n);g.tokens&&g.tokens.push(["syntax",i]),++g.index,y(g);var P=_(g);if(!P)throw T(g,"placeholder type");if(g.tokens&&g.tokens.push(["type",P]),y(g),z=L[g.index],z===n){if(g.tokens&&g.tokens.push(["syntax",n]),P==="plural"||P==="selectordinal"||P==="select")throw T(g,P+" sub-messages");return++g.index,[$,P]}if(z!==i)throw T(g,i+" or "+n);g.tokens&&g.tokens.push(["syntax",i]),++g.index,y(g);var Z;if(P==="plural"||P==="selectordinal"){var be=k(g);y(g),Z=[$,P,be,N(g,P)]}else if(P==="select")Z=[$,P,N(g,P)];else if(p.indexOf(P)>=0)Z=[$,P,x(g)];else{var le=g.index,je=x(g);y(g),L[g.index]===r&&(g.index=le,je=N(g,P)),Z=[$,P,je]}if(y(g),L[g.index]!==n)throw T(g,n);return g.tokens&&g.tokens.push(["syntax",n]),++g.index,Z}function v(g){var L=g.tagsType;if(!(!L||g.pattern[g.index]!==o)){if(g.pattern.slice(g.index,g.index+l.length)===l)throw T(g,null,"closing tag without matching opening tag");g.tokens&&g.tokens.push(["syntax",o]),++g.index;var B=_(g,!0);if(!B)throw T(g,"placeholder id");if(g.tokens&&g.tokens.push(["id",B]),y(g),g.pattern.slice(g.index,g.index+u.length)===u)return g.tokens&&g.tokens.push(["syntax",u]),g.index+=u.length,[B,L];if(g.pattern[g.index]!==a)throw T(g,a);g.tokens&&g.tokens.push(["syntax",a]),++g.index;var $=h(g,L),z=g.index;if(g.pattern.slice(g.index,g.index+l.length)!==l)throw T(g,l+B+a);g.tokens&&g.tokens.push(["syntax",l]),g.index+=l.length;var P=_(g,!0);if(P&&g.tokens&&g.tokens.push(["id",P]),B!==P)throw g.index=z,T(g,l+B+a,l+P+a);if(y(g),g.pattern[g.index]!==a)throw T(g,a);return g.tokens&&g.tokens.push(["syntax",a]),++g.index,[B,L,{children:$}]}}function _(g,L){for(var B=g.pattern,$=B.length,z="";g.index<$;){var P=B[g.index];if(P===r||P===n||P===i||P===s||P===c||b(P.charCodeAt(0))||L&&(P===o||P===a||P==="/"))break;z+=P,++g.index}return z}function x(g){var L=g.index,B=f(g,"{style}");if(!B)throw T(g,"placeholder style name");return g.tokens&&g.tokens.push(["style",g.pattern.slice(L,g.index)]),B}function k(g){var L=g.pattern,B=L.length,$=0;if(L.slice(g.index,g.index+d.length)===d){g.tokens&&g.tokens.push(["offset","offset"],["syntax",":"]),g.index+=d.length,y(g);for(var z=g.index;g.index<B&&S(L.charCodeAt(g.index));)++g.index;if(z===g.index)throw T(g,"offset number");g.tokens&&g.tokens.push(["number",L.slice(z,g.index)]),$=+L.slice(z,g.index)}return $}function S(g){return g>=48&&g<=57}function N(g,L){for(var B=g.pattern,$=B.length,z={};g.index<$&&B[g.index]!==n;){var P=_(g);if(!P)throw T(g,"sub-message selector");g.tokens&&g.tokens.push(["selector",P]),y(g),z[P]=A(g,L),y(g)}if(!z.other&&m.indexOf(L)>=0)throw T(g,null,null,'"other" sub-message must be specified in '+L);return z}function A(g,L){if(g.pattern[g.index]!==r)throw T(g,r+" to start sub-message");g.tokens&&g.tokens.push(["syntax",r]),++g.index;var B=h(g,L);if(g.pattern[g.index]!==n)throw T(g,n+" to end sub-message");return g.tokens&&g.tokens.push(["syntax",n]),++g.index,B}function T(g,L,B,$){var z=g.pattern,P=z.slice(0,g.index).split(/\r?\n/),Z=g.index,be=P.length,le=P.slice(-1)[0].length;return B=B||(g.index>=z.length?"end of message pattern":_(g)||z[g.index]),$||($=F(L,B)),$+=" in "+z.replace(/\r?\n/g,`
`),new C($,L,B,Z,be,le)}function F(g,L){return g?"Expected "+g+" but found "+L:"Unexpected "+L+" found"}function C(g,L,B,$,z,P){Error.call(this,g),this.name="SyntaxError",this.message=g,this.expected=L,this.found=B,this.offset=$,this.line=z,this.column=P}C.prototype=Object.create(Error.prototype),t.SyntaxError=C});vE.SyntaxError;var L$=new RegExp("^("+Object.keys(Yh).join("|")+")\\b"),Co=new WeakMap;function Ss(e,t,r){if(!(this instanceof Ss)||Co.has(this))throw new TypeError("calling MessageFormat constructor without new is invalid");var n=vE(e);Co.set(this,{ast:n,format:Jc(n,t,r&&r.types),locale:Ss.supportedLocalesOf(t)[0]||"en",locales:t,options:r})}var R$=Ss;Object.defineProperties(Ss.prototype,{format:{configurable:!0,get:function(){var t=Co.get(this);if(!t)throw new TypeError("MessageFormat.prototype.format called on value that's not an object initialized as a MessageFormat");return t.format}},formatToParts:{configurable:!0,writable:!0,value:function(t){var r=Co.get(this);if(!r)throw new TypeError("MessageFormat.prototype.formatToParts called on value that's not an object initialized as a MessageFormat");var n=r.toParts||(r.toParts=Jc.toParts(r.ast,r.locales,r.options&&r.options.types));return n(t)}},resolvedOptions:{configurable:!0,writable:!0,value:function(){var t=Co.get(this);if(!t)throw new TypeError("MessageFormat.prototype.resolvedOptions called on value that's not an object initialized as a MessageFormat");return{locale:t.locale}}}});typeof Symbol<"u"&&Object.defineProperty(Ss.prototype,Symbol.toStringTag,{value:"Object"});Object.defineProperties(Ss,{supportedLocalesOf:{configurable:!0,writable:!0,value:function(t){return[].concat(Intl.NumberFormat.supportedLocalesOf(t),Intl.DateTimeFormat.supportedLocalesOf(t),Intl.PluralRules?Intl.PluralRules.supportedLocalesOf(t):[],[].concat(t||[]).filter(function(r){return L$.test(r)})).filter(function(r,n,i){return i.indexOf(r)===n})}}});function $$(e){return!!(e&&e.default&&typeof e.default=="object"&&Object.keys(e).length===1)}const Mn=globalThis.document?.documentElement;class M$ extends EventTarget{formatNumberOptions={returnIfNaN:"",postProcessors:new Map};formatDateOptions={postProcessors:new Map};#e=!1;#t="";#r=null;__storage={};__namespacePatternsMap=new Map;__namespaceLoadersCache={};__namespaceLoaderPromisesCache={};get locale(){return this.#e?this.#t||"":Mn.lang||""}set locale(t){if(this.#n(t),!this.#e){const i=Mn.lang;this._setHtmlLangAttribute(t),this._onLocaleChanged(t,i);return}const r=this.#t;this.#t=t,this.#r===null&&this._setHtmlLangAttribute(t),this._onLocaleChanged(t,r)}get loadingComplete(){return typeof this.__namespaceLoaderPromisesCache[this.locale]=="object"?Promise.all(Object.values(this.__namespaceLoaderPromisesCache[this.locale])):Promise.resolve()}constructor({allowOverridesForExistingNamespaces:t=!1,autoLoadOnLocaleChange:r=!1,showKeyAsFallback:n=!1,fallbackLocale:i=""}={}){super(),this.__allowOverridesForExistingNamespaces=t,this._autoLoadOnLocaleChange=!!r,this._showKeyAsFallback=n,this._fallbackLocale=i;const s=Mn.getAttribute("data-localize-lang");this.#e=!!s,this.#e&&(this.locale=s,this._setupTranslationToolSupport()),Mn.lang||(Mn.lang=this.locale||"en-GB"),this._setupHtmlLangAttributeObserver()}addData(t,r,n){if(!this.__allowOverridesForExistingNamespaces&&this._isNamespaceInCache(t,r))throw new Error(`Namespace "${r}" has been already added for the locale "${t}".`);this.__storage[t]=this.__storage[t]||{},this.__allowOverridesForExistingNamespaces?this.__storage[t][r]={...this.__storage[t][r],...n}:this.__storage[t][r]=n}setupNamespaceLoader(t,r){this.__namespacePatternsMap.set(t,r)}loadNamespaces(t,{locale:r}={}){return Promise.all(t.map(n=>this.loadNamespace(n,{locale:r})))}loadNamespace(t,{locale:r=this.locale}={locale:this.locale}){const n=typeof t=="object",i=n?Object.keys(t)[0]:t;if(this._isNamespaceInCache(r,i))return Promise.resolve();const s=this._getCachedNamespaceLoaderPromise(r,i);return s||this._loadNamespaceData(r,t,n,i)}msg(t,r,n={}){const i=n.locale?n.locale:this.locale,s=this._getMessageForKeys(t,i);return s?new R$(s,i).format(r):""}teardown(){this._teardownHtmlLangAttributeObserver()}reset(){this.__storage={},this.__namespacePatternsMap=new Map,this.__namespaceLoadersCache={},this.__namespaceLoaderPromisesCache={}}setDatePostProcessorForLocale({locale:t,postProcessor:r}){this.formatDateOptions?.postProcessors.set(t,r)}setNumberPostProcessorForLocale({locale:t,postProcessor:r}){this.formatNumberOptions?.postProcessors.set(t,r)}_setupTranslationToolSupport(){this.#r=Mn.lang||null}_setHtmlLangAttribute(t){this._teardownHtmlLangAttributeObserver(),Mn.lang=t,this._setupHtmlLangAttributeObserver()}_setupHtmlLangAttributeObserver(){this._htmlLangAttributeObserver||(this._htmlLangAttributeObserver=new MutationObserver(t=>{t.forEach(r=>{this.#e?Mn.lang==="auto"?(this.#r=null,this._setHtmlLangAttribute(this.locale)):this.#r=document.documentElement.lang:this._onLocaleChanged(document.documentElement.lang,r.oldValue||"")})})),this._htmlLangAttributeObserver.observe(document.documentElement,{attributes:!0,attributeFilter:["lang"],attributeOldValue:!0})}_teardownHtmlLangAttributeObserver(){this._htmlLangAttributeObserver&&this._htmlLangAttributeObserver.disconnect()}_isNamespaceInCache(t,r){return!!(this.__storage[t]&&this.__storage[t][r])}_getCachedNamespaceLoaderPromise(t,r){return this.__namespaceLoaderPromisesCache[t]?this.__namespaceLoaderPromisesCache[t][r]:null}_loadNamespaceData(t,r,n,i){const s=this._getNamespaceLoader(r,n,i),o=this._getNamespaceLoaderPromise(s,t,i);return this._cacheNamespaceLoaderPromise(t,i,o),o.then(a=>{if(this.__namespaceLoaderPromisesCache[t]&&this.__namespaceLoaderPromisesCache[t][i]===o){const l=$$(a)?a.default:a;this.addData(t,i,l)}})}_getNamespaceLoader(t,r,n){let i=this.__namespaceLoadersCache[n];if(i||(r?(i=t[n],this.__namespaceLoadersCache[n]=i):(i=this._lookupNamespaceLoader(n),this.__namespaceLoadersCache[n]=i)),!i)throw new Error(`Namespace "${n}" was not properly setup.`);return this.__namespaceLoadersCache[n]=i,i}_getNamespaceLoaderPromise(t,r,n,i=this._fallbackLocale){return t(r,n).catch(()=>{const s=this._getLangFromLocale(r);return t(s,n).catch(()=>{if(i)return this._getNamespaceLoaderPromise(t,i,n,"").catch(()=>{const o=this._getLangFromLocale(i);throw new Error(`Data for namespace "${n}" and current locale "${r}" or fallback locale "${i}" could not be loaded. Make sure you have data either for locale "${r}" (and/or generic language "${s}") or for fallback "${i}" (and/or "${o}").`)});throw new Error(`Data for namespace "${n}" and locale "${r}" could not be loaded. Make sure you have data for locale "${r}" (and/or generic language "${s}").`)})})}_cacheNamespaceLoaderPromise(t,r,n){this.__namespaceLoaderPromisesCache[t]||(this.__namespaceLoaderPromisesCache[t]={}),this.__namespaceLoaderPromisesCache[t][r]=n}_lookupNamespaceLoader(t){for(const[r,n]of this.__namespacePatternsMap){const i=typeof r=="string"&&r===t,s=typeof r=="object"&&r.constructor.name==="RegExp"&&r.test(t);if(i||s)return n}return null}_getLangFromLocale(t){return t.substring(0,2)}_onLocaleChanged(t,r){this.dispatchEvent(new CustomEvent("__localeChanging")),t!==r&&(this._autoLoadOnLocaleChange?(this._loadAllMissing(t,r),this.loadingComplete.then(()=>{this.dispatchEvent(new CustomEvent("localeChanged",{detail:{newLocale:t,oldLocale:r}}))})):this.dispatchEvent(new CustomEvent("localeChanged",{detail:{newLocale:t,oldLocale:r}})))}_loadAllMissing(t,r){const n=this.__storage[r]||{},i=this.__storage[t]||{};Object.keys(n).forEach(s=>{i[s]||this.loadNamespace(s,{locale:t})})}_getMessageForKeys(t,r){if(typeof t=="string")return this._getMessageForKey(t,r);const n=Array.from(t).reverse();let i,s;for(;n.length;)if(i=n.pop(),s=this._getMessageForKey(i,r),s)return s}_getMessageForKey(t,r){if(!t||t.indexOf(":")===-1)throw new Error(`Namespace is missing in the key "${t}". The format for keys is "namespace:name".`);const[n,i]=t.split(":"),s=this.__storage[r],o=s?s[n]:{},l=i.split(".").reduce((u,c)=>typeof u=="object"?u[c]:u,o);return String(l||(this._showKeyAsFallback?t:""))}#n(t){if(!t.includes("-"))throw new Error(`
      Locale was set to ${t}.
      Language only locales are not allowed, please use the full language locale e.g. 'en-GB' instead of 'en'.
      See https://github.com/ing-bank/lion/issues/187 for more information.
    `)}get _supportExternalTranslationTools(){return this.#e}set _supportExternalTranslationTools(t){this.#e=t}get _langAttrSetByTranslationTool(){return this.#t}set _langAttrSetByTranslationTool(t){this.#t=t}}const Ld=Symbol.for("lion::SingletonManagerClassStorage"),Rd=globalThis||window;class D${constructor(){this._map=Rd[Ld]?Rd[Ld]:Rd[Ld]=new Map}set(t,r){this.has(t)||this._map.set(t,r)}get(t){return this._map.get(t)}has(t){return this._map.has(t)}}const gl=new D$;function oc(){if(gl.has("@lion/ui::localize::0.x"))return gl.get("@lion/ui::localize::0.x");const e=new M$({autoLoadOnLocaleChange:!0,fallbackLocale:"en-GB"});return gl.set("@lion/ui::localize::0.x",e),e}const Ao=(e,t)=>{const r=e._$AN;if(r===void 0)return!1;for(const n of r)n._$AO?.(t,!1),Ao(n,t);return!0},ac=e=>{let t,r;do{if((t=e._$AM)===void 0)break;r=t._$AN,r.delete(e),e=t}while(r?.size===0)},yE=e=>{for(let t;t=e._$AM;e=t){let r=t._$AN;if(r===void 0)t._$AN=r=new Set;else if(r.has(e))break;r.add(e),U$(t)}};function V$(e){this._$AN!==void 0?(ac(this),this._$AM=e,yE(this)):this._$AM=e}function B$(e,t=!1,r=0){const n=this._$AH,i=this._$AN;if(i!==void 0&&i.size!==0)if(t)if(Array.isArray(n))for(let s=r;s<n.length;s++)Ao(n[s],!1),ac(n[s]);else n!=null&&(Ao(n,!1),ac(n));else Ao(this,e)}const U$=e=>{e.type==Iv.CHILD&&(e._$AP??=B$,e._$AQ??=V$)};class z$ extends Fv{constructor(){super(...arguments),this._$AN=void 0}_$AT(t,r,n){super._$AT(t,r,n),yE(this),this.isConnected=t._$AU}_$AO(t,r=!0){t!==this.isConnected&&(this.isConnected=t,t?this.reconnected?.():this.disconnected?.()),r&&(Ao(this,t),ac(this))}setValue(t){if(v$(this._$Ct))this._$Ct._$AI(t,this);else{const r=[...this._$Ct._$AH];r[this._$Ci]=t,this._$Ct._$AI(r,this,0)}}disconnected(){}reconnected(){}}let q$=class{constructor(t){this.G=t}disconnect(){this.G=void 0}reconnect(t){this.G=t}deref(){return this.G}},H$=class{constructor(){this.Y=void 0,this.Z=void 0}get(){return this.Y}pause(){this.Y??=new Promise((t=>this.Z=t))}resume(){this.Z?.(),this.Y=this.Z=void 0}};const iv=e=>!b$(e)&&typeof e.then=="function",sv=1073741823;let j$=class extends z${constructor(){super(...arguments),this._$Cwt=sv,this._$Cbt=[],this._$CK=new q$(this),this._$CX=new H$}render(...t){return t.find((r=>!iv(r)))??Qd}update(t,r){const n=this._$Cbt;let i=n.length;this._$Cbt=r;const s=this._$CK,o=this._$CX;this.isConnected||this.disconnected();for(let a=0;a<r.length&&!(a>this._$Cwt);a++){const l=r[a];if(!iv(l))return this._$Cwt=a,l;a<i&&l===n[a]||(this._$Cwt=sv,i=0,Promise.resolve(l).then((async u=>{for(;o.get();)await o.get();const c=s.deref();if(c!==void 0){const d=c._$Cbt.indexOf(l);d>-1&&d<c._$Cwt&&(c._$Cwt=d,c.setValue(u))}})))}return Qd}disconnected(){this._$CK.disconnect(),this._$CX.pause()}reconnected(){this._$CK.reconnect(this),this._$CX.resume()}};const W$=Lv(j$),K$=e=>class extends e{static get localizeNamespaces(){return[]}static get waitForLocalizeNamespaces(){return!0}constructor(){super(),this._localizeManager=oc(),this.__boundLocalizeOnLocaleChanged=(...r)=>{const n=Array.from(r)[0];this.__localizeOnLocaleChanged(n)},this.__boundLocalizeOnLocaleChanging=()=>{this.__localizeOnLocaleChanging()},this.__localizeStartLoadingNamespaces(),this.localizeNamespacesLoaded&&this.localizeNamespacesLoaded.then(()=>{this.__localizeMessageSync=!0})}async scheduleUpdate(){Object.getPrototypeOf(this).constructor.waitForLocalizeNamespaces&&await this.localizeNamespacesLoaded,super.scheduleUpdate()}connectedCallback(){super.connectedCallback(),this.localizeNamespacesLoaded&&this.localizeNamespacesLoaded.then(()=>this.onLocaleReady()),this._localizeManager.addEventListener("__localeChanging",this.__boundLocalizeOnLocaleChanging),this._localizeManager.addEventListener("localeChanged",this.__boundLocalizeOnLocaleChanged)}disconnectedCallback(){super.disconnectedCallback(),this._localizeManager.removeEventListener("__localeChanging",this.__boundLocalizeOnLocaleChanging),this._localizeManager.removeEventListener("localeChanged",this.__boundLocalizeOnLocaleChanged)}msgLit(r,n,i){return this.__localizeMessageSync?this._localizeManager.msg(r,n,i):this.localizeNamespacesLoaded?W$(this.localizeNamespacesLoaded.then(()=>this._localizeManager.msg(r,n,i)),pt):""}__getUniqueNamespaces(){const r=[],n=new Set;return Object.getPrototypeOf(this).constructor.localizeNamespaces.forEach(n.add.bind(n)),n.forEach(i=>{r.push(i)}),r}__localizeStartLoadingNamespaces(){this.localizeNamespacesLoaded=this._localizeManager.loadNamespaces(this.__getUniqueNamespaces())}__localizeOnLocaleChanging(){this.__localizeStartLoadingNamespaces()}__localizeOnLocaleChanged(r){this.onLocaleChanged(r.detail.newLocale,r.detail.oldLocale)}onLocaleReady(){this.onLocaleUpdated()}onLocaleChanged(r,n){this.onLocaleUpdated(),this.requestUpdate()}onLocaleUpdated(){}},Qc=He(K$),Xh="3.0.0",ov=window.scopedElementsVersions||(window.scopedElementsVersions=[]);ov.includes(Xh)||ov.push(Xh);const G$=e=>class extends e{static scopedElements;static get scopedElementsVersion(){return Xh}static __registry;get registry(){return this.constructor.__registry}set registry(r){this.constructor.__registry=r}attachShadow(r){const{scopedElements:n}=this.constructor;if(!this.registry||this.registry===this.constructor.__registry&&!Object.prototype.hasOwnProperty.call(this.constructor,"__registry")){this.registry=new CustomElementRegistry;for(const[s,o]of Object.entries(n??{}))this.registry.define(s,o)}return super.attachShadow({...r,customElements:this.registry,registry:this.registry})}},Y$=He(G$),X$=e=>class extends Y$(e){createRenderRoot(){const{shadowRootOptions:r,elementStyles:n}=this.constructor,i=this.attachShadow(r);return this.renderOptions.creationScope=i,Pv(i,n),this.renderOptions.renderBefore??=i.firstChild,i}},J$=He(X$);function Ga(){return!!(globalThis.ShadowRoot?.prototype.createElement&&globalThis.ShadowRoot?.prototype.importNode)}const Q$=e=>class extends J$(e){constructor(){super()}createScopedElement(r){return(Ga()?this.shadowRoot:document).createElement(r)}defineScopedElement(r,n){const i=this.registry.get(r),s=i&&i!==n;return!Ga()&&s&&console.error([`You are trying to re-register the "${r}" custom element with a different class via ScopedElementsMixin.`,"This is only possible with a CustomElementRegistry.","Your browser does not support this feature so you will need to load a polyfill for it.",'Load "@webcomponents/scoped-custom-element-registry" before you register ANY web component to the global customElements registry.','e.g. add "<script src="/node_modules/@webcomponents/scoped-custom-element-registry/scoped-custom-element-registry.min.js"><\/script>" as your first script tag.',"For more details you can visit https://open-wc.org/docs/development/scoped-elements/"].join(`
`)),i?this.registry.get(r):this.registry.define(r,n)}attachShadow(r){const{scopedElements:n}=this.constructor;if(!this.registry||this.registry===this.constructor.__registry&&!Object.prototype.hasOwnProperty.call(this.constructor,"__registry")){this.registry=Ga()?new CustomElementRegistry:customElements;for(const[s,o]of Object.entries(n??{}))this.defineScopedElement(s,o)}return Element.prototype.attachShadow.call(this,{...r,customElements:this.registry,registry:this.registry})}createRenderRoot(){const{shadowRootOptions:r,elementStyles:n}=this.constructor,i=this.attachShadow(r);return Ga()&&(this.renderOptions.creationScope=i),i instanceof ShadowRoot&&(Pv(i,n),this.renderOptions.renderBefore=this.renderOptions.renderBefore||i.firstChild),i}},Ea=He(Q$);class Z${constructor(){this.__running=!1,this.__queue=[]}add(t){this.__queue.push(t),this.__running||(this.complete=new Promise(r=>{this.__callComplete=r}),this.__run())}async __run(){this.__running=!0,await this.__queue[0](),this.__queue.shift(),this.__queue.length>0?this.__run():(this.__running=!1,this.__callComplete&&this.__callComplete())}}function eM(e){return e.charAt(0).toUpperCase()+e.slice(1)}const tM=e=>class extends e{constructor(){super(),this.__SyncUpdatableNamespace={}}firstUpdated(t){super.firstUpdated(t),this.__syncUpdatableInitialize()}connectedCallback(){super.connectedCallback(),this.__SyncUpdatableNamespace.connected=!0}disconnectedCallback(){super.disconnectedCallback(),this.__SyncUpdatableNamespace.connected=!1}static enabledWarnings=super.enabledWarnings?.filter(t=>t!=="change-in-update")||[];static __syncUpdatableHasChanged(t,r,n){const i=this.elementProperties;return i.get(t)&&i.get(t).hasChanged?i.get(t).hasChanged(r,n):r!==n}__syncUpdatableInitialize(){const t=this.__SyncUpdatableNamespace,r=this.constructor;t.initialized=!0,t.queue&&Array.from(t.queue).forEach(n=>{r.__syncUpdatableHasChanged(n,this[n],void 0)&&this.updateSync(n,void 0)})}requestUpdate(t,r,n){if(super.requestUpdate(t,r,n),t===void 0)return;this.__SyncUpdatableNamespace=this.__SyncUpdatableNamespace||{};const i=this.__SyncUpdatableNamespace,s=this.constructor;i.initialized?s.__syncUpdatableHasChanged(t,this[t],r)&&this.updateSync(t,r):(i.queue=i.queue||new Set,i.queue.add(t))}updateSync(t,r){}},rM=He(tM),nM=e=>{switch(e){case"bg-BG":return Y(()=>import("./bg-BG2.js"),__vite__mapDeps([0,1]),import.meta.url);case"bg":return Y(()=>import("./bg3.js"),[],import.meta.url);case"cs-CZ":return Y(()=>import("./cs-CZ2.js"),__vite__mapDeps([2,3]),import.meta.url);case"cs":return Y(()=>import("./cs3.js"),[],import.meta.url);case"de-DE":return Y(()=>import("./de-DE2.js"),__vite__mapDeps([4,5]),import.meta.url);case"de":return Y(()=>import("./de3.js"),[],import.meta.url);case"en-AU":return Y(()=>import("./en-AU2.js"),__vite__mapDeps([6,7]),import.meta.url);case"en-GB":return Y(()=>import("./en-GB2.js"),__vite__mapDeps([8,7]),import.meta.url);case"en-US":return Y(()=>import("./en-US2.js"),__vite__mapDeps([9,7]),import.meta.url);case"en-PH":case"en":return Y(()=>import("./en3.js"),[],import.meta.url);case"es-ES":return Y(()=>import("./es-ES2.js"),__vite__mapDeps([10,11]),import.meta.url);case"es":return Y(()=>import("./es3.js"),[],import.meta.url);case"fr-FR":return Y(()=>import("./fr-FR2.js"),__vite__mapDeps([12,13]),import.meta.url);case"fr-BE":return Y(()=>import("./fr-BE2.js"),__vite__mapDeps([14,13]),import.meta.url);case"fr":return Y(()=>import("./fr3.js"),[],import.meta.url);case"hu-HU":return Y(()=>import("./hu-HU2.js"),__vite__mapDeps([15,16]),import.meta.url);case"hu":return Y(()=>import("./hu3.js"),[],import.meta.url);case"it-IT":return Y(()=>import("./it-IT2.js"),__vite__mapDeps([17,18]),import.meta.url);case"it":return Y(()=>import("./it3.js"),[],import.meta.url);case"nl-BE":return Y(()=>import("./nl-BE2.js"),__vite__mapDeps([19,20]),import.meta.url);case"nl-NL":return Y(()=>import("./nl-NL2.js"),__vite__mapDeps([21,20]),import.meta.url);case"nl":return Y(()=>import("./nl3.js"),[],import.meta.url);case"pl-PL":return Y(()=>import("./pl-PL2.js"),__vite__mapDeps([22,23]),import.meta.url);case"pl":return Y(()=>import("./pl3.js"),[],import.meta.url);case"ro-RO":return Y(()=>import("./ro-RO2.js"),__vite__mapDeps([24,25]),import.meta.url);case"ro":return Y(()=>import("./ro3.js"),[],import.meta.url);case"ru-RU":return Y(()=>import("./ru-RU2.js"),__vite__mapDeps([26,27]),import.meta.url);case"ru":return Y(()=>import("./ru3.js"),[],import.meta.url);case"sk-SK":return Y(()=>import("./sk-SK2.js"),__vite__mapDeps([28,29]),import.meta.url);case"sk":return Y(()=>import("./sk3.js"),[],import.meta.url);case"tr-TR":return Y(()=>import("./tr-TR.js"),__vite__mapDeps([30,31]),import.meta.url);case"tr":return Y(()=>import("./tr.js"),[],import.meta.url);case"uk-UA":return Y(()=>import("./uk-UA2.js"),__vite__mapDeps([32,33]),import.meta.url);case"uk":return Y(()=>import("./uk3.js"),[],import.meta.url);case"zh-CN":case"zh":return Y(()=>import("./zh3.js"),[],import.meta.url);default:return Y(()=>import("./en3.js"),[],import.meta.url)}},iM=e=>`${e[0].toUpperCase()}${e.slice(1)}`;class _E extends Qc(Ne){static get properties(){return{feedbackData:{attribute:!1}}}static localizeNamespaces=[{"lion-form-core":nM},...super.localizeNamespaces];static get styles(){return[te`
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
            ${t&&r?this._localizeManager.msg(`lion-form-core:validation${iM(r)}`):pt}
          </div>
          ${this._messageTemplate({message:t,type:r,validator:n})}
        `)}
    `}}class Ri{constructor(t){this.type="unparseable",this.viewValue=t}toString(){return JSON.stringify({type:this.type,viewValue:this.viewValue})}}const sM=[Node.DOCUMENT_POSITION_PRECEDING,Node.DOCUMENT_POSITION_CONTAINS,Node.DOCUMENT_POSITION_CONTAINS|Node.DOCUMENT_POSITION_PRECEDING];function wE(e,{reverse:t}={}){const r=(i,s)=>{const o=i.compareDocumentPosition(s);return sM.includes(o)?1:-1},n=e.filter(i=>i);return n.sort(r),t&&n.reverse(),n}const oM=e=>class extends e{constructor(){super(),this.name="",this._parentFormGroup=void 0,this.allowCrossRootRegistration=!1}get name(){return this.__name||""}set name(t){const r=this.name;this.__name=t.toString(),this.requestUpdate("name",r)}static get properties(){return{name:{type:String,reflect:!0},allowCrossRootRegistration:{type:Boolean,attribute:"allow-cross-root-registration"}}}connectedCallback(){super.connectedCallback(),this.dispatchEvent(new CustomEvent("form-element-register",{detail:{element:this},bubbles:!0,composed:!!this.allowCrossRootRegistration}))}disconnectedCallback(){super.disconnectedCallback(),this.__unregisterFormElement()}__unregisterFormElement(){this._parentFormGroup&&this._parentFormGroup.removeFormElement(this)}},Rp=He(oM),aM=e=>class extends Rp(ya(Ms(e))){static get properties(){return{readOnly:{type:Boolean,attribute:"readonly",reflect:!0},label:String,labelSrOnly:{type:Boolean,attribute:"label-sr-only",reflect:!0},helpText:{type:String,attribute:"help-text"},modelValue:{attribute:!1},_ariaLabelledNodes:{attribute:!1},_ariaDescribedNodes:{attribute:!1},_repropagationRole:{attribute:!1},_isRepropagationEndpoint:{attribute:!1}}}get label(){return this.__label??(this._labelNode?.textContent||"")}set label(r){const n=this.label;this.__label=r,this.requestUpdate("label",n)}get helpText(){return this.__helpText??(this._helpTextNode?.textContent||"")}set helpText(r){const n=this.helpText;this.__helpText=r,this.requestUpdate("helpText",n)}get fieldName(){return this.__fieldName||this.label||this.name||""}set fieldName(r){this.__fieldName=r}get slots(){return{...super.slots,label:()=>{const r=document.createElement("label");return r.textContent=this.label,r},"help-text":()=>{const r=document.createElement("div");return r.textContent=this.helpText,r}}}get _inputNode(){return this.__getDirectSlotChild("input")}get _labelNode(){return this.__getDirectSlotChild("label")}get _helpTextNode(){return this.__getDirectSlotChild("help-text")}get _feedbackNode(){return this.__getDirectSlotChild("feedback")}static enabledWarnings=super.enabledWarnings?.filter(r=>r!=="change-in-update")||[];constructor(){super(),this.readOnly=!1,this.labelSrOnly=!1,this._inputId=_a(this.localName),this._ariaLabelledNodes=[],this._ariaDescribedNodes=[],this._repropagationRole="child",this._isRepropagationEndpoint=!1,this.addEventListener("model-value-changed",this.__repropagateChildrenValues),this._onLabelClick=this._onLabelClick.bind(this)}connectedCallback(){super.connectedCallback(),this._enhanceLightDomClasses(),this._enhanceLightDomA11y(),this._triggerInitialModelValueChangedEvent(),this._labelNode&&this._labelNode.addEventListener("click",this._onLabelClick)}disconnectedCallback(){super.disconnectedCallback(),this._labelNode&&this._labelNode.removeEventListener("click",this._onLabelClick)}updated(r){super.updated(r),r.has("disabled")&&this._inputNode?.setAttribute("aria-disabled",`${!!this.disabled}`),r.has("_ariaLabelledNodes")&&this.__reflectAriaAttr("aria-labelledby",this._ariaLabelledNodes,this.__reorderAriaLabelledNodes),r.has("_ariaDescribedNodes")&&this.__reflectAriaAttr("aria-describedby",this._ariaDescribedNodes,this.__reorderAriaDescribedNodes),r.has("label")&&this.__label!==void 0&&this._labelNode&&(this._labelNode.textContent=this.label),r.has("helpText")&&this.__helpText!==void 0&&this._helpTextNode&&(this._helpTextNode.textContent=this.helpText),r.has("name")&&this.dispatchEvent(new CustomEvent("form-element-name-changed",{detail:{oldName:r.get("name"),newName:this.name},bubbles:!0}))}_triggerInitialModelValueChangedEvent(){this._dispatchInitialModelValueChangedEvent()}_enhanceLightDomClasses(){this._inputNode&&this._inputNode.classList.add("form-control")}_enhanceLightDomA11y(){const{_inputNode:r,_labelNode:n,_helpTextNode:i,_feedbackNode:s}=this;r&&(r.id=r.id||this._inputId),n&&(n.setAttribute("for",this._inputId),this.addToAriaLabelledBy(n,{idPrefix:"label"})),i&&this.addToAriaDescribedBy(i,{idPrefix:"help-text"}),s&&(this.addEventListener("focusin",()=>{s.setAttribute("aria-live","polite")}),this.addEventListener("focusout",()=>{s.setAttribute("aria-live","assertive")}),this.addToAriaDescribedBy(s,{idPrefix:"feedback"})),this._enhanceLightDomA11yForAdditionalSlots()}_enhanceLightDomA11yForAdditionalSlots(r=["prefix","suffix","before","after"]){r.forEach(n=>{const i=this.__getDirectSlotChild(n);i&&(i.hasAttribute("data-label")&&this.addToAriaLabelledBy(i,{idPrefix:n}),i.hasAttribute("data-description")&&this.addToAriaDescribedBy(i,{idPrefix:n}))})}__reflectAriaAttr(r,n,i){if(this._inputNode){if(i){const o=n.filter(d=>this.contains(d)),a=n.filter(d=>!this.contains(d)),l=o.map(d=>d.assignedSlot||d),u=[...wE(l)],c=[];u.forEach(d=>{o.forEach(p=>{d.name===p.slot&&c.push(p)})}),n=[...c,...a]}const s=n.map(o=>o.id).join(" ");this._inputNode.setAttribute(r,s)}}render(){return W`
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
          `:pt}_inputGroupInputTemplate(){return W`
        <div class="input-group__input">
          <slot name="input"></slot>
        </div>
      `}_inputGroupSuffixTemplate(){return Array.from(this.children).find(r=>r.slot==="suffix")?W`
            <div class="input-group__suffix">
              <slot name="suffix"></slot>
            </div>
          `:pt}_inputGroupAfterTemplate(){return W`
        <div class="input-group__after">
          <slot name="after"></slot>
        </div>
      `}_feedbackTemplate(){return W`
        <div class="form-field__feedback">
          <slot name="feedback"></slot>
        </div>
      `}_isEmpty(r=this.modelValue){let n=r;if(this.modelValue instanceof Ri&&(n=this.modelValue.viewValue),typeof n=="object"&&n!==null&&!(n instanceof Date))return!Object.keys(n).length;const i=typeof n=="number"&&(n===0||Number.isNaN(n));return!n&&!i&&!(typeof n=="boolean"&&n===!1)}static get styles(){return[te`
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
        `]}_getAriaDescriptionElements(){return[this._helpTextNode,this._feedbackNode]}addToAriaLabelledBy(r,{idPrefix:n="",reorder:i=!0}={}){r.id=r.id||`${n}-${this._inputId}`,this._ariaLabelledNodes.includes(r)||(this._ariaLabelledNodes=[...this._ariaLabelledNodes,r],this.__reorderAriaLabelledNodes=!!i)}removeFromAriaLabelledBy(r){this._ariaLabelledNodes.includes(r)&&(this._ariaLabelledNodes.splice(this._ariaLabelledNodes.indexOf(r),1),this._ariaLabelledNodes=[...this._ariaLabelledNodes],this.__reorderAriaLabelledNodes=!1)}addToAriaDescribedBy(r,{idPrefix:n="",reorder:i=!0}={}){r.id=r.id||`${n}-${this._inputId}`,this._ariaDescribedNodes.includes(r)||(this._ariaDescribedNodes=[...this._ariaDescribedNodes,r],this.__reorderAriaDescribedNodes=!!i)}removeFromAriaDescribedBy(r){this._ariaDescribedNodes.includes(r)&&(this._ariaDescribedNodes.splice(this._ariaDescribedNodes.indexOf(r),1),this._ariaDescribedNodes=[...this._ariaDescribedNodes],this.__reorderAriaLabelledNodes=!1)}__getDirectSlotChild(r){return Array.from(this.children).find(n=>n.slot===r)}_dispatchInitialModelValueChangedEvent(){this._repropagationRole!=="child"&&(this.__repropagateChildrenInitialized=!0,this.dispatchEvent(new CustomEvent("model-value-changed",{bubbles:!0,detail:{formPath:[this],initialize:!0,isTriggeredByUser:!1}})))}_onBeforeRepropagateChildrenValues(r){}__repropagateChildrenValues(r){this._onBeforeRepropagateChildrenValues(r);const n=r.detail&&r.detail.element||r.target,i=this._isRepropagationEndpoint||this._repropagationRole==="choice-group";if(n===this)return;r.stopImmediatePropagation();const o=this._repropagationRole!=="child"&&!this.__repropagateChildrenInitialized,a=r.detail&&r.detail.initialize;if(o||a||!this._repropagationCondition(n))return;let l=[];i||(l=r.detail&&r.detail.formPath||[n]);const u=[...l,this];this.dispatchEvent(new CustomEvent("model-value-changed",{bubbles:!0,detail:{formPath:u,isTriggeredByUser:!!r.detail?.isTriggeredByUser}}))}_repropagationCondition(r){return!!r}_onLabelClick(){}},Ui=He(aM);class Zc extends EventTarget{constructor(t,r){super(),this.__param=t,this.__config=r||{},this.type=r?.type||"error"}static _$isValidator$=!0;static validatorName="";static async=!1;execute(t,r,n){if(!this.constructor.validatorName)throw new Error(`A validator needs to have a name! Please set it via "static get validatorName() { return 'IsCat'; }"`);return!0}set param(t){this.__param=t,this.dispatchEvent(new Event("param-changed"))}get param(){return this.__param}set config(t){this.__config=t,this.dispatchEvent(new Event("config-changed"))}get config(){return this.__config}async _getMessage(t){const r=this.constructor,n={name:r.validatorName,type:this.type,params:this.param,config:this.config,...t};if(this.config.getMessage){if(typeof this.config.getMessage=="function")return this.config.getMessage(n);throw new Error(`You must provide a value for getMessage of type 'function', you provided a value of type: ${typeof this.config.getMessage}`)}return r.getMessage(n)}static async getMessage(t){return`Please configure an error message for "${this.name}" by overriding "static async getMessage()"`}onFormControlConnect(t){}onFormControlDisconnect(t){}abortExecution(){}}function av(e=[],t=[]){return e.filter(r=>!t.includes(r)).concat(t.filter(r=>!e.includes(r)))}function lM(e){return e instanceof Ri?e.viewValue:e}const cM=e=>class extends Ui(rM(ya(Ms(Ea(e))))){static get scopedElements(){return{...super.scopedElements,"lion-validation-feedback":_E}}static get properties(){return{validators:{attribute:!1},hasFeedbackFor:{attribute:!1},shouldShowFeedbackFor:{attribute:!1},showsFeedbackFor:{type:Array,attribute:"shows-feedback-for",reflect:!0,converter:{fromAttribute:t=>t.split(","),toAttribute:t=>t.join(",")}},validationStates:{attribute:!1},isPending:{type:Boolean,attribute:"is-pending",reflect:!0},defaultValidators:{attribute:!1},_visibleMessagesAmount:{attribute:!1},__childModelValueChanged:{attribute:!1}}}static get validationTypes(){return["error"]}get operationMode(){return"enter"}get slots(){return{...super.slots,feedback:()=>{const t=this.createScopedElement("lion-validation-feedback");return t.setAttribute("data-tag-name","lion-validation-feedback"),t}}}get _allValidators(){return[...this.validators,...this.defaultValidators]}constructor(){super(),this.hasFeedbackFor=[],this.showsFeedbackFor=[],this.shouldShowFeedbackFor=[],this.validationStates={},this.isPending=!1,this.validators=[],this.defaultValidators=[],this._visibleMessagesAmount=1,this.__syncValidationResult=[],this.__asyncValidationResult=[],this.__validationResult=[],this.__prevValidationResult=[],this.__prevShownValidationResult=[],this.__childModelValueChanged=!1,this._onValidatorUpdated=this._onValidatorUpdated.bind(this),this._updateFeedbackComponent=this._updateFeedbackComponent.bind(this)}connectedCallback(){super.connectedCallback(),oc().addEventListener("localeChanged",this._updateFeedbackComponent)}disconnectedCallback(){super.disconnectedCallback(),oc().removeEventListener("localeChanged",this._updateFeedbackComponent)}firstUpdated(t){super.firstUpdated(t),this.__isValidateInitialized=!0,this.validate(),this._repropagationRole!=="child"&&this.addEventListener("model-value-changed",()=>{this.__childModelValueChanged=!0})}updateSync(t,r){if(super.updateSync(t,r),t==="validators"?(this.__setupValidators(),this.validate({clearCurrentResult:!0})):t==="modelValue"&&this.validate({clearCurrentResult:!0}),["touched","dirty","prefilled","focused","submitted","hasFeedbackFor","filled"].includes(t)&&this._updateShouldShowFeedbackFor(),t==="showsFeedbackFor"){this._inputNode&&this._inputNode.setAttribute("aria-invalid",`${this._hasFeedbackVisibleFor("error")}`);const n=av(this.showsFeedbackFor,r);n.length>0&&this.dispatchEvent(new Event("showsFeedbackForChanged",{bubbles:!0})),n.forEach(i=>{this.dispatchEvent(new Event(`showsFeedbackFor${eM(i)}Changed`,{bubbles:!0}))})}t==="shouldShowFeedbackFor"&&av(this.shouldShowFeedbackFor,r).length>0&&this.dispatchEvent(new Event("shouldShowFeedbackForChanged",{bubbles:!0}))}async validate({clearCurrentResult:t=!1}={}){if(this.validateComplete=new Promise(r=>{this.__validateCompleteResolve=r}),this.disabled){this.__clearValidationResults(),this.__finishValidationPass(),this._updateFeedbackComponent();return}this.__isValidateInitialized&&(this.__prevValidationResult=this.__validationResult,t&&this.__clearValidationResults(),await this.__executeValidators())}#e(t){let r=t;for(;r;){if(r.constructor.validatorName==="Required")return!0;r=Object.getPrototypeOf(r)}return!1}async __executeValidators(){const t=lM(this.modelValue),r=this.__isEmpty(t);if(this.__syncValidationResult=[],r){const a=!this._isFormOrFieldset,l=this._allValidators.find(u=>u.constructor?.validatorName==="Required");if(l&&(this.__syncValidationResult=[{validator:l,outcome:!0}]),a){this.__finishValidationPass({syncValidationResult:this.__syncValidationResult});return}}const n=[],i=[],s=[];for(const a of this._allValidators)a?.executeOnResults?n.push(a):this.#e(a)||(a.constructor.async?s.push(a):i.push(a));const o=!!s.length;this.__syncValidationResult=[...this.__syncValidationResult,...this.__executeSyncValidators(i,t)],this.__finishValidationPass({syncValidationResult:this.__syncValidationResult,metaValidators:n}),o?(this.isPending=!0,this.__asyncValidationResult=await this.__executeAsyncValidators(s,t),this.isPending=!1,this.__finishValidationPass({syncValidationResult:this.__syncValidationResult,asyncValidationResult:this.__asyncValidationResult,metaValidators:n}),this.__validateCompleteResolve?.(!0)):this.__validateCompleteResolve?.(!0)}__executeSyncValidators(t,r){return t.map(n=>({validator:n,outcome:n.execute(r,n.param,{node:this})})).filter(n=>!!n.outcome)}async __executeAsyncValidators(t,r){const n=t.map(s=>s.execute(r,s.param,{node:this})),i=await Promise.all(n);return i.map((s,o)=>({validator:t[o],outcome:i[o]})).filter(s=>!!s.outcome)}__executeMetaValidators(t,r){return r.length?this._isEmpty(this.modelValue)?(this.__prevShownValidationResult=[],[]):r.map(n=>({validator:n,outcome:n.executeOnResults({regularValidationResult:t.map(i=>i.validator),prevValidationResult:this.__prevValidationResult.map(i=>i.validator),prevShownValidationResult:this.__prevShownValidationResult.map(i=>i.validator)})})).filter(n=>!!n.outcome):[]}__finishValidationPass({syncValidationResult:t=[],asyncValidationResult:r=[],metaValidators:n=[]}={}){const i=[...t,...r],s=this.__executeMetaValidators(i,n);this.__validationResult=[...s,...i];const a=this.constructor.validationTypes.reduce((l,u)=>({...l,[u]:{}}),{});for(const{validator:l,outcome:u}of this.__validationResult){a[l.type]||(a[l.type]={});const c=l.constructor;a[l.type][c.validatorName]=u}this.validationStates=a,this.hasFeedbackFor=[...new Set(this.__validationResult.map(({validator:l})=>l.type))],this.dispatchEvent(new Event("validate-performed",{bubbles:!0}))}__clearValidationResults(){this.__syncValidationResult=[],this.__asyncValidationResult=[]}_onValidatorUpdated(t){(t.type==="param-changed"||t.type==="config-changed")&&this.validate()}__setupValidators(){const t=["param-changed","config-changed"];for(const r of this.__prevValidators||[]){for(const n of t)r.removeEventListener?.(n,this._onValidatorUpdated);r.onFormControlDisconnect(this)}for(const r of this._allValidators){if(r.constructor._$isValidator$===void 0){const a=`Validators array only accepts class instances of Validator. Type "${Array.isArray(r)?"array":typeof r}" found. This may be caused by having multiple installations of "@lion/ui/form-core.js".`;throw console.error(a,this),new Error(a)}const i=this.constructor,s=r.constructor;if(i.validationTypes.indexOf(r.type)===-1){const o=`This component does not support the validator type "${r.type}" used in "${s.validatorName}". You may change your validators type or add it to the components "static get validationTypes() {}".`;throw console.error(o,this),new Error(o)}for(const o of t)r.addEventListener?.(o,a=>{this._onValidatorUpdated(a,{validator:r})});r.onFormControlConnect(this)}this.__prevValidators=this._allValidators}__isEmpty(t){return typeof this._isEmpty=="function"?this._isEmpty(t):this.modelValue===null||typeof this.modelValue>"u"||this.modelValue===""}async __getFeedbackMessages(t){let r=await this.fieldName;return Promise.all(t.map(async({validator:n,outcome:i})=>(n.config.fieldName&&(r=await n.config.fieldName),{message:await n._getMessage({modelValue:this.modelValue,formControl:this,fieldName:r,outcome:i}),type:n.type,validator:n,visibilityDuration:n.config?.visibilityDuration||3e3})))}_updateFeedbackComponent(){window.clearTimeout(this.removeMessage);const{_feedbackNode:t}=this;t&&(this.__feedbackQueue||(this.__feedbackQueue=new Z$),this.showsFeedbackFor.length>0?this.__feedbackQueue.add(async()=>{const r=this._prioritizeAndFilterFeedback({validationResult:this.__validationResult.map(i=>i.validator)});this.__prioritizedResult=r.map(i=>this.__validationResult.find(o=>i===o.validator)).filter(Boolean),this.__prioritizedResult.length>0&&(this.__prevShownValidationResult=this.__prioritizedResult);const n=await this.__getFeedbackMessages(this.__prioritizedResult);t.feedbackData=n||[],n?.[0]&&n[0].type==="success"&&n[0].visibilityDuration!==1/0&&(this.removeMessage=window.setTimeout(()=>{t.removeAttribute("type"),t.feedbackData=[]},n[0].visibilityDuration))}):this.__feedbackQueue.add(async()=>{t.feedbackData=[]}),this.feedbackComplete=this.__feedbackQueue.complete)}_showFeedbackConditionFor(t,r){return!0}get _feedbackConditionMeta(){return{modelValue:this.modelValue,el:this}}feedbackCondition(t,r=this._feedbackConditionMeta,n=this._showFeedbackConditionFor.bind(this)){return n(t,r)}_hasFeedbackVisibleFor(t){return this.hasFeedbackFor?.includes(t)&&this.shouldShowFeedbackFor?.includes(t)}updated(t){if(super.updated(t),t.has("shouldShowFeedbackFor")||t.has("hasFeedbackFor")){const r=this.constructor;this.showsFeedbackFor=r.validationTypes.map(n=>this._hasFeedbackVisibleFor(n)?n:void 0).filter(Boolean),this._updateFeedbackComponent()}if(t.has("__childModelValueChanged")&&this.__childModelValueChanged&&(this.validate({clearCurrentResult:!0}),this.__childModelValueChanged=!1),t.has("validationStates")){const r=t.get("validationStates");r&&Object.entries(this.validationStates).forEach(([n,i])=>{r[n]&&JSON.stringify(i)!==JSON.stringify(r[n])&&this.dispatchEvent(new CustomEvent(`${n}StateChanged`,{detail:i}))})}}_updateShouldShowFeedbackFor(){const r=this.constructor.validationTypes.map(n=>this.feedbackCondition(n,this._feedbackConditionMeta,this._showFeedbackConditionFor.bind(this))?n:void 0).filter(Boolean);JSON.stringify(this.shouldShowFeedbackFor)!==JSON.stringify(r)&&(this.shouldShowFeedbackFor=r)}_prioritizeAndFilterFeedback({validationResult:t}){const n=this.constructor.validationTypes;return t.filter(s=>this.feedbackCondition(s.type,this._feedbackConditionMeta,this._showFeedbackConditionFor.bind(this))).sort((s,o)=>n.indexOf(s.type)-n.indexOf(o.type)).slice(0,this._visibleMessagesAmount)}},xa=He(cM),uM=e=>class extends xa(Ui(e)){static get properties(){return{formattedValue:{attribute:!1},serializedValue:{attribute:!1},formatOptions:{attribute:!1}}}#e={didFormatterOutputSyncToView:!1,didFormatterRun:!1};requestUpdate(r,n,i){super.requestUpdate(r,n,i),r==="modelValue"&&this.modelValue!==n&&this._onModelValueChanged({modelValue:this.modelValue},{modelValue:n}),r==="serializedValue"&&this.serializedValue!==n&&this._calculateValues({source:"serialized"}),r==="formattedValue"&&this.formattedValue!==n&&this._calculateValues({source:"formatted"})}get value(){return this._inputNode?.value||this.__value||""}set value(r){this._inputNode?(this._inputNode.value=r,this.__value=void 0):this.__value=r}preprocessor(r,n){}parser(r,n){return r}formatter(r,n){return r}serializer(r){return r!==void 0?r:""}deserializer(r){return r===void 0?"":r}_calculateValues({source:r}={source:null}){this.__preventRecursiveTrigger||(this.__preventRecursiveTrigger=!0,r!=="model"&&(r==="serialized"?this.modelValue=this.deserializer(this.serializedValue):r==="formatted"&&(this.modelValue=this._callParser())),r!=="formatted"&&(this.formattedValue=this._callFormatter()),r!=="serialized"&&(this.serializedValue=this.serializer(this.modelValue)),this._reflectBackFormattedValueToUser(),this.__preventRecursiveTrigger=!1,this.__prevViewValue=this.value)}_callParser(r=this.formattedValue){if(r==="")return"";if(typeof r!="string")return;const n=this.parser(r,{...this.formatOptions,mode:this.#t(),viewValueStates:this.#r()});return n!==void 0?n:new Ri(r)}_callFormatter(){return this.#e.didFormatterRun=!1,this._isHandlingUserInput&&this.hasFeedbackFor?.includes("error")&&this._inputNode?this.value:this.modelValue instanceof Ri?this.modelValue.viewValue:(this.#e.didFormatterRun=!0,this.formatter(this.modelValue,{...this.formatOptions,mode:this.#t(),viewValueStates:this.#r()}))}_onModelValueChanged(...r){this._calculateValues({source:"model"}),this._dispatchModelValueChangedEvent(...r)}_dispatchModelValueChangedEvent(...r){this.dispatchEvent(new CustomEvent("model-value-changed",{bubbles:!0,detail:{formPath:[this],isTriggeredByUser:!!this._isHandlingUserInput}}))}_syncValueUpwards(){this.__isHandlingComposition||this.__handlePreprocessor();const r=this.formattedValue;this.modelValue=this._callParser(this.value),r===this.formattedValue&&this.__prevViewValue!==this.value&&this._calculateValues()}__handlePreprocessor(){let r=this.value.length;this._inputNode&&"selectionStart"in this._inputNode&&this._inputNode?.type!=="range"&&(r=this._inputNode.selectionStart);const n=this.preprocessor(this.value,{...this.formatOptions,currentCaretIndex:r,prevViewValue:this.__prevViewValue});if(n!==void 0){if(typeof n=="string")this.value=n;else if(typeof n=="object"){const{viewValue:i,caretIndex:s}=n;this.value=i,s&&this._inputNode&&"selectionStart"in this._inputNode&&(this._inputNode.selectionStart=s,this._inputNode.selectionEnd=s)}}}_reflectBackFormattedValueToUser(){this._reflectBackOn()&&(this.value=typeof this.formattedValue<"u"?this.formattedValue:"",this.#e.didFormatterOutputSyncToView=!!this.formattedValue&&this.#e.didFormatterRun)}_reflectBackOn(){return!this._isHandlingUserInput}_proxyInputEvent(){this.dispatchEvent(new Event("user-input-changed",{bubbles:!0}))}_onUserInputChanged(){this._isHandlingUserInput=!0,this._syncValueUpwards(),this._isHandlingUserInput=!1}__onCompositionEvent({type:r}){r==="compositionstart"?this.__isHandlingComposition=!0:r==="compositionend"&&(this.__isHandlingComposition=!1,this._syncValueUpwards())}constructor(){super(),this.formatOn="change",this.formatOptions={mode:"auto"},this.formattedValue=void 0,this.serializedValue=void 0,this._isPasting=!1,this._isHandlingUserInput=!1,this.__prevViewValue="",this.__onCompositionEvent=this.__onCompositionEvent.bind(this),this.addEventListener("user-input-changed",this._onUserInputChanged),this.addEventListener("paste",this.__onPaste),this._reflectBackFormattedValueToUser=this._reflectBackFormattedValueToUser.bind(this),this._reflectBackFormattedValueDebounced=()=>{setTimeout(this._reflectBackFormattedValueToUser)}}__onPaste(){this._isPasting=!0,setTimeout(()=>{this._isPasting=!1})}connectedCallback(){super.connectedCallback(),typeof this.modelValue>"u"&&this._syncValueUpwards(),this.__prevViewValue=this.value,this._reflectBackFormattedValueToUser(),this._inputNode&&(this._inputNode.addEventListener(this.formatOn,this._reflectBackFormattedValueDebounced),this._inputNode.addEventListener("input",this._proxyInputEvent),this._inputNode.addEventListener("compositionstart",this.__onCompositionEvent),this._inputNode.addEventListener("compositionend",this.__onCompositionEvent))}disconnectedCallback(){super.disconnectedCallback(),this._inputNode&&(this._inputNode.removeEventListener("input",this._proxyInputEvent),this._inputNode.removeEventListener(this.formatOn,this._reflectBackFormattedValueDebounced),this._inputNode.removeEventListener("compositionstart",this.__onCompositionEvent),this._inputNode.removeEventListener("compositionend",this.__onCompositionEvent))}#t(){return this._isPasting?"pasted":this._isHandlingUserInput&&this.__prevViewValue?"user-edited":"auto"}#r(){const r=[];return this.#e.didFormatterOutputSyncToView&&r.push("formatted"),r}},$p=He(uM),dM=e=>class extends Ui(e){static get properties(){return{touched:{type:Boolean,reflect:!0},dirty:{type:Boolean,reflect:!0},filled:{type:Boolean,reflect:!0},prefilled:{attribute:!1},submitted:{attribute:!1}}}requestUpdate(r,n,i){super.requestUpdate(r,n,i),r==="touched"&&this.touched!==n&&this._onTouchedChanged(),r==="modelValue"&&(this.filled=!this._isEmpty()),r==="dirty"&&this.dirty!==n&&this._onDirtyChanged()}constructor(){super(),this.touched=!1,this.dirty=!1,this.prefilled=!1,this.filled=!1,this._leaveEvent="blur",this._valueChangedEvent="model-value-changed",this._iStateOnLeave=this._iStateOnLeave.bind(this),this._iStateOnValueChange=this._iStateOnValueChange.bind(this)}connectedCallback(){super.connectedCallback(),this.addEventListener(this._leaveEvent,this._iStateOnLeave),this.addEventListener(this._valueChangedEvent,this._iStateOnValueChange),this.initInteractionState()}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener(this._leaveEvent,this._iStateOnLeave),this.removeEventListener(this._valueChangedEvent,this._iStateOnValueChange)}initInteractionState(){this.dirty=!1,this.prefilled=!this._isEmpty()}_iStateOnLeave(){this.touched=!0,this.prefilled=!this._isEmpty()}_iStateOnValueChange(){this.dirty=!0}resetInteractionState(){this.touched=!1,this.submitted=!1,this.dirty=!1,this.prefilled=!this._isEmpty()}_onTouchedChanged(){this.dispatchEvent(new Event("touched-changed",{bubbles:!0,composed:!0}))}_onDirtyChanged(){this.dispatchEvent(new Event("dirty-changed",{bubbles:!0,composed:!0}))}_showFeedbackConditionFor(r,n){return n.touched&&n.dirty||n.prefilled||n.submitted}get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,submitted:this.submitted,touched:this.touched,dirty:this.dirty,filled:this.filled,prefilled:this.prefilled}}},Mp=He(dM);class Sa extends Ui(Mp(Lp($p(xa(Ms(Ne)))))){firstUpdated(t){super.firstUpdated(t),this._initialModelValue=this.modelValue}connectedCallback(){super.connectedCallback(),this._onChange=this._onChange.bind(this),this._inputNode.addEventListener("change",this._onChange),this.classList.add("form-field")}disconnectedCallback(){super.disconnectedCallback(),this._inputNode?.removeEventListener("change",this._onChange)}resetInteractionState(){super.resetInteractionState(),this.submitted=!1}reset(){this.modelValue=this._initialModelValue,this.resetInteractionState()}clear(){this.modelValue=""}_onChange(t){this.dispatchEvent(new Event("user-input-changed",{bubbles:!0}))}get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,focused:this.focused}}get _focusableNode(){return this._inputNode}}class Jh extends Array{_keys(){return Object.keys(this).filter(t=>Number.isNaN(Number(t)))}}const hM=e=>class extends Rp(e){static get properties(){return{_isFormOrFieldset:{type:Boolean}}}constructor(){super(),this.formElements=new Jh,this._isFormOrFieldset=!1,this._onRequestToAddFormElement=this._onRequestToAddFormElement.bind(this),this._onRequestToChangeFormElementName=this._onRequestToChangeFormElementName.bind(this),this.addEventListener("form-element-register",this._onRequestToAddFormElement),this.addEventListener("form-element-name-changed",this._onRequestToChangeFormElementName),this.initComplete=new Promise((t,r)=>{this.__resolveInitComplete=t,this.__rejectInitComplete=r}),this.registrationComplete=new Promise((t,r)=>{this.__resolveRegistrationComplete=t,this.__rejectRegistrationComplete=r}),this.registrationComplete.done=!1,this.registrationComplete.then(()=>{this.registrationComplete.done=!0,this.__resolveInitComplete(void 0)},()=>{throw this.registrationComplete.done=!0,this.__rejectInitComplete(void 0),new Error("Registration could not finish. Please use await el.registrationComplete;")})}connectedCallback(){super.connectedCallback(),this._completeRegistration()}_completeRegistration(){Promise.resolve().then(()=>this.__resolveRegistrationComplete(void 0))}disconnectedCallback(){super.disconnectedCallback(),this.registrationComplete.done===!1&&Promise.resolve().then(()=>{Promise.resolve().then(()=>{this.__rejectRegistrationComplete()})})}isRegisteredFormElement(t){return this.formElements.some(r=>r===t)}addFormElement(t,r){if(t._parentFormGroup=this,r>=0?this.formElements.splice(r,0,t):this.formElements.push(t),this._isFormOrFieldset){const{name:n}=t;if(n===this.name)throw console.info("Error Node:",t),new TypeError(`You can not have the same name "${n}" as your parent`);if(n.substr(-2)==="[]")Array.isArray(this.formElements[n])||(this.formElements[n]=new Jh),r>0?this.formElements[n].splice(r,0,t):this.formElements[n].push(t);else if(!this.formElements[n])this.formElements[n]=t;else throw console.info("Error Node:",t),new TypeError(`Name "${n}" is already registered - if you want an array add [] to the end`)}}removeFormElement(t){const r=this.formElements.indexOf(t);if(r>-1&&this.formElements.splice(r,1),this._isFormOrFieldset){const{name:n}=t;if(n.substr(-2)==="[]"&&this.formElements[n]){const i=this.formElements[n].indexOf(t);i>-1&&this.formElements[n].splice(i,1)}else this.formElements[n]&&delete this.formElements[n]}}_onRequestToAddFormElement(t){const r=t.detail.element;if(r===this||this.isRegisteredFormElement(r))return;t.stopPropagation();let n=-1;if(this.formElements&&Array.isArray(this.formElements)){for(const[i,s]of this.formElements.entries())if(!(s.compareDocumentPosition(r)&Node.DOCUMENT_POSITION_FOLLOWING)){n=i;break}}this.addFormElement(r,n)}_onRequestToChangeFormElementName(t){const r=this.formElements[t.detail.oldName];r&&(this.formElements[t.detail.newName]=r,delete this.formElements[t.detail.oldName])}_onRequestToRemoveFormElement(t){const r=t.detail.element;r!==this&&this.isRegisteredFormElement(r)&&(t.stopPropagation(),this.removeFormElement(r))}},Dp=He(hM),fM=e=>class extends e{constructor(){super(),this.registrationTarget=void 0,this.__redispatchEventForFormRegistrarPortalMixin=this.__redispatchEventForFormRegistrarPortalMixin.bind(this),this.addEventListener("form-element-register",this.__redispatchEventForFormRegistrarPortalMixin)}__redispatchEventForFormRegistrarPortalMixin(t){if(t.stopPropagation(),!this.registrationTarget)throw new Error("A FormRegistrarPortal element requires a .registrationTarget");this.registrationTarget.dispatchEvent(new CustomEvent("form-element-register",{detail:{element:t.detail.element},bubbles:!0}))}},pM=He(fM),mM=e=>class extends $p(Lp(Ui(e))){static get properties(){return{autocomplete:{type:String,reflect:!0}}}constructor(){super(),this.autocomplete=void 0}get _inputNode(){return super._inputNode}get selectionStart(){const r=this._inputNode;return r&&r.selectionStart?r.selectionStart:0}set selectionStart(r){const n=this._inputNode;n&&n.selectionStart&&(n.selectionStart=r)}get selectionEnd(){const r=this._inputNode;return r&&r.selectionEnd?r.selectionEnd:0}set selectionEnd(r){const n=this._inputNode;n&&n.selectionEnd&&(n.selectionEnd=r)}get value(){return this._inputNode&&this._inputNode.value||this.__value||""}set value(r){this._inputNode?(this._inputNode.value!==r&&this._setValueAndPreserveCaret(r),this.__value=void 0):this.__value=r}_setValueAndPreserveCaret(r){if(this.focused)try{if(!(this._inputNode instanceof HTMLSelectElement)){const n=this._inputNode.selectionStart;this._inputNode.value=r,this._inputNode.selectionStart=n,this._inputNode.selectionEnd=n}}catch{this._inputNode.value=r}else this._inputNode.value=r}_reflectBackFormattedValueToUser(){if(super._reflectBackFormattedValueToUser(),this._reflectBackOn()&&this.focused)try{this._inputNode.selectionStart=this._inputNode.value.length}catch{}}get _focusableNode(){return this._inputNode}},EE=He(mM),gM=e=>class extends Dp(xa(Mp(e))){static get properties(){return{multipleChoice:{type:Boolean,attribute:"multiple-choice"}}}get modelValue(){const r=this._getCheckedElements();return this.multipleChoice?r.map(n=>n.choiceValue):r[0]?r[0].choiceValue:""}set modelValue(r){const n=(i,s)=>typeof i.choiceValue=="object"?JSON.stringify(i.choiceValue)===JSON.stringify(r):i.choiceValue===s;this.__isInitialModelValue?this.registrationComplete.then(()=>{this.__isInitialModelValue=!1,this._setCheckedElements(r,n),this.requestUpdate("modelValue",this._oldModelValue)}):(this._setCheckedElements(r,n),this.requestUpdate("modelValue",this._oldModelValue)),this._oldModelValue=this.modelValue}get serializedValue(){const r=this._getCheckedElements();return this.multipleChoice?r.map(n=>n.serializedValue.value):r[0]?r[0].serializedValue.value:""}set serializedValue(r){const n=(i,s)=>i.serializedValue.value===s;this.__isInitialSerializedValue?this.registrationComplete.then(()=>{this.__isInitialSerializedValue=!1,this._setCheckedElements(r,n),this.requestUpdate("serializedValue")}):(this._setCheckedElements(r,n),this.requestUpdate("serializedValue"))}get formattedValue(){const r=this._getCheckedElements();return this.multipleChoice?r.map(n=>n.formattedValue):r[0]?r[0].formattedValue:""}set formattedValue(r){const n=(i,s)=>i.formattedValue===s;this.__isInitialFormattedValue?this.registrationComplete.then(()=>{this.__isInitialFormattedValue=!1,this._setCheckedElements(r,n)}):this._setCheckedElements(r,n)}get operationMode(){return this._repropagationRole==="choice-group"?"select":"enter"}constructor(){super(),this.multipleChoice=!1,this._repropagationRole="choice-group",this.__isInitialModelValue=!0,this.__isInitialSerializedValue=!0,this.__isInitialFormattedValue=!0}connectedCallback(){super.connectedCallback(),this.registrationComplete.then(()=>{this.__isInitialModelValue=!1,this.__isInitialSerializedValue=!1,this.__isInitialFormattedValue=!1})}_completeRegistration(){Promise.resolve().then(()=>super._completeRegistration())}updated(r){super.updated(r),r.has("name")&&this.name!==r.get("name")&&this.formElements.forEach(n=>{n.name=this.name})}addFormElement(r,n){this._throwWhenInvalidChildModelValue(r),r.name=this.name,super.addFormElement(r,n)}clear(){this.multipleChoice?this.modelValue=[]:this.modelValue=""}_triggerInitialModelValueChangedEvent(){this.registrationComplete.then(()=>{this._dispatchInitialModelValueChangedEvent()})}_getFromAllFormElementsFilter(r,n){return!0}_getFromAllFormElements(r,n){const i=n||this._getFromAllFormElementsFilter;return r==="modelValue"||r==="serializedValue"||r==="formattedValue"?this[r]:this.formElements.filter(s=>i(s,r)).map(s=>s.property)}_throwWhenInvalidChildModelValue(r){if(typeof r.modelValue.checked!="boolean"||!Object.prototype.hasOwnProperty.call(r.modelValue,"value"))throw new Error(`The ${this.tagName.toLowerCase()} name="${this.name}" does not allow to register ${r.tagName.toLowerCase()} with .modelValue="${r.modelValue}" - The modelValue should represent an Object { value: "foo", checked: false }`)}_isEmpty(){return this.multipleChoice?this.modelValue.length===0:typeof this.modelValue=="string"&&this.modelValue===""||this.modelValue===void 0||this.modelValue===null}_checkSingleChoiceElements(r){const{target:n}=r;if(n.checked===!1)return;const i=n.name;this.formElements.filter(s=>s.name===i).forEach(s=>{s!==n&&(s.checked=!1)})}_getCheckedElements(){return this.formElements.filter(r=>r.checked&&!r.disabled)}_setCheckedElements(r,n){if(r==null){this.formElements.forEach(i=>i.checked=!1);return}for(let i=0;i<this.formElements.length;i+=1)if(this.multipleChoice){let s=r.includes(this.formElements[i].modelValue.value);typeof this.formElements[i].modelValue.value=="object"&&(s=r.map(o=>JSON.stringify(o)).includes(JSON.stringify(this.formElements[i].modelValue.value))),this.formElements[i].checked=s}else n(this.formElements[i],r)?this.formElements[i].checked=!0:this.formElements[i].checked=!1}__setChoiceGroupTouched(){const r=this.modelValue;r!=null&&r!==this.__previousCheckedValue&&(this.touched=!0,this.__previousCheckedValue=r)}_onBeforeRepropagateChildrenValues(r){const n=r.detail&&r.detail.element||r.target;this.multipleChoice||!n.checked||(this.formElements.forEach(i=>{n.choiceValue!==i.choiceValue&&(i.checked=!1)}),this.__setChoiceGroupTouched(),this.requestUpdate("modelValue",this._oldModelValue),this._oldModelValue=this.modelValue)}_repropagationCondition(r){return!(this._repropagationRole==="choice-group"&&!this.multipleChoice&&!r.checked)}},eu=He(gM),bM=(e,t={})=>e.value!==t.value||e.checked!==t.checked,vM=e=>class extends $p(e){static get properties(){return{checked:{type:Boolean,reflect:!0},disabled:{type:Boolean,reflect:!0},modelValue:{type:Object,hasChanged:bM},choiceValue:{type:Object}}}get choiceValue(){return this.modelValue.value}set choiceValue(r){this.requestUpdate("choiceValue",this.choiceValue),this.modelValue.value!==r&&(this.modelValue={value:r,checked:this.modelValue.checked})}requestUpdate(r,n,i){super.requestUpdate(r,n,i),r==="modelValue"?this.modelValue.checked!==this.checked&&this.__syncModelCheckedToChecked(this.modelValue.checked):r==="checked"&&this.modelValue.checked!==this.checked&&this.__syncCheckedToModel(this.checked)}firstUpdated(r){super.firstUpdated(r),r.has("checked")&&this.__syncCheckedToInputElement()}updated(r){super.updated(r),r.has("modelValue")&&this.__syncCheckedToInputElement(),r.has("name")&&this._parentFormGroup&&this._parentFormGroup.name!==this.name&&this._syncNameToParentFormGroup()}constructor(){super(),this.modelValue={value:"",checked:!1},this.disabled=!1,this._preventDuplicateLabelClick=this._preventDuplicateLabelClick.bind(this),this._toggleChecked=this._toggleChecked.bind(this)}static get styles(){return[...super.styles||[],te`
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
      `}_choiceGraphicTemplate(){return pt}_afterTemplate(){return pt}connectedCallback(){super.connectedCallback(),this._labelNode&&this._labelNode.addEventListener("click",this._preventDuplicateLabelClick),this.addEventListener("user-input-changed",this._toggleChecked)}disconnectedCallback(){super.disconnectedCallback(),this._labelNode&&this._labelNode.removeEventListener("click",this._preventDuplicateLabelClick),this.removeEventListener("user-input-changed",this._toggleChecked)}_preventDuplicateLabelClick(r){const n=i=>{i.stopImmediatePropagation(),this._inputNode.removeEventListener("click",n)};this._inputNode.addEventListener("click",n)}_toggleChecked(r){this.disabled||(this._isHandlingUserInput=!0,this.checked=!this.checked,this._isHandlingUserInput=!1)}_syncNameToParentFormGroup(){this._parentFormGroup.tagName.includes(this.tagName)&&(this.name=this._parentFormGroup?.name||"")}__syncModelCheckedToChecked(r){this.checked=r}__syncCheckedToModel(r){this.modelValue={value:this.choiceValue,checked:r}}__syncCheckedToInputElement(){this._inputNode&&(this._inputNode.checked=this.checked)}_proxyInputEvent(){}_onModelValueChanged({modelValue:r},n){let i;n&&n.modelValue&&(i=n.modelValue),this.constructor.elementProperties.get("modelValue").hasChanged(r,i)&&super._onModelValueChanged({modelValue:r})}parser(){return this.modelValue}formatter(r){return r&&r.value!==void 0?r.value:r}clear(){this.checked=!1}_isEmpty(){return!this.checked}_syncValueUpwards(){}},tu=He(vM);class yM extends Zc{static get validatorName(){return"FormElementsHaveNoError"}execute(t,r,n){return n?.node._anyFormElementHasFeedbackFor("error")}static async getMessage(){return""}}const _M=e=>class extends Dp(Ui(xa(ya(Ms(e))))){static get properties(){return{submitted:{type:Boolean,reflect:!0},focused:{type:Boolean,reflect:!0},dirty:{type:Boolean,reflect:!0},touched:{type:Boolean,reflect:!0},prefilled:{type:Boolean,reflect:!0}}}get _inputNode(){return this}get modelValue(){return this._getFromAllFormElements("modelValue")}set modelValue(r){this.__isInitialModelValue?(this.__isInitialModelValue=!1,this.registrationComplete.then(()=>{this._setValueMapForAllFormElements("modelValue",r)})):this._setValueMapForAllFormElements("modelValue",r)}get serializedValue(){return this._getFromAllFormElements("serializedValue")}set serializedValue(r){this.__isInitialSerializedValue?(this.__isInitialSerializedValue=!1,this.registrationComplete.then(()=>{this._setValueMapForAllFormElements("serializedValue",r)})):this._setValueMapForAllFormElements("serializedValue",r)}get formattedValue(){return this._getFromAllFormElements("formattedValue")}set formattedValue(r){this._setValueMapForAllFormElements("formattedValue",r)}get prefilled(){return this._everyFormElementHas("prefilled")}constructor(){super(),this.value="",this.disabled=!1,this.submitted=!1,this.dirty=!1,this.touched=!1,this.focused=!1,this.__addedSubValidators=!1,this.__isInitialModelValue=!0,this.__isInitialSerializedValue=!0,this._checkForOutsideClick=this._checkForOutsideClick.bind(this),this.addEventListener("focusin",this._syncFocused),this.addEventListener("focusout",this._onFocusOut),this.addEventListener("dirty-changed",this._syncDirty),this.addEventListener("validate-performed",this.__onChildValidatePerformed),this.defaultValidators=[new yM],this.__descriptionElementsInParentChain=new Set,this.__pendingValues={modelValue:{},serializedValue:{}}}connectedCallback(){super.connectedCallback(),this.setAttribute("role","group"),this.initComplete.then(()=>{this.__isInitialModelValue=!1,this.__isInitialSerializedValue=!1,this.__initInteractionStates()})}disconnectedCallback(){super.disconnectedCallback(),this.__hasActiveOutsideClickHandling&&(document.removeEventListener("click",this._checkForOutsideClick),this.__hasActiveOutsideClickHandling=!1),this.__descriptionElementsInParentChain.clear()}__initInteractionStates(){this.formElements.forEach(r=>{typeof r.initInteractionState=="function"&&r.initInteractionState()})}_triggerInitialModelValueChangedEvent(){this.registrationComplete.then(()=>{this._dispatchInitialModelValueChangedEvent()})}updated(r){super.updated(r),r.has("disabled")&&(this.disabled?this.__requestChildrenToBeDisabled():this.__retractRequestChildrenToBeDisabled()),r.has("focused")&&this.focused===!0&&this.__setupOutsideClickHandling()}__setupOutsideClickHandling(){this.__hasActiveOutsideClickHandling||(document.addEventListener("click",this._checkForOutsideClick),this.__hasActiveOutsideClickHandling=!0)}_checkForOutsideClick(r){!this.contains(r.target)&&(this.touched=!0)}__requestChildrenToBeDisabled(){this.formElements.forEach(r=>{r.makeRequestToBeDisabled&&r.makeRequestToBeDisabled()})}__retractRequestChildrenToBeDisabled(){this.formElements.forEach(r=>{r.retractRequestToBeDisabled&&r.retractRequestToBeDisabled()})}_inputGroupTemplate(){return W`
        <div class="input-group">
          <slot></slot>
        </div>
      `}submitGroup(){this.submitted=!0,this.formElements.forEach(r=>{typeof r.submitGroup=="function"?r.submitGroup():r.submitted=!0})}resetGroup(){this.formElements.forEach(r=>{typeof r.resetGroup=="function"?r.resetGroup():typeof r.reset=="function"&&r.reset()}),this.resetInteractionState()}clearGroup(){this.formElements.forEach(r=>{typeof r.clearGroup=="function"?r.clearGroup():typeof r.clear=="function"&&r.clear()}),this.resetInteractionState()}resetInteractionState(){this.submitted=!1,this.touched=!1,this.dirty=!1,this.formElements.forEach(r=>{typeof r.resetInteractionState=="function"&&r.resetInteractionState()})}_getFromAllFormElementsFilter(r,n){return!r.disabled}_getFromAllFormElements(r,n){const i={},s=n||this._getFromAllFormElementsFilter;return this.formElements._keys().forEach(o=>{const a=this.formElements[o];a instanceof Jh?i[o]=a.filter(l=>s(l,r)).map(l=>l[r]):s(a,r)&&(typeof a._getFromAllFormElements=="function"?i[o]=a._getFromAllFormElements(r):i[o]=a[r])}),i}_setValueForAllFormElements(r,n){this.formElements.forEach(i=>{i[r]=n})}_setValueMapForAllFormElements(r,n){n&&typeof n=="object"&&Object.keys(n).forEach(i=>{Array.isArray(this.formElements[i])&&this.formElements[i].forEach((s,o)=>{s[r]=n[i][o]}),this.formElements[i]?this.formElements[i][r]=n[i]:this.__pendingValues[r][i]=n[i]})}_anyFormElementHas(r){return Object.keys(this.formElements).some(n=>Array.isArray(this.formElements[n])?this.formElements[n].some(i=>!!i[r]):!!this.formElements[n][r])}_anyFormElementHasFeedbackFor(r){return Object.keys(this.formElements).some(n=>Array.isArray(this.formElements[n])?this.formElements[n].some(i=>!!(i.hasFeedbackFor&&i.hasFeedbackFor.includes(r))):!!(this.formElements[n].hasFeedbackFor&&this.formElements[n].hasFeedbackFor.includes(r)))}_everyFormElementHas(r){return Object.keys(this.formElements).every(n=>Array.isArray(this.formElements[n])?this.formElements[n].every(i=>!!i[r]):!!this.formElements[n][r])}__onChildValidatePerformed(r){r&&this.isRegisteredFormElement(r.target)&&this.validate()}_syncFocused(){this.focused=this._anyFormElementHas("focused")}_onFocusOut(r){const n=this.formElements[this.formElements.length-1];r.target===n&&(this.touched=!0),this.focused=!1}_syncDirty(){this.dirty=this._anyFormElementHas("dirty")}__storeAllDescriptionElementsInParentChain(){let n=this;for(;n;){const i=n._getAriaDescriptionElements();wE(i,{reverse:!0}).forEach(o=>{o.getAttribute("slot")==="feedback"&&this.__descriptionElementsInParentChain.add(o)}),n=n._parentFormGroup}}__linkParentMessages(r){this.__descriptionElementsInParentChain.forEach(n=>{typeof r.addToAriaDescribedBy=="function"&&r.addToAriaDescribedBy(n,{reorder:!1})})}__unlinkParentMessages(r){this.__descriptionElementsInParentChain.forEach(n=>{typeof r.removeFromAriaDescribedBy=="function"&&r.removeFromAriaDescribedBy(n)})}addFormElement(r,n){if(super.addFormElement(r,n),this.disabled&&r.makeRequestToBeDisabled(),this.__descriptionElementsInParentChain.size||this.__storeAllDescriptionElementsInParentChain(),this.__linkParentMessages(r),this.validate({clearCurrentResult:!0}),!r.modelValue){const i=this.__pendingValues;i.modelValue&&i.modelValue[r.name]?r.modelValue=i.modelValue[r.name]:i.serializedValue&&i.serializedValue[r.name]&&(r.serializedValue=i.serializedValue[r.name])}}get _initialModelValue(){return this._getFromAllFormElements("_initialModelValue")}removeFormElement(r){super.removeFormElement(r),this.validate({clearCurrentResult:!0}),typeof r.removeFromAriaLabelledBy=="function"&&this._labelNode&&r.removeFromAriaLabelledBy(this._labelNode,{reorder:!1}),this.__unlinkParentMessages(r)}_isEmpty(){return this.formElements.every(r=>r._isEmpty?.())}},xE=He(_M);class ru extends EE(Sa){static get properties(){return{readOnly:{type:Boolean,attribute:"readonly",reflect:!0},type:{type:String,reflect:!0},placeholder:{type:String,reflect:!0}}}get slots(){return{...super.slots,input:()=>{const t=document.createElement("input"),r=this.getAttribute("value");return r&&t.setAttribute("value",r),t}}}get _inputNode(){return super._inputNode}constructor(){super(),this.readOnly=!1,this.type="text",this.placeholder=""}requestUpdate(t,r,n){super.requestUpdate(t,r,n),t==="readOnly"&&this.__delegateReadOnly()}firstUpdated(t){super.firstUpdated(t),this.__delegateReadOnly()}updated(t){super.updated(t),t.has("type")&&(this._inputNode.type=this.type),t.has("placeholder")&&(this._inputNode.placeholder=this.placeholder),t.has("disabled")&&(this._inputNode.disabled=this.disabled,this.validate()),t.has("name")&&(this._inputNode.name=this.name),t.has("autocomplete")&&(this._inputNode.autocomplete=this.autocomplete)}__delegateReadOnly(){this._inputNode&&(this._inputNode.readOnly=this.readOnly)}}var Qh=class extends ru{static get styles(){return[...super.styles,wa,N$]}connectedCallback(){if(super.connectedCallback(),this._inputNode&&this.size){let t=parseInt(this.size,10);t>0&&(this._inputNode.size=t)}}};se([V({type:Number,reflect:!0})],Qh.prototype,"size",void 0),customElements.get("craft-input")||customElements.define("craft-input",Qh);const Gt=e=>e??pt;class bl extends Zc{static validatorName="IsAcceptedFile";static checkFileSize(t,r){return t<=r}static getExtension(t){return t?.slice(t.lastIndexOf("."))}static isExtensionAllowed(t,r){return r?.find(n=>n.toUpperCase()===t.toUpperCase())}static isFileTypeAllowed(t,r){return r?.find(n=>n.toUpperCase()===t.toUpperCase())}execute(t,r=this.param){let n,i;const s=this.constructor,{allowedFileTypes:o,allowedFileExtensions:a,maxFileSize:l}=r;return o?.length?(n=t.some(c=>!s.isFileTypeAllowed(c.type,o)),n):a?.length?(i=t.some(c=>!s.isExtensionAllowed(s.getExtension(c.name),a)),i):t.findIndex(c=>!s.checkFileSize(c.size,l))>-1}static async getMessage(){return""}}class wM extends Zc{static validatorName="DuplicateFileNames";constructor(t,r){super(t,r),this.type="info"}execute(t,r=this.param){return r.show}static async getMessage(){return oc().msg("lion-input-file:uploadTextDuplicateFileName")}}const EM=524288e3,$d={type:"FILE_TYPE",size:"FILE_SIZE"},Ys={fail:"FAIL",pass:"SUCCESS"};class xM{constructor(t,r){this.failedProp=[],this.systemFile=t,this._acceptCriteria=r,this.uploadFileStatus(),this.failedProp.length===0&&this.createDownloadUrl(t)}_getFileNameExtension(t){return t.slice(t.lastIndexOf("."))}uploadFileStatus(){if(this._acceptCriteria.allowedFileExtensions.length){const t=this._getFileNameExtension(this.systemFile.name);bl.isExtensionAllowed(t,this._acceptCriteria.allowedFileExtensions)||(this.status=Ys.fail,this.failedProp.push($d.type))}else if(this._acceptCriteria.allowedFileTypes.length){const t=this.systemFile.type;bl.isFileTypeAllowed(t,this._acceptCriteria.allowedFileTypes)||(this.status=Ys.fail,this.failedProp.push($d.type))}bl.checkFileSize(this.systemFile.size,this._acceptCriteria.maxFileSize)?this.status!==Ys.fail&&(this.status=Ys.pass):(this.status=Ys.fail,this.failedProp.push($d.size))}createDownloadUrl(t){this.downloadUrl=window.URL.createObjectURL(t)}}const lv=(e,t,r)=>{const n=new Map;for(let i=t;i<=r;i++)n.set(e[i],i);return n},SM=Lv(class extends Fv{constructor(e){if(super(e),e.type!==Iv.CHILD)throw Error("repeat() can only be used in text expressions")}dt(e,t,r){let n;r===void 0?r=t:t!==void 0&&(n=t);const i=[],s=[];let o=0;for(const a of e)i[o]=n?n(a,o):o,s[o]=r(a,o),o++;return{values:s,keys:i}}render(e,t,r){return this.dt(e,t,r).values}update(e,[t,r,n]){const i=w$(e),{values:s,keys:o}=this.dt(t,r,n);if(!Array.isArray(i))return this.ut=o,s;const a=this.ut??=[],l=[];let u,c,d=0,p=i.length-1,m=0,h=s.length-1;for(;d<=p&&m<=h;)if(i[d]===null)d++;else if(i[p]===null)p--;else if(a[d]===o[m])l[m]=di(i[d],s[m]),d++,m++;else if(a[p]===o[h])l[h]=di(i[p],s[h]),p--,h--;else if(a[d]===o[h])l[h]=di(i[d],s[h]),Gs(e,l[h+1],i[d]),d++,h--;else if(a[p]===o[m])l[m]=di(i[p],s[m]),Gs(e,i[d],i[p]),p--,m++;else if(u===void 0&&(u=lv(o,m,h),c=lv(a,d,p)),u.has(a[d]))if(u.has(a[p])){const f=c.get(o[m]),b=f!==void 0?i[f]:null;if(b===null){const y=Gs(e,i[d]);di(y,s[m]),l[m]=y}else l[m]=di(b,s[m]),Gs(e,i[d],b),i[f]=null;m++}else Nd(i[p]),p--;else Nd(i[d]),d++;for(;m<=h;){const f=Gs(e,l[h+1]);di(f,s[m]),l[m++]=f}for(;d<=p;){const f=i[d++];f!==null&&Nd(f)}return this.ut=o,_$(e,l),Qd}}),SE=e=>{switch(e){case"bg-BG":return Y(()=>import("./bg-BG.js"),__vite__mapDeps([34,35]),import.meta.url);case"bg":return Y(()=>import("./bg2.js"),[],import.meta.url);case"cs-CZ":return Y(()=>import("./cs-CZ.js"),__vite__mapDeps([36,37]),import.meta.url);case"cs":return Y(()=>import("./cs2.js"),[],import.meta.url);case"de-DE":return Y(()=>import("./de-DE.js"),__vite__mapDeps([38,39]),import.meta.url);case"de":return Y(()=>import("./de2.js"),[],import.meta.url);case"en-AU":return Y(()=>import("./en-AU.js"),__vite__mapDeps([40,41]),import.meta.url);case"en-GB":return Y(()=>import("./en-GB.js"),__vite__mapDeps([42,41]),import.meta.url);case"en-US":return Y(()=>import("./en-US.js"),__vite__mapDeps([43,41]),import.meta.url);case"en-PH":case"en":return Y(()=>import("./en2.js"),[],import.meta.url);case"es-ES":return Y(()=>import("./es-ES.js"),__vite__mapDeps([44,45]),import.meta.url);case"es":return Y(()=>import("./es2.js"),[],import.meta.url);case"fr-FR":return Y(()=>import("./fr-FR.js"),__vite__mapDeps([46,47]),import.meta.url);case"fr-BE":return Y(()=>import("./fr-BE.js"),__vite__mapDeps([48,47]),import.meta.url);case"fr":return Y(()=>import("./fr2.js"),[],import.meta.url);case"hu-HU":return Y(()=>import("./hu-HU.js"),__vite__mapDeps([49,50]),import.meta.url);case"hu":return Y(()=>import("./hu2.js"),[],import.meta.url);case"it-IT":return Y(()=>import("./it-IT.js"),__vite__mapDeps([51,52]),import.meta.url);case"it":return Y(()=>import("./it2.js"),[],import.meta.url);case"nl-BE":return Y(()=>import("./nl-BE.js"),__vite__mapDeps([53,54]),import.meta.url);case"nl-NL":return Y(()=>import("./nl-NL.js"),__vite__mapDeps([55,54]),import.meta.url);case"nl":return Y(()=>import("./nl2.js"),[],import.meta.url);case"pl-PL":return Y(()=>import("./pl-PL.js"),__vite__mapDeps([56,57]),import.meta.url);case"pl":return Y(()=>import("./pl2.js"),[],import.meta.url);case"ro-RO":return Y(()=>import("./ro-RO.js"),__vite__mapDeps([58,59]),import.meta.url);case"ro":return Y(()=>import("./ro2.js"),[],import.meta.url);case"ru-RU":return Y(()=>import("./ru-RU.js"),__vite__mapDeps([60,61]),import.meta.url);case"ru":return Y(()=>import("./ru2.js"),[],import.meta.url);case"sk-SK":return Y(()=>import("./sk-SK.js"),__vite__mapDeps([62,63]),import.meta.url);case"sk":return Y(()=>import("./sk2.js"),[],import.meta.url);case"uk-UA":return Y(()=>import("./uk-UA.js"),__vite__mapDeps([64,65]),import.meta.url);case"uk":return Y(()=>import("./uk2.js"),[],import.meta.url);case"zh-CN":case"zh":return Y(()=>import("./zh2.js"),[],import.meta.url);default:return Y(()=>import("./en2.js"),[],import.meta.url)}};class CE extends Qc(Ea(Ne)){static get scopedElements(){return{...super.scopedElements,"lion-validation-feedback":_E}}static get properties(){return{fileList:{type:Array},multiple:{type:Boolean}}}static localizeNamespaces=[{"lion-input-file":SE},...super.localizeNamespaces];constructor(){super(),this.fileList=[],this.multiple=!1}updated(t){super.updated(t),t.has("fileList")&&this._enhanceLightDomA11y()}_enhanceLightDomA11y(){const t=this.shadowRoot?.querySelectorAll('[id^="file-feedback"]'),r=this.parentNode?.parentNode;t?.forEach(n=>{r?.addEventListener("focusin",()=>{n.setAttribute("aria-live","polite")}),r?.addEventListener("focusout",()=>{n.setAttribute("aria-live","assertive")})})}_removeFile(t){this.dispatchEvent(new CustomEvent("file-remove-requested",{detail:{removedFile:t,status:t.status,uploadResponse:t.response}}))}_validationFeedbackTemplate(t,r){return W`
      <lion-validation-feedback
        id="file-feedback-${r}"
        .feedbackData="${t}"
        aria-live="assertive"
      ></lion-validation-feedback>
    `}_listItemBeforeTemplate(t){return pt}_listItemAfterTemplate(t,r){return W`
      <button
        class="selected__list__item__remove-button"
        aria-label="${this.msgLit("lion-input-file:removeButtonLabel",{fileName:t.systemFile.name})}"
        @click=${()=>this._removeFile(t)}
      >
        ${this._removeButtonContentTemplate()}
      </button>
    `}_removeButtonContentTemplate(){return W`✖️`}_selectedListItemTemplate(t){const r=_a();return W`
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
              ${SM(t.validationFeedback,n=>W`
                  ${this._validationFeedbackTemplate([n],r)}
                `)}
            `:pt}
      </div>
    `}render(){return this.fileList?.length?W`
          ${this.multiple?W`
                <ul class="selected__list">
                  ${this.fileList.map(t=>W` <li>${this._selectedListItemTemplate(t)}</li> `)}
                </ul>
              `:W` ${this._selectedListItemTemplate(this.fileList[0])} `}
        `:pt}static get styles(){return[te`
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
      `]}}function Md(e,t=2){if(!+e)return"0 Bytes";const r=1024,n=t<0?0:t,i=[" bytes","KB","MB","GB","TB","PB","EB","ZB","YB"],s=Math.floor(Math.log(e)/Math.log(r));return`${parseFloat((e/r**s).toFixed(n))}${i[s]}`}class CM extends Ea(Qc(Sa)){static get scopedElements(){return{...super.scopedElements,"lion-selected-file-list":CE}}static get properties(){return{accept:{type:String},multiple:{type:Boolean,reflect:!0},buttonLabel:{type:String,attribute:"button-label"},maxFileSize:{type:Number,attribute:"max-file-size"},enableDropZone:{type:Boolean,attribute:"enable-drop-zone"},uploadOnSelect:{type:Boolean,attribute:"upload-on-select"},isDragging:{type:Boolean,attribute:"is-dragging",reflect:!0},uploadResponse:{type:Array,state:!1},_selectedFilesMetaData:{type:Array,state:!0}}}static localizeNamespaces=[{"lion-input-file":SE},...super.localizeNamespaces];static get validationTypes(){return["error","info"]}get slots(){return{...super.slots,input:()=>W`<input .value="${Gt(this.getAttribute("value"))}" />`,"file-select-button":()=>W`<button
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
        `,renderAsDirectHostChild:!0})}}get _inputNode(){return super._inputNode}get _buttonNode(){return this.querySelector(`#select-button-${this._inputId}`)}get buttonLabel(){return this.__buttonLabel||this._buttonNode?.textContent?.trim()||""}set buttonLabel(t){const r=this.buttonLabel;this.__buttonLabel=t,this.requestUpdate("buttonLabel",r)}get _focusableNode(){return this._buttonNode}get _isDragAndDropSupported(){return"draggable"in document.createElement("div")}constructor(){super(),this.type="file",this._selectedFilesMetaData=[],this.uploadResponse=[],this.__initialUploadResponse=this.uploadResponse,this.uploadOnSelect=!1,this.multiple=!1,this.enableDropZone=!1,this.maxFileSize=EM,this.accept="",this.buttonLabel="",this._initialButtonLabel="",this.modelValue=[],this._onRemoveFile=this._onRemoveFile.bind(this),this.__duplicateFileNamesValidator=new wM({show:!1}),this.__previouslyParsedFiles=null}get _fileListNode(){return Array.from(this.children).find(t=>t.slot==="selected-file-list")}connectedCallback(){super.connectedCallback(),this.__initialUploadResponse=this.uploadResponse,this._initialButtonLabel=this.buttonLabel,this._inputNode.addEventListener("change",this._onChange),this._inputNode.addEventListener("click",this._onClick)}disconnectedCallback(){super.disconnectedCallback(),this._inputNode.removeEventListener("change",this._onChange),this._inputNode.removeEventListener("click",this._onClick)}onLocaleUpdated(){super.onLocaleUpdated(),this.multiple?this.buttonLabel=this._initialButtonLabel||this.msgLit("lion-input-file:selectTextMultipleFile"):this.buttonLabel=this._initialButtonLabel||this.msgLit("lion-input-file:selectTextSingleFile")}get operationMode(){return"upload"}get _acceptCriteria(){let t=[],r=[];if(this.accept){const n=this.accept.replace(/\s+/g,"").split(",");t=n.filter(i=>i.includes("/")),r=n.filter(i=>!i.includes("/"))}return{allowedFileTypes:t,allowedFileExtensions:r,maxFileSize:this.maxFileSize}}reset(){super.reset(),this._selectedFilesMetaData=[],this.uploadResponse=this.__initialUploadResponse,this.modelValue=[],this.dirty=!1}clear(){this._selectedFilesMetaData=[],this.uploadResponse=[],this.modelValue=[]}_showFeedbackConditionFor(t,r){return super._showFeedbackConditionFor(t,r)&&!(this.validationStates.error?.FileTypeAllowed||this.validationStates.error?.FileSizeAllowed)}parser(){if(this.__previouslyParsedFiles===this._inputNode.files)return this.modelValue;this.__previouslyParsedFiles=this._inputNode.files;const t=this._inputNode.files?Array.from(this._inputNode.files):[];return this.multiple?[...this.modelValue??[],...t]:t}formatter(t){return this._inputNode?.value||""}__setupDragDropEventListeners(){const t=this.shadowRoot?.querySelector(".input-file__drop-zone");["dragenter","dragover","dragleave"].forEach(r=>{t?.addEventListener(r,n=>{n.preventDefault(),n.stopPropagation(),this.isDragging=r!=="dragleave"},!1)}),window.addEventListener("drop",r=>{r.target===this._inputNode&&r.preventDefault(),this.isDragging=!1},!1)}firstUpdated(t){super.firstUpdated(t),this.__setupFileValidators(),this._inputNode&&(this._inputNode.type=this.type,this._inputNode.setAttribute("tabindex","-1"),this._inputNode.multiple=this.multiple,this.accept.length&&(this._inputNode.accept=this.accept)),this.enableDropZone&&this._isDragAndDropSupported&&(this.__setupDragDropEventListeners(),this.setAttribute("drop-zone","")),this._fileListNode.addEventListener("file-remove-requested",this._onRemoveFile)}updated(t){super.updated(t),t.has("disabled")&&(this._inputNode.disabled=this.disabled,this.validate()),t.has("buttonLabel")&&this._buttonNode&&(this._buttonNode.textContent=this.buttonLabel),t.has("name")&&(this._inputNode.name=this.name),t.has("_ariaLabelledNodes")&&this.__syncAriaLabelledByAttributesToButton(),t.has("_ariaDescribedNodes")&&this.__syncAriaDescribedByAttributesToButton(),t.has("uploadResponse")&&(this._selectedFilesMetaData.length===0&&this.uploadResponse.forEach(r=>{const n={systemFile:{name:r.name},response:r,status:r.status,validationFeedback:[{message:r.errorMessage}]};this._selectedFilesMetaData=[...this._selectedFilesMetaData,n]}),this._selectedFilesMetaData.forEach(r=>{!this.uploadResponse.some(n=>n.name===r.systemFile.name)&&this.uploadOnSelect?this.__removeFileFromList(r):(this.uploadResponse.forEach(n=>{n.name===r.systemFile.name&&(r.response=n,r.downloadUrl=n.downloadUrl?n.downloadUrl:r.downloadUrl,r.status=n.status,r.validationFeedback=[{type:typeof n.errorMessage=="string"&&n.errorMessage?.length>0?"error":"success",message:n.errorMessage??""}])}),this._selectedFilesMetaData=[...this._selectedFilesMetaData])}),this._updateUploadButtonDescription())}__computeNewAddedFiles(t){const r=t.filter(n=>this._selectedFilesMetaData.findIndex(i=>i.systemFile.name===n.name)===-1);return this.__duplicateFileNamesValidator.param={show:t.length!==r.length},this.validate(),r}_processDroppedFiles(t){if(t.preventDefault(),this.isDragging=!1,!(t.dataTransfer&&t.dataTransfer.items.length>1&&!this.multiple||!t.dataTransfer?.files)){if(this._inputNode.files=t.dataTransfer.files,this.multiple){const n=this.__computeNewAddedFiles(Array.from(t.dataTransfer.files));this.modelValue=[...this.modelValue??[],...n]}else this.modelValue=Array.from(t.dataTransfer.files);this._processFiles(Array.from(t.dataTransfer.files))}}_onChange(t){this.touched=!0,this._onUserInputChanged(),this._processFiles(t?.target?.files)}_onClick(t){t.target.value=""}__syncAriaLabelledByAttributesToButton(){if(this._inputNode.hasAttribute("aria-labelledby")){const t=this._inputNode.getAttribute("aria-labelledby");this._buttonNode?.setAttribute("aria-labelledby",`select-button-${this._inputId} ${t}`)}}__syncAriaDescribedByAttributesToButton(){if(this._inputNode.hasAttribute("aria-describedby")){const t=this._inputNode.getAttribute("aria-describedby")||"";this._buttonNode?.setAttribute("aria-describedby",t)}}__setupFileValidators(){this.defaultValidators=[new bl(this._acceptCriteria),this.__duplicateFileNamesValidator]}_processFiles(t){const r=this.__computeNewAddedFiles(Array.from(t));!this.multiple&&r.length>0&&(this._selectedFilesMetaData=[],this.uploadResponse=[]);let n;for(const s of r.values())n=new xM(s,this._acceptCriteria),n.failedProp?.length?(this._handleErroredFiles(n),this.uploadResponse=[...this.uploadResponse,{name:n.systemFile.name,status:"FAIL",errorMessage:n.validationFeedback[0].message}]):this.uploadResponse=[...this.uploadResponse,{name:n.systemFile.name,status:"SUCCESS"}],this._selectedFilesMetaData=[...this._selectedFilesMetaData,n],this._handleErrors();const i=this._selectedFilesMetaData.filter(({systemFile:s,status:o})=>r.includes(s)&&o==="SUCCESS").map(({systemFile:s})=>s);i.length>0&&this._dispatchFileListChangeEvent(i)}_dispatchFileListChangeEvent(t){this.dispatchEvent(new CustomEvent("file-list-changed",{detail:{newFiles:t}}))}_handleErrors(){let t=!1;if(this._selectedFilesMetaData.forEach(r=>{r.failedProp&&r.failedProp.length>0&&(t=!0)}),t)this.hasFeedbackFor?.push("error"),this.shouldShowFeedbackFor.push("error");else if(this._prevHasErrors&&this.hasFeedbackFor.includes("error")){const r=this.hasFeedbackFor.indexOf("error");this.hasFeedbackFor.slice(r,r+1);const n=this.shouldShowFeedbackFor.indexOf("error");this.shouldShowFeedbackFor.slice(n,n+1)}this._prevHasErrors=t}_handleErroredFiles(t){t.validationFeedback=[];const{allowedFileExtensions:r,allowedFileTypes:n}=this._acceptCriteria;let i=[],s=0,o;r.length?(i=r,o=i.pop(),s=i.length):n.length&&(n.forEach(u=>{if(u.endsWith("/*"))i.push(u.slice(0,-2));else if(u==="text/plain")i.push("text");else{const c=u.indexOf("/"),d=u.slice(c+1);if(!d.includes("+"))i.push(`.${d}`);else{const p=d.split("+");i.push(`.${p[0]}`)}}}),o=i.pop(),s=i.length);let a="";o?s?a=`${this.msgLit("lion-input-file:allowedFileValidatorComplex",{allowedTypesArray:i.join(", "),allowedTypesLastItem:o,maxSize:Md(this.maxFileSize)})}`:a=`${this.msgLit("lion-input-file:allowedFileValidatorSimple",{allowedType:o,maxSize:Md(this.maxFileSize)})}`:a=`${this.msgLit("lion-input-file:allowedFileSize",{maxSize:Md(this.maxFileSize)})}`;const l={message:a,type:"error"};t.validationFeedback?.push(l)}_updateUploadButtonDescription(){const t=[];let r;this._selectedFilesMetaData.forEach(i=>{i.status==="FAIL"&&(r=i.validationFeedback?i.validationFeedback[0].message.toString():"",t.push(i.systemFile.name))});const n=this.querySelector('[slot="after"]');if(n)if(!this._selectedFilesMetaData||this._selectedFilesMetaData.length===0)this.uploadOnSelect?n.textContent=this.msgLit("lion-input-file:noFilesUploaded"):n.textContent=this.msgLit("lion-input-file:noFilesSelected");else if(this._selectedFilesMetaData.length===1){const{name:i}=this._selectedFilesMetaData[0].systemFile;this.uploadOnSelect?n.textContent=r||this.msgLit("lion-input-file:fileUploaded")+(i??""):n.textContent=r||this.msgLit("lion-input-file:fileSelected")+(i??"")}else this.uploadOnSelect?n.textContent=`${this.msgLit("lion-input-file:filesUploaded",{numberOfFiles:this._selectedFilesMetaData.length})} ${r?this.msgLit("lion-input-file:generalValidatorMessage",{validatorMessage:r,listOfErroneousFiles:t.join(", ")}):""}`:n.textContent=`${this.msgLit("lion-input-file:filesSelected",{numberOfFiles:this._selectedFilesMetaData.length})} ${r?this.msgLit("lion-input-file:generalValidatorMessage",{validatorMessage:r,listOfErroneousFiles:t.join(", ")}):""}`}__removeFileFromList(t){this._selectedFilesMetaData=this._selectedFilesMetaData.filter(r=>r.systemFile.name!==t.systemFile.name),this.modelValue&&(this.modelValue=this.modelValue.filter(r=>r.name!==t.systemFile.name)),this._inputNode.value="",this._handleErrors(),this._updateUploadButtonDescription()}_onRemoveFile(t){if(this.disabled)return;const{removedFile:r}=t.detail;!this.uploadOnSelect&&r&&this.__removeFileFromList(r),this._removeFile(r)}_removeFile(t){this.dispatchEvent(new CustomEvent("file-removed",{detail:{removedFile:t,status:t.status,uploadResponse:t.response}}))}_reflectBackOn(){return!1}_isEmpty(){return this.modelValue?.length===0}_dropZoneTemplate(){return W`
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
      `]}__openDialogOnBtnClick(t){t.preventDefault(),t.stopPropagation(),this._inputNode.click()}}var AM=class extends CE{static get styles(){return[...super.styles,te`
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
      `]}_listItemAfterTemplate(t,r){return W`
      <craft-button
        icon
        size="small"
        variant="plain"
        class="selected__list__item__remove-button"
        aria-label="${this.msgLit("lion-input-file:removeButtonLabel",{fileName:t.systemFile.name})}"
        @click=${()=>this._removeFile(t)}
      >
        ${this._removeButtonContentTemplate()}
      </craft-button>
    `}_removeButtonContentTemplate(){return W`<craft-icon name="x"></craft-icon>`}_listItemBeforeTemplate(t){return W`<img src="${t.downloadUrl}" alt="" class="preview-thumb" />`}},TM=te`
  /* Add any craft-specific styles for input-file here */
  ::slotted([slot='selected-file-list']) {
    margin-block-start: var(--c-spacing-lg);
  }
`,kM=class extends CM{static get styles(){return[...super.styles,wa,TM]}get slots(){return{...super.slots,"file-select-button":()=>W`<craft-button
          type="button"
          id="select-button-${this._inputId}"
          @click="${this.__openDialogOnBtnClick}"
        >
          ${this.buttonLabel}
        </craft-button>`}}static get scopedElements(){return{...super.scopedElements,"lion-selected-file-list":AM}}};customElements.get("craft-input-file")||customElements.define("craft-input-file",kM);var OM=class extends Event{constructor(){super("wa-load",{bubbles:!0,cancelable:!1,composed:!0})}};var NM=class extends Event{constructor(){super("wa-error",{bubbles:!0,cancelable:!1,composed:!0})}},PM=`:host {
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
`,Xs=Symbol(),Ya=Symbol(),Dd,Vd=new Map,Nt=class extends qt{constructor(){super(...arguments),this.svg=null,this.autoWidth=!1,this.swapOpacity=!1,this.label="",this.library="default",this.resolveIcon=async(e,t)=>{let r;if(t?.spriteSheet){this.hasUpdated||await this.updateComplete,this.svg=W`<svg part="svg">
        <use part="use" href="${e}"></use>
      </svg>`,await this.updateComplete;const n=this.shadowRoot.querySelector("[part='svg']");return typeof t.mutator=="function"&&t.mutator(n,this),this.svg}try{if(r=await fetch(e,{mode:"cors"}),!r.ok)return r.status===410?Xs:Ya}catch{return Ya}try{const n=document.createElement("div");n.innerHTML=await r.text();const i=n.firstElementChild;if(i?.tagName?.toLowerCase()!=="svg")return Xs;Dd||(Dd=new DOMParser);const o=Dd.parseFromString(i.outerHTML,"text/html").body.querySelector("svg");return o?(o.part.add("svg"),document.adoptNode(o)):Xs}catch{return Xs}}}connectedCallback(){super.connectedCallback(),HL(this)}firstUpdated(e){super.firstUpdated(e),this.setIcon()}disconnectedCallback(){super.disconnectedCallback(),jL(this)}getIconSource(){const e=Td(this.library),t=this.family||GL();return this.name&&e?{url:e.resolver(this.name,t,this.variant,this.autoWidth),fromLibrary:!0}:{url:this.src,fromLibrary:!1}}handleLabelChange(){typeof this.label=="string"&&this.label.length>0?(this.setAttribute("role","img"),this.setAttribute("aria-label",this.label),this.removeAttribute("aria-hidden")):(this.removeAttribute("role"),this.removeAttribute("aria-label"),this.setAttribute("aria-hidden","true"))}async setIcon(){const{url:e,fromLibrary:t}=this.getIconSource(),r=t?Td(this.library):void 0;if(!e){this.svg=null;return}let n=Vd.get(e);n||(n=this.resolveIcon(e,r),Vd.set(e,n));const i=await n;if(i===Ya&&Vd.delete(e),e===this.getIconSource().url){if(gE(i)){this.svg=i;return}switch(i){case Ya:case Xs:this.svg=null,this.dispatchEvent(new NM);break;default:this.svg=i.cloneNode(!0),r?.mutator?.(this.svg,this),this.dispatchEvent(new OM)}}}updated(e){super.updated(e);const t=Td(this.library),r=this.shadowRoot?.querySelector("svg");r&&t?.mutator?.(r,this)}render(){return this.hasUpdated?this.svg:W`<svg part="svg" fill="currentColor" width="16" height="16"></svg>`}};Nt.css=PM;D([er()],Nt.prototype,"svg",2);D([V({reflect:!0})],Nt.prototype,"name",2);D([V({reflect:!0})],Nt.prototype,"family",2);D([V({reflect:!0})],Nt.prototype,"variant",2);D([V({attribute:"auto-width",type:Boolean,reflect:!0})],Nt.prototype,"autoWidth",2);D([V({attribute:"swap-opacity",type:Boolean,reflect:!0})],Nt.prototype,"swapOpacity",2);D([V()],Nt.prototype,"src",2);D([V()],Nt.prototype,"label",2);D([V({reflect:!0})],Nt.prototype,"library",2);D([ar("label")],Nt.prototype,"handleLabelChange",1);D([ar(["family","name","library","variant","src","autoWidth","swapOpacity"])],Nt.prototype,"setIcon",1);Nt=D([Lr("wa-icon")],Nt);var FM=te``,IM=class extends Nt{static get styles(){return[Nt.styles,FM]}};customElements.get("craft-icon")||customElements.define("craft-icon",IM);var LM=function(e,t,r,n,i){if(n==="m")throw new TypeError("Private method is not writable");if(n==="a"&&!i)throw new TypeError("Private accessor was defined without a setter");if(typeof t=="function"?e!==t||!i:!t.has(e))throw new TypeError("Cannot write private member to an object whose class did not declare it");return n==="a"?i.call(e,r):i?i.value=r:t.set(e,r),r},cv=function(e,t,r,n){if(r==="a"&&!n)throw new TypeError("Private accessor was defined without a getter");if(typeof t=="function"?e!==t||!n:!t.has(e))throw new TypeError("Cannot read private member from an object whose class did not declare it");return r==="m"?n:r==="a"?n.call(e):n?n.value:t.get(e)},no;class RM{formatToParts(t){const r=[];for(const n of t)r.push({type:"element",value:n}),r.push({type:"literal",value:", "});return r.slice(0,-1)}}const $M=typeof Intl<"u"&&Intl.ListFormat||RM,MM=[["years","year"],["months","month"],["weeks","week"],["days","day"],["hours","hour"],["minutes","minute"],["seconds","second"],["milliseconds","millisecond"]],DM={minimumIntegerDigits:2};class VM{constructor(t,r={}){no.set(this,void 0);let n=String(r.style||"short");n!=="long"&&n!=="short"&&n!=="narrow"&&n!=="digital"&&(n="short");let i=n==="digital"?"numeric":n;const s=r.hours||i;i=s==="2-digit"?"numeric":s;const o=r.minutes||i;i=o==="2-digit"?"numeric":o;const a=r.seconds||i;i=a==="2-digit"?"numeric":a;const l=r.milliseconds||i;LM(this,no,{locale:t,style:n,years:r.years||n==="digital"?"short":n,yearsDisplay:r.yearsDisplay==="always"?"always":"auto",months:r.months||n==="digital"?"short":n,monthsDisplay:r.monthsDisplay==="always"?"always":"auto",weeks:r.weeks||n==="digital"?"short":n,weeksDisplay:r.weeksDisplay==="always"?"always":"auto",days:r.days||n==="digital"?"short":n,daysDisplay:r.daysDisplay==="always"?"always":"auto",hours:s,hoursDisplay:r.hoursDisplay==="always"||n==="digital"?"always":"auto",minutes:o,minutesDisplay:r.minutesDisplay==="always"||n==="digital"?"always":"auto",seconds:a,secondsDisplay:r.secondsDisplay==="always"||n==="digital"?"always":"auto",milliseconds:l,millisecondsDisplay:r.millisecondsDisplay==="always"?"always":"auto"},"f")}resolvedOptions(){return cv(this,no,"f")}formatToParts(t){const r=[],n=cv(this,no,"f"),i=n.style,s=n.locale;for(const[o,a]of MM){const l=t[o];if(n[`${o}Display`]==="auto"&&!l)continue;const u=n[o],c=u==="2-digit"?DM:u==="numeric"?{}:{style:"unit",unit:a,unitDisplay:u};let d=new Intl.NumberFormat(s,c).format(l);o==="months"&&(u==="narrow"||i==="narrow"&&d.endsWith("m"))&&(d=d.replace(/(\d+)m$/,"$1mo")),r.push(d)}return new $M(s,{type:"unit",style:i==="digital"?"short":i}).formatToParts(r)}format(t){return this.formatToParts(t).map(r=>r.value).join("")}}no=new WeakMap;const AE=/^[-+]?P(?:(\d+)Y)?(?:(\d+)M)?(?:(\d+)W)?(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?)?$/,lc=["year","month","week","day","hour","minute","second","millisecond"],BM=e=>AE.test(e);class Jt{constructor(t=0,r=0,n=0,i=0,s=0,o=0,a=0,l=0){this.years=t,this.months=r,this.weeks=n,this.days=i,this.hours=s,this.minutes=o,this.seconds=a,this.milliseconds=l,this.years||(this.years=0),this.sign||(this.sign=Math.sign(this.years)),this.months||(this.months=0),this.sign||(this.sign=Math.sign(this.months)),this.weeks||(this.weeks=0),this.sign||(this.sign=Math.sign(this.weeks)),this.days||(this.days=0),this.sign||(this.sign=Math.sign(this.days)),this.hours||(this.hours=0),this.sign||(this.sign=Math.sign(this.hours)),this.minutes||(this.minutes=0),this.sign||(this.sign=Math.sign(this.minutes)),this.seconds||(this.seconds=0),this.sign||(this.sign=Math.sign(this.seconds)),this.milliseconds||(this.milliseconds=0),this.sign||(this.sign=Math.sign(this.milliseconds)),this.blank=this.sign===0}abs(){return new Jt(Math.abs(this.years),Math.abs(this.months),Math.abs(this.weeks),Math.abs(this.days),Math.abs(this.hours),Math.abs(this.minutes),Math.abs(this.seconds),Math.abs(this.milliseconds))}static from(t){var r;if(typeof t=="string"){const n=String(t).trim(),i=n.startsWith("-")?-1:1,s=(r=n.match(AE))===null||r===void 0?void 0:r.slice(1).map(o=>(Number(o)||0)*i);return s?new Jt(...s):new Jt}else if(typeof t=="object"){const{years:n,months:i,weeks:s,days:o,hours:a,minutes:l,seconds:u,milliseconds:c}=t;return new Jt(n,i,s,o,a,l,u,c)}throw new RangeError("invalid duration")}static compare(t,r){const n=Date.now(),i=Math.abs(uv(n,Jt.from(t)).getTime()-n),s=Math.abs(uv(n,Jt.from(r)).getTime()-n);return i>s?-1:i<s?1:0}toLocaleString(t,r){return new VM(t,r).format(this)}}function uv(e,t){const r=new Date(e);return t.sign<0?(r.setUTCSeconds(r.getUTCSeconds()+t.seconds),r.setUTCMinutes(r.getUTCMinutes()+t.minutes),r.setUTCHours(r.getUTCHours()+t.hours),r.setUTCDate(r.getUTCDate()+t.weeks*7+t.days),r.setUTCMonth(r.getUTCMonth()+t.months),r.setUTCFullYear(r.getUTCFullYear()+t.years)):(r.setUTCFullYear(r.getUTCFullYear()+t.years),r.setUTCMonth(r.getUTCMonth()+t.months),r.setUTCDate(r.getUTCDate()+t.weeks*7+t.days),r.setUTCHours(r.getUTCHours()+t.hours),r.setUTCMinutes(r.getUTCMinutes()+t.minutes),r.setUTCSeconds(r.getUTCSeconds()+t.seconds)),r}function UM(e,t="second",r=Date.now()){const n=e.getTime()-r;if(n===0)return new Jt;const i=Math.sign(n),s=Math.abs(n),o=Math.floor(s/1e3),a=Math.floor(o/60),l=Math.floor(a/60),u=Math.floor(l/24),c=Math.floor(u/30),d=Math.floor(c/12),p=lc.indexOf(t)||lc.length;return new Jt(p>=0?d*i:0,p>=1?(c-d*12)*i:0,0,p>=3?(u-c*30)*i:0,p>=4?(l-u*24)*i:0,p>=5?(a-l*60)*i:0,p>=6?(o-a*60)*i:0,p>=7?(s-o*1e3)*i:0)}function TE(e,{relativeTo:t=Date.now()}={}){if(t=new Date(t),e.blank)return e;const r=e.sign;let n=Math.abs(e.years),i=Math.abs(e.months),s=Math.abs(e.weeks),o=Math.abs(e.days),a=Math.abs(e.hours),l=Math.abs(e.minutes),u=Math.abs(e.seconds),c=Math.abs(e.milliseconds);c>=900&&(u+=Math.round(c/1e3)),(u||l||a||o||s||i||n)&&(c=0),u>=55&&(l+=Math.round(u/60)),(l||a||o||s||i||n)&&(u=0),l>=55&&(a+=Math.round(l/60)),(a||o||s||i||n)&&(l=0),o&&a>=12&&(o+=Math.round(a/24)),!o&&a>=21&&(o+=Math.round(a/24)),(o||s||i||n)&&(a=0);const d=t.getFullYear(),p=t.getMonth(),m=t.getDate();if(o>=27||n+i+o){const h=new Date(t);h.setDate(1),h.setMonth(p+i*r+1),h.setDate(0);const f=Math.max(0,m-h.getDate()),b=new Date(t);b.setFullYear(d+n*r),b.setDate(m-f),b.setMonth(p+i*r),b.setDate(m-f+o*r);const y=b.getFullYear()-t.getFullYear(),w=b.getMonth()-t.getMonth(),v=Math.abs(Math.round((Number(b)-Number(t))/864e5))+f,_=Math.abs(y*12+w);v<27?(o>=6?(s+=Math.round(o/7),o=0):o=v,i=n=0):_<=11?(i=_,n=0):(i=0,n=y*r),(i||n)&&(o=0)}return n&&(i=0),s>=4&&(i+=Math.round(s/4)),(i||n)&&(s=0),o&&s&&!i&&!n&&(s+=Math.round(o/7),o=0),new Jt(n*r,i*r,s*r,o*r,a*r,l*r,u*r,c*r)}function zM(e,t){const r=TE(e,t);if(r.blank)return[0,"second"];for(const n of lc){if(n==="millisecond")continue;const i=r[`${n}s`];if(i)return[i,n]}return[0,"second"]}var Fe=function(e,t,r,n){if(r==="a"&&!n)throw new TypeError("Private accessor was defined without a getter");if(typeof t=="function"?e!==t||!n:!t.has(e))throw new TypeError("Cannot read private member from an object whose class did not declare it");return r==="m"?n:r==="a"?n.call(e):n?n.value:t.get(e)},Xa=function(e,t,r,n,i){if(n==="m")throw new TypeError("Private method is not writable");if(n==="a"&&!i)throw new TypeError("Private accessor was defined without a setter");if(typeof t=="function"?e!==t||!i:!t.has(e))throw new TypeError("Cannot write private member to an object whose class did not declare it");return n==="a"?i.call(e,r):i?i.value=r:t.set(e,r),r},vt,io,so,Yi,vi,Zh,kE,OE,NE,PE,FE,ef,IE,es;const qM=globalThis.HTMLElement||null,Bd=new Jt,dv=new Jt(0,0,0,0,0,1);class HM extends Event{constructor(t,r,n,i){super("relative-time-updated",{bubbles:!0,composed:!0}),this.oldText=t,this.newText=r,this.oldTitle=n,this.newTitle=i}}function hv(e){if(!e.date)return 1/0;if(e.format==="duration"||e.format==="elapsed"){const r=e.precision;if(r==="second")return 1e3;if(r==="minute")return 60*1e3}const t=Math.abs(Date.now()-e.date.getTime());return t<60*1e3?1e3:t<3600*1e3?60*1e3:3600*1e3}const Ud=new class{constructor(){this.elements=new Set,this.time=1/0,this.timer=-1}observe(e){if(this.elements.has(e))return;this.elements.add(e);const t=e.date;if(t&&t.getTime()){const r=hv(e),n=Date.now()+r;n<this.time&&(clearTimeout(this.timer),this.timer=setTimeout(()=>this.update(),r),this.time=n)}}unobserve(e){this.elements.has(e)&&this.elements.delete(e)}update(){if(clearTimeout(this.timer),!this.elements.size)return;let e=1/0;for(const t of this.elements)e=Math.min(e,hv(t)),t.update();this.time=Math.min(3600*1e3,e),this.timer=setTimeout(()=>this.update(),this.time),this.time+=Date.now()}};class jM extends qM{constructor(){super(...arguments),vt.add(this),io.set(this,!1),so.set(this,!1),vi.set(this,this.shadowRoot?this.shadowRoot:this.attachShadow?this.attachShadow({mode:"open"}):this),es.set(this,null)}static define(t="relative-time",r=customElements){return r.define(t,this),this}get timeZone(){var t;return((t=this.closest("[time-zone]"))===null||t===void 0?void 0:t.getAttribute("time-zone"))||this.ownerDocument.documentElement.getAttribute("time-zone")||void 0}static get observedAttributes(){return["second","minute","hour","weekday","day","month","year","time-zone-name","prefix","threshold","tense","precision","format","format-style","no-title","datetime","lang","title","aria-hidden","time-zone"]}get onRelativeTimeUpdated(){return Fe(this,es,"f")}set onRelativeTimeUpdated(t){Fe(this,es,"f")&&this.removeEventListener("relative-time-updated",Fe(this,es,"f")),Xa(this,es,typeof t=="object"||typeof t=="function"?t:null,"f"),typeof t=="function"&&this.addEventListener("relative-time-updated",t)}get second(){const t=this.getAttribute("second");if(t==="numeric"||t==="2-digit")return t}set second(t){this.setAttribute("second",t||"")}get minute(){const t=this.getAttribute("minute");if(t==="numeric"||t==="2-digit")return t}set minute(t){this.setAttribute("minute",t||"")}get hour(){const t=this.getAttribute("hour");if(t==="numeric"||t==="2-digit")return t}set hour(t){this.setAttribute("hour",t||"")}get weekday(){const t=this.getAttribute("weekday");if(t==="long"||t==="short"||t==="narrow")return t;if(this.format==="datetime"&&t!=="")return this.formatStyle}set weekday(t){this.setAttribute("weekday",t||"")}get day(){var t;const r=(t=this.getAttribute("day"))!==null&&t!==void 0?t:"numeric";if(r==="numeric"||r==="2-digit")return r}set day(t){this.setAttribute("day",t||"")}get month(){const t=this.format;let r=this.getAttribute("month");if(r!==""&&(r??(r=t==="datetime"?this.formatStyle:"short"),r==="numeric"||r==="2-digit"||r==="short"||r==="long"||r==="narrow"))return r}set month(t){this.setAttribute("month",t||"")}get year(){var t;const r=this.getAttribute("year");if(r==="numeric"||r==="2-digit")return r;if(!this.hasAttribute("year")&&new Date().getUTCFullYear()!==((t=this.date)===null||t===void 0?void 0:t.getUTCFullYear()))return"numeric"}set year(t){this.setAttribute("year",t||"")}get timeZoneName(){const t=this.getAttribute("time-zone-name");if(t==="long"||t==="short"||t==="shortOffset"||t==="longOffset"||t==="shortGeneric"||t==="longGeneric")return t}set timeZoneName(t){this.setAttribute("time-zone-name",t||"")}get prefix(){var t;return(t=this.getAttribute("prefix"))!==null&&t!==void 0?t:this.format==="datetime"?"":"on"}set prefix(t){this.setAttribute("prefix",t)}get threshold(){const t=this.getAttribute("threshold");return t&&BM(t)?t:"P30D"}set threshold(t){this.setAttribute("threshold",t)}get tense(){const t=this.getAttribute("tense");return t==="past"?"past":t==="future"?"future":"auto"}set tense(t){this.setAttribute("tense",t)}get precision(){const t=this.getAttribute("precision");return lc.includes(t)?t:this.format==="micro"?"minute":"second"}set precision(t){this.setAttribute("precision",t)}get format(){const t=this.getAttribute("format");return t==="datetime"?"datetime":t==="relative"?"relative":t==="duration"?"duration":t==="micro"?"micro":t==="elapsed"?"elapsed":"auto"}set format(t){this.setAttribute("format",t)}get formatStyle(){const t=this.getAttribute("format-style");if(t==="long")return"long";if(t==="short")return"short";if(t==="narrow")return"narrow";const r=this.format;return r==="elapsed"||r==="micro"?"narrow":r==="datetime"?"short":"long"}set formatStyle(t){this.setAttribute("format-style",t)}get noTitle(){return this.hasAttribute("no-title")}set noTitle(t){this.toggleAttribute("no-title",t)}get datetime(){return this.getAttribute("datetime")||""}set datetime(t){this.setAttribute("datetime",t)}get date(){const t=Date.parse(this.datetime);return Number.isNaN(t)?null:new Date(t)}set date(t){this.datetime=t?.toISOString()||""}connectedCallback(){this.update()}disconnectedCallback(){Ud.unobserve(this)}attributeChangedCallback(t,r,n){r!==n&&(t==="title"&&Xa(this,io,n!==null&&(this.date&&Fe(this,vt,"m",Zh).call(this,this.date))!==n,"f"),!Fe(this,so,"f")&&!(t==="title"&&Fe(this,io,"f"))&&Xa(this,so,(async()=>{await Promise.resolve(),this.update(),Xa(this,so,!1,"f")})(),"f"))}update(){const t=Fe(this,vi,"f").textContent||this.textContent||"",r=this.getAttribute("title")||"";let n=r;const i=this.date;if(typeof Intl>"u"||!Intl.DateTimeFormat||!i){Fe(this,vi,"f").textContent=t;return}const s=Date.now();Fe(this,io,"f")||(n=Fe(this,vt,"m",Zh).call(this,i)||"",n&&!this.noTitle&&this.setAttribute("title",n));const o=UM(i,this.precision,s),a=Fe(this,vt,"m",kE).call(this,o);let l=t;const u=Fe(this,vt,"m",IE).call(this,a);u?l=Fe(this,vt,"m",FE).call(this,i):a==="duration"?l=Fe(this,vt,"m",OE).call(this,o):a==="relative"?l=Fe(this,vt,"m",NE).call(this,o):l=Fe(this,vt,"m",PE).call(this,i),l?Fe(this,vt,"m",ef).call(this,l):this.shadowRoot===Fe(this,vi,"f")&&this.textContent&&Fe(this,vt,"m",ef).call(this,this.textContent),(l!==t||n!==r)&&this.dispatchEvent(new HM(t,l,r,n)),(a==="relative"||a==="duration")&&!u?Ud.observe(this):Ud.unobserve(this)}}io=new WeakMap,so=new WeakMap,vi=new WeakMap,es=new WeakMap,vt=new WeakSet,Yi=function(){var t;const r=((t=this.closest("[lang]"))===null||t===void 0?void 0:t.getAttribute("lang"))||this.ownerDocument.documentElement.getAttribute("lang");try{return new Intl.Locale(r??"").toString()}catch{return"default"}},Zh=function(t){return new Intl.DateTimeFormat(Fe(this,vt,"a",Yi),{day:"numeric",month:"short",year:"numeric",hour:"numeric",minute:"2-digit",timeZoneName:"short",timeZone:this.timeZone}).format(t)},kE=function(t){const r=this.format;if(r==="datetime")return"datetime";if(r==="duration"||r==="elapsed"||r==="micro")return"duration";if((r==="auto"||r==="relative")&&typeof Intl<"u"&&Intl.RelativeTimeFormat){const n=this.tense;if(n==="past"||n==="future"||Jt.compare(t,this.threshold)===1)return"relative"}return"datetime"},OE=function(t){const r=Fe(this,vt,"a",Yi),n=this.format,i=this.formatStyle,s=this.tense;let o=Bd;n==="micro"?(t=TE(t),o=dv,t.months===0&&(this.tense==="past"&&t.sign!==-1||this.tense==="future"&&t.sign!==1)&&(t=dv)):(s==="past"&&t.sign!==-1||s==="future"&&t.sign!==1)&&(t=o);const a=`${this.precision}sDisplay`;return t.blank?o.toLocaleString(r,{style:i,[a]:"always"}):t.abs().toLocaleString(r,{style:i})},NE=function(t){const r=new Intl.RelativeTimeFormat(Fe(this,vt,"a",Yi),{numeric:"auto",style:this.formatStyle}),n=this.tense;n==="future"&&t.sign!==1&&(t=Bd),n==="past"&&t.sign!==-1&&(t=Bd);const[i,s]=zM(t);return s==="second"&&i<10?r.format(0,this.precision==="millisecond"?"second":this.precision):r.format(i,s)},PE=function(t){const r=new Intl.DateTimeFormat(Fe(this,vt,"a",Yi),{second:this.second,minute:this.minute,hour:this.hour,weekday:this.weekday,day:this.day,month:this.month,year:this.year,timeZoneName:this.timeZoneName,timeZone:this.timeZone});return`${this.prefix} ${r.format(t)}`.trim()},FE=function(t){return new Intl.DateTimeFormat(Fe(this,vt,"a",Yi),{day:"numeric",month:"short",year:"numeric",hour:"numeric",minute:"2-digit",timeZoneName:"short",timeZone:this.timeZone}).format(t)},ef=function(t){if(this.hasAttribute("aria-hidden")&&this.getAttribute("aria-hidden")==="true"){const r=document.createElement("span");r.setAttribute("aria-hidden","true"),r.textContent=t,Fe(this,vi,"f").replaceChildren(r)}else Fe(this,vi,"f").textContent=t},IE=function(t){var r;return t==="duration"?!1:this.ownerDocument.documentElement.getAttribute("data-prefers-absolute-time")==="true"||((r=this.ownerDocument.body)===null||r===void 0?void 0:r.getAttribute("data-prefers-absolute-time"))==="true"};const fv=typeof globalThis<"u"?globalThis:window;try{fv.RelativeTimeElement=jM.define()}catch(e){if(!(fv.DOMException&&e instanceof DOMException&&e.name==="NotSupportedError")&&!(e instanceof ReferenceError))throw e}var WM=class extends Qh{static get styles(){return[...super.styles,te`
        .input-group__input {
          font-family: var(--c-font-mono);
          font-size: 0.9em;
        }
      `]}constructor(){super(),this.autocorrect=!1}firstUpdated(t){super.firstUpdated(t),this._inputNode?.setAttribute("autocapitalize","off")}};customElements.get("craft-input-handle")||customElements.define("craft-input-handle",WM),rR();var pv=class extends ru{static get styles(){return[...super.styles,wa,te`
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
    `,this.type="password"}get slots(){return{...super.slots,suffix:()=>({template:this.renderSuffix()})}}};se([er()],pv.prototype,"_visible",void 0),customElements.get("craft-input-password")||customElements.define("craft-input-password",pv);var KM=te`
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
`,Ja=class extends Ne{constructor(...t){super(...t),this.size="",this.variant=""}render(){let t=!!this.querySelector('[slot="prefix"]'),r=!!this.querySelector('[slot="suffix"]');return W`
      <div
        class="${tr({chip:!0,"chip--small":this.size==="small","chip--medium":this.size==="medium","chip--large":this.size==="large","chip--plain":this.variant==="plain"})}"
      >
        ${t?W`<div class="chip__prefix"><slot name="prefix"></slot></div>`:pt}
        <div class="chip__body">
          <slot></slot>
        </div>
        ${r?W`<div class="chip__suffix"><slot name="suffix"></slot></div>`:pt}
      </div>
    `}};Ja.styles=[KM],se([V()],Ja.prototype,"size",void 0),se([V()],Ja.prototype,"variant",void 0),customElements.get("craft-chip")||customElements.define("craft-chip",Ja);var GM=te`
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
`,Qa=class extends Ne{constructor(...t){super(...t),this.label=null,this.status=null}getLabel(){return!this.label&&this.status?`Status: ${this.status}`:this.label}render(){return W`
      <span
        class="${tr({status:!0,"status--live":this.status==="live","status--enabled":this.status==="enabled","status--pending":this.status==="pending","status--expired":this.status==="expired","status--disabled":this.status==="disabled"})}"
        role="img"
        aria-label="${this.getLabel()}"
      ></span>
    `}};Qa.styles=[GM],se([V()],Qa.prototype,"label",void 0),se([V()],Qa.prototype,"status",void 0),customElements.get("craft-status")||customElements.define("craft-status",Qa);var To=new Map;function YM(e){var t=To.get(e);t&&t.destroy()}function XM(e){var t=To.get(e);t&&t.update()}var oo=null;typeof window>"u"?((oo=function(e){return e}).destroy=function(e){return e},oo.update=function(e){return e}):((oo=function(e,t){return e&&Array.prototype.forEach.call(e.length?e:[e],function(r){return(function(n){if(n&&n.nodeName&&n.nodeName==="TEXTAREA"&&!To.has(n)){var i,s=null,o=window.getComputedStyle(n),a=(i=n.value,function(){u({testForHeightReduction:i===""||!n.value.startsWith(i),restoreTextAlign:null}),i=n.value}),l=(function(d){n.removeEventListener("autosize:destroy",l),n.removeEventListener("autosize:update",c),n.removeEventListener("input",a),window.removeEventListener("resize",c),Object.keys(d).forEach(function(p){return n.style[p]=d[p]}),To.delete(n)}).bind(n,{height:n.style.height,resize:n.style.resize,textAlign:n.style.textAlign,overflowY:n.style.overflowY,overflowX:n.style.overflowX,wordWrap:n.style.wordWrap});n.addEventListener("autosize:destroy",l),n.addEventListener("autosize:update",c),n.addEventListener("input",a),window.addEventListener("resize",c),n.style.overflowX="hidden",n.style.wordWrap="break-word",To.set(n,{destroy:l,update:c}),c()}function u(d){var p,m,h=d.restoreTextAlign,f=h===void 0?null:h,b=d.testForHeightReduction,y=b===void 0||b,w=o.overflowY;if(n.scrollHeight!==0&&(o.resize==="vertical"?n.style.resize="none":o.resize==="both"&&(n.style.resize="horizontal"),y&&(p=(function(_){for(var x=[];_&&_.parentNode&&_.parentNode instanceof Element;)_.parentNode.scrollTop&&x.push([_.parentNode,_.parentNode.scrollTop]),_=_.parentNode;return function(){return x.forEach(function(k){var S=k[0],N=k[1];S.style.scrollBehavior="auto",S.scrollTop=N,S.style.scrollBehavior=null})}})(n),n.style.height=""),m=o.boxSizing==="content-box"?n.scrollHeight-(parseFloat(o.paddingTop)+parseFloat(o.paddingBottom)):n.scrollHeight+parseFloat(o.borderTopWidth)+parseFloat(o.borderBottomWidth),o.maxHeight!=="none"&&m>parseFloat(o.maxHeight)?(o.overflowY==="hidden"&&(n.style.overflow="scroll"),m=parseFloat(o.maxHeight)):o.overflowY!=="hidden"&&(n.style.overflow="hidden"),n.style.height=m+"px",f&&(n.style.textAlign=f),p&&p(),s!==m&&(n.dispatchEvent(new Event("autosize:resized",{bubbles:!0})),s=m),w!==o.overflow&&!f)){var v=o.textAlign;o.overflow==="hidden"&&(n.style.textAlign=v==="start"?"end":"start"),u({restoreTextAlign:v,testForHeightReduction:!0})}}function c(){u({testForHeightReduction:!0,restoreTextAlign:null})}})(r)}),e}).destroy=function(e){return e&&Array.prototype.forEach.call(e.length?e:[e],YM),e},oo.update=function(e){return e&&Array.prototype.forEach.call(e.length?e:[e],XM),e});var zd=oo;class JM extends Sa{get _inputNode(){return Array.from(this.children).find(t=>t.slot==="input")}}class QM extends EE(JM){static get properties(){return{maxRows:{type:Number,attribute:"max-rows"},rows:{type:Number,reflect:!0},readOnly:{type:Boolean,attribute:"readonly",reflect:!0},placeholder:{type:String,reflect:!0}}}get slots(){return{...super.slots,input:()=>{const t=document.createElement("textarea");return t.style.resize!==void 0&&(t.style.resize="none"),t}}}constructor(){super(),this.rows=2,this.maxRows=6,this.readOnly=!1,this.placeholder=""}connectedCallback(){super.connectedCallback(),this.__initializeAutoresize(),this.__intersectionObserver=new IntersectionObserver(()=>this.resizeTextarea()),this.__intersectionObserver.observe(this)}updated(t){if(super.updated(t),t.has("name")&&(this._inputNode.name=this.name),t.has("autocomplete")&&(this._inputNode.autocomplete=this.autocomplete),t.has("disabled")&&(this._inputNode.disabled=this.disabled,this.validate()),t.has("rows")){const r=this._inputNode;r&&(r.rows=this.rows)}if(t.has("readOnly")){const r=this._inputNode;r&&(r.readOnly=this.readOnly)}if(t.has("placeholder")){const r=this._inputNode;r&&(r.placeholder=this.placeholder)}t.has("modelValue")&&this.resizeTextarea(),(t.has("maxRows")||t.has("rows"))&&this.setTextareaMaxHeight()}disconnectedCallback(){super.disconnectedCallback(),zd.destroy(this._inputNode)}setTextareaMaxHeight(){const{value:t}=this._inputNode;this._inputNode.value="",this.resizeTextarea();const r=window.getComputedStyle(this._inputNode,null),n=parseFloat(r.lineHeight)||parseFloat(r.height)/this.rows,i=parseFloat(r.paddingTop)+parseFloat(r.paddingBottom),s=parseFloat(r.borderTopWidth)+parseFloat(r.borderBottomWidth),o=r.boxSizing==="border-box"?i+s:0;this._inputNode.style.maxHeight=`${n*this.maxRows+o}px`,this._inputNode.value=t,this.resizeTextarea()}static get styles(){return[...super.styles,te`
        .input-group__container > .input-group__input ::slotted(.form-control) {
          box-sizing: content-box;
          overflow-x: hidden; /* for FF adds height to the TextArea to reserve place for scroll-bars */
        }

        /* Workaround https://bugzilla.mozilla.org/show_bug.cgi?id=1739079 */
        :host([disabled]) ::slotted(textarea) {
          user-select: none;
        }
      `]}get updateComplete(){return this.__textareaUpdateComplete?Promise.all([this.__textareaUpdateComplete,super.updateComplete]):super.updateComplete}resizeTextarea(){zd.update(this._inputNode)}__initializeAutoresize(){this.__shady_native_contains?this.__textareaUpdateComplete=this.__waitForTextareaRenderedInRealDOM().then(()=>{this.__startAutoresize(),this.__textareaUpdateComplete=null}):this.__startAutoresize()}async __waitForTextareaRenderedInRealDOM(){let t=3;for(;t!==0&&!this.__shady_native_contains(this._inputNode);)await new Promise(r=>setTimeout(r)),t-=1}__startAutoresize(){zd(this._inputNode),this.setTextareaMaxHeight()}}var ZM=te`
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
`,eD=class extends QM{static get styles(){return[...super.styles,wa,ZM]}};customElements.get("craft-textarea")||customElements.define("craft-textarea",eD);var tD=te`
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
`,mv=class extends Ne{render(){return W`<slot></slot>`}};mv.styles=[tD],customElements.get("craft-button-group")||customElements.define("craft-button-group",mv);class rD extends Sa{static get properties(){return{autocomplete:{type:String}}}constructor(){super(),this.autocomplete=void 0}get _inputNode(){return Array.from(this.children).find(t=>t.slot==="input")}}class nD extends rD{get operationMode(){return"select"}connectedCallback(){super.connectedCallback(),this._inputNode.addEventListener("change",this._proxyChangeEvent),this.__selectObserver=new MutationObserver(()=>{this._syncValueUpwards(),this._calculateValues({source:"model"})}),this.__selectObserver.observe(this._inputNode,{attributes:!0,childList:!0,subtree:!0})}updated(t){super.updated(t),t.has("disabled")&&(this._inputNode.disabled=this.disabled,this.validate()),t.has("name")&&(this._inputNode.name=this.name),t.has("autocomplete")&&(this._inputNode.autocomplete=this.autocomplete)}disconnectedCallback(){super.disconnectedCallback(),this._inputNode.removeEventListener("change",this._proxyChangeEvent),this.__selectObserver?.disconnect()}formatter(t){const r=Array.from(this._inputNode.options).find(n=>n.value===t);return r?r.text:""}_reflectBackFormattedValueToUser(){this._reflectBackOn()&&(this.value=typeof this.modelValue<"u"?this.modelValue:"")}_proxyChangeEvent(){this.dispatchEvent(new CustomEvent("user-input-changed",{bubbles:!0,composed:!0}))}}var iD=te`
  ${Xc}

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
    ${Ip}
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
`,sD=class extends nD{static get styles(){return[...super.styles,iD]}_inputGroupInputTemplate(){return W`
      <div class="input-group__input">
        <slot name="input"></slot>
        <craft-icon
          class="indicator"
          name="chevron-down"
          style="font-size: 0.8em"
        ></craft-icon>
      </div>
    `}};customElements.get("craft-select")||customElements.define("craft-select",sD);class oD extends pM(Ne){static get properties(){return{tabIndex:{type:Number,reflect:!0,attribute:"tabindex"}}}constructor(){super(),this.tabIndex=0}connectedCallback(){super.connectedCallback(),this.setAttribute("role","listbox")}createRenderRoot(){return this}}function gv(e,t){Array.from(e.childNodes).forEach(r=>{r.hasAttribute&&r.hasAttribute("slot")||t.appendChild(r)})}const aD=e=>class extends Ui(Ea(eu(Ms(Dp(e))))){static get properties(){return{orientation:String,selectionFollowsFocus:{type:Boolean,attribute:"selection-follows-focus"},rotateKeyboardNavigation:{type:Boolean,attribute:"rotate-keyboard-navigation"},hasNoDefaultSelected:{type:Boolean,reflect:!0,attribute:"has-no-default-selected"},_noTypeAhead:{type:Boolean}}}static get styles(){return[...super.styles||[],te`
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
      `}static get scopedElements(){return{...super.scopedElements,"lion-options":oD}}get slots(){return{...super.slots,input:()=>{const r=this.createScopedElement("lion-options");return r.setAttribute("data-tag-name","lion-options"),r.registrationTarget=this,r}}}get _inputNode(){return this.querySelector('[slot="input"]')}get _listboxNode(){return this._inputNode}get _listboxActiveDescendantNode(){return this._listboxNode.querySelector(`#${this._listboxActiveDescendant}`)}get _listboxSlot(){return this.shadowRoot.querySelector("slot[name=input]")}get _scrollTargetNode(){return this._listboxNode}get _activeDescendantOwnerNode(){return this._listboxNode}get activeIndex(){return this.formElements.findIndex(r=>r.active===!0)}set activeIndex(r){if(this.formElements[r]){const n=this.formElements[r];this.__setChildActive(n)}else this.__setChildActive(null)}get checkedIndex(){const r=this.formElements;return this.multipleChoice?r.filter(n=>n.checked).map(n=>r.indexOf(n)):r.indexOf(r.find(n=>n.checked))}set checkedIndex(r){this.setCheckedIndex(r)}constructor(){super(),this.hasNoDefaultSelected=!1,this.orientation="vertical",this.rotateKeyboardNavigation=!1,this.selectionFollowsFocus=!1,this._noTypeAhead=!1,this._typeAheadTimeout=1e3,this._listboxActiveDescendant=null,this.__hasInitialSelectedFormElement=!1,this._repropagationRole="choice-group",this._listboxReceivesNoFocus=!1,this._oldModelValue=void 0,this._listboxOnKeyDown=this._listboxOnKeyDown.bind(this),this._listboxOnClick=this._listboxOnClick.bind(this),this._listboxOnKeyUp=this._listboxOnKeyUp.bind(this),this._onChildActiveChanged=this._onChildActiveChanged.bind(this),this.__proxyChildModelValueChanged=this.__proxyChildModelValueChanged.bind(this),this.__preventScrollingWithArrowKeys=this.__preventScrollingWithArrowKeys.bind(this),this.__typedChars=[]}connectedCallback(){this._listboxNode&&(this._listboxNode.registrationTarget=this),super.connectedCallback(),this._setupListboxNode(),this.__setupEventListeners(),this.registrationComplete.then(()=>{this.__initInteractionStates()})}firstUpdated(r){super.firstUpdated(r),this.__moveOptionsToListboxNode(),this.registrationComplete.then(()=>{this._initialModelValue=this.modelValue}),new MutationObserver(()=>{this._onListboxContentChanged()}).observe(this._listboxNode,{childList:!0})}updated(r){super.updated(r),r.has("disabled")&&(this.disabled?this.__requestOptionsToBeDisabled():this.__retractRequestOptionsToBeDisabled())}disconnectedCallback(){super.disconnectedCallback(),this._teardownListboxNode(),this.__teardownEventListeners()}setCheckedIndex(r){if(this.multipleChoice&&Array.isArray(r)){this._uncheckChildren(this.formElements.filter(n=>n===r)),r.forEach(n=>{this.formElements[n]&&(this.formElements[n].checked=!this.formElements[n].checked)});return}typeof r=="number"&&(r===-1&&this._uncheckChildren(),this.formElements[r]&&(this.formElements[r].disabled?this._uncheckChildren():this.multipleChoice?this.formElements[r].checked=!this.formElements[r].checked:this.formElements[r].checked=!0))}addFormElement(r,n){super.addFormElement(r,n),r.id=r.id||`${this.localName}-option-${_a()}`,this.disabled&&r.makeRequestToBeDisabled(),this.__setAttributeForAllFormElements("aria-setsize",this.formElements.length),this.formElements.forEach((i,s)=>{i.setAttribute("aria-posinset",s+1)}),this.__proxyChildModelValueChanged({target:r}),this.resetInteractionState()}resetInteractionState(){super.resetInteractionState(),this.submitted=!1}reset(){this.modelValue=this._initialModelValue,this.activeIndex=-1,this.resetInteractionState()}clear(){super.clear(),this.setCheckedIndex(-1),this.resetInteractionState()}_handleTypeAhead(r,{setAsChecked:n}){const{key:i,code:s}=r;if(s.startsWith("Key")||s.startsWith("Digit")||s.startsWith("Numpad")){r.preventDefault(),this.__typedChars.push(i);const o=this.__typedChars.join(""),a=this.formElements.findIndex(l=>l.modelValue.value.toLowerCase().startsWith(o));a>=0&&(n&&this.setCheckedIndex(a),this.activeIndex=a),this.__pendingTypeAheadTimeout&&window.clearTimeout(this.__pendingTypeAheadTimeout),this.__pendingTypeAheadTimeout=setTimeout(()=>{this.__typedChars=[]},this._typeAheadTimeout)}}_getCheckedElements(){return this.formElements.filter(r=>r.checked)}_setupListboxNode(){this._listboxNode?this.__setupListboxNodeInteractions():this._listboxSlot&&this._listboxSlot.addEventListener("slotchange",()=>{this.__setupListboxNodeInteractions()})}_onListboxContentChanged(){}_teardownListboxNode(){this._listboxNode&&(this._listboxNode.removeEventListener("keydown",this._listboxOnKeyDown),this._listboxNode.removeEventListener("click",this._listboxOnClick),this._listboxNode.removeEventListener("keyup",this._listboxOnKeyUp))}_getNextEnabledOption(r,n=1){return this.__getEnabledOption(r,n)}_getPreviousEnabledOption(r,n=-1){return this.__getEnabledOption(r,n)}_onChildActiveChanged({target:r}){r.active===!0&&this.__setChildActive(r)}_listboxOnKeyDown(r){if(this.disabled)return;this._isHandlingUserInput=!0,setTimeout(()=>{this._isHandlingUserInput=!1});const{key:n}=r;switch(n){case" ":case"Enter":{if(n===" "&&this._listboxReceivesNoFocus||(n===" "&&r.preventDefault(),!this.formElements[this.activeIndex])||this.formElements[this.activeIndex].disabled)return;this.formElements[this.activeIndex].href&&this.formElements[this.activeIndex].click(),this.setCheckedIndex(this.activeIndex);break}case"ArrowUp":r.preventDefault(),this.orientation==="vertical"&&(this.activeIndex=this._getPreviousEnabledOption(this.activeIndex));break;case"ArrowLeft":if(this._listboxReceivesNoFocus)return;r.preventDefault(),this.orientation==="horizontal"&&(this.activeIndex=this._getPreviousEnabledOption(this.activeIndex));break;case"ArrowDown":r.preventDefault(),this.orientation==="vertical"&&(this.activeIndex=this._getNextEnabledOption(this.activeIndex));break;case"ArrowRight":if(this._listboxReceivesNoFocus)return;r.preventDefault(),this.orientation==="horizontal"&&(this.activeIndex=this._getNextEnabledOption(this.activeIndex));break;case"Home":if(this._listboxReceivesNoFocus)return;r.preventDefault(),this.activeIndex=this._getNextEnabledOption(0,0);break;case"End":if(this._listboxReceivesNoFocus)return;r.preventDefault(),this.activeIndex=this._getPreviousEnabledOption(this.formElements.length-1,0);break;default:this._noTypeAhead||this._handleTypeAhead(r,{setAsChecked:this.selectionFollowsFocus&&!this.multipleChoice})}["ArrowUp","ArrowDown","ArrowLeft","ArrowRight","Home","End"].includes(n)&&this.selectionFollowsFocus&&!this.multipleChoice&&this.setCheckedIndex(this.activeIndex)}_listboxOnClick(r){}_listboxOnKeyUp(r){if(this.disabled)return;this._isHandlingUserInput=!0,setTimeout(()=>{this._isHandlingUserInput=!1});const{key:n}=r;switch(n){case"ArrowUp":case"ArrowDown":case"Home":case"End":case"Enter":r.preventDefault()}}_onLabelClick(){this._listboxNode.focus()}_scrollIntoView(r,n){r.scrollIntoView({behavior:"smooth",block:"nearest"})}__setupEventListeners(){this._listboxNode.addEventListener("active-changed",this._onChildActiveChanged),this._listboxNode.addEventListener("model-value-changed",this.__proxyChildModelValueChanged)}__teardownEventListeners(){this._listboxNode.removeEventListener("active-changed",this._onChildActiveChanged),this._listboxNode.removeEventListener("model-value-changed",this.__proxyChildModelValueChanged)}__setChildActive(r){if(this.formElements.forEach(n=>{n.active=r===n}),!r){this._activeDescendantOwnerNode.removeAttribute("aria-activedescendant");return}this._activeDescendantOwnerNode.setAttribute("aria-activedescendant",r.id),this._scrollIntoView(r,this._scrollTargetNode)}_uncheckChildren(r=[]){const n=Array.isArray(r)?r:[r];this.formElements.forEach(i=>{n.includes(i)||(i.checked=!1)})}__onChildCheckedChanged(r){const{target:n}=r;r.stopPropagation&&r.stopPropagation(),n.checked&&!this.multipleChoice&&this._uncheckChildren(n)}__setAttributeForAllFormElements(r,n){this.formElements.forEach(i=>{i.setAttribute(r,n)})}__proxyChildModelValueChanged(r){r.stopPropagation&&r.stopPropagation(),this.__onChildCheckedChanged(r),this.requestUpdate("modelValue",this._oldModelValue),r.detail&&r.detail.formPath&&this.dispatchEvent(new CustomEvent("model-value-changed",{detail:{formPath:r.detail.formPath,isTriggeredByUser:r.detail.isTriggeredByUser||this._isHandlingUserInput,element:r.target}})),this._oldModelValue=this.modelValue}__getEnabledOption(r,n){const i=s=>n===1?s<this.formElements.length:s>=0;for(let s=r+n;i(s);s+=n)if(this.formElements[s]&&!this.formElements[s].hasAttribute("aria-hidden"))return s;if(this.rotateKeyboardNavigation){const s=n===-1?this.formElements.length-1:0;for(let o=s;i(o);o+=n)if(this.formElements[o]&&!this.formElements[o].hasAttribute("aria-hidden"))return o}return r}__moveOptionsToListboxNode(){const r=this.shadowRoot.getElementById("options-outlet");r&&(gv(this,this._listboxNode),r.addEventListener("slotchange",()=>{gv(this,this._listboxNode)}))}__preventScrollingWithArrowKeys(r){if(this.disabled)return;const{key:n}=r;switch(n){case"ArrowUp":case"ArrowDown":case"Home":case"End":r.preventDefault()}}__setupListboxNodeInteractions(){this._listboxNode.setAttribute("role","listbox"),this._listboxNode.setAttribute("aria-orientation",this.orientation),this._listboxNode.setAttribute("aria-multiselectable",`${this.multipleChoice}`),this._listboxNode.setAttribute("tabindex","0"),this._listboxNode.addEventListener("click",this._listboxOnClick),this._listboxNode.addEventListener("keyup",this._listboxOnKeyUp),this._listboxNode.addEventListener("keydown",this._listboxOnKeyDown),this._scrollTargetNode.addEventListener("keydown",this.__preventScrollingWithArrowKeys)}__requestOptionsToBeDisabled(){this.formElements.forEach(r=>{r.makeRequestToBeDisabled&&r.makeRequestToBeDisabled()})}__retractRequestOptionsToBeDisabled(){this.formElements.forEach(r=>{r.retractRequestToBeDisabled&&r.retractRequestToBeDisabled()})}__initInteractionStates(){this.initInteractionState()}},lD=He(aD);class cD extends lD(Lp(Mp(xa(Ne)))){get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,focused:this.focused}}get _focusableNode(){return this._inputNode}}class bv extends ya(tu(Rp(Ms(Ne)))){static get properties(){return{active:{type:Boolean,reflect:!0}}}static get styles(){return[te`
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
    `}connectedCallback(){super.connectedCallback(),this.setAttribute("role","option")}__registerEventListeners(){this.addEventListener("click",this.__onClick)}__unRegisterEventListeners(){this.removeEventListener("click",this.__onClick)}__onClick(){if(this.disabled)return;const t=this._parentFormGroup;this._isHandlingUserInput=!0,t&&t.multipleChoice?(this.checked=!this.checked,this.active=!this.active):(this.checked=!0,this.active=!0),this._isHandlingUserInput=!1}}var uD=te`
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
`,vv=class extends bv{constructor(...t){super(...t),this.hint=null}static get styles(){return[...bv.styles,uD]}render(){return W`
      <div class="choice-field__label">
        <slot></slot>
        ${this.hint?W`<span class="hint">${this.hint}</span>`:pt}
        <slot name="suffix"></slot>
      </div>
    `}};se([V()],vv.prototype,"hint",void 0),customElements.get("craft-option")||customElements.define("craft-option",vv);var LE=`@layer wa-utilities {
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
`;var dD=class extends Event{constructor(e){super("wa-select",{bubbles:!0,cancelable:!0,composed:!0}),this.detail=e}};function*RE(e=document.activeElement){e!=null&&(yield e,"shadowRoot"in e&&e.shadowRoot&&e.shadowRoot.mode!=="closed"&&(yield*RE(e.shadowRoot.activeElement)))}var hD=`:host {
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
`,qd=new Set,Ot=class extends qt{constructor(){super(...arguments),this.submenuCleanups=new Map,this.localize=new Is(this),this.userTypedQuery="",this.openSubmenuStack=[],this.open=!1,this.size="medium",this.placement="bottom-start",this.distance=0,this.skidding=0,this.handleDocumentKeyDown=async e=>{const t=this.localize.dir()==="rtl";if(e.key==="Escape"){const c=this.getTrigger();e.preventDefault(),e.stopPropagation(),this.open=!1,c?.focus();return}const r=[...RE()].find(c=>c.localName==="wa-dropdown-item"),n=r?.localName==="wa-dropdown-item",i=this.getCurrentSubmenuItem(),s=!!i;let o,a,l;s?(o=this.getSubmenuItems(i),a=o.find(c=>c.active||c===r),l=a?o.indexOf(a):-1):(o=this.getItems(),a=o.find(c=>c.active||c===r),l=a?o.indexOf(a):-1);let u;if(e.key==="ArrowUp"&&(e.preventDefault(),e.stopPropagation(),l>0?u=o[l-1]:u=o[o.length-1]),e.key==="ArrowDown"&&(e.preventDefault(),e.stopPropagation(),l!==-1&&l<o.length-1?u=o[l+1]:u=o[0]),e.key===(t?"ArrowLeft":"ArrowRight")&&n&&a&&a.hasSubmenu){e.preventDefault(),e.stopPropagation(),a.submenuOpen=!0,this.addToSubmenuStack(a),setTimeout(()=>{const c=this.getSubmenuItems(a);c.length>0&&(c.forEach((d,p)=>d.active=p===0),c[0].focus())},0);return}if(e.key===(t?"ArrowRight":"ArrowLeft")&&s){e.preventDefault(),e.stopPropagation();const c=this.removeFromSubmenuStack();c&&(c.submenuOpen=!1,setTimeout(()=>{c.focus(),c.active=!0,(c.slot==="submenu"?this.getSubmenuItems(c.parentElement):this.getItems()).forEach(p=>{p!==c&&(p.active=!1)})},0));return}if((e.key==="Home"||e.key==="End")&&(e.preventDefault(),e.stopPropagation(),u=e.key==="Home"?o[0]:o[o.length-1]),e.key==="Tab"&&await this.hideMenu(),e.key.length===1&&!(e.metaKey||e.ctrlKey||e.altKey)&&!(e.key===" "&&this.userTypedQuery==="")&&(clearTimeout(this.userTypedTimeout),this.userTypedTimeout=setTimeout(()=>{this.userTypedQuery=""},1e3),this.userTypedQuery+=e.key,o.some(c=>{const d=(c.textContent||"").trim().toLowerCase(),p=this.userTypedQuery.trim().toLowerCase();return d.startsWith(p)?(u=c,!0):!1})),u){e.preventDefault(),e.stopPropagation(),o.forEach(c=>c.active=c===u),u.focus();return}(e.key==="Enter"||e.key===" "&&this.userTypedQuery==="")&&n&&a&&(e.preventDefault(),e.stopPropagation(),a.hasSubmenu?(a.submenuOpen=!0,this.addToSubmenuStack(a),setTimeout(()=>{const c=this.getSubmenuItems(a);c.length>0&&(c.forEach((d,p)=>d.active=p===0),c[0].focus())},0)):this.makeSelection(a))},this.handleDocumentPointerDown=e=>{e.composedPath().some(n=>n instanceof HTMLElement?n===this||n.closest('wa-dropdown, [part="submenu"]'):!1)||(this.open=!1)},this.handleGlobalMouseMove=e=>{const t=this.getCurrentSubmenuItem();if(!t?.submenuOpen||!t.submenuElement)return;const r=t.submenuElement.getBoundingClientRect(),n=this.localize.dir()==="rtl",i=n?r.right:r.left,s=n?Math.max(e.clientX,i):Math.min(e.clientX,i),o=Math.max(r.top,Math.min(e.clientY,r.bottom));t.submenuElement.style.setProperty("--safe-triangle-cursor-x",`${s}px`),t.submenuElement.style.setProperty("--safe-triangle-cursor-y",`${o}px`);const a=t.matches(":hover"),l=t.submenuElement?.matches(":hover")||!!e.composedPath().find(u=>u instanceof HTMLElement&&u.closest('[part="submenu"]')===t.submenuElement);!a&&!l&&setTimeout(()=>{!t.matches(":hover")&&!t.submenuElement?.matches(":hover")&&(t.submenuOpen=!1)},100)}}disconnectedCallback(){super.disconnectedCallback(),clearInterval(this.userTypedTimeout),this.closeAllSubmenus(),this.submenuCleanups.forEach(e=>e()),this.submenuCleanups.clear(),document.removeEventListener("mousemove",this.handleGlobalMouseMove)}firstUpdated(){this.syncAriaAttributes()}async updated(e){e.has("open")&&(this.customStates.set("open",this.open),this.open?await this.showMenu():(this.closeAllSubmenus(),await this.hideMenu())),e.has("size")&&this.syncItemSizes()}getItems(e=!1){const t=this.defaultSlot.assignedElements({flatten:!0}).filter(r=>r.localName==="wa-dropdown-item");return e?t:t.filter(r=>!r.disabled)}getSubmenuItems(e,t=!1){const r=e.shadowRoot?.querySelector('slot[name="submenu"]')||e.querySelector('slot[name="submenu"]');if(!r)return[];const n=r.assignedElements({flatten:!0}).filter(i=>i.localName==="wa-dropdown-item");return t?n:n.filter(i=>!i.disabled)}syncItemSizes(){this.defaultSlot.assignedElements({flatten:!0}).filter(t=>t.localName==="wa-dropdown-item").forEach(t=>t.size=this.size)}addToSubmenuStack(e){const t=this.openSubmenuStack.indexOf(e);t!==-1?this.openSubmenuStack=this.openSubmenuStack.slice(0,t+1):this.openSubmenuStack.push(e)}removeFromSubmenuStack(){return this.openSubmenuStack.pop()}getCurrentSubmenuItem(){return this.openSubmenuStack.length>0?this.openSubmenuStack[this.openSubmenuStack.length-1]:void 0}closeAllSubmenus(){this.getItems(!0).forEach(t=>{t.submenuOpen=!1}),this.openSubmenuStack=[]}closeSiblingSubmenus(e){const t=e.closest('wa-dropdown-item:not([slot="submenu"])');let r;t?r=this.getSubmenuItems(t,!0):r=this.getItems(!0),r.forEach(n=>{n!==e&&n.submenuOpen&&(n.submenuOpen=!1)}),this.openSubmenuStack.includes(e)||this.openSubmenuStack.push(e)}getTrigger(){return this.querySelector('[slot="trigger"]')}async showMenu(){if(!this.getTrigger())return;const t=new va;if(this.dispatchEvent(t),t.defaultPrevented){this.open=!1;return}qd.forEach(n=>n.open=!1),this.popup.active=!0,this.open=!0,qd.add(this),this.syncAriaAttributes(),document.addEventListener("keydown",this.handleDocumentKeyDown),document.addEventListener("pointerdown",this.handleDocumentPointerDown),document.addEventListener("mousemove",this.handleGlobalMouseMove),this.menu.classList.remove("hide"),await Tt(this.menu,"show");const r=this.getItems();r.length>0&&(r.forEach((n,i)=>n.active=i===0),r[0].focus()),this.dispatchEvent(new ga)}async hideMenu(){const e=new ba({source:this});if(this.dispatchEvent(e),e.defaultPrevented){this.open=!0;return}this.open=!1,qd.delete(this),this.syncAriaAttributes(),document.removeEventListener("keydown",this.handleDocumentKeyDown),document.removeEventListener("pointerdown",this.handleDocumentPointerDown),document.removeEventListener("mousemove",this.handleGlobalMouseMove),this.menu.classList.remove("show"),await Tt(this.menu,"hide"),this.popup.active=this.open,this.dispatchEvent(new ma)}handleMenuClick(e){const t=e.target.closest("wa-dropdown-item");if(!(!t||t.disabled)){if(t.hasSubmenu){t.submenuOpen||(this.closeSiblingSubmenus(t),this.addToSubmenuStack(t),t.submenuOpen=!0),e.stopPropagation();return}this.makeSelection(t)}}async handleMenuSlotChange(){const e=this.getItems(!0);await Promise.all(e.map(n=>n.updateComplete)),this.syncItemSizes();const t=e.some(n=>n.type==="checkbox"),r=e.some(n=>n.hasSubmenu);e.forEach((n,i)=>{n.active=i===0,n.checkboxAdjacent=t,n.submenuAdjacent=r})}handleTriggerClick(){this.open=!this.open}handleSubmenuOpening(e){const t=e.detail.item;this.closeSiblingSubmenus(t),this.addToSubmenuStack(t),this.setupSubmenuPosition(t),this.processSubmenuItems(t)}setupSubmenuPosition(e){if(!e.submenuElement)return;this.cleanupSubmenuPosition(e);const t=oE(e,e.submenuElement,()=>{this.positionSubmenu(e),this.updateSafeTriangleCoordinates(e)});this.submenuCleanups.set(e,t);const r=e.submenuElement.querySelector('slot[name="submenu"]');r&&(r.removeEventListener("slotchange",Ot.handleSubmenuSlotChange),r.addEventListener("slotchange",Ot.handleSubmenuSlotChange),Ot.handleSubmenuSlotChange({target:r}))}static handleSubmenuSlotChange(e){const t=e.target;if(!t)return;const r=t.assignedElements().filter(s=>s.localName==="wa-dropdown-item");if(r.length===0)return;const n=r.some(s=>s.hasSubmenu),i=r.some(s=>s.type==="checkbox");r.forEach(s=>{s.submenuAdjacent=n,s.checkboxAdjacent=i})}processSubmenuItems(e){if(!e.submenuElement)return;const t=this.getSubmenuItems(e,!0),r=t.some(n=>n.hasSubmenu);t.forEach(n=>{n.submenuAdjacent=r})}cleanupSubmenuPosition(e){const t=this.submenuCleanups.get(e);t&&(t(),this.submenuCleanups.delete(e))}positionSubmenu(e){if(!e.submenuElement)return;const r=this.localize.dir()==="rtl"?"left-start":"right-start";uE(e,e.submenuElement,{placement:r,middleware:[aE({mainAxis:0,crossAxis:-5}),cE({fallbackStrategy:"bestFit"}),lE({padding:8})]}).then(({x:n,y:i,placement:s})=>{e.submenuElement.setAttribute("data-placement",s),Object.assign(e.submenuElement.style,{left:`${n}px`,top:`${i}px`})})}updateSafeTriangleCoordinates(e){if(!e.submenuElement||!e.submenuOpen)return;if(document.activeElement?.matches(":focus-visible")){e.submenuElement.style.setProperty("--safe-triangle-visible","none");return}e.submenuElement.style.setProperty("--safe-triangle-visible","block");const r=e.submenuElement.getBoundingClientRect(),n=this.localize.dir()==="rtl";e.submenuElement.style.setProperty("--safe-triangle-submenu-start-x",`${n?r.right:r.left}px`),e.submenuElement.style.setProperty("--safe-triangle-submenu-start-y",`${r.top}px`),e.submenuElement.style.setProperty("--safe-triangle-submenu-end-x",`${n?r.right:r.left}px`),e.submenuElement.style.setProperty("--safe-triangle-submenu-end-y",`${r.bottom}px`)}makeSelection(e){const t=this.getTrigger();if(e.disabled)return;e.type==="checkbox"&&(e.checked=!e.checked);const r=new dD({item:e});this.dispatchEvent(r),r.defaultPrevented||(this.open=!1,t?.focus())}async syncAriaAttributes(){const e=this.getTrigger();let t;e&&(e.localName==="wa-button"?(await customElements.whenDefined("wa-button"),await e.updateComplete,t=e.shadowRoot.querySelector('[part="base"]')):t=e,t.hasAttribute("id")||t.setAttribute("id",Fp("wa-dropdown-trigger-")),t.setAttribute("aria-haspopup","menu"),t.setAttribute("aria-expanded",this.open?"true":"false"),this.menu.setAttribute("aria-expanded","false"))}render(){let e=this.hasUpdated?this.popup.active:this.open;return W`
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
    `}};Ot.css=[LE,hD];D([Ue("slot:not([name])")],Ot.prototype,"defaultSlot",2);D([Ue("#menu")],Ot.prototype,"menu",2);D([Ue("wa-popup")],Ot.prototype,"popup",2);D([V({type:Boolean,reflect:!0})],Ot.prototype,"open",2);D([V({reflect:!0})],Ot.prototype,"size",2);D([V({reflect:!0})],Ot.prototype,"placement",2);D([V({type:Number})],Ot.prototype,"distance",2);D([V({type:Number})],Ot.prototype,"skidding",2);Ot=D([Lr("wa-dropdown")],Ot);var nu=class{constructor(e,...t){this.slotNames=[],this.handleSlotChange=r=>{const n=r.target;(this.slotNames.includes("[default]")&&!n.name||n.name&&this.slotNames.includes(n.name))&&this.host.requestUpdate()},(this.host=e).addController(this),this.slotNames=t}hasDefaultSlot(){return[...this.host.childNodes].some(e=>{if(e.nodeType===Node.TEXT_NODE&&e.textContent.trim()!=="")return!0;if(e.nodeType===Node.ELEMENT_NODE){const t=e;if(t.tagName.toLowerCase()==="wa-visually-hidden")return!1;if(!t.hasAttribute("slot"))return!0}return!1})}hasNamedSlot(e){return this.host.querySelector(`:scope > [slot="${e}"]`)!==null}test(e){return e==="[default]"?this.hasDefaultSlot():this.hasNamedSlot(e)}hostConnected(){this.host.shadowRoot.addEventListener("slotchange",this.handleSlotChange)}hostDisconnected(){this.host.shadowRoot.removeEventListener("slotchange",this.handleSlotChange)}};var fD=`:host {
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
`,wt=class extends qt{constructor(){super(...arguments),this.hasSlotController=new nu(this,"[default]","start","end"),this.active=!1,this.variant="default",this.size="medium",this.checkboxAdjacent=!1,this.submenuAdjacent=!1,this.type="normal",this.checked=!1,this.disabled=!1,this.submenuOpen=!1,this.hasSubmenu=!1,this.handleSlotChange=()=>{this.hasSubmenu=this.hasSlotController.test("submenu"),this.updateHasSubmenuState(),this.hasSubmenu?(this.setAttribute("aria-haspopup","menu"),this.setAttribute("aria-expanded",this.submenuOpen?"true":"false")):(this.removeAttribute("aria-haspopup"),this.removeAttribute("aria-expanded"))}}connectedCallback(){super.connectedCallback(),this.addEventListener("mouseenter",this.handleMouseEnter.bind(this)),this.shadowRoot.addEventListener("slotchange",this.handleSlotChange)}disconnectedCallback(){super.disconnectedCallback(),this.closeSubmenu(),this.removeEventListener("mouseenter",this.handleMouseEnter),this.shadowRoot.removeEventListener("slotchange",this.handleSlotChange)}firstUpdated(){this.setAttribute("tabindex","-1"),this.hasSubmenu=this.hasSlotController.test("submenu"),this.updateHasSubmenuState()}updated(e){e.has("active")&&(this.setAttribute("tabindex",this.active?"0":"-1"),this.customStates.set("active",this.active)),e.has("checked")&&(this.setAttribute("aria-checked",this.checked?"true":"false"),this.customStates.set("checked",this.checked)),e.has("disabled")&&(this.setAttribute("aria-disabled",this.disabled?"true":"false"),this.customStates.set("disabled",this.disabled)),e.has("type")&&(this.type==="checkbox"?this.setAttribute("role","menuitemcheckbox"):this.setAttribute("role","menuitem")),e.has("submenuOpen")&&(this.customStates.set("submenu-open",this.submenuOpen),this.submenuOpen?this.openSubmenu():this.closeSubmenu())}updateHasSubmenuState(){this.customStates.set("has-submenu",this.hasSubmenu)}async openSubmenu(){!this.hasSubmenu||!this.submenuElement||(this.notifyParentOfOpening(),this.submenuElement.showPopover(),this.submenuElement.hidden=!1,this.submenuElement.setAttribute("data-visible",""),this.submenuOpen=!0,this.setAttribute("aria-expanded","true"),await Tt(this.submenuElement,"show"),setTimeout(()=>{const e=this.getSubmenuItems();e.length>0&&(e.forEach((t,r)=>t.active=r===0),e[0].focus())},0))}notifyParentOfOpening(){const e=new CustomEvent("submenu-opening",{bubbles:!0,composed:!0,detail:{item:this}});this.dispatchEvent(e);const t=this.parentElement;t&&[...t.children].filter(n=>n!==this&&n.localName==="wa-dropdown-item"&&n.getAttribute("slot")===this.getAttribute("slot")&&n.submenuOpen).forEach(n=>{n.submenuOpen=!1})}async closeSubmenu(){!this.hasSubmenu||!this.submenuElement||(this.submenuOpen=!1,this.setAttribute("aria-expanded","false"),this.submenuElement.hidden||(await Tt(this.submenuElement,"hide"),this.submenuElement.hidden=!0,this.submenuElement.removeAttribute("data-visible"),this.submenuElement.hidePopover()))}getSubmenuItems(){return[...this.children].filter(e=>e.localName==="wa-dropdown-item"&&e.getAttribute("slot")==="submenu"&&!e.hasAttribute("disabled"))}handleMouseEnter(){this.hasSubmenu&&!this.disabled&&(this.notifyParentOfOpening(),this.submenuOpen=!0)}render(){return W`
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
    `}};wt.css=fD;D([Ue("#submenu")],wt.prototype,"submenuElement",2);D([V({type:Boolean})],wt.prototype,"active",2);D([V({reflect:!0})],wt.prototype,"variant",2);D([V({reflect:!0})],wt.prototype,"size",2);D([V({attribute:"checkbox-adjacent",type:Boolean,reflect:!0})],wt.prototype,"checkboxAdjacent",2);D([V({attribute:"submenu-adjacent",type:Boolean,reflect:!0})],wt.prototype,"submenuAdjacent",2);D([V()],wt.prototype,"value",2);D([V({reflect:!0})],wt.prototype,"type",2);D([V({type:Boolean})],wt.prototype,"checked",2);D([V({type:Boolean,reflect:!0})],wt.prototype,"disabled",2);D([V({type:Boolean,reflect:!0})],wt.prototype,"submenuOpen",2);D([er()],wt.prototype,"hasSubmenu",2);wt=D([Lr("wa-dropdown-item")],wt);var pD=class extends Ot{static get styles(){return[Ot.styles,te`
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
      `]}},mD=class extends wt{static get styles(){return[wt.styles,te`
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
      `]}};customElements.get("craft-dropdown")||customElements.define("craft-dropdown",pD),customElements.get("craft-dropdown-item")||customElements.define("craft-dropdown-item",mD);function gD({el:e,uid:t}){e.setAttribute("id",`panel-${t}`),e.setAttribute("role","tabpanel"),e.setAttribute("aria-labelledby",`button-${t}`),e.hasAttribute("tabindex")||e.setAttribute("tabindex","0")}function bD(e){e.setAttribute("selected","true")}function yv(e){e.removeAttribute("selected")}function vD({el:e,uid:t,clickHandler:r,keydownHandler:n,keyupHandler:i}){e.setAttribute("id",`button-${t}`),e.setAttribute("role","tab"),e.setAttribute("aria-controls",`panel-${t}`),e.addEventListener("click",r),e.addEventListener("keyup",i),e.addEventListener("keydown",n)}function yD({el:e,clickHandler:t,keydownHandler:r,keyupHandler:n}){e.removeAttribute("id"),e.removeAttribute("role"),e.removeAttribute("aria-controls"),e.removeEventListener("click",t),e.removeEventListener("keyup",n),e.removeEventListener("keydown",r)}function _D(e,t=!1){t&&e.focus(),e.setAttribute("selected","true"),e.setAttribute("aria-selected","true"),e.setAttribute("tabindex","0")}function _v(e){e.removeAttribute("selected"),e.setAttribute("aria-selected","false"),e.setAttribute("tabindex","-1")}function wD(e){const t=e;switch(t.key){case"ArrowDown":case"ArrowRight":case"ArrowUp":case"ArrowLeft":case"Home":case"End":t.preventDefault()}}class ED extends Ne{static get properties(){return{selectedIndex:{type:Number,attribute:"selected-index",reflect:!0}}}static get styles(){return[te`
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
    `}constructor(){super(),this.selectedIndex=0}firstUpdated(t){super.firstUpdated(t),this.__setupSlots(),this.tabs[0]?.disabled&&(this.selectedIndex=this.tabs.findIndex(r=>!r.disabled))}get tabs(){return Array.from(this.children).filter(t=>t.slot==="tab")}get panels(){return Array.from(this.children).filter(t=>t.slot==="panel")}static enabledWarnings=super.enabledWarnings?.filter(t=>t!=="change-in-update")||[];__setupSlots(){if(this.shadowRoot){const t=this.shadowRoot.querySelector("slot[name=tab]"),r=()=>{this.__cleanStore(),this.__setupStore(),this.__updateSelected(!1)};t&&t.addEventListener("slotchange",r)}}__setupStore(){this.__store=[],this.tabs.length!==this.panels.length&&console.warn(`The amount of tabs (${this.tabs.length}) doesn't match the amount of panels (${this.panels.length}).`),this.tabs.forEach((t,r)=>{const n=_a(),i=this.panels[r],s={uid:n,el:t,button:t,panel:i,clickHandler:this.__createButtonClickHandler(r),keydownHandler:wD.bind(this),keyupHandler:this.__handleButtonKeyup.bind(this)};gD({...s,el:s.panel}),vD(s),yv(s.panel),_v(s.button),this.__store&&this.__store.push(s)})}__cleanStore(){this.__store&&(this.__store.forEach(t=>{yD(t)}),this.__store=[])}__getNextNotDisabledTab(t,r,n){let i=[];const s=t.filter((a,l)=>!a.disabled&&l>this.selectedIndex),o=t.filter((a,l)=>!a.disabled&&l<this.selectedIndex);return n==="right"?i=[...s,...o]:i=[...o.reverse(),...s.reverse()],i[0]}__getNextAvailableIndex(t,r){const n=this.tabs[this.selectedIndex];if(this.tabs.every(i=>!i.disabled))return t;if(r==="ArrowRight"||r==="ArrowDown"){const i=this.__getNextNotDisabledTab(this.tabs,n,"right");return this.tabs.findIndex(s=>i===s)}if(r==="ArrowLeft"||r==="ArrowUp"){const i=this.__getNextNotDisabledTab(this.tabs,n,"left");return this.tabs.findIndex(s=>i===s)}if(r==="Home")return this.tabs.findIndex(i=>!i.disabled);if(r==="End"){const i=this.tabs.map((s,o)=>({disabled:s.disabled,index:o})).filter(s=>!s.disabled);return i[i.length-1].index}return-1}__createButtonClickHandler(t){return()=>{this._setSelectedIndexWithFocus(t)}}__handleButtonKeyup(t){const r=t;if(typeof this.selectedIndex=="number")switch(r.key){case"ArrowDown":case"ArrowRight":this.selectedIndex+1>=this._pairCount?this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(0,r.key)):this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this.selectedIndex+1,r.key));break;case"ArrowUp":case"ArrowLeft":this.selectedIndex<=0?this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this._pairCount-1,r.key)):this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this.selectedIndex-1,r.key));break;case"Home":this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(0,r.key));break;case"End":this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this._pairCount-1,r.key));break}}get selectedIndex(){return this.__selectedIndex||0}set selectedIndex(t){if(t===this.__selectedIndex)return;const r=this.__selectedIndex;this.__selectedIndex=t,this.__updateSelected(!1),this.dispatchEvent(new Event("selected-changed")),this.requestUpdate("selectedIndex",r)}_setSelectedIndexWithFocus(t){if(t===-1)return;const r=this.__selectedIndex;this.__selectedIndex=t,this.__updateSelected(!0),this.dispatchEvent(new Event("selected-changed")),this.requestUpdate("selectedIndex",r)}get _pairCount(){return this.__store&&this.__store.length||0}__updateSelected(t=!1){if(!(this.__store&&typeof this.selectedIndex=="number"&&this.__store[this.selectedIndex]))return;const r=this.tabs.find(o=>o.hasAttribute("selected")),n=this.panels.find(o=>o.hasAttribute("selected"));r&&_v(r),n&&yv(n);const{button:i,panel:s}=this.__store[this.selectedIndex];i&&_D(i,t),s&&bD(s)}}var xD=te`
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
`,SD=class extends ED{static get styles(){return[...super.styles,xD]}};customElements.get("craft-tabs")||customElements.define("craft-tabs",SD);var CD=te`
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
`,Hd=class extends Ne{constructor(...t){super(...t),this.label=""}render(){let t=!!this.label||!!this.querySelector('[slot="header"]')||!!this.querySelector('[slot="label"]')||!!this.querySelector('[slot="actions"]'),r=!!this.querySelector('[slot="footer"]');return W`
      <div class="card">
        <div>
          ${t?W`<div class="card__header">
                <slot name="header">
                  <slot name="label" class="card__label" part="label"
                    >${this.label}</slot
                  >
                  <slot name="actions"></slot>
                </slot>
              </div>`:pt}

          <div class="card__body">
            <slot></slot>
          </div>

          ${r?W`<div class="card__footer"><slot name="footer"></slot></div>`:pt}
        </div>
      </div>
    `}};Hd.styles=[CD],se([V()],Hd.prototype,"label",void 0),customElements.get("craft-card")||customElements.define("craft-card",Hd);var AD=te`
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
`,wv=class extends Ne{render(){return W`<slot></slot> `}};wv.styles=[AD],customElements.get("craft-tab")||customElements.define("craft-tab",wv);class $E extends mE(Ne){static get properties(){return{checked:{type:Boolean,reflect:!0}}}static get styles(){return[te`
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
    `}constructor(){super(),this.value="",this.checked=!1,this.__initialized=!1,this._toggleChecked=this._toggleChecked.bind(this),this.__handleKeydown=this._handleKeydown.bind(this),this.__handleKeyup=this._handleKeyup.bind(this)}connectedCallback(){super.connectedCallback(),this.setAttribute("role","switch"),this.setAttribute("aria-checked",`${this.checked}`),this.addEventListener("click",this._toggleChecked),this.addEventListener("keydown",this.__handleKeydown),this.addEventListener("keyup",this.__handleKeyup)}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener("click",this._toggleChecked),this.removeEventListener("keydown",this.__handleKeydown),this.removeEventListener("keyup",this.__handleKeyup)}_toggleChecked(){this.disabled||(this.focus(),this.checked=!this.checked)}__checkedStateChange(){this.dispatchEvent(new Event("checked-changed",{bubbles:!0})),this.setAttribute("aria-checked",`${this.checked}`)}_handleKeydown(t){t.key===" "&&t.preventDefault()}_handleKeyup(t){[" ","Enter"].includes(t.key)&&this._toggleChecked()}updated(t){super.updated(t),t.has("disabled")&&this.setAttribute("aria-disabled",`${this.disabled}`)}requestUpdate(t,r,n){super.requestUpdate(t,r,n),this.__initialized&&this.isConnected&&t==="checked"&&this.checked!==r&&!this.disabled&&this.__checkedStateChange()}firstUpdated(t){super.firstUpdated(t),this.__initialized=!0}}class TD extends Ea(tu(Sa)){static get styles(){return[...super.styles,te`
        :host([hidden]) {
          display: none;
        }

        :host([disabled]) {
          color: #adadad;
        }
      `]}static get scopedElements(){return{...super.scopedElements,"lion-switch-button":$E}}get _inputNode(){return Array.from(this.children).find(t=>t.slot==="input")}get slots(){return{...super.slots,input:()=>{const t=this.createScopedElement("lion-switch-button");return t.setAttribute("data-tag-name","lion-switch-button"),t}}}render(){return W`
      <div class="form-field__group-one">${this._groupOneTemplate()}</div>
      <div class="form-field__group-two">${this._groupTwoTemplate()}</div>
    `}_groupOneTemplate(){return W`${this._labelTemplate()} ${this._helpTextTemplate()} ${this._feedbackTemplate()}`}_groupTwoTemplate(){return W`${this._inputGroupTemplate()}`}constructor(){super(),this.checked=!1,this.__handleButtonSwitchCheckedChanged=this.__handleButtonSwitchCheckedChanged.bind(this)}connectedCallback(){super.connectedCallback(),this.addEventListener("checked-changed",this.__handleButtonSwitchCheckedChanged),this._labelNode&&this._labelNode.addEventListener("click",this._toggleChecked),this._syncButtonSwitch()}disconnectedCallback(){super.disconnectedCallback(),this._inputNode&&this.removeEventListener("checked-changed",this.__handleButtonSwitchCheckedChanged),this._labelNode&&this._labelNode.removeEventListener("click",this._toggleChecked)}updated(t){super.updated(t),t.has("disabled")&&this._syncButtonSwitch()}_toggleChecked(t){t.preventDefault(),super._toggleChecked(t)}_isEmpty(){return!1}__handleButtonSwitchCheckedChanged(t){t.stopPropagation(),this._isHandlingUserInput=!0,this.checked=this._inputNode.checked,this._isHandlingUserInput=!1}_syncButtonSwitch(){this._inputNode.disabled=this.disabled}_onLabelClick(){this.disabled||this._inputNode.focus()}}var ME=class extends $E{static get styles(){return[...super.styles,te`
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
      `]}};customElements.get("craft-switch-button")||customElements.define("craft-switch-button",ME);var kD=te`
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
`,OD=class extends TD{static get styles(){return[...super.styles,Xc,kD]}get slots(){return{...super.slots,input:()=>{let t=this.createScopedElement("craft-switch-button");return t.setAttribute("data-tag-name","craft-switch-button"),t}}}static get scopedElements(){return{...super.scopedElements,"craft-switch-button":ME}}};customElements.get("craft-switch")||customElements.define("craft-switch",OD);var ND=te`
  .breadcrumbs {
    display: flex;
    align-items: center;
  }
`,Dn=class extends Ne{constructor(...t){super(...t),this.label="",this.items=[],this.visibleItems=0,this.firstRender=!0}getSeparator(){let t=this.separatorSlot.assignedElements({flatten:!0})[0].cloneNode(!0);return[t,...t.querySelectorAll("[id]")].forEach(r=>r.removeAttribute("id")),t.setAttribute("data-default",""),t.slot="separator",t}calculateBreadcrumbItemsWidth(){this.items=this.breadcrumbsElements.map((t,r)=>{let n=t.offsetWidth;return t.hasAttribute("hidden")&&(t.removeAttribute("hidden"),n=t.offsetWidth,t.setAttribute("hidden","")),{label:t.innerText,href:t.href,value:r.toString(),offsetWidth:n,isVisible:!0}})}async handleSlotChange(){let t=[...this.defaultSlot.assignedElements({flatten:!0})].filter(r=>r.tagName.toLowerCase()==="craft-breadcrumb-item");if(t.forEach((r,n)=>{let i=r.querySelector('[slot="separator"]');i===null?r.append(this.getSeparator()):i.hasAttribute("data-default")&&i.replaceWith(this.getSeparator()),n===t.length-1?r.setAttribute("aria-current","page"):r.removeAttribute("aria-current")}),this.breadcrumbsElements.length===0){this.items=[],this.visibleItems=0;return}await Promise.all(this.breadcrumbsElements.map(r=>r.updateComplete)),this.calculateBreadcrumbItemsWidth(),this.visibleItems=0,this.adjustOverflow()}connectedCallback(){super.connectedCallback(),this.hasAttribute("role")||this.setAttribute("role","navigation"),this.resizeObserver=new ResizeObserver(()=>{if(this.firstRender){this.firstRender=!1;return}this.adjustOverflow()}),this.resizeObserver.observe(this)}adjustOverflow(){let t=this.getBoundingClientRect().width;console.log({availableSpace:t})}disconnectedCallback(){this.resizeObserver?.unobserve(this),super.disconnectedCallback()}render(){return W`
      <nav class="breadcrumbs" aria-label="${this.label}">
        <slot @slotchange="${this.handleSlotChange}"></slot>
      </nav>

      <span hidden aria-hidden="true">
        <slot name="separator"><span class="separator">/</span></slot>
      </span>
    `}};Dn.styles=[ND],se([Ue("slot")],Dn.prototype,"defaultSlot",void 0),se([Ue('slot[name="separator"]')],Dn.prototype,"separatorSlot",void 0),se([hl({selector:"craft-breadcrumb-item"})],Dn.prototype,"breadcrumbsElements",void 0),se([V()],Dn.prototype,"label",void 0),se([er()],Dn.prototype,"items",void 0),se([er()],Dn.prototype,"visibleItems",void 0),customElements.get("craft-breadcrumbs")||customElements.define("craft-breadcrumbs",Dn);var PD=`:host {
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
`,Fr=class extends qt{constructor(){super(...arguments),this.renderType="button",this.rel="noreferrer noopener"}setRenderType(){const e=this.defaultSlot.assignedElements({flatten:!0}).filter(t=>t.tagName.toLowerCase()==="wa-dropdown").length>0;if(this.href){this.renderType="link";return}if(e){this.renderType="dropdown";return}this.renderType="button"}hrefChanged(){this.setRenderType()}handleSlotChange(){this.setRenderType()}render(){return W`
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
    `}};Fr.css=PD;D([Ue("slot:not([name])")],Fr.prototype,"defaultSlot",2);D([er()],Fr.prototype,"renderType",2);D([V()],Fr.prototype,"href",2);D([V()],Fr.prototype,"target",2);D([V()],Fr.prototype,"rel",2);D([ar("href",{waitUntilFirstUpdate:!0})],Fr.prototype,"hrefChanged",1);Fr=D([Lr("wa-breadcrumb-item")],Fr);var FD=class extends Fr{static get styles(){return[Fr.styles,te`
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
      `]}};customElements.get("craft-breadcrumb-item")||customElements.define("craft-breadcrumb-item",FD);var ID=`:host {
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
`,jd=new Set,ct=class extends qt{constructor(){super(...arguments),this.anchor=null,this.placement="top",this.open=!1,this.distance=8,this.skidding=0,this.for=null,this.withoutArrow=!1,this.eventController=new AbortController,this.handleAnchorClick=()=>{this.open=!this.open},this.handleBodyClick=e=>{e.target.closest('[data-popover="close"]')&&(e.stopPropagation(),this.open=!1)},this.handleDocumentKeyDown=e=>{e.key==="Escape"&&(e.preventDefault(),this.open=!1,this.anchor&&typeof this.anchor.focus=="function"&&this.anchor.focus())},this.handleDocumentClick=e=>{const t=e.target;this.anchor&&e.composedPath().includes(this.anchor)||t.closest("wa-popover")!==this&&(this.open=!1)}}connectedCallback(){super.connectedCallback(),this.id||(this.id=Fp("wa-popover-"))}disconnectedCallback(){super.disconnectedCallback(),document.removeEventListener("keydown",this.handleDocumentKeyDown),this.eventController.abort()}firstUpdated(){this.open&&(this.dialog.show(),this.popup.active=!0,this.popup.reposition())}updated(e){e.has("open")&&this.customStates.set("open",this.open)}async handleOpenChange(){if(this.open){const e=new va;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}jd.forEach(t=>t.open=!1),document.addEventListener("keydown",this.handleDocumentKeyDown,{signal:this.eventController.signal}),document.addEventListener("click",this.handleDocumentClick,{signal:this.eventController.signal}),this.dialog.show(),this.popup.active=!0,jd.add(this),requestAnimationFrame(()=>{const t=this.querySelector("[autofocus]");t&&typeof t.focus=="function"?t.focus():this.dialog.focus()}),await Tt(this.popup.popup,"show-with-scale"),this.popup.reposition(),this.dispatchEvent(new ga)}else{const e=new ba;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!0;return}document.removeEventListener("keydown",this.handleDocumentKeyDown),document.removeEventListener("click",this.handleDocumentClick),jd.delete(this),await Tt(this.popup.popup,"hide-with-scale"),this.popup.active=!1,this.dialog.close(),this.dispatchEvent(new ma)}}handleForChange(){const e=this.getRootNode();if(!e)return;const t=this.for?e.getElementById(this.for):null,r=this.anchor;if(t===r)return;const{signal:n}=this.eventController;t&&t.addEventListener("click",this.handleAnchorClick,{signal:n}),r&&r.removeEventListener("click",this.handleAnchorClick),this.anchor=t,this.for&&!t&&console.warn(`A popover was assigned to an element with an ID of "${this.for}" but the element could not be found.`,this)}async handleOptionsChange(){this.hasUpdated&&(await this.updateComplete,this.popup.reposition())}async show(){if(!this.open)return this.open=!0,ic(this,"wa-after-show")}async hide(){if(this.open)return this.open=!1,ic(this,"wa-after-hide")}render(){return W`
      <dialog part="dialog" class="dialog">
        <wa-popup
          part="popup"
          exportparts="
            popup:popup__popup,
            arrow:popup__arrow
          "
          class=${tr({popover:!0,"popover-open":this.open})}
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
    `}};ct.css=ID;ct.dependencies={"wa-popup":ke};D([Ue("dialog")],ct.prototype,"dialog",2);D([Ue(".body")],ct.prototype,"body",2);D([Ue("wa-popup")],ct.prototype,"popup",2);D([er()],ct.prototype,"anchor",2);D([V()],ct.prototype,"placement",2);D([V({type:Boolean,reflect:!0})],ct.prototype,"open",2);D([V({type:Number})],ct.prototype,"distance",2);D([V({type:Number})],ct.prototype,"skidding",2);D([V()],ct.prototype,"for",2);D([V({attribute:"without-arrow",type:Boolean,reflect:!0})],ct.prototype,"withoutArrow",2);D([ar("open",{waitUntilFirstUpdate:!0})],ct.prototype,"handleOpenChange",1);D([ar("for")],ct.prototype,"handleForChange",1);D([ar(["distance","placement","skidding"])],ct.prototype,"handleOptionsChange",1);ct=D([Lr("wa-popover")],ct);var LD=class extends ct{static get styles(){return[ct.styles,te`
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
      `]}};customElements.get("craft-popover")||customElements.define("craft-popover",LD);var tf=new Set;function RD(){const e=document.documentElement.clientWidth;return Math.abs(window.innerWidth-e)}function $D(){const e=Number(getComputedStyle(document.body).paddingRight.replace(/px/,""));return isNaN(e)||!e?0:e}function cc(e){if(tf.add(e),!document.documentElement.classList.contains("wa-scroll-lock")){const t=RD()+$D();let r=getComputedStyle(document.documentElement).scrollbarGutter;(!r||r==="auto")&&(r="stable"),t<2&&(r=""),document.documentElement.style.setProperty("--wa-scroll-lock-gutter",r),document.documentElement.classList.add("wa-scroll-lock"),document.documentElement.style.setProperty("--wa-scroll-lock-size",`${t}px`)}}function uc(e){tf.delete(e),tf.size===0&&(document.documentElement.classList.remove("wa-scroll-lock"),document.documentElement.style.removeProperty("--wa-scroll-lock-size"))}function DE(e){return e.split(" ").map(t=>t.trim()).filter(t=>t!=="")}var MD=`:host {
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
`,br=class extends qt{constructor(){super(...arguments),this.localize=new Is(this),this.hasSlotController=new nu(this,"footer","header-actions","label"),this.open=!1,this.label="",this.placement="end",this.withoutHeader=!1,this.lightDismiss=!0,this.handleDocumentKeyDown=e=>{e.key==="Escape"&&this.open&&(e.preventDefault(),e.stopPropagation(),this.requestClose(this.drawer))}}firstUpdated(){this.open&&(this.addOpenListeners(),this.drawer.showModal(),cc(this))}disconnectedCallback(){super.disconnectedCallback(),uc(this),this.removeOpenListeners()}async requestClose(e){const t=new ba({source:e});if(this.dispatchEvent(t),t.defaultPrevented){this.open=!0,Tt(this.drawer,"pulse");return}this.removeOpenListeners(),await Tt(this.drawer,"hide"),this.open=!1,this.drawer.close(),uc(this);const r=this.originalTrigger;typeof r?.focus=="function"&&setTimeout(()=>r.focus()),this.dispatchEvent(new ma)}addOpenListeners(){document.addEventListener("keydown",this.handleDocumentKeyDown)}removeOpenListeners(){document.removeEventListener("keydown",this.handleDocumentKeyDown)}handleDialogCancel(e){e.preventDefault(),!this.drawer.classList.contains("hide")&&e.target===this.drawer&&this.requestClose(this.drawer)}handleDialogClick(e){const r=e.target.closest('[data-drawer="close"]');r&&(e.stopPropagation(),this.requestClose(r))}async handleDialogPointerDown(e){e.target===this.drawer&&(this.lightDismiss?this.requestClose(this.drawer):await Tt(this.drawer,"pulse"))}handleOpenChange(){this.open&&!this.drawer.open?this.show():this.drawer.open&&(this.open=!0,this.requestClose(this.drawer))}async show(){const e=new va;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}this.addOpenListeners(),this.originalTrigger=document.activeElement,this.open=!0,this.drawer.showModal(),cc(this),requestAnimationFrame(()=>{const t=this.querySelector("[autofocus]");t&&typeof t.focus=="function"?t.focus():this.drawer.focus()}),await Tt(this.drawer,"show"),this.dispatchEvent(new ga)}render(){const e=!this.withoutHeader,t=this.hasSlotController.test("footer");return W`
      <dialog
        part="dialog"
        class=${tr({drawer:!0,open:this.open,top:this.placement==="top",end:this.placement==="end",bottom:this.placement==="bottom",start:this.placement==="start"})}
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
    `}};br.css=MD;D([Ue(".drawer")],br.prototype,"drawer",2);D([V({type:Boolean,reflect:!0})],br.prototype,"open",2);D([V({reflect:!0})],br.prototype,"label",2);D([V({reflect:!0})],br.prototype,"placement",2);D([V({attribute:"without-header",type:Boolean,reflect:!0})],br.prototype,"withoutHeader",2);D([V({attribute:"light-dismiss",type:Boolean})],br.prototype,"lightDismiss",2);D([ar("open",{waitUntilFirstUpdate:!0})],br.prototype,"handleOpenChange",1);br=D([Lr("wa-drawer")],br);document.addEventListener("click",e=>{const t=e.target.closest("[data-drawer]");if(t instanceof Element){const[r,n]=DE(t.getAttribute("data-drawer")||"");if(r==="open"&&n?.length){const s=t.getRootNode().getElementById(n);s?.localName==="wa-drawer"?s.open=!0:console.warn(`A drawer with an ID of "${n}" could not be found in this document.`)}}});document.body.addEventListener("pointerdown",()=>{});var DD=()=>({checkValidity(e){const t=e.input,r={message:"",isValid:!0,invalidKeys:[]};if(!t)return r;let n=!0;if("checkValidity"in t&&(n=t.checkValidity()),n)return r;if(r.isValid=!1,"validationMessage"in t&&(r.message=t.validationMessage),!("validity"in t))return r.invalidKeys.push("customError"),r;for(const i in t.validity){if(i==="valid")continue;const s=i;t.validity[s]&&r.invalidKeys.push(s)}return r}});var VE=class extends Event{constructor(){super("wa-invalid",{bubbles:!0,cancelable:!1,composed:!0})}},VD=()=>({observedAttributes:["custom-error"],checkValidity(e){const t={message:"",isValid:!0,invalidKeys:[]};return e.customError&&(t.message=e.customError,t.isValid=!1,t.invalidKeys=["customError"]),t}}),Tn=class extends qt{constructor(){super(),this.name=null,this.disabled=!1,this.required=!1,this.assumeInteractionOn=["input"],this.validators=[],this.valueHasChanged=!1,this.hasInteracted=!1,this.customError=null,this.emittedEvents=[],this.emitInvalid=e=>{e.target===this&&(this.hasInteracted=!0,this.dispatchEvent(new VE))},this.handleInteraction=e=>{const t=this.emittedEvents;t.includes(e.type)||t.push(e.type),t.length===this.assumeInteractionOn?.length&&(this.hasInteracted=!0)},this.addEventListener("invalid",this.emitInvalid)}static get validators(){return[VD()]}static get observedAttributes(){const e=new Set(super.observedAttributes||[]);for(const t of this.validators)if(t.observedAttributes)for(const r of t.observedAttributes)e.add(r);return[...e]}connectedCallback(){super.connectedCallback(),this.updateValidity(),this.assumeInteractionOn.forEach(e=>{this.addEventListener(e,this.handleInteraction)})}firstUpdated(...e){super.firstUpdated(...e),this.updateValidity()}willUpdate(e){if(e.has("customError")&&(this.customError||(this.customError=null),this.setCustomValidity(this.customError||"")),e.has("value")||e.has("disabled")){const t=this.value;if(Array.isArray(t)){if(this.name){const r=new FormData;for(const n of t)r.append(this.name,n);this.setValue(r,r)}}else this.setValue(t,t)}e.has("disabled")&&(this.customStates.set("disabled",this.disabled),(this.hasAttribute("disabled")||!this.matches(":disabled"))&&this.toggleAttribute("disabled",this.disabled)),this.updateValidity(),super.willUpdate(e)}get labels(){return this.internals.labels}getForm(){return this.internals.form}get validity(){return this.internals.validity}get willValidate(){return this.internals.willValidate}get validationMessage(){return this.internals.validationMessage}checkValidity(){return this.updateValidity(),this.internals.checkValidity()}reportValidity(){return this.updateValidity(),this.hasInteracted=!0,this.internals.reportValidity()}get validationTarget(){return this.input||void 0}setValidity(...e){const t=e[0],r=e[1];let n=e[2];n||(n=this.validationTarget),this.internals.setValidity(t,r,n||void 0),this.requestUpdate("validity"),this.setCustomStates()}setCustomStates(){const e=!!this.required,t=this.internals.validity.valid,r=this.hasInteracted;this.customStates.set("required",e),this.customStates.set("optional",!e),this.customStates.set("invalid",!t),this.customStates.set("valid",t),this.customStates.set("user-invalid",!t&&r),this.customStates.set("user-valid",t&&r)}setCustomValidity(e){if(!e){this.customError=null,this.setValidity({});return}this.customError=e,this.setValidity({customError:!0},e,this.validationTarget)}formResetCallback(){this.resetValidity(),this.hasInteracted=!1,this.valueHasChanged=!1,this.emittedEvents=[],this.updateValidity()}formDisabledCallback(e){this.disabled=e,this.updateValidity()}formStateRestoreCallback(e,t){this.value=e,t==="restore"&&this.resetValidity(),this.updateValidity()}setValue(...e){const[t,r]=e;this.internals.setFormValue(t,r)}get allValidators(){const e=this.constructor.validators||[],t=this.validators||[];return[...e,...t]}resetValidity(){this.setCustomValidity(""),this.setValidity({})}updateValidity(){if(this.disabled||this.hasAttribute("disabled")||!this.willValidate){this.resetValidity();return}const e=this.allValidators;if(!e?.length)return;const t={customError:!!this.customError},r=this.validationTarget||this.input||void 0;let n="";for(const i of e){const{isValid:s,message:o,invalidKeys:a}=i.checkValidity(this);s||(n||(n=o),a?.length>=0&&a.forEach(l=>t[l]=!0))}n||(n=this.validationMessage),this.setValidity(t,n,r)}};Tn.formAssociated=!0;D([V({reflect:!0})],Tn.prototype,"name",2);D([V({type:Boolean})],Tn.prototype,"disabled",2);D([V({state:!0,attribute:!1})],Tn.prototype,"valueHasChanged",2);D([V({state:!0,attribute:!1})],Tn.prototype,"hasInteracted",2);D([V({attribute:"custom-error",reflect:!0})],Tn.prototype,"customError",2);D([V({attribute:!1,state:!0,type:Object})],Tn.prototype,"validity",1);var BD=`@layer wa-utilities {
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
`;const BE=Symbol.for(""),UD=e=>{if(e?.r===BE)return e?._$litStatic$},Ev=(e,...t)=>({_$litStatic$:t.reduce(((r,n,i)=>r+(s=>{if(s._$litStatic$!==void 0)return s._$litStatic$;throw Error(`Value passed to 'literal' function must be a 'literal' result: ${s}. Use 'unsafeStatic' to pass non-literal values, but
            take care to ensure page security.`)})(n)+e[i+1]),e[0]),r:BE}),xv=new Map,zD=e=>(t,...r)=>{const n=r.length;let i,s;const o=[],a=[];let l,u=0,c=!1;for(;u<n;){for(l=t[u];u<n&&(s=r[u],(i=UD(s))!==void 0);)l+=i+t[++u],c=!0;u!==n&&a.push(s),o.push(l),u++}if(u===n&&o.push(t[n]),c){const d=o.join("$$lit$$");(t=xv.get(d))===void 0&&(o.raw=o,xv.set(d,t=o)),r=a}return e(t,...r)},Wd=zD(W);var qD=`@layer wa-component {
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
`,Ce=class extends Tn{constructor(){super(...arguments),this.assumeInteractionOn=["click"],this.hasSlotController=new nu(this,"[default]","start","end"),this.localize=new Is(this),this.invalid=!1,this.isIconButton=!1,this.title="",this.variant="neutral",this.appearance="accent",this.size="medium",this.withCaret=!1,this.disabled=!1,this.loading=!1,this.pill=!1,this.type="button",this.form=null}static get validators(){return[...super.validators,DD()]}constructLightDOMButton(){const e=document.createElement("button");return e.type=this.type,e.style.position="absolute",e.style.width="0",e.style.height="0",e.style.clipPath="inset(50%)",e.style.overflow="hidden",e.style.whiteSpace="nowrap",this.name&&(e.name=this.name),e.value=this.value||"",["form","formaction","formenctype","formmethod","formnovalidate","formtarget"].forEach(t=>{this.hasAttribute(t)&&e.setAttribute(t,this.getAttribute(t))}),e}handleClick(){if(!this.getForm())return;const t=this.constructLightDOMButton();this.parentElement?.append(t),t.click(),t.remove()}handleInvalid(){this.dispatchEvent(new VE)}handleLabelSlotChange(){const e=this.labelSlot.assignedNodes({flatten:!0});let t=!1,r=!1,n=!1,i=!1;[...e].forEach(s=>{if(s.nodeType===Node.ELEMENT_NODE){const o=s;o.localName==="wa-icon"?(r=!0,t||(t=o.label!==void 0)):i=!0}else s.nodeType===Node.TEXT_NODE&&(s.textContent?.trim()||"").length>0&&(n=!0)}),this.isIconButton=r&&!n&&!i,this.isIconButton&&!t&&console.warn('Icon buttons must have a label for screen readers. Add <wa-icon label="..."> to remove this warning.',this)}isButton(){return!this.href}isLink(){return!!this.href}handleDisabledChange(){this.updateValidity()}setValue(...e){}click(){this.button.click()}focus(e){this.button.focus(e)}blur(){this.button.blur()}render(){const e=this.isLink(),t=e?Ev`a`:Ev`button`;return Wd`
      <${t}
        part="base"
        class=${tr({button:!0,caret:this.withCaret,disabled:this.disabled,loading:this.loading,rtl:this.localize.dir()==="rtl","has-label":this.hasSlotController.test("[default]"),"has-start":this.hasSlotController.test("start"),"has-end":this.hasSlotController.test("end"),"is-icon-button":this.isIconButton})}
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
        ${this.withCaret?Wd`
                <wa-icon part="caret" class="caret" library="system" name="chevron-down" variant="solid"></wa-icon>
              `:""}
        ${this.loading?Wd`<wa-spinner part="spinner"></wa-spinner>`:""}
      </${t}>
    `}};Ce.shadowRootOptions={...Tn.shadowRootOptions,delegatesFocus:!0};Ce.css=[qD,BD,LE];D([Ue(".button")],Ce.prototype,"button",2);D([Ue("slot:not([name])")],Ce.prototype,"labelSlot",2);D([er()],Ce.prototype,"invalid",2);D([er()],Ce.prototype,"isIconButton",2);D([V()],Ce.prototype,"title",2);D([V({reflect:!0})],Ce.prototype,"variant",2);D([V({reflect:!0})],Ce.prototype,"appearance",2);D([V({reflect:!0})],Ce.prototype,"size",2);D([V({attribute:"with-caret",type:Boolean,reflect:!0})],Ce.prototype,"withCaret",2);D([V({type:Boolean})],Ce.prototype,"disabled",2);D([V({type:Boolean,reflect:!0})],Ce.prototype,"loading",2);D([V({type:Boolean,reflect:!0})],Ce.prototype,"pill",2);D([V()],Ce.prototype,"type",2);D([V({reflect:!0})],Ce.prototype,"name",2);D([V({reflect:!0})],Ce.prototype,"value",2);D([V({reflect:!0})],Ce.prototype,"href",2);D([V()],Ce.prototype,"target",2);D([V()],Ce.prototype,"rel",2);D([V()],Ce.prototype,"download",2);D([V({reflect:!0})],Ce.prototype,"form",2);D([V({attribute:"formaction"})],Ce.prototype,"formAction",2);D([V({attribute:"formenctype"})],Ce.prototype,"formEnctype",2);D([V({attribute:"formmethod"})],Ce.prototype,"formMethod",2);D([V({attribute:"formnovalidate",type:Boolean})],Ce.prototype,"formNoValidate",2);D([V({attribute:"formtarget"})],Ce.prototype,"formTarget",2);D([ar("disabled",{waitUntilFirstUpdate:!0})],Ce.prototype,"handleDisabledChange",1);Ce=D([Lr("wa-button")],Ce);var HD=`:host {
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
`,rf=class extends qt{constructor(){super(...arguments),this.localize=new Is(this)}render(){return W`
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
    `}};rf.css=HD;rf=D([Lr("wa-spinner")],rf);var jD=class extends br{static get styles(){return[br.styles,te`
        :host {
          --wa-color-surface-raised: var(--c-bg-raised);
          --spacing: var(--c-spacing-lg);
          background-color: red;
        }
      `]}};customElements.get("craft-drawer")||customElements.define("craft-drawer",jD);var WD=`:host {
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
`,Ir=class extends qt{constructor(){super(...arguments),this.localize=new Is(this),this.hasSlotController=new nu(this,"footer","header-actions","label"),this.open=!1,this.label="",this.withoutHeader=!1,this.lightDismiss=!1,this.handleDocumentKeyDown=e=>{e.key==="Escape"&&this.open&&(e.preventDefault(),e.stopPropagation(),this.requestClose(this.dialog))}}firstUpdated(){this.open&&(this.addOpenListeners(),this.dialog.showModal(),cc(this))}disconnectedCallback(){super.disconnectedCallback(),uc(this),this.removeOpenListeners()}async requestClose(e){const t=new ba({source:e});if(this.dispatchEvent(t),t.defaultPrevented){this.open=!0,Tt(this.dialog,"pulse");return}this.removeOpenListeners(),await Tt(this.dialog,"hide"),this.open=!1,this.dialog.close(),uc(this);const r=this.originalTrigger;typeof r?.focus=="function"&&setTimeout(()=>r.focus()),this.dispatchEvent(new ma)}addOpenListeners(){document.addEventListener("keydown",this.handleDocumentKeyDown)}removeOpenListeners(){document.removeEventListener("keydown",this.handleDocumentKeyDown)}handleDialogCancel(e){e.preventDefault(),!this.dialog.classList.contains("hide")&&e.target===this.dialog&&this.requestClose(this.dialog)}handleDialogClick(e){const r=e.target.closest('[data-dialog="close"]');r&&(e.stopPropagation(),this.requestClose(r))}async handleDialogPointerDown(e){e.target===this.dialog&&(this.lightDismiss?this.requestClose(this.dialog):await Tt(this.dialog,"pulse"))}handleOpenChange(){this.open&&!this.dialog.open?this.show():!this.open&&this.dialog.open&&(this.open=!0,this.requestClose(this.dialog))}async show(){const e=new va;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}this.addOpenListeners(),this.originalTrigger=document.activeElement,this.open=!0,this.dialog.showModal(),cc(this),requestAnimationFrame(()=>{const t=this.querySelector("[autofocus]");t&&typeof t.focus=="function"?t.focus():this.dialog.focus()}),await Tt(this.dialog,"show"),this.dispatchEvent(new ga)}render(){const e=!this.withoutHeader,t=this.hasSlotController.test("footer");return W`
      <dialog
        part="dialog"
        class=${tr({dialog:!0,open:this.open})}
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
    `}};Ir.css=WD;D([Ue(".dialog")],Ir.prototype,"dialog",2);D([V({type:Boolean,reflect:!0})],Ir.prototype,"open",2);D([V({reflect:!0})],Ir.prototype,"label",2);D([V({attribute:"without-header",type:Boolean,reflect:!0})],Ir.prototype,"withoutHeader",2);D([V({attribute:"light-dismiss",type:Boolean})],Ir.prototype,"lightDismiss",2);D([ar("open",{waitUntilFirstUpdate:!0})],Ir.prototype,"handleOpenChange",1);Ir=D([Lr("wa-dialog")],Ir);document.addEventListener("click",e=>{const t=e.target.closest("[data-dialog]");if(t instanceof Element){const[r,n]=DE(t.getAttribute("data-dialog")||"");if(r==="open"&&n?.length){const s=t.getRootNode().getElementById(n);s?.localName==="wa-dialog"?s.open=!0:console.warn(`A dialog with an ID of "${n}" could not be found in this document.`)}}});document.addEventListener("pointerdown",()=>{});var KD=class extends Ir{static get styles(){return[Ir.styles,te`
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
      `]}};customElements.get("craft-dialog")||customElements.define("craft-dialog",KD);class Sv extends eu(xE(Ne)){constructor(){super(),this.multipleChoice=!0}}class Cv extends tu(ru){connectedCallback(){super.connectedCallback(),this.type="checkbox"}}var GD=class extends Sv{static get styles(){return[...Sv.styles,te`
        .form-field__group-two {
          margin-top: var(--c-spacing-sm);
        }

        ::slotted(label) {
          font-weight: bold;
        }
      `]}};customElements.get("craft-checkbox-group")||customElements.define("craft-checkbox-group",GD);var YD=class extends Cv{static get styles(){return[...Cv.styles,te`
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
      `]}};customElements.get("craft-checkbox")||customElements.define("craft-checkbox",YD);const Cr={Default:"default",Success:"success",Warning:"warning",Danger:"danger",Info:"info"},XD={OutlineFill:"outline-fill"};var Vp=te`
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
`,JD=te`
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
`,Vn=class extends Ne{constructor(...e){super(...e),this.variant=Cr.Default,this.appearance=XD.OutlineFill,this.title="",this.icon=null,this.rounded="all",this.inline=!1}getDefaultIcon(){switch(this.variant){case Cr.Info:return"lightbulb";case Cr.Success:return"circle-check";case Cr.Warning:return"circle-exclamation";case Cr.Danger:return"triangle-exclamation";default:return null}}render(){return W`
      ${this.icon||this.querySelector('[slot="icon"]')?W`<slot name="icon" class="callout__icon">
            <craft-icon
              name="${this.getDefaultIcon()}"
              style="font-size: 0.9em"
            ></craft-icon>
          </slot>`:pt}
      <div class="callout__body">
        <slot name="title" class="callout__title">${this.title}</slot>
        <div class="callout__description">
          <slot></slot>
        </div>
      </div>
    `}};Vn.styles=[Vp,JD],se([V({reflect:!0})],Vn.prototype,"variant",void 0),se([V({reflect:!0})],Vn.prototype,"appearance",void 0),se([V()],Vn.prototype,"title",void 0),se([V()],Vn.prototype,"icon",void 0),se([V({reflect:!0})],Vn.prototype,"rounded",void 0),se([V({reflect:!0,type:Boolean})],Vn.prototype,"inline",void 0),customElements.get("craft-callout")||customElements.define("craft-callout",Vn);var QD=te`
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
`,Xi=class extends Ne{constructor(...t){super(...t),this.icon=null,this.href=null,this.disabled=!1,this.variant=Cr.Default}renderBody(){return W`
      <span class="action-item__prefix">
        <slot name="prefix">
          <slot name="icon">
            ${this.icon?W`<craft-icon name="${this.icon}"></craft-icon>`:pt}
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
        `}};Xi.styles=[Vp,QD],se([V()],Xi.prototype,"icon",void 0),se([V()],Xi.prototype,"href",void 0),se([V({type:Boolean})],Xi.prototype,"disabled",void 0),se([V({reflect:!0})],Xi.prototype,"variant",void 0),customElements.get("craft-action-item")||customElements.define("craft-action-item",Xi);const ZD=te`
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
`;class cn{static __createGlobalStyleNode(){const t=document.createElement("style");return t.setAttribute("data-overlays",""),t.textContent=ZD.cssText,document.head.appendChild(t),t}get list(){return this.__list}get shownList(){return this.__shownList}constructor(){this.__list=[],this.__shownList=[],this.__siblingsInert=!1,this.__blockingMap=new WeakMap,cn.__globalStyleNode||(cn.__globalStyleNode=cn.__createGlobalStyleNode())}add(t){if(this.list.find(r=>t===r))throw new Error("controller instance is already added");return this.list.push(t),t}remove(t){if(!this.list.find(r=>t===r))throw new Error("could not find controller to remove");this.__list=this.list.filter(r=>r!==t),this.__shownList=this.shownList.filter(r=>r!==t)}show(t){this.list.find(r=>t===r)&&this.hide(t),this.__shownList.unshift(t),Array.from(this.__shownList).reverse().forEach((r,n)=>{r.elevation=n+1})}hide(t){if(!this.list.find(r=>t===r))throw new Error("could not find controller to hide");this.__shownList=this.shownList.filter(r=>r!==t)}teardown(){this.list.forEach(t=>{t.teardown()}),this.__list=[],this.__shownList=[],this.__siblingsInert=!1,cn.__globalStyleNode&&(document.head.removeChild(cn.__globalStyleNode),cn.__globalStyleNode=void 0)}get siblingsInert(){return this.__siblingsInert}disableTrapsKeyboardFocusForAll(){this.shownList.forEach(t=>{t.trapsKeyboardFocus===!0&&t.disableTrapsKeyboardFocus&&t.disableTrapsKeyboardFocus({findNewTrap:!1})})}informTrapsKeyboardFocusGotEnabled(t){this.siblingsInert===!1&&t==="global"&&(this.__siblingsInert=!0)}informTrapsKeyboardFocusGotDisabled({disabledCtrl:t,findNewTrap:r=!0}={}){const n=this.shownList.find(i=>i!==t&&i.trapsKeyboardFocus===!0);n?r&&n.enableTrapsKeyboardFocus():this.siblingsInert===!0&&(this.__siblingsInert=!1)}requestToPreventScroll(){const{isIOS:t,isMacSafari:r}=sc;document.body.classList.add("overlays-scroll-lock"),(t||r)&&document.body.classList.add("overlays-scroll-lock-ios-fix"),t&&document.documentElement.classList.add("overlays-scroll-lock-ios-fix")}requestToEnableScroll(){if(this.shownList.some(i=>i.preventsScroll===!0))return;const{isIOS:r,isMacSafari:n}=sc;document.body.classList.remove("overlays-scroll-lock"),(r||n)&&document.body.classList.remove("overlays-scroll-lock-ios-fix"),r&&document.documentElement.classList.remove("overlays-scroll-lock-ios-fix")}requestToShowOnly(t){const r=this.shownList.filter(n=>n!==t);r.forEach(n=>n.hide()),this.__blockingMap.set(t,r)}retractRequestToShowOnly(t){this.__blockingMap.has(t)&&this.__blockingMap.get(t).forEach(n=>n.show())}}cn.__globalStyleNode=void 0;const eV=gl.get("@lion/ui::overlays::0.x")||new cn;function nf(){let e=document.activeElement||document.body;for(;e&&e.shadowRoot&&e.shadowRoot.activeElement;)e=e.shadowRoot.activeElement;return e}const Av=({visibility:e,display:t})=>e!=="hidden"&&t!=="none",tV=({display:e})=>e==="contents";function rV(e){if(!e||!e.isConnected||!Av(e.style))return!1;const t=window.getComputedStyle(e);return Av(t)?tV(t)?!0:!!(e.offsetWidth||e.offsetHeight||e.getClientRects().length):!1}function nV(e,t){const r=Math.max(e.tabIndex,0),n=Math.max(t.tabIndex,0);return r===0||n===0?n>r:r>n}function iV(e,t){const r=[];for(;e.length>0&&t.length>0;)nV(e[0],t[0])?r.push(t.shift()):r.push(e.shift());return[...r,...e,...t]}function sf(e){const t=e.length;if(t<2)return e;const r=Math.ceil(t/2),n=sf(e.slice(0,r)),i=sf(e.slice(r));return iV(n,i)}const Kd="matches"in Element.prototype?"matches":"msMatchesSelector";function sV(e){return e[Kd]("input, select, textarea, button, object")?e[Kd](":not([disabled])"):e[Kd]("a[href], area[href], iframe, [tabindex], [contentEditable]")}function oV(e){return sV(e)?Number(e.getAttribute("tabindex")||0):-1}function aV(e){if(e.localName==="slot")return e.assignedNodes({flatten:!0});const{children:t}=e.shadowRoot||e;return t||[]}function lV(e){return e.nodeType!==Node.ELEMENT_NODE?!1:e.localName==="slot"?!0:rV(e)}function UE(e,t){if(!lV(e))return!1;const r=e,n=oV(r);let i=n>0;n>=0&&t.push(r);const s=aV(r);for(let o=0;o<s.length;o+=1)i=UE(s[o],t)||i;return i}function zE(e){const t=[];return UE(e,t)?sf(t):t}function Cs(e,t,r={}){function n(m){return"getAttribute"in m}function i(m){if(!n(m))return null;const h=m.getAttribute("slot");let f=null;if(h){const b=r[h];b&&(f=b.filter(y=>y?.element===m)[0]||null)}return f}const s=i(e);if(s)return s.deepContains;function o(m){if(!n(e))return;const h=e.getAttribute("slot");h&&(r[h]=r[h]||[],r[h].push({element:e,deepContains:m}))}let a=e.contains(t);if(a)return o(!0),!0;function l(m){return m.tagName==="SLOT"}function u(m){return l(m)?m.assignedElements():[]}function c(m){return m.nodeType===Node.DOCUMENT_FRAGMENT_NODE}function d(m){let h=!1;for(let f=0;f<m.length;f+=1){const b=m[f];if(b&&(n(b)||c(b))&&Cs(b,t,r)){h=!0;break}}return h}function p(m){for(let h=0;h<m.children.length;h+=1){const f=m.children[h],b=i(f);if(b){a=b.deepContains||a;break}const y=u(f),w=[f.shadowRoot,...y];if(d(w)){a=!0;break}f.children.length>0&&p(f)}}return e instanceof HTMLElement&&e.shadowRoot&&(a=Cs(e.shadowRoot,t,r),a)?(o(!0),!0):(p(e),o(a),a)}const cV={tab:9};function uV(e,t){const r=zE(e);let n;r.length>=2?n=[r[0],r[r.length-1]]:r.length===1?n=[r[0],r[0]]:n=[e,e],t.shiftKey&&n.reverse();const[i,s]=n,o=nf();o===e||r.includes(o)&&s!==o||(t.preventDefault(),i.focus())}function dV(e){const t=zE(e),r=t.find(p=>p.hasAttribute("autofocus"))||e;let n,i;r===e&&(e.tabIndex=-1,e.style.setProperty("outline","none")),r.focus();function s(p){p.keyCode===cV.tab&&uV(e,p)}function o(){n=document.createElement("div"),n.style.display="none",n.setAttribute("data-is-tab-detection-element",""),e.insertBefore(n,e.children[0]),i=new MutationObserver(p=>{for(const m of p)if(m.type==="childList"){const h=!Array.from(e.children).find(b=>b.hasAttribute("data-is-tab-detection-element")),f=Array.from(m.addedNodes).find(b=>b instanceof HTMLElement&&b.hasAttribute("data-is-tab-detection-element"));h&&!f&&(i.disconnect(),o())}}),i.observe(e,{childList:!0})}function a(){return n.compareDocumentPosition(document.activeElement)===Node.DOCUMENT_POSITION_PRECEDING}function l({resetToRoot:p=!1}={}){if(Cs(e,nf()))return;let m;p?m=e:m=t[a()?0:t.length-1],m&&m.focus()}function u(){window.removeEventListener("focusin",u),l()}function c(){setTimeout(()=>{Cs(e,nf())||l({resetToRoot:!0})}),window.addEventListener("focusin",u)}function d(){window.removeEventListener("keydown",s),window.removeEventListener("focusin",u),window.removeEventListener("focusout",c),i.disconnect(),Array.from(e.children).includes(n)&&e.removeChild(n),e.style.removeProperty("outline")}return window.addEventListener("keydown",s),window.addEventListener("focusout",c),o(),{disconnect:d}}const Tv=te`
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
`,As={supportsAdoptingStyleSheets:window.ShadowRoot&&(window.ShadyCSS===void 0||window.ShadyCSS.nativeShadow)&&"adoptedStyleSheets"in Document.prototype&&"replace"in CSSStyleSheet.prototype,adoptStyle:void 0,adoptStyles:void 0},Gd=new WeakMap;function hV(e){return Array.from(e.cssRules).map(t=>t.cssText).join("")}function fV(e,t,{teardown:r=!1}={}){const n=e===document?document.body:e,i=t.cssText||hV(t);if(r){const s=Array.from(n.querySelectorAll("style"));for(const o of s)if(o.textContent===i){o.remove();break}}else{const s=document.createElement("style"),o=window.litNonce;o!==void 0&&s.setAttribute("nonce",o),s.textContent=i,n.appendChild(s)}}function pV(e,t,{teardown:r=!1}={}){let n=!1;e&&!Gd.has(e)&&Gd.set(e,[]);const i=Gd.get(e)??[],s=i.find(o=>t===o);return s&&r?i.splice(i.indexOf(t),1):!s&&!r?i.push(t):(s&&!r||!s&&r)&&(n=!0),{haltFurtherExecution:n}}function mV(e,t,{teardown:r=!1}={}){const{haltFurtherExecution:n}=pV(e,t,{teardown:r});if(n)return;if(!As.supportsAdoptingStyleSheets||sc.isIOS){fV(e,t,{teardown:r});return}const i=t instanceof CSSStyleSheet?t:t.styleSheet;if(!i)throw new Error("Please provide a CSSResultOrNative style");r?e.adoptedStyleSheets.includes(i)&&e.adoptedStyleSheets.splice(e.adoptedStyleSheets.indexOf(i),1):e.adoptedStyleSheets=[...e.adoptedStyleSheets,i]}function gV(e,t,{teardown:r=!1}={}){for(const n of t)As.adoptStyle(e,n,{teardown:r})}As.adoptStyle=mV;As.adoptStyles=gV;function bV({wrappingDialogNodeL1:e,contentWrapperNodeL2:t,contentNodeL3:r}){if(!(t.isConnected||r.isConnected))throw new Error('[OverlayController] Could not find a render target, since the provided contentNode is not connected to the DOM. Make sure that it is connected, e.g. by doing "document.body.appendChild(contentNode)", before passing it on.');let n;const i=document.createComment("tempMarker");t.isConnected?(n=t.parentElement||t.getRootNode(),n.insertBefore(i,t),e.appendChild(t)):r.assignedSlot?(n=r.assignedSlot.parentElement||r.assignedSlot.getRootNode(),n.insertBefore(i,r.assignedSlot),e.appendChild(t),t.appendChild(r.assignedSlot)):(n=r.parentElement||r.getRootNode(),n.insertBefore(i,r),e.appendChild(t),t.appendChild(r)),n.insertBefore(e,i),n?.removeChild(i)}async function vV(){return Y(()=>import("./popper.js"),[],import.meta.url)}const kv=new WeakMap;class yi extends EventTarget{constructor(t={},r=eV){super(),this.manager=r,this.__sharedConfig=t,this.__activeElementRightBeforeHide=null,this.config={},this._defaultConfig={placementMode:void 0,contentNode:t.contentNode,contentWrapperNode:t.contentWrapperNode,invokerNode:t.invokerNode,backdropNode:t.backdropNode,referenceNode:void 0,elementToFocusAfterHide:t.invokerNode,inheritsReferenceWidth:"none",hasBackdrop:!1,isBlocking:!1,preventsScroll:!1,trapsKeyboardFocus:!1,hidesOnEsc:!1,hidesOnOutsideEsc:!1,hidesOnOutsideClick:!1,isTooltip:!1,isAlertDialog:!1,invokerRelation:"description",visibilityTriggerFunction:void 0,handlesAccessibility:!1,popperConfig:{placement:"top",strategy:"fixed",modifiers:[{name:"preventOverflow",enabled:!0,options:{boundariesElement:"viewport",padding:8}},{name:"flip",options:{boundariesElement:"viewport",padding:16}},{name:"offset",enabled:!0,options:{offset:[0,8]}},{name:"arrow",enabled:!1}]},viewportConfig:{placement:"center"},zIndex:9999},this._contentId=`overlay-content--${Math.random().toString(36).slice(2,10)}`,this.__originalAttrs=new Map,this.updateConfig(t),this.__hasActiveTrapsKeyboardFocus=!1,this.__hasActiveBackdrop=!0,this.__escKeyHandler=this.__escKeyHandler.bind(this),this.__cancelHandler=this.__cancelHandler.bind(this)}get invoker(){return this.invokerNode}get content(){return this.__wrappingDialogNode}get placementMode(){return this.config?.placementMode}get invokerNode(){return this.config?.invokerNode}get referenceNode(){return this.config?.referenceNode}get contentNode(){return this.config?.contentNode}get contentWrapperNode(){return this.__contentWrapperNode||this.config?.contentWrapperNode}get backdropNode(){return this.__backdropNode||this.config?.backdropNode}get elementToFocusAfterHide(){return this.__elementToFocusAfterHide||this.config?.elementToFocusAfterHide}get hasBackdrop(){return!!this.backdropNode||this.config?.hasBackdrop}get isBlocking(){return this.config?.isBlocking}get preventsScroll(){return this.config?.preventsScroll}get trapsKeyboardFocus(){return this.config?.trapsKeyboardFocus}get hidesOnEsc(){return this.config?.hidesOnEsc}get hidesOnOutsideClick(){return this.config?.hidesOnOutsideClick}get hidesOnOutsideEsc(){return this.config?.hidesOnOutsideEsc}get inheritsReferenceWidth(){return this.config?.inheritsReferenceWidth}get handlesAccessibility(){return this.config?.handlesAccessibility}get isTooltip(){return this.config?.isTooltip}get isAlertDialog(){return this.config?.isAlertDialog}get invokerRelation(){return this.config?.invokerRelation}get popperConfig(){return this.config?.popperConfig}get viewportConfig(){return this.config?.viewportConfig}get visibilityTriggerFunction(){return this.config?.visibilityTriggerFunction}get _referenceNode(){return this.referenceNode||this.invokerNode}set elevation(t){this.__wrappingDialogNode.style.zIndex=`${this.config.zIndex+t}`}get elevation(){return Number(this.contentWrapperNode?.style.zIndex)}updateConfig(t){this.teardown(),this.__prevConfig=this.config,this.config={...this._defaultConfig,...this.__sharedConfig,...t,popperConfig:{...this._defaultConfig.popperConfig||{},...this.__sharedConfig.popperConfig||{},...t.popperConfig||{},modifiers:[...this._defaultConfig.popperConfig?.modifiers||[],...this.__sharedConfig.popperConfig?.modifiers||[],...t.popperConfig?.modifiers||[]]}},this.__validateConfiguration(this.config),this._init(),this.__elementToFocusAfterHide=void 0,this.#e()||this.manager.add(this)}#e(){return!!this.manager.list.find(t=>this===t)}__validateConfiguration(t){if(!t.placementMode)throw new Error('[OverlayController] You need to provide a .placementMode ("global"|"local")');if(!["global","local"].includes(t.placementMode))throw new Error(`[OverlayController] "${t.placementMode}" is not a valid .placementMode, use ("global"|"local")`);if(!t.contentNode)throw new Error("[OverlayController] You need to provide a .contentNode");if(t.isTooltip&&!t.handlesAccessibility)throw new Error("[OverlayController] .isTooltip only takes effect when .handlesAccessibility is enabled")}_init(){this.__contentHasBeenInitialized||(this.__initContentDomStructure(),this.__contentHasBeenInitialized=!0),this.contentWrapperNode.removeAttribute("style"),this.contentWrapperNode.removeAttribute("class"),this.placementMode==="local"&&(yi.popperModule||(yi.popperModule=vV())),this.__handleOverlayStyles({phase:"init"}),this._handleFeatures({phase:"init"})}__handleOverlayStyles({phase:t}){const r=this.contentWrapperNode?.getRootNode();t==="init"?As.adoptStyle(r,Tv):t==="teardown"&&As.adoptStyle(r,Tv,{teardown:!0})}__initContentDomStructure(){const t=document.createElement(this.config?._noDialogEl?"div":"dialog");t.setAttribute("role","none"),t.setAttribute("data-overlay-outer-wrapper",""),t.style.cssText=`display:none; z-index: ${this.config.zIndex}; padding: 0;`,this.__wrappingDialogNode=t,this.config?.contentWrapperNode||(this.__contentWrapperNode=document.createElement("div")),this.contentWrapperNode.setAttribute("data-id","content-wrapper"),bV({wrappingDialogNodeL1:t,contentWrapperNodeL2:this.contentWrapperNode,contentNodeL3:this.contentNode}),t.open=!0,this.isTooltip&&t.setAttribute("tabindex","-1"),this.__wrappingDialogNode.style.display="none",this.contentWrapperNode.style.zIndex="1",getComputedStyle(this.contentNode).position==="absolute"&&(this.contentNode.style.position="static"),HTMLDialogElement&&"closedBy"in HTMLDialogElement.prototype?t.closedBy="none":(t.addEventListener("keydown",n=>{n.key==="Escape"&&n.preventDefault()}),t.addEventListener("keyup",n=>{n.key==="Escape"&&n.preventDefault()}),t.addEventListener("cancel",n=>{n.stopPropagation()}),t.addEventListener("close",n=>{n.stopPropagation()}))}_handleZIndex({phase:t}){if(this.placementMode==="local"&&t==="setup"){const r=Number(getComputedStyle(this.contentNode).zIndex);(r<1||Number.isNaN(r))&&(this.contentNode.style.zIndex="1")}}__setupTeardownAccessibility({phase:t}){if(t==="init"){this.__storeOriginalAttrs(this.contentNode,["role","id"]);const r=this.trapsKeyboardFocus;if(this.invokerNode){const n=["aria-labelledby","aria-describedby"];r||n.push("aria-expanded"),this.__storeOriginalAttrs(this.invokerNode,n)}this.contentNode.id||this.contentNode.setAttribute("id",this._contentId),this.isTooltip?(this.invokerNode&&this.invokerNode.setAttribute(this.invokerRelation==="label"?"aria-labelledby":"aria-describedby",this._contentId),this.contentNode.setAttribute("role","tooltip")):(this.invokerNode&&!r&&this.invokerNode.setAttribute("aria-expanded",`${this.isShown}`),this.isAlertDialog?this.contentNode.setAttribute("role","alertdialog"):this.contentNode.getAttribute("role")||this.contentNode.setAttribute("role","dialog"))}else t==="teardown"&&this.__restoreOriginalAttrs()}__storeOriginalAttrs(t,r){const n={};r.forEach(i=>{n[i]=t.getAttribute(i)}),this.__originalAttrs.set(t,n)}__restoreOriginalAttrs(){for(const[t,r]of this.__originalAttrs)Object.entries(r).forEach(([n,i])=>{i!==null?t.setAttribute(n,i):t.removeAttribute(n)});this.__originalAttrs.clear()}get isShown(){return this.__wrappingDialogNode?.style.display!=="none"}async show(t=this.elementToFocusAfterHide){if(this._showComplete&&await this._showComplete,this._showComplete=new Promise(n=>{this._showResolve=n}),this.manager&&this.manager.show(this),this.isShown){this._showResolve();return}const r=new CustomEvent("before-show",{cancelable:!0});this.dispatchEvent(r),r.defaultPrevented||("HTMLDialogElement"in window&&this.__wrappingDialogNode instanceof HTMLDialogElement&&(this.__wrappingDialogNode.open=!0),this.__wrappingDialogNode.style.display="",this._keepBodySize({phase:"before-show"}),await this._handleFeatures({phase:"show"}),this._keepBodySize({phase:"show"}),await this._handlePosition({phase:"show"}),this.__elementToFocusAfterHide=t,this.dispatchEvent(new Event("show")),await this._transitionShow({backdropNode:this.backdropNode,contentNode:this.contentNode})),this._showResolve()}async _handlePosition({phase:t}){if(this.placementMode==="global"){const r=`overlays__overlay-container--${this.viewportConfig.placement}`;t==="show"?(this.contentWrapperNode.classList.add("overlays__overlay-container"),this.contentWrapperNode.classList.add(r),this.contentNode.classList.add("overlays__overlay")):t==="hide"&&(this.contentWrapperNode.classList.remove("overlays__overlay-container"),this.contentWrapperNode.classList.remove(r),this.contentNode.classList.remove("overlays__overlay"))}else this.placementMode==="local"&&t==="show"&&(await this.__createPopperInstance(),this._popper.forceUpdate())}_keepBodySize({phase:t}){if(this.preventsScroll)switch(t){case"before-show":this.__bodyClientWidth=document.body.clientWidth,this.__bodyClientHeight=document.body.clientHeight,this.__bodyMarginRightInline=document.body.style.marginRight,this.__bodyMarginBottomInline=document.body.style.marginBottom;break;case"show":{if(window.getComputedStyle){const o=window.getComputedStyle(document.body);this.__bodyMarginRight=parseInt(o.getPropertyValue("margin-right"),10),this.__bodyMarginBottom=parseInt(o.getPropertyValue("margin-bottom"),10)}else this.__bodyMarginRight=0,this.__bodyMarginBottom=0;const r=document.body.clientWidth-this.__bodyClientWidth,n=document.body.clientHeight-this.__bodyClientHeight,i=this.__bodyMarginRight+r,s=this.__bodyMarginBottom+n;window.CSS?.number&&document.body.attributeStyleMap?.set?(document.body.attributeStyleMap.set("margin-right",CSS.px(i)),document.body.attributeStyleMap.set("margin-bottom",CSS.px(s))):(document.body.style.marginRight=`${i}px`,document.body.style.marginBottom=`${s}px`);break}case"hide":document.body.style.marginRight=this.__bodyMarginRightInline||"",document.body.style.marginBottom=this.__bodyMarginBottomInline||"";break}}async hide(){if(this._hideComplete=new Promise(r=>{this._hideResolve=r}),this.__activeElementRightBeforeHide=this.contentNode.getRootNode().activeElement,this.manager&&this.manager.hide(this),!this.isShown){this._hideResolve();return}const t=new CustomEvent("before-hide",{cancelable:!0});this.dispatchEvent(t),t.defaultPrevented||(await this._transitionHide({backdropNode:this.backdropNode,contentNode:this.contentNode}),"HTMLDialogElement"in window&&this.__wrappingDialogNode instanceof HTMLDialogElement&&this.__wrappingDialogNode.close(),this.__wrappingDialogNode.style.display="none",this._handleFeatures({phase:"hide"}),this._keepBodySize({phase:"hide"}),this.dispatchEvent(new Event("hide")),this._restoreFocus()),this._hideResolve()}async transitionHide(t){}async _transitionHide({backdropNode:t,contentNode:r}){await this.transitionHide({backdropNode:t,contentNode:r}),this._handlePosition({phase:"hide"}),t&&t.classList.remove("overlays__backdrop--animation-in")}async transitionShow(t){}async _transitionShow(t){await this.transitionShow({backdropNode:this.backdropNode,contentNode:this.contentNode}),t.backdropNode&&t.backdropNode.classList.add("overlays__backdrop--animation-in")}_restoreFocus(){this.__activeElementRightBeforeHide instanceof HTMLElement&&this.contentNode.contains(this.__activeElementRightBeforeHide)&&(this.elementToFocusAfterHide instanceof HTMLElement?(this.elementToFocusAfterHide.focus(),this.elementToFocusAfterHide.scrollIntoView({block:"nearest"})):this.__activeElementRightBeforeHide.blur())}async toggle(){return this.isShown?this.hide():this.show()}_handleFeatures({phase:t}){this._handleZIndex({phase:t}),this.preventsScroll&&this._handlePreventsScroll({phase:t}),this.isBlocking&&this._handleBlocking({phase:t}),this.hasBackdrop&&this._handleBackdrop({phase:t}),this.trapsKeyboardFocus&&this._handleTrapsKeyboardFocus({phase:t}),this.hidesOnEsc&&this._handleHidesOnEsc({phase:t}),this.hidesOnOutsideEsc&&this._handleHidesOnOutsideEsc({phase:t}),this.hidesOnOutsideClick&&this._handleHidesOnOutsideClick({phase:t}),this.handlesAccessibility&&this._handleAccessibility({phase:t}),this.inheritsReferenceWidth&&this._handleInheritsReferenceWidth(),this.visibilityTriggerFunction&&this._handleVisibilityTriggers({phase:t})}_handleVisibilityTriggers({phase:t}){typeof this.visibilityTriggerFunction=="function"&&(t==="init"&&(this.__visibilityTriggerHandler=this.visibilityTriggerFunction({phase:t,controller:this})),this.__visibilityTriggerHandler[t]&&this.__visibilityTriggerHandler[t]())}_handlePreventsScroll({phase:t}){switch(t){case"show":this.manager.requestToPreventScroll();break;case"hide":this.manager.requestToEnableScroll();break}}_handleBlocking({phase:t}){switch(t){case"show":this.manager.requestToShowOnly(this);break;case"hide":this.manager.retractRequestToShowOnly(this);break}}get hasActiveBackdrop(){return this.__hasActiveBackdrop}_handleBackdrop({phase:t}){switch(t){case"init":{this.__backdropInitialized||(this.config?.backdropNode||(this.__backdropNode=document.createElement("div"),this.__backdropNode.classList.add("overlays__backdrop")),this.__wrappingDialogNode.prepend(this.backdropNode),this.__backdropInitialized=!0);break}case"show":this.config.hasBackdrop&&this.backdropNode.classList.add("overlays__backdrop--visible"),this.__hasActiveBackdrop=!0;break;case"hide":case"teardown":this.backdropNode.classList.remove("overlays__backdrop--visible"),this.__hasActiveBackdrop=!1;break}}get hasActiveTrapsKeyboardFocus(){return this.__hasActiveTrapsKeyboardFocus}_handleTrapsKeyboardFocus({phase:t}){t==="show"?("showModal"in this.__wrappingDialogNode&&(this.__wrappingDialogNode.close(),this.__wrappingDialogNode.showModal()),this.enableTrapsKeyboardFocus()):(t==="hide"||t==="teardown")&&this.disableTrapsKeyboardFocus()}enableTrapsKeyboardFocus(){if(this.__hasActiveTrapsKeyboardFocus)return;this.manager&&this.manager.disableTrapsKeyboardFocusForAll(),!!this.contentNode.shadowRoot&&console.warn("[overlays]: For best accessibility (compatibility with Safari + VoiceOver), provide a contentNode that is not a host for a shadow root"),this._containFocusHandler=dV(this.contentNode),this.__hasActiveTrapsKeyboardFocus=!0,this.manager&&this.manager.informTrapsKeyboardFocusGotEnabled(this.placementMode)}disableTrapsKeyboardFocus({findNewTrap:t=!0}={}){this.__hasActiveTrapsKeyboardFocus&&(this._containFocusHandler&&(this._containFocusHandler.disconnect(),this._containFocusHandler=void 0),this.__hasActiveTrapsKeyboardFocus=!1,this.manager&&this.manager.informTrapsKeyboardFocusGotDisabled({disabledCtrl:this,findNewTrap:t}))}__cancelHandler(t){t.preventDefault()}__escKeyHandler(t){if(t.key!=="Escape"||kv.has(t))return;(t.composedPath().includes(this.contentNode)||Cs(this.contentNode,t.target))&&(this.hide(),kv.set(t,this))}#t=t=>{t.key!=="Escape"||t.composedPath().includes(this.contentNode)||Cs(this.contentNode,t.target)||this.hide()};_handleHidesOnEsc({phase:t}){t==="show"?(this.contentNode.addEventListener("keyup",this.__escKeyHandler),this.invokerNode&&this.invokerNode.addEventListener("keyup",this.__escKeyHandler)):(t==="hide"||t==="teardown")&&(this.contentNode.removeEventListener("keyup",this.__escKeyHandler),this.invokerNode&&this.invokerNode.removeEventListener("keyup",this.__escKeyHandler))}_handleHidesOnOutsideEsc({phase:t}){t==="show"?document.addEventListener("keyup",this.#t):(t==="hide"||t==="teardown")&&document.removeEventListener("keyup",this.#t)}_handleInheritsReferenceWidth(){if(!this._referenceNode||this.placementMode==="global")return;const t=`${this._referenceNode.getBoundingClientRect().width}px`;switch(this.inheritsReferenceWidth){case"max":this.contentWrapperNode.style.maxWidth=t;break;case"full":this.contentWrapperNode.style.width=t;break;case"min":this.contentWrapperNode.style.minWidth=t,this.contentWrapperNode.style.width="auto";break}}_handleHidesOnOutsideClick({phase:t}){const r=t==="show"?"addEventListener":"removeEventListener";if(t==="show"){let n=!1,i=!1;this.__onInsideMouseDown=()=>{n=!0},this.__onInsideMouseUp=()=>{i=!0},this.__onDocumentMouseUp=()=>{setTimeout(()=>{!n&&!i&&this.hide(),n=!1,i=!1})},this.__onWindowBlur=()=>{setTimeout(()=>{this.hide()})}}this.contentWrapperNode[r]("mousedown",this.__onInsideMouseDown,!0),this.contentWrapperNode[r]("mouseup",this.__onInsideMouseUp,!0),this.invokerNode&&(this.invokerNode[r]("mousedown",this.__onInsideMouseDown,!0),this.invokerNode[r]("mouseup",this.__onInsideMouseUp,!0)),document.documentElement[r]("mouseup",this.__onDocumentMouseUp,!0),window[r]("blur",this.__onWindowBlur)}_handleAccessibility({phase:t}){(t==="init"||t==="teardown")&&this.__setupTeardownAccessibility({phase:t});const r=this.trapsKeyboardFocus;this.invokerNode&&!this.isTooltip&&!r&&this.invokerNode.setAttribute("aria-expanded",`${t==="show"}`)}teardown(){this.__handleOverlayStyles({phase:"teardown"}),this._handleFeatures({phase:"teardown"}),this.#e()&&this.manager.remove(this)}async __createPopperInstance(){if(this._popper&&(this._popper.destroy(),this._popper=void 0),yi.popperModule!==void 0){const{createPopper:t}=await yi.popperModule;this._popper=t(this._referenceNode,this.contentWrapperNode,{...this.config?.popperConfig})}}_hasDisabledInvoker(){return this.invokerNode?this.invokerNode.disabled||this.invokerNode.getAttribute("aria-disabled")==="true":!1}}yi.popperModule=void 0;function qE(e,t){if(typeof e!="object"||typeof t!="object"||e===null||t===null)return e===t;const r=Object.keys(e),n=Object.keys(t);if(r.length!==n.length)return!1;const i=s=>qE(e[s],t[s]);return r.every(i)}const yV=e=>class extends e{static get properties(){return{opened:{type:Boolean,reflect:!0}}}#e=!1;constructor(){super(),this.opened=!1,this.config={},this.toggle=this.toggle.bind(this),this.open=this.open.bind(this),this.close=this.close.bind(this)}get config(){return this.__config}set config(r){const n=!qE(this.config,r);this._overlayCtrl&&n&&this._overlayCtrl.updateConfig(r),this.__config=r,this._overlayCtrl&&n&&this.__syncToOverlayController()}requestUpdate(r,n,i){super.requestUpdate(r,n,i),r==="opened"&&this.opened!==n&&this.dispatchEvent(new CustomEvent("opened-changed",{detail:{opened:this.opened}}))}_defineOverlay({contentNode:r,invokerNode:n,referenceNode:i,backdropNode:s,contentWrapperNode:o}){const a=this._defineOverlayConfig()||{};return new yi({contentNode:r,invokerNode:n,referenceNode:i,backdropNode:s,contentWrapperNode:o,...a,...this.config,popperConfig:{...a.popperConfig||{},...this.config?.popperConfig||{},modifiers:[...a.popperConfig?.modifiers||[],...this.config?.popperConfig?.modifiers||[]]}})}_defineOverlayConfig(){return{placementMode:"local"}}updated(r){super.updated(r),r.has("opened")&&this._overlayCtrl&&!this.__blockSyncToOverlayCtrl&&this.__syncToOverlayController()}_setupOpenCloseListeners(){this.__closeEventInContentNodeHandler=r=>{r.stopPropagation(),this._overlayCtrl.hide()},this._overlayContentNode&&this._overlayContentNode.addEventListener("close-overlay",this.__closeEventInContentNodeHandler)}_teardownOpenCloseListeners(){this._overlayContentNode&&this._overlayContentNode.removeEventListener("close-overlay",this.__closeEventInContentNodeHandler)}connectedCallback(){super.connectedCallback(),this.updateComplete.then(()=>{this.isConnected&&(this.#e||(this._setupOverlayCtrl(),this.#e=!0))})}async disconnectedCallback(){super.disconnectedCallback(),await this._isPermanentlyDisconnected()&&(this._teardownOverlayCtrl(),this.#e=!1)}static enabledWarnings=super.enabledWarnings?.filter(r=>r!=="change-in-update")||[];get _overlayInvokerNode(){return Array.from(this.children).find(r=>r.slot==="invoker")}get _overlayReferenceNode(){}get _overlayBackdropNode(){return this.__cachedOverlayBackdropNode||(this.__cachedOverlayBackdropNode=Array.from(this.children).find(r=>r.slot==="backdrop")),this.__cachedOverlayBackdropNode}get _overlayContentNode(){return this._cachedOverlayContentNode||(this._cachedOverlayContentNode=Array.from(this.children).find(r=>r.slot==="content")||this.config.contentNode),this._cachedOverlayContentNode}get _overlayContentWrapperNode(){return this.shadowRoot?.querySelector("#overlay-content-node-wrapper")}_setupOverlayCtrl(){if(this.#e)return;const r={contentNode:this._overlayContentNode,contentWrapperNode:this._overlayContentWrapperNode,invokerNode:this._overlayInvokerNode,referenceNode:this._overlayReferenceNode,backdropNode:this._overlayBackdropNode};this._overlayCtrl?this._overlayCtrl.updateConfig(r):this._overlayCtrl=this._defineOverlay(r),this.__syncToOverlayController(),this.__setupSyncFromOverlayController(),this._setupOpenCloseListeners()}_teardownOverlayCtrl(){this._overlayCtrl&&(this._teardownOpenCloseListeners(),this.__teardownSyncFromOverlayController(),this._overlayCtrl.teardown())}async _setOpenedWithoutPropertyEffects(r){this.__blockSyncToOverlayCtrl=!0,this.opened=r,await this.updateComplete,this.__blockSyncToOverlayCtrl=!1}__setupSyncFromOverlayController(){this.__onOverlayCtrlShow=()=>{this.opened=!0},this.__onOverlayCtrlHide=()=>{this.opened=!1},this.__onBeforeShow=r=>{const n=new CustomEvent("before-opened",{cancelable:!0});this.dispatchEvent(n),n.defaultPrevented&&(this._setOpenedWithoutPropertyEffects(this._overlayCtrl.isShown),r.preventDefault())},this.__onBeforeHide=r=>{const n=new CustomEvent("before-closed",{cancelable:!0});this.dispatchEvent(n),n.defaultPrevented&&(this._setOpenedWithoutPropertyEffects(this._overlayCtrl.isShown),r.preventDefault())},this._overlayCtrl.addEventListener("show",this.__onOverlayCtrlShow),this._overlayCtrl.addEventListener("hide",this.__onOverlayCtrlHide),this._overlayCtrl.addEventListener("before-show",this.__onBeforeShow),this._overlayCtrl.addEventListener("before-hide",this.__onBeforeHide)}__teardownSyncFromOverlayController(){this._overlayCtrl.removeEventListener("show",this.__onOverlayCtrlShow),this._overlayCtrl.removeEventListener("hide",this.__onOverlayCtrlHide),this._overlayCtrl.removeEventListener("before-show",this.__onBeforeShow),this._overlayCtrl.removeEventListener("before-hide",this.__onBeforeHide)}__syncToOverlayController(){this.opened?this._overlayCtrl.show():this._overlayCtrl.hide()}async toggle(){await this._overlayCtrl.toggle()}async open(){await this._overlayCtrl.show()}async close(){await this._overlayCtrl.hide()}repositionOverlay(){const r=this._overlayCtrl;r.placementMode==="local"&&r._popper&&r._popper.update()}async _isPermanentlyDisconnected(){return await this.updateComplete,!this.isConnected}},HE=He(yV);function _V(){return{visibilityTriggerFunction:({controller:e})=>{function t(){e._hasDisabledInvoker()||e.toggle()}return{init:()=>{e.invokerNode?.addEventListener("click",t)},teardown:()=>{e.invokerNode?.removeEventListener("click",t)}}}}}const jE=()=>({placementMode:"local",inheritsReferenceWidth:"min",hidesOnOutsideClick:!0,hidesOnEsc:!0,popperConfig:{placement:"bottom-start",modifiers:[{name:"offset",enabled:!1}]},handlesAccessibility:!0,..._V()});var Js=class extends HE(Ne){_defineOverlayConfig(){return{...jE()}}_addEventListeners(){this.actionItems.forEach(t=>{t.addEventListener("click",r=>{r.target?.dispatchEvent(new Event("close-overlay",{bubbles:!0}))})})}_setupInvoker(){let t=this.invokerNodes[0];t&&(t.setAttribute("id",`invoker-${this.uid}`),t.setAttribute("aria-controls",`content-${this.uid}`))}_setupContent(){let t=this.contentNodes[0];t&&(t.setAttribute("id",`content-${this.uid}`),t.setAttribute("role","none"))}_setupOverlayCtrl(){super._setupOverlayCtrl(),this._setupInvoker(),this._setupContent()}firstUpdated(){this.uid=_a(),this._addEventListeners()}render(){return W`
      <slot name="invoker"></slot>
      <slot name="backdrop"></slot>
      <slot name="content"></slot>
    `}};Js.styles=te`
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
  `,se([hl({selector:"craft-action-item"})],Js.prototype,"actionItems",void 0),se([hl({slot:"invoker"})],Js.prototype,"invokerNodes",void 0),se([hl({slot:"content"})],Js.prototype,"contentNodes",void 0),customElements.get("craft-action-menu")||customElements.define("craft-action-menu",Js);const ao=new WeakMap;function WE(e,t){Array.from(e.childNodes).forEach(r=>{if(r.nodeName==="#text"){const n=new RegExp(`^(.*?)(${t})(.*)$`,"i"),i=r.nodeValue.match(n);if(i){const s=document.createTextNode(i[1]);e.appendChild(s);const o=document.createElement("b");o.textContent=i[2],e.appendChild(o);const a=document.createTextNode(i[3]);e.appendChild(a),e.removeChild(r),ao.set(e,()=>{e.appendChild(r),e.contains(s)&&s.parentNode!==null&&s.parentNode.removeChild(s),e.contains(o)&&o.parentNode!==null&&o.parentNode.removeChild(o),e.contains(a)&&a.parentNode!==null&&a.parentNode.removeChild(a)})}}else WE(r,t)})}function KE(e){ao.has(e)&&ao.get(e)(),Array.from(e.childNodes).forEach(t=>{t.nodeName==="#text"?ao.has(t)&&ao.get(t)():KE(t)})}class wV extends Zc{static get validatorName(){return"MatchesOption"}execute(t,r,n){return n?.node.modelValue instanceof Ri}}function Za(e){return Array.isArray(e)?e:[e]}const EV=e=>class extends eu(e){static get properties(){return{allowCustomChoice:{type:Boolean,attribute:"allow-custom-choice"},modelValue:{type:Object}}}get modelValue(){return this.__getChoicesFrom(super.modelValue)}set modelValue(r){if(super.modelValue=r,r==null||r==="")this._customChoices=new Set;else if(this.allowCustomChoice){const n=this.modelValue;this._customChoices=new Set(Za(r)),this.requestUpdate("modelValue",n)}}get formattedValue(){return this.__getChoicesFrom(super.formattedValue)}set formattedValue(r){if(super.formattedValue=r,r==null)this._customChoices=new Set;else if(this.allowCustomChoice){const n=this.modelValue;this._customChoices=new Set(Za(r).map(i=>this.formElements.find(s=>s.formattedValue===i)?.modelValue||i)),this.requestUpdate("modelValue",n)}}get serializedValue(){return this.__getChoicesFrom(super.serializedValue)}set serializedValue(r){if(super.serializedValue=r,r==null)this._customChoices=new Set;else if(this.allowCustomChoice){const n=this.modelValue;this._customChoices=new Set(Za(r).map(i=>this.formElements.find(s=>s.serializedValue===i)?.modelValue||i)),this.requestUpdate("modelValue",n)}}get customChoices(){if(!this.allowCustomChoice)return[];const r=this._getCheckedElements();return Array.from(this._customChoices).filter(n=>!r.some(i=>i.choiceValue===n))}constructor(){super(),this.allowCustomChoice=!1,this._customChoices=new Set}__getChoicesFrom(r){const n=r;return this.allowCustomChoice?this.multipleChoice?[...Za(n),...this.customChoices]:n===""?this._customChoices.values().next().value||"":n:n}_isEmpty(){return super._isEmpty()&&this._customChoices.size===0}clear(){this._customChoices=new Set,super.clear()}parser(r){return this.allowCustomChoice&&Array.isArray(r)?r.filter(n=>n.trim()!==""):r}},xV=He(EV),Yd=new WeakMap;class SV extends Qc(HE(xV(cD))){static get properties(){return{autocomplete:{type:String,reflect:!0},matchMode:{type:String,attribute:"match-mode"},showAllOnEmpty:{type:Boolean,attribute:"show-all-on-empty"},requireOptionMatch:{type:Boolean},allowCustomChoice:{type:Boolean,attribute:"allow-custom-choice"},__shouldAutocompleteNextUpdate:Boolean}}static get styles(){return[...super.styles,te`
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
          padding: 0;`,t.appendChild(r),t}return document.createElement("input")},listbox:super.slots.input}}get _comboboxNode(){return this.querySelector('[slot="input"]')}get _selectionDisplayNode(){return this.querySelector('[slot="selection-display"]')}get _inputNode(){return this._ariaVersion==="1.1"&&this._comboboxNode?this._comboboxNode.querySelector("input")||this._comboboxNode:this._comboboxNode}get _overlayContentNode(){return this._listboxNode}get _overlayReferenceNode(){return this.shadowRoot.querySelector(".input-group__container")}get _overlayInvokerNode(){return this._inputNode}get _listboxNode(){return this._overlayCtrl&&this._overlayCtrl.contentNode||Array.from(this.children).find(t=>t.slot==="listbox")}get _activeDescendantOwnerNode(){return this._inputNode}get requireOptionMatch(){return!this.allowCustomChoice}set requireOptionMatch(t){this.allowCustomChoice=!t}constructor(){super(),this.autocomplete="both",this.matchMode="all",this.showAllOnEmpty=!1,this.requireOptionMatch=!0,this.rotateKeyboardNavigation=!0,this.selectionFollowsFocus=!0,this.defaultValidators.push(new wV),this._ariaVersion=sc.isChromium?"1.1":"1.0",this._listboxReceivesNoFocus=!0,this._noTypeAhead=!0,this.__prevCboxValueNonSelected="",this.__prevCboxValue="",this.__hadUserIntendsInlineAutoFill=!1,this.__listboxContentChanged=!1,this._onKeyUp=this._onKeyUp.bind(this),this._textboxOnClick=this._textboxOnClick.bind(this),this._textboxOnInput=this._textboxOnInput.bind(this),this._textboxOnKeydown=this._textboxOnKeydown.bind(this)}connectedCallback(){super.connectedCallback(),this._selectionDisplayNode&&(this._selectionDisplayNode.comboboxElement=this),(this.disabled||this.readOnly)&&this.__setComboboxDisabledAndReadOnly()}requestUpdate(t,r,n){if(super.requestUpdate(t,r,n),(t==="disabled"||t==="readOnly")&&this.__setComboboxDisabledAndReadOnly(),t==="modelValue"&&this.modelValue&&this.modelValue!==r&&this._syncToTextboxCondition(this.modelValue,this._oldModelValue))if(this.multipleChoice)this._syncToTextboxMultiple(this.modelValue,this._oldModelValue);else{const i=this._getTextboxValueFromOption(this.formElements[this.checkedIndex]);this._setTextboxValue(i)}}parser(t){return this.requireOptionMatch&&this.checkedIndex===-1&&t!==""&&!Array.isArray(t)?new Ri(t):super.parser(t)}__unsyncCheckedIndexOnInputChange(){const t=this._autoSelectCondition(),r=this.formElements[this.checkedIndex];if(!this.multipleChoice&&!t&&r){const n=this._getTextboxValueFromOption(r);this._inputNode.value.startsWith(n)||this.setCheckedIndex(-1)}}updated(t){super.updated(t),t.has("__shouldAutocompleteNextUpdate")&&this.__unsyncCheckedIndexOnInputChange(),t.has("opened")&&(this.opened&&(this.activeIndex=-1),!this.opened&&t.get("opened")!==void 0&&(this.__onOverlayClose(),this.activeIndex=-1)),t.has("autocomplete")&&this._inputNode.setAttribute("aria-autocomplete",this.autocomplete),t.has("disabled")&&this.setAttribute("aria-disabled",`${this.disabled}`),t.has("__shouldAutocompleteNextUpdate")&&this.__shouldAutocompleteNextUpdate&&(this._handleAutocompletion(),this.__shouldAutocompleteNextUpdate=!1,this.__listboxContentChanged=!1),typeof this._selectionDisplayNode?.onComboboxElementUpdated=="function"&&this._selectionDisplayNode.onComboboxElementUpdated(t)}matchCondition(t,r){let n=-1;const i=this._getTextboxValueFromOption(t);return typeof i=="string"&&typeof r=="string"&&(n=i.toLowerCase().indexOf(r.toLowerCase())),this.matchMode==="all"?n>-1:n===0}_showOverlayCondition({lastKey:t}){const r=["Tab","Escape"],n=["Enter"];return this.disabled||this.readOnly||t&&(r.includes(t)||!this.multipleChoice&&n.includes(t))?!1:this.filled||this.showAllOnEmpty||!this.filled&&this.multipleChoice&&this.__prevCboxValueNonSelected?!0:this.opened}_getTextboxValueFromOption(t){return t?t.choiceValue:this.modelValue instanceof Ri?this.modelValue.viewValue:this.modelValue}_onListboxContentChanged(){super._onListboxContentChanged(),this.__shouldAutocompleteNextUpdate=!0,this.__listboxContentChanged=!0}_textboxOnInput(t){this.__shouldAutocompleteNextUpdate=!0,this.opened=this._showOverlayCondition({})}_textboxOnKeydown(t){t.key==="Tab"&&(this.opened=!1)}_listboxOnClick(t){super._listboxOnClick(t),this._inputNode.focus(),this.multipleChoice?(this._inputNode.value="",this._resetListboxOptions()):(this.activeIndex=-1,this.opened=!1)}_setTextboxValue(t){this._inputNode&&this._inputNode.value!==t&&(this._inputNode.value=t)}__onOverlayClose(){this.multipleChoice?this._syncToTextboxMultiple(this.modelValue,this._oldModelValue):this.checkedIndex!==-1&&this._syncToTextboxCondition(this.modelValue,this._oldModelValue,{phase:"overlay-close"})&&(this._inputNode.value=this._getTextboxValueFromOption(this.formElements[this.checkedIndex]))}_repropagationCondition(t){return super._repropagationCondition(t)||this.formElements.every(r=>!r.checked)}_onFilterMatch(t,r){this._highlightMatchedOption(t,r),t.style.display=""}_highlightMatchedOption(t,r){if(WE(t,r),t.textContent){const n=document.createElement("span");n.setAttribute("aria-label",t.textContent.replace(/\s+/g," ")),Array.from(t.childNodes).forEach(i=>{n.appendChild(i)}),t.appendChild(n),Yd.set(t,()=>{Array.from(n.childNodes).forEach(i=>{t.appendChild(i)}),t.contains(n)&&t.removeChild(n)})}}_onFilterUnmatch(t,r,n){this._unhighlightMatchedOption(t),t.style.display="none"}_unhighlightMatchedOption(t){KE(t),Yd.has(t)&&Yd.get(t)()}__computeUserIntendsAutoFill({prevValue:t,curValue:r}){const n=t.length<r.length,i=t.length&&r.length&&t[0].toLowerCase()!==r[0].toLowerCase();return n||i||this.__listboxContentChanged&&this.__hadUserIntendsInlineAutoFill}_handleAutocompletion(){const r=!(this._inputNode.selectionStart===this._inputNode.selectionEnd)&&this._inputNode.value.length!==this._inputNode.selectionStart,n=this._inputNode.value,i=this._inputNode.selectionStart,s=r&&i?n.slice(0,i):n,o=r||this.__hadSelectionLastAutofill?this.__prevCboxValueNonSelected:this.__prevCboxValue,a=!s,l=[];let u=!1;const c=this.__computeUserIntendsAutoFill({prevValue:o,curValue:s}),d=this.autocomplete==="both"||this.autocomplete==="inline",p=this._autoSelectCondition(),m=this.autocomplete==="inline"||this.autocomplete==="none";this.formElements.forEach((f,b)=>{const y=this.matchCondition(f,s);let w=!1;if(a?w=this.showAllOnEmpty:w=m||y,p&&!u&&y&&!f.disabled){const v=()=>{this.activeIndex=b,this.selectionFollowsFocus&&!this.multipleChoice&&this.setCheckedIndex(this.activeIndex),u=!0};if(c)if(d){const _=this._getTextboxValueFromOption(f);_&&typeof _=="string"&&typeof s=="string"&&_.toLowerCase().indexOf(s.toLowerCase())===0&&(this.__textboxInlineComplete(f),v())}else v()}f.onFilterUnmatch?f.onFilterUnmatch(s,o):this._onFilterUnmatch(f,s,o),f.setAttribute("aria-hidden","true"),f.removeAttribute("aria-posinset"),f.removeAttribute("aria-setsize"),w&&(l.push(f),f.onFilterMatch?f.onFilterMatch(s):this._onFilterMatch(f,s))});const h=l.length;l.forEach((f,b)=>{f.setAttribute("aria-posinset",`${b+1}`),f.setAttribute("aria-setsize",`${h}`),f.removeAttribute("aria-hidden")}),p&&!u&&!this.multipleChoice&&(this.setCheckedIndex(-1),o!==s&&(this.activeIndex=-1),this.modelValue=this.parser(n)),this.__prevCboxValueNonSelected=s,this.__prevCboxValue=this._inputNode.value,this.__hadSelectionLastAutofill=this._inputNode.value.length!==this._inputNode.selectionStart,this.__hadUserIntendsInlineAutoFill=c,this._overlayCtrl&&this._overlayCtrl._popper&&this._overlayCtrl._popper.update()}__textboxInlineComplete(t=this.formElements[this.activeIndex]){const r=this._getTextboxValueFromOption(t);if(this._inputNode.value!==r){const n=this._inputNode.value.length;this._inputNode.value=r,this._inputNode.selectionStart=n,this._inputNode.selectionEnd=this._inputNode.value.length}}_autoSelectCondition(){return this.autocomplete==="both"||this.autocomplete==="inline"}_setupListboxNode(){super._setupListboxNode(),this._listboxNode.removeAttribute("tabindex")}_defineOverlayConfig(){return{...jE(),elementToFocusAfterHide:void 0,invokerNode:this._comboboxNode,visibilityTriggerFunction:void 0}}_setupOverlayCtrl(){super._setupOverlayCtrl(),this.__shouldAutocompleteNextUpdate=!0,this.__setupCombobox()}_teardownOverlayCtrl(){super._teardownOverlayCtrl(),this.__teardownCombobox()}_setupOpenCloseListeners(){super._setupOpenCloseListeners(),this._inputNode.addEventListener("keyup",this._onKeyUp),this._inputNode.addEventListener("click",this._textboxOnClick)}_teardownOpenCloseListeners(){super._teardownOpenCloseListeners(),this._inputNode.removeEventListener("keyup",this._onKeyUp),this._inputNode.removeEventListener("click",this._textboxOnClick)}_listboxOnKeyDown(t){const{key:r}=t;switch(r){case"Escape":this.opened=!1,super._listboxOnKeyDown(t),this._setTextboxValue("");break;case"Backspace":case"Delete":this.requireOptionMatch?super._listboxOnKeyDown(t):this.opened=!1;break;case"Enter":this.opened&&t.preventDefault(),!this.requireOptionMatch&&this.multipleChoice&&(!this.formElements[this.activeIndex]||this.formElements[this.activeIndex].hasAttribute("aria-hidden")||!this.opened)?(this.modelValue=this.parser([...this.modelValue,this._inputNode.value]),this._inputNode.value="",this.opened=!1):(super._listboxOnKeyDown(t),this._resetListboxOptions()),this.multipleChoice?this._inputNode.value="":this.opened=!1;break;default:{super._listboxOnKeyDown(t);break}}}_syncToTextboxCondition(t,r,{phase:n}={}){return this.autocomplete==="both"||this.autocomplete==="inline"||!this.focused}_syncToTextboxMultiple(t,r=[]){if(this.requireOptionMatch){const n=t.filter(s=>!r.includes(s)),i=this.formElements.filter(s=>n.includes(s.choiceValue)).map(s=>this._getTextboxValueFromOption(s)).join(" ");this._setTextboxValue(i)}}_enhanceLightDomClasses(){const t=this.querySelector("[slot=input]");t&&t.classList.add("form-control")}__setComboboxDisabledAndReadOnly(){this._comboboxNode&&(this._comboboxNode.toggleAttribute("disabled",this.disabled),this._comboboxNode.setAttribute("aria-disabled",`${this.disabled}`),this._comboboxNode.toggleAttribute("readonly",this.readOnly),this._comboboxNode.setAttribute("aria-readonly",`${this.readOnly}`)),this._inputNode&&(this._inputNode.toggleAttribute("disabled",this.disabled),this._inputNode.toggleAttribute("readOnly",this.readOnly),this._inputNode.setAttribute("aria-readonly",`${this.readOnly}`),this._inputNode.tabIndex=this.disabled?-1:0)}__setupCombobox(){this._comboboxNode.setAttribute("role","combobox"),this._comboboxNode.setAttribute("aria-haspopup","listbox"),this._inputNode.setAttribute("aria-autocomplete",this.autocomplete),this._comboboxNode.setAttribute("aria-controls",this._listboxNode.id),this._ariaVersion==="1.1"?this._comboboxNode.setAttribute("aria-owns",this._listboxNode.id):this._inputNode.setAttribute("aria-owns",this._listboxNode.id),this._listboxNode.setAttribute("aria-labelledby",this._labelNode.id),this._inputNode.addEventListener("keydown",this._listboxOnKeyDown),this._inputNode.addEventListener("input",this._textboxOnInput),this._inputNode.addEventListener("keydown",this._textboxOnKeydown)}__teardownCombobox(){this._inputNode.removeEventListener("keydown",this._listboxOnKeyDown),this._inputNode.removeEventListener("input",this._textboxOnInput),this._inputNode.removeEventListener("keydown",this._textboxOnKeydown)}_onKeyUp(t){const r=t&&t.key;this.opened=this._showOverlayCondition({lastKey:r,currentValue:this._inputNode.value})}_textboxOnClick(t){this.opened=this._showOverlayCondition({})}clear(){this.value="",super.clear(),this.__shouldAutocompleteNextUpdate=!0}}var CV=te`
  ${Xc}

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
    ${Ip}
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
`,AV=class extends SV{static get styles(){return[...super.styles,CV]}constructor(){super(),this.defaultValidators=[]}_inputGroupInputTemplate(){return W`
      <div class="input-group__input">
        <slot name="input"></slot>
        <craft-icon
          class="indicator"
          name="chevron-down"
          style="font-size: 0.8em"
        ></craft-icon>
      </div>
    `}parser(t){return t===""?super.parser(t):t}_getTextboxValueFromOption(t){return t?t.textContent?.trim()||"":super._getTextboxValueFromOption(t)}};customElements.get("craft-combobox")||customElements.define("craft-combobox",AV);var el=class extends Ne{constructor(...t){super(...t),this.variant=Cr.Default,this.label=null}render(){return W`<span
      class="${tr({indicator:!0,"indicator--success":this.variant===Cr.Success,"indicator--danger":this.variant===Cr.Danger,"indicator--warning":this.variant===Cr.Warning,"indicator--info":this.variant===Cr.Info,"indicator--empty":this.variant==="empty"})}"
    ></span>`}};el.styles=[Vp,te`
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
    `],se([V({reflect:!0})],el.prototype,"variant",void 0),se([V()],el.prototype,"label",void 0),customElements.get("craft-indicator")||customElements.define("craft-indicator",el);var Qs=class extends Ne{constructor(){super(),this.alt=!1,this.shift=!1,this.os="Unknown",this.os=this.detectOS()}connectedCallback(){super.connectedCallback(),this.os==="Unknown"&&(this.os=this.detectOS())}detectOS(){let t=navigator.platform.toLowerCase();return t.includes("mac")||/iphone|ipad|ipod/.test(t)?"Mac":t.includes("win")?"Windows":t.includes("linux")?"Linux":"Unknown"}renderShortcutPrefix(){switch(this.os){case"Mac":return`${this.alt?"⌥":""}${this.shift?"⇧":""}⌘`;case"Linux":return`Super+${this.alt?"Alt+":""}${this.shift?"Shift+":""}`;default:return`Ctrl+${this.alt?"Alt+":""}${this.shift?"Shift+":""}`}}render(){return W`<span class="shortcut"
      >${this.renderShortcutPrefix()}<slot></slot
    ></span>`}};Qs.styles=te`
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
  `,se([V({type:Boolean})],Qs.prototype,"alt",void 0),se([V({type:Boolean})],Qs.prototype,"shift",void 0),se([V()],Qs.prototype,"os",void 0),customElements.get("craft-shortcut")||customElements.define("craft-shortcut",Qs);class TV extends eu(xE(Ne)){connectedCallback(){super.connectedCallback(),this.setAttribute("role","radiogroup")}resetGroup(){let t;this.formElements.forEach(r=>{typeof r.resetGroup=="function"?r.resetGroup():typeof r.reset=="function"&&(r.reset(),r.checked&&(t=r.choiceValue))}),this.modelValue=t,this.resetInteractionState()}}class kV extends tu(ru){connectedCallback(){super.connectedCallback(),this.type="radio"}}var OV=class extends TV{static get styles(){return[...super.styles,wa,te`
        .input-group {
          display: grid;
          gap: var(--c-spacing-xs);
        }
      `]}};customElements.get("craft-radio-group")||customElements.define("craft-radio-group",OV);var NV=class extends kV{static get styles(){return[...super.styles,te`
        /* same as checkbox, potentially consolidate */
        :host {
          gap: var(--c-spacing-sm);
        }
      `]}};customElements.get("craft-radio")||customElements.define("craft-radio",NV);var PV=class{constructor(){this.refreshPromise=null,this.tokenName=null,this.tokenValue=null,this.refreshPromise=null}async getToken(){return this.tokenValue||await this.refreshToken(),this.tokenValue}async refreshToken(){return this.refreshPromise||(this.refreshPromise=Jo.get("users/session-info").then(({data:e})=>{let{csrfTokenName:t,csrfTokenValue:r}=e;return this.tokenName=t??null,this.tokenValue=r??null,this.tokenValue}).finally(()=>{this.refreshPromise=null})),this.refreshPromise}clearToken(){this.tokenValue=null}};function FV(e=""){return`https://craft6-dev.ddev.site/admin/actions/${e}`}function IV(){let e={"X-Registered-Asset-Bundles":[...new Set(Craft.registeredAssetBundles)].join(","),"X-Registered-Js-Files":[...new Set(Craft.registeredJsFiles)].join(",")};return Craft.csrfTokenValue&&(e["X-CSRF-Token"]=Craft.csrfTokenValue),e}const Jo=ls.create({baseURL:FV()}),Xd=new PV;Jo.interceptors.request.use(async e=>{e.headers.set("X-Requested-With","XMLHttpRequest");let t=IV();if(Object.entries(t).forEach(([r,n])=>{e.headers.set(r,n)}),["post","put","patch","delete"].includes(e.method?.toLowerCase()||"")&&!e.url?.includes("users/session-info")){let r=await Xd.getToken();r&&e.headers.set("X-CSRF-Token",r)}return e}),Jo.interceptors.response.use(e=>e,async e=>{let t=e.config;if(e.response?.status===419||e.response?.status===403&&!t._retry){t._retry=!0;try{return Xd.clearToken(),t.headers["X-CSRF-Token"]=await Xd.refreshToken(),ls(t)}catch(r){return console.error("Failed to refresh CSRF token:",r),Promise.reject(r)}}return Promise.reject(e)});let vl=!1,Ti=null;async function LV(e){if(!vl){if(Ti)return Ti;vl=!0;try{return(await Jo.post("app/api-headers",void 0,{cancelToken:e})).data}catch{}finally{vl=!1}}}const Jd=ls.create({baseURL:"https://api.craftcms.com/v1/"});async function RV(e){return Ti?Object.entries(Ti).forEach(([t,r])=>{e.headers.set(t,r)}):(e.params=e.params||{},e.params.processCraftHeaders=1),e}async function $V(e,t){if(Ti)return;let{data:r}=await Jo.post("app/process-api-response-headers",{headers:e},{cancelToken:t});return Ti=r,vl=!1,Ti}async function MV(e){return await $V(e.headers,e.config.cancelToken),e}Jd.interceptors.request.use(async e=>{let{cancelToken:t}=e,r=await LV(t);r&&Object.entries(r).forEach(([i,s])=>{e.headers.set(i,s)});let n={...e,params:{...Craft.apiParams||{},...e.params,v:new Date().getTime()}};return r||(n.params.processCraftHeaders=1),Craft.httpProxy&&(n.proxy=Craft.httpProxy),n}),Jd.interceptors.request.use(RV),Jd.interceptors.response.use(MV);TL({resolve:e=>Ak(`./pages/${e}.vue`,Object.assign({"./pages/Install.vue":()=>Y(()=>import("./Install.js"),__vite__mapDeps([66,67,68,69,70,71,72,73,74,75,76,77,78,79]),import.meta.url),"./pages/SettingsGeneralPage.vue":()=>Y(()=>import("./SettingsGeneralPage.js"),__vite__mapDeps([80,67,68,69,70,71,72,73,81,82,83,84,85,74,77,78,86]),import.meta.url),"./pages/SettingsIndexPage.vue":()=>Y(()=>import("./SettingsIndexPage.js"),__vite__mapDeps([87,67,68,69,70,71,72,73,81,82,74,77,78,88]),import.meta.url),"./pages/SettingsSitesEdit.vue":()=>Y(()=>import("./SettingsSitesEdit.js"),__vite__mapDeps([89,81,67,68,69,70,71,72,73,82,84,85,90,75,76,83,91,74,77,78]),import.meta.url),"./pages/SettingsSitesIndex.vue":()=>Y(()=>import("./SettingsSitesIndex.js"),__vite__mapDeps([92,67,68,69,70,71,72,73,81,82,90,75,76,83,91,74,77,78,93]),import.meta.url)})),setup({el:e,App:t,props:r,plugin:n}){Ml({render:()=>Wr(t,r)}).use(n).mount(e)}});export{et as $,Cx as A,df as B,M1 as C,Ns as D,dS as E,st as F,Jx as G,l_ as H,pS as I,$C as J,SS as K,ta as L,ra as M,Ts as N,XV as O,YV as P,JV as Q,$1 as R,h1 as S,PC as T,sa as U,Px as V,Xr as W,Wr as X,my as Y,Ec as Z,ye as _,kf as a,Ox as a0,eS as a1,hx as a2,a_ as a3,at as a4,YS as b,Nl as c,Mi as d,jS as e,Zx as f,Of as g,Fx as h,Ei as i,De as j,xx as k,ze as l,GV as m,q0 as n,Mo as o,Hr as p,cA as q,tS as r,zt as s,ly as t,ea as u,$l as v,gf as w,Sx as x,Rv as y,po as z};
