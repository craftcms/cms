import {afterEach, expect, it, vi} from 'vite-plus/test';

afterEach(() => {
  vi.unstubAllGlobals();
  vi.resetModules();
});

it('loads the page’s translations as soon as it is imported', async () => {
  vi.stubGlobal('Craft', {
    translations: {app: {'Add an asset': 'Ajouter un fichier'}},
  });

  await import('./seed-translations');
  const {t} = await import('@craftcms/ui/utilities/translate');

  expect(t('Add an asset')).toBe('Ajouter un fichier');
});
