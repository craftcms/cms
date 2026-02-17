import type {
  JobFailedDetail,
  JobInfo,
  JobStatusKey,
  JobUpdateDetail,
  QueueJobData,
  QueueServiceOptions,
} from '@src/types';
import {JobStatus} from '@src/types';
import axios from 'axios';

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
    this.enabled = options.enabled ?? true;
    this.#appId = options.appId ?? '';
    this.canAccessQueueManager = options.canAccessQueueManager ?? false;
    this.#initBroadcaster();
  }

  // ─── Public Methods ──────────────────────────────────────────────────────────

  /**
   * Run the queue and start tracking jobs.
   * Sends a request to execute waiting jobs.
   */
  async runQueue(): Promise<void> {
    if (!this.enabled) {
      return;
    }

    try {
      const response = await axios.post('/admin/actions/queue/run');
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
    if (!this.enabled) {
      return;
    }

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
    } else if (typeof delay === 'number') {
      delayMs = delay;
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
    if (typeof BroadcastChannel === 'undefined' || !this.#appId) {
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
        if (this.jobInfo.length > 0) {
          const delay = this.#getAdaptiveDelay() + 1000;
          this.startTracking(delay);
        }
        break;
    }
  }

  #broadcast(event: string, data?: object): void {
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
    if (!this.enabled) return;

    // Notify other tabs we're taking over
    this.#broadcast('beforeTrackJobProgress');

    this.isTracking = true;
    this.#abortController = new AbortController();

    try {
      const response = await axios.get<QueueJobData>(
        '/admin/actions/queue/get-job-info',
        {
          params: {dontExtendSession: 1},
          signal: this.#abortController.signal,
        }
      );

      this.setJobData(response.data.jobs);

      // Broadcast to other tabs
      this.#broadcast('trackJobProgress', {jobData: response.data});

      // Continue polling if there are jobs
      if (this.jobInfo.length > 0) {
        this.startTracking(true, true);
      }
    } catch (error) {
      // Ignore aborted requests
      if (error instanceof Error && error.name === 'CanceledError') {
        return;
      }

      // For auth errors, stop tracking - user needs to log in
      const axiosError = error as {response?: {status: number}};
      if (
        axiosError.response?.status === 400 ||
        axiosError.response?.status === 403
      ) {
        this.stopTracking();
        return;
      }

      // For other errors, retry with delay
      this.startTracking(true, true);
    } finally {
      this.isTracking = false;
      this.#abortController = null;
    }
  }

  #setJobInfo(jobs: JobInfo[]): void {
    const oldDisplayedJob = this.displayedJob;

    this.jobInfo = jobs;
    this.displayedJob = this.#selectDisplayedJob();

    // Track if displayed job changed for adaptive delay
    if (
      oldDisplayedJob &&
      this.displayedJob &&
      oldDisplayedJob.id === this.displayedJob.id &&
      oldDisplayedJob.progress === this.displayedJob.progress &&
      oldDisplayedJob.progressLabel === this.displayedJob.progressLabel &&
      oldDisplayedJob.status === this.displayedJob.status
    ) {
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
    if (this.jobInfo.length === 0 && oldDisplayedJob) {
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
