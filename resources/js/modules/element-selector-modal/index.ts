import './globals';
import {
  AssetSelectorController,
  adoptLegacyRegistrations,
  hasElementSelectorController,
  registerElementSelectorController,
} from '@craftcms/ui';

const ASSET = 'CraftCms\\Cms\\Asset\\Elements\\Asset';

// The legacy bundle is a plain `<script>` and runs before this module, so a
// plugin can have registered a class before there was anywhere modern to put it.
adoptLegacyRegistrations();

// Assets get transform support. Skipped when something got there first, so a
// plugin can replace the built-in behavior without the duplicate-registration
// throw.
if (!hasElementSelectorController(ASSET)) {
  registerElementSelectorController(ASSET, AssetSelectorController);
}

export {createElementSelectorModal} from './create-element-selector-modal';
export type {
  ElementSelectorModalHandle,
  ElementSelectorModalSettings,
} from './create-element-selector-modal';
export {VolumeFolderSelectorModal} from './volume-folder-selector-modal';

// `ElementSelectorModal.vue` and `useElementSelectorController` are deliberately
// NOT re-exported here. `cp.ts` imports this module for its side effects, and a
// static re-export would pull the Vue view — and with it the whole element-index
// component tree — into every CP page, which is exactly what the factory's lazy
// `import()` exists to avoid. Import them from their own modules.
