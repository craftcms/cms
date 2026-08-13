import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {InputRange} from 'dom-input-range';
import type {AxiosResponse} from 'axios';
import {actionClient} from '@src/utilities/api/actionClient';
import type CraftTextExpander from './text-expander.js';
import type {
  TextExpanderOption,
  TextExpanderErrorDetail,
  TextExpanderSelectDetail,
  TextExpanderTriggers,
} from './text-expander.js';
import './text-expander.js';

type Target = HTMLInputElement | HTMLTextAreaElement;

async function createFixture(
  triggers: TextExpanderTriggers,
  target: Target = document.createElement('textarea')
): Promise<{expander: CraftTextExpander; target: Target}> {
  target.id = 'text-expander-target';
  document.body.append(target);

  const expander = document.createElement(
    'craft-text-expander'
  ) as CraftTextExpander;
  expander.for = target.id;
  expander.triggers = triggers;
  document.body.append(expander);
  await expander.updateComplete;

  return {expander, target};
}

function type(target: Target, value: string): void {
  target.focus();
  target.value = value;
  target.setSelectionRange(value.length, value.length);
  target.dispatchEvent(new InputEvent('input', {bubbles: true}));
}

function options(expander: CraftTextExpander): HTMLElement[] {
  return Array.from(expander.querySelectorAll('craft-option'));
}

beforeEach(() => {
  vi.restoreAllMocks();
  vi.useRealTimers();
  vi.spyOn(HTMLElement.prototype, 'offsetWidth', 'get').mockReturnValue(1);
  document.body.innerHTML = '';
});

describe('craft-text-expander', () => {
  it('positions the popup without imposing a minimum width', async () => {
    const getBoundingClientRect = vi.spyOn(
      InputRange.prototype,
      'getBoundingClientRect'
    );
    const {expander, target} = await createFixture({
      ':': {options: [{label: '😀', value: '😀'}]},
    });
    target.slot = 'input';

    type(target, ':');
    await expander.updateComplete;

    const popup = expander.shadowRoot!.querySelector<HTMLElement>(
      '.text-expander__popup'
    )!;

    expect(getComputedStyle(popup).minWidth).toBe('');
    expect(
      new InputRange(target).getStyleClone().element.parentElement?.slot
    ).toBe(target.slot);
    const positionCalls = getBoundingClientRect.mock.calls.length;
    new InputRange(target).getStyleClone().dispatchEvent(new Event('update'));
    expect(getBoundingClientRect.mock.calls.length).toBeGreaterThan(
      positionCalls
    );
    getBoundingClientRect.mockRestore();
  });

  it('binds by id and restores the target accessibility attributes', async () => {
    const input = document.createElement('input');
    input.setAttribute('aria-controls', 'existing');
    const {expander, target} = await createFixture(
      {'@': {options: [{label: 'Brad', value: '@brad'}]}},
      input
    );

    expect(target.getAttribute('role')).toBe('combobox');
    expect(target.getAttribute('aria-autocomplete')).toBe('list');
    expect(target.getAttribute('aria-controls')).toContain('existing');
    expect(target.getAttribute('aria-controls')).toContain(
      expander.querySelector('[role="listbox"]')!.id
    );

    expander.remove();
    await Promise.resolve();

    expect(target.getAttribute('role')).toBeNull();
    expect(target.getAttribute('aria-autocomplete')).toBeNull();
    expect(target.getAttribute('aria-controls')).toBe('existing');
  });

  it('binds when a native target replaces an upgrading custom element', async () => {
    const host = document.createElement('craft-markdown-field');
    host.id = 'late-target';
    document.body.append(host);

    const expander = document.createElement(
      'craft-text-expander'
    ) as CraftTextExpander;
    expander.for = host.id;
    expander.triggers = {
      '@': {options: [{label: 'Brad', value: '@brad'}]},
    };
    document.body.append(expander);
    await expander.updateComplete;

    host.id = `${host.id}-editor`;
    const target = document.createElement('textarea');
    target.id = 'late-target';
    host.append(target);
    await Promise.resolve();

    type(target, '@b');

    expect(options(expander)).toHaveLength(1);
  });

  it('preserves textarea semantics', async () => {
    const {target} = await createFixture({
      '@': {options: [{label: 'Brad', value: '@brad'}]},
    });

    expect(target).toBeInstanceOf(HTMLTextAreaElement);
    expect(target.getAttribute('role')).toBeNull();
    expect(target.getAttribute('aria-expanded')).toBeNull();
    expect(target.getAttribute('aria-autocomplete')).toBe('list');
  });

  it('matches static options anywhere in labels and keywords while preserving order and limit', async () => {
    const {expander, target} = await createFixture({
      '@': {
        limit: 2,
        options: [
          {label: 'Alpha', value: '@alpha'},
          {label: 'Bravo', value: '@bravo', keywords: ['scalpel']},
          {label: 'Alpine', value: '@alpine'},
        ],
      },
    });

    type(target, '@lp');

    expect(
      options(expander).map((option) => option.textContent?.trim())
    ).toEqual(['Alpha', 'Bravo']);
  });

  it('recognizes multiple triggers only at whitespace boundaries', async () => {
    const {expander, target} = await createFixture({
      '@': {options: [{label: 'Alias', value: '@alias'}]},
      '#': {options: [{label: 'Environment', value: '#ENVIRONMENT'}]},
    });

    type(target, 'mail@example');
    expect(options(expander)).toHaveLength(0);

    type(target, 'Use #ENV');
    expect(
      options(expander).map((option) => option.textContent?.trim())
    ).toEqual(['Environment']);
  });

  it('selects the first option with Enter and emits input before selection', async () => {
    const {expander, target} = await createFixture({
      '@': {
        options: [
          {label: 'Brad Bell', value: '@brad'},
          {label: 'Brandon Kelly', value: '@brandon'},
        ],
      },
    });
    const events: string[] = [];
    let detail: TextExpanderSelectDetail | null = null;
    target.addEventListener('input', () => events.push('input'));
    expander.addEventListener('craft-text-expander-select', (event) => {
      events.push('select');
      detail = event.detail;
    });

    type(target, 'Hello @br there');
    target.setSelectionRange(9, 9);
    target.dispatchEvent(new InputEvent('input', {bubbles: true}));
    await expander.updateComplete;
    const bubbledKeydown = vi.fn();
    document.addEventListener('keydown', bubbledKeydown);
    target.dispatchEvent(
      new KeyboardEvent('keydown', {key: 'Enter', bubbles: true})
    );
    document.removeEventListener('keydown', bubbledKeydown);

    expect(target.value).toBe('Hello @brad there');
    expect(events.slice(-2)).toEqual(['input', 'select']);
    expect(detail).toMatchObject({character: '@', query: 'br'});
    expect(bubbledKeydown).not.toHaveBeenCalled();
  });

  it('emits input for the final fallback replacement value', async () => {
    const {expander, target} = await createFixture({
      '@': {options: [{label: 'Brad', value: '@brad'}]},
    });
    const values: string[] = [];

    type(target, '@b');
    await expander.updateComplete;
    target.addEventListener('input', () => values.push(target.value));
    Object.defineProperty(document, 'execCommand', {
      configurable: true,
      value: () => {
        target.dispatchEvent(new InputEvent('input', {bubbles: true}));

        return false;
      },
    });
    try {
      target.dispatchEvent(
        new KeyboardEvent('keydown', {key: 'Enter', bubbles: true})
      );

      expect(target.value).toBe('@brad');
      expect(values).toEqual(['@b', '@brad']);
    } finally {
      Reflect.deleteProperty(document, 'execCommand');
    }
  });

  it('returns to the input at list boundaries and leaves Tab behavior untouched', async () => {
    const input = document.createElement('input');
    const {expander, target} = await createFixture(
      {
        '@': {
          options: [
            {label: 'Alpha', value: '@alpha'},
            {label: 'Alpine', value: '@alpine'},
          ],
        },
      },
      input
    );

    type(target, '@a');
    await expander.updateComplete;
    const firstActive = target.getAttribute('aria-activedescendant');
    expect(firstActive).toBe(options(expander)[0]!.id);
    const up = new KeyboardEvent('keydown', {
      key: 'ArrowUp',
      bubbles: true,
      cancelable: true,
    });
    target.dispatchEvent(up);
    expect(target.getAttribute('aria-activedescendant')).toBeNull();

    target.dispatchEvent(
      new KeyboardEvent('keydown', {key: 'ArrowDown', bubbles: true})
    );
    expect(target.getAttribute('aria-activedescendant')).toBe(firstActive);
    target.dispatchEvent(
      new KeyboardEvent('keydown', {key: 'ArrowDown', bubbles: true})
    );
    const secondActive = target.getAttribute('aria-activedescendant');
    expect(secondActive).toBe(options(expander)[1]!.id);
    target.dispatchEvent(
      new KeyboardEvent('keydown', {key: 'ArrowDown', bubbles: true})
    );
    expect(target.getAttribute('aria-activedescendant')).toBeNull();

    const tab = new KeyboardEvent('keydown', {
      key: 'Tab',
      bubbles: true,
      cancelable: true,
    });
    target.dispatchEvent(tab);
    expect(tab.defaultPrevented).toBe(false);
    expect(target.value).toBe('@a');
    expect(target.getAttribute('aria-expanded')).toBe('false');
    expect(options(expander)).toHaveLength(0);
  });

  it('selects rich options with a pointer without moving focus', async () => {
    const {expander, target} = await createFixture({
      '@': {
        options: [{label: 'Brad', value: '@brad', data: {online: true}}],
        renderOption(option) {
          const row = document.createElement('span');
          row.dataset.online = String(
            (option.data as {online: boolean}).online
          );
          row.textContent = option.label;
          return row;
        },
      },
    });

    type(target, '@b');
    await expander.updateComplete;
    const option = options(expander)[0]!;
    option.dispatchEvent(
      new PointerEvent('pointerdown', {bubbles: true, cancelable: true})
    );
    option.click();

    expect(target.value).toBe('@brad');
    expect(document.activeElement).toBe(target);
    expect(option.querySelector('[data-online="true"]')).not.toBeNull();
  });

  it('selects options with touch input', async () => {
    const {expander, target} = await createFixture({
      '@': {options: [{label: 'Brad', value: '@brad'}]},
    });

    type(target, '@b');
    await expander.updateComplete;
    const option = options(expander)[0]!;
    expect(
      option.dispatchEvent(
        new PointerEvent('pointerdown', {
          bubbles: true,
          cancelable: true,
          pointerType: 'touch',
        })
      )
    ).toBe(false);
    option.dispatchEvent(
      new PointerEvent('pointerup', {bubbles: true, pointerType: 'touch'})
    );

    expect(target.value).toBe('@brad');
  });

  it('debounces async sources and discards stale results', async () => {
    vi.useFakeTimers();
    let resolveFirst!: (
      response: AxiosResponse<readonly TextExpanderOption[]>
    ) => void;
    let resolveSecond!: (
      response: AxiosResponse<readonly TextExpanderOption[]>
    ) => void;
    const firstRequest = new Promise<
      AxiosResponse<readonly TextExpanderOption[]>
    >((resolve) => {
      resolveFirst = resolve;
    });
    const secondRequest = new Promise<
      AxiosResponse<readonly TextExpanderOption[]>
    >((resolve) => {
      resolveSecond = resolve;
    });
    const request = vi
      .spyOn(actionClient, 'get')
      .mockReturnValueOnce(firstRequest)
      .mockReturnValueOnce(secondRequest);
    const {expander, target} = await createFixture({
      '#': {source: 'https://craft.test/actions/text-expander/options'},
    });

    type(target, '#a');
    await expander.updateComplete;
    expect(expander.shadowRoot?.textContent).toContain('Loading');
    expect(request).not.toHaveBeenCalled();
    await vi.advanceTimersByTimeAsync(150);
    expect(request).toHaveBeenCalledWith(
      'https://craft.test/actions/text-expander/options',
      {
        params: {query: '#a', limit: 8},
        signal: expect.any(AbortSignal),
      }
    );
    const firstSignal = request.mock.calls[0]![1]!.signal!;

    type(target, '#ab');
    expect(firstSignal.aborted).toBe(true);
    await vi.advanceTimersByTimeAsync(150);
    resolveFirst({
      data: [{label: 'Stale', value: '#STALE'}],
    } as AxiosResponse<readonly TextExpanderOption[]>);
    resolveSecond({
      data: [{label: 'Current', value: '#CURRENT'}],
    } as AxiosResponse<readonly TextExpanderOption[]>);
    await Promise.resolve();
    await expander.updateComplete;

    expect(
      options(expander).map((option) => option.textContent?.trim())
    ).toEqual(['Current']);
  });

  it('dispatches provider failures and ignores aborted requests', async () => {
    vi.useFakeTimers();
    const failure = new Error('Unavailable');
    vi.spyOn(actionClient, 'get').mockRejectedValue(failure);
    const {expander, target} = await createFixture({
      '#': {source: 'text-expander/options'},
    });
    let detail: TextExpanderErrorDetail | null = null;
    expander.addEventListener('craft-text-expander-error', (event) => {
      detail = event.detail;
    });

    type(target, '#env');
    await vi.advanceTimersByTimeAsync(150);
    await Promise.resolve();

    expect(detail).toEqual({character: '#', query: 'env', error: failure});
    expect(options(expander)).toHaveLength(0);
  });

  it('does not activate during composition or over a selection', async () => {
    const {expander, target} = await createFixture({
      '@': {options: [{label: 'Brad', value: '@brad'}]},
    });

    target.focus();
    target.dispatchEvent(new CompositionEvent('compositionstart'));
    target.value = '@b';
    target.setSelectionRange(2, 2);
    target.dispatchEvent(new InputEvent('input', {bubbles: true}));
    expect(options(expander)).toHaveLength(0);

    target.dispatchEvent(new CompositionEvent('compositionend'));
    expect(options(expander)).toHaveLength(1);

    target.setSelectionRange(0, 2);
    target.dispatchEvent(new InputEvent('input', {bubbles: true}));
    expect(options(expander)).toHaveLength(0);
  });

  it('accepts static trigger configuration from JSON', async () => {
    const target = document.createElement('textarea');
    target.id = 'json-target';
    document.body.append(target);
    const expander = document.createElement(
      'craft-text-expander'
    ) as CraftTextExpander;
    expander.setAttribute('for', target.id);
    expander.setAttribute(
      'triggers',
      JSON.stringify({
        '@': {options: [{label: 'Brad', value: '@brad'}]},
      })
    );
    document.body.append(expander);
    await expander.updateComplete;

    type(target, '@b');

    expect(options(expander)).toHaveLength(1);
  });
});
