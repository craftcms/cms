<script setup lang="ts">
  import '@craftcms/ui/components/dialog/dialog';
  import '@craftcms/ui/components/button/button';
  import {t} from '@craftcms/ui/utilities/translate';
  import {computed, inject, nextTick, ref, watch} from 'vue';
  import UiNodeList from './UiNodeList.vue';
  import TableControl from './TableControl.vue';
  import {UiErrors, inputName, pathsMatch, valueAt} from './runtime';
  import {rowFields} from './table/rowUis';
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
  const uiErrors = inject(UiErrors, undefined);
  const errorsCleared = computed(
    () => uiErrors?.childrenCleared(props.control.path) ?? false
  );
  let opener: HTMLElement | undefined;
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
  const renderedSettingPaths = computed(() =>
    rowFields(settingsNodes.value).flatMap((node) =>
      node.control ? [inputName(node.control.path)] : []
    )
  );
  const serializedSettings = computed(() =>
    Object.entries(columns.value)
      .flatMap(([key, column]) =>
        Object.entries(column)
          .filter(([property]) => !metadataProperties.includes(property))
          .flatMap(([property, value]) =>
            hiddenInputs(
              inputName([...props.control.path, key, property]),
              value
            )
          )
      )
      .filter(
        (input) =>
          !renderedSettingPaths.value.some(
            (name) => input.name === name || input.name.startsWith(`${name}[`)
          )
      )
  );
  const heading = computed(() =>
    activeColumn.value ? columns.value[activeColumn.value]?.heading : ''
  );

  const cellErrors = computed(() => [
    ...props.errors,
    ...Object.entries(
      errorsCleared.value ? {} : (props.control.props.errors ?? {})
    ).flatMap(([key, errors]) =>
      Object.keys(errors)
        .filter(
          (property) =>
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
    if (activeColumn.value && !columns.value[activeColumn.value]) close();
  });

  function update(value: TableValue, kind: UiChangeKind): void {
    if (!value || Array.isArray(value)) return;

    emit('update:value', value as Columns, kind);
  }

  function configure(key: string, event: Event): void {
    opener = event.currentTarget as HTMLElement;
    activeColumn.value = key;
  }

  function close(): void {
    activeColumn.value = undefined;
    void nextTick(() => opener?.focus());
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
          icon="gear"
          size="small"
          variant="plain"
          :title="t('Configure')"
          :aria-label="
            t('Configure column {column}', {
              column: row.value.heading || index + 1,
            })
          "
          @click="configure(rowKey, $event)"
        />
      </template>
    </TableControl>
    <craft-dialog
      .opened="Boolean(activeColumn)"
      :label="
        t('Configure column: {heading}', {heading: heading || t('Column')})
      "
      @craft-hide="close"
    >
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
      <craft-button slot="footer" type="button" @click="close">{{
        t('Done')
      }}</craft-button>
    </craft-dialog>
  </div>
</template>

<style scoped>
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
