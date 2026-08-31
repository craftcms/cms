/**
 * Drag — modern, jQuery-free TypeScript port of Garnish's `Drag`.
 *
 * Builds on {@link BaseDrag} by "picking up" the selected element(s): it
 * snapshots the target's geometry and the full draggee set, builds floating
 * *helper* clones that follow the cursor with a little lag, and animates those
 * helpers back home (or fades them out) when the drag ends. It does not decide
 * *what* to do with a dragged element — that's left to `DragDrop` / `DragSort`.
 *
 * jQuery removals (see doc 09 §4):
 *
 * - `$draggee.clone()` → `draggee.cloneNode(true)`.
 * - `$.outerWidth()/outerHeight()` → `getOuterWidth/getOuterHeight`.
 * - `$.offset()` → `getOffset`; `$.hide()/.show()` → native display stash.
 * - `Garnish.copyInputValues` → `copyInputValues` (`utils/forms.ts`).
 * - `$helper.velocity({left,top})` return tween → the Web Animations API,
 *   mirroring `Modal._fade` (tracked, cancelable, reduced-motion gated).
 * - `Garnish.requestAnimationFrame` → `utils/animation.ts`.
 */

import {FX_DURATION} from '../constants';
import {bod, globals} from '../globals';
import {
  cancelAnimationFrame,
  getUserPreferredAnimationDuration,
  prefersReducedMotion,
  requestAnimationFrame,
} from '../utils/animation';
import {
  coerceElements,
  getOffset,
  getOuterHeight,
  getOuterWidth,
} from '../utils/dom';
import {copyInputValues} from '../utils/forms';
import {isPlainObject} from '../utils/misc';
import {
  BaseDrag,
  type BaseDragSettings,
  type DragItemsInput,
} from './base-drag';
import type {ElementInput} from '../types';

/** A `{left, top}` page-coordinate point used for helper positioning. */
interface Point {
  left: number;
  top: number;
}

/** A helper-wrapping hook: a function, an element/markup to wrap into, or `null`. */
export type DragHelper =
  | ((helper: HTMLElement, index: number) => HTMLElement)
  | ElementInput
  | null;

/** Settings accepted by {@link Drag} (extends {@link BaseDragSettings}). */
export interface DragSettings extends BaseDragSettings {
  /**
   * Which item(s) are actually dragged. A function returning the draggee set, a
   * selector that filters `$items`, or `null` → just `$targetItem`.
   */
  filter: (() => ElementInput) | string | null;
  /** Build one helper for the whole draggee set instead of one per draggee. */
  singleHelper: boolean;
  /** Collapse multiple draggees into the target (hide the rest via `display:none`). */
  collapseDraggees: boolean;
  /** Hide every draggee entirely (`display:none`) while dragging. */
  removeDraggee: boolean;
  /** Hide the draggee via `visibility:hidden` while dragging. */
  hideDraggee: boolean;
  /** Copy `<input>`/`<select>`/`<textarea>` values from source into the clone. */
  copyDraggeeInputValuesToHelper: boolean;
  /** Helper opacity (`1` → no opacity override). */
  helperOpacity: number;
  /** Position the helper's top-left at the cursor, not at the grab offset. */
  moveHelperToCursor: boolean;
  /**
   * Optional helper wrapper. A function `(helper, index) => wrappedHelper`, or an
   * element/markup the clone is appended into. `null` → use the bare clone.
   */
  helper: DragHelper;
  /**
   * Base z-index for drag helpers, which are appended to `<body>` and follow
   * the pointer over the CP's chrome. Defaults to the `--c-z-drag` rung of the
   * CP's stacking ladder (see `docs/z-layers.md`) — Garnish is standalone and
   * can't import `@craftcms/ui`, so the number is repeated here.
   */
  helperBaseZindex: number;
  helperLagBase: number;
  helperLagIncrementDividend: number;
  helperSpacingX: number;
  helperSpacingY: number;

  onReturnHelpersToDraggees: () => void;
}

const noop = (): void => {};

/**
 * The drag class. Extends {@link BaseDrag} with helper-clone creation, a
 * cursor-lag-follow loop, and a Web-Animations-API "return helpers to draggees"
 * animation on drop.
 *
 * @typeParam S - The settings shape; defaults to {@link DragSettings}.
 */
export class Drag<S extends DragSettings = DragSettings> extends BaseDrag<S> {
  /** Default {@link DragSettings}. */
  static override readonly defaults: DragSettings = {
    ...BaseDrag.defaults,
    filter: null,
    singleHelper: false,
    collapseDraggees: false,
    removeDraggee: false,
    hideDraggee: true,
    copyDraggeeInputValuesToHelper: false,
    helperOpacity: 1,
    moveHelperToCursor: false,
    helper: null,
    helperBaseZindex: 3000,
    helperLagBase: 3,
    helperLagIncrementDividend: 1.5,
    helperSpacingX: 5,
    helperSpacingY: 5,
    onReturnHelpersToDraggees: noop,
  };

  // --- Target geometry snapshot (read by drag()/DragSort) -------------------

  targetItemWidth: number | null = null;
  targetItemHeight: number | null = null;
  targetItemPositionInDraggee: number | null = null;

  /** The dragged element(s); the target item is always index 0. */
  $draggee: HTMLElement[] = [];

  /** Items NOT in {@link $draggee} (everything else still tracked). */
  otherItems: Element[] = [];
  totalOtherItems: number | null = null;

  /** The floating clones following the cursor (legacy held jQuery objects). */
  helpers: HTMLElement[] = [];
  helperTargets: Point[] = [];
  helperPositions: Point[] = [];
  helperLagIncrement: number | null = null;

  /** RAF handle for the lag-follow loop. */
  updateHelperPosFrame: number | null = null;

  lastMouseX: number | null = null;
  lastMouseY: number | null = null;

  /** Display value snapshotted off the target so we can restore it. */
  draggeeDisplay: string | null = null;

  /** Virtual midpoint of the draggee (read by DragSort / consumers). */
  draggeeVirtualMidpointX: number | null = null;
  draggeeVirtualMidpointY: number | null = null;

  /** Set true while the return-to-source animation runs; gates allowDragging(). */
  private _returningHelpersToDraggees = false;

  /** Tracked return animations so destroy()/a new drag can cancel mid-flight. */
  private _returnAnimations: Animation[] = [];

  /**
   * @param items - Elements that should be draggable right away, or — via the
   *   legacy param-shift — a `Partial<S>` settings object when no second
   *   argument is given.
   * @param settings - Optional settings overrides (see {@link DragSettings}).
   */
  constructor(
    items?: DragItemsInput | Partial<S> | null,
    settings?: Partial<S>
  ) {
    // Re-run the same plain-object param-shift BaseDrag uses, so we can merge
    // Drag.defaults *under* the caller's overrides before super() applies them.
    let resolvedItems: DragItemsInput | null =
      (items as DragItemsInput) ?? null;
    if (settings === undefined && isPlainObject(items)) {
      settings = items as Partial<S>;
      resolvedItems = null;
    }

    // Explicit merge (doc 09 §1.1, recommended form): Drag.defaults under the
    // caller's settings. BaseDrag's constructor then layers BaseDrag.defaults
    // *under* that via setSettings — and since Drag.defaults already spreads
    // BaseDrag.defaults, every key resolves correctly.
    super(resolvedItems, {
      ...(Drag.defaults as Partial<S>),
      ...(settings ?? {}),
    });
  }

  // --- Lifecycle overrides --------------------------------------------------

  /** Block a new drag while helpers are animating home (legacy parity). */
  override allowDragging(): boolean {
    return !this._returningHelpersToDraggees;
  }

  /**
   * Snapshot the target geometry + display, resolve the draggee set, build the
   * helpers, start the lag-follow loop, then finish drag start-up.
   *
   * DEVIATION (doc 09 §1.5): legacy `Drag.startDragging` *replaced* BaseDrag's
   * and fired `onBeforeDragStart` *before* building helpers. The modern
   * `BaseDrag.startDragging` bundles `onBeforeDragStart` with the rest, so
   * calling `super.startDragging()` last would reverse that order. To preserve
   * the legacy hook ordering we call `onBeforeDragStart` first, do the snapshot,
   * then inline the (small) remainder of `BaseDrag.startDragging`. This
   * duplicates ~4 lines of super; flagged in the impl notes.
   */
  override startDragging(): void {
    this.onBeforeDragStart();

    // Reset helper state.
    this.helpers = [];
    this.helperTargets = [];
    this.helperPositions = [];
    this.lastMouseX = this.lastMouseY = null;

    // Capture the target item's geometry + display.
    this.targetItemWidth = getOuterWidth(this.$targetItem!);
    this.targetItemHeight = getOuterHeight(this.$targetItem!);
    this.draggeeDisplay = getComputedStyle(this.$targetItem!).display;

    // Resolve the draggee set + build helpers + apply hide/collapse styling.
    this.setDraggee(this.findDraggee());

    // Everything not in the draggee set.
    this.otherItems = this.$items.filter(
      (item) => !this.$draggee.includes(item as HTMLElement)
    );
    this.totalOtherItems = this.otherItems.length;

    // Keep the helpers following the cursor with a little lag.
    this.helperLagIncrement =
      this.helpers.length === 1
        ? 0
        : this.settings!.helperLagIncrementDividend / (this.helpers.length - 1);
    this.updateHelperPosFrame = requestAnimationFrame(() =>
      this._updateHelperPos()
    );

    // --- Inlined tail of BaseDrag.startDragging (minus onBeforeDragStart) ---
    this.dragging = true;
    this.setScrollContainer();
    this.onDragStart();
    // Mute activate events while dragging (legacy parity), exactly as
    // BaseDrag.startDragging does.
    globals.activateEventsMuted = true;
  }

  /** Update the draggee's virtual midpoint from fresh coords, then super.drag(). */
  override drag(didMouseMove: boolean): void {
    this.draggeeVirtualMidpointX =
      this.mouseX! - this.mouseOffsetX! + this.targetItemWidth! / 2;
    this.draggeeVirtualMidpointY =
      this.mouseY! - this.mouseOffsetY! + this.targetItemHeight! / 2;

    super.drag(didMouseMove);
  }

  /** Cancel the lag-follow RAF, then super.stopDragging(). */
  override stopDragging(): void {
    if (this.updateHelperPosFrame !== null) {
      cancelAnimationFrame(this.updateHelperPosFrame);
      this.updateHelperPosFrame = null;
    }

    super.stopDragging();
  }

  // --- Draggee management ---------------------------------------------------

  /** Resolve which items are dragged (settings.filter | string | $targetItem). */
  findDraggee(): HTMLElement[] {
    const {filter} = this.settings!;

    if (typeof filter === 'function') {
      return coerceElements(filter()).filter(
        (el): el is HTMLElement => el instanceof HTMLElement
      );
    }

    if (typeof filter === 'string') {
      return this.$items.filter(
        (item): item is HTMLElement =>
          item instanceof HTMLElement && item.matches(filter)
      );
    }

    return this.$targetItem ? [this.$targetItem] : [];
  }

  /** Set $draggee (target first), create helpers, apply hide/collapse styling. */
  setDraggee(draggee: HTMLElement[]): void {
    const target = this.$targetItem!;
    const all = draggee.includes(target) ? draggee : [...draggee, target];

    // Record the target's index within the (target-included) draggee set.
    this.targetItemPositionInDraggee = all.indexOf(target);

    // Keep the target item at the front of the list.
    this.$draggee = [target, ...all.filter((el) => el !== target)];

    // Create the helper(s).
    if (this.settings!.singleHelper) {
      this._createHelper(0);
    } else {
      for (let i = 0; i < this.$draggee.length; i++) {
        this._createHelper(i);
      }
    }

    if (this.settings!.removeDraggee) {
      this.$draggee.forEach((el) => this._hide(el));
    } else if (this.settings!.collapseDraggees) {
      target.style.visibility = 'hidden';
      this.$draggee
        .filter((el) => el !== target)
        .forEach((el) => this._hide(el));
    } else if (this.settings!.hideDraggee) {
      this.$draggee.forEach((el) => (el.style.visibility = 'hidden'));
    }
  }

  /** Append additional draggees mid-drag (create helpers unless collapsed). */
  appendDraggee(newDraggee: HTMLElement[]): void {
    if (!newDraggee.length) {
      return;
    }

    const oldLength = this.$draggee.length;
    this.$draggee = [...this.$draggee, ...newDraggee];

    // Create new helpers (unless collapsing).
    if (!this.settings!.collapseDraggees) {
      const newLength = this.$draggee.length;
      for (let i = oldLength; i < newLength; i++) {
        this._createHelper(i);
      }
    }

    if (this.settings!.removeDraggee || this.settings!.collapseDraggees) {
      newDraggee.forEach((el) => this._hide(el));
    } else if (this.settings!.hideDraggee) {
      newDraggee.forEach((el) => (el.style.visibility = 'hidden'));
    }
  }

  // --- Helper geometry ------------------------------------------------------

  /** The helper's target X position (page coords). */
  getHelperTargetX(real = false): number {
    if (!real && this.settings!.moveHelperToCursor) {
      return this.mouseX!;
    }
    return this.mouseX! - this.mouseOffsetX!;
  }

  /** The helper's target Y position (page coords). */
  getHelperTargetY(real = false): number {
    if (!real && this.settings!.moveHelperToCursor) {
      return this.mouseY!;
    }
    return this.mouseY! - this.mouseOffsetY!;
  }

  // --- Return / fade --------------------------------------------------------

  /**
   * Animate every helper back to its source draggee's offset, then restore the
   * draggees and remove the helpers.
   *
   * Uses the Web Animations API (mirroring `Modal._fade`): tracked + cancelable,
   * reduced-motion gated, and feature-detected (happy-dom lacks
   * `element.animate`, so this resolves synchronously under test). Completion
   * fires after the LAST helper lands (a `pending` countdown), rather than off
   * helper 0's callback like legacy — see impl notes.
   */
  returnHelpersToDraggees(): void {
    this._returningHelpersToDraggees = true;

    let pending = this.helpers.length;
    if (pending === 0) {
      this._showDraggee();
      return;
    }

    this.helpers.forEach((helper, i) => {
      const draggee = this.$draggee[i] as HTMLElement | undefined;

      // Restore the draggee's display now (it animates in under the helper).
      if (draggee) {
        draggee.style.display = this.draggeeDisplay!;
        draggee.style.visibility = this.settings!.hideDraggee ? 'hidden' : '';
      }

      const targetOffset = draggee ? getOffset(draggee) : {left: 0, top: 0};

      const finalize = (): void => {
        helper.style.left = `${targetOffset.left}px`;
        helper.style.top = `${targetOffset.top}px`;
        if (--pending === 0) {
          this._showDraggee();
        }
      };

      // Reduced-motion or no WAAPI → jump straight home.
      if (prefersReducedMotion() || typeof helper.animate !== 'function') {
        finalize();
        return;
      }

      const fromLeft = parseFloat(helper.style.left) || getOffset(helper).left;
      const fromTop = parseFloat(helper.style.top) || getOffset(helper).top;

      const anim = helper.animate(
        [
          {left: `${fromLeft}px`, top: `${fromTop}px`},
          {left: `${targetOffset.left}px`, top: `${targetOffset.top}px`},
        ],
        {
          duration: getUserPreferredAnimationDuration(FX_DURATION),
          fill: 'forwards',
        }
      );
      this._returnAnimations.push(anim);
      anim.addEventListener('finish', finalize, {once: true});
      anim.addEventListener('cancel', finalize, {once: true});
    });
  }

  /** Fade each helper out and remove it (no return tween). */
  fadeOutHelpers(): void {
    this.helpers.forEach((helper) => {
      if (prefersReducedMotion() || typeof helper.animate !== 'function') {
        helper.remove();
        return;
      }
      const anim = helper.animate([{opacity: 1}, {opacity: 0}], {
        duration: getUserPreferredAnimationDuration(FX_DURATION),
        fill: 'forwards',
      });
      anim.addEventListener('finish', () => helper.remove(), {once: true});
      anim.addEventListener('cancel', () => helper.remove(), {once: true});
    });
  }

  // --- Hook (event emitter) -------------------------------------------------

  /** RAF-deferred: trigger('returnHelpersToDraggees') + settings callback. */
  onReturnHelpersToDraggees(): void {
    requestAnimationFrame(() => {
      this.trigger('returnHelpersToDraggees');
      this.settings!.onReturnHelpersToDraggees();
    });
  }

  /** Tear the dragger down: cancel loops/animations, remove helpers, then super. */
  override destroy(): void {
    if (this.updateHelperPosFrame !== null) {
      cancelAnimationFrame(this.updateHelperPosFrame);
      this.updateHelperPosFrame = null;
    }
    this._returnAnimations.forEach((anim) => anim.cancel());
    this._returnAnimations = [];
    this.helpers.forEach((helper) => helper.remove());
    this.helpers = [];

    super.destroy();
  }

  // --- Private --------------------------------------------------------------

  /** Creates a helper clone for the draggee at `index`. */
  private _createHelper(index: number): void {
    const draggee = this.$draggee[index]!;
    let helper = draggee.cloneNode(true) as HTMLElement;
    helper.classList.add('draghelper');

    if (draggee.closest('#content')) {
      helper.classList.add('drag-in-content');
    }
    if (draggee.closest('.slideout-container')) {
      helper.classList.add('drag-in-slideout');
    }

    if (this.settings!.copyDraggeeInputValuesToHelper) {
      copyInputValues(draggee, helper);
    }

    // Blank every name= so cloned radios/checkboxes don't hijack form values.
    helper
      .querySelectorAll<HTMLElement>('[name]')
      .forEach((el) => el.setAttribute('name', ''));

    // Size to match the source (border-box). jQuery `.outerWidth(n)` *sets* the
    // total box; the native equivalent is `box-sizing:border-box` + width/height.
    helper.style.boxSizing = 'border-box';
    helper.style.width = `${Math.ceil(getOuterWidth(draggee))}px`;
    helper.style.height = `${Math.ceil(getOuterHeight(draggee))}px`;
    helper.style.margin = '0';
    helper.style.pointerEvents = 'none';

    // Optional wrapper hook.
    if (this.settings!.helper) {
      const helperSetting = this.settings!.helper;
      if (typeof helperSetting === 'function') {
        helper = helperSetting(helper, index);
      } else {
        const wrapper = coerceElements(helperSetting)[0] as
          | HTMLElement
          | undefined;
        if (wrapper) {
          wrapper.appendChild(helper);
          helper = wrapper;
        }
      }
    }

    bod.appendChild(helper);

    const pos = this._getHelperTarget(index, true);
    helper.style.position = 'absolute';
    helper.style.top = `${pos.top}px`;
    helper.style.left = `${pos.left}px`;
    helper.style.zIndex = String(
      this.settings!.helperBaseZindex + this.$draggee.length - index
    );
    helper.style.display = this.draggeeDisplay!;
    if (this.settings!.helperOpacity !== 1) {
      helper.style.opacity = String(this.settings!.helperOpacity);
    }

    this.helperPositions[index] = {top: pos.top, left: pos.left};
    this.helpers.push(helper);
  }

  /** Target position for helper `index`, incl. per-index spacing. */
  private _getHelperTarget(index: number, real = false): Point {
    return {
      left: this.getHelperTargetX(real) + this.settings!.helperSpacingX * index,
      top: this.getHelperTargetY(real) + this.settings!.helperSpacingY * index,
    };
  }

  /** One frame of the lag-follow loop: ease each helper toward its target. */
  private _updateHelperPos(): void {
    // Recompute targets only when the pointer actually moved.
    if (this.mouseX !== this.lastMouseX || this.mouseY !== this.lastMouseY) {
      for (let i = 0; i < this.helpers.length; i++) {
        this.helperTargets[i] = this._getHelperTarget(i);
      }
      this.lastMouseX = this.mouseX;
      this.lastMouseY = this.mouseY;
    }

    // Gravitate helpers toward their target positions.
    for (let j = 0; j < this.helpers.length; j++) {
      const lag = this.settings!.helperLagBase + this.helperLagIncrement! * j;
      const cur = this.helperPositions[j]!;
      const tgt = this.helperTargets[j]!;
      cur.left += (tgt.left - cur.left) / lag;
      cur.top += (tgt.top - cur.top) / lag;
      this.helpers[j]!.style.left = `${cur.left}px`;
      this.helpers[j]!.style.top = `${cur.top}px`;
    }

    this.updateHelperPosFrame = requestAnimationFrame(() =>
      this._updateHelperPos()
    );
  }

  /** Completion: remove helpers, restore draggees, fire the hook. */
  private _showDraggee(): void {
    for (const helper of this.helpers) {
      helper.remove();
    }
    this.helpers = [];
    this._returnAnimations = [];

    // jQuery .show().css('visibility','') → restore display + clear visibility.
    this.$draggee.forEach((el) => {
      this._show(el);
      el.style.visibility = '';
    });

    this.onReturnHelpersToDraggees();
    this._returningHelpersToDraggees = false;
  }

  /** jQuery `.hide()` — stash the current inline display, set `display:none`. */
  private _hide(el: HTMLElement): void {
    if (el.style.display !== 'none') {
      el.dataset.garnishOldDisplay = el.style.display;
    }
    el.style.display = 'none';
  }

  /** jQuery `.show()` — restore the stashed inline display (or clear it). */
  private _show(el: HTMLElement): void {
    el.style.display = el.dataset.garnishOldDisplay ?? '';
    delete el.dataset.garnishOldDisplay;
  }
}

export default Drag;
