<script setup lang="ts">
  // `craft-action-menu`'s `actions` is a JS property (`attribute: false`), so the
  // element has to be defined before Vue patches it — otherwise the assignment
  // shadows the accessor and the menu never renders. Same reason ActionMenuNode
  // imports it. Leaf module, not the barrel.
  import '@craftcms/ui/components/action-menu/action-menu';
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/reorder-button/reorder-button';
  import '@craftcms/ui/components/spinner/spinner';
  import {t} from '@craftcms/ui';
  import {computed, onBeforeUnmount, onMounted, ref, toRaw, useId} from 'vue';
  import '@/modules/matrix';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import FormNodeList from './FormNodeList.vue';
  import type {ActionItems} from '@/common/types';
  import {
    NESTED_ELEMENT_UID_PREFIX,
    type FormChange,
    type FormControlPayload,
    type FormPayload,
    type NestedElementValue,
    type NestedFormPayload,
  } from './types';
  import {inputName} from './runtime';

  type EntryType = {value: string; label: string};
  type MatrixProps = {
    entryTypes?: EntryType[];
    addLabel: string;
    minEntries?: number | null;
    maxEntries?: number | null;
    /** Per-block presentation, keyed by identity. Server-built; never posted. */
    blocks?: Record<string, {actions?: ActionItems}>;
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
  const forms = computed(() => {
    const map = new Map<string, NestedFormPayload>();

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
      block?.collapsed
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
        action: blockEvent(uid, 'add', {entryType: type.value}),
      })),
    ];
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

    const next = structuredClone(toRaw(model.value));
    const block = next.entries[detail.uid];

    switch (detail.action) {
      case 'collapse':
      case 'expand':
        if (!block) return;
        block.collapsed = detail.action === 'collapse';
        break;

      case 'disable':
      case 'enable':
        if (!block) return;
        block.enabled = detail.action === 'enable';
        break;

      case 'delete':
        if (next.sortOrder.length <= (props.control.props.minEntries ?? 0)) {
          return;
        }
        delete next.entries[detail.uid];
        next.sortOrder = next.sortOrder.filter((uid) => uid !== detail.uid);
        break;

      case 'add': {
        // Insert above this block, so the new one lands where the menu was opened.
        const at = next.sortOrder.indexOf(detail.uid);
        const uid = `${NESTED_ELEMENT_UID_PREFIX}${crypto.randomUUID()}`;
        next.entries[uid] = {type: detail.entryType ?? '', enabled: true};
        next.sortOrder.splice(at < 0 ? next.sortOrder.length : at, 0, uid);
        break;
      }

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
      <span role="status" class="visually-hidden" data-status-message />
      <div class="grid gap-1" role="list" data-matrix-blocks>
        <craft-card
          v-for="(uid, index) in model.sortOrder"
          :key="uid"
          class="matrixblock js-deletable"
          :class="{
            collapsed: model.entries[uid]?.collapsed,
            'disabled-entry': model.entries[uid]?.enabled === false,
          }"
          :data-id="uid"
          :data-type="String(model.entries[uid]?.type ?? '')"
          data-matrix-block
          role="listitem"
        >
          <template v-if="editable">
            <input
              type="hidden"
              :name="`${inputName(control.path)}[sortOrder][]`"
              :value="uid"
            />
            <input
              type="hidden"
              :name="`${inputName(control.path)}[entries][${uid}][type]`"
              :value="String(model.entries[uid]?.type ?? '')"
            />
          </template>
          <div slot="label">
            {{ entryType(uid)?.label ?? uid }}
            <div class="preview" />
          </div>
          <div v-if="editable" slot="actions" class="flex flex-nowrap">
            <ActionMenu
              :actions="blockActions(uid)"
              :label="t('{type} actions', {type: entryType(uid)?.label ?? uid})"
            />
            <craft-reorder-button
              class="move-btn"
              :disabled="model.sortOrder.length < 2"
              :position="
                index === 0
                  ? 'first'
                  : index === model.sortOrder.length - 1
                    ? 'last'
                    : 'middle'
              "
            />
            <craft-button
              type="button"
              icon="trash"
              size="small"
              variant="danger-plain"
              :disabled="
                model.sortOrder.length <= (control.props.minEntries ?? 0)
              "
              data-form-matrix-remove
              :accessible-name="
                t('Remove {type}', {type: entryType(uid)?.label ?? uid})
              "
            />
          </div>
          <div v-show="!model.entries[uid]?.collapsed" class="fields">
            <template v-if="forms.get(uid)">
              <FormNodeList
                :nodes="forms.get(uid)!.nodes"
                :values="values"
                :errors="errors"
                :touched-paths="touchedPaths"
                :scope="forms.get(uid)!.scope"
                :refreshable="forms.get(uid)!.refreshable"
                @change="nestedChange($event, forms.get(uid)!)"
              />
            </template>
            <craft-spinner v-else :label="t('Loading')" />
          </div>
        </craft-card>
      </div>
      <div v-if="canAdd" class="buttons">
        <button
          v-for="type in control.props.entryTypes"
          :key="type.value"
          type="button"
          class="btn add icon dashed wrap"
          :data-form-matrix-add="type.value"
        >
          {{
            control.props.entryTypes?.length === 1
              ? control.props.addLabel
              : t('Add {type}', {type: type.label})
          }}
        </button>
      </div>
    </div>
  </craft-matrix-input>
</template>
