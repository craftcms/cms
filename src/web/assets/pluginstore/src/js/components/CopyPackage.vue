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

        .c-textbox {
            @apply .flex;

            &.c-field {
                @apply .mb-0;
            }

            .wrapper {
                @apply .flex .flex-1;

                input {
                    @apply .rounded-r-none .font-mono;
                }
            }
        }

        button.c-btn {
            @apply .text-black .border .border-solid .border-grey .rounded-l-none .border-l-0;

            -webkit-box-shadow: none !important;
            box-shadow: none !important;
        }
    }
</style>