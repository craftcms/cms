import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftSelect from './select.js';
import './select.js';

async function createSelect(
  attrs: Record<string, string> = {},
  innerHTML = '<select slot="input"><option value="a">A</option></select>'
): Promise<CraftSelect> {
  const element = document.createElement('craft-select') as CraftSelect;
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  element.innerHTML = innerHTML;
  document.body.append(element);
  await element.updateComplete;
  // Wait one more cycle for aria attribute reflection onto the input.
  await element.updateComplete;
  return element;
}

function control(element: CraftSelect): HTMLElement | null {
  return element.querySelector('[slot="input"]');
}

function labelNode(element: CraftSelect): HTMLElement | null {
  return element.querySelector(':scope > [slot="label"]');
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-select label position', () => {
  it('defaults to unset (stacked layout)', async () => {
    const element = await createSelect({label: 'Language'});

    expect(element.hasAttribute('label-position')).toBe(false);
    expect(element.labelPosition).toBeUndefined();
  });

  it('reflects label-position="start" onto the host', async () => {
    const element = await createSelect({
      label: 'Items per page',
      'label-position': 'start',
    });

    expect(element.getAttribute('label-position')).toBe('start');
    expect(element.labelPosition).toBe('start');
  });

  it('keeps the label element and its for/id association regardless of position', async () => {
    const stacked = await createSelect({label: 'Language'});
    const inline = await createSelect({
      label: 'Items per page',
      'label-position': 'start',
    });

    for (const element of [stacked, inline]) {
      const label = labelNode(element);
      const input = control(element);
      expect(label).not.toBeNull();
      expect(label!.textContent).toBeTruthy();
      expect(input!.id).toBeTruthy();
      expect(label!.getAttribute('for')).toBe(input!.id);
    }
  });

  it('is a documented no-op when combined with label-sr-only', async () => {
    const element = await createSelect({
      label: 'Items per page',
      'label-position': 'start',
      'label-sr-only': '',
    });

    const labelWrapper =
      element.shadowRoot!.querySelector('.form-field__label')!;
    expect(getComputedStyle(labelWrapper).position).toBe('absolute');
  });

  it('falls back to the stacked layout when help text is present', async () => {
    const element = await createSelect({
      label: 'Items per page',
      'label-position': 'start',
      'help-text': 'Applies to every source in this index.',
    });

    expect(element.hasAttribute('has-help-text')).toBe(true);
  });

  it('does not set has-help-text without help text', async () => {
    const element = await createSelect({
      label: 'Items per page',
      'label-position': 'start',
    });

    expect(element.hasAttribute('has-help-text')).toBe(false);
  });

  it('reflects has-help-text for slotted help text too', async () => {
    const element = await createSelect(
      {label: 'Items per page', 'label-position': 'start'},
      '<select slot="input"><option value="a">A</option></select>' +
        '<span slot="help-text">Applies to every source in this index.</span>'
    );

    expect(element.hasAttribute('has-help-text')).toBe(true);
  });
});
