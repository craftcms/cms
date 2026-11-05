import {PreviewFileModal} from './preview-file-modal';
import {registerCraftGlobals} from '@/common/craft-global';

// Assign the legacy `Craft.PreviewFileModal` global so PHP-emitted code and the
// still-legacy cp bundle keep working.
registerCraftGlobals({PreviewFileModal});
