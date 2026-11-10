import '@craftcms/ui/components/card/card';
import '@craftcms/ui/components/truncate/truncate';
import {createApp, h} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {actionClient} from '@craftcms/ui';
import {broadcast} from '@/common/craft/broadcast';
import AssistantShownElements from './AssistantShownElements.vue';

function card(label: string) {
  return {
    elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
    id: 4,
    siteId: 1,
    label,
    cardContentHtml: `<craft-truncate class="label"><a class="label-link" href="/admin/entries/4">${label}</a></craft-truncate>`,
  };
}

let unmount = () => {};

afterEach(() => {
  unmount();
  document.body.innerHTML = '';
  vi.restoreAllMocks();
});

it('keeps focus on a card’s link when the card is redrawn after its element is saved', async () => {
  vi.spyOn(actionClient, 'post').mockResolvedValue({
    data: {elements: [card('Lighthouses of Maine')]},
  } as never);
  const host = document.createElement('div');
  document.body.append(host);
  const app = createApp({
    render: () =>
      h(AssistantShownElements, {
        view: 'cards',
        elements: [card('Lighthouses')],
      }),
  });
  app.mount(host);
  unmount = () => app.unmount();
  await vi.waitFor(() =>
    expect(host.querySelector('a.label-link')).not.toBeNull()
  );
  host.querySelector<HTMLElement>('a.label-link')!.focus();

  broadcast({event: 'saveElement', id: 4});

  await vi.waitFor(() => {
    const link = host.querySelector('a.label-link');
    expect(link?.textContent).toBe('Lighthouses of Maine');
    expect(document.activeElement).toBe(link);
  });
});
