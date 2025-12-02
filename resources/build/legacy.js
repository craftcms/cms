const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["./bg-BG.js","./bg.js","./cs-CZ.js","./cs.js","./de-DE.js","./de.js","./en-AU.js","./en.js","./en-GB.js","./en-US.js","./es-ES.js","./es.js","./fr-FR.js","./fr.js","./fr-BE.js","./hu-HU.js","./hu.js","./it-IT.js","./it.js","./nl-BE.js","./nl.js","./nl-NL.js","./pl-PL.js","./pl.js","./ro-RO.js","./ro.js","./ru-RU.js","./ru.js","./sk-SK.js","./sk.js","./tr-TR.js","./tr.js","./uk-UA.js","./uk.js"])))=>i.map(i=>d[i]);
const Oa="modulepreload",La=function(n,e){return new URL(n,e).href},Rs={},V=function(e,t,i){let s=Promise.resolve();if(t&&t.length>0){let d=function(c){return Promise.all(c.map(p=>Promise.resolve(p).then(m=>({status:"fulfilled",value:m}),m=>({status:"rejected",reason:m}))))};const r=document.getElementsByTagName("link"),a=document.querySelector("meta[property=csp-nonce]"),l=a?.nonce||a?.getAttribute("nonce");s=d(t.map(c=>{if(c=La(c,i),c in Rs)return;Rs[c]=!0;const p=c.endsWith(".css"),m=p?'[rel="stylesheet"]':"";if(i)for(let f=r.length-1;f>=0;f--){const g=r[f];if(g.href===c&&(!p||g.rel==="stylesheet"))return}else if(document.querySelector(`link[href="${c}"]${m}`))return;const b=document.createElement("link");if(b.rel=p?"stylesheet":Oa,p||(b.as="script"),b.crossOrigin="",b.href=c,l&&b.setAttribute("nonce",l),document.head.appendChild(b),p)return new Promise((f,g)=>{b.addEventListener("load",f),b.addEventListener("error",()=>g(new Error(`Unable to preload CSS for ${c}`)))})}))}function o(r){const a=new Event("vite:preloadError",{cancelable:!0});if(a.payload=r,window.dispatchEvent(a),!a.defaultPrevented)throw r}return s.then(r=>{for(const a of r||[])a.status==="rejected"&&o(a.reason);return e().catch(o)})};function Yo(n,e){return function(){return n.apply(e,arguments)}}const{toString:Fa}=Object.prototype,{getPrototypeOf:os}=Object,{iterator:jn,toStringTag:Xo}=Symbol,Wn=(n=>e=>{const t=Fa.call(e);return n[t]||(n[t]=t.slice(8,-1).toLowerCase())})(Object.create(null)),Oe=n=>(n=n.toLowerCase(),e=>Wn(e)===n),Kn=n=>e=>typeof e===n,{isArray:Nt}=Array,Et=Kn("undefined");function tn(n){return n!==null&&!Et(n)&&n.constructor!==null&&!Et(n.constructor)&&fe(n.constructor.isBuffer)&&n.constructor.isBuffer(n)}const Jo=Oe("ArrayBuffer");function Ra(n){let e;return typeof ArrayBuffer<"u"&&ArrayBuffer.isView?e=ArrayBuffer.isView(n):e=n&&n.buffer&&Jo(n.buffer),e}const Ma=Kn("string"),fe=Kn("function"),Zo=Kn("number"),nn=n=>n!==null&&typeof n=="object",Da=n=>n===!0||n===!1,kn=n=>{if(Wn(n)!=="object")return!1;const e=os(n);return(e===null||e===Object.prototype||Object.getPrototypeOf(e)===null)&&!(Xo in n)&&!(jn in n)},Ia=n=>{if(!nn(n)||tn(n))return!1;try{return Object.keys(n).length===0&&Object.getPrototypeOf(n)===Object.prototype}catch{return!1}},Pa=Oe("Date"),za=Oe("File"),Va=Oe("Blob"),Ba=Oe("FileList"),Ua=n=>nn(n)&&fe(n.pipe),Ha=n=>{let e;return n&&(typeof FormData=="function"&&n instanceof FormData||fe(n.append)&&((e=Wn(n))==="formdata"||e==="object"&&fe(n.toString)&&n.toString()==="[object FormData]"))},qa=Oe("URLSearchParams"),[ja,Wa,Ka,Ga]=["ReadableStream","Request","Response","Headers"].map(Oe),Ya=n=>n.trim?n.trim():n.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g,"");function sn(n,e,{allOwnKeys:t=!1}={}){if(n===null||typeof n>"u")return;let i,s;if(typeof n!="object"&&(n=[n]),Nt(n))for(i=0,s=n.length;i<s;i++)e.call(null,n[i],i,n);else{if(tn(n))return;const o=t?Object.getOwnPropertyNames(n):Object.keys(n),r=o.length;let a;for(i=0;i<r;i++)a=o[i],e.call(null,n[a],a,n)}}function Qo(n,e){if(tn(n))return null;e=e.toLowerCase();const t=Object.keys(n);let i=t.length,s;for(;i-- >0;)if(s=t[i],e===s.toLowerCase())return s;return null}const st=typeof globalThis<"u"?globalThis:typeof self<"u"?self:typeof window<"u"?window:global,er=n=>!Et(n)&&n!==st;function Di(){const{caseless:n,skipUndefined:e}=er(this)&&this||{},t={},i=(s,o)=>{const r=n&&Qo(t,o)||o;kn(t[r])&&kn(s)?t[r]=Di(t[r],s):kn(s)?t[r]=Di({},s):Nt(s)?t[r]=s.slice():(!e||!Et(s))&&(t[r]=s)};for(let s=0,o=arguments.length;s<o;s++)arguments[s]&&sn(arguments[s],i);return t}const Xa=(n,e,t,{allOwnKeys:i}={})=>(sn(e,(s,o)=>{t&&fe(s)?n[o]=Yo(s,t):n[o]=s},{allOwnKeys:i}),n),Ja=n=>(n.charCodeAt(0)===65279&&(n=n.slice(1)),n),Za=(n,e,t,i)=>{n.prototype=Object.create(e.prototype,i),n.prototype.constructor=n,Object.defineProperty(n,"super",{value:e.prototype}),t&&Object.assign(n.prototype,t)},Qa=(n,e,t,i)=>{let s,o,r;const a={};if(e=e||{},n==null)return e;do{for(s=Object.getOwnPropertyNames(n),o=s.length;o-- >0;)r=s[o],(!i||i(r,n,e))&&!a[r]&&(e[r]=n[r],a[r]=!0);n=t!==!1&&os(n)}while(n&&(!t||t(n,e))&&n!==Object.prototype);return e},el=(n,e,t)=>{n=String(n),(t===void 0||t>n.length)&&(t=n.length),t-=e.length;const i=n.indexOf(e,t);return i!==-1&&i===t},tl=n=>{if(!n)return null;if(Nt(n))return n;let e=n.length;if(!Zo(e))return null;const t=new Array(e);for(;e-- >0;)t[e]=n[e];return t},nl=(n=>e=>n&&e instanceof n)(typeof Uint8Array<"u"&&os(Uint8Array)),il=(n,e)=>{const i=(n&&n[jn]).call(n);let s;for(;(s=i.next())&&!s.done;){const o=s.value;e.call(n,o[0],o[1])}},sl=(n,e)=>{let t;const i=[];for(;(t=n.exec(e))!==null;)i.push(t);return i},ol=Oe("HTMLFormElement"),rl=n=>n.toLowerCase().replace(/[-_\s]([a-z\d])(\w*)/g,function(t,i,s){return i.toUpperCase()+s}),Ms=(({hasOwnProperty:n})=>(e,t)=>n.call(e,t))(Object.prototype),al=Oe("RegExp"),tr=(n,e)=>{const t=Object.getOwnPropertyDescriptors(n),i={};sn(t,(s,o)=>{let r;(r=e(s,o,n))!==!1&&(i[o]=r||s)}),Object.defineProperties(n,i)},ll=n=>{tr(n,(e,t)=>{if(fe(n)&&["arguments","caller","callee"].indexOf(t)!==-1)return!1;const i=n[t];if(fe(i)){if(e.enumerable=!1,"writable"in e){e.writable=!1;return}e.set||(e.set=()=>{throw Error("Can not rewrite read-only method '"+t+"'")})}})},cl=(n,e)=>{const t={},i=s=>{s.forEach(o=>{t[o]=!0})};return Nt(n)?i(n):i(String(n).split(e)),t},dl=()=>{},ul=(n,e)=>n!=null&&Number.isFinite(n=+n)?n:e;function hl(n){return!!(n&&fe(n.append)&&n[Xo]==="FormData"&&n[jn])}const pl=n=>{const e=new Array(10),t=(i,s)=>{if(nn(i)){if(e.indexOf(i)>=0)return;if(tn(i))return i;if(!("toJSON"in i)){e[s]=i;const o=Nt(i)?[]:{};return sn(i,(r,a)=>{const l=t(r,s+1);!Et(l)&&(o[a]=l)}),e[s]=void 0,o}}return i};return t(n,0)},fl=Oe("AsyncFunction"),ml=n=>n&&(nn(n)||fe(n))&&fe(n.then)&&fe(n.catch),nr=((n,e)=>n?setImmediate:e?((t,i)=>(st.addEventListener("message",({source:s,data:o})=>{s===st&&o===t&&i.length&&i.shift()()},!1),s=>{i.push(s),st.postMessage(t,"*")}))(`axios@${Math.random()}`,[]):t=>setTimeout(t))(typeof setImmediate=="function",fe(st.postMessage)),bl=typeof queueMicrotask<"u"?queueMicrotask.bind(st):typeof process<"u"&&process.nextTick||nr,gl=n=>n!=null&&fe(n[jn]),_={isArray:Nt,isArrayBuffer:Jo,isBuffer:tn,isFormData:Ha,isArrayBufferView:Ra,isString:Ma,isNumber:Zo,isBoolean:Da,isObject:nn,isPlainObject:kn,isEmptyObject:Ia,isReadableStream:ja,isRequest:Wa,isResponse:Ka,isHeaders:Ga,isUndefined:Et,isDate:Pa,isFile:za,isBlob:Va,isRegExp:al,isFunction:fe,isStream:Ua,isURLSearchParams:qa,isTypedArray:nl,isFileList:Ba,forEach:sn,merge:Di,extend:Xa,trim:Ya,stripBOM:Ja,inherits:Za,toFlatObject:Qa,kindOf:Wn,kindOfTest:Oe,endsWith:el,toArray:tl,forEachEntry:il,matchAll:sl,isHTMLForm:ol,hasOwnProperty:Ms,hasOwnProp:Ms,reduceDescriptors:tr,freezeMethods:ll,toObjectSet:cl,toCamelCase:rl,noop:dl,toFiniteNumber:ul,findKey:Qo,global:st,isContextDefined:er,isSpecCompliantForm:hl,toJSONObject:pl,isAsyncFn:fl,isThenable:ml,setImmediate:nr,asap:bl,isIterable:gl};function z(n,e,t,i,s){Error.call(this),Error.captureStackTrace?Error.captureStackTrace(this,this.constructor):this.stack=new Error().stack,this.message=n,this.name="AxiosError",e&&(this.code=e),t&&(this.config=t),i&&(this.request=i),s&&(this.response=s,this.status=s.status?s.status:null)}_.inherits(z,Error,{toJSON:function(){return{message:this.message,name:this.name,description:this.description,number:this.number,fileName:this.fileName,lineNumber:this.lineNumber,columnNumber:this.columnNumber,stack:this.stack,config:_.toJSONObject(this.config),code:this.code,status:this.status}}});const ir=z.prototype,sr={};["ERR_BAD_OPTION_VALUE","ERR_BAD_OPTION","ECONNABORTED","ETIMEDOUT","ERR_NETWORK","ERR_FR_TOO_MANY_REDIRECTS","ERR_DEPRECATED","ERR_BAD_RESPONSE","ERR_BAD_REQUEST","ERR_CANCELED","ERR_NOT_SUPPORT","ERR_INVALID_URL"].forEach(n=>{sr[n]={value:n}});Object.defineProperties(z,sr);Object.defineProperty(ir,"isAxiosError",{value:!0});z.from=(n,e,t,i,s,o)=>{const r=Object.create(ir);_.toFlatObject(n,r,function(c){return c!==Error.prototype},d=>d!=="isAxiosError");const a=n&&n.message?n.message:"Error",l=e==null&&n?n.code:e;return z.call(r,a,l,t,i,s),n&&r.cause==null&&Object.defineProperty(r,"cause",{value:n,configurable:!0}),r.name=n&&n.name||"Error",o&&Object.assign(r,o),r};const vl=null;function Ii(n){return _.isPlainObject(n)||_.isArray(n)}function or(n){return _.endsWith(n,"[]")?n.slice(0,-2):n}function Ds(n,e,t){return n?n.concat(e).map(function(s,o){return s=or(s),!t&&o?"["+s+"]":s}).join(t?".":""):e}function yl(n){return _.isArray(n)&&!n.some(Ii)}const _l=_.toFlatObject(_,{},null,function(e){return/^is[A-Z]/.test(e)});function Gn(n,e,t){if(!_.isObject(n))throw new TypeError("target must be an object");e=e||new FormData,t=_.toFlatObject(t,{metaTokens:!0,dots:!1,indexes:!1},!1,function(g,y){return!_.isUndefined(y[g])});const i=t.metaTokens,s=t.visitor||c,o=t.dots,r=t.indexes,l=(t.Blob||typeof Blob<"u"&&Blob)&&_.isSpecCompliantForm(e);if(!_.isFunction(s))throw new TypeError("visitor must be a function");function d(f){if(f===null)return"";if(_.isDate(f))return f.toISOString();if(_.isBoolean(f))return f.toString();if(!l&&_.isBlob(f))throw new z("Blob is not supported. Use a Buffer instead.");return _.isArrayBuffer(f)||_.isTypedArray(f)?l&&typeof Blob=="function"?new Blob([f]):Buffer.from(f):f}function c(f,g,y){let E=f;if(f&&!y&&typeof f=="object"){if(_.endsWith(g,"{}"))g=i?g:g.slice(0,-2),f=JSON.stringify(f);else if(_.isArray(f)&&yl(f)||(_.isFileList(f)||_.endsWith(g,"[]"))&&(E=_.toArray(f)))return g=or(g),E.forEach(function(A,S){!(_.isUndefined(A)||A===null)&&e.append(r===!0?Ds([g],S,o):r===null?g:g+"[]",d(A))}),!1}return Ii(f)?!0:(e.append(Ds(y,g,o),d(f)),!1)}const p=[],m=Object.assign(_l,{defaultVisitor:c,convertValue:d,isVisitable:Ii});function b(f,g){if(!_.isUndefined(f)){if(p.indexOf(f)!==-1)throw Error("Circular reference detected in "+g.join("."));p.push(f),_.forEach(f,function(E,C){(!(_.isUndefined(E)||E===null)&&s.call(e,E,_.isString(C)?C.trim():C,g,m))===!0&&b(E,g?g.concat(C):[C])}),p.pop()}}if(!_.isObject(n))throw new TypeError("data must be an object");return b(n),e}function Is(n){const e={"!":"%21","'":"%27","(":"%28",")":"%29","~":"%7E","%20":"+","%00":"\0"};return encodeURIComponent(n).replace(/[!'()~]|%20|%00/g,function(i){return e[i]})}function rs(n,e){this._pairs=[],n&&Gn(n,this,e)}const rr=rs.prototype;rr.append=function(e,t){this._pairs.push([e,t])};rr.toString=function(e){const t=e?function(i){return e.call(this,i,Is)}:Is;return this._pairs.map(function(s){return t(s[0])+"="+t(s[1])},"").join("&")};function wl(n){return encodeURIComponent(n).replace(/%3A/gi,":").replace(/%24/g,"$").replace(/%2C/gi,",").replace(/%20/g,"+")}function ar(n,e,t){if(!e)return n;const i=t&&t.encode||wl;_.isFunction(t)&&(t={serialize:t});const s=t&&t.serialize;let o;if(s?o=s(e,t):o=_.isURLSearchParams(e)?e.toString():new rs(e,t).toString(i),o){const r=n.indexOf("#");r!==-1&&(n=n.slice(0,r)),n+=(n.indexOf("?")===-1?"?":"&")+o}return n}class Ps{constructor(){this.handlers=[]}use(e,t,i){return this.handlers.push({fulfilled:e,rejected:t,synchronous:i?i.synchronous:!1,runWhen:i?i.runWhen:null}),this.handlers.length-1}eject(e){this.handlers[e]&&(this.handlers[e]=null)}clear(){this.handlers&&(this.handlers=[])}forEach(e){_.forEach(this.handlers,function(i){i!==null&&e(i)})}}const lr={silentJSONParsing:!0,forcedJSONParsing:!0,clarifyTimeoutError:!1},El=typeof URLSearchParams<"u"?URLSearchParams:rs,xl=typeof FormData<"u"?FormData:null,kl=typeof Blob<"u"?Blob:null,Cl={isBrowser:!0,classes:{URLSearchParams:El,FormData:xl,Blob:kl},protocols:["http","https","file","blob","url","data"]},as=typeof window<"u"&&typeof document<"u",Pi=typeof navigator=="object"&&navigator||void 0,Sl=as&&(!Pi||["ReactNative","NativeScript","NS"].indexOf(Pi.product)<0),Al=typeof WorkerGlobalScope<"u"&&self instanceof WorkerGlobalScope&&typeof self.importScripts=="function",Tl=as&&window.location.href||"http://localhost",Nl=Object.freeze(Object.defineProperty({__proto__:null,hasBrowserEnv:as,hasStandardBrowserEnv:Sl,hasStandardBrowserWebWorkerEnv:Al,navigator:Pi,origin:Tl},Symbol.toStringTag,{value:"Module"})),ce={...Nl,...Cl};function $l(n,e){return Gn(n,new ce.classes.URLSearchParams,{visitor:function(t,i,s,o){return ce.isNode&&_.isBuffer(t)?(this.append(i,t.toString("base64")),!1):o.defaultVisitor.apply(this,arguments)},...e})}function Ol(n){return _.matchAll(/\w+|\[(\w*)]/g,n).map(e=>e[0]==="[]"?"":e[1]||e[0])}function Ll(n){const e={},t=Object.keys(n);let i;const s=t.length;let o;for(i=0;i<s;i++)o=t[i],e[o]=n[o];return e}function cr(n){function e(t,i,s,o){let r=t[o++];if(r==="__proto__")return!0;const a=Number.isFinite(+r),l=o>=t.length;return r=!r&&_.isArray(s)?s.length:r,l?(_.hasOwnProp(s,r)?s[r]=[s[r],i]:s[r]=i,!a):((!s[r]||!_.isObject(s[r]))&&(s[r]=[]),e(t,i,s[r],o)&&_.isArray(s[r])&&(s[r]=Ll(s[r])),!a)}if(_.isFormData(n)&&_.isFunction(n.entries)){const t={};return _.forEachEntry(n,(i,s)=>{e(Ol(i),s,t,0)}),t}return null}function Fl(n,e,t){if(_.isString(n))try{return(e||JSON.parse)(n),_.trim(n)}catch(i){if(i.name!=="SyntaxError")throw i}return(t||JSON.stringify)(n)}const on={transitional:lr,adapter:["xhr","http","fetch"],transformRequest:[function(e,t){const i=t.getContentType()||"",s=i.indexOf("application/json")>-1,o=_.isObject(e);if(o&&_.isHTMLForm(e)&&(e=new FormData(e)),_.isFormData(e))return s?JSON.stringify(cr(e)):e;if(_.isArrayBuffer(e)||_.isBuffer(e)||_.isStream(e)||_.isFile(e)||_.isBlob(e)||_.isReadableStream(e))return e;if(_.isArrayBufferView(e))return e.buffer;if(_.isURLSearchParams(e))return t.setContentType("application/x-www-form-urlencoded;charset=utf-8",!1),e.toString();let a;if(o){if(i.indexOf("application/x-www-form-urlencoded")>-1)return $l(e,this.formSerializer).toString();if((a=_.isFileList(e))||i.indexOf("multipart/form-data")>-1){const l=this.env&&this.env.FormData;return Gn(a?{"files[]":e}:e,l&&new l,this.formSerializer)}}return o||s?(t.setContentType("application/json",!1),Fl(e)):e}],transformResponse:[function(e){const t=this.transitional||on.transitional,i=t&&t.forcedJSONParsing,s=this.responseType==="json";if(_.isResponse(e)||_.isReadableStream(e))return e;if(e&&_.isString(e)&&(i&&!this.responseType||s)){const r=!(t&&t.silentJSONParsing)&&s;try{return JSON.parse(e,this.parseReviver)}catch(a){if(r)throw a.name==="SyntaxError"?z.from(a,z.ERR_BAD_RESPONSE,this,null,this.response):a}}return e}],timeout:0,xsrfCookieName:"XSRF-TOKEN",xsrfHeaderName:"X-XSRF-TOKEN",maxContentLength:-1,maxBodyLength:-1,env:{FormData:ce.classes.FormData,Blob:ce.classes.Blob},validateStatus:function(e){return e>=200&&e<300},headers:{common:{Accept:"application/json, text/plain, */*","Content-Type":void 0}}};_.forEach(["delete","get","head","post","put","patch"],n=>{on.headers[n]={}});const Rl=_.toObjectSet(["age","authorization","content-length","content-type","etag","expires","from","host","if-modified-since","if-unmodified-since","last-modified","location","max-forwards","proxy-authorization","referer","retry-after","user-agent"]),Ml=n=>{const e={};let t,i,s;return n&&n.split(`
`).forEach(function(r){s=r.indexOf(":"),t=r.substring(0,s).trim().toLowerCase(),i=r.substring(s+1).trim(),!(!t||e[t]&&Rl[t])&&(t==="set-cookie"?e[t]?e[t].push(i):e[t]=[i]:e[t]=e[t]?e[t]+", "+i:i)}),e},zs=Symbol("internals");function Dt(n){return n&&String(n).trim().toLowerCase()}function Cn(n){return n===!1||n==null?n:_.isArray(n)?n.map(Cn):String(n)}function Dl(n){const e=Object.create(null),t=/([^\s,;=]+)\s*(?:=\s*([^,;]+))?/g;let i;for(;i=t.exec(n);)e[i[1]]=i[2];return e}const Il=n=>/^[-_a-zA-Z0-9^`|~,!#$%&'*+.]+$/.test(n.trim());function ci(n,e,t,i,s){if(_.isFunction(i))return i.call(this,e,t);if(s&&(e=t),!!_.isString(e)){if(_.isString(i))return e.indexOf(i)!==-1;if(_.isRegExp(i))return i.test(e)}}function Pl(n){return n.trim().toLowerCase().replace(/([a-z\d])(\w*)/g,(e,t,i)=>t.toUpperCase()+i)}function zl(n,e){const t=_.toCamelCase(" "+e);["get","set","has"].forEach(i=>{Object.defineProperty(n,i+t,{value:function(s,o,r){return this[i].call(this,e,s,o,r)},configurable:!0})})}let me=class{constructor(e){e&&this.set(e)}set(e,t,i){const s=this;function o(a,l,d){const c=Dt(l);if(!c)throw new Error("header name must be a non-empty string");const p=_.findKey(s,c);(!p||s[p]===void 0||d===!0||d===void 0&&s[p]!==!1)&&(s[p||l]=Cn(a))}const r=(a,l)=>_.forEach(a,(d,c)=>o(d,c,l));if(_.isPlainObject(e)||e instanceof this.constructor)r(e,t);else if(_.isString(e)&&(e=e.trim())&&!Il(e))r(Ml(e),t);else if(_.isObject(e)&&_.isIterable(e)){let a={},l,d;for(const c of e){if(!_.isArray(c))throw TypeError("Object iterator must return a key-value pair");a[d=c[0]]=(l=a[d])?_.isArray(l)?[...l,c[1]]:[l,c[1]]:c[1]}r(a,t)}else e!=null&&o(t,e,i);return this}get(e,t){if(e=Dt(e),e){const i=_.findKey(this,e);if(i){const s=this[i];if(!t)return s;if(t===!0)return Dl(s);if(_.isFunction(t))return t.call(this,s,i);if(_.isRegExp(t))return t.exec(s);throw new TypeError("parser must be boolean|regexp|function")}}}has(e,t){if(e=Dt(e),e){const i=_.findKey(this,e);return!!(i&&this[i]!==void 0&&(!t||ci(this,this[i],i,t)))}return!1}delete(e,t){const i=this;let s=!1;function o(r){if(r=Dt(r),r){const a=_.findKey(i,r);a&&(!t||ci(i,i[a],a,t))&&(delete i[a],s=!0)}}return _.isArray(e)?e.forEach(o):o(e),s}clear(e){const t=Object.keys(this);let i=t.length,s=!1;for(;i--;){const o=t[i];(!e||ci(this,this[o],o,e,!0))&&(delete this[o],s=!0)}return s}normalize(e){const t=this,i={};return _.forEach(this,(s,o)=>{const r=_.findKey(i,o);if(r){t[r]=Cn(s),delete t[o];return}const a=e?Pl(o):String(o).trim();a!==o&&delete t[o],t[a]=Cn(s),i[a]=!0}),this}concat(...e){return this.constructor.concat(this,...e)}toJSON(e){const t=Object.create(null);return _.forEach(this,(i,s)=>{i!=null&&i!==!1&&(t[s]=e&&_.isArray(i)?i.join(", "):i)}),t}[Symbol.iterator](){return Object.entries(this.toJSON())[Symbol.iterator]()}toString(){return Object.entries(this.toJSON()).map(([e,t])=>e+": "+t).join(`
`)}getSetCookie(){return this.get("set-cookie")||[]}get[Symbol.toStringTag](){return"AxiosHeaders"}static from(e){return e instanceof this?e:new this(e)}static concat(e,...t){const i=new this(e);return t.forEach(s=>i.set(s)),i}static accessor(e){const i=(this[zs]=this[zs]={accessors:{}}).accessors,s=this.prototype;function o(r){const a=Dt(r);i[a]||(zl(s,r),i[a]=!0)}return _.isArray(e)?e.forEach(o):o(e),this}};me.accessor(["Content-Type","Content-Length","Accept","Accept-Encoding","User-Agent","Authorization"]);_.reduceDescriptors(me.prototype,({value:n},e)=>{let t=e[0].toUpperCase()+e.slice(1);return{get:()=>n,set(i){this[t]=i}}});_.freezeMethods(me);function di(n,e){const t=this||on,i=e||t,s=me.from(i.headers);let o=i.data;return _.forEach(n,function(a){o=a.call(t,o,s.normalize(),e?e.status:void 0)}),s.normalize(),o}function dr(n){return!!(n&&n.__CANCEL__)}function $t(n,e,t){z.call(this,n??"canceled",z.ERR_CANCELED,e,t),this.name="CanceledError"}_.inherits($t,z,{__CANCEL__:!0});function ur(n,e,t){const i=t.config.validateStatus;!t.status||!i||i(t.status)?n(t):e(new z("Request failed with status code "+t.status,[z.ERR_BAD_REQUEST,z.ERR_BAD_RESPONSE][Math.floor(t.status/100)-4],t.config,t.request,t))}function Vl(n){const e=/^([-+\w]{1,25})(:?\/\/|:)/.exec(n);return e&&e[1]||""}function Bl(n,e){n=n||10;const t=new Array(n),i=new Array(n);let s=0,o=0,r;return e=e!==void 0?e:1e3,function(l){const d=Date.now(),c=i[o];r||(r=d),t[s]=l,i[s]=d;let p=o,m=0;for(;p!==s;)m+=t[p++],p=p%n;if(s=(s+1)%n,s===o&&(o=(o+1)%n),d-r<e)return;const b=c&&d-c;return b?Math.round(m*1e3/b):void 0}}function Ul(n,e){let t=0,i=1e3/e,s,o;const r=(d,c=Date.now())=>{t=c,s=null,o&&(clearTimeout(o),o=null),n(...d)};return[(...d)=>{const c=Date.now(),p=c-t;p>=i?r(d,c):(s=d,o||(o=setTimeout(()=>{o=null,r(s)},i-p)))},()=>s&&r(s)]}const Fn=(n,e,t=3)=>{let i=0;const s=Bl(50,250);return Ul(o=>{const r=o.loaded,a=o.lengthComputable?o.total:void 0,l=r-i,d=s(l),c=r<=a;i=r;const p={loaded:r,total:a,progress:a?r/a:void 0,bytes:l,rate:d||void 0,estimated:d&&a&&c?(a-r)/d:void 0,event:o,lengthComputable:a!=null,[e?"download":"upload"]:!0};n(p)},t)},Vs=(n,e)=>{const t=n!=null;return[i=>e[0]({lengthComputable:t,total:n,loaded:i}),e[1]]},Bs=n=>(...e)=>_.asap(()=>n(...e)),Hl=ce.hasStandardBrowserEnv?((n,e)=>t=>(t=new URL(t,ce.origin),n.protocol===t.protocol&&n.host===t.host&&(e||n.port===t.port)))(new URL(ce.origin),ce.navigator&&/(msie|trident)/i.test(ce.navigator.userAgent)):()=>!0,ql=ce.hasStandardBrowserEnv?{write(n,e,t,i,s,o,r){if(typeof document>"u")return;const a=[`${n}=${encodeURIComponent(e)}`];_.isNumber(t)&&a.push(`expires=${new Date(t).toUTCString()}`),_.isString(i)&&a.push(`path=${i}`),_.isString(s)&&a.push(`domain=${s}`),o===!0&&a.push("secure"),_.isString(r)&&a.push(`SameSite=${r}`),document.cookie=a.join("; ")},read(n){if(typeof document>"u")return null;const e=document.cookie.match(new RegExp("(?:^|; )"+n+"=([^;]*)"));return e?decodeURIComponent(e[1]):null},remove(n){this.write(n,"",Date.now()-864e5,"/")}}:{write(){},read(){return null},remove(){}};function jl(n){return/^([a-z][a-z\d+\-.]*:)?\/\//i.test(n)}function Wl(n,e){return e?n.replace(/\/?\/$/,"")+"/"+e.replace(/^\/+/,""):n}function hr(n,e,t){let i=!jl(e);return n&&(i||t==!1)?Wl(n,e):e}const Us=n=>n instanceof me?{...n}:n;function dt(n,e){e=e||{};const t={};function i(d,c,p,m){return _.isPlainObject(d)&&_.isPlainObject(c)?_.merge.call({caseless:m},d,c):_.isPlainObject(c)?_.merge({},c):_.isArray(c)?c.slice():c}function s(d,c,p,m){if(_.isUndefined(c)){if(!_.isUndefined(d))return i(void 0,d,p,m)}else return i(d,c,p,m)}function o(d,c){if(!_.isUndefined(c))return i(void 0,c)}function r(d,c){if(_.isUndefined(c)){if(!_.isUndefined(d))return i(void 0,d)}else return i(void 0,c)}function a(d,c,p){if(p in e)return i(d,c);if(p in n)return i(void 0,d)}const l={url:o,method:o,data:o,baseURL:r,transformRequest:r,transformResponse:r,paramsSerializer:r,timeout:r,timeoutMessage:r,withCredentials:r,withXSRFToken:r,adapter:r,responseType:r,xsrfCookieName:r,xsrfHeaderName:r,onUploadProgress:r,onDownloadProgress:r,decompress:r,maxContentLength:r,maxBodyLength:r,beforeRedirect:r,transport:r,httpAgent:r,httpsAgent:r,cancelToken:r,socketPath:r,responseEncoding:r,validateStatus:a,headers:(d,c,p)=>s(Us(d),Us(c),p,!0)};return _.forEach(Object.keys({...n,...e}),function(c){const p=l[c]||s,m=p(n[c],e[c],c);_.isUndefined(m)&&p!==a||(t[c]=m)}),t}const pr=n=>{const e=dt({},n);let{data:t,withXSRFToken:i,xsrfHeaderName:s,xsrfCookieName:o,headers:r,auth:a}=e;if(e.headers=r=me.from(r),e.url=ar(hr(e.baseURL,e.url,e.allowAbsoluteUrls),n.params,n.paramsSerializer),a&&r.set("Authorization","Basic "+btoa((a.username||"")+":"+(a.password?unescape(encodeURIComponent(a.password)):""))),_.isFormData(t)){if(ce.hasStandardBrowserEnv||ce.hasStandardBrowserWebWorkerEnv)r.setContentType(void 0);else if(_.isFunction(t.getHeaders)){const l=t.getHeaders(),d=["content-type","content-length"];Object.entries(l).forEach(([c,p])=>{d.includes(c.toLowerCase())&&r.set(c,p)})}}if(ce.hasStandardBrowserEnv&&(i&&_.isFunction(i)&&(i=i(e)),i||i!==!1&&Hl(e.url))){const l=s&&o&&ql.read(o);l&&r.set(s,l)}return e},Kl=typeof XMLHttpRequest<"u",Gl=Kl&&function(n){return new Promise(function(t,i){const s=pr(n);let o=s.data;const r=me.from(s.headers).normalize();let{responseType:a,onUploadProgress:l,onDownloadProgress:d}=s,c,p,m,b,f;function g(){b&&b(),f&&f(),s.cancelToken&&s.cancelToken.unsubscribe(c),s.signal&&s.signal.removeEventListener("abort",c)}let y=new XMLHttpRequest;y.open(s.method.toUpperCase(),s.url,!0),y.timeout=s.timeout;function E(){if(!y)return;const A=me.from("getAllResponseHeaders"in y&&y.getAllResponseHeaders()),L={data:!a||a==="text"||a==="json"?y.responseText:y.response,status:y.status,statusText:y.statusText,headers:A,config:n,request:y};ur(function(P){t(P),g()},function(P){i(P),g()},L),y=null}"onloadend"in y?y.onloadend=E:y.onreadystatechange=function(){!y||y.readyState!==4||y.status===0&&!(y.responseURL&&y.responseURL.indexOf("file:")===0)||setTimeout(E)},y.onabort=function(){y&&(i(new z("Request aborted",z.ECONNABORTED,n,y)),y=null)},y.onerror=function(S){const L=S&&S.message?S.message:"Network Error",U=new z(L,z.ERR_NETWORK,n,y);U.event=S||null,i(U),y=null},y.ontimeout=function(){let S=s.timeout?"timeout of "+s.timeout+"ms exceeded":"timeout exceeded";const L=s.transitional||lr;s.timeoutErrorMessage&&(S=s.timeoutErrorMessage),i(new z(S,L.clarifyTimeoutError?z.ETIMEDOUT:z.ECONNABORTED,n,y)),y=null},o===void 0&&r.setContentType(null),"setRequestHeader"in y&&_.forEach(r.toJSON(),function(S,L){y.setRequestHeader(L,S)}),_.isUndefined(s.withCredentials)||(y.withCredentials=!!s.withCredentials),a&&a!=="json"&&(y.responseType=s.responseType),d&&([m,f]=Fn(d,!0),y.addEventListener("progress",m)),l&&y.upload&&([p,b]=Fn(l),y.upload.addEventListener("progress",p),y.upload.addEventListener("loadend",b)),(s.cancelToken||s.signal)&&(c=A=>{y&&(i(!A||A.type?new $t(null,n,y):A),y.abort(),y=null)},s.cancelToken&&s.cancelToken.subscribe(c),s.signal&&(s.signal.aborted?c():s.signal.addEventListener("abort",c)));const C=Vl(s.url);if(C&&ce.protocols.indexOf(C)===-1){i(new z("Unsupported protocol "+C+":",z.ERR_BAD_REQUEST,n));return}y.send(o||null)})},Yl=(n,e)=>{const{length:t}=n=n?n.filter(Boolean):[];if(e||t){let i=new AbortController,s;const o=function(d){if(!s){s=!0,a();const c=d instanceof Error?d:this.reason;i.abort(c instanceof z?c:new $t(c instanceof Error?c.message:c))}};let r=e&&setTimeout(()=>{r=null,o(new z(`timeout ${e} of ms exceeded`,z.ETIMEDOUT))},e);const a=()=>{n&&(r&&clearTimeout(r),r=null,n.forEach(d=>{d.unsubscribe?d.unsubscribe(o):d.removeEventListener("abort",o)}),n=null)};n.forEach(d=>d.addEventListener("abort",o));const{signal:l}=i;return l.unsubscribe=()=>_.asap(a),l}},Xl=function*(n,e){let t=n.byteLength;if(t<e){yield n;return}let i=0,s;for(;i<t;)s=i+e,yield n.slice(i,s),i=s},Jl=async function*(n,e){for await(const t of Zl(n))yield*Xl(t,e)},Zl=async function*(n){if(n[Symbol.asyncIterator]){yield*n;return}const e=n.getReader();try{for(;;){const{done:t,value:i}=await e.read();if(t)break;yield i}}finally{await e.cancel()}},Hs=(n,e,t,i)=>{const s=Jl(n,e);let o=0,r,a=l=>{r||(r=!0,i&&i(l))};return new ReadableStream({async pull(l){try{const{done:d,value:c}=await s.next();if(d){a(),l.close();return}let p=c.byteLength;if(t){let m=o+=p;t(m)}l.enqueue(new Uint8Array(c))}catch(d){throw a(d),d}},cancel(l){return a(l),s.return()}},{highWaterMark:2})},qs=64*1024,{isFunction:mn}=_,Ql=(({Request:n,Response:e})=>({Request:n,Response:e}))(_.global),{ReadableStream:js,TextEncoder:Ws}=_.global,Ks=(n,...e)=>{try{return!!n(...e)}catch{return!1}},ec=n=>{n=_.merge.call({skipUndefined:!0},Ql,n);const{fetch:e,Request:t,Response:i}=n,s=e?mn(e):typeof fetch=="function",o=mn(t),r=mn(i);if(!s)return!1;const a=s&&mn(js),l=s&&(typeof Ws=="function"?(f=>g=>f.encode(g))(new Ws):async f=>new Uint8Array(await new t(f).arrayBuffer())),d=o&&a&&Ks(()=>{let f=!1;const g=new t(ce.origin,{body:new js,method:"POST",get duplex(){return f=!0,"half"}}).headers.has("Content-Type");return f&&!g}),c=r&&a&&Ks(()=>_.isReadableStream(new i("").body)),p={stream:c&&(f=>f.body)};s&&["text","arrayBuffer","blob","formData","stream"].forEach(f=>{!p[f]&&(p[f]=(g,y)=>{let E=g&&g[f];if(E)return E.call(g);throw new z(`Response type '${f}' is not supported`,z.ERR_NOT_SUPPORT,y)})});const m=async f=>{if(f==null)return 0;if(_.isBlob(f))return f.size;if(_.isSpecCompliantForm(f))return(await new t(ce.origin,{method:"POST",body:f}).arrayBuffer()).byteLength;if(_.isArrayBufferView(f)||_.isArrayBuffer(f))return f.byteLength;if(_.isURLSearchParams(f)&&(f=f+""),_.isString(f))return(await l(f)).byteLength},b=async(f,g)=>{const y=_.toFiniteNumber(f.getContentLength());return y??m(g)};return async f=>{let{url:g,method:y,data:E,signal:C,cancelToken:A,timeout:S,onDownloadProgress:L,onUploadProgress:U,responseType:P,headers:Z,withCredentials:X="same-origin",fetchOptions:I}=pr(f),ve=e||fetch;P=P?(P+"").toLowerCase():"text";let se=Yl([C,A&&A.toAbortSignal()],S),u=null;const k=se&&se.unsubscribe&&(()=>{se.unsubscribe()});let N;try{if(U&&d&&y!=="get"&&y!=="head"&&(N=await b(Z,E))!==0){let H=new t(g,{method:"POST",body:E,duplex:"half"}),ze;if(_.isFormData(E)&&(ze=H.headers.get("content-type"))&&Z.setContentType(ze),H.body){const[li,fn]=Vs(N,Fn(Bs(U)));E=Hs(H.body,qs,li,fn)}}_.isString(X)||(X=X?"include":"omit");const O=o&&"credentials"in t.prototype,M={...I,signal:se,method:y.toUpperCase(),headers:Z.normalize().toJSON(),body:E,duplex:"half",credentials:O?X:void 0};u=o&&new t(g,M);let T=await(o?ve(u,I):ve(g,M));const j=c&&(P==="stream"||P==="response");if(c&&(L||j&&k)){const H={};["status","statusText","headers"].forEach(Fs=>{H[Fs]=T[Fs]});const ze=_.toFiniteNumber(T.headers.get("content-length")),[li,fn]=L&&Vs(ze,Fn(Bs(L),!0))||[];T=new i(Hs(T.body,qs,li,()=>{fn&&fn(),k&&k()}),H)}P=P||"text";let pe=await p[_.findKey(p,P)||"text"](T,f);return!j&&k&&k(),await new Promise((H,ze)=>{ur(H,ze,{data:pe,headers:me.from(T.headers),status:T.status,statusText:T.statusText,config:f,request:u})})}catch(O){throw k&&k(),O&&O.name==="TypeError"&&/Load failed|fetch/i.test(O.message)?Object.assign(new z("Network Error",z.ERR_NETWORK,f,u),{cause:O.cause||O}):z.from(O,O&&O.code,f,u)}}},tc=new Map,fr=n=>{let e=n&&n.env||{};const{fetch:t,Request:i,Response:s}=e,o=[i,s,t];let r=o.length,a=r,l,d,c=tc;for(;a--;)l=o[a],d=c.get(l),d===void 0&&c.set(l,d=a?new Map:ec(e)),c=d;return d};fr();const ls={http:vl,xhr:Gl,fetch:{get:fr}};_.forEach(ls,(n,e)=>{if(n){try{Object.defineProperty(n,"name",{value:e})}catch{}Object.defineProperty(n,"adapterName",{value:e})}});const Gs=n=>`- ${n}`,nc=n=>_.isFunction(n)||n===null||n===!1;function ic(n,e){n=_.isArray(n)?n:[n];const{length:t}=n;let i,s;const o={};for(let r=0;r<t;r++){i=n[r];let a;if(s=i,!nc(i)&&(s=ls[(a=String(i)).toLowerCase()],s===void 0))throw new z(`Unknown adapter '${a}'`);if(s&&(_.isFunction(s)||(s=s.get(e))))break;o[a||"#"+r]=s}if(!s){const r=Object.entries(o).map(([l,d])=>`adapter ${l} `+(d===!1?"is not supported by the environment":"is not available in the build"));let a=t?r.length>1?`since :
`+r.map(Gs).join(`
`):" "+Gs(r[0]):"as no adapter specified";throw new z("There is no suitable adapter to dispatch the request "+a,"ERR_NOT_SUPPORT")}return s}const mr={getAdapter:ic,adapters:ls};function ui(n){if(n.cancelToken&&n.cancelToken.throwIfRequested(),n.signal&&n.signal.aborted)throw new $t(null,n)}function Ys(n){return ui(n),n.headers=me.from(n.headers),n.data=di.call(n,n.transformRequest),["post","put","patch"].indexOf(n.method)!==-1&&n.headers.setContentType("application/x-www-form-urlencoded",!1),mr.getAdapter(n.adapter||on.adapter,n)(n).then(function(i){return ui(n),i.data=di.call(n,n.transformResponse,i),i.headers=me.from(i.headers),i},function(i){return dr(i)||(ui(n),i&&i.response&&(i.response.data=di.call(n,n.transformResponse,i.response),i.response.headers=me.from(i.response.headers))),Promise.reject(i)})}const br="1.13.2",Yn={};["object","boolean","number","function","string","symbol"].forEach((n,e)=>{Yn[n]=function(i){return typeof i===n||"a"+(e<1?"n ":" ")+n}});const Xs={};Yn.transitional=function(e,t,i){function s(o,r){return"[Axios v"+br+"] Transitional option '"+o+"'"+r+(i?". "+i:"")}return(o,r,a)=>{if(e===!1)throw new z(s(r," has been removed"+(t?" in "+t:"")),z.ERR_DEPRECATED);return t&&!Xs[r]&&(Xs[r]=!0,console.warn(s(r," has been deprecated since v"+t+" and will be removed in the near future"))),e?e(o,r,a):!0}};Yn.spelling=function(e){return(t,i)=>(console.warn(`${i} is likely a misspelling of ${e}`),!0)};function sc(n,e,t){if(typeof n!="object")throw new z("options must be an object",z.ERR_BAD_OPTION_VALUE);const i=Object.keys(n);let s=i.length;for(;s-- >0;){const o=i[s],r=e[o];if(r){const a=n[o],l=a===void 0||r(a,o,n);if(l!==!0)throw new z("option "+o+" must be "+l,z.ERR_BAD_OPTION_VALUE);continue}if(t!==!0)throw new z("Unknown option "+o,z.ERR_BAD_OPTION)}}const Sn={assertOptions:sc,validators:Yn},Le=Sn.validators;let lt=class{constructor(e){this.defaults=e||{},this.interceptors={request:new Ps,response:new Ps}}async request(e,t){try{return await this._request(e,t)}catch(i){if(i instanceof Error){let s={};Error.captureStackTrace?Error.captureStackTrace(s):s=new Error;const o=s.stack?s.stack.replace(/^.+\n/,""):"";try{i.stack?o&&!String(i.stack).endsWith(o.replace(/^.+\n.+\n/,""))&&(i.stack+=`
`+o):i.stack=o}catch{}}throw i}}_request(e,t){typeof e=="string"?(t=t||{},t.url=e):t=e||{},t=dt(this.defaults,t);const{transitional:i,paramsSerializer:s,headers:o}=t;i!==void 0&&Sn.assertOptions(i,{silentJSONParsing:Le.transitional(Le.boolean),forcedJSONParsing:Le.transitional(Le.boolean),clarifyTimeoutError:Le.transitional(Le.boolean)},!1),s!=null&&(_.isFunction(s)?t.paramsSerializer={serialize:s}:Sn.assertOptions(s,{encode:Le.function,serialize:Le.function},!0)),t.allowAbsoluteUrls!==void 0||(this.defaults.allowAbsoluteUrls!==void 0?t.allowAbsoluteUrls=this.defaults.allowAbsoluteUrls:t.allowAbsoluteUrls=!0),Sn.assertOptions(t,{baseUrl:Le.spelling("baseURL"),withXsrfToken:Le.spelling("withXSRFToken")},!0),t.method=(t.method||this.defaults.method||"get").toLowerCase();let r=o&&_.merge(o.common,o[t.method]);o&&_.forEach(["delete","get","head","post","put","patch","common"],f=>{delete o[f]}),t.headers=me.concat(r,o);const a=[];let l=!0;this.interceptors.request.forEach(function(g){typeof g.runWhen=="function"&&g.runWhen(t)===!1||(l=l&&g.synchronous,a.unshift(g.fulfilled,g.rejected))});const d=[];this.interceptors.response.forEach(function(g){d.push(g.fulfilled,g.rejected)});let c,p=0,m;if(!l){const f=[Ys.bind(this),void 0];for(f.unshift(...a),f.push(...d),m=f.length,c=Promise.resolve(t);p<m;)c=c.then(f[p++],f[p++]);return c}m=a.length;let b=t;for(;p<m;){const f=a[p++],g=a[p++];try{b=f(b)}catch(y){g.call(this,y);break}}try{c=Ys.call(this,b)}catch(f){return Promise.reject(f)}for(p=0,m=d.length;p<m;)c=c.then(d[p++],d[p++]);return c}getUri(e){e=dt(this.defaults,e);const t=hr(e.baseURL,e.url,e.allowAbsoluteUrls);return ar(t,e.params,e.paramsSerializer)}};_.forEach(["delete","get","head","options"],function(e){lt.prototype[e]=function(t,i){return this.request(dt(i||{},{method:e,url:t,data:(i||{}).data}))}});_.forEach(["post","put","patch"],function(e){function t(i){return function(o,r,a){return this.request(dt(a||{},{method:e,headers:i?{"Content-Type":"multipart/form-data"}:{},url:o,data:r}))}}lt.prototype[e]=t(),lt.prototype[e+"Form"]=t(!0)});let oc=class gr{constructor(e){if(typeof e!="function")throw new TypeError("executor must be a function.");let t;this.promise=new Promise(function(o){t=o});const i=this;this.promise.then(s=>{if(!i._listeners)return;let o=i._listeners.length;for(;o-- >0;)i._listeners[o](s);i._listeners=null}),this.promise.then=s=>{let o;const r=new Promise(a=>{i.subscribe(a),o=a}).then(s);return r.cancel=function(){i.unsubscribe(o)},r},e(function(o,r,a){i.reason||(i.reason=new $t(o,r,a),t(i.reason))})}throwIfRequested(){if(this.reason)throw this.reason}subscribe(e){if(this.reason){e(this.reason);return}this._listeners?this._listeners.push(e):this._listeners=[e]}unsubscribe(e){if(!this._listeners)return;const t=this._listeners.indexOf(e);t!==-1&&this._listeners.splice(t,1)}toAbortSignal(){const e=new AbortController,t=i=>{e.abort(i)};return this.subscribe(t),e.signal.unsubscribe=()=>this.unsubscribe(t),e.signal}static source(){let e;return{token:new gr(function(s){e=s}),cancel:e}}};function rc(n){return function(t){return n.apply(null,t)}}function ac(n){return _.isObject(n)&&n.isAxiosError===!0}const zi={Continue:100,SwitchingProtocols:101,Processing:102,EarlyHints:103,Ok:200,Created:201,Accepted:202,NonAuthoritativeInformation:203,NoContent:204,ResetContent:205,PartialContent:206,MultiStatus:207,AlreadyReported:208,ImUsed:226,MultipleChoices:300,MovedPermanently:301,Found:302,SeeOther:303,NotModified:304,UseProxy:305,Unused:306,TemporaryRedirect:307,PermanentRedirect:308,BadRequest:400,Unauthorized:401,PaymentRequired:402,Forbidden:403,NotFound:404,MethodNotAllowed:405,NotAcceptable:406,ProxyAuthenticationRequired:407,RequestTimeout:408,Conflict:409,Gone:410,LengthRequired:411,PreconditionFailed:412,PayloadTooLarge:413,UriTooLong:414,UnsupportedMediaType:415,RangeNotSatisfiable:416,ExpectationFailed:417,ImATeapot:418,MisdirectedRequest:421,UnprocessableEntity:422,Locked:423,FailedDependency:424,TooEarly:425,UpgradeRequired:426,PreconditionRequired:428,TooManyRequests:429,RequestHeaderFieldsTooLarge:431,UnavailableForLegalReasons:451,InternalServerError:500,NotImplemented:501,BadGateway:502,ServiceUnavailable:503,GatewayTimeout:504,HttpVersionNotSupported:505,VariantAlsoNegotiates:506,InsufficientStorage:507,LoopDetected:508,NotExtended:510,NetworkAuthenticationRequired:511,WebServerIsDown:521,ConnectionTimedOut:522,OriginIsUnreachable:523,TimeoutOccurred:524,SslHandshakeFailed:525,InvalidSslCertificate:526};Object.entries(zi).forEach(([n,e])=>{zi[e]=n});function vr(n){const e=new lt(n),t=Yo(lt.prototype.request,e);return _.extend(t,lt.prototype,e,{allOwnKeys:!0}),_.extend(t,e,null,{allOwnKeys:!0}),t.create=function(s){return vr(dt(n,s))},t}const te=vr(on);te.Axios=lt;te.CanceledError=$t;te.CancelToken=oc;te.isCancel=dr;te.VERSION=br;te.toFormData=Gn;te.AxiosError=z;te.Cancel=te.CanceledError;te.all=function(e){return Promise.all(e)};te.spread=rc;te.isAxiosError=ac;te.mergeConfig=dt;te.AxiosHeaders=me;te.formToJSON=n=>cr(_.isHTMLForm(n)?new FormData(n):n);te.getAdapter=mr.getAdapter;te.HttpStatusCode=zi;te.default=te;const{Axios:yf,AxiosError:_f,CanceledError:wf,isCancel:Ef,CancelToken:xf,VERSION:kf,all:Cf,Cancel:Sf,isAxiosError:Af,spread:Tf,toFormData:Nf,AxiosHeaders:$f,HttpStatusCode:Of,formToJSON:Lf,getAdapter:Ff,mergeConfig:Rf}=te;var Js=class extends HTMLElement{constructor(...e){super(...e),this.cookieName=null,this.state="collapsed",this.expanded=!1,this.handleOpen=()=>{this.trigger?.setAttribute("aria-expanded","true"),this.expanded=!0,this.dispatchEvent(new CustomEvent("open")),this.target&&(this.target.dataset.state="expanded"),this.cookieName&&window.Craft?.setCookie(this.cookieName,"expanded")},this.handleClose=()=>{this.trigger?.setAttribute("aria-expanded","false"),this.expanded=!1,this.dispatchEvent(new CustomEvent("close")),this.target&&(this.target.dataset.state="collapsed"),this.cookieName&&window.Craft?.setCookie(this.cookieName,"collapsed")}}get trigger(){return this.querySelector('button[type="button"]')}get target(){if(!this.trigger)return console.warn("No trigger found for disclosure."),null;let e=this.trigger.getAttribute("aria-controls");return e?document.getElementById(e):(console.warn("No target selector found for disclosure."),null)}connectedCallback(){if(!this.trigger){console.error("craft-disclosure elements must include a button",this);return}if(!this.target){console.error(`No target with id ${this.trigger.getAttribute("aria-controls")} found for disclosure. `,this.trigger);return}this.cookieName=this.getAttribute("cookie-name"),this.state=this.getAttribute("state")??"expanded",this.trigger.setAttribute("aria-expanded",this.state==="expanded"?"true":"false"),this.trigger.addEventListener("click",this.toggle.bind(this)),this.state==="expanded"?this.open():this.close()}disconnectedCallback(){this.open(),this.trigger?.removeEventListener("click",this.toggle.bind(this))}attributeChangedCallback(e,t,i){e==="state"&&(i==="expanded"?this.handleOpen():this.handleClose())}toggle(){this.expanded?this.close():this.open()}open(){this.setAttribute("state","expanded")}close(){this.setAttribute("state","collapsed")}};Js.observedAttributes=["state"],customElements.get("craft-disclosure")||customElements.define("craft-disclosure",Js);const An=globalThis,cs=An.ShadowRoot&&(An.ShadyCSS===void 0||An.ShadyCSS.nativeShadow)&&"adoptedStyleSheets"in Document.prototype&&"replace"in CSSStyleSheet.prototype,ds=Symbol(),Zs=new WeakMap;let yr=class{constructor(e,t,i){if(this._$cssResult$=!0,i!==ds)throw Error("CSSResult is not constructable. Use `unsafeCSS` or `css` instead.");this.cssText=e,this.t=t}get styleSheet(){let e=this.o;const t=this.t;if(cs&&e===void 0){const i=t!==void 0&&t.length===1;i&&(e=Zs.get(t)),e===void 0&&((this.o=e=new CSSStyleSheet).replaceSync(this.cssText),i&&Zs.set(t,e))}return e}toString(){return this.cssText}};const _r=n=>new yr(typeof n=="string"?n:n+"",void 0,ds),F=(n,...e)=>{const t=n.length===1?n[0]:e.reduce(((i,s,o)=>i+(r=>{if(r._$cssResult$===!0)return r.cssText;if(typeof r=="number")return r;throw Error("Value passed to 'css' function must be a 'css' function result: "+r+". Use 'unsafeCSS' to pass non-literal values, but take care to ensure page security.")})(s)+n[o+1]),n[0]);return new yr(t,n,ds)},us=(n,e)=>{if(cs)n.adoptedStyleSheets=e.map((t=>t instanceof CSSStyleSheet?t:t.styleSheet));else for(const t of e){const i=document.createElement("style"),s=An.litNonce;s!==void 0&&i.setAttribute("nonce",s),i.textContent=t.cssText,n.appendChild(i)}},Qs=cs?n=>n:n=>n instanceof CSSStyleSheet?(e=>{let t="";for(const i of e.cssRules)t+=i.cssText;return _r(t)})(n):n;const{is:lc,defineProperty:cc,getOwnPropertyDescriptor:dc,getOwnPropertyNames:uc,getOwnPropertySymbols:hc,getPrototypeOf:pc}=Object,Xn=globalThis,eo=Xn.trustedTypes,fc=eo?eo.emptyScript:"",mc=Xn.reactiveElementPolyfillSupport,Wt=(n,e)=>n,Rn={toAttribute(n,e){switch(e){case Boolean:n=n?fc:null;break;case Object:case Array:n=n==null?n:JSON.stringify(n)}return n},fromAttribute(n,e){let t=n;switch(e){case Boolean:t=n!==null;break;case Number:t=n===null?null:Number(n);break;case Object:case Array:try{t=JSON.parse(n)}catch{t=null}}return t}},hs=(n,e)=>!lc(n,e),to={attribute:!0,type:String,converter:Rn,reflect:!1,useDefault:!1,hasChanged:hs};Symbol.metadata??=Symbol("metadata"),Xn.litPropertyMetadata??=new WeakMap;let gt=class extends HTMLElement{static addInitializer(e){this._$Ei(),(this.l??=[]).push(e)}static get observedAttributes(){return this.finalize(),this._$Eh&&[...this._$Eh.keys()]}static createProperty(e,t=to){if(t.state&&(t.attribute=!1),this._$Ei(),this.prototype.hasOwnProperty(e)&&((t=Object.create(t)).wrapped=!0),this.elementProperties.set(e,t),!t.noAccessor){const i=Symbol(),s=this.getPropertyDescriptor(e,i,t);s!==void 0&&cc(this.prototype,e,s)}}static getPropertyDescriptor(e,t,i){const{get:s,set:o}=dc(this.prototype,e)??{get(){return this[t]},set(r){this[t]=r}};return{get:s,set(r){const a=s?.call(this);o?.call(this,r),this.requestUpdate(e,a,i)},configurable:!0,enumerable:!0}}static getPropertyOptions(e){return this.elementProperties.get(e)??to}static _$Ei(){if(this.hasOwnProperty(Wt("elementProperties")))return;const e=pc(this);e.finalize(),e.l!==void 0&&(this.l=[...e.l]),this.elementProperties=new Map(e.elementProperties)}static finalize(){if(this.hasOwnProperty(Wt("finalized")))return;if(this.finalized=!0,this._$Ei(),this.hasOwnProperty(Wt("properties"))){const t=this.properties,i=[...uc(t),...hc(t)];for(const s of i)this.createProperty(s,t[s])}const e=this[Symbol.metadata];if(e!==null){const t=litPropertyMetadata.get(e);if(t!==void 0)for(const[i,s]of t)this.elementProperties.set(i,s)}this._$Eh=new Map;for(const[t,i]of this.elementProperties){const s=this._$Eu(t,i);s!==void 0&&this._$Eh.set(s,t)}this.elementStyles=this.finalizeStyles(this.styles)}static finalizeStyles(e){const t=[];if(Array.isArray(e)){const i=new Set(e.flat(1/0).reverse());for(const s of i)t.unshift(Qs(s))}else e!==void 0&&t.push(Qs(e));return t}static _$Eu(e,t){const i=t.attribute;return i===!1?void 0:typeof i=="string"?i:typeof e=="string"?e.toLowerCase():void 0}constructor(){super(),this._$Ep=void 0,this.isUpdatePending=!1,this.hasUpdated=!1,this._$Em=null,this._$Ev()}_$Ev(){this._$ES=new Promise((e=>this.enableUpdating=e)),this._$AL=new Map,this._$E_(),this.requestUpdate(),this.constructor.l?.forEach((e=>e(this)))}addController(e){(this._$EO??=new Set).add(e),this.renderRoot!==void 0&&this.isConnected&&e.hostConnected?.()}removeController(e){this._$EO?.delete(e)}_$E_(){const e=new Map,t=this.constructor.elementProperties;for(const i of t.keys())this.hasOwnProperty(i)&&(e.set(i,this[i]),delete this[i]);e.size>0&&(this._$Ep=e)}createRenderRoot(){const e=this.shadowRoot??this.attachShadow(this.constructor.shadowRootOptions);return us(e,this.constructor.elementStyles),e}connectedCallback(){this.renderRoot??=this.createRenderRoot(),this.enableUpdating(!0),this._$EO?.forEach((e=>e.hostConnected?.()))}enableUpdating(e){}disconnectedCallback(){this._$EO?.forEach((e=>e.hostDisconnected?.()))}attributeChangedCallback(e,t,i){this._$AK(e,i)}_$ET(e,t){const i=this.constructor.elementProperties.get(e),s=this.constructor._$Eu(e,i);if(s!==void 0&&i.reflect===!0){const o=(i.converter?.toAttribute!==void 0?i.converter:Rn).toAttribute(t,i.type);this._$Em=e,o==null?this.removeAttribute(s):this.setAttribute(s,o),this._$Em=null}}_$AK(e,t){const i=this.constructor,s=i._$Eh.get(e);if(s!==void 0&&this._$Em!==s){const o=i.getPropertyOptions(s),r=typeof o.converter=="function"?{fromAttribute:o.converter}:o.converter?.fromAttribute!==void 0?o.converter:Rn;this._$Em=s;const a=r.fromAttribute(t,o.type);this[s]=a??this._$Ej?.get(s)??a,this._$Em=null}}requestUpdate(e,t,i){if(e!==void 0){const s=this.constructor,o=this[e];if(i??=s.getPropertyOptions(e),!((i.hasChanged??hs)(o,t)||i.useDefault&&i.reflect&&o===this._$Ej?.get(e)&&!this.hasAttribute(s._$Eu(e,i))))return;this.C(e,t,i)}this.isUpdatePending===!1&&(this._$ES=this._$EP())}C(e,t,{useDefault:i,reflect:s,wrapped:o},r){i&&!(this._$Ej??=new Map).has(e)&&(this._$Ej.set(e,r??t??this[e]),o!==!0||r!==void 0)||(this._$AL.has(e)||(this.hasUpdated||i||(t=void 0),this._$AL.set(e,t)),s===!0&&this._$Em!==e&&(this._$Eq??=new Set).add(e))}async _$EP(){this.isUpdatePending=!0;try{await this._$ES}catch(t){Promise.reject(t)}const e=this.scheduleUpdate();return e!=null&&await e,!this.isUpdatePending}scheduleUpdate(){return this.performUpdate()}performUpdate(){if(!this.isUpdatePending)return;if(!this.hasUpdated){if(this.renderRoot??=this.createRenderRoot(),this._$Ep){for(const[s,o]of this._$Ep)this[s]=o;this._$Ep=void 0}const i=this.constructor.elementProperties;if(i.size>0)for(const[s,o]of i){const{wrapped:r}=o,a=this[s];r!==!0||this._$AL.has(s)||a===void 0||this.C(s,void 0,o,a)}}let e=!1;const t=this._$AL;try{e=this.shouldUpdate(t),e?(this.willUpdate(t),this._$EO?.forEach((i=>i.hostUpdate?.())),this.update(t)):this._$EM()}catch(i){throw e=!1,this._$EM(),i}e&&this._$AE(t)}willUpdate(e){}_$AE(e){this._$EO?.forEach((t=>t.hostUpdated?.())),this.hasUpdated||(this.hasUpdated=!0,this.firstUpdated(e)),this.updated(e)}_$EM(){this._$AL=new Map,this.isUpdatePending=!1}get updateComplete(){return this.getUpdateComplete()}getUpdateComplete(){return this._$ES}shouldUpdate(e){return!0}update(e){this._$Eq&&=this._$Eq.forEach((t=>this._$ET(t,this[t]))),this._$EM()}updated(e){}firstUpdated(e){}};gt.elementStyles=[],gt.shadowRootOptions={mode:"open"},gt[Wt("elementProperties")]=new Map,gt[Wt("finalized")]=new Map,mc?.({ReactiveElement:gt}),(Xn.reactiveElementVersions??=[]).push("2.1.1");const ps=globalThis,Mn=ps.trustedTypes,no=Mn?Mn.createPolicy("lit-html",{createHTML:n=>n}):void 0,wr="$lit$",Je=`lit$${Math.random().toFixed(9).slice(2)}$`,Er="?"+Je,bc=`<${Er}>`,ut=document,Xt=()=>ut.createComment(""),Jt=n=>n===null||typeof n!="object"&&typeof n!="function",fs=Array.isArray,gc=n=>fs(n)||typeof n?.[Symbol.iterator]=="function",hi=`[ 	
\f\r]`,It=/<(?:(!--|\/[^a-zA-Z])|(\/?[a-zA-Z][^>\s]*)|(\/?$))/g,io=/-->/g,so=/>/g,tt=RegExp(`>|${hi}(?:([^\\s"'>=/]+)(${hi}*=${hi}*(?:[^ 	
\f\r"'\`<>=]|("|')|))|$)`,"g"),oo=/'/g,ro=/"/g,xr=/^(?:script|style|textarea|title)$/i,vc=n=>(e,...t)=>({_$litType$:n,strings:e,values:t}),x=vc(1),Me=Symbol.for("lit-noChange"),B=Symbol.for("lit-nothing"),ao=new WeakMap,ot=ut.createTreeWalker(ut,129);function kr(n,e){if(!fs(n)||!n.hasOwnProperty("raw"))throw Error("invalid template strings array");return no!==void 0?no.createHTML(e):e}const yc=(n,e)=>{const t=n.length-1,i=[];let s,o=e===2?"<svg>":e===3?"<math>":"",r=It;for(let a=0;a<t;a++){const l=n[a];let d,c,p=-1,m=0;for(;m<l.length&&(r.lastIndex=m,c=r.exec(l),c!==null);)m=r.lastIndex,r===It?c[1]==="!--"?r=io:c[1]!==void 0?r=so:c[2]!==void 0?(xr.test(c[2])&&(s=RegExp("</"+c[2],"g")),r=tt):c[3]!==void 0&&(r=tt):r===tt?c[0]===">"?(r=s??It,p=-1):c[1]===void 0?p=-2:(p=r.lastIndex-c[2].length,d=c[1],r=c[3]===void 0?tt:c[3]==='"'?ro:oo):r===ro||r===oo?r=tt:r===io||r===so?r=It:(r=tt,s=void 0);const b=r===tt&&n[a+1].startsWith("/>")?" ":"";o+=r===It?l+bc:p>=0?(i.push(d),l.slice(0,p)+wr+l.slice(p)+Je+b):l+Je+(p===-2?a:b)}return[kr(n,o+(n[t]||"<?>")+(e===2?"</svg>":e===3?"</math>":"")),i]};class Zt{constructor({strings:e,_$litType$:t},i){let s;this.parts=[];let o=0,r=0;const a=e.length-1,l=this.parts,[d,c]=yc(e,t);if(this.el=Zt.createElement(d,i),ot.currentNode=this.el.content,t===2||t===3){const p=this.el.content.firstChild;p.replaceWith(...p.childNodes)}for(;(s=ot.nextNode())!==null&&l.length<a;){if(s.nodeType===1){if(s.hasAttributes())for(const p of s.getAttributeNames())if(p.endsWith(wr)){const m=c[r++],b=s.getAttribute(p).split(Je),f=/([.?@])?(.*)/.exec(m);l.push({type:1,index:o,name:f[2],strings:b,ctor:f[1]==="."?wc:f[1]==="?"?Ec:f[1]==="@"?xc:Jn}),s.removeAttribute(p)}else p.startsWith(Je)&&(l.push({type:6,index:o}),s.removeAttribute(p));if(xr.test(s.tagName)){const p=s.textContent.split(Je),m=p.length-1;if(m>0){s.textContent=Mn?Mn.emptyScript:"";for(let b=0;b<m;b++)s.append(p[b],Xt()),ot.nextNode(),l.push({type:2,index:++o});s.append(p[m],Xt())}}}else if(s.nodeType===8)if(s.data===Er)l.push({type:2,index:o});else{let p=-1;for(;(p=s.data.indexOf(Je,p+1))!==-1;)l.push({type:7,index:o}),p+=Je.length-1}o++}}static createElement(e,t){const i=ut.createElement("template");return i.innerHTML=e,i}}function xt(n,e,t=n,i){if(e===Me)return e;let s=i!==void 0?t._$Co?.[i]:t._$Cl;const o=Jt(e)?void 0:e._$litDirective$;return s?.constructor!==o&&(s?._$AO?.(!1),o===void 0?s=void 0:(s=new o(n),s._$AT(n,t,i)),i!==void 0?(t._$Co??=[])[i]=s:t._$Cl=s),s!==void 0&&(e=xt(n,s._$AS(n,e.values),s,i)),e}class _c{constructor(e,t){this._$AV=[],this._$AN=void 0,this._$AD=e,this._$AM=t}get parentNode(){return this._$AM.parentNode}get _$AU(){return this._$AM._$AU}u(e){const{el:{content:t},parts:i}=this._$AD,s=(e?.creationScope??ut).importNode(t,!0);ot.currentNode=s;let o=ot.nextNode(),r=0,a=0,l=i[0];for(;l!==void 0;){if(r===l.index){let d;l.type===2?d=new rn(o,o.nextSibling,this,e):l.type===1?d=new l.ctor(o,l.name,l.strings,this,e):l.type===6&&(d=new kc(o,this,e)),this._$AV.push(d),l=i[++a]}r!==l?.index&&(o=ot.nextNode(),r++)}return ot.currentNode=ut,s}p(e){let t=0;for(const i of this._$AV)i!==void 0&&(i.strings!==void 0?(i._$AI(e,i,t),t+=i.strings.length-2):i._$AI(e[t])),t++}}class rn{get _$AU(){return this._$AM?._$AU??this._$Cv}constructor(e,t,i,s){this.type=2,this._$AH=B,this._$AN=void 0,this._$AA=e,this._$AB=t,this._$AM=i,this.options=s,this._$Cv=s?.isConnected??!0}get parentNode(){let e=this._$AA.parentNode;const t=this._$AM;return t!==void 0&&e?.nodeType===11&&(e=t.parentNode),e}get startNode(){return this._$AA}get endNode(){return this._$AB}_$AI(e,t=this){e=xt(this,e,t),Jt(e)?e===B||e==null||e===""?(this._$AH!==B&&this._$AR(),this._$AH=B):e!==this._$AH&&e!==Me&&this._(e):e._$litType$!==void 0?this.$(e):e.nodeType!==void 0?this.T(e):gc(e)?this.k(e):this._(e)}O(e){return this._$AA.parentNode.insertBefore(e,this._$AB)}T(e){this._$AH!==e&&(this._$AR(),this._$AH=this.O(e))}_(e){this._$AH!==B&&Jt(this._$AH)?this._$AA.nextSibling.data=e:this.T(ut.createTextNode(e)),this._$AH=e}$(e){const{values:t,_$litType$:i}=e,s=typeof i=="number"?this._$AC(e):(i.el===void 0&&(i.el=Zt.createElement(kr(i.h,i.h[0]),this.options)),i);if(this._$AH?._$AD===s)this._$AH.p(t);else{const o=new _c(s,this),r=o.u(this.options);o.p(t),this.T(r),this._$AH=o}}_$AC(e){let t=ao.get(e.strings);return t===void 0&&ao.set(e.strings,t=new Zt(e)),t}k(e){fs(this._$AH)||(this._$AH=[],this._$AR());const t=this._$AH;let i,s=0;for(const o of e)s===t.length?t.push(i=new rn(this.O(Xt()),this.O(Xt()),this,this.options)):i=t[s],i._$AI(o),s++;s<t.length&&(this._$AR(i&&i._$AB.nextSibling,s),t.length=s)}_$AR(e=this._$AA.nextSibling,t){for(this._$AP?.(!1,!0,t);e!==this._$AB;){const i=e.nextSibling;e.remove(),e=i}}setConnected(e){this._$AM===void 0&&(this._$Cv=e,this._$AP?.(e))}}class Jn{get tagName(){return this.element.tagName}get _$AU(){return this._$AM._$AU}constructor(e,t,i,s,o){this.type=1,this._$AH=B,this._$AN=void 0,this.element=e,this.name=t,this._$AM=s,this.options=o,i.length>2||i[0]!==""||i[1]!==""?(this._$AH=Array(i.length-1).fill(new String),this.strings=i):this._$AH=B}_$AI(e,t=this,i,s){const o=this.strings;let r=!1;if(o===void 0)e=xt(this,e,t,0),r=!Jt(e)||e!==this._$AH&&e!==Me,r&&(this._$AH=e);else{const a=e;let l,d;for(e=o[0],l=0;l<o.length-1;l++)d=xt(this,a[i+l],t,l),d===Me&&(d=this._$AH[l]),r||=!Jt(d)||d!==this._$AH[l],d===B?e=B:e!==B&&(e+=(d??"")+o[l+1]),this._$AH[l]=d}r&&!s&&this.j(e)}j(e){e===B?this.element.removeAttribute(this.name):this.element.setAttribute(this.name,e??"")}}class wc extends Jn{constructor(){super(...arguments),this.type=3}j(e){this.element[this.name]=e===B?void 0:e}}class Ec extends Jn{constructor(){super(...arguments),this.type=4}j(e){this.element.toggleAttribute(this.name,!!e&&e!==B)}}class xc extends Jn{constructor(e,t,i,s,o){super(e,t,i,s,o),this.type=5}_$AI(e,t=this){if((e=xt(this,e,t,0)??B)===Me)return;const i=this._$AH,s=e===B&&i!==B||e.capture!==i.capture||e.once!==i.once||e.passive!==i.passive,o=e!==B&&(i===B||s);s&&this.element.removeEventListener(this.name,this,i),o&&this.element.addEventListener(this.name,this,e),this._$AH=e}handleEvent(e){typeof this._$AH=="function"?this._$AH.call(this.options?.host??this.element,e):this._$AH.handleEvent(e)}}class kc{constructor(e,t,i){this.element=e,this.type=6,this._$AN=void 0,this._$AM=t,this.options=i}get _$AU(){return this._$AM._$AU}_$AI(e){xt(this,e)}}const Cc=ps.litHtmlPolyfillSupport;Cc?.(Zt,rn),(ps.litHtmlVersions??=[]).push("3.3.1");const Vi=(n,e,t)=>{const i=t?.renderBefore??e;let s=i._$litPart$;if(s===void 0){const o=t?.renderBefore??null;i._$litPart$=s=new rn(e.insertBefore(Xt(),o),o,void 0,t??{})}return s._$AI(n),s};const ms=globalThis;let G=class extends gt{constructor(){super(...arguments),this.renderOptions={host:this},this._$Do=void 0}createRenderRoot(){const e=super.createRenderRoot();return this.renderOptions.renderBefore??=e.firstChild,e}update(e){const t=this.render();this.hasUpdated||(this.renderOptions.isConnected=this.isConnected),super.update(e),this._$Do=Vi(t,this.renderRoot,this.renderOptions)}connectedCallback(){super.connectedCallback(),this._$Do?.setConnected(!0)}disconnectedCallback(){super.disconnectedCallback(),this._$Do?.setConnected(!1)}render(){return Me}};G._$litElement$=!0,G.finalized=!0,ms.litElementHydrateSupport?.({LitElement:G});const Sc=ms.litElementPolyfillSupport;Sc?.({LitElement:G});(ms.litElementVersions??=[]).push("4.2.1");const ke=n=>(e,t)=>{t!==void 0?t.addInitializer((()=>{customElements.define(n,e)})):customElements.define(n,e)};const Ac={attribute:!0,type:String,converter:Rn,reflect:!1,hasChanged:hs},Tc=(n=Ac,e,t)=>{const{kind:i,metadata:s}=t;let o=globalThis.litPropertyMetadata.get(s);if(o===void 0&&globalThis.litPropertyMetadata.set(s,o=new Map),i==="setter"&&((n=Object.create(n)).wrapped=!0),o.set(t.name,n),i==="accessor"){const{name:r}=t;return{set(a){const l=e.get.call(this);e.set.call(this,a),this.requestUpdate(r,l,n)},init(a){return a!==void 0&&this.C(r,void 0,n,a),a}}}if(i==="setter"){const{name:r}=t;return function(a){const l=this[r];e.call(this,a),this.requestUpdate(r,l,n)}}throw Error("Unsupported decorator location: "+i)};function w(n){return(e,t)=>typeof t=="object"?Tc(n,e,t):((i,s,o)=>{const r=s.hasOwnProperty(o);return s.constructor.createProperty(o,i),r?Object.getOwnPropertyDescriptor(s,o):void 0})(n,e,t)}function be(n){return w({...n,state:!0,attribute:!1})}const bs=(n,e,t)=>(t.configurable=!0,t.enumerable=!0,Reflect.decorate&&typeof e!="object"&&Object.defineProperty(n,e,t),t);function Q(n,e){return(t,i,s)=>{const o=r=>r.renderRoot?.querySelector(n)??null;return bs(t,i,{get(){return o(this)}})}}let Nc;function $c(n){return(e,t)=>bs(e,t,{get(){return(this.renderRoot??(Nc??=document.createDocumentFragment())).querySelectorAll(n)}})}function Cr(n){return(e,t)=>{const{slot:i,selector:s}=n??{},o="slot"+(i?`[name=${i}]`:":not([name])");return bs(e,t,{get(){const r=this.renderRoot?.querySelector(o),a=r?.assignedElements(n)??[];return s===void 0?a:a.filter((l=>l.matches(s)))}})}}var Oc=F`
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
`,lo=class extends G{render(){return x`
      <div tabindex="-1">
        <div class="spinner"></div>
        <span class="message visually-hidden"><slot /></span>
      </div>
    `}};lo.styles=[Oc],customElements.get("craft-spinner")||customElements.define("craft-spinner",lo);var Lc=class extends Event{constructor(){super("wa-reposition",{bubbles:!0,cancelable:!1,composed:!0})}};const Bi=new Set,_t=new Map;let it,gs="ltr",vs="en";const Sr=typeof MutationObserver<"u"&&typeof document<"u"&&typeof document.documentElement<"u";if(Sr){const n=new MutationObserver(Tr);gs=document.documentElement.dir||"ltr",vs=document.documentElement.lang||navigator.language,n.observe(document.documentElement,{attributes:!0,attributeFilter:["dir","lang"]})}function Ar(...n){n.map(e=>{const t=e.$code.toLowerCase();_t.has(t)?_t.set(t,Object.assign(Object.assign({},_t.get(t)),e)):_t.set(t,e),it||(it=e)}),Tr()}function Tr(){Sr&&(gs=document.documentElement.dir||"ltr",vs=document.documentElement.lang||navigator.language),[...Bi.keys()].map(n=>{typeof n.requestUpdate=="function"&&n.requestUpdate()})}let Fc=class{constructor(e){this.host=e,this.host.addController(this)}hostConnected(){Bi.add(this.host)}hostDisconnected(){Bi.delete(this.host)}dir(){return`${this.host.dir||gs}`.toLowerCase()}lang(){return`${this.host.lang||vs}`.toLowerCase()}getTranslationData(e){var t,i;const s=new Intl.Locale(e.replace(/_/g,"-")),o=s?.language.toLowerCase(),r=(i=(t=s?.region)===null||t===void 0?void 0:t.toLowerCase())!==null&&i!==void 0?i:"",a=_t.get(`${o}-${r}`),l=_t.get(o);return{locale:s,language:o,region:r,primary:a,secondary:l}}exists(e,t){var i;const{primary:s,secondary:o}=this.getTranslationData((i=t.lang)!==null&&i!==void 0?i:this.lang());return t=Object.assign({includeFallback:!1},t),!!(s&&s[e]||o&&o[e]||t.includeFallback&&it&&it[e])}term(e,...t){const{primary:i,secondary:s}=this.getTranslationData(this.lang());let o;if(i&&i[e])o=i[e];else if(s&&s[e])o=s[e];else if(it&&it[e])o=it[e];else return console.error(`No translation found for: ${String(e)}`),String(e);return typeof o=="function"?o(...t):o}date(e,t){return e=new Date(e),new Intl.DateTimeFormat(this.lang(),t).format(e)}number(e,t){return e=Number(e),isNaN(e)?"":new Intl.NumberFormat(this.lang(),t).format(e)}relativeTime(e,t,i){return new Intl.RelativeTimeFormat(this.lang(),i).format(e,t)}};var Nr={$code:"en",$name:"English",$dir:"ltr",carousel:"Carousel",clearEntry:"Clear entry",close:"Close",copied:"Copied",copy:"Copy",currentValue:"Current value",error:"Error",goToSlide:(n,e)=>`Go to slide ${n} of ${e}`,hidePassword:"Hide password",loading:"Loading",nextSlide:"Next slide",numOptionsSelected:n=>n===0?"No options selected":n===1?"1 option selected":`${n} options selected`,pauseAnimation:"Pause animation",playAnimation:"Play animation",previousSlide:"Previous slide",progress:"Progress",remove:"Remove",resize:"Resize",scrollableRegion:"Scrollable region",scrollToEnd:"Scroll to end",scrollToStart:"Scroll to start",selectAColorFromTheScreen:"Select a color from the screen",showPassword:"Show password",slideNum:n=>`Slide ${n}`,toggleColorFormat:"Toggle color format",zoomIn:"Zoom in",zoomOut:"Zoom out"};Ar(Nr);var Rc=Nr;var Ot=class extends Fc{};Ar(Rc);var Mc=Object.defineProperty,Dc=Object.getOwnPropertyDescriptor,$r=n=>{throw TypeError(n)},v=(n,e,t,i)=>{for(var s=i>1?void 0:i?Dc(e,t):e,o=n.length-1,r;o>=0;o--)(r=n[o])&&(s=(i?r(e,t,s):r(s))||s);return i&&s&&Mc(e,t,s),s},Or=(n,e,t)=>e.has(n)||$r("Cannot "+t),Ic=(n,e,t)=>(Or(n,e,"read from private field"),e.get(n)),Pc=(n,e,t)=>e.has(n)?$r("Cannot add the same private member more than once"):e instanceof WeakSet?e.add(n):e.set(n,t),zc=(n,e,t,i)=>(Or(n,e,"write to private field"),e.set(n,t),t);var Vc=`:host {
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
`,Tn,ge=class extends G{constructor(){super(),Pc(this,Tn,!1),this.initialReflectedProperties=new Map,this.didSSR=!!this.shadowRoot,this.customStates={set:(e,t)=>{if(this.internals?.states)try{t?this.internals.states.add(e):this.internals.states.delete(e)}catch(i){if(String(i).includes("must start with '--'"))console.error("Your browser implements an outdated version of CustomStateSet. Consider using a polyfill");else throw i}},has:e=>{if(!this.internals?.states)return!1;try{return this.internals.states.has(e)}catch{return!1}}};try{this.internals=this.attachInternals()}catch{console.error("Element internals are not supported in your browser. Consider using a polyfill")}this.customStates.set("wa-defined",!0);let n=this.constructor;for(let[e,t]of n.elementProperties)t.default==="inherit"&&t.initial!==void 0&&typeof e=="string"&&this.customStates.set(`initial-${e}-${t.initial}`,!0)}static get styles(){const n=Array.isArray(this.css)?this.css:this.css?[this.css]:[];return[Vc,...n].map(e=>typeof e=="string"?_r(e):e)}attributeChangedCallback(n,e,t){Ic(this,Tn)||(this.constructor.elementProperties.forEach((i,s)=>{i.reflect&&this[s]!=null&&this.initialReflectedProperties.set(s,this[s])}),zc(this,Tn,!0)),super.attributeChangedCallback(n,e,t)}willUpdate(n){super.willUpdate(n),this.initialReflectedProperties.forEach((e,t)=>{n.has(t)&&this[t]==null&&(this[t]=e)})}firstUpdated(n){super.firstUpdated(n),this.didSSR&&this.shadowRoot?.querySelectorAll("slot").forEach(e=>{e.dispatchEvent(new Event("slotchange",{bubbles:!0,composed:!1,cancelable:!1}))})}update(n){try{super.update(n)}catch(e){if(this.didSSR&&!this.hasUpdated){const t=new Event("lit-hydration-error",{bubbles:!0,composed:!0,cancelable:!1});t.error=e,this.dispatchEvent(t)}throw e}}relayNativeEvent(n,e){n.stopImmediatePropagation(),this.dispatchEvent(new n.constructor(n.type,{...n,...e}))}};Tn=new WeakMap;v([w()],ge.prototype,"dir",2);v([w()],ge.prototype,"lang",2);v([w({type:Boolean,reflect:!0,attribute:"did-ssr"})],ge.prototype,"didSSR",2);const Ze=Math.min,ye=Math.max,Dn=Math.round,bn=Math.floor,Re=n=>({x:n,y:n}),Bc={left:"right",right:"left",bottom:"top",top:"bottom"},Uc={start:"end",end:"start"};function Ui(n,e,t){return ye(n,Ze(e,t))}function Lt(n,e){return typeof n=="function"?n(e):n}function Qe(n){return n.split("-")[0]}function Ft(n){return n.split("-")[1]}function Lr(n){return n==="x"?"y":"x"}function ys(n){return n==="y"?"height":"width"}const Hc=new Set(["top","bottom"]);function Ue(n){return Hc.has(Qe(n))?"y":"x"}function _s(n){return Lr(Ue(n))}function qc(n,e,t){t===void 0&&(t=!1);const i=Ft(n),s=_s(n),o=ys(s);let r=s==="x"?i===(t?"end":"start")?"right":"left":i==="start"?"bottom":"top";return e.reference[o]>e.floating[o]&&(r=In(r)),[r,In(r)]}function jc(n){const e=In(n);return[Hi(n),e,Hi(e)]}function Hi(n){return n.replace(/start|end/g,e=>Uc[e])}const co=["left","right"],uo=["right","left"],Wc=["top","bottom"],Kc=["bottom","top"];function Gc(n,e,t){switch(n){case"top":case"bottom":return t?e?uo:co:e?co:uo;case"left":case"right":return e?Wc:Kc;default:return[]}}function Yc(n,e,t,i){const s=Ft(n);let o=Gc(Qe(n),t==="start",i);return s&&(o=o.map(r=>r+"-"+s),e&&(o=o.concat(o.map(Hi)))),o}function In(n){return n.replace(/left|right|bottom|top/g,e=>Bc[e])}function Xc(n){return{top:0,right:0,bottom:0,left:0,...n}}function Fr(n){return typeof n!="number"?Xc(n):{top:n,right:n,bottom:n,left:n}}function Pn(n){const{x:e,y:t,width:i,height:s}=n;return{width:i,height:s,top:t,left:e,right:e+i,bottom:t+s,x:e,y:t}}function ho(n,e,t){let{reference:i,floating:s}=n;const o=Ue(e),r=_s(e),a=ys(r),l=Qe(e),d=o==="y",c=i.x+i.width/2-s.width/2,p=i.y+i.height/2-s.height/2,m=i[a]/2-s[a]/2;let b;switch(l){case"top":b={x:c,y:i.y-s.height};break;case"bottom":b={x:c,y:i.y+i.height};break;case"right":b={x:i.x+i.width,y:p};break;case"left":b={x:i.x-s.width,y:p};break;default:b={x:i.x,y:i.y}}switch(Ft(e)){case"start":b[r]-=m*(t&&d?-1:1);break;case"end":b[r]+=m*(t&&d?-1:1);break}return b}const Jc=async(n,e,t)=>{const{placement:i="bottom",strategy:s="absolute",middleware:o=[],platform:r}=t,a=o.filter(Boolean),l=await(r.isRTL==null?void 0:r.isRTL(e));let d=await r.getElementRects({reference:n,floating:e,strategy:s}),{x:c,y:p}=ho(d,i,l),m=i,b={},f=0;for(let g=0;g<a.length;g++){const{name:y,fn:E}=a[g],{x:C,y:A,data:S,reset:L}=await E({x:c,y:p,initialPlacement:i,placement:m,strategy:s,middlewareData:b,rects:d,platform:r,elements:{reference:n,floating:e}});c=C??c,p=A??p,b={...b,[y]:{...b[y],...S}},L&&f<=50&&(f++,typeof L=="object"&&(L.placement&&(m=L.placement),L.rects&&(d=L.rects===!0?await r.getElementRects({reference:n,floating:e,strategy:s}):L.rects),{x:c,y:p}=ho(d,m,l)),g=-1)}return{x:c,y:p,placement:m,strategy:s,middlewareData:b}};async function ws(n,e){var t;e===void 0&&(e={});const{x:i,y:s,platform:o,rects:r,elements:a,strategy:l}=n,{boundary:d="clippingAncestors",rootBoundary:c="viewport",elementContext:p="floating",altBoundary:m=!1,padding:b=0}=Lt(e,n),f=Fr(b),y=a[m?p==="floating"?"reference":"floating":p],E=Pn(await o.getClippingRect({element:(t=await(o.isElement==null?void 0:o.isElement(y)))==null||t?y:y.contextElement||await(o.getDocumentElement==null?void 0:o.getDocumentElement(a.floating)),boundary:d,rootBoundary:c,strategy:l})),C=p==="floating"?{x:i,y:s,width:r.floating.width,height:r.floating.height}:r.reference,A=await(o.getOffsetParent==null?void 0:o.getOffsetParent(a.floating)),S=await(o.isElement==null?void 0:o.isElement(A))?await(o.getScale==null?void 0:o.getScale(A))||{x:1,y:1}:{x:1,y:1},L=Pn(o.convertOffsetParentRelativeRectToViewportRelativeRect?await o.convertOffsetParentRelativeRectToViewportRelativeRect({elements:a,rect:C,offsetParent:A,strategy:l}):C);return{top:(E.top-L.top+f.top)/S.y,bottom:(L.bottom-E.bottom+f.bottom)/S.y,left:(E.left-L.left+f.left)/S.x,right:(L.right-E.right+f.right)/S.x}}const Zc=n=>({name:"arrow",options:n,async fn(e){const{x:t,y:i,placement:s,rects:o,platform:r,elements:a,middlewareData:l}=e,{element:d,padding:c=0}=Lt(n,e)||{};if(d==null)return{};const p=Fr(c),m={x:t,y:i},b=_s(s),f=ys(b),g=await r.getDimensions(d),y=b==="y",E=y?"top":"left",C=y?"bottom":"right",A=y?"clientHeight":"clientWidth",S=o.reference[f]+o.reference[b]-m[b]-o.floating[f],L=m[b]-o.reference[b],U=await(r.getOffsetParent==null?void 0:r.getOffsetParent(d));let P=U?U[A]:0;(!P||!await(r.isElement==null?void 0:r.isElement(U)))&&(P=a.floating[A]||o.floating[f]);const Z=S/2-L/2,X=P/2-g[f]/2-1,I=Ze(p[E],X),ve=Ze(p[C],X),se=I,u=P-g[f]-ve,k=P/2-g[f]/2+Z,N=Ui(se,k,u),O=!l.arrow&&Ft(s)!=null&&k!==N&&o.reference[f]/2-(k<se?I:ve)-g[f]/2<0,M=O?k<se?k-se:k-u:0;return{[b]:m[b]+M,data:{[b]:N,centerOffset:k-N-M,...O&&{alignmentOffset:M}},reset:O}}}),Qc=function(n){return n===void 0&&(n={}),{name:"flip",options:n,async fn(e){var t,i;const{placement:s,middlewareData:o,rects:r,initialPlacement:a,platform:l,elements:d}=e,{mainAxis:c=!0,crossAxis:p=!0,fallbackPlacements:m,fallbackStrategy:b="bestFit",fallbackAxisSideDirection:f="none",flipAlignment:g=!0,...y}=Lt(n,e);if((t=o.arrow)!=null&&t.alignmentOffset)return{};const E=Qe(s),C=Ue(a),A=Qe(a)===a,S=await(l.isRTL==null?void 0:l.isRTL(d.floating)),L=m||(A||!g?[In(a)]:jc(a)),U=f!=="none";!m&&U&&L.push(...Yc(a,g,f,S));const P=[a,...L],Z=await ws(e,y),X=[];let I=((i=o.flip)==null?void 0:i.overflows)||[];if(c&&X.push(Z[E]),p){const k=qc(s,r,S);X.push(Z[k[0]],Z[k[1]])}if(I=[...I,{placement:s,overflows:X}],!X.every(k=>k<=0)){var ve,se;const k=(((ve=o.flip)==null?void 0:ve.index)||0)+1,N=P[k];if(N&&(!(p==="alignment"?C!==Ue(N):!1)||I.every(T=>Ue(T.placement)===C?T.overflows[0]>0:!0)))return{data:{index:k,overflows:I},reset:{placement:N}};let O=(se=I.filter(M=>M.overflows[0]<=0).sort((M,T)=>M.overflows[1]-T.overflows[1])[0])==null?void 0:se.placement;if(!O)switch(b){case"bestFit":{var u;const M=(u=I.filter(T=>{if(U){const j=Ue(T.placement);return j===C||j==="y"}return!0}).map(T=>[T.placement,T.overflows.filter(j=>j>0).reduce((j,pe)=>j+pe,0)]).sort((T,j)=>T[1]-j[1])[0])==null?void 0:u[0];M&&(O=M);break}case"initialPlacement":O=a;break}if(s!==O)return{reset:{placement:O}}}return{}}}},ed=new Set(["left","top"]);async function td(n,e){const{placement:t,platform:i,elements:s}=n,o=await(i.isRTL==null?void 0:i.isRTL(s.floating)),r=Qe(t),a=Ft(t),l=Ue(t)==="y",d=ed.has(r)?-1:1,c=o&&l?-1:1,p=Lt(e,n);let{mainAxis:m,crossAxis:b,alignmentAxis:f}=typeof p=="number"?{mainAxis:p,crossAxis:0,alignmentAxis:null}:{mainAxis:p.mainAxis||0,crossAxis:p.crossAxis||0,alignmentAxis:p.alignmentAxis};return a&&typeof f=="number"&&(b=a==="end"?f*-1:f),l?{x:b*c,y:m*d}:{x:m*d,y:b*c}}const nd=function(n){return n===void 0&&(n=0),{name:"offset",options:n,async fn(e){var t,i;const{x:s,y:o,placement:r,middlewareData:a}=e,l=await td(e,n);return r===((t=a.offset)==null?void 0:t.placement)&&(i=a.arrow)!=null&&i.alignmentOffset?{}:{x:s+l.x,y:o+l.y,data:{...l,placement:r}}}}},id=function(n){return n===void 0&&(n={}),{name:"shift",options:n,async fn(e){const{x:t,y:i,placement:s}=e,{mainAxis:o=!0,crossAxis:r=!1,limiter:a={fn:y=>{let{x:E,y:C}=y;return{x:E,y:C}}},...l}=Lt(n,e),d={x:t,y:i},c=await ws(e,l),p=Ue(Qe(s)),m=Lr(p);let b=d[m],f=d[p];if(o){const y=m==="y"?"top":"left",E=m==="y"?"bottom":"right",C=b+c[y],A=b-c[E];b=Ui(C,b,A)}if(r){const y=p==="y"?"top":"left",E=p==="y"?"bottom":"right",C=f+c[y],A=f-c[E];f=Ui(C,f,A)}const g=a.fn({...e,[m]:b,[p]:f});return{...g,data:{x:g.x-t,y:g.y-i,enabled:{[m]:o,[p]:r}}}}}},sd=function(n){return n===void 0&&(n={}),{name:"size",options:n,async fn(e){var t,i;const{placement:s,rects:o,platform:r,elements:a}=e,{apply:l=()=>{},...d}=Lt(n,e),c=await ws(e,d),p=Qe(s),m=Ft(s),b=Ue(s)==="y",{width:f,height:g}=o.floating;let y,E;p==="top"||p==="bottom"?(y=p,E=m===(await(r.isRTL==null?void 0:r.isRTL(a.floating))?"start":"end")?"left":"right"):(E=p,y=m==="end"?"top":"bottom");const C=g-c.top-c.bottom,A=f-c.left-c.right,S=Ze(g-c[y],C),L=Ze(f-c[E],A),U=!e.middlewareData.shift;let P=S,Z=L;if((t=e.middlewareData.shift)!=null&&t.enabled.x&&(Z=A),(i=e.middlewareData.shift)!=null&&i.enabled.y&&(P=C),U&&!m){const I=ye(c.left,0),ve=ye(c.right,0),se=ye(c.top,0),u=ye(c.bottom,0);b?Z=f-2*(I!==0||ve!==0?I+ve:ye(c.left,c.right)):P=g-2*(se!==0||u!==0?se+u:ye(c.top,c.bottom))}await l({...e,availableWidth:Z,availableHeight:P});const X=await r.getDimensions(a.floating);return f!==X.width||g!==X.height?{reset:{rects:!0}}:{}}}};function Zn(){return typeof window<"u"}function Rt(n){return Rr(n)?(n.nodeName||"").toLowerCase():"#document"}function we(n){var e;return(n==null||(e=n.ownerDocument)==null?void 0:e.defaultView)||window}function Pe(n){var e;return(e=(Rr(n)?n.ownerDocument:n.document)||window.document)==null?void 0:e.documentElement}function Rr(n){return Zn()?n instanceof Node||n instanceof we(n).Node:!1}function Ae(n){return Zn()?n instanceof Element||n instanceof we(n).Element:!1}function De(n){return Zn()?n instanceof HTMLElement||n instanceof we(n).HTMLElement:!1}function po(n){return!Zn()||typeof ShadowRoot>"u"?!1:n instanceof ShadowRoot||n instanceof we(n).ShadowRoot}const od=new Set(["inline","contents"]);function an(n){const{overflow:e,overflowX:t,overflowY:i,display:s}=Te(n);return/auto|scroll|overlay|hidden|clip/.test(e+i+t)&&!od.has(s)}const rd=new Set(["table","td","th"]);function ad(n){return rd.has(Rt(n))}const ld=[":popover-open",":modal"];function Qn(n){return ld.some(e=>{try{return n.matches(e)}catch{return!1}})}const cd=["transform","translate","scale","rotate","perspective"],dd=["transform","translate","scale","rotate","perspective","filter"],ud=["paint","layout","strict","content"];function ei(n){const e=Es(),t=Ae(n)?Te(n):n;return cd.some(i=>t[i]?t[i]!=="none":!1)||(t.containerType?t.containerType!=="normal":!1)||!e&&(t.backdropFilter?t.backdropFilter!=="none":!1)||!e&&(t.filter?t.filter!=="none":!1)||dd.some(i=>(t.willChange||"").includes(i))||ud.some(i=>(t.contain||"").includes(i))}function hd(n){let e=et(n);for(;De(e)&&!kt(e);){if(ei(e))return e;if(Qn(e))return null;e=et(e)}return null}function Es(){return typeof CSS>"u"||!CSS.supports?!1:CSS.supports("-webkit-backdrop-filter","none")}const pd=new Set(["html","body","#document"]);function kt(n){return pd.has(Rt(n))}function Te(n){return we(n).getComputedStyle(n)}function ti(n){return Ae(n)?{scrollLeft:n.scrollLeft,scrollTop:n.scrollTop}:{scrollLeft:n.scrollX,scrollTop:n.scrollY}}function et(n){if(Rt(n)==="html")return n;const e=n.assignedSlot||n.parentNode||po(n)&&n.host||Pe(n);return po(e)?e.host:e}function Mr(n){const e=et(n);return kt(e)?n.ownerDocument?n.ownerDocument.body:n.body:De(e)&&an(e)?e:Mr(e)}function Ct(n,e,t){var i;e===void 0&&(e=[]),t===void 0&&(t=!0);const s=Mr(n),o=s===((i=n.ownerDocument)==null?void 0:i.body),r=we(s);if(o){const a=qi(r);return e.concat(r,r.visualViewport||[],an(s)?s:[],a&&t?Ct(a):[])}return e.concat(s,Ct(s,[],t))}function qi(n){return n.parent&&Object.getPrototypeOf(n.parent)?n.frameElement:null}function Dr(n){const e=Te(n);let t=parseFloat(e.width)||0,i=parseFloat(e.height)||0;const s=De(n),o=s?n.offsetWidth:t,r=s?n.offsetHeight:i,a=Dn(t)!==o||Dn(i)!==r;return a&&(t=o,i=r),{width:t,height:i,$:a}}function xs(n){return Ae(n)?n:n.contextElement}function wt(n){const e=xs(n);if(!De(e))return Re(1);const t=e.getBoundingClientRect(),{width:i,height:s,$:o}=Dr(e);let r=(o?Dn(t.width):t.width)/i,a=(o?Dn(t.height):t.height)/s;return(!r||!Number.isFinite(r))&&(r=1),(!a||!Number.isFinite(a))&&(a=1),{x:r,y:a}}const fd=Re(0);function Ir(n){const e=we(n);return!Es()||!e.visualViewport?fd:{x:e.visualViewport.offsetLeft,y:e.visualViewport.offsetTop}}function md(n,e,t){return e===void 0&&(e=!1),!t||e&&t!==we(n)?!1:e}function ht(n,e,t,i){e===void 0&&(e=!1),t===void 0&&(t=!1);const s=n.getBoundingClientRect(),o=xs(n);let r=Re(1);e&&(i?Ae(i)&&(r=wt(i)):r=wt(n));const a=md(o,t,i)?Ir(o):Re(0);let l=(s.left+a.x)/r.x,d=(s.top+a.y)/r.y,c=s.width/r.x,p=s.height/r.y;if(o){const m=we(o),b=i&&Ae(i)?we(i):i;let f=m,g=qi(f);for(;g&&i&&b!==f;){const y=wt(g),E=g.getBoundingClientRect(),C=Te(g),A=E.left+(g.clientLeft+parseFloat(C.paddingLeft))*y.x,S=E.top+(g.clientTop+parseFloat(C.paddingTop))*y.y;l*=y.x,d*=y.y,c*=y.x,p*=y.y,l+=A,d+=S,f=we(g),g=qi(f)}}return Pn({width:c,height:p,x:l,y:d})}function ni(n,e){const t=ti(n).scrollLeft;return e?e.left+t:ht(Pe(n)).left+t}function Pr(n,e){const t=n.getBoundingClientRect(),i=t.left+e.scrollLeft-ni(n,t),s=t.top+e.scrollTop;return{x:i,y:s}}function bd(n){let{elements:e,rect:t,offsetParent:i,strategy:s}=n;const o=s==="fixed",r=Pe(i),a=e?Qn(e.floating):!1;if(i===r||a&&o)return t;let l={scrollLeft:0,scrollTop:0},d=Re(1);const c=Re(0),p=De(i);if((p||!p&&!o)&&((Rt(i)!=="body"||an(r))&&(l=ti(i)),De(i))){const b=ht(i);d=wt(i),c.x=b.x+i.clientLeft,c.y=b.y+i.clientTop}const m=r&&!p&&!o?Pr(r,l):Re(0);return{width:t.width*d.x,height:t.height*d.y,x:t.x*d.x-l.scrollLeft*d.x+c.x+m.x,y:t.y*d.y-l.scrollTop*d.y+c.y+m.y}}function gd(n){return Array.from(n.getClientRects())}function vd(n){const e=Pe(n),t=ti(n),i=n.ownerDocument.body,s=ye(e.scrollWidth,e.clientWidth,i.scrollWidth,i.clientWidth),o=ye(e.scrollHeight,e.clientHeight,i.scrollHeight,i.clientHeight);let r=-t.scrollLeft+ni(n);const a=-t.scrollTop;return Te(i).direction==="rtl"&&(r+=ye(e.clientWidth,i.clientWidth)-s),{width:s,height:o,x:r,y:a}}const fo=25;function yd(n,e){const t=we(n),i=Pe(n),s=t.visualViewport;let o=i.clientWidth,r=i.clientHeight,a=0,l=0;if(s){o=s.width,r=s.height;const c=Es();(!c||c&&e==="fixed")&&(a=s.offsetLeft,l=s.offsetTop)}const d=ni(i);if(d<=0){const c=i.ownerDocument,p=c.body,m=getComputedStyle(p),b=c.compatMode==="CSS1Compat"&&parseFloat(m.marginLeft)+parseFloat(m.marginRight)||0,f=Math.abs(i.clientWidth-p.clientWidth-b);f<=fo&&(o-=f)}else d<=fo&&(o+=d);return{width:o,height:r,x:a,y:l}}const _d=new Set(["absolute","fixed"]);function wd(n,e){const t=ht(n,!0,e==="fixed"),i=t.top+n.clientTop,s=t.left+n.clientLeft,o=De(n)?wt(n):Re(1),r=n.clientWidth*o.x,a=n.clientHeight*o.y,l=s*o.x,d=i*o.y;return{width:r,height:a,x:l,y:d}}function mo(n,e,t){let i;if(e==="viewport")i=yd(n,t);else if(e==="document")i=vd(Pe(n));else if(Ae(e))i=wd(e,t);else{const s=Ir(n);i={x:e.x-s.x,y:e.y-s.y,width:e.width,height:e.height}}return Pn(i)}function zr(n,e){const t=et(n);return t===e||!Ae(t)||kt(t)?!1:Te(t).position==="fixed"||zr(t,e)}function Ed(n,e){const t=e.get(n);if(t)return t;let i=Ct(n,[],!1).filter(a=>Ae(a)&&Rt(a)!=="body"),s=null;const o=Te(n).position==="fixed";let r=o?et(n):n;for(;Ae(r)&&!kt(r);){const a=Te(r),l=ei(r);!l&&a.position==="fixed"&&(s=null),(o?!l&&!s:!l&&a.position==="static"&&!!s&&_d.has(s.position)||an(r)&&!l&&zr(n,r))?i=i.filter(c=>c!==r):s=a,r=et(r)}return e.set(n,i),i}function xd(n){let{element:e,boundary:t,rootBoundary:i,strategy:s}=n;const r=[...t==="clippingAncestors"?Qn(e)?[]:Ed(e,this._c):[].concat(t),i],a=r[0],l=r.reduce((d,c)=>{const p=mo(e,c,s);return d.top=ye(p.top,d.top),d.right=Ze(p.right,d.right),d.bottom=Ze(p.bottom,d.bottom),d.left=ye(p.left,d.left),d},mo(e,a,s));return{width:l.right-l.left,height:l.bottom-l.top,x:l.left,y:l.top}}function kd(n){const{width:e,height:t}=Dr(n);return{width:e,height:t}}function Cd(n,e,t){const i=De(e),s=Pe(e),o=t==="fixed",r=ht(n,!0,o,e);let a={scrollLeft:0,scrollTop:0};const l=Re(0);function d(){l.x=ni(s)}if(i||!i&&!o)if((Rt(e)!=="body"||an(s))&&(a=ti(e)),i){const b=ht(e,!0,o,e);l.x=b.x+e.clientLeft,l.y=b.y+e.clientTop}else s&&d();o&&!i&&s&&d();const c=s&&!i&&!o?Pr(s,a):Re(0),p=r.left+a.scrollLeft-l.x-c.x,m=r.top+a.scrollTop-l.y-c.y;return{x:p,y:m,width:r.width,height:r.height}}function pi(n){return Te(n).position==="static"}function bo(n,e){if(!De(n)||Te(n).position==="fixed")return null;if(e)return e(n);let t=n.offsetParent;return Pe(n)===t&&(t=t.ownerDocument.body),t}function Vr(n,e){const t=we(n);if(Qn(n))return t;if(!De(n)){let s=et(n);for(;s&&!kt(s);){if(Ae(s)&&!pi(s))return s;s=et(s)}return t}let i=bo(n,e);for(;i&&ad(i)&&pi(i);)i=bo(i,e);return i&&kt(i)&&pi(i)&&!ei(i)?t:i||hd(n)||t}const Sd=async function(n){const e=this.getOffsetParent||Vr,t=this.getDimensions,i=await t(n.floating);return{reference:Cd(n.reference,await e(n.floating),n.strategy),floating:{x:0,y:0,width:i.width,height:i.height}}};function Ad(n){return Te(n).direction==="rtl"}const Nn={convertOffsetParentRelativeRectToViewportRelativeRect:bd,getDocumentElement:Pe,getClippingRect:xd,getOffsetParent:Vr,getElementRects:Sd,getClientRects:gd,getDimensions:kd,getScale:wt,isElement:Ae,isRTL:Ad};function Br(n,e){return n.x===e.x&&n.y===e.y&&n.width===e.width&&n.height===e.height}function Td(n,e){let t=null,i;const s=Pe(n);function o(){var a;clearTimeout(i),(a=t)==null||a.disconnect(),t=null}function r(a,l){a===void 0&&(a=!1),l===void 0&&(l=1),o();const d=n.getBoundingClientRect(),{left:c,top:p,width:m,height:b}=d;if(a||e(),!m||!b)return;const f=bn(p),g=bn(s.clientWidth-(c+m)),y=bn(s.clientHeight-(p+b)),E=bn(c),A={rootMargin:-f+"px "+-g+"px "+-y+"px "+-E+"px",threshold:ye(0,Ze(1,l))||1};let S=!0;function L(U){const P=U[0].intersectionRatio;if(P!==l){if(!S)return r();P?r(!1,P):i=setTimeout(()=>{r(!1,1e-7)},1e3)}P===1&&!Br(d,n.getBoundingClientRect())&&r(),S=!1}try{t=new IntersectionObserver(L,{...A,root:s.ownerDocument})}catch{t=new IntersectionObserver(L,A)}t.observe(n)}return r(!0),o}function Ur(n,e,t,i){i===void 0&&(i={});const{ancestorScroll:s=!0,ancestorResize:o=!0,elementResize:r=typeof ResizeObserver=="function",layoutShift:a=typeof IntersectionObserver=="function",animationFrame:l=!1}=i,d=xs(n),c=s||o?[...d?Ct(d):[],...Ct(e)]:[];c.forEach(E=>{s&&E.addEventListener("scroll",t,{passive:!0}),o&&E.addEventListener("resize",t)});const p=d&&a?Td(d,t):null;let m=-1,b=null;r&&(b=new ResizeObserver(E=>{let[C]=E;C&&C.target===d&&b&&(b.unobserve(e),cancelAnimationFrame(m),m=requestAnimationFrame(()=>{var A;(A=b)==null||A.observe(e)})),t()}),d&&!l&&b.observe(d),b.observe(e));let f,g=l?ht(n):null;l&&y();function y(){const E=ht(n);g&&!Br(g,E)&&t(),g=E,f=requestAnimationFrame(y)}return t(),()=>{var E;c.forEach(C=>{s&&C.removeEventListener("scroll",t),o&&C.removeEventListener("resize",t)}),p?.(),(E=b)==null||E.disconnect(),b=null,l&&cancelAnimationFrame(f)}}const Hr=nd,qr=id,jr=Qc,go=sd,Nd=Zc,Wr=(n,e,t)=>{const i=new Map,s={platform:Nn,...t},o={...s.platform,_c:i};return Jc(n,e,{...s,platform:o})};function $d(n){return Od(n)}function fi(n){return n.assignedSlot?n.assignedSlot:n.parentNode instanceof ShadowRoot?n.parentNode.host:n.parentNode}function Od(n){for(let e=n;e;e=fi(e))if(e instanceof Element&&getComputedStyle(e).display==="none")return null;for(let e=fi(n);e;e=fi(e)){if(!(e instanceof Element))continue;const t=getComputedStyle(e);if(t.display!=="contents"&&(t.position!=="static"||ei(t)||e.tagName==="BODY"))return e}return null}const ks={ATTRIBUTE:1,CHILD:2},Cs=n=>(...e)=>({_$litDirective$:n,values:e});let Ss=class{constructor(e){}get _$AU(){return this._$AM._$AU}_$AT(e,t,i){this._$Ct=e,this._$AM=t,this._$Ci=i}_$AS(e,t){return this.update(e,t)}update(e,t){return this.render(...t)}};const Ie=Cs(class extends Ss{constructor(n){if(super(n),n.type!==ks.ATTRIBUTE||n.name!=="class"||n.strings?.length>2)throw Error("`classMap()` can only be used in the `class` attribute and must be the only part in the attribute.")}render(n){return" "+Object.keys(n).filter((e=>n[e])).join(" ")+" "}update(n,[e]){if(this.st===void 0){this.st=new Set,n.strings!==void 0&&(this.nt=new Set(n.strings.join(" ").split(/\s/).filter((i=>i!==""))));for(const i in e)e[i]&&!this.nt?.has(i)&&this.st.add(i);return this.render(e)}const t=n.element.classList;for(const i of this.st)i in e||(t.remove(i),this.st.delete(i));for(const i in e){const s=!!e[i];s===this.st.has(i)||this.nt?.has(i)||(s?(t.add(i),this.st.add(i)):(t.remove(i),this.st.delete(i)))}return Me}});var Ld=`:host {
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
`;function vo(n){return n!==null&&typeof n=="object"&&"getBoundingClientRect"in n&&("contextElement"in n?n instanceof Element:!0)}var gn=globalThis?.HTMLElement?.prototype.hasOwnProperty("popover"),Y=class extends ge{constructor(){super(...arguments),this.localize=new Ot(this),this.active=!1,this.placement="top",this.boundary="viewport",this.distance=0,this.skidding=0,this.arrow=!1,this.arrowPlacement="anchor",this.arrowPadding=10,this.flip=!1,this.flipFallbackPlacements="",this.flipFallbackStrategy="best-fit",this.flipPadding=0,this.shift=!1,this.shiftPadding=0,this.autoSizePadding=0,this.hoverBridge=!1,this.updateHoverBridge=()=>{if(this.hoverBridge&&this.anchorEl){const n=this.anchorEl.getBoundingClientRect(),e=this.popup.getBoundingClientRect(),t=this.placement.includes("top")||this.placement.includes("bottom");let i=0,s=0,o=0,r=0,a=0,l=0,d=0,c=0;t?n.top<e.top?(i=n.left,s=n.bottom,o=n.right,r=n.bottom,a=e.left,l=e.top,d=e.right,c=e.top):(i=e.left,s=e.bottom,o=e.right,r=e.bottom,a=n.left,l=n.top,d=n.right,c=n.top):n.left<e.left?(i=n.right,s=n.top,o=e.left,r=e.top,a=n.right,l=n.bottom,d=e.left,c=e.bottom):(i=e.right,s=e.top,o=n.left,r=n.top,a=e.right,l=e.bottom,d=n.left,c=n.bottom),this.style.setProperty("--hover-bridge-top-left-x",`${i}px`),this.style.setProperty("--hover-bridge-top-left-y",`${s}px`),this.style.setProperty("--hover-bridge-top-right-x",`${o}px`),this.style.setProperty("--hover-bridge-top-right-y",`${r}px`),this.style.setProperty("--hover-bridge-bottom-left-x",`${a}px`),this.style.setProperty("--hover-bridge-bottom-left-y",`${l}px`),this.style.setProperty("--hover-bridge-bottom-right-x",`${d}px`),this.style.setProperty("--hover-bridge-bottom-right-y",`${c}px`)}}}async connectedCallback(){super.connectedCallback(),await this.updateComplete,this.start()}disconnectedCallback(){super.disconnectedCallback(),this.stop()}async updated(n){super.updated(n),n.has("active")&&(this.active?this.start():this.stop()),n.has("anchor")&&this.handleAnchorChange(),this.active&&(await this.updateComplete,this.reposition())}async handleAnchorChange(){if(await this.stop(),this.anchor&&typeof this.anchor=="string"){const n=this.getRootNode();this.anchorEl=n.getElementById(this.anchor)}else this.anchor instanceof Element||vo(this.anchor)?this.anchorEl=this.anchor:this.anchorEl=this.querySelector('[slot="anchor"]');this.anchorEl instanceof HTMLSlotElement&&(this.anchorEl=this.anchorEl.assignedElements({flatten:!0})[0]),this.anchorEl&&this.start()}start(){!this.anchorEl||!this.active||(this.popup.showPopover?.(),this.cleanup=Ur(this.anchorEl,this.popup,()=>{this.reposition()}))}async stop(){return new Promise(n=>{this.popup.hidePopover?.(),this.cleanup?(this.cleanup(),this.cleanup=void 0,this.removeAttribute("data-current-placement"),this.style.removeProperty("--auto-size-available-width"),this.style.removeProperty("--auto-size-available-height"),requestAnimationFrame(()=>n())):n()})}reposition(){if(!this.active||!this.anchorEl)return;const n=[Hr({mainAxis:this.distance,crossAxis:this.skidding})];this.sync?n.push(go({apply:({rects:i})=>{const s=this.sync==="width"||this.sync==="both",o=this.sync==="height"||this.sync==="both";this.popup.style.width=s?`${i.reference.width}px`:"",this.popup.style.height=o?`${i.reference.height}px`:""}})):(this.popup.style.width="",this.popup.style.height="");let e;gn&&!vo(this.anchor)&&this.boundary==="scroll"&&(e=Ct(this.anchorEl).filter(i=>i instanceof Element)),this.flip&&n.push(jr({boundary:this.flipBoundary||e,fallbackPlacements:this.flipFallbackPlacements,fallbackStrategy:this.flipFallbackStrategy==="best-fit"?"bestFit":"initialPlacement",padding:this.flipPadding})),this.shift&&n.push(qr({boundary:this.shiftBoundary||e,padding:this.shiftPadding})),this.autoSize?n.push(go({boundary:this.autoSizeBoundary||e,padding:this.autoSizePadding,apply:({availableWidth:i,availableHeight:s})=>{this.autoSize==="vertical"||this.autoSize==="both"?this.style.setProperty("--auto-size-available-height",`${s}px`):this.style.removeProperty("--auto-size-available-height"),this.autoSize==="horizontal"||this.autoSize==="both"?this.style.setProperty("--auto-size-available-width",`${i}px`):this.style.removeProperty("--auto-size-available-width")}})):(this.style.removeProperty("--auto-size-available-width"),this.style.removeProperty("--auto-size-available-height")),this.arrow&&n.push(Nd({element:this.arrowEl,padding:this.arrowPadding}));const t=gn?i=>Nn.getOffsetParent(i,$d):Nn.getOffsetParent;Wr(this.anchorEl,this.popup,{placement:this.placement,middleware:n,strategy:gn?"absolute":"fixed",platform:{...Nn,getOffsetParent:t}}).then(({x:i,y:s,middlewareData:o,placement:r})=>{const a=this.localize.dir()==="rtl",l={top:"bottom",right:"left",bottom:"top",left:"right"}[r.split("-")[0]];if(this.setAttribute("data-current-placement",r),Object.assign(this.popup.style,{left:`${i}px`,top:`${s}px`}),this.arrow){const d=o.arrow.x,c=o.arrow.y;let p="",m="",b="",f="";if(this.arrowPlacement==="start"){const g=typeof d=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"";p=typeof c=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"",m=a?g:"",f=a?"":g}else if(this.arrowPlacement==="end"){const g=typeof d=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"";m=a?"":g,f=a?g:"",b=typeof c=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:""}else this.arrowPlacement==="center"?(f=typeof d=="number"?"calc(50% - var(--arrow-size-diagonal))":"",p=typeof c=="number"?"calc(50% - var(--arrow-size-diagonal))":""):(f=typeof d=="number"?`${d}px`:"",p=typeof c=="number"?`${c}px`:"");Object.assign(this.arrowEl.style,{top:p,right:m,bottom:b,left:f,[l]:"calc(var(--arrow-size-diagonal) * -1)"})}}),requestAnimationFrame(()=>this.updateHoverBridge()),this.dispatchEvent(new Lc)}render(){return x`
      <slot name="anchor" @slotchange=${this.handleAnchorChange}></slot>

      <span
        part="hover-bridge"
        class=${Ie({"popup-hover-bridge":!0,"popup-hover-bridge-visible":this.hoverBridge&&this.active})}
      ></span>

      <div
        popover="manual"
        part="popup"
        class=${Ie({popup:!0,"popup-active":this.active,"popup-fixed":!gn,"popup-has-arrow":this.arrow})}
      >
        <slot></slot>
        ${this.arrow?x`<div part="arrow" class="arrow" role="presentation"></div>`:""}
      </div>
    `}};Y.css=Ld;v([Q(".popup")],Y.prototype,"popup",2);v([Q(".arrow")],Y.prototype,"arrowEl",2);v([w()],Y.prototype,"anchor",2);v([w({type:Boolean,reflect:!0})],Y.prototype,"active",2);v([w({reflect:!0})],Y.prototype,"placement",2);v([w()],Y.prototype,"boundary",2);v([w({type:Number})],Y.prototype,"distance",2);v([w({type:Number})],Y.prototype,"skidding",2);v([w({type:Boolean})],Y.prototype,"arrow",2);v([w({attribute:"arrow-placement"})],Y.prototype,"arrowPlacement",2);v([w({attribute:"arrow-padding",type:Number})],Y.prototype,"arrowPadding",2);v([w({type:Boolean})],Y.prototype,"flip",2);v([w({attribute:"flip-fallback-placements",converter:{fromAttribute:n=>n.split(" ").map(e=>e.trim()).filter(e=>e!==""),toAttribute:n=>n.join(" ")}})],Y.prototype,"flipFallbackPlacements",2);v([w({attribute:"flip-fallback-strategy"})],Y.prototype,"flipFallbackStrategy",2);v([w({type:Object})],Y.prototype,"flipBoundary",2);v([w({attribute:"flip-padding",type:Number})],Y.prototype,"flipPadding",2);v([w({type:Boolean})],Y.prototype,"shift",2);v([w({type:Object})],Y.prototype,"shiftBoundary",2);v([w({attribute:"shift-padding",type:Number})],Y.prototype,"shiftPadding",2);v([w({attribute:"auto-size"})],Y.prototype,"autoSize",2);v([w()],Y.prototype,"sync",2);v([w({type:Object})],Y.prototype,"autoSizeBoundary",2);v([w({attribute:"auto-size-padding",type:Number})],Y.prototype,"autoSizePadding",2);v([w({attribute:"hover-bridge",type:Boolean})],Y.prototype,"hoverBridge",2);Y=v([ke("wa-popup")],Y);var ln=class extends Event{constructor(){super("wa-after-hide",{bubbles:!0,cancelable:!1,composed:!0})}},cn=class extends Event{constructor(){super("wa-after-show",{bubbles:!0,cancelable:!1,composed:!0})}},dn=class extends Event{constructor(n){super("wa-hide",{bubbles:!0,cancelable:!0,composed:!0}),this.detail=n}},un=class extends Event{constructor(){super("wa-show",{bubbles:!0,cancelable:!0,composed:!0})}};const Fd="useandom-26T198340PX75pxJACKVERYMINDBUSHWOLF_GQZbfghjklqvwyzrict";let Rd=(n=21)=>{let e="",t=crypto.getRandomValues(new Uint8Array(n|=0));for(;n--;)e+=Fd[t[n]&63];return e};function As(n=""){return`${n}${Rd()}`}function zn(n,e){return new Promise(t=>{function i(s){s.target===n&&(n.removeEventListener(e,i),t())}n.addEventListener(e,i)})}function de(n,e){return new Promise(t=>{const i=new AbortController,{signal:s}=i;if(n.classList.contains(e))return;n.classList.remove(e),n.classList.add(e);let o=()=>{n.classList.remove(e),t(),i.abort()};n.addEventListener("animationend",o,{once:!0,signal:s}),n.addEventListener("animationcancel",o,{once:!0,signal:s})})}function Ee(n,e){const t={waitUntilFirstUpdate:!1,...e};return(i,s)=>{const{update:o}=i,r=Array.isArray(n)?n:[n];i.update=function(a){r.forEach(l=>{const d=l;if(a.has(d)){const c=a.get(d),p=this[d];c!==p&&(!t.waitUntilFirstUpdate||this.hasUpdated)&&this[s](c,p)}}),o.call(this,a)}}}var Md=`:host {
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
`,ee=class extends ge{constructor(){super(...arguments),this.placement="top",this.disabled=!1,this.distance=8,this.open=!1,this.skidding=0,this.showDelay=150,this.hideDelay=0,this.trigger="hover focus",this.withoutArrow=!1,this.for=null,this.anchor=null,this.eventController=new AbortController,this.handleBlur=()=>{this.hasTrigger("focus")&&this.hide()},this.handleClick=()=>{this.hasTrigger("click")&&(this.open?this.hide():this.show())},this.handleFocus=()=>{this.hasTrigger("focus")&&this.show()},this.handleDocumentKeyDown=n=>{n.key==="Escape"&&(n.stopPropagation(),this.hide())},this.handleMouseOver=()=>{this.hasTrigger("hover")&&(clearTimeout(this.hoverTimeout),this.hoverTimeout=window.setTimeout(()=>this.show(),this.showDelay))},this.handleMouseOut=()=>{this.hasTrigger("hover")&&(clearTimeout(this.hoverTimeout),this.hoverTimeout=window.setTimeout(()=>this.hide(),this.hideDelay))}}connectedCallback(){super.connectedCallback(),this.eventController.signal.aborted&&(this.eventController=new AbortController),this.open&&(this.open=!1,this.updateComplete.then(()=>{this.open=!0})),this.id||(this.id=As("wa-tooltip-")),this.for&&this.anchor?(this.anchor=null,this.handleForChange()):this.for&&this.handleForChange()}disconnectedCallback(){super.disconnectedCallback(),document.removeEventListener("keydown",this.handleDocumentKeyDown),this.eventController.abort(),this.anchor&&this.removeFromAriaLabelledBy(this.anchor,this.id)}firstUpdated(){this.body.hidden=!this.open,this.open&&(this.popup.active=!0,this.popup.reposition())}hasTrigger(n){return this.trigger.split(" ").includes(n)}addToAriaLabelledBy(n,e){const i=(n.getAttribute("aria-labelledby")||"").split(/\s+/).filter(Boolean);i.includes(e)||(i.push(e),n.setAttribute("aria-labelledby",i.join(" ")))}removeFromAriaLabelledBy(n,e){const s=(n.getAttribute("aria-labelledby")||"").split(/\s+/).filter(Boolean).filter(o=>o!==e);s.length>0?n.setAttribute("aria-labelledby",s.join(" ")):n.removeAttribute("aria-labelledby")}async handleOpenChange(){if(this.open){if(this.disabled)return;const n=new un;if(this.dispatchEvent(n),n.defaultPrevented){this.open=!1;return}document.addEventListener("keydown",this.handleDocumentKeyDown,{signal:this.eventController.signal}),this.body.hidden=!1,this.popup.active=!0,await de(this.popup.popup,"show-with-scale"),this.popup.reposition(),this.dispatchEvent(new cn)}else{const n=new dn;if(this.dispatchEvent(n),n.defaultPrevented){this.open=!1;return}document.removeEventListener("keydown",this.handleDocumentKeyDown),await de(this.popup.popup,"hide-with-scale"),this.popup.active=!1,this.body.hidden=!0,this.dispatchEvent(new ln)}}handleForChange(){const n=this.getRootNode();if(!n)return;const e=this.for?n.getElementById(this.for):null,t=this.anchor;if(e===t)return;const{signal:i}=this.eventController;e&&(this.addToAriaLabelledBy(e,this.id),e.addEventListener("blur",this.handleBlur,{capture:!0,signal:i}),e.addEventListener("focus",this.handleFocus,{capture:!0,signal:i}),e.addEventListener("click",this.handleClick,{signal:i}),e.addEventListener("mouseover",this.handleMouseOver,{signal:i}),e.addEventListener("mouseout",this.handleMouseOut,{signal:i})),t&&(this.removeFromAriaLabelledBy(t,this.id),t.removeEventListener("blur",this.handleBlur,{capture:!0}),t.removeEventListener("focus",this.handleFocus,{capture:!0}),t.removeEventListener("click",this.handleClick),t.removeEventListener("mouseover",this.handleMouseOver),t.removeEventListener("mouseout",this.handleMouseOut)),this.anchor=e}async handleOptionsChange(){this.hasUpdated&&(await this.updateComplete,this.popup.reposition())}handleDisabledChange(){this.disabled&&this.open&&this.hide()}async show(){if(!this.open)return this.open=!0,zn(this,"wa-after-show")}async hide(){if(this.open)return this.open=!1,zn(this,"wa-after-hide")}render(){return x`
      <wa-popup
        part="base"
        exportparts="
          popup:base__popup,
          arrow:base__arrow
        "
        class=${Ie({tooltip:!0,"tooltip-open":this.open})}
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
    `}};ee.css=Md;ee.dependencies={"wa-popup":Y};v([Q("slot:not([name])")],ee.prototype,"defaultSlot",2);v([Q(".body")],ee.prototype,"body",2);v([Q("wa-popup")],ee.prototype,"popup",2);v([w()],ee.prototype,"placement",2);v([w({type:Boolean,reflect:!0})],ee.prototype,"disabled",2);v([w({type:Number})],ee.prototype,"distance",2);v([w({type:Boolean,reflect:!0})],ee.prototype,"open",2);v([w({type:Number})],ee.prototype,"skidding",2);v([w({attribute:"show-delay",type:Number})],ee.prototype,"showDelay",2);v([w({attribute:"hide-delay",type:Number})],ee.prototype,"hideDelay",2);v([w()],ee.prototype,"trigger",2);v([w({attribute:"without-arrow",type:Boolean,reflect:!0})],ee.prototype,"withoutArrow",2);v([w()],ee.prototype,"for",2);v([be()],ee.prototype,"anchor",2);v([Ee("open",{waitUntilFirstUpdate:!0})],ee.prototype,"handleOpenChange",1);v([Ee("for")],ee.prototype,"handleForChange",1);v([Ee(["distance","placement","skidding"])],ee.prototype,"handleOptionsChange",1);v([Ee("disabled")],ee.prototype,"handleDisabledChange",1);ee=v([ke("wa-tooltip")],ee);var Dd=class extends ee{static get styles(){return[ee.styles,F`
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
      `]}};customElements.get("c-tooltip")||customElements.define("c-tooltip",Dd);function D(n,e,t,i){var s=arguments.length,o=s<3?e:i,r;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")o=Reflect.decorate(n,e,t,i);else for(var a=n.length-1;a>=0;a--)(r=n[a])&&(o=(s<3?r(o):s>3?r(e,t,o):r(e,t))||o);return s>3&&o&&Object.defineProperty(e,t,o),o}var Id=F`
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
`,Pd=F`
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
`,Kr=Object.defineProperty,yo=Object.getOwnPropertySymbols,zd=Object.prototype.hasOwnProperty,Vd=Object.prototype.propertyIsEnumerable,Gr=n=>{throw TypeError(n)},_o=(n,e,t)=>e in n?Kr(n,e,{enumerable:!0,configurable:!0,writable:!0,value:t}):n[e]=t,Bd=(n,e)=>{for(var t in e||(e={}))zd.call(e,t)&&_o(n,t,e[t]);if(yo)for(var t of yo(e))Vd.call(e,t)&&_o(n,t,e[t]);return n},wo=(n,e,t,i)=>{for(var s=void 0,o=n.length-1,r;o>=0;o--)(r=n[o])&&(s=r(e,t,s)||s);return s&&Kr(e,t,s),s},Yr=(n,e,t)=>e.has(n)||Gr("Cannot "+t),Ud=(n,e,t)=>(Yr(n,e,"read from private field"),e.get(n)),Hd=(n,e,t)=>e.has(n)?Gr("Cannot add the same private member more than once"):e instanceof WeakSet?e.add(n):e.set(n,t),qd=(n,e,t,i)=>(Yr(n,e,"write to private field"),e.set(n,t),t),$n,Bt=class extends G{constructor(){super(),Hd(this,$n,!1),this.initialReflectedProperties=new Map,Object.entries(this.constructor.dependencies).forEach(([n,e])=>{this.constructor.define(n,e)})}emit(n,e){let t=new CustomEvent(n,Bd({bubbles:!0,cancelable:!1,composed:!0,detail:{}},e));return this.dispatchEvent(t),t}static define(n,e=this,t={}){let i=customElements.get(n);if(!i){try{customElements.define(n,e,t)}catch{customElements.define(n,class extends e{},t)}return}let s=" (unknown version)",o=s;"version"in e&&e.version&&(s=" v"+e.version),"version"in i&&i.version&&(o=" v"+i.version),!(s&&o&&s===o)&&console.warn(`Attempted to register <${n}>${s}, but <${n}>${o} has already been registered.`)}attributeChangedCallback(n,e,t){Ud(this,$n)||(this.constructor.elementProperties.forEach((i,s)=>{i.reflect&&this[s]!=null&&this.initialReflectedProperties.set(s,this[s])}),qd(this,$n,!0)),super.attributeChangedCallback(n,e,t)}willUpdate(n){super.willUpdate(n),this.initialReflectedProperties.forEach((e,t)=>{n.has(t)&&this[t]==null&&(this[t]=e)})}};$n=new WeakMap,Bt.version="2.20.1",Bt.dependencies={},wo([w()],Bt.prototype,"dir"),wo([w()],Bt.prototype,"lang");var Eo=class extends Bt{render(){return x` <slot></slot> `}};Eo.styles=[Pd,Id],Eo.define("sl-visually-hidden");var jd=F`
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
`,Pt=class extends G{constructor(...n){super(...n),this.isCopying=!1,this.value="",this.disabled=!1}async copyValue(){if(!(this.isCopying||this.disabled)){this.isCopying=!0;try{await navigator.clipboard.writeText(this.value),this.dispatchEvent(new CustomEvent("craft-copy",{bubbles:!0,cancelable:!1,composed:!0,detail:{value:this.value}}))}catch{this.dispatchEvent(new CustomEvent("craft-error",{cancelable:!1,composed:!0,bubbles:!0}))}finally{this.isCopying=!1}}}render(){return x`
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
    `}};Pt.styles=[jd],D([be()],Pt.prototype,"isCopying",void 0),D([w({type:String})],Pt.prototype,"value",void 0),D([w({type:Boolean})],Pt.prototype,"disabled",void 0),customElements.get("craft-copy-button")||customElements.define("craft-copy-button",Pt);var Wd=F`
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
`,Kd=F`
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
`;const qe={"icon.in":{keyframes:[{scale:.25,opacity:.25},{scale:1,opacity:1}],options:{duration:100}},"icon.out":{keyframes:[{scale:1,opacity:1},{scale:.25,opacity:.25}],options:{duration:100}}};var Ce=class extends G{constructor(){super(),this.status="rest",this.value="",this.disabled=!1,this.feedbackDuration=1e3,this.tooltipLabel="Copy",this.addEventListener("craft-copy",()=>{this.showStatus("success")}),this.addEventListener("craft-error",()=>{this.showStatus("error")})}getId(){return`attribute-${this.value.replace(/([a-z])([A-Z])/g,"$1-$2").replace(/[\s_]+/g,"-").toLowerCase()}`}async showStatus(n){let e=n==="success"?this.successIconEl:this.errorIconEl;this.tooltipLabel=n==="success"?"Copied":"Copy failed",await e.animate(qe["icon.out"].keyframes,qe["icon.out"].options),this.copyIconEl.hidden=!0,e.hidden=!1,await e.animate(qe["icon.in"].keyframes,qe["icon.in"].options),this.status=n,setTimeout(async()=>{await e.animate(qe["icon.out"].keyframes,qe["icon.out"].options),e.hidden=!0,this.copyIconEl.hidden=!1,await this.copyIconEl.animate(qe["icon.in"].keyframes,qe["icon.in"].options),this.status="rest",this.tooltipLabel="Copy"},this.feedbackDuration)}render(){return x`
      <c-tooltip for="${this.getId()}">${this.tooltipLabel}</c-tooltip>
      <craft-copy-button
        value="${this.value}"
        id="${this.getId()}"
        class=${Ie({"copy-attribute":!0,"copy-attribute--success":this.status==="success","copy-attribute--error":this.status==="error"})}
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
    `}};Ce.styles=[Wd,Kd],D([be()],Ce.prototype,"status",void 0),D([Q('slot[name="copy-icon"]')],Ce.prototype,"copyIconEl",void 0),D([Q('slot[name="success-icon"]')],Ce.prototype,"successIconEl",void 0),D([Q('slot[name="error-icon"]')],Ce.prototype,"errorIconEl",void 0),D([Q("craft-copy-button")],Ce.prototype,"copyButtonEl",void 0),D([w({type:String})],Ce.prototype,"value",void 0),D([w({type:Boolean,reflect:!0})],Ce.prototype,"disabled",void 0),D([w({attribute:"feedback-duration",type:Number})],Ce.prototype,"feedbackDuration",void 0),D([w({reflect:!1})],Ce.prototype,"tooltipLabel",void 0),customElements.get("craft-copy-attribute")||customElements.define("craft-copy-attribute",Ce);const Xr=new WeakMap;function Gd(n,e){let t=e;for(;t;){if(Xr.get(t)===n)return!0;t=Object.getPrototypeOf(t)}return!1}function ie(n){return e=>{if(Gd(n,e))return e;const t=n(e);return Xr.set(t,n),t}}const Yd=n=>class extends n{static get properties(){return{disabled:{type:Boolean,reflect:!0}}}constructor(){super(),this._requestedToBeDisabled=!1,this.__isUserSettingDisabled=!0,this.__restoreDisabledTo=!1,this.disabled=!1}makeRequestToBeDisabled(){this._requestedToBeDisabled===!1&&(this._requestedToBeDisabled=!0,this.__restoreDisabledTo=this.disabled,this.__internalSetDisabled(!0))}retractRequestToBeDisabled(){this._requestedToBeDisabled===!0&&(this._requestedToBeDisabled=!1,this.__internalSetDisabled(this.__restoreDisabledTo))}__internalSetDisabled(e){this.__isUserSettingDisabled=!1,this.disabled=e,this.__isUserSettingDisabled=!0}requestUpdate(e,t,i){super.requestUpdate(e,t,i),e==="disabled"&&(this.__isUserSettingDisabled&&(this.__restoreDisabledTo=this.disabled),this.disabled===!1&&this._requestedToBeDisabled===!0&&this.__internalSetDisabled(!0))}click(){this.disabled||super.click()}},hn=ie(Yd),Xd=n=>class extends hn(n){static get properties(){return{tabIndex:{type:Number,reflect:!0,attribute:"tabindex"}}}constructor(){super(),this.__isUserSettingTabIndex=!0,this.__restoreTabIndexTo=0,this.__internalSetTabIndex(0)}makeRequestToBeDisabled(){super.makeRequestToBeDisabled(),this._requestedToBeDisabled===!1&&this.tabIndex!=null&&(this.__restoreTabIndexTo=this.tabIndex)}retractRequestToBeDisabled(){super.retractRequestToBeDisabled(),this._requestedToBeDisabled===!0&&this.__internalSetTabIndex(this.__restoreTabIndexTo)}static enabledWarnings=super.enabledWarnings?.filter(e=>e!=="change-in-update")||[];__internalSetTabIndex(e){this.__isUserSettingTabIndex=!1,this.tabIndex=e,this.__isUserSettingTabIndex=!0}requestUpdate(e,t,i){super.requestUpdate(e,t,i),e==="disabled"&&(this.disabled?this.__internalSetTabIndex(-1):this.__internalSetTabIndex(this.__restoreTabIndexTo)),e==="tabIndex"&&(this.__isUserSettingTabIndex&&this.tabIndex!=null&&(this.__restoreTabIndexTo=this.tabIndex),this.tabIndex!==-1&&this._requestedToBeDisabled===!0&&this.__internalSetTabIndex(-1))}firstUpdated(e){super.firstUpdated(e),this.disabled&&this.__internalSetTabIndex(-1)}},Jr=ie(Xd);const Jd=n=>n===null||typeof n!="object"&&typeof n!="function",Zr=(n,e)=>n?._$litType$!==void 0,Zd=n=>n.strings===void 0;function Qd(n){return n instanceof Node?"node":Zr(n)?"template-result":!Array.isArray(n)&&typeof n=="object"&&"template"in n?"slot-rerender-object":null}const eu=n=>class extends n{get slots(){return{}}constructor(){super(),this.__renderMetaPerSlot=new Map,this.__slotsThatNeedRerender=new Set,this.__slotsProvidedByUserOnFirstConnected=new Set,this.__privateSlots=new Set}connectedCallback(){super.connectedCallback(),this._connectSlotMixin()}__rerenderSlot(t){const i=this.slots[t]();this.__renderTemplateInScopedContext({renderAsDirectHostChild:i.renderAsDirectHostChild,template:i.template,slotName:t}),i.afterRender?.()}update(t){super.update(t);for(const i of this.__slotsThatNeedRerender)this.__rerenderSlot(i)}__renderTemplateInScopedContext({template:t,slotName:i,renderAsDirectHostChild:s}){if(!this.__renderMetaPerSlot.has(i)){const m=!!ShadowRoot.prototype.createElement;!!this.shadowRoot||console.error("[SlotMixin] No shadowRoot was found");const g=(m?this.shadowRoot:document).createElement("div"),y=document.createComment(`_start_slot_${i}_`),E=document.createComment(`_end_slot_${i}_`);g.appendChild(y),g.appendChild(E);const{creationScope:C,host:A}=this.renderOptions;if(Vi(t,g,{renderBefore:E,creationScope:C,host:A}),s){const S=Array.from(g.childNodes);this.__appendNodes({nodes:S,renderParent:this,slotName:i})}else g.slot=i,this.appendChild(g);this.__renderMetaPerSlot.set(i,{renderTargetThatRespectsShadowRootScoping:g,renderBefore:E});return}const{renderBefore:r,renderTargetThatRespectsShadowRootScoping:a}=this.__renderMetaPerSlot.get(i),l=s?this:a,{creationScope:d,host:c}=this.renderOptions;Vi(t,l,{creationScope:d,host:c,renderBefore:r}),s&&r.previousElementSibling&&!r.previousElementSibling.slot&&(r.previousElementSibling.slot=i)}__appendNodes({nodes:t,renderParent:i=this,slotName:s}){for(const o of t)o instanceof Element&&s&&s!==""&&o.setAttribute("slot",s),i.appendChild(o)}__initSlots(t){for(const i of t){if(this.__slotsProvidedByUserOnFirstConnected.has(i))continue;const s=this.slots[i]();if(s===void 0)continue;switch(this.__isConnectedSlotMixin||this.__privateSlots.add(i),Qd(s)){case"template-result":this.__renderTemplateInScopedContext({template:s,renderAsDirectHostChild:!0,slotName:i});break;case"node":this.__appendNodes({nodes:[s],renderParent:this,slotName:i});break;case"slot-rerender-object":this.__slotsThatNeedRerender.add(i),s.firstRenderOnConnected&&this.__rerenderSlot(i);break;default:throw new Error(`Slot "${i}" configured inside "get slots()" (in prototype) of ${this.constructor.name} may return these types: TemplateResult | Node | {template:TemplateResult, afterRender?:function} | undefined.
              You provided: ${s}`)}}}_connectSlotMixin(){if(this.__isConnectedSlotMixin)return;const t=Object.keys(this.slots);for(const i of t)(i===""?Array.from(this.children).find(o=>!o.hasAttribute("slot")):Array.from(this.children).find(o=>o.slot===i))&&this.__slotsProvidedByUserOnFirstConnected.add(i);this.__initSlots(t),this.__isConnectedSlotMixin=!0}_isPrivateSlot(t){return this.__privateSlots.has(t)}},pn=ie(eu);function mi(n="google-chrome"){const e=globalThis.navigator,t=!!e.userAgentData&&e.userAgentData.brands.some(l=>l.brand==="Chromium");if(n==="chromium")return t;const s=globalThis.navigator?.vendor,o=typeof globalThis.opr<"u",r=globalThis.userAgent?.indexOf("Edge")>-1,a=globalThis.userAgent?.match("CriOS");if(n==="ios")return a;if(n==="google-chrome")return t!==null&&typeof t<"u"&&s==="Google Inc."&&o===!1&&r===!1}const ji={isChrome:mi(),isIOSChrome:mi("ios"),isChromium:mi("chromium"),isFirefox:globalThis.navigator?.userAgent.toLowerCase().indexOf("firefox")>-1,isMac:globalThis.navigator?.appVersion?.indexOf("Mac")!==-1,isIOS:/iPhone|iPad|iPod/i.test(globalThis.navigator?.userAgent),isMacSafari:globalThis.navigator?.vendor&&globalThis.navigator?.vendor.indexOf("Apple")>-1&&globalThis.navigator?.userAgent&&globalThis.navigator?.userAgent.indexOf("CriOS")===-1&&globalThis.navigator?.userAgent.indexOf("FxiOS")===-1&&globalThis.navigator?.appVersion.indexOf("Mac")!==-1};function Qr(n=""){return`${n.length>0?`${n}-`:""}${Math.random().toString(36).substr(2,10)}`}const bi=n=>n.key===" "||n.key==="Enter",xo=n=>n.key===" ";class tu extends Jr(G){static get properties(){return{active:{type:Boolean,reflect:!0},type:{type:String,reflect:!0}}}render(){return x` <div class="button-content"><slot></slot></div> `}static get styles(){return[F`
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
      `]}constructor(){super(),this.type="button",this.active=!1,this.__setupEvents()}connectedCallback(){super.connectedCallback(),this.hasAttribute("role")||this.setAttribute("role","button")}updated(e){super.updated(e),e.has("disabled")&&(this.disabled?this.setAttribute("aria-disabled","true"):this.getAttribute("aria-disabled")!==null&&this.removeAttribute("aria-disabled"))}__setupEvents(){this.addEventListener("mousedown",this.__mousedownHandler),this.addEventListener("keydown",this.__keydownHandler),this.addEventListener("keyup",this.__keyupHandler)}__mousedownHandler(){this.active=!0;const e=()=>{this.active=!1,document.removeEventListener("mouseup",e),this.removeEventListener("mouseup",e)};document.addEventListener("mouseup",e),this.addEventListener("mouseup",e)}__keydownHandler(e){if(this.active||!bi(e)){xo(e)&&e.preventDefault();return}xo(e)&&e.preventDefault(),this.active=!0;const t=i=>{bi(i)&&(this.active=!1,document.removeEventListener("keyup",t,!0))};document.addEventListener("keyup",t,!0)}__keyupHandler(e){if(bi(e)){if(e.target&&e.target!==this)return;this.click()}}}class nu extends tu{constructor(){super(),this.type="reset",this.__setupDelegationInConstructor(),this.__submitAndResetHelperButton=document.createElement("button"),this.__preventEventLeakage=this.__preventEventLeakage.bind(this)}connectedCallback(){super.connectedCallback(),this.updateComplete.then(()=>{this._setupSubmitAndResetHelperOnConnected()})}disconnectedCallback(){super.disconnectedCallback(),this._teardownSubmitAndResetHelperOnDisconnected()}__preventEventLeakage(e){e.target===this.__submitAndResetHelperButton&&e.stopImmediatePropagation()}_setupSubmitAndResetHelperOnConnected(){this.appendChild(this.__submitAndResetHelperButton),this._form=this.__submitAndResetHelperButton.form,this.removeChild(this.__submitAndResetHelperButton),this._form&&this._form.addEventListener("click",this.__preventEventLeakage)}_teardownSubmitAndResetHelperOnDisconnected(){this._form&&this._form.removeEventListener("click",this.__preventEventLeakage)}async __clickDelegationHandler(e){this._form||await this.updateComplete,(this.type==="submit"||this.type==="reset")&&e.target===this&&this._form&&(this.__submitAndResetHelperButton.type=this.type,this._form.appendChild(this.__submitAndResetHelperButton),this.__submitAndResetHelperButton.click(),this._form.removeChild(this.__submitAndResetHelperButton))}__setupDelegationInConstructor(){this.addEventListener("click",this.__clickDelegationHandler,!0)}}const je=new WeakMap;function iu(){const n=document.createElement("button");return n.tabIndex=-1,n.type="submit",n.setAttribute("aria-hidden","true"),n.style.cssText=`
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
  `,n}class su extends nu{get _nativeButtonNode(){return je.get(this._form)?.helper||null}constructor(){super(),this.type="submit",this.__implicitSubmitHelperButton=null}_setupSubmitAndResetHelperOnConnected(){if(super._setupSubmitAndResetHelperOnConnected(),!this._form||this.type!=="submit")return;const e=this._form;if(!je.get(this._form)){const i=iu(),s=document.createElement("div");s.appendChild(i),je.set(this._form,{lionButtons:new Set,helper:i,observer:new MutationObserver(()=>{e.appendChild(s)})}),e.appendChild(s),je.get(e)?.observer.observe(s,{childList:!0})}je.get(e)?.lionButtons.add(this)}_teardownSubmitAndResetHelperOnDisconnected(){if(super._teardownSubmitAndResetHelperOnDisconnected(),this._form){const e=je.get(this._form);e&&(e.lionButtons.delete(this),e.lionButtons.size||(this._form.contains(e.helper)&&e.helper.remove(),je.get(this._form)?.observer.disconnect(),je.delete(this._form)))}}}var ou=F`
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
    padding-inline: var(--c-button-spacing-inline, var(--c-spacing-lg));
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
`,zt=class extends su{constructor(...e){super(...e),this.appearance="accent",this.variant="default",this.size="medium",this.loading=!1}static get styles(){return[...super.styles,ou]}render(){return x`
      <div class="button-content" part="content">
        <slot name="prefix" class="prefix" part="prefix"></slot>
        <slot class="label" part="label"></slot>
        <slot name="suffix" class="suffix" part="suffix"></slot>
      </div>
      ${this.loading?x`<craft-spinner part="spinner"></craft-spinner>`:B}
    `}};D([w({reflect:!0})],zt.prototype,"appearance",void 0),D([w({reflect:!0})],zt.prototype,"variant",void 0),D([w({reflect:!0})],zt.prototype,"size",void 0),D([w({reflect:!0,type:Boolean})],zt.prototype,"loading",void 0),customElements.get("craft-button")||customElements.define("craft-button",zt);var ru=F`
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
`,vn=class extends G{constructor(...e){super(...e),this.label=null,this._gradientId=null}connectedCallback(){super.connectedCallback(),this._gradientId=`avatar-gradient-${Math.random().toString(36).slice(2,8)}`}text(){return this.label?this.label.split(" ").map(e=>e.charAt(0).toUpperCase()).join(""):"?"}render(){return x`
      <span class="avatar">
        <svg
          viewBox="0 0 100 100"
          xmlns="http://www.w3.org/2000/svg"
          role="img"
        >
          ${this.label?x`<title>${this.label}</title>`:""}
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
    `}};vn.styles=[ru],D([w()],vn.prototype,"label",void 0),D([be()],vn.prototype,"_gradientId",void 0),customElements.get("craft-avatar")||customElements.define("craft-avatar",vn);const ea=F`
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
`,ta=F`
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
`,Ts=F`
  ${ta}

  ::slotted([slot='input']) {
    ${ea}
  }
`;var au=F`
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
`;const Wi=window,ko=new WeakMap;function lu(n){Wi.applyFocusVisiblePolyfill&&!ko.has(n)&&(Wi.applyFocusVisiblePolyfill(n),ko.set(n,void 0))}const cu=n=>class extends n{static get properties(){return{focused:{type:Boolean,reflect:!0},focusedVisible:{type:Boolean,reflect:!0,attribute:"focused-visible"},autofocus:{type:Boolean,reflect:!0}}}constructor(){super(),this.focused=!1,this.focusedVisible=!1,this.autofocus=!1}firstUpdated(t){super.firstUpdated(t),this.__registerEventsForFocusMixin(),this.__syncAutofocusToFocusableElement()}disconnectedCallback(){super.disconnectedCallback(),this.__teardownEventsForFocusMixin()}updated(t){super.updated(t),t.has("autofocus")&&this.__syncAutofocusToFocusableElement()}__syncAutofocusToFocusableElement(){this._focusableNode&&(this.hasAttribute("autofocus")?this._focusableNode.setAttribute("autofocus",""):this._focusableNode.removeAttribute("autofocus"))}focus(){this._focusableNode?.focus()}blur(){this._focusableNode?.blur()}get _focusableNode(){return this._inputNode||document.createElement("input")}__onFocus(){if(this.focused=!0,typeof Wi.applyFocusVisiblePolyfill=="function")this.focusedVisible=this._focusableNode.hasAttribute("data-focus-visible-added");else try{this.focusedVisible=this._focusableNode.matches(":focus-visible")}catch{this.focusedVisible=!1}}__onBlur(){this.focused=!1,this.focusedVisible=!1}__registerEventsForFocusMixin(){lu(this.getRootNode()),this.__redispatchFocus=t=>{t.stopPropagation(),this.dispatchEvent(new Event("focus"))},this._focusableNode.addEventListener("focus",this.__redispatchFocus),this.__redispatchBlur=t=>{t.stopPropagation(),this.dispatchEvent(new Event("blur"))},this._focusableNode.addEventListener("blur",this.__redispatchBlur),this.__redispatchFocusin=t=>{t.stopPropagation(),this.__onFocus(),this.dispatchEvent(new Event("focusin",{bubbles:!0,composed:!0}))},this._focusableNode.addEventListener("focusin",this.__redispatchFocusin),this.__redispatchFocusout=t=>{t.stopPropagation(),this.__onBlur(),this.dispatchEvent(new Event("focusout",{bubbles:!0,composed:!0}))},this._focusableNode.addEventListener("focusout",this.__redispatchFocusout)}__teardownEventsForFocusMixin(){this._focusableNode&&(this._focusableNode?.removeEventListener("focus",this.__redispatchFocus),this._focusableNode?.removeEventListener("blur",this.__redispatchBlur),this._focusableNode?.removeEventListener("focusin",this.__redispatchFocusin),this._focusableNode?.removeEventListener("focusout",this.__redispatchFocusout))}},na=ie(cu);function ia(n,e){return e={exports:{}},n(e,e.exports),e.exports}var nt="long",We="short",gi="narrow",q="numeric",Ke="2-digit",Ge={number:{decimal:{style:"decimal"},integer:{style:"decimal",maximumFractionDigits:0},currency:{style:"currency",currency:"USD"},percent:{style:"percent"},default:{style:"decimal"}},date:{short:{month:q,day:q,year:Ke},medium:{month:We,day:q,year:q},long:{month:nt,day:q,year:q},full:{month:nt,day:q,year:q,weekday:nt},default:{month:We,day:q,year:q}},time:{short:{hour:q,minute:q},medium:{hour:q,minute:q,second:q},long:{hour:q,minute:q,second:q,timeZoneName:We},full:{hour:q,minute:q,second:q,timeZoneName:We},default:{hour:q,minute:q,second:q}},duration:{default:{hours:{minimumIntegerDigits:1,maximumFractionDigits:0},minutes:{minimumIntegerDigits:2,maximumFractionDigits:0},seconds:{minimumIntegerDigits:2,maximumFractionDigits:3}}},parseNumberPattern:function(n){if(n){var e={},t=n.match(/\b[A-Z]{3}\b/i),i=n.replace(/[^¤]/g,"").length;if(!i&&t&&(i=1),i?(e.style="currency",e.currencyDisplay=i===1?"symbol":i===2?"code":"name",e.currency=t?t[0].toUpperCase():"USD"):n.indexOf("%")>=0&&(e.style="percent"),!/[@#0]/.test(n))return e.style?e:void 0;if(e.useGrouping=n.indexOf(",")>=0,/E\+?[@#0]+/i.test(n)||n.indexOf("@")>=0){var s=n.replace(/E\+?[@#0]+|[^@#0]/gi,"");e.minimumSignificantDigits=Math.min(Math.max(s.replace(/[^@0]/g,"").length,1),21),e.maximumSignificantDigits=Math.min(Math.max(s.length,1),21)}else{for(var o=n.replace(/[^#0.]/g,"").split("."),r=o[0],a=r.length-1;r[a]==="0";)--a;e.minimumIntegerDigits=Math.min(Math.max(r.length-1-a,1),21);var l=o[1]||"";for(a=0;l[a]==="0";)++a;for(e.minimumFractionDigits=Math.min(Math.max(a,0),20);l[a]==="#";)++a;e.maximumFractionDigits=Math.min(Math.max(a,0),20)}return e}},parseDatePattern:function(n){if(n){for(var e={},t=0;t<n.length;){for(var i=n[t],s=1;n[++t]===i;)++s;switch(i){case"G":e.era=s===5?gi:s===4?nt:We;break;case"y":case"Y":e.year=s===2?Ke:q;break;case"M":case"L":s=Math.min(Math.max(s-1,0),4),e.month=[q,Ke,We,nt,gi][s];break;case"E":case"e":case"c":e.weekday=s===5?gi:s===4?nt:We;break;case"d":case"D":e.day=s===2?Ke:q;break;case"h":case"K":e.hour12=!0,e.hour=s===2?Ke:q;break;case"H":case"k":e.hour12=!1,e.hour=s===2?Ke:q;break;case"m":e.minute=s===2?Ke:q;break;case"s":case"S":e.second=s===2?Ke:q;break;case"z":case"Z":case"v":case"V":e.timeZoneName=s===1?We:nt;break}}return Object.keys(e).length?e:void 0}}},du=function(e,t){if(typeof e=="string"&&t[e])return e;for(var i=[].concat(e||[]),s=0,o=i.length;s<o;++s)for(var r=i[s].split("-");r.length;){var a=r.join("-");if(t[a])return a;r.pop()}},pt="zero",R="one",oe="two",K="few",ne="many",$="other",h=[function(n){var e=+n;return e===1?R:$},function(n){var e=+n;return 0<=e&&e<=1?R:$},function(n){var e=Math.floor(Math.abs(+n)),t=+n;return e===0||t===1?R:$},function(n){var e=+n;return e===0?pt:e===1?R:e===2?oe:3<=e%100&&e%100<=10?K:11<=e%100&&e%100<=99?ne:$},function(n){var e=Math.floor(Math.abs(+n)),t=(n+".").split(".")[1].length;return e===1&&t===0?R:$},function(n){var e=+n;return e%10===1&&e%100!==11?R:2<=e%10&&e%10<=4&&(e%100<12||14<e%100)?K:e%10===0||5<=e%10&&e%10<=9||11<=e%100&&e%100<=14?ne:$},function(n){var e=+n;return e%10===1&&e%100!==11&&e%100!==71&&e%100!==91?R:e%10===2&&e%100!==12&&e%100!==72&&e%100!==92?oe:(3<=e%10&&e%10<=4||e%10===9)&&(e%100<10||19<e%100)&&(e%100<70||79<e%100)&&(e%100<90||99<e%100)?K:e!==0&&e%1e6===0?ne:$},function(n){var e=Math.floor(Math.abs(+n)),t=(n+".").split(".")[1].length,i=+(n+".").split(".")[1];return t===0&&e%10===1&&e%100!==11||i%10===1&&i%100!==11?R:t===0&&2<=e%10&&e%10<=4&&(e%100<12||14<e%100)||2<=i%10&&i%10<=4&&(i%100<12||14<i%100)?K:$},function(n){var e=Math.floor(Math.abs(+n)),t=(n+".").split(".")[1].length;return e===1&&t===0?R:2<=e&&e<=4&&t===0?K:t!==0?ne:$},function(n){var e=+n;return e===0?pt:e===1?R:e===2?oe:e===3?K:e===6?ne:$},function(n){var e=Math.floor(Math.abs(+n)),t=+(""+n).replace(/^[^.]*.?|0+$/g,""),i=+n;return i===1||t!==0&&(e===0||e===1)?R:$},function(n){var e=Math.floor(Math.abs(+n)),t=(n+".").split(".")[1].length,i=+(n+".").split(".")[1];return t===0&&e%100===1||i%100===1?R:t===0&&e%100===2||i%100===2?oe:t===0&&3<=e%100&&e%100<=4||3<=i%100&&i%100<=4?K:$},function(n){var e=Math.floor(Math.abs(+n));return e===0||e===1?R:$},function(n){var e=Math.floor(Math.abs(+n)),t=(n+".").split(".")[1].length,i=+(n+".").split(".")[1];return t===0&&(e===1||e===2||e===3)||t===0&&e%10!==4&&e%10!==6&&e%10!==9||t!==0&&i%10!==4&&i%10!==6&&i%10!==9?R:$},function(n){var e=+n;return e===1?R:e===2?oe:3<=e&&e<=6?K:7<=e&&e<=10?ne:$},function(n){var e=+n;return e===1||e===11?R:e===2||e===12?oe:3<=e&&e<=10||13<=e&&e<=19?K:$},function(n){var e=Math.floor(Math.abs(+n)),t=(n+".").split(".")[1].length;return t===0&&e%10===1?R:t===0&&e%10===2?oe:t===0&&(e%100===0||e%100===20||e%100===40||e%100===60||e%100===80)?K:t!==0?ne:$},function(n){var e=Math.floor(Math.abs(+n)),t=(n+".").split(".")[1].length,i=+n;return e===1&&t===0?R:e===2&&t===0?oe:t===0&&(i<0||10<i)&&i%10===0?ne:$},function(n){var e=Math.floor(Math.abs(+n)),t=+(""+n).replace(/^[^.]*.?|0+$/g,"");return t===0&&e%10===1&&e%100!==11||t!==0?R:$},function(n){var e=+n;return e===1?R:e===2?oe:$},function(n){var e=+n;return e===0?pt:e===1?R:$},function(n){var e=Math.floor(Math.abs(+n)),t=+n;return t===0?pt:(e===0||e===1)&&t!==0?R:$},function(n){var e=+(n+".").split(".")[1],t=+n;return t%10===1&&(t%100<11||19<t%100)?R:2<=t%10&&t%10<=9&&(t%100<11||19<t%100)?K:e!==0?ne:$},function(n){var e=(n+".").split(".")[1].length,t=+(n+".").split(".")[1],i=+n;return i%10===0||11<=i%100&&i%100<=19||e===2&&11<=t%100&&t%100<=19?pt:i%10===1&&i%100!==11||e===2&&t%10===1&&t%100!==11||e!==2&&t%10===1?R:$},function(n){var e=Math.floor(Math.abs(+n)),t=(n+".").split(".")[1].length,i=+(n+".").split(".")[1];return t===0&&e%10===1&&e%100!==11||i%10===1&&i%100!==11?R:$},function(n){var e=Math.floor(Math.abs(+n)),t=(n+".").split(".")[1].length,i=+n;return e===1&&t===0?R:t!==0||i===0||i!==1&&1<=i%100&&i%100<=19?K:$},function(n){var e=+n;return e===1?R:e===0||2<=e%100&&e%100<=10?K:11<=e%100&&e%100<=19?ne:$},function(n){var e=Math.floor(Math.abs(+n)),t=(n+".").split(".")[1].length;return e===1&&t===0?R:t===0&&2<=e%10&&e%10<=4&&(e%100<12||14<e%100)?K:t===0&&e!==1&&0<=e%10&&e%10<=1||t===0&&5<=e%10&&e%10<=9||t===0&&12<=e%100&&e%100<=14?ne:$},function(n){var e=Math.floor(Math.abs(+n));return 0<=e&&e<=1?R:$},function(n){var e=Math.floor(Math.abs(+n)),t=(n+".").split(".")[1].length;return t===0&&e%10===1&&e%100!==11?R:t===0&&2<=e%10&&e%10<=4&&(e%100<12||14<e%100)?K:t===0&&e%10===0||t===0&&5<=e%10&&e%10<=9||t===0&&11<=e%100&&e%100<=14?ne:$},function(n){var e=Math.floor(Math.abs(+n)),t=+n;return e===0||t===1?R:2<=t&&t<=10?K:$},function(n){var e=Math.floor(Math.abs(+n)),t=+(n+".").split(".")[1],i=+n;return i===0||i===1||e===0&&t===1?R:$},function(n){var e=Math.floor(Math.abs(+n)),t=(n+".").split(".")[1].length;return t===0&&e%100===1?R:t===0&&e%100===2?oe:t===0&&3<=e%100&&e%100<=4||t!==0?K:$},function(n){var e=+n;return 0<=e&&e<=1||11<=e&&e<=99?R:$},function(n){var e=+n;return e===1||e===5||e===7||e===8||e===9||e===10?R:e===2||e===3?oe:e===4?K:e===6?ne:$},function(n){var e=Math.floor(Math.abs(+n));return e%10===1||e%10===2||e%10===5||e%10===7||e%10===8||e%100===20||e%100===50||e%100===70||e%100===80?R:e%10===3||e%10===4||e%1e3===100||e%1e3===200||e%1e3===300||e%1e3===400||e%1e3===500||e%1e3===600||e%1e3===700||e%1e3===800||e%1e3===900?K:e===0||e%10===6||e%100===40||e%100===60||e%100===90?ne:$},function(n){var e=+n;return(e%10===2||e%10===3)&&e%100!==12&&e%100!==13?K:$},function(n){var e=+n;return e===1||e===3?R:e===2?oe:e===4?K:$},function(n){var e=+n;return e===0||e===7||e===8||e===9?pt:e===1?R:e===2?oe:e===3||e===4?K:e===5||e===6?ne:$},function(n){var e=+n;return e%10===1&&e%100!==11?R:e%10===2&&e%100!==12?oe:e%10===3&&e%100!==13?K:$},function(n){var e=+n;return e===1||e===11?R:e===2||e===12?oe:e===3||e===13?K:$},function(n){var e=+n;return e===1?R:e===2||e===3?oe:e===4?K:e===6?ne:$},function(n){var e=+n;return e===1||e===5?R:$},function(n){var e=+n;return e===11||e===8||e===80||e===800?ne:$},function(n){var e=Math.floor(Math.abs(+n));return e===1?R:e===0||2<=e%100&&e%100<=20||e%100===40||e%100===60||e%100===80?ne:$},function(n){var e=+n;return e%10===6||e%10===9||e%10===0&&e!==0?ne:$},function(n){var e=Math.floor(Math.abs(+n));return e%10===1&&e%100!==11?R:e%10===2&&e%100!==12?oe:(e%10===7||e%10===8)&&e%100!==17&&e%100!==18?ne:$},function(n){var e=+n;return e===1?R:e===2||e===3?oe:e===4?K:$},function(n){var e=+n;return 1<=e&&e<=4?R:$},function(n){var e=+n;return e===1||e===5||7<=e&&e<=9?R:e===2||e===3?oe:e===4?K:e===6?ne:$},function(n){var e=+n;return e===1?R:e%10===4&&e%100!==14?ne:$},function(n){var e=+n;return(e%10===1||e%10===2)&&e%100!==11&&e%100!==12?R:$},function(n){var e=+n;return e%10===6||e%10===9||e===10?K:$},function(n){var e=+n;return e%10===3&&e%100!==13?K:$}],Ki={af:{cardinal:h[0]},ak:{cardinal:h[1]},am:{cardinal:h[2]},ar:{cardinal:h[3]},ars:{cardinal:h[3]},as:{cardinal:h[2],ordinal:h[34]},asa:{cardinal:h[0]},ast:{cardinal:h[4]},az:{cardinal:h[0],ordinal:h[35]},be:{cardinal:h[5],ordinal:h[36]},bem:{cardinal:h[0]},bez:{cardinal:h[0]},bg:{cardinal:h[0]},bh:{cardinal:h[1]},bn:{cardinal:h[2],ordinal:h[34]},br:{cardinal:h[6]},brx:{cardinal:h[0]},bs:{cardinal:h[7]},ca:{cardinal:h[4],ordinal:h[37]},ce:{cardinal:h[0]},cgg:{cardinal:h[0]},chr:{cardinal:h[0]},ckb:{cardinal:h[0]},cs:{cardinal:h[8]},cy:{cardinal:h[9],ordinal:h[38]},da:{cardinal:h[10]},de:{cardinal:h[4]},dsb:{cardinal:h[11]},dv:{cardinal:h[0]},ee:{cardinal:h[0]},el:{cardinal:h[0]},en:{cardinal:h[4],ordinal:h[39]},eo:{cardinal:h[0]},es:{cardinal:h[0]},et:{cardinal:h[4]},eu:{cardinal:h[0]},fa:{cardinal:h[2]},ff:{cardinal:h[12]},fi:{cardinal:h[4]},fil:{cardinal:h[13],ordinal:h[0]},fo:{cardinal:h[0]},fr:{cardinal:h[12],ordinal:h[0]},fur:{cardinal:h[0]},fy:{cardinal:h[4]},ga:{cardinal:h[14],ordinal:h[0]},gd:{cardinal:h[15],ordinal:h[40]},gl:{cardinal:h[4]},gsw:{cardinal:h[0]},gu:{cardinal:h[2],ordinal:h[41]},guw:{cardinal:h[1]},gv:{cardinal:h[16]},ha:{cardinal:h[0]},haw:{cardinal:h[0]},he:{cardinal:h[17]},hi:{cardinal:h[2],ordinal:h[41]},hr:{cardinal:h[7]},hsb:{cardinal:h[11]},hu:{cardinal:h[0],ordinal:h[42]},hy:{cardinal:h[12],ordinal:h[0]},ia:{cardinal:h[4]},io:{cardinal:h[4]},is:{cardinal:h[18]},it:{cardinal:h[4],ordinal:h[43]},iu:{cardinal:h[19]},iw:{cardinal:h[17]},jgo:{cardinal:h[0]},ji:{cardinal:h[4]},jmc:{cardinal:h[0]},ka:{cardinal:h[0],ordinal:h[44]},kab:{cardinal:h[12]},kaj:{cardinal:h[0]},kcg:{cardinal:h[0]},kk:{cardinal:h[0],ordinal:h[45]},kkj:{cardinal:h[0]},kl:{cardinal:h[0]},kn:{cardinal:h[2]},ks:{cardinal:h[0]},ksb:{cardinal:h[0]},ksh:{cardinal:h[20]},ku:{cardinal:h[0]},kw:{cardinal:h[19]},ky:{cardinal:h[0]},lag:{cardinal:h[21]},lb:{cardinal:h[0]},lg:{cardinal:h[0]},ln:{cardinal:h[1]},lt:{cardinal:h[22]},lv:{cardinal:h[23]},mas:{cardinal:h[0]},mg:{cardinal:h[1]},mgo:{cardinal:h[0]},mk:{cardinal:h[24],ordinal:h[46]},ml:{cardinal:h[0]},mn:{cardinal:h[0]},mo:{cardinal:h[25],ordinal:h[0]},mr:{cardinal:h[2],ordinal:h[47]},mt:{cardinal:h[26]},nah:{cardinal:h[0]},naq:{cardinal:h[19]},nb:{cardinal:h[0]},nd:{cardinal:h[0]},ne:{cardinal:h[0],ordinal:h[48]},nl:{cardinal:h[4]},nn:{cardinal:h[0]},nnh:{cardinal:h[0]},no:{cardinal:h[0]},nr:{cardinal:h[0]},nso:{cardinal:h[1]},ny:{cardinal:h[0]},nyn:{cardinal:h[0]},om:{cardinal:h[0]},or:{cardinal:h[0],ordinal:h[49]},os:{cardinal:h[0]},pa:{cardinal:h[1]},pap:{cardinal:h[0]},pl:{cardinal:h[27]},prg:{cardinal:h[23]},ps:{cardinal:h[0]},pt:{cardinal:h[28]},"pt-PT":{cardinal:h[4]},rm:{cardinal:h[0]},ro:{cardinal:h[25],ordinal:h[0]},rof:{cardinal:h[0]},ru:{cardinal:h[29]},rwk:{cardinal:h[0]},saq:{cardinal:h[0]},sc:{cardinal:h[4],ordinal:h[43]},scn:{cardinal:h[4],ordinal:h[43]},sd:{cardinal:h[0]},sdh:{cardinal:h[0]},se:{cardinal:h[19]},seh:{cardinal:h[0]},sh:{cardinal:h[7]},shi:{cardinal:h[30]},si:{cardinal:h[31]},sk:{cardinal:h[8]},sl:{cardinal:h[32]},sma:{cardinal:h[19]},smi:{cardinal:h[19]},smj:{cardinal:h[19]},smn:{cardinal:h[19]},sms:{cardinal:h[19]},sn:{cardinal:h[0]},so:{cardinal:h[0]},sq:{cardinal:h[0],ordinal:h[50]},sr:{cardinal:h[7]},ss:{cardinal:h[0]},ssy:{cardinal:h[0]},st:{cardinal:h[0]},sv:{cardinal:h[4],ordinal:h[51]},sw:{cardinal:h[4]},syr:{cardinal:h[0]},ta:{cardinal:h[0]},te:{cardinal:h[0]},teo:{cardinal:h[0]},ti:{cardinal:h[1]},tig:{cardinal:h[0]},tk:{cardinal:h[0],ordinal:h[52]},tl:{cardinal:h[13],ordinal:h[0]},tn:{cardinal:h[0]},tr:{cardinal:h[0]},ts:{cardinal:h[0]},tzm:{cardinal:h[33]},ug:{cardinal:h[0]},uk:{cardinal:h[29],ordinal:h[53]},ur:{cardinal:h[4]},uz:{cardinal:h[0]},ve:{cardinal:h[0]},vo:{cardinal:h[0]},vun:{cardinal:h[0]},wa:{cardinal:h[1]},wae:{cardinal:h[0]},xh:{cardinal:h[0]},xog:{cardinal:h[0]},yi:{cardinal:h[4]},zu:{cardinal:h[2]},lo:{ordinal:h[0]},ms:{ordinal:h[0]},vi:{ordinal:h[0]}},ii=ia(function(n,e){e=n.exports=function(b,f,g){return t(b,null,f||"en",g||{},!0)},e.toParts=function(b,f,g){return t(b,null,f||"en",g||{},!1)};function t(m,b,f,g,y){var E=m.map(function(C){return i(C,b,f,g,y)});return y?E.length===1?E[0]:function(A){for(var S="",L=0;L<E.length;++L)S+=E[L](A);return S}:function(A){return E.reduce(function(S,L){return S.concat(L(A))},[])}}function i(m,b,f,g,y){if(typeof m=="string"){var E=m;return function(){return E}}var C=m[0],A=m[1];if(b&&m[0]==="#"){C=b[0];var S=b[2],L=(g.number||p.number)([C,"number"],f);return function(I){return L(s(C,I)-S,I)}}var U;A==="plural"||A==="selectordinal"?(U={},Object.keys(m[3]).forEach(function(X){U[X]=t(m[3][X],m,f,g,y)}),m=[m[0],m[1],m[2],U]):m[2]&&typeof m[2]=="object"&&(U={},Object.keys(m[2]).forEach(function(X){U[X]=t(m[2][X],m,f,g,y)}),m=[m[0],m[1],U]);var P=A&&(g[A]||p[A]);if(P){var Z=P(m,f);return function(I){return Z(s(C,I),I)}}return y?function(I){return String(s(C,I))}:function(I){return s(C,I)}}function s(m,b){if(b&&m in b)return b[m];for(var f=m.split("."),g=b,y=0,E=f.length;g&&y<E;++y)g=g[f[y]];return g}function o(m,b){var f=m[2],g=Ge.number[f]||Ge.parseNumberPattern(f)||Ge.number.default;return new Intl.NumberFormat(b,g).format}function r(m,b){var f=m[2],g=Ge.duration[f]||Ge.duration.default,y=new Intl.NumberFormat(b,g.seconds).format,E=new Intl.NumberFormat(b,g.minutes).format,C=new Intl.NumberFormat(b,g.hours).format,A=/^fi$|^fi-|^da/.test(String(b))?".":":";return function(S,L){if(S=+S,!isFinite(S))return y(S);var U=~~(S/60/60),P=~~(S/60%60),Z=(U?C(Math.abs(U))+A:"")+E(Math.abs(P))+A+y(Math.abs(S%60));return S<0?C(-1).replace(C(1),Z):Z}}function a(m,b){var f=m[1],g=m[2],y=Ge[f][g]||Ge.parseDatePattern(g)||Ge[f].default;return new Intl.DateTimeFormat(b,y).format}function l(m,b){var f=m[1],g=f==="selectordinal"?"ordinal":"cardinal",y=m[2],E=m[3],C;if(Intl.PluralRules&&Intl.PluralRules.supportedLocalesOf(b).length>0)C=new Intl.PluralRules(b,{type:g});else{var A=du(b,Ki),S=A&&Ki[A][g]||d;C={select:S}}return function(L,U){var P=E["="+ +L]||E[C.select(L-y)]||E.other;return P(U)}}function d(){return"other"}function c(m,b){var f=m[2];return function(g,y){var E=f[g]||f.other;return E(y)}}var p={number:o,ordinal:o,spellout:o,duration:r,date:a,time:a,plural:l,selectordinal:l,select:c};e.types=p});ii.toParts;ii.types;var sa=ia(function(n,e){var t="{",i="}",s=",",o="#",r="<",a=">",l="</",d="/>",c="'",p="offset:",m=["number","date","time","ordinal","duration","spellout"],b=["plural","select","selectordinal"];e=n.exports=function(k,N){return f({pattern:String(k),index:0,tagsType:N&&N.tagsType||null,tokens:N&&N.tokens||null},"")};function f(u,k){var N=u.pattern,O=N.length,M=[],T=u.index,j=g(u,k);for(j&&M.push(j),j&&u.tokens&&u.tokens.push(["text",N.slice(T,u.index)]);u.index<O;){if(N[u.index]===i){if(!k)throw I(u);break}if(k&&u.tagsType&&N.slice(u.index,u.index+l.length)===l)break;M.push(C(u)),T=u.index,j=g(u,k),j&&M.push(j),j&&u.tokens&&u.tokens.push(["text",N.slice(T,u.index)])}return M}function g(u,k){for(var N=u.pattern,O=N.length,M=k==="plural"||k==="selectordinal",T=!!u.tagsType,j=k==="{style}",pe="";u.index<O;){var H=N[u.index];if(H===t||H===i||M&&H===o||T&&H===r||j&&y(H.charCodeAt(0)))break;if(H===c)if(H=N[++u.index],H===c)pe+=H,++u.index;else if(H===t||H===i||M&&H===o||T&&H===r||j)for(pe+=H;++u.index<O;)if(H=N[u.index],H===c&&N[u.index+1]===c)pe+=c,++u.index;else if(H===c){++u.index;break}else pe+=H;else pe+=c;else pe+=H,++u.index}return pe}function y(u){return u>=9&&u<=13||u===32||u===133||u===160||u===6158||u>=8192&&u<=8205||u===8232||u===8233||u===8239||u===8287||u===8288||u===12288||u===65279}function E(u){for(var k=u.pattern,N=k.length,O=u.index;u.index<N&&y(k.charCodeAt(u.index));)++u.index;O<u.index&&u.tokens&&u.tokens.push(["space",u.pattern.slice(O,u.index)])}function C(u){var k=u.pattern;if(k[u.index]===o)return u.tokens&&u.tokens.push(["syntax",o]),++u.index,[o];var N=A(u);if(N)return N;if(k[u.index]!==t)throw I(u,t);u.tokens&&u.tokens.push(["syntax",t]),++u.index,E(u);var O=S(u);if(!O)throw I(u,"placeholder id");u.tokens&&u.tokens.push(["id",O]),E(u);var M=k[u.index];if(M===i)return u.tokens&&u.tokens.push(["syntax",i]),++u.index,[O];if(M!==s)throw I(u,s+" or "+i);u.tokens&&u.tokens.push(["syntax",s]),++u.index,E(u);var T=S(u);if(!T)throw I(u,"placeholder type");if(u.tokens&&u.tokens.push(["type",T]),E(u),M=k[u.index],M===i){if(u.tokens&&u.tokens.push(["syntax",i]),T==="plural"||T==="selectordinal"||T==="select")throw I(u,T+" sub-messages");return++u.index,[O,T]}if(M!==s)throw I(u,s+" or "+i);u.tokens&&u.tokens.push(["syntax",s]),++u.index,E(u);var j;if(T==="plural"||T==="selectordinal"){var pe=U(u);E(u),j=[O,T,pe,Z(u,T)]}else if(T==="select")j=[O,T,Z(u,T)];else if(m.indexOf(T)>=0)j=[O,T,L(u)];else{var H=u.index,ze=L(u);E(u),k[u.index]===t&&(u.index=H,ze=Z(u,T)),j=[O,T,ze]}if(E(u),k[u.index]!==i)throw I(u,i);return u.tokens&&u.tokens.push(["syntax",i]),++u.index,j}function A(u){var k=u.tagsType;if(!(!k||u.pattern[u.index]!==r)){if(u.pattern.slice(u.index,u.index+l.length)===l)throw I(u,null,"closing tag without matching opening tag");u.tokens&&u.tokens.push(["syntax",r]),++u.index;var N=S(u,!0);if(!N)throw I(u,"placeholder id");if(u.tokens&&u.tokens.push(["id",N]),E(u),u.pattern.slice(u.index,u.index+d.length)===d)return u.tokens&&u.tokens.push(["syntax",d]),u.index+=d.length,[N,k];if(u.pattern[u.index]!==a)throw I(u,a);u.tokens&&u.tokens.push(["syntax",a]),++u.index;var O=f(u,k),M=u.index;if(u.pattern.slice(u.index,u.index+l.length)!==l)throw I(u,l+N+a);u.tokens&&u.tokens.push(["syntax",l]),u.index+=l.length;var T=S(u,!0);if(T&&u.tokens&&u.tokens.push(["id",T]),N!==T)throw u.index=M,I(u,l+N+a,l+T+a);if(E(u),u.pattern[u.index]!==a)throw I(u,a);return u.tokens&&u.tokens.push(["syntax",a]),++u.index,[N,k,{children:O}]}}function S(u,k){for(var N=u.pattern,O=N.length,M="";u.index<O;){var T=N[u.index];if(T===t||T===i||T===s||T===o||T===c||y(T.charCodeAt(0))||k&&(T===r||T===a||T==="/"))break;M+=T,++u.index}return M}function L(u){var k=u.index,N=g(u,"{style}");if(!N)throw I(u,"placeholder style name");return u.tokens&&u.tokens.push(["style",u.pattern.slice(k,u.index)]),N}function U(u){var k=u.pattern,N=k.length,O=0;if(k.slice(u.index,u.index+p.length)===p){u.tokens&&u.tokens.push(["offset","offset"],["syntax",":"]),u.index+=p.length,E(u);for(var M=u.index;u.index<N&&P(k.charCodeAt(u.index));)++u.index;if(M===u.index)throw I(u,"offset number");u.tokens&&u.tokens.push(["number",k.slice(M,u.index)]),O=+k.slice(M,u.index)}return O}function P(u){return u>=48&&u<=57}function Z(u,k){for(var N=u.pattern,O=N.length,M={};u.index<O&&N[u.index]!==i;){var T=S(u);if(!T)throw I(u,"sub-message selector");u.tokens&&u.tokens.push(["selector",T]),E(u),M[T]=X(u,k),E(u)}if(!M.other&&b.indexOf(k)>=0)throw I(u,null,null,'"other" sub-message must be specified in '+k);return M}function X(u,k){if(u.pattern[u.index]!==t)throw I(u,t+" to start sub-message");u.tokens&&u.tokens.push(["syntax",t]),++u.index;var N=f(u,k);if(u.pattern[u.index]!==i)throw I(u,i+" to end sub-message");return u.tokens&&u.tokens.push(["syntax",i]),++u.index,N}function I(u,k,N,O){var M=u.pattern,T=M.slice(0,u.index).split(/\r?\n/),j=u.index,pe=T.length,H=T.slice(-1)[0].length;return N=N||(u.index>=M.length?"end of message pattern":S(u)||M[u.index]),O||(O=ve(k,N)),O+=" in "+M.replace(/\r?\n/g,`
`),new se(O,k,N,j,pe,H)}function ve(u,k){return u?"Expected "+u+" but found "+k:"Unexpected "+k+" found"}function se(u,k,N,O,M,T){Error.call(this,u),this.name="SyntaxError",this.message=u,this.expected=k,this.found=N,this.offset=O,this.line=M,this.column=T}se.prototype=Object.create(Error.prototype),e.SyntaxError=se});sa.SyntaxError;var uu=new RegExp("^("+Object.keys(Ki).join("|")+")\\b"),Kt=new WeakMap;function St(n,e,t){if(!(this instanceof St)||Kt.has(this))throw new TypeError("calling MessageFormat constructor without new is invalid");var i=sa(n);Kt.set(this,{ast:i,format:ii(i,e,t&&t.types),locale:St.supportedLocalesOf(e)[0]||"en",locales:e,options:t})}var hu=St;Object.defineProperties(St.prototype,{format:{configurable:!0,get:function(){var e=Kt.get(this);if(!e)throw new TypeError("MessageFormat.prototype.format called on value that's not an object initialized as a MessageFormat");return e.format}},formatToParts:{configurable:!0,writable:!0,value:function(e){var t=Kt.get(this);if(!t)throw new TypeError("MessageFormat.prototype.formatToParts called on value that's not an object initialized as a MessageFormat");var i=t.toParts||(t.toParts=ii.toParts(t.ast,t.locales,t.options&&t.options.types));return i(e)}},resolvedOptions:{configurable:!0,writable:!0,value:function(){var e=Kt.get(this);if(!e)throw new TypeError("MessageFormat.prototype.resolvedOptions called on value that's not an object initialized as a MessageFormat");return{locale:e.locale}}}});typeof Symbol<"u"&&Object.defineProperty(St.prototype,Symbol.toStringTag,{value:"Object"});Object.defineProperties(St,{supportedLocalesOf:{configurable:!0,writable:!0,value:function(e){return[].concat(Intl.NumberFormat.supportedLocalesOf(e),Intl.DateTimeFormat.supportedLocalesOf(e),Intl.PluralRules?Intl.PluralRules.supportedLocalesOf(e):[],[].concat(e||[]).filter(function(t){return uu.test(t)})).filter(function(t,i,s){return s.indexOf(t)===i})}}});function pu(n){return!!(n&&n.default&&typeof n.default=="object"&&Object.keys(n).length===1)}const Ye=globalThis.document?.documentElement;class fu extends EventTarget{formatNumberOptions={returnIfNaN:"",postProcessors:new Map};formatDateOptions={postProcessors:new Map};#e=!1;#t="";#n=null;__storage={};__namespacePatternsMap=new Map;__namespaceLoadersCache={};__namespaceLoaderPromisesCache={};get locale(){return this.#e?this.#t||"":Ye.lang||""}set locale(e){if(this.#i(e),!this.#e){const s=Ye.lang;this._setHtmlLangAttribute(e),this._onLocaleChanged(e,s);return}const t=this.#t;this.#t=e,this.#n===null&&this._setHtmlLangAttribute(e),this._onLocaleChanged(e,t)}get loadingComplete(){return typeof this.__namespaceLoaderPromisesCache[this.locale]=="object"?Promise.all(Object.values(this.__namespaceLoaderPromisesCache[this.locale])):Promise.resolve()}constructor({allowOverridesForExistingNamespaces:e=!1,autoLoadOnLocaleChange:t=!1,showKeyAsFallback:i=!1,fallbackLocale:s=""}={}){super(),this.__allowOverridesForExistingNamespaces=e,this._autoLoadOnLocaleChange=!!t,this._showKeyAsFallback=i,this._fallbackLocale=s;const o=Ye.getAttribute("data-localize-lang");this.#e=!!o,this.#e&&(this.locale=o,this._setupTranslationToolSupport()),Ye.lang||(Ye.lang=this.locale||"en-GB"),this._setupHtmlLangAttributeObserver()}addData(e,t,i){if(!this.__allowOverridesForExistingNamespaces&&this._isNamespaceInCache(e,t))throw new Error(`Namespace "${t}" has been already added for the locale "${e}".`);this.__storage[e]=this.__storage[e]||{},this.__allowOverridesForExistingNamespaces?this.__storage[e][t]={...this.__storage[e][t],...i}:this.__storage[e][t]=i}setupNamespaceLoader(e,t){this.__namespacePatternsMap.set(e,t)}loadNamespaces(e,{locale:t}={}){return Promise.all(e.map(i=>this.loadNamespace(i,{locale:t})))}loadNamespace(e,{locale:t=this.locale}={locale:this.locale}){const i=typeof e=="object",s=i?Object.keys(e)[0]:e;if(this._isNamespaceInCache(t,s))return Promise.resolve();const o=this._getCachedNamespaceLoaderPromise(t,s);return o||this._loadNamespaceData(t,e,i,s)}msg(e,t,i={}){const s=i.locale?i.locale:this.locale,o=this._getMessageForKeys(e,s);return o?new hu(o,s).format(t):""}teardown(){this._teardownHtmlLangAttributeObserver()}reset(){this.__storage={},this.__namespacePatternsMap=new Map,this.__namespaceLoadersCache={},this.__namespaceLoaderPromisesCache={}}setDatePostProcessorForLocale({locale:e,postProcessor:t}){this.formatDateOptions?.postProcessors.set(e,t)}setNumberPostProcessorForLocale({locale:e,postProcessor:t}){this.formatNumberOptions?.postProcessors.set(e,t)}_setupTranslationToolSupport(){this.#n=Ye.lang||null}_setHtmlLangAttribute(e){this._teardownHtmlLangAttributeObserver(),Ye.lang=e,this._setupHtmlLangAttributeObserver()}_setupHtmlLangAttributeObserver(){this._htmlLangAttributeObserver||(this._htmlLangAttributeObserver=new MutationObserver(e=>{e.forEach(t=>{this.#e?Ye.lang==="auto"?(this.#n=null,this._setHtmlLangAttribute(this.locale)):this.#n=document.documentElement.lang:this._onLocaleChanged(document.documentElement.lang,t.oldValue||"")})})),this._htmlLangAttributeObserver.observe(document.documentElement,{attributes:!0,attributeFilter:["lang"],attributeOldValue:!0})}_teardownHtmlLangAttributeObserver(){this._htmlLangAttributeObserver&&this._htmlLangAttributeObserver.disconnect()}_isNamespaceInCache(e,t){return!!(this.__storage[e]&&this.__storage[e][t])}_getCachedNamespaceLoaderPromise(e,t){return this.__namespaceLoaderPromisesCache[e]?this.__namespaceLoaderPromisesCache[e][t]:null}_loadNamespaceData(e,t,i,s){const o=this._getNamespaceLoader(t,i,s),r=this._getNamespaceLoaderPromise(o,e,s);return this._cacheNamespaceLoaderPromise(e,s,r),r.then(a=>{if(this.__namespaceLoaderPromisesCache[e]&&this.__namespaceLoaderPromisesCache[e][s]===r){const l=pu(a)?a.default:a;this.addData(e,s,l)}})}_getNamespaceLoader(e,t,i){let s=this.__namespaceLoadersCache[i];if(s||(t?(s=e[i],this.__namespaceLoadersCache[i]=s):(s=this._lookupNamespaceLoader(i),this.__namespaceLoadersCache[i]=s)),!s)throw new Error(`Namespace "${i}" was not properly setup.`);return this.__namespaceLoadersCache[i]=s,s}_getNamespaceLoaderPromise(e,t,i,s=this._fallbackLocale){return e(t,i).catch(()=>{const o=this._getLangFromLocale(t);return e(o,i).catch(()=>{if(s)return this._getNamespaceLoaderPromise(e,s,i,"").catch(()=>{const r=this._getLangFromLocale(s);throw new Error(`Data for namespace "${i}" and current locale "${t}" or fallback locale "${s}" could not be loaded. Make sure you have data either for locale "${t}" (and/or generic language "${o}") or for fallback "${s}" (and/or "${r}").`)});throw new Error(`Data for namespace "${i}" and locale "${t}" could not be loaded. Make sure you have data for locale "${t}" (and/or generic language "${o}").`)})})}_cacheNamespaceLoaderPromise(e,t,i){this.__namespaceLoaderPromisesCache[e]||(this.__namespaceLoaderPromisesCache[e]={}),this.__namespaceLoaderPromisesCache[e][t]=i}_lookupNamespaceLoader(e){for(const[t,i]of this.__namespacePatternsMap){const s=typeof t=="string"&&t===e,o=typeof t=="object"&&t.constructor.name==="RegExp"&&t.test(e);if(s||o)return i}return null}_getLangFromLocale(e){return e.substring(0,2)}_onLocaleChanged(e,t){this.dispatchEvent(new CustomEvent("__localeChanging")),e!==t&&(this._autoLoadOnLocaleChange?(this._loadAllMissing(e,t),this.loadingComplete.then(()=>{this.dispatchEvent(new CustomEvent("localeChanged",{detail:{newLocale:e,oldLocale:t}}))})):this.dispatchEvent(new CustomEvent("localeChanged",{detail:{newLocale:e,oldLocale:t}})))}_loadAllMissing(e,t){const i=this.__storage[t]||{},s=this.__storage[e]||{};Object.keys(i).forEach(o=>{s[o]||this.loadNamespace(o,{locale:e})})}_getMessageForKeys(e,t){if(typeof e=="string")return this._getMessageForKey(e,t);const i=Array.from(e).reverse();let s,o;for(;i.length;)if(s=i.pop(),o=this._getMessageForKey(s,t),o)return o}_getMessageForKey(e,t){if(!e||e.indexOf(":")===-1)throw new Error(`Namespace is missing in the key "${e}". The format for keys is "namespace:name".`);const[i,s]=e.split(":"),o=this.__storage[t],r=o?o[i]:{},l=s.split(".").reduce((d,c)=>typeof d=="object"?d[c]:d,r);return String(l||(this._showKeyAsFallback?e:""))}#i(e){if(!e.includes("-"))throw new Error(`
      Locale was set to ${e}.
      Language only locales are not allowed, please use the full language locale e.g. 'en-GB' instead of 'en'.
      See https://github.com/ing-bank/lion/issues/187 for more information.
    `)}get _supportExternalTranslationTools(){return this.#e}set _supportExternalTranslationTools(e){this.#e=e}get _langAttrSetByTranslationTool(){return this.#t}set _langAttrSetByTranslationTool(e){this.#t=e}}const vi=Symbol.for("lion::SingletonManagerClassStorage"),yi=globalThis||window;class mu{constructor(){this._map=yi[vi]?yi[vi]:yi[vi]=new Map}set(e,t){this.has(e)||this._map.set(e,t)}get(e){return this._map.get(e)}has(e){return this._map.has(e)}}const On=new mu;function Gi(){if(On.has("@lion/ui::localize::0.x"))return On.get("@lion/ui::localize::0.x");const n=new fu({autoLoadOnLocaleChange:!0,fallbackLocale:"en-GB"});return On.set("@lion/ui::localize::0.x",n),n}const Gt=(n,e)=>{const t=n._$AN;if(t===void 0)return!1;for(const i of t)i._$AO?.(e,!1),Gt(i,e);return!0},Vn=n=>{let e,t;do{if((e=n._$AM)===void 0)break;t=e._$AN,t.delete(n),n=e}while(t?.size===0)},oa=n=>{for(let e;e=n._$AM;n=e){let t=e._$AN;if(t===void 0)e._$AN=t=new Set;else if(t.has(n))break;t.add(n),vu(e)}};function bu(n){this._$AN!==void 0?(Vn(this),this._$AM=n,oa(this)):this._$AM=n}function gu(n,e=!1,t=0){const i=this._$AH,s=this._$AN;if(s!==void 0&&s.size!==0)if(e)if(Array.isArray(i))for(let o=t;o<i.length;o++)Gt(i[o],!1),Vn(i[o]);else i!=null&&(Gt(i,!1),Vn(i));else Gt(this,n)}const vu=n=>{n.type==ks.CHILD&&(n._$AP??=gu,n._$AQ??=bu)};class yu extends Ss{constructor(){super(...arguments),this._$AN=void 0}_$AT(e,t,i){super._$AT(e,t,i),oa(this),this.isConnected=e._$AU}_$AO(e,t=!0){e!==this.isConnected&&(this.isConnected=e,e?this.reconnected?.():this.disconnected?.()),t&&(Gt(this,e),Vn(this))}setValue(e){if(Zd(this._$Ct))this._$Ct._$AI(e,this);else{const t=[...this._$Ct._$AH];t[this._$Ci]=e,this._$Ct._$AI(t,this,0)}}disconnected(){}reconnected(){}}let _u=class{constructor(e){this.G=e}disconnect(){this.G=void 0}reconnect(e){this.G=e}deref(){return this.G}},wu=class{constructor(){this.Y=void 0,this.Z=void 0}get(){return this.Y}pause(){this.Y??=new Promise((e=>this.Z=e))}resume(){this.Z?.(),this.Y=this.Z=void 0}};const Co=n=>!Jd(n)&&typeof n.then=="function",So=1073741823;let Eu=class extends yu{constructor(){super(...arguments),this._$Cwt=So,this._$Cbt=[],this._$CK=new _u(this),this._$CX=new wu}render(...e){return e.find((t=>!Co(t)))??Me}update(e,t){const i=this._$Cbt;let s=i.length;this._$Cbt=t;const o=this._$CK,r=this._$CX;this.isConnected||this.disconnected();for(let a=0;a<t.length&&!(a>this._$Cwt);a++){const l=t[a];if(!Co(l))return this._$Cwt=a,l;a<s&&l===i[a]||(this._$Cwt=So,s=0,Promise.resolve(l).then((async d=>{for(;r.get();)await r.get();const c=o.deref();if(c!==void 0){const p=c._$Cbt.indexOf(l);p>-1&&p<c._$Cwt&&(c._$Cwt=p,c.setValue(d))}})))}return Me}disconnected(){this._$CK.disconnect(),this._$CX.pause()}reconnected(){this._$CK.reconnect(this),this._$CX.resume()}};const xu=Cs(Eu),ku=n=>class extends n{static get localizeNamespaces(){return[]}static get waitForLocalizeNamespaces(){return!0}constructor(){super(),this._localizeManager=Gi(),this.__boundLocalizeOnLocaleChanged=(...t)=>{const i=Array.from(t)[0];this.__localizeOnLocaleChanged(i)},this.__boundLocalizeOnLocaleChanging=()=>{this.__localizeOnLocaleChanging()},this.__localizeStartLoadingNamespaces(),this.localizeNamespacesLoaded&&this.localizeNamespacesLoaded.then(()=>{this.__localizeMessageSync=!0})}async scheduleUpdate(){Object.getPrototypeOf(this).constructor.waitForLocalizeNamespaces&&await this.localizeNamespacesLoaded,super.scheduleUpdate()}connectedCallback(){super.connectedCallback(),this.localizeNamespacesLoaded&&this.localizeNamespacesLoaded.then(()=>this.onLocaleReady()),this._localizeManager.addEventListener("__localeChanging",this.__boundLocalizeOnLocaleChanging),this._localizeManager.addEventListener("localeChanged",this.__boundLocalizeOnLocaleChanged)}disconnectedCallback(){super.disconnectedCallback(),this._localizeManager.removeEventListener("__localeChanging",this.__boundLocalizeOnLocaleChanging),this._localizeManager.removeEventListener("localeChanged",this.__boundLocalizeOnLocaleChanged)}msgLit(t,i,s){return this.__localizeMessageSync?this._localizeManager.msg(t,i,s):this.localizeNamespacesLoaded?xu(this.localizeNamespacesLoaded.then(()=>this._localizeManager.msg(t,i,s)),B):""}__getUniqueNamespaces(){const t=[],i=new Set;return Object.getPrototypeOf(this).constructor.localizeNamespaces.forEach(i.add.bind(i)),i.forEach(s=>{t.push(s)}),t}__localizeStartLoadingNamespaces(){this.localizeNamespacesLoaded=this._localizeManager.loadNamespaces(this.__getUniqueNamespaces())}__localizeOnLocaleChanging(){this.__localizeStartLoadingNamespaces()}__localizeOnLocaleChanged(t){this.onLocaleChanged(t.detail.newLocale,t.detail.oldLocale)}onLocaleReady(){this.onLocaleUpdated()}onLocaleChanged(t,i){this.onLocaleUpdated(),this.requestUpdate()}onLocaleUpdated(){}},Cu=ie(ku),Yi="3.0.0",Ao=window.scopedElementsVersions||(window.scopedElementsVersions=[]);Ao.includes(Yi)||Ao.push(Yi);const Su=n=>class extends n{static scopedElements;static get scopedElementsVersion(){return Yi}static __registry;get registry(){return this.constructor.__registry}set registry(t){this.constructor.__registry=t}attachShadow(t){const{scopedElements:i}=this.constructor;if(!this.registry||this.registry===this.constructor.__registry&&!Object.prototype.hasOwnProperty.call(this.constructor,"__registry")){this.registry=new CustomElementRegistry;for(const[o,r]of Object.entries(i??{}))this.registry.define(o,r)}return super.attachShadow({...t,customElements:this.registry,registry:this.registry})}},Au=ie(Su),Tu=n=>class extends Au(n){createRenderRoot(){const{shadowRootOptions:t,elementStyles:i}=this.constructor,s=this.attachShadow(t);return this.renderOptions.creationScope=s,us(s,i),this.renderOptions.renderBefore??=s.firstChild,s}},Nu=ie(Tu);function yn(){return!!(globalThis.ShadowRoot?.prototype.createElement&&globalThis.ShadowRoot?.prototype.importNode)}const $u=n=>class extends Nu(n){constructor(){super()}createScopedElement(t){return(yn()?this.shadowRoot:document).createElement(t)}defineScopedElement(t,i){const s=this.registry.get(t),o=s&&s!==i;return!yn()&&o&&console.error([`You are trying to re-register the "${t}" custom element with a different class via ScopedElementsMixin.`,"This is only possible with a CustomElementRegistry.","Your browser does not support this feature so you will need to load a polyfill for it.",'Load "@webcomponents/scoped-custom-element-registry" before you register ANY web component to the global customElements registry.','e.g. add "<script src="/node_modules/@webcomponents/scoped-custom-element-registry/scoped-custom-element-registry.min.js"><\/script>" as your first script tag.',"For more details you can visit https://open-wc.org/docs/development/scoped-elements/"].join(`
`)),s?this.registry.get(t):this.registry.define(t,i)}attachShadow(t){const{scopedElements:i}=this.constructor;if(!this.registry||this.registry===this.constructor.__registry&&!Object.prototype.hasOwnProperty.call(this.constructor,"__registry")){this.registry=yn()?new CustomElementRegistry:customElements;for(const[o,r]of Object.entries(i??{}))this.defineScopedElement(o,r)}return Element.prototype.attachShadow.call(this,{...t,customElements:this.registry,registry:this.registry})}createRenderRoot(){const{shadowRootOptions:t,elementStyles:i}=this.constructor,s=this.attachShadow(t);return yn()&&(this.renderOptions.creationScope=s),s instanceof ShadowRoot&&(us(s,i),this.renderOptions.renderBefore=this.renderOptions.renderBefore||s.firstChild),s}},ra=ie($u);class Ou{constructor(){this.__running=!1,this.__queue=[]}add(e){this.__queue.push(e),this.__running||(this.complete=new Promise(t=>{this.__callComplete=t}),this.__run())}async __run(){this.__running=!0,await this.__queue[0](),this.__queue.shift(),this.__queue.length>0?this.__run():(this.__running=!1,this.__callComplete&&this.__callComplete())}}function Lu(n){return n.charAt(0).toUpperCase()+n.slice(1)}const Fu=n=>class extends n{constructor(){super(),this.__SyncUpdatableNamespace={}}firstUpdated(e){super.firstUpdated(e),this.__syncUpdatableInitialize()}connectedCallback(){super.connectedCallback(),this.__SyncUpdatableNamespace.connected=!0}disconnectedCallback(){super.disconnectedCallback(),this.__SyncUpdatableNamespace.connected=!1}static enabledWarnings=super.enabledWarnings?.filter(e=>e!=="change-in-update")||[];static __syncUpdatableHasChanged(e,t,i){const s=this.elementProperties;return s.get(e)&&s.get(e).hasChanged?s.get(e).hasChanged(t,i):t!==i}__syncUpdatableInitialize(){const e=this.__SyncUpdatableNamespace,t=this.constructor;e.initialized=!0,e.queue&&Array.from(e.queue).forEach(i=>{t.__syncUpdatableHasChanged(i,this[i],void 0)&&this.updateSync(i,void 0)})}requestUpdate(e,t,i){if(super.requestUpdate(e,t,i),e===void 0)return;this.__SyncUpdatableNamespace=this.__SyncUpdatableNamespace||{};const s=this.__SyncUpdatableNamespace,o=this.constructor;s.initialized?o.__syncUpdatableHasChanged(e,this[e],t)&&this.updateSync(e,t):(s.queue=s.queue||new Set,s.queue.add(e))}updateSync(e,t){}},Ru=ie(Fu),Mu=n=>{switch(n){case"bg-BG":return V(()=>import("./bg-BG.js"),__vite__mapDeps([0,1]),import.meta.url);case"bg":return V(()=>import("./bg.js"),[],import.meta.url);case"cs-CZ":return V(()=>import("./cs-CZ.js"),__vite__mapDeps([2,3]),import.meta.url);case"cs":return V(()=>import("./cs.js"),[],import.meta.url);case"de-DE":return V(()=>import("./de-DE.js"),__vite__mapDeps([4,5]),import.meta.url);case"de":return V(()=>import("./de.js"),[],import.meta.url);case"en-AU":return V(()=>import("./en-AU.js"),__vite__mapDeps([6,7]),import.meta.url);case"en-GB":return V(()=>import("./en-GB.js"),__vite__mapDeps([8,7]),import.meta.url);case"en-US":return V(()=>import("./en-US.js"),__vite__mapDeps([9,7]),import.meta.url);case"en-PH":case"en":return V(()=>import("./en.js"),[],import.meta.url);case"es-ES":return V(()=>import("./es-ES.js"),__vite__mapDeps([10,11]),import.meta.url);case"es":return V(()=>import("./es.js"),[],import.meta.url);case"fr-FR":return V(()=>import("./fr-FR.js"),__vite__mapDeps([12,13]),import.meta.url);case"fr-BE":return V(()=>import("./fr-BE.js"),__vite__mapDeps([14,13]),import.meta.url);case"fr":return V(()=>import("./fr.js"),[],import.meta.url);case"hu-HU":return V(()=>import("./hu-HU.js"),__vite__mapDeps([15,16]),import.meta.url);case"hu":return V(()=>import("./hu.js"),[],import.meta.url);case"it-IT":return V(()=>import("./it-IT.js"),__vite__mapDeps([17,18]),import.meta.url);case"it":return V(()=>import("./it.js"),[],import.meta.url);case"nl-BE":return V(()=>import("./nl-BE.js"),__vite__mapDeps([19,20]),import.meta.url);case"nl-NL":return V(()=>import("./nl-NL.js"),__vite__mapDeps([21,20]),import.meta.url);case"nl":return V(()=>import("./nl.js"),[],import.meta.url);case"pl-PL":return V(()=>import("./pl-PL.js"),__vite__mapDeps([22,23]),import.meta.url);case"pl":return V(()=>import("./pl.js"),[],import.meta.url);case"ro-RO":return V(()=>import("./ro-RO.js"),__vite__mapDeps([24,25]),import.meta.url);case"ro":return V(()=>import("./ro.js"),[],import.meta.url);case"ru-RU":return V(()=>import("./ru-RU.js"),__vite__mapDeps([26,27]),import.meta.url);case"ru":return V(()=>import("./ru.js"),[],import.meta.url);case"sk-SK":return V(()=>import("./sk-SK.js"),__vite__mapDeps([28,29]),import.meta.url);case"sk":return V(()=>import("./sk.js"),[],import.meta.url);case"tr-TR":return V(()=>import("./tr-TR.js"),__vite__mapDeps([30,31]),import.meta.url);case"tr":return V(()=>import("./tr.js"),[],import.meta.url);case"uk-UA":return V(()=>import("./uk-UA.js"),__vite__mapDeps([32,33]),import.meta.url);case"uk":return V(()=>import("./uk.js"),[],import.meta.url);case"zh-CN":case"zh":return V(()=>import("./zh.js"),[],import.meta.url);default:return V(()=>import("./en.js"),[],import.meta.url)}},Du=n=>`${n[0].toUpperCase()}${n.slice(1)}`;class Iu extends Cu(G){static get properties(){return{feedbackData:{attribute:!1}}}static localizeNamespaces=[{"lion-form-core":Mu},...super.localizeNamespaces];static get styles(){return[F`
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
      `]}constructor(){super(),this.feedbackData=void 0}_messageTemplate({message:e}){return e}updated(e){super.updated(e),this.feedbackData&&this.feedbackData[0]?(this.setAttribute("type",this.feedbackData[0].type),this.currentType=this.feedbackData[0].type):this.currentType!=="success"&&this.removeAttribute("type")}render(){return x`
      ${this.feedbackData&&this.feedbackData.map(({message:e,type:t,validator:i})=>x`
          <div class="validation-feedback__type">
            ${e&&t?this._localizeManager.msg(`lion-form-core:validation${Du(t)}`):B}
          </div>
          ${this._messageTemplate({message:e,type:t,validator:i})}
        `)}
    `}}class Bn{constructor(e){this.type="unparseable",this.viewValue=e}toString(){return JSON.stringify({type:this.type,viewValue:this.viewValue})}}const Pu=[Node.DOCUMENT_POSITION_PRECEDING,Node.DOCUMENT_POSITION_CONTAINS,Node.DOCUMENT_POSITION_CONTAINS|Node.DOCUMENT_POSITION_PRECEDING];function aa(n,{reverse:e}={}){const t=(s,o)=>{const r=s.compareDocumentPosition(o);return Pu.includes(r)?1:-1},i=n.filter(s=>s);return i.sort(t),e&&i.reverse(),i}const zu=n=>class extends n{constructor(){super(),this.name="",this._parentFormGroup=void 0,this.allowCrossRootRegistration=!1}get name(){return this.__name||""}set name(e){const t=this.name;this.__name=e.toString(),this.requestUpdate("name",t)}static get properties(){return{name:{type:String,reflect:!0},allowCrossRootRegistration:{type:Boolean,attribute:"allow-cross-root-registration"}}}connectedCallback(){super.connectedCallback(),this.dispatchEvent(new CustomEvent("form-element-register",{detail:{element:this},bubbles:!0,composed:!!this.allowCrossRootRegistration}))}disconnectedCallback(){super.disconnectedCallback(),this.__unregisterFormElement()}__unregisterFormElement(){this._parentFormGroup&&this._parentFormGroup.removeFormElement(this)}},Ns=ie(zu),Vu=n=>class extends Ns(hn(pn(n))){static get properties(){return{readOnly:{type:Boolean,attribute:"readonly",reflect:!0},label:String,labelSrOnly:{type:Boolean,attribute:"label-sr-only",reflect:!0},helpText:{type:String,attribute:"help-text"},modelValue:{attribute:!1},_ariaLabelledNodes:{attribute:!1},_ariaDescribedNodes:{attribute:!1},_repropagationRole:{attribute:!1},_isRepropagationEndpoint:{attribute:!1}}}get label(){return this.__label??(this._labelNode?.textContent||"")}set label(t){const i=this.label;this.__label=t,this.requestUpdate("label",i)}get helpText(){return this.__helpText??(this._helpTextNode?.textContent||"")}set helpText(t){const i=this.helpText;this.__helpText=t,this.requestUpdate("helpText",i)}get fieldName(){return this.__fieldName||this.label||this.name||""}set fieldName(t){this.__fieldName=t}get slots(){return{...super.slots,label:()=>{const t=document.createElement("label");return t.textContent=this.label,t},"help-text":()=>{const t=document.createElement("div");return t.textContent=this.helpText,t}}}get _inputNode(){return this.__getDirectSlotChild("input")}get _labelNode(){return this.__getDirectSlotChild("label")}get _helpTextNode(){return this.__getDirectSlotChild("help-text")}get _feedbackNode(){return this.__getDirectSlotChild("feedback")}static enabledWarnings=super.enabledWarnings?.filter(t=>t!=="change-in-update")||[];constructor(){super(),this.readOnly=!1,this.labelSrOnly=!1,this._inputId=Qr(this.localName),this._ariaLabelledNodes=[],this._ariaDescribedNodes=[],this._repropagationRole="child",this._isRepropagationEndpoint=!1,this.addEventListener("model-value-changed",this.__repropagateChildrenValues),this._onLabelClick=this._onLabelClick.bind(this)}connectedCallback(){super.connectedCallback(),this._enhanceLightDomClasses(),this._enhanceLightDomA11y(),this._triggerInitialModelValueChangedEvent(),this._labelNode&&this._labelNode.addEventListener("click",this._onLabelClick)}disconnectedCallback(){super.disconnectedCallback(),this._labelNode&&this._labelNode.removeEventListener("click",this._onLabelClick)}updated(t){super.updated(t),t.has("disabled")&&this._inputNode?.setAttribute("aria-disabled",`${!!this.disabled}`),t.has("_ariaLabelledNodes")&&this.__reflectAriaAttr("aria-labelledby",this._ariaLabelledNodes,this.__reorderAriaLabelledNodes),t.has("_ariaDescribedNodes")&&this.__reflectAriaAttr("aria-describedby",this._ariaDescribedNodes,this.__reorderAriaDescribedNodes),t.has("label")&&this.__label!==void 0&&this._labelNode&&(this._labelNode.textContent=this.label),t.has("helpText")&&this.__helpText!==void 0&&this._helpTextNode&&(this._helpTextNode.textContent=this.helpText),t.has("name")&&this.dispatchEvent(new CustomEvent("form-element-name-changed",{detail:{oldName:t.get("name"),newName:this.name},bubbles:!0}))}_triggerInitialModelValueChangedEvent(){this._dispatchInitialModelValueChangedEvent()}_enhanceLightDomClasses(){this._inputNode&&this._inputNode.classList.add("form-control")}_enhanceLightDomA11y(){const{_inputNode:t,_labelNode:i,_helpTextNode:s,_feedbackNode:o}=this;t&&(t.id=t.id||this._inputId),i&&(i.setAttribute("for",this._inputId),this.addToAriaLabelledBy(i,{idPrefix:"label"})),s&&this.addToAriaDescribedBy(s,{idPrefix:"help-text"}),o&&(this.addEventListener("focusin",()=>{o.setAttribute("aria-live","polite")}),this.addEventListener("focusout",()=>{o.setAttribute("aria-live","assertive")}),this.addToAriaDescribedBy(o,{idPrefix:"feedback"})),this._enhanceLightDomA11yForAdditionalSlots()}_enhanceLightDomA11yForAdditionalSlots(t=["prefix","suffix","before","after"]){t.forEach(i=>{const s=this.__getDirectSlotChild(i);s&&(s.hasAttribute("data-label")&&this.addToAriaLabelledBy(s,{idPrefix:i}),s.hasAttribute("data-description")&&this.addToAriaDescribedBy(s,{idPrefix:i}))})}__reflectAriaAttr(t,i,s){if(this._inputNode){if(s){const r=i.filter(p=>this.contains(p)),a=i.filter(p=>!this.contains(p)),l=r.map(p=>p.assignedSlot||p),d=[...aa(l)],c=[];d.forEach(p=>{r.forEach(m=>{p.name===m.slot&&c.push(m)})}),i=[...c,...a]}const o=i.map(r=>r.id).join(" ");this._inputNode.setAttribute(t,o)}}render(){return x`
        <div class="form-field__group-one">${this._groupOneTemplate()}</div>
        <div class="form-field__group-two">${this._groupTwoTemplate()}</div>
      `}_groupOneTemplate(){return x` ${this._labelTemplate()} ${this._helpTextTemplate()} `}_groupTwoTemplate(){return x` ${this._inputGroupTemplate()} ${this._feedbackTemplate()} `}_labelTemplate(){return x`
        <div class="form-field__label">
          <slot name="label"></slot>
        </div>
      `}_helpTextTemplate(){return x`
        <small class="form-field__help-text">
          <slot name="help-text"></slot>
        </small>
      `}_inputGroupTemplate(){return x`
        <div class="input-group">
          ${this._inputGroupBeforeTemplate()}
          <div class="input-group__container">
            ${this._inputGroupPrefixTemplate()} ${this._inputGroupInputTemplate()}
            ${this._inputGroupSuffixTemplate()}
          </div>
          ${this._inputGroupAfterTemplate()}
        </div>
      `}_inputGroupBeforeTemplate(){return x`
        <div class="input-group__before">
          <slot name="before"></slot>
        </div>
      `}_inputGroupPrefixTemplate(){return Array.from(this.children).find(t=>t.slot==="prefix")?x`
            <div class="input-group__prefix">
              <slot name="prefix"></slot>
            </div>
          `:B}_inputGroupInputTemplate(){return x`
        <div class="input-group__input">
          <slot name="input"></slot>
        </div>
      `}_inputGroupSuffixTemplate(){return Array.from(this.children).find(t=>t.slot==="suffix")?x`
            <div class="input-group__suffix">
              <slot name="suffix"></slot>
            </div>
          `:B}_inputGroupAfterTemplate(){return x`
        <div class="input-group__after">
          <slot name="after"></slot>
        </div>
      `}_feedbackTemplate(){return x`
        <div class="form-field__feedback">
          <slot name="feedback"></slot>
        </div>
      `}_isEmpty(t=this.modelValue){let i=t;if(this.modelValue instanceof Bn&&(i=this.modelValue.viewValue),typeof i=="object"&&i!==null&&!(i instanceof Date))return!Object.keys(i).length;const s=typeof i=="number"&&(i===0||Number.isNaN(i));return!i&&!s&&!(typeof i=="boolean"&&i===!1)}static get styles(){return[F`
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
        `]}_getAriaDescriptionElements(){return[this._helpTextNode,this._feedbackNode]}addToAriaLabelledBy(t,{idPrefix:i="",reorder:s=!0}={}){t.id=t.id||`${i}-${this._inputId}`,this._ariaLabelledNodes.includes(t)||(this._ariaLabelledNodes=[...this._ariaLabelledNodes,t],this.__reorderAriaLabelledNodes=!!s)}removeFromAriaLabelledBy(t){this._ariaLabelledNodes.includes(t)&&(this._ariaLabelledNodes.splice(this._ariaLabelledNodes.indexOf(t),1),this._ariaLabelledNodes=[...this._ariaLabelledNodes],this.__reorderAriaLabelledNodes=!1)}addToAriaDescribedBy(t,{idPrefix:i="",reorder:s=!0}={}){t.id=t.id||`${i}-${this._inputId}`,this._ariaDescribedNodes.includes(t)||(this._ariaDescribedNodes=[...this._ariaDescribedNodes,t],this.__reorderAriaDescribedNodes=!!s)}removeFromAriaDescribedBy(t){this._ariaDescribedNodes.includes(t)&&(this._ariaDescribedNodes.splice(this._ariaDescribedNodes.indexOf(t),1),this._ariaDescribedNodes=[...this._ariaDescribedNodes],this.__reorderAriaLabelledNodes=!1)}__getDirectSlotChild(t){return Array.from(this.children).find(i=>i.slot===t)}_dispatchInitialModelValueChangedEvent(){this._repropagationRole!=="child"&&(this.__repropagateChildrenInitialized=!0,this.dispatchEvent(new CustomEvent("model-value-changed",{bubbles:!0,detail:{formPath:[this],initialize:!0,isTriggeredByUser:!1}})))}_onBeforeRepropagateChildrenValues(t){}__repropagateChildrenValues(t){this._onBeforeRepropagateChildrenValues(t);const i=t.detail&&t.detail.element||t.target,s=this._isRepropagationEndpoint||this._repropagationRole==="choice-group";if(i===this)return;t.stopImmediatePropagation();const r=this._repropagationRole!=="child"&&!this.__repropagateChildrenInitialized,a=t.detail&&t.detail.initialize;if(r||a||!this._repropagationCondition(i))return;let l=[];s||(l=t.detail&&t.detail.formPath||[i]);const d=[...l,this];this.dispatchEvent(new CustomEvent("model-value-changed",{bubbles:!0,detail:{formPath:d,isTriggeredByUser:!!t.detail?.isTriggeredByUser}}))}_repropagationCondition(t){return!!t}_onLabelClick(){}},Mt=ie(Vu);class Bu extends EventTarget{constructor(e,t){super(),this.__param=e,this.__config=t||{},this.type=t?.type||"error"}static _$isValidator$=!0;static validatorName="";static async=!1;execute(e,t,i){if(!this.constructor.validatorName)throw new Error(`A validator needs to have a name! Please set it via "static get validatorName() { return 'IsCat'; }"`);return!0}set param(e){this.__param=e,this.dispatchEvent(new Event("param-changed"))}get param(){return this.__param}set config(e){this.__config=e,this.dispatchEvent(new Event("config-changed"))}get config(){return this.__config}async _getMessage(e){const t=this.constructor,i={name:t.validatorName,type:this.type,params:this.param,config:this.config,...e};if(this.config.getMessage){if(typeof this.config.getMessage=="function")return this.config.getMessage(i);throw new Error(`You must provide a value for getMessage of type 'function', you provided a value of type: ${typeof this.config.getMessage}`)}return t.getMessage(i)}static async getMessage(e){return`Please configure an error message for "${this.name}" by overriding "static async getMessage()"`}onFormControlConnect(e){}onFormControlDisconnect(e){}abortExecution(){}}function To(n=[],e=[]){return n.filter(t=>!e.includes(t)).concat(e.filter(t=>!n.includes(t)))}function Uu(n){return n instanceof Bn?n.viewValue:n}const Hu=n=>class extends Mt(Ru(hn(pn(ra(n))))){static get scopedElements(){return{...super.scopedElements,"lion-validation-feedback":Iu}}static get properties(){return{validators:{attribute:!1},hasFeedbackFor:{attribute:!1},shouldShowFeedbackFor:{attribute:!1},showsFeedbackFor:{type:Array,attribute:"shows-feedback-for",reflect:!0,converter:{fromAttribute:e=>e.split(","),toAttribute:e=>e.join(",")}},validationStates:{attribute:!1},isPending:{type:Boolean,attribute:"is-pending",reflect:!0},defaultValidators:{attribute:!1},_visibleMessagesAmount:{attribute:!1},__childModelValueChanged:{attribute:!1}}}static get validationTypes(){return["error"]}get operationMode(){return"enter"}get slots(){return{...super.slots,feedback:()=>{const e=this.createScopedElement("lion-validation-feedback");return e.setAttribute("data-tag-name","lion-validation-feedback"),e}}}get _allValidators(){return[...this.validators,...this.defaultValidators]}constructor(){super(),this.hasFeedbackFor=[],this.showsFeedbackFor=[],this.shouldShowFeedbackFor=[],this.validationStates={},this.isPending=!1,this.validators=[],this.defaultValidators=[],this._visibleMessagesAmount=1,this.__syncValidationResult=[],this.__asyncValidationResult=[],this.__validationResult=[],this.__prevValidationResult=[],this.__prevShownValidationResult=[],this.__childModelValueChanged=!1,this._onValidatorUpdated=this._onValidatorUpdated.bind(this),this._updateFeedbackComponent=this._updateFeedbackComponent.bind(this)}connectedCallback(){super.connectedCallback(),Gi().addEventListener("localeChanged",this._updateFeedbackComponent)}disconnectedCallback(){super.disconnectedCallback(),Gi().removeEventListener("localeChanged",this._updateFeedbackComponent)}firstUpdated(e){super.firstUpdated(e),this.__isValidateInitialized=!0,this.validate(),this._repropagationRole!=="child"&&this.addEventListener("model-value-changed",()=>{this.__childModelValueChanged=!0})}updateSync(e,t){if(super.updateSync(e,t),e==="validators"?(this.__setupValidators(),this.validate({clearCurrentResult:!0})):e==="modelValue"&&this.validate({clearCurrentResult:!0}),["touched","dirty","prefilled","focused","submitted","hasFeedbackFor","filled"].includes(e)&&this._updateShouldShowFeedbackFor(),e==="showsFeedbackFor"){this._inputNode&&this._inputNode.setAttribute("aria-invalid",`${this._hasFeedbackVisibleFor("error")}`);const i=To(this.showsFeedbackFor,t);i.length>0&&this.dispatchEvent(new Event("showsFeedbackForChanged",{bubbles:!0})),i.forEach(s=>{this.dispatchEvent(new Event(`showsFeedbackFor${Lu(s)}Changed`,{bubbles:!0}))})}e==="shouldShowFeedbackFor"&&To(this.shouldShowFeedbackFor,t).length>0&&this.dispatchEvent(new Event("shouldShowFeedbackForChanged",{bubbles:!0}))}async validate({clearCurrentResult:e=!1}={}){if(this.validateComplete=new Promise(t=>{this.__validateCompleteResolve=t}),this.disabled){this.__clearValidationResults(),this.__finishValidationPass(),this._updateFeedbackComponent();return}this.__isValidateInitialized&&(this.__prevValidationResult=this.__validationResult,e&&this.__clearValidationResults(),await this.__executeValidators())}#e(e){let t=e;for(;t;){if(t.constructor.validatorName==="Required")return!0;t=Object.getPrototypeOf(t)}return!1}async __executeValidators(){const e=Uu(this.modelValue),t=this.__isEmpty(e);if(this.__syncValidationResult=[],t){const a=!this._isFormOrFieldset,l=this._allValidators.find(d=>d.constructor?.validatorName==="Required");if(l&&(this.__syncValidationResult=[{validator:l,outcome:!0}]),a){this.__finishValidationPass({syncValidationResult:this.__syncValidationResult});return}}const i=[],s=[],o=[];for(const a of this._allValidators)a?.executeOnResults?i.push(a):this.#e(a)||(a.constructor.async?o.push(a):s.push(a));const r=!!o.length;this.__syncValidationResult=[...this.__syncValidationResult,...this.__executeSyncValidators(s,e)],this.__finishValidationPass({syncValidationResult:this.__syncValidationResult,metaValidators:i}),r?(this.isPending=!0,this.__asyncValidationResult=await this.__executeAsyncValidators(o,e),this.isPending=!1,this.__finishValidationPass({syncValidationResult:this.__syncValidationResult,asyncValidationResult:this.__asyncValidationResult,metaValidators:i}),this.__validateCompleteResolve?.(!0)):this.__validateCompleteResolve?.(!0)}__executeSyncValidators(e,t){return e.map(i=>({validator:i,outcome:i.execute(t,i.param,{node:this})})).filter(i=>!!i.outcome)}async __executeAsyncValidators(e,t){const i=e.map(o=>o.execute(t,o.param,{node:this})),s=await Promise.all(i);return s.map((o,r)=>({validator:e[r],outcome:s[r]})).filter(o=>!!o.outcome)}__executeMetaValidators(e,t){return t.length?this._isEmpty(this.modelValue)?(this.__prevShownValidationResult=[],[]):t.map(i=>({validator:i,outcome:i.executeOnResults({regularValidationResult:e.map(s=>s.validator),prevValidationResult:this.__prevValidationResult.map(s=>s.validator),prevShownValidationResult:this.__prevShownValidationResult.map(s=>s.validator)})})).filter(i=>!!i.outcome):[]}__finishValidationPass({syncValidationResult:e=[],asyncValidationResult:t=[],metaValidators:i=[]}={}){const s=[...e,...t],o=this.__executeMetaValidators(s,i);this.__validationResult=[...o,...s];const a=this.constructor.validationTypes.reduce((l,d)=>({...l,[d]:{}}),{});for(const{validator:l,outcome:d}of this.__validationResult){a[l.type]||(a[l.type]={});const c=l.constructor;a[l.type][c.validatorName]=d}this.validationStates=a,this.hasFeedbackFor=[...new Set(this.__validationResult.map(({validator:l})=>l.type))],this.dispatchEvent(new Event("validate-performed",{bubbles:!0}))}__clearValidationResults(){this.__syncValidationResult=[],this.__asyncValidationResult=[]}_onValidatorUpdated(e){(e.type==="param-changed"||e.type==="config-changed")&&this.validate()}__setupValidators(){const e=["param-changed","config-changed"];for(const t of this.__prevValidators||[]){for(const i of e)t.removeEventListener?.(i,this._onValidatorUpdated);t.onFormControlDisconnect(this)}for(const t of this._allValidators){if(t.constructor._$isValidator$===void 0){const a=`Validators array only accepts class instances of Validator. Type "${Array.isArray(t)?"array":typeof t}" found. This may be caused by having multiple installations of "@lion/ui/form-core.js".`;throw console.error(a,this),new Error(a)}const s=this.constructor,o=t.constructor;if(s.validationTypes.indexOf(t.type)===-1){const r=`This component does not support the validator type "${t.type}" used in "${o.validatorName}". You may change your validators type or add it to the components "static get validationTypes() {}".`;throw console.error(r,this),new Error(r)}for(const r of e)t.addEventListener?.(r,a=>{this._onValidatorUpdated(a,{validator:t})});t.onFormControlConnect(this)}this.__prevValidators=this._allValidators}__isEmpty(e){return typeof this._isEmpty=="function"?this._isEmpty(e):this.modelValue===null||typeof this.modelValue>"u"||this.modelValue===""}async __getFeedbackMessages(e){let t=await this.fieldName;return Promise.all(e.map(async({validator:i,outcome:s})=>(i.config.fieldName&&(t=await i.config.fieldName),{message:await i._getMessage({modelValue:this.modelValue,formControl:this,fieldName:t,outcome:s}),type:i.type,validator:i,visibilityDuration:i.config?.visibilityDuration||3e3})))}_updateFeedbackComponent(){window.clearTimeout(this.removeMessage);const{_feedbackNode:e}=this;e&&(this.__feedbackQueue||(this.__feedbackQueue=new Ou),this.showsFeedbackFor.length>0?this.__feedbackQueue.add(async()=>{const t=this._prioritizeAndFilterFeedback({validationResult:this.__validationResult.map(s=>s.validator)});this.__prioritizedResult=t.map(s=>this.__validationResult.find(r=>s===r.validator)).filter(Boolean),this.__prioritizedResult.length>0&&(this.__prevShownValidationResult=this.__prioritizedResult);const i=await this.__getFeedbackMessages(this.__prioritizedResult);e.feedbackData=i||[],i?.[0]&&i[0].type==="success"&&i[0].visibilityDuration!==1/0&&(this.removeMessage=window.setTimeout(()=>{e.removeAttribute("type"),e.feedbackData=[]},i[0].visibilityDuration))}):this.__feedbackQueue.add(async()=>{e.feedbackData=[]}),this.feedbackComplete=this.__feedbackQueue.complete)}_showFeedbackConditionFor(e,t){return!0}get _feedbackConditionMeta(){return{modelValue:this.modelValue,el:this}}feedbackCondition(e,t=this._feedbackConditionMeta,i=this._showFeedbackConditionFor.bind(this)){return i(e,t)}_hasFeedbackVisibleFor(e){return this.hasFeedbackFor?.includes(e)&&this.shouldShowFeedbackFor?.includes(e)}updated(e){if(super.updated(e),e.has("shouldShowFeedbackFor")||e.has("hasFeedbackFor")){const t=this.constructor;this.showsFeedbackFor=t.validationTypes.map(i=>this._hasFeedbackVisibleFor(i)?i:void 0).filter(Boolean),this._updateFeedbackComponent()}if(e.has("__childModelValueChanged")&&this.__childModelValueChanged&&(this.validate({clearCurrentResult:!0}),this.__childModelValueChanged=!1),e.has("validationStates")){const t=e.get("validationStates");t&&Object.entries(this.validationStates).forEach(([i,s])=>{t[i]&&JSON.stringify(s)!==JSON.stringify(t[i])&&this.dispatchEvent(new CustomEvent(`${i}StateChanged`,{detail:s}))})}}_updateShouldShowFeedbackFor(){const t=this.constructor.validationTypes.map(i=>this.feedbackCondition(i,this._feedbackConditionMeta,this._showFeedbackConditionFor.bind(this))?i:void 0).filter(Boolean);JSON.stringify(this.shouldShowFeedbackFor)!==JSON.stringify(t)&&(this.shouldShowFeedbackFor=t)}_prioritizeAndFilterFeedback({validationResult:e}){const i=this.constructor.validationTypes;return e.filter(o=>this.feedbackCondition(o.type,this._feedbackConditionMeta,this._showFeedbackConditionFor.bind(this))).sort((o,r)=>i.indexOf(o.type)-i.indexOf(r.type)).slice(0,this._visibleMessagesAmount)}},si=ie(Hu),qu=n=>class extends si(Mt(n)){static get properties(){return{formattedValue:{attribute:!1},serializedValue:{attribute:!1},formatOptions:{attribute:!1}}}#e={didFormatterOutputSyncToView:!1,didFormatterRun:!1};requestUpdate(t,i,s){super.requestUpdate(t,i,s),t==="modelValue"&&this.modelValue!==i&&this._onModelValueChanged({modelValue:this.modelValue},{modelValue:i}),t==="serializedValue"&&this.serializedValue!==i&&this._calculateValues({source:"serialized"}),t==="formattedValue"&&this.formattedValue!==i&&this._calculateValues({source:"formatted"})}get value(){return this._inputNode?.value||this.__value||""}set value(t){this._inputNode?(this._inputNode.value=t,this.__value=void 0):this.__value=t}preprocessor(t,i){}parser(t,i){return t}formatter(t,i){return t}serializer(t){return t!==void 0?t:""}deserializer(t){return t===void 0?"":t}_calculateValues({source:t}={source:null}){this.__preventRecursiveTrigger||(this.__preventRecursiveTrigger=!0,t!=="model"&&(t==="serialized"?this.modelValue=this.deserializer(this.serializedValue):t==="formatted"&&(this.modelValue=this._callParser())),t!=="formatted"&&(this.formattedValue=this._callFormatter()),t!=="serialized"&&(this.serializedValue=this.serializer(this.modelValue)),this._reflectBackFormattedValueToUser(),this.__preventRecursiveTrigger=!1,this.__prevViewValue=this.value)}_callParser(t=this.formattedValue){if(t==="")return"";if(typeof t!="string")return;const i=this.parser(t,{...this.formatOptions,mode:this.#t(),viewValueStates:this.#n()});return i!==void 0?i:new Bn(t)}_callFormatter(){return this.#e.didFormatterRun=!1,this._isHandlingUserInput&&this.hasFeedbackFor?.includes("error")&&this._inputNode?this.value:this.modelValue instanceof Bn?this.modelValue.viewValue:(this.#e.didFormatterRun=!0,this.formatter(this.modelValue,{...this.formatOptions,mode:this.#t(),viewValueStates:this.#n()}))}_onModelValueChanged(...t){this._calculateValues({source:"model"}),this._dispatchModelValueChangedEvent(...t)}_dispatchModelValueChangedEvent(...t){this.dispatchEvent(new CustomEvent("model-value-changed",{bubbles:!0,detail:{formPath:[this],isTriggeredByUser:!!this._isHandlingUserInput}}))}_syncValueUpwards(){this.__isHandlingComposition||this.__handlePreprocessor();const t=this.formattedValue;this.modelValue=this._callParser(this.value),t===this.formattedValue&&this.__prevViewValue!==this.value&&this._calculateValues()}__handlePreprocessor(){let t=this.value.length;this._inputNode&&"selectionStart"in this._inputNode&&this._inputNode?.type!=="range"&&(t=this._inputNode.selectionStart);const i=this.preprocessor(this.value,{...this.formatOptions,currentCaretIndex:t,prevViewValue:this.__prevViewValue});if(i!==void 0){if(typeof i=="string")this.value=i;else if(typeof i=="object"){const{viewValue:s,caretIndex:o}=i;this.value=s,o&&this._inputNode&&"selectionStart"in this._inputNode&&(this._inputNode.selectionStart=o,this._inputNode.selectionEnd=o)}}}_reflectBackFormattedValueToUser(){this._reflectBackOn()&&(this.value=typeof this.formattedValue<"u"?this.formattedValue:"",this.#e.didFormatterOutputSyncToView=!!this.formattedValue&&this.#e.didFormatterRun)}_reflectBackOn(){return!this._isHandlingUserInput}_proxyInputEvent(){this.dispatchEvent(new Event("user-input-changed",{bubbles:!0}))}_onUserInputChanged(){this._isHandlingUserInput=!0,this._syncValueUpwards(),this._isHandlingUserInput=!1}__onCompositionEvent({type:t}){t==="compositionstart"?this.__isHandlingComposition=!0:t==="compositionend"&&(this.__isHandlingComposition=!1,this._syncValueUpwards())}constructor(){super(),this.formatOn="change",this.formatOptions={mode:"auto"},this.formattedValue=void 0,this.serializedValue=void 0,this._isPasting=!1,this._isHandlingUserInput=!1,this.__prevViewValue="",this.__onCompositionEvent=this.__onCompositionEvent.bind(this),this.addEventListener("user-input-changed",this._onUserInputChanged),this.addEventListener("paste",this.__onPaste),this._reflectBackFormattedValueToUser=this._reflectBackFormattedValueToUser.bind(this),this._reflectBackFormattedValueDebounced=()=>{setTimeout(this._reflectBackFormattedValueToUser)}}__onPaste(){this._isPasting=!0,setTimeout(()=>{this._isPasting=!1})}connectedCallback(){super.connectedCallback(),typeof this.modelValue>"u"&&this._syncValueUpwards(),this.__prevViewValue=this.value,this._reflectBackFormattedValueToUser(),this._inputNode&&(this._inputNode.addEventListener(this.formatOn,this._reflectBackFormattedValueDebounced),this._inputNode.addEventListener("input",this._proxyInputEvent),this._inputNode.addEventListener("compositionstart",this.__onCompositionEvent),this._inputNode.addEventListener("compositionend",this.__onCompositionEvent))}disconnectedCallback(){super.disconnectedCallback(),this._inputNode&&(this._inputNode.removeEventListener("input",this._proxyInputEvent),this._inputNode.removeEventListener(this.formatOn,this._reflectBackFormattedValueDebounced),this._inputNode.removeEventListener("compositionstart",this.__onCompositionEvent),this._inputNode.removeEventListener("compositionend",this.__onCompositionEvent))}#t(){return this._isPasting?"pasted":this._isHandlingUserInput&&this.__prevViewValue?"user-edited":"auto"}#n(){const t=[];return this.#e.didFormatterOutputSyncToView&&t.push("formatted"),t}},$s=ie(qu),ju=n=>class extends Mt(n){static get properties(){return{touched:{type:Boolean,reflect:!0},dirty:{type:Boolean,reflect:!0},filled:{type:Boolean,reflect:!0},prefilled:{attribute:!1},submitted:{attribute:!1}}}requestUpdate(t,i,s){super.requestUpdate(t,i,s),t==="touched"&&this.touched!==i&&this._onTouchedChanged(),t==="modelValue"&&(this.filled=!this._isEmpty()),t==="dirty"&&this.dirty!==i&&this._onDirtyChanged()}constructor(){super(),this.touched=!1,this.dirty=!1,this.prefilled=!1,this.filled=!1,this._leaveEvent="blur",this._valueChangedEvent="model-value-changed",this._iStateOnLeave=this._iStateOnLeave.bind(this),this._iStateOnValueChange=this._iStateOnValueChange.bind(this)}connectedCallback(){super.connectedCallback(),this.addEventListener(this._leaveEvent,this._iStateOnLeave),this.addEventListener(this._valueChangedEvent,this._iStateOnValueChange),this.initInteractionState()}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener(this._leaveEvent,this._iStateOnLeave),this.removeEventListener(this._valueChangedEvent,this._iStateOnValueChange)}initInteractionState(){this.dirty=!1,this.prefilled=!this._isEmpty()}_iStateOnLeave(){this.touched=!0,this.prefilled=!this._isEmpty()}_iStateOnValueChange(){this.dirty=!0}resetInteractionState(){this.touched=!1,this.submitted=!1,this.dirty=!1,this.prefilled=!this._isEmpty()}_onTouchedChanged(){this.dispatchEvent(new Event("touched-changed",{bubbles:!0,composed:!0}))}_onDirtyChanged(){this.dispatchEvent(new Event("dirty-changed",{bubbles:!0,composed:!0}))}_showFeedbackConditionFor(t,i){return i.touched&&i.dirty||i.prefilled||i.submitted}get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,submitted:this.submitted,touched:this.touched,dirty:this.dirty,filled:this.filled,prefilled:this.prefilled}}},la=ie(ju);class oi extends Mt(la(na($s(si(pn(G)))))){firstUpdated(e){super.firstUpdated(e),this._initialModelValue=this.modelValue}connectedCallback(){super.connectedCallback(),this._onChange=this._onChange.bind(this),this._inputNode.addEventListener("change",this._onChange),this.classList.add("form-field")}disconnectedCallback(){super.disconnectedCallback(),this._inputNode?.removeEventListener("change",this._onChange)}resetInteractionState(){super.resetInteractionState(),this.submitted=!1}reset(){this.modelValue=this._initialModelValue,this.resetInteractionState()}clear(){this.modelValue=""}_onChange(e){this.dispatchEvent(new Event("user-input-changed",{bubbles:!0}))}get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,focused:this.focused}}get _focusableNode(){return this._inputNode}}class Xi extends Array{_keys(){return Object.keys(this).filter(e=>Number.isNaN(Number(e)))}}const Wu=n=>class extends Ns(n){static get properties(){return{_isFormOrFieldset:{type:Boolean}}}constructor(){super(),this.formElements=new Xi,this._isFormOrFieldset=!1,this._onRequestToAddFormElement=this._onRequestToAddFormElement.bind(this),this._onRequestToChangeFormElementName=this._onRequestToChangeFormElementName.bind(this),this.addEventListener("form-element-register",this._onRequestToAddFormElement),this.addEventListener("form-element-name-changed",this._onRequestToChangeFormElementName),this.initComplete=new Promise((e,t)=>{this.__resolveInitComplete=e,this.__rejectInitComplete=t}),this.registrationComplete=new Promise((e,t)=>{this.__resolveRegistrationComplete=e,this.__rejectRegistrationComplete=t}),this.registrationComplete.done=!1,this.registrationComplete.then(()=>{this.registrationComplete.done=!0,this.__resolveInitComplete(void 0)},()=>{throw this.registrationComplete.done=!0,this.__rejectInitComplete(void 0),new Error("Registration could not finish. Please use await el.registrationComplete;")})}connectedCallback(){super.connectedCallback(),this._completeRegistration()}_completeRegistration(){Promise.resolve().then(()=>this.__resolveRegistrationComplete(void 0))}disconnectedCallback(){super.disconnectedCallback(),this.registrationComplete.done===!1&&Promise.resolve().then(()=>{Promise.resolve().then(()=>{this.__rejectRegistrationComplete()})})}isRegisteredFormElement(e){return this.formElements.some(t=>t===e)}addFormElement(e,t){if(e._parentFormGroup=this,t>=0?this.formElements.splice(t,0,e):this.formElements.push(e),this._isFormOrFieldset){const{name:i}=e;if(i===this.name)throw console.info("Error Node:",e),new TypeError(`You can not have the same name "${i}" as your parent`);if(i.substr(-2)==="[]")Array.isArray(this.formElements[i])||(this.formElements[i]=new Xi),t>0?this.formElements[i].splice(t,0,e):this.formElements[i].push(e);else if(!this.formElements[i])this.formElements[i]=e;else throw console.info("Error Node:",e),new TypeError(`Name "${i}" is already registered - if you want an array add [] to the end`)}}removeFormElement(e){const t=this.formElements.indexOf(e);if(t>-1&&this.formElements.splice(t,1),this._isFormOrFieldset){const{name:i}=e;if(i.substr(-2)==="[]"&&this.formElements[i]){const s=this.formElements[i].indexOf(e);s>-1&&this.formElements[i].splice(s,1)}else this.formElements[i]&&delete this.formElements[i]}}_onRequestToAddFormElement(e){const t=e.detail.element;if(t===this||this.isRegisteredFormElement(t))return;e.stopPropagation();let i=-1;if(this.formElements&&Array.isArray(this.formElements)){for(const[s,o]of this.formElements.entries())if(!(o.compareDocumentPosition(t)&Node.DOCUMENT_POSITION_FOLLOWING)){i=s;break}}this.addFormElement(t,i)}_onRequestToChangeFormElementName(e){const t=this.formElements[e.detail.oldName];t&&(this.formElements[e.detail.newName]=t,delete this.formElements[e.detail.oldName])}_onRequestToRemoveFormElement(e){const t=e.detail.element;t!==this&&this.isRegisteredFormElement(t)&&(e.stopPropagation(),this.removeFormElement(t))}},ca=ie(Wu),Ku=n=>class extends $s(na(Mt(n))){static get properties(){return{autocomplete:{type:String,reflect:!0}}}constructor(){super(),this.autocomplete=void 0}get _inputNode(){return super._inputNode}get selectionStart(){const t=this._inputNode;return t&&t.selectionStart?t.selectionStart:0}set selectionStart(t){const i=this._inputNode;i&&i.selectionStart&&(i.selectionStart=t)}get selectionEnd(){const t=this._inputNode;return t&&t.selectionEnd?t.selectionEnd:0}set selectionEnd(t){const i=this._inputNode;i&&i.selectionEnd&&(i.selectionEnd=t)}get value(){return this._inputNode&&this._inputNode.value||this.__value||""}set value(t){this._inputNode?(this._inputNode.value!==t&&this._setValueAndPreserveCaret(t),this.__value=void 0):this.__value=t}_setValueAndPreserveCaret(t){if(this.focused)try{if(!(this._inputNode instanceof HTMLSelectElement)){const i=this._inputNode.selectionStart;this._inputNode.value=t,this._inputNode.selectionStart=i,this._inputNode.selectionEnd=i}}catch{this._inputNode.value=t}else this._inputNode.value=t}_reflectBackFormattedValueToUser(){if(super._reflectBackFormattedValueToUser(),this._reflectBackOn()&&this.focused)try{this._inputNode.selectionStart=this._inputNode.value.length}catch{}}get _focusableNode(){return this._inputNode}},da=ie(Ku),Gu=n=>class extends ca(si(la(n))){static get properties(){return{multipleChoice:{type:Boolean,attribute:"multiple-choice"}}}get modelValue(){const t=this._getCheckedElements();return this.multipleChoice?t.map(i=>i.choiceValue):t[0]?t[0].choiceValue:""}set modelValue(t){const i=(s,o)=>typeof s.choiceValue=="object"?JSON.stringify(s.choiceValue)===JSON.stringify(t):s.choiceValue===o;this.__isInitialModelValue?this.registrationComplete.then(()=>{this.__isInitialModelValue=!1,this._setCheckedElements(t,i),this.requestUpdate("modelValue",this._oldModelValue)}):(this._setCheckedElements(t,i),this.requestUpdate("modelValue",this._oldModelValue)),this._oldModelValue=this.modelValue}get serializedValue(){const t=this._getCheckedElements();return this.multipleChoice?t.map(i=>i.serializedValue.value):t[0]?t[0].serializedValue.value:""}set serializedValue(t){const i=(s,o)=>s.serializedValue.value===o;this.__isInitialSerializedValue?this.registrationComplete.then(()=>{this.__isInitialSerializedValue=!1,this._setCheckedElements(t,i),this.requestUpdate("serializedValue")}):(this._setCheckedElements(t,i),this.requestUpdate("serializedValue"))}get formattedValue(){const t=this._getCheckedElements();return this.multipleChoice?t.map(i=>i.formattedValue):t[0]?t[0].formattedValue:""}set formattedValue(t){const i=(s,o)=>s.formattedValue===o;this.__isInitialFormattedValue?this.registrationComplete.then(()=>{this.__isInitialFormattedValue=!1,this._setCheckedElements(t,i)}):this._setCheckedElements(t,i)}get operationMode(){return this._repropagationRole==="choice-group"?"select":"enter"}constructor(){super(),this.multipleChoice=!1,this._repropagationRole="choice-group",this.__isInitialModelValue=!0,this.__isInitialSerializedValue=!0,this.__isInitialFormattedValue=!0}connectedCallback(){super.connectedCallback(),this.registrationComplete.then(()=>{this.__isInitialModelValue=!1,this.__isInitialSerializedValue=!1,this.__isInitialFormattedValue=!1})}_completeRegistration(){Promise.resolve().then(()=>super._completeRegistration())}updated(t){super.updated(t),t.has("name")&&this.name!==t.get("name")&&this.formElements.forEach(i=>{i.name=this.name})}addFormElement(t,i){this._throwWhenInvalidChildModelValue(t),t.name=this.name,super.addFormElement(t,i)}clear(){this.multipleChoice?this.modelValue=[]:this.modelValue=""}_triggerInitialModelValueChangedEvent(){this.registrationComplete.then(()=>{this._dispatchInitialModelValueChangedEvent()})}_getFromAllFormElementsFilter(t,i){return!0}_getFromAllFormElements(t,i){const s=i||this._getFromAllFormElementsFilter;return t==="modelValue"||t==="serializedValue"||t==="formattedValue"?this[t]:this.formElements.filter(o=>s(o,t)).map(o=>o.property)}_throwWhenInvalidChildModelValue(t){if(typeof t.modelValue.checked!="boolean"||!Object.prototype.hasOwnProperty.call(t.modelValue,"value"))throw new Error(`The ${this.tagName.toLowerCase()} name="${this.name}" does not allow to register ${t.tagName.toLowerCase()} with .modelValue="${t.modelValue}" - The modelValue should represent an Object { value: "foo", checked: false }`)}_isEmpty(){return this.multipleChoice?this.modelValue.length===0:typeof this.modelValue=="string"&&this.modelValue===""||this.modelValue===void 0||this.modelValue===null}_checkSingleChoiceElements(t){const{target:i}=t;if(i.checked===!1)return;const s=i.name;this.formElements.filter(o=>o.name===s).forEach(o=>{o!==i&&(o.checked=!1)})}_getCheckedElements(){return this.formElements.filter(t=>t.checked&&!t.disabled)}_setCheckedElements(t,i){if(t==null){this.formElements.forEach(s=>s.checked=!1);return}for(let s=0;s<this.formElements.length;s+=1)if(this.multipleChoice){let o=t.includes(this.formElements[s].modelValue.value);typeof this.formElements[s].modelValue.value=="object"&&(o=t.map(r=>JSON.stringify(r)).includes(JSON.stringify(this.formElements[s].modelValue.value))),this.formElements[s].checked=o}else i(this.formElements[s],t)?this.formElements[s].checked=!0:this.formElements[s].checked=!1}__setChoiceGroupTouched(){const t=this.modelValue;t!=null&&t!==this.__previousCheckedValue&&(this.touched=!0,this.__previousCheckedValue=t)}_onBeforeRepropagateChildrenValues(t){const i=t.detail&&t.detail.element||t.target;this.multipleChoice||!i.checked||(this.formElements.forEach(s=>{i.choiceValue!==s.choiceValue&&(s.checked=!1)}),this.__setChoiceGroupTouched(),this.requestUpdate("modelValue",this._oldModelValue),this._oldModelValue=this.modelValue)}_repropagationCondition(t){return!(this._repropagationRole==="choice-group"&&!this.multipleChoice&&!t.checked)}},Yu=ie(Gu),Xu=(n,e={})=>n.value!==e.value||n.checked!==e.checked,Ju=n=>class extends $s(n){static get properties(){return{checked:{type:Boolean,reflect:!0},disabled:{type:Boolean,reflect:!0},modelValue:{type:Object,hasChanged:Xu},choiceValue:{type:Object}}}get choiceValue(){return this.modelValue.value}set choiceValue(t){this.requestUpdate("choiceValue",this.choiceValue),this.modelValue.value!==t&&(this.modelValue={value:t,checked:this.modelValue.checked})}requestUpdate(t,i,s){super.requestUpdate(t,i,s),t==="modelValue"?this.modelValue.checked!==this.checked&&this.__syncModelCheckedToChecked(this.modelValue.checked):t==="checked"&&this.modelValue.checked!==this.checked&&this.__syncCheckedToModel(this.checked)}firstUpdated(t){super.firstUpdated(t),t.has("checked")&&this.__syncCheckedToInputElement()}updated(t){super.updated(t),t.has("modelValue")&&this.__syncCheckedToInputElement(),t.has("name")&&this._parentFormGroup&&this._parentFormGroup.name!==this.name&&this._syncNameToParentFormGroup()}constructor(){super(),this.modelValue={value:"",checked:!1},this.disabled=!1,this._preventDuplicateLabelClick=this._preventDuplicateLabelClick.bind(this),this._toggleChecked=this._toggleChecked.bind(this)}static get styles(){return[...super.styles||[],F`
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
        `]}render(){return x`
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
      `}_choiceGraphicTemplate(){return B}_afterTemplate(){return B}connectedCallback(){super.connectedCallback(),this._labelNode&&this._labelNode.addEventListener("click",this._preventDuplicateLabelClick),this.addEventListener("user-input-changed",this._toggleChecked)}disconnectedCallback(){super.disconnectedCallback(),this._labelNode&&this._labelNode.removeEventListener("click",this._preventDuplicateLabelClick),this.removeEventListener("user-input-changed",this._toggleChecked)}_preventDuplicateLabelClick(t){const i=s=>{s.stopImmediatePropagation(),this._inputNode.removeEventListener("click",i)};this._inputNode.addEventListener("click",i)}_toggleChecked(t){this.disabled||(this._isHandlingUserInput=!0,this.checked=!this.checked,this._isHandlingUserInput=!1)}_syncNameToParentFormGroup(){this._parentFormGroup.tagName.includes(this.tagName)&&(this.name=this._parentFormGroup?.name||"")}__syncModelCheckedToChecked(t){this.checked=t}__syncCheckedToModel(t){this.modelValue={value:this.choiceValue,checked:t}}__syncCheckedToInputElement(){this._inputNode&&(this._inputNode.checked=this.checked)}_proxyInputEvent(){}_onModelValueChanged({modelValue:t},i){let s;i&&i.modelValue&&(s=i.modelValue),this.constructor.elementProperties.get("modelValue").hasChanged(t,s)&&super._onModelValueChanged({modelValue:t})}parser(){return this.modelValue}formatter(t){return t&&t.value!==void 0?t.value:t}clear(){this.checked=!1}_isEmpty(){return!this.checked}_syncValueUpwards(){}},Os=ie(Ju);class Zu extends Bu{static get validatorName(){return"FormElementsHaveNoError"}execute(e,t,i){return i?.node._anyFormElementHasFeedbackFor("error")}static async getMessage(){return""}}const Qu=n=>class extends ca(Mt(si(hn(pn(n))))){static get properties(){return{submitted:{type:Boolean,reflect:!0},focused:{type:Boolean,reflect:!0},dirty:{type:Boolean,reflect:!0},touched:{type:Boolean,reflect:!0},prefilled:{type:Boolean,reflect:!0}}}get _inputNode(){return this}get modelValue(){return this._getFromAllFormElements("modelValue")}set modelValue(t){this.__isInitialModelValue?(this.__isInitialModelValue=!1,this.registrationComplete.then(()=>{this._setValueMapForAllFormElements("modelValue",t)})):this._setValueMapForAllFormElements("modelValue",t)}get serializedValue(){return this._getFromAllFormElements("serializedValue")}set serializedValue(t){this.__isInitialSerializedValue?(this.__isInitialSerializedValue=!1,this.registrationComplete.then(()=>{this._setValueMapForAllFormElements("serializedValue",t)})):this._setValueMapForAllFormElements("serializedValue",t)}get formattedValue(){return this._getFromAllFormElements("formattedValue")}set formattedValue(t){this._setValueMapForAllFormElements("formattedValue",t)}get prefilled(){return this._everyFormElementHas("prefilled")}constructor(){super(),this.value="",this.disabled=!1,this.submitted=!1,this.dirty=!1,this.touched=!1,this.focused=!1,this.__addedSubValidators=!1,this.__isInitialModelValue=!0,this.__isInitialSerializedValue=!0,this._checkForOutsideClick=this._checkForOutsideClick.bind(this),this.addEventListener("focusin",this._syncFocused),this.addEventListener("focusout",this._onFocusOut),this.addEventListener("dirty-changed",this._syncDirty),this.addEventListener("validate-performed",this.__onChildValidatePerformed),this.defaultValidators=[new Zu],this.__descriptionElementsInParentChain=new Set,this.__pendingValues={modelValue:{},serializedValue:{}}}connectedCallback(){super.connectedCallback(),this.setAttribute("role","group"),this.initComplete.then(()=>{this.__isInitialModelValue=!1,this.__isInitialSerializedValue=!1,this.__initInteractionStates()})}disconnectedCallback(){super.disconnectedCallback(),this.__hasActiveOutsideClickHandling&&(document.removeEventListener("click",this._checkForOutsideClick),this.__hasActiveOutsideClickHandling=!1),this.__descriptionElementsInParentChain.clear()}__initInteractionStates(){this.formElements.forEach(t=>{typeof t.initInteractionState=="function"&&t.initInteractionState()})}_triggerInitialModelValueChangedEvent(){this.registrationComplete.then(()=>{this._dispatchInitialModelValueChangedEvent()})}updated(t){super.updated(t),t.has("disabled")&&(this.disabled?this.__requestChildrenToBeDisabled():this.__retractRequestChildrenToBeDisabled()),t.has("focused")&&this.focused===!0&&this.__setupOutsideClickHandling()}__setupOutsideClickHandling(){this.__hasActiveOutsideClickHandling||(document.addEventListener("click",this._checkForOutsideClick),this.__hasActiveOutsideClickHandling=!0)}_checkForOutsideClick(t){!this.contains(t.target)&&(this.touched=!0)}__requestChildrenToBeDisabled(){this.formElements.forEach(t=>{t.makeRequestToBeDisabled&&t.makeRequestToBeDisabled()})}__retractRequestChildrenToBeDisabled(){this.formElements.forEach(t=>{t.retractRequestToBeDisabled&&t.retractRequestToBeDisabled()})}_inputGroupTemplate(){return x`
        <div class="input-group">
          <slot></slot>
        </div>
      `}submitGroup(){this.submitted=!0,this.formElements.forEach(t=>{typeof t.submitGroup=="function"?t.submitGroup():t.submitted=!0})}resetGroup(){this.formElements.forEach(t=>{typeof t.resetGroup=="function"?t.resetGroup():typeof t.reset=="function"&&t.reset()}),this.resetInteractionState()}clearGroup(){this.formElements.forEach(t=>{typeof t.clearGroup=="function"?t.clearGroup():typeof t.clear=="function"&&t.clear()}),this.resetInteractionState()}resetInteractionState(){this.submitted=!1,this.touched=!1,this.dirty=!1,this.formElements.forEach(t=>{typeof t.resetInteractionState=="function"&&t.resetInteractionState()})}_getFromAllFormElementsFilter(t,i){return!t.disabled}_getFromAllFormElements(t,i){const s={},o=i||this._getFromAllFormElementsFilter;return this.formElements._keys().forEach(r=>{const a=this.formElements[r];a instanceof Xi?s[r]=a.filter(l=>o(l,t)).map(l=>l[t]):o(a,t)&&(typeof a._getFromAllFormElements=="function"?s[r]=a._getFromAllFormElements(t):s[r]=a[t])}),s}_setValueForAllFormElements(t,i){this.formElements.forEach(s=>{s[t]=i})}_setValueMapForAllFormElements(t,i){i&&typeof i=="object"&&Object.keys(i).forEach(s=>{Array.isArray(this.formElements[s])&&this.formElements[s].forEach((o,r)=>{o[t]=i[s][r]}),this.formElements[s]?this.formElements[s][t]=i[s]:this.__pendingValues[t][s]=i[s]})}_anyFormElementHas(t){return Object.keys(this.formElements).some(i=>Array.isArray(this.formElements[i])?this.formElements[i].some(s=>!!s[t]):!!this.formElements[i][t])}_anyFormElementHasFeedbackFor(t){return Object.keys(this.formElements).some(i=>Array.isArray(this.formElements[i])?this.formElements[i].some(s=>!!(s.hasFeedbackFor&&s.hasFeedbackFor.includes(t))):!!(this.formElements[i].hasFeedbackFor&&this.formElements[i].hasFeedbackFor.includes(t)))}_everyFormElementHas(t){return Object.keys(this.formElements).every(i=>Array.isArray(this.formElements[i])?this.formElements[i].every(s=>!!s[t]):!!this.formElements[i][t])}__onChildValidatePerformed(t){t&&this.isRegisteredFormElement(t.target)&&this.validate()}_syncFocused(){this.focused=this._anyFormElementHas("focused")}_onFocusOut(t){const i=this.formElements[this.formElements.length-1];t.target===i&&(this.touched=!0),this.focused=!1}_syncDirty(){this.dirty=this._anyFormElementHas("dirty")}__storeAllDescriptionElementsInParentChain(){let i=this;for(;i;){const s=i._getAriaDescriptionElements();aa(s,{reverse:!0}).forEach(r=>{r.getAttribute("slot")==="feedback"&&this.__descriptionElementsInParentChain.add(r)}),i=i._parentFormGroup}}__linkParentMessages(t){this.__descriptionElementsInParentChain.forEach(i=>{typeof t.addToAriaDescribedBy=="function"&&t.addToAriaDescribedBy(i,{reorder:!1})})}__unlinkParentMessages(t){this.__descriptionElementsInParentChain.forEach(i=>{typeof t.removeFromAriaDescribedBy=="function"&&t.removeFromAriaDescribedBy(i)})}addFormElement(t,i){if(super.addFormElement(t,i),this.disabled&&t.makeRequestToBeDisabled(),this.__descriptionElementsInParentChain.size||this.__storeAllDescriptionElementsInParentChain(),this.__linkParentMessages(t),this.validate({clearCurrentResult:!0}),!t.modelValue){const s=this.__pendingValues;s.modelValue&&s.modelValue[t.name]?t.modelValue=s.modelValue[t.name]:s.serializedValue&&s.serializedValue[t.name]&&(t.serializedValue=s.serializedValue[t.name])}}get _initialModelValue(){return this._getFromAllFormElements("_initialModelValue")}removeFormElement(t){super.removeFormElement(t),this.validate({clearCurrentResult:!0}),typeof t.removeFromAriaLabelledBy=="function"&&this._labelNode&&t.removeFromAriaLabelledBy(this._labelNode,{reorder:!1}),this.__unlinkParentMessages(t)}_isEmpty(){return this.formElements.every(t=>t._isEmpty?.())}},eh=ie(Qu);class Ls extends da(oi){static get properties(){return{readOnly:{type:Boolean,attribute:"readonly",reflect:!0},type:{type:String,reflect:!0},placeholder:{type:String,reflect:!0}}}get slots(){return{...super.slots,input:()=>{const e=document.createElement("input"),t=this.getAttribute("value");return t&&e.setAttribute("value",t),e}}}get _inputNode(){return super._inputNode}constructor(){super(),this.readOnly=!1,this.type="text",this.placeholder=""}requestUpdate(e,t,i){super.requestUpdate(e,t,i),e==="readOnly"&&this.__delegateReadOnly()}firstUpdated(e){super.firstUpdated(e),this.__delegateReadOnly()}updated(e){super.updated(e),e.has("type")&&(this._inputNode.type=this.type),e.has("placeholder")&&(this._inputNode.placeholder=this.placeholder),e.has("disabled")&&(this._inputNode.disabled=this.disabled,this.validate()),e.has("name")&&(this._inputNode.name=this.name),e.has("autocomplete")&&(this._inputNode.autocomplete=this.autocomplete)}__delegateReadOnly(){this._inputNode&&(this._inputNode.readOnly=this.readOnly)}}var No=class extends Ls{static get styles(){return[...super.styles,Ts,au]}connectedCallback(){if(super.connectedCallback(),this._inputNode&&this.size){let e=parseInt(this.size,10);e>0&&(this._inputNode.size=e)}}};D([w({type:Number,reflect:!0})],No.prototype,"size",void 0),customElements.get("craft-input")||customElements.define("craft-input",No);var th=class extends Event{constructor(){super("wa-load",{bubbles:!0,cancelable:!1,composed:!0})}};var Ji="";function nh(n){Ji=n}function ih(){if(!Ji){const n=document.querySelector("[data-fa-kit-code]");n&&nh(n.getAttribute("data-fa-kit-code")||"")}return Ji}var Ve="7.0.1";function sh(n,e,t){const i=ih(),s=i.length>0;let o="solid";return e==="notdog"?(t==="solid"&&(o="solid"),t==="duo-solid"&&(o="duo-solid"),`https://ka-p.fontawesome.com/releases/v${Ve}/svgs/notdog-${o}/${n}.svg?token=${encodeURIComponent(i)}`):e==="chisel"?`https://ka-p.fontawesome.com/releases/v${Ve}/svgs/chisel-regular/${n}.svg?token=${encodeURIComponent(i)}`:e==="etch"?`https://ka-p.fontawesome.com/releases/v${Ve}/svgs/etch-solid/${n}.svg?token=${encodeURIComponent(i)}`:e==="jelly"?(t==="regular"&&(o="regular"),t==="duo-regular"&&(o="duo-regular"),t==="fill-regular"&&(o="fill-regular"),`https://ka-p.fontawesome.com/releases/v${Ve}/svgs/jelly-${o}/${n}.svg?token=${encodeURIComponent(i)}`):e==="slab"?((t==="solid"||t==="regular")&&(o="regular"),t==="press-regular"&&(o="press-regular"),`https://ka-p.fontawesome.com/releases/v${Ve}/svgs/slab-${o}/${n}.svg?token=${encodeURIComponent(i)}`):e==="thumbprint"?`https://ka-p.fontawesome.com/releases/v${Ve}/svgs/thumbprint-light/${n}.svg?token=${encodeURIComponent(i)}`:e==="whiteboard"?`https://ka-p.fontawesome.com/releases/v${Ve}/svgs/whiteboard-semibold/${n}.svg?token=${encodeURIComponent(i)}`:(e==="classic"&&(t==="thin"&&(o="thin"),t==="light"&&(o="light"),t==="regular"&&(o="regular"),t==="solid"&&(o="solid")),e==="sharp"&&(t==="thin"&&(o="sharp-thin"),t==="light"&&(o="sharp-light"),t==="regular"&&(o="sharp-regular"),t==="solid"&&(o="sharp-solid")),e==="duotone"&&(t==="thin"&&(o="duotone-thin"),t==="light"&&(o="duotone-light"),t==="regular"&&(o="duotone-regular"),t==="solid"&&(o="duotone")),e==="sharp-duotone"&&(t==="thin"&&(o="sharp-duotone-thin"),t==="light"&&(o="sharp-duotone-light"),t==="regular"&&(o="sharp-duotone-regular"),t==="solid"&&(o="sharp-duotone-solid")),e==="brands"&&(o="brands"),s?`https://ka-p.fontawesome.com/releases/v${Ve}/svgs/${o}/${n}.svg?token=${encodeURIComponent(i)}`:`https://ka-f.fontawesome.com/releases/v${Ve}/svgs/${o}/${n}.svg`)}var oh={name:"default",resolver:(n,e="classic",t="solid")=>sh(n,e,t),mutator:(n,e)=>{if(e?.family&&!n.hasAttribute("data-duotone-initialized")){const{family:t,variant:i}=e;if(t==="duotone"||t==="sharp-duotone"||t==="notdog"&&i==="duo-solid"||t==="jelly"&&i==="duo-regular"||t==="thumbprint"){const s=[...n.querySelectorAll("path")],o=s.find(a=>!a.hasAttribute("opacity")),r=s.find(a=>a.hasAttribute("opacity"));if(!o||!r)return;if(o.setAttribute("data-duotone-primary",""),r.setAttribute("data-duotone-secondary",""),e.swapOpacity&&o&&r){const a=r.getAttribute("opacity")||"0.4";o.style.setProperty("--path-opacity",a),r.style.setProperty("--path-opacity","1")}n.setAttribute("data-duotone-initialized","")}}}},rh=oh;function ah(n){return`data:image/svg+xml,${encodeURIComponent(n)}`}var _i={solid:{check:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M434.8 70.1c14.3 10.4 17.5 30.4 7.1 44.7l-256 352c-5.5 7.6-14 12.3-23.4 13.1s-18.5-2.7-25.1-9.3l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l101.5 101.5 234-321.7c10.4-14.3 30.4-17.5 44.7-7.1z"/></svg>',"chevron-down":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M201.4 406.6c12.5 12.5 32.8 12.5 45.3 0l192-192c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L224 338.7 54.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l192 192z"/></svg>',"chevron-left":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l192 192c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L77.3 256 246.6 86.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-192 192z"/></svg>',"chevron-right":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M311.1 233.4c12.5 12.5 12.5 32.8 0 45.3l-192 192c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L243.2 256 73.9 86.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l192 192z"/></svg>',circle:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0z"/></svg>',eyedropper:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M341.6 29.2l-101.6 101.6-9.4-9.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3l-9.4-9.4 101.6-101.6c39-39 39-102.2 0-141.1s-102.2-39-141.1 0zM55.4 323.3c-15 15-23.4 35.4-23.4 56.6l0 42.4-26.6 39.9c-8.5 12.7-6.8 29.6 4 40.4s27.7 12.5 40.4 4l39.9-26.6 42.4 0c21.2 0 41.6-8.4 56.6-23.4l109.4-109.4-45.3-45.3-109.4 109.4c-3 3-7.1 4.7-11.3 4.7l-36.1 0 0-36.1c0-4.2 1.7-8.3 4.7-11.3l109.4-109.4-45.3-45.3-109.4 109.4z"/></svg>',"grip-vertical":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M128 40c0-22.1-17.9-40-40-40L40 0C17.9 0 0 17.9 0 40L0 88c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zm0 192c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zM0 424l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zM320 40c0-22.1-17.9-40-40-40L232 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zM192 232l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zM320 424c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48z"/></svg>',indeterminate:'<svg part="indeterminate-icon" class="icon" viewBox="0 0 16 16"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" stroke-linecap="round"><g stroke="currentColor" stroke-width="2"><g transform="translate(2.285714 6.857143)"><path d="M10.2857143,1.14285714 L1.14285714,1.14285714"/></g></g></g></svg>',minus:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M0 256c0-17.7 14.3-32 32-32l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 288c-17.7 0-32-14.3-32-32z"/></svg>',pause:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M48 32C21.5 32 0 53.5 0 80L0 432c0 26.5 21.5 48 48 48l64 0c26.5 0 48-21.5 48-48l0-352c0-26.5-21.5-48-48-48L48 32zm224 0c-26.5 0-48 21.5-48 48l0 352c0 26.5 21.5 48 48 48l64 0c26.5 0 48-21.5 48-48l0-352c0-26.5-21.5-48-48-48l-64 0z"/></svg>',play:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M91.2 36.9c-12.4-6.8-27.4-6.5-39.6 .7S32 57.9 32 72l0 368c0 14.1 7.5 27.2 19.6 34.4s27.2 7.5 39.6 .7l336-184c12.8-7 20.8-20.5 20.8-35.1s-8-28.1-20.8-35.1l-336-184z"/></svg>',star:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M309.5-18.9c-4.1-8-12.4-13.1-21.4-13.1s-17.3 5.1-21.4 13.1L193.1 125.3 33.2 150.7c-8.9 1.4-16.3 7.7-19.1 16.3s-.5 18 5.8 24.4l114.4 114.5-25.2 159.9c-1.4 8.9 2.3 17.9 9.6 23.2s16.9 6.1 25 2L288.1 417.6 432.4 491c8 4.1 17.7 3.3 25-2s11-14.2 9.6-23.2L441.7 305.9 556.1 191.4c6.4-6.4 8.6-15.8 5.8-24.4s-10.1-14.9-19.1-16.3L383 125.3 309.5-18.9z"/></svg>',user:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M224 248a120 120 0 1 0 0-240 120 120 0 1 0 0 240zm-29.7 56C95.8 304 16 383.8 16 482.3 16 498.7 29.3 512 45.7 512l356.6 0c16.4 0 29.7-13.3 29.7-29.7 0-98.5-79.8-178.3-178.3-178.3l-59.4 0z"/></svg>',xmark:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M55.1 73.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L147.2 256 9.9 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192.5 301.3 329.9 438.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.8 256 375.1 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192.5 210.7 55.1 73.4z"/></svg>'},regular:{"circle-question":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M464 256a208 208 0 1 0 -416 0 208 208 0 1 0 416 0zM0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0zm256-80c-17.7 0-32 14.3-32 32 0 13.3-10.7 24-24 24s-24-10.7-24-24c0-44.2 35.8-80 80-80s80 35.8 80 80c0 47.2-36 67.2-56 74.5l0 3.8c0 13.3-10.7 24-24 24s-24-10.7-24-24l0-8.1c0-20.5 14.8-35.2 30.1-40.2 6.4-2.1 13.2-5.5 18.2-10.3 4.3-4.2 7.7-10 7.7-19.6 0-17.7-14.3-32-32-32zM224 368a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>',"circle-xmark":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M256 48a208 208 0 1 1 0 416 208 208 0 1 1 0-416zm0 464a256 256 0 1 0 0-512 256 256 0 1 0 0 512zM167 167c-9.4 9.4-9.4 24.6 0 33.9l55 55-55 55c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l55-55 55 55c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-55-55 55-55c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-55 55-55-55c-9.4-9.4-24.6-9.4-33.9 0z"/></svg>',copy:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M384 336l-192 0c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l133.5 0c4.2 0 8.3 1.7 11.3 4.7l58.5 58.5c3 3 4.7 7.1 4.7 11.3L400 320c0 8.8-7.2 16-16 16zM192 384l192 0c35.3 0 64-28.7 64-64l0-197.5c0-17-6.7-33.3-18.7-45.3L370.7 18.7C358.7 6.7 342.5 0 325.5 0L192 0c-35.3 0-64 28.7-64 64l0 256c0 35.3 28.7 64 64 64zM64 128c-35.3 0-64 28.7-64 64L0 448c0 35.3 28.7 64 64 64l192 0c35.3 0 64-28.7 64-64l0-16-48 0 0 16c0 8.8-7.2 16-16 16L64 464c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l16 0 0-48-16 0z"/></svg>',eye:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M288 80C222.8 80 169.2 109.6 128.1 147.7 89.6 183.5 63 226 49.4 256 63 286 89.6 328.5 128.1 364.3 169.2 402.4 222.8 432 288 432s118.8-29.6 159.9-67.7C486.4 328.5 513 286 526.6 256 513 226 486.4 183.5 447.9 147.7 406.8 109.6 353.2 80 288 80zM95.4 112.6C142.5 68.8 207.2 32 288 32s145.5 36.8 192.6 80.6c46.8 43.5 78.1 95.4 93 131.1 3.3 7.9 3.3 16.7 0 24.6-14.9 35.7-46.2 87.7-93 131.1-47.1 43.7-111.8 80.6-192.6 80.6S142.5 443.2 95.4 399.4c-46.8-43.5-78.1-95.4-93-131.1-3.3-7.9-3.3-16.7 0-24.6 14.9-35.7 46.2-87.7 93-131.1zM288 336c44.2 0 80-35.8 80-80 0-29.6-16.1-55.5-40-69.3-1.4 59.7-49.6 107.9-109.3 109.3 13.8 23.9 39.7 40 69.3 40zm-79.6-88.4c2.5 .3 5 .4 7.6 .4 35.3 0 64-28.7 64-64 0-2.6-.2-5.1-.4-7.6-37.4 3.9-67.2 33.7-71.1 71.1zm45.6-115c10.8-3 22.2-4.5 33.9-4.5 8.8 0 17.5 .9 25.8 2.6 .3 .1 .5 .1 .8 .2 57.9 12.2 101.4 63.7 101.4 125.2 0 70.7-57.3 128-128 128-61.6 0-113-43.5-125.2-101.4-1.8-8.6-2.8-17.5-2.8-26.6 0-11 1.4-21.8 4-32 .2-.7 .3-1.3 .5-1.9 11.9-43.4 46.1-77.6 89.5-89.5z"/></svg>',"eye-slash":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M41-24.9c-9.4-9.4-24.6-9.4-33.9 0S-2.3-.3 7 9.1l528 528c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-96.4-96.4c2.7-2.4 5.4-4.8 8-7.2 46.8-43.5 78.1-95.4 93-131.1 3.3-7.9 3.3-16.7 0-24.6-14.9-35.7-46.2-87.7-93-131.1-47.1-43.7-111.8-80.6-192.6-80.6-56.8 0-105.6 18.2-146 44.2L41-24.9zM176.9 111.1c32.1-18.9 69.2-31.1 111.1-31.1 65.2 0 118.8 29.6 159.9 67.7 38.5 35.7 65.1 78.3 78.6 108.3-13.6 30-40.2 72.5-78.6 108.3-3.1 2.8-6.2 5.6-9.4 8.4L393.8 328c14-20.5 22.2-45.3 22.2-72 0-70.7-57.3-128-128-128-26.7 0-51.5 8.2-72 22.2l-39.1-39.1zm182 182l-108-108c11.1-5.8 23.7-9.1 37.1-9.1 44.2 0 80 35.8 80 80 0 13.4-3.3 26-9.1 37.1zM103.4 173.2l-34-34c-32.6 36.8-55 75.8-66.9 104.5-3.3 7.9-3.3 16.7 0 24.6 14.9 35.7 46.2 87.7 93 131.1 47.1 43.7 111.8 80.6 192.6 80.6 37.3 0 71.2-7.9 101.5-20.6L352.2 422c-20 6.4-41.4 10-64.2 10-65.2 0-118.8-29.6-159.9-67.7-38.5-35.7-65.1-78.3-78.6-108.3 10.4-23.1 28.6-53.6 54-82.8z"/></svg>',star:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M288.1-32c9 0 17.3 5.1 21.4 13.1L383 125.3 542.9 150.7c8.9 1.4 16.3 7.7 19.1 16.3s.5 18-5.8 24.4L441.7 305.9 467 465.8c1.4 8.9-2.3 17.9-9.6 23.2s-17 6.1-25 2L288.1 417.6 143.8 491c-8 4.1-17.7 3.3-25-2s-11-14.2-9.6-23.2L134.4 305.9 20 191.4c-6.4-6.4-8.6-15.8-5.8-24.4s10.1-14.9 19.1-16.3l159.9-25.4 73.6-144.2c4.1-8 12.4-13.1 21.4-13.1zm0 76.8L230.3 158c-3.5 6.8-10 11.6-17.6 12.8l-125.5 20 89.8 89.9c5.4 5.4 7.9 13.1 6.7 20.7l-19.8 125.5 113.3-57.6c6.8-3.5 14.9-3.5 21.8 0l113.3 57.6-19.8-125.5c-1.2-7.6 1.3-15.3 6.7-20.7l89.8-89.9-125.5-20c-7.6-1.2-14.1-6-17.6-12.8L288.1 44.8z"/></svg>'}},lh={name:"system",resolver:(n,e="classic",t="solid")=>{let s=_i[t][n]??_i.regular[n]??_i.regular["circle-question"];return s?ah(s):""}},ch=lh;var dh="classic",uh=[rh,ch],Zi=[];function hh(n){Zi.push(n)}function ph(n){Zi=Zi.filter(e=>e!==n)}function wi(n){return uh.find(e=>e.name===n)}function fh(){return dh}var mh=class extends Event{constructor(){super("wa-error",{bubbles:!0,cancelable:!1,composed:!0})}},bh=`:host {
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
`,Vt=Symbol(),_n=Symbol(),Ei,xi=new Map,he=class extends ge{constructor(){super(...arguments),this.svg=null,this.autoWidth=!1,this.swapOpacity=!1,this.label="",this.library="default",this.resolveIcon=async(n,e)=>{let t;if(e?.spriteSheet){this.hasUpdated||await this.updateComplete,this.svg=x`<svg part="svg">
        <use part="use" href="${n}"></use>
      </svg>`,await this.updateComplete;const i=this.shadowRoot.querySelector("[part='svg']");return typeof e.mutator=="function"&&e.mutator(i,this),this.svg}try{if(t=await fetch(n,{mode:"cors"}),!t.ok)return t.status===410?Vt:_n}catch{return _n}try{const i=document.createElement("div");i.innerHTML=await t.text();const s=i.firstElementChild;if(s?.tagName?.toLowerCase()!=="svg")return Vt;Ei||(Ei=new DOMParser);const r=Ei.parseFromString(s.outerHTML,"text/html").body.querySelector("svg");return r?(r.part.add("svg"),document.adoptNode(r)):Vt}catch{return Vt}}}connectedCallback(){super.connectedCallback(),hh(this)}firstUpdated(n){super.firstUpdated(n),this.setIcon()}disconnectedCallback(){super.disconnectedCallback(),ph(this)}getIconSource(){const n=wi(this.library),e=this.family||fh();return this.name&&n?{url:n.resolver(this.name,e,this.variant,this.autoWidth),fromLibrary:!0}:{url:this.src,fromLibrary:!1}}handleLabelChange(){typeof this.label=="string"&&this.label.length>0?(this.setAttribute("role","img"),this.setAttribute("aria-label",this.label),this.removeAttribute("aria-hidden")):(this.removeAttribute("role"),this.removeAttribute("aria-label"),this.setAttribute("aria-hidden","true"))}async setIcon(){const{url:n,fromLibrary:e}=this.getIconSource(),t=e?wi(this.library):void 0;if(!n){this.svg=null;return}let i=xi.get(n);i||(i=this.resolveIcon(n,t),xi.set(n,i));const s=await i;if(s===_n&&xi.delete(n),n===this.getIconSource().url){if(Zr(s)){this.svg=s;return}switch(s){case _n:case Vt:this.svg=null,this.dispatchEvent(new mh);break;default:this.svg=s.cloneNode(!0),t?.mutator?.(this.svg,this),this.dispatchEvent(new th)}}}updated(n){super.updated(n);const e=wi(this.library),t=this.shadowRoot?.querySelector("svg");t&&e?.mutator?.(t,this)}render(){return this.hasUpdated?this.svg:x`<svg part="svg" fill="currentColor" width="16" height="16"></svg>`}};he.css=bh;v([be()],he.prototype,"svg",2);v([w({reflect:!0})],he.prototype,"name",2);v([w({reflect:!0})],he.prototype,"family",2);v([w({reflect:!0})],he.prototype,"variant",2);v([w({attribute:"auto-width",type:Boolean,reflect:!0})],he.prototype,"autoWidth",2);v([w({attribute:"swap-opacity",type:Boolean,reflect:!0})],he.prototype,"swapOpacity",2);v([w()],he.prototype,"src",2);v([w()],he.prototype,"label",2);v([w({reflect:!0})],he.prototype,"library",2);v([Ee("label")],he.prototype,"handleLabelChange",1);v([Ee(["family","name","library","variant","src","autoWidth","swapOpacity"])],he.prototype,"setIcon",1);he=v([ke("wa-icon")],he);var gh=F``,vh=class extends he{static get styles(){return[he.styles,gh]}};customElements.get("craft-icon")||customElements.define("craft-icon",vh);var $o=class extends Ls{static get styles(){return[...super.styles,Ts,F`
        .input-group__container {
          position: relative;
        }

        .input-group__suffix {
          position: absolute;
          inset-inline-end: var(--c-input-spacing-inline);
          inset-block-start: 50%;
          transform: translateY(calc(-50%));
        }
      `]}constructor(){super(),this._visible=!1,this.reveal=()=>{this._visible=!this._visible,this.type=this._visible?"text":"password"},this.renderSuffix=()=>x`
      <craft-button
        type="button"
        icon
        size="small"
        variant="plain"
        @click="${this.reveal}"
        appearance="plain"
      >
        <span class="icon"
          >${this._visible?x`<craft-icon name="eye-slash"></craft-icon>`:x`<craft-icon name="eye"></craft-icon>`}
        </span>
      </craft-button>
    `,this.type="password"}get slots(){return{...super.slots,suffix:()=>({template:this.renderSuffix()})}}};D([be()],$o.prototype,"_visible",void 0),customElements.get("craft-input-password")||customElements.define("craft-input-password",$o);var yh=F`
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
`,wn=class extends G{constructor(...e){super(...e),this.size="",this.variant=""}render(){let e=!!this.querySelector('[slot="prefix"]'),t=!!this.querySelector('[slot="suffix"]');return x`
      <div
        class="${Ie({chip:!0,"chip--small":this.size==="small","chip--medium":this.size==="medium","chip--large":this.size==="large","chip--plain":this.variant==="plain"})}"
      >
        ${e?x`<div class="chip__prefix"><slot name="prefix"></slot></div>`:B}
        <div class="chip__body">
          <slot></slot>
        </div>
        ${t?x`<div class="chip__suffix"><slot name="suffix"></slot></div>`:B}
      </div>
    `}};wn.styles=[yh],D([w()],wn.prototype,"size",void 0),D([w()],wn.prototype,"variant",void 0),customElements.get("craft-chip")||customElements.define("craft-chip",wn);var _h=F`
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
`,En=class extends G{constructor(...e){super(...e),this.label=null,this.status=null}getLabel(){return!this.label&&this.status?`Status: ${this.status}`:this.label}render(){return x`
      <span
        class="${Ie({status:!0,"status--live":this.status==="live","status--enabled":this.status==="enabled","status--pending":this.status==="pending","status--expired":this.status==="expired","status--disabled":this.status==="disabled"})}"
        role="img"
        aria-label="${this.getLabel()}"
      ></span>
    `}};En.styles=[_h],D([w()],En.prototype,"label",void 0),D([w()],En.prototype,"status",void 0),customElements.get("craft-status")||customElements.define("craft-status",En);var Yt=new Map;function wh(n){var e=Yt.get(n);e&&e.destroy()}function Eh(n){var e=Yt.get(n);e&&e.update()}var Ut=null;typeof window>"u"?((Ut=function(n){return n}).destroy=function(n){return n},Ut.update=function(n){return n}):((Ut=function(n,e){return n&&Array.prototype.forEach.call(n.length?n:[n],function(t){return(function(i){if(i&&i.nodeName&&i.nodeName==="TEXTAREA"&&!Yt.has(i)){var s,o=null,r=window.getComputedStyle(i),a=(s=i.value,function(){d({testForHeightReduction:s===""||!i.value.startsWith(s),restoreTextAlign:null}),s=i.value}),l=(function(p){i.removeEventListener("autosize:destroy",l),i.removeEventListener("autosize:update",c),i.removeEventListener("input",a),window.removeEventListener("resize",c),Object.keys(p).forEach(function(m){return i.style[m]=p[m]}),Yt.delete(i)}).bind(i,{height:i.style.height,resize:i.style.resize,textAlign:i.style.textAlign,overflowY:i.style.overflowY,overflowX:i.style.overflowX,wordWrap:i.style.wordWrap});i.addEventListener("autosize:destroy",l),i.addEventListener("autosize:update",c),i.addEventListener("input",a),window.addEventListener("resize",c),i.style.overflowX="hidden",i.style.wordWrap="break-word",Yt.set(i,{destroy:l,update:c}),c()}function d(p){var m,b,f=p.restoreTextAlign,g=f===void 0?null:f,y=p.testForHeightReduction,E=y===void 0||y,C=r.overflowY;if(i.scrollHeight!==0&&(r.resize==="vertical"?i.style.resize="none":r.resize==="both"&&(i.style.resize="horizontal"),E&&(m=(function(S){for(var L=[];S&&S.parentNode&&S.parentNode instanceof Element;)S.parentNode.scrollTop&&L.push([S.parentNode,S.parentNode.scrollTop]),S=S.parentNode;return function(){return L.forEach(function(U){var P=U[0],Z=U[1];P.style.scrollBehavior="auto",P.scrollTop=Z,P.style.scrollBehavior=null})}})(i),i.style.height=""),b=r.boxSizing==="content-box"?i.scrollHeight-(parseFloat(r.paddingTop)+parseFloat(r.paddingBottom)):i.scrollHeight+parseFloat(r.borderTopWidth)+parseFloat(r.borderBottomWidth),r.maxHeight!=="none"&&b>parseFloat(r.maxHeight)?(r.overflowY==="hidden"&&(i.style.overflow="scroll"),b=parseFloat(r.maxHeight)):r.overflowY!=="hidden"&&(i.style.overflow="hidden"),i.style.height=b+"px",g&&(i.style.textAlign=g),m&&m(),o!==b&&(i.dispatchEvent(new Event("autosize:resized",{bubbles:!0})),o=b),C!==r.overflow&&!g)){var A=r.textAlign;r.overflow==="hidden"&&(i.style.textAlign=A==="start"?"end":"start"),d({restoreTextAlign:A,testForHeightReduction:!0})}}function c(){d({testForHeightReduction:!0,restoreTextAlign:null})}})(t)}),n}).destroy=function(n){return n&&Array.prototype.forEach.call(n.length?n:[n],wh),n},Ut.update=function(n){return n&&Array.prototype.forEach.call(n.length?n:[n],Eh),n});var ki=Ut;class xh extends oi{get _inputNode(){return Array.from(this.children).find(e=>e.slot==="input")}}class kh extends da(xh){static get properties(){return{maxRows:{type:Number,attribute:"max-rows"},rows:{type:Number,reflect:!0},readOnly:{type:Boolean,attribute:"readonly",reflect:!0},placeholder:{type:String,reflect:!0}}}get slots(){return{...super.slots,input:()=>{const e=document.createElement("textarea");return e.style.resize!==void 0&&(e.style.resize="none"),e}}}constructor(){super(),this.rows=2,this.maxRows=6,this.readOnly=!1,this.placeholder=""}connectedCallback(){super.connectedCallback(),this.__initializeAutoresize(),this.__intersectionObserver=new IntersectionObserver(()=>this.resizeTextarea()),this.__intersectionObserver.observe(this)}updated(e){if(super.updated(e),e.has("name")&&(this._inputNode.name=this.name),e.has("autocomplete")&&(this._inputNode.autocomplete=this.autocomplete),e.has("disabled")&&(this._inputNode.disabled=this.disabled,this.validate()),e.has("rows")){const t=this._inputNode;t&&(t.rows=this.rows)}if(e.has("readOnly")){const t=this._inputNode;t&&(t.readOnly=this.readOnly)}if(e.has("placeholder")){const t=this._inputNode;t&&(t.placeholder=this.placeholder)}e.has("modelValue")&&this.resizeTextarea(),(e.has("maxRows")||e.has("rows"))&&this.setTextareaMaxHeight()}disconnectedCallback(){super.disconnectedCallback(),ki.destroy(this._inputNode)}setTextareaMaxHeight(){const{value:e}=this._inputNode;this._inputNode.value="",this.resizeTextarea();const t=window.getComputedStyle(this._inputNode,null),i=parseFloat(t.lineHeight)||parseFloat(t.height)/this.rows,s=parseFloat(t.paddingTop)+parseFloat(t.paddingBottom),o=parseFloat(t.borderTopWidth)+parseFloat(t.borderBottomWidth),r=t.boxSizing==="border-box"?s+o:0;this._inputNode.style.maxHeight=`${i*this.maxRows+r}px`,this._inputNode.value=e,this.resizeTextarea()}static get styles(){return[...super.styles,F`
        .input-group__container > .input-group__input ::slotted(.form-control) {
          box-sizing: content-box;
          overflow-x: hidden; /* for FF adds height to the TextArea to reserve place for scroll-bars */
        }

        /* Workaround https://bugzilla.mozilla.org/show_bug.cgi?id=1739079 */
        :host([disabled]) ::slotted(textarea) {
          user-select: none;
        }
      `]}get updateComplete(){return this.__textareaUpdateComplete?Promise.all([this.__textareaUpdateComplete,super.updateComplete]):super.updateComplete}resizeTextarea(){ki.update(this._inputNode)}__initializeAutoresize(){this.__shady_native_contains?this.__textareaUpdateComplete=this.__waitForTextareaRenderedInRealDOM().then(()=>{this.__startAutoresize(),this.__textareaUpdateComplete=null}):this.__startAutoresize()}async __waitForTextareaRenderedInRealDOM(){let e=3;for(;e!==0&&!this.__shady_native_contains(this._inputNode);)await new Promise(t=>setTimeout(t)),e-=1}__startAutoresize(){ki(this._inputNode),this.setTextareaMaxHeight()}}var Ch=F`
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
`,Sh=class extends kh{static get styles(){return[...super.styles,Ts,Ch]}};customElements.get("craft-textarea")||customElements.define("craft-textarea",Sh);var Ah=F`
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
`,Oo=class extends G{render(){return x`<slot></slot>`}};Oo.styles=[Ah],customElements.get("craft-button-group")||customElements.define("craft-button-group",Oo);class Th extends oi{static get properties(){return{autocomplete:{type:String}}}constructor(){super(),this.autocomplete=void 0}get _inputNode(){return Array.from(this.children).find(e=>e.slot==="input")}}class Nh extends Th{get operationMode(){return"select"}connectedCallback(){super.connectedCallback(),this._inputNode.addEventListener("change",this._proxyChangeEvent),this.__selectObserver=new MutationObserver(()=>{this._syncValueUpwards(),this._calculateValues({source:"model"})}),this.__selectObserver.observe(this._inputNode,{attributes:!0,childList:!0,subtree:!0})}updated(e){super.updated(e),e.has("disabled")&&(this._inputNode.disabled=this.disabled,this.validate()),e.has("name")&&(this._inputNode.name=this.name),e.has("autocomplete")&&(this._inputNode.autocomplete=this.autocomplete)}disconnectedCallback(){super.disconnectedCallback(),this._inputNode.removeEventListener("change",this._proxyChangeEvent),this.__selectObserver?.disconnect()}formatter(e){const t=Array.from(this._inputNode.options).find(i=>i.value===e);return t?t.text:""}_reflectBackFormattedValueToUser(){this._reflectBackOn()&&(this.value=typeof this.modelValue<"u"?this.modelValue:"")}_proxyChangeEvent(){this.dispatchEvent(new CustomEvent("user-input-changed",{bubbles:!0,composed:!0}))}}var $h=F`
  ${ta}

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
    ${ea}
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
`,Oh=class extends Nh{static get styles(){return[...super.styles,$h]}_inputGroupInputTemplate(){return x`
      <div class="input-group__input">
        <slot name="input"></slot>
        <craft-icon
          class="indicator"
          name="chevron-down"
          style="font-size: 0.8em"
        ></craft-icon>
      </div>
    `}};customElements.get("craft-select")||customElements.define("craft-select",Oh);class Lo extends hn(Os(Ns(pn(G)))){static get properties(){return{active:{type:Boolean,reflect:!0}}}static get styles(){return[F`
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
      `]}get slots(){return{}}constructor(){super(),this.active=!1,this.__onClick=this.__onClick.bind(this),this.__registerEventListeners()}requestUpdate(e,t,i){super.requestUpdate(e,t,i),e==="active"&&this.active!==t&&this.dispatchEvent(new Event("active-changed",{bubbles:!0}))}updated(e){super.updated(e),e.has("checked")&&this.setAttribute("aria-selected",`${this.checked}`),e.has("disabled")&&this.setAttribute("aria-disabled",`${this.disabled}`)}render(){return x`
      <div class="choice-field__label">
        <slot></slot>
      </div>
    `}connectedCallback(){super.connectedCallback(),this.setAttribute("role","option")}__registerEventListeners(){this.addEventListener("click",this.__onClick)}__unRegisterEventListeners(){this.removeEventListener("click",this.__onClick)}__onClick(){if(this.disabled)return;const e=this._parentFormGroup;this._isHandlingUserInput=!0,e&&e.multipleChoice?(this.checked=!this.checked,this.active=!this.active):(this.checked=!0,this.active=!0),this._isHandlingUserInput=!1}}var Lh=F`
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
`,Fh=class extends Lo{static get styles(){return[...Lo.styles,Lh]}};customElements.get("craft-option")||customElements.define("craft-option",Fh);var ua=`@layer wa-utilities {
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
`;var Rh=class extends Event{constructor(n){super("wa-select",{bubbles:!0,cancelable:!0,composed:!0}),this.detail=n}};function*ha(n=document.activeElement){n!=null&&(yield n,"shadowRoot"in n&&n.shadowRoot&&n.shadowRoot.mode!=="closed"&&(yield*ha(n.shadowRoot.activeElement)))}var Mh=`:host {
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
`,Ci=new Set,ue=class extends ge{constructor(){super(...arguments),this.submenuCleanups=new Map,this.localize=new Ot(this),this.userTypedQuery="",this.openSubmenuStack=[],this.open=!1,this.size="medium",this.placement="bottom-start",this.distance=0,this.skidding=0,this.handleDocumentKeyDown=async n=>{const e=this.localize.dir()==="rtl";if(n.key==="Escape"){const c=this.getTrigger();n.preventDefault(),n.stopPropagation(),this.open=!1,c?.focus();return}const t=[...ha()].find(c=>c.localName==="wa-dropdown-item"),i=t?.localName==="wa-dropdown-item",s=this.getCurrentSubmenuItem(),o=!!s;let r,a,l;o?(r=this.getSubmenuItems(s),a=r.find(c=>c.active||c===t),l=a?r.indexOf(a):-1):(r=this.getItems(),a=r.find(c=>c.active||c===t),l=a?r.indexOf(a):-1);let d;if(n.key==="ArrowUp"&&(n.preventDefault(),n.stopPropagation(),l>0?d=r[l-1]:d=r[r.length-1]),n.key==="ArrowDown"&&(n.preventDefault(),n.stopPropagation(),l!==-1&&l<r.length-1?d=r[l+1]:d=r[0]),n.key===(e?"ArrowLeft":"ArrowRight")&&i&&a&&a.hasSubmenu){n.preventDefault(),n.stopPropagation(),a.submenuOpen=!0,this.addToSubmenuStack(a),setTimeout(()=>{const c=this.getSubmenuItems(a);c.length>0&&(c.forEach((p,m)=>p.active=m===0),c[0].focus())},0);return}if(n.key===(e?"ArrowRight":"ArrowLeft")&&o){n.preventDefault(),n.stopPropagation();const c=this.removeFromSubmenuStack();c&&(c.submenuOpen=!1,setTimeout(()=>{c.focus(),c.active=!0,(c.slot==="submenu"?this.getSubmenuItems(c.parentElement):this.getItems()).forEach(m=>{m!==c&&(m.active=!1)})},0));return}if((n.key==="Home"||n.key==="End")&&(n.preventDefault(),n.stopPropagation(),d=n.key==="Home"?r[0]:r[r.length-1]),n.key==="Tab"&&await this.hideMenu(),n.key.length===1&&!(n.metaKey||n.ctrlKey||n.altKey)&&!(n.key===" "&&this.userTypedQuery==="")&&(clearTimeout(this.userTypedTimeout),this.userTypedTimeout=setTimeout(()=>{this.userTypedQuery=""},1e3),this.userTypedQuery+=n.key,r.some(c=>{const p=(c.textContent||"").trim().toLowerCase(),m=this.userTypedQuery.trim().toLowerCase();return p.startsWith(m)?(d=c,!0):!1})),d){n.preventDefault(),n.stopPropagation(),r.forEach(c=>c.active=c===d),d.focus();return}(n.key==="Enter"||n.key===" "&&this.userTypedQuery==="")&&i&&a&&(n.preventDefault(),n.stopPropagation(),a.hasSubmenu?(a.submenuOpen=!0,this.addToSubmenuStack(a),setTimeout(()=>{const c=this.getSubmenuItems(a);c.length>0&&(c.forEach((p,m)=>p.active=m===0),c[0].focus())},0)):this.makeSelection(a))},this.handleDocumentPointerDown=n=>{n.composedPath().some(i=>i instanceof HTMLElement?i===this||i.closest('wa-dropdown, [part="submenu"]'):!1)||(this.open=!1)},this.handleGlobalMouseMove=n=>{const e=this.getCurrentSubmenuItem();if(!e?.submenuOpen||!e.submenuElement)return;const t=e.submenuElement.getBoundingClientRect(),i=this.localize.dir()==="rtl",s=i?t.right:t.left,o=i?Math.max(n.clientX,s):Math.min(n.clientX,s),r=Math.max(t.top,Math.min(n.clientY,t.bottom));e.submenuElement.style.setProperty("--safe-triangle-cursor-x",`${o}px`),e.submenuElement.style.setProperty("--safe-triangle-cursor-y",`${r}px`);const a=e.matches(":hover"),l=e.submenuElement?.matches(":hover")||!!n.composedPath().find(d=>d instanceof HTMLElement&&d.closest('[part="submenu"]')===e.submenuElement);!a&&!l&&setTimeout(()=>{!e.matches(":hover")&&!e.submenuElement?.matches(":hover")&&(e.submenuOpen=!1)},100)}}disconnectedCallback(){super.disconnectedCallback(),clearInterval(this.userTypedTimeout),this.closeAllSubmenus(),this.submenuCleanups.forEach(n=>n()),this.submenuCleanups.clear(),document.removeEventListener("mousemove",this.handleGlobalMouseMove)}firstUpdated(){this.syncAriaAttributes()}async updated(n){n.has("open")&&(this.customStates.set("open",this.open),this.open?await this.showMenu():(this.closeAllSubmenus(),await this.hideMenu())),n.has("size")&&this.syncItemSizes()}getItems(n=!1){const e=this.defaultSlot.assignedElements({flatten:!0}).filter(t=>t.localName==="wa-dropdown-item");return n?e:e.filter(t=>!t.disabled)}getSubmenuItems(n,e=!1){const t=n.shadowRoot?.querySelector('slot[name="submenu"]')||n.querySelector('slot[name="submenu"]');if(!t)return[];const i=t.assignedElements({flatten:!0}).filter(s=>s.localName==="wa-dropdown-item");return e?i:i.filter(s=>!s.disabled)}syncItemSizes(){this.defaultSlot.assignedElements({flatten:!0}).filter(e=>e.localName==="wa-dropdown-item").forEach(e=>e.size=this.size)}addToSubmenuStack(n){const e=this.openSubmenuStack.indexOf(n);e!==-1?this.openSubmenuStack=this.openSubmenuStack.slice(0,e+1):this.openSubmenuStack.push(n)}removeFromSubmenuStack(){return this.openSubmenuStack.pop()}getCurrentSubmenuItem(){return this.openSubmenuStack.length>0?this.openSubmenuStack[this.openSubmenuStack.length-1]:void 0}closeAllSubmenus(){this.getItems(!0).forEach(e=>{e.submenuOpen=!1}),this.openSubmenuStack=[]}closeSiblingSubmenus(n){const e=n.closest('wa-dropdown-item:not([slot="submenu"])');let t;e?t=this.getSubmenuItems(e,!0):t=this.getItems(!0),t.forEach(i=>{i!==n&&i.submenuOpen&&(i.submenuOpen=!1)}),this.openSubmenuStack.includes(n)||this.openSubmenuStack.push(n)}getTrigger(){return this.querySelector('[slot="trigger"]')}async showMenu(){if(!this.getTrigger())return;const e=new un;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}Ci.forEach(i=>i.open=!1),this.popup.active=!0,this.open=!0,Ci.add(this),this.syncAriaAttributes(),document.addEventListener("keydown",this.handleDocumentKeyDown),document.addEventListener("pointerdown",this.handleDocumentPointerDown),document.addEventListener("mousemove",this.handleGlobalMouseMove),this.menu.classList.remove("hide"),await de(this.menu,"show");const t=this.getItems();t.length>0&&(t.forEach((i,s)=>i.active=s===0),t[0].focus()),this.dispatchEvent(new cn)}async hideMenu(){const n=new dn({source:this});if(this.dispatchEvent(n),n.defaultPrevented){this.open=!0;return}this.open=!1,Ci.delete(this),this.syncAriaAttributes(),document.removeEventListener("keydown",this.handleDocumentKeyDown),document.removeEventListener("pointerdown",this.handleDocumentPointerDown),document.removeEventListener("mousemove",this.handleGlobalMouseMove),this.menu.classList.remove("show"),await de(this.menu,"hide"),this.popup.active=this.open,this.dispatchEvent(new ln)}handleMenuClick(n){const e=n.target.closest("wa-dropdown-item");if(!(!e||e.disabled)){if(e.hasSubmenu){e.submenuOpen||(this.closeSiblingSubmenus(e),this.addToSubmenuStack(e),e.submenuOpen=!0),n.stopPropagation();return}this.makeSelection(e)}}async handleMenuSlotChange(){const n=this.getItems(!0);await Promise.all(n.map(i=>i.updateComplete)),this.syncItemSizes();const e=n.some(i=>i.type==="checkbox"),t=n.some(i=>i.hasSubmenu);n.forEach((i,s)=>{i.active=s===0,i.checkboxAdjacent=e,i.submenuAdjacent=t})}handleTriggerClick(){this.open=!this.open}handleSubmenuOpening(n){const e=n.detail.item;this.closeSiblingSubmenus(e),this.addToSubmenuStack(e),this.setupSubmenuPosition(e),this.processSubmenuItems(e)}setupSubmenuPosition(n){if(!n.submenuElement)return;this.cleanupSubmenuPosition(n);const e=Ur(n,n.submenuElement,()=>{this.positionSubmenu(n),this.updateSafeTriangleCoordinates(n)});this.submenuCleanups.set(n,e);const t=n.submenuElement.querySelector('slot[name="submenu"]');t&&(t.removeEventListener("slotchange",ue.handleSubmenuSlotChange),t.addEventListener("slotchange",ue.handleSubmenuSlotChange),ue.handleSubmenuSlotChange({target:t}))}static handleSubmenuSlotChange(n){const e=n.target;if(!e)return;const t=e.assignedElements().filter(o=>o.localName==="wa-dropdown-item");if(t.length===0)return;const i=t.some(o=>o.hasSubmenu),s=t.some(o=>o.type==="checkbox");t.forEach(o=>{o.submenuAdjacent=i,o.checkboxAdjacent=s})}processSubmenuItems(n){if(!n.submenuElement)return;const e=this.getSubmenuItems(n,!0),t=e.some(i=>i.hasSubmenu);e.forEach(i=>{i.submenuAdjacent=t})}cleanupSubmenuPosition(n){const e=this.submenuCleanups.get(n);e&&(e(),this.submenuCleanups.delete(n))}positionSubmenu(n){if(!n.submenuElement)return;const t=this.localize.dir()==="rtl"?"left-start":"right-start";Wr(n,n.submenuElement,{placement:t,middleware:[Hr({mainAxis:0,crossAxis:-5}),jr({fallbackStrategy:"bestFit"}),qr({padding:8})]}).then(({x:i,y:s,placement:o})=>{n.submenuElement.setAttribute("data-placement",o),Object.assign(n.submenuElement.style,{left:`${i}px`,top:`${s}px`})})}updateSafeTriangleCoordinates(n){if(!n.submenuElement||!n.submenuOpen)return;if(document.activeElement?.matches(":focus-visible")){n.submenuElement.style.setProperty("--safe-triangle-visible","none");return}n.submenuElement.style.setProperty("--safe-triangle-visible","block");const t=n.submenuElement.getBoundingClientRect(),i=this.localize.dir()==="rtl";n.submenuElement.style.setProperty("--safe-triangle-submenu-start-x",`${i?t.right:t.left}px`),n.submenuElement.style.setProperty("--safe-triangle-submenu-start-y",`${t.top}px`),n.submenuElement.style.setProperty("--safe-triangle-submenu-end-x",`${i?t.right:t.left}px`),n.submenuElement.style.setProperty("--safe-triangle-submenu-end-y",`${t.bottom}px`)}makeSelection(n){const e=this.getTrigger();if(n.disabled)return;n.type==="checkbox"&&(n.checked=!n.checked);const t=new Rh({item:n});this.dispatchEvent(t),t.defaultPrevented||(this.open=!1,e?.focus())}async syncAriaAttributes(){const n=this.getTrigger();let e;n&&(n.localName==="wa-button"?(await customElements.whenDefined("wa-button"),await n.updateComplete,e=n.shadowRoot.querySelector('[part="base"]')):e=n,e.hasAttribute("id")||e.setAttribute("id",As("wa-dropdown-trigger-")),e.setAttribute("aria-haspopup","menu"),e.setAttribute("aria-expanded",this.open?"true":"false"),this.menu.setAttribute("aria-expanded","false"))}render(){let n=this.hasUpdated?this.popup.active:this.open;return x`
      <wa-popup
        placement=${this.placement}
        distance=${this.distance}
        skidding=${this.skidding}
        ?active=${n}
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
    `}};ue.css=[ua,Mh];v([Q("slot:not([name])")],ue.prototype,"defaultSlot",2);v([Q("#menu")],ue.prototype,"menu",2);v([Q("wa-popup")],ue.prototype,"popup",2);v([w({type:Boolean,reflect:!0})],ue.prototype,"open",2);v([w({reflect:!0})],ue.prototype,"size",2);v([w({reflect:!0})],ue.prototype,"placement",2);v([w({type:Number})],ue.prototype,"distance",2);v([w({type:Number})],ue.prototype,"skidding",2);ue=v([ke("wa-dropdown")],ue);var ri=class{constructor(n,...e){this.slotNames=[],this.handleSlotChange=t=>{const i=t.target;(this.slotNames.includes("[default]")&&!i.name||i.name&&this.slotNames.includes(i.name))&&this.host.requestUpdate()},(this.host=n).addController(this),this.slotNames=e}hasDefaultSlot(){return[...this.host.childNodes].some(n=>{if(n.nodeType===Node.TEXT_NODE&&n.textContent.trim()!=="")return!0;if(n.nodeType===Node.ELEMENT_NODE){const e=n;if(e.tagName.toLowerCase()==="wa-visually-hidden")return!1;if(!e.hasAttribute("slot"))return!0}return!1})}hasNamedSlot(n){return this.host.querySelector(`:scope > [slot="${n}"]`)!==null}test(n){return n==="[default]"?this.hasDefaultSlot():this.hasNamedSlot(n)}hostConnected(){this.host.shadowRoot.addEventListener("slotchange",this.handleSlotChange)}hostDisconnected(){this.host.shadowRoot.removeEventListener("slotchange",this.handleSlotChange)}};var Dh=`:host {
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
`,le=class extends ge{constructor(){super(...arguments),this.hasSlotController=new ri(this,"[default]","start","end"),this.active=!1,this.variant="default",this.size="medium",this.checkboxAdjacent=!1,this.submenuAdjacent=!1,this.type="normal",this.checked=!1,this.disabled=!1,this.submenuOpen=!1,this.hasSubmenu=!1,this.handleSlotChange=()=>{this.hasSubmenu=this.hasSlotController.test("submenu"),this.updateHasSubmenuState(),this.hasSubmenu?(this.setAttribute("aria-haspopup","menu"),this.setAttribute("aria-expanded",this.submenuOpen?"true":"false")):(this.removeAttribute("aria-haspopup"),this.removeAttribute("aria-expanded"))}}connectedCallback(){super.connectedCallback(),this.addEventListener("mouseenter",this.handleMouseEnter.bind(this)),this.shadowRoot.addEventListener("slotchange",this.handleSlotChange)}disconnectedCallback(){super.disconnectedCallback(),this.closeSubmenu(),this.removeEventListener("mouseenter",this.handleMouseEnter),this.shadowRoot.removeEventListener("slotchange",this.handleSlotChange)}firstUpdated(){this.setAttribute("tabindex","-1"),this.hasSubmenu=this.hasSlotController.test("submenu"),this.updateHasSubmenuState()}updated(n){n.has("active")&&(this.setAttribute("tabindex",this.active?"0":"-1"),this.customStates.set("active",this.active)),n.has("checked")&&(this.setAttribute("aria-checked",this.checked?"true":"false"),this.customStates.set("checked",this.checked)),n.has("disabled")&&(this.setAttribute("aria-disabled",this.disabled?"true":"false"),this.customStates.set("disabled",this.disabled)),n.has("type")&&(this.type==="checkbox"?this.setAttribute("role","menuitemcheckbox"):this.setAttribute("role","menuitem")),n.has("submenuOpen")&&(this.customStates.set("submenu-open",this.submenuOpen),this.submenuOpen?this.openSubmenu():this.closeSubmenu())}updateHasSubmenuState(){this.customStates.set("has-submenu",this.hasSubmenu)}async openSubmenu(){!this.hasSubmenu||!this.submenuElement||(this.notifyParentOfOpening(),this.submenuElement.showPopover(),this.submenuElement.hidden=!1,this.submenuElement.setAttribute("data-visible",""),this.submenuOpen=!0,this.setAttribute("aria-expanded","true"),await de(this.submenuElement,"show"),setTimeout(()=>{const n=this.getSubmenuItems();n.length>0&&(n.forEach((e,t)=>e.active=t===0),n[0].focus())},0))}notifyParentOfOpening(){const n=new CustomEvent("submenu-opening",{bubbles:!0,composed:!0,detail:{item:this}});this.dispatchEvent(n);const e=this.parentElement;e&&[...e.children].filter(i=>i!==this&&i.localName==="wa-dropdown-item"&&i.getAttribute("slot")===this.getAttribute("slot")&&i.submenuOpen).forEach(i=>{i.submenuOpen=!1})}async closeSubmenu(){!this.hasSubmenu||!this.submenuElement||(this.submenuOpen=!1,this.setAttribute("aria-expanded","false"),this.submenuElement.hidden||(await de(this.submenuElement,"hide"),this.submenuElement.hidden=!0,this.submenuElement.removeAttribute("data-visible"),this.submenuElement.hidePopover()))}getSubmenuItems(){return[...this.children].filter(n=>n.localName==="wa-dropdown-item"&&n.getAttribute("slot")==="submenu"&&!n.hasAttribute("disabled"))}handleMouseEnter(){this.hasSubmenu&&!this.disabled&&(this.notifyParentOfOpening(),this.submenuOpen=!0)}render(){return x`
      ${this.type==="checkbox"?x`
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

      ${this.hasSubmenu?x`
            <wa-icon
              id="submenu-indicator"
              part="submenu-icon"
              exportparts="svg:submenu-icon__svg"
              library="system"
              name="chevron-right"
            ></wa-icon>
          `:""}
      ${this.hasSubmenu?x`
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
    `}};le.css=Dh;v([Q("#submenu")],le.prototype,"submenuElement",2);v([w({type:Boolean})],le.prototype,"active",2);v([w({reflect:!0})],le.prototype,"variant",2);v([w({reflect:!0})],le.prototype,"size",2);v([w({attribute:"checkbox-adjacent",type:Boolean,reflect:!0})],le.prototype,"checkboxAdjacent",2);v([w({attribute:"submenu-adjacent",type:Boolean,reflect:!0})],le.prototype,"submenuAdjacent",2);v([w()],le.prototype,"value",2);v([w({reflect:!0})],le.prototype,"type",2);v([w({type:Boolean})],le.prototype,"checked",2);v([w({type:Boolean,reflect:!0})],le.prototype,"disabled",2);v([w({type:Boolean,reflect:!0})],le.prototype,"submenuOpen",2);v([be()],le.prototype,"hasSubmenu",2);le=v([ke("wa-dropdown-item")],le);var Ih=class extends ue{static get styles(){return[ue.styles,F`
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
      `]}},Ph=class extends le{static get styles(){return[le.styles,F`
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
      `]}};customElements.get("craft-dropdown")||customElements.define("craft-dropdown",Ih),customElements.get("craft-dropdown-item")||customElements.define("craft-dropdown-item",Ph);function zh({el:n,uid:e}){n.setAttribute("id",`panel-${e}`),n.setAttribute("role","tabpanel"),n.setAttribute("aria-labelledby",`button-${e}`),n.hasAttribute("tabindex")||n.setAttribute("tabindex","0")}function Vh(n){n.setAttribute("selected","true")}function Fo(n){n.removeAttribute("selected")}function Bh({el:n,uid:e,clickHandler:t,keydownHandler:i,keyupHandler:s}){n.setAttribute("id",`button-${e}`),n.setAttribute("role","tab"),n.setAttribute("aria-controls",`panel-${e}`),n.addEventListener("click",t),n.addEventListener("keyup",s),n.addEventListener("keydown",i)}function Uh({el:n,clickHandler:e,keydownHandler:t,keyupHandler:i}){n.removeAttribute("id"),n.removeAttribute("role"),n.removeAttribute("aria-controls"),n.removeEventListener("click",e),n.removeEventListener("keyup",i),n.removeEventListener("keydown",t)}function Hh(n,e=!1){e&&n.focus(),n.setAttribute("selected","true"),n.setAttribute("aria-selected","true"),n.setAttribute("tabindex","0")}function Ro(n){n.removeAttribute("selected"),n.setAttribute("aria-selected","false"),n.setAttribute("tabindex","-1")}function qh(n){const e=n;switch(e.key){case"ArrowDown":case"ArrowRight":case"ArrowUp":case"ArrowLeft":case"Home":case"End":e.preventDefault()}}class jh extends G{static get properties(){return{selectedIndex:{type:Number,attribute:"selected-index",reflect:!0}}}static get styles(){return[F`
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
      `]}render(){return x`
      <div class="tabs__tab-group" role="tablist">
        <slot name="tab"></slot>
      </div>
      <div class="tabs__panels">
        <slot name="panel"></slot>
      </div>
    `}constructor(){super(),this.selectedIndex=0}firstUpdated(e){super.firstUpdated(e),this.__setupSlots(),this.tabs[0]?.disabled&&(this.selectedIndex=this.tabs.findIndex(t=>!t.disabled))}get tabs(){return Array.from(this.children).filter(e=>e.slot==="tab")}get panels(){return Array.from(this.children).filter(e=>e.slot==="panel")}static enabledWarnings=super.enabledWarnings?.filter(e=>e!=="change-in-update")||[];__setupSlots(){if(this.shadowRoot){const e=this.shadowRoot.querySelector("slot[name=tab]"),t=()=>{this.__cleanStore(),this.__setupStore(),this.__updateSelected(!1)};e&&e.addEventListener("slotchange",t)}}__setupStore(){this.__store=[],this.tabs.length!==this.panels.length&&console.warn(`The amount of tabs (${this.tabs.length}) doesn't match the amount of panels (${this.panels.length}).`),this.tabs.forEach((e,t)=>{const i=Qr(),s=this.panels[t],o={uid:i,el:e,button:e,panel:s,clickHandler:this.__createButtonClickHandler(t),keydownHandler:qh.bind(this),keyupHandler:this.__handleButtonKeyup.bind(this)};zh({...o,el:o.panel}),Bh(o),Fo(o.panel),Ro(o.button),this.__store&&this.__store.push(o)})}__cleanStore(){this.__store&&(this.__store.forEach(e=>{Uh(e)}),this.__store=[])}__getNextNotDisabledTab(e,t,i){let s=[];const o=e.filter((a,l)=>!a.disabled&&l>this.selectedIndex),r=e.filter((a,l)=>!a.disabled&&l<this.selectedIndex);return i==="right"?s=[...o,...r]:s=[...r.reverse(),...o.reverse()],s[0]}__getNextAvailableIndex(e,t){const i=this.tabs[this.selectedIndex];if(this.tabs.every(s=>!s.disabled))return e;if(t==="ArrowRight"||t==="ArrowDown"){const s=this.__getNextNotDisabledTab(this.tabs,i,"right");return this.tabs.findIndex(o=>s===o)}if(t==="ArrowLeft"||t==="ArrowUp"){const s=this.__getNextNotDisabledTab(this.tabs,i,"left");return this.tabs.findIndex(o=>s===o)}if(t==="Home")return this.tabs.findIndex(s=>!s.disabled);if(t==="End"){const s=this.tabs.map((o,r)=>({disabled:o.disabled,index:r})).filter(o=>!o.disabled);return s[s.length-1].index}return-1}__createButtonClickHandler(e){return()=>{this._setSelectedIndexWithFocus(e)}}__handleButtonKeyup(e){const t=e;if(typeof this.selectedIndex=="number")switch(t.key){case"ArrowDown":case"ArrowRight":this.selectedIndex+1>=this._pairCount?this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(0,t.key)):this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this.selectedIndex+1,t.key));break;case"ArrowUp":case"ArrowLeft":this.selectedIndex<=0?this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this._pairCount-1,t.key)):this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this.selectedIndex-1,t.key));break;case"Home":this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(0,t.key));break;case"End":this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this._pairCount-1,t.key));break}}get selectedIndex(){return this.__selectedIndex||0}set selectedIndex(e){if(e===this.__selectedIndex)return;const t=this.__selectedIndex;this.__selectedIndex=e,this.__updateSelected(!1),this.dispatchEvent(new Event("selected-changed")),this.requestUpdate("selectedIndex",t)}_setSelectedIndexWithFocus(e){if(e===-1)return;const t=this.__selectedIndex;this.__selectedIndex=e,this.__updateSelected(!0),this.dispatchEvent(new Event("selected-changed")),this.requestUpdate("selectedIndex",t)}get _pairCount(){return this.__store&&this.__store.length||0}__updateSelected(e=!1){if(!(this.__store&&typeof this.selectedIndex=="number"&&this.__store[this.selectedIndex]))return;const t=this.tabs.find(r=>r.hasAttribute("selected")),i=this.panels.find(r=>r.hasAttribute("selected"));t&&Ro(t),i&&Fo(i);const{button:s,panel:o}=this.__store[this.selectedIndex];s&&Hh(s,e),o&&Vh(o)}}var Wh=F`
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
`,Kh=class extends jh{static get styles(){return[...super.styles,Wh]}};customElements.get("craft-tabs")||customElements.define("craft-tabs",Kh);var Gh=F`
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
`,Si=class extends G{constructor(...e){super(...e),this.label=""}render(){let e=!!this.label||!!this.querySelector('[slot="header"]')||!!this.querySelector('[slot="label"]')||!!this.querySelector('[slot="actions"]'),t=!!this.querySelector('[slot="footer"]');return x`
      <div class="card">
        <div>
          ${e?x`<div class="card__header">
                <slot name="header">
                  <slot name="label" class="card__label" part="label"
                    >${this.label}</slot
                  >
                  <slot name="actions"></slot>
                </slot>
              </div>`:B}

          <div class="card__body">
            <slot></slot>
          </div>

          ${t?x`<div class="card__footer"><slot name="footer"></slot></div>`:B}
        </div>
      </div>
    `}};Si.styles=[Gh],D([w()],Si.prototype,"label",void 0),customElements.get("craft-card")||customElements.define("craft-card",Si);var Yh=F`
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
`,Mo=class extends G{render(){return x`<slot></slot> `}};Mo.styles=[Yh],customElements.get("craft-tab")||customElements.define("craft-tab",Mo);class pa extends Jr(G){static get properties(){return{checked:{type:Boolean,reflect:!0}}}static get styles(){return[F`
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
      `]}render(){return x`
      <div class="btn">
        <div class="switch-button__track"></div>
        <div class="switch-button__thumb"></div>
      </div>
    `}constructor(){super(),this.value="",this.checked=!1,this.__initialized=!1,this._toggleChecked=this._toggleChecked.bind(this),this.__handleKeydown=this._handleKeydown.bind(this),this.__handleKeyup=this._handleKeyup.bind(this)}connectedCallback(){super.connectedCallback(),this.setAttribute("role","switch"),this.setAttribute("aria-checked",`${this.checked}`),this.addEventListener("click",this._toggleChecked),this.addEventListener("keydown",this.__handleKeydown),this.addEventListener("keyup",this.__handleKeyup)}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener("click",this._toggleChecked),this.removeEventListener("keydown",this.__handleKeydown),this.removeEventListener("keyup",this.__handleKeyup)}_toggleChecked(){this.disabled||(this.focus(),this.checked=!this.checked)}__checkedStateChange(){this.dispatchEvent(new Event("checked-changed",{bubbles:!0})),this.setAttribute("aria-checked",`${this.checked}`)}_handleKeydown(e){e.key===" "&&e.preventDefault()}_handleKeyup(e){[" ","Enter"].includes(e.key)&&this._toggleChecked()}updated(e){super.updated(e),e.has("disabled")&&this.setAttribute("aria-disabled",`${this.disabled}`)}requestUpdate(e,t,i){super.requestUpdate(e,t,i),this.__initialized&&this.isConnected&&e==="checked"&&this.checked!==t&&!this.disabled&&this.__checkedStateChange()}firstUpdated(e){super.firstUpdated(e),this.__initialized=!0}}class Xh extends ra(Os(oi)){static get styles(){return[...super.styles,F`
        :host([hidden]) {
          display: none;
        }

        :host([disabled]) {
          color: #adadad;
        }
      `]}static get scopedElements(){return{...super.scopedElements,"lion-switch-button":pa}}get _inputNode(){return Array.from(this.children).find(e=>e.slot==="input")}get slots(){return{...super.slots,input:()=>{const e=this.createScopedElement("lion-switch-button");return e.setAttribute("data-tag-name","lion-switch-button"),e}}}render(){return x`
      <div class="form-field__group-one">${this._groupOneTemplate()}</div>
      <div class="form-field__group-two">${this._groupTwoTemplate()}</div>
    `}_groupOneTemplate(){return x`${this._labelTemplate()} ${this._helpTextTemplate()} ${this._feedbackTemplate()}`}_groupTwoTemplate(){return x`${this._inputGroupTemplate()}`}constructor(){super(),this.checked=!1,this.__handleButtonSwitchCheckedChanged=this.__handleButtonSwitchCheckedChanged.bind(this)}connectedCallback(){super.connectedCallback(),this.addEventListener("checked-changed",this.__handleButtonSwitchCheckedChanged),this._labelNode&&this._labelNode.addEventListener("click",this._toggleChecked),this._syncButtonSwitch()}disconnectedCallback(){super.disconnectedCallback(),this._inputNode&&this.removeEventListener("checked-changed",this.__handleButtonSwitchCheckedChanged),this._labelNode&&this._labelNode.removeEventListener("click",this._toggleChecked)}updated(e){super.updated(e),e.has("disabled")&&this._syncButtonSwitch()}_toggleChecked(e){e.preventDefault(),super._toggleChecked(e)}_isEmpty(){return!1}__handleButtonSwitchCheckedChanged(e){e.stopPropagation(),this._isHandlingUserInput=!0,this.checked=this._inputNode.checked,this._isHandlingUserInput=!1}_syncButtonSwitch(){this._inputNode.disabled=this.disabled}_onLabelClick(){this.disabled||this._inputNode.focus()}}var fa=class extends pa{static get styles(){return[...super.styles,F`
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
      `]}};customElements.get("craft-switch-button")||customElements.define("craft-switch-button",fa);var Jh=F`
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
`,Zh=class extends Xh{static get styles(){return[...super.styles,Jh]}get slots(){return{...super.slots,input:()=>{let e=this.createScopedElement("craft-switch-button");return e.setAttribute("data-tag-name","craft-switch-button"),e}}}static get scopedElements(){return{...super.scopedElements,"craft-switch-button":fa}}};customElements.get("craft-switch")||customElements.define("craft-switch",Zh);var Qh=F`
  .breadcrumbs {
    display: flex;
    align-items: center;
  }
`,Xe=class extends G{constructor(...e){super(...e),this.label="",this.items=[],this.visibleItems=0,this.firstRender=!0}getSeparator(){let e=this.separatorSlot.assignedElements({flatten:!0})[0].cloneNode(!0);return[e,...e.querySelectorAll("[id]")].forEach(t=>t.removeAttribute("id")),e.setAttribute("data-default",""),e.slot="separator",e}calculateBreadcrumbItemsWidth(){this.items=this.breadcrumbsElements.map((e,t)=>{let i=e.offsetWidth;return e.hasAttribute("hidden")&&(e.removeAttribute("hidden"),i=e.offsetWidth,e.setAttribute("hidden","")),{label:e.innerText,href:e.href,value:t.toString(),offsetWidth:i,isVisible:!0}})}async handleSlotChange(){let e=[...this.defaultSlot.assignedElements({flatten:!0})].filter(t=>t.tagName.toLowerCase()==="craft-breadcrumb-item");if(e.forEach((t,i)=>{let s=t.querySelector('[slot="separator"]');s===null?t.append(this.getSeparator()):s.hasAttribute("data-default")&&s.replaceWith(this.getSeparator()),i===e.length-1?t.setAttribute("aria-current","page"):t.removeAttribute("aria-current")}),this.breadcrumbsElements.length===0){this.items=[],this.visibleItems=0;return}await Promise.all(this.breadcrumbsElements.map(t=>t.updateComplete)),this.calculateBreadcrumbItemsWidth(),this.visibleItems=0,this.adjustOverflow()}connectedCallback(){super.connectedCallback(),this.hasAttribute("role")||this.setAttribute("role","navigation"),this.resizeObserver=new ResizeObserver(()=>{if(this.firstRender){this.firstRender=!1;return}this.adjustOverflow()}),this.resizeObserver.observe(this)}adjustOverflow(){let e=this.getBoundingClientRect().width;console.log({availableSpace:e})}disconnectedCallback(){this.resizeObserver?.unobserve(this),super.disconnectedCallback()}render(){return x`
      <nav class="breadcrumbs" aria-label="${this.label}">
        <slot @slotchange="${this.handleSlotChange}"></slot>
      </nav>

      <span hidden aria-hidden="true">
        <slot name="separator"><span class="separator">/</span></slot>
      </span>
    `}};Xe.styles=[Qh],D([Q("slot")],Xe.prototype,"defaultSlot",void 0),D([Q('slot[name="separator"]')],Xe.prototype,"separatorSlot",void 0),D([Cr({selector:"craft-breadcrumb-item"})],Xe.prototype,"breadcrumbsElements",void 0),D([w()],Xe.prototype,"label",void 0),D([be()],Xe.prototype,"items",void 0),D([be()],Xe.prototype,"visibleItems",void 0),customElements.get("craft-breadcrumbs")||customElements.define("craft-breadcrumbs",Xe);const Se=n=>n??B;var ep=`:host {
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
`,Ne=class extends ge{constructor(){super(...arguments),this.renderType="button",this.rel="noreferrer noopener"}setRenderType(){const n=this.defaultSlot.assignedElements({flatten:!0}).filter(e=>e.tagName.toLowerCase()==="wa-dropdown").length>0;if(this.href){this.renderType="link";return}if(n){this.renderType="dropdown";return}this.renderType="button"}hrefChanged(){this.setRenderType()}handleSlotChange(){this.setRenderType()}render(){return x`
      <span part="start" class="start">
        <slot name="start"></slot>
      </span>

      ${this.renderType==="link"?x`
            <a
              part="label"
              class="label label-link"
              href="${this.href}"
              target="${Se(this.target?this.target:void 0)}"
              rel=${Se(this.target?this.rel:void 0)}
            >
              <slot></slot>
            </a>
          `:""}
      ${this.renderType==="button"?x`
            <button part="label" type="button" class="label label-button">
              <slot @slotchange=${this.handleSlotChange}></slot>
            </button>
          `:""}
      ${this.renderType==="dropdown"?x`
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
    `}};Ne.css=ep;v([Q("slot:not([name])")],Ne.prototype,"defaultSlot",2);v([be()],Ne.prototype,"renderType",2);v([w()],Ne.prototype,"href",2);v([w()],Ne.prototype,"target",2);v([w()],Ne.prototype,"rel",2);v([Ee("href",{waitUntilFirstUpdate:!0})],Ne.prototype,"hrefChanged",1);Ne=v([ke("wa-breadcrumb-item")],Ne);var tp=class extends Ne{static get styles(){return[Ne.styles,F`
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
      `]}};customElements.get("craft-breadcrumb-item")||customElements.define("craft-breadcrumb-item",tp);var np=`:host {
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
`,Ai=new Set,re=class extends ge{constructor(){super(...arguments),this.anchor=null,this.placement="top",this.open=!1,this.distance=8,this.skidding=0,this.for=null,this.withoutArrow=!1,this.eventController=new AbortController,this.handleAnchorClick=()=>{this.open=!this.open},this.handleBodyClick=n=>{n.target.closest('[data-popover="close"]')&&(n.stopPropagation(),this.open=!1)},this.handleDocumentKeyDown=n=>{n.key==="Escape"&&(n.preventDefault(),this.open=!1,this.anchor&&typeof this.anchor.focus=="function"&&this.anchor.focus())},this.handleDocumentClick=n=>{const e=n.target;this.anchor&&n.composedPath().includes(this.anchor)||e.closest("wa-popover")!==this&&(this.open=!1)}}connectedCallback(){super.connectedCallback(),this.id||(this.id=As("wa-popover-"))}disconnectedCallback(){super.disconnectedCallback(),document.removeEventListener("keydown",this.handleDocumentKeyDown),this.eventController.abort()}firstUpdated(){this.open&&(this.dialog.show(),this.popup.active=!0,this.popup.reposition())}updated(n){n.has("open")&&this.customStates.set("open",this.open)}async handleOpenChange(){if(this.open){const n=new un;if(this.dispatchEvent(n),n.defaultPrevented){this.open=!1;return}Ai.forEach(e=>e.open=!1),document.addEventListener("keydown",this.handleDocumentKeyDown,{signal:this.eventController.signal}),document.addEventListener("click",this.handleDocumentClick,{signal:this.eventController.signal}),this.dialog.show(),this.popup.active=!0,Ai.add(this),requestAnimationFrame(()=>{const e=this.querySelector("[autofocus]");e&&typeof e.focus=="function"?e.focus():this.dialog.focus()}),await de(this.popup.popup,"show-with-scale"),this.popup.reposition(),this.dispatchEvent(new cn)}else{const n=new dn;if(this.dispatchEvent(n),n.defaultPrevented){this.open=!0;return}document.removeEventListener("keydown",this.handleDocumentKeyDown),document.removeEventListener("click",this.handleDocumentClick),Ai.delete(this),await de(this.popup.popup,"hide-with-scale"),this.popup.active=!1,this.dialog.close(),this.dispatchEvent(new ln)}}handleForChange(){const n=this.getRootNode();if(!n)return;const e=this.for?n.getElementById(this.for):null,t=this.anchor;if(e===t)return;const{signal:i}=this.eventController;e&&e.addEventListener("click",this.handleAnchorClick,{signal:i}),t&&t.removeEventListener("click",this.handleAnchorClick),this.anchor=e,this.for&&!e&&console.warn(`A popover was assigned to an element with an ID of "${this.for}" but the element could not be found.`,this)}async handleOptionsChange(){this.hasUpdated&&(await this.updateComplete,this.popup.reposition())}async show(){if(!this.open)return this.open=!0,zn(this,"wa-after-show")}async hide(){if(this.open)return this.open=!1,zn(this,"wa-after-hide")}render(){return x`
      <dialog part="dialog" class="dialog">
        <wa-popup
          part="popup"
          exportparts="
            popup:popup__popup,
            arrow:popup__arrow
          "
          class=${Ie({popover:!0,"popover-open":this.open})}
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
    `}};re.css=np;re.dependencies={"wa-popup":Y};v([Q("dialog")],re.prototype,"dialog",2);v([Q(".body")],re.prototype,"body",2);v([Q("wa-popup")],re.prototype,"popup",2);v([be()],re.prototype,"anchor",2);v([w()],re.prototype,"placement",2);v([w({type:Boolean,reflect:!0})],re.prototype,"open",2);v([w({type:Number})],re.prototype,"distance",2);v([w({type:Number})],re.prototype,"skidding",2);v([w()],re.prototype,"for",2);v([w({attribute:"without-arrow",type:Boolean,reflect:!0})],re.prototype,"withoutArrow",2);v([Ee("open",{waitUntilFirstUpdate:!0})],re.prototype,"handleOpenChange",1);v([Ee("for")],re.prototype,"handleForChange",1);v([Ee(["distance","placement","skidding"])],re.prototype,"handleOptionsChange",1);re=v([ke("wa-popover")],re);var ip=class extends re{static get styles(){return[re.styles,F`
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
      `]}};customElements.get("craft-popover")||customElements.define("craft-popover",ip);var Do=class extends G{render(){return x`
      <nav>
        <slot></slot>
      </nav>
    `}};Do.styles=F`
    :host {
      display: block;
    }

    nav {
      display: grid;
    }
  `,customElements.get("craft-navigation")||customElements.define("craft-navigation",Do);const ma="important",sp=" !"+ma,op=Cs(class extends Ss{constructor(n){if(super(n),n.type!==ks.ATTRIBUTE||n.name!=="style"||n.strings?.length>2)throw Error("The `styleMap` directive must be used in the `style` attribute and must be the only part in the attribute.")}render(n){return Object.keys(n).reduce(((e,t)=>{const i=n[t];return i==null?e:e+`${t=t.includes("-")?t:t.replace(/(?:^(webkit|moz|ms|o)|)(?=[A-Z])/g,"-$&").toLowerCase()}:${i};`}),"")}update(n,[e]){const{style:t}=n.element;if(this.ft===void 0)return this.ft=new Set(Object.keys(e)),this.render(e);for(const i of this.ft)e[i]==null&&(this.ft.delete(i),i.includes("-")?t.removeProperty(i):t[i]=null);for(const i in e){const s=e[i];if(s!=null){this.ft.add(i);const o=typeof s=="string"&&s.endsWith(sp);i.includes("-")||o?t.setProperty(i,o?s.slice(0,-11):s,o?ma:""):t[i]=s}}return Me}});var rp=F`
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
`,Fe=class extends G{constructor(){super(),this.active=!1,this.external=!1,this.indicator=!1,this.iconOnly=!1,this.subnavState="closed",this.id=this.id||Math.random().toString(36).substring(2,6)}connectedCallback(){super.connectedCallback(),this.subnavState=this.active?"open":"closed"}toggleSubnav(e){e.preventDefault(),e.stopPropagation(),this.subnavState=this.subnavState==="open"?"closed":"open"}renderIconItem(e){let t=`item-${this.id}`;return x`
      <a
        class="nav-item"
        id="${t}"
        href="${this.url}"
        aria-current="${this.active?"page":!1}"
      >
        <span class="nav-item__prefix">
          <slot name="prefix">
            <slot name="icon">
              ${this.icon?x` <craft-icon
                    name="${this.icon}"
                    class="nav-icon"
                  ></craft-icon>`:x` <span class="active-indicator"></span> `}
            </slot>
            ${this.indicator?x`<span class="indicator"></span>`:B}
          </slot>
        </span>

        <div class="nav-item__suffix">
          <slot name="suffix">
            ${e?x`
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
                `:B}
          </slot>
        </div>
      </a>
      <c-tooltip for="${t}" placement="right-start"
        ><slot></slot
      ></c-tooltip>
    `}renderItem(e){return x`
      <a
        class="nav-item"
        href="${this.url}"
        aria-current="${this.active?"page":!1}"
      >
        <span class="nav-item__prefix">
          <slot name="prefix">
            <slot name="icon">
              ${this.icon?x` <craft-icon
                    name="${this.icon}"
                    class="nav-icon"
                  ></craft-icon>`:x` <span class="active-indicator"></span> `}
            </slot>
            ${this.indicator?x`<span class="indicator"></span>`:B}
          </slot>
        </span>
        <slot></slot>

        <div class="nav-item__suffix">
          <slot name="suffix">
            ${e?x`
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
                `:B}
          </slot>
        </div>
      </a>
    `}render(){let e=!!this.querySelector('[slot="subnav"]');return x`
      ${this.iconOnly?this.renderIconItem(e):this.renderItem(e)}
      ${e?x`
            <div
              class="subnav"
              id="${this.id}-subnav"
              style="${op({display:this.subnavState==="open"?"block":"none"})}"
            >
              <slot name="subnav"></slot>
            </div>
          `:B}
    `}};Fe.styles=rp,D([w()],Fe.prototype,"icon",void 0),D([w()],Fe.prototype,"url",void 0),D([w({type:Boolean,reflect:!0})],Fe.prototype,"active",void 0),D([w({type:Boolean})],Fe.prototype,"external",void 0),D([w({type:Boolean})],Fe.prototype,"indicator",void 0),D([w()],Fe.prototype,"id",void 0),D([w({reflect:!0,type:Boolean,attribute:"icon-only"})],Fe.prototype,"iconOnly",void 0),D([be()],Fe.prototype,"subnavState",void 0),customElements.get("craft-nav-item")||customElements.define("craft-nav-item",Fe);var Qi=new Set;function ap(){const n=document.documentElement.clientWidth;return Math.abs(window.innerWidth-n)}function lp(){const n=Number(getComputedStyle(document.body).paddingRight.replace(/px/,""));return isNaN(n)||!n?0:n}function Un(n){if(Qi.add(n),!document.documentElement.classList.contains("wa-scroll-lock")){const e=ap()+lp();let t=getComputedStyle(document.documentElement).scrollbarGutter;(!t||t==="auto")&&(t="stable"),e<2&&(t=""),document.documentElement.style.setProperty("--wa-scroll-lock-gutter",t),document.documentElement.classList.add("wa-scroll-lock"),document.documentElement.style.setProperty("--wa-scroll-lock-size",`${e}px`)}}function Hn(n){Qi.delete(n),Qi.size===0&&(document.documentElement.classList.remove("wa-scroll-lock"),document.documentElement.style.removeProperty("--wa-scroll-lock-size"))}function ba(n){return n.split(" ").map(e=>e.trim()).filter(e=>e!=="")}var cp=`:host {
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
`,xe=class extends ge{constructor(){super(...arguments),this.localize=new Ot(this),this.hasSlotController=new ri(this,"footer","header-actions","label"),this.open=!1,this.label="",this.placement="end",this.withoutHeader=!1,this.lightDismiss=!0,this.handleDocumentKeyDown=n=>{n.key==="Escape"&&this.open&&(n.preventDefault(),n.stopPropagation(),this.requestClose(this.drawer))}}firstUpdated(){this.open&&(this.addOpenListeners(),this.drawer.showModal(),Un(this))}disconnectedCallback(){super.disconnectedCallback(),Hn(this),this.removeOpenListeners()}async requestClose(n){const e=new dn({source:n});if(this.dispatchEvent(e),e.defaultPrevented){this.open=!0,de(this.drawer,"pulse");return}this.removeOpenListeners(),await de(this.drawer,"hide"),this.open=!1,this.drawer.close(),Hn(this);const t=this.originalTrigger;typeof t?.focus=="function"&&setTimeout(()=>t.focus()),this.dispatchEvent(new ln)}addOpenListeners(){document.addEventListener("keydown",this.handleDocumentKeyDown)}removeOpenListeners(){document.removeEventListener("keydown",this.handleDocumentKeyDown)}handleDialogCancel(n){n.preventDefault(),!this.drawer.classList.contains("hide")&&n.target===this.drawer&&this.requestClose(this.drawer)}handleDialogClick(n){const t=n.target.closest('[data-drawer="close"]');t&&(n.stopPropagation(),this.requestClose(t))}async handleDialogPointerDown(n){n.target===this.drawer&&(this.lightDismiss?this.requestClose(this.drawer):await de(this.drawer,"pulse"))}handleOpenChange(){this.open&&!this.drawer.open?this.show():this.drawer.open&&(this.open=!0,this.requestClose(this.drawer))}async show(){const n=new un;if(this.dispatchEvent(n),n.defaultPrevented){this.open=!1;return}this.addOpenListeners(),this.originalTrigger=document.activeElement,this.open=!0,this.drawer.showModal(),Un(this),requestAnimationFrame(()=>{const e=this.querySelector("[autofocus]");e&&typeof e.focus=="function"?e.focus():this.drawer.focus()}),await de(this.drawer,"show"),this.dispatchEvent(new cn)}render(){const n=!this.withoutHeader,e=this.hasSlotController.test("footer");return x`
      <dialog
        part="dialog"
        class=${Ie({drawer:!0,open:this.open,top:this.placement==="top",end:this.placement==="end",bottom:this.placement==="bottom",start:this.placement==="start"})}
        @cancel=${this.handleDialogCancel}
        @click=${this.handleDialogClick}
        @pointerdown=${this.handleDialogPointerDown}
      >
        ${n?x`
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

        ${e?x`
              <footer part="footer" class="footer">
                <slot name="footer"></slot>
              </footer>
            `:""}
      </dialog>
    `}};xe.css=cp;v([Q(".drawer")],xe.prototype,"drawer",2);v([w({type:Boolean,reflect:!0})],xe.prototype,"open",2);v([w({reflect:!0})],xe.prototype,"label",2);v([w({reflect:!0})],xe.prototype,"placement",2);v([w({attribute:"without-header",type:Boolean,reflect:!0})],xe.prototype,"withoutHeader",2);v([w({attribute:"light-dismiss",type:Boolean})],xe.prototype,"lightDismiss",2);v([Ee("open",{waitUntilFirstUpdate:!0})],xe.prototype,"handleOpenChange",1);xe=v([ke("wa-drawer")],xe);document.addEventListener("click",n=>{const e=n.target.closest("[data-drawer]");if(e instanceof Element){const[t,i]=ba(e.getAttribute("data-drawer")||"");if(t==="open"&&i?.length){const o=e.getRootNode().getElementById(i);o?.localName==="wa-drawer"?o.open=!0:console.warn(`A drawer with an ID of "${i}" could not be found in this document.`)}}});document.body.addEventListener("pointerdown",()=>{});var dp=()=>({checkValidity(n){const e=n.input,t={message:"",isValid:!0,invalidKeys:[]};if(!e)return t;let i=!0;if("checkValidity"in e&&(i=e.checkValidity()),i)return t;if(t.isValid=!1,"validationMessage"in e&&(t.message=e.validationMessage),!("validity"in e))return t.invalidKeys.push("customError"),t;for(const s in e.validity){if(s==="valid")continue;const o=s;e.validity[o]&&t.invalidKeys.push(o)}return t}});var ga=class extends Event{constructor(){super("wa-invalid",{bubbles:!0,cancelable:!1,composed:!0})}},up=()=>({observedAttributes:["custom-error"],checkValidity(n){const e={message:"",isValid:!0,invalidKeys:[]};return n.customError&&(e.message=n.customError,e.isValid=!1,e.invalidKeys=["customError"]),e}}),He=class extends ge{constructor(){super(),this.name=null,this.disabled=!1,this.required=!1,this.assumeInteractionOn=["input"],this.validators=[],this.valueHasChanged=!1,this.hasInteracted=!1,this.customError=null,this.emittedEvents=[],this.emitInvalid=n=>{n.target===this&&(this.hasInteracted=!0,this.dispatchEvent(new ga))},this.handleInteraction=n=>{const e=this.emittedEvents;e.includes(n.type)||e.push(n.type),e.length===this.assumeInteractionOn?.length&&(this.hasInteracted=!0)},this.addEventListener("invalid",this.emitInvalid)}static get validators(){return[up()]}static get observedAttributes(){const n=new Set(super.observedAttributes||[]);for(const e of this.validators)if(e.observedAttributes)for(const t of e.observedAttributes)n.add(t);return[...n]}connectedCallback(){super.connectedCallback(),this.updateValidity(),this.assumeInteractionOn.forEach(n=>{this.addEventListener(n,this.handleInteraction)})}firstUpdated(...n){super.firstUpdated(...n),this.updateValidity()}willUpdate(n){if(n.has("customError")&&(this.customError||(this.customError=null),this.setCustomValidity(this.customError||"")),n.has("value")||n.has("disabled")){const e=this.value;if(Array.isArray(e)){if(this.name){const t=new FormData;for(const i of e)t.append(this.name,i);this.setValue(t,t)}}else this.setValue(e,e)}n.has("disabled")&&(this.customStates.set("disabled",this.disabled),(this.hasAttribute("disabled")||!this.matches(":disabled"))&&this.toggleAttribute("disabled",this.disabled)),this.updateValidity(),super.willUpdate(n)}get labels(){return this.internals.labels}getForm(){return this.internals.form}get validity(){return this.internals.validity}get willValidate(){return this.internals.willValidate}get validationMessage(){return this.internals.validationMessage}checkValidity(){return this.updateValidity(),this.internals.checkValidity()}reportValidity(){return this.updateValidity(),this.hasInteracted=!0,this.internals.reportValidity()}get validationTarget(){return this.input||void 0}setValidity(...n){const e=n[0],t=n[1];let i=n[2];i||(i=this.validationTarget),this.internals.setValidity(e,t,i||void 0),this.requestUpdate("validity"),this.setCustomStates()}setCustomStates(){const n=!!this.required,e=this.internals.validity.valid,t=this.hasInteracted;this.customStates.set("required",n),this.customStates.set("optional",!n),this.customStates.set("invalid",!e),this.customStates.set("valid",e),this.customStates.set("user-invalid",!e&&t),this.customStates.set("user-valid",e&&t)}setCustomValidity(n){if(!n){this.customError=null,this.setValidity({});return}this.customError=n,this.setValidity({customError:!0},n,this.validationTarget)}formResetCallback(){this.resetValidity(),this.hasInteracted=!1,this.valueHasChanged=!1,this.emittedEvents=[],this.updateValidity()}formDisabledCallback(n){this.disabled=n,this.updateValidity()}formStateRestoreCallback(n,e){this.value=n,e==="restore"&&this.resetValidity(),this.updateValidity()}setValue(...n){const[e,t]=n;this.internals.setFormValue(e,t)}get allValidators(){const n=this.constructor.validators||[],e=this.validators||[];return[...n,...e]}resetValidity(){this.setCustomValidity(""),this.setValidity({})}updateValidity(){if(this.disabled||this.hasAttribute("disabled")||!this.willValidate){this.resetValidity();return}const n=this.allValidators;if(!n?.length)return;const e={customError:!!this.customError},t=this.validationTarget||this.input||void 0;let i="";for(const s of n){const{isValid:o,message:r,invalidKeys:a}=s.checkValidity(this);o||(i||(i=r),a?.length>=0&&a.forEach(l=>e[l]=!0))}i||(i=this.validationMessage),this.setValidity(e,i,t)}};He.formAssociated=!0;v([w({reflect:!0})],He.prototype,"name",2);v([w({type:Boolean})],He.prototype,"disabled",2);v([w({state:!0,attribute:!1})],He.prototype,"valueHasChanged",2);v([w({state:!0,attribute:!1})],He.prototype,"hasInteracted",2);v([w({attribute:"custom-error",reflect:!0})],He.prototype,"customError",2);v([w({attribute:!1,state:!0,type:Object})],He.prototype,"validity",1);var hp=`@layer wa-utilities {
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
`;const va=Symbol.for(""),pp=n=>{if(n?.r===va)return n?._$litStatic$},Io=(n,...e)=>({_$litStatic$:e.reduce(((t,i,s)=>t+(o=>{if(o._$litStatic$!==void 0)return o._$litStatic$;throw Error(`Value passed to 'literal' function must be a 'literal' result: ${o}. Use 'unsafeStatic' to pass non-literal values, but
            take care to ensure page security.`)})(i)+n[s+1]),n[0]),r:va}),Po=new Map,fp=n=>(e,...t)=>{const i=t.length;let s,o;const r=[],a=[];let l,d=0,c=!1;for(;d<i;){for(l=e[d];d<i&&(o=t[d],(s=pp(o))!==void 0);)l+=s+e[++d],c=!0;d!==i&&a.push(o),r.push(l),d++}if(d===i&&r.push(e[i]),c){const p=r.join("$$lit$$");(e=Po.get(p))===void 0&&(r.raw=r,Po.set(p,e=r)),t=a}return n(e,...t)},Ti=fp(x);var mp=`@layer wa-component {
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
`,W=class extends He{constructor(){super(...arguments),this.assumeInteractionOn=["click"],this.hasSlotController=new ri(this,"[default]","start","end"),this.localize=new Ot(this),this.invalid=!1,this.isIconButton=!1,this.title="",this.variant="neutral",this.appearance="accent",this.size="medium",this.withCaret=!1,this.disabled=!1,this.loading=!1,this.pill=!1,this.type="button",this.form=null}static get validators(){return[...super.validators,dp()]}constructLightDOMButton(){const n=document.createElement("button");return n.type=this.type,n.style.position="absolute",n.style.width="0",n.style.height="0",n.style.clipPath="inset(50%)",n.style.overflow="hidden",n.style.whiteSpace="nowrap",this.name&&(n.name=this.name),n.value=this.value||"",["form","formaction","formenctype","formmethod","formnovalidate","formtarget"].forEach(e=>{this.hasAttribute(e)&&n.setAttribute(e,this.getAttribute(e))}),n}handleClick(){if(!this.getForm())return;const e=this.constructLightDOMButton();this.parentElement?.append(e),e.click(),e.remove()}handleInvalid(){this.dispatchEvent(new ga)}handleLabelSlotChange(){const n=this.labelSlot.assignedNodes({flatten:!0});let e=!1,t=!1,i=!1,s=!1;[...n].forEach(o=>{if(o.nodeType===Node.ELEMENT_NODE){const r=o;r.localName==="wa-icon"?(t=!0,e||(e=r.label!==void 0)):s=!0}else o.nodeType===Node.TEXT_NODE&&(o.textContent?.trim()||"").length>0&&(i=!0)}),this.isIconButton=t&&!i&&!s,this.isIconButton&&!e&&console.warn('Icon buttons must have a label for screen readers. Add <wa-icon label="..."> to remove this warning.',this)}isButton(){return!this.href}isLink(){return!!this.href}handleDisabledChange(){this.updateValidity()}setValue(...n){}click(){this.button.click()}focus(n){this.button.focus(n)}blur(){this.button.blur()}render(){const n=this.isLink(),e=n?Io`a`:Io`button`;return Ti`
      <${e}
        part="base"
        class=${Ie({button:!0,caret:this.withCaret,disabled:this.disabled,loading:this.loading,rtl:this.localize.dir()==="rtl","has-label":this.hasSlotController.test("[default]"),"has-start":this.hasSlotController.test("start"),"has-end":this.hasSlotController.test("end"),"is-icon-button":this.isIconButton})}
        ?disabled=${Se(n?void 0:this.disabled)}
        type=${Se(n?void 0:this.type)}
        title=${this.title}
        name=${Se(n?void 0:this.name)}
        value=${Se(n?void 0:this.value)}
        href=${Se(n?this.href:void 0)}
        target=${Se(n?this.target:void 0)}
        download=${Se(n?this.download:void 0)}
        rel=${Se(n&&this.rel?this.rel:void 0)}
        role=${Se(n?void 0:"button")}
        aria-disabled=${this.disabled?"true":"false"}
        tabindex=${this.disabled?"-1":"0"}
        @invalid=${this.isButton()?this.handleInvalid:null}
        @click=${this.handleClick}
      >
        <slot name="start" part="start" class="start"></slot>
        <slot part="label" class="label" @slotchange=${this.handleLabelSlotChange}></slot>
        <slot name="end" part="end" class="end"></slot>
        ${this.withCaret?Ti`
                <wa-icon part="caret" class="caret" library="system" name="chevron-down" variant="solid"></wa-icon>
              `:""}
        ${this.loading?Ti`<wa-spinner part="spinner"></wa-spinner>`:""}
      </${e}>
    `}};W.shadowRootOptions={...He.shadowRootOptions,delegatesFocus:!0};W.css=[mp,hp,ua];v([Q(".button")],W.prototype,"button",2);v([Q("slot:not([name])")],W.prototype,"labelSlot",2);v([be()],W.prototype,"invalid",2);v([be()],W.prototype,"isIconButton",2);v([w()],W.prototype,"title",2);v([w({reflect:!0})],W.prototype,"variant",2);v([w({reflect:!0})],W.prototype,"appearance",2);v([w({reflect:!0})],W.prototype,"size",2);v([w({attribute:"with-caret",type:Boolean,reflect:!0})],W.prototype,"withCaret",2);v([w({type:Boolean})],W.prototype,"disabled",2);v([w({type:Boolean,reflect:!0})],W.prototype,"loading",2);v([w({type:Boolean,reflect:!0})],W.prototype,"pill",2);v([w()],W.prototype,"type",2);v([w({reflect:!0})],W.prototype,"name",2);v([w({reflect:!0})],W.prototype,"value",2);v([w({reflect:!0})],W.prototype,"href",2);v([w()],W.prototype,"target",2);v([w()],W.prototype,"rel",2);v([w()],W.prototype,"download",2);v([w({reflect:!0})],W.prototype,"form",2);v([w({attribute:"formaction"})],W.prototype,"formAction",2);v([w({attribute:"formenctype"})],W.prototype,"formEnctype",2);v([w({attribute:"formmethod"})],W.prototype,"formMethod",2);v([w({attribute:"formnovalidate",type:Boolean})],W.prototype,"formNoValidate",2);v([w({attribute:"formtarget"})],W.prototype,"formTarget",2);v([Ee("disabled",{waitUntilFirstUpdate:!0})],W.prototype,"handleDisabledChange",1);W=v([ke("wa-button")],W);var bp=`:host {
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
`,es=class extends ge{constructor(){super(...arguments),this.localize=new Ot(this)}render(){return x`
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
    `}};es.css=bp;es=v([ke("wa-spinner")],es);var gp=class extends xe{static get styles(){return[xe.styles,F`
        :host {
          --wa-color-surface-raised: var(--c-bg-raised);
          --spacing: var(--c-spacing-lg);
          background-color: red;
        }
      `]}};customElements.get("craft-drawer")||customElements.define("craft-drawer",gp);var vp=`:host {
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
`,$e=class extends ge{constructor(){super(...arguments),this.localize=new Ot(this),this.hasSlotController=new ri(this,"footer","header-actions","label"),this.open=!1,this.label="",this.withoutHeader=!1,this.lightDismiss=!1,this.handleDocumentKeyDown=n=>{n.key==="Escape"&&this.open&&(n.preventDefault(),n.stopPropagation(),this.requestClose(this.dialog))}}firstUpdated(){this.open&&(this.addOpenListeners(),this.dialog.showModal(),Un(this))}disconnectedCallback(){super.disconnectedCallback(),Hn(this),this.removeOpenListeners()}async requestClose(n){const e=new dn({source:n});if(this.dispatchEvent(e),e.defaultPrevented){this.open=!0,de(this.dialog,"pulse");return}this.removeOpenListeners(),await de(this.dialog,"hide"),this.open=!1,this.dialog.close(),Hn(this);const t=this.originalTrigger;typeof t?.focus=="function"&&setTimeout(()=>t.focus()),this.dispatchEvent(new ln)}addOpenListeners(){document.addEventListener("keydown",this.handleDocumentKeyDown)}removeOpenListeners(){document.removeEventListener("keydown",this.handleDocumentKeyDown)}handleDialogCancel(n){n.preventDefault(),!this.dialog.classList.contains("hide")&&n.target===this.dialog&&this.requestClose(this.dialog)}handleDialogClick(n){const t=n.target.closest('[data-dialog="close"]');t&&(n.stopPropagation(),this.requestClose(t))}async handleDialogPointerDown(n){n.target===this.dialog&&(this.lightDismiss?this.requestClose(this.dialog):await de(this.dialog,"pulse"))}handleOpenChange(){this.open&&!this.dialog.open?this.show():!this.open&&this.dialog.open&&(this.open=!0,this.requestClose(this.dialog))}async show(){const n=new un;if(this.dispatchEvent(n),n.defaultPrevented){this.open=!1;return}this.addOpenListeners(),this.originalTrigger=document.activeElement,this.open=!0,this.dialog.showModal(),Un(this),requestAnimationFrame(()=>{const e=this.querySelector("[autofocus]");e&&typeof e.focus=="function"?e.focus():this.dialog.focus()}),await de(this.dialog,"show"),this.dispatchEvent(new cn)}render(){const n=!this.withoutHeader,e=this.hasSlotController.test("footer");return x`
      <dialog
        part="dialog"
        class=${Ie({dialog:!0,open:this.open})}
        @cancel=${this.handleDialogCancel}
        @click=${this.handleDialogClick}
        @pointerdown=${this.handleDialogPointerDown}
      >
        ${n?x`
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

        ${e?x`
              <footer part="footer" class="footer">
                <slot name="footer"></slot>
              </footer>
            `:""}
      </dialog>
    `}};$e.css=vp;v([Q(".dialog")],$e.prototype,"dialog",2);v([w({type:Boolean,reflect:!0})],$e.prototype,"open",2);v([w({reflect:!0})],$e.prototype,"label",2);v([w({attribute:"without-header",type:Boolean,reflect:!0})],$e.prototype,"withoutHeader",2);v([w({attribute:"light-dismiss",type:Boolean})],$e.prototype,"lightDismiss",2);v([Ee("open",{waitUntilFirstUpdate:!0})],$e.prototype,"handleOpenChange",1);$e=v([ke("wa-dialog")],$e);document.addEventListener("click",n=>{const e=n.target.closest("[data-dialog]");if(e instanceof Element){const[t,i]=ba(e.getAttribute("data-dialog")||"");if(t==="open"&&i?.length){const o=e.getRootNode().getElementById(i);o?.localName==="wa-dialog"?o.open=!0:console.warn(`A dialog with an ID of "${i}" could not be found in this document.`)}}});document.addEventListener("pointerdown",()=>{});var yp=class extends $e{static get styles(){return[$e.styles,F`
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
      `]}};customElements.get("craft-dialog")||customElements.define("craft-dialog",yp);class zo extends Yu(eh(G)){constructor(){super(),this.multipleChoice=!0}}class Vo extends Os(Ls){connectedCallback(){super.connectedCallback(),this.type="checkbox"}}var _p=class extends zo{static get styles(){return[...zo.styles,F`
        .form-field__group-two {
          margin-top: var(--c-spacing-sm);
        }

        ::slotted(label) {
          font-weight: bold;
        }
      `]}};customElements.get("craft-checkbox-group")||customElements.define("craft-checkbox-group",_p);var wp=class extends Vo{static get styles(){return[...Vo.styles,F`
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
      `]}};customElements.get("craft-checkbox")||customElements.define("craft-checkbox",wp);const vt={Default:"default",Success:"success",Warning:"warning",Danger:"danger",Info:"info"},Ep={OutlineFill:"outline-fill"};var ya=F`
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
`,xp=F`
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
    display: flex;
    gap: var(--c-spacing-sm);
    align-items: start;
    padding: var(--c-spacing-md);
    border-radius: var(--c-callout-radius, var(--c-radius-md));
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
`,ft=class extends G{constructor(...n){super(...n),this.variant=vt.Default,this.appearance=Ep.OutlineFill,this.title="",this.icon=null}getIcon(){switch(this.variant){case vt.Info:return"lightbulb";case vt.Success:return"check-circle";case vt.Warning:return"exclamation-circle";case vt.Danger:return"exclamation-triangle";default:return null}}render(){return x`
      ${this.icon||this.querySelector('[slot="icon"]')?x`<slot name="icon" class="callout__icon">
            <craft-icon
              name="${this.getIcon()}"
              style="font-size: 0.9em"
            ></craft-icon>
          </slot>`:B}
      <div class="callout__body">
        <slot name="title" class="callout__title">${this.title}</slot>
        <div class="callout__description">
          <slot></slot>
        </div>
      </div>
    `}};ft.styles=[ya,xp],D([w({reflect:!0})],ft.prototype,"variant",void 0),D([w({reflect:!0})],ft.prototype,"appearance",void 0),D([w()],ft.prototype,"title",void 0),D([w()],ft.prototype,"icon",void 0),customElements.get("craft-callout")||customElements.define("craft-callout",ft);var kp=F`
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
    :host(:hover) .action-item {
      background-color: var(--c-color-accent-bg-subtle);
      color: var(--c-color-accent-on-subtle);
    }
  }

  :host([active]) .action-item {
    background-color: var(--c-color-accent-bg-emphasis);
    color: var(--c-color-accent-on-emphasis);
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
    :host(:hover[variant='danger']) .action-item {
      background-color: var(--c-color-bg-subtle);
      color: var(--c-color-on-subtle);
    }
  }
`,mt=class extends G{constructor(...e){super(...e),this.icon=null,this.href=null,this.disabled=!1,this.variant=vt.Default}renderBody(){return x`
      <span class="action-item__prefix">
        <slot name="prefix">
          <slot name="icon">
            ${this.icon?x`<craft-icon name="${this.icon}"></craft-icon>`:B}
          </slot>
        </slot>
      </span>

      <slot></slot>

      <span class="action-item__suffix">
        <slot name="suffix"></slot>
      </span>
    `}render(){return this.href?x`
          <a class="action-item" href="${this.href}"> ${this.renderBody()} </a>
        `:x`
          <button
            type="button"
            class="action-item"
            ?disabled="${this.disabled}"
          >
            ${this.renderBody()}
          </button>
        `}};mt.styles=[ya,kp],D([w()],mt.prototype,"icon",void 0),D([w()],mt.prototype,"href",void 0),D([w({type:Boolean})],mt.prototype,"disabled",void 0),D([w()],mt.prototype,"variant",void 0),customElements.get("craft-action-item")||customElements.define("craft-action-item",mt);const Cp=F`
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
`;class Be{static __createGlobalStyleNode(){const e=document.createElement("style");return e.setAttribute("data-overlays",""),e.textContent=Cp.cssText,document.head.appendChild(e),e}get list(){return this.__list}get shownList(){return this.__shownList}constructor(){this.__list=[],this.__shownList=[],this.__siblingsInert=!1,this.__blockingMap=new WeakMap,Be.__globalStyleNode||(Be.__globalStyleNode=Be.__createGlobalStyleNode())}add(e){if(this.list.find(t=>e===t))throw new Error("controller instance is already added");return this.list.push(e),e}remove(e){if(!this.list.find(t=>e===t))throw new Error("could not find controller to remove");this.__list=this.list.filter(t=>t!==e),this.__shownList=this.shownList.filter(t=>t!==e)}show(e){this.list.find(t=>e===t)&&this.hide(e),this.__shownList.unshift(e),Array.from(this.__shownList).reverse().forEach((t,i)=>{t.elevation=i+1})}hide(e){if(!this.list.find(t=>e===t))throw new Error("could not find controller to hide");this.__shownList=this.shownList.filter(t=>t!==e)}teardown(){this.list.forEach(e=>{e.teardown()}),this.__list=[],this.__shownList=[],this.__siblingsInert=!1,Be.__globalStyleNode&&(document.head.removeChild(Be.__globalStyleNode),Be.__globalStyleNode=void 0)}get siblingsInert(){return this.__siblingsInert}disableTrapsKeyboardFocusForAll(){this.shownList.forEach(e=>{e.trapsKeyboardFocus===!0&&e.disableTrapsKeyboardFocus&&e.disableTrapsKeyboardFocus({findNewTrap:!1})})}informTrapsKeyboardFocusGotEnabled(e){this.siblingsInert===!1&&e==="global"&&(this.__siblingsInert=!0)}informTrapsKeyboardFocusGotDisabled({disabledCtrl:e,findNewTrap:t=!0}={}){const i=this.shownList.find(s=>s!==e&&s.trapsKeyboardFocus===!0);i?t&&i.enableTrapsKeyboardFocus():this.siblingsInert===!0&&(this.__siblingsInert=!1)}requestToPreventScroll(){const{isIOS:e,isMacSafari:t}=ji;document.body.classList.add("overlays-scroll-lock"),(e||t)&&document.body.classList.add("overlays-scroll-lock-ios-fix"),e&&document.documentElement.classList.add("overlays-scroll-lock-ios-fix")}requestToEnableScroll(){if(this.shownList.some(s=>s.preventsScroll===!0))return;const{isIOS:t,isMacSafari:i}=ji;document.body.classList.remove("overlays-scroll-lock"),(t||i)&&document.body.classList.remove("overlays-scroll-lock-ios-fix"),t&&document.documentElement.classList.remove("overlays-scroll-lock-ios-fix")}requestToShowOnly(e){const t=this.shownList.filter(i=>i!==e);t.forEach(i=>i.hide()),this.__blockingMap.set(e,t)}retractRequestToShowOnly(e){this.__blockingMap.has(e)&&this.__blockingMap.get(e).forEach(i=>i.show())}}Be.__globalStyleNode=void 0;const Sp=On.get("@lion/ui::overlays::0.x")||new Be;function ts(){let n=document.activeElement||document.body;for(;n&&n.shadowRoot&&n.shadowRoot.activeElement;)n=n.shadowRoot.activeElement;return n}const Bo=({visibility:n,display:e})=>n!=="hidden"&&e!=="none",Ap=({display:n})=>n==="contents";function Tp(n){if(!n||!n.isConnected||!Bo(n.style))return!1;const e=window.getComputedStyle(n);return Bo(e)?Ap(e)?!0:!!(n.offsetWidth||n.offsetHeight||n.getClientRects().length):!1}function Np(n,e){const t=Math.max(n.tabIndex,0),i=Math.max(e.tabIndex,0);return t===0||i===0?i>t:t>i}function $p(n,e){const t=[];for(;n.length>0&&e.length>0;)Np(n[0],e[0])?t.push(e.shift()):t.push(n.shift());return[...t,...n,...e]}function ns(n){const e=n.length;if(e<2)return n;const t=Math.ceil(e/2),i=ns(n.slice(0,t)),s=ns(n.slice(t));return $p(i,s)}const Ni="matches"in Element.prototype?"matches":"msMatchesSelector";function Op(n){return n[Ni]("input, select, textarea, button, object")?n[Ni](":not([disabled])"):n[Ni]("a[href], area[href], iframe, [tabindex], [contentEditable]")}function Lp(n){return Op(n)?Number(n.getAttribute("tabindex")||0):-1}function Fp(n){if(n.localName==="slot")return n.assignedNodes({flatten:!0});const{children:e}=n.shadowRoot||n;return e||[]}function Rp(n){return n.nodeType!==Node.ELEMENT_NODE?!1:n.localName==="slot"?!0:Tp(n)}function _a(n,e){if(!Rp(n))return!1;const t=n,i=Lp(t);let s=i>0;i>=0&&e.push(t);const o=Fp(t);for(let r=0;r<o.length;r+=1)s=_a(o[r],e)||s;return s}function wa(n){const e=[];return _a(n,e)?ns(e):e}function At(n,e,t={}){function i(b){return"getAttribute"in b}function s(b){if(!i(b))return null;const f=b.getAttribute("slot");let g=null;if(f){const y=t[f];y&&(g=y.filter(E=>E?.element===b)[0]||null)}return g}const o=s(n);if(o)return o.deepContains;function r(b){if(!i(n))return;const f=n.getAttribute("slot");f&&(t[f]=t[f]||[],t[f].push({element:n,deepContains:b}))}let a=n.contains(e);if(a)return r(!0),!0;function l(b){return b.tagName==="SLOT"}function d(b){return l(b)?b.assignedElements():[]}function c(b){return b.nodeType===Node.DOCUMENT_FRAGMENT_NODE}function p(b){let f=!1;for(let g=0;g<b.length;g+=1){const y=b[g];if(y&&(i(y)||c(y))&&At(y,e,t)){f=!0;break}}return f}function m(b){for(let f=0;f<b.children.length;f+=1){const g=b.children[f],y=s(g);if(y){a=y.deepContains||a;break}const E=d(g),C=[g.shadowRoot,...E];if(p(C)){a=!0;break}g.children.length>0&&m(g)}}return n instanceof HTMLElement&&n.shadowRoot&&(a=At(n.shadowRoot,e,t),a)?(r(!0),!0):(m(n),r(a),a)}const Mp={tab:9};function Dp(n,e){const t=wa(n);let i;t.length>=2?i=[t[0],t[t.length-1]]:t.length===1?i=[t[0],t[0]]:i=[n,n],e.shiftKey&&i.reverse();const[s,o]=i,r=ts();r===n||t.includes(r)&&o!==r||(e.preventDefault(),s.focus())}function Ip(n){const e=wa(n),t=e.find(m=>m.hasAttribute("autofocus"))||n;let i,s;t===n&&(n.tabIndex=-1,n.style.setProperty("outline","none")),t.focus();function o(m){m.keyCode===Mp.tab&&Dp(n,m)}function r(){i=document.createElement("div"),i.style.display="none",i.setAttribute("data-is-tab-detection-element",""),n.insertBefore(i,n.children[0]),s=new MutationObserver(m=>{for(const b of m)if(b.type==="childList"){const f=!Array.from(n.children).find(y=>y.hasAttribute("data-is-tab-detection-element")),g=Array.from(b.addedNodes).find(y=>y instanceof HTMLElement&&y.hasAttribute("data-is-tab-detection-element"));f&&!g&&(s.disconnect(),r())}}),s.observe(n,{childList:!0})}function a(){return i.compareDocumentPosition(document.activeElement)===Node.DOCUMENT_POSITION_PRECEDING}function l({resetToRoot:m=!1}={}){if(At(n,ts()))return;let b;m?b=n:b=e[a()?0:e.length-1],b&&b.focus()}function d(){window.removeEventListener("focusin",d),l()}function c(){setTimeout(()=>{At(n,ts())||l({resetToRoot:!0})}),window.addEventListener("focusin",d)}function p(){window.removeEventListener("keydown",o),window.removeEventListener("focusin",d),window.removeEventListener("focusout",c),s.disconnect(),Array.from(n.children).includes(i)&&n.removeChild(i),n.style.removeProperty("outline")}return window.addEventListener("keydown",o),window.addEventListener("focusout",c),r(),{disconnect:p}}const Uo=F`
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
`,Tt={supportsAdoptingStyleSheets:window.ShadowRoot&&(window.ShadyCSS===void 0||window.ShadyCSS.nativeShadow)&&"adoptedStyleSheets"in Document.prototype&&"replace"in CSSStyleSheet.prototype,adoptStyle:void 0,adoptStyles:void 0},$i=new WeakMap;function Pp(n){return Array.from(n.cssRules).map(e=>e.cssText).join("")}function zp(n,e,{teardown:t=!1}={}){const i=n===document?document.body:n,s=e.cssText||Pp(e);if(t){const o=Array.from(i.querySelectorAll("style"));for(const r of o)if(r.textContent===s){r.remove();break}}else{const o=document.createElement("style"),r=window.litNonce;r!==void 0&&o.setAttribute("nonce",r),o.textContent=s,i.appendChild(o)}}function Vp(n,e,{teardown:t=!1}={}){let i=!1;n&&!$i.has(n)&&$i.set(n,[]);const s=$i.get(n)??[],o=s.find(r=>e===r);return o&&t?s.splice(s.indexOf(e),1):!o&&!t?s.push(e):(o&&!t||!o&&t)&&(i=!0),{haltFurtherExecution:i}}function Bp(n,e,{teardown:t=!1}={}){const{haltFurtherExecution:i}=Vp(n,e,{teardown:t});if(i)return;if(!Tt.supportsAdoptingStyleSheets||ji.isIOS){zp(n,e,{teardown:t});return}const s=e instanceof CSSStyleSheet?e:e.styleSheet;if(!s)throw new Error("Please provide a CSSResultOrNative style");t?n.adoptedStyleSheets.includes(s)&&n.adoptedStyleSheets.splice(n.adoptedStyleSheets.indexOf(s),1):n.adoptedStyleSheets=[...n.adoptedStyleSheets,s]}function Up(n,e,{teardown:t=!1}={}){for(const i of e)Tt.adoptStyle(n,i,{teardown:t})}Tt.adoptStyle=Bp;Tt.adoptStyles=Up;function Hp({wrappingDialogNodeL1:n,contentWrapperNodeL2:e,contentNodeL3:t}){if(!(e.isConnected||t.isConnected))throw new Error('[OverlayController] Could not find a render target, since the provided contentNode is not connected to the DOM. Make sure that it is connected, e.g. by doing "document.body.appendChild(contentNode)", before passing it on.');let i;const s=document.createComment("tempMarker");e.isConnected?(i=e.parentElement||e.getRootNode(),i.insertBefore(s,e),n.appendChild(e)):t.assignedSlot?(i=t.assignedSlot.parentElement||t.assignedSlot.getRootNode(),i.insertBefore(s,t.assignedSlot),n.appendChild(e),e.appendChild(t.assignedSlot)):(i=t.parentElement||t.getRootNode(),i.insertBefore(s,t),n.appendChild(e),e.appendChild(t)),i.insertBefore(n,s),i?.removeChild(s)}async function qp(){return V(()=>import("./popper.js"),[],import.meta.url)}const Ho=new WeakMap;class rt extends EventTarget{constructor(e={},t=Sp){super(),this.manager=t,this.__sharedConfig=e,this.__activeElementRightBeforeHide=null,this.config={},this._defaultConfig={placementMode:void 0,contentNode:e.contentNode,contentWrapperNode:e.contentWrapperNode,invokerNode:e.invokerNode,backdropNode:e.backdropNode,referenceNode:void 0,elementToFocusAfterHide:e.invokerNode,inheritsReferenceWidth:"none",hasBackdrop:!1,isBlocking:!1,preventsScroll:!1,trapsKeyboardFocus:!1,hidesOnEsc:!1,hidesOnOutsideEsc:!1,hidesOnOutsideClick:!1,isTooltip:!1,isAlertDialog:!1,invokerRelation:"description",visibilityTriggerFunction:void 0,handlesAccessibility:!1,popperConfig:{placement:"top",strategy:"fixed",modifiers:[{name:"preventOverflow",enabled:!0,options:{boundariesElement:"viewport",padding:8}},{name:"flip",options:{boundariesElement:"viewport",padding:16}},{name:"offset",enabled:!0,options:{offset:[0,8]}},{name:"arrow",enabled:!1}]},viewportConfig:{placement:"center"},zIndex:9999},this._contentId=`overlay-content--${Math.random().toString(36).slice(2,10)}`,this.__originalAttrs=new Map,this.updateConfig(e),this.__hasActiveTrapsKeyboardFocus=!1,this.__hasActiveBackdrop=!0,this.__escKeyHandler=this.__escKeyHandler.bind(this),this.__cancelHandler=this.__cancelHandler.bind(this)}get invoker(){return this.invokerNode}get content(){return this.__wrappingDialogNode}get placementMode(){return this.config?.placementMode}get invokerNode(){return this.config?.invokerNode}get referenceNode(){return this.config?.referenceNode}get contentNode(){return this.config?.contentNode}get contentWrapperNode(){return this.__contentWrapperNode||this.config?.contentWrapperNode}get backdropNode(){return this.__backdropNode||this.config?.backdropNode}get elementToFocusAfterHide(){return this.__elementToFocusAfterHide||this.config?.elementToFocusAfterHide}get hasBackdrop(){return!!this.backdropNode||this.config?.hasBackdrop}get isBlocking(){return this.config?.isBlocking}get preventsScroll(){return this.config?.preventsScroll}get trapsKeyboardFocus(){return this.config?.trapsKeyboardFocus}get hidesOnEsc(){return this.config?.hidesOnEsc}get hidesOnOutsideClick(){return this.config?.hidesOnOutsideClick}get hidesOnOutsideEsc(){return this.config?.hidesOnOutsideEsc}get inheritsReferenceWidth(){return this.config?.inheritsReferenceWidth}get handlesAccessibility(){return this.config?.handlesAccessibility}get isTooltip(){return this.config?.isTooltip}get isAlertDialog(){return this.config?.isAlertDialog}get invokerRelation(){return this.config?.invokerRelation}get popperConfig(){return this.config?.popperConfig}get viewportConfig(){return this.config?.viewportConfig}get visibilityTriggerFunction(){return this.config?.visibilityTriggerFunction}get _referenceNode(){return this.referenceNode||this.invokerNode}set elevation(e){this.__wrappingDialogNode.style.zIndex=`${this.config.zIndex+e}`}get elevation(){return Number(this.contentWrapperNode?.style.zIndex)}updateConfig(e){this.teardown(),this.__prevConfig=this.config,this.config={...this._defaultConfig,...this.__sharedConfig,...e,popperConfig:{...this._defaultConfig.popperConfig||{},...this.__sharedConfig.popperConfig||{},...e.popperConfig||{},modifiers:[...this._defaultConfig.popperConfig?.modifiers||[],...this.__sharedConfig.popperConfig?.modifiers||[],...e.popperConfig?.modifiers||[]]}},this.__validateConfiguration(this.config),this._init(),this.__elementToFocusAfterHide=void 0,this.#e()||this.manager.add(this)}#e(){return!!this.manager.list.find(e=>this===e)}__validateConfiguration(e){if(!e.placementMode)throw new Error('[OverlayController] You need to provide a .placementMode ("global"|"local")');if(!["global","local"].includes(e.placementMode))throw new Error(`[OverlayController] "${e.placementMode}" is not a valid .placementMode, use ("global"|"local")`);if(!e.contentNode)throw new Error("[OverlayController] You need to provide a .contentNode");if(e.isTooltip&&!e.handlesAccessibility)throw new Error("[OverlayController] .isTooltip only takes effect when .handlesAccessibility is enabled")}_init(){this.__contentHasBeenInitialized||(this.__initContentDomStructure(),this.__contentHasBeenInitialized=!0),this.contentWrapperNode.removeAttribute("style"),this.contentWrapperNode.removeAttribute("class"),this.placementMode==="local"&&(rt.popperModule||(rt.popperModule=qp())),this.__handleOverlayStyles({phase:"init"}),this._handleFeatures({phase:"init"})}__handleOverlayStyles({phase:e}){const t=this.contentWrapperNode?.getRootNode();e==="init"?Tt.adoptStyle(t,Uo):e==="teardown"&&Tt.adoptStyle(t,Uo,{teardown:!0})}__initContentDomStructure(){const e=document.createElement(this.config?._noDialogEl?"div":"dialog");e.setAttribute("role","none"),e.setAttribute("data-overlay-outer-wrapper",""),e.style.cssText=`display:none; z-index: ${this.config.zIndex}; padding: 0;`,this.__wrappingDialogNode=e,this.config?.contentWrapperNode||(this.__contentWrapperNode=document.createElement("div")),this.contentWrapperNode.setAttribute("data-id","content-wrapper"),Hp({wrappingDialogNodeL1:e,contentWrapperNodeL2:this.contentWrapperNode,contentNodeL3:this.contentNode}),e.open=!0,this.isTooltip&&e.setAttribute("tabindex","-1"),this.__wrappingDialogNode.style.display="none",this.contentWrapperNode.style.zIndex="1",getComputedStyle(this.contentNode).position==="absolute"&&(this.contentNode.style.position="static"),HTMLDialogElement&&"closedBy"in HTMLDialogElement.prototype?e.closedBy="none":(e.addEventListener("keydown",i=>{i.key==="Escape"&&i.preventDefault()}),e.addEventListener("keyup",i=>{i.key==="Escape"&&i.preventDefault()}),e.addEventListener("cancel",i=>{i.stopPropagation()}),e.addEventListener("close",i=>{i.stopPropagation()}))}_handleZIndex({phase:e}){if(this.placementMode==="local"&&e==="setup"){const t=Number(getComputedStyle(this.contentNode).zIndex);(t<1||Number.isNaN(t))&&(this.contentNode.style.zIndex="1")}}__setupTeardownAccessibility({phase:e}){if(e==="init"){this.__storeOriginalAttrs(this.contentNode,["role","id"]);const t=this.trapsKeyboardFocus;if(this.invokerNode){const i=["aria-labelledby","aria-describedby"];t||i.push("aria-expanded"),this.__storeOriginalAttrs(this.invokerNode,i)}this.contentNode.id||this.contentNode.setAttribute("id",this._contentId),this.isTooltip?(this.invokerNode&&this.invokerNode.setAttribute(this.invokerRelation==="label"?"aria-labelledby":"aria-describedby",this._contentId),this.contentNode.setAttribute("role","tooltip")):(this.invokerNode&&!t&&this.invokerNode.setAttribute("aria-expanded",`${this.isShown}`),this.isAlertDialog?this.contentNode.setAttribute("role","alertdialog"):this.contentNode.getAttribute("role")||this.contentNode.setAttribute("role","dialog"))}else e==="teardown"&&this.__restoreOriginalAttrs()}__storeOriginalAttrs(e,t){const i={};t.forEach(s=>{i[s]=e.getAttribute(s)}),this.__originalAttrs.set(e,i)}__restoreOriginalAttrs(){for(const[e,t]of this.__originalAttrs)Object.entries(t).forEach(([i,s])=>{s!==null?e.setAttribute(i,s):e.removeAttribute(i)});this.__originalAttrs.clear()}get isShown(){return this.__wrappingDialogNode?.style.display!=="none"}async show(e=this.elementToFocusAfterHide){if(this._showComplete&&await this._showComplete,this._showComplete=new Promise(i=>{this._showResolve=i}),this.manager&&this.manager.show(this),this.isShown){this._showResolve();return}const t=new CustomEvent("before-show",{cancelable:!0});this.dispatchEvent(t),t.defaultPrevented||("HTMLDialogElement"in window&&this.__wrappingDialogNode instanceof HTMLDialogElement&&(this.__wrappingDialogNode.open=!0),this.__wrappingDialogNode.style.display="",this._keepBodySize({phase:"before-show"}),await this._handleFeatures({phase:"show"}),this._keepBodySize({phase:"show"}),await this._handlePosition({phase:"show"}),this.__elementToFocusAfterHide=e,this.dispatchEvent(new Event("show")),await this._transitionShow({backdropNode:this.backdropNode,contentNode:this.contentNode})),this._showResolve()}async _handlePosition({phase:e}){if(this.placementMode==="global"){const t=`overlays__overlay-container--${this.viewportConfig.placement}`;e==="show"?(this.contentWrapperNode.classList.add("overlays__overlay-container"),this.contentWrapperNode.classList.add(t),this.contentNode.classList.add("overlays__overlay")):e==="hide"&&(this.contentWrapperNode.classList.remove("overlays__overlay-container"),this.contentWrapperNode.classList.remove(t),this.contentNode.classList.remove("overlays__overlay"))}else this.placementMode==="local"&&e==="show"&&(await this.__createPopperInstance(),this._popper.forceUpdate())}_keepBodySize({phase:e}){if(this.preventsScroll)switch(e){case"before-show":this.__bodyClientWidth=document.body.clientWidth,this.__bodyClientHeight=document.body.clientHeight,this.__bodyMarginRightInline=document.body.style.marginRight,this.__bodyMarginBottomInline=document.body.style.marginBottom;break;case"show":{if(window.getComputedStyle){const r=window.getComputedStyle(document.body);this.__bodyMarginRight=parseInt(r.getPropertyValue("margin-right"),10),this.__bodyMarginBottom=parseInt(r.getPropertyValue("margin-bottom"),10)}else this.__bodyMarginRight=0,this.__bodyMarginBottom=0;const t=document.body.clientWidth-this.__bodyClientWidth,i=document.body.clientHeight-this.__bodyClientHeight,s=this.__bodyMarginRight+t,o=this.__bodyMarginBottom+i;window.CSS?.number&&document.body.attributeStyleMap?.set?(document.body.attributeStyleMap.set("margin-right",CSS.px(s)),document.body.attributeStyleMap.set("margin-bottom",CSS.px(o))):(document.body.style.marginRight=`${s}px`,document.body.style.marginBottom=`${o}px`);break}case"hide":document.body.style.marginRight=this.__bodyMarginRightInline||"",document.body.style.marginBottom=this.__bodyMarginBottomInline||"";break}}async hide(){if(this._hideComplete=new Promise(t=>{this._hideResolve=t}),this.__activeElementRightBeforeHide=this.contentNode.getRootNode().activeElement,this.manager&&this.manager.hide(this),!this.isShown){this._hideResolve();return}const e=new CustomEvent("before-hide",{cancelable:!0});this.dispatchEvent(e),e.defaultPrevented||(await this._transitionHide({backdropNode:this.backdropNode,contentNode:this.contentNode}),"HTMLDialogElement"in window&&this.__wrappingDialogNode instanceof HTMLDialogElement&&this.__wrappingDialogNode.close(),this.__wrappingDialogNode.style.display="none",this._handleFeatures({phase:"hide"}),this._keepBodySize({phase:"hide"}),this.dispatchEvent(new Event("hide")),this._restoreFocus()),this._hideResolve()}async transitionHide(e){}async _transitionHide({backdropNode:e,contentNode:t}){await this.transitionHide({backdropNode:e,contentNode:t}),this._handlePosition({phase:"hide"}),e&&e.classList.remove("overlays__backdrop--animation-in")}async transitionShow(e){}async _transitionShow(e){await this.transitionShow({backdropNode:this.backdropNode,contentNode:this.contentNode}),e.backdropNode&&e.backdropNode.classList.add("overlays__backdrop--animation-in")}_restoreFocus(){this.__activeElementRightBeforeHide instanceof HTMLElement&&this.contentNode.contains(this.__activeElementRightBeforeHide)&&(this.elementToFocusAfterHide instanceof HTMLElement?(this.elementToFocusAfterHide.focus(),this.elementToFocusAfterHide.scrollIntoView({block:"nearest"})):this.__activeElementRightBeforeHide.blur())}async toggle(){return this.isShown?this.hide():this.show()}_handleFeatures({phase:e}){this._handleZIndex({phase:e}),this.preventsScroll&&this._handlePreventsScroll({phase:e}),this.isBlocking&&this._handleBlocking({phase:e}),this.hasBackdrop&&this._handleBackdrop({phase:e}),this.trapsKeyboardFocus&&this._handleTrapsKeyboardFocus({phase:e}),this.hidesOnEsc&&this._handleHidesOnEsc({phase:e}),this.hidesOnOutsideEsc&&this._handleHidesOnOutsideEsc({phase:e}),this.hidesOnOutsideClick&&this._handleHidesOnOutsideClick({phase:e}),this.handlesAccessibility&&this._handleAccessibility({phase:e}),this.inheritsReferenceWidth&&this._handleInheritsReferenceWidth(),this.visibilityTriggerFunction&&this._handleVisibilityTriggers({phase:e})}_handleVisibilityTriggers({phase:e}){typeof this.visibilityTriggerFunction=="function"&&(e==="init"&&(this.__visibilityTriggerHandler=this.visibilityTriggerFunction({phase:e,controller:this})),this.__visibilityTriggerHandler[e]&&this.__visibilityTriggerHandler[e]())}_handlePreventsScroll({phase:e}){switch(e){case"show":this.manager.requestToPreventScroll();break;case"hide":this.manager.requestToEnableScroll();break}}_handleBlocking({phase:e}){switch(e){case"show":this.manager.requestToShowOnly(this);break;case"hide":this.manager.retractRequestToShowOnly(this);break}}get hasActiveBackdrop(){return this.__hasActiveBackdrop}_handleBackdrop({phase:e}){switch(e){case"init":{this.__backdropInitialized||(this.config?.backdropNode||(this.__backdropNode=document.createElement("div"),this.__backdropNode.classList.add("overlays__backdrop")),this.__wrappingDialogNode.prepend(this.backdropNode),this.__backdropInitialized=!0);break}case"show":this.config.hasBackdrop&&this.backdropNode.classList.add("overlays__backdrop--visible"),this.__hasActiveBackdrop=!0;break;case"hide":case"teardown":this.backdropNode.classList.remove("overlays__backdrop--visible"),this.__hasActiveBackdrop=!1;break}}get hasActiveTrapsKeyboardFocus(){return this.__hasActiveTrapsKeyboardFocus}_handleTrapsKeyboardFocus({phase:e}){e==="show"?("showModal"in this.__wrappingDialogNode&&(this.__wrappingDialogNode.close(),this.__wrappingDialogNode.showModal()),this.enableTrapsKeyboardFocus()):(e==="hide"||e==="teardown")&&this.disableTrapsKeyboardFocus()}enableTrapsKeyboardFocus(){if(this.__hasActiveTrapsKeyboardFocus)return;this.manager&&this.manager.disableTrapsKeyboardFocusForAll(),!!this.contentNode.shadowRoot&&console.warn("[overlays]: For best accessibility (compatibility with Safari + VoiceOver), provide a contentNode that is not a host for a shadow root"),this._containFocusHandler=Ip(this.contentNode),this.__hasActiveTrapsKeyboardFocus=!0,this.manager&&this.manager.informTrapsKeyboardFocusGotEnabled(this.placementMode)}disableTrapsKeyboardFocus({findNewTrap:e=!0}={}){this.__hasActiveTrapsKeyboardFocus&&(this._containFocusHandler&&(this._containFocusHandler.disconnect(),this._containFocusHandler=void 0),this.__hasActiveTrapsKeyboardFocus=!1,this.manager&&this.manager.informTrapsKeyboardFocusGotDisabled({disabledCtrl:this,findNewTrap:e}))}__cancelHandler(e){e.preventDefault()}__escKeyHandler(e){if(e.key!=="Escape"||Ho.has(e))return;(e.composedPath().includes(this.contentNode)||At(this.contentNode,e.target))&&(this.hide(),Ho.set(e,this))}#t=e=>{e.key!=="Escape"||e.composedPath().includes(this.contentNode)||At(this.contentNode,e.target)||this.hide()};_handleHidesOnEsc({phase:e}){e==="show"?(this.contentNode.addEventListener("keyup",this.__escKeyHandler),this.invokerNode&&this.invokerNode.addEventListener("keyup",this.__escKeyHandler)):(e==="hide"||e==="teardown")&&(this.contentNode.removeEventListener("keyup",this.__escKeyHandler),this.invokerNode&&this.invokerNode.removeEventListener("keyup",this.__escKeyHandler))}_handleHidesOnOutsideEsc({phase:e}){e==="show"?document.addEventListener("keyup",this.#t):(e==="hide"||e==="teardown")&&document.removeEventListener("keyup",this.#t)}_handleInheritsReferenceWidth(){if(!this._referenceNode||this.placementMode==="global")return;const e=`${this._referenceNode.getBoundingClientRect().width}px`;switch(this.inheritsReferenceWidth){case"max":this.contentWrapperNode.style.maxWidth=e;break;case"full":this.contentWrapperNode.style.width=e;break;case"min":this.contentWrapperNode.style.minWidth=e,this.contentWrapperNode.style.width="auto";break}}_handleHidesOnOutsideClick({phase:e}){const t=e==="show"?"addEventListener":"removeEventListener";if(e==="show"){let i=!1,s=!1;this.__onInsideMouseDown=()=>{i=!0},this.__onInsideMouseUp=()=>{s=!0},this.__onDocumentMouseUp=()=>{setTimeout(()=>{!i&&!s&&this.hide(),i=!1,s=!1})},this.__onWindowBlur=()=>{setTimeout(()=>{this.hide()})}}this.contentWrapperNode[t]("mousedown",this.__onInsideMouseDown,!0),this.contentWrapperNode[t]("mouseup",this.__onInsideMouseUp,!0),this.invokerNode&&(this.invokerNode[t]("mousedown",this.__onInsideMouseDown,!0),this.invokerNode[t]("mouseup",this.__onInsideMouseUp,!0)),document.documentElement[t]("mouseup",this.__onDocumentMouseUp,!0),window[t]("blur",this.__onWindowBlur)}_handleAccessibility({phase:e}){(e==="init"||e==="teardown")&&this.__setupTeardownAccessibility({phase:e});const t=this.trapsKeyboardFocus;this.invokerNode&&!this.isTooltip&&!t&&this.invokerNode.setAttribute("aria-expanded",`${e==="show"}`)}teardown(){this.__handleOverlayStyles({phase:"teardown"}),this._handleFeatures({phase:"teardown"}),this.#e()&&this.manager.remove(this)}async __createPopperInstance(){if(this._popper&&(this._popper.destroy(),this._popper=void 0),rt.popperModule!==void 0){const{createPopper:e}=await rt.popperModule;this._popper=e(this._referenceNode,this.contentWrapperNode,{...this.config?.popperConfig})}}_hasDisabledInvoker(){return this.invokerNode?this.invokerNode.disabled||this.invokerNode.getAttribute("aria-disabled")==="true":!1}}rt.popperModule=void 0;function Ea(n,e){if(typeof n!="object"||typeof e!="object"||n===null||e===null)return n===e;const t=Object.keys(n),i=Object.keys(e);if(t.length!==i.length)return!1;const s=o=>Ea(n[o],e[o]);return t.every(s)}const jp=n=>class extends n{static get properties(){return{opened:{type:Boolean,reflect:!0}}}#e=!1;constructor(){super(),this.opened=!1,this.config={},this.toggle=this.toggle.bind(this),this.open=this.open.bind(this),this.close=this.close.bind(this)}get config(){return this.__config}set config(t){const i=!Ea(this.config,t);this._overlayCtrl&&i&&this._overlayCtrl.updateConfig(t),this.__config=t,this._overlayCtrl&&i&&this.__syncToOverlayController()}requestUpdate(t,i,s){super.requestUpdate(t,i,s),t==="opened"&&this.opened!==i&&this.dispatchEvent(new CustomEvent("opened-changed",{detail:{opened:this.opened}}))}_defineOverlay({contentNode:t,invokerNode:i,referenceNode:s,backdropNode:o,contentWrapperNode:r}){const a=this._defineOverlayConfig()||{};return new rt({contentNode:t,invokerNode:i,referenceNode:s,backdropNode:o,contentWrapperNode:r,...a,...this.config,popperConfig:{...a.popperConfig||{},...this.config?.popperConfig||{},modifiers:[...a.popperConfig?.modifiers||[],...this.config?.popperConfig?.modifiers||[]]}})}_defineOverlayConfig(){return{placementMode:"local"}}updated(t){super.updated(t),t.has("opened")&&this._overlayCtrl&&!this.__blockSyncToOverlayCtrl&&this.__syncToOverlayController()}_setupOpenCloseListeners(){this.__closeEventInContentNodeHandler=t=>{t.stopPropagation(),this._overlayCtrl.hide()},this._overlayContentNode&&this._overlayContentNode.addEventListener("close-overlay",this.__closeEventInContentNodeHandler)}_teardownOpenCloseListeners(){this._overlayContentNode&&this._overlayContentNode.removeEventListener("close-overlay",this.__closeEventInContentNodeHandler)}connectedCallback(){super.connectedCallback(),this.updateComplete.then(()=>{this.isConnected&&(this.#e||(this._setupOverlayCtrl(),this.#e=!0))})}async disconnectedCallback(){super.disconnectedCallback(),await this._isPermanentlyDisconnected()&&(this._teardownOverlayCtrl(),this.#e=!1)}static enabledWarnings=super.enabledWarnings?.filter(t=>t!=="change-in-update")||[];get _overlayInvokerNode(){return Array.from(this.children).find(t=>t.slot==="invoker")}get _overlayReferenceNode(){}get _overlayBackdropNode(){return this.__cachedOverlayBackdropNode||(this.__cachedOverlayBackdropNode=Array.from(this.children).find(t=>t.slot==="backdrop")),this.__cachedOverlayBackdropNode}get _overlayContentNode(){return this._cachedOverlayContentNode||(this._cachedOverlayContentNode=Array.from(this.children).find(t=>t.slot==="content")||this.config.contentNode),this._cachedOverlayContentNode}get _overlayContentWrapperNode(){return this.shadowRoot?.querySelector("#overlay-content-node-wrapper")}_setupOverlayCtrl(){if(this.#e)return;const t={contentNode:this._overlayContentNode,contentWrapperNode:this._overlayContentWrapperNode,invokerNode:this._overlayInvokerNode,referenceNode:this._overlayReferenceNode,backdropNode:this._overlayBackdropNode};this._overlayCtrl?this._overlayCtrl.updateConfig(t):this._overlayCtrl=this._defineOverlay(t),this.__syncToOverlayController(),this.__setupSyncFromOverlayController(),this._setupOpenCloseListeners()}_teardownOverlayCtrl(){this._overlayCtrl&&(this._teardownOpenCloseListeners(),this.__teardownSyncFromOverlayController(),this._overlayCtrl.teardown())}async _setOpenedWithoutPropertyEffects(t){this.__blockSyncToOverlayCtrl=!0,this.opened=t,await this.updateComplete,this.__blockSyncToOverlayCtrl=!1}__setupSyncFromOverlayController(){this.__onOverlayCtrlShow=()=>{this.opened=!0},this.__onOverlayCtrlHide=()=>{this.opened=!1},this.__onBeforeShow=t=>{const i=new CustomEvent("before-opened",{cancelable:!0});this.dispatchEvent(i),i.defaultPrevented&&(this._setOpenedWithoutPropertyEffects(this._overlayCtrl.isShown),t.preventDefault())},this.__onBeforeHide=t=>{const i=new CustomEvent("before-closed",{cancelable:!0});this.dispatchEvent(i),i.defaultPrevented&&(this._setOpenedWithoutPropertyEffects(this._overlayCtrl.isShown),t.preventDefault())},this._overlayCtrl.addEventListener("show",this.__onOverlayCtrlShow),this._overlayCtrl.addEventListener("hide",this.__onOverlayCtrlHide),this._overlayCtrl.addEventListener("before-show",this.__onBeforeShow),this._overlayCtrl.addEventListener("before-hide",this.__onBeforeHide)}__teardownSyncFromOverlayController(){this._overlayCtrl.removeEventListener("show",this.__onOverlayCtrlShow),this._overlayCtrl.removeEventListener("hide",this.__onOverlayCtrlHide),this._overlayCtrl.removeEventListener("before-show",this.__onBeforeShow),this._overlayCtrl.removeEventListener("before-hide",this.__onBeforeHide)}__syncToOverlayController(){this.opened?this._overlayCtrl.show():this._overlayCtrl.hide()}async toggle(){await this._overlayCtrl.toggle()}async open(){await this._overlayCtrl.show()}async close(){await this._overlayCtrl.hide()}repositionOverlay(){const t=this._overlayCtrl;t.placementMode==="local"&&t._popper&&t._popper.update()}async _isPermanentlyDisconnected(){return await this.updateComplete,!this.isConnected}},Wp=ie(jp);function Kp(){return{visibilityTriggerFunction:({controller:n})=>{function e(){n._hasDisabledInvoker()||n.toggle()}return{init:()=>{n.invokerNode?.addEventListener("click",e)},teardown:()=>{n.invokerNode?.removeEventListener("click",e)}}}}}const Gp=()=>({placementMode:"local",inheritsReferenceWidth:"min",hidesOnOutsideClick:!0,hidesOnEsc:!0,popperConfig:{placement:"bottom-start",modifiers:[{name:"offset",enabled:!1}]},handlesAccessibility:!0,...Kp()});var Oi=class extends Wp(G){_defineOverlayConfig(){return{placementMode:"global",...Gp()}}get _overlayContentNode(){return this.shadowRoot?.querySelector(".menu")}firstUpdated(){this.actionItems.forEach(e=>{e.addEventListener("click",t=>{t.target?.dispatchEvent(new Event("close-overlay",{bubbles:!0}))})})}render(){return x`
      <slot name="invoker"></slot>
      <slot name="backdrop"></slot>

      <div class="menu">
        <slot></slot>
      </div>
    `}};Oi.styles=F`
    .menu {
      display: grid;
      gap: var(--c-spacing-sm);
      border: 1px solid var(--c-color-neutral-border-subtle);
      border-radius: var(--c-radius-md);
      background-color: var(--c-bg-overlay);
      box-shadow: var(--c-shadow-sm);
      padding: var(--c-spacing-sm);
    }

    ::slotted(hr) {
      margin: 0;
    }
  `,D([Cr({selector:"craft-action-item"})],Oi.prototype,"actionItems",void 0),customElements.get("craft-action-menu")||customElements.define("craft-action-menu",Oi);var Yp=class{constructor(){this.refreshPromise=null,this.tokenName=null,this.tokenValue=null,this.refreshPromise=null}async getToken(){return this.tokenValue||await this.refreshToken(),this.tokenValue}async refreshToken(){return this.refreshPromise||(this.refreshPromise=Qt.get("users/session-info").then(({data:n})=>{let{csrfTokenName:e,csrfTokenValue:t}=n;return this.tokenName=e??null,this.tokenValue=t??null,this.tokenValue}).finally(()=>{this.refreshPromise=null})),this.refreshPromise}clearToken(){this.tokenValue=null}};function Xp(n=""){return`https://craft6-dev.ddev.site/admin/actions/${n}`}function Jp(){let n={"X-Registered-Asset-Bundles":[...new Set(Craft.registeredAssetBundles)].join(","),"X-Registered-Js-Files":[...new Set(Craft.registeredJsFiles)].join(",")};return Craft.csrfTokenValue&&(n["X-CSRF-Token"]=Craft.csrfTokenValue),n}const Qt=te.create({baseURL:Xp()}),Li=new Yp;Qt.interceptors.request.use(async n=>{n.headers.set("X-Requested-With","XMLHttpRequest");let e=Jp();if(Object.entries(e).forEach(([t,i])=>{n.headers.set(t,i)}),["post","put","patch","delete"].includes(n.method?.toLowerCase()||"")&&!n.url?.includes("users/session-info")){let t=await Li.getToken();t&&n.headers.set("X-CSRF-Token",t)}return n}),Qt.interceptors.response.use(n=>n,async n=>{let e=n.config;if(n.response?.status===419||n.response?.status===403&&!e._retry){e._retry=!0;try{return Li.clearToken(),e.headers["X-CSRF-Token"]=await Li.refreshToken(),te(e)}catch(t){return console.error("Failed to refresh CSRF token:",t),Promise.reject(t)}}return Promise.reject(n)});let Ln=!1,ct=null;async function Zp(n){if(!Ln){if(ct)return ct;Ln=!0;try{return(await Qt.post("app/api-headers",void 0,{cancelToken:n})).data}catch{}finally{Ln=!1}}}const Fi=te.create({baseURL:"https://api.craftcms.com/v1/"});async function Qp(n){return ct?Object.entries(ct).forEach(([e,t])=>{n.headers.set(e,t)}):(n.params=n.params||{},n.params.processCraftHeaders=1),n}async function ef(n,e){if(ct)return;let{data:t}=await Qt.post("app/process-api-response-headers",{headers:n},{cancelToken:e});return ct=t,Ln=!1,ct}async function tf(n){return await ef(n.headers,n.config.cancelToken),n}Fi.interceptors.request.use(async n=>{let{cancelToken:e}=n,t=await Zp(e);t&&Object.entries(t).forEach(([s,o])=>{n.headers.set(s,o)});let i={...n,params:{...Craft.apiParams||{},...n.params,v:new Date().getTime()}};return t||(i.params.processCraftHeaders=1),Craft.httpProxy&&(i.proxy=Craft.httpProxy),i}),Fi.interceptors.request.use(Qp),Fi.interceptors.response.use(tf);var nf=function(n,e,t,i,s){if(i==="m")throw new TypeError("Private method is not writable");if(i==="a"&&!s)throw new TypeError("Private accessor was defined without a setter");if(typeof e=="function"?n!==e||!s:!e.has(n))throw new TypeError("Cannot write private member to an object whose class did not declare it");return i==="a"?s.call(n,t):s?s.value=t:e.set(n,t),t},qo=function(n,e,t,i){if(t==="a"&&!i)throw new TypeError("Private accessor was defined without a getter");if(typeof e=="function"?n!==e||!i:!e.has(n))throw new TypeError("Cannot read private member from an object whose class did not declare it");return t==="m"?i:t==="a"?i.call(n):i?i.value:e.get(n)},Ht;class sf{formatToParts(e){const t=[];for(const i of e)t.push({type:"element",value:i}),t.push({type:"literal",value:", "});return t.slice(0,-1)}}const of=typeof Intl<"u"&&Intl.ListFormat||sf,rf=[["years","year"],["months","month"],["weeks","week"],["days","day"],["hours","hour"],["minutes","minute"],["seconds","second"],["milliseconds","millisecond"]],af={minimumIntegerDigits:2};class lf{constructor(e,t={}){Ht.set(this,void 0);let i=String(t.style||"short");i!=="long"&&i!=="short"&&i!=="narrow"&&i!=="digital"&&(i="short");let s=i==="digital"?"numeric":i;const o=t.hours||s;s=o==="2-digit"?"numeric":o;const r=t.minutes||s;s=r==="2-digit"?"numeric":r;const a=t.seconds||s;s=a==="2-digit"?"numeric":a;const l=t.milliseconds||s;nf(this,Ht,{locale:e,style:i,years:t.years||i==="digital"?"short":i,yearsDisplay:t.yearsDisplay==="always"?"always":"auto",months:t.months||i==="digital"?"short":i,monthsDisplay:t.monthsDisplay==="always"?"always":"auto",weeks:t.weeks||i==="digital"?"short":i,weeksDisplay:t.weeksDisplay==="always"?"always":"auto",days:t.days||i==="digital"?"short":i,daysDisplay:t.daysDisplay==="always"?"always":"auto",hours:o,hoursDisplay:t.hoursDisplay==="always"||i==="digital"?"always":"auto",minutes:r,minutesDisplay:t.minutesDisplay==="always"||i==="digital"?"always":"auto",seconds:a,secondsDisplay:t.secondsDisplay==="always"||i==="digital"?"always":"auto",milliseconds:l,millisecondsDisplay:t.millisecondsDisplay==="always"?"always":"auto"},"f")}resolvedOptions(){return qo(this,Ht,"f")}formatToParts(e){const t=[],i=qo(this,Ht,"f"),s=i.style,o=i.locale;for(const[r,a]of rf){const l=e[r];if(i[`${r}Display`]==="auto"&&!l)continue;const d=i[r],c=d==="2-digit"?af:d==="numeric"?{}:{style:"unit",unit:a,unitDisplay:d};let p=new Intl.NumberFormat(o,c).format(l);r==="months"&&(d==="narrow"||s==="narrow"&&p.endsWith("m"))&&(p=p.replace(/(\d+)m$/,"$1mo")),t.push(p)}return new of(o,{type:"unit",style:s==="digital"?"short":s}).formatToParts(t)}format(e){return this.formatToParts(e).map(t=>t.value).join("")}}Ht=new WeakMap;const xa=/^[-+]?P(?:(\d+)Y)?(?:(\d+)M)?(?:(\d+)W)?(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?)?$/,qn=["year","month","week","day","hour","minute","second","millisecond"],cf=n=>xa.test(n);class _e{constructor(e=0,t=0,i=0,s=0,o=0,r=0,a=0,l=0){this.years=e,this.months=t,this.weeks=i,this.days=s,this.hours=o,this.minutes=r,this.seconds=a,this.milliseconds=l,this.years||(this.years=0),this.sign||(this.sign=Math.sign(this.years)),this.months||(this.months=0),this.sign||(this.sign=Math.sign(this.months)),this.weeks||(this.weeks=0),this.sign||(this.sign=Math.sign(this.weeks)),this.days||(this.days=0),this.sign||(this.sign=Math.sign(this.days)),this.hours||(this.hours=0),this.sign||(this.sign=Math.sign(this.hours)),this.minutes||(this.minutes=0),this.sign||(this.sign=Math.sign(this.minutes)),this.seconds||(this.seconds=0),this.sign||(this.sign=Math.sign(this.seconds)),this.milliseconds||(this.milliseconds=0),this.sign||(this.sign=Math.sign(this.milliseconds)),this.blank=this.sign===0}abs(){return new _e(Math.abs(this.years),Math.abs(this.months),Math.abs(this.weeks),Math.abs(this.days),Math.abs(this.hours),Math.abs(this.minutes),Math.abs(this.seconds),Math.abs(this.milliseconds))}static from(e){var t;if(typeof e=="string"){const i=String(e).trim(),s=i.startsWith("-")?-1:1,o=(t=i.match(xa))===null||t===void 0?void 0:t.slice(1).map(r=>(Number(r)||0)*s);return o?new _e(...o):new _e}else if(typeof e=="object"){const{years:i,months:s,weeks:o,days:r,hours:a,minutes:l,seconds:d,milliseconds:c}=e;return new _e(i,s,o,r,a,l,d,c)}throw new RangeError("invalid duration")}static compare(e,t){const i=Date.now(),s=Math.abs(jo(i,_e.from(e)).getTime()-i),o=Math.abs(jo(i,_e.from(t)).getTime()-i);return s>o?-1:s<o?1:0}toLocaleString(e,t){return new lf(e,t).format(this)}}function jo(n,e){const t=new Date(n);return e.sign<0?(t.setUTCSeconds(t.getUTCSeconds()+e.seconds),t.setUTCMinutes(t.getUTCMinutes()+e.minutes),t.setUTCHours(t.getUTCHours()+e.hours),t.setUTCDate(t.getUTCDate()+e.weeks*7+e.days),t.setUTCMonth(t.getUTCMonth()+e.months),t.setUTCFullYear(t.getUTCFullYear()+e.years)):(t.setUTCFullYear(t.getUTCFullYear()+e.years),t.setUTCMonth(t.getUTCMonth()+e.months),t.setUTCDate(t.getUTCDate()+e.weeks*7+e.days),t.setUTCHours(t.getUTCHours()+e.hours),t.setUTCMinutes(t.getUTCMinutes()+e.minutes),t.setUTCSeconds(t.getUTCSeconds()+e.seconds)),t}function df(n,e="second",t=Date.now()){const i=n.getTime()-t;if(i===0)return new _e;const s=Math.sign(i),o=Math.abs(i),r=Math.floor(o/1e3),a=Math.floor(r/60),l=Math.floor(a/60),d=Math.floor(l/24),c=Math.floor(d/30),p=Math.floor(c/12),m=qn.indexOf(e)||qn.length;return new _e(m>=0?p*s:0,m>=1?(c-p*12)*s:0,0,m>=3?(d-c*30)*s:0,m>=4?(l-d*24)*s:0,m>=5?(a-l*60)*s:0,m>=6?(r-a*60)*s:0,m>=7?(o-r*1e3)*s:0)}function ka(n,{relativeTo:e=Date.now()}={}){if(e=new Date(e),n.blank)return n;const t=n.sign;let i=Math.abs(n.years),s=Math.abs(n.months),o=Math.abs(n.weeks),r=Math.abs(n.days),a=Math.abs(n.hours),l=Math.abs(n.minutes),d=Math.abs(n.seconds),c=Math.abs(n.milliseconds);c>=900&&(d+=Math.round(c/1e3)),(d||l||a||r||o||s||i)&&(c=0),d>=55&&(l+=Math.round(d/60)),(l||a||r||o||s||i)&&(d=0),l>=55&&(a+=Math.round(l/60)),(a||r||o||s||i)&&(l=0),r&&a>=12&&(r+=Math.round(a/24)),!r&&a>=21&&(r+=Math.round(a/24)),(r||o||s||i)&&(a=0);const p=e.getFullYear(),m=e.getMonth(),b=e.getDate();if(r>=27||i+s+r){const f=new Date(e);f.setDate(1),f.setMonth(m+s*t+1),f.setDate(0);const g=Math.max(0,b-f.getDate()),y=new Date(e);y.setFullYear(p+i*t),y.setDate(b-g),y.setMonth(m+s*t),y.setDate(b-g+r*t);const E=y.getFullYear()-e.getFullYear(),C=y.getMonth()-e.getMonth(),A=Math.abs(Math.round((Number(y)-Number(e))/864e5))+g,S=Math.abs(E*12+C);A<27?(r>=6?(o+=Math.round(r/7),r=0):r=A,s=i=0):S<=11?(s=S,i=0):(s=0,i=E*t),(s||i)&&(r=0)}return i&&(s=0),o>=4&&(s+=Math.round(o/4)),(s||i)&&(o=0),r&&o&&!s&&!i&&(o+=Math.round(r/7),r=0),new _e(i*t,s*t,o*t,r*t,a*t,l*t,d*t,c*t)}function uf(n,e){const t=ka(n,e);if(t.blank)return[0,"second"];for(const i of qn){if(i==="millisecond")continue;const s=t[`${i}s`];if(s)return[s,i]}return[0,"second"]}var J=function(n,e,t,i){if(t==="a"&&!i)throw new TypeError("Private accessor was defined without a getter");if(typeof e=="function"?n!==e||!i:!e.has(n))throw new TypeError("Cannot read private member from an object whose class did not declare it");return t==="m"?i:t==="a"?i.call(n):i?i.value:e.get(n)},xn=function(n,e,t,i,s){if(i==="m")throw new TypeError("Private method is not writable");if(i==="a"&&!s)throw new TypeError("Private accessor was defined without a setter");if(typeof e=="function"?n!==e||!s:!e.has(n))throw new TypeError("Cannot write private member to an object whose class did not declare it");return i==="a"?s.call(n,t):s?s.value=t:e.set(n,t),t},ae,qt,jt,bt,at,is,Ca,Sa,Aa,Ta,Na,ss,$a,yt;const hf=globalThis.HTMLElement||null,Ri=new _e,Wo=new _e(0,0,0,0,0,1);class pf extends Event{constructor(e,t,i,s){super("relative-time-updated",{bubbles:!0,composed:!0}),this.oldText=e,this.newText=t,this.oldTitle=i,this.newTitle=s}}function Ko(n){if(!n.date)return 1/0;if(n.format==="duration"||n.format==="elapsed"){const t=n.precision;if(t==="second")return 1e3;if(t==="minute")return 60*1e3}const e=Math.abs(Date.now()-n.date.getTime());return e<60*1e3?1e3:e<3600*1e3?60*1e3:3600*1e3}const Mi=new class{constructor(){this.elements=new Set,this.time=1/0,this.timer=-1}observe(n){if(this.elements.has(n))return;this.elements.add(n);const e=n.date;if(e&&e.getTime()){const t=Ko(n),i=Date.now()+t;i<this.time&&(clearTimeout(this.timer),this.timer=setTimeout(()=>this.update(),t),this.time=i)}}unobserve(n){this.elements.has(n)&&this.elements.delete(n)}update(){if(clearTimeout(this.timer),!this.elements.size)return;let n=1/0;for(const e of this.elements)n=Math.min(n,Ko(e)),e.update();this.time=Math.min(3600*1e3,n),this.timer=setTimeout(()=>this.update(),this.time),this.time+=Date.now()}};class ff extends hf{constructor(){super(...arguments),ae.add(this),qt.set(this,!1),jt.set(this,!1),at.set(this,this.shadowRoot?this.shadowRoot:this.attachShadow?this.attachShadow({mode:"open"}):this),yt.set(this,null)}static define(e="relative-time",t=customElements){return t.define(e,this),this}get timeZone(){var e;return((e=this.closest("[time-zone]"))===null||e===void 0?void 0:e.getAttribute("time-zone"))||this.ownerDocument.documentElement.getAttribute("time-zone")||void 0}static get observedAttributes(){return["second","minute","hour","weekday","day","month","year","time-zone-name","prefix","threshold","tense","precision","format","format-style","no-title","datetime","lang","title","aria-hidden","time-zone"]}get onRelativeTimeUpdated(){return J(this,yt,"f")}set onRelativeTimeUpdated(e){J(this,yt,"f")&&this.removeEventListener("relative-time-updated",J(this,yt,"f")),xn(this,yt,typeof e=="object"||typeof e=="function"?e:null,"f"),typeof e=="function"&&this.addEventListener("relative-time-updated",e)}get second(){const e=this.getAttribute("second");if(e==="numeric"||e==="2-digit")return e}set second(e){this.setAttribute("second",e||"")}get minute(){const e=this.getAttribute("minute");if(e==="numeric"||e==="2-digit")return e}set minute(e){this.setAttribute("minute",e||"")}get hour(){const e=this.getAttribute("hour");if(e==="numeric"||e==="2-digit")return e}set hour(e){this.setAttribute("hour",e||"")}get weekday(){const e=this.getAttribute("weekday");if(e==="long"||e==="short"||e==="narrow")return e;if(this.format==="datetime"&&e!=="")return this.formatStyle}set weekday(e){this.setAttribute("weekday",e||"")}get day(){var e;const t=(e=this.getAttribute("day"))!==null&&e!==void 0?e:"numeric";if(t==="numeric"||t==="2-digit")return t}set day(e){this.setAttribute("day",e||"")}get month(){const e=this.format;let t=this.getAttribute("month");if(t!==""&&(t??(t=e==="datetime"?this.formatStyle:"short"),t==="numeric"||t==="2-digit"||t==="short"||t==="long"||t==="narrow"))return t}set month(e){this.setAttribute("month",e||"")}get year(){var e;const t=this.getAttribute("year");if(t==="numeric"||t==="2-digit")return t;if(!this.hasAttribute("year")&&new Date().getUTCFullYear()!==((e=this.date)===null||e===void 0?void 0:e.getUTCFullYear()))return"numeric"}set year(e){this.setAttribute("year",e||"")}get timeZoneName(){const e=this.getAttribute("time-zone-name");if(e==="long"||e==="short"||e==="shortOffset"||e==="longOffset"||e==="shortGeneric"||e==="longGeneric")return e}set timeZoneName(e){this.setAttribute("time-zone-name",e||"")}get prefix(){var e;return(e=this.getAttribute("prefix"))!==null&&e!==void 0?e:this.format==="datetime"?"":"on"}set prefix(e){this.setAttribute("prefix",e)}get threshold(){const e=this.getAttribute("threshold");return e&&cf(e)?e:"P30D"}set threshold(e){this.setAttribute("threshold",e)}get tense(){const e=this.getAttribute("tense");return e==="past"?"past":e==="future"?"future":"auto"}set tense(e){this.setAttribute("tense",e)}get precision(){const e=this.getAttribute("precision");return qn.includes(e)?e:this.format==="micro"?"minute":"second"}set precision(e){this.setAttribute("precision",e)}get format(){const e=this.getAttribute("format");return e==="datetime"?"datetime":e==="relative"?"relative":e==="duration"?"duration":e==="micro"?"micro":e==="elapsed"?"elapsed":"auto"}set format(e){this.setAttribute("format",e)}get formatStyle(){const e=this.getAttribute("format-style");if(e==="long")return"long";if(e==="short")return"short";if(e==="narrow")return"narrow";const t=this.format;return t==="elapsed"||t==="micro"?"narrow":t==="datetime"?"short":"long"}set formatStyle(e){this.setAttribute("format-style",e)}get noTitle(){return this.hasAttribute("no-title")}set noTitle(e){this.toggleAttribute("no-title",e)}get datetime(){return this.getAttribute("datetime")||""}set datetime(e){this.setAttribute("datetime",e)}get date(){const e=Date.parse(this.datetime);return Number.isNaN(e)?null:new Date(e)}set date(e){this.datetime=e?.toISOString()||""}connectedCallback(){this.update()}disconnectedCallback(){Mi.unobserve(this)}attributeChangedCallback(e,t,i){t!==i&&(e==="title"&&xn(this,qt,i!==null&&(this.date&&J(this,ae,"m",is).call(this,this.date))!==i,"f"),!J(this,jt,"f")&&!(e==="title"&&J(this,qt,"f"))&&xn(this,jt,(async()=>{await Promise.resolve(),this.update(),xn(this,jt,!1,"f")})(),"f"))}update(){const e=J(this,at,"f").textContent||this.textContent||"",t=this.getAttribute("title")||"";let i=t;const s=this.date;if(typeof Intl>"u"||!Intl.DateTimeFormat||!s){J(this,at,"f").textContent=e;return}const o=Date.now();J(this,qt,"f")||(i=J(this,ae,"m",is).call(this,s)||"",i&&!this.noTitle&&this.setAttribute("title",i));const r=df(s,this.precision,o),a=J(this,ae,"m",Ca).call(this,r);let l=e;const d=J(this,ae,"m",$a).call(this,a);d?l=J(this,ae,"m",Na).call(this,s):a==="duration"?l=J(this,ae,"m",Sa).call(this,r):a==="relative"?l=J(this,ae,"m",Aa).call(this,r):l=J(this,ae,"m",Ta).call(this,s),l?J(this,ae,"m",ss).call(this,l):this.shadowRoot===J(this,at,"f")&&this.textContent&&J(this,ae,"m",ss).call(this,this.textContent),(l!==e||i!==t)&&this.dispatchEvent(new pf(e,l,t,i)),(a==="relative"||a==="duration")&&!d?Mi.observe(this):Mi.unobserve(this)}}qt=new WeakMap,jt=new WeakMap,at=new WeakMap,yt=new WeakMap,ae=new WeakSet,bt=function(){var e;const t=((e=this.closest("[lang]"))===null||e===void 0?void 0:e.getAttribute("lang"))||this.ownerDocument.documentElement.getAttribute("lang");try{return new Intl.Locale(t??"").toString()}catch{return"default"}},is=function(e){return new Intl.DateTimeFormat(J(this,ae,"a",bt),{day:"numeric",month:"short",year:"numeric",hour:"numeric",minute:"2-digit",timeZoneName:"short",timeZone:this.timeZone}).format(e)},Ca=function(e){const t=this.format;if(t==="datetime")return"datetime";if(t==="duration"||t==="elapsed"||t==="micro")return"duration";if((t==="auto"||t==="relative")&&typeof Intl<"u"&&Intl.RelativeTimeFormat){const i=this.tense;if(i==="past"||i==="future"||_e.compare(e,this.threshold)===1)return"relative"}return"datetime"},Sa=function(e){const t=J(this,ae,"a",bt),i=this.format,s=this.formatStyle,o=this.tense;let r=Ri;i==="micro"?(e=ka(e),r=Wo,e.months===0&&(this.tense==="past"&&e.sign!==-1||this.tense==="future"&&e.sign!==1)&&(e=Wo)):(o==="past"&&e.sign!==-1||o==="future"&&e.sign!==1)&&(e=r);const a=`${this.precision}sDisplay`;return e.blank?r.toLocaleString(t,{style:s,[a]:"always"}):e.abs().toLocaleString(t,{style:s})},Aa=function(e){const t=new Intl.RelativeTimeFormat(J(this,ae,"a",bt),{numeric:"auto",style:this.formatStyle}),i=this.tense;i==="future"&&e.sign!==1&&(e=Ri),i==="past"&&e.sign!==-1&&(e=Ri);const[s,o]=uf(e);return o==="second"&&s<10?t.format(0,this.precision==="millisecond"?"second":this.precision):t.format(s,o)},Ta=function(e){const t=new Intl.DateTimeFormat(J(this,ae,"a",bt),{second:this.second,minute:this.minute,hour:this.hour,weekday:this.weekday,day:this.day,month:this.month,year:this.year,timeZoneName:this.timeZoneName,timeZone:this.timeZone});return`${this.prefix} ${t.format(e)}`.trim()},Na=function(e){return new Intl.DateTimeFormat(J(this,ae,"a",bt),{day:"numeric",month:"short",year:"numeric",hour:"numeric",minute:"2-digit",timeZoneName:"short",timeZone:this.timeZone}).format(e)},ss=function(e){if(this.hasAttribute("aria-hidden")&&this.getAttribute("aria-hidden")==="true"){const t=document.createElement("span");t.setAttribute("aria-hidden","true"),t.textContent=e,J(this,at,"f").replaceChildren(t)}else J(this,at,"f").textContent=e},$a=function(e){var t;return e==="duration"?!1:this.ownerDocument.documentElement.getAttribute("data-prefers-absolute-time")==="true"||((t=this.ownerDocument.body)===null||t===void 0?void 0:t.getAttribute("data-prefers-absolute-time"))==="true"};const Go=typeof globalThis<"u"?globalThis:window;try{Go.RelativeTimeElement=ff.define()}catch(n){if(!(Go.DOMException&&n instanceof DOMException&&n.name==="NotSupportedError")&&!(n instanceof ReferenceError))throw n}var mf=Object.defineProperty,bf=Object.getOwnPropertyDescriptor,ai=(n,e,t,i)=>{for(var s=i>1?void 0:i?bf(e,t):e,o=n.length-1,r;o>=0;o--)(r=n[o])&&(s=(i?r(e,t,s):r(s))||s);return i&&s&&mf(e,t,s),s};let en=class extends G{constructor(){super(...arguments),this.state=Craft.getCookie("sidebar")??"expanded"}connectedCallback(){super.connectedCallback(),this.trigger&&(this.trigger.addEventListener("open",this.expand.bind(this)),this.trigger.addEventListener("close",this.collapse.bind(this))),this.state==="expanded"?this.expand():this.collapse()}disconnectedCallback(){super.disconnectedCallback(),this.trigger&&(this.trigger.removeEventListener("open",this.expand.bind(this)),this.trigger.removeEventListener("close",this.collapse.bind(this))),this.state="expanded"}itemHasTooltip(n){return n.querySelector("craft-tooltip")}createTooltips(){this.items?.forEach(n=>n.setAttribute("icon-only",!0))}destroyTooltips(){this.items?.forEach(n=>n.removeAttribute("icon-only"))}expand(){document.body.setAttribute("data-sidebar","expanded"),Craft.setCookie("sidebar","expanded"),this.destroyTooltips()}collapse(){document.body.setAttribute("data-sidebar","collapsed"),Craft.setCookie("sidebar","collapsed"),this.createTooltips()}createRenderRoot(){return this}};ai([$c("craft-nav-item")],en.prototype,"items",2);ai([Q("#sidebar-trigger")],en.prototype,"trigger",2);ai([w({reflect:!0})],en.prototype,"state",2);en=ai([ke("cp-global-sidebar")],en);export{V as _,te as a};
