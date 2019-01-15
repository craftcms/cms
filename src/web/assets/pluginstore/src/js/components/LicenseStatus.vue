<template>
    <div :class="'license-status ' + status" data-icon="check">{{ description }}</div>
</template>

<script>
    export default {

        props: ['status', 'description']

    }
</script>

<style lang="scss" scoped>
    @import "../../../../../../../lib/craftcms-sass/mixins";

    .license-status {
        &:before {
            border: 1px solid;
            border-radius: 9px;
            width: 17px;
            height: 17px;
            line-height: 17px;
            margin-right: 5px;
        }

        &.installed {
            color: $successColor;

            &:before {
                border-color: $successColor;
            }
        }

        &.licensed {
            color: $noticeColor;

            &:before {
                border-color: $noticeColor;
            }
        }
    }
</style>