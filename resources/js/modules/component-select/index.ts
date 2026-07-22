import {ComponentSelect} from './component-select';
import CraftComponentSelect from '@/modules/component-select/component-select.ce';
import {defineElement} from '@/common/web-components';

// Guarded registration (mirrors the `customElements.get()` check the
// `@craftcms/cp` components do).
defineElement('craft-component-select', CraftComponentSelect);

// Unlike the other ported modules there is no `window.Craft.*` assignment:
// the legacy `Craft.ComponentSelectInput` still ships in the legacy bundle
// for the not-yet-migrated Twig surfaces, and `<craft-component-select>` (the
// element, not this controller) is the public API for the ported ones — see
// the module README's "No legacy-global shim" section.
export {CraftComponentSelect, ComponentSelect};
export type {
  ComponentSelectSettings,
  DefineChipActionsEventDetail,
} from './component-select';
export {componentSelectData} from './support';
