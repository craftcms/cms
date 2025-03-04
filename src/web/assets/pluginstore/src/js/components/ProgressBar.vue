<script>
  import {defineComponent} from 'vue';

  export default defineComponent({
    props: {
      indicatorClass: {
        type: String,
        default: 'tw-bg-blue-600 tw-dark:bg-blue-400',
      },
      trackClass: {
        type: String,
        default: 'tw-bg-gray-200 tw-dark:bg-gray-700 h-1',
      },
      value: Number,
      height: {
        type: Number,
        default: 3,
      },
    },
    computed: {
      computedHeight() {
        if (typeof this.height === 'number') {
          return `${this.height}px`;
        }

        return this.height;
      },
    },
  });
</script>

<template>
  <div
    class="track"
    :class="trackClass"
    :style="{
      height: computedHeight,
    }"
  >
    <div
      class="indicator"
      :style="{
        width: `${value}%`,
      }"
      :class="indicatorClass"
    ></div>
  </div>
</template>

<style scoped>
  .track {
    width: 100%;
    border-radius: 2px;
    position: relative;
  }

  .indicator {
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
  }
</style>
