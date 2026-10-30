import {afterEach, expect, it, vi} from 'vite-plus/test';
import {createApp, h, nextTick, type App} from 'vue';
import AiSkills from './AiSkills.vue';
import type {AgentSkillData} from './types';

vi.mock('@/common/composables/useAppLayout', () => ({useAppLayout: () => {}}));

let app: App | null = null;

afterEach(() => {
  app?.unmount();
  app = null;
  document.body.innerHTML = '';
});

async function mount(skills: AgentSkillData[]): Promise<HTMLElement> {
  const container = document.createElement('div');
  document.body.append(container);
  app = createApp(() =>
    h(AiSkills, {skills, createSkillUrl: '/skills/new', subnav: []})
  );
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);
  await nextTick();

  return container;
}

function newSkillButton(container: HTMLElement): HTMLElement | undefined {
  return [...container.querySelectorAll<HTMLElement>('craft-button')].find(
    (button) => button.textContent?.trim() === 'New skill'
  );
}

it('offers New skill under the message when there are no skills', async () => {
  const container = await mount([]);

  expect(newSkillButton(container)?.closest('craft-empty')).not.toBeNull();
});

it('offers New skill after the skills when there are some', async () => {
  const container = await mount([
    {
      id: 1,
      title: 'Write alt text',
      description: 'Describes images.',
      body: '',
      editUrl: '/skills/1',
    },
  ]);
  const button = newSkillButton(container)!;
  const list = container.querySelector('ul')!;

  expect(container.querySelector('craft-empty')).toBeNull();
  expect(
    list.compareDocumentPosition(button) & Node.DOCUMENT_POSITION_FOLLOWING
  ).toBeTruthy();
});
