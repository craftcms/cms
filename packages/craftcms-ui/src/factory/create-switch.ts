import type CraftSwitch from '../components/switch/switch.js';

export interface SwitchConfig {
  id?: string;
  name?: string;
  value?: string;
  indeterminateValue?: string;
  on?: boolean;
  indeterminate?: boolean;
  disabled?: boolean;
  small?: boolean;
  label?: string;
  labelId?: string;
  onLabel?: string;
  offLabel?: string;
  toggle?: string;
  reverseToggle?: string;
  onChange?: (on: boolean) => void;
}

/**
 * Creates a `<craft-switch>` — the client-side twin of the PHP
 * `CraftCms\Cms\Cp\Components\Lightswitch`. The switch button and hidden input
 * live in the light DOM so input namespacing and legacy Craft.FieldToggle hooks
 * keep working.
 */
export function createSwitch(config: SwitchConfig = {}): CraftSwitch {
  const value = config.value || '1';
  const indeterminateValue = config.indeterminateValue || '-';
  const on = !!config.on;
  const indeterminate = !on && !!config.indeterminate;
  const size = config.small ? 'small' : 'medium';

  // Host <craft-switch> (mirrors Lightswitch::hostAttributes()).
  const el = document.createElement('craft-switch');
  if (on) {
    el.setAttribute('checked', '');
  }
  if (indeterminate) {
    el.setAttribute('indeterminate', '');
  }
  if (config.disabled) {
    el.setAttribute('disabled', '');
  }
  if (config.small) {
    el.setAttribute('size', 'small');
  }
  if (value !== '1') {
    el.setAttribute('value', value);
  }
  if (indeterminateValue !== '-') {
    el.setAttribute('indeterminate-value', indeterminateValue);
  }
  if (config.label) {
    el.setAttribute('label', config.label);
  }
  if (config.onLabel && config.onLabel !== config.label) {
    el.setAttribute('on-label', config.onLabel);
  }
  if (config.offLabel) {
    el.setAttribute('off-label', config.offLabel);
  }

  // Switch button in the light DOM (mirrors Lightswitch::switchButtonHtml()).
  const button = document.createElement('craft-switch-button');
  button.slot = 'input';
  if (config.id) {
    button.id = config.id;
  }
  button.setAttribute('role', 'switch');
  button.setAttribute('size', size);
  button.setAttribute('data-tag-name', 'craft-switch-button');
  button.setAttribute(
    'aria-checked',
    on ? 'true' : indeterminate ? 'mixed' : 'false'
  );
  if (on) {
    button.setAttribute('checked', '');
  }
  if (indeterminate) {
    button.setAttribute('indeterminate', '');
  }
  if (config.disabled) {
    button.setAttribute('disabled', '');
  }
  if (config.labelId) {
    button.setAttribute('aria-labelledby', config.labelId);
  }
  if (config.toggle) {
    button.setAttribute('data-target', config.toggle);
  }
  if (config.reverseToggle) {
    button.setAttribute('data-reverse-target', config.reverseToggle);
  }
  if (config.toggle || config.reverseToggle) {
    button.classList.add('fieldtoggle');
  }
  el.append(button);

  // Hidden input posting the state (mirrors Lightswitch::hiddenInputHtml()).
  if (config.name) {
    const hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.slot = 'hidden-input';
    hidden.name = config.name;
    hidden.setAttribute(
      'value',
      on ? value : indeterminate ? indeterminateValue : ''
    );
    hidden.disabled = !!config.disabled;
    el.append(hidden);
  }

  // The element dispatches a native change event that bubbles to the host.
  if (config.onChange) {
    el.addEventListener('change', () => config.onChange!(el.on));
  }

  return el;
}
