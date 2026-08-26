import {FieldLayoutDesigner} from '@/modules/field-layout-designer/field-layout-designer';
import {ControllerElement} from '@/common/web-components';

/**
 * `<craft-field-layout-designer>` — boots a {@link FieldLayoutDesigner} around the
 * server-rendered `.layoutdesigner` markup it wraps, so PHP/Twig can emit the
 * element instead of a manual boot.
 *
 * The designer renders as **light-DOM children** — it treats `.layoutdesigner` as
 * its container and queries descendants, so shadow DOM would hide them. Settings
 * come from the `settings` attribute on this element; after construction it runs
 * `Craft.initUiElements` over this host to upgrade nested Craft UI elements.
 *
 * Self-boot/teardown via the connected/disconnected callbacks is the durable fix
 * for the after-save re-bind problem: when Inertia swaps the host's innerHTML, the
 * old element disconnects (→ `destroy()`) and the new one connects (→ boot).
 */
export default class CraftFieldLayoutDesigner extends ControllerElement<FieldLayoutDesigner> {
  protected readonly rootSelector = '.layoutdesigner';

  protected create(root: HTMLElement): FieldLayoutDesigner {
    return new FieldLayoutDesigner(root, this.jsonAttr('settings'));
  }

  protected override booted(): void {
    // Upgrade any nested Craft UI elements, scoped to this host.
    window.Craft?.initUiElements?.(this);
  }

  /**
   * The field layout config as the JSON **string** from the designer's hidden
   * `[name="fieldLayout"]` input, passed through verbatim — the same value a
   * Garnish form would post. Inertia forms collect only the form object, so the
   * page's submit transform reads this back in.
   *
   * It must stay a string (not parsed): the server reads it via
   * `JsonHelper::decode(Request::input('fieldLayout'))`, which throws on a
   * non-string. The FLD keeps the input encoded correctly (arrays as arrays), so
   * the raw value is sent as-is.
   */
  serialize(): string {
    const input = this.querySelector<HTMLInputElement>('[name="fieldLayout"]');
    return input?.value || '{}';
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-field-layout-designer': CraftFieldLayoutDesigner;
  }
}
