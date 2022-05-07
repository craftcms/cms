<template>
  <a
    ref="button"
    :href="linkHref"
    class="btn"
    :class="buttonClass"
    :data-icon="icon"
    @click="handleClick"
    >{{ label }}</a
  >
</template>

<script>
  export default {
    name: 'AdminTableButton',

    props: {
      btnClass: {
        type: String | Object,
        default: () => {
          return {};
        },
      },
      enabled: {
        type: Boolean | Function,
        default: () => {
          return true;
        },
      },
      href: String,
      label: String,
      icon: String,
    },

    methods: {
      handleClick(event) {
        if (!this.isEnabled) {
          event.preventDefault();
        }
      },
    },

    computed: {
      buttonClass() {
        let isEnabled = this.isEnabled;

        if (typeof this.btnClass == 'string') {
          return this.btnClass + (isEnabled ? '' : ' disabled');
        }

        return Object.assign(this.btnClass, {disabled: !isEnabled});
      },

      isEnabled() {
        return typeof this.enabled == 'function'
          ? this.enabled()
          : this.enabled;
      },

      linkHref() {
        return this.isEnabled ? this.href : '#';
      },
    },
  };
</script>

<style scoped></style>
