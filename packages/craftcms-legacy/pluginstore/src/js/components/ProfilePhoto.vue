<script>
  import {defineComponent} from 'vue';

  export default defineComponent({
    props: {
      url: String,
      default: null,
    },
  });
</script>

<template>
  <div
    class="icon tw-w-10 tw-h-10 tw-rounded-full tw-bg-gray-500/10 dark:tw-bg-white/20 tw-overflow-hidden tw-text-gray-500"
  >
    <img
      v-if="url"
      :src="url"
      alt=""
      width="50"
      height="50"
      class="tw-w-full"
    />
    <div v-else class="tw-flex tw-h-full tw-justify-center tw-items-center">
      <span class="tw-w-5">
        <svg
          aria-hidden="true"
          focusable="false"
          data-prefix="fas"
          data-icon="user"
          role="img"
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 448 512"
        >
          <path
            fill="currentColor"
            d="M224 256c70.7 0 128-57.31 128-128s-57.3-128-128-128C153.3 0 96 57.31 96 128S153.3 256 224 256zM274.7 304H173.3C77.61 304 0 381.6 0 477.3c0 19.14 15.52 34.67 34.66 34.67h378.7C432.5 512 448 496.5 448 477.3C448 381.6 370.4 304 274.7 304z"
          ></path>
        </svg>
      </span>
    </div>
  </div>
</template>

<style scoped lang="scss"></style>
