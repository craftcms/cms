
new Vue({
    el: "#queue-manager-utility",
    delimiters: ['[[',']]'],
    data() {
        return {
            loading: true,
            jobs: [],
        };
    },

    mounted() {
        this.updateJobs().then(this.handleDataResponse)

        window.setInterval(this.reIndexJobs, 2500);
    },
    methods: {
        reIndexJobs() {
            this.updateJobs().then(this.handleDataResponse)
        },
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

        handleDataResponse(response) {
            this.jobs = response.data
            this.loading = false
        },

        retryAll() {
            if (confirm('Are you sure?')) {
                this.craftPost('queue/retry-all', {}).then(function(response) {
                    Craft.cp.displayNotice('All jobs will be retried. They will soon show progress.')
                })
            }
        },
        releaseAll() {
            if (confirm('Are you sure?')) {
                this.craftPost('queue/release-all', {}).then(function(response) {
                    this.jobs = []
                    Craft.cp.displayNotice('All jobs released')
                })
            }
        },
        retryJob(job) {
            if (confirm('Are you sure?')) {
                this.craftPost('queue/retry', {id: job.id}).then(function(response) {
                    Craft.cp.displayNotice('Job retried. It will be updated soon.')
                })
            }
        },

        releaseJob(job) {
            if (confirm('Are you sure?')) {
                this.craftPost('queue/release', {id: job.id}).then(response => {
                    this.quickRemoveJob(job.id)
                    Craft.cp.displayNotice('Job released')
                })
            }
        },

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

