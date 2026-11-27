import '@craftcms/ui/components/action-item/action-item';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {reactive} from 'vue';
import {registerShortcut} from '@/common/shortcuts';
import type {CommandPaletteItem} from '../types';
import {createPageActionsProvider, onPageActions} from './on-page-actions';

vi.mock('@/common/composables/useGlobalSidebar', () => ({
  useGlobalSidebar: () => ({
    sidebar: reactive({visibility: 'visible'}),
    toggle: () => {},
  }),
}));

describe('createPageActionsProvider', () => {
  it('finds the page’s actions by their labels and keywords', async () => {
    const provider = createPageActionsProvider(() => [
      {id: 'switch-de', label: 'Switch to German', href: '/admin?site=de'},
      {id: 'nav', label: 'Collapse the global navigation', keywords: 'sidebar'},
    ]);

    expect((await provider.search!('switch')).map(({id}) => id)).toEqual([
      'switch-de',
    ]);
    expect((await provider.search!('sidebar')).map(({id}) => id)).toEqual([
      'nav',
    ]);
    expect(await provider.search!('publish')).toEqual([]);
  });

  it('leaves out actions whose labels only have the query’s letters in order', async () => {
    const provider = createPageActionsProvider(() => [
      {id: 'discard', label: 'Discard changes'},
    ]);

    expect(await provider.search!('caches')).toEqual([]);
  });
});

describe('onPageActions', () => {
  const cleanups: Array<() => void> = [];

  afterEach(() => {
    cleanups.splice(0).forEach((cleanup) => cleanup());
    document.body.innerHTML = '';
  });

  function action(id: string): CommandPaletteItem | undefined {
    return onPageActions([]).find((item) => item.id === id);
  }

  it('shows the global navigation’s shortcut while it works', () => {
    let available = true;
    cleanups.push(
      registerShortcut('Mod+[Backslash]', () => {}, {
        description: 'Collapse or expand the global navigation',
        isAvailable: () => available,
      }).unregister
    );

    expect(action('toggle-global-navigation')?.shortcut).toBe(
      'Mod+[Backslash]'
    );

    available = false;

    expect(action('toggle-global-navigation')?.shortcut).toBeNull();
  });

  it('shows the sidebar’s shortcut only when it’s registered', () => {
    document.body.innerHTML = `
      <div class="details-panels__rail">
        <button class="details-panels__trigger" aria-expanded="true">Info</button>
      </div>`;

    expect(action('toggle-sidebar')?.shortcut).toBeNull();

    cleanups.push(
      registerShortcut('Mod+Shift+[Backslash]', () => {}, {
        description: 'Collapse or expand the sidebar',
      }).unregister
    );

    expect(action('toggle-sidebar')?.shortcut).toBe('Mod+Shift+[Backslash]');
  });

  it('carries over the shortcut a page menu item shows', async () => {
    document.body.innerHTML = `
      <div data-command-palette-actions>
        <craft-action-item shortcut='{"key":"S","shift":true}'>Save and add another</craft-action-item>
        <craft-action-item>Copy URL</craft-action-item>
      </div>`;
    await customElements.whenDefined('craft-action-item');

    const items = onPageActions([]).filter((item) =>
      item.id.startsWith('page-action-')
    );

    expect(items.map(({label, shortcut}) => [label, shortcut])).toEqual([
      ['Save and add another', 'Mod+Shift+S'],
      ['Copy URL', null],
    ]);
  });
});
