import {afterEach, expect, it, vi} from 'vite-plus/test';
import {createApp, h, nextTick, type App} from 'vue';
import type {AboutCraftUpdates} from './about-craft';
import Updates from './AboutCraftUpdates.vue';

const state = vi.hoisted(() => ({post: vi.fn(), reload: vi.fn()}));
vi.mock('@craftcms/ui', async () => ({
  ...(await vi.importActual('@craftcms/ui')),
  actionClient: {post: state.post},
  formattingLocale: () => 'en-US',
}));
vi.mock('@inertiajs/vue3', () => ({router: {reload: state.reload}}));
vi.mock('@actions/Updates/UpdatesController', () => ({
  check: {url: () => '/updates/check'},
}));
vi.mock('@actions/Utilities/UtilitiesController', () => ({
  show: {url: () => '/utilities/updates'},
}));

let app: App | undefined;
let host: HTMLElement;

afterEach(() => {
  app?.unmount();
  host?.remove();
  vi.useRealTimers();
  vi.resetAllMocks();
});

function mount(updates: AboutCraftUpdates, onChecked?: () => void) {
  host = document.createElement('div');
  document.body.append(host);
  app = createApp({render: () => h(Updates, {updates, onChecked})});
  app.mount(host);
}

const text = (selector: string) =>
  host.querySelector(selector)!.textContent!.replace(/\s+/g, ' ').trim();
const row = () => text('.about-craft-updates');
const announced = () => text('[role="status"]');
const checkButton = () =>
  host.querySelector('.about-craft-updates__check') as HTMLElement & {
    loading?: boolean;
  };

it('says when it last checked, while up to date', () => {
  vi.useFakeTimers({now: new Date('2026-11-16T12:00:00Z')});
  mount({cached: true, total: 0, dateFetched: '2026-11-16T10:00:00Z'});

  expect(row()).toContain('Up to date');
  expect(row()).toContain('Checked 2 hours ago');
});

it('counts the available updates, and links to them', () => {
  mount({cached: true, total: 3, dateFetched: '2026-11-16T10:00:00Z'});

  expect(row()).toContain('3 updates available');
  expect(row()).not.toContain('Checked');
  // Set as the button's property, which it links with
  expect(
    (
      host.querySelector('.about-craft-updates__go') as HTMLElement & {
        href?: string;
      }
    ).href
  ).toBe('/utilities/updates');
});

it('checks again from its button, keeping the last result until it ends', async () => {
  vi.useFakeTimers({now: new Date('2026-11-16T12:00:00Z')});
  let respond!: (value: unknown) => void;
  state.post.mockReturnValue(new Promise((resolve) => (respond = resolve)));
  mount({cached: true, total: 0, dateFetched: '2026-11-15T12:00:00Z'});
  const button = checkButton();

  button.focus();
  button.dispatchEvent(new MouseEvent('click', {bubbles: true}));
  await nextTick();

  expect(state.post.mock.lastCall![1]).toEqual({forceRefresh: true});
  // The button spins; the row stays as it was, so nothing moves
  expect(button.loading).toBe(true);
  expect(row()).toContain('Up to date');
  expect(row()).toContain('Checked yesterday');
  expect(announced()).toBe('Checking for updates…');

  respond({data: {total: 1, dateFetched: '2026-11-16T12:00:00Z'}});
  await vi.waitFor(() => expect(row()).toContain('1 update available'));

  expect(announced()).toBe('1 update available');
  // With updates to go to, the button gives way to their link, and its focus
  // goes there too
  const go = host.querySelector('.about-craft-updates__go')!;
  expect(checkButton()).toBeNull();
  // As a link, the button's anchor takes focus, not the button itself.
  await vi.waitFor(() =>
    expect(go.shadowRoot!.activeElement).toBe(go.shadowRoot!.querySelector('a'))
  );
});

it('keeps its button while up to date, so focus stays on it', async () => {
  state.post.mockResolvedValue({
    data: {total: 0, dateFetched: '2026-11-16T12:00:00Z'},
  });
  mount({cached: true, total: 0, dateFetched: '2026-11-15T12:00:00Z'});
  const button = checkButton();

  button.dispatchEvent(new MouseEvent('click', {bubbles: true}));
  await vi.waitFor(() => expect(button.loading).toBe(false));

  expect(checkButton()).toBe(button);
});

it('has no check button while updates are available', () => {
  mount({cached: true, total: 3, dateFetched: '2026-11-16T10:00:00Z'});

  expect(checkButton()).toBeNull();
});

it('checks when shown, if the updates weren’t fetched lately', async () => {
  let respond!: (value: unknown) => void;
  state.post.mockReturnValue(new Promise((resolve) => (respond = resolve)));
  mount({cached: false, total: 0, dateFetched: null});
  await nextTick();

  // With no result yet, it doesn't claim to be up to date
  expect(row()).toContain('Checking for updates…');
  expect(host.querySelectorAll('craft-spinner')).toHaveLength(1);
  expect(checkButton().loading).toBe(false);
  expect(state.post.mock.lastCall![1]).toEqual({forceRefresh: false});

  respond({data: {total: 2, dateFetched: '2026-11-16T12:00:00Z'}});
  await vi.waitFor(() => expect(row()).toContain('2 updates available'));
  expect(announced()).toBe('2 updates available');
});

it('stops announcing a check’s result once it’s been read', async () => {
  vi.useFakeTimers({now: new Date('2026-11-16T12:00:00Z')});
  state.post.mockResolvedValue({
    data: {total: 0, dateFetched: '2026-11-16T12:00:00Z'},
  });
  mount({cached: true, total: 0, dateFetched: '2026-11-15T12:00:00Z'});

  checkButton().dispatchEvent(new MouseEvent('click', {bubbles: true}));
  await vi.waitFor(() => expect(checkButton().loading).toBe(false));
  await nextTick();
  expect(announced()).toBe('Up to date');

  await vi.advanceTimersByTimeAsync(5000);

  // The row still says so, for anyone browsing it
  expect(announced()).toBe('');
  expect(row()).toContain('Up to date');
});

it('announces a second check with the same result again', async () => {
  vi.useFakeTimers({now: new Date('2026-11-16T12:00:00Z')});
  let respond!: (value: unknown) => void;
  state.post.mockImplementation(
    () => new Promise((resolve) => (respond = resolve))
  );
  mount({cached: true, total: 0, dateFetched: '2026-11-15T12:00:00Z'});

  for (let i = 0; i < 2; i++) {
    checkButton().dispatchEvent(new MouseEvent('click', {bubbles: true}));
    await nextTick();
    expect(announced()).toBe('Checking for updates…');

    respond({data: {total: 0, dateFetched: '2026-11-16T12:00:00Z'}});
    await vi.waitFor(() => expect(announced()).toBe('Up to date'));
    await vi.advanceTimersByTimeAsync(5000);
    expect(announced()).toBe('');
  }
});

it('says it couldn’t fetch the updates, when there’s no result to show', async () => {
  state.post.mockRejectedValue(new Error('Network'));
  mount({cached: false, total: 0, dateFetched: null});

  await vi.waitFor(() =>
    expect(row()).toContain('Unable to fetch updates at this time.')
  );
  expect(row()).not.toContain('Up to date');
  expect(announced()).toBe('Unable to fetch updates at this time.');
});

it('says it couldn’t fetch the updates when that’s all there is to show', () => {
  // A failed request is cached too, with no fetch date.
  mount({cached: true, total: 0, dateFetched: null});

  expect(row()).toContain('Unable to fetch updates at this time.');
  expect(row()).not.toContain('Up to date');
  expect(state.post).not.toHaveBeenCalled();
});

it('says it couldn’t check again, keeping the last result, when the server couldn’t fetch the updates', async () => {
  vi.useFakeTimers({now: new Date('2026-11-16T12:00:00Z')});
  state.post.mockResolvedValue({data: {total: 0, dateFetched: null}});
  const onChecked = vi.fn();
  mount(
    {cached: true, total: 0, dateFetched: '2026-11-15T12:00:00Z'},
    onChecked
  );

  checkButton().dispatchEvent(new MouseEvent('click', {bubbles: true}));
  await vi.waitFor(() => expect(checkButton().loading).toBe(false));
  await nextTick();

  expect(row()).toContain('Up to date');
  expect(row()).toContain('Checked yesterday');
  expect(text('craft-callout')).toBe('Unable to fetch updates at this time.');
  expect(onChecked).not.toHaveBeenCalled();
});

it('says when it has checked, so the trials can be fetched again with the license info', async () => {
  state.post.mockResolvedValue({
    data: {total: 0, dateFetched: '2026-11-16T12:00:00Z'},
  });
  const onChecked = vi.fn();
  mount({cached: false, total: 0, dateFetched: null}, onChecked);

  await vi.waitFor(() => expect(onChecked).toHaveBeenCalledOnce());
});
