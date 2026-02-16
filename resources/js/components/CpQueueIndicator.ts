import {css, html, LitElement, nothing} from 'lit';
import {customElement, property} from 'lit/decorators.js';
import {JobStatus} from '@craftcms/cp/src/types/queue.js';
import type {JobInfo, JobUpdateDetail} from '@craftcms/cp';

import '@craftcms/cp/components/progress/progress.ts';

@customElement('cp-queue-indicator')
class CpQueueIndicator extends LitElement {
  static override styles = css`
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

  @property({type: Boolean}) enabled: boolean = true;
  @property({type: Object, attribute: 'displayed-job'})
  displayedJob: JobInfo | null = null;
  @property({type: Boolean, attribute: 'has-reserved-jobs'})
  hasReservedJobs: boolean = false;
  @property({type: Boolean, attribute: 'has-waiting-jobs'})
  hasWaitingJobs: boolean = false;

  override connectedCallback() {
    super.connectedCallback();
    window.Cp?.$queue?.addEventListener(
      'job-update',
      this.#handleJobUpdate as EventListener
    );

    if (this.hasReservedJobs) {
      window.Cp?.$queue.startTracking();
    } else if (this.hasWaitingJobs) {
      window.Cp?.$queue.runQueue();
    }

    // Set initial visibility based on current state
    this.#updateVisibility();
  }

  override disconnectedCallback() {
    super.disconnectedCallback();
    window.Cp?.$queue?.removeEventListener(
      'job-update',
      this.#handleJobUpdate as EventListener
    );
  }

  #handleJobUpdate = (event: CustomEvent<JobUpdateDetail>) => {
    this.displayedJob = event.detail.displayedJob;
    this.#updateVisibility();
  };

  #updateVisibility() {
    if (this.displayedJob) {
      this.setAttribute('visible', '');
    } else {
      this.removeAttribute('visible');
    }
  }

  get #progress(): number {
    if (!this.displayedJob) return 0;
    if (this.displayedJob.status.value === JobStatus.Failed) return 100;
    return this.displayedJob.progress ?? 0;
  }

  get #isFailed(): boolean {
    return this.displayedJob?.status.value === JobStatus.Failed;
  }

  get #queueManagerUrl(): string | null {
    if (!window.Cp?.$queue?.canAccessQueueManager) return null;
    if (window.Cp?.getUrl) {
      return window.Cp.getUrl('utilities/queue-manager');
    }
    return '/admin/utilities/queue-manager';
  }

  protected override render() {
    if (!this.displayedJob) {
      return nothing;
    }

    const url = this.#queueManagerUrl;

    return html`
      <craft-nav-item .href=${url}>
        <craft-progress
          slot="prefix"
          progress=${this.#progress}
          ?failed=${this.#isFailed}
          label=${this.displayedJob.description || 'Queue'}
        ></craft-progress>
        <div class="label">
          <span class="title">${this.displayedJob.description}</span>
          ${this.displayedJob.progressLabel
            ? html`<span class="progress-label"
                >${this.displayedJob.progressLabel}</span
              >`
            : nothing}
        </div>
      </craft-nav-item>
    `;
  }
}

export default CpQueueIndicator;
