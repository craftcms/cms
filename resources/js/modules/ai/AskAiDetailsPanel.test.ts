import {actionClient} from '@craftcms/ui';
import {
  createApp,
  defineComponent,
  h,
  nextTick,
  provide,
  shallowRef,
  type ShallowRef,
} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import AskAiController from '@/actions/CraftCms/Cms/Ai/Http/Controllers/AskAiController';
import AskAiDetailsPanel from './AskAiDetailsPanel.vue';
import {AskAiEditorKey, type AskAiProposal, type AskAiRequest} from './ask-ai';

vi.mock('@/modules/ui/UiRenderer.vue', () => ({
  default: defineComponent({
    props: ['payload', 'errors'],
    setup(_props, {expose}) {
      expose({
        currentValues: () => ({prompt: 'Shorter', includeImage: true}),
      });
      return () => h('textarea');
    },
  }),
}));

const flush = async () => {
  for (let i = 0; i < 4; i++) {
    await Promise.resolve();
    await nextTick();
  }
};

function proposal(key: string, label: string): AskAiProposal {
  return {
    key,
    label,
    node: {} as AskAiProposal['node'],
    value: `${label} value`,
    diff: {current: `Old ${label}`, proposed: `${label} value`},
    preview: null,
  };
}

const formResponse = {
  data: {
    ui: {nodes: [], values: {}},
    contextOptions: [
      {value: 'title', label: 'Title'},
      {value: 'fields.summary', label: 'Summary'},
    ],
    submitLabel: 'Ask',
  },
};

describe('AskAiDetailsPanel', () => {
  let app: ReturnType<typeof createApp>;
  let container: HTMLElement;
  let request: ShallowRef<AskAiRequest | null>;
  const post = vi.spyOn(actionClient, 'post');
  const applied: AskAiProposal[][] = [];

  async function mount(): Promise<void> {
    request = shallowRef(null);
    container = document.createElement('div');
    document.body.append(container);
    app = createApp(
      defineComponent({
        setup() {
          provide(AskAiEditorKey, {
            scope: () => [],
            values: () => ({title: 'Unsaved title'}),
            request,
            ask: (next) => (request.value = next),
            apply: (proposals) => applied.push(proposals),
          });

          return () =>
            h(AskAiDetailsPanel, {
              params: {
                elementType: 'Entry',
                elementId: 1,
                draftId: null,
                siteId: 1,
              },
              active: true,
            });
        },
      })
    );
    app.mount(container);
    await flush();
  }

  function button(label: string): HTMLElement {
    const found = [...container.querySelectorAll('craft-button')].find(
      (element) => element.textContent?.trim() === label
    );

    if (!found) {
      throw new Error(`No “${label}” button.`);
    }

    return found as HTMLElement;
  }

  async function click(label: string): Promise<void> {
    button(label).dispatchEvent(new Event('click'));
    await flush();
  }

  beforeEach(() => {
    post.mockResolvedValueOnce(formResponse);
    applied.length = 0;
  });

  afterEach(() => {
    app.unmount();
    container.remove();
    vi.clearAllMocks();
    vi.useRealTimers();
  });

  it('asks about every field by default, then applies the changes left checked', async () => {
    vi.useFakeTimers();
    post.mockResolvedValue({
      data: {
        proposals: [proposal('title', 'Title'), proposal('summary', 'Summary')],
        summary: null,
        findings: [],
      },
    });
    await mount();
    await click('Ask');

    expect(post).toHaveBeenCalledWith(
      AskAiController.form.url(),
      expect.objectContaining({title: 'Unsaved title', elementId: 1})
    );
    expect(post).toHaveBeenCalledWith(
      AskAiController.ask.url(),
      expect.objectContaining({
        prompt: 'Shorter',
        context: ['*'],
        elementId: 1,
        title: 'Unsaved title',
      }),
      expect.anything()
    );

    vi.advanceTimersByTime(700);
    const summaryCheckbox = container.querySelectorAll(
      'craft-card craft-checkbox'
    )[1]!;
    Object.assign(summaryCheckbox, {checked: false});
    summaryCheckbox.dispatchEvent(new Event('model-value-changed'));
    await flush();
    await click('Apply');

    expect(applied.map((p) => p.map((q) => q.key))).toEqual([['title']]);
    expect(container.querySelector('[role="status"]')!.textContent).toContain(
      'Applied 1 change.'
    );
    expect(document.activeElement).toBe(container.querySelector('textarea'));
  });

  it('takes a field’s request as its context, after refreshing the fields to choose from', async () => {
    await mount();
    const select = container.querySelector(
      'craft-checkbox-select'
    ) as HTMLElement & {
      modelValue: string[];
    };
    expect(select.modelValue).toEqual(['*']);

    post.mockResolvedValueOnce(formResponse);
    request.value = {context: ['fields.summary']};
    await flush();

    expect(post).toHaveBeenCalledTimes(2);
    expect(select.modelValue).toEqual(['fields.summary']);

    post.mockResolvedValueOnce({
      data: {proposals: [], summary: 'Reads well.', findings: []},
    });
    await click('Ask');

    expect(post).toHaveBeenLastCalledWith(
      AskAiController.ask.url(),
      expect.objectContaining({context: ['fields.summary']}),
      expect.anything()
    );
  });

  it('asks with Ctrl/Command + Enter, rather than saving the element', async () => {
    post.mockResolvedValue({
      data: {proposals: [], summary: 'Reads well.', findings: []},
    });
    await mount();
    const event = new KeyboardEvent('keydown', {
      key: 'Enter',
      metaKey: true,
      bubbles: true,
      cancelable: true,
    });
    container.querySelector('textarea')!.dispatchEvent(event);
    await flush();

    expect(event.defaultPrevented).toBe(true);
    expect(post).toHaveBeenLastCalledWith(
      AskAiController.ask.url(),
      expect.anything(),
      expect.anything()
    );
  });

  it('shows the summary and findings, each with its field', async () => {
    post.mockResolvedValue({
      data: {
        proposals: [proposal('title', 'Title')],
        summary: 'Shortened the title.',
        findings: [
          {key: 'title', label: 'Title', quote: null, issue: 'Too long.'},
          {
            key: 'blocks.1.text',
            label: 'Blocks › 1. Intro › Text',
            quote: null,
            issue: 'Repeats the title.',
          },
          {key: null, label: null, quote: null, issue: 'Add a call to action.'},
        ],
      },
    });
    await mount();
    await click('Ask');

    expect(container.textContent).toContain('Shortened the title.');
    const cards = [...container.querySelectorAll('craft-card')].map((card) =>
      card.textContent?.replace(/\s+/g, ' ').trim()
    );
    expect(cards).toHaveLength(3);
    expect(cards[0]).toContain('Too long.');
    expect(cards[1]).toContain('Blocks › 1. Intro › Text');
    expect(cards[1]).toContain('Repeats the title.');
    expect(cards[2]).toContain('Overall');
    expect(cards[2]).toContain('Add a call to action.');
  });

  it('collapses long unchanged text until the full field is shown', async () => {
    const unchanged = 'Unchanged words fill this paragraph. '.repeat(20);
    post.mockResolvedValue({
      data: {
        proposals: [
          {
            ...proposal('body', 'Body'),
            diff: {
              current: `${unchanged}Old ending.`,
              proposed: `${unchanged}New ending.`,
            },
          },
        ],
        summary: null,
        findings: [],
      },
    });
    await mount();
    await click('Ask');

    const diff = () => container.querySelector('.ask-ai-diff')!.textContent;
    expect(diff()).not.toContain(unchanged.trim());

    const toggle = container.querySelector('craft-button[slot="actions"]')!;
    expect(toggle.getAttribute('aria-expanded')).toBe('false');
    toggle.dispatchEvent(new Event('click'));
    await flush();

    expect(diff()).toContain(unchanged.trim());
    expect(toggle.getAttribute('aria-expanded')).toBe('true');
  });

  it('moves focus to the prompt when trying again', async () => {
    post.mockResolvedValue({
      data: {
        proposals: [proposal('title', 'Title')],
        summary: null,
        findings: [],
      },
    });
    await mount();
    await click('Ask');
    await click('Try again');

    expect(document.activeElement).toBe(container.querySelector('textarea'));
  });

  it('offers Done instead of Apply for feedback with nothing to apply', async () => {
    post.mockResolvedValue({
      data: {proposals: [], summary: 'Reads well.', findings: []},
    });
    await mount();
    await click('Ask');

    expect(container.querySelector('h4')!.textContent).toContain('Review');
    expect(() => button('Apply')).toThrow();

    await click('Done');

    expect(applied).toHaveLength(0);
    expect(container.querySelector('h4')).toBeNull();
  });

  it('shows the error and keeps the prompt when generating fails', async () => {
    post.mockRejectedValue({
      response: {
        data: {message: 'The generated value for “Summary” isn’t valid.'},
      },
    });
    await mount();
    await click('Ask');

    expect(container.querySelector('[role="alert"]')!.textContent).toContain(
      'The generated value for “Summary” isn’t valid.'
    );
    expect(button('Ask')).toBeTruthy();
    expect(applied).toHaveLength(0);
  });

  it('ignores Apply right after the review step appears', async () => {
    vi.useFakeTimers();
    post.mockResolvedValue({
      data: {
        proposals: [proposal('title', 'Title')],
        summary: null,
        findings: [],
      },
    });
    await mount();
    await click('Ask');
    await click('Apply');

    expect(applied).toHaveLength(0);

    vi.advanceTimersByTime(700);
    await click('Apply');

    expect(applied).toHaveLength(1);
  });
});
