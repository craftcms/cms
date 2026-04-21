<template>
  <input
    class="tw-rounded-md tw-bg-gray-50 tw-border tw-border-solid tw-px-3 tw-py-2 tw-border-gray-300 tw-text-sm"
    :type="computedType"
    :value="value"
    @blur="$emit('blur', $event)"
    @focus="$emit('focus', $event.target.value)"
    @change="$emit('change', $event)"
    @input="$emit('input', $event.target.value)"
    @keydown="$emit('keydown', $event)"
    @keypress="$emit('keypress', $event)"
    @keyup="$emit('keyup', $event)"
  />
</template>

<script>
  export default {
    name: 'TextboxComponent',
    props: {
      type: {
        type: String,
        default: 'text',
      },
      value: {
        type: [String, Number],
        default: '',
      },
    },

    computed: {
      computedType() {
        if (this.type === 'textarea') {
          return null;
        }
        return this.type;
      },
    },
  };
</script>
