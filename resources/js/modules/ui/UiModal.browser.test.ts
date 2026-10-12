import '../../../css/cp.css';
import '@craftcms/ui/components/pane/pane';
import {actionClient} from '@craftcms/ui';
import {createApp, h, shallowRef, type App} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {page, userEvent} from 'vite-plus/test/browser/context';
import {createCpComponentRegistry} from '@/bootstrap/components';
import UiModal from './UiModal.vue';
import {registerUiComponents} from './register';
import type {UiNodePayload, UiPayload} from './types';

let app: App | undefined;
let host: HTMLElement | undefined;

afterEach(() => {
  app?.unmount();
  host?.remove();
  vi.restoreAllMocks();
  vi.unstubAllGlobals();
});

function field(name: string, label: string): UiNodePayload {
  return {
    type: 'CraftCms\\Cms\\Ui\\Nodes\\Field',
    component: 'craft:field',
    props: {label},
    control: {
      type: 'CraftCms\\Cms\\Ui\\Controls\\Text',
      component: 'craft:text',
      props: {},
      path: [name],
      deltaGroup: [name],
      mode: 'editable',
    },
  };
}

async function mount(unsupportedControl = false): Promise<HTMLElement> {
  await page.viewport(1200, 800);

  const payload: UiPayload = {
    scope: [],
    refreshable: false,
    values: {source: 'Uploads', subpath: 'images', title: 'Assets'},
    errors: [],
    globalErrors: [],
    nodes: [
      {
        type: 'CraftCms\\Cms\\Ui\\Nodes\\Group',
        component: 'craft:group',
        uid: 'asset-location',
        props: {
          asField: true,
          label: 'Asset Location',
          labelHtml: '<strong>Asset Location</strong>',
          labelSrOnly: true,
          instructions: 'Choose the **location** where assets can be selected.',
          instructionsHtml:
            'Choose the <strong>location</strong> where assets can be selected.',
          instructionsPosition: 'after',
          layoutUid: 'location-layout-element',
          width: 50,
        },
        children: [field('source', 'Source'), field('subpath', 'Subpath')],
      },
      {...field('title', 'Title'), props: {label: 'Title', width: 50}},
    ],
  };
  if (unsupportedControl) {
    const title = payload.nodes[1]!;
    payload.nodes[1] = {
      ...title,
      control: {...title.control!, component: 'test:unsupported'},
    };
  }

  vi.stubGlobal(
    'fetch',
    vi.fn().mockResolvedValue(new Response('<svg></svg>'))
  );
  vi.spyOn(actionClient, 'get').mockResolvedValue({data: {ui: payload}});
  const active = shallowRef(false);
  host = document.createElement('div');
  document.body.append(host);
  app = createApp({
    render: () =>
      h('div', [
        h('button', {onClick: () => (active.value = true)}, 'Open settings'),
        active.value
          ? h(UiModal, {
              modalUrl: 'settings/modal',
              actionUrl: 'settings/save',
              title: 'Asset settings',
              width: '4xl',
              onClose: () => (active.value = false),
            })
          : null,
      ]),
  });
  const registry = createCpComponentRegistry();
  registerUiComponents(registry);
  registry.install(app);
  app.mount(host);
  await page.getByRole('button', {name: 'Open settings'}).click();
  await expect
    .element(page.getByRole('dialog', {name: 'Asset settings'}))
    .toBeVisible();

  return host;
}

it('renders formatted group instructions after the inputs with a layout identity and accessible names', async () => {
  const root = await mount();
  const group = root.querySelector<HTMLElementTagNameMap['craft-field']>(
    'craft-field[fieldset]'
  )!;
  await group.updateComplete;

  expect(
    group.querySelector(':scope > [slot="label"] strong')?.textContent
  ).toBe('Asset Location');
  expect(group.getAttribute('data-layout-element')).toBe(
    'location-layout-element'
  );
  await expect
    .element(page.getByRole('group', {name: /Asset Location/}))
    .toBeVisible();
  await expect
    .element(page.getByRole('textbox', {name: 'Source', exact: true}))
    .toBeVisible();
  const help = group.querySelector(':scope > [slot="help-text"]')!;
  expect(help.querySelector('strong')?.textContent).toBe('location');
  expect(help.textContent).toBe(
    'Choose the location where assets can be selected.'
  );
  const inputs = ['Source', 'Subpath'].map((name) =>
    page.getByRole('textbox', {name, exact: true}).element()
  );
  await vi.waitFor(() =>
    expect(help.getBoundingClientRect().top).toBeGreaterThanOrEqual(
      Math.max(...inputs.map((input) => input.getBoundingClientRect().bottom))
    )
  );
  expect(group.getAttribute('aria-describedby')?.split(' ')).toContain(help.id);
});

it('prevents submission when the modal UI cannot render a control', async () => {
  const post = vi.spyOn(actionClient, 'post').mockResolvedValue({data: {}});
  await mount(true);
  await expect
    .element(page.getByRole('alert'))
    .toHaveTextContent('component is not registered');

  await page.getByRole('button', {name: 'Save', exact: true}).click();

  expect(post).not.toHaveBeenCalled();
  await expect
    .element(page.getByRole('dialog', {name: 'Asset settings'}))
    .toBeVisible();
});

it('lays out modal fields in columns and stacks them in a narrow container without changing keyboard submission', async () => {
  const post = vi.spyOn(actionClient, 'post').mockResolvedValue({data: {}});
  const root = await mount();
  const group = root.querySelector('craft-field[fieldset]')!;
  const title = page.getByRole('textbox', {name: 'Title', exact: true});
  const titleField = title.element().closest('craft-field')!;

  async function tabThroughFields(): Promise<void> {
    page.getByRole('textbox', {name: 'Source', exact: true}).element().focus();
    await userEvent.tab();
    await expect
      .element(page.getByRole('textbox', {name: 'Subpath', exact: true}))
      .toHaveFocus();
    await userEvent.tab();
    await expect.element(title).toHaveFocus();
  }

  await vi.waitFor(() => {
    expect(titleField.getBoundingClientRect().top).toBe(
      group.getBoundingClientRect().top
    );
    expect(titleField.getBoundingClientRect().left).toBeGreaterThan(
      group.getBoundingClientRect().left
    );
  });
  await tabThroughFields();

  const content = root.querySelector<HTMLElement>('.content')!;
  content.style.width = '320px';
  await vi.waitFor(() =>
    expect(titleField.getBoundingClientRect().top).toBeGreaterThan(
      group.getBoundingClientRect().bottom
    )
  );
  expect(content.scrollWidth).toBeLessThanOrEqual(content.clientWidth);

  await tabThroughFields();
  await title.fill('Updated assets');
  await userEvent.keyboard('{Enter}');
  await vi.waitFor(() =>
    expect(post).toHaveBeenCalledWith('settings/save', {
      source: 'Uploads',
      subpath: 'images',
      title: 'Updated assets',
    })
  );
  await userEvent.keyboard('{Escape}');
  await expect
    .element(page.getByRole('button', {name: 'Open settings'}))
    .toHaveFocus();
});
