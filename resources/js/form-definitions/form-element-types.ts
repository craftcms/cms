import type {CpComponentRegistry} from '@/bootstrap/components';
import CraftColorPalette from '@craftcms/ui/vue/CraftColorPalette.vue';
import CraftFieldLayout from '@craftcms/ui/vue/CraftFieldLayout.vue';
import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
import CraftKeyedTable from '@craftcms/ui/vue/CraftKeyedTable.vue';
import CraftObjectSelect from '@craftcms/ui/vue/CraftObjectSelect.vue';
import CraftOptionRows from '@craftcms/ui/vue/CraftOptionRows.vue';
import CraftSwitch from '@craftcms/ui/vue/CraftSwitch.vue';
import '@craftcms/ui/components/color-palette/color-palette';
import '@craftcms/ui/components/field-layout/field-layout';
import '@craftcms/ui/components/input/input';
import '@craftcms/ui/components/keyed-table/keyed-table';
import '@craftcms/ui/components/object-select/object-select';
import '@craftcms/ui/components/option-rows/option-rows';
import '@craftcms/ui/components/switch/switch';
import '@/modules/icon-picker';
import CheckboxSelectInputRenderer from './renderers/CheckboxSelectInputRenderer.vue';
import ComboboxInputRenderer from './renderers/ComboboxInputRenderer.vue';
import DateInputRenderer from './renderers/DateInputRenderer.vue';
import EditableTableInputRenderer from './renderers/EditableTableInputRenderer.vue';
import ElementConditionInputRenderer from './renderers/ElementConditionInputRenderer.vue';
import MoneyInputRenderer from './renderers/MoneyInputRenderer.vue';
import NumberInputRenderer from './renderers/NumberInputRenderer.vue';
import SelectInputRenderer from './renderers/SelectInputRenderer.vue';
import TimeInputRenderer from './renderers/TimeInputRenderer.vue';

const sharedContainerTypes = new Set<string>([
  'craft:field',
  'craft:group',
  'craft:tabs',
  'craft:tab',
]);

export const nativeFormElementRenderers = {
  'craft:checkbox-select-input': CheckboxSelectInputRenderer,
  'craft:color-palette-input': CraftColorPalette,
  'craft:combobox-input': ComboboxInputRenderer,
  'craft:date-input': DateInputRenderer,
  'craft:editable-table-input': EditableTableInputRenderer,
  'craft:element-condition-input': ElementConditionInputRenderer,
  'craft:field-layout-input': CraftFieldLayout,
  'craft:keyed-table-input': CraftKeyedTable,
  'craft:lightswitch-input': CraftSwitch,
  'craft:money-input': MoneyInputRenderer,
  'craft:number-input': NumberInputRenderer,
  'craft:object-select-input': CraftObjectSelect,
  'craft:option-rows': CraftOptionRows,
  'craft:select-input': SelectInputRenderer,
  'craft:text-input': CraftInput,
  'craft:time-input': TimeInputRenderer,
};

export function registerNativeFormElementRenderers(
  registry: CpComponentRegistry
): void {
  for (const [type, renderer] of Object.entries(nativeFormElementRenderers)) {
    registry.register(`form-element:${type}`, renderer);
  }
}

export function isSharedContainer(type: string): boolean {
  return sharedContainerTypes.has(type);
}
