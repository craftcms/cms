<script setup lang="ts">
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/reorder-button/reorder-button';
  import '@craftcms/ui/components/spinner/spinner';
  import {t} from '@craftcms/ui';
  import {computed, ref, toRaw, useId} from 'vue';
  import '@/modules/matrix';
  import FormNodeList from './FormNodeList.vue';
  import type {
    FormChange,
    FormControlPayload,
    FormPayload,
    FormValues,
    NestedFormPayload,
  } from './types';
  import {inputName} from './runtime';

  type EntryType = {value: string; label: string};
  type MatrixProps = {
    entryTypes?: EntryType[];
    addLabel: string;
    minEntries?: number | null;
    maxEntries?: number | null;
  };
  interface MatrixEntryValues extends FormValues {
    type?: string;
  }
  interface MatrixEntries {
    [key: string]: MatrixEntryValues;
  }
  type MatrixValue = {
    entries: MatrixEntries;
    sortOrder: string[];
  };

  const props = defineProps<{
    control: FormControlPayload<MatrixProps>;
    value: MatrixValue;
    values: FormPayload['values'];
    errors: FormPayload['errors'];
    touchedPaths: Set<string>;
    editable: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: MatrixValue, kind: 'discrete'): void;
    (event: 'change', change: FormChange): void;
  }>();
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
      map.set(`uid:${uid}`, form);
    }

    return map;
  });
  const canAdd = computed(
    () =>
      props.editable &&
      (!props.control.props.maxEntries ||
        props.value.sortOrder.length < props.control.props.maxEntries)
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
      props.value.sortOrder,
      props.control.forms?.map((form) => form.scope),
      props.editable,
    ])
  );

  function sync(event?: Event): void {
    const value = structuredClone(toRaw(props.value));
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

        return [uid, value.entries[uid] ?? {type: entry.dataset.type ?? ''}];
      })
    );
    emit('update:value', value, 'discrete');
  }

  function entryType(uid: string): EntryType | undefined {
    const handle = props.value.entries[uid]?.type;

    return props.control.props.entryTypes?.find(
      (type) => type.value === handle
    );
  }

  function nestedChange(change: FormChange, form: NestedFormPayload): void {
    emit('change', {
      ...change,
      scope: form.scope,
      refreshable: form.refreshable,
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
      <div class="blocks" role="list">
        <div
          v-for="(uid, index) in value.sortOrder"
          :key="uid"
          class="matrixblock js-deletable"
          :data-id="uid"
          :data-type="String(value.entries[uid]?.type ?? '')"
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
              :value="String(value.entries[uid]?.type ?? '')"
            />
          </template>
          <div class="titlebar">
            <div class="blocktype flex flex-nowrap flex-gap-xs">
              {{ entryType(uid)?.label ?? uid }}
            </div>
            <div class="preview" />
          </div>
          <div v-if="editable" class="actions">
            <craft-reorder-button
              class="move-btn"
              :disabled="value.sortOrder.length < 2"
              :position="
                index === 0
                  ? 'first'
                  : index === value.sortOrder.length - 1
                    ? 'last'
                    : 'middle'
              "
            />
            <craft-button
              type="button"
              icon="trash"
              :disabled="
                value.sortOrder.length <= (control.props.minEntries ?? 0)
              "
              data-form-matrix-remove
              :accessible-name="
                t('Remove {type}', {type: entryType(uid)?.label ?? uid})
              "
            />
          </div>
          <div class="fields">
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
        </div>
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
