import{a as T,i as L,x as P,E as z}from"./lit-element.js";import{t as Y}from"./custom-element.js";import{e as f,r as Z}from"./state.js";import{n as g}from"./property.js";function R(t,e){if(e.has(t))throw TypeError("Cannot initialize the same private elements twice on an object")}function h(t,e,s){R(t,e),e.set(t,s)}function r(t,e,s){if(typeof t=="function"?t===e:t.has(e))return arguments.length<3?e:s;throw TypeError("Private element is not present on this object")}function a(t,e,s){return t.set(r(t,e),s),s}function i(t,e){return t.get(r(t,e))}function tt(t,e){R(t,e),e.add(t)}var l=new WeakMap,m=new WeakMap,x=new WeakMap,A=new WeakMap,E=new WeakMap,d=new WeakMap,b=new WeakMap,C=new WeakMap,c=new WeakMap,y=new WeakMap,w=new WeakMap,o=new WeakSet,u=class extends L{constructor(...t){super(...t),tt(this,o),this.progress=0,this.failed=!1,this.color="currentColor",this.bgColor="#a3afbb",this.failColor="#da5a47",this.label="Progress",this.autoComplete=!1,h(this,l,null),h(this,m,0),h(this,x,0),h(this,A,0),h(this,E,0),h(this,d,0),h(this,b,null),h(this,C,0),h(this,c,null),h(this,y,0),h(this,w,!1)}connectedCallback(){super.connectedCallback(),a(w,this,window.matchMedia("(prefers-reduced-motion: reduce)").matches)}disconnectedCallback(){super.disconnectedCallback(),r(o,this,j).call(this)}firstUpdated(){a(l,this,this.renderRoot.querySelector("canvas")),r(o,this,et).call(this),r(o,this,O).call(this)}updated(t){t.has("progress")?r(o,this,O).call(this):(t.has("color")||t.has("bgColor")||t.has("failColor")||t.has("failed"))&&r(o,this,_).call(this)}get canvas(){return i(l,this)}get prefersReducedMotion(){return i(w,this)}runCompleteAnimation(){return new Promise(t=>{if(i(w,this)){a(d,this,1),i(l,this)&&(i(l,this).style.opacity="0"),r(o,this,_).call(this),t();return}r(o,this,U).call(this,1,()=>{i(l,this)&&(i(l,this).style.transition="opacity 0.4s",i(l,this).style.opacity="0"),setTimeout(t,400)})})}async complete(){await this.runCompleteAnimation(),this.dispatchEvent(new CustomEvent("complete",{bubbles:!0,composed:!0}))}render(){return P`
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
    `}};function et(){let t=getComputedStyle(this),e=parseFloat(t.getPropertyValue("--_size")),s=parseFloat(t.getPropertyValue("--_stroke-width")),n=window.devicePixelRatio>1?2:1;a(m,this,e*n),a(x,this,i(m,this)/2),a(E,this,s*n),a(A,this,(e/2-s/2)*n),i(l,this)&&(i(l,this).width=i(m,this),i(l,this).height=i(m,this))}function O(){if(this.progress>=0&&i(c,this)!==null&&(cancelAnimationFrame(i(c,this)),a(c,this,null),a(C,this,0)),this.progress<0){i(c,this)===null&&r(o,this,it).call(this);return}let t=this.progress/100;if(this.autoComplete&&this.progress>=100&&i(y,this)<100){a(y,this,this.progress),this.complete();return}i(y,this)>0&&this.progress>i(y,this)&&!i(w,this)?r(o,this,U).call(this,t):(a(d,this,t),r(o,this,_).call(this)),a(y,this,this.progress)}function it(){if(i(w,this)){a(d,this,.25),r(o,this,_).call(this);return}let t=()=>{a(C,this,i(C,this)+.05),a(d,this,.15+.1*Math.sin(i(C,this)*3)),r(o,this,_).call(this),a(c,this,requestAnimationFrame(t))};a(c,this,requestAnimationFrame(t))}function _(){let t=i(l,this)?.getContext("2d");if(t){if(t.clearRect(0,0,i(m,this),i(m,this)),this.failed){r(o,this,F).call(this,t,this.failColor,1,0);return}if(r(o,this,F).call(this,t,this.bgColor,1,0),i(d,this)>0){let e=this.progress<0?i(C,this):-Math.PI/2;r(o,this,F).call(this,t,this.color,i(d,this),e)}}}function F(t,e,s,n){t.strokeStyle=e,t.lineWidth=i(E,this),t.lineCap="round",t.beginPath(),t.arc(i(x,this),i(x,this),i(A,this),n,n+s*2*Math.PI),t.stroke()}function U(t,e){r(o,this,j).call(this);let s=performance.now(),n=i(d,this),p=k=>{let $=k-s,q=Math.min($/500,1),X=1-(1-q)**3;a(d,this,n+(t-n)*X),r(o,this,_).call(this),q<1?a(b,this,requestAnimationFrame(p)):(a(b,this,null),e?.())};a(b,this,requestAnimationFrame(p))}function j(){i(b,this)!==null&&(cancelAnimationFrame(i(b,this)),a(b,this,null)),i(c,this)!==null&&(cancelAnimationFrame(i(c,this)),a(c,this,null))}u.styles=T`
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
  `,f([g({type:Number})],u.prototype,"progress",void 0),f([g({type:Boolean})],u.prototype,"failed",void 0),f([g({type:String})],u.prototype,"color",void 0),f([g({type:String,attribute:"bg-color"})],u.prototype,"bgColor",void 0),f([g({type:String,attribute:"fail-color"})],u.prototype,"failColor",void 0),f([g({type:String})],u.prototype,"label",void 0),f([g({type:Boolean,attribute:"auto-complete"})],u.prototype,"autoComplete",void 0),customElements.get("craft-progress")||customElements.define("craft-progress",u);const D={Waiting:1,Reserved:2,Done:3,Failed:4};var st=Object.defineProperty,at=Object.getOwnPropertyDescriptor,V=t=>{throw TypeError(t)},B=(t,e,s,n)=>{for(var p=n>1?void 0:n?at(e,s):e,k=t.length-1,$;k>=0;k--)($=t[k])&&(p=(n?$(e,s,p):$(p))||p);return n&&p&&st(e,s,p),p},G=(t,e,s)=>e.has(t)||V("Cannot "+s),M=(t,e,s)=>(G(t,e,"read from private field"),s?s.call(t):e.get(t)),I=(t,e,s)=>e.has(t)?V("Cannot add the same private member more than once"):e instanceof WeakSet?e.add(t):e.set(t,s),Q=(t,e,s)=>(G(t,e,"access private method"),s),W,v,S,N,H,K;let J=class extends L{constructor(){super(...arguments),I(this,v),this.displayedJob=null,I(this,W,t=>{console.log("handling update"),this.displayedJob=t.detail.displayedJob,Q(this,v,S).call(this)})}connectedCallback(){super.connectedCallback(),window.Craft?.$queue?.addEventListener("job-update",M(this,W)),Q(this,v,S).call(this)}disconnectedCallback(){super.disconnectedCallback(),window.Craft?.$queue?.removeEventListener("job-update",M(this,W))}render(){if(!this.displayedJob)return z;const t=M(this,v,K);return P`
      <craft-nav-item .url=${t}>
        <craft-progress
          slot="prefix"
          progress=${M(this,v,N)}
          ?failed=${M(this,v,H)}
          label=${this.displayedJob.description||"Queue"}
        ></craft-progress>
        <div class="label">
          <span class="title">${this.displayedJob.description}</span>
          ${this.displayedJob.progressLabel?P`<span class="progress-label"
                >${this.displayedJob.progressLabel}</span
              >`:z}
        </div>
      </craft-nav-item>
    `}};W=new WeakMap;v=new WeakSet;S=function(){this.displayedJob?this.setAttribute("visible",""):this.removeAttribute("visible")};N=function(){return this.displayedJob?this.displayedJob.status.value===D.Failed?100:this.displayedJob.progress??0:0};H=function(){return this.displayedJob?.status.value===D.Failed};K=function(){return window.Craft?.$queue?.canAccessQueueManager?window.Craft?.getUrl?window.Craft.getUrl("utilities/queue-manager"):"/admin/utilities/queue-manager":null};J.styles=T`
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
  `;B([Z()],J.prototype,"displayedJob",2);J=B([Y("cp-queue-indicator")],J);const rt=J,pt=Object.freeze(Object.defineProperty({__proto__:null,default:rt},Symbol.toStringTag,{value:"Module"}));export{pt as C,D as J,i,a as r,h as t};
