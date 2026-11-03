import type CraftSlidePicker from '../components/slide-picker/slide-picker.js';

export interface SlidePickerConfig {
  id?: string;
  class?: string;
  name?: string;
  min?: number | (() => number);
  max?: number | (() => number);
  step?: number;
  value?: number;
  label?: string;
  labelledBy?: string;
  describedBy?: string;
  valueLabel?: (value: number) => string;
  readOnly?: boolean;
  disabled?: boolean;
  static?: boolean;
  onChange?: (value: number) => void;
}

/**
 * Creates a `<craft-slide-picker>` (segmented slider). `name` is all it takes
 * to post the value; `onChange` fires on the element's `change` event.
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
  el.modelValue = value || 0;
  if (config.label) {
    el.label = config.label;
  }
  if (config.labelledBy) {
    el.setAttribute('labelled-by', config.labelledBy);
  }
  if (config.describedBy) {
    el.setAttribute('described-by', config.describedBy);
  }
  el.valueLabel = config.valueLabel || ((v) => `${v}`);
  if (readOnly) {
    el.readOnly = true;
  }
  if (config.disabled) {
    el.disabled = true;
  }
  if (config.name) {
    el.name = config.name;
  }
  if (config.id) {
    el.id = config.id;
  }
  if (config.class) {
    el.classList.add(...config.class.split(/\s+/).filter(Boolean));
  }

  el.addEventListener('change', () => {
    config.onChange?.(el.modelValue);
  });

  return el;
}
