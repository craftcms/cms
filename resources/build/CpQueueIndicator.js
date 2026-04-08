import{f as e,n as t,t as n}from"./Queue-C2kVe8rA.js";import{c as r,f as i,r as a,t as o}from"./lit.js";import{a as s,o as c}from"./decorators.js";import{t as l}from"./queue.js";import{t as u}from"./decorate.js";var d=e({default:()=>p}),f=class extends o{constructor(...e){super(...e),this.displayedJob=null,this.hasReservedJobs=!1,this.hasWaitingJobs=!1}static{this.styles=i`
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
  `}#e=n.getInstance();#t=t.getInstance();connectedCallback(){super.connectedCallback(),this.displayedJob||=this.#e.displayedJob,this.#e.addEventListener(`job-update`,this.#n),this.#i(),this.#r()}disconnectedCallback(){super.disconnectedCallback(),this.#e.removeEventListener(`job-update`,this.#n)}update(e){super.update(e),(e.has(`hasReservedJobs`)||e.has(`hasWaitingJobs`))&&this.#r(),e.has(`displayedJob`)&&this.#i()}#n=e=>{this.displayedJob=e.detail.displayedJob};#r(){this.hasReservedJobs?this.#e.startTracking():this.hasWaitingJobs&&this.#e.runQueue()}#i(){this.displayedJob?this.setAttribute(`visible`,``):this.removeAttribute(`visible`)}get#a(){return this.displayedJob?this.displayedJob.status.value===l.Failed?100:this.displayedJob.progress??0:0}get#o(){return this.displayedJob?.status.value===l.Failed}get#s(){return this.#e.canAccessQueueManager?null:this.#t.getCpUrl(`utilities/queue-manager`)}render(){return this.displayedJob?r`
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
    `:a}};u([s({type:Object,attribute:`displayed-job`})],f.prototype,`displayedJob`,void 0),u([s({type:Boolean,attribute:`has-reserved-jobs`})],f.prototype,`hasReservedJobs`,void 0),u([s({type:Boolean,attribute:`has-waiting-jobs`})],f.prototype,`hasWaitingJobs`,void 0),f=u([c(`cp-queue-indicator`)],f);var p=f;export{d as t};