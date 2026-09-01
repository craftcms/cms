import {createApp, defineComponent, h, inject, nextTick, provide} from 'vue';
import {afterEach, describe, expect, it} from 'vitest';
import LayoutSlot from '@/common/components/LayoutSlot.vue';
import LayoutSlotOutlet from '@/common/components/LayoutSlotOutlet.vue';
import {
  createLayoutSlotRegistry,
  provideLayoutSlotRegistry,
} from './layoutSlots';
import {ScreenShellKey} from './screen';

const mounted: Array<{unmount(): void; root: HTMLElement}> = [];

function mount(component: any): HTMLElement {
  const root = document.createElement('div');
  document.body.appendChild(root);
  const app = createApp(component);
  app.mount(root);
  mounted.push({unmount: () => app.unmount(), root});

  return root;
}

afterEach(() => {
  while (mounted.length) {
    const entry = mounted.pop()!;
    entry.unmount();
    entry.root.remove();
  }
});

/**
 * A minimal stand-in for a screen shell: owns a registry and renders one
 * outlet, the way `PageScreen`/`SlideoutScreen` do.
 */
const Shell = defineComponent({
  props: {
    scope: {type: String, required: true},
    slot: {type: String, default: 'details'},
  },
  setup(props, {slots}) {
    provideLayoutSlotRegistry(props.scope);

    return () =>
      h('div', {class: `shell shell--${props.scope}`}, [
        h(LayoutSlotOutlet, {name: props.slot}, () =>
          h('span', {class: 'fallback'}, 'fallback')
        ),
        slots.default?.(),
      ]);
  },
});

describe('LayoutSlotRegistry', () => {
  it('counts registrations so repeated slots survive one unregister', () => {
    const registry = createLayoutSlotRegistry('a');

    expect(registry.has('details')).toBe(false);

    registry.register('details');
    registry.register('details');
    registry.unregister('details');

    expect(registry.has('details')).toBe(true);

    registry.unregister('details');

    expect(registry.has('details')).toBe(false);
  });

  it('does not go negative when unregistered more than registered', () => {
    const registry = createLayoutSlotRegistry('a');
    registry.unregister('details');
    registry.register('details');

    expect(registry.has('details')).toBe(true);
  });

  it('keeps separate scopes independent', () => {
    const base = createLayoutSlotRegistry('base');
    const slideout = createLayoutSlotRegistry('slideout');

    slideout.register('details');

    expect(slideout.has('details')).toBe(true);
    expect(base.has('details')).toBe(false);
  });

  it('generates a unique scope when none is given', () => {
    expect(createLayoutSlotRegistry().scope).not.toBe(
      createLayoutSlotRegistry().scope
    );
  });
});

/**
 * The scoped registry depends on Vue resolving `inject` through slot content
 * via the *rendering* parent rather than the lexical owner.
 *
 * A page written as `<AppLayout><LayoutSlot/></AppLayout>` compiles the
 * `<LayoutSlot>` vnode in the page's scope, but it must inject the registry
 * that `AppLayout`'s shell provides — otherwise a page rendered inside a
 * slideout would register against, and teleport into, the base page's outlets.
 */
describe('inject through slot content', () => {
  const Key = Symbol('registry');

  const Consumer = defineComponent({
    setup() {
      const value = inject<string>(Key, 'none');
      return () => h('span', {class: 'consumer'}, value);
    },
  });

  const Provider = defineComponent({
    props: {scope: {type: String, required: true}},
    setup(props, {slots}) {
      provide(Key, props.scope);
      return () => h('div', slots.default?.());
    },
  });

  it('resolves to the shell that renders the slot, not the page that owns it', () => {
    // The page provides its own value, so a lexical-owner resolution would
    // return 'page' and a rendering-parent resolution returns 'shell'.
    const Page = defineComponent({
      setup() {
        provide(Key, 'page');
        return () => h(Provider, {scope: 'shell'}, () => h(Consumer));
      },
    });

    expect(mount(Page).querySelector('.consumer')?.textContent).toBe('shell');
  });

  it('resolves to the nearest shell when shells are nested', () => {
    const Page = defineComponent({
      setup() {
        return () =>
          h(Provider, {scope: 'outer'}, () => [
            h(Consumer),
            h(Provider, {scope: 'inner'}, () => h(Consumer)),
          ]);
      },
    });

    const consumers = Array.from(mount(Page).querySelectorAll('.consumer')).map(
      (el) => el.textContent
    );

    expect(consumers).toEqual(['outer', 'inner']);
  });

  it('falls back to the default when no shell provides one', () => {
    const Page = defineComponent({setup: () => () => h(Consumer)});

    expect(mount(Page).querySelector('.consumer')?.textContent).toBe('none');
  });
});

describe('LayoutSlot and LayoutSlotOutlet', () => {
  it('teleports page content into the enclosing shell and hides the fallback', async () => {
    const Page = defineComponent({
      setup: () => () =>
        h(Shell, {scope: 'page'}, () =>
          h(LayoutSlot, {name: 'details'}, () =>
            h('span', {class: 'filled'}, 'from the page')
          )
        ),
    });

    const root = mount(Page);
    await nextTick();

    const outlet = root.querySelector('[data-layout-slot="details"]')!;

    expect(outlet.getAttribute('data-layout-scope')).toBe('page');
    expect(outlet.querySelector('.filled')?.textContent).toBe('from the page');
    expect(root.querySelector('.fallback')).toBeNull();
  });

  it('renders the fallback while the slot is unfilled', async () => {
    const Page = defineComponent({
      setup: () => () => h(Shell, {scope: 'page'}),
    });

    const root = mount(Page);
    await nextTick();

    expect(root.querySelector('.fallback')?.textContent).toBe('fallback');
    // The outlet must stay in the DOM even when empty — it's a teleport
    // target, so removing it would race content mounting into it.
    expect(root.querySelector('[data-layout-slot="details"]')).not.toBeNull();
  });

  it('keeps two live shells from stealing each other content', async () => {
    // The slideout case: a second shell mounts while the first stays put.
    // An unscoped `[data-layout-slot=…]` selector would match the base
    // shell's outlet first and teleport the slideout's content behind it.
    const Page = defineComponent({
      setup: () => () => [
        h(Shell, {scope: 'base'}),
        h(Shell, {scope: 'slideout'}, () =>
          h(LayoutSlot, {name: 'details'}, () =>
            h('span', {class: 'filled'}, 'from the slideout')
          )
        ),
      ],
    });

    const root = mount(Page);
    await nextTick();

    const base = root.querySelector('.shell--base')!;
    const slideout = root.querySelector('.shell--slideout')!;

    expect(slideout.querySelector('.filled')?.textContent).toBe(
      'from the slideout'
    );
    expect(base.querySelector('.filled')).toBeNull();

    // The base shell must still show its own fallback — a shared registry
    // would have marked its `details` outlet filled too.
    expect(base.querySelector('.fallback')).not.toBeNull();
    expect(slideout.querySelector('.fallback')).toBeNull();
  });

  it('releases the outlet when the page content unmounts', async () => {
    const Page = defineComponent({
      props: {show: {type: Boolean, default: true}},
      setup: (props) => () =>
        h(Shell, {scope: 'page'}, () =>
          props.show
            ? h(LayoutSlot, {name: 'details'}, () =>
                h('span', {class: 'filled'})
              )
            : null
        ),
    });

    const root = document.createElement('div');
    document.body.appendChild(root);
    const app = createApp(Page, {show: true});
    const instance: any = app.mount(root);
    mounted.push({unmount: () => app.unmount(), root});

    await nextTick();
    expect(root.querySelector('.fallback')).toBeNull();

    instance.$.props.show = false;
    await nextTick();

    expect(root.querySelector('.filled')).toBeNull();
    expect(root.querySelector('.fallback')).not.toBeNull();
  });
});

describe('AppLayout dispatcher', () => {
  // The real shells drag in the whole CP (Inertia page props, web components,
  // the global sidebar); the dispatcher's job is only to pick one and forward
  // to it, so stand-ins make that observable on its own.
  const FakeShell = (name: string) =>
    defineComponent({
      setup:
        (_props, {slots}) =>
        () =>
          h('div', {class: `fake fake--${name}`}, [
            h('div', {class: 'slot-default'}, slots.default?.()),
            h('div', {class: 'slot-details'}, slots.details?.()),
          ]),
    });

  async function renderAppLayout(shell?: any) {
    const AppLayout = (await import('@/common/layouts/AppLayout.vue')).default;

    const Page = defineComponent({
      setup() {
        if (shell) {
          provide(ScreenShellKey, shell);
        }

        return () =>
          h(AppLayout, null, {
            default: () => h('span', {class: 'content'}, 'content'),
            details: () => h('span', {class: 'detail'}, 'detail'),
          });
      },
    });

    const root = mount(Page);
    await nextTick();

    return root;
  }

  it('renders the provided shell and forwards every slot it was given', async () => {
    const root = await renderAppLayout(FakeShell('slideout'));

    expect(root.querySelector('.fake--slideout')).not.toBeNull();
    expect(root.querySelector('.slot-default .content')?.textContent).toBe(
      'content'
    );
    expect(root.querySelector('.slot-details .detail')?.textContent).toBe(
      'detail'
    );
  });

  it('forwards only the slots the page actually passed', async () => {
    const Recorder = defineComponent({
      setup:
        (_props, {slots}) =>
        () =>
          h('div', {class: 'recorder'}, Object.keys(slots).join(',')),
    });

    const root = await renderAppLayout(Recorder);

    // Shells branch on `Boolean(slots.x)` to decide which regions to show, so
    // forwarding a function for every declared slot would light them all up.
    expect(root.querySelector('.recorder')?.textContent).toBe(
      'default,details'
    );
  });
});
