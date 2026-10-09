import {ComponentSelect} from './component-select';
import CraftComponentSelect from '@/modules/component-select/component-select.ce';
import {defineElement} from '@/common/web-components';

// Guarded registration (mirrors the `customElements.get()` check the
// `@craftcms/cp` components do).
defineElement('craft-component-select', CraftComponentSelect);

// Unlike the other ported modules there is no `window.Craft.*` assignment:
// `<craft-component-select>` (the element, not this controller) is the public
// API. Core no longer instantiates `Craft.ComponentSelectInput`; the legacy
// class was relocated out of the legacy bundle to a yii2-adapter compat asset
// purely for the `componentSelect.twig` `jsClass` escape hatch — see the
// module README's "No legacy-global shim" section.
export {CraftComponentSelect, ComponentSelect};
export type {
  ComponentSelectSettings,
  DefineChipActionsEventDetail,
} from './component-select';
export {componentSelectData} from './support';
