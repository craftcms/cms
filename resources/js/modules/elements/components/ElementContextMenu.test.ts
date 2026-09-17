import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {createApp, h, type App} from 'vue';
import type {ActionItem} from '@/common/types';
import ElementContextMenu from './ElementContextMenu.vue';

const rendered = vi.hoisted(() => ({actions: [] as Array<ActionItem>}));

vi.mock('@/common/components/ActionMenu.vue', () => ({
  default: {
    props: ['actions', 'label', 'icon'],
    setup(props: {actions: Array<ActionItem>}) {
      return () => {
        rendered.actions = props.actions;
        return null;
      };
    },
  },
}));

let app: App | undefined;

afterEach(() => {
  app?.unmount();
  rendered.actions = [];
});

function mount(items: Array<Record<string, unknown>>): Array<ActionItem> {
  app = createApp({
    render: () => h(ElementContextMenu, {label: 'Revisions', items}),
  });
  app.mount(document.createElement('div'));
  return rendered.actions;
}

describe('ElementContextMenu', () => {
  it('groups the links under each heading', () => {
    const actions = mount([
      {type: 'link', label: 'Current', href: '/current', selected: true},
      {type: 'heading', label: 'Recent Revisions'},
      {type: 'link', label: 'Revision 2', href: '/r2'},
      {type: 'link', label: 'Revision 1', href: '/r1'},
      {type: 'hr'},
      {type: 'link', label: 'View all revisions', href: '/all'},
    ]);

    expect(actions).toEqual([
      {type: 'link', label: 'Current', href: '/current', variant: 'accent'},
      {
        type: 'group',
        heading: 'Recent Revisions',
        items: [
          {type: 'link', label: 'Revision 2', href: '/r2', variant: undefined},
          {type: 'link', label: 'Revision 1', href: '/r1', variant: undefined},
        ],
      },
      {type: 'hr'},
      {
        type: 'link',
        label: 'View all revisions',
        href: '/all',
        variant: undefined,
      },
    ]);
  });

  it('starts a new group at each heading', () => {
    const actions = mount([
      {type: 'heading', label: 'Drafts'},
      {type: 'link', label: 'Draft 1', href: '/d1'},
      {type: 'heading', label: 'Revisions'},
      {type: 'link', label: 'Revision 1', href: '/r1'},
    ]);

    expect(
      actions.map((action) =>
        action.type === 'group'
          ? [action.heading, action.items.map((item) => item.label)]
          : action.type
      )
    ).toEqual([
      ['Drafts', ['Draft 1']],
      ['Revisions', ['Revision 1']],
    ]);
  });
});
