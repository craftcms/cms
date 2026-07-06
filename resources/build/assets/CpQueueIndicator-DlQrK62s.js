import {n as e} from './rolldown-runtime-DXc-PV0M.js';
import {m as t} from './cp-npqTfNqh.js';
import {c as n, f as r, r as i, t as a} from './lit-BpPOIUnZ.js';
import {a as o, o as s} from './decorators-BOwDFZC2.js';
import {t as c} from './decorate-CxrcuNn9.js';
var l = {Pending: 1, Reserved: 2, Done: 3, Failed: 4, Delayed: 5, Cancelled: 6},
  u = e({default: () => f}),
  d = class extends a {
    constructor(...e) {
      (super(...e),
        (this.displayedJob = null),
        (this.hasReservedJobs = !1),
        (this.hasWaitingJobs = !1),
        (this.#e = t.getInstance()),
        (this.#t = (e) => {
          this.displayedJob = e.detail.displayedJob;
        }));
    }
    static {
      this.styles = r`
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
  `;
    }
    #e;
    connectedCallback() {
      (super.connectedCallback(),
        (this.displayedJob ||= this.#e.displayedJob),
        this.#e.addEventListener(`job-update`, this.#t),
        this.#r(),
        this.#n());
    }
    disconnectedCallback() {
      (super.disconnectedCallback(),
        this.#e.removeEventListener(`job-update`, this.#t));
    }
    update(e) {
      (super.update(e),
        (e.has(`hasReservedJobs`) || e.has(`hasWaitingJobs`)) && this.#n(),
        e.has(`displayedJob`) && this.#r());
    }
    #t;
    #n() {
      this.hasReservedJobs
        ? this.#e.startTracking()
        : this.hasWaitingJobs && this.#e.runQueue();
    }
    #r() {
      this.displayedJob
        ? this.setAttribute(`visible`, ``)
        : this.removeAttribute(`visible`);
    }
    get #i() {
      return this.displayedJob
        ? this.displayedJob.status.value === l.Failed
          ? 100
          : (this.displayedJob.progress ?? 0)
        : 0;
    }
    get #a() {
      return this.displayedJob?.status.value === l.Failed;
    }
    get #o() {
      return this.#e.canAccessQueueManager
        ? null
        : window.Craft.getCpUrl(`utilities/queue-manager`);
    }
    render() {
      return this.displayedJob
        ? n`
      <craft-nav-item .href=${this.#o}>
        <craft-progress
          slot="prefix"
          progress=${this.#i}
          ?failed=${this.#a}
          label=${this.displayedJob.description || `Queue`}
        ></craft-progress>
        <div class="label">
          <span class="title">${this.displayedJob.description}</span>
          ${
            this.displayedJob.progressLabel
              ? n`<span class="progress-label"
                >${this.displayedJob.progressLabel}</span
              >`
              : i
          }
        </div>
      </craft-nav-item>
    `
        : i;
    }
  };
(c(
  [o({type: Object, attribute: `displayed-job`})],
  d.prototype,
  `displayedJob`,
  void 0
),
  c(
    [o({type: Boolean, attribute: `has-reserved-jobs`})],
    d.prototype,
    `hasReservedJobs`,
    void 0
  ),
  c(
    [o({type: Boolean, attribute: `has-waiting-jobs`})],
    d.prototype,
    `hasWaitingJobs`,
    void 0
  ),
  (d = c([s(`cp-queue-indicator`)], d)));
var f = d;
export {u as t};
