<template>
  <div
    :class="{
      'tw-border-b dark:tw-border-gray-700 tw-py-4': border,
    }"
  >
    <div class="tw-text-gray-500 dark:tw-text-gray-400">
      <slot name="title" />
    </div>
    <div class="tw-mt-1 tw-font-medium">
      <slot name="content" />
    </div>
  </div>
</template>

<script>
  import {defineComponent} from 'vue';

  export default defineComponent({
    name: 'StatComponent',
    props: {
      border: {
        type: Boolean,
        default: true,
      },
    },
  });
</script>
