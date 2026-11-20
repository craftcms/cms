import '../../../../../css/cp.css';
import '@craftcms/ui/components/button/button';
import '@craftcms/ui/components/pane/pane';
import '@craftcms/ui/components/popover/popover';
import {createApp, h, type App} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {page, userEvent} from 'vite-plus/test/browser/context';
import type {ConditionConfig, GroupConfig} from '@/modules/conditions/types';
import ElementIndexFilterBar from './ElementIndexFilterBar.vue';

vi.mock('@inertiajs/vue3', async () => ({
  ...(await vi.importActual('@inertiajs/vue3')),
  usePage: () => ({props: {elementType: 'Entry', source: {key: '*'}}}),
  // The builder loads at once, with whatever condition it was given.
  useHttp: () => {
    let payload: () => Record<string, unknown> = () => ({});

    return {
      processing: false,
      transform: (fn: typeof payload) => (payload = fn),
      post: (_url: string, {onSuccess}: {onSuccess: (data: unknown) => void}) =>
        onSuccess({
          builder: {value: payload().conditionConfig},
          headHtml: '',
          bodyHtml: '',
        }),
    };
  },
}));

vi.mock('@/common/composables/useAnnouncer', () => ({
  useAnnouncer: () => ({announce: () => {}}),
}));

// Stands in for the rule's own fields, which come from the server.
vi.mock('@/modules/conditions/ConditionBuilder.vue', () => ({
  default: {render: () => h('input', {'aria-label': 'Rule value'})},
}));

const condition: ConditionConfig = {
  class: 'EntryCondition',
  conditionRules: {
    operator: 'and' as GroupConfig['operator'],
    rules: [
      {class: 'TypeRule', uid: 'type'},
      {class: 'HasUrlRule', uid: 'url'},
      {
        operator: 'or' as GroupConfig['operator'],
        rules: [
          {class: 'TitleRule', uid: 'title'},
          {class: 'SlugRule', uid: 'slug'},
        ],
      },
    ],
  },
};

let app: App | undefined;

afterEach(() => {
  app?.unmount();
  app = undefined;
  document.body.innerHTML = '';
});

function mount() {
  const container = document.createElement('div');
  document.body.append(container);
  app = createApp({
    render: () =>
      h(ElementIndexFilterBar, {
        condition,
        summaries: [
          {uid: 'type', label: 'Entry Type', ui: null},
          {uid: 'url', label: 'Has URL', ui: null},
        ],
      }),
  });
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);
}

function panel(): (HTMLElement & {open: boolean}) | null {
  return document.querySelector('craft-popover.condition-popover');
}

function focusedName(): string {
  const focused = document.activeElement as HTMLElement | null;

  return (
    focused?.getAttribute('aria-label') ?? focused?.textContent?.trim() ?? ''
  );
}

async function openEditor(name: string): Promise<void> {
  await userEvent.click(page.getByRole('button', {name}));
  await expect.poll(() => panel()?.open).toBe(true);
  await expect
    .poll(() => panel()?.querySelector('[aria-label="Rule value"]'))
    .toBeTruthy();
}

it.each([
  {chip: 'a filter', name: 'Edit Entry Type', remove: 'Remove Entry Type'},
  {
    chip: 'a grouped filter',
    name: 'Edit Any of 2 filters',
    remove: 'Remove Any of 2 filters',
  },
])(
  'moves Tab from $chip into its editing panel, through each control, then on to its remove button',
  async ({name, remove}) => {
    mount();
    await openEditor(name);

    const reached: string[] = [];
    for (let i = 0; i < 10 && focusedName() !== remove; i++) {
      await userEvent.keyboard('{Tab}');
      if (focusedName() !== remove) {
        expect(panel()?.contains(document.activeElement)).toBe(true);
        expect(panel()?.open).toBe(true);
        reached.push(focusedName());
      }
    }

    expect(reached).toEqual(
      expect.arrayContaining([
        'Close',
        'Rule value',
        'Remove',
        'Cancel',
        'Apply',
      ])
    );
    expect(focusedName()).toBe(remove);
  }
);

it('returns to the filter with Shift+Tab from its panel, keeping the panel open', async () => {
  mount();
  await openEditor('Edit Entry Type');

  await userEvent.keyboard('{Tab}');
  await userEvent.keyboard('{Shift>}{Tab}{/Shift}');

  expect(focusedName()).toBe('Edit Entry Type');
  expect(panel()?.open).toBe(true);
});
