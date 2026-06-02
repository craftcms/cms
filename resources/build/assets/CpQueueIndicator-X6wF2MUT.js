import{f as e,m as t}from"./cp-BKCRQ1VT.js";import{c as n,f as r,r as i,t as a}from"./lit-BpPOIUnZ.js";import{a as o,o as s}from"./decorators-BOwDFZC2.js";import{t as c}from"./decorate-CpzDR30L.js";var l={Pending:1,Reserved:2,Done:3,Failed:4,Delayed:5,Cancelled:6},u=class extends a{constructor(...n){super(...n),this.displayedJob=null,this.hasReservedJobs=!1,this.hasWaitingJobs=!1,this.#e=e.getInstance(),this.#t=t.getInstance(),this.#n=e=>{this.displayedJob=e.detail.displayedJob}}static{this.styles=r`
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
  `}#e;#t;connectedCallback(){super.connectedCallback(),this.displayedJob||=this.#e.displayedJob,this.#e.addEventListener(`job-update`,this.#n),this.#i(),this.#r()}disconnectedCallback(){super.disconnectedCallback(),this.#e.removeEventListener(`job-update`,this.#n)}update(e){super.update(e),(e.has(`hasReservedJobs`)||e.has(`hasWaitingJobs`))&&this.#r(),e.has(`displayedJob`)&&this.#i()}#n;#r(){this.hasReservedJobs?this.#e.startTracking():this.hasWaitingJobs&&this.#e.runQueue()}#i(){this.displayedJob?this.setAttribute(`visible`,``):this.removeAttribute(`visible`)}get#a(){return this.displayedJob?this.displayedJob.status.value===l.Failed?100:this.displayedJob.progress??0:0}get#o(){return this.displayedJob?.status.value===l.Failed}get#s(){return this.#e.canAccessQueueManager?null:this.#t.getCpUrl(`utilities/queue-manager`)}render(){return this.displayedJob?n`
      <craft-nav-item .href=${this.#s}>
        <craft-progress
          slot="prefix"
          progress=${this.#a}
          ?failed=${this.#o}
          label=${this.displayedJob.description||`Queue`}
        ></craft-progress>
        <div class="label">
          <span class="title">${this.displayedJob.description}</span>
          ${this.displayedJob.progressLabel?n`<span class="progress-label"
                >${this.displayedJob.progressLabel}</span
              >`:i}
        </div>
      </craft-nav-item>
    `:i}};c([o({type:Object,attribute:`displayed-job`})],u.prototype,`displayedJob`,void 0),c([o({type:Boolean,attribute:`has-reserved-jobs`})],u.prototype,`hasReservedJobs`,void 0),c([o({type:Boolean,attribute:`has-waiting-jobs`})],u.prototype,`hasWaitingJobs`,void 0),u=c([s(`cp-queue-indicator`)],u);var d=u;export{d as t};