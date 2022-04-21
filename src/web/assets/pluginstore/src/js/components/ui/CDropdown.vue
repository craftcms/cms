<template>
  <div
    class="c-dropdown"
    :class="{
      'is-invalid': invalid,
      'tw-w-full': fullwidth,
      disabled,
    }"
  >
    <select
      :disabled="disabled"
      :value="value"
      :class="{
        'form-select sm:tw-text-sm sm:tw-leading-5 tw-ps-3 tw-pe-10 tw-rounded-md': true,
        'tw-w-full': fullwidth,
        'tw-border-danger': invalid,
        'tw-border-field': !invalid,
      }"
      @input="$emit('input', $event.target.value)"
    >
      <option v-for="(option, key) in options" :value="option.value" :key="key">
        {{ option.label }}
      </option>
    </select>
  </div>
</template>

<script>
  export default {
    props: {
      disabled: {
        type: Boolean,
        default: false,
      },
      invalid: {
        type: Boolean,
        default: false,
      },
      fullwidth: {
        type: Boolean,
        default: false,
      },
      id: {
        type: String,
        default: function () {
          return 'c-dropdown-id-' + Math.random().toString(36).substring(2, 11);
        },
      },
      options: {
        type: Array,
        default: null,
      },
      value: {
        type: [String, Number],
        default: null,
      },
    },
  };
</script>

<style lang="scss">
  @import '@craftcms/sass/mixins';

  .c-dropdown {
    display: inline-block;
    position: relative;

    &.disabled {
      @apply tw-opacity-50;
    }

    select {
      @apply tw-border-gray-200;

      /*
    TODO

    @include ltr() {
        background-position: right 0.5rem center;
    }

    @include rtl() {
        background-position: left 0.5rem center;
    }
    */
    }
  }
</style>
