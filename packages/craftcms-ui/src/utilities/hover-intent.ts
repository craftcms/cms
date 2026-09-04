/**
 * Shared open/close timing for overlays that appear on hover.
 *
 * Three behaviours, none of which an overlay can implement alone:
 *
 * - **Warm-up.** The first hover waits, so brushing past something on the way
 *   somewhere else doesn't flash an overlay open. Once one has opened the
 *   group is *warm* and the rest open immediately — having decided you're
 *   reading the menu, being made to wait again at every item is worse than
 *   useless.
 * - **Cool-down.** The group goes cold again a beat after the last overlay
 *   closes, so warmth doesn't outlive the interaction that earned it.
 * - **Handover.** Opening one overlay closes its siblings *now* rather than
 *   after their own close delay, which is what stops two flyouts overlapping
 *   while you sweep down a list.
 *
 * Ancestors are spared on handover: a nested overlay's trigger is a descendant
 * of the trigger that opened it, so closing "everything else" would take the
 * parent out from under the child.
 *
 * Groups are independent on purpose — flyouts and tooltips each want their own
 * warmth, and opening a flyout shouldn't make tooltips instant.
 */
export interface HoverIntentOptions {
  /** How long the first hover in a cold group waits before opening, in ms. */
  warmUpDelay: number;
  /** How long an overlay stays open after the pointer leaves, in ms. */
  closeDelay: number;
  /** How long the group stays warm after the last overlay closes, in ms. */
  coolDownDelay: number;
}

export interface HoverIntentMember {
  /** The trigger. Its position in the tree is what identifies nesting. */
  readonly element: Element;
  /** Called with the state the group wants the overlay in. */
  setOpen(open: boolean): void;
}

interface MemberState {
  open: boolean;
  openTimer?: ReturnType<typeof setTimeout>;
  closeTimer?: ReturnType<typeof setTimeout>;
}

export class HoverIntentGroup {
  /**
   * Mutable so a caller can retune without rebuilding the group, and so tests
   * can run the timings down to zero.
   */
  options: HoverIntentOptions;

  #members = new Map<HoverIntentMember, MemberState>();

  #warm = false;

  #coolDownTimer?: ReturnType<typeof setTimeout>;

  constructor(options: HoverIntentOptions) {
    this.options = options;
  }

  /** Whether the next hover will open immediately. */
  get warm(): boolean {
    return this.#warm;
  }

  /**
   * Ask for `member` to open.
   *
   * `immediate` skips the warm-up: a keyboard focus is a deliberate act and
   * shouldn't be made to wait the way a passing pointer is.
   */
  requestOpen(member: HoverIntentMember, {immediate = false} = {}): void {
    const state = this.#stateOf(member);

    clearTimeout(state.closeTimer);
    state.closeTimer = undefined;

    this.#closeSiblingsOf(member);

    if (state.open || state.openTimer !== undefined) {
      return;
    }

    if (immediate || this.#warm) {
      this.#open(member, state);

      return;
    }

    state.openTimer = setTimeout(() => {
      state.openTimer = undefined;
      this.#open(member, state);
    }, this.options.warmUpDelay);
  }

  /** Ask for `member` to close, after the group's close delay. */
  requestClose(member: HoverIntentMember): void {
    const state = this.#stateOf(member);

    // A hover that never made it past the warm-up leaves nothing to close.
    clearTimeout(state.openTimer);
    state.openTimer = undefined;

    if (!state.open || state.closeTimer !== undefined) {
      return;
    }

    state.closeTimer = setTimeout(() => {
      state.closeTimer = undefined;
      this.#close(member, state);
    }, this.options.closeDelay);
  }

  /**
   * Record that `member` closed itself — an overlay dismissed on Escape or an
   * outside click, say — so the group doesn't go on thinking it's open.
   */
  notifyClosed(member: HoverIntentMember): void {
    const state = this.#stateOf(member);

    clearTimeout(state.openTimer);
    clearTimeout(state.closeTimer);
    state.openTimer = undefined;
    state.closeTimer = undefined;

    if (state.open) {
      state.open = false;
      this.#startCoolDownIfIdle();
    }
  }

  /** Drops `member` and any timers it owns. Call this on disconnect. */
  remove(member: HoverIntentMember): void {
    const state = this.#members.get(member);

    if (!state) {
      return;
    }

    clearTimeout(state.openTimer);
    clearTimeout(state.closeTimer);
    this.#members.delete(member);
    this.#startCoolDownIfIdle();
  }

  /** Closes everything and goes cold. Mostly here so tests can isolate. */
  reset(): void {
    for (const [member, state] of this.#members) {
      clearTimeout(state.openTimer);
      clearTimeout(state.closeTimer);

      if (state.open) {
        member.setOpen(false);
      }
    }

    this.#members.clear();
    clearTimeout(this.#coolDownTimer);
    this.#coolDownTimer = undefined;
    this.#warm = false;
  }

  #stateOf(member: HoverIntentMember): MemberState {
    let state = this.#members.get(member);

    if (!state) {
      state = {open: false};
      this.#members.set(member, state);
    }

    return state;
  }

  /**
   * Everything open that isn't `member` or one of its ancestors goes now.
   *
   * Without the ancestor check this would close the parent whose flyout the
   * pointer just travelled through to reach the child.
   */
  #closeSiblingsOf(member: HoverIntentMember): void {
    for (const [other, state] of this.#members) {
      if (
        other === member ||
        !state.open ||
        other.element.contains(member.element)
      ) {
        continue;
      }

      clearTimeout(state.closeTimer);
      state.closeTimer = undefined;
      this.#close(other, state);
    }
  }

  #open(member: HoverIntentMember, state: MemberState): void {
    state.open = true;
    clearTimeout(this.#coolDownTimer);
    this.#coolDownTimer = undefined;
    this.#warm = true;
    member.setOpen(true);
  }

  #close(member: HoverIntentMember, state: MemberState): void {
    state.open = false;
    member.setOpen(false);
    this.#startCoolDownIfIdle();
  }

  #startCoolDownIfIdle(): void {
    if (!this.#warm || this.#coolDownTimer !== undefined) {
      return;
    }

    for (const state of this.#members.values()) {
      if (state.open || state.openTimer !== undefined) {
        return;
      }
    }

    this.#coolDownTimer = setTimeout(() => {
      this.#coolDownTimer = undefined;
      this.#warm = false;
    }, this.options.coolDownDelay);
  }
}

/**
 * Nav flyouts.
 *
 * Tooltips get their own group when they adopt this — the two shouldn't share
 * warmth, since reading a nav flyout says nothing about wanting tooltips.
 */
export const flyoutHoverIntent = new HoverIntentGroup({
  warmUpDelay: 250,
  closeDelay: 150,
  coolDownDelay: 500,
});
