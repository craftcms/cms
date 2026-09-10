import type {ReactiveController, ReactiveControllerHost} from 'lit';

/**
 * A reactive controller that tracks whether a host's slots have content, so
 * the host can skip rendering a slot's wrapper when nothing is slotted into
 * it. Modeled on Web Awesome's `HasSlotController`.
 *
 * Presence is read from the host's direct light-DOM children, and a light-DOM
 * `MutationObserver` re-renders the host when a tracked slot gains or loses
 * content. `slotchange` alone isn't enough: a slot that isn't rendered never
 * fires one, so it could never come back.
 *
 * @example
 * ```ts
 * private readonly hasSlot = new HasSlotController(this, 'footer');
 *
 * render() {
 *   return this.hasSlot.test('footer')
 *     ? html`<footer><slot name="footer"></slot></footer>`
 *     : nothing;
 * }
 * ```
 */
export class HasSlotController implements ReactiveController {
  /** Pass as a slot name to track the default (unnamed) slot. */
  static readonly DEFAULT_SLOT = '[default]';

  private readonly slotNames: string[];

  private observer?: MutationObserver;

  private presence: boolean[] = [];

  constructor(
    private readonly host: ReactiveControllerHost & HTMLElement,
    ...slotNames: string[]
  ) {
    this.slotNames = slotNames;
    host.addController(this);
  }

  /**
   * Whether anything is assigned to the given slot. Pass
   * `HasSlotController.DEFAULT_SLOT` to test the default slot.
   */
  test(slotName: string): boolean {
    return slotName === HasSlotController.DEFAULT_SLOT
      ? this.hasDefaultSlot()
      : this.hasNamedSlot(slotName);
  }

  hostConnected(): void {
    this.presence = this.snapshot();
    // Created here rather than in the constructor: hosts are also
    // constructed during SSR, where `MutationObserver` doesn't exist.
    this.observer ??= new MutationObserver(this.handleMutations);
    this.observer.observe(this.host, {
      childList: true,
      // Children re-slotted in place (a changed `slot` attribute).
      subtree: true,
      attributes: true,
      attributeFilter: ['slot'],
      // Default-slot presence depends on direct text nodes' content.
      characterData: this.slotNames.includes(HasSlotController.DEFAULT_SLOT),
    });
  }

  hostDisconnected(): void {
    this.observer?.disconnect();
  }

  private hasDefaultSlot(): boolean {
    return Array.from(this.host.childNodes).some((node) => {
      if (node.nodeType === Node.TEXT_NODE) {
        return node.textContent!.trim() !== '';
      }

      return node.nodeType === Node.ELEMENT_NODE && !(node as Element).slot;
    });
  }

  private hasNamedSlot(slotName: string): boolean {
    return Array.from(this.host.children).some(
      (child) => child.slot === slotName
    );
  }

  private snapshot(): boolean[] {
    return this.slotNames.map((slotName) => this.test(slotName));
  }

  /**
   * Re-renders only when a tracked slot's presence actually flips, so
   * unrelated light-DOM churn (including deep subtree mutations) is cheap.
   */
  private handleMutations = (): void => {
    const presence = this.snapshot();

    if (presence.some((hasSlot, index) => hasSlot !== this.presence[index])) {
      this.presence = presence;
      this.host.requestUpdate();
    }
  };
}
