import type {CpComponentRegistry} from '@/bootstrap/components';
import CraftColorPalette from '@craftcms/ui/vue/CraftColorPalette.vue';
import CraftCheckboxSelect from '@craftcms/ui/vue/CraftCheckboxSelect.vue';
import CraftCombobox from '@craftcms/ui/vue/CraftCombobox.vue';
import CraftEditableTable from '@craftcms/ui/vue/CraftEditableTable.vue';
import CraftElementCondition from '@craftcms/ui/vue/CraftElementCondition.vue';
import CraftFieldLayout from '@craftcms/ui/vue/CraftFieldLayout.vue';
import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
import CraftInputMoney from '@craftcms/ui/vue/CraftInputMoney.vue';
import CraftKeyedTable from '@craftcms/ui/vue/CraftKeyedTable.vue';
import CraftObjectSelect from '@craftcms/ui/vue/CraftObjectSelect.vue';
import CraftOptionRows from '@craftcms/ui/vue/CraftOptionRows.vue';
import CraftSelect from '@craftcms/ui/vue/CraftSelect.vue';
import CraftSwitch from '@craftcms/ui/vue/CraftSwitch.vue';
import '@craftcms/ui/components/checkbox-select/checkbox-select';
import '@craftcms/ui/components/color-palette/color-palette';
import '@craftcms/ui/components/editable-table/editable-table';
import '@craftcms/ui/components/element-condition/element-condition';
import '@craftcms/ui/components/field-layout/field-layout';
import '@craftcms/ui/components/input/input';
import '@craftcms/ui/components/keyed-table/keyed-table';
import '@craftcms/ui/components/object-select/object-select';
import '@craftcms/ui/components/option-rows/option-rows';
import '@craftcms/ui/components/select/select';
import '@craftcms/ui/components/switch/switch';
import '@/modules/icon-picker';

const sharedContainerTypes = new Set<string>([
  'craft:field',
  'craft:group',
  'craft:tabs',
  'craft:tab',
]);

export const nativeFormElementRenderers = {
  'craft:checkbox-select-input': CraftCheckboxSelect,
  'craft:color-palette-input': CraftColorPalette,
  'craft:combobox-input': CraftCombobox,
  'craft:date-input': CraftInput,
  'craft:editable-table-input': CraftEditableTable,
  'craft:element-condition-input': CraftElementCondition,
  'craft:field-layout-input': CraftFieldLayout,
  'craft:keyed-table-input': CraftKeyedTable,
  'craft:lightswitch-input': CraftSwitch,
  'craft:money-input': CraftInputMoney,
  'craft:number-input': CraftInput,
  'craft:object-select-input': CraftObjectSelect,
  'craft:option-rows': CraftOptionRows,
  'craft:select-input': CraftSelect,
  'craft:text-input': CraftInput,
  'craft:time-input': CraftInput,
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
