import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {announce, announcement, installAnnouncer} from './announcer';

function modalLayer(html: string): HTMLElement {
  const container = document.createElement('div');
  container.innerHTML = html;
  document.body.append(container);
  return container;
}

/** A legacy `Garnish` whose top modal layer holds `container`, as a jQuery-like collection. */
function openLegacyLayer(container: HTMLElement) {
  vi.stubGlobal('Garnish', {
    uiLayerManager: {highestModalLayer: {$container: {0: container}}},
  });
}

beforeEach(() => {
  vi.useFakeTimers();
});

afterEach(() => {
  vi.runAllTimers();
  vi.useRealTimers();
  vi.unstubAllGlobals();
  document.body.replaceChildren();
});

describe('announce()', () => {
  it('uses the page’s global live region, then clears it', () => {
    const region = modalLayer('').appendChild(document.createElement('div'));
    region.id = 'global-live-region';

    announce('Loading');
    expect(region.textContent).toBe('Loading');

    vi.advanceTimersByTime(5000);
    expect(region.textContent).toBe('');
  });

  it('falls back to <LiveRegion> when the page has no global region', () => {
    announce('Saved', {timeout: 1000});
    expect(announcement.value).toBe('Saved');

    vi.advanceTimersByTime(1000);
    expect(announcement.value).toBeNull();
  });

  it('speaks in the top modal layer, which hides the page behind it', () => {
    const page = modalLayer('<div id="global-live-region"></div>');
    const modal = modalLayer('<span role="status"></span>');
    openLegacyLayer(modal);

    announce('Loading');

    expect(modal.querySelector('[role="status"]')!.textContent).toBe('Loading');
    expect(page.textContent).toBe('');
  });

  it('finds a legacy slideout’s region inside its .slideout', () => {
    const slideout = modalLayer(
      '<div class="slideout"><span role="status"></span></div>'
    );
    openLegacyLayer(slideout);

    announce('Loading');

    expect(slideout.querySelector('[role="status"]')!.textContent).toBe(
      'Loading'
    );
  });

  it('clears the previous region when the next message goes elsewhere', () => {
    const page = modalLayer('<div id="global-live-region"></div>');
    announce('First');
    const modal = modalLayer('<span role="status"></span>');
    openLegacyLayer(modal);

    announce('Second');

    expect(page.textContent).toBe('');
    expect(modal.textContent).toBe('Second');
  });
});

it('routes Craft.cp.announce() here', () => {
  class CP {
    announce(_message: string) {}
  }
  vi.stubGlobal('Craft', {CP});
  installAnnouncer();

  new CP().announce('From a plugin');

  expect(announcement.value).toBe('From a plugin');
});
