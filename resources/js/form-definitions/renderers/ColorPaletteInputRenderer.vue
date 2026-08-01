<script setup lang="ts">
  import {computed} from 'vue';
  import type {FormElementBinding, JsonValue} from '../types';
  import {t} from '@craftcms/ui/utilities/translate';
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/checkbox/checkbox';
  import '@craftcms/ui/components/input-color/input-color';
  import '@craftcms/ui/components/input/input';
  import '@craftcms/ui/components/reorder-button/reorder-button';
  import '@craftcms/ui/components/visually-hidden/visually-hidden';

  type PaletteRow = {
    color: string;
    label: string | null;
    default: boolean;
  };

  const props = defineProps<{
    config: Record<string, JsonValue>;
    attributes: Record<string, JsonValue>;
    binding?: FormElementBinding;
  }>();

  const emit = defineEmits<{
    'update:value': [value: PaletteRow[]];
  }>();

  const rows = computed<PaletteRow[]>(() =>
    Array.isArray(props.binding?.value)
      ? (props.binding.value as PaletteRow[])
      : []
  );

  function emitRows(update: (rows: PaletteRow[]) => void): void {
    const nextRows = rows.value.map((row) => ({...row}));

    update(nextRows);
    emit('update:value', nextRows);
  }

  function updateRow(
    index: number,
    property: 'color' | 'label',
    event: Event
  ): void {
    const value = (event.target as unknown as {value: string}).value;

    emitRows((rows) => {
      rows[index]![property] =
        property === 'color' && value ? `#${value}` : value;
    });
  }

  function setDefault(index: number): void {
    emitRows((rows) => {
      rows.forEach((row, rowIndex) => {
        row.default = rowIndex === index;
      });
    });
  }

  function reorder(index: number, event: CustomEvent): void {
    const offset = event.detail.direction === 'up' ? -1 : 1;
    const targetIndex = index + offset;

    if (targetIndex < 0 || targetIndex >= rows.value.length) {
      return;
    }

    emitRows((rows) => {
      const [row] = rows.splice(index, 1);
      rows.splice(targetIndex, 0, row!);
    });
  }

  function addRow(): void {
    emitRows((rows) => {
      rows.push({color: '', label: '', default: false});
    });
  }

  function deleteRow(index: number): void {
    emitRows((rows) => {
      rows.splice(index, 1);
    });
  }

  function position(index: number): 'first' | 'middle' | 'last' {
    if (index === 0) {
      return 'first';
    }

    return index === rows.value.length - 1 ? 'last' : 'middle';
  }

  function rowLabel(row: PaletteRow, index: number): string {
    return row.label || row.color || t('color {number}', {number: index + 1});
  }
</script>

<template>
  <div v-bind="attributes" role="group">
    <table>
      <thead>
        <tr>
          <th>{{ t('Color') }}</th>
          <th>{{ t('Label') }}</th>
          <th>{{ t('Default') }}</th>
          <th>
            <craft-visually-hidden>{{ t('Actions') }}</craft-visually-hidden>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(row, index) in rows" :key="index" :data-palette-row="index">
          <td>
            <craft-input-color
              :value="row.color.replace(/^#/, '')"
              :disabled="binding?.readOnly"
              :aria-label="
                t('Color for {label}', {label: rowLabel(row, index)})
              "
              :data-palette-color="index"
              @input="updateRow(index, 'color', $event)"
            ></craft-input-color>
          </td>
          <td>
            <craft-input
              :value="row.label ?? ''"
              :disabled="binding?.readOnly"
              :aria-label="
                t('Label for {label}', {label: rowLabel(row, index)})
              "
              :data-palette-label="index"
              @input="updateRow(index, 'label', $event)"
            ></craft-input>
          </td>
          <td>
            <craft-checkbox
              :label="t('Default for {label}', {label: rowLabel(row, index)})"
              label-sr-only
              :checked="row.default"
              :disabled="binding?.readOnly"
              :data-palette-default="index"
              @change="setDefault(index)"
            ></craft-checkbox>
          </td>
          <td>
            <craft-reorder-button
              :position="position(index)"
              :label="t('Reorder {label}', {label: rowLabel(row, index)})"
              :disabled="binding?.readOnly"
              @reorder="reorder(index, $event)"
            ></craft-reorder-button>
            <craft-button
              variant="plain"
              :aria-label="t('Delete {label}', {label: rowLabel(row, index)})"
              :disabled="binding?.readOnly"
              @click="deleteRow(index)"
            >
              {{ t('Delete') }}
            </craft-button>
          </td>
        </tr>
      </tbody>
    </table>
    <craft-button :disabled="binding?.readOnly" @click="addRow">
      {{ t('Add a color') }}
    </craft-button>
  </div>
</template>
