export interface SlottedInputConfig {
  type?: string;
  id?: string;
  name?: string | null;
  value?: string | number | null;
  size?: number;
  disabled?: boolean;
  readonly?: boolean;
  placeholder?: string;
  autocomplete?: boolean | string;
  autofocus?: boolean;
  ariaLabel?: string;
  class?: string | string[];
}

/**
 * Builds the native `<input slot="input">` that a Lion-based control (craft-input,
 * craft-input-password, craft-input-color) adopts as its form control. The
 * jQuery-free equivalent of the subset of `Craft.ui.createTextInput` these
 * factories relied on.
 */
export function createSlottedInput(
  config: SlottedInputConfig
): HTMLInputElement {
  const input = document.createElement('input');
  input.slot = 'input';
  input.type = config.type ?? 'text';

  const classes = ['text'];
  if (config.size === undefined) {
    classes.push('fullwidth');
  }
  if (Array.isArray(config.class)) {
    classes.push(...config.class);
  } else if (config.class) {
    classes.push(config.class);
  }
  input.className = classes.join(' ');

  if (config.id) {
    input.id = config.id;
  }
  if (config.name) {
    input.name = config.name;
  }
  if (config.value !== undefined && config.value !== null) {
    input.setAttribute('value', String(config.value));
  }
  if (config.size !== undefined) {
    input.setAttribute('size', String(config.size));
  }
  if (config.placeholder) {
    input.placeholder = config.placeholder;
  }
  if (config.disabled) {
    input.disabled = true;
  }
  if (config.readonly) {
    input.readOnly = true;
  }
  if (config.autofocus) {
    input.autofocus = true;
  }
  if (typeof config.autocomplete === 'string') {
    input.setAttribute('autocomplete', config.autocomplete);
  } else if (config.autocomplete === false) {
    input.setAttribute('autocomplete', 'off');
  }
  if (config.ariaLabel) {
    input.setAttribute('aria-label', config.ariaLabel);
  }

  return input;
}
