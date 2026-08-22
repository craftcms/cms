import {registerCraftGlobals} from '@/common/craft-global';
import {
  AssetSelectorController,
  ElementSelectorController,
  adoptLegacyRegistrations,
  hasElementSelectorController,
  registerElementSelectorController,
} from '@craftcms/ui';
import {createElementSelectorModal} from './create-element-selector-modal';
import {VolumeFolderSelectorModal} from './volume-folder-selector-modal';

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

/**
 * `Craft.*` stays the entry point for plugins and PHP-emitted boots
 * (`new Craft.VolumeFolderSelectorModal(…)` in `MoveAssets.php` and the legacy
 * `AssetIndex`).
 *
 * `createElementSelectorModal` is now **async** and resolves to a
 * controller-backed handle rather than a modal object — a deliberate break, as
 * the modal is no longer a class anyone can subclass. `registerElementSelectorController`
 * replaces `registerElementSelectorModalClass`; plugins register a controller
 * (business logic) and get both presentation layers for free.
 */
registerCraftGlobals({
  VolumeFolderSelectorModal,
  createElementSelectorModal,
  registerElementSelectorController,
  ElementSelectorController,
  AssetSelectorController,
});

export {createElementSelectorModal} from './create-element-selector-modal';
export type {
  ElementSelectorModalHandle,
  ElementSelectorModalSettings,
} from './create-element-selector-modal';
export {VolumeFolderSelectorModal} from './volume-folder-selector-modal';
export {default as ElementSelectorModal} from './ElementSelectorModal.vue';
export {useElementSelectorController} from './useElementSelectorController';
