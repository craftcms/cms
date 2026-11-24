<script setup lang="ts">
  import {ref} from 'vue';
  import CpContainer from '@/common/components/CpContainer.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import {openSlideout} from '@/common/slideouts/store';

  defineProps<{
    cases: {label: string; notes: string; url: string; slideout?: boolean}[];
  }>();

  const widths = [1200, 900, 600] as const;

  const width = ref<(typeof widths)[number]>(1200);
</script>

<template>
  <LayoutSlot name="content-actions">
    <div class="flex gap-sm" role="group" aria-label="Frame width">
      <craft-button
        v-for="option in widths"
        :key="option"
        type="button"
        :aria-pressed="String(width === option)"
        @click="width = option"
      >
        {{ option }}px
      </craft-button>
    </div>
  </LayoutSlot>

  <CpContainer class="py-lg">
    <div class="grid gap-xl">
      <section v-for="item in cases" :key="item.url" class="grid gap-sm">
        <div class="flex flex-wrap items-baseline gap-md">
          <h2 class="cp-h3">{{ item.label }}</h2>
          <a :href="item.url" target="_blank">Open in a new tab</a>
          <craft-button
            v-if="item.slideout"
            type="button"
            size="small"
            @click="openSlideout(item.url)"
          >
            Open in a slideout
          </craft-button>
        </div>
        <p class="text-sm">{{ item.notes }}</p>
        <div class="overflow-x-auto">
          <iframe
            :src="item.url"
            :title="item.label"
            :style="{inlineSize: `${width}px`, blockSize: '640px'}"
            class="block border border-slate-300 rounded-md"
            loading="lazy"
          ></iframe>
        </div>
      </section>
    </div>
  </CpContainer>
</template>
