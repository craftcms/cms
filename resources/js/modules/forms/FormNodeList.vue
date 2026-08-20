<script setup lang="ts">
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/button-group/button-group';
  import '@craftcms/ui/components/icon/icon';
  import {t} from '@craftcms/ui/utilities/translate';
  import {computed, ref, watch} from 'vue';
  import FormNode from './FormNode.vue';
  import {formTabPanelId, pathsMatch} from './runtime';
  import type {FormChange, FormNodePayload, FormPayload} from './types';

  const props = defineProps<{
    nodes: FormNodePayload[];
    values: FormPayload['values'];
    errors: FormPayload['errors'];
    touchedPaths: Set<string>;
    scope: string[];
    refreshable: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'change', change: FormChange): void;
  }>();
  const tabs = computed(() =>
    props.nodes.filter(
      (node): node is FormNodePayload<{label: string}> & {uid: string} =>
        node.component === 'craft:tab' && node.uid != null
    )
  );
  const activeTab = ref<string | null>(null);

  watch(
    tabs,
    (currentTabs) => {
      if (!currentTabs.some((tab) => tab.uid === activeTab.value)) {
        activeTab.value = currentTabs[0]?.uid ?? null;
      }
    },
    {immediate: true}
  );

  function tabPanelId(tab: (typeof tabs.value)[number]): string {
    return formTabPanelId(tab.uid, props.scope);
  }

  function nodeHasErrors(node: FormNodePayload): boolean {
    const controlPath = node.control?.path;

    if (
      controlPath &&
      props.errors.some((error) => pathsMatch(error.path, controlPath))
    ) {
      return true;
    }

    if (node.children?.some(nodeHasErrors)) {
      return true;
    }

    return Boolean(
      node.control?.forms?.some((form) => form.nodes.some(nodeHasErrors))
    );
  }

  /**
   * `craft-tabs` owns the selection — clicks, keyboard navigation, and the
   * overflow menu all resolve to a `selectedIndex` — so the panel visibility
   * this component drives follows the strip rather than tracking the
   * interactions itself.
   */
  function onSelectionChanged(event: Event): void {
    const index = (event.target as {selectedIndex?: number} | null)
      ?.selectedIndex;
    const tab = index === undefined ? undefined : tabs.value[index];

    if (tab) {
      activeTab.value = tab.uid;
    }
  }
</script>

<template>
  <craft-tabs v-if="tabs.length > 1" @selected-changed="onSelectionChanged">
    <craft-tab
      v-for="tab in tabs"
      slot="tab"
      :key="tab.uid"
      :controls="`${tabPanelId(tab)}-tab`"
    >
      {{ tab.props.label }}
      <craft-icon
        v-if="nodeHasErrors(tab)"
        name="circle-exclamation"
        :label="t('Errors')"
        slot="suffix"
      />
    </craft-tab>
  </craft-tabs>
  <FormNode
    v-for="node in nodes"
    :key="node.uid ?? node.control?.path.join('.')"
    :node="node"
    slot="panel"
    :initially-hidden="node.component === 'craft:tab' && node.uid !== activeTab"
    :id="
      tabs.length > 1 && node.component === 'craft:tab' && node.uid
        ? `${formTabPanelId(node.uid, scope)}-tab`
        : undefined
    "
    :values="values"
    :errors="errors"
    :touched-paths="touchedPaths"
    :scope="scope"
    :refreshable="refreshable"
    @change="emit('change', $event)"
  />
</template>
