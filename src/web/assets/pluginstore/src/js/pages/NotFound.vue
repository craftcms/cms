<template>
    <div>
        <div id="graphic" class="spinner big error"></div>
        <div id="status">{{ statusMessage }}</div>
    </div>

</template>

<script>
    export default {
        computed: {
            statusMessage() {
                return this.$options.filters.t("Page not found.", 'app')
            }
        }
    }
</script>