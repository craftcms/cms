<script setup lang="ts">
  import '@craftcms/ui/components/button/button';
  import {t} from '@craftcms/ui/utilities/translate';
  import {computed, onBeforeUnmount, ref, shallowRef, watch} from 'vue';
  import UiNodeList from './UiNodeList.vue';
  import TableControl from './TableControl.vue';
  import {canonical, inputName, pathsMatch, valueAt} from './runtime';
  import {useOptionalUiRenderer} from './renderer/context';
  import {rowFields} from './table/rowUis';
  import {openColumnSettings} from './table/columnSettingsSlideout';
  import type {TableControlProps, TableValue} from './table/types';
  import type {
    UiChangeKind,
    UiControlPayload,
    UiPayload,
    NestedUiPayload,
    UiValue,
    UiValues,
  } from './types';

  type Column = UiValues & {heading?: string; type?: string};
  type Columns = Record<string, Column>;
  type ColumnProps = {
    cellTypes: Array<{label: string; value: string}>;
    rowTemplate?: NestedUiPayload;
    errors?: Record<string, Record<string, true>>;
  };
  const props = defineProps<{
    control: UiControlPayload<ColumnProps>;
    value: Columns;
    editable: boolean;
    values: UiPayload['values'];
    errors: UiPayload['errors'];
    touchedPaths: Set<string>;
    uiScope?: string[];
    uiRefreshable?: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: Columns, kind: UiChangeKind): void;
  }>();
  const metadataProperties = ['heading', 'handle', 'width', 'type'];
  const columns = computed(() => props.value ?? {});
  const activeColumn = ref<string>();
  const settingsTarget = shallowRef<HTMLElement | null>(null);
  const renderer = useOptionalUiRenderer();
  const errorsCleared = computed(
    () => renderer?.childErrorsCleared(props.control.path) ?? false
  );
  let slideoutId: string | undefined;
  /** The column as it was when its settings opened, restored unless they're applied. */
  let snapshot: Column | undefined;
  const tableControl = computed<UiControlPayload<TableControlProps>>(() => ({
    ...props.control,
    props: {
      rowTemplate: props.control.props.rowTemplate,
      columns: {
        heading: {
          heading: t('Heading'),
          width: '45%',
          type: 'singleline',
          autopopulate: 'handle',
          enterNavigation: false,
          tsvPaste: false,
        },
        handle: {
          heading: t('Handle'),
          width: '35%',
          type: 'singleline',
          enterNavigation: false,
          tsvPaste: false,
        },
        width: {
          heading: t('Width'),
          width: '5rem',
          type: 'singleline',
          enterNavigation: false,
          tsvPaste: false,
        },
        type: {
          heading: t('Type'),
          type: 'select',
          width: '12rem',
          class: 'select-cell',
        },
      },
      keyed: true,
      rowIdPrefix: 'col',
      allowAdd: true,
      allowDelete: true,
      allowReorder: true,
      addRowLabel: t('Add a column'),
      defaultValues: {
        heading: '',
        handle: '',
        width: '',
        type: props.control.props.cellTypes.some(
          (type) => type.value === 'singleline'
        )
          ? 'singleline'
          : (props.control.props.cellTypes[0]?.value ?? 'singleline'),
      },
      errors: errorsCleared.value ? {} : props.control.props.errors,
    },
  }));
  const settingsUi = computed(() =>
    props.control.uis?.find((ui) =>
      pathsMatch(ui.scope, [...props.control.path, activeColumn.value ?? ''])
    )
  );
  const settingsNodes = computed(
    () =>
      settingsUi.value?.nodes.filter(
        (node) =>
          !node.control ||
          !metadataProperties.includes(node.control.path.at(-1) ?? '')
      ) ?? []
  );
  const configurableColumns = computed(
    () =>
      new Set(
        props.control.uis
          ?.filter((ui) =>
            rowFields(ui.nodes).some(
              (node) =>
                node.control &&
                !metadataProperties.includes(node.control.path.at(-1) ?? '')
            )
          )
          .map((ui) => ui.scope.at(-1))
      )
  );
  // The settings render in a slideout, outside the form, so the form gets every
  // setting from here.
  const serializedSettings = computed(() =>
    Object.entries(columns.value).flatMap(([key, column]) =>
      Object.entries(column)
        .filter(([property]) => !metadataProperties.includes(property))
        .flatMap(([property, value]) =>
          hiddenInputs(inputName([...props.control.path, key, property]), value)
        )
    )
  );
  const heading = computed(() =>
    activeColumn.value ? columns.value[activeColumn.value]?.heading : ''
  );

  const columnErrors = computed(() =>
    errorsCleared.value ? {} : (props.control.props.errors ?? {})
  );
  /** Columns whose settings, rather than their table cells, are invalid. */
  const invalidSettingsColumns = computed(
    () =>
      new Set(
        Object.entries(columnErrors.value)
          .filter(([, errors]) =>
            Object.keys(errors).some(
              (property) => !metadataProperties.includes(property)
            )
          )
          .map(([key]) => key)
      )
  );

  const cellErrors = computed(() => [
    ...props.errors,
    ...Object.entries(columnErrors.value).flatMap(([key, errors]) =>
      Object.keys(errors)
        .filter(
          (property) =>
            ['handle', 'type'].includes(property) &&
            !props.errors.some((error) =>
              pathsMatch(error.path, [...props.control.path, key, property])
            )
        )
        .map((property) => ({
          path: [...props.control.path, key, property],
          messages: [
            property === 'handle'
              ? t('Enter a valid, unique handle.')
              : t('Choose an available column type.'),
          ],
        }))
    ),
  ]);

  watch(columns, () => {
    if (activeColumn.value && !columns.value[activeColumn.value]) {
      snapshot = undefined;
      closeSettings();
    }
  });

  let unmounted = false;

  onBeforeUnmount(() => {
    unmounted = true;
    snapshot = undefined;
    closeSettings();
  });

  function update(value: TableValue, kind: UiChangeKind): void {
    if (!value || Array.isArray(value)) return;

    emit('update:value', value as Columns, kind);
  }

  async function configure(key: string, event: Event): Promise<void> {
    const column = columns.value[key];
    // One column at a time, including while its slideout is still loading.
    if (!column || activeColumn.value) return;

    activeColumn.value = key;
    snapshot = JSON.parse(JSON.stringify(column)) as Column;

    const panel = await openColumnSettings({
      title: () =>
        t('Configure column: {heading}', {
          heading: heading.value || t('Column'),
        }),
      settings: () => canonical(columns.value[key] ?? {}),
      mount: (target) => (settingsTarget.value = target),
      apply: () => (snapshot = undefined),
      opener: event.currentTarget as HTMLElement,
      onClosed: closed,
    });

    if (!panel) {
      closed();

      return;
    }

    slideoutId = panel.id;

    // Unmounted while the slideout was loading.
    if (unmounted) closeSettings();
  }

  function closeSettings(): void {
    const id = slideoutId;
    if (!id) return;

    void import('@/common/slideouts').then(({closeSlideout}) =>
      closeSlideout(id, {force: true})
    );
  }

  function closed(): void {
    const key = activeColumn.value;
    const column = key ? columns.value[key] : undefined;

    if (snapshot && column && canonical(column) !== canonical(snapshot)) {
      emit('update:value', {...columns.value, [key!]: snapshot}, 'discrete');
    }

    snapshot = undefined;
    slideoutId = undefined;
    settingsTarget.value = null;
    activeColumn.value = undefined;
  }

  function changed(): void {
    const value = valueAt(props.values, props.control.path);
    if (value && typeof value === 'object' && !Array.isArray(value)) {
      emit('update:value', value as Columns, 'discrete');
    }
  }

  function hiddenInputs(
    name: string,
    value: UiValue
  ): Array<{name: string; value: string}> {
    // The same empty sentinel a rendered multi-select control posts.
    if (Array.isArray(value) && !value.length) {
      return [{name, value: ''}];
    }
    if (Array.isArray(value) || (value !== null && typeof value === 'object')) {
      return Object.entries(value).flatMap(([key, child]) =>
        hiddenInputs(`${name}[${key}]`, child)
      );
    }
    return [
      {
        name,
        value:
          value === true ? '1' : value === false ? '0' : String(value ?? ''),
      },
    ];
  }
</script>

<template>
  <div class="min-w-0">
    <template v-if="editable"
      ><input
        v-for="input in serializedSettings"
        :key="input.name"
        type="hidden"
        :name="input.name"
        :value="input.value"
    /></template>
    <TableControl
      :control="tableControl"
      :value="columns"
      :editable="editable"
      :values="values"
      :errors="cellErrors"
      :touched-paths="touchedPaths"
      :ui-scope="uiScope"
      :ui-refreshable="uiRefreshable"
      @update:value="update"
    >
      <template #row-actions="{row, rowKey, index}">
        <craft-button
          v-if="configurableColumns.has(rowKey)"
          type="button"
          :icon="
            invalidSettingsColumns.has(rowKey) ? 'triangle-exclamation' : 'gear'
          "
          size="small"
          variant="plain"
          :class="{
            'column-settings--invalid': invalidSettingsColumns.has(rowKey),
          }"
          :title="
            invalidSettingsColumns.has(rowKey)
              ? t('Configure (contains errors)')
              : t('Configure')
          "
          :aria-label="
            invalidSettingsColumns.has(rowKey)
              ? t('Configure column {column} (contains errors)', {
                  column: row.value.heading || index + 1,
                })
              : t('Configure column {column}', {
                  column: row.value.heading || index + 1,
                })
          "
          @click="configure(rowKey, $event)"
        />
      </template>
    </TableControl>
    <Teleport v-if="settingsTarget" :to="settingsTarget">
      <UiNodeList
        v-if="settingsNodes.length && settingsUi"
        :nodes="settingsNodes"
        :values="values"
        :errors="errors"
        :touched-paths="touchedPaths"
        :scope="settingsUi.scope"
        :refreshable="Boolean(uiRefreshable)"
        @change="changed"
      />
      <p v-else>{{ t('This column type has no settings.') }}</p>
    </Teleport>
  </div>
</template>

<style scoped>
  .column-settings--invalid {
    color: var(--c-color-danger-on-quiet);
  }

  :deep([data-column='width']) {
    min-width: 5rem;
  }

  :deep([data-column='type']) {
    min-width: 12rem;
  }

  :deep([data-column='type'] craft-select) {
    --c-input-height: var(--c-size-control-sm);
  }
</style>
