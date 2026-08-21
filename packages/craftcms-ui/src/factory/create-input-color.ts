import type CraftInputColor from '../components/input-color/input-color.js';
import {t} from '@src/utilities/translate';
import {createSlottedInput} from './internal/slotted-input.js';

export interface InputColorConfig {
  id?: string;
  name?: string;
  value?: string | null;
  disabled?: boolean;
  autofocus?: boolean;
  presets?: string[];
}

/**
 * Creates a `<craft-input-color>` — the client-side twin of the PHP `InputColor`
 * component (a hex input paired with a native color-picker swatch). The value is
 * stored bare (the component renders its own leading `#`); presets pass through as
 * a JSON attribute.
 */
export function createInputColor(
  config: InputColorConfig = {}
): CraftInputColor {
  const value =
    config.value != null ? String(config.value).replace(/^#/, '') : null;

  const input = createSlottedInput({
    id: config.id ?? `color${Math.floor(Math.random() * 1000000000)}`,
    name: config.name,
    value,
    size: 10,
    disabled: config.disabled,
    autofocus: config.autofocus,
    ariaLabel: t('Color hex value'),
    class: 'color-input',
  });

  const el = document.createElement('craft-input-color');
  if (config.name) {
    el.setAttribute('name', config.name);
  }
  if (config.disabled) {
    el.setAttribute('disabled', '');
  }
  if (config.presets && config.presets.length) {
    el.setAttribute('presets', JSON.stringify(config.presets));
  }

  el.append(input);

  return el;
}
