<template>
    <div class="copy-package">
        <textbox ref="input" class="w-full" :value="plugin.packageName" readonly="readonly" @focus="select" />
        <btn @click="copy"><icon icon="copy" /></btn>
    </div>
</template>

<script>
    export default {
        props: ['plugin'],

        methods: {
            select() {
                this.$refs.input.$refs.input.select()
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