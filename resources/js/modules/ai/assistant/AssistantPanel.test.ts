import {setIconResolver} from '@craftcms/ui/utilities/icons';
import {nothing} from 'lit';
import {detectPlatform} from '@tanstack/hotkeys';
import {
  createApp,
  defineComponent,
  h,
  nextTick,
  onBeforeUnmount,
  onMounted,
  provide,
  ref,
  useTemplateRef,
  type VNode,
} from 'vue';
import {
  afterEach,
  beforeAll,
  expect,
  it,
  onTestFinished,
  vi,
} from 'vite-plus/test';
import DetailsPanels from '@/common/components/DetailsPanels.vue';
import ScreenPanel from '@/common/components/ScreenPanel.vue';
import ScreenPanelsScope from '@/common/components/ScreenPanelsScope.vue';
import {
  ScreenPanelsKey,
  createScreenPanels,
  screenPanels,
} from '@/common/composables/screenPanels';
import {registerShortcut} from '@/common/shortcuts';
import AssistantPanel from './AssistantPanel.vue';
import AssistantToggle from './AssistantToggle.vue';
import {registerAssistantScreen, useAssistantPanel} from './assistant';

vi.mock('@craftcms/ui', async () => ({
  ...(await vi.importActual('@craftcms/ui')),
  t: (message: string) => message,
}));
vi.mock('@inertiajs/vue3', async () => ({
  ...(await vi.importActual('@inertiajs/vue3')),
  usePage: () => ({props: {craft: {currentUser: {id: 1}}}}),
}));
const chat = vi.hoisted(() => ({
  finish: (_first: boolean) => {},
  italic: () => {},
}));
const history = vi.hoisted(() => ({title: 'New chat', refresh: () => {}}));

// The panel loads the chat on demand, and Vue's async loader only unwraps
// `default` from what it can tell is a module.
vi.mock('./AssistantChat.vue', () => ({
  __esModule: true,
  default: defineComponent({
    emits: ['finish'],
    setup: (_, {expose, emit}) => {
      const input = useTemplateRef<HTMLTextAreaElement>('input');
      expose({
        focus: () => input.value?.focus(),
        focusTarget: () => input.value,
      });
      chat.finish = (first) => emit('finish', first);

      // The message box's Markdown field has an italic command on the same keys.
      onMounted(() => {
        const {unregister} = registerShortcut('Mod+I', () => chat.italic(), {
          target: input.value!,
          stopPropagation: true,
        });
        onBeforeUnmount(unregister);
      });

      return () =>
        h('div', {class: 'chat'}, [
          h('div', {class: 'assistant-chat__message-box'}, [
            h('textarea', {ref: 'input'}),
          ]),
        ]);
    },
  }),
}));
vi.mock('./AssistantHistory.vue', () => ({
  default: defineComponent({
    setup: (_, {expose}) => {
      expose({
        refresh: () => history.refresh(),
        get title() {
          return history.title;
        },
      });

      return () => h('div');
    },
  }),
}));

beforeAll(() => setIconResolver(() => nothing));

/** Whether the page has a panel of its own. */
const pagePanel = ref(false);
/** Whether the page's column mirrors its open panel in the URL hash. */
const pageSyncsHash = ref(false);
let app: ReturnType<typeof createApp> | undefined;

async function settle(): Promise<void> {
  for (let i = 0; i < 6; i++) {
    await nextTick();
  }
}

function triggers(): string[] {
  return [...document.querySelectorAll('craft-disclosure > button')].map(
    (button) => button.id
  );
}

async function mount(extra: () => VNode | null = () => null): Promise<void> {
  window.Craft = {systemUid: 'assistant-panel-test'} as never;
  const container = document.createElement('div');
  document.body.append(container);
  app = createApp(
    defineComponent({
      setup() {
        provide(ScreenPanelsKey, screenPanels);

        return () => [
          h(
            'div',
            {class: 'page'},
            screenPanels.panels.value.length
              ? h(DetailsPanels, {
                  registry: screenPanels,
                  syncLocationHash: pageSyncsHash.value,
                })
              : undefined
          ),
          pagePanel.value
            ? h(ScreenPanel, {id: 'info', label: 'Info', icon: 'info'}, () =>
                h('p', 'Info')
              )
            : null,
          h(AssistantPanel),
          extra(),
        ];
      },
    })
  );
  app.mount(container);
  await settle();
}

afterEach(() => {
  vi.useRealTimers();
  history.refresh = () => {};
  app?.unmount();
  app = undefined;
  document.body.innerHTML = '';
  useAssistantPanel().opened.value = false;
  pagePanel.value = false;
  pageSyncsHash.value = false;
  screenPanels.start(null);
});

it('takes the chat’s tab away when it’s closed on a page without panels of its own', async () => {
  await mount();

  useAssistantPanel().show();
  await settle();
  expect(triggers()).toEqual(['details-panel-ai-assistant']);

  useAssistantPanel().toggle();
  await settle();

  expect(triggers()).toEqual([]);
});

it('keeps the chat open from page to page', async () => {
  pagePanel.value = true;
  await mount();
  useAssistantPanel().show();
  await settle();

  pagePanel.value = false;
  await settle();

  expect(triggers()).toEqual(['details-panel-ai-assistant']);
  expect(useAssistantPanel().isOpen.value).toBe(true);
});

it.each([
  {
    pagePanels: true,
    tabs: ['details-panel-info', 'details-panel-ai-assistant'],
  },
  {pagePanels: false, tabs: []},
])(
  'shows the chat’s tab folded, before it’s been opened, only beside the page’s own panels ($pagePanels)',
  async ({pagePanels, tabs}) => {
    pagePanel.value = pagePanels;

    await mount();

    expect(triggers()).toEqual(tabs);
    expect(useAssistantPanel().isOpen.value).toBe(false);
  }
);

it('moves the open chat into the latest editor’s screen, and back once it closes', async () => {
  const editorScreen = createScreenPanels();
  const editorOpen = ref(false);
  pagePanel.value = true;
  await mount(() =>
    editorOpen.value
      ? h('div', {class: 'editor'}, [
          h(DetailsPanels, {registry: editorScreen, idPrefix: 'editor'}),
          h(ScreenPanelsScope, {panels: editorScreen}, () =>
            h(ScreenPanel, {id: 'info', label: 'Info', icon: 'info'}, () =>
              h('p', 'Info')
            )
          ),
        ])
      : null
  );
  useAssistantPanel().show();
  await vi.dynamicImportSettled();
  await settle();
  const chatElement = document.querySelector('.page .chat');
  expect(chatElement).not.toBeNull();

  editorOpen.value = true;
  const unregister = registerAssistantScreen(() => editorScreen, true);
  await settle();

  expect(document.querySelector('.editor .chat')).toBe(chatElement);
  expect(editorScreen.openPanelId.value).toBe('ai-assistant');
  expect(screenPanels.panels.value.map(({id}) => id)).toEqual(['info']);

  unregister();
  editorOpen.value = false;
  await settle();

  expect(document.querySelector('.page .chat')).toBe(chatElement);
  expect(screenPanels.openPanelId.value).toBe('ai-assistant');
});

function pressModI(): void {
  document.activeElement?.dispatchEvent(
    new KeyboardEvent('keydown', {
      key: 'i',
      code: 'KeyI',
      [detectPlatform() === 'mac' ? 'metaKey' : 'ctrlKey']: true,
      bubbles: true,
      cancelable: true,
    })
  );
}

async function focusMessageBoxWithModI(): Promise<HTMLElement> {
  pagePanel.value = true;
  await mount(() => h(AssistantToggle));
  const field = document.createElement('input');
  document.body.append(field);
  field.focus();

  pressModI();
  await vi.dynamicImportSettled();
  await settle();

  expect(document.activeElement).toBe(
    document.querySelector('.assistant-chat__message-box textarea')
  );

  return field;
}

it('folds the chat with Cmd+I straight after Cmd+I focused its message box, sending focus back', async () => {
  chat.italic = vi.fn();
  const field = await focusMessageBoxWithModI();

  pressModI();
  await settle();

  expect(useAssistantPanel().isOpen.value).toBe(false);
  expect(document.activeElement).toBe(field);
  expect(chat.italic).not.toHaveBeenCalled();
});

it.each([
  {
    change: 'typing',
    act: (input: HTMLTextAreaElement) =>
      input.dispatchEvent(new Event('input', {bubbles: true})),
  },
  {
    change: 'moving the cursor',
    act: (input: HTMLTextAreaElement) => {
      input.value = 'ab';
      input.setSelectionRange(1, 1);
    },
  },
])(
  'leaves Cmd+I to the message box’s italic command after $change',
  async ({act}) => {
    chat.italic = vi.fn();
    await focusMessageBoxWithModI();

    act(document.activeElement as HTMLTextAreaElement);
    pressModI();
    await settle();

    expect(chat.italic).toHaveBeenCalledTimes(1);
    expect(useAssistantPanel().isOpen.value).toBe(true);
  }
);

it('leaves the chat unopened once another panel is chosen while it’s folded for lack of room', async () => {
  pagePanel.value = true;
  await mount();
  useAssistantPanel().show();
  await settle();

  screenPanels.setOverlaid(true);
  await settle();
  expect(useAssistantPanel().opened.value).toBe(true);

  screenPanels.open('info');
  await settle();

  expect(useAssistantPanel().opened.value).toBe(false);
});

it('keeps a chat closed in an editor’s screen closed when the page it was folded on widens again', async () => {
  const editorScreen = createScreenPanels();
  const editorOpen = ref(false);
  pagePanel.value = true;
  await mount(() =>
    editorOpen.value
      ? h('div', {class: 'editor'}, [
          h(DetailsPanels, {registry: editorScreen, idPrefix: 'editor'}),
          h(ScreenPanelsScope, {panels: editorScreen}, () =>
            h(ScreenPanel, {id: 'info', label: 'Info', icon: 'info'}, () =>
              h('p', 'Info')
            )
          ),
        ])
      : null
  );
  useAssistantPanel().show();
  await settle();
  screenPanels.setOverlaid(true);
  await settle();

  editorOpen.value = true;
  const unregister = registerAssistantScreen(() => editorScreen, true);
  await settle();
  expect(editorScreen.openPanelId.value).toBe('ai-assistant');
  editorScreen.close('ai-assistant');
  await settle();

  unregister();
  editorOpen.value = false;
  await settle();
  screenPanels.setOverlaid(false);
  await settle();

  expect(screenPanels.openPanelId.value).not.toBe('ai-assistant');
});

it('keeps a chat the URL hash opened closed once it’s closed in an editor’s screen', async () => {
  const editorScreen = createScreenPanels();
  const editorOpen = ref(false);
  window.history.replaceState(null, '', '#ai-assistant');
  onTestFinished(() =>
    window.history.replaceState(null, '', window.location.pathname)
  );
  pagePanel.value = true;
  pageSyncsHash.value = true;
  await mount(() =>
    editorOpen.value
      ? h('div', {class: 'editor'}, [
          h(DetailsPanels, {registry: editorScreen, idPrefix: 'editor'}),
          h(ScreenPanelsScope, {panels: editorScreen}, () =>
            h(ScreenPanel, {id: 'info', label: 'Info', icon: 'info'}, () =>
              h('p', 'Info')
            )
          ),
        ])
      : null
  );
  expect(screenPanels.openPanelId.value).toBe('ai-assistant');

  editorOpen.value = true;
  const unregister = registerAssistantScreen(() => editorScreen, true);
  await settle();
  expect(editorScreen.openPanelId.value).toBe('ai-assistant');
  editorScreen.close('ai-assistant');
  await settle();

  unregister();
  editorOpen.value = false;
  await settle();

  expect(screenPanels.openPanelId.value).toBe('info');
  expect(useAssistantPanel().isOpen.value).toBe(false);
});

it('keeps a folded chat mounted while an editor’s screen has no panels yet', async () => {
  const editorScreen = createScreenPanels();
  const editorOpen = ref(false);
  const editorPanels = ref(false);
  pagePanel.value = true;
  await mount(() =>
    editorOpen.value
      ? h('div', {class: 'editor'}, [
          h(DetailsPanels, {registry: editorScreen, idPrefix: 'editor'}),
          editorPanels.value
            ? h(ScreenPanelsScope, {panels: editorScreen}, () =>
                h(ScreenPanel, {id: 'info', label: 'Info', icon: 'info'}, () =>
                  h('p', 'Info')
                )
              )
            : null,
        ])
      : null
  );
  useAssistantPanel().show();
  await vi.dynamicImportSettled();
  await settle();
  useAssistantPanel().toggle();
  await settle();
  const chatElement = document.querySelector('.chat');
  expect(chatElement).not.toBeNull();

  editorOpen.value = true;
  onTestFinished(registerAssistantScreen(() => editorScreen, true));
  await settle();
  editorPanels.value = true;
  await settle();

  expect(document.querySelector('.chat')).toBe(chatElement);
  expect(editorScreen.panels.value.map(({id}) => id)).toEqual([
    'info',
    'ai-assistant',
  ]);
});

it('mounts the chat the first time it opens, and keeps it once folded', async () => {
  pagePanel.value = true;
  await mount();
  await vi.dynamicImportSettled();
  await settle();
  expect(document.querySelector('.chat')).toBeNull();

  useAssistantPanel().show();
  await vi.dynamicImportSettled();
  await settle();
  expect(document.querySelector('.chat')).not.toBeNull();

  useAssistantPanel().toggle();
  await settle();
  expect(useAssistantPanel().isOpen.value).toBe(false);
  expect(document.querySelector('.chat')).not.toBeNull();
});

it('opens a chat left open before the page reloaded, leaving focus where it was', async () => {
  pagePanel.value = true;
  useAssistantPanel().opened.value = true;

  await mount();

  expect(useAssistantPanel().isOpen.value).toBe(true);
  expect(document.activeElement).toBe(document.body);
});

it.each([
  {first: true, refreshes: 3},
  {first: false, refreshes: 1},
])(
  'looks for a new conversation’s generated title until it arrives ($first)',
  async ({first, refreshes}) => {
    pagePanel.value = true;
    await mount();
    useAssistantPanel().show();
    await vi.dynamicImportSettled();
    await settle();
    vi.useFakeTimers();
    const refresh = vi.fn(() => {
      history.title = refresh.mock.calls.length >= 3 ? 'Generated' : 'Find…';
    });
    history.refresh = refresh;

    chat.finish(first);
    await vi.runAllTimersAsync();

    expect(refresh).toHaveBeenCalledTimes(refreshes);
  }
);
