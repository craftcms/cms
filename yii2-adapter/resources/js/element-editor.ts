import {cpComponentRegistry} from '@/bootstrap/components';

cpComponentRegistry.register(
  'craft-legacy:element-editor',
  // Loads with the first legacy element editor, not with every page cp-compat
  // is on.
  () => import('./LegacyElementEditor.vue')
);
