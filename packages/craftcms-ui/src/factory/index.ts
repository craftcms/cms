// Client-side factory functions for the CP web components — the JS twin of the
// PHP `src/Cp/Components/*` builders. Each returns the (typed) custom element for
// a config object, jQuery-free. Names align to the element tags / PHP components.
//
// These modules deliberately import the component classes **type-only**. Pulling
// them in for their registration side effect would drag Lit into every consumer's
// bundle — including the legacy webpack `cp` bundle, which builds separately from
// Vite and would then put a second copy of Lit on the page (custom elements get
// re-registered and Lit's directives blow up with `_$AT is not a function`).
// Registration is the entry point's job: `@craftcms/ui` defines every element
// these factories create, and the CP loads it via `resources/js/cp.ts`. The
// factories only call `document.createElement()` when invoked, so an element that
// isn't defined yet simply upgrades once it is.
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
