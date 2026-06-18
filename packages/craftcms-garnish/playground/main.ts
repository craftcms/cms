/**
 * @craftcms/garnish — interactive playground.
 *
 * Wires the REAL source (`../src/index.ts` + `../src/compat.ts`) into clickable
 * demos so we can exercise the modern, jQuery-free core in a browser with Vite
 * HMR. This file is dev-only; it is never shipped and never part of the tsdown
 * build.
 */

import {
  Modal,
  HUD,
  DisclosureMenu,
  BaseDrag,
  Drag,
  DragDrop,
  DragSort,
  DragMove,
  getFocusableElements,
  isKeyboardFocusable,
  installActivate,
  hasAttr,
  getDist,
  type ModalSettings,
} from '../src/index';
import {installGarnishCompat} from '../src/compat';

/* ------------------------------------------------------------------------- *
 * Event-log panel
 * ------------------------------------------------------------------------- */

const logList = document.getElementById('pg-log-list') as HTMLOListElement;

function log(tag: string, message: string, isError = false): void {
  const li = document.createElement('li');
  if (isError) {
    li.className = 'pg-log-error';
  }

  const time = document.createElement('time');
  time.textContent = new Date().toLocaleTimeString(undefined, {
    hour12: false,
  });

  const tagEl = document.createElement('span');
  tagEl.className = 'pg-log-tag';
  tagEl.textContent = `[${tag}] `;

  const text = document.createTextNode(message);

  li.append(time, tagEl, text);
  logList.appendChild(li);
  logList.parentElement!.scrollTop = logList.parentElement!.scrollHeight;
}

document.getElementById('pg-log-clear')!.addEventListener('click', () => {
  logList.innerHTML = '';
});

log('ready', 'Playground loaded. Imports resolved from ../src directly.');

/* ------------------------------------------------------------------------- *
 * Helpers
 * ------------------------------------------------------------------------- */

/** Build a styled modal container with the given heading + body HTML. */
function buildModalContainer(title: string, body: string): HTMLElement {
  const el = document.createElement('div');
  el.className = 'pg-modal';
  el.innerHTML = `
    <h3>${title}</h3>
    <div class="pg-modal-body">${body}</div>
    <div class="pg-modal-actions">
      <button type="button" class="pg-modal-primary" data-modal-close>Close (hide)</button>
    </div>
  `;
  document.body.appendChild(el);
  el.querySelector('[data-modal-close]')!.addEventListener('click', () => {
    // Find the owning Modal via the static registry and hide it.
    const owner = Modal.instances.find((m) => m.$container === el);
    owner?.hide();
  });
  return el;
}

/** Wire every demo event of a modal into the log panel. */
function wireModalEvents(modal: Modal, label: string): void {
  for (const evt of ['show', 'hide', 'fadeIn', 'fadeOut', 'escape'] as const) {
    modal.on(evt, () => log('modal', `${label}: ${evt}`));
  }
}

/**
 * Wire a dragger's lifecycle events into the log panel. `drag` fires once per
 * RAF frame, so it is coalesced to a single "drag (moving…)" line per gesture to
 * keep the log readable; `dragStart` / `dragStop` always log.
 */
function wireDragEvents(dragger: BaseDrag, tag: string, label: string): void {
  let dragging = false;
  dragger.on('dragStart', () => {
    dragging = false;
    log(tag, `${label}: dragStart`);
  });
  dragger.on('drag', () => {
    if (!dragging) {
      dragging = true;
      log(tag, `${label}: drag (moving…)`);
    }
  });
  dragger.on('dragStop', () => log(tag, `${label}: dragStop`));
}

/* ------------------------------------------------------------------------- *
 * 1. Modal demos
 * ------------------------------------------------------------------------- */

let basicModal: Modal | null = null;

function getBasicModal(): Modal {
  if (basicModal && Modal.instances.includes(basicModal)) {
    return basicModal;
  }
  const container = buildModalContainer(
    'Basic modal',
    '<p>A plain <code>new Modal(container)</code>. Try the quickShow / quickHide buttons too.</p>'
  );
  basicModal = new Modal(container, {autoShow: false});
  wireModalEvents(basicModal, 'basic');
  return basicModal;
}

const modalActions: Record<string, () => void> = {
  'basic-show': () => getBasicModal().show(),
  'basic-hide': () => getBasicModal().hide(),
  'quick-show': () => getBasicModal().quickShow(),
  'quick-hide': () => getBasicModal().quickHide(),

  'esc-shade': () => {
    const container = buildModalContainer(
      'hideOnEsc + hideOnShadeClick',
      '<p>Press <kbd>Esc</kbd> or click the dimmed shade outside this box to close it.</p>'
    );
    const settings: Partial<ModalSettings> = {
      hideOnEsc: true,
      hideOnShadeClick: true,
    };
    const modal = new Modal(container, settings);
    wireModalEvents(modal, 'esc/shade');
    log('modal', 'Opened modal with hideOnEsc + hideOnShadeClick');
  },

  'close-others': () => {
    // First, ensure something else is open to be closed.
    getBasicModal().show();
    const container = buildModalContainer(
      'closeOtherModals: true',
      '<p>Opening this one auto-closed any other visible modal (watch the log for the other modal’s <code>hide</code>).</p>'
    );
    const modal = new Modal(container, {closeOtherModals: true});
    wireModalEvents(modal, 'closeOthers');
    log('modal', 'Opened closeOtherModals modal — prior modal should hide');
  },

  'destroy-all': () => {
    const count = Modal.instances.length;
    // Copy: destroy() mutates Modal.instances.
    [...Modal.instances].forEach((m) => m.destroy());
    basicModal = null;
    log('modal', `Destroyed ${count} modal instance(s)`);
  },
};

document.querySelectorAll<HTMLButtonElement>('[data-modal]').forEach((btn) => {
  btn.addEventListener('click', () => {
    const action = btn.dataset.modal!;
    try {
      modalActions[action]?.();
    } catch (err) {
      log('modal', `Error: ${(err as Error).message}`, true);
    }
  });
});

/* ------------------------------------------------------------------------- *
 * 2. Focusable matcher + focus trap
 * ------------------------------------------------------------------------- */

const focusSample = document.getElementById('focus-sample') as HTMLElement;

const focusActions: Record<string, () => void> = {
  highlight: () => {
    let focusableCount = 0;
    let keyboardCount = 0;

    // Clear first.
    focusActions.clear!();

    const focusable = getFocusableElements(focusSample);
    focusable.forEach((el) => {
      el.classList.add('pg-highlight-focusable');
      focusableCount++;
      if (isKeyboardFocusable(el)) {
        el.classList.add('pg-highlight-keyboard');
        keyboardCount++;
      }
    });

    log(
      'focus',
      `getFocusableElements matched ${focusableCount}; ${keyboardCount} keyboard-focusable (blue ring)`
    );
  },

  clear: () => {
    focusSample
      .querySelectorAll('.pg-highlight-focusable, .pg-highlight-keyboard')
      .forEach((el) => {
        el.classList.remove('pg-highlight-focusable', 'pg-highlight-keyboard');
      });
  },

  'trap-modal': () => {
    const container = buildModalContainer(
      'Focus trap',
      `<p>Tab / Shift+Tab cycles only within these three controls:</p>
       <p>
         <button type="button">First</button>
         <input type="text" placeholder="Second" />
         <a href="#">Third</a>
       </p>`
    );
    const modal = new Modal(container);
    wireModalEvents(modal, 'focusTrap');
    log('focus', 'Opened focus-trap modal — Tab cycling is trapped inside');
  },
};

document.querySelectorAll<HTMLButtonElement>('[data-focus]').forEach((btn) => {
  btn.addEventListener('click', () => focusActions[btn.dataset.focus!]?.());
});

/* ------------------------------------------------------------------------- *
 * 3. Compat upgrade-path demo
 * ------------------------------------------------------------------------- */

interface LegacyCtorLike {
  extend(instance: Record<string, unknown>, statics?: object): LegacyCtorLike;
  new (...args: unknown[]): unknown;
}

interface GarnishGlobalLike {
  Modal: LegacyCtorLike;
  [key: string]: unknown;
}

let compatGarnish: GarnishGlobalLike | null = null;
let DemoModalSubclass: LegacyCtorLike | null = null;

const compatActions: Record<string, () => void> = {
  install: () => {
    compatGarnish = installGarnishCompat() as unknown as GarnishGlobalLike;
    const onWindow =
      (window as unknown as {Garnish?: unknown}).Garnish !== undefined;
    log(
      'compat',
      `installGarnishCompat() ran. window.Garnish present: ${onWindow}; Garnish.Modal.extend is ${typeof compatGarnish.Modal.extend}`
    );
  },

  extend: () => {
    if (!compatGarnish) {
      compatActions.install!();
    }
    const Garnish = compatGarnish!;

    // Define the subclass exactly the legacy way: init() trampoline + this.base().
    DemoModalSubclass = Garnish.Modal.extend({
      init(this: {
        base: (...a: unknown[]) => unknown;
        $container: HTMLElement | null;
      }) {
        const container = buildModalContainer(
          'Legacy .extend() subclass',
          '<p>This modal was created via <code>Garnish.Modal.extend({ init, onShow })</code> and uses <code>this.base()</code> in <code>onShow</code>.</p>'
        );
        // Call the modern Modal constructor through the init trampoline.
        this.base(container, {autoShow: false});
        log('compat', 'subclass init() ran, called this.base(container)');
      },
      onShow(this: {base: () => void}) {
        // this.base() dispatches to Modal.prototype.onShow (fires 'show').
        this.base();
        log('compat', 'subclass onShow() ran, then called this.base()');
      },
    }) as LegacyCtorLike;

    const instance = new DemoModalSubclass() as {
      on(evt: string, fn: () => void): void;
      show(): void;
    };
    instance.on('show', () => log('compat', "subclass 'show' event observed"));
    instance.show();
    log('compat', 'Instantiated + show()ed the .extend() subclass');
  },
};

document.querySelectorAll<HTMLButtonElement>('[data-compat]').forEach((btn) => {
  btn.addEventListener('click', () => {
    try {
      compatActions[btn.dataset.compat!]?.();
    } catch (err) {
      log('compat', `Error: ${(err as Error).message}`, true);
    }
  });
});

/* ------------------------------------------------------------------------- *
 * 4. Draggable / resizable Modal demos
 * ------------------------------------------------------------------------- */

/** Build a modal container styled to look draggable, optionally with a header handle. */
function buildDragModalContainer(
  title: string,
  body: string,
  withHandle: boolean
): HTMLElement {
  const el = document.createElement('div');
  el.className = 'pg-modal pg-modal--draggable';
  const handleHtml = withHandle
    ? `<div class="pg-modal-drag-handle" data-drag-handle>${title} — drag here</div>`
    : `<h3>${title}</h3>`;
  el.innerHTML = `
    ${handleHtml}
    <div class="pg-modal-body">${body}</div>
    <div class="pg-modal-actions">
      <button type="button" class="pg-modal-primary" data-modal-close>Close (hide)</button>
    </div>
  `;
  document.body.appendChild(el);
  el.querySelector('[data-modal-close]')!.addEventListener('click', () => {
    const owner = Modal.instances.find((m) => m.$container === el);
    owner?.hide();
  });
  return el;
}

const dragModals: Modal[] = [];

function openDragModal(
  label: string,
  settings: Partial<ModalSettings>,
  body: string,
  withHandle = false
): void {
  const container = buildDragModalContainer(label, body, withHandle);
  const modal = new Modal(container, settings);
  wireModalEvents(modal, label);
  // The Modal creates its dragger/resizeDragger during construction; wire their
  // events so live drags/resizes appear in the log.
  if (modal.dragger) {
    wireDragEvents(modal.dragger, 'modal-drag', `${label} (move)`);
  }
  if (modal.resizeDragger) {
    wireDragEvents(modal.resizeDragger, 'modal-drag', `${label} (resize)`);
  }
  dragModals.push(modal);
  log('modal', `Opened "${label}" — drag/resize it; watch the log.`);
}

const dragModalActions: Record<string, () => void> = {
  container: () =>
    openDragModal(
      'Draggable modal',
      {draggable: true},
      '<p>Drag this modal from <strong>anywhere</strong> on its surface (the whole container is the handle).</p>'
    ),

  handle: () =>
    openDragModal(
      'Header-handle modal',
      {draggable: true, dragHandleSelector: '[data-drag-handle]'},
      '<p>This modal only moves when you drag the dark <strong>header bar</strong> (<code>dragHandleSelector</code>). Dragging the body does nothing.</p>',
      true
    ),

  resizable: () =>
    openDragModal(
      'Resizable modal',
      {resizable: true},
      '<p>Grab the resize handle in the bottom-right corner and drag — the modal grows/shrinks symmetrically about its center.</p>'
    ),

  both: () =>
    openDragModal(
      'Draggable + resizable',
      {
        draggable: true,
        dragHandleSelector: '[data-drag-handle]',
        resizable: true,
      },
      '<p>Drag the header to move; drag the corner handle to resize. Both draggers are independent.</p>',
      true
    ),

  destroy: () => {
    const count = dragModals.length;
    dragModals.splice(0).forEach((m) => m.destroy());
    log('modal', `Destroyed ${count} draggable/resizable modal(s)`);
  },
};

document
  .querySelectorAll<HTMLButtonElement>('[data-dragmodal]')
  .forEach((btn) => {
    btn.addEventListener('click', () => {
      try {
        dragModalActions[btn.dataset.dragmodal!]?.();
      } catch (err) {
        log('modal', `Error: ${(err as Error).message}`, true);
      }
    });
  });

/* ------------------------------------------------------------------------- *
 * 5. Standalone BaseDrag / DragMove demos
 * ------------------------------------------------------------------------- */

const dragArena = document.getElementById('drag-arena');

if (dragArena) {
  // Initial positions keyed by data-box, so "reset" can restore them.
  const boxHomes: Record<string, {left: number; top: number}> = {
    'move-1': {left: 16, top: 16},
    'move-2': {left: 170, top: 16},
    'move-x': {left: 16, top: 100},
    'base-1': {left: 170, top: 100},
  };

  const placeBox = (box: HTMLElement): void => {
    const home = boxHomes[box.dataset.box!];
    if (home) {
      box.style.left = `${home.left}px`;
      box.style.top = `${home.top}px`;
    }
  };

  const boxes = Array.from(
    dragArena.querySelectorAll<HTMLElement>('[data-box]')
  );
  boxes.forEach(placeBox);

  // Green boxes: DragMove drives left/top for us.
  const moveBoxes = dragArena.querySelectorAll<HTMLElement>(
    '.pg-drag-box--move:not(.pg-drag-box--xlock)'
  );
  moveBoxes.forEach((box) => {
    const dragger = new DragMove(box);
    wireDragEvents(dragger, 'drag', box.dataset.box!);
  });

  // X-axis-locked DragMove.
  const xBox = dragArena.querySelector<HTMLElement>('.pg-drag-box--xlock');
  if (xBox) {
    const dragger = new DragMove(xBox, {axis: 'x'});
    wireDragEvents(dragger, 'drag', `${xBox.dataset.box} (x-locked)`);
  }

  // Blue box: raw BaseDrag with an onDrag that positions it ourselves. We track
  // the box's start position and move it by the cursor delta (mouseDistX/Y) —
  // robust regardless of the box's offset parent (the arena is positioned).
  const baseBox = dragArena.querySelector<HTMLElement>('.pg-drag-box--base');
  if (baseBox) {
    let start = {left: 0, top: 0};
    const dragger: BaseDrag = new BaseDrag(baseBox, {
      onDragStart: () => {
        start = {left: baseBox.offsetLeft, top: baseBox.offsetTop};
      },
      onDrag: () => {
        baseBox.style.left = `${start.left + dragger.mouseDistX!}px`;
        baseBox.style.top = `${start.top + dragger.mouseDistY!}px`;
      },
    });
    wireDragEvents(dragger, 'drag', 'base-1 (manual onDrag)');
  }

  document
    .querySelector<HTMLButtonElement>('[data-dragbox="reset"]')
    ?.addEventListener('click', () => {
      boxes.forEach(placeBox);
      log('drag', 'Reset box positions');
    });
}

/* ------------------------------------------------------------------------- *
 * 6. Auto-scroll while dragging
 * ------------------------------------------------------------------------- */

const scrollContainer = document.getElementById('scroll-container');

if (scrollContainer) {
  const scrollBox = scrollContainer.querySelector<HTMLElement>(
    '.pg-drag-box--scroll'
  );
  if (scrollBox) {
    const dragger = new DragMove(scrollBox);
    wireDragEvents(dragger, 'autoscroll', 'scroll-box');

    document
      .querySelector<HTMLButtonElement>('[data-autoscroll="reset"]')
      ?.addEventListener('click', () => {
        scrollBox.style.left = '20px';
        scrollBox.style.top = '20px';
        scrollContainer.scrollTop = 0;
        log('autoscroll', 'Reset scroll item + scroll position');
      });
  }
}

/* ------------------------------------------------------------------------- *
 * 7. Drag with helpers (clones + return-to-source)
 * ------------------------------------------------------------------------- */

/**
 * Wire a Drag's lifecycle into the log. Like `wireDragEvents`, but also logs the
 * `returnHelpersToDraggees` event so the return/fade tail is observable. `drag`
 * is coalesced to one line per gesture.
 */
function wireHelperDragEvents(dragger: Drag, tag: string, label: string): void {
  let dragging = false;
  dragger.on('dragStart', () => {
    dragging = false;
    log(tag, `${label}: dragStart (helper clone created)`);
  });
  dragger.on('drag', () => {
    if (!dragging) {
      dragging = true;
      log(tag, `${label}: drag (helper trailing cursor…)`);
    }
  });
  dragger.on('dragStop', () => log(tag, `${label}: dragStop`));
  dragger.on('returnHelpersToDraggees', () =>
    log(tag, `${label}: returnHelpersToDraggees (helpers home)`)
  );
}

const dragHelperList = document.getElementById('drag-helper-list');

if (dragHelperList) {
  // Drop mode is shared across the items; the dragStop handler reads it to pick
  // return-to-source (default) vs. fadeOutHelpers.
  let dropMode: 'return' | 'fade' = 'return';

  const helperItems = Array.from(
    dragHelperList.querySelectorAll<HTMLElement>('[data-helper-item]')
  );

  helperItems.forEach((item) => {
    const dragger = new Drag(item, {
      // Hide the source while its helper clone is in flight, then reveal it
      // again when the helper returns.
      hideDraggee: true,
      helperOpacity: 0.92,
      onDragStop() {
        // Default Drag does NOT auto-return; the consumer decides. Mirror the
        // legacy contract: pick return-to-source or a fade-out on drop.
        if (dropMode === 'fade') {
          dragger.fadeOutHelpers();
          log('draghelper', `${item.dataset.helperItem}: fadeOutHelpers()`);
        } else {
          dragger.returnHelpersToDraggees();
        }
      },
    });
    wireHelperDragEvents(
      dragger,
      'draghelper',
      `item ${item.dataset.helperItem}`
    );
  });

  const dragHelperActions: Record<string, () => void> = {
    'mode-return': () => {
      dropMode = 'return';
      log('draghelper', 'Drop mode → return-to-source');
    },
    'mode-fade': () => {
      dropMode = 'fade';
      log('draghelper', 'Drop mode → fade out');
    },
  };

  document
    .querySelectorAll<HTMLButtonElement>('[data-draghelper]')
    .forEach((btn) => {
      btn.addEventListener('click', () => {
        try {
          dragHelperActions[btn.dataset.draghelper!]?.();
        } catch (err) {
          log('draghelper', `Error: ${(err as Error).message}`, true);
        }
      });
    });
}

/* ------------------------------------------------------------------------- *
 * 8. DragDrop — drop targets & hit detection
 * ------------------------------------------------------------------------- */

const dragDropChips = document.getElementById('dragdrop-chips');
const dragDropZones = document.getElementById('dragdrop-zones');

if (dragDropChips && dragDropZones) {
  const chips = Array.from(
    dragDropChips.querySelectorAll<HTMLElement>('[data-dragdrop-chip]')
  );
  const zones = Array.from(
    dragDropZones.querySelectorAll<HTMLElement>('[data-dropzone]')
  );

  // A single DragDrop owns all the chips; its dropTargets are the three zones.
  const dragDrop = new DragDrop({
    dropTargets: zones,
    hideDraggee: true,
    helperOpacity: 0.92,
    // Fires only when the active target changes (incl. → null). The class is
    // toggled by DragDrop itself; we just narrate it.
    onDropTargetChange(activeDropTarget) {
      const name = activeDropTarget?.dataset.dropzone ?? 'none';
      log('dragdrop', `dropTargetChange → ${name}`);
    },
    onDragStop() {
      // Legacy contract: there is no `drop` event — read $activeDropTarget here.
      const target = dragDrop.$activeDropTarget;
      if (target) {
        log(
          'dragdrop',
          `dropped on "${target.dataset.dropzone}" (read from $activeDropTarget)`
        );
        target.classList.add('pg-dropzone--hit');
        setTimeout(() => target.classList.remove('pg-dropzone--hit'), 600);
      } else {
        log('dragdrop', 'dropped outside any drop target');
      }
      // Return the chip's helper home so the chip reappears in place.
      dragDrop.returnHelpersToDraggees();
    },
  });
  dragDrop.addItems(chips);

  // Also surface the raw drag lifecycle.
  let dragging = false;
  dragDrop.on('dragStart', () => {
    dragging = false;
    log('dragdrop', 'dragStart');
  });
  dragDrop.on('drag', () => {
    if (!dragging) {
      dragging = true;
      log('dragdrop', 'drag (over zones…)');
    }
  });

  document
    .querySelector<HTMLButtonElement>('[data-dragdrop="reset"]')
    ?.addEventListener('click', () => {
      zones.forEach((z) => z.classList.remove('active', 'pg-dropzone--hit'));
      log('dragdrop', 'Reset chips + cleared zone highlights');
    });
}

/* ------------------------------------------------------------------------- *
 * 9. DragSort — reorderable list
 * ------------------------------------------------------------------------- */

const dragSortList = document.getElementById('dragsort-list');

if (dragSortList) {
  const items = Array.from(
    dragSortList.querySelectorAll<HTMLElement>('[data-sort-item]')
  );

  // Snapshot the original markup so "reset" can restore the order.
  const originalOrder = items.map((el) => el.dataset.sortItem!);

  /** Read the current visible order as a list of item ids. */
  const currentOrder = (): string =>
    Array.from(dragSortList.querySelectorAll<HTMLElement>('[data-sort-item]'))
      .map((el) => el.dataset.sortItem)
      .join(', ');

  const dragSort = new DragSort(items, {
    // The list itself is the heighted container the sort is constrained to.
    container: dragSortList,
    // Lock to the vertical axis — this is a single-column list.
    axis: 'y',
    // Hide the source row while its helper clone is in flight.
    hideDraggee: true,
    helperOpacity: 0.95,
    // A dashed placeholder element shown at the landing spot.
    insertion: () => {
      const ph = document.createElement('li');
      ph.className = 'pg-sort-insertion';
      return ph;
    },
    onInsertionPointChange() {
      log('dragsort', `insertionPointChange → order now [${currentOrder()}]`);
    },
    onSortChange() {
      log('dragsort', `sortChange → new order [${currentOrder()}]`);
    },
  });

  // Surface the raw drag lifecycle too (coalesce the per-frame `drag`).
  let dragging = false;
  dragSort.on('dragStart', () => {
    dragging = false;
    log('dragsort', 'dragStart');
  });
  dragSort.on('drag', () => {
    if (!dragging) {
      dragging = true;
      log('dragsort', 'drag (reordering…)');
    }
  });
  dragSort.on('dragStop', () => log('dragsort', 'dragStop'));

  document
    .querySelector<HTMLButtonElement>('[data-dragsort="reset"]')
    ?.addEventListener('click', () => {
      // Re-append the rows in their original order.
      for (const id of originalOrder) {
        const el = dragSortList.querySelector<HTMLElement>(
          `[data-sort-item="${id}"]`
        );
        if (el) dragSortList.appendChild(el);
      }
      log('dragsort', `Reset list order → [${currentOrder()}]`);
    });
}

/* ------------------------------------------------------------------------- *
 * 10. Events & utilities
 * ------------------------------------------------------------------------- */

const utilActions: Record<string, (btn: HTMLButtonElement) => void> = {
  hasattr: (btn) => {
    const result = hasAttr(btn, 'data-demo');
    log('util', `hasAttr(button, "data-demo") → ${result}`);
  },
  getdist: () => {
    const d = getDist(0, 0, 3, 4);
    log('util', `getDist(0, 0, 3, 4) → ${d}`);
  },
  activate: (btn) => {
    // Install the synthetic `activate` custom event on the button (idempotent),
    // then listen for it. Click / Space / Enter all dispatch `activate`.
    installActivate(btn);
    if (!btn.dataset.activateWired) {
      btn.addEventListener('activate', () => {
        log('util', 'activate custom event fired on the button');
      });
      btn.dataset.activateWired = 'yes';
      log(
        'util',
        'installActivate(button) — now click it or press Space/Enter'
      );
    }
  },
};

document.querySelectorAll<HTMLButtonElement>('[data-util]').forEach((btn) => {
  btn.addEventListener('click', () => utilActions[btn.dataset.util!]?.(btn));
});

/* ------------------------------------------------------------------------- *
 * 11. HUD — anchored popover
 * ------------------------------------------------------------------------- */

const hudArena = document.getElementById('hud-arena');

if (hudArena) {
  // One lazily-created HUD per trigger; recreated if it was destroyed.
  const huds = new Map<HTMLElement, HUD>();

  const hudBody = (label: string): string => `
    <div class="pg-hud-content">
      <h4>HUD: ${label}</h4>
      <p>
        Orientation is chosen from the clearance around this trigger. Scroll or
        resize the window to watch the HUD follow.
      </p>
      <button type="button" class="pg-modal-primary" data-hud-close>Close</button>
    </div>`;

  const getHud = (trigger: HTMLElement): HUD => {
    const existing = huds.get(trigger);
    if (existing && HUD.instances.includes(existing)) {
      return existing;
    }

    const id = trigger.dataset.hudTrigger!;
    const hud = new HUD(trigger, hudBody(id), {
      showOnInit: false,
      hudClass: 'hud pg-hud',
    });

    hud.on('show', () => log('hud', `${id}: show`));
    hud.on('hide', () => log('hud', `${id}: hide`));
    hud.on('updateSizeAndPosition', () =>
      log('hud', `${id}: positioned → orientation "${hud.orientation}"`)
    );

    // The close button lives inside the HUD body (this.$main).
    hud.$main
      ?.querySelector<HTMLButtonElement>('[data-hud-close]')
      ?.addEventListener('click', () => hud.hide());

    huds.set(trigger, hud);
    return hud;
  };

  hudArena
    .querySelectorAll<HTMLButtonElement>('[data-hud-trigger]')
    .forEach((trigger) => {
      trigger.addEventListener('click', () => getHud(trigger).toggle());
    });

  document
    .querySelector<HTMLButtonElement>('[data-hud="destroy"]')
    ?.addEventListener('click', () => {
      const count = HUD.instances.length;
      [...HUD.instances].forEach((h) => h.destroy());
      huds.clear();
      log('hud', `Destroyed ${count} HUD instance(s)`);
    });
}

/* ------------------------------------------------------------------------- *
 * 12. DisclosureMenu — dropdown menu
 * ------------------------------------------------------------------------- */

const discActionsTrigger = document.querySelector<HTMLButtonElement>(
  '[aria-controls="pg-disc-menu-actions"]'
);
const discFilterTrigger = document.querySelector<HTMLButtonElement>(
  '[aria-controls="pg-disc-menu-filter"]'
);

if (discActionsTrigger && discFilterTrigger) {
  /** Wire a menu's lifecycle events into the log. */
  const wireMenuEvents = (menu: DisclosureMenu, label: string): void => {
    for (const evt of ['beforeShow', 'show', 'hide'] as const) {
      menu.on(evt, () => log('disclosure', `${label}: ${evt}`));
    }
  };

  // Menu 1 — static markup. Log a selection when any item is activated.
  const actionsMenu = new DisclosureMenu(discActionsTrigger);
  wireMenuEvents(actionsMenu, 'actions');
  actionsMenu
    .$container!.querySelectorAll<HTMLButtonElement>('.menu-item:not(.disabled)')
    .forEach((item) => {
      installActivate(item);
      item.addEventListener('activate', () => {
        const label = item.querySelector('.menu-item-label')?.textContent ?? '?';
        log('disclosure', `actions: selected "${label}"`);
      });
    });

  // Menu 2 — items built via the API, with a live search input.
  const filterMenu = new DisclosureMenu(discFilterTrigger, {
    withSearchInput: true,
  });
  wireMenuEvents(filterMenu, 'filter');
  const fruits = [
    'Apple',
    'Apricot',
    'Banana',
    'Blueberry',
    'Cherry',
    'Grape',
    'Mango',
    'Orange',
    'Peach',
    'Pear',
  ];
  filterMenu.addItems(
    fruits.map((name) => ({
      label: name,
      onActivate: () => log('disclosure', `filter: selected "${name}"`),
    }))
  );

  document
    .querySelector<HTMLButtonElement>('[data-disclosure="destroy"]')
    ?.addEventListener('click', () => {
      const count = DisclosureMenu.instances.length;
      [...DisclosureMenu.instances].forEach((m) => m.destroy());
      log('disclosure', `Destroyed ${count} DisclosureMenu instance(s)`);
    });
}
