import {afterEach, expect, it} from 'vite-plus/test';
import type {ElementIndexBody} from '@craftcms/ui';
import {createElementSelectorModal} from './create-element-selector-modal';

afterEach(() => {
  document.body.innerHTML = '';
});

it('returns focus to the trigger when the opener destroys the modal as it closes', async () => {
  const trigger = document.createElement('button');
  trigger.textContent = 'Add an entry';
  document.body.append(trigger);
  trigger.focus();

  const modal = await createElementSelectorModal(
    'CraftCms\\Cms\\Entry\\Elements\\Entry',
    {
      loadIndexBody: async () => null as unknown as ElementIndexBody,
      triggerElement: () => trigger,
      onSelect: () => {},
    }
  );
  modal.on('close', () => modal.destroy());

  modal.controller.setSelection([
    {
      id: 1,
      siteId: 1,
      label: 'Entry',
      status: null,
      url: null,
      hasThumb: false,
    },
  ]);
  await modal.controller.submit();

  await expect.poll(() => document.activeElement).toBe(trigger);
});
