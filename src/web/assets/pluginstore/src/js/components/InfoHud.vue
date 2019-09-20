<template>
    <div class="info-hud">
        <v-popover placement="right">
            <icon icon="info-circle" />

            <template slot="popover">
                <slot></slot>
            </template>
        </v-popover>
    </div>
</template>

<script>
    import Vue from 'vue'
    import VTooltip from 'v-tooltip'

    Vue.use(VTooltip)
    VTooltip.options.autoHide = false

    export default {}
</script>

<style lang="scss">
    @import "../../../../../../../lib/craftcms-sass/mixins";

    .info-hud {
        @apply .inline-block;

        .v-popover {
            @apply .text-grey;
            display: inline-block;
            line-height: 0;

            &:hover, &.open {
                @apply .cursor-pointer .text-blue;
            }

            & > span {
                outline: none;
            }
        }
    }

    .tooltip {
        max-width: 250px;
    }
</style>