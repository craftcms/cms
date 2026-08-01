<script setup lang="ts">
  import {computed, toRaw, useId} from 'vue';
  import {t} from '@craftcms/ui/utilities/translate';
  import {toHandle} from '@craftcms/ui/utilities/string';
  import type CraftCheckbox from '@craftcms/ui/components/checkbox/checkbox';
  import IconPicker from '@/common/form/IconPicker.vue';
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/checkbox/checkbox';
  import '@craftcms/ui/components/input/input';
  import '@craftcms/ui/components/input-color/input-color';
  import '@craftcms/ui/components/reorder-button/reorder-button';
  import type {FormElementBinding, JsonValue} from '../types';

  type OptionRow = Record<string, unknown> & {
    optgroup?: unknown;
    label?: unknown;
    value?: unknown;
    icon?: unknown;
    color?: unknown;
    default?: unknown;
  };

  const props = defineProps<{
    config: Record<string, JsonValue>;
    attributes: Record<string, JsonValue>;
    binding?: FormElementBinding;
  }>();

  const emit = defineEmits<{
    'update:value': [value: OptionRow[]];
  }>();

  const optionRows = computed(() =>
    Array.isArray(props.binding?.value)
      ? (props.binding.value as OptionRow[])
      : []
  );
  const renderedRows = computed(() =>
    optionRows.value.length > 0 ? optionRows.value : [blankOption()]
  );
  const readOnly = computed(() => props.binding?.readOnly ?? false);
  const multipleDefaults = computed(
    () => props.config.multipleDefaults === true
  );
  const supportsOptgroups = computed(() => props.config.optgroups === true);
  const supportsIcons = computed(() => props.config.icons === true);
  const supportsColors = computed(() => props.config.colors === true);
  const rowKeys = new WeakMap<OptionRow, number>();
  const iconHeadingId = useId();
  let nextRowKey = 0;

  function blankOption(): OptionRow {
    return {label: '', value: '', default: false};
  }

  function isOptgroup(row: OptionRow): boolean {
    return Object.hasOwn(row, 'optgroup');
  }

  function textValue(value: unknown): string {
    if (typeof value === 'string' || typeof value === 'number') {
      return String(value);
    }

    if (value && typeof value === 'object' && 'value' in value) {
      return textValue(value.value);
    }

    return '';
  }

  function rowLabel(row: OptionRow): string {
    return textValue(isOptgroup(row) ? row.optgroup : row.label);
  }

  function rowKey(row: OptionRow): number {
    const rawRow = toRaw(row);

    if (!rowKeys.has(rawRow)) {
      rowKeys.set(rawRow, nextRowKey++);
    }

    return rowKeys.get(rawRow)!;
  }

  function changedRow(row: OptionRow, changes: Partial<OptionRow>): OptionRow {
    const nextRow = {...row, ...changes};
    rowKeys.set(toRaw(nextRow), rowKey(row));

    return nextRow;
  }

  function generatedValue(label: string): string {
    return toHandle(label, {
      allowNonAlphaStart: true,
      handleCasing: window.Cp.$config?.get('handleCasing', 'camel') ?? 'camel',
    });
  }

  function inputName(index: number, property: string): string | undefined {
    const name = props.attributes.name;

    return typeof name === 'string'
      ? `${name}[${index}][${property}]`
      : undefined;
  }

  function inputValue(event: Event): string {
    return String(
      (event.currentTarget as HTMLElementTagNameMap['craft-input']).value ?? ''
    );
  }

  function checkedValue(event: Event): boolean {
    return (event.currentTarget as CraftCheckbox).checked;
  }

  function updateRow(index: number, changes: Partial<OptionRow>): void {
    emit(
      'update:value',
      renderedRows.value.map((row, rowIndex) =>
        rowIndex === index ? changedRow(row, changes) : row
      )
    );
  }

  function updateLabel(index: number, label: string): void {
    const row = renderedRows.value[index]!;

    if (isOptgroup(row)) {
      updateRow(index, {optgroup: label});

      return;
    }

    const currentLabel = textValue(row.label);
    const currentValue = textValue(row.value);
    const generated =
      currentValue === '' || currentValue === generatedValue(currentLabel);

    updateRow(index, {
      label,
      ...(generated ? {value: generatedValue(label)} : {}),
    });
  }

  function toggleOptgroup(index: number, enabled: boolean): void {
    const row = renderedRows.value[index]!;
    const label = rowLabel(row);
    const nextRow = enabled
      ? {optgroup: label}
      : {label, value: generatedValue(label), default: false};
    rowKeys.set(toRaw(nextRow), rowKey(row));

    emit(
      'update:value',
      renderedRows.value.map((current, rowIndex) =>
        rowIndex === index ? nextRow : current
      )
    );
  }

  function updateDefault(index: number, checked: boolean): void {
    emit(
      'update:value',
      renderedRows.value.map((row, rowIndex) => {
        if (isOptgroup(row)) {
          return row;
        }

        if (multipleDefaults.value) {
          return rowIndex === index ? changedRow(row, {default: checked}) : row;
        }

        return changedRow(row, {
          default: rowIndex === index && checked,
        });
      })
    );
  }

  function addRow(): void {
    emit('update:value', [...optionRows.value, blankOption()]);
  }

  function deleteRow(index: number): void {
    emit(
      'update:value',
      renderedRows.value.filter((_, rowIndex) => rowIndex !== index)
    );
  }

  function reorderRow(index: number, event: Event): void {
    const direction = (event as CustomEvent<{direction: 'up' | 'down'}>).detail
      .direction;
    const destination = direction === 'up' ? index - 1 : index + 1;

    if (destination < 0 || destination >= renderedRows.value.length) {
      return;
    }

    const rows = [...renderedRows.value];
    [rows[index], rows[destination]] = [rows[destination]!, rows[index]!];
    emit('update:value', rows);
  }

  function position(index: number): 'first' | 'middle' | 'last' {
    if (index === 0) {
      return 'first';
    }

    return index === renderedRows.value.length - 1 ? 'last' : 'middle';
  }
</script>

<template>
  <div
    :id="typeof attributes.id === 'string' ? attributes.id : undefined"
    role="group"
    :aria-labelledby="
      typeof attributes['aria-labelledby'] === 'string'
        ? attributes['aria-labelledby']
        : undefined
    "
    :aria-describedby="
      typeof attributes['aria-describedby'] === 'string'
        ? attributes['aria-describedby']
        : undefined
    "
  >
    <table>
      <thead>
        <tr>
          <th v-if="supportsOptgroups" scope="col">{{ t('Optgroup?') }}</th>
          <th scope="col">{{ t('Option Label') }}</th>
          <th scope="col">{{ t('Value') }}</th>
          <th v-if="supportsIcons" :id="iconHeadingId" scope="col">
            {{ t('Icon') }}
          </th>
          <th v-if="supportsColors" scope="col">{{ t('Color') }}</th>
          <th scope="col">{{ t('Default?') }}</th>
          <th scope="col">
            <span class="visually-hidden">{{ t('Actions') }}</span>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="(row, index) in renderedRows"
          :key="rowKey(row)"
          :data-option-row="index"
        >
          <td v-if="supportsOptgroups">
            <craft-checkbox
              label-sr-only
              :label="t('Optgroup?')"
              :name="inputName(index, 'isOptgroup')"
              .checked="isOptgroup(row)"
              .disabled="readOnly"
              data-option-optgroup
              @change="toggleOptgroup(index, checkedValue($event))"
            ></craft-checkbox>
          </td>
          <td>
            <craft-input
              type="text"
              :name="inputName(index, 'label')"
              :value="rowLabel(row)"
              :readonly="readOnly"
              :label="t('Option Label')"
              label-sr-only
              data-option-label
              @input="updateLabel(index, inputValue($event))"
            ></craft-input>
          </td>
          <td>
            <craft-input
              type="text"
              :name="inputName(index, 'value')"
              :value="textValue(row.value)"
              :readonly="readOnly"
              :disabled="isOptgroup(row)"
              :label="t('Value')"
              label-sr-only
              data-option-value
              @input="updateRow(index, {value: inputValue($event)})"
            ></craft-input>
          </td>
          <td v-if="supportsIcons" data-option-icon>
            <IconPicker
              :name="inputName(index, 'icon')"
              :disabled="readOnly || isOptgroup(row)"
              :model-value="textValue(row.icon)"
              :labelled-by="iconHeadingId"
              @update:model-value="updateRow(index, {icon: $event})"
            />
          </td>
          <td v-if="supportsColors">
            <craft-input-color
              :name="inputName(index, 'color')"
              :value="textValue(row.color)"
              :readonly="readOnly"
              :disabled="readOnly || isOptgroup(row)"
              :label="t('Color')"
              label-sr-only
              data-option-color
              @input="updateRow(index, {color: inputValue($event)})"
            ></craft-input-color>
          </td>
          <td>
            <craft-checkbox
              label-sr-only
              :label="t('Default?')"
              :name="inputName(index, 'default')"
              .checked="Boolean(row.default)"
              .disabled="readOnly || isOptgroup(row)"
              data-option-default
              @change="updateDefault(index, checkedValue($event))"
            ></craft-checkbox>
          </td>
          <td>
            <div class="actions">
              <craft-reorder-button
                :position="position(index)"
                :disabled="readOnly"
                @reorder="reorderRow(index, $event)"
              ></craft-reorder-button>
              <craft-button
                type="button"
                size="small"
                variant="plain"
                :disabled="readOnly"
                data-delete-option
                @click="deleteRow(index)"
                >{{ t('Delete') }}</craft-button
              >
            </div>
          </td>
        </tr>
      </tbody>
    </table>
    <craft-button
      type="button"
      size="small"
      :disabled="readOnly"
      data-add-option
      @click="addRow"
      >{{ t('Add an option') }}</craft-button
    >
  </div>
</template>

<style scoped>
  table {
    width: 100%;
    border-collapse: collapse;
    margin-block-end: var(--c-spacing-sm);
  }

  th,
  td {
    padding: var(--c-spacing-xs);
    text-align: start;
    vertical-align: middle;
  }

  craft-input,
  craft-input-color {
    width: 100%;
  }

  .actions {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-xs);
  }

  .visually-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip-path: inset(100%);
  }
</style>
