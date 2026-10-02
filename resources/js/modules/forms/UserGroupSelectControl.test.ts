import {createApp, h, nextTick, shallowRef} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import UserGroupSelectControl from './UserGroupSelectControl.vue';
import type {FormControlPayload} from './types';

const state = vi.hoisted(() => ({
  open: vi.fn(),
}));

vi.mock('@/common/slideouts', () => ({
  useSlideoutOpener: () => ({open: state.open}),
}));

const editorsUid = '11111111-1111-4111-8111-111111111111';
const publishersUid = '22222222-2222-4222-8222-222222222222';
const reviewersUid = '33333333-3333-4333-8333-333333333333';
type UserGroupSelectProps = {
  canCreate: boolean;
  groups: Array<{
    id: number;
    uid: string;
    name: string;
    handle: string;
    description: string | null;
  }>;
};
const control: FormControlPayload<UserGroupSelectProps> = {
  type: 'CraftCms\\Cms\\Form\\Controls\\UserGroupSelect',
  component: 'craft:user-group-select',
  props: {
    canCreate: true,
    groups: [
      {
        id: 10,
        uid: editorsUid,
        name: 'Editors',
        handle: 'editors',
        description: 'Reviews editorial content.',
      },
      {
        id: 20,
        uid: publishersUid,
        name: 'Publishers',
        handle: 'publishers',
        description: null,
      },
    ],
  },
  path: ['settings', 'userGroups'],
  mode: 'editable',
  deltaGroup: ['settings', 'userGroups'],
};

describe('UserGroupSelectControl', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  beforeEach(() => {
    vi.stubGlobal(
      'fetch',
      vi.fn(async () => new Response('<svg></svg>'))
    );
    state.open.mockReset();
  });

  afterEach(() => {
    app?.unmount();
    container?.remove();
    vi.unstubAllGlobals();
  });

  async function mount(editable = true) {
    const value = shallowRef<string[]>([editorsUid]);
    const updates: Array<{value: string[]; kind: string}> = [];
    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () =>
        h(UserGroupSelectControl, {
          control,
          value: value.value,
          label: 'Reviewer groups',
          editable,
          invalid: false,
          required: true,
          'onUpdate:value': (updated: string[], kind: string) => {
            value.value = updated;
            updates.push({value: updated, kind});
          },
        }),
    });
    app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
    app.mount(container);
    await nextTick();

    return updates;
  }

  it('adapts stored UIDs to the shared numeric-ID selector', async () => {
    const updates = await mount();
    const group = container!.querySelector('[role="group"]')!;
    const selectedChip = container!.querySelector('craft-chip')!;

    expect(group.getAttribute('aria-label')).toBe('Reviewer groups');
    expect(selectedChip.textContent).toContain('Editors');

    const publisher = [
      ...container!.querySelectorAll<HTMLElement>('craft-action-item'),
    ].find((item) => item.textContent?.includes('Publishers'))!;
    publisher.click();
    await nextTick();

    expect(updates.at(-1)).toEqual({
      value: [editorsUid, publishersUid],
      kind: 'discrete',
    });
    expect(
      [
        ...container!.querySelectorAll<HTMLInputElement>(
          'input[type="hidden"]'
        ),
      ].map((input) => [input.name, input.value])
    ).toEqual([
      ['settings[userGroups]', ''],
      ['settings[userGroups][]', editorsUid],
      ['settings[userGroups][]', publishersUid],
    ]);
  });

  it('makes a newly created group available without reloading the form', async () => {
    const updates = await mount();
    const createButton = [
      ...container!.querySelectorAll<HTMLElement>('craft-button'),
    ].find((button) => button.textContent?.trim() === 'Create')!;

    createButton.click();
    await nextTick();

    const openCall = state.open.mock.calls[0];
    expect(openCall?.[0]).toContain('/settings/users/groups/new');
    openCall?.[1].onSaved({
      data: {
        group: {
          id: 30,
          uid: reviewersUid,
          name: 'Reviewers',
          handle: 'reviewers',
          description: 'Reviews content.',
        },
      },
    });
    await nextTick();

    const reviewers = [
      ...container!.querySelectorAll<HTMLElement>('craft-action-item'),
    ].find((item) => item.textContent?.includes('Reviewers'))!;
    reviewers.click();
    await nextTick();

    expect(updates.at(-1)).toEqual({
      value: [editorsUid, reviewersUid],
      kind: 'discrete',
    });
  });

  it('shows selected groups without posting values in non-editable mode', async () => {
    await mount(false);

    expect(container!.querySelector('craft-chip')?.textContent).toContain(
      'Editors'
    );
    expect(container!.querySelector('craft-action-menu')).toBeNull();
    expect(container!.querySelector('input[name]')).toBeNull();
  });
});
