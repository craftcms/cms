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
  BaseDrag,
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

  // Blue box: raw BaseDrag with an onDrag that positions it ourselves.
  const baseBox = dragArena.querySelector<HTMLElement>('.pg-drag-box--base');
  if (baseBox) {
    const dragger: BaseDrag = new BaseDrag(baseBox, {
      onDrag: () => {
        baseBox.style.left = `${dragger.mouseX! - dragger.mouseOffsetX!}px`;
        baseBox.style.top = `${dragger.mouseY! - dragger.mouseOffsetY!}px`;
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
 * 7. Events & utilities
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
