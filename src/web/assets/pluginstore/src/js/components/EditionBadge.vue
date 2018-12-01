<template>
    <div class="edition-badge">{{name}}</div>
</template>

<script>
    export default {

        props: ['name']

    }
</script>

<style lang="scss" scoped>
    @import "../../../../../../../lib/craftcms-sass/mixins";

    .edition-badge {
        @apply .inline-block;
        margin: 0 auto 24px !important;
        font-weight: 500;
        font-size: 14px;
        color: $submitColor;
        letter-spacing: 3.3px;
        line-height: 14px;
        border: 1px solid $submitColor;
        border-radius: 3px;
        padding: 8px 6.7px 8px 9px;
        text-transform: uppercase;
    }
</style>