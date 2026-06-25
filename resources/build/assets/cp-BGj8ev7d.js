const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["./bg-BG-CUtyclK6.js","./bg-Chst73Jv.js","./bg-DLFHZPGS.js","./cs-CZ-BPXuRBmR.js","./cs-B5OXtETZ.js","./cs-B0MDOmrH.js","./de-DE-f3atla9t.js","./de-tkyyOjgk.js","./de-BOjktHME.js","./en-AU-CqsU-pto.js","./en-Bsd-2jFz.js","./en-GB-B_evmll_.js","./en-US-DWNMkAIW.js","./en-WrhTbaWQ.js","./es-ES-uk_vgP_u.js","./es-CDwmzO6U.js","./es-CRrZWrYq.js","./fr-FR-BFuXT0QG.js","./fr-ByyFPvdM.js","./fr-BE-iinjvp9X.js","./fr-DBZDLfez.js","./hu-HU-DjIBc18o.js","./hu-C6_kYYxd.js","./hu-CCfsXRXO.js","./it-IT-C9isceCq.js","./it-Kx0IYV8j.js","./it-D5-Wj26w.js","./nl-BE-fuc5y42r.js","./nl-BvaUo3ia.js","./nl-NL-BHde5wmq.js","./nl-AuR6boED.js","./pl-PL-SKj89Ze8.js","./pl-CmDh_SW7.js","./pl-BvPycIUK.js","./ro-RO-BBPvRREA.js","./ro-CcN6KfqE.js","./ro-DHvdrytz.js","./ru-RU-Cqbzf1DP.js","./ru-CNqhiMbt.js","./ru-DHPAL3Qd.js","./sk-SK-CVB1omfc.js","./sk-BdBYXzL3.js","./sk-CqwbQKQ8.js","./tr-TR-YY3D0g62.js","./tr-DsrcJ5Tl.js","./tr-BM9B7JDW.js","./uk-UA-A4zQpn9J.js","./uk-B2pXdq4G.js","./uk-Chd3A0Z6.js","./bg-BG-CObybLdG.js","./bg-CA_DEhmN.js","./bg-wQv8tmsc.js","./cs-CZ-BwIkezDW.js","./cs-C520O6W-.js","./cs-5tTjsUL1.js","./de-DE-LaNdL5bB.js","./de-Csjtm1lN.js","./de-DYEGLgmt.js","./en-AU-BiyNSWc6.js","./en-hkXKdxwz.js","./en-GB-CZGY43uJ.js","./en-US-CSlu5dxx.js","./en-DiHy43zH.js","./es-ES-jPlhVXZA.js","./es-DnN8su-L.js","./es-DIo-tXtk.js","./fr-FR-D9Wr5IMM.js","./fr-6RiqQl_g.js","./fr-BE-B1Z-P9dE.js","./fr-KNNuvZkx.js","./hu-HU-DoQBKuAX.js","./hu-DKdRCuPW.js","./hu-DOpZOk-1.js","./id-ID-7Sxz33aH.js","./id-DgmadLJZ.js","./id-vPvEL-pw.js","./it-IT-JQQdrN0z.js","./it-D_hx1WH1.js","./it-D9PmN7Mv.js","./nl-BE-C_nEmOBa.js","./nl-DaffLKe7.js","./nl-NL-9CtxRMFG.js","./nl-CSbWBoPS.js","./pl-PL-BQMQAmiK.js","./pl-B0Ll0czk.js","./pl-BcSXeD0R.js","./ro-RO-CLWtFGEK.js","./ro-ljbfDqce.js","./ro-tPVWc-9n.js","./ru-RU-OpGtkoCc.js","./ru-BKL-WcKh.js","./ru-B5TFsE0s.js","./sk-SK-H-rStg-J.js","./sk-V5tdfCdA.js","./sk-C39Ho2ZP.js","./uk-UA-CMqSolUP.js","./uk-BpuooMAm.js","./uk-BlyCX4EQ.js"])))=>i.map(i=>d[i]);
import{a as e,i as t,l as n,n as r,o as i,r as a,s as o}from"./nav-item-DFZbQ-Jw-CtSAdykQ.js";import{t as s}from"./preload-helper-I1wKxk_B.js";import{a as c,c as l,d as u,f as d,i as f,n as p,r as m,t as h}from"./lit-BpPOIUnZ.js";import{a as g,i as _,o as v,r as y,t as b}from"./decorators-BOwDFZC2.js";import"./nav-list.ts-DjsniC4m.js";var ee=Object.defineProperty,te=(e,t)=>()=>(t||(e((t={exports:{}}).exports,t),e=null),t.exports),ne=(e,t)=>{let n={};for(var r in e)ee(n,r,{get:e[r],enumerable:!0});return t||ee(n,Symbol.toStringTag,{value:`Module`}),n},x={Pending:1,Reserved:2,Done:3,Failed:4,Delayed:5,Cancelled:6},S={Idle:`idle`,Loading:`loading`,Success:`success`,Error:`error`};function C(e,t){if(t.has(e))throw TypeError(`Cannot initialize the same private elements twice on an object`)}function w(e,t,n){C(e,t),t.set(e,n)}function T(e,t,n){if(typeof e==`function`?e===t:e.has(t))return arguments.length<3?t:n;throw TypeError(`Private element is not present on this object`)}function E(e,t,n){return e.set(T(e,t),n),n}function D(e,t){return e.get(T(e,t))}function re(e,t){C(e,t),t.add(e)}var O=new WeakMap,ie=new WeakSet,ae=class e{constructor(){re(this,ie),w(this,O,new Map)}static getInstance(t={}){return se._||=new e,se._}static resetInstance(){se._&&=null}initialize(e={}){E(O,this,new Map(Object.entries(e)))}getCpUrl(e){return T(ie,this,oe).call(this,D(O,this).get(`baseCpUrl`),e)}getActionUrl(e){return T(ie,this,oe).call(this,D(O,this).get(`actionUrl`),e)}all(){return D(O,this)}set(e,t){D(O,this).set(e,t)}get(e,t=null){return D(O,this).has(e)?D(O,this).get(e):t}};function oe(e,t){let n=new URL(e),r=t.startsWith(`/`)?t.slice(1):t;return n.pathname=`${n.pathname}/${r}`,n.toString()}var se={_:null};function ce(e,t){return function(){return e.apply(t,arguments)}}var{toString:le}=Object.prototype,{getPrototypeOf:ue}=Object,{iterator:de,toStringTag:fe}=Symbol,pe=(e=>t=>{let n=le.call(t);return e[n]||(e[n]=n.slice(8,-1).toLowerCase())})(Object.create(null)),me=e=>(e=e.toLowerCase(),t=>pe(t)===e),he=e=>t=>typeof t===e,{isArray:ge}=Array,_e=he(`undefined`);function ve(e){return e!==null&&!_e(e)&&e.constructor!==null&&!_e(e.constructor)&&Se(e.constructor.isBuffer)&&e.constructor.isBuffer(e)}var ye=me(`ArrayBuffer`);function be(e){let t;return t=typeof ArrayBuffer<`u`&&ArrayBuffer.isView?ArrayBuffer.isView(e):e&&e.buffer&&ye(e.buffer),t}var xe=he(`string`),Se=he(`function`),Ce=he(`number`),we=e=>typeof e==`object`&&!!e,Te=e=>e===!0||e===!1,Ee=e=>{if(pe(e)!==`object`)return!1;let t=ue(e);return(t===null||t===Object.prototype||Object.getPrototypeOf(t)===null)&&!(fe in e)&&!(de in e)},De=e=>{if(!we(e)||ve(e))return!1;try{return Object.keys(e).length===0&&Object.getPrototypeOf(e)===Object.prototype}catch{return!1}},Oe=me(`Date`),ke=me(`File`),Ae=e=>!!(e&&e.uri!==void 0),je=e=>e&&e.getParts!==void 0,Me=me(`Blob`),Ne=me(`FileList`),Pe=e=>we(e)&&Se(e.pipe);function Fe(){return typeof globalThis<`u`?globalThis:typeof self<`u`?self:typeof window<`u`?window:typeof global<`u`?global:{}}var Ie=Fe(),Le=Ie.FormData===void 0?void 0:Ie.FormData,Re=e=>{if(!e)return!1;if(Le&&e instanceof Le)return!0;let t=ue(e);if(!t||t===Object.prototype||!Se(e.append))return!1;let n=pe(e);return n===`formdata`||n===`object`&&Se(e.toString)&&e.toString()===`[object FormData]`},ze=me(`URLSearchParams`),[Be,Ve,He,Ue]=[`ReadableStream`,`Request`,`Response`,`Headers`].map(me),We=e=>e.trim?e.trim():e.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g,``);function Ge(e,t,{allOwnKeys:n=!1}={}){if(e==null)return;let r,i;if(typeof e!=`object`&&(e=[e]),ge(e))for(r=0,i=e.length;r<i;r++)t.call(null,e[r],r,e);else{if(ve(e))return;let i=n?Object.getOwnPropertyNames(e):Object.keys(e),a=i.length,o;for(r=0;r<a;r++)o=i[r],t.call(null,e[o],o,e)}}function Ke(e,t){if(ve(e))return null;t=t.toLowerCase();let n=Object.keys(e),r=n.length,i;for(;r-- >0;)if(i=n[r],t===i.toLowerCase())return i;return null}var qe=typeof globalThis<`u`?globalThis:typeof self<`u`?self:typeof window<`u`?window:global,Je=e=>!_e(e)&&e!==qe;function Ye(...e){let{caseless:t,skipUndefined:n}=Je(this)&&this||{},r={},i=(e,i)=>{if(i===`__proto__`||i===`constructor`||i===`prototype`)return;let a=t&&typeof i==`string`&&Ke(r,i)||i,o=st(r,a)?r[a]:void 0;Ee(o)&&Ee(e)?r[a]=Ye(o,e):Ee(e)?r[a]=Ye({},e):ge(e)?r[a]=e.slice():(!n||!_e(e))&&(r[a]=e)};for(let t=0,n=e.length;t<n;t++){let n=e[t];if(!n||ve(n)||(Ge(n,i),typeof n!=`object`||ge(n)))continue;let r=Object.getOwnPropertySymbols(n);for(let e=0;e<r.length;e++){let t=r[e];ct.call(n,t)&&i(n[t],t)}}return r}var Xe=(e,t,n,{allOwnKeys:r}={})=>(Ge(t,(t,r)=>{n&&Se(t)?Object.defineProperty(e,r,{__proto__:null,value:ce(t,n),writable:!0,enumerable:!0,configurable:!0}):Object.defineProperty(e,r,{__proto__:null,value:t,writable:!0,enumerable:!0,configurable:!0})},{allOwnKeys:r}),e),Ze=e=>(e.charCodeAt(0)===65279&&(e=e.slice(1)),e),Qe=(e,t,n,r)=>{e.prototype=Object.create(t.prototype,r),Object.defineProperty(e.prototype,`constructor`,{__proto__:null,value:e,writable:!0,enumerable:!1,configurable:!0}),Object.defineProperty(e,`super`,{__proto__:null,value:t.prototype}),n&&Object.assign(e.prototype,n)},$e=(e,t,n,r)=>{let i,a,o,s={};if(t||={},e==null)return t;do{for(i=Object.getOwnPropertyNames(e),a=i.length;a-- >0;)o=i[a],(!r||r(o,e,t))&&!s[o]&&(t[o]=e[o],s[o]=!0);e=n!==!1&&ue(e)}while(e&&(!n||n(e,t))&&e!==Object.prototype);return t},et=(e,t,n)=>{e=String(e),(n===void 0||n>e.length)&&(n=e.length),n-=t.length;let r=e.indexOf(t,n);return r!==-1&&r===n},tt=e=>{if(!e)return null;if(ge(e))return e;let t=e.length;if(!Ce(t))return null;let n=Array(t);for(;t-- >0;)n[t]=e[t];return n},nt=(e=>t=>e&&t instanceof e)(typeof Uint8Array<`u`&&ue(Uint8Array)),rt=(e,t)=>{let n=(e&&e[de]).call(e),r;for(;(r=n.next())&&!r.done;){let n=r.value;t.call(e,n[0],n[1])}},it=(e,t)=>{let n,r=[];for(;(n=e.exec(t))!==null;)r.push(n);return r},at=me(`HTMLFormElement`),ot=e=>e.toLowerCase().replace(/[-_\s]([a-z\d])(\w*)/g,function(e,t,n){return t.toUpperCase()+n}),st=(({hasOwnProperty:e})=>(t,n)=>e.call(t,n))(Object.prototype),{propertyIsEnumerable:ct}=Object.prototype,lt=me(`RegExp`),ut=(e,t)=>{let n=Object.getOwnPropertyDescriptors(e),r={};Ge(n,(n,i)=>{let a;(a=t(n,i,e))!==!1&&(r[i]=a||n)}),Object.defineProperties(e,r)},dt=e=>{ut(e,(t,n)=>{if(Se(e)&&[`arguments`,`caller`,`callee`].includes(n))return!1;let r=e[n];if(Se(r)){if(t.enumerable=!1,`writable`in t){t.writable=!1;return}t.set||=()=>{throw Error(`Can not rewrite read-only method '`+n+`'`)}}})},ft=(e,t)=>{let n={},r=e=>{e.forEach(e=>{n[e]=!0})};return ge(e)?r(e):r(String(e).split(t)),n},pt=()=>{},mt=(e,t)=>e!=null&&Number.isFinite(e=+e)?e:t;function ht(e){return!!(e&&Se(e.append)&&e[fe]===`FormData`&&e[de])}var gt=e=>{let t=new WeakSet,n=e=>{if(we(e)){if(t.has(e))return;if(ve(e))return e;if(!(`toJSON`in e)){t.add(e);let r=ge(e)?[]:{};return Ge(e,(e,t)=>{let i=n(e);!_e(i)&&(r[t]=i)}),t.delete(e),r}}return e};return n(e)},_t=me(`AsyncFunction`),vt=e=>e&&(we(e)||Se(e))&&Se(e.then)&&Se(e.catch),yt=((e,t)=>e?setImmediate:t?((e,t)=>(qe.addEventListener(`message`,({source:n,data:r})=>{n===qe&&r===e&&t.length&&t.shift()()},!1),n=>{t.push(n),qe.postMessage(e,`*`)}))(`axios@${Math.random()}`,[]):e=>setTimeout(e))(typeof setImmediate==`function`,Se(qe.postMessage)),k={isArray:ge,isArrayBuffer:ye,isBuffer:ve,isFormData:Re,isArrayBufferView:be,isString:xe,isNumber:Ce,isBoolean:Te,isObject:we,isPlainObject:Ee,isEmptyObject:De,isReadableStream:Be,isRequest:Ve,isResponse:He,isHeaders:Ue,isUndefined:_e,isDate:Oe,isFile:ke,isReactNativeBlob:Ae,isReactNative:je,isBlob:Me,isRegExp:lt,isFunction:Se,isStream:Pe,isURLSearchParams:ze,isTypedArray:nt,isFileList:Ne,forEach:Ge,merge:Ye,extend:Xe,trim:We,stripBOM:Ze,inherits:Qe,toFlatObject:$e,kindOf:pe,kindOfTest:me,endsWith:et,toArray:tt,forEachEntry:rt,matchAll:it,isHTMLForm:at,hasOwnProperty:st,hasOwnProp:st,reduceDescriptors:ut,freezeMethods:dt,toObjectSet:ft,toCamelCase:ot,noop:pt,toFiniteNumber:mt,findKey:Ke,global:qe,isContextDefined:Je,isSpecCompliantForm:ht,toJSONObject:gt,isAsyncFn:_t,isThenable:vt,setImmediate:yt,asap:typeof queueMicrotask<`u`?queueMicrotask.bind(qe):typeof process<`u`&&process.nextTick||yt,isIterable:e=>e!=null&&Se(e[de])},bt=k.toObjectSet([`age`,`authorization`,`content-length`,`content-type`,`etag`,`expires`,`from`,`host`,`if-modified-since`,`if-unmodified-since`,`last-modified`,`location`,`max-forwards`,`proxy-authorization`,`referer`,`retry-after`,`user-agent`]),xt=e=>{let t={},n,r,i;return e&&e.split(`
`).forEach(function(e){i=e.indexOf(`:`),n=e.substring(0,i).trim().toLowerCase(),r=e.substring(i+1).trim(),!(!n||t[n]&&bt[n])&&(n===`set-cookie`?t[n]?t[n].push(r):t[n]=[r]:t[n]=t[n]?t[n]+`, `+r:r)}),t};function St(e){let t=0,n=e.length;for(;t<n;){let n=e.charCodeAt(t);if(n!==9&&n!==32)break;t+=1}for(;n>t;){let t=e.charCodeAt(n-1);if(t!==9&&t!==32)break;--n}return t===0&&n===e.length?e:e.slice(t,n)}var Ct=RegExp(`[\\u0000-\\u0008\\u000a-\\u001f\\u007f]+`,`g`),wt=RegExp(`[^\\u0009\\u0020-\\u007e\\u0080-\\u00ff]+`,`g`);function Tt(e,t){return k.isArray(e)?e.map(e=>Tt(e,t)):St(String(e).replace(t,``))}var Et=e=>Tt(e,Ct),Dt=e=>Tt(e,wt);function Ot(e){let t=Object.create(null);return k.forEach(e.toJSON(),(e,n)=>{t[n]=Dt(e)}),t}var kt=Symbol(`internals`);function At(e){return e&&String(e).trim().toLowerCase()}function jt(e){return e===!1||e==null?e:k.isArray(e)?e.map(jt):Et(String(e))}function Mt(e){let t=Object.create(null),n=/([^\s,;=]+)\s*(?:=\s*([^,;]+))?/g,r;for(;r=n.exec(e);)t[r[1]]=r[2];return t}var Nt=e=>/^[-_a-zA-Z0-9^`|~,!#$%&'*+.]+$/.test(e.trim());function Pt(e,t,n,r,i){if(k.isFunction(r))return r.call(this,t,n);if(i&&(t=n),k.isString(t)){if(k.isString(r))return t.indexOf(r)!==-1;if(k.isRegExp(r))return r.test(t)}}function Ft(e){return e.trim().toLowerCase().replace(/([a-z\d])(\w*)/g,(e,t,n)=>t.toUpperCase()+n)}function It(e,t){let n=k.toCamelCase(` `+t);[`get`,`set`,`has`].forEach(r=>{Object.defineProperty(e,r+n,{__proto__:null,value:function(e,n,i){return this[r].call(this,t,e,n,i)},configurable:!0})})}var Lt=class{constructor(e){e&&this.set(e)}set(e,t,n){let r=this;function i(e,t,n){let i=At(t);if(!i)return;let a=k.findKey(r,i);(!a||r[a]===void 0||n===!0||n===void 0&&r[a]!==!1)&&(r[a||t]=jt(e))}let a=(e,t)=>k.forEach(e,(e,n)=>i(e,n,t));if(k.isPlainObject(e)||e instanceof this.constructor)a(e,t);else if(k.isString(e)&&(e=e.trim())&&!Nt(e))a(xt(e),t);else if(k.isObject(e)&&k.isIterable(e)){let n={},r,i;for(let t of e){if(!k.isArray(t))throw TypeError(`Object iterator must return a key-value pair`);n[i=t[0]]=(r=n[i])?k.isArray(r)?[...r,t[1]]:[r,t[1]]:t[1]}a(n,t)}else e!=null&&i(t,e,n);return this}get(e,t){if(e=At(e),e){let n=k.findKey(this,e);if(n){let e=this[n];if(!t)return e;if(t===!0)return Mt(e);if(k.isFunction(t))return t.call(this,e,n);if(k.isRegExp(t))return t.exec(e);throw TypeError(`parser must be boolean|regexp|function`)}}}has(e,t){if(e=At(e),e){let n=k.findKey(this,e);return!!(n&&this[n]!==void 0&&(!t||Pt(this,this[n],n,t)))}return!1}delete(e,t){let n=this,r=!1;function i(e){if(e=At(e),e){let i=k.findKey(n,e);i&&(!t||Pt(n,n[i],i,t))&&(delete n[i],r=!0)}}return k.isArray(e)?e.forEach(i):i(e),r}clear(e){let t=Object.keys(this),n=t.length,r=!1;for(;n--;){let i=t[n];(!e||Pt(this,this[i],i,e,!0))&&(delete this[i],r=!0)}return r}normalize(e){let t=this,n={};return k.forEach(this,(r,i)=>{let a=k.findKey(n,i);if(a){t[a]=jt(r),delete t[i];return}let o=e?Ft(i):String(i).trim();o!==i&&delete t[i],t[o]=jt(r),n[o]=!0}),this}concat(...e){return this.constructor.concat(this,...e)}toJSON(e){let t=Object.create(null);return k.forEach(this,(n,r)=>{n!=null&&n!==!1&&(t[r]=e&&k.isArray(n)?n.join(`, `):n)}),t}[Symbol.iterator](){return Object.entries(this.toJSON())[Symbol.iterator]()}toString(){return Object.entries(this.toJSON()).map(([e,t])=>e+`: `+t).join(`
`)}getSetCookie(){return this.get(`set-cookie`)||[]}get[Symbol.toStringTag](){return`AxiosHeaders`}static from(e){return e instanceof this?e:new this(e)}static concat(e,...t){let n=new this(e);return t.forEach(e=>n.set(e)),n}static accessor(e){let t=(this[kt]=this[kt]={accessors:{}}).accessors,n=this.prototype;function r(e){let r=At(e);t[r]||(It(n,e),t[r]=!0)}return k.isArray(e)?e.forEach(r):r(e),this}};Lt.accessor([`Content-Type`,`Content-Length`,`Accept`,`Accept-Encoding`,`User-Agent`,`Authorization`]),k.reduceDescriptors(Lt.prototype,({value:e},t)=>{let n=t[0].toUpperCase()+t.slice(1);return{get:()=>e,set(e){this[n]=e}}}),k.freezeMethods(Lt);var Rt=`[REDACTED ****]`;function zt(e){if(k.hasOwnProp(e,`toJSON`))return!0;let t=Object.getPrototypeOf(e);for(;t&&t!==Object.prototype;){if(k.hasOwnProp(t,`toJSON`))return!0;t=Object.getPrototypeOf(t)}return!1}function Bt(e,t){let n=new Set(t.map(e=>String(e).toLowerCase())),r=[],i=e=>{if(typeof e!=`object`||!e||k.isBuffer(e))return e;if(r.indexOf(e)!==-1)return;e instanceof Lt&&(e=e.toJSON()),r.push(e);let t;if(k.isArray(e))t=[],e.forEach((e,n)=>{let r=i(e);k.isUndefined(r)||(t[n]=r)});else{if(!k.isPlainObject(e)&&zt(e))return r.pop(),e;t=Object.create(null);for(let[r,a]of Object.entries(e)){let e=n.has(r.toLowerCase())?Rt:i(a);k.isUndefined(e)||(t[r]=e)}}return r.pop(),t};return i(e)}var A=class e extends Error{static from(t,n,r,i,a,o){let s=new e(t.message,n||t.code,r,i,a);return s.cause=t,s.name=t.name,t.status!=null&&s.status==null&&(s.status=t.status),o&&Object.assign(s,o),s}constructor(e,t,n,r,i){super(e),Object.defineProperty(this,`message`,{__proto__:null,value:e,enumerable:!0,writable:!0,configurable:!0}),this.name=`AxiosError`,this.isAxiosError=!0,t&&(this.code=t),n&&(this.config=n),r&&(this.request=r),i&&(this.response=i,this.status=i.status)}toJSON(){let e=this.config,t=e&&k.hasOwnProp(e,`redact`)?e.redact:void 0,n=k.isArray(t)&&t.length>0?Bt(e,t):k.toJSONObject(e);return{message:this.message,name:this.name,description:this.description,number:this.number,fileName:this.fileName,lineNumber:this.lineNumber,columnNumber:this.columnNumber,stack:this.stack,config:n,code:this.code,status:this.status}}};A.ERR_BAD_OPTION_VALUE=`ERR_BAD_OPTION_VALUE`,A.ERR_BAD_OPTION=`ERR_BAD_OPTION`,A.ECONNABORTED=`ECONNABORTED`,A.ETIMEDOUT=`ETIMEDOUT`,A.ECONNREFUSED=`ECONNREFUSED`,A.ERR_NETWORK=`ERR_NETWORK`,A.ERR_FR_TOO_MANY_REDIRECTS=`ERR_FR_TOO_MANY_REDIRECTS`,A.ERR_DEPRECATED=`ERR_DEPRECATED`,A.ERR_BAD_RESPONSE=`ERR_BAD_RESPONSE`,A.ERR_BAD_REQUEST=`ERR_BAD_REQUEST`,A.ERR_CANCELED=`ERR_CANCELED`,A.ERR_NOT_SUPPORT=`ERR_NOT_SUPPORT`,A.ERR_INVALID_URL=`ERR_INVALID_URL`,A.ERR_FORM_DATA_DEPTH_EXCEEDED=`ERR_FORM_DATA_DEPTH_EXCEEDED`;function Vt(e){return k.isPlainObject(e)||k.isArray(e)}function Ht(e){return k.endsWith(e,`[]`)?e.slice(0,-2):e}function Ut(e,t,n){return e?e.concat(t).map(function(e,t){return e=Ht(e),!n&&t?`[`+e+`]`:e}).join(n?`.`:``):t}function Wt(e){return k.isArray(e)&&!e.some(Vt)}var Gt=k.toFlatObject(k,{},null,function(e){return/^is[A-Z]/.test(e)});function Kt(e,t,n){if(!k.isObject(e))throw TypeError(`target must be an object`);t||=new FormData,n=k.toFlatObject(n,{metaTokens:!0,dots:!1,indexes:!1},!1,function(e,t){return!k.isUndefined(t[e])});let r=n.metaTokens,i=n.visitor||d,a=n.dots,o=n.indexes,s=n.Blob||typeof Blob<`u`&&Blob,c=n.maxDepth===void 0?100:n.maxDepth,l=s&&k.isSpecCompliantForm(t);if(!k.isFunction(i))throw TypeError(`visitor must be a function`);function u(e){if(e===null)return``;if(k.isDate(e))return e.toISOString();if(k.isBoolean(e))return e.toString();if(!l&&k.isBlob(e))throw new A(`Blob is not supported. Use a Buffer instead.`);return k.isArrayBuffer(e)||k.isTypedArray(e)?l&&typeof Blob==`function`?new Blob([e]):Buffer.from(e):e}function d(e,n,i){let s=e;if(k.isReactNative(t)&&k.isReactNativeBlob(e))return t.append(Ut(i,n,a),u(e)),!1;if(e&&!i&&typeof e==`object`){if(k.endsWith(n,`{}`))n=r?n:n.slice(0,-2),e=JSON.stringify(e);else if(k.isArray(e)&&Wt(e)||(k.isFileList(e)||k.endsWith(n,`[]`))&&(s=k.toArray(e)))return n=Ht(n),s.forEach(function(e,r){!(k.isUndefined(e)||e===null)&&t.append(o===!0?Ut([n],r,a):o===null?n:n+`[]`,u(e))}),!1}return Vt(e)?!0:(t.append(Ut(i,n,a),u(e)),!1)}let f=[],p=Object.assign(Gt,{defaultVisitor:d,convertValue:u,isVisitable:Vt});function m(e,n,r=0){if(!k.isUndefined(e)){if(r>c)throw new A(`Object is too deeply nested (`+r+` levels). Max depth: `+c,A.ERR_FORM_DATA_DEPTH_EXCEEDED);if(f.indexOf(e)!==-1)throw Error(`Circular reference detected in `+n.join(`.`));f.push(e),k.forEach(e,function(e,a){(!(k.isUndefined(e)||e===null)&&i.call(t,e,k.isString(a)?a.trim():a,n,p))===!0&&m(e,n?n.concat(a):[a],r+1)}),f.pop()}}if(!k.isObject(e))throw TypeError(`data must be an object`);return m(e),t}function qt(e){let t={"!":`%21`,"'":`%27`,"(":`%28`,")":`%29`,"~":`%7E`,"%20":`+`};return encodeURIComponent(e).replace(/[!'()~]|%20/g,function(e){return t[e]})}function Jt(e,t){this._pairs=[],e&&Kt(e,this,t)}var Yt=Jt.prototype;Yt.append=function(e,t){this._pairs.push([e,t])},Yt.toString=function(e){let t=e?function(t){return e.call(this,t,qt)}:qt;return this._pairs.map(function(e){return t(e[0])+`=`+t(e[1])},``).join(`&`)};function Xt(e){return encodeURIComponent(e).replace(/%3A/gi,`:`).replace(/%24/g,`$`).replace(/%2C/gi,`,`).replace(/%20/g,`+`)}function Zt(e,t,n){if(!t)return e;let r=n&&n.encode||Xt,i=k.isFunction(n)?{serialize:n}:n,a=i&&i.serialize,o;if(o=a?a(t,i):k.isURLSearchParams(t)?t.toString():new Jt(t,i).toString(r),o){let t=e.indexOf(`#`);t!==-1&&(e=e.slice(0,t)),e+=(e.indexOf(`?`)===-1?`?`:`&`)+o}return e}var Qt=class{constructor(){this.handlers=[]}use(e,t,n){return this.handlers.push({fulfilled:e,rejected:t,synchronous:n?n.synchronous:!1,runWhen:n?n.runWhen:null}),this.handlers.length-1}eject(e){this.handlers[e]&&(this.handlers[e]=null)}clear(){this.handlers&&=[]}forEach(e){k.forEach(this.handlers,function(t){t!==null&&e(t)})}},$t={silentJSONParsing:!0,forcedJSONParsing:!0,clarifyTimeoutError:!1,legacyInterceptorReqResOrdering:!0,advertiseZstdAcceptEncoding:!1},en={isBrowser:!0,classes:{URLSearchParams:typeof URLSearchParams<`u`?URLSearchParams:Jt,FormData:typeof FormData<`u`?FormData:null,Blob:typeof Blob<`u`?Blob:null},protocols:[`http`,`https`,`file`,`blob`,`url`,`data`]},tn=ne({hasBrowserEnv:()=>nn,hasStandardBrowserEnv:()=>an,hasStandardBrowserWebWorkerEnv:()=>on,navigator:()=>rn,origin:()=>sn}),nn=typeof window<`u`&&typeof document<`u`,rn=typeof navigator==`object`&&navigator||void 0,an=nn&&(!rn||[`ReactNative`,`NativeScript`,`NS`].indexOf(rn.product)<0),on=typeof WorkerGlobalScope<`u`&&self instanceof WorkerGlobalScope&&typeof self.importScripts==`function`,sn=nn&&window.location.href||`http://localhost`,j={...tn,...en};function cn(e,t){return Kt(e,new j.classes.URLSearchParams,{visitor:function(e,t,n,r){return j.isNode&&k.isBuffer(e)?(this.append(t,e.toString(`base64`)),!1):r.defaultVisitor.apply(this,arguments)},...t})}function ln(e){return k.matchAll(/\w+|\[(\w*)]/g,e).map(e=>e[0]===`[]`?``:e[1]||e[0])}function un(e){let t={},n=Object.keys(e),r,i=n.length,a;for(r=0;r<i;r++)a=n[r],t[a]=e[a];return t}function dn(e){function t(e,n,r,i){let a=e[i++];if(a===`__proto__`)return!0;let o=Number.isFinite(+a),s=i>=e.length;return a=!a&&k.isArray(r)?r.length:a,s?(k.hasOwnProp(r,a)?r[a]=k.isArray(r[a])?r[a].concat(n):[r[a],n]:r[a]=n,!o):((!k.hasOwnProp(r,a)||!k.isObject(r[a]))&&(r[a]=[]),t(e,n,r[a],i)&&k.isArray(r[a])&&(r[a]=un(r[a])),!o)}if(k.isFormData(e)&&k.isFunction(e.entries)){let n={};return k.forEachEntry(e,(e,r)=>{t(ln(e),r,n,0)}),n}return null}var fn=(e,t)=>e!=null&&k.hasOwnProp(e,t)?e[t]:void 0;function pn(e,t,n){if(k.isString(e))try{return(t||JSON.parse)(e),k.trim(e)}catch(e){if(e.name!==`SyntaxError`)throw e}return(n||JSON.stringify)(e)}var mn={transitional:$t,adapter:[`xhr`,`http`,`fetch`],transformRequest:[function(e,t){let n=t.getContentType()||``,r=n.indexOf(`application/json`)>-1,i=k.isObject(e);if(i&&k.isHTMLForm(e)&&(e=new FormData(e)),k.isFormData(e))return r?JSON.stringify(dn(e)):e;if(k.isArrayBuffer(e)||k.isBuffer(e)||k.isStream(e)||k.isFile(e)||k.isBlob(e)||k.isReadableStream(e))return e;if(k.isArrayBufferView(e))return e.buffer;if(k.isURLSearchParams(e))return t.setContentType(`application/x-www-form-urlencoded;charset=utf-8`,!1),e.toString();let a;if(i){let t=fn(this,`formSerializer`);if(n.indexOf(`application/x-www-form-urlencoded`)>-1)return cn(e,t).toString();if((a=k.isFileList(e))||n.indexOf(`multipart/form-data`)>-1){let n=fn(this,`env`),r=n&&n.FormData;return Kt(a?{"files[]":e}:e,r&&new r,t)}}return i||r?(t.setContentType(`application/json`,!1),pn(e)):e}],transformResponse:[function(e){let t=fn(this,`transitional`)||mn.transitional,n=t&&t.forcedJSONParsing,r=fn(this,`responseType`),i=r===`json`;if(k.isResponse(e)||k.isReadableStream(e))return e;if(e&&k.isString(e)&&(n&&!r||i)){let n=!(t&&t.silentJSONParsing)&&i;try{return JSON.parse(e,fn(this,`parseReviver`))}catch(e){if(n)throw e.name===`SyntaxError`?A.from(e,A.ERR_BAD_RESPONSE,this,null,fn(this,`response`)):e}}return e}],timeout:0,xsrfCookieName:`XSRF-TOKEN`,xsrfHeaderName:`X-XSRF-TOKEN`,maxContentLength:-1,maxBodyLength:-1,env:{FormData:j.classes.FormData,Blob:j.classes.Blob},validateStatus:function(e){return e>=200&&e<300},headers:{common:{Accept:`application/json, text/plain, */*`,"Content-Type":void 0}}};k.forEach([`delete`,`get`,`head`,`post`,`put`,`patch`,`query`],e=>{mn.headers[e]={}});function hn(e,t){let n=this||mn,r=t||n,i=Lt.from(r.headers),a=r.data;return k.forEach(e,function(e){a=e.call(n,a,i.normalize(),t?t.status:void 0)}),i.normalize(),a}function gn(e){return!!(e&&e.__CANCEL__)}var _n=class extends A{constructor(e,t,n){super(e??`canceled`,A.ERR_CANCELED,t,n),this.name=`CanceledError`,this.__CANCEL__=!0}};function vn(e,t,n){let r=n.config.validateStatus;!n.status||!r||r(n.status)?e(n):t(new A(`Request failed with status code `+n.status,n.status>=400&&n.status<500?A.ERR_BAD_REQUEST:A.ERR_BAD_RESPONSE,n.config,n.request,n))}function yn(e){let t=/^([-+\w]{1,25}):(?:\/\/)?/.exec(e);return t&&t[1]||``}function bn(e,t){e||=10;let n=Array(e),r=Array(e),i=0,a=0,o;return t=t===void 0?1e3:t,function(s){let c=Date.now(),l=r[a];o||=c,n[i]=s,r[i]=c;let u=a,d=0;for(;u!==i;)d+=n[u++],u%=e;if(i=(i+1)%e,i===a&&(a=(a+1)%e),c-o<t)return;let f=l&&c-l;return f?Math.round(d*1e3/f):void 0}}function xn(e,t){let n=0,r=1e3/t,i,a,o=(t,r=Date.now())=>{n=r,i=null,a&&=(clearTimeout(a),null),e(...t)};return[(...e)=>{let t=Date.now(),s=t-n;s>=r?o(e,t):(i=e,a||=setTimeout(()=>{a=null,o(i)},r-s))},()=>i&&o(i)]}var Sn=(e,t,n=3)=>{let r=0,i=bn(50,250);return xn(n=>{if(!n||typeof n.loaded!=`number`)return;let a=n.loaded,o=n.lengthComputable?n.total:void 0,s=o==null?a:Math.min(a,o),c=Math.max(0,s-r),l=i(c);r=Math.max(r,s),e({loaded:s,total:o,progress:o?s/o:void 0,bytes:c,rate:l||void 0,estimated:l&&o?(o-s)/l:void 0,event:n,lengthComputable:o!=null,[t?`download`:`upload`]:!0})},n)},Cn=(e,t)=>{let n=e!=null;return[r=>t[0]({lengthComputable:n,total:e,loaded:r}),t[1]]},wn=e=>(...t)=>k.asap(()=>e(...t)),Tn=j.hasStandardBrowserEnv?((e,t)=>n=>(n=new URL(n,j.origin),e.protocol===n.protocol&&e.host===n.host&&(t||e.port===n.port)))(new URL(j.origin),j.navigator&&/(msie|trident)/i.test(j.navigator.userAgent)):()=>!0,En=j.hasStandardBrowserEnv?{write(e,t,n,r,i,a,o){if(typeof document>`u`)return;let s=[`${e}=${encodeURIComponent(t)}`];k.isNumber(n)&&s.push(`expires=${new Date(n).toUTCString()}`),k.isString(r)&&s.push(`path=${r}`),k.isString(i)&&s.push(`domain=${i}`),a===!0&&s.push(`secure`),k.isString(o)&&s.push(`SameSite=${o}`),document.cookie=s.join(`; `)},read(e){if(typeof document>`u`)return null;let t=document.cookie.split(`;`);for(let n=0;n<t.length;n++){let r=t[n].replace(/^\s+/,``),i=r.indexOf(`=`);if(i!==-1&&r.slice(0,i)===e)return decodeURIComponent(r.slice(i+1))}return null},remove(e){this.write(e,``,Date.now()-864e5,`/`)}}:{write(){},read(){return null},remove(){}};function Dn(e){return typeof e==`string`?/^([a-z][a-z\d+\-.]*:)?\/\//i.test(e):!1}function On(e,t){return t?e.replace(/\/?\/$/,``)+`/`+t.replace(/^\/+/,``):e}function kn(e,t,n){let r=!Dn(t);return e&&(r||n===!1)?On(e,t):t}var An=e=>e instanceof Lt?{...e}:e;function jn(e,t){t||={};let n=Object.create(null);Object.defineProperty(n,`hasOwnProperty`,{__proto__:null,value:Object.prototype.hasOwnProperty,enumerable:!1,writable:!0,configurable:!0});function r(e,t,n,r){return k.isPlainObject(e)&&k.isPlainObject(t)?k.merge.call({caseless:r},e,t):k.isPlainObject(t)?k.merge({},t):k.isArray(t)?t.slice():t}function i(e,t,n,i){if(!k.isUndefined(t))return r(e,t,n,i);if(!k.isUndefined(e))return r(void 0,e,n,i)}function a(e,t){if(!k.isUndefined(t))return r(void 0,t)}function o(e,t){if(!k.isUndefined(t))return r(void 0,t);if(!k.isUndefined(e))return r(void 0,e)}function s(n,i,a){if(k.hasOwnProp(t,a))return r(n,i);if(k.hasOwnProp(e,a))return r(void 0,n)}let c={url:a,method:a,data:a,baseURL:o,transformRequest:o,transformResponse:o,paramsSerializer:o,timeout:o,timeoutMessage:o,withCredentials:o,withXSRFToken:o,adapter:o,responseType:o,xsrfCookieName:o,xsrfHeaderName:o,onUploadProgress:o,onDownloadProgress:o,decompress:o,maxContentLength:o,maxBodyLength:o,beforeRedirect:o,transport:o,httpAgent:o,httpsAgent:o,cancelToken:o,socketPath:o,allowedSocketPaths:o,responseEncoding:o,validateStatus:s,headers:(e,t,n)=>i(An(e),An(t),n,!0)};return k.forEach(Object.keys({...e,...t}),function(r){if(r===`__proto__`||r===`constructor`||r===`prototype`)return;let a=k.hasOwnProp(c,r)?c[r]:i,o=a(k.hasOwnProp(e,r)?e[r]:void 0,k.hasOwnProp(t,r)?t[r]:void 0,r);k.isUndefined(o)&&a!==s||(n[r]=o)}),n}var Mn=[`content-type`,`content-length`];function Nn(e,t,n){if(n!==`content-only`){e.set(t);return}Object.entries(t).forEach(([t,n])=>{Mn.includes(t.toLowerCase())&&e.set(t,n)})}var Pn=e=>encodeURIComponent(e).replace(/%([0-9A-F]{2})/gi,(e,t)=>String.fromCharCode(parseInt(t,16)));function Fn(e){let t=jn({},e),n=e=>k.hasOwnProp(t,e)?t[e]:void 0,r=n(`data`),i=n(`withXSRFToken`),a=n(`xsrfHeaderName`),o=n(`xsrfCookieName`),s=n(`headers`),c=n(`auth`),l=n(`baseURL`),u=n(`allowAbsoluteUrls`),d=n(`url`);if(t.headers=s=Lt.from(s),t.url=Zt(kn(l,d,u),n(`params`),n(`paramsSerializer`)),c&&s.set(`Authorization`,`Basic `+btoa((c.username||``)+`:`+(c.password?Pn(c.password):``))),k.isFormData(r)&&(j.hasStandardBrowserEnv||j.hasStandardBrowserWebWorkerEnv||k.isReactNative(r)?s.setContentType(void 0):k.isFunction(r.getHeaders)&&Nn(s,r.getHeaders(),n(`formDataHeaderPolicy`))),j.hasStandardBrowserEnv&&(k.isFunction(i)&&(i=i(t)),i===!0||i==null&&Tn(t.url))){let e=a&&o&&En.read(o);e&&s.set(a,e)}return t}var In=typeof XMLHttpRequest<`u`&&function(e){return new Promise(function(t,n){let r=Fn(e),i=r.data,a=Lt.from(r.headers).normalize(),{responseType:o,onUploadProgress:s,onDownloadProgress:c}=r,l,u,d,f,p;function m(){f&&f(),p&&p(),r.cancelToken&&r.cancelToken.unsubscribe(l),r.signal&&r.signal.removeEventListener(`abort`,l)}let h=new XMLHttpRequest;h.open(r.method.toUpperCase(),r.url,!0),h.timeout=r.timeout;function g(){if(!h)return;let r=Lt.from(`getAllResponseHeaders`in h&&h.getAllResponseHeaders());vn(function(e){t(e),m()},function(e){n(e),m()},{data:!o||o===`text`||o===`json`?h.responseText:h.response,status:h.status,statusText:h.statusText,headers:r,config:e,request:h}),h=null}`onloadend`in h?h.onloadend=g:h.onreadystatechange=function(){!h||h.readyState!==4||h.status===0&&!(h.responseURL&&h.responseURL.startsWith(`file:`))||setTimeout(g)},h.onabort=function(){h&&=(n(new A(`Request aborted`,A.ECONNABORTED,e,h)),m(),null)},h.onerror=function(t){let r=new A(t&&t.message?t.message:`Network Error`,A.ERR_NETWORK,e,h);r.event=t||null,n(r),m(),h=null},h.ontimeout=function(){let t=r.timeout?`timeout of `+r.timeout+`ms exceeded`:`timeout exceeded`,i=r.transitional||$t;r.timeoutErrorMessage&&(t=r.timeoutErrorMessage),n(new A(t,i.clarifyTimeoutError?A.ETIMEDOUT:A.ECONNABORTED,e,h)),m(),h=null},i===void 0&&a.setContentType(null),`setRequestHeader`in h&&k.forEach(Ot(a),function(e,t){h.setRequestHeader(t,e)}),k.isUndefined(r.withCredentials)||(h.withCredentials=!!r.withCredentials),o&&o!==`json`&&(h.responseType=r.responseType),c&&([d,p]=Sn(c,!0),h.addEventListener(`progress`,d)),s&&h.upload&&([u,f]=Sn(s),h.upload.addEventListener(`progress`,u),h.upload.addEventListener(`loadend`,f)),(r.cancelToken||r.signal)&&(l=t=>{h&&=(n(!t||t.type?new _n(null,e,h):t),h.abort(),m(),null)},r.cancelToken&&r.cancelToken.subscribe(l),r.signal&&(r.signal.aborted?l():r.signal.addEventListener(`abort`,l)));let _=yn(r.url);if(_&&!j.protocols.includes(_)){n(new A(`Unsupported protocol `+_+`:`,A.ERR_BAD_REQUEST,e));return}h.send(i||null)})},Ln=(e,t)=>{if(e=e?e.filter(Boolean):[],!t&&!e.length)return;let n=new AbortController,r=!1,i=function(e){if(!r){r=!0,o();let t=e instanceof Error?e:this.reason;n.abort(t instanceof A?t:new _n(t instanceof Error?t.message:t))}},a=t&&setTimeout(()=>{a=null,i(new A(`timeout of ${t}ms exceeded`,A.ETIMEDOUT))},t),o=()=>{e&&=(a&&clearTimeout(a),a=null,e.forEach(e=>{e.unsubscribe?e.unsubscribe(i):e.removeEventListener(`abort`,i)}),null)};e.forEach(e=>e.addEventListener(`abort`,i));let{signal:s}=n;return s.unsubscribe=()=>k.asap(o),s},Rn=function*(e,t){let n=e.byteLength;if(!t||n<t){yield e;return}let r=0,i;for(;r<n;)i=r+t,yield e.slice(r,i),r=i},zn=async function*(e,t){for await(let n of Bn(e))yield*Rn(n,t)},Bn=async function*(e){if(e[Symbol.asyncIterator]){yield*e;return}let t=e.getReader();try{for(;;){let{done:e,value:n}=await t.read();if(e)break;yield n}}finally{await t.cancel()}},Vn=(e,t,n,r)=>{let i=zn(e,t),a=0,o,s=e=>{o||(o=!0,r&&r(e))};return new ReadableStream({async pull(e){try{let{done:t,value:r}=await i.next();if(t){s(),e.close();return}let o=r.byteLength;n&&n(a+=o),e.enqueue(new Uint8Array(r))}catch(e){throw s(e),e}},cancel(e){return s(e),i.return()}},{highWaterMark:2})};function Hn(e){if(!e||typeof e!=`string`||!e.startsWith(`data:`))return 0;let t=e.indexOf(`,`);if(t<0)return 0;let n=e.slice(5,t),r=e.slice(t+1);if(/;base64/i.test(n)){let e=r.length,t=r.length;for(let n=0;n<t;n++)if(r.charCodeAt(n)===37&&n+2<t){let t=r.charCodeAt(n+1),i=r.charCodeAt(n+2);(t>=48&&t<=57||t>=65&&t<=70||t>=97&&t<=102)&&(i>=48&&i<=57||i>=65&&i<=70||i>=97&&i<=102)&&(e-=2,n+=2)}let n=0,i=t-1,a=e=>e>=2&&r.charCodeAt(e-2)===37&&r.charCodeAt(e-1)===51&&(r.charCodeAt(e)===68||r.charCodeAt(e)===100);i>=0&&(r.charCodeAt(i)===61?(n++,i--):a(i)&&(n++,i-=3)),n===1&&i>=0&&(r.charCodeAt(i)===61||a(i))&&n++;let o=Math.floor(e/4)*3-(n||0);return o>0?o:0}if(typeof Buffer<`u`&&typeof Buffer.byteLength==`function`)return Buffer.byteLength(r,`utf8`);let i=0;for(let e=0,t=r.length;e<t;e++){let n=r.charCodeAt(e);if(n<128)i+=1;else if(n<2048)i+=2;else if(n>=55296&&n<=56319&&e+1<t){let t=r.charCodeAt(e+1);t>=56320&&t<=57343?(i+=4,e++):i+=3}else i+=3}return i}var Un=`1.17.0`,Wn=64*1024,{isFunction:Gn}=k,Kn=e=>encodeURIComponent(e).replace(/%([0-9A-F]{2})/gi,(e,t)=>String.fromCharCode(parseInt(t,16))),qn=e=>{if(!k.isString(e))return e;try{return decodeURIComponent(e)}catch{return e}},Jn=(e,...t)=>{try{return!!e(...t)}catch{return!1}},Yn=e=>{let t=e.indexOf(`://`),n=e;return t!==-1&&(n=n.slice(t+3)),n.includes(`@`)||n.includes(`:`)},Xn=e=>{let t=k.global!==void 0&&k.global!==null?k.global:globalThis,{ReadableStream:n,TextEncoder:r}=t;e=k.merge.call({skipUndefined:!0},{Request:t.Request,Response:t.Response},e);let{fetch:i,Request:a,Response:o}=e,s=i?Gn(i):typeof fetch==`function`,c=Gn(a),l=Gn(o);if(!s)return!1;let u=s&&Gn(n),d=s&&(typeof r==`function`?(e=>t=>e.encode(t))(new r):async e=>new Uint8Array(await new a(e).arrayBuffer())),f=c&&u&&Jn(()=>{let e=!1,t=new a(j.origin,{body:new n,method:`POST`,get duplex(){return e=!0,`half`}}),r=t.headers.has(`Content-Type`);return t.body!=null&&t.body.cancel(),e&&!r}),p=l&&u&&Jn(()=>k.isReadableStream(new o(``).body)),m={stream:p&&(e=>e.body)};s&&[`text`,`arrayBuffer`,`blob`,`formData`,`stream`].forEach(e=>{!m[e]&&(m[e]=(t,n)=>{let r=t&&t[e];if(r)return r.call(t);throw new A(`Response type '${e}' is not supported`,A.ERR_NOT_SUPPORT,n)})});let h=async e=>{if(e==null)return 0;if(k.isBlob(e))return e.size;if(k.isSpecCompliantForm(e))return(await new a(j.origin,{method:`POST`,body:e}).arrayBuffer()).byteLength;if(k.isArrayBufferView(e)||k.isArrayBuffer(e))return e.byteLength;if(k.isURLSearchParams(e)&&(e+=``),k.isString(e))return(await d(e)).byteLength},g=async(e,t)=>k.toFiniteNumber(e.getContentLength())??h(t);return async e=>{let{url:t,method:n,data:s,signal:l,cancelToken:u,timeout:d,onDownloadProgress:h,onUploadProgress:_,responseType:v,headers:y,withCredentials:b=`same-origin`,fetchOptions:ee,maxContentLength:te,maxBodyLength:ne}=Fn(e),x=k.isNumber(te)&&te>-1,S=k.isNumber(ne)&&ne>-1,C=t=>k.hasOwnProp(e,t)?e[t]:void 0,w=i||fetch;v=v?(v+``).toLowerCase():`text`;let T=Ln([l,u&&u.toAbortSignal()],d),E=null,D=T&&T.unsubscribe&&(()=>{T.unsubscribe()}),re;try{let i,l=C(`auth`);if(l&&(i={username:l.username||``,password:l.password||``}),Yn(t)){let e=new URL(t,j.origin);!i&&(e.username||e.password)&&(i={username:qn(e.username),password:qn(e.password)}),(e.username||e.password)&&(e.username=``,e.password=``,t=e.href)}if(i&&(y.delete(`authorization`),y.set(`Authorization`,`Basic `+btoa(Kn((i.username||``)+`:`+(i.password||``))))),x&&typeof t==`string`&&t.startsWith(`data:`)&&Hn(t)>te)throw new A(`maxContentLength size of `+te+` exceeded`,A.ERR_BAD_RESPONSE,e,E);if(S&&n!==`get`&&n!==`head`){let t=await g(y,s);if(typeof t==`number`&&isFinite(t)&&t>ne)throw new A(`Request body larger than maxBodyLength limit`,A.ERR_BAD_REQUEST,e,E)}if(_&&f&&n!==`get`&&n!==`head`&&(re=await g(y,s))!==0){let e=new a(t,{method:`POST`,body:s,duplex:`half`}),n;if(k.isFormData(s)&&(n=e.headers.get(`content-type`))&&y.setContentType(n),e.body){let[t,n]=Cn(re,Sn(wn(_)));s=Vn(e.body,Wn,t,n)}}k.isString(b)||(b=b?`include`:`omit`);let u=c&&`credentials`in a.prototype;if(k.isFormData(s)){let e=y.getContentType();e&&/^multipart\/form-data/i.test(e)&&!/boundary=/i.test(e)&&y.delete(`content-type`)}y.set(`User-Agent`,`axios/`+Un,!1);let d={...ee,signal:T,method:n.toUpperCase(),headers:Ot(y.normalize()),body:s,duplex:`half`,credentials:u?b:void 0};E=c&&new a(t,d);let O=await(c?w(E,ee):w(t,d));if(x){let t=k.toFiniteNumber(O.headers.get(`content-length`));if(t!=null&&t>te)throw new A(`maxContentLength size of `+te+` exceeded`,A.ERR_BAD_RESPONSE,e,E)}let ie=p&&(v===`stream`||v===`response`);if(p&&O.body&&(h||x||ie&&D)){let t={};[`status`,`statusText`,`headers`].forEach(e=>{t[e]=O[e]});let n=k.toFiniteNumber(O.headers.get(`content-length`)),[r,i]=h&&Cn(n,Sn(wn(h),!0))||[],a=0;O=new o(Vn(O.body,Wn,t=>{if(x&&(a=t,a>te))throw new A(`maxContentLength size of `+te+` exceeded`,A.ERR_BAD_RESPONSE,e,E);r&&r(t)},()=>{i&&i(),D&&D()}),t)}v||=`text`;let ae=await m[k.findKey(m,v)||`text`](O,e);if(x&&!p&&!ie){let t;if(ae!=null&&(typeof ae.byteLength==`number`?t=ae.byteLength:typeof ae.size==`number`?t=ae.size:typeof ae==`string`&&(t=typeof r==`function`?new r().encode(ae).byteLength:ae.length)),typeof t==`number`&&t>te)throw new A(`maxContentLength size of `+te+` exceeded`,A.ERR_BAD_RESPONSE,e,E)}return!ie&&D&&D(),await new Promise((t,n)=>{vn(t,n,{data:ae,headers:Lt.from(O.headers),status:O.status,statusText:O.statusText,config:e,request:E})})}catch(t){if(D&&D(),T&&T.aborted&&T.reason instanceof A){let n=T.reason;throw n.config=e,E&&(n.request=E),t!==n&&(n.cause=t),n}throw t&&t.name===`TypeError`&&/Load failed|fetch/i.test(t.message)?Object.assign(new A(`Network Error`,A.ERR_NETWORK,e,E,t&&t.response),{cause:t.cause||t}):A.from(t,t&&t.code,e,E,t&&t.response)}}},Zn=new Map,Qn=e=>{let t=e&&e.env||{},{fetch:n,Request:r,Response:i}=t,a=[r,i,n],o=a.length,s,c,l=Zn;for(;o--;)s=a[o],c=l.get(s),c===void 0&&l.set(s,c=o?new Map:Xn(t)),l=c;return c};Qn();var $n={http:null,xhr:In,fetch:{get:Qn}};k.forEach($n,(e,t)=>{if(e){try{Object.defineProperty(e,`name`,{__proto__:null,value:t})}catch{}Object.defineProperty(e,`adapterName`,{__proto__:null,value:t})}});var er=e=>`- ${e}`,tr=e=>k.isFunction(e)||e===null||e===!1;function nr(e,t){e=k.isArray(e)?e:[e];let{length:n}=e,r,i,a={};for(let o=0;o<n;o++){r=e[o];let n;if(i=r,!tr(r)&&(i=$n[(n=String(r)).toLowerCase()],i===void 0))throw new A(`Unknown adapter '${n}'`);if(i&&(k.isFunction(i)||(i=i.get(t))))break;a[n||`#`+o]=i}if(!i){let e=Object.entries(a).map(([e,t])=>`adapter ${e} `+(t===!1?`is not supported by the environment`:`is not available in the build`));throw new A(`There is no suitable adapter to dispatch the request `+(n?e.length>1?`since :
`+e.map(er).join(`
`):` `+er(e[0]):`as no adapter specified`),`ERR_NOT_SUPPORT`)}return i}var rr={getAdapter:nr,adapters:$n};function ir(e){if(e.cancelToken&&e.cancelToken.throwIfRequested(),e.signal&&e.signal.aborted)throw new _n(null,e)}function ar(e){return ir(e),e.headers=Lt.from(e.headers),e.data=hn.call(e,e.transformRequest),[`post`,`put`,`patch`].indexOf(e.method)!==-1&&e.headers.setContentType(`application/x-www-form-urlencoded`,!1),rr.getAdapter(e.adapter||mn.adapter,e)(e).then(function(t){ir(e),e.response=t;try{t.data=hn.call(e,e.transformResponse,t)}finally{delete e.response}return t.headers=Lt.from(t.headers),t},function(t){if(!gn(t)&&(ir(e),t&&t.response)){e.response=t.response;try{t.response.data=hn.call(e,e.transformResponse,t.response)}finally{delete e.response}t.response.headers=Lt.from(t.response.headers)}return Promise.reject(t)})}var or={};[`object`,`boolean`,`number`,`function`,`string`,`symbol`].forEach((e,t)=>{or[e]=function(n){return typeof n===e||`a`+(t<1?`n `:` `)+e}});var sr={};or.transitional=function(e,t,n){function r(e,t){return`[Axios v`+Un+`] Transitional option '`+e+`'`+t+(n?`. `+n:``)}return(n,i,a)=>{if(e===!1)throw new A(r(i,` has been removed`+(t?` in `+t:``)),A.ERR_DEPRECATED);return t&&!sr[i]&&(sr[i]=!0,console.warn(r(i,` has been deprecated since v`+t+` and will be removed in the near future`))),e?e(n,i,a):!0}},or.spelling=function(e){return(t,n)=>(console.warn(`${n} is likely a misspelling of ${e}`),!0)};function cr(e,t,n){if(typeof e!=`object`)throw new A(`options must be an object`,A.ERR_BAD_OPTION_VALUE);let r=Object.keys(e),i=r.length;for(;i-- >0;){let a=r[i],o=Object.prototype.hasOwnProperty.call(t,a)?t[a]:void 0;if(o){let t=e[a],n=t===void 0||o(t,a,e);if(n!==!0)throw new A(`option `+a+` must be `+n,A.ERR_BAD_OPTION_VALUE);continue}if(n!==!0)throw new A(`Unknown option `+a,A.ERR_BAD_OPTION)}}var lr={assertOptions:cr,validators:or},ur=lr.validators,dr=class{constructor(e){this.defaults=e||{},this.interceptors={request:new Qt,response:new Qt}}async request(e,t){try{return await this._request(e,t)}catch(e){if(e instanceof Error){let t={};Error.captureStackTrace?Error.captureStackTrace(t):t=Error();let n=(()=>{if(!t.stack)return``;let e=t.stack.indexOf(`
`);return e===-1?``:t.stack.slice(e+1)})();try{if(!e.stack)e.stack=n;else if(n){let t=n.indexOf(`
`),r=t===-1?-1:n.indexOf(`
`,t+1),i=r===-1?``:n.slice(r+1);String(e.stack).endsWith(i)||(e.stack+=`
`+n)}}catch{}}throw e}}_request(e,t){typeof e==`string`?(t||={},t.url=e):t=e||{},t=jn(this.defaults,t);let{transitional:n,paramsSerializer:r,headers:i}=t;n!==void 0&&lr.assertOptions(n,{silentJSONParsing:ur.transitional(ur.boolean),forcedJSONParsing:ur.transitional(ur.boolean),clarifyTimeoutError:ur.transitional(ur.boolean),legacyInterceptorReqResOrdering:ur.transitional(ur.boolean),advertiseZstdAcceptEncoding:ur.transitional(ur.boolean)},!1),r!=null&&(k.isFunction(r)?t.paramsSerializer={serialize:r}:lr.assertOptions(r,{encode:ur.function,serialize:ur.function},!0)),t.allowAbsoluteUrls!==void 0||(this.defaults.allowAbsoluteUrls===void 0?t.allowAbsoluteUrls=!0:t.allowAbsoluteUrls=this.defaults.allowAbsoluteUrls),lr.assertOptions(t,{baseUrl:ur.spelling(`baseURL`),withXsrfToken:ur.spelling(`withXSRFToken`)},!0),t.method=(t.method||this.defaults.method||`get`).toLowerCase();let a=i&&k.merge(i.common,i[t.method]);i&&k.forEach([`delete`,`get`,`head`,`post`,`put`,`patch`,`query`,`common`],e=>{delete i[e]}),t.headers=Lt.concat(a,i);let o=[],s=!0;this.interceptors.request.forEach(function(e){if(typeof e.runWhen==`function`&&e.runWhen(t)===!1)return;s&&=e.synchronous;let n=t.transitional||$t;n&&n.legacyInterceptorReqResOrdering?o.unshift(e.fulfilled,e.rejected):o.push(e.fulfilled,e.rejected)});let c=[];this.interceptors.response.forEach(function(e){c.push(e.fulfilled,e.rejected)});let l,u=0,d;if(!s){let e=[ar.bind(this),void 0];for(e.unshift(...o),e.push(...c),d=e.length,l=Promise.resolve(t);u<d;)l=l.then(e[u++],e[u++]);return l}d=o.length;let f=t;for(;u<d;){let e=o[u++],t=o[u++];try{f=e(f)}catch(e){t.call(this,e);break}}try{l=ar.call(this,f)}catch(e){return Promise.reject(e)}for(u=0,d=c.length;u<d;)l=l.then(c[u++],c[u++]);return l}getUri(e){return e=jn(this.defaults,e),Zt(kn(e.baseURL,e.url,e.allowAbsoluteUrls),e.params,e.paramsSerializer)}};k.forEach([`delete`,`get`,`head`,`options`],function(e){dr.prototype[e]=function(t,n){return this.request(jn(n||{},{method:e,url:t,data:(n||{}).data}))}}),k.forEach([`post`,`put`,`patch`,`query`],function(e){function t(t){return function(n,r,i){return this.request(jn(i||{},{method:e,headers:t?{"Content-Type":`multipart/form-data`}:{},url:n,data:r}))}}dr.prototype[e]=t(),e!==`query`&&(dr.prototype[e+`Form`]=t(!0))});var fr=class e{constructor(e){if(typeof e!=`function`)throw TypeError(`executor must be a function.`);let t;this.promise=new Promise(function(e){t=e});let n=this;this.promise.then(e=>{if(!n._listeners)return;let t=n._listeners.length;for(;t-- >0;)n._listeners[t](e);n._listeners=null}),this.promise.then=e=>{let t,r=new Promise(e=>{n.subscribe(e),t=e}).then(e);return r.cancel=function(){n.unsubscribe(t)},r},e(function(e,r,i){n.reason||(n.reason=new _n(e,r,i),t(n.reason))})}throwIfRequested(){if(this.reason)throw this.reason}subscribe(e){if(this.reason){e(this.reason);return}this._listeners?this._listeners.push(e):this._listeners=[e]}unsubscribe(e){if(!this._listeners)return;let t=this._listeners.indexOf(e);t!==-1&&this._listeners.splice(t,1)}toAbortSignal(){let e=new AbortController,t=t=>{e.abort(t)};return this.subscribe(t),e.signal.unsubscribe=()=>this.unsubscribe(t),e.signal}static source(){let t;return{token:new e(function(e){t=e}),cancel:t}}};function pr(e){return function(t){return e.apply(null,t)}}function mr(e){return k.isObject(e)&&e.isAxiosError===!0}var hr={Continue:100,SwitchingProtocols:101,Processing:102,EarlyHints:103,Ok:200,Created:201,Accepted:202,NonAuthoritativeInformation:203,NoContent:204,ResetContent:205,PartialContent:206,MultiStatus:207,AlreadyReported:208,ImUsed:226,MultipleChoices:300,MovedPermanently:301,Found:302,SeeOther:303,NotModified:304,UseProxy:305,Unused:306,TemporaryRedirect:307,PermanentRedirect:308,BadRequest:400,Unauthorized:401,PaymentRequired:402,Forbidden:403,NotFound:404,MethodNotAllowed:405,NotAcceptable:406,ProxyAuthenticationRequired:407,RequestTimeout:408,Conflict:409,Gone:410,LengthRequired:411,PreconditionFailed:412,PayloadTooLarge:413,UriTooLong:414,UnsupportedMediaType:415,RangeNotSatisfiable:416,ExpectationFailed:417,ImATeapot:418,MisdirectedRequest:421,UnprocessableEntity:422,Locked:423,FailedDependency:424,TooEarly:425,UpgradeRequired:426,PreconditionRequired:428,TooManyRequests:429,RequestHeaderFieldsTooLarge:431,UnavailableForLegalReasons:451,InternalServerError:500,NotImplemented:501,BadGateway:502,ServiceUnavailable:503,GatewayTimeout:504,HttpVersionNotSupported:505,VariantAlsoNegotiates:506,InsufficientStorage:507,LoopDetected:508,NotExtended:510,NetworkAuthenticationRequired:511,WebServerIsDown:521,ConnectionTimedOut:522,OriginIsUnreachable:523,TimeoutOccurred:524,SslHandshakeFailed:525,InvalidSslCertificate:526};Object.entries(hr).forEach(([e,t])=>{hr[t]=e});function gr(e){let t=new dr(e),n=ce(dr.prototype.request,t);return k.extend(n,dr.prototype,t,{allOwnKeys:!0}),k.extend(n,t,null,{allOwnKeys:!0}),n.create=function(t){return gr(jn(e,t))},n}var M=gr(mn);M.Axios=dr,M.CanceledError=_n,M.CancelToken=fr,M.isCancel=gn,M.VERSION=Un,M.toFormData=Kt,M.AxiosError=A,M.Cancel=M.CanceledError,M.all=function(e){return Promise.all(e)},M.spread=pr,M.isAxiosError=mr,M.mergeConfig=jn,M.AxiosHeaders=Lt,M.formToJSON=e=>dn(k.isHTMLForm(e)?new FormData(e):e),M.getAdapter=rr.getAdapter,M.HttpStatusCode=hr,M.default=M;var _r=new WeakMap,vr=new WeakMap,yr=new WeakMap,br=new WeakMap,xr=new WeakMap,Sr=new WeakMap,Cr=new WeakMap,wr=new WeakSet,Tr=class e extends EventTarget{constructor(...e){super(...e),re(this,wr),w(this,_r,Math.random().toString(36).slice(2)),this.enabled=!0,w(this,vr,``),this.canAccessQueueManager=!1,w(this,yr,!0),this.totalJobs=0,this.jobInfo=[],this.displayedJob=null,this.displayedJobUnchangedCount=1,w(this,br,null),this.isTracking=!1,w(this,xr,null),w(this,Sr,null),w(this,Cr,ae.getInstance())}static getInstance(){return Lr._||=new e,Lr._}static resetInstance(){Lr._&&=(Lr._.stopTracking(),D(Sr,Lr._)?.close(),null)}initialize(e={}){E(vr,this,e.appId??``),this.canAccessQueueManager=e.canAccessQueueManager??!1,E(yr,this,e.runAutomatically??!0),T(wr,this,Er).call(this)}async runQueue(){if(!D(yr,this)){this.startTracking(!1,!0);return}try{await M.post(D(Cr,this).getActionUrl(`queue/run`))}catch(e){console.error(e)}this.startTracking(!1,!0)}startTracking(e=!1,t=!1){if(this.isTracking&&!t)return;D(br,this)&&(clearTimeout(D(br,this)),E(br,this,null));let n=0;e===!0?n=T(wr,this,kr).call(this):typeof e==`number`&&(n=e),n>0?E(br,this,setTimeout(()=>{T(wr,this,Ar).call(this)},n)):T(wr,this,Ar).call(this)}stopTracking(){this.isTracking=!1,D(br,this)&&(clearTimeout(D(br,this)),E(br,this,null)),D(xr,this)&&(D(xr,this).abort(),E(xr,this,null))}setJobData(e){this.totalJobs=e.length,T(wr,this,Mr).call(this,e)}};function Er(){if(typeof BroadcastChannel>`u`||!D(vr,this))return;let e=`CraftCMS:${D(vr,this)}:queue`;E(Sr,this,new BroadcastChannel(e)),D(Sr,this).addEventListener(`message`,e=>{T(wr,this,Dr).call(this,e.data)})}function Dr(e){if(e.instanceId!==D(_r,this))switch(e.event){case`beforeTrackJobProgress`:this.stopTracking();break;case`trackJobProgress`:if(e.jobData&&this.setJobData(e.jobData.jobs),this.displayedJob!==null){let e=T(wr,this,kr).call(this)+1e3;this.startTracking(e)}break}}function Or(e,t){D(Sr,this)?.postMessage({event:e,instanceId:D(_r,this),...t})}function kr(){return Math.min(6e4,this.displayedJobUnchangedCount*500)}async function Ar(){T(wr,this,Or).call(this,`beforeTrackJobProgress`),this.isTracking=!0,E(xr,this,new AbortController);try{let e=await M.get(D(Cr,this).getActionUrl(`queue/get-job-info`),{params:{dontExtendSession:1},signal:D(xr,this).signal});this.setJobData(e.data.jobs),T(wr,this,Or).call(this,`trackJobProgress`,{jobData:e.data}),this.displayedJob!==null&&this.startTracking(!0,!0)}catch(e){if(e instanceof Error&&e.name===`CanceledError`)return;let t=e;if(t.response?.status===400||t.response?.status===403){this.stopTracking();return}this.displayedJob!==null&&this.startTracking(!0,!0)}finally{this.isTracking=!1,E(xr,this,null)}}function jr(e,t){return e===null&&t===null?!0:e===null||t===null?!1:e.id===t.id&&e.progress===t.progress&&e.progressLabel===t.progressLabel&&e.status===t.status}function Mr(e){let t=this.displayedJob;this.jobInfo=e,this.displayedJob=T(wr,this,Nr).call(this),T(wr,this,jr).call(this,t,this.displayedJob)?this.displayedJobUnchangedCount++:this.displayedJobUnchangedCount=1,T(wr,this,Pr).call(this),this.displayedJob?.status.value===x.Failed&&T(wr,this,Ir).call(this,this.displayedJob),this.displayedJob===null&&t&&T(wr,this,Fr).call(this)}function Nr(){if(this.jobInfo?.length===0)return null;let e=[x.Reserved,x.Failed,x.Pending];for(let t of e){let e=this.jobInfo.find(e=>e.status.value===t?!(t===x.Pending&&e.delay>0):!1);if(e)return e}return null}function Pr(){let e={totalJobs:this.totalJobs,jobInfo:this.jobInfo,displayedJob:this.displayedJob};this.dispatchEvent(new CustomEvent(`job-update`,{detail:e}))}function Fr(){this.dispatchEvent(new CustomEvent(`job-complete`))}function Ir(e){let t={job:e};this.dispatchEvent(new CustomEvent(`job-failed`,{detail:t}))}var Lr={_:null},Rr=``,zr=``,Br=``;function Vr(e){Rr=e}function Hr(e=``){if(!Rr){let e=document.querySelector(`[data-webawesome]`);if(e?.hasAttribute(`data-webawesome`)){let t=new URL(e.getAttribute(`data-webawesome`)??``,window.location.href).pathname;Vr(t)}else{let e=[...document.getElementsByTagName(`script`)].find(e=>e.src.endsWith(`webawesome.js`)||e.src.endsWith(`webawesome.loader.js`)||e.src.endsWith(`webawesome.ssr-loader.js`));e&&Vr(String(e.getAttribute(`src`)).split(`/`).slice(0,-1).join(`/`))}}return Rr.replace(/\/$/,``)+(e?`/${e.replace(/^\//,``)}`:``)}function Ur(){return zr.replace(/\/$/,``)}function Wr(e){Br=e}function Gr(){if(!Br){let e=document.querySelector(`[data-fa-kit-code]`);e&&Wr(e.getAttribute(`data-fa-kit-code`)||``)}return Br}new MutationObserver(e=>{for(let{addedNodes:t}of e)for(let e of t)e.nodeType===Node.ELEMENT_NODE&&Kr(e)});async function Kr(e){let t=e instanceof Element?e.tagName.toLowerCase():``,n=t?.startsWith(`wa-`),r=[...e.querySelectorAll(`:not(:defined)`)].map(e=>e.tagName.toLowerCase()).filter(e=>e.startsWith(`wa-`));n&&!customElements.get(t)&&r.push(t);let i=e.querySelectorAll(`[data-wa-preload]`),a=e instanceof Element&&e.hasAttribute(`data-wa-preload`)?[e,...i]:i;for(let e of a)r.push(...e.getAttribute(`data-wa-preload`).split(/\s+/).filter(e=>e.startsWith(`wa-`)));let o=[...new Set(r)],s=await Promise.allSettled(o.map(e=>qr(e)));for(let e of s)e.status===`rejected`&&console.warn(e.reason);await new Promise(requestAnimationFrame),e.dispatchEvent(new CustomEvent(`wa-discovery-complete`,{bubbles:!1,cancelable:!1,composed:!0}))}function qr(e){if(customElements.get(e))return Promise.resolve();let t=e.replace(/^wa-/i,``),n=Hr(`components/${t}/${t}.js`);return new Promise((t,r)=>{s(()=>import(n).then(()=>t()),[],import.meta.url).catch(()=>r(Error(`Unable to autoload <${e}> from ${n}`)))})}var Jr=new Set,Yr=new Map,Xr,Zr=`ltr`,Qr=`en`,$r=typeof MutationObserver<`u`&&typeof document<`u`&&document.documentElement!==void 0;if($r){let e=new MutationObserver(ti);Zr=document.documentElement.dir||`ltr`,Qr=document.documentElement.lang||navigator.language,e.observe(document.documentElement,{attributes:!0,attributeFilter:[`dir`,`lang`]})}function ei(...e){e.map(e=>{let t=e.$code.toLowerCase();Yr.has(t)?Yr.set(t,Object.assign(Object.assign({},Yr.get(t)),e)):Yr.set(t,e),Xr||=e}),ti()}function ti(){$r&&(Zr=document.documentElement.dir||`ltr`,Qr=document.documentElement.lang||navigator.language),[...Jr.keys()].map(e=>{typeof e.requestUpdate==`function`&&e.requestUpdate()})}var ni=class{constructor(e){this.host=e,this.host.addController(this)}hostConnected(){Jr.add(this.host)}hostDisconnected(){Jr.delete(this.host)}dir(){return`${this.host.dir||Zr}`.toLowerCase()}lang(){return`${this.host.lang||Qr}`.toLowerCase()}getTranslationData(e){let t;try{t=new Intl.Locale(e.replace(/_/g,`-`))}catch{return{locale:void 0,language:``,region:``,primary:void 0,secondary:void 0}}let n=t.language.toLowerCase(),r=t.region?.toLowerCase()??``,i=Yr.get(`${n}-${r}`),a=Yr.get(n);return{locale:t,language:n,region:r,primary:i,secondary:a}}exists(e,t){let{primary:n,secondary:r}=this.getTranslationData(t.lang??this.lang());return t=Object.assign({includeFallback:!1},t),!!(n&&n[e]||r&&r[e]||t.includeFallback&&Xr&&Xr[e])}term(e,...t){let{primary:n,secondary:r}=this.getTranslationData(this.lang()),i;if(n&&n[e])i=n[e];else if(r&&r[e])i=r[e];else if(Xr&&Xr[e])i=Xr[e];else return console.error(`No translation found for: ${String(e)}`),String(e);return typeof i==`function`?i(...t):i}date(e,t){return e=new Date(e),new Intl.DateTimeFormat(this.lang(),t).format(e)}number(e,t){return e=Number(e),isNaN(e)?``:new Intl.NumberFormat(this.lang(),t).format(e)}relativeTime(e,t,n){return new Intl.RelativeTimeFormat(this.lang(),n).format(e,t)}},ri={$code:`en`,$name:`English`,$dir:`ltr`,carousel:`Carousel`,captions:`Captions`,clearEntry:`Clear entry`,close:`Close`,createOption:e=>`Create "${e}"`,copied:`Copied`,copy:`Copy`,currentValue:`Current value`,dropFileHere:`Drop file here or click to browse`,decrement:`Decrement`,dropFilesHere:`Drop files here or click to browse`,error:`Error`,enterFullscreen:`Enter fullscreen`,exitFullscreen:`Exit fullscreen`,goToSlide:(e,t)=>`Go to slide ${e} of ${t}`,hidePassword:`Hide password`,increment:`Increment`,loading:`Loading`,moreOptions:`More Options`,mute:`Mute`,nextSlide:`Next slide`,nextVideo:`Next Video`,numCharacters:e=>e===1?`1 character`:`${e} characters`,numCharactersRemaining:e=>e===1?`1 character remaining`:`${e} characters remaining`,numOptionsSelected:e=>e===0?`No options selected`:e===1?`1 option selected`:`${e} options selected`,pause:`Pause`,pauseAnimation:`Pause animation`,pictureInPicture:`Picture in picture`,play:`Play`,playbackSpeed:`Playback speed`,playlist:`Playlist`,playAnimation:`Play animation`,previousSlide:`Previous slide`,previousVideo:`Previous video`,progress:`Progress`,remove:`Remove`,resize:`Resize`,scrollableRegion:`Scrollable region`,scrollToEnd:`Scroll to end`,scrollToStart:`Scroll to start`,selectAColorFromTheScreen:`Select a color from the screen`,showPassword:`Show password`,slideNum:e=>`Slide ${e}`,toggleColorFormat:`Toggle color format`,seek:`Seek`,seekProgress:(e,t)=>`${e} of ${t}`,currentlyPlaying:`currently playing`,unmute:`Unmute`,videoPlayer:`Video player`,volume:`Volume`,zoomIn:`Zoom in`,zoomOut:`Zoom out`};ei(ri);var ii=ri,ai=class extends ni{};ei(ii);var oi=`7.2.0`;function si(e,t,n){let r=`solid`;return t===`chisel`&&(r=`chisel-regular`),t===`etch`&&(r=`etch-solid`),t===`graphite`&&(r=`graphite-thin`),t===`jelly`&&(r=`jelly-regular`,n===`duo-regular`&&(r=`jelly-duo-regular`),n===`fill-regular`&&(r=`jelly-fill-regular`)),t===`jelly-duo`&&(r=`jelly-duo-regular`),t===`jelly-fill`&&(r=`jelly-fill-regular`),t===`notdog`&&(n===`solid`&&(r=`notdog-solid`),n===`duo-solid`&&(r=`notdog-duo-solid`)),t===`notdog-duo`&&(r=`notdog-duo-solid`),t===`slab`&&((n===`solid`||n===`regular`)&&(r=`slab-regular`),n===`press-regular`&&(r=`slab-press-regular`)),t===`slab-press`&&(r=`slab-press-regular`),t===`thumbprint`&&(r=`thumbprint-light`),t===`utility`&&(r=`utility-semibold`),t===`utility-duo`&&(r=`utility-duo-semibold`),t===`utility-fill`&&(r=`utility-fill-semibold`),t===`whiteboard`&&(r=`whiteboard-semibold`),t===`classic`&&(n===`thin`&&(r=`thin`),n===`light`&&(r=`light`),n===`regular`&&(r=`regular`),n===`solid`&&(r=`solid`)),t===`duotone`&&(n===`thin`&&(r=`duotone-thin`),n===`light`&&(r=`duotone-light`),n===`regular`&&(r=`duotone-regular`),n===`solid`&&(r=`duotone`)),t===`sharp`&&(n===`thin`&&(r=`sharp-thin`),n===`light`&&(r=`sharp-light`),n===`regular`&&(r=`sharp-regular`),n===`solid`&&(r=`sharp-solid`)),t===`sharp-duotone`&&(n===`thin`&&(r=`sharp-duotone-thin`),n===`light`&&(r=`sharp-duotone-light`),n===`regular`&&(r=`sharp-duotone-regular`),n===`solid`&&(r=`sharp-duotone-solid`)),t===`brands`&&(r=`brands`),r}function ci(e,t,n){let r=si(e,t,n),i=Ur();if(i)return`${i}/${r}/${e}.svg`;let a=Gr();return a.length>0?`https://ka-p.fontawesome.com/releases/v${oi}/svgs/${r}/${e}.svg?token=${encodeURIComponent(a)}`:`https://ka-f.fontawesome.com/releases/v${oi}/svgs/${r}/${e}.svg`}var li={name:`default`,resolver:(e,t=`classic`,n=`solid`)=>ci(e,t,n),mutator:(e,t)=>{if(t?.family&&!e.hasAttribute(`data-duotone-initialized`)){let{family:n,variant:r}=t;if(n===`duotone`||n===`sharp-duotone`||n===`notdog-duo`||n===`notdog`&&r===`duo-solid`||n===`jelly-duo`||n===`jelly`&&r===`duo-regular`||n===`utility-duo`||n===`thumbprint`){let n=[...e.querySelectorAll(`path`)],r=n.find(e=>!e.hasAttribute(`opacity`)),i=n.find(e=>e.hasAttribute(`opacity`));if(!r||!i)return;if(r.setAttribute(`data-duotone-primary`,``),i.setAttribute(`data-duotone-secondary`,``),t.swapOpacity&&r&&i){let e=i.getAttribute(`opacity`)||`0.4`;r.style.setProperty(`--path-opacity`,e),i.style.setProperty(`--path-opacity`,`1`)}e.setAttribute(`data-duotone-initialized`,``)}}}};function ui(e){return`data:image/svg+xml,${encodeURIComponent(e)}`}var di={solid:{backward:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M236.3 107.1C247.9 96 265 92.9 279.7 99.2C294.4 105.5 304 120 304 136L304 272.3L476.3 107.2C487.9 96 505 92.9 519.7 99.2C534.4 105.5 544 120 544 136L544 504C544 520 534.4 534.5 519.7 540.8C505 547.1 487.9 544 476.3 532.9L304 367.7L304 504C304 520 294.4 534.5 279.7 540.8C265 547.1 247.9 544 236.3 532.9L44.3 348.9C36.5 341.3 32 330.9 32 320C32 309.1 36.5 298.7 44.3 291.1L236.3 107.1z"/></svg>`,"backward-step":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M491 100.8C478.1 93.8 462.3 94.5 450 102.6L192 272.1L192 128C192 110.3 177.7 96 160 96C142.3 96 128 110.3 128 128L128 512C128 529.7 142.3 544 160 544C177.7 544 192 529.7 192 512L192 367.9L450 537.5C462.3 545.6 478 546.3 491 539.3C504 532.3 512 518.8 512 504.1L512 136.1C512 121.4 503.9 107.9 491 100.9z"/></svg>`,check:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M434.8 70.1c14.3 10.4 17.5 30.4 7.1 44.7l-256 352c-5.5 7.6-14 12.3-23.4 13.1s-18.5-2.7-25.1-9.3l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l101.5 101.5 234-321.7c10.4-14.3 30.4-17.5 44.7-7.1z"/></svg>`,"chevron-down":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M201.4 406.6c12.5 12.5 32.8 12.5 45.3 0l192-192c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L224 338.7 54.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l192 192z"/></svg>`,"chevron-left":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l192 192c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L77.3 256 246.6 86.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-192 192z"/></svg>`,"chevron-right":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M311.1 233.4c12.5 12.5 12.5 32.8 0 45.3l-192 192c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L243.2 256 73.9 86.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l192 192z"/></svg>`,circle:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0z"/></svg>`,"closed-captioning":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M64 192C64 156.7 92.7 128 128 128L512 128C547.3 128 576 156.7 576 192L576 448C576 483.3 547.3 512 512 512L128 512C92.7 512 64 483.3 64 448L64 192zM216 272L248 272C252.4 272 256 275.6 256 280C256 293.3 266.7 304 280 304C293.3 304 304 293.3 304 280C304 249.1 278.9 224 248 224L216 224C185.1 224 160 249.1 160 280L160 360C160 390.9 185.1 416 216 416L248 416C278.9 416 304 390.9 304 360C304 346.7 293.3 336 280 336C266.7 336 256 346.7 256 360C256 364.4 252.4 368 248 368L216 368C211.6 368 208 364.4 208 360L208 280C208 275.6 211.6 272 216 272zM384 280C384 275.6 387.6 272 392 272L424 272C428.4 272 432 275.6 432 280C432 293.3 442.7 304 456 304C469.3 304 480 293.3 480 280C480 249.1 454.9 224 424 224L392 224C361.1 224 336 249.1 336 280L336 360C336 390.9 361.1 416 392 416L424 416C454.9 416 480 390.9 480 360C480 346.7 469.3 336 456 336C442.7 336 432 346.7 432 360C432 364.4 428.4 368 424 368L392 368C387.6 368 384 364.4 384 360L384 280z"/></svg>`,"closed-captioning-slash":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M39 39.1C48.4 29.7 63.6 29.7 72.9 39.1L161.8 128L512 128C547.3 128 576 156.7 576 192L576 448C576 473.5 561.1 495.4 539.6 505.8L601 567.1C610.4 576.5 610.4 591.7 601 601C591.6 610.3 576.4 610.4 567.1 601L39 73.1C29.7 63.7 29.7 48.5 39 39.1zM384 350.1L384 279.9C384 275.5 387.6 271.9 392 271.9L424 271.9C428.4 271.9 432 275.5 432 279.9C432 293.2 442.7 303.9 456 303.9C469.3 303.9 480 293.2 480 279.9C480 249 454.9 223.9 424 223.9L392 223.9C361.1 223.9 336 249 336 279.9L336 302.1L384 350.1zM445.5 411.6C465.7 403.2 480 383.2 480 359.9C480 346.6 469.3 335.9 456 335.9C442.7 335.9 432 346.6 432 359.9C432 364.3 428.4 367.9 424 367.9L401.8 367.9L445.5 411.6zM162.3 264.1C160.8 269.1 160 274.5 160 280L160 360C160 390.9 185.1 416 216 416L248 416C266.1 416 282.1 407.5 292.4 394.2L410.2 512L128 512C92.7 512 64 483.3 64 448L64 192C64 184.2 65.4 176.7 68 169.8L162.3 264.1zM256.1 357.9C256 358.6 256 359.3 256 360C256 364.4 252.4 368 248 368L216 368C211.6 368 208 364.4 208 360L208 309.8L256.1 357.9z"/></svg>`,compress:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M160 64c0-17.7-14.3-32-32-32S96 46.3 96 64l0 64-64 0c-17.7 0-32 14.3-32 32s14.3 32 32 32l96 0c17.7 0 32-14.3 32-32l0-96zM32 320c-17.7 0-32 14.3-32 32s14.3 32 32 32l64 0 0 64c0 17.7 14.3 32 32 32s32-14.3 32-32l0-96c0-17.7-14.3-32-32-32l-96 0zM352 64c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 96c0 17.7 14.3 32 32 32l96 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-64 0 0-64zM320 320c-17.7 0-32 14.3-32 32l0 96c0 17.7 14.3 32 32 32s32-14.3 32-32l0-64 64 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-96 0z"/></svg>`,"ellipsis-vertical":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M320 208C289.1 208 264 182.9 264 152C264 121.1 289.1 96 320 96C350.9 96 376 121.1 376 152C376 182.9 350.9 208 320 208zM320 432C350.9 432 376 457.1 376 488C376 518.9 350.9 544 320 544C289.1 544 264 518.9 264 488C264 457.1 289.1 432 320 432zM376 320C376 350.9 350.9 376 320 376C289.1 376 264 350.9 264 320C264 289.1 289.1 264 320 264C350.9 264 376 289.1 376 320z"/></svg>`,expand:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M128 96C110.3 96 96 110.3 96 128L96 224C96 241.7 110.3 256 128 256C145.7 256 160 241.7 160 224L160 160L224 160C241.7 160 256 145.7 256 128C256 110.3 241.7 96 224 96L128 96zM160 416C160 398.3 145.7 384 128 384C110.3 384 96 398.3 96 416L96 512C96 529.7 110.3 544 128 544L224 544C241.7 544 256 529.7 256 512C256 494.3 241.7 480 224 480L160 480L160 416zM416 96C398.3 96 384 110.3 384 128C384 145.7 398.3 160 416 160L480 160L480 224C480 241.7 494.3 256 512 256C529.7 256 544 241.7 544 224L544 128C544 110.3 529.7 96 512 96L416 96zM544 416C544 398.3 529.7 384 512 384C494.3 384 480 398.3 480 416L480 480L416 480C398.3 480 384 494.3 384 512C384 529.7 398.3 544 416 544L512 544C529.7 544 544 529.7 544 512L544 416z"/></svg>`,eyedropper:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M341.6 29.2l-101.6 101.6-9.4-9.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3l-9.4-9.4 101.6-101.6c39-39 39-102.2 0-141.1s-102.2-39-141.1 0zM55.4 323.3c-15 15-23.4 35.4-23.4 56.6l0 42.4-26.6 39.9c-8.5 12.7-6.8 29.6 4 40.4s27.7 12.5 40.4 4l39.9-26.6 42.4 0c21.2 0 41.6-8.4 56.6-23.4l109.4-109.4-45.3-45.3-109.4 109.4c-3 3-7.1 4.7-11.3 4.7l-36.1 0 0-36.1c0-4.2 1.7-8.3 4.7-11.3l109.4-109.4-45.3-45.3-109.4 109.4z"/></svg>`,forward:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M403.7 107.1C392.1 96 375 92.9 360.3 99.2C345.6 105.5 336 120 336 136L336 272.3L163.7 107.2C152.1 96 135 92.9 120.3 99.2C105.6 105.5 96 120 96 136L96 504C96 520 105.6 534.5 120.3 540.8C135 547.1 152.1 544 163.7 532.9L336 367.7L336 504C336 520 345.6 534.5 360.3 540.8C375 547.1 392.1 544 403.7 532.9L595.7 348.9C603.6 341.4 608 330.9 608 320C608 309.1 603.5 298.7 595.7 291.1L403.7 107.1z"/></svg>`,file:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free 7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M192 64C156.7 64 128 92.7 128 128L128 512C128 547.3 156.7 576 192 576L448 576C483.3 576 512 547.3 512 512L512 234.5C512 217.5 505.3 201.2 493.3 189.2L386.7 82.7C374.7 70.7 358.5 64 341.5 64L192 64zM453.5 240L360 240C346.7 240 336 229.3 336 216L336 122.5L453.5 240z"/></svg>`,"file-audio":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free 7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M128 128C128 92.7 156.7 64 192 64L341.5 64C358.5 64 374.8 70.7 386.8 82.7L493.3 189.3C505.3 201.3 512 217.6 512 234.6L512 512C512 547.3 483.3 576 448 576L192 576C156.7 576 128 547.3 128 512L128 128zM336 122.5L336 216C336 229.3 346.7 240 360 240L453.5 240L336 122.5zM389.8 307.7C380.7 301.4 368.3 303.6 362 312.7C355.7 321.8 357.9 334.2 367 340.5C390.9 357.2 406.4 384.8 406.4 416C406.4 447.2 390.8 474.9 367 491.5C357.9 497.8 355.7 510.3 362 519.3C368.3 528.3 380.8 530.6 389.8 524.3C423.9 500.5 446.4 460.8 446.4 416C446.4 371.2 424 331.5 389.8 307.7zM208 376C199.2 376 192 383.2 192 392L192 440C192 448.8 199.2 456 208 456L232 456L259.2 490C262.2 493.8 266.8 496 271.7 496L272 496C280.8 496 288 488.8 288 480L288 352C288 343.2 280.8 336 272 336L271.7 336C266.8 336 262.2 338.2 259.2 342L232 376L208 376zM336 448.2C336 458.9 346.5 466.4 354.9 459.8C367.8 449.5 376 433.7 376 416C376 398.3 367.8 382.5 354.9 372.2C346.5 365.5 336 373.1 336 383.8L336 448.3z"/></svg>`,"file-code":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free 7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M128 128C128 92.7 156.7 64 192 64L341.5 64C358.5 64 374.8 70.7 386.8 82.7L493.3 189.3C505.3 201.3 512 217.6 512 234.6L512 512C512 547.3 483.3 576 448 576L192 576C156.7 576 128 547.3 128 512L128 128zM336 122.5L336 216C336 229.3 346.7 240 360 240L453.5 240L336 122.5zM282.2 359.6C290.8 349.5 289.7 334.4 279.6 325.8C269.5 317.2 254.4 318.3 245.8 328.4L197.8 384.4C190.1 393.4 190.1 406.6 197.8 415.6L245.8 471.6C254.4 481.7 269.6 482.8 279.6 474.2C289.6 465.6 290.8 450.4 282.2 440.4L247.6 400L282.2 359.6zM394.2 328.4C385.6 318.3 370.4 317.2 360.4 325.8C350.4 334.4 349.2 349.6 357.8 359.6L392.4 400L357.8 440.4C349.2 450.5 350.3 465.6 360.4 474.2C370.5 482.8 385.6 481.7 394.2 471.6L442.2 415.6C449.9 406.6 449.9 393.4 442.2 384.4L394.2 328.4z"/></svg>`,"file-excel":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free 7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M128 128C128 92.7 156.7 64 192 64L341.5 64C358.5 64 374.8 70.7 386.8 82.7L493.3 189.3C505.3 201.3 512 217.6 512 234.6L512 512C512 547.3 483.3 576 448 576L192 576C156.7 576 128 547.3 128 512L128 128zM336 122.5L336 216C336 229.3 346.7 240 360 240L453.5 240L336 122.5zM292 330.7C284.6 319.7 269.7 316.7 258.7 324C247.7 331.3 244.7 346.3 252 357.3L291.2 416L252 474.7C244.6 485.7 247.6 500.6 258.7 508C269.8 515.4 284.6 512.4 292 501.3L320 459.3L348 501.3C355.4 512.3 370.3 515.3 381.3 508C392.3 500.7 395.3 485.7 388 474.7L348.8 416L388 357.3C395.4 346.3 392.4 331.4 381.3 324C370.2 316.6 355.4 319.6 348 330.7L320 372.7L292 330.7z"/></svg>`,"file-image":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free 7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M128 128C128 92.7 156.7 64 192 64L341.5 64C358.5 64 374.8 70.7 386.8 82.7L493.3 189.3C505.3 201.3 512 217.6 512 234.6L512 512C512 547.3 483.3 576 448 576L192 576C156.7 576 128 547.3 128 512L128 128zM336 122.5L336 216C336 229.3 346.7 240 360 240L453.5 240L336 122.5zM256 320C256 302.3 241.7 288 224 288C206.3 288 192 302.3 192 320C192 337.7 206.3 352 224 352C241.7 352 256 337.7 256 320zM220.6 512L419.4 512C435.2 512 448 499.2 448 483.4C448 476.1 445.2 469 440.1 463.7L343.3 361.9C337.3 355.6 328.9 352 320.1 352L319.8 352C311 352 302.7 355.6 296.6 361.9L199.9 463.7C194.8 469 192 476.1 192 483.4C192 499.2 204.8 512 220.6 512z"/></svg>`,"file-pdf":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free 7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M128 64C92.7 64 64 92.7 64 128L64 512C64 547.3 92.7 576 128 576L208 576L208 464C208 428.7 236.7 400 272 400L448 400L448 234.5C448 217.5 441.3 201.2 429.3 189.2L322.7 82.7C310.7 70.7 294.5 64 277.5 64L128 64zM389.5 240L296 240C282.7 240 272 229.3 272 216L272 122.5L389.5 240zM272 444C261 444 252 453 252 464L252 592C252 603 261 612 272 612C283 612 292 603 292 592L292 564L304 564C337.1 564 364 537.1 364 504C364 470.9 337.1 444 304 444L272 444zM304 524L292 524L292 484L304 484C315 484 324 493 324 504C324 515 315 524 304 524zM400 444C389 444 380 453 380 464L380 592C380 603 389 612 400 612L432 612C460.7 612 484 588.7 484 560L484 496C484 467.3 460.7 444 432 444L400 444zM420 572L420 484L432 484C438.6 484 444 489.4 444 496L444 560C444 566.6 438.6 572 432 572L420 572zM508 464L508 592C508 603 517 612 528 612C539 612 548 603 548 592L548 548L576 548C587 548 596 539 596 528C596 517 587 508 576 508L548 508L548 484L576 484C587 484 596 475 596 464C596 453 587 444 576 444L528 444C517 444 508 453 508 464z"/></svg>`,"file-powerpoint":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free 7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M128 128C128 92.7 156.7 64 192 64L341.5 64C358.5 64 374.8 70.7 386.8 82.7L493.3 189.3C505.3 201.3 512 217.6 512 234.6L512 512C512 547.3 483.3 576 448 576L192 576C156.7 576 128 547.3 128 512L128 128zM336 122.5L336 216C336 229.3 346.7 240 360 240L453.5 240L336 122.5zM280 320C266.7 320 256 330.7 256 344L256 488C256 501.3 266.7 512 280 512C293.3 512 304 501.3 304 488L304 464L328 464C367.8 464 400 431.8 400 392C400 352.2 367.8 320 328 320L280 320zM328 416L304 416L304 368L328 368C341.3 368 352 378.7 352 392C352 405.3 341.3 416 328 416z"/></svg>`,"file-video":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free 7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M128 128C128 92.7 156.7 64 192 64L341.5 64C358.5 64 374.8 70.7 386.8 82.7L493.3 189.3C505.3 201.3 512 217.6 512 234.6L512 512C512 547.3 483.3 576 448 576L192 576C156.7 576 128 547.3 128 512L128 128zM336 122.5L336 216C336 229.3 346.7 240 360 240L453.5 240L336 122.5zM208 368L208 464C208 481.7 222.3 496 240 496L336 496C353.7 496 368 481.7 368 464L368 440L403 475C406.2 478.2 410.5 480 415 480C424.4 480 432 472.4 432 463L432 368.9C432 359.5 424.4 351.9 415 351.9C410.5 351.9 406.2 353.7 403 356.9L368 391.9L368 367.9C368 350.2 353.7 335.9 336 335.9L240 335.9C222.3 335.9 208 350.2 208 367.9z"/></svg>`,"file-word":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free 7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M128 128C128 92.7 156.7 64 192 64L341.5 64C358.5 64 374.8 70.7 386.8 82.7L493.3 189.3C505.3 201.3 512 217.6 512 234.6L512 512C512 547.3 483.3 576 448 576L192 576C156.7 576 128 547.3 128 512L128 128zM336 122.5L336 216C336 229.3 346.7 240 360 240L453.5 240L336 122.5zM263.4 338.8C260.5 325.9 247.7 317.7 234.8 320.6C221.9 323.5 213.7 336.3 216.6 349.2L248.6 493.2C250.9 503.7 260 511.4 270.8 512C281.6 512.6 291.4 505.9 294.8 495.6L320 419.9L345.2 495.6C348.6 505.8 358.4 512.5 369.2 512C380 511.5 389.1 503.8 391.4 493.2L423.4 349.2C426.3 336.3 418.1 323.4 405.2 320.6C392.3 317.8 379.4 325.9 376.6 338.8L363.4 398.2L342.8 336.4C339.5 326.6 330.4 320 320 320C309.6 320 300.5 326.6 297.2 336.4L276.6 398.2L263.4 338.8z"/></svg>`,"file-zipper":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free 7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M128 128C128 92.7 156.7 64 192 64L341.5 64C358.5 64 374.8 70.7 386.8 82.7L493.3 189.3C505.3 201.3 512 217.6 512 234.6L512 512C512 547.3 483.3 576 448 576L192 576C156.7 576 128 547.3 128 512L128 128zM336 122.5L336 216C336 229.3 346.7 240 360 240L453.5 240L336 122.5zM192 136C192 149.3 202.7 160 216 160L264 160C277.3 160 288 149.3 288 136C288 122.7 277.3 112 264 112L216 112C202.7 112 192 122.7 192 136zM192 232C192 245.3 202.7 256 216 256L264 256C277.3 256 288 245.3 288 232C288 218.7 277.3 208 264 208L216 208C202.7 208 192 218.7 192 232zM256 304L224 304C206.3 304 192 318.3 192 336L192 384C192 410.5 213.5 432 240 432C266.5 432 288 410.5 288 384L288 336C288 318.3 273.7 304 256 304zM240 368C248.8 368 256 375.2 256 384C256 392.8 248.8 400 240 400C231.2 400 224 392.8 224 384C224 375.2 231.2 368 240 368z"/></svg>`,"forward-step":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M21 36.8c12.9-7 28.7-6.3 41 1.8L320 208.1 320 64c0-17.7 14.3-32 32-32s32 14.3 32 32l0 384c0 17.7-14.3 32-32 32s-32-14.3-32-32l0-144.1-258 169.6c-12.3 8.1-28 8.8-41 1.8S0 454.7 0 440L0 72C0 57.3 8.1 43.8 21 36.8z"/></svg>`,gauge:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0zm320 96c0-26.9-16.5-49.9-40-59.3L280 120c0-13.3-10.7-24-24-24s-24 10.7-24 24l0 172.7c-23.5 9.5-40 32.5-40 59.3 0 35.3 28.7 64 64 64s64-28.7 64-64zM144 176a32 32 0 1 0 0-64 32 32 0 1 0 0 64zm-16 80a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zm288 32a32 32 0 1 0 0-64 32 32 0 1 0 0 64zM400 144a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z"/></svg>`,gear:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M259.1 73.5C262.1 58.7 275.2 48 290.4 48L350.2 48C365.4 48 378.5 58.7 381.5 73.5L396 143.5C410.1 149.5 423.3 157.2 435.3 166.3L503.1 143.8C517.5 139 533.3 145 540.9 158.2L570.8 210C578.4 223.2 575.7 239.8 564.3 249.9L511 297.3C511.9 304.7 512.3 312.3 512.3 320C512.3 327.7 511.8 335.3 511 342.7L564.4 390.2C575.8 400.3 578.4 417 570.9 430.1L541 481.9C533.4 495 517.6 501.1 503.2 496.3L435.4 473.8C423.3 482.9 410.1 490.5 396.1 496.6L381.7 566.5C378.6 581.4 365.5 592 350.4 592L290.6 592C275.4 592 262.3 581.3 259.3 566.5L244.9 496.6C230.8 490.6 217.7 482.9 205.6 473.8L137.5 496.3C123.1 501.1 107.3 495.1 99.7 481.9L69.8 430.1C62.2 416.9 64.9 400.3 76.3 390.2L129.7 342.7C128.8 335.3 128.4 327.7 128.4 320C128.4 312.3 128.9 304.7 129.7 297.3L76.3 249.8C64.9 239.7 62.3 223 69.8 209.9L99.7 158.1C107.3 144.9 123.1 138.9 137.5 143.7L205.3 166.2C217.4 157.1 230.6 149.5 244.6 143.4L259.1 73.5zM320.3 400C364.5 399.8 400.2 363.9 400 319.7C399.8 275.5 363.9 239.8 319.7 240C275.5 240.2 239.8 276.1 240 320.3C240.2 364.5 276.1 400.2 320.3 400z"/></svg>`,"grip-vertical":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M128 40c0-22.1-17.9-40-40-40L40 0C17.9 0 0 17.9 0 40L0 88c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zm0 192c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zM0 424l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zM320 40c0-22.1-17.9-40-40-40L232 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zM192 232l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zM320 424c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48z"/></svg>`,indeterminate:`<svg part="indeterminate-icon" class="icon" viewBox="0 0 16 16"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" stroke-linecap="round"><g stroke="currentColor" stroke-width="2"><g transform="translate(2.285714 6.857143)"><path d="M10.2857143,1.14285714 L1.14285714,1.14285714"/></g></g></g></svg>`,minus:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M0 256c0-17.7 14.3-32 32-32l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 288c-17.7 0-32-14.3-32-32z"/></svg>`,pause:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M48 32C21.5 32 0 53.5 0 80L0 432c0 26.5 21.5 48 48 48l64 0c26.5 0 48-21.5 48-48l0-352c0-26.5-21.5-48-48-48L48 32zm224 0c-26.5 0-48 21.5-48 48l0 352c0 26.5 21.5 48 48 48l64 0c26.5 0 48-21.5 48-48l0-352c0-26.5-21.5-48-48-48l-64 0z"/></svg>`,"picture-in-picture":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M448 32c35.3 0 64 28.7 64 64l0 112-64 0 0-112-384 0 0 320 144 0 0 64-144 0-6.5-.3c-30.1-3.1-54.1-27-57.1-57.1L0 416 0 96C0 62.9 25.2 35.6 57.5 32.3L64 32 448 32zm16 224c26.5 0 48 21.5 48 48l0 128c0 26.5-21.5 48-48 48l-160 0c-26.5 0-48-21.5-48-48l0-128c0-26.5 21.5-48 48-48l160 0z"/></svg>`,play:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M91.2 36.9c-12.4-6.8-27.4-6.5-39.6 .7S32 57.9 32 72l0 368c0 14.1 7.5 27.2 19.6 34.4s27.2 7.5 39.6 .7l336-184c12.8-7 20.8-20.5 20.8-35.1s-8-28.1-20.8-35.1l-336-184z"/></svg>`,"play-circle":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0zM188.3 147.1c-7.6 4.2-12.3 12.3-12.3 20.9l0 176c0 8.7 4.7 16.7 12.3 20.9s16.8 4.1 24.3-.5l144-88c7.1-4.4 11.5-12.1 11.5-20.5s-4.4-16.1-11.5-20.5l-144-88c-7.4-4.5-16.7-4.7-24.3-.5z"/></svg>`,plus:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free 7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M352 128C352 110.3 337.7 96 320 96C302.3 96 288 110.3 288 128L288 288L128 288C110.3 288 96 302.3 96 320C96 337.7 110.3 352 128 352L288 352L288 512C288 529.7 302.3 544 320 544C337.7 544 352 529.7 352 512L352 352L512 352C529.7 352 544 337.7 544 320C544 302.3 529.7 288 512 288L352 288L352 128z"/></svg>`,star:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M309.5-18.9c-4.1-8-12.4-13.1-21.4-13.1s-17.3 5.1-21.4 13.1L193.1 125.3 33.2 150.7c-8.9 1.4-16.3 7.7-19.1 16.3s-.5 18 5.8 24.4l114.4 114.5-25.2 159.9c-1.4 8.9 2.3 17.9 9.6 23.2s16.9 6.1 25 2L288.1 417.6 432.4 491c8 4.1 17.7 3.3 25-2s11-14.2 9.6-23.2L441.7 305.9 556.1 191.4c6.4-6.4 8.6-15.8 5.8-24.4s-10.1-14.9-19.1-16.3L383 125.3 309.5-18.9z"/></svg>`,upload:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free 7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M352 173.3L352 384C352 401.7 337.7 416 320 416C302.3 416 288 401.7 288 384L288 173.3L246.6 214.7C234.1 227.2 213.8 227.2 201.3 214.7C188.8 202.2 188.8 181.9 201.3 169.4L297.3 73.4C309.8 60.9 330.1 60.9 342.6 73.4L438.6 169.4C451.1 181.9 451.1 202.2 438.6 214.7C426.1 227.2 405.8 227.2 393.3 214.7L352 173.3zM320 464C364.2 464 400 428.2 400 384L480 384C515.3 384 544 412.7 544 448L544 480C544 515.3 515.3 544 480 544L160 544C124.7 544 96 515.3 96 480L96 448C96 412.7 124.7 384 160 384L240 384C240 428.2 275.8 464 320 464zM464 488C477.3 488 488 477.3 488 464C488 450.7 477.3 440 464 440C450.7 440 440 450.7 440 464C440 477.3 450.7 488 464 488z"/></svg>`,user:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M224 248a120 120 0 1 0 0-240 120 120 0 1 0 0 240zm-29.7 56C95.8 304 16 383.8 16 482.3 16 498.7 29.3 512 45.7 512l356.6 0c16.4 0 29.7-13.3 29.7-29.7 0-98.5-79.8-178.3-178.3-178.3l-59.4 0z"/></svg>`,volume:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M48 352l48 0 134.1 119.2c6.4 5.7 14.6 8.8 23.1 8.8 19.2 0 34.8-15.6 34.8-34.8l0-378.4c0-19.2-15.6-34.8-34.8-34.8-8.5 0-16.7 3.1-23.1 8.8L96 160 48 160c-26.5 0-48 21.5-48 48l0 96c0 26.5 21.5 48 48 48zM441.1 107c-10.3-8.4-25.4-6.8-33.8 3.5s-6.8 25.4 3.5 33.8C443.3 170.7 464 210.9 464 256s-20.7 85.3-53.2 111.8c-10.3 8.4-11.8 23.5-3.5 33.8s23.5 11.8 33.8 3.5c43.2-35.2 70.9-88.9 70.9-149s-27.7-113.8-70.9-149zm-60.5 74.5c-10.3-8.4-25.4-6.8-33.8 3.5s-6.8 25.4 3.5 33.8C361.1 227.6 368 241 368 256s-6.9 28.4-17.7 37.3c-10.3 8.4-11.8 23.5-3.5 33.8s23.5 11.8 33.8 3.5C402.1 312.9 416 286.1 416 256s-13.9-56.9-35.5-74.5z"/></svg>`,"volume-low":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M48 352l48 0 134.1 119.2c6.4 5.7 14.6 8.8 23.1 8.8 19.2 0 34.8-15.6 34.8-34.8l0-378.4c0-19.2-15.6-34.8-34.8-34.8-8.5 0-16.7 3.1-23.1 8.8L96 160 48 160c-26.5 0-48 21.5-48 48l0 96c0 26.5 21.5 48 48 48zM380.6 181.5c-10.3-8.4-25.4-6.8-33.8 3.5s-6.8 25.4 3.5 33.8C361.1 227.6 368 241 368 256s-6.9 28.4-17.7 37.3c-10.3 8.4-11.8 23.5-3.5 33.8s23.5 11.8 33.8 3.5C402.1 312.9 416 286.1 416 256s-13.9-56.9-35.5-74.5z"/></svg>`,"volume-xmark":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="currentColor" d="M48 352l48 0 134.1 119.2c6.4 5.7 14.6 8.8 23.1 8.8 19.2 0 34.8-15.6 34.8-34.8l0-378.4c0-19.2-15.6-34.8-34.8-34.8-8.5 0-16.7 3.1-23.1 8.8L96 160 48 160c-26.5 0-48 21.5-48 48l0 96c0 26.5 21.5 48 48 48zM367 175c-9.4 9.4-9.4 24.6 0 33.9l47 47-47 47c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l47-47 47 47c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-47-47 47-47c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-47 47-47-47c-9.4-9.4-24.6-9.4-33.9 0z"/></svg>`,xmark:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M55.1 73.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L147.2 256 9.9 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192.5 301.3 329.9 438.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.8 256 375.1 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192.5 210.7 55.1 73.4z"/></svg>`},regular:{"circle-question":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M464 256a208 208 0 1 0 -416 0 208 208 0 1 0 416 0zM0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0zm256-80c-17.7 0-32 14.3-32 32 0 13.3-10.7 24-24 24s-24-10.7-24-24c0-44.2 35.8-80 80-80s80 35.8 80 80c0 47.2-36 67.2-56 74.5l0 3.8c0 13.3-10.7 24-24 24s-24-10.7-24-24l0-8.1c0-20.5 14.8-35.2 30.1-40.2 6.4-2.1 13.2-5.5 18.2-10.3 4.3-4.2 7.7-10 7.7-19.6 0-17.7-14.3-32-32-32zM224 368a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>`,"circle-xmark":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M256 48a208 208 0 1 1 0 416 208 208 0 1 1 0-416zm0 464a256 256 0 1 0 0-512 256 256 0 1 0 0 512zM167 167c-9.4 9.4-9.4 24.6 0 33.9l55 55-55 55c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l55-55 55 55c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-55-55 55-55c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-55 55-55-55c-9.4-9.4-24.6-9.4-33.9 0z"/></svg>`,copy:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M384 336l-192 0c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l133.5 0c4.2 0 8.3 1.7 11.3 4.7l58.5 58.5c3 3 4.7 7.1 4.7 11.3L400 320c0 8.8-7.2 16-16 16zM192 384l192 0c35.3 0 64-28.7 64-64l0-197.5c0-17-6.7-33.3-18.7-45.3L370.7 18.7C358.7 6.7 342.5 0 325.5 0L192 0c-35.3 0-64 28.7-64 64l0 256c0 35.3 28.7 64 64 64zM64 128c-35.3 0-64 28.7-64 64L0 448c0 35.3 28.7 64 64 64l192 0c35.3 0 64-28.7 64-64l0-16-48 0 0 16c0 8.8-7.2 16-16 16L64 464c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l16 0 0-48-16 0z"/></svg>`,eye:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M288 80C222.8 80 169.2 109.6 128.1 147.7 89.6 183.5 63 226 49.4 256 63 286 89.6 328.5 128.1 364.3 169.2 402.4 222.8 432 288 432s118.8-29.6 159.9-67.7C486.4 328.5 513 286 526.6 256 513 226 486.4 183.5 447.9 147.7 406.8 109.6 353.2 80 288 80zM95.4 112.6C142.5 68.8 207.2 32 288 32s145.5 36.8 192.6 80.6c46.8 43.5 78.1 95.4 93 131.1 3.3 7.9 3.3 16.7 0 24.6-14.9 35.7-46.2 87.7-93 131.1-47.1 43.7-111.8 80.6-192.6 80.6S142.5 443.2 95.4 399.4c-46.8-43.5-78.1-95.4-93-131.1-3.3-7.9-3.3-16.7 0-24.6 14.9-35.7 46.2-87.7 93-131.1zM288 336c44.2 0 80-35.8 80-80 0-29.6-16.1-55.5-40-69.3-1.4 59.7-49.6 107.9-109.3 109.3 13.8 23.9 39.7 40 69.3 40zm-79.6-88.4c2.5 .3 5 .4 7.6 .4 35.3 0 64-28.7 64-64 0-2.6-.2-5.1-.4-7.6-37.4 3.9-67.2 33.7-71.1 71.1zm45.6-115c10.8-3 22.2-4.5 33.9-4.5 8.8 0 17.5 .9 25.8 2.6 .3 .1 .5 .1 .8 .2 57.9 12.2 101.4 63.7 101.4 125.2 0 70.7-57.3 128-128 128-61.6 0-113-43.5-125.2-101.4-1.8-8.6-2.8-17.5-2.8-26.6 0-11 1.4-21.8 4-32 .2-.7 .3-1.3 .5-1.9 11.9-43.4 46.1-77.6 89.5-89.5z"/></svg>`,"eye-slash":`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M41-24.9c-9.4-9.4-24.6-9.4-33.9 0S-2.3-.3 7 9.1l528 528c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-96.4-96.4c2.7-2.4 5.4-4.8 8-7.2 46.8-43.5 78.1-95.4 93-131.1 3.3-7.9 3.3-16.7 0-24.6-14.9-35.7-46.2-87.7-93-131.1-47.1-43.7-111.8-80.6-192.6-80.6-56.8 0-105.6 18.2-146 44.2L41-24.9zM176.9 111.1c32.1-18.9 69.2-31.1 111.1-31.1 65.2 0 118.8 29.6 159.9 67.7 38.5 35.7 65.1 78.3 78.6 108.3-13.6 30-40.2 72.5-78.6 108.3-3.1 2.8-6.2 5.6-9.4 8.4L393.8 328c14-20.5 22.2-45.3 22.2-72 0-70.7-57.3-128-128-128-26.7 0-51.5 8.2-72 22.2l-39.1-39.1zm182 182l-108-108c11.1-5.8 23.7-9.1 37.1-9.1 44.2 0 80 35.8 80 80 0 13.4-3.3 26-9.1 37.1zM103.4 173.2l-34-34c-32.6 36.8-55 75.8-66.9 104.5-3.3 7.9-3.3 16.7 0 24.6 14.9 35.7 46.2 87.7 93 131.1 47.1 43.7 111.8 80.6 192.6 80.6 37.3 0 71.2-7.9 101.5-20.6L352.2 422c-20 6.4-41.4 10-64.2 10-65.2 0-118.8-29.6-159.9-67.7-38.5-35.7-65.1-78.3-78.6-108.3 10.4-23.1 28.6-53.6 54-82.8z"/></svg>`,star:`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M288.1-32c9 0 17.3 5.1 21.4 13.1L383 125.3 542.9 150.7c8.9 1.4 16.3 7.7 19.1 16.3s.5 18-5.8 24.4L441.7 305.9 467 465.8c1.4 8.9-2.3 17.9-9.6 23.2s-17 6.1-25 2L288.1 417.6 143.8 491c-8 4.1-17.7 3.3-25-2s-11-14.2-9.6-23.2L134.4 305.9 20 191.4c-6.4-6.4-8.6-15.8-5.8-24.4s10.1-14.9 19.1-16.3l159.9-25.4 73.6-144.2c4.1-8 12.4-13.1 21.4-13.1zm0 76.8L230.3 158c-3.5 6.8-10 11.6-17.6 12.8l-125.5 20 89.8 89.9c5.4 5.4 7.9 13.1 6.7 20.7l-19.8 125.5 113.3-57.6c6.8-3.5 14.9-3.5 21.8 0l113.3 57.6-19.8-125.5c-1.2-7.6 1.3-15.3 6.7-20.7l89.8-89.9-125.5-20c-7.6-1.2-14.1-6-17.6-12.8L288.1 44.8z"/></svg>`}},fi={name:`system`,resolver:(e,t=`classic`,n=`solid`)=>{let r=di[n][e]??di.regular[e]??di.regular[`circle-question`];return r?ui(r):``}},pi=`classic`,mi=[li,fi],hi=[];function gi(e){hi.push(e)}function _i(e){hi=hi.filter(t=>t!==e)}function vi(e){return mi.find(t=>t.name===e)}function yi(e,t){bi(e),mi.push({name:e,resolver:t.resolver,mutator:t.mutator,spriteSheet:t.spriteSheet}),hi.forEach(t=>{t.library===e&&t.setIcon()})}function bi(e){mi=mi.filter(t=>t.name!==e)}function xi(){return pi}var Si=Object.defineProperty,Ci=Object.getOwnPropertyDescriptor,wi=e=>{throw TypeError(e)},N=(e,t,n,r)=>{for(var i=r>1?void 0:r?Ci(t,n):t,a=e.length-1,o;a>=0;a--)(o=e[a])&&(i=(r?o(t,n,i):o(i))||i);return r&&i&&Si(t,n,i),i},Ti=(e,t,n)=>t.has(e)||wi(`Cannot `+n),Ei=(e,t,n)=>(Ti(e,t,`read from private field`),n?n.call(e):t.get(e)),Di=(e,t,n)=>t.has(e)?wi(`Cannot add the same private member more than once`):t instanceof WeakSet?t.add(e):t.set(e,n),Oi=(e,t,n,r)=>(Ti(e,t,`write to private field`),r?r.call(e,n):t.set(e,n),n),ki={alert:`triangle-exclamation`,asc:`arrow-down-short-wide`,asset:`image`,assets:`image`,circleuarr:`circle-arrow-up`,collapse:`down-left-and-up-right-to-center`,condition:`diamond`,darr:`arrow-down`,date:`calendar`,desc:`arrow-down-wide-short`,disabled:`circle-dashed`,done:`circle-check`,downangle:`angle-down`,draft:`scribble`,edit:`pencil`,enabled:`circle`,expand:`up-right-and-down-left-from-center`,external:`arrow-up-right-from-square`,field:`pen-to-square`,help:`circle-question`,home:`house`,info:`circle-info`,insecure:`unlock`,larr:`arrow-left`,layout:`table-layout`,leftangle:`angle-left`,listrtl:`list-flip`,location:`location-dot`,mail:`envelope`,menu:`bars`,move:`grip-dots`,newstamp:`certificate`,paperplane:`paper-plane`,plugin:`plug`,rarr:`arrow-right`,refresh:`arrows-rotate`,remove:`xmark`,rightangle:`angle-right`,rotate:`rotate-left`,routes:`signs-post`,search:`magnifying-glass`,secure:`lock`,settings:`gear`,shareleft:`share-flip`,shuteye:`eye-slash`,"sidebar-left":`sidebar`,"sidebar-right":`sidebar-flip`,"sidebar-start":`sidebar`,"sidebar-end":`sidebar-flip`,structure:`list-tree`,structurertl:`list-tree-flip`,template:`file-code`,time:`clock`,tool:`wrench`,uarr:`arrow-up`,upangle:`angle-up`,view:`eye`,wand:`wand-magic-sparkles`};function Ai(e,t=`classic`,n=`regular`){let r=`solid`,i=n,a=e.endsWith(`.svg`)?e.split(`.svg`)[0]:e;if(e.includes(`/`)){let[t,...n]=e.split(`/`);i=t??i,a=n.join(`/`)}return i===`thin`?r=`thin`:i===`light`?r=`light`:i===`regular`?r=`regular`:i===`solid`&&(r=`solid`),t===`brands`&&(r=`brands`),(t===`custom-icons`||i===`custom-icons`)&&(r=`custom-icons`),a=ki[a]??a,`/vendor/craft/icons/${r}/${a}.svg`}function ji(){yi(`default`,{resolver:(e,t=`classic`,n=`solid`)=>Ai(e,t,n),mutator:e=>e.setAttribute(`fill`,`currentColor`)})}var Mi=class extends HTMLElement{constructor(...e){super(...e),this.cookieName=null,this.state=`collapsed`,this.expanded=!1,this.handleOpen=()=>{this.trigger?.setAttribute(`aria-expanded`,`true`),this.expanded=!0,this.dispatchEvent(new CustomEvent(`open`)),this.target&&(this.target.dataset.state=`expanded`),this.cookieName&&window.Craft?.setCookie(this.cookieName,`expanded`)},this.handleClose=()=>{this.trigger?.setAttribute(`aria-expanded`,`false`),this.expanded=!1,this.dispatchEvent(new CustomEvent(`close`)),this.target&&(this.target.dataset.state=`collapsed`),this.cookieName&&window.Craft?.setCookie(this.cookieName,`collapsed`)}}get trigger(){return this.querySelector(`button[type="button"]`)}get target(){if(!this.trigger)return console.warn(`No trigger found for disclosure.`),null;let e=this.trigger.getAttribute(`aria-controls`);return e?document.getElementById(e):(console.warn(`No target selector found for disclosure.`),null)}connectedCallback(){if(!this.trigger){console.error(`craft-disclosure elements must include a button`,this);return}if(!this.target){console.error(`No target with id ${this.trigger.getAttribute(`aria-controls`)} found for disclosure. `,this.trigger);return}this.cookieName=this.getAttribute(`cookie-name`),this.state=this.getAttribute(`state`)??`expanded`,this.trigger.setAttribute(`aria-expanded`,this.state===`expanded`?`true`:`false`),this.trigger.addEventListener(`click`,this.toggle.bind(this)),this.state===`expanded`?this.open():this.close()}disconnectedCallback(){this.open(),this.trigger?.removeEventListener(`click`,this.toggle.bind(this))}attributeChangedCallback(e,t,n){e===`state`&&(n===`expanded`?this.handleOpen():this.handleClose())}toggle(){this.expanded?this.close():this.open()}open(){this.setAttribute(`state`,`expanded`)}close(){this.setAttribute(`state`,`collapsed`)}};Mi.observedAttributes=[`state`],customElements.get(`craft-disclosure`)||customElements.define(`craft-disclosure`,Mi);var Ni=class extends Event{constructor(){super(`wa-error`,{bubbles:!0,cancelable:!1,composed:!0})}},Pi=d`
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
`,Fi=class extends Event{constructor(){super(`wa-load`,{bubbles:!0,cancelable:!1,composed:!0})}};function P(e,t){let n={waitUntilFirstUpdate:!1,...t};return(t,r)=>{let{update:i}=t,a=Array.isArray(e)?e:[e];t.update=function(e){a.forEach(t=>{let i=t;if(e.has(i)){let t=e.get(i),a=this[i];t!==a&&(!n.waitUntilFirstUpdate||this.hasUpdated)&&this[r](t,a)}}),i.call(this,e)}}}var Ii=d`
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
`,Li,Ri=class extends h{constructor(){super(),Di(this,Li,!1),this.initialReflectedProperties=new Map,this.didSSR=!!this.shadowRoot,this.customStates={set:(e,t)=>{if(this.internals?.states)try{t?this.internals.states.add(e):this.internals.states.delete(e)}catch(e){if(String(e).includes(`must start with '--'`))console.error(`Your browser implements an outdated version of CustomStateSet. Consider using a polyfill`);else throw e}},has:e=>{if(!this.internals?.states)return!1;try{return this.internals.states.has(e)}catch{return!1}}};try{this.internals=this.attachInternals()}catch{console.error(`Element internals are not supported in your browser. Consider using a polyfill`)}this.customStates.set(`wa-defined`,!0);let e=this.constructor;for(let[t,n]of e.elementProperties)n.default===`inherit`&&n.initial!==void 0&&typeof t==`string`&&this.customStates.set(`initial-${t}-${n.initial}`,!0)}static get styles(){return[Ii,...Array.isArray(this.css)?this.css:this.css?[this.css]:[]]}connectedCallback(){super.connectedCallback(),this.shadowRoot?.prepend(document.createComment(` Web Awesome: https://webawesome.com/docs/components/${this.localName.replace(`wa-`,``)} `))}attributeChangedCallback(e,t,n){Ei(this,Li)||(this.constructor.elementProperties.forEach((e,t)=>{e.reflect&&this[t]!=null&&this.initialReflectedProperties.set(t,this[t])}),Oi(this,Li,!0)),super.attributeChangedCallback(e,t,n)}willUpdate(e){super.willUpdate(e),this.initialReflectedProperties.forEach((t,n)=>{e.has(n)&&this[n]==null&&(this[n]=t)})}firstUpdated(e){super.firstUpdated(e),this.didSSR&&this.shadowRoot?.querySelectorAll(`slot`).forEach(e=>{e.dispatchEvent(new Event(`slotchange`,{bubbles:!0,composed:!1,cancelable:!1}))})}update(e){try{super.update(e)}catch(e){if(this.didSSR&&!this.hasUpdated){let t=new Event(`lit-hydration-error`,{bubbles:!0,composed:!0,cancelable:!1});t.error=e,this.dispatchEvent(t)}throw e}}relayNativeEvent(e,t){e.stopImmediatePropagation(),this.dispatchEvent(new e.constructor(e.type,{...e,...t}))}};Li=new WeakMap,N([g()],Ri.prototype,`dir`,2),N([g()],Ri.prototype,`lang`,2),N([g({type:Boolean,reflect:!0,attribute:`did-ssr`})],Ri.prototype,`didSSR`,2);var{I:zi}=c,Bi=e=>e===null||typeof e!=`object`&&typeof e!=`function`,Vi=(e,t)=>t===void 0?e?._$litType$!==void 0:e?._$litType$===t,Hi=e=>e.strings===void 0,Ui=()=>document.createComment(``),Wi=(e,t,n)=>{let r=e._$AA.parentNode,i=t===void 0?e._$AB:t._$AA;if(n===void 0)n=new zi(r.insertBefore(Ui(),i),r.insertBefore(Ui(),i),e,e.options);else{let t=n._$AB.nextSibling,a=n._$AM,o=a!==e;if(o){let t;n._$AQ?.(e),n._$AM=e,n._$AP!==void 0&&(t=e._$AU)!==a._$AU&&n._$AP(t)}if(t!==i||o){let e=n._$AA;for(;e!==t;){let t=e.nextSibling;r.insertBefore(e,i),e=t}}}return n},Gi=(e,t,n=e)=>(e._$AI(t,n),e),Ki={},qi=(e,t=Ki)=>e._$AH=t,Ji=e=>e._$AH,Yi=e=>{e._$AR(),e._$AA.remove()},Xi=Symbol(),Zi=Symbol(),Qi,$i=new Map,F=class extends Ri{constructor(){super(...arguments),this.svg=null,this.autoWidth=!1,this.swapOpacity=!1,this.label=``,this.library=`default`,this.rotate=0,this.resolveIcon=async(e,t)=>{let n;if(t?.spriteSheet){this.hasUpdated||await this.updateComplete,this.svg=l`<svg part="svg">
        <use part="use" href="${e}"></use>
      </svg>`,await this.updateComplete;let n=this.shadowRoot.querySelector(`[part='svg']`);return typeof t.mutator==`function`&&t.mutator(n,this),this.svg}try{if(n=await fetch(e,{mode:`cors`}),!n.ok)return n.status===410?Xi:Zi}catch{return Zi}try{let e=document.createElement(`div`);e.innerHTML=await n.text();let t=e.firstElementChild;if(t?.tagName?.toLowerCase()!==`svg`)return Xi;Qi||=new DOMParser;let r=Qi.parseFromString(t.outerHTML,`text/html`).body.querySelector(`svg`);return r?(r.part.add(`svg`),document.adoptNode(r)):Xi}catch{return Xi}}}connectedCallback(){super.connectedCallback(),gi(this)}firstUpdated(e){super.firstUpdated(e),this.hasAttribute(`rotate`)&&this.style.setProperty(`--rotate-angle`,`${this.rotate}deg`),this.setIcon()}disconnectedCallback(){super.disconnectedCallback(),_i(this)}async getIconSource(){let e=vi(this.library),t=this.family||xi();if(this.name&&e){let n;try{n=await e.resolver(this.name,t,this.variant,this.autoWidth)}catch{n=void 0}return{url:n,fromLibrary:!0}}return{url:this.src,fromLibrary:!1}}handleLabelChange(){typeof this.label==`string`&&this.label.length>0?(this.setAttribute(`role`,`img`),this.setAttribute(`aria-label`,this.label),this.removeAttribute(`aria-hidden`)):(this.removeAttribute(`role`),this.removeAttribute(`aria-label`),this.setAttribute(`aria-hidden`,`true`))}async setIcon(){let{url:e,fromLibrary:t}=await this.getIconSource(),n=t?vi(this.library):void 0;if(!e){this.svg=null;return}let r=$i.get(e);r||(r=this.resolveIcon(e,n),$i.set(e,r));let i=await r;if(i===Zi&&$i.delete(e),e===(await this.getIconSource()).url){if(Vi(i)){this.svg=i;return}switch(i){case Zi:case Xi:this.svg=null,this.dispatchEvent(new Ni);break;default:this.svg=i.cloneNode(!0),n?.mutator?.(this.svg,this),this.dispatchEvent(new Fi)}}}updated(e){super.updated(e);let t=vi(this.library);this.hasAttribute(`rotate`)&&this.style.setProperty(`--rotate-angle`,`${this.rotate}deg`);let n=this.shadowRoot?.querySelector(`svg`);n&&t?.mutator?.(n,this)}render(){return this.hasUpdated?this.svg:l`<svg part="svg" width="16" height="16"></svg>`}};F.css=Pi,N([_()],F.prototype,`svg`,2),N([g({reflect:!0})],F.prototype,`name`,2),N([g({reflect:!0})],F.prototype,`family`,2),N([g({reflect:!0})],F.prototype,`variant`,2),N([g({attribute:`auto-width`,type:Boolean,reflect:!0})],F.prototype,`autoWidth`,2),N([g({attribute:`swap-opacity`,type:Boolean,reflect:!0})],F.prototype,`swapOpacity`,2),N([g()],F.prototype,`src`,2),N([g()],F.prototype,`label`,2),N([g({reflect:!0})],F.prototype,`library`,2),N([g({type:Number,reflect:!0})],F.prototype,`rotate`,2),N([g({type:String,reflect:!0})],F.prototype,`flip`,2),N([g({type:String,reflect:!0})],F.prototype,`animation`,2),N([P(`label`)],F.prototype,`handleLabelChange`,1),N([P([`family`,`name`,`library`,`variant`,`src`,`autoWidth`,`swapOpacity`],{waitUntilFirstUpdate:!0})],F.prototype,`setIcon`,1),F=N([v(`wa-icon`)],F);var ea=class extends F{constructor(...e){super(...e),this.appearance=`plain`}connectedCallback(){super.connectedCallback(),this.appearance===`badge`&&!this.getAttribute(`data-color`)&&this.setAttribute(`data-color`,`warning`)}static get styles(){return[F.styles,d`
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
      `]}};o([g({reflect:!0})],ea.prototype,`appearance`,void 0),customElements.get(`craft-icon`)||customElements.define(`craft-icon`,ea);var ta=d`
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
`,na=class extends Event{constructor(){super(`wa-reposition`,{bubbles:!0,cancelable:!1,composed:!0})}},ra=d`
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
`,ia=Math.min,aa=Math.max,oa=Math.round,sa=Math.floor,ca=e=>({x:e,y:e}),la={left:`right`,right:`left`,bottom:`top`,top:`bottom`};function ua(e,t,n){return aa(e,ia(t,n))}function da(e,t){return typeof e==`function`?e(t):e}function fa(e){return e.split(`-`)[0]}function pa(e){return e.split(`-`)[1]}function ma(e){return e===`x`?`y`:`x`}function ha(e){return e===`y`?`height`:`width`}function ga(e){let t=e[0];return t===`t`||t===`b`?`y`:`x`}function _a(e){return ma(ga(e))}function va(e,t,n){n===void 0&&(n=!1);let r=pa(e),i=_a(e),a=ha(i),o=i===`x`?r===(n?`end`:`start`)?`right`:`left`:r===`start`?`bottom`:`top`;return t.reference[a]>t.floating[a]&&(o=Da(o)),[o,Da(o)]}function ya(e){let t=Da(e);return[ba(e),t,ba(t)]}function ba(e){return e.includes(`start`)?e.replace(`start`,`end`):e.replace(`end`,`start`)}var xa=[`left`,`right`],Sa=[`right`,`left`],Ca=[`top`,`bottom`],wa=[`bottom`,`top`];function Ta(e,t,n){switch(e){case`top`:case`bottom`:return n?t?Sa:xa:t?xa:Sa;case`left`:case`right`:return t?Ca:wa;default:return[]}}function Ea(e,t,n,r){let i=pa(e),a=Ta(fa(e),n===`start`,r);return i&&(a=a.map(e=>e+`-`+i),t&&(a=a.concat(a.map(ba)))),a}function Da(e){let t=fa(e);return la[t]+e.slice(t.length)}function Oa(e){return{top:0,right:0,bottom:0,left:0,...e}}function ka(e){return typeof e==`number`?{top:e,right:e,bottom:e,left:e}:Oa(e)}function Aa(e){let{x:t,y:n,width:r,height:i}=e;return{width:r,height:i,top:n,left:t,right:t+r,bottom:n+i,x:t,y:n}}function ja(e,t,n){let{reference:r,floating:i}=e,a=ga(t),o=_a(t),s=ha(o),c=fa(t),l=a===`y`,u=r.x+r.width/2-i.width/2,d=r.y+r.height/2-i.height/2,f=r[s]/2-i[s]/2,p;switch(c){case`top`:p={x:u,y:r.y-i.height};break;case`bottom`:p={x:u,y:r.y+r.height};break;case`right`:p={x:r.x+r.width,y:d};break;case`left`:p={x:r.x-i.width,y:d};break;default:p={x:r.x,y:r.y}}switch(pa(t)){case`start`:p[o]-=f*(n&&l?-1:1);break;case`end`:p[o]+=f*(n&&l?-1:1);break}return p}async function Ma(e,t){t===void 0&&(t={});let{x:n,y:r,platform:i,rects:a,elements:o,strategy:s}=e,{boundary:c=`clippingAncestors`,rootBoundary:l=`viewport`,elementContext:u=`floating`,altBoundary:d=!1,padding:f=0}=da(t,e),p=ka(f),m=o[d?u===`floating`?`reference`:`floating`:u],h=Aa(await i.getClippingRect({element:await(i.isElement==null?void 0:i.isElement(m))??!0?m:m.contextElement||await(i.getDocumentElement==null?void 0:i.getDocumentElement(o.floating)),boundary:c,rootBoundary:l,strategy:s})),g=u===`floating`?{x:n,y:r,width:a.floating.width,height:a.floating.height}:a.reference,_=await(i.getOffsetParent==null?void 0:i.getOffsetParent(o.floating)),v=await(i.isElement==null?void 0:i.isElement(_))&&await(i.getScale==null?void 0:i.getScale(_))||{x:1,y:1},y=Aa(i.convertOffsetParentRelativeRectToViewportRelativeRect?await i.convertOffsetParentRelativeRectToViewportRelativeRect({elements:o,rect:g,offsetParent:_,strategy:s}):g);return{top:(h.top-y.top+p.top)/v.y,bottom:(y.bottom-h.bottom+p.bottom)/v.y,left:(h.left-y.left+p.left)/v.x,right:(y.right-h.right+p.right)/v.x}}var Na=50,Pa=async(e,t,n)=>{let{placement:r=`bottom`,strategy:i=`absolute`,middleware:a=[],platform:o}=n,s=o.detectOverflow?o:{...o,detectOverflow:Ma},c=await(o.isRTL==null?void 0:o.isRTL(t)),l=await o.getElementRects({reference:e,floating:t,strategy:i}),{x:u,y:d}=ja(l,r,c),f=r,p=0,m={};for(let n=0;n<a.length;n++){let h=a[n];if(!h)continue;let{name:g,fn:_}=h,{x:v,y,data:b,reset:ee}=await _({x:u,y:d,initialPlacement:r,placement:f,strategy:i,middlewareData:m,rects:l,platform:s,elements:{reference:e,floating:t}});u=v??u,d=y??d,m[g]={...m[g],...b},ee&&p<Na&&(p++,typeof ee==`object`&&(ee.placement&&(f=ee.placement),ee.rects&&(l=ee.rects===!0?await o.getElementRects({reference:e,floating:t,strategy:i}):ee.rects),{x:u,y:d}=ja(l,f,c)),n=-1)}return{x:u,y:d,placement:f,strategy:i,middlewareData:m}},Fa=e=>({name:`arrow`,options:e,async fn(t){let{x:n,y:r,placement:i,rects:a,platform:o,elements:s,middlewareData:c}=t,{element:l,padding:u=0}=da(e,t)||{};if(l==null)return{};let d=ka(u),f={x:n,y:r},p=_a(i),m=ha(p),h=await o.getDimensions(l),g=p===`y`,_=g?`top`:`left`,v=g?`bottom`:`right`,y=g?`clientHeight`:`clientWidth`,b=a.reference[m]+a.reference[p]-f[p]-a.floating[m],ee=f[p]-a.reference[p],te=await(o.getOffsetParent==null?void 0:o.getOffsetParent(l)),ne=te?te[y]:0;(!ne||!await(o.isElement==null?void 0:o.isElement(te)))&&(ne=s.floating[y]||a.floating[m]);let x=b/2-ee/2,S=ne/2-h[m]/2-1,C=ia(d[_],S),w=ia(d[v],S),T=C,E=ne-h[m]-w,D=ne/2-h[m]/2+x,re=ua(T,D,E),O=!c.arrow&&pa(i)!=null&&D!==re&&a.reference[m]/2-(D<T?C:w)-h[m]/2<0,ie=O?D<T?D-T:D-E:0;return{[p]:f[p]+ie,data:{[p]:re,centerOffset:D-re-ie,...O&&{alignmentOffset:ie}},reset:O}}}),Ia=function(e){return e===void 0&&(e={}),{name:`flip`,options:e,async fn(t){var n;let{placement:r,middlewareData:i,rects:a,initialPlacement:o,platform:s,elements:c}=t,{mainAxis:l=!0,crossAxis:u=!0,fallbackPlacements:d,fallbackStrategy:f=`bestFit`,fallbackAxisSideDirection:p=`none`,flipAlignment:m=!0,...h}=da(e,t);if((n=i.arrow)!=null&&n.alignmentOffset)return{};let g=fa(r),_=ga(o),v=fa(o)===o,y=await(s.isRTL==null?void 0:s.isRTL(c.floating)),b=d||(v||!m?[Da(o)]:ya(o)),ee=p!==`none`;!d&&ee&&b.push(...Ea(o,m,p,y));let te=[o,...b],ne=await s.detectOverflow(t,h),x=[],S=i.flip?.overflows||[];if(l&&x.push(ne[g]),u){let e=va(r,a,y);x.push(ne[e[0]],ne[e[1]])}if(S=[...S,{placement:r,overflows:x}],!x.every(e=>e<=0)){let e=(i.flip?.index||0)+1,t=te[e];if(t&&(!(u===`alignment`&&_!==ga(t))||S.every(e=>ga(e.placement)===_?e.overflows[0]>0:!0)))return{data:{index:e,overflows:S},reset:{placement:t}};let n=S.filter(e=>e.overflows[0]<=0).sort((e,t)=>e.overflows[1]-t.overflows[1])[0]?.placement;if(!n)switch(f){case`bestFit`:{let e=S.filter(e=>{if(ee){let t=ga(e.placement);return t===_||t===`y`}return!0}).map(e=>[e.placement,e.overflows.filter(e=>e>0).reduce((e,t)=>e+t,0)]).sort((e,t)=>e[1]-t[1])[0]?.[0];e&&(n=e);break}case`initialPlacement`:n=o;break}if(r!==n)return{reset:{placement:n}}}return{}}}},La=new Set([`left`,`top`]);async function Ra(e,t){let{placement:n,platform:r,elements:i}=e,a=await(r.isRTL==null?void 0:r.isRTL(i.floating)),o=fa(n),s=pa(n),c=ga(n)===`y`,l=La.has(o)?-1:1,u=a&&c?-1:1,d=da(t,e),{mainAxis:f,crossAxis:p,alignmentAxis:m}=typeof d==`number`?{mainAxis:d,crossAxis:0,alignmentAxis:null}:{mainAxis:d.mainAxis||0,crossAxis:d.crossAxis||0,alignmentAxis:d.alignmentAxis};return s&&typeof m==`number`&&(p=s===`end`?m*-1:m),c?{x:p*u,y:f*l}:{x:f*l,y:p*u}}var za=function(e){return e===void 0&&(e=0),{name:`offset`,options:e,async fn(t){var n;let{x:r,y:i,placement:a,middlewareData:o}=t,s=await Ra(t,e);return a===o.offset?.placement&&(n=o.arrow)!=null&&n.alignmentOffset?{}:{x:r+s.x,y:i+s.y,data:{...s,placement:a}}}}},Ba=function(e){return e===void 0&&(e={}),{name:`shift`,options:e,async fn(t){let{x:n,y:r,placement:i,platform:a}=t,{mainAxis:o=!0,crossAxis:s=!1,limiter:c={fn:e=>{let{x:t,y:n}=e;return{x:t,y:n}}},...l}=da(e,t),u={x:n,y:r},d=await a.detectOverflow(t,l),f=ga(fa(i)),p=ma(f),m=u[p],h=u[f];if(o){let e=p===`y`?`top`:`left`,t=p===`y`?`bottom`:`right`,n=m+d[e],r=m-d[t];m=ua(n,m,r)}if(s){let e=f===`y`?`top`:`left`,t=f===`y`?`bottom`:`right`,n=h+d[e],r=h-d[t];h=ua(n,h,r)}let g=c.fn({...t,[p]:m,[f]:h});return{...g,data:{x:g.x-n,y:g.y-r,enabled:{[p]:o,[f]:s}}}}}},Va=function(e){return e===void 0&&(e={}),{name:`size`,options:e,async fn(t){var n,r;let{placement:i,rects:a,platform:o,elements:s}=t,{apply:c=()=>{},...l}=da(e,t),u=await o.detectOverflow(t,l),d=fa(i),f=pa(i),p=ga(i)===`y`,{width:m,height:h}=a.floating,g,_;d===`top`||d===`bottom`?(g=d,_=f===(await(o.isRTL==null?void 0:o.isRTL(s.floating))?`start`:`end`)?`left`:`right`):(_=d,g=f===`end`?`top`:`bottom`);let v=h-u.top-u.bottom,y=m-u.left-u.right,b=ia(h-u[g],v),ee=ia(m-u[_],y),te=!t.middlewareData.shift,ne=b,x=ee;if((n=t.middlewareData.shift)!=null&&n.enabled.x&&(x=y),(r=t.middlewareData.shift)!=null&&r.enabled.y&&(ne=v),te&&!f){let e=aa(u.left,0),t=aa(u.right,0),n=aa(u.top,0),r=aa(u.bottom,0);p?x=m-2*(e!==0||t!==0?e+t:aa(u.left,u.right)):ne=h-2*(n!==0||r!==0?n+r:aa(u.top,u.bottom))}await c({...t,availableWidth:x,availableHeight:ne});let S=await o.getDimensions(s.floating);return m!==S.width||h!==S.height?{reset:{rects:!0}}:{}}}};function Ha(){return typeof window<`u`}function Ua(e){return Ka(e)?(e.nodeName||``).toLowerCase():`#document`}function Wa(e){var t;return(e==null||(t=e.ownerDocument)==null?void 0:t.defaultView)||window}function Ga(e){return((Ka(e)?e.ownerDocument:e.document)||window.document)?.documentElement}function Ka(e){return Ha()?e instanceof Node||e instanceof Wa(e).Node:!1}function qa(e){return Ha()?e instanceof Element||e instanceof Wa(e).Element:!1}function Ja(e){return Ha()?e instanceof HTMLElement||e instanceof Wa(e).HTMLElement:!1}function Ya(e){return!Ha()||typeof ShadowRoot>`u`?!1:e instanceof ShadowRoot||e instanceof Wa(e).ShadowRoot}function Xa(e){let{overflow:t,overflowX:n,overflowY:r,display:i}=so(e);return/auto|scroll|overlay|hidden|clip/.test(t+r+n)&&i!==`inline`&&i!==`contents`}function Za(e){return/^(table|td|th)$/.test(Ua(e))}function Qa(e){try{if(e.matches(`:popover-open`))return!0}catch{}try{return e.matches(`:modal`)}catch{return!1}}var $a=/transform|translate|scale|rotate|perspective|filter/,eo=/paint|layout|strict|content/,to=e=>!!e&&e!==`none`,no;function ro(e){let t=qa(e)?so(e):e;return to(t.transform)||to(t.translate)||to(t.scale)||to(t.rotate)||to(t.perspective)||!ao()&&(to(t.backdropFilter)||to(t.filter))||$a.test(t.willChange||``)||eo.test(t.contain||``)}function io(e){let t=lo(e);for(;Ja(t)&&!oo(t);){if(ro(t))return t;if(Qa(t))return null;t=lo(t)}return null}function ao(){return no??=typeof CSS<`u`&&CSS.supports&&CSS.supports(`-webkit-backdrop-filter`,`none`),no}function oo(e){return/^(html|body|#document)$/.test(Ua(e))}function so(e){return Wa(e).getComputedStyle(e)}function co(e){return qa(e)?{scrollLeft:e.scrollLeft,scrollTop:e.scrollTop}:{scrollLeft:e.scrollX,scrollTop:e.scrollY}}function lo(e){if(Ua(e)===`html`)return e;let t=e.assignedSlot||e.parentNode||Ya(e)&&e.host||Ga(e);return Ya(t)?t.host:t}function uo(e){let t=lo(e);return oo(t)?e.ownerDocument?e.ownerDocument.body:e.body:Ja(t)&&Xa(t)?t:uo(t)}function fo(e,t,n){t===void 0&&(t=[]),n===void 0&&(n=!0);let r=uo(e),i=r===e.ownerDocument?.body,a=Wa(r);if(i){let e=po(a);return t.concat(a,a.visualViewport||[],Xa(r)?r:[],e&&n?fo(e):[])}else return t.concat(r,fo(r,[],n))}function po(e){return e.parent&&Object.getPrototypeOf(e.parent)?e.frameElement:null}function mo(e){let t=so(e),n=parseFloat(t.width)||0,r=parseFloat(t.height)||0,i=Ja(e),a=i?e.offsetWidth:n,o=i?e.offsetHeight:r,s=oa(n)!==a||oa(r)!==o;return s&&(n=a,r=o),{width:n,height:r,$:s}}function ho(e){return qa(e)?e:e.contextElement}function go(e){let t=ho(e);if(!Ja(t))return ca(1);let n=t.getBoundingClientRect(),{width:r,height:i,$:a}=mo(t),o=(a?oa(n.width):n.width)/r,s=(a?oa(n.height):n.height)/i;return(!o||!Number.isFinite(o))&&(o=1),(!s||!Number.isFinite(s))&&(s=1),{x:o,y:s}}var _o=ca(0);function vo(e){let t=Wa(e);return!ao()||!t.visualViewport?_o:{x:t.visualViewport.offsetLeft,y:t.visualViewport.offsetTop}}function yo(e,t,n){return t===void 0&&(t=!1),!n||t&&n!==Wa(e)?!1:t}function bo(e,t,n,r){t===void 0&&(t=!1),n===void 0&&(n=!1);let i=e.getBoundingClientRect(),a=ho(e),o=ca(1);t&&(r?qa(r)&&(o=go(r)):o=go(e));let s=yo(a,n,r)?vo(a):ca(0),c=(i.left+s.x)/o.x,l=(i.top+s.y)/o.y,u=i.width/o.x,d=i.height/o.y;if(a){let e=Wa(a),t=r&&qa(r)?Wa(r):r,n=e,i=po(n);for(;i&&r&&t!==n;){let e=go(i),t=i.getBoundingClientRect(),r=so(i),a=t.left+(i.clientLeft+parseFloat(r.paddingLeft))*e.x,o=t.top+(i.clientTop+parseFloat(r.paddingTop))*e.y;c*=e.x,l*=e.y,u*=e.x,d*=e.y,c+=a,l+=o,n=Wa(i),i=po(n)}}return Aa({width:u,height:d,x:c,y:l})}function xo(e,t){let n=co(e).scrollLeft;return t?t.left+n:bo(Ga(e)).left+n}function So(e,t){let n=e.getBoundingClientRect();return{x:n.left+t.scrollLeft-xo(e,n),y:n.top+t.scrollTop}}function Co(e){let{elements:t,rect:n,offsetParent:r,strategy:i}=e,a=i===`fixed`,o=Ga(r),s=t?Qa(t.floating):!1;if(r===o||s&&a)return n;let c={scrollLeft:0,scrollTop:0},l=ca(1),u=ca(0),d=Ja(r);if((d||!d&&!a)&&((Ua(r)!==`body`||Xa(o))&&(c=co(r)),d)){let e=bo(r);l=go(r),u.x=e.x+r.clientLeft,u.y=e.y+r.clientTop}let f=o&&!d&&!a?So(o,c):ca(0);return{width:n.width*l.x,height:n.height*l.y,x:n.x*l.x-c.scrollLeft*l.x+u.x+f.x,y:n.y*l.y-c.scrollTop*l.y+u.y+f.y}}function wo(e){return Array.from(e.getClientRects())}function To(e){let t=Ga(e),n=co(e),r=e.ownerDocument.body,i=aa(t.scrollWidth,t.clientWidth,r.scrollWidth,r.clientWidth),a=aa(t.scrollHeight,t.clientHeight,r.scrollHeight,r.clientHeight),o=-n.scrollLeft+xo(e),s=-n.scrollTop;return so(r).direction===`rtl`&&(o+=aa(t.clientWidth,r.clientWidth)-i),{width:i,height:a,x:o,y:s}}var Eo=25;function Do(e,t){let n=Wa(e),r=Ga(e),i=n.visualViewport,a=r.clientWidth,o=r.clientHeight,s=0,c=0;if(i){a=i.width,o=i.height;let e=ao();(!e||e&&t===`fixed`)&&(s=i.offsetLeft,c=i.offsetTop)}let l=xo(r);if(l<=0){let e=r.ownerDocument,t=e.body,n=getComputedStyle(t),i=e.compatMode===`CSS1Compat`&&parseFloat(n.marginLeft)+parseFloat(n.marginRight)||0,o=Math.abs(r.clientWidth-t.clientWidth-i);o<=Eo&&(a-=o)}else l<=Eo&&(a+=l);return{width:a,height:o,x:s,y:c}}function Oo(e,t){let n=bo(e,!0,t===`fixed`),r=n.top+e.clientTop,i=n.left+e.clientLeft,a=Ja(e)?go(e):ca(1);return{width:e.clientWidth*a.x,height:e.clientHeight*a.y,x:i*a.x,y:r*a.y}}function ko(e,t,n){let r;if(t===`viewport`)r=Do(e,n);else if(t===`document`)r=To(Ga(e));else if(qa(t))r=Oo(t,n);else{let n=vo(e);r={x:t.x-n.x,y:t.y-n.y,width:t.width,height:t.height}}return Aa(r)}function Ao(e,t){let n=lo(e);return n===t||!qa(n)||oo(n)?!1:so(n).position===`fixed`||Ao(n,t)}function jo(e,t){let n=t.get(e);if(n)return n;let r=fo(e,[],!1).filter(e=>qa(e)&&Ua(e)!==`body`),i=null,a=so(e).position===`fixed`,o=a?lo(e):e;for(;qa(o)&&!oo(o);){let t=so(o),n=ro(o);!n&&t.position===`fixed`&&(i=null),(a?!n&&!i:!n&&t.position===`static`&&i&&(i.position===`absolute`||i.position===`fixed`)||Xa(o)&&!n&&Ao(e,o))?r=r.filter(e=>e!==o):i=t,o=lo(o)}return t.set(e,r),r}function Mo(e){let{element:t,boundary:n,rootBoundary:r,strategy:i}=e,a=[...n===`clippingAncestors`?Qa(t)?[]:jo(t,this._c):[].concat(n),r],o=ko(t,a[0],i),s=o.top,c=o.right,l=o.bottom,u=o.left;for(let e=1;e<a.length;e++){let n=ko(t,a[e],i);s=aa(n.top,s),c=ia(n.right,c),l=ia(n.bottom,l),u=aa(n.left,u)}return{width:c-u,height:l-s,x:u,y:s}}function No(e){let{width:t,height:n}=mo(e);return{width:t,height:n}}function Po(e,t,n){let r=Ja(t),i=Ga(t),a=n===`fixed`,o=bo(e,!0,a,t),s={scrollLeft:0,scrollTop:0},c=ca(0);function l(){c.x=xo(i)}if(r||!r&&!a)if((Ua(t)!==`body`||Xa(i))&&(s=co(t)),r){let e=bo(t,!0,a,t);c.x=e.x+t.clientLeft,c.y=e.y+t.clientTop}else i&&l();a&&!r&&i&&l();let u=i&&!r&&!a?So(i,s):ca(0);return{x:o.left+s.scrollLeft-c.x-u.x,y:o.top+s.scrollTop-c.y-u.y,width:o.width,height:o.height}}function Fo(e){return so(e).position===`static`}function Io(e,t){if(!Ja(e)||so(e).position===`fixed`)return null;if(t)return t(e);let n=e.offsetParent;return Ga(e)===n&&(n=n.ownerDocument.body),n}function Lo(e,t){let n=Wa(e);if(Qa(e))return n;if(!Ja(e)){let t=lo(e);for(;t&&!oo(t);){if(qa(t)&&!Fo(t))return t;t=lo(t)}return n}let r=Io(e,t);for(;r&&Za(r)&&Fo(r);)r=Io(r,t);return r&&oo(r)&&Fo(r)&&!ro(r)?n:r||io(e)||n}var Ro=async function(e){let t=this.getOffsetParent||Lo,n=this.getDimensions,r=await n(e.floating);return{reference:Po(e.reference,await t(e.floating),e.strategy),floating:{x:0,y:0,width:r.width,height:r.height}}};function zo(e){return so(e).direction===`rtl`}var Bo={convertOffsetParentRelativeRectToViewportRelativeRect:Co,getDocumentElement:Ga,getClippingRect:Mo,getOffsetParent:Lo,getElementRects:Ro,getClientRects:wo,getDimensions:No,getScale:go,isElement:qa,isRTL:zo};function Vo(e,t){return e.x===t.x&&e.y===t.y&&e.width===t.width&&e.height===t.height}function Ho(e,t){let n=null,r,i=Ga(e);function a(){var e;clearTimeout(r),(e=n)==null||e.disconnect(),n=null}function o(s,c){s===void 0&&(s=!1),c===void 0&&(c=1),a();let l=e.getBoundingClientRect(),{left:u,top:d,width:f,height:p}=l;if(s||t(),!f||!p)return;let m=sa(d),h=sa(i.clientWidth-(u+f)),g=sa(i.clientHeight-(d+p)),_=sa(u),v={rootMargin:-m+`px `+-h+`px `+-g+`px `+-_+`px`,threshold:aa(0,ia(1,c))||1},y=!0;function b(t){let n=t[0].intersectionRatio;if(n!==c){if(!y)return o();n?o(!1,n):r=setTimeout(()=>{o(!1,1e-7)},1e3)}n===1&&!Vo(l,e.getBoundingClientRect())&&o(),y=!1}try{n=new IntersectionObserver(b,{...v,root:i.ownerDocument})}catch{n=new IntersectionObserver(b,v)}n.observe(e)}return o(!0),a}function Uo(e,t,n,r){r===void 0&&(r={});let{ancestorScroll:i=!0,ancestorResize:a=!0,elementResize:o=typeof ResizeObserver==`function`,layoutShift:s=typeof IntersectionObserver==`function`,animationFrame:c=!1}=r,l=ho(e),u=i||a?[...l?fo(l):[],...t?fo(t):[]]:[];u.forEach(e=>{i&&e.addEventListener(`scroll`,n,{passive:!0}),a&&e.addEventListener(`resize`,n)});let d=l&&s?Ho(l,n):null,f=-1,p=null;o&&(p=new ResizeObserver(e=>{let[r]=e;r&&r.target===l&&p&&t&&(p.unobserve(t),cancelAnimationFrame(f),f=requestAnimationFrame(()=>{var e;(e=p)==null||e.observe(t)})),n()}),l&&!c&&p.observe(l),t&&p.observe(t));let m,h=c?bo(e):null;c&&g();function g(){let t=bo(e);h&&!Vo(h,t)&&n(),h=t,m=requestAnimationFrame(g)}return n(),()=>{var e;u.forEach(e=>{i&&e.removeEventListener(`scroll`,n),a&&e.removeEventListener(`resize`,n)}),d?.(),(e=p)==null||e.disconnect(),p=null,c&&cancelAnimationFrame(m)}}var Wo=za,Go=Ba,Ko=Ia,qo=Va,Jo=Fa,Yo=(e,t,n)=>{let r=new Map,i={platform:Bo,...n},a={...i.platform,_c:r};return Pa(e,t,{...i,platform:a})};function Xo(e){return Qo(e)}function Zo(e){return e.assignedSlot?e.assignedSlot:e.parentNode instanceof ShadowRoot?e.parentNode.host:e.parentNode}function Qo(e){for(let t=e;t;t=Zo(t))if(t instanceof Element&&getComputedStyle(t).display===`none`)return null;for(let t=Zo(e);t;t=Zo(t)){if(!(t instanceof Element))continue;let e=getComputedStyle(t);if(e.display!==`contents`&&(e.position!==`static`||ro(e)||t.tagName===`BODY`))return t}return null}function $o(e){return typeof e==`object`&&!!e&&`getBoundingClientRect`in e&&(`contextElement`in e?e instanceof Element:!0)}var es=globalThis?.HTMLElement?.prototype.hasOwnProperty(`popover`),I=class extends Ri{constructor(){super(...arguments),this.localize=new ai(this),this.active=!1,this.placement=`top`,this.boundary=`viewport`,this.distance=0,this.skidding=0,this.arrow=!1,this.arrowPlacement=`anchor`,this.arrowPadding=10,this.flip=!1,this.flipFallbackPlacements=``,this.flipFallbackStrategy=`best-fit`,this.flipPadding=0,this.shift=!1,this.shiftPadding=0,this.autoSizePadding=0,this.hoverBridge=!1,this.updateHoverBridge=()=>{if(this.hoverBridge&&this.anchorEl&&this.popup){let e=this.anchorEl.getBoundingClientRect(),t=this.popup.getBoundingClientRect(),n=this.placement.includes(`top`)||this.placement.includes(`bottom`),r=0,i=0,a=0,o=0,s=0,c=0,l=0,u=0;n?e.top<t.top?(r=e.left,i=e.bottom,a=e.right,o=e.bottom,s=t.left,c=t.top,l=t.right,u=t.top):(r=t.left,i=t.bottom,a=t.right,o=t.bottom,s=e.left,c=e.top,l=e.right,u=e.top):e.left<t.left?(r=e.right,i=e.top,a=t.left,o=t.top,s=e.right,c=e.bottom,l=t.left,u=t.bottom):(r=t.right,i=t.top,a=e.left,o=e.top,s=t.right,c=t.bottom,l=e.left,u=e.bottom),this.style.setProperty(`--hover-bridge-top-left-x`,`${r}px`),this.style.setProperty(`--hover-bridge-top-left-y`,`${i}px`),this.style.setProperty(`--hover-bridge-top-right-x`,`${a}px`),this.style.setProperty(`--hover-bridge-top-right-y`,`${o}px`),this.style.setProperty(`--hover-bridge-bottom-left-x`,`${s}px`),this.style.setProperty(`--hover-bridge-bottom-left-y`,`${c}px`),this.style.setProperty(`--hover-bridge-bottom-right-x`,`${l}px`),this.style.setProperty(`--hover-bridge-bottom-right-y`,`${u}px`)}}}async connectedCallback(){super.connectedCallback(),await this.updateComplete,this.start()}disconnectedCallback(){super.disconnectedCallback(),this.stop()}async updated(e){super.updated(e),e.has(`active`)&&(this.active?this.start():this.stop()),e.has(`anchor`)&&this.handleAnchorChange(),this.active&&(await this.updateComplete,this.reposition())}async handleAnchorChange(){if(await this.stop(),this.anchor&&typeof this.anchor==`string`){let e=this.getRootNode();this.anchorEl=e.getElementById(this.anchor)}else this.anchor instanceof Element||$o(this.anchor)?this.anchorEl=this.anchor:this.anchorEl=this.querySelector(`[slot="anchor"]`);this.anchorEl instanceof HTMLSlotElement&&(this.anchorEl=this.anchorEl.assignedElements({flatten:!0})[0]),this.anchorEl&&this.start()}start(){!this.anchorEl||!this.active||!this.isConnected||(this.popup?.showPopover?.(),this.cleanup=Uo(this.anchorEl,this.popup,()=>{this.reposition()}))}async stop(){return new Promise(e=>{this.popup?.hidePopover?.(),this.cleanup?(this.cleanup(),this.cleanup=void 0,this.removeAttribute(`data-current-placement`),this.style.removeProperty(`--auto-size-available-width`),this.style.removeProperty(`--auto-size-available-height`),requestAnimationFrame(()=>e())):e()})}reposition(){if(!this.active||!this.anchorEl||!this.popup)return;let e=[Wo({mainAxis:this.distance,crossAxis:this.skidding})];this.sync?e.push(qo({apply:({rects:e})=>{let t=this.sync===`width`||this.sync===`both`,n=this.sync===`height`||this.sync===`both`;this.popup.style.width=t?`${e.reference.width}px`:``,this.popup.style.height=n?`${e.reference.height}px`:``}})):(this.popup.style.width=``,this.popup.style.height=``);let t;es&&!$o(this.anchor)&&this.boundary===`scroll`&&(t=fo(this.anchorEl).filter(e=>e instanceof Element)),this.flip&&e.push(Ko({boundary:this.flipBoundary||t,fallbackPlacements:this.flipFallbackPlacements,fallbackStrategy:this.flipFallbackStrategy===`best-fit`?`bestFit`:`initialPlacement`,padding:this.flipPadding})),this.shift&&e.push(Go({boundary:this.shiftBoundary||t,padding:this.shiftPadding})),this.autoSize?e.push(qo({boundary:this.autoSizeBoundary||t,padding:this.autoSizePadding,apply:({availableWidth:e,availableHeight:t})=>{this.autoSize===`vertical`||this.autoSize===`both`?this.style.setProperty(`--auto-size-available-height`,`${t}px`):this.style.removeProperty(`--auto-size-available-height`),this.autoSize===`horizontal`||this.autoSize===`both`?this.style.setProperty(`--auto-size-available-width`,`${e}px`):this.style.removeProperty(`--auto-size-available-width`)}})):(this.style.removeProperty(`--auto-size-available-width`),this.style.removeProperty(`--auto-size-available-height`)),this.arrow&&e.push(Jo({element:this.arrowEl,padding:this.arrowPadding}));let n=es?e=>Bo.getOffsetParent(e,Xo):Bo.getOffsetParent;Yo(this.anchorEl,this.popup,{placement:this.placement,middleware:e,strategy:es?`absolute`:`fixed`,platform:{...Bo,getOffsetParent:n}}).then(({x:e,y:t,middlewareData:n,placement:r})=>{let i=this.localize.dir()===`rtl`,a={top:`bottom`,right:`left`,bottom:`top`,left:`right`}[r.split(`-`)[0]];if(this.setAttribute(`data-current-placement`,r),Object.assign(this.popup.style,{left:`${e}px`,top:`${t}px`}),this.arrow){let e=n.arrow.x,t=n.arrow.y,r=``,o=``,s=``,c=``;if(this.arrowPlacement===`start`){let n=typeof e==`number`?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:``;r=typeof t==`number`?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:``,o=i?n:``,c=i?``:n}else if(this.arrowPlacement===`end`){let n=typeof e==`number`?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:``;o=i?``:n,c=i?n:``,s=typeof t==`number`?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:``}else this.arrowPlacement===`center`?(c=typeof e==`number`?`calc(50% - var(--arrow-size-diagonal))`:``,r=typeof t==`number`?`calc(50% - var(--arrow-size-diagonal))`:``):(c=typeof e==`number`?`${e}px`:``,r=typeof t==`number`?`${t}px`:``);Object.assign(this.arrowEl.style,{top:r,right:o,bottom:s,left:c,[a]:`calc(var(--arrow-base-offset) - var(--arrow-size-diagonal))`})}}),requestAnimationFrame(()=>this.updateHoverBridge()),this.dispatchEvent(new na)}render(){return l`
      <slot name="anchor" @slotchange=${this.handleAnchorChange}></slot>

      <span
        part="hover-bridge"
        class=${a({"popup-hover-bridge":!0,"popup-hover-bridge-visible":this.hoverBridge&&this.active})}
      ></span>

      <div
        popover="manual"
        part="popup"
        class=${a({popup:!0,"popup-active":this.active,"popup-fixed":!es,"popup-has-arrow":this.arrow})}
      >
        <slot></slot>
        ${this.arrow?l`<div part="arrow" class="arrow" role="presentation"></div>`:``}
      </div>
    `}};I.css=ra,N([y(`.popup`)],I.prototype,`popup`,2),N([y(`.arrow`)],I.prototype,`arrowEl`,2),N([g()],I.prototype,`anchor`,2),N([g({type:Boolean,reflect:!0})],I.prototype,`active`,2),N([g({reflect:!0})],I.prototype,`placement`,2),N([g()],I.prototype,`boundary`,2),N([g({type:Number})],I.prototype,`distance`,2),N([g({type:Number})],I.prototype,`skidding`,2),N([g({type:Boolean})],I.prototype,`arrow`,2),N([g({attribute:`arrow-placement`})],I.prototype,`arrowPlacement`,2),N([g({attribute:`arrow-padding`,type:Number})],I.prototype,`arrowPadding`,2),N([g({type:Boolean})],I.prototype,`flip`,2),N([g({attribute:`flip-fallback-placements`,converter:{fromAttribute:e=>e.split(` `).map(e=>e.trim()).filter(e=>e!==``),toAttribute:e=>e.join(` `)}})],I.prototype,`flipFallbackPlacements`,2),N([g({attribute:`flip-fallback-strategy`})],I.prototype,`flipFallbackStrategy`,2),N([g({type:Object})],I.prototype,`flipBoundary`,2),N([g({attribute:`flip-padding`,type:Number})],I.prototype,`flipPadding`,2),N([g({type:Boolean})],I.prototype,`shift`,2),N([g({type:Object})],I.prototype,`shiftBoundary`,2),N([g({attribute:`shift-padding`,type:Number})],I.prototype,`shiftPadding`,2),N([g({attribute:`auto-size`})],I.prototype,`autoSize`,2),N([g()],I.prototype,`sync`,2),N([g({type:Object})],I.prototype,`autoSizeBoundary`,2),N([g({attribute:`auto-size-padding`,type:Number})],I.prototype,`autoSizePadding`,2),N([g({attribute:`hover-bridge`,type:Boolean})],I.prototype,`hoverBridge`,2),I=N([v(`wa-popup`)],I);var ts=[];function ns(e){ts.push(e)}function rs(e){for(let t=ts.length-1;t>=0;t--)if(ts[t]===e){ts.splice(t,1);break}}function is(e){return ts.length>0&&ts[ts.length-1]===e}var as=class extends Event{constructor(){super(`wa-show`,{bubbles:!0,cancelable:!0,composed:!0})}},os=class extends Event{constructor(e){super(`wa-hide`,{bubbles:!0,cancelable:!0,composed:!0}),this.detail=e}},ss=class extends Event{constructor(){super(`wa-after-hide`,{bubbles:!0,cancelable:!1,composed:!0})}},cs=class extends Event{constructor(){super(`wa-after-show`,{bubbles:!0,cancelable:!1,composed:!0})}},ls=`useandom-26T198340PX75pxJACKVERYMINDBUSHWOLF_GQZbfghjklqvwyzrict`,us=(e=21)=>{let t=``,n=crypto.getRandomValues(new Uint8Array(e|=0));for(;e--;)t+=ls[n[e]&63];return t};function ds(e=``){return`${e}${us()}`}function fs(e,t){return new Promise(n=>{function r(i){i.target===e&&(e.removeEventListener(t,r),n())}e.addEventListener(t,r)})}function ps(e,t){return new Promise(n=>{let r=new AbortController,{signal:i}=r;if(e.classList.contains(t))return;e.classList.add(t);let a=!1,o=()=>{a||(a=!0,e.classList.remove(t),n(),r.abort())};e.addEventListener(`animationend`,o,{once:!0,signal:i}),e.addEventListener(`animationcancel`,o,{once:!0,signal:i}),requestAnimationFrame(()=>{!a&&e.getAnimations().length===0&&o()})})}var L=class extends Ri{constructor(){super(...arguments),this.placement=`top`,this.disabled=!1,this.distance=8,this.open=!1,this.skidding=0,this.showDelay=150,this.hideDelay=0,this.trigger=`hover focus`,this.withoutArrow=!1,this.for=null,this.anchor=null,this.eventController=new AbortController,this.handleBlur=()=>{this.hasTrigger(`focus`)&&this.hide()},this.handleClick=()=>{this.hasTrigger(`click`)&&(this.open?this.hide():this.show())},this.handleFocus=()=>{this.hasTrigger(`focus`)&&this.show()},this.handleDocumentKeyDown=e=>{e.key===`Escape`&&this.open&&is(this)&&(e.preventDefault(),e.stopPropagation(),this.hide())},this.handleMouseOver=()=>{this.hasTrigger(`hover`)&&(clearTimeout(this.hoverTimeout),this.hoverTimeout=window.setTimeout(()=>this.show(),this.showDelay))},this.handleMouseOut=()=>{if(this.hasTrigger(`hover`)){let e=!!this.anchor?.matches(`:hover`),t=this.matches(`:hover`);if(e||t)return;clearTimeout(this.hoverTimeout),e||t||(this.hoverTimeout=window.setTimeout(()=>{this.hide()},this.hideDelay))}}}connectedCallback(){super.connectedCallback(),this.eventController.signal.aborted&&(this.eventController=new AbortController),this.addEventListener(`mouseout`,this.handleMouseOut),this.open&&(this.open=!1,this.updateComplete.then(()=>{this.open=!0})),this.id||=ds(`wa-tooltip-`),this.for&&this.anchor?(this.anchor=null,this.handleForChange()):this.for&&this.handleForChange()}disconnectedCallback(){super.disconnectedCallback(),document.removeEventListener(`keydown`,this.handleDocumentKeyDown),rs(this),this.eventController.abort(),this.anchor&&this.removeFromAriaLabelledBy(this.anchor,this.id)}firstUpdated(){this.body.hidden=!this.open,this.open&&(this.popup.active=!0,this.popup.reposition())}hasTrigger(e){return this.trigger.split(` `).includes(e)}addToAriaLabelledBy(e,t){let n=(e.getAttribute(`aria-labelledby`)||``).split(/\s+/).filter(Boolean);n.includes(t)||(n.push(t),e.setAttribute(`aria-labelledby`,n.join(` `)))}removeFromAriaLabelledBy(e,t){let n=(e.getAttribute(`aria-labelledby`)||``).split(/\s+/).filter(Boolean).filter(e=>e!==t);n.length>0?e.setAttribute(`aria-labelledby`,n.join(` `)):e.removeAttribute(`aria-labelledby`)}async handleOpenChange(){if(this.open){if(this.disabled)return;let e=new as;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}document.addEventListener(`keydown`,this.handleDocumentKeyDown,{signal:this.eventController.signal}),ns(this),this.body.hidden=!1,this.popup.active=!0,await ps(this.popup.popup,`show-with-scale`),this.popup.reposition(),this.dispatchEvent(new cs)}else{let e=new os;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}document.removeEventListener(`keydown`,this.handleDocumentKeyDown),rs(this),await ps(this.popup.popup,`hide-with-scale`),this.popup.active=!1,this.body.hidden=!0,this.dispatchEvent(new ss)}}handleForChange(){let e=this.getRootNode();if(!e)return;let t=this.for?e.getElementById(this.for):null,n=this.anchor;if(t===n)return;let{signal:r}=this.eventController;t&&(this.addToAriaLabelledBy(t,this.id),t.addEventListener(`blur`,this.handleBlur,{capture:!0,signal:r}),t.addEventListener(`focus`,this.handleFocus,{capture:!0,signal:r}),t.addEventListener(`click`,this.handleClick,{signal:r}),t.addEventListener(`mouseover`,this.handleMouseOver,{signal:r}),t.addEventListener(`mouseout`,this.handleMouseOut,{signal:r})),n&&(this.removeFromAriaLabelledBy(n,this.id),n.removeEventListener(`blur`,this.handleBlur,{capture:!0}),n.removeEventListener(`focus`,this.handleFocus,{capture:!0}),n.removeEventListener(`click`,this.handleClick),n.removeEventListener(`mouseover`,this.handleMouseOver),n.removeEventListener(`mouseout`,this.handleMouseOut)),this.anchor=t}async handleOptionsChange(){this.hasUpdated&&(await this.updateComplete,this.popup.reposition())}handleDisabledChange(){this.disabled&&this.open&&this.hide()}async show(){if(!this.open)return this.open=!0,fs(this,`wa-after-show`)}async hide(){if(this.open)return this.open=!1,fs(this,`wa-after-hide`)}render(){return l`
      <wa-popup
        part="base"
        exportparts="
          popup:base__popup,
          arrow:base__arrow
        "
        class=${a({tooltip:!0,"tooltip-open":this.open})}
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
    `}};L.css=ta,L.dependencies={"wa-popup":I},N([y(`slot:not([name])`)],L.prototype,`defaultSlot`,2),N([y(`.body`)],L.prototype,`body`,2),N([y(`wa-popup`)],L.prototype,`popup`,2),N([g()],L.prototype,`placement`,2),N([g({type:Boolean,reflect:!0})],L.prototype,`disabled`,2),N([g({type:Number})],L.prototype,`distance`,2),N([g({type:Boolean,reflect:!0})],L.prototype,`open`,2),N([g({type:Number})],L.prototype,`skidding`,2),N([g({attribute:`show-delay`,type:Number})],L.prototype,`showDelay`,2),N([g({attribute:`hide-delay`,type:Number})],L.prototype,`hideDelay`,2),N([g()],L.prototype,`trigger`,2),N([g({attribute:`without-arrow`,type:Boolean,reflect:!0})],L.prototype,`withoutArrow`,2),N([g()],L.prototype,`for`,2),N([_()],L.prototype,`anchor`,2),N([P(`open`,{waitUntilFirstUpdate:!0})],L.prototype,`handleOpenChange`,1),N([P(`for`)],L.prototype,`handleForChange`,1),N([P([`distance`,`placement`,`skidding`])],L.prototype,`handleOptionsChange`,1),N([P(`disabled`)],L.prototype,`handleDisabledChange`,1),L=N([v(`wa-tooltip`)],L);var ms=class extends L{static get styles(){return[L.styles,d`
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
      `]}};customElements.get(`c-tooltip`)||customElements.define(`c-tooltip`,ms);var hs=new WeakMap;function gs(e,t){let n=t;for(;n;){if(hs.get(n)===e)return!0;n=Object.getPrototypeOf(n)}return!1}function R(e){return t=>{if(gs(e,t))return t;let n=e(t);return hs.set(n,e),n}}var _s=R(e=>class extends e{static get properties(){return{disabled:{type:Boolean,reflect:!0}}}constructor(){super(),this._requestedToBeDisabled=!1,this.__isUserSettingDisabled=!0,this.__restoreDisabledTo=!1,this.disabled=!1}makeRequestToBeDisabled(){this._requestedToBeDisabled===!1&&(this._requestedToBeDisabled=!0,this.__restoreDisabledTo=this.disabled,this.__internalSetDisabled(!0))}retractRequestToBeDisabled(){this._requestedToBeDisabled===!0&&(this._requestedToBeDisabled=!1,this.__internalSetDisabled(this.__restoreDisabledTo))}__internalSetDisabled(e){this.__isUserSettingDisabled=!1,this.disabled=e,this.__isUserSettingDisabled=!0}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`disabled`&&(this.__isUserSettingDisabled&&(this.__restoreDisabledTo=this.disabled),this.disabled===!1&&this._requestedToBeDisabled===!0&&this.__internalSetDisabled(!0))}click(){this.disabled||super.click()}}),vs=R(e=>class extends _s(e){static get properties(){return{tabIndex:{type:Number,reflect:!0,attribute:`tabindex`}}}constructor(){super(),this.__isUserSettingTabIndex=!0,this.__restoreTabIndexTo=0,this.__internalSetTabIndex(0)}makeRequestToBeDisabled(){super.makeRequestToBeDisabled(),this._requestedToBeDisabled===!1&&this.tabIndex!=null&&(this.__restoreTabIndexTo=this.tabIndex)}retractRequestToBeDisabled(){super.retractRequestToBeDisabled(),this._requestedToBeDisabled===!0&&this.__internalSetTabIndex(this.__restoreTabIndexTo)}static enabledWarnings=super.enabledWarnings?.filter(e=>e!==`change-in-update`)||[];__internalSetTabIndex(e){this.__isUserSettingTabIndex=!1,this.tabIndex=e,this.__isUserSettingTabIndex=!0}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`disabled`&&(this.disabled?this.__internalSetTabIndex(-1):this.__internalSetTabIndex(this.__restoreTabIndexTo)),e===`tabIndex`&&(this.__isUserSettingTabIndex&&this.tabIndex!=null&&(this.__restoreTabIndexTo=this.tabIndex),this.tabIndex!==-1&&this._requestedToBeDisabled===!0&&this.__internalSetTabIndex(-1))}firstUpdated(e){super.firstUpdated(e),this.disabled&&this.__internalSetTabIndex(-1)}});function ys(e,t){let n=!1;Array.from(e.childNodes).forEach(e=>{let r=e.hasAttribute&&e.hasAttribute(`slot`);if(e.nodeType===Node.COMMENT_NODE&&!n&&(n=e.textContent.includes(`_start_slot_`)),n){e.textContent.includes(`_end_slot_`)&&(n=!1);return}r||t.appendChild(e)})}function bs(e){return e instanceof Node?`node`:Vi(e)?`template-result`:!Array.isArray(e)&&typeof e==`object`&&`template`in e?`slot-rerender-object`:null}var xs=R(e=>class extends e{get slots(){return{}}constructor(){super(),this.__renderMetaPerSlot=new Map,this.__slotsThatNeedRerender=new Set,this.__slotsProvidedByUserOnFirstConnected=new Set,this.__privateSlots=new Set}connectedCallback(){super.connectedCallback(),this._connectSlotMixin()}__rerenderSlot(e){let t=this.slots[e]();this.__renderTemplateInScopedContext({renderAsDirectHostChild:t.renderAsDirectHostChild,template:t.template,slotName:e}),t.afterRender?.()}update(e){super.update(e);for(let e of this.__slotsThatNeedRerender)this.__rerenderSlot(e)}__renderTemplateInScopedContext({template:e,slotName:t,renderAsDirectHostChild:n}){if(!this.__renderMetaPerSlot.has(t)){let r=!!ShadowRoot.prototype.createElement;this.shadowRoot||console.error(`[SlotMixin] No shadowRoot was found`);let i=(r?this.shadowRoot:document).createElement(`div`),a=document.createComment(`_start_slot_${t}_`),o=document.createComment(`_end_slot_${t}_`);i.appendChild(a),i.appendChild(o);let{creationScope:s,host:c}=this.renderOptions;if(p(e,i,{renderBefore:o,creationScope:s,host:c}),n){let e=Array.from(i.childNodes);this.__appendNodes({nodes:e,renderParent:this,slotName:t})}else i.slot=t,this.appendChild(i);this.__renderMetaPerSlot.set(t,{renderTargetThatRespectsShadowRootScoping:i,renderBefore:o});return}let{renderBefore:r,renderTargetThatRespectsShadowRootScoping:i}=this.__renderMetaPerSlot.get(t),a=n?this:i,{creationScope:o,host:s}=this.renderOptions;p(e,a,{creationScope:o,host:s,renderBefore:r}),n&&r.previousElementSibling&&!r.previousElementSibling.slot&&(r.previousElementSibling.slot=t)}__appendNodes({nodes:e,renderParent:t=this,slotName:n}){for(let r of e)r instanceof Element&&n&&n!==``&&r.setAttribute(`slot`,n),t.appendChild(r)}__initSlots(e){for(let t of e){if(this.__slotsProvidedByUserOnFirstConnected.has(t))continue;let e=this.slots[t]();if(e!==void 0)switch(this.__isConnectedSlotMixin||this.__privateSlots.add(t),bs(e)){case`template-result`:this.__renderTemplateInScopedContext({template:e,renderAsDirectHostChild:!0,slotName:t});break;case`node`:this.__appendNodes({nodes:[e],renderParent:this,slotName:t});break;case`slot-rerender-object`:this.__slotsThatNeedRerender.add(t),e.firstRenderOnConnected&&this.__rerenderSlot(t);break;default:throw Error(`Slot "${t}" configured inside "get slots()" (in prototype) of ${this.constructor.name} may return these types: TemplateResult | Node | {template:TemplateResult, afterRender?:function} | undefined.
              You provided: ${e}`)}}}_connectSlotMixin(){if(this.__isConnectedSlotMixin)return;let e=Object.keys(this.slots);for(let t of e)(t===``?Array.from(this.children).find(e=>!e.hasAttribute(`slot`)):Array.from(this.children).find(e=>e.slot===t))&&this.__slotsProvidedByUserOnFirstConnected.add(t);this.__initSlots(e),this.__isConnectedSlotMixin=!0}_isPrivateSlot(e){return this.__privateSlots.has(e)}});function Ss(e=`google-chrome`){let t=globalThis.navigator,n=!!t.userAgentData&&t.userAgentData.brands.some(e=>e.brand===`Chromium`);if(e===`chromium`)return n;let r=globalThis.navigator?.vendor,i=globalThis.opr!==void 0,a=globalThis.userAgent?.indexOf(`Edge`)>-1,o=globalThis.userAgent?.match(`CriOS`);if(e===`ios`)return o;if(e===`google-chrome`)return n!=null&&r===`Google Inc.`&&i===!1&&a===!1}var Cs={isIE11:/Trident/.test(globalThis.navigator?.userAgent),isChrome:Ss(),isIOSChrome:Ss(`ios`),isChromium:Ss(`chromium`),isFirefox:globalThis.navigator?.userAgent.toLowerCase().indexOf(`firefox`)>-1,isMac:globalThis.navigator?.appVersion?.indexOf(`Mac`)!==-1,isIOS:/iPhone|iPad|iPod/i.test(globalThis.navigator?.userAgent),isMacSafari:globalThis.navigator?.vendor&&globalThis.navigator?.vendor.indexOf(`Apple`)>-1&&globalThis.navigator?.userAgent&&globalThis.navigator?.userAgent.indexOf(`CriOS`)===-1&&globalThis.navigator?.userAgent.indexOf(`FxiOS`)===-1&&globalThis.navigator?.appVersion.indexOf(`Mac`)!==-1};function ws(e=``){return`${e.length>0?`${e}-`:``}${Math.random().toString(36).substr(2,10)}`}var Ts=e=>e.key===` `||e.key===`Enter`,Es=e=>e.key===` `,Ds=class extends vs(h){static get properties(){return{active:{type:Boolean,reflect:!0},type:{type:String,reflect:!0}}}render(){return l` <div class="button-content"><slot></slot></div> `}static get styles(){return[d`
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
      `]}constructor(){super(),this.type=`button`,this.active=!1,this.__setupEvents()}connectedCallback(){super.connectedCallback(),this.hasAttribute(`role`)||this.setAttribute(`role`,`button`)}updated(e){super.updated(e),e.has(`disabled`)&&(this.disabled?this.setAttribute(`aria-disabled`,`true`):this.getAttribute(`aria-disabled`)!==null&&this.removeAttribute(`aria-disabled`))}__setupEvents(){this.addEventListener(`mousedown`,this.__mousedownHandler),this.addEventListener(`keydown`,this.__keydownHandler),this.addEventListener(`keyup`,this.__keyupHandler)}__mousedownHandler(){this.active=!0;let e=()=>{this.active=!1,document.removeEventListener(`mouseup`,e),this.removeEventListener(`mouseup`,e)};document.addEventListener(`mouseup`,e),this.addEventListener(`mouseup`,e)}__keydownHandler(e){if(this.active||!Ts(e)){Es(e)&&e.preventDefault();return}Es(e)&&e.preventDefault(),this.active=!0;let t=e=>{Ts(e)&&(this.active=!1,document.removeEventListener(`keyup`,t,!0))};document.addEventListener(`keyup`,t,!0)}__keyupHandler(e){if(Ts(e)){if(e.target&&e.target!==this)return;this.click()}}},Os=class extends Ds{constructor(){super(),this.type=`reset`,this.__setupDelegationInConstructor(),this.__submitAndResetHelperButton=document.createElement(`button`),this.__preventEventLeakage=this.__preventEventLeakage.bind(this)}connectedCallback(){super.connectedCallback(),this.updateComplete.then(()=>{this._setupSubmitAndResetHelperOnConnected()})}disconnectedCallback(){super.disconnectedCallback(),this._teardownSubmitAndResetHelperOnDisconnected()}__preventEventLeakage(e){e.target===this.__submitAndResetHelperButton&&e.stopImmediatePropagation()}_setupSubmitAndResetHelperOnConnected(){this.appendChild(this.__submitAndResetHelperButton),this._form=this.__submitAndResetHelperButton.form,this.removeChild(this.__submitAndResetHelperButton),this._form&&this._form.addEventListener(`click`,this.__preventEventLeakage)}_teardownSubmitAndResetHelperOnDisconnected(){this._form&&this._form.removeEventListener(`click`,this.__preventEventLeakage)}async __clickDelegationHandler(e){this._form||await this.updateComplete,(this.type===`submit`||this.type===`reset`)&&e.target===this&&this._form&&(this.__submitAndResetHelperButton.type=this.type,this._form.appendChild(this.__submitAndResetHelperButton),this.__submitAndResetHelperButton.click(),this._form.removeChild(this.__submitAndResetHelperButton))}__setupDelegationInConstructor(){this.addEventListener(`click`,this.__clickDelegationHandler,!0)}},ks=new WeakMap;function As(){let e=document.createElement(`button`);return e.tabIndex=-1,e.type=`submit`,e.setAttribute(`aria-hidden`,`true`),e.style.cssText=`
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
  `,e}var js=class extends Os{get _nativeButtonNode(){return ks.get(this._form)?.helper||null}constructor(){super(),this.type=`submit`,this.__implicitSubmitHelperButton=null}_setupSubmitAndResetHelperOnConnected(){if(super._setupSubmitAndResetHelperOnConnected(),!this._form||this.type!==`submit`)return;let e=this._form;if(!ks.get(this._form)){let t=As(),n=document.createElement(`div`);n.appendChild(t),ks.set(this._form,{lionButtons:new Set,helper:t,observer:new MutationObserver(()=>{e.appendChild(n)})}),e.appendChild(n),ks.get(e)?.observer.observe(n,{childList:!0})}ks.get(e)?.lionButtons.add(this)}_teardownSubmitAndResetHelperOnDisconnected(){if(super._teardownSubmitAndResetHelperOnDisconnected(),this._form){let e=ks.get(this._form);e&&(e.lionButtons.delete(this),e.lionButtons.size||(this._form.contains(e.helper)&&e.helper.remove(),ks.get(this._form)?.observer.disconnect(),ks.delete(this._form)))}}},Ms=d`
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
`,Ns=d`
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
`,Ps=d`
  :host {
    // Necessary to use variables here to override the default active style specificity
    --_active-background-color: hsl(
      from var(--c-color-fill-loud, var(--c-color-neutral-fill-normal)) h s
        calc(l - 10)
    );
    --_active-color: var(--c-color-on-loud);
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
    border-width: var(--c-button-border-width, 1px);
    border-style: var(--c-button-border-style, solid);

    /* Colorable styles */
    color: var(--c-color-on-loud, var(--c-color-neutral-on-loud));
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
      background-color: hsl(
        from var(--c-color-fill-loud, var(--c-button-default-fill)) h s
          calc(l - 5)
      );
      color: var(--c-color-on-loud);
    }
  }

  :host(:not(:disabled):not(.loading):active) {
    color: var(--_active-color);
    background-color: var(--_active-background-color);
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

  /* Plain & Outline (Shared) */
  :host([appearance~='plain']),
  :host([appearance~='outline']) {
    background-color: transparent;
    color: var(--c-color-on-quiet);
  }

  :host([appearance~='plain']:hover),
  :host([appearance~='outline']:hover) {
    background-color: hsl(
      from var(--c-color-fill-quiet, var(--c-color-neutral-fill-quiet)) h s
        calc(l - 5)
    );
  }

  :host([appearance~='plain']:active),
  :host([appearance~='outline']:active) {
    --_active-background-color: hsl(
      from var(--c-color-fill-quiet, var(--c-color-neutral-fill-quiet)) h s
        calc(l - 8)
    );
    --_active-color: var(--c-color-on-quiet, var(--c-color-neutral-on-quiet));
  }

  /* Plain */
  :host([appearance~='plain']) {
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
  :host([appearance~='solid']) {
    background-color: var(
      --c-color-fill-loud,
      var(--c-color-neutral-fill-loud)
    );
    border-color: transparent;
    color: var(--c-color-on-loud, var(--c-color-neutral-on-loud));
  }

  :host([appearance='solid']:hover) {
    background-color: hsl(
      from var(--c-color-fill-loud, var(--c-color-neutral-fill-loud)) h s
        calc(l - 5)
    );
    color: var(--c-color-on-loud, var(--c-color-neutral-on-loud));
  }

  :host([appearance='solid']:active) {
    --_active-background-color: hsl(
      from var(--c-color-fill-loud, var(--c-color-neutral-fill-loud)) h s
        calc(l - 10)
    );
    --_active-color: var(--c-color-on-loud, var(--c-color-neutral-on-loud));
  }

  /* Outline */
  :host([appearance='outline']) {
    border-color: var(--c-color-border-loud);
  }

  /*
  Variants (aka fill colors) 
   */
  :host([variant~='accent']) {
    --c-color-fill-loud: var(--c-color-accent-fill-loud);
    --c-color-fill-normal: var(--c-color-accent-fill-normal);
    --c-color-fill-quiet: var(--c-color-accent-fill-quiet);
    --c-color-border-loud: var(--c-color-accent-border-loud);
    --c-color-border-normal: var(--c-color-accent-border-normal);
    --c-color-border-quiet: var(--c-color-accent-border-quiet);
    --c-color-on-loud: var(--c-color-accent-on-loud);
    --c-color-on-normal: var(--c-color-accent-on-normal);
    --c-color-on-quiet: var(--c-color-accent-on-quiet);
  }

  :host([variant='neutral']) {
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
`,Fs=Object.prototype.toString;function Is(e){return typeof e==`function`||Fs.call(e)===`[object Function]`}function Ls(e){var t=Number(e);return isNaN(t)?0:t===0||!isFinite(t)?t:(t>0?1:-1)*Math.floor(Math.abs(t))}var Rs=2**53-1;function zs(e){var t=Ls(e);return Math.min(Math.max(t,0),Rs)}function Bs(e,t){var n=Array,r=Object(e);if(e==null)throw TypeError(`Array.from requires an array-like object - not null or undefined`);if(t!==void 0&&!Is(t))throw TypeError(`Array.from: when provided, the second argument must be a function`);for(var i=zs(r.length),a=Is(n)?Object(new n(i)):Array(i),o=0,s;o<i;)s=r[o],t?a[o]=t(s,o):a[o]=s,o+=1;return a.length=i,a}function Vs(e){"@babel/helpers - typeof";return Vs=typeof Symbol==`function`&&typeof Symbol.iterator==`symbol`?function(e){return typeof e}:function(e){return e&&typeof Symbol==`function`&&e.constructor===Symbol&&e!==Symbol.prototype?`symbol`:typeof e},Vs(e)}function Hs(e,t){if(!(e instanceof t))throw TypeError(`Cannot call a class as a function`)}function Us(e,t){for(var n=0;n<t.length;n++){var r=t[n];r.enumerable=r.enumerable||!1,r.configurable=!0,`value`in r&&(r.writable=!0),Object.defineProperty(e,Ks(r.key),r)}}function Ws(e,t,n){return t&&Us(e.prototype,t),n&&Us(e,n),Object.defineProperty(e,`prototype`,{writable:!1}),e}function Gs(e,t,n){return t=Ks(t),t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function Ks(e){var t=qs(e,`string`);return Vs(t)==`symbol`?t:t+``}function qs(e,t){if(Vs(e)!=`object`||!e)return e;var n=e[Symbol.toPrimitive];if(n!==void 0){var r=n.call(e,t||`default`);if(Vs(r)!=`object`)return r;throw TypeError(`@@toPrimitive must return a primitive value.`)}return(t===`string`?String:Number)(e)}var Js=typeof Set>`u`?Set:function(){function e(){var t=arguments.length>0&&arguments[0]!==void 0?arguments[0]:[];Hs(this,e),Gs(this,`items`,void 0),this.items=t}return Ws(e,[{key:`add`,value:function(e){return this.has(e)===!1&&this.items.push(e),this}},{key:`clear`,value:function(){this.items=[]}},{key:`delete`,value:function(e){var t=this.items.length;return this.items=this.items.filter(function(t){return t!==e}),t!==this.items.length}},{key:`forEach`,value:function(e){var t=this;this.items.forEach(function(n){e(n,n,t)})}},{key:`has`,value:function(e){return this.items.indexOf(e)!==-1}},{key:`size`,get:function(){return this.items.length}}])}();function Ys(e){return e.localName??e.tagName.toLowerCase()}var Xs={article:`article`,aside:`complementary`,button:`button`,datalist:`listbox`,dd:`definition`,details:`group`,dialog:`dialog`,dt:`term`,fieldset:`group`,figure:`figure`,form:`form`,footer:`contentinfo`,h1:`heading`,h2:`heading`,h3:`heading`,h4:`heading`,h5:`heading`,h6:`heading`,header:`banner`,hr:`separator`,html:`document`,legend:`legend`,li:`listitem`,math:`math`,main:`main`,menu:`list`,nav:`navigation`,ol:`list`,optgroup:`group`,option:`option`,output:`status`,progress:`progressbar`,section:`region`,summary:`button`,table:`table`,tbody:`rowgroup`,textarea:`textbox`,tfoot:`rowgroup`,td:`cell`,th:`columnheader`,thead:`rowgroup`,tr:`row`,ul:`list`},Zs={caption:new Set([`aria-label`,`aria-labelledby`]),code:new Set([`aria-label`,`aria-labelledby`]),deletion:new Set([`aria-label`,`aria-labelledby`]),emphasis:new Set([`aria-label`,`aria-labelledby`]),generic:new Set([`aria-label`,`aria-labelledby`,`aria-roledescription`]),insertion:new Set([`aria-label`,`aria-labelledby`]),none:new Set([`aria-label`,`aria-labelledby`]),paragraph:new Set([`aria-label`,`aria-labelledby`]),presentation:new Set([`aria-label`,`aria-labelledby`]),strong:new Set([`aria-label`,`aria-labelledby`]),subscript:new Set([`aria-label`,`aria-labelledby`]),superscript:new Set([`aria-label`,`aria-labelledby`])};function Qs(e,t){return[`aria-atomic`,`aria-busy`,`aria-controls`,`aria-current`,`aria-description`,`aria-describedby`,`aria-details`,`aria-dropeffect`,`aria-flowto`,`aria-grabbed`,`aria-hidden`,`aria-keyshortcuts`,`aria-label`,`aria-labelledby`,`aria-live`,`aria-owns`,`aria-relevant`,`aria-roledescription`].some(function(n){var r;return e.hasAttribute(n)&&!((r=Zs[t])!=null&&r.has(n))})}function $s(e,t){return Qs(e,t)}function ec(e){var t=nc(e);if(t===null||rc.indexOf(t)!==-1){var n=tc(e);if(rc.indexOf(t||``)===-1||$s(e,n||``))return n}return t}function tc(e){var t=Xs[Ys(e)];if(t!==void 0)return t;switch(Ys(e)){case`a`:case`area`:case`link`:if(e.hasAttribute(`href`))return`link`;break;case`img`:return e.getAttribute(`alt`)===``&&!$s(e,`img`)?`presentation`:`img`;case`input`:var n=e.type;switch(n){case`button`:case`image`:case`reset`:case`submit`:return`button`;case`checkbox`:case`radio`:return n;case`range`:return`slider`;case`email`:case`tel`:case`text`:case`url`:return e.hasAttribute(`list`)?`combobox`:`textbox`;case`search`:return e.hasAttribute(`list`)?`combobox`:`searchbox`;case`number`:return`spinbutton`;default:return null}case`select`:return e.hasAttribute(`multiple`)||e.size>1?`listbox`:`combobox`}return null}function nc(e){var t=e.getAttribute(`role`);if(t!==null){var n=t.trim().split(` `)[0];if(n.length>0)return n}return null}var rc=[`presentation`,`none`];function z(e){return e!==null&&e.nodeType===e.ELEMENT_NODE}function ic(e){return z(e)&&Ys(e)===`caption`}function ac(e){return z(e)&&Ys(e)===`input`}function oc(e){return z(e)&&Ys(e)===`optgroup`}function sc(e){return z(e)&&Ys(e)===`select`}function cc(e){return z(e)&&Ys(e)===`table`}function lc(e){return z(e)&&Ys(e)===`textarea`}function uc(e){var t=(e.ownerDocument===null?e:e.ownerDocument).defaultView;if(t===null)throw TypeError(`no window available`);return t}function dc(e){return z(e)&&Ys(e)===`fieldset`}function fc(e){return z(e)&&Ys(e)===`legend`}function pc(e){return z(e)&&Ys(e)===`slot`}function mc(e){return z(e)&&e.ownerSVGElement!==void 0}function hc(e){return z(e)&&Ys(e)===`svg`}function gc(e){return mc(e)&&Ys(e)===`title`}function _c(e,t){if(z(e)&&e.hasAttribute(t)){var n=e.getAttribute(t).split(` `),r=e.getRootNode?e.getRootNode():e.ownerDocument;return n.map(function(e){return r.getElementById(e)}).filter(function(e){return e!==null})}return[]}function vc(e,t){return z(e)?t.indexOf(ec(e))!==-1:!1}function yc(e){return e.trim().replace(/\s\s+/g,` `)}function bc(e,t){if(!z(e))return!1;if(e.hasAttribute(`hidden`)||e.getAttribute(`aria-hidden`)===`true`)return!0;var n=t(e);return n.getPropertyValue(`display`)===`none`||n.getPropertyValue(`visibility`)===`hidden`}function xc(e){return vc(e,[`button`,`combobox`,`listbox`,`textbox`])||Sc(e,`range`)}function Sc(e,t){if(!z(e))return!1;switch(t){case`range`:return vc(e,[`meter`,`progressbar`,`scrollbar`,`slider`,`spinbutton`]);default:throw TypeError(`No knowledge about abstract role '${t}'. This is likely a bug :(`)}}function Cc(e,t){var n=Bs(e.querySelectorAll(t));return _c(e,`aria-owns`).forEach(function(e){n.push.apply(n,Bs(e.querySelectorAll(t)))}),n}function wc(e){return sc(e)?e.selectedOptions||Cc(e,`[selected]`):Cc(e,`[aria-selected="true"]`)}function Tc(e){return vc(e,rc)}function Ec(e){return ic(e)}function Dc(e){return vc(e,[`button`,`cell`,`checkbox`,`columnheader`,`gridcell`,`heading`,`label`,`legend`,`link`,`menuitem`,`menuitemcheckbox`,`menuitemradio`,`option`,`radio`,`row`,`rowheader`,`switch`,`tab`,`tooltip`,`treeitem`])}function Oc(e){return!1}function kc(e){return ac(e)||lc(e)?e.value:e.textContent||``}function Ac(e){var t=e.getPropertyValue(`content`);return/^["'].*["']$/.test(t)?t.slice(1,-1):``}function jc(e){var t=Ys(e);return t===`button`||t===`input`&&e.getAttribute(`type`)!==`hidden`||t===`meter`||t===`output`||t===`progress`||t===`select`||t===`textarea`}function Mc(e){if(jc(e))return e;var t=null;return e.childNodes.forEach(function(e){if(t===null&&z(e)){var n=Mc(e);n!==null&&(t=n)}}),t}function Nc(e){if(e.control!==void 0)return e.control;var t=e.getAttribute(`for`);return t===null?Mc(e):e.ownerDocument.getElementById(t)}function Pc(e){var t=e.labels;if(t===null)return t;if(t!==void 0)return Bs(t);if(!jc(e))return null;var n=e.ownerDocument;return Bs(n.querySelectorAll(`label`)).filter(function(t){return Nc(t)===e})}function Fc(e){var t=e.assignedNodes();return t.length===0?Bs(e.childNodes):t}function Ic(e){var t=arguments.length>1&&arguments[1]!==void 0?arguments[1]:{},n=new Js,r=typeof Map>`u`?void 0:new Map,i=uc(e),a=t.compute,o=a===void 0?`name`:a,s=t.computedStyleSupportsPseudoElements,c=s===void 0?t.getComputedStyle!==void 0:s,l=t.getComputedStyle,u=l===void 0?i.getComputedStyle.bind(i):l,d=t.hidden,f=d===void 0?!1:d,p=function(e,t){if(t!==void 0)throw Error(`use uncachedGetComputedStyle directly for pseudo elements`);if(r===void 0)return u(e);var n=r.get(e);if(n)return n;var i=u(e,t);return r.set(e,i),i};function m(e,t){var n=``;if(z(e)&&c&&(n=`${Ac(u(e,`::before`))} ${n}`),(pc(e)?Fc(e):Bs(e.childNodes).concat(_c(e,`aria-owns`))).forEach(function(e){var r=v(e,{isEmbeddedInLabel:t.isEmbeddedInLabel,isReferenced:!1,recursion:!0}),i=(z(e)?p(e).getPropertyValue(`display`):`inline`)===`inline`?``:` `;n+=`${i}${r}${i}`}),z(e)&&c){var r=Ac(u(e,`::after`));n=`${n} ${r}`}return n.trim()}function h(e,t){var r=e.getAttributeNode(t);return r!==null&&!n.has(r)&&r.value.trim()!==``?(n.add(r),r.value):null}function g(e){return z(e)?h(e,`title`):null}function _(e){if(!z(e))return null;if(dc(e)){n.add(e);for(var t=Bs(e.childNodes),r=0;r<t.length;r+=1){var i=t[r];if(fc(i))return v(i,{isEmbeddedInLabel:!1,isReferenced:!1,recursion:!1})}}else if(cc(e)){n.add(e);for(var a=Bs(e.childNodes),o=0;o<a.length;o+=1){var s=a[o];if(ic(s))return v(s,{isEmbeddedInLabel:!1,isReferenced:!1,recursion:!1})}}else if(hc(e)){n.add(e);for(var c=Bs(e.childNodes),l=0;l<c.length;l+=1){var u=c[l];if(gc(u))return u.textContent}return null}else if(Ys(e)===`img`||Ys(e)===`area`){var d=h(e,`alt`);if(d!==null)return d}else if(oc(e)){var f=h(e,`label`);if(f!==null)return f}if(ac(e)&&(e.type===`button`||e.type===`submit`||e.type===`reset`)){var p=h(e,`value`);if(p!==null)return p;if(e.type===`submit`)return`Submit`;if(e.type===`reset`)return`Reset`}var g=Pc(e);if(g!==null&&g.length!==0)return n.add(e),Bs(g).map(function(e){return v(e,{isEmbeddedInLabel:!0,isReferenced:!1,recursion:!0})}).filter(function(e){return e.length>0}).join(` `);if(ac(e)&&e.type===`image`){var _=h(e,`alt`);if(_!==null)return _;var y=h(e,`title`);return y===null?`Submit Query`:y}if(vc(e,[`button`])){var b=m(e,{isEmbeddedInLabel:!1,isReferenced:!1});if(b!==``)return b}return null}function v(e,t){if(n.has(e))return``;if(!f&&bc(e,p)&&!t.isReferenced)return n.add(e),``;var r=z(e)?e.getAttributeNode(`aria-labelledby`):null,i=r!==null&&!n.has(r)?_c(e,`aria-labelledby`):[];if(o===`name`&&!t.isReferenced&&i.length>0)return n.add(r),i.map(function(e){return v(e,{isEmbeddedInLabel:t.isEmbeddedInLabel,isReferenced:!0,recursion:!1})}).join(` `);var a=t.recursion&&xc(e)&&o===`name`;if(!a){var s=(z(e)&&e.getAttribute(`aria-label`)||``).trim();if(s!==``&&o===`name`)return n.add(e),s;if(!Tc(e)){var c=_(e);if(c!==null)return n.add(e),c}}if(vc(e,[`menu`]))return n.add(e),``;if(a||t.isEmbeddedInLabel||t.isReferenced){if(vc(e,[`combobox`,`listbox`])){n.add(e);var l=wc(e);return l.length===0?ac(e)?e.value:``:Bs(l).map(function(e){return v(e,{isEmbeddedInLabel:t.isEmbeddedInLabel,isReferenced:!1,recursion:!0})}).join(` `)}if(Sc(e,`range`))return n.add(e),e.hasAttribute(`aria-valuetext`)?e.getAttribute(`aria-valuetext`):e.hasAttribute(`aria-valuenow`)?e.getAttribute(`aria-valuenow`):e.getAttribute(`value`)||``;if(vc(e,[`textbox`]))return n.add(e),kc(e)}if(Dc(e)||z(e)&&t.isReferenced||Ec(e)||Oc(e)){var u=m(e,{isEmbeddedInLabel:t.isEmbeddedInLabel,isReferenced:!1});if(u!==``)return n.add(e),u}if(e.nodeType===e.TEXT_NODE)return n.add(e),e.textContent||``;if(t.recursion)return n.add(e),m(e,{isEmbeddedInLabel:t.isEmbeddedInLabel,isReferenced:!1});var d=g(e);return d===null?(n.add(e),``):(n.add(e),d)}return yc(v(e,{isEmbeddedInLabel:!1,isReferenced:o===`description`,recursion:!1}))}function Lc(e){return vc(e,[`caption`,`code`,`deletion`,`emphasis`,`generic`,`insertion`,`none`,`paragraph`,`presentation`,`strong`,`subscript`,`superscript`])}function Rc(e){var t=arguments.length>1&&arguments[1]!==void 0?arguments[1]:{};return Lc(e)?``:Ic(e,t)}var zc=class extends js{constructor(...e){super(...e),this.appearance=`solid`,this.variant=`neutral`,this.size=`medium`,this.loading=!1,this.align=`center`,this.icon=null,this._hasAccessibilityError=!1}static get styles(){return[...super.styles,Ps]}async firstUpdated(e){super.firstUpdated(e),await this.updateComplete;let t=this.querySelectorAll(`craft-icon, craft-spinner`);await Promise.all(Array.from(t).map(e=>e.updateComplete)),this.accessibleName||=Rc(this),this._hasAccessibilityError=!this.accessibleName||this.accessibleName.trim()===``}render(){return l`
      <!--@TODO need to figure this out-->
      <!--<div role="status" class="sr-only"></div>-->
      <div
        class="${a({"button-content":!0,"button-content--start":this.align===`start`,"button-content--end":this.align===`end`,"a11y-error":this._hasAccessibilityError})}"
        part="content"
      >
        <slot name="prefix" class="prefix" part="prefix">
          ${this.icon?l`<craft-icon name="${this.icon}"></craft-icon>`:m}
        </slot>
        <slot class="label" part="label"></slot>
        <slot name="suffix" class="suffix" part="suffix"></slot>
      </div>
      ${this.loading?l`<craft-spinner part="spinner"></craft-spinner>`:m}
    `}};o([g()],zc.prototype,`accessibleName`,void 0),o([g({reflect:!0})],zc.prototype,`appearance`,void 0),o([g({reflect:!0})],zc.prototype,`variant`,void 0),o([g({reflect:!0})],zc.prototype,`size`,void 0),o([g({reflect:!0,type:Boolean})],zc.prototype,`loading`,void 0),o([g()],zc.prototype,`align`,void 0),o([g()],zc.prototype,`icon`,void 0),o([_()],zc.prototype,`_hasAccessibilityError`,void 0),customElements.get(`craft-button`)||customElements.define(`craft-button`,zc);var Bc=new WeakMap,Vc=class extends h{constructor(...e){super(...e),this.label=n(`More Info`),this.icon=`circle-info`,this.disabled=!1,this.status=``,w(this,Bc,new AbortController)}connectedCallback(){super.connectedCallback(),D(Bc,this).signal.aborted&&E(Bc,this,new AbortController),this.id||=`info-icon-${Math.random().toString(36).slice(2,8)}`;let{signal:e}=D(Bc,this);this.addEventListener(`wa-show`,()=>{Hc._&&Hc._!==this&&Hc._.renderRoot.querySelector(`c-tooltip`)?.hide(),Hc._=this},{signal:e}),this.addEventListener(`wa-after-show`,()=>{this.status=``,setTimeout(()=>{this.status=`Some new status`},200)},{signal:e}),this.addEventListener(`wa-after-hide`,()=>{Hc._===this&&(Hc._=null),this.status=``},{signal:e})}disconnectedCallback(){Hc._===this&&(Hc._=null),D(Bc,this).abort(),super.disconnectedCallback()}render(){return l`
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
    `}};Vc.styles=d`
    :host {
      display: inline-flex;
    }
  `;var Hc={_:null};o([g()],Vc.prototype,`label`,void 0),o([g()],Vc.prototype,`icon`,void 0),o([g({type:Boolean,reflect:!0})],Vc.prototype,`disabled`,void 0),o([g()],Vc.prototype,`id`,void 0),o([_()],Vc.prototype,`status`,void 0),o([y(`c-tooltip`)],Vc.prototype,`tooltip`,void 0),customElements.get(`craft-info-icon`)||customElements.define(`craft-info-icon`,Vc);var Uc=class extends h{constructor(...e){super(...e),this.debug=!1}render(){return l`<slot></slot>`}};Uc.styles=d`
    :host(:not([debug])) {
      position: absolute;
      width: 1px;
      height: 1px;
      overflow: hidden;
      clip: rect(0 0 0 0);
      clip-path: inset(50%);
      white-space: nowrap;
    }
  `,o([g({type:Boolean,reflect:!0})],Uc.prototype,`debug`,void 0),customElements.get(`craft-visually-hidden`)||customElements.define(`craft-visually-hidden`,Uc);var Wc=class extends h{constructor(...e){super(...e),this.visible=!0}show(){this.visible=!0,this.dispatchEvent(new CustomEvent(`show`))}hide(){this.visible=!1,this.dispatchEvent(new CustomEvent(`hide`))}focus(){this.wrapper?.focus()}render(){return l`
      <div
        tabindex="-1"
        class="${a({wrapper:!0,hidden:!this.visible})}"
      >
        <div class="spinner"></div>
        <span class="cp-visually-hidden"><slot /></span>
      </div>
    `}};Wc.styles=[Ns,Ms],o([g({reflect:!0,type:Boolean})],Wc.prototype,`visible`,void 0),o([y(`.wrapper`)],Wc.prototype,`wrapper`,void 0),customElements.get(`craft-spinner`)||customElements.define(`craft-spinner`,Wc);var Gc=d`
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
`,Kc={"icon.in":{keyframes:[{scale:.25,opacity:.25},{scale:1,opacity:1}],options:{duration:100}},"icon.out":{keyframes:[{scale:1,opacity:1},{scale:.25,opacity:.25}],options:{duration:100}}},qc=class extends h{constructor(...e){super(...e),this.isCopying=!1,this.status=`rest`,this.value=``,this.disabled=!1,this.feedbackDuration=1e3,this.tooltipLabel=null}async copyValue(){if(!(this.status===`copying`||this.disabled)){this.status=`copying`;try{await navigator.clipboard.writeText(this.value),this.dispatchEvent(new CustomEvent(`craft-copy`,{bubbles:!0,cancelable:!1,composed:!0,detail:{value:this.value}}))}catch{this.dispatchEvent(new CustomEvent(`craft-error`,{cancelable:!1,composed:!0,bubbles:!0}))}finally{this.isCopying=!1}}}async showStatus(e){let t=e===`success`?this.successIconEl:this.errorIconEl;this.tooltipLabel=e===`success`?`Copied`:`Copy failed`,await t.animate(Kc[`icon.out`].keyframes,Kc[`icon.out`].options),this.copyIconEl.hidden=!0,t.hidden=!1,await t.animate(Kc[`icon.in`].keyframes,Kc[`icon.in`].options),this.status=e,setTimeout(async()=>{await t.animate(Kc[`icon.out`].keyframes,Kc[`icon.out`].options),t.hidden=!0,this.copyIconEl.hidden=!1,await this.copyIconEl.animate(Kc[`icon.in`].keyframes,Kc[`icon.in`].options),this.status=`rest`,this.tooltipLabel=`Copy`},this.feedbackDuration)}connectedCallback(){super.connectedCallback(),this.tooltipLabel=this.getAttribute(`tooltip-label`)||n(`Copy`),this.id||=`copy-${Math.floor(Math.random()*1e8)}`,this.addEventListener(`craft-copy`,()=>{this.showStatus(`success`)}),this.addEventListener(`craft-error`,()=>{this.showStatus(`error`)})}render(){return l`
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
    `}};qc.styles=[Gc],o([_()],qc.prototype,`isCopying`,void 0),o([_()],qc.prototype,`status`,void 0),o([y(`slot[name="copy-icon"]`)],qc.prototype,`copyIconEl`,void 0),o([y(`slot[name="success-icon"]`)],qc.prototype,`successIconEl`,void 0),o([y(`slot[name="error-icon"]`)],qc.prototype,`errorIconEl`,void 0),o([g({type:String})],qc.prototype,`value`,void 0),o([g({type:Boolean})],qc.prototype,`disabled`,void 0),o([g({attribute:`feedback-duration`,type:Number})],qc.prototype,`feedbackDuration`,void 0),o([g()],qc.prototype,`tooltipLabel`,void 0),customElements.get(`craft-copy-button`)||customElements.define(`craft-copy-button`,qc);var Jc=d`
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
`,Yc=d`
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
`,Xc=class extends h{constructor(...e){super(...e),this.value=``,this.disabled=!1}getId(){return`attribute-${this.value.replace(/([a-z])([A-Z])/g,`$1-$2`).replace(/[\s_]+/g,`-`).toLowerCase()}`}render(){return l`
      <craft-copy-button
        id="${this.getId()}"
        value="${this.value}"
        class=${a({"copy-attribute":!0})}
      >
        ${this.value}
      </craft-copy-button>
    `}};Xc.styles=[Jc,Yc],o([y(`craft-copy-button`)],Xc.prototype,`copyButtonEl`,void 0),o([g({type:String})],Xc.prototype,`value`,void 0),o([g({type:Boolean,reflect:!0})],Xc.prototype,`disabled`,void 0),customElements.get(`craft-copy-attribute`)||customElements.define(`craft-copy-attribute`,Xc);var Zc=d`
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
`,Qc=class extends h{constructor(...e){super(...e),this.label=null,this._gradientId=null}connectedCallback(){super.connectedCallback(),this._gradientId=`avatar-gradient-${Math.random().toString(36).slice(2,8)}`}text(){return this.label?this.label.split(` `).map(e=>e.charAt(0).toUpperCase()).join(``):`?`}render(){return l`
      <span class="avatar">
        <svg
          viewBox="0 0 100 100"
          xmlns="http://www.w3.org/2000/svg"
          role="img"
        >
          ${this.label?l`<title>${this.label}</title>`:``}
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
    `}};Qc.styles=[Zc],o([g()],Qc.prototype,`label`,void 0),o([_()],Qc.prototype,`_gradientId`,void 0),customElements.get(`craft-avatar`)||customElements.define(`craft-avatar`,Qc);var $c=d`
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
`,el=d`
  :host(:not([label-sr-only]))
    .form-field__group-one
    .form-field__label
    slot:not(:empty) {
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
`,tl=d`
  ${el}

  ::slotted([slot='input']) {
    font: inherit;
    padding-block: 0;
    border: none;
    appearance: none;
    padding-inline: var(--c-input-spacing-inline);
    background-color: transparent;
  }

  .input-group__container {
    ${$c}
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
`,nl=window,rl=new WeakMap;function il(e){nl.applyFocusVisiblePolyfill&&!rl.has(e)&&(nl.applyFocusVisiblePolyfill(e),rl.set(e,void 0))}var al=R(e=>class extends e{static get properties(){return{focused:{type:Boolean,reflect:!0},focusedVisible:{type:Boolean,reflect:!0,attribute:`focused-visible`},autofocus:{type:Boolean,reflect:!0}}}constructor(){super(),this.focused=!1,this.focusedVisible=!1,this.autofocus=!1}firstUpdated(e){super.firstUpdated(e),this.__registerEventsForFocusMixin(),this.__syncAutofocusToFocusableElement()}disconnectedCallback(){super.disconnectedCallback(),this.__teardownEventsForFocusMixin()}updated(e){super.updated(e),e.has(`autofocus`)&&this.__syncAutofocusToFocusableElement()}__syncAutofocusToFocusableElement(){this._focusableNode&&(this.hasAttribute(`autofocus`)?this._focusableNode.setAttribute(`autofocus`,``):this._focusableNode.removeAttribute(`autofocus`))}focus(){this._focusableNode?.focus()}blur(){this._focusableNode?.blur()}get _focusableNode(){return this._inputNode||document.createElement(`input`)}__onFocus(){if(this.focused=!0,typeof nl.applyFocusVisiblePolyfill==`function`)this.focusedVisible=this._focusableNode.hasAttribute(`data-focus-visible-added`);else try{this.focusedVisible=this._focusableNode.matches(`:focus-visible`)}catch{this.focusedVisible=!1}}__onBlur(){this.focused=!1,this.focusedVisible=!1}__registerEventsForFocusMixin(){il(this.getRootNode()),this.__redispatchFocus=e=>{e.stopPropagation(),this.dispatchEvent(new Event(`focus`))},this._focusableNode.addEventListener(`focus`,this.__redispatchFocus),this.__redispatchBlur=e=>{e.stopPropagation(),this.dispatchEvent(new Event(`blur`))},this._focusableNode.addEventListener(`blur`,this.__redispatchBlur),this.__redispatchFocusin=e=>{e.stopPropagation(),this.__onFocus(),this.dispatchEvent(new Event(`focusin`,{bubbles:!0,composed:!0}))},this._focusableNode.addEventListener(`focusin`,this.__redispatchFocusin),this.__redispatchFocusout=e=>{e.stopPropagation(),this.__onBlur(),this.dispatchEvent(new Event(`focusout`,{bubbles:!0,composed:!0}))},this._focusableNode.addEventListener(`focusout`,this.__redispatchFocusout)}__teardownEventsForFocusMixin(){this._focusableNode&&(this._focusableNode?.removeEventListener(`focus`,this.__redispatchFocus),this._focusableNode?.removeEventListener(`blur`,this.__redispatchBlur),this._focusableNode?.removeEventListener(`focusin`,this.__redispatchFocusin),this._focusableNode?.removeEventListener(`focusout`,this.__redispatchFocusout))}});function ol(e,t){return t={exports:{}},e(t,t.exports),t.exports}var sl=`long`,cl=`short`,ll=`narrow`,B=`numeric`,ul=`2-digit`,dl={number:{decimal:{style:`decimal`},integer:{style:`decimal`,maximumFractionDigits:0},currency:{style:`currency`,currency:`USD`},percent:{style:`percent`},default:{style:`decimal`}},date:{short:{month:B,day:B,year:ul},medium:{month:cl,day:B,year:B},long:{month:sl,day:B,year:B},full:{month:sl,day:B,year:B,weekday:sl},default:{month:cl,day:B,year:B}},time:{short:{hour:B,minute:B},medium:{hour:B,minute:B,second:B},long:{hour:B,minute:B,second:B,timeZoneName:cl},full:{hour:B,minute:B,second:B,timeZoneName:cl},default:{hour:B,minute:B,second:B}},duration:{default:{hours:{minimumIntegerDigits:1,maximumFractionDigits:0},minutes:{minimumIntegerDigits:2,maximumFractionDigits:0},seconds:{minimumIntegerDigits:2,maximumFractionDigits:3}}},parseNumberPattern:function(e){if(e){var t={},n=e.match(/\b[A-Z]{3}\b/i),r=e.replace(/[^¤]/g,``).length;if(!r&&n&&(r=1),r?(t.style=`currency`,t.currencyDisplay=r===1?`symbol`:r===2?`code`:`name`,t.currency=n?n[0].toUpperCase():`USD`):e.indexOf(`%`)>=0&&(t.style=`percent`),!/[@#0]/.test(e))return t.style?t:void 0;if(t.useGrouping=e.indexOf(`,`)>=0,/E\+?[@#0]+/i.test(e)||e.indexOf(`@`)>=0){var i=e.replace(/E\+?[@#0]+|[^@#0]/gi,``);t.minimumSignificantDigits=Math.min(Math.max(i.replace(/[^@0]/g,``).length,1),21),t.maximumSignificantDigits=Math.min(Math.max(i.length,1),21)}else{for(var a=e.replace(/[^#0.]/g,``).split(`.`),o=a[0],s=o.length-1;o[s]===`0`;)--s;t.minimumIntegerDigits=Math.min(Math.max(o.length-1-s,1),21);var c=a[1]||``;for(s=0;c[s]===`0`;)++s;for(t.minimumFractionDigits=Math.min(Math.max(s,0),20);c[s]===`#`;)++s;t.maximumFractionDigits=Math.min(Math.max(s,0),20)}return t}},parseDatePattern:function(e){if(e){for(var t={},n=0;n<e.length;){for(var r=e[n],i=1;e[++n]===r;)++i;switch(r){case`G`:t.era=i===5?ll:i===4?sl:cl;break;case`y`:case`Y`:t.year=i===2?ul:B;break;case`M`:case`L`:i=Math.min(Math.max(i-1,0),4),t.month=[B,ul,cl,sl,ll][i];break;case`E`:case`e`:case`c`:t.weekday=i===5?ll:i===4?sl:cl;break;case`d`:case`D`:t.day=i===2?ul:B;break;case`h`:case`K`:t.hour12=!0,t.hour=i===2?ul:B;break;case`H`:case`k`:t.hour12=!1,t.hour=i===2?ul:B;break;case`m`:t.minute=i===2?ul:B;break;case`s`:case`S`:t.second=i===2?ul:B;break;case`z`:case`Z`:case`v`:case`V`:t.timeZoneName=i===1?cl:sl;break}}return Object.keys(t).length?t:void 0}}},fl=function(e,t){if(typeof e==`string`&&t[e])return e;for(var n=[].concat(e||[]),r=0,i=n.length;r<i;++r)for(var a=n[r].split(`-`);a.length;){var o=a.join(`-`);if(t[o])return o;a.pop()}},pl=`zero`,V=`one`,H=`two`,U=`few`,W=`many`,G=`other`,K=[function(e){return+e==1?V:G},function(e){var t=+e;return 0<=t&&t<=1?V:G},function(e){var t=Math.floor(Math.abs(+e)),n=+e;return t===0||n===1?V:G},function(e){var t=+e;return t===0?pl:t===1?V:t===2?H:3<=t%100&&t%100<=10?U:11<=t%100&&t%100<=99?W:G},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length;return t===1&&n===0?V:G},function(e){var t=+e;return t%10==1&&t%100!=11?V:2<=t%10&&t%10<=4&&(t%100<12||14<t%100)?U:t%10==0||5<=t%10&&t%10<=9||11<=t%100&&t%100<=14?W:G},function(e){var t=+e;return t%10==1&&t%100!=11&&t%100!=71&&t%100!=91?V:t%10==2&&t%100!=12&&t%100!=72&&t%100!=92?H:(3<=t%10&&t%10<=4||t%10==9)&&(t%100<10||19<t%100)&&(t%100<70||79<t%100)&&(t%100<90||99<t%100)?U:t!==0&&t%1e6==0?W:G},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length,r=+(e+`.`).split(`.`)[1];return n===0&&t%10==1&&t%100!=11||r%10==1&&r%100!=11?V:n===0&&2<=t%10&&t%10<=4&&(t%100<12||14<t%100)||2<=r%10&&r%10<=4&&(r%100<12||14<r%100)?U:G},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length;return t===1&&n===0?V:2<=t&&t<=4&&n===0?U:n===0?G:W},function(e){var t=+e;return t===0?pl:t===1?V:t===2?H:t===3?U:t===6?W:G},function(e){var t=Math.floor(Math.abs(+e)),n=+(``+e).replace(/^[^.]*.?|0+$/g,``);return+e==1||n!==0&&(t===0||t===1)?V:G},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length,r=+(e+`.`).split(`.`)[1];return n===0&&t%100==1||r%100==1?V:n===0&&t%100==2||r%100==2?H:n===0&&3<=t%100&&t%100<=4||3<=r%100&&r%100<=4?U:G},function(e){var t=Math.floor(Math.abs(+e));return t===0||t===1?V:G},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length,r=+(e+`.`).split(`.`)[1];return n===0&&(t===1||t===2||t===3)||n===0&&t%10!=4&&t%10!=6&&t%10!=9||n!==0&&r%10!=4&&r%10!=6&&r%10!=9?V:G},function(e){var t=+e;return t===1?V:t===2?H:3<=t&&t<=6?U:7<=t&&t<=10?W:G},function(e){var t=+e;return t===1||t===11?V:t===2||t===12?H:3<=t&&t<=10||13<=t&&t<=19?U:G},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length;return n===0&&t%10==1?V:n===0&&t%10==2?H:n===0&&(t%100==0||t%100==20||t%100==40||t%100==60||t%100==80)?U:n===0?G:W},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length,r=+e;return t===1&&n===0?V:t===2&&n===0?H:n===0&&(r<0||10<r)&&r%10==0?W:G},function(e){var t=Math.floor(Math.abs(+e)),n=+(``+e).replace(/^[^.]*.?|0+$/g,``);return n===0&&t%10==1&&t%100!=11||n!==0?V:G},function(e){var t=+e;return t===1?V:t===2?H:G},function(e){var t=+e;return t===0?pl:t===1?V:G},function(e){var t=Math.floor(Math.abs(+e)),n=+e;return n===0?pl:(t===0||t===1)&&n!==0?V:G},function(e){var t=+(e+`.`).split(`.`)[1],n=+e;return n%10==1&&(n%100<11||19<n%100)?V:2<=n%10&&n%10<=9&&(n%100<11||19<n%100)?U:t===0?G:W},function(e){var t=(e+`.`).split(`.`)[1].length,n=+(e+`.`).split(`.`)[1],r=+e;return r%10==0||11<=r%100&&r%100<=19||t===2&&11<=n%100&&n%100<=19?pl:r%10==1&&r%100!=11||t===2&&n%10==1&&n%100!=11||t!==2&&n%10==1?V:G},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length,r=+(e+`.`).split(`.`)[1];return n===0&&t%10==1&&t%100!=11||r%10==1&&r%100!=11?V:G},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length,r=+e;return t===1&&n===0?V:n!==0||r===0||r!==1&&1<=r%100&&r%100<=19?U:G},function(e){var t=+e;return t===1?V:t===0||2<=t%100&&t%100<=10?U:11<=t%100&&t%100<=19?W:G},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length;return t===1&&n===0?V:n===0&&2<=t%10&&t%10<=4&&(t%100<12||14<t%100)?U:n===0&&t!==1&&0<=t%10&&t%10<=1||n===0&&5<=t%10&&t%10<=9||n===0&&12<=t%100&&t%100<=14?W:G},function(e){var t=Math.floor(Math.abs(+e));return 0<=t&&t<=1?V:G},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length;return n===0&&t%10==1&&t%100!=11?V:n===0&&2<=t%10&&t%10<=4&&(t%100<12||14<t%100)?U:n===0&&t%10==0||n===0&&5<=t%10&&t%10<=9||n===0&&11<=t%100&&t%100<=14?W:G},function(e){var t=Math.floor(Math.abs(+e)),n=+e;return t===0||n===1?V:2<=n&&n<=10?U:G},function(e){var t=Math.floor(Math.abs(+e)),n=+(e+`.`).split(`.`)[1],r=+e;return r===0||r===1||t===0&&n===1?V:G},function(e){var t=Math.floor(Math.abs(+e)),n=(e+`.`).split(`.`)[1].length;return n===0&&t%100==1?V:n===0&&t%100==2?H:n===0&&3<=t%100&&t%100<=4||n!==0?U:G},function(e){var t=+e;return 0<=t&&t<=1||11<=t&&t<=99?V:G},function(e){var t=+e;return t===1||t===5||t===7||t===8||t===9||t===10?V:t===2||t===3?H:t===4?U:t===6?W:G},function(e){var t=Math.floor(Math.abs(+e));return t%10==1||t%10==2||t%10==5||t%10==7||t%10==8||t%100==20||t%100==50||t%100==70||t%100==80?V:t%10==3||t%10==4||t%1e3==100||t%1e3==200||t%1e3==300||t%1e3==400||t%1e3==500||t%1e3==600||t%1e3==700||t%1e3==800||t%1e3==900?U:t===0||t%10==6||t%100==40||t%100==60||t%100==90?W:G},function(e){var t=+e;return(t%10==2||t%10==3)&&t%100!=12&&t%100!=13?U:G},function(e){var t=+e;return t===1||t===3?V:t===2?H:t===4?U:G},function(e){var t=+e;return t===0||t===7||t===8||t===9?pl:t===1?V:t===2?H:t===3||t===4?U:t===5||t===6?W:G},function(e){var t=+e;return t%10==1&&t%100!=11?V:t%10==2&&t%100!=12?H:t%10==3&&t%100!=13?U:G},function(e){var t=+e;return t===1||t===11?V:t===2||t===12?H:t===3||t===13?U:G},function(e){var t=+e;return t===1?V:t===2||t===3?H:t===4?U:t===6?W:G},function(e){var t=+e;return t===1||t===5?V:G},function(e){var t=+e;return t===11||t===8||t===80||t===800?W:G},function(e){var t=Math.floor(Math.abs(+e));return t===1?V:t===0||2<=t%100&&t%100<=20||t%100==40||t%100==60||t%100==80?W:G},function(e){var t=+e;return t%10==6||t%10==9||t%10==0&&t!==0?W:G},function(e){var t=Math.floor(Math.abs(+e));return t%10==1&&t%100!=11?V:t%10==2&&t%100!=12?H:(t%10==7||t%10==8)&&t%100!=17&&t%100!=18?W:G},function(e){var t=+e;return t===1?V:t===2||t===3?H:t===4?U:G},function(e){var t=+e;return 1<=t&&t<=4?V:G},function(e){var t=+e;return t===1||t===5||7<=t&&t<=9?V:t===2||t===3?H:t===4?U:t===6?W:G},function(e){var t=+e;return t===1?V:t%10==4&&t%100!=14?W:G},function(e){var t=+e;return(t%10==1||t%10==2)&&t%100!=11&&t%100!=12?V:G},function(e){var t=+e;return t%10==6||t%10==9||t===10?U:G},function(e){var t=+e;return t%10==3&&t%100!=13?U:G}],ml={af:{cardinal:K[0]},ak:{cardinal:K[1]},am:{cardinal:K[2]},ar:{cardinal:K[3]},ars:{cardinal:K[3]},as:{cardinal:K[2],ordinal:K[34]},asa:{cardinal:K[0]},ast:{cardinal:K[4]},az:{cardinal:K[0],ordinal:K[35]},be:{cardinal:K[5],ordinal:K[36]},bem:{cardinal:K[0]},bez:{cardinal:K[0]},bg:{cardinal:K[0]},bh:{cardinal:K[1]},bn:{cardinal:K[2],ordinal:K[34]},br:{cardinal:K[6]},brx:{cardinal:K[0]},bs:{cardinal:K[7]},ca:{cardinal:K[4],ordinal:K[37]},ce:{cardinal:K[0]},cgg:{cardinal:K[0]},chr:{cardinal:K[0]},ckb:{cardinal:K[0]},cs:{cardinal:K[8]},cy:{cardinal:K[9],ordinal:K[38]},da:{cardinal:K[10]},de:{cardinal:K[4]},dsb:{cardinal:K[11]},dv:{cardinal:K[0]},ee:{cardinal:K[0]},el:{cardinal:K[0]},en:{cardinal:K[4],ordinal:K[39]},eo:{cardinal:K[0]},es:{cardinal:K[0]},et:{cardinal:K[4]},eu:{cardinal:K[0]},fa:{cardinal:K[2]},ff:{cardinal:K[12]},fi:{cardinal:K[4]},fil:{cardinal:K[13],ordinal:K[0]},fo:{cardinal:K[0]},fr:{cardinal:K[12],ordinal:K[0]},fur:{cardinal:K[0]},fy:{cardinal:K[4]},ga:{cardinal:K[14],ordinal:K[0]},gd:{cardinal:K[15],ordinal:K[40]},gl:{cardinal:K[4]},gsw:{cardinal:K[0]},gu:{cardinal:K[2],ordinal:K[41]},guw:{cardinal:K[1]},gv:{cardinal:K[16]},ha:{cardinal:K[0]},haw:{cardinal:K[0]},he:{cardinal:K[17]},hi:{cardinal:K[2],ordinal:K[41]},hr:{cardinal:K[7]},hsb:{cardinal:K[11]},hu:{cardinal:K[0],ordinal:K[42]},hy:{cardinal:K[12],ordinal:K[0]},ia:{cardinal:K[4]},io:{cardinal:K[4]},is:{cardinal:K[18]},it:{cardinal:K[4],ordinal:K[43]},iu:{cardinal:K[19]},iw:{cardinal:K[17]},jgo:{cardinal:K[0]},ji:{cardinal:K[4]},jmc:{cardinal:K[0]},ka:{cardinal:K[0],ordinal:K[44]},kab:{cardinal:K[12]},kaj:{cardinal:K[0]},kcg:{cardinal:K[0]},kk:{cardinal:K[0],ordinal:K[45]},kkj:{cardinal:K[0]},kl:{cardinal:K[0]},kn:{cardinal:K[2]},ks:{cardinal:K[0]},ksb:{cardinal:K[0]},ksh:{cardinal:K[20]},ku:{cardinal:K[0]},kw:{cardinal:K[19]},ky:{cardinal:K[0]},lag:{cardinal:K[21]},lb:{cardinal:K[0]},lg:{cardinal:K[0]},ln:{cardinal:K[1]},lt:{cardinal:K[22]},lv:{cardinal:K[23]},mas:{cardinal:K[0]},mg:{cardinal:K[1]},mgo:{cardinal:K[0]},mk:{cardinal:K[24],ordinal:K[46]},ml:{cardinal:K[0]},mn:{cardinal:K[0]},mo:{cardinal:K[25],ordinal:K[0]},mr:{cardinal:K[2],ordinal:K[47]},mt:{cardinal:K[26]},nah:{cardinal:K[0]},naq:{cardinal:K[19]},nb:{cardinal:K[0]},nd:{cardinal:K[0]},ne:{cardinal:K[0],ordinal:K[48]},nl:{cardinal:K[4]},nn:{cardinal:K[0]},nnh:{cardinal:K[0]},no:{cardinal:K[0]},nr:{cardinal:K[0]},nso:{cardinal:K[1]},ny:{cardinal:K[0]},nyn:{cardinal:K[0]},om:{cardinal:K[0]},or:{cardinal:K[0],ordinal:K[49]},os:{cardinal:K[0]},pa:{cardinal:K[1]},pap:{cardinal:K[0]},pl:{cardinal:K[27]},prg:{cardinal:K[23]},ps:{cardinal:K[0]},pt:{cardinal:K[28]},"pt-PT":{cardinal:K[4]},rm:{cardinal:K[0]},ro:{cardinal:K[25],ordinal:K[0]},rof:{cardinal:K[0]},ru:{cardinal:K[29]},rwk:{cardinal:K[0]},saq:{cardinal:K[0]},sc:{cardinal:K[4],ordinal:K[43]},scn:{cardinal:K[4],ordinal:K[43]},sd:{cardinal:K[0]},sdh:{cardinal:K[0]},se:{cardinal:K[19]},seh:{cardinal:K[0]},sh:{cardinal:K[7]},shi:{cardinal:K[30]},si:{cardinal:K[31]},sk:{cardinal:K[8]},sl:{cardinal:K[32]},sma:{cardinal:K[19]},smi:{cardinal:K[19]},smj:{cardinal:K[19]},smn:{cardinal:K[19]},sms:{cardinal:K[19]},sn:{cardinal:K[0]},so:{cardinal:K[0]},sq:{cardinal:K[0],ordinal:K[50]},sr:{cardinal:K[7]},ss:{cardinal:K[0]},ssy:{cardinal:K[0]},st:{cardinal:K[0]},sv:{cardinal:K[4],ordinal:K[51]},sw:{cardinal:K[4]},syr:{cardinal:K[0]},ta:{cardinal:K[0]},te:{cardinal:K[0]},teo:{cardinal:K[0]},ti:{cardinal:K[1]},tig:{cardinal:K[0]},tk:{cardinal:K[0],ordinal:K[52]},tl:{cardinal:K[13],ordinal:K[0]},tn:{cardinal:K[0]},tr:{cardinal:K[0]},ts:{cardinal:K[0]},tzm:{cardinal:K[33]},ug:{cardinal:K[0]},uk:{cardinal:K[29],ordinal:K[53]},ur:{cardinal:K[4]},uz:{cardinal:K[0]},ve:{cardinal:K[0]},vo:{cardinal:K[0]},vun:{cardinal:K[0]},wa:{cardinal:K[1]},wae:{cardinal:K[0]},xh:{cardinal:K[0]},xog:{cardinal:K[0]},yi:{cardinal:K[4]},zu:{cardinal:K[2]},lo:{ordinal:K[0]},ms:{ordinal:K[0]},vi:{ordinal:K[0]}},hl=ol(function(e,t){t=e.exports=function(e,t,r){return n(e,null,t||`en`,r||{},!0)},t.toParts=function(e,t,r){return n(e,null,t||`en`,r||{},!1)};function n(e,t,n,i,a){var o=e.map(function(e){return r(e,t,n,i,a)});return a?o.length===1?o[0]:function(e){for(var t=``,n=0;n<o.length;++n)t+=o[n](e);return t}:function(e){return o.reduce(function(t,n){return t.concat(n(e))},[])}}function r(e,t,r,a,o){if(typeof e==`string`){var s=e;return function(){return s}}var c=e[0],l=e[1];if(t&&e[0]===`#`){c=t[0];var u=t[2],f=(a.number||d.number)([c,`number`],r);return function(e){return f(i(c,e)-u,e)}}var p;l===`plural`||l===`selectordinal`?(p={},Object.keys(e[3]).forEach(function(t){p[t]=n(e[3][t],e,r,a,o)}),e=[e[0],e[1],e[2],p]):e[2]&&typeof e[2]==`object`&&(p={},Object.keys(e[2]).forEach(function(t){p[t]=n(e[2][t],e,r,a,o)}),e=[e[0],e[1],p]);var m=l&&(a[l]||d[l]);if(m){var h=m(e,r);return function(e){return h(i(c,e),e)}}return o?function(e){return String(i(c,e))}:function(e){return i(c,e)}}function i(e,t){if(t&&e in t)return t[e];for(var n=e.split(`.`),r=t,i=0,a=n.length;r&&i<a;++i)r=r[n[i]];return r}function a(e,t){var n=e[2],r=dl.number[n]||dl.parseNumberPattern(n)||dl.number.default;return new Intl.NumberFormat(t,r).format}function o(e,t){var n=e[2],r=dl.duration[n]||dl.duration.default,i=new Intl.NumberFormat(t,r.seconds).format,a=new Intl.NumberFormat(t,r.minutes).format,o=new Intl.NumberFormat(t,r.hours).format,s=/^fi$|^fi-|^da/.test(String(t))?`.`:`:`;return function(e,t){if(e=+e,!isFinite(e))return i(e);var n=~~(e/60/60),r=~~(e/60%60),c=(n?o(Math.abs(n))+s:``)+a(Math.abs(r))+s+i(Math.abs(e%60));return e<0?o(-1).replace(o(1),c):c}}function s(e,t){var n=e[1],r=e[2],i=dl[n][r]||dl.parseDatePattern(r)||dl[n].default;return new Intl.DateTimeFormat(t,i).format}function c(e,t){var n=e[1]===`selectordinal`?`ordinal`:`cardinal`,r=e[2],i=e[3],a;if(Intl.PluralRules&&Intl.PluralRules.supportedLocalesOf(t).length>0)a=new Intl.PluralRules(t,{type:n});else{var o=fl(t,ml);a={select:o&&ml[o][n]||l}}return function(e,t){return(i[`=`+ +e]||i[a.select(e-r)]||i.other)(t)}}function l(){return`other`}function u(e,t){var n=e[2];return function(e,t){return(n[e]||n.other)(t)}}var d={number:a,ordinal:a,spellout:a,duration:o,date:s,time:s,plural:c,selectordinal:c,select:u};t.types=d});hl.toParts,hl.types;var gl=ol(function(e,t){var n=`{`,r=`}`,i=`,`,a=`#`,o=`<`,s=`>`,c=`</`,l=`/>`,u=`'`,d=`offset:`,f=[`number`,`date`,`time`,`ordinal`,`duration`,`spellout`],p=[`plural`,`select`,`selectordinal`];t=e.exports=function(e,t){return m({pattern:String(e),index:0,tagsType:t&&t.tagsType||null,tokens:t&&t.tokens||null},``)};function m(e,t){var n=e.pattern,i=n.length,a=[],o=e.index,s=h(e,t);for(s&&a.push(s),s&&e.tokens&&e.tokens.push([`text`,n.slice(o,e.index)]);e.index<i;){if(n[e.index]===r){if(!t)throw C(e);break}if(t&&e.tagsType&&n.slice(e.index,e.index+c.length)===c)break;a.push(v(e)),o=e.index,s=h(e,t),s&&a.push(s),s&&e.tokens&&e.tokens.push([`text`,n.slice(o,e.index)])}return a}function h(e,t){for(var i=e.pattern,s=i.length,c=t===`plural`||t===`selectordinal`,l=!!e.tagsType,d=t===`{style}`,f=``;e.index<s;){var p=i[e.index];if(p===n||p===r||c&&p===a||l&&p===o||d&&g(p.charCodeAt(0)))break;if(p===u)if(p=i[++e.index],p===u)f+=p,++e.index;else if(p===n||p===r||c&&p===a||l&&p===o||d)for(f+=p;++e.index<s;)if(p=i[e.index],p===u&&i[e.index+1]===u)f+=u,++e.index;else if(p===u){++e.index;break}else f+=p;else f+=u;else f+=p,++e.index}return f}function g(e){return e>=9&&e<=13||e===32||e===133||e===160||e===6158||e>=8192&&e<=8205||e===8232||e===8233||e===8239||e===8287||e===8288||e===12288||e===65279}function _(e){for(var t=e.pattern,n=t.length,r=e.index;e.index<n&&g(t.charCodeAt(e.index));)++e.index;r<e.index&&e.tokens&&e.tokens.push([`space`,e.pattern.slice(r,e.index)])}function v(e){var t=e.pattern;if(t[e.index]===a)return e.tokens&&e.tokens.push([`syntax`,a]),++e.index,[a];var o=y(e);if(o)return o;if(t[e.index]!==n)throw C(e,n);e.tokens&&e.tokens.push([`syntax`,n]),++e.index,_(e);var s=b(e);if(!s)throw C(e,`placeholder id`);e.tokens&&e.tokens.push([`id`,s]),_(e);var c=t[e.index];if(c===r)return e.tokens&&e.tokens.push([`syntax`,r]),++e.index,[s];if(c!==i)throw C(e,i+` or `+r);e.tokens&&e.tokens.push([`syntax`,i]),++e.index,_(e);var l=b(e);if(!l)throw C(e,`placeholder type`);if(e.tokens&&e.tokens.push([`type`,l]),_(e),c=t[e.index],c===r){if(e.tokens&&e.tokens.push([`syntax`,r]),l===`plural`||l===`selectordinal`||l===`select`)throw C(e,l+` sub-messages`);return++e.index,[s,l]}if(c!==i)throw C(e,i+` or `+r);e.tokens&&e.tokens.push([`syntax`,i]),++e.index,_(e);var u;if(l===`plural`||l===`selectordinal`){var d=te(e);_(e),u=[s,l,d,x(e,l)]}else if(l===`select`)u=[s,l,x(e,l)];else if(f.indexOf(l)>=0)u=[s,l,ee(e)];else{var p=e.index,m=ee(e);_(e),t[e.index]===n&&(e.index=p,m=x(e,l)),u=[s,l,m]}if(_(e),t[e.index]!==r)throw C(e,r);return e.tokens&&e.tokens.push([`syntax`,r]),++e.index,u}function y(e){var t=e.tagsType;if(!(!t||e.pattern[e.index]!==o)){if(e.pattern.slice(e.index,e.index+c.length)===c)throw C(e,null,`closing tag without matching opening tag`);e.tokens&&e.tokens.push([`syntax`,o]),++e.index;var n=b(e,!0);if(!n)throw C(e,`placeholder id`);if(e.tokens&&e.tokens.push([`id`,n]),_(e),e.pattern.slice(e.index,e.index+l.length)===l)return e.tokens&&e.tokens.push([`syntax`,l]),e.index+=l.length,[n,t];if(e.pattern[e.index]!==s)throw C(e,s);e.tokens&&e.tokens.push([`syntax`,s]),++e.index;var r=m(e,t),i=e.index;if(e.pattern.slice(e.index,e.index+c.length)!==c)throw C(e,c+n+s);e.tokens&&e.tokens.push([`syntax`,c]),e.index+=c.length;var a=b(e,!0);if(a&&e.tokens&&e.tokens.push([`id`,a]),n!==a)throw e.index=i,C(e,c+n+s,c+a+s);if(_(e),e.pattern[e.index]!==s)throw C(e,s);return e.tokens&&e.tokens.push([`syntax`,s]),++e.index,[n,t,{children:r}]}}function b(e,t){for(var c=e.pattern,l=c.length,d=``;e.index<l;){var f=c[e.index];if(f===n||f===r||f===i||f===a||f===u||g(f.charCodeAt(0))||t&&(f===o||f===s||f===`/`))break;d+=f,++e.index}return d}function ee(e){var t=e.index,n=h(e,`{style}`);if(!n)throw C(e,`placeholder style name`);return e.tokens&&e.tokens.push([`style`,e.pattern.slice(t,e.index)]),n}function te(e){var t=e.pattern,n=t.length,r=0;if(t.slice(e.index,e.index+d.length)===d){e.tokens&&e.tokens.push([`offset`,`offset`],[`syntax`,`:`]),e.index+=d.length,_(e);for(var i=e.index;e.index<n&&ne(t.charCodeAt(e.index));)++e.index;if(i===e.index)throw C(e,`offset number`);e.tokens&&e.tokens.push([`number`,t.slice(i,e.index)]),r=+t.slice(i,e.index)}return r}function ne(e){return e>=48&&e<=57}function x(e,t){for(var n=e.pattern,i=n.length,a={};e.index<i&&n[e.index]!==r;){var o=b(e);if(!o)throw C(e,`sub-message selector`);e.tokens&&e.tokens.push([`selector`,o]),_(e),a[o]=S(e,t),_(e)}if(!a.other&&p.indexOf(t)>=0)throw C(e,null,null,`"other" sub-message must be specified in `+t);return a}function S(e,t){if(e.pattern[e.index]!==n)throw C(e,n+` to start sub-message`);e.tokens&&e.tokens.push([`syntax`,n]),++e.index;var i=m(e,t);if(e.pattern[e.index]!==r)throw C(e,r+` to end sub-message`);return e.tokens&&e.tokens.push([`syntax`,r]),++e.index,i}function C(e,t,n,r){var i=e.pattern,a=i.slice(0,e.index).split(/\r?\n/),o=e.index,s=a.length,c=a.slice(-1)[0].length;return n||=e.index>=i.length?`end of message pattern`:b(e)||i[e.index],r||=w(t,n),r+=` in `+i.replace(/\r?\n/g,`
`),new T(r,t,n,o,s,c)}function w(e,t){return e?`Expected `+e+` but found `+t:`Unexpected `+t+` found`}function T(e,t,n,r,i,a){Error.call(this,e),this.name=`SyntaxError`,this.message=e,this.expected=t,this.found=n,this.offset=r,this.line=i,this.column=a}T.prototype=Object.create(Error.prototype),t.SyntaxError=T});gl.SyntaxError;var _l=RegExp(`^(`+Object.keys(ml).join(`|`)+`)\\b`),vl=new WeakMap;function yl(e,t,n){if(!(this instanceof yl)||vl.has(this))throw TypeError(`calling MessageFormat constructor without new is invalid`);var r=gl(e);vl.set(this,{ast:r,format:hl(r,t,n&&n.types),locale:yl.supportedLocalesOf(t)[0]||`en`,locales:t,options:n})}var bl=yl;Object.defineProperties(yl.prototype,{format:{configurable:!0,get:function(){var e=vl.get(this);if(!e)throw TypeError(`MessageFormat.prototype.format called on value that's not an object initialized as a MessageFormat`);return e.format}},formatToParts:{configurable:!0,writable:!0,value:function(e){var t=vl.get(this);if(!t)throw TypeError(`MessageFormat.prototype.formatToParts called on value that's not an object initialized as a MessageFormat`);return(t.toParts||=hl.toParts(t.ast,t.locales,t.options&&t.options.types))(e)}},resolvedOptions:{configurable:!0,writable:!0,value:function(){var e=vl.get(this);if(!e)throw TypeError(`MessageFormat.prototype.resolvedOptions called on value that's not an object initialized as a MessageFormat`);return{locale:e.locale}}}}),typeof Symbol<`u`&&Object.defineProperty(yl.prototype,Symbol.toStringTag,{value:`Object`}),Object.defineProperties(yl,{supportedLocalesOf:{configurable:!0,writable:!0,value:function(e){return[].concat(Intl.NumberFormat.supportedLocalesOf(e),Intl.DateTimeFormat.supportedLocalesOf(e),Intl.PluralRules?Intl.PluralRules.supportedLocalesOf(e):[],[].concat(e||[]).filter(function(e){return _l.test(e)})).filter(function(e,t,n){return n.indexOf(e)===t})}}});function xl(e){return!!(e&&e.default&&typeof e.default==`object`&&Object.keys(e).length===1)}var Sl=globalThis.document?.documentElement,Cl=class extends EventTarget{formatNumberOptions={returnIfNaN:``,postProcessors:new Map};formatDateOptions={postProcessors:new Map};#e=!1;#t=``;#n=null;__storage={};__namespacePatternsMap=new Map;__namespaceLoadersCache={};__namespaceLoaderPromisesCache={};get locale(){return this.#e?this.#t||``:Sl.lang||``}set locale(e){if(this.#r(e),!this.#e){let t=Sl.lang;this._setHtmlLangAttribute(e),this._onLocaleChanged(e,t);return}let t=this.#t;this.#t=e,this.#n===null&&this._setHtmlLangAttribute(e),this._onLocaleChanged(e,t)}get loadingComplete(){return typeof this.__namespaceLoaderPromisesCache[this.locale]==`object`?Promise.all(Object.values(this.__namespaceLoaderPromisesCache[this.locale])):Promise.resolve()}constructor({allowOverridesForExistingNamespaces:e=!1,autoLoadOnLocaleChange:t=!1,showKeyAsFallback:n=!1,fallbackLocale:r=``}={}){super(),this.__allowOverridesForExistingNamespaces=e,this._autoLoadOnLocaleChange=!!t,this._showKeyAsFallback=n,this._fallbackLocale=r;let i=Sl.getAttribute(`data-localize-lang`);this.#e=!!i,this.#e&&(this.locale=i,this._setupTranslationToolSupport()),Sl.lang||=this.locale||`en-GB`,this._setupHtmlLangAttributeObserver()}addData(e,t,n){if(!this.__allowOverridesForExistingNamespaces&&this._isNamespaceInCache(e,t))throw Error(`Namespace "${t}" has been already added for the locale "${e}".`);this.__storage[e]=this.__storage[e]||{},this.__allowOverridesForExistingNamespaces?this.__storage[e][t]={...this.__storage[e][t],...n}:this.__storage[e][t]=n}setupNamespaceLoader(e,t){this.__namespacePatternsMap.set(e,t)}loadNamespaces(e,{locale:t}={}){return Promise.all(e.map(e=>this.loadNamespace(e,{locale:t})))}loadNamespace(e,{locale:t=this.locale}={locale:this.locale}){let n=typeof e==`object`,r=n?Object.keys(e)[0]:e;return this._isNamespaceInCache(t,r)?Promise.resolve():this._getCachedNamespaceLoaderPromise(t,r)||this._loadNamespaceData(t,e,n,r)}msg(e,t,n={}){let r=n.locale?n.locale:this.locale,i=this._getMessageForKeys(e,r);return i?new bl(i,r).format(t):``}teardown(){this._teardownHtmlLangAttributeObserver()}reset(){this.__storage={},this.__namespacePatternsMap=new Map,this.__namespaceLoadersCache={},this.__namespaceLoaderPromisesCache={}}setDatePostProcessorForLocale({locale:e,postProcessor:t}){this.formatDateOptions?.postProcessors.set(e,t)}setNumberPostProcessorForLocale({locale:e,postProcessor:t}){this.formatNumberOptions?.postProcessors.set(e,t)}_setupTranslationToolSupport(){this.#n=Sl.lang||null}_setHtmlLangAttribute(e){this._teardownHtmlLangAttributeObserver(),Sl.lang=e,this._setupHtmlLangAttributeObserver()}_setupHtmlLangAttributeObserver(){this._htmlLangAttributeObserver||=new MutationObserver(e=>{e.forEach(e=>{this.#e?Sl.lang===`auto`?(this.#n=null,this._setHtmlLangAttribute(this.locale)):this.#n=document.documentElement.lang:this._onLocaleChanged(document.documentElement.lang,e.oldValue||``)})}),this._htmlLangAttributeObserver.observe(document.documentElement,{attributes:!0,attributeFilter:[`lang`],attributeOldValue:!0})}_teardownHtmlLangAttributeObserver(){this._htmlLangAttributeObserver&&this._htmlLangAttributeObserver.disconnect()}_isNamespaceInCache(e,t){return!!(this.__storage[e]&&this.__storage[e][t])}_getCachedNamespaceLoaderPromise(e,t){return this.__namespaceLoaderPromisesCache[e]?this.__namespaceLoaderPromisesCache[e][t]:null}_loadNamespaceData(e,t,n,r){let i=this._getNamespaceLoader(t,n,r),a=this._getNamespaceLoaderPromise(i,e,r);return this._cacheNamespaceLoaderPromise(e,r,a),a.then(t=>{if(this.__namespaceLoaderPromisesCache[e]&&this.__namespaceLoaderPromisesCache[e][r]===a){let n=xl(t)?t.default:t;this.addData(e,r,n)}})}_getNamespaceLoader(e,t,n){let r=this.__namespaceLoadersCache[n];if(r||(t?(r=e[n],this.__namespaceLoadersCache[n]=r):(r=this._lookupNamespaceLoader(n),this.__namespaceLoadersCache[n]=r)),!r)throw Error(`Namespace "${n}" was not properly setup.`);return this.__namespaceLoadersCache[n]=r,r}_getNamespaceLoaderPromise(e,t,n,r=this._fallbackLocale){return e(t,n).catch(()=>{let i=this._getLangFromLocale(t);return e(i,n).catch(()=>{if(r)return this._getNamespaceLoaderPromise(e,r,n,``).catch(()=>{let e=this._getLangFromLocale(r);throw Error(`Data for namespace "${n}" and current locale "${t}" or fallback locale "${r}" could not be loaded. Make sure you have data either for locale "${t}" (and/or generic language "${i}") or for fallback "${r}" (and/or "${e}").`)});throw Error(`Data for namespace "${n}" and locale "${t}" could not be loaded. Make sure you have data for locale "${t}" (and/or generic language "${i}").`)})})}_cacheNamespaceLoaderPromise(e,t,n){this.__namespaceLoaderPromisesCache[e]||(this.__namespaceLoaderPromisesCache[e]={}),this.__namespaceLoaderPromisesCache[e][t]=n}_lookupNamespaceLoader(e){for(let[t,n]of this.__namespacePatternsMap){let r=typeof t==`string`&&t===e,i=typeof t==`object`&&t.constructor.name===`RegExp`&&t.test(e);if(r||i)return n}return null}_getLangFromLocale(e){return e.substring(0,2)}_onLocaleChanged(e,t){this.dispatchEvent(new CustomEvent(`__localeChanging`)),e!==t&&(this._autoLoadOnLocaleChange?(this._loadAllMissing(e,t),this.loadingComplete.then(()=>{this.dispatchEvent(new CustomEvent(`localeChanged`,{detail:{newLocale:e,oldLocale:t}}))})):this.dispatchEvent(new CustomEvent(`localeChanged`,{detail:{newLocale:e,oldLocale:t}})))}_loadAllMissing(e,t){let n=this.__storage[t]||{},r=this.__storage[e]||{};Object.keys(n).forEach(t=>{r[t]||this.loadNamespace(t,{locale:e})})}_getMessageForKeys(e,t){if(typeof e==`string`)return this._getMessageForKey(e,t);let n=Array.from(e).reverse(),r,i;for(;n.length;)if(r=n.pop(),i=this._getMessageForKey(r,t),i)return i}_getMessageForKey(e,t){if(!e||e.indexOf(`:`)===-1)throw Error(`Namespace is missing in the key "${e}". The format for keys is "namespace:name".`);let[n,r]=e.split(`:`),i=this.__storage[t],a=i?i[n]:{},o=r.split(`.`).reduce((e,t)=>typeof e==`object`?e[t]:e,a);return String(o||(this._showKeyAsFallback?e:``))}#r(e){if(!e.includes(`-`))throw Error(`
      Locale was set to ${e}.
      Language only locales are not allowed, please use the full language locale e.g. 'en-GB' instead of 'en'.
      See https://github.com/ing-bank/lion/issues/187 for more information.
    `)}get _supportExternalTranslationTools(){return this.#e}set _supportExternalTranslationTools(e){this.#e=e}get _langAttrSetByTranslationTool(){return this.#t}set _langAttrSetByTranslationTool(e){this.#t=e}},wl=Symbol.for(`lion::SingletonManagerClassStorage`),Tl=globalThis||window,El=class{constructor(){this._map=Tl[wl]?Tl[wl]:Tl[wl]=new Map}set(e,t){this.has(e)||this._map.set(e,t)}get(e){return this._map.get(e)}has(e){return this._map.has(e)}},Dl=e=>{let t=null,n=()=>(t===null&&(t=e()),t);return new Proxy({},{get(e,t){let r=n();return t===`addEventListener`||t===`removeEventListener`?Reflect.get(r,t).bind(r):t===`__instance_for_testing`?r:Reflect.get(r,t,r)},set(e,t,r){return Reflect.set(n(),t,r)},getOwnPropertyDescriptor(e,t){return Reflect.getOwnPropertyDescriptor(n(),t)},getPrototypeOf(){return Reflect.getPrototypeOf(n())}})},Ol=new El;function kl(){if(!Ol.has(`@lion/ui::localize::0.x`)){let e=new Cl({autoLoadOnLocaleChange:!0,fallbackLocale:`en-GB`});Ol.set(`@lion/ui::localize::0.x`,e)}return Ol.get(`@lion/ui::localize::0.x`)}function Al(){return Dl(kl)}var jl=(e,t)=>{let n=e._$AN;if(n===void 0)return!1;for(let e of n)e._$AO?.(t,!1),jl(e,t);return!0},Ml=e=>{let t,n;do{if((t=e._$AM)===void 0)break;n=t._$AN,n.delete(e),e=t}while(n?.size===0)},Nl=e=>{for(let t;t=e._$AM;e=t){let n=t._$AN;if(n===void 0)t._$AN=n=new Set;else if(n.has(e))break;n.add(e),Il(t)}};function Pl(e){this._$AN===void 0?this._$AM=e:(Ml(this),this._$AM=e,Nl(this))}function Fl(e,t=!1,n=0){let r=this._$AH,i=this._$AN;if(i!==void 0&&i.size!==0)if(t)if(Array.isArray(r))for(let e=n;e<r.length;e++)jl(r[e],!1),Ml(r[e]);else r!=null&&(jl(r,!1),Ml(r));else jl(this,e)}var Il=e=>{e.type==i.CHILD&&(e._$AP??=Fl,e._$AQ??=Pl)},Ll=class extends e{constructor(){super(...arguments),this._$AN=void 0}_$AT(e,t,n){super._$AT(e,t,n),Nl(this),this.isConnected=e._$AU}_$AO(e,t=!0){e!==this.isConnected&&(this.isConnected=e,e?this.reconnected?.():this.disconnected?.()),t&&(jl(this,e),Ml(this))}setValue(e){if(Hi(this._$Ct))this._$Ct._$AI(e,this);else{let t=[...this._$Ct._$AH];t[this._$Ci]=e,this._$Ct._$AI(t,this,0)}}disconnected(){}reconnected(){}},Rl=class{constructor(e){this.G=e}disconnect(){this.G=void 0}reconnect(e){this.G=e}deref(){return this.G}},zl=class{constructor(){this.Y=void 0,this.Z=void 0}get(){return this.Y}pause(){this.Y??=new Promise((e=>this.Z=e))}resume(){this.Z?.(),this.Y=this.Z=void 0}},Bl=e=>!Bi(e)&&typeof e.then==`function`,Vl=1073741823,Hl=t(class extends Ll{constructor(){super(...arguments),this._$Cwt=Vl,this._$Cbt=[],this._$CK=new Rl(this),this._$CX=new zl}render(...e){return e.find((e=>!Bl(e)))??f}update(e,t){let n=this._$Cbt,r=n.length;this._$Cbt=t;let i=this._$CK,a=this._$CX;this.isConnected||this.disconnected();for(let e=0;e<t.length&&!(e>this._$Cwt);e++){let o=t[e];if(!Bl(o))return this._$Cwt=e,o;e<r&&o===n[e]||(this._$Cwt=Vl,r=0,Promise.resolve(o).then((async e=>{for(;a.get();)await a.get();let t=i.deref();if(t!==void 0){let n=t._$Cbt.indexOf(o);n>-1&&n<t._$Cwt&&(t._$Cwt=n,t.setValue(e))}})))}return f}disconnected(){this._$CK.disconnect(),this._$CX.pause()}reconnected(){this._$CK.reconnect(this),this._$CX.resume()}}),Ul=R(e=>class extends e{static get localizeNamespaces(){return[]}static get waitForLocalizeNamespaces(){return!0}constructor(){super(),this._localizeManager=Al(),this.__boundLocalizeOnLocaleChanged=(...e)=>{let t=Array.from(e)[0];this.__localizeOnLocaleChanged(t)},this.__boundLocalizeOnLocaleChanging=()=>{this.__localizeOnLocaleChanging()},this.__localizeStartLoadingNamespaces(),this.localizeNamespacesLoaded&&this.localizeNamespacesLoaded.then(()=>{this.__localizeMessageSync=!0})}async scheduleUpdate(){Object.getPrototypeOf(this).constructor.waitForLocalizeNamespaces&&await this.localizeNamespacesLoaded,super.scheduleUpdate()}connectedCallback(){super.connectedCallback(),this.localizeNamespacesLoaded&&this.localizeNamespacesLoaded.then(()=>this.onLocaleReady()),this._localizeManager.addEventListener(`__localeChanging`,this.__boundLocalizeOnLocaleChanging),this._localizeManager.addEventListener(`localeChanged`,this.__boundLocalizeOnLocaleChanged)}disconnectedCallback(){super.disconnectedCallback(),this._localizeManager.removeEventListener(`__localeChanging`,this.__boundLocalizeOnLocaleChanging),this._localizeManager.removeEventListener(`localeChanged`,this.__boundLocalizeOnLocaleChanged)}msgLit(e,t,n){return this.__localizeMessageSync?this._localizeManager.msg(e,t,n):this.localizeNamespacesLoaded?Hl(this.localizeNamespacesLoaded.then(()=>this._localizeManager.msg(e,t,n)),m):``}__getUniqueNamespaces(){let e=[],t=new Set;return Object.getPrototypeOf(this).constructor.localizeNamespaces.forEach(t.add.bind(t)),t.forEach(t=>{e.push(t)}),e}__localizeStartLoadingNamespaces(){this.localizeNamespacesLoaded=this._localizeManager.loadNamespaces(this.__getUniqueNamespaces())}__localizeOnLocaleChanging(){this.__localizeStartLoadingNamespaces()}__localizeOnLocaleChanged(e){this.onLocaleChanged(e.detail.newLocale,e.detail.oldLocale)}onLocaleReady(){this.onLocaleUpdated()}onLocaleChanged(e,t){this.onLocaleUpdated(),this.requestUpdate()}onLocaleUpdated(){}}),Wl=`3.0.0`,Gl=window.scopedElementsVersions||(window.scopedElementsVersions=[]);Gl.includes(Wl)||Gl.push(Wl);var Kl=R(e=>class extends e{static scopedElements;static get scopedElementsVersion(){return Wl}static __registry;get registry(){return this.constructor.__registry}set registry(e){this.constructor.__registry=e}attachShadow(e){let{scopedElements:t}=this.constructor;if(!this.registry||this.registry===this.constructor.__registry&&!Object.prototype.hasOwnProperty.call(this.constructor,`__registry`)){this.registry=new CustomElementRegistry;for(let[e,n]of Object.entries(t??{}))this.registry.define(e,n)}return super.attachShadow({...e,customElements:this.registry,registry:this.registry})}}),ql=R(e=>class extends Kl(e){createRenderRoot(){let{shadowRootOptions:e,elementStyles:t}=this.constructor,n=this.attachShadow(e);return this.renderOptions.creationScope=n,u(n,t),this.renderOptions.renderBefore??=n.firstChild,n}});function Jl(){return!!(globalThis.ShadowRoot?.prototype.createElement&&globalThis.ShadowRoot?.prototype.importNode)}var Yl=R(e=>class extends ql(e){constructor(){super()}createScopedElement(e){return(Jl()?this.shadowRoot:document).createElement(e)}defineScopedElement(e,t){let n=this.registry.get(e),r=n&&n!==t;return!Jl()&&r&&console.error([`You are trying to re-register the "${e}" custom element with a different class via ScopedElementsMixin.`,`This is only possible with a CustomElementRegistry.`,`Your browser does not support this feature so you will need to load a polyfill for it.`,`Load "@webcomponents/scoped-custom-element-registry" before you register ANY web component to the global customElements registry.`,`e.g. add "<script src="/node_modules/@webcomponents/scoped-custom-element-registry/scoped-custom-element-registry.min.js"><\/script>" as your first script tag.`,`For more details you can visit https://open-wc.org/docs/development/scoped-elements/`].join(`
`)),n?this.registry.get(e):this.registry.define(e,t)}attachShadow(e){let{scopedElements:t}=this.constructor;if(!this.registry||this.registry===this.constructor.__registry&&!Object.prototype.hasOwnProperty.call(this.constructor,`__registry`)){this.registry=Jl()?new CustomElementRegistry:customElements;for(let[e,n]of Object.entries(t??{}))this.defineScopedElement(e,n)}return Element.prototype.attachShadow.call(this,{...e,customElements:this.registry,registry:this.registry})}createRenderRoot(){let{shadowRootOptions:e,elementStyles:t}=this.constructor,n=this.attachShadow(e);return Jl()&&(this.renderOptions.creationScope=n),n instanceof ShadowRoot&&(u(n,t),this.renderOptions.renderBefore=this.renderOptions.renderBefore||n.firstChild),n}}),Xl=[Node.DOCUMENT_POSITION_PRECEDING,Node.DOCUMENT_POSITION_CONTAINS,Node.DOCUMENT_POSITION_CONTAINS|Node.DOCUMENT_POSITION_PRECEDING];function Zl(e,{reverse:t}={}){let n=(e,t)=>{let n=e.compareDocumentPosition(t);return Xl.includes(n)?1:-1},r=e.filter(e=>e);return r.sort(n),t&&r.reverse(),r}var Ql=class{constructor(e){this.type=`unparseable`,this.viewValue=e}toString(){return JSON.stringify({type:this.type,viewValue:this.viewValue})}},$l=R(e=>class extends e{constructor(){super(),this.name=``,this._parentFormGroup=void 0,this.allowCrossRootRegistration=!1}get name(){return this.__name||``}set name(e){let t=this.name;this.__name=e.toString(),this.requestUpdate(`name`,t)}static get properties(){return{name:{type:String,reflect:!0},allowCrossRootRegistration:{type:Boolean,attribute:`allow-cross-root-registration`}}}connectedCallback(){super.connectedCallback(),this.dispatchEvent(new CustomEvent(`form-element-register`,{detail:{element:this},bubbles:!0,composed:!!this.allowCrossRootRegistration}))}disconnectedCallback(){super.disconnectedCallback(),this.__unregisterFormElement()}__unregisterFormElement(){this._parentFormGroup&&this._parentFormGroup.removeFormElement(this)}}),eu=R(e=>class extends $l(_s(xs(e))){static get properties(){return{readOnly:{type:Boolean,attribute:`readonly`,reflect:!0},label:String,labelSrOnly:{type:Boolean,attribute:`label-sr-only`,reflect:!0},helpText:{type:String,attribute:`help-text`},modelValue:{attribute:!1},_ariaLabelledNodes:{attribute:!1},_ariaDescribedNodes:{attribute:!1},_repropagationRole:{attribute:!1},_isRepropagationEndpoint:{attribute:!1}}}get label(){return this.__label??(this._labelNode?.textContent||``)}set label(e){let t=this.label;this.__label=e,this.requestUpdate(`label`,t)}get helpText(){return this.__helpText??(this._helpTextNode?.textContent||``)}set helpText(e){let t=this.helpText;this.__helpText=e,this.requestUpdate(`helpText`,t)}get fieldName(){return this.__fieldName||this.label||this.name||``}set fieldName(e){this.__fieldName=e}get slots(){return{...super.slots,label:()=>{let e=document.createElement(`label`);return e.textContent=this.label,e},"help-text":()=>{let e=document.createElement(`div`);return e.textContent=this.helpText,e}}}get _inputNode(){return this.__getDirectSlotChild(`input`)}get _labelNode(){return this.__getDirectSlotChild(`label`)}get _helpTextNode(){return this.__getDirectSlotChild(`help-text`)}get _feedbackNode(){return this.__getDirectSlotChild(`feedback`)}static enabledWarnings=super.enabledWarnings?.filter(e=>e!==`change-in-update`)||[];constructor(){super(),this.readOnly=!1,this.labelSrOnly=!1,this._inputId=ws(this.localName),this._ariaLabelledNodes=[],this._ariaDescribedNodes=[],this._repropagationRole=`child`,this._isRepropagationEndpoint=!1,this.addEventListener(`model-value-changed`,this.__repropagateChildrenValues),this._onLabelClick=this._onLabelClick.bind(this)}connectedCallback(){super.connectedCallback(),this._enhanceLightDomClasses(),this._enhanceLightDomA11y(),this._triggerInitialModelValueChangedEvent(),this._labelNode&&this._labelNode.addEventListener(`click`,this._onLabelClick)}disconnectedCallback(){super.disconnectedCallback(),this._labelNode&&this._labelNode.removeEventListener(`click`,this._onLabelClick)}updated(e){super.updated(e),e.has(`disabled`)&&this._inputNode?.setAttribute(`aria-disabled`,`${!!this.disabled}`),e.has(`_ariaLabelledNodes`)&&this.__reflectAriaAttr(`aria-labelledby`,this._ariaLabelledNodes,this.__reorderAriaLabelledNodes),e.has(`_ariaDescribedNodes`)&&this.__reflectAriaAttr(`aria-describedby`,this._ariaDescribedNodes,this.__reorderAriaDescribedNodes),e.has(`label`)&&this.__label!==void 0&&this._labelNode&&(this._labelNode.textContent=this.label),e.has(`helpText`)&&this.__helpText!==void 0&&this._helpTextNode&&(this._helpTextNode.textContent=this.helpText),e.has(`name`)&&this.dispatchEvent(new CustomEvent(`form-element-name-changed`,{detail:{oldName:e.get(`name`),newName:this.name},bubbles:!0}))}_triggerInitialModelValueChangedEvent(){this._dispatchInitialModelValueChangedEvent()}_enhanceLightDomClasses(){this._inputNode&&this._inputNode.classList.add(`form-control`)}_enhanceLightDomA11y(){let{_inputNode:e,_labelNode:t,_helpTextNode:n,_feedbackNode:r}=this;e&&(e.id=e.id||this._inputId),t&&(t.setAttribute(`for`,this._inputId),this.addToAriaLabelledBy(t,{idPrefix:`label`})),n&&this.addToAriaDescribedBy(n,{idPrefix:`help-text`}),r&&(this.addEventListener(`focusin`,()=>{r.setAttribute(`aria-live`,`polite`)}),this.addEventListener(`focusout`,()=>{r.setAttribute(`aria-live`,`assertive`)}),this.addToAriaDescribedBy(r,{idPrefix:`feedback`})),this._enhanceLightDomA11yForAdditionalSlots()}_enhanceLightDomA11yForAdditionalSlots(e=[`prefix`,`suffix`,`before`,`after`]){e.forEach(e=>{let t=this.__getDirectSlotChild(e);t&&(t.hasAttribute(`data-label`)&&this.addToAriaLabelledBy(t,{idPrefix:e}),t.hasAttribute(`data-description`)&&this.addToAriaDescribedBy(t,{idPrefix:e}))})}__reflectAriaAttr(e,t,n){if(this._inputNode){if(n){let e=t.filter(e=>this.contains(e)),n=t.filter(e=>!this.contains(e)),r=[...Zl(e.map(e=>e.assignedSlot||e))],i=[];r.forEach(t=>{e.forEach(e=>{t.name===e.slot&&i.push(e)})}),t=[...i,...n]}let r=t.map(e=>e.id).join(` `);this._inputNode.setAttribute(e,r)}}render(){return l`
        <div class="form-field__group-one">${this._groupOneTemplate()}</div>
        <div class="form-field__group-two">${this._groupTwoTemplate()}</div>
      `}_groupOneTemplate(){return l` ${this._labelTemplate()} ${this._helpTextTemplate()} `}_groupTwoTemplate(){return l` ${this._inputGroupTemplate()} ${this._feedbackTemplate()} `}_labelTemplate(){return l`
        <div class="form-field__label">
          <slot name="label"></slot>
        </div>
      `}_helpTextTemplate(){return l`
        <small class="form-field__help-text">
          <slot name="help-text"></slot>
        </small>
      `}_inputGroupTemplate(){return l`
        <div class="input-group">
          ${this._inputGroupBeforeTemplate()}
          <div class="input-group__container">
            ${this._inputGroupPrefixTemplate()} ${this._inputGroupInputTemplate()}
            ${this._inputGroupSuffixTemplate()}
          </div>
          ${this._inputGroupAfterTemplate()}
        </div>
      `}_inputGroupBeforeTemplate(){return l`
        <div class="input-group__before">
          <slot name="before"></slot>
        </div>
      `}_inputGroupPrefixTemplate(){return Array.from(this.children).find(e=>e.slot===`prefix`)?l`
            <div class="input-group__prefix">
              <slot name="prefix"></slot>
            </div>
          `:m}_inputGroupInputTemplate(){return l`
        <div class="input-group__input">
          <slot name="input"></slot>
        </div>
      `}_inputGroupSuffixTemplate(){return Array.from(this.children).find(e=>e.slot===`suffix`)?l`
            <div class="input-group__suffix">
              <slot name="suffix"></slot>
            </div>
          `:m}_inputGroupAfterTemplate(){return l`
        <div class="input-group__after">
          <slot name="after"></slot>
        </div>
      `}_feedbackTemplate(){return l`
        <div class="form-field__feedback">
          <slot name="feedback"></slot>
        </div>
      `}_isEmpty(e=this.modelValue){let t=e;return this.modelValue instanceof Ql&&(t=this.modelValue.viewValue),typeof t==`object`&&t&&!(t instanceof Date)?!Object.keys(t).length:!t&&!(typeof t==`number`&&(t===0||Number.isNaN(t)))&&!(typeof t==`boolean`&&t===!1)}static get styles(){return[d`
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
        `]}_getAriaDescriptionElements(){return[this._helpTextNode,this._feedbackNode]}addToAriaLabelledBy(e,{idPrefix:t=``,reorder:n=!0}={}){e.id=e.id||`${t}-${this._inputId}`,this._ariaLabelledNodes.includes(e)||(this._ariaLabelledNodes=[...this._ariaLabelledNodes,e],this.__reorderAriaLabelledNodes=!!n)}removeFromAriaLabelledBy(e){this._ariaLabelledNodes.includes(e)&&(this._ariaLabelledNodes.splice(this._ariaLabelledNodes.indexOf(e),1),this._ariaLabelledNodes=[...this._ariaLabelledNodes],this.__reorderAriaLabelledNodes=!1)}addToAriaDescribedBy(e,{idPrefix:t=``,reorder:n=!0}={}){e.id=e.id||`${t}-${this._inputId}`,this._ariaDescribedNodes.includes(e)||(this._ariaDescribedNodes=[...this._ariaDescribedNodes,e],this.__reorderAriaDescribedNodes=!!n)}removeFromAriaDescribedBy(e){this._ariaDescribedNodes.includes(e)&&(this._ariaDescribedNodes.splice(this._ariaDescribedNodes.indexOf(e),1),this._ariaDescribedNodes=[...this._ariaDescribedNodes],this.__reorderAriaLabelledNodes=!1)}__getDirectSlotChild(e){return Array.from(this.children).find(t=>t.slot===e)}_dispatchInitialModelValueChangedEvent(){this._repropagationRole!==`child`&&(this.__repropagateChildrenInitialized=!0,this.dispatchEvent(new CustomEvent(`model-value-changed`,{bubbles:!0,detail:{formPath:[this],initialize:!0,isTriggeredByUser:!1}})))}_onBeforeRepropagateChildrenValues(e){}__repropagateChildrenValues(e){this._onBeforeRepropagateChildrenValues(e);let t=e.detail&&e.detail.element||e.target,n=this._isRepropagationEndpoint||this._repropagationRole===`choice-group`;if(t===this)return;e.stopImmediatePropagation();let r=this._repropagationRole!==`child`&&!this.__repropagateChildrenInitialized,i=e.detail&&e.detail.initialize;if(r||i||!this._repropagationCondition(t))return;let a=[];n||(a=e.detail&&e.detail.formPath||[t]);let o=[...a,this];this.dispatchEvent(new CustomEvent(`model-value-changed`,{bubbles:!0,detail:{formPath:o,isTriggeredByUser:!!e.detail?.isTriggeredByUser}}))}_repropagationCondition(e){return!!e}_onLabelClick(){}}),tu=class{constructor(){this.__running=!1,this.__queue=[]}add(e){this.__queue.push(e),this.__running||(this.complete=new Promise(e=>{this.__callComplete=e}),this.__run())}async __run(){this.__running=!0,await this.__queue[0](),this.__queue.shift(),this.__queue.length>0?this.__run():(this.__running=!1,this.__callComplete&&this.__callComplete())}};function nu(e){return e.charAt(0).toUpperCase()+e.slice(1)}var ru=R(e=>class extends e{constructor(){super(),this.__SyncUpdatableNamespace={}}firstUpdated(e){super.firstUpdated(e),this.__syncUpdatableInitialize()}connectedCallback(){super.connectedCallback(),this.__SyncUpdatableNamespace.connected=!0}disconnectedCallback(){super.disconnectedCallback(),this.__SyncUpdatableNamespace.connected=!1}static enabledWarnings=super.enabledWarnings?.filter(e=>e!==`change-in-update`)||[];static __syncUpdatableHasChanged(e,t,n){let r=this.elementProperties;return r.get(e)&&r.get(e).hasChanged?r.get(e).hasChanged(t,n):t!==n}__syncUpdatableInitialize(){let e=this.__SyncUpdatableNamespace,t=this.constructor;e.initialized=!0,e.queue&&Array.from(e.queue).forEach(e=>{t.__syncUpdatableHasChanged(e,this[e],void 0)&&this.updateSync(e,void 0)})}requestUpdate(e,t,n){if(super.requestUpdate(e,t,n),e===void 0)return;this.__SyncUpdatableNamespace=this.__SyncUpdatableNamespace||{};let r=this.__SyncUpdatableNamespace,i=this.constructor;r.initialized?i.__syncUpdatableHasChanged(e,this[e],t)&&this.updateSync(e,t):(r.queue=r.queue||new Set,r.queue.add(e))}updateSync(e,t){}}),iu=e=>{switch(e){case`bg-BG`:return s(()=>import(`./bg-BG-CUtyclK6.js`),__vite__mapDeps([0,1]),import.meta.url);case`bg`:return s(()=>import(`./bg-DLFHZPGS.js`),__vite__mapDeps([2,1]),import.meta.url);case`cs-CZ`:return s(()=>import(`./cs-CZ-BPXuRBmR.js`),__vite__mapDeps([3,4]),import.meta.url);case`cs`:return s(()=>import(`./cs-B0MDOmrH.js`),__vite__mapDeps([5,4]),import.meta.url);case`de-DE`:return s(()=>import(`./de-DE-f3atla9t.js`),__vite__mapDeps([6,7]),import.meta.url);case`de`:return s(()=>import(`./de-BOjktHME.js`),__vite__mapDeps([8,7]),import.meta.url);case`en-AU`:return s(()=>import(`./en-AU-CqsU-pto.js`),__vite__mapDeps([9,10]),import.meta.url);case`en-GB`:return s(()=>import(`./en-GB-B_evmll_.js`),__vite__mapDeps([11,10]),import.meta.url);case`en-US`:return s(()=>import(`./en-US-DWNMkAIW.js`),__vite__mapDeps([12,10]),import.meta.url);case`en-PH`:case`en`:return s(()=>import(`./en-WrhTbaWQ.js`),__vite__mapDeps([13,10]),import.meta.url);case`es-ES`:return s(()=>import(`./es-ES-uk_vgP_u.js`),__vite__mapDeps([14,15]),import.meta.url);case`es`:return s(()=>import(`./es-CRrZWrYq.js`),__vite__mapDeps([16,15]),import.meta.url);case`fr-FR`:return s(()=>import(`./fr-FR-BFuXT0QG.js`),__vite__mapDeps([17,18]),import.meta.url);case`fr-BE`:return s(()=>import(`./fr-BE-iinjvp9X.js`),__vite__mapDeps([19,18]),import.meta.url);case`fr`:return s(()=>import(`./fr-DBZDLfez.js`),__vite__mapDeps([20,18]),import.meta.url);case`hu-HU`:return s(()=>import(`./hu-HU-DjIBc18o.js`),__vite__mapDeps([21,22]),import.meta.url);case`hu`:return s(()=>import(`./hu-CCfsXRXO.js`),__vite__mapDeps([23,22]),import.meta.url);case`it-IT`:return s(()=>import(`./it-IT-C9isceCq.js`),__vite__mapDeps([24,25]),import.meta.url);case`it`:return s(()=>import(`./it-D5-Wj26w.js`),__vite__mapDeps([26,25]),import.meta.url);case`nl-BE`:return s(()=>import(`./nl-BE-fuc5y42r.js`),__vite__mapDeps([27,28]),import.meta.url);case`nl-NL`:return s(()=>import(`./nl-NL-BHde5wmq.js`),__vite__mapDeps([29,28]),import.meta.url);case`nl`:return s(()=>import(`./nl-AuR6boED.js`),__vite__mapDeps([30,28]),import.meta.url);case`pl-PL`:return s(()=>import(`./pl-PL-SKj89Ze8.js`),__vite__mapDeps([31,32]),import.meta.url);case`pl`:return s(()=>import(`./pl-BvPycIUK.js`),__vite__mapDeps([33,32]),import.meta.url);case`ro-RO`:return s(()=>import(`./ro-RO-BBPvRREA.js`),__vite__mapDeps([34,35]),import.meta.url);case`ro`:return s(()=>import(`./ro-DHvdrytz.js`),__vite__mapDeps([36,35]),import.meta.url);case`ru-RU`:return s(()=>import(`./ru-RU-Cqbzf1DP.js`),__vite__mapDeps([37,38]),import.meta.url);case`ru`:return s(()=>import(`./ru-DHPAL3Qd.js`),__vite__mapDeps([39,38]),import.meta.url);case`sk-SK`:return s(()=>import(`./sk-SK-CVB1omfc.js`),__vite__mapDeps([40,41]),import.meta.url);case`sk`:return s(()=>import(`./sk-CqwbQKQ8.js`),__vite__mapDeps([42,41]),import.meta.url);case`tr-TR`:return s(()=>import(`./tr-TR-YY3D0g62.js`),__vite__mapDeps([43,44]),import.meta.url);case`tr`:return s(()=>import(`./tr-BM9B7JDW.js`),__vite__mapDeps([45,44]),import.meta.url);case`uk-UA`:return s(()=>import(`./uk-UA-A4zQpn9J.js`),__vite__mapDeps([46,47]),import.meta.url);case`uk`:return s(()=>import(`./uk-Chd3A0Z6.js`),__vite__mapDeps([48,47]),import.meta.url);case`zh-CN`:case`zh`:return s(()=>import(`./zh-D9yl6Nyt.js`),[],import.meta.url);default:return s(()=>import(`./en-WrhTbaWQ.js`),__vite__mapDeps([13,10]),import.meta.url)}},au=e=>`${e[0].toUpperCase()}${e.slice(1)}`,ou=class extends Ul(h){static get properties(){return{feedbackData:{attribute:!1}}}static localizeNamespaces=[{"lion-form-core":iu},...super.localizeNamespaces];static get styles(){return[d`
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
      `]}constructor(){super(),this.feedbackData=void 0}_messageTemplate({message:e}){return e}updated(e){super.updated(e),this.feedbackData&&this.feedbackData[0]?(this.setAttribute(`type`,this.feedbackData[0].type),this.currentType=this.feedbackData[0].type):this.currentType!==`success`&&this.removeAttribute(`type`)}render(){return l`
      ${this.feedbackData&&this.feedbackData.map(({message:e,type:t,validator:n})=>l`
          <span class="validation-feedback__type">
            ${e&&t?this._localizeManager.msg(`lion-form-core:validation${au(t)}`):m}
          </span>
          ${this._messageTemplate({message:e,type:t,validator:n})}
        `)}
    `}},su=class extends EventTarget{constructor(e,t){super(),this.__param=e,this.__config=t||{},this.type=t?.type||`error`}static _$isValidator$=!0;static validatorName=``;static async=!1;execute(e,t,n){if(!this.constructor.validatorName)throw Error(`A validator needs to have a name! Please set it via "static get validatorName() { return 'IsCat'; }"`);return!0}set param(e){this.__param=e,this.dispatchEvent(new Event(`param-changed`))}get param(){return this.__param}set config(e){this.__config=e,this.dispatchEvent(new Event(`config-changed`))}get config(){return this.__config}async _getMessage(e){let t=this.constructor,n={name:t.validatorName,type:this.type,params:this.param,config:this.config,...e};if(this.config.getMessage){if(typeof this.config.getMessage==`function`)return this.config.getMessage(n);throw Error(`You must provide a value for getMessage of type 'function', you provided a value of type: ${typeof this.config.getMessage}`)}return t.getMessage(n)}static async getMessage(e){return`Please configure an error message for "${this.name}" by overriding "static async getMessage()"`}onFormControlConnect(e){}onFormControlDisconnect(e){}abortExecution(){}};function cu(e=[],t=[]){return e.filter(e=>!t.includes(e)).concat(t.filter(t=>!e.includes(t)))}function lu(e){return e instanceof Ql?e.viewValue:e}var uu=R(e=>class extends eu(ru(_s(xs(Yl(e))))){static get scopedElements(){return{...super.scopedElements,"lion-validation-feedback":ou}}static get properties(){return{validators:{attribute:!1},hasFeedbackFor:{attribute:!1},shouldShowFeedbackFor:{attribute:!1},showsFeedbackFor:{type:Array,attribute:`shows-feedback-for`,reflect:!0,converter:{fromAttribute:e=>e.split(`,`),toAttribute:e=>e.join(`,`)}},validationStates:{attribute:!1},isPending:{type:Boolean,attribute:`is-pending`,reflect:!0},defaultValidators:{attribute:!1},_visibleMessagesAmount:{attribute:!1},__childModelValueChanged:{attribute:!1}}}static get validationTypes(){return[`error`]}get operationMode(){return`enter`}get slots(){return{...super.slots,feedback:()=>{let e=this.createScopedElement(`lion-validation-feedback`);return e.setAttribute(`data-tag-name`,`lion-validation-feedback`),e}}}get _allValidators(){return[...this.validators,...this.defaultValidators]}constructor(){super(),this.hasFeedbackFor=[],this.showsFeedbackFor=[],this.shouldShowFeedbackFor=[],this.validationStates={},this.isPending=!1,this.validators=[],this.defaultValidators=[],this._visibleMessagesAmount=1,this.__syncValidationResult=[],this.__asyncValidationResult=[],this.__validationResult=[],this.__prevValidationResult=[],this.__prevShownValidationResult=[],this.__childModelValueChanged=!1,this._onValidatorUpdated=this._onValidatorUpdated.bind(this),this._updateFeedbackComponent=this._updateFeedbackComponent.bind(this)}connectedCallback(){super.connectedCallback(),Al().addEventListener(`localeChanged`,this._updateFeedbackComponent)}disconnectedCallback(){super.disconnectedCallback(),Al().removeEventListener(`localeChanged`,this._updateFeedbackComponent)}firstUpdated(e){super.firstUpdated(e),this.__isValidateInitialized=!0,this.validate(),this._repropagationRole!==`child`&&this.addEventListener(`model-value-changed`,()=>{this.__childModelValueChanged=!0})}updateSync(e,t){if(super.updateSync(e,t),e===`validators`?(this.__setupValidators(),this.validate({clearCurrentResult:!0})):e===`modelValue`&&this.validate({clearCurrentResult:!0}),[`touched`,`dirty`,`prefilled`,`focused`,`submitted`,`hasFeedbackFor`,`filled`].includes(e)&&this._updateShouldShowFeedbackFor(),e===`showsFeedbackFor`){this._inputNode&&this._inputNode.setAttribute(`aria-invalid`,`${this._hasFeedbackVisibleFor(`error`)}`);let e=cu(this.showsFeedbackFor,t);e.length>0&&this.dispatchEvent(new Event(`showsFeedbackForChanged`,{bubbles:!0})),e.forEach(e=>{this.dispatchEvent(new Event(`showsFeedbackFor${nu(e)}Changed`,{bubbles:!0}))})}e===`shouldShowFeedbackFor`&&cu(this.shouldShowFeedbackFor,t).length>0&&this.dispatchEvent(new Event(`shouldShowFeedbackForChanged`,{bubbles:!0}))}async validate({clearCurrentResult:e=!1}={}){if(this.validateComplete=new Promise(e=>{this.__validateCompleteResolve=e}),this.disabled||this.readOnly){this.__clearValidationResults(),this.__finishValidationPass(),this._updateFeedbackComponent();return}this.__isValidateInitialized&&(this.__prevValidationResult=this.__validationResult,e&&this.__clearValidationResults(),await this.__executeValidators())}#e(e){let t=e;for(;t;){if(t.constructor.validatorName===`Required`)return!0;t=Object.getPrototypeOf(t)}return!1}async __executeValidators(){let e=lu(this.modelValue),t=this.__isEmpty(e);if(this.__syncValidationResult=[],t){let e=!this._isFormOrFieldset,t=this._allValidators.find(e=>e.constructor?.validatorName===`Required`);if(t&&(this.__syncValidationResult=[{validator:t,outcome:!0}]),e){this.__finishValidationPass({syncValidationResult:this.__syncValidationResult});return}}let n=[],r=[],i=[];for(let e of this._allValidators)e?.executeOnResults?n.push(e):this.#e(e)||(e.constructor.async?i.push(e):r.push(e));let a=!!i.length;this.__syncValidationResult=[...this.__syncValidationResult,...this.__executeSyncValidators(r,e)],this.__finishValidationPass({syncValidationResult:this.__syncValidationResult,metaValidators:n}),a?(this.isPending=!0,this.__asyncValidationResult=await this.__executeAsyncValidators(i,e),this.isPending=!1,this.__finishValidationPass({syncValidationResult:this.__syncValidationResult,asyncValidationResult:this.__asyncValidationResult,metaValidators:n}),this.__validateCompleteResolve?.(!0)):this.__validateCompleteResolve?.(!0)}__executeSyncValidators(e,t){return e.map(e=>({validator:e,outcome:e.execute(t,e.param,{node:this})})).filter(e=>!!e.outcome)}async __executeAsyncValidators(e,t){let n=e.map(e=>e.execute(t,e.param,{node:this})),r=await Promise.all(n);return r.map((t,n)=>({validator:e[n],outcome:r[n]})).filter(e=>!!e.outcome)}__executeMetaValidators(e,t){return t.length?this._isEmpty(this.modelValue)?(this.__prevShownValidationResult=[],[]):t.map(t=>({validator:t,outcome:t.executeOnResults({regularValidationResult:e.map(e=>e.validator),prevValidationResult:this.__prevValidationResult.map(e=>e.validator),prevShownValidationResult:this.__prevShownValidationResult.map(e=>e.validator)})})).filter(e=>!!e.outcome):[]}__finishValidationPass({syncValidationResult:e=[],asyncValidationResult:t=[],metaValidators:n=[]}={}){let r=[...e,...t],i=this.__executeMetaValidators(r,n);this.__validationResult=[...i,...r];let a=this.constructor.validationTypes.reduce((e,t)=>({...e,[t]:{}}),{});for(let{validator:e,outcome:t}of this.__validationResult){a[e.type]||(a[e.type]={});let n=e.constructor;a[e.type][n.validatorName]=t}this.validationStates=a,this.hasFeedbackFor=[...new Set(this.__validationResult.map(({validator:e})=>e.type))],this.dispatchEvent(new Event(`validate-performed`,{bubbles:!0}))}__clearValidationResults(){this.__syncValidationResult=[],this.__asyncValidationResult=[]}_onValidatorUpdated(e){(e.type===`param-changed`||e.type===`config-changed`)&&this.validate()}__setupValidators(){let e=[`param-changed`,`config-changed`];for(let t of this.__prevValidators||[]){for(let n of e)t.removeEventListener?.(n,this._onValidatorUpdated);t.onFormControlDisconnect(this)}for(let t of this._allValidators){if(t.constructor._$isValidator$===void 0){let e=`Validators array only accepts class instances of Validator. Type "${Array.isArray(t)?`array`:typeof t}" found. This may be caused by having multiple installations of "@lion/ui/form-core.js".`;throw console.error(e,this),Error(e)}let n=this.constructor,r=t.constructor;if(n.validationTypes.indexOf(t.type)===-1){let e=`This component does not support the validator type "${t.type}" used in "${r.validatorName}". You may change your validators type or add it to the components "static get validationTypes() {}".`;throw console.error(e,this),Error(e)}for(let n of e)t.addEventListener?.(n,e=>{this._onValidatorUpdated(e,{validator:t})});t.onFormControlConnect(this)}this.__prevValidators=this._allValidators}__isEmpty(e){return typeof this._isEmpty==`function`?this._isEmpty(e):this.modelValue===null||this.modelValue===void 0||this.modelValue===``}async __getFeedbackMessages(e){let t=await this.fieldName;return Promise.all(e.map(async({validator:e,outcome:n})=>(e.config.fieldName&&(t=await e.config.fieldName),{message:await e._getMessage({modelValue:this.modelValue,formControl:this,fieldName:t,outcome:n}),type:e.type,validator:e,visibilityDuration:e.config?.visibilityDuration||3e3})))}_updateFeedbackComponent(){window.clearTimeout(this.removeMessage);let{_feedbackNode:e}=this;e&&(this.__feedbackQueue||=new tu,this.showsFeedbackFor.length>0?this.__feedbackQueue.add(async()=>{let t=this._prioritizeAndFilterFeedback({validationResult:this.__validationResult.map(e=>e.validator)});this.__prioritizedResult=t.map(e=>this.__validationResult.find(t=>e===t.validator)).filter(Boolean),this.__prioritizedResult.length>0&&(this.__prevShownValidationResult=this.__prioritizedResult);let n=await this.__getFeedbackMessages(this.__prioritizedResult);e.feedbackData=n||[],n?.[0]&&n[0].type===`success`&&n[0].visibilityDuration!==1/0&&(this.removeMessage=window.setTimeout(()=>{e.removeAttribute(`type`),e.feedbackData=[]},n[0].visibilityDuration))}):this.__feedbackQueue.add(async()=>{e.feedbackData=[]}),this.feedbackComplete=this.__feedbackQueue.complete)}_showFeedbackConditionFor(e,t){return!0}get _feedbackConditionMeta(){return{modelValue:this.modelValue,el:this}}feedbackCondition(e,t=this._feedbackConditionMeta,n=this._showFeedbackConditionFor.bind(this)){return n(e,t)}_hasFeedbackVisibleFor(e){return this.hasFeedbackFor?.includes(e)&&this.shouldShowFeedbackFor?.includes(e)}updated(e){if(super.updated(e),e.has(`shouldShowFeedbackFor`)||e.has(`hasFeedbackFor`)){let e=this.constructor;this.showsFeedbackFor=e.validationTypes.map(e=>this._hasFeedbackVisibleFor(e)?e:void 0).filter(Boolean),this._updateFeedbackComponent()}if(e.has(`__childModelValueChanged`)&&this.__childModelValueChanged&&(this.validate({clearCurrentResult:!0}),this.__childModelValueChanged=!1),e.has(`validationStates`)){let t=e.get(`validationStates`);t&&Object.entries(this.validationStates).forEach(([e,n])=>{t[e]&&JSON.stringify(n)!==JSON.stringify(t[e])&&this.dispatchEvent(new CustomEvent(`${e}StateChanged`,{detail:n}))})}}_updateShouldShowFeedbackFor(){let e=this.constructor.validationTypes.map(e=>this.feedbackCondition(e,this._feedbackConditionMeta,this._showFeedbackConditionFor.bind(this))?e:void 0).filter(Boolean);JSON.stringify(this.shouldShowFeedbackFor)!==JSON.stringify(e)&&(this.shouldShowFeedbackFor=e)}_prioritizeAndFilterFeedback({validationResult:e}){let t=this.constructor.validationTypes;return e.filter(e=>this.feedbackCondition(e.type,this._feedbackConditionMeta,this._showFeedbackConditionFor.bind(this))).sort((e,n)=>t.indexOf(e.type)-t.indexOf(n.type)).slice(0,this._visibleMessagesAmount)}}),du=R(e=>class extends uu(eu(e)){static get properties(){return{formattedValue:{attribute:!1},serializedValue:{attribute:!1},formatOptions:{attribute:!1}}}#e={didFormatterOutputSyncToView:!1,didFormatterRun:!1};requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`modelValue`&&this.modelValue!==t&&this._onModelValueChanged({modelValue:this.modelValue},{modelValue:t}),e===`serializedValue`&&this.serializedValue!==t&&this._calculateValues({source:`serialized`}),e===`formattedValue`&&this.formattedValue!==t&&this._calculateValues({source:`formatted`})}get value(){return this._inputNode?.value||this.__value||``}set value(e){this._inputNode?(this._inputNode.value=e,this.__value=void 0):this.__value=e}preprocessor(e,t){}parser(e,t){return e}formatter(e,t){return e}serializer(e){return e===void 0?``:e}deserializer(e){return e===void 0?``:e}_calculateValues({source:e}={source:null}){this.__preventRecursiveTrigger||(this.__preventRecursiveTrigger=!0,e!==`model`&&(e===`serialized`?this.modelValue=this.deserializer(this.serializedValue):e===`formatted`&&(this.modelValue=this._callParser())),e!==`formatted`&&(this.formattedValue=this._callFormatter()),e!==`serialized`&&(this.serializedValue=this.serializer(this.modelValue)),this._reflectBackFormattedValueToUser(),this.__preventRecursiveTrigger=!1,this.__prevViewValue=this.value)}_callParser(e=this.formattedValue){if(e===``)return``;if(typeof e!=`string`)return;let t=this.parser(e,{...this.formatOptions,mode:this.#t(),viewValueStates:this.#n()});return t===void 0?new Ql(e):t}_callFormatter(){return this.#e.didFormatterRun=!1,this._isHandlingUserInput&&this.hasFeedbackFor?.includes(`error`)&&this._inputNode?this.value:this.modelValue instanceof Ql?this.modelValue.viewValue:(this.#e.didFormatterRun=!0,this.formatter(this.modelValue,{...this.formatOptions,mode:this.#t(),viewValueStates:this.#n()}))}_onModelValueChanged(...e){this._calculateValues({source:`model`}),this._dispatchModelValueChangedEvent(...e)}_dispatchModelValueChangedEvent(...e){this.dispatchEvent(new CustomEvent(`model-value-changed`,{bubbles:!0,detail:{formPath:[this],isTriggeredByUser:!!this._isHandlingUserInput}}))}_syncValueUpwards(){this.__isHandlingComposition||this.__handlePreprocessor();let e=this.formattedValue;this.modelValue=this._callParser(this.value),e===this.formattedValue&&this.__prevViewValue!==this.value&&this._calculateValues()}__handlePreprocessor(){let e=this.value.length;this._inputNode&&`selectionStart`in this._inputNode&&this._inputNode?.type!==`range`&&(e=this._inputNode.selectionStart);let t=this.preprocessor(this.value,{...this.formatOptions,currentCaretIndex:e,prevViewValue:this.__prevViewValue});if(t!==void 0){if(typeof t==`string`)this.value=t;else if(typeof t==`object`){let{viewValue:e,caretIndex:n}=t;this.value=e,n&&this._inputNode&&`selectionStart`in this._inputNode&&(this._inputNode.selectionStart=n,this._inputNode.selectionEnd=n)}}}_reflectBackFormattedValueToUser(){this._reflectBackOn()&&(this.value=this.formattedValue===void 0?``:this.formattedValue,this.#e.didFormatterOutputSyncToView=!!this.formattedValue&&this.#e.didFormatterRun)}_reflectBackOn(){return!this._isHandlingUserInput}_proxyInputEvent(){this.dispatchEvent(new Event(`user-input-changed`,{bubbles:!0}))}_onUserInputChanged(){this._isHandlingUserInput=!0,this._syncValueUpwards(),this._isHandlingUserInput=!1}__onCompositionEvent({type:e}){e===`compositionstart`?this.__isHandlingComposition=!0:e===`compositionend`&&(this.__isHandlingComposition=!1,this._syncValueUpwards())}constructor(){super(),this.formatOn=`change`,this.formatOptions={mode:`auto`},this.formattedValue=void 0,this.serializedValue=void 0,this._isPasting=!1,this._isHandlingUserInput=!1,this.__prevViewValue=``,this.__onCompositionEvent=this.__onCompositionEvent.bind(this),this.addEventListener(`user-input-changed`,this._onUserInputChanged),this.addEventListener(`paste`,this.__onPaste),this._reflectBackFormattedValueToUser=this._reflectBackFormattedValueToUser.bind(this),this._reflectBackFormattedValueDebounced=()=>{setTimeout(this._reflectBackFormattedValueToUser)}}__onPaste(){this._isPasting=!0,setTimeout(()=>{this._isPasting=!1})}connectedCallback(){super.connectedCallback(),this.modelValue===void 0&&this._syncValueUpwards(),this.__prevViewValue=this.value,this._reflectBackFormattedValueToUser(),this._inputNode&&(this._inputNode.addEventListener(this.formatOn,this._reflectBackFormattedValueDebounced),this._inputNode.addEventListener(`input`,this._proxyInputEvent),this._inputNode.addEventListener(`compositionstart`,this.__onCompositionEvent),this._inputNode.addEventListener(`compositionend`,this.__onCompositionEvent))}disconnectedCallback(){super.disconnectedCallback(),this._inputNode&&(this._inputNode.removeEventListener(`input`,this._proxyInputEvent),this._inputNode.removeEventListener(this.formatOn,this._reflectBackFormattedValueDebounced),this._inputNode.removeEventListener(`compositionstart`,this.__onCompositionEvent),this._inputNode.removeEventListener(`compositionend`,this.__onCompositionEvent))}#t(){return this._isPasting?`pasted`:this._isHandlingUserInput&&this.__prevViewValue?`user-edited`:`auto`}#n(){let e=[];return this.#e.didFormatterOutputSyncToView&&e.push(`formatted`),e}}),fu=R(e=>class extends eu(e){static get properties(){return{touched:{type:Boolean,reflect:!0},dirty:{type:Boolean,reflect:!0},filled:{type:Boolean,reflect:!0},prefilled:{attribute:!1},submitted:{attribute:!1}}}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`touched`&&this.touched!==t&&this._onTouchedChanged(),e===`modelValue`&&(this.filled=!this._isEmpty()),e===`dirty`&&this.dirty!==t&&this._onDirtyChanged()}constructor(){super(),this.touched=!1,this.dirty=!1,this.prefilled=!1,this.filled=!1,this._leaveEvent=`blur`,this._valueChangedEvent=`model-value-changed`,this._iStateOnLeave=this._iStateOnLeave.bind(this),this._iStateOnValueChange=this._iStateOnValueChange.bind(this)}connectedCallback(){super.connectedCallback(),this.addEventListener(this._leaveEvent,this._iStateOnLeave),this.addEventListener(this._valueChangedEvent,this._iStateOnValueChange),this.initInteractionState()}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener(this._leaveEvent,this._iStateOnLeave),this.removeEventListener(this._valueChangedEvent,this._iStateOnValueChange)}initInteractionState(){this.dirty=!1,this.prefilled=!this._isEmpty()}_iStateOnLeave(){this.touched=!0,this.prefilled=!this._isEmpty()}_iStateOnValueChange(){this.dirty=!0}resetInteractionState(){this.touched=!1,this.submitted=!1,this.dirty=!1,this.prefilled=!this._isEmpty()}_onTouchedChanged(){this.dispatchEvent(new Event(`touched-changed`,{bubbles:!0,composed:!0}))}_onDirtyChanged(){this.dispatchEvent(new Event(`dirty-changed`,{bubbles:!0,composed:!0}))}_showFeedbackConditionFor(e,t){return t.touched&&t.dirty||t.prefilled||t.submitted}get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,submitted:this.submitted,touched:this.touched,dirty:this.dirty,filled:this.filled,prefilled:this.prefilled}}}),pu=class extends eu(fu(al(du(uu(xs(h)))))){firstUpdated(e){super.firstUpdated(e),this._initialModelValue=this.modelValue}connectedCallback(){super.connectedCallback(),this._onChange=this._onChange.bind(this),this._inputNode.addEventListener(`change`,this._onChange),this.classList.add(`form-field`)}disconnectedCallback(){super.disconnectedCallback(),this._inputNode?.removeEventListener(`change`,this._onChange)}resetInteractionState(){super.resetInteractionState(),this.submitted=!1}reset(){this.modelValue=this._initialModelValue,this.resetInteractionState()}clear(){this.modelValue=``}_onChange(e){this.dispatchEvent(new Event(`user-input-changed`,{bubbles:!0}))}get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,focused:this.focused}}get _focusableNode(){return this._inputNode}},mu=class extends Array{_keys(){return Object.keys(this).filter(e=>Number.isNaN(Number(e)))}},hu=R(e=>class extends $l(e){static get properties(){return{_isFormOrFieldset:{type:Boolean}}}constructor(){super(),this.formElements=new mu,this._isFormOrFieldset=!1,this._onRequestToAddFormElement=this._onRequestToAddFormElement.bind(this),this._onRequestToChangeFormElementName=this._onRequestToChangeFormElementName.bind(this),this.addEventListener(`form-element-register`,this._onRequestToAddFormElement),this.addEventListener(`form-element-name-changed`,this._onRequestToChangeFormElementName),this.initComplete=new Promise((e,t)=>{this.__resolveInitComplete=e,this.__rejectInitComplete=t}),this.registrationComplete=new Promise((e,t)=>{this.__resolveRegistrationComplete=e,this.__rejectRegistrationComplete=t}),this.registrationComplete.done=!1,this.registrationComplete.then(()=>{this.registrationComplete.done=!0,this.__resolveInitComplete(void 0)},()=>{throw this.registrationComplete.done=!0,this.__rejectInitComplete(void 0),Error(`Registration could not finish. Please use await el.registrationComplete;`)})}connectedCallback(){super.connectedCallback(),this._completeRegistration()}_completeRegistration(){Promise.resolve().then(()=>this.__resolveRegistrationComplete(void 0))}disconnectedCallback(){super.disconnectedCallback(),this.registrationComplete.done===!1&&Promise.resolve().then(()=>{Promise.resolve().then(()=>{this.__rejectRegistrationComplete()})})}isRegisteredFormElement(e){return this.formElements.some(t=>t===e)}addFormElement(e,t){if(e._parentFormGroup=this,t>=0?this.formElements.splice(t,0,e):this.formElements.push(e),this._isFormOrFieldset){let{name:n}=e;if(n===this.name)throw console.info(`Error Node:`,e),TypeError(`You can not have the same name "${n}" as your parent`);if(n.substr(-2)===`[]`)Array.isArray(this.formElements[n])||(this.formElements[n]=new mu),t>0?this.formElements[n].splice(t,0,e):this.formElements[n].push(e);else if(!this.formElements[n])this.formElements[n]=e;else throw console.info(`Error Node:`,e),TypeError(`Name "${n}" is already registered - if you want an array add [] to the end`)}}removeFormElement(e){let t=this.formElements.indexOf(e);if(t>-1&&this.formElements.splice(t,1),this._isFormOrFieldset){let{name:t}=e;if(t.substr(-2)===`[]`&&this.formElements[t]){let n=this.formElements[t].indexOf(e);n>-1&&this.formElements[t].splice(n,1)}else this.formElements[t]&&delete this.formElements[t]}}_onRequestToAddFormElement(e){let t=e.detail.element;if(t===this||this.isRegisteredFormElement(t))return;e.stopPropagation();let n=-1;if(this.formElements&&Array.isArray(this.formElements)){for(let[e,r]of this.formElements.entries())if(!(r.compareDocumentPosition(t)&Node.DOCUMENT_POSITION_FOLLOWING)){n=e;break}}this.addFormElement(t,n)}_onRequestToChangeFormElementName(e){let t=this.formElements[e.detail.oldName];t&&(this.formElements[e.detail.newName]=t,delete this.formElements[e.detail.oldName])}_onRequestToRemoveFormElement(e){let t=e.detail.element;t!==this&&this.isRegisteredFormElement(t)&&(e.stopPropagation(),this.removeFormElement(t))}}),gu=R(e=>class extends e{constructor(){super(),this.registrationTarget=void 0,this.__redispatchEventForFormRegistrarPortalMixin=this.__redispatchEventForFormRegistrarPortalMixin.bind(this),this.addEventListener(`form-element-register`,this.__redispatchEventForFormRegistrarPortalMixin)}__redispatchEventForFormRegistrarPortalMixin(e){if(e.stopPropagation(),!this.registrationTarget)throw Error(`A FormRegistrarPortal element requires a .registrationTarget`);this.registrationTarget.dispatchEvent(new CustomEvent(`form-element-register`,{detail:{element:e.detail.element},bubbles:!0}))}}),_u=R(e=>class extends du(al(eu(e))){static get properties(){return{autocomplete:{type:String,reflect:!0}}}constructor(){super(),this.autocomplete=void 0}get _inputNode(){return super._inputNode}get selectionStart(){let e=this._inputNode;return e&&e.selectionStart?e.selectionStart:0}set selectionStart(e){let t=this._inputNode;t&&t.selectionStart&&(t.selectionStart=e)}get selectionEnd(){let e=this._inputNode;return e&&e.selectionEnd?e.selectionEnd:0}set selectionEnd(e){let t=this._inputNode;t&&t.selectionEnd&&(t.selectionEnd=e)}get value(){return this._inputNode&&this._inputNode.value||this.__value||``}set value(e){this._inputNode?(this._inputNode.value!==e&&this._setValueAndPreserveCaret(e),this.__value=void 0):this.__value=e}_setValueAndPreserveCaret(e){if(this.focused)try{if(!(this._inputNode instanceof HTMLSelectElement)){let t=this._inputNode.selectionStart;this._inputNode.value=e,this._inputNode.selectionStart=t,this._inputNode.selectionEnd=t}}catch{this._inputNode.value=e}else this._inputNode.value=e}_reflectBackFormattedValueToUser(){if(super._reflectBackFormattedValueToUser(),this._reflectBackOn()&&this.focused)try{this._inputNode.selectionStart=this._inputNode.value.length}catch{}}get _focusableNode(){return this._inputNode}}),vu=R(e=>class extends hu(uu(fu(e))){static get properties(){return{multipleChoice:{type:Boolean,attribute:`multiple-choice`}}}get modelValue(){let e=this._getCheckedElements();return this.multipleChoice?e.map(e=>e.choiceValue):e[0]?e[0].choiceValue:``}set modelValue(e){let t=(t,n)=>typeof t.choiceValue==`object`?JSON.stringify(t.choiceValue)===JSON.stringify(e):t.choiceValue===n;this.__isInitialModelValue?this.registrationComplete.then(()=>{this.__isInitialModelValue=!1,this._setCheckedElements(e,t),this.requestUpdate(`modelValue`,this._oldModelValue)}):(this._setCheckedElements(e,t),this.requestUpdate(`modelValue`,this._oldModelValue)),this._oldModelValue=this.modelValue}get serializedValue(){let e=this._getCheckedElements();return this.multipleChoice?e.map(e=>e.serializedValue.value):e[0]?e[0].serializedValue.value:``}set serializedValue(e){let t=(e,t)=>e.serializedValue.value===t;this.__isInitialSerializedValue?this.registrationComplete.then(()=>{this.__isInitialSerializedValue=!1,this._setCheckedElements(e,t),this.requestUpdate(`serializedValue`)}):(this._setCheckedElements(e,t),this.requestUpdate(`serializedValue`))}get formattedValue(){let e=this._getCheckedElements();return this.multipleChoice?e.map(e=>e.formattedValue):e[0]?e[0].formattedValue:``}set formattedValue(e){let t=(e,t)=>e.formattedValue===t;this.__isInitialFormattedValue?this.registrationComplete.then(()=>{this.__isInitialFormattedValue=!1,this._setCheckedElements(e,t)}):this._setCheckedElements(e,t)}get operationMode(){return this._repropagationRole===`choice-group`?`select`:`enter`}constructor(){super(),this.multipleChoice=!1,this._repropagationRole=`choice-group`,this.__isInitialModelValue=!0,this.__isInitialSerializedValue=!0,this.__isInitialFormattedValue=!0}connectedCallback(){super.connectedCallback(),this.registrationComplete.then(()=>{this.__isInitialModelValue=!1,this.__isInitialSerializedValue=!1,this.__isInitialFormattedValue=!1})}_completeRegistration(){Promise.resolve().then(()=>super._completeRegistration())}updated(e){super.updated(e),e.has(`name`)&&this.name!==e.get(`name`)&&this.formElements.forEach(e=>{e.name=this.name})}addFormElement(e,t){this._throwWhenInvalidChildModelValue(e),e.name=this.name,super.addFormElement(e,t)}clear(){this.multipleChoice?this.modelValue=[]:this.modelValue=``}_triggerInitialModelValueChangedEvent(){this.registrationComplete.then(()=>{this._dispatchInitialModelValueChangedEvent()})}_getFromAllFormElementsFilter(e,t){return!0}_getFromAllFormElements(e,t){let n=t||this._getFromAllFormElementsFilter;if(e===`modelValue`||e===`serializedValue`||e===`formattedValue`)return this[e];let r=this.formElements.filter(t=>n(t,e));return e===`_initialModelValue`?this.multipleChoice?r.filter(t=>t[e].checked).map(t=>t[e].value):r.find(t=>t[e].checked)?.value:r.map(t=>t[e])}_throwWhenInvalidChildModelValue(e){if(typeof e.modelValue.checked!=`boolean`||!Object.prototype.hasOwnProperty.call(e.modelValue,`value`))throw Error(`The ${this.tagName.toLowerCase()} name="${this.name}" does not allow to register ${e.tagName.toLowerCase()} with .modelValue="${e.modelValue}" - The modelValue should represent an Object { value: "foo", checked: false }`)}_isEmpty(){return this.multipleChoice?this.modelValue.length===0:typeof this.modelValue==`string`&&this.modelValue===``||this.modelValue===void 0||this.modelValue===null}_checkSingleChoiceElements(e){let{target:t}=e;if(t.checked===!1)return;let n=t.name;this.formElements.filter(e=>e.name===n).forEach(e=>{e!==t&&(e.checked=!1)})}_getCheckedElements(){return this.formElements.filter(e=>e.checked&&!e.disabled)}_setCheckedElements(e,t){if(e==null){this.formElements.forEach(e=>e.checked=!1);return}for(let n=0;n<this.formElements.length;n+=1)if(this.multipleChoice){let t=e.includes(this.formElements[n].modelValue.value);typeof this.formElements[n].modelValue.value==`object`&&(t=e.map(e=>JSON.stringify(e)).includes(JSON.stringify(this.formElements[n].modelValue.value))),this.formElements[n].checked=t}else t(this.formElements[n],e)?this.formElements[n].checked=!0:this.formElements[n].checked=!1}__setChoiceGroupTouched(){let e=this.modelValue;e!=null&&e!==this.__previousCheckedValue&&(this.touched=!0,this.__previousCheckedValue=e)}_onBeforeRepropagateChildrenValues(e){let t=e.detail&&e.detail.element||e.target;this.multipleChoice||!t.checked||(this.formElements.forEach(e=>{t.choiceValue!==e.choiceValue&&(e.checked=!1)}),this.__setChoiceGroupTouched(),this.requestUpdate(`modelValue`,this._oldModelValue),this._oldModelValue=this.modelValue)}_repropagationCondition(e){return!(this._repropagationRole===`choice-group`&&!this.multipleChoice&&!e.checked)}}),yu=(e,t={})=>e.value!==t.value||e.checked!==t.checked,bu=R(e=>class extends du(e){static get properties(){return{checked:{type:Boolean,reflect:!0},disabled:{type:Boolean,reflect:!0},modelValue:{type:Object,hasChanged:yu},choiceValue:{type:Object}}}get choiceValue(){return this.modelValue.value}set choiceValue(e){this.requestUpdate(`choiceValue`,this.choiceValue),this.modelValue.value!==e&&(this.modelValue={value:e,checked:this.modelValue.checked})}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`modelValue`?this.modelValue.checked!==this.checked&&this.__syncModelCheckedToChecked(this.modelValue.checked):e===`checked`&&this.modelValue.checked!==this.checked&&this.__syncCheckedToModel(this.checked)}firstUpdated(e){super.firstUpdated(e),e.has(`checked`)&&this.__syncCheckedToInputElement()}updated(e){super.updated(e),e.has(`modelValue`)&&this.__syncCheckedToInputElement(),e.has(`name`)&&this._parentFormGroup&&this._parentFormGroup.name!==this.name&&this._syncNameToParentFormGroup()}constructor(){super(),this.modelValue={value:``,checked:!1},this.disabled=!1,this._preventDuplicateLabelClick=this._preventDuplicateLabelClick.bind(this),this._toggleChecked=this._toggleChecked.bind(this)}static get styles(){return[...super.styles||[],d`
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
        `]}render(){return l`
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
      `}_choiceGraphicTemplate(){return m}_afterTemplate(){return m}connectedCallback(){super.connectedCallback(),this._labelNode&&this._labelNode.addEventListener(`click`,this._preventDuplicateLabelClick),this.addEventListener(`user-input-changed`,this._toggleChecked)}disconnectedCallback(){super.disconnectedCallback(),this._labelNode&&this._labelNode.removeEventListener(`click`,this._preventDuplicateLabelClick),this.removeEventListener(`user-input-changed`,this._toggleChecked)}_preventDuplicateLabelClick(e){let t=e=>{e.stopImmediatePropagation(),this._inputNode.removeEventListener(`click`,t)};this._inputNode.addEventListener(`click`,t)}_toggleChecked(e){this.disabled||(this._isHandlingUserInput=!0,this.checked=!this.checked,this._isHandlingUserInput=!1)}_syncNameToParentFormGroup(){this._parentFormGroup.tagName.includes(this.tagName)&&(this.name=this._parentFormGroup?.name||``)}__syncModelCheckedToChecked(e){this.checked=e}__syncCheckedToModel(e){this.modelValue={value:this.choiceValue,checked:e}}__syncCheckedToInputElement(){this._inputNode&&(this._inputNode.checked=this.checked)}_proxyInputEvent(){}_onModelValueChanged({modelValue:e},t){let n;t&&t.modelValue&&(n=t.modelValue),this.constructor.elementProperties.get(`modelValue`).hasChanged(e,n)&&super._onModelValueChanged({modelValue:e})}parser(){return this.modelValue}formatter(e){return e&&e.value!==void 0?e.value:e}clear(){this.checked=!1}_isEmpty(){return!this.checked}_syncValueUpwards(){}}),xu=class extends su{static get validatorName(){return`FormElementsHaveNoError`}execute(e,t,n){return n?.node._anyFormElementHasFeedbackFor(`error`)}static async getMessage(){return``}},Su=R(e=>class extends hu(eu(uu(_s(xs(e))))){static get properties(){return{submitted:{type:Boolean,reflect:!0},focused:{type:Boolean,reflect:!0},dirty:{type:Boolean,reflect:!0},touched:{type:Boolean,reflect:!0},prefilled:{type:Boolean,reflect:!0}}}get _inputNode(){return this}get modelValue(){return this._getFromAllFormElements(`modelValue`)}set modelValue(e){this.__isInitialModelValue?(this.__isInitialModelValue=!1,this.registrationComplete.then(()=>{this._setValueMapForAllFormElements(`modelValue`,e)})):this._setValueMapForAllFormElements(`modelValue`,e)}get serializedValue(){return this._getFromAllFormElements(`serializedValue`)}set serializedValue(e){this.__isInitialSerializedValue?(this.__isInitialSerializedValue=!1,this.registrationComplete.then(()=>{this._setValueMapForAllFormElements(`serializedValue`,e)})):this._setValueMapForAllFormElements(`serializedValue`,e)}get formattedValue(){return this._getFromAllFormElements(`formattedValue`)}set formattedValue(e){this._setValueMapForAllFormElements(`formattedValue`,e)}get prefilled(){return this._everyFormElementHas(`prefilled`)}constructor(){super(),this.value=``,this.disabled=!1,this.submitted=!1,this.dirty=!1,this.touched=!1,this.focused=!1,this.__addedSubValidators=!1,this.__isInitialModelValue=!0,this.__isInitialSerializedValue=!0,this._checkForOutsideClick=this._checkForOutsideClick.bind(this),this.addEventListener(`focusin`,this._syncFocused),this.addEventListener(`focusout`,this._onFocusOut),this.addEventListener(`dirty-changed`,this._syncDirty),this.addEventListener(`validate-performed`,this.__onChildValidatePerformed),this.defaultValidators=[new xu],this.__descriptionElementsInParentChain=new Set,this.__pendingValues={modelValue:{},serializedValue:{}}}connectedCallback(){super.connectedCallback(),this.setAttribute(`role`,`group`),this.initComplete.then(()=>{this.__isInitialModelValue=!1,this.__isInitialSerializedValue=!1,this.__initInteractionStates()})}disconnectedCallback(){super.disconnectedCallback(),this.__hasActiveOutsideClickHandling&&=(document.removeEventListener(`click`,this._checkForOutsideClick),!1),this.__descriptionElementsInParentChain.clear()}__initInteractionStates(){this.formElements.forEach(e=>{typeof e.initInteractionState==`function`&&e.initInteractionState()})}_triggerInitialModelValueChangedEvent(){this.registrationComplete.then(()=>{this._dispatchInitialModelValueChangedEvent()})}updated(e){super.updated(e),e.has(`disabled`)&&(this.disabled?this.__requestChildrenToBeDisabled():this.__retractRequestChildrenToBeDisabled()),e.has(`focused`)&&this.focused===!0&&this.__setupOutsideClickHandling()}__setupOutsideClickHandling(){this.__hasActiveOutsideClickHandling||=(document.addEventListener(`click`,this._checkForOutsideClick),!0)}_checkForOutsideClick(e){this.contains(e.target)||(this.touched=!0)}__requestChildrenToBeDisabled(){this.formElements.forEach(e=>{e.makeRequestToBeDisabled&&e.makeRequestToBeDisabled()})}__retractRequestChildrenToBeDisabled(){this.formElements.forEach(e=>{e.retractRequestToBeDisabled&&e.retractRequestToBeDisabled()})}_inputGroupTemplate(){return l`
        <div class="input-group">
          <slot></slot>
        </div>
      `}submitGroup(){this.submitted=!0,this.formElements.forEach(e=>{typeof e.submitGroup==`function`?e.submitGroup():e.submitted=!0})}resetGroup(){this.formElements.forEach(e=>{typeof e.resetGroup==`function`?e.resetGroup():typeof e.reset==`function`&&e.reset()}),this.resetInteractionState()}clearGroup(){this.formElements.forEach(e=>{typeof e.clearGroup==`function`?e.clearGroup():typeof e.clear==`function`&&e.clear()}),this.resetInteractionState()}resetInteractionState(){this.submitted=!1,this.touched=!1,this.dirty=!1,this.formElements.forEach(e=>{typeof e.resetInteractionState==`function`&&e.resetInteractionState()})}_getFromAllFormElementsFilter(e,t){return!e.disabled}_getFromAllFormElements(e,t){let n={},r=t||this._getFromAllFormElementsFilter;return this.formElements._keys().forEach(t=>{let i=this.formElements[t];i instanceof mu?n[t]=i.filter(t=>r(t,e)).map(t=>t[e]):r(i,e)&&(typeof i._getFromAllFormElements==`function`?n[t]=i._getFromAllFormElements(e):n[t]=i[e])}),n}_setValueForAllFormElements(e,t){this.formElements.forEach(n=>{n[e]=t})}_setValueMapForAllFormElements(e,t){t&&typeof t==`object`&&Object.keys(t).forEach(n=>{Array.isArray(this.formElements[n])&&this.formElements[n].forEach((r,i)=>{r[e]=t[n][i]}),this.formElements[n]?this.formElements[n][e]=t[n]:this.__pendingValues[e][n]=t[n]})}_anyFormElementHas(e){return Object.keys(this.formElements).some(t=>Array.isArray(this.formElements[t])?this.formElements[t].some(t=>!!t[e]):!!this.formElements[t][e])}_anyFormElementHasFeedbackFor(e){return Object.keys(this.formElements).some(t=>Array.isArray(this.formElements[t])?this.formElements[t].some(t=>!!(t.hasFeedbackFor&&t.hasFeedbackFor.includes(e))):!!(this.formElements[t].hasFeedbackFor&&this.formElements[t].hasFeedbackFor.includes(e)))}_everyFormElementHas(e){return Object.keys(this.formElements).every(t=>Array.isArray(this.formElements[t])?this.formElements[t].every(t=>!!t[e]):!!this.formElements[t][e])}__onChildValidatePerformed(e){e&&this.isRegisteredFormElement(e.target)&&this.validate()}_syncFocused(){this.focused=this._anyFormElementHas(`focused`)}_onFocusOut(e){let t=this.formElements[this.formElements.length-1];e.target===t&&(this.touched=!0),this.focused=!1}_syncDirty(){this.dirty=this._anyFormElementHas(`dirty`)}__storeAllDescriptionElementsInParentChain(){let e=this;for(;e;)Zl(e._getAriaDescriptionElements(),{reverse:!0}).forEach(e=>{e.getAttribute(`slot`)===`feedback`&&this.__descriptionElementsInParentChain.add(e)}),e=e._parentFormGroup}__linkParentMessages(e){this.__descriptionElementsInParentChain.forEach(t=>{typeof e.addToAriaDescribedBy==`function`&&e.addToAriaDescribedBy(t,{reorder:!1})})}__unlinkParentMessages(e){this.__descriptionElementsInParentChain.forEach(t=>{typeof e.removeFromAriaDescribedBy==`function`&&e.removeFromAriaDescribedBy(t)})}addFormElement(e,t){if(super.addFormElement(e,t),this.disabled&&e.makeRequestToBeDisabled(),this.__descriptionElementsInParentChain.size||this.__storeAllDescriptionElementsInParentChain(),this.__linkParentMessages(e),this.validate({clearCurrentResult:!0}),!e.modelValue){let t=this.__pendingValues;t.modelValue&&t.modelValue[e.name]?e.modelValue=t.modelValue[e.name]:t.serializedValue&&t.serializedValue[e.name]&&(e.serializedValue=t.serializedValue[e.name])}}get _initialModelValue(){return this._getFromAllFormElements(`_initialModelValue`)}removeFormElement(e){super.removeFormElement(e),this.validate({clearCurrentResult:!0}),typeof e.removeFromAriaLabelledBy==`function`&&this._labelNode&&e.removeFromAriaLabelledBy(this._labelNode,{reorder:!1}),this.__unlinkParentMessages(e)}_isEmpty(){return this.formElements.every(e=>e._isEmpty?.())}}),Cu=class extends _u(pu){static get properties(){return{readOnly:{type:Boolean,attribute:`readonly`,reflect:!0},type:{type:String,reflect:!0},placeholder:{type:String,reflect:!0}}}get slots(){return{...super.slots,input:()=>{let e=document.createElement(`input`),t=this.getAttribute(`value`);return t&&e.setAttribute(`value`,t),e}}}get _inputNode(){return super._inputNode}constructor(){super(),this.readOnly=!1,this.type=`text`,this.placeholder=``}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`readOnly`&&this.__delegateReadOnly()}firstUpdated(e){super.firstUpdated(e),this.__delegateReadOnly()}updated(e){super.updated(e),e.has(`type`)&&(this._inputNode.type=this.type),e.has(`placeholder`)&&(this._inputNode.placeholder=this.placeholder),e.has(`disabled`)&&(this._inputNode.disabled=this.disabled,this.validate()),e.has(`name`)&&(this._inputNode.name=this.name),e.has(`autocomplete`)&&(this._inputNode.autocomplete=this.autocomplete)}__delegateReadOnly(){this._inputNode&&(this._inputNode.readOnly=this.readOnly)}},wu=d`
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
`,Tu=class extends Cu{constructor(...e){super(...e),this.size=`medium`,this.small=!1,this.center=!1}static get styles(){return[...super.styles,tl,wu]}connectedCallback(){super.connectedCallback(),this._inputNode&&this.maxlength&&this.maxlength>0&&(this._inputNode.maxLength=this.maxlength)}};o([g({type:Number,reflect:!0})],Tu.prototype,`maxlength`,void 0),o([g({type:String,reflect:!0})],Tu.prototype,`size`,void 0),o([g({reflect:!0,type:Boolean})],Tu.prototype,`small`,void 0),o([g({reflect:!0,type:Boolean})],Tu.prototype,`center`,void 0),customElements.get(`craft-input`)||customElements.define(`craft-input`,Tu);var Eu=e=>e??m,Du=class extends su{static validatorName=`IsAcceptedFile`;static checkFileSize(e,t){return e<=t}static getExtension(e){return e?.slice(e.lastIndexOf(`.`))}static isExtensionAllowed(e,t){return t?.find(t=>t.toUpperCase()===e.toUpperCase())}static isFileTypeAllowed(e,t){return t?.find(t=>t.toUpperCase()===e.toUpperCase())}execute(e,t=this.param){let n,r,i=this.constructor,{allowedFileTypes:a,allowedFileExtensions:o,maxFileSize:s}=t;return a?.length?(n=e.some(e=>!i.isFileTypeAllowed(e.type,a)),n):o?.length?(r=e.some(e=>!i.isExtensionAllowed(i.getExtension(e.name),o)),r):e.findIndex(e=>!i.checkFileSize(e.size,s))>-1}static async getMessage(){return``}},Ou=class extends su{static validatorName=`DuplicateFileNames`;constructor(e,t){super(e,t),this.type=`info`}execute(e,t=this.param){return t.show}static async getMessage(){return Al().msg(`lion-input-file:uploadTextDuplicateFileName`)}},ku=524288e3,Au={type:`FILE_TYPE`,size:`FILE_SIZE`},ju={fail:`FAIL`,pass:`SUCCESS`},Mu=class{constructor(e,t){this.failedProp=[],this.systemFile=e,this._acceptCriteria=t,this.uploadFileStatus(),this.failedProp.length===0&&this.createDownloadUrl(e)}_getFileNameExtension(e){return e.slice(e.lastIndexOf(`.`))}uploadFileStatus(){if(this._acceptCriteria.allowedFileExtensions.length){let e=this._getFileNameExtension(this.systemFile.name);Du.isExtensionAllowed(e,this._acceptCriteria.allowedFileExtensions)||(this.status=ju.fail,this.failedProp.push(Au.type))}else if(this._acceptCriteria.allowedFileTypes.length){let e=this.systemFile.type;Du.isFileTypeAllowed(e,this._acceptCriteria.allowedFileTypes)||(this.status=ju.fail,this.failedProp.push(Au.type))}Du.checkFileSize(this.systemFile.size,this._acceptCriteria.maxFileSize)?this.status!==ju.fail&&(this.status=ju.pass):(this.status=ju.fail,this.failedProp.push(Au.size))}createDownloadUrl(e){this.downloadUrl=window.URL.createObjectURL(e)}},Nu=(e,t,n)=>{let r=new Map;for(let i=t;i<=n;i++)r.set(e[i],i);return r},Pu=t(class extends e{constructor(e){if(super(e),e.type!==i.CHILD)throw Error(`repeat() can only be used in text expressions`)}dt(e,t,n){let r;n===void 0?n=t:t!==void 0&&(r=t);let i=[],a=[],o=0;for(let t of e)i[o]=r?r(t,o):o,a[o]=n(t,o),o++;return{values:a,keys:i}}render(e,t,n){return this.dt(e,t,n).values}update(e,[t,n,r]){let i=Ji(e),{values:a,keys:o}=this.dt(t,n,r);if(!Array.isArray(i))return this.ut=o,a;let s=this.ut??=[],c=[],l,u,d=0,p=i.length-1,m=0,h=a.length-1;for(;d<=p&&m<=h;)if(i[d]===null)d++;else if(i[p]===null)p--;else if(s[d]===o[m])c[m]=Gi(i[d],a[m]),d++,m++;else if(s[p]===o[h])c[h]=Gi(i[p],a[h]),p--,h--;else if(s[d]===o[h])c[h]=Gi(i[d],a[h]),Wi(e,c[h+1],i[d]),d++,h--;else if(s[p]===o[m])c[m]=Gi(i[p],a[m]),Wi(e,i[d],i[p]),p--,m++;else if(l===void 0&&(l=Nu(o,m,h),u=Nu(s,d,p)),l.has(s[d]))if(l.has(s[p])){let t=u.get(o[m]),n=t===void 0?null:i[t];if(n===null){let t=Wi(e,i[d]);Gi(t,a[m]),c[m]=t}else c[m]=Gi(n,a[m]),Wi(e,i[d],n),i[t]=null;m++}else Yi(i[p]),p--;else Yi(i[d]),d++;for(;m<=h;){let t=Wi(e,c[h+1]);Gi(t,a[m]),c[m++]=t}for(;d<=p;){let e=i[d++];e!==null&&Yi(e)}return this.ut=o,qi(e,c),f}}),Fu=e=>{switch(e){case`bg-BG`:return s(()=>import(`./bg-BG-CObybLdG.js`),__vite__mapDeps([49,50]),import.meta.url);case`bg`:return s(()=>import(`./bg-wQv8tmsc.js`),__vite__mapDeps([51,50]),import.meta.url);case`cs-CZ`:return s(()=>import(`./cs-CZ-BwIkezDW.js`),__vite__mapDeps([52,53]),import.meta.url);case`cs`:return s(()=>import(`./cs-5tTjsUL1.js`),__vite__mapDeps([54,53]),import.meta.url);case`de-DE`:return s(()=>import(`./de-DE-LaNdL5bB.js`),__vite__mapDeps([55,56]),import.meta.url);case`de`:return s(()=>import(`./de-DYEGLgmt.js`),__vite__mapDeps([57,56]),import.meta.url);case`en-AU`:return s(()=>import(`./en-AU-BiyNSWc6.js`),__vite__mapDeps([58,59]),import.meta.url);case`en-GB`:return s(()=>import(`./en-GB-CZGY43uJ.js`),__vite__mapDeps([60,59]),import.meta.url);case`en-US`:return s(()=>import(`./en-US-CSlu5dxx.js`),__vite__mapDeps([61,59]),import.meta.url);case`en-PH`:case`en`:return s(()=>import(`./en-DiHy43zH.js`),__vite__mapDeps([62,59]),import.meta.url);case`es-ES`:return s(()=>import(`./es-ES-jPlhVXZA.js`),__vite__mapDeps([63,64]),import.meta.url);case`es`:return s(()=>import(`./es-DIo-tXtk.js`),__vite__mapDeps([65,64]),import.meta.url);case`fr-FR`:return s(()=>import(`./fr-FR-D9Wr5IMM.js`),__vite__mapDeps([66,67]),import.meta.url);case`fr-BE`:return s(()=>import(`./fr-BE-B1Z-P9dE.js`),__vite__mapDeps([68,67]),import.meta.url);case`fr`:return s(()=>import(`./fr-KNNuvZkx.js`),__vite__mapDeps([69,67]),import.meta.url);case`hu-HU`:return s(()=>import(`./hu-HU-DoQBKuAX.js`),__vite__mapDeps([70,71]),import.meta.url);case`hu`:return s(()=>import(`./hu-DOpZOk-1.js`),__vite__mapDeps([72,71]),import.meta.url);case`id-ID`:return s(()=>import(`./id-ID-7Sxz33aH.js`),__vite__mapDeps([73,74]),import.meta.url);case`id`:return s(()=>import(`./id-vPvEL-pw.js`),__vite__mapDeps([75,74]),import.meta.url);case`it-IT`:return s(()=>import(`./it-IT-JQQdrN0z.js`),__vite__mapDeps([76,77]),import.meta.url);case`it`:return s(()=>import(`./it-D9PmN7Mv.js`),__vite__mapDeps([78,77]),import.meta.url);case`nl-BE`:return s(()=>import(`./nl-BE-C_nEmOBa.js`),__vite__mapDeps([79,80]),import.meta.url);case`nl-NL`:return s(()=>import(`./nl-NL-9CtxRMFG.js`),__vite__mapDeps([81,80]),import.meta.url);case`nl`:return s(()=>import(`./nl-CSbWBoPS.js`),__vite__mapDeps([82,80]),import.meta.url);case`pl-PL`:return s(()=>import(`./pl-PL-BQMQAmiK.js`),__vite__mapDeps([83,84]),import.meta.url);case`pl`:return s(()=>import(`./pl-BcSXeD0R.js`),__vite__mapDeps([85,84]),import.meta.url);case`ro-RO`:return s(()=>import(`./ro-RO-CLWtFGEK.js`),__vite__mapDeps([86,87]),import.meta.url);case`ro`:return s(()=>import(`./ro-tPVWc-9n.js`),__vite__mapDeps([88,87]),import.meta.url);case`ru-RU`:return s(()=>import(`./ru-RU-OpGtkoCc.js`),__vite__mapDeps([89,90]),import.meta.url);case`ru`:return s(()=>import(`./ru-B5TFsE0s.js`),__vite__mapDeps([91,90]),import.meta.url);case`sk-SK`:return s(()=>import(`./sk-SK-H-rStg-J.js`),__vite__mapDeps([92,93]),import.meta.url);case`sk`:return s(()=>import(`./sk-C39Ho2ZP.js`),__vite__mapDeps([94,93]),import.meta.url);case`uk-UA`:return s(()=>import(`./uk-UA-CMqSolUP.js`),__vite__mapDeps([95,96]),import.meta.url);case`uk`:return s(()=>import(`./uk-BlyCX4EQ.js`),__vite__mapDeps([97,96]),import.meta.url);case`zh-CN`:case`zh`:return s(()=>import(`./zh-eSiYsgk9.js`),[],import.meta.url);default:return s(()=>import(`./en-DiHy43zH.js`),__vite__mapDeps([62,59]),import.meta.url)}},Iu=class extends Ul(Yl(h)){static get scopedElements(){return{...super.scopedElements,"lion-validation-feedback":ou}}static get properties(){return{fileList:{type:Array},multiple:{type:Boolean}}}static localizeNamespaces=[{"lion-input-file":Fu},...super.localizeNamespaces];constructor(){super(),this.fileList=[],this.multiple=!1}updated(e){super.updated(e),e.has(`fileList`)&&this._enhanceLightDomA11y()}_enhanceLightDomA11y(){let e=this.shadowRoot?.querySelectorAll(`[id^="file-feedback"]`),t=this.parentNode?.parentNode;e?.forEach(e=>{t?.addEventListener(`focusin`,()=>{e.setAttribute(`aria-live`,`polite`)}),t?.addEventListener(`focusout`,()=>{e.setAttribute(`aria-live`,`assertive`)})})}_removeFile(e){this.dispatchEvent(new CustomEvent(`file-remove-requested`,{detail:{removedFile:e,status:e.status,uploadResponse:e.response}}))}_validationFeedbackTemplate(e,t){return l`
      <lion-validation-feedback
        id="file-feedback-${t}"
        .feedbackData="${e}"
        aria-live="assertive"
      ></lion-validation-feedback>
    `}_listItemBeforeTemplate(e){return m}_listItemAfterTemplate(e,t){return l`
      <button
        class="selected__list__item__remove-button"
        aria-label="${this.msgLit(`lion-input-file:removeButtonLabel`,{fileName:e.systemFile.name})}"
        @click=${()=>this._removeFile(e)}
      >
        ${this._removeButtonContentTemplate()}
      </button>
    `}_removeButtonContentTemplate(){return l`✖️`}_selectedListItemTemplate(e){let t=ws();return l`
      <div class="selected__list__item" status="${e.status?e.status.toLowerCase():``}">
        <div class="selected__list__item__label">
          ${this._listItemBeforeTemplate(e)}
          <span id="selected-list-item-label-${t}" class="selected__list__item__label__text">
            <span class="sr-only">${this.msgLit(`lion-input-file:fileNameDescriptionLabel`)}</span>
            ${e.downloadUrl&&e.status!==`LOADING`?l`
                  <a
                    class="selected__list__item__label__link"
                    href="${e.downloadUrl}"
                    target="${e.downloadUrl.startsWith(`blob`)?`_blank`:``}"
                    rel="${Eu(e.downloadUrl.startsWith(`blob`)?`noopener noreferrer`:void 0)}"
                    >${e.systemFile?.name}</a
                  >
                `:e.systemFile?.name}
          </span>
          ${this._listItemAfterTemplate(e,t)}
        </div>
        ${e.status===`FAIL`&&e.validationFeedback?l`
              ${Pu(e.validationFeedback,e=>l`
                  ${this._validationFeedbackTemplate([e],t)}
                `)}
            `:m}
      </div>
    `}render(){return this.fileList?.length?l`
          ${this.multiple?l`
                <ul class="selected__list">
                  ${this.fileList.map(e=>l` <li>${this._selectedListItemTemplate(e)}</li> `)}
                </ul>
              `:l` ${this._selectedListItemTemplate(this.fileList[0])} `}
        `:m}static get styles(){return[d`
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
      `]}};function Lu(e,t=2){if(!+e)return`0 Bytes`;let n=1024,r=t<0?0:t,i=[` bytes`,`KB`,`MB`,`GB`,`TB`,`PB`,`EB`,`ZB`,`YB`],a=Math.floor(Math.log(e)/Math.log(n));return`${parseFloat((e/n**a).toFixed(r))}${i[a]}`}var Ru=class extends Yl(Ul(pu)){static get scopedElements(){return{...super.scopedElements,"lion-selected-file-list":Iu}}static get properties(){return{accept:{type:String},multiple:{type:Boolean,reflect:!0},buttonLabel:{type:String,attribute:`button-label`},maxFileSize:{type:Number,attribute:`max-file-size`},enableDropZone:{type:Boolean,attribute:`enable-drop-zone`},uploadOnSelect:{type:Boolean,attribute:`upload-on-select`},isDragging:{type:Boolean,attribute:`is-dragging`,reflect:!0},uploadResponse:{type:Array,state:!1},_selectedFilesMetaData:{type:Array,state:!0}}}static localizeNamespaces=[{"lion-input-file":Fu},...super.localizeNamespaces];static get validationTypes(){return[`error`,`info`]}get slots(){return{...super.slots,input:()=>l`<input .value="${Eu(this.getAttribute(`value`))}" />`,"file-select-button":()=>l`<button
          type="button"
          id="select-button-${this._inputId}"
          @click="${this.__openDialogOnBtnClick}"
        >
          ${this.buttonLabel}
        </button>`,after:()=>l`<div data-description></div>`,"selected-file-list":()=>({template:l`
          <lion-selected-file-list
            .fileList=${this._selectedFilesMetaData}
            .multiple=${this.multiple}
          ></lion-selected-file-list>
        `,renderAsDirectHostChild:!0})}}get _inputNode(){return super._inputNode}get _buttonNode(){return this.querySelector(`#select-button-${this._inputId}`)}get buttonLabel(){return this.__buttonLabel||this._buttonNode?.textContent?.trim()||``}set buttonLabel(e){let t=this.buttonLabel;this.__buttonLabel=e,this.requestUpdate(`buttonLabel`,t)}get _focusableNode(){return this._buttonNode}get _isDragAndDropSupported(){return`draggable`in document.createElement(`div`)}constructor(){super(),this.type=`file`,this._selectedFilesMetaData=[],this.uploadResponse=[],this.__initialUploadResponse=this.uploadResponse,this.uploadOnSelect=!1,this.multiple=!1,this.enableDropZone=!1,this.maxFileSize=ku,this.accept=``,this.buttonLabel=``,this._initialButtonLabel=``,this.modelValue=[],this._onRemoveFile=this._onRemoveFile.bind(this),this.__duplicateFileNamesValidator=new Ou({show:!1}),this.__previouslyParsedFiles=null}get _fileListNode(){return Array.from(this.children).find(e=>e.slot===`selected-file-list`)}connectedCallback(){super.connectedCallback(),this.__initialUploadResponse=this.uploadResponse,this._initialButtonLabel=this.buttonLabel,this._inputNode.addEventListener(`change`,this._onChange),this._inputNode.addEventListener(`click`,this._onClick)}disconnectedCallback(){super.disconnectedCallback(),this._inputNode.removeEventListener(`change`,this._onChange),this._inputNode.removeEventListener(`click`,this._onClick)}onLocaleUpdated(){super.onLocaleUpdated(),this.multiple?this.buttonLabel=this._initialButtonLabel||this.msgLit(`lion-input-file:selectTextMultipleFile`):this.buttonLabel=this._initialButtonLabel||this.msgLit(`lion-input-file:selectTextSingleFile`)}get operationMode(){return`upload`}get _acceptCriteria(){let e=[],t=[];if(this.accept){let n=this.accept.replace(/\s+/g,``).split(`,`);e=n.filter(e=>e.includes(`/`)),t=n.filter(e=>!e.includes(`/`))}return{allowedFileTypes:e,allowedFileExtensions:t,maxFileSize:this.maxFileSize}}reset(){super.reset(),this._selectedFilesMetaData=[],this.uploadResponse=this.__initialUploadResponse,this.modelValue=[],this.dirty=!1}clear(){this._selectedFilesMetaData=[],this.uploadResponse=[],this.modelValue=[]}_showFeedbackConditionFor(e,t){return super._showFeedbackConditionFor(e,t)&&!(this.validationStates.error?.FileTypeAllowed||this.validationStates.error?.FileSizeAllowed)}parser(){if(this.__previouslyParsedFiles===this._inputNode.files)return this.modelValue;this.__previouslyParsedFiles=this._inputNode.files;let e=this._inputNode.files?Array.from(this._inputNode.files):[];return this.multiple?[...this.modelValue??[],...e]:e}formatter(e){return this._inputNode?.value||``}__setupDragDropEventListeners(){let e=this.shadowRoot?.querySelector(`.input-file__drop-zone`);[`dragenter`,`dragover`,`dragleave`].forEach(t=>{e?.addEventListener(t,e=>{e.preventDefault(),e.stopPropagation(),this.isDragging=t!==`dragleave`},!1)}),window.addEventListener(`drop`,e=>{e.target===this._inputNode&&e.preventDefault(),this.isDragging=!1},!1)}firstUpdated(e){super.firstUpdated(e),this.__setupFileValidators(),this._inputNode&&(this._inputNode.type=this.type,this._inputNode.setAttribute(`tabindex`,`-1`),this._inputNode.multiple=this.multiple,this.accept.length&&(this._inputNode.accept=this.accept)),this.enableDropZone&&this._isDragAndDropSupported&&(this.__setupDragDropEventListeners(),this.setAttribute(`drop-zone`,``)),this._fileListNode.addEventListener(`file-remove-requested`,this._onRemoveFile)}updated(e){super.updated(e),e.has(`disabled`)&&(this._inputNode.disabled=this.disabled,this.validate()),e.has(`buttonLabel`)&&this._buttonNode&&(this._buttonNode.textContent=this.buttonLabel),e.has(`name`)&&(this._inputNode.name=this.name),e.has(`_ariaLabelledNodes`)&&this.__syncAriaLabelledByAttributesToButton(),e.has(`_ariaDescribedNodes`)&&this.__syncAriaDescribedByAttributesToButton(),e.has(`uploadResponse`)&&(this._selectedFilesMetaData.length===0&&this.uploadResponse.forEach(e=>{let t={systemFile:{name:e.name},response:e,status:e.status,validationFeedback:[{message:e.errorMessage}]};this._selectedFilesMetaData=[...this._selectedFilesMetaData,t]}),this._selectedFilesMetaData.forEach(e=>{!this.uploadResponse.some(t=>t.name===e.systemFile.name)&&this.uploadOnSelect?this.__removeFileFromList(e):(this.uploadResponse.forEach(t=>{t.name===e.systemFile.name&&(e.response=t,e.downloadUrl=t.downloadUrl?t.downloadUrl:e.downloadUrl,e.status=t.status,e.validationFeedback=[{type:typeof t.errorMessage==`string`&&t.errorMessage?.length>0?`error`:`success`,message:t.errorMessage??``}])}),this._selectedFilesMetaData=[...this._selectedFilesMetaData])}),this._updateUploadButtonDescription())}__computeNewAddedFiles(e){let t=e.filter(e=>this._selectedFilesMetaData.findIndex(t=>t.systemFile.name===e.name)===-1);return this.__duplicateFileNamesValidator.param={show:e.length!==t.length},this.validate(),t}_processDroppedFiles(e){if(e.preventDefault(),this.isDragging=!1,!(e.dataTransfer&&e.dataTransfer.items.length>1&&!this.multiple||!e.dataTransfer?.files)){if(this._inputNode.files=e.dataTransfer.files,this.multiple){let t=this.__computeNewAddedFiles(Array.from(e.dataTransfer.files));this.modelValue=[...this.modelValue??[],...t]}else this.modelValue=Array.from(e.dataTransfer.files);this._processFiles(Array.from(e.dataTransfer.files))}}_onChange(e){this.touched=!0,this._onUserInputChanged(),this._processFiles(e?.target?.files)}_onClick(e){e.target.value=``}__syncAriaLabelledByAttributesToButton(){if(this._inputNode.hasAttribute(`aria-labelledby`)){let e=this._inputNode.getAttribute(`aria-labelledby`);this._buttonNode?.setAttribute(`aria-labelledby`,`select-button-${this._inputId} ${e}`)}}__syncAriaDescribedByAttributesToButton(){if(this._inputNode.hasAttribute(`aria-describedby`)){let e=this._inputNode.getAttribute(`aria-describedby`)||``;this._buttonNode?.setAttribute(`aria-describedby`,e)}}__setupFileValidators(){this.defaultValidators=[new Du(this._acceptCriteria),this.__duplicateFileNamesValidator]}_processFiles(e){let t=this.__computeNewAddedFiles(Array.from(e));!this.multiple&&t.length>0&&(this._selectedFilesMetaData=[],this.uploadResponse=[]);let n;for(let e of t.values())n=new Mu(e,this._acceptCriteria),n.failedProp?.length?(this._handleErroredFiles(n),this.uploadResponse=[...this.uploadResponse,{name:n.systemFile.name,status:`FAIL`,errorMessage:n.validationFeedback[0].message}]):this.uploadResponse=[...this.uploadResponse,{name:n.systemFile.name,status:`SUCCESS`}],this._selectedFilesMetaData=[...this._selectedFilesMetaData,n],this._handleErrors();let r=this._selectedFilesMetaData.filter(({systemFile:e,status:n})=>t.includes(e)&&n===`SUCCESS`).map(({systemFile:e})=>e);r.length>0&&this._dispatchFileListChangeEvent(r)}_dispatchFileListChangeEvent(e){this.dispatchEvent(new CustomEvent(`file-list-changed`,{detail:{newFiles:e}}))}_handleErrors(){let e=!1;if(this._selectedFilesMetaData.forEach(t=>{t.failedProp&&t.failedProp.length>0&&(e=!0)}),e)this.hasFeedbackFor?.push(`error`),this.shouldShowFeedbackFor.push(`error`);else if(this._prevHasErrors&&this.hasFeedbackFor.includes(`error`)){let e=this.hasFeedbackFor.indexOf(`error`);this.hasFeedbackFor.slice(e,e+1);let t=this.shouldShowFeedbackFor.indexOf(`error`);this.shouldShowFeedbackFor.slice(t,t+1)}this._prevHasErrors=e}_handleErroredFiles(e){e.validationFeedback=[];let{allowedFileExtensions:t,allowedFileTypes:n}=this._acceptCriteria,r=[],i=0,a;t.length?(r=t,a=r.pop(),i=r.length):n.length&&(n.forEach(e=>{if(e.endsWith(`/*`))r.push(e.slice(0,-2));else if(e===`text/plain`)r.push(`text`);else{let t=e.indexOf(`/`),n=e.slice(t+1);if(!n.includes(`+`))r.push(`.${n}`);else{let e=n.split(`+`);r.push(`.${e[0]}`)}}}),a=r.pop(),i=r.length);let o=``;o=a?i?`${this.msgLit(`lion-input-file:allowedFileValidatorComplex`,{allowedTypesArray:r.join(`, `),allowedTypesLastItem:a,maxSize:Lu(this.maxFileSize)})}`:`${this.msgLit(`lion-input-file:allowedFileValidatorSimple`,{allowedType:a,maxSize:Lu(this.maxFileSize)})}`:`${this.msgLit(`lion-input-file:allowedFileSize`,{maxSize:Lu(this.maxFileSize)})}`;let s={message:o,type:`error`};e.validationFeedback?.push(s)}_updateUploadButtonDescription(){let e=[],t;this._selectedFilesMetaData.forEach(n=>{n.status===`FAIL`&&(t=n.validationFeedback?n.validationFeedback[0].message.toString():``,e.push(n.systemFile.name))});let n=this.querySelector(`[slot="after"]`);if(n)if(!this._selectedFilesMetaData||this._selectedFilesMetaData.length===0)this.uploadOnSelect?n.textContent=this.msgLit(`lion-input-file:noFilesUploaded`):n.textContent=this.msgLit(`lion-input-file:noFilesSelected`);else if(this._selectedFilesMetaData.length===1){let{name:e}=this._selectedFilesMetaData[0].systemFile;this.uploadOnSelect?n.textContent=t||this.msgLit(`lion-input-file:fileUploaded`)+(e??``):n.textContent=t||this.msgLit(`lion-input-file:fileSelected`)+(e??``)}else this.uploadOnSelect?n.textContent=`${this.msgLit(`lion-input-file:filesUploaded`,{numberOfFiles:this._selectedFilesMetaData.length})} ${t?this.msgLit(`lion-input-file:generalValidatorMessage`,{validatorMessage:t,listOfErroneousFiles:e.join(`, `)}):``}`:n.textContent=`${this.msgLit(`lion-input-file:filesSelected`,{numberOfFiles:this._selectedFilesMetaData.length})} ${t?this.msgLit(`lion-input-file:generalValidatorMessage`,{validatorMessage:t,listOfErroneousFiles:e.join(`, `)}):``}`}__removeFileFromList(e){this._selectedFilesMetaData=this._selectedFilesMetaData.filter(t=>t.systemFile.name!==e.systemFile.name),this.modelValue&&=this.modelValue.filter(t=>t.name!==e.systemFile.name),this._inputNode.value=``,this._handleErrors(),this._updateUploadButtonDescription()}_onRemoveFile(e){if(this.disabled)return;let{removedFile:t}=e.detail;!this.uploadOnSelect&&t&&this.__removeFileFromList(t),this._removeFile(t)}_removeFile(e){this.dispatchEvent(new CustomEvent(`file-removed`,{detail:{removedFile:e,status:e.status,uploadResponse:e.response}}))}_reflectBackOn(){return!1}_isEmpty(){return this.modelValue?.length===0}_dropZoneTemplate(){return l`
      <div @drop="${this._processDroppedFiles}" class="input-file__drop-zone">
        <div class="input-file__drop-zone__text">
          ${this.msgLit(`lion-input-file:dragAndDropText`)}
        </div>
        <slot name="file-select-button"></slot>
      </div>
    `}_inputGroupAfterTemplate(){return l` <slot name="selected-file-list"></slot> `}_inputGroupInputTemplate(){return l`
      <slot name="input"> </slot>
      <slot name="after"> </slot>
      ${this.enableDropZone&&this._isDragAndDropSupported?this._dropZoneTemplate():l`
            <div class="input-group__file-select-button">
              <slot name="file-select-button"></slot>
            </div>
          `}
    `}static get styles(){return[super.styles,d`
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
      `]}__openDialogOnBtnClick(e){e.preventDefault(),e.stopPropagation(),this._inputNode.click()}},zu=class extends Iu{static get styles(){return[...super.styles,d`
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
      `]}_listItemAfterTemplate(e,t){return l`
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
    `}_removeButtonContentTemplate(){return l`<craft-icon name="x"></craft-icon>`}_listItemBeforeTemplate(e){return l`<img src="${e.downloadUrl}" alt="" class="preview-thumb" />`}},Bu=d`
  /* Add any craft-specific styles for input-file here */
  ::slotted([slot='selected-file-list']) {
    margin-block-start: var(--c-spacing-lg);
  }
`,Vu=class extends Ru{static get styles(){return[...super.styles,tl,Bu]}get slots(){return{...super.slots,"file-select-button":()=>l`<craft-button
          type="button"
          id="select-button-${this._inputId}"
          @click="${this.__openDialogOnBtnClick}"
        >
          ${this.buttonLabel}
        </craft-button>`}}static get scopedElements(){return{...super.scopedElements,"lion-selected-file-list":zu}}};customElements.get(`craft-input-file`)||customElements.define(`craft-input-file`,Vu);var Hu=class extends Cu{static get styles(){return[...super.styles,tl,d`
        .input-group__container {
          position: relative;
        }

        .input-group__suffix {
          position: absolute;
          inset-inline-end: var(--c-input-spacing-inline);
          inset-block-start: 50%;
          transform: translateY(calc(-50%));
        }
      `]}constructor(){super(),this._visible=!1,this.reveal=()=>{this._visible=!this._visible,this.type=this._visible?`text`:`password`},this.renderSuffix=()=>l`
      <craft-button
        type="button"
        icon
        size="small"
        variant="plain"
        @click="${this.reveal}"
        appearance="plain"
      >
        <span class="icon"
          >${this._visible?l`<craft-icon
                name="eye-slash"
                label="${n(`Hide`)}"
              ></craft-icon>`:l`<craft-icon name="eye" label="${n(`Show`)}"></craft-icon>`}
        </span>
      </craft-button>
    `,this.type=`password`}get slots(){return{...super.slots,suffix:()=>({template:this.renderSuffix()})}}};o([_()],Hu.prototype,`_visible`,void 0),customElements.get(`craft-input-password`)||customElements.define(`craft-input-password`,Hu);var Uu=d`
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
`,Wu=class extends h{constructor(...e){super(...e),this.size=``,this.variant=``,this.icon=null}renderPrefix(){return l`<div class="cp-chip__prefix" part="prefix">
      <slot name="prefix">
        <slot name="icon">
          ${this.icon?l`<craft-icon name="${this.icon}"></craft-icon>`:m}
        </slot>
      </slot>
    </div>`}render(){let e=!!this.querySelector(`[slot="prefix"]`)||!!this.querySelector(`[slot="icon"]`)||this.icon,t=!!this.querySelector(`[slot="suffix"]`);return l`
      <div
        part="chip"
        class="${a({"cp-chip":!0,"cp-chip--small":this.size===`small`,"cp-chip--medium":this.size===`medium`,"cp-chip--large":this.size===`large`,"cp-chip--plain":this.variant===`plain`})}"
      >
        ${e?this.renderPrefix():m}
        <div class="cp-chip__body">
          <slot></slot>
        </div>
        ${t?l`<div class="cp-chip__suffix" part="suffix">
              <slot name="suffix"></slot>
            </div>`:m}
      </div>
    `}};Wu.styles=[Uu],o([g()],Wu.prototype,`size`,void 0),o([g()],Wu.prototype,`variant`,void 0),o([g()],Wu.prototype,`icon`,void 0),customElements.get(`craft-chip`)||customElements.define(`craft-chip`,Wu);var Gu=d`
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
`,Ku=class extends h{constructor(...e){super(...e),this.label=null,this.status=null}getLabel(){return!this.label&&this.status?`Status: ${this.status}`:this.label}render(){return l`
      <span
        class="${a({status:!0,"status--live":this.status===`live`,"status--enabled":this.status===`enabled`,"status--pending":this.status===`pending`,"status--expired":this.status===`expired`,"status--disabled":this.status===`disabled`})}"
        role="img"
        aria-label="${this.getLabel()}"
      ></span>
    `}};Ku.styles=[Gu],o([g()],Ku.prototype,`label`,void 0),o([g()],Ku.prototype,`status`,void 0),customElements.get(`craft-status`)||customElements.define(`craft-status`,Ku);var qu=new Map;function Ju(e){var t=qu.get(e);t&&t.destroy()}function Yu(e){var t=qu.get(e);t&&t.update()}var Xu=null;typeof window>`u`?((Xu=function(e){return e}).destroy=function(e){return e},Xu.update=function(e){return e}):((Xu=function(e,t){return e&&Array.prototype.forEach.call(e.length?e:[e],function(e){return function(e){if(e&&e.nodeName&&e.nodeName===`TEXTAREA`&&!qu.has(e)){var t,n=null,r=window.getComputedStyle(e),i=(t=e.value,function(){o({testForHeightReduction:t===``||!e.value.startsWith(t),restoreTextAlign:null}),t=e.value}),a=function(t){e.removeEventListener(`autosize:destroy`,a),e.removeEventListener(`autosize:update`,s),e.removeEventListener(`input`,i),window.removeEventListener(`resize`,s),Object.keys(t).forEach(function(n){return e.style[n]=t[n]}),qu.delete(e)}.bind(e,{height:e.style.height,resize:e.style.resize,textAlign:e.style.textAlign,overflowY:e.style.overflowY,overflowX:e.style.overflowX,wordWrap:e.style.wordWrap});e.addEventListener(`autosize:destroy`,a),e.addEventListener(`autosize:update`,s),e.addEventListener(`input`,i),window.addEventListener(`resize`,s),e.style.overflowX=`hidden`,e.style.wordWrap=`break-word`,qu.set(e,{destroy:a,update:s}),s()}function o(t){var i,a,s=t.restoreTextAlign,c=s===void 0?null:s,l=t.testForHeightReduction,u=l===void 0||l,d=r.overflowY;if(e.scrollHeight!==0&&(r.resize===`vertical`?e.style.resize=`none`:r.resize===`both`&&(e.style.resize=`horizontal`),u&&(i=function(e){for(var t=[];e&&e.parentNode&&e.parentNode instanceof Element;)e.parentNode.scrollTop&&t.push([e.parentNode,e.parentNode.scrollTop]),e=e.parentNode;return function(){return t.forEach(function(e){var t=e[0],n=e[1];t.style.scrollBehavior=`auto`,t.scrollTop=n,t.style.scrollBehavior=null})}}(e),e.style.height=``),a=r.boxSizing===`content-box`?e.scrollHeight-(parseFloat(r.paddingTop)+parseFloat(r.paddingBottom)):e.scrollHeight+parseFloat(r.borderTopWidth)+parseFloat(r.borderBottomWidth),r.maxHeight!==`none`&&a>parseFloat(r.maxHeight)?(r.overflowY===`hidden`&&(e.style.overflow=`scroll`),a=parseFloat(r.maxHeight)):r.overflowY!==`hidden`&&(e.style.overflow=`hidden`),e.style.height=a+`px`,c&&(e.style.textAlign=c),i&&i(),n!==a&&(e.dispatchEvent(new Event(`autosize:resized`,{bubbles:!0})),n=a),d!==r.overflow&&!c)){var f=r.textAlign;r.overflow===`hidden`&&(e.style.textAlign=f===`start`?`end`:`start`),o({restoreTextAlign:f,testForHeightReduction:!0})}}function s(){o({testForHeightReduction:!0,restoreTextAlign:null})}}(e)}),e}).destroy=function(e){return e&&Array.prototype.forEach.call(e.length?e:[e],Ju),e},Xu.update=function(e){return e&&Array.prototype.forEach.call(e.length?e:[e],Yu),e});var Zu=Xu,Qu=class extends pu{get _inputNode(){return Array.from(this.children).find(e=>e.slot===`input`)}},$u=class extends _u(Qu){static get properties(){return{maxRows:{type:Number,attribute:`max-rows`},rows:{type:Number,reflect:!0},readOnly:{type:Boolean,attribute:`readonly`,reflect:!0},placeholder:{type:String,reflect:!0}}}get slots(){return{...super.slots,input:()=>{let e=document.createElement(`textarea`);return e.style.resize!==void 0&&(e.style.resize=`none`),e}}}constructor(){super(),this.rows=2,this.maxRows=6,this.readOnly=!1,this.placeholder=``}connectedCallback(){super.connectedCallback(),this.__initializeAutoresize(),this.__intersectionObserver=new IntersectionObserver(()=>this.resizeTextarea()),this.__intersectionObserver.observe(this)}updated(e){if(super.updated(e),e.has(`name`)&&(this._inputNode.name=this.name),e.has(`autocomplete`)&&(this._inputNode.autocomplete=this.autocomplete),e.has(`disabled`)&&(this._inputNode.disabled=this.disabled,this.validate()),e.has(`rows`)){let e=this._inputNode;e&&(e.rows=this.rows)}if(e.has(`readOnly`)){let e=this._inputNode;e&&(e.readOnly=this.readOnly)}if(e.has(`placeholder`)){let e=this._inputNode;e&&(e.placeholder=this.placeholder)}e.has(`modelValue`)&&this.resizeTextarea(),(e.has(`maxRows`)||e.has(`rows`))&&this.setTextareaMaxHeight()}disconnectedCallback(){super.disconnectedCallback(),Zu.destroy(this._inputNode)}setTextareaMaxHeight(){let{value:e}=this._inputNode;this._inputNode.value=``,this.resizeTextarea();let t=window.getComputedStyle(this._inputNode,null),n=parseFloat(t.lineHeight)||parseFloat(t.height)/this.rows,r=parseFloat(t.paddingTop)+parseFloat(t.paddingBottom),i=parseFloat(t.borderTopWidth)+parseFloat(t.borderBottomWidth),a=t.boxSizing===`border-box`?r+i:0;this._inputNode.style.maxHeight=`${n*this.maxRows+a}px`,this._inputNode.value=e,this.resizeTextarea()}static get styles(){return[...super.styles,d`
        .input-group__container > .input-group__input ::slotted(.form-control) {
          box-sizing: content-box;
          overflow-x: hidden; /* for FF adds height to the TextArea to reserve place for scroll-bars */
        }

        /* Workaround https://bugzilla.mozilla.org/show_bug.cgi?id=1739079 */
        :host([disabled]) ::slotted(textarea) {
          user-select: none;
        }
      `]}get updateComplete(){return this.__textareaUpdateComplete?Promise.all([this.__textareaUpdateComplete,super.updateComplete]):super.updateComplete}resizeTextarea(){Zu.update(this._inputNode)}__initializeAutoresize(){this.__shady_native_contains?this.__textareaUpdateComplete=this.__waitForTextareaRenderedInRealDOM().then(()=>{this.__startAutoresize(),this.__textareaUpdateComplete=null}):this.__startAutoresize()}async __waitForTextareaRenderedInRealDOM(){let e=3;for(;e!==0&&!this.__shady_native_contains(this._inputNode);)await new Promise(e=>setTimeout(e)),--e}__startAutoresize(){Zu(this._inputNode),this.setTextareaMaxHeight()}},ed=d`
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
`,td=class extends $u{constructor(...e){super(...e),this.monospace=!1}static get styles(){return[...super.styles,tl,ed]}};o([g({type:Boolean,reflect:!0})],td.prototype,`monospace`,void 0),customElements.get(`craft-textarea`)||customElements.define(`craft-textarea`,td);var nd=d`
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
`,rd=class extends h{render(){return l`<slot></slot>`}};rd.styles=[nd],customElements.get(`craft-button-group`)||customElements.define(`craft-button-group`,rd);var id=class extends gu(h){static get properties(){return{tabIndex:{type:Number,reflect:!0,attribute:`tabindex`}}}constructor(){super(),this.tabIndex=0}connectedCallback(){super.connectedCallback(),this.setAttribute(`role`,`listbox`)}createRenderRoot(){return this}},ad=R(e=>class extends eu(Yl(vu(xs(hu(e))))){static get properties(){return{orientation:String,selectionFollowsFocus:{type:Boolean,attribute:`selection-follows-focus`},rotateKeyboardNavigation:{type:Boolean,attribute:`rotate-keyboard-navigation`},hasNoDefaultSelected:{type:Boolean,reflect:!0,attribute:`has-no-default-selected`},_noTypeAhead:{type:Boolean}}}static get styles(){return[...super.styles||[],d`
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
        `]}_inputGroupInputTemplate(){return l`
        <div class="input-group__input">
          <slot name="input"></slot>
          <slot id="options-outlet"></slot>
        </div>
      `}static get scopedElements(){return{...super.scopedElements,"lion-options":id}}get slots(){return{...super.slots,input:()=>{let e=this.createScopedElement(`lion-options`);return e.setAttribute(`data-tag-name`,`lion-options`),e.registrationTarget=this,e}}}get _inputNode(){return this.querySelector(`[slot="input"]`)}get _listboxNode(){return this._inputNode}get _listboxActiveDescendantNode(){return this._listboxNode.querySelector(`#${this._listboxActiveDescendant}`)}get _listboxSlot(){return this.shadowRoot.querySelector(`slot[name=input]`)}get _scrollTargetNode(){return this._listboxNode}get _activeDescendantOwnerNode(){return this._listboxNode}get activeIndex(){return this.formElements.findIndex(e=>e.active===!0)}set activeIndex(e){if(this.formElements[e]){let t=this.formElements[e];this.__setChildActive(t)}else this.__setChildActive(null)}get checkedIndex(){let e=this.formElements;return this.multipleChoice?e.filter(e=>e.checked).map(t=>e.indexOf(t)):e.indexOf(e.find(e=>e.checked))}set checkedIndex(e){this.setCheckedIndex(e)}constructor(){super(),this.hasNoDefaultSelected=!1,this.orientation=`vertical`,this.rotateKeyboardNavigation=!1,this.selectionFollowsFocus=!1,this._noTypeAhead=!1,this._typeAheadTimeout=1e3,this._listboxActiveDescendant=null,this.__hasInitialSelectedFormElement=!1,this._repropagationRole=`choice-group`,this._listboxReceivesNoFocus=!1,this._oldModelValue=void 0,this._listboxOnKeyDown=this._listboxOnKeyDown.bind(this),this._listboxOnClick=this._listboxOnClick.bind(this),this._listboxOnKeyUp=this._listboxOnKeyUp.bind(this),this._onChildActiveChanged=this._onChildActiveChanged.bind(this),this.__proxyChildModelValueChanged=this.__proxyChildModelValueChanged.bind(this),this.__preventScrollingWithArrowKeys=this.__preventScrollingWithArrowKeys.bind(this),this.__typedChars=[]}connectedCallback(){this._listboxNode&&(this._listboxNode.registrationTarget=this),super.connectedCallback(),this._setupListboxNode(),this.__setupEventListeners(),this.registrationComplete.then(()=>{this.__initInteractionStates()})}firstUpdated(e){super.firstUpdated(e),this.__moveOptionsToListboxNode(),this.registrationComplete.then(()=>{this._initialModelValue=this.modelValue}),new MutationObserver(()=>{this._onListboxContentChanged()}).observe(this._listboxNode,{childList:!0})}updated(e){super.updated(e),e.has(`disabled`)&&(this.disabled?this.__requestOptionsToBeDisabled():this.__retractRequestOptionsToBeDisabled())}disconnectedCallback(){super.disconnectedCallback(),this._teardownListboxNode(),this.__teardownEventListeners()}setCheckedIndex(e){if(this.multipleChoice&&Array.isArray(e)){this._uncheckChildren(this.formElements.filter(t=>t===e)),e.forEach(e=>{this.formElements[e]&&(this.formElements[e].checked=!this.formElements[e].checked)});return}typeof e==`number`&&(e===-1&&this._uncheckChildren(),this.formElements[e]&&(this.formElements[e].disabled?this._uncheckChildren():this.multipleChoice?this.formElements[e].checked=!this.formElements[e].checked:this.formElements[e].checked=!0))}addFormElement(e,t){super.addFormElement(e,t),e.id=e.id||`${this.localName}-option-${ws()}`,this.disabled&&e.makeRequestToBeDisabled(),this.__setAttributeForAllFormElements(`aria-setsize`,this.formElements.length),this.formElements.forEach((e,t)=>{e.setAttribute(`aria-posinset`,t+1)}),this.__proxyChildModelValueChanged({target:e}),this.resetInteractionState()}resetInteractionState(){super.resetInteractionState(),this.submitted=!1}reset(){this.modelValue=this._initialModelValue,this.activeIndex=-1,this.resetInteractionState()}clear(){super.clear(),this.setCheckedIndex(-1)}_handleTypeAhead(e,{setAsChecked:t}){let{key:n,code:r}=e;if(r.startsWith(`Key`)||r.startsWith(`Digit`)||r.startsWith(`Numpad`)){e.preventDefault(),this.__typedChars.push(n);let r=this.__typedChars.join(``),i=this.formElements.findIndex(e=>e.modelValue.value.toLowerCase().startsWith(r));i>=0&&(t&&this.setCheckedIndex(i),this.activeIndex=i),this.__pendingTypeAheadTimeout&&window.clearTimeout(this.__pendingTypeAheadTimeout),this.__pendingTypeAheadTimeout=setTimeout(()=>{this.__typedChars=[]},this._typeAheadTimeout)}}_getCheckedElements(){return this.formElements.filter(e=>e.checked)}_setupListboxNode(){this._listboxNode?this.__setupListboxNodeInteractions():this._listboxSlot&&this._listboxSlot.addEventListener(`slotchange`,()=>{this.__setupListboxNodeInteractions()})}_onListboxContentChanged(){}_teardownListboxNode(){this._listboxNode&&(this._listboxNode.removeEventListener(`keydown`,this._listboxOnKeyDown),this._listboxNode.removeEventListener(`click`,this._listboxOnClick),this._listboxNode.removeEventListener(`keyup`,this._listboxOnKeyUp))}_getNextEnabledOption(e,t=1){return this.__getEnabledOption(e,t)}_getPreviousEnabledOption(e,t=-1){return this.__getEnabledOption(e,t)}_onChildActiveChanged({target:e}){e.active===!0&&this.__setChildActive(e)}_listboxOnKeyDown(e){if(this.disabled)return;this._isHandlingUserInput=!0,setTimeout(()=>{this._isHandlingUserInput=!1});let{key:t}=e;switch(t){case` `:case`Enter`:if(t===` `&&this._listboxReceivesNoFocus||(t===` `&&e.preventDefault(),!this.formElements[this.activeIndex])||this.formElements[this.activeIndex].disabled)return;this.formElements[this.activeIndex].href&&this.formElements[this.activeIndex].click(),this.setCheckedIndex(this.activeIndex);break;case`ArrowUp`:e.preventDefault(),this.orientation===`vertical`&&(this.activeIndex=this._getPreviousEnabledOption(this.activeIndex));break;case`ArrowLeft`:if(this._listboxReceivesNoFocus)return;e.preventDefault(),this.orientation===`horizontal`&&(this.activeIndex=this._getPreviousEnabledOption(this.activeIndex));break;case`ArrowDown`:e.preventDefault(),this.orientation===`vertical`&&(this.activeIndex=this._getNextEnabledOption(this.activeIndex));break;case`ArrowRight`:if(this._listboxReceivesNoFocus)return;e.preventDefault(),this.orientation===`horizontal`&&(this.activeIndex=this._getNextEnabledOption(this.activeIndex));break;case`Home`:if(this._listboxReceivesNoFocus)return;e.preventDefault(),this.activeIndex=this._getNextEnabledOption(0,0);break;case`End`:if(this._listboxReceivesNoFocus)return;e.preventDefault(),this.activeIndex=this._getPreviousEnabledOption(this.formElements.length-1,0);break;default:this._noTypeAhead||this._handleTypeAhead(e,{setAsChecked:this.selectionFollowsFocus&&!this.multipleChoice})}[`ArrowUp`,`ArrowDown`,`ArrowLeft`,`ArrowRight`,`Home`,`End`].includes(t)&&this.selectionFollowsFocus&&!this.multipleChoice&&this.setCheckedIndex(this.activeIndex)}_listboxOnClick(e){}_listboxOnKeyUp(e){if(this.disabled)return;this._isHandlingUserInput=!0,setTimeout(()=>{this._isHandlingUserInput=!1});let{key:t}=e;switch(t){case`ArrowUp`:case`ArrowDown`:case`Home`:case`End`:case`Enter`:e.preventDefault()}}_onLabelClick(){this._listboxNode.focus()}_scrollIntoView(e,t){e.scrollIntoView({behavior:`smooth`,block:`nearest`})}__setupEventListeners(){this._listboxNode.addEventListener(`active-changed`,this._onChildActiveChanged),this._listboxNode.addEventListener(`model-value-changed`,this.__proxyChildModelValueChanged)}__teardownEventListeners(){this._listboxNode.removeEventListener(`active-changed`,this._onChildActiveChanged),this._listboxNode.removeEventListener(`model-value-changed`,this.__proxyChildModelValueChanged)}__setChildActive(e){if(this.formElements.forEach(t=>{t.active=e===t}),!e){this._activeDescendantOwnerNode.removeAttribute(`aria-activedescendant`);return}this._activeDescendantOwnerNode.setAttribute(`aria-activedescendant`,e.id),this._scrollIntoView(e,this._scrollTargetNode)}_uncheckChildren(e=[]){let t=Array.isArray(e)?e:[e];this.formElements.forEach(e=>{t.includes(e)||(e.checked=!1)})}__onChildCheckedChanged(e){let{target:t}=e;e.stopPropagation&&e.stopPropagation(),t.checked&&!this.multipleChoice&&this._uncheckChildren(t)}__setAttributeForAllFormElements(e,t){this.formElements.forEach(n=>{n.setAttribute(e,t)})}__proxyChildModelValueChanged(e){e.stopPropagation&&e.stopPropagation(),this.__onChildCheckedChanged(e),this.requestUpdate(`modelValue`,this._oldModelValue),e.detail&&e.detail.formPath&&this.dispatchEvent(new CustomEvent(`model-value-changed`,{detail:{formPath:e.detail.formPath,isTriggeredByUser:e.detail.isTriggeredByUser||this._isHandlingUserInput,element:e.target}})),this._oldModelValue=this.modelValue}__getEnabledOption(e,t){let n=e=>t===1?e<this.formElements.length:e>=0;for(let r=e+t;n(r);r+=t)if(this.formElements[r]&&!this.formElements[r].hasAttribute(`aria-hidden`))return r;if(this.rotateKeyboardNavigation){let e=t===-1?this.formElements.length-1:0;for(let r=e;n(r);r+=t)if(this.formElements[r]&&!this.formElements[r].hasAttribute(`aria-hidden`))return r}return e}__moveOptionsToListboxNode(){let e=this.shadowRoot.getElementById(`options-outlet`);e&&(ys(this,this._listboxNode),e.addEventListener(`slotchange`,()=>{ys(this,this._listboxNode)}))}__preventScrollingWithArrowKeys(e){if(this.disabled)return;let{key:t}=e;switch(t){case`ArrowUp`:case`ArrowDown`:case`Home`:case`End`:e.preventDefault()}}__setupListboxNodeInteractions(){this._listboxNode.setAttribute(`role`,`listbox`),this._listboxNode.setAttribute(`aria-orientation`,this.orientation),this._listboxNode.setAttribute(`aria-multiselectable`,`${this.multipleChoice}`),this._listboxNode.setAttribute(`tabindex`,`0`),this._listboxNode.addEventListener(`click`,this._listboxOnClick),this._listboxNode.addEventListener(`keyup`,this._listboxOnKeyUp),this._listboxNode.addEventListener(`keydown`,this._listboxOnKeyDown),this._scrollTargetNode.addEventListener(`keydown`,this.__preventScrollingWithArrowKeys)}__requestOptionsToBeDisabled(){this.formElements.forEach(e=>{e.makeRequestToBeDisabled&&e.makeRequestToBeDisabled()})}__retractRequestOptionsToBeDisabled(){this.formElements.forEach(e=>{e.retractRequestToBeDisabled&&e.retractRequestToBeDisabled()})}__initInteractionStates(){this.initInteractionState()}}),od=class extends ad(al(fu(uu(h)))){get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,focused:this.focused}}get _focusableNode(){return this._inputNode}},sd=class extends _s(bu($l(xs(h)))){static get properties(){return{active:{type:Boolean,reflect:!0}}}static get styles(){return[d`
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
      `]}get slots(){return{}}constructor(){super(),this.active=!1,this.__onClick=this.__onClick.bind(this),this.__registerEventListeners()}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`active`&&this.active!==t&&this.dispatchEvent(new Event(`active-changed`,{bubbles:!0}))}updated(e){super.updated(e),e.has(`checked`)&&this.setAttribute(`aria-selected`,`${this.checked}`),e.has(`disabled`)&&this.setAttribute(`aria-disabled`,`${this.disabled}`)}render(){return l`
      <div class="choice-field__label">
        <slot></slot>
      </div>
    `}connectedCallback(){super.connectedCallback(),this.setAttribute(`role`,`option`)}__registerEventListeners(){this.addEventListener(`click`,this.__onClick)}__unRegisterEventListeners(){this.removeEventListener(`click`,this.__onClick)}__onClick(){if(this.disabled)return;let e=this._parentFormGroup;this._isHandlingUserInput=!0,e&&e.multipleChoice?(this.checked=!this.checked,this.active=!this.active):(this.checked=!0,this.active=!0),this._isHandlingUserInput=!1}},cd=d`
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
`,ld=new WeakMap,ud=class extends sd{static get styles(){return[...sd.styles,cd]}constructor(){super(),this.hint=null,w(this,ld,640),E(ld,this,parseInt(getComputedStyle(this).getPropertyValue(`--c-option-wide-threshold`)||`640`,10))}connectedCallback(){super.connectedCallback();let e=this.getBoundingClientRect().width??0;this.toggleAttribute(`wide`,e>=D(ld,this))}render(){return l`
      <div class="choice-field__label">
        <slot></slot>
        ${this.hint?l`<span class="hint">${this.hint}</span>`:m}
        <slot name="suffix"></slot>
      </div>
    `}};o([g()],ud.prototype,`hint`,void 0),customElements.get(`craft-option`)||customElements.define(`craft-option`,ud);var dd=class extends pu{static get properties(){return{autocomplete:{type:String}}}constructor(){super(),this.autocomplete=void 0}get _inputNode(){return Array.from(this.children).find(e=>e.slot===`input`)}},fd=class extends dd{get operationMode(){return`select`}connectedCallback(){super.connectedCallback(),this._inputNode.addEventListener(`change`,this._proxyChangeEvent),this.__selectObserver=new MutationObserver(()=>{this._syncValueUpwards(),this._calculateValues({source:`model`})}),this.__selectObserver.observe(this._inputNode,{attributes:!0,childList:!0,subtree:!0})}updated(e){super.updated(e),e.has(`disabled`)&&(this._inputNode.disabled=this.disabled,this.validate()),e.has(`name`)&&(this._inputNode.name=this.name),e.has(`autocomplete`)&&(this._inputNode.autocomplete=this.autocomplete)}disconnectedCallback(){super.disconnectedCallback(),this._inputNode.removeEventListener(`change`,this._proxyChangeEvent),this.__selectObserver?.disconnect()}formatter(e){let t=Array.from(this._inputNode.options).find(t=>t.value===e);return t?t.text:``}_reflectBackFormattedValueToUser(){this._reflectBackOn()&&(this.value=this.modelValue===void 0?``:this.modelValue)}_proxyChangeEvent(){this.dispatchEvent(new CustomEvent(`user-input-changed`,{bubbles:!0,composed:!0}))}},pd=d`
  ${el}

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
    ${$c}
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
`,md=class extends fd{constructor(...e){super(...e),this.small=!1}static get styles(){return[...super.styles,pd]}_inputGroupInputTemplate(){return l`
      <div class="input-group__input">
        <slot name="input"></slot>
        <craft-icon
          class="indicator"
          name="chevron-down"
          style="font-size: 0.8em"
        ></craft-icon>
      </div>
    `}};o([g({reflect:!0,type:Boolean})],md.prototype,`small`,void 0),customElements.get(`craft-select`)||customElements.define(`craft-select`,md);var hd=class extends Event{constructor(e){super(`wa-select`,{bubbles:!0,cancelable:!0,composed:!0}),this.detail=e}},gd=d`
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
`,_d={small:`s`,medium:`m`,large:`l`},vd=new Set;function yd(e,t){t in _d&&!vd.has(`${e}:${t}`)&&(vd.add(`${e}:${t}`),console.warn(`[${e}] size="${t}" is deprecated. Use size="${_d[t]}" instead. The long-form value will be removed in the next major version.`))}var bd=d`
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
`;function*xd(e=document.activeElement){e!=null&&(yield e,`shadowRoot`in e&&e.shadowRoot&&e.shadowRoot.mode!==`closed`&&(yield*xd(e.shadowRoot.activeElement)))}var Sd=new Set,Cd=class extends Ri{constructor(){super(...arguments),this.submenuCleanups=new Map,this.localize=new ai(this),this.userTypedQuery=``,this.openSubmenuStack=[],this.open=!1,this.size=`m`,this.placement=`bottom-start`,this.distance=0,this.skidding=0,this.handleDocumentKeyDown=async e=>{let t=this.localize.dir()===`rtl`;if(e.key===`Escape`&&this.open&&is(this)){let t=this.getTrigger();e.preventDefault(),e.stopPropagation(),this.open=!1,t?.focus({preventScroll:!0});return}let n=[...xd()].find(e=>e.localName===`wa-dropdown-item`),r=n?.localName===`wa-dropdown-item`,i=this.getCurrentSubmenuItem(),a=!!i,o,s,c;a?(o=this.getSubmenuItems(i),s=o.find(e=>e.active||e===n),c=s?o.indexOf(s):-1):(o=this.getItems(),s=o.find(e=>e.active||e===n),c=s?o.indexOf(s):-1);let l;if(e.key===`ArrowUp`&&(e.preventDefault(),e.stopPropagation(),l=c>0?o[c-1]:o[o.length-1]),e.key===`ArrowDown`&&(e.preventDefault(),e.stopPropagation(),l=c!==-1&&c<o.length-1?o[c+1]:o[0]),e.key===(t?`ArrowLeft`:`ArrowRight`)&&r&&s&&s.hasSubmenu){e.preventDefault(),e.stopPropagation(),s.submenuOpen=!0,this.addToSubmenuStack(s),setTimeout(()=>{let e=this.getSubmenuItems(s);e.length>0&&(e.forEach((e,t)=>e.active=t===0),e[0].focus({preventScroll:!0}))},0);return}if(e.key===(t?`ArrowRight`:`ArrowLeft`)&&a){e.preventDefault(),e.stopPropagation();let t=this.removeFromSubmenuStack();t&&(t.submenuOpen=!1,setTimeout(()=>{t.focus({preventScroll:!0}),t.active=!0,(t.slot===`submenu`?this.getSubmenuItems(t.parentElement):this.getItems()).forEach(e=>{e!==t&&(e.active=!1)})},0));return}if((e.key===`Home`||e.key===`End`)&&(e.preventDefault(),e.stopPropagation(),l=e.key===`Home`?o[0]:o[o.length-1]),e.key===`Tab`&&await this.hideMenu(),e.key.length===1&&!(e.metaKey||e.ctrlKey||e.altKey)&&!(e.key===` `&&this.userTypedQuery===``)&&(clearTimeout(this.userTypedTimeout),this.userTypedTimeout=setTimeout(()=>{this.userTypedQuery=``},1e3),this.userTypedQuery+=e.key,o.some(e=>{let t=(e.textContent||``).trim().toLowerCase(),n=this.userTypedQuery.trim().toLowerCase();return t.startsWith(n)?(l=e,!0):!1})),l){e.preventDefault(),e.stopPropagation(),o.forEach(e=>e.active=e===l),l.focus({preventScroll:!0});return}(e.key===`Enter`||e.key===` `&&this.userTypedQuery===``)&&r&&s&&(e.preventDefault(),e.stopPropagation(),s.hasSubmenu?(s.submenuOpen=!0,this.addToSubmenuStack(s),setTimeout(()=>{let e=this.getSubmenuItems(s);e.length>0&&(e.forEach((e,t)=>e.active=t===0),e[0].focus({preventScroll:!0}))},0)):this.makeSelection(s))},this.handleDocumentPointerDown=e=>{e.composedPath().some(e=>e instanceof HTMLElement?e===this||e.closest(`wa-dropdown, [part="submenu"]`):!1)||(this.open=!1)},this.handleGlobalMouseMove=e=>{let t=this.getCurrentSubmenuItem();if(!t?.submenuOpen||!t.submenuElement)return;let n=t.submenuElement.getBoundingClientRect(),r=this.localize.dir()===`rtl`,i=r?n.right:n.left,a=r?Math.max(e.clientX,i):Math.min(e.clientX,i),o=Math.max(n.top,Math.min(e.clientY,n.bottom));t.submenuElement.style.setProperty(`--safe-triangle-cursor-x`,`${a}px`),t.submenuElement.style.setProperty(`--safe-triangle-cursor-y`,`${o}px`);let s=e.composedPath(),c=t.matches(`:hover`),l=!!t.submenuElement?.matches(`:hover`),u=c||!!s.find(e=>e===t),d=l||!!s.find(e=>e instanceof HTMLElement&&e.closest(`[part="submenu"]`)===t.submenuElement);!u&&!d&&setTimeout(()=>{!c&&!l&&(t.submenuOpen=!1)},100)}}handleSizeChange(){yd(this.localName,this.size)}disconnectedCallback(){super.disconnectedCallback(),clearInterval(this.userTypedTimeout),this.closeAllSubmenus(),this.submenuCleanups.forEach(e=>e()),this.submenuCleanups.clear(),document.removeEventListener(`mousemove`,this.handleGlobalMouseMove),document.removeEventListener(`keydown`,this.handleDocumentKeyDown),document.removeEventListener(`pointerdown`,this.handleDocumentPointerDown),rs(this)}firstUpdated(){this.syncAriaAttributes()}async updated(e){if(e.has(`open`)){let t=e.get(`open`);if(t===this.open||t===void 0&&this.open===!1)return;this.customStates.set(`open`,this.open),this.open?await this.showMenu():(this.closeAllSubmenus(),await this.hideMenu())}e.has(`size`)&&this.syncItemSizes()}getItems(e=!1){let t=(this.defaultSlot?.assignedElements({flatten:!0})??[]).filter(e=>e.localName===`wa-dropdown-item`);return e?t:t.filter(e=>!e.disabled)}getSubmenuItems(e,t=!1){let n=e.shadowRoot?.querySelector(`slot[name="submenu"]`)||e.querySelector(`slot[name="submenu"]`);if(!n)return[];let r=n.assignedElements({flatten:!0}).filter(e=>e.localName===`wa-dropdown-item`);return t?r:r.filter(e=>!e.disabled)}syncItemSizes(){(this.defaultSlot?.assignedElements({flatten:!0})??[]).filter(e=>e.localName===`wa-dropdown-item`).forEach(e=>e.size=this.size)}addToSubmenuStack(e){let t=this.openSubmenuStack.indexOf(e);t===-1?this.openSubmenuStack.push(e):this.openSubmenuStack=this.openSubmenuStack.slice(0,t+1)}removeFromSubmenuStack(){return this.openSubmenuStack.pop()}getCurrentSubmenuItem(){return this.openSubmenuStack.length>0?this.openSubmenuStack[this.openSubmenuStack.length-1]:void 0}closeAllSubmenus(){this.getItems(!0).forEach(e=>{e.submenuOpen=!1}),this.openSubmenuStack=[]}closeSiblingSubmenus(e){let t=e.closest(`wa-dropdown-item:not([slot="submenu"])`),n;n=t?this.getSubmenuItems(t,!0):this.getItems(!0),n.forEach(t=>{t!==e&&t.submenuOpen&&(t.submenuOpen=!1)}),this.openSubmenuStack.includes(e)||this.openSubmenuStack.push(e)}getTrigger(){return this.querySelector(`[slot="trigger"]`)}async showMenu(){if(!this.getTrigger()||!this.popup||!this.menu)return;let e=new as;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}if(this.popup.active)return;Sd.forEach(e=>e.open=!1),this.popup.active=!0,this.open=!0,Sd.add(this),ns(this),this.syncAriaAttributes(),document.addEventListener(`keydown`,this.handleDocumentKeyDown),document.addEventListener(`pointerdown`,this.handleDocumentPointerDown),document.addEventListener(`mousemove`,this.handleGlobalMouseMove),this.menu.classList.remove(`hide`),await ps(this.menu,`show`);let t=this.getItems();t.length>0&&(t.forEach((e,t)=>e.active=t===0),t[0].focus({preventScroll:!0})),this.dispatchEvent(new cs)}async hideMenu(){if(!this.popup||!this.menu)return;let e=new os({source:this});if(this.dispatchEvent(e),e.defaultPrevented){this.open=!0;return}this.open=!1,Sd.delete(this),rs(this),this.syncAriaAttributes(),document.removeEventListener(`keydown`,this.handleDocumentKeyDown),document.removeEventListener(`pointerdown`,this.handleDocumentPointerDown),document.removeEventListener(`mousemove`,this.handleGlobalMouseMove),this.menu.classList.remove(`show`),await ps(this.menu,`hide`),this.popup.active=this.open,this.dispatchEvent(new ss)}handleMenuClick(e){let t=e.target.closest(`wa-dropdown-item`);if(!(!t||t.disabled)){if(t.hasSubmenu){t.submenuOpen||=(this.closeSiblingSubmenus(t),this.addToSubmenuStack(t),!0),e.stopPropagation();return}this.makeSelection(t)}}async handleMenuSlotChange(){let e=this.getItems(!0);await Promise.all(e.map(e=>e.updateComplete)),this.syncItemSizes();let t=e.some(e=>e.type===`checkbox`),n=e.some(e=>e.hasSubmenu);e.forEach((e,r)=>{e.active=r===0,e.checkboxAdjacent=t,e.submenuAdjacent=n})}handleTriggerClick(){this.open=!this.open}handleSubmenuOpening(e){let t=e.detail.item;this.closeSiblingSubmenus(t),this.addToSubmenuStack(t),this.setupSubmenuPosition(t),this.processSubmenuItems(t)}setupSubmenuPosition(e){if(!e.submenuElement)return;this.cleanupSubmenuPosition(e);let t=Uo(e,e.submenuElement,()=>{this.positionSubmenu(e),this.updateSafeTriangleCoordinates(e)});this.submenuCleanups.set(e,t);let n=e.submenuElement.querySelector(`slot[name="submenu"]`);n&&(n.removeEventListener(`slotchange`,Cd.handleSubmenuSlotChange),n.addEventListener(`slotchange`,Cd.handleSubmenuSlotChange),Cd.handleSubmenuSlotChange({target:n}))}static handleSubmenuSlotChange(e){let t=e.target;if(!t)return;let n=t.assignedElements().filter(e=>e.localName===`wa-dropdown-item`);if(n.length===0)return;let r=n.some(e=>e.hasSubmenu),i=n.some(e=>e.type===`checkbox`);n.forEach(e=>{e.submenuAdjacent=r,e.checkboxAdjacent=i})}processSubmenuItems(e){if(!e.submenuElement)return;let t=this.getSubmenuItems(e,!0),n=t.some(e=>e.hasSubmenu);t.forEach(e=>{e.submenuAdjacent=n})}cleanupSubmenuPosition(e){let t=this.submenuCleanups.get(e);t&&(t(),this.submenuCleanups.delete(e))}positionSubmenu(e){if(!e.submenuElement)return;let t=this.localize.dir()===`rtl`?`left-start`:`right-start`;Yo(e,e.submenuElement,{placement:t,middleware:[Wo({mainAxis:0,crossAxis:-5}),Ko({fallbackStrategy:`bestFit`}),Go({padding:8})]}).then(({x:t,y:n,placement:r})=>{e.submenuElement.setAttribute(`data-placement`,r),Object.assign(e.submenuElement.style,{left:`${t}px`,top:`${n}px`})})}updateSafeTriangleCoordinates(e){if(!e.submenuElement||!e.submenuOpen)return;if(document.activeElement?.matches(`:focus-visible`)){e.submenuElement.style.setProperty(`--safe-triangle-visible`,`none`);return}e.submenuElement.style.setProperty(`--safe-triangle-visible`,`block`);let t=e.submenuElement.getBoundingClientRect(),n=this.localize.dir()===`rtl`;e.submenuElement.style.setProperty(`--safe-triangle-submenu-start-x`,`${n?t.right:t.left}px`),e.submenuElement.style.setProperty(`--safe-triangle-submenu-start-y`,`${t.top}px`),e.submenuElement.style.setProperty(`--safe-triangle-submenu-end-x`,`${n?t.right:t.left}px`),e.submenuElement.style.setProperty(`--safe-triangle-submenu-end-y`,`${t.bottom}px`)}makeSelection(e){let t=this.getTrigger();if(e.disabled)return;e.type===`checkbox`&&(e.checked=!e.checked);let n=new hd({item:e});this.dispatchEvent(n),n.defaultPrevented||(this.open=!1,t?.focus({preventScroll:!0}))}async syncAriaAttributes(){let e=this.getTrigger(),t;e&&(e.localName===`wa-button`?(await customElements.whenDefined(`wa-button`),await e.updateComplete,t=e.shadowRoot.querySelector(`[part="base"]`)):t=e,t.hasAttribute(`id`)||t.setAttribute(`id`,ds(`wa-dropdown-trigger-`)),t.setAttribute(`aria-haspopup`,`menu`),t.setAttribute(`aria-expanded`,this.open?`true`:`false`),this.menu?.setAttribute(`aria-expanded`,`false`))}render(){let e=this.hasUpdated?this.popup?.active:this.open;return l`
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
    `}};Cd.css=[bd,gd],N([y(`slot:not([name])`)],Cd.prototype,`defaultSlot`,2),N([y(`#menu`)],Cd.prototype,`menu`,2),N([y(`wa-popup`)],Cd.prototype,`popup`,2),N([g({type:Boolean,reflect:!0})],Cd.prototype,`open`,2),N([g({reflect:!0})],Cd.prototype,`size`,2),N([P(`size`)],Cd.prototype,`handleSizeChange`,1),N([g({reflect:!0})],Cd.prototype,`placement`,2),N([g({type:Number})],Cd.prototype,`distance`,2),N([g({type:Number})],Cd.prototype,`skidding`,2),Cd=N([v(`wa-dropdown`)],Cd);var wd=d`
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
`,Td=class{constructor(e,...t){this.slotNames=[],this.handleSlotChange=e=>{let t=e.target;(this.slotNames.includes(`[default]`)&&!t.name||t.name&&this.slotNames.includes(t.name))&&this.host.requestUpdate()},(this.host=e).addController(this),this.slotNames=t}hasDefaultSlot(){return this.host.childNodes?[...this.host.childNodes].some(e=>{if(e.nodeType===Node.TEXT_NODE&&e.textContent.trim()!==``)return!0;if(e.nodeType===Node.ELEMENT_NODE){let t=e;if(t.tagName.toLowerCase()===`wa-visually-hidden`)return!1;if(!t.hasAttribute(`slot`))return!0}return!1}):!1}hasNamedSlot(e){return this.host.querySelector?.(`:scope > [slot="${e}"]`)!==null}test(e){return e===`[default]`?this.hasDefaultSlot():this.hasNamedSlot(e)}hostConnected(){this.host.shadowRoot?.addEventListener?.(`slotchange`,this.handleSlotChange)}hostDisconnected(){this.host.shadowRoot?.removeEventListener?.(`slotchange`,this.handleSlotChange)}},q=class extends Ri{constructor(){super(...arguments),this.hasSlotController=new Td(this,`[default]`,`start`,`end`),this.active=!1,this.variant=`default`,this.size=`m`,this.checkboxAdjacent=!1,this.submenuAdjacent=!1,this.type=`normal`,this.checked=!1,this.disabled=!1,this.submenuOpen=!1,this.hasSubmenu=!1,this.handleSlotChange=()=>{this.hasSubmenu=this.hasSlotController.test(`submenu`),this.updateHasSubmenuState(),this.hasSubmenu?(this.setAttribute(`aria-haspopup`,`menu`),this.setAttribute(`aria-expanded`,this.submenuOpen?`true`:`false`)):(this.removeAttribute(`aria-haspopup`),this.removeAttribute(`aria-expanded`))},this.handleHostClick=e=>{this.disabled&&(e.preventDefault(),e.stopImmediatePropagation())},this.handleClick=e=>{this.disabled&&(e.preventDefault(),e.stopImmediatePropagation())}}handleSizeChange(){yd(this.localName,this.size)}connectedCallback(){super.connectedCallback(),this.addEventListener(`click`,this.handleHostClick),this.addEventListener(`mouseenter`,this.handleMouseEnter.bind(this)),this.shadowRoot.addEventListener(`click`,this.handleClick,{capture:!0}),this.shadowRoot.addEventListener(`slotchange`,this.handleSlotChange)}disconnectedCallback(){super.disconnectedCallback(),this.closeSubmenu(),this.removeEventListener(`click`,this.handleHostClick),this.removeEventListener(`mouseenter`,this.handleMouseEnter),this.shadowRoot.removeEventListener(`click`,this.handleClick,{capture:!0}),this.shadowRoot.removeEventListener(`slotchange`,this.handleSlotChange)}firstUpdated(){this.setAttribute(`tabindex`,`-1`),this.hasSubmenu=this.hasSlotController.test(`submenu`),this.updateHasSubmenuState()}updated(e){e.has(`active`)&&(this.setAttribute(`tabindex`,this.active?`0`:`-1`),this.customStates.set(`active`,this.active)),e.has(`checked`)&&(this.type===`checkbox`?this.setAttribute(`aria-checked`,this.checked?`true`:`false`):this.removeAttribute(`aria-checked`),this.customStates.set(`checked`,this.checked)),e.has(`disabled`)&&(this.setAttribute(`aria-disabled`,this.disabled?`true`:`false`),this.customStates.set(`disabled`,this.disabled)),e.has(`type`)&&(this.type===`checkbox`?(this.setAttribute(`role`,`menuitemcheckbox`),this.setAttribute(`aria-checked`,this.checked?`true`:`false`)):(this.setAttribute(`role`,`menuitem`),this.removeAttribute(`aria-checked`))),e.has(`submenuOpen`)&&(this.customStates.set(`submenu-open`,this.submenuOpen),this.submenuOpen?this.openSubmenu():this.closeSubmenu())}updateHasSubmenuState(){this.customStates.set(`has-submenu`,this.hasSubmenu)}async openSubmenu(){let e=this.submenuElement;!this.hasSubmenu||!e||!this.isConnected||(this.notifyParentOfOpening(),e.showPopover?.(),e.hidden=!1,e.setAttribute(`data-visible`,``),this.submenuOpen=!0,this.setAttribute(`aria-expanded`,`true`),await ps(e,`show`),setTimeout(()=>{let e=this.getSubmenuItems();e.length>0&&(e.forEach((e,t)=>e.active=t===0),e[0].focus({preventScroll:!0}))},0))}notifyParentOfOpening(){let e=new CustomEvent(`submenu-opening`,{bubbles:!0,composed:!0,detail:{item:this}});this.dispatchEvent(e);let t=this.parentElement;t&&[...t.children].filter(e=>e!==this&&e.localName===`wa-dropdown-item`&&e.getAttribute(`slot`)===this.getAttribute(`slot`)&&e.submenuOpen).forEach(e=>{e.submenuOpen=!1})}async closeSubmenu(){let e=this.submenuElement;!this.hasSubmenu||!e||(this.submenuOpen=!1,this.setAttribute(`aria-expanded`,`false`),e.hidden||(await ps(e,`hide`),e?.isConnected&&(e.hidden=!0,e.removeAttribute(`data-visible`),e.hidePopover?.())))}getSubmenuItems(){return[...this.children].filter(e=>e.localName===`wa-dropdown-item`&&e.getAttribute(`slot`)===`submenu`&&!e.hasAttribute(`disabled`))}handleMouseEnter(){this.hasSubmenu&&!this.disabled&&(this.notifyParentOfOpening(),this.submenuOpen=!0)}render(){return l`
      ${this.type===`checkbox`?l`
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

      ${this.hasSubmenu?l`
            <wa-icon
              id="submenu-indicator"
              part="submenu-icon"
              exportparts="svg:submenu-icon__svg"
              library="system"
              name="chevron-right"
            ></wa-icon>
          `:``}
      ${this.hasSubmenu?l`
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
    `}};q.css=wd,N([y(`#submenu`)],q.prototype,`submenuElement`,2),N([g({type:Boolean})],q.prototype,`active`,2),N([g({reflect:!0})],q.prototype,`variant`,2),N([g({reflect:!0})],q.prototype,`size`,2),N([P(`size`)],q.prototype,`handleSizeChange`,1),N([g({attribute:`checkbox-adjacent`,type:Boolean,reflect:!0})],q.prototype,`checkboxAdjacent`,2),N([g({attribute:`submenu-adjacent`,type:Boolean,reflect:!0})],q.prototype,`submenuAdjacent`,2),N([g()],q.prototype,`value`,2),N([g({reflect:!0})],q.prototype,`type`,2),N([g({type:Boolean})],q.prototype,`checked`,2),N([g({type:Boolean,reflect:!0})],q.prototype,`disabled`,2),N([g({type:Boolean,reflect:!0})],q.prototype,`submenuOpen`,2),N([_()],q.prototype,`hasSubmenu`,2),q=N([v(`wa-dropdown-item`)],q);var Ed=class extends Cd{static get styles(){return[Cd.styles,d`
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
      `]}},Dd=class extends q{static get styles(){return[q.styles,d`
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
      `]}};customElements.get(`craft-dropdown`)||customElements.define(`craft-dropdown`,Ed),customElements.get(`craft-dropdown-item`)||customElements.define(`craft-dropdown-item`,Dd);function Od({el:e,uid:t}){e.setAttribute(`id`,`panel-${t}`),e.setAttribute(`role`,`tabpanel`),e.setAttribute(`aria-labelledby`,`button-${t}`),e.hasAttribute(`tabindex`)||e.setAttribute(`tabindex`,`0`)}function kd(e){e.setAttribute(`selected`,`true`)}function Ad(e){e.removeAttribute(`selected`)}function jd({el:e,uid:t,clickHandler:n,keydownHandler:r,keyupHandler:i}){e.setAttribute(`id`,`button-${t}`),e.setAttribute(`role`,`tab`),e.setAttribute(`aria-controls`,`panel-${t}`),e.addEventListener(`click`,n),e.addEventListener(`keyup`,i),e.addEventListener(`keydown`,r)}function Md({el:e,clickHandler:t,keydownHandler:n,keyupHandler:r}){e.removeAttribute(`id`),e.removeAttribute(`role`),e.removeAttribute(`aria-controls`),e.removeEventListener(`click`,t),e.removeEventListener(`keyup`,r),e.removeEventListener(`keydown`,n)}function Nd(e,t=!1){t&&e.focus(),e.setAttribute(`selected`,`true`),e.setAttribute(`aria-selected`,`true`),e.setAttribute(`tabindex`,`0`)}function Pd(e){e.removeAttribute(`selected`),e.setAttribute(`aria-selected`,`false`),e.setAttribute(`tabindex`,`-1`)}function Fd(e){let t=e;switch(t.key){case`ArrowDown`:case`ArrowRight`:case`ArrowUp`:case`ArrowLeft`:case`Home`:case`End`:t.preventDefault()}}var Id=class extends h{static get properties(){return{selectedIndex:{type:Number,attribute:`selected-index`,reflect:!0}}}static get styles(){return[d`
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
      `]}render(){return l`
      <div class="tabs__tab-group" role="tablist">
        <slot name="tab"></slot>
      </div>
      <div class="tabs__panels">
        <slot name="panel"></slot>
      </div>
    `}constructor(){super(),this.selectedIndex=0}firstUpdated(e){super.firstUpdated(e),this.__setupSlots(),this.tabs[0]?.disabled&&(this.selectedIndex=this.tabs.findIndex(e=>!e.disabled))}get tabs(){return Array.from(this.children).filter(e=>e.slot===`tab`)}get panels(){return Array.from(this.children).filter(e=>e.slot===`panel`)}static enabledWarnings=super.enabledWarnings?.filter(e=>e!==`change-in-update`)||[];__setupSlots(){if(this.shadowRoot){let e=this.shadowRoot.querySelector(`slot[name=tab]`);e&&e.addEventListener(`slotchange`,()=>{this.__cleanStore(),this.__setupStore(),this.__updateSelected(!1)})}}__setupStore(){this.__store=[],this.tabs.length!==this.panels.length&&console.warn(`The amount of tabs (${this.tabs.length}) doesn't match the amount of panels (${this.panels.length}).`),this.tabs.forEach((e,t)=>{let n={uid:ws(),el:e,button:e,panel:this.panels[t],clickHandler:this.__createButtonClickHandler(t),keydownHandler:Fd.bind(this),keyupHandler:this.__handleButtonKeyup.bind(this)};Od({...n,el:n.panel}),jd(n),Ad(n.panel),Pd(n.button),this.__store&&this.__store.push(n)})}__cleanStore(){this.__store&&=(this.__store.forEach(e=>{Md(e)}),[])}__getNextNotDisabledTab(e,t,n){let r=[],i=e.filter((e,t)=>!e.disabled&&t>this.selectedIndex),a=e.filter((e,t)=>!e.disabled&&t<this.selectedIndex);return r=n===`right`?[...i,...a]:[...a.reverse(),...i.reverse()],r[0]}__getNextAvailableIndex(e,t){let n=this.tabs[this.selectedIndex];if(this.tabs.every(e=>!e.disabled))return e;if(t===`ArrowRight`||t===`ArrowDown`){let e=this.__getNextNotDisabledTab(this.tabs,n,`right`);return this.tabs.findIndex(t=>e===t)}if(t===`ArrowLeft`||t===`ArrowUp`){let e=this.__getNextNotDisabledTab(this.tabs,n,`left`);return this.tabs.findIndex(t=>e===t)}if(t===`Home`)return this.tabs.findIndex(e=>!e.disabled);if(t===`End`){let e=this.tabs.map((e,t)=>({disabled:e.disabled,index:t})).filter(e=>!e.disabled);return e[e.length-1].index}return-1}__createButtonClickHandler(e){return()=>{this._setSelectedIndexWithFocus(e)}}__handleButtonKeyup(e){let t=e;if(typeof this.selectedIndex==`number`)switch(t.key){case`ArrowDown`:case`ArrowRight`:this.selectedIndex+1>=this._pairCount?this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(0,t.key)):this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this.selectedIndex+1,t.key));break;case`ArrowUp`:case`ArrowLeft`:this.selectedIndex<=0?this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this._pairCount-1,t.key)):this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this.selectedIndex-1,t.key));break;case`Home`:this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(0,t.key));break;case`End`:this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this._pairCount-1,t.key));break}}get selectedIndex(){return this.__selectedIndex||0}set selectedIndex(e){if(e===this.__selectedIndex)return;let t=this.__selectedIndex;this.__selectedIndex=e,this.__updateSelected(!1),this.dispatchEvent(new Event(`selected-changed`)),this.requestUpdate(`selectedIndex`,t)}_setSelectedIndexWithFocus(e){if(e===-1)return;let t=this.__selectedIndex;this.__selectedIndex=e,this.__updateSelected(!0),this.dispatchEvent(new Event(`selected-changed`)),this.requestUpdate(`selectedIndex`,t)}get _pairCount(){return this.__store&&this.__store.length||0}__updateSelected(e=!1){if(!(this.__store&&typeof this.selectedIndex==`number`&&this.__store[this.selectedIndex]))return;let t=this.tabs.find(e=>e.hasAttribute(`selected`)),n=this.panels.find(e=>e.hasAttribute(`selected`));t&&Pd(t),n&&Ad(n);let{button:r,panel:i}=this.__store[this.selectedIndex];r&&Nd(r,e),i&&kd(i)}},Ld=d`
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
`,Rd=class extends Id{static get styles(){return[...super.styles,Ld]}};customElements.get(`craft-tabs`)||customElements.define(`craft-tabs`,Rd);var zd=d`
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
`,Bd=class extends h{constructor(...e){super(...e),this.label=``}render(){let e=!!this.label||!!this.querySelector(`[slot="header"]`)||!!this.querySelector(`[slot="label"]`)||!!this.querySelector(`[slot="actions"]`),t=!!this.querySelector(`[slot="footer"]`);return l`
      <div class="card">
        <div>
          ${e?l`<div class="card__header">
                <slot name="header">
                  <slot name="label" class="card__label" part="label"
                    >${this.label}</slot
                  >
                  <slot name="actions"></slot>
                </slot>
              </div>`:m}

          <div class="card__body">
            <slot></slot>
          </div>

          ${t?l`<div class="card__footer"><slot name="footer"></slot></div>`:m}
        </div>
      </div>
    `}};Bd.styles=[zd],o([g()],Bd.prototype,`label`,void 0),customElements.get(`craft-card`)||customElements.define(`craft-card`,Bd);var Vd=d`
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
`,Hd=class extends h{render(){return l`<slot></slot> `}};Hd.styles=[Vd],customElements.get(`craft-tab`)||customElements.define(`craft-tab`,Hd);var Ud=class extends vs(h){static get properties(){return{checked:{type:Boolean,reflect:!0}}}static get styles(){return[d`
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
      `]}render(){return l`
      <div class="btn">
        <div class="switch-button__track"></div>
        <div class="switch-button__thumb"></div>
      </div>
    `}constructor(){super(),this.value=``,this.checked=!1,this.__initialized=!1,this._toggleChecked=this._toggleChecked.bind(this),this.__handleKeydown=this._handleKeydown.bind(this),this.__handleKeyup=this._handleKeyup.bind(this)}connectedCallback(){super.connectedCallback(),this.setAttribute(`role`,`switch`),this.setAttribute(`aria-checked`,`${this.checked}`),this.addEventListener(`click`,this._toggleChecked),this.addEventListener(`keydown`,this.__handleKeydown),this.addEventListener(`keyup`,this.__handleKeyup)}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener(`click`,this._toggleChecked),this.removeEventListener(`keydown`,this.__handleKeydown),this.removeEventListener(`keyup`,this.__handleKeyup)}_toggleChecked(){this.disabled||(this.focus(),this.checked=!this.checked)}__checkedStateChange(){this.dispatchEvent(new Event(`checked-changed`,{bubbles:!0})),this.setAttribute(`aria-checked`,`${this.checked}`)}_handleKeydown(e){e.key===` `&&e.preventDefault()}_handleKeyup(e){[` `,`Enter`].includes(e.key)&&this._toggleChecked()}updated(e){super.updated(e),e.has(`disabled`)&&this.setAttribute(`aria-disabled`,`${this.disabled}`)}requestUpdate(e,t,n){super.requestUpdate(e,t,n),this.__initialized&&this.isConnected&&e===`checked`&&this.checked!==t&&!this.disabled&&this.__checkedStateChange()}firstUpdated(e){super.firstUpdated(e),this.__initialized=!0}},Wd=class extends Yl(bu(pu)){static get styles(){return[...super.styles,d`
        :host([hidden]) {
          display: none;
        }

        :host([disabled]) {
          color: #adadad;
        }
      `]}static get scopedElements(){return{...super.scopedElements,"lion-switch-button":Ud}}get _inputNode(){return Array.from(this.children).find(e=>e.slot===`input`)}get slots(){return{...super.slots,input:()=>{let e=this.createScopedElement(`lion-switch-button`);return e.setAttribute(`data-tag-name`,`lion-switch-button`),e}}}render(){return l`
      <div class="form-field__group-one">${this._groupOneTemplate()}</div>
      <div class="form-field__group-two">${this._groupTwoTemplate()}</div>
    `}_groupOneTemplate(){return l`${this._labelTemplate()} ${this._helpTextTemplate()} ${this._feedbackTemplate()}`}_groupTwoTemplate(){return l`${this._inputGroupTemplate()}`}constructor(){super(),this.checked=!1,this.__handleButtonSwitchCheckedChanged=this.__handleButtonSwitchCheckedChanged.bind(this)}connectedCallback(){super.connectedCallback(),this.addEventListener(`checked-changed`,this.__handleButtonSwitchCheckedChanged),this._labelNode&&this._labelNode.addEventListener(`click`,this._toggleChecked),this._syncButtonSwitch()}disconnectedCallback(){super.disconnectedCallback(),this._inputNode&&this.removeEventListener(`checked-changed`,this.__handleButtonSwitchCheckedChanged),this._labelNode&&this._labelNode.removeEventListener(`click`,this._toggleChecked)}updated(e){super.updated(e),e.has(`disabled`)&&this._syncButtonSwitch()}_toggleChecked(e){e.preventDefault(),super._toggleChecked(e)}_isEmpty(){return!1}__handleButtonSwitchCheckedChanged(e){e.stopPropagation(),this._isHandlingUserInput=!0,this.checked=this._inputNode.checked,this._isHandlingUserInput=!1}_syncButtonSwitch(){this._inputNode.disabled=this.disabled}_onLabelClick(){this.disabled||this._inputNode.focus()}},Gd=class extends Ud{static get styles(){return[...super.styles,d`
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
          background-color: var(--c-color-static-success-fill);
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
          background-color: var(--c-color-success-on-normal);
        }
      `]}};customElements.get(`craft-switch-button`)||customElements.define(`craft-switch-button`,Gd);var Kd=d`
  :host {
    display: grid;
  }

  .input-group {
    display: inline-flex;
  }

  ::slotted(label) {
    font-weight: bold;
  }
`,qd=class extends Wd{constructor(...e){super(...e),this.size=`medium`}static get styles(){return[...super.styles,el,Kd]}get slots(){return{...super.slots,input:()=>{let e=this.createScopedElement(`craft-switch-button`);return e.setAttribute(`size`,this.size),e.setAttribute(`data-tag-name`,`craft-switch-button`),e}}}static get scopedElements(){return{...super.scopedElements,"craft-switch-button":Gd}}};o([g({type:String,reflect:!0})],qd.prototype,`size`,void 0),customElements.get(`craft-switch`)||customElements.define(`craft-switch`,qd);var Jd=d`
  .breadcrumbs {
    display: flex;
    align-items: center;
  }
`,Yd=class extends h{constructor(...e){super(...e),this.label=n(`Breadcrumbs`),this.items=[],this.visibleItems=0,this.firstRender=!0}getSeparator(){let e=this.separatorSlot.assignedElements({flatten:!0})[0].cloneNode(!0);return[e,...e.querySelectorAll(`[id]`)].forEach(e=>e.removeAttribute(`id`)),e.setAttribute(`data-default`,``),e.slot=`separator`,e}calculateBreadcrumbItemsWidth(){this.items=this.breadcrumbsElements.map((e,t)=>{let n=e.offsetWidth;return e.hasAttribute(`hidden`)&&(e.removeAttribute(`hidden`),n=e.offsetWidth,e.setAttribute(`hidden`,``)),{label:e.innerText,href:e.href,value:t.toString(),offsetWidth:n,isVisible:!0}})}async handleSlotChange(){let e=[...this.defaultSlot.assignedElements({flatten:!0})].filter(e=>e.tagName.toLowerCase()===`craft-breadcrumb-item`);if(e.forEach((t,n)=>{let r=t.querySelector(`[slot="separator"]`);r===null?t.append(this.getSeparator()):r.hasAttribute(`data-default`)&&r.replaceWith(this.getSeparator()),n===e.length-1?t.setAttribute(`aria-current`,`page`):t.removeAttribute(`aria-current`)}),this.breadcrumbsElements.length===0){this.items=[],this.visibleItems=0;return}await Promise.all(this.breadcrumbsElements.map(e=>e.updateComplete)),this.calculateBreadcrumbItemsWidth(),this.visibleItems=0,this.adjustOverflow()}connectedCallback(){super.connectedCallback(),this.resizeObserver=new ResizeObserver(()=>{if(this.firstRender){this.firstRender=!1;return}this.adjustOverflow()}),this.resizeObserver.observe(this)}adjustOverflow(){let e=this.getBoundingClientRect().width;console.log({availableSpace:e})}disconnectedCallback(){this.resizeObserver?.unobserve(this),super.disconnectedCallback()}render(){return l`
      <nav class="breadcrumbs" aria-label="${this.label}">
        <slot @slotchange="${this.handleSlotChange}"></slot>
      </nav>

      <span hidden aria-hidden="true">
        <slot name="separator"><span class="separator">/</span></slot>
      </span>
    `}};Yd.styles=[Jd],o([y(`slot`)],Yd.prototype,`defaultSlot`,void 0),o([y(`slot[name="separator"]`)],Yd.prototype,`separatorSlot`,void 0),o([b({selector:`craft-breadcrumb-item`})],Yd.prototype,`breadcrumbsElements`,void 0),o([g()],Yd.prototype,`label`,void 0),o([_()],Yd.prototype,`items`,void 0),o([_()],Yd.prototype,`visibleItems`,void 0),customElements.get(`craft-breadcrumbs`)||customElements.define(`craft-breadcrumbs`,Yd);var Xd=d`
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
`,Zd=class extends Ri{constructor(){super(...arguments),this.renderType=`button`,this.rel=`noreferrer noopener`}setRenderType(){let e=this.defaultSlot.assignedElements({flatten:!0}).filter(e=>e.tagName.toLowerCase()===`wa-dropdown`).length>0;if(this.href){this.renderType=`link`;return}if(e){this.renderType=`dropdown`;return}this.renderType=`button`}hrefChanged(){this.setRenderType()}handleSlotChange(){this.setRenderType()}render(){return l`
      <span part="start" class="start">
        <slot name="start"></slot>
      </span>

      ${this.renderType===`link`?l`
            <a
              part="label"
              class="label label-link"
              href="${this.href}"
              target="${Eu(this.target?this.target:void 0)}"
              rel=${Eu(this.target?this.rel:void 0)}
            >
              <slot></slot>
            </a>
          `:``}
      ${this.renderType===`button`?l`
            <button part="label" type="button" class="label label-button">
              <slot @slotchange=${this.handleSlotChange}></slot>
            </button>
          `:``}
      ${this.renderType===`dropdown`?l`
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
    `}};Zd.css=Xd,N([y(`slot:not([name])`)],Zd.prototype,`defaultSlot`,2),N([_()],Zd.prototype,`renderType`,2),N([g()],Zd.prototype,`href`,2),N([g()],Zd.prototype,`target`,2),N([g()],Zd.prototype,`rel`,2),N([P(`href`,{waitUntilFirstUpdate:!0})],Zd.prototype,`hrefChanged`,1),Zd=N([v(`wa-breadcrumb-item`)],Zd);var Qd=class extends Zd{static get styles(){return[Zd.styles,d`
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
      `]}};customElements.get(`craft-breadcrumb-item`)||customElements.define(`craft-breadcrumb-item`,Qd);var $d=d`
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
`,ef=new Set,J=class extends Ri{constructor(){super(...arguments),this.anchor=null,this.placement=`top`,this.open=!1,this.distance=8,this.skidding=0,this.for=null,this.withoutArrow=!1,this.eventController=new AbortController,this.handleAnchorClick=()=>{this.open=!this.open},this.handleBodyClick=e=>{e.target.closest(`[data-popover="close"]`)&&(e.stopPropagation(),this.open=!1)},this.handleDocumentKeyDown=e=>{e.key===`Escape`&&this.open&&is(this)&&(e.preventDefault(),e.stopPropagation(),this.open=!1,this.anchor&&typeof this.anchor.focus==`function`&&this.anchor.focus())},this.handleDocumentClick=e=>{this.anchor&&e.composedPath().includes(this.anchor)||e.composedPath().includes(this)||(this.open=!1)}}connectedCallback(){super.connectedCallback(),this.id||=ds(`wa-popover-`),this.eventController.signal.aborted&&(this.eventController=new AbortController),this.for&&this.anchor&&(this.anchor=null,this.handleForChange())}disconnectedCallback(){super.disconnectedCallback(),document.removeEventListener(`keydown`,this.handleDocumentKeyDown),rs(this),this.eventController.abort()}firstUpdated(){this.open&&(this.dialog.show(),this.popup.active=!0,this.popup.reposition())}updated(e){e.has(`open`)&&this.customStates.set(`open`,this.open)}async handleOpenChange(){if(this.open){let e=new as;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}ef.forEach(e=>e.open=!1),document.addEventListener(`keydown`,this.handleDocumentKeyDown,{signal:this.eventController.signal}),document.addEventListener(`click`,this.handleDocumentClick,{signal:this.eventController.signal}),this.dialog.show(),this.popup.active=!0,ef.add(this),ns(this),requestAnimationFrame(()=>{let e=this.querySelector(`[autofocus]`);e&&typeof e.focus==`function`?e.focus():this.dialog.focus()}),await ps(this.popup.popup,`show-with-scale`),this.popup.reposition(),this.dispatchEvent(new cs)}else{let e=new os;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!0;return}document.removeEventListener(`keydown`,this.handleDocumentKeyDown),document.removeEventListener(`click`,this.handleDocumentClick),ef.delete(this),rs(this),await ps(this.popup.popup,`hide-with-scale`),this.popup.active=!1,this.dialog.close(),this.dispatchEvent(new ss)}}handleForChange(){let e=this.getRootNode();if(!e)return;let t=this.for?e.getElementById(this.for):null,n=this.anchor;if(t===n)return;let{signal:r}=this.eventController;t&&t.addEventListener(`click`,this.handleAnchorClick,{signal:r}),n&&n.removeEventListener(`click`,this.handleAnchorClick),this.anchor=t,this.for&&!t&&console.warn(`A popover was assigned to an element with an ID of "${this.for}" but the element could not be found.`,this)}async handleOptionsChange(){this.hasUpdated&&(await this.updateComplete,this.popup.reposition())}async show(){if(!this.open)return this.open=!0,fs(this,`wa-after-show`)}async hide(){if(this.open)return this.open=!1,fs(this,`wa-after-hide`)}render(){return l`
      <dialog part="dialog" class="dialog">
        <wa-popup
          part="popup"
          exportparts="
            popup:popup__popup,
            arrow:popup__arrow
          "
          class=${a({popover:!0,"popover-open":this.open})}
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
    `}};J.css=$d,J.dependencies={"wa-popup":I},N([y(`dialog`)],J.prototype,`dialog`,2),N([y(`.body`)],J.prototype,`body`,2),N([y(`wa-popup`)],J.prototype,`popup`,2),N([_()],J.prototype,`anchor`,2),N([g()],J.prototype,`placement`,2),N([g({type:Boolean,reflect:!0})],J.prototype,`open`,2),N([g({type:Number})],J.prototype,`distance`,2),N([g({type:Number})],J.prototype,`skidding`,2),N([g()],J.prototype,`for`,2),N([g({attribute:`without-arrow`,type:Boolean,reflect:!0})],J.prototype,`withoutArrow`,2),N([P(`open`,{waitUntilFirstUpdate:!0})],J.prototype,`handleOpenChange`,1),N([P(`for`)],J.prototype,`handleForChange`,1),N([P([`distance`,`placement`,`skidding`])],J.prototype,`handleOptionsChange`,1),J=N([v(`wa-popover`)],J);var tf=class extends J{static get styles(){return[J.styles,d`
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
      `]}};customElements.get(`craft-popover`)||customElements.define(`craft-popover`,tf);var nf=new Set;function rf(){let e=document.documentElement.clientWidth;return Math.abs(window.innerWidth-e)}function af(){let e=Number(getComputedStyle(document.body).paddingRight.replace(/px/,``));return isNaN(e)||!e?0:e}function of(e){if(nf.add(e),!document.documentElement.classList.contains(`wa-scroll-lock`)){let e=rf()+af(),t=getComputedStyle(document.documentElement).scrollbarGutter;(!t||t===`auto`)&&(t=`stable`),e<2&&(t=``),document.documentElement.style.setProperty(`--wa-scroll-lock-gutter`,t),document.documentElement.classList.add(`wa-scroll-lock`),document.documentElement.style.setProperty(`--wa-scroll-lock-size`,`${e}px`)}}function sf(e){nf.delete(e),nf.size===0&&(document.documentElement.classList.remove(`wa-scroll-lock`),document.documentElement.style.removeProperty(`--wa-scroll-lock-size`))}function cf(e){return e.split(` `).map(e=>e.trim()).filter(e=>e!==``)}var lf=d`
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
`,uf=class extends Ri{constructor(){super(...arguments),this.localize=new ai(this),this.hasSlotController=new Td(this,`footer`,`header-actions`,`label`),this.open=!1,this.label=``,this.placement=`end`,this.withoutHeader=!1,this.lightDismiss=!0,this.withFooter=!1,this.handleDocumentKeyDown=e=>{e.key===`Escape`&&this.open&&is(this)&&(e.preventDefault(),e.stopPropagation(),this.requestClose(this.drawer))}}firstUpdated(){this.open&&(this.addOpenListeners(),this.drawer.showModal(),of(this))}disconnectedCallback(){super.disconnectedCallback(),sf(this),this.removeOpenListeners()}async requestClose(e){let t=new os({source:e});if(this.dispatchEvent(t),t.defaultPrevented){this.open=!0,ps(this.drawer,`pulse`);return}this.removeOpenListeners(),await ps(this.drawer,`hide`),this.open=!1,this.drawer.close(),sf(this);let n=this.originalTrigger;typeof n?.focus==`function`&&setTimeout(()=>n.focus()),this.dispatchEvent(new ss)}addOpenListeners(){document.addEventListener(`keydown`,this.handleDocumentKeyDown),ns(this)}removeOpenListeners(){document.removeEventListener(`keydown`,this.handleDocumentKeyDown),rs(this)}handleDialogCancel(e){e.preventDefault(),!this.drawer.classList.contains(`hide`)&&e.target===this.drawer&&is(this)&&this.requestClose(this.drawer)}handleDialogClick(e){let t=e.target.closest(`[data-drawer="close"]`);t&&(e.stopPropagation(),this.requestClose(t))}async handleDialogPointerDown(e){e.target===this.drawer&&(this.lightDismiss?this.requestClose(this.drawer):await ps(this.drawer,`pulse`))}handleOpenChange(){this.open&&!this.drawer.open?this.show():this.drawer.open&&(this.open=!0,this.requestClose(this.drawer))}async show(){let e=new as;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}this.addOpenListeners(),this.originalTrigger=document.activeElement,this.open=!0,this.drawer.showModal(),of(this),requestAnimationFrame(()=>{let e=this.querySelector(`[autofocus]`);e&&typeof e.focus==`function`?e.focus():this.drawer.focus()}),await ps(this.drawer,`show`),this.dispatchEvent(new cs)}render(){let e=!this.withoutHeader,t=this.hasUpdated?this.hasSlotController.test(`footer`):this.withFooter;return l`
      <dialog
        part="dialog"
        class=${a({drawer:!0,open:this.open,top:this.placement===`top`,end:this.placement===`end`,bottom:this.placement===`bottom`,start:this.placement===`start`})}
        @cancel=${this.handleDialogCancel}
        @click=${this.handleDialogClick}
        @pointerdown=${this.handleDialogPointerDown}
      >
        ${e?l`
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

        ${t?l`
              <footer part="footer" class="footer">
                <slot name="footer"></slot>
              </footer>
            `:``}
      </dialog>
    `}};uf.css=lf,N([y(`.drawer`)],uf.prototype,`drawer`,2),N([g({type:Boolean,reflect:!0})],uf.prototype,`open`,2),N([g({reflect:!0})],uf.prototype,`label`,2),N([g({reflect:!0})],uf.prototype,`placement`,2),N([g({attribute:`without-header`,type:Boolean,reflect:!0})],uf.prototype,`withoutHeader`,2),N([g({attribute:`light-dismiss`,type:Boolean})],uf.prototype,`lightDismiss`,2),N([g({attribute:`with-footer`,type:Boolean})],uf.prototype,`withFooter`,2),N([P(`open`,{waitUntilFirstUpdate:!0})],uf.prototype,`handleOpenChange`,1),uf=N([v(`wa-drawer`)],uf),document.addEventListener(`click`,e=>{let t=e.target.closest(`[data-drawer]`);if(t instanceof Element){let[e,n]=cf(t.getAttribute(`data-drawer`)||``);if(e===`open`&&n?.length){let e=t.getRootNode().getElementById(n);e?.localName===`wa-drawer`?e.open=!0:console.warn(`A drawer with an ID of "${n}" could not be found in this document.`)}}}),document.addEventListener(`pointerdown`,()=>{});var df=()=>({checkValidity(e){let t=e.input,n={message:``,isValid:!0,invalidKeys:[]};if(!t)return n;let r=!0;if(`checkValidity`in t&&(r=t.checkValidity()),r)return n;if(n.isValid=!1,`validationMessage`in t&&(n.message=t.validationMessage),!(`validity`in t))return n.invalidKeys.push(`customError`),n;for(let e in t.validity){if(e===`valid`)continue;let r=e;t.validity[r]&&n.invalidKeys.push(r)}return n}}),ff=class extends Event{constructor(){super(`wa-invalid`,{bubbles:!0,cancelable:!1,composed:!0})}},pf=()=>({observedAttributes:[`custom-error`],checkValidity(e){let t={message:``,isValid:!0,invalidKeys:[]};return e.customError&&(t.message=e.customError,t.isValid=!1,t.invalidKeys=[`customError`]),t}}),mf=class extends Ri{constructor(){super(),this.name=null,this.disabled=!1,this.required=!1,this.assumeInteractionOn=[`input`],this.validators=[],this.valueHasChanged=!1,this.hasInteracted=!1,this.customError=null,this.emittedEvents=[],this.emitInvalid=e=>{e.target===this&&(this.hasInteracted=!0,this.dispatchEvent(new ff))},this.handleInteraction=e=>{let t=this.emittedEvents;t.includes(e.type)||t.push(e.type),t.length===this.assumeInteractionOn?.length&&(this.hasInteracted=!0)},this.addEventListener(`invalid`,this.emitInvalid)}static get validators(){return[pf()]}static get observedAttributes(){let e=new Set(super.observedAttributes||[]);for(let t of this.validators)if(t.observedAttributes)for(let n of t.observedAttributes)e.add(n);return[...e]}connectedCallback(){super.connectedCallback(),this.updateValidity(),this.assumeInteractionOn.forEach(e=>{this.addEventListener(e,this.handleInteraction)})}firstUpdated(...e){super.firstUpdated(...e),this.updateValidity()}willUpdate(e){if(e.has(`customError`)&&(this.customError||=null,this.setCustomValidity(this.customError||``)),e.has(`value`)||e.has(`disabled`)||e.has(`defaultValue`)){let e=this.value;if(Array.isArray(e)){if(this.name){let t=new FormData;for(let n of e)t.append(this.name,n);this.setValue(t,t)}}else this.setValue(e,e)}e.has(`disabled`)&&(this.customStates.set(`disabled`,this.disabled),(this.hasAttribute(`disabled`)||!this.matches(`:disabled`))&&this.toggleAttribute(`disabled`,this.disabled)),super.willUpdate(e),this.updateValidity()}get labels(){return this.internals.labels}getForm(){return this.internals.form}set form(e){e?this.setAttribute(`form`,e):this.removeAttribute(`form`)}get form(){return this.internals.form}get validity(){return this.internals.validity}get willValidate(){return this.internals.willValidate}get validationMessage(){return this.internals.validationMessage}checkValidity(){return this.updateValidity(),this.internals.checkValidity()}reportValidity(){return this.updateValidity(),this.hasInteracted=!0,this.internals.reportValidity()}get validationTarget(){return this.input||void 0}setValidity(...e){let t=e[0],n=e[1],r=e[2];r||=this.validationTarget,this.internals.setValidity(t,n,r||void 0),this.requestUpdate(`validity`),this.setCustomStates()}setCustomStates(){let e=!!this.required,t=this.internals.validity.valid,n=this.hasInteracted;this.customStates.set(`required`,e),this.customStates.set(`optional`,!e),this.customStates.set(`invalid`,!t),this.customStates.set(`valid`,t),this.customStates.set(`user-invalid`,!t&&n),this.customStates.set(`user-valid`,t&&n)}setCustomValidity(e){if(!e){this.customError=null,this.setValidity({});return}this.customError=e,this.setValidity({customError:!0},e,this.validationTarget)}formResetCallback(){this.resetValidity(),this.hasInteracted=!1,this.valueHasChanged=!1,this.emittedEvents=[],this.updateValidity()}formDisabledCallback(e){this.disabled=e,this.updateValidity()}formStateRestoreCallback(e,t){this.value=e,t===`restore`&&this.resetValidity(),this.updateValidity()}setValue(...e){let[t,n]=e;this.internals.setFormValue(t,n)}get allValidators(){let e=this.constructor.validators||[],t=this.validators||[];return[...e,...t]}resetValidity(){this.setCustomValidity(``),this.setValidity({})}updateValidity(){if(this.disabled||this.hasAttribute(`disabled`)||!this.willValidate){this.resetValidity();return}let e=this.allValidators;if(!e?.length)return;let t={customError:!!this.customError},n=this.validationTarget||this.input||void 0,r=``;for(let n of e){let{isValid:e,message:i,invalidKeys:a}=n.checkValidity(this);e||(r||=i,a?.length>=0&&a.forEach(e=>t[e]=!0))}r||=this.validationMessage,this.setValidity(t,r,n)}};mf.formAssociated=!0,N([g({reflect:!0})],mf.prototype,`name`,2),N([g({type:Boolean})],mf.prototype,`disabled`,2),N([g({state:!0,attribute:!1})],mf.prototype,`valueHasChanged`,2),N([g({state:!0,attribute:!1})],mf.prototype,`hasInteracted`,2),N([g({attribute:`custom-error`,reflect:!0})],mf.prototype,`customError`,2),N([g({attribute:!1,state:!0,type:Object})],mf.prototype,`validity`,1);var hf=d`
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
`,gf=d`
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
`,_f=Symbol.for(``),vf=e=>{if(e?.r===_f)return e?._$litStatic$},yf=(e,...t)=>({_$litStatic$:t.reduce(((t,n,r)=>t+(e=>{if(e._$litStatic$!==void 0)return e._$litStatic$;throw Error(`Value passed to 'literal' function must be a 'literal' result: ${e}. Use 'unsafeStatic' to pass non-literal values, but\n            take care to ensure page security.`)})(n)+e[r+1]),e[0]),r:_f}),bf=new Map,xf=(e=>(t,...n)=>{let r=n.length,i,a,o=[],s=[],c,l=0,u=!1;for(;l<r;){for(c=t[l];l<r&&(a=n[l],i=vf(a))!==void 0;)c+=i+t[++l],u=!0;l!==r&&s.push(a),o.push(c),l++}if(l===r&&o.push(t[r]),u){let e=o.join(`$$lit$$`);(t=bf.get(e))===void 0&&(o.raw=o,bf.set(e,t=o)),n=s}return e(t,...n)})(l),Y=class extends mf{constructor(){super(...arguments),this.assumeInteractionOn=[`click`],this.hasSlotController=new Td(this,`[default]`,`start`,`end`),this.localize=new ai(this),this.invalid=!1,this.isIconButton=!1,this.title=``,this.variant=`neutral`,this.appearance=`accent`,this.size=`m`,this.withCaret=!1,this.withStart=!1,this.withEnd=!1,this.disabled=!1,this.loading=!1,this.pill=!1,this.type=`button`}static get validators(){return[...super.validators,df()]}handleSizeChange(){yd(this.localName,this.size)}constructLightDOMButton(){let e=document.createElement(`button`);for(let t of this.attributes)t.name!==`style`&&e.setAttribute(t.name,t.value);return e.type=this.type,e.style.position=`absolute !important`,e.style.width=`0 !important`,e.style.height=`0 !important`,e.style.clipPath=`inset(50%) !important`,e.style.overflow=`hidden !important`,e.style.whiteSpace=`nowrap !important`,this.name&&(e.name=this.name),e.value=this.value||``,e}handleClick(e){if(this.disabled||this.loading){e.preventDefault(),e.stopImmediatePropagation();return}if(this.type!==`submit`&&this.type!==`reset`||!this.getForm())return;let t=this.constructLightDOMButton();this.parentElement?.append(t),t.click(),t.remove()}handleInvalid(){this.dispatchEvent(new ff)}handleLabelSlotChange(){let e=this.labelSlot.assignedNodes({flatten:!0}),t=!1,n=!1,r=!1,i=!1;[...e].forEach(e=>{if(e.nodeType===Node.ELEMENT_NODE){let r=e;r.localName===`wa-icon`?(n=!0,t||=r.label!==void 0):i=!0}else e.nodeType===Node.TEXT_NODE&&(e.textContent?.trim()||``).length>0&&(r=!0)}),this.isIconButton=n&&!r&&!i,this.customStates.set(`icon-button`,this.isIconButton),this.isIconButton&&!t&&console.warn(`Icon buttons must have a label for screen readers. Add <wa-icon label="..."> to remove this warning.`,this)}isButton(){return!this.href}isLink(){return!!this.href}handleDisabledChange(){this.customStates.set(`disabled`,this.disabled),this.updateValidity()}handleHrefChange(){this.customStates.set(`link`,this.isLink())}handleLoadingChange(){this.customStates.set(`loading`,this.loading)}setValue(...e){}click(){this.button.click()}focus(e){this.button.focus(e)}blur(){this.button.blur()}render(){let e=this.isLink(),t=e?yf`a`:yf`button`;return xf`
      <${t}
        part="base"
        class=${a({button:!0,caret:this.withCaret,disabled:this.disabled,loading:this.loading,rtl:this.localize.dir()===`rtl`,"has-label":this.hasSlotController.test(`[default]`),"has-start":this.hasUpdated?this.hasSlotController.test(`start`):this.withStart,"has-end":this.hasUpdated?this.hasSlotController.test(`end`):this.withEnd,"is-icon-button":this.isIconButton})}
        ?disabled=${Eu(e?void 0:this.disabled)}
        type=${Eu(e?void 0:this.type)}
        title=${this.title}
        name=${Eu(e?void 0:this.name)}
        value=${Eu(e?void 0:this.value)}
        href=${Eu(e?this.href:void 0)}
        target=${Eu(e?this.target:void 0)}
        download=${Eu(e?this.download:void 0)}
        rel=${Eu(e&&this.rel?this.rel:void 0)}
        role=${Eu(e?void 0:`button`)}
        aria-disabled=${Eu(e&&this.disabled?`true`:void 0)}
        tabindex=${this.disabled?`-1`:`0`}
        @invalid=${this.isButton()?this.handleInvalid:null}
        @click=${this.handleClick}
      >
        <slot name="start" part="start" class="start"></slot>
        <slot part="label" class="label" @slotchange=${this.handleLabelSlotChange}></slot>
        <slot name="end" part="end" class="end"></slot>
        ${this.withCaret?xf`
                <wa-icon part="caret" class="caret" library="system" name="chevron-down" variant="solid"></wa-icon>
              `:``}
        ${this.loading?xf`<wa-spinner part="spinner"></wa-spinner>`:``}
      </${t}>
    `}};Y.shadowRootOptions={...mf.shadowRootOptions,delegatesFocus:!0},Y.css=[gf,hf,bd],N([y(`.button`)],Y.prototype,`button`,2),N([y(`slot:not([name])`)],Y.prototype,`labelSlot`,2),N([_()],Y.prototype,`invalid`,2),N([_()],Y.prototype,`isIconButton`,2),N([g()],Y.prototype,`title`,2),N([g({reflect:!0})],Y.prototype,`variant`,2),N([g({reflect:!0})],Y.prototype,`appearance`,2),N([g({reflect:!0})],Y.prototype,`size`,2),N([P(`size`)],Y.prototype,`handleSizeChange`,1),N([g({attribute:`with-caret`,type:Boolean,reflect:!0})],Y.prototype,`withCaret`,2),N([g({attribute:`with-start`,type:Boolean})],Y.prototype,`withStart`,2),N([g({attribute:`with-end`,type:Boolean})],Y.prototype,`withEnd`,2),N([g({type:Boolean})],Y.prototype,`disabled`,2),N([g({type:Boolean,reflect:!0})],Y.prototype,`loading`,2),N([g({type:Boolean,reflect:!0})],Y.prototype,`pill`,2),N([g()],Y.prototype,`type`,2),N([g({reflect:!0})],Y.prototype,`name`,2),N([g({reflect:!0})],Y.prototype,`value`,2),N([g({reflect:!0})],Y.prototype,`href`,2),N([g()],Y.prototype,`target`,2),N([g()],Y.prototype,`rel`,2),N([g()],Y.prototype,`download`,2),N([g({attribute:`formaction`})],Y.prototype,`formAction`,2),N([g({attribute:`formenctype`})],Y.prototype,`formEnctype`,2),N([g({attribute:`formmethod`})],Y.prototype,`formMethod`,2),N([g({attribute:`formnovalidate`,type:Boolean})],Y.prototype,`formNoValidate`,2),N([g({attribute:`formtarget`})],Y.prototype,`formTarget`,2),N([P(`disabled`,{waitUntilFirstUpdate:!0})],Y.prototype,`handleDisabledChange`,1),N([P(`href`)],Y.prototype,`handleHrefChange`,1),N([P(`loading`,{waitUntilFirstUpdate:!0})],Y.prototype,`handleLoadingChange`,1),Y=N([v(`wa-button`)],Y),Y.disableWarning?.(`change-in-update`);var Sf=d`
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
`,Cf=class extends Ri{constructor(){super(...arguments),this.localize=new ai(this)}render(){return l`
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
    `}};Cf.css=Sf,Cf=N([v(`wa-spinner`)],Cf);var wf=class extends uf{static get styles(){return[uf.styles,d`
        :host {
          --wa-color-surface-raised: var(--c-surface-raised);
          --spacing: var(--c-spacing-lg);
        }
      `]}};customElements.get(`craft-drawer`)||customElements.define(`craft-drawer`,wf);var Tf=d`
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
`,Ef=class extends Ri{constructor(){super(...arguments),this.localize=new ai(this),this.hasSlotController=new Td(this,`footer`,`header-actions`,`label`),this.open=!1,this.label=``,this.withoutHeader=!1,this.lightDismiss=!1,this.withFooter=!1,this.handleDocumentKeyDown=e=>{e.key===`Escape`&&this.open&&is(this)&&(e.preventDefault(),e.stopPropagation(),this.requestClose(this.dialog))}}firstUpdated(){this.open&&(this.addOpenListeners(),this.dialog.showModal(),of(this))}disconnectedCallback(){super.disconnectedCallback(),sf(this),this.removeOpenListeners()}async requestClose(e){let t=new os({source:e});if(this.dispatchEvent(t),t.defaultPrevented){this.open=!0,ps(this.dialog,`pulse`);return}this.removeOpenListeners(),await ps(this.dialog,`hide`),this.open=!1,this.dialog.close(),sf(this);let n=this.originalTrigger;typeof n?.focus==`function`&&setTimeout(()=>n.focus()),this.dispatchEvent(new ss)}addOpenListeners(){document.addEventListener(`keydown`,this.handleDocumentKeyDown),ns(this)}removeOpenListeners(){document.removeEventListener(`keydown`,this.handleDocumentKeyDown),rs(this)}handleDialogCancel(e){e.preventDefault(),!this.dialog.classList.contains(`hide`)&&e.target===this.dialog&&is(this)&&this.requestClose(this.dialog)}handleDialogClick(e){let t=e.target.closest(`[data-dialog="close"]`);t&&(e.stopPropagation(),this.requestClose(t))}async handleDialogPointerDown(e){e.target===this.dialog&&(this.lightDismiss?this.requestClose(this.dialog):await ps(this.dialog,`pulse`))}handleOpenChange(){this.open&&!this.dialog.open?this.show():!this.open&&this.dialog.open&&(this.open=!0,this.requestClose(this.dialog))}async show(){let e=new as;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}this.addOpenListeners(),this.originalTrigger=document.activeElement,this.open=!0,this.dialog.showModal(),of(this),requestAnimationFrame(()=>{let e=this.querySelector(`[autofocus]`);e&&typeof e.focus==`function`?e.focus():this.dialog.focus()}),await ps(this.dialog,`show`),this.dispatchEvent(new cs)}render(){let e=!this.withoutHeader,t=this.hasUpdated?this.hasSlotController.test(`footer`):this.withFooter;return l`
      <dialog
        part="dialog"
        class=${a({dialog:!0,open:this.open})}
        @cancel=${this.handleDialogCancel}
        @click=${this.handleDialogClick}
        @pointerdown=${this.handleDialogPointerDown}
      >
        ${e?l`
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

        ${t?l`
              <footer part="footer" class="footer">
                <slot name="footer"></slot>
              </footer>
            `:``}
      </dialog>
    `}};Ef.css=Tf,N([y(`.dialog`)],Ef.prototype,`dialog`,2),N([g({type:Boolean,reflect:!0})],Ef.prototype,`open`,2),N([g({reflect:!0})],Ef.prototype,`label`,2),N([g({attribute:`without-header`,type:Boolean,reflect:!0})],Ef.prototype,`withoutHeader`,2),N([g({attribute:`light-dismiss`,type:Boolean})],Ef.prototype,`lightDismiss`,2),N([g({attribute:`with-footer`,type:Boolean})],Ef.prototype,`withFooter`,2),N([P(`open`,{waitUntilFirstUpdate:!0})],Ef.prototype,`handleOpenChange`,1),Ef=N([v(`wa-dialog`)],Ef),document.addEventListener(`click`,e=>{let t=e.target.closest(`[data-dialog]`);if(t instanceof Element){let[e,n]=cf(t.getAttribute(`data-dialog`)||``);if(e===`open`&&n?.length){let e=t.getRootNode().getElementById(n);e?.localName===`wa-dialog`?e.open=!0:console.warn(`A dialog with an ID of "${n}" could not be found in this document.`)}}}),document.addEventListener(`pointerdown`,()=>{});var Df=class extends Ef{static get styles(){return[Ef.styles,d`
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
      `]}};customElements.get(`craft-dialog`)||customElements.define(`craft-dialog`,Df);var Of=class extends vu(Su(h)){constructor(){super(),this.multipleChoice=!0}},kf=class extends bu(Cu){connectedCallback(){super.connectedCallback(),this.type=`checkbox`}},Af=class extends kf{static get styles(){return[...super.styles||[],d`
        :host .choice-field__nested-checkboxes {
          display: block;
        }
        ::slotted(*) {
          padding-left: 8px;
        }
      `]}static get properties(){return{indeterminate:{type:Boolean,reflect:!0},mixedState:{type:Boolean,reflect:!0,attribute:`mixed-state`}}}get _checkboxGroupNode(){return this._parentFormGroup}get _subCheckboxes(){return this.__subCheckboxes}_storeIndeterminateState(){this._indeterminateSubStates=this._subCheckboxes.map(e=>e.checked)}_setOldState(){this.indeterminate?this._oldState=`indeterminate`:this._oldState=this.checked?`checked`:`unchecked`}_setOwnCheckedState(){let e=this._subCheckboxes;if(!e.length)return;this.__settingOwnChecked=!0;let t=e.filter(e=>e.checked);switch(e.length-t.length){case 0:this.indeterminate=!1,this.checked=!0;break;case e.length:this.indeterminate=!1,this.checked=!1;break;default:{this.indeterminate=!0;let n=e.filter(e=>e.disabled&&e.checked===!1);this.checked=e.length-t.length-n.length===0}}this.updateComplete.then(()=>{this.__settingOwnChecked=!1})}_setBasedOnMixedState(){switch(this._oldState){case`checked`:this.checked=!1,this.indeterminate=!1;break;case`unchecked`:this.checked=!1,this.indeterminate=!0;break;case`indeterminate`:this.checked=!0,this.indeterminate=!1;break}}__onModelValueChanged(e){if(!this.disabled){if(e.detail.formPath[0]===this&&!this.__settingOwnChecked){this.mixedState&&!(e=>e.every(t=>t===e[0]))(this._indeterminateSubStates)&&this._setBasedOnMixedState(),this.__settingOwnSubs=!0;let e=this._subCheckboxes,t=e.filter(e=>e.checked),n=e.filter(e=>e.disabled),r=e.length>0&&e.length===t.length;e.length>0&&e.length===n.length&&(this.checked=r),this.indeterminate&&this.mixedState?this._subCheckboxes.forEach((e,t)=>{e.checked=this._indeterminateSubStates[t]}):this._subCheckboxes.filter(e=>!e.disabled).forEach(e=>{e.checked=this._inputNode.checked}),this.updateComplete.then(()=>{this.__settingOwnSubs=!1})}else this._setOwnCheckedState(),this.updateComplete.then(()=>{!this.__settingOwnSubs&&!this.__settingOwnChecked&&this.mixedState&&this._storeIndeterminateState()});this.mixedState&&this._setOldState()}}_afterTemplate(){return l`
      <div class="choice-field__nested-checkboxes" role="list">
        <slot></slot>
      </div>
    `}_onRequestToAddFormElement(e){e.target.hasAttribute(`role`)||e.target?.setAttribute(`role`,`listitem`),this.__addToSubCheckboxes(e.detail.element),this._setOwnCheckedState()}_onRequestToRemoveFormElement(e){e.target.getAttribute(`role`)===`listitem`&&e.target?.removeAttribute(`role`),this.__removeFromSubCheckboxes(e.detail.element)}__addToSubCheckboxes(e){e!==this&&this.contains(e)&&this.__subCheckboxes.push(e)}__removeFromSubCheckboxes(e){let t=this.__subCheckboxes.indexOf(e);t!==-1&&this.__subCheckboxes.splice(t,1)}constructor(){super(),this.indeterminate=!1,this._onRequestToAddFormElement=this._onRequestToAddFormElement.bind(this),this.__onModelValueChanged=this.__onModelValueChanged.bind(this),this.__subCheckboxes=[],this._indeterminateSubStates=[],this.mixedState=!1}connectedCallback(){super.connectedCallback(),this.addEventListener(`model-value-changed`,this.__onModelValueChanged),this.addEventListener(`form-element-register`,this._onRequestToAddFormElement)}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener(`model-value-changed`,this.__onModelValueChanged),this.removeEventListener(`form-element-register`,this._onRequestToAddFormElement)}firstUpdated(e){super.firstUpdated(e),this._setOldState(),this.indeterminate&&this._storeIndeterminateState()}updated(e){super.updated(e),(e.has(`indeterminate`)||e.has(`checked`))&&(this._inputNode.indeterminate=this.indeterminate)}},jf=class extends Of{static get styles(){return[...Of.styles,d`
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
      `]}};customElements.get(`craft-checkbox-group`)||customElements.define(`craft-checkbox-group`,jf);var Mf=class extends kf{static get styles(){return[...kf.styles,d`
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
      `]}};customElements.get(`craft-checkbox`)||customElements.define(`craft-checkbox`,Mf);var Nf=class extends Af{static get styles(){return[...Af.styles,d`
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
      `]}};customElements.get(`craft-checkbox-indeterminate`)||customElements.define(`craft-checkbox-indeterminate`,Nf);var Pf={Solid:`solid`,OutlineFill:`outline-fill`,Fill:`fill`,Outline:`outline`,Plain:`plain`};Object.values(Pf);var Ff={Neutral:`neutral`,Success:`success`,Warning:`warning`,Danger:`danger`,Info:`info`},If=Object.values(Ff),Lf=d`
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
`,Rf=d`
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

  :host([appearance~='solid']) {
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
`,zf=class extends h{constructor(...e){super(...e),this.variant=Ff.Neutral,this.appearance=Pf.OutlineFill,this.title=``,this.icon=null,this.rounded=`all`,this.inline=!1}getDefaultIcon(){switch(this.variant){case Ff.Info:return`lightbulb`;case Ff.Success:return`circle-check`;case Ff.Warning:return`circle-exclamation`;case Ff.Danger:return`triangle-exclamation`;default:return null}}render(){return l`
      ${this.icon||this.querySelector(`[slot="icon"]`)?l`<slot name="icon" class="callout__icon">
            <craft-icon
              name="${this.getDefaultIcon()}"
              style="font-size: 0.9em"
            ></craft-icon>
          </slot>`:m}
      <div class="callout__body">
        <slot name="title" class="callout__title">${this.title}</slot>
        <div class="callout__description">
          <slot></slot>
        </div>
      </div>
    `}};zf.styles=[Lf,Rf],o([g({reflect:!0})],zf.prototype,`variant`,void 0),o([g({reflect:!0})],zf.prototype,`appearance`,void 0),o([g()],zf.prototype,`title`,void 0),o([g()],zf.prototype,`icon`,void 0),o([g({reflect:!0})],zf.prototype,`rounded`,void 0),o([g({reflect:!0,type:Boolean})],zf.prototype,`inline`,void 0),customElements.get(`craft-callout`)||customElements.define(`craft-callout`,zf);var Bf=class extends h{constructor(){super(),this.alt=!1,this.shift=!1,this.os=`Unknown`,this.os=this.detectOS()}connectedCallback(){super.connectedCallback(),this.os===`Unknown`&&(this.os=this.detectOS())}detectOS(){let e=navigator.platform.toLowerCase();return e.includes(`mac`)||/iphone|ipad|ipod/.test(e)?`Mac`:e.includes(`win`)?`Windows`:e.includes(`linux`)?`Linux`:`Unknown`}renderShortcutPrefix(){switch(this.os){case`Mac`:return`${this.alt?`⌥`:``}${this.shift?`⇧`:``}⌘`;case`Linux`:return`Super+${this.alt?`Alt+`:``}${this.shift?`Shift+`:``}`;default:return`Ctrl+${this.alt?`Alt+`:``}${this.shift?`Shift+`:``}`}}render(){return l`<span class="shortcut"
      >${this.renderShortcutPrefix()}<slot></slot
    ></span>`}};Bf.styles=d`
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
  `,o([g({type:Boolean})],Bf.prototype,`alt`,void 0),o([g({type:Boolean})],Bf.prototype,`shift`,void 0),o([g()],Bf.prototype,`os`,void 0),customElements.get(`craft-shortcut`)||customElements.define(`craft-shortcut`,Bf);async function Vf(e){switch(e.type){case`clipboard`:await navigator.clipboard.writeText(e.value);break;case`http`:if(e.confirm&&!confirm(e.confirm))return;let t=await fetch(e.url,{method:e.method||`POST`,headers:{"Content-Type":`application/json`},body:e.body?JSON.stringify(e.body):void 0});if(!t.ok){let e=await t.json().catch(()=>({}));throw Error(e.message??`Request failed`)}break;case`event`:if(e.confirm&&!confirm(e.confirm))return;window.dispatchEvent(new CustomEvent(e.name,{detail:e.detail??{}}));break;case`download`:let n=document.createElement(`a`);n.href=e.url,n.download=e.filename??``,n.click();break;default:throw Error(`Unknown action type: ${e.type}`)}}var Hf=d`
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
`,Uf=class extends h{constructor(...e){super(...e),this.icon=null,this.href=null,this.disabled=!1,this.variant=Ff.Neutral,this.checked=!1,this.active=!1,this.type=`button`,this.action=null,this.feedback=null,this.feedbackDuration=1e3,this.confirm=null,this.state=S.Idle,this.feedbackMessage=null,this.shortcut=null}renderShortcut(){return typeof this.shortcut==`string`?l`<craft-shortcut>${this.shortcut}</craft-shortcut>`:this.shortcut===null?m:l`<craft-shortcut
        ?alt="${this.shortcut.alt??!1}"
        ?shift="${this.shortcut.shift??!1}"
        >${this.shortcut.key}</craft-shortcut
      >`}connectedCallback(){super.connectedCallback(),this.addEventListener(`click`,this)}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener(`click`,this)}setState(e,t={}){this.state=e,this.feedbackMessage=t.message??null,this.dispatchEvent(new CustomEvent(`action:change-state`,{bubbles:!0,composed:!0,detail:{state:e,actionType:this.action?.type,...t}}))}async handleEvent(e){if(this.disabled){e.preventDefault();return}if(e.type===`click`&&this.action){this.action.type===`http`&&this.setState(S.Loading);try{await Vf(this.action),this.setState(S.Success,this.feedback?.success)}catch(e){this.setState(S.Error,{message:e.message,...this.feedback?.error||{}})}finally{setTimeout(()=>{this.setState(S.Idle)},this.feedbackDuration)}}}renderCheckbox(){return l`<span class="action-item__check">
      <slot name="checkmark">
        ${this.checked?l`<craft-icon name="check"></craft-icon>`:m}
      </slot>
    </span>`}renderIcon(){switch(this.state){case S.Loading:return l`<craft-spinner style="--size: 0.8em"></craft-spinner>`;case S.Success:return l`<craft-icon
          name="check"
          style="color: var(--c-color-success-on-normal)"
        ></craft-icon>`;case S.Error:return l`<craft-icon
          name="xmark"
          style="color: var(--c-color-danger-on-normal)"
        ></craft-icon>`;default:return l`
          <slot name="icon">
            ${this.icon?l`<craft-icon name="${this.icon}"></craft-icon>`:m}
          </slot>
        `}}renderPrefix(){let e=!!this.querySelector(`[slot="icon"]`)||!!this.icon;return l`
      ${this.type===`checkbox`?this.renderCheckbox():m}
      ${e?l`<div class="action-item__icon">${this.renderIcon()}</div>`:m}
    `}renderBody(){return l`
      ${this.renderPrefix()}

      <span class="action-item__label">
        ${this.feedbackMessage?this.feedbackMessage:l`<slot></slot>`}
      </span>

      <span class="action-item__suffix">
        <slot name="suffix"></slot>
        ${this.shortcut?this.renderShortcut():m}
      </span>
    `}render(){return this.href?l`
          <a
            class="${a({"action-item":!0,"action-item--checkbox":this.type===`checkbox`})}"
            href="${this.href}"
          >
            ${this.renderBody()}
          </a>
        `:l`
          <button
            type="button"
            class="${a({"action-item":!0,"action-item--checkbox":this.type===`checkbox`})}"
            ?disabled="${this.disabled}"
          >
            ${this.renderBody()}
          </button>
        `}};Uf.styles=[Lf,Hf],o([g()],Uf.prototype,`icon`,void 0),o([g()],Uf.prototype,`href`,void 0),o([g({type:Boolean})],Uf.prototype,`disabled`,void 0),o([g({reflect:!0})],Uf.prototype,`variant`,void 0),o([g({type:Boolean})],Uf.prototype,`checked`,void 0),o([g({type:Boolean})],Uf.prototype,`active`,void 0),o([g()],Uf.prototype,`type`,void 0),o([g({type:Object})],Uf.prototype,`action`,void 0),o([g({type:Object})],Uf.prototype,`feedback`,void 0),o([g({type:Number})],Uf.prototype,`feedbackDuration`,void 0),o([g()],Uf.prototype,`confirm`,void 0),o([_()],Uf.prototype,`state`,void 0),o([_()],Uf.prototype,`feedbackMessage`,void 0),o([g({converter:{fromAttribute(e){if(e===null)return null;try{let t=JSON.parse(e);if(typeof t==`object`&&t)return t}catch{}return e},toAttribute(e){return e===null?null:typeof e==`string`?e:JSON.stringify(e)}}})],Uf.prototype,`shortcut`,void 0),customElements.get(`craft-action-item`)||customElements.define(`craft-action-item`,Uf);var Wf=d`
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
`,Gf=class e{static __createGlobalStyleNode(){let e=document.createElement(`style`);return e.setAttribute(`data-overlays`,``),e.textContent=Wf.cssText,document.head.appendChild(e),e}get list(){return this.__list}get shownList(){return this.__shownList}constructor(){this.__list=[],this.__shownList=[],this._siblingsInert=!1,this.__blockingMap=new WeakMap,e.__globalStyleNode||=e.__createGlobalStyleNode()}add(e){if(this.list.find(t=>e===t))throw Error(`controller instance is already added`);return this.list.push(e),e}remove(e){if(!this.list.find(t=>e===t))throw Error(`could not find controller to remove`);this.__list=this.list.filter(t=>t!==e),this.__shownList=this.shownList.filter(t=>t!==e)}show(e){this.list.find(t=>e===t)&&this.hide(e),this.__shownList.unshift(e),Array.from(this.__shownList).reverse().forEach((e,t)=>{e.elevation=t+1})}hide(e){if(!this.list.find(t=>e===t))throw Error(`could not find controller to hide`);this.__shownList=this.shownList.filter(t=>t!==e)}teardown(){this.list.forEach(e=>{e.teardown()}),this.__list=[],this.__shownList=[],this._siblingsInert=!1,e.__globalStyleNode&&=(document.head.removeChild(e.__globalStyleNode),void 0)}get siblingsInert(){return this._siblingsInert}requestToPreventScroll(){let{isIOS:e,isMacSafari:t}=Cs;document.body.classList.add(`overlays-scroll-lock`),(e||t)&&document.body.classList.add(`overlays-scroll-lock-ios-fix`),e&&document.documentElement.classList.add(`overlays-scroll-lock-ios-fix`)}requestToEnableScroll(e){if((e?this.shownList.filter(t=>t!==e):this.shownList).some(e=>e.preventsScroll===!0))return;let{isIOS:t,isMacSafari:n}=Cs;document.body.classList.remove(`overlays-scroll-lock`),(t||n)&&document.body.classList.remove(`overlays-scroll-lock-ios-fix`),t&&document.documentElement.classList.remove(`overlays-scroll-lock-ios-fix`)}requestToShowOnly(e){let t=this.shownList.filter(t=>t!==e);t.forEach(e=>e.hide()),this.__blockingMap.set(e,t)}retractRequestToShowOnly(e){this.__blockingMap.has(e)&&this.__blockingMap.get(e).forEach(e=>e.show())}};Gf.__globalStyleNode=void 0;function Kf(){if(!Ol.has(`@lion/ui::overlays::0.x`)){let e=new Gf;Ol.set(`@lion/ui::overlays::0.x`,e)}return Ol.get(`@lion/ui::overlays::0.x`)}var qf=Dl(Kf);function Jf(e,t,n={}){function r(e){return`getAttribute`in e}function i(e){if(!r(e))return null;let t=e.getAttribute(`slot`),i=null;if(t){let r=n[t];r&&(i=r.filter(t=>t?.element===e)[0]||null)}return i}let a=i(e);if(a)return a.deepContains;function o(t){if(!r(e))return;let i=e.getAttribute(`slot`);i&&(n[i]=n[i]||[],n[i].push({element:e,deepContains:t}))}let s=e.contains(t);if(s)return o(!0),!0;function c(e){return e.tagName===`SLOT`}function l(e){return c(e)?e.assignedElements():[]}function u(e){return e.nodeType===Node.DOCUMENT_FRAGMENT_NODE}function d(e){let i=!1;for(let a=0;a<e.length;a+=1){let o=e[a];if(o&&(r(o)||u(o))&&Jf(o,t,n)){i=!0;break}}return i}function f(e){for(let t=0;t<e.children.length;t+=1){let n=e.children[t],r=i(n);if(r){s=r.deepContains||s;break}let a=l(n);if(d([n.shadowRoot,...a])){s=!0;break}n.children.length>0&&f(n)}}return e instanceof HTMLElement&&e.shadowRoot&&(s=Jf(e.shadowRoot,t,n),s)?(o(!0),!0):(f(e),o(s),s)}var Yf=d`
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
`,Xf={supportsAdoptingStyleSheets:window.ShadowRoot&&(window.ShadyCSS===void 0||window.ShadyCSS.nativeShadow)&&`adoptedStyleSheets`in Document.prototype&&`replace`in CSSStyleSheet.prototype,adoptStyle:void 0,adoptStyles:void 0},Zf=new WeakMap;function Qf(e){return Array.from(e.cssRules).map(e=>e.cssText).join(``)}function $f(e,t,{teardown:n=!1}={}){let r=e===document?document.body:e,i=t.cssText||Qf(t);if(n){let e=Array.from(r.querySelectorAll(`style`));for(let t of e)if(t.textContent===i){t.remove();break}}else{let e=document.createElement(`style`),t=window.litNonce;t!==void 0&&e.setAttribute(`nonce`,t),e.textContent=i,r.appendChild(e)}}function ep(e,t,{teardown:n=!1}={}){let r=!1;e&&!Zf.has(e)&&Zf.set(e,[]);let i=Zf.get(e)??[],a=i.find(e=>t===e);return a&&n?i.splice(i.indexOf(t),1):!a&&!n?i.push(t):(a&&!n||!a&&n)&&(r=!0),{haltFurtherExecution:r}}function tp(e,t,{teardown:n=!1}={}){let{haltFurtherExecution:r}=ep(e,t,{teardown:n});if(r)return;if(!Xf.supportsAdoptingStyleSheets||Cs.isIOS){$f(e,t,{teardown:n});return}let i=t instanceof CSSStyleSheet?t:t.styleSheet;if(!i)throw Error(`Please provide a CSSResultOrNative style`);n?e.adoptedStyleSheets.includes(i)&&e.adoptedStyleSheets.splice(e.adoptedStyleSheets.indexOf(i),1):e.adoptedStyleSheets=[...e.adoptedStyleSheets,i]}function np(e,t,{teardown:n=!1}={}){for(let r of t)Xf.adoptStyle(e,r,{teardown:n})}Xf.adoptStyle=tp,Xf.adoptStyles=np;var rp=({visibility:e,display:t})=>e!==`hidden`&&t!==`none`,ip=({display:e})=>e===`contents`;function ap(e){if(!e||!e.isConnected||!rp(e.style))return!1;let t=window.getComputedStyle(e);return rp(t)?ip(t)?!0:!!(e.offsetWidth||e.offsetHeight||e.getClientRects().length):!1}function op(e,t){let n=Math.max(e.tabIndex,0),r=Math.max(t.tabIndex,0);return n===0||r===0?r>n:n>r}function sp(e,t){let n=[];for(;e.length>0&&t.length>0;)op(e[0],t[0])?n.push(t.shift()):n.push(e.shift());return[...n,...e,...t]}function cp(e){let t=e.length;if(t<2)return e;let n=Math.ceil(t/2);return sp(cp(e.slice(0,n)),cp(e.slice(n)))}var lp=`matches`in Element.prototype?`matches`:`msMatchesSelector`;function up(e){return e[lp](`input, select, textarea, button, object`)?e[lp](`:not([disabled])`):e[lp](`a[href], area[href], iframe, [tabindex], [contentEditable]`)}function dp(e){return up(e)?Number(e.getAttribute(`tabindex`)||0):-1}function fp(e){if(e.localName===`slot`)return e.assignedNodes({flatten:!0});let{children:t}=e.shadowRoot||e;return t||[]}function pp(e){return e.nodeType===Node.ELEMENT_NODE?e.localName===`slot`?!0:ap(e):!1}function mp(e,t){if(!pp(e))return!1;let n=e,r=dp(n),i=r>0;r>=0&&t.push(n);let a=fp(n);for(let e=0;e<a.length;e+=1)i=mp(a[e],t)||i;return i}function hp(e){let t=[];return mp(e,t)?cp(t):t}function gp({wrappingDialogNodeL1:e,contentWrapperNodeL2:t,contentNodeL3:n}){if(!(t.isConnected||n.isConnected))throw Error(`[OverlayController] Could not find a render target, since the provided contentNode is not connected to the DOM. Make sure that it is connected, e.g. by doing "document.body.appendChild(contentNode)", before passing it on.`);let r,i=document.createComment(`tempMarker`);t.isConnected?(r=t.parentElement||t.getRootNode(),r.insertBefore(i,t),e.appendChild(t)):n.assignedSlot?(r=n.assignedSlot.parentElement||n.assignedSlot.getRootNode(),r.insertBefore(i,n.assignedSlot),e.appendChild(t),t.appendChild(n.assignedSlot)):(r=n.parentElement||n.getRootNode(),r.insertBefore(i,n),e.appendChild(t),t.appendChild(n)),r.insertBefore(e,i),r?.removeChild(i)}async function _p(){return s(()=>import(`./popper-Ce_22Cdr.js`),[],import.meta.url)}var vp=new WeakMap,yp=class e extends EventTarget{#e=!1;constructor(e={},t=qf){super(),this.manager=t,this.__sharedConfig=e,this.__activeElementRightBeforeHide=null,this.config={},this._defaultConfig={placementMode:void 0,contentNode:e.contentNode,contentWrapperNode:e.contentWrapperNode,invokerNode:e.invokerNode,backdropNode:e.backdropNode,referenceNode:void 0,elementToFocusAfterHide:e.invokerNode,inheritsReferenceWidth:`none`,hasBackdrop:!1,isBlocking:!1,preventsScroll:!1,trapsKeyboardFocus:!1,hidesOnEsc:!1,hidesOnOutsideEsc:!1,hidesOnOutsideClick:!1,isTooltip:!1,isAlertDialog:!1,invokerRelation:`description`,visibilityTriggerFunction:void 0,handlesAccessibility:!1,popperConfig:{placement:`top`,strategy:`fixed`,modifiers:[{name:`preventOverflow`,enabled:!0,options:{boundariesElement:`viewport`,padding:8}},{name:`flip`,options:{boundariesElement:`viewport`,padding:16}},{name:`offset`,enabled:!0,options:{offset:[0,8]}},{name:`arrow`,enabled:!1}]},viewportConfig:{placement:`center`},zIndex:9999},this._contentId=`overlay-content--${Math.random().toString(36).slice(2,10)}`,this.__originalAttrs=new Map,this.__escKeyHandler=this.__escKeyHandler.bind(this),this.updateConfig(e),this.__hasActiveBackdrop=!0,this.__cancelHandler=this.__cancelHandler.bind(this),this.__escKeyHandlerCalled=!1}get invoker(){return this.invokerNode}get content(){return this.__wrappingDialogNode}get placementMode(){return this.config?.placementMode}get invokerNode(){return this.config?.invokerNode}get referenceNode(){return this.config?.referenceNode}get contentNode(){return this.config?.contentNode}get contentWrapperNode(){return this.__contentWrapperNode||this.config?.contentWrapperNode}get backdropNode(){return this.__backdropNode||this.config?.backdropNode}get elementToFocusAfterHide(){return this.__elementToFocusAfterHide||this.config?.elementToFocusAfterHide}get hasBackdrop(){return!!this.backdropNode||this.config?.hasBackdrop}get isBlocking(){return this.config?.isBlocking}get preventsScroll(){return this.config?.preventsScroll}get trapsKeyboardFocus(){return this.config?.trapsKeyboardFocus}get hidesOnEsc(){return this.config?.hidesOnEsc}get hidesOnOutsideClick(){return this.config?.hidesOnOutsideClick}get hidesOnOutsideEsc(){return this.config?.hidesOnOutsideEsc}get inheritsReferenceWidth(){return this.config?.inheritsReferenceWidth}get handlesAccessibility(){return this.config?.handlesAccessibility}get isTooltip(){return this.config?.isTooltip}get isAlertDialog(){return this.config?.isAlertDialog}get invokerRelation(){return this.config?.invokerRelation}get popperConfig(){return this.config?.popperConfig}get viewportConfig(){return this.config?.viewportConfig}get visibilityTriggerFunction(){return this.config?.visibilityTriggerFunction}get _referenceNode(){return this.referenceNode||this.invokerNode}set elevation(e){this.__wrappingDialogNode.style.zIndex=`${this.config.zIndex+e}`}get elevation(){return Number(this.contentWrapperNode?.style.zIndex)}updateConfig(e){this.teardown(),this.__prevConfig=this.config,this.config={...this._defaultConfig,...this.__sharedConfig,...e,popperConfig:{...this._defaultConfig.popperConfig||{},...this.__sharedConfig.popperConfig||{},...e.popperConfig||{},modifiers:[...this._defaultConfig.popperConfig?.modifiers||[],...this.__sharedConfig.popperConfig?.modifiers||[],...e.popperConfig?.modifiers||[]]}},this.__validateConfiguration(this.config),this._init(),this.__elementToFocusAfterHide=void 0,this.#t()||this.manager.add(this)}#t(){return!!this.manager.list.find(e=>this===e)}__validateConfiguration(e){if(!e.placementMode)throw Error(`[OverlayController] You need to provide a .placementMode ("global"|"local")`);if(![`global`,`local`].includes(e.placementMode))throw Error(`[OverlayController] "${e.placementMode}" is not a valid .placementMode, use ("global"|"local")`);if(!e.contentNode)throw Error(`[OverlayController] You need to provide a .contentNode`);if(e.isTooltip&&!e.handlesAccessibility)throw Error(`[OverlayController] .isTooltip only takes effect when .handlesAccessibility is enabled`)}_init(){this.__contentHasBeenInitialized||=(this.__initContentDomStructure(),!0),this.contentWrapperNode.removeAttribute(`style`),this.contentWrapperNode.removeAttribute(`class`),this.placementMode===`local`&&(e.popperModule||=_p()),this.__handleOverlayStyles({phase:`init`}),this._handleFeatures({phase:`init`})}__handleOverlayStyles({phase:e}){let t=this.contentWrapperNode?.getRootNode();e===`init`?Xf.adoptStyle(t,Yf):e===`teardown`&&Xf.adoptStyle(t,Yf,{teardown:!0})}__initContentDomStructure(){let e=document.createElement(`dialog`);e.setAttribute(`role`,`none`),e.setAttribute(`data-overlay-outer-wrapper`,``),e.style.cssText=`display:none; z-index: ${this.config.zIndex}; padding: 0;`,this.__wrappingDialogNode=e,this.config?.contentWrapperNode||(this.__contentWrapperNode=document.createElement(`div`)),this.contentWrapperNode.setAttribute(`data-id`,`content-wrapper`),gp({wrappingDialogNodeL1:e,contentWrapperNodeL2:this.contentWrapperNode,contentNodeL3:this.contentNode}),e.open=!0,this.isTooltip&&e.setAttribute(`tabindex`,`-1`),this.__wrappingDialogNode.style.display=`none`,this.contentWrapperNode.style.zIndex=`1`,getComputedStyle(this.contentNode).position===`absolute`&&(this.contentNode.style.position=`static`),HTMLDialogElement&&`closedBy`in HTMLDialogElement.prototype?e.closedBy=`none`:(e.addEventListener(`keydown`,e=>{e.key===`Escape`&&e.preventDefault()}),e.addEventListener(`keyup`,e=>{e.key===`Escape`&&e.preventDefault()}),e.addEventListener(`cancel`,e=>{e.stopPropagation()}),e.addEventListener(`close`,e=>{e.stopPropagation()}))}_handleZIndex({phase:e}){if(this.placementMode===`local`&&e===`setup`){let e=Number(getComputedStyle(this.contentNode).zIndex);(e<1||Number.isNaN(e))&&(this.contentNode.style.zIndex=`1`)}}__setupTeardownAccessibility({phase:e}){if(e===`init`){this.__storeOriginalAttrs(this.contentNode,[`role`,`id`]);let e=this.trapsKeyboardFocus;if(this.invokerNode){let t=[`aria-labelledby`,`aria-describedby`];e||t.push(`aria-expanded`),this.__storeOriginalAttrs(this.invokerNode,t)}this.contentNode.id||this.contentNode.setAttribute(`id`,this._contentId),this.isTooltip?(this.invokerNode&&this.invokerNode.setAttribute(this.invokerRelation===`label`?`aria-labelledby`:`aria-describedby`,this._contentId),this.contentNode.setAttribute(`role`,`tooltip`)):(this.invokerNode&&!e&&this.invokerNode.setAttribute(`aria-expanded`,`${this.isShown}`),this.isAlertDialog?this.contentNode.setAttribute(`role`,`alertdialog`):this.contentNode.getAttribute(`role`)||this.contentNode.setAttribute(`role`,`dialog`))}else e===`teardown`&&this.__restoreOriginalAttrs()}__storeOriginalAttrs(e,t){let n={};t.forEach(t=>{n[t]=e.getAttribute(t)}),this.__originalAttrs.set(e,n)}__restoreOriginalAttrs(){for(let[e,t]of this.__originalAttrs)Object.entries(t).forEach(([t,n])=>{n===null?e.removeAttribute(t):e.setAttribute(t,n)});this.__originalAttrs.clear()}get isShown(){return this.__wrappingDialogNode?.style.display!==`none`}async show(e=this.elementToFocusAfterHide){if(this._showComplete&&await this._showComplete,this._showComplete=new Promise(e=>{this._showResolve=e}),this.manager&&this.manager.show(this),this.isShown){this._showResolve();return}let t=new CustomEvent(`before-show`,{cancelable:!0});this.dispatchEvent(t),t.defaultPrevented||(`HTMLDialogElement`in window&&this.__wrappingDialogNode instanceof HTMLDialogElement&&(this.__wrappingDialogNode.open=!0),this.__wrappingDialogNode.style.display=``,this._keepBodySize({phase:`before-show`}),await this._handleFeatures({phase:`show`}),this._keepBodySize({phase:`show`}),await this._handlePosition({phase:`show`}),this.__elementToFocusAfterHide=e,this.dispatchEvent(new Event(`show`)),await this._transitionShow({backdropNode:this.backdropNode,contentNode:this.contentNode})),this._showResolve()}async _handlePosition({phase:e}){if(this.placementMode===`global`){let t=`overlays__overlay-container--${this.viewportConfig.placement}`;e===`show`?(this.contentWrapperNode.classList.add(`overlays__overlay-container`),this.contentWrapperNode.classList.add(t),this.contentNode.classList.add(`overlays__overlay`)):e===`hide`&&(this.contentWrapperNode.classList.remove(`overlays__overlay-container`),this.contentWrapperNode.classList.remove(t),this.contentNode.classList.remove(`overlays__overlay`))}else this.placementMode===`local`&&e===`show`&&(await this.__createPopperInstance(),this._popper.forceUpdate())}_keepBodySize({phase:e}){if(this.preventsScroll)switch(e){case`before-show`:this.__bodyClientWidth=document.body.clientWidth,this.__bodyClientHeight=document.body.clientHeight,this.__bodyMarginRightInline=document.body.style.marginRight,this.__bodyMarginBottomInline=document.body.style.marginBottom;break;case`show`:{if(window.getComputedStyle){let e=window.getComputedStyle(document.body);this.__bodyMarginRight=parseInt(e.getPropertyValue(`margin-right`),10),this.__bodyMarginBottom=parseInt(e.getPropertyValue(`margin-bottom`),10)}else this.__bodyMarginRight=0,this.__bodyMarginBottom=0;let e=document.body.clientWidth-this.__bodyClientWidth,t=document.body.clientHeight-this.__bodyClientHeight,n=this.__bodyMarginRight+e,r=this.__bodyMarginBottom+t;window.CSS?.number&&document.body.attributeStyleMap?.set?(document.body.attributeStyleMap.set(`margin-right`,CSS.px(n)),document.body.attributeStyleMap.set(`margin-bottom`,CSS.px(r))):(document.body.style.marginRight=`${n}px`,document.body.style.marginBottom=`${r}px`);break}case`hide`:document.body.style.marginRight=this.__bodyMarginRightInline||``,document.body.style.marginBottom=this.__bodyMarginBottomInline||``;break}}async hide(){if(this._hideComplete=new Promise(e=>{this._hideResolve=e}),this.__activeElementRightBeforeHide=this.contentNode.getRootNode().activeElement,this.manager&&this.#t()&&this.manager.hide(this),!this.isShown){this._hideResolve();return}let e=new CustomEvent(`before-hide`,{cancelable:!0});this.dispatchEvent(e),e.defaultPrevented||(await this._transitionHide({backdropNode:this.backdropNode,contentNode:this.contentNode}),`HTMLDialogElement`in window&&this.__wrappingDialogNode instanceof HTMLDialogElement&&this.__wrappingDialogNode.close(),this.__wrappingDialogNode.style.display=`none`,this._handleFeatures({phase:`hide`}),this._keepBodySize({phase:`hide`}),this.dispatchEvent(new Event(`hide`)),this._restoreFocus()),this._hideResolve()}async transitionHide(e){}async _transitionHide({backdropNode:e,contentNode:t}){await this.transitionHide({backdropNode:e,contentNode:t}),this._handlePosition({phase:`hide`}),e&&e.classList.remove(`overlays__backdrop--animation-in`)}async transitionShow(e){}async _transitionShow(e){await this.transitionShow({backdropNode:this.backdropNode,contentNode:this.contentNode}),e.backdropNode&&e.backdropNode.classList.add(`overlays__backdrop--animation-in`)}_restoreFocus(){this.__activeElementRightBeforeHide instanceof HTMLElement&&this.contentNode.contains(this.__activeElementRightBeforeHide)&&(this.elementToFocusAfterHide instanceof HTMLElement?(this.elementToFocusAfterHide.focus(),this.elementToFocusAfterHide.scrollIntoView({block:`nearest`})):this.__activeElementRightBeforeHide.blur())}async toggle(){return this.isShown?this.hide():this.show()}_handleFeatures({phase:e}){this._handleZIndex({phase:e}),this.preventsScroll&&this._handlePreventsScroll({phase:e}),this.isBlocking&&this._handleBlocking({phase:e}),this.hasBackdrop&&this._handleBackdrop({phase:e}),this.trapsKeyboardFocus&&this._handleTrapsKeyboardFocus({phase:e}),this.hidesOnEsc&&this._handleHidesOnEsc({phase:e}),this.hidesOnOutsideEsc&&this._handleHidesOnOutsideEsc({phase:e}),this.hidesOnOutsideClick&&this._handleHidesOnOutsideClick({phase:e}),this.handlesAccessibility&&this._handleAccessibility({phase:e}),this.inheritsReferenceWidth&&this._handleInheritsReferenceWidth(),this.visibilityTriggerFunction&&this._handleVisibilityTriggers({phase:e})}_handleVisibilityTriggers({phase:e}){typeof this.visibilityTriggerFunction==`function`&&(e===`init`&&(this.__visibilityTriggerHandler=this.visibilityTriggerFunction({phase:e,controller:this})),this.__visibilityTriggerHandler[e]&&this.__visibilityTriggerHandler[e]())}_handlePreventsScroll({phase:e}){switch(e){case`show`:this.manager.requestToPreventScroll();break;case`hide`:this.manager.requestToEnableScroll();break;case`teardown`:this.manager.requestToEnableScroll(this);break}}_handleBlocking({phase:e}){switch(e){case`show`:this.manager.requestToShowOnly(this);break;case`hide`:this.manager.retractRequestToShowOnly(this);break}}get hasActiveBackdrop(){return this.__hasActiveBackdrop}_handleBackdrop({phase:e}){switch(e){case`init`:this.__backdropInitialized||=(this.config?.backdropNode||(this.__backdropNode=document.createElement(`div`),this.__backdropNode.classList.add(`overlays__backdrop`)),this.__wrappingDialogNode.prepend(this.backdropNode),!0);break;case`show`:this.config.hasBackdrop&&this.backdropNode.classList.add(`overlays__backdrop--visible`),this.__hasActiveBackdrop=!0;break;case`hide`:case`teardown`:this.backdropNode.classList.remove(`overlays__backdrop--visible`),this.__hasActiveBackdrop=!1;break}}#n=e=>{e.key===`Shift`&&(this.#e=!0)};#r=e=>{e.key===`Shift`&&(this.#e=!1)};#i=()=>{window.addEventListener(`keydown`,this.#n),window.addEventListener(`keyup`,this.#r)};#a=()=>{window.removeEventListener(`keydown`,this.#n),window.removeEventListener(`keyup`,this.#r)};#o=()=>hp(this.contentNode).find(e=>e.hasAttribute(`autofocus`))||this.contentNode;#s=()=>{this.__wrappingDialogNode?.addEventListener(`focus`,()=>{this.#e||this.#o().focus()})};_handleTrapsKeyboardFocus({phase:e}){e===`init`&&(this.contentNode.style.outline=`none`,this.contentNode.tabIndex=-1,this.contentNode.shadowRoot&&console.warn(`[overlays]: For best accessibility (compatibility with Safari + VoiceOver), provide a contentNode that is not a host for a shadow root`)),e===`show`&&(this.#i(),this.#s(),this.__wrappingDialogNode?.close(),this.__wrappingDialogNode?.showModal(),this.#o().focus()),e===`hide`&&this.#a()}__cancelHandler(e){e.preventDefault()}__escKeyHandler(e){e.key!==`Escape`||vp.has(e)||!this.isShown&&this.__escKeyHandlerCalled||this.#c(e)&&(this.__escKeyHandlerCalled=!0,this.hide(),vp.set(e,this))}#c=e=>e.composedPath().includes(this.__wrappingDialogNode)||this.invokerNode&&e.composedPath().includes(this.invokerNode)||Jf(this.contentNode,e.target);#l=e=>{e.key===`Escape`&&(this.#c(e)||this.hide())};_handleHidesOnEsc({phase:e}){e===`init`&&(this.contentNode.removeEventListener(`keyup`,this.__escKeyHandler),this.contentNode.addEventListener(`keyup`,this.__escKeyHandler),this.invokerNode&&this.invokerNode.addEventListener(`keyup`,this.__escKeyHandler)),e===`show`&&(this.__escKeyHandlerCalled=!1),e===`teardown`&&(this.contentNode.removeEventListener(`keyup`,this.__escKeyHandler),this.invokerNode&&this.invokerNode.removeEventListener(`keyup`,this.__escKeyHandler))}_handleHidesOnOutsideEsc({phase:e}){e===`init`?(document.removeEventListener(`keyup`,this.#l),document.addEventListener(`keyup`,this.#l)):e===`teardown`&&document.removeEventListener(`keyup`,this.#l)}_handleInheritsReferenceWidth(){if(!this._referenceNode||this.placementMode===`global`)return;let e=`${this._referenceNode.getBoundingClientRect().width}px`;switch(this.inheritsReferenceWidth){case`max`:this.contentWrapperNode.style.maxWidth=e;break;case`full`:this.contentWrapperNode.style.width=e;break;case`min`:this.contentWrapperNode.style.minWidth=e,this.contentWrapperNode.style.width=`auto`;break}}_handleHidesOnOutsideClick({phase:e}){let t=e===`show`?`addEventListener`:`removeEventListener`;if(e===`show`){let e=!1,t=!1;this.__onInsideMouseDown=()=>{e=!0},this.__onInsideMouseUp=()=>{t=!0},this.__onDocumentMouseUp=()=>{setTimeout(()=>{!e&&!t&&this.hide(),e=!1,t=!1})},this.__onWindowBlur=()=>{setTimeout(()=>{this.hide()})}}this.contentWrapperNode[t](`mousedown`,this.__onInsideMouseDown,!0),this.contentWrapperNode[t](`mouseup`,this.__onInsideMouseUp,!0),this.invokerNode&&(this.invokerNode[t](`mousedown`,this.__onInsideMouseDown,!0),this.invokerNode[t](`mouseup`,this.__onInsideMouseUp,!0)),document.documentElement[t](`mouseup`,this.__onDocumentMouseUp,!0),window[t](`blur`,this.__onWindowBlur)}_handleAccessibility({phase:e}){(e===`init`||e===`teardown`)&&this.__setupTeardownAccessibility({phase:e});let t=this.trapsKeyboardFocus;this.invokerNode&&!this.isTooltip&&!t&&this.invokerNode.setAttribute(`aria-expanded`,`${e===`show`}`)}teardown(){this.__handleOverlayStyles({phase:`teardown`}),this._handleFeatures({phase:`teardown`}),this.#t()&&this.manager.remove(this)}async __createPopperInstance(){if(this._popper&&=(this._popper.destroy(),void 0),e.popperModule!==void 0){let{createPopper:t}=await e.popperModule;this._popper=t(this._referenceNode,this.contentWrapperNode,{...this.config?.popperConfig})}}_hasDisabledInvoker(){return this.invokerNode?this.invokerNode.disabled||this.invokerNode.getAttribute(`aria-disabled`)===`true`:!1}};yp.popperModule=void 0;function bp(e,t){if(typeof e!=`object`||typeof t!=`object`||e===null||t===null)return e===t;let n=Object.keys(e),r=Object.keys(t);return n.length===r.length?n.every(n=>bp(e[n],t[n])):!1}var xp=R(e=>class extends e{static get properties(){return{opened:{type:Boolean,reflect:!0}}}#e=!1;constructor(){super(),this.opened=!1,this.config={},this.toggle=this.toggle.bind(this),this.open=this.open.bind(this),this.close=this.close.bind(this)}get config(){return this.__config}set config(e){let t=!bp(this.config,e);this._overlayCtrl&&t&&this._overlayCtrl.updateConfig(e),this.__config=e,this._overlayCtrl&&t&&this.__syncToOverlayController()}requestUpdate(e,t,n){super.requestUpdate(e,t,n),e===`opened`&&this.opened!==t&&this.dispatchEvent(new CustomEvent(`opened-changed`,{detail:{opened:this.opened}}))}_defineOverlay({contentNode:e,invokerNode:t,referenceNode:n,backdropNode:r,contentWrapperNode:i}){let a=this._defineOverlayConfig()||{};return new yp({contentNode:e,invokerNode:t,referenceNode:n,backdropNode:r,contentWrapperNode:i,...a,...this.config,popperConfig:{...a.popperConfig||{},...this.config?.popperConfig||{},modifiers:[...a.popperConfig?.modifiers||[],...this.config?.popperConfig?.modifiers||[]]}})}_defineOverlayConfig(){return{placementMode:`local`}}updated(e){super.updated(e),e.has(`opened`)&&this._overlayCtrl&&!this.__blockSyncToOverlayCtrl&&this.__syncToOverlayController()}_setupOpenCloseListeners(){this.__closeEventInContentNodeHandler=e=>{e.stopPropagation(),this._overlayCtrl.hide()},this._overlayContentNode&&this._overlayContentNode.addEventListener(`close-overlay`,this.__closeEventInContentNodeHandler)}_teardownOpenCloseListeners(){this._overlayContentNode&&this._overlayContentNode.removeEventListener(`close-overlay`,this.__closeEventInContentNodeHandler)}connectedCallback(){super.connectedCallback(),this.updateComplete.then(()=>{this.isConnected&&(this.#e||=(this._setupOverlayCtrl(),!0))})}async disconnectedCallback(){super.disconnectedCallback(),await this._isPermanentlyDisconnected()&&(this._teardownOverlayCtrl(),this.#e=!1)}static enabledWarnings=super.enabledWarnings?.filter(e=>e!==`change-in-update`)||[];get _overlayInvokerNode(){return Array.from(this.children).find(e=>e.slot===`invoker`)}get _overlayReferenceNode(){}get _overlayBackdropNode(){return this.__cachedOverlayBackdropNode||=Array.from(this.children).find(e=>e.slot===`backdrop`),this.__cachedOverlayBackdropNode}get _overlayContentNode(){return this._cachedOverlayContentNode||=Array.from(this.children).find(e=>e.slot===`content`)||this.config.contentNode,this._cachedOverlayContentNode}get _overlayContentWrapperNode(){return this.shadowRoot?.querySelector(`#overlay-content-node-wrapper`)}_setupOverlayCtrl(){if(this.#e)return;let e={contentNode:this._overlayContentNode,contentWrapperNode:this._overlayContentWrapperNode,invokerNode:this._overlayInvokerNode,referenceNode:this._overlayReferenceNode,backdropNode:this._overlayBackdropNode};this._overlayCtrl?this._overlayCtrl.updateConfig(e):this._overlayCtrl=this._defineOverlay(e),this.__syncToOverlayController(),this.__setupSyncFromOverlayController(),this._setupOpenCloseListeners()}_teardownOverlayCtrl(){this._overlayCtrl&&(this._teardownOpenCloseListeners(),this.__teardownSyncFromOverlayController(),this._overlayCtrl.teardown())}async _setOpenedWithoutPropertyEffects(e){this.__blockSyncToOverlayCtrl=!0,this.opened=e,await this.updateComplete,this.__blockSyncToOverlayCtrl=!1}__setupSyncFromOverlayController(){this.__onOverlayCtrlShow=()=>{this.opened=!0},this.__onOverlayCtrlHide=()=>{this.opened=!1},this.__onBeforeShow=e=>{let t=new CustomEvent(`before-opened`,{cancelable:!0});this.dispatchEvent(t),t.defaultPrevented&&(this._setOpenedWithoutPropertyEffects(this._overlayCtrl.isShown),e.preventDefault())},this.__onBeforeHide=e=>{let t=new CustomEvent(`before-closed`,{cancelable:!0});this.dispatchEvent(t),t.defaultPrevented&&(this._setOpenedWithoutPropertyEffects(this._overlayCtrl.isShown),e.preventDefault())},this._overlayCtrl.addEventListener(`show`,this.__onOverlayCtrlShow),this._overlayCtrl.addEventListener(`hide`,this.__onOverlayCtrlHide),this._overlayCtrl.addEventListener(`before-show`,this.__onBeforeShow),this._overlayCtrl.addEventListener(`before-hide`,this.__onBeforeHide)}__teardownSyncFromOverlayController(){this._overlayCtrl.removeEventListener(`show`,this.__onOverlayCtrlShow),this._overlayCtrl.removeEventListener(`hide`,this.__onOverlayCtrlHide),this._overlayCtrl.removeEventListener(`before-show`,this.__onBeforeShow),this._overlayCtrl.removeEventListener(`before-hide`,this.__onBeforeHide)}__syncToOverlayController(){this.opened?this._overlayCtrl.show():this._overlayCtrl.hide()}async toggle(){await this._overlayCtrl.toggle()}async open(){await this._overlayCtrl.show()}async close(){await this._overlayCtrl.hide()}repositionOverlay(){let e=this._overlayCtrl;e.placementMode===`local`&&e._popper&&e._popper.update()}async _isPermanentlyDisconnected(){return await this.updateComplete,!this.isConnected}});function Sp(){return{visibilityTriggerFunction:({controller:e})=>{function t(){e._hasDisabledInvoker()||e.toggle()}return{init:()=>{e.invokerNode?.addEventListener(`click`,t)},teardown:()=>{e.invokerNode?.removeEventListener(`click`,t)}}}}}var wp=()=>({placementMode:`local`,inheritsReferenceWidth:`min`,hidesOnOutsideClick:!0,hidesOnEsc:!0,popperConfig:{placement:`bottom-start`,modifiers:[{name:`offset`,enabled:!1}]},handlesAccessibility:!0,...Sp()}),Tp=class extends xp(h){_defineOverlayConfig(){return{...wp()}}_addEventListeners(){this.actionItems.forEach(e=>{e.addEventListener(`click`,e=>{e.target?.dispatchEvent(new Event(`close-overlay`,{bubbles:!0}))})})}_setupInvoker(){let e=this.invokerNodes[0];e&&(e.setAttribute(`id`,`invoker-${this.uid}`),e.setAttribute(`aria-controls`,`content-${this.uid}`))}_setupContent(){let e=this.contentNodes[0];e&&(e.setAttribute(`id`,`content-${this.uid}`),e.setAttribute(`role`,`none`))}_setupOverlayCtrl(){super._setupOverlayCtrl(),this._setupInvoker(),this._setupContent()}firstUpdated(){this.uid=ws(),this._addEventListeners()}render(){return l`
      <slot name="invoker"></slot>
      <slot name="backdrop"></slot>
      <slot name="content"></slot>
    `}};Tp.styles=d`
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
  `,o([b({selector:`craft-action-item`})],Tp.prototype,`actionItems`,void 0),o([b({slot:`invoker`})],Tp.prototype,`invokerNodes`,void 0),o([b({slot:`content`})],Tp.prototype,`contentNodes`,void 0),customElements.get(`craft-action-menu`)||customElements.define(`craft-action-menu`,Tp);var Ep=new WeakMap;function Dp(e,t){Array.from(e.childNodes).forEach(n=>{if(n.nodeName===`#text`){let r=RegExp(`^(.*?)(${t})(.*)$`,`i`),i=n.nodeValue.match(r);if(i){let t=document.createTextNode(i[1]);e.appendChild(t);let r=document.createElement(`b`);r.textContent=i[2],e.appendChild(r);let a=document.createTextNode(i[3]);e.appendChild(a),e.removeChild(n),Ep.set(e,()=>{e.appendChild(n),e.contains(t)&&t.parentNode!==null&&t.parentNode.removeChild(t),e.contains(r)&&r.parentNode!==null&&r.parentNode.removeChild(r),e.contains(a)&&a.parentNode!==null&&a.parentNode.removeChild(a)})}}else Dp(n,t)})}function Op(e){Ep.has(e)&&Ep.get(e)(),Array.from(e.childNodes).forEach(e=>{e.nodeName===`#text`?Ep.has(e)&&Ep.get(e)():Op(e)})}var kp=class extends su{static get validatorName(){return`MatchesOption`}execute(e,t,n){return n?.node.modelValue instanceof Ql}};function Ap(e){return Array.isArray(e)?e:[e]}var jp=R(e=>class extends vu(e){static get properties(){return{allowCustomChoice:{type:Boolean,attribute:`allow-custom-choice`},modelValue:{type:Object}}}get modelValue(){return this.__getChoicesFrom(super.modelValue)}set modelValue(e){if(super.modelValue=e,e==null||e===``)this._customChoices=new Set;else if(this.allowCustomChoice){let t=this.modelValue;this._customChoices=new Set(Ap(e)),this.requestUpdate(`modelValue`,t)}}get formattedValue(){return this.__getChoicesFrom(super.formattedValue)}set formattedValue(e){if(super.formattedValue=e,e==null)this._customChoices=new Set;else if(this.allowCustomChoice){let t=this.modelValue;this._customChoices=new Set(Ap(e).map(e=>this.formElements.find(t=>t.formattedValue===e)?.modelValue||e)),this.requestUpdate(`modelValue`,t)}}get serializedValue(){return this.__getChoicesFrom(super.serializedValue)}set serializedValue(e){if(super.serializedValue=e,e==null)this._customChoices=new Set;else if(this.allowCustomChoice){let t=this.modelValue;this._customChoices=new Set(Ap(e).map(e=>this.formElements.find(t=>t.serializedValue===e)?.modelValue||e)),this.requestUpdate(`modelValue`,t)}}get customChoices(){if(!this.allowCustomChoice)return[];let e=this._getCheckedElements();return Array.from(this._customChoices).filter(t=>!e.some(e=>e.choiceValue===t))}constructor(){super(),this.allowCustomChoice=!1,this._customChoices=new Set}__getChoicesFrom(e){let t=e;return this.allowCustomChoice?this.multipleChoice?[...Ap(t),...this.customChoices]:t===``?this._customChoices.values().next().value||``:t:t}_isEmpty(){return super._isEmpty()&&this._customChoices.size===0}clear(){this._customChoices=new Set,super.clear()}parser(e){return this.allowCustomChoice&&Array.isArray(e)?e.filter(e=>e.trim()!==``):e}}),Mp=new WeakMap,Np=class extends Ul(xp(jp(od))){static get properties(){return{autocomplete:{type:String,reflect:!0},matchMode:{type:String,attribute:`match-mode`},showAllOnEmpty:{type:Boolean,attribute:`show-all-on-empty`},requireOptionMatch:{type:Boolean},allowCustomChoice:{type:Boolean,attribute:`allow-custom-choice`},__shouldAutocompleteNextUpdate:Boolean}}static get styles(){return[...super.styles,d`
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
      `]}static get localizeNamespaces(){return[{"lion-combobox":e=>{switch(e){case`bg-BG`:case`bg`:return s(()=>import(`./bg-CuEGhruT.js`),[],import.meta.url);case`cs-CZ`:case`cs`:return s(()=>import(`./cs-2SZ7QM1V.js`),[],import.meta.url);case`de-AT`:case`de-DE`:case`de`:return s(()=>import(`./de-CEDvd6cg.js`),[],import.meta.url);case`en-AU`:case`en-GB`:case`en-PH`:case`en-US`:case`en`:return s(()=>import(`./en-DOV3vT-D.js`),[],import.meta.url);case`es-ES`:case`es`:return s(()=>import(`./es-C0IHc1Wd.js`),[],import.meta.url);case`fr-FR`:case`fr-BE`:case`fr`:return s(()=>import(`./fr-CulG44I5.js`),[],import.meta.url);case`hu-HU`:case`hu`:return s(()=>import(`./hu-AoJz8w28.js`),[],import.meta.url);case`it-IT`:case`it`:return s(()=>import(`./it-BJz8uygq.js`),[],import.meta.url);case`nl-BE`:case`nl-NL`:case`nl`:return s(()=>import(`./nl-B1D0dNMv.js`),[],import.meta.url);case`pl-PL`:case`pl`:return s(()=>import(`./pl-0uB8UuXI.js`),[],import.meta.url);case`ro-RO`:case`ro`:return s(()=>import(`./ro-AqclXPPt.js`),[],import.meta.url);case`ru-RU`:case`ru`:return s(()=>import(`./ru-BFlrFCvk.js`),[],import.meta.url);case`sk-SK`:case`sk`:return s(()=>import(`./sk-C_awP-xt.js`),[],import.meta.url);case`uk-UA`:case`uk`:return s(()=>import(`./uk-BZmRIks-.js`),[],import.meta.url);case`zh-CN`:case`zh`:return s(()=>import(`./zh-DPyH6Lk_.js`),[],import.meta.url);default:return s(()=>import(`./en-DOV3vT-D.js`),[],import.meta.url)}}},...super.localizeNamespaces]}get modelValue(){let e=super.modelValue;return e===``?this.parser(this.value):e}set modelValue(e){super.modelValue=e}get value(){return this._inputNode?.value||this.__value||``}set value(e){this._inputNode?(this._inputNode.value=e,this.__value=void 0):this.__value=e}reset(){super.reset(),this.multipleChoice||(this.value=this._initialModelValue),this._resetListboxOptions()}_resetListboxOptions(){this.formElements.forEach((e,t)=>{this._unhighlightMatchedOption(e),!this.showAllOnEmpty||!this.opened?e.style.display=`none`:(e.style.display=``,e.setAttribute(`aria-posinset`,`${t+1}`),e.setAttribute(`aria-setsize`,`${this.formElements.length}`),e.removeAttribute(`aria-hidden`))})}_inputGroupInputTemplate(){return l`
      <div class="input-group__input">
        <slot name="selection-display"></slot>
        <slot name="input"></slot>
      </div>
    `}_overlayListboxTemplate(){return l`
      <div
        id="overlay-content-node-wrapper"
        role="dialog"
        aria-label="${this.msgLit(`lion-combobox:optionsPopup`)}"
      >
        <slot name="listbox"></slot>
      </div>
      <slot id="options-outlet"></slot>
    `}_groupTwoTemplate(){return l` ${super._groupTwoTemplate()} ${this._overlayListboxTemplate()}`}get slots(){return{...super.slots,input:()=>{if(this._ariaVersion===`1.1`){let e=document.createElement(`div`),t=document.createElement(`input`);return t.style.cssText=`
          border: none;
          outline: none;
          width: 100%;
          height: 100%;
          font: inherit;
          background: inherit;
          color: inherit;
          border-radius: inherit;
          box-sizing: border-box;
          padding: 0;`,e.appendChild(t),e}return document.createElement(`input`)},listbox:super.slots.input}}get _comboboxNode(){return this.querySelector(`[slot="input"]`)}get _selectionDisplayNode(){return this.querySelector(`[slot="selection-display"]`)}get _inputNode(){return this._ariaVersion===`1.1`&&this._comboboxNode&&this._comboboxNode.querySelector(`input`)||this._comboboxNode}get _overlayContentNode(){return this._listboxNode}get _overlayReferenceNode(){return this.shadowRoot.querySelector(`.input-group__container`)}get _overlayInvokerNode(){return this._inputNode}get _listboxNode(){return this._overlayCtrl&&this._overlayCtrl.contentNode||Array.from(this.children).find(e=>e.slot===`listbox`)}get _activeDescendantOwnerNode(){return this._inputNode}get requireOptionMatch(){return!this.allowCustomChoice}set requireOptionMatch(e){this.allowCustomChoice=!e}constructor(){super(),this.autocomplete=`both`,this.matchMode=`all`,this.showAllOnEmpty=!1,this.requireOptionMatch=!0,this.rotateKeyboardNavigation=!0,this.selectionFollowsFocus=!0,this.defaultValidators.push(new kp),this._ariaVersion=Cs.isChromium?`1.1`:`1.0`,this._listboxReceivesNoFocus=!0,this._noTypeAhead=!0,this.__prevCboxValueNonSelected=``,this.__prevCboxValue=``,this.__hadUserIntendsInlineAutoFill=!1,this.__listboxContentChanged=!1,this._onKeyUp=this._onKeyUp.bind(this),this._textboxOnClick=this._textboxOnClick.bind(this),this._textboxOnInput=this._textboxOnInput.bind(this),this._textboxOnKeydown=this._textboxOnKeydown.bind(this)}connectedCallback(){super.connectedCallback(),this._selectionDisplayNode&&(this._selectionDisplayNode.comboboxElement=this),(this.disabled||this.readOnly)&&this.__setComboboxDisabledAndReadOnly()}requestUpdate(e,t,n){if(super.requestUpdate(e,t,n),(e===`disabled`||e===`readOnly`)&&this.__setComboboxDisabledAndReadOnly(),e===`modelValue`&&this.modelValue&&this.modelValue!==t&&this._syncToTextboxCondition(this.modelValue,this._oldModelValue))if(this.multipleChoice)this._syncToTextboxMultiple(this.modelValue,this._oldModelValue);else{let e=this._getTextboxValueFromOption(this.formElements[this.checkedIndex]);this._setTextboxValue(e)}}parser(e){return this.requireOptionMatch&&this.checkedIndex===-1&&e!==``&&!Array.isArray(e)?new Ql(e):super.parser(e)}__unsyncCheckedIndexOnInputChange(){let e=this._autoSelectCondition(),t=this.formElements[this.checkedIndex];if(!this.multipleChoice&&!e&&t){let e=this._getTextboxValueFromOption(t);this._inputNode.value.startsWith(e)||this.setCheckedIndex(-1)}}updated(e){super.updated(e),e.has(`__shouldAutocompleteNextUpdate`)&&this.__unsyncCheckedIndexOnInputChange(),e.has(`opened`)&&(this.opened&&(this.activeIndex=-1),!this.opened&&e.get(`opened`)!==void 0&&(this.__onOverlayClose(),this.activeIndex=-1)),e.has(`autocomplete`)&&this._inputNode.setAttribute(`aria-autocomplete`,this.autocomplete),e.has(`disabled`)&&this.setAttribute(`aria-disabled`,`${this.disabled}`),e.has(`__shouldAutocompleteNextUpdate`)&&this.__shouldAutocompleteNextUpdate&&(this._handleAutocompletion(),this.__shouldAutocompleteNextUpdate=!1,this.__listboxContentChanged=!1),typeof this._selectionDisplayNode?.onComboboxElementUpdated==`function`&&this._selectionDisplayNode.onComboboxElementUpdated(e)}matchCondition(e,t){let n=-1,r=this._getTextboxValueFromOption(e);return typeof r==`string`&&typeof t==`string`&&(n=r.toLowerCase().indexOf(t.toLowerCase())),this.matchMode===`all`?n>-1:n===0}_showOverlayCondition({lastKey:e}){return this.disabled||this.readOnly||e&&([`Tab`,`Escape`].includes(e)||!this.multipleChoice&&[`Enter`].includes(e))?!1:this.filled||this.showAllOnEmpty||!this.filled&&this.multipleChoice&&this.__prevCboxValueNonSelected?!0:this.opened}_getTextboxValueFromOption(e){return e?e.choiceValue:this.modelValue instanceof Ql?this.modelValue.viewValue:this.modelValue}_onListboxContentChanged(){super._onListboxContentChanged(),this.__shouldAutocompleteNextUpdate=!0,this.__listboxContentChanged=!0}_textboxOnInput(e){this.__shouldAutocompleteNextUpdate=!0,this.opened=this._showOverlayCondition({})}_textboxOnKeydown(e){e.key===`Tab`&&(this.opened=!1)}_listboxOnClick(e){super._listboxOnClick(e),this._inputNode.focus(),this.multipleChoice?(this._inputNode.value=``,this._resetListboxOptions()):(this.activeIndex=-1,this.opened=!1)}_setTextboxValue(e){this._inputNode&&this._inputNode.value!==e&&(this._inputNode.value=e)}__onOverlayClose(){this.multipleChoice?this._syncToTextboxMultiple(this.modelValue,this._oldModelValue):this.checkedIndex!==-1&&this._syncToTextboxCondition(this.modelValue,this._oldModelValue,{phase:`overlay-close`})&&(this._inputNode.value=this._getTextboxValueFromOption(this.formElements[this.checkedIndex]))}_repropagationCondition(e){return super._repropagationCondition(e)||this.formElements.every(e=>!e.checked)}_onFilterMatch(e,t){this._highlightMatchedOption(e,t),e.style.display=``}_highlightMatchedOption(e,t){if(Dp(e,t),e.textContent){let t=document.createElement(`span`);t.setAttribute(`aria-label`,e.textContent.replace(/\s+/g,` `)),Array.from(e.childNodes).forEach(e=>{t.appendChild(e)}),e.appendChild(t),Mp.set(e,()=>{Array.from(t.childNodes).forEach(t=>{e.appendChild(t)}),e.contains(t)&&e.removeChild(t)})}}_onFilterUnmatch(e,t,n){this._unhighlightMatchedOption(e),e.style.display=`none`}_unhighlightMatchedOption(e){Op(e),Mp.has(e)&&Mp.get(e)()}__computeUserIntendsAutoFill({prevValue:e,curValue:t}){let n=e.length<t.length,r=e.length&&t.length&&e[0].toLowerCase()!==t[0].toLowerCase();return n||r||this.__listboxContentChanged&&this.__hadUserIntendsInlineAutoFill}_handleAutocompletion(){let e=this._inputNode.selectionStart!==this._inputNode.selectionEnd&&this._inputNode.value.length!==this._inputNode.selectionStart,t=this._inputNode.value,n=this._inputNode.selectionStart,r=e&&n?t.slice(0,n):t,i=e||this.__hadSelectionLastAutofill?this.__prevCboxValueNonSelected:this.__prevCboxValue,a=!r,o=[],s=!1,c=this.__computeUserIntendsAutoFill({prevValue:i,curValue:r}),l=this.autocomplete===`both`||this.autocomplete===`inline`,u=this._autoSelectCondition(),d=this.autocomplete===`inline`||this.autocomplete===`none`;this.formElements.forEach((e,t)=>{let n=this.matchCondition(e,r),f=!1;if(f=a?this.showAllOnEmpty:d||n,u&&!s&&n&&!e.disabled){let n=()=>{this.activeIndex=t,this.selectionFollowsFocus&&!this.multipleChoice&&this.setCheckedIndex(this.activeIndex),s=!0};if(c)if(l){let t=this._getTextboxValueFromOption(e);typeof t==`string`&&t!==``&&typeof r==`string`&&r!==``&&t.toLowerCase().indexOf(r.toLowerCase())===0&&(this.__textboxInlineComplete(e),n())}else n()}e.onFilterUnmatch?e.onFilterUnmatch(r,i):this._onFilterUnmatch(e,r,i),e.setAttribute(`aria-hidden`,`true`),e.removeAttribute(`aria-posinset`),e.removeAttribute(`aria-setsize`),f&&(o.push(e),e.onFilterMatch?e.onFilterMatch(r):this._onFilterMatch(e,r))});let f=o.length;o.forEach((e,t)=>{e.setAttribute(`aria-posinset`,`${t+1}`),e.setAttribute(`aria-setsize`,`${f}`),e.removeAttribute(`aria-hidden`)}),u&&!s&&!this.multipleChoice&&(this.setCheckedIndex(-1),i!==r&&(this.activeIndex=-1),this.modelValue=this.parser(t)),this.__prevCboxValueNonSelected=r,this.__prevCboxValue=this._inputNode.value,this.__hadSelectionLastAutofill=this._inputNode.value.length!==this._inputNode.selectionStart,this.__hadUserIntendsInlineAutoFill=c,this._overlayCtrl&&this._overlayCtrl._popper&&this._overlayCtrl._popper.update()}__textboxInlineComplete(e=this.formElements[this.activeIndex]){let t=this._getTextboxValueFromOption(e);if(this._inputNode.value!==t){let e=this._inputNode.value.length;this._inputNode.value=t,this._inputNode.selectionStart=e,this._inputNode.selectionEnd=this._inputNode.value.length}}_autoSelectCondition(){return this.autocomplete===`both`||this.autocomplete===`inline`}_setupListboxNode(){super._setupListboxNode(),this._listboxNode.removeAttribute(`tabindex`)}_defineOverlayConfig(){return{...wp(),elementToFocusAfterHide:void 0,invokerNode:this._comboboxNode,visibilityTriggerFunction:void 0}}_setupOverlayCtrl(){super._setupOverlayCtrl(),this.__shouldAutocompleteNextUpdate=!0,this.__setupCombobox()}_teardownOverlayCtrl(){super._teardownOverlayCtrl(),this.__teardownCombobox()}_setupOpenCloseListeners(){super._setupOpenCloseListeners(),this._inputNode.addEventListener(`keyup`,this._onKeyUp),this._inputNode.addEventListener(`click`,this._textboxOnClick)}_teardownOpenCloseListeners(){super._teardownOpenCloseListeners(),this._inputNode.removeEventListener(`keyup`,this._onKeyUp),this._inputNode.removeEventListener(`click`,this._textboxOnClick)}_listboxOnKeyDown(e){let{key:t}=e;switch(t){case`Escape`:this.opened=!1,super._listboxOnKeyDown(e),this._setTextboxValue(``);break;case`Backspace`:case`Delete`:this.requireOptionMatch?super._listboxOnKeyDown(e):this.opened=!1;break;case`Enter`:this.opened&&e.preventDefault(),!this.requireOptionMatch&&this.multipleChoice&&(!this.formElements[this.activeIndex]||this.formElements[this.activeIndex].hasAttribute(`aria-hidden`)||!this.opened)?(this.modelValue=this.parser([...this.modelValue,this._inputNode.value]),this._inputNode.value=``,this.opened=!1):(super._listboxOnKeyDown(e),this._resetListboxOptions()),this.multipleChoice?this._inputNode.value=``:this.opened=!1;break;default:super._listboxOnKeyDown(e);break}}_syncToTextboxCondition(e,t,{phase:n}={}){return this.autocomplete===`both`||this.autocomplete===`inline`||!this.focused}_syncToTextboxMultiple(e,t=[]){if(this.requireOptionMatch){let n=e.filter(e=>!t.includes(e)),r=this.formElements.filter(e=>n.includes(e.choiceValue)).map(e=>this._getTextboxValueFromOption(e)).join(` `);this._setTextboxValue(r)}}_enhanceLightDomClasses(){let e=this.querySelector(`[slot=input]`);e&&e.classList.add(`form-control`)}__setComboboxDisabledAndReadOnly(){this._comboboxNode&&(this._comboboxNode.toggleAttribute(`disabled`,this.disabled),this._comboboxNode.setAttribute(`aria-disabled`,`${this.disabled}`),this._comboboxNode.toggleAttribute(`readonly`,this.readOnly),this._comboboxNode.setAttribute(`aria-readonly`,`${this.readOnly}`)),this._inputNode&&(this._inputNode.toggleAttribute(`disabled`,this.disabled),this._inputNode.toggleAttribute(`readOnly`,this.readOnly),this._inputNode.setAttribute(`aria-readonly`,`${this.readOnly}`),this._inputNode.tabIndex=this.disabled?-1:0)}__setupCombobox(){this._comboboxNode.setAttribute(`role`,`combobox`),this._comboboxNode.setAttribute(`aria-haspopup`,`listbox`),this._inputNode.setAttribute(`aria-autocomplete`,this.autocomplete),this._comboboxNode.setAttribute(`aria-controls`,this._listboxNode.id),this._ariaVersion===`1.1`?this._comboboxNode.setAttribute(`aria-owns`,this._listboxNode.id):this._inputNode.setAttribute(`aria-owns`,this._listboxNode.id),this._listboxNode.setAttribute(`aria-labelledby`,this._labelNode.id),this._inputNode.addEventListener(`keydown`,this._listboxOnKeyDown),this._inputNode.addEventListener(`input`,this._textboxOnInput),this._inputNode.addEventListener(`keydown`,this._textboxOnKeydown)}__teardownCombobox(){this._inputNode.removeEventListener(`keydown`,this._listboxOnKeyDown),this._inputNode.removeEventListener(`input`,this._textboxOnInput),this._inputNode.removeEventListener(`keydown`,this._textboxOnKeydown)}_onKeyUp(e){let t=e&&e.key;this.opened=this._showOverlayCondition({lastKey:t,currentValue:this._inputNode.value})}_textboxOnClick(e){this.opened=this._showOverlayCondition({})}clear(){this.value=``,super.clear(),this.__shouldAutocompleteNextUpdate=!0}},Pp=d`
  ${el}

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
    ${$c}
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
`,Fp=class extends Np{static get styles(){return[...super.styles,Pp]}constructor(){super(),this.defaultValidators=[]}_inputGroupInputTemplate(){return l`
      <div class="input-group__input">
        <slot name="input"></slot>
        <craft-icon
          class="indicator"
          name="chevron-down"
          style="font-size: 0.8em"
        ></craft-icon>
      </div>
    `}parser(e){return e===``?super.parser(e):e}_getTextboxValueFromOption(e){return e?e.textContent?.trim()||``:super._getTextboxValueFromOption(e)}};customElements.get(`craft-combobox`)||customElements.define(`craft-combobox`,Fp);var Ip=Object.values({Red:`red`,Orange:`orange`,Amber:`amber`,Yellow:`yellow`,Lime:`lime`,Green:`green`,Emerald:`emerald`,Teal:`teal`,Cyan:`cyan`,Sky:`sky`,Blue:`blue`,Indigo:`indigo`,Violet:`violet`,Purple:`purple`,Fuchsia:`fuchsia`,Pink:`pink`,Rose:`rose`,White:`white`,Gray:`gray`,Black:`black`}),Lp=class extends h{constructor(...e){super(...e),this.size=`md`,this.fill=`var(--c-color-fill-loud)`,this.label=null,this.appearance=Pf.OutlineFill}getFill(){return Ip.includes(this.fill)||If.includes(this.fill)?`var(--c-color-${this.fill}-fill-loud)`:this.fill}getSize(){switch(this.size){case`md`:return`0.6em`;case`lg`:return`1em`;default:return this.size}}render(){return l`<span
      style="--fill: ${this.getFill()}; --size: ${this.getSize()}"
      aria-label="${this.label}"
      role="img"
      class="${a({indicator:!0,"indicator--outline":this.appearance===Pf.Outline})}"
    ></span>`}};Lp.styles=[Lf,d`
      .indicator {
        --_fill: var(--fill, var(--c-color-fill-loud));
        --_size: var(--size, 0.5em);
        display: inline-flex;
        aspect-ratio: 1;
        width: var(--_size);
        border-radius: var(--c-radius-full);
        background: var(--_fill);
        border: 1px solid var(--_fill);
      }

      /* Appearances */
      :host([appearance~='outline-fill']) .indicator {
        background: var(--_fill);
        border: 1px solid rgba(0, 0, 0, 0.5);
      }

      :host([appearance~='solid']) .indicator {
        background: var(--_fill);
        border-color: transparent;
      }

      :host([appearance~='outline']) .indicator {
        background: transparent;
        border: 2px solid var(--_fill);
      }
    `],o([g()],Lp.prototype,`size`,void 0),o([g({reflect:!0})],Lp.prototype,`fill`,void 0),o([g()],Lp.prototype,`label`,void 0),o([g({reflect:!0})],Lp.prototype,`appearance`,void 0),customElements.get(`craft-indicator`)||customElements.define(`craft-indicator`,Lp);var Rp=new WeakMap,zp=new WeakMap,Bp=new WeakMap,Vp=new WeakMap,Hp=new WeakMap,Up=new WeakMap,Wp=new WeakMap,Gp=new WeakMap,Kp=new WeakMap,qp=new WeakMap,Jp=new WeakMap,X=new WeakSet,Yp=class extends h{constructor(...e){super(...e),re(this,X),this.progress=0,this.failed=!1,this.color=`currentColor`,this.bgColor=`#a3afbb`,this.failColor=`#da5a47`,this.label=`Progress`,this.autoComplete=!1,w(this,Rp,null),w(this,zp,0),w(this,Bp,0),w(this,Vp,0),w(this,Hp,0),w(this,Up,0),w(this,Wp,null),w(this,Gp,0),w(this,Kp,null),w(this,qp,0),w(this,Jp,!1)}connectedCallback(){super.connectedCallback(),E(Jp,this,window.matchMedia(`(prefers-reduced-motion: reduce)`).matches)}disconnectedCallback(){super.disconnectedCallback(),T(X,this,nm).call(this)}firstUpdated(){E(Rp,this,this.renderRoot.querySelector(`canvas`)),T(X,this,Xp).call(this),T(X,this,Zp).call(this)}updated(e){e.has(`progress`)?T(X,this,Zp).call(this):(e.has(`color`)||e.has(`bgColor`)||e.has(`failColor`)||e.has(`failed`))&&T(X,this,$p).call(this)}get canvas(){return D(Rp,this)}get prefersReducedMotion(){return D(Jp,this)}runCompleteAnimation(){return new Promise(e=>{if(D(Jp,this)){E(Up,this,1),D(Rp,this)&&(D(Rp,this).style.opacity=`0`),T(X,this,$p).call(this),e();return}T(X,this,tm).call(this,1,()=>{D(Rp,this)&&(D(Rp,this).style.transition=`opacity 0.4s`,D(Rp,this).style.opacity=`0`),setTimeout(e,400)})})}async complete(){await this.runCompleteAnimation(),this.dispatchEvent(new CustomEvent(`complete`,{bubbles:!0,composed:!0}))}render(){return l`
      <canvas
        part="canvas"
        role="progressbar"
        aria-valuenow=${(this.progress>=0?this.progress:void 0)??``}
        aria-valuemin="0"
        aria-valuemax="100"
        aria-label=${this.label}
      ></canvas>
      <span class="visually-hidden">
        ${this.failed?`Failed`:this.progress<0?`Loading`:`${this.progress}%`}
      </span>
    `}};function Xp(){let e=getComputedStyle(this),t=parseFloat(e.getPropertyValue(`--_size`)),n=parseFloat(e.getPropertyValue(`--_stroke-width`)),r=window.devicePixelRatio>1?2:1;E(zp,this,t*r),E(Bp,this,D(zp,this)/2),E(Hp,this,n*r),E(Vp,this,(t/2-n/2)*r),D(Rp,this)&&(D(Rp,this).width=D(zp,this),D(Rp,this).height=D(zp,this))}function Zp(){if(this.progress>=0&&D(Kp,this)!==null&&(cancelAnimationFrame(D(Kp,this)),E(Kp,this,null),E(Gp,this,0)),this.progress<0){D(Kp,this)===null&&T(X,this,Qp).call(this);return}let e=this.progress/100;if(this.autoComplete&&this.progress>=100&&D(qp,this)<100){E(qp,this,this.progress),this.complete();return}D(qp,this)>0&&this.progress>D(qp,this)&&!D(Jp,this)?T(X,this,tm).call(this,e):(E(Up,this,e),T(X,this,$p).call(this)),E(qp,this,this.progress)}function Qp(){if(D(Jp,this)){E(Up,this,.25),T(X,this,$p).call(this);return}let e=()=>{E(Gp,this,D(Gp,this)+.05),E(Up,this,.15+.1*Math.sin(D(Gp,this)*3)),T(X,this,$p).call(this),E(Kp,this,requestAnimationFrame(e))};E(Kp,this,requestAnimationFrame(e))}function $p(){let e=D(Rp,this)?.getContext(`2d`);if(e){if(e.clearRect(0,0,D(zp,this),D(zp,this)),this.failed){T(X,this,em).call(this,e,this.failColor,1,0);return}if(T(X,this,em).call(this,e,this.bgColor,1,0),D(Up,this)>0){let t=this.progress<0?D(Gp,this):-Math.PI/2;T(X,this,em).call(this,e,this.color,D(Up,this),t)}}}function em(e,t,n,r){e.strokeStyle=t,e.lineWidth=D(Hp,this),e.lineCap=`round`,e.beginPath(),e.arc(D(Bp,this),D(Bp,this),D(Vp,this),r,r+n*2*Math.PI),e.stroke()}function tm(e,t){T(X,this,nm).call(this);let n=performance.now(),r=D(Up,this),i=a=>{let o=a-n,s=Math.min(o/500,1),c=1-(1-s)**3;E(Up,this,r+(e-r)*c),T(X,this,$p).call(this),s<1?E(Wp,this,requestAnimationFrame(i)):(E(Wp,this,null),t?.())};E(Wp,this,requestAnimationFrame(i))}function nm(){D(Wp,this)!==null&&(cancelAnimationFrame(D(Wp,this)),E(Wp,this,null)),D(Kp,this)!==null&&(cancelAnimationFrame(D(Kp,this)),E(Kp,this,null))}Yp.styles=d`
    :host {
      --_size: var(--c-progress-size, 16px);
      --_stroke-width: var(--c-progress-stroke-width, 3px);

      display: inline-block;
      position: relative;
      width: var(--_size);
      height: var(--_size);
    }

    canvas {
      position: absolute;
      top: 0;
      left: 0;
      width: var(--_size);
      height: var(--_size);
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
  `,o([g({type:Number})],Yp.prototype,`progress`,void 0),o([g({type:Boolean})],Yp.prototype,`failed`,void 0),o([g({type:String})],Yp.prototype,`color`,void 0),o([g({type:String,attribute:`bg-color`})],Yp.prototype,`bgColor`,void 0),o([g({type:String,attribute:`fail-color`})],Yp.prototype,`failColor`,void 0),o([g({type:String})],Yp.prototype,`label`,void 0),o([g({type:Boolean,attribute:`auto-complete`})],Yp.prototype,`autoComplete`,void 0),customElements.get(`craft-progress`)||customElements.define(`craft-progress`,Yp);var rm=d`
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
`,im=new WeakMap,am=class extends h{constructor(...e){super(...e),this.progress=0,this.total=0,this.processed=0,this.showStatus=!1,this.pending=!1,this.smooth=!1,this.label=`Progress`,w(this,im,0)}updated(e){if((e.has(`total`)||e.has(`processed`))&&this.total>0){let e=Math.min(100,Math.round(this.processed/this.total*100));e>=100&&D(im,this)<100&&this.dispatchEvent(new CustomEvent(`complete`,{bubbles:!0,composed:!0})),this.progress=e}e.has(`progress`)&&(this.progress>0&&this.pending&&(this.pending=!1),E(im,this,this.progress))}get progressPercent(){return Math.min(100,Math.max(0,this.progress))}get statusText(){return this.total>0?`${this.processed} / ${this.total}`:`${this.progressPercent}%`}reset(){this.progress=0,this.processed=0,this.pending=!0,E(im,this,0)}show(){this.hidden=!1}hide(){this.hidden=!0}render(){let e={width:this.pending?`100%`:`${this.progressPercent}%`};return l`
      <div
        class=${a({"progress-bar":!0,"progress-bar--pending":this.pending})}
        part="track"
        role="progressbar"
        aria-valuenow=${this.pending?m:this.progressPercent}
        aria-valuemin="0"
        aria-valuemax="100"
        aria-label=${this.label}
      >
        <div
          class=${a({"progress-bar__fill":!0,"progress-bar__fill--smooth":this.smooth&&!this.pending})}
          part="fill"
          style=${r(e)}
        ></div>
      </div>
      ${this.showStatus?l`<div class="progress-bar__status" part="status">
            ${this.statusText}
          </div>`:m}
      <span class="visually-hidden">
        ${this.pending?`Loading`:`${this.progressPercent}%`}
      </span>
    `}};am.styles=[rm],o([g({type:Number})],am.prototype,`progress`,void 0),o([g({type:Number})],am.prototype,`total`,void 0),o([g({type:Number})],am.prototype,`processed`,void 0),o([g({type:Boolean,attribute:`show-status`})],am.prototype,`showStatus`,void 0),o([g({type:Boolean,reflect:!0})],am.prototype,`pending`,void 0),o([g({type:Boolean})],am.prototype,`smooth`,void 0),o([g({type:String})],am.prototype,`label`,void 0),customElements.get(`craft-progress-bar`)||customElements.define(`craft-progress-bar`,am);var om=class extends vu(Su(h)){connectedCallback(){super.connectedCallback(),this.setAttribute(`role`,`radiogroup`)}resetGroup(){let e;this.formElements.forEach(t=>{typeof t.resetGroup==`function`?t.resetGroup():typeof t.reset==`function`&&(t.reset(),t.checked&&(e=t.choiceValue))}),this.modelValue=e,this.resetInteractionState()}},sm=class extends bu(Cu){connectedCallback(){super.connectedCallback(),this.type=`radio`}},cm=class extends om{static get styles(){return[...super.styles,tl,d`
        .input-group {
          display: grid;
          gap: var(--c-spacing-xs);
        }
      `]}};customElements.get(`craft-radio-group`)||customElements.define(`craft-radio-group`,cm);var lm=class extends sm{static get styles(){return[...super.styles,d`
        /* same as checkbox, potentially consolidate */
        :host {
          gap: var(--c-spacing-sm);
        }
      `]}};customElements.get(`craft-radio`)||customElements.define(`craft-radio`,lm);var um=class extends h{render(){return l`<slot></slot>`}};um.styles=[d`
      :host {
        display: grid;
        gap: var(--c-spacing-md);
      }
    `],customElements.get(`craft-field-group`)||customElements.define(`craft-field-group`,um);var dm=class extends h{constructor(...e){super(...e),this.appearance=`raised`,this.size=`lg`}render(){return l`<div
      class="${a({"cp-pane":!0,"cp-pane--raised":this.appearance===`raised`,"cp-pane--sunken":this.appearance===`sunken`,"cp-pane--lg":this.size===`lg`,"cp-pane--md":this.size===`md`,"cp-pane--sm":this.size===`sm`})}"
    >
      <slot></slot>
    </div>`}};dm.styles=[d`
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
    `],o([g()],dm.prototype,`appearance`,void 0),o([g()],dm.prototype,`size`,void 0),customElements.get(`craft-pane`)||customElements.define(`craft-pane`,dm);var fm=class e{constructor(t={}){this.config={...e.defaultCookieOptions,...t}}set(e,t,n={}){let{path:r,domain:i,maxAge:a,expires:o,secure:s,sameSite:c,prefix:l}=Object.assign({},this.config,n),u=`${this.config.prefix}:${e}=${encodeURIComponent(t)}`;r&&(u+=`;path=${r}`),i&&(u+=`;domain=${i}`),a?u+=`;max-age-in-seconds=${a}`:o&&(u+=`;expires=${o.toUTCString()}`),s&&(u+=`;secure`),document.cookie=u}get(e){return document.cookie.replace(RegExp(`(?:(?:^|.*;\\s*)${this.config.prefix}:${e}\\s*\\=\\s*([^;]*).*$)|^.*$`),`$1`)}remove(e){this.set(e,``,{expires:new Date(`1970-01-01T00:00:00`)})}};fm.defaultCookieOptions={path:`/`,domain:null,secure:!1,sameSite:`strict`,prefix:`Craft`};var pm=class{constructor(){this.refreshPromise=null,this.tokenName=null,this.tokenValue=null,this.refreshPromise=null}async getToken(){return this.tokenValue||await this.refreshToken(),this.tokenValue}async refreshToken(){return this.refreshPromise||=gm.get(`users/session-info`).then(({data:e})=>{let{csrfTokenName:t,csrfTokenValue:n}=e;return this.tokenName=t??null,this.tokenValue=n??null,this.tokenValue}).finally(()=>{this.refreshPromise=null}),this.refreshPromise}clearToken(){this.tokenValue=null}};function mm(e=``){return ae.getInstance().getActionUrl(e)}function hm(){return{"X-Registered-Asset-Bundles":[...new Set(Cp.registeredAssetBundles)].join(`,`),"X-Registered-Js-Files":[...new Set(Cp.registeredJsFiles)].join(`,`)}}var gm=M.create(),_m=new pm;gm.interceptors.request.use(async e=>{e.baseURL=mm(),e.headers.set(`X-Requested-With`,`XMLHttpRequest`);let t=hm();return Object.entries(t).forEach(([t,n])=>{e.headers.set(t,n)}),e}),gm.interceptors.response.use(e=>e,async e=>{let t=e.config;if(e.response?.status===419||e.response?.status===403&&!t._retry){t._retry=!0;try{return _m.clearToken(),t.headers[`X-CSRF-Token`]=await _m.refreshToken(),M(t)}catch(e){return console.error(`Failed to refresh CSRF token:`,e),Promise.reject(e)}}return Promise.reject(e)});var vm=!1,ym=null;async function bm(e){if(!vm){if(ym)return ym;vm=!0;try{return(await gm.post(`app/api-headers`,void 0,{cancelToken:e})).data}catch{}finally{vm=!1}}}var xm=M.create({baseURL:`https://api.craftcms.com/v1/`});async function Sm(e){return ym?Object.entries(ym).forEach(([t,n])=>{e.headers.set(t,n)}):(e.params=e.params||{},e.params.processCraftHeaders=1),e}async function Cm(e,t){if(ym)return;let{data:n}=await gm.post(`app/process-api-response-headers`,{headers:e},{cancelToken:t});return ym=n,vm=!1,ym}async function wm(e){return await Cm(e.headers,e.config.cancelToken),e}xm.interceptors.request.use(async e=>{let{cancelToken:t}=e,n=await bm(t);n&&Object.entries(n).forEach(([t,n])=>{e.headers.set(t,n)});let r={...e,params:{...Cp.apiParams||{},...e.params,v:new Date().getTime()}};return n||(r.params.processCraftHeaders=1),Cp.httpProxy&&(r.proxy=Cp.httpProxy),r}),xm.interceptors.request.use(Sm),xm.interceptors.response.use(wm);var Tm={START:`asset-indexes/start-indexing`,STOP:`asset-indexes/stop-indexing-session`,PROCESS:`asset-indexes/process-indexing-session`,OVERVIEW:`asset-indexes/indexing-session-overview`,FINISH:`asset-indexes/finish-indexing-session`},Em=new WeakMap,Dm=new WeakMap,Om=new WeakMap,km=new WeakMap,Am=new WeakMap,jm=new WeakMap,Mm=new WeakMap,Z=new WeakSet,Nm=class{constructor(e={}){re(this,Z),w(this,Em,new Map),w(this,Dm,null),w(this,Om,0),w(this,km,[]),w(this,Am,[]),w(this,jm,new Set),w(this,Mm,new Map);let{existingSessions:t=[],maxConcurrentConnections:n=3,autoResume:r=!0}=e;this.maxConcurrentConnections=n;for(let e of t)D(Em,this).set(e.id,e);r&&(T(Z,this,Lm).call(this),D(Dm,this)!==null&&T(Z,this,Rm).call(this))}getSessions(){return Array.from(D(Em,this).values())}getCurrentSessionId(){return D(Dm,this)}isProcessing(){return D(Om,this)>0}on(e,t){return D(Mm,this).has(e)||D(Mm,this).set(e,new Set),D(Mm,this).get(e).add(t),()=>{D(Mm,this).get(e)?.delete(t)}}async startIndexing(e){let t=await gm.post(Tm.START,e),{data:n}=t;return n.session&&(D(Em,this).set(n.session.id,n.session),E(Dm,this,n.session.id),T(Z,this,Fm).call(this),n.stop||T(Z,this,Rm).call(this)),n.stop&&T(Z,this,Im).call(this,n.stop),t}stopSession(e){T(Z,this,zm).call(this,e),T(Z,this,Bm).call(this,{sessionId:e,action:Tm.STOP,params:{sessionId:e},priority:!0})}getSessionOverview(e){T(Z,this,Bm).call(this,{sessionId:e,action:Tm.OVERVIEW,params:{sessionId:e},priority:!0})}finishSession(e){T(Z,this,Bm).call(this,{sessionId:e.sessionId,action:Tm.FINISH,params:e,priority:!0})}destroy(){D(Em,this).clear(),E(km,this,[]),E(Am,this,[]),D(Mm,this).clear(),E(Dm,this,null),E(Om,this,0)}};function Pm(e,t){D(Mm,this).get(e)?.forEach(e=>e(t))}function Fm(e){T(Z,this,Pm).call(this,`change`,{sessions:this.getSessions(),currentSessionId:D(Dm,this),reviewSessionId:e})}function Im(e){D(Em,this).delete(e),D(Dm,this)===e&&E(Dm,this,null),T(Z,this,Fm).call(this)}function Lm(){for(let[e,t]of D(Em,this))if(!t.actionRequired&&!D(jm,this).has(e)){E(Dm,this,e);return}E(Dm,this,null)}function Rm(){if(D(Dm,this)||T(Z,this,Lm).call(this),!D(Dm,this))return;let e=D(Em,this).get(D(Dm,this));if(!e)return;let t=e.totalEntries-e.processedEntries,n=this.maxConcurrentConnections-D(Om,this),r=Math.min(n,t);for(let t=0;t<r;t++)T(Z,this,Bm).call(this,{sessionId:e.id,action:Tm.PROCESS,params:{sessionId:D(Dm,this)},priority:!1});e.processIfRootEmpty&&T(Z,this,Bm).call(this,{sessionId:e.id,action:Tm.PROCESS,params:{sessionId:D(Dm,this)},priority:!1})}function zm(e){D(jm,this).add(e),E(km,this,D(km,this).filter(t=>t.sessionId!==e))}function Bm(e){e.priority?D(Am,this).push(e):D(km,this).push(e),T(Z,this,Vm).call(this)}function Vm(){if(!(D(km,this).length+D(Am,this).length===0||D(Om,this)>=this.maxConcurrentConnections))for(;D(km,this).length+D(Am,this).length>0&&D(Om,this)<this.maxConcurrentConnections;){var e;E(Om,this,(e=D(Om,this),e++,e));let t=D(Am,this).length>0?D(Am,this).shift():D(km,this).shift();T(Z,this,Hm).call(this,t)}}async function Hm(e){try{let t=await gm.post(e.action,e.params);T(Z,this,Um).call(this,t.data)}catch(t){T(Z,this,Wm).call(this,t,e)}finally{var t;E(Om,this,(t=D(Om,this),t--,t)),T(Z,this,Vm).call(this)}}function Um(e){let t;e.session&&(D(Em,this).set(e.session.id,e.session),T(Z,this,Lm).call(this),e.session.actionRequired&&!e.skipDialog?D(jm,this).has(e.session.id)||(t=e.session.id):D(jm,this).has(e.session.id)||T(Z,this,Rm).call(this)),T(Z,this,Lm).call(this),e.stop&&(D(Em,this).delete(e.stop),D(Dm,this)===e.stop&&E(Dm,this,null)),T(Z,this,Fm).call(this,t),D(Em,this).size===0&&T(Z,this,Pm).call(this,`complete`,{})}function Wm(e,t){T(Z,this,Lm).call(this);let n=e?.response?.data?.message||e.message||`An error occurred during indexing.`;T(Z,this,Pm).call(this,`error`,{message:n,sessionId:t.sessionId}),T(Z,this,Vm).call(this)}var Gm=function(e,t,n,r,i){if(r===`m`)throw TypeError(`Private method is not writable`);if(r===`a`&&!i)throw TypeError(`Private accessor was defined without a setter`);if(typeof t==`function`?e!==t||!i:!t.has(e))throw TypeError(`Cannot write private member to an object whose class did not declare it`);return r===`a`?i.call(e,n):i?i.value=n:t.set(e,n),n},Km=function(e,t,n,r){if(n===`a`&&!r)throw TypeError(`Private accessor was defined without a getter`);if(typeof t==`function`?e!==t||!r:!t.has(e))throw TypeError(`Cannot read private member from an object whose class did not declare it`);return n===`m`?r:n===`a`?r.call(e):r?r.value:t.get(e)},qm,Jm=class{formatToParts(e){let t=[];for(let n of e)t.push({type:`element`,value:n}),t.push({type:`literal`,value:`, `});return t.slice(0,-1)}},Ym=typeof Intl<`u`&&Intl.ListFormat||Jm,Xm=[[`years`,`year`],[`months`,`month`],[`weeks`,`week`],[`days`,`day`],[`hours`,`hour`],[`minutes`,`minute`],[`seconds`,`second`],[`milliseconds`,`millisecond`]],Zm={minimumIntegerDigits:2},Qm=class{constructor(e,t={}){qm.set(this,void 0);let n=String(t.style||`short`);n!==`long`&&n!==`short`&&n!==`narrow`&&n!==`digital`&&(n=`short`);let r=n===`digital`?`numeric`:n,i=t.hours||r;r=i===`2-digit`?`numeric`:i;let a=t.minutes||r;r=a===`2-digit`?`numeric`:a;let o=t.seconds||r;r=o===`2-digit`?`numeric`:o;let s=t.milliseconds||r;Gm(this,qm,{locale:e,style:n,years:t.years||n===`digital`?`short`:n,yearsDisplay:t.yearsDisplay===`always`?`always`:`auto`,months:t.months||n===`digital`?`short`:n,monthsDisplay:t.monthsDisplay===`always`?`always`:`auto`,weeks:t.weeks||n===`digital`?`short`:n,weeksDisplay:t.weeksDisplay===`always`?`always`:`auto`,days:t.days||n===`digital`?`short`:n,daysDisplay:t.daysDisplay===`always`?`always`:`auto`,hours:i,hoursDisplay:t.hoursDisplay===`always`||n===`digital`?`always`:`auto`,minutes:a,minutesDisplay:t.minutesDisplay===`always`||n===`digital`?`always`:`auto`,seconds:o,secondsDisplay:t.secondsDisplay===`always`||n===`digital`?`always`:`auto`,milliseconds:s,millisecondsDisplay:t.millisecondsDisplay===`always`?`always`:`auto`},`f`)}resolvedOptions(){return Km(this,qm,`f`)}formatToParts(e){let t=[],n=Km(this,qm,`f`),r=n.style,i=n.locale;for(let[a,o]of Xm){let s=e[a];if(n[`${a}Display`]===`auto`&&!s)continue;let c=n[a],l=c===`2-digit`?Zm:c===`numeric`?{}:{style:`unit`,unit:o,unitDisplay:c},u=new Intl.NumberFormat(i,l).format(s);a===`months`&&(c===`narrow`||r===`narrow`&&u.endsWith(`m`))&&(u=u.replace(/(\d+)m$/,`$1mo`)),t.push(u)}return new Ym(i,{type:`unit`,style:r===`digital`?`short`:r}).formatToParts(t)}format(e){return this.formatToParts(e).map(e=>e.value).join(``)}};qm=new WeakMap;var $m=/^[-+]?P(?:(\d+)Y)?(?:(\d+)M)?(?:(\d+)W)?(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?)?$/,eh=[`year`,`month`,`week`,`day`,`hour`,`minute`,`second`,`millisecond`],th=e=>$m.test(e),nh=class e{constructor(e=0,t=0,n=0,r=0,i=0,a=0,o=0,s=0){this.years=e,this.months=t,this.weeks=n,this.days=r,this.hours=i,this.minutes=a,this.seconds=o,this.milliseconds=s,this.years||=0,this.sign||=Math.sign(this.years),this.months||=0,this.sign||=Math.sign(this.months),this.weeks||=0,this.sign||=Math.sign(this.weeks),this.days||=0,this.sign||=Math.sign(this.days),this.hours||=0,this.sign||=Math.sign(this.hours),this.minutes||=0,this.sign||=Math.sign(this.minutes),this.seconds||=0,this.sign||=Math.sign(this.seconds),this.milliseconds||=0,this.sign||=Math.sign(this.milliseconds),this.blank=this.sign===0}abs(){return new e(Math.abs(this.years),Math.abs(this.months),Math.abs(this.weeks),Math.abs(this.days),Math.abs(this.hours),Math.abs(this.minutes),Math.abs(this.seconds),Math.abs(this.milliseconds))}static from(t){if(typeof t==`string`){let n=String(t).trim(),r=n.startsWith(`-`)?-1:1,i=n.match($m)?.slice(1).map(e=>(Number(e)||0)*r);return i?new e(...i):new e}else if(typeof t==`object`){let{years:n,months:r,weeks:i,days:a,hours:o,minutes:s,seconds:c,milliseconds:l}=t;return new e(n,r,i,a,o,s,c,l)}throw RangeError(`invalid duration`)}static compare(t,n){let r=Date.now(),i=Math.abs(rh(r,e.from(t)).getTime()-r),a=Math.abs(rh(r,e.from(n)).getTime()-r);return i>a?-1:+(i<a)}toLocaleString(e,t){return new Qm(e,t).format(this)}};function rh(e,t){let n=new Date(e);return t.sign<0?(n.setUTCSeconds(n.getUTCSeconds()+t.seconds),n.setUTCMinutes(n.getUTCMinutes()+t.minutes),n.setUTCHours(n.getUTCHours()+t.hours),n.setUTCDate(n.getUTCDate()+t.weeks*7+t.days),n.setUTCMonth(n.getUTCMonth()+t.months),n.setUTCFullYear(n.getUTCFullYear()+t.years)):(n.setUTCFullYear(n.getUTCFullYear()+t.years),n.setUTCMonth(n.getUTCMonth()+t.months),n.setUTCDate(n.getUTCDate()+t.weeks*7+t.days),n.setUTCHours(n.getUTCHours()+t.hours),n.setUTCMinutes(n.getUTCMinutes()+t.minutes),n.setUTCSeconds(n.getUTCSeconds()+t.seconds)),n}function ih(e,t=`second`,n=Date.now()){let r=e.getTime()-n;if(r===0)return new nh;let i=Math.sign(r),a=Math.abs(r),o=Math.floor(a/1e3),s=Math.floor(o/60),c=Math.floor(s/60),l=Math.floor(c/24),u=Math.floor(l/30),d=Math.floor(u/12),f=eh.indexOf(t)||eh.length;return new nh(f>=0?d*i:0,f>=1?(u-d*12)*i:0,0,f>=3?(l-u*30)*i:0,f>=4?(c-l*24)*i:0,f>=5?(s-c*60)*i:0,f>=6?(o-s*60)*i:0,f>=7?(a-o*1e3)*i:0)}function ah(e,{relativeTo:t=Date.now()}={}){if(t=new Date(t),e.blank)return e;let n=e.sign,r=Math.abs(e.years),i=Math.abs(e.months),a=Math.abs(e.weeks),o=Math.abs(e.days),s=Math.abs(e.hours),c=Math.abs(e.minutes),l=Math.abs(e.seconds),u=Math.abs(e.milliseconds);u>=900&&(l+=Math.round(u/1e3)),(l||c||s||o||a||i||r)&&(u=0),l>=55&&(c+=Math.round(l/60)),(c||s||o||a||i||r)&&(l=0),c>=55&&(s+=Math.round(c/60)),(s||o||a||i||r)&&(c=0),o&&s>=12&&(o+=Math.round(s/24)),!o&&s>=21&&(o+=Math.round(s/24)),(o||a||i||r)&&(s=0);let d=t.getFullYear(),f=t.getMonth(),p=t.getDate();if(o>=27||r+i+o){let e=new Date(t);e.setDate(1),e.setMonth(f+i*n+1),e.setDate(0);let s=Math.max(0,p-e.getDate()),c=new Date(t);c.setFullYear(d+r*n),c.setDate(p-s),c.setMonth(f+i*n),c.setDate(p-s+o*n);let l=c.getFullYear()-t.getFullYear(),u=c.getMonth()-t.getMonth(),m=Math.abs(Math.round((Number(c)-Number(t))/864e5))+s,h=Math.abs(l*12+u);m<27?(o>=6?(a+=Math.round(o/7),o=0):o=m,i=r=0):h<=11?(i=h,r=0):(i=0,r=l*n),(i||r)&&(o=0)}return r&&(i=0),a>=4&&(i+=Math.round(a/4)),(i||r)&&(a=0),o&&a&&!i&&!r&&(a+=Math.round(o/7),o=0),new nh(r*n,i*n,a*n,o*n,s*n,c*n,l*n,u*n)}function oh(e,t){let n=ah(e,t);if(n.blank)return[0,`second`];for(let e of eh){if(e===`millisecond`)continue;let t=n[`${e}s`];if(t)return[t,e]}return[0,`second`]}var Q=function(e,t,n,r){if(n===`a`&&!r)throw TypeError(`Private accessor was defined without a getter`);if(typeof t==`function`?e!==t||!r:!t.has(e))throw TypeError(`Cannot read private member from an object whose class did not declare it`);return n===`m`?r:n===`a`?r.call(e):r?r.value:t.get(e)},sh=function(e,t,n,r,i){if(r===`m`)throw TypeError(`Private method is not writable`);if(r===`a`&&!i)throw TypeError(`Private accessor was defined without a setter`);if(typeof t==`function`?e!==t||!i:!t.has(e))throw TypeError(`Cannot write private member to an object whose class did not declare it`);return r===`a`?i.call(e,n):i?i.value=n:t.set(e,n),n},$,ch,lh,uh,dh,fh,ph,mh,hh,gh,_h,vh,yh,bh,xh,Sh,Ch=globalThis.HTMLElement||null,wh=new nh,Th=new nh(0,0,0,0,0,1),Eh=class extends Event{constructor(e,t,n,r){super(`relative-time-updated`,{bubbles:!0,composed:!0}),this.oldText=e,this.newText=t,this.oldTitle=n,this.newTitle=r}};function Dh(e){if(!e.date)return 1/0;if(e.format===`duration`||e.format===`elapsed`){let t=e.precision;if(t===`second`)return 1e3;if(t===`minute`)return 60*1e3}let t=Math.abs(Date.now()-e.date.getTime());return t<60*1e3?1e3:t<3600*1e3?60*1e3:3600*1e3}var Oh=new class{constructor(){this.elements=new Set,this.time=1/0,this.timer=-1}observe(e){if(this.elements.has(e))return;this.elements.add(e);let t=e.date;if(t&&t.getTime()){let t=Dh(e),n=Date.now()+t;n<this.time&&(clearTimeout(this.timer),this.timer=setTimeout(()=>this.update(),t),this.time=n)}}unobserve(e){this.elements.has(e)&&this.elements.delete(e)}update(){if(clearTimeout(this.timer),!this.elements.size)return;let e=1/0;for(let t of this.elements)e=Math.min(e,Dh(t)),t.update();this.time=Math.min(3600*1e3,e),this.timer=setTimeout(()=>this.update(),this.time),this.time+=Date.now()}},kh=class extends Ch{constructor(){super(...arguments),$.add(this),ch.set(this,!1),lh.set(this,!1),dh.set(this,this.shadowRoot?this.shadowRoot:this.attachShadow?this.attachShadow({mode:`open`}):this),Sh.set(this,null)}static define(e=`relative-time`,t=customElements){return t.define(e,this),this}get timeZone(){return this.closest(`[time-zone]`)?.getAttribute(`time-zone`)||this.ownerDocument.documentElement.getAttribute(`time-zone`)||void 0}static get observedAttributes(){return[`second`,`minute`,`hour`,`weekday`,`day`,`month`,`year`,`time-zone-name`,`prefix`,`threshold`,`tense`,`precision`,`format`,`format-style`,`no-title`,`datetime`,`lang`,`title`,`aria-hidden`,`time-zone`]}get onRelativeTimeUpdated(){return Q(this,Sh,`f`)}set onRelativeTimeUpdated(e){Q(this,Sh,`f`)&&this.removeEventListener(`relative-time-updated`,Q(this,Sh,`f`)),sh(this,Sh,typeof e==`object`||typeof e==`function`?e:null,`f`),typeof e==`function`&&this.addEventListener(`relative-time-updated`,e)}get second(){let e=this.getAttribute(`second`);if(e===`numeric`||e===`2-digit`)return e}set second(e){this.setAttribute(`second`,e||``)}get minute(){let e=this.getAttribute(`minute`);if(e===`numeric`||e===`2-digit`)return e}set minute(e){this.setAttribute(`minute`,e||``)}get hour(){let e=this.getAttribute(`hour`);if(e===`numeric`||e===`2-digit`)return e}set hour(e){this.setAttribute(`hour`,e||``)}get weekday(){let e=this.getAttribute(`weekday`);if(e===`long`||e===`short`||e===`narrow`)return e;if(this.format===`datetime`&&e!==``)return this.formatStyle}set weekday(e){this.setAttribute(`weekday`,e||``)}get day(){let e=this.getAttribute(`day`)??`numeric`;if(e===`numeric`||e===`2-digit`)return e}set day(e){this.setAttribute(`day`,e||``)}get month(){let e=this.format,t=this.getAttribute(`month`);if(t!==``&&(t??=e===`datetime`?this.formatStyle:`short`,t===`numeric`||t===`2-digit`||t===`short`||t===`long`||t===`narrow`))return t}set month(e){this.setAttribute(`month`,e||``)}get year(){let e=this.getAttribute(`year`);if(e===`numeric`||e===`2-digit`)return e;if(!this.hasAttribute(`year`)&&new Date().getUTCFullYear()!==this.date?.getUTCFullYear())return`numeric`}set year(e){this.setAttribute(`year`,e||``)}get timeZoneName(){let e=this.getAttribute(`time-zone-name`);if(e===`long`||e===`short`||e===`shortOffset`||e===`longOffset`||e===`shortGeneric`||e===`longGeneric`)return e}set timeZoneName(e){this.setAttribute(`time-zone-name`,e||``)}get prefix(){return this.getAttribute(`prefix`)??(this.format===`datetime`?``:`on`)}set prefix(e){this.setAttribute(`prefix`,e)}get threshold(){let e=this.getAttribute(`threshold`);return e&&th(e)?e:`P30D`}set threshold(e){this.setAttribute(`threshold`,e)}get tense(){let e=this.getAttribute(`tense`);return e===`past`?`past`:e===`future`?`future`:`auto`}set tense(e){this.setAttribute(`tense`,e)}get precision(){let e=this.getAttribute(`precision`);return eh.includes(e)?e:this.format===`micro`?`minute`:`second`}set precision(e){this.setAttribute(`precision`,e)}get format(){let e=this.getAttribute(`format`);return e===`datetime`?`datetime`:e===`relative`?`relative`:e===`duration`?`duration`:e===`micro`?`micro`:e===`elapsed`?`elapsed`:`auto`}set format(e){this.setAttribute(`format`,e)}get formatStyle(){let e=this.getAttribute(`format-style`);if(e===`long`)return`long`;if(e===`short`)return`short`;if(e===`narrow`)return`narrow`;let t=this.format;return t===`elapsed`||t===`micro`?`narrow`:t===`datetime`?`short`:`long`}set formatStyle(e){this.setAttribute(`format-style`,e)}get noTitle(){return this.hasAttribute(`no-title`)}set noTitle(e){this.toggleAttribute(`no-title`,e)}get datetime(){return this.getAttribute(`datetime`)||``}set datetime(e){this.setAttribute(`datetime`,e)}get date(){let e=Date.parse(this.datetime);return Number.isNaN(e)?null:new Date(e)}set date(e){this.datetime=e?.toISOString()||``}connectedCallback(){this.update()}disconnectedCallback(){Oh.unobserve(this)}attributeChangedCallback(e,t,n){t!==n&&(e===`title`&&sh(this,ch,n!==null&&(this.date&&Q(this,$,`m`,fh).call(this,this.date))!==n,`f`),!Q(this,lh,`f`)&&!(e===`title`&&Q(this,ch,`f`))&&sh(this,lh,(async()=>{await Promise.resolve(),this.update(),sh(this,lh,!1,`f`)})(),`f`))}update(){let e=Q(this,dh,`f`).textContent||this.textContent||``,t=this.getAttribute(`title`)||``,n=t,r=this.date;if(typeof Intl>`u`||!Intl.DateTimeFormat||!r){Q(this,dh,`f`).textContent=e;return}let i=Date.now();Q(this,ch,`f`)||(n=Q(this,$,`m`,fh).call(this,r)||``,n&&!this.noTitle&&this.setAttribute(`title`,n));let a=ih(r,this.precision,i),o=Q(this,$,`m`,ph).call(this,a),s=e,c=Q(this,$,`m`,xh).call(this,o);s=c?Q(this,$,`m`,yh).call(this,r):o===`duration`?Q(this,$,`m`,mh).call(this,a):o===`relative`?Q(this,$,`m`,hh).call(this,a):Q(this,$,`m`,gh).call(this,r),s?Q(this,$,`m`,bh).call(this,s):this.shadowRoot===Q(this,dh,`f`)&&this.textContent&&Q(this,$,`m`,bh).call(this,this.textContent),(s!==e||n!==t)&&this.dispatchEvent(new Eh(e,s,t,n)),o===`relative`||o===`duration`||c&&(Q(this,$,`m`,_h).call(this,r)||Q(this,$,`m`,vh).call(this,r))?Oh.observe(this):Oh.unobserve(this)}};ch=new WeakMap,lh=new WeakMap,dh=new WeakMap,Sh=new WeakMap,$=new WeakSet,uh=function(){let e=this.closest(`[lang]`)?.getAttribute(`lang`)||this.ownerDocument.documentElement.getAttribute(`lang`);try{return new Intl.Locale(e??``).toString()}catch{return`default`}},fh=function(e){return new Intl.DateTimeFormat(Q(this,$,`a`,uh),{day:`numeric`,month:`short`,year:`numeric`,hour:`numeric`,minute:`2-digit`,timeZoneName:`short`,timeZone:this.timeZone}).format(e)},ph=function(e){let t=this.format;if(t===`datetime`)return`datetime`;if(t===`duration`||t===`elapsed`||t===`micro`)return`duration`;if((t===`auto`||t===`relative`)&&typeof Intl<`u`&&Intl.RelativeTimeFormat){let t=this.tense;if(t===`past`||t===`future`||nh.compare(e,this.threshold)===1)return`relative`}return`datetime`},mh=function(e){let t=Q(this,$,`a`,uh),n=this.format,r=this.formatStyle,i=this.tense,a=wh;n===`micro`?(e=ah(e),a=Th,e.months===0&&(this.tense===`past`&&e.sign!==-1||this.tense===`future`&&e.sign!==1)&&(e=Th)):(i===`past`&&e.sign!==-1||i===`future`&&e.sign!==1)&&(e=a);let o=`${this.precision}sDisplay`;return e.blank?a.toLocaleString(t,{style:r,[o]:`always`}):e.abs().toLocaleString(t,{style:r})},hh=function(e){let t=new Intl.RelativeTimeFormat(Q(this,$,`a`,uh),{numeric:`auto`,style:this.formatStyle}),n=this.tense;n===`future`&&e.sign!==1&&(e=wh),n===`past`&&e.sign!==-1&&(e=wh);let[r,i]=oh(e);return i===`second`&&r<10?t.format(0,this.precision===`millisecond`?`second`:this.precision):t.format(r,i)},gh=function(e){let t=new Intl.DateTimeFormat(Q(this,$,`a`,uh),{second:this.second,minute:this.minute,hour:this.hour,weekday:this.weekday,day:this.day,month:this.month,year:this.year,timeZoneName:this.timeZoneName,timeZone:this.timeZone});return`${this.prefix} ${t.format(e)}`.trim()},_h=function(e){let t=new Date,n=new Intl.DateTimeFormat(Q(this,$,`a`,uh),{timeZone:this.timeZone,year:`numeric`,month:`2-digit`,day:`2-digit`});return n.format(t)===n.format(e)},vh=function(e){let t=new Date,n=new Intl.DateTimeFormat(Q(this,$,`a`,uh),{timeZone:this.timeZone,year:`numeric`});return n.format(t)===n.format(e)},yh=function(e){let t={hour:`numeric`,minute:`2-digit`,timeZoneName:`short`,timeZone:this.timeZone};if(Q(this,$,`m`,_h).call(this,e)){let n=new Intl.RelativeTimeFormat(Q(this,$,`a`,uh),{numeric:`auto`}).format(0,`day`);n=n.charAt(0).toLocaleUpperCase(Q(this,$,`a`,uh))+n.slice(1);let r=new Intl.DateTimeFormat(Q(this,$,`a`,uh),t).format(e);return`${n} ${r}`}let n=Object.assign(Object.assign({},t),{day:`numeric`,month:`short`});return Q(this,$,`m`,vh).call(this,e)?new Intl.DateTimeFormat(Q(this,$,`a`,uh),n).format(e):new Intl.DateTimeFormat(Q(this,$,`a`,uh),Object.assign(Object.assign({},n),{year:`numeric`})).format(e)},bh=function(e){if(this.hasAttribute(`aria-hidden`)&&this.getAttribute(`aria-hidden`)===`true`){let t=document.createElement(`span`);t.setAttribute(`aria-hidden`,`true`),t.textContent=e,Q(this,dh,`f`).replaceChildren(t)}else Q(this,dh,`f`).textContent=e},xh=function(e){return e===`duration`?!1:this.ownerDocument.documentElement.getAttribute(`data-prefers-absolute-time`)===`true`||this.ownerDocument.body?.getAttribute(`data-prefers-absolute-time`)===`true`};var Ah=typeof globalThis<`u`?globalThis:window;try{Ah.RelativeTimeElement=kh.define()}catch(e){if(!(Ah.DOMException&&e instanceof DOMException&&e.name===`NotSupportedError`)&&!(e instanceof ReferenceError))throw e}var jh=class extends Tu{constructor(...e){super(...e),this.autocorrect=!1,this.autocapitalize=`off`}static get styles(){return[...super.styles,d`
        .input-group__input {
          font-family: var(--c-font-mono);
          font-size: 0.9em;
        }
      `]}firstUpdated(e){super.firstUpdated(e),this._inputNode?.setAttribute(`autocorrect`,this.autocorrect?`on`:`off`),this._inputNode?.setAttribute(`autocapitalize`,this.autocapitalize)}};o([g({reflect:!0})],jh.prototype,`autocorrect`,void 0),o([g({reflect:!0,type:String})],jh.prototype,`autocapitalize`,void 0),customElements.get(`craft-input-handle`)||customElements.define(`craft-input-handle`,jh);var Mh=new Map,Nh=class extends h{constructor(...e){super(...e),this.returnUrl=``,this._state=`idle`}firstUpdated(){this._input?.focus()}async _onSubmit(e){e.preventDefault(),await this._submit(this._input?.value??``)}async _submit(e){if(this._state!==`loading`){this._state=`loading`;try{await gm.post(this.endpoint,{code:e}),this.dispatchEvent(new CustomEvent(`login-verified`,{bubbles:!0,composed:!0,detail:{returnUrl:this.returnUrl}})),this._state=`success`,setTimeout(()=>{this._state=`idle`},2e3)}catch(e){this._state=`error`,this.dispatchEvent(new CustomEvent(`login-failed`,{bubbles:!0,composed:!0,detail:{message:e?.response?.data?.message??n(`A server error occurred.`)}}))}}}renderInput(){return l`<slot name="input">
      <div>
        ${n(`Implement the renderInput() method or provide an input slot`)}
      </div>
    </slot>`}renderFields(){return l`
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
    `}render(){return l`
      <form
        class="login-form"
        accept-charset="UTF-8"
        @submit="${this._onSubmit}"
      >
        <div class="login-form__fields">${this.renderFields()}</div>
      </form>
    `}static register(e,t){customElements.get(e)||customElements.define(e,t),Mh.set(t.METHOD,t)}static isNative(e){return Mh.has(e)}};Nh.styles=[d`
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
    `],o([g({attribute:`return-url`})],Nh.prototype,`returnUrl`,void 0),o([_()],Nh.prototype,`_state`,void 0),o([y(`craft-input`)],Nh.prototype,`_input`,void 0),ji();var Ph={Á:`A`,á:`a`,Ä:`A`,ä:`a`,À:`A`,à:`a`,Â:`A`,â:`a`,É:`E`,é:`e`,Ë:`E`,ë:`e`,È:`E`,è:`e`,Ê:`E`,ê:`e`,Í:`I`,í:`i`,Ï:`I`,ï:`i`,Ì:`I`,ì:`i`,Î:`I`,î:`i`,Ó:`O`,ó:`o`,Ö:`O`,ö:`o`,Ò:`O`,ò:`o`,Ô:`O`,ô:`o`,Ú:`U`,ú:`u`,Ü:`U`,ü:`u`,Ù:`U`,ù:`u`,Û:`U`,û:`u`,Ý:`Y`,ý:`y`,Ÿ:`Y`,А:`A`,Б:`B`,В:`V`,Г:`G`,Д:`D`,Ѓ:`Gj`,Е:`E`,Ж:`Z`,З:`Z`,Ѕ:`Dz`,И:`I`,Ј:`j`,К:`K`,Л:`L`,Љ:`Lj`,М:`M`,Н:`N`,Њ:`Nj`,О:`O`,П:`P`,Р:`R`,С:`S`,Т:`T`,Ќ:`Kj`,У:`U`,Ф:`F`,Х:`X`,Ц:`C`,Ч:`C`,Џ:`Dz`,Ш:`S`,а:`a`,б:`b`,в:`v`,г:`g`,д:`d`,ѓ:`gj`,е:`e`,ж:`z`,з:`z`,ѕ:`dz`,и:`i`,ј:`j`,к:`k`,л:`l`,љ:`lj`,м:`m`,н:`n`,њ:`nj`,о:`o`,п:`p`,р:`r`,с:`s`,т:`t`,ќ:`kj`,у:`u`,ф:`f`,х:`x`,ц:`c`,ч:`c`,џ:`dz`,ш:`s`,æ:`ae`,ǽ:`ae`,Ã:`A`,Å:`A`,Ǻ:`A`,Ă:`A`,Ǎ:`A`,Æ:`AE`,Ǽ:`AE`,ã:`a`,å:`a`,ǻ:`a`,ă:`a`,ǎ:`a`,ª:`a`,Ĉ:`C`,Ċ:`C`,Ç:`C`,ç:`c`,ĉ:`c`,ċ:`c`,Ð:`D`,Đ:`D`,ð:`d`,đ:`d`,Ĕ:`E`,Ė:`E`,ĕ:`e`,ė:`e`,ƒ:`f`,Ĝ:`G`,Ġ:`G`,ĝ:`g`,ġ:`g`,Ĥ:`H`,Ħ:`H`,ĥ:`h`,ħ:`h`,Ĩ:`I`,Ĭ:`I`,Ǐ:`I`,Į:`I`,Ĳ:`IJ`,ĩ:`i`,ĭ:`i`,ǐ:`i`,į:`i`,ĳ:`ij`,Ĵ:`J`,ĵ:`j`,Ĺ:`L`,Ľ:`L`,Ŀ:`L`,ĺ:`l`,ľ:`l`,ŀ:`l`,Ñ:`N`,ñ:`n`,ŉ:`n`,Õ:`O`,Ō:`O`,Ŏ:`O`,Ǒ:`O`,Ő:`O`,Ơ:`O`,Ø:`O`,Ǿ:`O`,Œ:`OE`,õ:`o`,ō:`o`,ŏ:`o`,ǒ:`o`,ő:`o`,ơ:`o`,ø:`o`,ǿ:`o`,º:`o`,œ:`oe`,Ŕ:`R`,Ŗ:`R`,ŕ:`r`,ŗ:`r`,Ŝ:`S`,Ș:`S`,ŝ:`s`,ș:`s`,ſ:`s`,Ţ:`T`,Ț:`T`,Ŧ:`T`,Þ:`TH`,ţ:`t`,ț:`t`,ŧ:`t`,þ:`th`,Ũ:`U`,Ŭ:`U`,Ű:`U`,Ų:`U`,Ư:`U`,Ǔ:`U`,Ǖ:`U`,Ǘ:`U`,Ǚ:`U`,Ǜ:`U`,ũ:`u`,ŭ:`u`,ű:`u`,ų:`u`,ư:`u`,ǔ:`u`,ǖ:`u`,ǘ:`u`,ǚ:`u`,ǜ:`u`,Ŵ:`W`,ŵ:`w`,Ŷ:`Y`,ÿ:`y`,ŷ:`y`,ΑΥ:`AU`,ΑΎ:`AU`,Αυ:`Au`,Αύ:`Au`,ΕΊ:`I`,ΕΙ:`I`,Ει:`Ei`,ΕΥ:`EF`,ΕΎ:`EU`,Εί:`I`,Ευ:`Ef`,Εύ:`Eu`,ΟΙ:`I`,ΟΊ:`I`,ΟΥ:`U`,ΟΎ:`OU`,Οι:`Oi`,Οί:`I`,Ου:`Oy`,Ού:`Ou`,ΥΙ:`I`,ΎΙ:`I`,Υι:`Yi`,Ύι:`I`,ΥΊ:`I`,Υί:`I`,αυ:`au`,αύ:`au`,εί:`i`,ει:`ei`,ευ:`ef`,εύ:`eu`,οι:`oi`,οί:`i`,ου:`oy`,ού:`ou`,υι:`yi`,ύι:`i`,υί:`i`,Α:`A`,Ά:`A`,Β:`B`,Δ:`D`,Ε:`E`,Έ:`E`,Φ:`F`,Γ:`G`,Η:`H`,Ή:`I`,Ι:`I`,Ί:`I`,Ϊ:`I`,Κ:`K`,Ξ:`Ks`,Λ:`L`,Μ:`M`,Ν:`N`,Π:`P`,Ο:`O`,Ό:`O`,Ψ:`Ps`,Ρ:`R`,Σ:`S`,Τ:`T`,Θ:`Th`,Ω:`O`,Ώ:`W`,Χ:`X`,ϒ:`Y`,Υ:`Y`,Ύ:`Y`,Ϋ:`Y`,Ζ:`Z`,α:`a`,ά:`a`,β:`v`,δ:`d`,ε:`e`,έ:`e`,φ:`f`,γ:`gh`,η:`i`,ή:`i`,ι:`i`,ί:`i`,ϊ:`i`,ΐ:`i`,κ:`k`,ξ:`ks`,λ:`l`,μ:`m`,ν:`n`,ο:`o`,ό:`o`,π:`p`,ψ:`ps`,ρ:`r`,σ:`s`,ς:`s`,τ:`t`,ϑ:`th`,θ:`th`,ϐ:`v`,ω:`o`,ώ:`w`,χ:`kh`,υ:`i`,ύ:`y`,ΰ:`y`,ϋ:`y`,ζ:`z`,अ:`a`,आ:`aa`,ए:`e`,ई:`ii`,ऍ:`ei`,ऎ:`ae`,ऐ:`ai`,इ:`i`,ओ:`o`,ऑ:`oi`,ऒ:`oii`,ऊ:`uu`,औ:`ou`,उ:`u`,ब:`B`,भ:`Bha`,च:`Ca`,छ:`Chha`,ड:`Da`,ढ:`Dha`,फ:`Fa`,फ़:`Fi`,ग:`Ga`,घ:`Gha`,ग़:`Ghi`,ह:`Ha`,ज:`Ja`,झ:`Jha`,क:`Ka`,ख:`Kha`,ख़:`Khi`,ल:`L`,ळ:`Li`,ऌ:`Li`,ऴ:`Lii`,ॡ:`Lii`,म:`Ma`,न:`Na`,ङ:`Na`,ञ:`Nia`,ण:`Nae`,ऩ:`Ni`,ॐ:`oms`,प:`Pa`,क़:`Qi`,र:`Ra`,ऋ:`Ri`,ॠ:`Ri`,ऱ:`Ri`,स:`Sa`,श:`Sha`,ष:`Shha`,ट:`Ta`,त:`Ta`,ठ:`Tha`,द:`Tha`,थ:`Tha`,ध:`Thha`,ड़:`ugDha`,ढ़:`ugDhha`,व:`Va`,य:`Ya`,य़:`Yi`,ज़:`Za`,Ա:`A`,Բ:`B`,Գ:`G`,Դ:`D`,Ե:`E`,Զ:`Z`,Է:`E`,Ը:`Y`,Թ:`Th`,Ժ:`Zh`,Ի:`I`,Լ:`L`,Խ:`Kh`,Ծ:`Ts`,Կ:`K`,Հ:`H`,Ձ:`Dz`,Ղ:`Gh`,Ճ:`Tch`,Մ:`M`,Յ:`Y`,Ն:`N`,Շ:`Sh`,Ո:`Vo`,Չ:`Ch`,Պ:`P`,Ջ:`J`,Ռ:`R`,Ս:`S`,Վ:`V`,Տ:`T`,Ր:`R`,Ց:`C`,Ւ:`u`,Փ:`Ph`,Ք:`Q`,և:`ev`,Օ:`O`,Ֆ:`F`,ա:`a`,բ:`b`,գ:`g`,դ:`d`,ե:`e`,զ:`z`,է:`e`,ը:`y`,թ:`th`,ժ:`zh`,ի:`i`,լ:`l`,խ:`kh`,ծ:`ts`,կ:`k`,հ:`h`,ձ:`dz`,ղ:`gh`,ճ:`tch`,մ:`m`,յ:`y`,ն:`n`,շ:`sh`,ո:`vo`,չ:`ch`,պ:`p`,ջ:`j`,ռ:`r`,ս:`s`,վ:`v`,տ:`t`,ր:`r`,ց:`c`,ւ:`u`,փ:`ph`,ք:`q`,օ:`o`,ֆ:`f`,Ž:`Z`,Ň:`N`,Ş:`S`,ž:`z`,ň:`n`,ş:`s`,ı:`i`,İ:`I`,ğ:`g`,Ğ:`G`,ьо:`yo`,Й:`i`,Щ:`Shh`,Ъ:`Ie`,Ь:``,Ю:`Iu`,Я:`Ia`,й:`i`,щ:`shh`,ъ:`ie`,ь:``,ю:`iu`,я:`ia`,Ē:`E`,ē:`e`,န်ုပ်:`nub`,"ောင်":`aung`,"ိုက်":`aik`,"ိုဒ်":`ok`,"ိုင်":`aing`,"ိုလ်":`ol`,"ေါင်":`aung`,သြော:`aw`,"ောက်":`auk`,"ိတ်":`eik`,"ုတ်":`ok`,"ုန်":`on`,"ေတ်":`it`,"ုဒ်":`ait`,"ာန်":`an`,"ိန်":`ein`,"ွတ်":`ut`,"ေါ်":`aw`,"ွန်":`un`,"ိပ်":`eik`,"ုပ်":`ok`,"ွပ်":`ut`,"ိမ်":`ein`,"ုမ်":`on`,"ော်":`aw`,"ွမ်":`un`,က်:`et`,"ေါ":`aw`,"ော":`aw`,"ျွ":`ywa`,"ြွ":`yw`,"ို":`o`,"ုံ":`on`,တ်:`at`,င်:`in`,ည်:`i`,ဒ်:`d`,န်:`an`,ပ်:`at`,မ်:`an`,စျ:`za`,ယ်:`e`,ဉ်:`in`,စ်:`it`,"ိံ":`ein`,"ဲ":`e`,"း":``,"ာ":`a`,"ါ":`a`,"ေ":`e`,"ံ":`an`,"ိ":`i`,"ီ":`i`,"ု":`u`,"ူ":`u`,"်":`at`,"္":``,"့":``,က:`k`,"၉":`9`,တ:`t`,ရ:`ya`,ယ:`y`,မ:`m`,ဘ:`ba`,ဗ:`b`,ဖ:`pa`,ပ:`p`,န:`n`,ဓ:`da`,ဒ:`d`,ထ:`ta`,ဏ:`na`,ဝ:`w`,ဎ:`da`,ဍ:`d`,ဌ:`ta`,ဋ:`t`,ည:`ny`,ဇ:`z`,ဆ:`sa`,စ:`s`,င:`ng`,ဃ:`ga`,ဂ:`g`,လ:`l`,သ:`th`,"၈":`8`,ဩ:`aw`,ခ:`kh`,"၆":`6`,"၅":`5`,"၄":`4`,"၃":`3`,"၂":`2`,"၁":`1`,"၀":`0`,"၌":`hnaik`,"၍":`ywae`,ဪ:`aw`,ဦ:`-u`,ဟ:`h`,ဉ:`u`,ဤ:`-i`,ဣ:`i`,"၏":`-e`,ဧ:`e`,"ှ":`h`,"ွ":`w`,"ျ":`ya`,"ြ":`y`,အ:`a`,ဠ:`la`,"၇":`7`,DŽ:`DZ`,Dž:`Dz`,dž:`dz`,Ǳ:`DZ`,ǲ:`Dz`,ǳ:`dz`,Ǉ:`LJ`,ǈ:`Lj`,ǉ:`lj`,Ǌ:`NJ`,ǋ:`Nj`,ǌ:`nj`,č:`c`,Č:`C`,ć:`c`,Ć:`C`,š:`s`,Š:`S`,ა:`a`,ბ:`b`,გ:`g`,დ:`d`,ე:`e`,ვ:`v`,ზ:`z`,თ:`t`,ი:`i`,კ:`k`,ლ:`l`,მ:`m`,ნ:`n`,ო:`o`,პ:`p`,ჟ:`zh`,რ:`r`,ს:`s`,ტ:`t`,უ:`u`,ფ:`f`,ქ:`q`,ღ:`gh`,ყ:`y`,შ:`sh`,ჩ:`ch`,ც:`ts`,ძ:`dz`,წ:`ts`,ჭ:`ch`,ხ:`kh`,ჯ:`j`,ჰ:`h`,Ё:`E`,ё:`e`,Ы:`Y`,ы:`y`,Э:`E`,э:`e`,І:`I`,і:`i`,Ѳ:`F`,ѳ:`f`,Ѣ:`E`,ѣ:`e`,Ѵ:`I`,ѵ:`i`,Є:`Je`,є:`je`,Ѥ:`Je`,ѥ:`je`,Ꙋ:`U`,ꙋ:`u`,Ѡ:`O`,ѡ:`o`,Ѿ:`Ot`,ѿ:`ot`,Ѫ:`U`,ѫ:`u`,Ѧ:`Ja`,ѧ:`ja`,Ѭ:`Ju`,ѭ:`ju`,Ѩ:`Ja`,ѩ:`Ja`,Ѯ:`Ks`,ѯ:`ks`,Ѱ:`Ps`,ѱ:`ps`,Ґ:`G`,ґ:`g`,Ї:`Yi`,ї:`yi`,Ә:`A`,Ғ:`G`,Қ:`Q`,Ң:`N`,Ө:`O`,Ұ:`U`,Ү:`U`,Һ:`H`,ә:`a`,ғ:`g`,қ:`q`,ң:`n`,ө:`o`,ұ:`u`,ү:`u`,һ:`h`,ď:`d`,Ď:`D`,ě:`e`,Ě:`E`,ř:`r`,Ř:`R`,ť:`t`,Ť:`T`,ů:`u`,Ů:`U`,ą:`a`,ę:`e`,ł:`l`,ń:`n`,ś:`s`,ź:`z`,ż:`z`,Ą:`A`,Ę:`E`,Ł:`L`,Ń:`N`,Ś:`S`,Ź:`Z`,Ż:`Z`,ā:`a`,ģ:`g`,ī:`i`,ķ:`k`,ļ:`l`,ņ:`n`,ū:`u`,Ā:`A`,Ģ:`G`,Ī:`I`,Ķ:`k`,Ļ:`L`,Ņ:`N`,Ū:`U`,Ả:`A`,Ạ:`A`,Ắ:`A`,Ằ:`A`,Ẳ:`A`,Ẵ:`A`,Ặ:`A`,Ấ:`A`,Ầ:`A`,Ẩ:`A`,Ẫ:`A`,Ậ:`A`,ả:`a`,ạ:`a`,ắ:`a`,ằ:`a`,ẳ:`a`,ẵ:`a`,ặ:`a`,ấ:`a`,ầ:`a`,ẩ:`a`,ẫ:`a`,ậ:`a`,Ẻ:`E`,Ẽ:`E`,Ẹ:`E`,Ế:`E`,Ề:`E`,Ể:`E`,Ễ:`E`,Ệ:`E`,ẻ:`e`,ẽ:`e`,ẹ:`e`,ế:`e`,ề:`e`,ể:`e`,ễ:`e`,ệ:`e`,Ỉ:`I`,Ị:`I`,ỉ:`i`,ị:`i`,Ỏ:`O`,Ọ:`O`,Ố:`O`,Ồ:`O`,Ổ:`O`,Ỗ:`O`,Ộ:`O`,Ớ:`O`,Ờ:`O`,Ở:`O`,Ỡ:`O`,Ợ:`O`,ỏ:`o`,ọ:`o`,ố:`o`,ồ:`o`,ổ:`o`,ỗ:`o`,ộ:`o`,ớ:`o`,ờ:`o`,ở:`o`,ỡ:`o`,ợ:`o`,Ủ:`U`,Ụ:`U`,Ứ:`U`,Ừ:`U`,Ử:`U`,Ữ:`U`,Ự:`U`,ủ:`u`,ụ:`u`,ứ:`u`,ừ:`u`,ử:`u`,ữ:`u`,ự:`u`,Ỳ:`Y`,Ỷ:`Y`,Ỹ:`Y`,Ỵ:`Y`,ỳ:`y`,ỷ:`y`,ỹ:`y`,ỵ:`y`,ا:`a`,ب:`b`,پ:`p`,ت:`t`,ث:`th`,ج:`g`,چ:`ch`,ح:`h`,خ:`kh`,د:`d`,ذ:`th`,ر:`r`,ز:`z`,س:`s`,ش:`sh`,ص:`s`,ض:`d`,ط:`t`,ظ:`th`,ع:`aa`,غ:`gh`,ف:`f`,ق:`k`,ک:`k`,گ:`g`,ل:`l`,ژ:`zh`,ك:`k`,م:`m`,ن:`n`,ه:`h`,و:`o`,ی:`y`,آ:`a`,"٠":`0`,"١":`1`,"٢":`2`,"٣":`3`,"٤":`4`,"٥":`5`,"٦":`6`,"٧":`7`,"٨":`8`,"٩":`9`,أ:`a`,ي:`y`,إ:`a`,ؤ:`o`,ئ:`y`,ء:`aa`,ђ:`dj`,ћ:`c`,Ђ:`Dj`,Ћ:`C`,ə:`e`,Ə:`E`,ß:`ss`,ẞ:`SS`,ভ্ল:`vl`,পশ:`psh`,ব্ধ:`bdh`,ব্জ:`bj`,ব্দ:`bd`,ব্ব:`bb`,ব্ল:`bl`,ভ:`v`,ব:`b`,চ্ঞ:`cNG`,চ্ছ:`cch`,চ্চ:`cc`,ছ:`ch`,চ:`c`,ধ্ন:`dhn`,ধ্ম:`dhm`,দ্ঘ:`dgh`,দ্ধ:`ddh`,দ্ভ:`dv`,দ্ম:`dm`,ড্ড:`DD`,ঢ:`Dh`,ধ:`dh`,দ্গ:`dg`,দ্দ:`dd`,ড:`D`,দ:`d`,"।":`.`,ঘ্ন:`Ghn`,গ্ধ:`Gdh`,গ্ণ:`GN`,গ্ন:`Gn`,গ্ম:`Gm`,গ্ল:`Gl`,জ্ঞ:`jNG`,ঘ:`Gh`,গ:`g`,হ্ণ:`hN`,হ্ন:`hn`,হ্ম:`hm`,হ্ল:`hl`,হ:`h`,জ্ঝ:`jjh`,ঝ:`jh`,জ্জ:`jj`,জ:`j`,ক্ষ্ণ:`kxN`,ক্ষ্ম:`kxm`,ক্ষ:`ksh`,কশ:`ksh`,ক্ক:`kk`,ক্ট:`kT`,ক্ত:`kt`,ক্ল:`kl`,ক্স:`ks`,খ:`kh`,ক:`k`,ল্ভ:`lv`,ল্ধ:`ldh`,লখ:`lkh`,লঘ:`lgh`,লফ:`lph`,ল্ক:`lk`,ল্গ:`lg`,ল্ট:`lT`,ল্ড:`lD`,ল্প:`lp`,ল্ম:`lm`,ল্ল:`ll`,ল্ব:`lb`,ল:`l`,ম্থ:`mth`,ম্ফ:`mf`,ম্ভ:`mv`,মপ্ল:`mpl`,ম্ন:`mn`,ম্প:`mp`,ম্ম:`mm`,ম্ল:`ml`,ম্ব:`mb`,ম:`m`,"০":`0`,"১":`1`,"২":`2`,"৩":`3`,"৪":`4`,"৫":`5`,"৬":`6`,"৭":`7`,"৮":`8`,"৯":`9`,ঙ্ক্ষ:`Ngkx`,ঞ্ছ:`nch`,ঙ্ঘ:`ngh`,ঙ্খ:`nkh`,ঞ্ঝ:`njh`,ঙ্গৌ:`ngOU`,ঙ্গৈ:`ngOI`,ঞ্চ:`nc`,ঙ্ক:`nk`,ঙ্ষ:`Ngx`,ঙ্গ:`ngo`,ঙ্ম:`Ngm`,ঞ্জ:`nj`,ন্ধ:`ndh`,ন্ঠ:`nTh`,ণ্ঠ:`NTh`,ন্থ:`nth`,ঙ্গা:`nga`,ঙ্গি:`ngi`,ঙ্গী:`ngI`,ঙ্গু:`ngu`,ঙ্গূ:`ngU`,ঙ্গে:`nge`,ঙ্গো:`ngO`,ণ্ঢ:`NDh`,নশ:`nsh`,ঙর:`Ngr`,ঞর:`NGr`,"ংর":`ngr`,ঙ:`Ng`,ঞ:`NG`,"ং":`ng`,ন্ন:`nn`,ণ্ণ:`NN`,ণ্ন:`Nn`,ন্ম:`nm`,ণ্ম:`Nm`,ন্দ:`nd`,ন্ট:`nT`,ণ্ট:`NT`,ন্ড:`nD`,ণ্ড:`ND`,ন্ত:`nt`,ন্স:`ns`,ন:`n`,ণ:`N`,"ৈ":`OI`,"ৌ":`OU`,"ো":`O`,ঐ:`OI`,ঔ:`OU`,অ:`o`,ও:`oo`,ফ্ল:`fl`,প্ট:`pT`,প্ত:`pt`,প্ন:`pn`,প্প:`pp`,প্ল:`pl`,প্স:`ps`,ফ:`f`,প:`p`,"ৃ":`rri`,ঋ:`rri`,রর‍্য:`rry`,"্র্য":`ry`,"্রর":`rr`,ড়্গ:`Rg`,ঢ়:`Rh`,ড়:`R`,র:`r`,"্র":`r`,শ্ছ:`Sch`,ষ্ঠ:`ShTh`,ষ্ফ:`Shf`,স্ক্ল:`skl`,স্খ:`skh`,স্থ:`sth`,স্ফ:`sf`,শ্চ:`Sc`,শ্ত:`St`,শ্ন:`Sn`,শ্ম:`Sm`,শ্ল:`Sl`,ষ্ক:`Shk`,ষ্ট:`ShT`,ষ্ণ:`ShN`,ষ্প:`Shp`,ষ্ম:`Shm`,স্প্ল:`spl`,স্ক:`sk`,স্ট:`sT`,স্ত:`st`,স্ন:`sn`,স্প:`sp`,স্ম:`sm`,স্ল:`sl`,শ:`S`,ষ:`Sh`,স:`s`,"ু":`u`,উ:`u`,অ্য:`oZ`,ত্থ:`tth`,ৎ:`tt`,ট্ট:`TT`,ট্ম:`Tm`,ঠ:`Th`,ত্ন:`tn`,ত্ম:`tm`,থ:`th`,ত্ত:`tt`,ট:`T`,ত:`t`,অ্যা:`AZ`,"া":`a`,আ:`a`,য়া:`ya`,য়:`y`,"ি":`i`,ই:`i`,"ী":`ee`,ঈ:`ee`,"ূ":`uu`,ঊ:`uu`,"ে":`e`,এ:`e`,য:`z`,"্য":`Z`,ইয়:`y`,ওয়:`w`,"্ব":`w`,এক্স:`x`,"ঃ":`:`,"ঁ":`nn`,"্‌":``,"˚":`0`,"¹":`1`,"²":`2`,"³":`3`,"⁴":`4`,"⁵":`5`,"⁶":`6`,"⁷":`7`,"⁸":`8`,"⁹":`9`,"₀":`0`,"₁":`1`,"₂":`2`,"₃":`3`,"₄":`4`,"₅":`5`,"₆":`6`,"₇":`7`,"₈":`8`,"₉":`9`,"௦":`0`,"௧":`1`,"௨":`2`,"௩":`3`,"௪":`4`,"௫":`5`,"௬":`6`,"௭":`7`,"௮":`8`,"௯":`9`,"௰":`10`,"௱":`100`,"௲":`1000`,Ꜳ:`AA`,ꜳ:`aa`,Ꜵ:`AO`,ꜵ:`ao`,Ꜷ:`AU`,ꜷ:`au`,Ꜹ:`AV`,ꜹ:`av`,Ꜻ:`av`,ꜻ:`av`,Ꜽ:`AY`,ꜽ:`ay`,ȸ:`db`,ʣ:`dz`,ʥ:`dz`,ʤ:`dezh`,"🙰":`et`,ﬀ:`ff`,ﬃ:`ffi`,ﬄ:`ffl`,ﬁ:`fi`,ﬂ:`fl`,ʩ:`feng`,ʪ:`ls`,ʫ:`lz`,ɮ:`lezh`,ȹ:`qp`,ʨ:`tc`,ʦ:`ts`,ʧ:`tesh`,Ꝏ:`OO`,ꝏ:`oo`,ﬆ:`st`,ﬅ:`st`,Ꜩ:`TZ`,ꜩ:`tz`,ᵫ:`ue`,Aι:`Ai`,αι:`ai`,ἀ:`a`,ἁ:`a`,ἂ:`a`,ἃ:`a`,ἄ:`a`,ἅ:`a`,ἆ:`a`,ἇ:`a`,Ἀ:`A`,Ἁ:`A`,Ἂ:`A`,Ἃ:`A`,Ἄ:`A`,Ἅ:`A`,Ἆ:`A`,Ἇ:`A`,ᾰ:`a`,ᾱ:`a`,ᾲ:`a`,ᾳ:`a`,ᾴ:`a`,ᾶ:`a`,ᾷ:`a`,Ᾰ:`A`,Ᾱ:`A`,Ὰ:`A`,Ά:`A`,ᾼ:`A`,A̧:`A`,a̧:`a`,Ⱥ:`A`,ⱥ:`a`,Ȧ:`A`,ȧ:`a`,Ɓ:`B`,C̈:`C`,c̈:`c`,C̨:`C`,c̨:`c`,Ȼ:`C`,ȼ:`c`,C̀:`C`,c̀:`c`,C̣:`C`,c̣:`c`,C̄:`C`,c̄:`c`,C̃:`C`,c̃:`c`,Ȩ:`E`,ȩ:`e`,Ɇ:`E`,ɇ:`e`,I̧:`I`,i̧:`i`,Ɨ:`I`,ɨ:`i`,i:`i`,J́́:`J`,j́:`j`,J̀̀:`J`,j̀:`j`,J̈:`J`,j̈:`j`,J̧:`J`,j̧:`j`,J̨:`J`,j̨:`j`,Ɉ:`J`,ɉ:`j`,J̌:`J`,ǰ:`j`,J̇:`J`,j:`j`,J̣:`J`,j̣:`j`,J̄:`J`,j̄:`j`,J̃:`J`,j̃:`j`,ĸ:`k`,L̀:`L`,l̀:`l`,L̂:`L`,l̂:`l`,L̈:`L`,l̈:`l`,L̨:`L`,l̨:`l`,Ƚ:`L`,ƚ:`l`,L̇:`L`,l̇:`l`,Ḷ:`L`,ḷ:`l`,L̄:`L`,l̄:`l`,L̃:`L`,l̃:`l`,Ŋ:`N`,ŋ:`n`,Ǹ:`N`,ǹ:`n`,N̂:`N`,n̂:`n`,N̈:`N`,n̈:`n`,N̨:`N`,n̨:`n`,Ꞥ:`N`,ꞥ:`n`,Ṅ:`N`,ṅ:`n`,Ṇ:`N`,ṇ:`n`,N̄:`N`,n̄:`n`,O̧:`O`,o̧:`o`,Ǫ:`O`,ǫ:`o`,Ɵ:`O`,ɵ:`o`,Ȯ:`O`,ȯ:`o`,S̀:`S`,s̀:`s`,Ŝ̀:`S`,S̈:`S`,s̈:`s`,S̨:`S`,s̨:`s`,Ꞩ:`S`,ꞩ:`s`,Ṡ:`S`,ṡ:`s`,Ṣ:`S`,ṣ:`s`,S̄:`S`,s̄:`s`,S̃:`S`,s̃:`s`,T́:`T`,t́:`t`,T̀:`T`,t̀:`t`,T̂:`T`,t̂:`t`,T̈:`T`,ẗ:`t`,T̨:`T`,t̨:`t`,Ⱦ:`T`,ⱦ:`t`,Ṫ:`T`,ṫ:`t`,Ṭ:`T`,ṭ:`t`,T̄:`T`,t̄:`t`,T̃:`T`,t̃:`t`,U̧:`U`,u̧:`u`,Ʉ:`U`,ʉ:`u`,U̇:`U`,u̇:`u`,Ʊ:`U`,ʊ:`u`,Ẁ:`W`,ẁ:`w`,Ẃ:`W`,ẃ:`w`,Ẅ:`W`,ẅ:`w`,Ꙗ:`Ja`,ꙗ:`ja`,Y̧:`Y`,y̧:`y`,Y̨:`Y`,y̨:`y`,Ɏ:`Y`,ɏ:`y`,Y̌:`Y`,y̌:`y`,Ẏ:`Y`,ẏ:`y`,Ȳ:`Y`,ȳ:`y`,Z̀:`Z`,z̀:`z`,Ẑ:`Z`,ẑ:`z`,Z̈:`Z`,z̈:`z`,Z̧:`Z`,z̧:`z`,Z̨:`Z`,z̨:`z`,Ƶ:`Z`,ƶ:`z`,Ẓ:`Z`,ẓ:`z`,Z̄:`Z`,z̄:`z`,Z̃:`Z`,z̃:`z`,"\xA0":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `," ":` `,"\u2028":` `,"\u2029":` `,"​":` `," ":` `," ":` `,"　":` `,ﾠ:` `,"«":`<<`,"»":`>>`,"‘":`'`,"’":`'`,"‚":`'`,"‛":`'`,"“":`"`,"”":`"`,"„":`"`,"‟":`"`,"‹":`'`,"›":`'`,"–":`-`,"—":`-`,"…":`...`,"€":`EUR`,$:`$`,"₢":`Cr`,"₣":`Fr.`,"£":`PS`,"₤":`L.`,ℳ:`M`,"₥":`mil`,"₦":`N`,"₧":`Pts`,"₨":`Rs`,රු:`LKR`,ரூ:`LKR`,"௹":`Rs`,रू:`NPR`,"₹":`Rs`,"૱":`Rs`,"₩":`W`,"₪":`NS`,"₸":`KZT`,"₫":`D`,"֏":`AMD`,"₭":`K`,"₺":`TL`,"₼":`AZN`,"₮":`T`,"₯":`Dr`,"₲":`PYG`,"₾":`GEL`,"₳":`ARA`,"₴":`UAH`,"₽":`RUB`,"₵":`GHS`,"₡":`CL`,"¢":`c`,"¥":`YEN`,円:`JPY`,"৳":`BDT`,元:`CNY`,"﷼":`SAR`,"៛":`KR`,"₠":`ECU`,"¤":`$?`,"฿":`THB`,"؋":`AFN`};function Fh(e,t=Ph){e=e.normalize(`NFC`);let n=``,r;for(let i=0;i<e.length;i++)r=e.charAt(i),n+=typeof t[r]==`string`?t[r]:r;return n}function Ih(e,t={}){let n={allowNonAlphaStart:!1,handleCasing:`camel`,...t};var r=e.replace(/<(.*?)>/g,``);r=r.replace(/['"‘’“”ʻ\[\]\(\)\{\}:]/g,``),r=r.toLowerCase(),r=Fh(r),n.allowNonAlphaStart||(r=r.replace(/^[^a-z]+/,``));let i=r.split(/[^a-z0-9]+/).filter(Boolean);if(r=``,n.handleCasing===`snake`)return i.join(`_`);for(let e=0;e<i.length;e++)n.handleCasing!==`pascal`&&e===0?r+=i[e]:r+=i[e].charAt(0).toUpperCase()+i[e].substring(1);return r}function Lh(e,t={}){let n={prefix:``,suffix:``,...t},r=Ih(e,{handleCasing:`snake`}).toUpperCase();return r?`${n.prefix}${r}${n.suffix}`:``}function Rh(e){let t=e.replace(/<(.*?)>/g,``);return t=t.toLowerCase(),t=Fh(t),t=t.replace(/^[^a-z]+/,``),t=t.replace(/[^a-z0-9]+$/,``),t.split(/[^a-z0-9]+/).filter(Boolean).join(`-`)}function zh(e){return e.charAt(0).toUpperCase()+e.slice(1)}var Bh=null,Vh=null;async function Hh(e,t){let n=[],r=[],i=[],a=()=>{for(let e of n)e.parentNode?.removeChild(e);if(Bh)for(let e of r){let t=Bh.indexOf(e);t!==-1&&Bh.splice(t,1)}if(Vh)for(let e of i){let t=Vh.indexOf(e);t!==-1&&Vh.splice(t,1)}};if(!e)return a;let o=document.createElement(`div`);o.innerHTML=e.trim();let s=Array.from(o.childNodes);for(let e of s){if(e instanceof HTMLLinkElement&&e.href){Bh||=Array.from(document.querySelectorAll(`link[href]`)).map(e=>e.href.replace(/&/g,`&amp;`));let i=e.href.replace(/&/g,`&amp;`);if(Bh.includes(i))continue;Bh.push(i),r.push(i);let a=document.createElement(`link`);Array.from(e.attributes).forEach(e=>{a.setAttribute(e.name,e.value)}),t.appendChild(a),n.push(a);continue}if(e instanceof HTMLScriptElement){let r=document.createElement(`script`);if(Array.from(e.attributes).forEach(e=>{r.setAttribute(e.name,e.value)}),e.src){Vh||=Array.from(document.querySelectorAll(`script[src]`)).map(e=>e.src.replace(/&/g,`&amp;`));let t=e.src.replace(/&/g,`&amp;`);if(Vh.includes(t))continue;Vh.push(t),i.push(t),r.async=!1}else r.textContent=e.textContent;t.appendChild(r),n.push(r);continue}let a=e.cloneNode(!0);t.appendChild(a),n.push(a)}return a}async function Uh(e){return Hh(e,document.head)}async function Wh(e){return Hh(e,document.body)}export{ne as _,Ih as a,Nm as c,Ns as d,Tr as f,te as g,x as h,Rh as i,xm as l,ae as m,Uh as n,Lh as o,M as p,zh as r,Nh as s,Wh as t,gm as u};