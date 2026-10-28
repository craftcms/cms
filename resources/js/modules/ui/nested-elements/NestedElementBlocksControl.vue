<script setup lang="ts">
  // `craft-action-menu`'s `actions` is a JS property (`attribute: false`), so the
  // element has to be defined before Vue patches it — otherwise the assignment
  // shadows the accessor and the menu never renders. Same reason ActionMenuNode
  // imports it. Leaf module, not the barrel.
  import '@craftcms/ui/components/action-menu/action-menu';
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/icon/icon';
  import '@craftcms/ui/components/status/status';
  import '@craftcms/ui/components/spinner/spinner';
  import '@craftcms/ui/components/tooltip/tooltip';
  import {t} from '@craftcms/ui';
  import {computed, ref, toRaw, useId} from 'vue';
  import {NEW_BLOCK_ATTRIBUTE} from '@/modules/matrix/new-block';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import {useSelectable} from '@/common/composables/useSelectable';
  import SelectableCardList from '@/common/components/SelectableCardList.vue';
  import UiNodeList from '../UiNodeList.vue';
  import NestedElementsCreateButton from './NestedElementsCreateButton.vue';
  import type {ActionItems} from '@/common/types';
  import type {
    UiChange,
    UiControlPayload,
    UiPayload,
    NestedElementValue,
    NestedUiPayload,
  } from '../types';
  import {inputName} from '../runtime';
  import {blockActions as resolveBlockActions} from './nested-element-block-menu';
  import type {
    EntryType,
    NestedElementBlocksProps,
  } from './nested-element-blocks';
  import {useBlockCollapse} from './useBlockCollapse';
  import {useBlockCreation} from './useBlockCreation';
  import {useBlockHighlight} from './useBlockHighlight';
  import {useBlockStatus} from './useBlockStatus';
  import {useMatrixFieldEvents} from './useMatrixFieldEvents';
  import {useScrollHold} from './useScrollHold';

  const props = defineProps<{
    control: UiControlPayload<NestedElementBlocksProps>;
    value: NestedElementValue;
    values: UiPayload['values'];
    errors: UiPayload['errors'];
    touchedPaths: Set<string>;
    editable: boolean;
  }>();
  const emit = defineEmits<{
    (
      event: 'update:value',
      value: NestedElementValue,
      kind: 'discrete',
      definition?: UiControlPayload<NestedElementBlocksProps>
    ): void;
    (event: 'change', change: UiChange): void;
  }>();
  const matrixHost = ref<HTMLElement>();
  const matrixId = useId();

  function update(
    next: NestedElementValue,
    definition?: UiControlPayload<NestedElementBlocksProps>
  ): void {
    if (definition) {
      emit('update:value', next, 'discrete', definition);
    } else {
      emit('update:value', next, 'discrete');
    }
  }

  const status = useBlockStatus({
    value: () => props.value,
    siteId: () => props.control.props.siteId,
    siteName: () => props.control.props.siteName,
    update,
  });
  const {
    isDisabled,
    isGloballyDisabled,
    isDisabledForSite,
    disabledLabel,
    setStatus,
  } = status;
  const {
    isCollapsed,
    preview,
    setCollapsed,
    toggleFromTitlebar,
    preventTitlebarSelection,
  } = useBlockCollapse({
    value: () => props.value,
    siteId: () => props.control.props.siteId,
    host: matrixHost,
    isDisabled,
    update,
  });

  /**
   * The card frame — selection, the select checkbox, drag-sort and the reorder
   * handle — comes from SelectableCardList, shared with the element index.
   */
  const selection = useSelectable<string>({
    ids: () => props.value.sortOrder,
    enabled: () => props.editable,
  });

  /**
   * The blocks a menu action applies to: the whole selection when the invoking
   * block is part of a multi-selection, otherwise just that block. Craft 5 spelled
   * this `bulkActionMode()`.
   */
  function actionTargets(uid: string): string[] {
    const selected = selection.selectedIds.value;

    return selected.length > 1 && selection.isSelected(uid)
      ? props.value.sortOrder.filter((id) => selected.includes(id))
      : [uid];
  }

  /**
   * Rebuilds the field whenever the block list changes.
   *
   * Heavy-handed, and deliberately so: a block can hold a control that
   * relocates its own light DOM — a Lion overlay behind an action menu, say —
   * and patching the list around one of those throws `insertBefore` on null,
   * which takes the whole ui down. Tearing the field down and building it
   * again is the way past that until those controls can survive a patch.
   *
   * The cost is that the field leaves the document for a frame, so
   * {@link useScrollHold} puts the page back where it was.
   */
  const key = computed(() =>
    JSON.stringify([
      props.value.sortOrder,
      props.control.uis?.map((ui) => ui.scope),
      props.editable,
    ])
  );
  const {setRevealing} = useScrollHold(key, matrixHost);
  const {highlightBlock, isHighlighted} = useBlockHighlight();

  const {
    uis,
    block,
    adding,
    pasting,
    busy,
    canAdd,
    pasteable,
    creationTypes,
    createChoices,
    addBlock,
    duplicateBlocks,
    copyBlocks,
    pasteBlocks,
  } = useBlockCreation({
    control: () => props.control,
    value: () => props.value,
    editable: () => props.editable,
    errors: () => props.errors,
    host: matrixHost,
    actionTargets,
    highlightBlock,
    setRevealing,
    update,
  });

  useMatrixFieldEvents({
    host: matrixHost,
    value: () => props.value,
    selection,
    actionTargets,
    isCollapsed,
    status,
    commands: {
      setCollapsed,
      setStatus,
      remove: removeBlocks,
      add: (entryType, beforeUid) => addBlock(entryType, beforeUid),
      duplicate: duplicateBlocks,
      copy: copyBlocks,
      paste: pasteBlocks,
    },
  });

  function statusId(uid: string): string {
    return `${matrixId}-${uid}-status`;
  }

  /**
   * Moves the block at `from` to `to`, keeping `entries` untouched.
   *
   * The whole selection travels when the block being moved is part of one, the
   * way Craft 5's drag-sort did — the drag engine only ever reports the one
   * block, so the rest are gathered here and land together, in the order they
   * were already in.
   */
  function move(from: number, to: number): void {
    if (from === to || from < 0 || to < 0) {
      return;
    }

    const next = structuredClone(toRaw(props.value));
    const [uid] = next.sortOrder.splice(from, 1);

    if (uid === undefined) {
      return;
    }

    const group = actionTargets(uid);
    let index = to;

    // Each block pulled out from before the landing point drags it back one.
    for (const id of group) {
      const at = next.sortOrder.indexOf(id);

      if (at === -1) {
        continue;
      }

      next.sortOrder.splice(at, 1);

      if (at < index) {
        index--;
      }
    }

    next.sortOrder.splice(index, 0, ...group);
    update(next);
  }

  /** Deletes blocks, as many as the field's minimum leaves room for. */
  function removeBlocks(targets: readonly string[]): void {
    const next = structuredClone(toRaw(props.value));
    const minimum = props.control.props.minEntries ?? 0;
    const removable = targets.slice(
      0,
      Math.max(next.sortOrder.length - minimum, 0)
    );

    if (!removable.length) {
      return;
    }

    for (const uid of removable) {
      delete next.entries[uid];
      selection.select(uid, false);
    }
    next.sortOrder = next.sortOrder.filter((uid) => !removable.includes(uid));
    update(next);
  }

  /**
   * Collapsing is the card's own state, so it can fold away body and footer.
   * The color goes on the card too, so it's styled as a card in that color —
   * its borders, and white's darker surface in the dark theme — not only
   * painted from the wrapper's palette. Without a color, the card has none
   * of its own either, taking the colors around it.
   */
  function blockCardAttrs(uid: string): Record<string, unknown> {
    return {
      collapsed: isCollapsed(uid),
      'data-color': block(uid)?.color || undefined,
    };
  }

  function blockIcon(uid: string): {name: string; family: string} | null {
    return block(uid)?.icon ?? null;
  }

  function blockAttrs(uid: string): Record<string, unknown> {
    const presentation = block(uid);

    return {
      ...blockData(uid),
      'data-id': uid,
      'data-type': props.value.entries[uid]?.type ?? '',
      // The CP's generated colorable rules turn this into the whole `--c-color-*`
      // alias set, which the card and everything in it paints from.
      'data-color': presentation?.color ?? undefined,
      'data-ui-label': uiLabel(uid) || undefined,
      'data-collapsed': isCollapsed(uid) ? '' : undefined,
      'data-disabled': isDisabled(uid) ? '' : undefined,
      'data-disabled-global': isGloballyDisabled(uid) ? '' : undefined,
      'data-disabled-site': isDisabledForSite(uid) ? '' : undefined,
      [NEW_BLOCK_ATTRIBUTE]: isHighlighted(uid) ? '' : undefined,
      // No Craft 5 class names: the legacy stylesheet styles them, and would
      // restyle the card. Behavior hangs off these data attributes instead.
      'data-matrix-block': '',
      role: 'listitem',
    };
  }

  /**
   * The block's identity as `data-*` attributes. The CP's element clipboard reads
   * it back off the DOM, so copy and paste need it there rather than only in the
   * payload. Absent for a block the browser minted — there's no element yet.
   */
  function blockData(uid: string): Record<string, number | string> {
    return Object.fromEntries(
      Object.entries(block(uid)?.data ?? {}).map(([name, value]) => [
        `data-${name}`,
        value,
      ])
    );
  }

  function blockActions(uid: string): ActionItems {
    return resolveBlockActions(block(uid)?.actions, {
      uid,
      targets: actionTargets(uid),
      collapsed: isCollapsed(uid),
      status,
      siteName: props.control.props.siteName,
      creationTypes: creationTypes.value,
      canAdd: canAdd.value,
      busy: busy.value,
      pasteable: pasteable.value.length,
    });
  }

  /**
   * Announced while the server mints or pastes a block. The add buttons show a
   * spinner, but "Add {type} above" is a menu item with nowhere to put one.
   */
  const statusMessage = computed(() => (busy.value ? t('Loading') : ''));

  /**
   * What a folded-up block is called. Its own fields aren't on screen to
   * identify it, so the UI label stands in.
   *
   * The server's copy is authoritative but only as fresh as the last save, so a
   * live title wins while it's being typed.
   */
  function uiLabel(uid: string): string {
    const title = props.value.entries[uid]?.title;

    if (typeof title === 'string' && title.trim() !== '') {
      return title;
    }

    return block(uid)?.label ?? '';
  }

  /**
   * What a folded-up block shows in its header: its UI label, or — for an entry
   * type that has none — a summary of its own field values, the way Craft 5
   * fell back.
   */
  function previewText(uid: string): string {
    return uiLabel(uid) || preview(uid);
  }

  /** Whether the block has errors the header should own up to. */
  function hasErrors(uid: string): boolean {
    if (block(uid)?.error) {
      return true;
    }

    const scope = [...props.control.path, 'entries', uid];

    return props.errors.some((error) =>
      scope.every((segment, index) => error.path[index] === segment)
    );
  }

  function entryType(uid: string): EntryType | undefined {
    const handle = props.value.entries[uid]?.type;

    return props.control.props.entryTypes?.find(
      (type) => type.value === handle
    );
  }

  function nestedChange(change: UiChange, ui: NestedUiPayload): void {
    emit('change', {
      ...change,
      scope: ui.scope,
      refreshable: ui.refreshable && change.refreshable,
    });
  }
</script>

<template>
  <div ref="matrixHost" :key="key" class="form-control" data-matrix-field>
    <input v-if="editable" type="hidden" :name="inputName(control.path)" />
    <div :id="matrixId">
      <span role="status" class="sr-only" data-status-message>{{
        statusMessage
      }}</span>
      <SelectableCardList
        role="list"
        data-matrix-blocks
        :ids="value.sortOrder"
        :selection="selection"
        :selectable="editable"
        :sortable="editable"
        :read-only="!editable"
        single-column
        tag="div"
        item-tag="div"
        list-class="grid gap-md"
        :item-attrs="blockAttrs"
        :card-attrs="blockCardAttrs"
        @reorder="move"
        @header-mousedown="preventTitlebarSelection"
        @header-dblclick="toggleFromTitlebar"
      >
        <template #label="{id: uid}">
          <div
            class="flex flex-nowrap gap-sm items-center"
            data-matrix-block-titlebar
            data-card-label
          >
            <craft-icon v-if="blockIcon(uid)" v-bind="blockIcon(uid)!" />
            {{ entryType(uid)?.label ?? uid }}
            <craft-icon
              v-if="hasErrors(uid)"
              name="triangle-exclamation"
              :aria-label="t('Error')"
            />

            <div data-matrix-block-preview v-if="isCollapsed(uid)">
              {{ previewText(uid) }}
            </div>
          </div>
        </template>

        <template #actions="{id: uid}">
          <craft-status
            v-if="isDisabled(uid)"
            :id="statusId(uid)"
            status="disabled"
            :label="disabledLabel(uid)"
          />
          <craft-tooltip v-if="isDisabled(uid)" :for="statusId(uid)">
            {{ disabledLabel(uid) }}
          </craft-tooltip>
          <ActionMenu
            v-if="editable"
            :actions="blockActions(uid)"
            :flush="false"
            :label="
              t('{type} actions', {
                type: entryType(uid)?.label ?? uid,
              })
            "
          />
        </template>

        <template #default="{id}">
          <template v-if="editable">
            <input
              type="hidden"
              :name="`${inputName(control.path)}[sortOrder][]`"
              :value="id"
            />
            <input
              type="hidden"
              :name="`${inputName(control.path)}[entries][${id}][type]`"
              :value="value.entries[id]?.type ?? ''"
            />
            <input
              v-for="state in ['enabled', 'enabledForSite', 'collapsed']"
              :key="state"
              type="hidden"
              :name="`${inputName(control.path)}[entries][${id}][${state}]`"
              :value="
                (value.entries[id]?.[state] ?? state !== 'collapsed') ? '1' : ''
              "
            />
          </template>
          <div data-matrix-block-fields>
            <template v-if="uis.get(id)">
              <UiNodeList
                :nodes="uis.get(id)!.nodes"
                :values="values"
                :errors="errors"
                :touched-paths="touchedPaths"
                :scope="uis.get(id)!.scope"
                :refreshable="uis.get(id)!.refreshable"
                @change="nestedChange($event, uis.get(id)!)"
              />
            </template>
            <craft-spinner v-else :label="t('Loading')" />
          </div>
        </template>
      </SelectableCardList>
      <div v-if="canAdd" class="mt-md">
        <NestedElementsCreateButton
          :choices="createChoices"
          :label="control.props.addLabel"
          :adding="adding"
          :disabled="busy"
          @create="addBlock($event)"
        />
        <craft-button
          v-if="pasteable.length"
          type="button"
          variant="outline"
          icon="custom-icons/duplicate"
          class="paste-btn mt-sm"
          .loading="pasting"
          .disabled="busy"
          @click.stop.prevent="pasteBlocks()"
        >
          {{
            t('Paste {type}', {
              type: pasteable.length === 1 ? t('block') : t('blocks'),
            })
          }}
        </craft-button>
      </div>
    </div>
  </div>
</template>
