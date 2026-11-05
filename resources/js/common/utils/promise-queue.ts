import {Base} from '@craftcms/garnish';

type Job<T = unknown> = () => Promise<T>;

/**
 * Runs async jobs one at a time, in order.
 *
 * The queue pauses itself while the tab is hidden, so background work doesn't
 * pile up requests nobody is looking at. Triggers `beforeRun`/`afterRun` around
 * a run of jobs, `beforeExec`/`afterExec` around each one, and `pause`/`resume`.
 */
export class PromiseQueue extends Base {
  running = false;
  paused = false;
  pausedForVisibility = false;
  jobs: Job<void>[] = [];

  constructor() {
    super();
    if (new.target === PromiseQueue) {
      this.init();
    }
  }

  init(): void {
    this.addListener(document, 'visibilitychange', () => {
      if (this.pausedForVisibility && isVisible()) {
        this.pausedForVisibility = false;
        this.#resume();
      }
    });
  }

  get length(): number {
    return this.jobs.length;
  }

  get isPaused(): boolean {
    return this.paused || this.pausedForVisibility;
  }

  /** Adds a job to the end of the queue. Resolves with the job's result. */
  push<T>(job: Job<T>): Promise<T> {
    return this.#add(job, 'push');
  }

  /** Adds a job to the front of the queue. Resolves with the job's result. */
  unshift<T>(job: Job<T>): Promise<T> {
    return this.#add(job, 'unshift');
  }

  pause(): void {
    if (!this.paused) {
      this.paused = true;
      if (!this.pausedForVisibility) {
        this.trigger('pause');
      }
    }
  }

  resume(): void {
    if (this.paused) {
      this.paused = false;
      this.#resume();
    }
  }

  clear(): void {
    this.jobs.length = 0;
  }

  #add<T>(job: Job<T>, method: 'push' | 'unshift'): Promise<T> {
    return new Promise<T>((resolve, reject) => {
      // The queue only needs to know when the job settles; the caller gets
      // its outcome through the returned promise. A job that throws
      // synchronously has to settle the same way, or it escapes `#exec()` and
      // leaves the queue running with nothing left to drive it.
      this.jobs[method](() => {
        try {
          return Promise.resolve(job()).then(resolve, reject);
        } catch (error) {
          reject(error);
          return Promise.resolve();
        }
      });

      if (!this.running) {
        this.trigger('beforeRun');
        this.running = true;
        this.#exec();
      }
    });
  }

  #exec(): void {
    if (!this.jobs.length) {
      this.running = false;
      this.trigger('afterRun');
      return;
    }

    if (!this.pausedForVisibility && !isVisible()) {
      this.pausedForVisibility = true;
      if (!this.paused) {
        this.trigger('pause');
      }
    }

    if (this.isPaused) {
      return;
    }

    this.trigger('beforeExec');
    const job = this.jobs.shift()!;
    // Queued wrappers never reject; see `#add()`.
    void job().then(() => {
      this.trigger('afterExec');
      this.#exec();
    });
  }

  #resume(): void {
    if (!this.isPaused) {
      this.trigger('resume');
      this.#exec();
    }
  }
}

function isVisible(): boolean {
  return document.visibilityState !== 'hidden';
}
