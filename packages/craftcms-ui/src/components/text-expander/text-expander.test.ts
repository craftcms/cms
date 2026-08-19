import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {InputRange} from 'dom-input-range';
import type {AxiosResponse} from 'axios';
import {actionClient} from '@src/utilities/api/actionClient';
import type CraftPopover from '../popover/popover.js';
import type CraftTextExpander from './text-expander.js';
import type {
  TextExpanderOption,
  TextExpanderErrorDetail,
  TextExpanderSelectDetail,
  TextExpanderTrigger,
  TextExpanderTriggerBoundary,
  TextExpanderTriggers,
} from './text-expander.js';
import './text-expander.js';

type Target = HTMLInputElement | HTMLTextAreaElement;
type OptionalBoundary<T> = T extends unknown
  ? Omit<T, 'trigger' | 'boundary'> & {
      boundary?: TextExpanderTriggerBoundary;
    }
  : never;
type FixtureTrigger = OptionalBoundary<TextExpanderTrigger>;
type FixtureTriggerMap = Record<
  string,
  FixtureTrigger | readonly FixtureTrigger[]
>;
type FixtureTriggers = FixtureTriggerMap | TextExpanderTriggers;

async function createFixture(
  triggers: FixtureTriggers,
  target: Target = document.createElement('textarea')
): Promise<{expander: CraftTextExpander; target: Target}> {
  target.id = 'text-expander-target';
  document.body.append(target);

  const expander = document.createElement(
    'craft-text-expander'
  ) as CraftTextExpander;
  expander.for = target.id;
  expander.triggers = Array.isArray(triggers)
    ? triggers
    : Object.entries(triggers).flatMap(([trigger, configuration]) =>
        (Array.isArray(configuration) ? configuration : [configuration]).map(
          (configuration) => ({
            trigger,
            boundary: 'whitespace',
            ...configuration,
          })
        )
      );
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

function popover(expander: CraftTextExpander): CraftPopover {
  return expander.shadowRoot!.querySelector('craft-popover')!;
}

function dialog(expander: CraftTextExpander): HTMLDialogElement | null {
  return popover(expander).shadowRoot!.querySelector('dialog');
}

async function waitForFirstOption(expander: CraftTextExpander): Promise<void> {
  await vi.waitFor(() => {
    expect(options(expander)[0]?.getAttribute('aria-selected')).toBe('true');
  });
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
    await vi.waitFor(() =>
      expect(
        new InputRange(target).getStyleClone().element.parentElement?.slot
      ).toBe(target.slot)
    );
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

    expect(target.getAttribute('role')).toBeNull();
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
    expander.triggers = [
      {
        trigger: '@',
        boundary: 'whitespace',
        options: [{label: 'Brad', value: '@brad'}],
      },
    ];
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

  it('rebinds when the native target is replaced', async () => {
    const {expander, target} = await createFixture({
      '@': {options: [{label: 'Brad', value: '@brad'}]},
    });
    const replacement = document.createElement('textarea');
    replacement.id = target.id;
    target.replaceWith(replacement);
    await Promise.resolve();

    type(replacement, '@b');

    expect(options(expander)).toHaveLength(1);
  });

  it('reuses its overlay when reconnected', async () => {
    const {expander, target} = await createFixture({
      '@': {options: [{label: 'Brad', value: '@brad'}]},
    });

    expander.remove();
    document.body.append(expander);
    await Promise.resolve();
    type(target, '@b');

    expect(
      popover(expander).shadowRoot?.querySelectorAll('dialog')
    ).toHaveLength(1);
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

  it('shows all static options when no limit is configured', async () => {
    const {expander, target} = await createFixture({
      '@': {
        options: Array.from({length: 9}, (_, index) => ({
          label: `Option ${index + 1}`,
          value: `@option-${index + 1}`,
        })),
      },
    });

    type(target, '@');

    expect(options(expander)).toHaveLength(9);
  });

  it('matches static options anywhere in labels and keywords while preserving order and an explicit limit', async () => {
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

  it('keeps dotted queries active', async () => {
    const {expander, target} = await createFixture({
      '{': {
        boundary: 'anywhere',
        options: [
          {
            label: 'Author Username',
            value: '{author.username}',
            keywords: ['author.username'],
          },
        ],
      },
    });

    type(target, '{author.user');

    expect(options(expander)[0]?.textContent?.trim()).toBe('Author Username');
  });

  it('does not rebuild options when selectionchange keeps the same caret', async () => {
    const {expander, target} = await createFixture({
      '@': {options: [{label: 'Brad', value: '@brad'}]},
    });

    type(target, '@b');
    const firstOption = options(expander)[0];
    document.dispatchEvent(new Event('selectionchange'));
    await Promise.resolve();

    expect(options(expander)[0]).toBe(firstOption);
  });

  it('honors each trigger boundary', async () => {
    const {expander, target} = await createFixture([
      {
        trigger: '@',
        boundary: 'whitespace',
        options: [{label: 'Alias', value: '@alias'}],
      },
      {
        trigger: '$',
        boundary: 'start',
        options: [{label: 'Direct', value: '$DIRECT'}],
      },
      {
        trigger: '$',
        boundary: 'anywhere',
        options: [
          {label: 'Embedded Direct', value: '${DIRECT}'},
          {label: 'Nested', value: '${NESTED}'},
        ],
      },
    ]);

    type(target, 'mail@example');
    expect(options(expander)).toHaveLength(0);

    type(target, 'Use @ali');
    expect(
      options(expander).map((option) => option.textContent?.trim())
    ).toEqual(['Alias']);

    type(target, 'Use $DIR');
    expect(options(expander)[0]?.textContent?.trim()).toBe('Embedded Direct');

    type(target, '$DIR');
    expect(options(expander)[0]?.textContent?.trim()).toBe('Direct');

    type(target, 'https://$NES');
    expect(options(expander)[0]?.textContent?.trim()).toBe('Nested');
  });

  it('renders option hints and includes them in accessible names', async () => {
    const {expander, target} = await createFixture({
      $: {
        boundary: 'start',
        options: [
          {
            label: '$PRIMARY_SITE_URL',
            value: '$PRIMARY_SITE_URL',
            data: {hint: 'https://example.com'},
          },
        ],
      },
    });

    type(target, '$PRIMARY');
    const option = options(expander)[0] as HTMLElement & {hint: string};

    expect(option.hint).toBe('https://example.com');
    expect(option.getAttribute('aria-label')).toBe(
      '$PRIMARY_SITE_URL, https://example.com'
    );
  });

  it.each([
    ['$PRIMARY', '$PRIMARY_SITE_URL'],
    ['Prefix $PRIMARY', 'Prefix ${PRIMARY_SITE_URL}'],
  ])('uses the first matching variant for %s', async (value, expectedValue) => {
    const {expander, target} = await createFixture([
      {
        trigger: '$',
        boundary: 'start',
        options: [{label: '$PRIMARY_SITE_URL', value: '$PRIMARY_SITE_URL'}],
      },
      {
        trigger: '$',
        boundary: 'anywhere',
        options: [{label: '$PRIMARY_SITE_URL', value: '${PRIMARY_SITE_URL}'}],
      },
    ]);

    type(target, value);
    await waitForFirstOption(expander);
    target.dispatchEvent(
      new KeyboardEvent('keydown', {key: 'Enter', bubbles: true})
    );

    expect(target.value).toBe(expectedValue);
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
    await waitForFirstOption(expander);
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

  it('selects the first option after the popup closes and reopens', async () => {
    vi.spyOn(HTMLElement.prototype, 'offsetWidth', 'get').mockImplementation(
      function (this: HTMLElement) {
        const expander = this.closest('craft-text-expander');
        const dialog = expander
          ? popover(expander as CraftTextExpander).shadowRoot?.querySelector(
              'dialog'
            )
          : null;

        return dialog?.style.display === 'none' ? 0 : 1;
      }
    );
    const {expander, target} = await createFixture({
      '@': {options: [{label: 'Brad', value: '@brad'}]},
    });

    type(target, '@b');
    await vi.waitFor(() => {
      expect(options(expander)[0]?.getAttribute('aria-selected')).toBe('true');
    });
    type(target, '@z');
    await vi.waitFor(() => {
      expect(dialog(expander)?.style.display).toBe('none');
    });
    type(target, '@b');
    await vi.waitFor(() => {
      expect(dialog(expander)?.style.display).toBe('');
    });
    const enter = new KeyboardEvent('keydown', {
      key: 'Enter',
      bubbles: true,
      cancelable: true,
    });
    target.dispatchEvent(enter);

    expect(enter.defaultPrevented).toBe(true);
    expect(target.value).toBe('@brad');
  });

  it('closes the popup with Escape', async () => {
    const {expander, target} = await createFixture({
      '@': {options: [{label: 'Brad', value: '@brad'}]},
    });

    type(target, '@b');
    await waitForFirstOption(expander);
    target.dispatchEvent(
      new KeyboardEvent('keydown', {key: 'Escape', bubbles: true})
    );
    target.dispatchEvent(
      new KeyboardEvent('keyup', {key: 'Escape', bubbles: true})
    );

    await vi.waitFor(() => expect(options(expander)).toHaveLength(0));
    expect(popover(expander).opened).toBe(false);
  });

  it('closes the popup when clicking outside', async () => {
    const {expander, target} = await createFixture({
      '@': {options: [{label: 'Brad', value: '@brad'}]},
    });
    const outside = document.createElement('button');
    document.body.append(outside);

    type(target, '@b');
    await waitForFirstOption(expander);
    outside.dispatchEvent(new MouseEvent('mousedown', {bubbles: true}));
    outside.dispatchEvent(new MouseEvent('mouseup', {bubbles: true}));

    await vi.waitFor(() => expect(options(expander)).toHaveLength(0));
    expect(popover(expander).opened).toBe(false);
  });

  it('emits input for the final fallback replacement value', async () => {
    const {expander, target} = await createFixture({
      '@': {options: [{label: 'Brad', value: '@brad'}]},
    });
    const values: string[] = [];

    type(target, '@b');
    await waitForFirstOption(expander);
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

  it('honors maxlength when falling back to setRangeText', async () => {
    const target = document.createElement('textarea');
    target.maxLength = 3;
    const {expander} = await createFixture(
      {'@': {options: [{label: 'Brad', value: '@brad'}]}},
      target
    );

    type(target, '@b');
    await waitForFirstOption(expander);
    target.dispatchEvent(
      new KeyboardEvent('keydown', {key: 'Enter', bubbles: true})
    );

    expect(target.value).toBe('@br');
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
    await waitForFirstOption(expander);
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
    expect(popover(expander).opened).toBe(true);

    target.blur();
    await Promise.resolve();

    expect(popover(expander).opened).toBe(false);
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
    await waitForFirstOption(expander);
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
    await waitForFirstOption(expander);
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
        params: {query: '#a'},
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

  it('shows static options after canceling an async match', async () => {
    vi.useFakeTimers();
    vi.spyOn(actionClient, 'get').mockImplementation(
      () => new Promise(() => {})
    );
    const {expander, target} = await createFixture({
      ':': {options: [{label: 'Smile', value: '🙂'}]},
      '@': {source: 'text-expander/options'},
    });

    type(target, ':sm @br');
    await expander.updateComplete;
    target.setSelectionRange(3, 3);
    document.dispatchEvent(new Event('selectionchange'));
    await Promise.resolve();
    await expander.updateComplete;

    expect(
      options(expander).map((option) => option.textContent?.trim())
    ).toEqual(['Smile']);
    expect(
      expander.querySelector<HTMLElement>('[role="listbox"]')?.hidden
    ).toBe(false);
    await vi.waitFor(() => {
      expect(expander.shadowRoot?.querySelector('[part="loading"]')).toBeNull();
    });
  });

  it('reports malformed async responses and clears loading', async () => {
    vi.useFakeTimers();
    vi.spyOn(actionClient, 'get').mockResolvedValue({
      data: {},
    } as unknown as AxiosResponse<readonly TextExpanderOption[]>);
    const {expander, target} = await createFixture({
      '@': {source: 'text-expander/options'},
    });
    let detail: TextExpanderErrorDetail | null = null;
    expander.addEventListener('craft-text-expander-error', (event) => {
      detail = event.detail;
    });

    type(target, '@b');
    await vi.advanceTimersByTimeAsync(150);
    await Promise.resolve();
    await expander.updateComplete;

    expect(detail).toMatchObject({error: expect.any(TypeError)});
    expect(expander.shadowRoot?.querySelector('[part="loading"]')).toBeNull();
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
      JSON.stringify([
        {
          trigger: '@',
          boundary: 'whitespace',
          label: 'People',
          options: [{label: 'Brad', value: '@brad'}],
        },
      ])
    );
    document.body.append(expander);
    await expander.updateComplete;

    type(target, '@b');

    expect(options(expander)).toHaveLength(1);
    expect(
      expander.querySelector('[role="listbox"]')?.getAttribute('aria-label')
    ).toBe('People');
  });

  it('labels the listbox for the active trigger, falling back to a default label', async () => {
    const {expander, target} = await createFixture({
      '#': {
        label: 'Channels',
        options: [{label: 'General', value: '#general'}],
      },
      '@': {
        label: 'People',
        options: [{label: 'Brad', value: '@brad'}],
      },
      ':': {options: [{label: 'Smile', value: ':smile'}]},
    });
    const listbox = expander.querySelector('[role="listbox"]')!;

    expect(listbox.getAttribute('aria-label')).toBe('Suggestions');

    type(target, '#g');
    await waitForFirstOption(expander);
    expect(listbox.getAttribute('aria-label')).toBe('Channels');

    type(target, '@b');
    await waitForFirstOption(expander);
    expect(listbox.getAttribute('aria-label')).toBe('People');

    type(target, ':sm');
    await waitForFirstOption(expander);
    expect(listbox.getAttribute('aria-label')).toBe('Suggestions');
  });

  it('announces the active trigger label when the popover closes', async () => {
    const {expander, target} = await createFixture({
      '#': {
        label: 'Channels',
        options: [{label: 'General', value: '#general'}],
      },
      ':': {options: [{label: 'Smile', value: ':smile'}]},
    });
    const liveRegion = expander.shadowRoot!.querySelector('[aria-live]')!;

    type(target, '#g');
    await waitForFirstOption(expander);
    target.blur();

    await vi.waitFor(() =>
      expect(liveRegion.textContent?.trim()).toBe(
        'Channels suggestions collapsed'
      )
    );

    type(target, ':sm');
    await waitForFirstOption(expander);
    target.blur();

    await vi.waitFor(() =>
      expect(liveRegion.textContent?.trim()).toBe('Suggestions collapsed')
    );
  });

  it('clears the live region after the announcement timeout elapses', async () => {
    vi.useFakeTimers();
    const {expander, target} = await createFixture({
      '@': {options: [{label: 'Brad', value: '@brad'}]},
    });
    const liveRegion = expander.shadowRoot!.querySelector('[aria-live]')!;

    type(target, '@xyz');
    await vi.advanceTimersByTimeAsync(0);

    expect(liveRegion.textContent?.trim()).toBe('No suggestions');

    await vi.advanceTimersByTimeAsync(4999);
    expect(liveRegion.textContent?.trim()).toBe('No suggestions');

    await vi.advanceTimersByTimeAsync(1);
    expect(liveRegion.textContent?.trim()).toBe('');
  });
});
