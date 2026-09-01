import type CraftInputPassword from '../components/input-password/input-password.js';
import {createSlottedInput} from './internal/slotted-input.js';

export interface InputPasswordConfig {
  id?: string;
  name?: string;
  value?: string;
  placeholder?: string;
  disabled?: boolean;
  readonly?: boolean;
  autofocus?: boolean;
}

/**
 * Creates a `<craft-input-password>` — the client-side twin of the PHP
 * `InputPassword` component (a text input with a built-in reveal toggle). The
 * native input lives in the light DOM as the component's form control; the
 * Lion-pushed control props are also mirrored on the host.
 */
export function createInputPassword(
  config: InputPasswordConfig = {}
): CraftInputPassword {
  const input = createSlottedInput({
    type: 'password',
    id: config.id,
    name: config.name,
    value: config.value,
    placeholder: config.placeholder,
    disabled: config.disabled,
    readonly: config.readonly,
    autofocus: config.autofocus,
    autocomplete: false,
    class: 'password',
  });

  const el = document.createElement('craft-input-password');
  if (config.name) {
    el.setAttribute('name', config.name);
  }
  if (config.placeholder) {
    el.setAttribute('placeholder', config.placeholder);
  }
  if (config.disabled) {
    el.setAttribute('disabled', '');
  }
  if (config.readonly) {
    el.setAttribute('readonly', '');
  }

  el.append(input);

  return el;
}
