import {createApp, h, nextTick, type App} from 'vue';
import {afterEach, expect, it} from 'vite-plus/test';
import {page} from 'vite-plus/test/browser/context';
import type {
  LinkAdvancedField,
  LinkFieldValue,
  LinkTypeConfig,
} from '../link-field/craft-link-field';
import LinkControl from './LinkControl.vue';
import type {UiControlPayload} from './types';

let app: App | undefined;
let container: HTMLElement | undefined;

afterEach(() => {
  app?.unmount();
  container?.remove();
});

const urlType: LinkTypeConfig = {id: 'url', label: 'URL', kind: 'text'};
const entryType: LinkTypeConfig = {
  id: 'entry',
  label: 'Entry',
  kind: 'element',
  elementType: 'Entry',
  refHandle: 'entry',
};

/** A compact Link control, as a Table field's cell renders one. */
function compactControl(
  types: LinkTypeConfig[],
  advancedFields: LinkAdvancedField[] = []
): UiControlPayload<{
  types: LinkTypeConfig[];
  showLabelField: boolean;
  advancedFields: LinkAdvancedField[];
  compact: boolean;
}> {
  return {
    type: 'CraftCms\\Cms\\Ui\\Controls\\Link',
    component: 'craft:link',
    props: {
      types,
      showLabelField: true,
      advancedFields,
      compact: true,
    },
    path: ['fields', 'link'],
    mode: 'editable',
    deltaGroup: ['fields', 'link'],
  };
}

/** Mounts a compact Link control in a cell `width` wide: narrow enough for a chip by default. */
async function mountCell(
  value: Partial<LinkFieldValue> & Pick<LinkFieldValue, 'type' | 'value'>,
  {types = [urlType], width = 200, advancedFields = []} = {} as {
    types?: LinkTypeConfig[];
    width?: number;
    advancedFields?: LinkAdvancedField[];
  }
): Promise<{updates: Array<Record<string, unknown>>}> {
  const updates: Array<Record<string, unknown>> = [];
  container = document.createElement('div');
  container.style.width = `${width}px`;
  document.body.append(container);
  app = createApp({
    render: () =>
      h(LinkControl, {
        control: compactControl(types, advancedFields),
        value,
        editable: true,
        label: 'Link, row 1',
        'onUpdate:value': (value: Record<string, unknown>) =>
          updates.push(value),
      }),
  });
  app.mount(container);
  await nextTick();

  return {updates};
}

/** Opens the chip's menu, as authors do. */
async function openMenu(): Promise<void> {
  await page.getByRole('button', {name: 'Actions, Link, row 1'}).click();
}

/** Opens the link editor from the chip's menu. */
async function editLink(): Promise<void> {
  await openMenu();
  await page.getByText('Edit link').click();
}

function linkPopover(): HTMLElementTagNameMap['craft-popover'] | null {
  return document.querySelector('craft-popover.craft-link-popover');
}

it('lets table columns keep their widths when a compact link has a long URL', async () => {
  container = document.createElement('div');
  container.style.width = '400px';
  document.body.append(container);
  app = createApp({
    render: () =>
      h('table', {style: {width: '100%'}}, [
        h('thead', [
          h('tr', [
            h('th', {style: {width: '50%'}}, 'Event'),
            h('th', {style: {width: '50%'}}, 'Link'),
          ]),
        ]),
        h('tbody', [
          h('tr', [
            h('td', 'Talk'),
            h('td', [
              h(LinkControl, {
                control: compactControl([urlType]),
                value: {
                  type: 'url',
                  value: `https://tickets.example.com/${'long-path/'.repeat(20)}`,
                },
                editable: true,
                label: 'Link, row 1',
              }),
            ]),
          ]),
        ]),
      ]),
  });
  app.mount(container);
  await nextTick();

  const [event, link] = container.querySelectorAll('th');

  await expect
    .element(page.getByText(/long-path\/long-path\//))
    .toBeInTheDocument();
  expect(link!.offsetWidth).toBe(event!.offsetWidth);
});

it('shows the link type and destination side by side once a compact link has room', async () => {
  await mountCell({type: 'url', value: 'https://craftcms.com'}, {width: 400});

  await expect
    .element(page.getByRole('textbox', {name: 'URL'}))
    .toHaveValue('https://craftcms.com');

  container!.style.width = '200px';

  await expect
    .element(page.getByRole('button', {name: 'Actions, Link, row 1'}))
    .toBeVisible();
  expect(container!.querySelector('craft-chip')?.textContent).toContain(
    'https://craftcms.com'
  );
});

it('keeps a wide compact link’s advanced attributes in a popover', async () => {
  const {updates} = await mountCell(
    {type: 'url', value: 'https://craftcms.com'},
    {width: 400, advancedFields: ['title']}
  );

  const cog = page.getByRole('button', {name: 'Advanced, Link, row 1'});

  await cog.click();
  await expect.element(cog).toHaveAttribute('aria-expanded', 'true');
  await page.getByRole('textbox', {name: 'Label'}).fill('Docs');

  expect(updates.at(-1)).toMatchObject({
    type: 'url',
    value: 'https://craftcms.com',
    label: 'Docs',
  });

  await cog.click();

  await expect.poll(() => linkPopover()?.open).toBe(false);
  await expect.element(cog).toHaveAttribute('aria-expanded', 'false');
});

it('summarizes a compact link in a chip, with its menu and form values', async () => {
  await mountCell({
    type: 'url',
    value: 'https://craftcms.com/docs',
    label: 'Docs',
  });

  await expect
    .poll(
      () => container!.querySelector('craft-chip craft-truncate')?.textContent
    )
    .toBe('Docs');
  expect(
    container!.querySelector<HTMLInputElement>(
      'input[name="fields[link][value]"]'
    )?.value
  ).toBe('https://craftcms.com/docs');

  await openMenu();

  await expect.element(page.getByText('Edit link')).toBeVisible();
  await expect.element(page.getByText('Remove link')).toBeVisible();
});

it('offers an element link’s own actions alongside editing the link', async () => {
  await mountCell(
    {
      type: 'entry',
      value: '{entry:12@1:url}',
      element: {
        id: 12,
        label: 'Welcome',
        siteId: 1,
        url: 'https://example.com/welcome',
        canEdit: true,
      },
    },
    {types: [entryType]}
  );

  await openMenu();

  for (const item of [
    'View in a new tab',
    'Edit Entry',
    'Replace',
    'Edit link',
    'Remove link',
  ]) {
    await expect.element(page.getByText(item, {exact: true})).toBeVisible();
  }
});

it('offers to add a link when a compact link is empty', async () => {
  await mountCell({type: 'url', value: ''});

  await expect
    .element(page.getByRole('button', {name: 'Add link, Link, row 1'}))
    .toBeVisible();
  expect(container!.querySelector('craft-chip')).toBeNull();
});

it('removes a compact link, and moves focus to the Add link button', async () => {
  const {updates} = await mountCell({
    type: 'url',
    value: 'https://craftcms.com',
  });

  await openMenu();
  await page.getByText('Remove link').click();

  expect(updates).toEqual([
    expect.objectContaining({type: 'url', value: '', label: ''}),
  ]);
  await expect
    .poll(() => document.activeElement?.getAttribute('aria-label'))
    .toBe('Add link, Link, row 1');
});

it('locks links of unavailable types at any width, and keeps submitting them', async () => {
  // Wide enough for the side-by-side editor, which would swap in the first available type
  await mountCell(
    {type: 'retired', value: 'opaque', label: 'Kept'},
    {width: 400}
  );

  await expect
    .element(page.getByText('This link type is no longer available.'))
    .toBeVisible();
  expect(
    container!
      .querySelector('craft-link-field[compact]')!
      .querySelector('craft-input, craft-select')
  ).toBeNull();
  expect(
    Object.fromEntries(
      [...container!.querySelectorAll<HTMLInputElement>('input[type="hidden"]')]
        .filter((input) => input.value !== '')
        .map((input) => [input.name, input.value])
    )
  ).toEqual({
    'fields[link][type]': 'retired',
    'fields[link][value]': 'opaque',
    'fields[link][label]': 'Kept',
  });

  await openMenu();

  await expect.element(page.getByText('Remove link')).toBeVisible();
  await expect.element(page.getByText('Edit link')).not.toBeInTheDocument();
});

it('draws the popover outside its cell, in the same top-level container', async () => {
  await mountCell({type: 'url', value: ''});
  await expect
    .element(page.getByRole('button', {name: 'Add link, Link, row 1'}))
    .toBeVisible();

  const editor = linkPopover()!.querySelector('craft-link-field')!;

  expect(container!.contains(editor)).toBe(true);
  expect(
    container!.querySelector('.craft-link-field--compact')!.contains(editor)
  ).toBe(false);
});

it('keeps unapplied edits and focus when a chip’s actions open in a compact link', async () => {
  await mountCell(
    {
      type: 'entry',
      value: '{entry:12@1:url}',
      defaultLabel: 'pending',
      element: {
        id: 12,
        label: 'pending',
        siteId: 1,
        url: 'https://example.com/pending',
      },
    },
    {types: [entryType]}
  );

  await editLink();
  await page.getByRole('button', {name: 'Advanced'}).click();
  await page.getByRole('textbox', {name: 'Label'}).fill('Read more');
  await page.getByRole('button', {name: 'Actions', exact: true}).click();

  const view = page.getByRole('button', {name: 'View in a new tab'});
  await expect.element(view).toBeVisible();
  await expect
    .element(page.getByRole('textbox', {name: 'Label'}))
    .toHaveValue('Read more');
  expect(document.activeElement?.tagName).toBe('CRAFT-ACTION-ITEM');
});

it('applies or discards a compact link’s edits from the popover’s buttons', async () => {
  const {updates} = await mountCell({
    type: 'url',
    value: 'https://craftcms.com',
  });

  const url = page.getByRole('textbox', {name: 'URL'});

  await editLink();
  await url.fill('craftcms.com/cancelled');
  await page.getByRole('button', {name: 'Cancel'}).click();
  await expect.poll(() => linkPopover()?.open).toBe(false);
  expect(updates).toEqual([]);

  await editLink();
  await url.fill('craftcms.com/docs');
  await page.getByRole('button', {name: 'Apply'}).click();

  expect(updates).toEqual([
    expect.objectContaining({type: 'url', value: 'craftcms.com/docs'}),
  ]);
});

it('shows a link applied from the popover once the cell widens', async () => {
  await mountCell({type: 'url', value: 'https://craftcms.com'});

  await editLink();
  await page.getByRole('textbox', {name: 'URL'}).fill('craftcms.com/applied');
  await page.getByRole('button', {name: 'Apply'}).click();
  await expect.poll(() => linkPopover()?.open).toBe(false);

  // The field doesn't switch views while focus is in it
  (document.activeElement as HTMLElement | null)?.blur();
  container!.style.width = '400px';

  await expect
    .poll(
      () =>
        container!.querySelector<HTMLInputElement>(
          'craft-link-field[compact] craft-input input'
        )?.value
    )
    .toBe('craftcms.com/applied');
});
