<template>
    <div :class="'c-btn block large outline license-status ' + status">
        <icon icon="check"></icon>
        {{ description }}
    </div>
</template>

<script>
    export default {

        props: ['status', 'description']

    }
</script>

<style lang="scss" scoped>
    @import "../../../../../../../lib/craftcms-sass/mixins";

    .license-status {
        &:hover {
            cursor: default;
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