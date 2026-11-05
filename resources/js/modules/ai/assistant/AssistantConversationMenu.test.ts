import {createApp, nextTick} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import AssistantConversationMenu from './AssistantConversationMenu.vue';

const currentUser: Record<string, unknown> = {};

vi.mock('@craftcms/ui', async () => ({
  ...(await vi.importActual('@craftcms/ui')),
  t: (message: string) => message,
}));
vi.mock('@inertiajs/vue3', async () => ({
  ...(await vi.importActual('@inertiajs/vue3')),
  usePage: () => ({props: {craft: {currentUser}}}),
}));

let unmount = () => {};

afterEach(() => {
  unmount();
  document.body.innerHTML = '';
});

/** Mounts the menu, and returns its items’ labels and links, or `null` when there’s no menu. */
async function menu(
  stored: boolean
): Promise<Array<{label: string; href: string | null}> | null> {
  const host = document.createElement('div');
  document.body.append(host);
  const app = createApp(AssistantConversationMenu, {
    threadId: 'thread-1',
    title: 'Teaser writing',
    stored,
  });
  app.mount(host);
  unmount = () => app.unmount();
  await nextTick();

  const element = host.querySelector('craft-action-menu');

  return element
    ? [...element.querySelectorAll('craft-action-item')].map((item) => ({
        label: item.textContent?.trim() ?? '',
        href: (item as HTMLElement & {href?: string}).href || null,
      }))
    : null;
}

it('links to where guidance and skills are edited, beside the conversation’s own actions', async () => {
  Object.assign(currentUser, {
    personalGuidanceUrl: '/admin/myaccount/ai-assistant',
    aiGuidanceUrl: '/admin/utilities/ai-guidance',
    aiSkillsUrl: '/admin/utilities/ai-guidance/skills',
  });

  expect(await menu(true)).toEqual([
    {label: 'Personal guidance', href: '/admin/myaccount/ai-assistant'},
    {label: 'Site guidance', href: '/admin/utilities/ai-guidance'},
    {label: 'Skills', href: '/admin/utilities/ai-guidance/skills'},
    {label: 'Rename…', href: null},
    {label: 'Delete', href: null},
  ]);
});

it('only links to the utility for users who can use it, and only offers the links before a conversation is stored', async () => {
  Object.assign(currentUser, {
    personalGuidanceUrl: '/admin/myaccount/ai-assistant',
    aiGuidanceUrl: null,
    aiSkillsUrl: null,
  });

  expect(await menu(false)).toEqual([
    {label: 'Personal guidance', href: '/admin/myaccount/ai-assistant'},
  ]);
});
