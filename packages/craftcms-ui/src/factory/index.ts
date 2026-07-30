// Client-side factory functions for the CP web components — the JS twin of the
// PHP `src/Cp/Components/*` builders. Each returns the (typed) custom element for
// a config object, jQuery-free. Names align to the element tags / PHP components.
export {createSwitch, type SwitchConfig} from './create-switch.js';
export {createTextInput, type TextInputConfig} from './create-text-input.js';
export {
  createCopyTextPrompt,
  type CopyTextPromptConfig,
} from './create-copy-text-prompt.js';
export {
  createSlidePicker,
  type SlidePickerConfig,
} from './create-slide-picker.js';
export {
  createInputPassword,
  type InputPasswordConfig,
} from './create-input-password.js';
export {createInputColor, type InputColorConfig} from './create-input-color.js';
