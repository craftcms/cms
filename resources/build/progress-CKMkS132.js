import{e as d}from"./state.js";import{a as q,i as R,x as T}from"./lit-element.js";import{n as m}from"./property.js";function A(t,s){if(s.has(t))throw TypeError("Cannot initialize the same private elements twice on an object")}function l(t,s,r){A(t,s),s.set(t,r)}function a(t,s,r){if(typeof t=="function"?t===s:t.has(s))return arguments.length<3?s:r;throw TypeError("Private element is not present on this object")}function e(t,s,r){return t.set(a(t,s),r),r}function i(t,s){return t.get(a(t,s))}function j(t,s){A(t,s),s.add(t)}var h=new WeakMap,f=new WeakMap,C=new WeakMap,M=new WeakMap,W=new WeakMap,u=new WeakMap,g=new WeakMap,y=new WeakMap,n=new WeakMap,w=new WeakMap,v=new WeakMap,o=new WeakSet,c=class extends R{constructor(...t){super(...t),j(this,o),this.progress=0,this.failed=!1,this.color="currentColor",this.bgColor="#a3afbb",this.failColor="#da5a47",this.label="Progress",this.autoComplete=!1,l(this,h,null),l(this,f,0),l(this,C,0),l(this,M,0),l(this,W,0),l(this,u,0),l(this,g,null),l(this,y,0),l(this,n,null),l(this,w,0),l(this,v,!1)}connectedCallback(){super.connectedCallback(),e(v,this,window.matchMedia("(prefers-reduced-motion: reduce)").matches)}disconnectedCallback(){super.disconnectedCallback(),a(o,this,S).call(this)}firstUpdated(){e(h,this,this.renderRoot.querySelector("canvas")),a(o,this,B).call(this),a(o,this,F).call(this)}updated(t){t.has("progress")?a(o,this,F).call(this):(t.has("color")||t.has("bgColor")||t.has("failColor")||t.has("failed"))&&a(o,this,b).call(this)}get canvas(){return i(h,this)}get prefersReducedMotion(){return i(v,this)}runCompleteAnimation(){return new Promise(t=>{if(i(v,this)){e(u,this,1),i(h,this)&&(i(h,this).style.opacity="0"),a(o,this,b).call(this),t();return}a(o,this,P).call(this,1,()=>{i(h,this)&&(i(h,this).style.transition="opacity 0.4s",i(h,this).style.opacity="0"),setTimeout(t,400)})})}async complete(){await this.runCompleteAnimation(),this.dispatchEvent(new CustomEvent("complete",{bubbles:!0,composed:!0}))}render(){return T`
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
    `}};function B(){let t=getComputedStyle(this),s=parseFloat(t.getPropertyValue("--_size")),r=parseFloat(t.getPropertyValue("--_stroke-width")),p=window.devicePixelRatio>1?2:1;e(f,this,s*p),e(C,this,i(f,this)/2),e(W,this,r*p),e(M,this,(s/2-r/2)*p),i(h,this)&&(i(h,this).width=i(f,this),i(h,this).height=i(f,this))}function F(){if(this.progress>=0&&i(n,this)!==null&&(cancelAnimationFrame(i(n,this)),e(n,this,null),e(y,this,0)),this.progress<0){i(n,this)===null&&a(o,this,I).call(this);return}let t=this.progress/100;if(this.autoComplete&&this.progress>=100&&i(w,this)<100){e(w,this,this.progress),this.complete();return}i(w,this)>0&&this.progress>i(w,this)&&!i(v,this)?a(o,this,P).call(this,t):(e(u,this,t),a(o,this,b).call(this)),e(w,this,this.progress)}function I(){if(i(v,this)){e(u,this,.25),a(o,this,b).call(this);return}let t=()=>{e(y,this,i(y,this)+.05),e(u,this,.15+.1*Math.sin(i(y,this)*3)),a(o,this,b).call(this),e(n,this,requestAnimationFrame(t))};e(n,this,requestAnimationFrame(t))}function b(){let t=i(h,this)?.getContext("2d");if(t){if(t.clearRect(0,0,i(f,this),i(f,this)),this.failed){a(o,this,k).call(this,t,this.failColor,1,0);return}if(a(o,this,k).call(this,t,this.bgColor,1,0),i(u,this)>0){let s=this.progress<0?i(y,this):-Math.PI/2;a(o,this,k).call(this,t,this.color,i(u,this),s)}}}function k(t,s,r,p){t.strokeStyle=s,t.lineWidth=i(W,this),t.lineCap="round",t.beginPath(),t.arc(i(C,this),i(C,this),i(M,this),p,p+r*2*Math.PI),t.stroke()}function P(t,s){a(o,this,S).call(this);let r=performance.now(),p=i(u,this),x=_=>{let z=_-r,$=Math.min(z/500,1),E=1-(1-$)**3;e(u,this,p+(t-p)*E),a(o,this,b).call(this),$<1?e(g,this,requestAnimationFrame(x)):(e(g,this,null),s?.())};e(g,this,requestAnimationFrame(x))}function S(){i(g,this)!==null&&(cancelAnimationFrame(i(g,this)),e(g,this,null)),i(n,this)!==null&&(cancelAnimationFrame(i(n,this)),e(n,this,null))}c.styles=q`
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
  `,d([m({type:Number})],c.prototype,"progress",void 0),d([m({type:Boolean})],c.prototype,"failed",void 0),d([m({type:String})],c.prototype,"color",void 0),d([m({type:String,attribute:"bg-color"})],c.prototype,"bgColor",void 0),d([m({type:String,attribute:"fail-color"})],c.prototype,"failColor",void 0),d([m({type:String})],c.prototype,"label",void 0),d([m({type:Boolean,attribute:"auto-complete"})],c.prototype,"autoComplete",void 0),customElements.get("craft-progress")||customElements.define("craft-progress",c);
