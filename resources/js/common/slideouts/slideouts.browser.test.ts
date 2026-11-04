import {defineComponent, h} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {page, userEvent} from 'vite-plus/test/browser/context';
import type CraftPopover from '@craftcms/ui/components/popover/popover';
import '@craftcms/ui/components/button/button';
import '@craftcms/ui/components/popover/popover';
import {
  closeAllSlideouts,
  closeSlideout,
  openSlideout,
  setSlideoutPageLoader,
} from './store';

afterEach(() => {
  setSlideoutPageLoader();
  closeAllSlideouts();
  document.body.innerHTML = '';
});

it('returns focus to a popover’s button once a slideout opened from inside it closes', async () => {
  setSlideoutPageLoader(
    vi.fn().mockResolvedValue({
      component: defineComponent({render: () => h('div')}),
      props: {},
      url: '/a',
    })
  );
  document.body.innerHTML = `
    <craft-popover>
      <craft-button slot="invoker" type="button">View</craft-button>
      <div slot="content"><button type="button">Edit columns</button></div>
    </craft-popover>
  `;
  const popover = document.querySelector<CraftPopover>('craft-popover')!;
  const opener = popover.querySelector<HTMLElement>('[slot="content"] button')!;
  let panelId = '';
  opener.addEventListener('click', async () => {
    const panel = (await openSlideout('/a', {opener}))!;
    panelId = panel.id;
    // Standing in for `SlideoutPanel`, which focuses itself as it opens.
    const element = document.createElement('div');
    element.dataset.slideoutId = panel.id;
    element.tabIndex = -1;
    document.body.append(element);
    element.focus();
  });
  await popover.updateComplete;

  await userEvent.click(page.getByRole('button', {name: 'View'}));
  await userEvent.click(page.getByRole('button', {name: 'Edit columns'}));

  await expect.poll(() => panelId).not.toBe('');
  await expect.poll(() => popover.opened).toBe(false);

  closeSlideout(panelId);

  expect(document.activeElement).toBe(
    popover.querySelector('[slot="invoker"]')
  );
});
