<script setup lang="ts">
  /**
   * The elements a `show_elements` call showed, as cards or a list of chips,
   * each with its action menu.
   *
   * They're drawn through the shared presentation cache, as relation fields
   * draw theirs, so they're redrawn when they're saved, in this tab or
   * another one, while the chat stays open.
   */
  import {t} from '@craftcms/ui';
  import {computed, useTemplateRef} from 'vue';
  import ElementCards from '@/modules/elements/components/ElementCards.vue';
  import ElementChips, {
    type ChipElement,
  } from '@/modules/elements/components/ElementChips.vue';
  import {useSelectable} from '@/common/composables/useSelectable';
  import {useFocusThroughRedraw} from '@/common/composables/useFocusThroughRedraw';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import {
    createElementActionMenu,
    type ElementActionMenuItem,
  } from '@/modules/elements/composables/useElementActionMenu';
  import {useElementPresentations} from '@/modules/elements/element-presentations';

  /** An element a `show_elements` call showed, with its class and its chip’s menu. */
  export type ShownElement = ChipElement & {
    elementType?: string;
    actions?: ElementActionMenuItem[];
  };

  const props = defineProps<{
    elements: ShownElement[];
    view: 'cards' | 'list';
    /** The thumbnail size the server drew them at, if they have thumbnails. */
    thumbSize?: string | null;
  }>();

  const cardList = useTemplateRef<HTMLElement>('cardList');

  /** One dispatcher for every shown element’s menu. */
  const toActionItems = createElementActionMenu();

  /** Shown elements can’t be selected. */
  const noSelection = useSelectable<number>({ids: [], enabled: false});

  function ofType(type: string): ShownElement[] {
    return props.elements.filter(({elementType}) => elementType === type);
  }

  /**
   * The cache of each type of element shown, which is drawn the same way.
   * The chat doesn’t seed it: what it’s shown is trimmed for the chat, and a
   * relation field drawing the same element needs all of it.
   */
  const caches = new Map(
    [
      ...new Set(props.elements.flatMap(({elementType}) => elementType ?? [])),
    ].map(
      (type) =>
        [
          type,
          useElementPresentations(
            () => ({
              elementType: type,
              viewMode: props.view,
              thumbSize: props.thumbSize,
            }),
            () =>
              ofType(type).map(({id, siteId}) => ({
                id,
                siteId: Number(siteId) || null,
              }))
          ),
        ] as const
    )
  );

  /**
   * Each element as it’s cached now, or as the call showed it. Without its
   * structure level, which would nest chips under the ones shown before them.
   */
  const shown = computed(() =>
    props.elements.map(
      (element): ShownElement => ({
        ...((element.elementType &&
          caches.get(element.elementType)?.get(element.id, element.siteId)) ||
          element),
        level: undefined,
      })
    )
  );

  useFocusThroughRedraw(cardList, shown, () =>
    props.elements.map(({id}) => id).join()
  );
</script>

<template>
  <div v-if="view === 'cards'" ref="cardList">
    <ElementCards
      :data="shown"
      :selection="noSelection"
      read-only
      single-column
      :render-server-actions="false"
    >
      <template #actions="{element}">
        <ActionMenu
          v-if="(element as ShownElement).actions?.length"
          :actions="toActionItems((element as ShownElement).actions!)"
          :label="t('Actions for {name}', {name: element?.label ?? ''})"
        />
      </template>
    </ElementCards>
  </div>
  <ElementChips v-else :data="shown" :selection="noSelection" read-only linked>
    <template #suffix="{element}">
      <ActionMenu
        v-if="(element as ShownElement).actions?.length"
        :actions="toActionItems((element as ShownElement).actions!)"
        :label="t('Actions for {name}', {name: element.label})"
        flush="inline-start"
      />
    </template>
  </ElementChips>
</template>
