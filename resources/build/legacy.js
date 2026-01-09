const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["./bg-BG2.js","./bg3.js","./cs-CZ2.js","./cs3.js","./de-DE2.js","./de3.js","./en-AU2.js","./en3.js","./en-GB2.js","./en-US2.js","./es-ES2.js","./es3.js","./fr-FR2.js","./fr3.js","./fr-BE2.js","./hu-HU2.js","./hu3.js","./it-IT2.js","./it3.js","./nl-BE2.js","./nl3.js","./nl-NL2.js","./pl-PL2.js","./pl3.js","./ro-RO2.js","./ro3.js","./ru-RU2.js","./ru3.js","./sk-SK2.js","./sk3.js","./tr-TR.js","./tr.js","./uk-UA2.js","./uk3.js","./bg-BG.js","./bg2.js","./cs-CZ.js","./cs2.js","./de-DE.js","./de2.js","./en-AU.js","./en2.js","./en-GB.js","./en-US.js","./es-ES.js","./es2.js","./fr-FR.js","./fr2.js","./fr-BE.js","./hu-HU.js","./hu2.js","./it-IT.js","./it2.js","./nl-BE.js","./nl2.js","./nl-NL.js","./pl-PL.js","./pl2.js","./ro-RO.js","./ro2.js","./ru-RU.js","./ru2.js","./sk-SK.js","./sk2.js","./uk-UA.js","./uk2.js"])))=>i.map(i=>d[i]);
const rl="modulepreload",al=function(i,e){return new URL(i,e).href},ro={},C=function(e,t,s){let n=Promise.resolve();if(t&&t.length>0){let d=function(c){return Promise.all(c.map(h=>Promise.resolve(h).then(m=>({status:"fulfilled",value:m}),m=>({status:"rejected",reason:m}))))};const r=document.getElementsByTagName("link"),a=document.querySelector("meta[property=csp-nonce]"),l=a?.nonce||a?.getAttribute("nonce");n=d(t.map(c=>{if(c=al(c,s),c in ro)return;ro[c]=!0;const h=c.endsWith(".css"),m=h?'[rel="stylesheet"]':"";if(s)for(let p=r.length-1;p>=0;p--){const g=r[p];if(g.href===c&&(!h||g.rel==="stylesheet"))return}else if(document.querySelector(`link[href="${c}"]${m}`))return;const b=document.createElement("link");if(b.rel=h?"stylesheet":rl,h||(b.as="script"),b.crossOrigin="",b.href=c,l&&b.setAttribute("nonce",l),document.head.appendChild(b),h)return new Promise((p,g)=>{b.addEventListener("load",p),b.addEventListener("error",()=>g(new Error(`Unable to preload CSS for ${c}`)))})}))}function o(r){const a=new Event("vite:preloadError",{cancelable:!0});if(a.payload=r,window.dispatchEvent(a),!a.defaultPrevented)throw r}return n.then(r=>{for(const a of r||[])a.status==="rejected"&&o(a.reason);return e().catch(o)})};function Sr(i,e){return function(){return i.apply(e,arguments)}}const{toString:ll}=Object.prototype,{getPrototypeOf:Tn}=Object,{iterator:ls,toStringTag:Ar}=Symbol,cs=(i=>e=>{const t=ll.call(e);return i[t]||(i[t]=t.slice(8,-1).toLowerCase())})(Object.create(null)),Re=i=>(i=i.toLowerCase(),e=>cs(e)===i),ds=i=>e=>typeof e===i,{isArray:Lt}=Array,kt=ds("undefined");function di(i){return i!==null&&!kt(i)&&i.constructor!==null&&!kt(i.constructor)&&fe(i.constructor.isBuffer)&&i.constructor.isBuffer(i)}const Tr=Re("ArrayBuffer");function cl(i){let e;return typeof ArrayBuffer<"u"&&ArrayBuffer.isView?e=ArrayBuffer.isView(i):e=i&&i.buffer&&Tr(i.buffer),e}const dl=ds("string"),fe=ds("function"),Nr=ds("number"),ui=i=>i!==null&&typeof i=="object",ul=i=>i===!0||i===!1,Di=i=>{if(cs(i)!=="object")return!1;const e=Tn(i);return(e===null||e===Object.prototype||Object.getPrototypeOf(e)===null)&&!(Ar in i)&&!(ls in i)},hl=i=>{if(!ui(i)||di(i))return!1;try{return Object.keys(i).length===0&&Object.getPrototypeOf(i)===Object.prototype}catch{return!1}},pl=Re("Date"),fl=Re("File"),ml=Re("Blob"),bl=Re("FileList"),gl=i=>ui(i)&&fe(i.pipe),_l=i=>{let e;return i&&(typeof FormData=="function"&&i instanceof FormData||fe(i.append)&&((e=cs(i))==="formdata"||e==="object"&&fe(i.toString)&&i.toString()==="[object FormData]"))},vl=Re("URLSearchParams"),[yl,wl,xl,El]=["ReadableStream","Request","Response","Headers"].map(Re),Cl=i=>i.trim?i.trim():i.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g,"");function hi(i,e,{allOwnKeys:t=!1}={}){if(i===null||typeof i>"u")return;let s,n;if(typeof i!="object"&&(i=[i]),Lt(i))for(s=0,n=i.length;s<n;s++)e.call(null,i[s],s,i);else{if(di(i))return;const o=t?Object.getOwnPropertyNames(i):Object.keys(i),r=o.length;let a;for(s=0;s<r;s++)a=o[s],e.call(null,i[a],a,i)}}function Or(i,e){if(di(i))return null;e=e.toLowerCase();const t=Object.keys(i);let s=t.length,n;for(;s-- >0;)if(n=t[s],e===n.toLowerCase())return n;return null}const at=typeof globalThis<"u"?globalThis:typeof self<"u"?self:typeof window<"u"?window:global,Fr=i=>!kt(i)&&i!==at;function an(){const{caseless:i,skipUndefined:e}=Fr(this)&&this||{},t={},s=(n,o)=>{const r=i&&Or(t,o)||o;Di(t[r])&&Di(n)?t[r]=an(t[r],n):Di(n)?t[r]=an({},n):Lt(n)?t[r]=n.slice():(!e||!kt(n))&&(t[r]=n)};for(let n=0,o=arguments.length;n<o;n++)arguments[n]&&hi(arguments[n],s);return t}const kl=(i,e,t,{allOwnKeys:s}={})=>(hi(e,(n,o)=>{t&&fe(n)?i[o]=Sr(n,t):i[o]=n},{allOwnKeys:s}),i),Sl=i=>(i.charCodeAt(0)===65279&&(i=i.slice(1)),i),Al=(i,e,t,s)=>{i.prototype=Object.create(e.prototype,s),i.prototype.constructor=i,Object.defineProperty(i,"super",{value:e.prototype}),t&&Object.assign(i.prototype,t)},Tl=(i,e,t,s)=>{let n,o,r;const a={};if(e=e||{},i==null)return e;do{for(n=Object.getOwnPropertyNames(i),o=n.length;o-- >0;)r=n[o],(!s||s(r,i,e))&&!a[r]&&(e[r]=i[r],a[r]=!0);i=t!==!1&&Tn(i)}while(i&&(!t||t(i,e))&&i!==Object.prototype);return e},Nl=(i,e,t)=>{i=String(i),(t===void 0||t>i.length)&&(t=i.length),t-=e.length;const s=i.indexOf(e,t);return s!==-1&&s===t},Ol=i=>{if(!i)return null;if(Lt(i))return i;let e=i.length;if(!Nr(e))return null;const t=new Array(e);for(;e-- >0;)t[e]=i[e];return t},Fl=(i=>e=>i&&e instanceof i)(typeof Uint8Array<"u"&&Tn(Uint8Array)),Ll=(i,e)=>{const s=(i&&i[ls]).call(i);let n;for(;(n=s.next())&&!n.done;){const o=n.value;e.call(i,o[0],o[1])}},$l=(i,e)=>{let t;const s=[];for(;(t=i.exec(e))!==null;)s.push(t);return s},Rl=Re("HTMLFormElement"),Il=i=>i.toLowerCase().replace(/[-_\s]([a-z\d])(\w*)/g,function(t,s,n){return s.toUpperCase()+n}),ao=(({hasOwnProperty:i})=>(e,t)=>i.call(e,t))(Object.prototype),Dl=Re("RegExp"),Lr=(i,e)=>{const t=Object.getOwnPropertyDescriptors(i),s={};hi(t,(n,o)=>{let r;(r=e(n,o,i))!==!1&&(s[o]=r||n)}),Object.defineProperties(i,s)},Ml=i=>{Lr(i,(e,t)=>{if(fe(i)&&["arguments","caller","callee"].indexOf(t)!==-1)return!1;const s=i[t];if(fe(s)){if(e.enumerable=!1,"writable"in e){e.writable=!1;return}e.set||(e.set=()=>{throw Error("Can not rewrite read-only method '"+t+"'")})}})},Vl=(i,e)=>{const t={},s=n=>{n.forEach(o=>{t[o]=!0})};return Lt(i)?s(i):s(String(i).split(e)),t},Pl=()=>{},zl=(i,e)=>i!=null&&Number.isFinite(i=+i)?i:e;function Bl(i){return!!(i&&fe(i.append)&&i[Ar]==="FormData"&&i[ls])}const Ul=i=>{const e=new Array(10),t=(s,n)=>{if(ui(s)){if(e.indexOf(s)>=0)return;if(di(s))return s;if(!("toJSON"in s)){e[n]=s;const o=Lt(s)?[]:{};return hi(s,(r,a)=>{const l=t(r,n+1);!kt(l)&&(o[a]=l)}),e[n]=void 0,o}}return s};return t(i,0)},Hl=Re("AsyncFunction"),ql=i=>i&&(ui(i)||fe(i))&&fe(i.then)&&fe(i.catch),$r=((i,e)=>i?setImmediate:e?((t,s)=>(at.addEventListener("message",({source:n,data:o})=>{n===at&&o===t&&s.length&&s.shift()()},!1),n=>{s.push(n),at.postMessage(t,"*")}))(`axios@${Math.random()}`,[]):t=>setTimeout(t))(typeof setImmediate=="function",fe(at.postMessage)),jl=typeof queueMicrotask<"u"?queueMicrotask.bind(at):typeof process<"u"&&process.nextTick||$r,Wl=i=>i!=null&&fe(i[ls]),w={isArray:Lt,isArrayBuffer:Tr,isBuffer:di,isFormData:_l,isArrayBufferView:cl,isString:dl,isNumber:Nr,isBoolean:ul,isObject:ui,isPlainObject:Di,isEmptyObject:hl,isReadableStream:yl,isRequest:wl,isResponse:xl,isHeaders:El,isUndefined:kt,isDate:pl,isFile:fl,isBlob:ml,isRegExp:Dl,isFunction:fe,isStream:gl,isURLSearchParams:vl,isTypedArray:Fl,isFileList:bl,forEach:hi,merge:an,extend:kl,trim:Cl,stripBOM:Sl,inherits:Al,toFlatObject:Tl,kindOf:cs,kindOfTest:Re,endsWith:Nl,toArray:Ol,forEachEntry:Ll,matchAll:$l,isHTMLForm:Rl,hasOwnProperty:ao,hasOwnProp:ao,reduceDescriptors:Lr,freezeMethods:Ml,toObjectSet:Vl,toCamelCase:Il,noop:Pl,toFiniteNumber:zl,findKey:Or,global:at,isContextDefined:Fr,isSpecCompliantForm:Bl,toJSONObject:Ul,isAsyncFn:Hl,isThenable:ql,setImmediate:$r,asap:jl,isIterable:Wl};function z(i,e,t,s,n){Error.call(this),Error.captureStackTrace?Error.captureStackTrace(this,this.constructor):this.stack=new Error().stack,this.message=i,this.name="AxiosError",e&&(this.code=e),t&&(this.config=t),s&&(this.request=s),n&&(this.response=n,this.status=n.status?n.status:null)}w.inherits(z,Error,{toJSON:function(){return{message:this.message,name:this.name,description:this.description,number:this.number,fileName:this.fileName,lineNumber:this.lineNumber,columnNumber:this.columnNumber,stack:this.stack,config:w.toJSONObject(this.config),code:this.code,status:this.status}}});const Rr=z.prototype,Ir={};["ERR_BAD_OPTION_VALUE","ERR_BAD_OPTION","ECONNABORTED","ETIMEDOUT","ERR_NETWORK","ERR_FR_TOO_MANY_REDIRECTS","ERR_DEPRECATED","ERR_BAD_RESPONSE","ERR_BAD_REQUEST","ERR_CANCELED","ERR_NOT_SUPPORT","ERR_INVALID_URL"].forEach(i=>{Ir[i]={value:i}});Object.defineProperties(z,Ir);Object.defineProperty(Rr,"isAxiosError",{value:!0});z.from=(i,e,t,s,n,o)=>{const r=Object.create(Rr);w.toFlatObject(i,r,function(c){return c!==Error.prototype},d=>d!=="isAxiosError");const a=i&&i.message?i.message:"Error",l=e==null&&i?i.code:e;return z.call(r,a,l,t,s,n),i&&r.cause==null&&Object.defineProperty(r,"cause",{value:i,configurable:!0}),r.name=i&&i.name||"Error",o&&Object.assign(r,o),r};const Kl=null;function ln(i){return w.isPlainObject(i)||w.isArray(i)}function Dr(i){return w.endsWith(i,"[]")?i.slice(0,-2):i}function lo(i,e,t){return i?i.concat(e).map(function(n,o){return n=Dr(n),!t&&o?"["+n+"]":n}).join(t?".":""):e}function Gl(i){return w.isArray(i)&&!i.some(ln)}const Yl=w.toFlatObject(w,{},null,function(e){return/^is[A-Z]/.test(e)});function us(i,e,t){if(!w.isObject(i))throw new TypeError("target must be an object");e=e||new FormData,t=w.toFlatObject(t,{metaTokens:!0,dots:!1,indexes:!1},!1,function(g,_){return!w.isUndefined(_[g])});const s=t.metaTokens,n=t.visitor||c,o=t.dots,r=t.indexes,l=(t.Blob||typeof Blob<"u"&&Blob)&&w.isSpecCompliantForm(e);if(!w.isFunction(n))throw new TypeError("visitor must be a function");function d(p){if(p===null)return"";if(w.isDate(p))return p.toISOString();if(w.isBoolean(p))return p.toString();if(!l&&w.isBlob(p))throw new z("Blob is not supported. Use a Buffer instead.");return w.isArrayBuffer(p)||w.isTypedArray(p)?l&&typeof Blob=="function"?new Blob([p]):Buffer.from(p):p}function c(p,g,_){let E=p;if(p&&!_&&typeof p=="object"){if(w.endsWith(g,"{}"))g=s?g:g.slice(0,-2),p=JSON.stringify(p);else if(w.isArray(p)&&Gl(p)||(w.isFileList(p)||w.endsWith(g,"[]"))&&(E=w.toArray(p)))return g=Dr(g),E.forEach(function(A,S){!(w.isUndefined(A)||A===null)&&e.append(r===!0?lo([g],S,o):r===null?g:g+"[]",d(A))}),!1}return ln(p)?!0:(e.append(lo(_,g,o),d(p)),!1)}const h=[],m=Object.assign(Yl,{defaultVisitor:c,convertValue:d,isVisitable:ln});function b(p,g){if(!w.isUndefined(p)){if(h.indexOf(p)!==-1)throw Error("Circular reference detected in "+g.join("."));h.push(p),w.forEach(p,function(E,k){(!(w.isUndefined(E)||E===null)&&n.call(e,E,w.isString(k)?k.trim():k,g,m))===!0&&b(E,g?g.concat(k):[k])}),h.pop()}}if(!w.isObject(i))throw new TypeError("data must be an object");return b(i),e}function co(i){const e={"!":"%21","'":"%27","(":"%28",")":"%29","~":"%7E","%20":"+","%00":"\0"};return encodeURIComponent(i).replace(/[!'()~]|%20|%00/g,function(s){return e[s]})}function Nn(i,e){this._pairs=[],i&&us(i,this,e)}const Mr=Nn.prototype;Mr.append=function(e,t){this._pairs.push([e,t])};Mr.toString=function(e){const t=e?function(s){return e.call(this,s,co)}:co;return this._pairs.map(function(n){return t(n[0])+"="+t(n[1])},"").join("&")};function Xl(i){return encodeURIComponent(i).replace(/%3A/gi,":").replace(/%24/g,"$").replace(/%2C/gi,",").replace(/%20/g,"+")}function Vr(i,e,t){if(!e)return i;const s=t&&t.encode||Xl;w.isFunction(t)&&(t={serialize:t});const n=t&&t.serialize;let o;if(n?o=n(e,t):o=w.isURLSearchParams(e)?e.toString():new Nn(e,t).toString(s),o){const r=i.indexOf("#");r!==-1&&(i=i.slice(0,r)),i+=(i.indexOf("?")===-1?"?":"&")+o}return i}class uo{constructor(){this.handlers=[]}use(e,t,s){return this.handlers.push({fulfilled:e,rejected:t,synchronous:s?s.synchronous:!1,runWhen:s?s.runWhen:null}),this.handlers.length-1}eject(e){this.handlers[e]&&(this.handlers[e]=null)}clear(){this.handlers&&(this.handlers=[])}forEach(e){w.forEach(this.handlers,function(s){s!==null&&e(s)})}}const Pr={silentJSONParsing:!0,forcedJSONParsing:!0,clarifyTimeoutError:!1},Zl=typeof URLSearchParams<"u"?URLSearchParams:Nn,Jl=typeof FormData<"u"?FormData:null,Ql=typeof Blob<"u"?Blob:null,ec={isBrowser:!0,classes:{URLSearchParams:Zl,FormData:Jl,Blob:Ql},protocols:["http","https","file","blob","url","data"]},On=typeof window<"u"&&typeof document<"u",cn=typeof navigator=="object"&&navigator||void 0,tc=On&&(!cn||["ReactNative","NativeScript","NS"].indexOf(cn.product)<0),ic=typeof WorkerGlobalScope<"u"&&self instanceof WorkerGlobalScope&&typeof self.importScripts=="function",sc=On&&window.location.href||"http://localhost",nc=Object.freeze(Object.defineProperty({__proto__:null,hasBrowserEnv:On,hasStandardBrowserEnv:tc,hasStandardBrowserWebWorkerEnv:ic,navigator:cn,origin:sc},Symbol.toStringTag,{value:"Module"})),ce={...nc,...ec};function oc(i,e){return us(i,new ce.classes.URLSearchParams,{visitor:function(t,s,n,o){return ce.isNode&&w.isBuffer(t)?(this.append(s,t.toString("base64")),!1):o.defaultVisitor.apply(this,arguments)},...e})}function rc(i){return w.matchAll(/\w+|\[(\w*)]/g,i).map(e=>e[0]==="[]"?"":e[1]||e[0])}function ac(i){const e={},t=Object.keys(i);let s;const n=t.length;let o;for(s=0;s<n;s++)o=t[s],e[o]=i[o];return e}function zr(i){function e(t,s,n,o){let r=t[o++];if(r==="__proto__")return!0;const a=Number.isFinite(+r),l=o>=t.length;return r=!r&&w.isArray(n)?n.length:r,l?(w.hasOwnProp(n,r)?n[r]=[n[r],s]:n[r]=s,!a):((!n[r]||!w.isObject(n[r]))&&(n[r]=[]),e(t,s,n[r],o)&&w.isArray(n[r])&&(n[r]=ac(n[r])),!a)}if(w.isFormData(i)&&w.isFunction(i.entries)){const t={};return w.forEachEntry(i,(s,n)=>{e(rc(s),n,t,0)}),t}return null}function lc(i,e,t){if(w.isString(i))try{return(e||JSON.parse)(i),w.trim(i)}catch(s){if(s.name!=="SyntaxError")throw s}return(t||JSON.stringify)(i)}const pi={transitional:Pr,adapter:["xhr","http","fetch"],transformRequest:[function(e,t){const s=t.getContentType()||"",n=s.indexOf("application/json")>-1,o=w.isObject(e);if(o&&w.isHTMLForm(e)&&(e=new FormData(e)),w.isFormData(e))return n?JSON.stringify(zr(e)):e;if(w.isArrayBuffer(e)||w.isBuffer(e)||w.isStream(e)||w.isFile(e)||w.isBlob(e)||w.isReadableStream(e))return e;if(w.isArrayBufferView(e))return e.buffer;if(w.isURLSearchParams(e))return t.setContentType("application/x-www-form-urlencoded;charset=utf-8",!1),e.toString();let a;if(o){if(s.indexOf("application/x-www-form-urlencoded")>-1)return oc(e,this.formSerializer).toString();if((a=w.isFileList(e))||s.indexOf("multipart/form-data")>-1){const l=this.env&&this.env.FormData;return us(a?{"files[]":e}:e,l&&new l,this.formSerializer)}}return o||n?(t.setContentType("application/json",!1),lc(e)):e}],transformResponse:[function(e){const t=this.transitional||pi.transitional,s=t&&t.forcedJSONParsing,n=this.responseType==="json";if(w.isResponse(e)||w.isReadableStream(e))return e;if(e&&w.isString(e)&&(s&&!this.responseType||n)){const r=!(t&&t.silentJSONParsing)&&n;try{return JSON.parse(e,this.parseReviver)}catch(a){if(r)throw a.name==="SyntaxError"?z.from(a,z.ERR_BAD_RESPONSE,this,null,this.response):a}}return e}],timeout:0,xsrfCookieName:"XSRF-TOKEN",xsrfHeaderName:"X-XSRF-TOKEN",maxContentLength:-1,maxBodyLength:-1,env:{FormData:ce.classes.FormData,Blob:ce.classes.Blob},validateStatus:function(e){return e>=200&&e<300},headers:{common:{Accept:"application/json, text/plain, */*","Content-Type":void 0}}};w.forEach(["delete","get","head","post","put","patch"],i=>{pi.headers[i]={}});const cc=w.toObjectSet(["age","authorization","content-length","content-type","etag","expires","from","host","if-modified-since","if-unmodified-since","last-modified","location","max-forwards","proxy-authorization","referer","retry-after","user-agent"]),dc=i=>{const e={};let t,s,n;return i&&i.split(`
`).forEach(function(r){n=r.indexOf(":"),t=r.substring(0,n).trim().toLowerCase(),s=r.substring(n+1).trim(),!(!t||e[t]&&cc[t])&&(t==="set-cookie"?e[t]?e[t].push(s):e[t]=[s]:e[t]=e[t]?e[t]+", "+s:s)}),e},ho=Symbol("internals");function zt(i){return i&&String(i).trim().toLowerCase()}function Mi(i){return i===!1||i==null?i:w.isArray(i)?i.map(Mi):String(i)}function uc(i){const e=Object.create(null),t=/([^\s,;=]+)\s*(?:=\s*([^,;]+))?/g;let s;for(;s=t.exec(i);)e[s[1]]=s[2];return e}const hc=i=>/^[-_a-zA-Z0-9^`|~,!#$%&'*+.]+$/.test(i.trim());function Os(i,e,t,s,n){if(w.isFunction(s))return s.call(this,e,t);if(n&&(e=t),!!w.isString(e)){if(w.isString(s))return e.indexOf(s)!==-1;if(w.isRegExp(s))return s.test(e)}}function pc(i){return i.trim().toLowerCase().replace(/([a-z\d])(\w*)/g,(e,t,s)=>t.toUpperCase()+s)}function fc(i,e){const t=w.toCamelCase(" "+e);["get","set","has"].forEach(s=>{Object.defineProperty(i,s+t,{value:function(n,o,r){return this[s].call(this,e,n,o,r)},configurable:!0})})}let me=class{constructor(e){e&&this.set(e)}set(e,t,s){const n=this;function o(a,l,d){const c=zt(l);if(!c)throw new Error("header name must be a non-empty string");const h=w.findKey(n,c);(!h||n[h]===void 0||d===!0||d===void 0&&n[h]!==!1)&&(n[h||l]=Mi(a))}const r=(a,l)=>w.forEach(a,(d,c)=>o(d,c,l));if(w.isPlainObject(e)||e instanceof this.constructor)r(e,t);else if(w.isString(e)&&(e=e.trim())&&!hc(e))r(dc(e),t);else if(w.isObject(e)&&w.isIterable(e)){let a={},l,d;for(const c of e){if(!w.isArray(c))throw TypeError("Object iterator must return a key-value pair");a[d=c[0]]=(l=a[d])?w.isArray(l)?[...l,c[1]]:[l,c[1]]:c[1]}r(a,t)}else e!=null&&o(t,e,s);return this}get(e,t){if(e=zt(e),e){const s=w.findKey(this,e);if(s){const n=this[s];if(!t)return n;if(t===!0)return uc(n);if(w.isFunction(t))return t.call(this,n,s);if(w.isRegExp(t))return t.exec(n);throw new TypeError("parser must be boolean|regexp|function")}}}has(e,t){if(e=zt(e),e){const s=w.findKey(this,e);return!!(s&&this[s]!==void 0&&(!t||Os(this,this[s],s,t)))}return!1}delete(e,t){const s=this;let n=!1;function o(r){if(r=zt(r),r){const a=w.findKey(s,r);a&&(!t||Os(s,s[a],a,t))&&(delete s[a],n=!0)}}return w.isArray(e)?e.forEach(o):o(e),n}clear(e){const t=Object.keys(this);let s=t.length,n=!1;for(;s--;){const o=t[s];(!e||Os(this,this[o],o,e,!0))&&(delete this[o],n=!0)}return n}normalize(e){const t=this,s={};return w.forEach(this,(n,o)=>{const r=w.findKey(s,o);if(r){t[r]=Mi(n),delete t[o];return}const a=e?pc(o):String(o).trim();a!==o&&delete t[o],t[a]=Mi(n),s[a]=!0}),this}concat(...e){return this.constructor.concat(this,...e)}toJSON(e){const t=Object.create(null);return w.forEach(this,(s,n)=>{s!=null&&s!==!1&&(t[n]=e&&w.isArray(s)?s.join(", "):s)}),t}[Symbol.iterator](){return Object.entries(this.toJSON())[Symbol.iterator]()}toString(){return Object.entries(this.toJSON()).map(([e,t])=>e+": "+t).join(`
`)}getSetCookie(){return this.get("set-cookie")||[]}get[Symbol.toStringTag](){return"AxiosHeaders"}static from(e){return e instanceof this?e:new this(e)}static concat(e,...t){const s=new this(e);return t.forEach(n=>s.set(n)),s}static accessor(e){const s=(this[ho]=this[ho]={accessors:{}}).accessors,n=this.prototype;function o(r){const a=zt(r);s[a]||(fc(n,r),s[a]=!0)}return w.isArray(e)?e.forEach(o):o(e),this}};me.accessor(["Content-Type","Content-Length","Accept","Accept-Encoding","User-Agent","Authorization"]);w.reduceDescriptors(me.prototype,({value:i},e)=>{let t=e[0].toUpperCase()+e.slice(1);return{get:()=>i,set(s){this[t]=s}}});w.freezeMethods(me);function Fs(i,e){const t=this||pi,s=e||t,n=me.from(s.headers);let o=s.data;return w.forEach(i,function(a){o=a.call(t,o,n.normalize(),e?e.status:void 0)}),n.normalize(),o}function Br(i){return!!(i&&i.__CANCEL__)}function $t(i,e,t){z.call(this,i??"canceled",z.ERR_CANCELED,e,t),this.name="CanceledError"}w.inherits($t,z,{__CANCEL__:!0});function Ur(i,e,t){const s=t.config.validateStatus;!t.status||!s||s(t.status)?i(t):e(new z("Request failed with status code "+t.status,[z.ERR_BAD_REQUEST,z.ERR_BAD_RESPONSE][Math.floor(t.status/100)-4],t.config,t.request,t))}function mc(i){const e=/^([-+\w]{1,25})(:?\/\/|:)/.exec(i);return e&&e[1]||""}function bc(i,e){i=i||10;const t=new Array(i),s=new Array(i);let n=0,o=0,r;return e=e!==void 0?e:1e3,function(l){const d=Date.now(),c=s[o];r||(r=d),t[n]=l,s[n]=d;let h=o,m=0;for(;h!==n;)m+=t[h++],h=h%i;if(n=(n+1)%i,n===o&&(o=(o+1)%i),d-r<e)return;const b=c&&d-c;return b?Math.round(m*1e3/b):void 0}}function gc(i,e){let t=0,s=1e3/e,n,o;const r=(d,c=Date.now())=>{t=c,n=null,o&&(clearTimeout(o),o=null),i(...d)};return[(...d)=>{const c=Date.now(),h=c-t;h>=s?r(d,c):(n=d,o||(o=setTimeout(()=>{o=null,r(n)},s-h)))},()=>n&&r(n)]}const Ki=(i,e,t=3)=>{let s=0;const n=bc(50,250);return gc(o=>{const r=o.loaded,a=o.lengthComputable?o.total:void 0,l=r-s,d=n(l),c=r<=a;s=r;const h={loaded:r,total:a,progress:a?r/a:void 0,bytes:l,rate:d||void 0,estimated:d&&a&&c?(a-r)/d:void 0,event:o,lengthComputable:a!=null,[e?"download":"upload"]:!0};i(h)},t)},po=(i,e)=>{const t=i!=null;return[s=>e[0]({lengthComputable:t,total:i,loaded:s}),e[1]]},fo=i=>(...e)=>w.asap(()=>i(...e)),_c=ce.hasStandardBrowserEnv?((i,e)=>t=>(t=new URL(t,ce.origin),i.protocol===t.protocol&&i.host===t.host&&(e||i.port===t.port)))(new URL(ce.origin),ce.navigator&&/(msie|trident)/i.test(ce.navigator.userAgent)):()=>!0,vc=ce.hasStandardBrowserEnv?{write(i,e,t,s,n,o,r){if(typeof document>"u")return;const a=[`${i}=${encodeURIComponent(e)}`];w.isNumber(t)&&a.push(`expires=${new Date(t).toUTCString()}`),w.isString(s)&&a.push(`path=${s}`),w.isString(n)&&a.push(`domain=${n}`),o===!0&&a.push("secure"),w.isString(r)&&a.push(`SameSite=${r}`),document.cookie=a.join("; ")},read(i){if(typeof document>"u")return null;const e=document.cookie.match(new RegExp("(?:^|; )"+i+"=([^;]*)"));return e?decodeURIComponent(e[1]):null},remove(i){this.write(i,"",Date.now()-864e5,"/")}}:{write(){},read(){return null},remove(){}};function yc(i){return/^([a-z][a-z\d+\-.]*:)?\/\//i.test(i)}function wc(i,e){return e?i.replace(/\/?\/$/,"")+"/"+e.replace(/^\/+/,""):i}function Hr(i,e,t){let s=!yc(e);return i&&(s||t==!1)?wc(i,e):e}const mo=i=>i instanceof me?{...i}:i;function pt(i,e){e=e||{};const t={};function s(d,c,h,m){return w.isPlainObject(d)&&w.isPlainObject(c)?w.merge.call({caseless:m},d,c):w.isPlainObject(c)?w.merge({},c):w.isArray(c)?c.slice():c}function n(d,c,h,m){if(w.isUndefined(c)){if(!w.isUndefined(d))return s(void 0,d,h,m)}else return s(d,c,h,m)}function o(d,c){if(!w.isUndefined(c))return s(void 0,c)}function r(d,c){if(w.isUndefined(c)){if(!w.isUndefined(d))return s(void 0,d)}else return s(void 0,c)}function a(d,c,h){if(h in e)return s(d,c);if(h in i)return s(void 0,d)}const l={url:o,method:o,data:o,baseURL:r,transformRequest:r,transformResponse:r,paramsSerializer:r,timeout:r,timeoutMessage:r,withCredentials:r,withXSRFToken:r,adapter:r,responseType:r,xsrfCookieName:r,xsrfHeaderName:r,onUploadProgress:r,onDownloadProgress:r,decompress:r,maxContentLength:r,maxBodyLength:r,beforeRedirect:r,transport:r,httpAgent:r,httpsAgent:r,cancelToken:r,socketPath:r,responseEncoding:r,validateStatus:a,headers:(d,c,h)=>n(mo(d),mo(c),h,!0)};return w.forEach(Object.keys({...i,...e}),function(c){const h=l[c]||n,m=h(i[c],e[c],c);w.isUndefined(m)&&h!==a||(t[c]=m)}),t}const qr=i=>{const e=pt({},i);let{data:t,withXSRFToken:s,xsrfHeaderName:n,xsrfCookieName:o,headers:r,auth:a}=e;if(e.headers=r=me.from(r),e.url=Vr(Hr(e.baseURL,e.url,e.allowAbsoluteUrls),i.params,i.paramsSerializer),a&&r.set("Authorization","Basic "+btoa((a.username||"")+":"+(a.password?unescape(encodeURIComponent(a.password)):""))),w.isFormData(t)){if(ce.hasStandardBrowserEnv||ce.hasStandardBrowserWebWorkerEnv)r.setContentType(void 0);else if(w.isFunction(t.getHeaders)){const l=t.getHeaders(),d=["content-type","content-length"];Object.entries(l).forEach(([c,h])=>{d.includes(c.toLowerCase())&&r.set(c,h)})}}if(ce.hasStandardBrowserEnv&&(s&&w.isFunction(s)&&(s=s(e)),s||s!==!1&&_c(e.url))){const l=n&&o&&vc.read(o);l&&r.set(n,l)}return e},xc=typeof XMLHttpRequest<"u",Ec=xc&&function(i){return new Promise(function(t,s){const n=qr(i);let o=n.data;const r=me.from(n.headers).normalize();let{responseType:a,onUploadProgress:l,onDownloadProgress:d}=n,c,h,m,b,p;function g(){b&&b(),p&&p(),n.cancelToken&&n.cancelToken.unsubscribe(c),n.signal&&n.signal.removeEventListener("abort",c)}let _=new XMLHttpRequest;_.open(n.method.toUpperCase(),n.url,!0),_.timeout=n.timeout;function E(){if(!_)return;const A=me.from("getAllResponseHeaders"in _&&_.getAllResponseHeaders()),R={data:!a||a==="text"||a==="json"?_.responseText:_.response,status:_.status,statusText:_.statusText,headers:A,config:i,request:_};Ur(function(P){t(P),g()},function(P){s(P),g()},R),_=null}"onloadend"in _?_.onloadend=E:_.onreadystatechange=function(){!_||_.readyState!==4||_.status===0&&!(_.responseURL&&_.responseURL.indexOf("file:")===0)||setTimeout(E)},_.onabort=function(){_&&(s(new z("Request aborted",z.ECONNABORTED,i,_)),_=null)},_.onerror=function(S){const R=S&&S.message?S.message:"Network Error",U=new z(R,z.ERR_NETWORK,i,_);U.event=S||null,s(U),_=null},_.ontimeout=function(){let S=n.timeout?"timeout of "+n.timeout+"ms exceeded":"timeout exceeded";const R=n.transitional||Pr;n.timeoutErrorMessage&&(S=n.timeoutErrorMessage),s(new z(S,R.clarifyTimeoutError?z.ETIMEDOUT:z.ECONNABORTED,i,_)),_=null},o===void 0&&r.setContentType(null),"setRequestHeader"in _&&w.forEach(r.toJSON(),function(S,R){_.setRequestHeader(R,S)}),w.isUndefined(n.withCredentials)||(_.withCredentials=!!n.withCredentials),a&&a!=="json"&&(_.responseType=n.responseType),d&&([m,p]=Ki(d,!0),_.addEventListener("progress",m)),l&&_.upload&&([h,b]=Ki(l),_.upload.addEventListener("progress",h),_.upload.addEventListener("loadend",b)),(n.cancelToken||n.signal)&&(c=A=>{_&&(s(!A||A.type?new $t(null,i,_):A),_.abort(),_=null)},n.cancelToken&&n.cancelToken.subscribe(c),n.signal&&(n.signal.aborted?c():n.signal.addEventListener("abort",c)));const k=mc(n.url);if(k&&ce.protocols.indexOf(k)===-1){s(new z("Unsupported protocol "+k+":",z.ERR_BAD_REQUEST,i));return}_.send(o||null)})},Cc=(i,e)=>{const{length:t}=i=i?i.filter(Boolean):[];if(e||t){let s=new AbortController,n;const o=function(d){if(!n){n=!0,a();const c=d instanceof Error?d:this.reason;s.abort(c instanceof z?c:new $t(c instanceof Error?c.message:c))}};let r=e&&setTimeout(()=>{r=null,o(new z(`timeout ${e} of ms exceeded`,z.ETIMEDOUT))},e);const a=()=>{i&&(r&&clearTimeout(r),r=null,i.forEach(d=>{d.unsubscribe?d.unsubscribe(o):d.removeEventListener("abort",o)}),i=null)};i.forEach(d=>d.addEventListener("abort",o));const{signal:l}=s;return l.unsubscribe=()=>w.asap(a),l}},kc=function*(i,e){let t=i.byteLength;if(t<e){yield i;return}let s=0,n;for(;s<t;)n=s+e,yield i.slice(s,n),s=n},Sc=async function*(i,e){for await(const t of Ac(i))yield*kc(t,e)},Ac=async function*(i){if(i[Symbol.asyncIterator]){yield*i;return}const e=i.getReader();try{for(;;){const{done:t,value:s}=await e.read();if(t)break;yield s}}finally{await e.cancel()}},bo=(i,e,t,s)=>{const n=Sc(i,e);let o=0,r,a=l=>{r||(r=!0,s&&s(l))};return new ReadableStream({async pull(l){try{const{done:d,value:c}=await n.next();if(d){a(),l.close();return}let h=c.byteLength;if(t){let m=o+=h;t(m)}l.enqueue(new Uint8Array(c))}catch(d){throw a(d),d}},cancel(l){return a(l),n.return()}},{highWaterMark:2})},go=64*1024,{isFunction:ki}=w,Tc=(({Request:i,Response:e})=>({Request:i,Response:e}))(w.global),{ReadableStream:_o,TextEncoder:vo}=w.global,yo=(i,...e)=>{try{return!!i(...e)}catch{return!1}},Nc=i=>{i=w.merge.call({skipUndefined:!0},Tc,i);const{fetch:e,Request:t,Response:s}=i,n=e?ki(e):typeof fetch=="function",o=ki(t),r=ki(s);if(!n)return!1;const a=n&&ki(_o),l=n&&(typeof vo=="function"?(p=>g=>p.encode(g))(new vo):async p=>new Uint8Array(await new t(p).arrayBuffer())),d=o&&a&&yo(()=>{let p=!1;const g=new t(ce.origin,{body:new _o,method:"POST",get duplex(){return p=!0,"half"}}).headers.has("Content-Type");return p&&!g}),c=r&&a&&yo(()=>w.isReadableStream(new s("").body)),h={stream:c&&(p=>p.body)};n&&["text","arrayBuffer","blob","formData","stream"].forEach(p=>{!h[p]&&(h[p]=(g,_)=>{let E=g&&g[p];if(E)return E.call(g);throw new z(`Response type '${p}' is not supported`,z.ERR_NOT_SUPPORT,_)})});const m=async p=>{if(p==null)return 0;if(w.isBlob(p))return p.size;if(w.isSpecCompliantForm(p))return(await new t(ce.origin,{method:"POST",body:p}).arrayBuffer()).byteLength;if(w.isArrayBufferView(p)||w.isArrayBuffer(p))return p.byteLength;if(w.isURLSearchParams(p)&&(p=p+""),w.isString(p))return(await l(p)).byteLength},b=async(p,g)=>{const _=w.toFiniteNumber(p.getContentLength());return _??m(g)};return async p=>{let{url:g,method:_,data:E,signal:k,cancelToken:A,timeout:S,onDownloadProgress:R,onUploadProgress:U,responseType:P,headers:J,withCredentials:X="same-origin",fetchOptions:V}=qr(p),_e=e||fetch;P=P?(P+"").toLowerCase():"text";let ne=Cc([k,A&&A.toAbortSignal()],S),u=null;const T=ne&&ne.unsubscribe&&(()=>{ne.unsubscribe()});let F;try{if(U&&d&&_!=="get"&&_!=="head"&&(F=await b(J,E))!==0){let q=new t(g,{method:"POST",body:E,duplex:"half"}),ze;if(w.isFormData(E)&&(ze=q.headers.get("content-type"))&&J.setContentType(ze),q.body){const[Ns,Ci]=po(F,Ki(fo(U)));E=bo(q.body,go,Ns,Ci)}}w.isString(X)||(X=X?"include":"omit");const I=o&&"credentials"in t.prototype,M={...V,signal:ne,method:_.toUpperCase(),headers:J.normalize().toJSON(),body:E,duplex:"half",credentials:I?X:void 0};u=o&&new t(g,M);let N=await(o?_e(u,V):_e(g,M));const W=c&&(P==="stream"||P==="response");if(c&&(R||W&&T)){const q={};["status","statusText","headers"].forEach(oo=>{q[oo]=N[oo]});const ze=w.toFiniteNumber(N.headers.get("content-length")),[Ns,Ci]=R&&po(ze,Ki(fo(R),!0))||[];N=new s(bo(N.body,go,Ns,()=>{Ci&&Ci(),T&&T()}),q)}P=P||"text";let pe=await h[w.findKey(h,P)||"text"](N,p);return!W&&T&&T(),await new Promise((q,ze)=>{Ur(q,ze,{data:pe,headers:me.from(N.headers),status:N.status,statusText:N.statusText,config:p,request:u})})}catch(I){throw T&&T(),I&&I.name==="TypeError"&&/Load failed|fetch/i.test(I.message)?Object.assign(new z("Network Error",z.ERR_NETWORK,p,u),{cause:I.cause||I}):z.from(I,I&&I.code,p,u)}}},Oc=new Map,jr=i=>{let e=i&&i.env||{};const{fetch:t,Request:s,Response:n}=e,o=[s,n,t];let r=o.length,a=r,l,d,c=Oc;for(;a--;)l=o[a],d=c.get(l),d===void 0&&c.set(l,d=a?new Map:Nc(e)),c=d;return d};jr();const Fn={http:Kl,xhr:Ec,fetch:{get:jr}};w.forEach(Fn,(i,e)=>{if(i){try{Object.defineProperty(i,"name",{value:e})}catch{}Object.defineProperty(i,"adapterName",{value:e})}});const wo=i=>`- ${i}`,Fc=i=>w.isFunction(i)||i===null||i===!1;function Lc(i,e){i=w.isArray(i)?i:[i];const{length:t}=i;let s,n;const o={};for(let r=0;r<t;r++){s=i[r];let a;if(n=s,!Fc(s)&&(n=Fn[(a=String(s)).toLowerCase()],n===void 0))throw new z(`Unknown adapter '${a}'`);if(n&&(w.isFunction(n)||(n=n.get(e))))break;o[a||"#"+r]=n}if(!n){const r=Object.entries(o).map(([l,d])=>`adapter ${l} `+(d===!1?"is not supported by the environment":"is not available in the build"));let a=t?r.length>1?`since :
`+r.map(wo).join(`
`):" "+wo(r[0]):"as no adapter specified";throw new z("There is no suitable adapter to dispatch the request "+a,"ERR_NOT_SUPPORT")}return n}const Wr={getAdapter:Lc,adapters:Fn};function Ls(i){if(i.cancelToken&&i.cancelToken.throwIfRequested(),i.signal&&i.signal.aborted)throw new $t(null,i)}function xo(i){return Ls(i),i.headers=me.from(i.headers),i.data=Fs.call(i,i.transformRequest),["post","put","patch"].indexOf(i.method)!==-1&&i.headers.setContentType("application/x-www-form-urlencoded",!1),Wr.getAdapter(i.adapter||pi.adapter,i)(i).then(function(s){return Ls(i),s.data=Fs.call(i,i.transformResponse,s),s.headers=me.from(s.headers),s},function(s){return Br(s)||(Ls(i),s&&s.response&&(s.response.data=Fs.call(i,i.transformResponse,s.response),s.response.headers=me.from(s.response.headers))),Promise.reject(s)})}const Kr="1.13.2",hs={};["object","boolean","number","function","string","symbol"].forEach((i,e)=>{hs[i]=function(s){return typeof s===i||"a"+(e<1?"n ":" ")+i}});const Eo={};hs.transitional=function(e,t,s){function n(o,r){return"[Axios v"+Kr+"] Transitional option '"+o+"'"+r+(s?". "+s:"")}return(o,r,a)=>{if(e===!1)throw new z(n(r," has been removed"+(t?" in "+t:"")),z.ERR_DEPRECATED);return t&&!Eo[r]&&(Eo[r]=!0,console.warn(n(r," has been deprecated since v"+t+" and will be removed in the near future"))),e?e(o,r,a):!0}};hs.spelling=function(e){return(t,s)=>(console.warn(`${s} is likely a misspelling of ${e}`),!0)};function $c(i,e,t){if(typeof i!="object")throw new z("options must be an object",z.ERR_BAD_OPTION_VALUE);const s=Object.keys(i);let n=s.length;for(;n-- >0;){const o=s[n],r=e[o];if(r){const a=i[o],l=a===void 0||r(a,o,i);if(l!==!0)throw new z("option "+o+" must be "+l,z.ERR_BAD_OPTION_VALUE);continue}if(t!==!0)throw new z("Unknown option "+o,z.ERR_BAD_OPTION)}}const Vi={assertOptions:$c,validators:hs},Ie=Vi.validators;let ut=class{constructor(e){this.defaults=e||{},this.interceptors={request:new uo,response:new uo}}async request(e,t){try{return await this._request(e,t)}catch(s){if(s instanceof Error){let n={};Error.captureStackTrace?Error.captureStackTrace(n):n=new Error;const o=n.stack?n.stack.replace(/^.+\n/,""):"";try{s.stack?o&&!String(s.stack).endsWith(o.replace(/^.+\n.+\n/,""))&&(s.stack+=`
`+o):s.stack=o}catch{}}throw s}}_request(e,t){typeof e=="string"?(t=t||{},t.url=e):t=e||{},t=pt(this.defaults,t);const{transitional:s,paramsSerializer:n,headers:o}=t;s!==void 0&&Vi.assertOptions(s,{silentJSONParsing:Ie.transitional(Ie.boolean),forcedJSONParsing:Ie.transitional(Ie.boolean),clarifyTimeoutError:Ie.transitional(Ie.boolean)},!1),n!=null&&(w.isFunction(n)?t.paramsSerializer={serialize:n}:Vi.assertOptions(n,{encode:Ie.function,serialize:Ie.function},!0)),t.allowAbsoluteUrls!==void 0||(this.defaults.allowAbsoluteUrls!==void 0?t.allowAbsoluteUrls=this.defaults.allowAbsoluteUrls:t.allowAbsoluteUrls=!0),Vi.assertOptions(t,{baseUrl:Ie.spelling("baseURL"),withXsrfToken:Ie.spelling("withXSRFToken")},!0),t.method=(t.method||this.defaults.method||"get").toLowerCase();let r=o&&w.merge(o.common,o[t.method]);o&&w.forEach(["delete","get","head","post","put","patch","common"],p=>{delete o[p]}),t.headers=me.concat(r,o);const a=[];let l=!0;this.interceptors.request.forEach(function(g){typeof g.runWhen=="function"&&g.runWhen(t)===!1||(l=l&&g.synchronous,a.unshift(g.fulfilled,g.rejected))});const d=[];this.interceptors.response.forEach(function(g){d.push(g.fulfilled,g.rejected)});let c,h=0,m;if(!l){const p=[xo.bind(this),void 0];for(p.unshift(...a),p.push(...d),m=p.length,c=Promise.resolve(t);h<m;)c=c.then(p[h++],p[h++]);return c}m=a.length;let b=t;for(;h<m;){const p=a[h++],g=a[h++];try{b=p(b)}catch(_){g.call(this,_);break}}try{c=xo.call(this,b)}catch(p){return Promise.reject(p)}for(h=0,m=d.length;h<m;)c=c.then(d[h++],d[h++]);return c}getUri(e){e=pt(this.defaults,e);const t=Hr(e.baseURL,e.url,e.allowAbsoluteUrls);return Vr(t,e.params,e.paramsSerializer)}};w.forEach(["delete","get","head","options"],function(e){ut.prototype[e]=function(t,s){return this.request(pt(s||{},{method:e,url:t,data:(s||{}).data}))}});w.forEach(["post","put","patch"],function(e){function t(s){return function(o,r,a){return this.request(pt(a||{},{method:e,headers:s?{"Content-Type":"multipart/form-data"}:{},url:o,data:r}))}}ut.prototype[e]=t(),ut.prototype[e+"Form"]=t(!0)});let Rc=class Gr{constructor(e){if(typeof e!="function")throw new TypeError("executor must be a function.");let t;this.promise=new Promise(function(o){t=o});const s=this;this.promise.then(n=>{if(!s._listeners)return;let o=s._listeners.length;for(;o-- >0;)s._listeners[o](n);s._listeners=null}),this.promise.then=n=>{let o;const r=new Promise(a=>{s.subscribe(a),o=a}).then(n);return r.cancel=function(){s.unsubscribe(o)},r},e(function(o,r,a){s.reason||(s.reason=new $t(o,r,a),t(s.reason))})}throwIfRequested(){if(this.reason)throw this.reason}subscribe(e){if(this.reason){e(this.reason);return}this._listeners?this._listeners.push(e):this._listeners=[e]}unsubscribe(e){if(!this._listeners)return;const t=this._listeners.indexOf(e);t!==-1&&this._listeners.splice(t,1)}toAbortSignal(){const e=new AbortController,t=s=>{e.abort(s)};return this.subscribe(t),e.signal.unsubscribe=()=>this.unsubscribe(t),e.signal}static source(){let e;return{token:new Gr(function(n){e=n}),cancel:e}}};function Ic(i){return function(t){return i.apply(null,t)}}function Dc(i){return w.isObject(i)&&i.isAxiosError===!0}const dn={Continue:100,SwitchingProtocols:101,Processing:102,EarlyHints:103,Ok:200,Created:201,Accepted:202,NonAuthoritativeInformation:203,NoContent:204,ResetContent:205,PartialContent:206,MultiStatus:207,AlreadyReported:208,ImUsed:226,MultipleChoices:300,MovedPermanently:301,Found:302,SeeOther:303,NotModified:304,UseProxy:305,Unused:306,TemporaryRedirect:307,PermanentRedirect:308,BadRequest:400,Unauthorized:401,PaymentRequired:402,Forbidden:403,NotFound:404,MethodNotAllowed:405,NotAcceptable:406,ProxyAuthenticationRequired:407,RequestTimeout:408,Conflict:409,Gone:410,LengthRequired:411,PreconditionFailed:412,PayloadTooLarge:413,UriTooLong:414,UnsupportedMediaType:415,RangeNotSatisfiable:416,ExpectationFailed:417,ImATeapot:418,MisdirectedRequest:421,UnprocessableEntity:422,Locked:423,FailedDependency:424,TooEarly:425,UpgradeRequired:426,PreconditionRequired:428,TooManyRequests:429,RequestHeaderFieldsTooLarge:431,UnavailableForLegalReasons:451,InternalServerError:500,NotImplemented:501,BadGateway:502,ServiceUnavailable:503,GatewayTimeout:504,HttpVersionNotSupported:505,VariantAlsoNegotiates:506,InsufficientStorage:507,LoopDetected:508,NotExtended:510,NetworkAuthenticationRequired:511,WebServerIsDown:521,ConnectionTimedOut:522,OriginIsUnreachable:523,TimeoutOccurred:524,SslHandshakeFailed:525,InvalidSslCertificate:526};Object.entries(dn).forEach(([i,e])=>{dn[e]=i});function Yr(i){const e=new ut(i),t=Sr(ut.prototype.request,e);return w.extend(t,ut.prototype,e,{allOwnKeys:!0}),w.extend(t,e,null,{allOwnKeys:!0}),t.create=function(n){return Yr(pt(i,n))},t}const te=Yr(pi);te.Axios=ut;te.CanceledError=$t;te.CancelToken=Rc;te.isCancel=Br;te.VERSION=Kr;te.toFormData=us;te.AxiosError=z;te.Cancel=te.CanceledError;te.all=function(e){return Promise.all(e)};te.spread=Ic;te.isAxiosError=Dc;te.mergeConfig=pt;te.AxiosHeaders=me;te.formToJSON=i=>zr(w.isHTMLForm(i)?new FormData(i):i);te.getAdapter=Wr.getAdapter;te.HttpStatusCode=dn;te.default=te;const{Axios:vm,AxiosError:ym,CanceledError:wm,isCancel:xm,CancelToken:Em,VERSION:Cm,all:km,Cancel:Sm,isAxiosError:Am,spread:Tm,toFormData:Nm,AxiosHeaders:Om,HttpStatusCode:Fm,formToJSON:Lm,getAdapter:$m,mergeConfig:Rm}=te;var un="",hn="";function Co(i){un=i}function Mc(i=""){if(!un){const e=document.querySelector("[data-webawesome]");if(e?.hasAttribute("data-webawesome")){const t=new URL(e.getAttribute("data-webawesome")??"",window.location.href).pathname;Co(t)}else{const s=[...document.getElementsByTagName("script")].find(n=>n.src.endsWith("webawesome.js")||n.src.endsWith("webawesome.loader.js")||n.src.endsWith("webawesome.ssr-loader.js"));if(s){const n=String(s.getAttribute("src"));Co(n.split("/").slice(0,-1).join("/"))}}}return un.replace(/\/$/,"")+(i?`/${i.replace(/^\//,"")}`:"")}function Vc(i){hn=i}function Pc(){if(!hn){const i=document.querySelector("[data-fa-kit-code]");i&&Vc(i.getAttribute("data-fa-kit-code")||"")}return hn}var Be="7.0.1";function zc(i,e,t){const s=Pc(),n=s.length>0;let o="solid";return e==="notdog"?(t==="solid"&&(o="solid"),t==="duo-solid"&&(o="duo-solid"),`https://ka-p.fontawesome.com/releases/v${Be}/svgs/notdog-${o}/${i}.svg?token=${encodeURIComponent(s)}`):e==="chisel"?`https://ka-p.fontawesome.com/releases/v${Be}/svgs/chisel-regular/${i}.svg?token=${encodeURIComponent(s)}`:e==="etch"?`https://ka-p.fontawesome.com/releases/v${Be}/svgs/etch-solid/${i}.svg?token=${encodeURIComponent(s)}`:e==="jelly"?(t==="regular"&&(o="regular"),t==="duo-regular"&&(o="duo-regular"),t==="fill-regular"&&(o="fill-regular"),`https://ka-p.fontawesome.com/releases/v${Be}/svgs/jelly-${o}/${i}.svg?token=${encodeURIComponent(s)}`):e==="slab"?((t==="solid"||t==="regular")&&(o="regular"),t==="press-regular"&&(o="press-regular"),`https://ka-p.fontawesome.com/releases/v${Be}/svgs/slab-${o}/${i}.svg?token=${encodeURIComponent(s)}`):e==="thumbprint"?`https://ka-p.fontawesome.com/releases/v${Be}/svgs/thumbprint-light/${i}.svg?token=${encodeURIComponent(s)}`:e==="whiteboard"?`https://ka-p.fontawesome.com/releases/v${Be}/svgs/whiteboard-semibold/${i}.svg?token=${encodeURIComponent(s)}`:(e==="classic"&&(t==="thin"&&(o="thin"),t==="light"&&(o="light"),t==="regular"&&(o="regular"),t==="solid"&&(o="solid")),e==="sharp"&&(t==="thin"&&(o="sharp-thin"),t==="light"&&(o="sharp-light"),t==="regular"&&(o="sharp-regular"),t==="solid"&&(o="sharp-solid")),e==="duotone"&&(t==="thin"&&(o="duotone-thin"),t==="light"&&(o="duotone-light"),t==="regular"&&(o="duotone-regular"),t==="solid"&&(o="duotone")),e==="sharp-duotone"&&(t==="thin"&&(o="sharp-duotone-thin"),t==="light"&&(o="sharp-duotone-light"),t==="regular"&&(o="sharp-duotone-regular"),t==="solid"&&(o="sharp-duotone-solid")),e==="brands"&&(o="brands"),n?`https://ka-p.fontawesome.com/releases/v${Be}/svgs/${o}/${i}.svg?token=${encodeURIComponent(s)}`:`https://ka-f.fontawesome.com/releases/v${Be}/svgs/${o}/${i}.svg`)}var Bc={name:"default",resolver:(i,e="classic",t="solid")=>zc(i,e,t),mutator:(i,e)=>{if(e?.family&&!i.hasAttribute("data-duotone-initialized")){const{family:t,variant:s}=e;if(t==="duotone"||t==="sharp-duotone"||t==="notdog"&&s==="duo-solid"||t==="jelly"&&s==="duo-regular"||t==="thumbprint"){const n=[...i.querySelectorAll("path")],o=n.find(a=>!a.hasAttribute("opacity")),r=n.find(a=>a.hasAttribute("opacity"));if(!o||!r)return;if(o.setAttribute("data-duotone-primary",""),r.setAttribute("data-duotone-secondary",""),e.swapOpacity&&o&&r){const a=r.getAttribute("opacity")||"0.4";o.style.setProperty("--path-opacity",a),r.style.setProperty("--path-opacity","1")}i.setAttribute("data-duotone-initialized","")}}}},Uc=Bc;new MutationObserver(i=>{for(const{addedNodes:e}of i)for(const t of e)t.nodeType===Node.ELEMENT_NODE&&Hc(t)});async function Hc(i){const e=i instanceof Element?i.tagName.toLowerCase():"",t=e?.startsWith("wa-"),s=[...i.querySelectorAll(":not(:defined)")].map(r=>r.tagName.toLowerCase()).filter(r=>r.startsWith("wa-"));t&&!customElements.get(e)&&s.push(e);const n=[...new Set(s)],o=await Promise.allSettled(n.map(r=>qc(r)));for(const r of o)r.status==="rejected"&&console.warn(r.reason);await new Promise(requestAnimationFrame),i.dispatchEvent(new CustomEvent("wa-discovery-complete",{bubbles:!1,cancelable:!1,composed:!0}))}function qc(i){if(customElements.get(i))return Promise.resolve();const e=i.replace(/^wa-/i,""),t=Mc(`components/${e}/${e}.js`);return new Promise((s,n)=>{import(t).then(()=>s()).catch(()=>n(new Error(`Unable to autoload <${i}> from ${t}`)))})}const pn=new Set,Et=new Map;let rt,Ln="ltr",$n="en";const Xr=typeof MutationObserver<"u"&&typeof document<"u"&&typeof document.documentElement<"u";if(Xr){const i=new MutationObserver(Jr);Ln=document.documentElement.dir||"ltr",$n=document.documentElement.lang||navigator.language,i.observe(document.documentElement,{attributes:!0,attributeFilter:["dir","lang"]})}function Zr(...i){i.map(e=>{const t=e.$code.toLowerCase();Et.has(t)?Et.set(t,Object.assign(Object.assign({},Et.get(t)),e)):Et.set(t,e),rt||(rt=e)}),Jr()}function Jr(){Xr&&(Ln=document.documentElement.dir||"ltr",$n=document.documentElement.lang||navigator.language),[...pn.keys()].map(i=>{typeof i.requestUpdate=="function"&&i.requestUpdate()})}let jc=class{constructor(e){this.host=e,this.host.addController(this)}hostConnected(){pn.add(this.host)}hostDisconnected(){pn.delete(this.host)}dir(){return`${this.host.dir||Ln}`.toLowerCase()}lang(){return`${this.host.lang||$n}`.toLowerCase()}getTranslationData(e){var t,s;const n=new Intl.Locale(e.replace(/_/g,"-")),o=n?.language.toLowerCase(),r=(s=(t=n?.region)===null||t===void 0?void 0:t.toLowerCase())!==null&&s!==void 0?s:"",a=Et.get(`${o}-${r}`),l=Et.get(o);return{locale:n,language:o,region:r,primary:a,secondary:l}}exists(e,t){var s;const{primary:n,secondary:o}=this.getTranslationData((s=t.lang)!==null&&s!==void 0?s:this.lang());return t=Object.assign({includeFallback:!1},t),!!(n&&n[e]||o&&o[e]||t.includeFallback&&rt&&rt[e])}term(e,...t){const{primary:s,secondary:n}=this.getTranslationData(this.lang());let o;if(s&&s[e])o=s[e];else if(n&&n[e])o=n[e];else if(rt&&rt[e])o=rt[e];else return console.error(`No translation found for: ${String(e)}`),String(e);return typeof o=="function"?o(...t):o}date(e,t){return e=new Date(e),new Intl.DateTimeFormat(this.lang(),t).format(e)}number(e,t){return e=Number(e),isNaN(e)?"":new Intl.NumberFormat(this.lang(),t).format(e)}relativeTime(e,t,s){return new Intl.RelativeTimeFormat(this.lang(),s).format(e,t)}};var Qr={$code:"en",$name:"English",$dir:"ltr",carousel:"Carousel",clearEntry:"Clear entry",close:"Close",copied:"Copied",copy:"Copy",currentValue:"Current value",error:"Error",goToSlide:(i,e)=>`Go to slide ${i} of ${e}`,hidePassword:"Hide password",loading:"Loading",nextSlide:"Next slide",numOptionsSelected:i=>i===0?"No options selected":i===1?"1 option selected":`${i} options selected`,pauseAnimation:"Pause animation",playAnimation:"Play animation",previousSlide:"Previous slide",progress:"Progress",remove:"Remove",resize:"Resize",scrollableRegion:"Scrollable region",scrollToEnd:"Scroll to end",scrollToStart:"Scroll to start",selectAColorFromTheScreen:"Select a color from the screen",showPassword:"Show password",slideNum:i=>`Slide ${i}`,toggleColorFormat:"Toggle color format",zoomIn:"Zoom in",zoomOut:"Zoom out"};Zr(Qr);var Wc=Qr;var Rt=class extends jc{};Zr(Wc);function Kc(i){return`data:image/svg+xml,${encodeURIComponent(i)}`}var $s={solid:{check:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M434.8 70.1c14.3 10.4 17.5 30.4 7.1 44.7l-256 352c-5.5 7.6-14 12.3-23.4 13.1s-18.5-2.7-25.1-9.3l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l101.5 101.5 234-321.7c10.4-14.3 30.4-17.5 44.7-7.1z"/></svg>',"chevron-down":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M201.4 406.6c12.5 12.5 32.8 12.5 45.3 0l192-192c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L224 338.7 54.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l192 192z"/></svg>',"chevron-left":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l192 192c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L77.3 256 246.6 86.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-192 192z"/></svg>',"chevron-right":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M311.1 233.4c12.5 12.5 12.5 32.8 0 45.3l-192 192c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L243.2 256 73.9 86.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l192 192z"/></svg>',circle:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0z"/></svg>',eyedropper:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M341.6 29.2l-101.6 101.6-9.4-9.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3l-9.4-9.4 101.6-101.6c39-39 39-102.2 0-141.1s-102.2-39-141.1 0zM55.4 323.3c-15 15-23.4 35.4-23.4 56.6l0 42.4-26.6 39.9c-8.5 12.7-6.8 29.6 4 40.4s27.7 12.5 40.4 4l39.9-26.6 42.4 0c21.2 0 41.6-8.4 56.6-23.4l109.4-109.4-45.3-45.3-109.4 109.4c-3 3-7.1 4.7-11.3 4.7l-36.1 0 0-36.1c0-4.2 1.7-8.3 4.7-11.3l109.4-109.4-45.3-45.3-109.4 109.4z"/></svg>',"grip-vertical":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M128 40c0-22.1-17.9-40-40-40L40 0C17.9 0 0 17.9 0 40L0 88c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zm0 192c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zM0 424l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zM320 40c0-22.1-17.9-40-40-40L232 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48zM192 232l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zM320 424c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48z"/></svg>',indeterminate:'<svg part="indeterminate-icon" class="icon" viewBox="0 0 16 16"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" stroke-linecap="round"><g stroke="currentColor" stroke-width="2"><g transform="translate(2.285714 6.857143)"><path d="M10.2857143,1.14285714 L1.14285714,1.14285714"/></g></g></g></svg>',minus:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M0 256c0-17.7 14.3-32 32-32l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 288c-17.7 0-32-14.3-32-32z"/></svg>',pause:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M48 32C21.5 32 0 53.5 0 80L0 432c0 26.5 21.5 48 48 48l64 0c26.5 0 48-21.5 48-48l0-352c0-26.5-21.5-48-48-48L48 32zm224 0c-26.5 0-48 21.5-48 48l0 352c0 26.5 21.5 48 48 48l64 0c26.5 0 48-21.5 48-48l0-352c0-26.5-21.5-48-48-48l-64 0z"/></svg>',play:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M91.2 36.9c-12.4-6.8-27.4-6.5-39.6 .7S32 57.9 32 72l0 368c0 14.1 7.5 27.2 19.6 34.4s27.2 7.5 39.6 .7l336-184c12.8-7 20.8-20.5 20.8-35.1s-8-28.1-20.8-35.1l-336-184z"/></svg>',star:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M309.5-18.9c-4.1-8-12.4-13.1-21.4-13.1s-17.3 5.1-21.4 13.1L193.1 125.3 33.2 150.7c-8.9 1.4-16.3 7.7-19.1 16.3s-.5 18 5.8 24.4l114.4 114.5-25.2 159.9c-1.4 8.9 2.3 17.9 9.6 23.2s16.9 6.1 25 2L288.1 417.6 432.4 491c8 4.1 17.7 3.3 25-2s11-14.2 9.6-23.2L441.7 305.9 556.1 191.4c6.4-6.4 8.6-15.8 5.8-24.4s-10.1-14.9-19.1-16.3L383 125.3 309.5-18.9z"/></svg>',user:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M224 248a120 120 0 1 0 0-240 120 120 0 1 0 0 240zm-29.7 56C95.8 304 16 383.8 16 482.3 16 498.7 29.3 512 45.7 512l356.6 0c16.4 0 29.7-13.3 29.7-29.7 0-98.5-79.8-178.3-178.3-178.3l-59.4 0z"/></svg>',xmark:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M55.1 73.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L147.2 256 9.9 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192.5 301.3 329.9 438.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.8 256 375.1 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192.5 210.7 55.1 73.4z"/></svg>'},regular:{"circle-question":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M464 256a208 208 0 1 0 -416 0 208 208 0 1 0 416 0zM0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0zm256-80c-17.7 0-32 14.3-32 32 0 13.3-10.7 24-24 24s-24-10.7-24-24c0-44.2 35.8-80 80-80s80 35.8 80 80c0 47.2-36 67.2-56 74.5l0 3.8c0 13.3-10.7 24-24 24s-24-10.7-24-24l0-8.1c0-20.5 14.8-35.2 30.1-40.2 6.4-2.1 13.2-5.5 18.2-10.3 4.3-4.2 7.7-10 7.7-19.6 0-17.7-14.3-32-32-32zM224 368a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>',"circle-xmark":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M256 48a208 208 0 1 1 0 416 208 208 0 1 1 0-416zm0 464a256 256 0 1 0 0-512 256 256 0 1 0 0 512zM167 167c-9.4 9.4-9.4 24.6 0 33.9l55 55-55 55c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l55-55 55 55c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-55-55 55-55c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-55 55-55-55c-9.4-9.4-24.6-9.4-33.9 0z"/></svg>',copy:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M384 336l-192 0c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l133.5 0c4.2 0 8.3 1.7 11.3 4.7l58.5 58.5c3 3 4.7 7.1 4.7 11.3L400 320c0 8.8-7.2 16-16 16zM192 384l192 0c35.3 0 64-28.7 64-64l0-197.5c0-17-6.7-33.3-18.7-45.3L370.7 18.7C358.7 6.7 342.5 0 325.5 0L192 0c-35.3 0-64 28.7-64 64l0 256c0 35.3 28.7 64 64 64zM64 128c-35.3 0-64 28.7-64 64L0 448c0 35.3 28.7 64 64 64l192 0c35.3 0 64-28.7 64-64l0-16-48 0 0 16c0 8.8-7.2 16-16 16L64 464c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l16 0 0-48-16 0z"/></svg>',eye:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M288 80C222.8 80 169.2 109.6 128.1 147.7 89.6 183.5 63 226 49.4 256 63 286 89.6 328.5 128.1 364.3 169.2 402.4 222.8 432 288 432s118.8-29.6 159.9-67.7C486.4 328.5 513 286 526.6 256 513 226 486.4 183.5 447.9 147.7 406.8 109.6 353.2 80 288 80zM95.4 112.6C142.5 68.8 207.2 32 288 32s145.5 36.8 192.6 80.6c46.8 43.5 78.1 95.4 93 131.1 3.3 7.9 3.3 16.7 0 24.6-14.9 35.7-46.2 87.7-93 131.1-47.1 43.7-111.8 80.6-192.6 80.6S142.5 443.2 95.4 399.4c-46.8-43.5-78.1-95.4-93-131.1-3.3-7.9-3.3-16.7 0-24.6 14.9-35.7 46.2-87.7 93-131.1zM288 336c44.2 0 80-35.8 80-80 0-29.6-16.1-55.5-40-69.3-1.4 59.7-49.6 107.9-109.3 109.3 13.8 23.9 39.7 40 69.3 40zm-79.6-88.4c2.5 .3 5 .4 7.6 .4 35.3 0 64-28.7 64-64 0-2.6-.2-5.1-.4-7.6-37.4 3.9-67.2 33.7-71.1 71.1zm45.6-115c10.8-3 22.2-4.5 33.9-4.5 8.8 0 17.5 .9 25.8 2.6 .3 .1 .5 .1 .8 .2 57.9 12.2 101.4 63.7 101.4 125.2 0 70.7-57.3 128-128 128-61.6 0-113-43.5-125.2-101.4-1.8-8.6-2.8-17.5-2.8-26.6 0-11 1.4-21.8 4-32 .2-.7 .3-1.3 .5-1.9 11.9-43.4 46.1-77.6 89.5-89.5z"/></svg>',"eye-slash":'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M41-24.9c-9.4-9.4-24.6-9.4-33.9 0S-2.3-.3 7 9.1l528 528c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-96.4-96.4c2.7-2.4 5.4-4.8 8-7.2 46.8-43.5 78.1-95.4 93-131.1 3.3-7.9 3.3-16.7 0-24.6-14.9-35.7-46.2-87.7-93-131.1-47.1-43.7-111.8-80.6-192.6-80.6-56.8 0-105.6 18.2-146 44.2L41-24.9zM176.9 111.1c32.1-18.9 69.2-31.1 111.1-31.1 65.2 0 118.8 29.6 159.9 67.7 38.5 35.7 65.1 78.3 78.6 108.3-13.6 30-40.2 72.5-78.6 108.3-3.1 2.8-6.2 5.6-9.4 8.4L393.8 328c14-20.5 22.2-45.3 22.2-72 0-70.7-57.3-128-128-128-26.7 0-51.5 8.2-72 22.2l-39.1-39.1zm182 182l-108-108c11.1-5.8 23.7-9.1 37.1-9.1 44.2 0 80 35.8 80 80 0 13.4-3.3 26-9.1 37.1zM103.4 173.2l-34-34c-32.6 36.8-55 75.8-66.9 104.5-3.3 7.9-3.3 16.7 0 24.6 14.9 35.7 46.2 87.7 93 131.1 47.1 43.7 111.8 80.6 192.6 80.6 37.3 0 71.2-7.9 101.5-20.6L352.2 422c-20 6.4-41.4 10-64.2 10-65.2 0-118.8-29.6-159.9-67.7-38.5-35.7-65.1-78.3-78.6-108.3 10.4-23.1 28.6-53.6 54-82.8z"/></svg>',star:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Free 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path fill="currentColor" d="M288.1-32c9 0 17.3 5.1 21.4 13.1L383 125.3 542.9 150.7c8.9 1.4 16.3 7.7 19.1 16.3s.5 18-5.8 24.4L441.7 305.9 467 465.8c1.4 8.9-2.3 17.9-9.6 23.2s-17 6.1-25 2L288.1 417.6 143.8 491c-8 4.1-17.7 3.3-25-2s-11-14.2-9.6-23.2L134.4 305.9 20 191.4c-6.4-6.4-8.6-15.8-5.8-24.4s10.1-14.9 19.1-16.3l159.9-25.4 73.6-144.2c4.1-8 12.4-13.1 21.4-13.1zm0 76.8L230.3 158c-3.5 6.8-10 11.6-17.6 12.8l-125.5 20 89.8 89.9c5.4 5.4 7.9 13.1 6.7 20.7l-19.8 125.5 113.3-57.6c6.8-3.5 14.9-3.5 21.8 0l113.3 57.6-19.8-125.5c-1.2-7.6 1.3-15.3 6.7-20.7l89.8-89.9-125.5-20c-7.6-1.2-14.1-6-17.6-12.8L288.1 44.8z"/></svg>'}},Gc={name:"system",resolver:(i,e="classic",t="solid")=>{let n=$s[t][i]??$s.regular[i]??$s.regular["circle-question"];return n?Kc(n):""}},Yc=Gc;var Xc="classic",Gi=[Uc,Yc],Yi=[];function Zc(i){Yi.push(i)}function Jc(i){Yi=Yi.filter(e=>e!==i)}function Rs(i){return Gi.find(e=>e.name===i)}function Qc(i,e){ed(i),Gi.push({name:i,resolver:e.resolver,mutator:e.mutator,spriteSheet:e.spriteSheet}),Yi.forEach(t=>{t.library===i&&t.setIcon()})}function ed(i){Gi=Gi.filter(e=>e.name!==i)}function td(){return Xc}var id=Object.defineProperty,sd=Object.getOwnPropertyDescriptor,ea=i=>{throw TypeError(i)},v=(i,e,t,s)=>{for(var n=s>1?void 0:s?sd(e,t):e,o=i.length-1,r;o>=0;o--)(r=i[o])&&(n=(s?r(e,t,n):r(n))||n);return s&&n&&id(e,t,n),n},ta=(i,e,t)=>e.has(i)||ea("Cannot "+t),nd=(i,e,t)=>(ta(i,e,"read from private field"),e.get(i)),od=(i,e,t)=>e.has(i)?ea("Cannot add the same private member more than once"):e instanceof WeakSet?e.add(i):e.set(i,t),rd=(i,e,t,s)=>(ta(i,e,"write to private field"),e.set(i,t),t);const ad={alert:"triangle-exclamation",asc:"arrow-down-short-wide",asset:"image",assets:"image",circleuarr:"circle-arrow-up",collapse:"down-left-and-up-right-to-center",condition:"diamond",darr:"arrow-down",date:"calendar",desc:"arrow-down-wide-short",disabled:"circle-dashed",done:"circle-check",downangle:"angle-down",draft:"scribble",edit:"pencil",enabled:"circle",expand:"up-right-and-down-left-from-center",external:"arrow-up-right-from-square",field:"pen-to-square",help:"circle-question",home:"house",info:"circle-info",insecure:"unlock",larr:"arrow-left",layout:"table-layout",leftangle:"angle-left",listrtl:"list-flip",location:"location-dot",mail:"envelope",menu:"bars",move:"grip-dots",newstamp:"certificate",paperplane:"paper-plane",plugin:"plug",rarr:"arrow-right",refresh:"arrows-rotate",remove:"xmark",rightangle:"angle-right",rotate:"rotate-left",routes:"signs-post",search:"magnifying-glass",secure:"lock",settings:"gear",shareleft:"share-flip",shuteye:"eye-slash","sidebar-left":"sidebar","sidebar-right":"sidebar-flip","sidebar-start":"sidebar","sidebar-end":"sidebar-flip",structure:"list-tree",structurertl:"list-tree-flip",template:"file-code",time:"clock",tool:"wrench",uarr:"arrow-up",upangle:"angle-up",view:"eye",wand:"wand-magic-sparkles"};function ld(i,e="classic",t="regular"){let s="solid",n=t,o=i.endsWith(".svg")?i.split(".svg")[0]:i;if(i.includes("/")){let[r,...a]=i.split("/");n=r??n,o=a.join("/")}return n==="thin"?s="thin":n==="light"?s="light":n==="regular"?s="regular":n==="solid"&&(s="solid"),e==="brands"&&(s="brands"),n==="custom-icons"&&(s="custom-icons"),o=ad[o]??o,`/vendor/craft/icons/${s}/${o}.svg`}function cd(){Qc("default",{resolver:(i,e="classic",t="solid")=>ld(i,e,t),mutator:i=>i.setAttribute("fill","currentColor")})}var ko=class extends HTMLElement{constructor(...e){super(...e),this.cookieName=null,this.state="collapsed",this.expanded=!1,this.handleOpen=()=>{this.trigger?.setAttribute("aria-expanded","true"),this.expanded=!0,this.dispatchEvent(new CustomEvent("open")),this.target&&(this.target.dataset.state="expanded"),this.cookieName&&window.Craft?.setCookie(this.cookieName,"expanded")},this.handleClose=()=>{this.trigger?.setAttribute("aria-expanded","false"),this.expanded=!1,this.dispatchEvent(new CustomEvent("close")),this.target&&(this.target.dataset.state="collapsed"),this.cookieName&&window.Craft?.setCookie(this.cookieName,"collapsed")}}get trigger(){return this.querySelector('button[type="button"]')}get target(){if(!this.trigger)return console.warn("No trigger found for disclosure."),null;let e=this.trigger.getAttribute("aria-controls");return e?document.getElementById(e):(console.warn("No target selector found for disclosure."),null)}connectedCallback(){if(!this.trigger){console.error("craft-disclosure elements must include a button",this);return}if(!this.target){console.error(`No target with id ${this.trigger.getAttribute("aria-controls")} found for disclosure. `,this.trigger);return}this.cookieName=this.getAttribute("cookie-name"),this.state=this.getAttribute("state")??"expanded",this.trigger.setAttribute("aria-expanded",this.state==="expanded"?"true":"false"),this.trigger.addEventListener("click",this.toggle.bind(this)),this.state==="expanded"?this.open():this.close()}disconnectedCallback(){this.open(),this.trigger?.removeEventListener("click",this.toggle.bind(this))}attributeChangedCallback(e,t,s){e==="state"&&(s==="expanded"?this.handleOpen():this.handleClose())}toggle(){this.expanded?this.close():this.open()}open(){this.setAttribute("state","expanded")}close(){this.setAttribute("state","collapsed")}};ko.observedAttributes=["state"],customElements.get("craft-disclosure")||customElements.define("craft-disclosure",ko);const Pi=globalThis,Rn=Pi.ShadowRoot&&(Pi.ShadyCSS===void 0||Pi.ShadyCSS.nativeShadow)&&"adoptedStyleSheets"in Document.prototype&&"replace"in CSSStyleSheet.prototype,In=Symbol(),So=new WeakMap;let ia=class{constructor(e,t,s){if(this._$cssResult$=!0,s!==In)throw Error("CSSResult is not constructable. Use `unsafeCSS` or `css` instead.");this.cssText=e,this.t=t}get styleSheet(){let e=this.o;const t=this.t;if(Rn&&e===void 0){const s=t!==void 0&&t.length===1;s&&(e=So.get(t)),e===void 0&&((this.o=e=new CSSStyleSheet).replaceSync(this.cssText),s&&So.set(t,e))}return e}toString(){return this.cssText}};const sa=i=>new ia(typeof i=="string"?i:i+"",void 0,In),O=(i,...e)=>{const t=i.length===1?i[0]:e.reduce(((s,n,o)=>s+(r=>{if(r._$cssResult$===!0)return r.cssText;if(typeof r=="number")return r;throw Error("Value passed to 'css' function must be a 'css' function result: "+r+". Use 'unsafeCSS' to pass non-literal values, but take care to ensure page security.")})(n)+i[o+1]),i[0]);return new ia(t,i,In)},Dn=(i,e)=>{if(Rn)i.adoptedStyleSheets=e.map((t=>t instanceof CSSStyleSheet?t:t.styleSheet));else for(const t of e){const s=document.createElement("style"),n=Pi.litNonce;n!==void 0&&s.setAttribute("nonce",n),s.textContent=t.cssText,i.appendChild(s)}},Ao=Rn?i=>i:i=>i instanceof CSSStyleSheet?(e=>{let t="";for(const s of e.cssRules)t+=s.cssText;return sa(t)})(i):i;const{is:dd,defineProperty:ud,getOwnPropertyDescriptor:hd,getOwnPropertyNames:pd,getOwnPropertySymbols:fd,getPrototypeOf:md}=Object,ps=globalThis,To=ps.trustedTypes,bd=To?To.emptyScript:"",gd=ps.reactiveElementPolyfillSupport,ti=(i,e)=>i,Xi={toAttribute(i,e){switch(e){case Boolean:i=i?bd:null;break;case Object:case Array:i=i==null?i:JSON.stringify(i)}return i},fromAttribute(i,e){let t=i;switch(e){case Boolean:t=i!==null;break;case Number:t=i===null?null:Number(i);break;case Object:case Array:try{t=JSON.parse(i)}catch{t=null}}return t}},Mn=(i,e)=>!dd(i,e),No={attribute:!0,type:String,converter:Xi,reflect:!1,useDefault:!1,hasChanged:Mn};Symbol.metadata??=Symbol("metadata"),ps.litPropertyMetadata??=new WeakMap;let wt=class extends HTMLElement{static addInitializer(e){this._$Ei(),(this.l??=[]).push(e)}static get observedAttributes(){return this.finalize(),this._$Eh&&[...this._$Eh.keys()]}static createProperty(e,t=No){if(t.state&&(t.attribute=!1),this._$Ei(),this.prototype.hasOwnProperty(e)&&((t=Object.create(t)).wrapped=!0),this.elementProperties.set(e,t),!t.noAccessor){const s=Symbol(),n=this.getPropertyDescriptor(e,s,t);n!==void 0&&ud(this.prototype,e,n)}}static getPropertyDescriptor(e,t,s){const{get:n,set:o}=hd(this.prototype,e)??{get(){return this[t]},set(r){this[t]=r}};return{get:n,set(r){const a=n?.call(this);o?.call(this,r),this.requestUpdate(e,a,s)},configurable:!0,enumerable:!0}}static getPropertyOptions(e){return this.elementProperties.get(e)??No}static _$Ei(){if(this.hasOwnProperty(ti("elementProperties")))return;const e=md(this);e.finalize(),e.l!==void 0&&(this.l=[...e.l]),this.elementProperties=new Map(e.elementProperties)}static finalize(){if(this.hasOwnProperty(ti("finalized")))return;if(this.finalized=!0,this._$Ei(),this.hasOwnProperty(ti("properties"))){const t=this.properties,s=[...pd(t),...fd(t)];for(const n of s)this.createProperty(n,t[n])}const e=this[Symbol.metadata];if(e!==null){const t=litPropertyMetadata.get(e);if(t!==void 0)for(const[s,n]of t)this.elementProperties.set(s,n)}this._$Eh=new Map;for(const[t,s]of this.elementProperties){const n=this._$Eu(t,s);n!==void 0&&this._$Eh.set(n,t)}this.elementStyles=this.finalizeStyles(this.styles)}static finalizeStyles(e){const t=[];if(Array.isArray(e)){const s=new Set(e.flat(1/0).reverse());for(const n of s)t.unshift(Ao(n))}else e!==void 0&&t.push(Ao(e));return t}static _$Eu(e,t){const s=t.attribute;return s===!1?void 0:typeof s=="string"?s:typeof e=="string"?e.toLowerCase():void 0}constructor(){super(),this._$Ep=void 0,this.isUpdatePending=!1,this.hasUpdated=!1,this._$Em=null,this._$Ev()}_$Ev(){this._$ES=new Promise((e=>this.enableUpdating=e)),this._$AL=new Map,this._$E_(),this.requestUpdate(),this.constructor.l?.forEach((e=>e(this)))}addController(e){(this._$EO??=new Set).add(e),this.renderRoot!==void 0&&this.isConnected&&e.hostConnected?.()}removeController(e){this._$EO?.delete(e)}_$E_(){const e=new Map,t=this.constructor.elementProperties;for(const s of t.keys())this.hasOwnProperty(s)&&(e.set(s,this[s]),delete this[s]);e.size>0&&(this._$Ep=e)}createRenderRoot(){const e=this.shadowRoot??this.attachShadow(this.constructor.shadowRootOptions);return Dn(e,this.constructor.elementStyles),e}connectedCallback(){this.renderRoot??=this.createRenderRoot(),this.enableUpdating(!0),this._$EO?.forEach((e=>e.hostConnected?.()))}enableUpdating(e){}disconnectedCallback(){this._$EO?.forEach((e=>e.hostDisconnected?.()))}attributeChangedCallback(e,t,s){this._$AK(e,s)}_$ET(e,t){const s=this.constructor.elementProperties.get(e),n=this.constructor._$Eu(e,s);if(n!==void 0&&s.reflect===!0){const o=(s.converter?.toAttribute!==void 0?s.converter:Xi).toAttribute(t,s.type);this._$Em=e,o==null?this.removeAttribute(n):this.setAttribute(n,o),this._$Em=null}}_$AK(e,t){const s=this.constructor,n=s._$Eh.get(e);if(n!==void 0&&this._$Em!==n){const o=s.getPropertyOptions(n),r=typeof o.converter=="function"?{fromAttribute:o.converter}:o.converter?.fromAttribute!==void 0?o.converter:Xi;this._$Em=n;const a=r.fromAttribute(t,o.type);this[n]=a??this._$Ej?.get(n)??a,this._$Em=null}}requestUpdate(e,t,s){if(e!==void 0){const n=this.constructor,o=this[e];if(s??=n.getPropertyOptions(e),!((s.hasChanged??Mn)(o,t)||s.useDefault&&s.reflect&&o===this._$Ej?.get(e)&&!this.hasAttribute(n._$Eu(e,s))))return;this.C(e,t,s)}this.isUpdatePending===!1&&(this._$ES=this._$EP())}C(e,t,{useDefault:s,reflect:n,wrapped:o},r){s&&!(this._$Ej??=new Map).has(e)&&(this._$Ej.set(e,r??t??this[e]),o!==!0||r!==void 0)||(this._$AL.has(e)||(this.hasUpdated||s||(t=void 0),this._$AL.set(e,t)),n===!0&&this._$Em!==e&&(this._$Eq??=new Set).add(e))}async _$EP(){this.isUpdatePending=!0;try{await this._$ES}catch(t){Promise.reject(t)}const e=this.scheduleUpdate();return e!=null&&await e,!this.isUpdatePending}scheduleUpdate(){return this.performUpdate()}performUpdate(){if(!this.isUpdatePending)return;if(!this.hasUpdated){if(this.renderRoot??=this.createRenderRoot(),this._$Ep){for(const[n,o]of this._$Ep)this[n]=o;this._$Ep=void 0}const s=this.constructor.elementProperties;if(s.size>0)for(const[n,o]of s){const{wrapped:r}=o,a=this[n];r!==!0||this._$AL.has(n)||a===void 0||this.C(n,void 0,o,a)}}let e=!1;const t=this._$AL;try{e=this.shouldUpdate(t),e?(this.willUpdate(t),this._$EO?.forEach((s=>s.hostUpdate?.())),this.update(t)):this._$EM()}catch(s){throw e=!1,this._$EM(),s}e&&this._$AE(t)}willUpdate(e){}_$AE(e){this._$EO?.forEach((t=>t.hostUpdated?.())),this.hasUpdated||(this.hasUpdated=!0,this.firstUpdated(e)),this.updated(e)}_$EM(){this._$AL=new Map,this.isUpdatePending=!1}get updateComplete(){return this.getUpdateComplete()}getUpdateComplete(){return this._$ES}shouldUpdate(e){return!0}update(e){this._$Eq&&=this._$Eq.forEach((t=>this._$ET(t,this[t]))),this._$EM()}updated(e){}firstUpdated(e){}};wt.elementStyles=[],wt.shadowRootOptions={mode:"open"},wt[ti("elementProperties")]=new Map,wt[ti("finalized")]=new Map,gd?.({ReactiveElement:wt}),(ps.reactiveElementVersions??=[]).push("2.1.1");const Vn=globalThis,Zi=Vn.trustedTypes,Oo=Zi?Zi.createPolicy("lit-html",{createHTML:i=>i}):void 0,na="$lit$",Je=`lit$${Math.random().toFixed(9).slice(2)}$`,oa="?"+Je,_d=`<${oa}>`,ft=document,oi=()=>ft.createComment(""),ri=i=>i===null||typeof i!="object"&&typeof i!="function",Pn=Array.isArray,vd=i=>Pn(i)||typeof i?.[Symbol.iterator]=="function",Is=`[ 	
\f\r]`,Bt=/<(?:(!--|\/[^a-zA-Z])|(\/?[a-zA-Z][^>\s]*)|(\/?$))/g,Fo=/-->/g,Lo=/>/g,it=RegExp(`>|${Is}(?:([^\\s"'>=/]+)(${Is}*=${Is}*(?:[^ 	
\f\r"'\`<>=]|("|')|))|$)`,"g"),$o=/'/g,Ro=/"/g,ra=/^(?:script|style|textarea|title)$/i,yd=i=>(e,...t)=>({_$litType$:i,strings:e,values:t}),x=yd(1),Te=Symbol.for("lit-noChange"),B=Symbol.for("lit-nothing"),Io=new WeakMap,lt=ft.createTreeWalker(ft,129);function aa(i,e){if(!Pn(i)||!i.hasOwnProperty("raw"))throw Error("invalid template strings array");return Oo!==void 0?Oo.createHTML(e):e}const wd=(i,e)=>{const t=i.length-1,s=[];let n,o=e===2?"<svg>":e===3?"<math>":"",r=Bt;for(let a=0;a<t;a++){const l=i[a];let d,c,h=-1,m=0;for(;m<l.length&&(r.lastIndex=m,c=r.exec(l),c!==null);)m=r.lastIndex,r===Bt?c[1]==="!--"?r=Fo:c[1]!==void 0?r=Lo:c[2]!==void 0?(ra.test(c[2])&&(n=RegExp("</"+c[2],"g")),r=it):c[3]!==void 0&&(r=it):r===it?c[0]===">"?(r=n??Bt,h=-1):c[1]===void 0?h=-2:(h=r.lastIndex-c[2].length,d=c[1],r=c[3]===void 0?it:c[3]==='"'?Ro:$o):r===Ro||r===$o?r=it:r===Fo||r===Lo?r=Bt:(r=it,n=void 0);const b=r===it&&i[a+1].startsWith("/>")?" ":"";o+=r===Bt?l+_d:h>=0?(s.push(d),l.slice(0,h)+na+l.slice(h)+Je+b):l+Je+(h===-2?a:b)}return[aa(i,o+(i[t]||"<?>")+(e===2?"</svg>":e===3?"</math>":"")),s]};class ai{constructor({strings:e,_$litType$:t},s){let n;this.parts=[];let o=0,r=0;const a=e.length-1,l=this.parts,[d,c]=wd(e,t);if(this.el=ai.createElement(d,s),lt.currentNode=this.el.content,t===2||t===3){const h=this.el.content.firstChild;h.replaceWith(...h.childNodes)}for(;(n=lt.nextNode())!==null&&l.length<a;){if(n.nodeType===1){if(n.hasAttributes())for(const h of n.getAttributeNames())if(h.endsWith(na)){const m=c[r++],b=n.getAttribute(h).split(Je),p=/([.?@])?(.*)/.exec(m);l.push({type:1,index:o,name:p[2],strings:b,ctor:p[1]==="."?Ed:p[1]==="?"?Cd:p[1]==="@"?kd:fs}),n.removeAttribute(h)}else h.startsWith(Je)&&(l.push({type:6,index:o}),n.removeAttribute(h));if(ra.test(n.tagName)){const h=n.textContent.split(Je),m=h.length-1;if(m>0){n.textContent=Zi?Zi.emptyScript:"";for(let b=0;b<m;b++)n.append(h[b],oi()),lt.nextNode(),l.push({type:2,index:++o});n.append(h[m],oi())}}}else if(n.nodeType===8)if(n.data===oa)l.push({type:2,index:o});else{let h=-1;for(;(h=n.data.indexOf(Je,h+1))!==-1;)l.push({type:7,index:o}),h+=Je.length-1}o++}}static createElement(e,t){const s=ft.createElement("template");return s.innerHTML=e,s}}function St(i,e,t=i,s){if(e===Te)return e;let n=s!==void 0?t._$Co?.[s]:t._$Cl;const o=ri(e)?void 0:e._$litDirective$;return n?.constructor!==o&&(n?._$AO?.(!1),o===void 0?n=void 0:(n=new o(i),n._$AT(i,t,s)),s!==void 0?(t._$Co??=[])[s]=n:t._$Cl=n),n!==void 0&&(e=St(i,n._$AS(i,e.values),n,s)),e}let xd=class{constructor(e,t){this._$AV=[],this._$AN=void 0,this._$AD=e,this._$AM=t}get parentNode(){return this._$AM.parentNode}get _$AU(){return this._$AM._$AU}u(e){const{el:{content:t},parts:s}=this._$AD,n=(e?.creationScope??ft).importNode(t,!0);lt.currentNode=n;let o=lt.nextNode(),r=0,a=0,l=s[0];for(;l!==void 0;){if(r===l.index){let d;l.type===2?d=new It(o,o.nextSibling,this,e):l.type===1?d=new l.ctor(o,l.name,l.strings,this,e):l.type===6&&(d=new Sd(o,this,e)),this._$AV.push(d),l=s[++a]}r!==l?.index&&(o=lt.nextNode(),r++)}return lt.currentNode=ft,n}p(e){let t=0;for(const s of this._$AV)s!==void 0&&(s.strings!==void 0?(s._$AI(e,s,t),t+=s.strings.length-2):s._$AI(e[t])),t++}};class It{get _$AU(){return this._$AM?._$AU??this._$Cv}constructor(e,t,s,n){this.type=2,this._$AH=B,this._$AN=void 0,this._$AA=e,this._$AB=t,this._$AM=s,this.options=n,this._$Cv=n?.isConnected??!0}get parentNode(){let e=this._$AA.parentNode;const t=this._$AM;return t!==void 0&&e?.nodeType===11&&(e=t.parentNode),e}get startNode(){return this._$AA}get endNode(){return this._$AB}_$AI(e,t=this){e=St(this,e,t),ri(e)?e===B||e==null||e===""?(this._$AH!==B&&this._$AR(),this._$AH=B):e!==this._$AH&&e!==Te&&this._(e):e._$litType$!==void 0?this.$(e):e.nodeType!==void 0?this.T(e):vd(e)?this.k(e):this._(e)}O(e){return this._$AA.parentNode.insertBefore(e,this._$AB)}T(e){this._$AH!==e&&(this._$AR(),this._$AH=this.O(e))}_(e){this._$AH!==B&&ri(this._$AH)?this._$AA.nextSibling.data=e:this.T(ft.createTextNode(e)),this._$AH=e}$(e){const{values:t,_$litType$:s}=e,n=typeof s=="number"?this._$AC(e):(s.el===void 0&&(s.el=ai.createElement(aa(s.h,s.h[0]),this.options)),s);if(this._$AH?._$AD===n)this._$AH.p(t);else{const o=new xd(n,this),r=o.u(this.options);o.p(t),this.T(r),this._$AH=o}}_$AC(e){let t=Io.get(e.strings);return t===void 0&&Io.set(e.strings,t=new ai(e)),t}k(e){Pn(this._$AH)||(this._$AH=[],this._$AR());const t=this._$AH;let s,n=0;for(const o of e)n===t.length?t.push(s=new It(this.O(oi()),this.O(oi()),this,this.options)):s=t[n],s._$AI(o),n++;n<t.length&&(this._$AR(s&&s._$AB.nextSibling,n),t.length=n)}_$AR(e=this._$AA.nextSibling,t){for(this._$AP?.(!1,!0,t);e!==this._$AB;){const s=e.nextSibling;e.remove(),e=s}}setConnected(e){this._$AM===void 0&&(this._$Cv=e,this._$AP?.(e))}}class fs{get tagName(){return this.element.tagName}get _$AU(){return this._$AM._$AU}constructor(e,t,s,n,o){this.type=1,this._$AH=B,this._$AN=void 0,this.element=e,this.name=t,this._$AM=n,this.options=o,s.length>2||s[0]!==""||s[1]!==""?(this._$AH=Array(s.length-1).fill(new String),this.strings=s):this._$AH=B}_$AI(e,t=this,s,n){const o=this.strings;let r=!1;if(o===void 0)e=St(this,e,t,0),r=!ri(e)||e!==this._$AH&&e!==Te,r&&(this._$AH=e);else{const a=e;let l,d;for(e=o[0],l=0;l<o.length-1;l++)d=St(this,a[s+l],t,l),d===Te&&(d=this._$AH[l]),r||=!ri(d)||d!==this._$AH[l],d===B?e=B:e!==B&&(e+=(d??"")+o[l+1]),this._$AH[l]=d}r&&!n&&this.j(e)}j(e){e===B?this.element.removeAttribute(this.name):this.element.setAttribute(this.name,e??"")}}class Ed extends fs{constructor(){super(...arguments),this.type=3}j(e){this.element[this.name]=e===B?void 0:e}}class Cd extends fs{constructor(){super(...arguments),this.type=4}j(e){this.element.toggleAttribute(this.name,!!e&&e!==B)}}class kd extends fs{constructor(e,t,s,n,o){super(e,t,s,n,o),this.type=5}_$AI(e,t=this){if((e=St(this,e,t,0)??B)===Te)return;const s=this._$AH,n=e===B&&s!==B||e.capture!==s.capture||e.once!==s.once||e.passive!==s.passive,o=e!==B&&(s===B||n);n&&this.element.removeEventListener(this.name,this,s),o&&this.element.addEventListener(this.name,this,e),this._$AH=e}handleEvent(e){typeof this._$AH=="function"?this._$AH.call(this.options?.host??this.element,e):this._$AH.handleEvent(e)}}class Sd{constructor(e,t,s){this.element=e,this.type=6,this._$AN=void 0,this._$AM=t,this.options=s}get _$AU(){return this._$AM._$AU}_$AI(e){St(this,e)}}const Ad={I:It},Td=Vn.litHtmlPolyfillSupport;Td?.(ai,It),(Vn.litHtmlVersions??=[]).push("3.3.1");const fn=(i,e,t)=>{const s=t?.renderBefore??e;let n=s._$litPart$;if(n===void 0){const o=t?.renderBefore??null;s._$litPart$=n=new It(e.insertBefore(oi(),o),o,void 0,t??{})}return n._$AI(i),n};const zn=globalThis;let H=class extends wt{constructor(){super(...arguments),this.renderOptions={host:this},this._$Do=void 0}createRenderRoot(){const e=super.createRenderRoot();return this.renderOptions.renderBefore??=e.firstChild,e}update(e){const t=this.render();this.hasUpdated||(this.renderOptions.isConnected=this.isConnected),super.update(e),this._$Do=fn(t,this.renderRoot,this.renderOptions)}connectedCallback(){super.connectedCallback(),this._$Do?.setConnected(!0)}disconnectedCallback(){super.disconnectedCallback(),this._$Do?.setConnected(!1)}render(){return Te}};H._$litElement$=!0,H.finalized=!0,zn.litElementHydrateSupport?.({LitElement:H});const Nd=zn.litElementPolyfillSupport;Nd?.({LitElement:H});(zn.litElementVersions??=[]).push("4.2.1");const ke=i=>(e,t)=>{t!==void 0?t.addInitializer((()=>{customElements.define(i,e)})):customElements.define(i,e)};const Od={attribute:!0,type:String,converter:Xi,reflect:!1,hasChanged:Mn},Fd=(i=Od,e,t)=>{const{kind:s,metadata:n}=t;let o=globalThis.litPropertyMetadata.get(n);if(o===void 0&&globalThis.litPropertyMetadata.set(n,o=new Map),s==="setter"&&((i=Object.create(i)).wrapped=!0),o.set(t.name,i),s==="accessor"){const{name:r}=t;return{set(a){const l=e.get.call(this);e.set.call(this,a),this.requestUpdate(r,l,i)},init(a){return a!==void 0&&this.C(r,void 0,i,a),a}}}if(s==="setter"){const{name:r}=t;return function(a){const l=this[r];e.call(this,a),this.requestUpdate(r,l,i)}}throw Error("Unsupported decorator location: "+s)};function y(i){return(e,t)=>typeof t=="object"?Fd(i,e,t):((s,n,o)=>{const r=n.hasOwnProperty(o);return n.constructor.createProperty(o,s),r?Object.getOwnPropertyDescriptor(n,o):void 0})(i,e,t)}function be(i){return y({...i,state:!0,attribute:!1})}const Bn=(i,e,t)=>(t.configurable=!0,t.enumerable=!0,Reflect.decorate&&typeof e!="object"&&Object.defineProperty(i,e,t),t);function Q(i,e){return(t,s,n)=>{const o=r=>r.renderRoot?.querySelector(i)??null;return Bn(t,s,{get(){return o(this)}})}}let Ld;function $d(i){return(e,t)=>Bn(e,t,{get(){return(this.renderRoot??(Ld??=document.createDocumentFragment())).querySelectorAll(i)}})}function zi(i){return(e,t)=>{const{slot:s,selector:n}=i??{},o="slot"+(s?`[name=${s}]`:":not([name])");return Bn(e,t,{get(){const r=this.renderRoot?.querySelector(o),a=r?.assignedElements(i)??[];return n===void 0?a:a.filter((l=>l.matches(n)))}})}}var Rd=O`
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
`,Do=class extends H{render(){return x`
      <div tabindex="-1">
        <div class="spinner"></div>
        <span class="message visually-hidden"><slot /></span>
      </div>
    `}};Do.styles=[Rd],customElements.get("craft-spinner")||customElements.define("craft-spinner",Do);var Id=class extends Event{constructor(){super("wa-reposition",{bubbles:!0,cancelable:!1,composed:!0})}};var Dd=`:host {
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
`,Bi,ge=class extends H{constructor(){super(),od(this,Bi,!1),this.initialReflectedProperties=new Map,this.didSSR=!!this.shadowRoot,this.customStates={set:(e,t)=>{if(this.internals?.states)try{t?this.internals.states.add(e):this.internals.states.delete(e)}catch(s){if(String(s).includes("must start with '--'"))console.error("Your browser implements an outdated version of CustomStateSet. Consider using a polyfill");else throw s}},has:e=>{if(!this.internals?.states)return!1;try{return this.internals.states.has(e)}catch{return!1}}};try{this.internals=this.attachInternals()}catch{console.error("Element internals are not supported in your browser. Consider using a polyfill")}this.customStates.set("wa-defined",!0);let i=this.constructor;for(let[e,t]of i.elementProperties)t.default==="inherit"&&t.initial!==void 0&&typeof e=="string"&&this.customStates.set(`initial-${e}-${t.initial}`,!0)}static get styles(){const i=Array.isArray(this.css)?this.css:this.css?[this.css]:[];return[Dd,...i].map(e=>typeof e=="string"?sa(e):e)}attributeChangedCallback(i,e,t){nd(this,Bi)||(this.constructor.elementProperties.forEach((s,n)=>{s.reflect&&this[n]!=null&&this.initialReflectedProperties.set(n,this[n])}),rd(this,Bi,!0)),super.attributeChangedCallback(i,e,t)}willUpdate(i){super.willUpdate(i),this.initialReflectedProperties.forEach((e,t)=>{i.has(t)&&this[t]==null&&(this[t]=e)})}firstUpdated(i){super.firstUpdated(i),this.didSSR&&this.shadowRoot?.querySelectorAll("slot").forEach(e=>{e.dispatchEvent(new Event("slotchange",{bubbles:!0,composed:!1,cancelable:!1}))})}update(i){try{super.update(i)}catch(e){if(this.didSSR&&!this.hasUpdated){const t=new Event("lit-hydration-error",{bubbles:!0,composed:!0,cancelable:!1});t.error=e,this.dispatchEvent(t)}throw e}}relayNativeEvent(i,e){i.stopImmediatePropagation(),this.dispatchEvent(new i.constructor(i.type,{...i,...e}))}};Bi=new WeakMap;v([y()],ge.prototype,"dir",2);v([y()],ge.prototype,"lang",2);v([y({type:Boolean,reflect:!0,attribute:"did-ssr"})],ge.prototype,"didSSR",2);const Qe=Math.min,ye=Math.max,Ji=Math.round,Si=Math.floor,Me=i=>({x:i,y:i}),Md={left:"right",right:"left",bottom:"top",top:"bottom"},Vd={start:"end",end:"start"};function mn(i,e,t){return ye(i,Qe(e,t))}function Dt(i,e){return typeof i=="function"?i(e):i}function et(i){return i.split("-")[0]}function Mt(i){return i.split("-")[1]}function la(i){return i==="x"?"y":"x"}function Un(i){return i==="y"?"height":"width"}const Pd=new Set(["top","bottom"]);function He(i){return Pd.has(et(i))?"y":"x"}function Hn(i){return la(He(i))}function zd(i,e,t){t===void 0&&(t=!1);const s=Mt(i),n=Hn(i),o=Un(n);let r=n==="x"?s===(t?"end":"start")?"right":"left":s==="start"?"bottom":"top";return e.reference[o]>e.floating[o]&&(r=Qi(r)),[r,Qi(r)]}function Bd(i){const e=Qi(i);return[bn(i),e,bn(e)]}function bn(i){return i.replace(/start|end/g,e=>Vd[e])}const Mo=["left","right"],Vo=["right","left"],Ud=["top","bottom"],Hd=["bottom","top"];function qd(i,e,t){switch(i){case"top":case"bottom":return t?e?Vo:Mo:e?Mo:Vo;case"left":case"right":return e?Ud:Hd;default:return[]}}function jd(i,e,t,s){const n=Mt(i);let o=qd(et(i),t==="start",s);return n&&(o=o.map(r=>r+"-"+n),e&&(o=o.concat(o.map(bn)))),o}function Qi(i){return i.replace(/left|right|bottom|top/g,e=>Md[e])}function Wd(i){return{top:0,right:0,bottom:0,left:0,...i}}function ca(i){return typeof i!="number"?Wd(i):{top:i,right:i,bottom:i,left:i}}function es(i){const{x:e,y:t,width:s,height:n}=i;return{width:s,height:n,top:t,left:e,right:e+s,bottom:t+n,x:e,y:t}}function Po(i,e,t){let{reference:s,floating:n}=i;const o=He(e),r=Hn(e),a=Un(r),l=et(e),d=o==="y",c=s.x+s.width/2-n.width/2,h=s.y+s.height/2-n.height/2,m=s[a]/2-n[a]/2;let b;switch(l){case"top":b={x:c,y:s.y-n.height};break;case"bottom":b={x:c,y:s.y+s.height};break;case"right":b={x:s.x+s.width,y:h};break;case"left":b={x:s.x-n.width,y:h};break;default:b={x:s.x,y:s.y}}switch(Mt(e)){case"start":b[r]-=m*(t&&d?-1:1);break;case"end":b[r]+=m*(t&&d?-1:1);break}return b}const Kd=async(i,e,t)=>{const{placement:s="bottom",strategy:n="absolute",middleware:o=[],platform:r}=t,a=o.filter(Boolean),l=await(r.isRTL==null?void 0:r.isRTL(e));let d=await r.getElementRects({reference:i,floating:e,strategy:n}),{x:c,y:h}=Po(d,s,l),m=s,b={},p=0;for(let g=0;g<a.length;g++){const{name:_,fn:E}=a[g],{x:k,y:A,data:S,reset:R}=await E({x:c,y:h,initialPlacement:s,placement:m,strategy:n,middlewareData:b,rects:d,platform:r,elements:{reference:i,floating:e}});c=k??c,h=A??h,b={...b,[_]:{...b[_],...S}},R&&p<=50&&(p++,typeof R=="object"&&(R.placement&&(m=R.placement),R.rects&&(d=R.rects===!0?await r.getElementRects({reference:i,floating:e,strategy:n}):R.rects),{x:c,y:h}=Po(d,m,l)),g=-1)}return{x:c,y:h,placement:m,strategy:n,middlewareData:b}};async function qn(i,e){var t;e===void 0&&(e={});const{x:s,y:n,platform:o,rects:r,elements:a,strategy:l}=i,{boundary:d="clippingAncestors",rootBoundary:c="viewport",elementContext:h="floating",altBoundary:m=!1,padding:b=0}=Dt(e,i),p=ca(b),_=a[m?h==="floating"?"reference":"floating":h],E=es(await o.getClippingRect({element:(t=await(o.isElement==null?void 0:o.isElement(_)))==null||t?_:_.contextElement||await(o.getDocumentElement==null?void 0:o.getDocumentElement(a.floating)),boundary:d,rootBoundary:c,strategy:l})),k=h==="floating"?{x:s,y:n,width:r.floating.width,height:r.floating.height}:r.reference,A=await(o.getOffsetParent==null?void 0:o.getOffsetParent(a.floating)),S=await(o.isElement==null?void 0:o.isElement(A))?await(o.getScale==null?void 0:o.getScale(A))||{x:1,y:1}:{x:1,y:1},R=es(o.convertOffsetParentRelativeRectToViewportRelativeRect?await o.convertOffsetParentRelativeRectToViewportRelativeRect({elements:a,rect:k,offsetParent:A,strategy:l}):k);return{top:(E.top-R.top+p.top)/S.y,bottom:(R.bottom-E.bottom+p.bottom)/S.y,left:(E.left-R.left+p.left)/S.x,right:(R.right-E.right+p.right)/S.x}}const Gd=i=>({name:"arrow",options:i,async fn(e){const{x:t,y:s,placement:n,rects:o,platform:r,elements:a,middlewareData:l}=e,{element:d,padding:c=0}=Dt(i,e)||{};if(d==null)return{};const h=ca(c),m={x:t,y:s},b=Hn(n),p=Un(b),g=await r.getDimensions(d),_=b==="y",E=_?"top":"left",k=_?"bottom":"right",A=_?"clientHeight":"clientWidth",S=o.reference[p]+o.reference[b]-m[b]-o.floating[p],R=m[b]-o.reference[b],U=await(r.getOffsetParent==null?void 0:r.getOffsetParent(d));let P=U?U[A]:0;(!P||!await(r.isElement==null?void 0:r.isElement(U)))&&(P=a.floating[A]||o.floating[p]);const J=S/2-R/2,X=P/2-g[p]/2-1,V=Qe(h[E],X),_e=Qe(h[k],X),ne=V,u=P-g[p]-_e,T=P/2-g[p]/2+J,F=mn(ne,T,u),I=!l.arrow&&Mt(n)!=null&&T!==F&&o.reference[p]/2-(T<ne?V:_e)-g[p]/2<0,M=I?T<ne?T-ne:T-u:0;return{[b]:m[b]+M,data:{[b]:F,centerOffset:T-F-M,...I&&{alignmentOffset:M}},reset:I}}}),Yd=function(i){return i===void 0&&(i={}),{name:"flip",options:i,async fn(e){var t,s;const{placement:n,middlewareData:o,rects:r,initialPlacement:a,platform:l,elements:d}=e,{mainAxis:c=!0,crossAxis:h=!0,fallbackPlacements:m,fallbackStrategy:b="bestFit",fallbackAxisSideDirection:p="none",flipAlignment:g=!0,..._}=Dt(i,e);if((t=o.arrow)!=null&&t.alignmentOffset)return{};const E=et(n),k=He(a),A=et(a)===a,S=await(l.isRTL==null?void 0:l.isRTL(d.floating)),R=m||(A||!g?[Qi(a)]:Bd(a)),U=p!=="none";!m&&U&&R.push(...jd(a,g,p,S));const P=[a,...R],J=await qn(e,_),X=[];let V=((s=o.flip)==null?void 0:s.overflows)||[];if(c&&X.push(J[E]),h){const T=zd(n,r,S);X.push(J[T[0]],J[T[1]])}if(V=[...V,{placement:n,overflows:X}],!X.every(T=>T<=0)){var _e,ne;const T=(((_e=o.flip)==null?void 0:_e.index)||0)+1,F=P[T];if(F&&(!(h==="alignment"?k!==He(F):!1)||V.every(N=>He(N.placement)===k?N.overflows[0]>0:!0)))return{data:{index:T,overflows:V},reset:{placement:F}};let I=(ne=V.filter(M=>M.overflows[0]<=0).sort((M,N)=>M.overflows[1]-N.overflows[1])[0])==null?void 0:ne.placement;if(!I)switch(b){case"bestFit":{var u;const M=(u=V.filter(N=>{if(U){const W=He(N.placement);return W===k||W==="y"}return!0}).map(N=>[N.placement,N.overflows.filter(W=>W>0).reduce((W,pe)=>W+pe,0)]).sort((N,W)=>N[1]-W[1])[0])==null?void 0:u[0];M&&(I=M);break}case"initialPlacement":I=a;break}if(n!==I)return{reset:{placement:I}}}return{}}}},Xd=new Set(["left","top"]);async function Zd(i,e){const{placement:t,platform:s,elements:n}=i,o=await(s.isRTL==null?void 0:s.isRTL(n.floating)),r=et(t),a=Mt(t),l=He(t)==="y",d=Xd.has(r)?-1:1,c=o&&l?-1:1,h=Dt(e,i);let{mainAxis:m,crossAxis:b,alignmentAxis:p}=typeof h=="number"?{mainAxis:h,crossAxis:0,alignmentAxis:null}:{mainAxis:h.mainAxis||0,crossAxis:h.crossAxis||0,alignmentAxis:h.alignmentAxis};return a&&typeof p=="number"&&(b=a==="end"?p*-1:p),l?{x:b*c,y:m*d}:{x:m*d,y:b*c}}const Jd=function(i){return i===void 0&&(i=0),{name:"offset",options:i,async fn(e){var t,s;const{x:n,y:o,placement:r,middlewareData:a}=e,l=await Zd(e,i);return r===((t=a.offset)==null?void 0:t.placement)&&(s=a.arrow)!=null&&s.alignmentOffset?{}:{x:n+l.x,y:o+l.y,data:{...l,placement:r}}}}},Qd=function(i){return i===void 0&&(i={}),{name:"shift",options:i,async fn(e){const{x:t,y:s,placement:n}=e,{mainAxis:o=!0,crossAxis:r=!1,limiter:a={fn:_=>{let{x:E,y:k}=_;return{x:E,y:k}}},...l}=Dt(i,e),d={x:t,y:s},c=await qn(e,l),h=He(et(n)),m=la(h);let b=d[m],p=d[h];if(o){const _=m==="y"?"top":"left",E=m==="y"?"bottom":"right",k=b+c[_],A=b-c[E];b=mn(k,b,A)}if(r){const _=h==="y"?"top":"left",E=h==="y"?"bottom":"right",k=p+c[_],A=p-c[E];p=mn(k,p,A)}const g=a.fn({...e,[m]:b,[h]:p});return{...g,data:{x:g.x-t,y:g.y-s,enabled:{[m]:o,[h]:r}}}}}},eu=function(i){return i===void 0&&(i={}),{name:"size",options:i,async fn(e){var t,s;const{placement:n,rects:o,platform:r,elements:a}=e,{apply:l=()=>{},...d}=Dt(i,e),c=await qn(e,d),h=et(n),m=Mt(n),b=He(n)==="y",{width:p,height:g}=o.floating;let _,E;h==="top"||h==="bottom"?(_=h,E=m===(await(r.isRTL==null?void 0:r.isRTL(a.floating))?"start":"end")?"left":"right"):(E=h,_=m==="end"?"top":"bottom");const k=g-c.top-c.bottom,A=p-c.left-c.right,S=Qe(g-c[_],k),R=Qe(p-c[E],A),U=!e.middlewareData.shift;let P=S,J=R;if((t=e.middlewareData.shift)!=null&&t.enabled.x&&(J=A),(s=e.middlewareData.shift)!=null&&s.enabled.y&&(P=k),U&&!m){const V=ye(c.left,0),_e=ye(c.right,0),ne=ye(c.top,0),u=ye(c.bottom,0);b?J=p-2*(V!==0||_e!==0?V+_e:ye(c.left,c.right)):P=g-2*(ne!==0||u!==0?ne+u:ye(c.top,c.bottom))}await l({...e,availableWidth:J,availableHeight:P});const X=await r.getDimensions(a.floating);return p!==X.width||g!==X.height?{reset:{rects:!0}}:{}}}};function ms(){return typeof window<"u"}function Vt(i){return da(i)?(i.nodeName||"").toLowerCase():"#document"}function xe(i){var e;return(i==null||(e=i.ownerDocument)==null?void 0:e.defaultView)||window}function Pe(i){var e;return(e=(da(i)?i.ownerDocument:i.document)||window.document)==null?void 0:e.documentElement}function da(i){return ms()?i instanceof Node||i instanceof xe(i).Node:!1}function Ne(i){return ms()?i instanceof Element||i instanceof xe(i).Element:!1}function Ve(i){return ms()?i instanceof HTMLElement||i instanceof xe(i).HTMLElement:!1}function zo(i){return!ms()||typeof ShadowRoot>"u"?!1:i instanceof ShadowRoot||i instanceof xe(i).ShadowRoot}const tu=new Set(["inline","contents"]);function fi(i){const{overflow:e,overflowX:t,overflowY:s,display:n}=Oe(i);return/auto|scroll|overlay|hidden|clip/.test(e+s+t)&&!tu.has(n)}const iu=new Set(["table","td","th"]);function su(i){return iu.has(Vt(i))}const nu=[":popover-open",":modal"];function bs(i){return nu.some(e=>{try{return i.matches(e)}catch{return!1}})}const ou=["transform","translate","scale","rotate","perspective"],ru=["transform","translate","scale","rotate","perspective","filter"],au=["paint","layout","strict","content"];function gs(i){const e=jn(),t=Ne(i)?Oe(i):i;return ou.some(s=>t[s]?t[s]!=="none":!1)||(t.containerType?t.containerType!=="normal":!1)||!e&&(t.backdropFilter?t.backdropFilter!=="none":!1)||!e&&(t.filter?t.filter!=="none":!1)||ru.some(s=>(t.willChange||"").includes(s))||au.some(s=>(t.contain||"").includes(s))}function lu(i){let e=tt(i);for(;Ve(e)&&!At(e);){if(gs(e))return e;if(bs(e))return null;e=tt(e)}return null}function jn(){return typeof CSS>"u"||!CSS.supports?!1:CSS.supports("-webkit-backdrop-filter","none")}const cu=new Set(["html","body","#document"]);function At(i){return cu.has(Vt(i))}function Oe(i){return xe(i).getComputedStyle(i)}function _s(i){return Ne(i)?{scrollLeft:i.scrollLeft,scrollTop:i.scrollTop}:{scrollLeft:i.scrollX,scrollTop:i.scrollY}}function tt(i){if(Vt(i)==="html")return i;const e=i.assignedSlot||i.parentNode||zo(i)&&i.host||Pe(i);return zo(e)?e.host:e}function ua(i){const e=tt(i);return At(e)?i.ownerDocument?i.ownerDocument.body:i.body:Ve(e)&&fi(e)?e:ua(e)}function Tt(i,e,t){var s;e===void 0&&(e=[]),t===void 0&&(t=!0);const n=ua(i),o=n===((s=i.ownerDocument)==null?void 0:s.body),r=xe(n);if(o){const a=gn(r);return e.concat(r,r.visualViewport||[],fi(n)?n:[],a&&t?Tt(a):[])}return e.concat(n,Tt(n,[],t))}function gn(i){return i.parent&&Object.getPrototypeOf(i.parent)?i.frameElement:null}function ha(i){const e=Oe(i);let t=parseFloat(e.width)||0,s=parseFloat(e.height)||0;const n=Ve(i),o=n?i.offsetWidth:t,r=n?i.offsetHeight:s,a=Ji(t)!==o||Ji(s)!==r;return a&&(t=o,s=r),{width:t,height:s,$:a}}function Wn(i){return Ne(i)?i:i.contextElement}function Ct(i){const e=Wn(i);if(!Ve(e))return Me(1);const t=e.getBoundingClientRect(),{width:s,height:n,$:o}=ha(e);let r=(o?Ji(t.width):t.width)/s,a=(o?Ji(t.height):t.height)/n;return(!r||!Number.isFinite(r))&&(r=1),(!a||!Number.isFinite(a))&&(a=1),{x:r,y:a}}const du=Me(0);function pa(i){const e=xe(i);return!jn()||!e.visualViewport?du:{x:e.visualViewport.offsetLeft,y:e.visualViewport.offsetTop}}function uu(i,e,t){return e===void 0&&(e=!1),!t||e&&t!==xe(i)?!1:e}function mt(i,e,t,s){e===void 0&&(e=!1),t===void 0&&(t=!1);const n=i.getBoundingClientRect(),o=Wn(i);let r=Me(1);e&&(s?Ne(s)&&(r=Ct(s)):r=Ct(i));const a=uu(o,t,s)?pa(o):Me(0);let l=(n.left+a.x)/r.x,d=(n.top+a.y)/r.y,c=n.width/r.x,h=n.height/r.y;if(o){const m=xe(o),b=s&&Ne(s)?xe(s):s;let p=m,g=gn(p);for(;g&&s&&b!==p;){const _=Ct(g),E=g.getBoundingClientRect(),k=Oe(g),A=E.left+(g.clientLeft+parseFloat(k.paddingLeft))*_.x,S=E.top+(g.clientTop+parseFloat(k.paddingTop))*_.y;l*=_.x,d*=_.y,c*=_.x,h*=_.y,l+=A,d+=S,p=xe(g),g=gn(p)}}return es({width:c,height:h,x:l,y:d})}function vs(i,e){const t=_s(i).scrollLeft;return e?e.left+t:mt(Pe(i)).left+t}function fa(i,e){const t=i.getBoundingClientRect(),s=t.left+e.scrollLeft-vs(i,t),n=t.top+e.scrollTop;return{x:s,y:n}}function hu(i){let{elements:e,rect:t,offsetParent:s,strategy:n}=i;const o=n==="fixed",r=Pe(s),a=e?bs(e.floating):!1;if(s===r||a&&o)return t;let l={scrollLeft:0,scrollTop:0},d=Me(1);const c=Me(0),h=Ve(s);if((h||!h&&!o)&&((Vt(s)!=="body"||fi(r))&&(l=_s(s)),Ve(s))){const b=mt(s);d=Ct(s),c.x=b.x+s.clientLeft,c.y=b.y+s.clientTop}const m=r&&!h&&!o?fa(r,l):Me(0);return{width:t.width*d.x,height:t.height*d.y,x:t.x*d.x-l.scrollLeft*d.x+c.x+m.x,y:t.y*d.y-l.scrollTop*d.y+c.y+m.y}}function pu(i){return Array.from(i.getClientRects())}function fu(i){const e=Pe(i),t=_s(i),s=i.ownerDocument.body,n=ye(e.scrollWidth,e.clientWidth,s.scrollWidth,s.clientWidth),o=ye(e.scrollHeight,e.clientHeight,s.scrollHeight,s.clientHeight);let r=-t.scrollLeft+vs(i);const a=-t.scrollTop;return Oe(s).direction==="rtl"&&(r+=ye(e.clientWidth,s.clientWidth)-n),{width:n,height:o,x:r,y:a}}const Bo=25;function mu(i,e){const t=xe(i),s=Pe(i),n=t.visualViewport;let o=s.clientWidth,r=s.clientHeight,a=0,l=0;if(n){o=n.width,r=n.height;const c=jn();(!c||c&&e==="fixed")&&(a=n.offsetLeft,l=n.offsetTop)}const d=vs(s);if(d<=0){const c=s.ownerDocument,h=c.body,m=getComputedStyle(h),b=c.compatMode==="CSS1Compat"&&parseFloat(m.marginLeft)+parseFloat(m.marginRight)||0,p=Math.abs(s.clientWidth-h.clientWidth-b);p<=Bo&&(o-=p)}else d<=Bo&&(o+=d);return{width:o,height:r,x:a,y:l}}const bu=new Set(["absolute","fixed"]);function gu(i,e){const t=mt(i,!0,e==="fixed"),s=t.top+i.clientTop,n=t.left+i.clientLeft,o=Ve(i)?Ct(i):Me(1),r=i.clientWidth*o.x,a=i.clientHeight*o.y,l=n*o.x,d=s*o.y;return{width:r,height:a,x:l,y:d}}function Uo(i,e,t){let s;if(e==="viewport")s=mu(i,t);else if(e==="document")s=fu(Pe(i));else if(Ne(e))s=gu(e,t);else{const n=pa(i);s={x:e.x-n.x,y:e.y-n.y,width:e.width,height:e.height}}return es(s)}function ma(i,e){const t=tt(i);return t===e||!Ne(t)||At(t)?!1:Oe(t).position==="fixed"||ma(t,e)}function _u(i,e){const t=e.get(i);if(t)return t;let s=Tt(i,[],!1).filter(a=>Ne(a)&&Vt(a)!=="body"),n=null;const o=Oe(i).position==="fixed";let r=o?tt(i):i;for(;Ne(r)&&!At(r);){const a=Oe(r),l=gs(r);!l&&a.position==="fixed"&&(n=null),(o?!l&&!n:!l&&a.position==="static"&&!!n&&bu.has(n.position)||fi(r)&&!l&&ma(i,r))?s=s.filter(c=>c!==r):n=a,r=tt(r)}return e.set(i,s),s}function vu(i){let{element:e,boundary:t,rootBoundary:s,strategy:n}=i;const r=[...t==="clippingAncestors"?bs(e)?[]:_u(e,this._c):[].concat(t),s],a=r[0],l=r.reduce((d,c)=>{const h=Uo(e,c,n);return d.top=ye(h.top,d.top),d.right=Qe(h.right,d.right),d.bottom=Qe(h.bottom,d.bottom),d.left=ye(h.left,d.left),d},Uo(e,a,n));return{width:l.right-l.left,height:l.bottom-l.top,x:l.left,y:l.top}}function yu(i){const{width:e,height:t}=ha(i);return{width:e,height:t}}function wu(i,e,t){const s=Ve(e),n=Pe(e),o=t==="fixed",r=mt(i,!0,o,e);let a={scrollLeft:0,scrollTop:0};const l=Me(0);function d(){l.x=vs(n)}if(s||!s&&!o)if((Vt(e)!=="body"||fi(n))&&(a=_s(e)),s){const b=mt(e,!0,o,e);l.x=b.x+e.clientLeft,l.y=b.y+e.clientTop}else n&&d();o&&!s&&n&&d();const c=n&&!s&&!o?fa(n,a):Me(0),h=r.left+a.scrollLeft-l.x-c.x,m=r.top+a.scrollTop-l.y-c.y;return{x:h,y:m,width:r.width,height:r.height}}function Ds(i){return Oe(i).position==="static"}function Ho(i,e){if(!Ve(i)||Oe(i).position==="fixed")return null;if(e)return e(i);let t=i.offsetParent;return Pe(i)===t&&(t=t.ownerDocument.body),t}function ba(i,e){const t=xe(i);if(bs(i))return t;if(!Ve(i)){let n=tt(i);for(;n&&!At(n);){if(Ne(n)&&!Ds(n))return n;n=tt(n)}return t}let s=Ho(i,e);for(;s&&su(s)&&Ds(s);)s=Ho(s,e);return s&&At(s)&&Ds(s)&&!gs(s)?t:s||lu(i)||t}const xu=async function(i){const e=this.getOffsetParent||ba,t=this.getDimensions,s=await t(i.floating);return{reference:wu(i.reference,await e(i.floating),i.strategy),floating:{x:0,y:0,width:s.width,height:s.height}}};function Eu(i){return Oe(i).direction==="rtl"}const Ui={convertOffsetParentRelativeRectToViewportRelativeRect:hu,getDocumentElement:Pe,getClippingRect:vu,getOffsetParent:ba,getElementRects:xu,getClientRects:pu,getDimensions:yu,getScale:Ct,isElement:Ne,isRTL:Eu};function ga(i,e){return i.x===e.x&&i.y===e.y&&i.width===e.width&&i.height===e.height}function Cu(i,e){let t=null,s;const n=Pe(i);function o(){var a;clearTimeout(s),(a=t)==null||a.disconnect(),t=null}function r(a,l){a===void 0&&(a=!1),l===void 0&&(l=1),o();const d=i.getBoundingClientRect(),{left:c,top:h,width:m,height:b}=d;if(a||e(),!m||!b)return;const p=Si(h),g=Si(n.clientWidth-(c+m)),_=Si(n.clientHeight-(h+b)),E=Si(c),A={rootMargin:-p+"px "+-g+"px "+-_+"px "+-E+"px",threshold:ye(0,Qe(1,l))||1};let S=!0;function R(U){const P=U[0].intersectionRatio;if(P!==l){if(!S)return r();P?r(!1,P):s=setTimeout(()=>{r(!1,1e-7)},1e3)}P===1&&!ga(d,i.getBoundingClientRect())&&r(),S=!1}try{t=new IntersectionObserver(R,{...A,root:n.ownerDocument})}catch{t=new IntersectionObserver(R,A)}t.observe(i)}return r(!0),o}function _a(i,e,t,s){s===void 0&&(s={});const{ancestorScroll:n=!0,ancestorResize:o=!0,elementResize:r=typeof ResizeObserver=="function",layoutShift:a=typeof IntersectionObserver=="function",animationFrame:l=!1}=s,d=Wn(i),c=n||o?[...d?Tt(d):[],...Tt(e)]:[];c.forEach(E=>{n&&E.addEventListener("scroll",t,{passive:!0}),o&&E.addEventListener("resize",t)});const h=d&&a?Cu(d,t):null;let m=-1,b=null;r&&(b=new ResizeObserver(E=>{let[k]=E;k&&k.target===d&&b&&(b.unobserve(e),cancelAnimationFrame(m),m=requestAnimationFrame(()=>{var A;(A=b)==null||A.observe(e)})),t()}),d&&!l&&b.observe(d),b.observe(e));let p,g=l?mt(i):null;l&&_();function _(){const E=mt(i);g&&!ga(g,E)&&t(),g=E,p=requestAnimationFrame(_)}return t(),()=>{var E;c.forEach(k=>{n&&k.removeEventListener("scroll",t),o&&k.removeEventListener("resize",t)}),h?.(),(E=b)==null||E.disconnect(),b=null,l&&cancelAnimationFrame(p)}}const va=Jd,ya=Qd,wa=Yd,qo=eu,ku=Gd,xa=(i,e,t)=>{const s=new Map,n={platform:Ui,...t},o={...n.platform,_c:s};return Kd(i,e,{...n,platform:o})};function Su(i){return Au(i)}function Ms(i){return i.assignedSlot?i.assignedSlot:i.parentNode instanceof ShadowRoot?i.parentNode.host:i.parentNode}function Au(i){for(let e=i;e;e=Ms(e))if(e instanceof Element&&getComputedStyle(e).display==="none")return null;for(let e=Ms(i);e;e=Ms(e)){if(!(e instanceof Element))continue;const t=getComputedStyle(e);if(t.display!=="contents"&&(t.position!=="static"||gs(t)||e.tagName==="BODY"))return e}return null}const ys={ATTRIBUTE:1,CHILD:2},ws=i=>(...e)=>({_$litDirective$:i,values:e});let xs=class{constructor(e){}get _$AU(){return this._$AM._$AU}_$AT(e,t,s){this._$Ct=e,this._$AM=t,this._$Ci=s}_$AS(e,t){return this.update(e,t)}update(e,t){return this.render(...t)}};const Fe=ws(class extends xs{constructor(i){if(super(i),i.type!==ys.ATTRIBUTE||i.name!=="class"||i.strings?.length>2)throw Error("`classMap()` can only be used in the `class` attribute and must be the only part in the attribute.")}render(i){return" "+Object.keys(i).filter((e=>i[e])).join(" ")+" "}update(i,[e]){if(this.st===void 0){this.st=new Set,i.strings!==void 0&&(this.nt=new Set(i.strings.join(" ").split(/\s/).filter((s=>s!==""))));for(const s in e)e[s]&&!this.nt?.has(s)&&this.st.add(s);return this.render(e)}const t=i.element.classList;for(const s of this.st)s in e||(t.remove(s),this.st.delete(s));for(const s in e){const n=!!e[s];n===this.st.has(s)||this.nt?.has(s)||(n?(t.add(s),this.st.add(s)):(t.remove(s),this.st.delete(s)))}return Te}});var Tu=`:host {
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
`;function jo(i){return i!==null&&typeof i=="object"&&"getBoundingClientRect"in i&&("contextElement"in i?i instanceof Element:!0)}var Ai=globalThis?.HTMLElement?.prototype.hasOwnProperty("popover"),Y=class extends ge{constructor(){super(...arguments),this.localize=new Rt(this),this.active=!1,this.placement="top",this.boundary="viewport",this.distance=0,this.skidding=0,this.arrow=!1,this.arrowPlacement="anchor",this.arrowPadding=10,this.flip=!1,this.flipFallbackPlacements="",this.flipFallbackStrategy="best-fit",this.flipPadding=0,this.shift=!1,this.shiftPadding=0,this.autoSizePadding=0,this.hoverBridge=!1,this.updateHoverBridge=()=>{if(this.hoverBridge&&this.anchorEl){const i=this.anchorEl.getBoundingClientRect(),e=this.popup.getBoundingClientRect(),t=this.placement.includes("top")||this.placement.includes("bottom");let s=0,n=0,o=0,r=0,a=0,l=0,d=0,c=0;t?i.top<e.top?(s=i.left,n=i.bottom,o=i.right,r=i.bottom,a=e.left,l=e.top,d=e.right,c=e.top):(s=e.left,n=e.bottom,o=e.right,r=e.bottom,a=i.left,l=i.top,d=i.right,c=i.top):i.left<e.left?(s=i.right,n=i.top,o=e.left,r=e.top,a=i.right,l=i.bottom,d=e.left,c=e.bottom):(s=e.right,n=e.top,o=i.left,r=i.top,a=e.right,l=e.bottom,d=i.left,c=i.bottom),this.style.setProperty("--hover-bridge-top-left-x",`${s}px`),this.style.setProperty("--hover-bridge-top-left-y",`${n}px`),this.style.setProperty("--hover-bridge-top-right-x",`${o}px`),this.style.setProperty("--hover-bridge-top-right-y",`${r}px`),this.style.setProperty("--hover-bridge-bottom-left-x",`${a}px`),this.style.setProperty("--hover-bridge-bottom-left-y",`${l}px`),this.style.setProperty("--hover-bridge-bottom-right-x",`${d}px`),this.style.setProperty("--hover-bridge-bottom-right-y",`${c}px`)}}}async connectedCallback(){super.connectedCallback(),await this.updateComplete,this.start()}disconnectedCallback(){super.disconnectedCallback(),this.stop()}async updated(i){super.updated(i),i.has("active")&&(this.active?this.start():this.stop()),i.has("anchor")&&this.handleAnchorChange(),this.active&&(await this.updateComplete,this.reposition())}async handleAnchorChange(){if(await this.stop(),this.anchor&&typeof this.anchor=="string"){const i=this.getRootNode();this.anchorEl=i.getElementById(this.anchor)}else this.anchor instanceof Element||jo(this.anchor)?this.anchorEl=this.anchor:this.anchorEl=this.querySelector('[slot="anchor"]');this.anchorEl instanceof HTMLSlotElement&&(this.anchorEl=this.anchorEl.assignedElements({flatten:!0})[0]),this.anchorEl&&this.start()}start(){!this.anchorEl||!this.active||(this.popup.showPopover?.(),this.cleanup=_a(this.anchorEl,this.popup,()=>{this.reposition()}))}async stop(){return new Promise(i=>{this.popup.hidePopover?.(),this.cleanup?(this.cleanup(),this.cleanup=void 0,this.removeAttribute("data-current-placement"),this.style.removeProperty("--auto-size-available-width"),this.style.removeProperty("--auto-size-available-height"),requestAnimationFrame(()=>i())):i()})}reposition(){if(!this.active||!this.anchorEl)return;const i=[va({mainAxis:this.distance,crossAxis:this.skidding})];this.sync?i.push(qo({apply:({rects:s})=>{const n=this.sync==="width"||this.sync==="both",o=this.sync==="height"||this.sync==="both";this.popup.style.width=n?`${s.reference.width}px`:"",this.popup.style.height=o?`${s.reference.height}px`:""}})):(this.popup.style.width="",this.popup.style.height="");let e;Ai&&!jo(this.anchor)&&this.boundary==="scroll"&&(e=Tt(this.anchorEl).filter(s=>s instanceof Element)),this.flip&&i.push(wa({boundary:this.flipBoundary||e,fallbackPlacements:this.flipFallbackPlacements,fallbackStrategy:this.flipFallbackStrategy==="best-fit"?"bestFit":"initialPlacement",padding:this.flipPadding})),this.shift&&i.push(ya({boundary:this.shiftBoundary||e,padding:this.shiftPadding})),this.autoSize?i.push(qo({boundary:this.autoSizeBoundary||e,padding:this.autoSizePadding,apply:({availableWidth:s,availableHeight:n})=>{this.autoSize==="vertical"||this.autoSize==="both"?this.style.setProperty("--auto-size-available-height",`${n}px`):this.style.removeProperty("--auto-size-available-height"),this.autoSize==="horizontal"||this.autoSize==="both"?this.style.setProperty("--auto-size-available-width",`${s}px`):this.style.removeProperty("--auto-size-available-width")}})):(this.style.removeProperty("--auto-size-available-width"),this.style.removeProperty("--auto-size-available-height")),this.arrow&&i.push(ku({element:this.arrowEl,padding:this.arrowPadding}));const t=Ai?s=>Ui.getOffsetParent(s,Su):Ui.getOffsetParent;xa(this.anchorEl,this.popup,{placement:this.placement,middleware:i,strategy:Ai?"absolute":"fixed",platform:{...Ui,getOffsetParent:t}}).then(({x:s,y:n,middlewareData:o,placement:r})=>{const a=this.localize.dir()==="rtl",l={top:"bottom",right:"left",bottom:"top",left:"right"}[r.split("-")[0]];if(this.setAttribute("data-current-placement",r),Object.assign(this.popup.style,{left:`${s}px`,top:`${n}px`}),this.arrow){const d=o.arrow.x,c=o.arrow.y;let h="",m="",b="",p="";if(this.arrowPlacement==="start"){const g=typeof d=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"";h=typeof c=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"",m=a?g:"",p=a?"":g}else if(this.arrowPlacement==="end"){const g=typeof d=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"";m=a?"":g,p=a?g:"",b=typeof c=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:""}else this.arrowPlacement==="center"?(p=typeof d=="number"?"calc(50% - var(--arrow-size-diagonal))":"",h=typeof c=="number"?"calc(50% - var(--arrow-size-diagonal))":""):(p=typeof d=="number"?`${d}px`:"",h=typeof c=="number"?`${c}px`:"");Object.assign(this.arrowEl.style,{top:h,right:m,bottom:b,left:p,[l]:"calc(var(--arrow-size-diagonal) * -1)"})}}),requestAnimationFrame(()=>this.updateHoverBridge()),this.dispatchEvent(new Id)}render(){return x`
      <slot name="anchor" @slotchange=${this.handleAnchorChange}></slot>

      <span
        part="hover-bridge"
        class=${Fe({"popup-hover-bridge":!0,"popup-hover-bridge-visible":this.hoverBridge&&this.active})}
      ></span>

      <div
        popover="manual"
        part="popup"
        class=${Fe({popup:!0,"popup-active":this.active,"popup-fixed":!Ai,"popup-has-arrow":this.arrow})}
      >
        <slot></slot>
        ${this.arrow?x`<div part="arrow" class="arrow" role="presentation"></div>`:""}
      </div>
    `}};Y.css=Tu;v([Q(".popup")],Y.prototype,"popup",2);v([Q(".arrow")],Y.prototype,"arrowEl",2);v([y()],Y.prototype,"anchor",2);v([y({type:Boolean,reflect:!0})],Y.prototype,"active",2);v([y({reflect:!0})],Y.prototype,"placement",2);v([y()],Y.prototype,"boundary",2);v([y({type:Number})],Y.prototype,"distance",2);v([y({type:Number})],Y.prototype,"skidding",2);v([y({type:Boolean})],Y.prototype,"arrow",2);v([y({attribute:"arrow-placement"})],Y.prototype,"arrowPlacement",2);v([y({attribute:"arrow-padding",type:Number})],Y.prototype,"arrowPadding",2);v([y({type:Boolean})],Y.prototype,"flip",2);v([y({attribute:"flip-fallback-placements",converter:{fromAttribute:i=>i.split(" ").map(e=>e.trim()).filter(e=>e!==""),toAttribute:i=>i.join(" ")}})],Y.prototype,"flipFallbackPlacements",2);v([y({attribute:"flip-fallback-strategy"})],Y.prototype,"flipFallbackStrategy",2);v([y({type:Object})],Y.prototype,"flipBoundary",2);v([y({attribute:"flip-padding",type:Number})],Y.prototype,"flipPadding",2);v([y({type:Boolean})],Y.prototype,"shift",2);v([y({type:Object})],Y.prototype,"shiftBoundary",2);v([y({attribute:"shift-padding",type:Number})],Y.prototype,"shiftPadding",2);v([y({attribute:"auto-size"})],Y.prototype,"autoSize",2);v([y()],Y.prototype,"sync",2);v([y({type:Object})],Y.prototype,"autoSizeBoundary",2);v([y({attribute:"auto-size-padding",type:Number})],Y.prototype,"autoSizePadding",2);v([y({attribute:"hover-bridge",type:Boolean})],Y.prototype,"hoverBridge",2);Y=v([ke("wa-popup")],Y);var mi=class extends Event{constructor(){super("wa-after-hide",{bubbles:!0,cancelable:!1,composed:!0})}},bi=class extends Event{constructor(){super("wa-after-show",{bubbles:!0,cancelable:!1,composed:!0})}},gi=class extends Event{constructor(i){super("wa-hide",{bubbles:!0,cancelable:!0,composed:!0}),this.detail=i}},_i=class extends Event{constructor(){super("wa-show",{bubbles:!0,cancelable:!0,composed:!0})}};const Nu="useandom-26T198340PX75pxJACKVERYMINDBUSHWOLF_GQZbfghjklqvwyzrict";let Ou=(i=21)=>{let e="",t=crypto.getRandomValues(new Uint8Array(i|=0));for(;i--;)e+=Nu[t[i]&63];return e};function Kn(i=""){return`${i}${Ou()}`}function ts(i,e){return new Promise(t=>{function s(n){n.target===i&&(i.removeEventListener(e,s),t())}i.addEventListener(e,s)})}function de(i,e){return new Promise(t=>{const s=new AbortController,{signal:n}=s;if(i.classList.contains(e))return;i.classList.remove(e),i.classList.add(e);let o=()=>{i.classList.remove(e),t(),s.abort()};i.addEventListener("animationend",o,{once:!0,signal:n}),i.addEventListener("animationcancel",o,{once:!0,signal:n})})}function Ee(i,e){const t={waitUntilFirstUpdate:!1,...e};return(s,n)=>{const{update:o}=s,r=Array.isArray(i)?i:[i];s.update=function(a){r.forEach(l=>{const d=l;if(a.has(d)){const c=a.get(d),h=this[d];c!==h&&(!t.waitUntilFirstUpdate||this.hasUpdated)&&this[n](c,h)}}),o.call(this,a)}}}var Fu=`:host {
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
`,ee=class extends ge{constructor(){super(...arguments),this.placement="top",this.disabled=!1,this.distance=8,this.open=!1,this.skidding=0,this.showDelay=150,this.hideDelay=0,this.trigger="hover focus",this.withoutArrow=!1,this.for=null,this.anchor=null,this.eventController=new AbortController,this.handleBlur=()=>{this.hasTrigger("focus")&&this.hide()},this.handleClick=()=>{this.hasTrigger("click")&&(this.open?this.hide():this.show())},this.handleFocus=()=>{this.hasTrigger("focus")&&this.show()},this.handleDocumentKeyDown=i=>{i.key==="Escape"&&(i.stopPropagation(),this.hide())},this.handleMouseOver=()=>{this.hasTrigger("hover")&&(clearTimeout(this.hoverTimeout),this.hoverTimeout=window.setTimeout(()=>this.show(),this.showDelay))},this.handleMouseOut=()=>{this.hasTrigger("hover")&&(clearTimeout(this.hoverTimeout),this.hoverTimeout=window.setTimeout(()=>this.hide(),this.hideDelay))}}connectedCallback(){super.connectedCallback(),this.eventController.signal.aborted&&(this.eventController=new AbortController),this.open&&(this.open=!1,this.updateComplete.then(()=>{this.open=!0})),this.id||(this.id=Kn("wa-tooltip-")),this.for&&this.anchor?(this.anchor=null,this.handleForChange()):this.for&&this.handleForChange()}disconnectedCallback(){super.disconnectedCallback(),document.removeEventListener("keydown",this.handleDocumentKeyDown),this.eventController.abort(),this.anchor&&this.removeFromAriaLabelledBy(this.anchor,this.id)}firstUpdated(){this.body.hidden=!this.open,this.open&&(this.popup.active=!0,this.popup.reposition())}hasTrigger(i){return this.trigger.split(" ").includes(i)}addToAriaLabelledBy(i,e){const s=(i.getAttribute("aria-labelledby")||"").split(/\s+/).filter(Boolean);s.includes(e)||(s.push(e),i.setAttribute("aria-labelledby",s.join(" ")))}removeFromAriaLabelledBy(i,e){const n=(i.getAttribute("aria-labelledby")||"").split(/\s+/).filter(Boolean).filter(o=>o!==e);n.length>0?i.setAttribute("aria-labelledby",n.join(" ")):i.removeAttribute("aria-labelledby")}async handleOpenChange(){if(this.open){if(this.disabled)return;const i=new _i;if(this.dispatchEvent(i),i.defaultPrevented){this.open=!1;return}document.addEventListener("keydown",this.handleDocumentKeyDown,{signal:this.eventController.signal}),this.body.hidden=!1,this.popup.active=!0,await de(this.popup.popup,"show-with-scale"),this.popup.reposition(),this.dispatchEvent(new bi)}else{const i=new gi;if(this.dispatchEvent(i),i.defaultPrevented){this.open=!1;return}document.removeEventListener("keydown",this.handleDocumentKeyDown),await de(this.popup.popup,"hide-with-scale"),this.popup.active=!1,this.body.hidden=!0,this.dispatchEvent(new mi)}}handleForChange(){const i=this.getRootNode();if(!i)return;const e=this.for?i.getElementById(this.for):null,t=this.anchor;if(e===t)return;const{signal:s}=this.eventController;e&&(this.addToAriaLabelledBy(e,this.id),e.addEventListener("blur",this.handleBlur,{capture:!0,signal:s}),e.addEventListener("focus",this.handleFocus,{capture:!0,signal:s}),e.addEventListener("click",this.handleClick,{signal:s}),e.addEventListener("mouseover",this.handleMouseOver,{signal:s}),e.addEventListener("mouseout",this.handleMouseOut,{signal:s})),t&&(this.removeFromAriaLabelledBy(t,this.id),t.removeEventListener("blur",this.handleBlur,{capture:!0}),t.removeEventListener("focus",this.handleFocus,{capture:!0}),t.removeEventListener("click",this.handleClick),t.removeEventListener("mouseover",this.handleMouseOver),t.removeEventListener("mouseout",this.handleMouseOut)),this.anchor=e}async handleOptionsChange(){this.hasUpdated&&(await this.updateComplete,this.popup.reposition())}handleDisabledChange(){this.disabled&&this.open&&this.hide()}async show(){if(!this.open)return this.open=!0,ts(this,"wa-after-show")}async hide(){if(this.open)return this.open=!1,ts(this,"wa-after-hide")}render(){return x`
      <wa-popup
        part="base"
        exportparts="
          popup:base__popup,
          arrow:base__arrow
        "
        class=${Fe({tooltip:!0,"tooltip-open":this.open})}
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
    `}};ee.css=Fu;ee.dependencies={"wa-popup":Y};v([Q("slot:not([name])")],ee.prototype,"defaultSlot",2);v([Q(".body")],ee.prototype,"body",2);v([Q("wa-popup")],ee.prototype,"popup",2);v([y()],ee.prototype,"placement",2);v([y({type:Boolean,reflect:!0})],ee.prototype,"disabled",2);v([y({type:Number})],ee.prototype,"distance",2);v([y({type:Boolean,reflect:!0})],ee.prototype,"open",2);v([y({type:Number})],ee.prototype,"skidding",2);v([y({attribute:"show-delay",type:Number})],ee.prototype,"showDelay",2);v([y({attribute:"hide-delay",type:Number})],ee.prototype,"hideDelay",2);v([y()],ee.prototype,"trigger",2);v([y({attribute:"without-arrow",type:Boolean,reflect:!0})],ee.prototype,"withoutArrow",2);v([y()],ee.prototype,"for",2);v([be()],ee.prototype,"anchor",2);v([Ee("open",{waitUntilFirstUpdate:!0})],ee.prototype,"handleOpenChange",1);v([Ee("for")],ee.prototype,"handleForChange",1);v([Ee(["distance","placement","skidding"])],ee.prototype,"handleOptionsChange",1);v([Ee("disabled")],ee.prototype,"handleDisabledChange",1);ee=v([ke("wa-tooltip")],ee);var Lu=class extends ee{static get styles(){return[ee.styles,O`
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
      `]}};customElements.get("c-tooltip")||customElements.define("c-tooltip",Lu);function L(i,e,t,s){var n=arguments.length,o=n<3?e:s,r;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")o=Reflect.decorate(i,e,t,s);else for(var a=i.length-1;a>=0;a--)(r=i[a])&&(o=(n<3?r(o):n>3?r(e,t,o):r(e,t))||o);return n>3&&o&&Object.defineProperty(e,t,o),o}var $u=O`
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
`,Ru=O`
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
`,Ea=Object.defineProperty,Wo=Object.getOwnPropertySymbols,Iu=Object.prototype.hasOwnProperty,Du=Object.prototype.propertyIsEnumerable,Ca=i=>{throw TypeError(i)},Ko=(i,e,t)=>e in i?Ea(i,e,{enumerable:!0,configurable:!0,writable:!0,value:t}):i[e]=t,Mu=(i,e)=>{for(var t in e||(e={}))Iu.call(e,t)&&Ko(i,t,e[t]);if(Wo)for(var t of Wo(e))Du.call(e,t)&&Ko(i,t,e[t]);return i},Go=(i,e,t,s)=>{for(var n=void 0,o=i.length-1,r;o>=0;o--)(r=i[o])&&(n=r(e,t,n)||n);return n&&Ea(e,t,n),n},ka=(i,e,t)=>e.has(i)||Ca("Cannot "+t),Vu=(i,e,t)=>(ka(i,e,"read from private field"),e.get(i)),Pu=(i,e,t)=>e.has(i)?Ca("Cannot add the same private member more than once"):e instanceof WeakSet?e.add(i):e.set(i,t),zu=(i,e,t,s)=>(ka(i,e,"write to private field"),e.set(i,t),t),Hi,Yt=class extends H{constructor(){super(),Pu(this,Hi,!1),this.initialReflectedProperties=new Map,Object.entries(this.constructor.dependencies).forEach(([i,e])=>{this.constructor.define(i,e)})}emit(i,e){let t=new CustomEvent(i,Mu({bubbles:!0,cancelable:!1,composed:!0,detail:{}},e));return this.dispatchEvent(t),t}static define(i,e=this,t={}){let s=customElements.get(i);if(!s){try{customElements.define(i,e,t)}catch{customElements.define(i,class extends e{},t)}return}let n=" (unknown version)",o=n;"version"in e&&e.version&&(n=" v"+e.version),"version"in s&&s.version&&(o=" v"+s.version),!(n&&o&&n===o)&&console.warn(`Attempted to register <${i}>${n}, but <${i}>${o} has already been registered.`)}attributeChangedCallback(i,e,t){Vu(this,Hi)||(this.constructor.elementProperties.forEach((s,n)=>{s.reflect&&this[n]!=null&&this.initialReflectedProperties.set(n,this[n])}),zu(this,Hi,!0)),super.attributeChangedCallback(i,e,t)}willUpdate(i){super.willUpdate(i),this.initialReflectedProperties.forEach((e,t)=>{i.has(t)&&this[t]==null&&(this[t]=e)})}};Hi=new WeakMap,Yt.version="2.20.1",Yt.dependencies={},Go([y()],Yt.prototype,"dir"),Go([y()],Yt.prototype,"lang");var Yo=class extends Yt{render(){return x` <slot></slot> `}};Yo.styles=[Ru,$u],Yo.define("sl-visually-hidden");var Bu=O`
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
`,Ut=class extends H{constructor(...i){super(...i),this.isCopying=!1,this.value="",this.disabled=!1}async copyValue(){if(!(this.isCopying||this.disabled)){this.isCopying=!0;try{await navigator.clipboard.writeText(this.value),this.dispatchEvent(new CustomEvent("craft-copy",{bubbles:!0,cancelable:!1,composed:!0,detail:{value:this.value}}))}catch{this.dispatchEvent(new CustomEvent("craft-error",{cancelable:!1,composed:!0,bubbles:!0}))}finally{this.isCopying=!1}}}render(){return x`
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
    `}};Ut.styles=[Bu],L([be()],Ut.prototype,"isCopying",void 0),L([y({type:String})],Ut.prototype,"value",void 0),L([y({type:Boolean})],Ut.prototype,"disabled",void 0),customElements.get("craft-copy-button")||customElements.define("craft-copy-button",Ut);var Uu=O`
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
`,Hu=O`
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
`;const je={"icon.in":{keyframes:[{scale:.25,opacity:.25},{scale:1,opacity:1}],options:{duration:100}},"icon.out":{keyframes:[{scale:1,opacity:1},{scale:.25,opacity:.25}],options:{duration:100}}};var Se=class extends H{constructor(){super(),this.status="rest",this.value="",this.disabled=!1,this.feedbackDuration=1e3,this.tooltipLabel="Copy",this.addEventListener("craft-copy",()=>{this.showStatus("success")}),this.addEventListener("craft-error",()=>{this.showStatus("error")})}getId(){return`attribute-${this.value.replace(/([a-z])([A-Z])/g,"$1-$2").replace(/[\s_]+/g,"-").toLowerCase()}`}async showStatus(i){let e=i==="success"?this.successIconEl:this.errorIconEl;this.tooltipLabel=i==="success"?"Copied":"Copy failed",await e.animate(je["icon.out"].keyframes,je["icon.out"].options),this.copyIconEl.hidden=!0,e.hidden=!1,await e.animate(je["icon.in"].keyframes,je["icon.in"].options),this.status=i,setTimeout(async()=>{await e.animate(je["icon.out"].keyframes,je["icon.out"].options),e.hidden=!0,this.copyIconEl.hidden=!1,await this.copyIconEl.animate(je["icon.in"].keyframes,je["icon.in"].options),this.status="rest",this.tooltipLabel="Copy"},this.feedbackDuration)}render(){return x`
      <c-tooltip for="${this.getId()}">${this.tooltipLabel}</c-tooltip>
      <craft-copy-button
        value="${this.value}"
        id="${this.getId()}"
        class=${Fe({"copy-attribute":!0,"copy-attribute--success":this.status==="success","copy-attribute--error":this.status==="error"})}
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
    `}};Se.styles=[Uu,Hu],L([be()],Se.prototype,"status",void 0),L([Q('slot[name="copy-icon"]')],Se.prototype,"copyIconEl",void 0),L([Q('slot[name="success-icon"]')],Se.prototype,"successIconEl",void 0),L([Q('slot[name="error-icon"]')],Se.prototype,"errorIconEl",void 0),L([Q("craft-copy-button")],Se.prototype,"copyButtonEl",void 0),L([y({type:String})],Se.prototype,"value",void 0),L([y({type:Boolean,reflect:!0})],Se.prototype,"disabled",void 0),L([y({attribute:"feedback-duration",type:Number})],Se.prototype,"feedbackDuration",void 0),L([y({reflect:!1})],Se.prototype,"tooltipLabel",void 0),customElements.get("craft-copy-attribute")||customElements.define("craft-copy-attribute",Se);const Sa=new WeakMap;function qu(i,e){let t=e;for(;t;){if(Sa.get(t)===i)return!0;t=Object.getPrototypeOf(t)}return!1}function ie(i){return e=>{if(qu(i,e))return e;const t=i(e);return Sa.set(t,i),t}}const ju=i=>class extends i{static get properties(){return{disabled:{type:Boolean,reflect:!0}}}constructor(){super(),this._requestedToBeDisabled=!1,this.__isUserSettingDisabled=!0,this.__restoreDisabledTo=!1,this.disabled=!1}makeRequestToBeDisabled(){this._requestedToBeDisabled===!1&&(this._requestedToBeDisabled=!0,this.__restoreDisabledTo=this.disabled,this.__internalSetDisabled(!0))}retractRequestToBeDisabled(){this._requestedToBeDisabled===!0&&(this._requestedToBeDisabled=!1,this.__internalSetDisabled(this.__restoreDisabledTo))}__internalSetDisabled(e){this.__isUserSettingDisabled=!1,this.disabled=e,this.__isUserSettingDisabled=!0}requestUpdate(e,t,s){super.requestUpdate(e,t,s),e==="disabled"&&(this.__isUserSettingDisabled&&(this.__restoreDisabledTo=this.disabled),this.disabled===!1&&this._requestedToBeDisabled===!0&&this.__internalSetDisabled(!0))}click(){this.disabled||super.click()}},vi=ie(ju),Wu=i=>class extends vi(i){static get properties(){return{tabIndex:{type:Number,reflect:!0,attribute:"tabindex"}}}constructor(){super(),this.__isUserSettingTabIndex=!0,this.__restoreTabIndexTo=0,this.__internalSetTabIndex(0)}makeRequestToBeDisabled(){super.makeRequestToBeDisabled(),this._requestedToBeDisabled===!1&&this.tabIndex!=null&&(this.__restoreTabIndexTo=this.tabIndex)}retractRequestToBeDisabled(){super.retractRequestToBeDisabled(),this._requestedToBeDisabled===!0&&this.__internalSetTabIndex(this.__restoreTabIndexTo)}static enabledWarnings=super.enabledWarnings?.filter(e=>e!=="change-in-update")||[];__internalSetTabIndex(e){this.__isUserSettingTabIndex=!1,this.tabIndex=e,this.__isUserSettingTabIndex=!0}requestUpdate(e,t,s){super.requestUpdate(e,t,s),e==="disabled"&&(this.disabled?this.__internalSetTabIndex(-1):this.__internalSetTabIndex(this.__restoreTabIndexTo)),e==="tabIndex"&&(this.__isUserSettingTabIndex&&this.tabIndex!=null&&(this.__restoreTabIndexTo=this.tabIndex),this.tabIndex!==-1&&this._requestedToBeDisabled===!0&&this.__internalSetTabIndex(-1))}firstUpdated(e){super.firstUpdated(e),this.disabled&&this.__internalSetTabIndex(-1)}},Aa=ie(Wu);const{I:Ku}=Ad,Gu=i=>i===null||typeof i!="object"&&typeof i!="function",Ta=(i,e)=>i?._$litType$!==void 0,Yu=i=>i.strings===void 0,Xo=()=>document.createComment(""),Ht=(i,e,t)=>{const s=i._$AA.parentNode,n=e===void 0?i._$AB:e._$AA;if(t===void 0){const o=s.insertBefore(Xo(),n),r=s.insertBefore(Xo(),n);t=new Ku(o,r,i,i.options)}else{const o=t._$AB.nextSibling,r=t._$AM,a=r!==i;if(a){let l;t._$AQ?.(i),t._$AM=i,t._$AP!==void 0&&(l=i._$AU)!==r._$AU&&t._$AP(l)}if(o!==n||a){let l=t._$AA;for(;l!==o;){const d=l.nextSibling;s.insertBefore(l,n),l=d}}}return t},st=(i,e,t=i)=>(i._$AI(e,t),i),Xu={},Zu=(i,e=Xu)=>i._$AH=e,Ju=i=>i._$AH,Vs=i=>{i._$AR(),i._$AA.remove()};function Qu(i){return i instanceof Node?"node":Ta(i)?"template-result":!Array.isArray(i)&&typeof i=="object"&&"template"in i?"slot-rerender-object":null}const eh=i=>class extends i{get slots(){return{}}constructor(){super(),this.__renderMetaPerSlot=new Map,this.__slotsThatNeedRerender=new Set,this.__slotsProvidedByUserOnFirstConnected=new Set,this.__privateSlots=new Set}connectedCallback(){super.connectedCallback(),this._connectSlotMixin()}__rerenderSlot(t){const s=this.slots[t]();this.__renderTemplateInScopedContext({renderAsDirectHostChild:s.renderAsDirectHostChild,template:s.template,slotName:t}),s.afterRender?.()}update(t){super.update(t);for(const s of this.__slotsThatNeedRerender)this.__rerenderSlot(s)}__renderTemplateInScopedContext({template:t,slotName:s,renderAsDirectHostChild:n}){if(!this.__renderMetaPerSlot.has(s)){const m=!!ShadowRoot.prototype.createElement;!!this.shadowRoot||console.error("[SlotMixin] No shadowRoot was found");const g=(m?this.shadowRoot:document).createElement("div"),_=document.createComment(`_start_slot_${s}_`),E=document.createComment(`_end_slot_${s}_`);g.appendChild(_),g.appendChild(E);const{creationScope:k,host:A}=this.renderOptions;if(fn(t,g,{renderBefore:E,creationScope:k,host:A}),n){const S=Array.from(g.childNodes);this.__appendNodes({nodes:S,renderParent:this,slotName:s})}else g.slot=s,this.appendChild(g);this.__renderMetaPerSlot.set(s,{renderTargetThatRespectsShadowRootScoping:g,renderBefore:E});return}const{renderBefore:r,renderTargetThatRespectsShadowRootScoping:a}=this.__renderMetaPerSlot.get(s),l=n?this:a,{creationScope:d,host:c}=this.renderOptions;fn(t,l,{creationScope:d,host:c,renderBefore:r}),n&&r.previousElementSibling&&!r.previousElementSibling.slot&&(r.previousElementSibling.slot=s)}__appendNodes({nodes:t,renderParent:s=this,slotName:n}){for(const o of t)o instanceof Element&&n&&n!==""&&o.setAttribute("slot",n),s.appendChild(o)}__initSlots(t){for(const s of t){if(this.__slotsProvidedByUserOnFirstConnected.has(s))continue;const n=this.slots[s]();if(n===void 0)continue;switch(this.__isConnectedSlotMixin||this.__privateSlots.add(s),Qu(n)){case"template-result":this.__renderTemplateInScopedContext({template:n,renderAsDirectHostChild:!0,slotName:s});break;case"node":this.__appendNodes({nodes:[n],renderParent:this,slotName:s});break;case"slot-rerender-object":this.__slotsThatNeedRerender.add(s),n.firstRenderOnConnected&&this.__rerenderSlot(s);break;default:throw new Error(`Slot "${s}" configured inside "get slots()" (in prototype) of ${this.constructor.name} may return these types: TemplateResult | Node | {template:TemplateResult, afterRender?:function} | undefined.
              You provided: ${n}`)}}}_connectSlotMixin(){if(this.__isConnectedSlotMixin)return;const t=Object.keys(this.slots);for(const s of t)(s===""?Array.from(this.children).find(o=>!o.hasAttribute("slot")):Array.from(this.children).find(o=>o.slot===s))&&this.__slotsProvidedByUserOnFirstConnected.add(s);this.__initSlots(t),this.__isConnectedSlotMixin=!0}_isPrivateSlot(t){return this.__privateSlots.has(t)}},Pt=ie(eh);function Ps(i="google-chrome"){const e=globalThis.navigator,t=!!e.userAgentData&&e.userAgentData.brands.some(l=>l.brand==="Chromium");if(i==="chromium")return t;const n=globalThis.navigator?.vendor,o=typeof globalThis.opr<"u",r=globalThis.userAgent?.indexOf("Edge")>-1,a=globalThis.userAgent?.match("CriOS");if(i==="ios")return a;if(i==="google-chrome")return t!==null&&typeof t<"u"&&n==="Google Inc."&&o===!1&&r===!1}const is={isChrome:Ps(),isIOSChrome:Ps("ios"),isChromium:Ps("chromium"),isFirefox:globalThis.navigator?.userAgent.toLowerCase().indexOf("firefox")>-1,isMac:globalThis.navigator?.appVersion?.indexOf("Mac")!==-1,isIOS:/iPhone|iPad|iPod/i.test(globalThis.navigator?.userAgent),isMacSafari:globalThis.navigator?.vendor&&globalThis.navigator?.vendor.indexOf("Apple")>-1&&globalThis.navigator?.userAgent&&globalThis.navigator?.userAgent.indexOf("CriOS")===-1&&globalThis.navigator?.userAgent.indexOf("FxiOS")===-1&&globalThis.navigator?.appVersion.indexOf("Mac")!==-1};function yi(i=""){return`${i.length>0?`${i}-`:""}${Math.random().toString(36).substr(2,10)}`}const zs=i=>i.key===" "||i.key==="Enter",Zo=i=>i.key===" ";class th extends Aa(H){static get properties(){return{active:{type:Boolean,reflect:!0},type:{type:String,reflect:!0}}}render(){return x` <div class="button-content"><slot></slot></div> `}static get styles(){return[O`
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
      `]}constructor(){super(),this.type="button",this.active=!1,this.__setupEvents()}connectedCallback(){super.connectedCallback(),this.hasAttribute("role")||this.setAttribute("role","button")}updated(e){super.updated(e),e.has("disabled")&&(this.disabled?this.setAttribute("aria-disabled","true"):this.getAttribute("aria-disabled")!==null&&this.removeAttribute("aria-disabled"))}__setupEvents(){this.addEventListener("mousedown",this.__mousedownHandler),this.addEventListener("keydown",this.__keydownHandler),this.addEventListener("keyup",this.__keyupHandler)}__mousedownHandler(){this.active=!0;const e=()=>{this.active=!1,document.removeEventListener("mouseup",e),this.removeEventListener("mouseup",e)};document.addEventListener("mouseup",e),this.addEventListener("mouseup",e)}__keydownHandler(e){if(this.active||!zs(e)){Zo(e)&&e.preventDefault();return}Zo(e)&&e.preventDefault(),this.active=!0;const t=s=>{zs(s)&&(this.active=!1,document.removeEventListener("keyup",t,!0))};document.addEventListener("keyup",t,!0)}__keyupHandler(e){if(zs(e)){if(e.target&&e.target!==this)return;this.click()}}}class ih extends th{constructor(){super(),this.type="reset",this.__setupDelegationInConstructor(),this.__submitAndResetHelperButton=document.createElement("button"),this.__preventEventLeakage=this.__preventEventLeakage.bind(this)}connectedCallback(){super.connectedCallback(),this.updateComplete.then(()=>{this._setupSubmitAndResetHelperOnConnected()})}disconnectedCallback(){super.disconnectedCallback(),this._teardownSubmitAndResetHelperOnDisconnected()}__preventEventLeakage(e){e.target===this.__submitAndResetHelperButton&&e.stopImmediatePropagation()}_setupSubmitAndResetHelperOnConnected(){this.appendChild(this.__submitAndResetHelperButton),this._form=this.__submitAndResetHelperButton.form,this.removeChild(this.__submitAndResetHelperButton),this._form&&this._form.addEventListener("click",this.__preventEventLeakage)}_teardownSubmitAndResetHelperOnDisconnected(){this._form&&this._form.removeEventListener("click",this.__preventEventLeakage)}async __clickDelegationHandler(e){this._form||await this.updateComplete,(this.type==="submit"||this.type==="reset")&&e.target===this&&this._form&&(this.__submitAndResetHelperButton.type=this.type,this._form.appendChild(this.__submitAndResetHelperButton),this.__submitAndResetHelperButton.click(),this._form.removeChild(this.__submitAndResetHelperButton))}__setupDelegationInConstructor(){this.addEventListener("click",this.__clickDelegationHandler,!0)}}const We=new WeakMap;function sh(){const i=document.createElement("button");return i.tabIndex=-1,i.type="submit",i.setAttribute("aria-hidden","true"),i.style.cssText=`
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
  `,i}class nh extends ih{get _nativeButtonNode(){return We.get(this._form)?.helper||null}constructor(){super(),this.type="submit",this.__implicitSubmitHelperButton=null}_setupSubmitAndResetHelperOnConnected(){if(super._setupSubmitAndResetHelperOnConnected(),!this._form||this.type!=="submit")return;const e=this._form;if(!We.get(this._form)){const s=sh(),n=document.createElement("div");n.appendChild(s),We.set(this._form,{lionButtons:new Set,helper:s,observer:new MutationObserver(()=>{e.appendChild(n)})}),e.appendChild(n),We.get(e)?.observer.observe(n,{childList:!0})}We.get(e)?.lionButtons.add(this)}_teardownSubmitAndResetHelperOnDisconnected(){if(super._teardownSubmitAndResetHelperOnDisconnected(),this._form){const e=We.get(this._form);e&&(e.lionButtons.delete(this),e.lionButtons.size||(this._form.contains(e.helper)&&e.helper.remove(),We.get(this._form)?.observer.disconnect(),We.delete(this._form)))}}}var oh=O`
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
`,qt=class extends nh{constructor(...e){super(...e),this.appearance="accent",this.variant="default",this.size="medium",this.loading=!1}static get styles(){return[...super.styles,oh]}render(){return x`
      <div class="button-content" part="content">
        <slot name="prefix" class="prefix" part="prefix"></slot>
        <slot class="label" part="label"></slot>
        <slot name="suffix" class="suffix" part="suffix"></slot>
      </div>
      ${this.loading?x`<craft-spinner part="spinner"></craft-spinner>`:B}
    `}};L([y({reflect:!0})],qt.prototype,"appearance",void 0),L([y({reflect:!0})],qt.prototype,"variant",void 0),L([y({reflect:!0})],qt.prototype,"size",void 0),L([y({reflect:!0,type:Boolean})],qt.prototype,"loading",void 0),customElements.get("craft-button")||customElements.define("craft-button",qt);var rh=O`
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
`,Ti=class extends H{constructor(...e){super(...e),this.label=null,this._gradientId=null}connectedCallback(){super.connectedCallback(),this._gradientId=`avatar-gradient-${Math.random().toString(36).slice(2,8)}`}text(){return this.label?this.label.split(" ").map(e=>e.charAt(0).toUpperCase()).join(""):"?"}render(){return x`
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
    `}};Ti.styles=[rh],L([y()],Ti.prototype,"label",void 0),L([be()],Ti.prototype,"_gradientId",void 0),customElements.get("craft-avatar")||customElements.define("craft-avatar",Ti);const Gn=O`
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
`,Yn=O`
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
`,Es=O`
  ${Yn}

  ::slotted([slot='input']) {
    ${Gn}
  }
`;var ah=O`
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
`;const _n=window,Jo=new WeakMap;function lh(i){_n.applyFocusVisiblePolyfill&&!Jo.has(i)&&(_n.applyFocusVisiblePolyfill(i),Jo.set(i,void 0))}const ch=i=>class extends i{static get properties(){return{focused:{type:Boolean,reflect:!0},focusedVisible:{type:Boolean,reflect:!0,attribute:"focused-visible"},autofocus:{type:Boolean,reflect:!0}}}constructor(){super(),this.focused=!1,this.focusedVisible=!1,this.autofocus=!1}firstUpdated(t){super.firstUpdated(t),this.__registerEventsForFocusMixin(),this.__syncAutofocusToFocusableElement()}disconnectedCallback(){super.disconnectedCallback(),this.__teardownEventsForFocusMixin()}updated(t){super.updated(t),t.has("autofocus")&&this.__syncAutofocusToFocusableElement()}__syncAutofocusToFocusableElement(){this._focusableNode&&(this.hasAttribute("autofocus")?this._focusableNode.setAttribute("autofocus",""):this._focusableNode.removeAttribute("autofocus"))}focus(){this._focusableNode?.focus()}blur(){this._focusableNode?.blur()}get _focusableNode(){return this._inputNode||document.createElement("input")}__onFocus(){if(this.focused=!0,typeof _n.applyFocusVisiblePolyfill=="function")this.focusedVisible=this._focusableNode.hasAttribute("data-focus-visible-added");else try{this.focusedVisible=this._focusableNode.matches(":focus-visible")}catch{this.focusedVisible=!1}}__onBlur(){this.focused=!1,this.focusedVisible=!1}__registerEventsForFocusMixin(){lh(this.getRootNode()),this.__redispatchFocus=t=>{t.stopPropagation(),this.dispatchEvent(new Event("focus"))},this._focusableNode.addEventListener("focus",this.__redispatchFocus),this.__redispatchBlur=t=>{t.stopPropagation(),this.dispatchEvent(new Event("blur"))},this._focusableNode.addEventListener("blur",this.__redispatchBlur),this.__redispatchFocusin=t=>{t.stopPropagation(),this.__onFocus(),this.dispatchEvent(new Event("focusin",{bubbles:!0,composed:!0}))},this._focusableNode.addEventListener("focusin",this.__redispatchFocusin),this.__redispatchFocusout=t=>{t.stopPropagation(),this.__onBlur(),this.dispatchEvent(new Event("focusout",{bubbles:!0,composed:!0}))},this._focusableNode.addEventListener("focusout",this.__redispatchFocusout)}__teardownEventsForFocusMixin(){this._focusableNode&&(this._focusableNode?.removeEventListener("focus",this.__redispatchFocus),this._focusableNode?.removeEventListener("blur",this.__redispatchBlur),this._focusableNode?.removeEventListener("focusin",this.__redispatchFocusin),this._focusableNode?.removeEventListener("focusout",this.__redispatchFocusout))}},Xn=ie(ch);function Na(i,e){return e={exports:{}},i(e,e.exports),e.exports}var nt="long",Ke="short",Bs="narrow",j="numeric",Ge="2-digit",Ye={number:{decimal:{style:"decimal"},integer:{style:"decimal",maximumFractionDigits:0},currency:{style:"currency",currency:"USD"},percent:{style:"percent"},default:{style:"decimal"}},date:{short:{month:j,day:j,year:Ge},medium:{month:Ke,day:j,year:j},long:{month:nt,day:j,year:j},full:{month:nt,day:j,year:j,weekday:nt},default:{month:Ke,day:j,year:j}},time:{short:{hour:j,minute:j},medium:{hour:j,minute:j,second:j},long:{hour:j,minute:j,second:j,timeZoneName:Ke},full:{hour:j,minute:j,second:j,timeZoneName:Ke},default:{hour:j,minute:j,second:j}},duration:{default:{hours:{minimumIntegerDigits:1,maximumFractionDigits:0},minutes:{minimumIntegerDigits:2,maximumFractionDigits:0},seconds:{minimumIntegerDigits:2,maximumFractionDigits:3}}},parseNumberPattern:function(i){if(i){var e={},t=i.match(/\b[A-Z]{3}\b/i),s=i.replace(/[^¤]/g,"").length;if(!s&&t&&(s=1),s?(e.style="currency",e.currencyDisplay=s===1?"symbol":s===2?"code":"name",e.currency=t?t[0].toUpperCase():"USD"):i.indexOf("%")>=0&&(e.style="percent"),!/[@#0]/.test(i))return e.style?e:void 0;if(e.useGrouping=i.indexOf(",")>=0,/E\+?[@#0]+/i.test(i)||i.indexOf("@")>=0){var n=i.replace(/E\+?[@#0]+|[^@#0]/gi,"");e.minimumSignificantDigits=Math.min(Math.max(n.replace(/[^@0]/g,"").length,1),21),e.maximumSignificantDigits=Math.min(Math.max(n.length,1),21)}else{for(var o=i.replace(/[^#0.]/g,"").split("."),r=o[0],a=r.length-1;r[a]==="0";)--a;e.minimumIntegerDigits=Math.min(Math.max(r.length-1-a,1),21);var l=o[1]||"";for(a=0;l[a]==="0";)++a;for(e.minimumFractionDigits=Math.min(Math.max(a,0),20);l[a]==="#";)++a;e.maximumFractionDigits=Math.min(Math.max(a,0),20)}return e}},parseDatePattern:function(i){if(i){for(var e={},t=0;t<i.length;){for(var s=i[t],n=1;i[++t]===s;)++n;switch(s){case"G":e.era=n===5?Bs:n===4?nt:Ke;break;case"y":case"Y":e.year=n===2?Ge:j;break;case"M":case"L":n=Math.min(Math.max(n-1,0),4),e.month=[j,Ge,Ke,nt,Bs][n];break;case"E":case"e":case"c":e.weekday=n===5?Bs:n===4?nt:Ke;break;case"d":case"D":e.day=n===2?Ge:j;break;case"h":case"K":e.hour12=!0,e.hour=n===2?Ge:j;break;case"H":case"k":e.hour12=!1,e.hour=n===2?Ge:j;break;case"m":e.minute=n===2?Ge:j;break;case"s":case"S":e.second=n===2?Ge:j;break;case"z":case"Z":case"v":case"V":e.timeZoneName=n===1?Ke:nt;break}}return Object.keys(e).length?e:void 0}}},dh=function(e,t){if(typeof e=="string"&&t[e])return e;for(var s=[].concat(e||[]),n=0,o=s.length;n<o;++n)for(var r=s[n].split("-");r.length;){var a=r.join("-");if(t[a])return a;r.pop()}},_t="zero",D="one",oe="two",G="few",se="many",$="other",f=[function(i){var e=+i;return e===1?D:$},function(i){var e=+i;return 0<=e&&e<=1?D:$},function(i){var e=Math.floor(Math.abs(+i)),t=+i;return e===0||t===1?D:$},function(i){var e=+i;return e===0?_t:e===1?D:e===2?oe:3<=e%100&&e%100<=10?G:11<=e%100&&e%100<=99?se:$},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length;return e===1&&t===0?D:$},function(i){var e=+i;return e%10===1&&e%100!==11?D:2<=e%10&&e%10<=4&&(e%100<12||14<e%100)?G:e%10===0||5<=e%10&&e%10<=9||11<=e%100&&e%100<=14?se:$},function(i){var e=+i;return e%10===1&&e%100!==11&&e%100!==71&&e%100!==91?D:e%10===2&&e%100!==12&&e%100!==72&&e%100!==92?oe:(3<=e%10&&e%10<=4||e%10===9)&&(e%100<10||19<e%100)&&(e%100<70||79<e%100)&&(e%100<90||99<e%100)?G:e!==0&&e%1e6===0?se:$},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length,s=+(i+".").split(".")[1];return t===0&&e%10===1&&e%100!==11||s%10===1&&s%100!==11?D:t===0&&2<=e%10&&e%10<=4&&(e%100<12||14<e%100)||2<=s%10&&s%10<=4&&(s%100<12||14<s%100)?G:$},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length;return e===1&&t===0?D:2<=e&&e<=4&&t===0?G:t!==0?se:$},function(i){var e=+i;return e===0?_t:e===1?D:e===2?oe:e===3?G:e===6?se:$},function(i){var e=Math.floor(Math.abs(+i)),t=+(""+i).replace(/^[^.]*.?|0+$/g,""),s=+i;return s===1||t!==0&&(e===0||e===1)?D:$},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length,s=+(i+".").split(".")[1];return t===0&&e%100===1||s%100===1?D:t===0&&e%100===2||s%100===2?oe:t===0&&3<=e%100&&e%100<=4||3<=s%100&&s%100<=4?G:$},function(i){var e=Math.floor(Math.abs(+i));return e===0||e===1?D:$},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length,s=+(i+".").split(".")[1];return t===0&&(e===1||e===2||e===3)||t===0&&e%10!==4&&e%10!==6&&e%10!==9||t!==0&&s%10!==4&&s%10!==6&&s%10!==9?D:$},function(i){var e=+i;return e===1?D:e===2?oe:3<=e&&e<=6?G:7<=e&&e<=10?se:$},function(i){var e=+i;return e===1||e===11?D:e===2||e===12?oe:3<=e&&e<=10||13<=e&&e<=19?G:$},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length;return t===0&&e%10===1?D:t===0&&e%10===2?oe:t===0&&(e%100===0||e%100===20||e%100===40||e%100===60||e%100===80)?G:t!==0?se:$},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length,s=+i;return e===1&&t===0?D:e===2&&t===0?oe:t===0&&(s<0||10<s)&&s%10===0?se:$},function(i){var e=Math.floor(Math.abs(+i)),t=+(""+i).replace(/^[^.]*.?|0+$/g,"");return t===0&&e%10===1&&e%100!==11||t!==0?D:$},function(i){var e=+i;return e===1?D:e===2?oe:$},function(i){var e=+i;return e===0?_t:e===1?D:$},function(i){var e=Math.floor(Math.abs(+i)),t=+i;return t===0?_t:(e===0||e===1)&&t!==0?D:$},function(i){var e=+(i+".").split(".")[1],t=+i;return t%10===1&&(t%100<11||19<t%100)?D:2<=t%10&&t%10<=9&&(t%100<11||19<t%100)?G:e!==0?se:$},function(i){var e=(i+".").split(".")[1].length,t=+(i+".").split(".")[1],s=+i;return s%10===0||11<=s%100&&s%100<=19||e===2&&11<=t%100&&t%100<=19?_t:s%10===1&&s%100!==11||e===2&&t%10===1&&t%100!==11||e!==2&&t%10===1?D:$},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length,s=+(i+".").split(".")[1];return t===0&&e%10===1&&e%100!==11||s%10===1&&s%100!==11?D:$},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length,s=+i;return e===1&&t===0?D:t!==0||s===0||s!==1&&1<=s%100&&s%100<=19?G:$},function(i){var e=+i;return e===1?D:e===0||2<=e%100&&e%100<=10?G:11<=e%100&&e%100<=19?se:$},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length;return e===1&&t===0?D:t===0&&2<=e%10&&e%10<=4&&(e%100<12||14<e%100)?G:t===0&&e!==1&&0<=e%10&&e%10<=1||t===0&&5<=e%10&&e%10<=9||t===0&&12<=e%100&&e%100<=14?se:$},function(i){var e=Math.floor(Math.abs(+i));return 0<=e&&e<=1?D:$},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length;return t===0&&e%10===1&&e%100!==11?D:t===0&&2<=e%10&&e%10<=4&&(e%100<12||14<e%100)?G:t===0&&e%10===0||t===0&&5<=e%10&&e%10<=9||t===0&&11<=e%100&&e%100<=14?se:$},function(i){var e=Math.floor(Math.abs(+i)),t=+i;return e===0||t===1?D:2<=t&&t<=10?G:$},function(i){var e=Math.floor(Math.abs(+i)),t=+(i+".").split(".")[1],s=+i;return s===0||s===1||e===0&&t===1?D:$},function(i){var e=Math.floor(Math.abs(+i)),t=(i+".").split(".")[1].length;return t===0&&e%100===1?D:t===0&&e%100===2?oe:t===0&&3<=e%100&&e%100<=4||t!==0?G:$},function(i){var e=+i;return 0<=e&&e<=1||11<=e&&e<=99?D:$},function(i){var e=+i;return e===1||e===5||e===7||e===8||e===9||e===10?D:e===2||e===3?oe:e===4?G:e===6?se:$},function(i){var e=Math.floor(Math.abs(+i));return e%10===1||e%10===2||e%10===5||e%10===7||e%10===8||e%100===20||e%100===50||e%100===70||e%100===80?D:e%10===3||e%10===4||e%1e3===100||e%1e3===200||e%1e3===300||e%1e3===400||e%1e3===500||e%1e3===600||e%1e3===700||e%1e3===800||e%1e3===900?G:e===0||e%10===6||e%100===40||e%100===60||e%100===90?se:$},function(i){var e=+i;return(e%10===2||e%10===3)&&e%100!==12&&e%100!==13?G:$},function(i){var e=+i;return e===1||e===3?D:e===2?oe:e===4?G:$},function(i){var e=+i;return e===0||e===7||e===8||e===9?_t:e===1?D:e===2?oe:e===3||e===4?G:e===5||e===6?se:$},function(i){var e=+i;return e%10===1&&e%100!==11?D:e%10===2&&e%100!==12?oe:e%10===3&&e%100!==13?G:$},function(i){var e=+i;return e===1||e===11?D:e===2||e===12?oe:e===3||e===13?G:$},function(i){var e=+i;return e===1?D:e===2||e===3?oe:e===4?G:e===6?se:$},function(i){var e=+i;return e===1||e===5?D:$},function(i){var e=+i;return e===11||e===8||e===80||e===800?se:$},function(i){var e=Math.floor(Math.abs(+i));return e===1?D:e===0||2<=e%100&&e%100<=20||e%100===40||e%100===60||e%100===80?se:$},function(i){var e=+i;return e%10===6||e%10===9||e%10===0&&e!==0?se:$},function(i){var e=Math.floor(Math.abs(+i));return e%10===1&&e%100!==11?D:e%10===2&&e%100!==12?oe:(e%10===7||e%10===8)&&e%100!==17&&e%100!==18?se:$},function(i){var e=+i;return e===1?D:e===2||e===3?oe:e===4?G:$},function(i){var e=+i;return 1<=e&&e<=4?D:$},function(i){var e=+i;return e===1||e===5||7<=e&&e<=9?D:e===2||e===3?oe:e===4?G:e===6?se:$},function(i){var e=+i;return e===1?D:e%10===4&&e%100!==14?se:$},function(i){var e=+i;return(e%10===1||e%10===2)&&e%100!==11&&e%100!==12?D:$},function(i){var e=+i;return e%10===6||e%10===9||e===10?G:$},function(i){var e=+i;return e%10===3&&e%100!==13?G:$}],vn={af:{cardinal:f[0]},ak:{cardinal:f[1]},am:{cardinal:f[2]},ar:{cardinal:f[3]},ars:{cardinal:f[3]},as:{cardinal:f[2],ordinal:f[34]},asa:{cardinal:f[0]},ast:{cardinal:f[4]},az:{cardinal:f[0],ordinal:f[35]},be:{cardinal:f[5],ordinal:f[36]},bem:{cardinal:f[0]},bez:{cardinal:f[0]},bg:{cardinal:f[0]},bh:{cardinal:f[1]},bn:{cardinal:f[2],ordinal:f[34]},br:{cardinal:f[6]},brx:{cardinal:f[0]},bs:{cardinal:f[7]},ca:{cardinal:f[4],ordinal:f[37]},ce:{cardinal:f[0]},cgg:{cardinal:f[0]},chr:{cardinal:f[0]},ckb:{cardinal:f[0]},cs:{cardinal:f[8]},cy:{cardinal:f[9],ordinal:f[38]},da:{cardinal:f[10]},de:{cardinal:f[4]},dsb:{cardinal:f[11]},dv:{cardinal:f[0]},ee:{cardinal:f[0]},el:{cardinal:f[0]},en:{cardinal:f[4],ordinal:f[39]},eo:{cardinal:f[0]},es:{cardinal:f[0]},et:{cardinal:f[4]},eu:{cardinal:f[0]},fa:{cardinal:f[2]},ff:{cardinal:f[12]},fi:{cardinal:f[4]},fil:{cardinal:f[13],ordinal:f[0]},fo:{cardinal:f[0]},fr:{cardinal:f[12],ordinal:f[0]},fur:{cardinal:f[0]},fy:{cardinal:f[4]},ga:{cardinal:f[14],ordinal:f[0]},gd:{cardinal:f[15],ordinal:f[40]},gl:{cardinal:f[4]},gsw:{cardinal:f[0]},gu:{cardinal:f[2],ordinal:f[41]},guw:{cardinal:f[1]},gv:{cardinal:f[16]},ha:{cardinal:f[0]},haw:{cardinal:f[0]},he:{cardinal:f[17]},hi:{cardinal:f[2],ordinal:f[41]},hr:{cardinal:f[7]},hsb:{cardinal:f[11]},hu:{cardinal:f[0],ordinal:f[42]},hy:{cardinal:f[12],ordinal:f[0]},ia:{cardinal:f[4]},io:{cardinal:f[4]},is:{cardinal:f[18]},it:{cardinal:f[4],ordinal:f[43]},iu:{cardinal:f[19]},iw:{cardinal:f[17]},jgo:{cardinal:f[0]},ji:{cardinal:f[4]},jmc:{cardinal:f[0]},ka:{cardinal:f[0],ordinal:f[44]},kab:{cardinal:f[12]},kaj:{cardinal:f[0]},kcg:{cardinal:f[0]},kk:{cardinal:f[0],ordinal:f[45]},kkj:{cardinal:f[0]},kl:{cardinal:f[0]},kn:{cardinal:f[2]},ks:{cardinal:f[0]},ksb:{cardinal:f[0]},ksh:{cardinal:f[20]},ku:{cardinal:f[0]},kw:{cardinal:f[19]},ky:{cardinal:f[0]},lag:{cardinal:f[21]},lb:{cardinal:f[0]},lg:{cardinal:f[0]},ln:{cardinal:f[1]},lt:{cardinal:f[22]},lv:{cardinal:f[23]},mas:{cardinal:f[0]},mg:{cardinal:f[1]},mgo:{cardinal:f[0]},mk:{cardinal:f[24],ordinal:f[46]},ml:{cardinal:f[0]},mn:{cardinal:f[0]},mo:{cardinal:f[25],ordinal:f[0]},mr:{cardinal:f[2],ordinal:f[47]},mt:{cardinal:f[26]},nah:{cardinal:f[0]},naq:{cardinal:f[19]},nb:{cardinal:f[0]},nd:{cardinal:f[0]},ne:{cardinal:f[0],ordinal:f[48]},nl:{cardinal:f[4]},nn:{cardinal:f[0]},nnh:{cardinal:f[0]},no:{cardinal:f[0]},nr:{cardinal:f[0]},nso:{cardinal:f[1]},ny:{cardinal:f[0]},nyn:{cardinal:f[0]},om:{cardinal:f[0]},or:{cardinal:f[0],ordinal:f[49]},os:{cardinal:f[0]},pa:{cardinal:f[1]},pap:{cardinal:f[0]},pl:{cardinal:f[27]},prg:{cardinal:f[23]},ps:{cardinal:f[0]},pt:{cardinal:f[28]},"pt-PT":{cardinal:f[4]},rm:{cardinal:f[0]},ro:{cardinal:f[25],ordinal:f[0]},rof:{cardinal:f[0]},ru:{cardinal:f[29]},rwk:{cardinal:f[0]},saq:{cardinal:f[0]},sc:{cardinal:f[4],ordinal:f[43]},scn:{cardinal:f[4],ordinal:f[43]},sd:{cardinal:f[0]},sdh:{cardinal:f[0]},se:{cardinal:f[19]},seh:{cardinal:f[0]},sh:{cardinal:f[7]},shi:{cardinal:f[30]},si:{cardinal:f[31]},sk:{cardinal:f[8]},sl:{cardinal:f[32]},sma:{cardinal:f[19]},smi:{cardinal:f[19]},smj:{cardinal:f[19]},smn:{cardinal:f[19]},sms:{cardinal:f[19]},sn:{cardinal:f[0]},so:{cardinal:f[0]},sq:{cardinal:f[0],ordinal:f[50]},sr:{cardinal:f[7]},ss:{cardinal:f[0]},ssy:{cardinal:f[0]},st:{cardinal:f[0]},sv:{cardinal:f[4],ordinal:f[51]},sw:{cardinal:f[4]},syr:{cardinal:f[0]},ta:{cardinal:f[0]},te:{cardinal:f[0]},teo:{cardinal:f[0]},ti:{cardinal:f[1]},tig:{cardinal:f[0]},tk:{cardinal:f[0],ordinal:f[52]},tl:{cardinal:f[13],ordinal:f[0]},tn:{cardinal:f[0]},tr:{cardinal:f[0]},ts:{cardinal:f[0]},tzm:{cardinal:f[33]},ug:{cardinal:f[0]},uk:{cardinal:f[29],ordinal:f[53]},ur:{cardinal:f[4]},uz:{cardinal:f[0]},ve:{cardinal:f[0]},vo:{cardinal:f[0]},vun:{cardinal:f[0]},wa:{cardinal:f[1]},wae:{cardinal:f[0]},xh:{cardinal:f[0]},xog:{cardinal:f[0]},yi:{cardinal:f[4]},zu:{cardinal:f[2]},lo:{ordinal:f[0]},ms:{ordinal:f[0]},vi:{ordinal:f[0]}},Cs=Na(function(i,e){e=i.exports=function(b,p,g){return t(b,null,p||"en",g||{},!0)},e.toParts=function(b,p,g){return t(b,null,p||"en",g||{},!1)};function t(m,b,p,g,_){var E=m.map(function(k){return s(k,b,p,g,_)});return _?E.length===1?E[0]:function(A){for(var S="",R=0;R<E.length;++R)S+=E[R](A);return S}:function(A){return E.reduce(function(S,R){return S.concat(R(A))},[])}}function s(m,b,p,g,_){if(typeof m=="string"){var E=m;return function(){return E}}var k=m[0],A=m[1];if(b&&m[0]==="#"){k=b[0];var S=b[2],R=(g.number||h.number)([k,"number"],p);return function(V){return R(n(k,V)-S,V)}}var U;A==="plural"||A==="selectordinal"?(U={},Object.keys(m[3]).forEach(function(X){U[X]=t(m[3][X],m,p,g,_)}),m=[m[0],m[1],m[2],U]):m[2]&&typeof m[2]=="object"&&(U={},Object.keys(m[2]).forEach(function(X){U[X]=t(m[2][X],m,p,g,_)}),m=[m[0],m[1],U]);var P=A&&(g[A]||h[A]);if(P){var J=P(m,p);return function(V){return J(n(k,V),V)}}return _?function(V){return String(n(k,V))}:function(V){return n(k,V)}}function n(m,b){if(b&&m in b)return b[m];for(var p=m.split("."),g=b,_=0,E=p.length;g&&_<E;++_)g=g[p[_]];return g}function o(m,b){var p=m[2],g=Ye.number[p]||Ye.parseNumberPattern(p)||Ye.number.default;return new Intl.NumberFormat(b,g).format}function r(m,b){var p=m[2],g=Ye.duration[p]||Ye.duration.default,_=new Intl.NumberFormat(b,g.seconds).format,E=new Intl.NumberFormat(b,g.minutes).format,k=new Intl.NumberFormat(b,g.hours).format,A=/^fi$|^fi-|^da/.test(String(b))?".":":";return function(S,R){if(S=+S,!isFinite(S))return _(S);var U=~~(S/60/60),P=~~(S/60%60),J=(U?k(Math.abs(U))+A:"")+E(Math.abs(P))+A+_(Math.abs(S%60));return S<0?k(-1).replace(k(1),J):J}}function a(m,b){var p=m[1],g=m[2],_=Ye[p][g]||Ye.parseDatePattern(g)||Ye[p].default;return new Intl.DateTimeFormat(b,_).format}function l(m,b){var p=m[1],g=p==="selectordinal"?"ordinal":"cardinal",_=m[2],E=m[3],k;if(Intl.PluralRules&&Intl.PluralRules.supportedLocalesOf(b).length>0)k=new Intl.PluralRules(b,{type:g});else{var A=dh(b,vn),S=A&&vn[A][g]||d;k={select:S}}return function(R,U){var P=E["="+ +R]||E[k.select(R-_)]||E.other;return P(U)}}function d(){return"other"}function c(m,b){var p=m[2];return function(g,_){var E=p[g]||p.other;return E(_)}}var h={number:o,ordinal:o,spellout:o,duration:r,date:a,time:a,plural:l,selectordinal:l,select:c};e.types=h});Cs.toParts;Cs.types;var Oa=Na(function(i,e){var t="{",s="}",n=",",o="#",r="<",a=">",l="</",d="/>",c="'",h="offset:",m=["number","date","time","ordinal","duration","spellout"],b=["plural","select","selectordinal"];e=i.exports=function(T,F){return p({pattern:String(T),index:0,tagsType:F&&F.tagsType||null,tokens:F&&F.tokens||null},"")};function p(u,T){var F=u.pattern,I=F.length,M=[],N=u.index,W=g(u,T);for(W&&M.push(W),W&&u.tokens&&u.tokens.push(["text",F.slice(N,u.index)]);u.index<I;){if(F[u.index]===s){if(!T)throw V(u);break}if(T&&u.tagsType&&F.slice(u.index,u.index+l.length)===l)break;M.push(k(u)),N=u.index,W=g(u,T),W&&M.push(W),W&&u.tokens&&u.tokens.push(["text",F.slice(N,u.index)])}return M}function g(u,T){for(var F=u.pattern,I=F.length,M=T==="plural"||T==="selectordinal",N=!!u.tagsType,W=T==="{style}",pe="";u.index<I;){var q=F[u.index];if(q===t||q===s||M&&q===o||N&&q===r||W&&_(q.charCodeAt(0)))break;if(q===c)if(q=F[++u.index],q===c)pe+=q,++u.index;else if(q===t||q===s||M&&q===o||N&&q===r||W)for(pe+=q;++u.index<I;)if(q=F[u.index],q===c&&F[u.index+1]===c)pe+=c,++u.index;else if(q===c){++u.index;break}else pe+=q;else pe+=c;else pe+=q,++u.index}return pe}function _(u){return u>=9&&u<=13||u===32||u===133||u===160||u===6158||u>=8192&&u<=8205||u===8232||u===8233||u===8239||u===8287||u===8288||u===12288||u===65279}function E(u){for(var T=u.pattern,F=T.length,I=u.index;u.index<F&&_(T.charCodeAt(u.index));)++u.index;I<u.index&&u.tokens&&u.tokens.push(["space",u.pattern.slice(I,u.index)])}function k(u){var T=u.pattern;if(T[u.index]===o)return u.tokens&&u.tokens.push(["syntax",o]),++u.index,[o];var F=A(u);if(F)return F;if(T[u.index]!==t)throw V(u,t);u.tokens&&u.tokens.push(["syntax",t]),++u.index,E(u);var I=S(u);if(!I)throw V(u,"placeholder id");u.tokens&&u.tokens.push(["id",I]),E(u);var M=T[u.index];if(M===s)return u.tokens&&u.tokens.push(["syntax",s]),++u.index,[I];if(M!==n)throw V(u,n+" or "+s);u.tokens&&u.tokens.push(["syntax",n]),++u.index,E(u);var N=S(u);if(!N)throw V(u,"placeholder type");if(u.tokens&&u.tokens.push(["type",N]),E(u),M=T[u.index],M===s){if(u.tokens&&u.tokens.push(["syntax",s]),N==="plural"||N==="selectordinal"||N==="select")throw V(u,N+" sub-messages");return++u.index,[I,N]}if(M!==n)throw V(u,n+" or "+s);u.tokens&&u.tokens.push(["syntax",n]),++u.index,E(u);var W;if(N==="plural"||N==="selectordinal"){var pe=U(u);E(u),W=[I,N,pe,J(u,N)]}else if(N==="select")W=[I,N,J(u,N)];else if(m.indexOf(N)>=0)W=[I,N,R(u)];else{var q=u.index,ze=R(u);E(u),T[u.index]===t&&(u.index=q,ze=J(u,N)),W=[I,N,ze]}if(E(u),T[u.index]!==s)throw V(u,s);return u.tokens&&u.tokens.push(["syntax",s]),++u.index,W}function A(u){var T=u.tagsType;if(!(!T||u.pattern[u.index]!==r)){if(u.pattern.slice(u.index,u.index+l.length)===l)throw V(u,null,"closing tag without matching opening tag");u.tokens&&u.tokens.push(["syntax",r]),++u.index;var F=S(u,!0);if(!F)throw V(u,"placeholder id");if(u.tokens&&u.tokens.push(["id",F]),E(u),u.pattern.slice(u.index,u.index+d.length)===d)return u.tokens&&u.tokens.push(["syntax",d]),u.index+=d.length,[F,T];if(u.pattern[u.index]!==a)throw V(u,a);u.tokens&&u.tokens.push(["syntax",a]),++u.index;var I=p(u,T),M=u.index;if(u.pattern.slice(u.index,u.index+l.length)!==l)throw V(u,l+F+a);u.tokens&&u.tokens.push(["syntax",l]),u.index+=l.length;var N=S(u,!0);if(N&&u.tokens&&u.tokens.push(["id",N]),F!==N)throw u.index=M,V(u,l+F+a,l+N+a);if(E(u),u.pattern[u.index]!==a)throw V(u,a);return u.tokens&&u.tokens.push(["syntax",a]),++u.index,[F,T,{children:I}]}}function S(u,T){for(var F=u.pattern,I=F.length,M="";u.index<I;){var N=F[u.index];if(N===t||N===s||N===n||N===o||N===c||_(N.charCodeAt(0))||T&&(N===r||N===a||N==="/"))break;M+=N,++u.index}return M}function R(u){var T=u.index,F=g(u,"{style}");if(!F)throw V(u,"placeholder style name");return u.tokens&&u.tokens.push(["style",u.pattern.slice(T,u.index)]),F}function U(u){var T=u.pattern,F=T.length,I=0;if(T.slice(u.index,u.index+h.length)===h){u.tokens&&u.tokens.push(["offset","offset"],["syntax",":"]),u.index+=h.length,E(u);for(var M=u.index;u.index<F&&P(T.charCodeAt(u.index));)++u.index;if(M===u.index)throw V(u,"offset number");u.tokens&&u.tokens.push(["number",T.slice(M,u.index)]),I=+T.slice(M,u.index)}return I}function P(u){return u>=48&&u<=57}function J(u,T){for(var F=u.pattern,I=F.length,M={};u.index<I&&F[u.index]!==s;){var N=S(u);if(!N)throw V(u,"sub-message selector");u.tokens&&u.tokens.push(["selector",N]),E(u),M[N]=X(u,T),E(u)}if(!M.other&&b.indexOf(T)>=0)throw V(u,null,null,'"other" sub-message must be specified in '+T);return M}function X(u,T){if(u.pattern[u.index]!==t)throw V(u,t+" to start sub-message");u.tokens&&u.tokens.push(["syntax",t]),++u.index;var F=p(u,T);if(u.pattern[u.index]!==s)throw V(u,s+" to end sub-message");return u.tokens&&u.tokens.push(["syntax",s]),++u.index,F}function V(u,T,F,I){var M=u.pattern,N=M.slice(0,u.index).split(/\r?\n/),W=u.index,pe=N.length,q=N.slice(-1)[0].length;return F=F||(u.index>=M.length?"end of message pattern":S(u)||M[u.index]),I||(I=_e(T,F)),I+=" in "+M.replace(/\r?\n/g,`
`),new ne(I,T,F,W,pe,q)}function _e(u,T){return u?"Expected "+u+" but found "+T:"Unexpected "+T+" found"}function ne(u,T,F,I,M,N){Error.call(this,u),this.name="SyntaxError",this.message=u,this.expected=T,this.found=F,this.offset=I,this.line=M,this.column=N}ne.prototype=Object.create(Error.prototype),e.SyntaxError=ne});Oa.SyntaxError;var uh=new RegExp("^("+Object.keys(vn).join("|")+")\\b"),ii=new WeakMap;function Nt(i,e,t){if(!(this instanceof Nt)||ii.has(this))throw new TypeError("calling MessageFormat constructor without new is invalid");var s=Oa(i);ii.set(this,{ast:s,format:Cs(s,e,t&&t.types),locale:Nt.supportedLocalesOf(e)[0]||"en",locales:e,options:t})}var hh=Nt;Object.defineProperties(Nt.prototype,{format:{configurable:!0,get:function(){var e=ii.get(this);if(!e)throw new TypeError("MessageFormat.prototype.format called on value that's not an object initialized as a MessageFormat");return e.format}},formatToParts:{configurable:!0,writable:!0,value:function(e){var t=ii.get(this);if(!t)throw new TypeError("MessageFormat.prototype.formatToParts called on value that's not an object initialized as a MessageFormat");var s=t.toParts||(t.toParts=Cs.toParts(t.ast,t.locales,t.options&&t.options.types));return s(e)}},resolvedOptions:{configurable:!0,writable:!0,value:function(){var e=ii.get(this);if(!e)throw new TypeError("MessageFormat.prototype.resolvedOptions called on value that's not an object initialized as a MessageFormat");return{locale:e.locale}}}});typeof Symbol<"u"&&Object.defineProperty(Nt.prototype,Symbol.toStringTag,{value:"Object"});Object.defineProperties(Nt,{supportedLocalesOf:{configurable:!0,writable:!0,value:function(e){return[].concat(Intl.NumberFormat.supportedLocalesOf(e),Intl.DateTimeFormat.supportedLocalesOf(e),Intl.PluralRules?Intl.PluralRules.supportedLocalesOf(e):[],[].concat(e||[]).filter(function(t){return uh.test(t)})).filter(function(t,s,n){return n.indexOf(t)===s})}}});function ph(i){return!!(i&&i.default&&typeof i.default=="object"&&Object.keys(i).length===1)}const Xe=globalThis.document?.documentElement;class fh extends EventTarget{formatNumberOptions={returnIfNaN:"",postProcessors:new Map};formatDateOptions={postProcessors:new Map};#e=!1;#t="";#i=null;__storage={};__namespacePatternsMap=new Map;__namespaceLoadersCache={};__namespaceLoaderPromisesCache={};get locale(){return this.#e?this.#t||"":Xe.lang||""}set locale(e){if(this.#s(e),!this.#e){const n=Xe.lang;this._setHtmlLangAttribute(e),this._onLocaleChanged(e,n);return}const t=this.#t;this.#t=e,this.#i===null&&this._setHtmlLangAttribute(e),this._onLocaleChanged(e,t)}get loadingComplete(){return typeof this.__namespaceLoaderPromisesCache[this.locale]=="object"?Promise.all(Object.values(this.__namespaceLoaderPromisesCache[this.locale])):Promise.resolve()}constructor({allowOverridesForExistingNamespaces:e=!1,autoLoadOnLocaleChange:t=!1,showKeyAsFallback:s=!1,fallbackLocale:n=""}={}){super(),this.__allowOverridesForExistingNamespaces=e,this._autoLoadOnLocaleChange=!!t,this._showKeyAsFallback=s,this._fallbackLocale=n;const o=Xe.getAttribute("data-localize-lang");this.#e=!!o,this.#e&&(this.locale=o,this._setupTranslationToolSupport()),Xe.lang||(Xe.lang=this.locale||"en-GB"),this._setupHtmlLangAttributeObserver()}addData(e,t,s){if(!this.__allowOverridesForExistingNamespaces&&this._isNamespaceInCache(e,t))throw new Error(`Namespace "${t}" has been already added for the locale "${e}".`);this.__storage[e]=this.__storage[e]||{},this.__allowOverridesForExistingNamespaces?this.__storage[e][t]={...this.__storage[e][t],...s}:this.__storage[e][t]=s}setupNamespaceLoader(e,t){this.__namespacePatternsMap.set(e,t)}loadNamespaces(e,{locale:t}={}){return Promise.all(e.map(s=>this.loadNamespace(s,{locale:t})))}loadNamespace(e,{locale:t=this.locale}={locale:this.locale}){const s=typeof e=="object",n=s?Object.keys(e)[0]:e;if(this._isNamespaceInCache(t,n))return Promise.resolve();const o=this._getCachedNamespaceLoaderPromise(t,n);return o||this._loadNamespaceData(t,e,s,n)}msg(e,t,s={}){const n=s.locale?s.locale:this.locale,o=this._getMessageForKeys(e,n);return o?new hh(o,n).format(t):""}teardown(){this._teardownHtmlLangAttributeObserver()}reset(){this.__storage={},this.__namespacePatternsMap=new Map,this.__namespaceLoadersCache={},this.__namespaceLoaderPromisesCache={}}setDatePostProcessorForLocale({locale:e,postProcessor:t}){this.formatDateOptions?.postProcessors.set(e,t)}setNumberPostProcessorForLocale({locale:e,postProcessor:t}){this.formatNumberOptions?.postProcessors.set(e,t)}_setupTranslationToolSupport(){this.#i=Xe.lang||null}_setHtmlLangAttribute(e){this._teardownHtmlLangAttributeObserver(),Xe.lang=e,this._setupHtmlLangAttributeObserver()}_setupHtmlLangAttributeObserver(){this._htmlLangAttributeObserver||(this._htmlLangAttributeObserver=new MutationObserver(e=>{e.forEach(t=>{this.#e?Xe.lang==="auto"?(this.#i=null,this._setHtmlLangAttribute(this.locale)):this.#i=document.documentElement.lang:this._onLocaleChanged(document.documentElement.lang,t.oldValue||"")})})),this._htmlLangAttributeObserver.observe(document.documentElement,{attributes:!0,attributeFilter:["lang"],attributeOldValue:!0})}_teardownHtmlLangAttributeObserver(){this._htmlLangAttributeObserver&&this._htmlLangAttributeObserver.disconnect()}_isNamespaceInCache(e,t){return!!(this.__storage[e]&&this.__storage[e][t])}_getCachedNamespaceLoaderPromise(e,t){return this.__namespaceLoaderPromisesCache[e]?this.__namespaceLoaderPromisesCache[e][t]:null}_loadNamespaceData(e,t,s,n){const o=this._getNamespaceLoader(t,s,n),r=this._getNamespaceLoaderPromise(o,e,n);return this._cacheNamespaceLoaderPromise(e,n,r),r.then(a=>{if(this.__namespaceLoaderPromisesCache[e]&&this.__namespaceLoaderPromisesCache[e][n]===r){const l=ph(a)?a.default:a;this.addData(e,n,l)}})}_getNamespaceLoader(e,t,s){let n=this.__namespaceLoadersCache[s];if(n||(t?(n=e[s],this.__namespaceLoadersCache[s]=n):(n=this._lookupNamespaceLoader(s),this.__namespaceLoadersCache[s]=n)),!n)throw new Error(`Namespace "${s}" was not properly setup.`);return this.__namespaceLoadersCache[s]=n,n}_getNamespaceLoaderPromise(e,t,s,n=this._fallbackLocale){return e(t,s).catch(()=>{const o=this._getLangFromLocale(t);return e(o,s).catch(()=>{if(n)return this._getNamespaceLoaderPromise(e,n,s,"").catch(()=>{const r=this._getLangFromLocale(n);throw new Error(`Data for namespace "${s}" and current locale "${t}" or fallback locale "${n}" could not be loaded. Make sure you have data either for locale "${t}" (and/or generic language "${o}") or for fallback "${n}" (and/or "${r}").`)});throw new Error(`Data for namespace "${s}" and locale "${t}" could not be loaded. Make sure you have data for locale "${t}" (and/or generic language "${o}").`)})})}_cacheNamespaceLoaderPromise(e,t,s){this.__namespaceLoaderPromisesCache[e]||(this.__namespaceLoaderPromisesCache[e]={}),this.__namespaceLoaderPromisesCache[e][t]=s}_lookupNamespaceLoader(e){for(const[t,s]of this.__namespacePatternsMap){const n=typeof t=="string"&&t===e,o=typeof t=="object"&&t.constructor.name==="RegExp"&&t.test(e);if(n||o)return s}return null}_getLangFromLocale(e){return e.substring(0,2)}_onLocaleChanged(e,t){this.dispatchEvent(new CustomEvent("__localeChanging")),e!==t&&(this._autoLoadOnLocaleChange?(this._loadAllMissing(e,t),this.loadingComplete.then(()=>{this.dispatchEvent(new CustomEvent("localeChanged",{detail:{newLocale:e,oldLocale:t}}))})):this.dispatchEvent(new CustomEvent("localeChanged",{detail:{newLocale:e,oldLocale:t}})))}_loadAllMissing(e,t){const s=this.__storage[t]||{},n=this.__storage[e]||{};Object.keys(s).forEach(o=>{n[o]||this.loadNamespace(o,{locale:e})})}_getMessageForKeys(e,t){if(typeof e=="string")return this._getMessageForKey(e,t);const s=Array.from(e).reverse();let n,o;for(;s.length;)if(n=s.pop(),o=this._getMessageForKey(n,t),o)return o}_getMessageForKey(e,t){if(!e||e.indexOf(":")===-1)throw new Error(`Namespace is missing in the key "${e}". The format for keys is "namespace:name".`);const[s,n]=e.split(":"),o=this.__storage[t],r=o?o[s]:{},l=n.split(".").reduce((d,c)=>typeof d=="object"?d[c]:d,r);return String(l||(this._showKeyAsFallback?e:""))}#s(e){if(!e.includes("-"))throw new Error(`
      Locale was set to ${e}.
      Language only locales are not allowed, please use the full language locale e.g. 'en-GB' instead of 'en'.
      See https://github.com/ing-bank/lion/issues/187 for more information.
    `)}get _supportExternalTranslationTools(){return this.#e}set _supportExternalTranslationTools(e){this.#e=e}get _langAttrSetByTranslationTool(){return this.#t}set _langAttrSetByTranslationTool(e){this.#t=e}}const Us=Symbol.for("lion::SingletonManagerClassStorage"),Hs=globalThis||window;class mh{constructor(){this._map=Hs[Us]?Hs[Us]:Hs[Us]=new Map}set(e,t){this.has(e)||this._map.set(e,t)}get(e){return this._map.get(e)}has(e){return this._map.has(e)}}const qi=new mh;function ss(){if(qi.has("@lion/ui::localize::0.x"))return qi.get("@lion/ui::localize::0.x");const i=new fh({autoLoadOnLocaleChange:!0,fallbackLocale:"en-GB"});return qi.set("@lion/ui::localize::0.x",i),i}const si=(i,e)=>{const t=i._$AN;if(t===void 0)return!1;for(const s of t)s._$AO?.(e,!1),si(s,e);return!0},ns=i=>{let e,t;do{if((e=i._$AM)===void 0)break;t=e._$AN,t.delete(i),i=e}while(t?.size===0)},Fa=i=>{for(let e;e=i._$AM;i=e){let t=e._$AN;if(t===void 0)e._$AN=t=new Set;else if(t.has(i))break;t.add(i),_h(e)}};function bh(i){this._$AN!==void 0?(ns(this),this._$AM=i,Fa(this)):this._$AM=i}function gh(i,e=!1,t=0){const s=this._$AH,n=this._$AN;if(n!==void 0&&n.size!==0)if(e)if(Array.isArray(s))for(let o=t;o<s.length;o++)si(s[o],!1),ns(s[o]);else s!=null&&(si(s,!1),ns(s));else si(this,i)}const _h=i=>{i.type==ys.CHILD&&(i._$AP??=gh,i._$AQ??=bh)};class vh extends xs{constructor(){super(...arguments),this._$AN=void 0}_$AT(e,t,s){super._$AT(e,t,s),Fa(this),this.isConnected=e._$AU}_$AO(e,t=!0){e!==this.isConnected&&(this.isConnected=e,e?this.reconnected?.():this.disconnected?.()),t&&(si(this,e),ns(this))}setValue(e){if(Yu(this._$Ct))this._$Ct._$AI(e,this);else{const t=[...this._$Ct._$AH];t[this._$Ci]=e,this._$Ct._$AI(t,this,0)}}disconnected(){}reconnected(){}}let yh=class{constructor(e){this.G=e}disconnect(){this.G=void 0}reconnect(e){this.G=e}deref(){return this.G}},wh=class{constructor(){this.Y=void 0,this.Z=void 0}get(){return this.Y}pause(){this.Y??=new Promise((e=>this.Z=e))}resume(){this.Z?.(),this.Y=this.Z=void 0}};const Qo=i=>!Gu(i)&&typeof i.then=="function",er=1073741823;let xh=class extends vh{constructor(){super(...arguments),this._$Cwt=er,this._$Cbt=[],this._$CK=new yh(this),this._$CX=new wh}render(...e){return e.find((t=>!Qo(t)))??Te}update(e,t){const s=this._$Cbt;let n=s.length;this._$Cbt=t;const o=this._$CK,r=this._$CX;this.isConnected||this.disconnected();for(let a=0;a<t.length&&!(a>this._$Cwt);a++){const l=t[a];if(!Qo(l))return this._$Cwt=a,l;a<n&&l===s[a]||(this._$Cwt=er,n=0,Promise.resolve(l).then((async d=>{for(;r.get();)await r.get();const c=o.deref();if(c!==void 0){const h=c._$Cbt.indexOf(l);h>-1&&h<c._$Cwt&&(c._$Cwt=h,c.setValue(d))}})))}return Te}disconnected(){this._$CK.disconnect(),this._$CX.pause()}reconnected(){this._$CK.reconnect(this),this._$CX.resume()}};const Eh=ws(xh),Ch=i=>class extends i{static get localizeNamespaces(){return[]}static get waitForLocalizeNamespaces(){return!0}constructor(){super(),this._localizeManager=ss(),this.__boundLocalizeOnLocaleChanged=(...t)=>{const s=Array.from(t)[0];this.__localizeOnLocaleChanged(s)},this.__boundLocalizeOnLocaleChanging=()=>{this.__localizeOnLocaleChanging()},this.__localizeStartLoadingNamespaces(),this.localizeNamespacesLoaded&&this.localizeNamespacesLoaded.then(()=>{this.__localizeMessageSync=!0})}async scheduleUpdate(){Object.getPrototypeOf(this).constructor.waitForLocalizeNamespaces&&await this.localizeNamespacesLoaded,super.scheduleUpdate()}connectedCallback(){super.connectedCallback(),this.localizeNamespacesLoaded&&this.localizeNamespacesLoaded.then(()=>this.onLocaleReady()),this._localizeManager.addEventListener("__localeChanging",this.__boundLocalizeOnLocaleChanging),this._localizeManager.addEventListener("localeChanged",this.__boundLocalizeOnLocaleChanged)}disconnectedCallback(){super.disconnectedCallback(),this._localizeManager.removeEventListener("__localeChanging",this.__boundLocalizeOnLocaleChanging),this._localizeManager.removeEventListener("localeChanged",this.__boundLocalizeOnLocaleChanged)}msgLit(t,s,n){return this.__localizeMessageSync?this._localizeManager.msg(t,s,n):this.localizeNamespacesLoaded?Eh(this.localizeNamespacesLoaded.then(()=>this._localizeManager.msg(t,s,n)),B):""}__getUniqueNamespaces(){const t=[],s=new Set;return Object.getPrototypeOf(this).constructor.localizeNamespaces.forEach(s.add.bind(s)),s.forEach(n=>{t.push(n)}),t}__localizeStartLoadingNamespaces(){this.localizeNamespacesLoaded=this._localizeManager.loadNamespaces(this.__getUniqueNamespaces())}__localizeOnLocaleChanging(){this.__localizeStartLoadingNamespaces()}__localizeOnLocaleChanged(t){this.onLocaleChanged(t.detail.newLocale,t.detail.oldLocale)}onLocaleReady(){this.onLocaleUpdated()}onLocaleChanged(t,s){this.onLocaleUpdated(),this.requestUpdate()}onLocaleUpdated(){}},ks=ie(Ch),yn="3.0.0",tr=window.scopedElementsVersions||(window.scopedElementsVersions=[]);tr.includes(yn)||tr.push(yn);const kh=i=>class extends i{static scopedElements;static get scopedElementsVersion(){return yn}static __registry;get registry(){return this.constructor.__registry}set registry(t){this.constructor.__registry=t}attachShadow(t){const{scopedElements:s}=this.constructor;if(!this.registry||this.registry===this.constructor.__registry&&!Object.prototype.hasOwnProperty.call(this.constructor,"__registry")){this.registry=new CustomElementRegistry;for(const[o,r]of Object.entries(s??{}))this.registry.define(o,r)}return super.attachShadow({...t,customElements:this.registry,registry:this.registry})}},Sh=ie(kh),Ah=i=>class extends Sh(i){createRenderRoot(){const{shadowRootOptions:t,elementStyles:s}=this.constructor,n=this.attachShadow(t);return this.renderOptions.creationScope=n,Dn(n,s),this.renderOptions.renderBefore??=n.firstChild,n}},Th=ie(Ah);function Ni(){return!!(globalThis.ShadowRoot?.prototype.createElement&&globalThis.ShadowRoot?.prototype.importNode)}const Nh=i=>class extends Th(i){constructor(){super()}createScopedElement(t){return(Ni()?this.shadowRoot:document).createElement(t)}defineScopedElement(t,s){const n=this.registry.get(t),o=n&&n!==s;return!Ni()&&o&&console.error([`You are trying to re-register the "${t}" custom element with a different class via ScopedElementsMixin.`,"This is only possible with a CustomElementRegistry.","Your browser does not support this feature so you will need to load a polyfill for it.",'Load "@webcomponents/scoped-custom-element-registry" before you register ANY web component to the global customElements registry.','e.g. add "<script src="/node_modules/@webcomponents/scoped-custom-element-registry/scoped-custom-element-registry.min.js"><\/script>" as your first script tag.',"For more details you can visit https://open-wc.org/docs/development/scoped-elements/"].join(`
`)),n?this.registry.get(t):this.registry.define(t,s)}attachShadow(t){const{scopedElements:s}=this.constructor;if(!this.registry||this.registry===this.constructor.__registry&&!Object.prototype.hasOwnProperty.call(this.constructor,"__registry")){this.registry=Ni()?new CustomElementRegistry:customElements;for(const[o,r]of Object.entries(s??{}))this.defineScopedElement(o,r)}return Element.prototype.attachShadow.call(this,{...t,customElements:this.registry,registry:this.registry})}createRenderRoot(){const{shadowRootOptions:t,elementStyles:s}=this.constructor,n=this.attachShadow(t);return Ni()&&(this.renderOptions.creationScope=n),n instanceof ShadowRoot&&(Dn(n,s),this.renderOptions.renderBefore=this.renderOptions.renderBefore||n.firstChild),n}},wi=ie(Nh);class Oh{constructor(){this.__running=!1,this.__queue=[]}add(e){this.__queue.push(e),this.__running||(this.complete=new Promise(t=>{this.__callComplete=t}),this.__run())}async __run(){this.__running=!0,await this.__queue[0](),this.__queue.shift(),this.__queue.length>0?this.__run():(this.__running=!1,this.__callComplete&&this.__callComplete())}}function Fh(i){return i.charAt(0).toUpperCase()+i.slice(1)}const Lh=i=>class extends i{constructor(){super(),this.__SyncUpdatableNamespace={}}firstUpdated(e){super.firstUpdated(e),this.__syncUpdatableInitialize()}connectedCallback(){super.connectedCallback(),this.__SyncUpdatableNamespace.connected=!0}disconnectedCallback(){super.disconnectedCallback(),this.__SyncUpdatableNamespace.connected=!1}static enabledWarnings=super.enabledWarnings?.filter(e=>e!=="change-in-update")||[];static __syncUpdatableHasChanged(e,t,s){const n=this.elementProperties;return n.get(e)&&n.get(e).hasChanged?n.get(e).hasChanged(t,s):t!==s}__syncUpdatableInitialize(){const e=this.__SyncUpdatableNamespace,t=this.constructor;e.initialized=!0,e.queue&&Array.from(e.queue).forEach(s=>{t.__syncUpdatableHasChanged(s,this[s],void 0)&&this.updateSync(s,void 0)})}requestUpdate(e,t,s){if(super.requestUpdate(e,t,s),e===void 0)return;this.__SyncUpdatableNamespace=this.__SyncUpdatableNamespace||{};const n=this.__SyncUpdatableNamespace,o=this.constructor;n.initialized?o.__syncUpdatableHasChanged(e,this[e],t)&&this.updateSync(e,t):(n.queue=n.queue||new Set,n.queue.add(e))}updateSync(e,t){}},$h=ie(Lh),Rh=i=>{switch(i){case"bg-BG":return C(()=>import("./bg-BG2.js"),__vite__mapDeps([0,1]),import.meta.url);case"bg":return C(()=>import("./bg3.js"),[],import.meta.url);case"cs-CZ":return C(()=>import("./cs-CZ2.js"),__vite__mapDeps([2,3]),import.meta.url);case"cs":return C(()=>import("./cs3.js"),[],import.meta.url);case"de-DE":return C(()=>import("./de-DE2.js"),__vite__mapDeps([4,5]),import.meta.url);case"de":return C(()=>import("./de3.js"),[],import.meta.url);case"en-AU":return C(()=>import("./en-AU2.js"),__vite__mapDeps([6,7]),import.meta.url);case"en-GB":return C(()=>import("./en-GB2.js"),__vite__mapDeps([8,7]),import.meta.url);case"en-US":return C(()=>import("./en-US2.js"),__vite__mapDeps([9,7]),import.meta.url);case"en-PH":case"en":return C(()=>import("./en3.js"),[],import.meta.url);case"es-ES":return C(()=>import("./es-ES2.js"),__vite__mapDeps([10,11]),import.meta.url);case"es":return C(()=>import("./es3.js"),[],import.meta.url);case"fr-FR":return C(()=>import("./fr-FR2.js"),__vite__mapDeps([12,13]),import.meta.url);case"fr-BE":return C(()=>import("./fr-BE2.js"),__vite__mapDeps([14,13]),import.meta.url);case"fr":return C(()=>import("./fr3.js"),[],import.meta.url);case"hu-HU":return C(()=>import("./hu-HU2.js"),__vite__mapDeps([15,16]),import.meta.url);case"hu":return C(()=>import("./hu3.js"),[],import.meta.url);case"it-IT":return C(()=>import("./it-IT2.js"),__vite__mapDeps([17,18]),import.meta.url);case"it":return C(()=>import("./it3.js"),[],import.meta.url);case"nl-BE":return C(()=>import("./nl-BE2.js"),__vite__mapDeps([19,20]),import.meta.url);case"nl-NL":return C(()=>import("./nl-NL2.js"),__vite__mapDeps([21,20]),import.meta.url);case"nl":return C(()=>import("./nl3.js"),[],import.meta.url);case"pl-PL":return C(()=>import("./pl-PL2.js"),__vite__mapDeps([22,23]),import.meta.url);case"pl":return C(()=>import("./pl3.js"),[],import.meta.url);case"ro-RO":return C(()=>import("./ro-RO2.js"),__vite__mapDeps([24,25]),import.meta.url);case"ro":return C(()=>import("./ro3.js"),[],import.meta.url);case"ru-RU":return C(()=>import("./ru-RU2.js"),__vite__mapDeps([26,27]),import.meta.url);case"ru":return C(()=>import("./ru3.js"),[],import.meta.url);case"sk-SK":return C(()=>import("./sk-SK2.js"),__vite__mapDeps([28,29]),import.meta.url);case"sk":return C(()=>import("./sk3.js"),[],import.meta.url);case"tr-TR":return C(()=>import("./tr-TR.js"),__vite__mapDeps([30,31]),import.meta.url);case"tr":return C(()=>import("./tr.js"),[],import.meta.url);case"uk-UA":return C(()=>import("./uk-UA2.js"),__vite__mapDeps([32,33]),import.meta.url);case"uk":return C(()=>import("./uk3.js"),[],import.meta.url);case"zh-CN":case"zh":return C(()=>import("./zh3.js"),[],import.meta.url);default:return C(()=>import("./en3.js"),[],import.meta.url)}},Ih=i=>`${i[0].toUpperCase()}${i.slice(1)}`;class La extends ks(H){static get properties(){return{feedbackData:{attribute:!1}}}static localizeNamespaces=[{"lion-form-core":Rh},...super.localizeNamespaces];static get styles(){return[O`
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
      ${this.feedbackData&&this.feedbackData.map(({message:e,type:t,validator:s})=>x`
          <div class="validation-feedback__type">
            ${e&&t?this._localizeManager.msg(`lion-form-core:validation${Ih(t)}`):B}
          </div>
          ${this._messageTemplate({message:e,type:t,validator:s})}
        `)}
    `}}class bt{constructor(e){this.type="unparseable",this.viewValue=e}toString(){return JSON.stringify({type:this.type,viewValue:this.viewValue})}}const Dh=[Node.DOCUMENT_POSITION_PRECEDING,Node.DOCUMENT_POSITION_CONTAINS,Node.DOCUMENT_POSITION_CONTAINS|Node.DOCUMENT_POSITION_PRECEDING];function $a(i,{reverse:e}={}){const t=(n,o)=>{const r=n.compareDocumentPosition(o);return Dh.includes(r)?1:-1},s=i.filter(n=>n);return s.sort(t),e&&s.reverse(),s}const Mh=i=>class extends i{constructor(){super(),this.name="",this._parentFormGroup=void 0,this.allowCrossRootRegistration=!1}get name(){return this.__name||""}set name(e){const t=this.name;this.__name=e.toString(),this.requestUpdate("name",t)}static get properties(){return{name:{type:String,reflect:!0},allowCrossRootRegistration:{type:Boolean,attribute:"allow-cross-root-registration"}}}connectedCallback(){super.connectedCallback(),this.dispatchEvent(new CustomEvent("form-element-register",{detail:{element:this},bubbles:!0,composed:!!this.allowCrossRootRegistration}))}disconnectedCallback(){super.disconnectedCallback(),this.__unregisterFormElement()}__unregisterFormElement(){this._parentFormGroup&&this._parentFormGroup.removeFormElement(this)}},Zn=ie(Mh),Vh=i=>class extends Zn(vi(Pt(i))){static get properties(){return{readOnly:{type:Boolean,attribute:"readonly",reflect:!0},label:String,labelSrOnly:{type:Boolean,attribute:"label-sr-only",reflect:!0},helpText:{type:String,attribute:"help-text"},modelValue:{attribute:!1},_ariaLabelledNodes:{attribute:!1},_ariaDescribedNodes:{attribute:!1},_repropagationRole:{attribute:!1},_isRepropagationEndpoint:{attribute:!1}}}get label(){return this.__label??(this._labelNode?.textContent||"")}set label(t){const s=this.label;this.__label=t,this.requestUpdate("label",s)}get helpText(){return this.__helpText??(this._helpTextNode?.textContent||"")}set helpText(t){const s=this.helpText;this.__helpText=t,this.requestUpdate("helpText",s)}get fieldName(){return this.__fieldName||this.label||this.name||""}set fieldName(t){this.__fieldName=t}get slots(){return{...super.slots,label:()=>{const t=document.createElement("label");return t.textContent=this.label,t},"help-text":()=>{const t=document.createElement("div");return t.textContent=this.helpText,t}}}get _inputNode(){return this.__getDirectSlotChild("input")}get _labelNode(){return this.__getDirectSlotChild("label")}get _helpTextNode(){return this.__getDirectSlotChild("help-text")}get _feedbackNode(){return this.__getDirectSlotChild("feedback")}static enabledWarnings=super.enabledWarnings?.filter(t=>t!=="change-in-update")||[];constructor(){super(),this.readOnly=!1,this.labelSrOnly=!1,this._inputId=yi(this.localName),this._ariaLabelledNodes=[],this._ariaDescribedNodes=[],this._repropagationRole="child",this._isRepropagationEndpoint=!1,this.addEventListener("model-value-changed",this.__repropagateChildrenValues),this._onLabelClick=this._onLabelClick.bind(this)}connectedCallback(){super.connectedCallback(),this._enhanceLightDomClasses(),this._enhanceLightDomA11y(),this._triggerInitialModelValueChangedEvent(),this._labelNode&&this._labelNode.addEventListener("click",this._onLabelClick)}disconnectedCallback(){super.disconnectedCallback(),this._labelNode&&this._labelNode.removeEventListener("click",this._onLabelClick)}updated(t){super.updated(t),t.has("disabled")&&this._inputNode?.setAttribute("aria-disabled",`${!!this.disabled}`),t.has("_ariaLabelledNodes")&&this.__reflectAriaAttr("aria-labelledby",this._ariaLabelledNodes,this.__reorderAriaLabelledNodes),t.has("_ariaDescribedNodes")&&this.__reflectAriaAttr("aria-describedby",this._ariaDescribedNodes,this.__reorderAriaDescribedNodes),t.has("label")&&this.__label!==void 0&&this._labelNode&&(this._labelNode.textContent=this.label),t.has("helpText")&&this.__helpText!==void 0&&this._helpTextNode&&(this._helpTextNode.textContent=this.helpText),t.has("name")&&this.dispatchEvent(new CustomEvent("form-element-name-changed",{detail:{oldName:t.get("name"),newName:this.name},bubbles:!0}))}_triggerInitialModelValueChangedEvent(){this._dispatchInitialModelValueChangedEvent()}_enhanceLightDomClasses(){this._inputNode&&this._inputNode.classList.add("form-control")}_enhanceLightDomA11y(){const{_inputNode:t,_labelNode:s,_helpTextNode:n,_feedbackNode:o}=this;t&&(t.id=t.id||this._inputId),s&&(s.setAttribute("for",this._inputId),this.addToAriaLabelledBy(s,{idPrefix:"label"})),n&&this.addToAriaDescribedBy(n,{idPrefix:"help-text"}),o&&(this.addEventListener("focusin",()=>{o.setAttribute("aria-live","polite")}),this.addEventListener("focusout",()=>{o.setAttribute("aria-live","assertive")}),this.addToAriaDescribedBy(o,{idPrefix:"feedback"})),this._enhanceLightDomA11yForAdditionalSlots()}_enhanceLightDomA11yForAdditionalSlots(t=["prefix","suffix","before","after"]){t.forEach(s=>{const n=this.__getDirectSlotChild(s);n&&(n.hasAttribute("data-label")&&this.addToAriaLabelledBy(n,{idPrefix:s}),n.hasAttribute("data-description")&&this.addToAriaDescribedBy(n,{idPrefix:s}))})}__reflectAriaAttr(t,s,n){if(this._inputNode){if(n){const r=s.filter(h=>this.contains(h)),a=s.filter(h=>!this.contains(h)),l=r.map(h=>h.assignedSlot||h),d=[...$a(l)],c=[];d.forEach(h=>{r.forEach(m=>{h.name===m.slot&&c.push(m)})}),s=[...c,...a]}const o=s.map(r=>r.id).join(" ");this._inputNode.setAttribute(t,o)}}render(){return x`
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
      `}_isEmpty(t=this.modelValue){let s=t;if(this.modelValue instanceof bt&&(s=this.modelValue.viewValue),typeof s=="object"&&s!==null&&!(s instanceof Date))return!Object.keys(s).length;const n=typeof s=="number"&&(s===0||Number.isNaN(s));return!s&&!n&&!(typeof s=="boolean"&&s===!1)}static get styles(){return[O`
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
        `]}_getAriaDescriptionElements(){return[this._helpTextNode,this._feedbackNode]}addToAriaLabelledBy(t,{idPrefix:s="",reorder:n=!0}={}){t.id=t.id||`${s}-${this._inputId}`,this._ariaLabelledNodes.includes(t)||(this._ariaLabelledNodes=[...this._ariaLabelledNodes,t],this.__reorderAriaLabelledNodes=!!n)}removeFromAriaLabelledBy(t){this._ariaLabelledNodes.includes(t)&&(this._ariaLabelledNodes.splice(this._ariaLabelledNodes.indexOf(t),1),this._ariaLabelledNodes=[...this._ariaLabelledNodes],this.__reorderAriaLabelledNodes=!1)}addToAriaDescribedBy(t,{idPrefix:s="",reorder:n=!0}={}){t.id=t.id||`${s}-${this._inputId}`,this._ariaDescribedNodes.includes(t)||(this._ariaDescribedNodes=[...this._ariaDescribedNodes,t],this.__reorderAriaDescribedNodes=!!n)}removeFromAriaDescribedBy(t){this._ariaDescribedNodes.includes(t)&&(this._ariaDescribedNodes.splice(this._ariaDescribedNodes.indexOf(t),1),this._ariaDescribedNodes=[...this._ariaDescribedNodes],this.__reorderAriaLabelledNodes=!1)}__getDirectSlotChild(t){return Array.from(this.children).find(s=>s.slot===t)}_dispatchInitialModelValueChangedEvent(){this._repropagationRole!=="child"&&(this.__repropagateChildrenInitialized=!0,this.dispatchEvent(new CustomEvent("model-value-changed",{bubbles:!0,detail:{formPath:[this],initialize:!0,isTriggeredByUser:!1}})))}_onBeforeRepropagateChildrenValues(t){}__repropagateChildrenValues(t){this._onBeforeRepropagateChildrenValues(t);const s=t.detail&&t.detail.element||t.target,n=this._isRepropagationEndpoint||this._repropagationRole==="choice-group";if(s===this)return;t.stopImmediatePropagation();const r=this._repropagationRole!=="child"&&!this.__repropagateChildrenInitialized,a=t.detail&&t.detail.initialize;if(r||a||!this._repropagationCondition(s))return;let l=[];n||(l=t.detail&&t.detail.formPath||[s]);const d=[...l,this];this.dispatchEvent(new CustomEvent("model-value-changed",{bubbles:!0,detail:{formPath:d,isTriggeredByUser:!!t.detail?.isTriggeredByUser}}))}_repropagationCondition(t){return!!t}_onLabelClick(){}},gt=ie(Vh);class Ss extends EventTarget{constructor(e,t){super(),this.__param=e,this.__config=t||{},this.type=t?.type||"error"}static _$isValidator$=!0;static validatorName="";static async=!1;execute(e,t,s){if(!this.constructor.validatorName)throw new Error(`A validator needs to have a name! Please set it via "static get validatorName() { return 'IsCat'; }"`);return!0}set param(e){this.__param=e,this.dispatchEvent(new Event("param-changed"))}get param(){return this.__param}set config(e){this.__config=e,this.dispatchEvent(new Event("config-changed"))}get config(){return this.__config}async _getMessage(e){const t=this.constructor,s={name:t.validatorName,type:this.type,params:this.param,config:this.config,...e};if(this.config.getMessage){if(typeof this.config.getMessage=="function")return this.config.getMessage(s);throw new Error(`You must provide a value for getMessage of type 'function', you provided a value of type: ${typeof this.config.getMessage}`)}return t.getMessage(s)}static async getMessage(e){return`Please configure an error message for "${this.name}" by overriding "static async getMessage()"`}onFormControlConnect(e){}onFormControlDisconnect(e){}abortExecution(){}}function ir(i=[],e=[]){return i.filter(t=>!e.includes(t)).concat(e.filter(t=>!i.includes(t)))}function Ph(i){return i instanceof bt?i.viewValue:i}const zh=i=>class extends gt($h(vi(Pt(wi(i))))){static get scopedElements(){return{...super.scopedElements,"lion-validation-feedback":La}}static get properties(){return{validators:{attribute:!1},hasFeedbackFor:{attribute:!1},shouldShowFeedbackFor:{attribute:!1},showsFeedbackFor:{type:Array,attribute:"shows-feedback-for",reflect:!0,converter:{fromAttribute:e=>e.split(","),toAttribute:e=>e.join(",")}},validationStates:{attribute:!1},isPending:{type:Boolean,attribute:"is-pending",reflect:!0},defaultValidators:{attribute:!1},_visibleMessagesAmount:{attribute:!1},__childModelValueChanged:{attribute:!1}}}static get validationTypes(){return["error"]}get operationMode(){return"enter"}get slots(){return{...super.slots,feedback:()=>{const e=this.createScopedElement("lion-validation-feedback");return e.setAttribute("data-tag-name","lion-validation-feedback"),e}}}get _allValidators(){return[...this.validators,...this.defaultValidators]}constructor(){super(),this.hasFeedbackFor=[],this.showsFeedbackFor=[],this.shouldShowFeedbackFor=[],this.validationStates={},this.isPending=!1,this.validators=[],this.defaultValidators=[],this._visibleMessagesAmount=1,this.__syncValidationResult=[],this.__asyncValidationResult=[],this.__validationResult=[],this.__prevValidationResult=[],this.__prevShownValidationResult=[],this.__childModelValueChanged=!1,this._onValidatorUpdated=this._onValidatorUpdated.bind(this),this._updateFeedbackComponent=this._updateFeedbackComponent.bind(this)}connectedCallback(){super.connectedCallback(),ss().addEventListener("localeChanged",this._updateFeedbackComponent)}disconnectedCallback(){super.disconnectedCallback(),ss().removeEventListener("localeChanged",this._updateFeedbackComponent)}firstUpdated(e){super.firstUpdated(e),this.__isValidateInitialized=!0,this.validate(),this._repropagationRole!=="child"&&this.addEventListener("model-value-changed",()=>{this.__childModelValueChanged=!0})}updateSync(e,t){if(super.updateSync(e,t),e==="validators"?(this.__setupValidators(),this.validate({clearCurrentResult:!0})):e==="modelValue"&&this.validate({clearCurrentResult:!0}),["touched","dirty","prefilled","focused","submitted","hasFeedbackFor","filled"].includes(e)&&this._updateShouldShowFeedbackFor(),e==="showsFeedbackFor"){this._inputNode&&this._inputNode.setAttribute("aria-invalid",`${this._hasFeedbackVisibleFor("error")}`);const s=ir(this.showsFeedbackFor,t);s.length>0&&this.dispatchEvent(new Event("showsFeedbackForChanged",{bubbles:!0})),s.forEach(n=>{this.dispatchEvent(new Event(`showsFeedbackFor${Fh(n)}Changed`,{bubbles:!0}))})}e==="shouldShowFeedbackFor"&&ir(this.shouldShowFeedbackFor,t).length>0&&this.dispatchEvent(new Event("shouldShowFeedbackForChanged",{bubbles:!0}))}async validate({clearCurrentResult:e=!1}={}){if(this.validateComplete=new Promise(t=>{this.__validateCompleteResolve=t}),this.disabled){this.__clearValidationResults(),this.__finishValidationPass(),this._updateFeedbackComponent();return}this.__isValidateInitialized&&(this.__prevValidationResult=this.__validationResult,e&&this.__clearValidationResults(),await this.__executeValidators())}#e(e){let t=e;for(;t;){if(t.constructor.validatorName==="Required")return!0;t=Object.getPrototypeOf(t)}return!1}async __executeValidators(){const e=Ph(this.modelValue),t=this.__isEmpty(e);if(this.__syncValidationResult=[],t){const a=!this._isFormOrFieldset,l=this._allValidators.find(d=>d.constructor?.validatorName==="Required");if(l&&(this.__syncValidationResult=[{validator:l,outcome:!0}]),a){this.__finishValidationPass({syncValidationResult:this.__syncValidationResult});return}}const s=[],n=[],o=[];for(const a of this._allValidators)a?.executeOnResults?s.push(a):this.#e(a)||(a.constructor.async?o.push(a):n.push(a));const r=!!o.length;this.__syncValidationResult=[...this.__syncValidationResult,...this.__executeSyncValidators(n,e)],this.__finishValidationPass({syncValidationResult:this.__syncValidationResult,metaValidators:s}),r?(this.isPending=!0,this.__asyncValidationResult=await this.__executeAsyncValidators(o,e),this.isPending=!1,this.__finishValidationPass({syncValidationResult:this.__syncValidationResult,asyncValidationResult:this.__asyncValidationResult,metaValidators:s}),this.__validateCompleteResolve?.(!0)):this.__validateCompleteResolve?.(!0)}__executeSyncValidators(e,t){return e.map(s=>({validator:s,outcome:s.execute(t,s.param,{node:this})})).filter(s=>!!s.outcome)}async __executeAsyncValidators(e,t){const s=e.map(o=>o.execute(t,o.param,{node:this})),n=await Promise.all(s);return n.map((o,r)=>({validator:e[r],outcome:n[r]})).filter(o=>!!o.outcome)}__executeMetaValidators(e,t){return t.length?this._isEmpty(this.modelValue)?(this.__prevShownValidationResult=[],[]):t.map(s=>({validator:s,outcome:s.executeOnResults({regularValidationResult:e.map(n=>n.validator),prevValidationResult:this.__prevValidationResult.map(n=>n.validator),prevShownValidationResult:this.__prevShownValidationResult.map(n=>n.validator)})})).filter(s=>!!s.outcome):[]}__finishValidationPass({syncValidationResult:e=[],asyncValidationResult:t=[],metaValidators:s=[]}={}){const n=[...e,...t],o=this.__executeMetaValidators(n,s);this.__validationResult=[...o,...n];const a=this.constructor.validationTypes.reduce((l,d)=>({...l,[d]:{}}),{});for(const{validator:l,outcome:d}of this.__validationResult){a[l.type]||(a[l.type]={});const c=l.constructor;a[l.type][c.validatorName]=d}this.validationStates=a,this.hasFeedbackFor=[...new Set(this.__validationResult.map(({validator:l})=>l.type))],this.dispatchEvent(new Event("validate-performed",{bubbles:!0}))}__clearValidationResults(){this.__syncValidationResult=[],this.__asyncValidationResult=[]}_onValidatorUpdated(e){(e.type==="param-changed"||e.type==="config-changed")&&this.validate()}__setupValidators(){const e=["param-changed","config-changed"];for(const t of this.__prevValidators||[]){for(const s of e)t.removeEventListener?.(s,this._onValidatorUpdated);t.onFormControlDisconnect(this)}for(const t of this._allValidators){if(t.constructor._$isValidator$===void 0){const a=`Validators array only accepts class instances of Validator. Type "${Array.isArray(t)?"array":typeof t}" found. This may be caused by having multiple installations of "@lion/ui/form-core.js".`;throw console.error(a,this),new Error(a)}const n=this.constructor,o=t.constructor;if(n.validationTypes.indexOf(t.type)===-1){const r=`This component does not support the validator type "${t.type}" used in "${o.validatorName}". You may change your validators type or add it to the components "static get validationTypes() {}".`;throw console.error(r,this),new Error(r)}for(const r of e)t.addEventListener?.(r,a=>{this._onValidatorUpdated(a,{validator:t})});t.onFormControlConnect(this)}this.__prevValidators=this._allValidators}__isEmpty(e){return typeof this._isEmpty=="function"?this._isEmpty(e):this.modelValue===null||typeof this.modelValue>"u"||this.modelValue===""}async __getFeedbackMessages(e){let t=await this.fieldName;return Promise.all(e.map(async({validator:s,outcome:n})=>(s.config.fieldName&&(t=await s.config.fieldName),{message:await s._getMessage({modelValue:this.modelValue,formControl:this,fieldName:t,outcome:n}),type:s.type,validator:s,visibilityDuration:s.config?.visibilityDuration||3e3})))}_updateFeedbackComponent(){window.clearTimeout(this.removeMessage);const{_feedbackNode:e}=this;e&&(this.__feedbackQueue||(this.__feedbackQueue=new Oh),this.showsFeedbackFor.length>0?this.__feedbackQueue.add(async()=>{const t=this._prioritizeAndFilterFeedback({validationResult:this.__validationResult.map(n=>n.validator)});this.__prioritizedResult=t.map(n=>this.__validationResult.find(r=>n===r.validator)).filter(Boolean),this.__prioritizedResult.length>0&&(this.__prevShownValidationResult=this.__prioritizedResult);const s=await this.__getFeedbackMessages(this.__prioritizedResult);e.feedbackData=s||[],s?.[0]&&s[0].type==="success"&&s[0].visibilityDuration!==1/0&&(this.removeMessage=window.setTimeout(()=>{e.removeAttribute("type"),e.feedbackData=[]},s[0].visibilityDuration))}):this.__feedbackQueue.add(async()=>{e.feedbackData=[]}),this.feedbackComplete=this.__feedbackQueue.complete)}_showFeedbackConditionFor(e,t){return!0}get _feedbackConditionMeta(){return{modelValue:this.modelValue,el:this}}feedbackCondition(e,t=this._feedbackConditionMeta,s=this._showFeedbackConditionFor.bind(this)){return s(e,t)}_hasFeedbackVisibleFor(e){return this.hasFeedbackFor?.includes(e)&&this.shouldShowFeedbackFor?.includes(e)}updated(e){if(super.updated(e),e.has("shouldShowFeedbackFor")||e.has("hasFeedbackFor")){const t=this.constructor;this.showsFeedbackFor=t.validationTypes.map(s=>this._hasFeedbackVisibleFor(s)?s:void 0).filter(Boolean),this._updateFeedbackComponent()}if(e.has("__childModelValueChanged")&&this.__childModelValueChanged&&(this.validate({clearCurrentResult:!0}),this.__childModelValueChanged=!1),e.has("validationStates")){const t=e.get("validationStates");t&&Object.entries(this.validationStates).forEach(([s,n])=>{t[s]&&JSON.stringify(n)!==JSON.stringify(t[s])&&this.dispatchEvent(new CustomEvent(`${s}StateChanged`,{detail:n}))})}}_updateShouldShowFeedbackFor(){const t=this.constructor.validationTypes.map(s=>this.feedbackCondition(s,this._feedbackConditionMeta,this._showFeedbackConditionFor.bind(this))?s:void 0).filter(Boolean);JSON.stringify(this.shouldShowFeedbackFor)!==JSON.stringify(t)&&(this.shouldShowFeedbackFor=t)}_prioritizeAndFilterFeedback({validationResult:e}){const s=this.constructor.validationTypes;return e.filter(o=>this.feedbackCondition(o.type,this._feedbackConditionMeta,this._showFeedbackConditionFor.bind(this))).sort((o,r)=>s.indexOf(o.type)-s.indexOf(r.type)).slice(0,this._visibleMessagesAmount)}},xi=ie(zh),Bh=i=>class extends xi(gt(i)){static get properties(){return{formattedValue:{attribute:!1},serializedValue:{attribute:!1},formatOptions:{attribute:!1}}}#e={didFormatterOutputSyncToView:!1,didFormatterRun:!1};requestUpdate(t,s,n){super.requestUpdate(t,s,n),t==="modelValue"&&this.modelValue!==s&&this._onModelValueChanged({modelValue:this.modelValue},{modelValue:s}),t==="serializedValue"&&this.serializedValue!==s&&this._calculateValues({source:"serialized"}),t==="formattedValue"&&this.formattedValue!==s&&this._calculateValues({source:"formatted"})}get value(){return this._inputNode?.value||this.__value||""}set value(t){this._inputNode?(this._inputNode.value=t,this.__value=void 0):this.__value=t}preprocessor(t,s){}parser(t,s){return t}formatter(t,s){return t}serializer(t){return t!==void 0?t:""}deserializer(t){return t===void 0?"":t}_calculateValues({source:t}={source:null}){this.__preventRecursiveTrigger||(this.__preventRecursiveTrigger=!0,t!=="model"&&(t==="serialized"?this.modelValue=this.deserializer(this.serializedValue):t==="formatted"&&(this.modelValue=this._callParser())),t!=="formatted"&&(this.formattedValue=this._callFormatter()),t!=="serialized"&&(this.serializedValue=this.serializer(this.modelValue)),this._reflectBackFormattedValueToUser(),this.__preventRecursiveTrigger=!1,this.__prevViewValue=this.value)}_callParser(t=this.formattedValue){if(t==="")return"";if(typeof t!="string")return;const s=this.parser(t,{...this.formatOptions,mode:this.#t(),viewValueStates:this.#i()});return s!==void 0?s:new bt(t)}_callFormatter(){return this.#e.didFormatterRun=!1,this._isHandlingUserInput&&this.hasFeedbackFor?.includes("error")&&this._inputNode?this.value:this.modelValue instanceof bt?this.modelValue.viewValue:(this.#e.didFormatterRun=!0,this.formatter(this.modelValue,{...this.formatOptions,mode:this.#t(),viewValueStates:this.#i()}))}_onModelValueChanged(...t){this._calculateValues({source:"model"}),this._dispatchModelValueChangedEvent(...t)}_dispatchModelValueChangedEvent(...t){this.dispatchEvent(new CustomEvent("model-value-changed",{bubbles:!0,detail:{formPath:[this],isTriggeredByUser:!!this._isHandlingUserInput}}))}_syncValueUpwards(){this.__isHandlingComposition||this.__handlePreprocessor();const t=this.formattedValue;this.modelValue=this._callParser(this.value),t===this.formattedValue&&this.__prevViewValue!==this.value&&this._calculateValues()}__handlePreprocessor(){let t=this.value.length;this._inputNode&&"selectionStart"in this._inputNode&&this._inputNode?.type!=="range"&&(t=this._inputNode.selectionStart);const s=this.preprocessor(this.value,{...this.formatOptions,currentCaretIndex:t,prevViewValue:this.__prevViewValue});if(s!==void 0){if(typeof s=="string")this.value=s;else if(typeof s=="object"){const{viewValue:n,caretIndex:o}=s;this.value=n,o&&this._inputNode&&"selectionStart"in this._inputNode&&(this._inputNode.selectionStart=o,this._inputNode.selectionEnd=o)}}}_reflectBackFormattedValueToUser(){this._reflectBackOn()&&(this.value=typeof this.formattedValue<"u"?this.formattedValue:"",this.#e.didFormatterOutputSyncToView=!!this.formattedValue&&this.#e.didFormatterRun)}_reflectBackOn(){return!this._isHandlingUserInput}_proxyInputEvent(){this.dispatchEvent(new Event("user-input-changed",{bubbles:!0}))}_onUserInputChanged(){this._isHandlingUserInput=!0,this._syncValueUpwards(),this._isHandlingUserInput=!1}__onCompositionEvent({type:t}){t==="compositionstart"?this.__isHandlingComposition=!0:t==="compositionend"&&(this.__isHandlingComposition=!1,this._syncValueUpwards())}constructor(){super(),this.formatOn="change",this.formatOptions={mode:"auto"},this.formattedValue=void 0,this.serializedValue=void 0,this._isPasting=!1,this._isHandlingUserInput=!1,this.__prevViewValue="",this.__onCompositionEvent=this.__onCompositionEvent.bind(this),this.addEventListener("user-input-changed",this._onUserInputChanged),this.addEventListener("paste",this.__onPaste),this._reflectBackFormattedValueToUser=this._reflectBackFormattedValueToUser.bind(this),this._reflectBackFormattedValueDebounced=()=>{setTimeout(this._reflectBackFormattedValueToUser)}}__onPaste(){this._isPasting=!0,setTimeout(()=>{this._isPasting=!1})}connectedCallback(){super.connectedCallback(),typeof this.modelValue>"u"&&this._syncValueUpwards(),this.__prevViewValue=this.value,this._reflectBackFormattedValueToUser(),this._inputNode&&(this._inputNode.addEventListener(this.formatOn,this._reflectBackFormattedValueDebounced),this._inputNode.addEventListener("input",this._proxyInputEvent),this._inputNode.addEventListener("compositionstart",this.__onCompositionEvent),this._inputNode.addEventListener("compositionend",this.__onCompositionEvent))}disconnectedCallback(){super.disconnectedCallback(),this._inputNode&&(this._inputNode.removeEventListener("input",this._proxyInputEvent),this._inputNode.removeEventListener(this.formatOn,this._reflectBackFormattedValueDebounced),this._inputNode.removeEventListener("compositionstart",this.__onCompositionEvent),this._inputNode.removeEventListener("compositionend",this.__onCompositionEvent))}#t(){return this._isPasting?"pasted":this._isHandlingUserInput&&this.__prevViewValue?"user-edited":"auto"}#i(){const t=[];return this.#e.didFormatterOutputSyncToView&&t.push("formatted"),t}},Jn=ie(Bh),Uh=i=>class extends gt(i){static get properties(){return{touched:{type:Boolean,reflect:!0},dirty:{type:Boolean,reflect:!0},filled:{type:Boolean,reflect:!0},prefilled:{attribute:!1},submitted:{attribute:!1}}}requestUpdate(t,s,n){super.requestUpdate(t,s,n),t==="touched"&&this.touched!==s&&this._onTouchedChanged(),t==="modelValue"&&(this.filled=!this._isEmpty()),t==="dirty"&&this.dirty!==s&&this._onDirtyChanged()}constructor(){super(),this.touched=!1,this.dirty=!1,this.prefilled=!1,this.filled=!1,this._leaveEvent="blur",this._valueChangedEvent="model-value-changed",this._iStateOnLeave=this._iStateOnLeave.bind(this),this._iStateOnValueChange=this._iStateOnValueChange.bind(this)}connectedCallback(){super.connectedCallback(),this.addEventListener(this._leaveEvent,this._iStateOnLeave),this.addEventListener(this._valueChangedEvent,this._iStateOnValueChange),this.initInteractionState()}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener(this._leaveEvent,this._iStateOnLeave),this.removeEventListener(this._valueChangedEvent,this._iStateOnValueChange)}initInteractionState(){this.dirty=!1,this.prefilled=!this._isEmpty()}_iStateOnLeave(){this.touched=!0,this.prefilled=!this._isEmpty()}_iStateOnValueChange(){this.dirty=!0}resetInteractionState(){this.touched=!1,this.submitted=!1,this.dirty=!1,this.prefilled=!this._isEmpty()}_onTouchedChanged(){this.dispatchEvent(new Event("touched-changed",{bubbles:!0,composed:!0}))}_onDirtyChanged(){this.dispatchEvent(new Event("dirty-changed",{bubbles:!0,composed:!0}))}_showFeedbackConditionFor(t,s){return s.touched&&s.dirty||s.prefilled||s.submitted}get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,submitted:this.submitted,touched:this.touched,dirty:this.dirty,filled:this.filled,prefilled:this.prefilled}}},Qn=ie(Uh);class Ei extends gt(Qn(Xn(Jn(xi(Pt(H)))))){firstUpdated(e){super.firstUpdated(e),this._initialModelValue=this.modelValue}connectedCallback(){super.connectedCallback(),this._onChange=this._onChange.bind(this),this._inputNode.addEventListener("change",this._onChange),this.classList.add("form-field")}disconnectedCallback(){super.disconnectedCallback(),this._inputNode?.removeEventListener("change",this._onChange)}resetInteractionState(){super.resetInteractionState(),this.submitted=!1}reset(){this.modelValue=this._initialModelValue,this.resetInteractionState()}clear(){this.modelValue=""}_onChange(e){this.dispatchEvent(new Event("user-input-changed",{bubbles:!0}))}get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,focused:this.focused}}get _focusableNode(){return this._inputNode}}class wn extends Array{_keys(){return Object.keys(this).filter(e=>Number.isNaN(Number(e)))}}const Hh=i=>class extends Zn(i){static get properties(){return{_isFormOrFieldset:{type:Boolean}}}constructor(){super(),this.formElements=new wn,this._isFormOrFieldset=!1,this._onRequestToAddFormElement=this._onRequestToAddFormElement.bind(this),this._onRequestToChangeFormElementName=this._onRequestToChangeFormElementName.bind(this),this.addEventListener("form-element-register",this._onRequestToAddFormElement),this.addEventListener("form-element-name-changed",this._onRequestToChangeFormElementName),this.initComplete=new Promise((e,t)=>{this.__resolveInitComplete=e,this.__rejectInitComplete=t}),this.registrationComplete=new Promise((e,t)=>{this.__resolveRegistrationComplete=e,this.__rejectRegistrationComplete=t}),this.registrationComplete.done=!1,this.registrationComplete.then(()=>{this.registrationComplete.done=!0,this.__resolveInitComplete(void 0)},()=>{throw this.registrationComplete.done=!0,this.__rejectInitComplete(void 0),new Error("Registration could not finish. Please use await el.registrationComplete;")})}connectedCallback(){super.connectedCallback(),this._completeRegistration()}_completeRegistration(){Promise.resolve().then(()=>this.__resolveRegistrationComplete(void 0))}disconnectedCallback(){super.disconnectedCallback(),this.registrationComplete.done===!1&&Promise.resolve().then(()=>{Promise.resolve().then(()=>{this.__rejectRegistrationComplete()})})}isRegisteredFormElement(e){return this.formElements.some(t=>t===e)}addFormElement(e,t){if(e._parentFormGroup=this,t>=0?this.formElements.splice(t,0,e):this.formElements.push(e),this._isFormOrFieldset){const{name:s}=e;if(s===this.name)throw console.info("Error Node:",e),new TypeError(`You can not have the same name "${s}" as your parent`);if(s.substr(-2)==="[]")Array.isArray(this.formElements[s])||(this.formElements[s]=new wn),t>0?this.formElements[s].splice(t,0,e):this.formElements[s].push(e);else if(!this.formElements[s])this.formElements[s]=e;else throw console.info("Error Node:",e),new TypeError(`Name "${s}" is already registered - if you want an array add [] to the end`)}}removeFormElement(e){const t=this.formElements.indexOf(e);if(t>-1&&this.formElements.splice(t,1),this._isFormOrFieldset){const{name:s}=e;if(s.substr(-2)==="[]"&&this.formElements[s]){const n=this.formElements[s].indexOf(e);n>-1&&this.formElements[s].splice(n,1)}else this.formElements[s]&&delete this.formElements[s]}}_onRequestToAddFormElement(e){const t=e.detail.element;if(t===this||this.isRegisteredFormElement(t))return;e.stopPropagation();let s=-1;if(this.formElements&&Array.isArray(this.formElements)){for(const[n,o]of this.formElements.entries())if(!(o.compareDocumentPosition(t)&Node.DOCUMENT_POSITION_FOLLOWING)){s=n;break}}this.addFormElement(t,s)}_onRequestToChangeFormElementName(e){const t=this.formElements[e.detail.oldName];t&&(this.formElements[e.detail.newName]=t,delete this.formElements[e.detail.oldName])}_onRequestToRemoveFormElement(e){const t=e.detail.element;t!==this&&this.isRegisteredFormElement(t)&&(e.stopPropagation(),this.removeFormElement(t))}},eo=ie(Hh),qh=i=>class extends i{constructor(){super(),this.registrationTarget=void 0,this.__redispatchEventForFormRegistrarPortalMixin=this.__redispatchEventForFormRegistrarPortalMixin.bind(this),this.addEventListener("form-element-register",this.__redispatchEventForFormRegistrarPortalMixin)}__redispatchEventForFormRegistrarPortalMixin(e){if(e.stopPropagation(),!this.registrationTarget)throw new Error("A FormRegistrarPortal element requires a .registrationTarget");this.registrationTarget.dispatchEvent(new CustomEvent("form-element-register",{detail:{element:e.detail.element},bubbles:!0}))}},jh=ie(qh),Wh=i=>class extends Jn(Xn(gt(i))){static get properties(){return{autocomplete:{type:String,reflect:!0}}}constructor(){super(),this.autocomplete=void 0}get _inputNode(){return super._inputNode}get selectionStart(){const t=this._inputNode;return t&&t.selectionStart?t.selectionStart:0}set selectionStart(t){const s=this._inputNode;s&&s.selectionStart&&(s.selectionStart=t)}get selectionEnd(){const t=this._inputNode;return t&&t.selectionEnd?t.selectionEnd:0}set selectionEnd(t){const s=this._inputNode;s&&s.selectionEnd&&(s.selectionEnd=t)}get value(){return this._inputNode&&this._inputNode.value||this.__value||""}set value(t){this._inputNode?(this._inputNode.value!==t&&this._setValueAndPreserveCaret(t),this.__value=void 0):this.__value=t}_setValueAndPreserveCaret(t){if(this.focused)try{if(!(this._inputNode instanceof HTMLSelectElement)){const s=this._inputNode.selectionStart;this._inputNode.value=t,this._inputNode.selectionStart=s,this._inputNode.selectionEnd=s}}catch{this._inputNode.value=t}else this._inputNode.value=t}_reflectBackFormattedValueToUser(){if(super._reflectBackFormattedValueToUser(),this._reflectBackOn()&&this.focused)try{this._inputNode.selectionStart=this._inputNode.value.length}catch{}}get _focusableNode(){return this._inputNode}},Ra=ie(Wh),Kh=i=>class extends eo(xi(Qn(i))){static get properties(){return{multipleChoice:{type:Boolean,attribute:"multiple-choice"}}}get modelValue(){const t=this._getCheckedElements();return this.multipleChoice?t.map(s=>s.choiceValue):t[0]?t[0].choiceValue:""}set modelValue(t){const s=(n,o)=>typeof n.choiceValue=="object"?JSON.stringify(n.choiceValue)===JSON.stringify(t):n.choiceValue===o;this.__isInitialModelValue?this.registrationComplete.then(()=>{this.__isInitialModelValue=!1,this._setCheckedElements(t,s),this.requestUpdate("modelValue",this._oldModelValue)}):(this._setCheckedElements(t,s),this.requestUpdate("modelValue",this._oldModelValue)),this._oldModelValue=this.modelValue}get serializedValue(){const t=this._getCheckedElements();return this.multipleChoice?t.map(s=>s.serializedValue.value):t[0]?t[0].serializedValue.value:""}set serializedValue(t){const s=(n,o)=>n.serializedValue.value===o;this.__isInitialSerializedValue?this.registrationComplete.then(()=>{this.__isInitialSerializedValue=!1,this._setCheckedElements(t,s),this.requestUpdate("serializedValue")}):(this._setCheckedElements(t,s),this.requestUpdate("serializedValue"))}get formattedValue(){const t=this._getCheckedElements();return this.multipleChoice?t.map(s=>s.formattedValue):t[0]?t[0].formattedValue:""}set formattedValue(t){const s=(n,o)=>n.formattedValue===o;this.__isInitialFormattedValue?this.registrationComplete.then(()=>{this.__isInitialFormattedValue=!1,this._setCheckedElements(t,s)}):this._setCheckedElements(t,s)}get operationMode(){return this._repropagationRole==="choice-group"?"select":"enter"}constructor(){super(),this.multipleChoice=!1,this._repropagationRole="choice-group",this.__isInitialModelValue=!0,this.__isInitialSerializedValue=!0,this.__isInitialFormattedValue=!0}connectedCallback(){super.connectedCallback(),this.registrationComplete.then(()=>{this.__isInitialModelValue=!1,this.__isInitialSerializedValue=!1,this.__isInitialFormattedValue=!1})}_completeRegistration(){Promise.resolve().then(()=>super._completeRegistration())}updated(t){super.updated(t),t.has("name")&&this.name!==t.get("name")&&this.formElements.forEach(s=>{s.name=this.name})}addFormElement(t,s){this._throwWhenInvalidChildModelValue(t),t.name=this.name,super.addFormElement(t,s)}clear(){this.multipleChoice?this.modelValue=[]:this.modelValue=""}_triggerInitialModelValueChangedEvent(){this.registrationComplete.then(()=>{this._dispatchInitialModelValueChangedEvent()})}_getFromAllFormElementsFilter(t,s){return!0}_getFromAllFormElements(t,s){const n=s||this._getFromAllFormElementsFilter;return t==="modelValue"||t==="serializedValue"||t==="formattedValue"?this[t]:this.formElements.filter(o=>n(o,t)).map(o=>o.property)}_throwWhenInvalidChildModelValue(t){if(typeof t.modelValue.checked!="boolean"||!Object.prototype.hasOwnProperty.call(t.modelValue,"value"))throw new Error(`The ${this.tagName.toLowerCase()} name="${this.name}" does not allow to register ${t.tagName.toLowerCase()} with .modelValue="${t.modelValue}" - The modelValue should represent an Object { value: "foo", checked: false }`)}_isEmpty(){return this.multipleChoice?this.modelValue.length===0:typeof this.modelValue=="string"&&this.modelValue===""||this.modelValue===void 0||this.modelValue===null}_checkSingleChoiceElements(t){const{target:s}=t;if(s.checked===!1)return;const n=s.name;this.formElements.filter(o=>o.name===n).forEach(o=>{o!==s&&(o.checked=!1)})}_getCheckedElements(){return this.formElements.filter(t=>t.checked&&!t.disabled)}_setCheckedElements(t,s){if(t==null){this.formElements.forEach(n=>n.checked=!1);return}for(let n=0;n<this.formElements.length;n+=1)if(this.multipleChoice){let o=t.includes(this.formElements[n].modelValue.value);typeof this.formElements[n].modelValue.value=="object"&&(o=t.map(r=>JSON.stringify(r)).includes(JSON.stringify(this.formElements[n].modelValue.value))),this.formElements[n].checked=o}else s(this.formElements[n],t)?this.formElements[n].checked=!0:this.formElements[n].checked=!1}__setChoiceGroupTouched(){const t=this.modelValue;t!=null&&t!==this.__previousCheckedValue&&(this.touched=!0,this.__previousCheckedValue=t)}_onBeforeRepropagateChildrenValues(t){const s=t.detail&&t.detail.element||t.target;this.multipleChoice||!s.checked||(this.formElements.forEach(n=>{s.choiceValue!==n.choiceValue&&(n.checked=!1)}),this.__setChoiceGroupTouched(),this.requestUpdate("modelValue",this._oldModelValue),this._oldModelValue=this.modelValue)}_repropagationCondition(t){return!(this._repropagationRole==="choice-group"&&!this.multipleChoice&&!t.checked)}},to=ie(Kh),Gh=(i,e={})=>i.value!==e.value||i.checked!==e.checked,Yh=i=>class extends Jn(i){static get properties(){return{checked:{type:Boolean,reflect:!0},disabled:{type:Boolean,reflect:!0},modelValue:{type:Object,hasChanged:Gh},choiceValue:{type:Object}}}get choiceValue(){return this.modelValue.value}set choiceValue(t){this.requestUpdate("choiceValue",this.choiceValue),this.modelValue.value!==t&&(this.modelValue={value:t,checked:this.modelValue.checked})}requestUpdate(t,s,n){super.requestUpdate(t,s,n),t==="modelValue"?this.modelValue.checked!==this.checked&&this.__syncModelCheckedToChecked(this.modelValue.checked):t==="checked"&&this.modelValue.checked!==this.checked&&this.__syncCheckedToModel(this.checked)}firstUpdated(t){super.firstUpdated(t),t.has("checked")&&this.__syncCheckedToInputElement()}updated(t){super.updated(t),t.has("modelValue")&&this.__syncCheckedToInputElement(),t.has("name")&&this._parentFormGroup&&this._parentFormGroup.name!==this.name&&this._syncNameToParentFormGroup()}constructor(){super(),this.modelValue={value:"",checked:!1},this.disabled=!1,this._preventDuplicateLabelClick=this._preventDuplicateLabelClick.bind(this),this._toggleChecked=this._toggleChecked.bind(this)}static get styles(){return[...super.styles||[],O`
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
      `}_choiceGraphicTemplate(){return B}_afterTemplate(){return B}connectedCallback(){super.connectedCallback(),this._labelNode&&this._labelNode.addEventListener("click",this._preventDuplicateLabelClick),this.addEventListener("user-input-changed",this._toggleChecked)}disconnectedCallback(){super.disconnectedCallback(),this._labelNode&&this._labelNode.removeEventListener("click",this._preventDuplicateLabelClick),this.removeEventListener("user-input-changed",this._toggleChecked)}_preventDuplicateLabelClick(t){const s=n=>{n.stopImmediatePropagation(),this._inputNode.removeEventListener("click",s)};this._inputNode.addEventListener("click",s)}_toggleChecked(t){this.disabled||(this._isHandlingUserInput=!0,this.checked=!this.checked,this._isHandlingUserInput=!1)}_syncNameToParentFormGroup(){this._parentFormGroup.tagName.includes(this.tagName)&&(this.name=this._parentFormGroup?.name||"")}__syncModelCheckedToChecked(t){this.checked=t}__syncCheckedToModel(t){this.modelValue={value:this.choiceValue,checked:t}}__syncCheckedToInputElement(){this._inputNode&&(this._inputNode.checked=this.checked)}_proxyInputEvent(){}_onModelValueChanged({modelValue:t},s){let n;s&&s.modelValue&&(n=s.modelValue),this.constructor.elementProperties.get("modelValue").hasChanged(t,n)&&super._onModelValueChanged({modelValue:t})}parser(){return this.modelValue}formatter(t){return t&&t.value!==void 0?t.value:t}clear(){this.checked=!1}_isEmpty(){return!this.checked}_syncValueUpwards(){}},io=ie(Yh);class Xh extends Ss{static get validatorName(){return"FormElementsHaveNoError"}execute(e,t,s){return s?.node._anyFormElementHasFeedbackFor("error")}static async getMessage(){return""}}const Zh=i=>class extends eo(gt(xi(vi(Pt(i))))){static get properties(){return{submitted:{type:Boolean,reflect:!0},focused:{type:Boolean,reflect:!0},dirty:{type:Boolean,reflect:!0},touched:{type:Boolean,reflect:!0},prefilled:{type:Boolean,reflect:!0}}}get _inputNode(){return this}get modelValue(){return this._getFromAllFormElements("modelValue")}set modelValue(t){this.__isInitialModelValue?(this.__isInitialModelValue=!1,this.registrationComplete.then(()=>{this._setValueMapForAllFormElements("modelValue",t)})):this._setValueMapForAllFormElements("modelValue",t)}get serializedValue(){return this._getFromAllFormElements("serializedValue")}set serializedValue(t){this.__isInitialSerializedValue?(this.__isInitialSerializedValue=!1,this.registrationComplete.then(()=>{this._setValueMapForAllFormElements("serializedValue",t)})):this._setValueMapForAllFormElements("serializedValue",t)}get formattedValue(){return this._getFromAllFormElements("formattedValue")}set formattedValue(t){this._setValueMapForAllFormElements("formattedValue",t)}get prefilled(){return this._everyFormElementHas("prefilled")}constructor(){super(),this.value="",this.disabled=!1,this.submitted=!1,this.dirty=!1,this.touched=!1,this.focused=!1,this.__addedSubValidators=!1,this.__isInitialModelValue=!0,this.__isInitialSerializedValue=!0,this._checkForOutsideClick=this._checkForOutsideClick.bind(this),this.addEventListener("focusin",this._syncFocused),this.addEventListener("focusout",this._onFocusOut),this.addEventListener("dirty-changed",this._syncDirty),this.addEventListener("validate-performed",this.__onChildValidatePerformed),this.defaultValidators=[new Xh],this.__descriptionElementsInParentChain=new Set,this.__pendingValues={modelValue:{},serializedValue:{}}}connectedCallback(){super.connectedCallback(),this.setAttribute("role","group"),this.initComplete.then(()=>{this.__isInitialModelValue=!1,this.__isInitialSerializedValue=!1,this.__initInteractionStates()})}disconnectedCallback(){super.disconnectedCallback(),this.__hasActiveOutsideClickHandling&&(document.removeEventListener("click",this._checkForOutsideClick),this.__hasActiveOutsideClickHandling=!1),this.__descriptionElementsInParentChain.clear()}__initInteractionStates(){this.formElements.forEach(t=>{typeof t.initInteractionState=="function"&&t.initInteractionState()})}_triggerInitialModelValueChangedEvent(){this.registrationComplete.then(()=>{this._dispatchInitialModelValueChangedEvent()})}updated(t){super.updated(t),t.has("disabled")&&(this.disabled?this.__requestChildrenToBeDisabled():this.__retractRequestChildrenToBeDisabled()),t.has("focused")&&this.focused===!0&&this.__setupOutsideClickHandling()}__setupOutsideClickHandling(){this.__hasActiveOutsideClickHandling||(document.addEventListener("click",this._checkForOutsideClick),this.__hasActiveOutsideClickHandling=!0)}_checkForOutsideClick(t){!this.contains(t.target)&&(this.touched=!0)}__requestChildrenToBeDisabled(){this.formElements.forEach(t=>{t.makeRequestToBeDisabled&&t.makeRequestToBeDisabled()})}__retractRequestChildrenToBeDisabled(){this.formElements.forEach(t=>{t.retractRequestToBeDisabled&&t.retractRequestToBeDisabled()})}_inputGroupTemplate(){return x`
        <div class="input-group">
          <slot></slot>
        </div>
      `}submitGroup(){this.submitted=!0,this.formElements.forEach(t=>{typeof t.submitGroup=="function"?t.submitGroup():t.submitted=!0})}resetGroup(){this.formElements.forEach(t=>{typeof t.resetGroup=="function"?t.resetGroup():typeof t.reset=="function"&&t.reset()}),this.resetInteractionState()}clearGroup(){this.formElements.forEach(t=>{typeof t.clearGroup=="function"?t.clearGroup():typeof t.clear=="function"&&t.clear()}),this.resetInteractionState()}resetInteractionState(){this.submitted=!1,this.touched=!1,this.dirty=!1,this.formElements.forEach(t=>{typeof t.resetInteractionState=="function"&&t.resetInteractionState()})}_getFromAllFormElementsFilter(t,s){return!t.disabled}_getFromAllFormElements(t,s){const n={},o=s||this._getFromAllFormElementsFilter;return this.formElements._keys().forEach(r=>{const a=this.formElements[r];a instanceof wn?n[r]=a.filter(l=>o(l,t)).map(l=>l[t]):o(a,t)&&(typeof a._getFromAllFormElements=="function"?n[r]=a._getFromAllFormElements(t):n[r]=a[t])}),n}_setValueForAllFormElements(t,s){this.formElements.forEach(n=>{n[t]=s})}_setValueMapForAllFormElements(t,s){s&&typeof s=="object"&&Object.keys(s).forEach(n=>{Array.isArray(this.formElements[n])&&this.formElements[n].forEach((o,r)=>{o[t]=s[n][r]}),this.formElements[n]?this.formElements[n][t]=s[n]:this.__pendingValues[t][n]=s[n]})}_anyFormElementHas(t){return Object.keys(this.formElements).some(s=>Array.isArray(this.formElements[s])?this.formElements[s].some(n=>!!n[t]):!!this.formElements[s][t])}_anyFormElementHasFeedbackFor(t){return Object.keys(this.formElements).some(s=>Array.isArray(this.formElements[s])?this.formElements[s].some(n=>!!(n.hasFeedbackFor&&n.hasFeedbackFor.includes(t))):!!(this.formElements[s].hasFeedbackFor&&this.formElements[s].hasFeedbackFor.includes(t)))}_everyFormElementHas(t){return Object.keys(this.formElements).every(s=>Array.isArray(this.formElements[s])?this.formElements[s].every(n=>!!n[t]):!!this.formElements[s][t])}__onChildValidatePerformed(t){t&&this.isRegisteredFormElement(t.target)&&this.validate()}_syncFocused(){this.focused=this._anyFormElementHas("focused")}_onFocusOut(t){const s=this.formElements[this.formElements.length-1];t.target===s&&(this.touched=!0),this.focused=!1}_syncDirty(){this.dirty=this._anyFormElementHas("dirty")}__storeAllDescriptionElementsInParentChain(){let s=this;for(;s;){const n=s._getAriaDescriptionElements();$a(n,{reverse:!0}).forEach(r=>{r.getAttribute("slot")==="feedback"&&this.__descriptionElementsInParentChain.add(r)}),s=s._parentFormGroup}}__linkParentMessages(t){this.__descriptionElementsInParentChain.forEach(s=>{typeof t.addToAriaDescribedBy=="function"&&t.addToAriaDescribedBy(s,{reorder:!1})})}__unlinkParentMessages(t){this.__descriptionElementsInParentChain.forEach(s=>{typeof t.removeFromAriaDescribedBy=="function"&&t.removeFromAriaDescribedBy(s)})}addFormElement(t,s){if(super.addFormElement(t,s),this.disabled&&t.makeRequestToBeDisabled(),this.__descriptionElementsInParentChain.size||this.__storeAllDescriptionElementsInParentChain(),this.__linkParentMessages(t),this.validate({clearCurrentResult:!0}),!t.modelValue){const n=this.__pendingValues;n.modelValue&&n.modelValue[t.name]?t.modelValue=n.modelValue[t.name]:n.serializedValue&&n.serializedValue[t.name]&&(t.serializedValue=n.serializedValue[t.name])}}get _initialModelValue(){return this._getFromAllFormElements("_initialModelValue")}removeFormElement(t){super.removeFormElement(t),this.validate({clearCurrentResult:!0}),typeof t.removeFromAriaLabelledBy=="function"&&this._labelNode&&t.removeFromAriaLabelledBy(this._labelNode,{reorder:!1}),this.__unlinkParentMessages(t)}_isEmpty(){return this.formElements.every(t=>t._isEmpty?.())}},Jh=ie(Zh);class so extends Ra(Ei){static get properties(){return{readOnly:{type:Boolean,attribute:"readonly",reflect:!0},type:{type:String,reflect:!0},placeholder:{type:String,reflect:!0}}}get slots(){return{...super.slots,input:()=>{const e=document.createElement("input"),t=this.getAttribute("value");return t&&e.setAttribute("value",t),e}}}get _inputNode(){return super._inputNode}constructor(){super(),this.readOnly=!1,this.type="text",this.placeholder=""}requestUpdate(e,t,s){super.requestUpdate(e,t,s),e==="readOnly"&&this.__delegateReadOnly()}firstUpdated(e){super.firstUpdated(e),this.__delegateReadOnly()}updated(e){super.updated(e),e.has("type")&&(this._inputNode.type=this.type),e.has("placeholder")&&(this._inputNode.placeholder=this.placeholder),e.has("disabled")&&(this._inputNode.disabled=this.disabled,this.validate()),e.has("name")&&(this._inputNode.name=this.name),e.has("autocomplete")&&(this._inputNode.autocomplete=this.autocomplete)}__delegateReadOnly(){this._inputNode&&(this._inputNode.readOnly=this.readOnly)}}var sr=class extends so{static get styles(){return[...super.styles,Es,ah]}connectedCallback(){if(super.connectedCallback(),this._inputNode&&this.size){let e=parseInt(this.size,10);e>0&&(this._inputNode.size=e)}}};L([y({type:Number,reflect:!0})],sr.prototype,"size",void 0),customElements.get("craft-input")||customElements.define("craft-input",sr);const ve=i=>i??B;class ji extends Ss{static validatorName="IsAcceptedFile";static checkFileSize(e,t){return e<=t}static getExtension(e){return e?.slice(e.lastIndexOf("."))}static isExtensionAllowed(e,t){return t?.find(s=>s.toUpperCase()===e.toUpperCase())}static isFileTypeAllowed(e,t){return t?.find(s=>s.toUpperCase()===e.toUpperCase())}execute(e,t=this.param){let s,n;const o=this.constructor,{allowedFileTypes:r,allowedFileExtensions:a,maxFileSize:l}=t;return r?.length?(s=e.some(c=>!o.isFileTypeAllowed(c.type,r)),s):a?.length?(n=e.some(c=>!o.isExtensionAllowed(o.getExtension(c.name),a)),n):e.findIndex(c=>!o.checkFileSize(c.size,l))>-1}static async getMessage(){return""}}class Qh extends Ss{static validatorName="DuplicateFileNames";constructor(e,t){super(e,t),this.type="info"}execute(e,t=this.param){return t.show}static async getMessage(){return ss().msg("lion-input-file:uploadTextDuplicateFileName")}}const ep=524288e3,qs={type:"FILE_TYPE",size:"FILE_SIZE"},jt={fail:"FAIL",pass:"SUCCESS"};class tp{constructor(e,t){this.failedProp=[],this.systemFile=e,this._acceptCriteria=t,this.uploadFileStatus(),this.failedProp.length===0&&this.createDownloadUrl(e)}_getFileNameExtension(e){return e.slice(e.lastIndexOf("."))}uploadFileStatus(){if(this._acceptCriteria.allowedFileExtensions.length){const e=this._getFileNameExtension(this.systemFile.name);ji.isExtensionAllowed(e,this._acceptCriteria.allowedFileExtensions)||(this.status=jt.fail,this.failedProp.push(qs.type))}else if(this._acceptCriteria.allowedFileTypes.length){const e=this.systemFile.type;ji.isFileTypeAllowed(e,this._acceptCriteria.allowedFileTypes)||(this.status=jt.fail,this.failedProp.push(qs.type))}ji.checkFileSize(this.systemFile.size,this._acceptCriteria.maxFileSize)?this.status!==jt.fail&&(this.status=jt.pass):(this.status=jt.fail,this.failedProp.push(qs.size))}createDownloadUrl(e){this.downloadUrl=window.URL.createObjectURL(e)}}const nr=(i,e,t)=>{const s=new Map;for(let n=e;n<=t;n++)s.set(i[n],n);return s},ip=ws(class extends xs{constructor(i){if(super(i),i.type!==ys.CHILD)throw Error("repeat() can only be used in text expressions")}dt(i,e,t){let s;t===void 0?t=e:e!==void 0&&(s=e);const n=[],o=[];let r=0;for(const a of i)n[r]=s?s(a,r):r,o[r]=t(a,r),r++;return{values:o,keys:n}}render(i,e,t){return this.dt(i,e,t).values}update(i,[e,t,s]){const n=Ju(i),{values:o,keys:r}=this.dt(e,t,s);if(!Array.isArray(n))return this.ut=r,o;const a=this.ut??=[],l=[];let d,c,h=0,m=n.length-1,b=0,p=o.length-1;for(;h<=m&&b<=p;)if(n[h]===null)h++;else if(n[m]===null)m--;else if(a[h]===r[b])l[b]=st(n[h],o[b]),h++,b++;else if(a[m]===r[p])l[p]=st(n[m],o[p]),m--,p--;else if(a[h]===r[p])l[p]=st(n[h],o[p]),Ht(i,l[p+1],n[h]),h++,p--;else if(a[m]===r[b])l[b]=st(n[m],o[b]),Ht(i,n[h],n[m]),m--,b++;else if(d===void 0&&(d=nr(r,b,p),c=nr(a,h,m)),d.has(a[h]))if(d.has(a[m])){const g=c.get(r[b]),_=g!==void 0?n[g]:null;if(_===null){const E=Ht(i,n[h]);st(E,o[b]),l[b]=E}else l[b]=st(_,o[b]),Ht(i,n[h],_),n[g]=null;b++}else Vs(n[m]),m--;else Vs(n[h]),h++;for(;b<=p;){const g=Ht(i,l[p+1]);st(g,o[b]),l[b++]=g}for(;h<=m;){const g=n[h++];g!==null&&Vs(g)}return this.ut=r,Zu(i,l),Te}}),Ia=i=>{switch(i){case"bg-BG":return C(()=>import("./bg-BG.js"),__vite__mapDeps([34,35]),import.meta.url);case"bg":return C(()=>import("./bg2.js"),[],import.meta.url);case"cs-CZ":return C(()=>import("./cs-CZ.js"),__vite__mapDeps([36,37]),import.meta.url);case"cs":return C(()=>import("./cs2.js"),[],import.meta.url);case"de-DE":return C(()=>import("./de-DE.js"),__vite__mapDeps([38,39]),import.meta.url);case"de":return C(()=>import("./de2.js"),[],import.meta.url);case"en-AU":return C(()=>import("./en-AU.js"),__vite__mapDeps([40,41]),import.meta.url);case"en-GB":return C(()=>import("./en-GB.js"),__vite__mapDeps([42,41]),import.meta.url);case"en-US":return C(()=>import("./en-US.js"),__vite__mapDeps([43,41]),import.meta.url);case"en-PH":case"en":return C(()=>import("./en2.js"),[],import.meta.url);case"es-ES":return C(()=>import("./es-ES.js"),__vite__mapDeps([44,45]),import.meta.url);case"es":return C(()=>import("./es2.js"),[],import.meta.url);case"fr-FR":return C(()=>import("./fr-FR.js"),__vite__mapDeps([46,47]),import.meta.url);case"fr-BE":return C(()=>import("./fr-BE.js"),__vite__mapDeps([48,47]),import.meta.url);case"fr":return C(()=>import("./fr2.js"),[],import.meta.url);case"hu-HU":return C(()=>import("./hu-HU.js"),__vite__mapDeps([49,50]),import.meta.url);case"hu":return C(()=>import("./hu2.js"),[],import.meta.url);case"it-IT":return C(()=>import("./it-IT.js"),__vite__mapDeps([51,52]),import.meta.url);case"it":return C(()=>import("./it2.js"),[],import.meta.url);case"nl-BE":return C(()=>import("./nl-BE.js"),__vite__mapDeps([53,54]),import.meta.url);case"nl-NL":return C(()=>import("./nl-NL.js"),__vite__mapDeps([55,54]),import.meta.url);case"nl":return C(()=>import("./nl2.js"),[],import.meta.url);case"pl-PL":return C(()=>import("./pl-PL.js"),__vite__mapDeps([56,57]),import.meta.url);case"pl":return C(()=>import("./pl2.js"),[],import.meta.url);case"ro-RO":return C(()=>import("./ro-RO.js"),__vite__mapDeps([58,59]),import.meta.url);case"ro":return C(()=>import("./ro2.js"),[],import.meta.url);case"ru-RU":return C(()=>import("./ru-RU.js"),__vite__mapDeps([60,61]),import.meta.url);case"ru":return C(()=>import("./ru2.js"),[],import.meta.url);case"sk-SK":return C(()=>import("./sk-SK.js"),__vite__mapDeps([62,63]),import.meta.url);case"sk":return C(()=>import("./sk2.js"),[],import.meta.url);case"uk-UA":return C(()=>import("./uk-UA.js"),__vite__mapDeps([64,65]),import.meta.url);case"uk":return C(()=>import("./uk2.js"),[],import.meta.url);case"zh-CN":case"zh":return C(()=>import("./zh2.js"),[],import.meta.url);default:return C(()=>import("./en2.js"),[],import.meta.url)}};class Da extends ks(wi(H)){static get scopedElements(){return{...super.scopedElements,"lion-validation-feedback":La}}static get properties(){return{fileList:{type:Array},multiple:{type:Boolean}}}static localizeNamespaces=[{"lion-input-file":Ia},...super.localizeNamespaces];constructor(){super(),this.fileList=[],this.multiple=!1}updated(e){super.updated(e),e.has("fileList")&&this._enhanceLightDomA11y()}_enhanceLightDomA11y(){const e=this.shadowRoot?.querySelectorAll('[id^="file-feedback"]'),t=this.parentNode?.parentNode;e?.forEach(s=>{t?.addEventListener("focusin",()=>{s.setAttribute("aria-live","polite")}),t?.addEventListener("focusout",()=>{s.setAttribute("aria-live","assertive")})})}_removeFile(e){this.dispatchEvent(new CustomEvent("file-remove-requested",{detail:{removedFile:e,status:e.status,uploadResponse:e.response}}))}_validationFeedbackTemplate(e,t){return x`
      <lion-validation-feedback
        id="file-feedback-${t}"
        .feedbackData="${e}"
        aria-live="assertive"
      ></lion-validation-feedback>
    `}_listItemBeforeTemplate(e){return B}_listItemAfterTemplate(e,t){return x`
      <button
        class="selected__list__item__remove-button"
        aria-label="${this.msgLit("lion-input-file:removeButtonLabel",{fileName:e.systemFile.name})}"
        @click=${()=>this._removeFile(e)}
      >
        ${this._removeButtonContentTemplate()}
      </button>
    `}_removeButtonContentTemplate(){return x`✖️`}_selectedListItemTemplate(e){const t=yi();return x`
      <div class="selected__list__item" status="${e.status?e.status.toLowerCase():""}">
        <div class="selected__list__item__label">
          ${this._listItemBeforeTemplate(e)}
          <span id="selected-list-item-label-${t}" class="selected__list__item__label__text">
            <span class="sr-only">${this.msgLit("lion-input-file:fileNameDescriptionLabel")}</span>
            ${e.downloadUrl&&e.status!=="LOADING"?x`
                  <a
                    class="selected__list__item__label__link"
                    href="${e.downloadUrl}"
                    target="${e.downloadUrl.startsWith("blob")?"_blank":""}"
                    rel="${ve(e.downloadUrl.startsWith("blob")?"noopener noreferrer":void 0)}"
                    >${e.systemFile?.name}</a
                  >
                `:e.systemFile?.name}
          </span>
          ${this._listItemAfterTemplate(e,t)}
        </div>
        ${e.status==="FAIL"&&e.validationFeedback?x`
              ${ip(e.validationFeedback,s=>x`
                  ${this._validationFeedbackTemplate([s],t)}
                `)}
            `:B}
      </div>
    `}render(){return this.fileList?.length?x`
          ${this.multiple?x`
                <ul class="selected__list">
                  ${this.fileList.map(e=>x` <li>${this._selectedListItemTemplate(e)}</li> `)}
                </ul>
              `:x` ${this._selectedListItemTemplate(this.fileList[0])} `}
        `:B}static get styles(){return[O`
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
      `]}}function js(i,e=2){if(!+i)return"0 Bytes";const t=1024,s=e<0?0:e,n=[" bytes","KB","MB","GB","TB","PB","EB","ZB","YB"],o=Math.floor(Math.log(i)/Math.log(t));return`${parseFloat((i/t**o).toFixed(s))}${n[o]}`}class sp extends wi(ks(Ei)){static get scopedElements(){return{...super.scopedElements,"lion-selected-file-list":Da}}static get properties(){return{accept:{type:String},multiple:{type:Boolean,reflect:!0},buttonLabel:{type:String,attribute:"button-label"},maxFileSize:{type:Number,attribute:"max-file-size"},enableDropZone:{type:Boolean,attribute:"enable-drop-zone"},uploadOnSelect:{type:Boolean,attribute:"upload-on-select"},isDragging:{type:Boolean,attribute:"is-dragging",reflect:!0},uploadResponse:{type:Array,state:!1},_selectedFilesMetaData:{type:Array,state:!0}}}static localizeNamespaces=[{"lion-input-file":Ia},...super.localizeNamespaces];static get validationTypes(){return["error","info"]}get slots(){return{...super.slots,input:()=>x`<input .value="${ve(this.getAttribute("value"))}" />`,"file-select-button":()=>x`<button
          type="button"
          id="select-button-${this._inputId}"
          @click="${this.__openDialogOnBtnClick}"
        >
          ${this.buttonLabel}
        </button>`,after:()=>x`<div data-description></div>`,"selected-file-list":()=>({template:x`
          <lion-selected-file-list
            .fileList=${this._selectedFilesMetaData}
            .multiple=${this.multiple}
          ></lion-selected-file-list>
        `,renderAsDirectHostChild:!0})}}get _inputNode(){return super._inputNode}get _buttonNode(){return this.querySelector(`#select-button-${this._inputId}`)}get buttonLabel(){return this.__buttonLabel||this._buttonNode?.textContent?.trim()||""}set buttonLabel(e){const t=this.buttonLabel;this.__buttonLabel=e,this.requestUpdate("buttonLabel",t)}get _focusableNode(){return this._buttonNode}get _isDragAndDropSupported(){return"draggable"in document.createElement("div")}constructor(){super(),this.type="file",this._selectedFilesMetaData=[],this.uploadResponse=[],this.__initialUploadResponse=this.uploadResponse,this.uploadOnSelect=!1,this.multiple=!1,this.enableDropZone=!1,this.maxFileSize=ep,this.accept="",this.buttonLabel="",this._initialButtonLabel="",this.modelValue=[],this._onRemoveFile=this._onRemoveFile.bind(this),this.__duplicateFileNamesValidator=new Qh({show:!1}),this.__previouslyParsedFiles=null}get _fileListNode(){return Array.from(this.children).find(e=>e.slot==="selected-file-list")}connectedCallback(){super.connectedCallback(),this.__initialUploadResponse=this.uploadResponse,this._initialButtonLabel=this.buttonLabel,this._inputNode.addEventListener("change",this._onChange),this._inputNode.addEventListener("click",this._onClick)}disconnectedCallback(){super.disconnectedCallback(),this._inputNode.removeEventListener("change",this._onChange),this._inputNode.removeEventListener("click",this._onClick)}onLocaleUpdated(){super.onLocaleUpdated(),this.multiple?this.buttonLabel=this._initialButtonLabel||this.msgLit("lion-input-file:selectTextMultipleFile"):this.buttonLabel=this._initialButtonLabel||this.msgLit("lion-input-file:selectTextSingleFile")}get operationMode(){return"upload"}get _acceptCriteria(){let e=[],t=[];if(this.accept){const s=this.accept.replace(/\s+/g,"").split(",");e=s.filter(n=>n.includes("/")),t=s.filter(n=>!n.includes("/"))}return{allowedFileTypes:e,allowedFileExtensions:t,maxFileSize:this.maxFileSize}}reset(){super.reset(),this._selectedFilesMetaData=[],this.uploadResponse=this.__initialUploadResponse,this.modelValue=[],this.dirty=!1}clear(){this._selectedFilesMetaData=[],this.uploadResponse=[],this.modelValue=[]}_showFeedbackConditionFor(e,t){return super._showFeedbackConditionFor(e,t)&&!(this.validationStates.error?.FileTypeAllowed||this.validationStates.error?.FileSizeAllowed)}parser(){if(this.__previouslyParsedFiles===this._inputNode.files)return this.modelValue;this.__previouslyParsedFiles=this._inputNode.files;const e=this._inputNode.files?Array.from(this._inputNode.files):[];return this.multiple?[...this.modelValue??[],...e]:e}formatter(e){return this._inputNode?.value||""}__setupDragDropEventListeners(){const e=this.shadowRoot?.querySelector(".input-file__drop-zone");["dragenter","dragover","dragleave"].forEach(t=>{e?.addEventListener(t,s=>{s.preventDefault(),s.stopPropagation(),this.isDragging=t!=="dragleave"},!1)}),window.addEventListener("drop",t=>{t.target===this._inputNode&&t.preventDefault(),this.isDragging=!1},!1)}firstUpdated(e){super.firstUpdated(e),this.__setupFileValidators(),this._inputNode&&(this._inputNode.type=this.type,this._inputNode.setAttribute("tabindex","-1"),this._inputNode.multiple=this.multiple,this.accept.length&&(this._inputNode.accept=this.accept)),this.enableDropZone&&this._isDragAndDropSupported&&(this.__setupDragDropEventListeners(),this.setAttribute("drop-zone","")),this._fileListNode.addEventListener("file-remove-requested",this._onRemoveFile)}updated(e){super.updated(e),e.has("disabled")&&(this._inputNode.disabled=this.disabled,this.validate()),e.has("buttonLabel")&&this._buttonNode&&(this._buttonNode.textContent=this.buttonLabel),e.has("name")&&(this._inputNode.name=this.name),e.has("_ariaLabelledNodes")&&this.__syncAriaLabelledByAttributesToButton(),e.has("_ariaDescribedNodes")&&this.__syncAriaDescribedByAttributesToButton(),e.has("uploadResponse")&&(this._selectedFilesMetaData.length===0&&this.uploadResponse.forEach(t=>{const s={systemFile:{name:t.name},response:t,status:t.status,validationFeedback:[{message:t.errorMessage}]};this._selectedFilesMetaData=[...this._selectedFilesMetaData,s]}),this._selectedFilesMetaData.forEach(t=>{!this.uploadResponse.some(s=>s.name===t.systemFile.name)&&this.uploadOnSelect?this.__removeFileFromList(t):(this.uploadResponse.forEach(s=>{s.name===t.systemFile.name&&(t.response=s,t.downloadUrl=s.downloadUrl?s.downloadUrl:t.downloadUrl,t.status=s.status,t.validationFeedback=[{type:typeof s.errorMessage=="string"&&s.errorMessage?.length>0?"error":"success",message:s.errorMessage??""}])}),this._selectedFilesMetaData=[...this._selectedFilesMetaData])}),this._updateUploadButtonDescription())}__computeNewAddedFiles(e){const t=e.filter(s=>this._selectedFilesMetaData.findIndex(n=>n.systemFile.name===s.name)===-1);return this.__duplicateFileNamesValidator.param={show:e.length!==t.length},this.validate(),t}_processDroppedFiles(e){if(e.preventDefault(),this.isDragging=!1,!(e.dataTransfer&&e.dataTransfer.items.length>1&&!this.multiple||!e.dataTransfer?.files)){if(this._inputNode.files=e.dataTransfer.files,this.multiple){const s=this.__computeNewAddedFiles(Array.from(e.dataTransfer.files));this.modelValue=[...this.modelValue??[],...s]}else this.modelValue=Array.from(e.dataTransfer.files);this._processFiles(Array.from(e.dataTransfer.files))}}_onChange(e){this.touched=!0,this._onUserInputChanged(),this._processFiles(e?.target?.files)}_onClick(e){e.target.value=""}__syncAriaLabelledByAttributesToButton(){if(this._inputNode.hasAttribute("aria-labelledby")){const e=this._inputNode.getAttribute("aria-labelledby");this._buttonNode?.setAttribute("aria-labelledby",`select-button-${this._inputId} ${e}`)}}__syncAriaDescribedByAttributesToButton(){if(this._inputNode.hasAttribute("aria-describedby")){const e=this._inputNode.getAttribute("aria-describedby")||"";this._buttonNode?.setAttribute("aria-describedby",e)}}__setupFileValidators(){this.defaultValidators=[new ji(this._acceptCriteria),this.__duplicateFileNamesValidator]}_processFiles(e){const t=this.__computeNewAddedFiles(Array.from(e));!this.multiple&&t.length>0&&(this._selectedFilesMetaData=[],this.uploadResponse=[]);let s;for(const o of t.values())s=new tp(o,this._acceptCriteria),s.failedProp?.length?(this._handleErroredFiles(s),this.uploadResponse=[...this.uploadResponse,{name:s.systemFile.name,status:"FAIL",errorMessage:s.validationFeedback[0].message}]):this.uploadResponse=[...this.uploadResponse,{name:s.systemFile.name,status:"SUCCESS"}],this._selectedFilesMetaData=[...this._selectedFilesMetaData,s],this._handleErrors();const n=this._selectedFilesMetaData.filter(({systemFile:o,status:r})=>t.includes(o)&&r==="SUCCESS").map(({systemFile:o})=>o);n.length>0&&this._dispatchFileListChangeEvent(n)}_dispatchFileListChangeEvent(e){this.dispatchEvent(new CustomEvent("file-list-changed",{detail:{newFiles:e}}))}_handleErrors(){let e=!1;if(this._selectedFilesMetaData.forEach(t=>{t.failedProp&&t.failedProp.length>0&&(e=!0)}),e)this.hasFeedbackFor?.push("error"),this.shouldShowFeedbackFor.push("error");else if(this._prevHasErrors&&this.hasFeedbackFor.includes("error")){const t=this.hasFeedbackFor.indexOf("error");this.hasFeedbackFor.slice(t,t+1);const s=this.shouldShowFeedbackFor.indexOf("error");this.shouldShowFeedbackFor.slice(s,s+1)}this._prevHasErrors=e}_handleErroredFiles(e){e.validationFeedback=[];const{allowedFileExtensions:t,allowedFileTypes:s}=this._acceptCriteria;let n=[],o=0,r;t.length?(n=t,r=n.pop(),o=n.length):s.length&&(s.forEach(d=>{if(d.endsWith("/*"))n.push(d.slice(0,-2));else if(d==="text/plain")n.push("text");else{const c=d.indexOf("/"),h=d.slice(c+1);if(!h.includes("+"))n.push(`.${h}`);else{const m=h.split("+");n.push(`.${m[0]}`)}}}),r=n.pop(),o=n.length);let a="";r?o?a=`${this.msgLit("lion-input-file:allowedFileValidatorComplex",{allowedTypesArray:n.join(", "),allowedTypesLastItem:r,maxSize:js(this.maxFileSize)})}`:a=`${this.msgLit("lion-input-file:allowedFileValidatorSimple",{allowedType:r,maxSize:js(this.maxFileSize)})}`:a=`${this.msgLit("lion-input-file:allowedFileSize",{maxSize:js(this.maxFileSize)})}`;const l={message:a,type:"error"};e.validationFeedback?.push(l)}_updateUploadButtonDescription(){const e=[];let t;this._selectedFilesMetaData.forEach(n=>{n.status==="FAIL"&&(t=n.validationFeedback?n.validationFeedback[0].message.toString():"",e.push(n.systemFile.name))});const s=this.querySelector('[slot="after"]');if(s)if(!this._selectedFilesMetaData||this._selectedFilesMetaData.length===0)this.uploadOnSelect?s.textContent=this.msgLit("lion-input-file:noFilesUploaded"):s.textContent=this.msgLit("lion-input-file:noFilesSelected");else if(this._selectedFilesMetaData.length===1){const{name:n}=this._selectedFilesMetaData[0].systemFile;this.uploadOnSelect?s.textContent=t||this.msgLit("lion-input-file:fileUploaded")+(n??""):s.textContent=t||this.msgLit("lion-input-file:fileSelected")+(n??"")}else this.uploadOnSelect?s.textContent=`${this.msgLit("lion-input-file:filesUploaded",{numberOfFiles:this._selectedFilesMetaData.length})} ${t?this.msgLit("lion-input-file:generalValidatorMessage",{validatorMessage:t,listOfErroneousFiles:e.join(", ")}):""}`:s.textContent=`${this.msgLit("lion-input-file:filesSelected",{numberOfFiles:this._selectedFilesMetaData.length})} ${t?this.msgLit("lion-input-file:generalValidatorMessage",{validatorMessage:t,listOfErroneousFiles:e.join(", ")}):""}`}__removeFileFromList(e){this._selectedFilesMetaData=this._selectedFilesMetaData.filter(t=>t.systemFile.name!==e.systemFile.name),this.modelValue&&(this.modelValue=this.modelValue.filter(t=>t.name!==e.systemFile.name)),this._inputNode.value="",this._handleErrors(),this._updateUploadButtonDescription()}_onRemoveFile(e){if(this.disabled)return;const{removedFile:t}=e.detail;!this.uploadOnSelect&&t&&this.__removeFileFromList(t),this._removeFile(t)}_removeFile(e){this.dispatchEvent(new CustomEvent("file-removed",{detail:{removedFile:e,status:e.status,uploadResponse:e.response}}))}_reflectBackOn(){return!1}_isEmpty(){return this.modelValue?.length===0}_dropZoneTemplate(){return x`
      <div @drop="${this._processDroppedFiles}" class="input-file__drop-zone">
        <div class="input-file__drop-zone__text">
          ${this.msgLit("lion-input-file:dragAndDropText")}
        </div>
        <slot name="file-select-button"></slot>
      </div>
    `}_inputGroupAfterTemplate(){return x` <slot name="selected-file-list"></slot> `}_inputGroupInputTemplate(){return x`
      <slot name="input"> </slot>
      <slot name="after"> </slot>
      ${this.enableDropZone&&this._isDragAndDropSupported?this._dropZoneTemplate():x`
            <div class="input-group__file-select-button">
              <slot name="file-select-button"></slot>
            </div>
          `}
    `}static get styles(){return[super.styles,O`
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
      `]}__openDialogOnBtnClick(e){e.preventDefault(),e.stopPropagation(),this._inputNode.click()}}var np=class extends Da{static get styles(){return[...super.styles,O`
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
      `]}_listItemAfterTemplate(e,t){return x`
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
    `}_removeButtonContentTemplate(){return x`<craft-icon name="x"></craft-icon>`}_listItemBeforeTemplate(e){return x`<img src="${e.downloadUrl}" alt="" class="preview-thumb" />`}},op=O`
  /* Add any craft-specific styles for input-file here */
  ::slotted([slot='selected-file-list']) {
    margin-block-start: var(--c-spacing-lg);
  }
`,rp=class extends sp{static get styles(){return[...super.styles,Es,op]}get slots(){return{...super.slots,"file-select-button":()=>x`<craft-button
          type="button"
          id="select-button-${this._inputId}"
          @click="${this.__openDialogOnBtnClick}"
        >
          ${this.buttonLabel}
        </craft-button>`}}static get scopedElements(){return{...super.scopedElements,"lion-selected-file-list":np}}};customElements.get("craft-input-file")||customElements.define("craft-input-file",rp);var ap=class extends Event{constructor(){super("wa-load",{bubbles:!0,cancelable:!1,composed:!0})}};var lp=class extends Event{constructor(){super("wa-error",{bubbles:!0,cancelable:!1,composed:!0})}},cp=`:host {
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
`,Wt=Symbol(),Oi=Symbol(),Ws,Ks=new Map,he=class extends ge{constructor(){super(...arguments),this.svg=null,this.autoWidth=!1,this.swapOpacity=!1,this.label="",this.library="default",this.resolveIcon=async(i,e)=>{let t;if(e?.spriteSheet){this.hasUpdated||await this.updateComplete,this.svg=x`<svg part="svg">
        <use part="use" href="${i}"></use>
      </svg>`,await this.updateComplete;const s=this.shadowRoot.querySelector("[part='svg']");return typeof e.mutator=="function"&&e.mutator(s,this),this.svg}try{if(t=await fetch(i,{mode:"cors"}),!t.ok)return t.status===410?Wt:Oi}catch{return Oi}try{const s=document.createElement("div");s.innerHTML=await t.text();const n=s.firstElementChild;if(n?.tagName?.toLowerCase()!=="svg")return Wt;Ws||(Ws=new DOMParser);const r=Ws.parseFromString(n.outerHTML,"text/html").body.querySelector("svg");return r?(r.part.add("svg"),document.adoptNode(r)):Wt}catch{return Wt}}}connectedCallback(){super.connectedCallback(),Zc(this)}firstUpdated(i){super.firstUpdated(i),this.setIcon()}disconnectedCallback(){super.disconnectedCallback(),Jc(this)}getIconSource(){const i=Rs(this.library),e=this.family||td();return this.name&&i?{url:i.resolver(this.name,e,this.variant,this.autoWidth),fromLibrary:!0}:{url:this.src,fromLibrary:!1}}handleLabelChange(){typeof this.label=="string"&&this.label.length>0?(this.setAttribute("role","img"),this.setAttribute("aria-label",this.label),this.removeAttribute("aria-hidden")):(this.removeAttribute("role"),this.removeAttribute("aria-label"),this.setAttribute("aria-hidden","true"))}async setIcon(){const{url:i,fromLibrary:e}=this.getIconSource(),t=e?Rs(this.library):void 0;if(!i){this.svg=null;return}let s=Ks.get(i);s||(s=this.resolveIcon(i,t),Ks.set(i,s));const n=await s;if(n===Oi&&Ks.delete(i),i===this.getIconSource().url){if(Ta(n)){this.svg=n;return}switch(n){case Oi:case Wt:this.svg=null,this.dispatchEvent(new lp);break;default:this.svg=n.cloneNode(!0),t?.mutator?.(this.svg,this),this.dispatchEvent(new ap)}}}updated(i){super.updated(i);const e=Rs(this.library),t=this.shadowRoot?.querySelector("svg");t&&e?.mutator?.(t,this)}render(){return this.hasUpdated?this.svg:x`<svg part="svg" fill="currentColor" width="16" height="16"></svg>`}};he.css=cp;v([be()],he.prototype,"svg",2);v([y({reflect:!0})],he.prototype,"name",2);v([y({reflect:!0})],he.prototype,"family",2);v([y({reflect:!0})],he.prototype,"variant",2);v([y({attribute:"auto-width",type:Boolean,reflect:!0})],he.prototype,"autoWidth",2);v([y({attribute:"swap-opacity",type:Boolean,reflect:!0})],he.prototype,"swapOpacity",2);v([y()],he.prototype,"src",2);v([y()],he.prototype,"label",2);v([y({reflect:!0})],he.prototype,"library",2);v([Ee("label")],he.prototype,"handleLabelChange",1);v([Ee(["family","name","library","variant","src","autoWidth","swapOpacity"])],he.prototype,"setIcon",1);he=v([ke("wa-icon")],he);var dp=O``,up=class extends he{static get styles(){return[he.styles,dp]}};customElements.get("craft-icon")||customElements.define("craft-icon",up);var or=class extends so{static get styles(){return[...super.styles,Es,O`
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
    `,this.type="password"}get slots(){return{...super.slots,suffix:()=>({template:this.renderSuffix()})}}};L([be()],or.prototype,"_visible",void 0),customElements.get("craft-input-password")||customElements.define("craft-input-password",or);var hp=O`
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
`,Fi=class extends H{constructor(...e){super(...e),this.size="",this.variant=""}render(){let e=!!this.querySelector('[slot="prefix"]'),t=!!this.querySelector('[slot="suffix"]');return x`
      <div
        class="${Fe({chip:!0,"chip--small":this.size==="small","chip--medium":this.size==="medium","chip--large":this.size==="large","chip--plain":this.variant==="plain"})}"
      >
        ${e?x`<div class="chip__prefix"><slot name="prefix"></slot></div>`:B}
        <div class="chip__body">
          <slot></slot>
        </div>
        ${t?x`<div class="chip__suffix"><slot name="suffix"></slot></div>`:B}
      </div>
    `}};Fi.styles=[hp],L([y()],Fi.prototype,"size",void 0),L([y()],Fi.prototype,"variant",void 0),customElements.get("craft-chip")||customElements.define("craft-chip",Fi);var pp=O`
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
`,Li=class extends H{constructor(...e){super(...e),this.label=null,this.status=null}getLabel(){return!this.label&&this.status?`Status: ${this.status}`:this.label}render(){return x`
      <span
        class="${Fe({status:!0,"status--live":this.status==="live","status--enabled":this.status==="enabled","status--pending":this.status==="pending","status--expired":this.status==="expired","status--disabled":this.status==="disabled"})}"
        role="img"
        aria-label="${this.getLabel()}"
      ></span>
    `}};Li.styles=[pp],L([y()],Li.prototype,"label",void 0),L([y()],Li.prototype,"status",void 0),customElements.get("craft-status")||customElements.define("craft-status",Li);var ni=new Map;function fp(i){var e=ni.get(i);e&&e.destroy()}function mp(i){var e=ni.get(i);e&&e.update()}var Xt=null;typeof window>"u"?((Xt=function(i){return i}).destroy=function(i){return i},Xt.update=function(i){return i}):((Xt=function(i,e){return i&&Array.prototype.forEach.call(i.length?i:[i],function(t){return(function(s){if(s&&s.nodeName&&s.nodeName==="TEXTAREA"&&!ni.has(s)){var n,o=null,r=window.getComputedStyle(s),a=(n=s.value,function(){d({testForHeightReduction:n===""||!s.value.startsWith(n),restoreTextAlign:null}),n=s.value}),l=(function(h){s.removeEventListener("autosize:destroy",l),s.removeEventListener("autosize:update",c),s.removeEventListener("input",a),window.removeEventListener("resize",c),Object.keys(h).forEach(function(m){return s.style[m]=h[m]}),ni.delete(s)}).bind(s,{height:s.style.height,resize:s.style.resize,textAlign:s.style.textAlign,overflowY:s.style.overflowY,overflowX:s.style.overflowX,wordWrap:s.style.wordWrap});s.addEventListener("autosize:destroy",l),s.addEventListener("autosize:update",c),s.addEventListener("input",a),window.addEventListener("resize",c),s.style.overflowX="hidden",s.style.wordWrap="break-word",ni.set(s,{destroy:l,update:c}),c()}function d(h){var m,b,p=h.restoreTextAlign,g=p===void 0?null:p,_=h.testForHeightReduction,E=_===void 0||_,k=r.overflowY;if(s.scrollHeight!==0&&(r.resize==="vertical"?s.style.resize="none":r.resize==="both"&&(s.style.resize="horizontal"),E&&(m=(function(S){for(var R=[];S&&S.parentNode&&S.parentNode instanceof Element;)S.parentNode.scrollTop&&R.push([S.parentNode,S.parentNode.scrollTop]),S=S.parentNode;return function(){return R.forEach(function(U){var P=U[0],J=U[1];P.style.scrollBehavior="auto",P.scrollTop=J,P.style.scrollBehavior=null})}})(s),s.style.height=""),b=r.boxSizing==="content-box"?s.scrollHeight-(parseFloat(r.paddingTop)+parseFloat(r.paddingBottom)):s.scrollHeight+parseFloat(r.borderTopWidth)+parseFloat(r.borderBottomWidth),r.maxHeight!=="none"&&b>parseFloat(r.maxHeight)?(r.overflowY==="hidden"&&(s.style.overflow="scroll"),b=parseFloat(r.maxHeight)):r.overflowY!=="hidden"&&(s.style.overflow="hidden"),s.style.height=b+"px",g&&(s.style.textAlign=g),m&&m(),o!==b&&(s.dispatchEvent(new Event("autosize:resized",{bubbles:!0})),o=b),k!==r.overflow&&!g)){var A=r.textAlign;r.overflow==="hidden"&&(s.style.textAlign=A==="start"?"end":"start"),d({restoreTextAlign:A,testForHeightReduction:!0})}}function c(){d({testForHeightReduction:!0,restoreTextAlign:null})}})(t)}),i}).destroy=function(i){return i&&Array.prototype.forEach.call(i.length?i:[i],fp),i},Xt.update=function(i){return i&&Array.prototype.forEach.call(i.length?i:[i],mp),i});var Gs=Xt;class bp extends Ei{get _inputNode(){return Array.from(this.children).find(e=>e.slot==="input")}}class gp extends Ra(bp){static get properties(){return{maxRows:{type:Number,attribute:"max-rows"},rows:{type:Number,reflect:!0},readOnly:{type:Boolean,attribute:"readonly",reflect:!0},placeholder:{type:String,reflect:!0}}}get slots(){return{...super.slots,input:()=>{const e=document.createElement("textarea");return e.style.resize!==void 0&&(e.style.resize="none"),e}}}constructor(){super(),this.rows=2,this.maxRows=6,this.readOnly=!1,this.placeholder=""}connectedCallback(){super.connectedCallback(),this.__initializeAutoresize(),this.__intersectionObserver=new IntersectionObserver(()=>this.resizeTextarea()),this.__intersectionObserver.observe(this)}updated(e){if(super.updated(e),e.has("name")&&(this._inputNode.name=this.name),e.has("autocomplete")&&(this._inputNode.autocomplete=this.autocomplete),e.has("disabled")&&(this._inputNode.disabled=this.disabled,this.validate()),e.has("rows")){const t=this._inputNode;t&&(t.rows=this.rows)}if(e.has("readOnly")){const t=this._inputNode;t&&(t.readOnly=this.readOnly)}if(e.has("placeholder")){const t=this._inputNode;t&&(t.placeholder=this.placeholder)}e.has("modelValue")&&this.resizeTextarea(),(e.has("maxRows")||e.has("rows"))&&this.setTextareaMaxHeight()}disconnectedCallback(){super.disconnectedCallback(),Gs.destroy(this._inputNode)}setTextareaMaxHeight(){const{value:e}=this._inputNode;this._inputNode.value="",this.resizeTextarea();const t=window.getComputedStyle(this._inputNode,null),s=parseFloat(t.lineHeight)||parseFloat(t.height)/this.rows,n=parseFloat(t.paddingTop)+parseFloat(t.paddingBottom),o=parseFloat(t.borderTopWidth)+parseFloat(t.borderBottomWidth),r=t.boxSizing==="border-box"?n+o:0;this._inputNode.style.maxHeight=`${s*this.maxRows+r}px`,this._inputNode.value=e,this.resizeTextarea()}static get styles(){return[...super.styles,O`
        .input-group__container > .input-group__input ::slotted(.form-control) {
          box-sizing: content-box;
          overflow-x: hidden; /* for FF adds height to the TextArea to reserve place for scroll-bars */
        }

        /* Workaround https://bugzilla.mozilla.org/show_bug.cgi?id=1739079 */
        :host([disabled]) ::slotted(textarea) {
          user-select: none;
        }
      `]}get updateComplete(){return this.__textareaUpdateComplete?Promise.all([this.__textareaUpdateComplete,super.updateComplete]):super.updateComplete}resizeTextarea(){Gs.update(this._inputNode)}__initializeAutoresize(){this.__shady_native_contains?this.__textareaUpdateComplete=this.__waitForTextareaRenderedInRealDOM().then(()=>{this.__startAutoresize(),this.__textareaUpdateComplete=null}):this.__startAutoresize()}async __waitForTextareaRenderedInRealDOM(){let e=3;for(;e!==0&&!this.__shady_native_contains(this._inputNode);)await new Promise(t=>setTimeout(t)),e-=1}__startAutoresize(){Gs(this._inputNode),this.setTextareaMaxHeight()}}var _p=O`
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
`,vp=class extends gp{static get styles(){return[...super.styles,Es,_p]}};customElements.get("craft-textarea")||customElements.define("craft-textarea",vp);var yp=O`
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
`,rr=class extends H{render(){return x`<slot></slot>`}};rr.styles=[yp],customElements.get("craft-button-group")||customElements.define("craft-button-group",rr);class wp extends Ei{static get properties(){return{autocomplete:{type:String}}}constructor(){super(),this.autocomplete=void 0}get _inputNode(){return Array.from(this.children).find(e=>e.slot==="input")}}class xp extends wp{get operationMode(){return"select"}connectedCallback(){super.connectedCallback(),this._inputNode.addEventListener("change",this._proxyChangeEvent),this.__selectObserver=new MutationObserver(()=>{this._syncValueUpwards(),this._calculateValues({source:"model"})}),this.__selectObserver.observe(this._inputNode,{attributes:!0,childList:!0,subtree:!0})}updated(e){super.updated(e),e.has("disabled")&&(this._inputNode.disabled=this.disabled,this.validate()),e.has("name")&&(this._inputNode.name=this.name),e.has("autocomplete")&&(this._inputNode.autocomplete=this.autocomplete)}disconnectedCallback(){super.disconnectedCallback(),this._inputNode.removeEventListener("change",this._proxyChangeEvent),this.__selectObserver?.disconnect()}formatter(e){const t=Array.from(this._inputNode.options).find(s=>s.value===e);return t?t.text:""}_reflectBackFormattedValueToUser(){this._reflectBackOn()&&(this.value=typeof this.modelValue<"u"?this.modelValue:"")}_proxyChangeEvent(){this.dispatchEvent(new CustomEvent("user-input-changed",{bubbles:!0,composed:!0}))}}var Ep=O`
  ${Yn}

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
    ${Gn}
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
`,Cp=class extends xp{static get styles(){return[...super.styles,Ep]}_inputGroupInputTemplate(){return x`
      <div class="input-group__input">
        <slot name="input"></slot>
        <craft-icon
          class="indicator"
          name="chevron-down"
          style="font-size: 0.8em"
        ></craft-icon>
      </div>
    `}};customElements.get("craft-select")||customElements.define("craft-select",Cp);class kp extends jh(H){static get properties(){return{tabIndex:{type:Number,reflect:!0,attribute:"tabindex"}}}constructor(){super(),this.tabIndex=0}connectedCallback(){super.connectedCallback(),this.setAttribute("role","listbox")}createRenderRoot(){return this}}function ar(i,e){Array.from(i.childNodes).forEach(t=>{t.hasAttribute&&t.hasAttribute("slot")||e.appendChild(t)})}const Sp=i=>class extends gt(wi(to(Pt(eo(i))))){static get properties(){return{orientation:String,selectionFollowsFocus:{type:Boolean,attribute:"selection-follows-focus"},rotateKeyboardNavigation:{type:Boolean,attribute:"rotate-keyboard-navigation"},hasNoDefaultSelected:{type:Boolean,reflect:!0,attribute:"has-no-default-selected"},_noTypeAhead:{type:Boolean}}}static get styles(){return[...super.styles||[],O`
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
        `]}_inputGroupInputTemplate(){return x`
        <div class="input-group__input">
          <slot name="input"></slot>
          <slot id="options-outlet"></slot>
        </div>
      `}static get scopedElements(){return{...super.scopedElements,"lion-options":kp}}get slots(){return{...super.slots,input:()=>{const t=this.createScopedElement("lion-options");return t.setAttribute("data-tag-name","lion-options"),t.registrationTarget=this,t}}}get _inputNode(){return this.querySelector('[slot="input"]')}get _listboxNode(){return this._inputNode}get _listboxActiveDescendantNode(){return this._listboxNode.querySelector(`#${this._listboxActiveDescendant}`)}get _listboxSlot(){return this.shadowRoot.querySelector("slot[name=input]")}get _scrollTargetNode(){return this._listboxNode}get _activeDescendantOwnerNode(){return this._listboxNode}get activeIndex(){return this.formElements.findIndex(t=>t.active===!0)}set activeIndex(t){if(this.formElements[t]){const s=this.formElements[t];this.__setChildActive(s)}else this.__setChildActive(null)}get checkedIndex(){const t=this.formElements;return this.multipleChoice?t.filter(s=>s.checked).map(s=>t.indexOf(s)):t.indexOf(t.find(s=>s.checked))}set checkedIndex(t){this.setCheckedIndex(t)}constructor(){super(),this.hasNoDefaultSelected=!1,this.orientation="vertical",this.rotateKeyboardNavigation=!1,this.selectionFollowsFocus=!1,this._noTypeAhead=!1,this._typeAheadTimeout=1e3,this._listboxActiveDescendant=null,this.__hasInitialSelectedFormElement=!1,this._repropagationRole="choice-group",this._listboxReceivesNoFocus=!1,this._oldModelValue=void 0,this._listboxOnKeyDown=this._listboxOnKeyDown.bind(this),this._listboxOnClick=this._listboxOnClick.bind(this),this._listboxOnKeyUp=this._listboxOnKeyUp.bind(this),this._onChildActiveChanged=this._onChildActiveChanged.bind(this),this.__proxyChildModelValueChanged=this.__proxyChildModelValueChanged.bind(this),this.__preventScrollingWithArrowKeys=this.__preventScrollingWithArrowKeys.bind(this),this.__typedChars=[]}connectedCallback(){this._listboxNode&&(this._listboxNode.registrationTarget=this),super.connectedCallback(),this._setupListboxNode(),this.__setupEventListeners(),this.registrationComplete.then(()=>{this.__initInteractionStates()})}firstUpdated(t){super.firstUpdated(t),this.__moveOptionsToListboxNode(),this.registrationComplete.then(()=>{this._initialModelValue=this.modelValue}),new MutationObserver(()=>{this._onListboxContentChanged()}).observe(this._listboxNode,{childList:!0})}updated(t){super.updated(t),t.has("disabled")&&(this.disabled?this.__requestOptionsToBeDisabled():this.__retractRequestOptionsToBeDisabled())}disconnectedCallback(){super.disconnectedCallback(),this._teardownListboxNode(),this.__teardownEventListeners()}setCheckedIndex(t){if(this.multipleChoice&&Array.isArray(t)){this._uncheckChildren(this.formElements.filter(s=>s===t)),t.forEach(s=>{this.formElements[s]&&(this.formElements[s].checked=!this.formElements[s].checked)});return}typeof t=="number"&&(t===-1&&this._uncheckChildren(),this.formElements[t]&&(this.formElements[t].disabled?this._uncheckChildren():this.multipleChoice?this.formElements[t].checked=!this.formElements[t].checked:this.formElements[t].checked=!0))}addFormElement(t,s){super.addFormElement(t,s),t.id=t.id||`${this.localName}-option-${yi()}`,this.disabled&&t.makeRequestToBeDisabled(),this.__setAttributeForAllFormElements("aria-setsize",this.formElements.length),this.formElements.forEach((n,o)=>{n.setAttribute("aria-posinset",o+1)}),this.__proxyChildModelValueChanged({target:t}),this.resetInteractionState()}resetInteractionState(){super.resetInteractionState(),this.submitted=!1}reset(){this.modelValue=this._initialModelValue,this.activeIndex=-1,this.resetInteractionState()}clear(){super.clear(),this.setCheckedIndex(-1),this.resetInteractionState()}_handleTypeAhead(t,{setAsChecked:s}){const{key:n,code:o}=t;if(o.startsWith("Key")||o.startsWith("Digit")||o.startsWith("Numpad")){t.preventDefault(),this.__typedChars.push(n);const r=this.__typedChars.join(""),a=this.formElements.findIndex(l=>l.modelValue.value.toLowerCase().startsWith(r));a>=0&&(s&&this.setCheckedIndex(a),this.activeIndex=a),this.__pendingTypeAheadTimeout&&window.clearTimeout(this.__pendingTypeAheadTimeout),this.__pendingTypeAheadTimeout=setTimeout(()=>{this.__typedChars=[]},this._typeAheadTimeout)}}_getCheckedElements(){return this.formElements.filter(t=>t.checked)}_setupListboxNode(){this._listboxNode?this.__setupListboxNodeInteractions():this._listboxSlot&&this._listboxSlot.addEventListener("slotchange",()=>{this.__setupListboxNodeInteractions()})}_onListboxContentChanged(){}_teardownListboxNode(){this._listboxNode&&(this._listboxNode.removeEventListener("keydown",this._listboxOnKeyDown),this._listboxNode.removeEventListener("click",this._listboxOnClick),this._listboxNode.removeEventListener("keyup",this._listboxOnKeyUp))}_getNextEnabledOption(t,s=1){return this.__getEnabledOption(t,s)}_getPreviousEnabledOption(t,s=-1){return this.__getEnabledOption(t,s)}_onChildActiveChanged({target:t}){t.active===!0&&this.__setChildActive(t)}_listboxOnKeyDown(t){if(this.disabled)return;this._isHandlingUserInput=!0,setTimeout(()=>{this._isHandlingUserInput=!1});const{key:s}=t;switch(s){case" ":case"Enter":{if(s===" "&&this._listboxReceivesNoFocus||(s===" "&&t.preventDefault(),!this.formElements[this.activeIndex])||this.formElements[this.activeIndex].disabled)return;this.formElements[this.activeIndex].href&&this.formElements[this.activeIndex].click(),this.setCheckedIndex(this.activeIndex);break}case"ArrowUp":t.preventDefault(),this.orientation==="vertical"&&(this.activeIndex=this._getPreviousEnabledOption(this.activeIndex));break;case"ArrowLeft":if(this._listboxReceivesNoFocus)return;t.preventDefault(),this.orientation==="horizontal"&&(this.activeIndex=this._getPreviousEnabledOption(this.activeIndex));break;case"ArrowDown":t.preventDefault(),this.orientation==="vertical"&&(this.activeIndex=this._getNextEnabledOption(this.activeIndex));break;case"ArrowRight":if(this._listboxReceivesNoFocus)return;t.preventDefault(),this.orientation==="horizontal"&&(this.activeIndex=this._getNextEnabledOption(this.activeIndex));break;case"Home":if(this._listboxReceivesNoFocus)return;t.preventDefault(),this.activeIndex=this._getNextEnabledOption(0,0);break;case"End":if(this._listboxReceivesNoFocus)return;t.preventDefault(),this.activeIndex=this._getPreviousEnabledOption(this.formElements.length-1,0);break;default:this._noTypeAhead||this._handleTypeAhead(t,{setAsChecked:this.selectionFollowsFocus&&!this.multipleChoice})}["ArrowUp","ArrowDown","ArrowLeft","ArrowRight","Home","End"].includes(s)&&this.selectionFollowsFocus&&!this.multipleChoice&&this.setCheckedIndex(this.activeIndex)}_listboxOnClick(t){}_listboxOnKeyUp(t){if(this.disabled)return;this._isHandlingUserInput=!0,setTimeout(()=>{this._isHandlingUserInput=!1});const{key:s}=t;switch(s){case"ArrowUp":case"ArrowDown":case"Home":case"End":case"Enter":t.preventDefault()}}_onLabelClick(){this._listboxNode.focus()}_scrollIntoView(t,s){t.scrollIntoView({behavior:"smooth",block:"nearest"})}__setupEventListeners(){this._listboxNode.addEventListener("active-changed",this._onChildActiveChanged),this._listboxNode.addEventListener("model-value-changed",this.__proxyChildModelValueChanged)}__teardownEventListeners(){this._listboxNode.removeEventListener("active-changed",this._onChildActiveChanged),this._listboxNode.removeEventListener("model-value-changed",this.__proxyChildModelValueChanged)}__setChildActive(t){if(this.formElements.forEach(s=>{s.active=t===s}),!t){this._activeDescendantOwnerNode.removeAttribute("aria-activedescendant");return}this._activeDescendantOwnerNode.setAttribute("aria-activedescendant",t.id),this._scrollIntoView(t,this._scrollTargetNode)}_uncheckChildren(t=[]){const s=Array.isArray(t)?t:[t];this.formElements.forEach(n=>{s.includes(n)||(n.checked=!1)})}__onChildCheckedChanged(t){const{target:s}=t;t.stopPropagation&&t.stopPropagation(),s.checked&&!this.multipleChoice&&this._uncheckChildren(s)}__setAttributeForAllFormElements(t,s){this.formElements.forEach(n=>{n.setAttribute(t,s)})}__proxyChildModelValueChanged(t){t.stopPropagation&&t.stopPropagation(),this.__onChildCheckedChanged(t),this.requestUpdate("modelValue",this._oldModelValue),t.detail&&t.detail.formPath&&this.dispatchEvent(new CustomEvent("model-value-changed",{detail:{formPath:t.detail.formPath,isTriggeredByUser:t.detail.isTriggeredByUser||this._isHandlingUserInput,element:t.target}})),this._oldModelValue=this.modelValue}__getEnabledOption(t,s){const n=o=>s===1?o<this.formElements.length:o>=0;for(let o=t+s;n(o);o+=s)if(this.formElements[o]&&!this.formElements[o].hasAttribute("aria-hidden"))return o;if(this.rotateKeyboardNavigation){const o=s===-1?this.formElements.length-1:0;for(let r=o;n(r);r+=s)if(this.formElements[r]&&!this.formElements[r].hasAttribute("aria-hidden"))return r}return t}__moveOptionsToListboxNode(){const t=this.shadowRoot.getElementById("options-outlet");t&&(ar(this,this._listboxNode),t.addEventListener("slotchange",()=>{ar(this,this._listboxNode)}))}__preventScrollingWithArrowKeys(t){if(this.disabled)return;const{key:s}=t;switch(s){case"ArrowUp":case"ArrowDown":case"Home":case"End":t.preventDefault()}}__setupListboxNodeInteractions(){this._listboxNode.setAttribute("role","listbox"),this._listboxNode.setAttribute("aria-orientation",this.orientation),this._listboxNode.setAttribute("aria-multiselectable",`${this.multipleChoice}`),this._listboxNode.setAttribute("tabindex","0"),this._listboxNode.addEventListener("click",this._listboxOnClick),this._listboxNode.addEventListener("keyup",this._listboxOnKeyUp),this._listboxNode.addEventListener("keydown",this._listboxOnKeyDown),this._scrollTargetNode.addEventListener("keydown",this.__preventScrollingWithArrowKeys)}__requestOptionsToBeDisabled(){this.formElements.forEach(t=>{t.makeRequestToBeDisabled&&t.makeRequestToBeDisabled()})}__retractRequestOptionsToBeDisabled(){this.formElements.forEach(t=>{t.retractRequestToBeDisabled&&t.retractRequestToBeDisabled()})}__initInteractionStates(){this.initInteractionState()}},Ap=ie(Sp);class Tp extends Ap(Xn(Qn(xi(H)))){get _feedbackConditionMeta(){return{...super._feedbackConditionMeta,focused:this.focused}}get _focusableNode(){return this._inputNode}}class lr extends vi(io(Zn(Pt(H)))){static get properties(){return{active:{type:Boolean,reflect:!0}}}static get styles(){return[O`
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
      `]}get slots(){return{}}constructor(){super(),this.active=!1,this.__onClick=this.__onClick.bind(this),this.__registerEventListeners()}requestUpdate(e,t,s){super.requestUpdate(e,t,s),e==="active"&&this.active!==t&&this.dispatchEvent(new Event("active-changed",{bubbles:!0}))}updated(e){super.updated(e),e.has("checked")&&this.setAttribute("aria-selected",`${this.checked}`),e.has("disabled")&&this.setAttribute("aria-disabled",`${this.disabled}`)}render(){return x`
      <div class="choice-field__label">
        <slot></slot>
      </div>
    `}connectedCallback(){super.connectedCallback(),this.setAttribute("role","option")}__registerEventListeners(){this.addEventListener("click",this.__onClick)}__unRegisterEventListeners(){this.removeEventListener("click",this.__onClick)}__onClick(){if(this.disabled)return;const e=this._parentFormGroup;this._isHandlingUserInput=!0,e&&e.multipleChoice?(this.checked=!this.checked,this.active=!this.active):(this.checked=!0,this.active=!0),this._isHandlingUserInput=!1}}var Np=O`
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
    color: var(--c-color-neutral-on-subtle);
  }

  :host([active]) .hint {
    color: var(--c-color-neutral-on-emphasis);
  }
`,cr=class extends lr{constructor(...e){super(...e),this.hint=null}static get styles(){return[...lr.styles,Np]}render(){return x`
      <div class="choice-field__label">
        <slot></slot>
        ${this.hint?x` — <span class="hint">${this.hint}</span>`:B}
      </div>
    `}};L([y()],cr.prototype,"hint",void 0),customElements.get("craft-option")||customElements.define("craft-option",cr);var Ma=`@layer wa-utilities {
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
`;var Op=class extends Event{constructor(i){super("wa-select",{bubbles:!0,cancelable:!0,composed:!0}),this.detail=i}};function*Va(i=document.activeElement){i!=null&&(yield i,"shadowRoot"in i&&i.shadowRoot&&i.shadowRoot.mode!=="closed"&&(yield*Va(i.shadowRoot.activeElement)))}var Fp=`:host {
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
`,Ys=new Set,ue=class extends ge{constructor(){super(...arguments),this.submenuCleanups=new Map,this.localize=new Rt(this),this.userTypedQuery="",this.openSubmenuStack=[],this.open=!1,this.size="medium",this.placement="bottom-start",this.distance=0,this.skidding=0,this.handleDocumentKeyDown=async i=>{const e=this.localize.dir()==="rtl";if(i.key==="Escape"){const c=this.getTrigger();i.preventDefault(),i.stopPropagation(),this.open=!1,c?.focus();return}const t=[...Va()].find(c=>c.localName==="wa-dropdown-item"),s=t?.localName==="wa-dropdown-item",n=this.getCurrentSubmenuItem(),o=!!n;let r,a,l;o?(r=this.getSubmenuItems(n),a=r.find(c=>c.active||c===t),l=a?r.indexOf(a):-1):(r=this.getItems(),a=r.find(c=>c.active||c===t),l=a?r.indexOf(a):-1);let d;if(i.key==="ArrowUp"&&(i.preventDefault(),i.stopPropagation(),l>0?d=r[l-1]:d=r[r.length-1]),i.key==="ArrowDown"&&(i.preventDefault(),i.stopPropagation(),l!==-1&&l<r.length-1?d=r[l+1]:d=r[0]),i.key===(e?"ArrowLeft":"ArrowRight")&&s&&a&&a.hasSubmenu){i.preventDefault(),i.stopPropagation(),a.submenuOpen=!0,this.addToSubmenuStack(a),setTimeout(()=>{const c=this.getSubmenuItems(a);c.length>0&&(c.forEach((h,m)=>h.active=m===0),c[0].focus())},0);return}if(i.key===(e?"ArrowRight":"ArrowLeft")&&o){i.preventDefault(),i.stopPropagation();const c=this.removeFromSubmenuStack();c&&(c.submenuOpen=!1,setTimeout(()=>{c.focus(),c.active=!0,(c.slot==="submenu"?this.getSubmenuItems(c.parentElement):this.getItems()).forEach(m=>{m!==c&&(m.active=!1)})},0));return}if((i.key==="Home"||i.key==="End")&&(i.preventDefault(),i.stopPropagation(),d=i.key==="Home"?r[0]:r[r.length-1]),i.key==="Tab"&&await this.hideMenu(),i.key.length===1&&!(i.metaKey||i.ctrlKey||i.altKey)&&!(i.key===" "&&this.userTypedQuery==="")&&(clearTimeout(this.userTypedTimeout),this.userTypedTimeout=setTimeout(()=>{this.userTypedQuery=""},1e3),this.userTypedQuery+=i.key,r.some(c=>{const h=(c.textContent||"").trim().toLowerCase(),m=this.userTypedQuery.trim().toLowerCase();return h.startsWith(m)?(d=c,!0):!1})),d){i.preventDefault(),i.stopPropagation(),r.forEach(c=>c.active=c===d),d.focus();return}(i.key==="Enter"||i.key===" "&&this.userTypedQuery==="")&&s&&a&&(i.preventDefault(),i.stopPropagation(),a.hasSubmenu?(a.submenuOpen=!0,this.addToSubmenuStack(a),setTimeout(()=>{const c=this.getSubmenuItems(a);c.length>0&&(c.forEach((h,m)=>h.active=m===0),c[0].focus())},0)):this.makeSelection(a))},this.handleDocumentPointerDown=i=>{i.composedPath().some(s=>s instanceof HTMLElement?s===this||s.closest('wa-dropdown, [part="submenu"]'):!1)||(this.open=!1)},this.handleGlobalMouseMove=i=>{const e=this.getCurrentSubmenuItem();if(!e?.submenuOpen||!e.submenuElement)return;const t=e.submenuElement.getBoundingClientRect(),s=this.localize.dir()==="rtl",n=s?t.right:t.left,o=s?Math.max(i.clientX,n):Math.min(i.clientX,n),r=Math.max(t.top,Math.min(i.clientY,t.bottom));e.submenuElement.style.setProperty("--safe-triangle-cursor-x",`${o}px`),e.submenuElement.style.setProperty("--safe-triangle-cursor-y",`${r}px`);const a=e.matches(":hover"),l=e.submenuElement?.matches(":hover")||!!i.composedPath().find(d=>d instanceof HTMLElement&&d.closest('[part="submenu"]')===e.submenuElement);!a&&!l&&setTimeout(()=>{!e.matches(":hover")&&!e.submenuElement?.matches(":hover")&&(e.submenuOpen=!1)},100)}}disconnectedCallback(){super.disconnectedCallback(),clearInterval(this.userTypedTimeout),this.closeAllSubmenus(),this.submenuCleanups.forEach(i=>i()),this.submenuCleanups.clear(),document.removeEventListener("mousemove",this.handleGlobalMouseMove)}firstUpdated(){this.syncAriaAttributes()}async updated(i){i.has("open")&&(this.customStates.set("open",this.open),this.open?await this.showMenu():(this.closeAllSubmenus(),await this.hideMenu())),i.has("size")&&this.syncItemSizes()}getItems(i=!1){const e=this.defaultSlot.assignedElements({flatten:!0}).filter(t=>t.localName==="wa-dropdown-item");return i?e:e.filter(t=>!t.disabled)}getSubmenuItems(i,e=!1){const t=i.shadowRoot?.querySelector('slot[name="submenu"]')||i.querySelector('slot[name="submenu"]');if(!t)return[];const s=t.assignedElements({flatten:!0}).filter(n=>n.localName==="wa-dropdown-item");return e?s:s.filter(n=>!n.disabled)}syncItemSizes(){this.defaultSlot.assignedElements({flatten:!0}).filter(e=>e.localName==="wa-dropdown-item").forEach(e=>e.size=this.size)}addToSubmenuStack(i){const e=this.openSubmenuStack.indexOf(i);e!==-1?this.openSubmenuStack=this.openSubmenuStack.slice(0,e+1):this.openSubmenuStack.push(i)}removeFromSubmenuStack(){return this.openSubmenuStack.pop()}getCurrentSubmenuItem(){return this.openSubmenuStack.length>0?this.openSubmenuStack[this.openSubmenuStack.length-1]:void 0}closeAllSubmenus(){this.getItems(!0).forEach(e=>{e.submenuOpen=!1}),this.openSubmenuStack=[]}closeSiblingSubmenus(i){const e=i.closest('wa-dropdown-item:not([slot="submenu"])');let t;e?t=this.getSubmenuItems(e,!0):t=this.getItems(!0),t.forEach(s=>{s!==i&&s.submenuOpen&&(s.submenuOpen=!1)}),this.openSubmenuStack.includes(i)||this.openSubmenuStack.push(i)}getTrigger(){return this.querySelector('[slot="trigger"]')}async showMenu(){if(!this.getTrigger())return;const e=new _i;if(this.dispatchEvent(e),e.defaultPrevented){this.open=!1;return}Ys.forEach(s=>s.open=!1),this.popup.active=!0,this.open=!0,Ys.add(this),this.syncAriaAttributes(),document.addEventListener("keydown",this.handleDocumentKeyDown),document.addEventListener("pointerdown",this.handleDocumentPointerDown),document.addEventListener("mousemove",this.handleGlobalMouseMove),this.menu.classList.remove("hide"),await de(this.menu,"show");const t=this.getItems();t.length>0&&(t.forEach((s,n)=>s.active=n===0),t[0].focus()),this.dispatchEvent(new bi)}async hideMenu(){const i=new gi({source:this});if(this.dispatchEvent(i),i.defaultPrevented){this.open=!0;return}this.open=!1,Ys.delete(this),this.syncAriaAttributes(),document.removeEventListener("keydown",this.handleDocumentKeyDown),document.removeEventListener("pointerdown",this.handleDocumentPointerDown),document.removeEventListener("mousemove",this.handleGlobalMouseMove),this.menu.classList.remove("show"),await de(this.menu,"hide"),this.popup.active=this.open,this.dispatchEvent(new mi)}handleMenuClick(i){const e=i.target.closest("wa-dropdown-item");if(!(!e||e.disabled)){if(e.hasSubmenu){e.submenuOpen||(this.closeSiblingSubmenus(e),this.addToSubmenuStack(e),e.submenuOpen=!0),i.stopPropagation();return}this.makeSelection(e)}}async handleMenuSlotChange(){const i=this.getItems(!0);await Promise.all(i.map(s=>s.updateComplete)),this.syncItemSizes();const e=i.some(s=>s.type==="checkbox"),t=i.some(s=>s.hasSubmenu);i.forEach((s,n)=>{s.active=n===0,s.checkboxAdjacent=e,s.submenuAdjacent=t})}handleTriggerClick(){this.open=!this.open}handleSubmenuOpening(i){const e=i.detail.item;this.closeSiblingSubmenus(e),this.addToSubmenuStack(e),this.setupSubmenuPosition(e),this.processSubmenuItems(e)}setupSubmenuPosition(i){if(!i.submenuElement)return;this.cleanupSubmenuPosition(i);const e=_a(i,i.submenuElement,()=>{this.positionSubmenu(i),this.updateSafeTriangleCoordinates(i)});this.submenuCleanups.set(i,e);const t=i.submenuElement.querySelector('slot[name="submenu"]');t&&(t.removeEventListener("slotchange",ue.handleSubmenuSlotChange),t.addEventListener("slotchange",ue.handleSubmenuSlotChange),ue.handleSubmenuSlotChange({target:t}))}static handleSubmenuSlotChange(i){const e=i.target;if(!e)return;const t=e.assignedElements().filter(o=>o.localName==="wa-dropdown-item");if(t.length===0)return;const s=t.some(o=>o.hasSubmenu),n=t.some(o=>o.type==="checkbox");t.forEach(o=>{o.submenuAdjacent=s,o.checkboxAdjacent=n})}processSubmenuItems(i){if(!i.submenuElement)return;const e=this.getSubmenuItems(i,!0),t=e.some(s=>s.hasSubmenu);e.forEach(s=>{s.submenuAdjacent=t})}cleanupSubmenuPosition(i){const e=this.submenuCleanups.get(i);e&&(e(),this.submenuCleanups.delete(i))}positionSubmenu(i){if(!i.submenuElement)return;const t=this.localize.dir()==="rtl"?"left-start":"right-start";xa(i,i.submenuElement,{placement:t,middleware:[va({mainAxis:0,crossAxis:-5}),wa({fallbackStrategy:"bestFit"}),ya({padding:8})]}).then(({x:s,y:n,placement:o})=>{i.submenuElement.setAttribute("data-placement",o),Object.assign(i.submenuElement.style,{left:`${s}px`,top:`${n}px`})})}updateSafeTriangleCoordinates(i){if(!i.submenuElement||!i.submenuOpen)return;if(document.activeElement?.matches(":focus-visible")){i.submenuElement.style.setProperty("--safe-triangle-visible","none");return}i.submenuElement.style.setProperty("--safe-triangle-visible","block");const t=i.submenuElement.getBoundingClientRect(),s=this.localize.dir()==="rtl";i.submenuElement.style.setProperty("--safe-triangle-submenu-start-x",`${s?t.right:t.left}px`),i.submenuElement.style.setProperty("--safe-triangle-submenu-start-y",`${t.top}px`),i.submenuElement.style.setProperty("--safe-triangle-submenu-end-x",`${s?t.right:t.left}px`),i.submenuElement.style.setProperty("--safe-triangle-submenu-end-y",`${t.bottom}px`)}makeSelection(i){const e=this.getTrigger();if(i.disabled)return;i.type==="checkbox"&&(i.checked=!i.checked);const t=new Op({item:i});this.dispatchEvent(t),t.defaultPrevented||(this.open=!1,e?.focus())}async syncAriaAttributes(){const i=this.getTrigger();let e;i&&(i.localName==="wa-button"?(await customElements.whenDefined("wa-button"),await i.updateComplete,e=i.shadowRoot.querySelector('[part="base"]')):e=i,e.hasAttribute("id")||e.setAttribute("id",Kn("wa-dropdown-trigger-")),e.setAttribute("aria-haspopup","menu"),e.setAttribute("aria-expanded",this.open?"true":"false"),this.menu.setAttribute("aria-expanded","false"))}render(){let i=this.hasUpdated?this.popup.active:this.open;return x`
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
    `}};ue.css=[Ma,Fp];v([Q("slot:not([name])")],ue.prototype,"defaultSlot",2);v([Q("#menu")],ue.prototype,"menu",2);v([Q("wa-popup")],ue.prototype,"popup",2);v([y({type:Boolean,reflect:!0})],ue.prototype,"open",2);v([y({reflect:!0})],ue.prototype,"size",2);v([y({reflect:!0})],ue.prototype,"placement",2);v([y({type:Number})],ue.prototype,"distance",2);v([y({type:Number})],ue.prototype,"skidding",2);ue=v([ke("wa-dropdown")],ue);var As=class{constructor(i,...e){this.slotNames=[],this.handleSlotChange=t=>{const s=t.target;(this.slotNames.includes("[default]")&&!s.name||s.name&&this.slotNames.includes(s.name))&&this.host.requestUpdate()},(this.host=i).addController(this),this.slotNames=e}hasDefaultSlot(){return[...this.host.childNodes].some(i=>{if(i.nodeType===Node.TEXT_NODE&&i.textContent.trim()!=="")return!0;if(i.nodeType===Node.ELEMENT_NODE){const e=i;if(e.tagName.toLowerCase()==="wa-visually-hidden")return!1;if(!e.hasAttribute("slot"))return!0}return!1})}hasNamedSlot(i){return this.host.querySelector(`:scope > [slot="${i}"]`)!==null}test(i){return i==="[default]"?this.hasDefaultSlot():this.hasNamedSlot(i)}hostConnected(){this.host.shadowRoot.addEventListener("slotchange",this.handleSlotChange)}hostDisconnected(){this.host.shadowRoot.removeEventListener("slotchange",this.handleSlotChange)}};var Lp=`:host {
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
`,le=class extends ge{constructor(){super(...arguments),this.hasSlotController=new As(this,"[default]","start","end"),this.active=!1,this.variant="default",this.size="medium",this.checkboxAdjacent=!1,this.submenuAdjacent=!1,this.type="normal",this.checked=!1,this.disabled=!1,this.submenuOpen=!1,this.hasSubmenu=!1,this.handleSlotChange=()=>{this.hasSubmenu=this.hasSlotController.test("submenu"),this.updateHasSubmenuState(),this.hasSubmenu?(this.setAttribute("aria-haspopup","menu"),this.setAttribute("aria-expanded",this.submenuOpen?"true":"false")):(this.removeAttribute("aria-haspopup"),this.removeAttribute("aria-expanded"))}}connectedCallback(){super.connectedCallback(),this.addEventListener("mouseenter",this.handleMouseEnter.bind(this)),this.shadowRoot.addEventListener("slotchange",this.handleSlotChange)}disconnectedCallback(){super.disconnectedCallback(),this.closeSubmenu(),this.removeEventListener("mouseenter",this.handleMouseEnter),this.shadowRoot.removeEventListener("slotchange",this.handleSlotChange)}firstUpdated(){this.setAttribute("tabindex","-1"),this.hasSubmenu=this.hasSlotController.test("submenu"),this.updateHasSubmenuState()}updated(i){i.has("active")&&(this.setAttribute("tabindex",this.active?"0":"-1"),this.customStates.set("active",this.active)),i.has("checked")&&(this.setAttribute("aria-checked",this.checked?"true":"false"),this.customStates.set("checked",this.checked)),i.has("disabled")&&(this.setAttribute("aria-disabled",this.disabled?"true":"false"),this.customStates.set("disabled",this.disabled)),i.has("type")&&(this.type==="checkbox"?this.setAttribute("role","menuitemcheckbox"):this.setAttribute("role","menuitem")),i.has("submenuOpen")&&(this.customStates.set("submenu-open",this.submenuOpen),this.submenuOpen?this.openSubmenu():this.closeSubmenu())}updateHasSubmenuState(){this.customStates.set("has-submenu",this.hasSubmenu)}async openSubmenu(){!this.hasSubmenu||!this.submenuElement||(this.notifyParentOfOpening(),this.submenuElement.showPopover(),this.submenuElement.hidden=!1,this.submenuElement.setAttribute("data-visible",""),this.submenuOpen=!0,this.setAttribute("aria-expanded","true"),await de(this.submenuElement,"show"),setTimeout(()=>{const i=this.getSubmenuItems();i.length>0&&(i.forEach((e,t)=>e.active=t===0),i[0].focus())},0))}notifyParentOfOpening(){const i=new CustomEvent("submenu-opening",{bubbles:!0,composed:!0,detail:{item:this}});this.dispatchEvent(i);const e=this.parentElement;e&&[...e.children].filter(s=>s!==this&&s.localName==="wa-dropdown-item"&&s.getAttribute("slot")===this.getAttribute("slot")&&s.submenuOpen).forEach(s=>{s.submenuOpen=!1})}async closeSubmenu(){!this.hasSubmenu||!this.submenuElement||(this.submenuOpen=!1,this.setAttribute("aria-expanded","false"),this.submenuElement.hidden||(await de(this.submenuElement,"hide"),this.submenuElement.hidden=!0,this.submenuElement.removeAttribute("data-visible"),this.submenuElement.hidePopover()))}getSubmenuItems(){return[...this.children].filter(i=>i.localName==="wa-dropdown-item"&&i.getAttribute("slot")==="submenu"&&!i.hasAttribute("disabled"))}handleMouseEnter(){this.hasSubmenu&&!this.disabled&&(this.notifyParentOfOpening(),this.submenuOpen=!0)}render(){return x`
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
    `}};le.css=Lp;v([Q("#submenu")],le.prototype,"submenuElement",2);v([y({type:Boolean})],le.prototype,"active",2);v([y({reflect:!0})],le.prototype,"variant",2);v([y({reflect:!0})],le.prototype,"size",2);v([y({attribute:"checkbox-adjacent",type:Boolean,reflect:!0})],le.prototype,"checkboxAdjacent",2);v([y({attribute:"submenu-adjacent",type:Boolean,reflect:!0})],le.prototype,"submenuAdjacent",2);v([y()],le.prototype,"value",2);v([y({reflect:!0})],le.prototype,"type",2);v([y({type:Boolean})],le.prototype,"checked",2);v([y({type:Boolean,reflect:!0})],le.prototype,"disabled",2);v([y({type:Boolean,reflect:!0})],le.prototype,"submenuOpen",2);v([be()],le.prototype,"hasSubmenu",2);le=v([ke("wa-dropdown-item")],le);var $p=class extends ue{static get styles(){return[ue.styles,O`
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
      `]}},Rp=class extends le{static get styles(){return[le.styles,O`
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
      `]}};customElements.get("craft-dropdown")||customElements.define("craft-dropdown",$p),customElements.get("craft-dropdown-item")||customElements.define("craft-dropdown-item",Rp);function Ip({el:i,uid:e}){i.setAttribute("id",`panel-${e}`),i.setAttribute("role","tabpanel"),i.setAttribute("aria-labelledby",`button-${e}`),i.hasAttribute("tabindex")||i.setAttribute("tabindex","0")}function Dp(i){i.setAttribute("selected","true")}function dr(i){i.removeAttribute("selected")}function Mp({el:i,uid:e,clickHandler:t,keydownHandler:s,keyupHandler:n}){i.setAttribute("id",`button-${e}`),i.setAttribute("role","tab"),i.setAttribute("aria-controls",`panel-${e}`),i.addEventListener("click",t),i.addEventListener("keyup",n),i.addEventListener("keydown",s)}function Vp({el:i,clickHandler:e,keydownHandler:t,keyupHandler:s}){i.removeAttribute("id"),i.removeAttribute("role"),i.removeAttribute("aria-controls"),i.removeEventListener("click",e),i.removeEventListener("keyup",s),i.removeEventListener("keydown",t)}function Pp(i,e=!1){e&&i.focus(),i.setAttribute("selected","true"),i.setAttribute("aria-selected","true"),i.setAttribute("tabindex","0")}function ur(i){i.removeAttribute("selected"),i.setAttribute("aria-selected","false"),i.setAttribute("tabindex","-1")}function zp(i){const e=i;switch(e.key){case"ArrowDown":case"ArrowRight":case"ArrowUp":case"ArrowLeft":case"Home":case"End":e.preventDefault()}}class Bp extends H{static get properties(){return{selectedIndex:{type:Number,attribute:"selected-index",reflect:!0}}}static get styles(){return[O`
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
    `}constructor(){super(),this.selectedIndex=0}firstUpdated(e){super.firstUpdated(e),this.__setupSlots(),this.tabs[0]?.disabled&&(this.selectedIndex=this.tabs.findIndex(t=>!t.disabled))}get tabs(){return Array.from(this.children).filter(e=>e.slot==="tab")}get panels(){return Array.from(this.children).filter(e=>e.slot==="panel")}static enabledWarnings=super.enabledWarnings?.filter(e=>e!=="change-in-update")||[];__setupSlots(){if(this.shadowRoot){const e=this.shadowRoot.querySelector("slot[name=tab]"),t=()=>{this.__cleanStore(),this.__setupStore(),this.__updateSelected(!1)};e&&e.addEventListener("slotchange",t)}}__setupStore(){this.__store=[],this.tabs.length!==this.panels.length&&console.warn(`The amount of tabs (${this.tabs.length}) doesn't match the amount of panels (${this.panels.length}).`),this.tabs.forEach((e,t)=>{const s=yi(),n=this.panels[t],o={uid:s,el:e,button:e,panel:n,clickHandler:this.__createButtonClickHandler(t),keydownHandler:zp.bind(this),keyupHandler:this.__handleButtonKeyup.bind(this)};Ip({...o,el:o.panel}),Mp(o),dr(o.panel),ur(o.button),this.__store&&this.__store.push(o)})}__cleanStore(){this.__store&&(this.__store.forEach(e=>{Vp(e)}),this.__store=[])}__getNextNotDisabledTab(e,t,s){let n=[];const o=e.filter((a,l)=>!a.disabled&&l>this.selectedIndex),r=e.filter((a,l)=>!a.disabled&&l<this.selectedIndex);return s==="right"?n=[...o,...r]:n=[...r.reverse(),...o.reverse()],n[0]}__getNextAvailableIndex(e,t){const s=this.tabs[this.selectedIndex];if(this.tabs.every(n=>!n.disabled))return e;if(t==="ArrowRight"||t==="ArrowDown"){const n=this.__getNextNotDisabledTab(this.tabs,s,"right");return this.tabs.findIndex(o=>n===o)}if(t==="ArrowLeft"||t==="ArrowUp"){const n=this.__getNextNotDisabledTab(this.tabs,s,"left");return this.tabs.findIndex(o=>n===o)}if(t==="Home")return this.tabs.findIndex(n=>!n.disabled);if(t==="End"){const n=this.tabs.map((o,r)=>({disabled:o.disabled,index:r})).filter(o=>!o.disabled);return n[n.length-1].index}return-1}__createButtonClickHandler(e){return()=>{this._setSelectedIndexWithFocus(e)}}__handleButtonKeyup(e){const t=e;if(typeof this.selectedIndex=="number")switch(t.key){case"ArrowDown":case"ArrowRight":this.selectedIndex+1>=this._pairCount?this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(0,t.key)):this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this.selectedIndex+1,t.key));break;case"ArrowUp":case"ArrowLeft":this.selectedIndex<=0?this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this._pairCount-1,t.key)):this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this.selectedIndex-1,t.key));break;case"Home":this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(0,t.key));break;case"End":this._setSelectedIndexWithFocus(this.__getNextAvailableIndex(this._pairCount-1,t.key));break}}get selectedIndex(){return this.__selectedIndex||0}set selectedIndex(e){if(e===this.__selectedIndex)return;const t=this.__selectedIndex;this.__selectedIndex=e,this.__updateSelected(!1),this.dispatchEvent(new Event("selected-changed")),this.requestUpdate("selectedIndex",t)}_setSelectedIndexWithFocus(e){if(e===-1)return;const t=this.__selectedIndex;this.__selectedIndex=e,this.__updateSelected(!0),this.dispatchEvent(new Event("selected-changed")),this.requestUpdate("selectedIndex",t)}get _pairCount(){return this.__store&&this.__store.length||0}__updateSelected(e=!1){if(!(this.__store&&typeof this.selectedIndex=="number"&&this.__store[this.selectedIndex]))return;const t=this.tabs.find(r=>r.hasAttribute("selected")),s=this.panels.find(r=>r.hasAttribute("selected"));t&&ur(t),s&&dr(s);const{button:n,panel:o}=this.__store[this.selectedIndex];n&&Pp(n,e),o&&Dp(o)}}var Up=O`
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
`,Hp=class extends Bp{static get styles(){return[...super.styles,Up]}};customElements.get("craft-tabs")||customElements.define("craft-tabs",Hp);var qp=O`
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
`,Xs=class extends H{constructor(...e){super(...e),this.label=""}render(){let e=!!this.label||!!this.querySelector('[slot="header"]')||!!this.querySelector('[slot="label"]')||!!this.querySelector('[slot="actions"]'),t=!!this.querySelector('[slot="footer"]');return x`
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
    `}};Xs.styles=[qp],L([y()],Xs.prototype,"label",void 0),customElements.get("craft-card")||customElements.define("craft-card",Xs);var jp=O`
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
`,hr=class extends H{render(){return x`<slot></slot> `}};hr.styles=[jp],customElements.get("craft-tab")||customElements.define("craft-tab",hr);class Pa extends Aa(H){static get properties(){return{checked:{type:Boolean,reflect:!0}}}static get styles(){return[O`
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
    `}constructor(){super(),this.value="",this.checked=!1,this.__initialized=!1,this._toggleChecked=this._toggleChecked.bind(this),this.__handleKeydown=this._handleKeydown.bind(this),this.__handleKeyup=this._handleKeyup.bind(this)}connectedCallback(){super.connectedCallback(),this.setAttribute("role","switch"),this.setAttribute("aria-checked",`${this.checked}`),this.addEventListener("click",this._toggleChecked),this.addEventListener("keydown",this.__handleKeydown),this.addEventListener("keyup",this.__handleKeyup)}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener("click",this._toggleChecked),this.removeEventListener("keydown",this.__handleKeydown),this.removeEventListener("keyup",this.__handleKeyup)}_toggleChecked(){this.disabled||(this.focus(),this.checked=!this.checked)}__checkedStateChange(){this.dispatchEvent(new Event("checked-changed",{bubbles:!0})),this.setAttribute("aria-checked",`${this.checked}`)}_handleKeydown(e){e.key===" "&&e.preventDefault()}_handleKeyup(e){[" ","Enter"].includes(e.key)&&this._toggleChecked()}updated(e){super.updated(e),e.has("disabled")&&this.setAttribute("aria-disabled",`${this.disabled}`)}requestUpdate(e,t,s){super.requestUpdate(e,t,s),this.__initialized&&this.isConnected&&e==="checked"&&this.checked!==t&&!this.disabled&&this.__checkedStateChange()}firstUpdated(e){super.firstUpdated(e),this.__initialized=!0}}class Wp extends wi(io(Ei)){static get styles(){return[...super.styles,O`
        :host([hidden]) {
          display: none;
        }

        :host([disabled]) {
          color: #adadad;
        }
      `]}static get scopedElements(){return{...super.scopedElements,"lion-switch-button":Pa}}get _inputNode(){return Array.from(this.children).find(e=>e.slot==="input")}get slots(){return{...super.slots,input:()=>{const e=this.createScopedElement("lion-switch-button");return e.setAttribute("data-tag-name","lion-switch-button"),e}}}render(){return x`
      <div class="form-field__group-one">${this._groupOneTemplate()}</div>
      <div class="form-field__group-two">${this._groupTwoTemplate()}</div>
    `}_groupOneTemplate(){return x`${this._labelTemplate()} ${this._helpTextTemplate()} ${this._feedbackTemplate()}`}_groupTwoTemplate(){return x`${this._inputGroupTemplate()}`}constructor(){super(),this.checked=!1,this.__handleButtonSwitchCheckedChanged=this.__handleButtonSwitchCheckedChanged.bind(this)}connectedCallback(){super.connectedCallback(),this.addEventListener("checked-changed",this.__handleButtonSwitchCheckedChanged),this._labelNode&&this._labelNode.addEventListener("click",this._toggleChecked),this._syncButtonSwitch()}disconnectedCallback(){super.disconnectedCallback(),this._inputNode&&this.removeEventListener("checked-changed",this.__handleButtonSwitchCheckedChanged),this._labelNode&&this._labelNode.removeEventListener("click",this._toggleChecked)}updated(e){super.updated(e),e.has("disabled")&&this._syncButtonSwitch()}_toggleChecked(e){e.preventDefault(),super._toggleChecked(e)}_isEmpty(){return!1}__handleButtonSwitchCheckedChanged(e){e.stopPropagation(),this._isHandlingUserInput=!0,this.checked=this._inputNode.checked,this._isHandlingUserInput=!1}_syncButtonSwitch(){this._inputNode.disabled=this.disabled}_onLabelClick(){this.disabled||this._inputNode.focus()}}var za=class extends Pa{static get styles(){return[...super.styles,O`
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
      `]}};customElements.get("craft-switch-button")||customElements.define("craft-switch-button",za);var Kp=O`
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
`,Gp=class extends Wp{static get styles(){return[...super.styles,Kp]}get slots(){return{...super.slots,input:()=>{let e=this.createScopedElement("craft-switch-button");return e.setAttribute("data-tag-name","craft-switch-button"),e}}}static get scopedElements(){return{...super.scopedElements,"craft-switch-button":za}}};customElements.get("craft-switch")||customElements.define("craft-switch",Gp);var Yp=O`
  .breadcrumbs {
    display: flex;
    align-items: center;
  }
`,Ze=class extends H{constructor(...e){super(...e),this.label="",this.items=[],this.visibleItems=0,this.firstRender=!0}getSeparator(){let e=this.separatorSlot.assignedElements({flatten:!0})[0].cloneNode(!0);return[e,...e.querySelectorAll("[id]")].forEach(t=>t.removeAttribute("id")),e.setAttribute("data-default",""),e.slot="separator",e}calculateBreadcrumbItemsWidth(){this.items=this.breadcrumbsElements.map((e,t)=>{let s=e.offsetWidth;return e.hasAttribute("hidden")&&(e.removeAttribute("hidden"),s=e.offsetWidth,e.setAttribute("hidden","")),{label:e.innerText,href:e.href,value:t.toString(),offsetWidth:s,isVisible:!0}})}async handleSlotChange(){let e=[...this.defaultSlot.assignedElements({flatten:!0})].filter(t=>t.tagName.toLowerCase()==="craft-breadcrumb-item");if(e.forEach((t,s)=>{let n=t.querySelector('[slot="separator"]');n===null?t.append(this.getSeparator()):n.hasAttribute("data-default")&&n.replaceWith(this.getSeparator()),s===e.length-1?t.setAttribute("aria-current","page"):t.removeAttribute("aria-current")}),this.breadcrumbsElements.length===0){this.items=[],this.visibleItems=0;return}await Promise.all(this.breadcrumbsElements.map(t=>t.updateComplete)),this.calculateBreadcrumbItemsWidth(),this.visibleItems=0,this.adjustOverflow()}connectedCallback(){super.connectedCallback(),this.hasAttribute("role")||this.setAttribute("role","navigation"),this.resizeObserver=new ResizeObserver(()=>{if(this.firstRender){this.firstRender=!1;return}this.adjustOverflow()}),this.resizeObserver.observe(this)}adjustOverflow(){let e=this.getBoundingClientRect().width;console.log({availableSpace:e})}disconnectedCallback(){this.resizeObserver?.unobserve(this),super.disconnectedCallback()}render(){return x`
      <nav class="breadcrumbs" aria-label="${this.label}">
        <slot @slotchange="${this.handleSlotChange}"></slot>
      </nav>

      <span hidden aria-hidden="true">
        <slot name="separator"><span class="separator">/</span></slot>
      </span>
    `}};Ze.styles=[Yp],L([Q("slot")],Ze.prototype,"defaultSlot",void 0),L([Q('slot[name="separator"]')],Ze.prototype,"separatorSlot",void 0),L([zi({selector:"craft-breadcrumb-item"})],Ze.prototype,"breadcrumbsElements",void 0),L([y()],Ze.prototype,"label",void 0),L([be()],Ze.prototype,"items",void 0),L([be()],Ze.prototype,"visibleItems",void 0),customElements.get("craft-breadcrumbs")||customElements.define("craft-breadcrumbs",Ze);var Xp=`:host {
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
`,Le=class extends ge{constructor(){super(...arguments),this.renderType="button",this.rel="noreferrer noopener"}setRenderType(){const i=this.defaultSlot.assignedElements({flatten:!0}).filter(e=>e.tagName.toLowerCase()==="wa-dropdown").length>0;if(this.href){this.renderType="link";return}if(i){this.renderType="dropdown";return}this.renderType="button"}hrefChanged(){this.setRenderType()}handleSlotChange(){this.setRenderType()}render(){return x`
      <span part="start" class="start">
        <slot name="start"></slot>
      </span>

      ${this.renderType==="link"?x`
            <a
              part="label"
              class="label label-link"
              href="${this.href}"
              target="${ve(this.target?this.target:void 0)}"
              rel=${ve(this.target?this.rel:void 0)}
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
    `}};Le.css=Xp;v([Q("slot:not([name])")],Le.prototype,"defaultSlot",2);v([be()],Le.prototype,"renderType",2);v([y()],Le.prototype,"href",2);v([y()],Le.prototype,"target",2);v([y()],Le.prototype,"rel",2);v([Ee("href",{waitUntilFirstUpdate:!0})],Le.prototype,"hrefChanged",1);Le=v([ke("wa-breadcrumb-item")],Le);var Zp=class extends Le{static get styles(){return[Le.styles,O`
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
      `]}};customElements.get("craft-breadcrumb-item")||customElements.define("craft-breadcrumb-item",Zp);var Jp=`:host {
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
`,Zs=new Set,re=class extends ge{constructor(){super(...arguments),this.anchor=null,this.placement="top",this.open=!1,this.distance=8,this.skidding=0,this.for=null,this.withoutArrow=!1,this.eventController=new AbortController,this.handleAnchorClick=()=>{this.open=!this.open},this.handleBodyClick=i=>{i.target.closest('[data-popover="close"]')&&(i.stopPropagation(),this.open=!1)},this.handleDocumentKeyDown=i=>{i.key==="Escape"&&(i.preventDefault(),this.open=!1,this.anchor&&typeof this.anchor.focus=="function"&&this.anchor.focus())},this.handleDocumentClick=i=>{const e=i.target;this.anchor&&i.composedPath().includes(this.anchor)||e.closest("wa-popover")!==this&&(this.open=!1)}}connectedCallback(){super.connectedCallback(),this.id||(this.id=Kn("wa-popover-"))}disconnectedCallback(){super.disconnectedCallback(),document.removeEventListener("keydown",this.handleDocumentKeyDown),this.eventController.abort()}firstUpdated(){this.open&&(this.dialog.show(),this.popup.active=!0,this.popup.reposition())}updated(i){i.has("open")&&this.customStates.set("open",this.open)}async handleOpenChange(){if(this.open){const i=new _i;if(this.dispatchEvent(i),i.defaultPrevented){this.open=!1;return}Zs.forEach(e=>e.open=!1),document.addEventListener("keydown",this.handleDocumentKeyDown,{signal:this.eventController.signal}),document.addEventListener("click",this.handleDocumentClick,{signal:this.eventController.signal}),this.dialog.show(),this.popup.active=!0,Zs.add(this),requestAnimationFrame(()=>{const e=this.querySelector("[autofocus]");e&&typeof e.focus=="function"?e.focus():this.dialog.focus()}),await de(this.popup.popup,"show-with-scale"),this.popup.reposition(),this.dispatchEvent(new bi)}else{const i=new gi;if(this.dispatchEvent(i),i.defaultPrevented){this.open=!0;return}document.removeEventListener("keydown",this.handleDocumentKeyDown),document.removeEventListener("click",this.handleDocumentClick),Zs.delete(this),await de(this.popup.popup,"hide-with-scale"),this.popup.active=!1,this.dialog.close(),this.dispatchEvent(new mi)}}handleForChange(){const i=this.getRootNode();if(!i)return;const e=this.for?i.getElementById(this.for):null,t=this.anchor;if(e===t)return;const{signal:s}=this.eventController;e&&e.addEventListener("click",this.handleAnchorClick,{signal:s}),t&&t.removeEventListener("click",this.handleAnchorClick),this.anchor=e,this.for&&!e&&console.warn(`A popover was assigned to an element with an ID of "${this.for}" but the element could not be found.`,this)}async handleOptionsChange(){this.hasUpdated&&(await this.updateComplete,this.popup.reposition())}async show(){if(!this.open)return this.open=!0,ts(this,"wa-after-show")}async hide(){if(this.open)return this.open=!1,ts(this,"wa-after-hide")}render(){return x`
      <dialog part="dialog" class="dialog">
        <wa-popup
          part="popup"
          exportparts="
            popup:popup__popup,
            arrow:popup__arrow
          "
          class=${Fe({popover:!0,"popover-open":this.open})}
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
    `}};re.css=Jp;re.dependencies={"wa-popup":Y};v([Q("dialog")],re.prototype,"dialog",2);v([Q(".body")],re.prototype,"body",2);v([Q("wa-popup")],re.prototype,"popup",2);v([be()],re.prototype,"anchor",2);v([y()],re.prototype,"placement",2);v([y({type:Boolean,reflect:!0})],re.prototype,"open",2);v([y({type:Number})],re.prototype,"distance",2);v([y({type:Number})],re.prototype,"skidding",2);v([y()],re.prototype,"for",2);v([y({attribute:"without-arrow",type:Boolean,reflect:!0})],re.prototype,"withoutArrow",2);v([Ee("open",{waitUntilFirstUpdate:!0})],re.prototype,"handleOpenChange",1);v([Ee("for")],re.prototype,"handleForChange",1);v([Ee(["distance","placement","skidding"])],re.prototype,"handleOptionsChange",1);re=v([ke("wa-popover")],re);var Qp=class extends re{static get styles(){return[re.styles,O`
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
      `]}};customElements.get("craft-popover")||customElements.define("craft-popover",Qp);var pr=class extends H{render(){return x`
      <ul class="nav-list">
        <slot></slot>
      </ul>
    `}};pr.styles=O`
    :host {
      display: block;
    }

    .nav-list {
      display: grid;
      margin: 0;
      padding: 0;
      list-style: none;
    }
  `,customElements.get("craft-nav-list")||customElements.define("craft-nav-list",pr);const Ba="important",ef=" !"+Ba,tf=ws(class extends xs{constructor(i){if(super(i),i.type!==ys.ATTRIBUTE||i.name!=="style"||i.strings?.length>2)throw Error("The `styleMap` directive must be used in the `style` attribute and must be the only part in the attribute.")}render(i){return Object.keys(i).reduce(((e,t)=>{const s=i[t];return s==null?e:e+`${t=t.includes("-")?t:t.replace(/(?:^(webkit|moz|ms|o)|)(?=[A-Z])/g,"-$&").toLowerCase()}:${s};`}),"")}update(i,[e]){const{style:t}=i.element;if(this.ft===void 0)return this.ft=new Set(Object.keys(e)),this.render(e);for(const s of this.ft)e[s]==null&&(this.ft.delete(s),s.includes("-")?t.removeProperty(s):t[s]=null);for(const s in e){const n=e[s];if(n!=null){this.ft.add(s);const o=typeof n=="string"&&n.endsWith(ef);s.includes("-")||o?t.setProperty(s,o?n.slice(0,-11):n,o?Ba:""):t[s]=n}}return Te}});var sf=O`
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
`,De=class extends H{constructor(){super(),this.active=!1,this.external=!1,this.indicator=!1,this.iconOnly=!1,this.subnavState="closed",this.id=this.id||Math.random().toString(36).substring(2,6)}connectedCallback(){super.connectedCallback(),this.subnavState=this.active?"open":"closed"}toggleSubnav(e){e.preventDefault(),e.stopPropagation(),this.subnavState=this.subnavState==="open"?"closed":"open"}renderIconItem(e){let t=`item-${this.id}`;return x`
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
      <li>
        ${this.iconOnly?this.renderIconItem(e):this.renderItem(e)}
        ${e?x`
              <div
                class="subnav"
                id="${this.id}-subnav"
                style="${tf({display:this.subnavState==="open"?"block":"none"})}"
              >
                <slot name="subnav"></slot>
              </div>
            `:B}
      </li>
    `}};De.styles=sf,L([y()],De.prototype,"icon",void 0),L([y()],De.prototype,"url",void 0),L([y({type:Boolean,reflect:!0})],De.prototype,"active",void 0),L([y({type:Boolean})],De.prototype,"external",void 0),L([y({type:Boolean})],De.prototype,"indicator",void 0),L([y()],De.prototype,"id",void 0),L([y({reflect:!0,type:Boolean,attribute:"icon-only"})],De.prototype,"iconOnly",void 0),L([be()],De.prototype,"subnavState",void 0),customElements.get("craft-nav-item")||customElements.define("craft-nav-item",De);var xn=new Set;function nf(){const i=document.documentElement.clientWidth;return Math.abs(window.innerWidth-i)}function of(){const i=Number(getComputedStyle(document.body).paddingRight.replace(/px/,""));return isNaN(i)||!i?0:i}function os(i){if(xn.add(i),!document.documentElement.classList.contains("wa-scroll-lock")){const e=nf()+of();let t=getComputedStyle(document.documentElement).scrollbarGutter;(!t||t==="auto")&&(t="stable"),e<2&&(t=""),document.documentElement.style.setProperty("--wa-scroll-lock-gutter",t),document.documentElement.classList.add("wa-scroll-lock"),document.documentElement.style.setProperty("--wa-scroll-lock-size",`${e}px`)}}function rs(i){xn.delete(i),xn.size===0&&(document.documentElement.classList.remove("wa-scroll-lock"),document.documentElement.style.removeProperty("--wa-scroll-lock-size"))}function Ua(i){return i.split(" ").map(e=>e.trim()).filter(e=>e!=="")}var rf=`:host {
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
`,Ce=class extends ge{constructor(){super(...arguments),this.localize=new Rt(this),this.hasSlotController=new As(this,"footer","header-actions","label"),this.open=!1,this.label="",this.placement="end",this.withoutHeader=!1,this.lightDismiss=!0,this.handleDocumentKeyDown=i=>{i.key==="Escape"&&this.open&&(i.preventDefault(),i.stopPropagation(),this.requestClose(this.drawer))}}firstUpdated(){this.open&&(this.addOpenListeners(),this.drawer.showModal(),os(this))}disconnectedCallback(){super.disconnectedCallback(),rs(this),this.removeOpenListeners()}async requestClose(i){const e=new gi({source:i});if(this.dispatchEvent(e),e.defaultPrevented){this.open=!0,de(this.drawer,"pulse");return}this.removeOpenListeners(),await de(this.drawer,"hide"),this.open=!1,this.drawer.close(),rs(this);const t=this.originalTrigger;typeof t?.focus=="function"&&setTimeout(()=>t.focus()),this.dispatchEvent(new mi)}addOpenListeners(){document.addEventListener("keydown",this.handleDocumentKeyDown)}removeOpenListeners(){document.removeEventListener("keydown",this.handleDocumentKeyDown)}handleDialogCancel(i){i.preventDefault(),!this.drawer.classList.contains("hide")&&i.target===this.drawer&&this.requestClose(this.drawer)}handleDialogClick(i){const t=i.target.closest('[data-drawer="close"]');t&&(i.stopPropagation(),this.requestClose(t))}async handleDialogPointerDown(i){i.target===this.drawer&&(this.lightDismiss?this.requestClose(this.drawer):await de(this.drawer,"pulse"))}handleOpenChange(){this.open&&!this.drawer.open?this.show():this.drawer.open&&(this.open=!0,this.requestClose(this.drawer))}async show(){const i=new _i;if(this.dispatchEvent(i),i.defaultPrevented){this.open=!1;return}this.addOpenListeners(),this.originalTrigger=document.activeElement,this.open=!0,this.drawer.showModal(),os(this),requestAnimationFrame(()=>{const e=this.querySelector("[autofocus]");e&&typeof e.focus=="function"?e.focus():this.drawer.focus()}),await de(this.drawer,"show"),this.dispatchEvent(new bi)}render(){const i=!this.withoutHeader,e=this.hasSlotController.test("footer");return x`
      <dialog
        part="dialog"
        class=${Fe({drawer:!0,open:this.open,top:this.placement==="top",end:this.placement==="end",bottom:this.placement==="bottom",start:this.placement==="start"})}
        @cancel=${this.handleDialogCancel}
        @click=${this.handleDialogClick}
        @pointerdown=${this.handleDialogPointerDown}
      >
        ${i?x`
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
    `}};Ce.css=rf;v([Q(".drawer")],Ce.prototype,"drawer",2);v([y({type:Boolean,reflect:!0})],Ce.prototype,"open",2);v([y({reflect:!0})],Ce.prototype,"label",2);v([y({reflect:!0})],Ce.prototype,"placement",2);v([y({attribute:"without-header",type:Boolean,reflect:!0})],Ce.prototype,"withoutHeader",2);v([y({attribute:"light-dismiss",type:Boolean})],Ce.prototype,"lightDismiss",2);v([Ee("open",{waitUntilFirstUpdate:!0})],Ce.prototype,"handleOpenChange",1);Ce=v([ke("wa-drawer")],Ce);document.addEventListener("click",i=>{const e=i.target.closest("[data-drawer]");if(e instanceof Element){const[t,s]=Ua(e.getAttribute("data-drawer")||"");if(t==="open"&&s?.length){const o=e.getRootNode().getElementById(s);o?.localName==="wa-drawer"?o.open=!0:console.warn(`A drawer with an ID of "${s}" could not be found in this document.`)}}});document.body.addEventListener("pointerdown",()=>{});var af=()=>({checkValidity(i){const e=i.input,t={message:"",isValid:!0,invalidKeys:[]};if(!e)return t;let s=!0;if("checkValidity"in e&&(s=e.checkValidity()),s)return t;if(t.isValid=!1,"validationMessage"in e&&(t.message=e.validationMessage),!("validity"in e))return t.invalidKeys.push("customError"),t;for(const n in e.validity){if(n==="valid")continue;const o=n;e.validity[o]&&t.invalidKeys.push(o)}return t}});var Ha=class extends Event{constructor(){super("wa-invalid",{bubbles:!0,cancelable:!1,composed:!0})}},lf=()=>({observedAttributes:["custom-error"],checkValidity(i){const e={message:"",isValid:!0,invalidKeys:[]};return i.customError&&(e.message=i.customError,e.isValid=!1,e.invalidKeys=["customError"]),e}}),qe=class extends ge{constructor(){super(),this.name=null,this.disabled=!1,this.required=!1,this.assumeInteractionOn=["input"],this.validators=[],this.valueHasChanged=!1,this.hasInteracted=!1,this.customError=null,this.emittedEvents=[],this.emitInvalid=i=>{i.target===this&&(this.hasInteracted=!0,this.dispatchEvent(new Ha))},this.handleInteraction=i=>{const e=this.emittedEvents;e.includes(i.type)||e.push(i.type),e.length===this.assumeInteractionOn?.length&&(this.hasInteracted=!0)},this.addEventListener("invalid",this.emitInvalid)}static get validators(){return[lf()]}static get observedAttributes(){const i=new Set(super.observedAttributes||[]);for(const e of this.validators)if(e.observedAttributes)for(const t of e.observedAttributes)i.add(t);return[...i]}connectedCallback(){super.connectedCallback(),this.updateValidity(),this.assumeInteractionOn.forEach(i=>{this.addEventListener(i,this.handleInteraction)})}firstUpdated(...i){super.firstUpdated(...i),this.updateValidity()}willUpdate(i){if(i.has("customError")&&(this.customError||(this.customError=null),this.setCustomValidity(this.customError||"")),i.has("value")||i.has("disabled")){const e=this.value;if(Array.isArray(e)){if(this.name){const t=new FormData;for(const s of e)t.append(this.name,s);this.setValue(t,t)}}else this.setValue(e,e)}i.has("disabled")&&(this.customStates.set("disabled",this.disabled),(this.hasAttribute("disabled")||!this.matches(":disabled"))&&this.toggleAttribute("disabled",this.disabled)),this.updateValidity(),super.willUpdate(i)}get labels(){return this.internals.labels}getForm(){return this.internals.form}get validity(){return this.internals.validity}get willValidate(){return this.internals.willValidate}get validationMessage(){return this.internals.validationMessage}checkValidity(){return this.updateValidity(),this.internals.checkValidity()}reportValidity(){return this.updateValidity(),this.hasInteracted=!0,this.internals.reportValidity()}get validationTarget(){return this.input||void 0}setValidity(...i){const e=i[0],t=i[1];let s=i[2];s||(s=this.validationTarget),this.internals.setValidity(e,t,s||void 0),this.requestUpdate("validity"),this.setCustomStates()}setCustomStates(){const i=!!this.required,e=this.internals.validity.valid,t=this.hasInteracted;this.customStates.set("required",i),this.customStates.set("optional",!i),this.customStates.set("invalid",!e),this.customStates.set("valid",e),this.customStates.set("user-invalid",!e&&t),this.customStates.set("user-valid",e&&t)}setCustomValidity(i){if(!i){this.customError=null,this.setValidity({});return}this.customError=i,this.setValidity({customError:!0},i,this.validationTarget)}formResetCallback(){this.resetValidity(),this.hasInteracted=!1,this.valueHasChanged=!1,this.emittedEvents=[],this.updateValidity()}formDisabledCallback(i){this.disabled=i,this.updateValidity()}formStateRestoreCallback(i,e){this.value=i,e==="restore"&&this.resetValidity(),this.updateValidity()}setValue(...i){const[e,t]=i;this.internals.setFormValue(e,t)}get allValidators(){const i=this.constructor.validators||[],e=this.validators||[];return[...i,...e]}resetValidity(){this.setCustomValidity(""),this.setValidity({})}updateValidity(){if(this.disabled||this.hasAttribute("disabled")||!this.willValidate){this.resetValidity();return}const i=this.allValidators;if(!i?.length)return;const e={customError:!!this.customError},t=this.validationTarget||this.input||void 0;let s="";for(const n of i){const{isValid:o,message:r,invalidKeys:a}=n.checkValidity(this);o||(s||(s=r),a?.length>=0&&a.forEach(l=>e[l]=!0))}s||(s=this.validationMessage),this.setValidity(e,s,t)}};qe.formAssociated=!0;v([y({reflect:!0})],qe.prototype,"name",2);v([y({type:Boolean})],qe.prototype,"disabled",2);v([y({state:!0,attribute:!1})],qe.prototype,"valueHasChanged",2);v([y({state:!0,attribute:!1})],qe.prototype,"hasInteracted",2);v([y({attribute:"custom-error",reflect:!0})],qe.prototype,"customError",2);v([y({attribute:!1,state:!0,type:Object})],qe.prototype,"validity",1);var cf=`@layer wa-utilities {
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
`;const qa=Symbol.for(""),df=i=>{if(i?.r===qa)return i?._$litStatic$},fr=(i,...e)=>({_$litStatic$:e.reduce(((t,s,n)=>t+(o=>{if(o._$litStatic$!==void 0)return o._$litStatic$;throw Error(`Value passed to 'literal' function must be a 'literal' result: ${o}. Use 'unsafeStatic' to pass non-literal values, but
            take care to ensure page security.`)})(s)+i[n+1]),i[0]),r:qa}),mr=new Map,uf=i=>(e,...t)=>{const s=t.length;let n,o;const r=[],a=[];let l,d=0,c=!1;for(;d<s;){for(l=e[d];d<s&&(o=t[d],(n=df(o))!==void 0);)l+=n+e[++d],c=!0;d!==s&&a.push(o),r.push(l),d++}if(d===s&&r.push(e[s]),c){const h=r.join("$$lit$$");(e=mr.get(h))===void 0&&(r.raw=r,mr.set(h,e=r)),t=a}return i(e,...t)},Js=uf(x);var hf=`@layer wa-component {
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
`,K=class extends qe{constructor(){super(...arguments),this.assumeInteractionOn=["click"],this.hasSlotController=new As(this,"[default]","start","end"),this.localize=new Rt(this),this.invalid=!1,this.isIconButton=!1,this.title="",this.variant="neutral",this.appearance="accent",this.size="medium",this.withCaret=!1,this.disabled=!1,this.loading=!1,this.pill=!1,this.type="button",this.form=null}static get validators(){return[...super.validators,af()]}constructLightDOMButton(){const i=document.createElement("button");return i.type=this.type,i.style.position="absolute",i.style.width="0",i.style.height="0",i.style.clipPath="inset(50%)",i.style.overflow="hidden",i.style.whiteSpace="nowrap",this.name&&(i.name=this.name),i.value=this.value||"",["form","formaction","formenctype","formmethod","formnovalidate","formtarget"].forEach(e=>{this.hasAttribute(e)&&i.setAttribute(e,this.getAttribute(e))}),i}handleClick(){if(!this.getForm())return;const e=this.constructLightDOMButton();this.parentElement?.append(e),e.click(),e.remove()}handleInvalid(){this.dispatchEvent(new Ha)}handleLabelSlotChange(){const i=this.labelSlot.assignedNodes({flatten:!0});let e=!1,t=!1,s=!1,n=!1;[...i].forEach(o=>{if(o.nodeType===Node.ELEMENT_NODE){const r=o;r.localName==="wa-icon"?(t=!0,e||(e=r.label!==void 0)):n=!0}else o.nodeType===Node.TEXT_NODE&&(o.textContent?.trim()||"").length>0&&(s=!0)}),this.isIconButton=t&&!s&&!n,this.isIconButton&&!e&&console.warn('Icon buttons must have a label for screen readers. Add <wa-icon label="..."> to remove this warning.',this)}isButton(){return!this.href}isLink(){return!!this.href}handleDisabledChange(){this.updateValidity()}setValue(...i){}click(){this.button.click()}focus(i){this.button.focus(i)}blur(){this.button.blur()}render(){const i=this.isLink(),e=i?fr`a`:fr`button`;return Js`
      <${e}
        part="base"
        class=${Fe({button:!0,caret:this.withCaret,disabled:this.disabled,loading:this.loading,rtl:this.localize.dir()==="rtl","has-label":this.hasSlotController.test("[default]"),"has-start":this.hasSlotController.test("start"),"has-end":this.hasSlotController.test("end"),"is-icon-button":this.isIconButton})}
        ?disabled=${ve(i?void 0:this.disabled)}
        type=${ve(i?void 0:this.type)}
        title=${this.title}
        name=${ve(i?void 0:this.name)}
        value=${ve(i?void 0:this.value)}
        href=${ve(i?this.href:void 0)}
        target=${ve(i?this.target:void 0)}
        download=${ve(i?this.download:void 0)}
        rel=${ve(i&&this.rel?this.rel:void 0)}
        role=${ve(i?void 0:"button")}
        aria-disabled=${this.disabled?"true":"false"}
        tabindex=${this.disabled?"-1":"0"}
        @invalid=${this.isButton()?this.handleInvalid:null}
        @click=${this.handleClick}
      >
        <slot name="start" part="start" class="start"></slot>
        <slot part="label" class="label" @slotchange=${this.handleLabelSlotChange}></slot>
        <slot name="end" part="end" class="end"></slot>
        ${this.withCaret?Js`
                <wa-icon part="caret" class="caret" library="system" name="chevron-down" variant="solid"></wa-icon>
              `:""}
        ${this.loading?Js`<wa-spinner part="spinner"></wa-spinner>`:""}
      </${e}>
    `}};K.shadowRootOptions={...qe.shadowRootOptions,delegatesFocus:!0};K.css=[hf,cf,Ma];v([Q(".button")],K.prototype,"button",2);v([Q("slot:not([name])")],K.prototype,"labelSlot",2);v([be()],K.prototype,"invalid",2);v([be()],K.prototype,"isIconButton",2);v([y()],K.prototype,"title",2);v([y({reflect:!0})],K.prototype,"variant",2);v([y({reflect:!0})],K.prototype,"appearance",2);v([y({reflect:!0})],K.prototype,"size",2);v([y({attribute:"with-caret",type:Boolean,reflect:!0})],K.prototype,"withCaret",2);v([y({type:Boolean})],K.prototype,"disabled",2);v([y({type:Boolean,reflect:!0})],K.prototype,"loading",2);v([y({type:Boolean,reflect:!0})],K.prototype,"pill",2);v([y()],K.prototype,"type",2);v([y({reflect:!0})],K.prototype,"name",2);v([y({reflect:!0})],K.prototype,"value",2);v([y({reflect:!0})],K.prototype,"href",2);v([y()],K.prototype,"target",2);v([y()],K.prototype,"rel",2);v([y()],K.prototype,"download",2);v([y({reflect:!0})],K.prototype,"form",2);v([y({attribute:"formaction"})],K.prototype,"formAction",2);v([y({attribute:"formenctype"})],K.prototype,"formEnctype",2);v([y({attribute:"formmethod"})],K.prototype,"formMethod",2);v([y({attribute:"formnovalidate",type:Boolean})],K.prototype,"formNoValidate",2);v([y({attribute:"formtarget"})],K.prototype,"formTarget",2);v([Ee("disabled",{waitUntilFirstUpdate:!0})],K.prototype,"handleDisabledChange",1);K=v([ke("wa-button")],K);var pf=`:host {
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
`,En=class extends ge{constructor(){super(...arguments),this.localize=new Rt(this)}render(){return x`
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
    `}};En.css=pf;En=v([ke("wa-spinner")],En);var ff=class extends Ce{static get styles(){return[Ce.styles,O`
        :host {
          --wa-color-surface-raised: var(--c-bg-raised);
          --spacing: var(--c-spacing-lg);
          background-color: red;
        }
      `]}};customElements.get("craft-drawer")||customElements.define("craft-drawer",ff);var mf=`:host {
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
`,$e=class extends ge{constructor(){super(...arguments),this.localize=new Rt(this),this.hasSlotController=new As(this,"footer","header-actions","label"),this.open=!1,this.label="",this.withoutHeader=!1,this.lightDismiss=!1,this.handleDocumentKeyDown=i=>{i.key==="Escape"&&this.open&&(i.preventDefault(),i.stopPropagation(),this.requestClose(this.dialog))}}firstUpdated(){this.open&&(this.addOpenListeners(),this.dialog.showModal(),os(this))}disconnectedCallback(){super.disconnectedCallback(),rs(this),this.removeOpenListeners()}async requestClose(i){const e=new gi({source:i});if(this.dispatchEvent(e),e.defaultPrevented){this.open=!0,de(this.dialog,"pulse");return}this.removeOpenListeners(),await de(this.dialog,"hide"),this.open=!1,this.dialog.close(),rs(this);const t=this.originalTrigger;typeof t?.focus=="function"&&setTimeout(()=>t.focus()),this.dispatchEvent(new mi)}addOpenListeners(){document.addEventListener("keydown",this.handleDocumentKeyDown)}removeOpenListeners(){document.removeEventListener("keydown",this.handleDocumentKeyDown)}handleDialogCancel(i){i.preventDefault(),!this.dialog.classList.contains("hide")&&i.target===this.dialog&&this.requestClose(this.dialog)}handleDialogClick(i){const t=i.target.closest('[data-dialog="close"]');t&&(i.stopPropagation(),this.requestClose(t))}async handleDialogPointerDown(i){i.target===this.dialog&&(this.lightDismiss?this.requestClose(this.dialog):await de(this.dialog,"pulse"))}handleOpenChange(){this.open&&!this.dialog.open?this.show():!this.open&&this.dialog.open&&(this.open=!0,this.requestClose(this.dialog))}async show(){const i=new _i;if(this.dispatchEvent(i),i.defaultPrevented){this.open=!1;return}this.addOpenListeners(),this.originalTrigger=document.activeElement,this.open=!0,this.dialog.showModal(),os(this),requestAnimationFrame(()=>{const e=this.querySelector("[autofocus]");e&&typeof e.focus=="function"?e.focus():this.dialog.focus()}),await de(this.dialog,"show"),this.dispatchEvent(new bi)}render(){const i=!this.withoutHeader,e=this.hasSlotController.test("footer");return x`
      <dialog
        part="dialog"
        class=${Fe({dialog:!0,open:this.open})}
        @cancel=${this.handleDialogCancel}
        @click=${this.handleDialogClick}
        @pointerdown=${this.handleDialogPointerDown}
      >
        ${i?x`
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
    `}};$e.css=mf;v([Q(".dialog")],$e.prototype,"dialog",2);v([y({type:Boolean,reflect:!0})],$e.prototype,"open",2);v([y({reflect:!0})],$e.prototype,"label",2);v([y({attribute:"without-header",type:Boolean,reflect:!0})],$e.prototype,"withoutHeader",2);v([y({attribute:"light-dismiss",type:Boolean})],$e.prototype,"lightDismiss",2);v([Ee("open",{waitUntilFirstUpdate:!0})],$e.prototype,"handleOpenChange",1);$e=v([ke("wa-dialog")],$e);document.addEventListener("click",i=>{const e=i.target.closest("[data-dialog]");if(e instanceof Element){const[t,s]=Ua(e.getAttribute("data-dialog")||"");if(t==="open"&&s?.length){const o=e.getRootNode().getElementById(s);o?.localName==="wa-dialog"?o.open=!0:console.warn(`A dialog with an ID of "${s}" could not be found in this document.`)}}});document.addEventListener("pointerdown",()=>{});var bf=class extends $e{static get styles(){return[$e.styles,O`
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
      `]}};customElements.get("craft-dialog")||customElements.define("craft-dialog",bf);class br extends to(Jh(H)){constructor(){super(),this.multipleChoice=!0}}class gr extends io(so){connectedCallback(){super.connectedCallback(),this.type="checkbox"}}var gf=class extends br{static get styles(){return[...br.styles,O`
        .form-field__group-two {
          margin-top: var(--c-spacing-sm);
        }

        ::slotted(label) {
          font-weight: bold;
        }
      `]}};customElements.get("craft-checkbox-group")||customElements.define("craft-checkbox-group",gf);var _f=class extends gr{static get styles(){return[...gr.styles,O`
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
      `]}};customElements.get("craft-checkbox")||customElements.define("craft-checkbox",_f);const Ae={Default:"default",Success:"success",Warning:"warning",Danger:"danger",Info:"info"},vf={OutlineFill:"outline-fill"};var no=O`
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
`,yf=O`
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

  :host([]) :host([appearance~='accent']) {
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
`,ot=class extends H{constructor(...i){super(...i),this.variant=Ae.Default,this.appearance=vf.OutlineFill,this.title="",this.icon=null,this.rounded="all"}getDefaultIcon(){switch(this.variant){case Ae.Info:return"lightbulb";case Ae.Success:return"check-circle";case Ae.Warning:return"exclamation-circle";case Ae.Danger:return"exclamation-triangle";default:return null}}render(){return x`
      ${this.icon||this.querySelector('[slot="icon"]')?x`<slot name="icon" class="callout__icon">
            <craft-icon
              name="${this.getDefaultIcon()}"
              style="font-size: 0.9em"
            ></craft-icon>
          </slot>`:B}
      <div class="callout__body">
        <slot name="title" class="callout__title">${this.title}</slot>
        <div class="callout__description">
          <slot></slot>
        </div>
      </div>
    `}};ot.styles=[no,yf],L([y({reflect:!0})],ot.prototype,"variant",void 0),L([y({reflect:!0})],ot.prototype,"appearance",void 0),L([y()],ot.prototype,"title",void 0),L([y()],ot.prototype,"icon",void 0),L([y({reflect:!0})],ot.prototype,"rounded",void 0),customElements.get("craft-callout")||customElements.define("craft-callout",ot);var wf=O`
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
`,vt=class extends H{constructor(...e){super(...e),this.icon=null,this.href=null,this.disabled=!1,this.variant=Ae.Default}renderBody(){return x`
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
        `}};vt.styles=[no,wf],L([y()],vt.prototype,"icon",void 0),L([y()],vt.prototype,"href",void 0),L([y({type:Boolean})],vt.prototype,"disabled",void 0),L([y()],vt.prototype,"variant",void 0),customElements.get("craft-action-item")||customElements.define("craft-action-item",vt);const xf=O`
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
`;class Ue{static __createGlobalStyleNode(){const e=document.createElement("style");return e.setAttribute("data-overlays",""),e.textContent=xf.cssText,document.head.appendChild(e),e}get list(){return this.__list}get shownList(){return this.__shownList}constructor(){this.__list=[],this.__shownList=[],this.__siblingsInert=!1,this.__blockingMap=new WeakMap,Ue.__globalStyleNode||(Ue.__globalStyleNode=Ue.__createGlobalStyleNode())}add(e){if(this.list.find(t=>e===t))throw new Error("controller instance is already added");return this.list.push(e),e}remove(e){if(!this.list.find(t=>e===t))throw new Error("could not find controller to remove");this.__list=this.list.filter(t=>t!==e),this.__shownList=this.shownList.filter(t=>t!==e)}show(e){this.list.find(t=>e===t)&&this.hide(e),this.__shownList.unshift(e),Array.from(this.__shownList).reverse().forEach((t,s)=>{t.elevation=s+1})}hide(e){if(!this.list.find(t=>e===t))throw new Error("could not find controller to hide");this.__shownList=this.shownList.filter(t=>t!==e)}teardown(){this.list.forEach(e=>{e.teardown()}),this.__list=[],this.__shownList=[],this.__siblingsInert=!1,Ue.__globalStyleNode&&(document.head.removeChild(Ue.__globalStyleNode),Ue.__globalStyleNode=void 0)}get siblingsInert(){return this.__siblingsInert}disableTrapsKeyboardFocusForAll(){this.shownList.forEach(e=>{e.trapsKeyboardFocus===!0&&e.disableTrapsKeyboardFocus&&e.disableTrapsKeyboardFocus({findNewTrap:!1})})}informTrapsKeyboardFocusGotEnabled(e){this.siblingsInert===!1&&e==="global"&&(this.__siblingsInert=!0)}informTrapsKeyboardFocusGotDisabled({disabledCtrl:e,findNewTrap:t=!0}={}){const s=this.shownList.find(n=>n!==e&&n.trapsKeyboardFocus===!0);s?t&&s.enableTrapsKeyboardFocus():this.siblingsInert===!0&&(this.__siblingsInert=!1)}requestToPreventScroll(){const{isIOS:e,isMacSafari:t}=is;document.body.classList.add("overlays-scroll-lock"),(e||t)&&document.body.classList.add("overlays-scroll-lock-ios-fix"),e&&document.documentElement.classList.add("overlays-scroll-lock-ios-fix")}requestToEnableScroll(){if(this.shownList.some(n=>n.preventsScroll===!0))return;const{isIOS:t,isMacSafari:s}=is;document.body.classList.remove("overlays-scroll-lock"),(t||s)&&document.body.classList.remove("overlays-scroll-lock-ios-fix"),t&&document.documentElement.classList.remove("overlays-scroll-lock-ios-fix")}requestToShowOnly(e){const t=this.shownList.filter(s=>s!==e);t.forEach(s=>s.hide()),this.__blockingMap.set(e,t)}retractRequestToShowOnly(e){this.__blockingMap.has(e)&&this.__blockingMap.get(e).forEach(s=>s.show())}}Ue.__globalStyleNode=void 0;const Ef=qi.get("@lion/ui::overlays::0.x")||new Ue;function Cn(){let i=document.activeElement||document.body;for(;i&&i.shadowRoot&&i.shadowRoot.activeElement;)i=i.shadowRoot.activeElement;return i}const _r=({visibility:i,display:e})=>i!=="hidden"&&e!=="none",Cf=({display:i})=>i==="contents";function kf(i){if(!i||!i.isConnected||!_r(i.style))return!1;const e=window.getComputedStyle(i);return _r(e)?Cf(e)?!0:!!(i.offsetWidth||i.offsetHeight||i.getClientRects().length):!1}function Sf(i,e){const t=Math.max(i.tabIndex,0),s=Math.max(e.tabIndex,0);return t===0||s===0?s>t:t>s}function Af(i,e){const t=[];for(;i.length>0&&e.length>0;)Sf(i[0],e[0])?t.push(e.shift()):t.push(i.shift());return[...t,...i,...e]}function kn(i){const e=i.length;if(e<2)return i;const t=Math.ceil(e/2),s=kn(i.slice(0,t)),n=kn(i.slice(t));return Af(s,n)}const Qs="matches"in Element.prototype?"matches":"msMatchesSelector";function Tf(i){return i[Qs]("input, select, textarea, button, object")?i[Qs](":not([disabled])"):i[Qs]("a[href], area[href], iframe, [tabindex], [contentEditable]")}function Nf(i){return Tf(i)?Number(i.getAttribute("tabindex")||0):-1}function Of(i){if(i.localName==="slot")return i.assignedNodes({flatten:!0});const{children:e}=i.shadowRoot||i;return e||[]}function Ff(i){return i.nodeType!==Node.ELEMENT_NODE?!1:i.localName==="slot"?!0:kf(i)}function ja(i,e){if(!Ff(i))return!1;const t=i,s=Nf(t);let n=s>0;s>=0&&e.push(t);const o=Of(t);for(let r=0;r<o.length;r+=1)n=ja(o[r],e)||n;return n}function Wa(i){const e=[];return ja(i,e)?kn(e):e}function Ot(i,e,t={}){function s(b){return"getAttribute"in b}function n(b){if(!s(b))return null;const p=b.getAttribute("slot");let g=null;if(p){const _=t[p];_&&(g=_.filter(E=>E?.element===b)[0]||null)}return g}const o=n(i);if(o)return o.deepContains;function r(b){if(!s(i))return;const p=i.getAttribute("slot");p&&(t[p]=t[p]||[],t[p].push({element:i,deepContains:b}))}let a=i.contains(e);if(a)return r(!0),!0;function l(b){return b.tagName==="SLOT"}function d(b){return l(b)?b.assignedElements():[]}function c(b){return b.nodeType===Node.DOCUMENT_FRAGMENT_NODE}function h(b){let p=!1;for(let g=0;g<b.length;g+=1){const _=b[g];if(_&&(s(_)||c(_))&&Ot(_,e,t)){p=!0;break}}return p}function m(b){for(let p=0;p<b.children.length;p+=1){const g=b.children[p],_=n(g);if(_){a=_.deepContains||a;break}const E=d(g),k=[g.shadowRoot,...E];if(h(k)){a=!0;break}g.children.length>0&&m(g)}}return i instanceof HTMLElement&&i.shadowRoot&&(a=Ot(i.shadowRoot,e,t),a)?(r(!0),!0):(m(i),r(a),a)}const Lf={tab:9};function $f(i,e){const t=Wa(i);let s;t.length>=2?s=[t[0],t[t.length-1]]:t.length===1?s=[t[0],t[0]]:s=[i,i],e.shiftKey&&s.reverse();const[n,o]=s,r=Cn();r===i||t.includes(r)&&o!==r||(e.preventDefault(),n.focus())}function Rf(i){const e=Wa(i),t=e.find(m=>m.hasAttribute("autofocus"))||i;let s,n;t===i&&(i.tabIndex=-1,i.style.setProperty("outline","none")),t.focus();function o(m){m.keyCode===Lf.tab&&$f(i,m)}function r(){s=document.createElement("div"),s.style.display="none",s.setAttribute("data-is-tab-detection-element",""),i.insertBefore(s,i.children[0]),n=new MutationObserver(m=>{for(const b of m)if(b.type==="childList"){const p=!Array.from(i.children).find(_=>_.hasAttribute("data-is-tab-detection-element")),g=Array.from(b.addedNodes).find(_=>_ instanceof HTMLElement&&_.hasAttribute("data-is-tab-detection-element"));p&&!g&&(n.disconnect(),r())}}),n.observe(i,{childList:!0})}function a(){return s.compareDocumentPosition(document.activeElement)===Node.DOCUMENT_POSITION_PRECEDING}function l({resetToRoot:m=!1}={}){if(Ot(i,Cn()))return;let b;m?b=i:b=e[a()?0:e.length-1],b&&b.focus()}function d(){window.removeEventListener("focusin",d),l()}function c(){setTimeout(()=>{Ot(i,Cn())||l({resetToRoot:!0})}),window.addEventListener("focusin",d)}function h(){window.removeEventListener("keydown",o),window.removeEventListener("focusin",d),window.removeEventListener("focusout",c),n.disconnect(),Array.from(i.children).includes(s)&&i.removeChild(s),i.style.removeProperty("outline")}return window.addEventListener("keydown",o),window.addEventListener("focusout",c),r(),{disconnect:h}}const vr=O`
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
`,Ft={supportsAdoptingStyleSheets:window.ShadowRoot&&(window.ShadyCSS===void 0||window.ShadyCSS.nativeShadow)&&"adoptedStyleSheets"in Document.prototype&&"replace"in CSSStyleSheet.prototype,adoptStyle:void 0,adoptStyles:void 0},en=new WeakMap;function If(i){return Array.from(i.cssRules).map(e=>e.cssText).join("")}function Df(i,e,{teardown:t=!1}={}){const s=i===document?document.body:i,n=e.cssText||If(e);if(t){const o=Array.from(s.querySelectorAll("style"));for(const r of o)if(r.textContent===n){r.remove();break}}else{const o=document.createElement("style"),r=window.litNonce;r!==void 0&&o.setAttribute("nonce",r),o.textContent=n,s.appendChild(o)}}function Mf(i,e,{teardown:t=!1}={}){let s=!1;i&&!en.has(i)&&en.set(i,[]);const n=en.get(i)??[],o=n.find(r=>e===r);return o&&t?n.splice(n.indexOf(e),1):!o&&!t?n.push(e):(o&&!t||!o&&t)&&(s=!0),{haltFurtherExecution:s}}function Vf(i,e,{teardown:t=!1}={}){const{haltFurtherExecution:s}=Mf(i,e,{teardown:t});if(s)return;if(!Ft.supportsAdoptingStyleSheets||is.isIOS){Df(i,e,{teardown:t});return}const n=e instanceof CSSStyleSheet?e:e.styleSheet;if(!n)throw new Error("Please provide a CSSResultOrNative style");t?i.adoptedStyleSheets.includes(n)&&i.adoptedStyleSheets.splice(i.adoptedStyleSheets.indexOf(n),1):i.adoptedStyleSheets=[...i.adoptedStyleSheets,n]}function Pf(i,e,{teardown:t=!1}={}){for(const s of e)Ft.adoptStyle(i,s,{teardown:t})}Ft.adoptStyle=Vf;Ft.adoptStyles=Pf;function zf({wrappingDialogNodeL1:i,contentWrapperNodeL2:e,contentNodeL3:t}){if(!(e.isConnected||t.isConnected))throw new Error('[OverlayController] Could not find a render target, since the provided contentNode is not connected to the DOM. Make sure that it is connected, e.g. by doing "document.body.appendChild(contentNode)", before passing it on.');let s;const n=document.createComment("tempMarker");e.isConnected?(s=e.parentElement||e.getRootNode(),s.insertBefore(n,e),i.appendChild(e)):t.assignedSlot?(s=t.assignedSlot.parentElement||t.assignedSlot.getRootNode(),s.insertBefore(n,t.assignedSlot),i.appendChild(e),e.appendChild(t.assignedSlot)):(s=t.parentElement||t.getRootNode(),s.insertBefore(n,t),i.appendChild(e),e.appendChild(t)),s.insertBefore(i,n),s?.removeChild(n)}async function Bf(){return C(()=>import("./popper.js"),[],import.meta.url)}const yr=new WeakMap;class ct extends EventTarget{constructor(e={},t=Ef){super(),this.manager=t,this.__sharedConfig=e,this.__activeElementRightBeforeHide=null,this.config={},this._defaultConfig={placementMode:void 0,contentNode:e.contentNode,contentWrapperNode:e.contentWrapperNode,invokerNode:e.invokerNode,backdropNode:e.backdropNode,referenceNode:void 0,elementToFocusAfterHide:e.invokerNode,inheritsReferenceWidth:"none",hasBackdrop:!1,isBlocking:!1,preventsScroll:!1,trapsKeyboardFocus:!1,hidesOnEsc:!1,hidesOnOutsideEsc:!1,hidesOnOutsideClick:!1,isTooltip:!1,isAlertDialog:!1,invokerRelation:"description",visibilityTriggerFunction:void 0,handlesAccessibility:!1,popperConfig:{placement:"top",strategy:"fixed",modifiers:[{name:"preventOverflow",enabled:!0,options:{boundariesElement:"viewport",padding:8}},{name:"flip",options:{boundariesElement:"viewport",padding:16}},{name:"offset",enabled:!0,options:{offset:[0,8]}},{name:"arrow",enabled:!1}]},viewportConfig:{placement:"center"},zIndex:9999},this._contentId=`overlay-content--${Math.random().toString(36).slice(2,10)}`,this.__originalAttrs=new Map,this.updateConfig(e),this.__hasActiveTrapsKeyboardFocus=!1,this.__hasActiveBackdrop=!0,this.__escKeyHandler=this.__escKeyHandler.bind(this),this.__cancelHandler=this.__cancelHandler.bind(this)}get invoker(){return this.invokerNode}get content(){return this.__wrappingDialogNode}get placementMode(){return this.config?.placementMode}get invokerNode(){return this.config?.invokerNode}get referenceNode(){return this.config?.referenceNode}get contentNode(){return this.config?.contentNode}get contentWrapperNode(){return this.__contentWrapperNode||this.config?.contentWrapperNode}get backdropNode(){return this.__backdropNode||this.config?.backdropNode}get elementToFocusAfterHide(){return this.__elementToFocusAfterHide||this.config?.elementToFocusAfterHide}get hasBackdrop(){return!!this.backdropNode||this.config?.hasBackdrop}get isBlocking(){return this.config?.isBlocking}get preventsScroll(){return this.config?.preventsScroll}get trapsKeyboardFocus(){return this.config?.trapsKeyboardFocus}get hidesOnEsc(){return this.config?.hidesOnEsc}get hidesOnOutsideClick(){return this.config?.hidesOnOutsideClick}get hidesOnOutsideEsc(){return this.config?.hidesOnOutsideEsc}get inheritsReferenceWidth(){return this.config?.inheritsReferenceWidth}get handlesAccessibility(){return this.config?.handlesAccessibility}get isTooltip(){return this.config?.isTooltip}get isAlertDialog(){return this.config?.isAlertDialog}get invokerRelation(){return this.config?.invokerRelation}get popperConfig(){return this.config?.popperConfig}get viewportConfig(){return this.config?.viewportConfig}get visibilityTriggerFunction(){return this.config?.visibilityTriggerFunction}get _referenceNode(){return this.referenceNode||this.invokerNode}set elevation(e){this.__wrappingDialogNode.style.zIndex=`${this.config.zIndex+e}`}get elevation(){return Number(this.contentWrapperNode?.style.zIndex)}updateConfig(e){this.teardown(),this.__prevConfig=this.config,this.config={...this._defaultConfig,...this.__sharedConfig,...e,popperConfig:{...this._defaultConfig.popperConfig||{},...this.__sharedConfig.popperConfig||{},...e.popperConfig||{},modifiers:[...this._defaultConfig.popperConfig?.modifiers||[],...this.__sharedConfig.popperConfig?.modifiers||[],...e.popperConfig?.modifiers||[]]}},this.__validateConfiguration(this.config),this._init(),this.__elementToFocusAfterHide=void 0,this.#e()||this.manager.add(this)}#e(){return!!this.manager.list.find(e=>this===e)}__validateConfiguration(e){if(!e.placementMode)throw new Error('[OverlayController] You need to provide a .placementMode ("global"|"local")');if(!["global","local"].includes(e.placementMode))throw new Error(`[OverlayController] "${e.placementMode}" is not a valid .placementMode, use ("global"|"local")`);if(!e.contentNode)throw new Error("[OverlayController] You need to provide a .contentNode");if(e.isTooltip&&!e.handlesAccessibility)throw new Error("[OverlayController] .isTooltip only takes effect when .handlesAccessibility is enabled")}_init(){this.__contentHasBeenInitialized||(this.__initContentDomStructure(),this.__contentHasBeenInitialized=!0),this.contentWrapperNode.removeAttribute("style"),this.contentWrapperNode.removeAttribute("class"),this.placementMode==="local"&&(ct.popperModule||(ct.popperModule=Bf())),this.__handleOverlayStyles({phase:"init"}),this._handleFeatures({phase:"init"})}__handleOverlayStyles({phase:e}){const t=this.contentWrapperNode?.getRootNode();e==="init"?Ft.adoptStyle(t,vr):e==="teardown"&&Ft.adoptStyle(t,vr,{teardown:!0})}__initContentDomStructure(){const e=document.createElement(this.config?._noDialogEl?"div":"dialog");e.setAttribute("role","none"),e.setAttribute("data-overlay-outer-wrapper",""),e.style.cssText=`display:none; z-index: ${this.config.zIndex}; padding: 0;`,this.__wrappingDialogNode=e,this.config?.contentWrapperNode||(this.__contentWrapperNode=document.createElement("div")),this.contentWrapperNode.setAttribute("data-id","content-wrapper"),zf({wrappingDialogNodeL1:e,contentWrapperNodeL2:this.contentWrapperNode,contentNodeL3:this.contentNode}),e.open=!0,this.isTooltip&&e.setAttribute("tabindex","-1"),this.__wrappingDialogNode.style.display="none",this.contentWrapperNode.style.zIndex="1",getComputedStyle(this.contentNode).position==="absolute"&&(this.contentNode.style.position="static"),HTMLDialogElement&&"closedBy"in HTMLDialogElement.prototype?e.closedBy="none":(e.addEventListener("keydown",s=>{s.key==="Escape"&&s.preventDefault()}),e.addEventListener("keyup",s=>{s.key==="Escape"&&s.preventDefault()}),e.addEventListener("cancel",s=>{s.stopPropagation()}),e.addEventListener("close",s=>{s.stopPropagation()}))}_handleZIndex({phase:e}){if(this.placementMode==="local"&&e==="setup"){const t=Number(getComputedStyle(this.contentNode).zIndex);(t<1||Number.isNaN(t))&&(this.contentNode.style.zIndex="1")}}__setupTeardownAccessibility({phase:e}){if(e==="init"){this.__storeOriginalAttrs(this.contentNode,["role","id"]);const t=this.trapsKeyboardFocus;if(this.invokerNode){const s=["aria-labelledby","aria-describedby"];t||s.push("aria-expanded"),this.__storeOriginalAttrs(this.invokerNode,s)}this.contentNode.id||this.contentNode.setAttribute("id",this._contentId),this.isTooltip?(this.invokerNode&&this.invokerNode.setAttribute(this.invokerRelation==="label"?"aria-labelledby":"aria-describedby",this._contentId),this.contentNode.setAttribute("role","tooltip")):(this.invokerNode&&!t&&this.invokerNode.setAttribute("aria-expanded",`${this.isShown}`),this.isAlertDialog?this.contentNode.setAttribute("role","alertdialog"):this.contentNode.getAttribute("role")||this.contentNode.setAttribute("role","dialog"))}else e==="teardown"&&this.__restoreOriginalAttrs()}__storeOriginalAttrs(e,t){const s={};t.forEach(n=>{s[n]=e.getAttribute(n)}),this.__originalAttrs.set(e,s)}__restoreOriginalAttrs(){for(const[e,t]of this.__originalAttrs)Object.entries(t).forEach(([s,n])=>{n!==null?e.setAttribute(s,n):e.removeAttribute(s)});this.__originalAttrs.clear()}get isShown(){return this.__wrappingDialogNode?.style.display!=="none"}async show(e=this.elementToFocusAfterHide){if(this._showComplete&&await this._showComplete,this._showComplete=new Promise(s=>{this._showResolve=s}),this.manager&&this.manager.show(this),this.isShown){this._showResolve();return}const t=new CustomEvent("before-show",{cancelable:!0});this.dispatchEvent(t),t.defaultPrevented||("HTMLDialogElement"in window&&this.__wrappingDialogNode instanceof HTMLDialogElement&&(this.__wrappingDialogNode.open=!0),this.__wrappingDialogNode.style.display="",this._keepBodySize({phase:"before-show"}),await this._handleFeatures({phase:"show"}),this._keepBodySize({phase:"show"}),await this._handlePosition({phase:"show"}),this.__elementToFocusAfterHide=e,this.dispatchEvent(new Event("show")),await this._transitionShow({backdropNode:this.backdropNode,contentNode:this.contentNode})),this._showResolve()}async _handlePosition({phase:e}){if(this.placementMode==="global"){const t=`overlays__overlay-container--${this.viewportConfig.placement}`;e==="show"?(this.contentWrapperNode.classList.add("overlays__overlay-container"),this.contentWrapperNode.classList.add(t),this.contentNode.classList.add("overlays__overlay")):e==="hide"&&(this.contentWrapperNode.classList.remove("overlays__overlay-container"),this.contentWrapperNode.classList.remove(t),this.contentNode.classList.remove("overlays__overlay"))}else this.placementMode==="local"&&e==="show"&&(await this.__createPopperInstance(),this._popper.forceUpdate())}_keepBodySize({phase:e}){if(this.preventsScroll)switch(e){case"before-show":this.__bodyClientWidth=document.body.clientWidth,this.__bodyClientHeight=document.body.clientHeight,this.__bodyMarginRightInline=document.body.style.marginRight,this.__bodyMarginBottomInline=document.body.style.marginBottom;break;case"show":{if(window.getComputedStyle){const r=window.getComputedStyle(document.body);this.__bodyMarginRight=parseInt(r.getPropertyValue("margin-right"),10),this.__bodyMarginBottom=parseInt(r.getPropertyValue("margin-bottom"),10)}else this.__bodyMarginRight=0,this.__bodyMarginBottom=0;const t=document.body.clientWidth-this.__bodyClientWidth,s=document.body.clientHeight-this.__bodyClientHeight,n=this.__bodyMarginRight+t,o=this.__bodyMarginBottom+s;window.CSS?.number&&document.body.attributeStyleMap?.set?(document.body.attributeStyleMap.set("margin-right",CSS.px(n)),document.body.attributeStyleMap.set("margin-bottom",CSS.px(o))):(document.body.style.marginRight=`${n}px`,document.body.style.marginBottom=`${o}px`);break}case"hide":document.body.style.marginRight=this.__bodyMarginRightInline||"",document.body.style.marginBottom=this.__bodyMarginBottomInline||"";break}}async hide(){if(this._hideComplete=new Promise(t=>{this._hideResolve=t}),this.__activeElementRightBeforeHide=this.contentNode.getRootNode().activeElement,this.manager&&this.manager.hide(this),!this.isShown){this._hideResolve();return}const e=new CustomEvent("before-hide",{cancelable:!0});this.dispatchEvent(e),e.defaultPrevented||(await this._transitionHide({backdropNode:this.backdropNode,contentNode:this.contentNode}),"HTMLDialogElement"in window&&this.__wrappingDialogNode instanceof HTMLDialogElement&&this.__wrappingDialogNode.close(),this.__wrappingDialogNode.style.display="none",this._handleFeatures({phase:"hide"}),this._keepBodySize({phase:"hide"}),this.dispatchEvent(new Event("hide")),this._restoreFocus()),this._hideResolve()}async transitionHide(e){}async _transitionHide({backdropNode:e,contentNode:t}){await this.transitionHide({backdropNode:e,contentNode:t}),this._handlePosition({phase:"hide"}),e&&e.classList.remove("overlays__backdrop--animation-in")}async transitionShow(e){}async _transitionShow(e){await this.transitionShow({backdropNode:this.backdropNode,contentNode:this.contentNode}),e.backdropNode&&e.backdropNode.classList.add("overlays__backdrop--animation-in")}_restoreFocus(){this.__activeElementRightBeforeHide instanceof HTMLElement&&this.contentNode.contains(this.__activeElementRightBeforeHide)&&(this.elementToFocusAfterHide instanceof HTMLElement?(this.elementToFocusAfterHide.focus(),this.elementToFocusAfterHide.scrollIntoView({block:"nearest"})):this.__activeElementRightBeforeHide.blur())}async toggle(){return this.isShown?this.hide():this.show()}_handleFeatures({phase:e}){this._handleZIndex({phase:e}),this.preventsScroll&&this._handlePreventsScroll({phase:e}),this.isBlocking&&this._handleBlocking({phase:e}),this.hasBackdrop&&this._handleBackdrop({phase:e}),this.trapsKeyboardFocus&&this._handleTrapsKeyboardFocus({phase:e}),this.hidesOnEsc&&this._handleHidesOnEsc({phase:e}),this.hidesOnOutsideEsc&&this._handleHidesOnOutsideEsc({phase:e}),this.hidesOnOutsideClick&&this._handleHidesOnOutsideClick({phase:e}),this.handlesAccessibility&&this._handleAccessibility({phase:e}),this.inheritsReferenceWidth&&this._handleInheritsReferenceWidth(),this.visibilityTriggerFunction&&this._handleVisibilityTriggers({phase:e})}_handleVisibilityTriggers({phase:e}){typeof this.visibilityTriggerFunction=="function"&&(e==="init"&&(this.__visibilityTriggerHandler=this.visibilityTriggerFunction({phase:e,controller:this})),this.__visibilityTriggerHandler[e]&&this.__visibilityTriggerHandler[e]())}_handlePreventsScroll({phase:e}){switch(e){case"show":this.manager.requestToPreventScroll();break;case"hide":this.manager.requestToEnableScroll();break}}_handleBlocking({phase:e}){switch(e){case"show":this.manager.requestToShowOnly(this);break;case"hide":this.manager.retractRequestToShowOnly(this);break}}get hasActiveBackdrop(){return this.__hasActiveBackdrop}_handleBackdrop({phase:e}){switch(e){case"init":{this.__backdropInitialized||(this.config?.backdropNode||(this.__backdropNode=document.createElement("div"),this.__backdropNode.classList.add("overlays__backdrop")),this.__wrappingDialogNode.prepend(this.backdropNode),this.__backdropInitialized=!0);break}case"show":this.config.hasBackdrop&&this.backdropNode.classList.add("overlays__backdrop--visible"),this.__hasActiveBackdrop=!0;break;case"hide":case"teardown":this.backdropNode.classList.remove("overlays__backdrop--visible"),this.__hasActiveBackdrop=!1;break}}get hasActiveTrapsKeyboardFocus(){return this.__hasActiveTrapsKeyboardFocus}_handleTrapsKeyboardFocus({phase:e}){e==="show"?("showModal"in this.__wrappingDialogNode&&(this.__wrappingDialogNode.close(),this.__wrappingDialogNode.showModal()),this.enableTrapsKeyboardFocus()):(e==="hide"||e==="teardown")&&this.disableTrapsKeyboardFocus()}enableTrapsKeyboardFocus(){if(this.__hasActiveTrapsKeyboardFocus)return;this.manager&&this.manager.disableTrapsKeyboardFocusForAll(),!!this.contentNode.shadowRoot&&console.warn("[overlays]: For best accessibility (compatibility with Safari + VoiceOver), provide a contentNode that is not a host for a shadow root"),this._containFocusHandler=Rf(this.contentNode),this.__hasActiveTrapsKeyboardFocus=!0,this.manager&&this.manager.informTrapsKeyboardFocusGotEnabled(this.placementMode)}disableTrapsKeyboardFocus({findNewTrap:e=!0}={}){this.__hasActiveTrapsKeyboardFocus&&(this._containFocusHandler&&(this._containFocusHandler.disconnect(),this._containFocusHandler=void 0),this.__hasActiveTrapsKeyboardFocus=!1,this.manager&&this.manager.informTrapsKeyboardFocusGotDisabled({disabledCtrl:this,findNewTrap:e}))}__cancelHandler(e){e.preventDefault()}__escKeyHandler(e){if(e.key!=="Escape"||yr.has(e))return;(e.composedPath().includes(this.contentNode)||Ot(this.contentNode,e.target))&&(this.hide(),yr.set(e,this))}#t=e=>{e.key!=="Escape"||e.composedPath().includes(this.contentNode)||Ot(this.contentNode,e.target)||this.hide()};_handleHidesOnEsc({phase:e}){e==="show"?(this.contentNode.addEventListener("keyup",this.__escKeyHandler),this.invokerNode&&this.invokerNode.addEventListener("keyup",this.__escKeyHandler)):(e==="hide"||e==="teardown")&&(this.contentNode.removeEventListener("keyup",this.__escKeyHandler),this.invokerNode&&this.invokerNode.removeEventListener("keyup",this.__escKeyHandler))}_handleHidesOnOutsideEsc({phase:e}){e==="show"?document.addEventListener("keyup",this.#t):(e==="hide"||e==="teardown")&&document.removeEventListener("keyup",this.#t)}_handleInheritsReferenceWidth(){if(!this._referenceNode||this.placementMode==="global")return;const e=`${this._referenceNode.getBoundingClientRect().width}px`;switch(this.inheritsReferenceWidth){case"max":this.contentWrapperNode.style.maxWidth=e;break;case"full":this.contentWrapperNode.style.width=e;break;case"min":this.contentWrapperNode.style.minWidth=e,this.contentWrapperNode.style.width="auto";break}}_handleHidesOnOutsideClick({phase:e}){const t=e==="show"?"addEventListener":"removeEventListener";if(e==="show"){let s=!1,n=!1;this.__onInsideMouseDown=()=>{s=!0},this.__onInsideMouseUp=()=>{n=!0},this.__onDocumentMouseUp=()=>{setTimeout(()=>{!s&&!n&&this.hide(),s=!1,n=!1})},this.__onWindowBlur=()=>{setTimeout(()=>{this.hide()})}}this.contentWrapperNode[t]("mousedown",this.__onInsideMouseDown,!0),this.contentWrapperNode[t]("mouseup",this.__onInsideMouseUp,!0),this.invokerNode&&(this.invokerNode[t]("mousedown",this.__onInsideMouseDown,!0),this.invokerNode[t]("mouseup",this.__onInsideMouseUp,!0)),document.documentElement[t]("mouseup",this.__onDocumentMouseUp,!0),window[t]("blur",this.__onWindowBlur)}_handleAccessibility({phase:e}){(e==="init"||e==="teardown")&&this.__setupTeardownAccessibility({phase:e});const t=this.trapsKeyboardFocus;this.invokerNode&&!this.isTooltip&&!t&&this.invokerNode.setAttribute("aria-expanded",`${e==="show"}`)}teardown(){this.__handleOverlayStyles({phase:"teardown"}),this._handleFeatures({phase:"teardown"}),this.#e()&&this.manager.remove(this)}async __createPopperInstance(){if(this._popper&&(this._popper.destroy(),this._popper=void 0),ct.popperModule!==void 0){const{createPopper:e}=await ct.popperModule;this._popper=e(this._referenceNode,this.contentWrapperNode,{...this.config?.popperConfig})}}_hasDisabledInvoker(){return this.invokerNode?this.invokerNode.disabled||this.invokerNode.getAttribute("aria-disabled")==="true":!1}}ct.popperModule=void 0;function Ka(i,e){if(typeof i!="object"||typeof e!="object"||i===null||e===null)return i===e;const t=Object.keys(i),s=Object.keys(e);if(t.length!==s.length)return!1;const n=o=>Ka(i[o],e[o]);return t.every(n)}const Uf=i=>class extends i{static get properties(){return{opened:{type:Boolean,reflect:!0}}}#e=!1;constructor(){super(),this.opened=!1,this.config={},this.toggle=this.toggle.bind(this),this.open=this.open.bind(this),this.close=this.close.bind(this)}get config(){return this.__config}set config(t){const s=!Ka(this.config,t);this._overlayCtrl&&s&&this._overlayCtrl.updateConfig(t),this.__config=t,this._overlayCtrl&&s&&this.__syncToOverlayController()}requestUpdate(t,s,n){super.requestUpdate(t,s,n),t==="opened"&&this.opened!==s&&this.dispatchEvent(new CustomEvent("opened-changed",{detail:{opened:this.opened}}))}_defineOverlay({contentNode:t,invokerNode:s,referenceNode:n,backdropNode:o,contentWrapperNode:r}){const a=this._defineOverlayConfig()||{};return new ct({contentNode:t,invokerNode:s,referenceNode:n,backdropNode:o,contentWrapperNode:r,...a,...this.config,popperConfig:{...a.popperConfig||{},...this.config?.popperConfig||{},modifiers:[...a.popperConfig?.modifiers||[],...this.config?.popperConfig?.modifiers||[]]}})}_defineOverlayConfig(){return{placementMode:"local"}}updated(t){super.updated(t),t.has("opened")&&this._overlayCtrl&&!this.__blockSyncToOverlayCtrl&&this.__syncToOverlayController()}_setupOpenCloseListeners(){this.__closeEventInContentNodeHandler=t=>{t.stopPropagation(),this._overlayCtrl.hide()},this._overlayContentNode&&this._overlayContentNode.addEventListener("close-overlay",this.__closeEventInContentNodeHandler)}_teardownOpenCloseListeners(){this._overlayContentNode&&this._overlayContentNode.removeEventListener("close-overlay",this.__closeEventInContentNodeHandler)}connectedCallback(){super.connectedCallback(),this.updateComplete.then(()=>{this.isConnected&&(this.#e||(this._setupOverlayCtrl(),this.#e=!0))})}async disconnectedCallback(){super.disconnectedCallback(),await this._isPermanentlyDisconnected()&&(this._teardownOverlayCtrl(),this.#e=!1)}static enabledWarnings=super.enabledWarnings?.filter(t=>t!=="change-in-update")||[];get _overlayInvokerNode(){return Array.from(this.children).find(t=>t.slot==="invoker")}get _overlayReferenceNode(){}get _overlayBackdropNode(){return this.__cachedOverlayBackdropNode||(this.__cachedOverlayBackdropNode=Array.from(this.children).find(t=>t.slot==="backdrop")),this.__cachedOverlayBackdropNode}get _overlayContentNode(){return this._cachedOverlayContentNode||(this._cachedOverlayContentNode=Array.from(this.children).find(t=>t.slot==="content")||this.config.contentNode),this._cachedOverlayContentNode}get _overlayContentWrapperNode(){return this.shadowRoot?.querySelector("#overlay-content-node-wrapper")}_setupOverlayCtrl(){if(this.#e)return;const t={contentNode:this._overlayContentNode,contentWrapperNode:this._overlayContentWrapperNode,invokerNode:this._overlayInvokerNode,referenceNode:this._overlayReferenceNode,backdropNode:this._overlayBackdropNode};this._overlayCtrl?this._overlayCtrl.updateConfig(t):this._overlayCtrl=this._defineOverlay(t),this.__syncToOverlayController(),this.__setupSyncFromOverlayController(),this._setupOpenCloseListeners()}_teardownOverlayCtrl(){this._overlayCtrl&&(this._teardownOpenCloseListeners(),this.__teardownSyncFromOverlayController(),this._overlayCtrl.teardown())}async _setOpenedWithoutPropertyEffects(t){this.__blockSyncToOverlayCtrl=!0,this.opened=t,await this.updateComplete,this.__blockSyncToOverlayCtrl=!1}__setupSyncFromOverlayController(){this.__onOverlayCtrlShow=()=>{this.opened=!0},this.__onOverlayCtrlHide=()=>{this.opened=!1},this.__onBeforeShow=t=>{const s=new CustomEvent("before-opened",{cancelable:!0});this.dispatchEvent(s),s.defaultPrevented&&(this._setOpenedWithoutPropertyEffects(this._overlayCtrl.isShown),t.preventDefault())},this.__onBeforeHide=t=>{const s=new CustomEvent("before-closed",{cancelable:!0});this.dispatchEvent(s),s.defaultPrevented&&(this._setOpenedWithoutPropertyEffects(this._overlayCtrl.isShown),t.preventDefault())},this._overlayCtrl.addEventListener("show",this.__onOverlayCtrlShow),this._overlayCtrl.addEventListener("hide",this.__onOverlayCtrlHide),this._overlayCtrl.addEventListener("before-show",this.__onBeforeShow),this._overlayCtrl.addEventListener("before-hide",this.__onBeforeHide)}__teardownSyncFromOverlayController(){this._overlayCtrl.removeEventListener("show",this.__onOverlayCtrlShow),this._overlayCtrl.removeEventListener("hide",this.__onOverlayCtrlHide),this._overlayCtrl.removeEventListener("before-show",this.__onBeforeShow),this._overlayCtrl.removeEventListener("before-hide",this.__onBeforeHide)}__syncToOverlayController(){this.opened?this._overlayCtrl.show():this._overlayCtrl.hide()}async toggle(){await this._overlayCtrl.toggle()}async open(){await this._overlayCtrl.show()}async close(){await this._overlayCtrl.hide()}repositionOverlay(){const t=this._overlayCtrl;t.placementMode==="local"&&t._popper&&t._popper.update()}async _isPermanentlyDisconnected(){return await this.updateComplete,!this.isConnected}},Ga=ie(Uf);function Hf(){return{visibilityTriggerFunction:({controller:i})=>{function e(){i._hasDisabledInvoker()||i.toggle()}return{init:()=>{i.invokerNode?.addEventListener("click",e)},teardown:()=>{i.invokerNode?.removeEventListener("click",e)}}}}}const Ya=()=>({placementMode:"local",inheritsReferenceWidth:"min",hidesOnOutsideClick:!0,hidesOnEsc:!0,popperConfig:{placement:"bottom-start",modifiers:[{name:"offset",enabled:!1}]},handlesAccessibility:!0,...Hf()});var Kt=class extends Ga(H){_defineOverlayConfig(){return{...Ya()}}_addEventListeners(){this.actionItems.forEach(e=>{e.addEventListener("click",t=>{t.target?.dispatchEvent(new Event("close-overlay",{bubbles:!0}))})})}_setupInvoker(){let e=this.invokerNodes[0];e&&(e.setAttribute("id",`invoker-${this.uid}`),e.setAttribute("aria-controls",`content-${this.uid}`))}_setupContent(){let e=this.contentNodes[0];e&&(e.setAttribute("id",`content-${this.uid}`),e.setAttribute("role","none"))}_setupOverlayCtrl(){super._setupOverlayCtrl(),this._setupInvoker(),this._setupContent()}firstUpdated(){this.uid=yi(),this._addEventListeners()}render(){return x`
      <slot name="invoker"></slot>
      <slot name="backdrop"></slot>
      <slot name="content"></slot>
    `}};Kt.styles=O`
    ::slotted([slot='content']) {
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
  `,L([zi({selector:"craft-action-item"})],Kt.prototype,"actionItems",void 0),L([zi({slot:"invoker"})],Kt.prototype,"invokerNodes",void 0),L([zi({slot:"content"})],Kt.prototype,"contentNodes",void 0),customElements.get("craft-action-menu")||customElements.define("craft-action-menu",Kt);const Zt=new WeakMap;function Xa(i,e){Array.from(i.childNodes).forEach(t=>{if(t.nodeName==="#text"){const s=new RegExp(`^(.*?)(${e})(.*)$`,"i"),n=t.nodeValue.match(s);if(n){const o=document.createTextNode(n[1]);i.appendChild(o);const r=document.createElement("b");r.textContent=n[2],i.appendChild(r);const a=document.createTextNode(n[3]);i.appendChild(a),i.removeChild(t),Zt.set(i,()=>{i.appendChild(t),i.contains(o)&&o.parentNode!==null&&o.parentNode.removeChild(o),i.contains(r)&&r.parentNode!==null&&r.parentNode.removeChild(r),i.contains(a)&&a.parentNode!==null&&a.parentNode.removeChild(a)})}}else Xa(t,e)})}function Za(i){Zt.has(i)&&Zt.get(i)(),Array.from(i.childNodes).forEach(e=>{e.nodeName==="#text"?Zt.has(e)&&Zt.get(e)():Za(e)})}class qf extends Ss{static get validatorName(){return"MatchesOption"}execute(e,t,s){return s?.node.modelValue instanceof bt}}function $i(i){return Array.isArray(i)?i:[i]}const jf=i=>class extends to(i){static get properties(){return{allowCustomChoice:{type:Boolean,attribute:"allow-custom-choice"},modelValue:{type:Object}}}get modelValue(){return this.__getChoicesFrom(super.modelValue)}set modelValue(t){if(super.modelValue=t,t==null||t==="")this._customChoices=new Set;else if(this.allowCustomChoice){const s=this.modelValue;this._customChoices=new Set($i(t)),this.requestUpdate("modelValue",s)}}get formattedValue(){return this.__getChoicesFrom(super.formattedValue)}set formattedValue(t){if(super.formattedValue=t,t==null)this._customChoices=new Set;else if(this.allowCustomChoice){const s=this.modelValue;this._customChoices=new Set($i(t).map(n=>this.formElements.find(o=>o.formattedValue===n)?.modelValue||n)),this.requestUpdate("modelValue",s)}}get serializedValue(){return this.__getChoicesFrom(super.serializedValue)}set serializedValue(t){if(super.serializedValue=t,t==null)this._customChoices=new Set;else if(this.allowCustomChoice){const s=this.modelValue;this._customChoices=new Set($i(t).map(n=>this.formElements.find(o=>o.serializedValue===n)?.modelValue||n)),this.requestUpdate("modelValue",s)}}get customChoices(){if(!this.allowCustomChoice)return[];const t=this._getCheckedElements();return Array.from(this._customChoices).filter(s=>!t.some(n=>n.choiceValue===s))}constructor(){super(),this.allowCustomChoice=!1,this._customChoices=new Set}__getChoicesFrom(t){const s=t;return this.allowCustomChoice?this.multipleChoice?[...$i(s),...this.customChoices]:s===""?this._customChoices.values().next().value||"":s:s}_isEmpty(){return super._isEmpty()&&this._customChoices.size===0}clear(){this._customChoices=new Set,super.clear()}parser(t){return this.allowCustomChoice&&Array.isArray(t)?t.filter(s=>s.trim()!==""):t}},Wf=ie(jf),tn=new WeakMap;class Kf extends ks(Ga(Wf(Tp))){static get properties(){return{autocomplete:{type:String,reflect:!0},matchMode:{type:String,attribute:"match-mode"},showAllOnEmpty:{type:Boolean,attribute:"show-all-on-empty"},requireOptionMatch:{type:Boolean},allowCustomChoice:{type:Boolean,attribute:"allow-custom-choice"},__shouldAutocompleteNextUpdate:Boolean}}static get styles(){return[...super.styles,O`
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
      `]}static get localizeNamespaces(){return[{"lion-combobox":e=>{switch(e){case"bg-BG":case"bg":return C(()=>import("./bg.js"),[],import.meta.url);case"cs-CZ":case"cs":return C(()=>import("./cs.js"),[],import.meta.url);case"de-AT":case"de-DE":case"de":return C(()=>import("./de.js"),[],import.meta.url);case"en-AU":case"en-GB":case"en-PH":case"en-US":case"en":return C(()=>import("./en.js"),[],import.meta.url);case"es-ES":case"es":return C(()=>import("./es.js"),[],import.meta.url);case"fr-FR":case"fr-BE":case"fr":return C(()=>import("./fr.js"),[],import.meta.url);case"hu-HU":case"hu":return C(()=>import("./hu.js"),[],import.meta.url);case"it-IT":case"it":return C(()=>import("./it.js"),[],import.meta.url);case"nl-BE":case"nl-NL":case"nl":return C(()=>import("./nl.js"),[],import.meta.url);case"pl-PL":case"pl":return C(()=>import("./pl.js"),[],import.meta.url);case"ro-RO":case"ro":return C(()=>import("./ro.js"),[],import.meta.url);case"ru-RU":case"ru":return C(()=>import("./ru.js"),[],import.meta.url);case"sk-SK":case"sk":return C(()=>import("./sk.js"),[],import.meta.url);case"uk-UA":case"uk":return C(()=>import("./uk.js"),[],import.meta.url);case"zh-CN":case"zh":return C(()=>import("./zh.js"),[],import.meta.url);default:return C(()=>import("./en.js"),[],import.meta.url)}}},...super.localizeNamespaces]}get modelValue(){const e=super.modelValue;return e!==""?e:this.parser(this.value)}set modelValue(e){super.modelValue=e}get value(){return this._inputNode?.value||this.__value||""}set value(e){this._inputNode?(this._inputNode.value=e,this.__value=void 0):this.__value=e}reset(){super.reset(),this.multipleChoice||(this.value=this._initialModelValue),this._resetListboxOptions()}_resetListboxOptions(){this.formElements.forEach((e,t)=>{this._unhighlightMatchedOption(e),!this.showAllOnEmpty||!this.opened?e.style.display="none":(e.style.display="",e.setAttribute("aria-posinset",`${t+1}`),e.setAttribute("aria-setsize",`${this.formElements.length}`),e.removeAttribute("aria-hidden"))})}_inputGroupInputTemplate(){return x`
      <div class="input-group__input">
        <slot name="selection-display"></slot>
        <slot name="input"></slot>
      </div>
    `}_overlayListboxTemplate(){return x`
      <div
        id="overlay-content-node-wrapper"
        role="dialog"
        aria-label="${this.msgLit("lion-combobox:optionsPopup")}"
      >
        <slot name="listbox"></slot>
      </div>
      <slot id="options-outlet"></slot>
    `}_groupTwoTemplate(){return x` ${super._groupTwoTemplate()} ${this._overlayListboxTemplate()}`}get slots(){return{...super.slots,input:()=>{if(this._ariaVersion==="1.1"){const e=document.createElement("div"),t=document.createElement("input");return t.style.cssText=`
          border: none;
          outline: none;
          width: 100%;
          height: 100%;
          font: inherit;
          background: inherit;
          color: inherit;
          border-radius: inherit;
          box-sizing: border-box;
          padding: 0;`,e.appendChild(t),e}return document.createElement("input")},listbox:super.slots.input}}get _comboboxNode(){return this.querySelector('[slot="input"]')}get _selectionDisplayNode(){return this.querySelector('[slot="selection-display"]')}get _inputNode(){return this._ariaVersion==="1.1"&&this._comboboxNode?this._comboboxNode.querySelector("input")||this._comboboxNode:this._comboboxNode}get _overlayContentNode(){return this._listboxNode}get _overlayReferenceNode(){return this.shadowRoot.querySelector(".input-group__container")}get _overlayInvokerNode(){return this._inputNode}get _listboxNode(){return this._overlayCtrl&&this._overlayCtrl.contentNode||Array.from(this.children).find(e=>e.slot==="listbox")}get _activeDescendantOwnerNode(){return this._inputNode}get requireOptionMatch(){return!this.allowCustomChoice}set requireOptionMatch(e){this.allowCustomChoice=!e}constructor(){super(),this.autocomplete="both",this.matchMode="all",this.showAllOnEmpty=!1,this.requireOptionMatch=!0,this.rotateKeyboardNavigation=!0,this.selectionFollowsFocus=!0,this.defaultValidators.push(new qf),this._ariaVersion=is.isChromium?"1.1":"1.0",this._listboxReceivesNoFocus=!0,this._noTypeAhead=!0,this.__prevCboxValueNonSelected="",this.__prevCboxValue="",this.__hadUserIntendsInlineAutoFill=!1,this.__listboxContentChanged=!1,this._onKeyUp=this._onKeyUp.bind(this),this._textboxOnClick=this._textboxOnClick.bind(this),this._textboxOnInput=this._textboxOnInput.bind(this),this._textboxOnKeydown=this._textboxOnKeydown.bind(this)}connectedCallback(){super.connectedCallback(),this._selectionDisplayNode&&(this._selectionDisplayNode.comboboxElement=this),(this.disabled||this.readOnly)&&this.__setComboboxDisabledAndReadOnly()}requestUpdate(e,t,s){if(super.requestUpdate(e,t,s),(e==="disabled"||e==="readOnly")&&this.__setComboboxDisabledAndReadOnly(),e==="modelValue"&&this.modelValue&&this.modelValue!==t&&this._syncToTextboxCondition(this.modelValue,this._oldModelValue))if(this.multipleChoice)this._syncToTextboxMultiple(this.modelValue,this._oldModelValue);else{const n=this._getTextboxValueFromOption(this.formElements[this.checkedIndex]);this._setTextboxValue(n)}}parser(e){return this.requireOptionMatch&&this.checkedIndex===-1&&e!==""&&!Array.isArray(e)?new bt(e):super.parser(e)}__unsyncCheckedIndexOnInputChange(){const e=this._autoSelectCondition(),t=this.formElements[this.checkedIndex];if(!this.multipleChoice&&!e&&t){const s=this._getTextboxValueFromOption(t);this._inputNode.value.startsWith(s)||this.setCheckedIndex(-1)}}updated(e){super.updated(e),e.has("__shouldAutocompleteNextUpdate")&&this.__unsyncCheckedIndexOnInputChange(),e.has("opened")&&(this.opened&&(this.activeIndex=-1),!this.opened&&e.get("opened")!==void 0&&(this.__onOverlayClose(),this.activeIndex=-1)),e.has("autocomplete")&&this._inputNode.setAttribute("aria-autocomplete",this.autocomplete),e.has("disabled")&&this.setAttribute("aria-disabled",`${this.disabled}`),e.has("__shouldAutocompleteNextUpdate")&&this.__shouldAutocompleteNextUpdate&&(this._handleAutocompletion(),this.__shouldAutocompleteNextUpdate=!1,this.__listboxContentChanged=!1),typeof this._selectionDisplayNode?.onComboboxElementUpdated=="function"&&this._selectionDisplayNode.onComboboxElementUpdated(e)}matchCondition(e,t){let s=-1;const n=this._getTextboxValueFromOption(e);return typeof n=="string"&&typeof t=="string"&&(s=n.toLowerCase().indexOf(t.toLowerCase())),this.matchMode==="all"?s>-1:s===0}_showOverlayCondition({lastKey:e}){const t=["Tab","Escape"],s=["Enter"];return this.disabled||this.readOnly||e&&(t.includes(e)||!this.multipleChoice&&s.includes(e))?!1:this.filled||this.showAllOnEmpty||!this.filled&&this.multipleChoice&&this.__prevCboxValueNonSelected?!0:this.opened}_getTextboxValueFromOption(e){return e?e.choiceValue:this.modelValue instanceof bt?this.modelValue.viewValue:this.modelValue}_onListboxContentChanged(){super._onListboxContentChanged(),this.__shouldAutocompleteNextUpdate=!0,this.__listboxContentChanged=!0}_textboxOnInput(e){this.__shouldAutocompleteNextUpdate=!0,this.opened=this._showOverlayCondition({})}_textboxOnKeydown(e){e.key==="Tab"&&(this.opened=!1)}_listboxOnClick(e){super._listboxOnClick(e),this._inputNode.focus(),this.multipleChoice?(this._inputNode.value="",this._resetListboxOptions()):(this.activeIndex=-1,this.opened=!1)}_setTextboxValue(e){this._inputNode&&this._inputNode.value!==e&&(this._inputNode.value=e)}__onOverlayClose(){this.multipleChoice?this._syncToTextboxMultiple(this.modelValue,this._oldModelValue):this.checkedIndex!==-1&&this._syncToTextboxCondition(this.modelValue,this._oldModelValue,{phase:"overlay-close"})&&(this._inputNode.value=this._getTextboxValueFromOption(this.formElements[this.checkedIndex]))}_repropagationCondition(e){return super._repropagationCondition(e)||this.formElements.every(t=>!t.checked)}_onFilterMatch(e,t){this._highlightMatchedOption(e,t),e.style.display=""}_highlightMatchedOption(e,t){if(Xa(e,t),e.textContent){const s=document.createElement("span");s.setAttribute("aria-label",e.textContent.replace(/\s+/g," ")),Array.from(e.childNodes).forEach(n=>{s.appendChild(n)}),e.appendChild(s),tn.set(e,()=>{Array.from(s.childNodes).forEach(n=>{e.appendChild(n)}),e.contains(s)&&e.removeChild(s)})}}_onFilterUnmatch(e,t,s){this._unhighlightMatchedOption(e),e.style.display="none"}_unhighlightMatchedOption(e){Za(e),tn.has(e)&&tn.get(e)()}__computeUserIntendsAutoFill({prevValue:e,curValue:t}){const s=e.length<t.length,n=e.length&&t.length&&e[0].toLowerCase()!==t[0].toLowerCase();return s||n||this.__listboxContentChanged&&this.__hadUserIntendsInlineAutoFill}_handleAutocompletion(){const t=!(this._inputNode.selectionStart===this._inputNode.selectionEnd)&&this._inputNode.value.length!==this._inputNode.selectionStart,s=this._inputNode.value,n=this._inputNode.selectionStart,o=t&&n?s.slice(0,n):s,r=t||this.__hadSelectionLastAutofill?this.__prevCboxValueNonSelected:this.__prevCboxValue,a=!o,l=[];let d=!1;const c=this.__computeUserIntendsAutoFill({prevValue:r,curValue:o}),h=this.autocomplete==="both"||this.autocomplete==="inline",m=this._autoSelectCondition(),b=this.autocomplete==="inline"||this.autocomplete==="none";this.formElements.forEach((g,_)=>{const E=this.matchCondition(g,o);let k=!1;if(a?k=this.showAllOnEmpty:k=b||E,m&&!d&&E&&!g.disabled){const A=()=>{this.activeIndex=_,this.selectionFollowsFocus&&!this.multipleChoice&&this.setCheckedIndex(this.activeIndex),d=!0};if(c)if(h){const S=this._getTextboxValueFromOption(g);S&&typeof S=="string"&&typeof o=="string"&&S.toLowerCase().indexOf(o.toLowerCase())===0&&(this.__textboxInlineComplete(g),A())}else A()}g.onFilterUnmatch?g.onFilterUnmatch(o,r):this._onFilterUnmatch(g,o,r),g.setAttribute("aria-hidden","true"),g.removeAttribute("aria-posinset"),g.removeAttribute("aria-setsize"),k&&(l.push(g),g.onFilterMatch?g.onFilterMatch(o):this._onFilterMatch(g,o))});const p=l.length;l.forEach((g,_)=>{g.setAttribute("aria-posinset",`${_+1}`),g.setAttribute("aria-setsize",`${p}`),g.removeAttribute("aria-hidden")}),m&&!d&&!this.multipleChoice&&(this.setCheckedIndex(-1),r!==o&&(this.activeIndex=-1),this.modelValue=this.parser(s)),this.__prevCboxValueNonSelected=o,this.__prevCboxValue=this._inputNode.value,this.__hadSelectionLastAutofill=this._inputNode.value.length!==this._inputNode.selectionStart,this.__hadUserIntendsInlineAutoFill=c,this._overlayCtrl&&this._overlayCtrl._popper&&this._overlayCtrl._popper.update()}__textboxInlineComplete(e=this.formElements[this.activeIndex]){const t=this._getTextboxValueFromOption(e);if(this._inputNode.value!==t){const s=this._inputNode.value.length;this._inputNode.value=t,this._inputNode.selectionStart=s,this._inputNode.selectionEnd=this._inputNode.value.length}}_autoSelectCondition(){return this.autocomplete==="both"||this.autocomplete==="inline"}_setupListboxNode(){super._setupListboxNode(),this._listboxNode.removeAttribute("tabindex")}_defineOverlayConfig(){return{...Ya(),elementToFocusAfterHide:void 0,invokerNode:this._comboboxNode,visibilityTriggerFunction:void 0}}_setupOverlayCtrl(){super._setupOverlayCtrl(),this.__shouldAutocompleteNextUpdate=!0,this.__setupCombobox()}_teardownOverlayCtrl(){super._teardownOverlayCtrl(),this.__teardownCombobox()}_setupOpenCloseListeners(){super._setupOpenCloseListeners(),this._inputNode.addEventListener("keyup",this._onKeyUp),this._inputNode.addEventListener("click",this._textboxOnClick)}_teardownOpenCloseListeners(){super._teardownOpenCloseListeners(),this._inputNode.removeEventListener("keyup",this._onKeyUp),this._inputNode.removeEventListener("click",this._textboxOnClick)}_listboxOnKeyDown(e){const{key:t}=e;switch(t){case"Escape":this.opened=!1,super._listboxOnKeyDown(e),this._setTextboxValue("");break;case"Backspace":case"Delete":this.requireOptionMatch?super._listboxOnKeyDown(e):this.opened=!1;break;case"Enter":this.opened&&e.preventDefault(),!this.requireOptionMatch&&this.multipleChoice&&(!this.formElements[this.activeIndex]||this.formElements[this.activeIndex].hasAttribute("aria-hidden")||!this.opened)?(this.modelValue=this.parser([...this.modelValue,this._inputNode.value]),this._inputNode.value="",this.opened=!1):(super._listboxOnKeyDown(e),this._resetListboxOptions()),this.multipleChoice?this._inputNode.value="":this.opened=!1;break;default:{super._listboxOnKeyDown(e);break}}}_syncToTextboxCondition(e,t,{phase:s}={}){return this.autocomplete==="both"||this.autocomplete==="inline"||!this.focused}_syncToTextboxMultiple(e,t=[]){if(this.requireOptionMatch){const s=e.filter(o=>!t.includes(o)),n=this.formElements.filter(o=>s.includes(o.choiceValue)).map(o=>this._getTextboxValueFromOption(o)).join(" ");this._setTextboxValue(n)}}_enhanceLightDomClasses(){const e=this.querySelector("[slot=input]");e&&e.classList.add("form-control")}__setComboboxDisabledAndReadOnly(){this._comboboxNode&&(this._comboboxNode.toggleAttribute("disabled",this.disabled),this._comboboxNode.setAttribute("aria-disabled",`${this.disabled}`),this._comboboxNode.toggleAttribute("readonly",this.readOnly),this._comboboxNode.setAttribute("aria-readonly",`${this.readOnly}`)),this._inputNode&&(this._inputNode.toggleAttribute("disabled",this.disabled),this._inputNode.toggleAttribute("readOnly",this.readOnly),this._inputNode.setAttribute("aria-readonly",`${this.readOnly}`),this._inputNode.tabIndex=this.disabled?-1:0)}__setupCombobox(){this._comboboxNode.setAttribute("role","combobox"),this._comboboxNode.setAttribute("aria-haspopup","listbox"),this._inputNode.setAttribute("aria-autocomplete",this.autocomplete),this._comboboxNode.setAttribute("aria-controls",this._listboxNode.id),this._ariaVersion==="1.1"?this._comboboxNode.setAttribute("aria-owns",this._listboxNode.id):this._inputNode.setAttribute("aria-owns",this._listboxNode.id),this._listboxNode.setAttribute("aria-labelledby",this._labelNode.id),this._inputNode.addEventListener("keydown",this._listboxOnKeyDown),this._inputNode.addEventListener("input",this._textboxOnInput),this._inputNode.addEventListener("keydown",this._textboxOnKeydown)}__teardownCombobox(){this._inputNode.removeEventListener("keydown",this._listboxOnKeyDown),this._inputNode.removeEventListener("input",this._textboxOnInput),this._inputNode.removeEventListener("keydown",this._textboxOnKeydown)}_onKeyUp(e){const t=e&&e.key;this.opened=this._showOverlayCondition({lastKey:t,currentValue:this._inputNode.value})}_textboxOnClick(e){this.opened=this._showOverlayCondition({})}clear(){this.value="",super.clear(),this.__shouldAutocompleteNextUpdate=!0}}var Gf=O`
  ${Yn}

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
    ${Gn}
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
`,Yf=class extends Kf{static get styles(){return[...super.styles,Gf]}constructor(){super(),this.defaultValidators=[]}_inputGroupInputTemplate(){return x`
      <div class="input-group__input">
        <slot name="input"></slot>
        <craft-icon
          class="indicator"
          name="chevron-down"
          style="font-size: 0.8em"
        ></craft-icon>
      </div>
    `}_getTextboxValueFromOption(e){return e?e.textContent?.trim()||"":super._getTextboxValueFromOption(e)}};customElements.get("craft-combobox")||customElements.define("craft-combobox",Yf);var Ri=class extends H{constructor(...e){super(...e),this.variant=Ae.Default,this.label=null}render(){return x`<span
      class="${Fe({indicator:!0,"indicator--success":this.variant===Ae.Success,"indicator--danger":this.variant===Ae.Danger,"indicator--warning":this.variant===Ae.Warning,"indicator--info":this.variant===Ae.Info})}"
    ></span>`}};Ri.styles=[no,O`
      .indicator {
        display: inline-flex;
        aspect-ratio: 1;
        width: var(--c-indicator-size, 0.5em);
        border-radius: var(--c-radius-full);
        color: var(--c-color-on-emphasis);
        background-color: var(--c-color-bg-emphasis);
        border: 1px solid var(--c-color-bg-emphasis);
      }
    `],L([y({reflect:!0})],Ri.prototype,"variant",void 0),L([y()],Ri.prototype,"label",void 0),customElements.get("craft-indicator")||customElements.define("craft-indicator",Ri);var Gt=class extends H{constructor(){super(),this.alt=!1,this.shift=!1,this.os="Unknown",this.os=this.detectOS()}connectedCallback(){super.connectedCallback(),this.os==="Unknown"&&(this.os=this.detectOS())}detectOS(){let e=navigator.platform.toLowerCase();return e.includes("mac")||/iphone|ipad|ipod/.test(e)?"Mac":e.includes("win")?"Windows":e.includes("linux")?"Linux":"Unknown"}renderShortcutPrefix(){switch(this.os){case"Mac":return`${this.alt?"⌥":""}${this.shift?"⇧":""}⌘`;case"Linux":return`Super+${this.alt?"Alt+":""}${this.shift?"Shift+":""}`;default:return`Ctrl+${this.alt?"Alt+":""}${this.shift?"Shift+":""}`}}render(){return x`<span class="shortcut"
      >${this.renderShortcutPrefix()}<slot></slot
    ></span>`}};Gt.styles=O`
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
  `,L([y({type:Boolean})],Gt.prototype,"alt",void 0),L([y({type:Boolean})],Gt.prototype,"shift",void 0),L([y()],Gt.prototype,"os",void 0),customElements.get("craft-shortcut")||customElements.define("craft-shortcut",Gt);var Xf=class{constructor(){this.refreshPromise=null,this.tokenName=null,this.tokenValue=null,this.refreshPromise=null}async getToken(){return this.tokenValue||await this.refreshToken(),this.tokenValue}async refreshToken(){return this.refreshPromise||(this.refreshPromise=li.get("users/session-info").then(({data:i})=>{let{csrfTokenName:e,csrfTokenValue:t}=i;return this.tokenName=e??null,this.tokenValue=t??null,this.tokenValue}).finally(()=>{this.refreshPromise=null})),this.refreshPromise}clearToken(){this.tokenValue=null}};function Zf(i=""){return`https://craft6-dev.ddev.site/admin/actions/${i}`}function Jf(){let i={"X-Registered-Asset-Bundles":[...new Set(Craft.registeredAssetBundles)].join(","),"X-Registered-Js-Files":[...new Set(Craft.registeredJsFiles)].join(",")};return Craft.csrfTokenValue&&(i["X-CSRF-Token"]=Craft.csrfTokenValue),i}const li=te.create({baseURL:Zf()}),sn=new Xf;li.interceptors.request.use(async i=>{i.headers.set("X-Requested-With","XMLHttpRequest");let e=Jf();if(Object.entries(e).forEach(([t,s])=>{i.headers.set(t,s)}),["post","put","patch","delete"].includes(i.method?.toLowerCase()||"")&&!i.url?.includes("users/session-info")){let t=await sn.getToken();t&&i.headers.set("X-CSRF-Token",t)}return i}),li.interceptors.response.use(i=>i,async i=>{let e=i.config;if(i.response?.status===419||i.response?.status===403&&!e._retry){e._retry=!0;try{return sn.clearToken(),e.headers["X-CSRF-Token"]=await sn.refreshToken(),te(e)}catch(t){return console.error("Failed to refresh CSRF token:",t),Promise.reject(t)}}return Promise.reject(i)});let Wi=!1,ht=null;async function Qf(i){if(!Wi){if(ht)return ht;Wi=!0;try{return(await li.post("app/api-headers",void 0,{cancelToken:i})).data}catch{}finally{Wi=!1}}}const nn=te.create({baseURL:"https://api.craftcms.com/v1/"});async function em(i){return ht?Object.entries(ht).forEach(([e,t])=>{i.headers.set(e,t)}):(i.params=i.params||{},i.params.processCraftHeaders=1),i}async function tm(i,e){if(ht)return;let{data:t}=await li.post("app/process-api-response-headers",{headers:i},{cancelToken:e});return ht=t,Wi=!1,ht}async function im(i){return await tm(i.headers,i.config.cancelToken),i}nn.interceptors.request.use(async i=>{let{cancelToken:e}=i,t=await Qf(e);t&&Object.entries(t).forEach(([n,o])=>{i.headers.set(n,o)});let s={...i,params:{...Craft.apiParams||{},...i.params,v:new Date().getTime()}};return t||(s.params.processCraftHeaders=1),Craft.httpProxy&&(s.proxy=Craft.httpProxy),s}),nn.interceptors.request.use(em),nn.interceptors.response.use(im);var sm=function(i,e,t,s,n){if(s==="m")throw new TypeError("Private method is not writable");if(s==="a"&&!n)throw new TypeError("Private accessor was defined without a setter");if(typeof e=="function"?i!==e||!n:!e.has(i))throw new TypeError("Cannot write private member to an object whose class did not declare it");return s==="a"?n.call(i,t):n?n.value=t:e.set(i,t),t},wr=function(i,e,t,s){if(t==="a"&&!s)throw new TypeError("Private accessor was defined without a getter");if(typeof e=="function"?i!==e||!s:!e.has(i))throw new TypeError("Cannot read private member from an object whose class did not declare it");return t==="m"?s:t==="a"?s.call(i):s?s.value:e.get(i)},Jt;class nm{formatToParts(e){const t=[];for(const s of e)t.push({type:"element",value:s}),t.push({type:"literal",value:", "});return t.slice(0,-1)}}const om=typeof Intl<"u"&&Intl.ListFormat||nm,rm=[["years","year"],["months","month"],["weeks","week"],["days","day"],["hours","hour"],["minutes","minute"],["seconds","second"],["milliseconds","millisecond"]],am={minimumIntegerDigits:2};class lm{constructor(e,t={}){Jt.set(this,void 0);let s=String(t.style||"short");s!=="long"&&s!=="short"&&s!=="narrow"&&s!=="digital"&&(s="short");let n=s==="digital"?"numeric":s;const o=t.hours||n;n=o==="2-digit"?"numeric":o;const r=t.minutes||n;n=r==="2-digit"?"numeric":r;const a=t.seconds||n;n=a==="2-digit"?"numeric":a;const l=t.milliseconds||n;sm(this,Jt,{locale:e,style:s,years:t.years||s==="digital"?"short":s,yearsDisplay:t.yearsDisplay==="always"?"always":"auto",months:t.months||s==="digital"?"short":s,monthsDisplay:t.monthsDisplay==="always"?"always":"auto",weeks:t.weeks||s==="digital"?"short":s,weeksDisplay:t.weeksDisplay==="always"?"always":"auto",days:t.days||s==="digital"?"short":s,daysDisplay:t.daysDisplay==="always"?"always":"auto",hours:o,hoursDisplay:t.hoursDisplay==="always"||s==="digital"?"always":"auto",minutes:r,minutesDisplay:t.minutesDisplay==="always"||s==="digital"?"always":"auto",seconds:a,secondsDisplay:t.secondsDisplay==="always"||s==="digital"?"always":"auto",milliseconds:l,millisecondsDisplay:t.millisecondsDisplay==="always"?"always":"auto"},"f")}resolvedOptions(){return wr(this,Jt,"f")}formatToParts(e){const t=[],s=wr(this,Jt,"f"),n=s.style,o=s.locale;for(const[r,a]of rm){const l=e[r];if(s[`${r}Display`]==="auto"&&!l)continue;const d=s[r],c=d==="2-digit"?am:d==="numeric"?{}:{style:"unit",unit:a,unitDisplay:d};let h=new Intl.NumberFormat(o,c).format(l);r==="months"&&(d==="narrow"||n==="narrow"&&h.endsWith("m"))&&(h=h.replace(/(\d+)m$/,"$1mo")),t.push(h)}return new om(o,{type:"unit",style:n==="digital"?"short":n}).formatToParts(t)}format(e){return this.formatToParts(e).map(t=>t.value).join("")}}Jt=new WeakMap;const Ja=/^[-+]?P(?:(\d+)Y)?(?:(\d+)M)?(?:(\d+)W)?(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?)?$/,as=["year","month","week","day","hour","minute","second","millisecond"],cm=i=>Ja.test(i);class we{constructor(e=0,t=0,s=0,n=0,o=0,r=0,a=0,l=0){this.years=e,this.months=t,this.weeks=s,this.days=n,this.hours=o,this.minutes=r,this.seconds=a,this.milliseconds=l,this.years||(this.years=0),this.sign||(this.sign=Math.sign(this.years)),this.months||(this.months=0),this.sign||(this.sign=Math.sign(this.months)),this.weeks||(this.weeks=0),this.sign||(this.sign=Math.sign(this.weeks)),this.days||(this.days=0),this.sign||(this.sign=Math.sign(this.days)),this.hours||(this.hours=0),this.sign||(this.sign=Math.sign(this.hours)),this.minutes||(this.minutes=0),this.sign||(this.sign=Math.sign(this.minutes)),this.seconds||(this.seconds=0),this.sign||(this.sign=Math.sign(this.seconds)),this.milliseconds||(this.milliseconds=0),this.sign||(this.sign=Math.sign(this.milliseconds)),this.blank=this.sign===0}abs(){return new we(Math.abs(this.years),Math.abs(this.months),Math.abs(this.weeks),Math.abs(this.days),Math.abs(this.hours),Math.abs(this.minutes),Math.abs(this.seconds),Math.abs(this.milliseconds))}static from(e){var t;if(typeof e=="string"){const s=String(e).trim(),n=s.startsWith("-")?-1:1,o=(t=s.match(Ja))===null||t===void 0?void 0:t.slice(1).map(r=>(Number(r)||0)*n);return o?new we(...o):new we}else if(typeof e=="object"){const{years:s,months:n,weeks:o,days:r,hours:a,minutes:l,seconds:d,milliseconds:c}=e;return new we(s,n,o,r,a,l,d,c)}throw new RangeError("invalid duration")}static compare(e,t){const s=Date.now(),n=Math.abs(xr(s,we.from(e)).getTime()-s),o=Math.abs(xr(s,we.from(t)).getTime()-s);return n>o?-1:n<o?1:0}toLocaleString(e,t){return new lm(e,t).format(this)}}function xr(i,e){const t=new Date(i);return e.sign<0?(t.setUTCSeconds(t.getUTCSeconds()+e.seconds),t.setUTCMinutes(t.getUTCMinutes()+e.minutes),t.setUTCHours(t.getUTCHours()+e.hours),t.setUTCDate(t.getUTCDate()+e.weeks*7+e.days),t.setUTCMonth(t.getUTCMonth()+e.months),t.setUTCFullYear(t.getUTCFullYear()+e.years)):(t.setUTCFullYear(t.getUTCFullYear()+e.years),t.setUTCMonth(t.getUTCMonth()+e.months),t.setUTCDate(t.getUTCDate()+e.weeks*7+e.days),t.setUTCHours(t.getUTCHours()+e.hours),t.setUTCMinutes(t.getUTCMinutes()+e.minutes),t.setUTCSeconds(t.getUTCSeconds()+e.seconds)),t}function dm(i,e="second",t=Date.now()){const s=i.getTime()-t;if(s===0)return new we;const n=Math.sign(s),o=Math.abs(s),r=Math.floor(o/1e3),a=Math.floor(r/60),l=Math.floor(a/60),d=Math.floor(l/24),c=Math.floor(d/30),h=Math.floor(c/12),m=as.indexOf(e)||as.length;return new we(m>=0?h*n:0,m>=1?(c-h*12)*n:0,0,m>=3?(d-c*30)*n:0,m>=4?(l-d*24)*n:0,m>=5?(a-l*60)*n:0,m>=6?(r-a*60)*n:0,m>=7?(o-r*1e3)*n:0)}function Qa(i,{relativeTo:e=Date.now()}={}){if(e=new Date(e),i.blank)return i;const t=i.sign;let s=Math.abs(i.years),n=Math.abs(i.months),o=Math.abs(i.weeks),r=Math.abs(i.days),a=Math.abs(i.hours),l=Math.abs(i.minutes),d=Math.abs(i.seconds),c=Math.abs(i.milliseconds);c>=900&&(d+=Math.round(c/1e3)),(d||l||a||r||o||n||s)&&(c=0),d>=55&&(l+=Math.round(d/60)),(l||a||r||o||n||s)&&(d=0),l>=55&&(a+=Math.round(l/60)),(a||r||o||n||s)&&(l=0),r&&a>=12&&(r+=Math.round(a/24)),!r&&a>=21&&(r+=Math.round(a/24)),(r||o||n||s)&&(a=0);const h=e.getFullYear(),m=e.getMonth(),b=e.getDate();if(r>=27||s+n+r){const p=new Date(e);p.setDate(1),p.setMonth(m+n*t+1),p.setDate(0);const g=Math.max(0,b-p.getDate()),_=new Date(e);_.setFullYear(h+s*t),_.setDate(b-g),_.setMonth(m+n*t),_.setDate(b-g+r*t);const E=_.getFullYear()-e.getFullYear(),k=_.getMonth()-e.getMonth(),A=Math.abs(Math.round((Number(_)-Number(e))/864e5))+g,S=Math.abs(E*12+k);A<27?(r>=6?(o+=Math.round(r/7),r=0):r=A,n=s=0):S<=11?(n=S,s=0):(n=0,s=E*t),(n||s)&&(r=0)}return s&&(n=0),o>=4&&(n+=Math.round(o/4)),(n||s)&&(o=0),r&&o&&!n&&!s&&(o+=Math.round(r/7),r=0),new we(s*t,n*t,o*t,r*t,a*t,l*t,d*t,c*t)}function um(i,e){const t=Qa(i,e);if(t.blank)return[0,"second"];for(const s of as){if(s==="millisecond")continue;const n=t[`${s}s`];if(n)return[n,s]}return[0,"second"]}var Z=function(i,e,t,s){if(t==="a"&&!s)throw new TypeError("Private accessor was defined without a getter");if(typeof e=="function"?i!==e||!s:!e.has(i))throw new TypeError("Cannot read private member from an object whose class did not declare it");return t==="m"?s:t==="a"?s.call(i):s?s.value:e.get(i)},Ii=function(i,e,t,s,n){if(s==="m")throw new TypeError("Private method is not writable");if(s==="a"&&!n)throw new TypeError("Private accessor was defined without a setter");if(typeof e=="function"?i!==e||!n:!e.has(i))throw new TypeError("Cannot write private member to an object whose class did not declare it");return s==="a"?n.call(i,t):n?n.value=t:e.set(i,t),t},ae,Qt,ei,yt,dt,Sn,el,tl,il,sl,nl,An,ol,xt;const hm=globalThis.HTMLElement||null,on=new we,Er=new we(0,0,0,0,0,1);class pm extends Event{constructor(e,t,s,n){super("relative-time-updated",{bubbles:!0,composed:!0}),this.oldText=e,this.newText=t,this.oldTitle=s,this.newTitle=n}}function Cr(i){if(!i.date)return 1/0;if(i.format==="duration"||i.format==="elapsed"){const t=i.precision;if(t==="second")return 1e3;if(t==="minute")return 60*1e3}const e=Math.abs(Date.now()-i.date.getTime());return e<60*1e3?1e3:e<3600*1e3?60*1e3:3600*1e3}const rn=new class{constructor(){this.elements=new Set,this.time=1/0,this.timer=-1}observe(i){if(this.elements.has(i))return;this.elements.add(i);const e=i.date;if(e&&e.getTime()){const t=Cr(i),s=Date.now()+t;s<this.time&&(clearTimeout(this.timer),this.timer=setTimeout(()=>this.update(),t),this.time=s)}}unobserve(i){this.elements.has(i)&&this.elements.delete(i)}update(){if(clearTimeout(this.timer),!this.elements.size)return;let i=1/0;for(const e of this.elements)i=Math.min(i,Cr(e)),e.update();this.time=Math.min(3600*1e3,i),this.timer=setTimeout(()=>this.update(),this.time),this.time+=Date.now()}};class fm extends hm{constructor(){super(...arguments),ae.add(this),Qt.set(this,!1),ei.set(this,!1),dt.set(this,this.shadowRoot?this.shadowRoot:this.attachShadow?this.attachShadow({mode:"open"}):this),xt.set(this,null)}static define(e="relative-time",t=customElements){return t.define(e,this),this}get timeZone(){var e;return((e=this.closest("[time-zone]"))===null||e===void 0?void 0:e.getAttribute("time-zone"))||this.ownerDocument.documentElement.getAttribute("time-zone")||void 0}static get observedAttributes(){return["second","minute","hour","weekday","day","month","year","time-zone-name","prefix","threshold","tense","precision","format","format-style","no-title","datetime","lang","title","aria-hidden","time-zone"]}get onRelativeTimeUpdated(){return Z(this,xt,"f")}set onRelativeTimeUpdated(e){Z(this,xt,"f")&&this.removeEventListener("relative-time-updated",Z(this,xt,"f")),Ii(this,xt,typeof e=="object"||typeof e=="function"?e:null,"f"),typeof e=="function"&&this.addEventListener("relative-time-updated",e)}get second(){const e=this.getAttribute("second");if(e==="numeric"||e==="2-digit")return e}set second(e){this.setAttribute("second",e||"")}get minute(){const e=this.getAttribute("minute");if(e==="numeric"||e==="2-digit")return e}set minute(e){this.setAttribute("minute",e||"")}get hour(){const e=this.getAttribute("hour");if(e==="numeric"||e==="2-digit")return e}set hour(e){this.setAttribute("hour",e||"")}get weekday(){const e=this.getAttribute("weekday");if(e==="long"||e==="short"||e==="narrow")return e;if(this.format==="datetime"&&e!=="")return this.formatStyle}set weekday(e){this.setAttribute("weekday",e||"")}get day(){var e;const t=(e=this.getAttribute("day"))!==null&&e!==void 0?e:"numeric";if(t==="numeric"||t==="2-digit")return t}set day(e){this.setAttribute("day",e||"")}get month(){const e=this.format;let t=this.getAttribute("month");if(t!==""&&(t??(t=e==="datetime"?this.formatStyle:"short"),t==="numeric"||t==="2-digit"||t==="short"||t==="long"||t==="narrow"))return t}set month(e){this.setAttribute("month",e||"")}get year(){var e;const t=this.getAttribute("year");if(t==="numeric"||t==="2-digit")return t;if(!this.hasAttribute("year")&&new Date().getUTCFullYear()!==((e=this.date)===null||e===void 0?void 0:e.getUTCFullYear()))return"numeric"}set year(e){this.setAttribute("year",e||"")}get timeZoneName(){const e=this.getAttribute("time-zone-name");if(e==="long"||e==="short"||e==="shortOffset"||e==="longOffset"||e==="shortGeneric"||e==="longGeneric")return e}set timeZoneName(e){this.setAttribute("time-zone-name",e||"")}get prefix(){var e;return(e=this.getAttribute("prefix"))!==null&&e!==void 0?e:this.format==="datetime"?"":"on"}set prefix(e){this.setAttribute("prefix",e)}get threshold(){const e=this.getAttribute("threshold");return e&&cm(e)?e:"P30D"}set threshold(e){this.setAttribute("threshold",e)}get tense(){const e=this.getAttribute("tense");return e==="past"?"past":e==="future"?"future":"auto"}set tense(e){this.setAttribute("tense",e)}get precision(){const e=this.getAttribute("precision");return as.includes(e)?e:this.format==="micro"?"minute":"second"}set precision(e){this.setAttribute("precision",e)}get format(){const e=this.getAttribute("format");return e==="datetime"?"datetime":e==="relative"?"relative":e==="duration"?"duration":e==="micro"?"micro":e==="elapsed"?"elapsed":"auto"}set format(e){this.setAttribute("format",e)}get formatStyle(){const e=this.getAttribute("format-style");if(e==="long")return"long";if(e==="short")return"short";if(e==="narrow")return"narrow";const t=this.format;return t==="elapsed"||t==="micro"?"narrow":t==="datetime"?"short":"long"}set formatStyle(e){this.setAttribute("format-style",e)}get noTitle(){return this.hasAttribute("no-title")}set noTitle(e){this.toggleAttribute("no-title",e)}get datetime(){return this.getAttribute("datetime")||""}set datetime(e){this.setAttribute("datetime",e)}get date(){const e=Date.parse(this.datetime);return Number.isNaN(e)?null:new Date(e)}set date(e){this.datetime=e?.toISOString()||""}connectedCallback(){this.update()}disconnectedCallback(){rn.unobserve(this)}attributeChangedCallback(e,t,s){t!==s&&(e==="title"&&Ii(this,Qt,s!==null&&(this.date&&Z(this,ae,"m",Sn).call(this,this.date))!==s,"f"),!Z(this,ei,"f")&&!(e==="title"&&Z(this,Qt,"f"))&&Ii(this,ei,(async()=>{await Promise.resolve(),this.update(),Ii(this,ei,!1,"f")})(),"f"))}update(){const e=Z(this,dt,"f").textContent||this.textContent||"",t=this.getAttribute("title")||"";let s=t;const n=this.date;if(typeof Intl>"u"||!Intl.DateTimeFormat||!n){Z(this,dt,"f").textContent=e;return}const o=Date.now();Z(this,Qt,"f")||(s=Z(this,ae,"m",Sn).call(this,n)||"",s&&!this.noTitle&&this.setAttribute("title",s));const r=dm(n,this.precision,o),a=Z(this,ae,"m",el).call(this,r);let l=e;const d=Z(this,ae,"m",ol).call(this,a);d?l=Z(this,ae,"m",nl).call(this,n):a==="duration"?l=Z(this,ae,"m",tl).call(this,r):a==="relative"?l=Z(this,ae,"m",il).call(this,r):l=Z(this,ae,"m",sl).call(this,n),l?Z(this,ae,"m",An).call(this,l):this.shadowRoot===Z(this,dt,"f")&&this.textContent&&Z(this,ae,"m",An).call(this,this.textContent),(l!==e||s!==t)&&this.dispatchEvent(new pm(e,l,t,s)),(a==="relative"||a==="duration")&&!d?rn.observe(this):rn.unobserve(this)}}Qt=new WeakMap,ei=new WeakMap,dt=new WeakMap,xt=new WeakMap,ae=new WeakSet,yt=function(){var e;const t=((e=this.closest("[lang]"))===null||e===void 0?void 0:e.getAttribute("lang"))||this.ownerDocument.documentElement.getAttribute("lang");try{return new Intl.Locale(t??"").toString()}catch{return"default"}},Sn=function(e){return new Intl.DateTimeFormat(Z(this,ae,"a",yt),{day:"numeric",month:"short",year:"numeric",hour:"numeric",minute:"2-digit",timeZoneName:"short",timeZone:this.timeZone}).format(e)},el=function(e){const t=this.format;if(t==="datetime")return"datetime";if(t==="duration"||t==="elapsed"||t==="micro")return"duration";if((t==="auto"||t==="relative")&&typeof Intl<"u"&&Intl.RelativeTimeFormat){const s=this.tense;if(s==="past"||s==="future"||we.compare(e,this.threshold)===1)return"relative"}return"datetime"},tl=function(e){const t=Z(this,ae,"a",yt),s=this.format,n=this.formatStyle,o=this.tense;let r=on;s==="micro"?(e=Qa(e),r=Er,e.months===0&&(this.tense==="past"&&e.sign!==-1||this.tense==="future"&&e.sign!==1)&&(e=Er)):(o==="past"&&e.sign!==-1||o==="future"&&e.sign!==1)&&(e=r);const a=`${this.precision}sDisplay`;return e.blank?r.toLocaleString(t,{style:n,[a]:"always"}):e.abs().toLocaleString(t,{style:n})},il=function(e){const t=new Intl.RelativeTimeFormat(Z(this,ae,"a",yt),{numeric:"auto",style:this.formatStyle}),s=this.tense;s==="future"&&e.sign!==1&&(e=on),s==="past"&&e.sign!==-1&&(e=on);const[n,o]=um(e);return o==="second"&&n<10?t.format(0,this.precision==="millisecond"?"second":this.precision):t.format(n,o)},sl=function(e){const t=new Intl.DateTimeFormat(Z(this,ae,"a",yt),{second:this.second,minute:this.minute,hour:this.hour,weekday:this.weekday,day:this.day,month:this.month,year:this.year,timeZoneName:this.timeZoneName,timeZone:this.timeZone});return`${this.prefix} ${t.format(e)}`.trim()},nl=function(e){return new Intl.DateTimeFormat(Z(this,ae,"a",yt),{day:"numeric",month:"short",year:"numeric",hour:"numeric",minute:"2-digit",timeZoneName:"short",timeZone:this.timeZone}).format(e)},An=function(e){if(this.hasAttribute("aria-hidden")&&this.getAttribute("aria-hidden")==="true"){const t=document.createElement("span");t.setAttribute("aria-hidden","true"),t.textContent=e,Z(this,dt,"f").replaceChildren(t)}else Z(this,dt,"f").textContent=e},ol=function(e){var t;return e==="duration"?!1:this.ownerDocument.documentElement.getAttribute("data-prefers-absolute-time")==="true"||((t=this.ownerDocument.body)===null||t===void 0?void 0:t.getAttribute("data-prefers-absolute-time"))==="true"};const kr=typeof globalThis<"u"?globalThis:window;try{kr.RelativeTimeElement=fm.define()}catch(i){if(!(kr.DOMException&&i instanceof DOMException&&i.name==="NotSupportedError")&&!(i instanceof ReferenceError))throw i}cd();var mm=Object.defineProperty,bm=Object.getOwnPropertyDescriptor,Ts=(i,e,t,s)=>{for(var n=s>1?void 0:s?bm(e,t):e,o=i.length-1,r;o>=0;o--)(r=i[o])&&(n=(s?r(e,t,n):r(n))||n);return s&&n&&mm(e,t,n),n};let ci=class extends H{constructor(){super(...arguments),this.state=Craft.getCookie("sidebar")??"expanded"}connectedCallback(){super.connectedCallback(),this.trigger&&(this.trigger.addEventListener("open",this.expand.bind(this)),this.trigger.addEventListener("close",this.collapse.bind(this))),this.state==="expanded"?this.expand():this.collapse()}disconnectedCallback(){super.disconnectedCallback(),this.trigger&&(this.trigger.removeEventListener("open",this.expand.bind(this)),this.trigger.removeEventListener("close",this.collapse.bind(this))),this.state="expanded"}itemHasTooltip(i){return i.querySelector("craft-tooltip")}createTooltips(){this.items?.forEach(i=>i.setAttribute("icon-only",!0))}destroyTooltips(){this.items?.forEach(i=>i.removeAttribute("icon-only"))}expand(){document.body.setAttribute("data-sidebar","expanded"),Craft.setCookie("sidebar","expanded"),this.destroyTooltips()}collapse(){document.body.setAttribute("data-sidebar","collapsed"),Craft.setCookie("sidebar","collapsed"),this.createTooltips()}createRenderRoot(){return this}};Ts([$d("craft-nav-item")],ci.prototype,"items",2);Ts([Q("#sidebar-trigger")],ci.prototype,"trigger",2);Ts([y({reflect:!0})],ci.prototype,"state",2);ci=Ts([ke("cp-global-sidebar")],ci);export{C as _,te as a};
