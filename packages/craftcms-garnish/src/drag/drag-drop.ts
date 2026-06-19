/**
 * DragDrop — modern, jQuery-free TypeScript port of Garnish's `DragDrop`.
 *
 * Builds on {@link Drag} by letting you set up "drop targets" the dragged
 * element(s) can be dropped onto. Hit-detection reuses the shared `hitTest`
 * (`utils/dom.ts`), so this layer is thin.
 *
 * jQuery removals (see doc 09 §4):
 *
 * - `$(this.settings.dropTargets)` / `$(fn())` → `coerceElements(...)`.
 * - `Garnish.hitTest(mouseX, mouseY, elem)` → `hitTest(this.mouseX!, this.mouseY!, elem)`.
 * - `$activeDropTarget.addClass/removeClass` → `el.classList.add/remove`.
 * - `this.$activeDropTarget[0]` identity compare → raw `HTMLElement` ref compare.
 *
 * RENAME (doc 09 §5.2): legacy `$activeDropTarget` was a jQuery object and
 * consumers read `this.$activeDropTarget[0]`. Modern holds a single
 * `HTMLElement | null` directly — consumers must drop the `[0]`.
 */

import {coerceElements, hitTest} from '../utils/dom';
import {isPlainObject} from '../utils/misc';
import {Drag, type DragSettings} from './drag';
import type {ElementInput} from '../types';

/** Settings accepted by {@link DragDrop} (extends {@link DragSettings}). */
export interface DragDropSettings extends DragSettings {
  /** Drop-target element(s), a selector, or a resolver function. */
  dropTargets: ElementInput | (() => ElementInput) | null;
  /** Called whenever the active drop target changes (with the new one or `null`). */
  onDropTargetChange: (activeDropTarget: HTMLElement | null) => void;
  /** Class toggled on the active drop target. */
  activeDropTargetClass: string;
}

const noop = (): void => {};

/**
 * The drag-and-drop class. Extends {@link Drag} with drop targets and
 * active-target tracking. There is no separate "drop" event — consumers read
 * {@link $activeDropTarget} inside their own `dragStop`/`onDragStop` handler to
 * perform the drop (legacy contract preserved).
 *
 * @typeParam S - The settings shape; defaults to {@link DragDropSettings}.
 */
export class DragDrop<
  S extends DragDropSettings = DragDropSettings,
> extends Drag<S> {
  /** Default {@link DragDropSettings}. */
  static override readonly defaults: DragDropSettings = {
    ...Drag.defaults,
    dropTargets: null,
    onDropTargetChange: noop,
    activeDropTargetClass: 'active',
  };

  /** Resolved drop targets, or `null` when none (legacy: jQuery | null). */
  $dropTargets: HTMLElement[] | null = null;

  /** The element the cursor is currently over, or `null` (legacy: jQuery | null). */
  $activeDropTarget: HTMLElement | null = null;

  /**
   * @param settings - Optional settings overrides (see {@link DragDropSettings}).
   *   Legacy `DragDrop.init` only took `(settings)`; items come via `addItems`.
   */
  constructor(settings?: Partial<S>) {
    // Mirror legacy `init(settings)`: items are never passed positionally. Guard
    // against a stray non-settings first arg the same way the param-shift would.
    const resolved =
      settings !== undefined && isPlainObject(settings)
        ? settings
        : ((settings as Partial<S> | undefined) ?? ({} as Partial<S>));
    super(null, {
      ...(DragDrop.defaults as Partial<S>),
      ...resolved,
    });
  }

  /** Resolve `settings.dropTargets` → `HTMLElement[] | null` (null if empty). */
  updateDropTargets(): void {
    const dt = this.settings!.dropTargets;
    if (!dt) {
      this.$dropTargets = null;
      return;
    }

    const resolved = (
      typeof dt === 'function' ? coerceElements(dt()) : coerceElements(dt)
    ).filter((el): el is HTMLElement => el instanceof HTMLElement);

    this.$dropTargets = resolved.length ? resolved : null;
  }

  override onDragStart(): void {
    this.updateDropTargets();
    this.$activeDropTarget = null;
    super.onDragStart();
  }

  override onDrag(): void {
    if (this.$dropTargets) {
      // Is the cursor over any drop target? First match wins (legacy parity).
      let active: HTMLElement | null = null;
      for (const el of this.$dropTargets) {
        if (hitTest(this.mouseX!, this.mouseY!, el)) {
          active = el;
          break;
        }
      }

      // Has the active target changed? (identity compare, no `[0]` unwrap)
      if (active !== this.$activeDropTarget) {
        if (this.$activeDropTarget) {
          this.$activeDropTarget.classList.remove(
            this.settings!.activeDropTargetClass
          );
        }
        this.$activeDropTarget = active;
        if (active) {
          active.classList.add(this.settings!.activeDropTargetClass);
        }
        this.settings!.onDropTargetChange(this.$activeDropTarget);
      }
    }

    super.onDrag();
  }

  override onDragStop(): void {
    if (this.$dropTargets && this.$activeDropTarget) {
      this.$activeDropTarget.classList.remove(
        this.settings!.activeDropTargetClass
      );
    }

    super.onDragStop();
  }
}

export default DragDrop;
