import {afterEach, describe, expect, it, vi, type Mock} from 'vitest';
import {
  registerPanel,
  shadeElement,
  stackedPanels,
  topStackedPanel,
  unregisterPanel,
  type StackedPanel,
} from './panel-stack';

interface FakePanel extends StackedPanel {
  position: Mock<(index: number, total: number) => void>;
  handleShadeClick: Mock<() => void>;
}

/**
 * A stand-in for either implementation — the stack only ever sees this much of
 * a panel, which is the point of the interface.
 */
function fakePanel(
  overrides: Pick<Partial<StackedPanel>, 'suppressShade'> = {}
): FakePanel {
  const element = document.createElement('div');
  document.body.appendChild(element);

  return {
    element,
    position: vi.fn<(index: number, total: number) => void>(),
    handleShadeClick: vi.fn<() => void>(),
    ...overrides,
  };
}

/** The `(index, total)` pairs a panel has been positioned at, most recent last. */
function positions(panel: FakePanel) {
  return panel.position.mock.calls;
}

afterEach(() => {
  for (const panel of [...stackedPanels()]) {
    unregisterPanel(panel);
  }

  document.body.innerHTML = '';
});

describe('registration', () => {
  it('stacks panels oldest first', () => {
    const first = fakePanel();
    const second = fakePanel();

    registerPanel(first);
    registerPanel(second);

    expect(stackedPanels()).toEqual([first, second]);
    expect(topStackedPanel()).toBe(second);
  });

  it('ignores a panel that is already registered', () => {
    const panel = fakePanel();

    registerPanel(panel);
    registerPanel(panel);

    expect(stackedPanels()).toHaveLength(1);
  });

  it('ignores an unregister for a panel it never had', () => {
    const panel = fakePanel();

    expect(() => unregisterPanel(panel)).not.toThrow();
    expect(stackedPanels()).toHaveLength(0);
  });
});

describe('positioning', () => {
  it('places a lone panel last of one', () => {
    const panel = fakePanel();

    registerPanel(panel);

    expect(positions(panel).at(-1)).toEqual([0, 1]);
  });

  /**
   * The whole reason the stack is shared: opening a second panel has to move
   * the first one, so it peeks out from behind rather than being covered.
   */
  it('repositions the panels already open when another arrives', () => {
    const first = fakePanel();
    const second = fakePanel();

    registerPanel(first);
    registerPanel(second);

    expect(positions(first).at(-1)).toEqual([0, 2]);
    expect(positions(second).at(-1)).toEqual([1, 2]);
  });

  it('repositions what is left when a panel closes', () => {
    const first = fakePanel();
    const second = fakePanel();

    registerPanel(first);
    registerPanel(second);
    unregisterPanel(second);

    expect(positions(first).at(-1)).toEqual([0, 1]);
  });

  /**
   * A closing panel sees itself off — it knows which way its own chrome
   * slides away, and the stack no longer knows about it.
   */
  it('does not reposition a panel it has just dropped', () => {
    const panel = fakePanel();

    registerPanel(panel);
    const before = positions(panel).length;
    unregisterPanel(panel);

    expect(positions(panel)).toHaveLength(before);
  });
});

describe('the shared shade', () => {
  it('shows while anything is open and hides once nothing is', () => {
    const panel = fakePanel();

    registerPanel(panel);
    expect(shadeElement().classList.contains('is-visible')).toBe(true);

    unregisterPanel(panel);
    expect(shadeElement().classList.contains('is-visible')).toBe(false);
  });

  it('stays up while a second panel is still open', () => {
    const first = fakePanel();
    const second = fakePanel();

    registerPanel(first);
    registerPanel(second);
    unregisterPanel(second);

    expect(shadeElement().classList.contains('is-visible')).toBe(true);
  });

  /** One shared listener, dispatched to the top — not one per panel. */
  it('dismisses only the topmost panel when clicked', () => {
    const first = fakePanel();
    const second = fakePanel();

    registerPanel(first);
    registerPanel(second);
    shadeElement().click();

    expect(second.handleShadeClick).toHaveBeenCalledOnce();
    expect(first.handleShadeClick).not.toHaveBeenCalled();
  });

  it('stays hidden for a panel that covers the viewport', () => {
    const sheet = fakePanel({suppressShade: () => true});

    registerPanel(sheet);

    expect(shadeElement().classList.contains('is-visible')).toBe(false);
  });

  it('shows anyway when another open panel wants it', () => {
    const sheet = fakePanel({suppressShade: () => true});
    const panel = fakePanel();

    registerPanel(sheet);
    registerPanel(panel);

    expect(shadeElement().classList.contains('is-visible')).toBe(true);
  });

  /**
   * The shade is left in the document between slideouts, so anything that
   * replaces the body's contents would otherwise strand it and leave every
   * later slideout unshaded.
   */
  it('comes back after the body is emptied underneath it', () => {
    const first = fakePanel();
    registerPanel(first);
    unregisterPanel(first);

    document.body.innerHTML = '';

    const second = fakePanel();
    registerPanel(second);

    expect(shadeElement().isConnected).toBe(true);
    expect(shadeElement().classList.contains('is-visible')).toBe(true);
  });
});

describe('the body scroll lock', () => {
  it('holds until the last panel closes', () => {
    const first = fakePanel();
    const second = fakePanel();

    registerPanel(first);
    registerPanel(second);
    expect(document.body.classList.contains('no-scroll')).toBe(true);

    unregisterPanel(second);
    expect(document.body.classList.contains('no-scroll')).toBe(true);

    unregisterPanel(first);
    expect(document.body.classList.contains('no-scroll')).toBe(false);
  });
});

/**
 * The point of the module. A `Slideout` and a `SlideoutPanel.vue` know nothing
 * about each other, but both register here, so each is positioned against the
 * other's panels and both answer to the same shade.
 */
describe('a mixed stack', () => {
  it('counts both implementations in every panel’s position', () => {
    const legacy = fakePanel();
    const vue = fakePanel();

    registerPanel(legacy);
    registerPanel(vue);

    expect(positions(legacy).at(-1)).toEqual([0, 2]);
    expect(positions(vue).at(-1)).toEqual([1, 2]);
  });

  it('sends a shade click to whichever went on top last', () => {
    const legacy = fakePanel();
    const vue = fakePanel();

    registerPanel(vue);
    registerPanel(legacy);
    shadeElement().click();

    expect(legacy.handleShadeClick).toHaveBeenCalledOnce();
    expect(vue.handleShadeClick).not.toHaveBeenCalled();
  });

  it('moves the remaining panels back when one closes out of order', () => {
    const legacy = fakePanel();
    const vue = fakePanel();
    const another = fakePanel();

    registerPanel(legacy);
    registerPanel(vue);
    registerPanel(another);

    // The Vue panel in the middle goes away — say its opener navigated.
    unregisterPanel(vue);

    expect(positions(legacy).at(-1)).toEqual([0, 2]);
    expect(positions(another).at(-1)).toEqual([1, 2]);
  });
});
