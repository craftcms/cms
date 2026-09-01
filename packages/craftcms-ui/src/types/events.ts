import type {AsyncState} from '@src/types/index';
import type {FeedbackData} from '@src/actions';

interface ActionPluginRemoveDetails {
  packageName: string;
}

interface ActionStateChangeDetails extends FeedbackData {
  state: AsyncState;
}

declare global {
  interface WindowEventMap {
    'action:remove-plugin': CustomEvent<ActionPluginRemoveDetails>;
    'action:state-change': CustomEvent<ActionStateChangeDetails>;
  }
}

export {};
