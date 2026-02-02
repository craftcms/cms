import{_ as f}from"./state.js";import{a as q,i as $,x as T}from"./lit-element.js";import{n as v}from"./property.js";function F(t,s){if(s.has(t))throw new TypeError("Cannot initialize the same private elements twice on an object")}function h(t,s,o){F(t,s),s.set(t,o)}function a(t,s,o){if(typeof t=="function"?t===s:t.has(s))return arguments.length<3?s:o;throw new TypeError("Private element is not present on this object")}function e(t,s,o){return t.set(a(t,s),o),o}function i(t,s){return t.get(a(t,s))}function B(t,s){F(t,s),s.add(t)}var n=new WeakMap,u=new WeakMap,b=new WeakMap,k=new WeakMap,M=new WeakMap,c=new WeakMap,m=new WeakMap,_=new WeakMap,l=new WeakMap,g=new WeakMap,w=new WeakMap,r=new WeakSet,p=class extends ${constructor(...t){super(...t),B(this,r),this.progress=0,this.failed=!1,this.color="currentColor",this.bgColor="#a3afbb",this.failColor="#da5a47",this.label="Progress",this.autoComplete=!1,h(this,n,null),h(this,u,0),h(this,b,0),h(this,k,0),h(this,M,0),h(this,c,0),h(this,m,null),h(this,_,0),h(this,l,null),h(this,g,0),h(this,w,!1)}connectedCallback(){super.connectedCallback(),e(w,this,window.matchMedia("(prefers-reduced-motion: reduce)").matches)}disconnectedCallback(){super.disconnectedCallback(),a(r,this,E).call(this)}firstUpdated(){e(n,this,this.renderRoot.querySelector("canvas")),a(r,this,j).call(this),a(r,this,S).call(this)}updated(t){t.has("progress")?a(r,this,S).call(this):(t.has("color")||t.has("bgColor")||t.has("failColor")||t.has("failed"))&&a(r,this,y).call(this)}get canvas(){return i(n,this)}get prefersReducedMotion(){return i(w,this)}runCompleteAnimation(){return new Promise(t=>{if(i(w,this)){e(c,this,1),i(n,this)&&(i(n,this).style.opacity="0"),a(r,this,y).call(this),t();return}a(r,this,z).call(this,1,()=>{i(n,this)&&(i(n,this).style.transition="opacity 0.4s",i(n,this).style.opacity="0"),setTimeout(t,400)})})}async complete(){await this.runCompleteAnimation(),this.dispatchEvent(new CustomEvent("complete",{bubbles:!0,composed:!0}))}render(){return T`
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
    `}};function j(){const t=getComputedStyle(this),s=parseFloat(t.getPropertyValue("--_size")),o=parseFloat(t.getPropertyValue("--_stroke-width")),d=window.devicePixelRatio>1?2:1;e(u,this,s*d),e(b,this,i(u,this)/2),e(M,this,o*d),e(k,this,(s/2-o/2)*d),i(n,this)&&(i(n,this).width=i(u,this),i(n,this).height=i(u,this))}function S(){if(this.progress>=0&&i(l,this)!==null&&(cancelAnimationFrame(i(l,this)),e(l,this,null),e(_,this,0)),this.progress<0){i(l,this)===null&&a(r,this,V).call(this);return}const t=this.progress/100;if(this.autoComplete&&this.progress>=100&&i(g,this)<100){e(g,this,this.progress),this.complete();return}i(g,this)>0&&this.progress>i(g,this)&&!i(w,this)?a(r,this,z).call(this,t):(e(c,this,t),a(r,this,y).call(this)),e(g,this,this.progress)}function V(){if(i(w,this)){e(c,this,.25),a(r,this,y).call(this);return}const t=()=>{e(_,this,i(_,this)+.05),e(c,this,.15+.1*Math.sin(i(_,this)*3)),a(r,this,y).call(this),e(l,this,requestAnimationFrame(t))};e(l,this,requestAnimationFrame(t))}function y(){const t=i(n,this)?.getContext("2d");if(t){if(t.clearRect(0,0,i(u,this),i(u,this)),this.failed){a(r,this,C).call(this,t,this.failColor,1,0);return}if(a(r,this,C).call(this,t,this.bgColor,1,0),i(c,this)>0){const s=this.progress<0?i(_,this):-Math.PI/2;a(r,this,C).call(this,t,this.color,i(c,this),s)}}}function C(t,s,o,d){t.strokeStyle=s,t.lineWidth=i(M,this),t.lineCap="round",t.beginPath(),t.arc(i(b,this),i(b,this),i(k,this),d,d+o*2*Math.PI),t.stroke()}function z(t,s){a(r,this,E).call(this);const o=performance.now(),d=500,P=i(c,this),W=R=>{const I=R-o,A=Math.min(I/d,1),x=1-Math.pow(1-A,3);e(c,this,P+(t-P)*x),a(r,this,y).call(this),A<1?e(m,this,requestAnimationFrame(W)):(e(m,this,null),s?.())};e(m,this,requestAnimationFrame(W))}function E(){i(m,this)!==null&&(cancelAnimationFrame(i(m,this)),e(m,this,null)),i(l,this)!==null&&(cancelAnimationFrame(i(l,this)),e(l,this,null))}p.styles=q`
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
  `;f([v({type:Number})],p.prototype,"progress",void 0);f([v({type:Boolean})],p.prototype,"failed",void 0);f([v({type:String})],p.prototype,"color",void 0);f([v({type:String,attribute:"bg-color"})],p.prototype,"bgColor",void 0);f([v({type:String,attribute:"fail-color"})],p.prototype,"failColor",void 0);f([v({type:String})],p.prototype,"label",void 0);f([v({type:Boolean,attribute:"auto-complete"})],p.prototype,"autoComplete",void 0);customElements.get("craft-progress")||customElements.define("craft-progress",p);
