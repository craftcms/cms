import {beforeEach, describe, expect, it} from 'vite-plus/test';
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

describe('craft-button link semantics', () => {
  it('is a presentation host and not a tab stop in link mode', async () => {
    const element = await createButton({href: '/x'});
    expect(element.getAttribute('role')).toBe('presentation');
    expect(element.tabIndex).toBe(-1);
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
    expect(element.tabIndex).toBe(-1);
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
