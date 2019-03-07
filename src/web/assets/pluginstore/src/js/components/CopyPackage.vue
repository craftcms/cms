<template>
    <div class="copy-package">
        <input ref="input" type="text" class="text w-full" :value="plugin.packageName" @focus="select" readonly="readonly" />
        <btn @click="copy"><icon icon="copy" /></btn>
    </div>
</template>

<script>
    export default {
        props: ['plugin'],

        methods: {
            select() {
                this.$refs.input.select()
            },

            copy() {
                this.select();

                window.document.execCommand('copy');
            },
        }
    }
</script>

<style lang="scss">
    .copy-package {
        @apply .flex;

        input {
            @apply .font-mono;
            border-top-right-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
        }

        button {
            @apply .text-black .border .rounded-l-none .border-l-0;
        }
    }
</style>