<script setup lang="ts">
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import FormRenderer from '@/modules/forms/FormRenderer.vue';
  import type {FormPayload} from '@/modules/forms/types';
  import {ref} from 'vue';

  type Tab = {
    tabId: string;
    label: string;
    url: string;
    class: string | null;
  };

  const props = defineProps<{
    stories: Record<string, FormPayload>;
    additionalButtons: string;
    tabs: Record<string, Tab>;
  }>();
  const activeTab = ref(Object.keys(props.tabs)[0] ?? null);

  function selectTab(containerId: string): void {
    activeTab.value = containerId;

    for (const id of Object.keys(props.tabs)) {
      document
        .getElementById(id)
        ?.classList.toggle('hidden', id !== containerId);
    }
  }
</script>

<template>
  <LayoutSlot name="actions">
    <DynamicHtmlRenderer :html="additionalButtons" />
  </LayoutSlot>

  <LayoutSlot v-if="Object.keys(tabs).length > 1" name="tabs">
    <craft-button-group role="tablist">
      <craft-button
        v-for="(tab, containerId) in tabs"
        :key="containerId"
        :id="tab.tabId"
        type="button"
        role="tab"
        appearance="outline"
        :active="activeTab === containerId ? 'true' : null"
        :aria-selected="activeTab === containerId"
        :aria-controls="containerId"
        @click="selectTab(containerId)"
      >
        {{ tab.label }}
        <craft-icon
          v-if="tab.class === 'error'"
          name="circle-exclamation"
          slot="suffix"
        />
      </craft-button>
    </craft-button-group>
  </LayoutSlot>

  <article v-for="(payload, name) in stories" :key="name" class="mb-xl">
    <h2 class="text-lg">{{ name }}</h2>
    <form class="pane" @submit.prevent>
      <FormRenderer :payload="payload" />
    </form>
  </article>
</template>
