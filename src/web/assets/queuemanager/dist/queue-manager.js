
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
        axios.get(Craft.getActionUrl('queue/get-full-jobs')).then(this.handleDataResponse, function(response) {
            Craft.cp.displayError(response.message)
        })
    },
    methods: {
        handleDataResponse(response) {
            this.jobs = response.data
            this.loading = false
        },

        retry() {

        },
        cancel() {

        }
    }
})

