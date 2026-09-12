<script setup lang="ts">
  import {ref} from 'vue';
  import {t} from '@craftcms/ui';
  import type {ActionItem} from '@/common/types';
  import type {DragData} from '@/common/composables/useReorderableItems';
  import CustomSourceList from './CustomSourceList.vue';
  import PageSettingsModal from './PageSettingsModal.vue';
  import {pageNameId, type PageRow} from './types';

  const props = defineProps<{
    pages: PageRow[];
    selected: string | null;
  }>();

  const emit = defineEmits<{
    (e: 'select', name: string): void;
    (e: 'reorder', from: number, to: number): void;
    (e: 'add', name: string, icon: string | null): void;
    (e: 'update', page: PageRow, name: string, icon: string | null): void;
    (e: 'remove', page: PageRow): void;
    (e: 'promote', key: string, index: number): void;
  }>();

  const editing = ref<PageRow | null>(null);
  const modalActive = ref(false);

  /** A page's name is both its identity and its label. */
  function itemId(page: PageRow): string {
    return page.name;
  }

  function icon(page: PageRow): string | null {
    return page.icon;
  }

  function open(page: PageRow | null): void {
    editing.value = page;
    modalActive.value = true;
  }

  function save(name: string, icon: string | null): void {
    if (editing.value) {
      emit('update', editing.value, name, icon);
    } else {
      emit('add', name, icon);
    }

    modalActive.value = false;
  }

  function validateName(name: string, page: PageRow | null): string | null {
    if (pageNameId(name) === '') {
      return t('{attribute} cannot be blank.', {attribute: t('Page Name')});
    }

    const id = pageNameId(name);
    const clash = props.pages.some(
      (p) => p !== page && pageNameId(p.name) === id
    );

    return clash ? t('Another page already has that name.') : null;
  }

  /**
   * A source dragged in here becomes a page of its own, beside the pages it's
   * dropped between — not a source *on* one of them, which is what the source's
   * own “Move to …” actions do.
   */
  function canDropForeign(data: DragData): boolean {
    return typeof data.sourceKey === 'string';
  }

  function onForeignDrop(data: DragData, index: number): void {
    if (typeof data.sourceKey !== 'string') return;

    emit('promote', data.sourceKey, index);
  }

  function actions(page: PageRow): ActionItem[] {
    return [
      {label: t('Settings'), onClick: () => open(page)},
      // Deleting the last page would leave its sources homeless.
      ...(props.pages.length > 1
        ? [
            {
              label: t('Delete'),
              variant: 'danger',
              onClick: () => emit('remove', page),
            } satisfies ActionItem,
          ]
        : []),
    ];
  }
</script>

<template>
  <div class="grid gap-3">
    <CustomSourceList
      :items="pages"
      :item-id="itemId"
      :label="itemId"
      :icon="icon"
      :selected="selected"
      :actions="actions"
      :can-drop-foreign="canDropForeign"
      @select="(name) => emit('select', name)"
      @reorder="(from, to) => emit('reorder', from, to)"
      @foreign-drop="onForeignDrop"
    />

    <div>
      <craft-button
        type="button"
        class="cs-add"
        @click="open(null)"
        icon="plus"
        variant="dashed"
      >
        {{ t('New page') }}
      </craft-button>
    </div>
  </div>

  <PageSettingsModal
    :is-active="modalActive"
    :page="editing"
    :validate-name="validateName"
    @close="modalActive = false"
    @save="save"
  />
</template>
