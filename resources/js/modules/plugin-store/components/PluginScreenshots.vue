<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed, shallowRef} from 'vue';

  const {thumbnails, images} = defineProps<{
    thumbnails: string[];
    images: string[];
  }>();

  const openIndex = shallowRef<number | null>(null);
  const openImage = computed(() =>
    openIndex.value === null ? null : images[openIndex.value]
  );

  function step(by: number): void {
    if (openIndex.value !== null) {
      openIndex.value = (openIndex.value + by + images.length) % images.length;
    }
  }

  function onKeydown(event: KeyboardEvent): void {
    const forward = document.dir === 'rtl' ? 'ArrowLeft' : 'ArrowRight';
    const back = document.dir === 'rtl' ? 'ArrowRight' : 'ArrowLeft';

    if (event.key === forward) {
      step(1);
    } else if (event.key === back) {
      step(-1);
    }
  }
</script>

<template>
  <div class="plugin-screenshots">
    <button
      v-for="(thumbnail, index) in thumbnails"
      :key="thumbnail"
      type="button"
      class="plugin-screenshots__thumb"
      :aria-label="
        t('Screenshot {num} of {total}', {
          num: index + 1,
          total: thumbnails.length,
        })
      "
      @click="openIndex = index"
    >
      <img :src="thumbnail" alt="" />
    </button>
  </div>

  <craft-dialog
    fullscreen
    close-on-outside-click
    :label="t('Screenshots')"
    .opened="openIndex !== null"
    @craft-hide="openIndex = null"
    @keydown="onKeydown"
  >
    <div class="plugin-screenshots__viewer">
      <img v-if="openImage" :src="openImage" alt="" />
    </div>
    <div
      v-if="images.length > 1"
      slot="footer"
      class="flex items-center gap-md"
    >
      <craft-button
        icon="chevron-left"
        :aria-label="t('Previous')"
        @click="step(-1)"
      >
      </craft-button>
      <span>{{ (openIndex ?? 0) + 1 }} / {{ images.length }}</span>
      <craft-button
        icon="chevron-right"
        :aria-label="t('Next')"
        @click="step(1)"
      >
      </craft-button>
    </div>
  </craft-dialog>
</template>

<style scoped>
  .plugin-screenshots {
    display: flex;
    gap: var(--c-spacing-lg);
    overflow-x: auto;
    padding-block-end: var(--c-spacing-lg);
    scroll-snap-type: x mandatory;
  }

  .plugin-screenshots__thumb {
    display: flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    inline-size: 16rem;
    aspect-ratio: 4 / 3;
    padding: 0;
    border: 0;
    background: none;
    cursor: zoom-in;
    scroll-snap-align: start;
  }

  .plugin-screenshots__thumb img {
    max-inline-size: 100%;
    max-block-size: 100%;
    border-radius: var(--c-radius-md);
  }

  .plugin-screenshots__viewer {
    display: flex;
    align-items: center;
    justify-content: center;
    block-size: 100%;
  }

  .plugin-screenshots__viewer img {
    max-inline-size: 100%;
    max-block-size: 100%;
    object-fit: contain;
  }
</style>
