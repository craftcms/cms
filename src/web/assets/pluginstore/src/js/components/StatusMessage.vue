<template>
    <div class="status-message">
        <div>
            <template v-if="error">
                <icon icon="exclamation-triangle"></icon>
            </template>
            <template v-else>
                <spinner size="lg"></spinner>
            </template>
            <div class="message">{{ message }}</div>
        </div>
    </div>
</template>

<script>
    export default {
        props: ['message', 'error']
    }
</script>

<style lang="scss">
    .status-message {
        @apply .flex-1 .flex .items-center .justify-center .text-center;

        div {
            .c-spinner {
                @apply .mb-6;
            }

            .c-icon {
                @apply .mb-6;

                width: 48px;
                height: 48px;
            }

            .message {
                @apply .text-center;
            }
        }
    }
</style>
