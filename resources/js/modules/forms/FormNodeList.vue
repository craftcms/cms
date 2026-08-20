<script setup lang="ts">
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/button-group/button-group';
  import '@craftcms/ui/components/icon/icon';
  import {t} from '@craftcms/ui/utilities/translate';
  import {computed, nextTick, ref, watch} from 'vue';
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
  const tablist = ref<HTMLElement>();
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

  function onTabKeydown(event: KeyboardEvent, index: number): void {
    let nextIndex: number;

    if (event.key === 'Home') {
      nextIndex = 0;
    } else if (event.key === 'End') {
      nextIndex = tabs.value.length - 1;
    } else if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
      if (!(event.currentTarget instanceof Element)) {
        return;
      }

      const rtl = getComputedStyle(event.currentTarget).direction === 'rtl';
      const previous = event.key === (rtl ? 'ArrowRight' : 'ArrowLeft');
      nextIndex =
        (index + (previous ? -1 : 1) + tabs.value.length) % tabs.value.length;
    } else {
      return;
    }

    event.preventDefault();
    activeTab.value = tabs.value[nextIndex]!.uid;
    nextTick(() =>
      tablist.value
        ?.querySelectorAll<HTMLElement>('[role="tab"]')
        [nextIndex]?.focus()
    );
  }
</script>

<template>
  <craft-button-group v-if="tabs.length > 1" ref="tablist" role="tablist">
    <craft-button
      v-for="(tab, index) in tabs"
      :key="tab.uid"
      :id="`${tabPanelId(tab)}-tab`"
      type="button"
      role="tab"
      variant="outline"
      :active="activeTab === tab.uid"
      :tabindex="activeTab === tab.uid ? 0 : -1"
      :aria-selected="activeTab === tab.uid"
      :aria-controls="tabPanelId(tab)"
      @click="activeTab = tab.uid"
      @keydown="onTabKeydown($event, index)"
    >
      {{ tab.props.label }}
      <craft-icon
        v-if="nodeHasErrors(tab)"
        name="circle-exclamation"
        :label="t('Errors')"
        slot="suffix"
      />
    </craft-button>
  </craft-button-group>
  <FormNode
    v-for="node in nodes"
    :key="node.uid ?? node.control?.path.join('.')"
    :node="node"
    :initially-hidden="node.component === 'craft:tab' && node.uid !== activeTab"
    :tab-button-id="
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
