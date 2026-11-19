import '../../../../css/cp.css';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {page} from 'vite-plus/test/browser/context';
import {createApp, h, type App} from 'vue';
import NestedElementsCreateButton from './NestedElementsCreateButton.vue';

let app: App | undefined;

afterEach(() => {
  app?.unmount();
  app = undefined;
  document.body.innerHTML = '';
});

it('switches to the menu once a hidden tab shows buttons that do not fit', async () => {
  const container = document.createElement('div');
  container.style.width = '200px';
  container.hidden = true;
  document.body.append(container);
  app = createApp({
    render: () =>
      h(NestedElementsCreateButton, {
        choices: Array.from({length: 6}, (_, i) => ({
          value: `type${i}`,
          label: `Type ${i}`,
        })),
        label: 'New block',
        disabled: false,
      }),
  });
  app.mount(container);
  await new Promise(requestAnimationFrame);
  expect(container.querySelector('[data-ui-matrix-add]')).not.toBeNull();

  container.hidden = false;

  await expect
    .element(page.getByRole('button', {name: 'New block'}))
    .toBeVisible();
  expect(container.querySelector('[data-ui-matrix-add]')).toBeNull();
});

it('opens the entry type menu and creates the chosen type', async () => {
  const onCreate = vi.fn();
  const container = document.createElement('div');
  container.style.width = '200px';
  document.body.append(container);
  const choices = Array.from({length: 11}, (_, i) => ({
    value: `type${i}`,
    label: `Type ${i}`,
  }));
  app = createApp({
    render: () =>
      h(NestedElementsCreateButton, {
        choices,
        label: 'New block',
        disabled: false,
        onCreate,
      }),
  });
  app.mount(container);

  await page.getByRole('button', {name: 'New block'}).click();
  await page.getByRole('button', {name: 'Type 7', exact: true}).click();

  await vi.waitFor(() =>
    expect(onCreate).toHaveBeenCalledWith(
      'type7',
      container.querySelector('craft-button[slot="invoker"]')
    )
  );
});
