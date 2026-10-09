import {actionClient} from '@craftcms/ui';
import {createApp, nextTick, reactive, ref, type App} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {
  useActivityTimeline,
  type ActivityTimelineProps,
} from './useActivityTimeline';

const observers: Array<() => void> = [];
let app: App | undefined;

beforeEach(() => {
  vi.stubGlobal(
    'ResizeObserver',
    class {
      constructor(callback: ResizeObserverCallback) {
        observers.push(() => callback([], this as ResizeObserver));
      }

      observe() {}

      unobserve() {}

      disconnect() {}
    }
  );
  vi.spyOn(actionClient, 'request').mockResolvedValue({
    data: {events: []},
  } as never);
});

afterEach(() => {
  app?.unmount();
  observers.length = 0;
  vi.restoreAllMocks();
  vi.unstubAllGlobals();
});

/** A scroll container whose content height the test controls. */
function scroller(): HTMLElement & {contentHeight: number} {
  const element = document.createElement('div') as unknown as HTMLElement & {
    contentHeight: number;
  };
  element.contentHeight = 100;
  Object.defineProperty(element, 'clientHeight', {value: 100});
  Object.defineProperty(element, 'scrollHeight', {
    get: () => element.contentHeight,
  });
  let scrollTop = 0;
  Object.defineProperty(element, 'scrollTop', {
    get: () => scrollTop,
    set: (value: number) => {
      scrollTop = Math.max(0, Math.min(value, element.contentHeight - 100));
    },
  });
  element.append(document.createElement('div'));

  return element;
}

async function mount(props: ActivityTimelineProps, element: HTMLElement) {
  const timeline = ref<HTMLElement | null>(element);
  app = createApp({
    setup() {
      useActivityTimeline(props, timeline);

      return () => null;
    },
  });
  app.mount(document.createElement('div'));
  await vi.waitFor(() => expect(actionClient.request).toHaveBeenCalled());
  await nextTick();
  await nextTick();
}

const grow = async (element: {contentHeight: number}, height: number) => {
  element.contentHeight = height;
  observers.forEach((notify) => notify());
  await nextTick();
};

it('stays scrolled to the newest activity as events render in, until the reader scrolls up', async () => {
  const props = reactive<ActivityTimelineProps>({
    active: true,
    url: '/activity',
    elementType: 'Entry',
    elementId: 1,
    siteId: 1,
  });
  const element = scroller();
  await mount(props, element);

  await grow(element, 600);
  expect(element.scrollTop).toBe(500);

  // The scroll that pinning set off can arrive after more has rendered.
  element.contentHeight = 700;
  element.dispatchEvent(new Event('scroll'));
  await grow(element, 700);
  expect(element.scrollTop).toBe(600);

  element.scrollTop = 200;
  element.dispatchEvent(new Event('scroll'));
  await grow(element, 800);
  expect(element.scrollTop).toBe(200);

  props.active = false;
  await nextTick();
  props.active = true;
  await nextTick();
  await grow(element, 900);
  expect(element.scrollTop).toBe(800);
});
