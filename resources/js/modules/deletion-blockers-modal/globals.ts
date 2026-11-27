import {DeletionBlockersModal} from './deletion-blockers-modal';
import {registerCraftGlobals} from '@/common/craft-global';

// Blocker callbacks from the server call it as `Craft.DeletionBlockersModal`.
registerCraftGlobals({DeletionBlockersModal});

/**
 * Answers `@craftcms/ui` actions that check deletion blockers before they
 * run, such as an edit page's Delete action.
 */
window.addEventListener('craft:check-deletion-blockers', (event) => {
  const {detail} = event as CustomEvent<{
    type: string;
    id: number | string;
    result?: ReturnType<typeof DeletionBlockersModal.confirm>;
  }>;
  detail.result = DeletionBlockersModal.confirm(detail.type, detail.id);
});
