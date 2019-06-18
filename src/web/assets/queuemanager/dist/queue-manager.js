/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

new Vue({
    el: "#queue-manager-utility",
    delimiters: ['[[',']]'],
    data() {
        return {
            loading: true,
            jobs: [],
            modal: null,
            activeJob: null,
        };
    },

    /**
     * Mounted function
     */
    mounted() {
        this.modal = new Garnish.Modal(this.$refs.detailmodal, {
            autoShow: false,
            resizable: true,
            onHide() {
                $this.$emit('update:show', false)
            }
        })

        this.updateJobs().then(this.handleDataResponse)

        window.setInterval(this.reIndexJobs, 2500);
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
            return new Promise(function(resolve, reject) {
                axios.get(Craft.getActionUrl('queue/get-job-info')).then(function(response) {
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

            axios.get(Craft.getActionUrl('queue/get-job-details?id='+job.id+'', {})).then(function(response) {
                $this.activeJob = response.data
                $this.loading = false
            }, function(response) {
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
                this.craftPost('queue/retry-all', {}).then(function(response) {
                    Craft.cp.displayNotice('All jobs will be retried. They will soon show progress.')
                })
            }
        },

        /**
         * Releases all jobs
         */
        releaseAll() {
            if (confirm('Are you sure?')) {
                this.craftPost('queue/release-all', {}).then(function(response) {
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
                this.craftPost('queue/retry', {id: job.id}).then(function(response) {
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
            let job = this.jobs.find(function(job) {
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
            return new Promise(function(resolve, reject) {
                Craft.postActionRequest(action, params, resolve)
            })
        }
    }
})

