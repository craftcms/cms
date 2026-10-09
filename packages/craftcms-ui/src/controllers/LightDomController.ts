import type {ReactiveController, ReactiveControllerHost} from 'lit';

interface LightDomControllerOptions {
  /**
   * Extra host attributes to watch alongside `slot`. Pass the ones the host
   * reads during render, such as `aria-label`.
   */
  attributeFilter?: string[];
  /** Whether text changes in the light DOM should count as a mutation. */
  characterData?: boolean;
  /**
   * Run on connect and on every mutation batch, before the host re-renders.
   * Use it to refresh derived state; the host is asked to update either way.
   */
  onChange?: () => void;
}

/**
 * Re-renders its host when the light DOM changes.
 *
 * Components that render a slot only when something is in it can't use
 * `slotchange` to find out: a slot that was never rendered has nothing to fire
 * the event. Lit doesn't re-render on light-DOM changes on its own either, so
 * the host would show whatever was there on first paint — a chip whose action
 * menu is injected after mount would never show it.
 *
 * The observer watches the `slot` attribute as well as child lists, because
 * content moves between slots by having its `slot` attribute rewritten rather
 * than by being added or removed.
 *
 * Pair it with `hasSlotted()` to read presence during render.
 */
export class LightDomController implements ReactiveController {
  private readonly host: ReactiveControllerHost & Element;

  private readonly options: LightDomControllerOptions;

  private readonly observer: MutationObserver;

  constructor(
    host: ReactiveControllerHost & Element,
    options: LightDomControllerOptions = {}
  ) {
    this.host = host;
    this.options = options;
    this.observer = new MutationObserver(() => {
      this.options.onChange?.();
      this.host.requestUpdate();
    });
    host.addController(this);
  }

  hostConnected() {
    this.options.onChange?.();
    this.observer.observe(this.host, {
      childList: true,
      subtree: true,
      characterData: this.options.characterData ?? false,
      attributes: true,
      attributeFilter: ['slot', ...(this.options.attributeFilter ?? [])],
    });
  }

  hostDisconnected() {
    this.observer.disconnect();
  }
}

/**
 * Whether any of the named slots has content.
 *
 * Only direct children can fill an element's slots, so presence is checked
 * against them rather than the whole subtree — a nested component with its own
 * `footer` slot must not light up its ancestor's chrome. The default slot is
 * matched by the empty string, the same way `HTMLElement.slot` reports it.
 */
export function hasSlotted(host: Element, ...names: string[]): boolean {
  return Array.from(host.children).some((child) => names.includes(child.slot));
}
