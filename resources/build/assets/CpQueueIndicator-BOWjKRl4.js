import{n as e}from"./rolldown-runtime-DXc-PV0M.js";import{c as t,f as n,r,t as i}from"./lit-BpPOIUnZ.js";import{a,o}from"./decorators-BOwDFZC2.js";import{n as s,t as c}from"./Queue.ts-DEyZUw_x.js";import{t as l}from"./queue-Co4Wx1VG.js";import{t as u}from"./decorate-DeIGM8H9.js";var d=e({default:()=>p}),f=class extends i{constructor(...e){super(...e),this.displayedJob=null,this.hasReservedJobs=!1,this.hasWaitingJobs=!1,this.#e=c.getInstance(),this.#t=s.getInstance(),this.#n=e=>{this.displayedJob=e.detail.displayedJob}}static{this.styles=n`
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
  `}#e;#t;connectedCallback(){super.connectedCallback(),this.displayedJob||=this.#e.displayedJob,this.#e.addEventListener(`job-update`,this.#n),this.#i(),this.#r()}disconnectedCallback(){super.disconnectedCallback(),this.#e.removeEventListener(`job-update`,this.#n)}update(e){super.update(e),(e.has(`hasReservedJobs`)||e.has(`hasWaitingJobs`))&&this.#r(),e.has(`displayedJob`)&&this.#i()}#n;#r(){this.hasReservedJobs?this.#e.startTracking():this.hasWaitingJobs&&this.#e.runQueue()}#i(){this.displayedJob?this.setAttribute(`visible`,``):this.removeAttribute(`visible`)}get#a(){return this.displayedJob?this.displayedJob.status.value===l.Failed?100:this.displayedJob.progress??0:0}get#o(){return this.displayedJob?.status.value===l.Failed}get#s(){return this.#e.canAccessQueueManager?null:this.#t.getCpUrl(`utilities/queue-manager`)}render(){return this.displayedJob?t`
      <craft-nav-item .href=${this.#s}>
        <craft-progress
          slot="prefix"
          progress=${this.#a}
          ?failed=${this.#o}
          label=${this.displayedJob.description||`Queue`}
        ></craft-progress>
        <div class="label">
          <span class="title">${this.displayedJob.description}</span>
          ${this.displayedJob.progressLabel?t`<span class="progress-label"
                >${this.displayedJob.progressLabel}</span
              >`:r}
        </div>
      </craft-nav-item>
    `:r}};u([a({type:Object,attribute:`displayed-job`})],f.prototype,`displayedJob`,void 0),u([a({type:Boolean,attribute:`has-reserved-jobs`})],f.prototype,`hasReservedJobs`,void 0),u([a({type:Boolean,attribute:`has-waiting-jobs`})],f.prototype,`hasWaitingJobs`,void 0),f=u([o(`cp-queue-indicator`)],f);var p=f;export{d as t};