import {registerCraftGlobals} from '@/common/craft-global';
import {LegacyPreview, mirrorPreviews} from './legacy-preview';

registerCraftGlobals({Preview: LegacyPreview});
mirrorPreviews();
