import{e as k,n as c,f as M,Q as E,g as I,E as _,x as g,J as C,h as O}from"./cp3.js";var S=Object.defineProperty,j=Object.getOwnPropertyDescriptor,Q=e=>{throw TypeError(e)},l=(e,s,t,d)=>{for(var n=d>1?void 0:d?j(s,t):s,b=e.length-1,f;b>=0;b--)(f=e[b])&&(n=(d?f(s,t,n):f(n))||n);return d&&n&&S(s,t,n),n},W=(e,s,t)=>s.has(e)||Q("Cannot "+t),a=(e,s,t)=>(W(e,s,"read from private field"),t?t.call(e):s.get(e)),p=(e,s,t)=>s.has(e)?Q("Cannot add the same private member more than once"):s instanceof WeakSet?s.add(e):s.set(e,t),h=(e,s,t)=>(W(e,s,"access private method"),t),r,J,u,i,v,y,w,$,m;let o=class extends M{constructor(){super(...arguments),p(this,i),this.enabled=!0,this.displayedJob=null,this.hasReservedJobs=!1,this.hasWaitingJobs=!1,p(this,r,E.getInstance()),p(this,J,I.getInstance()),p(this,u,e=>{this.displayedJob=e.detail.displayedJob})}connectedCallback(){super.connectedCallback(),this.displayedJob||(this.displayedJob=a(this,r).displayedJob),a(this,r).addEventListener("job-update",a(this,u)),h(this,i,y).call(this),h(this,i,v).call(this)}disconnectedCallback(){super.disconnectedCallback(),a(this,r).removeEventListener("job-update",a(this,u))}update(e){super.update(e),(e.has("hasReservedJobs")||e.has("hasWaitingJobs"))&&h(this,i,v).call(this),e.has("displayedJob")&&h(this,i,y).call(this)}render(){if(!this.displayedJob)return _;const e=a(this,i,m);return g`
      <craft-nav-item .href=${e}>
        <craft-progress
          slot="prefix"
          progress=${a(this,i,w)}
          ?failed=${a(this,i,$)}
          label=${this.displayedJob.description||"Queue"}
        ></craft-progress>
        <div class="label">
          <span class="title">${this.displayedJob.description}</span>
          ${this.displayedJob.progressLabel?g`<span class="progress-label"
                >${this.displayedJob.progressLabel}</span
              >`:_}
        </div>
      </craft-nav-item>
    `}};r=new WeakMap;J=new WeakMap;u=new WeakMap;i=new WeakSet;v=function(){this.hasReservedJobs?a(this,r).startTracking():this.hasWaitingJobs&&a(this,r).runQueue()};y=function(){this.displayedJob?this.setAttribute("visible",""):this.removeAttribute("visible")};w=function(){return this.displayedJob?this.displayedJob.status.value===C.Failed?100:this.displayedJob.progress??0:0};$=function(){return this.displayedJob?.status.value===C.Failed};m=function(){return a(this,r).canAccessQueueManager?null:a(this,J).getCpUrl("utilities/queue-manager")};o.styles=k`
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
  `;l([c({type:Boolean})],o.prototype,"enabled",2);l([c({type:Object,attribute:"displayed-job"})],o.prototype,"displayedJob",2);l([c({type:Boolean,attribute:"has-reserved-jobs"})],o.prototype,"hasReservedJobs",2);l([c({type:Boolean,attribute:"has-waiting-jobs"})],o.prototype,"hasWaitingJobs",2);o=l([O("cp-queue-indicator")],o);const x=o;export{x as default};
