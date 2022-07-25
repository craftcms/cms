/** global: Craft */
/** global: Garnish */
import Garnish from '../../../garnish/src';

/**
 * Queue
 */
Craft.Queue = Garnish.Base.extend({
  running: false,
  paused: false,
  pausedForVisibility: false,
  jobs: null,

  get length() {
    return this.jobs.length;
  },

  get isPaused() {
    return this.paused || this.pausedForVisibility;
  },

  init: function () {
    this.jobs = [];

    Garnish.$doc.on('visibilitychange', () => {
      if (this.pausedForVisibility && Craft.isVisible()) {
        this.pausedForVisibility = false;
        this._resume();
      }
    });
  },

  /**
   * Adds a job to the queue.
   * @param {function} job
   * @return {Promise}
   */
  push: function (job) {
    return this._add(job, 'push');
  },

  /**
   * Adds a job to the beginning of the queue.
   * @param {function} job
   * @return {Promise}
   */
  unshift: function (job) {
    return this._add(job, 'unshift');
  },

  pause: function () {
    if (!this.paused) {
      this.paused = true;
      if (!this.pausedForVisibility) {
        this.trigger('pause');
      }
    }
  },

  resume: function () {
    if (this.paused) {
      this.paused = false;
      this._resume();
    }
  },

  /**
   * Adds a job to the queue.
   * @param {function} job
   * @param {string} method
   * @return {Promise}
   * @private
   */
  _add: function (job, method) {
    return new Promise((resolve, reject) => {
      this.jobs[method](() => {
        return new Promise((qResolve, qReject) => {
          job()
            .then((value) => {
              // ...arguments doesn't work here :(
              resolve(value);
              qResolve();
            })
            .catch((value) => {
              // ...arguments doesn't work here :(
              reject(value);
              qReject();
            });
        });
      });

      if (!this.running) {
        this.trigger('beforeRun');
        this.running = true;
        this._exec();
      }
    });
  },

  clear: function () {
    this.jobs.length = 0;
  },

  /**
   * Runs the next job in the queue.
   * @private
   */
  _exec: function () {
    if (!this.jobs.length) {
      this.running = false;
      this.trigger('afterRun');
      return;
    }

    if (!this.pausedForVisibility && !Craft.isVisible()) {
      this.pausedForVisibility = true;
      if (!this.paused) {
        this.trigger('pause');
      }
    }

    if (this.paused || this.pausedForVisibility) {
      return;
    }

    this.trigger('beforeExec');
    const job = this.jobs.shift();
    job().finally(() => {
      this.trigger('afterExec');
      this._exec();
    });
  },

  _resume: function () {
    if (!this.isPaused) {
      this.trigger('resume');
      this._exec();
    }
  },
});

Craft.queue = new Craft.Queue();
