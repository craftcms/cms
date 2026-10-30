import {setIconResolver} from '@craftcms/ui/utilities/icons';
import {nothing} from 'lit';
import {createApp, defineComponent, h, nextTick, provide, ref} from 'vue';
import {afterEach, beforeAll, expect, it, vi} from 'vite-plus/test';
import DetailsPanels from '@/common/components/DetailsPanels.vue';
import ScreenPanel from '@/common/components/ScreenPanel.vue';
import {ScreenPanelsKey, screenPanels} from '@/common/composables/screenPanels';
import AssistantPanel from './AssistantPanel.vue';
import {useAssistantPanel} from './assistant';

vi.mock('@craftcms/ui', async () => ({
  ...(await vi.importActual('@craftcms/ui')),
  t: (message: string) => message,
}));
vi.mock('@inertiajs/vue3', async () => ({
  ...(await vi.importActual('@inertiajs/vue3')),
  usePage: () => ({props: {craft: {currentUser: {id: 1}}}}),
}));
const chat = vi.hoisted(() => ({finish: (_first: boolean) => {}}));
const history = vi.hoisted(() => ({title: 'New chat', refresh: () => {}}));

vi.mock('./AssistantChat.vue', () => ({
  default: defineComponent({
    emits: ['finish'],
    setup: (_, {expose, emit}) => {
      expose({focus: () => {}, focusTarget: () => null});
      chat.finish = (first) => emit('finish', first);

      return () => h('div', {class: 'chat'});
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

async function mount(): Promise<void> {
  window.Craft = {systemUid: 'assistant-panel-test'} as never;
  const container = document.createElement('div');
  document.body.append(container);
  app = createApp(
    defineComponent({
      setup() {
        provide(ScreenPanelsKey, screenPanels);

        return () => [
          screenPanels.panels.value.length
            ? h(DetailsPanels, {registry: screenPanels})
            : null,
          pagePanel.value
            ? h(ScreenPanel, {id: 'info', label: 'Info', icon: 'info'}, () =>
                h('p', 'Info')
              )
            : null,
          h(AssistantPanel),
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
