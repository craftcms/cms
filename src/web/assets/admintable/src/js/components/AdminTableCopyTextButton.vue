<template>
  <div class="flex">
    <div class="flex-grow"></div>
  </div>
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
          $(this.$el).prepend(Craft.ui.createCopyTextBtn({
            value: this.value,
            class: 'code small light',
          }));
        }
    }
</script>

<style scoped>

</style>