<script setup lang="ts">
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import FormRenderer from '@/modules/forms/FormRenderer.vue';
  import type {FormPayload} from '@/modules/forms/types';

  defineProps<{
    stories: Record<string, FormPayload>;
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
      <FormRenderer :payload="payload" />
    </form>
  </article>
</template>
