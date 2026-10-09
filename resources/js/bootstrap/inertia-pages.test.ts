import {defineComponent} from 'vue';
import {describe, expect, it} from 'vite-plus/test';
import {
  createInertiaPageRegistry,
  resolveCoreInertiaPage,
  resolveInertiaPage,
  type InertiaPageGlob,
} from './inertia-pages';

const settingsPage = defineComponent({name: 'SettingsPage'});
const alternateSettingsPage = defineComponent({name: 'AlternateSettingsPage'});
const sitesPage = defineComponent({name: 'SitesPage'});

describe('Inertia page registry', () => {
  it('registers and resolves plugin page components', async () => {
    const registry = createInertiaPageRegistry();

    registry.register('test-plugin::Settings', settingsPage);

    await expect(registry.resolve('test-plugin::Settings')).resolves.toBe(
      settingsPage
    );
  });

  it('registers and resolves plugin page loaders', async () => {
    const registry = createInertiaPageRegistry();

    registry.register('test-plugin::Settings', async () => ({
      default: settingsPage,
    }));

    await expect(registry.resolve('test-plugin::Settings')).resolves.toBe(
      settingsPage
    );
  });

  it('allows duplicate registrations with the same value', () => {
    const registry = createInertiaPageRegistry();

    registry.register('test-plugin::Settings', settingsPage);

    expect(() => {
      registry.register('test-plugin::Settings', settingsPage);
    }).not.toThrow();
  });

  it('fails duplicate registrations with a different value', () => {
    const registry = createInertiaPageRegistry();

    registry.register('test-plugin::Settings', settingsPage);

    expect(() => {
      registry.register('test-plugin::Settings', alternateSettingsPage);
    }).toThrow('Inertia page already registered: test-plugin::Settings');
  });

  it('resolves core pages with the pages shorthand naming convention', async () => {
    const corePages: InertiaPageGlob = {
      '../pages/settings/sites/Index.vue': async () => ({
        default: sitesPage,
      }),
    };

    await expect(
      resolveCoreInertiaPage('settings/sites/Index', corePages)
    ).resolves.toBe(sitesPage);
  });

  it('falls back to the registry when a core page is missing', async () => {
    const registry = createInertiaPageRegistry();

    registry.register('test-plugin::Settings', settingsPage);

    await expect(
      resolveInertiaPage('test-plugin::Settings', registry)
    ).resolves.toBe(settingsPage);
  });

  it('fails loudly when no core or registered page exists', async () => {
    const registry = createInertiaPageRegistry();

    await expect(
      resolveInertiaPage('test-plugin::Missing', registry)
    ).rejects.toThrow('Page not found: test-plugin::Missing');
  });
});
