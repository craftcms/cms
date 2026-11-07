import {beforeEach, describe, expect, it} from 'vite-plus/test';

import './input.js';
import type CraftInput from './input.js';

async function createInput(
  attrs: Record<string, string> = {},
  innerHTML = '<label slot="label">Handle</label>'
): Promise<CraftInput> {
  const element = document.createElement('craft-input') as CraftInput;
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  element.innerHTML = innerHTML;
  document.body.append(element);
  await element.updateComplete;
  // Lion wires the label and feedback relations after its own first update,
  // so give it a turn before reading them.
  await new Promise((resolve) => setTimeout(resolve, 0));
  await element.updateComplete;
  return element;
}

/** Lion's native input, which is the element the component drives. */
function native(element: CraftInput): HTMLInputElement {
  return element.querySelector('input')!;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-input', () => {
  it('renders a native input with the label wired to it', async () => {
    const element = await createInput();
    const label = element.querySelector('label')!;

    expect(native(element)).toBeTruthy();
    // The label points at the input's id, which is what makes clicking the
    // label focus the field.
    expect(label.getAttribute('for')).toBe(native(element).id);
    expect(native(element).id).not.toBe('');
  });

  it('defaults to a medium control', async () => {
    expect((await createInput()).size).toBe('medium');
  });

  it('writes its value to the native input', async () => {
    const element = await createInput();

    element.value = 'entryType';
    await element.updateComplete;

    expect(native(element).value).toBe('entryType');
  });

  /**
   * `maxlength` caps the value and is also the width hint: a four-character
   * field should not stretch across the page.
   */
  it('applies maxlength to the native input', async () => {
    const element = await createInput({maxlength: '12'});
    await element.updateComplete;

    expect(native(element).maxLength).toBe(12);
    expect(native(element).size).toBe(12);
  });

  it('follows maxlength changes on the native input, dropping it once unset', async () => {
    const element = await createInput({maxlength: '12'});
    element.maxlength = 4;
    await element.updateComplete;

    expect(native(element).maxLength).toBe(4);

    element.maxlength = undefined;
    await element.updateComplete;

    expect(native(element).hasAttribute('maxlength')).toBe(false);
  });

  it('keeps an explicit input size over the maxlength', async () => {
    const element = await createInput({maxlength: '3'});
    element.inputSize = 8;
    await element.updateComplete;

    expect(native(element).size).toBe(8);
  });

  it('reflects the width override so the stylesheet can act on it', async () => {
    const element = await createInput({maxlength: '4', width: 'full'});

    expect(element.getAttribute('width')).toBe('full');
    expect(native(element).hasAttribute('size')).toBe(false);
  });

  /** These are presentation flags the stylesheet keys off, so they reflect. */
  it('reflects its presentation flags', async () => {
    const element = await createInput({
      monospace: '',
      center: '',
      small: '',
    });

    expect(element.monospace).toBe(true);
    expect(element.center).toBe(true);
    expect(element.hasAttribute('monospace')).toBe(true);
    expect(element.hasAttribute('center')).toBe(true);
  });

  it('passes a type through to the native input', async () => {
    const element = await createInput({type: 'email'});
    await element.updateComplete;

    expect(native(element).type).toBe('email');
  });

  it('disables the native input', async () => {
    const element = await createInput({disabled: ''});
    await element.updateComplete;

    expect(native(element).disabled).toBe(true);
  });

  /**
   * `inputSize`, `min`, `max`, and `step` are properties rather than
   * attributes, and are synced onto the native input after render.
   */
  it('syncs the native-only properties onto the input', async () => {
    const element = await createInput({type: 'number'});

    element.min = 1;
    element.max = 10;
    element.step = 2;
    await element.updateComplete;

    expect(native(element).getAttribute('min')).toBe('1');
    expect(native(element).getAttribute('max')).toBe('10');
    expect(native(element).getAttribute('step')).toBe('2');
  });
});

describe('craft-input references', () => {
  it('drops aria-describedby once nothing describes the input', async () => {
    const element = await createInput(
      {},
      '<label slot="label">Handle</label><div slot="help-text">Lowercase</div>'
    );
    const help = element.querySelector<HTMLElement>('[slot="help-text"]')!;
    expect(native(element).getAttribute('aria-describedby')).toBe(help.id);

    element.removeFromAriaDescribedBy(help);
    expect(native(element).hasAttribute('aria-describedby')).toBe(false);
  });

  it('announces feedback politely only while the field has focus', async () => {
    const element = await createInput(
      {},
      '<label slot="label">Handle</label><div slot="feedback">Required</div>'
    );
    const feedback = element.querySelector('[slot="feedback"]')!;

    native(element).dispatchEvent(new FocusEvent('focusin', {bubbles: true}));
    expect(feedback.getAttribute('aria-live')).toBe('polite');

    native(element).dispatchEvent(new FocusEvent('focusout', {bubbles: true}));
    expect(feedback.getAttribute('aria-live')).toBe('assertive');
  });
});

describe('craft-input invalid', () => {
  it('marks the native input invalid', async () => {
    const element = await createInput();
    const input = native(element);

    element.invalid = true;
    await element.updateComplete;
    expect(input.getAttribute('aria-invalid')).toBe('true');
    expect(element.hasAttribute('invalid')).toBe(true);

    element.invalid = false;
    await element.updateComplete;
    expect(input.hasAttribute('aria-invalid')).toBe(false);
  });
});

describe('craft-input server-rendered control', () => {
  async function createServerRendered(
    hostAttributes: string,
    inputAttributes: string
  ): Promise<{element: CraftInput; input: HTMLInputElement}> {
    // Parsed before it's connected, as a server-rendered page is by the time
    // the component's script runs.
    const container = document.createElement('div');
    container.innerHTML = `
      <craft-input ${hostAttributes}>
        <input slot="input" ${inputAttributes}>
      </craft-input>`;
    document.body.append(...container.childNodes);
    const element = document.querySelector('craft-input') as CraftInput;
    await element.updateComplete;

    return {element, input: element.querySelector('input')!};
  }

  it("keeps the control's own attributes the host doesn't set", async () => {
    const {input} = await createServerRendered(
      '',
      'type="password" name="secret" placeholder="Hidden" readonly disabled required'
    );

    expect(input.type).toBe('password');
    expect(input.name).toBe('secret');
    expect(input.placeholder).toBe('Hidden');
    expect(input.readOnly).toBe(true);
    expect(input.disabled).toBe(true);
    expect(input.required).toBe(true);
  });

  it('applies what the host does set', async () => {
    const {element, input} = await createServerRendered(
      'name="title" placeholder="Title"',
      'name="old"'
    );

    expect(input.name).toBe('title');
    expect(input.placeholder).toBe('Title');

    element.disabled = true;
    await element.updateComplete;
    expect(input.disabled).toBe(true);
  });
});
