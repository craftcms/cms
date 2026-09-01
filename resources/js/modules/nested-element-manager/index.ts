import {NestedElementManager} from './nested-element-manager';
import CraftNestedElementManager from './nested-element-manager.ce';
import {defineElement} from '@/common/web-components';

// Guarded registration (mirrors the `customElements.get()` check the
// `@craftcms/cp` components do).
defineElement('craft-nested-element-manager', CraftNestedElementManager);

// Legacy-global shim: the legacy `Craft.NestedElementManager` no longer ships
// in the legacy bundle, so any remaining `new Craft.NestedElementManager(
// container, elementType, settings)` boots (plugins) resolve to the modern
// class — its constructor still accepts the legacy three-argument signature.
declare global {
  interface Window {
    Craft: any;
  }
}
window.Craft = window.Craft || {};
window.Craft.NestedElementManager = NestedElementManager;

export {CraftNestedElementManager, NestedElementManager};
export type {
  NestedElementManagerSettings,
  CreateAttributes,
} from './nested-element-manager';
export {nestedElementManagerData} from './support';
