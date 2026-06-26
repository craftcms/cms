import {FieldLayoutDesigner} from '@/modules/field-layout-designer/FieldLayoutDesigner';

/**
 * `<craft-field-layout-designer>` — boots a {@link FieldLayoutDesigner} around the
 * server-rendered `.layoutdesigner` markup it wraps, so PHP/Twig can emit the
 * element instead of a manual boot.
 *
 * The designer renders as **light-DOM children** — it treats `.layoutdesigner` as
 * its container and queries descendants, so shadow DOM would hide them. Boot: find
 * that child, parse the `settings` attribute on this element, construct, then run
 * `Craft.initUiElements` over this host.
 *
 * Self-boot/teardown via the connected/disconnected callbacks is the durable fix
 * for the after-save re-bind problem: when Inertia swaps the host's innerHTML, the
 * old element disconnects (→ `destroy()`) and the new one connects (→ `#boot()`).
 */
export default class CraftFieldLayoutDesigner extends HTMLElement {
  #instance: FieldLayoutDesigner | null = null;

  connectedCallback(): void {
    this.#boot();
  }

  /**
   * Construct the designer once its `.layoutdesigner` child is present.
   * Inertia/AJAX-injected markup may not have children yet when the element
   * upgrades, so retry on the next frame until they do (bailing if disconnected).
   */
  #boot(): void {
    if (this.#instance || !this.isConnected) {
      return;
    }

    const designerEl = this.querySelector<HTMLElement>('.layoutdesigner');
    if (!designerEl) {
      requestAnimationFrame(() => this.#boot());
      return;
    }

    const settings = this.#jsonAttr('settings');
    this.#instance = new FieldLayoutDesigner(designerEl, settings);

    // Upgrade any nested Craft UI elements, scoped to this host — mirrors the
    // composable's `Craft.initUiElements(host)`.
    (window as any).Craft?.initUiElements?.(this);
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

  #jsonAttr(name: string): Record<string, any> {
    const raw = this.getAttribute(name);
    if (!raw) {
      return {};
    }
    try {
      return JSON.parse(raw);
    } catch {
      return {};
    }
  }

  disconnectedCallback(): void {
    if (this.#instance) {
      this.#instance.destroy();
      this.#instance = null;
    }
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-field-layout-designer': CraftFieldLayoutDesigner;
  }
}
