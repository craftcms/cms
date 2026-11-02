import {S_KEY} from '@craftcms/garnish';
import {createApp, h} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {uiLayerManager} from '@/common/slideouts/panel-stack';
import {registerShortcuts} from '../registry';
import ShortcutsView from './ShortcutsView.vue';

vi.mock('@inertiajs/vue3', () => ({
  usePage: () => ({props: {}}),
}));

describe('ShortcutsView', () => {
  let app: ReturnType<typeof createApp>;
  let container: HTMLElement;
  const cleanups: Array<() => void> = [];

  function mount(): void {
    container = document.createElement('div');
    document.body.append(container);
    app = createApp(() => h(ShortcutsView));
    app.mount(container);
  }

  function listed(): Record<string, string[]> {
    return Object.fromEntries(
      [...container.querySelectorAll('section')].map((section) => [
        section.querySelector('h3')!.textContent!.trim(),
        [...section.querySelectorAll('tbody td')].map((cell) =>
          cell.textContent!.trim()
        ),
      ])
    );
  }

  afterEach(() => {
    app.unmount();
    container.remove();
    cleanups.splice(0).forEach((cleanup) => cleanup());
  });

  it('lists only the shortcuts that work everywhere when nothing offers more', () => {
    mount();

    expect(listed()).toEqual({
      General: [
        'Open the command palette',
        'Open the command palette when not typing in a field',
        'Close the current modal or slideout',
      ],
      Forms: ['Submit the form a text field is in'],
    });
  });

  it('adds the shortcuts that registered sources and Garnish layers offer', () => {
    cleanups.push(
      registerShortcuts(() => ['markdown']).unregister,
      registerShortcuts(() => []).unregister
    );
    const saveAndAddAnother = {keyCode: S_KEY, ctrl: true, shift: true};
    uiLayerManager().registerShortcut(saveAndAddAnother, () => {});
    cleanups.push(() =>
      uiLayerManager().unregisterShortcut(saveAndAddAnother, 0)
    );

    mount();

    expect(Object.keys(listed())).toEqual([
      'General',
      'Forms',
      'Markdown fields',
    ]);
    expect(listed().Forms).toEqual([
      'Save and add another',
      'Submit the form a text field is in',
    ]);
  });

  it('leaves out the shortcuts of Garnish layers covered by one that keeps them', () => {
    const save = {keyCode: S_KEY, ctrl: true};
    uiLayerManager().registerShortcut(save, () => {});
    uiLayerManager().addLayer();
    cleanups.push(() => {
      uiLayerManager().removeLayer();
      uiLayerManager().unregisterShortcut(save, 0);
    });

    mount();

    expect(listed().Forms).not.toContain('Save');
  });
});
