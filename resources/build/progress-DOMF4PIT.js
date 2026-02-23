import{n as f}from"./property.js";import{a as R,i as q,x as j}from"./lit-element.js";function g(t,e,r,h){var m=arguments.length,c=m<3?e:h,w;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")c=Reflect.decorate(t,e,r,h);else for(var b=t.length-1;b>=0;b--)(w=t[b])&&(c=(m<3?w(c):m>3?w(e,r,c):w(e,r))||c);return m>3&&c&&Object.defineProperty(e,r,c),c}function D(t){return f({...t,state:!0,attribute:!1})}function S(t,e){if(e.has(t))throw TypeError("Cannot initialize the same private elements twice on an object")}function l(t,e,r){S(t,e),e.set(t,r)}function a(t,e,r){if(typeof t=="function"?t===e:t.has(e))return arguments.length<3?e:r;throw TypeError("Private element is not present on this object")}function s(t,e,r){return t.set(a(t,e),r),r}function i(t,e){return t.get(a(t,e))}function T(t,e){S(t,e),e.add(t)}var n=new WeakMap,y=new WeakMap,W=new WeakMap,F=new WeakMap,P=new WeakMap,d=new WeakMap,v=new WeakMap,M=new WeakMap,p=new WeakMap,C=new WeakMap,k=new WeakMap,o=new WeakSet,u=class extends q{constructor(...t){super(...t),T(this,o),this.progress=0,this.failed=!1,this.color="currentColor",this.bgColor="#a3afbb",this.failColor="#da5a47",this.label="Progress",this.autoComplete=!1,l(this,n,null),l(this,y,0),l(this,W,0),l(this,F,0),l(this,P,0),l(this,d,0),l(this,v,null),l(this,M,0),l(this,p,null),l(this,C,0),l(this,k,!1)}connectedCallback(){super.connectedCallback(),s(k,this,window.matchMedia("(prefers-reduced-motion: reduce)").matches)}disconnectedCallback(){super.disconnectedCallback(),a(o,this,z).call(this)}firstUpdated(){s(n,this,this.renderRoot.querySelector("canvas")),a(o,this,B).call(this),a(o,this,A).call(this)}updated(t){t.has("progress")?a(o,this,A).call(this):(t.has("color")||t.has("bgColor")||t.has("failColor")||t.has("failed"))&&a(o,this,x).call(this)}get canvas(){return i(n,this)}get prefersReducedMotion(){return i(k,this)}runCompleteAnimation(){return new Promise(t=>{if(i(k,this)){s(d,this,1),i(n,this)&&(i(n,this).style.opacity="0"),a(o,this,x).call(this),t();return}a(o,this,_).call(this,1,()=>{i(n,this)&&(i(n,this).style.transition="opacity 0.4s",i(n,this).style.opacity="0"),setTimeout(t,400)})})}async complete(){await this.runCompleteAnimation(),this.dispatchEvent(new CustomEvent("complete",{bubbles:!0,composed:!0}))}render(){return j`
      <canvas
        part="canvas"
        role="progressbar"
        aria-valuenow=${(this.progress>=0?this.progress:void 0)??""}
        aria-valuemin="0"
        aria-valuemax="100"
        aria-label=${this.label}
      ></canvas>
      <span class="visually-hidden">
        ${this.failed?"Failed":this.progress<0?"Loading":`${this.progress}%`}
      </span>
    `}};function B(){let t=getComputedStyle(this),e=parseFloat(t.getPropertyValue("--_size")),r=parseFloat(t.getPropertyValue("--_stroke-width")),h=window.devicePixelRatio>1?2:1;s(y,this,e*h),s(W,this,i(y,this)/2),s(P,this,r*h),s(F,this,(e/2-r/2)*h),i(n,this)&&(i(n,this).width=i(y,this),i(n,this).height=i(y,this))}function A(){if(this.progress>=0&&i(p,this)!==null&&(cancelAnimationFrame(i(p,this)),s(p,this,null),s(M,this,0)),this.progress<0){i(p,this)===null&&a(o,this,I).call(this);return}let t=this.progress/100;if(this.autoComplete&&this.progress>=100&&i(C,this)<100){s(C,this,this.progress),this.complete();return}i(C,this)>0&&this.progress>i(C,this)&&!i(k,this)?a(o,this,_).call(this,t):(s(d,this,t),a(o,this,x).call(this)),s(C,this,this.progress)}function I(){if(i(k,this)){s(d,this,.25),a(o,this,x).call(this);return}let t=()=>{s(M,this,i(M,this)+.05),s(d,this,.15+.1*Math.sin(i(M,this)*3)),a(o,this,x).call(this),s(p,this,requestAnimationFrame(t))};s(p,this,requestAnimationFrame(t))}function x(){let t=i(n,this)?.getContext("2d");if(t){if(t.clearRect(0,0,i(y,this),i(y,this)),this.failed){a(o,this,$).call(this,t,this.failColor,1,0);return}if(a(o,this,$).call(this,t,this.bgColor,1,0),i(d,this)>0){let e=this.progress<0?i(M,this):-Math.PI/2;a(o,this,$).call(this,t,this.color,i(d,this),e)}}}function $(t,e,r,h){t.strokeStyle=e,t.lineWidth=i(P,this),t.lineCap="round",t.beginPath(),t.arc(i(W,this),i(W,this),i(F,this),h,h+r*2*Math.PI),t.stroke()}function _(t,e){a(o,this,z).call(this);let r=performance.now(),h=i(d,this),m=c=>{let w=c-r,b=Math.min(w/500,1),E=1-(1-b)**3;s(d,this,h+(t-h)*E),a(o,this,x).call(this),b<1?s(v,this,requestAnimationFrame(m)):(s(v,this,null),e?.())};s(v,this,requestAnimationFrame(m))}function z(){i(v,this)!==null&&(cancelAnimationFrame(i(v,this)),s(v,this,null)),i(p,this)!==null&&(cancelAnimationFrame(i(p,this)),s(p,this,null))}u.styles=R`
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
  `,g([f({type:Number})],u.prototype,"progress",void 0),g([f({type:Boolean})],u.prototype,"failed",void 0),g([f({type:String})],u.prototype,"color",void 0),g([f({type:String,attribute:"bg-color"})],u.prototype,"bgColor",void 0),g([f({type:String,attribute:"fail-color"})],u.prototype,"failColor",void 0),g([f({type:String})],u.prototype,"label",void 0),g([f({type:Boolean,attribute:"auto-complete"})],u.prototype,"autoComplete",void 0),customElements.get("craft-progress")||customElements.define("craft-progress",u);export{g as e,D as r};
