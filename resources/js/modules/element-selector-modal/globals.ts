import {
  AssetSelectorController,
  ElementSelectorController,
  registerElementSelectorController,
} from '@craftcms/ui';
import {createElementSelectorModal} from './create-element-selector-modal';
import {VolumeFolderSelectorModal} from './volume-folder-selector-modal';
import {registerCraftGlobals} from '@/common/craft-global';

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
