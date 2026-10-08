<script setup lang="ts">
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import UiRenderer from '@/modules/ui/UiRenderer.vue';
  import type {UiPayload} from '@/modules/ui/types';

  defineProps<{
    stories: Record<string, UiPayload>;
    additionalButtons: string;
  }>();
</script>

<template>
  <LayoutSlot name="actions">
    <DynamicHtmlRenderer :html="additionalButtons" />
  </LayoutSlot>

  <article v-for="(payload, name) in stories" :key="name" class="mb-xl">
    <h2 class="text-lg">{{ name }}</h2>
    <form class="pane" @submit.prevent>
      <UiRenderer :payload="payload" />
    </form>
  </article>
</template>
