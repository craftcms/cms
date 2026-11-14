import type CraftSlidePicker from '@craftcms/ui/components/slide-picker/slide-picker';
import type CraftSlidePickerButton from '@craftcms/ui/components/slide-picker-button/slide-picker-button';

export interface SlidePickerConfig {
  id?: string;
  class?: string;
  name?: string;
  min?: number | (() => number);
  max?: number | (() => number);
  step?: number;
  value?: number;
  label?: string;
  /**
   * Builds a `<craft-slide-picker-button>` instead: a small button that opens
   * a full-size picker in a popover, for a picker in a dense row. It's named
   * by `label`, or by `buttonLabel` when that adds context, and ignores
   * `labelledBy` and `describedBy`.
   */
  button?: boolean;
  buttonLabel?: string;
  labelledBy?: string;
  describedBy?: string;
  valueLabel?: (value: number) => string;
  readOnly?: boolean;
  disabled?: boolean;
  static?: boolean;
  onChange?: (value: number) => void;
}

/**
 * Creates a `<craft-slide-picker>` (segmented slider), or with `button`, a
 * `<craft-slide-picker-button>`. `name` is all it takes to post the value;
 * `onChange` fires on the element's `change` event.
 */
export function createSlidePicker(
  config: SlidePickerConfig & {button: true}
): CraftSlidePickerButton;
export function createSlidePicker(
  config?: SlidePickerConfig & {button?: false}
): CraftSlidePicker;
export function createSlidePicker(
  config?: SlidePickerConfig
): CraftSlidePicker | CraftSlidePickerButton;
export function createSlidePicker(
  config: SlidePickerConfig = {}
): CraftSlidePicker | CraftSlidePickerButton {
  const min =
    typeof config.min === 'function' ? config.min() : (config.min ?? 0);
  const max =
    typeof config.max === 'function' ? config.max() : (config.max ?? 100);
  const step = typeof config.step !== 'undefined' ? config.step : 10;
  const value = typeof config.value === 'number' ? config.value : min;
  const readOnly = !!(config.readOnly || config.disabled || config.static);

  let el: CraftSlidePicker | CraftSlidePickerButton;
  if (config.button) {
    const button = document.createElement('craft-slide-picker-button');
    if (config.buttonLabel) {
      button.buttonLabel = config.buttonLabel;
    }
    button.readonly = readOnly;
    el = button;
  } else {
    const picker = document.createElement('craft-slide-picker');
    if (config.labelledBy) {
      picker.setAttribute('labelled-by', config.labelledBy);
    }
    if (config.describedBy) {
      picker.setAttribute('described-by', config.describedBy);
    }
    if (readOnly) {
      picker.readOnly = true;
    }
    el = picker;
  }
  el.min = min;
  el.max = max;
  el.step = step;
  el.value = value || 0;
  if (config.label) {
    el.label = config.label;
  }
  el.valueLabel = config.valueLabel || ((v) => `${v}`);
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
    config.onChange?.(el.value);
  });

  return el;
}
