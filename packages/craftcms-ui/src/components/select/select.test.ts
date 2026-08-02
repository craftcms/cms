import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftSelect from './select.js';
import './select.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-select options', () => {
  it('materializes serialized options as native controls', async () => {
    const element = document.createElement('craft-select');
    element.options = [
      {label: 'Choose', value: null},
      {
        type: 'optgroup',
        label: 'Visible',
        options: [
          {label: 'Published', value: 'published'},
          {label: 'Archived', value: 'archived', disabled: true},
        ],
      },
      {
        type: 'optgroup',
        label: 'Unavailable',
        disabled: true,
        options: [
          {
            label: 'Draft',
            value: 'draft',
            data: {reason: 'restricted'},
          },
        ],
      },
    ];
    element.modelValue = 'published';
    document.body.append(element);
    await element.updateComplete;

    const select = element.querySelector('select')!;
    const options = select.querySelectorAll('option');

    expect(select.value).toBe('published');
    expect(options[0]!.value).toBe('');
    expect(options[2]!.disabled).toBe(true);
    expect(options[3]!.dataset.reason).toBe('restricted');
    expect(select.querySelectorAll('optgroup')[1]!.disabled).toBe(true);

    select.value = '';
    select.dispatchEvent(new Event('change', {bubbles: true}));

    expect(element.modelValue).toBe('');
  });

  it('preserves server-rendered native options without client options', async () => {
    const template = document.createElement('template');
    template.innerHTML = `
      <craft-select>
        <select slot="input">
          <option value="draft">Draft</option>
        </select>
      </craft-select>
    `;
    document.body.append(template.content);
    const element = document.body.querySelector('craft-select') as CraftSelect;
    await element.updateComplete;

    expect(element.querySelectorAll('select')).toHaveLength(1);
    expect(element.querySelector('option')?.textContent).toBe('Draft');
  });
});
