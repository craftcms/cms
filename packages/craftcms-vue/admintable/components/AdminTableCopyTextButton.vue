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
      if (this.value) {
        $(this.$el).html(
          Craft.ui.createCopyTextBtn({
            value: this.value,
            class: 'code light',
          })
        );
      }
    },
  };
</script>

<style scoped></style>
