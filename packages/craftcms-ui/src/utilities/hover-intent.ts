/**
 * Shared open/close timing for overlays that appear on hover.
 *
 * Four behaviours, none of which an overlay can implement alone:
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
 * - **Safe area.** An overlay opens *beside* its trigger, so reaching it means
 *   travelling diagonally across whatever sits between the two. A hover that
 *   lands in the triangle from where the pointer left the trigger out to the
 *   overlay's near edge counts as still on the way there, so the group holds
 *   it rather than acting on it, and crossing a neighbour doesn't shut the
 *   thing you're aiming at. The hold expires. `graceDelay` caps how long the
 *   group believes the aim, or a pointer that came to rest inside the
 *   triangle would never get the item it stopped on.
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
  /** How long a pointer may go on aiming at an open overlay, in ms. */
  graceDelay: number;
}

export interface HoverIntentMember {
  /** The trigger. Its position in the tree is what identifies nesting. */
  readonly element: Element;
  /** Called with the state the group wants the overlay in. */
  setOpen(open: boolean): void;
  /**
   * Where the open overlay is, for the safe area. A member with nothing to
   * measure leaves this off and gets no grace. A leaf nav item joins the
   * group for its timing but has no flyout to aim at.
   */
  overlayRect?(): DOMRect | undefined;
}

interface Point {
  x: number;
  y: number;
}

type Triangle = [Point, Point, Point];

interface MemberState {
  open: boolean;
  openTimer?: ReturnType<typeof setTimeout>;
  closeTimer?: ReturnType<typeof setTimeout>;
  /** Where and when the pointer left the trigger. See the safe area. */
  exit?: Point & {at: number};
}

/**
 * The triangle a pointer may cross without the overlay it just left counting
 * as abandoned. It runs from where the pointer left the trigger out to the
 * near vertical edge of the overlay.
 *
 * The edge has to be strictly clear of the exit point, or the three corners
 * are collinear and the "triangle" would contain the whole plane.
 */
function safeArea(exit: Point, rect: DOMRect): Triangle | undefined {
  const x =
    rect.left > exit.x
      ? rect.left
      : rect.right < exit.x
        ? rect.right
        : undefined;

  if (x === undefined || rect.height <= 0) {
    return undefined;
  }

  return [exit, {x, y: rect.top}, {x, y: rect.bottom}];
}

/** Which side of `qr` the point `p` falls on. */
function side(p: Point, q: Point, r: Point): number {
  return (p.x - r.x) * (q.y - r.y) - (q.x - r.x) * (p.y - r.y);
}

function contains([a, b, c]: Triangle, point: Point): boolean {
  const d1 = side(point, a, b);
  const d2 = side(point, b, c);
  const d3 = side(point, c, a);

  return !((d1 < 0 || d2 < 0 || d3 < 0) && (d1 > 0 || d2 > 0 || d3 > 0));
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

  /** Latest pointer position, for the safe area. */
  #pointer?: Point;

  /** An open request the safe area is holding back. */
  #pendingOpen?: HoverIntentMember;

  #pointerWatch?: AbortController;

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
   * `immediate` skips both the warm-up and the safe area. A keyboard focus is
   * deliberate, so the group neither makes it wait the way it makes a passing
   * pointer wait, nor reads it as a pointer on its way past.
   */
  requestOpen(member: HoverIntentMember, {immediate = false} = {}): void {
    const state = this.#stateOf(member);

    // Watched from the first hover, not the first open. The pointer enters a
    // trigger before it moves within it, so a watch started on opening would
    // miss every position up to the one that left again.
    this.#watchPointer();

    clearTimeout(state.closeTimer);
    state.closeTimer = undefined;
    state.exit = undefined;

    // The pointer is only crossing this item on the way to an overlay already
    // open beside it. Held rather than dropped, because it may yet stop here,
    // and then this is the hover it meant. `#onPointerMove` and the close tick
    // both retry it.
    if (!immediate && this.#aimedAt(member)) {
      this.#pendingOpen = member;

      return;
    }

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

    // The pointer has left, so a hover held here was passing through after all.
    if (this.#pendingOpen === member) {
      this.#pendingOpen = undefined;
    }

    if (!state.open || state.closeTimer !== undefined) {
      return;
    }

    state.exit = this.#pointer && {...this.#pointer, at: Date.now()};
    this.#scheduleClose(member, state);
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

    if (this.#pendingOpen === member) {
      this.#pendingOpen = undefined;
    }

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
    this.#pendingOpen = undefined;
    this.#pointer = undefined;
    this.#pointerWatch?.abort();
    this.#pointerWatch = undefined;
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

  /**
   * Whether the pointer is still travelling towards this member's overlay. It
   * has to be inside the safe area, and there for less than `graceDelay`. A
   * pointer that still hasn't arrived by then isn't travelling anywhere.
   */
  #graceHolds(member: HoverIntentMember, state: MemberState): boolean {
    const {exit} = state;
    const pointer = this.#pointer;

    if (!exit || !pointer || Date.now() - exit.at >= this.options.graceDelay) {
      return false;
    }

    const rect = member.overlayRect?.();
    const area = rect && safeArea(exit, rect);

    return !!area && contains(area, pointer);
  }

  /**
   * Whether hovering `member` is really the pointer aiming past it, at an
   * overlay that handover would otherwise close. This skips ancestors, which
   * survive handover anyway and so need no grace.
   */
  #aimedAt(member: HoverIntentMember): boolean {
    for (const [other, state] of this.#members) {
      if (
        other === member ||
        !state.open ||
        other.element.contains(member.element)
      ) {
        continue;
      }

      if (this.#graceHolds(other, state)) {
        return true;
      }
    }

    return false;
  }

  #scheduleClose(member: HoverIntentMember, state: MemberState): void {
    state.closeTimer = setTimeout(() => {
      state.closeTimer = undefined;

      // Still crossing the safe area towards this overlay; check back rather
      // than closing it out from under the pointer.
      if (this.#graceHolds(member, state)) {
        this.#scheduleClose(member, state);

        return;
      }

      this.#close(member, state);
      this.#flushPending();
    }, this.options.closeDelay);
  }

  /**
   * Let a held hover through. Not `immediate`, so if some other overlay still
   * has a live safe area, `requestOpen` holds it again.
   */
  #flushPending(): void {
    const member = this.#pendingOpen;

    this.#pendingOpen = undefined;

    if (member) {
      this.requestOpen(member);
    }
  }

  #onPointerMove = (event: PointerEvent): void => {
    this.#pointer = {x: event.clientX, y: event.clientY};

    if (this.#pendingOpen) {
      this.#flushPending();
    }
  };

  /**
   * ponytail: attached on the first hover and released only on `reset`. It's
   * a passive listener writing two numbers. Tearing it down every time the
   * group goes idle costs more bookkeeping than it saves.
   */
  #watchPointer(): void {
    if (this.#pointerWatch) {
      return;
    }

    this.#pointerWatch = new AbortController();
    document.addEventListener('pointermove', this.#onPointerMove, {
      capture: true,
      passive: true,
      signal: this.#pointerWatch.signal,
    });
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
    state.exit = undefined;
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
  graceDelay: 300,
});
