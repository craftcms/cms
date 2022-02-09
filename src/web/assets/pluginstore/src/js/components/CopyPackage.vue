<template>
  <div class="tw-flex">
    <input
      ref="input"
      class="tw-font-mono tw-border tw-border-r-0 tw-border-solid tw-border-gray-200 tw-w-full tw-py-2 tw-px-3 tw-rounded-l"
      :value="plugin.packageName"
      readonly="readonly"
      @focus="select" />
    <button
      class="tw-border tw-border-solid tw-border-gray-200 tw-py-2 tw-px-3 tw-rounded-r tw-bg-gray-50 hover:tw-bg-gray-100 active:tw-bg-gray-200 hover:tw-cursor-pointer"
      @click="copy">
      <icon
        class="tw-text-gray-500"
        icon="copy" />
    </button>
  </div>
</template>

<script>
export default {
  props: ['plugin'],

  methods: {
    select() {
      this.$refs.input.$refs.input.select()
    },

    copy() {
      this.select();

      window.document.execCommand('copy');
    },
  }
}
</script>
