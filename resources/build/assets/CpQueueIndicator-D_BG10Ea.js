import{f as e}from"./cp-BGj8ev7d.js";import{c as t,f as n,r,t as i}from"./lit-BpPOIUnZ.js";import{a,o}from"./decorators-BOwDFZC2.js";import{t as s}from"./decorate-CpzDR30L.js";var c={Pending:1,Reserved:2,Done:3,Failed:4,Delayed:5,Cancelled:6},l=class extends i{constructor(...t){super(...t),this.displayedJob=null,this.hasReservedJobs=!1,this.hasWaitingJobs=!1,this.#e=e.getInstance(),this.#t=e=>{this.displayedJob=e.detail.displayedJob}}static{this.styles=n`
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
  `}#e;connectedCallback(){super.connectedCallback(),this.displayedJob||=this.#e.displayedJob,this.#e.addEventListener(`job-update`,this.#t),this.#r(),this.#n()}disconnectedCallback(){super.disconnectedCallback(),this.#e.removeEventListener(`job-update`,this.#t)}update(e){super.update(e),(e.has(`hasReservedJobs`)||e.has(`hasWaitingJobs`))&&this.#n(),e.has(`displayedJob`)&&this.#r()}#t;#n(){this.hasReservedJobs?this.#e.startTracking():this.hasWaitingJobs&&this.#e.runQueue()}#r(){this.displayedJob?this.setAttribute(`visible`,``):this.removeAttribute(`visible`)}get#i(){return this.displayedJob?this.displayedJob.status.value===c.Failed?100:this.displayedJob.progress??0:0}get#a(){return this.displayedJob?.status.value===c.Failed}get#o(){return this.#e.canAccessQueueManager?null:window.Craft.getCpUrl(`utilities/queue-manager`)}render(){return this.displayedJob?t`
      <craft-nav-item .href=${this.#o}>
        <craft-progress
          slot="prefix"
          progress=${this.#i}
          ?failed=${this.#a}
          label=${this.displayedJob.description||`Queue`}
        ></craft-progress>
        <div class="label">
          <span class="title">${this.displayedJob.description}</span>
          ${this.displayedJob.progressLabel?t`<span class="progress-label"
                >${this.displayedJob.progressLabel}</span
              >`:r}
        </div>
      </craft-nav-item>
    `:r}};s([a({type:Object,attribute:`displayed-job`})],l.prototype,`displayedJob`,void 0),s([a({type:Boolean,attribute:`has-reserved-jobs`})],l.prototype,`hasReservedJobs`,void 0),s([a({type:Boolean,attribute:`has-waiting-jobs`})],l.prototype,`hasWaitingJobs`,void 0),l=s([o(`cp-queue-indicator`)],l);var u=l;export{u as t};