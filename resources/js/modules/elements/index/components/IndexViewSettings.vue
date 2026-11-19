<script setup lang="ts">
  import {nextTick, useTemplateRef} from 'vue';
  import {ButtonVariant, t} from '@craftcms/ui';
  import ViewSettingsFields from './ViewSettingsFields.vue';
  import PaneActions from './PaneActions.vue';
  import type {CheckboxOption} from '@/common/types';
  import type {ViewSettingKind} from '@/modules/elements/index/composables/useElementIndex';
  import type {
    ThumbSize,
    SortOption,
    ViewMode,
  } from '@/modules/elements/types/view-state';

  const props = withDefaults(
    defineProps<{
      /** The button's label. */
      label?: string;
      icon?: string;
      /** The settings this menu holds; all of them when left out. */
      only?: ViewSettingKind[];
      viewModes?: Array<ViewMode>;
      /** The toggleable/reorderable table columns. */
      options: Array<CheckboxOption>;
      /** The sortable attributes for the "Sort by" select; an empty `value` means no sort. */
      sortOptions: Array<SortOption>;
      /** Whether the active sort has an intrinsic direction. */
      sortDirectionLocked?: boolean;
      disabled?: boolean;
      /** The thumbnail sizes the active view mode offers, if any. */
      thumbSizeOptions?: Array<ThumbSize>;
      /** Whether the active view mode has table columns to choose. */
      showTableColumns?: boolean;
      /** How many of the View menu's settings differ from the source's. */
      changeCount?: number;
      /** Which of the View menu's settings differ from the source's. */
      changed?: ViewSettingKind[];
    }>(),
    {label: () => t('View'), icon: 'sliders'}
  );

  const sortField = defineModel<string>('sortField', {required: true});
  const sortDirection = defineModel<'asc' | 'desc'>('sortDirection', {
    required: true,
  });
  const tableColumns = defineModel<Array<string>>('tableColumns', {
    required: true,
  });
  const thumbSize = defineModel<ThumbSize>('thumbSize', {default: 'sm'});
  const mode = defineModel<ViewMode['mode']>('mode');

  const emit = defineEmits<{
    (e: 'reorder', options: Array<CheckboxOption>): void;
    (e: 'use-defaults'): void;
  }>();

  const fields =
    useTemplateRef<InstanceType<typeof ViewSettingsFields>>('fields');
  const actions = useTemplateRef<InstanceType<typeof PaneActions>>('actions');
  const popover = useTemplateRef<
    HTMLElement & {show(): Promise<void>; hide(): Promise<void>}
  >('popover');

  defineExpose({
    /** Opens the menu, as its button would. */
    show: () => popover.value?.show(),
  });

  /**
   * The settings as they were when the menu opened, which Cancel goes back to.
   * Closing it any other way keeps the changes; Apply is only a way to close.
   */
  let opened: {
    mode?: ViewMode['mode'];
    sortField: string;
    sortDirection: 'asc' | 'desc';
    tableColumns: string[];
    thumbSize: ThumbSize;
    order: string[];
  } | null = null;

  function onShow(): void {
    fields.value?.takeSnapshot();
    opened = {
      mode: mode.value,
      sortField: sortField.value,
      sortDirection: sortDirection.value,
      tableColumns: [...tableColumns.value],
      thumbSize: thumbSize.value,
      order: props.options.map((option) => String(option.value)),
    };
  }

  /** Puts back only what changed, so an untouched setting sends no visit. */
  function revert(): void {
    const was = opened;
    opened = null;
    if (!was) return;

    if (was.mode !== undefined && mode.value !== was.mode)
      mode.value = was.mode;
    if (sortField.value !== was.sortField) sortField.value = was.sortField;
    if (sortDirection.value !== was.sortDirection) {
      sortDirection.value = was.sortDirection;
    }
    if (thumbSize.value !== was.thumbSize) thumbSize.value = was.thumbSize;
    if (tableColumns.value.join() !== was.tableColumns.join()) {
      tableColumns.value = was.tableColumns;
    }

    const order = props.options.map((option) => String(option.value));
    if (order.join() !== was.order.join()) {
      emit(
        'reorder',
        [...props.options].sort(
          (a, b) =>
            was.order.indexOf(String(a.value)) -
            was.order.indexOf(String(b.value))
        )
      );
    }
  }

  // The button goes away once the settings are back to their defaults, so
  // focus moves on to the one beside it rather than being lost.
  async function useDefaults() {
    emit('use-defaults');
    await nextTick();
    actions.value?.focusCancel();
  }

  function cancel(): void {
    revert();
    void popover.value?.hide();
  }
</script>

<template>
  <craft-popover
    ref="popover"
    class="index-view-settings"
    style="--popover-min-inline-size: 24rem; --popover-max-inline-size: 24rem"
    @craft-show.self="onShow"
  >
    <!-- Something else can trigger it, like a chip summing up its settings. -->
    <slot name="invoker">
      <craft-button
        type="button"
        slot="invoker"
        :icon="icon"
        :variant="ButtonVariant.Fill"
        .disabled="disabled"
      >
        {{ label }}
        <!-- Says something here has changed; the filter bar says what. -->
        <craft-badge-indicator
          v-if="changeCount"
          slot="suffix"
          style="margin-inline-start: var(--c-spacing-xs)"
          :alt-text="t('Changed')"
        ></craft-badge-indicator>
      </craft-button>
    </slot>

    <!-- A pane like the filter menu's, so the menus look and act alike. -->
    <craft-pane slot="content" padding="lg" class="index-view-settings__pane">
      <ViewSettingsFields
        ref="fields"
        :options="options"
        :sort-options="sortOptions"
        :sort-direction-locked="sortDirectionLocked"
        :thumb-size-options="thumbSizeOptions"
        :show-table-columns="showTableColumns"
        :view-modes="viewModes"
        :only="only"
        :changed="changed"
        v-model:mode="mode"
        v-model:sort-field="sortField"
        v-model:sort-direction="sortDirection"
        v-model:table-columns="tableColumns"
        v-model:thumb-size="thumbSize"
        @reorder="(next) => emit('reorder', next)"
      />

      <PaneActions
        ref="actions"
        :clear-label="changeCount ? t('Clear') : undefined"
        @clear="useDefaults"
        @cancel="cancel"
        @apply="popover?.hide()"
      />
    </craft-pane>
  </craft-popover>
</template>

<style scoped>
  /* The filter menu's shadow, with the pane in place of the menu's own padding. */
  .index-view-settings::part(popup) {
    box-shadow: var(--c-shadow-overlay);
  }

  .index-view-settings__pane {
    --c-pane-border-width: 0;
    display: block;
  }
</style>
