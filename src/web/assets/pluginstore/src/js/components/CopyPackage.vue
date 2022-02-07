<template>
    <div class="copy-package">
        <textbox ref="input" class="tw-w-full" :value="plugin.packageName" readonly="readonly" @focus="select" />
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
        @apply tw-flex;

        .c-textbox {
            @apply tw-flex;

            &.c-field {
                @apply tw-mb-0;
            }

            .wrapper {
                @apply tw-flex tw-flex-1;

                input {
                    @apply tw-rounded-r-none tw-font-mono;
                }
            }
        }

        button.c-btn {
            @apply tw-text-black tw-border tw-border-solid tw-border-gray-600 tw-rounded-l-none tw-border-l-0;

            -webkit-box-shadow: none !important;
            box-shadow: none !important;
        }
    }
</style>