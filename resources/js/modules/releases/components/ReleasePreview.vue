<script setup lang="ts">
  /**
   * A release's drafts in Live Preview's layout: the drafts listed in place of
   * the editor, as a secondary nav is, and whichever is selected loaded beside
   * them, as Live Preview would load it.
   *
   * Without room for the list, the drafts move into a menu in the preview's
   * toolbar, with the selected draft's targets beneath it.
   */
  import {router} from '@inertiajs/vue3';
  import {t} from '@craftcms/ui';
  import {computed, onMounted} from 'vue';
  import ActionList from '@/common/components/ActionList.vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import {cpBreakpoints} from '@/common/composables/useCpBreakpoints';
  import type {ActionItems} from '@/common/types';
  import PreviewLayout from '@/modules/elements/live-preview/PreviewLayout.vue';
  import {useLivePreview} from '@/modules/elements/live-preview/useLivePreview';
  import type {ReleasePreviewItem, ReleasePreviewPayload} from '../types';

  const props = defineProps<{
    release: {name: string};
    preview: ReleasePreviewPayload;
  }>();

  const compact = cpBreakpoints.smaller('md');

  const selected = computed(
    () =>
      props.preview.items.find(
        (item) => item.id === props.preview.selectedItemId
      ) ?? null
  );

  const controller = useLivePreview(() => selected.value?.preview ?? null, {
    save: async () => {},
    hasPendingChanges: false,
  });

  onMounted(() => void controller.open());

  /** Switches drafts in place, keeping the URL in step so a reload stays on it. */
  function select(item: ReleasePreviewItem): void {
    if (item.id === props.preview.selectedItemId) {
      return;
    }

    void router.replace({
      url: item.url,
      props: (current) => ({
        ...current,
        preview: {...props.preview, selectedItemId: item.id},
      }),
      preserveState: true,
      preserveScroll: true,
    });
  }

  /** Lets a modified click open the draft's preview in a new tab instead. */
  function onLinkClick(event: Event, item: ReleasePreviewItem): void {
    const mouse = event as MouseEvent;

    if (
      mouse.button !== 0 ||
      mouse.metaKey ||
      mouse.ctrlKey ||
      mouse.shiftKey ||
      mouse.altKey
    ) {
      return;
    }

    event.preventDefault();
    select(item);
  }

  const navItems = computed<ActionItems>(() =>
    props.preview.items.map((item) => ({
      type: 'link',
      href: item.url,
      label: item.title,
      selected: item.id === props.preview.selectedItemId,
      onClick: (event: Event) => onLinkClick(event, item),
    }))
  );

  /** The drafts, with the selected one's targets indented beneath it. */
  const menuItems = computed<ActionItems>(() =>
    props.preview.items.flatMap((item) => {
      const isSelected = item.id === props.preview.selectedItemId;
      const targets = isSelected ? controller.targets.value : [];

      return [
        {
          label: item.title,
          selected: isSelected,
          onClick: () => select(item),
        },
        ...(targets.length > 1
          ? targets.map((target, index) => ({
              label: target.label,
              selected: index === controller.activeIndex.value,
              attrs: {class: 'release-preview__target'},
              onClick: () => controller.switchTarget(index),
            }))
          : []),
      ];
    })
  );

  const menuLabel = computed(() => {
    const target = controller.activeTarget.value;

    return target && controller.targets.value.length > 1
      ? `${selected.value?.title} – ${target.label}`
      : (selected.value?.title ?? '');
  });

  function returnToRelease(): void {
    void router.replace({
      url: props.preview.closeUrl,
      props: (current) => ({...current, preview: null}),
      preserveState: true,
      preserveScroll: true,
    });
  }
</script>

<template>
  <PreviewLayout
    :controller="controller"
    :title="release.name"
    sidebar-width="calc(var(--cp-sidebar-width) + 1px)"
    :compact="compact"
    @after-leave="returnToRelease"
  >
    <nav
      class="lp-editor__body release-preview__drafts"
      :aria-label="t('Drafts')"
    >
      <craft-nav-list>
        <ActionList :actions="navItems" as="craft-nav-item" mode="inline" />
      </craft-nav-list>
    </nav>

    <template v-if="compact" #toolbar-start>
      <ActionMenu
        :key="`${preview.selectedItemId}-${controller.activeIndex.value}`"
        :actions="menuItems"
        :label="menuLabel"
        :flush="false"
      >
        <template #invoker="{attributes}">
          <craft-button
            v-bind="attributes"
            type="button"
            size="small"
            variant="plain"
            icon="chevron-down"
            icon-position="suffix"
            class="release-preview__menu-button"
          >
            {{ menuLabel }}
          </craft-button>
        </template>
      </ActionMenu>
    </template>
  </PreviewLayout>
</template>

<style scoped>
  .release-preview__drafts {
    padding: var(--c-spacing-md);
  }

  .release-preview__menu-button {
    max-inline-size: 100%;
  }

  .release-preview__menu-button::part(label) {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  :deep(.release-preview__target)::part(control) {
    padding-inline-start: calc(var(--c-spacing-sm) + var(--c-spacing-lg));
  }
</style>
