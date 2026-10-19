import {actionClient} from '@craftcms/ui';
import {createApp, defineComponent, h, nextTick, reactive} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import type {UseAppLayoutOptions} from '@/common/composables/useAppLayout';
import AskAiController from '@/actions/CraftCms/Cms/Ai/Http/Controllers/AskAiController';
import AskAiSlideout from './AskAiSlideout.vue';
import type {AskAiOptions, AskAiProposal} from './ask-ai';

let layout: () => UseAppLayoutOptions = () => ({});
let context: AskAiOptions;
const slideout = {close: vi.fn()};

vi.mock('@/common/composables/useAppLayout', () => ({
  useAppLayout: (options: () => UseAppLayoutOptions) => {
    layout = options;
  },
}));

vi.mock('@/common/slideouts', () => ({
  useSlideout: () => slideout,
}));

vi.mock('@inertiajs/vue3', () => ({
  useForm: (data: object) =>
    reactive({...data, processing: false, defaults: () => {}}),
}));

vi.mock('./ask-ai', async (importOriginal) => ({
  ...(await importOriginal<object>()),
  takeAskAiContext: () => context,
}));

vi.mock('@/common/components/LayoutSlot.vue', () => ({
  default: defineComponent({
    setup(_props, {slots}) {
      return () => h('div', slots.default?.());
    },
  }),
}));

vi.mock('@/modules/ui/UiRenderer.vue', () => ({
  default: defineComponent({
    props: ['payload', 'errors'],
    setup(_props, {expose}) {
      expose({
        currentValues: () => ({prompt: 'Shorter', includeElement: true}),
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

function proposal(uid: string, label: string): AskAiProposal {
  return {
    layoutElementUid: uid,
    label,
    node: {} as AskAiProposal['node'],
    value: `${label} value`,
    diff: {current: `Old ${label}`, proposed: `${label} value`},
    preview: null,
  };
}

describe('AskAiSlideout', () => {
  let app: ReturnType<typeof createApp>;
  let container: HTMLElement;
  const post = vi.spyOn(actionClient, 'post');
  const applied: AskAiProposal[][] = [];

  function mount(layoutElementUid?: string): void {
    context = {
      params: {
        elementType: 'Entry',
        elementId: 1,
        draftId: null,
        siteId: 1,
        ...(layoutElementUid ? {layoutElementUid} : {}),
      },
      namespace: null,
      values: () => ({title: 'Unsaved title'}),
      onApply: (proposals) => applied.push(proposals),
    };
    container = document.createElement('div');
    document.body.append(container);
    app = createApp(() => h(AskAiSlideout, {contextId: 'test'}));
    app.mount(container);
  }

  async function save(): Promise<void> {
    layout().onSave?.();
    await flush();
  }

  beforeEach(() => {
    (window as any).Craft = {cp: {displayError: vi.fn()}};
    post.mockResolvedValueOnce({
      data: {
        ui: {nodes: [], values: {}},
        title: 'Ask AI',
        submitLabel: 'Ask',
      },
    });
    applied.length = 0;
  });

  afterEach(() => {
    app.unmount();
    container.remove();
    vi.clearAllMocks();
    vi.useRealTimers();
  });

  it('applies only the changes left checked, then closes', async () => {
    vi.useFakeTimers();
    post.mockResolvedValue({
      data: {
        proposals: [proposal('a', 'Title'), proposal('b', 'Summary')],
        summary: null,
        findings: [],
      },
    });
    mount();
    await flush();
    await save();

    expect(post).toHaveBeenCalledWith(
      AskAiController.form.url(),
      expect.objectContaining({title: 'Unsaved title', elementId: 1})
    );
    expect(post).toHaveBeenCalledWith(
      AskAiController.ask.url(),
      expect.objectContaining({
        prompt: 'Shorter',
        elementId: 1,
        title: 'Unsaved title',
      }),
      expect.anything()
    );
    expect(layout().submitButtonLabel).toBe('Apply');

    vi.advanceTimersByTime(700);
    const summaryCheckbox = container.querySelectorAll('craft-checkbox')[1]!;
    Object.assign(summaryCheckbox, {checked: false});
    summaryCheckbox.dispatchEvent(new Event('model-value-changed'));
    await flush();
    await save();

    expect(applied.map((p) => p.map((q) => q.layoutElementUid))).toEqual([
      ['a'],
    ]);
    expect(slideout.close).toHaveBeenCalledWith({force: true});
  });

  it('shows the summary and findings, each with its field', async () => {
    post.mockResolvedValue({
      data: {
        proposals: [proposal('a', 'Title')],
        summary: 'Shortened the title.',
        findings: [
          {
            layoutElementUid: 'a',
            label: 'Title',
            quote: null,
            issue: 'Too long.',
          },
          {
            layoutElementUid: 'b',
            label: 'Summary',
            quote: null,
            issue: 'Repeats the title.',
          },
          {
            layoutElementUid: null,
            label: null,
            quote: null,
            issue: 'Add a call to action.',
          },
        ],
      },
    });
    mount();
    await flush();
    await save();

    expect(container.textContent).toContain('Shortened the title.');
    const cards = [...container.querySelectorAll('craft-card')].map((card) =>
      card.textContent?.replace(/\s+/g, ' ').trim()
    );
    expect(cards).toHaveLength(3);
    expect(cards[0]).toContain('Too long.');
    expect(cards[1]).toContain('Summary');
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
            ...proposal('a', 'Body'),
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
    mount('a');
    await flush();
    await save();

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
      data: {proposals: [proposal('a', 'Title')], summary: null, findings: []},
    });
    mount();
    await flush();
    await save();

    container
      .querySelector<HTMLElement>('craft-button[variant="outline"]')!
      .dispatchEvent(new Event('click'));
    await flush();

    expect(document.activeElement).toBe(container.querySelector('textarea'));
  });

  it('offers Done instead of Apply for feedback with nothing to apply', async () => {
    vi.useFakeTimers();
    post.mockResolvedValue({
      data: {proposals: [], summary: 'Reads well.', findings: []},
    });
    mount();
    await flush();
    await save();

    expect(container.querySelector('h2')!.textContent).toContain('Review');
    expect(layout().submitButtonLabel).toBe('Done');

    vi.advanceTimersByTime(700);
    await flush();
    await save();

    expect(applied).toHaveLength(0);
    expect(slideout.close).toHaveBeenCalledWith({force: true});
  });

  it('shows the error and keeps the prompt when generating fails', async () => {
    post.mockRejectedValue({
      response: {
        data: {message: 'The generated value for “Summary” isn’t valid.'},
      },
    });
    mount('b');
    await flush();
    await save();

    expect(container.querySelector('[role="alert"]')!.textContent).toContain(
      'The generated value for “Summary” isn’t valid.'
    );
    expect(layout().submitButtonLabel).toBe('Ask');
    expect(applied).toHaveLength(0);
  });

  it('ignores Apply right after the review step appears', async () => {
    vi.useFakeTimers();
    post.mockResolvedValue({
      data: {proposals: [proposal('a', 'Title')], summary: null, findings: []},
    });
    mount('a');
    await flush();
    await save();

    await save();
    expect(applied).toHaveLength(0);

    vi.advanceTimersByTime(700);
    await flush();
    await save();

    expect(applied).toHaveLength(1);
  });
});
