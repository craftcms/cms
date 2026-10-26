import '../../../css/cp.css';
import '@craftcms/ui/components/button/button';
import '@craftcms/ui/components/popover/popover';
import '@craftcms/ui/components/reorder-button/reorder-button';
import '@craftcms/ui/components/slide-picker/slide-picker';
import {createApp, h, nextTick, ref} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {page, userEvent} from 'vite-plus/test/browser/context';
import type {DashboardWidget} from './types';
import WidgetManager from './WidgetManager.vue';

let app: ReturnType<typeof createApp> | undefined;

afterEach(() => {
  app?.unmount();
  document.body.innerHTML = '';
});

function widget(id: number, title: string): DashboardWidget {
  return {id, title, name: title, colspan: 1, maxColspan: 2} as DashboardWidget;
}

async function mountManager() {
  const widgets = ref([widget(1, 'Feed'), widget(2, 'Updates')]);
  const busy = ref(false);
  const opened = ref(true);
  let finishSave: (() => void) | undefined;

  const container = document.createElement('div');
  document.body.append(container);
  app = createApp({
    setup: () => () =>
      h(WidgetManager, {
        widgets: widgets.value,
        columns: 2,
        busy: busy.value,
        opened: opened.value,
        'onUpdate:opened': (value: boolean) => (opened.value = value),
        async onReorder(from: number, to: number) {
          busy.value = true;
          await new Promise<void>((resolve) => (finishSave = resolve));
          const order = [...widgets.value];
          order.splice(to, 0, order.splice(from, 1)[0]!);
          widgets.value = order;
          busy.value = false;
        },
      }),
  });
  app.mount(container);
  await nextTick();

  const reorderButton = (title: string) =>
    [...document.querySelectorAll('li')]
      .find((li) => li.textContent?.includes(title))!
      .querySelector('craft-reorder-button')!;

  return {widgets, reorderButton, finishSave: () => finishSave?.()};
}

function renderedTitles(): string[] {
  return [...document.querySelectorAll('[id^="widget-label-"]')].map((label) =>
    label.textContent!.trim()
  );
}

function menuOf(button: HTMLElement) {
  return button.shadowRoot!.querySelector('craft-action-menu')!;
}

it.each([
  ['moveDown', 'Feed'],
  ['moveUp', 'Updates'],
])(
  'moves a widget with %s from the keyboard and keeps focus on its reorder button',
  async (action, title) => {
    const {widgets, reorderButton, finishSave} = await mountManager();
    const button = reorderButton(title);
    await button.updateComplete;

    button.shadowRoot!.querySelector<HTMLElement>('craft-button')!.focus();
    await userEvent.keyboard('{Enter}');
    await vi.waitFor(() => expect(menuOf(button).opened).toBe(true));
    button
      .shadowRoot!.querySelector<HTMLElement>(`[data-action="${action}"]`)!
      .focus();
    await userEvent.keyboard('{Enter}');
    finishSave();
    await vi.waitFor(() =>
      expect(renderedTitles()).toEqual(['Updates', 'Feed'])
    );

    expect(widgets.value.map(({title}) => title)).toEqual(['Updates', 'Feed']);
    const moved = reorderButton(title);
    expect(document.activeElement).toBe(moved);
    expect(moved.shadowRoot!.activeElement).toBe(
      moved.shadowRoot!.querySelector('craft-button')
    );
  }
);

it('names each widget’s reorder button, column picker and delete button after the widget', async () => {
  await mountManager();
  const row = [...document.querySelectorAll('li')].find((li) =>
    li.textContent?.includes('Updates')
  )!;
  const reorder = row.querySelector('craft-reorder-button')!;
  const picker = row.querySelector('craft-slide-picker')!;
  await reorder.updateComplete;
  await picker.updateComplete;

  expect(reorder.label).toBe('Reorder Updates');
  expect(
    picker
      .shadowRoot!.querySelector('[role="slider"]')!
      .getAttribute('aria-label')
  ).toBe('Number of columns Updates');
  await expect
    .element(page.getByRole('button', {name: 'Delete Updates', exact: true}))
    .toBeInTheDocument();
});

it('closes with one Escape after a widget is moved', async () => {
  const {reorderButton, finishSave} = await mountManager();
  const popover = document.querySelector('craft-popover')!;
  const gear = popover.querySelector<HTMLElement>('[slot="invoker"]')!;
  const feed = reorderButton('Feed');
  await feed.updateComplete;

  feed.shadowRoot!.querySelector<HTMLElement>('craft-button')!.focus();
  await userEvent.keyboard('{Enter}');
  await vi.waitFor(() => expect(menuOf(feed).opened).toBe(true));
  feed
    .shadowRoot!.querySelector<HTMLElement>('[data-action="moveDown"]')!
    .focus();
  await userEvent.keyboard('{Enter}');
  finishSave();
  await vi.waitFor(() => expect(renderedTitles()).toEqual(['Updates', 'Feed']));
  await userEvent.keyboard('{Escape}');

  await vi.waitFor(() => expect(popover.opened).toBe(false));
  expect(document.activeElement).toBe(gear);
});
