<script setup lang="ts">
  import {ref} from 'vue';
  import {t} from '@craftcms/ui';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItem} from '@/common/types';
  import {useReorderableItems} from '@/common/composables/useReorderableItems';
  import PageIcon from './PageIcon.vue';
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

  function reorder(from: number, to: number): void {
    if (to < 0 || to > props.pages.length - 1) return;
    emit('reorder', from, to);
  }

  const {setItemRef, setHandleRef, getDragState, getRowPosition} =
    useReorderableItems({
      getItemIds: () => props.pages.map((page) => page.name),
      onReorder: reorder,
      enabled: () => props.pages.length > 1,
    });

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

  function actions(page: PageRow, index: number): ActionItem[] {
    return [
      {label: t('Settings'), onClick: () => open(page)},
      {
        label: t('Move up'),
        disabled: index === 0,
        onClick: () => reorder(index, index - 1),
      },
      {
        label: t('Move down'),
        disabled: index === props.pages.length - 1,
        onClick: () => reorder(index, index + 1),
      },
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
  <ol class="cs-list">
    <li
      v-for="(page, index) in pages"
      :key="page.name"
      :ref="(el) => setItemRef(el as HTMLElement, page.name)"
      class="cs-item"
      :class="{
        'cs-item--selected': page.name === selected,
        'cs-item--dragging': getDragState(page.name).type === 'is-dragging',
      }"
    >
      <span
        v-if="pages.length > 1"
        :ref="(el) => setHandleRef(el as HTMLElement, page.name)"
        class="cs-item__handle"
      >
        <craft-reorder-button
          :position="getRowPosition(index)"
          @reorder="
            (e: CustomEvent<{direction: 'up' | 'down'}>) =>
              reorder(
                index,
                e.detail.direction === 'up' ? index - 1 : index + 1
              )
          "
        />
      </span>

      <button
        type="button"
        class="cs-item__btn"
        :aria-pressed="page.name === selected"
        @click="emit('select', page.name)"
      >
        <PageIcon :icon="page.icon" />
        <span>{{ page.name }}</span>
      </button>

      <ActionMenu :actions="actions(page, index)" />
    </li>
  </ol>

  <craft-button type="button" class="cs-add" @click="open(null)">
    {{ t('New page') }}
  </craft-button>

  <PageSettingsModal
    :is-active="modalActive"
    :page="editing"
    :validate-name="validateName"
    @close="modalActive = false"
    @save="save"
  />
</template>

<style scoped lang="scss">
  .cs-list {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-xs);
    margin: 0 0 var(--c-spacing-m);
    padding: 0;
    list-style: none;
  }

  .cs-item {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-xs);
    padding-inline-end: var(--c-spacing-xs);
    border-radius: var(--c-border-radius-md);
    background-color: var(--c-bg-subtle);
  }

  .cs-item--selected {
    background-color: var(--c-bg-selected, var(--c-color-accent));
  }

  .cs-item--dragging {
    opacity: 0.4;
  }

  .cs-item__handle {
    display: inline-flex;
    align-items: center;
    color: var(--c-text-quiet);
    cursor: grab;
  }

  .cs-item__btn {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    gap: var(--c-spacing-xs);
    padding: var(--c-spacing-xs) 0;
    border: none;
    background: none;
    color: inherit;
    font: inherit;
    text-align: start;
    cursor: pointer;
  }
</style>
