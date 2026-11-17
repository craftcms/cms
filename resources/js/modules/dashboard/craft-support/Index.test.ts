import {afterEach, expect, it, vi} from 'vite-plus/test';
import {createApp, h, shallowRef, type App} from 'vue';
import CraftSupport from './Index.vue';

const state = vi.hoisted(() => ({
  show: vi.fn(),
  aiAssistant: false,
}));
vi.mock('@/common/composables/useCraftData', async () => {
  const {computed} = await import('vue');
  return {
    default: () => ({
      currentUser: computed(() => ({aiAssistant: state.aiAssistant})),
    }),
  };
});
vi.mock('@/modules/ai/assistant/assistant', () => ({
  useAssistantPanel: () => ({show: state.show}),
}));

let app: App;
let host: HTMLElement;

afterEach(() => {
  app?.unmount();
  host?.remove();
  state.show.mockReset();
});

function mount(aiAssistant: boolean) {
  state.aiAssistant = aiAssistant;
  host = document.createElement('div');
  document.body.append(host);
  app = createApp({
    setup: () => () =>
      h(CraftSupport, {
        data: {
          links: [
            {
              url: 'https://craftcms.com/docs/6.x/',
              label: 'Documentation',
              icon: 'book',
            },
            {
              url: 'https://craftcms.com/contact',
              label: 'Contact Craft Support',
              icon: 'headset',
            },
          ],
        },
      }),
  });
  app.mount(host);
}

const askAi = () =>
  Array.from(host.querySelectorAll('craft-button')).find(
    (button) => button.textContent?.trim() === 'Ask AI'
  );

it('lists its links, opening in a new window', () => {
  mount(false);

  const links = Array.from(host.querySelectorAll('a'));
  expect(links.map((link) => link.textContent?.trim())).toEqual([
    'Documentation',
    'Contact Craft Support',
  ]);
  expect(links.every((link) => link.target === '_blank')).toBe(true);
  expect(
    host.querySelector<HTMLElement & {label: string}>('.support-link__external')
      ?.label
  ).toBe('Opens in a new window');
});

it('opens the AI assistant, when the user can use it', () => {
  mount(true);

  (askAi() as HTMLElement).click();

  expect(state.show).toHaveBeenCalledOnce();
});

it('leaves out Ask AI when the user can’t use the assistant', () => {
  mount(false);

  expect(askAi()).toBeUndefined();
});
