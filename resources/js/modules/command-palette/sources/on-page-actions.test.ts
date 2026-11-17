import {describe, expect, it} from 'vite-plus/test';
import {createPageActionsProvider} from './on-page-actions';

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
