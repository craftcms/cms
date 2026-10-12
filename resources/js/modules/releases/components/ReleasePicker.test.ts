import {createApp} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import ReleasePicker from './ReleasePicker.vue';

const DateTimeFormat = Intl.DateTimeFormat;
let app: ReturnType<typeof createApp>;
let container: HTMLElement;

beforeEach(() => {
  vi.spyOn(Intl, 'DateTimeFormat').mockImplementation(
    function (_locales, options) {
      return new DateTimeFormat('en-US', {...options, timeZone: 'UTC'});
    }
  );

  container = document.createElement('div');
  document.body.append(container);
});

afterEach(() => {
  app.unmount();
  container.remove();
  vi.restoreAllMocks();
});

it('shows planned dates and selects releases through the real picker', async () => {
  const selected = vi.fn();
  const create = vi.fn();
  const release = {
    id: 4,
    name: 'October launch',
    status: 'draft' as const,
    plannedAt: '2026-10-16T06:30:00+09:00',
  };
  const unplanned = {
    id: 5,
    name: 'Someday',
    status: 'draft' as const,
    plannedAt: null,
  };

  app = createApp(ReleasePicker, {
    releases: [release, unplanned],
    canCreate: true,
    onSelect: selected,
    onCreate: create,
  });
  app.mount(container);

  await vi.waitFor(() =>
    expect(container.querySelectorAll('craft-action-item')).toHaveLength(3)
  );

  const items = [
    ...container.querySelectorAll<HTMLElement>('craft-action-item'),
  ];
  expect(items.map((item) => item.textContent?.trim())).toEqual([
    'October launch – Oct 15, 2026, 9:30 PM',
    'Someday – No planned date',
    'Create a release',
  ]);

  items[0]!.click();
  expect(selected).toHaveBeenCalledWith(release);

  items[2]!.click();
  expect(create).toHaveBeenCalledWith(container.querySelector('craft-button'));
});
