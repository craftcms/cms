import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import type CraftEditableTable from './editable-table.js';
import './editable-table.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-editable-table', () => {
  it('edits typed cells and submits established nested names', async () => {
    const form = document.createElement('form');
    const element = document.createElement('craft-editable-table');
    const listener = vi.fn();

    element.name = 'settings[rows]';
    element.includeRowId = true;
    element.columns = [
      {key: 'title', label: 'Title', type: 'text'},
      {key: 'published', label: 'Published', type: 'checkbox'},
      {
        key: 'category',
        label: 'Category',
        type: 'select',
        options: [
          {label: 'News', value: 'news'},
          {label: 'Opinion', value: 'opinion'},
        ],
      },
    ];
    element.value = [
      {
        rowId: 'story-row',
        title: 'Lead story',
        published: false,
        category: 'news',
      },
    ];
    element.addEventListener('input', listener);
    form.append(element);
    document.body.append(form);
    await element.updateComplete;

    const root = element.shadowRoot!;
    const title = root.querySelector<HTMLElementTagNameMap['craft-input']>(
      '[data-table-cell="story-row:title"]'
    )!;
    const published = root.querySelector<
      HTMLElementTagNameMap['craft-checkbox']
    >('[data-table-cell="story-row:published"]')!;
    const category = root.querySelector<HTMLSelectElement>(
      '[data-table-cell="story-row:category"] select'
    )!;

    title.value = 'Analysis';
    title.dispatchEvent(new Event('input', {bubbles: true, composed: true}));
    await element.updateComplete;
    published.checked = true;
    published.dispatchEvent(
      new Event('change', {bubbles: true, composed: true})
    );
    await element.updateComplete;
    category.value = 'opinion';
    category.dispatchEvent(
      new Event('change', {bubbles: true, composed: true})
    );
    await element.updateComplete;

    expect(element.value).toEqual([
      {
        rowId: 'story-row',
        title: 'Analysis',
        published: true,
        category: 'opinion',
      },
    ]);
    expect(listener).toHaveBeenCalledTimes(3);

    const data = new FormData(form);

    expect(data.get('settings[rows][0][rowId]')).toBe('story-row');
    expect(data.get('settings[rows][0][title]')).toBe('Analysis');
    expect(data.get('settings[rows][0][published]')).toBe('1');
    expect(data.get('settings[rows][0][category]')).toBe('opinion');
  });

  it('adds, reorders, and deletes keyed rows without changing their keys', async () => {
    const element = document.createElement('craft-editable-table');

    element.keyed = true;
    element.columns = [{key: 'label', label: 'Label', type: 'text'}];
    element.defaultRow = {label: 'New'};
    element.value = {
      first: {label: 'First'},
      second: {label: 'Second'},
    };
    document.body.append(element);
    await element.updateComplete;

    const firstRow = element.shadowRoot!.querySelector(
      '[data-row-key="first"]'
    );
    element
      .shadowRoot!.querySelector<HTMLElement>(
        '[data-row-key="first"] craft-reorder-button'
      )!
      .dispatchEvent(
        new CustomEvent('reorder', {
          bubbles: true,
          composed: true,
          detail: {direction: 'down'},
        })
      );
    await element.updateComplete;

    expect(Object.keys(element.value)).toEqual(['second', 'first']);
    expect(element.shadowRoot!.querySelector('[data-row-key="first"]')).toBe(
      firstRow
    );

    element.shadowRoot!.querySelector<HTMLElement>('[data-add-row]')!.click();
    await element.updateComplete;

    expect(Object.keys(element.value)).toEqual(['second', 'first', 'new1']);

    element
      .shadowRoot!.querySelector<HTMLElement>(
        '[data-row-key="first"] [data-delete-row]'
      )!
      .click();
    await element.updateComplete;

    expect(Object.keys(element.value)).toEqual(['second', 'new1']);
  });

  it('shares edited column definitions with a dependent table', async () => {
    const form = document.createElement('form');
    const source = document.createElement('craft-editable-table');
    const defaults = document.createElement('craft-editable-table');

    source.name = 'settings[columns]';
    source.sourceName = 'columns';
    source.keyed = true;
    source.definesColumns = true;
    source.columns = [
      {key: 'heading', label: 'Heading', type: 'text'},
      {key: 'handle', label: 'Handle', type: 'text'},
      {key: 'type', label: 'Type', type: 'text'},
    ];
    source.value = {
      headline: {heading: 'Headline', handle: 'headline', type: 'singleline'},
    };
    defaults.name = 'settings[defaults]';
    defaults.sourceName = 'defaults';
    defaults.columnsFrom = 'columns';
    defaults.value = [{headline: 'Lead story'}];
    form.append(source, defaults);
    document.body.append(form);
    await source.updateComplete;
    await defaults.updateComplete;

    expect(defaults.shadowRoot!.querySelector('th')?.textContent?.trim()).toBe(
      'Headline'
    );

    const heading = source.shadowRoot!.querySelector<
      HTMLElementTagNameMap['craft-input']
    >('[data-table-cell="headline:heading"]')!;

    heading.value = 'Story title';
    heading.dispatchEvent(new Event('input', {bubbles: true, composed: true}));
    await source.updateComplete;
    await defaults.updateComplete;

    expect(defaults.shadowRoot!.querySelector('th')?.textContent?.trim()).toBe(
      'Story title'
    );
  });

  it('keeps row identity and disables every editing control when read-only', async () => {
    const element = document.createElement('craft-editable-table');

    element.columns = [{key: 'title', label: 'Title', type: 'text'}];
    element.includeRowId = true;
    element.value = [{rowId: 'draft-row', title: 'Draft'}];
    element.readOnly = true;
    document.body.append(element);
    await element.updateComplete;

    const row = element.shadowRoot!.querySelector('[data-editable-table-row]');

    expect(element.getAttribute('aria-readonly')).toBe('true');
    expect(
      element
        .shadowRoot!.querySelector<HTMLElementTagNameMap['craft-input']>(
          'craft-input'
        )!
        .getAttribute('label')
    ).toBe('Title');

    for (const control of element.shadowRoot!.querySelectorAll<
      HTMLElement & {disabled?: boolean; readOnly?: boolean}
    >('craft-input, craft-reorder-button, craft-button')) {
      expect(control.disabled || control.readOnly).toBe(true);
    }

    element.value = [{rowId: 'draft-row', title: 'Ready'}];
    await element.updateComplete;

    expect(element.shadowRoot!.querySelector('[data-editable-table-row]')).toBe(
      row
    );
  });

  it('fails before rendering invalid columns and values', async () => {
    const invalidColumn = document.createElement('craft-editable-table');

    invalidColumn.columns = [
      {key: 'value', label: 'Value', type: 'unsupported'},
    ] as never;
    document.body.append(invalidColumn);

    await expect(invalidColumn.updateComplete).rejects.toThrow(
      'does not support the unsupported type'
    );

    const invalidValue = document.createElement('craft-editable-table');

    invalidValue.value = {row: {value: 'not ordered'}};
    document.body.append(invalidValue);

    await expect(invalidValue.updateComplete).rejects.toThrow(
      'must be a JSON array'
    );
  });
});
