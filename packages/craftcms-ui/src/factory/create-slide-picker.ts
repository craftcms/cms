import type CraftSlidePicker from '../components/slide-picker/slide-picker.js';
import {t} from '@src/utilities/translate';

export interface SlidePickerConfig {
  id?: string;
  class?: string;
  name?: string;
  min?: number | (() => number);
  max?: number | (() => number);
  step?: number;
  value?: number;
  label?: string;
  valueLabel?: (value: number) => string;
  describedBy?: string;
  readOnly?: boolean;
  disabled?: boolean;
  static?: boolean;
  onChange?: (value: number) => void;
}

/**
 * Creates a `<craft-slide-picker>` (segmented slider). A hidden input carries the
 * value for form posting; `onChange` fires on the element's `value-change` event.
 */
export function createSlidePicker(
  config: SlidePickerConfig = {}
): CraftSlidePicker {
  const min =
    typeof config.min === 'function' ? config.min() : (config.min ?? 0);
  const max =
    typeof config.max === 'function' ? config.max() : (config.max ?? 100);
  const step = typeof config.step !== 'undefined' ? config.step : 10;
  const value = typeof config.value === 'number' ? config.value : min;
  const readOnly = !!(config.readOnly || config.disabled || config.static);

  const el = document.createElement('craft-slide-picker');
  el.min = min;
  el.max = max;
  el.step = step;
  el.value = value || 0;
  el.label = config.label || t('Number of columns');
  el.valueLabel = config.valueLabel || ((v) => `${v}`);
  if (config.describedBy) {
    el.setAttribute('described-by', config.describedBy);
  }
  if (readOnly) {
    el.setAttribute('read-only', '');
  }
  if (config.id) {
    el.id = config.id;
  }
  if (config.class) {
    el.classList.add(...config.class.split(/\s+/).filter(Boolean));
  }

  let hidden: HTMLInputElement | null = null;
  if (config.name) {
    hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = config.name;
    hidden.setAttribute('value', String(el.value));
    hidden.disabled = !!config.disabled;
    el.append(hidden);
  }

  el.addEventListener('value-change', (event) => {
    const next = (event as CustomEvent<{value: number}>).detail?.value;
    if (typeof next !== 'number') {
      return;
    }
    if (hidden) {
      hidden.value = String(next);
    }
    config.onChange?.(next);
  });

  return el;
}
