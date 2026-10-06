import {ControllerElement} from '@/common/web-components';
import {
  NestedElementManager,
  type NestedElementManagerSettings,
} from './nested-element-manager';

/**
 * `<craft-nested-element-manager>` — boots a {@link NestedElementManager}
 * around the server-rendered markup from PHP
 * `NestedElementManager::getCardsHtml()` / `getIndexHtml()` that it wraps, so
 * `NestedElementManager::createView()` can emit the element instead of a
 * `new Craft.NestedElementManager(...)` `HtmlStack` JS boot.
 *
 * Configuration arrives as attributes:
 *
 * - `element-type` — the nested element type's class name.
 * - `settings` — the JSON settings blob (the same shape the legacy boot
 *   passed; see {@link NestedElementManagerSettings}), following
 *   `<craft-field-layout-designer>`'s single-JSON-attribute precedent since
 *   the settings include nested objects (`indexSettings`,
 *   `createAttributes`).
 *
 * The element is a dumb boot/teardown wrapper: consumers reach the instance
 * through the `support.ts` WeakMap (keyed by the inner container, matching
 * the legacy `.data('nestedElementManager')` location), not through methods
 * on this element. Self-boot/teardown via `ControllerElement` is what lets an
 * Inertia page swap the fragment (e.g. after a save) and have the manager
 * re-bind for free.
 */
export default class CraftNestedElementManager extends ControllerElement<NestedElementManager> {
  // The cards container or embedded element index `div` rendered by PHP.
  protected readonly rootSelector = ':scope > div';

  protected create(root: HTMLElement): NestedElementManager {
    // SAFETY: PHP renders the settings attribute from NestedElementManagerSettings.
    const settings = this.jsonAttr(
      'settings'
    ) as Partial<NestedElementManagerSettings>;

    return new NestedElementManager(root, {
      ...settings,
      elementType: this.getAttribute('element-type') ?? settings.elementType,
    });
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-nested-element-manager': CraftNestedElementManager;
  }
}
