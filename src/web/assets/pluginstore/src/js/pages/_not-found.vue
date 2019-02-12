<template>
    <status-message :error="true" :message="message"></status-message>
</template>

<script>
    import StatusMessage from '../components/StatusMessage'

    export default {

        components: {
            StatusMessage
        },

        computed: {
            message() {
                return this.$options.filters.t("Page not found.", 'app')
            }
        }
    }
</script>