import {registerCraftGlobals} from '@/common/craft-global';
import {
  CustomizeSourcesModal,
  PageSettingsModal,
  SourceDrag,
  Page,
  BaseSource,
  Source,
  CustomSource,
  Heading,
} from './customize-sources-modal';

// Assign the legacy `Craft.CustomizeSourcesModal` global plus its nested classes
// (`Craft.CustomizeSourcesModal.Page`, etc.) so PHP-emitted code and the
// still-legacy cp bundle keep working.
registerCraftGlobals({CustomizeSourcesModal});
Object.assign(CustomizeSourcesModal, {
  PageSettingsModal,
  SourceDrag,
  Page,
  BaseSource,
  Source,
  CustomSource,
  Heading,
});

export {
  CustomizeSourcesModal,
  PageSettingsModal,
  SourceDrag,
  Page,
  BaseSource,
  Source,
  CustomSource,
  Heading,
};
