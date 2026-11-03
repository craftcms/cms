import {S_KEY} from '@craftcms/garnish';
import {createApp, h} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import {registerShortcut} from '@/common/shortcuts';
import {uiLayerManager} from '@/common/slideouts/panel-stack';
import ShortcutsView from './ShortcutsView.vue';

describe('ShortcutsView', () => {
  let app: ReturnType<typeof createApp>;
  let container: HTMLElement;
  const cleanups: Array<() => void> = [];
  const noop = () => {};

  function mount(): void {
    container = document.createElement('div');
    document.body.append(container);
    app = createApp(() => h(ShortcutsView));
    app.mount(container);
  }

  function listed(): Record<string, string[]> {
    return Object.fromEntries(
      [...container.querySelectorAll('section')].map((section) => [
        section.querySelector('h3')?.textContent!.trim() ?? '',
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

  it('lists the described shortcuts that work now, under their groups', () => {
    const removed = document.createElement('textarea');

    for (const registration of [
      registerShortcut('Mod+K', noop, {description: 'Open the palette'}),
      registerShortcut('Mod+E', noop, {
        description: 'Code',
        group: 'Markdown fields',
      }),
      registerShortcut('Mod+S', noop, {
        description: 'Save',
        group: 'Forms',
        isAvailable: () => false,
      }),
      registerShortcut('Mod+B', noop, {
        description: 'Bold',
        group: 'Markdown fields',
        target: removed,
      }),
      registerShortcut('Mod+J', noop),
    ]) {
      cleanups.push(registration.unregister);
    }

    mount();

    expect(listed()).toEqual({
      '': ['Open the palette', 'Close the current modal or slideout'],
      'Markdown fields': ['Code'],
    });
  });

  it('lists a shortcut once, however many places register it', () => {
    for (let i = 0; i < 2; i++) {
      cleanups.push(
        registerShortcut('Mod+S', noop, {description: 'Save', group: 'Forms'})
          .unregister
      );
    }

    mount();

    expect(listed().Forms).toEqual(['Save']);
  });

  it('adds the described shortcuts of the Garnish layers a press would reach', () => {
    const saveAndAddAnother = {
      keyCode: S_KEY,
      ctrl: true,
      shift: true,
      description: 'Save and add another',
      group: 'Forms',
    };
    const save = {
      keyCode: S_KEY,
      ctrl: true,
      description: 'Save and continue editing',
      group: 'Forms',
    };
    const undescribed = {keyCode: S_KEY, ctrl: true, alt: true};
    uiLayerManager().registerShortcut(saveAndAddAnother, noop);
    uiLayerManager().registerShortcut(save, noop);
    uiLayerManager().registerShortcut(undescribed, noop);
    cleanups.push(() => {
      uiLayerManager().unregisterShortcut(saveAndAddAnother, 0);
      uiLayerManager().unregisterShortcut(save, 0);
      uiLayerManager().unregisterShortcut(undescribed, 0);
    });

    mount();

    expect(listed().Forms).toEqual([
      'Save and add another',
      'Save and continue editing',
    ]);
  });

  it('leaves out the shortcuts of Garnish layers covered by one that keeps them', () => {
    const save = {
      keyCode: S_KEY,
      ctrl: true,
      description: 'Save',
      group: 'Forms',
    };
    uiLayerManager().registerShortcut(save, noop);
    uiLayerManager().addLayer();
    cleanups.push(() => {
      uiLayerManager().removeLayer();
      uiLayerManager().unregisterShortcut(save, 0);
    });

    mount();

    expect(listed()).not.toHaveProperty('Forms');
  });
});
