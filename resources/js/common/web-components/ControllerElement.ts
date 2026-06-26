/**
 * Base for the light-DOM custom elements that boot a JS **controller** — a
 * garnish/jQuery class that manages server-rendered markup — on connect, and tear
 * it down on disconnect. Used by `<craft-field-layout-designer>`,
 * `<craft-generated-fields-table>`, and `<craft-sortable-checkbox-select>`.
 *
 * These render their controller's DOM as **light-DOM children**: the controllers
 * query the document and/or use jQuery, so a shadow root would hide their markup.
 * Subclasses declare the child to wait for ({@link rootSelector}) and how to build
 * the controller from it ({@link create}); this base owns the (retrying) boot and
 * the teardown. Self-boot/teardown via the connected/disconnected callbacks is
 * what lets a host swap this markup (e.g. an Inertia page after a save) and have
 * the controller re-bind for free.
 *
 * @typeParam T - the controller instance type; must expose `destroy()`.
 */
export abstract class ControllerElement<
  T extends {destroy(): void},
> extends HTMLElement {
  #instance: T | null = null;

  /** CSS selector for the server-rendered child the controller wraps. */
  protected abstract readonly rootSelector: string;

  /** Build the controller instance from the resolved root element. */
  protected abstract create(root: HTMLElement): T;

  /**
   * Optional post-construction step, e.g. `Craft.initUiElements(this)`. Default
   * no-op. The new instance is reachable via {@link instance}.
   */
  protected booted(): void {}

  /** The live controller instance, or `null` before boot / after teardown. */
  protected get instance(): T | null {
    return this.#instance;
  }

  connectedCallback(): void {
    this.#boot();
  }

  /**
   * Construct the controller once {@link rootSelector} resolves. The markup may
   * not be parsed yet when the element upgrades (initial HTML parse vs. an
   * AJAX/Inertia-injected fragment), so retry on the next frame until it is —
   * bailing if the element is disconnected meanwhile.
   */
  #boot(): void {
    if (this.#instance || !this.isConnected) {
      return;
    }

    const root = this.querySelector<HTMLElement>(this.rootSelector);
    if (!root) {
      requestAnimationFrame(() => this.#boot());
      return;
    }

    this.#instance = this.create(root);
    this.booted();
  }

  disconnectedCallback(): void {
    if (this.#instance) {
      this.#instance.destroy();
      this.#instance = null;
    }
  }

  /** Parse a JSON object attribute, returning `{}` when absent or invalid. */
  protected jsonAttr(name: string): Record<string, any> {
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
}
