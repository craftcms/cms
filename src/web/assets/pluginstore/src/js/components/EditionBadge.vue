<template>
    <div class="edition-badge" :class="cssClass">
        <div class="edition-badge-name">{{name}}</div>
    </div>
</template>

<script>
    export default {

        props: ['name', 'block', 'big'],

        computed: {

            cssClass() {
                const cssClasses = {}

                if (typeof this.block !== 'undefined') {
                    cssClasses['is-block'] = true
                }

                if (typeof this.big !== 'undefined') {
                    cssClasses['is-big'] = true
                }

                return cssClasses
            }

        }
    }
</script>

<style lang="scss" scoped>
    @import "../../../../../../../lib/craftcms-sass/mixins";

    .edition-badge {
        @apply .inline-block;

        &.is-block {
            @apply .block;
        }

        &:not(.is-block) {
            @apply .relative;
            top: -1px;
        }

        &.is-big {
            .edition-badge-name {
                @apply .text-base .px-3 py-1;
            }
        }

        .edition-badge-name {
            @apply .inline-block .uppercase .border .border-solid .border-grey .px-2 py-0 .text-grey-darker .rounded .text-xs;
            letter-spacing: 1.5px;
        }
    }
</style>