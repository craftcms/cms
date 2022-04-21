<template>
  <div
    class="tw-overflow-x-auto tw-w-full tw-flex tw-gap-6 tw-snap-x tw-snap-mandatory tw-pb-8"
  >
    <a
      v-for="(image, key) in thumbnails"
      :key="key"
      class="tw-shrink-0 tw-snap-start tw-w-64 tw-aspect-[4/3]"
      @click.prevent="zoomImage(key)"
    >
      <div
        class="tw-flex tw-justify-center tw-items-center tw-w-full tw-h-full"
      >
        <img
          :alt="
            'Plugin screenshot thumbnail ' + (key + 1) + '/' + thumbnails.length
          "
          class="tw-rounded-md tw-max-w-full tw-max-h-full"
          :src="image"
        />
      </div>
    </a>
  </div>
</template>

<script>
  export default {
    props: {
      thumbnails: {
        type: Array,
        required: true,
      },
      images: {
        type: Array,
        required: true,
      },
    },

    methods: {
      zoomImage(key) {
        this.$store.commit('app/updateScreenshotModalImages', this.images);
        this.$store.commit('app/updateShowingScreenshotModal', true);
        this.$store.commit('app/updateScreenshotModalImageKey', key);
      },
    },
  };
</script>
