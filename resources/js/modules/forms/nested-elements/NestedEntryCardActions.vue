<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {Selectable} from '@/common/composables/useSelectable';
  import {canOpenEntry, type NestedEntry} from './nested-entries';
  import {nestedEntryActions} from './nested-entry-actions';
  import type {
    NestedEntryOperations,
    NestedEntryQuickEdit,
  } from './useNestedEntryOperations';

  const props = defineProps<{
    entry: NestedEntry;
    entries: NestedEntry[];
    selection: Selectable<number>;
    singleColumn: boolean;
    editable: boolean;
    busy: boolean;
    reorderable: boolean;
    operations: NestedEntryOperations;
    quickEdit: NestedEntryQuickEdit;
    editOpener?: HTMLElement | null;
  }>();

  function actionIds(): number[] {
    return props.selection.isSelected(props.entry.id)
      ? [...props.selection.selectedIds.value]
      : [props.entry.id];
  }

  function actions() {
    const ids = actionIds();
    const selectedEntries = props.entries.filter((entry) =>
      ids.includes(entry.id)
    );
    const index = props.entries.findIndex(
      (candidate) => candidate.id === props.entry.id
    );

    return nestedEntryActions({
      entry: props.entry,
      selectedEntries,
      ids,
      index,
      count: props.entries.length,
      editable: props.editable,
      busy: props.busy,
      canPaste: props.operations.canPaste.value,
      canReorder: props.reorderable,
      canAdd: props.operations.canAdd,
      pasteLabel: props.operations.pasteActionLabel(
        props.singleColumn ? 'above' : 'before'
      ),
    });
  }

  function editLabel(): string {
    return t('Edit {title}', {
      title: props.entry.cardAttributes?.data?.label ?? `#${props.entry.id}`,
    });
  }

  function actionLabel(): string {
    return t('Actions for {title}', {
      title: props.entry.cardAttributes?.data?.label ?? `#${props.entry.id}`,
    });
  }
</script>

<template>
  <craft-button
    v-if="canOpenEntry(entry)"
    data-edit-entry
    variant="plain"
    type="button"
    size="small"
    icon="edit"
    :aria-label="editLabel()"
    :title="t('Edit')"
    .disabled="busy"
    @click.stop="
      quickEdit.openRow(
        entry,
        editOpener ?? ($event.currentTarget as HTMLElement)
      )
    "
  ></craft-button>
  <ActionMenu
    v-if="!entry.cardAttributes?.data?.['revision-id'] && actions().length > 0"
    :label="actionLabel()"
    :actions="actions()"
    :flush="false"
  />
</template>
