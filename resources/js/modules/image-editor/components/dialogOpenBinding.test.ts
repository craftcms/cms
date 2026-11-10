import {expect, it} from 'vite-plus/test';
import {createApp, h, nextTick} from 'vue';
import '@craftcms/ui/components/dialog/dialog';
import type CraftDialog from '@craftcms/ui/components/dialog/dialog';

/**
 * Vue sets `open` as a property because the element has one, so `:open="false"`
 * doesn't write the string "false", which Lit would read as present.
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

it('leaves a dialog closed when handed `false`', async () => {
  const dialog = await mountDialog({open: false});

  expect(dialog.open).toBe(false);
  expect(dialog.hasAttribute('open')).toBe(false);
});

it('opens a dialog when handed `true`', async () => {
  const dialog = await mountDialog({open: true});

  expect(dialog.open).toBe(true);
});
