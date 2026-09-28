<script setup lang="ts">
  import {ButtonVariant, t} from '@craftcms/ui';
  import {onLongPress, useEventListener} from '@vueuse/core';
  import {computed, ref} from 'vue';
  import type {Selectable} from '@/common/composables/useSelectable';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItem} from '@/common/types';
  import {isInteractiveClick} from '@/common/utils/dom';
  import ElementCards from '@/modules/elements/components/ElementCards.vue';
  import {useCopiedElements} from '@/modules/matrix/copied-elements';
  import {craft} from '@/modules/matrix/interop';
  import {withoutStraySeparators} from '@/modules/matrix/selection-menu';
  import NestedEntriesCreateButton from './NestedEntriesCreateButton.vue';
  import {
    editLinkTarget,
    entryCan,
    type NestedEntry,
    type NestedEntriesManager,
  } from './nested-entries';

  const props = defineProps<{
    cards: NestedEntry[];
    manager: NestedEntriesManager | null;
    selection: Selectable<number>;
    singleColumn: boolean;
    editable: boolean;
    busy: boolean;
    /** Whether `count` more entries fit within the field's limits. */
    canAdd: (count: number) => boolean;
    canPaste: boolean;
    canReorder: boolean;
  }>();
  const emit = defineEmits<{
    edit: [entry: NestedEntry];
    duplicate: [ids: number[]];
    copy: [ids: number[]];
    delete: [ids: number[]];
    paste: [beforeId?: number];
    reorder: [from: number, to: number];
    move: [from: number, to: number];
    create: [attributes: Record<string, string | number>, opener: HTMLElement];
  }>();

  const copiedElements = useCopiedElements();
  const container = ref<HTMLElement>();
  onLongPress(container, (event) => {
    if (event.pointerType === 'touch') openCardAtEvent(event);
  });

  const displayCards = computed(() =>
    props.cards.map((card) => ({
      ...card,
      cardHeaderHtml: card.cardLabelHtml ?? '',
    }))
  );
  const cardsById = computed(
    () => new Map(props.cards.map((card) => [card.id, card]))
  );
  const createChoices = computed(() => {
    const choices = props.manager?.createAttributes;

    return Array.isArray(choices)
      ? choices.map((choice) => ({
          value: choice.attributes,
          label: choice.label,
          icon: choice.icon,
          color: choice.color,
          group: choice.group,
        }))
      : [{value: choices ?? {}, label: props.manager?.createButtonLabel ?? ''}];
  });

  function cardActionLabel(id: number): string {
    return t('Actions for {title}', {
      title: cardsById.value.get(id)?.cardAttributes?.data?.label ?? `#${id}`,
    });
  }

  function editLabel(card: NestedEntry): string {
    return t('Edit {title}', {
      title: card.cardAttributes?.data?.label ?? `#${card.id}`,
    });
  }

  function actionIds(id: number): number[] {
    return props.selection.isSelected(id)
      ? [...props.selection.selectedIds.value]
      : [id];
  }

  /** Names what's on the clipboard, like the legacy manager's paste button. */
  const pasteButtonLabel = computed(() => {
    const names = craft().elementTypeNames[props.manager?.elementType ?? ''];
    const single = copiedElements.value.length === 1;
    const label = t('Paste {type}', {
      type: names?.[single ? 2 : 3] ?? t(single ? 'entry' : 'entries'),
    });

    return label.charAt(0).toUpperCase() + label.slice(1);
  });

  function pasteLabel(): string {
    const names = craft().elementTypeNames[props.manager?.elementType ?? ''];
    const nameIndex = copiedElements.value.length === 1 ? 2 : 3;

    return t(
      props.singleColumn ? 'Paste {type} above' : 'Paste {type} before',
      {
        type:
          names?.[nameIndex] ??
          t(copiedElements.value.length === 1 ? 'entry' : 'entries'),
      }
    );
  }

  function cardActions(id: number): ActionItem[] {
    const card = cardsById.value.get(id);
    const ids = actionIds(id);
    const index = props.cards.findIndex((candidate) => candidate.id === id);

    return withoutStraySeparators(
      (card?.actionMenuItems ?? []).flatMap((item): ActionItem[] => {
        if (
          !('action' in item) ||
          item.action?.type !== 'event' ||
          item.action.name !== 'craft:nested-element-action'
        ) {
          return [item];
        }

        const name = item.action.detail?.action;
        if (typeof name !== 'string') {
          return [item];
        }
        const bulkLabel = item.action.detail?.bulkLabel;

        if (
          (!props.editable && name !== 'copy') ||
          (name === 'paste' && (!props.canPaste || !props.canReorder)) ||
          (name === 'move-forward' && (!props.canReorder || index === 0)) ||
          (name === 'move-backward' &&
            (!props.canReorder || index === props.cards.length - 1))
        ) {
          return [];
        }

        return [
          {
            ...item,
            label:
              (ids.length > 1 && typeof bulkLabel === 'string'
                ? bulkLabel
                : undefined) ?? (name === 'paste' ? pasteLabel() : item.label),
            disabled:
              item.disabled ||
              (name !== 'copy' && props.busy) ||
              (name === 'duplicate' && !props.canAdd(ids.length)),
          },
        ];
      })
    );
  }

  useEventListener(window, 'craft:nested-element-action', (event: Event) => {
    const {action, elementId, trigger} = (
      event as CustomEvent<{
        action: string;
        elementId: number;
        trigger: HTMLElement;
      }>
    ).detail;

    if (
      !container.value?.contains(trigger) ||
      (props.busy && action !== 'copy')
    ) {
      return;
    }

    if (!props.editable && action !== 'copy') {
      return;
    }

    const index = props.cards.findIndex((card) => card.id === elementId);
    switch (action) {
      case 'copy':
        emit('copy', actionIds(elementId));
        break;
      case 'duplicate':
        emit('duplicate', actionIds(elementId));
        break;
      case 'delete':
        emit('delete', actionIds(elementId));
        break;
      case 'paste':
        emit('paste', elementId);
        break;
      case 'move-forward':
      case 'move-backward':
        emit('move', index, index + (action === 'move-forward' ? -1 : 1));
        break;
    }
  });

  function canOpen(card: NestedEntry | null | undefined): card is NestedEntry {
    return Boolean(card?.editUrl) && entryCan(card ?? undefined, 'editable');
  }

  function openCard(
    card: NestedEntry | null | undefined,
    event?: MouseEvent
  ): void {
    if (!canOpen(card) || props.busy) {
      return;
    }

    if (card.cpEditUrl && (event?.ctrlKey || event?.metaKey)) {
      window.open(card.cpEditUrl, '_blank', 'noopener');

      return;
    }

    emit('edit', card);
  }

  function onCardLink(event: MouseEvent): void {
    const card = editLinkTarget(event, props.cards);
    if (canOpen(card)) {
      event.preventDefault();
      emit('edit', card);
    }
  }

  function openCardAtEvent(event: MouseEvent): void {
    if (!(event.target instanceof Element)) {
      return;
    }

    const item = event.target.closest('[data-nested-id]');
    if (!item || isInteractiveClick(event, item)) {
      return;
    }

    openCard(
      cardsById.value.get(Number(item.getAttribute('data-nested-id'))),
      event
    );
  }
</script>

<template>
  <div>
    <div
      ref="container"
      class="min-w-0 max-w-full overflow-x-auto"
      :aria-busy="busy"
      @click.capture="onCardLink"
      @dblclick="openCardAtEvent"
    >
      <ElementCards
        :data="displayCards"
        :item-behavior="{attrs: (element) => ({'data-nested-id': element.id})}"
        :selection="selection"
        :selectable="editable"
        :read-only="!editable"
        :single-column="singleColumn"
        :sortable="canReorder && !busy"
        :loading="busy && !cards.length"
        @reorder="(from, to) => emit('reorder', from, to)"
      >
        <template #actions="{element}">
          <craft-button
            v-if="canOpen(element as NestedEntry)"
            data-edit-entry
            type="button"
            size="small"
            icon="edit"
            :aria-label="editLabel(element as NestedEntry)"
            :title="t('Edit')"
            .disabled="busy"
            @click.stop="openCard(element as NestedEntry, $event)"
          ></craft-button>
          <ActionMenu
            v-if="
              element &&
              !(element as NestedEntry).cardAttributes?.data?.['revision-id'] &&
              cardActions(Number(element.id)).length
            "
            :label="cardActionLabel(Number(element.id))"
            :actions="cardActions(Number(element.id))"
            :flush="false"
          />
        </template>
        <template #empty>
          <craft-empty>{{ t('Nothing yet.') }}</craft-empty>
        </template>
      </ElementCards>
    </div>
    <div v-if="editable" class="nested-create">
      <craft-button
        v-if="canPaste"
        type="button"
        :variant="ButtonVariant.Fill"
        .disabled="busy || !canAdd(1)"
        @click="emit('paste')"
        >{{ pasteButtonLabel }}</craft-button
      >
      <NestedEntriesCreateButton
        v-if="manager?.canCreate"
        class="grow basis-full"
        :choices="createChoices"
        :label="manager.createButtonLabel || t('New entry')"
        :disabled="busy || !canAdd(1)"
        @create="(attributes, opener) => emit('create', attributes, opener)"
      />
    </div>
  </div>
</template>

<style scoped>
  :deep(.card-grid:not(.card-grid--single)) {
    grid-template-columns: repeat(auto-fill, minmax(min(270px, 100%), 1fr));
  }

  .nested-create {
    display: flex;
    flex-wrap: wrap;
    gap: var(--c-spacing-sm);
    align-items: center;
    margin-block: var(--c-spacing-sm);
  }
</style>
