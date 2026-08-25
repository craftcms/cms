import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import type CraftElementSelectorModal from './element-selector-modal.js';
import './element-selector-modal.js';
import type {
  ElementSelectorController,
  ElementSelectorState,
} from '@src/core/element-selector/index.js';

if (typeof HTMLDialogElement !== 'undefined') {
  HTMLDialogElement.prototype.showModal ??= function (this: HTMLDialogElement) {
    this.setAttribute('open', '');
  };
  HTMLDialogElement.prototype.show ??= function (this: HTMLDialogElement) {
    this.setAttribute('open', '');
  };
  HTMLDialogElement.prototype.close ??= function (this: HTMLDialogElement) {
    this.removeAttribute('open');
  };
}

async function createModal(
  configure: (modal: CraftElementSelectorModal) => void = () => {}
): Promise<CraftElementSelectorModal> {
  const modal = document.createElement(
    'craft-element-selector-modal'
  ) as CraftElementSelectorModal;
  const index = document.createElement('div');
  index.className = 'stub-index';
  index.textContent = 'Index';
  modal.append(index);
  configure(modal);
  document.body.append(modal);
  await modal.updateComplete;
  return modal;
}

function shadow<E extends Element = HTMLElement>(
  modal: CraftElementSelectorModal,
  selector: string
): E | null {
  return modal.shadowRoot!.querySelector<E>(selector);
}

const button = (modal: CraftElementSelectorModal, part: 'cancel' | 'select') =>
  shadow<HTMLElement>(modal, `[part="${part}"]`)!;

function state(
  overrides: Partial<ElementSelectorState> = {}
): ElementSelectorState {
  return {
    open: true,
    loading: false,
    busy: false,
    selection: [],
    disabledElementIds: [],
    indexBody: {html: '', props: {}},
    error: null,
    title: 'Choose an entry',
    showTitle: true,
    selectLabel: 'Select',
    canSubmit: false,
    canCancel: true,
    ...overrides,
  };
}

/**
 * A hand-rolled stand-in rather than a real controller: this file is about the
 * chrome, and the controller has its own suite on the node environment.
 */
function fakeController(initial = state()) {
  const listeners = new Set<(s: ElementSelectorState) => void>();
  let current = initial;

  return {
    submit: vi.fn(),
    cancel: vi.fn(),
    get state() {
      return current;
    },
    on(_event: string, listener: (s: ElementSelectorState) => void) {
      listeners.add(listener);
      return () => listeners.delete(listener);
    },
    push(next: Partial<ElementSelectorState>) {
      current = {...current, ...next};
      listeners.forEach((listener) => listener(current));
    },
  };
}

const bind = (
  modal: CraftElementSelectorModal,
  controller: ReturnType<typeof fakeController>
) => {
  modal.controller = controller as unknown as ElementSelectorController;
};

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('chrome', () => {
  it('renders a heading, a body slot and a footer', async () => {
    const modal = await createModal((m) => {
      m.label = 'Choose';
      m.showTitle = true;
    });

    expect(shadow(modal, '[part="title"]')!.textContent!.trim()).toBe('Choose');
    expect(shadow(modal, '.body slot')).not.toBeNull();
    expect(shadow(modal, '[part="footer"]')).not.toBeNull();
  });

  it('keeps the heading in the a11y tree when the title is hidden', async () => {
    // The dialog is labelled by it, so it must not be removed — only hidden.
    const modal = await createModal((m) => {
      m.label = 'Choose';
    });
    const title = shadow(modal, '[part="title"]')!;

    expect(modal.showTitle).toBe(false);
    expect(title.classList.contains('title--hidden')).toBe(true);
    expect(shadow(modal, 'dialog')!.getAttribute('aria-labelledby')).toBe(
      title.id
    );
  });

  it('projects the index without relocating it', async () => {
    const modal = await createModal();
    const index = modal.querySelector('.stub-index')!;
    const slot = shadow<HTMLSlotElement>(modal, '.body slot')!;

    expect(index.parentElement).toBe(modal);
    expect(slot.assignedNodes({flatten: true})).toContain(index);
  });

  it('has a close button that dismisses it', async () => {
    const modal = await createModal((m) => m.setAttribute('open', ''));
    const seen = vi.fn();
    modal.addEventListener('craft-cancel', seen);

    shadow<HTMLButtonElement>(modal, '[part="close"]')!.click();
    await modal.updateComplete;

    expect(seen).toHaveBeenCalledTimes(1);
    expect(modal.opened).toBe(false);
  });

  it('keeps the close button when the title is hidden', async () => {
    // The heading goes out of flow, but the button still needs to be reachable.
    const modal = await createModal();

    expect(modal.showTitle).toBe(false);
    expect(shadow(modal, '[part="close"]')).not.toBeNull();
  });

  it('refuses a close click while busy', async () => {
    const modal = await createModal((m) => {
      m.setAttribute('open', '');
      m.busy = true;
    });
    const seen = vi.fn();
    modal.addEventListener('craft-cancel', seen);

    shadow<HTMLButtonElement>(modal, '[part="close"]')!.click();
    await modal.updateComplete;

    expect(seen).not.toHaveBeenCalled();
    expect(modal.opened).toBe(true);
  });

  it('exposes the footer slots', async () => {
    const modal = await createModal();

    expect(shadow(modal, 'slot[name="secondary-actions"]')).not.toBeNull();
    expect(shadow(modal, 'slot[name="primary-actions"]')).not.toBeNull();
  });

  it('shows a spinner while loading with no index yet', async () => {
    const modal = await createModal((m) => {
      m.loading = true;
    });

    expect(shadow(modal, '.loading craft-spinner')).not.toBeNull();
  });
});

describe('unbound (attribute-driven)', () => {
  it('disables Select until can-submit is set', async () => {
    const modal = await createModal();
    expect(button(modal, 'select').hasAttribute('disabled')).toBe(true);

    modal.canSubmit = true;
    await modal.updateComplete;

    expect(button(modal, 'select').hasAttribute('disabled')).toBe(false);
  });

  it('uses the given labels, falling back to defaults', async () => {
    const modal = await createModal();
    expect(button(modal, 'select').textContent!.trim()).toBeTruthy();

    modal.selectLabel = 'Choose it';
    modal.cancelLabel = 'Never mind';
    await modal.updateComplete;

    expect(button(modal, 'select').textContent!.trim()).toBe('Choose it');
    expect(button(modal, 'cancel').textContent!.trim()).toBe('Never mind');
  });

  it('emits craft-select when Select is clicked', async () => {
    const modal = await createModal((m) => {
      m.canSubmit = true;
    });
    const seen = vi.fn();
    modal.addEventListener('craft-select', seen);

    button(modal, 'select').click();

    expect(seen).toHaveBeenCalledTimes(1);
  });

  it('ignores a Select click while it cannot submit', async () => {
    const modal = await createModal();
    const seen = vi.fn();
    modal.addEventListener('craft-select', seen);

    button(modal, 'select').click();

    expect(seen).not.toHaveBeenCalled();
  });

  it('emits craft-cancel and closes when Cancel is clicked', async () => {
    const modal = await createModal((m) => m.setAttribute('open', ''));
    const seen = vi.fn();
    modal.addEventListener('craft-cancel', seen);

    button(modal, 'cancel').click();
    await modal.updateComplete;

    expect(seen).toHaveBeenCalledTimes(1);
    expect(modal.opened).toBe(false);
  });
});

describe('busy', () => {
  it('disables both buttons and marks the index inert', async () => {
    const modal = await createModal((m) => {
      m.canSubmit = true;
      m.busy = true;
    });

    expect(button(modal, 'cancel').hasAttribute('disabled')).toBe(true);
    expect(button(modal, 'select').hasAttribute('loading')).toBe(true);
    expect(shadow(modal, '.body')!.hasAttribute('inert')).toBe(true);
    expect(
      shadow(modal, '.footer__group--secondary')!.hasAttribute('inert')
    ).toBe(true);
  });

  it('reflects busy to an attribute for styling', async () => {
    const modal = await createModal((m) => {
      m.busy = true;
    });

    expect(modal.hasAttribute('busy')).toBe(true);
  });

  it('refuses a Cancel click', async () => {
    const modal = await createModal((m) => {
      m.setAttribute('open', '');
      m.busy = true;
    });
    const seen = vi.fn();
    modal.addEventListener('craft-cancel', seen);

    button(modal, 'cancel').click();
    await modal.updateComplete;

    expect(seen).not.toHaveBeenCalled();
    expect(modal.opened).toBe(true);
  });
});

describe('bound to a controller', () => {
  it('takes its state from the controller', async () => {
    const controller = fakeController();
    const modal = await createModal((m) => bind(m, controller));
    await modal.updateComplete;

    expect(modal.opened).toBe(true);
    expect(modal.label).toBe('Choose an entry');
    expect(modal.showTitle).toBe(true);
    expect(button(modal, 'select').hasAttribute('disabled')).toBe(true);
  });

  it('re-renders when the controller emits change', async () => {
    const controller = fakeController();
    const modal = await createModal((m) => bind(m, controller));
    await modal.updateComplete;

    controller.push({canSubmit: true, selectLabel: 'Add'});
    await modal.updateComplete;

    expect(modal.canSubmit).toBe(true);
    expect(button(modal, 'select').textContent!.trim()).toBe('Add');
  });

  it('lets the controller win over locally-set properties', async () => {
    // One direction only: state flows out of the controller, never into it.
    const controller = fakeController();
    const modal = await createModal((m) => bind(m, controller));

    modal.canSubmit = true;
    await modal.updateComplete;

    expect(modal.canSubmit).toBe(false);
  });

  it('routes Select to the controller', async () => {
    const controller = fakeController(state({canSubmit: true}));
    const modal = await createModal((m) => bind(m, controller));
    await modal.updateComplete;

    button(modal, 'select').click();

    expect(controller.submit).toHaveBeenCalledTimes(1);
  });

  it('routes Cancel to the controller rather than closing itself', async () => {
    const controller = fakeController();
    const modal = await createModal((m) => bind(m, controller));
    await modal.updateComplete;

    button(modal, 'cancel').click();
    await modal.updateComplete;

    expect(controller.cancel).toHaveBeenCalledTimes(1);
    // The controller still reports open, so the modal stays open — that is what
    // lets it refuse a dismissal mid-save.
    expect(modal.opened).toBe(true);
  });

  it('closes when the controller says it has closed', async () => {
    const controller = fakeController();
    const modal = await createModal((m) => bind(m, controller));
    await modal.updateComplete;

    controller.push({open: false});
    await modal.updateComplete;

    expect(modal.opened).toBe(false);
  });

  it('unsubscribes from the previous controller when rebound', async () => {
    const first = fakeController();
    const second = fakeController(state({title: 'Second'}));
    const modal = await createModal((m) => bind(m, first));
    await modal.updateComplete;

    bind(modal, second);
    await modal.updateComplete;
    expect(modal.label).toBe('Second');

    first.push({title: 'Stale'});
    await modal.updateComplete;

    expect(modal.label).toBe('Second');
  });

  it('stops listening once disconnected', async () => {
    const controller = fakeController();
    const modal = await createModal((m) => bind(m, controller));
    await modal.updateComplete;

    modal.remove();
    controller.push({title: 'After removal'});
    await modal.updateComplete;

    expect(modal.label).toBe('Choose an entry');
  });
});
