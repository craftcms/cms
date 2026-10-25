<script setup lang="ts">
  /**
   * Adds a panel to the nearest screen's details column while it's mounted:
   * an icon in the rail, and the panel it opens. The default slot is the
   * panel's content, and `header-actions` goes in its header, both moved
   * into the column while staying in the scope they're written in, so they
   * keep their bindings and state.
   *
   * ```vue
   * <ScreenPanel id="info" :label="t('Info')" icon="circle-info">
   *   <MetadataDetailsContent :html="html" />
   * </ScreenPanel>
   * ```
   */
  import {computed, onBeforeUnmount, onMounted, watch} from 'vue';
  import {useScreenPanels} from '@/common/composables/screenPanels';
  import type {DetailsPanel, DetailsPanelStatus} from './detailsPanels';

  const props = defineProps<{
    /** Unique among the screen's panels, and used in the URL hash on a page. */
    id: string;
    label: string;
    icon: string;
    /** Show the label's first letter instead of the icon. */
    initialIcon?: boolean;
    /** Where the panel goes in the rail. Lower comes first. */
    order?: number;
    status?: DetailsPanelStatus | null;
    /** Stays from page to page. See {@link DetailsPanel.persistent}. */
    persistent?: boolean;
    /** The content scrolls itself, so the panel's body doesn't. */
    scrollsItself?: boolean;
    /** What gets focus when the panel is opened from its trigger. Defaults to its heading. */
    initialFocus?: () => HTMLElement | null | undefined;
  }>();

  const panels = useScreenPanels();

  if (import.meta.env.DEV && !panels) {
    console.warn(
      `[ScreenPanel] “${props.id}” isn't inside a screen, so nothing will show it.`
    );
  }

  const descriptor = computed<DetailsPanel>(() => ({
    id: props.id,
    label: props.label,
    icon: props.icon,
    initialIcon: props.initialIcon,
    order: props.order,
    statusData: props.status ?? null,
    persistent: props.persistent,
    scrollsItself: props.scrollsItself,
    initialFocus: props.initialFocus,
  }));

  // Registered once mounted rather than during setup: registering changes the
  // screen, which would otherwise re-render while this is still mounting.
  onMounted(() => {
    watch(
      descriptor,
      (panel, previous) => {
        if (previous && previous.id !== panel.id) {
          panels?.unregister(previous.id);
        }

        panels?.register(panel);
      },
      {immediate: true}
    );
  });

  onBeforeUnmount(() => panels?.unregister(props.id));

  const body = computed(() => panels?.bodies.get(props.id) ?? null);
  const header = computed(() => panels?.headers.get(props.id) ?? null);
</script>

<template>
  <!-- The content's home until the column renders the panel. -->
  <div hidden>
    <Teleport :to="body ?? 'body'" :disabled="!body">
      <slot></slot>
    </Teleport>
    <Teleport
      v-if="$slots['header-actions']"
      :to="header ?? 'body'"
      :disabled="!header"
    >
      <slot name="header-actions"></slot>
    </Teleport>
  </div>
</template>
