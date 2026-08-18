import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftField from './field.js';
import './field.js';
import '../input/input.js';

async function createField(
  attrs: Record<string, string> = {},
  innerHTML = '<input slot="input" type="text">'
): Promise<CraftField> {
  const element = document.createElement('craft-field') as CraftField;
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

function input(element: CraftField): HTMLElement | null {
  return element.querySelector('[slot="input"]');
}

function labelNode(element: CraftField): HTMLElement | null {
  return element.querySelector(':scope > [slot="label"]');
}

function describedBy(element: CraftField): string[] {
  return (input(element)?.getAttribute('aria-describedby') ?? '')
    .split(/\s+/)
    .filter(Boolean);
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-field label association', () => {
  it('associates the generated label with the slotted input', async () => {
    const element = await createField({label: 'My field'});

    const label = labelNode(element);
    const control = input(element);
    expect(label).not.toBeNull();
    expect(label!.textContent).toContain('My field');
    expect(control!.id).toBeTruthy();
    expect(label!.getAttribute('for')).toBe(control!.id);
  });

  it('links the label into the input aria-labelledby', async () => {
    const element = await createField({label: 'My field'});

    const labelledBy = input(element)?.getAttribute('aria-labelledby') ?? '';
    expect(labelNode(element)!.id).toBeTruthy();
    expect(labelledBy.split(/\s+/)).toContain(labelNode(element)!.id);
  });

  it('adopts a slotted label element', async () => {
    const element = await createField(
      {},
      '<label slot="label">Custom label</label><input slot="input" type="text">'
    );

    expect(labelNode(element)!.getAttribute('for')).toBe(input(element)!.id);
  });

  it('labels a nested form control instead of its wrapper', async () => {
    const element = await createField(
      {label: 'My field'},
      '<craft-input slot="input"></craft-input>'
    );
    const nestedControl = element.querySelector('craft-input')!;
    await nestedControl.updateComplete;

    const nativeInput = nestedControl.querySelector('input')!;
    expect(nativeInput.getAttribute('aria-labelledby')?.split(/\s+/)).toContain(
      labelNode(element)!.id
    );
    expect(input(element)!.getAttribute('aria-labelledby') ?? '').toBe('');
    expect(labelNode(element)!.hasAttribute('for')).toBe(false);
  });

  it('uses group semantics for a composite control wrapper', async () => {
    const element = await createField(
      {label: 'My group'},
      '<div slot="input"><button type="button">Add</button></div>'
    );

    expect(element.getAttribute('role')).toBe('group');
    expect(element.getAttribute('aria-labelledby')).toBe(
      labelNode(element)!.id
    );
    expect(labelNode(element)!.hasAttribute('for')).toBe(false);
  });
});

describe('craft-field required indicator', () => {
  it('renders the required spans inside the label', async () => {
    const element = await createField({label: 'My field', required: ''});

    const label = labelNode(element)!;
    const srOnly = label.querySelector('span.visually-hidden');
    const indicator = label.querySelector('span.required');
    expect(srOnly?.textContent).toBe('Required');
    expect(indicator).not.toBeNull();
    expect(indicator!.getAttribute('aria-hidden')).toBe('true');
  });

  it('removes the indicator when required is unset', async () => {
    const element = await createField({label: 'My field', required: ''});

    element.required = false;
    await element.updateComplete;

    expect(labelNode(element)!.querySelector('span.required')).toBeNull();
  });

  it('does not render an indicator without a label', async () => {
    const element = await createField({required: ''});

    expect(element.querySelector('span.required')).toBeNull();
  });
});

describe('craft-field translatable indicator', () => {
  it('renders the translation icon button inside the label', async () => {
    const element = await createField({
      label: 'My field',
      translatable: '',
      'translation-description': 'Translated per site.',
    });

    const tooltip = labelNode(element)!.querySelector('craft-tooltip');
    expect(tooltip).not.toBeNull();
    expect(tooltip!.getAttribute('text')).toBe('Translated per site.');

    const button = tooltip!.querySelector('button.t9n-indicator');
    expect(button).not.toBeNull();
    expect(button!.getAttribute('data-icon')).toBe('language');
    expect(button!.getAttribute('aria-label')).toBe('Translated per site.');
  });
});

describe('craft-field fieldset mode', () => {
  it('exposes group semantics on the host', async () => {
    const element = await createField({label: 'My group', fieldset: ''});

    expect(element.getAttribute('role')).toBe('group');
    expect(element.getAttribute('aria-labelledby')).toBe(
      labelNode(element)!.id
    );
    expect(labelNode(element)!.hasAttribute('for')).toBe(false);
  });

  it('restores the label association when fieldset is unset', async () => {
    const element = await createField({label: 'My group', fieldset: ''});

    element.fieldset = false;
    await element.updateComplete;

    expect(element.hasAttribute('role')).toBe(false);
    expect(element.hasAttribute('aria-labelledby')).toBe(false);
    expect(labelNode(element)!.getAttribute('for')).toBe(input(element)!.id);
  });
});

describe('craft-field aria-describedby wiring', () => {
  it('links help text, tip, warning and feedback to the input', async () => {
    const element = await createField(
      {label: 'My field', 'help-text': 'Some instructions'},
      '<input slot="input" type="text">' +
        '<span slot="tip">A helpful tip</span>' +
        '<span slot="warning">A dire warning</span>' +
        '<ul slot="feedback"><li>Bad value</li></ul>'
    );

    const ids = describedBy(element);
    const helpText = element.querySelector<HTMLElement>('[slot="help-text"]');
    const tip = element.querySelector<HTMLElement>('[slot="tip"]');
    const warning = element.querySelector<HTMLElement>('[slot="warning"]');
    const feedback = element.querySelector<HTMLElement>('[slot="feedback"]');

    for (const node of [helpText, tip, warning, feedback]) {
      expect(node?.id).toBeTruthy();
      expect(ids).toContain(node!.id);
    }
  });

  it('renders tip and warning content inside callouts', async () => {
    const element = await createField(
      {label: 'My field'},
      '<input slot="input" type="text">' +
        '<span slot="tip">A helpful tip</span>' +
        '<span slot="warning">A dire warning</span>'
    );

    const callouts = Array.from(
      element.shadowRoot!.querySelectorAll('.field-notice')
    );
    expect(callouts).toHaveLength(2);

    const [tip, warning] = callouts;
    expect(tip!.getAttribute('variant')).toBe('info');
    expect(tip!.hasAttribute('icon')).toBe(false);
    expect(tip!.querySelector('craft-visually-hidden')?.textContent).toContain(
      'Tip:'
    );
    expect(tip!.querySelector('slot[name="tip"]')).not.toBeNull();

    expect(warning!.getAttribute('variant')).toBe('warning');
    expect(warning!.hasAttribute('icon')).toBe(false);
    expect(
      warning!.querySelector('craft-visually-hidden')?.textContent
    ).toContain('Warning:');
    expect(warning!.querySelector('slot[name="warning"]')).not.toBeNull();
  });

  it('renders no callouts without tip or warning content', async () => {
    const element = await createField({label: 'My field'});
    expect(element.shadowRoot!.querySelectorAll('.field-notice')).toHaveLength(
      0
    );
  });
});

describe('craft-field status badge', () => {
  it('renders the status badge with a visually hidden label', async () => {
    const element = await createField({
      label: 'My field',
      status: 'modified',
      'status-label': 'This field has been modified.',
    });

    const badge = element.shadowRoot!.querySelector('.status-badge');
    expect(badge).not.toBeNull();
    expect(badge!.classList.contains('modified')).toBe(true);
    expect(badge!.getAttribute('title')).toBe('This field has been modified.');
    expect(badge!.getAttribute('aria-hidden')).toBe('true');
    expect(badge!.querySelector('.cp-visually-hidden')?.textContent).toBe(
      'This field has been modified.'
    );
  });

  it('renders no badge without a status', async () => {
    const element = await createField({label: 'My field'});
    expect(element.shadowRoot!.querySelector('.status-badge')).toBeNull();
  });
});

describe('craft-field error state', () => {
  it('reflects has-errors when feedback content is slotted', async () => {
    const element = await createField(
      {label: 'My field'},
      '<input slot="input" type="text">' +
        '<ul slot="feedback"><li>Bad value</li></ul>'
    );

    expect(element.hasAttribute('has-errors')).toBe(true);
    const inputGroup = element.shadowRoot!.querySelector('.input-group');
    expect(inputGroup!.classList.contains('errors')).toBe(true);
  });

  it('supports setting has-errors manually', async () => {
    const element = await createField({label: 'My field', 'has-errors': ''});

    expect(element.hasErrors).toBe(true);
    expect(
      element
        .shadowRoot!.querySelector('.input-group')!
        .classList.contains('errors')
    ).toBe(true);
  });

  it('has no error hooks by default', async () => {
    const element = await createField({label: 'My field'});

    expect(element.hasAttribute('has-errors')).toBe(false);
    expect(
      element
        .shadowRoot!.querySelector('.input-group')!
        .classList.contains('errors')
    ).toBe(false);
  });
});

describe('craft-field orientation', () => {
  it('defaults to ltr', async () => {
    const element = await createField({label: 'My field'});
    const inputGroup = element.shadowRoot!.querySelector('.input-group')!;
    expect(inputGroup.classList.contains('ltr')).toBe(true);
    expect(inputGroup.classList.contains('rtl')).toBe(false);
  });

  it('applies the orientation attribute to the input container', async () => {
    const element = await createField({label: 'My field', orientation: 'rtl'});
    expect(
      element
        .shadowRoot!.querySelector('.input-group')!
        .classList.contains('rtl')
    ).toBe(true);
  });

  it('inherits the direction from the closest dir attribute', async () => {
    const wrapper = document.createElement('div');
    wrapper.setAttribute('dir', 'rtl');
    document.body.append(wrapper);

    const element = document.createElement('craft-field') as CraftField;
    element.setAttribute('label', 'My field');
    element.innerHTML = '<input slot="input" type="text">';
    wrapper.append(element);
    await element.updateComplete;

    expect(
      element
        .shadowRoot!.querySelector('.input-group')!
        .classList.contains('rtl')
    ).toBe(true);
  });
});

describe('craft-field read-only badge', () => {
  it('renders the badge in the heading when read-only', async () => {
    const element = await createField({label: 'My field', readonly: ''});

    const badge = element.shadowRoot!.querySelector(
      '.heading .read-only-badge'
    );
    expect(badge?.textContent).toBe('Read Only');
  });

  it('renders no badge otherwise', async () => {
    const element = await createField({label: 'My field'});
    expect(element.shadowRoot!.querySelector('.read-only-badge')).toBeNull();
  });
});

describe('craft-field label extras', () => {
  it('renders a flex-grow spacer before slotted label extras', async () => {
    const element = await createField(
      {label: 'My field'},
      '<input slot="input" type="text"><code slot="label-extra">handle</code>'
    );

    const heading = element.shadowRoot!.querySelector('.heading')!;
    const spacer = heading.querySelector('.flex-grow');
    const slot = heading.querySelector('slot[name="label-extra"]');
    expect(spacer).not.toBeNull();
    expect(slot).not.toBeNull();
    expect(
      spacer!.compareDocumentPosition(slot!) & Node.DOCUMENT_POSITION_FOLLOWING
    ).toBeTruthy();
  });

  it('renders no spacer without label extras', async () => {
    const element = await createField({label: 'My field'});
    expect(element.shadowRoot!.querySelector('.flex-grow')).toBeNull();
  });
});

describe('craft-field actions', () => {
  it('renders a flex-grow spacer before slotted actions', async () => {
    const element = await createField(
      {label: 'My field'},
      '<input slot="input" type="text"><button slot="actions">Hide</button>'
    );

    const heading = element.shadowRoot!.querySelector('.heading')!;
    const spacer = heading.querySelector('.flex-grow');
    const slot = heading.querySelector('slot[name="actions"]');
    expect(spacer).not.toBeNull();
    expect(slot).not.toBeNull();
    expect(
      spacer!.compareDocumentPosition(slot!) & Node.DOCUMENT_POSITION_FOLLOWING
    ).toBeTruthy();
  });

  it('groups slotted actions', async () => {
    const element = await createField(
      {label: 'My field'},
      '<input slot="input" type="text"><button slot="actions">Hide</button>'
    );

    const group = element.shadowRoot!.querySelector('.field-actions')!;
    expect(group).not.toBeNull();
    expect(group.getAttribute('role')).toBe('group');
    expect(group.querySelector('slot[name="actions"]')).not.toBeNull();
  });

  it('renders actions after label extras', async () => {
    const element = await createField(
      {label: 'My field'},
      '<input slot="input" type="text"><code slot="label-extra">handle</code><button slot="actions">Hide</button>'
    );

    const heading = element.shadowRoot!.querySelector('.heading')!;
    const labelExtra = heading.querySelector('slot[name="label-extra"]')!;
    const actions = heading.querySelector('slot[name="actions"]')!;
    expect(
      labelExtra.compareDocumentPosition(actions) &
        Node.DOCUMENT_POSITION_FOLLOWING
    ).toBeTruthy();
  });

  it('renders no action group without actions', async () => {
    const element = await createField({label: 'My field'});
    expect(element.shadowRoot!.querySelector('.field-actions')).toBeNull();
  });
});

describe('craft-field disabled state', () => {
  it('adds the disabled class to the input container only', async () => {
    const element = await createField({label: 'My field', disabled: ''});

    expect(
      element
        .shadowRoot!.querySelector('.input-group')!
        .classList.contains('disabled')
    ).toBe(true);
    // The shell must not touch the slotted input's state.
    expect(input(element)!.hasAttribute('disabled')).toBe(false);
    expect(input(element)!.hasAttribute('aria-disabled')).toBe(false);
  });
});

describe('craft-field instructions position', () => {
  it('renders instructions before the input by default', async () => {
    const element = await createField({
      label: 'My field',
      'help-text': 'Some instructions',
    });

    expect(
      element.shadowRoot!.querySelector(
        '.form-field__group-one .form-field__help-text'
      )
    ).not.toBeNull();
  });

  it('renders instructions after the input when requested', async () => {
    const element = await createField({
      label: 'My field',
      'help-text': 'Some instructions',
      'instructions-position': 'after',
    });

    expect(
      element.shadowRoot!.querySelector(
        '.form-field__group-one .form-field__help-text'
      )
    ).toBeNull();
    expect(
      element.shadowRoot!.querySelector(
        '.form-field__group-two .form-field__help-text'
      )
    ).not.toBeNull();
  });
});

describe('craft-field heading prefix/suffix', () => {
  it('renders heading-prefix and heading-suffix slots around the label', async () => {
    const element = document.createElement('craft-field') as CraftField;
    element.setAttribute('label', 'My field');
    element.innerHTML = `
      <input slot="input">
      <span slot="heading-prefix">pre</span>
      <span slot="heading-suffix">post</span>
    `;
    document.body.append(element);
    await element.updateComplete;
    await element.updateComplete;

    const heading = element.shadowRoot!.querySelector('.heading')!;
    const slotNames = Array.from(heading.querySelectorAll('slot')).map((s) =>
      s.getAttribute('name')
    );
    expect(slotNames[0]).toBe('heading-prefix');
    expect(slotNames[slotNames.length - 1]).toBe('heading-suffix');
  });
});

describe('craft-field control width mirroring', () => {
  it('reflects the slotted control maxlength as has-maxlength', async () => {
    const element = await createField(
      {},
      '<input slot="input" type="text" maxlength="255">'
    );

    expect(element.hasAttribute('has-maxlength')).toBe(true);
  });

  it('mirrors the slotted control width override onto the host', async () => {
    const element = await createField(
      {},
      '<input slot="input" type="text" maxlength="255" width="full">'
    );

    expect(element.getAttribute('width')).toBe('full');
  });

  it('keeps an explicit host width over the control width', async () => {
    const element = await createField(
      {width: 'auto'},
      '<input slot="input" type="text" width="full">'
    );

    expect(element.getAttribute('width')).toBe('auto');
  });

  it('does not set a width without a control override', async () => {
    const element = await createField(
      {},
      '<input slot="input" type="text" maxlength="255">'
    );

    expect(element.hasAttribute('width')).toBe(false);
  });
});
