import type {
  JobFailedDetail,
  JobInfo,
  JobStatusKey,
  JobUpdateDetail,
  QueueJobData,
  QueueServiceOptions,
} from './types';
import {JobStatus} from './types';
import axios from 'axios';
// Imports stay relative or bare-package here: this module is also bundled by
// the legacy webpack build (via CP.js), which doesn't know the `@/` alias.
import {ConfigService} from '@craftcms/ui/services/Config';

/**
 * Service for managing queue job tracking.
 *
 * @event job-update - Fired when job data changes
 * @event job-complete - Fired when all jobs are done
 * @event job-failed - Fired when a job fails
 *
 * @example
 * ```ts
 * const queue = QueueService.getInstance();
 * queue.addEventListener('job-update', (e) => {
 *   console.log(e.detail.displayedJob);
 * });
 * queue.startTracking();
 * ```
 */
export class QueueService extends EventTarget {
  static #instance: QueueService | null = null;
  #instanceId = Math.random().toString(36).slice(2);

  // Configuration
  enabled: boolean = true;
  #appId: string = '';
  canAccessQueueManager: boolean = false;
  #runAutomatically: boolean = true;

  // Job state
  totalJobs: number = 0;
  jobInfo: JobInfo[] = [];
  displayedJob: JobInfo | null = null;
  displayedJobUnchangedCount: number = 1;

  // Polling control
  #trackingTimeout: ReturnType<typeof setTimeout> | null = null;
  isTracking: boolean = false;
  #abortController: AbortController | null = null;

  // Cross-tab broadcasting
  #broadcaster: BroadcastChannel | null = null;

  // Configuration service instance
  #config: ConfigService = ConfigService.getInstance();

  /** Get the singleton instance */
  static getInstance(): QueueService {
    if (!QueueService.#instance) {
      QueueService.#instance = new QueueService();
    }
    return QueueService.#instance;
  }

  /** Reset the singleton (mainly for testing) */
  static resetInstance(): void {
    if (QueueService.#instance) {
      QueueService.#instance.stopTracking();
      QueueService.#instance.#broadcaster?.close();
      QueueService.#instance = null;
    }
  }

  initialize(options: QueueServiceOptions = {}) {
    this.#appId = options.appId ?? '';
    this.canAccessQueueManager = options.canAccessQueueManager ?? false;
    this.#runAutomatically = options.runAutomatically ?? true;
    this.#initBroadcaster();
  }

  // ─── Public Methods ──────────────────────────────────────────────────────────

  /**
   * Run the queue and start tracking jobs.
   * Sends a request to execute waiting jobs.
   * Does nothing if runAutomatically is false.
   */
  async runQueue(): Promise<void> {
    if (!this.#runAutomatically) {
      // Just track progress without triggering queue execution
      this.startTracking(false, true);
      return;
    }

    try {
      await axios.post(this.#config.getActionUrl('queue/run'));
    } catch (e: unknown) {
      // Ignore errors - queue might already be running
      console.error(e);
    }

    this.startTracking(false, true);
  }

  /**
   * Start tracking job progress.
   * @param delay - Delay before starting: true for adaptive delay, number for ms, false for immediate
   * @param force - Force tracking even if already tracking
   */
  startTracking(delay: boolean | number = false, force: boolean = false): void {
    if (this.isTracking && !force) {
      return;
    }

    // Clear any existing timeout
    if (this.#trackingTimeout) {
      clearTimeout(this.#trackingTimeout);
      this.#trackingTimeout = null;
    }

    // Determine delay
    let delayMs = 0;
    if (delay === true) {
      delayMs = this.#getAdaptiveDelay();
    } else if (Number.isFinite(delay)) {
      delayMs = Number(delay);
    }

    if (delayMs > 0) {
      this.#trackingTimeout = setTimeout(() => {
        this.#trackJobProgress();
      }, delayMs);
    } else {
      this.#trackJobProgress();
    }
  }

  /** Stop tracking job progress */
  stopTracking(): void {
    this.isTracking = false;

    if (this.#trackingTimeout) {
      clearTimeout(this.#trackingTimeout);
      this.#trackingTimeout = null;
    }

    if (this.#abortController) {
      this.#abortController.abort();
      this.#abortController = null;
    }
  }

  /**
   * Set job data from server response.
   * Used for initial data and cross-tab sync.
   */
  setJobData(data: Array<JobInfo>): void {
    this.totalJobs = data.length;
    this.#setJobInfo(data);
  }

  // ─── Private Methods ─────────────────────────────────────────────────────────

  #initBroadcaster(): void {
    if (!('BroadcastChannel' in globalThis) || !this.#appId) {
      return;
    }

    const channelName = `CraftCMS:${this.#appId}:queue`;
    this.#broadcaster = new BroadcastChannel(channelName);

    this.#broadcaster.addEventListener('message', (ev) => {
      this.#handleBroadcastMessage(ev.data);
    });
  }

  #handleBroadcastMessage(data: {
    event: string;
    instanceId?: string;
    jobData?: QueueJobData;
  }): void {
    // Ignore messages from our own tab
    if (data.instanceId === this.#instanceId) return;

    switch (data.event) {
      case 'beforeTrackJobProgress':
        // Another tab is taking over tracking
        this.stopTracking();
        break;

      case 'trackJobProgress':
        // Another tab finished polling - use their data
        if (data.jobData) {
          this.setJobData(data.jobData.jobs);
        }
        // Schedule our next poll with extra delay to avoid conflicts
        if (this.displayedJob !== null) {
          const delay = this.#getAdaptiveDelay() + 1000;
          this.startTracking(delay);
        }
        break;
    }
  }

  #broadcast(event: string, data?: {jobData: QueueJobData}): void {
    this.#broadcaster?.postMessage({
      event,
      instanceId: this.#instanceId,
      ...data,
    });
  }

  #getAdaptiveDelay(): number {
    // Start at 500ms, increase by 500ms for each unchanged poll, cap at 60s
    return Math.min(60000, this.displayedJobUnchangedCount * 500);
  }

  async #trackJobProgress(): Promise<void> {
    // Notify other tabs we're taking over
    this.#broadcast('beforeTrackJobProgress');

    this.isTracking = true;
    this.#abortController = new AbortController();

    try {
      const response = await axios.get<QueueJobData>(
        this.#config.getActionUrl('queue/get-job-info'),
        {
          params: {dontExtendSession: 1},
          signal: this.#abortController.signal,
        }
      );

      this.setJobData(response.data.jobs);

      // Broadcast to other tabs
      this.#broadcast('trackJobProgress', {jobData: response.data});

      // Continue polling while there's an active job to display
      if (this.displayedJob !== null) {
        this.startTracking(true, true);
      }
    } catch (error) {
      // Ignore aborted requests
      if (error instanceof Error && error.name === 'CanceledError') {
        return;
      }

      // For auth errors, stop tracking - user needs to log in
      // SAFETY: Queue requests reject with Axios errors carrying an optional response status.
      const axiosError = error as {response?: {status: number}};
      if (
        axiosError.response?.status === 400 ||
        axiosError.response?.status === 403
      ) {
        this.stopTracking();
        return;
      }

      // For other errors, retry with delay if there's still an active job
      if (this.displayedJob !== null) {
        this.startTracking(true, true);
      }
    } finally {
      this.isTracking = false;
      this.#abortController = null;
    }
  }

  #displayedJobEqual(a: JobInfo | null, b: JobInfo | null): boolean {
    if (a === null && b === null) return true;
    if (a === null || b === null) return false;
    return (
      a.id === b.id &&
      a.progress === b.progress &&
      a.progressLabel === b.progressLabel &&
      a.status === b.status
    );
  }

  #setJobInfo(jobs: JobInfo[]): void {
    const oldDisplayedJob = this.displayedJob;

    this.jobInfo = jobs;
    this.displayedJob = this.#selectDisplayedJob();

    if (this.#displayedJobEqual(oldDisplayedJob, this.displayedJob)) {
      this.displayedJobUnchangedCount++;
    } else {
      this.displayedJobUnchangedCount = 1;
    }

    // Emit events
    this.#emitJobUpdate();

    // Check for failed jobs
    if (this.displayedJob?.status.value === JobStatus.Failed) {
      this.#emitJobFailed(this.displayedJob);
    }

    // Check for completion
    if (this.displayedJob === null && oldDisplayedJob) {
      this.#emitJobComplete();
    }
  }

  #selectDisplayedJob(): JobInfo | null {
    if (this.jobInfo?.length === 0) {
      return null;
    }

    // Priority: Reserved > Failed > Waiting (non-delayed)
    const priorities: JobStatusKey[] = [
      JobStatus.Reserved,
      JobStatus.Failed,
      JobStatus.Pending,
    ];

    for (const status of priorities) {
      const job = this.jobInfo.find((j) => {
        if (j.status.value !== status) {
          return false;
        }

        // Skip delayed waiting jobs
        return !(status === JobStatus.Pending && j.delay > 0);
      });

      if (job) {
        return job;
      }
    }

    return null;
  }

  #emitJobUpdate(): void {
    const detail: JobUpdateDetail = {
      totalJobs: this.totalJobs,
      jobInfo: this.jobInfo,
      displayedJob: this.displayedJob,
    };
    this.dispatchEvent(new CustomEvent('job-update', {detail}));
  }

  #emitJobComplete(): void {
    this.dispatchEvent(new CustomEvent('job-complete'));
  }

  #emitJobFailed(job: JobInfo): void {
    const detail: JobFailedDetail = {job};
    this.dispatchEvent(new CustomEvent('job-failed', {detail}));
  }
}
