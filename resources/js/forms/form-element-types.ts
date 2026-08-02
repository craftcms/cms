import type {CpComponentRegistry} from '@/bootstrap/components';
import CraftCombobox from '@craftcms/ui/vue/CraftCombobox.vue';
import CraftEditableTable from '@craftcms/ui/vue/CraftEditableTable.vue';
import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
import CraftInputMoney from '@craftcms/ui/vue/CraftInputMoney.vue';
import CraftSelect from '@craftcms/ui/vue/CraftSelect.vue';
import CraftSwitch from '@craftcms/ui/vue/CraftSwitch.vue';
import '@craftcms/ui/components/editable-table/editable-table';
import '@craftcms/ui/components/input/input';
import '@craftcms/ui/components/select/select';
import '@craftcms/ui/components/switch/switch';
import '@/modules/icon-picker';
import CheckboxSelectValueAdapter from './CheckboxSelectValueAdapter.vue';
import ElementConditionRenderer from './ElementConditionRenderer.vue';
import EntryTypeSelectRenderer from './EntryTypeSelectRenderer.vue';
import FieldLayoutDesignerValueAdapter from './FieldLayoutDesignerValueAdapter.vue';

const sharedContainerTypes = new Set<string>([
  'craft:field',
  'craft:group',
  'craft:tabs',
  'craft:tab',
]);

export const nativeFormElementRenderers = {
  'craft:checkbox-select-input': CheckboxSelectValueAdapter,
  'craft:combobox-input': CraftCombobox,
  'craft:date-input': CraftInput,
  'craft:editable-table-input': CraftEditableTable,
  'craft:element-condition-input': ElementConditionRenderer,
  'craft:entry-type-select-input': EntryTypeSelectRenderer,
  'craft:field-layout-designer': FieldLayoutDesignerValueAdapter,
  'craft:lightswitch-input': CraftSwitch,
  'craft:money-input': CraftInputMoney,
  'craft:number-input': CraftInput,
  'craft:select-input': CraftSelect,
  'craft:text-input': CraftInput,
  'craft:time-input': CraftInput,
};

export function registerNativeFormElementRenderers(
  registry: CpComponentRegistry
): void {
  for (const [type, renderer] of Object.entries(nativeFormElementRenderers)) {
    registry.register(type, renderer);
  }
}

export function isSharedContainer(type: string): boolean {
  return sharedContainerTypes.has(type);
}
