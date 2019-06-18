
new Vue({
    el: "#queue-manager-utility",
    delimiters: ['[[',']]'],
    data() {
        return {
            loading: true,
            jobs: []
        };
    },

    mounted() {
        this.updateJobs()

        window.setInterval(this.updateJobs, 2500);
    },
    methods: {
        updateJobs(notify = false) {
            axios.get(Craft.getActionUrl('queue/get-job-info')).then(this.handleDataResponse, function(response) {
                if (notify) {
                    Craft.cp.displayError(response.message)
                }
            })
        },

        handleDataResponse(response) {
            this.jobs = response.data
            this.loading = false
        },

        retry(job) {
            Craft.postActionRequest('queue/retry', {id: job.id}, this.updateJobs)
        },
        cancel() {

        },

        replaceJob() {

        }
    }
})

