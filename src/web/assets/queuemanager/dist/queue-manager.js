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
    el: "#queue-manager-utility",
    delimiters: ['[[',']]'],
    data() {
        return {
            loading: true,
            jobs: [],
            activeJob: null,
            limit: 50
        };
    },

    /**
     * Mounted function
     */
    mounted() {
        this.reIndexJobs()

        window.setInterval(this.reIndexJobs, 2500);
    },

    filters: {
        /**
         * Capitalize a string
         * @param string
         * @returns {string}
         */
        capitalize(string) {
            if (!string) return ''
            string = string.toString()
            return string.charAt(0).toUpperCase() + string.slice(1)
        }
    },
    methods: {
        /**
         * Updates and sets the this.jobs.
         */
        reIndexJobs() {
            this.updateJobs().then(this.handleDataResponse)
        },

        /**
         * Updates the this.jobs
         * @returns {Promise<any>}
         */
        updateJobs() {
            return new Promise((resolve, reject) => {
                axios.get(Craft.getActionUrl('queue/get-job-info', {limit: this.limit})).then(function(response) {
                    resolve(response)
                }, function(response) {
                    Craft.cp.displayError(response.response.data.error)
                    reject(response)
                })
            })
        },

        setActiveJob(job) {
            this.loading = true
            let $this = this

            axios.get(Craft.getActionUrl('queue/get-job-details?id='+job.id+'', {})).then(response => {
                $this.activeJob = response.data
                $this.loading = false
            }, response => {
                Craft.cp.displayError(response.response.data.error)
                reject(response)
            })
        },

        /**
         * A setter for job response data
         * @param response
         */
        handleDataResponse(response) {
            this.jobs = response.data
            this.loading = false
        },

        /**
         * Retries all jobs
         */
        retryAll() {
            if (confirm('Are you sure?')) {
                this.craftPost('queue/retry-all', {}).then(response => {
                    Craft.cp.displayNotice('All jobs will be retried. They will soon show progress.')
                })
            }
        },

        /**
         * Releases all jobs
         */
        releaseAll() {
            if (confirm('Are you sure? This will delete all jobs in the Queue - not just those displayed below.')) {
                this.craftPost('queue/release-all', {}).then(response => {
                    this.jobs = []
                    this.activeJob = null
                    Craft.cp.displayNotice('All jobs released')
                })
            }
        },

        /**
         * Retries a specific job
         * @param job
         */
        retryJob(job) {
            if (confirm('Are you sure?')) {
                this.craftPost('queue/retry', {id: job.id}).then(response => {
                    this.activeJob = null
                    Craft.cp.displayNotice('Job retried. It will be updated soon.')
                })
            }
        },

        /**
         * Releases job
         * @param job
         */
        releaseJob(job) {
            if (confirm('Are you sure?')) {
                this.craftPost('queue/release', {id: job.id}).then(response => {
                    this.quickRemoveJob(job.id)
                    this.activeJob = null
                    Craft.cp.displayNotice('Job released')
                })
            }
        },

        /**
         * Removes a job from the local state (this.jobs)
         * @param jobId
         */
        quickRemoveJob(jobId) {
            let job = this.jobs.find(job => {
                return job.id == jobId
            })

            if (job) {
                this.jobs.splice(
                    this.jobs.indexOf(job),
                    1
                )
            }
        },

        /**
         * Resets an active job so that the index screen is displayed.
         */
        resetActiveJob() {
            this.activeJob = null
        },

        /**
         * Gets a job status code
         *
         * @param job
         * @returns {string}
         */
        jobStatusDeterminer(job) {
            switch (job.status.toString()) {
                case '1':
                    return 'Pending'
                    break;
                case '2':
                    return 'Reserved'
                    break;
                case '3':
                    return 'Done'
                    break;
                case '4':
                    return 'Failed'
                    break;
                default:
                    return 'Unkown status'
            }
        },

        /**
         * Helper for Craft.postActionRequest that incorporates the Promise<> lib.
         * @param action
         * @param params
         * @returns {Promise<any>}
         */
        craftPost(action, params) {
            return new Promise((resolve, reject) => {
                Craft.postActionRequest(action, params, resolve)
            })
        }
    }
})

