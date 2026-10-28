import {actionClient} from '@craftcms/ui';
import {createApp, h, nextTick} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import DocsSearchView from './DocsSearchView.vue';

vi.mock('@actions/CommandPaletteController', () => ({
  docs: {url: () => '/palette/docs'},
}));

const flush = async () => {
  for (let i = 0; i < 4; i++) {
    await Promise.resolve();
    await nextTick();
  }
};

describe('DocsSearchView', () => {
  let app: ReturnType<typeof createApp>;
  let container: HTMLElement;
  const get = vi.spyOn(actionClient, 'get');
  const open = vi.spyOn(window, 'open').mockReturnValue(null);

  function mount(): HTMLInputElement {
    container = document.createElement('div');
    document.body.append(container);
    app = createApp(() =>
      h(DocsSearchView, {
        item: {id: 'search-docs', label: 'Search Craft CMS documentation'},
        view: {type: 'docs'},
      })
    );
    app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
    app.mount(container);

    return container.querySelector<HTMLInputElement>('[role="combobox"]')!;
  }

  async function type(input: HTMLInputElement, value: string): Promise<void> {
    input.value = value;
    input.dispatchEvent(new Event('input', {bubbles: true}));
    await flush();
    vi.runAllTimers();
    await flush();
  }

  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    app.unmount();
    container.remove();
    vi.useRealTimers();
    vi.clearAllMocks();
  });

  it('searches once typing pauses, and opens the selected result in a new tab', async () => {
    get.mockResolvedValue({
      data: {
        results: [
          {
            id: 'docs-1',
            label: 'craft.matrix',
            labelHtml: '<code>craft.matrix</code>',
            hint: 'About craft.matrix.',
            hintHtml: 'About <code>craft.matrix</code>.',
            href: 'https://craftcms.com/glossary/matrix',
            external: true,
          },
          {
            id: 'docs-2',
            label: 'Matrix Fields',
            href: 'https://craftcms.com/docs/matrix',
            external: true,
          },
        ],
      },
    } as never);
    const input = mount();

    await type(input, 'matrix');

    expect(get).toHaveBeenCalledOnce();
    expect(get.mock.calls[0]![1]).toMatchObject({params: {query: 'matrix'}});
    expect(
      [...container.querySelectorAll('code')].map((code) => code.textContent)
    ).toEqual(['craft.matrix', 'craft.matrix']);

    input.dispatchEvent(
      new KeyboardEvent('keydown', {key: 'ArrowDown', bubbles: true})
    );
    await nextTick();
    input.dispatchEvent(
      new KeyboardEvent('keydown', {key: 'Enter', bubbles: true})
    );

    expect(open).toHaveBeenCalledWith(
      'https://craftcms.com/docs/matrix',
      '_blank',
      'noopener'
    );
  });

  it('lists its results straight in the listbox, which already says they’re results', async () => {
    get.mockResolvedValue({
      data: {
        results: [
          {id: 'docs-1', label: 'Matrix Fields', href: 'https://craftcms.com'},
        ],
      },
    } as never);
    const input = mount();

    await type(input, 'matrix');

    const listbox = container.querySelector('[role="listbox"]')!;
    expect(input.getAttribute('aria-controls')).toBe(listbox.id);
    expect(listbox.getAttribute('aria-label')).toBe('Results');
    expect(listbox.querySelector('[role="group"]')).toBeNull();
    expect(listbox.querySelectorAll('[role="option"]')).toHaveLength(1);
    expect(
      listbox
        .querySelector('.command-palette-section__heading')!
        .getAttribute('aria-hidden')
    ).toBe('true');
  });

  it('keeps the latest results when an earlier search answers late', async () => {
    let answerEarlier!: (value: unknown) => void;
    get
      .mockReturnValueOnce(
        new Promise((resolve) => (answerEarlier = resolve)) as never
      )
      .mockResolvedValueOnce({
        data: {results: [{id: 'docs-1', label: 'Matrix Fields'}]},
      } as never);
    const input = mount();

    await type(input, 'mat');
    await type(input, 'matrix');
    answerEarlier({data: {results: []}});
    await flush();

    expect(container.querySelectorAll('.command-palette-row')).toHaveLength(1);
  });

  it('says when the docs couldn’t be searched', async () => {
    get.mockRejectedValue(new Error('Bad gateway'));
    const input = mount();

    await type(input, 'matrix');

    expect(container.textContent).toContain(
      'Couldn’t search the Craft CMS documentation.'
    );
  });
});
