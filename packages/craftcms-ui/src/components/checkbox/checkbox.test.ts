import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftCheckbox from './checkbox.js';
import './checkbox.js';

/**
 * Parses markup inertly and appends the complete subtree, matching how
 * server-rendered islands reach the page (`appendElementHtml` clones parsed
 * nodes into the container) — the element upgrades with its children present.
 */
async function createFromMarkup(markup: string): Promise<CraftCheckbox> {
  const template = document.createElement('template');
  template.innerHTML = markup;
  document.body.append(template.content);
  const element = document.body.querySelector(
    'craft-checkbox'
  ) as CraftCheckbox;
  await element.updateComplete;
  return element;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('SSR hydration', () => {
  it('adopts state from a slotted input instead of wiping it', async () => {
    const element = await createFromMarkup(`
      <craft-checkbox>
        <input slot="input" type="checkbox" name="allowedKinds[]" value="image" checked>
        <label slot="label">Image</label>
      </craft-checkbox>
    `);
    const input = element.querySelector('input')!;

    expect(element.name).toBe('allowedKinds[]');
    expect(element.checked).toBe(true);
    expect(input.name).toBe('allowedKinds[]');
    expect(input.checked).toBe(true);
    expect(input.value).toBe('image');
  });

  it('adopts disabled from the slotted input', async () => {
    const element = await createFromMarkup(`
      <craft-checkbox>
        <input slot="input" type="checkbox" name="agree" disabled>
      </craft-checkbox>
    `);

    expect(element.disabled).toBe(true);
    expect(element.querySelector('input')!.disabled).toBe(true);
  });

  it('lets host attributes win over the slotted input', async () => {
    const element = await createFromMarkup(`
      <craft-checkbox name="fromHost">
        <input slot="input" type="checkbox" name="fromInput">
      </craft-checkbox>
    `);

    expect(element.name).toBe('fromHost');
    expect(element.querySelector('input')!.name).toBe('fromHost');
  });

  it('keeps the label association so label clicks toggle', async () => {
    const element = await createFromMarkup(`
      <craft-checkbox>
        <input slot="input" type="checkbox" id="cb" name="agree" value="1">
        <label slot="label" for="cb">I agree</label>
      </craft-checkbox>
    `);
    const input = element.querySelector('input')!;
    const label = element.querySelector('label')!;

    expect(label.getAttribute('for')).toBe(input.id);

    label.click();
    await element.updateComplete;

    expect(element.checked).toBe(true);
    expect(input.checked).toBe(true);
  });

  it('still toggles after hydration', async () => {
    const element = await createFromMarkup(`
      <craft-checkbox>
        <input slot="input" type="checkbox" name="agree" value="1">
      </craft-checkbox>
    `);
    const input = element.querySelector('input')!;

    input.click();
    await element.updateComplete;

    expect(element.checked).toBe(true);
    expect(input.checked).toBe(true);
  });

  it('mirrors a direct input.checked write (no change event) onto the host', async () => {
    // The legacy checkbox-select "All" handler flips options via jQuery
    // `.prop('checked', …)`, which sets the property without firing an event.
    const element = await createFromMarkup(`
      <craft-checkbox>
        <input slot="input" type="checkbox" name="sources[]" value="one">
      </craft-checkbox>
    `);
    const input = element.querySelector('input')!;

    input.checked = true;
    expect(element.checked).toBe(true);

    input.checked = false;
    expect(element.checked).toBe(false);
  });

  it('mirrors a direct input.disabled write and allows selecting afterwards', async () => {
    // The legacy "All" handler re-enables options via `.prop({checked, disabled})`
    // with no events; a stale host `disabled` would block the
    // next user toggle.
    const element = await createFromMarkup(`
      <craft-checkbox>
        <input slot="input" type="checkbox" name="allowedKinds[]" value="image" checked disabled>
      </craft-checkbox>
    `);
    const input = element.querySelector('input')!;

    expect(element.disabled).toBe(true);

    // "All" unchecked: options reset + re-enabled, properties only
    input.checked = false;
    input.disabled = false;
    await element.updateComplete;

    expect(element.disabled).toBe(false);
    expect(element.checked).toBe(false);

    // the user can now select the option
    input.click();
    await element.updateComplete;

    expect(element.checked).toBe(true);
    expect(input.checked).toBe(true);
  });

  it('ignores the always-post hidden input when adopting state', async () => {
    // The PHP component nests the always-post hidden input in the host so the
    // control slots into <craft-field> as a single element. It matches no
    // slot, so it never renders — but it stays in the form's DOM and posts.
    const element = await createFromMarkup(`
      <craft-checkbox>
        <input type="hidden" name="agree" value="">
        <input slot="input" type="checkbox" id="cb" name="agree" value="1" checked>
        <label slot="label" for="cb">I agree</label>
      </craft-checkbox>
    `);
    const hidden = element.querySelector<HTMLInputElement>(
      'input[type="hidden"]'
    )!;
    const input = element.querySelector<HTMLInputElement>(
      'input[type="checkbox"]'
    )!;

    expect(element.name).toBe('agree');
    expect(element.checked).toBe(true);
    expect(input.value).toBe('1');
    expect(hidden.value).toBe('');
    // No default slot to fall into, so the hidden input is never rendered.
    expect(element.shadowRoot!.querySelector('slot:not([name])')).toBeNull();
  });

  it('remains client-drivable without slotted content', async () => {
    const element = await createFromMarkup(
      '<craft-checkbox name="agree" checked></craft-checkbox>'
    );
    const input = element.querySelector('input')!;

    expect(input.name).toBe('agree');
    expect(input.checked).toBe(true);
  });
});

describe('semantics', () => {
  it('names the input with its label and describes it with its help text', async () => {
    const element = await createFromMarkup(
      '<craft-checkbox label="Enable indexing" help-text="Search engines may index it."></craft-checkbox>'
    );
    const input = element.querySelector('input')!;
    const label = element.querySelector<HTMLElement>('[slot="label"]')!;
    const helpText = element.querySelector<HTMLElement>('[slot="help-text"]')!;

    expect(label.getAttribute('for')).toBe(input.id);
    expect(input.getAttribute('aria-labelledby')).toBe(label.id);
    expect(input.getAttribute('aria-describedby')).toBe(helpText.id);
  });

  it('marks the input required and invalid', async () => {
    const element = await createFromMarkup(
      '<craft-checkbox label="Agree" required invalid></craft-checkbox>'
    );
    const input = element.querySelector('input')!;

    expect(input.getAttribute('aria-required')).toBe('true');
    expect(input.getAttribute('aria-invalid')).toBe('true');

    element.invalid = false;
    await element.updateComplete;

    expect(input.hasAttribute('aria-invalid')).toBe(false);
  });

  it('shows indeterminate on the input', async () => {
    const element = await createFromMarkup(
      '<craft-checkbox label="All" indeterminate></craft-checkbox>'
    );

    expect(element.querySelector('input')!.indeterminate).toBe(true);
  });
});

describe('events', () => {
  it("fires the input's own events when the user toggles it", async () => {
    const element = await createFromMarkup(
      '<craft-checkbox label="Agree"></craft-checkbox>'
    );
    const seen: string[] = [];
    element.addEventListener('input', () =>
      seen.push(`input:${element.checked}`)
    );
    element.addEventListener('change', () =>
      seen.push(`change:${element.checked}`)
    );

    element.querySelector('input')!.click();

    expect(seen).toEqual(['input:true', 'change:true']);
  });

  it('is silent when checked from code, and reflects the new state', async () => {
    const element = await createFromMarkup(
      '<craft-checkbox label="Agree"></craft-checkbox>'
    );
    const seen: string[] = [];
    element.addEventListener('input', () => seen.push('input'));
    element.addEventListener('change', () => seen.push('change'));

    element.checked = true;
    await element.updateComplete;

    expect(seen).toEqual([]);
    expect(element.querySelector('input')!.checked).toBe(true);
    expect(element.hasAttribute('checked')).toBe(true);
  });

  it('posts its value from the input', async () => {
    const element = await createFromMarkup(
      '<craft-checkbox name="kinds[]" value="image" checked></craft-checkbox>'
    );
    const input = element.querySelector('input')!;

    expect(element.value).toBe('image');
    expect([input.name, input.value, input.checked]).toEqual([
      'kinds[]',
      'image',
      true,
    ]);
  });
});
