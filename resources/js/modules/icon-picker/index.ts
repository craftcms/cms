import CraftIconPicker from './icon-picker.ce';
import {defineElement} from '@/common/web-components';

// Register the declarative element so Twig can emit `<craft-icon-picker>` (and
// the Craft.ui.createIconPicker factory can build it) instead of the legacy
// `new Craft.IconPicker(...)` boot. No legacy `Craft.IconPicker` global is
// assigned here — the class is gone; a deprecation shim for stray plugin callers
// lives in the yii2-adapter cpcompat bundle.
defineElement('craft-icon-picker', CraftIconPicker);

export {CraftIconPicker};
