import {registerCraftGlobals} from '@/common/craft-global';
import {
  LegacyPreview,
  mirrorPreviews,
  refreshClassicLivePreviews,
} from './legacy-preview';

registerCraftGlobals({Preview: LegacyPreview});
mirrorPreviews();
window.addEventListener('craft:refresh-previews', refreshClassicLivePreviews);
