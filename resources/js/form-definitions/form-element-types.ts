import type {CpComponentRegistry} from '@/bootstrap/components';
import CheckboxSelectInputRenderer from './renderers/CheckboxSelectInputRenderer.vue';
import ColorPaletteInputRenderer from './renderers/ColorPaletteInputRenderer.vue';
import ComboboxInputRenderer from './renderers/ComboboxInputRenderer.vue';
import DateInputRenderer from './renderers/DateInputRenderer.vue';
import EditableTableInputRenderer from './renderers/EditableTableInputRenderer.vue';
import ElementConditionInputRenderer from './renderers/ElementConditionInputRenderer.vue';
import FieldLayoutInputRenderer from './renderers/FieldLayoutInputRenderer.vue';
import KeyedTableInputRenderer from './renderers/KeyedTableInputRenderer.vue';
import LightswitchInputRenderer from './renderers/LightswitchInputRenderer.vue';
import MoneyInputRenderer from './renderers/MoneyInputRenderer.vue';
import NumberInputRenderer from './renderers/NumberInputRenderer.vue';
import ObjectSelectInputRenderer from './renderers/ObjectSelectInputRenderer.vue';
import OptionRowsRenderer from './renderers/OptionRowsRenderer.vue';
import SelectInputRenderer from './renderers/SelectInputRenderer.vue';
import TextInputRenderer from './renderers/TextInputRenderer.vue';
import TimeInputRenderer from './renderers/TimeInputRenderer.vue';

const sharedContainerTypes = new Set<string>([
  'craft:field',
  'craft:group',
  'craft:tabs',
  'craft:tab',
]);

export const nativeFormElementRenderers = {
  'craft:checkbox-select-input': CheckboxSelectInputRenderer,
  'craft:color-palette-input': ColorPaletteInputRenderer,
  'craft:combobox-input': ComboboxInputRenderer,
  'craft:date-input': DateInputRenderer,
  'craft:editable-table-input': EditableTableInputRenderer,
  'craft:element-condition-input': ElementConditionInputRenderer,
  'craft:field-layout-input': FieldLayoutInputRenderer,
  'craft:keyed-table-input': KeyedTableInputRenderer,
  'craft:lightswitch-input': LightswitchInputRenderer,
  'craft:money-input': MoneyInputRenderer,
  'craft:number-input': NumberInputRenderer,
  'craft:object-select-input': ObjectSelectInputRenderer,
  'craft:option-rows': OptionRowsRenderer,
  'craft:select-input': SelectInputRenderer,
  'craft:text-input': TextInputRenderer,
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
