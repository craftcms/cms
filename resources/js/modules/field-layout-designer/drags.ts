import {
  bod,
  Drag,
  FX_DURATION,
  getDist,
  getOffset,
  getOuterHeight,
  getOuterWidth,
  getUserPreferredAnimationDuration,
  hasAttr,
  hitTest,
  prefersReducedMotion,
} from '@craftcms/garnish';
import {fldElementData, fldTabData} from './support';
import type {FieldLayoutDesigner} from './field-layout-designer';

// jQuery (`$`) survives only at the Craft.Grid seam (`tabGrid.addItems/removeItems`
// require jQuery). All other DOM work here is native.
declare const $: any;

/**
 * Drag-sort logic for tabs and elements. Native DOM port of the legacy
 * `Craft.FieldLayoutDesigner.BaseDrag` / `.TabDrag` / `.ElementDrag` onto the
 * modern `@craftcms/garnish` `Drag`.
 *
 * The parent-owned `$items`, `$draggee`, and `helpers` are already native
 * `Element[]`/`HTMLElement[]` arrays (not jQuery collections), so the array math
 * uses {@link union}/{@link without}/`filter`. FLD's own `$insertion`/`$caboose`
 * are native elements too.
 */

/** Coerce an array / element / null into a native `Element[]`. */
function toEls(x: any): Element[] {
  if (x == null) {
    return [];
  }
  if (Array.isArray(x)) {
    return x;
  }
  return [x];
}

/** Union of a native array with anything coercible, deduped (legacy `$().add()`). */
function union(a: Element[], b: any): Element[] {
  const set = new Set<Element>(a);
  for (const el of toEls(b)) {
    set.add(el);
  }
  return [...set];
}

/** `a` minus anything coercible (legacy `$items.not(b)`). */
function without(a: Element[], b: any): Element[] {
  const remove = new Set<Element>(toEls(b));
  return a.filter((el) => !remove.has(el));
}

/** jQuery `.height()` — content-box height (excludes padding/border). */
function getContentHeight(el: HTMLElement): number {
  const cs = getComputedStyle(el);
  return (
    el.clientHeight -
    parseFloat(cs.paddingTop || '0') -
    parseFloat(cs.paddingBottom || '0')
  );
}

interface Midpoint {
  left: number;
  top: number;
}

/** Native replacement for the legacy `$item.data('midpoint', …)`. */
const midpointData = new WeakMap<Element, Midpoint>();

export class BaseDrag extends Drag {
  designer: FieldLayoutDesigner;
  $insertion: HTMLElement | null = null;
  showingInsertion = false;
  $caboose: HTMLElement[] = [];

  /**
   * Constructor — `super(null, settings)` first (we can't reference
   * `this.designer` before `super()`), then resolve the initial item set.
   */
  constructor(designer: FieldLayoutDesigner, settings?: any) {
    super(null, settings);
    this.designer = designer;
    this.addItems(this.findItems());
  }

  /** Overridden by subclasses. */
  findItems(): any {
    return [];
  }

  /** Overridden by subclasses. */
  createInsertion(): HTMLElement | null {
    return null;
  }

  /** Overridden by subclasses. */
  createCaboose(): HTMLElement[] {
    return [];
  }

  /**
   * On Drag Start
   */
  override onDragStart(): void {
    super.onDragStart();

    // Create the insertion
    this.$insertion = this.createInsertion();

    // Add the caboose
    this.$caboose = this.createCaboose();
    this.$items = union(this.$items, this.$caboose);

    bod.classList.add('dragging');
  }

  removeCaboose(): void {
    this.$items = without(this.$items, this.$caboose);
    this.$caboose.forEach((el) => el.remove());
  }

  swapDraggeeWithInsertion(): void {
    this.$draggee[0]!.before(this.$insertion!);
    this.$draggee.forEach((el) => el.remove());
    this.$items = union(without(this.$items, this.$draggee), this.$insertion);
    this.showingInsertion = true;
  }

  swapInsertionWithDraggee(): void {
    this.$insertion!.replaceWith(...this.$draggee);
    this.$items = union(without(this.$items, this.$insertion), this.$draggee);
    this.showingInsertion = false;
  }

  /** Sets item midpoints up front so we don't recompute them on every mouse move. */
  setMidpoints(): void {
    for (const item of this.$items) {
      if (!(item instanceof HTMLElement)) {
        continue;
      }
      // Skip library elements
      if (item.classList.contains('unused')) {
        continue;
      }

      const offset = getOffset(item);
      midpointData.set(item, {
        left: offset.left + getOuterWidth(item) / 2,
        top: offset.top + getOuterHeight(item) / 2,
      });
    }
  }

  /**
   * Returns the closest item to the cursor.
   */
  getClosestItem(): Element | null {
    let closestItem: Element | null = null;
    let closestItemMouseDiff: number | null = null;

    for (const item of this.$items) {
      const midpoint = midpointData.get(item);
      if (!midpoint) {
        continue;
      }

      const mouseDiff = getDist(
        midpoint.left,
        midpoint.top,
        this.mouseX!,
        this.mouseY!
      );

      if (closestItem === null || mouseDiff < closestItemMouseDiff!) {
        closestItem = item;
        closestItemMouseDiff = mouseDiff;
      }
    }

    return closestItem;
  }

  checkForNewClosestItem(): void {
    // Is there a new closest item?
    const closestItem = this.getClosestItem();

    if (closestItem === this.$insertion) {
      return;
    }

    if (closestItem) {
      if (
        this.showingInsertion &&
        this.$items.indexOf(this.$insertion!) <
          this.$items.indexOf(closestItem) &&
        closestItem instanceof HTMLElement &&
        !this.$caboose.includes(closestItem)
      ) {
        closestItem.after(this.$insertion!);
      } else {
        closestItem.before(this.$insertion!);
      }
    }

    // we only want to do it all if there's at least one tab in the layout
    if (this.designer.tabGrid.$items.length > 0) {
      this.$items = union(this.$items, this.$insertion);
      this.showingInsertion = true;
      this.setMidpoints();
    }
  }

  /**
   * On Drag Stop
   */
  override onDragStop(): void {
    if (this.showingInsertion) {
      this.swapInsertionWithDraggee();
    }

    this.removeCaboose();

    // return the helpers to the draggees
    const first = this.$draggee[0];
    const offset = first ? getOffset(first) : null;
    if (!offset || (offset.top === 0 && offset.left === 0)) {
      for (const el of this.$draggee) {
        el.style.display = this.draggeeDisplay ?? '';
        el.style.visibility = 'visible';
        this._fade(el, 0, 1);
      }
      const helper = this.helpers[0];
      if (helper) {
        this._fade(helper, 1, 0, () => {
          this._showDraggeeFallback();
        });
      } else {
        this._showDraggeeFallback();
      }
    } else {
      this.returnHelpersToDraggees();
    }

    super.onDragStop();

    bod.classList.remove('dragging');
  }

  /**
   * Opacity tween via the Web Animations API (reduced-motion gated), replacing
   * the legacy jQuery `.animate({opacity})`. Runs `onDone` when it lands.
   */
  private _fade(
    el: HTMLElement,
    from: number,
    to: number,
    onDone?: () => void
  ): void {
    el.style.opacity = String(to);
    if (prefersReducedMotion()) {
      onDone?.();
      return;
    }
    const anim = el.animate([{opacity: from}, {opacity: to}], {
      duration: getUserPreferredAnimationDuration(FX_DURATION),
      fill: 'forwards',
    });
    if (onDone) {
      anim.addEventListener('finish', onDone, {once: true});
      anim.addEventListener('cancel', onDone, {once: true});
    }
  }

  /**
   * Replicates the modern (private) `Drag._showDraggee` for the legacy
   * "fade in place" branch above: remove the helpers, restore the draggee(s),
   * then fire the return hook.
   */
  private _showDraggeeFallback(): void {
    for (const helper of this.helpers) {
      helper.remove();
    }
    this.helpers = [];

    const hideDraggee = Object.getOwnPropertyDescriptor(
      this.settings ?? {},
      'hideDraggee'
    )?.value;
    this.$draggee.forEach((el) => {
      el.style.display = this.draggeeDisplay ?? '';
      el.style.visibility = hideDraggee ? 'hidden' : '';
    });

    this.onReturnHelpersToDraggees();
  }
}

export class TabDrag extends BaseDrag {
  /**
   * Constructor
   */
  constructor(designer: FieldLayoutDesigner) {
    super(designer, {
      handle: '.tab',
    });
  }

  override findItems(): any {
    return Array.from(
      this.designer.$tabContainer.querySelectorAll(':scope > div.fld-tab')
    );
  }

  /**
   * On Drag Start
   */
  override onDragStart(): void {
    super.onDragStart();
    this.swapDraggeeWithInsertion();
    this.setMidpoints();
  }

  override swapDraggeeWithInsertion(): void {
    super.swapDraggeeWithInsertion();
    this.designer.tabGrid.removeItems($(this.$draggee));
    this.designer.tabGrid.addItems($(this.$insertion));
  }

  override swapInsertionWithDraggee(): void {
    super.swapInsertionWithDraggee();
    this.designer.tabGrid.removeItems($(this.$insertion));
    this.designer.tabGrid.addItems($(this.$draggee));
  }

  /**
   * On Drag
   */
  override onDrag(): void {
    this.checkForNewClosestItem();
    super.onDrag();
  }

  /**
   * On Drag Stop
   */
  override onDragStop(): void {
    super.onDragStop();

    // "show" the tab, but make it invisible
    for (const el of this.$draggee) {
      el.style.display = this.draggeeDisplay ?? '';
      el.style.visibility = 'hidden';
    }

    fldTabData.get(this.$draggee[0]!)?.updatePositionInConfig();
  }

  /**
   * Creates the caboose
   */
  override createCaboose(): HTMLElement[] {
    const $caboose = document.createElement('div');
    $caboose.className = 'fld-tab fld-tab-caboose';
    this.designer.$tabContainer.appendChild($caboose);
    this.designer.tabGrid.addItems($($caboose));
    return [$caboose];
  }

  /**
   * Removes the caboose
   */
  override removeCaboose(): void {
    super.removeCaboose();
    this.designer.tabGrid.removeItems($(this.$caboose));
  }

  /**
   * Creates the insertion
   */
  override createInsertion(): HTMLElement {
    const $draggee = this.$draggee[0]!;
    const $tab = $draggee.querySelector<HTMLElement>('.tab');
    const $tabContent = $draggee.querySelector<HTMLElement>('.fld-tabcontent');
    if (!$tab || !$tabContent) {
      throw new Error('The dragged field layout tab is incomplete.');
    }

    const template = document.createElement('template');
    template.innerHTML = `
<div class="fld-tab fld-insertion" style="height: ${getContentHeight(
      $draggee
    )}px;">
  <div class="tabs"><div class="tab sel draggable" style="width: ${getOuterWidth(
    $tab
  )}px; height: ${getOuterHeight($tab) + 2}px;"></div></div>
  <div class="fld-tabcontent" style="height: ${
    getContentHeight($tabContent) - 2
  }px;"></div>
</div>
`.trim();
    const insertion = template.content.firstElementChild;
    if (!(insertion instanceof HTMLElement)) {
      throw new Error('The field layout insertion template is invalid.');
    }
    return insertion;
  }
}

export class ElementDrag extends BaseDrag {
  draggingLibraryElement = false;
  draggingField = false;
  draggingMultiInstanceElement = false;
  originalTab: any = null;

  /**
   * On Drag Start
   */
  override onDragStart(): void {
    super.onDragStart();

    const $draggee = this.$draggee[0]!;

    // Are we dragging an element from the library?
    this.draggingLibraryElement = $draggee.classList.contains('unused');

    // Is it a field?
    this.draggingField = $draggee.classList.contains('fld-field');

    // Can the element have multiple instances?
    this.draggingMultiInstanceElement = hasAttr(
      $draggee,
      'data-is-multi-instance'
    );

    // keep UI elements visible
    if (this.draggingLibraryElement && this.draggingMultiInstanceElement) {
      for (const el of this.$draggee) {
        el.style.display = this.draggeeDisplay ?? '';
        el.style.visibility = 'visible';
      }
    }

    // Swap the draggee with the insertion if dragging a selected item
    if (!this.draggingLibraryElement) {
      const tabEl = $draggee.closest('.fld-tab');
      this.originalTab = tabEl ? fldTabData.get(tabEl) : null;
      this.swapDraggeeWithInsertion();
    } else {
      this.originalTab = null;
    }

    this.setMidpoints();
  }

  /**
   * On Drag
   */
  override onDrag(): void {
    if (this.isDraggeeMandatory() || this.isHoveringOverTab()) {
      this.checkForNewClosestItem();
    } else if (this.showingInsertion) {
      this.$insertion?.remove();
      this.$items = without(this.$items, this.$insertion);
      this.showingInsertion = false;
      this.setMidpoints();
    }

    super.onDrag();
  }

  isDraggeeMandatory(): boolean {
    return hasAttr(this.$draggee[0]!, 'data-mandatory');
  }

  isHoveringOverTab(): boolean {
    for (let i = 0; i < this.designer.tabGrid.$items.length; i++) {
      if (
        hitTest(this.mouseX!, this.mouseY!, this.designer.tabGrid.$items.get(i))
      ) {
        return true;
      }
    }

    return false;
  }

  override findItems(): any {
    // Return all of the in-use layout elements. (We'll add the library elements via initLibraryElement().)
    return Array.from(
      this.designer.$tabContainer.querySelectorAll('.fld-element')
    );
  }

  /**
   * Creates the caboose
   */
  override createCaboose(): HTMLElement[] {
    const $caboose: HTMLElement[] = [];
    const $fieldContainers = this.designer.$tabContainer.querySelectorAll(
      ':scope > .fld-tab > .fld-tabcontent'
    );

    for (const $fieldContainer of $fieldContainers) {
      const $div = document.createElement('div');
      const $addBtn = $fieldContainer.querySelector('[command="--add-field"]');
      if ($addBtn) {
        $addBtn.before($div);
      } else {
        $fieldContainer.appendChild($div);
      }
      $caboose.push($div);
    }

    return $caboose;
  }

  /**
   * Creates the insertion
   */
  override createInsertion(): HTMLElement {
    const $insertion = document.createElement('div');
    $insertion.className = 'fld-element fld-insertion';
    $insertion.style.height = `${getOuterHeight(this.$draggee[0]!)}px`;
    return $insertion;
  }

  /**
   * On Drag Stop
   */
  override onDragStop(): void {
    const showingInsertion = this.showingInsertion;
    if (showingInsertion) {
      if (this.draggingLibraryElement) {
        const draggee = this.$draggee[0];
        if (!draggee) {
          throw new Error('Field layout drag requires a draggee.');
        }
        // Clone the element and set this.$draggee to the clone, as if we were dragging that all along
        this.$draggee = [
          this.designer.cloneLibraryElementForSelection(draggee),
        ];
      }
    } else if (!this.draggingLibraryElement) {
      const $draggee = this.$draggee[0]!;
      const $libraryElement = this.draggingField
        ? this.designer.$fields.find((el: HTMLElement) =>
            el.matches(`[data-attribute="${$draggee.dataset.attribute}"]`)
          )
        : this.designer.$uiLibraryElements.find((el: HTMLElement) =>
            el.matches(`[data-type="${$draggee.dataset.type}"]`)
          );

      if (this.draggingField && $libraryElement) {
        // show the field in the library
        $libraryElement.classList.remove('hidden');
        $libraryElement.closest('.fld-field-group')?.classList.remove('hidden');
      }

      // Destroy the original element
      fldElementData.get($draggee)?.destroy();

      // Set this.$draggee to the library element, as if we were dragging that all along
      this.$draggee = $libraryElement ? [$libraryElement] : [];
    }

    super.onDragStop();

    for (const el of this.$draggee) {
      el.style.display = this.draggeeDisplay ?? '';
      el.style.visibility =
        this.draggingField || showingInsertion ? 'hidden' : 'visible';
    }

    if (showingInsertion) {
      const tabEl = this.$draggee[0]?.closest('.fld-tab');
      const tab = tabEl ? fldTabData.get(tabEl) : undefined;
      let element: any;

      if (this.draggingLibraryElement) {
        element = tab!.initElement(this.$draggee[0]);
        element.onSelect();
      } else {
        element = fldElementData.get(this.$draggee[0]!);

        // New tab?
        if (tab !== this.originalTab) {
          const config = element.config;

          this.originalTab.updateConfig((config: any) => {
            const index = element.index;
            if (index === -1) {
              return false;
            }
            config.elements.splice(index, 1);
            return config;
          });

          element.tab = tab;
          element.config = config;
        }
      }

      element.updatePositionInConfig();
    }
  }
}
