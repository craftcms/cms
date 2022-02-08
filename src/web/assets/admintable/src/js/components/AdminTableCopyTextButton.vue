<template>
  <div></div>
</template>

<script>
    /* global Craft */
    /* global $ */

    export default {
        name: 'AdminTableCopyTextButton',

        props: {
            value: String,
        },

        mounted() {
          $(this.$el).html(Craft.ui.createCopyTextBtn({
            value: this.value,
            class: 'code small light copytextbtn-expand-r',
          }));
        }
    }
</script>

<style scoped>

</style>