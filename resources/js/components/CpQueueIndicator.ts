import {css, html, LitElement, nothing} from 'lit';
import {customElement, state} from 'lit/decorators.js';
import {JobStatus} from '@craftcms/cp/src/types/queue.js';
import type {JobInfo, JobUpdateDetail} from '@craftcms/cp';
import {Craft} from '@/legacy';

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

  @state() private displayedJob: JobInfo | null = null;

  override connectedCallback() {
    super.connectedCallback();
    Craft.$queue.addEventListener(
      'job-update',
      this.#handleJobUpdate as EventListener
    );

    // Set initial visibility based on current state
    this.#updateVisibility();
  }

  override disconnectedCallback() {
    super.disconnectedCallback();
    Craft.$queue.removeEventListener(
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
    if (this.displayedJob.status === JobStatus.Failed) return 100;
    return this.displayedJob.progress ?? 0;
  }

  get #isFailed(): boolean {
    return this.displayedJob?.status === JobStatus.Failed;
  }

  get #queueManagerUrl(): string | null {
    if (!Craft.$queue?.canAccessQueueManager) return null;
    // Use Craft global if available for proper URL generation
    // @ts-ignore
    if (typeof Craft !== 'undefined' && Craft.getUrl) {
      // @ts-ignore
      return Craft.getUrl('utilities/queue-manager');
    }
    return '/admin/utilities/queue-manager';
  }

  protected override render() {
    if (!this.displayedJob) {
      return nothing;
    }

    const url = this.#queueManagerUrl;

    return html`
      <craft-nav-item .url=${url}>
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
