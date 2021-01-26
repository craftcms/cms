/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

/**
 * Vue component for the Queue manager
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
new Vue({
    el: "#main",
    delimiters: ['[[', ']]'],
    data() {
        return {
            loading: false,
            indexTimeout: null,
            jobs: [],
            totalJobs: null,
            totalJobsFormatted: null,
            activeJobId: null,
            activeJob: null,
            limit: 50
        }
    },

    /**
     * Mounted function
     */
    mounted() {
        document.getElementById('queue-manager-utility').removeAttribute('class')

        Craft.cp.on('setJobInfo', () => {
            this.jobs = Craft.cp.jobInfo.slice(0)
            this.totalJobs = Craft.cp.totalJobs
            this.totalJobsFormatted = Craft.formatNumber(this.totalJobs)
            if (!this.loading) {
                this.refreshActiveJob()
            }
        })

        window.onpopstate = (event) => {
            console.log('popstate', event.state)
            if (event.state && event.state.jobId) {
                this.setActiveJob(event.state.jobId, false)
            } else {
                this.clearActiveJob(false)
            }
        }

        // Was a specific job requested?
        let m = Craft.path.match(/utilities\/queue-manager\/([^\/]+)/)
        if (m) {
            let jobId = m[1]
            history.replaceState({jobId: jobId}, '', this.url(jobId))
            this.setActiveJob(jobId, false)
        }
    },

    methods: {
        /**
         * Force-updates the job progress.
         */
        updateJobProgress() {
            Craft.cp.trackJobProgress(false, true)
        },

        /**
         * Sets the active job that should be shown.
         * @param {string} jobId
         * @param {boolean} pushState
         * @return {Promise}
         */
        setActiveJob(jobId, pushState) {
            return new Promise((resolve, reject) => {
                window.clearTimeout(this.indexTimeout)
                this.loading = true
                this.activeJobId = jobId

                if (pushState) {
                    history.pushState({jobId: jobId}, '', this.url(jobId))
                }

                axios.get(Craft.getActionUrl('queue/get-job-details?id=' + jobId + '', {})).then(response => {
                    if (response.data.id != this.activeJobId) {
                        resolve(false)
                        return
                    }
                    this.activeJob = response.data
                    this.loading = false
                    resolve(true)
                }, response => {
                    Craft.cp.displayError(response.response.data.error)
                    reject(response)
                })
            })
        },

        /**
         * Refreshes the active job
         * @return {Promise}
         */
        refreshActiveJob() {
            return new Promise((resolve, reject) => {
                if (!this.activeJobId) {
                    resolve(false)
                    return
                }
                let oldJob = this.activeJob
                this.setActiveJob(this.activeJobId, false).then((success) => {
                    // If it's done now, the response is probably missing critical info about the job
                    if (success && oldJob && this.activeJob.status == 3) {
                        $.extend(oldJob, {
                            progress: 100,
                            status: 3,
                        })
                        delete oldJob.error
                        delete oldJob.progressLabel
                        this.activeJob = oldJob
                    }
                    resolve(success)
                }).catch(reject)
            })
        },

        /**
         * Retries all jobs.
         * @return {Promise}
         */
        retryAll() {
            return new Promise((resolve, reject) => {
                window.clearTimeout(this.indexTimeout)
                this.postActionRequest('queue/retry-all').then(response => {
                    Craft.cp.displayNotice(Craft.t('app', 'Retrying all failed jobs.'))
                    this.updateJobProgress()
                    resolve()
                }).catch(reject)
            })
        },

        /**
         * Releases all jobs.
         * @return {Promise}
         */
        releaseAll() {
            return new Promise((resolve, reject) => {
                if (!confirm(Craft.t('app', 'Are you sure you want to release all jobs in the queue?'))) {
                    resolve(false)
                    return
                }

                this.postActionRequest('queue/release-all').then(response => {
                    Craft.cp.displayNotice(Craft.t('app', 'All jobs released.'))
                    this.clearActiveJob(true)
                    this.updateJobProgress()
                    resolve(true)
                }).catch(reject)
            })
        },

        /**
         * Retries a specific job.
         * @param {Object} job
         * @return {Promise}
         */
        retryJob(job) {
            return new Promise((resolve, reject) => {
                // Only confirm if the job is currently reserved
                if (job.status == 2) {
                    let message = Craft.t('app', 'Are you sure you want to restart the job “{description}”? Any progress could be lost.', {
                        description: job.description
                    })
                    if (!confirm(message)) {
                        resolve(false)
                        return
                    }
                }

                window.clearTimeout(this.indexTimeout)

                this.postActionRequest('queue/retry', {id: job.id}).then(response => {
                    if (job.status == 2) {
                        Craft.cp.displayNotice(Craft.t('app', 'Job restarted.'))
                    } else {
                        Craft.cp.displayNotice(Craft.t('app', 'Job retried.'))
                    }

                    this.updateJobProgress()
                    resolve(true)
                }).catch(reject)
            })
        },

        /**
         * Retries the active job.
         * @return {Promise}
         */
        retryActiveJob() {
            return new Promise((resolve, reject) => {
                this.retryJob(this.activeJob).then(resolve).catch(reject)
            })
        },

        /**
         * Releases a job.
         * @param {Object} job
         * @returns {Promise}
         */
        releaseJob(job) {
            return new Promise((resolve, reject) => {
                let message = Craft.t('app', 'Are you sure you want to release the job “{description}”?', {
                    description: job.description
                })
                if (!confirm(message)) {
                    resolve(false)
                    return
                }
                this.postActionRequest('queue/release', {id: job.id}).then(response => {
                    Craft.cp.displayNotice(Craft.t('app', 'Job released.'))
                    this.updateJobProgress()
                    resolve(true)
                })
            })
        },

        /**
         * Releases the active job.
         * @returns {Promise}
         */
        releaseActiveJob() {
            return new Promise((resolve, reject) => {
                this.releaseJob(this.activeJob).then((released) => {
                    if (released) {
                        this.clearActiveJob(true)
                    }
                    resolve(released)
                }).catch(reject)
            })
        },

        /**
         * Resets an active job so that the index screen is displayed.
         * @param {boolean} pushState
         */
        clearActiveJob(pushState) {
            if (!this.activeJob) {
                return
            }

            this.activeJob = null
            this.activeJobId = null

            if (pushState) {
                history.pushState({}, '', this.url())
            }
        },

        /**
         * Returns a Queue Manager URL.
         * @param {string|null} jobId
         * @returns {string}
         */
        url(jobId) {
            return Craft.getUrl('utilities/queue-manager' + (jobId ? '/' + jobId : ''))
        },

        /**
         * Returns whether a job can be retried.
         * @param {Object} job
         * @returns {boolean}
         */
        isRetryable(job) {
            return job.status == 2 || job.status == 4
        },

        /**
         * Returns the class name a job's status cell should have.
         * @param {number} status
         * @returns {string}
         */
        jobStatusClass(status) {
            if (status == 4) {
                return 'error'
            }
            return ''
        },

        /**
         * Returns a job status code.
         * @param {number} status
         * @returns {string}
         */
        jobStatusLabel(status) {
            switch (status) {
                case 1:
                    return Craft.t('app', 'Pending')
                    break
                case 2:
                    return Craft.t('app', 'Reserved')
                    break
                case 3:
                    return Craft.t('app', 'Finished')
                    break
                case 4:
                    return Craft.t('app', 'Failed')
                    break
                default:
                    return ''
            }
        },

        /**
         * Returns a job status icon class.
         * @param {number} status
         * @returns {string}
         */
        jobStatusIconClass(status) {
            let c = 'status'
            switch (status) {
                case 1:
                    c += ' orange'
                    break
                case 2:
                    c += ' green'
                    break
                case 4:
                    c += ' red'
                    break
            }
            return c
        },

        /**
         * Returns a job attribute name.
         * @param {string} name
         * @returns {string}
         */
        jobAttributeName(name) {
            switch (name) {
                case 'id':
                    return Craft.t('app', 'ID')
                case 'status':
                    return Craft.t('app', 'Status')
                case 'progress':
                    return Craft.t('app', 'Progress')
                case 'description':
                    return Craft.t('app', 'Description')
                case 'ttr':
                    return Craft.t('app', 'Time to reserve')
                case 'error':
                    return Craft.t('app', 'Error')
                default:
                    return name
            }
        },

        /**
         * Formats a TTR value.
         * @param {string} value
         * @return {string}
         */
        ttrValue(value) {
            return Craft.t('app', '{num, number} {num, plural, =1{second} other{seconds}}', {
                num: value
            })
        },

        /**
         * Promise wrapper for `Craft.postActionRequest()`.
         * @param {string} action
         * @param {Object} params
         * @returns {Promise}
         */
        postActionRequest(action, params) {
            return new Promise((resolve, reject) => {
                Craft.postActionRequest(action, params, (response, textStatus) => {
                    if (textStatus !== 'success') {
                        reject()
                        return
                    }
                    resolve(response)
                })
            })
        }
    }
})

