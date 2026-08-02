import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import './keyed-table.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-keyed-table', () => {
  it('updates one cell, preserves siblings, and submits stable nested names', async () => {
    const form = document.createElement('form');
    const element = document.createElement('craft-keyed-table');
    const listener = vi.fn();

    element.name = 'settings[sites]';
    element.columns = [
      {key: 'uriFormat', label: 'Entry URI Format', code: true},
      {key: 'template', label: 'Template', placeholder: 'entries/_entry'},
    ];
    element.rows = [{key: 'english', label: 'English'}];
    element.value = {
      english: {uriFormat: 'news/{slug}', template: 'entries/article'},
    };
    element.addEventListener('input', listener);
    form.append(element);
    document.body.append(form);
    await element.updateComplete;

    const input = element.shadowRoot!.querySelector<
      HTMLElementTagNameMap['craft-input']
    >('[data-keyed-table-cell="english:uriFormat"]')!;

    expect(input.getAttribute('label')).toBe('Entry URI Format');
    input.value = 'stories/{slug}';
    input.dispatchEvent(new Event('input', {bubbles: true, composed: true}));
    await element.updateComplete;

    expect(element.value.english).toEqual({
      uriFormat: 'stories/{slug}',
      template: 'entries/article',
    });
    expect(listener).toHaveBeenCalledOnce();

    const data = new FormData(form);

    expect(data.get('settings[sites][english][uriFormat]')).toBe(
      'stories/{slug}'
    );
    expect(data.get('settings[sites][english][template]')).toBe(
      'entries/article'
    );
  });

  it('honors read-only state and rejects malformed definitions', async () => {
    const element = document.createElement('craft-keyed-table');

    element.columns = [{key: 'uri', label: 'URI'}];
    element.rows = [{key: 'english', label: 'English'}];
    element.value = {english: {uri: 'news/{slug}'}};
    element.readOnly = true;
    document.body.append(element);
    await element.updateComplete;

    expect(element.getAttribute('aria-readonly')).toBe('true');
    expect(
      element.shadowRoot!.querySelector<HTMLElementTagNameMap['craft-input']>(
        'craft-input'
      )!.readOnly
    ).toBe(true);

    const invalid = document.createElement('craft-keyed-table');

    invalid.columns = [{key: 'uri', label: 'URI', unknown: true}] as never;
    document.body.append(invalid);

    await expect(invalid.updateComplete).rejects.toThrow(
      'unsupported unknown property'
    );
  });
});
