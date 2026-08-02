import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import './field-layout.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-field-layout', () => {
  it('edits layout elements while preserving tabs and stable control identity', async () => {
    const element = document.createElement('craft-field-layout');
    const listener = vi.fn();

    element.availableElements = [
      {
        key: 'field:title',
        label: 'Title',
        value: {type: 'TitleField'},
        multiple: false,
      },
      {
        key: 'field:body',
        label: 'Body',
        value: {type: 'BodyField'},
        multiple: false,
      },
      {
        key: 'ui:line',
        label: 'Line',
        value: {type: 'HorizontalRule'},
        multiple: true,
      },
    ];
    element.value = {
      tabs: [
        {
          uid: 'content-tab',
          name: 'Content',
          elements: [
            {uid: 'title-element', type: 'TitleField'},
            {uid: 'body-element', type: 'BodyField'},
          ],
        },
        {
          uid: 'meta-tab',
          name: 'Meta',
          elements: [],
        },
      ],
      marker: 'host-owned',
    };
    element.addEventListener('input', listener);
    document.body.append(element);
    await element.updateComplete;

    const contentTab = element.shadowRoot!.querySelector(
      '[data-field-layout-tab="content-tab"]'
    );
    const titleRow = element.shadowRoot!.querySelector(
      '[data-field-layout-element="title-element"]'
    );

    element.value = structuredClone(element.value);
    await element.updateComplete;

    expect(
      element.shadowRoot!.querySelector('[data-field-layout-tab="content-tab"]')
    ).toBe(contentTab);
    expect(
      element.shadowRoot!.querySelector(
        '[data-field-layout-element="title-element"]'
      )
    ).toBe(titleRow);

    titleRow!.querySelector('craft-reorder-button')!.dispatchEvent(
      new CustomEvent('reorder', {
        bubbles: true,
        detail: {direction: 'down'},
      })
    );
    await element.updateComplete;

    expect(element.value.tabs?.[0]?.elements?.map(({uid}) => uid)).toEqual([
      'body-element',
      'title-element',
    ]);
    expect(element.value.tabs?.[1]).toEqual({
      uid: 'meta-tab',
      name: 'Meta',
      elements: [],
    });
    expect(element.value.marker).toBe('host-owned');

    const metaTab = element.shadowRoot!.querySelector(
      '[data-field-layout-tab="meta-tab"]'
    )!;
    const select = metaTab.querySelector<HTMLSelectElement>(
      '[data-field-layout-available]'
    )!;

    select.value = 'ui:line';
    select.dispatchEvent(new Event('change', {bubbles: true}));
    metaTab
      .querySelector('[data-field-layout-add]')!
      .dispatchEvent(new CustomEvent('activate', {bubbles: true}));
    await element.updateComplete;

    expect(element.value.tabs?.[1]?.elements?.[0]?.type).toBe('HorizontalRule');
    element
      .shadowRoot!.querySelector(
        '[data-field-layout-element="title-element"] [data-field-layout-remove]'
      )!
      .dispatchEvent(new CustomEvent('activate', {bubbles: true}));
    await element.updateComplete;

    expect(element.value.tabs?.[0]?.elements?.map(({uid}) => uid)).toEqual([
      'body-element',
    ]);
    expect(listener).toHaveBeenCalledTimes(3);
  });

  it('edits and submits tabs and generated fields under the host input name', async () => {
    const form = document.createElement('form');
    const element = document.createElement('craft-field-layout');

    element.name = 'settings[fieldLayout]';
    element.withGeneratedFields = true;
    element.value = {
      tabs: [{uid: 'content-tab', name: 'Content', elements: []}],
      generatedFields: [
        {
          uid: 'reading-time',
          name: 'Reading time',
          handle: 'readingTime',
          template: 'words / 200',
        },
        {
          uid: 'summary',
          name: 'Summary',
          handle: 'summary',
          template: 'entry.summary',
        },
      ],
    };
    form.append(element);
    document.body.append(form);
    await element.updateComplete;

    const tabName = element.shadowRoot!.querySelector<
      HTMLElementTagNameMap['craft-input']
    >('[data-field-layout-tab] craft-input')!;

    tabName.value = 'Main content';
    tabName.dispatchEvent(new Event('input', {bubbles: true}));
    await element.updateComplete;

    element
      .shadowRoot!.querySelector('[data-field-layout-add-tab]')!
      .dispatchEvent(new CustomEvent('activate', {bubbles: true}));
    await element.updateComplete;

    const addedTab = element.shadowRoot!.querySelectorAll(
      '[data-field-layout-tab]'
    )[1]!;

    addedTab.querySelector('craft-reorder-button')!.dispatchEvent(
      new CustomEvent('reorder', {
        bubbles: true,
        detail: {direction: 'up'},
      })
    );
    await element.updateComplete;

    const generatedFields = element.shadowRoot!.querySelectorAll(
      '[data-generated-field]'
    );
    const template = generatedFields[0]!.querySelector('textarea')!;

    template.value = 'words / 180';
    template.dispatchEvent(new Event('input', {bubbles: true}));
    await element.updateComplete;
    generatedFields[0]!.querySelector('craft-reorder-button')!.dispatchEvent(
      new CustomEvent('reorder', {
        bubbles: true,
        detail: {direction: 'down'},
      })
    );
    await element.updateComplete;

    expect(element.value.tabs?.map(({name}) => name)).toEqual([
      'New Tab',
      'Main content',
    ]);
    expect(element.value.generatedFields?.map(({uid}) => uid)).toEqual([
      'summary',
      'reading-time',
    ]);
    expect(element.value.generatedFields?.[1]?.template).toBe('words / 180');

    const data = new FormData(form);

    expect(data.get('settings[fieldLayout][tabs][1][name]')).toBe(
      'Main content'
    );
    expect(
      data.get('settings[fieldLayout][generatedFields][1][template]')
    ).toBe('words / 180');

    element
      .shadowRoot!.querySelector(
        '[data-field-layout-tab] [data-field-layout-remove-tab]'
      )!
      .dispatchEvent(new CustomEvent('activate', {bubbles: true}));
    await element.updateComplete;

    expect(element.value.tabs?.map(({name}) => name)).toEqual(['Main content']);

    element
      .shadowRoot!.querySelector(
        '[data-generated-field] [data-generated-field-remove]'
      )!
      .dispatchEvent(new CustomEvent('activate', {bubbles: true}));
    await element.updateComplete;
    element
      .shadowRoot!.querySelector('[data-generated-field-add]')!
      .dispatchEvent(new CustomEvent('activate', {bubbles: true}));
    await element.updateComplete;

    expect(element.value.generatedFields).toHaveLength(2);
    expect(element.value.generatedFields?.[0]?.uid).toBe('reading-time');
  });

  it('exposes accessible read-only layout controls without accepting updates', async () => {
    const element = document.createElement('craft-field-layout');
    const listener = vi.fn();

    element.availableElements = [
      {
        key: 'field:body',
        label: 'Body',
        value: {type: 'BodyField'},
        multiple: false,
      },
    ];
    element.value = {
      tabs: [
        {
          uid: 'content-tab',
          name: 'Content',
          elements: [{uid: 'title-element', type: 'TitleField'}],
        },
      ],
    };
    element.readOnly = true;
    element.setAttribute('aria-labelledby', 'field-layout-label');
    element.setAttribute('aria-describedby', 'field-layout-errors');
    element.addEventListener('input', listener);
    document.body.append(element);
    await element.updateComplete;

    const controls = element.shadowRoot!.querySelectorAll<
      HTMLElement & {disabled?: boolean}
    >('craft-button, craft-input, craft-reorder-button, select, textarea');

    expect(element.getAttribute('role')).toBe('group');
    expect(element.getAttribute('aria-readonly')).toBe('true');
    expect(element.getAttribute('aria-labelledby')).toBe('field-layout-label');
    expect(element.getAttribute('aria-describedby')).toBe(
      'field-layout-errors'
    );
    expect(Array.from(controls).every((control) => control.disabled)).toBe(
      true
    );
    expect(
      element
        .shadowRoot!.querySelector('[data-field-layout-remove]')!
        .getAttribute('aria-label')
    ).toBe('Remove TitleField');
    expect(
      element
        .shadowRoot!.querySelector('[data-field-layout-remove-tab]')!
        .getAttribute('aria-label')
    ).toBe('Remove tab Content');

    element
      .shadowRoot!.querySelector('[data-field-layout-remove]')!
      .dispatchEvent(new CustomEvent('activate', {bubbles: true}));
    element
      .shadowRoot!.querySelector('[data-field-layout-add-tab]')!
      .dispatchEvent(new CustomEvent('activate', {bubbles: true}));
    await element.updateComplete;

    expect(element.value.tabs).toHaveLength(1);
    expect(element.value.tabs?.[0]?.elements).toHaveLength(1);
    expect(listener).not.toHaveBeenCalled();
  });
});
