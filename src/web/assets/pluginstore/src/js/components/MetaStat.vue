<template>
  <div
    :class="{
      'tw-border-b tw-py-4': border,
    }"
  >
    <dt class="tw-text-gray-500">
      <slot name="title" />
    </dt>
    <dd class="tw-mt-1 tw-font-medium">
      <slot name="content" />
    </dd>
  </div>
</template>

<script>
  export default {
    props: {
      border: {
        type: Boolean,
        default: true,
      },
    },
  };
</script>
