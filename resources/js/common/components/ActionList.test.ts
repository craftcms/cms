import {createApp, defineComponent, h, nextTick} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import '@craftcms/ui/components/action-item/action-item';
import '@craftcms/ui/components/button/button';
import type {ActionItems} from '@/common/types';
import ActionList from './ActionList.vue';

let teardown: (() => void) | undefined;

type ActionListProps = InstanceType<typeof ActionList>['$props'];

function mount(props: ActionListProps): HTMLElement {
  const container = document.createElement('div');
  document.body.append(container);

  const app = createApp({render: () => h(ActionList, props)});
  app.mount(container);

  teardown = () => {
    app.unmount();
    container.remove();
  };

  return container;
}

afterEach(() => {
  teardown?.();
  teardown = undefined;
});

describe('ActionList', () => {
  it('renders one descriptor as a button and as a menu item', async () => {
    const onClick = vi.fn();
    const actions: ActionItems = [{label: 'New Group', icon: 'plus', onClick}];

    // The whole point of the component: the same descriptor, both shapes.
    const buttons = mount({actions});
    await nextTick();

    const button = buttons.querySelector('craft-button')! as HTMLElement & {
      icon?: string;
    };

    expect(button.icon).toBe('plus');
    expect(button.textContent).toContain('New Group');

    button.dispatchEvent(new Event('click'));
    expect(onClick).toHaveBeenCalledTimes(1);

    teardown!();

    const items = mount({actions, as: 'craft-action-item'});
    await nextTick();

    const item = items.querySelector('craft-action-item')! as HTMLElement & {
      icon?: string;
    };

    expect(item.icon).toBe('plus');
    expect(item.textContent).toContain('New Group');

    item.dispatchEvent(new Event('click'));
    expect(onClick).toHaveBeenCalledTimes(2);
  });

  it('hands the element the action and feedback objects, not attributes', async () => {
    const action = {type: 'http', url: '/admin/actions/thing'} as const;
    const feedback = {success: {message: 'Done'}};
    const container = mount({
      actions: [{label: 'Run', action, feedback}],
    });
    await nextTick();

    // `craft-button` and `craft-action-item` both read these through the
    // `Actionable` mixin, which wants the objects themselves.
    const button = container.querySelector('craft-button')! as HTMLElement & {
      action?: unknown;
      feedback?: unknown;
    };

    expect(button.action).toEqual(action);
    expect(button.feedback).toEqual(feedback);
  });

  it('routes a link action through Inertia rather than the shadow anchor', async () => {
    const container = mount({
      actions: [
        {type: 'link', href: '/admin/settings/sites/new', label: 'New Site'},
      ],
      as: 'craft-action-item',
    });
    await nextTick();

    const item = container.querySelector(
      'craft-action-item'
    )! as HTMLElement & {
      href?: string;
    };

    // The element keeps its `href`, so the shadow anchor still gives real link
    // semantics (middle-click, open-in-new-tab, the status bar). What `CpLink`
    // adds is the click handler on the host that cancels the navigation and
    // makes an Inertia visit of it instead.
    expect(item.href).toBe('/admin/settings/sites/new');
    expect(item.textContent).toContain('New Site');

    const click = new MouseEvent('click', {bubbles: true, cancelable: true});
    item.dispatchEvent(click);

    expect(click.defaultPrevented).toBe(true);
  });

  it('leaves the page for a link marked external', async () => {
    const container = mount({
      actions: [
        {
          type: 'link',
          href: '/admin/entries/new',
          label: 'New Entry',
          external: true,
        },
      ],
      as: 'craft-action-item',
    });
    await nextTick();

    const item = container.querySelector('craft-action-item')!;
    const click = new MouseEvent('click', {bubbles: true, cancelable: true});

    item.dispatchEvent(click);

    // The opt-out for links that hand off to the legacy stack or leave the CP:
    // nothing cancels the navigation, so the browser takes it.
    expect(click.defaultPrevented).toBe(false);
  });

  it('keeps a separator between menu items and drops it between buttons', async () => {
    const actions: ActionItems = [{label: 'One'}, {type: 'hr'}, {label: 'Two'}];

    const items = mount({actions, as: 'craft-action-item'});
    await nextTick();

    expect(items.querySelectorAll('hr')).toHaveLength(1);

    teardown!();

    // A rule between two buttons in a row is just a stray line, so callers
    // don't have to keep a separate list for the button rendering.
    const buttons = mount({actions});
    await nextTick();

    expect(buttons.querySelectorAll('hr')).toHaveLength(0);
    expect(buttons.querySelectorAll('craft-button')).toHaveLength(2);
  });

  it('marks the current item, and gutters the whole list to match', async () => {
    const container = mount({
      actions: [
        {type: 'link', href: '/a', label: 'Volumes', selected: true},
        {type: 'link', href: '/b', label: 'Image Transforms', selected: false},
      ],
      as: 'craft-action-item',
    });
    await nextTick();

    const items = [...container.querySelectorAll('craft-action-item')] as Array<
      HTMLElement & {type?: string; checked?: boolean}
    >;

    // Every item takes the checkmark gutter, not just the current one —
    // otherwise the selected label sits in its own indent.
    expect(items.map((item) => item.type)).toEqual(['checkbox', 'checkbox']);
    expect(items.map((item) => item.checked)).toEqual([true, false]);
  });

  it('leaves a list of commands unguttered', async () => {
    const container = mount({
      actions: [{label: 'Edit'}, {label: 'Delete'}],
      as: 'craft-action-item',
    });
    await nextTick();

    const items = [...container.querySelectorAll('craft-action-item')] as Array<
      HTMLElement & {type?: string}
    >;

    // Nothing claimed to be current, so this is a set of commands.
    expect(items.map((item) => item.type)).toEqual(['button', 'button']);
  });

  it('heads a group with a label, keeping its items siblings', async () => {
    const container = mount({
      actions: [
        {type: 'link', href: '/all', label: 'All entries', selected: false},
        {
          type: 'group',
          heading: 'Channels',
          items: [
            {type: 'link', href: '/blog', label: 'Blog', selected: true},
            {type: 'link', href: '/news', label: 'News', selected: false},
          ],
        },
      ],
      as: 'craft-action-item',
    });
    await nextTick();

    // `ActionList` renders a flat run into whatever contains it, so the
    // container's own children are the list.
    // Siblings, not nested: a menu's roving focus and search filter walk the
    // item list, and a container around the group would hide them from both.
    expect(
      [...container.children].map((child) =>
        child.tagName === 'DIV'
          ? `# ${child.textContent?.trim()}`
          : child.textContent?.trim()
      )
    ).toEqual(['All entries', '# Channels', 'Blog', 'News']);

    // The heading is decoration, so it stays out of the item list entirely.
    expect(
      container.querySelector('.action-list__heading')?.getAttribute('role')
    ).toBe('presentation');
    expect(container.querySelectorAll('craft-action-item')).toHaveLength(3);
  });

  it('marks a grouped item, and gutters the list around it', async () => {
    const container = mount({
      actions: [
        {type: 'link', href: '/all', label: 'All entries', selected: false},
        {
          type: 'group',
          heading: 'Channels',
          items: [{type: 'link', href: '/blog', label: 'Blog', selected: true}],
        },
      ],
      as: 'craft-action-item',
    });
    await nextTick();

    const items = [...container.querySelectorAll('craft-action-item')] as Array<
      HTMLElement & {type?: string; checked?: boolean}
    >;

    // Whether an item is a choice is a property of the list, so a `selected`
    // buried in a group still gutters the ungrouped items above it.
    expect(items.map((item) => item.type)).toEqual(['checkbox', 'checkbox']);
    expect(items.map((item) => item.checked)).toEqual([false, true]);
  });

  it('renders a display action as its component', async () => {
    const container = mount({
      actions: [
        {
          type: 'display',
          is: defineComponent({render: () => h('p', 'Ten selected')}),
        },
      ],
    });
    await nextTick();

    expect(container.querySelector('p')?.textContent).toBe('Ten selected');
  });
});
