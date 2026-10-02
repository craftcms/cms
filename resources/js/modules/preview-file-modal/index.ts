import {registerCraftGlobals} from '@/common/craft-global';
import {PreviewFileModal} from './preview-file-modal';

// Assign the legacy `Craft.PreviewFileModal` global so PHP-emitted code and the
// still-legacy cp bundle keep working.
registerCraftGlobals({PreviewFileModal});

export {PreviewFileModal};
