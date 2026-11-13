import '@craftcms/ui/components/button/button';
import '@craftcms/ui/components/input/input';
import '@craftcms/ui/components/select/select';
import '@craftcms/ui/components/switch/switch';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {page, userEvent} from 'vite-plus/test/browser/context';
import './craft-link-field';
import type {LinkChangeDetail, LinkTypeConfig} from './craft-link-field';

const selector = vi.hoisted(() => {
  const listeners = new Map<string, () => void>();

  return {
    listeners,
    createElementSelectorModal: vi.fn(async () => ({
      on: (event: string, listener: () => void) => {
        listeners.set(event, listener);
      },
      destroy: () => {},
    })),
  };
});

type SelectorSettings = {
  triggerElement: () => HTMLElement;
  onHandoff: (settled: Promise<void>) => void;
};

vi.mock(
  '@/modules/element-selector-modal/create-element-selector-modal',
  () => selector
);

/**
 * The link type and its destination render as one grouped input, like a
 * grouped UI field (“Field Limit”). Runs in Chromium so names, descriptions
 * and layout are the browser's own.
 */

const types: LinkTypeConfig[] = [
  {
    id: 'url',
    label: 'URL',
    kind: 'text',
    prefixes: ['https://', 'http://'],
    pattern: '^https?://',
  },
  {id: 'entry', label: 'Entry', kind: 'element', elementType: 'Entry'},
];

let linkField: HTMLElementTagNameMap['craft-link-field'];

beforeEach(async () => {
  linkField = document.createElement('craft-link-field');
  linkField.types = types;
  document.body.append(linkField);
  await linkField.updateComplete;
});

afterEach(() => {
  linkField.remove();
});

it('sets the link type beside its destination, each with its own label', async () => {
  const type = page.getByRole('combobox', {name: 'Link Type'});
  const url = page.getByRole('textbox', {name: 'URL'});

  await expect.element(type).toBeVisible();
  await expect.element(url).toBeVisible();

  const typeBox = type.element().getBoundingClientRect();
  const urlBox = url.element().getBoundingClientRect();
  expect(urlBox.top).toBe(typeBox.top);
  expect(urlBox.left).toBeGreaterThanOrEqual(typeBox.right);
});

it('labels the element chooser with its link type', async () => {
  await chooseType('entry');

  await expect
    .element(
      page
        .getByRole('group', {name: 'Entry'})
        .getByRole('button', {name: 'Choose'})
    )
    .toBeVisible();
});

it('describes the destination with its error', async () => {
  const url = page.getByRole('textbox', {name: 'URL'});

  await page.getByRole('button', {name: 'Apply'}).click();

  await expect.element(url).toHaveAttribute('aria-invalid', 'true');
  await expect
    .element(url)
    .toHaveAccessibleDescription(/URL cannot be blank\./);
});

async function chooseType(id: string): Promise<void> {
  await page.getByRole('combobox', {name: 'Link Type'}).selectOptions(id);
  await linkField.updateComplete;
}

async function openSelector(): Promise<{
  choose: ReturnType<typeof page.getByRole>;
  settings: SelectorSettings;
}> {
  selector.createElementSelectorModal.mockClear();
  selector.listeners.clear();
  linkField.types = [
    types[0]!,
    {
      id: 'entry',
      label: 'Entry',
      kind: 'element',
      elementType: 'Entry',
      refHandle: 'entry',
    },
  ];
  await linkField.updateComplete;
  await chooseType('entry');
  const choose = page
    .getByRole('group', {name: 'Entry'})
    .getByRole('button', {name: 'Choose'});
  await choose.click();
  await expect
    .poll(() => selector.createElementSelectorModal.mock.calls.length)
    .toBe(1);

  const [, settings] = selector.createElementSelectorModal.mock
    .calls[0] as unknown as [string, SelectorSettings];

  return {choose, settings};
}

it('places slideouts opened from its element selector in its own panel', async () => {
  const {choose, settings} = await openSelector();

  expect(settings.triggerElement()).toBe(choose.element());
});

it('stays suspended while its element selector hands off to a slideout', async () => {
  const ends: Event[] = [];
  linkField.addEventListener('element-select-end', (event) => ends.push(event));
  const {settings} = await openSelector();

  let settle!: () => void;
  settings.onHandoff(new Promise((resolve) => (settle = resolve)));
  selector.listeners.get('close')!();

  // A link popover resuming now would be dismissed by the slideout taking
  // focus, taking the link with it.
  await Promise.resolve();
  expect(ends).toHaveLength(0);

  settle();
  await expect.poll(() => ends.length).toBe(1);
});

it('resumes as soon as its element selector closes otherwise', async () => {
  const ends: Event[] = [];
  linkField.addEventListener('element-select-end', (event) => ends.push(event));
  await openSelector();

  selector.listeners.get('close')!();

  expect(ends).toHaveLength(1);
});

describe('live', () => {
  let changes: LinkChangeDetail[];

  beforeEach(async () => {
    changes = [];
    linkField.live = true;
    linkField.showLabelField = true;
    linkField.advancedFields = ['target'];
    linkField.addEventListener('link-change', (event) => {
      changes.push((event as CustomEvent<LinkChangeDetail>).detail);
    });
    await linkField.updateComplete;
  });

  it('applies each edit without rewriting what’s being typed', async () => {
    const url = page.getByRole('textbox', {name: 'URL'});

    await url.fill('craftcms.com');

    expect(changes.at(-1)).toMatchObject({
      kind: 'typing',
      value: {type: 'url', value: 'https://craftcms.com'},
    });
    // Echo the value back, as the field's owner does.
    linkField.modelValue = {...changes.at(-1)!.value};
    await linkField.updateComplete;
    await expect.element(url).toHaveValue('craftcms.com');
    await expect
      .element(page.getByRole('button', {name: 'Apply'}))
      .not.toBeInTheDocument();
  });

  it('sits in a pane, with its label and attributes under “Advanced”', async () => {
    expect(linkField.querySelector(':scope > craft-pane')).not.toBeNull();

    await page.getByRole('button', {name: 'Advanced'}).click();
    await expect
      .element(page.getByRole('textbox', {name: 'Label'}))
      .toBeVisible();
    page.getByRole('switch', {name: 'Open in a new tab'}).element().focus();
    await userEvent.keyboard(' ');

    expect(changes.at(-1)).toMatchObject({
      kind: 'discrete',
      value: {target: '_blank'},
    });
  });
});
