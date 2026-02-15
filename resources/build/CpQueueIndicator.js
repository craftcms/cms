import{a as T,i as L,x as J,E as I}from"./lit-element.js";import{t as Z}from"./custom-element.js";import{_ as g,r as tt}from"./state.js";import{n as _}from"./property.js";function O(t,e){if(e.has(t))throw new TypeError("Cannot initialize the same private elements twice on an object")}function h(t,e,s){O(t,e),e.set(t,s)}function r(t,e,s){if(typeof t=="function"?t===e:t.has(e))return arguments.length<3?e:s;throw new TypeError("Private element is not present on this object")}function a(t,e,s){return t.set(r(t,e),s),s}function i(t,e){return t.get(r(t,e))}function et(t,e){O(t,e),e.add(t)}var l=new WeakMap,v=new WeakMap,A=new WeakMap,E=new WeakMap,z=new WeakMap,d=new WeakMap,m=new WeakMap,C=new WeakMap,p=new WeakMap,y=new WeakMap,w=new WeakMap,o=new WeakSet,u=class extends L{constructor(...t){super(...t),et(this,o),this.progress=0,this.failed=!1,this.color="currentColor",this.bgColor="#a3afbb",this.failColor="#da5a47",this.label="Progress",this.autoComplete=!1,h(this,l,null),h(this,v,0),h(this,A,0),h(this,E,0),h(this,z,0),h(this,d,0),h(this,m,null),h(this,C,0),h(this,p,null),h(this,y,0),h(this,w,!1)}connectedCallback(){super.connectedCallback(),a(w,this,window.matchMedia("(prefers-reduced-motion: reduce)").matches)}disconnectedCallback(){super.disconnectedCallback(),r(o,this,j).call(this)}firstUpdated(){a(l,this,this.renderRoot.querySelector("canvas")),r(o,this,it).call(this),r(o,this,x).call(this)}updated(t){t.has("progress")?r(o,this,x).call(this):(t.has("color")||t.has("bgColor")||t.has("failColor")||t.has("failed"))&&r(o,this,k).call(this)}get canvas(){return i(l,this)}get prefersReducedMotion(){return i(w,this)}runCompleteAnimation(){return new Promise(t=>{if(i(w,this)){a(d,this,1),i(l,this)&&(i(l,this).style.opacity="0"),r(o,this,k).call(this),t();return}r(o,this,U).call(this,1,()=>{i(l,this)&&(i(l,this).style.transition="opacity 0.4s",i(l,this).style.opacity="0"),setTimeout(t,400)})})}async complete(){await this.runCompleteAnimation(),this.dispatchEvent(new CustomEvent("complete",{bubbles:!0,composed:!0}))}render(){return J`
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
    `}};function it(){const t=getComputedStyle(this),e=parseFloat(t.getPropertyValue("--_size")),s=parseFloat(t.getPropertyValue("--_stroke-width")),n=window.devicePixelRatio>1?2:1;a(v,this,e*n),a(A,this,i(v,this)/2),a(z,this,s*n),a(E,this,(e/2-s/2)*n),i(l,this)&&(i(l,this).width=i(v,this),i(l,this).height=i(v,this))}function x(){if(this.progress>=0&&i(p,this)!==null&&(cancelAnimationFrame(i(p,this)),a(p,this,null),a(C,this,0)),this.progress<0){i(p,this)===null&&r(o,this,st).call(this);return}const t=this.progress/100;if(this.autoComplete&&this.progress>=100&&i(y,this)<100){a(y,this,this.progress),this.complete();return}i(y,this)>0&&this.progress>i(y,this)&&!i(w,this)?r(o,this,U).call(this,t):(a(d,this,t),r(o,this,k).call(this)),a(y,this,this.progress)}function st(){if(i(w,this)){a(d,this,.25),r(o,this,k).call(this);return}const t=()=>{a(C,this,i(C,this)+.05),a(d,this,.15+.1*Math.sin(i(C,this)*3)),r(o,this,k).call(this),a(p,this,requestAnimationFrame(t))};a(p,this,requestAnimationFrame(t))}function k(){const t=i(l,this)?.getContext("2d");if(t){if(t.clearRect(0,0,i(v,this),i(v,this)),this.failed){r(o,this,F).call(this,t,this.failColor,1,0);return}if(r(o,this,F).call(this,t,this.bgColor,1,0),i(d,this)>0){const e=this.progress<0?i(C,this):-Math.PI/2;r(o,this,F).call(this,t,this.color,i(d,this),e)}}}function F(t,e,s,n){t.strokeStyle=e,t.lineWidth=i(z,this),t.lineCap="round",t.beginPath(),t.arc(i(A,this),i(A,this),i(E,this),n,n+s*2*Math.PI),t.stroke()}function U(t,e){r(o,this,j).call(this);const s=performance.now(),n=500,c=i(d,this),b=M=>{const X=M-s,q=Math.min(X/n,1),Y=1-Math.pow(1-q,3);a(d,this,c+(t-c)*Y),r(o,this,k).call(this),q<1?a(m,this,requestAnimationFrame(b)):(a(m,this,null),e?.())};a(m,this,requestAnimationFrame(b))}function j(){i(m,this)!==null&&(cancelAnimationFrame(i(m,this)),a(m,this,null)),i(p,this)!==null&&(cancelAnimationFrame(i(p,this)),a(p,this,null))}u.styles=T`
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
  `;g([_({type:Number})],u.prototype,"progress",void 0);g([_({type:Boolean})],u.prototype,"failed",void 0);g([_({type:String})],u.prototype,"color",void 0);g([_({type:String,attribute:"bg-color"})],u.prototype,"bgColor",void 0);g([_({type:String,attribute:"fail-color"})],u.prototype,"failColor",void 0);g([_({type:String})],u.prototype,"label",void 0);g([_({type:Boolean,attribute:"auto-complete"})],u.prototype,"autoComplete",void 0);customElements.get("craft-progress")||customElements.define("craft-progress",u);const B={Waiting:1,Reserved:2,Done:3,Failed:4};var at=Object.defineProperty,rt=Object.getOwnPropertyDescriptor,D=t=>{throw TypeError(t)},V=(t,e,s,n)=>{for(var c=n>1?void 0:n?rt(e,s):e,b=t.length-1,M;b>=0;b--)(M=t[b])&&(c=(n?M(e,s,c):M(c))||c);return n&&c&&at(e,s,c),c},G=(t,e,s)=>e.has(t)||D("Cannot "+s),P=(t,e,s)=>(G(t,e,"read from private field"),s?s.call(t):e.get(t)),R=(t,e,s)=>e.has(t)?D("Cannot add the same private member more than once"):e instanceof WeakSet?e.add(t):e.set(t,s),Q=(t,e,s)=>(G(t,e,"access private method"),s),W,f,$,N,H,K;let S=class extends L{constructor(){super(...arguments),R(this,f),this.displayedJob=null,R(this,W,t=>{console.log("handling update"),this.displayedJob=t.detail.displayedJob,Q(this,f,$).call(this)})}connectedCallback(){super.connectedCallback(),window.Craft?.$queue?.addEventListener("job-update",P(this,W)),Q(this,f,$).call(this)}disconnectedCallback(){super.disconnectedCallback(),window.Craft?.$queue?.removeEventListener("job-update",P(this,W))}render(){if(!this.displayedJob)return I;const t=P(this,f,K);return J`
      <craft-nav-item .url=${t}>
        <craft-progress
          slot="prefix"
          progress=${P(this,f,N)}
          ?failed=${P(this,f,H)}
          label=${this.displayedJob.description||"Queue"}
        ></craft-progress>
        <div class="label">
          <span class="title">${this.displayedJob.description}</span>
          ${this.displayedJob.progressLabel?J`<span class="progress-label"
                >${this.displayedJob.progressLabel}</span
              >`:I}
        </div>
      </craft-nav-item>
    `}};W=new WeakMap;f=new WeakSet;$=function(){this.displayedJob?this.setAttribute("visible",""):this.removeAttribute("visible")};N=function(){return this.displayedJob?this.displayedJob.status.value===B.Failed?100:this.displayedJob.progress??0:0};H=function(){return this.displayedJob?.status.value===B.Failed};K=function(){return window.Craft?.$queue?.canAccessQueueManager?window.Craft?.getUrl?window.Craft.getUrl("utilities/queue-manager"):"/admin/utilities/queue-manager":null};S.styles=T`
    :host {
      display: contents;
    }

    :host(:not([visible])) {
      display: none;
    }

    .progress-label {
      font-size: 0.85em;
      opacity: 0.7;
    }
  `;V([tt()],S.prototype,"displayedJob",2);S=V([Z("cp-queue-indicator")],S);const ot=S,pt=Object.freeze(Object.defineProperty({__proto__:null,default:ot},Symbol.toStringTag,{value:"Module"}));export{pt as C,B as J,h as _,i as a,a as b};
