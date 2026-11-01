<script setup lang="ts">
  /**
   * Edits a navigation, every level of it, as a structure table. The page
   * around it decides where the result is saved.
   *
   * Rows are dragged by their handle, or moved from its menu with the
   * keyboard. Settings for a row open in a slideout.
   */
  import {computed, h, ref} from 'vue';
  import {t} from '@craftcms/ui';
  import type {ReorderMove} from '@craftcms/ui';
  import type {ActionItem} from '@/common/types';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import {useCraftTable} from '@/common/table/craftTable';
  import {createCraftColumnHelper} from '@/common/table/createCraftColumnHelper';
  import {useTableRowSelection} from '@/common/composables/useTableRowSelection';
  import ElementTable from '@/modules/elements/index/components/ElementTable.vue';
  import {hideCollapsedDescendants} from '@/modules/elements/index/composables/useElementIndexStructure';
  import {openNavItemSettings} from '../settings-slideout';
  import {
    addLink,
    addSection,
    canPlace,
    flatten,
    moveNode,
    placement,
    remove,
    removeSection,
    update,
    type NavMove,
    type NavNode,
    type NavRow,
  } from '../tree';

  const tree = defineModel<NavNode[]>({required: true});
  const {readOnly = false} = defineProps<{readOnly?: boolean}>();

  const allRows = computed(() => flatten(tree.value));

  /**
   * Rows whose children are folded away. Items start collapsed, since their
   * subnavs make the list long; sections start open.
   */
  const collapsed = ref(
    new Set(
      allRows.value
        .filter((row) => row.node.kind !== 'section' && row.hasDescendants)
        .map((row) => row.id)
    )
  );

  function toggle(id: string): void {
    const next = new Set(collapsed.value);

    if (!next.delete(id)) next.add(id);

    collapsed.value = next;
  }

  const rows = computed(() =>
    hideCollapsedDescendants(allRows.value, (id) =>
      collapsed.value.has(String(id))
    )
  );

  function can(id: string, move: NavMove): boolean {
    return canPlace(tree.value, id, placement(tree.value, id, move));
  }

  function apply(id: string, move: NavMove): void {
    const result = moveNode(tree.value, id, move);

    if (!result) return;

    tree.value = result;

    // Moved into a collapsed row: open it, so the row can be seen landing.
    const parentId = flatten(result).find((row) => row.id === id)?.parentId;

    if (parentId && collapsed.value.has(parentId)) toggle(parentId);
  }

  /** Where a row can go besides up, down, in and out: the top level, or a section. */
  function moves(id: string): ReorderMove[] {
    const row = allRows.value.find((r) => r.id === id);

    if (!row || row.node.kind === 'section') return [];

    return [
      {id: null, label: t('the top level')},
      ...tree.value
        .filter((node) => node.kind === 'section')
        .map((section) => ({id: section.id, label: section.label})),
    ]
      .filter(
        (target) =>
          target.id !== row.parentId &&
          can(id, {type: 'to', parentId: target.id})
      )
      .map((target) => ({
        value: target.id ?? '',
        label: t('Move to {place}', {place: target.label}),
      }));
  }

  function edit(node: NavNode, opener: HTMLElement | null): void {
    openSettings(node, opener, (changes) => {
      tree.value = update(tree.value, node.id, changes);
    });
  }

  function openSettings(
    node: NavNode,
    opener: HTMLElement | null,
    apply: (changes: Partial<NavNode>) => void
  ): void {
    openNavItemSettings({node, readOnly, apply}, opener).catch(() => {
      // Such as a page left open across a deploy, whose slideout code is gone.
      window.Craft?.cp?.displayError?.(t('Couldn’t open the settings.'));
    });
  }

  const labelRefs = new Map<string, HTMLElement>();

  function actions(node: NavNode): ActionItem[] {
    const items: ActionItem[] = [
      {
        label: readOnly ? t('View') : t('Edit'),
        // Focus goes back to the row's label, not the menu that's closing.
        onClick: () => edit(node, labelRefs.get(node.id) ?? null),
      },
    ];

    if (readOnly || node.source === 'default') return items;

    return [
      ...items,
      {
        label: node.kind === 'section' ? t('Remove section') : t('Remove'),
        variant: 'danger',
        onClick: () =>
          (tree.value =
            node.kind === 'section'
              ? removeSection(tree.value, node.id)
              : remove(tree.value, node.id)),
      },
    ];
  }

  function sublabel(node: NavNode): string | null {
    return node.type === 'link' ? node.url : null;
  }

  const columnHelper = createCraftColumnHelper<NavRow>();
  const table = useCraftTable<NavRow>({
    get data() {
      return rows.value;
    },
    getRowId: (row) => row.id,
    columns: [
      columnHelper.display({
        id: 'label',
        header: t('Item'),
        meta: {columnClass: 'cp-table-cell--title nav-builder__item'},
        cell: ({row: {original: row}}) =>
          h(
            'button',
            {
              type: 'button',
              class: {
                'nav-row__label': true,
                'nav-row__label--strong':
                  row.node.kind === 'section' || row.node.heading,
                'nav-row__label--hidden': row.node.hidden,
              },
              ref: (el: unknown) =>
                el instanceof HTMLElement
                  ? labelRefs.set(row.id, el)
                  : labelRefs.delete(row.id),
              onClick: (event: MouseEvent) =>
                edit(row.node, event.currentTarget as HTMLElement),
            },
            [
              row.node.icon && row.node.kind === 'item'
                ? h('craft-icon', {name: row.node.icon, 'aria-hidden': 'true'})
                : null,
              h('span', {class: 'nav-row__text'}, row.label || t('(blank)')),
              sublabel(row.node)
                ? h('span', {class: 'nav-row__sublabel'}, sublabel(row.node)!)
                : null,
            ]
          ),
      }),
      columnHelper.display({
        id: 'shown',
        header: t('Show'),
        meta: {columnClass: 'nav-builder__show'},
        cell: ({row: {original: row}}) =>
          row.node.source === 'default'
            ? h(
                'craft-switch',
                {
                  size: 'small',
                  '.checked': !row.node.hidden,
                  '.disabled': readOnly,
                  onChange: (event: Event) =>
                    (tree.value = update(tree.value, row.id, {
                      hidden: !(event.target as HTMLInputElement).checked,
                    })),
                },
                [
                  h(
                    'span',
                    {slot: 'label', class: 'sr-only'},
                    t('Show {item}', {item: row.label})
                  ),
                ]
              )
            : null,
      }),
      columnHelper.actions(
        ({row: {original: row}}) => [
          h(ActionMenu, {
            // ActionMenu renders its button once, so a rename remounts it.
            key: `${row.id}:${row.label}`,
            actions: actions(row.node),
            label: t('Actions for {item}', {item: row.label}),
          }),
        ],
        {meta: {columnClass: 'nav-builder__actions'}}
      ),
    ],
  });
  const selection = useTableRowSelection(table, {
    selectable: false,
    readOnly: () => readOnly,
  });

  /** Adds a node once its settings are applied, so cancelling adds nothing. */
  function create(add: (nodes: NavNode[]) => [NavNode[], string]): void {
    const [preview, id] = add(tree.value);

    openSettings(
      preview.find((n) => n.id === id)!,
      null,
      (changes) => {
        const [next, addedId] = add(tree.value);
        tree.value = update(next, addedId, changes);
      }
    );
  }

  function newSection(): void {
    create((nodes) => addSection(nodes, t('New section')));
  }

  function newLink(): void {
    create((nodes) =>
      addLink(nodes, {
        label: t('New link'),
        url: 'https://',
        icon: null,
        newWindow: true,
      })
    );
  }
</script>

<template>
  <div class="nav-builder">
    <ElementTable
      :table="table"
      :selection="selection"
      :title="t('Navigation')"
      structure
      reorderable
      :is-row-collapsed="(id) => collapsed.has(String(id))"
      :can-move-row="(id, move) => can(String(id), move)"
      :structure-moves="(id) => moves(String(id))"
      @toggle-structure="(id) => toggle(String(id))"
      @move-structure-row="(id, move) => apply(String(id), move)"
      @move-structure-row-to="
        (id, value) => apply(String(id), {type: 'to', parentId: value || null})
      "
    />

    <ActionMenu
      v-if="!readOnly"
      class="nav-builder__add"
      :label="t('Add')"
      :actions="[
        {label: t('New section'), onClick: newSection},
        {label: t('New link'), onClick: newLink},
      ]"
    >
      <template #invoker="{attributes}">
        <craft-button
          v-bind="attributes"
          type="button"
          variant="dashed"
          icon="plus"
        >
          {{ t('Add') }}
        </craft-button>
      </template>
    </ActionMenu>
  </div>
</template>

<style scoped>
  .nav-builder__add {
    display: block;
    margin-block-start: var(--c-spacing-md);
  }

  /*
   * The item takes the spare width, so the switches and actions sit together
   * at the end of each row.
   */
  :deep(.nav-builder__item) {
    width: 100%;
  }

  :deep(.nav-builder__show),
  :deep(.nav-builder__actions) {
    width: 1%;
    white-space: nowrap;
  }

  :deep(.nav-row__label) {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--c-spacing-xs) var(--c-spacing-sm);
    padding-block: var(--c-spacing-xs);
    padding-inline: 0;
    border: 0;
    background: none;
    color: inherit;
    font: inherit;
    text-align: start;
    white-space: normal;
    cursor: pointer;
  }

  :deep(.nav-row__label:focus-visible) {
    outline: var(--c-focus-outline-width) var(--c-focus-outline-style)
      var(--c-color-focus-outline);
    outline-offset: 2px;
    border-radius: var(--c-radius-sm);
  }

  :deep(.nav-row__label--strong .nav-row__text) {
    font-weight: bold;
  }

  :deep(.nav-row__label--hidden .nav-row__text) {
    color: var(--c-text-quiet);
    text-decoration: line-through;
  }

  :deep(.nav-row__sublabel) {
    color: var(--c-text-quiet);
    font-size: var(--c-text-sm);
    overflow-wrap: anywhere;
  }
</style>
