import {createApp, defineComponent, h, nextTick, ref} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import type {ActionItems} from '@/common/types';
import ActionMenu from './ActionMenu.vue';

let teardown: (() => void) | undefined;

function mount(actions: ActionItems | ReturnType<typeof ref<ActionItems>>) {
  const current = ref(actions) as ReturnType<typeof ref<ActionItems>>;
  const container = document.createElement('div');
  document.body.append(container);

  const app = createApp({
    render: () => h(ActionMenu, {actions: current.value as ActionItems}),
  });
  app.mount(container);

  teardown = () => {
    app.unmount();
    container.remove();
  };

  return {
    container,
    menu: container.querySelector('craft-action-menu')!,
    set: (next: ActionItems) => {
      current.value = next;
    },
  };
}

function labels(menu: Element): string[] {
  return [...menu.querySelectorAll('craft-action-item')].map((item) =>
    item.textContent?.trim()
  ) as string[];
}

function visibleLabels(menu: Element): string[] {
  return [...menu.querySelectorAll('craft-action-item:not([hidden])')].map(
    (item) => item.textContent?.trim()
  ) as string[];
}

afterEach(() => {
  teardown?.();
  teardown = undefined;
});

describe('ActionMenu', () => {
  it('renders its items itself, into a wrapper it owns', async () => {
    const {menu} = mount([{label: 'Edit'}, {label: 'Duplicate'}]);
    await nextTick();

    // `craft-popover` auto-wraps unslotted children into a container it makes
    // once and appends. Owning the wrapper stops that from running, which is
    // what lets Vue keep patching the list afterwards.
    const wrappers = menu.querySelectorAll(':scope > [slot="content"]');

    expect(wrappers).toHaveLength(1);
    expect(labels(wrappers[0]!)).toEqual(['Edit', 'Duplicate']);
  });

  it('keeps a later item inside that wrapper', async () => {
    const {menu, set} = mount([{label: 'Edit'}]);
    await nextTick();

    set([{label: 'Edit'}, {label: 'Duplicate'}]);
    await nextTick();

    expect(menu.querySelectorAll(':scope > [slot="content"]')).toHaveLength(1);
    expect(labels(menu.querySelector(':scope > [slot="content"]')!)).toEqual([
      'Edit',
      'Duplicate',
    ]);
  });

  it('only shows actions that are currently available', async () => {
    const {menu, set} = mount([
      {label: 'Collapse'},
      {label: 'Expand', hidden: true},
    ]);
    await nextTick();

    expect(visibleLabels(menu)).toEqual(['Collapse']);

    set([{label: 'Collapse', hidden: true}, {label: 'Expand'}]);
    await nextTick();

    expect(visibleLabels(menu)).toEqual(['Expand']);
  });

  it('sinks destructive items to the bottom, stably', async () => {
    const {menu} = mount([
      {label: 'Delete', variant: 'danger'},
      {label: 'Edit'},
      {label: 'Remove', variant: 'danger'},
      {label: 'Duplicate'},
    ]);
    await nextTick();

    // `craft-action-menu` does this for the menus it builds from `actions`,
    // and this one is slotted — so the convention is applied here instead.
    expect(labels(menu)).toEqual(['Edit', 'Duplicate', 'Delete', 'Remove']);
  });

  describe('invoker', () => {
    function mountWithInvoker(slots?: Record<string, unknown>) {
      const container = document.createElement('div');
      document.body.append(container);

      const app = createApp({
        render: () =>
          h(
            ActionMenu,
            {actions: [{label: 'Edit'}], label: 'Entry actions'},
            slots
          ),
      });
      app.mount(container);

      teardown = () => {
        app.unmount();
        container.remove();
      };

      return container.querySelector('craft-action-menu')! as HTMLElement & {
        updateComplete: Promise<unknown>;
      };
    }

    // `craft-action-menu` puts its ARIA wiring on whatever is assigned to its
    // `invoker` slot, so that has to be the focusable button itself rather
    // than something wrapping it.
    async function expectWiredInvoker(
      menu: HTMLElement & {updateComplete: Promise<unknown>}
    ) {
      await nextTick();
      await menu.updateComplete;

      const invokers = menu.querySelectorAll(':scope > [slot="invoker"]');
      expect(invokers).toHaveLength(1);

      const invoker = invokers[0]!;
      expect(invoker.tagName).toBe('CRAFT-BUTTON');
      expect(invoker.id).toMatch(/^invoker-/);
      expect(invoker.getAttribute('aria-controls')).toMatch(/^content-/);
      expect(invoker.getAttribute('aria-haspopup')).toBe('true');
      expect(invoker.getAttribute('aria-expanded')).toBe('false');

      return invoker;
    }

    it('wires up the default invoker', async () => {
      const invoker = await expectWiredInvoker(mountWithInvoker());

      expect(invoker.getAttribute('aria-label')).toBe('Entry actions');
    });

    it('wires up a custom invoker', async () => {
      const invoker = await expectWiredInvoker(
        mountWithInvoker({
          invoker: ({attributes}: {attributes: Record<string, string>}) =>
            h('craft-button', {...attributes, type: 'button'}, 'Custom'),
        })
      );

      expect(invoker.textContent).toBe('Custom');
    });
  });

  it('renders a display item as its component', async () => {
    const {menu} = mount([
      {
        type: 'display',
        is: defineComponent({render: () => h('div', 'Sections')}),
      },
      {label: 'Blog'},
    ]);
    await nextTick();

    expect(menu.querySelector('[slot="content"] div')?.textContent).toBe(
      'Sections'
    );
  });
});
