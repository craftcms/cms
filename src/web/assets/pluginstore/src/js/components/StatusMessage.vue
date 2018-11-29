<template>
    <div class="status-message">
        <div>
            <div class="graphic spinner big" :class="{ error: error }"></div>
            <div class="message">{{ message }}</div>
        </div>
    </div>
</template>

<script>
    export default {

        props: ['message', 'error']

    }
</script>

<style lang="scss" scoped>
    .status-message {
        @apply .tw-flex-1 .tw-flex .tw-items-center .justify-center;

        div {
            .graphic {
                @apply .tw-block .tw-mx-auto .tw-mb-6;
            }
            .message {
                @apply .text-center;
            }
        }
    }
</style>