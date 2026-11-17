import {afterEach, describe, expect, it, vi} from 'vite-plus/test';

const CHANNEL = 'CraftCMS:';
const channels: BroadcastChannel[] = [];

function openChannel(): BroadcastChannel {
  const channel = new BroadcastChannel(CHANNEL);
  channels.push(channel);
  return channel;
}

async function importBroadcast() {
  vi.resetModules();
  return import('./broadcast');
}

afterEach(() => {
  channels.forEach((channel) => channel.close());
  channels.length = 0;
  (
    window.Craft as {broadcaster?: BroadcastChannel} | undefined
  )?.broadcaster?.close();
  vi.unstubAllGlobals();
});

describe('broadcast()', () => {
  it('reaches Craft.messageReceiver, but not Craft.broadcaster listeners, in this tab', async () => {
    // Legacy element editors listen on `Craft.broadcaster` for other tabs' saves.
    const broadcaster = new BroadcastChannel(CHANNEL);
    vi.stubGlobal('Craft', {broadcaster});
    const legacyEditor = vi.fn();
    broadcaster.addEventListener('message', legacyEditor);
    const messageReceiver = vi.fn();
    openChannel().addEventListener('message', messageReceiver);
    const {broadcast} = await importBroadcast();

    broadcast({event: 'saveElement', id: 7});

    await vi.waitFor(() => expect(messageReceiver).toHaveBeenCalled());
    await new Promise((resolve) => setTimeout(resolve, 50));
    expect(legacyEditor).not.toHaveBeenCalled();
  });

  it('shares its channel as Craft.broadcaster when it opens one first', async () => {
    vi.stubGlobal('Craft', {});
    const {onBroadcast} = await importBroadcast();
    const fromOtherTab = vi.fn();

    onBroadcast('saveElement', fromOtherTab);
    openChannel().postMessage({event: 'saveElement', id: 7});

    await vi.waitFor(() => expect(fromOtherTab).toHaveBeenCalled());
    expect(window.Craft.broadcaster).toBeInstanceOf(BroadcastChannel);
  });
});
