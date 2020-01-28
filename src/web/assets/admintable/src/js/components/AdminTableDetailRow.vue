<template>
  <div v-if="rowData.detail.content" v-html="rowData.detail.content"></div>
</template>

<script>
    export default {
        name: 'AdminTableDeleteButton',

        props: {
            rowData: {
                type: Object,
                required: true,
            },
            rowIndex: {
                type: Number
            },
            options: {
                type: Object
            }
        },

        data() {
            return {}
        },
    }
</script>

<style scoped>

</style>