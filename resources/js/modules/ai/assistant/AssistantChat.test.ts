import type {ActionMenuItemButton} from '@craftcms/ui/components/action-menu/action-menu.types';
import {createApp, nextTick, shallowRef, type ShallowRef} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import type {ConnectionAdapter} from '@tanstack/ai-vue';
import {assistantApi} from '@/bootstrap/assistant';
import {AssistantRequestError} from './assistant';
import {registerAssistantEditor, useAssistantContext} from './context';
import AssistantChat from './AssistantChat.vue';

const chat = vi.hoisted(() => ({
  sendMessage: vi.fn(),
  resumeInterruptsUnsafe: vi.fn(),
  interrupts: null as unknown as ShallowRef<unknown[]>,
  messages: null as unknown as ShallowRef<unknown[]>,
  error: null as unknown as ShallowRef<Error | undefined>,
  connection: null as unknown as Extract<ConnectionAdapter, {connect: unknown}>,
}));

vi.mock('@craftcms/ui', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@craftcms/ui')>()),
  actionHeaders: () => ({}),
  getActionUrl: (url: string) => url,
}));

vi.mock('@tanstack/ai-vue', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@tanstack/ai-vue')>()),
  useChat: (options: {connection: ConnectionAdapter}) => ({
    connection: (chat.connection =
      options.connection as typeof chat.connection),
    messages: (chat.messages = shallowRef([])),
    isLoading: shallowRef(false),
    error: (chat.error = shallowRef(undefined)),
    sendMessage: chat.sendMessage,
    stop: vi.fn(),
    interrupts: (chat.interrupts = shallowRef([])),
    resumeInterruptsUnsafe: chat.resumeInterruptsUnsafe,
  }),
}));

const flush = async () => {
  for (let i = 0; i < 4; i++) {
    await Promise.resolve();
    await nextTick();
  }
};

describe('AssistantChat', () => {
  let container: HTMLElement;
  let app: ReturnType<typeof createApp>;

  beforeEach(async () => {
    container = document.createElement('div');
    document.body.append(container);
    app = createApp(AssistantChat, {threadId: crypto.randomUUID()});
    app.mount(container);
    await flush();
  });

  afterEach(() => {
    app.unmount();
    container.remove();
    useAssistantContext().sent.value = [];
    vi.clearAllMocks();
  });

  async function type(text: string): Promise<HTMLTextAreaElement> {
    const textarea = container.querySelector('textarea')!;
    textarea.value = text;
    textarea.dispatchEvent(new Event('input', {bubbles: true}));
    await flush();

    return textarea;
  }

  function press(
    textarea: HTMLTextAreaElement,
    modifiers: KeyboardEventInit = {}
  ): void {
    textarea.dispatchEvent(
      new KeyboardEvent('keydown', {
        key: 'Enter',
        bubbles: true,
        composed: true,
        cancelable: true,
        ...modifiers,
      })
    );
  }

  /**
   * Makes a request for each run, resuming the given interrupts, and returns
   * the bodies sent.
   */
  async function requests(
    resumes: Array<unknown[] | undefined> = [undefined]
  ): Promise<
    Array<{forwardedProps: {context: unknown[]}; resume?: unknown[]}>
  > {
    const fetch = vi.fn(
      async (_input: RequestInfo | URL, _init?: RequestInit) =>
        new Response('', {status: 200})
    );
    vi.stubGlobal('fetch', fetch);

    for (const resume of resumes) {
      const run = chat.connection.connect([], undefined, undefined, {
        threadId: 'thread-1',
        runId: crypto.randomUUID(),
        ...(resume ? {resume} : {}),
      } as never);

      // The empty reply ends the stream early, which doesn’t matter here.
      await (async () => {
        for await (const _ of run);
      })().catch(() => {});
    }

    vi.unstubAllGlobals();

    return fetch.mock.calls.flatMap(([, init]) =>
      typeof init?.body === 'string' ? [JSON.parse(init.body)] : []
    );
  }

  it('sends the message on Enter, and empties the box', async () => {
    const textarea = await type('  How many posts are there?  ');

    press(textarea);
    await flush();

    expect(chat.sendMessage).toHaveBeenCalledWith('How many posts are there?');
    expect(textarea.value).toBe('');
  });

  it('sends a message with its context, which leaves the box, and goes with every request of its turn, including answers to approval requests', async () => {
    const ref = assistantApi.addContext({
      type: 'commerce:order',
      key: 'order-42',
      label: 'Order #42',
      data: () => ({orderId: 42}),
    });
    press(await type('Is it shipped?'));
    await flush();

    expect(chat.sendMessage).toHaveBeenCalledWith(
      `<context>${JSON.stringify([{type: 'commerce:order', ref, label: 'Order #42'}])}</context>\n\nIs it shipped?`
    );
    expect(assistantApi.getContext('order-42')).toBeUndefined();

    const bodies = await requests([
      undefined,
      [{interruptId: 'call_1', status: 'resolved'}],
    ]);

    expect(bodies.map((body) => body.forwardedProps.context)).toEqual([
      [{type: 'commerce:order', ref, orderId: 42}],
      [{type: 'commerce:order', ref, orderId: 42}],
    ]);
    expect(bodies[1]!.resume).toHaveLength(1);
  });

  it('names the open editor’s element in each request, without a chip', async () => {
    const params = {
      elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
      elementId: 42,
      draftId: null,
      siteId: 1,
      ownerId: null,
      fieldId: null,
      canonicalId: 42,
    };
    const close = registerAssistantEditor({
      id: 'page',
      params: () => params,
      label: () => 'Lighthouses',
      scope: () => ['editor'],
      values: () => ({title: 'Lighthouses of Maine'}),
      container: () => null,
    });
    await flush();
    const pointer = {
      ...params,
      type: 'craft:element',
      ref: 'page',
      open: true,
      fields: null,
      namespace: ['editor'],
      values: {title: 'Lighthouses of Maine'},
    };

    expect(container.querySelector('.assistant-chat__chips')).toBeNull();
    expect((await requests())[0]!.forwardedProps.context).toEqual([pointer]);

    close();
  });

  it('shows the server’s message when a request fails', async () => {
    chat.error.value = new Error('Stream response body read failed', {
      cause: new AssistantRequestError('The AI assistant is disabled.'),
    });
    await flush();

    expect(container.querySelector('[role=alert]')?.textContent).toContain(
      'The AI assistant is disabled.'
    );
  });

  it('offers the context plugins can add now in the Add menu, and adds the one chosen', async () => {
    const add = vi.fn();
    assistantApi.registerContextSource({
      id: 'test:order',
      label: () => 'Order #1042',
      add,
    });
    assistantApi.registerContextSource({
      id: 'test:unavailable',
      label: () => 'Nothing to add',
      available: () => false,
      add: vi.fn(),
    });
    await flush();

    const menu = container.querySelector<
      HTMLElement & {actions: () => ActionMenuItemButton[]}
    >('.assistant-chat__toolbar craft-action-menu')!;
    const items = menu.actions();
    items[0]!.onClick!(new Event('click'));

    expect(items.map((item) => item.label)).toEqual(['Order #1042']);
    expect(add).toHaveBeenCalledOnce();
  });

  // Shift + Enter, and Enter on touch screens, which have no Shift + Enter, are
  // left to the textarea; it doesn't add a line on Ctrl/Command + Enter itself.
  it.each([
    ['Shift + Enter', {shiftKey: true}, false, 'First line'],
    ['Enter on a touch screen', {}, true, 'First line'],
    ['Ctrl + Enter', {ctrlKey: true}, false, 'First line\n'],
    ['Command + Enter', {metaKey: true}, false, 'First line\n'],
  ])(
    'starts a new line on %s, rather than sending',
    async (_key, modifiers, touch, value) => {
      vi.stubGlobal('matchMedia', (query: string) => ({
        matches: touch && query === '(pointer: coarse)',
      }));
      const textarea = await type('First line');
      textarea.setSelectionRange(textarea.value.length, textarea.value.length);

      press(textarea, modifiers);
      await flush();
      vi.unstubAllGlobals();

      expect(chat.sendMessage).not.toHaveBeenCalled();
      expect(textarea.value).toBe(value);
    }
  );

  function approval(id: string): unknown {
    return {
      id,
      interruptId: id,
      reason: 'approval_required',
      message: 'This changes content or settings.',
      metadata: {kind: 'approval', toolName: 'elements_update', input: {id: 1}},
    };
  }

  function button(label: string, index = 0): HTMLElement {
    return [...container.querySelectorAll<HTMLElement>('craft-button')].filter(
      (element) => element.textContent?.trim() === label
    )[index]!;
  }

  it('asks again when the answers to approval requests couldn’t be sent', async () => {
    chat.resumeInterruptsUnsafe.mockResolvedValue(false);
    chat.interrupts.value = [approval('call_1')];
    await flush();

    button('Approve').click();
    await flush();

    expect(button('Approve')).toBeDefined();
  });

  it('waits for an answer to every approval request, then sends them together', async () => {
    chat.interrupts.value = [approval('call_1'), approval('call_2')];
    await flush();

    expect(container.textContent).toContain(
      'Allow the assistant to use elements.update?'
    );

    button('Approve', 0).click();
    await flush();

    expect(chat.resumeInterruptsUnsafe).not.toHaveBeenCalled();

    button('Reject').click();
    await flush();

    expect(chat.resumeInterruptsUnsafe).toHaveBeenCalledWith([
      {interruptId: 'call_1', status: 'resolved', payload: {approved: true}},
      {interruptId: 'call_2', status: 'resolved', payload: {approved: false}},
    ]);
  });

  it('keeps a message sent while an approval is waiting, and says why', async () => {
    chat.interrupts.value = [approval('call_1')];
    await flush();
    const textarea = await type('What sites are there?');

    press(textarea);
    await flush();

    expect(chat.sendMessage).not.toHaveBeenCalled();
    expect(textarea.value).toBe('What sites are there?');
    expect(container.querySelector('[role=alert]')?.textContent).toContain(
      'Approve or reject the assistant’s request'
    );
  });

  it('offers the text expanders that have suggestions on the message box, and lets the chosen one act on it', async () => {
    const select = vi.fn();
    const option = {label: 'Order #42', value: 'Order #42'};
    assistantApi.registerTextExpander({
      id: 'test:orders',
      trigger: '#',
      label: 'Orders',
      options: () => [option],
      select,
    });
    assistantApi.registerTextExpander({
      id: 'test:nothing',
      trigger: '%',
      label: 'Nothing',
      options: () => [],
    });
    await flush();

    const expander = container.querySelector<
      HTMLElement & {triggers: Array<{trigger: string}>}
    >('craft-text-expander')!;
    expander.dispatchEvent(
      new CustomEvent('craft-select', {
        detail: {character: '#', query: '', option},
      })
    );

    expect(expander.triggers.map(({trigger}) => trigger)).toContain('#');
    expect(expander.triggers.map(({trigger}) => trigger)).not.toContain('%');
    expect(select).toHaveBeenCalledWith(option);
  });

  it('highlights what the text expanders inserted behind the message box, until it’s edited', async () => {
    const option = {label: 'Order #42', value: '@Order #42'};
    assistantApi.registerTextExpander({
      id: 'test:mentions',
      trigger: '~',
      label: 'Mentions',
      options: () => [option],
    });
    await flush();

    const textarea = await type('Ship @Order #42 today');
    container.querySelector('craft-text-expander')!.dispatchEvent(
      new CustomEvent('craft-select', {
        detail: {character: '~', query: '', option},
      })
    );
    await flush();

    const layer = () => container.querySelector('.assistant-mentions')!;

    expect(layer().textContent?.replace(/\u200b\s*$/, '')).toBe(
      'Ship @Order #42 today'
    );
    expect(layer().querySelector('mark')?.textContent).toBe('@Order #42');

    textarea.value = 'Ship @Order #4 today';
    textarea.dispatchEvent(new Event('input', {bubbles: true}));
    await flush();

    expect(layer().querySelector('mark')).toBeNull();
  });

  it('shows the elements show_elements returned as chips that link to them, with their actions', async () => {
    chat.messages.value = [
      {
        id: 'm1',
        role: 'assistant',
        parts: [
          {
            type: 'tool-call',
            id: 'call_1',
            name: 'show_elements',
            arguments: '{}',
            state: 'input-complete',
            output: JSON.stringify({
              message: 'Showed 1 element(s).',
              elements: [
                {
                  id: 4,
                  label: 'Lighthouses',
                  cpEditUrl: '/admin/entries/4',
                  actions: [
                    {
                      label: 'View in a new tab',
                      behavior: {type: 'link', href: '/lighthouses'},
                    },
                  ],
                },
              ],
            }),
          },
        ],
      },
    ];
    await flush();

    const link = container.querySelector('craft-chip a');

    expect(container.textContent).toContain('Showed elements');
    expect(link?.textContent).toBe('Lighthouses');
    expect(link?.getAttribute('href')).toBe('/admin/entries/4');
    expect(
      container
        .querySelector('craft-chip craft-action-menu craft-button')
        ?.getAttribute('aria-label')
    ).toBe('Actions for Lighthouses');
    expect(container.textContent).toContain('View in a new tab');
  });

  it('shows the elements show_elements returned as cards, with their actions, when it chose cards', async () => {
    chat.messages.value = [
      {
        id: 'm1',
        role: 'assistant',
        parts: [
          {
            type: 'tool-call',
            id: 'call_1',
            name: 'show_elements',
            arguments: '{}',
            state: 'input-complete',
            output: JSON.stringify({
              view: 'cards',
              elements: [
                {
                  id: 4,
                  label: 'Lighthouses',
                  cardHeaderHtml: '<a href="/admin/entries/4">Lighthouses</a>',
                  actions: [
                    {
                      label: 'View in a new tab',
                      behavior: {type: 'link', href: '/lighthouses'},
                    },
                  ],
                },
              ],
            }),
          },
        ],
      },
    ];
    await flush();

    expect(container.querySelector('craft-chip')).toBeNull();
    expect(container.textContent).toContain('Lighthouses');
    expect(
      container
        .querySelector('craft-action-menu craft-button')
        ?.getAttribute('aria-label')
    ).toBe('Actions for Lighthouses');
  });

  it('shows what a message was sent with on its bubble, apart from its text', async () => {
    chat.messages.value = [
      {
        id: 'm1',
        role: 'user',
        parts: [
          {
            type: 'text',
            content:
              '<context>[{"label":"Order #42","icon":"cart-shopping","status":{"fill":"green","label":"Paid"}}]</context>\n\nIs it **shipped**?',
          },
        ],
      },
    ];
    await flush();

    const bubble = container.querySelector('.assistant-chat__prompt')!;

    expect(bubble.querySelector('craft-chip')?.textContent?.trim()).toBe(
      'Order #42'
    );
    expect(
      bubble.querySelector('craft-chip craft-indicator')?.getAttribute('fill')
    ).toBe('green');
    expect(
      bubble.querySelector('.assistant-chat__prompt-text')?.textContent
    ).toBe('Is it shipped?');
    expect(
      bubble.querySelector('.assistant-chat__prompt-text strong')?.textContent
    ).toBe('shipped');
  });

  it('shows the images Craft made with assets.transform-url inline, and others as links', async () => {
    const url = 'https://example.test/transforms/_800x600/photo.jpg';
    chat.messages.value = [
      {
        id: 'm1',
        role: 'assistant',
        parts: [
          {
            type: 'tool-call',
            id: 'call_1',
            name: 'assets_transform-url',
            arguments: '{"id":1}',
            state: 'input-complete',
            output: JSON.stringify({url}),
          },
          {
            type: 'text',
            content: `![Photo](${url}) ![Other](https://evil.example/x.png?d=1)`,
          },
        ],
      },
    ];
    await flush();

    expect(
      [...container.querySelectorAll('.assistant-chat__markdown img')].map(
        (image) => image.getAttribute('src')
      )
    ).toEqual([url]);
    expect(
      container.querySelector('.assistant-chat__markdown a')?.textContent
    ).toBe('Other');
  });

  it('says when a tool call never finished, rather than that it’s still running', async () => {
    chat.messages.value = [
      {
        id: 'm1',
        role: 'assistant',
        parts: [
          {
            type: 'tool-call',
            id: 'call_1',
            name: 'elements_update',
            arguments: '{}',
            state: 'error',
          },
        ],
      },
    ];
    await flush();

    expect(container.textContent).toContain(
      'Didn’t finish using elements.update'
    );
  });
});
