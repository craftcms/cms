import {expect, it} from 'vite-plus/test';
import {createApp, h, nextTick} from 'vue';
import '@craftcms/ui/components/dialog/dialog';
import type CraftDialog from '@craftcms/ui/components/dialog/dialog';

/**
 * `craft-dialog` takes its open state as the `opened` property, backed by the
 * `open` attribute. Vue only removes a false boolean for the seven names in its
 * `isSpecialBooleanAttr` list; `open` is in the wider `isBooleanAttr` list that
 * `patchAttr` never consults. So an attribute binding writes the string
 * `"false"`, Lit's boolean converter sees an attribute that is present, and the
 * dialog shows itself with nothing having asked it to.
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

  // Not what anyone wants — this is the trap the property binding avoids, and
  // it fails loudly here if Vue ever starts stripping the attribute.
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
