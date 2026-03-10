import{f as k,n as y,g as M,h as j,s as x,E as _,x as g,J as C,j as E}from"./cp3.js";var I=Object.defineProperty,O=Object.getOwnPropertyDescriptor,W=e=>{throw TypeError(e)},u=(e,s,t,l)=>{for(var n=l>1?void 0:l?O(s,t):s,c=e.length-1,b;c>=0;c--)(b=e[c])&&(n=(l?b(s,t,n):b(n))||n);return l&&n&&I(s,t,n),n},$=(e,s,t)=>s.has(e)||W("Cannot "+t),a=(e,s,t)=>($(e,s,"read from private field"),t?t.call(e):s.get(e)),d=(e,s,t)=>s.has(e)?W("Cannot add the same private member more than once"):s instanceof WeakSet?s.add(e):s.set(e,t),p=(e,s,t)=>($(e,s,"access private method"),t),r,J,h,i,f,v,w,m,Q;let o=class extends M{constructor(){super(...arguments),d(this,i),this.displayedJob=null,this.hasReservedJobs=!1,this.hasWaitingJobs=!1,d(this,r,j.getInstance()),d(this,J,x.getInstance()),d(this,h,e=>{this.displayedJob=e.detail.displayedJob})}connectedCallback(){super.connectedCallback(),this.displayedJob||(this.displayedJob=a(this,r).displayedJob),a(this,r).addEventListener("job-update",a(this,h)),p(this,i,v).call(this),p(this,i,f).call(this)}disconnectedCallback(){super.disconnectedCallback(),a(this,r).removeEventListener("job-update",a(this,h))}update(e){super.update(e),(e.has("hasReservedJobs")||e.has("hasWaitingJobs"))&&p(this,i,f).call(this),e.has("displayedJob")&&p(this,i,v).call(this)}render(){if(!this.displayedJob)return _;const e=a(this,i,Q);return g`
      <craft-nav-item .href=${e}>
        <craft-progress
          slot="prefix"
          progress=${a(this,i,w)}
          ?failed=${a(this,i,m)}
          label=${this.displayedJob.description||"Queue"}
        ></craft-progress>
        <div class="label">
          <span class="title">${this.displayedJob.description}</span>
          ${this.displayedJob.progressLabel?g`<span class="progress-label"
                >${this.displayedJob.progressLabel}</span
              >`:_}
        </div>
      </craft-nav-item>
    `}};r=new WeakMap;J=new WeakMap;h=new WeakMap;i=new WeakSet;f=function(){this.hasReservedJobs?a(this,r).startTracking():this.hasWaitingJobs&&a(this,r).runQueue()};v=function(){this.displayedJob?this.setAttribute("visible",""):this.removeAttribute("visible")};w=function(){return this.displayedJob?this.displayedJob.status.value===C.Failed?100:this.displayedJob.progress??0:0};m=function(){return this.displayedJob?.status.value===C.Failed};Q=function(){return a(this,r).canAccessQueueManager?null:a(this,J).getCpUrl("utilities/queue-manager")};o.styles=k`
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
  `;u([y({type:Object,attribute:"displayed-job"})],o.prototype,"displayedJob",2);u([y({type:Boolean,attribute:"has-reserved-jobs"})],o.prototype,"hasReservedJobs",2);u([y({type:Boolean,attribute:"has-waiting-jobs"})],o.prototype,"hasWaitingJobs",2);o=u([E("cp-queue-indicator")],o);const A=o;export{A as default};
