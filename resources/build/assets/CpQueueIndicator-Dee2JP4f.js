import{n as e}from"./rolldown-runtime-DXc-PV0M.js";import{h as t,p as n}from"./cp-DpSwX5ei.js";import{c as r,f as i,r as a,t as o}from"./lit-BpPOIUnZ.js";import{a as s,o as c}from"./decorators-BOwDFZC2.js";import{t as l}from"./decorate-DQXQhzse.js";var u={Pending:1,Reserved:2,Done:3,Failed:4,Delayed:5,Cancelled:6},d=e({default:()=>p}),f=class extends o{constructor(...e){super(...e),this.displayedJob=null,this.hasReservedJobs=!1,this.hasWaitingJobs=!1,this.#e=n.getInstance(),this.#t=t.getInstance(),this.#n=e=>{this.displayedJob=e.detail.displayedJob}}static{this.styles=i`
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
  `}#e;#t;connectedCallback(){super.connectedCallback(),this.displayedJob||=this.#e.displayedJob,this.#e.addEventListener(`job-update`,this.#n),this.#i(),this.#r()}disconnectedCallback(){super.disconnectedCallback(),this.#e.removeEventListener(`job-update`,this.#n)}update(e){super.update(e),(e.has(`hasReservedJobs`)||e.has(`hasWaitingJobs`))&&this.#r(),e.has(`displayedJob`)&&this.#i()}#n;#r(){this.hasReservedJobs?this.#e.startTracking():this.hasWaitingJobs&&this.#e.runQueue()}#i(){this.displayedJob?this.setAttribute(`visible`,``):this.removeAttribute(`visible`)}get#a(){return this.displayedJob?this.displayedJob.status.value===u.Failed?100:this.displayedJob.progress??0:0}get#o(){return this.displayedJob?.status.value===u.Failed}get#s(){return this.#e.canAccessQueueManager?null:this.#t.getCpUrl(`utilities/queue-manager`)}render(){return this.displayedJob?r`
      <craft-nav-item .href=${this.#s}>
        <craft-progress
          slot="prefix"
          progress=${this.#a}
          ?failed=${this.#o}
          label=${this.displayedJob.description||`Queue`}
        ></craft-progress>
        <div class="label">
          <span class="title">${this.displayedJob.description}</span>
          ${this.displayedJob.progressLabel?r`<span class="progress-label"
                >${this.displayedJob.progressLabel}</span
              >`:a}
        </div>
      </craft-nav-item>
    `:a}};l([s({type:Object,attribute:`displayed-job`})],f.prototype,`displayedJob`,void 0),l([s({type:Boolean,attribute:`has-reserved-jobs`})],f.prototype,`hasReservedJobs`,void 0),l([s({type:Boolean,attribute:`has-waiting-jobs`})],f.prototype,`hasWaitingJobs`,void 0),f=l([c(`cp-queue-indicator`)],f);var p=f;export{d as t};