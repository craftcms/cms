import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftSwitch from './switch.js';
import './switch.js';

async function createSwitch(
  attrs: Record<string, string> = {}
): Promise<CraftSwitch> {
  const element = document.createElement('craft-switch') as CraftSwitch;
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  document.body.append(element);
  await element.updateComplete;
  // Wait one more cycle for aria attribute reflection onto the switch button.
  await element.updateComplete;
  return element;
}

function switchButton(element: CraftSwitch): HTMLElement | null {
  return element.querySelector('[slot="input"]');
}

function stateDescription(element: CraftSwitch): HTMLElement | null {
  return element.querySelector('[slot="state-description"]');
}

function stateLabels(element: CraftSwitch): HTMLElement[] {
  return Array.from(element.shadowRoot?.querySelectorAll('.state-label') ?? []);
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-switch without on/off labels', () => {
  it('renders no state labels', async () => {
    const element = await createSwitch({label: 'Enable feature'});
    expect(stateLabels(element)).toHaveLength(0);
  });

  it('keeps the state description empty and unlinked', async () => {
    const element = await createSwitch({label: 'Enable feature'});
    const description = stateDescription(element);
    expect(description?.textContent).toBe('');
    const describedBy =
      switchButton(element)?.getAttribute('aria-describedby') ?? '';
    expect(describedBy).not.toContain('state-description');
  });
});

describe('craft-switch with on/off labels', () => {
  it('renders the off label before the control and the on label after it', async () => {
    const element = await createSwitch({
      label: 'Enable feature',
      'on-label': 'Enabled',
      'off-label': 'Disabled',
    });

    const before = element.shadowRoot?.querySelector('.input-group__before');
    const after = element.shadowRoot?.querySelector('.input-group__after');

    const offLabel = before?.querySelector('.state-label');
    expect(offLabel?.textContent?.trim()).toBe('Disabled');
    expect(offLabel?.getAttribute('data-state')).toBe('off');

    const onLabel = after?.querySelector('.state-label');
    expect(onLabel?.textContent?.trim()).toBe('Enabled');
    expect(onLabel?.getAttribute('data-state')).toBe('on');
  });

  it('hides the visible state labels from assistive technology', async () => {
    const element = await createSwitch({
      'on-label': 'Enabled',
      'off-label': 'Disabled',
    });

    for (const label of stateLabels(element)) {
      expect(label.getAttribute('aria-hidden')).toBe('true');
    }
  });

  it('describes both states in a visually hidden light DOM node', async () => {
    const element = await createSwitch({
      'on-label': 'Enabled',
      'off-label': 'Disabled',
    });

    expect(stateDescription(element)?.textContent).toBe(
      'Check for Enabled. Uncheck for Disabled.'
    );
  });

  it('links the state description to the switch via aria-describedby', async () => {
    const element = await createSwitch({
      'on-label': 'Enabled',
      'off-label': 'Disabled',
    });

    const description = stateDescription(element);
    expect(description?.id).toBeTruthy();
    const describedBy =
      switchButton(element)?.getAttribute('aria-describedby') ?? '';
    expect(describedBy.split(/\s+/)).toContain(description!.id);
  });

  it('only describes the provided state when a single label is set', async () => {
    const element = await createSwitch({'on-label': 'Enabled'});

    expect(stateDescription(element)?.textContent).toBe('Check for Enabled.');
    const labels = stateLabels(element);
    expect(labels).toHaveLength(1);
    expect(labels[0]?.getAttribute('data-state')).toBe('on');
  });

  it('updates the labels and description when properties change', async () => {
    const element = await createSwitch({label: 'Enable feature'});

    element.onLabel = 'Live';
    element.offLabel = 'Offline';
    await element.updateComplete;
    await element.updateComplete;

    expect(stateLabels(element)).toHaveLength(2);
    expect(stateDescription(element)?.textContent).toBe(
      'Check for Live. Uncheck for Offline.'
    );
    const describedBy =
      switchButton(element)?.getAttribute('aria-describedby') ?? '';
    expect(describedBy.split(/\s+/)).toContain(stateDescription(element)!.id);
  });

  it('unlinks the description when the labels are removed', async () => {
    const element = await createSwitch({
      'on-label': 'Enabled',
      'off-label': 'Disabled',
    });

    element.onLabel = '';
    element.offLabel = '';
    await element.updateComplete;
    await element.updateComplete;

    expect(stateLabels(element)).toHaveLength(0);
    expect(stateDescription(element)?.textContent).toBe('');
    const describedBy =
      switchButton(element)?.getAttribute('aria-describedby') ?? '';
    expect(describedBy).not.toContain(stateDescription(element)!.id);
  });

  it('still toggles checked state with labels present', async () => {
    const element = await createSwitch({
      'on-label': 'Enabled',
      'off-label': 'Disabled',
    });

    expect(element.checked).toBe(false);
    switchButton(element)?.click();
    await element.updateComplete;
    expect(element.checked).toBe(true);
  });

  it('sets the state when a state label is clicked', async () => {
    const element = await createSwitch({
      'on-label': 'Enabled',
      'off-label': 'Disabled',
    });

    const onLabel = element.shadowRoot?.querySelector<HTMLElement>(
      '.state-label[data-state="on"]'
    );
    const offLabel = element.shadowRoot?.querySelector<HTMLElement>(
      '.state-label[data-state="off"]'
    );

    onLabel?.click();
    await element.updateComplete;
    expect(element.checked).toBe(true);

    // Clicking the active state again is a no-op
    onLabel?.click();
    await element.updateComplete;
    expect(element.checked).toBe(true);

    offLabel?.click();
    await element.updateComplete;
    expect(element.checked).toBe(false);
  });

  it('ignores state label clicks when disabled', async () => {
    const element = await createSwitch({
      'on-label': 'Enabled',
      'off-label': 'Disabled',
      disabled: '',
    });

    element.shadowRoot
      ?.querySelector<HTMLElement>('.state-label[data-state="on"]')
      ?.click();
    await element.updateComplete;
    expect(element.checked).toBe(false);
  });
});

describe('craft-switch indeterminate state', () => {
  it('exposes aria-checked="mixed" on the switch button', async () => {
    const element = await createSwitch({indeterminate: ''});

    expect(element.indeterminate).toBe(true);
    expect(switchButton(element)?.getAttribute('aria-checked')).toBe('mixed');
    expect(switchButton(element)?.hasAttribute('indeterminate')).toBe(true);
  });

  it('clears the indeterminate state when turned on', async () => {
    const element = await createSwitch({indeterminate: ''});

    switchButton(element)?.click();
    await element.updateComplete;
    await element.updateComplete;

    expect(element.checked).toBe(true);
    expect(element.indeterminate).toBe(false);
    expect(switchButton(element)?.getAttribute('aria-checked')).toBe('true');
    expect(switchButton(element)?.hasAttribute('indeterminate')).toBe(false);
  });

  it('never combines indeterminate with checked', async () => {
    const element = await createSwitch({checked: '', indeterminate: ''});
    expect(switchButton(element)?.getAttribute('aria-checked')).toBe('true');
    expect(element.indeterminate).toBe(false);
  });
});

describe('craft-switch hidden input', () => {
  function hiddenInput(element: CraftSwitch): HTMLInputElement | null {
    return element.querySelector('input[slot="hidden-input"]');
  }

  it('creates a hidden input when a name is set', async () => {
    const element = await createSwitch({name: 'foo'});
    const input = hiddenInput(element);
    expect(input).not.toBeNull();
    expect(input!.type).toBe('hidden');
    expect(input!.name).toBe('foo');
    expect(input!.value).toBe('');
  });

  it('does not create a hidden input without a name', async () => {
    const element = await createSwitch();
    expect(hiddenInput(element)).toBeNull();
  });

  it('posts the value when on, indeterminate value when indeterminate', async () => {
    const element = await createSwitch({
      name: 'foo',
      value: 'yes',
      'indeterminate-value': 'maybe',
      indeterminate: '',
    });
    expect(hiddenInput(element)!.value).toBe('maybe');

    switchButton(element)?.click();
    await element.updateComplete;
    await element.updateComplete;
    expect(hiddenInput(element)!.value).toBe('yes');

    switchButton(element)?.click();
    await element.updateComplete;
    await element.updateComplete;
    expect(hiddenInput(element)!.value).toBe('');
  });

  it('disables the hidden input when the switch is disabled', async () => {
    const element = await createSwitch({name: 'foo', disabled: ''});
    expect(hiddenInput(element)!.disabled).toBe(true);
  });

  it('adopts a server-rendered hidden input instead of creating one', async () => {
    const element = document.createElement('craft-switch') as CraftSwitch;
    element.setAttribute('checked', '');
    element.innerHTML =
      '<input type="hidden" name="foo" value="1" slot="hidden-input">';
    document.body.append(element);
    await element.updateComplete;
    await element.updateComplete;

    const inputs = element.querySelectorAll('input[slot="hidden-input"]');
    expect(inputs).toHaveLength(1);
    expect((inputs[0] as HTMLInputElement).value).toBe('1');

    switchButton(element)?.click();
    await element.updateComplete;
    await element.updateComplete;
    expect((inputs[0] as HTMLInputElement).value).toBe('');
  });
});

describe('craft-switch legacy JS interop', () => {
  it('dispatches a bubbling native change event from the switch button', async () => {
    const element = await createSwitch();
    let changeTarget: EventTarget | null = null;
    document.body.addEventListener(
      'change',
      (event) => {
        changeTarget = event.target;
      },
      {once: true}
    );

    switchButton(element)?.click();
    await element.updateComplete;

    expect(changeTarget).toBe(switchButton(element));
  });

  it('preserves fieldtoggle hooks on a server-rendered switch button', async () => {
    const element = document.createElement('craft-switch') as CraftSwitch;
    element.innerHTML =
      '<craft-switch-button slot="input" id="my-switch" role="switch"' +
      ' class="fieldtoggle" data-target="my-target" aria-checked="false">' +
      '</craft-switch-button>';
    document.body.append(element);
    await element.updateComplete;
    await element.updateComplete;

    const btn = switchButton(element)!;
    expect(btn.id).toBe('my-switch');
    expect(btn.classList.contains('fieldtoggle')).toBe(true);
    expect(btn.getAttribute('data-target')).toBe('my-target');
    expect(btn.getAttribute('role')).toBe('switch');

    // The adopted button still drives the switch
    btn.click();
    await element.updateComplete;
    expect(element.checked).toBe(true);
  });

  it('reveals data-target and hides data-reverse-target containers', async () => {
    const target = document.createElement('div');
    target.id = 'reveal-target';
    target.className = 'hidden';
    const reverseTarget = document.createElement('div');
    reverseTarget.id = 'reverse-target';
    document.body.append(target, reverseTarget);

    const element = document.createElement('craft-switch') as CraftSwitch;
    element.innerHTML =
      '<craft-switch-button slot="input" class="fieldtoggle"' +
      ' data-target="reveal-target" data-reverse-target="reverse-target">' +
      '</craft-switch-button>';
    document.body.append(element);
    await element.updateComplete;

    // Initial state applied (off): target hidden, reverse target shown
    expect(target.classList.contains('hidden')).toBe(true);
    expect(reverseTarget.classList.contains('hidden')).toBe(false);

    switchButton(element)?.click();
    await element.updateComplete;
    await element.updateComplete;

    expect(target.classList.contains('hidden')).toBe(false);
    expect(reverseTarget.classList.contains('hidden')).toBe(true);

    switchButton(element)?.click();
    await element.updateComplete;
    await element.updateComplete;

    expect(target.classList.contains('hidden')).toBe(true);
    expect(reverseTarget.classList.contains('hidden')).toBe(false);
  });
});

describe('craft-switch external aria and labels', () => {
  it('keeps server-rendered aria-labelledby and aria-describedby references', async () => {
    const label = document.createElement('span');
    label.id = 'ext-label';
    label.textContent = 'External label';
    const instructions = document.createElement('div');
    instructions.id = 'ext-instructions';
    document.body.append(label, instructions);

    const element = document.createElement('craft-switch') as CraftSwitch;
    element.innerHTML =
      '<craft-switch-button slot="input" aria-labelledby="ext-label"' +
      ' aria-describedby="ext-instructions"></craft-switch-button>';
    document.body.append(element);
    await element.updateComplete;
    await element.updateComplete;

    const btn = switchButton(element)!;
    expect(btn.getAttribute('aria-labelledby') ?? '').toContain('ext-label');
    expect(btn.getAttribute('aria-describedby') ?? '').toContain(
      'ext-instructions'
    );
  });

  it('toggles when an external label element is clicked', async () => {
    const label = document.createElement('label');
    label.id = 'ext-label';
    label.htmlFor = 'my-switch';
    label.textContent = 'External label';
    document.body.append(label);

    const element = document.createElement('craft-switch') as CraftSwitch;
    element.innerHTML =
      '<craft-switch-button slot="input" id="my-switch"' +
      ' aria-labelledby="ext-label"></craft-switch-button>';
    document.body.append(element);
    await element.updateComplete;

    label.click();
    await element.updateComplete;
    expect(element.checked).toBe(true);

    label.click();
    await element.updateComplete;
    expect(element.checked).toBe(false);
  });
});

describe('craft-switch keyboard support', () => {
  function pressKey(target: HTMLElement, key: string): void {
    target.dispatchEvent(
      new KeyboardEvent('keydown', {key, bubbles: true, cancelable: true})
    );
  }

  it('turns on with ArrowRight and off with ArrowLeft', async () => {
    const element = await createSwitch();
    const btn = switchButton(element)!;

    pressKey(btn, 'ArrowRight');
    await element.updateComplete;
    expect(element.checked).toBe(true);

    pressKey(btn, 'ArrowRight');
    await element.updateComplete;
    expect(element.checked).toBe(true);

    pressKey(btn, 'ArrowLeft');
    await element.updateComplete;
    expect(element.checked).toBe(false);
  });

  it('clears the indeterminate state with ArrowLeft without toggling', async () => {
    const element = await createSwitch({indeterminate: ''});
    const btn = switchButton(element)!;

    pressKey(btn, 'ArrowLeft');
    await element.updateComplete;
    expect(element.checked).toBe(false);
    expect(element.indeterminate).toBe(false);
  });
});

describe('craft-switch toggle reveal', () => {
  async function createToggleSwitch(
    buttonAttrs: Record<string, string>,
    {checked = false} = {}
  ): Promise<CraftSwitch> {
    const element = document.createElement('craft-switch') as CraftSwitch;
    if (checked) {
      element.setAttribute('checked', '');
    }
    const button = document.createElement('craft-switch-button');
    button.setAttribute('slot', 'input');
    button.setAttribute('role', 'switch');
    for (const [name, value] of Object.entries(buttonAttrs)) {
      button.setAttribute(name, value);
    }
    element.append(button);
    document.body.append(element);
    await element.updateComplete;
    await element.updateComplete;
    return element;
  }

  function target(id: string, hidden: boolean): HTMLElement {
    const el = document.createElement('div');
    el.id = id;
    if (hidden) {
      el.classList.add('hidden');
    }
    document.body.append(el);
    return el;
  }

  it('reveals the data-target container when turned on and hides it when turned off', async () => {
    const container = target('reveal-toggle', true);
    const element = await createToggleSwitch({'data-target': '#reveal-toggle'});

    switchButton(element)?.click();
    await element.updateComplete;
    await element.updateComplete;
    expect(container.classList.contains('hidden')).toBe(false);

    switchButton(element)?.click();
    await element.updateComplete;
    await element.updateComplete;
    expect(container.classList.contains('hidden')).toBe(true);
  });

  it('treats bare selectors as element ids like Craft.FieldToggle', async () => {
    const container = target('bare-toggle', true);
    const element = await createToggleSwitch({'data-target': 'bare-toggle'});

    switchButton(element)?.click();
    await element.updateComplete;
    await element.updateComplete;
    expect(container.classList.contains('hidden')).toBe(false);
  });

  it('toggles reverse targets in the opposite direction', async () => {
    const container = target('reverse-toggle', false);
    const element = await createToggleSwitch({
      'data-reverse-target': '#reverse-toggle',
    });

    switchButton(element)?.click();
    await element.updateComplete;
    await element.updateComplete;
    expect(container.classList.contains('hidden')).toBe(true);

    switchButton(element)?.click();
    await element.updateComplete;
    await element.updateComplete;
    expect(container.classList.contains('hidden')).toBe(false);
  });

  it('syncs targets on first render for a server-rendered checked switch', async () => {
    const container = target('initial-toggle', true);
    await createToggleSwitch(
      {'data-target': '#initial-toggle'},
      {checked: true}
    );

    expect(container.classList.contains('hidden')).toBe(false);
  });

  it('dispatches a window resize when revealing, like Craft.FieldToggle', async () => {
    target('resize-toggle', true);
    const element = await createToggleSwitch({'data-target': '#resize-toggle'});

    let resized = false;
    const onResize = () => {
      resized = true;
    };
    window.addEventListener('resize', onResize);

    try {
      switchButton(element)?.click();
      await element.updateComplete;
      await element.updateComplete;
      expect(resized).toBe(true);
    } finally {
      window.removeEventListener('resize', onResize);
    }
  });

  it('defers to a bound Craft.FieldToggle instance', async () => {
    const container = target('legacy-toggle', true);
    const element = await createToggleSwitch({'data-target': '#legacy-toggle'});

    const globalWindow = window as unknown as {jQuery?: unknown};

    try {
      globalWindow.jQuery = () => ({
        data: (key: string) => (key === 'fieldtoggle' ? {} : undefined),
      });

      switchButton(element)?.click();
      await element.updateComplete;
      await element.updateComplete;
      expect(container.classList.contains('hidden')).toBe(true);
    } finally {
      delete globalWindow.jQuery;
    }
  });
});

describe('craft-switch imperative API (legacy Craft.LightSwitch)', () => {
  function countChanges(element: CraftSwitch): () => number {
    let changes = 0;
    element.addEventListener('change', () => {
      changes++;
    });
    return () => changes;
  }

  it('turnOn()/turnOff() set the state, reflected by `on`', async () => {
    const element = await createSwitch({label: 'Enabled'});
    expect(element.on).toBe(false);

    element.turnOn();
    await element.updateComplete;
    expect(element.checked).toBe(true);
    expect(element.on).toBe(true);

    element.turnOff();
    await element.updateComplete;
    expect(element.checked).toBe(false);
    expect(element.on).toBe(false);
  });

  it('fires a single change event on unmuted turnOn()/turnOff()', async () => {
    const element = await createSwitch({label: 'Enabled'});
    const changes = countChanges(element);

    element.turnOn();
    await element.updateComplete;
    expect(changes()).toBe(1);

    element.turnOff();
    await element.updateComplete;
    expect(changes()).toBe(2);
  });

  it('suppresses the change event when muted', async () => {
    const element = await createSwitch({label: 'Enabled'});
    const changes = countChanges(element);

    element.turnOn(true);
    await element.updateComplete;
    await element.updateComplete;
    expect(element.checked).toBe(true);
    expect(changes()).toBe(0);

    // A later unmuted change still fires normally.
    element.turnOff();
    await element.updateComplete;
    expect(changes()).toBe(1);
  });

  it('turnIndeterminate() sets the mixed state', async () => {
    const element = await createSwitch({label: 'Enabled'});
    element.turnOn(true);
    await element.updateComplete;

    element.turnIndeterminate();
    await element.updateComplete;
    expect(element.indeterminate).toBe(true);
    expect(element.checked).toBe(false);
  });

  it('reports the posted value for each state', async () => {
    const element = await createSwitch({
      label: 'Enabled',
      value: 'yes',
      'indeterminate-value': 'maybe',
    });
    expect(element.postedValue).toBe('');

    element.turnOn(true);
    await element.updateComplete;
    expect(element.postedValue).toBe('yes');

    element.turnIndeterminate(true);
    await element.updateComplete;
    expect(element.postedValue).toBe('maybe');
  });

  it('ignores turn* calls when disabled', async () => {
    const element = await createSwitch({label: 'Enabled', disabled: ''});
    const changes = countChanges(element);

    element.turnOn();
    await element.updateComplete;
    expect(element.checked).toBe(false);
    expect(changes()).toBe(0);
  });
});
