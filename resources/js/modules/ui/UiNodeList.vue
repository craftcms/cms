<script setup lang="ts">
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/button-group/button-group';
  import '@craftcms/ui/components/icon/icon';
  import {t} from '@craftcms/ui/utilities/translate';
  import {
    computed,
    onBeforeUnmount,
    onMounted,
    ref,
    shallowRef,
    watch,
  } from 'vue';
  import {useIsSlideout} from '@/common/composables/screen';
  import UiNode from './UiNode.vue';
  import {
    fieldId,
    inputId,
    uiTabPanelId,
    pathsMatch,
    visitControls,
  } from './runtime';
  import {
    isSidebarTab,
    forSidebar,
    type SidebarTabNode,
    type SidebarTabOutlets,
  } from './sidebarTabs';
  import type {UiChange, UiNodePayload, UiPayload} from './types';

  const props = defineProps<{
    nodes: UiNodePayload[];
    values: UiPayload['values'];
    errors: UiPayload['errors'];
    touchedPaths: Set<string>;
    scope: string[];
    refreshable: boolean;
    /** Where sidebar tabs render. Without it, they're shown as ordinary tabs. */
    sidebarTabOutlets?: SidebarTabOutlets;
    /** Where the tab strip renders, rather than above the fields. */
    tabsOutlet?: HTMLElement | null;
  }>();
  /**
   * A slideout sits over a page whose URL is about what's behind it, so its
   * tabs leave the hash alone.
   */
  const isSlideout = useIsSlideout();

  const emit = defineEmits<{
    (event: 'change', change: UiChange): void;
  }>();
  const tabs = computed(() =>
    props.nodes.filter(
      (
        node
      ): node is UiNodePayload<{label: string; icon?: string}> & {
        uid: string;
      } =>
        node.component === 'craft:tab' && node.uid != null && !inSidebar(node)
    )
  );

  function inSidebar(node: UiNodePayload): node is SidebarTabNode {
    return Boolean(props.sidebarTabOutlets) && isSidebarTab(node);
  }

  function sidebarOutlet(node: UiNodePayload): HTMLElement | undefined {
    return node.uid ? props.sidebarTabOutlets?.get(node.uid) : undefined;
  }
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

  /**
   * The tabs whose fields have been rendered. Rendering every tab's fields up
   * front is most of what opening a form with several tabs costs, so only the
   * selected tab's are rendered at first. The rest follow one at a time while
   * the browser is idle, or as soon as they're needed: when the tab is
   * selected, when its fields have errors, so the error summary can link to
   * them, and when it holds the field the URL fragment points at. Once
   * rendered, a tab's fields stay, so what they hold survives switching away.
   *
   * Values don't depend on it: the renderer holds them, so a tab that's never
   * rendered still submits its fields' values.
   */
  const renderedTabs = shallowRef(new Set<string>());
  let idleRender: number | undefined;

  function renderTab(uid: string): void {
    if (!renderedTabs.value.has(uid)) {
      renderedTabs.value = new Set([...renderedTabs.value, uid]);
    }
  }

  function isDeferred(node: UiNodePayload): boolean {
    return (
      tabs.value.length > 1 &&
      node.component === 'craft:tab' &&
      node.uid != null &&
      !renderedTabs.value.has(node.uid)
    );
  }

  function nextUnrenderedTab(): string | undefined {
    return tabs.value.find((tab) => !renderedTabs.value.has(tab.uid))?.uid;
  }

  function renderNextTabWhenIdle(): void {
    if (idleRender !== undefined || !nextUnrenderedTab()) {
      return;
    }

    idleRender = whenIdle(() => {
      idleRender = undefined;
      const uid = nextUnrenderedTab();

      if (uid) {
        renderTab(uid);
      }
    });
  }

  /** Without `requestIdleCallback`, the next task stands in for idle time. */
  function whenIdle(callback: () => void): number {
    return typeof window.requestIdleCallback === 'function'
      ? window.requestIdleCallback(callback)
      : window.setTimeout(callback);
  }

  function cancelIdle(handle: number): void {
    if (typeof window.cancelIdleCallback === 'function') {
      window.cancelIdleCallback(handle);
    } else {
      window.clearTimeout(handle);
    }
  }

  /** Whether a node holds the field, or the input, `id` names. */
  function nodeHolds(node: UiNodePayload, id: string): boolean {
    let found = false;

    visitControls([node], (control) => {
      found ||= fieldId(control.path) === id || inputId(control.path) === id;
    });

    return found;
  }

  function fragmentId(): string {
    try {
      return decodeURIComponent(window.location.hash.slice(1));
    } catch {
      return '';
    }
  }

  watch(
    activeTab,
    (uid) => {
      if (uid) {
        renderTab(uid);
      }
    },
    {immediate: true}
  );

  watch(
    () => tabs.value.filter(nodeHasErrors).map((tab) => tab.uid),
    (uids) => uids.forEach(renderTab),
    {immediate: true}
  );

  const fragment = fragmentId();

  if (fragment) {
    tabs.value
      .filter((tab) => nodeHolds(tab, fragment))
      .forEach((tab) => renderTab(tab.uid));
  }

  onMounted(renderNextTabWhenIdle);
  watch([renderedTabs, tabs], renderNextTabWhenIdle, {flush: 'post'});
  onBeforeUnmount(() => {
    if (idleRender !== undefined) {
      cancelIdle(idleRender);
    }
  });

  function tabPanelId(tab: (typeof tabs.value)[number]): string {
    return uiTabPanelId(tab.uid, props.scope);
  }

  function panelAttributes(node: UiNodePayload): {id?: string} {
    if (tabs.value.length <= 1 || node.component !== 'craft:tab' || !node.uid) {
      return {};
    }

    return {id: `${uiTabPanelId(node.uid, props.scope)}-tab`};
  }

  function nodeHasErrors(node: UiNodePayload): boolean {
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
      node.control?.uis?.some((ui) => ui.nodes.some(nodeHasErrors))
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
  <!-- UiNodeList -->
  <Teleport v-if="tabs.length > 1" :to="tabsOutlet" :disabled="!tabsOutlet">
    <craft-tabs
      class="ui-node-list__tabs"
      :label="t('Primary fields')"
      :sync-location-hash="isSlideout ? 'false' : null"
      @craft-tab-show="onSelectionChanged"
    >
      <craft-tab
        v-for="tab in tabs"
        slot="tab"
        :key="tab.uid"
        :controls="`${tabPanelId(tab)}-tab`"
      >
        <craft-icon
          v-if="tab.props.icon"
          :name="tab.props.icon"
          slot="prefix"
        ></craft-icon>
        {{ tab.props.label }}
        <craft-icon
          v-if="nodeHasErrors(tab)"
          name="circle-exclamation"
          :label="t('Errors')"
          slot="suffix"
        />
      </craft-tab>
    </craft-tabs>
  </Teleport>
  <template
    v-for="node in nodes"
    :key="node.uid ?? node.control?.path.join('.')"
  >
    <!-- Sidebar tabs wait, hidden, until their outlet exists; the teleport
      then moves them there without remounting their fields. -->
    <Teleport
      v-if="inSidebar(node)"
      :to="sidebarOutlet(node) ?? 'body'"
      :disabled="!sidebarOutlet(node)"
    >
      <div :hidden="!sidebarOutlet(node)">
        <UiNode
          :node="forSidebar(node)"
          :values="values"
          :errors="errors"
          :touched-paths="touchedPaths"
          :scope="scope"
          :refreshable="refreshable"
          @change="emit('change', $event)"
        />
      </div>
    </Teleport>
    <UiNode
      v-else
      :node="node"
      :initially-hidden="
        node.component === 'craft:tab' && node.uid !== activeTab
      "
      :deferred="isDeferred(node)"
      v-bind="panelAttributes(node)"
      :values="values"
      :errors="errors"
      :touched-paths="touchedPaths"
      :scope="scope"
      :refreshable="refreshable"
      @change="emit('change', $event)"
    />
  </template>
</template>

<style scoped>
  .ui-node-list__tabs::part(strip) {
    box-sizing: border-box;
    block-size: var(--cp-header-height);
    align-items: stretch;
  }

  .ui-node-list__tabs::part(tab-group) {
    align-items: stretch;
  }

  .ui-node-list__tabs craft-tab {
    align-items: center;
    padding-block: 0;
  }
</style>
