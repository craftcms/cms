import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import type CraftCheckbox from '../checkbox/checkbox.js';
import type CraftOptionRows from './option-rows.js';
import './option-rows.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-option-rows', () => {
  it('fails loudly when its serialized value is invalid', () => {
    const malformed = document.createElement('craft-option-rows');
    const wrongShape = document.createElement('craft-option-rows');
    expect(() => malformed.setAttribute('value', '{')).toThrow(SyntaxError);
    expect(() => wrongShape.setAttribute('value', '{}')).toThrow(TypeError);
    expect(() =>
      document
        .createElement('craft-option-rows')
        .setAttribute('value', '[{"label":"News"}]')
    ).toThrow(TypeError);
    expect(() =>
      document
        .createElement('craft-option-rows')
        .setAttribute(
          'value',
          '[{"label":"News","value":"news","default":"yes"}]'
        )
    ).toThrow('Option row 0 has an invalid default state.');
    expect(() =>
      document
        .createElement('craft-option-rows')
        .setAttribute(
          'value',
          '[{"label":"News","value":"news","mystery":true}]'
        )
    ).toThrow('Option row 0 has an unsupported mystery property.');
  });

  it('edits, adds, removes, reorders, and selects option rows', async () => {
    const element = document.createElement('craft-option-rows');
    const listener = vi.fn();

    element.multipleDefaults = true;
    element.value = [
      {label: 'First Choice', value: 'firstChoice', default: true},
      {label: 'Last Choice', value: 'lastChoice', default: false},
    ];
    element.addEventListener('input', listener);
    document.body.append(element);
    await element.updateComplete;

    expect(element.getAttribute('role')).toBe('group');

    const root = element.shadowRoot!;
    const firstLabel = root.querySelector<HTMLElementTagNameMap['craft-input']>(
      '[data-option-row="0"] [data-option-label]'
    )!;

    firstLabel.value = '24 Hours';
    firstLabel.dispatchEvent(new Event('input', {bubbles: true}));
    await element.updateComplete;

    expect(element.value[0]).toMatchObject({
      label: '24 Hours',
      value: '24Hours',
    });

    root
      .querySelector('[data-option-row="1"] craft-reorder-button')!
      .dispatchEvent(
        new CustomEvent('reorder', {
          bubbles: true,
          detail: {direction: 'up'},
        })
      );
    await element.updateComplete;

    expect(element.value.map((row) => row.label)).toEqual([
      'Last Choice',
      '24 Hours',
    ]);

    const firstDefault = root.querySelector<CraftCheckbox>(
      '[data-option-row="0"] [data-option-default]'
    )!;
    firstDefault.checked = true;
    firstDefault.dispatchEvent(new Event('change', {bubbles: true}));
    await element.updateComplete;

    expect(element.value.map((row) => row.default)).toEqual([true, true]);

    root.querySelector<HTMLElement>('[data-add-option]')!.click();
    await element.updateComplete;
    expect(element.value).toHaveLength(3);

    root
      .querySelector<HTMLElement>('[data-option-row="2"] [data-delete-option]')!
      .click();
    await element.updateComplete;

    expect(element.value).toHaveLength(2);
    expect(listener).toHaveBeenCalledTimes(5);
  });

  it('supports optgroups, icons, colors, and accessible row controls', async () => {
    const element = document.createElement('craft-option-rows');

    element.optgroups = true;
    element.icons = true;
    element.colors = true;
    element.value = [
      {optgroup: 'Published'},
      {
        label: 'News',
        value: 'news',
        icon: 'newspaper',
        color: 'ff0000',
        default: false,
      },
    ];
    document.body.append(element);
    await element.updateComplete;

    const root = element.shadowRoot!;
    const label = root.querySelector(
      '[data-option-row="1"] [data-option-label]'
    )!;
    const icon = root.querySelector(
      '[data-option-row="1"] [data-option-icon]'
    )!;

    expect(root.querySelectorAll('[data-option-optgroup]')).toHaveLength(2);
    expect(root.querySelectorAll('[data-option-color]')).toHaveLength(2);
    expect(root.querySelectorAll('[data-option-icon]')).toHaveLength(2);
    expect(label.getAttribute('aria-label')).toBe('Option Label for News');
    expect(icon.getAttribute('labelled-by')).not.toBe('');

    icon.dispatchEvent(
      new CustomEvent('change', {
        bubbles: true,
        detail: {value: 'star'},
      })
    );
    await element.updateComplete;

    const color = root.querySelector<HTMLElementTagNameMap['craft-input']>(
      '[data-option-row="1"] [data-option-color]'
    )!;
    color.value = '00ff00';
    color.dispatchEvent(new Event('input', {bubbles: true}));
    await element.updateComplete;

    expect(element.value[1]).toMatchObject({
      icon: 'star',
      color: '00ff00',
    });

    const optgroup = root.querySelector<CraftCheckbox>(
      '[data-option-row="1"] [data-option-optgroup]'
    )!;
    optgroup.checked = true;
    optgroup.dispatchEvent(new Event('change', {bubbles: true}));
    await element.updateComplete;

    expect(element.value[1]).toEqual({optgroup: 'News'});
  });

  it('submits the established nested option row shape', async () => {
    const form = document.createElement('form');
    const element = document.createElement('craft-option-rows');

    element.name = 'options';
    element.optgroups = true;
    element.icons = true;
    element.colors = true;
    element.value = [
      {optgroup: 'Published'},
      {
        label: 'News',
        value: 'news',
        icon: 'newspaper',
        color: 'ff0000',
        default: true,
      },
    ];
    form.append(element);
    document.body.append(form);
    await element.updateComplete;

    expect(element.getAttribute('role')).toBe('group');

    const data = new FormData(form);

    expect(data.get('options[0][isOptgroup]')).toBe('1');
    expect(data.get('options[0][label]')).toBe('Published');
    expect(data.get('options[1][label]')).toBe('News');
    expect(data.get('options[1][value]')).toBe('news');
    expect(data.get('options[1][icon]')).toBe('newspaper');
    expect(data.get('options[1][color]')).toBe('ff0000');
    expect(data.get('options[1][default]')).toBe('1');
  });

  it('preserves unchanged row controls when values are refreshed', async () => {
    const element = document.createElement('craft-option-rows');

    element.value = [
      {label: 'News', value: 'news', default: true},
      {label: 'Opinion', value: 'opinion', default: false},
    ];
    document.body.append(element);
    await element.updateComplete;

    const label = element.shadowRoot!.querySelector(
      '[data-option-row="0"] [data-option-label]'
    );

    element.value = element.value.map((row) => ({...row}));
    await element.updateComplete;

    expect(
      element.shadowRoot!.querySelector(
        '[data-option-row="0"] [data-option-label]'
      )
    ).toBe(label);
  });

  it('disables editing without dropping submitted values when read-only', async () => {
    const form = document.createElement('form');
    const element = document.createElement('craft-option-rows');

    element.name = 'options';
    element.icons = true;
    element.value = [
      {
        label: 'News',
        value: 'news',
        icon: 'newspaper',
        default: true,
      },
    ];
    element.readOnly = true;
    form.append(element);
    document.body.append(form);
    await element.updateComplete;

    expect(element.getAttribute('role')).toBe('group');
    expect(element.getAttribute('aria-readonly')).toBe('true');

    for (const control of element.shadowRoot!.querySelectorAll<
      HTMLElement & {disabled?: boolean; readOnly?: boolean}
    >(
      'craft-input, craft-input-color, craft-checkbox, craft-reorder-button, craft-button, craft-icon-picker'
    )) {
      expect(
        control.disabled || control.readOnly || control.hasAttribute('disabled')
      ).toBe(true);
    }

    expect(new FormData(form).get('options[0][value]')).toBe('news');
  });

  it('keeps disabled rows intact while other rows remain editable', async () => {
    const element = document.createElement('craft-option-rows');

    element.value = [
      {label: 'Locked', value: 'locked', default: true, disabled: true},
      {label: 'Open', value: 'open', default: false},
    ];
    document.body.append(element);
    await element.updateComplete;

    const root = element.shadowRoot!;
    const lockedLabel = root.querySelector<
      HTMLElementTagNameMap['craft-input']
    >('[data-option-row="0"] [data-option-label]')!;
    const openLabel = root.querySelector<HTMLElementTagNameMap['craft-input']>(
      '[data-option-row="1"] [data-option-label]'
    )!;

    expect(lockedLabel.readOnly).toBe(true);
    expect(openLabel.readOnly).toBe(false);

    lockedLabel.value = 'Changed';
    lockedLabel.dispatchEvent(new Event('input', {bubbles: true}));
    await element.updateComplete;

    expect(element.value[0]).toEqual({
      label: 'Locked',
      value: 'locked',
      default: true,
      disabled: true,
    });
  });
});
