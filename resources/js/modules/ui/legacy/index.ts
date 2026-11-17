// The builders behind the legacy `Craft.ui.create*()` methods. They only import
// component types, so the legacy bundle can use them without registering a
// second copy of any component.
export {createInputColor, type InputColorConfig} from './input-color';
export {createInputPassword, type InputPasswordConfig} from './input-password';
export {createSlidePicker, type SlidePickerConfig} from './slide-picker';
export {createSwitch, type SwitchConfig} from './switch';
export {createTextInput, type TextInputConfig} from './text-input';
