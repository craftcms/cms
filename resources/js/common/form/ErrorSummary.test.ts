import {afterEach, expect, it, vi} from 'vite-plus/test';
import {computeAccessibleName} from 'dom-accessibility-api';
import {createApp, h, nextTick, type App} from 'vue';
import ErrorSummary from './ErrorSummary.vue';

vi.mock('@craftcms/ui', () => ({t: (message: string) => message}));

let app: App | undefined;

async function mount(
  errors: Record<string, string>,
  props: {errorPaths?: (key: string) => (string[] | null)[]} = {}
): Promise<HTMLElement> {
  const container = document.createElement('div');
  document.body.append(container);
  app = createApp({render: () => h(ErrorSummary, {errors, ...props})});
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);
  await nextTick();
  await nextTick();

  return container;
}

afterEach(() => {
  app?.unmount();
  app = undefined;
  document.body.innerHTML = '';
});

it('links each error to its field, and leaves the rest as text', async () => {
  document.body.innerHTML = `<craft-field id="ui-fields-artist"><input></craft-field>`;

  const summary = await mount({
    'fields.artist': 'Artist is required.',
    elsewhere: 'Something else went wrong.',
  });
  const items = [...summary.querySelectorAll('li')];

  expect(items[0]!.querySelector('a')?.getAttribute('href')).toBe(
    '#ui-fields-artist'
  );
  expect(items[0]!.textContent?.trim()).toBe('Artist is required.');
  expect(items[1]!.querySelector('a')).toBeNull();
  expect(items[1]!.textContent?.trim()).toBe('Something else went wrong.');
});

it('links a field error by the path its screen resolves the key to, with the field name in bold', async () => {
  document.body.innerHTML = `
    <craft-field id="ui-fields-a11yDate"><input></craft-field>
    <craft-field id="ui-postDate"><input></craft-field>`;
  const errorPaths = (key: string) => [
    key === 'a11yDate' ? ['fields', key] : null,
    [key],
  ];

  const summary = await mount(
    {
      a11yDate: 'The *Date* field is required.',
      postDate: '*Post Date* must be a date.',
    },
    {errorPaths}
  );
  const links = [...summary.querySelectorAll('a')];

  expect(links.map((link) => link.getAttribute('href'))).toEqual([
    '#ui-fields-a11yDate',
    '#ui-postDate',
  ]);
  expect(links.map((link) => computeAccessibleName(link))).toEqual([
    'The Date field is required.',
    'Post Date must be a date.',
  ]);
  expect(
    links.map((link) => link.querySelector('strong')?.textContent)
  ).toEqual(['Date', 'Post Date']);
});

it('shows an unlinked error with its field name in bold, without the asterisks', async () => {
  const summary = await mount({elsewhere: 'The *Date* field is required.'});
  const item = summary.querySelector('li')!;

  expect(item.querySelector('a')).toBeNull();
  expect(item.textContent?.trim()).toBe('The Date field is required.');
  expect(item.querySelector('strong')?.textContent).toBe('Date');
});

it('takes focus when it appears, so the failure is announced', async () => {
  const summary = await mount({title: 'Title is required.'});
  const group = summary.querySelector('[role="group"]')!;

  expect(document.activeElement).toBe(group);
  expect(group.getAttribute('aria-labelledby')).toBe(
    summary.querySelector('[slot="title"]')!.id
  );
});

it('switches to the tab a linked field is on and focuses its input', async () => {
  document.body.innerHTML = `
    <a role="tab" aria-controls="tab-2" href="#tab-2">Details</a>
    <section id="tab-2" role="tabpanel" class="hidden">
      <craft-field id="ui-title">
        <div slot="actions"><button type="button">Actions</button></div>
        <div slot="input"><input id="ui-title-input"></div>
      </craft-field>
    </section>`;
  const tab = document.querySelector<HTMLElement>('[role="tab"]')!;
  tab.addEventListener('click', () =>
    document.getElementById('tab-2')!.classList.remove('hidden')
  );
  Element.prototype.scrollIntoView = vi.fn();

  const summary = await mount({title: 'Title is required.'});
  summary.querySelector('a')!.click();
  await nextTick();

  expect(document.getElementById('tab-2')!.classList.contains('hidden')).toBe(
    false
  );
  expect(document.activeElement).toBe(
    document.getElementById('ui-title-input')
  );
});
