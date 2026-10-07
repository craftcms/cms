import {createApp, defineComponent, h, nextTick, type Component} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {
  createInertiaPageRegistry,
  resolveInertiaPage,
  type InertiaPageComponent,
} from '@/bootstrap/inertia-pages';
import {ownedPageOnly} from './ownedPageOnly';

const Chrome = ownedPageOnly(
  'craft:test',
  defineComponent({
    setup:
      (_props, {slots}) =>
      () =>
        h('div', {class: 'chrome'}, slots.default?.()),
  })
);

/** Stands in for a widget or form node a plugin renders on a page. */
const PluginComponent = defineComponent({
  setup: () => () => h(Chrome, null, () => h('span', 'from the plugin')),
});

async function resolvePage(
  origin: 'core' | 'plugin',
  child: Component = PluginComponent
) {
  const page = defineComponent({
    setup: () => () => h('main', h(child)),
  }) as unknown as InertiaPageComponent;
  const registry = createInertiaPageRegistry();

  if (origin === 'plugin') {
    registry.register('my-plugin/Page', page);

    return resolveInertiaPage('my-plugin/Page', registry, {});
  }

  return resolveInertiaPage('Page', registry, {
    '../pages/Page.vue': () => ({default: page}),
  });
}

const roots: HTMLElement[] = [];

function mount(component: unknown): HTMLElement {
  const root = document.createElement('div');
  document.body.appendChild(root);
  createApp(component as InertiaPageComponent).mount(root);
  roots.push(root);

  return root;
}

afterEach(() => {
  roots.splice(0).forEach((root) => root.remove());
  vi.restoreAllMocks();
});

describe('ownedPageOnly', () => {
  it('renders on a page the plugin registered', async () => {
    const root = mount(await resolvePage('plugin'));
    await nextTick();

    expect(root.querySelector('.chrome')?.textContent).toBe('from the plugin');
  });

  it('refuses a core page, and says why', async () => {
    const warn = vi.spyOn(console, 'warn').mockImplementation(() => {});
    const root = mount(await resolvePage('core'));
    await nextTick();

    expect(root.querySelector('.chrome')).toBeNull();
    expect(warn).toHaveBeenCalledWith(expect.stringContaining("doesn't own"));
  });

  it('is decided by the nearest page, not the outermost', async () => {
    vi.spyOn(console, 'warn').mockImplementation(() => {});

    // A core page rendered inside a plugin page — a slideout opened from it,
    // say — is still a page the plugin doesn't own.
    const corePage = await resolvePage('core');
    const root = mount(await resolvePage('plugin', corePage));
    await nextTick();

    expect(root.querySelector('.chrome')).toBeNull();
  });

  it('survives `createApp()` copying the root page', async () => {
    // The legacy slideout mounts its page as an app root.
    const page = await resolvePage('plugin');
    const root = mount({...page});
    await nextTick();

    expect(root.querySelector('.chrome')).not.toBeNull();
  });

  it('refuses outside any page', async () => {
    vi.spyOn(console, 'warn').mockImplementation(() => {});
    const root = mount(PluginComponent);
    await nextTick();

    expect(root.querySelector('.chrome')).toBeNull();
  });
});
