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
import {streamAction} from './stream-action';
import {
  AskAiEditorKey,
  type AskAiConversationTurn,
  type AskAiProposal,
  type AskAiRequest,
  type AskAiResult,
} from './ask-ai';

const composer = vi.hoisted(() => ({prompt: ''}));

vi.mock('./stream-action', () => ({streamAction: vi.fn()}));

vi.mock('@/modules/ui/UiRenderer.vue', () => ({
  default: defineComponent({
    props: ['payload', 'errors'],
    setup(_props, {expose}) {
      expose({
        currentValues: () => ({prompt: composer.prompt, includeImage: true}),
        setValue: (_path: string[], value: string) => (composer.prompt = value),
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

function reply(data: Partial<AskAiResult> = {}): {data: AskAiResult} {
  return {
    data: {
      proposals: [],
      summary: null,
      findings: [],
      turn: {prompt: composer.prompt, answer: '{}'},
      ...data,
    },
  };
}

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
  let conversation: ShallowRef<AskAiConversationTurn[]>;
  const post = vi.spyOn(actionClient, 'post');
  const applied: AskAiProposal[][] = [];
  /** The keys of proposals whose fields are no longer in the form. */
  const goneFields = new Set<string>();

  async function mount(): Promise<void> {
    request = shallowRef(null);
    conversation = shallowRef([]);
    container = document.createElement('div');
    document.body.append(container);
    app = createApp(
      defineComponent({
        setup() {
          provide(AskAiEditorKey, {
            scope: () => [],
            values: () => ({title: 'Unsaved title'}),
            request,
            conversation,
            ask: (next) => (request.value = next),
            apply: (proposals) => {
              applied.push(proposals);

              return proposals.filter(({key}) => !goneFields.has(key));
            },
          });

          return () =>
            h(AskAiDetailsPanel, {
              params: {
                elementType: 'Entry',
                elementId: 1,
                draftId: null,
                siteId: 1,
                ownerId: null,
                fieldId: null,
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
      (element) => element.textContent?.replace(/\s+/g, ' ').trim() === label
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

  function prompts(): string[] {
    return [...container.querySelectorAll('.ask-ai-thread__prompt')].map(
      (element) => element.textContent!.replace('You:', '').trim()
    );
  }

  beforeEach(() => {
    // Streams each answer as one `result` event, from the mocked `actionClient.post`.
    vi.mocked(streamAction).mockImplementation(
      async (url, data, {signal, onEvent}) => {
        const {data: result} = await actionClient.post(url, data, {signal});
        onEvent({type: 'result', result});
      }
    );
    post.mockResolvedValueOnce(formResponse);
    applied.length = 0;
    goneFields.clear();
    composer.prompt = 'Shorter';
  });

  afterEach(() => {
    app.unmount();
    container.remove();
    vi.clearAllMocks();
    vi.useRealTimers();
  });

  it('asks about every field by default, and applies changes one at a time, collapsing each', async () => {
    post.mockResolvedValueOnce(
      reply({
        proposals: [proposal('title', 'Title'), proposal('summary', 'Summary')],
      })
    );
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
        history: [],
        elementId: 1,
        title: 'Unsaved title',
      }),
      expect.anything()
    );
    expect(prompts()).toEqual(['Shorter']);
    expect(composer.prompt).toBe('');

    const toggle = button('Summary');
    const card = toggle.closest('craft-card') as HTMLElement & {
      collapsed: boolean;
    };
    expect(card.collapsed).toBe(false);
    expect(toggle.getAttribute('aria-expanded')).toBe('true');

    await click('Apply Summary');

    expect(applied.map((p) => p.map((q) => q.key))).toEqual([['summary']]);
    expect(document.activeElement).toBe(toggle);
    expect(() => button('Apply Summary')).toThrow();
    expect(
      container.querySelector('.ask-ai-reply__applied')!.textContent
    ).toContain('Applied');
    expect(container.querySelector('[role="status"]')!.textContent).toContain(
      'Applied 1 change.'
    );
    expect(card.collapsed).toBe(true);
    expect(toggle.getAttribute('aria-expanded')).toBe('false');

    await click('Summary');

    expect(card.collapsed).toBe(false);
    expect(toggle.getAttribute('aria-expanded')).toBe('true');

    await click('Apply Title');

    expect(applied.map((p) => p.map((q) => q.key))).toEqual([
      ['summary'],
      ['title'],
    ]);
  });

  it('keeps a change whose field is gone, and says it couldn’t be applied', async () => {
    post.mockResolvedValueOnce(
      reply({
        proposals: [proposal('title', 'Title'), proposal('summary', 'Summary')],
      })
    );
    goneFields.add('summary');
    await mount();
    await click('Ask');
    await click('Apply all');

    expect(() => button('Apply Title')).toThrow();
    expect(button('Apply Summary')).toBeTruthy();
    expect(container.querySelector('[role="status"]')!.textContent).toContain(
      'Applied 1 change.'
    );
    expect(container.querySelector('[role="alert"]')!.textContent).toContain(
      '1 change couldn’t be applied'
    );
  });

  it('shows the summary as it’s written, then says more is coming until the changes arrive', async () => {
    let start!: () => void;
    let finish!: () => void;
    vi.mocked(streamAction).mockImplementationOnce(
      async (_url, _data, {onEvent}) => {
        await new Promise<void>((resolve) => (start = resolve));
        onEvent({type: 'summary', delta: 'Shortened '});
        onEvent({type: 'summary', delta: 'the summary.'});
        await new Promise<void>((resolve) => (finish = resolve));
        onEvent({
          type: 'result',
          result: reply({
            summary: 'Shortened the summary.',
            proposals: [proposal('summary', 'Summary')],
          }).data,
        });
      }
    );
    await mount();
    await click('Ask');

    const replyText = () =>
      container
        .querySelector('.ask-ai-reply')!
        .textContent!.replace('Ask AI:', '')
        .trim();
    expect(replyText()).toBe('Thinking…');

    vi.useFakeTimers({toFake: ['setTimeout', 'clearTimeout']});
    start();
    await flush();

    // Not while the summary is still arriving, only once it pauses.
    expect(replyText()).toBe('Shortened the summary.');

    vi.advanceTimersByTime(1000);
    await flush();

    expect(replyText()).toMatch(
      /^Shortened the summary\.\s*Preparing suggestions…$/
    );
    expect(container.querySelector('[aria-busy="true"]')).not.toBeNull();
    expect(container.querySelector('craft-card')).toBeNull();

    await click('Send');

    expect(streamAction).toHaveBeenCalledTimes(1);
    expect(container.querySelector('[role="status"]')!.textContent).toContain(
      'Wait for the current reply to finish.'
    );

    finish();
    await flush();

    expect(container.querySelector('[aria-busy="true"]')).toBeNull();
    expect(container.querySelector('craft-card')).not.toBeNull();
    expect(replyText()).not.toContain('Preparing suggestions…');
  });

  it('shows a sent prompt wherever the user had scrolled to, then follows the reply', async () => {
    let next!: () => void;
    vi.mocked(streamAction).mockImplementationOnce(
      async (_url, _data, {onEvent}) => {
        await new Promise<void>((resolve) => (next = resolve));
        onEvent({type: 'summary', delta: 'Shortened '});
        await new Promise<void>((resolve) => (next = resolve));
      }
    );
    await mount();
    const history = container.querySelector<HTMLElement>(
      '.ask-ai-panel__history'
    )!;
    let height = 1000;
    Object.defineProperty(history, 'scrollHeight', {get: () => height});
    Object.defineProperty(history, 'clientHeight', {get: () => 200});
    for (const top of [800, 100]) {
      history.scrollTop = top;
      history.dispatchEvent(new Event('scroll'));
    }

    await click('Ask');
    expect(history.scrollTop).toBe(1000);

    height = 1200;
    next();
    await flush();
    expect(history.scrollTop).toBe(1200);
  });

  it('sends the earlier turns with a follow-up', async () => {
    post.mockResolvedValueOnce(
      reply({
        summary: 'Shortened it.',
        turn: {prompt: 'Shorter', answer: '{"summary":"Shortened it."}'},
      })
    );
    await mount();
    await click('Ask');

    composer.prompt = 'Even shorter';
    post.mockResolvedValueOnce(reply({summary: 'Shortened it more.'}));
    await click('Send');

    expect(post).toHaveBeenLastCalledWith(
      AskAiController.ask.url(),
      expect.objectContaining({
        prompt: 'Even shorter',
        history: [{prompt: 'Shorter', answer: '{"summary":"Shortened it."}'}],
      }),
      expect.anything()
    );
    expect(prompts()).toEqual(['Shorter', 'Even shorter']);
    expect(container.textContent).toContain('Shortened it more.');
  });

  it('starts a new conversation when the header empties it', async () => {
    post.mockResolvedValueOnce(reply({summary: 'Shortened it.'}));
    await mount();
    await click('Ask');

    expect(conversation.value).toHaveLength(1);

    conversation.value = [];
    await flush();

    expect(prompts()).toEqual([]);
    expect(container.querySelector('[role="status"]')!.textContent).toContain(
      'Started a new conversation.'
    );
    expect(document.activeElement).toBe(container.querySelector('textarea'));

    composer.prompt = 'Longer';
    post.mockResolvedValueOnce(reply());
    await click('Ask');

    expect(post).toHaveBeenLastCalledWith(
      AskAiController.ask.url(),
      expect.objectContaining({prompt: 'Longer', history: []}),
      expect.anything()
    );
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
    expect(button('Context: Summary')).toBeTruthy();

    post.mockResolvedValueOnce(reply({summary: 'Reads well.'}));
    await click('Ask');

    expect(post).toHaveBeenLastCalledWith(
      AskAiController.ask.url(),
      expect.objectContaining({context: ['fields.summary']}),
      expect.anything()
    );
  });

  it('asks with Ctrl/Command + Enter, rather than saving the element', async () => {
    post.mockResolvedValueOnce(reply({summary: 'Reads well.'}));
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
    post.mockResolvedValueOnce(
      reply({
        proposals: [proposal('title', 'Title')],
        summary: 'Shortened the title.',
        findings: [
          {
            key: 'title',
            path: 'title',
            label: 'Title',
            quote: null,
            issue: 'Too long.',
          },
          {
            key: 'blocks.1.text',
            path: 'fields.blocks.entries.1.fields.text',
            label: 'Blocks › 1. Intro › Text',
            quote: null,
            issue: 'Repeats the title.',
          },
          {
            key: null,
            path: null,
            label: null,
            quote: null,
            issue: 'Add a call to action.',
          },
        ],
      })
    );
    await mount();
    await click('Ask');

    expect(container.textContent).toContain('Shortened the title.');
    const cards = [...container.querySelectorAll('craft-card')].map((card) =>
      card.textContent?.replace(/\s+/g, ' ').trim()
    );
    expect(cards).toHaveLength(3);
    expect(cards[0]).toContain('Too long.');
    expect(cards[0]).not.toContain('Ask to fix');
    expect(cards[1]).toContain('Ask to fix');
    expect(cards[1]).toContain('Blocks › 1. Intro › Text');
    expect(cards[1]).toContain('Repeats the title.');
    expect(cards[2]).toContain('Overall');
    expect(cards[2]).toContain('Add a call to action.');
  });

  it('asks to fix a finding in the field it concerns', async () => {
    post.mockResolvedValueOnce(
      reply({
        findings: [
          {
            key: 'blocks.1.text',
            path: 'fields.blocks.entries.1.fields.text',
            label: 'Blocks › 1. Intro › Text',
            quote: null,
            issue: 'Repeats the title.',
          },
        ],
      })
    );
    await mount();
    await click('Ask');

    post.mockResolvedValueOnce(reply());
    await click('Ask to fix Repeats the title.');

    expect(post).toHaveBeenLastCalledWith(
      AskAiController.ask.url(),
      expect.objectContaining({
        prompt: 'Fix this: Repeats the title.',
        context: ['fields.blocks.entries.1.fields.text'],
        history: [expect.anything()],
      }),
      expect.anything()
    );
    expect(prompts()).toEqual(['Shorter', 'Fix this: Repeats the title.']);
  });

  it('asks to fix all of a field’s findings at once', async () => {
    const finding = (issue: string) => ({
      key: 'summary',
      path: 'fields.summary',
      label: 'Summary',
      quote: null,
      issue,
    });
    post.mockResolvedValueOnce(
      reply({findings: [finding('Too long.'), finding('Repeats the title.')]})
    );
    await mount();
    await click('Ask');

    expect(
      container.querySelector('craft-card craft-badge')!.textContent!.trim()
    ).toBe('2 notes');

    post.mockResolvedValueOnce(reply());
    await click('Fix all Summary');

    expect(post).toHaveBeenLastCalledWith(
      AskAiController.ask.url(),
      expect.objectContaining({
        prompt: 'Fix these problems:\n- Too long.\n- Repeats the title.',
        context: ['fields.summary'],
      }),
      expect.anything()
    );
  });

  it('shows a text change as a collapsible diff, marking the field’s quoted findings', async () => {
    post.mockResolvedValueOnce(
      reply({
        proposals: [proposal('body', 'Body')],
        findings: [
          {
            key: 'body',
            path: 'body',
            label: 'Body',
            quote: 'Old',
            issue: 'Outdated.',
          },
        ],
      })
    );
    await mount();
    await click('Ask');

    const diff = container.querySelector('craft-text-diff')!;

    expect(diff).toMatchObject({
      current: 'Old Body',
      proposed: 'Body value',
      highlights: ['Old'],
      collapse: true,
      label: 'Body',
    });
  });

  it('keeps a follow-up typed while waiting when generating fails', async () => {
    vi.mocked(streamAction).mockImplementationOnce(
      async (_url, _data, {onEvent}) => {
        composer.prompt = 'Follow-up';
        onEvent({type: 'error', status: 502, message: 'Failed.'});
      }
    );
    await mount();
    await click('Ask');

    expect(composer.prompt).toBe('Follow-up');
  });

  it('shows the error, and gives the prompt back, when generating fails', async () => {
    vi.mocked(streamAction).mockImplementationOnce(
      async (_url, _data, {onEvent}) => {
        onEvent({type: 'summary', delta: 'Shortening '});

        // As `streamAction()` rejects with an `error` event.
        throw {
          response: {
            status: 502,
            data: {
              type: 'error',
              message: 'The content couldn’t be generated.',
            },
          },
        };
      }
    );
    await mount();
    await click('Ask');

    expect(container.querySelector('[role="alert"]')!.textContent).toContain(
      'The content couldn’t be generated.'
    );
    expect(prompts()).toEqual([]);
    expect(composer.prompt).toBe('Shorter');
    expect(applied).toHaveLength(0);
  });
});
