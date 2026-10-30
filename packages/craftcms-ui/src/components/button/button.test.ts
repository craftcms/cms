import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import type CraftButton from './button.js';
import './button.js';

async function createButton(
  attrs: Record<string, string> = {},
  text = 'Label'
): Promise<CraftButton> {
  const element = document.createElement('craft-button') as CraftButton;
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  element.textContent = text;
  document.body.append(element);
  await element.updateComplete;
  return element;
}

function anchor(element: CraftButton): HTMLAnchorElement | null {
  return element.shadowRoot?.querySelector('a.link') ?? null;
}

/** The inner content wrapper, which carries the nameless-button warning class. */
function content(element: CraftButton): HTMLElement | null {
  return element.shadowRoot?.querySelector('.button-content') ?? null;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

// Disconnects the last test's buttons, which cancels their pending feedback
// resets before the environment is torn down.
afterEach(() => {
  document.body.innerHTML = '';
});

describe('craft-button link mode', () => {
  it('renders no anchor when href is absent', async () => {
    const element = await createButton();
    expect(anchor(element)).toBeNull();
    expect(element.shadowRoot?.querySelector('.button-content')).not.toBeNull();
  });

  it('renders a real anchor wrapping the content when href is set', async () => {
    const element = await createButton({href: '/settings'});
    const a = anchor(element);
    expect(a).not.toBeNull();
    expect(a!.getAttribute('href')).toBe('/settings');
    expect(a!.querySelector('.button-content')).not.toBeNull();
  });

  it('forwards target and download to the anchor', async () => {
    const element = await createButton({
      href: '/file.zip',
      target: '_self',
      download: 'file.zip',
    });
    const a = anchor(element)!;
    expect(a.getAttribute('target')).toBe('_self');
    expect(a.getAttribute('download')).toBe('file.zip');
  });

  it('adds noopener to rel when target is _blank', async () => {
    const element = await createButton({href: '/x', target: '_blank'});
    expect(anchor(element)!.getAttribute('rel')).toContain('noopener');
  });

  it('preserves an explicit rel and still adds noopener for _blank', async () => {
    const element = await createButton({
      href: '/x',
      target: '_blank',
      rel: 'nofollow',
    });
    const rel = anchor(element)!.getAttribute('rel')!;
    expect(rel).toContain('nofollow');
    expect(rel).toContain('noopener');
  });

  it('does not set rel when target is not _blank and no rel given', async () => {
    const element = await createButton({href: '/x'});
    expect(anchor(element)!.hasAttribute('rel')).toBe(false);
  });

  it('does not flag an accessible-name error for a labeled link', async () => {
    const element = await createButton({href: '/x'}, 'Settings');
    // Wait for firstUpdated's async accessible-name computation.
    await element.updateComplete;
    expect(content(element)!.classList.contains('a11y-error')).toBe(false);
  });

  /** An icon-only button with nothing to read from is the case worth catching. */
  it('flags an accessible-name error for a nameless button', async () => {
    const element = await createButton({}, '');
    await element.updateComplete;
    await new Promise((resolve) => setTimeout(resolve));
    expect(content(element)!.classList.contains('a11y-error')).toBe(true);
  });
});

describe('craft-button shortcut', () => {
  function shortcut(element: CraftButton) {
    return element.shadowRoot?.querySelector('craft-shortcut') ?? null;
  }

  it('renders no shortcut without the attribute', async () => {
    const element = await createButton();
    expect(shortcut(element)).toBeNull();
  });

  it('shows a plain key, hidden from screen readers', async () => {
    const element = await createButton({shortcut: 'K'}, 'Search');
    expect(shortcut(element)?.textContent).toBe('K');
    expect(shortcut(element)?.getAttribute('aria-hidden')).toBe('true');
  });

  it('reads modifiers from a JSON shortcut', async () => {
    const element = await createButton(
      {shortcut: '{"key": "S", "alt": true, "shift": true}'},
      'Save'
    );
    expect(shortcut(element)?.textContent).toBe('S');
    expect(shortcut(element)?.alt).toBe(true);
    expect(shortcut(element)?.shift).toBe(true);
  });

  it('announces its shortcut with aria-keyshortcuts', async () => {
    vi.spyOn(navigator, 'platform', 'get').mockReturnValue('MacIntel');
    const element = await createButton(
      {shortcut: '{"key": "s", "shift": true}'},
      'Save'
    );
    expect(element.getAttribute('aria-keyshortcuts')).toBe('Meta+Shift+S');

    element.shortcut = null;
    await element.updateComplete;
    expect(element.hasAttribute('aria-keyshortcuts')).toBe(false);
    vi.restoreAllMocks();
  });

  it('keeps an aria-keyshortcuts its author set', async () => {
    const element = await createButton(
      {shortcut: 'S', 'aria-keyshortcuts': 'Control+Enter'},
      'Save'
    );
    expect(element.getAttribute('aria-keyshortcuts')).toBe('Control+Enter');
  });

  /** Vue's in-DOM compiler sets attribute values as string properties. */
  it('reads modifiers from JSON set as a property', async () => {
    const element = await createButton({}, 'Save');
    element.shortcut = '{"key": "S", "alt": true}';
    await element.updateComplete;
    expect(shortcut(element)?.textContent).toBe('S');
    expect(shortcut(element)?.alt).toBe(true);
  });
});

describe('craft-button accessible name check', () => {
  // A detached button's root is itself, which can't resolve an
  // `aria-labelledby` ID, so a check that ran after removal threw.
  it('skips the check for a button removed before it ran', async () => {
    const errors: unknown[] = [];
    const onRejection = (reason: unknown) => errors.push(reason);
    process.on('unhandledRejection', onRejection);

    const label = document.createElement('span');
    label.id = 'button-name';
    label.textContent = 'Settings';
    const element = document.createElement('craft-button') as CraftButton;
    element.setAttribute('aria-labelledby', 'button-name');
    document.body.append(label, element);
    element.remove();
    await element.updateComplete;
    await new Promise((resolve) => setTimeout(resolve));

    process.off('unhandledRejection', onRejection);
    expect(errors).toEqual([]);
  });
});

describe('craft-button link semantics', () => {
  it('is a presentation host and not a tab stop in link mode', async () => {
    const element = await createButton({href: '/x'});
    expect(element.getAttribute('role')).toBe('presentation');
    expect(element.hasAttribute('tabindex')).toBe(false);
    expect(element.type).toBe('button');
  });

  it('keeps Lion button semantics when there is no href', async () => {
    const element = await createButton();
    expect(element.getAttribute('role')).toBe('button');
    expect(element.tabIndex).toBe(0);
    // Defaults to "button" even though we extend LionButtonSubmit.
    expect(element.type).toBe('button');
  });

  it('honors an explicit type="submit"', async () => {
    const element = await createButton({type: 'submit'});
    expect(element.type).toBe('submit');
  });

  it('restores an explicit type="submit" when href is removed', async () => {
    const element = await createButton({type: 'submit', href: '/x'});
    expect(element.type).toBe('button');

    element.href = null;
    await element.updateComplete;

    expect(element.type).toBe('submit');
  });

  it('treats disabled+href as an inert button, not a link', async () => {
    const element = await createButton({href: '/x', disabled: ''});
    expect(anchor(element)).toBeNull();
    expect(element.getAttribute('aria-disabled')).toBe('true');
    expect(element.getAttribute('role')).toBe('button');
  });

  it('can remain focusable when disabled', async () => {
    const element = await createButton({
      disabled: '',
      'focusable-when-disabled': '',
    });

    expect(element.getAttribute('aria-disabled')).toBe('true');
    expect(element.tabIndex).toBe(0);
  });

  it('does not submit a form when a link-mode button is clicked', async () => {
    const form = document.createElement('form');
    const element = document.createElement('craft-button') as CraftButton;
    element.setAttribute('href', '/x');
    element.textContent = 'Go';
    form.append(element);
    document.body.append(form);
    await element.updateComplete;

    let submitted = false;
    form.addEventListener('submit', (e) => {
      submitted = true;
      e.preventDefault();
    });
    element.click();

    expect(submitted).toBe(false);
  });

  it('re-syncs host state when href is added after connect', async () => {
    const element = await createButton();
    expect(element.getAttribute('role')).toBe('button');

    element.href = '/later';
    await element.updateComplete;

    expect(element.getAttribute('role')).toBe('presentation');
    expect(element.hasAttribute('tabindex')).toBe(false);
  });

  it('leaves a disabled non-link button non-focusable (tabIndex -1)', async () => {
    const element = await createButton({disabled: ''});
    expect(element.tabIndex).toBe(-1);
  });

  it('keeps disabled+href non-focusable (tabIndex -1)', async () => {
    const element = await createButton({href: '/x', disabled: ''});
    expect(element.tabIndex).toBe(-1);
    expect(anchor(element)).toBeNull();
  });

  it('does not override an explicit type on a plain button', async () => {
    const element = await createButton({type: 'button'});
    expect(element.type).toBe('button');
  });

  it('restores button semantics when href is removed at runtime', async () => {
    const element = await createButton({href: '/x'});
    expect(element.getAttribute('role')).toBe('presentation');

    element.href = null;
    await element.updateComplete;

    expect(element.getAttribute('role')).toBe('button');
    expect(element.tabIndex).toBe(0);
  });
});

describe('craft-button actions', () => {
  it('parses a JSON action attribute into the action property', async () => {
    const element = await createButton({
      action: '{"type":"event","name":"craft:test"}',
    });
    expect(element.action).toEqual({type: 'event', name: 'craft:test'});
  });

  it('runs an event action on click, with trigger and sourceEvent in the detail', async () => {
    const element = await createButton({
      action:
        '{"type":"event","name":"craft:test-action","detail":{"foo":"bar"}}',
    });

    let detail: any = null;
    window.addEventListener(
      'craft:test-action',
      ((ev: CustomEvent) => {
        detail = ev.detail;
      }) as EventListener,
      {once: true}
    );
    element.click();

    expect(detail).not.toBeNull();
    expect(detail.foo).toBe('bar');
    expect(detail.trigger).toBe(element);
    expect(detail.sourceEvent).toBeInstanceOf(Event);
  });

  it('runs an action assigned as a raw JSON string property (Vue in-DOM compilation)', async () => {
    const element = await createButton();
    element.action = '{"type":"event","name":"craft:test-string"}';

    let fired = false;
    window.addEventListener(
      'craft:test-string',
      () => {
        fired = true;
      },
      {once: true}
    );
    element.click();

    expect(fired).toBe(true);
  });

  it('does not run the action when disabled', async () => {
    const element = await createButton({
      action: '{"type":"event","name":"craft:test-disabled"}',
      disabled: '',
    });

    let fired = false;
    window.addEventListener(
      'craft:test-disabled',
      () => {
        fired = true;
      },
      {once: true}
    );
    element.click();

    expect(fired).toBe(false);
  });

  it('stops running the action once it is removed', async () => {
    const element = await createButton({
      action: '{"type":"event","name":"craft:test-removed"}',
    });
    element.removeAttribute('action');
    element.action = null;
    await element.updateComplete;

    let fired = false;
    window.addEventListener(
      'craft:test-removed',
      () => {
        fired = true;
      },
      {once: true}
    );
    element.click();

    expect(fired).toBe(false);
  });
});

describe('craft-button action feedback', () => {
  it('cancels its pending reset to idle once it is removed', async () => {
    vi.useFakeTimers();

    try {
      const element = await createButton({
        action: '{"type":"event","name":"craft:test-feedback"}',
      });
      const states: string[] = [];
      element.addEventListener('craft-state-change', (event) => {
        states.push((event as CustomEvent<{state: string}>).detail.state);
      });

      await element.triggerAction();
      element.remove();
      vi.advanceTimersByTime(element.feedbackDuration * 2);

      expect(states).toEqual(['success']);
    } finally {
      vi.useRealTimers();
    }
  });

  it('returns to idle without scheduling a reset when its action settles after removal', async () => {
    vi.useFakeTimers();

    try {
      const element = await createButton({
        action: '{"type":"event","name":"craft:test-feedback"}',
      });
      const states: string[] = [];
      element.addEventListener('craft-state-change', (event) => {
        states.push((event as CustomEvent<{state: string}>).detail.state);
      });

      const run = element.triggerAction();
      element.remove();
      await run;
      vi.advanceTimersByTime(element.feedbackDuration * 2);

      expect(states).toEqual(['success']);
      expect(element.actionState).toBe('idle');
    } finally {
      vi.useRealTimers();
    }
  });

  it('keeps a new run’s feedback for its full duration', async () => {
    vi.useFakeTimers();

    try {
      const element = await createButton({
        action: '{"type":"event","name":"craft:test-feedback"}',
      });

      await element.triggerAction();
      vi.advanceTimersByTime(element.feedbackDuration - 1);
      await element.triggerAction();
      vi.advanceTimersByTime(element.feedbackDuration - 1);

      expect(element.actionState).toBe('success');

      vi.advanceTimersByTime(1);

      expect(element.actionState).toBe('idle');
    } finally {
      vi.useRealTimers();
    }
  });
});

describe('craft-button toggle', () => {
  it('derives aria-pressed from active', async () => {
    const button = document.createElement('craft-button');
    button.toggle = true;
    document.body.append(button);
    await button.updateComplete;

    expect(button.getAttribute('aria-pressed')).toBe('false');

    button.active = true;
    await button.updateComplete;

    expect(button.getAttribute('aria-pressed')).toBe('true');
  });

  it('leaves aria-pressed alone on a button that is not a toggle', async () => {
    // craft-button-group sets aria-pressed on its children; overwriting it
    // would be worse than leaving it be.
    const button = document.createElement('craft-button');
    button.setAttribute('aria-pressed', 'true');
    document.body.append(button);
    await button.updateComplete;

    button.active = false;
    await button.updateComplete;

    expect(button.getAttribute('aria-pressed')).toBe('true');
  });

  it('reports the state being asked for without changing active itself', async () => {
    const button = document.createElement('craft-button');
    button.toggle = true;
    document.body.append(button);
    await button.updateComplete;

    let asked: boolean | undefined;
    button.addEventListener('craft-toggle', (event) => {
      asked = (event as CustomEvent<{active: boolean}>).detail.active;
    });

    button.click();
    await button.updateComplete;

    expect(asked).toBe(true);
    // The owner of `active` decides; the button does not move on its own.
    expect(button.active).toBe(false);
  });

  it('stays quiet when disabled', async () => {
    const button = document.createElement('craft-button');
    button.toggle = true;
    button.setAttribute('disabled', '');
    document.body.append(button);
    await button.updateComplete;

    let fired = false;
    button.addEventListener('craft-toggle', () => {
      fired = true;
    });
    button.click();

    expect(fired).toBe(false);
  });
});

describe('craft-button loading', () => {
  function liveRegion(element: CraftButton): string {
    return (
      element.shadowRoot?.querySelector('[data-live-region]')?.textContent ?? ''
    );
  }

  it('clears the loading announcement when loading ends', async () => {
    const button = await createButton({}, 'Reset password');

    button.loading = true;
    await button.updateComplete;

    expect(liveRegion(button)).toBe('Loading');

    button.loading = false;
    await button.updateComplete;

    expect(liveRegion(button)).toBe('');
  });

  it('keeps the loading announcement for as long as loading lasts', async () => {
    vi.useFakeTimers();

    try {
      const button = await createButton({}, 'Reset password');

      button.loading = true;
      await button.updateComplete;
      vi.advanceTimersByTime(10000);

      expect(liveRegion(button)).toBe('Loading');
    } finally {
      vi.useRealTimers();
    }
  });
});

describe('craft-button icon spacing', () => {
  function content(element: CraftButton): DOMTokenList {
    return element.shadowRoot!.querySelector('.button-content')!.classList;
  }

  it('spaces a prefix icon from the label', async () => {
    const element = await createButton({icon: 'pen'});

    expect(content(element)).toContain('button-content--spaced-prefix');
    expect(content(element)).not.toContain('button-content--spaced-suffix');
  });

  it('spaces a suffix icon from the label', async () => {
    const element = await createButton({
      icon: 'chevron-down',
      'icon-position': 'suffix',
    });

    expect(content(element)).toContain('button-content--spaced-suffix');
    expect(content(element)).not.toContain('button-content--spaced-prefix');
  });

  it('spaces slotted icons on either side', async () => {
    const element = await createButton();
    const prefix = document.createElement('craft-icon');
    prefix.slot = 'prefix';
    const suffix = document.createElement('craft-icon');
    suffix.slot = 'suffix';
    element.append(prefix, suffix);
    await new Promise((resolve) => setTimeout(resolve));
    await element.updateComplete;

    expect(content(element)).toContain('button-content--spaced-prefix');
    expect(content(element)).toContain('button-content--spaced-suffix');
  });

  it('adds no space to an icon-only button', async () => {
    const element = await createButton(
      {icon: 'xmark-large', 'aria-label': 'Close'},
      ''
    );

    expect(content(element)).not.toContain('button-content--spaced-prefix');
  });

  it('ignores whitespace-only content', async () => {
    const element = await createButton(
      {icon: 'xmark-large', 'aria-label': 'Close'},
      '\n  '
    );

    expect(content(element)).not.toContain('button-content--spaced-prefix');
  });

  it('follows the label as it comes and goes', async () => {
    const element = await createButton({icon: 'pen'}, '');
    expect(content(element)).not.toContain('button-content--spaced-prefix');

    element.textContent = 'Edit';
    await new Promise((resolve) => setTimeout(resolve));
    await element.updateComplete;
    expect(content(element)).toContain('button-content--spaced-prefix');

    element.firstChild!.textContent = '';
    await new Promise((resolve) => setTimeout(resolve));
    await element.updateComplete;
    expect(content(element)).not.toContain('button-content--spaced-prefix');
  });
});

describe('craft-button press state', () => {
  it('clears the press when a native drag ends instead of a mouseup', async () => {
    // A drag handle never gets the mouseup Lion waits for; without this it
    // stays `active`, looking selected, until the next click anywhere.
    const button = await createButton();

    button.dispatchEvent(new MouseEvent('mousedown', {bubbles: true}));
    expect(button.active).toBe(true);

    button.dispatchEvent(
      new DragEvent('dragend', {bubbles: true, composed: true})
    );
    expect(button.active).toBe(false);
  });

  it('leaves a later drag alone once the press has been released', async () => {
    const button = await createButton();

    button.dispatchEvent(new MouseEvent('mousedown', {bubbles: true}));
    document.dispatchEvent(new MouseEvent('mouseup'));

    // Set by an owner (a button group, say) after the press ended.
    button.active = true;
    document.dispatchEvent(new DragEvent('dragend'));

    expect(button.active).toBe(true);
  });
});
