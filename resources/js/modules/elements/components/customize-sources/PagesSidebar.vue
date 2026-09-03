<script setup lang="ts">
  import {ref} from 'vue';
  import {t} from '@craftcms/ui';
  import type {ActionItem} from '@/common/types';
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
      @select="(name) => emit('select', name)"
      @reorder="(from, to) => emit('reorder', from, to)"
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
