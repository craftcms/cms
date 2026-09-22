<script setup lang="ts">
  import {t} from '@craftcms/ui/utilities/translate';
  import {computed, ref} from 'vue';
  import Select from '@/common/form/Select.vue';

  /**
   * A "Move to page…" control for a {@see Table} Form Node in `dataUrl()` mode — the
   * within-page drag-and-drop reorder `AdminTableNode.vue` already offers can't reach a
   * page that isn't loaded, so this is the only way to move a row to a different one.
   * Mirrors the legacy `Craft.VueAdminTable` widget's own `AdminTableMoveToPageHud` (a
   * small "choose a page" popover, not literal drag-across-pages) — same interaction,
   * rebuilt on `<craft-popover>` instead of a `Garnish.HUD`.
   *
   * Lives once in the selection footer (`AdminTableNode.vue`), not per row — matching
   * this codebase's own established convention (see the real Entries index: uncommon
   * per-selection actions like "Move to…" live in a footer "Actions" menu once
   * something's selected, not in a disclosure control on every row) rather than
   * inventing a new per-row menu. `AdminTableNode.vue` only mounts this component while
   * exactly one row is selected, so there's no separate `disabled` state to model here.
   */
  const props = defineProps<{
    currentPage: number;
    lastPage: number;
    loading?: boolean;
  }>();

  const emit = defineEmits<{
    (e: 'move', page: number): void;
  }>();

  interface PopoverElement extends HTMLElement {
    hide(): Promise<void>;
  }

  const popover = ref<PopoverElement>();
  const targetPageRaw = ref<string | number>(props.currentPage);

  // `Select`'s v-model round-trips through a native <select>, whose value is
  // always a string — coerce back to a number the same way `BaseElementIndex.
  // vue`'s own page-size/page-index proxies already do.
  const targetPage = computed(() => parseInt(String(targetPageRaw.value), 10));

  const pages = Array.from({length: props.lastPage}, (_, i) => i + 1);

  function handleMove(): void {
    if (targetPage.value === props.currentPage) {
      return;
    }

    emit('move', targetPage.value);
    popover.value?.hide();
  }

  function onOpenedChanged(event: CustomEvent<boolean>): void {
    if (event.detail) {
      targetPageRaw.value = props.currentPage;
    }
  }
</script>

<template>
  <craft-popover
    ref="popover"
    placement="bottom-end"
    @opened-changed="onOpenedChanged"
  >
    <!--
      `v-once` is load-bearing, not an optimization — see `ActionMenu.vue`'s own
      identical comment. `craft-popover` extends the same Lion `OverlayMixin` as
      `craft-action-menu` and relocates/restructures its slotted invoker once
      mounted. This component stays mounted for as long as exactly one row is
      selected, and `AdminTableNode.vue` passes it a shared `loading` prop that
      flips during that same window (the move's own `fetchPage()` refetch);
      without `v-once`, that re-render patches the invoker against DOM the
      overlay has already moved. The invoker is static text, so freezing it
      costs nothing.
    -->
    <craft-button type="button" slot="invoker" size="small" v-once>{{
      t('Move to page…')
    }}</craft-button>

    <div slot="content-body" class="flex flex-nowrap items-end gap-2">
      <Select
        :label="t('Choose a page')"
        v-model="targetPageRaw"
        :options="pages.map((p) => ({label: String(p), value: String(p)}))"
      />
      <craft-button
        type="button"
        variant="primary"
        size="small"
        :loading="loading"
        @click="handleMove"
        >{{ t('Move') }}</craft-button
      >
    </div>
  </craft-popover>
</template>
