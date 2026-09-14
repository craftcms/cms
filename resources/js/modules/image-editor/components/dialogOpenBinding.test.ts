import {expect, it} from 'vite-plus/test';
import {createApp, h, nextTick} from 'vue';
import '@craftcms/ui/components/dialog/dialog';
import type CraftDialog from '@craftcms/ui/components/dialog/dialog';

/**
 * `:open="false"` writes the string "false", which Lit reads as present;
 * `.opened` binds the property instead.
 */
async function mountDialog(props: Record<string, unknown>) {
  const container = document.createElement('div');
  document.body.append(container);
  createApp({render: () => h('craft-dialog', props)}).mount(container);
  await nextTick();

  const dialog = container.querySelector<CraftDialog>('craft-dialog')!;
  await dialog.updateComplete;

  return dialog;
}

it('opens a dialog handed `false` through the attribute', async () => {
  const dialog = await mountDialog({open: false});

  // Documents the attribute trap; fails if Vue starts stripping it.
  expect(dialog.opened).toBe(true);
});

it('leaves a dialog closed when the property is bound instead', async () => {
  const dialog = await mountDialog({'.opened': false});

  expect(dialog.opened).toBe(false);
  expect(dialog.hasAttribute('open')).toBe(false);
});

it('still opens on demand through the property', async () => {
  const dialog = await mountDialog({'.opened': true});

  expect(dialog.opened).toBe(true);
});
