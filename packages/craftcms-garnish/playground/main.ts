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
 * 4. Events & utilities
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
