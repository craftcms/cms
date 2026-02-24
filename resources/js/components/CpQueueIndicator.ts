import {css, html, LitElement, nothing, type PropertyValues} from 'lit';
import {customElement, property} from 'lit/decorators.js';
import {JobStatus} from '@craftcms/cp/src/types/queue.js';
import type {JobInfo, JobUpdateDetail} from '@craftcms/cp';

import '@craftcms/cp/components/progress/progress.ts';
import {QueueService} from '@craftcms/cp/src/services/Queue.js';
import {ConfigService} from '@craftcms/cp/src/services/Config.js';

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

  @property({type: Object, attribute: 'displayed-job'})
  displayedJob: JobInfo | null = null;
  @property({type: Boolean, attribute: 'has-reserved-jobs'})
  hasReservedJobs: boolean = false;
  @property({type: Boolean, attribute: 'has-waiting-jobs'})
  hasWaitingJobs: boolean = false;

  #queue: QueueService = QueueService.getInstance();
  #config: ConfigService = ConfigService.getInstance();

  override connectedCallback() {
    super.connectedCallback();

    // Use service's displayed job if we don't have one from props
    if (!this.displayedJob) {
      this.displayedJob = this.#queue.displayedJob;
    }

    this.#queue.addEventListener(
      'job-update',
      this.#handleJobUpdate as EventListener
    );

    // Set initial visibility based on current state
    this.#updateVisibility();
    // Either track or run the queue based on initial state
    this.#updateQueue();
  }

  override disconnectedCallback() {
    super.disconnectedCallback();
    this.#queue.removeEventListener(
      'job-update',
      this.#handleJobUpdate as EventListener
    );
  }

  protected override update(changedProperties: PropertyValues) {
    super.update(changedProperties);

    if (
      changedProperties.has('hasReservedJobs') ||
      changedProperties.has('hasWaitingJobs')
    ) {
      this.#updateQueue();
    }

    if (changedProperties.has('displayedJob')) {
      this.#updateVisibility();
    }
  }

  #handleJobUpdate = (event: CustomEvent<JobUpdateDetail>) => {
    this.displayedJob = event.detail.displayedJob;
  };

  #updateQueue() {
    if (this.hasReservedJobs) {
      this.#queue.startTracking();
    } else if (this.hasWaitingJobs) {
      this.#queue.runQueue();
    }
  }

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
    if (this.#queue.canAccessQueueManager) {
      return null;
    }

    return this.#config.getCpUrl('utilities/queue-manager');
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
