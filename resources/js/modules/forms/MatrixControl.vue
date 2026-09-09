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
  import {actionClient, t} from '@craftcms/ui';
  import {
    computed,
    onBeforeUnmount,
    nextTick,
    onMounted,
    ref,
    toRaw,
    useId,
    watch,
  } from 'vue';
  import '@/modules/matrix';
  import {
    collapsedBlockId,
    isBlockCollapsed,
    setBlockCollapsed,
  } from '@/modules/matrix/collapsed-blocks';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import {useSelectable} from '@/common/composables/useSelectable';
  import SelectableCardList from '@/common/components/SelectableCardList.vue';
  import FormNodeList from './FormNodeList.vue';
  import type {ActionItems} from '@/common/types';
  import {useFlashMessages} from '@/common/composables/useFlashMessages';
  import {
    NESTED_ELEMENT_UID_PREFIX,
    type FormChange,
    type FormControlPayload,
    type FormPayload,
    type FormValue,
    type FormValues,
    type NestedElementValue,
    type NestedFormPayload,
  } from './types';
  import {inputName, isRecord, valueAt} from './runtime';

  type EntryType = {value: string; label: string};
  type MatrixProps = {
    entryTypes?: EntryType[];
    addLabel: string;
    minEntries?: number | null;
    maxEntries?: number | null;
    /** Per-block presentation, keyed by identity. Server-built; never posted. */
    blocks?: Record<
      string,
      {
        label?: string;
        icon?: {name: string; family: string} | null;
        color?: string | null;
        actions?: ActionItems;
      }
    >;
    /**
     * What `matrix/create-entry` needs to mint a block, or absent when the server
     * can't — an unsaved owner, or a nested element field that isn't Matrix-backed.
     */
    create?: {
      fieldId: number;
      ownerId: number;
      ownerElementType: string;
      siteId: number;
      entryTypeIds: Record<string, number>;
    } | null;
  };
  type CreatedBlock = {
    uid: string;
    type: string;
    form: NestedFormPayload;
    values: FormValues;
  };
  /** The instance-local half of a block's menu. See `Matrix::blockActions()`. */
  type BlockActionDetail = {
    action: 'collapse' | 'expand' | 'disable' | 'enable' | 'delete' | 'add';
    uid: string;
    entryType?: string;
    trigger?: unknown;
  };
  type MatrixValue = NestedElementValue;

  const props = defineProps<{
    control: FormControlPayload<MatrixProps>;
    /**
     * Undefined for a beat whenever this control's path isn't in the values tree
     * yet — a nested repeater inside a block whose identity the server has just
     * rewritten, say. Read {@link model} rather than this.
     */
    value: MatrixValue | undefined;
    values: FormPayload['values'];
    errors: FormPayload['errors'];
    touchedPaths: Set<string>;
    editable: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: MatrixValue, kind: 'discrete'): void;
    (event: 'change', change: FormChange): void;
  }>();
  const EMPTY: MatrixValue = {entries: {}, sortOrder: []};
  const model = computed<MatrixValue>(() => props.value ?? EMPTY);
  const matrixHost = ref<HTMLElement>();
  const matrixId = useId();
  /**
   * Forms for blocks the server minted since the last full payload. They're
   * dropped as soon as that payload catches up and carries them itself.
   */
  const created = ref(new Map<string, NestedFormPayload>());
  const {flash} = useFlashMessages();
  const adding = ref<string | null>(null);

  const forms = computed(() => {
    const map = new Map<string, NestedFormPayload>();

    for (const [uid, form] of created.value) {
      map.set(uid, form);
    }

    for (const form of props.control.forms ?? []) {
      // Entries added client-side are keyed with a `uid:` prefix, which the
      // server strips before saving — so their forms come back scoped to the
      // bare UUID while the block is still keyed with the prefix.
      const uid = form.scope.at(-1)!;
      map.set(uid, form);
      map.set(`${NESTED_ELEMENT_UID_PREFIX}${uid}`, form);
    }

    return map;
  });
  const canAdd = computed(
    () =>
      props.editable &&
      (!props.control.props.maxEntries ||
        model.value.sortOrder.length < props.control.props.maxEntries)
  );
  const entryTypes = computed(() =>
    (props.control.props.entryTypes ?? []).map((type, index) => ({
      id: index + 1,
      handle: type.value,
      name: type.label,
    }))
  );
  const settings = computed(() =>
    JSON.stringify({
      formControl: true,
      maxEntries: props.control.props.maxEntries,
    })
  );
  const key = computed(() =>
    JSON.stringify([
      model.value.sortOrder,
      props.control.forms?.map((form) => form.scope),
      props.editable,
    ])
  );

  function sync(event?: Event): void {
    const value = structuredClone(toRaw(model.value));
    const source =
      event?.currentTarget instanceof HTMLElement
        ? event.currentTarget
        : matrixHost.value;
    const entries = [
      ...(source?.querySelectorAll<HTMLElement>('.matrixblock') ?? []),
    ];
    value.sortOrder = entries.map((entry) => entry.dataset.id!);
    value.entries = Object.fromEntries(
      entries.map((entry) => {
        const uid = entry.dataset.id!;

        return [
          uid,
          value.entries[uid] ?? {type: entry.dataset.type ?? '', enabled: true},
        ];
      })
    );
    emit('update:value', value, 'discrete');
  }

  /**
   * Collapsed blocks live in localStorage, not the value — it's a view
   * preference, and posting it would mark the form dirty just for collapsing
   * something. Craft 5 did the same. `collapsedTick` re-reads storage after a
   * write, since a plain module read isn't reactive.
   */
  const collapsedTick = ref(0);

  function isCollapsed(uid: string): boolean {
    void collapsedTick.value;

    return isBlockCollapsed(uid);
  }

  function setCollapsed(uid: string, collapsed: boolean): void {
    setBlockCollapsed(uid, collapsed);
    collapsedTick.value++;

    // A block the browser minted isn't in storage under an identity the server
    // knows yet, so its state also rides along in the posted value — the same
    // job Craft 5's hidden `[collapsed]` input did for new blocks.
    if (uid.startsWith(NESTED_ELEMENT_UID_PREFIX)) {
      const next = structuredClone(toRaw(model.value));

      if (next.entries[uid]) {
        next.entries[uid].collapsed = collapsed;
        emit('update:value', next, 'discrete');
      }
    }
  }

  /**
   * Blocks the server says are collapsed (a new block whose posted `collapsed`
   * came back) are adopted into storage once, so the two agree from then on.
   */
  watch(
    () => props.control.forms,
    (serverForms) => {
      if (!created.value.size || !serverForms?.length) {
        return;
      }

      const known = new Set(serverForms.map((form) => form.scope.at(-1)));
      const next = new Map(
        [...created.value].filter(([uid]) => !known.has(uid))
      );

      if (next.size !== created.value.size) {
        created.value = next;
      }
    }
  );

  function isDisabled(uid: string): boolean {
    return model.value.entries[uid]?.enabled === false;
  }

  onMounted(() => {
    for (const uid of model.value.sortOrder) {
      // A disabled block isn't being edited, so it opens out of the way. It can
      // still be expanded from its menu — this only decides where it starts.
      const startsCollapsed =
        model.value.entries[uid]?.collapsed || isDisabled(uid);

      if (startsCollapsed && !isBlockCollapsed(uid)) {
        setBlockCollapsed(uid, true);
      }
    }
    collapsedTick.value++;
  });

  /**
   * The card frame — selection, the select checkbox, drag-sort and the reorder
   * handle — comes from SelectableCardList, shared with the element index.
   */
  const selection = useSelectable<string>({
    ids: () => model.value.sortOrder,
    enabled: () => props.editable,
  });

  /**
   * Adds a block, letting the server mint it the way Craft 5 did: the button
   * shows a loading state while `matrix/create-entry` persists the entry as a
   * draft and hands back its form nodes, which render through FormNodeList like
   * any other form. The identity is the server's, so nothing has to be
   * reconciled when the next save comes around.
   *
   * Without a `create` config (an unsaved owner, or an Addresses field on this
   * same Control) the browser mints the block and the next save materializes it.
   */
  async function addBlock(
    entryType: string,
    beforeUid?: string
  ): Promise<void> {
    if (!canAdd.value || adding.value !== null) {
      return;
    }

    const create = props.control.props.create;
    const at = beforeUid
      ? model.value.sortOrder.indexOf(beforeUid)
      : model.value.sortOrder.length;
    const index = at < 0 ? model.value.sortOrder.length : at;

    if (!create) {
      await insertBlock(
        `${NESTED_ELEMENT_UID_PREFIX}${crypto.randomUUID()}`,
        entryType,
        index
      );

      return;
    }

    adding.value = entryType;

    try {
      const {data} = await actionClient.post<CreatedBlock>(
        'matrix/create-entry',
        {
          fieldId: create.fieldId,
          entryTypeId: create.entryTypeIds[entryType],
          ownerId: create.ownerId,
          ownerElementType: create.ownerElementType,
          siteId: create.siteId,
          path: props.control.path,
        }
      );

      created.value = new Map(created.value).set(data.uid, data.form);

      // The block's own field values ride along in the same emit. Writing them
      // straight into `values` wouldn't survive: the Control's value is written
      // back wholesale at its own path, which would drop anything under the
      // block that wasn't part of it.
      const blockValues = valueAt(data.values as FormValue, data.form.scope);
      await insertBlock(
        data.uid,
        data.type,
        index,
        isRecord(blockValues) ? blockValues : {}
      );
    } catch (error) {
      flash('error', t('Couldn’t create {type}.', {type: t('entry')}));
      throw error;
    } finally {
      adding.value = null;
    }
  }

  /**
   * Deferred a tick on purpose. Changing sortOrder re-keys `craft-matrix-input`,
   * which tears the whole subtree down and rebuilds it — and the button that was
   * clicked lives in there. Doing that while its click is still dispatching
   * leaves Vue patching against DOM a Lion overlay inside a block has already
   * moved, which throws `insertBefore` on null and takes the form down with it.
   */
  async function insertBlock(
    uid: string,
    entryType: string,
    index: number,
    values: FormValues = {}
  ): Promise<void> {
    await nextTick();

    const next = structuredClone(toRaw(model.value));
    next.entries[uid] = {...values, type: entryType, enabled: true};
    next.sortOrder.splice(index, 0, uid);
    emit('update:value', next, 'discrete');
  }

  /** Moves the block at `from` to `to`, keeping `entries` untouched. */
  function move(from: number, to: number): void {
    if (from === to || from < 0 || to < 0) {
      return;
    }

    const next = structuredClone(toRaw(model.value));
    const [uid] = next.sortOrder.splice(from, 1);

    if (uid === undefined) {
      return;
    }

    next.sortOrder.splice(to, 0, uid);
    emit('update:value', next, 'discrete');
  }

  /** Collapsing is the card's own state, so it can fold away body and footer. */
  function blockCardAttrs(uid: string): Record<string, unknown> {
    return {collapsed: isCollapsed(uid)};
  }

  function blockIcon(uid: string): {name: string; family: string} | null {
    return props.control.props.blocks?.[uid]?.icon ?? null;
  }

  /**
   * `.matrixblock` stays the direct child of the blocks container: the legacy
   * `craft-matrix-input` still finds its entries through it, and `sync()` reads
   * the identity back off `data-id`.
   */
  function blockAttrs(uid: string): Record<string, unknown> {
    return {
      'data-id': uid,
      'data-type': model.value.entries[uid]?.type ?? '',
      // The CP's generated colorable rules turn this into the whole `--c-color-*`
      // alias set, which the card and everything in it paints from.
      'data-color': props.control.props.blocks?.[uid]?.color ?? undefined,
      'data-matrix-block': '',
      role: 'listitem',
      class: {
        collapsed: isCollapsed(uid),
        'disabled-entry': model.value.entries[uid]?.enabled === false,
      },
    };
  }

  /**
   * The blocks a menu action applies to: the whole selection when the invoking
   * block is part of a multi-selection, otherwise just that block. Craft 5 spelled
   * this `bulkActionMode()`.
   */
  function actionTargets(uid: string): string[] {
    const selected = selection.selectedIds.value;

    return selected.length > 1 && selection.isSelected(uid)
      ? model.value.sortOrder.filter((id) => selected.includes(id))
      : [uid];
  }

  /** Actions whose label depends on live state, so the server's copy goes stale. */
  const STATEFUL = new Set(['collapse', 'expand', 'disable', 'enable']);

  function blockEvent(
    uid: string,
    action: string,
    detail: Record<string, unknown> = {}
  ) {
    return {
      type: 'event' as const,
      name: 'craft:matrix-block-action',
      detail: {action, uid, ...detail},
    };
  }

  /** Collapse/Expand and Disable/Enable, resolved against the block right now. */
  function stateActions(uid: string): ActionItems {
    const block = model.value.entries[uid];

    return [
      isCollapsed(uid)
        ? {
            label: t('Expand'),
            icon: 'up-right-and-down-left-from-center',
            action: blockEvent(uid, 'expand'),
          }
        : {
            label: t('Collapse'),
            icon: 'down-left-and-up-right-to-center',
            action: blockEvent(uid, 'collapse'),
          },
      block?.enabled === false
        ? {
            label: t('Enable'),
            icon: 'circle',
            action: blockEvent(uid, 'enable'),
          }
        : {
            label: t('Disable'),
            icon: 'circle-dashed',
            action: blockEvent(uid, 'disable'),
          },
    ];
  }

  /**
   * A block the browser minted has no server-built menu until the next save
   * materializes it, so compose the half that needs no server data.
   */
  function localActions(uid: string): ActionItems {
    return [
      ...stateActions(uid),
      {type: 'hr'},
      {
        label: t('Delete'),
        icon: 'trash',
        variant: 'danger',
        action: blockEvent(uid, 'delete'),
      },
      {type: 'hr'},
      ...(props.control.props.entryTypes ?? []).map((type) => ({
        label: t('Add {type} above', {type: type.label}),
        icon: 'plus',
        disabled: adding.value !== null,
        action: blockEvent(uid, 'add', {entryType: type.value}),
      })),
    ];
  }

  /**
   * Announced while the server mints a block. The add buttons show a spinner,
   * but "Add {type} above" is a menu item with nowhere to put one.
   */
  const statusMessage = computed(() =>
    adding.value === null ? '' : t('Loading')
  );

  /**
   * What a folded-up block is called. Its own fields aren't on screen to
   * identify it, so the UI label stands in.
   *
   * The server's copy is authoritative but only as fresh as the last save, so a
   * live title wins while it's being typed.
   */
  function uiLabel(uid: string): string {
    const title = model.value.entries[uid]?.title;

    if (typeof title === 'string' && title.trim() !== '') {
      return title;
    }

    return props.control.props.blocks?.[uid]?.label ?? '';
  }

  function blockActions(uid: string): ActionItems {
    const server = props.control.props.blocks?.[uid]?.actions;

    if (!server?.length) {
      return localActions(uid);
    }

    // Drop the server's stateful pair — including the ones it marked hidden —
    // and lead with a freshly resolved one.
    return [
      ...stateActions(uid),
      ...server.filter((item) => {
        const action = 'action' in item ? item.action : undefined;
        const name =
          action?.type === 'event'
            ? (action.detail?.action as string | undefined)
            : undefined;

        if (name === 'add') {
          // Server-built, so it can't know a create is already in flight.
          Object.assign(item, {disabled: adding.value !== null});
        }

        return !(name && STATEFUL.has(name)) && !('hidden' in item);
      }),
    ];
  }

  /**
   * `runAction()` dispatches `event` actions on `window`, so every Matrix on the
   * page hears every block action. Scope by the invoking element: the menu keeps
   * its content in place, so the trigger is still inside the block it belongs to.
   */
  function onBlockAction(event: Event): void {
    const detail = (event as CustomEvent<BlockActionDetail>).detail;

    if (!detail || !matrixHost.value) {
      return;
    }

    const trigger = detail.trigger;
    const owned =
      trigger instanceof HTMLElement &&
      trigger.closest('craft-matrix-input') === matrixHost.value;

    if (!owned) {
      return;
    }

    const targets = actionTargets(detail.uid);
    const next = structuredClone(toRaw(model.value));

    switch (detail.action) {
      case 'collapse':
      case 'expand':
        for (const uid of targets) {
          setCollapsed(uid, detail.action === 'collapse');
        }

        return;

      case 'disable':
      case 'enable': {
        const enabled = detail.action === 'enable';

        for (const uid of targets) {
          if (next.entries[uid]) {
            next.entries[uid].enabled = enabled;

            // Disabling folds the block away; enabling brings it back, which is
            // what Craft 5's enable did too. Written straight onto `next` rather
            // than through `setCollapsed`, whose own emit this one would clobber.
            if (uid.startsWith(NESTED_ELEMENT_UID_PREFIX)) {
              next.entries[uid].collapsed = !enabled;
            }
          }

          setBlockCollapsed(uid, !enabled);
        }
        collapsedTick.value++;
        break;
      }

      case 'delete': {
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
        next.sortOrder = next.sortOrder.filter(
          (uid) => !removable.includes(uid)
        );
        break;
      }

      case 'add':
        void addBlock(detail.entryType ?? '', detail.uid);

        return;

      default:
        return;
    }

    emit('update:value', next, 'discrete');
  }

  onMounted(() =>
    window.addEventListener('craft:matrix-block-action', onBlockAction)
  );
  onBeforeUnmount(() =>
    window.removeEventListener('craft:matrix-block-action', onBlockAction)
  );

  function entryType(uid: string): EntryType | undefined {
    const handle = model.value.entries[uid]?.type;

    return props.control.props.entryTypes?.find(
      (type) => type.value === handle
    );
  }

  function nestedChange(change: FormChange, form: NestedFormPayload): void {
    emit('change', {
      ...change,
      scope: form.scope,
      refreshable: form.refreshable && change.refreshable,
    });
  }
</script>

<template>
  <craft-matrix-input
    ref="matrixHost"
    :key="key"
    form-control
    :entry-types="JSON.stringify(entryTypes)"
    :input-name-prefix="inputName(control.path)"
    :settings="settings"
    :min-entries="control.props.minEntries ?? 0"
    @form-change="sync"
  >
    <input v-if="editable" type="hidden" :name="inputName(control.path)" />
    <div :id="matrixId" class="matrix matrix-field">
      <span role="status" class="sr-only" data-status-message>{{
        statusMessage
      }}</span>
      <!-- `data-matrix-blocks` sits on the list itself: the legacy
           `craft-matrix-input` finds its entries with `:scope > .matrixblock`,
           so a wrapper between the two hides every block from it. -->
      <SelectableCardList
        role="list"
        data-matrix-blocks
        :ids="model.sortOrder"
        :selection="selection"
        :selectable="editable"
        :sortable="editable"
        :read-only="!editable"
        single-column
        tag="div"
        item-tag="div"
        list-class="grid gap-1"
        :item-class="() => 'matrixblock js-deletable'"
        :item-attrs="blockAttrs"
        :card-attrs="blockCardAttrs"
        @reorder="move"
        @item-click="(uid, event) => selection.handleClick(uid, event)"
      >
        <template #label="{id: uid}">
          <div class="flex flex-nowrap gap-1 items-center">
            <craft-icon v-if="blockIcon(uid)" v-bind="blockIcon(uid)!" />
            {{ entryType(uid)?.label ?? uid }}

            <div class="preview" v-if="isCollapsed(uid)">
              {{ uiLabel(uid) }}
            </div>
          </div>
        </template>

        <template #actions="{id: uid}">
          <craft-status
            v-if="isDisabled(uid)"
            status="disabled"
            :label="t('Disabled')"
          />
          <ActionMenu
            v-if="editable"
            :actions="blockActions(uid)"
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
              :value="model.entries[id]?.type ?? ''"
            />
          </template>
          <div class="fields">
            <template v-if="forms.get(id)">
              <FormNodeList
                :nodes="forms.get(id)!.nodes"
                :values="values"
                :errors="errors"
                :touched-paths="touchedPaths"
                :scope="forms.get(id)!.scope"
                :refreshable="forms.get(id)!.refreshable"
                @change="nestedChange($event, forms.get(id)!)"
              />
            </template>
            <craft-spinner v-else :label="t('Loading')" />
          </div>
        </template>
      </SelectableCardList>
      <div v-if="canAdd" class="flex flex-wrap gap-1 items-center mt-3">
        <craft-button
          v-for="type in control.props.entryTypes"
          :key="type.value"
          type="button"
          variant="dashed"
          icon="plus"
          :loading="adding === type.value"
          :disabled="adding !== null"
          :data-form-matrix-add="type.value"
          @click.stop.prevent="addBlock(type.value)"
        >
          {{
            control.props.entryTypes?.length === 1
              ? control.props.addLabel
              : t('Add {type}', {type: type.label})
          }}
        </craft-button>
      </div>
    </div>
  </craft-matrix-input>
</template>

<style scoped lang="scss">
  /**
   * A disabled block reads as a problem to fix rather than a neutral off state,
   * so its dot is red. Set through the status component's own custom properties
   * — they inherit into its shadow DOM — rather than repainting the shared
   * `--c-status-disabled-*` tokens, which every other disabled thing uses.
   */
  .matrixblock.disabled-entry craft-status {
    --c-status-disabled-fill: var(--c-status-expired-fill);
    --c-status-disabled-border: var(--c-status-expired-border);
  }

  .preview {
    position: relative;
    padding-inline-start: var(--c-spacing-sm);
    margin-inline-start: var(--c-spacing-sm);

    &:before {
      content: '';
      height: 60%;
      inset-block-start: 15%;
      border-inline-start: 1px solid color-mix(transparent, currentColor);
      position: absolute;
      inset-inline-start: 0;
    }
  }
</style>
